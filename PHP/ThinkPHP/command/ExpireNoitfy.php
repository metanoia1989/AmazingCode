<?php
declare (strict_types = 1);

namespace app\command;

use app\models\SendSms;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use chuanglan\ChuanglanSmsApi;
use think\facade\Log;

/**
 * 相册过期短信提醒
 * 过期前7天开始提醒，分别提醒2次，倒数第7天第一次，最后一天第二次。
 * 短信内容：尊敬的孔雀云相册用户，您的高清原片即将在X天后失效，请尽快下载或开通永久保功能。
 */
class ExpireNoitfy extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('expire:notify')
            ->setDescription('过期相册短信提醒，只处理有文件的相册');        
    }

    protected function execute(Input $input, Output $output)
    {
    	// 指令输出
    	$output->writeln('expire:notify');

        $currTime = time();
        // 过期前七天发送一次
        $this->processWillExpire(7, $currTime);  
        // 过期前一天发送一次
        $this->processWillExpire(1, $currTime);  
    }

    protected function processWillExpire($day, $currTime)
    {
        // 一对多不能联表查询，会一的那个表记录会重复   
        $field = 'fd.customer_phone, fd.id,'.
            'CASE WHEN s.send_status = 1 THEN 1 ELSE 0 END AS `has_send`,'.
            'CASE WHEN mf.id THEN 1 ELSE 0 END AS `has_file`';
        $fileTable = "SELECT max(id) AS id, folder_id FROM c_mch_file WHERE is_del = 0 GROUP BY folder_id";
        $sendSmsTable = "SELECT DISTINCT phone,send_status,subject_id AS folder_id FROM `c_send_sms` 
            WHERE model='c_folder' AND send_status = 1 AND `time_point` = '-{$day} day' GROUP BY subject_id, phone";

        $willExpire = Db::name('folder')->alias('fd')
            ->field($field)    
            ->join("($sendSmsTable) s", 'fd.id=s.folder_id AND s.phone=fd.customer_phone', 'left')
            ->join("($fileTable) AS mf", 'fd.id=mf.folder_id', 'left')
            ->where('fd.is_del', 0)
            ->where('fd.expire_time', '<>', 0)
            ->whereBetween('fd.expire_time', [
                strtotime("-{$day} day", $currTime), 
                strtotime("-".($day - 1)." day", $currTime)
            ])
            ->where('fd.customer_phone', '<>', '')
            ->where('fd.customer_phone', '13593871052')
            ->group('fd.id')
            ->select();
    
        foreach ($willExpire as $item) {
            if ($item["has_file"] != 1 || $item["has_send"]) {
                continue;
            }
            $phone = $item["customer_phone"];
            $ChuanglanSmsApi = new ChuanglanSmsApi();
            $msg = "【孔雀巢】尊敬的孔雀云相册用户，您的高清原片即将在{$day}天后失效，请尽快下载或开通永久保存功能。";
            $res = $ChuanglanSmsApi->sendSMS($phone, $msg);
            $res = json_decode($res,true);
            if($res['code'] == 0){//成功
                SendSms::create([
                    "phone" => $phone,
                    "title" => $msg,
                    "subject_id" => $item["id"],
                    "model" => "c_folder",
                    "time_point" => "-{$day} day",
                    "send_status" => 1,
                ]);
            } else {
                Log::error("云相册过期的短信提醒发送失败：{$phone} ".json_encode($res));
            }
        }
    }
}
