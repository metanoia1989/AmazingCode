<?php
/**
* ua
*/
class UAHelper
{
    protected $_ua;

    function __construct($ua)
    {
        $this->_ua = $ua;
    }

    /**
     * 判断是否微信浏览器
     *
     * <code>
     * (new UA($_SERVER['HTTP_USER_AGENT']))->isWeiXinBrowser();
     * </code>
     * 
     * @return boolean
     */
    public function isWeiXinBrowser()
    {
        $userAgent = $this->_ua;
        return (false !== stripos($userAgent, 'MicroMessenger'));
    }

    /**
     * 判断是否安卓
     *
     * <code>
     * (new UA($_SERVER['HTTP_USER_AGENT']))->isAndroid();
     * </code>
     * 
     * @return boolean
     */
    public function isAndroid()
    {
        $userAgent = $this->_ua;
        return (false !== stripos($userAgent, 'Android'));
    }

    /**
     * 判断是否IOS
     *
     * <code>
     * (new UA($_SERVER['HTTP_USER_AGENT']))->isIOS();
     * </code>
     * 
     * @return boolean
     */
    public function isIOS()
    {
        $userAgent = $this->_ua;
        return !!preg_match('/\(i[^;]+;( U;)? CPU.+Mac OS X/', $userAgent);
    }

    /**
     * 判断是否手机
     *
     * <code>
     * (new UA($_SERVER['HTTP_USER_AGENT']))->isMobile();
     * </code>
     * 
     * @return boolean
     */
    public function isMobile()
    {
        $userAgent = $this->_ua;
        return !!preg_match('/AppleWebKit.*Mobile.*/', $userAgent);
    }
}