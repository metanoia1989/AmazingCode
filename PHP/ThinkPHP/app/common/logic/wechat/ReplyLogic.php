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
use app\common\model\wechat\WechatReply;
use app\common\model\wechat\Wechat;

class ReplyLogic extends Logic
{
    public static function lists($get)
    {
        $where[] = ['del','=',0];

        if(isset($get['type'])){ // 回复类型
            $where[] = ['reply_type','=',$get['type']];
        }

        $count = WechatReply::where($where)->count();
        $list = WechatReply::where($where)
            ->order([
                'sort' => 'asc',
                'id' => 'desc'
            ])
            ->page($get['page'],$get['limit'])
            ->select()
            ->toArray();

        foreach ($list as $key =>  $reply) {
            // 内容类型
            $reply['content_type'] && $list[$key]['content_type'] = '文本';
            // 匹配类型
            switch ($reply['matching_type']){
                case 1:
                    $list[$key]['matching_type'] = '全匹配';
                    break;
                case 2:
                    $list[$key]['matching_type'] = '模糊匹配';
                    break;
            }
        }
        return ['count'=>$count,'list'=>$list];
    }

    public static function add($post)
    {
        $post['create_time'] = time();
        $post['del'] = 0;

        if($post['reply_type'] !== WeChat::msg_type_text && $post['status']){
            // 除了关键词回复，其他回复类型开启记录只允许一条,若当前正在新增的记录将是开启状态，则该回复类型下的现有记录需先更新为停用状态
            WechatReply::where(['reply_type'=>$post['reply_type']])->update(['update_time'=>time(),'status'=>0]);
        }
        return WechatReply::insert($post);
    }

    public static function getReply($id)
    {
        $detail =  WechatReply::findOrEmpty($id);
        $detail = $detail->isEmpty() ? [] : $detail->toArray();
        return $detail;
    }

    public static function edit($post){
        $post['update_time'] = time();
        if($post['reply_type'] !== WeChat::msg_type_text && $post['status']){
            WechatReply::where(['reply_type'=>$post['reply_type']])->update(['update_time'=>time(),'status'=>0]);
        }
        return WechatReply::where(['id'=>$post['id']])->update($post);
    }

    public static function del($id){
        return WechatReply::where(['id'=>$id])->update(['update_time'=>time(),'del'=>1]);
    }

    public static function changeFields($id,$field,$field_value,$reply_type){
        if( 'status' === $field && $field_value && $reply_type !== WeChat::msg_type_text){
            WechatReply::where(['reply_type'=>$reply_type])->update(['update_time'=>time(),'status'=>0]);
        }
        return WechatReply::where(['id'=>$id])->update(['update_time'=>time(),$field=>$field_value]);
    }
}