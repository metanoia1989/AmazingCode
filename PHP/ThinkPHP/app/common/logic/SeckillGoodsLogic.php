<?php
namespace app\api\logic;

use app\common\basics\Logic;
use app\common\model\seckill\SeckillGoods;
use app\common\model\seckill\SeckillTime;
use app\common\server\UrlServer;
use app\common\model\goods\Goods;
use app\common\enum\GoodsEnum;

class SeckillGoodsLogic extends Logic
{
    public static function seckillTime(){
        $time_list = SeckillTime::where(['del'=>0])
            ->order('start_time asc')
            ->field('id,start_time,end_time')
            ->select()
            ->toArray();
        $now = time();
        $today_date = date('Y-m-d');
        foreach ($time_list as &$item){
            $item['status'] = 2;
            $item['tips'] = '';
            $start_time = strtotime($today_date.' '.$item['start_time']);
            $end_time = strtotime($today_date.' '.$item['end_time']);
            if($now >= $end_time ){
                $item['tips'] = '已结束';
            }
            if($start_time <= $now && $now < $end_time){
                $item['status'] = 1;
                $item['tips'] = '抢购中';
            }
            if($start_time >= $now){
                $item['tips'] = '未开始';
                $item['status'] = 0;
            }
            $item['end_time_int'] = strtotime($item['end_time']);
        }
        return $time_list;
    }

    public static function getSeckillGoods($get)
    {
        $where = [
            ['sg.del', '=', 0],
            ['sg.seckill_id', '=', $get['seckill_id']],
            ['sg.review_status', '=', 1],
        ];
        $field = 'sg.seckill_id,sg.start_date,sg.end_date,g.id as goods_id,g.name as goods_name,g.image as goods_image,g.min_price as goods_min_price';
        $lists = SeckillGoods::alias('sg')
            ->leftJoin('seckill_time st', 'st.id=sg.seckill_id')
            ->leftJoin('goods g', 'g.id=sg.goods_id')
            ->group('sg.seckill_id,sg.start_date,sg.end_date,g.id')
            ->field($field)
            ->where($where)
            ->select()
            ->toArray();

        // 过滤日期
        foreach($lists as $key => &$item) {
            $start_date_time = strtotime($item['start_date'].' 00:00:00');
            $end_date_time = strtotime($item['end_date'].' 23:59:59');
            if($start_date_time <= time() && $end_date_time >= time()) {
                // 在活动日期，判断商品是否在线
                $goods = Goods::where([
                    'del' => GoodsEnum::DEL_NORMAL,
                    'status' => GoodsEnum::STATUS_SHELVES,
                    'audit_status' => GoodsEnum::AUDIT_STATUS_OK,
                    'id' => $item['goods_id']
                ])->findOrEmpty();
                if($goods->isEmpty()) { // 商品不存在或未处于销售状态
                    unset($lists[$key]);
                }
            }else{
                // 非活动日期，去除记录
                unset($lists[$key]);
            }
        }

        // 分页处理
        $count = count($lists);
        $index = ($get['page_no']-1) * $get['page_size'];
        $lists = array_slice($lists, $index, $get['page_size']);

        // 格式化信息
        foreach($lists as &$item) {
            // 图片加域名
            $item['goods_image'] = UrlServer::getFileUrl($item['goods_image']);
            // 秒杀价最小值
            $seckillGoodsArr = SeckillGoods::where([
                'seckill_id'=>$item['seckill_id'],
                'start_date' => $item['start_date'],
                'end_date' => $item['end_date'],
                'goods_id' => $item['goods_id']
            ])->select()->toArray();

            $item['seckill_price'] = $item['goods_min_price'];
            $item['seckill_total'] = 0;
            foreach($seckillGoodsArr as $subItem) {
                if($item['seckill_price'] > $subItem['price']) {
                    $item['seckill_price'] = $subItem['price'];
                }
                $item['seckill_total'] += $subItem['sales_sum'];
            }
        }

        return [
            'count' => $count,
            'lists' => $lists,
            'page_no' => $get['page_no'],
            'page_size' => $get['page_size'],
            'more' => is_more($count, $get['page_no'], $get['page_size'])
        ];
    }

    public static function getSeckillGoodsTwo($seckill_id)
    {
        $where = [
            ['sg.del', '=', 0],
            ['sg.seckill_id', '=', $seckill_id],
        ];
        $field = 'sg.seckill_id,sg.start_date,sg.end_date,g.id as goods_id,g.name as goods_name,g.image as goods_image,g.min_price as goods_min_price';
        $lists = SeckillGoods::alias('sg')
            ->leftJoin('seckill_time st', 'st.id=sg.seckill_id')
            ->leftJoin('goods g', 'g.id=sg.goods_id')
            ->group('sg.seckill_id,sg.start_date,sg.end_date,g.id')
            ->field($field)
            ->where($where)
            ->select()
            ->toArray();

        // 过滤日期
        foreach($lists as $key => &$item) {
            $start_date_time = strtotime($item['start_date'].' 00:00:00');
            $end_date_time = strtotime($item['end_date'].' 23:59:59');
            if($start_date_time <= time() && $end_date_time >= time()) {
                // 在活动日期，判断商品是否在线
                $goods = Goods::where([
                    'del' => GoodsEnum::DEL_NORMAL,
                    'status' => GoodsEnum::STATUS_SHELVES,
                    'audit_status' => GoodsEnum::AUDIT_STATUS_OK,
                    'id' => $item['goods_id']
                ])->findOrEmpty();
                if($goods->isEmpty()) { // 商品不存在或未处于销售状态
                    unset($lists[$key]);
                }
            }else{
                // 非活动日期，去除记录
                unset($lists[$key]);
            }
        }


        $lists = array_slice($lists, 0, 3); // 取3条记录

        // 格式化信息
        foreach($lists as &$item) {
            // 图片加域名
            $item['goods_image'] = UrlServer::getFileUrl($item['goods_image']);
            // 秒杀价最小值
            $seckillGoodsArr = SeckillGoods::where([
                'seckill_id'=>$item['seckill_id'],
                'start_date' => $item['start_date'],
                'end_date' => $item['end_date'],
                'goods_id' => $item['goods_id']
            ])->select()->toArray();

            $item['seckill_price'] = $item['goods_min_price'];
            $item['seckill_total'] = 0;
            foreach($seckillGoodsArr as $subItem) {
                if($item['seckill_price'] > $subItem['price']) {
                    $item['seckill_price'] = $subItem['price'];
                }
                $item['seckill_total'] += $subItem['sales_sum'];
            }
        }

        return $lists;
    }

    /**
     * 获取正在秒杀时段id
     */
    public static function getSeckillTimeIng()
    {
        $time_list = SeckillTime::where(['del'=>0])
            ->order('start_time asc')
            ->field('id,start_time,end_time')
            ->select()
            ->toArray();
        $now = time();
        $today_date = date('Y-m-d');
        foreach ($time_list as &$item){
            $start_time = strtotime($today_date.' '.$item['start_time']);
            $end_time = strtotime($today_date.' '.$item['end_time']);
            if($start_time <= $now && $now < $end_time){
               return $item;
            }
        }
        return false;
    }
}
