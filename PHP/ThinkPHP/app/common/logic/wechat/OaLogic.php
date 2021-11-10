<?php
// +----------------------------------------------------------------------
// | LikeShop有特色的全开源社交分销电商系统
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 商业用途务必购买系统授权，以免引起不必要的法律纠纷
// | 禁止对系统程序代码以任何目的，任何形式的再发布
// | 微信公众号：好象科技
// | 访问官网：http://www.likemarket.net
// | 访问社区：http://bbs.likemarket.net
// | 访问手册：http://doc.likemarket.net
// | 好象科技开发团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | Author: LikeShopTeam-段誉
// +----------------------------------------------------------------------

namespace app\admin\logic\wechat;

use app\common\basics\Logic;
use app\common\server\ConfigServer;
use app\common\server\UrlServer;
use app\common\server\WeChatServer;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\Exception;

class OaLogic extends Logic
{
    public static function setOa($post)
    {
        ConfigServer::set('oa','name', trim($post['name']));
        ConfigServer::set('oa','original_id', trim($post['original_id']));
        ConfigServer::set('oa','app_id',$post['app_id']);
        ConfigServer::set('oa','secret',$post['app_secret']);
        ConfigServer::set('oa','token',$post['token']);
        ConfigServer::set('oa','encoding_ses_key',$post['encoding_ses_key']);
        ConfigServer::set('oa','encryption_type',$post['encryption_type']);
        ConfigServer::set('oa','qr_code',$post['qr_code']);
    }

    public static function getOa()
    {
        $domain_name = $_SERVER['SERVER_NAME'];
        $qr_code = ConfigServer::get('oa', 'qr_code', '');
        $config = [
            'name'              => ConfigServer::get('oa', 'name', ''),
            'original_id'       => ConfigServer::get('oa', 'original_id', ''),
            'qr_code'           => $qr_code,
            'abs_qr_code'       => UrlServer::getFileUrl($qr_code),
            'app_id'            => ConfigServer::get('oa', 'app_id', ''),
            'app_secret'        => ConfigServer::get('oa', 'secret', ''),
            'url'               => url('api/wechat/index',[],'',true),
            'token'             => ConfigServer::get('oa', 'token', 'LikeMall'),
            'encoding_ses_key'  => ConfigServer::get('oa', 'encoding_ses_key', ''),
            'encryption_type'   => ConfigServer::get('oa', 'encryption_type', 1),
            'business_domain'   => $domain_name,
            'safety_domain'     => $domain_name,
            'auth_domain'       => $domain_name,
        ];
        return $config;
    }

    /**
     * 发布菜单
     */
    public static function pulishMenu($menu)
    {
        try {
            $config = WeChatServer::getOaConfig();

            if(empty($config['app_id']) || empty($config['secret'])){
                throw new \think\Exception('请先配置微信公众号参数');
            }

            $app = Factory::officialAccount($config);

            $result = $app->menu->create($menu);

            if($result['errcode'] == 0){
                ConfigServer::set('menu','wechat_menu',$menu);
                return true;
            }
            self::$error = '菜单发布失败:'.json_encode($result);
            return false;
        } catch (\think\Exception $e){
            self::$error = $e->getError();
            return false;
        } catch(\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }
}