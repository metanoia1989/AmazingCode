<?php
/**
 * 数组转换字符串（以逗号隔开）
 * @param 
 * @author Michael_xu
 * @return 
 */
function arrayToString($array)
{
    if (!is_array($array)) {
        $data_arr[] = $array;
    } else {
    	$data_arr = $array;
    }
    $data_arr = array_filter($data_arr); //数组去空
    $data_arr = array_unique($data_arr); //数组去重
    $data_arr = array_merge($data_arr);
    $string = $data_arr ? ','.implode(',', $data_arr).',' : '';
    return $string ? : '';
}

/**
 * 字符串转换数组（以逗号隔开）
 * @param 
 * @author Michael_xu
 * @return 
 */
function stringToArray($string)
{
    if (is_array($string)) {
        $data_arr = array_unique(array_filter($string));
    } else {
        $data_arr = $string ? array_unique(array_filter(explode(',', $string))) : [];
    }
    $data_arr = $data_arr ? array_merge($data_arr) : [];
    return $data_arr ? : [];
}

/**
 * 获取 IP  地理位置
 * 新浪IP 淘宝IP接口
 * @Return: array
 */
function getCity($ip = '')
{
    if($ip == ''){
        $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
        $ip=json_decode(file_get_contents($url),true);
        $data = $ip;
    }else{
        $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
        $ip=json_decode(file_get_contents($url));   
        if((string)$ip->code=='1'){
           return false;
        }
        $data = (array)$ip->data;
    }
    
    return $data;   
}

/**
*判断浏览器类型
**/
function getBrowser() {
    $user_OSagent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_OSagent, "Maxthon") && strpos($user_OSagent, "MSIE")) {
        $visitor_browser = "Maxthon(Microsoft IE)";
    } elseif (strpos($user_OSagent, "Maxthon 2.0")) {
        $visitor_browser = "Maxthon 2.0";
    } elseif (strpos($user_OSagent, "Maxthon")) {
        $visitor_browser = "Maxthon";
    } elseif (strpos($user_OSagent, "Edge")) {
        $visitor_browser = "Edge";
    } elseif (strpos($user_OSagent, "Trident")) {
        $visitor_browser = "IE";
    } elseif (strpos($user_OSagent, "MSIE")) {
        $visitor_browser = "IE";
    } elseif (strpos($user_OSagent, "MSIE")) {
        $visitor_browser = "MSIE 较高版本";
    } elseif (strpos($user_OSagent, "NetCaptor")) {
        $visitor_browser = "NetCaptor";
    } elseif (strpos($user_OSagent, "Netscape")) {
        $visitor_browser = "Netscape";
    } elseif (strpos($user_OSagent, "Chrome")) {
        $visitor_browser = "Chrome";
    } elseif (strpos($user_OSagent, "Lynx")) {
        $visitor_browser = "Lynx";
    } elseif (strpos($user_OSagent, "Opera")) {
        $visitor_browser = "Opera";
    } elseif (strpos($user_OSagent, "MicroMessenger")) {
        $visitor_browser = "微信浏览器";
    } elseif (strpos($user_OSagent, "Konqueror")) {
        $visitor_browser = "Konqueror";
    } elseif (strpos($user_OSagent, "Mozilla/5.0")) {
        $visitor_browser = "Mozilla";
    } elseif (strpos($user_OSagent, "Firefox")) {
        $visitor_browser = "Firefox";
    } elseif (strpos($user_OSagent, "U")) {
        $visitor_browser = "Firefox";
    } else {
        $visitor_browser = "其它";
    }
    return $visitor_browser;
}


/**
*判断是否是移动端
**/
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
    {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
    {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 判断手机发送的客户端标志,兼容性有待提高,把常见的类型放到前面
    if (isset ($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords = array (
            'android',
            'iphone',
            'samsung',
            'ucweb',
            'wap',
            'mobile',
            'nokia',
            'huawei',
            'sony',
            'ericsson',
            'mot',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'ipod',
            'blackberry',
            'meizu',
            'netfront',
            'symbian',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
        {
            return true;
        }
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        }
    }
    return false;
}


//字符串转Unicode编码
function unicode_encode($strLong) {
    $strArr = preg_split('/(?<!^)(?!$)/u', $strLong);//拆分字符串为数组(含中文字符)
    $resUnicode = '';
    foreach ($strArr as $str)
    {
        $bin_str = '';
        $arr = is_array($str) ? $str : str_split($str);//获取字符内部数组表示,此时$arr应类似array(228, 189, 160)
        foreach ($arr as $value)
        {
            $bin_str .= decbin(ord($value));//转成数字再转成二进制字符串,$bin_str应类似111001001011110110100000,如果是汉字"你"
        }
        $bin_str = preg_replace('/^.{4}(.{4}).{2}(.{6}).{2}(.{6})$/', '$1$2$3', $bin_str);//正则截取, $bin_str应类似0100111101100000,如果是汉字"你"
        $unicode = dechex(bindec($bin_str));//返回unicode十六进制
        $_sup = '';
        for ($i = 0; $i < 4 - strlen($unicode); $i++)
        {
            $_sup .= '0';//补位高字节 0
        }
        $str =  '\\u' . $_sup . $unicode; //加上 \u  返回
        $resUnicode .= $str;
    }
    return $resUnicode;
  }
  //Unicode编码转字符串方法1
  function unicode_decode($name)
  {
    // 转换编码，将Unicode编码转换成可以浏览的utf-8编码
    $pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
    preg_match_all($pattern, $name, $matches);
    if (!empty($matches))
    {
      $name = '';
      for ($j = 0; $j < count($matches[0]); $j++)
      {
        $str = $matches[0][$j];
        if (strpos($str, '\\u') === 0)
        {
          $code = base_convert(substr($str, 2, 2), 16, 10);
          $code2 = base_convert(substr($str, 4), 16, 10);
          $c = chr($code).chr($code2);
          $c = iconv('UCS-2', 'UTF-8', $c);
          $name .= $c;
        }
        else
        {
          $name .= $str;
        }
      }
    }
    return $name;
  }

//Unicode编码转字符串
function unicode_decode2($str){
$json = '{"str":"' . $str . '"}';
$arr = json_decode($json, true);
if (empty($arr)) return '';
return $arr['str'];
}



/**
 * 处理提取学信网学籍信息
 */
function extract_xuexin_info($html)
{
    $dom = new DOMDocument();
    $dom->loadHTMLFile("./test.html");

    $fixedPart = $dom->getElementById("fixedPart");

    $data = [
        "username_avatar" => "",
        "sex" => "未知",
        "academy" => "",
        "major" => "",
        "join_school_time" => "",
        "student_status" => "",
    ];

    // 提取头像
    $imgs = $fixedPart->getElementsByTagName("img");
    foreach($imgs as $img) {
        if ($img->getAttribute('class') == 'by_img') {
            $data["username_avatar"] = "https://www.chsi.com.cn".$img->getAttribute('src');
        }
    }

    // 提取院校、专业、入学时间、学籍状态
    $keys = [
        "性别" => "sex",
        "院校" => "academy",
        "专业" => "major",
        "入学时间" => "join_school_time",
        "学籍状态" => "student_status",
    ];
    $xpath = new DOMXPath($dom);
    $tds = $xpath->query('//*[@id="fixedPart"]//td');
    foreach ($tds as $i => $td) {
        // 键对应的下一个td就是对应的值
        if (array_key_exists(trim($td->textContent), $keys)) {
            $key = $keys[$td->textContent];
            $data[$key] = trim($tds[$i+1]->textContent); 
        }
    }

    return $data;
}


/**
 * Convert a key => value array to a 'key: value' array for headers
 *
 * @param array $array Dictionary of header values
 * @return array List of headers
 */
function flatten($array) {
    $return = array();
    foreach ($array as $key => $value) {
        $return[] = sprintf('%s: %s', $key, $value);
    }
    return $return;
}