<?php

namespace app\common\server\sms\engine;


class Server
{
    /**
     * 错误信息
     * @var
     */
    protected $error;

    /**
     * 返回错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

}
