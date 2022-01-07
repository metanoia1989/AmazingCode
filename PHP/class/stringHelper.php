<?php
/**
* stringHelper
*/
class stringHelper
{
    /**
     * 字符串连接转驼峰
     *
     * <code>
     * echo (new stringHelper())->camelize('terse_test', '_');
     * </code>
     * 
     * @param  string $string
     * @param  string $separator
     * @return string
     */
    public function camelize($string, $separator = '_')
    {
        $string = strtolower($string);

        // 将 '_' 换成 ' '
        $string = strtr($string, $separator, ' ');
        $string = ucwords($string);
        return strtr($string, [' ' => '']);
    }

    /**
     * 驼峰转字符串连接
     *
     * <code>
     * echo (new stringHelper())->uncamelize('terseTest', '_');
     * </code>
     * 
     * @param  string $string
     * @param  string $separator
     * @return string
     */
    public function uncamelize($string, $separator = '_')
    {
        $string = preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $string);
        return strtolower($string);
    }
}