<?php
namespace app\common\model\wechat;

use think\Model;

class Wechat extends Model
{
    /**
     * 消息类型常量
     */
    const msg_type_text       = 'text';         //回复文本信息
    const msg_type_image      = 'image';        //回复图片信息
    const msg_type_voice      = 'voice';        //回复语音信息
    const msg_type_video      = 'video';        //回复视频消息
    const msg_type_music      = 'music';        //回复音乐消息
    const msg_type_news       = 'news';         //回复图文消息
    const msg_type_location   = 'location';     //回复地理位置消息
    const msg_type_link       = 'link';         //回复链接信息
    const msg_type_event      = 'event';        //回复事件
    const msg_type_default    = 'default';      //默认回复

    /**
     * 事件类型常量
     */
    const msg_event_subscribe   = 'subscribe';      //关注事件-首次关注
    const msg_event_unsubscribe = 'unsubscribe';    //取消关注事件
    const msg_event_scan        = 'SCAN';           //关注事件-已关注
    const msg_event_location    = 'LOCATION';       //上报地理位置事件
    const msg_event_click       = 'CLICK';          //点击菜单拉取消息时的事件推送
    const msg_event_view        = 'VIEW';           //点击菜单跳转链接时的事件

    public static function getCustomReply($form = true){
        $desc = [
            self::msg_event_subscribe   => '关注回复',
            self::msg_type_text         => '关键词回复',
            self::msg_type_default      => '默认回复',
        ];
        if( true === $form){
            return $desc;
        }
        return $desc[$form] ??[];
    }
}