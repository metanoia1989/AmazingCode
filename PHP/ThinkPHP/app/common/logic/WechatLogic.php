<?php
namespace app\api\logic;

use app\common\basics\Logic;
use app\common\model\wechat\Wechat;
use app\common\model\wechat\WechatReply;
use app\common\server\WeChatServer;
use EasyWeChat\Kernel\Exceptions\Exception;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Factory;

class WechatLogic extends Logic
{
    public static function index()
    {
        // Token验证 将微信转过来的数据原样返回
        if(isset($_GET['echostr'])) {
            echo $_GET['echostr'];
            exit;
        }

        // 获取公众号配置
        $config = WechatServer::getOaConfig();
        $app = Factory::officialAccount($config);

        $app->server->push(function ($message) {
            switch ($message['MsgType']) { // 消息类型
                case WeChat::msg_type_event: // 回复事件
                    switch ($message['Event']) {
                        case WeChat::msg_event_subscribe: // 关注事件
                            $reply_content = WechatReply::where(['reply_type' => WeChat::msg_event_subscribe, 'status' => 1, 'del' => 0])
                                ->value('content');
                            //关注回复空的话，找默认回复
                            if (empty($reply_content)) {
                                $reply_content = WechatReply::where(['reply_type' => WeChat::msg_type_default, 'status' => 1, 'del' => 0])
                                    ->value('content');
                            }
                            if ($reply_content) {
                                $text = new Text($reply_content);
                                return $text;
                            }
                            break;
                    }

                case WeChat::msg_type_text://消息类型
                    $reply_list = WechatReply::where(['reply_type' => WeChat::msg_type_text, 'status' => 1, 'del' => 0])
                        ->order('sort asc')
                        ->select();
                    $reply_content = '';
                    foreach ($reply_list as $reply) {
                        switch ($reply['matching_type']) {
                            case 1://全匹配
                                $reply['keyword'] === $message['Content'] && $reply_content = $reply['content'];
                                break;
                            case 2://模糊匹配
                                stripos($message['Content'], $reply['keyword']) !== false && $reply_content = $reply['content'];
                                break;
                        }
                        if($reply_content) {
                            break; // 得到回复文本，中止循环
                        }
                    }
                    //消息回复为空的话，找默认回复
                    if (empty($reply_content)) {
                        $reply_content = WechatReply::where(['reply_type' => WeChat::msg_type_default, 'status' => 1, 'del' => 0])
                            ->value('content');
                    }
                    if ($reply_content) {
                        $text = new Text($reply_content);
                        return $text;
                    }
                    break;
            }
        });
        $response = $app->server->serve();
        $response->send();
    }

    /**
     * 获取微信配置
     * @param $url
     * @return array|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function jsConfig($url)
    {
        $config = WeChatServer::getOaConfig();
        $app = Factory::officialAccount($config);
        $url = urldecode($url);
        $app->jssdk->setUrl($url);
        $apis = ['onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ', 'onMenuShareWeibo', 'onMenuShareQZone', 'openLocation', 'getLocation', 'chooseWXPay', 'updateAppMessageShareData', 'updateTimelineShareData', 'openAddress'];
        try {
            $data = $app->jssdk->getConfigArray($apis, $debug = false, $beta = false);
            return data_success('', $data);
        } catch (Exception $e) {
            return data_error('公众号配置出错' . $e->getMessage());
        }
    }
}