<?php
// +----------------------------------------------------------------------
// | LikeShop100%开源免费商用电商系统
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | 商业版本务必购买商业授权，以免引起法律纠纷
// | 禁止对系统程序代码以任何目的，任何形式的再发布
// | Gitee下载：https://gitee.com/likemarket/likeshopv2
// | 访问官网：https://www.likemarket.net
// | 访问社区：https://home.likemarket.net
// | 访问手册：http://doc.likemarket.net
// | 微信公众号：好象科技
// | 好象科技开发团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------

// | Author: LikeShopTeam
// +----------------------------------------------------------------------

namespace app\api\http\middleware;

use think\exception\ValidateException;
use app\api\validate\TokenValidate;
use app\common\model\user\User;
use app\common\server\JsonServer;

class Login
{
    /**
     * 登录验证
     * @param $request
     * @param \Closure $next
     * @return mixed|\think\response\Redirect
     */
    public function handle($request, \Closure $next)
    {
        //允许跨域调用
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Authorization, Sec-Fetch-Mode, DNT, X-Mx-ReqToken, Keep-Alive, User-Agent, If-Match, If-None-Match, If-Unmodified-Since, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Accept-Language, Origin, Accept-Encoding,Access-Token,token");
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Max-Age: 1728000');
        header('Access-Control-Allow-Credentials:true');

        if (strtoupper($request->method()) == "OPTIONS") {
            return response();
        }

        $token = $request->header('token');

        // 免登录
        if ($this->isNotNeedLogin($request) && empty($token)) {
            return $next($request);
        }


        // 根据token读取缓存
        $userInfo = $token ? cache($token) : [];
        if($userInfo) {
            // 设置用户信息
            $request->user_info = $userInfo;
            return $next($request);
        }

        //token验证，并生成缓存
        $validateError = '';
        try{
            validate(TokenValidate::class)->check(['token' => $token]);
            $userInfo = User::alias('u')
                ->join('session s', 'u.id=s.user_id')
                ->where(['s.token' => $token])
                ->field('u.*,s.token,s.client')
                ->find();
            $userInfo = $userInfo ? $userInfo->toArray() : [];
            // 设置缓存
            cache($token, $userInfo);
            // 设置用户信息
            $request->user_info = $userInfo;
            return $next($request);
        }catch(ValidateException $e){
            $validateError = $e->getError();
        }

        //无需要登录，带token的情况
        if ($this->isNotNeedLogin($request) && $token) {
            return $next($request);
        }

        //登录失败
        $result = array(
            'code' => -1,
            'show' => 1,
            'msg'  => $validateError,
            'data' => []
        );
        return json($result);

    }


    /**
     * 是否免登录验证
     * @param $request
     * @return bool
     */
    private function isNotNeedLogin($request)
    {
        // 提取当前请求控制器名称
        $baseUrl = $request->baseUrl(); //   /api/goods/test
        $apperTwo = strpos($baseUrl, '/', 1);
        $apperThird  = strpos($baseUrl, '/', $apperTwo + 1);
        $len = $apperThird - $apperTwo - 1;
        $controllerName = substr($baseUrl, $apperTwo + 1, $len);

        // 控制名称处理(兼容下划线的情况 例：goods_columns 处理成 GoodsColumns)
        $controllerNameArr = explode('_', $controllerName);
        $controllerNameArr = array_map('ucfirst', $controllerNameArr);
        $controllerName = implode($controllerNameArr);

        // 实例化当前请求的控制器
        $controllerObj = invoke('\\app\\api\\controller\\' . $controllerName);
        $data = $controllerObj->like_not_need_login;
        if (empty($data)) {
            return false;
        }

        // 提取操作名称
        $action = strtolower(substr($baseUrl, $apperThird + 1));

        $data = array_map('strtolower', $data);

        if (!in_array($action, $data)) {
            return false;
        }
        return true;
    }
}
