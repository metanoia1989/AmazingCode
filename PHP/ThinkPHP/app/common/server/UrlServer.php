<?php
// +----------------------------------------------------------------------
// | likeshop开源商城系统
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | gitee下载：https://gitee.com/likeshop_gitee
// | github下载：https://github.com/likeshop-github
// | 访问官网：https://www.likeshop.cn
// | 访问社区：https://home.likeshop.cn
// | 访问手册：http://doc.likeshop.cn
// | 微信公众号：likeshop技术社区
// | likeshop系列产品在gitee、github等公开渠道开源版本可免费商用，未经许可不能去除前后端官方版权标识
// |  likeshop系列产品收费版本务必购买商业授权，购买去版权授权后，方可去除前后端官方版权标识
// | 禁止对系统程序代码以任何目的，任何形式的再发布
// | likeshop团队版权所有并拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeshop.cn.team
// +----------------------------------------------------------------------


namespace app\common\server;


/**
 * URL转换 服务类
 * Class UrlServer
 * @package app\common\server
 */
class UrlServer
{
    /**
     * Notes: 获取文件全路径
     * @param string $uri
     * @author 张无忌(2021/1/29 9:42)
     * @return string
     */
    public static function getFileUrl($uri='',$type='')
    {
        if (strstr($uri, 'http://'))  return $uri;
        if (strstr($uri, 'https://')) return $uri;

        $engine = ConfigServer::get('storage', 'default', 'local');
        if ($engine === 'local') {
            //图片分享处理
            if ($type && $type == 'share') {
                return ROOT_PATH . $uri;
            }
            $domain = request()->domain();
            return $domain . '/' . $uri;
        } else {
            $config = ConfigServer::get('storage_engine', $engine);
            return $config['domain'] . $uri;
        }
    }

    /**
     * NOTE: 设置文件路径转相对路径
     * @author: 张无忌
     * @param string $uri
     * @return mixed
     */
    public static function setFileUrl($uri='')
    {
        $engine = ConfigServer::get('storage', 'default', 'local');
        if ($engine === 'local') {
            $domain = request()->domain();
            return str_replace($domain.'/', '', $uri);
        } else {
            $config = ConfigServer::get('storage_engine', $engine);
            return str_replace($config['domain'], '', $uri);
        }
    }
}