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


use app\common\enum\PayEnum;
use app\common\logic\PaymentLogic;
use app\common\logic\PayNotifyLogic;
use app\common\model\Client_;
use app\common\model\Pay;
use EasyWeChat\Factory;
use EasyWeChat\Payment\Application;
use app\common\model\order\OrderLog;
use app\common\model\order\Order;
use app\common\model\order\OrderTrade;
use Endroid\QrCode\QrCode;
use http\Client;
use think\facade\Db;
use think\Exception;

/**
 * Class WeChatPayServer
 * @package app\common\server
 */
class WeChatPayServer
{

    protected static $error = '未知错误';
    protected static $return_code = 0;

    /**
     * @notes 错误信息
     * @return string
     * @author suny
     * @date 2021/7/13 6:34 下午
     */
    public static function getError()
    {

        return self::$error;
    }

    /**
     * @notes 返回状态码
     * @return int
     * @author suny
     * @date 2021/7/13 6:34 下午
     */
    public static function getReturnCode()
    {

        return self::$return_code;
    }


    /**
     * @notes 微信统一下单
     * @param $from
     * @param $order
     * @param $order_source
     * @return array|false|string
     * @author suny
     * @date 2021/7/13 6:34 下午
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \Endroid\QrCode\Exception\InvalidWriterException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public static function unifiedOrder($from, $order, $order_source)
    {

        try {
            $wechat_config = self::getWeChatConfig($order, $order_source);
            $auth = $wechat_config['auth'];
            $config = $wechat_config['config'];
            $notify_url = $wechat_config['notify_url'];

            //jsapi需要验证openID
            $check_source = [Client_::mnp, Client_::oa];
            if (!$auth && in_array($order_source, $check_source)) {
                throw new Exception('授权信息失效');
            }

            $app = Factory::payment($config);
            $attributes = self::getAttributes($from, $order, $order_source, $auth, $notify_url);
            $result = $app->order->unify($attributes);

            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {

                //小程序,公众号
                if (in_array($order_source, [Client_::mnp, Client_::oa])) {
                    $data = $app->jssdk->bridgeConfig($result['prepay_id'], false);
                }

                //app客户端
                if (in_array($order_source, [Client_::ios, Client_::android])) {
                    $data = $app->jssdk->appConfig($result['prepay_id'], false);
                }

                //pc端
                if ($order_source == Client_::pc) {
                    $data = self::getNativeCode($result, $order);
                }

                //h5(非微信环境)
                if ($order_source == Client_::h5) {
                    $redirect_url = request()->domain() . '/mobile/pages/user_order/user_order';
                    $redirect_url = urlencode($redirect_url);
                    $data = $result['mweb_url'] . '&redirect_url=' . $redirect_url;
                }
                return $data;
            } else {
                if (isset($result['return_code']) && $result['return_code'] == 'FAIL') {
                    throw new Exception($result['return_msg']);
                }
                if (isset($result['err_code_des'])) {
                    throw new Exception($result['err_code_des']);
                }
                throw new Exception('未知原因');
            }

        } catch (Exception $e) {
            self::$error = '支付失败:' . $e->getMessage();
            return false;
        }
    }

    /**
     * @notes NATIVE 支付二维码
     * @param $result
     * @param $order
     * @return string
     * @author suny
     * @date 2021/7/13 6:34 下午
     */
    public static function getNativeCode($result, $order)
    {

        $save_dir = 'uploads/pay_code/';
        $qr_src = md5($order['order_sn'] . mt_rand(10000, 99999)) . '.png';
        $code_url = ROOT_PATH . '/' . $save_dir . $qr_src;

        $qrCode = new QrCode();
        $qrCode->setText($result['code_url']);
        $qrCode->setSize(200);
        $qrCode->setWriterByName('png');
        !file_exists($save_dir) && mkdir($save_dir, 777, true);
        $qrCode->writeFile($code_url);

        //生成base64临时图片
        if ($fp = fopen($code_url, "rb", 0)) {
            $gambar = fread($fp, filesize($code_url));
            fclose($fp);
            $base64 = chunk_split(base64_encode($gambar));
            $base64 = 'data:image/png;base64,' . $base64;
        }
        //删除文件
        if (strstr($code_url, $save_dir)) {
            unlink($code_url);
        }
        return $base64;
    }


    /**
     * @notes 支付参数
     * @param $from
     * @param $order
     * @param $order_source
     * @param $auth
     * @param $notify_url
     * @return array
     * @author suny
     * @date 2021/7/13 6:34 下午
     */
    public static function getAttributes($from, $order, $order_source, $auth, $notify_url)
    {

        switch ($from) {
            case 'trade':
                $attributes = [
                    'trade_type' => 'JSAPI',
                    'body' => '商品总订单',
                    'total_fee' => $order['order_amount'] * 100, // 单位：分
                    'notify_url' => $notify_url,
                    'openid' => $auth['openid'],
                    'attach' => 'trade'
                ];
                break;
            case 'order':
                $attributes = [
                    'trade_type' => 'JSAPI',
                    'body' => '商品子订单',
                    'total_fee' => $order['order_amount'] * 100, // 单位：分
                    'notify_url' => $notify_url,
                    'openid' => $auth['openid'],
                    'attach' => 'order'
                ];
                break;
            case 'recharge':
                $attributes = [
                    'trade_type' => 'JSAPI',
                    'body' => '充值',
                    'total_fee' => $order['order_amount'] * 100, // 单位：分
                    'notify_url' => $notify_url,
                    'openid' => $auth['openid'],
                    'attach' => 'recharge'
                ];
                break;
        }

        //app支付类型
        if ($order_source == Client_::android || $order_source == Client_::ios) {
            $attributes['trade_type'] = 'APP';
        }

        //NATIVE模式设置
        if ($order_source == Client_::pc) {
            $attributes['trade_type'] = 'NATIVE';
            $attributes['product_id'] = $order['order_sn'];
        }

        //h5支付类型
        if ($order_source == Client_::h5) {
            $attributes['trade_type'] = 'MWEB';
        }

        //在白名单内,一分钱
        if (PaymentLogic::isPayWhiteList($order['user_id'])) {
            $attributes['total_fee'] = 1;
        }

        //修改微信统一下单,订单编号 -> 支付回调时截取前面的单号 18个
        //修改原因:回调时使用了不同的回调地址,导致跨客户端支付时(例如小程序,公众号)可能出现201,商户订单号重复错误
        if ($from == 'trade') {
            $attributes['out_trade_no'] = $order['t_sn'] . $attributes['trade_type'] . $order_source;
        } else {
            $attributes['out_trade_no'] = $order['order_sn'] . $attributes['trade_type'] . $order_source;
        }
        return $attributes;
    }


    /**
     * @notes 获取微信配置
     * @param $order
     * @param $order_source
     * @return array
     * @author suny
     * @date 2021/7/13 6:35 下午
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws Exception
     */
    public static function getWeChatConfig($order, $order_source)
    {

        $pay_config = WeChatServer::getPayConfigBySource($order_source);
        $where[] = ['user_id', '=', $order['user_id']];
        $where[] = ['client', '=', $order_source];
        $auth = Db::name('user_auth')->where($where)->find();

        $data = [
            'auth' => $auth,
            'config' => $pay_config['config'],
            'notify_url' => $pay_config['notify_url'],
            'order_source' => $order_source,
        ];
        return $data;
    }

    /**
     * @notes 添加订单日志表
     * @param $order_id
     * @param $user_id
     * @param $shop_id
     * @return array
     * @author suny
     * @date 2021/7/13 6:35 下午
     */
    public static function getOrderLogData($order_id, $user_id, $shop_id)
    {

        $order_log_data = [];
        $order_log_data['type'] = 0;
        $order_log_data['channel'] = 101;
        $order_log_data['order_id'] = $order_id;
        $order_log_data['handle_id'] = $user_id;
        $order_log_data['shop_id'] = $shop_id;
        $order_log_data['content'] = 105;
        $order_log_data['create_time'] = time();

        return $order_log_data;

    }

    /**
     * @notes 支付回调
     * @param $config
     * @author suny
     * @date 2021/7/13 6:35 下午
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public static function notify($config)
    {

        $app = new Application($config);
        $response = $app->handlePaidNotify(function ($message, $fail) {

            if ($message['return_code'] !== 'SUCCESS') {
                return $fail('通信失败');
            }
//            // 用户是否支付成功
            if ($message['result_code'] === 'SUCCESS') {
                $extra['transaction_id'] = $message['transaction_id'];
                $attach = $message['attach'];
                $message['out_trade_no'] = mb_substr($message['out_trade_no'], 0, 18);
                switch ($attach) {
                    case 'trade':
                        $order_trade = OrderTrade::where('t_sn', $message['out_trade_no'])->find();
                        $order = Order::where('trade_id', $order_trade['id'])->find();
                        if (!$order || $order['pay_status'] >= PayEnum::ISPAID) {
                            return true;
                        }
                        PayNotifyLogic::handle('trade', $message['out_trade_no'], $extra);
                        break;
                    case 'order':
                        $order = Db::name('order')->where(['order_sn' => $message['out_trade_no']])->find();
                        if (!$order || $order['pay_status'] >= PayEnum::ISPAID) {
                            return true;
                        }
                        PayNotifyLogic::handle('order', $message['out_trade_no'], $extra);
                        break;
                    case 'recharge':
                        $order = Db::name('recharge_order')->where(['order_sn' => $message['out_trade_no']])->find();
                        if (!$order || $order['pay_status'] >= PayEnum::ISPAID) {
                            return true;
                        }
                        PayNotifyLogic::handle('recharge', $message['out_trade_no'], $extra);
                        break;
                }
            } elseif ($message['result_code'] === 'FAIL') {
                // 用户支付失败
            }
            return true; // 返回处理完成
        });
        $response->send();
    }


    /**
     * @notes 退款
     * @param $config
     * @param $data
     * @return array|\EasyWeChat\Kernel\Support\Collection|false|object|\Psr\Http\Message\ResponseInterface|string
     * @author suny
     * @date 2021/7/13 6:35 下午
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public static function refund($config, $data)
    {

        if (!empty($data["transaction_id"])) {
            $app = Factory::payment($config);
            $result = $app->refund->byTransactionId(
                $data['transaction_id'],
                $data['refund_sn'],
                $data['total_fee'],
                $data['refund_fee']
            );
            return $result;
        } else {
            return false;
        }
    }

}