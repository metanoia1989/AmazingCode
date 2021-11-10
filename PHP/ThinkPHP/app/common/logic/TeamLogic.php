<?php


namespace app\api\logic;


use app\common\basics\Logic;
use app\common\enum\OrderEnum;
use app\common\enum\TeamEnum;
use app\common\model\goods\Goods;
use app\common\model\goods\GoodsItem;
use app\common\model\order\Order;
use app\common\model\order\OrderGoods;
use app\common\model\order\OrderTrade;
use app\common\model\shop\Shop;
use app\common\model\team\TeamActivity;
use app\common\model\team\TeamFound;
use app\common\model\team\TeamJoin;
use app\common\model\user\User;
use Exception;
use think\facade\Db;

class TeamLogic extends Logic
{
    /**
     * @Notes: 获取拼团活动
     * @Author: 张无忌
     * @param array $get
     * @return array|bool
     */
    public static function activity(array $get)
    {
        try {
            $pageNo   = $get['page_no'] ?? 1;
            $pageSize = $get['page_size'] ?? 20;

            $model = new TeamActivity();
            $lists = $model->alias('T')->field([
                    'T.id,T.goods_id,T.people_num,T.team_max_price,T.team_min_price,sales_volume',
                    'G.name,G.image,G.max_price,G.min_price,G.market_price'
                ])
                ->where([
                    ['T.audit', '=', 1],
                    ['T.status', '=', 1],
                    ['T.del', '=', 0],
                    ['T.activity_start_time', '<=', time()],
                    ['T.activity_end_time', '>=', time()],
                    ['S.is_freeze', '=', 0],
                    ['S.is_run', '=', 1],
                ])
                ->join('goods G', 'G.id = T.goods_id')
                ->join('shop S', 'S.id = T.shop_id')
                ->paginate([
                    'page'      => $pageNo,
                    'list_rows' => $pageSize,
                    'var_page'  => 'page'
                ])->toArray();

            return [
                'list'      => $lists['data'],
                'count'     => $lists['total'],
                'more'      => is_more($lists['total'], $pageNo, $pageSize),
                'page_no'   => $pageNo,
                'page_size' => $pageSize
            ];
        } catch (Exception $e) {
            static::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @Notes: 开团信息
     * @Author: 张无忌
     * @param $post
     * @param $user_id
     * @return bool|array
     */
    public static function kaituanInfo($post, $user_id)
    {
        try {
            $teamActivity = (new TeamActivity())->alias('T')
               ->field([
                   'T.id as team_activity_id,T.shop_id,T.effective_time,GI.goods_id,GI.id as item_id,T.people_num,TG.team_price',
                   'G.name,G.image,GI.spec_value_str,GI.spec_value_ids,GI.market_price',
                   'GI.price,GI.stock'
               ])->where([
                    ['T.goods_id', '=', (int)$post['goods_id']],
                    ['T.audit', '=', 1],
                    ['T.status', '=', 1],
                    ['T.del', '=', 0],
                    ['T.activity_start_time', '<=', time()],
                    ['T.activity_end_time', '>=', time()],
                    ['TG.goods_id', '=', (int)$post['goods_id']],
                    ['TG.item_id', '=', (int)$post['item_id']],
               ])->join('team_goods TG', 'TG.team_id = T.id')
                 ->join('goods G', 'G.id = TG.goods_id')
                 ->join('goods_item GI', 'GI.id = TG.item_id')
                 ->findOrEmpty()->toArray();

            if (!$teamActivity) throw new \think\Exception('当前商品未参与拼团活动，下次再来吧');
            if ($teamActivity['stock'] - intval($post['count']) < 0) throw new \think\Exception('抱歉,库存不足');

            if (empty($post['address_id']) || !$post['address_id']) {
                $address = UserAddressLogic::getDefaultAddress($user_id);
            } else {
                $address = UserAddressLogic::getOneAddress($user_id, ['id'=>$post['address_id']]);
            }

            $user    = (new User())->findOrEmpty($user_id)->toArray();
            $shop    = (new Shop())->field(['id as shop_id,name as shop_name'])->findOrEmpty($teamActivity['shop_id'])->toArray();
            $shop['distribution_type'] = 0;
            $shop['distribution_type_text'] = '快递';
            $teamActivity['count'] = $post['count'];
            $shop['goods'][] = $teamActivity;

            return [
                'team_id'        => $post['team_id'] ?? 0,
                'pay_way'        => $post['pay_way'] ?? 1,
                'order_type'     => OrderEnum::TEAM_ORDER,
                'total_amount'   => round($teamActivity['team_price'] * $post['count'], 2),
                'total_count'    => intval($post['count']),
                'shipping_price' => 0,
                'user_money'     => $user['user_money'],
                'remark'         => $post['remark'] ?? '',
                'address'        => $address,
                'shop'           => $shop,
            ];
        } catch (Exception $e) {
            static::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @Notes: 发起开团/参团
     * @Author: 张无忌
     * @param $info
     * @param $user_id
     * @return bool|array
     */
    public static function kaituan($info, $user_id)
    {
        Db::startTrans();
        try {
            $time = time();
            $teamGoods = $info['shop']['goods'][0];

            // 参团验证
            if ($info['team_id']) {
                $teamFound = (new TeamFound())->where(['id'=>$info['team_id']])->findOrEmpty()->toArray();
                if (!$teamFound) throw new \think\Exception('选择的团不存在');
                if ($teamFound['status'] != 0) throw new \think\Exception('当前拼团已结束，请重新选择拼团');
                if ($teamFound['invalid_time'] <= time()) throw new \think\Exception('当前拼团已结束，请重新选择拼团');
                if ($teamFound['user_id'] == $user_id) throw new \think\Exception('您已是该团成员了,不能重复参团哦！');
                if ($teamFound['people'] == $teamFound['join']) throw new \think\Exception('当前拼团已满员，请重新选择拼团！');

                // 获取已参团记录
                $people = (new TeamJoin())->where(['team_id'=>$info['team_id'], 'user_id'=>$user_id])->findOrEmpty()->toArray();
                if ($people) throw new \think\Exception('您已是该团成员了,不能重复参团哦！');
            }

            // 验证收货地址
            if (empty($info['address']) || !$info['address']) {
                throw new \think\Exception('请选择收货地址');
            }

            // 创建交易单
            $trade = OrderTrade::create([
                't_sn'            => createSn('order_trade', 't_sn'),
                'user_id'         => $user_id,
                'goods_price'     => $info['total_amount'],
                'order_amount'    => $info['total_amount'],
                'total_amount'    => $info['total_amount'],
                'shop_id'         => $info['shop']['shop_id'],
                'create_time'     => $time
            ]);

            // 创建订单
            $order = Order::create([
                'trade_id'       => $trade['id'],
                'shop_id'        => $info['shop']['shop_id'],
                'order_sn'       => createSn('order', 'order_sn'),
                'user_id'        => $user_id,
                'order_type'     => OrderEnum::TEAM_ORDER,
                'delivery_type'  => $info['shop']['distribution_type'],
                'pay_way'        => $info['pay_way'],
                'consignee'      => $info['address']['contact'],
                'province'       => $info['address']['province_id'],
                'city'           => $info['address']['city_id'],
                'district'       => $info['address']['district_id'],
                'address'        => $info['address']['address'],
                'mobile'         => $info['address']['telephone'],
                'goods_price'    => $info['total_amount'],
                'total_amount'   => $info['total_amount'],
                'order_amount'   => $info['total_amount'],
                'total_num'      => $info['total_count'],
                'shipping_price' => $info['shipping_price'],
                'discount_amount' => 0,
                'user_remark'    => $info['remark'] ?? '',
                'create_time'    => $time
            ]);

            // 创建订单商品
            OrderGoods::create([
                'order_id'        => $order['id'],
                'goods_id'        => $teamGoods['goods_id'],
                'item_id'         => $teamGoods['item_id'],
                'goods_num'       => $teamGoods['count'],
                'goods_name'      => $teamGoods['name'],
                'goods_price'     => $teamGoods['team_price'],
                'total_price'     => $teamGoods['team_price'],
                'total_pay_price' => $teamGoods['team_price'],
                'discount_price'  => 0,
                'spec_value'      => $teamGoods['spec_value_str'],
                'spec_value_ids'  => $teamGoods['spec_value_ids'],
                'image'           => $teamGoods['image'],
                'shop_id'         => $info['shop']['shop_id']
            ]);

            // 开新团
            $team_id = 0;
            if (!$info['team_id']) {
                $teamFound = TeamFound::create([
                    'shop_id'          => $info['shop']['shop_id'],
                    'team_activity_id' => $teamGoods['team_activity_id'],
                    'team_sn'          => createSn('team_found', 'team_sn'),
                    'user_id'          => $user_id,
                    'status'           => 0,
                    'join'             => 0,
                    'people'           => $teamGoods['people_num'],
                    'goods_snap'       => json_encode([
                        'id'      => $teamGoods['goods_id'],
                        'shop_id' => $teamGoods['shop_id'],
                        'name'    => $teamGoods['name'],
                        'image'   => $teamGoods['image']
                    ]),
                    'kaituan_time'     => $time,
                    'invalid_time'     => ($teamGoods['effective_time'] * 60 * 60) + time()
                ]);
                $team_id = $teamFound['id'];
            }

            // 加入团
            TeamJoin::create([
                'shop_id'          => $info['shop']['shop_id'],
                'team_activity_id' => $teamGoods['team_activity_id'],
                'team_id'          => $team_id ?: $info['team_id'],
                'sn'               => createSn('team_join', 'sn'),
                'user_id'          => $user_id,
                'order_id'         => $order['id'],
                'identity'         => $info['team_id'] ? 2 : 1,
                'team_snap'        => json_encode($info['shop']['goods'][0], JSON_UNESCAPED_UNICODE),
                'create_time'      => $time,
                'update_time'      => $time
            ]);

            // 扣减库存
            (new GoodsItem())->where([
                'goods_id'=>$teamGoods['goods_id'],
                'id'      =>$teamGoods['item_id']
            ])->update(['stock' => ['dec', $teamGoods['count']]]);

            (new Goods())->where([
                'id'=>$teamGoods['goods_id']
            ])->update(['stock' => ['dec', $teamGoods['count']]]);

            // 更新参数人数
            TeamFound::update([
                'join' => ['inc', 1]
            ], ['id'=>$team_id ?: $info['team_id']]);

            // 更新活动拼团数
            TeamActivity::update([
                'sales_volume' => ['inc', 1]
            ], ['id'=>$teamGoods['team_activity_id']]);

            Db::commit();
            return [
                'team_id'  => $team_id ?: $info['team_id'],
                'type'     => 'trade',
                'trade_id' => $trade['id'],
                'order_id' => $order['id']
            ];
        } catch (Exception $e) {
            Db::rollback();
            static::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @Notes: 获取拼团记录
     * @Author: 张无忌
     * @param $get
     * @param $user_id
     * @return array|bool
     */
    public static function record($get, $user_id)
    {
        try {
            $pageNo   = $get['page_no'] ?? 1;
            $pageSize = $get['page_size'] ?? 20;

            $where = [];
            if (isset($get['type']) and $get['type'] >= 0) {
                $type = intval($get['type']);
                $where[] = ['TJ.status', '=', $type];
            }

            $model = new TeamJoin();
            $lists = $model->alias('TJ')->field(['TJ.*,S.name as shop_name,O.order_amount'])
                ->where(['TJ.user_id'=>$user_id])
                ->order('id desc')
                ->where($where)
                ->join('Shop S', 'S.id = TJ.shop_id')
                ->join('Order O', 'O.id = TJ.order_id')
                ->paginate([
                    'page'      => $pageNo,
                    'list_rows' => $pageSize,
                    'var_page'  => 'page'
                ])->toArray();

            $data = [];
            foreach ($lists['data'] as &$item) {
                $item['team_snap'] = json_decode($item['team_snap'], true);
                $data[] = [
                    'id' => $item['id'],
                    'order_id'   => $item['order_id'],
                    'shop_name'  => $item['shop_name'],
                    'people_num' => $item['team_snap']['people_num'],
                    'name'       => $item['team_snap']['name'],
                    'image'      => $item['team_snap']['image'],
                    'price'      => $item['team_snap']['price'],
                    'count'      => $item['team_snap']['count'],
                    'spec_value_str' => $item['team_snap']['spec_value_str'],
                    'order_amount'   => $item['order_amount'],
                    'status'         => $item['status'],
                    'identity'       => $item['identity'],
                    'identity_text'  => $item['identity'] == 1 ? '团长' : '团员',
                    'status_text'    => TeamEnum::getStatusDesc($item['status'])
                ];
            }

            return [
                'list'      => $data,
                'count'     => $lists['total'],
                'more'      => is_more($lists['total'], $pageNo, $pageSize),
                'page_no'   => $pageNo,
                'page_size' => $pageSize
            ];

        } catch (Exception $e) {
            static::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @Notes: 验证团
     * @Author: 张无忌
     * @param $post
     * @param $user_id
     * @return bool
     */
    public static function check($post, $user_id)
    {
        try {
            $teamActivity = (new TeamActivity())->alias('T')
                ->field([
                    'T.id as team_activity_id,T.shop_id,T.effective_time,GI.goods_id,GI.id as item_id,T.people_num,TG.team_price',
                    'G.name,G.image,GI.spec_value_str,GI.spec_value_ids,GI.market_price',
                    'GI.price,GI.stock'
                ])->where([
                    ['T.goods_id', '=', (int)$post['goods_id']],
                    ['T.audit', '=', 1],
                    ['T.status', '=', 1],
                    ['T.del', '=', 0],
                    ['T.activity_start_time', '<=', time()],
                    ['T.activity_end_time', '>=', time()],
                    ['TG.goods_id', '=', (int)$post['goods_id']],
                    ['TG.item_id', '=', (int)$post['item_id']],
                ])->join('team_goods TG', 'TG.team_id = T.id')
                ->join('goods G', 'G.id = TG.goods_id')
                ->join('goods_item GI', 'GI.id = TG.item_id')
                ->findOrEmpty()->toArray();

            if (!$teamActivity) throw new \think\Exception('当前商品未参与拼团活动，下次再来吧');
            if ($teamActivity['stock'] - intval($post['count']) < 0) throw new \think\Exception('抱歉,库存不足');

            // 参团验证
            if (!empty($post['team_id']) and $post['team_id']) {
                $teamFound = (new TeamFound())->where(['id'=>$post['team_id']])->findOrEmpty()->toArray();
                if (!$teamFound) throw new \think\Exception('选择的团不存在');
                if ($teamFound['status'] != 0) throw new \think\Exception('当前拼团已结束，请重新选择拼团');
                if ($teamFound['invalid_time'] <= time()) throw new \think\Exception('当前拼团已结束，请重新选择拼团');
                if ($teamFound['user_id'] == $user_id) throw new \think\Exception('您已是该团成员了,不能重复参团哦！');
                if ($teamFound['people'] == $teamFound['join']) throw new \think\Exception('当前拼团已满员，请重新选择拼团！');

                // 获取已参团记录
                $people = (new TeamJoin())->where(['team_id'=>$post['team_id'], 'user_id'=>$user_id])->findOrEmpty()->toArray();
                if ($people) throw new \think\Exception('您已是该团成员了,不能重复参团哦！');
            }


            return true;
        } catch (Exception $e) {
            static::$error = $e->getMessage();
            return false;
        }
    }
}