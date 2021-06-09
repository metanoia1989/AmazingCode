<?php
declare (strict_types = 1);

namespace app\command;

use app\api\model\AuthModel;
use app\api\model\UserModel;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class MockLogin extends Command
{
    protected function configure()
    {
        $this->setName('mocklogin')
            ->setDescription("模拟用户登陆")
            ->addArgument("phone", Argument::OPTIONAL, "输入要登录的用户手机号", "13593871052");
    } 

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("模拟登陆 Start...");
        
        $phone = $input->getArgument('phone');
        $user = UserModel::where('phone', $phone)->find();
        if ($user) {
            $open_id = $user['open_id'];
            $uid = $user["uid"];
            $token = md5($open_id.time());
            AuthModel::CreateUserToken($token, $uid);
            $output->writeln("生成token成功：{$token} ！");
        } else {
            $output->writeln("此手机号{$phone}未注册！");
        } 
        $output->writeln("模拟登陆 End...");
    }
}