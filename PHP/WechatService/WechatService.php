<?php
namespace WechatService;

class WechatService{
    private static $APP_ID;                          //微信提供的appid
    private static $APP_SECRET;        //微信提供的secret
    private static $CODE2SESSION;        //微信提供的secret

    public function __construct(){
        self::$APP_ID = 'appid';
        self::$APP_SECRET = 'appsecret';
        self::$CODE2SESSION = 'https://api.weixin.qq.com/sns/jscode2session';
    }

    /**
     * 获取登录凭证校验
     * @param $jsCode
     * @return mixed
     */
    public function returnOpenID($jsCode){
        $data = [
            'appid' => self::$APP_ID,
            'secret' => self::$APP_SECRET,
            'js_code' => $jsCode,
            'grant_type' => 'authorization_code'
        ];
        return GetCurl(self::$CODE2SESSION,$data);
    }

    /**
     * 解密用户信息
     * @param $jscode
     * @param $encryptedData
     * @param $iv
     * @return mixed
     */
    public function decryptData($jscode,$encryptedData,$iv){
        $result = $this->returnOpenID($jscode);
        if(isset($result['errcode']) && $result['errcode']!=0) return json(['code'=>-1,'msg'=>$result['errmsg']]);

        $aesKey = base64_decode($result['session_key']);
        $aesCipher = base64_decode($encryptedData);
        $aesIV = base64_decode($iv);
        $res = openssl_decrypt($aesCipher,"AES-128-CBC",$aesKey,1,$aesIV);
        $data = json_decode($res,true);
        if($data === NULL) return false;
        if($data['watermark']['appid'] != self::$APP_ID) return false;
        return $data;
    }
}