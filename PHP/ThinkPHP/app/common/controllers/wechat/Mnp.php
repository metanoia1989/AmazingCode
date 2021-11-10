<?php
namespace  app\admin\controller\wechat;

use app\common\basics\AdminBase;
use app\admin\logic\wechat\MnpLogic;
use app\common\server\JsonServer;
use app\common\server\ConfigServer;

class Mnp extends AdminBase
{
    /**
     * 小程序设置
     */
    public function setMnp()
    {
        if($this->request->isAjax()) {
            $post = $this->request->post();
            if(isset($post['qr_code'])){
                $domain = $this->request->domain();
                $post['qr_code'] = str_replace($domain, '', $post['qr_code']);
            }else{
                $post['qr_code'] = '';
            }
            MnpLogic::setMnp($post);
            return JsonServer::success('设置成功');
        }

        $mnp = MnpLogic::getMnp();
        return view('set_mnp', ['mnp' => $mnp]);
    }
}