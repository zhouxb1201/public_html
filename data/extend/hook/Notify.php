<?php

namespace data\extend\hook;

use addons\channel\model\VslChannelOrderGoodsModel;
use addons\channel\model\VslChannelOrderModel;
use addons\channel\model\VslChannelOrderPaymentModel;
use addons\distribution\model\SysMessagePushModel;
use addons\miniprogram\model\MpTemplateRelationModel;
use addons\miniprogram\model\WeixinAuthModel;
use data\extend\WchatOauth;
use data\extend\WchatOpen;
use data\model\VslMemberAccountModel;
use data\model\WebSiteModel;
use data\model\UserModel;
use data\model\ConfigModel;
use data\extend\Send;
use data\model\NoticeTemplateModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderModel;
use data\service\Member\MemberAccount;
use data\service\Order as orderService;
use addons\shop\service\Shop;
use data\model\NoticeTemplateTypeModel;
use data\model\VslOrderPaymentModel;
use data\model\WeixinInstanceMsgModel;
use data\service\SendMessageJd;
use data\model\MessageCountModel;
use data\service\WebSite;
use think\Request;
use think\Session;
use data\service\Member as memberService;
use data\model\WeixinFansModel;
class Notify {

    public $result = array(
        "code" => 0,
        "message" => "success",
        "param" => ""
    );

    /**
     * 邮件的配置信息
     * @var unknown
     */
    public $email_is_open = 0;
    public $email_host = "";
    public $email_port = "";
    public $email_addr = "";
    public $email_id = "";
    public $email_pass = "";
    public $email_is_security = false;

    /**
     * 短信的配置信息
     * @var unknown
     */
    public $mobile_is_open;
    public $appKey = "";
    public $secretKey = "";
    public $freeSignName = "";
    public $shop_name;
    public $ali_use_type = 2;
    public $userid = "";
    public $username = "";
    public $password = "";
    public $register_message = "";
    public $count = 0; //短信余额
    public $http = "http://";
    public $mobile_type = 0;//账号体系设置手机类型
    public $int_sign_name = 0;//国际短信签名
    public $international = 0;//国际短信
    public function __construct() {
        $is_ssl = \think\Request::instance()->isSsl();
        $this->http = "http://";
        if($is_ssl){
            $this->http = 'https://';
        }
    }
    /**
     * 得到系统通知的配置信息
     * @param unknown $shop_id
     */

    private function getShopNotifyInfo($shop_id, $website_id) {

        $website_model = new WebSiteModel();
        $website_obj = $website_model->getInfo("website_id=" . $website_id, "mall_name,mobile_type");
        if (empty($website_obj)) {
            $this->shop_name = "微商来商城";
        } else {
            $this->mobile_type = $website_obj['mobile_type'];
            $this->shop_name = $website_obj["mall_name"];
        }

        $config_model = new ConfigModel();
        #查看邮箱是否开启
        $email_info = $config_model->getInfo(["instance_id" => $shop_id, "website_id" => $website_id, "`key`" => "EMAILMESSAGE"], "*");
        if (!empty($email_info)) {
            $this->email_is_open = $email_info["is_use"];
            $value = $email_info["value"];
            if (!empty($value)) {
                $email_array = json_decode($value, true);
                $this->email_host = $email_array["email_host"];
                $this->email_port = $email_array["email_port"];
                $this->email_addr = $email_array["email_addr"];
                $this->email_id = $email_array["email_id"];
                $this->email_pass = $email_array["email_pass"];
                $this->email_is_security = $email_array["email_is_security"];
            }
        }
        $mobile_info = $config_model->getInfo(["instance_id" => $shop_id, "website_id" => $website_id, "`key`" => "MOBILEMESSAGE"], "*");
        if (!empty($mobile_info)) {
            $this->mobile_is_open = $mobile_info["is_use"];
            $value = $mobile_info["value"];
            if (!empty($value)) {
                $mobile_array = json_decode($value, true);
                $this->appKey = $mobile_array["appKey"];
                $this->secretKey = $mobile_array["secretKey"];
                $this->freeSignName = $mobile_array["freeSignName"];
                $this->ali_use_type = $mobile_array["user_type"];
                $this->int_sign_name = $mobile_array["int_sign_name"];
                $this->international = $mobile_array["international"];
                if (empty($this->ali_use_type)) {
                    $this->ali_use_type = 2;
                }
            }
        }
        $master_info = $config_model->getInfo(["instance_id" => 0, "website_id" => 0, "`key`" => "MOBILEMESSAGE"], "*");//a端短信配置
        if (!empty($master_info)) {
            $value_master = $master_info["value"];
            if (!empty($value_master)) {
                $master_array = json_decode($value_master, true);
                $this->userid = $master_array["userid"];
                $this->username = $master_array["username"];
                $this->password = $master_array["password"];
            }
        }
        $messageCount = new MessageCountModel();
        $count = $messageCount->getInfo(['website_id' => $website_id], 'count');
        $this->count = $count ? $count['count'] : 0;
    }

    /**
     * 查询模板的信息
     * @param unknown $shop_id
     * @param unknown $template_code
     * @param unknown $type
     * @return unknown
     */
    private function getTemplateDetail($shop_id, $website_id, $template_code, $type, $notify_type = "user") {
        $template_model = new NoticeTemplateModel();
        $template_obj = $template_model->getInfo(["instance_id" => $shop_id, "website_id" => $website_id, "template_type" => $type, "template_code" => $template_code, "notify_type" => $notify_type]);
        return $template_obj;
    }

    /**
     * 查询模板的类型
     * @param unknown $template_code
     * @return unknown
     */
    private function getTemplateType($template_code) {
        $template_model = new NoticeTemplateTypeModel();
        $template_type = $template_model->getInfo(["template_code" => $template_code]);
        return $template_type;
    }

    /**
     * 处理阿里大于 的返回数据
     * @param unknown $result
     */
    private function dealAliSmsResult($result) {
        $deal_result = array();
        try {
            if ($this->ali_use_type == 0) {
                #旧用户发送
                if (!empty($result)) {
                    if (!isset($result->result)) {
                        $result = json_decode(json_encode($result), true);
                        #发送失败
                        $deal_result["code"] = $result["code"];
                        $deal_result["message"] = $result["msg"];
                    } else {
                        #发送成功
                        $deal_result["code"] = 0;
                        $deal_result["message"] = "发送成功";
                    }
                }
            } else {
                #新用户发送
                if (!empty($result)) {
                    if ($result->Code == "OK") {
                        #发送成功
                        $deal_result["code"] = 0;
                        $deal_result["message"] = "发送成功";
                    } else {
                        #发送失败
                        $deal_result["code"] = -1;
                        $deal_result["message"] = $result->Message;
                    }
                }
            }
        } catch (\Exception $e) {
            $deal_result["code"] = -1;
            $deal_result["message"] = "发送失败!";
        }

        return $deal_result;
    }

    /**
     * 注册短信验证
     * @param string $params
     */
    public function registerBefore($params = null) {
        $rand = rand(100000, 999999);
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "code" => $rand . "",
            "country_code" => $params['country_code']
        );
        return $this->sendMessage('register_validate', $mobile, $sms_params, $shop_id, $website_id);
    }

    /*
     * 短信发送公用方法
     */

    public function sendMessage($messageType = '', $mobile = '', $params = array(), $shop_id = 0, $website_id = 0) {
        if (!$messageType) {
            $this->result["code"] = -1;
            $this->result["message"] = "参数有误!";
            return $this->result;
        }
        if(substr($mobile , 0 , 6) == '157564'){
            $this->result["code"] = -1;
            $this->result["message"] = "号码有误!";
            return $this->result;
        }
        //非商城会员默认大陆手机号
        if(in_array($messageType, ['merchant_forgot_password','anti_forgot_password','merchant_register_validate','merchant_register_success','assistant_code','qlkefu_register_validate','send_password'])){
            $this->mobile_type = 0;
        }
        $this->getShopNotifyInfo($shop_id, $website_id);
        $userModel = new UserModel();
        $countryCode = $params['country_code'];
        if(!is_array($mobile) && !$countryCode){
            $countryCode = $userModel->getInfo(['website_id' => $website_id, 'is_member' => 1, 'user_tel' => $mobile],'country_code')['country_code'];
        }
		debugLog($countryCode.$mobile,'短信模板2==>');
        if ($this->ali_use_type == 2 && ($countryCode == '86' || $countryCode == '')) {
            return $this->sendMessageByJd($messageType, $mobile, $params, $shop_id, $website_id);
        }
        if (!$this->mobile_is_open) {
            $this->result["code"] = -1;
            $this->result["message"] = "店家没有开启短信验证";
            return $this->result;
        }
        $template_type = $this->getTemplateType($messageType);
        $template_obj = $this->getTemplateDetail($shop_id, $website_id, $messageType, "sms", $template_type['notify_type']);
        if (!$template_obj || !$template_obj["is_enable"]) {
            $this->result["code"] = -1;
            $this->result["message"] = "商家没开启短信模板!";
            return $this->result;
        }
        if (!$this->appKey || !$this->secretKey || ($this->international?!$template_obj["int_template_title"]:!$template_obj["template_title"])) {
            $this->result["code"] = -1;
            $this->result["message"] = "短信配置信息有误!";
            return $this->result;
        }
        
        
        $sms_params = '';
        switch ($messageType) {
            case 'after_register':
                $sms_params = array(
                    "shopname" => $this->shop_name,
                    "username" => $params['username']
                );
                break;
            case 'register_validate':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'recharge_success':
                $sms_params = array(
                    "time" => $params['time'],
                    "money" => $params['money']
                );
                break;
            case 'confirm_order':
                $sms_params = array(
                    "username" => $params['username']
                );
                break;
            case 'pay_success':
                $sms_params = array(
                    "username" => $params['username']
                );
                break;
            case 'create_order':
                $sms_params = array(
                    "username" => $params['username'],
                    "ordermoney" => $params['ordermoney'],
                    "timeout" => $params['timeout'],
                );
                break;
            case 'order_deliver':
                $sms_params = array(
                    "username" => $params['username'],
                    "orderno" => $params['orderno']
                );
                break;
            case 'forgot_password':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'bind_mobile':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'bind_email':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'refund_order':
                break;
            case 'order_remind':
                $sms_params = array(
                    "ordermoney" => $params['ordermoney']
                );
                break;
            case 'login_validate':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'change_pay_password':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'withdrawal_success':
                $sms_params = array(
                    "takeoutmoney" => $params['takeoutmoney']
                );
                break;
            case 'withdrawal_fail':
                $sms_params = array(
                    "refusal" => $params['refusal']
                );
                break;
            case 'cancel_order':
                $sms_params = array(
                    "username" => $params['username'],
                    "orderno" => $params['orderno']
                );
                break;
            case 'agree_return':
                $sms_params = array(
                    "username" => $params['username'],
                    "orderno" => $params['orderno']
                );
                break;
            case 'agree_refund':
                $sms_params = array(
                    "username" => $params['username'],
                    "orderno" => $params['orderno'],
                    "refundmoney" => $params['refundmoney']
                );
                break;
            case 'refuse_return':
                $sms_params = array(
                    "username" => $params['username'],
                    "orderno" => $params['orderno'],
                    "refusal" => $params['refusal']
                );
                break;
            case 'refuse_refund':
                $sms_params = array(
                    "username" => $params['username'],
                    "orderno" => $params['orderno'],
                    "refusal" => $params['refusal']
                );
                break;
            case 'return_success':
                $sms_params = array(
                    "username" => $params['username'],
                    "orderno" => $params['orderno'],
                    "refundmoney" => $params['refundmoney']
                );
                break;
            case 'change_password':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'merchant_register_validate':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'merchant_forgot_password':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'merchant_register_success':
                break;
            case 'assistant_code':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
            case 'qlkefu_register_validate':
                $sms_params = array(
                "code" => $params['code']
                );
                break;
            case 'anti_forgot_password':
                $sms_params = array(
                    "code" => $params['code']
                );
                break;
	    case 'send_password':
                $sms_params = array(
                "password" => $params['password']
                );
                break;
        }
        if (!$mobile) {
            $this->result["code"] = -1;
            $this->result["message"] = "参数有误!";
            return $this->result;
        }
        
        if (is_array($mobile)) {
            foreach ($mobile as $v) {
                $countryCode = $userModel->getInfo(['website_id' => $website_id, 'is_member' => 1, 'user_tel' => $v],'country_code')['country_code'];
                if($this->mobile_type && $this->international && $countryCode && $countryCode != '86'){
                    $result = aliSmsSend($this->appKey, $this->secretKey, $this->int_sign_name, json_encode($sms_params), $countryCode.$v, $template_obj["int_template_title"], $this->ali_use_type);
                }else{
                    $result = aliSmsSend($this->appKey, $this->secretKey, $this->freeSignName, json_encode($sms_params), $v, $template_obj["template_title"], $this->ali_use_type);
                }
                
            }
        } else {
            $countryCode = $params['country_code'];
            if(!$countryCode){
                $countryCode = $userModel->getInfo(['website_id' => $website_id, 'is_member' => 1, 'user_tel' => $mobile],'country_code')['country_code'];
            }
			debugLog($countryCode.$mobile,'短信模板3');
            if($this->mobile_type && $this->international && $countryCode && $countryCode != '86'){
				debugLog($countryCode.$mobile.$template_obj["int_template_title"],'短信模板4');
                $result = aliSmsSend($this->appKey, $this->secretKey, $this->int_sign_name, json_encode($sms_params), $countryCode.$mobile, $template_obj["int_template_title"], $this->ali_use_type);
            }else{
				debugLog($countryCode.$mobile.$template_obj["template_title"],'短信模板5');
                $result = aliSmsSend($this->appKey, $this->secretKey, $this->freeSignName, json_encode($sms_params), $mobile, $template_obj["template_title"], $this->ali_use_type);
            }
            
        }

        $this->result["code"] = $result->Code;
        $this->result["message"] = $result->Message;
        if ($params['code']) {
            $this->result["param"] = $params['code'];
        }
        return $this->result;
    }

    /*
     * 短信发送公用方法(京东万象)
     */

    public function sendMessageByJd($messageType = '', $mobile = '', $params = array(), $shop_id = 0, $website_id = 0) {
        if (!$messageType) {
            $this->result["code"] = -1;
            $this->result["message"] = "参数有误!";
            return $this->result;
        }
        if(substr($mobile , 0 , 6) == '157564'){
            $this->result["code"] = -1;
            $this->result["message"] = "号码有误!";
            return $this->result;
        }
        $this->getShopNotifyInfo($shop_id, $website_id);
        if (!$this->mobile_is_open) {
            $this->result["code"] = -1;
            $this->result["message"] = "店家没有开启短信验证";
            return $this->result;
        }
        $template_type = $this->getTemplateType($messageType);
        $template_obj = $this->getTemplateDetail($shop_id, $website_id, $messageType, "sms", $template_type['notify_type']);
        if (!$template_obj || !$template_obj["is_enable"]) {
            $this->result["code"] = -1;
            $this->result["message"] = "商家没开启短信模板!";
            return $this->result;
        }
        if (!$this->userid || !$this->username || !$this->password) {
            $this->result["code"] = -1;
            $this->result["message"] = "短信配置信息有误,请联系平台!";
            return $this->result;
        }
        if($website_id && !$this->count){
            $this->result["code"] = -1;
            $this->result["message"] = "短信余额不足，请及时充值!";
            return $this->result;
        }
        $sms_params = '';
        $sign_master = 0;//是否使用平台签名
        switch ($messageType) {
            case 'after_register':
                $sms_params = str_replace('${shopname}', $this->shop_name, $template_obj['template_content']);
                $sms_params = str_replace('${username}', $params['username'], $sms_params);
                break;
            case 'register_validate':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
            case 'recharge_success':
                $sms_params = str_replace('${time}', $params['time'], $template_obj['template_content']);
                $sms_params = str_replace('${money}', $params['money'], $sms_params);
                break;
            case 'confirm_order':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                break;
            case 'pay_success':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                break;
            case 'create_order':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                $sms_params = str_replace('${ordermoney}', $params['ordermoney'], $sms_params);
                $sms_params = str_replace('${timeout}', $params['timeout'], $sms_params);
                break;
            case 'order_deliver':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                $sms_params = str_replace('${orderno}', $params['orderno'], $sms_params);
                break;
            case 'forgot_password':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
            case 'bind_mobile':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
            case 'bind_email':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
            case 'refund_order':
                $sms_params = $template_obj['template_content'];
                break;
            case 'order_remind':
                $sms_params = str_replace('${ordermoney}', $params['ordermoney'], $template_obj['template_content']);
                break;
            case 'login_validate':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
            case 'change_pay_password':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
            case 'withdrawal_success':
                $sms_params = str_replace('${takeoutmoney}', $params['takeoutmoney'], $template_obj['template_content']);
                break;
            case 'withdrawal_fail':
                $sms_params = str_replace('${refusal}', $params['refusal'], $template_obj['template_content']);
                break;
            case 'cancel_order':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                $sms_params = str_replace('${orderno}', $params['orderno'], $sms_params);
                break;
            case 'agree_return':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                $sms_params = str_replace('${orderno}', $params['orderno'], $sms_params);
                break;
            case 'agree_refund':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                $sms_params = str_replace('${orderno}', $params['orderno'], $sms_params);
                $sms_params = str_replace('${refundmoney}', $params['refundmoney'], $sms_params);
                break;
            case 'refuse_return':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                $sms_params = str_replace('${orderno}', $params['orderno'], $sms_params);
                $sms_params = str_replace('${refusal}', $params['refusal'], $sms_params);
                break;
            case 'refuse_refund':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                $sms_params = str_replace('${orderno}', $params['orderno'], $sms_params);
                $sms_params = str_replace('${refusal}', $params['refusal'], $sms_params);
                break;
            case 'return_success':
                $sms_params = str_replace('${username}', $params['username'], $template_obj['template_content']);
                $sms_params = str_replace('${orderno}', $params['orderno'], $sms_params);
                $sms_params = str_replace('${refundmoney}', $params['refundmoney'], $sms_params);
                break;
            case 'change_password':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
            case 'merchant_register_validate':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                $sign_master = 1;
                break;
            case 'merchant_forgot_password':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                $sign_master = 1;
                break;
            case 'merchant_register_success':
                $sign_master = 1;
                $sms_params = $template_obj['template_content'];
                break;
            case 'assistant_code':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
            case 'qlkefu_register_validate':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
            case 'anti_forgot_password':
                $sms_params = str_replace('${code}', $params['code'], $template_obj['template_content']);
                break;
	    case 'send_password':
                $sms_params = str_replace('${password}', $params['password'], $template_obj['template_content']);
                break;
        }
        if (!$mobile) {
            $this->result["code"] = -1;
            $this->result["message"] = "参数有误!";
            return $this->result;
        }
        $send = new SendMessageJd();
        if (is_array($mobile)) {
            foreach ($mobile as $v) {
                $result = $send->sendsms($v, $sms_params, $website_id, $sign_master);
            }
        } else {
            $result = $send->sendsms($mobile, $sms_params, $website_id, $sign_master);
        }

        $this->result["code"] = $result['code'];
        $this->result["message"] = $result['message'];
        if ($params['code']) {
            $this->result["param"] = $params['code'];
        }
        return $this->result;
    }


    public function sendCustomMessage($params = null){ 
        
        $user = new UserModel();
        $user_info = $user->getInfo(['uid'=>$params['uid']]);
        $user_name = ($user_info ['nick_name'])?$user_info ['nick_name']:($user_info ['user_name']?$user_info ['user_name']:($user_info ['user_tel']?$user_info ['user_tel']:$user_info ['uid']));
        $open_id = $user_info['wx_openid'];
        $message = new SysMessagePushModel();
        $template_obj= $message->getInfo(['is_enable'=>1,'template_type'=>$params['messageType'],'website_id'=>$user_info['website_id']]);
        if($params['referee_id']){
            $referee_info = $user->getInfo(['uid'=>$params['referee_id']]);
            $referee_name = ($referee_info['nick_name'])?$referee_info['nick_name']:($referee_info['user_name']?$referee_info['user_name']:($referee_info['user_tel']?$referee_info['user_tel']:$referee_info['uid']));
            $open_id = $referee_info['wx_openid'];
        }
        if($params['order_id']){
            $order_model = new orderService();
            $order_obj = $order_model->getOrderMessage($params['order_id']);
        }
        //如果通知的下线用户名称为uid或者空 则尝试再次请求
        if($user_name == $user_info ['uid'] && $open_id){ 
            //通过openid换取 微信昵称
            $fans = new WeixinFansModel();
            $fans_info = $fans->getInfo(['openid'=>$open_id,'website_id'=>$user_info['website_id']]);
            if($fans_info && $fans_info['nickname']){
                $user_name = $fans_info['nickname'];
            }
            /*
            $user = new UserModel();
            $user_info = $user->getInfo(['uid'=>$params['uid']]);
            $user_name = ($user_info ['nick_name'])?$user_info ['nick_name']:($user_info ['user_name']?$user_info ['user_name']:($user_info ['user_tel']?$user_info ['user_tel']:$user_info ['uid']));
            */
        }
        
        $sms_params= '';
        if($template_obj){
            switch ($params['messageType']) {
                case 'apply_distributor':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${applytime}',date("Y-m-d H:i:s",$params['apply_time']),$sms_params);
                    break;
                case 'become_distributor':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${commissionratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${becometime}',date("Y-m-d H:i:s",$params['become_time']), $sms_params);
                    $sms_params = str_replace('${presentgrade}', $params['level_name'], $sms_params);
                    break;
                case 'new_offline':
                    $user_name = $params['nickname'] ? $params['nickname'] : $user_name;
                    $sms_params = str_replace('${nickname}', $referee_name, $template_obj['template_content']);
                    $sms_params = str_replace('${offlinenickname}', $user_name, $sms_params);
                    $sms_params = str_replace('${additionaltime}',date("Y-m-d H:i:s",$params['add_time']), $sms_params);
                    break;
                case 'subordinate_payment':
                    $sms_params = str_replace('${nickname}', $referee_name, $template_obj['template_content']);
                    $sms_params = str_replace('${offlinenickname}', $user_name, $sms_params);
                    $sms_params = str_replace('${orderno}', $order_obj['order_no'], $sms_params);
                    $sms_params = str_replace('${ordermoney}', $order_obj['order_money'], $sms_params);
                    $sms_params = str_replace('${goodsdetail}', $order_obj['goods_name'], $sms_params);
                    $sms_params = str_replace('${freezingcommission}', $params['freezing_commission'], $sms_params);
                    $sms_params = str_replace('${ordertime}',date("Y-m-d H:i:s",$order_obj['order_time']) , $sms_params);
                    break;
                case 'subordinate_order_fulfillment':
                    $sms_params = str_replace('${nickname}', $referee_name, $template_obj['template_content']);
                    $sms_params = str_replace('${offlinenickname}', $user_name, $sms_params);
                    $sms_params = str_replace('${orderno}', $order_obj['order_no'], $sms_params);
                    $sms_params = str_replace('${ordermoney}', $order_obj['order_money'], $sms_params);
                    $sms_params = str_replace('${goodsdetail}', $order_obj['goods_name'], $sms_params);
                    $sms_params = str_replace('${ordertime}', date("Y-m-d H:i:s",$order_obj['order_time']), $sms_params);
                    $sms_params = str_replace('${thawcommission}', $params['freezing_commission'], $sms_params);
                    break;
                case 'application_cash':
                    $sms_params = str_replace('${nickname}',$user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${withdrawtime}', date("Y-m-d H:i:s",$params['withdraw_time']), $sms_params);
                    $sms_params = str_replace('${withdrawmoney}', $params['withdraw_money'], $sms_params);
                    $sms_params = str_replace('${withdrawtype}', $params['withdraw_type'], $sms_params);
                    break;
                case 'cash_withdrawal':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${withdrawtime}', date("Y-m-d H:i:s",$params['withdraw_time']), $sms_params);
                    $sms_params = str_replace('${withdrawmoney}', $params['withdraw_money'], $sms_params);
                    $sms_params = str_replace('${withdrawtype}', $params['withdraw_type'], $sms_params);
                    $sms_params = str_replace('${handlestatus}', $params['handle_status'], $sms_params);
                    $sms_params = str_replace('${handletime}', date("Y-m-d H:i:s",$params['handle_time']), $sms_params);
                    break;
                case 'commission_payment':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${withdrawtime}', date("Y-m-d H:i:s",$params['withdraw_time']), $sms_params);
                    $sms_params = str_replace('${withdrawmoney}', $params['withdraw_money'], $sms_params);
                    $sms_params = str_replace('${withdrawtype}', $params['withdraw_type'], $sms_params);
                    break;
                case 'upgrade_notice':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${presentgrade}', $params['present_grade'], $sms_params);
                    $sms_params = str_replace('${primarygrade}', $params['primary_grade'], $sms_params);
                    $sms_params = str_replace('${commissionratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${upgradetime}', date("Y-m-d H:i:s",$params['upgrade_time']), $sms_params);
                    break;
                case 'down_notice':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${presentgrade}', $params['present_grade'], $sms_params);
                    $sms_params = str_replace('${primarygrade}', $params['primary_grade'], $sms_params);
                    $sms_params = str_replace('${commissionratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${downtime}',date("Y-m-d H:i:s",$params['down_time']), $sms_params);
                    $sms_params = str_replace('${downreason}', $params['down_reason'], $sms_params);
                    break;
                case 'apply_global':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${applytime}',date("Y-m-d H:i:s",$params['apply_time']) , $sms_params);
                    break;
                case 'apply_area':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${agentarea}', $params['agent_area'], $sms_params);
                    $sms_params = str_replace('${applytime}', date("Y-m-d H:i:s",$params['apply_time']), $sms_params);
                    break;
                case 'apply_team':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${applytime}', date("Y-m-d H:i:s",$params['apply_time']), $sms_params);
                    break;
                case 'become_global':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${becometime}', date("Y-m-d H:i:s",$params['become_time']), $sms_params);
                    $sms_params = str_replace('${presentgrade}', $params['level_name'], $sms_params);
                    break;
                case 'become_area':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${agentarea}', $params['agent_area'], $sms_params);
                    $sms_params = str_replace('${becometime}', date("Y-m-d H:i:s",$params['become_time']), $sms_params);
                    $sms_params = str_replace('${presentgrade}', $params['level_name'], $sms_params);
                    break;
                case 'become_team':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${becometime}', date("Y-m-d H:i:s",$params['become_time']), $sms_params);
                    $sms_params = str_replace('${presentgrade}', $params['level_name'], $sms_params);
                    break;
                case 'freezing_globalbonus':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${ordertime}', date("Y-m-d H:i:s",$params['order_time']), $sms_params);
                    $sms_params = str_replace('${bonusmoney}', $params['bonus_money'], $sms_params);
                    break;
                case 'freezing_areabonus':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${ordertime}', date("Y-m-d H:i:s",$params['order_time']), $sms_params);
                    $sms_params = str_replace('${bonusmoney}', $params['bonus_money'], $sms_params);
                    break;
                case 'freezing_teambonus':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${ordertime}', date("Y-m-d H:i:s",$params['order_time']), $sms_params);
                    $sms_params = str_replace('${bonusmoney}', $params['bonus_money'], $sms_params);
                    break;
                case 'globalbonus_payment':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${paytime}', date("Y-m-d H:i:s",$params['pay_time']), $sms_params);
                    $sms_params = str_replace('${bonusmoney}', $params['bonus_money'], $sms_params);
                    break;
                case 'areabonus_payment':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${paytime}', date("Y-m-d H:i:s",$params['pay_time']), $sms_params);
                    $sms_params = str_replace('${bonusmoney}', $params['bonus_money'], $sms_params);
                    break;
                case 'teambonus_payment':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${paytime}', date("Y-m-d H:i:s",$params['pay_time']), $sms_params);
                    $sms_params = str_replace('${bonusmoney}', $params['bonus_money'], $sms_params);
                    break;
                case 'global_upgrade_notice':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${presentgrade}', $params['present_grade'], $sms_params);
                    $sms_params = str_replace('${primarygrade}', $params['primary_grade'], $sms_params);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${upgradetime}', date("Y-m-d H:i:s",$params['upgrade_time']), $sms_params);
                    break;
                case 'global_down_notice':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${presentgrade}', $params['present_grade'], $sms_params);
                    $sms_params = str_replace('${primarygrade}', $params['primary_grade'], $sms_params);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${downtime}', date("Y-m-d H:i:s",$params['down_time']), $sms_params);
                    $sms_params = str_replace('${downreason}', $params['down_reason'], $sms_params);
                    break;
                case 'area_upgrade_notice':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${presentgrade}', $params['present_grade'], $sms_params);
                    $sms_params = str_replace('${primarygrade}', $params['primary_grade'], $sms_params);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${agentarea}', $params['agent_area'], $sms_params);
                    $sms_params = str_replace('${upgradetime}', date("Y-m-d H:i:s",$params['upgrade_time']), $sms_params);
                    break;
                case 'area_down_notice':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${presentgrade}', $params['present_grade'], $sms_params);
                    $sms_params = str_replace('${primarygrade}', $params['primary_grade'], $sms_params);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${downtime}', date("Y-m-d H:i:s",$params['down_time']), $sms_params);
                    $sms_params = str_replace('${agentarea}', $params['agent_area'], $sms_params);
                    $sms_params = str_replace('${downreason}', $params['down_reason'], $sms_params);
                    break;
                case 'team_upgrade_notice':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${presentgrade}', $params['present_grade'], $sms_params);
                    $sms_params = str_replace('${primarygrade}', $params['primary_grade'], $sms_params);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${upgradetime}',date("Y-m-d H:i:s",$params['upgrade_time']), $sms_params);
                    break;
                case 'team_down_notice':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${presentgrade}', $params['present_grade'], $sms_params);
                    $sms_params = str_replace('${primarygrade}', $params['primary_grade'], $sms_params);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${downtime}', date("Y-m-d H:i:s",$params['down_time']), $sms_params);
                    $sms_params = str_replace('${downreason}', $params['down_reason'], $sms_params);
                    break;
                case 'area_adjust_notice':
                    $sms_params = str_replace('${nickname}', $user_name, $template_obj['template_content']);
                    $sms_params = str_replace('${presentgrade}', $params['present_grade'], $sms_params);
                    $sms_params = str_replace('${primaryagentarea}', $params['primary_agentarea'], $sms_params);
                    $sms_params = str_replace('${presentagentarea}', $params['present_agentarea'], $sms_params);
                    $sms_params = str_replace('${bonusratio}', $params['ratio'], $sms_params);
                    $sms_params = str_replace('${adjusttime}', date("Y-m-d H:i:s",$params['adjust_time']), $sms_params);
                    break;
            }
            $weixin = new WchatOauth($user_info['website_id']);
            $res = $weixin->send_message($open_id,'text',$sms_params);
            
        }
    }
    /**
     * 找回密码短信验证码
     * @param string $params
     * @return multitype:number string
     */
    public function forgotPasswordBySms($params = null) {
        $send_param = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $rand = rand(100000, 999999);
        $smsParams = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('forgot_password', $send_param, $smsParams, $shop_id, $website_id);
    }
    /**
     * 商家找回密码短信验证码
     * @param string $params
     * @return multitype:number string
     */
    public function merchantForgotPasswordBySms($params = null) {
        $send_param = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $rand = rand(100000, 999999);
        $smsParams = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('merchant_forgot_password', $send_param, $smsParams, $shop_id, $website_id);
    }

    /**
     * 防伪溯源找回密码短信验证码
     * @param string $params
     * @return multitype:number string
     */
    public function antiForgotPasswordBySms($params = null) {
        $send_param = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $rand = rand(100000, 999999);
        $smsParams = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('anti_forgot_password', $send_param, $smsParams, $shop_id, $website_id);
    }

    /**
     * 用户绑定手机号验证码
     * @param string $params
     */
    public function bindMobileBySms($params = null) {
        $rand = rand(100000, 999999);
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "code" => $rand . "",
            "country_code" => $params['country_code']
        );
        return $this->sendMessage('bind_mobile', $mobile, $sms_params, $shop_id, $website_id);
    }

    /**
     * 订单创建成功发送短信
     * @param string $params
     */
    public function orderCreateBySms($params = null) {
        $order_id = $params["order_id"];
        $order_model = new VslOrderModel();
        $order_obj = $order_model->get($order_id);
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $user_name = $user_info["nick_name"] ? $user_info["nick_name"] : $user_info["user_name"];
        $user_tel = $user_info["user_tel"];
        $website_id = $order_obj["website_id"];
        $order_money = $order_obj["pay_money"];
        $sms_params = array(
            "ordermoney" => $order_money,
            "username" => $user_name,
            "timeout" => $params["time_out"]
        );
        return $this->sendMessage('create_order', $user_tel, $sms_params, 0, $website_id);
    }

    /**
     * 订单创建成功发送短信
     * @param string $params
     */
    public function channelOrderCreateBySms($params = null) {
        $order_id = $params["order_id"];
        $order_model = new VslChannelOrderModel();
        $order_obj = $order_model->get($order_id);
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $user_name = $user_info["nick_name"] ? $user_info["nick_name"] : $user_info["user_name"];
        $user_tel = $user_info["user_tel"];
        $website_id = $order_obj["website_id"];
        $order_money = $order_obj["pay_money"];
        $sms_params = array(
            "ordermoney" => $order_money,
            "username" => $user_name,
            "timeout" => $params["time_out"]
        );
        return $this->sendMessage('create_order', $user_tel, $sms_params, 0, $website_id);
    }

    /**
     * 订单付款成功发送短信
     * @param string $params
     */
    public function orderPayBySms($params = null) {
        $order_id = $params["order_id"];
        $order_model = new VslOrderModel();
//        $sql = "insert into `sys_log` (`content`) VALUES ('$order_id')";
//        Db::query($sql);
        $order_obj = $order_model->get($order_id);
        $website_id = $order_obj["website_id"];
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $user_name = $user_info["nick_name"] ? $user_info["nick_name"] : $user_info["user_name"];
        $user_tel = $user_info["user_tel"];
        $sms_params = array(
            "username" => $user_name
        );
        return $this->sendMessage('pay_success', $user_tel, $sms_params, 0, $website_id);
    }

    /**
     * 订单付款成功发送短信
     * @param string $params
     */
    public function channelOrderPayBySms($params = null) {
        if(getAddons('channel', $params['website_id'])){
            $order_id = $params["order_id"];
            $order_model = new VslChannelOrderModel();
//        $sql = "insert into `sys_log` (`content`) VALUES ('$order_id')";
//        Db::query($sql);
            $order_obj = $order_model->get($order_id);
            $website_id = $order_obj["website_id"];
            $user = new UserModel();
            $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
            $user_name = $user_info["nick_name"] ? $user_info["nick_name"] : $user_info["user_name"];
            $user_tel = $user_info["user_tel"];
            $sms_params = array(
                "username" => $user_name
            );
            return $this->sendMessage('pay_success', $user_tel, $sms_params, 0, $website_id);
        }
    }

    /**
     * 订单付款成功发送模板消息
     * @param string $params
     */
    public function orderPayByTemplate($params = null) {
        if (isFromMp()) {return;}
        $order_id = $params["order_id"];
        $order_model = new orderService();
        $order_obj = $order_model->getOrderMessage($order_id);
        if($params["order_type"] == 7){
            $order_obj['order_money'] = round($order_obj['order_money'], 2);
        }
        $website_id = $order_obj["website_id"];
        $url = $this->http . $_SERVER['HTTP_HOST'] . '/wap/order/list';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 1, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的订单已完成付款',
                        'color' => '#2c9cf0'
                    ),
                    'orderProductPrice' => array(
                        'value' => $order_obj['order_money'],
                        'color' => '#173177'
                    ),
                    'orderProductName' => array(
                        'value' => $order_obj['goods_name'],
                        'color' => '#173177'
                    ),
                    'orderAddress' => array(
                        'value' => $order_obj['receiver_address'],
                        'color' => '#173177'
                    ),
                    'orderName' => array(
                        'value' => $order_obj['order_no'],
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '商家会尽快为您安排发货，感谢您对我们的支持。',
                        'color' => '#2c9cf0'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }

    /**
     * 订单付款成功发送模板消息
     * @param string $params
     */
    public function channelOrderPayByTemplate($params = null) {
        if (isFromMp()) {return;}
        $order_id = $params["order_id"];
        $website_id = $params["website_id"];
        $order_model = new orderService();
        $order_obj = $order_model->getOrderMessage($order_id, 1, $website_id);
        $website_id = $order_obj["website_id"];
        $url = $this->http . $_SERVER['HTTP_HOST'] . '/wap/order/list';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 1, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的订单已完成付款',
                        'color' => '#2c9cf0'
                    ),
                    'orderProductPrice' => array(
                        'value' => $order_obj['order_money'],
                        'color' => '#173177'
                    ),
                    'orderProductName' => array(
                        'value' => $order_obj['goods_name'],
                        'color' => '#173177'
                    ),
                    'orderAddress' => array(
                        'value' => $order_obj['receiver_address'],
                        'color' => '#173177'
                    ),
                    'orderName' => array(
                        'value' => $order_obj['order_no'],
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '商家会尽快为您安排发货，感谢您对我们的支持。',
                        'color' => '#2c9cf0'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }

    /**
     * 订单取消成功发送模板消息
     * @param string $params
     */
    public function orderCancelByTemplate($params = null) {
        if (isFromMp()) {return;}
        $order_id = $params["order_id"];
        $order_model = new orderService();
        if(isset($params["channel_status"])){
            $channel_status = $params["channel_status"] ?: '';
            $website_id = $params["website_id"] ?: '';
            $order_obj = $order_model->getOrderMessage($order_id, $channel_status, $website_id);
        }else{
            $order_obj = $order_model->getOrderMessage($order_id);
        }
        $website_id = $order_obj["website_id"];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$website_id],'*');
        if($website_info['realm_ip']){
            $url = $this->http . $website_info['realm_ip'] . '/wap/order/list';
        }else{
            $url = $this->http . $website_info['realm_two_ip'] . '/wap/order/list';
        }
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 2, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的订单已取消',
                        'color' => '#2c9cf0'
                    ),
                    'orderProductPrice' => array(
                        'value' => $order_obj['order_money'],
                        'color' => '#173177'
                    ),
                    'orderProductName' => array(
                        'value' => $order_obj['goods_name'],
                        'color' => '#173177'
                    ),
                    'orderAddress' => array(
                        'value' => $order_obj['receiver_address'],
                        'color' => '#173177'
                    ),
                    'orderName' => array(
                        'value' => $order_obj['order_no'],
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '点击"详情"查看订单相关信息，如有疑问可联系客服',
                        'color' => '#2c9cf0'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }

    /**
     * 售后退款成功发送模板消息()
     * @param string $params
     */
    public function refundSuccessByTemplate($params = null) {
        $order_goods_id = $params["order_goods_id"];
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $order_model = new VslOrderGoodsModel();
        if (count($order_goods_id) > 1) {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $website = new WebSiteModel();
            $website_info = $website->getInfo(['website_id'=>$order_info['website_id']],'*');
            if($website_info['realm_ip']){
                $ip = $website_info['realm_ip'];
            }else{
                $ip = $website_info['realm_two_ip'];
            }
            $url = $this->http . $ip . '/wap/order/post?order_goods_id=' . $order_info['order_goods_id'];
            $order_obj = $order_model->Query(['order_goods_id' => ['IN', $order_goods_id]], 'goods_name');
            $goods_name = implode(' ', $order_obj);
            $order = new VslOrderModel();
            $orderInfo = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no, http_from');
            $order_no = $orderInfo['order_no'];
            $http_from = $orderInfo['http_from'];
            if ($http_from == 1) {return;}
            $website_id = $order_info["website_id"];
            $return_money = $order_model->getSum(['order_goods_id' => ['IN', implode(',', $order_goods_id)]], 'refund_real_money');
        } else {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $website = new WebSiteModel();
            $website_info = $website->getInfo(['website_id'=>$order_info['website_id']],'*');
            if($website_info['realm_ip']){
                $ip = $website_info['realm_ip'];
            }else{
                $ip = $website_info['realm_two_ip'];
            }
            $url = $this->http.$ip.'/wap/order/post?order_goods_id=' . $order_info['order_goods_id'];
            $goods_name = $order_info['goods_name'];
            $order = new VslOrderModel();
            $orderInfo = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no,http_from');
            $order_no = $orderInfo['order_no'];
            $http_from = $orderInfo['http_from'];
            if ($http_from == 1) {return;}
            $website_id = $order_info["website_id"];
            $return_money = $order_info['refund_require_money'];
        }
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_info['buyer_id']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 4, 'website_id' => $website_id, 'is_use' => 1], '*');
        $array = array(
            'touser' => $openid,
            'template_id' => $template_info['template_id'],
            'url' => $url,
            'data' => array(
                'first' => array(
                    'value' => '您申请的售后服务有新的进度！',
                    'color' => '#173177'
                ),
                'keyword1' => array(
                    'value' => '仅退款',
                    'color' => '#173177'
                ),
                'keyword2' => array(
                    'value' => '退款成功',
                    'color' => '#28B400'
                ),
                'keyword3' => array(
                    'value' => $goods_name,
                    'color' => '#173177'
                ),
                'keyword4' => array(
                    'value' => $order_no,
                    'color' => '#173177'
                ),
                'remark' => array(
                    'value' => '您的退款已原路返回，退款金额为' . $return_money . '元，退款可能有延时，请留意到账情况',
                    'color' => '#173177'
                )
            )
        );
        $weixin = new WchatOauth($website_id);
        return $weixin->templateMessageSend($array, $website_id);
    }

    /**
     * 售后拒绝退款发送模板消息
     * @param string $params
     */
    public function refundFailedByTemplate($params = null) {
        $order_goods_id = $params["order_goods_id"];
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $order_model = new VslOrderGoodsModel();
        $website = new WebSiteModel();
        if (count($order_goods_id) > 1) {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $website_info = $website->getInfo(['website_id'=>$order_info['website_id']],'*');
            if($website_info['realm_ip']){
                $ip = $website_info['realm_ip'];
            }else{
                $ip = $website_info['realm_two_ip'];
            }
            $url = $this->http . $ip . '/wap/order/post?order_goods_id=' . $order_info['order_goods_id'];
            $order_obj = $order_model->Query(['order_goods_id' => ['IN', $order_goods_id]], 'goods_name');
            $goods_name = implode(' ', $order_obj);
            $order = new VslOrderModel();
            $orderInfo = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no, http_from');
            $order_no = $orderInfo['order_no'];
            $http_from = $orderInfo['http_from'];
            if ($http_from == 1) {return;}
            $website_id = $order_info["website_id"];
        } else {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $website_info = $website->getInfo(['website_id'=>$order_info['website_id']],'*');
            if($website_info['realm_ip']){
                $ip = $website_info['realm_ip'];
            }else{
                $ip = $website_info['realm_two_ip'];
            }
            $url = $this->http.$ip.'/wap/order/post?order_goods_id=' . $order_info['order_goods_id'];
            $goods_name = $order_info['goods_name'];
            $order = new VslOrderModel();
            $orderInfo = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no, http_from');
            $order_no = $orderInfo['order_no'];
            $http_from = $orderInfo['http_from'];
            if ($http_from == 1) {return;}
            $website_id = $order_info["website_id"];
        }
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_info['buyer_id']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 4, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info && $order_info['refund_type'] == 1) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您申请的售后服务有新的进度！',
                        'color' => '#173177'
                    ),
                    'keyword1' => array(
                        'value' => '退款',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => '退款失败',
                        'color' => '#FF0000'
                    ),
                    'keyword3' => array(
                        'value' => $goods_name,
                        'color' => '#173177'
                    ),
                    'keyword4' => array(
                        'value' => $order_no,
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '失败原因' . $params['refusal'] . '，如有疑问请联系客服',
                        'color' => '#173177'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }

    /**
     * 售后同意退货发送模板消息
     * @param string $params
     */
    public function agreedReturnByTemplate($params = null) {
        $order_goods_id = $params["order_goods_id"];
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $order_model = new VslOrderGoodsModel();
        $website = new WebSiteModel();
        if (count($order_goods_id) > 1) {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $website_info = $website->getInfo(['website_id'=>$order_info['website_id']],'*');
            if($website_info['realm_ip']){
                $ip = $website_info['realm_ip'];
            }else{
                $ip = $website_info['realm_two_ip'];
            }
            $url = $this->http . $ip . '/wap/order/post?order_goods_id=' . $order_info['order_goods_id'];
            $order_obj = $order_model->Query(['order_goods_id' => ['IN', $order_goods_id]], 'goods_name');
            $goods_name = implode(' ', $order_obj);
            $order = new VslOrderModel();
            $orderInfo = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no, http_from');
            $order_no = $orderInfo['order_no'];
            $http_from = $orderInfo['http_from'];
            if ($http_from == 1) {return;}
            $website_id = $order_info["website_id"];
        } else {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $website_info = $website->getInfo(['website_id'=>$order_info['website_id']],'*');
            if($website_info['realm_ip']){
                $ip = $website_info['realm_ip'];
            }else{
                $ip = $website_info['realm_two_ip'];
            }
            $url = $this->http.$ip.'/wap/order/post?order_goods_id=' . $order_info['order_goods_id'];
            $goods_name = $order_info['goods_name'];
            $order = new VslOrderModel();
            $orderInfo = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no, http_from');
            $order_no = $orderInfo['order_no'];
            $http_from = $orderInfo['http_from'];
            if ($http_from == 1) {return;}
            $website_id = $order_info["website_id"];
        }
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_info['buyer_id']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 4, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info && $order_info['refund_type'] == 2) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您申请的售后服务有新的进度！',
                        'color' => '#173177'
                    ),
                    'keyword1' => array(
                        'value' => '退货退款',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => '同意退货',
                        'color' => '#28B400'
                    ),
                    'keyword3' => array(
                        'value' => $goods_name,
                        'color' => '#173177'
                    ),
                    'keyword4' => array(
                        'value' => $order_no,
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '点击"详情"查看商家收货信息，如有疑问请联系客服。',
                        'color' => '#173177'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }

    /**
     * 售后拒绝退货发送模板消息
     * @param string $params
     */
    public function refuseReturnByTemplate($params = null) {
        $order_goods_id = $params["order_goods_id"];
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $order_model = new VslOrderGoodsModel();
        $website = new WebSiteModel();
        if (count($order_goods_id) > 1) {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $website_info = $website->getInfo(['website_id'=>$order_info['website_id']],'*');
            if($website_info['realm_ip']){
                $ip = $website_info['realm_ip'];
            }else{
                $ip = $website_info['realm_two_ip'];
            }
            $url = $this->http . $ip . '/wap/order/post?order_goods_id=' . $order_info['order_goods_id'];
            $order_obj = $order_model->Query(['order_goods_id' => ['IN', $order_goods_id]], 'goods_name');
            $goods_name = implode(' ', $order_obj);
            $order = new VslOrderModel();
            $orderInfo = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no, http_from');
            $order_no = $orderInfo['order_no'];
            $http_from = $orderInfo['http_from'];
            if ($http_from == 1) {return;}
            $website_id = $order_info["website_id"];
        } else {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $website_info = $website->getInfo(['website_id'=>$order_info['website_id']],'*');
            if($website_info['realm_ip']){
                $ip = $website_info['realm_ip'];
            }else{
                $ip = $website_info['realm_two_ip'];
            }
            $url = $this->http.$ip . '/wap/order/post?order_goods_id=' . $order_info['order_goods_id'];
            $goods_name = $order_info['goods_name'];
            $order = new VslOrderModel();
            $orderInfo = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no, http_from');
            $order_no = $orderInfo['order_no'];
            $http_from = $orderInfo['http_from'];
            if ($http_from == 1) {return;}
            $website_id = $order_info["website_id"];
        }
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_info['buyer_id']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 4, 'website_id' => $website_id, 'is_use' => 1], '*');
        $array = array(
            'touser' => $openid,
            'template_id' => $template_info['template_id'],
            'url' => $url,
            'data' => array(
                'first' => array(
                    'value' => '您申请的售后服务有新的进度！',
                    'color' => '#173177'
                ),
                'keyword1' => array(
                    'value' => '退货',
                    'color' => '#173177'
                ),
                'keyword2' => array(
                    'value' => '拒绝退货',
                    'color' => '#FF0000'
                ),
                'keyword3' => array(
                    'value' => $goods_name,
                    'color' => '#173177'
                ),
                'keyword4' => array(
                    'value' => $order_no,
                    'color' => '#173177'
                ),
                'remark' => array(
                    'value' => '失败原因' . $params['refusal'] . '，如有疑问请联系客服',
                    'color' => '#173177'
                )
            )
        );
        $weixin = new WchatOauth($website_id);
        return $weixin->templateMessageSend($array, $website_id);
    }

    /**
     * 提现成功发送模板消息
     * @param string $params
     */
    public function successfulWithdrawalsByTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '*');
        $website_id = $member_obj["website_id"];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$website_id],'*');
        if($website_info['realm_ip']){
            $ip = $website_info['realm_ip'];
        }else{
            $ip = $website_info['realm_two_ip'];
        }
        $url = $this->http . $ip . '/wap/property/log';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 3, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的账户余额发生变动，信息如下。',
                        'color' => '#173177'
                    ),
                    'keyword1' => array(
                        'value' => '余额',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => '提现成功',
                        'color' => '#28B400'
                    ),
                    'keyword3' => array(
                        'value' => '流水号:' . $params['withdraw_no'],
                        'color' => '#173177'
                    ),
                    'keyword4' => array(
                        'value' => '-' . $params['takeoutmoney'] . '元',
                        'color' => '#173177'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元',
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '如对上述余额变动有异议，请联系客服人员协助处理。',
                        'color' => '#173177'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }

    /**
     * 提现失败发送模板消息
     * @param string $params
     */
    public function failureWithdrawalsByTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '*');
        $website_id = $member_obj["website_id"];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$website_id],'*');
        if($website_info['realm_ip']){
            $ip = $website_info['realm_ip'];
        }else{
            $ip = $website_info['realm_two_ip'];
        }
        $url = $this->http . $ip . '/wap/property/log';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 3, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的账户余额发生变动，信息如下。',
                        'color' => '#173177'
                    ),
                    'keyword1' => array(
                        'value' => '提现',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => '拒绝提现',
                        'color' => '#FF0000'
                    ),
                    'keyword3' => array(
                        'value' => '流水号:' . $params['withdraw_no'],
                        'color' => '#173177'
                    ),
                    'keyword4' => array(
                        'value' => $params['money'] . '元',
                        'color' => '#173177'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元',
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '失败原因：' . $params['refusal'] . '，如有疑问请联系客服',
                        'color' => '#173177'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }

    /**
     * 充值成功发送模板消息
     * @param string $params
     */
    public function successfulRechargeByTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '');
        $website_id = $params["website_id"];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$website_id],'*');
        if($website_info['realm_ip']){
            $ip = $website_info['realm_ip'];
        }else{
            $ip = $website_info['realm_two_ip'];
        }
        $url = $this->http . $ip . '/wap/property/log';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 3, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的账户余额发生变动，信息如下。',
                        'color' => '#173177'
                    ),
                    'keyword1' => array(
                        'value' => '余额',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => '充值成功',
                        'color' => '#28B400'
                    ),
                    'keyword3' => array(
                        'value' => '流水号:' . $params['out_trade_no'],
                        'color' => '#173177'
                    ),
                    'keyword4' => array(
                        'value' => '+' . $params['pay_money'] . '元',
                        'color' => '#173177'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元',
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '如对上述余额变动有异议，请联系客服人员协助处理。',
                        'color' => '#173177'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }

    /**
     * 渠道商出货零售入账发送模板消息
     * @param string $params
     */
    public function successfulChannelBonusByTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '');
        $website_id = $params["website_id"];
        $url = $this->http . $_SERVER['HTTP_HOST'] . '/wap/property/log';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $openid = $user_info['wx_openid'];
        if($params['money_channel_type'] == 2){
            $money_str = '奖金';
        }else{
            $money_str = '出货/零售产生金额';
        }
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 3, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的账户余额发生变动，信息如下。',
                        'color' => '#173177'
                    ),
                    'keyword1' => array(
                        'value' => '余额',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => '渠道商'.$money_str.'入账成功',
                        'color' => '#28B400'
                    ),
                    'keyword3' => array(
                        'value' => '渠道商'.$money_str,
                        'color' => '#173177'
                    ),
                    'keyword4' => array(
                        'value' => '+' . $params['pay_money'] . '元',
                        'color' => '#173177'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元',
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '如对上述余额变动有异议，请联系客服人员协助处理。',
                        'color' => '#173177'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }
    /**
     * 余额变动提醒
     * @param string $params
     */
    public function balanceChangeByTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '');
        $website_id = $params["website_id"];
        $url = $this->http . $_SERVER['HTTP_HOST'] . '/wap/property/log';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 3, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的账户余额发生变动，信息如下。',
                        'color' => '#173177'
                    ),
                    'keyword1' => array(
                        'value' => '余额',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => $params['change_str'],
                        'color' => '#28B400'
                    ),
                    'keyword3' => array(
                        'value' => $params['type_desc'], //类型描述
                        'color' => '#173177'
                    ),
                    'keyword4' => array(
                        'value' => '+' . $params['change_money'] . '元',
                        'color' => '#173177'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元',
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '如对上述余额变动有异议，请联系客服人员协助处理。',
                        'color' => '#173177'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }
    /**
     * 领取成功发送模板消息
     * @param string $params
     */
    public function successacceptPrizeByTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '*');
        $website_id = $member_obj["website_id"];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$website_id],'*');
        if($website_info['realm_ip']){
            $ip = $website_info['realm_ip'];
        }else{
            $ip = $website_info['realm_two_ip'];
        }
        $url = $this->http . $ip . '/wap/property/log';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 3, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的账户余额发生变动，信息如下。',
                        'color' => '#173177'
                    ),
                    'keyword1' => array(
                        'value' => '余额',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => '奖品领取成功',
                        'color' => '#28B400'
                    ),
                    'keyword3' => array(
                        'value' => '流水号:' . $params['records_no'],
                        'color' => '#173177'
                    ),
                    'keyword4' => array(
                        'value' => '+' . $params['money'] . '元',
                        'color' => '#173177'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元',
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '如对上述余额变动有异议，请联系客服人员协助处理。',
                        'color' => '#173177'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }
    
    /**
     * 领取成功发送模板消息
     * @param string $params
     */
    public function successSigninByTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '*');
        $website_id = $member_obj["website_id"];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$website_id],'*');
        if($website_info['realm_ip']){
            $ip = $website_info['realm_ip'];
        }else{
            $ip = $website_info['realm_two_ip'];
        }
        $url = $this->http . $ip . '/wap/property/log';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 3, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'first' => array(
                        'value' => '您的账户余额发生变动，信息如下。',
                        'color' => '#173177'
                    ),
                    'keyword1' => array(
                        'value' => '余额',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => '签到余额发放成功',
                        'color' => '#28B400'
                    ),
                    'keyword3' => array(
                        'value' => '流水号:' . $params['records_no'],
                        'color' => '#173177'
                    ),
                    'keyword4' => array(
                        'value' => '+' . $params['money'] . '元',
                        'color' => '#173177'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元',
                        'color' => '#173177'
                    ),
                    'remark' => array(
                        'value' => '如对上述余额变动有异议，请联系客服人员协助处理。',
                        'color' => '#173177'
                    )
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }
    
    /**
     * 成为分销商发送模板消息
     * @param string $params
     */
    public function successfulDistributorByTemplate($params = null) {
        $website_id = $params["website_id"];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$website_id],'*');
        if($website_info['realm_ip']){
            $ip = $website_info['realm_ip'];
        }else{
            $ip = $website_info['realm_two_ip'];
        }
        $url = $this->http . $ip . '/wap/mall';
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        if($user_info['member_name']){
            $member_name = $user_info['member_name'];
        }else{
            $member_name = $user_info['nick_name'];
        }
        $openid = $user_info['wx_openid'];
        $template = new WeixinInstanceMsgModel();
        $template_info = $template->getInfo(['type' => 5, 'website_id' => $website_id, 'is_use' => 1], '*');
        if ($openid && $template_info) {
            $array = array(
                'touser' => $openid,
                'template_id' => $template_info['template_id'],
                'url' => $url,
                'data' => array(
                    'keyword1' => array(
                        'value' => '成为分销商通知',
                        'color' => '#173177'
                    ),
                    'keyword2' => array(
                        'value' => $member_name ,
                        'color' => '#173177'
                    ),
                    'keyword3' => array(
                        'value' => '亲爱的用户，您已成为分销商！',
                        'color' => '#28B400'
                    ),
                    'keyword4' => array(
                        'value' => date("Y-m-d H:i:s",time()),
                        'color' => '#173177'
                    ),
                )
            );
            $weixin = new WchatOauth($website_id);
            return $weixin->templateMessageSend($array, $website_id);
        }
    }
    /**
     * 订单确认发送短信
     * @param string $params
     */
    public function orderCompleteBySms($params = null) {
        $order_id = $params["order_id"];
        $order_model = new VslOrderModel();
        $order_obj = $order_model->get($order_id);
        $website_id = $order_obj["website_id"];
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $user_name = $user_info["nick_name"] ? $user_info["nick_name"] : $user_info["user_name"];
        $user_tel = $user_info["user_tel"];
        $sms_params = array(
            "username" => $user_name
        );

        return $this->sendMessage('confirm_order', $user_tel, $sms_params, 0, $website_id);
    }

    /**
     * 订单发货发送短信
     * @param string $params
     */
    public function orderDeliveryBySms($params = null) {
        $order_goods_ids = $params["order_goods_ids"];
        $order_goods_str = explode(",", $order_goods_ids);
        $user_name = "";
        if (count($order_goods_str) > 0) {
            $order_goods_id = $order_goods_str[0];
            $order_goods_model = new VslOrderGoodsModel();
            $order_goods_obj = $order_goods_model->get($order_goods_id);
            $website_id = $order_goods_obj["website_id"];
            $order_id = $order_goods_obj["order_id"];
            $order_model = new VslOrderModel();
            $order_obj = $order_model->get($order_id);
            $user = new UserModel();
            $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
            $user_name = $user_info["nick_name"] ? $user_info["nick_name"] : $user_info["user_name"];
            $user_tel = $user_info["user_tel"];
            $order_no = $order_obj["order_no"];
            $sms_params = array(
                "username" => $user_name,
                "orderno" => $order_no,
            );

            return $this->sendMessage('order_deliver', $user_tel, $sms_params, 0, $website_id);
        }
    }

    /**
     * 用户注册成功后发送短信
     * @param string $params
     */
    public function registAfterBySms($params = null) {
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $user_id = $params["user_id"];
        $user_model = new UserModel();
        $user_obj = $user_model->get($user_id);
        $mobile = "";
        $user_name = "";
        if (empty($user_obj)) {
            $user_name = "用户";
        } else {
            $user_name = $user_obj["user_name"] ? $user_obj["user_name"] : $user_obj["nick_name"];
            $mobile = $user_obj["user_tel"];
        }
        $sms_params = array(
            "username" => $user_name
        );

        return $this->sendMessage('after_register', $mobile, $sms_params, $shop_id, $website_id);
    }

    /**
     * 订单提醒发送短信
     * @param string $params
     */
    public function orderRemindBusinessBySms($params = null) {
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $order_id = $params["order_id"]; //订单号
        if (!empty($order_id)) {
            $vsl_order = new VslOrderModel();
            $order_detial = $vsl_order->getInfo(["order_id" => $order_id]);
            if(getAddons('shop', $website_id) && $order_detial['shop_id'] > 0){
                $shop = new Shop();
                $shopUser = $shop->getShopUserDetail($order_detial['shop_id'], $website_id);
                $mobile = $shopUser['user_tel'];
            }else{
                $websiteServer = new WebSite();
                $websiteInfo = $websiteServer->getWebSiteInfo($website_id);
                $mobile = $websiteInfo['user_tel'];
            }
            $sms_params = array(
                "ordermoney" => $order_detial['order_money']
            );
            return $this->sendMessage('order_remind', $mobile, $sms_params, $shop_id, $website_id);
        }
    }
    /**
     * 订单提醒发送短信
     * @param string $params
     */
    public function channelOrderRemindBusinessBySms($params = null) {
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $order_id = $params["order_id"]; //订单号
        if (!empty($order_id)) {
            $vsl_order = new VslChannelOrderModel();
            $order_detial = $vsl_order->getInfo(["order_id" => $order_id]);
            if(getAddons('shop', $website_id)){
                $shop = new Shop();
                $shopUser = $shop->getShopUserDetail($order_detial['shop_id'], $website_id);
                $mobile = $shopUser['user_tel'];
            }else{
                $websiteServer = new WebSite();
                $websiteInfo = $websiteServer->getWebSiteInfo($website_id);
                $mobile = $websiteInfo['user_tel'];
            }
            $sms_params = array(
                "ordermoney" => $order_detial['order_money']
            );
            return $this->sendMessage('order_remind', $mobile, $sms_params, $shop_id, $website_id);
        }
    }
    /**
     * 订单退款提醒发送短信
     * @param string $params
     */
    public function orderRefoundBusinessBySms($params = null) {
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $order_id = $params["order_id"]; //订单号
        if (!empty($order_id)) {
            $vsl_order = new VslOrderModel();
            $order_detial = $vsl_order->getInfo(["order_id" => $order_id]);
            if(getAddons('shop', $website_id)){
                $shop = new Shop();
                $shopUser = $shop->getShopUserDetail($order_detial['shop_id'], $website_id);
                $mobile = $shopUser['user_tel'];
            }else{
                $websiteServer = new WebSite();
                $websiteInfo = $websiteServer->getWebSiteInfo($website_id);
                $mobile = $websiteInfo['user_tel'];
            }
            return $this->sendMessage('refund_order', $mobile, array(), $shop_id, $website_id);
        }
    }

    /**
     * 修改密码短信验证码
     * @param string $params
     * @return multitype:number string
     */
    public function changePasswordBySms($params = null) {
        $send_param = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $rand = rand(100000, 999999);
        $smsParams = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('change_password', $send_param, $smsParams, $shop_id, $website_id);
    }

    /**
     * 修改密码短信验证码
     * @param string $params
     * @return multitype:number string
     */
    public function changePayPasswordBySms($params = null) {
        $send_param = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $rand = rand(100000, 999999);
        $smsParams = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('change_pay_password', $send_param, $smsParams, $shop_id, $website_id);
    }

    /**
     * 用户绑定邮箱验证码
     * @param string $params
     */
    public function bindEmailBySms($params = null) {
        $rand = rand(100000, 999999);
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('bind_email', $mobile, $sms_params, $shop_id, $website_id);
    }

    /**
     * 用户充值余额用户提醒短信
     */
    public function rechargeSuccessUserBySms($params = null) {
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $out_trade_no = $params["out_trade_no"];
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $user_tel = $user_info["user_tel"];
        if (!empty($out_trade_no)) {
            $pay = new VslOrderPaymentModel();
            $order_payment = $pay->getInfo(["out_trade_no" => $out_trade_no]);
            $sms_params = array(
                "time" => date('Y-m-d H:i:s', $order_payment['create_time']),
                "money" => $order_payment['pay_money']
            );
            return $this->sendMessage('recharge_success', $user_tel, $sms_params, $shop_id, $website_id);
        }
    }

    /**
     * 手机登陆验证码
     * @param string $params
     */
    public function loginBySms($params = null) {
        $rand = rand(100000, 999999);
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('login_validate', $mobile, $sms_params, $shop_id, $website_id);
    }

    /**
     * 提现成功短信通知
     * @param string $params
     */
    public function withdrawalSuccessBySms($params = null) {
        $takeoutmoney = $params["takeoutmoney"];
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $user_tel = $user_info["user_tel"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "takeoutmoney" => $takeoutmoney . ""
        );
        return $this->sendMessage('withdrawal_success', $user_tel, $sms_params, $shop_id, $website_id);
    }

    /**
     * 提现失败短信通知
     * @param string $params
     */
    public function withdrawalFailBySms($params = null) {
        $refusal = $params["refusal"];
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $user_tel = $user_info["user_tel"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "refusal" => $refusal . ""
        );
        return $this->sendMessage('withdrawal_fail', $user_tel, $sms_params, $shop_id, $website_id);
    }

    /**
     * 订单作废发送短信
     * @param string $params
     */
    public function orderCancelBySms($params = null) {
        $order_id = $params["order_id"];
        $channel_status = $params["channel_status"];
        $website_id = $params["website_id"];
        $order_model = new VslOrderModel();
        if ($channel_status == 1 && getAddons('channel', $website_id)) {
            $order_model = new VslChannelOrderModel();
        }
        $order_obj = $order_model->get($order_id);
        $website_id = $order_obj["website_id"];
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $user_name = $user_info["nick_name"] ? $user_info["nick_name"] : $user_info["user_name"];
        $user_tel = $user_info["user_tel"];
        $order_no = $order_obj["order_no"];
        $sms_params = array(
            "username" => $user_name,
            "orderno" => $order_no
        );
        return $this->sendMessage('cancel_order', $user_tel, $sms_params, 0, $website_id);
    }

    /**
     * 同意退款退货发送短信
     * @param string $params
     */
    public function agreeRefundOrReturnBySms($params = null) {
        $order_id = $params["order_id"];
        $order_goods_id = $params["order_goods_id"];
        $order_model = new VslOrderModel();
        $order_obj = $order_model->get($order_id);
        $order_goods_model = new VslOrderGoodsModel();
        $order_goods_obj = $order_goods_model::all(['order_goods_id' => ['IN', $order_goods_id]]);
        //$order_refund_model = new VslOrderRefundAccountRecordsModel();
        //$order_refund_obj = $order_refund_model::all(['order_goods_id', ['IN', $order_goods_id]]);
//        $refund_type = reset($order_goods_obj)['refund_type'];
        $refund_money = 0;
        foreach ($order_goods_obj as $v) {
            $refund_money += $v['refund_require_money'];
        }
        $website_id = $order_obj["website_id"];
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $user_name = $user_info["nick_name"] ? $user_info["nick_name"] : $user_info["user_name"];
        $user_tel = $user_info["user_tel"];
        $order_no = $order_obj["order_no"];
        if ($params['refund_type'] == 1) {
            $sms_params = array(
                "username" => $user_name,
                "orderno" => $order_no,
                "refundmoney" => $refund_money,
            );
            return $this->sendMessage('agree_refund', $user_tel, $sms_params, 0, $website_id);
        } elseif ($params['refund_type'] == 2) {
            $sms_params = array(
                "username" => $user_name,
                "orderno" => $order_no
            );
            return $this->sendMessage('agree_return', $user_tel, $sms_params, 0, $website_id);
        }
    }

    /**
     * 订单退款退货拒绝发送短信
     * @param string $params
     */
    public function refuseRefundOrReturnBySms($params = null) {
        $order_id = $params["order_id"];
        $order_goods_id = $params["order_goods_id"];
        $refusal = $params["refusal"];
        $order_model = new VslOrderModel();
        $order_obj = $order_model->get($order_id);
        $order_goods_model = new VslOrderGoodsModel();
        $order_goods_obj = $order_goods_model::all(['order_goods_id' => ['IN', $order_goods_id]]);
        $refund_type = reset($order_goods_obj)['refund_type'];
        $website_id = $order_obj["website_id"];
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $user_name = $user_info["nick_name"] ? $user_info["nick_name"] : $user_info["user_name"];
        $user_tel = $user_info["user_tel"];
        $order_no = $order_obj["order_no"];
        $sms_params = array(
            "username" => $user_name,
            "orderno" => $order_no,
            "refusal" => $refusal,
        );

        if ($refund_type == 1) {
            return $this->sendMessage('refuse_refund', $user_tel, $sms_params, 0, $website_id);
        } elseif ($refund_type == 2) {
            return $this->sendMessage('refuse_return', $user_tel, $sms_params, 0, $website_id);
        }
    }

    /**
     * 订单确认退款发送短信
     * @param string $params
     */
    public function confirmRefundBySms($params = null) {
        $order_goods_model = new VslOrderGoodsModel();
        $order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $params['order_goods_id']]]);
        $user = new UserModel();
        $user_info = $user->getInfo(['uid' => $params['uid']], '*');
        $user_name = $user_info['nick_name'] ? $user_info['nick_name'] : $user_info['user_name'];
        $user_tel = $user_info['user_tel'];
        $order_no = reset($order_goods_info)['order']['order_no'];
        $sms_params = array(
            'username' => $user_name,
            'orderno' => $order_no,
            'refundmoney' => $params['refund_money'],
        );
        return $this->sendMessage('return_success', $user_tel, $sms_params, 0, $params['website_id']);
    }

    public function emailSend(array $params) {
        $user_model = new UserModel();
        if($params['is_channel'] && getAddons('channel', $params['website_id'])){
            $order_payment_model = new VslChannelOrderPaymentModel();
            $order_model = new VslChannelOrderModel();
            $order_goods_model = new VslChannelOrderGoodsModel();
        }else{
            $order_payment_model = new VslOrderPaymentModel();
            $order_model = new VslOrderModel();
            $order_goods_model = new VslOrderGoodsModel();
        }
        if (empty($params['website_id']) && !empty($params['order_id'])) {
            $order_info = $order_model::get(['order_id' => $params['order_id']]);
            $params['website_id'] = $order_info->website_id;
        }
        $this->getShopNotifyInfo(0, $params['website_id']);
        if ($this->email_is_open != 1) {
            $this->result['code'] = -1;
            $this->result['message'] = '店家没有开启邮箱验证';
            return $this->result;
        }

        if (empty($this->email_host) || empty($this->email_id) || empty($this->email_pass) || empty($this->email_addr)) {
            $this->result['code'] = -1;
            $this->result['message'] = '邮箱配置信息有误!';
            return $this->result;
        }

        // 仅有平台有模板
        if (empty($params['template_code'])) {
            // 目前拒绝退货/退款没有确定消息模板
            $order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $params['order_goods_id']]]);
            if (reset($order_goods_info)['refund_type'] == 1) {
                $params['template_code'] = 'refuse_refund';
            } else {
                $params['template_code'] = 'refuse_return';
            }
        }

        $template_email_obj = $this->getTemplateDetail(0, $params['website_id'], $params['template_code'], 'email', $params['notify_type']);
        if (empty($template_email_obj)) {
            $this->result['code'] = -1;
            $this->result['message'] = '不存在邮件模板';
            return $this->result;
        }

        if ($template_email_obj['is_enable'] != 1) {
            $this->result['code'] = -1;
            $this->result['message'] = '邮件模板不可用';
            return $this->result;
        }

        $website_model = new WebSiteModel();
        $website_info = $website_model::get(['website_id' => $params['website_id']]);

        $send = new Send();
        switch ($params['template_code']) {
            case 'register_validate':
                // 邮箱验证码
                if (empty($params['to_email'])) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                $rand = rand(100000, 999999);
                $content = $template_email_obj['template_content'];
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${code}', $rand, $content);
                $content = str_replace('${timeout}', $params['expire'], $content);

                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛code｝', $rand, $content);
                $content = str_replace('$｛timeout｝', $params['expire'], $content);
                $result = $send->email($params['to_email'], $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                    return $this->result;
                }
                $this->result['param'] = $rand;
                return $this->result;
                break;
            case 'bind_email':
                // 绑定邮箱
                $rand = rand(100000, 999999);
                if (empty($params['to_email'])) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                $content = $template_email_obj['template_content'];
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${code}', $rand, $content);
                $content = str_replace('${timeout}', $params['expire'], $content);

                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛code｝', $rand, $content);
                $content = str_replace('$｛timeout｝', $params['expire'], $content);
                $result = $send->email($params['to_email'], $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                $this->result['param'] = $rand;
                return $this->result;
                break;
            case 'after_register':
                // 注册成功
                $user_info = $user_model::get(['uid' => $params['uid']]);
                $to_email = $params['to_email'] ?: $user_info['user_email'];
                $user_name = $params['user_name'] ?: ($user_info['nick_name'] ?: $user_info['user_name']);
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                $content = $template_email_obj['template_content'];
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);

                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);

                $result = $send->email($params['to_email'], $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'return_success':
                // 退款成功
                $order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $params['order_goods_id']]]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: (reset($order_goods_info)->order->buyer->nick_name ?: reset($order_goods_info)->order->buyer->user_name);
                $order_no = $params['order_no'] ?: (reset($order_goods_info)->order->order_no ?: '');
                $to_email = (reset($order_goods_info)->order->buyer->user_email ?: '');
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${orderno}', $order_no, $content);
                $content = str_replace('${refundmoney}', $params['refund_money'], $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛orderno｝', $order_no, $content);
                $content = str_replace('$｛refundmoney｝', $params['refund_money'], $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'recharge_success':
                // 充值成功
                $order_payment_info = $order_payment_model::get($params['out_trade_no']);
                $user_info = $user_model::get($params['uid']);
                $pay_money = $params['pay_money'] ?: ($order_payment_info['pay_money'] ?: 0);
                $to_email = $params['to_email'] ?: $user_info['user_email'];
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                $content = $template_email_obj['template_content'];
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${money}', $pay_money, $content);

                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛money｝', $pay_money, $content);

                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'withdrawal_success':
                // 提现成功
                $user_info = $user_model::get($params['uid']);
                $to_email = $params['to_email'] ?: $user_info['user_email'];
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                $content = $template_email_obj['template_content'];
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${takeoutmoney}', $params['take_out_money'], $content);

                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛takeoutmoney｝', $params['take_out_money'], $content);

                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'withdrawal_fail':
                // 提现失败
                $user_info = $user_model::get($params['uid']);
                $to_email = $params['to_email'] ?: $user_info['user_email'];
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                $content = $template_email_obj['template_content'];
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${refusal}', $params['refusal'], $content);

                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛refusal｝', $params['refusal'], $content);

                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'pay_success':
                // 支付成功
                $order_info = $order_model::get(['order_id' => $params['order_id']]);
                $user_name = $params['user_name'] ?: ($order_info->buyer->nick_name ?: $order_info->buyer->user_name);
                $to_email = $order_info->buyer->user_email;
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                $content = $template_email_obj['template_content'];
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'create_order':
                // 创建订单
                $order_info = $order_model::get(['order_id' => $params['order_id']]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: ($order_info->buyer->nick_name ?: $order_info->buyer->user_name);
                $order_money = $params['order_money'] ?: $order_info->order_money;
                $to_email = $order_info->buyer->user_email;
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${ordermoney}', $order_money, $content);
                $content = str_replace('${timeout}', $params['time_out'], $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛ordermoney｝', $order_money, $content);
                $content = str_replace('$｛timeout｝', $params['time_out'], $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'create_channel_order':
                $channel_order_mdl = new VslChannelOrderModel();
                // 创建订单
                $order_info = $channel_order_mdl::get(['order_id' => $params['order_id']]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: ($order_info->buyer->nick_name ?: $order_info->buyer->user_name);
                $order_money = $params['order_money'] ?: $order_info->order_money;
                $to_email = $order_info->buyer->user_email;
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${ordermoney}', $order_money, $content);
                $content = str_replace('${timeout}', $params['time_out'], $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛ordermoney｝', $order_money, $content);
                $content = str_replace('$｛timeout｝', $params['time_out'], $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'order_deliver':
                // 订单发货
                $order_info = $order_model::get(['order_id' => $params['order_id']]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: ($order_info->buyer->nick_name ?: $order_info->buyer->user_name);
                $order_no = $params['order_no'] ?: $order_info->order_no;
                $to_email = $order_info->buyer->user_email;
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${orderno}', $order_no, $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛orderno｝', $order_no, $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'confirm_order':
                // 确认收货
                $order_info = $order_model::get(['order_id' => $params['order_id']]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: ($order_info->buyer->nick_name ?: $order_info->buyer->user_name);
                $to_email = $order_info->buyer->user_email;
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'cancel_order':
                // 取消订单
                $order_info = $order_model::get(['order_id' => $params['order_id']]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: ($order_info->buyer->nick_name ?: $order_info->buyer->user_name);
                $order_no = $params['order_no'] ?: $order_info->order_no;
                $to_email = $order_info->buyer->user_email;
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${orderno}', $order_no, $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛orderno｝', $order_no, $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'cancel_channel_order':
                // 取消订单
                $order_model = new VslChannelOrderModel();
                $order_info = $order_model::get(['order_id' => $params['order_id']]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: ($order_info->buyer->nick_name ?: $order_info->buyer->user_name);
                $order_no = $params['order_no'] ?: $order_info->order_no;
                $to_email = $order_info->buyer->user_email;
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${orderno}', $order_no, $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛orderno｝', $order_no, $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'agree_return':
                // 同意退货
                $order_info = $order_model::get(['order_id' => $params['order_id']]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: ($order_info->buyer->nick_name ?: $order_info->buyer->user_name);
                $order_no = $params['order_no'] ?: $order_info->order_no;
                $to_email = $order_info->buyer->user_email;
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${orderno}', $order_no, $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛orderno｝', $order_no, $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'agree_refund':
                // 同意退款
                $order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $params['order_goods_id']]]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: (reset($order_goods_info)->order->buyer->nick_name ?: reset($order_goods_info)->order->buyer->user_name);
                $order_no = $params['order_no'] ?: (reset($order_goods_info)->order->order_no ?: '');
                $refund_money = 0;
                foreach ($order_goods_info as $v) {
                    $refund_money += $v['refund_require_money'];
                }
                $to_email = (reset($order_goods_info)->order->buyer->user_email ?: '');
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${orderno}', $order_no, $content);
                $content = str_replace('${refundmoney}', $refund_money, $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛orderno｝', $order_no, $content);
                $content = str_replace('$｛refundmoney｝', $refund_money, $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'refuse_refund':
                // 拒绝退款
                //$order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $params['order_goods_id']]]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: (reset($order_goods_info)->order->buyer->nick_name ?: reset($order_goods_info)->order->buyer->user_name);
                $order_no = $params['order_no'] ?: (reset($order_goods_info)->order->order_no ?: '');
                $to_email = (reset($order_goods_info)->order->buyer->user_email ?: '');
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${orderno}', $order_no, $content);
                $content = str_replace('${refusal}', $params['refuse_reason'], $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛orderno｝', $order_no, $content);
                $content = str_replace('$｛refusal｝', $params['refuse_reason'], $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'refuse_return':
                // 拒绝退货
                //$order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $params['order_goods_id']]]);
                $content = $template_email_obj['template_content'];
                $user_name = $params['user_name'] ?: (reset($order_goods_info)->order->buyer->nick_name ?: reset($order_goods_info)->order->buyer->user_name);
                $order_no = $params['order_no'] ?: (reset($order_goods_info)->order->order_no ?: '');
                $to_email = (reset($order_goods_info)->order->buyer->user_email ?: '');
                if (empty($to_email)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                // {} in english
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${username}', $user_name, $content);
                $content = str_replace('${orderno}', $order_no, $content);
                $content = str_replace('${refusal}', $params['refuse_reason'], $content);
                // {} in chinese
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛username｝', $user_name, $content);
                $content = str_replace('$｛orderno｝', $order_no, $content);
                $content = str_replace('$｛refusal｝', $params['refuse_reason'], $content);
                $result = $send->email($to_email, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'forgot_password':
                // 忘记密码
                $rand = rand(100000, 999999);
                $content = $template_email_obj['template_content'];
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${code}', $rand, $content);

                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛code｝', $rand, $content);
                $result = $send->email($params['to_email'], $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                $this->result['param'] = $rand;
                return $this->result;
                break;
            case 'refund_order':
                // 订单退款 -卖家通知
                $email_array = explode(',', $template_email_obj['notification_mode']); //获取要提醒的人
                if (empty($email_array)) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '缺少收件邮箱号';
                    return $this->result;
                }
                $content = $template_email_obj['template_content'];
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $result = $send->email($email_array, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            case 'order_remind':
                // 支付提醒
                $email_array = explode(',', $template_email_obj['notification_mode']); //获取要提醒的人
                $order_info = $order_model::get(['order_id' => $params['order_id']]);
                $order_money = $params['order_money'] ?: $order_info->order_money;
                $content = $template_email_obj['template_content'];
                $content = str_replace('${shopname}', $website_info['mall_name'], $content);
                $content = str_replace('${ordermoney}', $order_money, $content);

                $content = str_replace('$｛shopname｝', $website_info['mall_name'], $content);
                $content = str_replace('$｛ordermoney｝', $order_money, $content);
                $result = $send->email($email_array, $template_email_obj['template_title'], $content);
                if (!$result) {
                    $this->result['code'] = -1;
                    $this->result['message'] = '发送失败';
                }
                return $this->result;
                break;
            default:
                $this->result['code'] = -1;
                $this->result['message'] = '不存在邮件模板';
                return $this->result;
        }
    }

    /**
     * 商家注册验证码
     * @param string $params
     */
    public function merchantRegisterBySms($params = null) {
        $rand = rand(100000, 999999);
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('merchant_register_validate', $mobile, $sms_params, $shop_id, $website_id);
    }

    /**
     * a端登陆验证码
     * @param string $params
     */
    public function masterLoginBySms($params = null) {
        $rand = rand(100000, 999999);
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('merchant_register_validate', $mobile, $sms_params, $shop_id, $website_id);
    }
    /**
     * 商家注册成功
     * @param string $params
     */
    public function merchantRegSuccessBySms($params = null) {
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            
        );
        return $this->sendMessage('merchant_register_success', $mobile, $sms_params, $shop_id, $website_id);
    }
    /**
     * 店员端验证码
     * @param string $params
     */
    public function assistantCode($params = null) {
        $rand = rand(100000, 999999);
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('assistant_code', $mobile, $sms_params, $shop_id, $website_id);
    }

    /**
     * 客服端注册验证码
     * @param string $params
     */
    public function qlkefuLoginBySms($params = null) {
        $rand = rand(100000, 999999);
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $sms_params = array(
            "code" => $rand . ""
        );
        return $this->sendMessage('qlkefu_register_validate', $mobile, $sms_params, $shop_id, $website_id);
    }

    /**
     * 订单付款成功发送模板消息 - 小程序
     * @param string $params
     */
    public function orderPayMpByTemplate($params = null) {
        $order_id = $params["order_id"];
        $order_model = new orderService();
        $order_obj = $order_model->getOrderMessage($order_id);
        $website_id = $order_obj["website_id"];
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 1,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }
        if (empty($order_obj['form_id'])) {
            $form_id = $order_model->getOrderPaymentFormIdByOrderId($order_id);
        } else {
            $form_id = $order_obj['form_id'];
        }

        if ($mp_open_id && $mp_template_id['mp_template_id']) {
            $data = array(
                'touser' => $mp_open_id,
                'template_id' => $mp_template_id['mp_template_id'],
                'page' => $url,
                'form_id' => $form_id,
                'data' => array(
                    'keyword1' => array(
                        'value' => $order_obj['order_money']
                    ),
                    'keyword2' => array(
                        'value' => $order_obj['goods_name']
                    ),
                    'keyword3' => array(
                        'value' => $order_obj['receiver_address']
                    ),
                    'keyword4' => array(
                        'value' => $order_obj['order_no']
                    )
                )
            );

            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            $sendResult = $weixin->templateMessageSend($authorizer_access_token, $data);
        }
    }

    /**
     * 订单取消成功发送模板消息 - 小程序
     * @param string $params
     */
    public function orderCancelMpByTemplate($params = null) {
        $order_id = $params["order_id"];
        $order_model = new orderService();
        if(isset($params["channel_status"])){
            $channel_status = $params["channel_status"] ?: '';
            $website_id = $params["website_id"] ?: '';
            $order_obj = $order_model->getOrderMessage($order_id, $channel_status, $website_id);
        }else{
            $order_obj = $order_model->getOrderMessage($order_id);
        }
        $website_id = $order_obj["website_id"];
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_obj['buyer_id']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 2,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }
        if (empty($order_obj['form_id'])) {
            $form_id = $order_model->getOrderPaymentFormIdByOrderId($order_id);
        } else {
            $form_id = $order_obj['form_id'];
        }
        if ($mp_open_id && $mp_template_id['mp_template_id']) {
            $data = array(
                'touser' => $mp_open_id,
                'template_id' => $mp_template_id['mp_template_id'],
                'page' => $url,
                'form_id' => $form_id,
                'data' => array(
                    'keyword1' => array(
                        'value' => '用户取消'
                    ),
                    'keyword2' => array(
                        'value' => $order_obj['order_money']
                    ),
                    'keyword3' => array(
                        'value' => $order_obj['goods_name']
                    ),
                    'keyword4' => array(
                        'value' => $order_obj['receiver_address']
                    ),
                    'keyword5' => array(
                        'value' => $order_obj['order_no']
                    )
                )
            );

            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            $result = $weixin->templateMessageSend($authorizer_access_token, $data);
        }
    }


    /**
     * 提现成功发送模板消息 - 小程序
     * @param string $params
     */
    public function successfulWithdrawalsByMpTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '*');
        $website_id = $member_obj["website_id"];
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $url = 'pages/member/index';//会员中心
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 3,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }

        // todo... 通过提现流水号获取form_id
        if ($mp_open_id && $mp_template_id['mp_template_id']) {
            $withdraw_no = $params['withdraw_no'];
            $member_account = new MemberAccount();
            $form_id = $member_account->getWithdrawalsFormIdByWithdrawNo($withdraw_no);
            $data = array(
                'touser' => $mp_open_id,
                'template_id' => $mp_template_id['mp_template_id'],
                'page' => $url,
                'form_id' => $form_id,
                'data' => array(
                    'keyword1' => array(
                        'value' => '您的账户余额发生变动，信息如下'
                    ),
                    'keyword2' => array(
                        'value' => '流水号:' . $withdraw_no
                    ),
                    'keyword3' => array(
                        'value' => '提现成功'
                    ),
                    'keyword4' => array(
                        'value' => '-' . $params['takeoutmoney'] ?: $params['money'] . '元'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元'
                    )
                ),
            );

            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            $result = $weixin->templateMessageSend($authorizer_access_token, $data);
        }
    }

    /**
     * 提现失败发送模板消息 - 小程序
     * @param string $params
     */
    public function failureWithdrawalsByMpTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '*');
        $website_id = $member_obj["website_id"];
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $url = 'pages/member/index';//会员中心
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 3,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }
        // todo... 通过提现流水号获取form_id
        if ($mp_open_id && $mp_template_id['mp_template_id']) {
            $withdraw_id = $params['withdraw_no'];
            $member_account = new MemberAccount();
            $withdraw_result = $member_account->getWithdrawalsFormIdByWithdrawId($withdraw_id);
            $data = array(
                'touser' => $mp_open_id,
                'template_id' => $mp_template_id['mp_template_id'],
                'page' => $url,
                'form_id' => $withdraw_result['form_id'],
                'data' => array(
                    'keyword1' => array(
                        'value' => '您的账户余额发生变动，信息如下'
                    ),
                    'keyword2' => array(
                        'value' => '流水号:' . $withdraw_result['withdraw_no']
                    ),
                    'keyword3' => array(
                        'value' => '提现失败'
                    ),
                    'keyword4' => array(
                        'value' => '-' . $params['takeoutmoney'] ?: $params['money']. '元'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元'
                    )
                ),
            );

            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            $result = $weixin->templateMessageSend($authorizer_access_token, $data);
        }
    }

    /**
     * 充值成功发送模板消息 - 小程序
     * @param string $params
     */
    public function successfulRechargeByMpTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '');
        $website_id = $params["website_id"];
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $url = 'pages/member/index';//会员中心
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 3,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }

        // todo... 小程序通过流水号获取form_id
        if ($mp_open_id && $mp_template_id['mp_template_id']) {
            $out_trade_no = $params['out_trade_no'];
            $member = new memberService();
            $form_id = $member->getRechargeFormIdByOutTradeNo($out_trade_no);
            $data = array(
                'touser' => $mp_open_id,
                'template_id' => $mp_template_id['mp_template_id'],
                'page' => $url,
                'form_id' => $form_id,
                'data' => array(
                    'keyword1' => array(
                        'value' => '您的账户余额发生变动，信息如下'
                    ),
                    'keyword2' => array(
                        'value' => '流水号:' .$out_trade_no
                    ),
                    'keyword3' => array(
                        'value' => '充值成功'
                    ),
                    'keyword4' => array(
                        'value' => '+' . $params['pay_money'] . '元'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元'
                    )
                ),
            );

            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            $result = $weixin->templateMessageSend($authorizer_access_token, $data);
        }
    }
    /**
     * 出货/零售产生金额发送模板消息 - 小程序
     * @param string $params
     */
    public function successfulChannelBonusByMpTemplate($params = null) {
        $member_model = new VslMemberAccountModel();
        $member_obj = $member_model->getInfo(['uid' => $params['uid']], '');
        $website_id = $params["website_id"];
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $url = 'pages/member/index';//会员中心
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $params['uid']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        if($params['money_channel_type'] == 2){
            $money_str = '奖金';
        }else{
            $money_str = '出货/零售产生金额';
        }
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 3,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }

        // todo... 小程序通过流水号获取form_id
        if ($mp_open_id && $mp_template_id['mp_template_id']) {
            $out_trade_no = $params['out_trade_no'];
            $member = new memberService();
            $form_id = $member->getRechargeFormIdByOutTradeNo($out_trade_no);
            $data = array(
                'touser' => $mp_open_id,
                'template_id' => $mp_template_id['mp_template_id'],
                'page' => $url,
                'form_id' => $form_id,
                'data' => array(
                    'keyword1' => array(
                        'value' => '您的账户余额发生变动，信息如下'
                    ),
                    'keyword2' => array(
                        'value' => '流水号:' .$out_trade_no
                    ),
                    'keyword3' => array(
                        'value' => '渠道商'.$money_str.'入账成功',
                    ),
                    'keyword4' => array(
                        'value' => '+' . $params['pay_money'] . '元'
                    ),
                    'keyword5' => array(
                        'value' => $member_obj['balance'] . '元'
                    )
                ),
            );

            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            $result = $weixin->templateMessageSend($authorizer_access_token, $data);
        }
    }

    /**
     * 售后退款成功发送模板消息 - 小程序
     * @param string $params
     */
    public function refundSuccessByMpTemplate($params = null) {
        $order_goods_id = $params["order_goods_id"];
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $order_model = new VslOrderGoodsModel();
        $order_id = $params['order_id'];
        if (count($order_goods_id) > 1) {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
            $order_obj = $order_model->Query(['order_goods_id' => ['IN', $order_goods_id]], 'goods_name');
            $goods_name = implode(' ', $order_obj);
            $order = new VslOrderModel();
            $order_no = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no')['order_no'];
            $website_id = $order_info["website_id"];
            $return_money = $order_model->getSum(['order_goods_id' => ['IN', implode(',', $order_goods_id)]], 'refund_real_money');
        } else {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
            $goods_name = $order_info['goods_name'];
            $order = new VslOrderModel();
            $order_no = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no')['order_no'];
            $website_id = $order_info["website_id"];
            $return_money = $order_info['refund_require_money'];
        }
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_info['buyer_id']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 4,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }
        // todo... 售后小程序模板信息form_id
        if ($mp_open_id && $mp_template_id['mp_template_id']) {
            $order_service = new orderService();
            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            foreach ($order_goods_id as  $id) {
                $form_id = $order_service->getOrderRefundFormIdByOrderGoodsId($id);
                $data = array(
                    'touser' => $mp_open_id,
                    'template_id' => $mp_template_id['mp_template_id'],
                    'page' => $url,
                    'form_id' => $form_id,
                    'data' => array(
                        'keyword1' => array(
                            'value' => '仅退款'
                        ),
                        'keyword2' => array(
                            'value' => '退款成功'
                        ),
                        'keyword3' => array(
                            'value' => $goods_name
                        ),
                        'keyword4' => array(
                            'value' => $order_no
                        ),
                        'keyword5' => array(
                            'value' => '您的退款已原路返回，退款金额为' . $return_money . '元，退款可能有延时，请留意到账情况'
                        )
                    ),
                );
                $result = $weixin->templateMessageSend($authorizer_access_token, $data);
            }
        }
    }

    /**
     * 售后拒绝退款发送模板消息 - 小程序
     * @param string $params
     */
    public function refundFailedByMpTemplate($params = null) {
        $order_goods_id = $params["order_goods_id"];
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $order_model = new VslOrderGoodsModel();
        $order_id = $params['order_id'];
        if (count($order_goods_id) > 1) {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
            $order_obj = $order_model->Query(['order_goods_id' => ['IN', $order_goods_id]], 'goods_name');
            $goods_name = implode(' ', $order_obj);
            $order = new VslOrderModel();
            $order_no = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no')['order_no'];
            $website_id = $order_info["website_id"];
        } else {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
            $goods_name = $order_info['goods_name'];
            $order = new VslOrderModel();
            $order_no = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no')['order_no'];
            $website_id = $order_info["website_id"];
        }
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_info['buyer_id']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 4,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }
        // todo... 售后小程序模板信息form_id
        if ($mp_open_id && $mp_template_id['mp_template_id'] && $order_info['refund_type'] == 1) {
            $order_service = new orderService();
            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            foreach ($order_goods_id as  $id) {
                $form_id = $order_service->getOrderRefundFormIdByOrderGoodsId($id);
                    $data = array(
                        'touser' => $mp_open_id,
                        'template_id' => $mp_template_id['mp_template_id'],
                        'page' => $url,
                        'form_id' => $form_id,
                        'data' => array(
                            'keyword1' => array(
                                'value' => '退款'
                            ),
                            'keyword2' => array(
                                'value' => '退款失败'
                            ),
                            'keyword3' => array(
                                'value' => $goods_name
                            ),
                            'keyword4' => array(
                                'value' => $order_no
                            ),
                            'keyword5' => array(
                                'value' =>  $params['refusal'] ? '失败原因: ' . $params['refusal'] . '，如有疑问请联系客服' : '如有疑问请联系客服'
                            )
                        ),
                    );
                    $result = $weixin->templateMessageSend($authorizer_access_token, $data);
                }
        }
    }

    /**
     * 售后同意退货发送模板消息 - 小程序
     * @param string $params
     */
    public function agreedReturnByMpTemplate($params = null) {
        $order_goods_id = $params["order_goods_id"];
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $order_model = new VslOrderGoodsModel();
        $order_id = $params['order_id'];
        if (count($order_goods_id) > 1) {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
            $order_obj = $order_model->Query(['order_goods_id' => ['IN', $order_goods_id]], 'goods_name');
            $goods_name = implode(' ', $order_obj);
            $order = new VslOrderModel();
            $order_no = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no')['order_no'];
            $website_id = $order_info["website_id"];
        } else {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
            $goods_name = $order_info['goods_name'];
            $order = new VslOrderModel();
            $order_no = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no')['order_no'];
            $website_id = $order_info["website_id"];
        }
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_info['buyer_id']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 4,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }
        // todo... 售后小程序模板信息form_id
        if ($mp_open_id && $mp_template_id['mp_template_id'] && $order_info['refund_type'] == 2) {
            $order_service = new orderService();
            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            foreach ($order_goods_id as  $id) {
                $form_id = $order_service->getOrderRefundFormIdByOrderGoodsId($id);
                $data = array(
                    'touser' => $mp_open_id,
                    'template_id' => $mp_template_id['mp_template_id'],
                    'page' => $url,
                    'form_id' => $form_id,
                    'data' => array(
                        'keyword1' => array(
                            'value' => '退货退款'
                        ),
                        'keyword2' => array(
                            'value' => '退货成功'
                        ),
                        'keyword3' => array(
                            'value' => $goods_name
                        ),
                        'keyword4' => array(
                            'value' => $order_no
                        ),
                        'keyword5' => array(
                            'value' =>  '点击"详情"查看商家收货信息，如有疑问请联系客服。'
                        )
                    ),
                );
                $result = $weixin->templateMessageSend($authorizer_access_token, $data);
            }
        }
    }

    /**
     * 售后拒绝退货发送模板消息 - 小程序
     * @param string $params
     */
    public function refuseReturnByMpTemplate($params = null) {
        $order_goods_id = $params["order_goods_id"];
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $order_model = new VslOrderGoodsModel();
        $order_id = $params['order_id'];
        if (count($order_goods_id) > 1) {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
            $order_obj = $order_model->Query(['order_goods_id' => ['IN', $order_goods_id]], 'goods_name');
            $goods_name = implode(' ', $order_obj);
            $order = new VslOrderModel();
            $order_no = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no')['order_no'];
            $website_id = $order_info["website_id"];
        } else {
            $order_info = $order_model->getInfo(['order_goods_id' => $order_goods_id[0]], '*');
            $url = 'pages/order/detail/index?orderId='.$order_id.'&minnav=true';//订单详情
            $goods_name = $order_info['goods_name'];
            $order = new VslOrderModel();
            $order_no = $order->getInfo(['order_id' => $order_info['order_id']], 'order_no')['order_no'];
            $website_id = $order_info["website_id"];
        }
        if(!getAddons('miniprogram', $website_id)){
            return;
        }
        $user = new UserModel();
        $user_info = $user->getInfo(["uid" => $order_info['buyer_id']], "*");
        $mp_open_id = $user_info['mp_open_id'];
        $shop_id = $user_info['instance_id'];
        $template = new MpTemplateRelationModel();
        $condition = [
            'template_id' => 4,
            'website_id' => $website_id,
            'status' => 1,
            'shop_id' => $shop_id
        ];
        $mp_template_id = $template->getInfo($condition, 'mp_template_id');
        if (empty($mp_template_id['mp_template_id'])) {
            return;
        }
        // todo... 售后小程序模板信息form_id
        if ($mp_open_id && $mp_template_id['mp_template_id']) {
            $order_service = new orderService();
            $wx_auth = new WeixinAuthModel();
            $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
            $authorizer_access_token = $result['authorizer_access_token'] ?: '';
            $weixin = new WchatOpen($website_id);
            foreach ($order_goods_id as  $id) {
                $form_id = $order_service->getOrderRefundFormIdByOrderGoodsId($id);
                $data = array(
                    'touser' => $mp_open_id,
                    'template_id' => $mp_template_id['mp_template_id'],
                    'page' => $url,
                    'form_id' => $form_id,
                    'data' => array(
                        'keyword1' => array(
                            'value' => '退货退款'
                        ),
                        'keyword2' => array(
                            'value' => '退货失败'
                        ),
                        'keyword3' => array(
                            'value' => $goods_name
                        ),
                        'keyword4' => array(
                            'value' => $order_no
                        ),
                        'keyword5' => array(
                            'value' =>  $params['refusal'] ? '失败原因: ' . $params['refusal'] . '，如有疑问请联系客服' : '如有疑问请联系客服'
                        )
                    ),
                );
                $result = $weixin->templateMessageSend($authorizer_access_token, $data);
            }
        }
    }
    /**
     * 门店会员注册成功,发送密码
     * @param string $params
     */
    public function sendPassWord($params = null) {
        $mobile = $params["mobile"];
        $shop_id = $params["shop_id"];
        $website_id = $params["website_id"];
        $password = $params["password"];
        $sms_params = array(
            "password" => $password . ""
        );
        return $this->sendMessage('send_password', $mobile, $sms_params, $shop_id, $website_id);
    }
}
