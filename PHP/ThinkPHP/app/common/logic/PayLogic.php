<?php

namespace app\api\logic;

use app\common\basics\Logic;
use app\common\enum\NoticeEnum;
use app\common\enum\OrderEnum;
use app\common\enum\PayEnum;
use app\common\model\distribution\DistributionOrderGoods;
use app\common\model\goods\Goods;
use app\common\model\AccountLog;
use app\common\logic\AccountLogLogic;
use app\common\model\order\OrderGoods;
use app\common\model\order\OrderTrade;
use app\common\model\order\Order;
use app\common\model\RechargeOrder;
use app\common\model\shop\Shop;
use app\common\model\user\User;
use app\common\server\JsonServer;
use app\common\server\WeChatPayServer;
use app\common\server\ConfigServer;
use think\facade\Db;
use app\common\server\AliPayServer;

/**
 * Class PayLogic
 * @package app\api\logic
 */
class PayLogic extends Logic
{
    /**
     * @notes 检验支付状态
     * @param $trade_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:24 下午
     */
    public static function checkPayStatus($trade_id)
    {

        $where = [
            'trade_id' => $trade_id,
            'pay_status' => PayEnum::ISPAID,
            'del' => 0
        ];

        $check = Order::where($where)->find();
        if ($check) {
            return true;
        }
        return false;

    }

    /**
     * @notes 余额支付
     * @param $order_id
     * @param $form
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\PDOException
     * @author suny
     * @date 2021/7/13 6:24 下午
     */
    public static function balancePay($order_id, $form)
    {

        switch ($form) {
            case "trade":
                $order = Order::where(['trade_id' => $order_id])->find();
                if (self::checkPayStatus($order_id)) {
                    $order['pay_status'] = PayEnum::ISPAID;
                }
                break;
            case "order":
                $order = Order::where([
                    ['del', '=', 0],
                    ['id', '=', $order_id]
                ])->find();
                break;
        }
        if (empty($order)) {
            return JsonServer::error('订单不存在');
        }
        if (isset($order['pay_status']) && $order['pay_status'] == PayEnum::ISPAID) {
            return JsonServer::error('订单已支付');
        }
        $user_balance = User::where(['id' => $order['user_id']])->value('user_money');
        if ($user_balance < $order['order_amount']) {
            return JsonServer::error('余额不足');
        }
        try {
            Db::startTrans();
            $User = new User();
            if ($order['order_amount'] != 0) {
                $user_balance_dec = User::where(['id' => $order['user_id']])
                    ->dec('user_money', $order['order_amount'])
                    ->update();
                if (!$user_balance_dec) {
                    Db::rollback();
                    return JsonServer::error('余额扣除失败');
                }
            }

            //记录余额
            $acountLogResult = AccountLogLogic::AccountRecord($order['user_id'], $order['order_amount'], 2, AccountLog::balance_pay_order);
            if ($acountLogResult === false) {
                Db::rollback();
                return JsonServer::error('账户明细记录添加失败');
            }

            if ($form == "trade") {
                $order_id = Order::where('trade_id', $order_id)->column('id');
            }
            $orderStatusChange = self::changOrderStatus($order_id);
            if ($orderStatusChange == false) {
                Db::rollback();
                return JsonServer::error('子订单状态改变失败');
            }

            if ($User->confirmDistribution($order['user_id'])) {//该用户进行分销
                $distribution_order_goods_inster = self::distributionOrderGoods($order_id, $order['user_id']);
                if ($distribution_order_goods_inster === false) {
                    Db::rollback();
                    return JsonServer::error('商品分销记录失败');
                }
            }

            // 增加商品销量
            $order_goods = OrderGoods::where('order_id', $order['id'])->find();
            Goods::where('id', $order_goods['goods_id'])
                ->inc('sales_actual', $order['total_num'])
                ->update();

            //修改用户消费累计额度
            $user = User::find($order['user_id']);
            $user->total_order_amount = ['inc', $order['order_amount']];
            $user->save();

            //赠送成长值
            $growth_ratio = ConfigServer::get('transaction', 'money_to_growth', 0);
            if ($growth_ratio > 0) {
                $able_get_growth = floor($order['total_amount'] / $growth_ratio);
                $user->where('id', $order['user_id'])
                    ->inc('user_growth', $able_get_growth)
                    ->update();
                AccountLogLogic::AccountRecord($order['user_id'], $able_get_growth, 1, AccountLog::order_give_growth, '', $order['id'], $order['order_sn']);
            }


            //通知用户
            event('Notice', [
                'scene' => NoticeEnum::ORDER_PAY_NOTICE,
                'mobile' => $user['mobile'],
                'params' => ['order_id' => $order['id'], 'user_id' => $order['user_id']]
            ]);

            //通知商家
            if (!empty($order['shop']['mobile'])) {
                event('Notice', [
                    'scene' => NoticeEnum::USER_PAID_NOTICE_SHOP,
                    'mobile' => $order['shop']['mobile'],
                    'params' => ['order_id' => $order['id'], 'user_id' => $order['user_id']]
                ]);
            }


            Db::commit();
            return JsonServer::success('支付成功', [], 10001);
        } catch (\Exception $e) {
            Db::rollback();
            return JsonServer::error($e->getMessage());
        }
    }

    /**
     * @notes 微信支付
     * @param $order_id
     * @param $form
     * @param $client
     * @return \think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:24 下午
     */
    public static function wechatPay($order_id, $form, $client)
    {

        switch ($form) {
            case "trade":
                $order = OrderTrade::find($order_id);
                if (self::checkPayStatus($order_id)) {
                    $order['pay_status'] = PayEnum::ISPAID;
                }
                break;
            case "order":
                $order = Order::where([
                    ['del', '=', 0],
                    ['id', '=', $order_id]
                ])->find();
                break;
            case "recharge":
                $order = RechargeOrder::where([
                    ['id', '=', $order_id]
                ])->find();
                break;
        }
        if (empty($order)) {
            return JsonServer::error('订单不存在');
        }
        if (isset($order['pay_status']) && $order['pay_status'] == PayEnum::ISPAID) {
            return JsonServer::error('订单已支付');
        }
        // 这里进行微信支付
        $res = WeChatPayServer::unifiedOrder($form, $order, $client);
        if (false === $res) {
            return JsonServer::error(WeChatPayServer::getError());
        }
        if (is_object($res) || is_string($res)) {
            $res = (array)($res);
        }
        if (is_string($res)) {
            $data = [
                'code' => 1,
                'msg' => '微信支付发起成功',
                'show' => 0,
                'data' => $res
            ];
            return json($data);
        }
        return JsonServer::success('微信支付发起成功', $res, 1);
    }

    /**
     * @notes 支付宝支付
     * @param $order_id
     * @param $from
     * @param $client
     * @return bool|string
     * @author suny
     * @date 2021/7/27 4:22 下午
     */
    public static function aliPay($order_id , $from , $client)
    {
        $aliPay = new AliPayServer();
        $res = $aliPay->pay($from , $order_id , $client);
        return $res;
    }

    /**
     * order表状态改变
     */
    public static function changOrderStatus($order_id)
    {
        $where = ['id', '=', $order_id];
        if (is_array($order_id)) {
            $where = ['id', 'in', $order_id];
        }

        $orders = Order::where([ $where ])
            ->update([
                'pay_status' => PayEnum::ISPAID,
                'order_status' => OrderEnum::ORDER_STATUS_DELIVERY,
                'pay_way' => OrderEnum::PAY_WAY_BALANCE,
                'pay_time' => time()
            ]);

        if ($orders) {
            return true;
        }
        return false;
    }


    /**
     * @notes 分销商品记录
     * @param $order_id
     * @param $user_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author suny
     * @date 2021/7/13 6:24 下午
     */
    public static function distributionOrderGoods($order_id, $user_id)
    {

        $where = ['order_id', '=', $order_id];
        if (is_array($order_id)) {
            $where = ['order_id', 'in', $order_id];
        }

        $goods = OrderGoods::where([ $where ])
            ->field('id,goods_id,total_pay_price,goods_num,order_id,shop_id')
            ->select()->toArray();

        $User = new User();
        $Goods = new Goods();
        $user_leader = $User->where('id', $user_id)->field('first_leader,second_leader,third_leader')->find()->toArray();

        foreach ($goods as $key => $value) {
            // 商家是否开启分销
            $shop_is_distribution = Shop::where('id', $value['shop_id'])->value('is_distribution');

            if (!$shop_is_distribution) continue;
            // 商品是否开启分销
            if ($Goods->where(['id' => $value['goods_id'], 'is_distribution' => 1])->find()) {
                $goods_distribution = $Goods->where(['id' => $value['goods_id']])->field('first_ratio,second_ratio,third_ratio')->find()->toArray();

                if (!empty($user_leader['first_leader']) && !is_null($user_leader['first_leader'])) {
                    DistributionOrderGoods::create([
                        'sn' => createSn('distribution_order_goods', 'sn'),
                        'user_id' => $user_leader['first_leader'],
                        'real_name' => $User->where('id', $user_leader['first_leader'])->value('nickname') ?? '',
                        'order_id' => $value['order_id'],
                        'order_goods_id' => $value['id'],
                        'goods_num' => $value['goods_num'],
                        'money' => bcdiv(bcmul($value['total_pay_price'], $goods_distribution['first_ratio'], 2), 100, 2),
                        'status' => 1,
                        'shop_id' => $value['shop_id'],
                        'create_time' => time()
                    ]);
                }

                if (!empty($user_leader['second_leader']) && !is_null($user_leader['second_leader'])) {
                    DistributionOrderGoods::create([
                        'sn' => createSn('distribution_order_goods', 'sn'),
                        'user_id' => $user_leader['second_leader'],
                        'real_name' => $User->where('id', $user_leader['second_leader'])->value('nickname') ?? '',
                        'order_id' => $value['order_id'],
                        'order_goods_id' => $value['id'],
                        'goods_num' => $value['goods_num'],
                        'money' => bcdiv(bcmul($value['total_pay_price'], $goods_distribution['second_ratio'], 2), 100, 2),
                        'status' => 1,
                        'shop_id' => $value['shop_id'],
                        'create_time' => time()
                    ]);
                }

                if (!empty($user_leader['third_leader']) && !is_null($user_leader['third_leader'])) {
                    DistributionOrderGoods::create([
                        'sn' => createSn('distribution_order_goods', 'sn'),
                        'user_id' => $user_leader['third_leader'],
                        'real_name' => $User->where('id', $user_leader['third_leader'])->value('nickname') ?? '',
                        'order_id' => $value['order_id'],
                        'order_goods_id' => $value['id'],
                        'goods_num' => $value['goods_num'],
                        'money' => bcdiv(bcmul($value['total_pay_price'], $goods_distribution['third_ratio'], 2), 100, 2),
                        'status' => 1,
                        'shop_id' => $value['shop_id'],
                        'create_time' => time()
                    ]);
                }
            }
        }

        return true;
    }

}
