<?php
class Oss
{

    /**
     * 获取STS临时访问凭证访问
     *
     * @return void
     */
    public function getSTSToken()        
    {
        $return_data = [
            'err' => 1,
            'msg' => 'fail'
        ];

        //构建一个阿里云客户端，用于发起请求。
        $accessKeyId = config('app.aliyun_oss.AccessKeyID');
        $accessKeySecret  = config('app.aliyun_oss.AccessKeySecret');
        $region = config('app.aliyun_oss.region');
        $orn = config('app.aliyun_oss.STS_ORN');

        //构建阿里云客户端时需要设置AccessKey ID和AccessKey Secret。
        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
                                ->regionId('cn-guangzhou')
                                ->asDefaultClient();

        //设置参数，发起请求。关于参数含义和设置方法，请参见《API参考》。
        try {
            $result = AlibabaCloud::rpc()
                ->product('Sts')
                ->scheme('https') // https | http
                ->version('2015-04-01')
                ->action('AssumeRole')
                ->method('POST')
                ->host('sts.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => $region,
                        'RoleArn' => $orn,
                        'RoleSessionName' => "ChatFileUpload",
                    ],
                ])
                ->request();

            $return_data['err'] = 0;
            $return_data['msg'] = 'success';
            $return_data['data'] = $result->toArray();
        } catch (ClientException $e) {
            $return_data['msg'] = $e->getErrorMessage();
        } catch (ServerException $e) {
            $return_data['msg'] = $e->getErrorMessage();
        }

        return json($return_data);
    }
}