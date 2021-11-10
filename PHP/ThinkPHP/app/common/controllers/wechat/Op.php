<?php
namespace  app\admin\controller\wechat;

use app\common\basics\AdminBase;
use app\admin\logic\wechat\OpLogic;
use app\common\server\JsonServer;

class Op extends AdminBase
{
    /**
     * 开放平台配置
     */
    public function config()
    {
        if($this->request->isAjax()){
            $post = $this->request->post();
            OpLogic::setConfig($post);
            return JsonServer::success('设置成功');
        }

        return view('config', ['config' => OpLogic::getConfig(['app_id','secret'])]);
    }
}