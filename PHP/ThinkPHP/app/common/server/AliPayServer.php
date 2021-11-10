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

// | Author: LikeShopTeam-段誉
// +----------------------------------------------------------------------


namespace app\common\server;


use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;
use app\common\logic\PayNotifyLogic;
use app\common\model\Client_;
use app\common\enum\PayEnum;
use app\common\model\Pay;
use app\common\model\Test;
use think\facade\Db;
use think\facade\Log;

class AliPayServer
{


    protected $error = '未知错误';

    public function getError()
    {
        return $this->error;
    }


    public function __construct()
    {
        Factory::setOptions($this->getOptions());
    }


    /**
     * Notes: 支付设置
     * @author 段誉(2021/3/23 10:33)
     * @return Config
     * @throws \Exception
     */
    public function getOptions()
    {
        $result = (new Pay())->where(['code' => 'alipay'])->find();
        if (empty($result)) {
            throw new \Exception('请配置好支付设置');
        }

        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
//        $options->gatewayHost = 'openapi.alipaydev.com'; //测试沙箱地址
        $options->signType = 'RSA2';
        $options->appId = $result['config']['app_id'] ?? '';
        // 应用私钥
        $options->merchantPrivateKey = $result['config']['private_key'] ?? '';
        //支付宝公钥
        $options->alipayPublicKey = $result['config']['ali_public_key'] ?? '';
        //回调地址
        $options->notifyUrl = (string)url('pay/aliNotify', [], false, true);
        return $options;
    }


    /**
     * Notes: pc支付
     * @param $attach
     * @param $order
     * @author 段誉(2021/3/22 18:38)
     * @return string
     */
    public function pagePay($attach, $order_id)
    {
        $domain = request()->domain();
        if($attach == 'trade'){
            $trade = Db::name('order_trade')->where(['id' => $order_id])->find();
            $sn = $trade['t_sn'];
            $order_amount = $trade['order_amount'];
        }else{
            $order = Db::name('order')->where(['id' => $order_id])->find();
            $sn = $order['order_sn'];
            $order_amount = $order['order_amount'];
        }
        $result = Factory::payment()->page()->optional('passback_params', $attach)->pay(
            '订单:'.$sn,
            $sn,
            $order_amount,
            $domain.'/pc/user/order'
        );
        return $result->body;
    }


    /**
     * Notes: app支付
     * @param $attach
     * @param $order
     * @author 段誉(2021/3/22 18:38)
     * @return string
     */
    public function appPay($attach, $order_id)
    {
        if($attach == 'trade'){
            $trade = Db::name('order_trade')->where(['id' => $order_id])->find();
            $sn = $trade['t_sn'];
            $order_amount = $trade['order_amount'];
        }else{
            $order = Db::name('order')->where(['id' => $order_id])->find();
            $sn = $order['order_sn'];
            $order_amount = $order['order_amount'];
        }
        $result = Factory::payment()->app()->optional('passback_params', $attach)->pay(
            $sn,
            $sn,
            $order_amount
        );
        return $result->body;
    }


    /**
     * Notes: 手机网页支付
     * @param $attach
     * @param $order
     * @author 段誉(2021/3/22 18:38)
     * @return string
     */
    public function wapPay($attach, $order_id)
    {
        if($attach == 'trade'){
            $trade = Db::name('order_trade')->where(['id' => $order_id])->find();
            $sn = $trade['t_sn'];
            $order_amount = $trade['order_amount'];
        }else{
            $order = Db::name('order')->where(['id' => $order_id])->find();
            $sn = $order['order_sn'];
            $order_amount = $order['order_amount'];
        }
        $domain = request()->domain();
        $result = Factory::payment()->wap()->optional('passback_params', $attach)->pay(
            '订单:'.$sn,
            $sn,
            $order_amount,
            $domain.'/mobile/bundle/pages/user_order/user_order',
            $domain.'/mobile/bundle/pages/user_order/user_order'
        );
        return $result->body;
    }


    /**
     * Notes: 支付
     * @param $from
     * @param $order
     * @param $order_source
     * @author 段誉(2021/3/22 18:33)
     * @return bool|string
     */
    public function pay($from, $order_id, $order_source)
    {
//        $order_source = Client_::ios;
        try{
            switch ($order_source){
                case Client_::pc:
                    $result = $this->pagePay($from, $order_id);
                    break;
                case Client_::ios:
                case Client_::android:
                    $result = $this->appPay($from, $order_id);
                    break;
                case Client_::h5:
                    $result = $this->wapPay($from, $order_id);
                    break;
                default:
                    throw new \Exception('支付方式错误');
            }
            return $result;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * Notes: 支付回调验证
     * @param $data
     * @author 段誉(2021/3/22 17:22)
     * @return bool
     */
    public function verifyNotify($data)
    {
        try {
            $verify = Factory::payment()->common()->verifyNotify($data);
            if (false === $verify) {
                throw new \Exception('异步通知验签失败');
            }
            $extra['transaction_id'] = $data['trade_no'];
            //验证订单是否已支付
            switch ($data['passback_params']) {
                case 'order':
                    $order = Db::name('order')->where(['order_sn' => $data['out_trade_no']])->find();
                    if (!$order || $order['pay_status'] >= PayEnum::ISPAID) {
                        return true;
                    }
                    PayNotifyLogic::handle('order', $data['out_trade_no'], $extra);
                    break;

                case 'trade':
                    $order_trade = Db::name('order_trade')->where(['t_sn' => $data['out_trade_no']])->find();
                    $trade_id = $order_trade['id'];
                    $orders = Db::name('order')->where(['trade_id' => $trade_id])->select();
                    foreach ($orders as $order) {
                        if (!$order || $order['pay_status'] >= PayEnum::ISPAID) {
                            return true;
                        }
                    }
                    PayNotifyLogic::handle('trade', $data['out_trade_no'], $extra);
                    break;

                case 'recharge':
                    $order = Db::name('recharge_order')->where(['order_sn' => $data['out_trade_no']])->find();
                    if (!$order || $order['pay_status'] >= PayEnum::ISPAID) {
                        return true;
                    }
                    PayNotifyLogic::handle('recharge', $data['out_trade_no'], $extra);
                    break;
            }

            return true;
        } catch (\Exception $e) {
            $record = [
                __CLASS__, __FUNCTION__, $e->getFile(), $e->getLine(), $e->getMessage()
            ];
            Log::record(implode('-', $record));
            return false;
        }
    }


    /**
     * Notes: 查询订单
     * @param $order_sn
     * @author 段誉(2021/3/23 15:21)
     * @return \Alipay\EasySDK\Payment\Common\Models\AlipayTradeQueryResponse
     * @throws \Exception
     */
    public function checkPay($order_sn)
    {
        return Factory::payment()->common()->query($order_sn);
    }


    /**
     * Notes: 退款
     * @param $order_sn 订单号
     * @param $order_amount 金额
     * @author 段誉(2021/3/25 10:24)
     * @return \Alipay\EasySDK\Payment\Common\Models\AlipayTradeRefundResponse
     * @throws \Exception
     */
    public function refund($order_sn, $order_amount)
    {
        return Factory::payment()->common()->refund($order_sn, $order_amount);
    }


}

