<?php
/**
 * 获取jscode，用户获取Openid 的
 */
function getCode() {
    $code = input('get.code');
    $host = input('get.host');
    if ($code) {
        $host = str_replace('井号','#', $host);
        $param = strstr($host, '?') ? '&code='.$code : '?code='.$code;
        echo "<script>window.location.href='".$host.$param."'</script>";exit;
    } else {
        $scope = input('scope') ?? 'snsapi_base';  // snsapi_base静默登录, snsapi_userinfo授权登录
        $domain = KQC_URL;
        $config_data = config('wechat');
        $redirect_uri = $domain . '/api/api/getCode?host='.str_replace('#', '井号', urldecode($host));  // 获取code后回到这个方法
        $redirect_uri = urlencode($redirect_uri);
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $config_data['appId'] . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=' . $scope . '#wechat_redirect';
        header("location:" . $url);
    }
}


/**
 * 微信网页授权(静默登录)
 */
function getUser($code, $getinfo=1) {
    $config_data = config('wechat');
    // 获取openid
    $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config_data['appId'] . '&secret=' . $config_data['appScrect'] . '&code=' . $code . '&grant_type=authorization_code';
    $data = vget($url);
    $token = json_decode($data, true);
    if (!empty($token['errcode']) && $token['errcode'] == 40163) {
        return json(['code' => 2, 'msg' => '登录失败']);
    }
    if ($getinfo != 1) return json(['code'=>0, 'msg'=>'success', 'data'=>$token]);
    // 获取用户信息
    $userInfo = vget('https://api.weixin.qq.com/sns/userinfo?access_token='.$token['access_token'].'&openid='.$token['openid'].'&lang=zh_CN');
    $userInfo = json_decode($userInfo, true);

    return json(['code'=>0, 'msg'=>'success', 'data'=>$userInfo]);
}


/**
 * 获取微信分享参数
 */
function getShareParam() {
    $url = $this->request->post('url');
    if (is_null($url)) return json(['code'=>1, 'msg'=>'缺少参数']);

    // 实例化jsSdk
    $config_data = config("app.wechat");
    $new = new JsSdk($config_data["appId"], $config_data["appSecret"]);

    // 生成签名等参数
    $data = $new->GetSignPackage($url);

    return json(['code'=>0, 'msg'=>'success', 'data'=>$data]);
}