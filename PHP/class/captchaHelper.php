<?php
/**
 * 验证码类
 */
class captchaHelper
{
    /**
     * 字体文件
     */
    private $_fontFile;

    /**
     * 验证码长度
     * 
     * @var integer
     */
    private $_codeSize = 4;

    /**
     * Captcha constructor.
     * 
     * @param $fontFile 字体文件
     */
    public function __construct($fontFile)
    {
        $this->_fontFile = $fontFile;
    }

    /**
     * 获取验证码
     *
     * <code>
     * echo (new captchaHelper())->getVerifyCode(4);
     * </code>
     * 
     * @param  integer $codeSize
     * @return string
     */
    public function getVerifyCode($codeSize)
    {
        $charset = 'abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789';
        $verifyCode = '';
        $charsetSize = strlen($charset) - 1;
        for ($i = 0; $i < $codeSize; $i++) {
            $verifyCode .= $charset[mt_rand(0, $charsetSize)];
        }
        $this->_codeSize = $codeSize;
        return $verifyCode;
    }

    /**
     * 创建验证码图形
     *
     * <code>
     * echo (new captchaHelper())->create('abcd', 200, 80);
     * </code>
     * 
     * @param  string  $verifyCode
     * @param  integer $width
     * @param  integer $height
     * @param  integer $fontSize
     * @return mixed
     */
    public function create($verifyCode, $width, $height, $fontSize = 20)
    {
        $im = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        //填充图像
        imagefill($im, 0, 0, $white);

        // 随机画点,已经改为划星星了
        for ($i = 0; $i < $width; $i++) {
            $randColor = imagecolorallocate($im, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($im, mt_rand(1, 5), mt_rand(0, $width), mt_rand(0, $height), '*', $randColor);
        }

        // 随机画线,线条数量=字符数量（随便）
        for ($i = 0; $i < $this->_codeSize; $i++) {
            $randColor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imageline($im, 0, mt_rand(0, $height), $width, mt_rand(0, $height), $randColor);
        }

        // 计算字符距离
        $_x = intval($width / $this->_codeSize);
        // 字符显示在图片70%的位置
        $_y = intval($height * 0.7);
        for ($i = 0; $i < strlen($verifyCode); $i++) {
            $randColor = imagecolorallocate($im, mt_rand(0, 150), mt_rand(0, 150), mt_rand(0, 150));
            imagettftext($im, $fontSize, mt_rand(-30, 30), $i * $_x + 3, $_y, $randColor, $this->_fontFile, $verifyCode[$i]);
        }
        return $im;
    }

    /**
     * 获取PNG图片
     *
     * <code>
     * echo (new captchaHelper())->getPNG('abcd', 200, 80);
     * </code>
     * 
     * @param  string  $verifyCode
     * @param  integer $width
     * @param  integer $height
     * @param  integer $fontSize
     * @return image
     */
    public function getPNG($verifyCode, $width, $height, $fontSize = 20)
    {
        $im = $this->create($verifyCode, $width, $height, $fontSize);

        ob_start();
        imagepng($im);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($im);

        header('Content-type: image/png');
        return $imageData;
    }
}