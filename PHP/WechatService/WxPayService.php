<?php
namespace WechatService;

class WxPayService{
    protected $values;
    protected $key = '商户key';
    private static $unifiedorder_url = "https://api.mch.weixin.qq.com/pay/unifiedorder";

    public function __construct()
    {
        $this->values['appid'] = 'xxxxx';//微信支付分配的公众账号ID
        $this->values['mch_id'] = 'xxxxxx';//微信支付分配的商户号
    }

    /**
     * 返回商户号
     * @return mixed
     */
    public function GetMchID(){
        return $this->values['mch_id'];
    }

    /**
     * 返回key
     * @return mixed
     */
    public function GetKey(){
        return $this->key;
    }

    /**
     * 返回商户号
     * @return mixed
     */
    public function GetSign(){
        return $this->values['sign'];
    }

    /**
     * 商品简单描述，该字段请按照规范传递
     * @param $value
     * @return mixed
     */
    public function SetBody($value){
        return $this->values['body'] = $value;
    }

    /**
     * 商户系统内部订单号
     * @param $value
     * @return mixed
     */
    public function SetOutTradeNo($value){
        return $this->values['out_trade_no'] = $value;
    }

    /**
     * 订单总金额，单位为分
     * @param $value
     * @return mixed
     */
    public function SetTotalFee($value){
        return $this->values['total_fee'] = $value*100;
    }

    /**
     * 	支持IPV4和IPV6两种格式的IP地址。用户的客户端IP
     * @param $value
     * @return mixed
     */
    public function SetSpbillCreateIp($value){
        return $this->values['spbill_create_ip'] = $value;
    }

    /**
     * 回调地址
     * @param $value
     * @return mixed
     */
    public function SetNotifyUrl($value){
        return $this->values['notify_url'] = $value;
    }

    /**
     * 支付有效期
     * @param $value
     * @return mixed
     */
    public function SetTimeExpire($value){
        return $this->values['time_expire'] = $value;
    }

    /**
     * 支付方式
     * JSAPI -JSAPI支付
     * NATIVE -Native支付
     * APP -APP支付
     * @param $value
     * @return mixed
     */
    public function SetTradeType($value){
        return $this->values['trade_type'] = $value;
    }

    /**
     * trade_type=NATIVE时，此参数必传。此参数为二维码中包含的商品ID，商户自行定义。
     * @param $value
     * @return mixed
     */
    public function SetProductId($value){
        return $this->values['product_id'] = $value;
    }

    /**
     * trade_type=JSAPI时（即JSAPI支付），此参数必传，此参数为微信用户在商户对应appid下的唯一标识。
     * @param $value
     * @return mixed
     */
    public function SetOpenId($value){
        return $this->values['openid'] = $value;
    }

    /**
     * 转XML数据格式
     * @return array|string|\think\response\Json
     */
    private function ToXML(){
        if(!is_array($this->values) || count($this->values) <= 0)
        {
            return ReturnData(-1,'数组数据异常！');
        }

        $xml = "<xml>";
        foreach ($this->values as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 生成32为随机数
     * @param int $length
     * @return string
     */
    private function CreateNonceStr($length = 32){
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 生成签名
     * @return string
     */
    private function CreateSign(){
        //过滤空元素
        $arr = array_filter($this->values);
        //按照ASCII码从小到大排序
        ksort($arr);
        //将数组转为&key=>value的形式
        $stringA = "";
        foreach ($arr as $k => $v) {
            $stringA .= "&".$k."=".$v;
        }
        $stringA = trim($stringA,'&');
        //拼接key值
        $stringSignTemp = $stringA.'&key='.$this->key;
        //采用微信默认的加密方式md5
        $string = md5($stringSignTemp);
        //将md5后的值转成大写
        return strtoupper($string);
    }

    /**
     * 以post方式提交xml到对应的接口url
     */
    public function PostXmlCurl(){
        $this->values['nonce_str'] = $this->CreateNonceStr();
        $this->values['sign'] = $this->CreateSign();
        $post_data = $this->ToXML($this->values);
        $xml = $this->postCurl(self::$unifiedorder_url,$post_data);
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }



    /**
     * post方式发送curl请求
     * @param $url
     * @return mixed
     */
    private function postCurl($url,$data){
        $ch = curl_init();
//        $header = "Accept-Charset: utf-8";
        $header = array(
            'Accept-Charset: utf-8',
        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        }else{
            return $tmpInfo;
        }
    }
}