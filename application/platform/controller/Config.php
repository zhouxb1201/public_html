<?php

namespace app\platform\controller;

use addons\cpsunion\server\Cpsunion;
use addons\store\server\Store;
use data\model\CustomTemplateModel;
use data\model\WebSiteModel;
use data\service\Address as DataAddress;
use data\service\Config as WebConfig;
use data\service\GoodsCategory as GoodsCategory;
use data\service\Goods as Goods;
use data\extend\custom\Common;
use data\service\Order as OrderService;
use data\model\MessageCountModel;
use data\model\SysAddonsModel;
use data\model\ConfigModel;
use think\Db;
use data\model\VslBankModel;
use data\service\Upload\AliOss;
/**
 * 网站设置模块控制器
 *
 * @author  www.vslai.com
 *
 */
class Config extends BaseController {

    public $backup_path = "runtime/dbsql/";
    private $dir;
    private $dirDefault;
    private $dir_common; //公共部分路径
    private $dir_shop_common; //店铺公共部分路径
    private $com;
    protected $realm_ip;
    protected $realm_two_ip;
    public function __construct() {
        parent::__construct();
        $this->dir = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/shop_' . $this->instance_id;
        $this->dirDefault = ROOT_PATH . 'public/static/custompc/data/default/tem';
        $this->dir_shop_common = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/shop_' . $this->instance_id . '/common';
        $this->dir_common = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/common';
        $this->com = new Common($this->instance_id, $this->website_id);
        $web_info = $this->website->getWebSiteInfo();
        $this->realm_ip = $web_info['realm_ip'];
        $this->assign('realm_pay_callback',$this->http.$this->realm_ip.'/wapapi/member/wchatcallback');
        $this->assign('realm_ip',$this->realm_ip);
        $call_payback_url1 = $this->realm_ip . '/wap/pay/';
        $this->assign("call_payback_url1", $call_payback_url1);
        $hasHelpcenter = getAddons('helpcenter', $this->website_id);//帮助中心是否存在
        $this->assign('hasHelpcenter',$hasHelpcenter);
        $this->assign('alipay_callback',$this->http.$this->realm_ip.'/wapapi/pay/aliUrlBack');
        $this->assign('apply_back_realm',$this->realm_ip.'/wapapi/login/callback');
        $this->assign('apply_realm',$this->realm_ip);
        $real_ip = $this->http.$this->realm_ip;
        $this->assign('real_ip',$real_ip);
        $this->assign('http',$this->http);
    }
    //银行列表
    public function getBankList()
    {
        $bank = new VslBankModel();
        $bank_list = $bank->getQuery([], 'bank_short_name,bank_iocn', 'sort asc');
        if (empty($bank_list))
        {
            $resBank = $bank->setBankList();
            if ($resBank)
            {
                $bank_list = $bank->getQuery([], 'bank_short_name,bank_iocn', 'sort asc');
            }
        }
        $data['data'] = $bank_list;
        $data['code'] = 1;
        return json($data);
    }

    /**
     * 商城配置
     */
    public function sysConfig() {
        if (request()->isAjax()) {
            $config = new WebConfig();
            $config_type = request()->post('config_type', ''); // 设置类型
            if ($config_type == 'basic' || $config_type == 'realm') {
                $list = $this->website->getWebSiteInfo();
                $list['realm'] = $config->getRealIpConfig();
            }
            if ($config_type == 'copystyle') {
                $list = $config->getStyleConfig($this->instance_id);
            }
            if ($config_type == 'redis') {
                $list = $config->getRedisConfig($this->instance_id);
            }
            if ($config_type == 'wechat') {
                $list = $config->getWechatOpenConfig($this->instance_id);
            }
            if ($config_type == 'validate') {
                $list = $config->getLoginVerifyCodeConfig($this->instance_id)['value'];
            }
            if ($config_type == 'message') {
                $list['template_type_list'] = $config->getTemplateType();
                $list['email_message'] = $config->getEmailMessage($this->instance_id);
                $list['mobile_message'] = $config->getMobileMessage($this->instance_id);
                $messageCount = new MessageCountModel();
                $list['count'] = (int)$messageCount->getInfo(['website_id' => $this->website_id],'count')['count'];
            }
            if ($config_type == 'payment') {
                $list['pay_list'] = $config->getPayConfig($this->instance_id);
                $list['b_set'] = $config->getBpayConfig($this->instance_id);
                $list['d_set'] = $config->getDpayConfig($this->instance_id);
                $list['wx_set'] = $config->getWpayConfig($this->website_id);
                $list['ali_set'] = $config->getAlipayConfig($this->website_id);
                $list['tl_set'] = $config->getTlConfig($this->instance_id,$this->website_id);
                $list['eth_set'] = $config->getEthConfig($this->instance_id,$this->website_id);
                $list['eos_set'] = $config->getEosConfig($this->instance_id,$this->website_id);
				$list['glopay_set'] = $config->getGpayConfig($this->website_id);
            }
            if($config_type == 'tradeset'){
                $list= $config->getShopConfig(0,$this->website_id);
            }
            if($config_type == 'returnsetting'){
                $order_service = new OrderService();
                $list= $order_service->getShopReturnList(0, $this->website_id);
            }
            if($config_type == 'withdrawalset'){
                $config_service = new WebConfig();
                $list = $config_service->getBalanceWithdrawConfig(0);
            }
            if($config_type == 'storageconfig'){
                $web_config = new WebConfig();
                $upload_type = $web_config->getUploadType();
                $list["type"] = $upload_type;
                // 获取七牛参数
                $config_alioss_info = $web_config->getAliossConfig();
                $list["data"]["alioss"] = $config_alioss_info;
                $aliOss = new AliOss();
                $buckets = $aliOss->attachment_alioss_buctkets($list["data"]["alioss"]['Accesskey'], $list["data"]["alioss"]['Secretkey']);
                $list["buckets"] = [];
                foreach($buckets as $val){
                    $list["buckets"][] = $val;
                }
                $list["bucket_datacenter"] = array(
                    'oss-cn-hangzhou' => '杭州数据中心',
                    'oss-cn-qingdao' => '青岛数据中心',
                    'oss-cn-beijing' => '北京数据中心',
                    'oss-cn-hongkong' => '香港数据中心',
                    'oss-cn-shenzhen' => '深圳数据中心',
                    'oss-cn-shanghai' => '上海数据中心',
                    'oss-us-west-1' => '美国硅谷数据中心',
                );
            }
            return $list;
        }
        $config_type = request()->get('type', ''); // 设置类型
        $this->assign('config_type',$config_type);
        $config_service = new WebConfig();
        $config_model = new ConfigModel();
        $info_master = $config_model->getInfo(['key' => 'MOBILEMESSAGE', 'website_id' => 0, 'instance_id' => 0], '*');
        $master_array = json_decode($info_master['value'], true);
        $jd_sms = 1;//判断京东万象是否配置
        if(!$master_array['userid'] || !$master_array['username'] || !$master_array['password']){
            $jd_sms = 0;
        }
        //是否有小程序，有的话才需要配置微信开放平台
        $miniprogram = getAddons('miniprogram', $this->website_id);
        $this->assign('jd_sms',$jd_sms);
        $this->assign('miniprogram',$miniprogram);
        $this->assign('alipay',$alipay);
        $this->assign('wpay',$wpay);
	$wchat_config = $config_service->getInstanceWchatConfig($this->instance_id, $this->website_id);
        $appid = $wchat_config["value"]['appid'];
        $this->assign('appid',$appid);
        $blockchain = getAddons('blockchain',$this->website_id);
        $this->assign('blockchain',$blockchain);
        //门店
        if(getAddons('store',$this->website_id)) {
            $store_server = new Store();
            $store = $store_server->getStoreSet(0)['is_use'];
        }else{
            $store = 0;
        }
        $this->assign('store',$store);
        return view($this->style . "Config/shopConfig");
    }
    /*
      * 配置物流查询
      * * */
   public function setCompany(){
       $Config = new WebConfig();
       if (request()->isPost()) {
           $keyValue = request()->post("keyValue", '');
           $retval = $Config->setCompanyConfig($keyValue);
           return AjaxReturn($retval);
       }
       $config_service = new WebConfig();
       $value = $config_service->getConfig(0, 'COMPANYCONFIGSET')['value'];
       return $value;
   }
    /*
     * 更改模板是否开启短信、邮箱验证
     * * */

    public function updateNoticeTemplateEnable() {
        $config = new WebConfig();
        $template_code = request()->post('template_code', '');
        $template_type = request()->post('model', '');
        $is_enable = request()->post('is_enable', 0);
        $website_id = $this->website_id;
        $instance_id = $this->instance_id;
        $condition = [
            'template_code' => $template_code,
            'template_type' => $template_type,
            'website_id' => $website_id,
            'instance_id' => $instance_id,
        ];
        $bool = $config->updateNoticeTemplateEnable($condition, $is_enable);
        if ($bool) {
            $this->addUserLogByParam('更改模板是否开启短信、邮箱验证', '模板类型：' . $template_type . '，模板名称：' . $template_code);
        }
        return AjaxReturn($bool);
    }

    /*
     * 更改配置的支付方式、第三方登录方式是否启用
     * * */

    public function updateConfigIsuse() {
        $config = new WebConfig();
        $id = request()->post('id', 0);
        $is_use = request()->post('is_use', 0);
        $condition = array(
            'instance_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'id' => $id
        );
        $bool = $config->updateConfigIsuse($condition, $is_use);
        if ($bool) {
            $this->addUserLogByParam('更改配置的支付方式、第三方登录方式是否启用', '支付方式或者第三方登录方式id：' . $id);
        }
        return AjaxReturn($bool);
    }

    /**
     * 基本设置
     */
    public function webConfig() {
        if (request()->isAjax()) {
            // 网站设置
            $data = array();
            $data['wap_status'] = request()->post("wap_status", 0); // 手机端网站运营状态
            $data['wap_register_adv'] = request()->post("wap_register_adv", ''); // 手机端注册广告图
            $data['wap_register_jump'] = request()->post("wap_register_jump", ''); // 手机端注册广告图跳转
            $data['wap_login_adv'] = request()->post("wap_login_adv", ''); // 手机端登陆广告图
            $data['wap_login_jump'] = request()->post("wap_login_jump", ''); // 手机端登陆广告图跳转
            $data['wap_pop'] = request()->post("wap_pop", '0'); // 手机端首页弹窗广告
            $data['wap_pop_adv'] = request()->post("wap_pop_adv", ''); // 手机端首页弹窗广告图
            $data['wap_pop_jump'] = request()->post("wap_pop_jump", ''); // 手机端首页弹窗广告跳转
            $data['wap_pop_rule'] = request()->post("wap_pop_rule", ''); // 手机端弹窗规则
            $data['pur_id'] = request()->post("pur_id", '0'); // 用户购买协议
            $data['reg_id'] = request()->post("reg_id", '0'); // 用户注册协议
            $data['reg_rule'] = request()->post("reg_rule", '0'); // 用户购买协议
            $data['pur_rule'] = request()->post("pur_rule", '0'); // 用户注册协议
            $data['close_reason'] = request()->post("close_reason", '0'); // 关闭原因
            $data['modify_time'] = time();
            $data['mall_name'] = request()->post("mall_name", ''); // 商城名称
            $data['default_url'] = $this->http.$_SERVER['HTTP_HOST']; // 后台url
            $data['logo'] = request()->post("logo", ''); // 商城logo
            $retval = $this->website->updateWebSite($data);
            $this->codeConfig();
            $Config = new WebConfig();
            $Config->setStyleConfig(0,0,'积分','余额');
            if ($retval) {
                $this->addUserLogByParam('系统商城基本设置保存', $retval);
            }
            return AjaxReturn($retval);
        }
    }

    /**
     * 文案样式设置
     */
    public function styleConfig() {
        $Config = new WebConfig();
        $point_style = request()->post("point_style", '积分');
        $balance_style = request()->post("balance_style", '余额');
        $retval = $Config->setStyleConfig(1,0, $point_style, $balance_style);
        return AjaxReturn($retval);
    }
    /**
     * redis设置
     */
    public function redisConfig() {
        $Config = new WebConfig();
        $host = request()->post("host", '');
        $pass = request()->post("pass", '');
        $retval = $Config->setRedisConfig(0, $host, $pass);
        return AjaxReturn($retval);
    }
    /**
     * 微信开放平台设置
     */
    public function wechatOpenConfig() {
        $Config = new WebConfig();
        $open_appid = request()->post("open_appid", '');
        $open_secrect = request()->post("open_secrect", '');
        $open_key = request()->post("open_key", '');
        $open_token = request()->post("open_token", '');
        $retval = $Config->setWechatOpenConfig(0, $open_appid, $open_secrect, $open_key, $open_token);
        return AjaxReturn($retval);
    }


    /**
     * 验证码设置
     */
    public function codeConfig() {
        $webConfig = new WebConfig();
        if (request()->isAjax()) {
            $pc = request()->post('pcCode', 0);
            $res = $webConfig->setLoginVerifyCodeConfig($this->instance_id, $pc);
            return AjaxReturn($res);
        }
    }
    public function selectWapUrl() {
        $config['shop'] = getAddons('shop',$this->website_id,0,true);
        $config['distribution'] = getAddons('distribution',$this->website_id,0,true);
        $config['areabonus'] = getAddons('areabonus',$this->website_id,0,true);
        $config['globalbonus'] = getAddons('globalbonus',$this->website_id,0,true);
        $config['teambonus'] = getAddons('teambonus',$this->website_id,0,true);
        $config['coupontype'] = getAddons('coupontype',$this->website_id,0,true);
        $config['microshop'] = getAddons('microshop',$this->website_id,0,true);
        $config['integral'] = getAddons('integral',$this->website_id,0,true);
        $config['channel'] = getAddons('channel',$this->website_id,0,true);
        $config['seckill'] = getAddons('seckill',$this->website_id,0,true);
        $config['presell'] = getAddons('presell',$this->website_id,0,true);
        $config['groupshopping'] = getAddons('groupshopping',$this->website_id,0,true);
        $config['bargain'] = getAddons('bargain',$this->website_id,0,true);
        $config['signin'] = getAddons('signin',$this->website_id,0,true);
        $config['followgift'] = getAddons('followgift',$this->website_id,0,true);
        $config['festivalcare'] = getAddons('festivalcare',$this->website_id,0,true);
        $config['paygift'] = getAddons('paygift',$this->website_id,0,true);
        $config['scratchcard'] = getAddons('scratchcard',$this->website_id,0,true);
        $config['smashegg'] = getAddons('smashegg',$this->website_id,0,true);
        $config['wheelsurf'] = getAddons('smashegg',$this->website_id,0,true);
        $config['qlkefu'] = getAddons('qlkefu',$this->website_id,0,true);
        if($config['followgift'] || $config['festivalcare'] || $config['paygift'] || $config['scratchcard'] || $config['smashegg'] || $config['wheelsurf']){
            $config['memberprize'] = 1;
        }else{
            $config['memberprize'] = 0;
        }
        $this->assign('config', $config);
        $this->assign('shop_id',$this->instance_id);
        return view($this->style . 'Config/linksDialog');
    }
    public function selectMinUrl() {
        $config['shop'] = getAddons('shop',$this->website_id,0,true);
        $config['distribution'] = getAddons('distribution',$this->website_id,0,true);
        $config['areabonus'] = getAddons('areabonus',$this->website_id,0,true);
        $config['globalbonus'] = getAddons('globalbonus',$this->website_id,0,true);
        $config['teambonus'] = getAddons('teambonus',$this->website_id,0,true);
        $config['coupontype'] = getAddons('coupontype',$this->website_id,0,true);
        $config['microshop'] = getAddons('microshop',$this->website_id,0,true);
        $config['integral'] = getAddons('integral',$this->website_id,0,true);
        $config['channel'] = getAddons('channel',$this->website_id,0,true);
        $config['seckill'] = getAddons('seckill',$this->website_id,0,true);
        $config['presell'] = getAddons('presell',$this->website_id,0,true);
        $config['groupshopping'] = getAddons('groupshopping',$this->website_id,0,true);
        $config['bargain'] = getAddons('bargain',$this->website_id,0,true);
        $config['signin'] = getAddons('signin',$this->website_id,0,true);
        $config['followgift'] = getAddons('followgift',$this->website_id,0,true);
        $config['festivalcare'] = getAddons('festivalcare',$this->website_id,0,true);
        $config['paygift'] = getAddons('paygift',$this->website_id,0,true);
        $config['scratchcard'] = getAddons('scratchcard',$this->website_id,0,true);
        $config['smashegg'] = getAddons('smashegg',$this->website_id,0,true);
        $config['wheelsurf'] = getAddons('smashegg',$this->website_id,0,true);
        $config['qlkefu'] = getAddons('qlkefu',$this->website_id,0,true);
        if($config['followgift'] || $config['festivalcare'] || $config['paygift'] || $config['scratchcard'] || $config['smashegg'] || $config['wheelsurf']){
            $config['memberprize'] = 1;
        }else{
            $config['memberprize'] = 0;
        }
        $this->assign('config', $config);
        $this->assign('shop_id',$this->instance_id);
        return view($this->style . 'Config/linksMinDialog');
    }
    public function selectKey() {
        return view($this->style . 'Config/linksKeyDialog');
    }
    /**
     * ajax 邮件接口
     */
    public function setEmailMessage() {
        $email_host = request()->post('email_host', '');
        $email_port = request()->post('email_port', '');
        $email_addr = request()->post('email_addr', '');
        $email_id = request()->post('email_id', '');
        $email_pass = request()->post('email_pass', '');
        $is_use = request()->post('is_use', 0);
        $email_is_security = request()->post('email_is_security', false);
        $config = new WebConfig();
        $res = $config->setEmailMessage($this->instance_id, $email_host, $email_port, $email_addr, $email_id, $email_pass, $is_use, $email_is_security);
        return AjaxReturn($res);
    }

    /**
     * ajax 短信接口
     */
    public function setMobileMessage() {
        $data['appKey'] = request()->post('app_key', '');
        $data['secretKey'] = request()->post('secret_key', '');
        $data['freeSignName'] = request()->post('free_sign_name', '');
        $data['user_type'] = request()->post('user_type', 1);
        $data['alarm_mobile'] = request()->post('alarm_mobile', '');
        $data['alarm_num'] = request()->post('alarm_num', '');
        $data['is_use'] = request()->post('is_use', '');
        $data['jd_sign_name'] = request()->post('jd_sign_name', '');
        $data['international'] = request()->post('international', 0);
        $data['int_sign_name'] = request()->post('int_sign_name', '');
        $data['instance_id'] = $this->instance_id;
        $config = new WebConfig();
        $res = $config->setMobileMessage($data);
        return AjaxReturn($res);
    }

    /**
     * @param int $convert_rate 积分抵扣 定义多少积分等于多少钱，用于退款退货
     * @param int $shopping_back_points 购物返积分节点 1-订单已完成 2-已收货 3-支付完成
     * @param int $point_invoice_tax 购物返积分比例
     * @param int $is_point 购物返积分是否开启 0-未开启 1-开启
     * @param int $integral_calculation 积分计算方式 1-订单总价 2-商品总价 3-实际支付金额
     * @param int $is_translation 是否开启自动评论 1-开启 0关闭
     * @param int $translation_time 自动评论时间
     * @param int $translation_text 自动评论内容
     * * 余额转账设置
     * @param int $is_transfer, 1开启余额转账 0不开启
     * @param int $is_transfer_charge, 1开启转账费率
     * @param int $charge_type, 费率类型 1比例 2固定
     * @param int $charge_pares, 费率比例
     * @param int $charge_pares_min, 费率最低
     * @param int $charge_pares2, 费率固定
     * 余额积分转账设置
     * @param int $is_point_transfer, 1开启积分余额转账 0不开启
     * @param int $is_point_transfer_charge, 1开启转账费率
     * @param int $point_charge_type, 费率类型 1比例 2固定
     * @param int $point_charge_pares, 费率比例
     * @param int $point_charge_pares_min, 费率最低
     * @param int $point_charge_pares2,费率固定
     * 交易设置
     */
    public function shopSet() {
        if (request()->isAjax()) {
            $has_express = request()->post("has_express", '');
            $order_auto_delivery = request()->post("order_auto_delivery", '') ? request()->post("order_auto_delivery", '') : 0; //
            $order_delivery_complete_time = request()->post("order_delivery_complete_time", '') ? request()->post("order_delivery_complete_time", '') : 0; //
            $convert_rate = request()->post("convert_rate", '') ? request()->post("convert_rate", '') : ''; //
            $order_buy_close_time = request()->post("order_buy_close_time", '') ? request()->post("order_buy_close_time", '') : 0; //
            $is_point = request()->post("is_point", '0'); //
            $integral_calculation = request()->post("integral_calculation", '') ? request()->post("integral_calculation", '') : 0; //
            $point_invoice_tax = request()->post("point_invoice_tax", '') ? request()->post("point_invoice_tax", '') : 0; //
            $shopping_back_points = request()->post("shopping_back_points", '') ? request()->post("shopping_back_points", '') : 0; //
            $is_point_deduction = request()->post("is_point_deduction", '0'); 
            $point_deduction_calculation = request()->post("point_deduction_calculation", '') ? request()->post("point_deduction_calculation", '') : 0; 
            $point_deduction_max = request()->post("point_deduction_max", '') ? (int)request()->post("point_deduction_max", '') : '';

            $is_translation = request()->post("is_translation", '') ? request()->post("is_translation", '') : 0; 
            $translation_time = request()->post("translation_time", '') ? request()->post("translation_time", '') : 0; 
            $translation_text = request()->post("translation_text", '') ? request()->post("translation_text", '') : ''; 

            //余额转账设置
            $is_transfer = request()->post("is_transfer", '') ? request()->post("is_transfer", '') : 0; 
            $is_transfer_charge = request()->post("is_transfer_charge", '') ? request()->post("is_transfer_charge", '') : 0; 
            $charge_type = request()->post("charge_type", '') ? request()->post("charge_type", '') : 1; 
            $charge_pares = request()->post("charge_pares", '') ? request()->post("charge_pares", '') : 0; 
            $charge_pares_min = request()->post("charge_pares_min", '') ? request()->post("charge_pares_min", '') : 0; 
            $charge_pares2 = request()->post("charge_pares2", '') ? request()->post("charge_pares2", '') : 0; 
            //积分余额转账设置
            $is_point_transfer = request()->post("is_point_transfer", '') ? request()->post("is_point_transfer", '') : 0; 
            $is_point_transfer_charge = request()->post("is_point_transfer_charge", '') ? request()->post("is_point_transfer_charge", '') : 0; 
            $point_charge_type = request()->post("point_charge_type", '') ? request()->post("point_charge_type", '') : 1; 
            $point_charge_pares = request()->post("point_charge_pares", '') ? request()->post("point_charge_pares", '') : 0; 
            $point_charge_pares_min = request()->post("point_charge_pares_min", '') ? request()->post("point_charge_pares_min", '') : 0; 
            $point_charge_pares2 = request()->post("point_charge_pares2", '') ? request()->post("point_charge_pares2", '') : 0; 

            $Config = new WebConfig();
            
            $retval = $Config->setShopConfig(0, $order_auto_delivery, $convert_rate, $order_delivery_complete_time, $order_buy_close_time, $shopping_back_points, $point_invoice_tax, $is_point, $integral_calculation,$is_point_deduction,$point_deduction_calculation,$point_deduction_max,$is_translation,$translation_time,$translation_text, $is_transfer,$is_transfer_charge,$charge_type,$charge_pares,$charge_pares_min,$charge_pares2, $is_point_transfer,$is_point_transfer_charge,$point_charge_type,$point_charge_pares,$point_charge_pares_min,$point_charge_pares2,$has_express);
            if ($retval) {
                $this->addUserLogByParam('系统交易设置保存', $retval);
            }
            return AjaxReturn($retval);
        }
    }

    /**
     * 提现设置
     */
    public function memberWithdrawSetting() {
        if (request()->isAjax()) {
            $key = 'WITHDRAW_BALANCE';
            $value = array(
                'withdraw_cash_min' => $_POST['cash_min'] ? $_POST['cash_min'] : 0,
                'withdraw_poundage' => $_POST['poundage'] ? $_POST['poundage'] : 0,
                'member_withdraw_poundage' => $_POST['member_poundage'] ? $_POST['member_poundage'] : 0,
                'withdraw_message' => $_POST['message'] ? $_POST['message'] : '',
                'is_examine' => $_POST['is_examine'] ? $_POST['is_examine'] : '',
                'make_money' => $_POST['make_money'] ? $_POST['make_money'] : '',
                'withdrawals_begin' => $_POST['withdrawals_begin'] ? $_POST['withdrawals_begin'] : '',
                'withdrawals_end' => $_POST['withdrawals_end'] ? $_POST['withdrawals_end'] : ''
            );
            $is_use = $_POST['is_use'];
            $config_service = new WebConfig();
            $retval = $config_service->setBalanceWithdrawConfig(0, $key, $value, $is_use);
            if ($retval) {
                $this->addUserLogByParam('系统提现设置保存', $retval);
            }
            return AjaxReturn($retval);
        }
    }

    /**
     * 地区管理
     */
    public function areaManagement() {
        $dataAddress = new DataAddress();
        $area_list = $dataAddress->getAreaList(); // 区域地址
        $list = $dataAddress->getProvinceList();
        foreach ($list as $k => $v) {
            if ($dataAddress->getCityCountByProvinceId($v['province_id']) > 0) {
                $v['issetLowerLevel'] = 1;
            } else {
                $v['issetLowerLevel'] = 0;
            }
            if (!empty($area_list)) {
                foreach ($area_list as $area) {
                    if ($area['area_id'] == $v['area_id']) {
                        $list[$k]['area_name'] = $area['area_name'];
                        break;
                    }
                }
            }
        }
        $this->assign("area_list", $area_list);
        $this->assign("list", $list);
        return view($this->style . 'Config/areaManagement');
    }

    public function selectCityListAjax() {
        if (request()->isAjax()) {
            $province_id = request()->post('province_id', '');
            $dataAddress = new DataAddress();
            $list = $dataAddress->getCityList($province_id);
            foreach ($list as $v) {
                if ($dataAddress->getDistrictCountByCityId($v['city_id']) > 0) {
                    $v['issetLowerLevel'] = 1;
                } else {
                    $v['issetLowerLevel'] = 0;
                }
            }
            return $list;
        }
    }

    public function selectDistrictListAjax() {
        if (request()->isAjax()) {
            $city_id = request()->post('city_id', '');
            $dataAddress = new DataAddress();
            $list['0'] = $dataAddress->getDistrictList($city_id);
            $list['1'] = $this->website_id;
            return $list;
        }
    }

    public function addCityAjax() {
        if (request()->isAjax()) {
            $dataAddress = new DataAddress();
            $city_id = 0;
            $province_id = request()->post('superiorRegionId', '');
            $city_name = request()->post('regionName', '');
            $zipcode = request()->post('zipcode', '');
            $sort = request()->post('regionSort', '');
            $res = $dataAddress->addOrupdateCity($city_id, $province_id, $city_name, $zipcode, $sort);
            if ($res) {
                $this->addUserLogByParam('添加市级区域', $res);
            }
            return AjaxReturn($res);
        }
    }

    public function updateCityAjax() {
        if (request()->isAjax()) {
            $dataAddress = new DataAddress();
            $city_id = request()->post('eventId', '');
            $province_id = request()->post('superiorRegionId', '');
            $city_name = request()->post('regionName', '');
            $zipcode = request()->post('zipcode', '');
            $sort = request()->post('regionSort', '');
            $res = $dataAddress->addOrupdateCity($city_id, $province_id, $city_name, $zipcode, $sort);
            if ($res) {
                $this->addUserLogByParam('修改市级区域', $res);
            }
            return AjaxReturn($res);
        }
    }

    public function addDistrictAjax() {
        if (request()->isAjax()) {
            $dataAddress = new DataAddress();
            $district_id = 0;
            $city_id = request()->post('superiorRegionId', '');
            $district_name = request()->post('regionName', '');
            $sort = request()->post('regionSort', '');
            $res = $dataAddress->addOrupdateDistrict($district_id, $city_id, $district_name, $sort);
            if ($res) {
                $this->addUserLogByParam('添加县级区域', $res);
            }
            return AjaxReturn($res);
        }
    }

    public function updateDistrictAjax() {
        if (request()->isAjax()) {
            $dataAddress = new DataAddress();
            $district_id = request()->post('eventId', '');
            $city_id = request()->post('superiorRegionId', '');
            $district_name = request()->post('regionName', '');
            $sort = request()->post('regionSort', '');
            $res = $dataAddress->addOrupdateDistrict($district_id, $city_id, $district_name, $sort);
            if ($res) {
                $this->addUserLogByParam('修改县级区域', $res);
            }
            return AjaxReturn($res);
        }
    }

    public function updateProvinceAjax() {
        if (request()->isAjax()) {
            $dataAddress = new DataAddress();
            $province_id = request()->post('eventId', '');
            $province_name = request()->post('regionName', '');
            $sort = request()->post('regionSort', '');
            $area_id = request()->post('area_id', '');
            $res = $dataAddress->updateProvince($province_id, $province_name, $sort, $area_id);
            if ($res) {
                $this->addUserLogByParam('修改省级区域', $res);
            }
            return AjaxReturn($res);
        }
    }

    public function addProvinceAjax() {
        if (request()->isAjax()) {
            $dataAddress = new DataAddress();
            $province_name = request()->post('regionName', ''); // 区域名称
            $sort = request()->post('regionSort', ''); // 排序
            $area_id = request()->post('area_id', 0); // 区域id
            $res = $dataAddress->addProvince($province_name, $sort, $area_id);
            if ($res) {
                $this->addUserLogByParam('添加省级区域', $res);
            }
            return AjaxReturn($res);
        }
    }

    public function deleteRegion() {
        if (request()->isAjax()) {
            $type = request()->post('type', '');
            $regionId = request()->post('regionId', '');
            $dataAddress = new DataAddress();
            if ($type == 1) {
                $res = $dataAddress->deleteProvince($regionId);
                return AjaxReturn($res);
            }
            if ($type == 2) {
                $res = $dataAddress->deleteCity($regionId);
                return AjaxReturn($res);
            }
            if ($type == 3) {
                $res = $dataAddress->deleteDistrict($regionId);
                return AjaxReturn($res);
            }
        }
    }

    public function updateRegionAjax() {
        if (request()->isAjax()) {
            $dataAddress = new DataAddress();
            $upType = request()->post('upType', '');
            $regionType = request()->post('regionType', '');
            $regionName = request()->post('regionName', '');
            $regionSort = request()->post('regionSort', '');
            $regionId = request()->post('regionId', '');
            $res = $dataAddress->updateRegionNameAndRegionSort($upType, $regionType, $regionName, $regionSort, $regionId);
            return AjaxReturn($res);
        }
    }

    /**
     * 支付方式列表
     */
    public function paymentConfig() {
        $config_service = new WebConfig();
        $shop_id = $this->instance_id;
        $pay_list = $config_service->getPayConfig($shop_id);
        $this->assign("pay_list", $pay_list);
        return view($this->style . 'Config/paymentConfig');
    }
	/**
     * GlobePay配置
     */
    public function glopayConfig() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            // GlobePay
            $appid = str_replace(' ', '', request()->post('appid', ''));
            $partner_code = str_replace(' ', '', request()->post('partner_code', ''));
            $credential_code = str_replace(' ', '', request()->post('credential_code', ''));
			$currency = str_replace(' ', '', request()->post('currency', ''));
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setGpayConfig($this->instance_id, $appid,$partner_code,$credential_code,$currency,$is_use);
            return AjaxReturn($retval);
        }
    }
    /**
     * 微信支付配置
     */
    public function payConfig() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            // 微信支付
            $appkey = str_replace(' ', '', request()->post('appkey', ''));
            $paySignKey = str_replace(' ', '', request()->post('paySignKey', ''));
            $MCHID = str_replace(' ', '', request()->post('MCHID', ''));
            $certkey = request()->post('certkey', '');
            $cert = request()->post('cert', '');
            $is_use = request()->post('is_use', 0);
            $wx_tw = request()->post('wx_tw', 0);
            // 获取数据
            $retval = $web_config->setWpayConfig($this->instance_id, $appkey, $MCHID, $paySignKey, $is_use, $certkey, $cert,$wx_tw);
            return AjaxReturn($retval);
        }
    }

    /**
     * 余额支付配置
     */
    public function bPayConfig() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setBpayConfig($this->instance_id, '', '', '', $is_use);
            return AjaxReturn($retval);
        }
    }

    /**
     * 货到付款配置
     */
    public function dPayConfig() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setDpayConfig($this->instance_id, '', '', '', $is_use);
            return AjaxReturn($retval);
        }
    }
    /**
     * eth付款配置
     */
    public function ethPayConfig() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setEthConfig($this->instance_id, $is_use);
            return AjaxReturn($retval);
        }
    }
    /**
     * eos付款配置
     */
    public function eosPayConfig() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setEosConfig($this->instance_id, $is_use);
            return AjaxReturn($retval);
        }
    }
    /**
     * 通联支付配置
     */
    public function tlPayConfig() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            $tl_key = str_replace(' ', '', request()->post('tl_key', ''));
            $tl_appid = str_replace(' ', '', request()->post('tl_appid', ''));
            $tl_cusid = str_replace(' ', '', request()->post('tl_cusid', ''));
            $tl_id = str_replace(' ', '', request()->post('tl_id', ''));
            $tl_username = str_replace(' ', '', request()->post('tl_username', ''));
            $tl_password = str_replace(' ', '', request()->post('tl_password', ''));
            $tl_public = str_replace(' ', '', request()->post('tl_public', ''));
            $tl_private = str_replace(' ', '', request()->post('tl_private', ''));
            $is_use = request()->post('is_use', 0);
            $tl_tw = request()->post('tl_tw', 0);
            // 获取数据
            $retval = $web_config->settlConfig($this->instance_id,$tl_id, $tl_cusid, $tl_appid, $tl_key,$tl_username,$tl_password,$tl_public, $tl_private,$tl_tw,$is_use);
            return AjaxReturn($retval);
        }
    }
    /**
     * 支付宝配置
     */
    public function payAliConfig() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            // 支付宝
            $partnerid = str_replace(' ', '', request()->post('ali_partnerid', ''));
            $seller = str_replace(' ', '', request()->post('ali_seller', ''));
            $ali_key = str_replace(' ', '', request()->post('ali_key', ''));
            $appid = trim(request()->post('appid', ''));
            $ali_public_key = trim(request()->post('ali_public_key', ''));
            $ali_private_key = trim(request()->post('ali_private_key', ''));
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setAlipayConfig($this->instance_id, $partnerid, $seller, $ali_key, $is_use, $appid, $ali_public_key, $ali_private_key);
            return AjaxReturn($retval);
        }
    }

    /**
     * 设置微信和支付宝开关状态是否启用
     */
    public function setStatus() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            $is_use = request()->post("is_use", '');
            $type = request()->post("type", '');
            $retval = $web_config->setWpayStatusConfig($this->instance_id, $is_use, $type);
            return AjaxReturn($retval);
        }
    }
    
    /**
     * 商家地址详情
     */
    public function getShopReturn() {
        $order_service = new OrderService();
        $return_id = request()->post('return_id', 0);
        $shop_id = $this->instance_id;
        $website_id = $this->website_id;
        $info = $order_service->getShopReturn($return_id,$shop_id,$website_id);
        return $info;
    }
    
    /**
     * 商家地址
     */
    public function getShopReturnList() {
        $order_service = new OrderService();
        $list= $order_service->getShopReturnList($this->instance_id, $this->website_id);
        return $list;
    }

    /**
     * 商家地址
     */
    public function returnSetting() {
        $order_service = new OrderService();
        $shop_id = $this->instance_id;
        if (request()->isAjax()) {
            $return_id = request()->post('return_id', 0);
            $consigner = request()->post('consigner', '');
            $mobile = request()->post('mobile', '');
            $province = request()->post('province', '');
            $city = request()->post('city', '');
            $district = request()->post('district', '');
            $address = request()->post('address', '');
            $zip_code = request()->post('zip_code', '');
            $is_default = request()->post('is_default', 0);
            $retval = $order_service->updateShopReturnSet($shop_id,$return_id,$consigner,$mobile,$province,$city,$district,$address,$zip_code,$is_default);
            if ($retval) {
                $this->addUserLogByParam('系统商家地址保存', $retval);
            }
            return AjaxReturn($retval);
        }
    }
    
    /**
     * 删除商家地址
     */
    public function returnDelete() {
        $order_service = new OrderService();
        $shop_id = $this->instance_id;
        $return_id = request()->post('return_id', 0);
        $retval = $order_service->deleteShopReturnSet($shop_id,$return_id);
        if ($retval) {
            $this->addUserLogByParam('系统商家地址删除', $retval);
        }
        return AjaxReturn($retval);
    }

    /**
     * 修改短信通知模板
     */
    public function notifySmsTemplate() {
        $type = 'sms';
        $config_service = new WebConfig();
        $notify_type = request()->post('notify_type', '');
        $template_code = request()->post('template_code', '');
        $template_detail = $config_service->getNoticeTemplateDetail($template_code, $type, $notify_type);
        $template_type_list = $config_service->getNoticeTemplateType($notify_type, $template_code);
        $template_item_list = $config_service->getNoticeTemplateItem($template_code, ['all', 'sms']);
        $mobile_message = $config_service->getMobileMessage($this->instance_id);
        $value = $mobile_message['value']['user_type'];
        $international = $mobile_message['value']['international'];
        $item_list['list'] = $template_item_list;
        $item_list['message'] = $value;
        $item_list['international'] = $international;
        $item_list["template_content"] = str_replace(PHP_EOL, '', $template_detail["template_content"]);
        $item_list["is_enable"] = (int)$template_detail['is_enable'];
        $item_list["template_title"] = $template_detail['template_title'] ? : '';
        $item_list["int_template_title"] = $template_detail['int_template_title'] ? : '';
        $item_list["template_name"] = $template_type_list['template_name'] ? : '';
        $item_list["notification_mode"] = $template_detail['notification_mode'] ? : '';
        $item_list["sample"] = $template_type_list['sample'] ? : '';
        return $item_list;
    }

    /**
     * 修改邮箱通知模板
     */
    public function notifyEmailTemplate() {
        $type = 'email';
        $config_service = new WebConfig();
        $notify_type = request()->post('notify_type', '');
        $template_code = request()->post('template_code', '');
        $template_detail = $config_service->getNoticeTemplateDetail($template_code, $type, $notify_type);
        $template_type_list = $config_service->getNoticeTemplateType($notify_type, $template_code);
        $template_item_list = $config_service->getNoticeTemplateItem($template_code, ['all', 'email']);
        $item_list['list'] = $template_item_list;
        $item_list['template_content'] = str_replace(PHP_EOL, '', $template_detail['template_content']);
        $item_list['is_enable'] = $template_detail['is_enable']? : '';;
        $item_list['template_title'] = $template_detail['template_title']? : '';
        $item_list['template_name'] = $template_type_list['template_name']? : '';
        $item_list['notification_mode'] = $template_detail['notification_mode']? : '';
        return $item_list;
    }

    /**
     * 更新通知模板
     */
    public function updateNotifyTemplate() {
        $is_enable = request()->post('is_enable', 0);
        $template_type = request()->post('type', '');
        $template_code = request()->post('template_code', '');
        $template_content = request()->post('template_content', '');
        $template_title = request()->post('template_title', '');
        $int_template_title = request()->post('int_template_title', '');
        $notify_type = request()->post("notify_type", "");
        $notification_mode = request()->post("notification_mode", "");
        $config_service = new WebConfig();
        $retval = $config_service->updateNoticeTemplate($is_enable, $template_type, $template_code, $template_content, $template_title, $notify_type, $notification_mode,$int_template_title);
        return AjaxReturn($retval);
    }


    /**
     * 邮件短信接口设置
     */
    public function messageConfig() {
        $type = request()->get('type', 'email');
        if ($type == 'email') {
            $child_menu_list = array(
                array(
                    'url' => "Config/messageConfig?type=email",
                    'menu_name' => "邮箱设置",
                    "active" => 1
                ),
                array(
                    'url' => "Config/messageConfig?type=sms",
                    'menu_name' => "短信设置",
                    "active" => 0
                )
            );
        } else {
            $child_menu_list = array(
                array(
                    'url' => "Config/messageConfig?type=email",
                    'menu_name' => "邮箱设置",
                    "active" => 0
                ),
                array(
                    'url' => "Config/messageConfig?type=sms",
                    'menu_name' => "短信设置",
                    "active" => 1
                )
            );
        }
        $config = new WebConfig();
        $email_message = $config->getEmailMessage($this->instance_id);
        $this->assign('email_message', $email_message);
        $mobile_message = $config->getMobileMessage($this->instance_id);
        $this->assign('mobile_message', $mobile_message);
        $this->assign('child_menu_list', $child_menu_list);
        $this->assign('type', $type);
        return view($this->style . 'Config/messageConfig');
    }

    /**
     * 短信通知设置
     */
    public function messagesmsconfig() {
        $config = new WebConfig();
        $type = 'sms';
        $email_message = $config->getEmailMessage($this->instance_id);
        $this->assign('email_message', $email_message);
        $mobile_message = $config->getMobileMessage($this->instance_id);
        $this->assign('mobile_message', $mobile_message);
        $this->assign('type', $type);
        return view($this->style . 'Config/messagesmsconfig');
    }

    /**
     * 邮件通知设置
     * @return \think\response\View
     */
    public function messageemailconfig() {
        $config = new WebConfig();
        $type = 'email';
        $email_message = $config->getEmailMessage($this->instance_id);
        $this->assign('email_message', $email_message);
        $mobile_message = $config->getMobileMessage($this->instance_id);
        $this->assign('mobile_message', $mobile_message);
        $this->assign('type', $type);
        return view($this->style . 'Config/messageemailconfig');
    }

    public function searchGoods() {
        $goods_name = request()->post('goods_name', '');
        $category_id = request()->post('category_id', '');
        $category_level = request()->post('category_level', '');
        $where['ng.goods_name'] = array(
            'like',
            '%' . $goods_name . '%'
        );
        $where['ng.category_id_' . $category_level] = $category_id;
        $where['ng.state'] = 1;
        $where = array_filter($where);
        $goods = new Goods();
        $list = $goods->getGoodsList(1, 0, $where);
        return $list;
    }

    /**
     * 开启和关闭 邮件 和短信的开启和 关闭
     */
    public function updateNotifyEnable() {
        $id = request()->post('id', '');
        $is_use = request()->post('is_use', '');
        $config_service = new WebConfig();
        $retval = $config_service->updateConfigEnable($id, $is_use);
        return AjaxReturn($retval);
    }

    /**
     * 数据库列表
     */
    public function databaseList() {
        if (request()->isAjax()) {
            $web_config = new WebConfig();
            $database_list = $web_config->getDatabaseList();
            //将所有建都转为小写
            $database_list = array_map('array_change_key_case', $database_list);
            foreach ($database_list as $k => $v) {
                $database_list[$k]["data_length_info"] = format_bytes($v['data_length']);
            }
            return $database_list;
        } else {
            $child_menu_list = array(
                array(
                    'url' => "Config/DatabaseList",
                    'menu_name' => "数据库备份",
                    "active" => 1
                ),
                array(
                    'url' => "Config/importDataList",
                    'menu_name' => "数据库恢复",
                    "active" => 0
                )
            );
            $this->assign('child_menu_list', $child_menu_list);
            return view($this->style . "Config/databaseList");
        }
    }

    /**
     * 添加自定义模板
     */
    public function addCustomTemplate() {
        $web_config = new WebConfig();
        $template_data_temp = request()->post('template_data/a', '');
        if(!isset($template_data_temp['items']) || !$template_data_temp['items']){
            return ['code' => -1,'message' => '空白模板无法保存'];
        }
        $type = $template_data_temp['page']['type'];
        foreach($template_data_temp as $key => $val){
            if($key !='items'){
                continue;
            }
            foreach($val as $kk => $vv){
                if(!$vv['params']){
                    continue;
                }
//                if(array_key_exists('content', $vv['params'])){
//                    if(is_base64($vv['params']['content'])){
//                        $decode = base64_decode($vv['params']['content']);
/*                        $search ="/<script[^>]*?>.*?<\/script>/si";*/
//                        $rr = preg_replace($search,' ',$decode);
//                        $template_data_temp[$key][$kk]['params']['content'] = $rr;
//                    }
//                }
                
            }
        }
        $template_data = json_encode($template_data_temp, JSON_UNESCAPED_UNICODE); // 模板数据
        $tab_bar = json_encode(request()->post('tabbar/a', ''), JSON_UNESCAPED_UNICODE);
        $copyright = json_encode(request()->post('copyright/a', ''), JSON_UNESCAPED_UNICODE);
        $wechat_set = json_encode(request()->post('wechat_set/a', ''), JSON_UNESCAPED_UNICODE);
        $popupAdv = json_encode(request()->post('popupAdv/a', ''), JSON_UNESCAPED_UNICODE);
        $id = request()->post('id', ''); // 模板id
        $data['template_data'] = $template_data;
        if ($id) {
            $data['modify_time'] = time();
        } else {
            $data['create_time'] = time();
        }
        if($type == 1){
            $web_config->setPopAdvConfig($popupAdv,$this->website_id);
        }
        $return = $web_config->saveCustomTemplate($data, $id, $type);
        if($return) {
            if(getAddons('cpsunion',$this->website_id)) {
            $cpsunion_server = new Cpsunion();
            $cpsunion_server->saveCpsGoods($template_data_temp);
            }
        }
        //$custom_info = $web_config->getCustomTemplateInfo(['id' => $id]);
        $wechat_set_data['template_data'] = $wechat_set;
        $wechat_set_info = $web_config->getCustomTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 10]);
        if ($wechat_set_info) {
            $wechat_set_data['modify_time'] = time();
        } else {
            $wechat_set_data['shop_id'] = $this->instance_id;
            $wechat_set_data['website_id'] = $this->website_id;
            $wechat_set_data['type'] = 10; //公众号信息
            $wechat_set_data['template_name'] = '公众号信息'; //公众号信息
            $wechat_set_data['create_time'] = time();
        }
        $web_config->saveCustomTemplate($wechat_set_data, $wechat_set_info['id'] ?: 0);
        if (isWIthTarBarAndCopyright($template_data_temp['page']['type'])) {
            //商城首页,会员中心才有底部信息
            $tab_bar_data['template_data'] = $tab_bar;
            $tab_bar_info = $web_config->getCustomTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 7]);
            if ($tab_bar_info) {
                $tab_bar_data['modify_time'] = time();
            } else {
                $tab_bar_data['shop_id'] = $this->instance_id;
                $tab_bar_data['website_id'] = $this->website_id;
                $tab_bar_data['type'] = 7; //底部
                $tab_bar_data['create_time'] = time();
            }
            $web_config->saveCustomTemplate($tab_bar_data, $tab_bar_info['id'] ?: 0);

            $copyright_data['template_data'] = $copyright;
            $copyright_info = $web_config->getCustomTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 8]);
            if ($copyright_info) {
                $copyright_data['modify_time'] = time();
            } else {
                $copyright_data['shop_id'] = $this->instance_id;
                $copyright_data['website_id'] = $this->website_id;
                $copyright_data['type'] = 8; //版权
                $copyright_data['create_time'] = time();
            }
            $web_config->saveCustomTemplate($copyright_data, $copyright_info['id'] ?: 0);
        }

        return AjaxReturn($return);
    }
    
    public function is_base64($str){
        if($str==base64_encode(base64_decode($str))){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 开启关闭自定义模板
     */
    public function setIsEnableCustomTemplate() {
        $web_config = new WebConfig();
        $is_enable = request()->post("is_enable", 0);
        $res = $web_config->setIsEnableCustomTemplate($this->instance_id, $is_enable);
        return AjaxReturn($res);
    }

    /**
     * 新增装修页面
     */
    public function createCustomTemplate() {
        $id = request()->post('id');
        $type = request()->post('type');
        $template_name = request()->post('template_name');
        if (empty($type)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $web_config = new WebConfig();
        if ($id) {
            $condition['id'] = $id;
            $system_default_template_data = $web_config->getCustomTemplateInfo($condition);
            $template_data = json_decode($system_default_template_data['template_data'], true);
        } else {
            $template_data = '';
        }

        $data['template_name'] = !empty($template_name) ? $template_name : ( isset($template_data['page']['name']) ? $template_data['page']['name'] : '新建模板');
        $data['type'] = $type;
        $data['shop_id'] = $this->instance_id;
        $data['website_id'] = $this->website_id;
        $data['create_time'] = time();
        $data['modify_time'] = time();
        $data['template_data'] = json_encode($template_data, JSON_UNESCAPED_UNICODE);
        $id = $web_config->createCustomTemplate($data);
        return AjaxReturn(1, ['id' => $id]);
    }

    //装修
    public function customTemplate() {
        $config_server = new WebConfig();
        $id = request()->get('id', 0); //自定义模板id
        $goods_category = new GoodsCategory();
        $website_model = new WebSiteModel();
        $goods_category_list = $goods_category->getFormatGoodsCategoryList();
        if ($id) {
            $custom_template_info = $this->getCustomTeplateInfo($id);
            $template_data = $custom_template_info['template_data'];
            $type = $custom_template_info['type'];
            $template_name = $custom_template_info['template_name'];
            //底部
            $bar_info = $config_server->getCustomTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 7]);
            $tab_bar = $bar_info['template_data'];
            //版权
            $copyright_info = $config_server->getCustomTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 8]);
            $copyright = $copyright_info['template_data'];
            //公众号信息
            $wechat_info = $config_server->getCustomTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 10]);
            $wechat_set = $wechat_info['template_data'];
        } else {
            $template_data = '';
            $template_name = '';
            $tab_bar = '';
            $type = 1;
            $copyright = '';
            $wechat_set = '';
            
        }
        $popadv = $config_server->getPopAdvConfig($this->website_id);
        //列表数据

        $condition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ];
        $condition['type'] = ['NOT IN', [7, 8, 10]];
        if(!$this->shopStatus){
            $condition['type'] = ['NOT IN', [2, 7, 8, 10]];
        }
        $list = $config_server->getCustomTemplateList(1, 0, $condition);
        $addons = new SysAddonsModel();
        $allAddons = $addons->getQuery(['status' => 1], 'name', '');
        $addonsIsUse = [];
        if($allAddons){
            foreach($allAddons as $val){
                $addonsIsUse[$val['name']] = getAddons($val['name'], $this->website_id);
            }
        }
        $this->assign('template_list', $list['data']);
        $this->assign('type', $type);
        $this->assign('id', $id);
        $this->assign('goods_category_list', json_encode($goods_category_list));
        $this->assign('template_data', $template_data);
        $this->assign('template_name', $template_name);
        $this->assign('tabbar', $tab_bar);
        $this->assign('copyright', $copyright);
        $this->assign('wechat_set', $wechat_set);
        $this->assign('popadv', $popadv);
        $this->assign('addonsIsUse', json_encode($addonsIsUse));
        $this->assign('shopStatus', $this->shopStatus);
        $this->assign('default_version',$website_model::get($this->website_id,['merchant_version'])['merchant_version']['is_default']);
        //return view($this->style . 'Config/customTemplate');
        return view($this->style . 'Config/customTemplate');
    }

    /**
     * 获取自定义模板列表
     *
     * @return list
     */
    public function getCustomTeplateInfo($id) {
        $web_config = new WebConfig();
        $info = $web_config->getCustomTemplateInfo([
            'shop_id' => 0,
            'id' => $id,
            'website_id' => $this->website_id
        ]);
        return $info;
    }
    public function editTemplateName() {
        $id = request()->post('id', 0); //自定义模板id
        $name = request()->post('name', '');
        $Custom = new CustomTemplateModel();
        $info = $Custom->save(['template_name'=>$name],['id' => $id]);
        return AjaxReturn($info);
    }
    /**
     * 手机端自定义模板
     *
     * @return list
     */
    public function customTemplateList() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $template_name = request()->post('template_name', '');
            $template_type = request()->post('template_type');
            $condition = [
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id
            ];
            if(!$this->integralStatus){
                $condition['type'] = ['NOT IN', [6, 7, 8, 9, 10]];
            }

            if ($template_type == 'diy') {
                $condition['type'] = 6;
            } elseif (getAddons('shop', $this->website_id, 0, true)) {
                $condition['type'] = ['NOT IN', [6, 7, 8, 10]];
            } else {
                $condition['type'] = ['NOT IN', [2, 6, 7, 8, 10]];
            }

            if ($template_name) {
                $condition['template_name'] = ['like', "%" . $template_name . "%"];
            }
            $list = $web_config->getCustomTemplateList($page_index, $page_size, $condition, 'in_use DESC,modify_time DESC');

            return $list;
        }
        //return view($this->style . 'Config/customTemplateList');
        
        $count = $web_config->getCustomTemplateCount(['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
        if ($count == 0) {
            $web_config->initCustomTemplate($this->website_id, $this->instance_id);
        }
        $count_wechat = $web_config->getCustomTemplateCount(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 10]);
        if(!$count_wechat){
            $default_set = $web_config->getCustomTemplateInfo(['is_system_default' => 1, 'type' => 10]);
            $wechat_set_data['shop_id'] = $this->instance_id;
            $wechat_set_data['website_id'] = $this->website_id;
            $wechat_set_data['type'] = 10; //公众号信息
            $wechat_set_data['template_name'] = '公众号信息'; //公众号信息
            $wechat_set_data['create_time'] = time();
            $wechat_set_data['template_data'] = $default_set ? $default_set['template_data'] : '';
            $web_config->saveCustomTemplate($wechat_set_data, 0);
        }
        return view($this->style . 'Config/customTemplateList');
    }

    public function getSystemDefaultTemplateList()
    {
        $custom_template_model = new CustomTemplateModel();
        $condition['is_system_default'] = 1;

        $type = [1, 2, 3, 4, 5, 6, 9];
        if(!getAddons('shop', $this->website_id, 0, true)){
            $type = array_merge(array_diff($type, [2]));
        }
        if(!$this->integralStatus){
            $type = array_merge(array_diff($type, [9]));
        }
        $condition['type'] = ['IN', $type];
        $condition['shop_id'] = 0;
        $condition['website_id'] = 0;
        $custom_template_list = $custom_template_model::all($condition);
        $list = [];
        foreach ($custom_template_list as $v) {
            $temp['id'] = $v['id'];
            $temp['template_name'] = $v['template_name'];
            $temp['type'] = $v['type'];
            $temp['template_logo'] = $v['template_logo'];
            $list[$v['type']][] = $temp;
        }
        return $list;
    }

    /**
     * 删除手机端自定义模板
     */
    public function deleteCustomTemplateById() {
        $id = request()->post('id/a', 0);
        if (!$id) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $web_config = new WebConfig();
        $condition = [
            'shop_id' => 0,
            'website_id' => $this->website_id,
            'id' => ['in', $id]
        ];
        $res = $web_config->deleteCustomTemplateById($condition);
        return AjaxReturn($res);
    }


    /**
     * icon图标选择
     */
    public function modalIcons() {
        return view($this->style . 'Shop/iconDialog');
    }

    /**
     * wap_icon图标选择
     */
    public function modalWapIcons() {
        return view($this->style . 'Shop/wap_iconDialog');
    }

    // 弹窗广告设置 
    public function modalPopupAdv() {
        return view($this->style . 'Config/popupAdvDialog');
    }
    /**
     * 广告编辑获取 图片内容
     */
    public function getAlbunPic() {
        $is_vis = intval(request()->post('is_vis', 0));
        $inid = intval(request()->post('inid', 0));
        $album_id = intval(request()->post('album_id', 0));
        $pic_list_val = $this->com->getAlbumList($album_id);
        $pic_list = $pic_list_val['list'];
        $filter = $pic_list_val['filter'];
        $temp = 'shop_adv';
        $this->assign('temp', $temp);
        $this->assign('is_vis', $is_vis);
        $this->assign('pic_list', $pic_list);
        $this->assign('inid', $inid);
        $this->assign('filter', $filter);
        return view($this->style . 'Config/modalFrame');
    }

    /**
     * 获取分类信息
     */
    public function filterCategory() {
        $cat_id = intval(request()->post('cat_id', 0));
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $parent_cat_list = $this->com->get_select_category($cat_id, 1, true);
        $filter_category_navigation = $this->com->get_array_category_info($parent_cat_list);
        $cat_nav = '';

        if ($filter_category_navigation) {
            foreach ($filter_category_navigation as $key => $val) {
                if ($key == 0) {
                    $cat_nav .= $val['category_name'];
                } else if (0 < $key) {
                    $cat_nav .= ' > ' . $val['category_name'];
                }
            }
        } else {
            $cat_nav = '请选择分类';
        }

        $result['cat_nav'] = $cat_nav;
        $cat_level = count($parent_cat_list);

        if ($cat_level <= 3) {
            $filter_category_list = $this->com->get_category_list($cat_id, 2);
        } else {
            $filter_category_list = $this->com->get_category_list($cat_id, 0);
            $cat_level -= 1;
        }
        $filter_category_level = $cat_level;
        $result_jsonsss = json_encode($result);
        $this->assign('result_jsonsss', $result_jsonsss);
        $this->assign('filter_category_level', $filter_category_level);
        $this->assign('filter_category_list', $filter_category_list);
        return view($this->style . 'Config/filterCategory');
    }


    public function useCustomTemplate()
    {
        $config_server = new WebConfig();
        $id = request()->post('id');
        $type = request()->post('type');
        if (empty($id) || empty($type)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $data = $config_server->getCustomTemplateInfo(['id' => $id]);
        if(!$data){
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $templateData = json_decode($data['template_data'],true);
        if(!isset($templateData['items']) || !$templateData['items']){
            return ['code' => -1, 'message' => '空白模板无法使用'];
        }
        $result = $config_server->useCustomTemplate($id, $type, $this->instance_id, $this->website_id);
        if ($result) {
            $this->addUserLogByParam('设置使用模板', $id);
        }
        return AjaxReturn($result);
    }


    /**
     * wap创建模板弹窗
     *
     * @return list
     */
    public function createWapTemplateDialog()
    {
        $this->assign('shopStatus', getAddons('shop', $this->website_id, 0, true));
        $this->assign('integralStatus', $this->integralStatus);
        return view($this->style . 'Config/createWapTemplate');
    }

    private function postCurlPort($url = '', $data = []) {
        // 第一次为空，则从文件中读取
        $curl = curl_init(); // 创建一个新url资源
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $AjaxReturn = curl_exec($curl);
        curl_close($curl);
        $strjson = json_decode($AjaxReturn, true);
        return $strjson;
    }

}
