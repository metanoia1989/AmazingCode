<?php

namespace app\api\logic;

use app\api\controller\Seckill;
use app\common\basics\Logic;
use app\common\enum\ClientEnum;
use app\common\enum\FootprintEnum;
use app\common\enum\OrderEnum;
use app\common\enum\OrderGoodsEnum;
use app\common\enum\OrderLogEnum;
use app\common\enum\PayEnum;
use app\common\enum\TeamEnum;
use app\common\logic\OrderLogLogic;
use app\common\logic\OrderRefundLogic;
use app\common\logic\RefundLogic;
use app\common\model\Cart;
use app\common\model\Freight;
use app\common\model\FreightConfig;
use app\common\model\goods\Goods;
use app\common\model\Delivery;
use app\common\model\goods\GoodsItem;
use app\common\model\Order as CommonOrder;
use app\common\model\order\Order;
use app\common\model\order\OrderGoods;
use app\common\model\order\OrderLog;
use app\common\model\order\OrderRefund;
use app\common\model\order\OrderTrade;
use app\common\model\Pay;
use app\common\model\shop\Shop;
use app\common\model\coupon\Coupon;
use app\common\model\coupon\CouponGoods;
use app\common\model\coupon\CouponList;
use app\common\model\team\TeamActivity;
use app\common\model\team\TeamFound;
use app\common\model\team\TeamJoin;
use app\common\model\user\User;
use app\common\model\user\UserLevel;
use app\common\server\UrlServer;
use app\common\model\seckill\SeckillGoods;
use app\common\model\bargain\BargainLaunch;
use app\common\model\user\UserAddress;
use app\common\server\AreaServer;
use app\common\server\ConfigServer;
use app\common\server\JsonServer;
use expressage\Kd100;
use expressage\Kdniao;
use think\Exception;
use think\facade\Db;
use think\facade\Env;

/**
 * Class OrderLogic
 * @package app\api\logic
 */
class OrderLogic extends Logic
{
    public static $order_type = OrderEnum::NORMAL_ORDER;

    /**
     * @notes 下单
     * @param $post
     * @return array|false
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\PDOException
     * @author suny
     * @date 2021/7/13 6:19 下午
     */
    public static function add($post)
    {

        if (!empty($post['goods'])) {
            $goods = json_decode($post['goods'], true);
            $post['goods'] = $goods;
        } else {
            $where = [[
                'id', 'in', $post['cart_id']]
            ];
            $post['goods'] = $goods = Cart::where($where)
                ->field(['goods_id', 'item_id', 'shop_id', 'goods_num as num'])
                ->select()->toArray();
        }

        // 砍价订单验证
        if (isset($post['bargain_launch_id']) and $post['bargain_launch_id'] > 0) {
            self::$order_type = OrderEnum::BARGAIN_ORDER;
            $bargainLaunchModel = new BargainLaunch();
            $launch = $bargainLaunchModel->where(['id' => (int)$post['bargain_launch_id']])->find();
            if (!$launch) {
                throw new Exception('砍价异常');
            }
            if ($launch['status'] == 2) {
                throw new Exception('砍价失败,禁止下单');
            }
            if ($launch['payment_limit_time'] < time() and $launch['payment_limit_time'] > 0) {
                throw new Exception('下单失败,超出限制时间');
            }
            if ($launch['order_id'] > 0) {
                throw new Exception('您已下单了, 请勿重复操作');
            }
        }

        //检查商品上架状态及库存
        $check_goods = self::checkGoods($goods);
        Db::startTrans();
        try {
            if (false === $check_goods) {
                return false;
            }
            $address = UserAddress::where('id', $post['address_id'])
                ->field('contact,telephone,province_id,city_id,district_id,address')
                ->find();
            if (empty($address)) {
                throw new Exception('请选择地址');
            }

            $order_trade_add = self::addOrderTrade($post, $address);
            if (false === $order_trade_add) {
                throw new Exception('父订单创建失败');
            }
            $shop_goods = [];
            $order_goods_datas_insert = [];
            $order_log_datas_insert = [];
            foreach ($post['goods'] as $key => $value) { //按店铺区分商品
                $res = self::checkShop($value); //判断商家营业状态
                if ($res !== true) {
                    throw new Exception($res);
                }
                $shop_goods[$value['shop_id']][] = $value;
            }
            foreach ($shop_goods as $key => $value) {
                foreach ($value as $val) {
                    $seckill_goods_price = GoodsItem::isSeckill($val['item_id']);
                    if ($seckill_goods_price != 0) {//是秒杀商品
                        $sales_sum_res = self::setSeckillSaleSum($val['item_id'], $val['num']);
                        if ($sales_sum_res !== true) {
                            throw new Exception('秒杀商品销量设置失败');
                        }
                    }
                }

                $order_add = self::addOrder($order_trade_add, $value, $post, $key, $address);
                if (false === $order_add) {
                    throw new Exception('订单生成失败');
                }
                $order_log_add_data = self::getOrderLogData($key, $post['user_id'], $key);
                $order_log_datas_insert[] = $order_log_add_data;
                $order_goods_data = self::getOrderGoodsData($order_add, $value);
                $order_goods_datas_insert = array_merge($order_goods_datas_insert, $order_goods_data);
            }
            $order_log_insert = OrderLog::insertAll($order_log_datas_insert);
            if (false === $order_log_insert) {
                throw new Exception('订单日志生成失败');
            }
            $order_goods_inster = OrderGoods::insertAll($order_goods_datas_insert);
            if (false === $order_goods_inster) {
                throw new Exception('订单商品生成失败');
            }
            //商品库存减少
            $sub_goods_stock = self::subGoodsStock($post['goods']);
            if (false === $sub_goods_stock) {
                throw new Exception('库存修改失败');
            }
            //更改优惠券状态
            if (isset($post['coupon_id']) && !empty($post['coupon_id'])) {
                $coupon = self::editCoupon($post['coupon_id'], $order_add);
                if (false === $coupon) {
                    throw new Exception('优惠券修改失败');
                }
            }

            //购物车删除
            if (isset($post['cart_id']) && $post['cart_id'] != 0) {
                $delCart = self::delCart($post['cart_id']);
                if (false === $delCart) {
                    throw new Exception('购物车删除失败');
                }
            }

            // 砍价订单处理
            if (isset($post['bargain_launch_id']) and $post['bargain_launch_id'] > 0) {
                $bargainLaunchModel = new BargainLaunch();
                $bargainLaunchModel->where(['id' => (int)$post['bargain_launch_id']])
                    ->update(['order_id' => $order_add, 'status' => 1]);
            }


            Db::commit();
            return ['trade_id' => $order_trade_add, 'order_id' => $order_add, 'type' => 'trade'];
        } catch (Exception $e) {
            Db::rollback();
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @notes 结算页数据
     * @param $post
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:19 下午
     */
    public static function settlement($post)
    {
        if (!empty($post['goods'])) {
            $goods = json_decode($post['goods'], true);
            $post['goods'] = $goods;
        } else {
            $where = [[
                'id', 'in', $post['cart_id']]
            ];
            $post['goods'] = $goods = Cart::where($where)
                ->field(['goods_id', 'item_id', 'shop_id', 'goods_num as num'])
                ->select()->toArray();
        }

        // 检查店铺营业状态
        foreach($post['goods'] as $good) {
            $shop = Shop::field('expire_time,is_run,is_freeze')->where(['del' => 0, 'id' => $good['shop_id']])->findOrEmpty();
            if($shop->isEmpty()) {
                self::$error = '部分商品所属店铺不存在';
                return false;
            }
            // 获取原始数据(不经获取器)
            $shop = $shop->getData();
            if(!empty($shop['expire_time']) && ($shop['expire_time'] <= time())) {
                self::$error = '部分商品所属店铺已到期';
                return false;
            }
            if($shop['is_freeze']) {
                self::$error = '部分商品所属店铺已被冻结';
                return false;
            }
            if(!$shop['is_run']) {
                self::$error = '部分商品所属店铺暂停营业中';
                return false;
            }
        }
        $Goods = new Goods();
        $GoodsItem = new GoodsItem();
        $Shop = new Shop();
        if (isset($post['address_id']) && !empty($post['address_id'])) {
            $where = [
                'id' => $post['address_id'],
                'del' => 0,
            ];
            $address = UserAddress::where($where)
                ->field('id,contact,telephone,province_id,city_id,district_id,address')
                ->find()->toArray();
        } else {
            $address = UserAddress::where(['user_id' => $post['user_id'], 'is_default' => 1])
                ->field('id,contact,telephone,province_id,city_id,district_id,address')
                ->find();
        }
        if (!empty($address)) {
            $address['province'] = AreaServer::getAddress($address['province_id']);
            $address['city'] = AreaServer::getAddress($address['city_id']);
            $address['district'] = AreaServer::getAddress($address['district_id']);
        } else {
            $address = [];
        }
        foreach ($goods as &$good) {
            $goods_item = $GoodsItem
                ->where(['id' => $good['item_id'], 'goods_id' => $good['goods_id']])
                ->field('price,spec_value_str,image')
                ->find()
                ->toArray();
            $good['name'] = $Goods->getGoodsNameById($good['goods_id']);
            $good['price'] = $goods_item['price'];
            $good['spec_value'] = $goods_item['spec_value_str'];
            $good['image'] = $goods_item['image'];

        }
        $shop = [];
        foreach ($goods as $key => $value) { //按店铺区分商品
            $shop[$value['shop_id']]['shop_id'] = $value['shop_id'];
            $shop[$value['shop_id']]['shop_name'] = $Shop->where('id', $value['shop_id'])->value('name');
            $shop[$value['shop_id']]['distribution_type_text'] = "快递";
            $shop[$value['shop_id']]['distribution_type'] = OrderEnum::DISTRIBUTION_TYPE_COURIER;
            //是否为秒杀
            $seckill_goods_price = GoodsItem::isSeckill($value['item_id']);
            if ($seckill_goods_price != 0) {
                $value['price'] = $seckill_goods_price;
                self::$order_type = OrderEnum::SECKILL_ORDER;  //秒杀订单
            }

            // 如果是砍价的商品，则替换信息
            if (isset($post['bargain_launch_id']) and $post['bargain_launch_id'] > 0) {
                $bargainLaunchModel = new BargainLaunch();
                $launch = $bargainLaunchModel->field(true)
                    ->where(['id' => (int)$post['bargain_launch_id']])
                    ->find();

                $bargainImage = $launch['goods_snap']['image'] == '' ? $launch['goods_snap']['goods_iamge'] : $launch['goods_snap']['image'];
                $value['goods_name'] = $launch['goods_snap']['name'];
                $value['image_str'] = UrlServer::getFileUrl($bargainImage);
                $value['price'] = $launch['current_price'];
                $value['spec_value_str'] = $launch['goods_snap']['spec_value_str'];
                self::$order_type = OrderEnum::BARGAIN_ORDER;//砍价订单
            }

            $shop[$value['shop_id']]['goods'][] = $value;
            $shop[$value['shop_id']]['shipping_price'] = self::calculateFreight($shop[$value['shop_id']]['goods'], $address);
            if (self::$order_type == OrderEnum::BARGAIN_ORDER) { //如果是砍价
                $shop[$value['shop_id']]['total_amount'] = round(round($value['price'] * $value['num'], 2) + $shop[$value['shop_id']]['shipping_price'], 2);
            } else {
                $shop[$value['shop_id']]['total_amount'] = round(self::calculateGoodsPrice($shop[$value['shop_id']]['goods']) + $shop[$value['shop_id']]['shipping_price'], 2);
            }
            //优惠券
            $discount_amount = 0;
            if (isset($post['coupon_id']) && !empty($post['coupon_id'])) {
                $result = self::checkCoupon($post['coupon_id'], $value['shop_id']);
                if ($result) {
                    $discount_amount = self::getDiscountAmount($post['coupon_id']);
                }
            }
            $shop[$value['shop_id']]['discount_amount'] = $discount_amount;
            if ($shop[$value['shop_id']]['total_amount'] > $discount_amount) {
                $shop[$value['shop_id']]['total_amount'] = round($shop[$value['shop_id']]['total_amount'] - $discount_amount, 2);
            } else { //优惠金额大于当前商品总价，总价为0
                $shop[$value['shop_id']]['total_amount'] = 0;
            }

            //用户等级折扣
            $user = User::where('id', $post['user_id'])->find();
            $discount = UserLevel::where('id', $user['level'])->value('discount');
            if ($discount && $discount != 0) {
                $shop[$value['shop_id']]['total_amount'] = round($shop[$value['shop_id']]['total_amount'] * $discount / 10, 2);
            }

            $num = 0;
            foreach ($shop[$value['shop_id']]['goods'] as $item) {
                $num += $item['num'];
            }
            $shop[$value['shop_id']]['total_num'] = $num;
          
         	/*$whetherExistGiveawayGoods = Goods::where('id', $post['goods'][0]['goods_id'])->value('giveaway_goods');
            if ($whetherExistGiveawayGoods) {
                $shop[$value['shop_id']]['giveaway_goods'][] = Goods::field('id,name')
                    ->where('id', $whetherExistGiveawayGoods)
                    ->find()
                    ->toArray();
            } else {
                $shop[$value['shop_id']]['giveaway_goods'][] = '';
            }*/
          	
          	/**
             * 赠品
             * @author: Zhang
             */
            $whetherExistGiveawayGoods = Goods::where('id', $post['goods'][0]['goods_id'])->value('giveaway_goods');
            $giveawayGoodsArray = explode(',', $whetherExistGiveawayGoods);
            if ($whetherExistGiveawayGoods) {
                for ($i = 0; $i < count($giveawayGoodsArray); $i++) {
                    $shop[$value['shop_id']]['giveaway_goods'][] = Goods::field('id,name,image')
                        ->where('id', $giveawayGoodsArray[$i])
                        ->find()
                        ->toArray();
                }
            } else {
                $shop[$value['shop_id']]['giveaway_goods'][] = '';
            }
      

        }
      
        $shop = array_values($shop);
        $total_amount = array_sum(array_column($shop, 'total_amount'));
        $orders['address'] = $address;
        $orders['shop'] = $shop;
        $orders['order_type'] = self::$order_type;
        $orders['total_amount'] = $total_amount;
        $orders['pay_way_text'] = "微信支付";
        $orders['pay_way'] = OrderEnum::PAY_WAY_WECHAT;
        return $orders;
    }

    /**
     * @notes 获取优惠金额
     * @param $coupon_id
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:19 下午
     */
    public static function getDiscountAmount($coupon_id)
    {

        if (!isset($coupon_id) || empty($coupon_id)) {
            return 0;
        }
        //优惠金额
//        $coupon_id = json_decode($coupon_id, true);
        foreach ($coupon_id as $item) {
            $Coupon_list = CouponList::where('id', $item)->find();
            $coupon = Coupon::where(['id' => $Coupon_list['coupon_id'], 'del' => 0])
                ->find();
            if (!empty($coupon)) {
                return $coupon['money'];
            } else {
                return 0;
            }
        }
    }

    /**
     * @notes shop优惠券
     * @param $coupon_ids
     * @param $shop_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:20 下午
     */
    public static function checkCoupon($coupon_ids, $shop_id)
    {

        $coupons = CouponList::where([['id', 'in', $coupon_ids]])->select()->toArray();

        if ($coupons) {
            foreach ($coupons as $item) {
                $coupon_id = $item['coupon_id'];
                $where = [
                    'id' => $coupon_id
                ];
                $result = Coupon::where($where)->value('shop_id');
                if ($shop_id == $result) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * @notes 检查商品库存
     * @param $goods
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:20 下午
     */
    public static function checkGoods($goods)
    {

        if (!is_array($goods)) {
            self::$error = '商品数据格式不正确';
            return false;
        }
        $goods_item = new GoodsItem();
        $Good = new Goods();
        foreach ($goods as $key => $value) {
            $check_static = $Good->checkStatusById($value['goods_id']);
            $goods_name = $Good->where('id', $value['goods_id'])->value('name');
            if (false === $check_static) {
                self::$error = $goods_name . '商品不存在/未上架';
                return false;
            }
            $check_stock = $goods_item
                ->where([
                    ['goods_id', '=', $value['goods_id']],
                    ['id', '=', $value['item_id']],
                    ['stock', '<', $value['num']],
                ])
                ->find();
            if ($check_stock) {
                self::$error = $goods_name . '商品库存不足';
                return false;
            }
        }
        return true;
    }


    /**
     * @notes 计算商品总价格
     * @param $goods
     * @return false|float|int
     * @author suny
     * @date 2021/7/13 6:20 下午
     */
    public static function calculateGoodsPrice($goods)
    {

        if (!is_array($goods)) {
            return false;
        }
        $GoodsItem = new GoodsItem();
        $all_goods_price = 0;
        foreach ($goods as $key => $value) {
            $goods_price = $GoodsItem->sumGoodsPrice($value['goods_id'], $value['item_id'], $value['num']);
            $all_goods_price = round($goods_price + $all_goods_price, 2);
        }
        return $all_goods_price;
    }

    /**
     * @notes 根据goods计算商品总运费
     * @param $goods
     * @param $address
     * @return false|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:20 下午
     */
    public static function calculateFreight($goods, $address)
    {

        if (!is_array($goods)) {
            return false;
        }
        $Goods = new Goods();
        $GoodsItem = new GoodsItem();
        $Freight = new Freight();
        $freight = 0;
        if (empty($address)) {
            return $freight;
        }
        foreach ($goods as $key => $value) {
            $express = $Goods->getExpressType($value['goods_id']);
            if ($express['express_type'] == 2) { //统一运费
                $price = $express['express_money'] * $value['num'];
                $freight = bcadd($freight, $price, 2);
            }
            if ($express['express_type'] == 3) { //运费模板
                $goods_item = $GoodsItem->where([
                    ['goods_id', '=', $value['goods_id']],
                    ['id', '=', $value['item_id']]
                ])->field('stock,volume,weight')
                    ->find()
                    ->toArray();
                $price = $Freight->sumFreight($address, $goods_item, $express['express_template_id'], $value['num']);
                $freight = bcadd($freight, $price, 2);
            }
        }
        return $freight;
    }

    /**
     * @notes 添加父订单
     * @param $post
     * @param $address
     * @return false|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author suny
     * @date 2021/7/13 6:20 下午
     */
    public static function addOrderTrade($post, $address)
    {

        $OrderTrade = new OrderTrade();
        $order_amount = 0;
        $total_amount = 0;
        //计算商品总价格
        $all_goods_price = self::calculateGoodsPrice($post['goods']);
        //计算商品运费
        $all_freight = self::calculateFreight($post['goods'], $address);
        $total_amount = $all_freight + $all_goods_price;
        //计算优惠券优惠的金额
        if (isset($post['coupon_id'])) {
            $discount_amount = self::getDiscountAmount($post['coupon_id']);
        } else {
            $discount_amount = 0;
        }

        $total_amount = $total_amount - $discount_amount;
        if ($total_amount > $discount_amount) {
            $total_amount = round($total_amount - $discount_amount, 2);
        } else { //优惠金额大于当前商品总价，总价为0
            $total_amount = 0;
        }

        //用户等级
        $user = User::where('id', $post['user_id'])->find();
        $discount = UserLevel::where('id', $user['level'])->value('discount');
        if ($discount && $discount != 0) {
            $total_amount = round($total_amount * $discount / 10, 2);
        }

        // 砍价订单
        if (isset($post['bargain_launch_id']) and $post['bargain_launch_id'] > 0) {
            foreach ($post['goods'] as $goods) {
                $bargainLaunchModel = new BargainLaunch();
                $launch = $bargainLaunchModel->field(true)
                    ->where(['id' => (int)$post['bargain_launch_id']])
                    ->find();
                $total_amount = round($launch['current_price'] * $goods['num'], 2);
            }
        }

        // 记录访问足迹
        event('Footprint', [
            'type' => FootprintEnum::PLACE_ORDER,
            'user_id' => $post['user_id'],
            'total_money' => $total_amount
        ]);
        $trade_order_data = [];
        $trade_order_data['t_sn'] = createSn('order_trade', 't_sn');

        // 拿shop_id,连接成字符串存入order_trade表shop_id中
        $shop_id = '';
        foreach ($post['goods'] as $key => $value) {
            $shop_id .= ',' . $value['shop_id'];
        }
        $shop_id = substr($shop_id, 1);
        $trade_order_data['shop_id'] = $shop_id;
        $trade_order_data['user_id'] = $post['user_id'];
        $trade_order_data['goods_price'] = $all_goods_price;
        $trade_order_data['order_amount'] = $total_amount;
        $trade_order_data['total_amount'] = $total_amount;
        $trade_order_data['discount_amount'] = $discount_amount;
        $trade_order_data['create_time'] = time();
        $order_trade_create = $OrderTrade->create($trade_order_data);
        if (false === $order_trade_create) {
            return false;
        }
        return $order_trade_create->id;
    }

    /**
     * @notes 添加子订单
     * @param $order_id
     * @param $goods
     * @param $post
     * @param $shop_id
     * @param $address
     * @return false|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author suny
     * @date 2021/7/13 6:21 下午
     */
    public static function addOrder($order_id, $goods, $post, $shop_id, $address)
    {

        $Order = new Order();

        $remarks = isset($post['remark']) ? json_decode($post['remark'], true) : '';
        if ($remarks != '') {
            foreach ($remarks as $key => $value) {
                $user_remark[$value['shop_id']] = $value['remark'];
            }
            if (array_key_exists($shop_id, $user_remark)) {
                $remark = $user_remark[$shop_id];
            } else $remark = '';
        } else {
            $remark = $remarks;
        }
        $goods_price = self::calculateGoodsPrice($goods);
        $shipping_price = self::calculateFreight($goods, $address);
        //计算优惠券优惠的金额
        if (isset($post['coupon_id'])) {
            $discount_amount = self::getDiscountAmount($post['coupon_id']);
        } else {
            $discount_amount = 0;
        }
        if($goods_price + $shipping_price <= $discount_amount){
            $total_amount = $order_amount = 0;
        }else{
            $total_amount = $order_amount = $goods_price + $shipping_price - $discount_amount;
        }

        // 砍价订单
        if (isset($post['bargain_launch_id']) and $post['bargain_launch_id'] > 0) {
            foreach ($post['goods'] as $goods) {
                $bargainLaunchModel = new BargainLaunch();
                $launch = $bargainLaunchModel->field(true)
                    ->where(['id' => (int)$post['bargain_launch_id']])
                    ->find();
                $order_amount = $total_amount = round($launch['current_price'] * $goods['num'], 2);
            }
        }

        //用户等级
        $user = User::where('id', $post['user_id'])->find();
        $discount = UserLevel::where('id', $user['level'])->value('discount');
        if ($discount && $discount != 0) {
            $order_amount = $total_amount = round($total_amount * $discount / 10, 2);
        }

        $order_data = [];
        $order_data['trade_id'] = $order_id;
        $order_data['shop_id'] = $shop_id;
        $order_data['user_id'] = $post['user_id'];
        $order_data['order_sn'] = createSn('order_trade', 't_sn');
        $order_data['order_type'] = self::$order_type;
        $order_data['order_source'] = $post['client'];
        $order_data['order_status'] = OrderEnum::ORDER_STATUS_NO_PAID;
        $order_data['pay_status'] = OrderEnum::PAY_STATUS_NO_PAID;
        $order_data['pay_way'] = $post['pay_way'];
        $order_data['distribution_type'] = $post['distribution_type'];
        $order_data['aftersale_status'] = OrderEnum::AFTERSALE_STATUS_NO_SALE;
        $order_data['consignee'] = $address['contact'];
        $order_data['province'] = $address['province_id'];
        $order_data['city'] = $address['city_id'];
        $order_data['district'] = $address['district_id'];
        $order_data['address'] = $address['address'];
        $order_data['mobile'] = $address['telephone'];
        $order_data['goods_price'] = $goods_price;
        $order_data['shipping_price'] = $shipping_price;
        $order_data['order_amount'] = $order_amount;
        $order_data['discount_amount'] = $discount_amount;
        $order_data['total_amount'] = $total_amount;
        $order_data['total_num'] = array_sum(array_column($goods, 'num'));
        $order_data['user_remark'] = $remark;
        $order_data['create_time'] = time();
        $order_create = $Order->create($order_data);
        if (false === $order_create) {
            return false;
        }
        return $order_create->id;
    }


    /**
     * @notes 添加订单商品
     * @param $order_id
     * @param $goods
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:21 下午
     */
    public static function getOrderGoodsData($order_id, $goods)
    {

        $goods_data = [];
        foreach ($goods as $key => $value) {
            $Goods = Goods::where('id', $value['goods_id'])->field('name,image,shop_id,integral_ratio')->find();
            $GoodsItem = GoodsItem::where([
                ['id', '=', $value['item_id']],
                ['goods_id', '=', $value['goods_id']],
            ])->field('price,image,spec_value_ids,spec_value_str')
                ->find();
            $goods_data[$key]['order_id'] = $order_id;
            $goods_data[$key]['shop_id'] = $Goods['shop_id'];
            $goods_data[$key]['goods_id'] = $value['goods_id'];
            $goods_data[$key]['item_id'] = $value['item_id'];
            $goods_data[$key]['goods_num'] = $value['num'];
            $goods_data[$key]['goods_name'] = $Goods['name'];
            $goods_data[$key]['goods_price'] = $GoodsItem['price'];
            $goods_data[$key]['total_price'] = self::calculateGoodsPrice([$value]);
            $goods_data[$key]['total_pay_price'] = self::calculateGoodsPrice([$value]);
            $goods_data[$key]['spec_value'] = $GoodsItem['spec_value_str'];
            $goods_data[$key]['spec_value_ids'] = $GoodsItem['spec_value_ids'];
          	$goods_data[$key]['integral_ratio'] = $Goods['integral_ratio']; // 积分比例 author: Zhang
            $goods_data[$key]['image'] = $Goods['image'];
            $goods_data[$key]['create_time'] = time();
        }
        return $goods_data;
    }

    /**
     * @notes 扣除商品库存
     * @param $goods
     * @return bool
     * @author suny
     * @date 2021/7/13 6:21 下午
     */
    public static function subGoodsStock($goods)
    {

        foreach ($goods as $key => $value) {
            $goods_item_stock_dec = GoodsItem::where([
                ['id', '=', $value['item_id']],
                ['goods_id', '=', $value['goods_id']],
            ])->dec('stock', $value['num'])
                ->update();
            $goods_stock_dec = Goods::where('id', $value['goods_id'])
                ->dec('stock', $value['num'])
                ->update();
            if (false === $goods_item_stock_dec) {
                return false;
            }
            if (false === $goods_stock_dec) {
                return false;
            }
        }
        return true;

    }

    /**
     * @notes 添加订单日志表
     * @param $order_id
     * @param $user_id
     * @param $shop_id
     * @return array
     * @author suny
     * @date 2021/7/13 6:21 下午
     */
    public static function getOrderLogData($order_id, $user_id, $shop_id)
    {

        $order_log_data = [];
        $order_log_data['type'] = OrderLogEnum::TYPE_USER;
        $order_log_data['channel'] = OrderLogEnum::USER_ADD_ORDER;
        $order_log_data['order_id'] = $order_id;
        $order_log_data['handle_id'] = $user_id;
        $order_log_data['shop_id'] = $shop_id;
        $order_log_data['content'] = OrderLogEnum::USER_ADD_ORDER;
        $order_log_data['create_time'] = time();

        return $order_log_data;

    }

    /**
     * @notes 删除购物车
     * @param $cart_id
     * @return bool
     * @author suny
     * @date 2021/7/13 6:21 下午
     */
    public static function delCart($cart_id)
    {

        $delCart = Cart::where([
            ['id', 'in', $cart_id],
            ['selected', '=', 1]
        ])
            ->delete();
        if (false === $delCart) {
            return false;
        }
        return true;

    }

    /**
     * @notes 订单列表
     * @param $user_id
     * @param $type
     * @param $page
     * @param $size
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:21 下午
     */
    public static function getOrderList($user_id, $type, $page, $size)
    {

        $order = new Order();
        $where[] = ['del', '=', 0];
        $where[] = ['user_id', '=', $user_id];

        switch ($type) {
            case 'pay':
                $where[] = ['order_status', '=', OrderEnum::ORDER_STATUS_NO_PAID];
                break;
            case 'delivery':
                $where[] = ['order_status', 'in', [OrderEnum::ORDER_STATUS_DELIVERY, OrderEnum::ORDER_STATUS_GOODS]];
                break;
            case 'finish':
                $where[] = ['order_status', '=', OrderEnum::ORDER_STATUS_COMPLETE];
                break;
            case 'close':
                $where[] = ['order_status', '=', OrderEnum::ORDER_STATUS_DOWN];
                break;
        }

        $count = $order->where(['del' => 0, 'user_id' => $user_id])
            ->where($where)
            ->count();

        $lists = $order->where(['del' => 0, 'user_id' => $user_id])
            ->where($where)
            ->with(['order_goods', 'shop'])
            ->field('id,order_sn,order_status,pay_status,order_amount,order_status,order_type,shipping_status,create_time,shop_id')
            ->page($page, $size)
            ->order('id desc')
            ->select();

        $lists->append(['goods_count', 'pay_btn', 'cancel_btn', 'delivery_btn', 'take_btn', 'del_btn', 'comment_btn', 'order_cancel_time']);

        foreach ($lists as $list) {
            if ($list['order_type'] == OrderEnum::SECKILL_ORDER) {//如果是秒杀
                foreach ($list['order_goods'] as $item) {
                    $seckill_price = GoodsItem::isSeckill($item['item_id']);
                    if ($seckill_price != 0) {
                        $item['goods_price'] = $seckill_price;
                    }
                }
            }
        }
        $data = [
            'list' => $lists,
            'page' => $page,
            'size' => $size,
            'count' => $count,
            'more' => is_more($count, $page, $size)
        ];
        return $data;
    }

    /**
     * @notes 通过规格id查询秒杀价格
     * @param $item_id
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:21 下午
     */
    public static function getSekillPriceByItemId($item_id)
    {

        $where = [
            'item_id' => $item_id,
            'del' => 0,
            'review_status' => 1
        ];
        $seckill = SeckillGoods::where($where)->find();
        return isset($seckill['price']) ? $seckill['price'] : 0;
    }

    /**
     * @notes 订单详情
     * @param $order_id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:22 下午
     */
    public static function getOrderDetail($order_id)
    {

        $order = Order::with(['order_goods', 'shop'])->where(['del' => 0, 'id' => $order_id])->find();
      
      	/**
         * 赠品信息
         */
        for ($i = 0; $i < count($order['order_goods']); $i++) {
            $giveawayGoods[$i] = Goods::where('id', $order['order_goods'][$i]['goods_id'])->value('giveaway_goods');
            if (empty($giveawayGoods[$i])) {
                $order['order_goods'][$i]['giveaway_goods'] = '';
                continue;
            }

            $giveawayGoodsArray = [];
            $explodeGiveawayGoods = explode(',', $giveawayGoods[$i]);
            for ($j = 0; $j < count($explodeGiveawayGoods); $j++) {
                $giveawayGoodsArray[$j] = Goods::where('id', $explodeGiveawayGoods[$j])->field('id,name,image')->find();
                $order['order_goods'][$i]['giveaway_goods'] = $giveawayGoodsArray;
            }
        }
      
        if ($order) {
            $order->append(['delivery_address', 'pay_btn', 'cancel_btn', 'delivery_btn', 'take_btn', 'del_btn', 'order_cancel_time'])
                ->hidden(['user_id', 'order_source',
                    'city', 'district', 'address', 'shipping_status', 'shipping_code',
                    'pay_status', 'transaction_id', 'del', 'province']);

            $refund_days = ConfigServer::get('after_sale', 'refund_days', 7 * 86400, 0) * 86400;
            $now = time();
            foreach ($order->order_goods as $order_good) {
                if ($order['order_type'] == OrderEnum::SECKILL_ORDER) { // 是秒杀商品
                    $seckill_price = GoodsItem::isSeckill($order_good['item_id']);
                    if ($seckill_price != 0) {
                        $order_good['goods_price'] = $seckill_price;
                    }
                }
                $order_good['comment_btn'] = 0;
                if ($order['pay_status'] == PayEnum::ISPAID && $order['order_status'] == OrderEnum::ORDER_STATUS_COMPLETE && $order_good['is_comment'] == 0) {
                    $order_good['comment_btn'] = 1;
                }
                $order_good['refund_btn'] = 0;

                $confirm_take_time = strtotime($order['confirm_take_time']) ?: 0;
                $refund_time = $confirm_take_time + $refund_days;
                if ($order['order_status'] == OrderEnum::ORDER_STATUS_COMPLETE && $order_good['refund_status'] == OrderGoodsEnum::REFUND_STATUS_NO) {
                    $order_good['refund_btn'] = 1;
                }
            }
        }

        // 如果是拼团的订单
        if ($order['order_type'] == OrderEnum::TEAM_ORDER) {
            $teamJoin = (new TeamJoin())->where(['order_id' => $order['id']])->findOrEmpty()->toArray();
            $teamJoin['team_snap'] = json_decode($teamJoin['team_snap'], true);
            $order['team'] = [
                'team_activity_id' => $teamJoin['team_activity_id'],
                'team_id' => $teamJoin['team_id'],
                'identity' => $teamJoin['identity'] == 1 ? '团长' : '团员',
                'people_num' => $teamJoin['team_snap']['people_num'],
                'status' => $teamJoin['status'],
                'status_text' => TeamEnum::getStatusDesc($teamJoin['status'])
            ];
        }

        $order['order_type'] = Order::getOrderType($order['order_type']);
        $order['pay_way'] = PayEnum::getPayWay($order['pay_way']);
        $order['create_time'] = $order['create_time'] == 0 ? '' : $order['create_time'];
        $order['update_time'] = $order['update_time'] == 0 ? '' : $order['update_time'];
        $order['confirm_take_time'] = $order['confirm_take_time'] == 0 ? '' : date('Y-m-d H:i:s', $order['confirm_take_time']);;
        $order['shipping_time'] = $order['shipping_time'] == 0 ? '' : date('Y-m-d H:i:s', $order['shipping_time']);
        $order['pay_time'] = $order['pay_time'] == 0 ? '' : date('Y-m-d H:i:s', $order['pay_time']);

        return $order;
    }

    /**
     * @notes 取消订单
     * @param $order_id
     * @param $user_id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\PDOException
     * @author suny
     * @date 2021/7/13 6:22 下午
     */
    public static function cancel($order_id, $user_id)
    {

        $time = time();
        $order = Order::with(['orderGoods'])->where(['del' => 0, 'user_id' => $user_id, 'id' => $order_id])->find();

        if (!$order || (int)$order['order_status'] > OrderEnum::ORDER_STATUS_DELIVERY) {
            return JsonServer::error('很抱歉!订单无法取消');
        }
        Db::startTrans();
        try {
            // 如果是拼团的订单
            if ($order['order_type'] == OrderEnum::TEAM_ORDER) {

                $team_id = (new TeamJoin())->where(['order_id' => $order['id']])->value('team_id');
                $teamJoin = (new TeamJoin())->alias('TJ')
                    ->field(['TJ.*,O.order_sn,O.order_status,O.pay_status,O.refund_status,O.order_amount'])
                    ->where(['team_id' => $team_id])
                    ->join('order O', 'O.id=TJ.order_id')
                    ->select()->toArray();

                TeamFound::update(['status' => TeamEnum::TEAM_STATUS_FAIL, 'team_end_time' => $time], ['id' => $team_id]);
                foreach ($teamJoin as $item) {
                    TeamJoin::update(['status' => TeamEnum::TEAM_STATUS_FAIL, 'update_time' => $time], ['id' => $item['id']]);
                    OrderRefundLogic::cancelOrder($item['order_id'], OrderLogEnum::TYPE_USER);  //取消订单

                    if ($item['pay_status'] == PayEnum::ISPAID) {
                        $order = (new Order())->findOrEmpty($item['order_id'])->toArray();
                        OrderRefundLogic::cancelOrderRefundUpdate($order); //更新订单状态
                        OrderRefundLogic::refund($order, $order['order_amount'], $order['order_amount']); //订单退款
                    }
                }


            } else {
                //取消订单
                OrderRefundLogic::cancelOrder($order_id, OrderLogEnum::TYPE_USER);
                self::backStock($order['orderGoods']);
                //已支付的订单,取消,退款
                if ($order['pay_status'] == PayEnum::ISPAID) {
                    //更新订单状态
                    OrderRefundLogic::cancelOrderRefundUpdate($order);
                    //订单退款
                    OrderRefundLogic::refund($order, $order['order_amount'], $order['order_amount']);
                }

            }

            Db::commit();
            return JsonServer::success('取消成功');
        } catch (Exception $e) {
            Db::rollback();
            self::addErrorRefund($order, $e->getMessage());
            return JsonServer::error($e->getMessage());
        }
    }

    /**
     * @notes 回退商品库存
     * @param $goods
     * @author suny
     * @date 2021/7/13 6:22 下午
     */
    public static function backStock($goods)
    {

        foreach ($goods as $good) {
            //回退库存,回退规格库存,减少商品销量
            Goods::where('id', $good['goods_id'])
                ->update([
                    'stock' => Db::raw('stock+' . $good['goods_num'])
                ]);

            //补充规格表库存
            GoodsItem::where('id', $good['item_id'])
                ->inc('stock', $good['goods_num'])
                ->update();
        }
    }

    /**
     * @notes 增加退款失败记录
     * @param $order
     * @param $err_msg
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author suny
     * @date 2021/7/13 6:22 下午
     */
    public static function addErrorRefund($order, $err_msg)
    {

        $orderRefund = new OrderRefund();
        $refund_data = [
            'order_id' => $order['id'],
            'user_id' => $order['user_id'],
            'refund_sn' => createSn('order_refund', 'refund_sn'),
            'order_amount' => $order['order_amount'],//订单应付金额
            'refund_amount' => $order['order_amount'],//订单退款金额
            'transaction_id' => $order['transaction_id'],
            'create_time' => time(),
            'refund_status' => 2,
            'refund_msg' => json_encode($err_msg, JSON_UNESCAPED_UNICODE),
        ];
        return $orderRefund->insertGetId($refund_data);
    }

    /**
     * @notes 获取退款订单的应付金额
     * @param $order
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:22 下午
     */
    public static function getOrderTotalFee($order)
    {

        $OrderTrade = new OrderTrade();
        $trade = $OrderTrade
            ->where('transaction_id', $order['transaction_id'])
            ->find();

        $total_fee = $order['order_amount'];
        if ($trade) {
            $total_fee = $trade['order_amount'];
        }
        return $total_fee;
    }

    /**
     * @notes 退款
     * @param $order
     * @return bool
     * @author suny
     * @date 2021/7/13 6:22 下午
     */
    public static function refund($order)
    {

        switch ($order['pay_way']) {
            case OrderEnum::PAY_WAY_BALANCE:
                RefundLogic::balanceRefund($order);
                break;
            case OrderEnum::PAY_WAY_ALIPAY:
                break;
            case OrderEnum::PAY_WAY_OFFLINE:
                break;
            case OrderEnum::PAY_WAY_WECHAT:
                break;

        }

        return true;

    }

    /**
     * @notes 确认订单
     * @param $order_id
     * @param $user_id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:22 下午
     */
    public static function confirm($order_id, $user_id)
    {
        Db::startTrans();
        try {
            $order = Order::where(['del' => 0, 'id' => $order_id])->find();
            if ($order['order_status'] == OrderEnum::ORDER_STATUS_COMPLETE) {
                return JsonServer::error('订单已完成');
            }
            if ($order['shipping_status'] == 0) {
                return JsonServer::error('订单未发货');
            }
            $order->order_status = OrderEnum::ORDER_STATUS_COMPLETE;
            $order->update_time = time();
            $order->confirm_take_time = time();
            $order->save();

            //订单日志
            OrderLogLogic::record(
                OrderLogEnum::TYPE_USER,
                OrderLogEnum::USER_CONFIRM_ORDER,
                $order_id,
                $user_id,
                OrderLogEnum::USER_CONFIRM_ORDER
            );

			/**
             * 积分 - 余额支付没积分
             * @author Zhang
             */
            if ($order['pay_way'] !== 3) {
                $orderGoods = OrderGoods::where('order_id', $order_id)->field('goods_id,total_pay_price,integral_ratio')->select()->toArray();
                $totalIntegral = 0;
                for ($i = 0; $i < count($orderGoods); $i++) {
                    $totalIntegral += $orderGoods[$i]['total_pay_price'] * ($orderGoods[$i]['integral_ratio'] / 100);
                }
                $oldUserMoney = User::where('id', $user_id)->value('user_money');
                $newUserMoney = $oldUserMoney + $totalIntegral;
                User::where('id', $user_id)->update(['user_money' => $newUserMoney]);
            }
    
            Db::commit();
            return JsonServer::success('确认成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

    /**
     * @notes 删除订单
     * @param $order_id
     * @param $user_id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:23 下午
     */
    public static function del($order_id, $user_id)
    {

        $where = [
            'order_status' => OrderEnum::ORDER_STATUS_DOWN,
            'user_id' => $user_id,
            'id' => $order_id,
            'del' => 0,
        ];
        $order = Order::where($where)->find();

        if (!$order) {
            return JsonServer::error('订单无法删除');
        }

//        $res = $order->save(['del' => 1, 'update_time' => time()]);
        $data = ['del' => 1, 'update_time' => time(), 'pat_status' => OrderEnum::ORDER_STATUS_DOWN];
        $res = Order::update($data, ['id' => $order['id']]);
        OrderLogLogic::record(
            OrderLogEnum::TYPE_USER,
            OrderLogEnum::USER_DEL_ORDER,
            $order_id,
            $user_id,
            OrderLogEnum::USER_DEL_ORDER
        );
        return JsonServer::success('删除成功', ['res' => $res]);
    }

    /**
     * @notes 获取订单支付结果
     * @param $trade_id
     * @return array|false
     * @author suny
     * @date 2021/7/13 6:23 下午
     */
    public static function pay_result($trade_id)
    {

        $result = Order::where(['trade_id' => $trade_id, 'pay_status' => 1])
            ->field(['id', 'order_sn', 'pay_time', 'pay_way', 'total_amount'])
            ->findOrEmpty()->toArray();
        $result['pay_time'] = date('Y-m-d H:i:s', $result['pay_time']);
        if ($result) {
            $pay_way_text = PayEnum::getPayWay($result['pay_way']);
            $result['pay_way'] = $pay_way_text;
            return $result;
        } else {
            return false;
        }

    }

    /**
     * @notes 获取支付方式
     * @param $user_id
     * @return array|array[]|\array[][]|\array[][][]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:23 下午
     */
    public static function getPayWay($user_id, $client)
    {

        $payModel = new Pay();
        $payway = $payModel->where(['status' => 1])->order('sort')->hidden(['config'])->select()->toArray();
        foreach ($payway as $k => &$item) {
            if ($item['code'] == 'wechat') {
                $item['extra'] = '微信快捷支付';
                $item['pay_way'] = PayEnum::WECHAT_PAY;
            }

            if ($item['code'] == 'balance') {
                $user_money = Db::name('user')->where(['id' => $user_id])->value('user_money');
                $item['extra'] = '可用余额:' . $user_money;
                $item['pay_way'] = PayEnum::BALANCE_PAY;
            }

            if ($item['code'] == 'alipay') {
                $item['extra'] = '';
                $item['pay_way'] = PayEnum::ALI_PAY;
                if (in_array($client, [ClientEnum::mnp, ClientEnum::oa])) {
                    unset($payway[$k]);
                }
            }
        }
        return $payway;
    }

    /**
     * @notes 查询物流
     * @param $id
     * @param $user_id
     * @return array|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author suny
     * @date 2021/7/13 6:23 下午
     */
    public static function orderTraces($id, $user_id)
    {

        $order = new Order();
        $order = $order->alias('o')
            ->join('order_goods og', 'o.id = og.order_id')
            ->join('goods g', 'g.id = og.goods_id')
            ->where(['o.id' => $id, 'o.user_id' => $user_id, 'pay_status' => OrderEnum::ORDER_STATUS_DELIVERY, 'o.del' => 0])
            ->field('o.id,order_status,total_num,og.image,o.consignee,o.mobile,o.province,o.city,o.district,o.address,pay_time,confirm_take_time,o.shipping_status,shipping_time')
            ->append(['delivery_address'])
            ->find();
        if (!self::checkDelivery($order['delivery_id'])) {
            return false;
        }

        //初始化数据
        $order_tips = '已下单';
        $order_traces = [];
        $traces = [];//物流轨迹
        $shipment = [//发货
            'title' => '已发货',
            'tips' => '',
            'time' => '',
        ];
        $finish = [//交易完成
            'title' => '交易完成',
            'tips' => '',
            'time' => '',
        ];

        if ($order) {
            $order_delivery = Delivery::where(['order_id' => $id])->field('invoice_no,shipping_name,shipping_id')->find();
            $express = ConfigServer::get('express', 'way', '', '');
            //已发货
            if ($express && $order['shipping_status']) {
                $app = ConfigServer::get($express, 'appkey', '', '');
                $key = ConfigServer::get($express, 'appsecret', '', '');
                //获取物流配置
                if ($app && $key) {
                    //快递配置设置为快递鸟时
                    if ($express === 'kdniao') {
                        $expressage = (new Kdniao($app, $key, Env::get('app.app_debug', 'true')));
                        $shipping_field = 'codebird';
                    } else {
                        $expressage = (new Kd100($key, $app, Env::get('app.app_debug', 'true')));
                        $shipping_field = 'code100';
                    }
                    //快递编码
                    $shipping_code = Db::name('express')->where(['id' => $order_delivery['shipping_id']])->value($shipping_field);
                    //获取物流轨迹
                    $expressage->logistics($shipping_code, $order_delivery['invoice_no']);
                    $traces = $expressage->logisticsFormat();
                    //获取不到物流轨迹时
                    if ($traces == false) {
                        $traces[] = ['暂无物流信息'];

                    } else {
                        foreach ($traces as &$item) {
                            $item = array_values(array_unique($item));
                        }
                    }

                }
            }
            //待收货
            if ($order['order_status'] == 2) {
                $shipment['tips'] = '商品已出库';
                $shipment['time'] = date('Y-m-d H:i:s', $order['shipping_time']);
            }
            //确认收货
            if ($order['order_status'] == 3) {
                $order_tips = '交易完成';
                $finish['tips'] = '订单交易完成';
                $finish['time'] = $order['confirm_take_time'] ? date('Y-m-d H:i:s', $order['confirm_take_time']) : $order['confirm_take_time'];
            }
            //数据合并
            $order_traces = [
                'order' => [
                    'tips' => $order_tips,
                    'image' => UrlServer::getFileUrl($order['image']),
                    'count' => $order['total_num'],
                    'invoice_no' => $order_delivery['invoice_no'],
                    'shipping_name' => $order_delivery['shipping_name'],
                ],
                'take' => [
                    'contacts' => $order['consignee'],
                    'mobile' => $order['mobile'],
                    'address' => $order['delivery_address'],
                ],
                'finish' => $finish,
                'delivery' => [
                    'title' => '运输中',
                    'traces' => $traces
                ],
                'shipment' => $shipment,
                'buy' => [
                    'title' => '已下单',
                    'tips' => '订单提交成功',
                    'time' => date('Y-m-d H:i:s', $order['pay_time'])
                ],
            ];
            return $order_traces;
        }

        return $order_traces;

    }

    /**
     * @notes 配送方式无需快递的
     * @param $delivery_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:23 下午
     */
    public static function checkDelivery($delivery_id)
    {

        $delivery = Delivery::where(['id' => $delivery_id])
            ->find();
        if ($delivery['send_type'] == 2) {
            return false;
        }
        return true;
    }

    /**
     * @notes 判断商家营业状态
     * @param $value
     * @return bool|string
     * @author suny
     * @date 2021/7/13 6:23 下午
     */
    public static function checkShop($value)
    {

        $shop_id = $value['shop_id'];
        $is_run = Shop::where('id', $shop_id)->value('is_run');
        if ($is_run == 0) {
            return '该商家已暂停营业';
        } else {
            return true;
        }
    }

    /**
     * @notes 修改优惠券状态
     * @param $coupon_id
     * @param $order_id
     * @return CouponList|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:23 下午
     */
    public static function editCoupon($coupon_id, $order_id)
    {

        $status = CouponList::where(['id' => $coupon_id, 'status' => 0])->find();
        if (!$status) {
            return false;
        }
        $time = time();
        $data = [
            'status' => 1,
            'use_time' => $time,
            'update_time' => $time,
            'order_id' => $order_id
        ];
        $res = CouponList::where('id', $status->id)
            ->update($data);
        return $res;
    }

    /**
     * @notes 设置秒杀商品销量
     * @param $item_id
     * @param $num
     * @return bool
     * @author suny
     * @date 2021/7/13 6:23 下午
     */
    public static function setSeckillSaleSum($item_id, $num)
    {

        $result = SeckillGoods::where('item_id', $item_id)
            ->inc('sales_sum', $num)
            ->save();
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
