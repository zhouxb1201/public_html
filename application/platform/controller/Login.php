<?php

namespace app\platform\controller;

\think\Loader::addNamespace('data', 'data/');

use data\model\UserModel;
use data\service\AdminUser as AdminUser;
use data\service\User as User;
use data\service\WebSite as WebSite;
use think\Controller;
use data\model\ModuleModel;
use \think\Session as Session;
use think\db;
use data\service\Config;
use data\service\Member as Member;

class Login extends Controller {

    public $user;

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
        $this->style = 'platform/';
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

    /**
     * 用户登录
     *
     * @return number
     */
    public function login() {
        $user_name = $_POST['username'];
        $password = $_POST['password'];

        $retval = $this->user->login($user_name, $password);
        
        if ($retval < 1) {
            return AjaxReturn($retval);
        }
        $model = $this->user->getRequestModel();
        $isAdmin = Session::get($model . 'is_admin');
        if ($retval == 1) {
            $first_list = Session::get($model . 'module_id_array');
            $module = new ModuleModel();
            //$list = $module->where("module_id in ($first_list) and is_menu=1 and level=1 and module='".$model."'")->order('sort','asc')->find();
            $list = $module->where(['module_id' => ['IN', $first_list], 'is_menu' => 1, 'level' => 1, 'module' => $model])->order('sort', 'asc')->find();
            //var_dump(Db::table('')->getLastSql());
            $checkSecond = $module->where("pid='" . $list['module_id'] . "' and is_menu=1 and level=2 and module='" . $model . "'")->order('sort', 'asc')->find();
            $user = new User();
            if ($checkSecond) {
                $checkAuth = $user->checkAuth($checkSecond['module_id']);
                if (!$checkAuth) {
                    $list = $module->where("module_id in ($first_list) and is_menu=1 and level=2 and module='" . $model . "'")->order('sort', 'asc')->find();
                } else {
                    $list = $checkSecond;
                }
            } else {
                //$list = $module->where("module_id in ($first_list) and is_menu=1 and level=1 and module='".$model."'")->order('sort','asc')->find();
                $list = $module->where(['module_id' => ['IN', $first_list], 'is_menu' => 1, 'level' => 1, 'module' => $model])->order('sort', 'asc')->find();
            }
//            $website = new WebSite();
//            $website->ucMemberLogin($user_name, $password);
//            objToArr($list);
//            $web_site = new WebSite();
//            $web_info = $web_site->getWebSiteInfo();
//            if ($web_info && (strtotime($web_info['shop_validity_time']) + 3600 * 7 * 24) <= time()) {
//                $website = new WebSiteModel();
//                $website->save(['web_status'=>0,'wap_status'=>0], [
//                    "website_id" => $web_info['website_id']
//                ]);
//            }
            if ($list) {
                return AjaxReturn(1,['url'=>$list['url']]);
            } else {
                return AjaxReturn(USER_NO_AITHORITY);
            }
        }else{
            return AjaxReturn(-2001);
        }
    }
    /**
     * 发送注册短信验证码
     */
    public function sendSmsRegisterCode() {
        $params['mobile'] = request()->post('mobile', '');
        $params['shop_id'] = 0;
        $params['website_id'] = 0;
        $result = runhook('Notify', 'merchantRegisterBySms', $params);
        Session::set('mobileVerificationCode', $result['param'],300);
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

    public function register() {

        return view($this->style . 'register/register');
    }
	public function registerlk() { //dong:添加registerlk方法

        return view($this->style . 'register/registerLk');
    }
	public function loginlk() {
        return view($this->style . 'Login/loginlk');
    }
    public function registerMobile() {

        return view($this->style . 'register/registerMobile');
    }
    public function registerMobileSuccess() {

        return view($this->style . 'register/registerMobileSuccess');
    }

   public function versionLow() {

        return view($this->style . 'Login/versionLow');
    }
    public function loginMobile() {

        return view($this->style . 'Login/loginMobile');
    }
    /**
     * 短信验证
     */
    public function forgotValidation() {
        $send_type = request()->post("type", "");
        $send_param = request()->post("send_param", "");
        if ($send_type == 'sms') {
            $member = new UserModel();
            $user_list = $member->getInfo(['user_tel' => $send_param, 'is_system' => 1, 'port' => 'platform']);
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
                "website_id" => 0
            );
            $result = runhook("Notify", "merchantForgotPasswordBySms", $params);
            Session::set('forgotPasswordVerificationCodeP', $result['param'],300);
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

    /*
     * 以下为找回密码页面
     */

    public function retrievePwd() {
        if (request()->isAjax()) {
            $member = new UserModel();
            // 获取数据库中的用户列表
            $tel = request()->get('username', '');
            $exist = 0;
            $user_list = $member->getInfo(['user_tel' => $tel, 'is_system' => 1, 'port' => 'platform']);
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
        return view($this->style . "register/retrievePwd");
    }

    public function check_find_password_code() {
        $send_param = request()->post('send_param', '');
        $param = Session::get('forgotPasswordVerificationCodeP');
        if ($send_param == $param && $send_param != '') {
            Session::delete('forgotPasswordVerificationCodeP');
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

    public function checkMobile() {
        // 获取数据库中的用户列表
        $user = new UserModel();
        $mobile = request()->get('mobile', '');
        $user_info = $user->getInfo(['user_tel' => $mobile, 'port' => 'platform'], 'uid')['uid'];
        return $user_info;
    }

    /**
     * 修改密码
     */
    public function setNewPassword()
    {
        $userInfo = request()->post('userInfo', '');
        $password = request()->post('password', '');
        $member = new Member();
        $condition = ['port'=>'platform','user_tel'=>$userInfo];
        $res = $member->updatePassword($password,$condition);
        return AjaxReturn($res);
    }
    /**
     * 找回密码密码重置
     */
    public function setNewPasswordByEmailOrMobile() {
        $userInfo = request()->post('userInfo', '');
        $password = request()->post('password', '');
        $type = request()->post('type', '');
        $member = new Member();
        if ($type == "email") {
            $codeEmail = Session::get("codeEmail");
            if ($userInfo != $codeEmail) {
                return $retval = array(
                    "code" => -1,
                    "message" => "该邮箱与验证邮箱不符"
                );
            } else {
                $res = $member->updateUserPasswordByEmail($userInfo, $password);
                Session::delete("codeEmail");
            }
        } else
        if ($type == "mobile") {
            $codeMobile = Session::get("codeMobile");
            if ($userInfo != $codeMobile) {
                return $retval = array(
                    "code" => -1,
                    "message" => "该手机号与验证手机不符"
                );
            } else {
                $res = $member->updateUserPasswordByMobile($userInfo, $password);
                Session::delete("codeMobile");
            }
        }
        return AjaxReturn($res);
    }

    public function addWebsite() {
        if (request()->isAjax()) {
            $shop_type = $this->get_default_version();
            $user_account = request()->post('user_account', '');
            $user_pwd = request()->post('user_pwd', '');
            $shop_name = request()->post('shop_name', '');
            $shop_status = request()->post('shop_status', '');
            $note = request()->post('note', '');
            $user_tel = request()->post('user_tel', '');
            $shop_validity_time = strtotime(request()->post('shop_validity_time', date('Y-m-d H:i:s',strtotime('+1month'))));
            $mobile_code = request()->post('mobile_code', '');
            $verification_code = Session::get('mobileVerificationCode');
            if ($mobile_code == $verification_code && !empty($verification_code)) {
                $shop = new WebSite();
                $res = $shop->addWebsite($user_account, $user_pwd, $shop_name, $shop_type, $shop_status, $shop_validity_time, $note, $user_tel);
                if($res){
                    $params['mobile'] = $user_tel;
                    $params['shop_id'] = 0;
                    $params['website_id'] = 0;
                    runhook('Notify', 'merchantRegSuccessBySms', $params);
                }
                return AjaxReturn($res);
            } else {
                return AjaxReturn(-1, '验证码错误');
            }
        }
    }

    /**
     * 查询默认版本
     */
    public function get_default_version() {

        $id = Db::query('select merchant_versionid from `sys_merchant_version` where `is_default` = 1');
        return !empty($id) ? $id[0]['merchant_versionid'] : '1';
    }

    /**
     * 版本到期提示页
     */
    public function errorTmpl() {
        return view($this->style . 'Login/errorTmpl');
    }

    /**
     * 退出登录
     */
    public function logout() {
        $this->user->Logout();
        if (request()->isAjax()) {
            return AjaxReturn(1);
        }
        $this->redirect(__URL(__URL__ . '/' . PLATFORM_MODULE . "/login"));
    }

}
