<?php
namespace app\shop\controller;

use data\service\Config as Config;
use data\service\Member as MemberService;
use think\Session;
use data\model\ConfigModel;
/*
 * 组件控制器
 */
class Components extends BaseController
{
    // 验证码配置
    public $login_verify_code;
    // 通知配置
    public $notice;

    public function __construct()
    {
        parent::__construct();
        // 是否开启验证码
        $instance_id = 0;
        $web_config = new Config();
        $this->login_verify_code = $web_config->getLoginVerifyCodeConfig($instance_id);
        // 是否开启通知
        $noticeMobile = $web_config->getNoticeMobileConfig($instance_id);
        $noticeEmail = $web_config->getNoticeEmailConfig($instance_id);
        $this->notice['noticeEmail'] = $noticeEmail[0]['is_use'];
        $this->notice['noticeMobile'] = $noticeMobile[0]['is_use'];
        $this->assign("notice", $this->notice);
    }
    // 获取登录信息
    public function getLoginInfo()
    {
        $member_info = $this->user->getMemberDetail(); // 用户信息查询
        if (! empty($member_info['user_info']['user_headimg'])) {
            $member_info['member_img'] = $member_info['user_info']['user_headimg'];
        } elseif (! empty($member_info['user_info']['qq_openid'])) {
            $member_info['member_img'] = $member_info['user_info']['qq_info_array']['figureurl_qq_1'];
        } elseif (! empty($member_info['user_info']['wx_openid'])) {
            $member_info['member_img'] = '0';
        } elseif (! empty($member_info)) {
            $member_info['member_img'] = '0';
        }
        return $member_info;
    }

    /**
     * 收藏商品或者店铺
     */
    public function collectionGoodsOrShop()
    {
        $fav_id = request()->post('fav_id', '');
        $fav_type = request()->post('fav_type', '');
        $log_msg = request()->post('log_msg', '');
        $member = new MemberService();
        $result = $member->addMemberFavouites($fav_id, $fav_type, $log_msg);
        return AjaxReturn($result);
    }

    /**
     * 取消收藏 商品/店铺
     */
    public function cancelCollGoodsOrShop()
    {
        $fav_id = request()->post('fav_id', '');
        $fav_type = request()->post('fav_type', '');
        $member = new MemberService();
        $result = $member->deleteMemberFavorites($fav_id, $fav_type);
        return AjaxReturn($result);
    }

    /**
     * 手机验证码发送
     *
     * @return number
     */
    public function mobileVerificationCode()
    {
        $mobile = request()->post('mobile', '');
        $vertification = request()->post('vertification', '');
        $member = new MemberService();
        $is_bin_mobile = $member->memberIsMobile($mobile); // 判断手机号是否已被绑定
        /*
         * if ($is_bin_mobile) {
         * return array(
         * 'code' => 0,
         * 'message' => '该手机号已被绑定'
         * );
         * }
         */
        
        /*
         * $code = rand(100000, 999999);
         * $time = '60秒';
         * Session::set('mobileVerificationCode', $code);
         * $sen = new Send();
         * $result = $sen->sms([
         * 'param' => [
         * 'code' => (string) $code,
         * 'time' => $time
         * ],
         * 'mobile' => $mobile,
         * 'template' => 'SMS_43210099'
         * ]);
         * if ($result !== true) {
         * return AjaxReturn(0);
         * }
         * return array(
         * 'code' => 1,
         * 'time' => $time
         * );
         */
        if ($this->login_verify_code["value"]["pc"] == 1) {
            if (! captcha_check($vertification)) {
                $result = [
                    'code' => - 1,
                    'message' => "验证码错误"
                ];
            } else 
                if ($is_bin_mobile) {
                    $result = [
                        'code' => - 1,
                        'message' => '该手机号已被绑定'
                    ];
                } else {
                    $params['mobile'] = $mobile;
                    $params['shop_id'] = 0;
                    $params['website_id'] = $this->website_id;
                    $params["user_id"] = $this->uid;
                    $hook = runhook('Notify', 'bindMobileBySms', $params);
                    
                    if (! empty($hook) && ! empty($hook['param'])) {
                        
                        $result = [
                            'code' => 0,
                            'message' => '发送成功'
                        ];
                    } else {
                        
                        $result = [
                            'code' => - 1,
                            'message' => '发送失败'
                        ];
                    }
                    Session::set('mobileVerificationCode', $hook['param'],300);
                }
            return $result;
        } else {
            if ($is_bin_mobile) {
                $result = [
                    'code' => - 1,
                    'message' => '该手机号已被绑定'
                ];
            } else {
                $params['mobile'] = $mobile;
                $params['shop_id'] = 0;
                $params["user_id"] = $this->uid;
                $params['website_id'] = $this->website_id;
                $hook = runhook('Notify', 'bindMobileBySms', $params);
                
                if (! empty($hook) && ! empty($hook['param'])) {
                    
                    $result = [
                        'code' => 0,
                        'message' => '发送成功'
                    ];
                } else {
                    
                    $result = [
                        'code' => - 1,
                        'message' => '发送失败'
                    ];
                }
                Session::set('mobileVerificationCode', $hook['param'],300);
            }
            return $result;
        }
    }
}