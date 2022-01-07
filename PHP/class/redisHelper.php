<?php
/**
* redis并发锁
*/
class redisHelper
{
    protected $redis;

    function __construct()
    {
        # todo 实例化Redis
        $this->redis = '';
    }

    /**
     * 测试函数
     *
     * @return void
     * @throws \Exception
     */
    public function test()
    {
        // 这里的 token 一般和用户挂钩
        $token = '123';
        if (!$this->lock($token, 10)) {
            throw new \Exception('系统繁忙，请稍后再试', -__LINE__);
        }
        # todo 做一些业务上的处理
        $this->unlock($token);
    }

    /**
     * redis 加并发锁
     * 
     * @param  string  $token
     * @param  integer $timeout
     * @return boolean
     */
    protected function lock($token, $timeout = 5)
    {
        $redis = $this->redis;
        if ($redis->setnx($token, time() + $timeout)) {
            $redis->expire($token, $timeout);
            return true;
        }
        return false;
    }

    /**
     * redis 解并发锁
     * 
     * @param  string $token
     */
    protected function unlock($token)
    {
        $redis = $this->redis;
        $redis->delete($token);
    }
}