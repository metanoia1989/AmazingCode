<?php
namespace  app\api\logic;

use app\common\basics\Logic;
use app\common\enum\FootprintEnum;
use app\common\model\coupon\Coupon;
use app\common\model\coupon\CouponGoods;
use app\common\model\coupon\CouponList;
use app\common\model\goods\Goods;
use app\common\model\Cart;
use app\common\model\goods\GoodsItem;

class CouponLogic extends Logic
{
    public static function getCouponList($get)
    {
        // 未删除 & 上架中 & 发放时间内
        $where = [
            ['del', '=', 0],
            ['status', '=', 1],
            ['send_time_start', '<=', time()],
            ['send_time_end', '>=', time()],
            ['get_type', '=', 1], // 直接领取
        ];

        if(isset($get['goods_id'])) {
            $shop_id = Goods::where('id', $get['goods_id'])->value('shop_id');
            $where[] = ['shop_id', '=', $shop_id];
        }

        if(isset($get['shop_id'])) {
         $where[] = ['shop_id', '=', $get['shop_id']];
        }

        // 券类型 type all-全部(默认) shop-店铺券 platform-平台通用券
        if(isset($get['type']) && !empty($get['type'])) {
            switch ($get['type']) {
                case 'shop':
                    $where[] = ['shop_id', '<>', 0];
                    break;
                case 'platform':
                    $where[] = ['shop_id', '=', 0]; // 0 代表平台券
                    break;
            }
        }

        $lists = Coupon::field(true)
            ->with('shop')
            ->where($where)
            ->select()
            ->toArray();

        // 过滤没有库存的优惠券
        foreach($lists as $key => &$item) {
            if($item['send_total_type'] == 2) { // 限制张数
                // 已发放数量
                $already_issue = CouponList::where(['del'=>0, 'coupon_id'=>$item['id']])->count();
                if($already_issue >= $item['send_total']) {
                    // 已发完，前端不展示
                    unset($lists[$key]);
                    // 下一轮循环
                    continue;
                }
            }
            // === 拼装信息
            // 小数点去0
            $item['money'] = clearZero($item['money']);
            $item['condition_money'] = clearZero($item['condition_money']);
            // 优惠券名称
            $item['coupon_name'] = $item['shop']['name'].$item['name'];
            // 使用门槛
            switch($item['condition_type']) {
                case 1:
                    $item['condition_type_desc'] = '无门槛';
                    break;
                case 2:
                    $item['condition_type_desc'] = "满{$item['condition_money']}减{$item['money']}";
                    break;
            }
            // 用券时间
            switch($item['use_time_type']) {
                case 1:
                    $item['user_time_desc'] = date('Y-m-d H:i:s',$item['use_time_start']). ' 至 ' . date('Y-m-d H:i:s', $item['use_time_end']);
                    break;
                case 2:
                    $item['user_time_desc'] = "领取当天{$item['use_time']}天内可用";
                    break;
                case 3:
                    $item['user_time_desc'] = "领取次日{$item['use_time']}天内可用";
                    break;
            }
            // 使用场景
            switch($item['use_goods_type']) {
                case 1:
                    $item['use_scene_desc'] = $item['shop']['name'].'全店通用';
                    break;
                case 2:
                    $item['use_scene_desc'] = $item['shop']['name'].'部分商品可用';
                    if(isset($get['goods_id'])) {
                        $goodsArr = CouponGoods::where(['coupon_id'=>$item['id'], 'goods_id'=>$get['goods_id']])->select()->toArray();
                        if(!$goodsArr) {
                            // 该券不适用此商品
                            unset($lists[$key]);
                        }
                    }
                    break;
                case 3:
                    $item['use_scene_desc'] = $item['shop']['name'].'部分商品不可用';
                    if(isset($get['goods_id'])) {
                        $goodsArr = CouponGoods::where(['coupon_id'=>$item['id'], 'goods_id'=>$get['goods_id']])->select()->toArray();
                        if($goodsArr) {
                            // 该券不适用此商品
                            unset($lists[$key]);
                        }
                    }
                    break;
            }
            // 判断该券用户是否已领取过
            $count = CouponList::where([
                'del' => 0,
                'user_id' => $get['user_id'],
                'coupon_id' => $item['id']
            ])->count();
            $item['is_get'] = $count ? 1: 0;
        }

        // 分页处理
        $count = count($lists);
        $index = ($get['page_no'] -1) * $get['page_size'];
        $lists = array_slice($lists, $index, $get['page_size']);

        return [
            'count' => $count,
            'lists' => $lists,
            'page_no' => $get['page_no'],
            'page_size' => $get['page_size'],
            'more' => is_more($count, $get['page_no'], $get['page_size']),
        ];
    }

    public static function getCoupon($post)
    {
        try{
            // 未删除 & 上架中 & 发放时间内
            $where = [
                ['del', '=', 0],
                ['status', '=', 1],
                ['send_time_start', '<=', time()],
                ['send_time_end', '>=', time()],
                ['id', '=', $post['coupon_id']],
            ];
            // 校验优惠券信息
            $coupon = Coupon::where($where)->findOrEmpty();
            if($coupon->isEmpty()) {
                throw new \think\Exception('优惠券已失效');
            }
            // 校验限制数量
            if($coupon['send_total_type'] == 2) {
                // 已发放数量
                $already_issue = CouponList::where(['del'=>0, 'coupon_id'=>$coupon['id']])->count();
                if($already_issue >= $coupon['send_total']) {
                    throw new \think\Exception('优惠券已领取完了');
                }
            }
            // 校验领取次数限制
            switch($coupon['get_num_type']) {
                case 2: // 限制总领取次数
                    // 当前用户已领取次数
                    $already_get = CouponList::where(['del'=>0, 'coupon_id'=>$coupon['id'], 'user_id'=>$post['user_id']])->count();
                    if($already_get >= $coupon['get_num']) {
                        throw new \think\Exception('您已超过领取次数');
                    }
                    break;
                case 3: // 限制每天领取次数
                    $today_date = date('Y-m-d', time());
                    $today_time = strtotime($today_date.' 00:00:00');
                    // 当前用户今天已领取次数
                    $already_today_get = CouponList::where([
                        ['del', '=', 0],
                        ['coupon_id', '=', $coupon['id']],
                        ['user_id', '=', $post['user_id']],
                        ['create_time', '>=', $today_time],
                    ])->count();
                    if($already_today_get >= $coupon['get_num']) {
                        throw new \think\Exception('今天您已超过领取次数');
                    }
                    break;
            }
            // 开始领券
            $time = time();
            $addData = [
                'user_id' => $post['user_id'],
                'coupon_id' => $post['coupon_id'],
                'coupon_code' => create_coupon_code(),
                'status' => 0,
                'create_time' => $time,
                'update_time' => $time,
                'del' => 0
            ];
            CouponList::create($addData);

            // 记录访问足迹
            event('Footprint', [
                'type'    => FootprintEnum::RECEIVE_COUPON,
                'user_id' => $post['user_id'],
                'foreign_id' => $post['coupon_id']
            ]);

            return true;
        }catch(\Exception $e){
            self::$error = $e->getMessage();
            return false;
        }
    }

    public static function myCouponList($get)
    {
        // 提取用户未删除的优惠券
        $whereOne = [
            ['cl.del', '=', 0],
            ['cl.user_id', '=', $get['user_id']]
        ];
        $fieldOne = 'cl.*, c.name,c.use_time_type,c.use_time_start,c.use_time_end,c.use_time as coupon_use_time,s.name as shop_name,c.condition_type,c.condition_money,c.money,c.use_goods_type';
        $count_list = CouponList::alias('cl')
            ->leftJoin('coupon c', 'c.id=cl.coupon_id')
            ->leftJoin('shop s', 's.id=c.shop_id')
            ->field($fieldOne)
            ->where($whereOne)
            ->order('id', 'desc')
            ->select()
            ->toArray();

        // 循环标识每条记录的券是否过期
        foreach($count_list as &$item) {
            $item['is_expired'] = 0; // 默认先标识为未过期
            switch($item['use_time_type']) {
                case 1: // 固定时间
                    if($item['use_time_end'] <= time()) {
                        $item['is_expired'] = 1;
                    }
                    break;
                case 2:  // 领券当天起
                    $days = '+'.$item['coupon_use_time'].' day';
                    $expired_time = strtotime($days, $item['create_time']);
                    if($expired_time <= time()) {
                        $item['is_expired'] = 1;
                    }
                    break;
                case 3: // 领券次日起
                    $days = '+'.($item['coupon_use_time']+1).' day';
                    $expired_time = strtotime($days, $item['create_time']);
                    if($expired_time <= time()) {
                        $item['is_expired'] = 1;
                    }
                    break;
            }
        }

        // 根据类型过滤记录提取 type: used-已使用 valid-可使用 expired-已过期
        if(isset($get['type'])) {
            switch($get['type']) {
                case 'used':
                    $count_list = array_filter($count_list, function($item) {
                        return $item['status'] == 1; // 已使用
                    });
                    break;
                case 'valid':
                    $count_list = array_filter($count_list, function($item) {
                        return $item['status'] == 0 && $item['is_expired'] == 0; // 未使用，未过期
                    });
                    break;
                case 'expired':
                    $count_list = array_filter($count_list, function($item) {
                        return $item['status'] == 0 && $item['is_expired'] == 1; // 未使用，已过期
                    });
                    break;
            }
        }else{ // 默认 valid 可使用的
            $count_list = array_filter($count_list, function($item) {
                return $item['status'] == 0 && $item['is_expired'] == 0; // 未使用，未过期
            });
        }
        // 拼装信息
        $count_list = self::formatInfo($count_list);

        // 分页处理
        $count = count($count_list);
        $index = ($get['page_no'] -1) * $get['page_size'];
        $lists = array_slice($count_list, $index, $get['page_size']);

        // 返回数据
        return [
            'count' => $count,
            'lists' => $lists,
            'page_no' => $get['page_no'],
            'page_size' => $get['page_size'],
            'more' => is_more($count, $get['page_no'], $get['page_size']),
        ];
    }

    /**
     * 拼装信息
     */
    public static function formatInfo($coupon_list)
    {
        foreach($coupon_list as  &$item) {
            // 去除无效的0
            $item['money'] = clearZero($item['money']);
            $item['condition_money'] = clearZero($item['condition_money']);
            // 优惠券名称
            $item['coupon_name'] = $item['shop_name'].$item['name'];
            // 使用门槛
            switch($item['condition_type']) {
                case 1:
                    $item['condition_type_desc'] = '无门槛';
                    break;
                case 2:
                    $item['condition_type_desc'] = "满{$item['condition_money']}减{$item['money']}";
                    break;
            }
            // 用券时间
            switch($item['use_time_type']) {
                case 1:
                    $item['user_time_desc'] = date('Y-m-d H:i:s',$item['use_time_start']). ' 至 ' . date('Y-m-d H:i:s', $item['use_time_end']);
                    break;
                case 2:
                    $item['user_time_desc'] = "领取当天{$item['coupon_use_time']}天内可用";
                    break;
                case 3:
                    $item['user_time_desc'] = "领取次日{$item['coupon_use_time']}天内可用";
                    break;
            }
            // 使用场景
            switch($item['use_goods_type']) {
                case 1:
                    $item['use_scene_desc'] = $item['shop_name'].'全店通用';
                    $item['use_goods_desc'] = '所有商品可用';
                    break;
                case 2:
                    $item['use_scene_desc'] = $item['shop_name'].'部分商品可用';
                    $goods = CouponGoods::alias('cg')
                        ->field('g.name as goods_name')
                        ->leftJoin('goods g', 'g.id=cg.goods_id')
                        ->where(['coupon_id'=>$item['coupon_id']])
                        ->select()
                        ->toArray();
                    $item['use_goods_desc'] = count($goods) ? '仅限 ' : '';
                    foreach($goods as $goods) {
                        $item['use_goods_desc'] .= $goods['goods_name'] . '、';
                    }
                    $item['use_goods_desc'] = trim( $item['use_goods_desc'], '、');
                    $item['use_goods_desc'] .= ' 商品可用';
                    break;
                case 3:
                    $item['use_scene_desc'] = $item['shop_name'].'部分商品不可用';
                    $goods = CouponGoods::alias('cg')
                        ->field('g.name as goods_name')
                        ->leftJoin('goods g', 'g.id=cg.goods_id')
                        ->where(['coupon_id'=>$item['coupon_id']])
                        ->select()
                        ->toArray();
                    $item['use_goods_desc'] = count($goods) ? '仅限 ' : '';
                    foreach($goods as $goods) {
                        $item['use_goods_desc'] .= $goods['goods_name'] . '、';
                    }
                    $item['use_goods_desc'] = trim( $item['use_goods_desc'], '、');
                    $item['use_goods_desc'] .= ' 之外的商品可用';
                    break;
            }
        }
        return $coupon_list;
    }

    /**
     * 统计订单价格
     */
    public static function calcMoney($buy_type, $get)
    {
       if($buy_type == 'buy') { // 立即购买
          $price =  GoodsItem::where('id', $get['item_id'])->value('price');
          return $price * $get['num'];
       }
       if($buy_type == 'cart') { // 购买车
           $total = 0;
           $buyGoodsArr = Cart::alias('c')
               ->leftJoin('goods_item gi', 'gi.id=c.item_id')
               ->field('c.item_id,c.goods_num,gi.price')
               ->where([
               ['id', 'in', $get['param']],
               ['selected', '=', '1']
           ])->select()->toArray();
           foreach($buyGoodsArr as $item) {
                $total += $item['price'] * $item['goods_num'];
           }
           return $total;
       }
    }

    /**
     * 统计订单商品及金额
     */
    public static  function getGoodsInfo($goods)
    {
        $buyGoodsArr = [];
        $buyMoney = 0;
        foreach($goods as $item) {
            $buyGoodsArr[] = $item['goods_id'];
            $price = GoodsItem::where('id', $item['item_id'])->value('price');
            $buyMoney += $item['num'] * $price;
        }
        return [
            'buyGoodsArr' => $buyGoodsArr,
            'buyMoney' => $buyMoney,
        ];
    }



    public static function getBuyCouponList($post)
    {
//        $post['goods'] = json_decode($post['goods'], true);

        // 提取用户未删除的优惠券
        $whereOne = [
            ['cl.del', '=', 0],
            ['cl.user_id', '=', $post['user_id']],
            ['s.id', '=', $post['shop_id']],
        ];
        $fieldOne = 'cl.*, c.name,c.use_time_type,c.use_time_start,c.use_time_end,c.use_time as coupon_use_time,s.name as shop_name,c.condition_type,c.condition_money,c.money,c.use_goods_type';
        $count_list = CouponList::alias('cl')
            ->leftJoin('coupon c', 'c.id=cl.coupon_id')
            ->leftJoin('shop s', 's.id=c.shop_id')
            ->field($fieldOne)
            ->where($whereOne)
            ->order('id', 'desc')
            ->select()
            ->toArray();

        // 循环标识每条记录的券是否过期
        foreach($count_list as &$item) {
            $item['is_expired'] = 0; // 默认先标识为未过期
            switch($item['use_time_type']) {
                case 1: // 固定时间
                    if($item['use_time_end'] <= time()) {
                        $item['is_expired'] = 1;
                    }
                    break;
                case 2:  // 领券当天起
                    $days = '+'.$item['coupon_use_time'].' day';
                    $expired_time = strtotime($days, $item['create_time']);
                    if($expired_time <= time()) {
                        $item['is_expired'] = 1;
                    }
                    break;
                case 3: // 领券次日起
                    $days = '+'.($item['coupon_use_time']+1).' day';
                    $expired_time = strtotime($days, $item['create_time']);
                    if($expired_time <= time()) {
                        $item['is_expired'] = 1;
                    }
                    break;
            }
        }

        // 根据类型过滤记录提取
        $count_list = array_filter($count_list, function($item) {
            return $item['status'] == 0 && $item['is_expired'] == 0; // 未使用，未过期
        });

        // 结算页优惠券列表
        $goodsInfo = self::getGoodsInfo($post['goods']);
        $buyGoodsArr = $goodsInfo['buyGoodsArr'];
        $buyMoney = $goodsInfo['buyMoney'];

        $suit = []; // 适用券
        $unSuit = []; // 不适用券
        foreach($count_list as $key => $item) {
            $flag = true; // 默认先标识为适用
            // 使用场景校验
            switch($item['use_goods_type']) {
                case 2: // 部分商品可用
                    $goods = CouponGoods::alias('cg')
                        ->field('g.id')
                        ->leftJoin('goods g', 'g.id=cg.goods_id')
                        ->where(['coupon_id'=>$item['coupon_id']])
                        ->select()
                        ->toArray();
                    foreach($buyGoodsArr as $subItem) {
                        if(!in_array($subItem, $goods)) { // 是否在可用商品列表
                            $flag = false;
                            break;
                        }
                    }
                    break;
                case 3: // 部分商品不可用
                    $goods = CouponGoods::alias('cg')
                        ->field('g.id')
                        ->leftJoin('goods g', 'g.id=cg.goods_id')
                        ->where(['coupon_id'=>$item['coupon_id']])
                        ->select()
                        ->toArray();
                    foreach($buyGoodsArr as $subItem) {
                        if(in_array($subItem, $goods)) { // 是否在不可用商品列表
                            $flag = false;
                            break;
                        }
                    }
                    break;
            }

            // 使用门槛校验
            switch($item['condition_type']) {
                case 2:
                    if($buyMoney < $item['condition_money']) {
                        // 不满足门槛
                        $flag = false;
                    }
                    break;
            }

            if($flag) {
                // 符合场景、符合门槛
                $suit[] = $item;
            }else{
                // 不符合场景或门槛
                $unSuit[] = $item;
            }
        }

        $suit = self::formatInfo($suit);
        $unSuit = self::formatInfo($unSuit);
        return [
            'suit' => $suit,
            'un_suit' => $unSuit,
        ];
    }
}
