<?php
namespace  app\admin\controller\wechat;

use app\common\basics\AdminBase;
use app\admin\logic\wechat\OaLogic;
use app\common\server\JsonServer;
use app\common\server\ConfigServer;

class Oa extends AdminBase
{
    /**
     * 公众号设置
     */
    public function setOa()
    {
        if($this->request->isAjax()){
            $post = $this->request->post();
            if(isset($post['qr_code']) && !empty($post['qr_code'])) {
                $domain = $this->request->domain();
                $post['qr_code'] = str_replace($domain, '', $post['qr_code']);
            }else{
                $post['qr_code'] = '';
            }
            OaLogic::setOa($post);
            return JsonServer::success('设置成功');
        }
        $oa = OaLogic::getOa();
        return view('setoa', ['oa' => $oa]);
    }

    /**
     * 菜单管理
     */
    public function oaMenu()
    {
        $wechat_menu = ConfigServer::get('menu', 'wechat_menu',[]);
        return view('oamenu', ['menu' => $wechat_menu]);
    }

    /**
     * 发布菜单
     */
    public function pulishMenu()
    {
        $menu = $this->request->post('button');
        if(empty($menu)){
            return JsonServer::error('请设置菜单');
        }
        $result = OaLogic::pulishMenu($menu);
        if($result){
            return JsonServer::success('菜单发布成功');
        }
        return JsonServer::error(OaLogic::getError());
    }
}