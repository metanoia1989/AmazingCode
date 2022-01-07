<?php
/**
* MobileHelper
*/
class mobileHelper
{
    /**
     * 手机号检测
     *
     * <code>
     * echo (new mobileHelper())->checkMobile(15711111111);
     * </code>
     * 
     * @param  string $mobile
     * @return string
     */
    public function checkMobile($mobile)
    {
        return preg_match('#^1\d{10}$#', $mobile);
    }

    /**
     * 隐藏手机号中间四位
     *
     * <code>
     * echo (new mobileHelper())->hideMobile(15711111111); // 157****1111
     * </code>
     * 
     * @param  string $mobile
     * @return string
     */
    public function hideMobile($mobile)
    {
        return substr_replace($mobile, '****', 3, 4);
    }
}