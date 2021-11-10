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

namespace app\api\logic;

use app\admin\logic\user\LevelLogic;
use app\api\server\UserServer;
use app\common\basics\Logic;
use app\common\server\WeChatServer;
use app\common\server\ConfigServer;
use app\common\model\Client_;
use app\common\model\user\User;
use app\common\model\user\UserAuth;
use app\common\model\user\UserLevel;
use app\common\model\Session as SessionModel;
use EasyWeChat\Factory;
use think\facade\Config;
use think\facade\Cache;
use think\facade\Db;
use app\api\cache\TokenCache;
use app\common\logic\AccountLogLogic;
use app\common\model\AccountLog;
use app\common\server\UrlServer;
use think\Exception;
use Requests;


class LoginLogic extends Logic
{
    /**
     * Notes: 旧用户登录
     * @param $post
     * @author 段誉(2021/4/19 16:57)
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public static function silentLogin($post)
    {
        try {
            //通过code获取微信 openid
            $response = self::getWechatResByCode($post);
            //通过获取到的openID或unionid获取当前 系统 用户id
            $user_id = self::getUserByWechatResponse($response);
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }

        if (empty($user_id)) {
            //系统中没有用户-调用authlogin接口生成新用户
            return [];
        } else {
            $user_info = UserServer::updateUser($response, Client_::mnp, $user_id);
        }

        //验证用户信息
        $check_res = self::checkUserInfo($user_info);
        if (true !== $check_res) {
            self::$error = $check_res;
            return  false;
        }

        //创建会话
        $user_info['token'] = self::createSession($user_info['id'], Client_::mnp);

        unset($user_info['id'], $user_info['disable']);

        return $user_info->toArray();
    }

    /**
     * Notes: 新用户登录
     * @param $post
     * @author 段誉(2021/4/19 16:57)
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public static function authLogin($post)
    {
        try {
            //通过code获取微信 openid
            $response = self::getWechatResByCode($post);
            $response['headimgurl'] = $post['headimgurl'] ?? '';
            $response['nickname'] = $post['nickname'] ?? '';
            //通过获取到的openID或unionid获取当前 系统 用户id
            $user_id = self::getUserByWechatResponse($response);
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }

        if (empty($user_id)) {
            $user_info = UserServer::createUser($response, Client_::mnp);
        } else {
            $user_info = UserServer::updateUser($response, Client_::mnp, $user_id);
        }

        //验证用户信息
        $check_res = self::checkUserInfo($user_info);
        if (true !== $check_res) {
            self::$error = $check_res;
            return false;
        }

        //创建会话
        $user_info['token'] = self::createSession($user_info['id'], Client_::mnp);

        unset($user_info['id'], $user_info['disable']);

        return $user_info->toArray();
    }

    /**
     * Notes: 根据code 获取微信信息(openid, unionid)
     * @param $post
     * @author 段誉(2021/4/19 16:52)
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws Exception
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public static function getWechatResByCode($post)
    {
        $config = WeChatServer::getMnpConfig();
        $app = Factory::miniProgram($config);
        $response = $app->auth->session($post['code']);
        if (!isset($response['openid']) || empty($response['openid'])) {
            throw new \think\Exception('获取openID失败');
        }

        return $response;
    }

    /**
     * Notes: 根据微信返回信息查询当前用户id
     * @param $response
     * @author 段誉(2021/4/19 16:52)
     * @return mixed
     */
    public static function getUserByWechatResponse($response)
    {
        $user_id = UserAuth::alias('au')
            ->join('user u', 'au.user_id=u.id')
            ->where(['u.del' => 0])
            ->where(function ($query) use ($response) {
                $query->whereOr(['au.openid' => $response['openid']]);
                if(isset($response['unionid']) && !empty($response['unionid'])){
                    $query->whereOr(['au.unionid' => $response['unionid']]);
                }
            })
            ->value('user_id');

        return $user_id;
    }


    /**
     * Notes: 检查用户信息
     * @param $user_info
     * @author 段誉(2021/4/19 16:54)
     * @return bool|string
     */
    public static function checkUserInfo($user_info)
    {
        if (empty($user_info)) {
            return '登录失败:user';
        }

        if ($user_info['disable']) {
            return '该用户被禁用';
        }

        return true;
    }

    /**
     * 创建会话
     * @param $user_id
     * @param $client
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public static function createSession($user_id, $client)
    {
        //清除之前缓存
        $token = SessionModel::where(['user_id' => $user_id, 'client' => $client])
            ->value('token');
        if($token) {
            Cache::delete($token);
        }

        $result = SessionModel::where(['user_id' => $user_id, 'client' => $client])
            ->findOrEmpty();

        $time = time();
        $expire_time = $time + Config::get('project.token_expire_time');
        // 新token
        $token = md5($user_id . $client . $time);
        $data = [
            'user_id' => $user_id,
            'token' => $token,
            'client' => $client,
            'update_time' => $time,
            'expire_time' => $expire_time,
        ];

        if ($result->isEmpty()) {
            SessionModel::create($data);
        } else {
            SessionModel::where(['user_id' => $user_id, 'client' => $client])
                ->update($data);
        }

        //更新登录信息
        $login_ip = $ip = request()->ip();
        User::where(['id' => $user_id])
            ->update(['login_time' => $time, 'login_ip' => $login_ip]);

        // 获取最新的用户信息
        $user_info = User::alias('u')
            ->join('session s', 'u.id=s.user_id')
            ->where(['s.token' => $token])
            ->field('u.*,s.token,s.client')
            ->find();
        $user_info = $user_info ? $user_info->toArray() : [];

        //创建新的缓存
        $ttl = 0 + Config::get('project.token_expire_time');
        Cache::set($token, $user_info, $ttl);

        return $token;
    }

    public static function register($post)
    {
        Db::startTrans();
        try{
            $time = time();
            $salt = substr(md5($time . $post['mobile']), 0, 4);//随机4位密码盐
            $password = create_password($post['password'], $salt);//生成密码
            $user_data = [
                'avatar'        => ConfigServer::get('website', 'user_image'),
                'sn'            => create_user_sn(),
                'mobile'        => $post['mobile'],
                'salt'          => $salt,
                'password'      => $password,
                'create_time'   => $time,
                'distribution_code' => generate_invite_code(),//分销邀请码
                'is_distribution' => DistributionLogic::isDistributionMember(),//是否为分销会员
                'client' => $post['client']
            ];

            $user_data['nickname'] = '用户'.$user_data['sn'];

            $user = User::create($user_data);

            $token = self::createSession($user->id, $post['client']);

            //生成会员分销扩展表
            DistributionLogic::createUserDistribution($user->id);

            //注册赠送
            self::registerAward($user->id);

            Db::commit();
            return ['token' => $token];
        }catch(\Exception $e){
            Db::rollback();
            self::$error = $e->getMessage();
            return false;
        }
    }

    public static function registerAward($user_id){
        $register_award_integral_status = ConfigServer::get('marketing','register_award_integral_status',0);
        $register_award_coupon_status = ConfigServer::get('marketing','register_award_coupon_status',0);
        //赠送积分
        if($register_award_integral_status){
            $register_award_integral = ConfigServer::get('marketing','register_award_integral',0);
            //赠送的积分
            if($register_award_integral > 0){
                $user = User::findOrEmpty($user_id);
                $user->user_integral += $register_award_integral;
                $user->save();
                AccountLogLogic::AccountRecord($user_id,$register_award_integral,1,AccountLog::register_add_integral,'');
            }
        }
        //注册账号，首次进入首页时领取优惠券
        $register_award_coupon = ConfigServer::get('marketing','register_award_coupon','');
        if($register_award_coupon_status && $register_award_coupon){
            Cache::tag('register_coupon')->set('register_coupon_'.$user_id,$register_award_coupon);
        }
        // 赠送成长值
        $register_growth = ConfigServer::get('register', 'growth', 0);
        if($register_growth > 0) {
            $user = User::findOrEmpty($user_id);
            $user->user_growth += $register_growth;
            $user->save();
            AccountLogLogic::AccountRecord($user_id,$register_growth,1,AccountLog::register_give_growth,'');
            // 更新用户会员等级
            LevelLogic::updateUserLevel([$user]);
        }
    }

    /**
     * 手机号密码登录
     */
    public static function mpLogin($post)
    {
        $user = User::field(['id', 'nickname', 'avatar', 'level', 'disable', 'distribution_code'])
            ->where(['mobile' => $post['mobile'], 'del' => 0])
            ->findOrEmpty()->toArray();
        $user['token'] = self::createSession($user['id'], $post['client']);
        if (empty($user['avatar'])) {
            $user['avatar'] = UrlServer::getFileUrl(ConfigServer::get('website', 'user_image'));
        } else {
            $user['avatar'] = UrlServer::getFileUrl($user['avatar']);
        }
        return $user;
    }

    /**
     * 获取code的url
     * @param $url
     * @return string
     */
    public static function codeUrl($url)
    {
        $config = WeChatServer::getOaConfig();
        $app = Factory::officialAccount($config);
        $response = $app
            ->oauth
            ->scopes(['snsapi_userinfo'])
            ->redirect($url)
            ->getTargetUrl();
        return $response;
    }

    /***
     * Desc: 微信公众号登录
     * @param $post
     * @return array|string
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public static function oaLogin($post)
    {
        //微信调用
        try {
            $config = WeChatServer::getOaConfig();
            $app = Factory::officialAccount($config);
            $response = $app
                ->oauth
                ->scopes(['snsapi_userinfo'])
                ->getAccessToken($post['code']);
            if (!isset($response['openid']) || empty($response['openid'])) {
                throw new Exception();
            }
            $user = $app->oauth->user($response);
            $user = $user->getOriginal();
            } catch (Exception $e) {
                return $e->getMessage();
            }
        //添加或更新用户
        $user_id = UserAuth::alias('au')
            ->join('user u', 'au.user_id=u.id')
            ->where(['u.del' => 0])
            ->where(function ($query) use ($user) {
                $query->whereOr(['au.openid' => $user['openid']]);
                if(isset($user['unionid']) && !empty($user['unionid'])){
                    $query->whereOr(['au.unionid' => $user['unionid']]);
                }
            })
            ->value('user_id');
        if (empty($user_id)) {
            $user_info = UserServer::createUser($user, Client_::oa);
        } else {
            $user_info = UserServer::updateUser($user, Client_::oa, $user_id);
        }

        if (empty($user_info)) {
            return '登录失败:user';
        }

        if ($user_info['disable']) {
            return '该用户被禁用';
        }

        //创建会话
        $user_info['token'] = self::createSession($user_info['id'], Client_::oa);


        unset($user_info['id']);
        unset($user_info['disable']);
        return $user_info->toArray();
//        return $user_info;

    }

    /***
     * app微信登录
     * @param $post
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public static function uinAppLogin($post)
    {
        //微信调用
        try {
            if (empty($post['openid']) || empty($post['access_token']) || empty($post['client'])){
                throw new Exception('参数缺失');
            }

            //sdk不支持app登录，直接调用微信接口
            $requests = Requests::get('https://api.weixin.qq.com/sns/userinfo?openid=' . 'openid=' . $post['openid'] . '&access_token=' . $post['access_token']);
            $user = json_decode($requests->body, true);
        } catch (Exception $e) {
            return $e->getMessage();
        }
//        dump($user);
        //添加或更新用户
        $user_id = UserAuth::alias('au')->join('user u', 'au.user_id=u.id')
            ->where(['u.del' => 0])
            ->where(function ($query) use ($user) {
                $query->whereOr(['au.openid' => $user['openid']])
                    ->whereOr(['au.unionid' => $user['unionid']]);
            })
            ->value('user_id');

        if (empty($user_id)) {
            $user_info = UserServer::createUser($user, $post['client']);
        } else {
            $user_info = UserServer::updateUser($user, $post['client'], $user_id);
        }

        if (empty($user_info)) {
            return '登录失败:user';
        }

        if ($user_info['disable']) {
            return '该用户被禁用';
        }

        //创建会话
        $user_info['token'] = self::createSession($user_info['id'], $post['client']);

        unset($user_info['id']);
        unset($user_info['disable']);
        return $user_info;
    }

    //手机号密码登录 
    public static function login($post)
    {
        $user_info = User::field(['id', 'nickname', 'avatar', 'level', 'disable', 'distribution_code'])
            ->where(['account|mobile' => $post['mobile'], 'del' => 0])
            ->find()->toArray();
        $user_info['token'] = self::createSession($user_info['id'], $post['client']);
        if (empty($user_info['avatar'])) {
            $user_info['avatar'] = UrlServer::getFileUrl(ConfigServer::get('website', 'user_image'));
        } else {
            $user_info['avatar'] = UrlServer::getFileUrl($user_info['avatar']);
        }
        return $user_info;
    }

    //退出登录
    public static function logout($user_id, $client)
    {
        return self::expirationSession($user_id, $client);
    }

    /**
     * 设置会话过期
     * @param $user_id
     * @param $client
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function expirationSession($user_id, $client)
    {
        $time = time();
        $token = Db::name('session')
            ->where(['user_id' => $user_id, 'client' => $client])
            ->value('token');
        $token_cache = new TokenCache($token);

        $token_cache->del();
        return Db::name('session')
            ->where(['user_id' => $user_id, 'client' => $client])
            ->update(['update_time' => $time, 'expire_time' => $time]);
    }
}
