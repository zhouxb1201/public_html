<?php

namespace app\admin\controller;

\think\Loader::addNamespace('data', 'data/');

use data\service\AdminUser as AdminUser;
use data\service\WebSite as WebSite;
use think\Controller;
use \think\Session as Session;
use data\model\UserModel;
use data\service\Member as Member;
use data\service\Config;

class Login extends Controller {

    public $user;
    public $website_id;

    /**
     * 当前版本的路径
     *
     * @var string
     */
    public $style;
    // 验证码配置
    public $login_verify_code;

    public function __construct() {
        parent::__construct();
        $this->init();
    }

    private function init() {

        $this->user = new AdminUser();
        $web_site = new WebSite();
        $this->style = 'admin/';
        $this->website_id = request()->get('website_id', 0);
        $web_info = $web_site->getWebSiteInfo();
        $this->style = STYLE_DEFAULT_ADMIN . '/';
        $this->assign("style", STYLE_DEFAULT_ADMIN);
        $this->assign("title_name", $web_info['title']);
        if($this->website_id){
            $this->assign("website_id", $this->website_id);
        }
        if( request()->isGet() && empty($this->website_id)){
            $this->error('无权限访问');
        }
        $config_service = new Config();
        $logoConfig = $config_service->getLogoConfig();
        $this->assign('logo_config', $logoConfig);
    }

    public function index() {
        return view($this->style . 'Login/login');
    }

    public function admin_login() {
        return view($this->style . 'login/login');
    }
    public function versionLow() {

        return view($this->style . 'Login/versionLow');
    }
    public function loginMobile() {

        return view($this->style . 'Login/loginMobile');
    }

    /**
     * 用户登录
     *
     * @return number
     */
    public function login() {
        $user_name = request()->post('userName', '');
        $password = request()->post('password', '');
        $website_id = request()->post('website_id', '');
        if(empty($website_id)){
            return AjaxReturn(-3000);
        }
        $retval = $this->user->login($user_name, $password,0,$website_id);
        $model = $this->user->getRequestModel();
        $isAdmin = Session::get($model . 'is_admin');
        if ($retval == 1 && !$isAdmin) {
            $web_site = new WebSite();
            $web_info = $web_site->getWebSiteInfo();
            if (strtotime($web_info['shop_validity_time']) + 3600 * 7 * 24 <= time()  && $web_info['shop_validity_time'] > 0) {
                return AjaxReturn(-1001);
            }else{
                return AjaxReturn($retval);
            }
        }else{
            return AjaxReturn(-2001);
        }
    }

    /**
     * 退出登录
     */
    public function logout() {
        $this->user->Logout();
        $redirect = __URL(__URL__ . '/' . ADMIN_MODULE . '/login?website_id=').$this->website_id;
        $this->redirect($redirect);
    }
    /*
     * 以下为找回密码页面
     */

    public function retrievePwd() {
        if (request()->isAjax()) {
            $member = new UserModel();
            // 获取数据库中的用户列表
            $tel = request()->get('username', '');
            $exist = 0;
            $user_list = $member->getInfo(['user_tel' => $tel, 'is_system' => 1, 'port' => 'admin']);
            if ($user_list) {
                $exist = 1;
            }
            return $exist;
        }

        // 获取商城logo
        $website = new WebSite();
        $web_info = $website->getWebSiteInfo();
        $this->assign("web_info", $web_info);
        $this->assign("title_before", "密码找回");
        return view($this->style . "Login/retrievePwd");
    }
    /**
     * 短信验证
     */
    public function forgotValidation() {
        $send_type = request()->post("type", "");
        $send_param = request()->post("send_param", "");
        if ($send_type == 'sms') {
            $member = new UserModel();
            $user_list = $member->getInfo(['user_tel' => $send_param, 'is_system' => 1, 'port' => 'admin']);
            if (!$user_list) {
                return $result = [
                    'code' => -1,
                    'message' => "该手机号未注册"
                ];
            }
            $params = array(
                "send_type" => $send_type,
                "mobile" => $send_param,
                "shop_id" => 0,
                "website_id" => $this->website_id
            );
            $result = runhook("Notify", "forgotPasswordBySms", $params);
            Session::set('forgotPasswordVerificationCodeA', $result['param'],300);
            if (empty($result)) {
                return $result = [
                    'code' => -1,
                    'message' => "发送失败"
                ];
            } else if ($result['code'] < 0) {
                return $result = [
                    'code' => $result['code'],
                    'message' => $result['message']
                ];
            } else {
                return $result = [
                    'code' => 1,
                    'message' => "发送成功"
                ];
            }
        }
    }
    public function check_find_password_code() {
        $send_param = request()->post('send_param', '');
        $param = Session::get('forgotPasswordVerificationCodeA');
        if ($send_param == $param && $send_param != '') {
            Session::delete('forgotPasswordVerificationCodeA');
            $retval = [
                'code' => 1,
                'message' => "验证码一致"
            ];
        } else {
            $retval = [
                'code' => -1,
                'message' => "验证码不一致"
            ];
        }
        return $retval;
    }
    /**
     * 修改密码
     */
    public function setNewPassword()
    {
        $userInfo = request()->post('userInfo', '');
        $password = request()->post('password', '');
        $website_id = request()->post('website_id', '');
        if(!$userInfo || !$password || !$website_id){
            return AjaxReturn(0);
        }
        $member = new Member();
        $condition = ['port'=>'admin','user_tel'=>$userInfo,'website_id' => $website_id];
        $res = $member->updatePassword($password,$condition);
        return AjaxReturn($res);
    }

}
