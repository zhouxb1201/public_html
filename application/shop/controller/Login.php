<?php

namespace app\shop\controller;
use addons\coupontype\model\VslCouponModel;
use addons\giftvoucher\model\VslGiftVoucherRecordsModel;
use addons\registermarketing\server\RegisterMarketing;
use addons\taskcenter\model\VslPosterRecordModel;
use data\extend\ThinkOauth as ThinkOauth;
use data\model\UserModel;
use data\model\UserTaskModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslMemberModel;
use data\model\WebSiteModel;
use data\service\Config as Config;
use data\service\Member as Member;
use data\service\WebSite as WebSite;
use think\Controller;
use data\service\BaseService;
use \think\Session as Session;
use data\service\Member\MemberAccount;
use data\model\SysPcCustomStyleConfigModel;
use addons\coupontype\server\Coupon as CouponServer;
use data\extend\custom\Common;
use data\extend\WchatOauth;
use data\service\AddonsConfig as addonsConfig;
\think\Loader::addNamespace('data', 'data/');
/**
 * 登录控制器
 */
class Login extends Controller
{
    // 验证码配置
    public $login_verify_code;
    // 通知配置
    public $notice;

    protected $website_id;

    public function __construct()
    {
        $default_client = request()->cookie("default_client", "");
        if ($default_client == "shop") {
        } elseif (request()->isMobile()) {
            $redirect = __URL(__URL__ . "/wap/mall/index");
            $this->redirect($redirect);
            exit();
        }
        parent::__construct();
        $base = new BaseService();
        $model = $base->getRequestModel();
        $website_id = checkUrl();
        if ($website_id && is_numeric($website_id)) {
            Session::set($model . 'website_id', intval($website_id));
            $this->website_id = $website_id;
        } elseif (Session::get($model . 'website_id')) {
            $this->website_id = Session::get($model . 'website_id');
        } else {
            $this->error("参数错误，请检查域名");
        }
        $this->assign("website_id", $this->website_id);
        $ConfigService = new addonsConfig();
        $pc_info = $ConfigService->getAddonsConfig('pcport',$this->website_id);
        $pc_info = json_decode($pc_info['value'],true);
        if(!getAddons('pcport', $this->website_id)){
            $this->error($pc_info['close_reason']);
        }
        $this->assign("pc_info", $pc_info);
        $this->init();
    }

    public function _empty($name)
    {
    }

    public function init()
    {
        $this->user = new Member();
        $this->web_site = new WebSite();
        $config = new Config();
        $web_info = $this->web_site->getWebSiteInfo();
        $this->assign("platform_shopname", $this->user->getInstanceName());
        $this->assign("title", $web_info['title']);
        // 获取当前使用的PC端模板
        $this->style = "shop/new/";
        //风格
        $styleModel = new SysPcCustomStyleConfigModel();
        $style = $styleModel->getInfo(['website_id'=>$this->website_id]);
        $this->assign('colorStyle',$style['style']);
        // 是否开启qq跟微信
        $instance_id = 0;
        $qq_info = $config->getQQConfig($instance_id);
        $Wchat_info = $config->getWchatConfig($instance_id);
        $this->assign("qq_info", $qq_info);
        $this->assign("Wchat_info", $Wchat_info);
        // 是否开启验证码
        $this->login_verify_code = $config->getLoginVerifyCodeConfig($instance_id);
        $this->assign("login_verify_code", $this->login_verify_code["value"]);
        // 是否开启通知
        $noticeMobile = $config->getNoticeMobileConfig($instance_id);
        $noticeEmail = $config->getNoticeEmailConfig($instance_id);
        $this->notice['noticeEmail'] = $noticeEmail[0]['is_use'];
        $this->notice['noticeMobile'] = $noticeMobile[0]['is_use'];
        $this->assign("notice", $this->notice);
        // 配置头部
        $seoconfig = $config->getSeoConfig($instance_id);
        $this->assign("seoconfig", $seoconfig);
        $com = new Common($instance_id, $this->website_id);
        $dir_common = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/common';
        if(file_exists($dir_common)){
            $bottom = $com->get_html_file($dir_common . '/bottom_html.php');
            $this->assign('bottom', $bottom);
        }
    }
    /*
       * 首页登录
       */
    public function index()
    {
        if (request()->isPost()) {
            $num = Session::get('loginCode');
            if($num==null){
                Session::set('loginCode',0,300);
                $num = 0;
            }
            $num++;
            Session::set('loginCode',$num,300);
            $username = request()->post('username', '');
            $password = request()->post('password', '');
            if (trim($username) == "" || trim($username) == undefined) {
                $retval = [
                    'code' => 0,
                    'num'=>$num,
                    'message' => "账号不能为空"
                ];
                return $retval;
            }
            if (trim($password) == "" || trim($password) == undefined) {
                $retval = [
                    'code' => 0,
                    'num'=>$num,
                    'message' => "密码不能为空"
                ];
                return $retval;
            }
            $user_name = trim($username);
            $password = trim($password);
            if (!empty($user_name) && !empty($password)) {
                $res = $this->user->login($username, $password, 1, '', 4);//0-无来源 联合登录 (1-公众号 2-小程序 3-移动H5  4-PC  5-APP)
            }
            if ($res == 1) {
                Session::delete('loginCode');
                if (!empty($_SESSION['login_pre_url'])) {
                    $retval = [
                        'code' => 1,
                        'url' => $_SESSION['login_pre_url']
                    ];
                }else{
                    $retval = [
                        'code' => 2,
                        'url' => __URL("SHOP_MAIN/index/index")
                    ];
                }
            } else {
                $retval = [
                    'code' => $res,
                    'num'=>$num,
                    'message' => "用户名或者密码错误"
                ];
            }
            return $retval;
        }
        // 获取商场logo
        $website = new WebSite();
        $web_info = $website->getWebSiteInfo();
        $tencent = new Config();
        $instance_id = 0;
        $qq_info = $tencent->getQQConfig($instance_id);
        $Wchat_info = $tencent->getWchatConfig($instance_id);
        $this->assign("qq_info", $qq_info);
        $this->assign("Wchat_info", $Wchat_info);
        $this->assign("web_info", $web_info);
        $this->assign("title_before", "用户登录");
        return view($this->style . 'Login/login');
    }
     public function versionLow() {

        return view($this->style . 'Login/versionLow');
    }

    /*
     * 以下两个分别为注册页面
     */
    public function register()
    {
        if (request()->isPost()) {
            $member = new Member();
            $password = request()->post('password', '');
            $email = '';
            $mobile = request()->post('mobile', '');
            $extend_code = request()->post('extend_code', '');//推广码
            $user_name = '';
            $oauthWq = Session::get('oauthWq');
            $uid = request()->post('uid', '');
            if ($this->notice['noticeMobile'] == 1) {
                $mobile_code = request()->post('mobile_code', '');
                $verification_code1 = Session::get('mobileBindVerificationCode');
                $verification_code = Session::get('registerVerificationCode');
                if ($mobile_code == $verification_code && !empty($verification_code)) {
                   if(empty($uid) && $oauthWq['type']==2){
                        $retval = $member->registerMember($extend_code,$user_name, $password, $email, $mobile, $oauthWq['unionid'], json_encode($oauthWq['data']), '', '', '');
                    }elseif(empty($uid) && $oauthWq['type']==1){
                        $retval = $member->registerMember($extend_code,$user_name, $password, $email, $mobile, '','' , '', json_encode($oauthWq['data']), $oauthWq['unionid'], '', $oauthWq['data']['openid'], '', '', 4);
                    }else {
                        $retval = $member->registerMember($extend_code,$user_name, $password, $email, $mobile, '', '', '', '', '', '', '', '', '', 4);
                    }
                    $result = true;
                } elseif($mobile_code == $verification_code1 && !empty($verification_code1)){
                    if($uid && $oauthWq['type']==2){
                        $retval = $member->bUserInfo(2,$uid,$password,$mobile,json_encode($oauthWq['data']), $oauthWq['data']['openid']);
                    }elseif($uid && $oauthWq['type']==1){
                        $retval = $member->bUserInfo(1,$uid,$password,$mobile,json_encode($oauthWq['data']), $oauthWq['unionid'], $oauthWq['data']['openid']);
                    }
                    $result = true;
                } else{
                   return $result = [
                        'code' => -1,
                        'message' => "手机验证码错误"
                    ];
                }
            }
            if ($retval > 0 && $result  && getAddons('registermarketing', $this->website_id)) {
                //注册营销
                $registerMarketingServer = new RegisterMarketing();
                $registerMarketingServer->deliveryAward($retval);
            }
            if($retval > 0){
                Session::delete('registerVerificationCode');
                Session::delete('mobileBindVerificationCode');
                return $result = [
                    'code' => 1,
                    'url' => __URL("SHOP_MAIN/index/index")
                ];
             }else {
                return $result = [
                    'code' => -1,
                    'message' => "注册失败"
                ];
            }
        }
        // 获取商场logo
        $website = new WebSite();
        $web_info = $website->getWebSiteInfo();
        $config = new Config();
        $instanceid = 0;
        $phone_info = $config->getMobileMessage($instanceid);
        $this->assign("phone_info", $phone_info);
        $notice_templa_info = $config->getNoticeTemplateOneDetail($instanceid, 'sms', 'register_validate');
        if (!empty($notice_templa_info)) {
            $is_enable = $notice_templa_info['is_enable'];
        } else {
            $is_enable = 0;
        }
        $email_info = $config->getEmailMessage($instanceid);
        $this->assign("email_info", $email_info);
        $this->assign("web_info", $web_info);
        $this->assign("is_enable", $is_enable);
        $this->assign("title_before", "手机注册");
        return view($this->style . "Login/registered");
    }
    /**
     * 第三方登录
     */
    public function oauthLogin()
    {
        $config = new Config();
        $type = request()->get('type', '');
        // 因为回调的module是wapapi 所以设置一个标识 在保存登陆信息的时候保存在 shop 名下
        // see service/User initLoginInfo
        Session::set('oa_login_type','pc');
        if ($type == "WCHAT") {
            $config_info = $config->getWchatConfig(0);
            if (empty($config_info["value"]["APP_KEY"]) || empty($config_info["value"]["APP_SECRET"])) {
                $this->error("当前系统未设置微信第三方登录!");
            }
        } else
            if ($type == "QQLOGIN") {
                $config_info = $config->getQQConfig(0);
                if (empty($config_info["value"]["APP_KEY"]) || empty($config_info["value"]["APP_SECRET"])) {
                    $this->error("当前系统未设置QQ第三方登录!");
                }
            }
        $_SESSION['login_type'] = $type;
        $test = ThinkOauth::getInstance($type,null,$this->website_id);
        $this->redirect($test->getRequestCodeURL($this->website_id));
    }
    /**
     * 检测微信浏览器并且自动登录
     */
    public function wchatLogin()
    {
        if (!($this->determineWapWhetherToOpen())) {
            return ['code' => -1, 'message' => '已关闭'];
        }
        // 微信浏览器自动登录
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {

            if (empty($_SESSION['request_url'])) {
                $_SESSION['request_url'] = request()->url(true);
            }
            $domain_name = \think\Request::instance()->domain();
            if (!empty($_COOKIE[$domain_name . "member_access_token"])) {
                $token = json_decode($_COOKIE[$domain_name . "member_access_token"], true);
            } else {
                $wchat_oauth = new WchatOauth($this->website_id);
                $token = $wchat_oauth->get_member_access_token();
                if (!empty($token['access_token'])) {
                    setcookie($domain_name . "member_access_token", json_encode($token));
                }
            }
            $wchat_oauth = new WchatOauth($this->website_id);

            if (!empty($token['openid'])) {
                if (!empty($token['unionid'])) {
                    $wx_unionid = $token['unionid'];
                    $retval = $this->user->wchatUnionLogin($wx_unionid);
                    if ($retval == 1) {
                        $this->user->modifyUserWxhatLogin($token['openid'], $wx_unionid);
                    } elseif ($retval == USER_LOCK) {
                        $redirect = __URL(__URL__ . "/shop/login/userlock");
                        $this->redirect($redirect);
                    } else {
                        $retval = $this->user->wchatLogin($token['openid']);
                        if ($retval == USER_NBUND) {
                            $info = $wchat_oauth->get_oauth_member_info($token);

                            $result = $this->user->registerMember('', '123456', '', '', '', '', $token['openid'], $info, $wx_unionid);
                        } elseif ($retval == USER_LOCK) {
                            // 锁定跳转
                            $redirect = __URL(__URL__ . "/shop/login/userlock");
                            $this->redirect($redirect);
                        }
                    }
                } else {
                    $wx_unionid = '';
                    $retval = $this->user->wchatLogin($token['openid']);
                    if ($retval == USER_NBUND) {
                        $info = $wchat_oauth->get_oauth_member_info($token);
                        $result = $this->user->registerMember('', '123456', '', '', '', '', $token['openid'], $info, $wx_unionid);
                    } elseif ($retval == USER_LOCK) {
                        // 锁定跳转
                        $redirect = __URL(__URL__ . "/shop/login/userlock");
                        $this->redirect($redirect);
                    }
                }

                if (!empty($_SESSION['login_pre_url'])) {
                    $this->redirect($_SESSION['login_pre_url']);
                } else {
                    $redirect = __URL(__URL__ . "/shop/member");
                    $this->redirect($redirect);
                }
            }
        }
    }
    /**
     * 判断wap端是否开启
     */
    public function determineWapWhetherToOpen()
    {
        $web_site = new WebSite();
        $web_info = $web_site->getWebSiteInfo();

        if ($web_info['wap_status'] == 2) {
            //webClose($web_info['close_reason'], $web_info['logo']);
            return false;
        }
        return true;
    }
    /**
     * 用户锁定界面
     *
     * @return \think\response\View
     */
    public function userLock()
    {
        return view($this->style . "Login/userLock");
    }
    /**
     * 发送注册短信验证码
     */
    public function sendSmsRegisterCode()
    {
        $params['mobile'] = request()->post('mobile', '');
        $params['shop_id'] = 0;
        $params['website_id'] = $this->website_id;
        $result = runhook('Notify', 'registerBefore', $params);
        Session::set('registerVerificationCode', $result['param'],300);
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
                'code' => 0,
                'message' => "发送成功"
            ];
        }
    }
    /**
     * 发送绑定手机短信验证码
     */
    public function sendSmsBindMobile()
    {
        $params['mobile'] = request()->post('mobile', '');
        $params['shop_id'] = 0;
        $params['website_id'] = $this->website_id;
        $result = runhook('Notify', 'bindMobileBySms', $params);
        Session::set('mobileBindVerificationCode', $result['param'],300);
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
                'code' => 0,
                'message' => "发送成功"
            ];
        }
    }

    /**
     * 注册手机号验证码验证
     */
    public function registerCheckCode()
    {
        $num = Session::get('regCode');
        if($num==null){
            Session::set('regCode',0,300);
            $num = 0;
        }
        $num++;
        Session::set('regCode',$num,300);
        $send_param = request()->post('send_param', '');
        $param = session::get('registerVerificationCode');
        if ($send_param == $param && $send_param != '') {
            $retval = [
                'code' => 0,
                'message' => "手机验证码正确"
            ];
        } else {
            $retval = [
                'code' => -1,
                'num' => $num,
                'message' => "手机验证码错误"
            ];
        }
        return $retval;
    }
    /**
     * 注册邮箱验证码验证
     */
    public function email()
    {
        if (request()->isAjax()) {
            // 获取数据库中的用户列表
            $user_email = request()->get('email', '');
            $member = new Member();
            $exist = $member->memberIsEmail($user_email);
            return $exist;
        }
        if (request()->isPost()) {
            $min = 1;
            $max = 1000000000;
            $member = new Member();
            $password = request()->post('password', '');
            $email = request()->post('email', '');
            $mobile = '';
            $user_name = '';
            $uid = $this->user->getSessionUid();
            $email_code = request()->post('email_code', '');
            $verification_code = Session::get('emailVerificationCode');
            // 判断邮箱是否开启
            if ($this->notice['noticeEmail'] == 1) {
                // 开启的话进行验证
                if ($email_code == $verification_code && !empty($verification_code)) {
                    $retval = $member->registerMember($user_name, $password, $email, $mobile, '', '', '', '', '');
                    $result = true;
                } else {
                    $retval = "";
                    $result = false;
                }
            } else {
                // 未开启直接进行注册
                $retval = $member->registerMember($user_name, $password, $email, $mobile, '', '', '', '', '');
                $result = true;
            }
            if ($retval > 0) {
                $this->success("注册成功", __URL(__URL__ . "/index"));
            } else {
                $error_array = AjaxReturn($retval);
                $message = $error_array["message"];
                $this->error($message, __URL(__URL__ . "/login/email"));
            }
        }
        // 获取商场logo
        $website = new WebSite();
        $web_info = $website->getWebSiteInfo();
        $instanceid = 0;
        $config = new Config();
        // 验证注册配置
//        $this->verifyRegConfig("email");

        $phone_info = $config->getMobileMessage($instanceid);
        $this->assign("phone_info", $phone_info);
        $email_info = $config->getEmailMessage($instanceid);
        $this->assign("email_info", $email_info);

        $this->assign("web_info", $web_info);
        $this->assign("title_before", "邮箱注册");
        return view($this->style . "Login/email");
    }

    /**
     * 验证手机号是否已注册
     */
    public function checkMobile()
    {
        // 获取数据库中的用户列表
        $user = new UserModel();
        $mobile = request()->get('mobile', '');
        // 判断是哪种账号体系
        $website = new WebSiteModel();
        $account_type = $website->getInfo(['website_id' => $this->website_id], 'account_type')['account_type'];
        $condition = ['user_tel'=>$mobile,'website_id'=>$this->website_id,'is_member' =>1];
        if($account_type == 3){
            $condition['mall_port'] = request()->get('mall_port', 0) ? : 4;
        }
        $user_info = $user->getInfo($condition, 'uid');
        return $user_info;
    }
    /**
     * 验证码
     */
    public function vertify()
    {
        $vertification = request()->post('vertification', '');
        if (!captcha_check($vertification)) {
            $retval = [
                'code' => 0,
                'message' => "验证码错误"
            ];
        } else {
            $retval = [
                'code' => 1,
                'message' => "验证码正确"
            ];
        }
        return $retval;
    }
    /*
     * 以下为找回密码页面
     */
    public function findPasswd()
    {
        if (request()->isAjax()) {
            // 获取数据库中的用户列表
            $username = request()->get('username', '');
            $exist = 0;
//            $user_list = $this->user->getMemberList();
//            foreach ($user_list["data"] as $user_list2) {
//                if ($user_list2["user_tel"] == $username) {
//                    $exist = 1;
//                }
//            }
            $user = new UserModel();
            $user_info = $user->getInfo(['website_id'=>$this->website_id,'user_tel'=>$username,'is_member'=>1],'user_status');
            if(!$user_info){
                return 0;
            }
            if($user_info['user_status']){
                $exist =1;
            }else{
                $exist =2;
            }
            return $exist;
        }

        // 获取商城logo
        $website = new WebSite();
        $web_info = $website->getWebSiteInfo();
        $this->assign("web_info", $web_info);
        $this->assign("title_before", "密码找回");
        return view($this->style . "Login/forgetPwd");
    }
    /*
       * 以下为绑定手机
       */
    public function bMobile()
    {
        if (request()->isPost()) {
            $member = new Member();
            $mobile = request()->post('mobile', '');
            $id = request()->post('uid', '');
            $mobile_code = request()->post('mobile_code', '');
            $verification_code = Session::get('mobileVerificationCode');
            if ($mobile_code == $verification_code && !empty($verification_code)) {
                    $res = $member->setMobile($id,$mobile);
                Session::delete('mobileVerificationCode');
                    return AjaxReturn($res);
            }else{
                $retval = [
                    'code' => -1,
                    'message' => "验证码错误"
                ];
                return $retval;
            }
        }
        // 获取商城logo
        $website = new WebSite();
        $web_info = $website->getWebSiteInfo();
        $uid = request()->get('uid', '');
        $this->assign("web_info", $web_info);
        $this->assign("uid", $uid);
        $this->assign("title_before", "绑定手机");
        return view($this->style . "Login/phoneRelated");
    }
    /**
     * 短信验证
     */
    public function forgotValidation()
    {
        $send_type = request()->post("type", "");
        $send_param = request()->post("send_param", "");
        $member = new Member();
        if ($send_type == 'sms') {
            if (!$member->memberIsMobile($send_param)) {
                $result = [
                    'code' => -2,
                    'message' => "该手机号未注册"
                ];
                return $result;
            } else {
                Session::set("codeMobile", $send_param,300);
            }
            $params = array(
                "shop_id"=>0,
                "mobile" => $send_param,
                "website_id" => $this->website_id
            );
            $result = runhook("Notify", "forgotPasswordBySms", $params);
            Session::set('forgotPasswordVerificationCode', $result['param'],300);
            if ($result['code']==-1) {
                return $result = [
                    'code' => -1,
                    'message' => "发送失败"
                ];
            } else {
                return $result = [
                    'code' => 1,
                    'message' => "发送成功"
                ];
            }
        }
    }

    /**
     * 找回密码密码重置
     */
    public function setNewPasswordByEmailOrMobile()
    {
        $userInfo = request()->post('userInfo', '');
        $password = request()->post('password', '');
        $type = request()->post('type', '');
        // 判断是哪种账号体系
        $website = new WebSiteModel();
        $account_type = $website->getInfo(['website_id' => $this->website_id], 'account_type')['account_type'];
        if($account_type == 3){
            $mall_port = 4;
        }else{
            $mall_port = 0;
        }
        if ($type == "email") {
            $codeEmail = Session::get("codeEmail");
            if ($userInfo != $codeEmail) {
                return $retval = array(
                    "code" => -1,
                    "message" => "该邮箱与验证邮箱不符"
                );
            } else {
                $res = $this->user->updateUserPasswordByEmail($userInfo, $password, $mall_port);
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
                    $res = $this->user->updateUserPasswordByMobile($userInfo, $password, $mall_port);
                    Session::delete("codeMobile");
                }
            }
        return AjaxReturn($res);
    }

    public function check_code()
    {
        $send_param = request()->post('send_param', '');
        $param = Session::get('emailVerificationCode');
        if ($send_param == $param && $send_param != '') {
            $retval = [
                'code' => 0,
                'message' => "验证码一致"
            ];
            Session::delete('emailVerificationCode');
        } else {
            $retval = [
                'code' => 1,
                'message' => "验证码不一致"
            ];
        }
        return $retval;
    }

    public function check_find_password_code()
    {
        $send_param = request()->post('send_param', '');
        $param = Session::get('forgotPasswordVerificationCode');
        if ($send_param == $param && $send_param != '') {
            $retval = [
                'code' => 1,
                'message' => "验证码一致"
            ];
            Session::delete('forgotPasswordVerificationCode');
        } else {
            $retval = [
                'code' => -1,
                'message' => "验证码不一致"
            ];
        }
        return $retval;
    }

    /*
     * 判断当前账号是否绑定了手机
     * **/
    public function getAccountType()
    {
        $uid = $this->user->getSessionUid();
        $website = new WebSiteModel();
        $account_type_arr = $website->getInfo(['website_id' => $this->website_id], 'account_type, is_bind_phone');
        //判断当前用户是否绑定过手机
        $user = new UserModel();
        $user_tel = $user->getInfo(['uid' => $uid, 'website_id' => $this->website_id], 'user_tel')['user_tel'];
//        if( ($account_type_arr['account_type'] != 3 || ($account_type_arr['account_type'] == 3 && $account_type_arr['is_bind_phone'] == 1))
//            && empty($user_tel) ){//判断第三种情况考虑绑不绑定手机的时候
        if( empty($user_tel) ){//不管是什么情况都要绑定手机
            $account_arr['code'] = 1;
            $account_arr['have_phone'] = 0;
        }else{
            $account_arr['code'] = 0;
            $account_arr['have_phone'] = 1;
        }
        if($account_type_arr['account_type'] == 3 && $account_type_arr['is_bind_phone'] == 0){//如果是第三种并且 设置了不绑定手机
            $account_arr['code'] = 0;
            $account_arr['have_phone'] = 1;
        }
        return json($account_arr);
    }

    /*
     * 获取当前账号体系类型
     * **/
    public function isBindPhone()
    {
        $uid = $this->user->getSessionUid();
        $user_tel = request()->post('mobile');
        $website = new WebSiteModel();
        $account_type_arr = $website->getInfo(['website_id' => $this->website_id], 'account_type, is_bind_phone');
        //判断当前用户是否绑定过手机
        $user = new UserModel();
        if($account_type_arr['account_type'] == 3){
            $mall_port = $user->getInfo(['uid' => $uid, 'website_id' => $this->website_id], 'mall_port')['mall_port'];
            $condition['mall_port'] = $mall_port;
        }
        $condition['user_tel'] = $user_tel;
        $condition['is_member'] = 1;
        $condition['website_id'] = $this->website_id;
//        var_dump($user->getCount($condition));
//        echo $user->getLastSql();exit;
        if($user->getCount($condition) > 0){//说明绑定过了
            return json(['code' => 0]);
        }else{
            return json(['code' => 1]);
        }
    }

    /**
     * 关联账号
     */
    public function shopAssociateAccount()
    {
        $oauthWq = Session::get('oauthWq') ? : [];
//        if (empty($oauthWq['type'])) {
//            echo json_encode(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录'], JSON_UNESCAPED_UNICODE);
//            exit;
//        }
        // 判断是否有头像。
        $member = new Member();
        $user_model = new UserModel();
//        $oauthWq = Session::get('oauthWq');
        $member_model = new VslMemberModel();
        $website = new WebSiteModel();
        $uid = $this->user->getSessionUid();
        $account_type = $website->getInfo(['website_id' =>$this->website_id], 'account_type')['account_type'];
        if($account_type == 3){
            //先通过uid查出当前是哪个端口。
            $mall_port = 4;//pc
        }else{
            $mall_port = 0;
        }
        $mobile = request()->post('mobile');
        $verification_code = request()->post('mobile_code');
        $password = request()->post('password', ''); // by sgw
        if (empty($mobile)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        if ($verification_code != Session::get('mobileBindVerificationCode')) {//mobileVerificationCode
            return json(['code' => -1, 'message' => '验证码错误！']);
        }

        $condition['user_tel'] = $mobile;
        $condition['user_model.website_id'] = $this->website_id;
        $condition['is_member'] = 1;
        if($account_type == 3 && $mall_port){
            $condition['mall_port'] = $mall_port;
        }
        $del_user_info = $user_model->getInfo(['uid' => $uid], 'pcwx_open_id, wx_unionid');
        $user_info = $user_model::get($condition, ['member_info']);//shop注册
        if(isset($user_info) && !empty($user_info) ){
            if( !empty($user_info['wx_unionid']) ){
                $wx_unionid = $user_model->getInfo(['uid' => $uid])['wx_unionid'];
                if($wx_unionid == $user_info['wx_unionid']){//如果当前用户unionid 跟查到的unionid 不相等说明是两个不同的账号，不能认为绑定过。
                    return ['code' => -1, 'message' => '已有其它账号绑定过该手机号码'];
                }
            }
            if( !empty($user_info['pcwx_open_id']) ){//如果需要合并的账号微信open_id不为空，说明已经合并过了，不能再合并了。
                return ['code' => -1, 'message' => '已有其它账号绑定过该手机号码'];
            }
        }
        if ($user_info['user_status'] == USER_LOCK) {
            return json(AjaxReturn(USER_LOCK));
        }
        if (!empty($user_info)) {
            //迁移海报奖励
            //会员账户表
            $member_account = new VslMemberAccountModel();
            $member_account_records = new VslMemberAccountRecordsModel();
            $user_task = new UserTaskModel();
            $poster_record = new VslPosterRecordModel();
            $member_mdl = new VslMemberModel();
            $del_account_info = $member_account->getInfo(['uid' => $uid], '*');
            $real_account_info = $member_account->getInfo(['uid' => $user_info['uid']], '*');

            //成长值
            $del_growth = $member_model->getInfo(['uid' => $uid], 'growth_num')['growth_num'];
            $real_growth = $member_model->getInfo(['uid' => $user_info['uid']], 'growth_num')['growth_num'];

            //积分
            $account_data['point'] = $real_account_info['point'] + $del_account_info['point'];
            //余额
            $account_data['balance'] = $real_account_info['balance'] + $del_account_info['balance'];
            $member_account->save($account_data, ['uid' => $user_info['uid']]);
            //成长值
            $member_data['growth_num'] = $del_growth + $real_growth;
            $member_model->save($member_data, ['uid' => $user_info['uid']]);
            //余额流水
            $member_account_records->save(['uid'=>$user_info['uid']], ['uid' => $uid]);
            //任务
            $user_task->save(['uid'=>$user_info['uid']], ['uid' => $uid]);
            //海报扫描纪录
            $poster_record->save(['reco_uid'=>$user_info['uid']], ['reco_uid' => $uid]);
            $poster_record->save(['be_reco_uid'=>$user_info['uid']], ['be_reco_uid' => $uid]);
            //推荐人
            $member_mdl->save(['referee_id'=>$user_info['uid']], ['referee_id' => $uid]);
            $member_mdl->save(['recommend_id'=>$user_info['uid']], ['recommend_id' => $uid]);
            if(getAddons('giftvoucher', $this->website_id)){
                //礼品券
                $voucher_records = new VslGiftVoucherRecordsModel();
                $voucher_change_uid_data['uid'] = $user_info['uid'];
                $voucher_records->save($voucher_change_uid_data, ['uid' => $uid]);
            }
            if(getAddons('coupontype', $this->website_id)){
                //优惠券
                $coupon = new VslCouponModel();
                $coupon_change_uid_data['uid'] = $user_info['uid'];
                $coupon->save($coupon_change_uid_data, ['uid' => $uid]);
            }

            // 删除 之前unionid/openid 注册的账号
            $member->deleteMember($uid);
            switch($oauthWq['type']){
                case 1://微信
                    $open_id_arr['pcwx_open_id'] = $oauthWq['openid']?:$del_user_info['pcwx_open_id'];
                    break;
                case 2://qq
                    //关联已有的账号
                    $result = $member->bUserInfo(2, $user_info['uid'], '', $mobile, json_encode($oauthWq['data']), $oauthWq['unionid']);
                    if (!empty($result) && $result > 0) {
                        $user_info = $this->user->login($mobile, '', 1);
                        if (is_array($user_info)) {
                            Session::delete(['mobileVerificationCode', 'sendMobile']);
                            return json(['code' => 1, 'message' => '关联成功！', 'data' => ['user_token' => md5($user_info['uid'])]]);
                        } else {
                            return json(['code' => -1, 'message' => '关联失败！']);
                        }
                    }
                    break;
            }
            $open_id_arr['wx_unionid'] = $oauthWq['unionid']?:$del_user_info['wx_unionid'];
            $open_id_arr['nick_name'] = $user_info['nick_name'] ?: $oauthWq['data']['nickname'];
            $open_id_arr['user_headimg'] = $user_info['user_headimg'] ?: $oauthWq['data']['headimgurl'];
            $open_id_arr['sex'] = $user_info['sex'] ?: $oauthWq['data']['sex'];
            // 如果用户有就不覆盖
            $this->user->updateUserNew($open_id_arr, ['uid' => $user_info['uid']]);
            unset($condition);
            $condition['user_tel'] = $mobile;
            $condition['website_id'] = $this->website_id;
            $condition['is_member'] = 1;
            if($account_type == 3 && $mall_port){
                $condition['mall_port'] = $mall_port;
            }
            $this->user->wchatLoginNew($condition, $mall_port);
            $res = $member_model->save(['mobile' => $mobile], ['uid' => $user_info['uid']]);
            $data = [
                'user_token' => md5($user_info['uid']),
                'have_mobile' => true
            ];
            if($res){
                return json(['code' => 1, 'message' => '关联成功！', 'data' => $data]);
            }else{
                return json(['code' => -1, 'message' => '关联失败！']);
            }

        } else {
            //新增用户
            if ($oauthWq['type'] == 2) {
                //qq
                $result = $member->registerMember('', '', '', '', $mobile, $oauthWq['openid'], json_encode($oauthWq['data']), '', '', '');
            }
            if ($oauthWq['type'] == 1) {
                //wechat
                if (empty($password)) {
                    return json(AjaxReturn(LACK_OF_PARAMETER));
                }
                $update_data = [
                    'user_tel' => $mobile,
                    'user_password' => md5($password)
                ];
                unset($condition);
                $condition['uid'] = $uid;
                $condition['website_id'] = $this->website_id;
                $condition['is_member'] = 1;
                $this->user->updateUserNew($update_data, $condition);
                //注册营销
                if (getAddons('registermarketing', $this->website_id)) {
                    $registerMarketingServer = new RegisterMarketing();
                    $registerMarketingServer->deliveryAward($uid);
                }
                return json(['code' => 1, 'message' => '关联成功！']);
            }

            if (!empty($result) && $result > 0) {
                $user_info = $this->user->login($mobile, '', 1);
                if ($user_info == USER_LOCK) {
                    Session::delete(['mobileVerificationCode', 'sendMobile']);
                    return json(AjaxReturn(USER_LOCK));
                }
                if (is_array($user_info)) {
                    //注册营销
                    if (getAddons('registermarketing', $this->website_id)) {
                        $registerMarketingServer = new RegisterMarketing();
                        $registerMarketingServer->deliveryAward($user_info['uid']);
                    }

                    return json(['code' => 1, 'message' => '关联成功！']);
                }
            }

            return json(['code' => -1, 'message' => '关联失败！']);
        }
    }

}



