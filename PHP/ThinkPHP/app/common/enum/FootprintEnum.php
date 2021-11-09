<?php


namespace app\common\enum;


class FootprintEnum
{
    const ENTER_MALL     = 1;  //访问商城
    const BROWSE_GOODS   = 2;  //浏览商品
    const ADD_CART       = 3;  //加入购物车
    const RECEIVE_COUPON = 4;  //领取优惠券
    const PLACE_ORDER    = 5;  //商品下单

    /**
     * @Notes: 获取场景描述
     * @Author: 张无忌
     * @param bool $type
     * @return array|mixed|string
     */
    public static function getSceneDesc($type = true)
    {
        $desc = [
            self::ENTER_MALL      => '访问商城',
            self::BROWSE_GOODS    => '浏览商品',
            self::ADD_CART        => '加入购物车',
            self::RECEIVE_COUPON  => '领取优惠券',
            self::PLACE_ORDER     => '下单结算',
        ];
        if ($type === true) {
            return $desc;
        }
        return $desc[$type] ?? '未知';
    }
}