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
// | Author: LikeShopTeam
// +----------------------------------------------------------------------


namespace app\common\server;


use app\common\model\Config as configModel;
use think\facade\Config;

/**
 * 配置 服务类
 * Class ConfigServer
 * @Author FZR
 * @package app\common\server
 */
class ConfigServer
{

    /**
     * Notes: 设置配置
     * @param $type
     * @param $name
     * @param $value
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉(2021/4/14 16:01)
     */
    public static function set($type, $name, $value, $shop_id = 0)
    {
        $original = $value;
        $update_time = time();
        if (is_array($value)) {
            $value = json_encode($value, true);
        }

        $data = configModel::where(['type' => $type, 'name' => $name, 'shop_id' => $shop_id])->find();

        if (empty($data)) {
            configModel::create([
                'type'      => $type,
                'name'      => $name,
                'value'     => $value,
                'shop_id'   => $shop_id
            ]);
        } else {
            configModel::update([
                'value'         => $value,
                'update_time'   => $update_time
            ], ['type' => $type, 'name' => $name, 'shop_id' => $shop_id]);
        }
        return $original;
    }


    /**
     * Notes: 获取配置
     * @param $type
     * @param string $name
     * @param null $default_value
     * @author 段誉(2021/4/14 16:07)
     * @return array|mixed|null
     */
    public static function get($type, $name = '', $default_value = null, $shop_id = 0)
    {
        if ($name) {
            $result = configModel::where([
                'type'      => $type,
                'name'      => $name,
                'shop_id'   => $shop_id
            ])->value('value');

            $json = json_decode($result, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result = $json;
            }
        } else {
            $result = configModel::where(['type' => $type, 'shop_id' => $shop_id])
                ->column('value', 'name');

            foreach ($result as $k => $v) {
                $json = json_decode($v, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result[$k] = $json;
                }
            }
        }

        if ($result) {
            return $result;
        }
        if ($result === 0 || $result === '0') {
            return $result;
        }
        if ($default_value !== null) {
            return $default_value;
        }
        return Config::get('default.' . $type . '.' . $name);
    }

}