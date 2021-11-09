<?php


namespace app\common\listener;


use app\common\enum\FootprintEnum;
use app\common\model\coupon\Coupon;
use app\common\model\Footprint as FootprintModel;
use app\common\model\FootprintRecord as FootprintRecordModel;
use app\common\model\goods\Goods;
use app\common\server\ConfigServer;
use Exception;
use think\facade\Log;

/**
 * 足迹气泡事件
 * Class Footprint
 * @package app\common\listener
 */
class Footprint
{
    public function handle($params)
    {
        try {
            $footprint_status = ConfigServer::get('footprint', 'footprint_status', 1);
            if (!$footprint_status) return;
            if (empty($params['type']) || !$params['type']) return;
            if (empty($params['user_id']) || !$params['user_id']) return;

            if ($this->getFootPrintIsAllowByType($params['type'])) {
                $this->record($params);
            }
        } catch (Exception $e) {
            Log::write('足迹气泡异常:'.$e->getMessage());
        }
    }

    /**
     * @Notes: 判断足迹功能是否开启
     * @Author: 张无忌
     * @param $type (场景)
     * @return bool
     */
    private function getFootPrintIsAllowByType($type)
    {
        $model = (new FootprintModel)->where(['type'=>(int)$type])->findOrEmpty()->toArray();
        if ($model and $model['status']) {
            return true;
        }
        return false;
    }

    /**
     * @Notes: 记录足迹气泡
     * @Author: 张无忌
     * @param $params
     * @throws \think\Exception
     */
    private function record($params)
    {
        switch ($params['type']) {
            case FootprintEnum::ENTER_MALL:
                if(!FootprintRecordModel::getFootPrintOneHourInner($params)) {
                    $tpl = '访问了商城';
                    FootprintRecordModel::add($params, $tpl);
                }
                break;
            case FootprintEnum::BROWSE_GOODS:
                if(!FootprintRecordModel::getFootPrintOneHourInner($params)) {
                    $tpl = '正在浏览'.$this->getName('goods', $params['foreign_id']);
                    FootprintRecordModel::add($params, $tpl);
                }
                break;
            case FootprintEnum::ADD_CART:
                $tpl = '正在购买'.$this->getName('goods', $params['foreign_id']);
                FootprintRecordModel::add($params, $tpl);
                break;
            case FootprintEnum::RECEIVE_COUPON:
                $tpl = '正在领取'.$this->getName('coupon', $params['foreign_id']).'优惠券';
                FootprintRecordModel::add($params, $tpl);
                break;
            case FootprintEnum::PLACE_ORDER:
                $tpl = '成功下单'.$params['total_money'];
                FootprintRecordModel::add($params, $tpl);
                break;
        }
    }

    /**
     * @Notes: 获取名称
     * @Author: 张无忌
     * @param $scene
     * @param $id
     * @return mixed|string
     */
    private function getName($scene, $id)
    {
        $name = '';
        switch ($scene) {
            case 'goods':
                $name = (new Goods())->findOrEmpty($id)->value('name');

                if (mb_strlen($name) > 6) {
                    $name = mb_substr($name, 0, 6). '**';
                }
                break;
            case 'coupon':
                $name = (new Coupon())->findOrEmpty($id)->value('name');
                break;
        }

        return $name;
    }
}