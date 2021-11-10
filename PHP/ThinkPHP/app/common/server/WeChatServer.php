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

use app\common\model\Client_;
use app\common\model\Pay;
use app\common\server\ConfigServer;
use think\Exception;

/**
 * 微信服务 服务类
 * Class WeChatServer
 * @package app\common\server
 */
class WeChatServer
{
    /**
     * @notes 获取小程序配置
     * @return array
     * @author suny
     * @date 2021/7/13 6:35 下午
     */
    public static function getMnpConfig()
    {

        $config = [
            'app_id' => ConfigServer::get('mnp', 'app_id'),
            'secret' => ConfigServer::get('mnp', 'secret'),
            'mch_id' => ConfigServer::get('mnp', 'mch_id'),
            'key' => ConfigServer::get('mnp', 'key'),
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => '../runtime/log/wechat.log'
            ],
        ];
        return $config;
    }

    /**
     * @notes 获取微信公众号配置
     * @return array
     * @author suny
     * @date 2021/7/13 6:35 下午
     */
    public static function getOaConfig()
    {

        $config = [
            'app_id' => ConfigServer::get('oa', 'app_id'),
            'secret' => ConfigServer::get('oa', 'secret'),
            'mch_id' => ConfigServer::get('oa', 'mch_id'),
            'key' => ConfigServer::get('oa', 'key'),
            'token' => ConfigServer::get('oa', 'token', ''),
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => '../runtime/log/wechat.log'
            ],
        ];
        return $config;
    }

    /**
     * @notes 获取url
     * @param $str
     * @return string
     * @author suny
     * @date 2021/7/13 6:35 下午
     */
    public static function getUrl($str)
    {

        return (string)url($str, [], false, true);
    }

    /**
     * @notes 根据不同来源获取支付配置
     * @param $order_source
     * @return array
     * @throws Exception
     * @author suny
     * @date 2021/7/13 6:36 下午
     */
    public static function getPayConfigBySource($order_source)
    {

        $notify_url = '';
        switch ($order_source) {
            case Client_::mnp:
                $notify_url = self::getUrl('pay/notifyMnp');
                break;
            case Client_::oa:
            case Client_::pc:
            case Client_::h5:
                $notify_url = self::getUrl('pay/notifyOa');
                break;
            case Client_::android:
            case Client_::ios:
                $notify_url = self::getUrl('pay/notifyApp');
                break;
        }
        $config = self::getPayConfig($order_source);
        if (empty($config) ||
            empty($config['key']) ||
            empty($config['mch_id']) ||
            empty($config['app_id']) ||
            empty($config['secret'])
        ) {
            throw new Exception('请在后台配置好微信支付！');
        }

        return [
            'config' => $config,
            'notify_url' => $notify_url,
        ];
    }

    //===================================支付配置=======================================================

    /**
     * @notes 微信支付设置 H5支付 appid 可以是公众号appid
     * @param $client
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author suny
     * @date 2021/7/13 6:36 下午
     */
    public static function getPayConfig($client)
    {

        switch ($client) {
            case Client_::mnp:
                $appid = ConfigServer::get('mnp', 'app_id');
                $secret = ConfigServer::get('mnp', 'secret');
                break;
            case Client_::oa:
            case Client_::pc:
            case Client_::h5:
                $appid = ConfigServer::get('oa', 'app_id');
                $secret = ConfigServer::get('oa', 'secret');
                break;
            case Client_::android:
            case Client_::ios:
                $appid = ConfigServer::get('op', 'app_id');
                $secret = ConfigServer::get('op', 'secret');
                break;
            default:
                $appid = '';
                $secret = '';
        }

        $pay = Pay::where(['code' => 'wechat'])->find()->toArray();

        $config = [
            'app_id' => $appid,
            'secret' => $secret,
            'mch_id' => $pay['config']['mch_id'] ?? '',
            'key' => $pay['config']['pay_sign_key'] ?? '',
            'cert_path' => $pay['config']['apiclient_cert'] ?? '',
            'key_path' => $pay['config']['apiclient_key'] ?? '',
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => '../runtime/log/wechat.log'
            ],
        ];

        if (is_cli()) {
            if (!defined('ROOT_PATH')) {
                define('ROOT_PATH', __DIR__);
            }
            $config['cert_path'] = ROOT_PATH . '/public/' . $pay['config']['apiclient_cert'];
            $config['key_path'] = ROOT_PATH . '/public/' . $pay['config']['apiclient_key'];
        }

        return $config;
    }
}