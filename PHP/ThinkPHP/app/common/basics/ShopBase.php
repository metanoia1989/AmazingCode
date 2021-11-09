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
// | Author: LikeShopTeam
// +----------------------------------------------------------------------

namespace app\common\basics;


use app\common\server\ConfigServer;
use app\common\server\UrlServer;
use app\common\utils\Time;
use app\shop\server\AuthServer;
use think\App;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\View;

/**
 * 后台基类
 * Class ShopBase
 * @Author FZR
 * @package app\common\basics
 */
abstract class ShopBase
{
    /**
     * Request实例
     */
    protected $request;

    /**
     * 应用实例
     */
    protected $app;

    /**
     * 商家信息
     * @var
     */
    protected $shop;

    /**
     * 商家id
     * @var
     */
    protected $shop_id;

    /**
     * 商家名称
     * @var
     */
    protected $shop_name;

    /**
     * 管理员id
     * @var
     */
    protected $admin_id;

    /**
     * 管理员名称
     * @var
     */
    protected $admin_name;

    /**
     * 逻辑
     * @var
     */
    protected $logic;

    /**
     * 验证器
     * @var
     */
    protected $validate;

    /**
     * 不需要登录的方法
     * @var array
     */
    public $like_not_need_login = [];

    /**
     * js数据
     * @var array
     */
    protected $js_data = [];

    /**
     * 分页
     * @var int
     */
    public $page_no = 1;
    public $page_size = 15;

    /**
     * 模板颜色
     * @var string
     */
    public $view_theme_color = '';



    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        //默认设置参数
        $this->initConfig();

        //验证登录
        $this->checkLogin();

        //验证权限
        $this->checkAuth();

        //默认页面参数
        $this->setViewValue();
        return true;
    }


    /**
     * Notes: 基础配置参数
     * @author 段誉(2021/4/9 14:18)
     */
    protected function initConfig()
    {
        $this->shop = session('shop_info');
        $this->shop_id = session('shop_info.shop_id');
        $this->shop_name = session('shop_info.shop_name');
        $this->admin_id = session('shop_info.id');
        $this->admin_name = session('shop_info.name');
        //分页参数
        $page_no = (int)$this->request->get('page_no');
        $this->page_no = $page_no && is_numeric($page_no) ? $page_no : $this->page_no;
        $page_size = (int)$this->request->get('page_size');
        $this->page_size = $page_size && is_numeric($page_size) ? $page_size : $this->page_size;
        $this->page_size = min($this->page_size, 100);
    }


    /**
     * 设置视图全局变量
     */
    private function setViewValue()
    {
        $app = Config::get('project');
        View::assign([
            'view_env_name'     => $app['env_name'],
            'view_admin_name'   => $app['admin_name'],
            'view_theme_color'  => $app['theme_color'],
            'view_theme_button' => $app['theme_button'],
            'front_version'     => $app['front_version'],
            'version'           => $app['version'],
            'dateTime'          => Time::getTime(),
            'storageUrl'        => UrlServer::getFileUrl('/'),
            'company_name'      => ConfigServer::get('copyright', 'company_name')
        ]);
        $this->assignJs('image_upload_url', '');
    }


    /**
     * Notes: 检查登录
     * @author 段誉(2021/4/9 14:05)
     * @return bool
     */
    protected function checkLogin()
    {
        //已登录的访问登录页
        if ($this->shop && !$this->isNotNeedLogin()) {
            return true;
        }

        //已登录的访问非登录页
        if ($this->shop && $this->isNotNeedLogin()) {
            $this->redirect(url('index/index'));
        }

        //未登录的访问非登录页
        if (!$this->shop && $this->isNotNeedLogin()) {
            return true;
        }

        //未登录访问登录页
        $this->redirect(url('login/login'));
    }


    /**
     * Notes: 验证登录角色权限
     * @author 段誉(2021/4/13 11:34)
     * @return bool
     */
    protected function checkAuth()
    {
        //未登录的无需权限控制
        if (empty(session('shop_info'))) {
            return true;
        }

        //如果id为1，视为系统超级管理，无需权限控制
        if (session('shop_info.id') == 1) {
            return true;
        }

        //权限控制判断
        $controller_action = request()->controller() . '/' . request()->action();// 当前访问
        $controller_action = strtolower($controller_action);

        //没有的权限
        $none_auth = AuthServer::getRoleNoneAuthUris(session('shop_info.role_id'));
        if (empty($none_auth) || !in_array($controller_action, $none_auth)) {
            //通过权限控制
            return true;
        }
        //TODO 权限不足时跳转
        $this->redirect(url('dispatch/dispatch_error',['msg' => '权限不足，无法访问']));
        return false;
    }


    /**
     * Notes: js
     * @param $name
     * @param $value
     * @author 段誉(2021/4/9 14:23)
     */
    protected function assignJs($name, $value)
    {
        $this->js_data[$name] = $value;
        $js_code = "<script>";
        foreach ($this->js_data as $name => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif (!is_integer($value)) {
                $value = '"' . $value . '"';
            }
            $js_code .= $name . '=' . $value . ';';
        }
        $js_code .= "</script>";
        View::assign('js_code', $js_code);
    }


    /**
     * Notes: 是否无需登录
     * @author 段誉(2021/4/9 14:03)
     * @return bool
     */
    private function isNotNeedLogin()
    {
        if (empty($this->like_not_need_login)) {
            return false;
        }
        $action = strtolower(request()->action());
        $data = array_map('strtolower', $this->like_not_need_login);
        if (!in_array($action, $data)) {
            return false;
        }
        return true;
    }


    /**
     * Notes: 自定义重定向
     * @param mixed ...$args
     * @author 段誉(2021/4/9 14:04)
     */
    public function redirect(...$args)
    {
        throw new HttpResponseException(redirect(...$args));
    }


}