<?php
// +----------------------------------------------------------------------
// | LikeShop100%开源免费商用电商系统
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | 商业版本务必购买商业授权，以免引起法律纠纷
// | 禁止对系统程序代码以任何目的，任何形式的再发布
// | Gitee下载：https://gitee.com/likeshop_gitee/likeshop
// | 访问官网：https://www.likemarket.net
// | 访问社区：https://home.likemarket.net
// | 访问手册：http://doc.likemarket.net
// | 微信公众号：好象科技
// | 好象科技开发团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------

// | Author: LikeShopTeam
// +----------------------------------------------------------------------

namespace app\common\server;


use app\common\enum\NoticeEnum;
use app\common\enum\SmsEnum;
use app\common\logic\MessageNoticeLogic;
use app\common\model\NoticeSetting;
use app\common\model\SmsLog;
use app\common\server\sms\Driver;

class SmsMessageServer
{

    protected $sms_log;
    protected $notice;

    public function send($params)
    {
        try{
            //场景对应短信模板信息
            $scene_config = NoticeSetting::where(['scene' => $params['scene']])->findOrEmpty();

            //增加短信记录
            $content = $this->getContentInfo($scene_config, $params);
            $code = $this->getCodeInfo($params['scene'], $scene_config, $params['params']);
            $this->sms_log = $this->addSmsLog($params, $content, $code);

            //增加通知记录
            $this->notice = MessageNoticeLogic::addNoticeLog($params, $scene_config['sms_notice'], NoticeEnum::SMS_NOTICE, $content);

            //发送短信
            $SmsDriver = new Driver();
            $res = $SmsDriver->send($params['mobile'], [
                'template_id' => $scene_config['sms_notice']['template_code'],
                'param' => $this->setSmsParams($scene_config, $params),
            ]);
            if (false === $res) {
                $this->updateSmsLog($this->sms_log['id'], SmsEnum::SEND_FAIL, $SmsDriver->getError());
                throw new \Exception($SmsDriver->getError());
            }
            $this->updateSmsLog($this->sms_log['id'], SmsEnum::SEND_SUCCESS, $res);
            return true;

        } catch (\Exception $e) {
            if (!empty($this->sms_log['id'])) {
                $this->updateSmsLog($this->sms_log['id'], SmsEnum::SEND_FAIL, $e->getMessage());
            }
            if (!empty($this->notice['id'])) {
                MessageNoticeLogic::updateNotice($this->notice['id'],  $e->getMessage());
            }
            return $e->getMessage();
        }
    }


    public function addSmsLog($params, $content, $code)
    {
        return SmsLog::create([
            'message_key'   => $params['scene'],
            'mobile'        => $params['mobile'],
            'content'       => $content,
            'code'          => $code,
            'send_status'   => SmsEnum::SEND_ING,
            'send_time'     => time(),
        ]);
    }

    public function updateSmsLog($id, $status, $result)
    {
        SmsLog::update([
            'send_status' => $status,
            'results' => json_encode($result, JSON_UNESCAPED_UNICODE)
        ],['id' => $id]);
    }

    //发送内容(替换设置好的模板变量)
    public function getContentInfo($scene_config, $params)
    {
        $content = $scene_config['sms_notice']['content'];
        foreach ($params['params'] as $item => $val) {
            $search_replace = '{' . $item . '}';
            $content = str_replace($search_replace, $val, $content);
        }
        return $content;
    }


    //短信验证码
    public function getCodeInfo($scene, $scene_config, $sms_params)
    {
        $code = '';
        if (in_array($scene, NoticeEnum::NOTICE_NEED_CODE)) {
            $code = array_intersect_key($sms_params, $scene_config['variable']);
            if ($code) {
                 return array_shift($code);
            }
        }
        return $code;
    }


    /**
     * @notes 腾讯云参数设置
     * @param $scene_config
     * @param $params
     * @return array|mixed
     * @author 段誉
     * @date 2021/8/4 14:09
     */
    public function setSmsParams($scene_config, $params)
    {
        $sms_driver = ConfigServer::get('sms_driver', 'default', '');
        if ($sms_driver != 'tc') {
            return $params['params'];
        }

        //腾讯云特殊处理
        $arr = [];
        $content = $scene_config['sms_notice']['content'];
        foreach ($params['params'] as $item => $val) {
            $search = '{' . $item . '}';
            if(strpos($content, $search) !== false
                && !in_array($item, $arr)
            ) {
                //arr => 获的数组[nickname, order_sn] //顺序可能是乱的
                $arr[] = $item;
            }
        }

        //arr2 => 获得数组[nickname, order_sn] //调整好顺序的变量名数组
        $arr2 = [];
        if (!empty($arr)) {
            foreach ($arr as $v) {
                $key = strpos($content, $v);
                $arr2[$key] = $v;
            }
        }

        //格式化 arr2 => 以小到大的排序的数组
        ksort($arr2);
        $arr3 = array_values($arr2);

        //arr4 => 获取到变量数组的对应的值 [mofung, 123456789]
        $arr4 = [];
        foreach ($arr3 as $v2) {
            if(isset($params['params'][$v2])) {
                $arr4[] = $params['params'][$v2];
            }
        }
        return $arr4;
    }

}