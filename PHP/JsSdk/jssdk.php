<?php
    class JSSDK {
        private $appId;
        private $appSecret;
        
        public function __construct($appId, $appSecret) {
            $this->appId = $appId;
            $this->appSecret = $appSecret;
        }
        
        public function getSignPackage($url='') {
            $jsapiTicket = $this->getJsApiTicket();
            
           
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "https://";
            if ($url == '') {
				$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			}
            
            $timestamp = time();
            $nonceStr = $this->createNonceStr();
            
          
            $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
            
            $signature = sha1($string);
//            $access_token = $this->getAccessToken();
            $signPackage = array(
                "appId"     => $this->appId,
                "nonceStr"  => $nonceStr,
                "timestamp" => $timestamp,
                "url"       => $url,
                "signature" => $signature,
//                "rawString" => $string,
//				"access_token" => $access_token
            );
            return $signPackage;
        }
        
        private function createNonceStr($length = 16) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $str = "";
            for ($i = 0; $i < $length; $i++) {
                $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            }
            return $str;
        }
        
        private function getJsApiTicket() {
            $data = json_decode(file_get_contents(VENDOR_PATH .'JsSdk'. DS ."jsapi_ticket.php"));
            if ($data->expire_time < time()) {
                $accessToken = $this->getAccessToken();
              
                // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
                $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
                $res = json_decode($this->httpGet($url));
                $ticket = $res->ticket;
                if ($ticket) {
                    $data->expire_time = time() + 7000;
                    $data->jsapi_ticket = $ticket;
                    $fp = fopen(VENDOR_PATH .'JsSdk'. DS ."jsapi_ticket.php", "w");
                    fwrite($fp, json_encode($data));
                    fclose($fp);
                }
            } else {
                $ticket = $data->jsapi_ticket;
            }
            
            return $ticket;
        }
        
        private function getAccessToken() {
          
            $data = json_decode(file_get_contents(VENDOR_PATH .'JsSdk'. DS ."access_token.php"));
            if ($data->expire_time < time()) {
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
                $res = json_decode($this->httpGet($url), true);
                if (!empty($res['access_token'])) {
                    $data->expire_time = time() + 7000;
                    $data->access_token = $res['access_token'];
                    $fp = fopen(VENDOR_PATH .'JsSdk'. DS ."access_token.php", "w");
                    fwrite($fp, json_encode($data));
                    fclose($fp);
                    return $res['access_token'];
                }
            } else {
                return $data->access_token;
            }
            return false;
        }
        
        private function httpGet($url) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 500);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_URL, $url);
            
            $res = curl_exec($curl);
            curl_close($curl);
            
            return $res;
        }
    }