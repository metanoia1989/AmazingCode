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

namespace app\common\server\sms\engine;


use TencentCloud\Sms\V20190711\SmsClient;
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;


/**
 * 腾讯云短信
 * Class TcSms
 */
class TcSms extends Server
{
    protected $config;
    protected $mobile;          //下发手机号码
    protected $template_id;     //模板id
    protected $template_param ; //模板参数

    public function __construct($config)
    {
        if (empty($config)) {
            $this->error = '请联系管理员配置参数';
            return false;
        }
        $this->config = $config;
    }


    /**
     * Notes: 设置手机号
     * @param string $mobile
     * @author 段誉(2021/5/18 17:41)
     * @return $this
     */
    public function setMobile($mobile = '')
    {
        $this->mobile = $mobile;
        return $this;
    }


    /**
     * Notes: 设置模板id
     * @param null $template_id
     * @author 段誉(2021/5/18 17:41)
     * @return $this
     */
    public function setTemplateId($template_id = null)
    {
        $this->template_id = $template_id;
        return $this;
    }


    /**
     * Notes: 设置模板参数
     * @param null $template_param
     * @author 段誉(2021/5/18 17:42)
     * @return $this
     */
    public function setTemplateParam($template_param = null)
    {
        $this->template_param = $template_param;
        return $this;
    }



    /**
     * @notes 发送 (此处$config[app_key]为系统统一命名,原腾讯云命名为secret_id)
     * @return false|mixed
     * @author 段誉
     * @date 2021/8/4 10:40
     */
    public function send()
    {
        try {
            $cred = new Credential($this->config['app_key'], $this->config['secret_key']);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("sms.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);

            $client = new SmsClient($cred, "", $clientProfile);
            $req = new SendSmsRequest();

            $params = [
                'PhoneNumberSet'    => ['+86'.$this->mobile],
                'TemplateID'        => $this->template_id,
                'Sign'              => $this->config['sign'],
                'TemplateParamSet'  => $this->template_param,
                'SmsSdkAppid'       => $this->config['app_id'],
            ];
            $req->fromJsonString(json_encode($params));

            $resp = json_decode($client->SendSms($req)->toJsonString(), true);

            if (isset($resp['SendStatusSet']) && $resp['SendStatusSet'][0]['Code'] == 'Ok') {
                return $resp;
            } else {
                $message = $res['SendStatusSet'][0]['Message'] ?? json_encode($resp);
                throw new \Exception('短信错误：' . $message);
            }

        } catch (TencentCloudSDKException $e) {
            $this->error = $e->getMessage();
            return false;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}