<?php
// +----------------------------------------------------------------------
// | LikeShop有特色的全开源社交分销电商系统
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 商业用途务必购买系统授权，以免引起不必要的法律纠纷
// | 禁止对系统程序代码以任何目的，任何形式的再发布
// | 微信公众号：好象科技
// | 访问官网：http://www.likemarket.net
// | 访问社区：http://bbs.likemarket.net
// | 访问手册：http://doc.likemarket.net
// | 好象科技开发团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | Author: LikeShopTeam-段誉
// +----------------------------------------------------------------------


namespace app\api\logic;


use app\common\basics\Logic;
use app\common\enum\FootprintEnum;
use app\common\model\Cart;
use app\common\model\goods\Goods;

class CartLogic extends Logic
{

    /**
     * Notes: 列表
     * @param $user_id
     * @author 段誉(2021/5/11 15:44)
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function lists($user_id)
    {
        $carts = Cart::with(['goods', 'goods_item', 'shop'])
            ->where('user_id', $user_id)
            ->order('id desc')
            ->select()->toArray();

        $goods_num = 0;
        $total = 0;
        $lists = [];

        $shop_ids = array_unique(array_column($carts, 'shop_id'));

        foreach ($shop_ids as $shop_id) {

            $lists[$shop_id]['is_selected'] = 1;
            $shop_info = $cart_lists = [];

            foreach ($carts as $k => $cart) {
                if ($shop_id != $cart['shop_id']) {
                    continue;
                }
                if (empty($shop_info)) {
                    $shop_info = [
                        'shop_id'   => $cart['shop']['id'],
                        'shop_name' => $cart['shop']['name'],
                        'type'      => $cart['shop']['type'],
                    ];
                }

                $sub_price = 0;
                if ($cart['selected'] == 1 && $cart['goods']['status'] == 1 && $cart['goods']['del'] == 0) {
                    $goods_num += $cart['goods_num'];
                    $total += $cart['goods_item']['price'] * $cart['goods_num'];
                    $sub_price = round($cart['goods_item']['price'] * $cart['goods_num'], 2);
                } else {
                    $cart['selected'] = 0;
                }

                if ($cart['selected'] == 0 && $cart['goods']['status'] == 1 && $cart['goods']['del'] == 0) {
                    $lists[$shop_id]['is_selected'] = 0;
                }

                $cart_lists[] = [
                    'cart_id'           => $cart['id'],
                    'goods_id'          => $cart['goods_id'],
                    'goods_name'        => $cart['goods']['name'],
                    'image'             => empty($cart['goods_item']['image']) ? $cart['goods']['image'] : $cart['goods_item']['image'],
                    'goods_num'         => $cart['goods_num'],
                    'goods_status'      => $cart['goods']['status'],
                    'goods_del'         => $cart['goods']['del'],
                    'spec_value_str'    => $cart['goods_item']['spec_value_str'],
                    'price'             => $cart['goods_item']['price'],
                  	'integral_ratio'        => $cart['goods']['integral_ratio'],
                    'stock'             => $cart['goods_item']['stock'],
                    'selected'          => intval($cart['selected']),
                    'item_id'           => $cart['item_id'],
                    'sub_price'         => $sub_price
                ];
            }
            $lists[$shop_id]['shop'] = $shop_info;
            $lists[$shop_id]['cart'] = $cart_lists;
        }

        return [
            'lists' => array_values($lists),
            'total_amount' => round($total, 2),
            'total_num' => $goods_num,
        ];
    }



    /**
     * Notes: 添加
     * @param $post
     * @param $user_id
     * @return bool
     * @author 段誉(2021/5/10 19:03)
     */
    public static function add($post, $user_id)
    {
        try {
            $item_id = $post['item_id'];
            $goods_num = $post['goods_num'];

            $cart = Cart::where(['user_id' => $user_id, 'item_id' => $item_id])->find();
            $cart_num = $post['goods_num'] + (isset($cart) ? $cart['goods_num'] : 0);

            $goods = self::checkCartGoods($item_id, $cart_num);
            if (false === $goods) {
                throw new \Exception(self::getError() ?: '商品信息错误');
            }

            if ($cart) {
                //购物车内已有该商品
                Cart::where(['id' => $cart['id'], 'shop_id' => $goods['shop_id']])
                    ->update(['goods_num' => $goods_num + $cart['goods_num']]);
            } else {
                //新增购物车记录
                Cart::create([
                    'user_id' => $user_id,
                    'goods_id' => $goods['id'],
                    'goods_num' => $goods_num,
                    'item_id' => $item_id,
                    'shop_id'  => $goods['shop_id'],
                ]);
            }

            // 记录访问足迹
            event('Footprint', [
                'type'    => FootprintEnum::ADD_CART,
                'user_id' => $user_id,
                'foreign_id' => $goods['id']
            ]);

            return true;
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * Notes: 变动数量
     * @param $cart_id
     * @param $goods_num
     * @author 段誉(2021/5/11 11:59)
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function change($cart_id, $goods_num)
    {
        $cart = Cart::find($cart_id);
        $goods_num = ($goods_num <= 0) ? 1 : $goods_num;
        if (false === self::checkCartGoods($cart['item_id'], $goods_num)) {
            return false;
        }
        Cart::update(['goods_num' => $goods_num], ['id' => $cart_id]);
        return true;
    }


    /**
     * Notes: 删除
     * @param $cart_id
     * @param $user_id
     * @author 段誉(2021/5/11 12:02)
     * @return bool
     */
    public static function del($cart_id, $user_id)
    {
        return Cart::where(['id' => $cart_id, 'user_id' => $user_id])->delete();
    }


    /**
     * Notes: 更改选中状态
     * @param $post
     * @param $user_id
     * @author 段誉(2021/5/11 15:49)
     * @return Cart
     */
    public static function selected($post, $user_id)
    {
        return Cart::where(['user_id' => $user_id, 'id' => $post['cart_id']])
            ->update(['selected' => $post['selected']]);
    }


    /**
     * Notes: 购物车数量
     * @param $user_id
     * @author 段誉(2021/5/11 12:07)
     * @return array
     */
    public static function cartNum($user_id)
    {
        $cart = new Cart();
        $num = $cart->alias('c')
            ->join('goods g', 'g.id = c.goods_id')
            ->join('goods_item i', 'i.id = c.item_id')
            ->where(['g.status' => 1, 'g.del' => 0, 'c.user_id' => $user_id])
            ->sum('goods_num');
        return ['num' => $num ?? 0];
    }


    /**
     * Notes: 验证商品
     * @param $item_id
     * @param $goods_num
     * @author 段誉(2021/5/11 11:59)
     * @return bool
     */
    public static function checkCartGoods($item_id, $goods_num)
    {
        $goodsModel = new Goods();
        $goods = $goodsModel->alias('g')
            ->field('g.id, g.status, g.del,g.shop_id, i.stock')
            ->join('goods_item i', 'i.goods_id = g.id')
            ->where('i.id', $item_id)
            ->find()->toArray();

        if (empty($goods) || $goods['status'] == 0 || $goods['del'] != 0) {
            self::$error = '商品不存在或已下架';
            return false;
        }
        if ($goods['stock'] < $goods_num) {
            self::$error = '很抱歉,库存不足';
            return false;
        }
        return $goods;
    }


}