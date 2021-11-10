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

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;


/**
 * 阿里短信
 * Class AliSms
 */
class AliSms extends Server
{

    protected $config;
    protected $mobile;          //下发手机号
    protected $template_code;   //验证码
    protected $template_param;  //模板参数

    public function __construct($config = [])
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
     * @author 段誉(2021/5/18 17:39)
     * @return $this
     */
    public function setMobile($mobile = '')
    {
        $this->mobile = $mobile;
        return $this;
    }


    /**
     * Notes: 设置模板id
     * @param string $template_code
     * @author 段誉(2021/5/18 17:39)
     * @return $this
     */
    public function setTemplateId($template_code = '')
    {
        $this->template_code = $template_code;
        return $this;
    }


    /**
     * Notes: 设置模板参数
     * @param string $template_param
     * @author 段誉(2021/5/18 17:39)
     * @return $this
     */
    public function setTemplateParam($template_param = '')
    {
        $this->template_param = json_encode($template_param);
        return $this;
    }


    /**
     * Notes: 发送短信
     * @author 段誉(2021/5/18 17:40)
     * @return array|bool
     * @throws ClientException
     */
    public function send()
    {
        AlibabaCloud::accessKeyClient($this->config['app_key'], $this->config['secret_key'])
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpcRequest()
                ->product('Dysmsapi')
                ->host('dysmsapi.aliyuncs.com')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'PhoneNumbers'  => $this->mobile,            //发送手机号
                        'SignName'      => $this->config['sign'],    //短信签名
                        'TemplateCode'  => $this->template_code,     //短信模板CODE
                        'TemplateParam' => $this->template_param,    //自定义随机数
                    ],
                ])
                ->request();

            $res = $result->toArray();
            if (isset($res['Code']) && $res['Code'] == 'OK') {
                return $res;
            }
            $message = $res['Message'] ?? $res;
            throw new \Exception('短信错误：' . $message);

        } catch (ClientException $e) {
            $this->error = $e->getErrorMessage();
            return false;
        } catch (ServerException $e) {
            $this->error = $e->getErrorMessage();
            return false;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}


