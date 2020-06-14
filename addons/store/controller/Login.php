<?php

namespace addons\store\controller;

use addons\store\Store as baseStore;
use addons\store\server\Store as storeServer;
use \think\Session as Session;
use data\service\BaseService;
use think\Validate;
use data\extend\WchatOauth;
use data\model\ConfigModel;
use data\extend\ThinkOauth as ThinkOauth;
use think\Db;
use think\Cookie;

/**
 * o2o门店控制器
 * Class GoodHelper
 * @package addons\store\controller
 */
class Login extends baseStore {

    public $store;
    protected $config;

    public function __construct() {
        parent::__construct();
        $base = new BaseService();
        $model = $base->getRequestModel();
        $website_id = checkUrl();
        if ($website_id && is_numeric($website_id)) {
            Session::set($model . 'website_id', $website_id);
            $this->website_id = $website_id;
        } elseif (Session::get($model . 'website_id')) {
            $this->website_id = Session::get($model . 'website_id');
        } else {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        
        
        if (!isApiLegal()) {
            
            $data['code'] = -2;
            $data['message'] = '接口签名错误';
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->store = new storeServer();
        $config_array = ['MOBILEMESSAGE'];
        $config_server_condition['key'] = ['IN', $config_array];
        $config_server_condition['website_id'] = $this->website_id;
        $config_server_condition['instance_id'] = 0;
        $config_model = new ConfigModel();
        $config_list = $config_model->getQuery($config_server_condition, '*', '');
        foreach ($config_list as $k => $v) {
            switch ($v['key']) {
                case 'MOBILEMESSAGE':
                    if ($v['is_use'] == 0) {
                        $this->config['mobile_verification'] = false;
                        break;
                    }
                    $this->config['mobile_verification'] = true;
                    break;
            }
        }
    }

    public function index() {
        //$bind_message_info = json_decode(Session::get("bind_message_info"), true);
        $password = request()->post('password', '');
        $mobile = request()->post('account', '');
        $verification_code = request()->post('verification_code');
        if (empty($mobile) || (empty($verification_code) && empty($password))) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }

        if (!empty(Session::get('sendMobile')) && $mobile != Session::get('sendMobile')) {
            return json(['code' => -1, 'message' => '手机已更改请重新获取验证码']);
        }
        if ($verification_code && $verification_code != Session::get('mobileVerificationCode')) {
            return json(['code' => -1, 'message' => '手机验证码错误']);
        }
        if ($password) {
            $result = $this->store->login($mobile, $password);
        } else {
            $result = $this->store->login($mobile, '');
        }

        if (is_array($result)) {
            Session::delete(['sendMobile', 'mobileVerificationCode']);
            return json([
                'code' => 1,
                'message' => '登陆成功',
                'data' => [
                    'user_token' => md5($result['assistant_id']),
                    'assistant_name' => $result['assistant_name'],
                    'assistant_headimg' => getApiSrc($result['assistant_headimg']),
                ]
            ]);
        } else {
            return json(AjaxReturn($result));
        }
    }

    /**
     * 忘记密码
     */
    public function resetPassword() {
        $mobile = request()->post('mobile');
        $verification_code = request()->post('verification_code');
        $password = request()->post('password');

        if (empty($mobile) || empty($password)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        if ($mobile != Session::get('sendMobile')) {
            return json(['code' => -1, 'message' => '手机已更改请重新获取验证码']);
        }
        if ($verification_code != Session::get('mobileVerificationCode')) {
            return json(['code' => -1, 'message' => '手机验证码错误']);
        }
        $data['password'] = md5($password);

        $condition['assistant_tel'] = $mobile;
        $condition['website_id'] = $this->website_id;
        $result = $this->store->updateAssistantFiled($data, $condition);
        if ($result > 0) {
            Session::delete('send_mobile_verification_times, sendMobile');
            return json(['code' => 1, 'message' => '修改成功']);
        } else {
            return json(['code' => -1, 'message' => '修改失败']);
        }
    }

    public function logout() {
        $this->store->logout();
        return json(['code' => 1, 'message' => '已退出登陆']);
    }

    /**
     * 获取手机短信验证码
     */
    public function getVerificationCode() {
        if (!$this->config['mobile_verification']) {
            return json(['code' => -1, 'message' => '商城未开启短信模版']);
        }
        $mobile = request()->post('mobile', '');

        $send_mobile_verification_times = Session::get('send_mobile_verification_times') ?: 0;
        if ($send_mobile_verification_times >= 3) {
            return json(['code' => -1, 'message' => "验证码错误"]);
        } else {
            $params['mobile'] = $mobile;
            $params['shop_id'] = 0;
            $params['website_id'] = $this->website_id;
            $result = runhook('Notify', 'assistantCode', $params);
        }
        if (!empty($result) && !empty($result['param'])) {
            $expire = 5 * 60;
            Session::set('mobileVerificationCode', $result['param'], $expire);
            Session::set('sendMobile', $mobile, $expire);
            Session::set('send_mobile_verification_times', ++$send_mobile_verification_times, $expire);
            $code = ($send_mobile_verification_times >= 3) ? 0 : 1;
            return json(['code' => $code, 'message' => '发送成功']);
        } elseif (isset($result['code']) && isset($result['message']) && $result['code'] == -1) {
            return json(['code' => -1, 'message' => $result['message']]);
        } else {
            return json(['code' => -1, 'message' => '发送失败']);
        }
    }

    /**
     * wap第三方登录
     */
    public function oauthLogin() {
        $base = new BaseService();
        $model = $base->getRequestModel();
        $type = request()->post('type') ?: Session::get('oauth_login_type');


        if (isWeixin() && !empty($_SERVER['HTTP_USER_TOKEN'])) {
            // 让微信环境下每次进入商城不请求微信的授权登陆，先使用常规登陆
            $user_token = $_SERVER['HTTP_USER_TOKEN'];
            $result = $this->store->loginByUserToken($user_token);
            if ($result == USER_NBUND) {
                return json(['code' => 2, 'message' => '请绑定手机']);
            }
            if ($result['assistant_id']) {
                $data['user_token'] = md5(Session::get($model . 'assistant_id'));
                if (!Session::has('oauthWq')) {
                    $oa_data['data'] = [];
                    $oa_data['openid'] = $result['wx_openid'];
                    $oa_data['nickname'] = $result['assistant_name'];
                    $oa_data['headimgurl'] = $result['assistant_headimg'];
                    Session::set('oauthWq', $oa_data);
                }
                return json(['code' => 1, 'message' => '登陆成功', 'data' => $data]);
            }
        }
        Session::set('oauth_login_type', $type);
        
        if ($type == 'WCHAT') {
            if (isWeixin()) {
//              微信环境 微信登陆流程
                $result = $this->wchatLogin();
                
                if ($result['code'] == -9999) {
                    return json(['code' => 4, 'message' => '配置有效', 'data' => ['url' => $result['url']]]);
                }

                $domain_name = \think\Request::instance()->domain();
                $token = json_decode($_COOKIE[$domain_name . "member_access_token_clerk"], true);
                $wchat_oauth = new WchatOauth($this->website_id);
                $wechat_user_info = $wchat_oauth->get_fans_info($token['openid'], $this->website_id);
                $oa_data['data'] = [];
                $oa_data['openid'] = $token['openid'];
                $oa_data['nickname'] = $wechat_user_info['nickname'];
                $oa_data['headimgurl'] = $wechat_user_info['headimgurl'];
//                Db::table('sys_log')->insert(['content' => json_encode($oa_data)]);
                if ($result['code'] == 1) {
                    $this->store->updateAssistantFiled([
                        'assistant_headimg' => $oa_data['headimgurl'],
                        'user_token' => md5($result['assistant_id'])
                            ], ['assistant_id' => $result['uid']]);
                    Session::set('oauthWq', $oa_data);
                    return json(['code' => 1, 'message' => '登陆成功', 'data' => ['user_token' => md5($result['uid'])]]);
                }
                if ($result['code'] == USER_NBUND) {
                    Session::set('oauthWq', $oa_data);
                    return json(['code' => 2, 'message' => '请绑定手机']);
                }
                if ($result['code'] == USER_LOCK) {
                    Session::set('oauthWq', $oa_data);
                    return json(['code' => 3, 'message' => '店员账号已被禁用']);
                }
            }
        } else {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $_SESSION['login_type'] = $type;
        $test = ThinkOauth::getInstance($type, null, $this->website_id);
        return json(['code' => 4, 'message' => '配置有效', 'data' => ['url' => $test->getRequestCodeURL($this->website_id)]]);
    }

    /**
     * 检测是否存在unionid or openid
     */
    public function wchatLogin() {

        $domain_name = \think\Request::instance()->domain();
        
        if (!empty($_COOKIE[$domain_name . "member_access_token_clerk"])) {
            $token = json_decode($_COOKIE[$domain_name . "member_access_token_clerk"], true);
        } else {
            $wchat_oauth = new WchatOauth($this->website_id);
            $token = $wchat_oauth->get_member_access_token_clerk();
            
            
            if (!empty($token['access_token'])) {
//                $_COOKIE($domain_name . "member_access_token", json_encode($token));
                Cookie::set($domain_name . "member_access_token_clerk", json_encode($token));
            } else {
                // return get code url
                return ['code' => -9999, 'url' => $token];
            }
        }
        $uid = $this->store->getAssistantId();
        if (!empty($token['openid'])) {
                $retval = $this->store->wchatLogin($token['openid']);
                if ($uid) {
                    // 绑定账号时已登录，直接更新unionid和openid
                    $this->store->updateAssistantFiled(['wx_openid' => $token['openid']], ['assistant_id' => $uid]);
                    return ['code' => 1, 'uid' => $uid];
                }
                if ($retval > 0) {
                    return ['code' => 1, 'uid' => $retval];
                } else if ($retval == USER_NBUND) {
                    return ['code' => USER_NBUND];
                } elseif ($retval == USER_LOCK) {
                    return ['code' => USER_LOCK];
                }
            
        }
    }
    
    /**
     * 关联账号
     */
    public function AssociateAccount()
    {
        $mobile = request()->post('mobile');
        $verification_code = request()->post('verification_code');

        if (empty($mobile)) {
            return json(['code' => -1, 'message' => '参数错误']);
        }
        if ($mobile != Session::get('sendMobile')) {
            return json(['code' => -1, 'message' => '手机已更改请重新获取验证码']);
        }
        if ($verification_code != Session::get('mobileVerificationCode')) {
            return json(['code' => -1, 'message' => '手机验证码错误']);
        }
        $oauthWq = Session::get('oauthWq');
        $condition['assistant_tel'] = $mobile;
        $condition['website_id'] = $this->website_id;
        $user_info = $this->store->getAssistantInfo($condition);
        if(!$user_info){
            return json(['code' => -1, 'message' => '账号不存在']);
        }
        if (!$user_info['status']) {
            return json(['code' => -1, 'message' => '店员账号已被禁用，无法进行账号关联']);
        }
        if($user_info['wx_openid']){
            return json(['code' => -1, 'message' => '账号已关联其他微信']);
        }
        //wechat
        $result = $this->store->updateAssistantFiled([
            'wx_openid' => $oauthWq['openid'],
            'assistant_headimg' => $oauthWq['headimgurl']
        ], ['assistant_id' => $user_info['assistant_id']]);
        if(!$result){
            return json(['code' => -1, 'message' => '关联账号失败']);
        }
        $login = $this->store->login($mobile);
        if($login == USER_LOCK){
            return json(['code' => -1, 'message' => '店员账号已被禁用，无法进行账号关联']);
        }
        if($login == USER_ERROR){
            return json(['code' => -1, 'message' => '关联账号失败']);
        }
        return json(['code' => 1, 'message' => '关联账号成功', 'data' => ['user_token' => md5($user_info['assistant_id'])]]);
    }
    
    /**
     * 获取验证码前判断手机号是否可以绑定
     */
    public function checkMobileCanBund()
    {
        $mobile = request()->post('mobile');
        if (empty($mobile)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $condition['assistant_tel'] = $mobile;
        $condition['website_id'] = $this->website_id;
        $user_info = $this->store->getAssistantInfo($condition);
        if(!$user_info){
            return json(['code' => -1, 'message' => '店员账号不存在,无法进行账号关联']);
        }
        if($user_info['wx_openid']){
            return json(['code' => -2, 'message' => '账号已关联其他微信']);
        }
        if (!$user_info['status']) {
            return json(['code' => -3, 'message' => '店员账号已被禁用，无法进行账号关联']);
        }
        return json(['code' => 1, 'message' => '账号可以绑定']);
    }

    /**
     * qq,wechat登录回调
     */
    public function callback()
    {
        $base = new BaseService();
        $model = $base->getRequestModel();
        $code = request()->get('code', '');
        if (empty($code)) {
            if (request()->isMobile()) {
                $this->redirect('/clerk');
            }
            die();
        }
        if ($_SESSION['login_type'] == 'WCHAT') {

            $wchat = ThinkOauth::getInstance('WCHAT', null, $this->website_id);
            $token = $wchat->getAccessToken($code, $this->website_id);
            if (!empty($token['openid'])) {
                $retval = $this->store->loginByOpenid($token['openid']);
                // 已经绑定
                if ($retval > 1) {
                    if (request()->isMobile()) {
                        $user_token = md5(Session::get($model . 'uid'));
                        $this->redirect('/clerk/pages/login/author?user_token=' . $user_token);
                    }
                    if (!empty($_SESSION['login_pre_url'])) {
                        $this->redirect($_SESSION['login_pre_url']);
                    } else {
                        $redirect = __URL(__URL__ . '/member/memberCenter');
                        $this->redirect($redirect);
                    }
                }
                if ($retval == USER_NBUND) {
                    // 2.绑定操作
                    $wchat = ThinkOauth::getInstance('WCHAT', $token, $this->website_id);
                    $data = $wchat->call('sns/userinfo');
                    $wxInfo['data'] = $data;
                    $wxInfo['type'] = 1;
                    if ($wxInfo['data']) {
                        Session::set('oauthWq', $wxInfo);
                    }
                    if (request()->isMobile()) {
                        //$this->redirect('/wap/login/author?code=' . $code);
                        $this->redirect('/clerk/bind');
                    }
                }
            }

        }
    }
}
