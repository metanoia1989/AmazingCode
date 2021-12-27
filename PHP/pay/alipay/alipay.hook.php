<?php

//发送post数据
function alipay_curlpost($url, $arr, $type = 'arr') {
	$result = pe_curl_post($url, $arr, $type);
    return $result;
	// return json_decode($result, true);
}

//发送get数据
function alipay_curlget($url) {
	return pe_curl_get($url);
}

// 支付包签名处理
class AlipaySign
{

    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key 私钥字符串
     * @return 签名结果
     */
    function rsaSign($data, $private_key, $type = 'RSA') {
        $search = [
            "-----BEGIN RSA PRIVATE KEY-----",
            "-----END RSA PRIVATE KEY-----",
            "\n",
            "\r",
            "\r\n"
        ];
 
        // $private_key=str_replace($search,"",$private_key);
        // $private_key=$search[0] . PHP_EOL . wordwrap($private_key, 64, "\n", true) . PHP_EOL . $search[1];
        $res=openssl_get_privatekey($private_key);
 
        if($res)
        {
            if($type == 'RSA'){
                openssl_sign($data, $sign,$res);
            }elseif($type == 'RSA2'){
                //OPENSSL_ALGO_SHA256
                openssl_sign($data, $sign,$res,OPENSSL_ALGO_SHA256);
            }
            openssl_free_key($res);
        }else {
            var_dump(openssl_error_string());
            exit("私钥格式有误");
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    //生成json數據的方法
    public function getStr($arr,$type = 'RSA'){
        //筛选
        if(isset($arr['sign'])){
            unset($arr['sign']);
        }
        if(isset($arr['sign_type']) && $type == 'RSA'){
            unset($arr['sign_type']);
        }
        //排序
        ksort($arr);

        //拼接
        return urldecode(http_build_query($arr));
    }

    public function makeSign($arr, $private_key, $type = 'RSA')
    {
        return $this->rsaSign($this->getStr($arr, $type), $private_key, $type);
    }
}

//支付宝 扫码支付
function alipay_webpay($order_id) {
	global $db, $pe;

    include_once($pe['path_root']."public/plugin/payment/alipay/alipay.config.php");

	$order = $db->pe_select(order_table($order_id), array('order_id'=>pe_dbhold($order_id)));
	if ($order['order_state'] != 'wpay') {
		return array('result'=>false, 'show'=>'请勿重复支付');		
	}
	//统一下单接口

    $bizContent = [
        'out_trade_no' => "{$order['order_id']}_".rand(100,999),
        'total_amount' => $order['order_money']*100,
        'subject' =>  pe_cut($order['order_name'], 13, '...'),
    ];
    ksort($bizContent);

	$params['app_id'] = trim($payment['alipay_appid']);  // 支付宝分配给开发者的应用ID
    $params['method'] = 'alipay.trade.precreate';
    $params['format'] = 'JSON';
    $params['charset'] = 'gbk';
    $params['sign_type'] = 'RSA';

    $private_key = $payment['alipay_my_private_key'];
    $AlipaySign = new AlipaySign();
    $params['sign'] = $AlipaySign->makeSign($bizContent, $private_key, 'RSA'); 
    $params['timestamp'] = date('Y-m-d H:i:s');
    $params['version'] = '1.0'; 
    $params['notify_url'] = $alipay_config['notify_url'] ; 
    $params['biz_content'] = json_encode($bizContent);

	//发送xml下单请求
	$json = alipay_curlpost('https://openapi.alipay.com/gateway.do', $params);
    $json = json_decode(converToUtf8($json), true);
    $json = $json["alipay_trade_precreate_response"];
	if ($json['msg'] == 'Success' && $json['code'] == '10000') {
		return array('result'=>true, 'qrcode'=> $json["qr_code"]);
	} else {
		return array('result'=>false, 'show'=>"{$json['msg']}, {$json['sub_code']}, {$json['sub_msg']}");
	}
}
