<?php
/**
 * curl get方法
 *
 * @param	string	$url	要请求的url?参数
 * @return	bool|string
 */
function vget($url) {
	$info = curl_init();
	curl_setopt($info, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($info, CURLOPT_HEADER, 0);
	curl_setopt($info, CURLOPT_NOBODY, 0);
	curl_setopt($info, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($info, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($info, CURLOPT_URL, $url);
	$output = curl_exec($info);
	curl_close($info);
	return $output;
}

/**
 * curl post方法
 *
 * @param	int		 $url			要请求的url
 * @param	string	 $params		请求参数
 * @param	int		 $type			请求类型, ==1时为json
 * @param 	string	 $Authorization	请求头Authorization
 * @return	bool|string
 */
function vpost($url,$params='',$type=0, $Authorization='') {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FAILONERROR, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	//https 请求
	if(strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https"){
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	}

	$header = [
		"User-Agent: */*",
		"Accept: application/json; charset=UTF-8;",
	];
	if (is_array($params) && 0 < count($params) && $type != 1) {
		$postBodyString = "";
		foreach ($params as $k => $v) {
			$postBodyString .= "$k=" . $v . "&";
		}
		unset($k, $v);
		$header[] = "content-type: application/x-www-form-urlencoded; charset=UTF-8;";
		$params = substr($postBodyString, 0, -1);
	} else {
		$header[] = "content-type: application/json; charset=UTF-8;";
	}
	if ($Authorization) $header[] = "Authorization: ".$Authorization;
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	$reponse = curl_exec($ch);
	if (curl_errno($ch)) {
		throw new Exception(curl_error($ch), 500);
	} else {
		$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (200 !== $httpStatusCode) {
			curl_close($ch);
			return json_encode(['httpStatusCode'=>$httpStatusCode, 'msg'=>$reponse], JSON_UNESCAPED_UNICODE);
		}
	}

	curl_close($ch);
	return $reponse;
}
