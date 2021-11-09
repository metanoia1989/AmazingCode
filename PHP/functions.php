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

/**
 * 是否为移动端
 */
function is_mobile()
{
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    if (isset($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'textml') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'textml')))) {
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

/**
 * User: 意象信息科技 lr
 * Desc: 下载文件
 * @param $url 文件url
 * @param $save_dir 保存目录
 * @param $file_name 文件名
 * @return string
 */
function download_file($url, $save_dir, $file_name)
{
    if (!file_exists($save_dir)) {
        mkdir($save_dir, 0775, true);
    }
    $file_src = $save_dir . $file_name;
    file_exists($file_src) && unlink($file_src);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    $file = curl_exec($ch);
    curl_close($ch);
    $resource = fopen($file_src, 'a');
    fwrite($resource, $file);
    fclose($resource);
    if (filesize($file_src) == 0) {
        unlink($file_src);
        return '';
    }
    return $file_src;
}

/**
 * 线性结构转换成树形结构
 * @param array $data 线性结构数组
 * @param string $sub_key_name 自动生成子数组名
 * @param string $id_name 数组id名
 * @param string $parent_id_name 数组祖先id名
 * @param int $parent_id 此值请勿给参数
 * @return array
 */
function linear_to_tree($data, $sub_key_name = 'sub', $id_name = 'id', $parent_id_name = 'pid', $parent_id = 0)
{
  $tree = [];
  foreach ($data as $row) {
    if ($row[$parent_id_name] == $parent_id) {
      $temp = $row;
      $temp[$sub_key_name] = linear_to_tree($data, $sub_key_name, $id_name, $parent_id_name, $row[$id_name]);
      $tree[] = $temp;
    }
  }
  return $tree;
}

/**
 * 生成会员码
 * @return 会员码
 */
function create_user_sn($prefix = '', $length = 8)
{
    $rand_str = '';
    for ($i = 0; $i < $length; $i++) {
        $rand_str .= mt_rand(0, 9);
    }
    $sn = $prefix . $rand_str;
    $user = User::where(['sn' => $sn])->findOrEmpty();
    if (!$user->isEmpty()) {
        return create_user_sn($prefix, $length);
    }
    return $sn;
}

//生成用户邀请码
function generate_invite_code()
{
    $letter_all = range('A', 'Z');
    shuffle($letter_all);
    //排除I、O字母
    $letter_array = array_diff($letter_all, ['I', 'O', 'D']);
    //排除1、0
    $num_array = range('2', '9');
    shuffle($num_array);

    $pattern = array_merge($num_array, $letter_array, $num_array);
    shuffle($pattern);
    $pattern = array_values($pattern);

    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $pattern[mt_rand(0, count($pattern) - 1)];
    }

    $code = strtoupper($code);
    $check = User::where('distribution_code', $code)->findOrEmpty();
    if (!$check->isEmpty()) {
        return generate_invite_code();
    }
    return $code;
}

/**
 * User: 意象信息科技 lr
 * Desc：生成密码密文
 * @param $plaintext string 明文
 * @param $salt string 密码盐
 * @return string
 */
function create_password($plaintext, $salt)
{
    $salt = md5('y' . $salt . 'x');
    $salt .= '2021';
    return md5($plaintext . $salt);
}

/**
 * User: 意象信息科技 mjf
 * Desc: 用时间生成订单编号
 * @param $table
 * @param $field
 * @param string $prefix
 * @param int $rand_suffix_length
 * @param array $pool
 * @return string
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 */
function createSn($table, $field, $prefix = '', $rand_suffix_length = 4, $pool = [])
{
    $suffix = '';
    for ($i = 0; $i < $rand_suffix_length; $i++) {
        if (empty($pool)) {
            $suffix .= rand(0, 9);
        } else {
            $suffix .= $pool[array_rand($pool)];
        }
    }
    $sn = $prefix . date('YmdHis') . $suffix;
    if (Db::name($table)->where($field, $sn)->find()) {
        return createSn($table, $field, $prefix, $rand_suffix_length, $pool);
    }
    return $sn;
}

/**
 * note 生成验证码
 * @param int $length 验证码长度
 * @return string
 */
function create_sms_code($length = 4)
{
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= rand(0, 9);
    }
    return $code;
}

/**
 * 生成商品编码
 * 8位
 */
function create_goods_code($shop_id)
{
    $code =  mt_rand(10000000, 99999999);
    $goods = Goods::where([
        'code' => $code,
        'shop_id' => $shop_id,
        'del' => 0
    ])->findOrEmpty();
    if($goods->isEmpty()) {
        return $code;
    }
    create_goods_code();
}

/**
 * 是否在cli模式
 */
if (!function_exists('is_cli')) {
    function is_cli()
    {
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }
}

/**
 * Notes:判断文件是否存在（远程和本地文件）
 * @param $file string 完整的文件链接
 * @return bool
 */
function check_file_exists($file)
{
    //远程文件
    if ('http' == strtolower(substr($file, 0, 4))) {

        $header = get_headers($file, true);

        return isset($header[0]) && (strpos($header[0], '200') || strpos($header[0], '304'));

    } else {

        return file_exists($file);
    }
}

/**
 * 将图片切成圆角
 */
function rounded_corner($src_img)
{
    $w = imagesx($src_img);//微信头像宽度 正方形的
    $h = imagesy($src_img);//微信头像宽度 正方形的
    $w = min($w, $h);
    $h = $w;
    $img = imagecreatetruecolor($w, $h);
    //这一句一定要有
    imagesavealpha($img, true);
    //拾取一个完全透明的颜色,最后一个参数127为全透明
    $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
    imagefill($img, 0, 0, $bg);
    $r = $w / 2; //圆半径
//    $y_x = $r; //圆心X坐标
//    $y_y = $r; //圆心Y坐标
    for ($x = 0; $x < $w; $x++) {
        for ($y = 0; $y < $h; $y++) {
            $rgbColor = imagecolorat($src_img, $x, $y);
            if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                imagesetpixel($img, $x, $y, $rgbColor);
            }
        }
    }
    unset($src_img);
    return $img;
}

/**
 * Notes:去掉名称中的表情
 * @param $str
 * @return string|string[]|null
 * @author: cjhao 2021/3/29 15:56
 */
function filterEmoji($str)
{
    $str = preg_replace_callback(
        '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);
    return $str;
}

/**
 * Notes:生成一个范围内的随机浮点数
 * @param int $min 最小值
 * @param int $max 最大值
 * @return float|int 返回随机数
 */
function random_float($min = 0, $max = 1)
{
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

/**
 * Notes: 获取文件扩展名
 * @param $file
 * @author 段誉(2021/7/7 18:03)
 * @return mixed
 */
if (!function_exists('get_extension')) {
    function get_extension($file)
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }
}

/**
 * Notes: 删除目标目录
 * @param $path
 * @param $delDir
 * @author 段誉(2021/7/7 18:19)
 * @return bool
 */
if (!function_exists('del_target_dir')) {
    function del_target_dir($path, $delDir)
    {
        $handle = opendir($path);
        if ($handle) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$path/$item")) {
                        del_target_dir("$path/$item", $delDir);
                    } else {
                        unlink("$path/$item");
                    }
                }
            }
            closedir($handle);
            if ($delDir) {
                return rmdir($path);
            }
        } else {
            if (file_exists($path)) {
                return unlink($path);
            }
            return false;
        }
    }
}


/**
 * Notes: 获取本地版本数据
 * @return mixed
 * @author 段誉(2021/7/7 18:18)
 */
if (!function_exists('local_version')) {
    function local_version()
    {
        if(!file_exists('./upgrade/')) {
            // 若文件夹不存在，先创建文件夹
            mkdir('./upgrade/', 0777, true);
        }
        if(!file_exists('./upgrade/version.json')) {
            // 获取本地版本号
            $version = config('project.version');
            $data = ['version' => $version];
            $src = './upgrade/version.json';
            // 新建文件
            file_put_contents($src, json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        $json_string = file_get_contents('./upgrade/version.json');
        // 用参数true把JSON字符串强制转成PHP数组
        $data = json_decode($json_string, true);
        return $data;
    }
}

/**
 * Notes: 获取ip
 * @author 段誉(2021/7/9 10:19)
 * @return array|false|mixed|string
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        return $cip;
    }
}