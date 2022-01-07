<?php
/**
 * HTTP CLIENT
 */
class HttpClientHelper
{
    /**
     * http get
     *
     * <code>
     * HttpClient::get('http://www.demo.com/api');
     * </code>
     * 
     * @param  string  $url
     * @param  integer $timeOut
     * @return mixed
     * @throws \Exception
     */
    public static function get($url, $timeOut = 5)
    {
        $headers = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $errorNo = curl_errno($ch);
        $errorInfo = curl_error($ch);
        curl_close($ch);
        if ($errorNo) {
            throw new \Exception($errorInfo, -__LINE__);
        }
        return $response;
    }

    /**
     * http post
     *
     * <code>
     * HttpClient::post('http://www.demo.com/api', json_encode($params), 'json', 5);
     * </code>
     * 
     * @param  string       $url
     * @param  array|string $data
     * @param  string       $httpHeaderType
     * @param  integer      $timeOut
     * @return mixed
     * @throws \Exception
     */
    public static function post($url, $data, $httpHeaderType = '', $timeOut = 10)
    {
        $ch = curl_init();
        if ($httpHeaderType == 'json') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json;charset=UTF-8'));
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $returnValue = curl_exec($ch);
        $errorNo = curl_errno($ch);
        $errorInfo = curl_error($ch);
        curl_close($ch);
        if ($errorNo) {
            throw new \Exception($errorInfo, -__LINE__);
        }
        return $returnValue;
    }
}