<?php
namespace  app\admin\controller\wechat;

use app\common\basics\AdminBase;
use app\admin\logic\wechat\ReplyLogic;
use app\common\server\JsonServer;
use app\common\server\ConfigServer;
use app\common\model\wechat\Wechat;
use app\admin\validate\wechat\ReplyValidate;

class Reply extends AdminBase
{
    public function lists(){
        if($this->request->isAjax()){
            $get = $this->request->get();
            $data = ReplyLogic::lists($get);
            return JsonServer::success('', $data);
        }

        $type_list= Wechat::getCustomReply();
        return view('lists', ['type_list' => $type_list]);
    }

    public function add()
    {
        if($this->request->isAjax()){
            try{
                $post = $this->request->post();
                validate(ReplyValidate::class)->scene($post['reply_type'])->check($post);
            }catch(\think\exception\ValidateException $e) {
                return JsonServer::error($e->getError());
            }
            $result= ReplyLogic::add($post);
            if($result) {
                return JsonServer::success('新增成功');
            }else{
                return JsonServer::error('新增失败');
            }
        }

        $type = $this->request->get('type');
        $template = 'add_'.$type;
        return view($template);
    }

    public function edit()
    {
        if($this->request->isAjax()){
            try{
                $post = $this->request->post();
                validate(ReplyValidate::class)->scene($post['reply_type'])->check($post);
            }catch(\think\exception\ValidateException $e) {
                return JsonServer::error($e->getError());
            }
            $result= ReplyLogic::edit($post);
            if($result) {
                return JsonServer::success('编辑成功');
            }else{
                return JsonServer::error('编辑失败');
            }
        }

        $id = $this->request->get('id');
        $detail = ReplyLogic::getReply($id);
        $template = 'edit_'.$detail['reply_type'];
        return view($template, ['detail' => $detail]);
    }

    public function del(){
        if($this->request->isPost()) {
            try{
                $post = $this->request->post();
                validate(ReplyValidate::class)->scene('del')->check($post);
            }catch(\think\exception\ValidateException $e) {
                return JsonServer::error($e->getError());
            }
            $result = ReplyLogic::del($post['id']);
            if($result) {
                return JsonServer::success('删除成功');
            }else{
                return JsonServer::error('删除失败');
            }
        }else{
            return JsonServer::error('请求类型错误');
        }
    }

    public function changeFields(){
        $pk_value = $this->request->post('id');
        $field = $this->request->post('field');
        $field_value = $this->request->post('value');
        $reply_type = $this->request->post('reply_type');
        $result = ReplyLogic::changeFields($pk_value, $field, $field_value,$reply_type);
        if ($result) {
            return JsonServer::success('修改成功');
        }
        return JsonServer::error('修改失败');
    }
}