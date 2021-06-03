<?php
namespace app\api\controller;

use app\BaseController;
use WechatService\WxPayService;
use WechatService\WechatService;

class WxPay extends BaseController{
    /**
     * 创建支付
     * @return \think\response\Json
     */
    public function Pay(){
        $order_sn = input('post.order_sn');
        $jscode = input('post.jscode');
        /**获取用户openid**/
        $wx = new WechatService();
        $result = $wx->returnOpenID($jscode);
        if(isset($result['errcode']) && $result['errcode']!=0) return json(['code'=>-1,'msg'=>$result['errmsg']]);

        $openid = $result['openid'];
        /**获取订单金额**/
        $price = $this->GetOrderPrice($order_sn);
        $wxpay = new WxPayService();
        /********创建body********/
        //判断是什么类型的订单
        $wxpay->SetBody('云相册加片');
        $wxpay->SetNotifyUrl('https://xxxx.com/mapp_order/notify');
        $randStr = mt_rand(10000,99999);
        $wxpay->SetOutTradeNo($randStr.$order_sn);
        $wxpay->SetSpbillCreateIp($_SERVER['REMOTE_ADDR']);
//        $price = 0.01;
        $wxpay->SetTotalFee($price);
        $wxpay->SetTradeType('JSAPI');
        $wxpay->SetOpenId($openid);
        $ret = $wxpay->PostXmlCurl();
        if(isset($ret['return_code']) && $ret['return_code'] == "SUCCESS" && $ret['result_code'] != "FAIL"){
            $data = [
                'appId' => $ret['appid'],
                'timeStamp' => (string)time(),
                'nonceStr' => $ret['nonce_str'],
                'package' => 'prepay_id='.$ret['prepay_id'],
                'signType' => 'MD5'
            ];
            $data['paySign'] = $this->CreatePaySign($data,$wxpay->GetKey());
            return json(['code'=>0,'msg'=>'success','data'=>$data,'test'=>$ret]);
        }else{
            return json(['code'=>-1,'msg'=>'fail','errorMsg'=>$ret]);
        }
    }

    /**
     * 获取订单金额
     * @param $order_sn
     * @return mixed
     */
    private function GetOrderPrice($order_sn){
        $url = "https://xxx.com/mapp_order/OrderPrice";
        $res = PostCurl($url,['order_sn'=>$order_sn]);
        return $res['price'];
    }

    /**
     * 创建支付时需要的sign
     * @param $data
     * @param $key
     */
    private function CreatePaySign($data,$key){
        //过滤空数组
        $data = array_filter($data);
        //首先正序排序
        ksort($data);
        //将数组转为&key=>value的形式
        $stringA = "";
        foreach ($data as $k => $v) {
            $stringA .= "&".$k."=".$v;
        }
        $stringA = trim($stringA,'&');
        //拼接Key
        $String = $stringA.'&key='.$key;
        //MD5加密后转大写
        return strtoupper(md5($String));
    }
}