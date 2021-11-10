<?php
// +----------------------------------------------------------------------
// | LikeShop100%开源免费商用电商系统
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | 商业版本务必购买商业授权，以免引起法律纠纷
// | 禁止对系统程序代码以任何目的，任何形式的再发布
// | Gitee下载：https://gitee.com/likeshop_gitee/likeshop
// | 访问官网：https://www.likemarket.net
// | 访问社区：https://home.likemarket.net
// | 访问手册：http://doc.likemarket.net
// | 微信公众号：好象科技
// | 好象科技开发团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------

// | Author: LikeShopTeam
// +----------------------------------------------------------------------


namespace app\common\server;


use app\common\enum\NoticeEnum;
use think\facade\Db;

class DistributionServer
{
    public static function commission($order_id)
    {
        $field = [
            'o.user_id' => 'user_id',
            'og.id' => 'order_goods_id',
            'og.goods_num' => 'goods_num',
            'og.total_pay_price' => 'total_price',
            'g.first_ratio' => 'first_ratio',
            'g.second_ratio' => 'second_ratio',
            'g.third_ratio' => 'third_ratio',
            'u.first_leader' => 'first_leader',
            'u.second_leader' => 'second_leader',
            'u.third_leader' => 'third_leader',
        ];
        $goods_lists = Db::name('order_goods')
            ->alias('og')
            ->join('goods g', 'og.goods_id=g.id')
            ->join('order o', 'og.order_id=o.id')
            ->join('user u', 'o.user_id=u.id')
            ->field($field)
            ->where('o.id', $order_id)
            ->where('g.is_distribution', 1)
            ->select();
        if (empty($goods_lists)) {
            //无商品需要分佣
            return;
        }

        //按商品分佣
        $time = time();
        foreach ($goods_lists as $goods) {

            //一级分佣
            if ($goods['first_ratio'] <= 0 || $goods['first_ratio'] >= 100) {
                //设置为0，不分佣
                continue;
            }
            if (empty($goods['first_leader'])) {
                //无上级，无需分佣
                continue;
            }
            $money = $goods['total_price'] * $goods['first_ratio'] / 100;
            if ($money < 0.01) {
                //金额小于0.01分佣
                continue;
            }
            self::createOrder($goods['first_leader'], $goods['order_goods_id'], $goods['goods_num'], $money, $time);
        }

        foreach ($goods_lists as $goods) {
            //二级分佣
            if ($goods['second_ratio'] <= 0 || $goods['second_ratio'] >= 100) {
                //设置为0，不分佣
                continue;
            }
            $money = $goods['total_price'] * $goods['second_ratio'] / 100;
            if ($money < 0.01) {
                //金额小于0.01分佣
                continue;
            }
            if (empty($goods['second_leader'])) {
                //无上级，无需分佣
                continue;
            }
            self::createOrder($goods['second_leader'], $goods['order_goods_id'], $goods['goods_num'], $money, $time);

        }

        foreach ($goods_lists as $goods) {
            //三级分佣
            if ($goods['three_ratio'] <= 0) {
                //设置为0，不分佣
                continue;
            }
            $money = $goods['total_price'] * $goods['three_ratio'] / 100;
            if ($money < 0.01) {
                //金额小于0.01分佣
                continue;
            }
            if (empty($goods['third_leader'])) {
                //无上级，无需分佣
                continue;
            }
            self::createOrder($goods['third_leader'], $goods['order_goods_id'], $goods['goods_num'], $money, $time);
        }


    }

    /**
     * 创建分佣订单
     * @param $leader_id
     * @param $source_id
     * @param $goods_num
     * @param $money
     * @param $create_time
     */
    private static function createOrder($leader_id, $source_id, $goods_num, $money, $create_time)
    {
        $data = [
            'sn' => createSn('distribution_order_goods','sn'),
            'user_id' => $leader_id,
            'order_goods_id' => $source_id,
            'goods_num' => $goods_num,
            'money' => $money,
            'status' => 1,
            'create_time' => $create_time,
        ];
        Db::name('distribution_order_goods')
            ->insert($data);

        event('Notice', [
            'scene'  => NoticeEnum::GET_FUTURE_EARNINGS_NOTICE,
            'params' => [
                'user_id' => $leader_id,
                'future_earnings' => $money,
                'time' => date('Y-m-d H:i:s', time())
            ]
        ]);

    }
}