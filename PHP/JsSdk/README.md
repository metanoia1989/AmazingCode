# Wechat JS SDK
JS-SDK使用权限签名算法  https://developers.weixin.qq.com/doc/offiaccount/OA_Web_Apps/JS-SDK.html#62     

获取微信的JSSDK需要的参数，这个缓存的思路也很棒，真心不错，简单的一个代码。         
```php
<?php
/**
 * 微信分享
 */
public function wxShare($id) {
    // 获取请帖信息
    $info = db('admin_template_user')
        ->field('uid,title,bg,mini_cover,tpl_id,studio_sid,hl_time,xl_name,xn_name,birthday,child_name,type,banquet_type')
        ->where('id',$id)
        ->find();
    Vendor('JsSdk.jssdk');  // 引入微信jsSdk类
    $config_data = config('kqc_wechat');  // 获取微信公众号配置
    $new = new \jssdk($config_data['appId'], $config_data['appScrect']); // 实例化jsSdk
    $data = $new->GetSignPackage(INVT_URL.'/h5/');  // 生成签名等参数
    if ($info['type'] == 2) {  // 宝宝模板请帖
        $banquet_type = [1=>'满月', 2=>'百日', 3=>'周岁'];  // 宝宝宴席类型
        $data['title'] = $info['title']?$info['title']:'诚邀您参加' . $info['child_name'] . '的'.$banquet_type[$info['banquet_type']].'宴';
        $data['desc'] = '我将在'.date('m月d日',$info['hl_time']).'举办'.$banquet_type[$info['banquet_type']].'宴，欢迎您的到来哦';
    } else {  // 婚礼/结婚证模板请帖
        $data['title'] = $info['title']?$info['title']:'诚邀您参加' . $info['xl_name'] . '&' . $info['xn_name'] . '的婚礼';
        $data['desc'] = '我们将在'.date('m月d日',$info['hl_time']).'举行婚礼，诚挚邀请您的到来';
    }
    $data['link'] = INVT_URL.'/h5/#/?ids='.$id.'&tpl_id='.$info['tpl_id'].'&user_id='.$info['uid'].'&studio_sid='.$info['studio_sid'];  // 跳转链接
    $data['imgurl'] = $info['mini_cover']?ALIYUNOSS_URL.'/'.$info['mini_cover']:get_file_path($info['bg']);  // 微信分享缩略图
    return json(['code'=>0, 'msg'=>'success', 'data'=>$data]);
}
```