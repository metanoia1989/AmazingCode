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

namespace app\common\server\sms;

use app\common\server\ConfigServer;

/**
 * 短信驱动
 * Class Driver
 * @package app\common\server\smsEngine
 */
class Driver
{
    protected $config;
    protected $smsEngine;
    protected $defaultEngine;
    protected $error;

    public function getError()
    {
        return $this->error;
    }


    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Notes: 初始化配置
     * @author 段誉(2021/6/22 0:43)
     * @return bool
     */
    public function initialize()
    {
        try {
            $defaultEngine = ConfigServer::get('sms_driver', 'default', '');
            if (empty($defaultEngine)) {
                throw new \Exception('请开启短信配置');
            }
            $this->defaultEngine = $defaultEngine;

            $classSpace = __NAMESPACE__ . '\\engine\\' . ucfirst($defaultEngine.'Sms');
            if (!class_exists($classSpace)) {
                throw new \Exception('对应短信配置类不存在');
            }

            $engineConfig = ConfigServer::get('sms_engine', $defaultEngine, []);
            if (empty($engineConfig)) {
                throw new \Exception('请在后台设置好('.$defaultEngine.')的配置');
            }
            $this->smsEngine = new $classSpace($engineConfig);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * Notes: 发送短信
     * @param $mobile
     * @param $data
     * @author 段誉(2021/6/22 0:42)
     * @return bool
     */
    public function send($mobile, $data)
    {
        try{
            $res = $this->smsEngine
                ->setMobile($mobile)
                ->setTemplateId($data['template_id'])
                ->setTemplateParam($data['param'])
                ->send();

            if (false === $res) {
                throw new \Exception($this->smsEngine->getError());
            }
            return $res;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }



}