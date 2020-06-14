<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 10:31
 */

namespace app\wapapi\controller;

use addons\blockchain\service\Block;
use data\model\ConfigModel;
use data\model\AddonsConfigModel;
use data\model\WebSiteModel;
use data\extend\WchatOauth;
use data\service\AddonsConfig;
use data\service\Config as ConfigServer;
use data\service\User;
use think\Session as Session;
use addons\qlkefu\server\Qlkefu;

class Config extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $website_model = new WebSiteModel();
        $config_model = new ConfigModel();
        $website_setting = $website_model->getInfo(['website_id' => $this->website_id], '*');

        $config = new ConfigServer();
        $wchat_config = $config->getInstanceWchatConfig($this->instance_id, $this->website_id);


        $list = $website_model->getInfo(['website_id'=>$this->website_id]);
        $reg_rule = true;
        if ($list['reg_rule'] == 0) {
            $reg_rule = false;
        }
        $is_wchat = false;
        if (!empty($wchat_config['value']['appid']) && !empty($wchat_config['value']['public_name']) && !empty($wchat_config['value']['appsecret'])) {
            $is_wchat = true;
        }

        $base_config = [];
        if ($website_setting) {
            $base_config['mall_name'] = $website_setting['mall_name'];
            $base_config['logo'] = getApiSrc($website_setting['logo']);
            $base_config['icon'] = getApiSrc($website_setting['icon']);
            //$base_config['web_status'] = $website_setting['web_status'];
            $base_config['wap_status'] = $website_setting['wap_status'];
            $base_config['close_reason'] = $website_setting['close_reason'];
            //$base_config['shop_status'] = $website_setting['shop_status'];
            $base_config['wap_register_adv'] = getApiSrc($website_setting['wap_register_adv']);
            $base_config['wap_register_jump'] = $website_setting['wap_register_jump'];
            $base_config['wap_login_adv'] = getApiSrc($website_setting['wap_login_adv']);
            $base_config['wap_login_jump'] = $website_setting['wap_login_jump'];
            $base_config['wap_pop'] = $config->getPopAdvConfig($this->website_id) ? json_decode($config->getPopAdvConfig($this->website_id)) : [];
            $base_config['is_wchat'] = $is_wchat;
            $base_config['reg_rule'] = $reg_rule;
            $base_config['account_type'] = $website_setting['account_type'];
            $base_config['mobile_type'] = $website_setting['mobile_type'];
            $base_config['is_bind_phone'] = $website_setting['is_bind_phone'];
        }
        //获取是否开启转账设置
        $is_transfer = $config->getConfig($this->instance_id, 'IS_TRANSFER', $this->website_id);
        $is_point_transfer = $config->getConfig($this->instance_id, 'IS_POINT_TRANSFER', $this->website_id);
        $base_config['is_transfer'] = $is_transfer['value'] ? $is_transfer['value'] : 0;
        $base_config['is_point_transfer'] = $is_point_transfer['value'] ? $is_point_transfer['value'] : 0;
        //积分汇率
        $convert_rate = $config->getConfig($this->instance_id, 'POINT_DEDUCTION_NUM', $this->website_id);
        $base_config['convert_rate'] = $convert_rate['value'] ? $convert_rate['value'] : 1;

        $config_array = ['SEO_TITLE', 'SEO_META', 'SEO_DESC', 'LOGINVERIFYCODE', 'BPAY', 'ALIPAY','DPAY','TLPAY','ETHPAY','EOSPAY','WPAY', 'WCHAT', 'QQLOGIN', 'EMAILMESSAGE', 'MOBILEMESSAGE', 'WITHDRAW_BALANCE', 'GPPAY', 'GLOPAY'];
        $config_server_condition['key'] = ['IN', $config_array];
        $config_server_condition['website_id'] = $this->website_id;
//        $config_server_condition['is_use'] = 1;
        $blockchain = getAddons('blockchain',$this->website_id);
        $eth_set = false;
        $eos_set = false;
        if($blockchain){
            $site = new Block();
            $site_info = $site->getBlockChainSite($this->website_id);
            if($site_info['is_use']==1 && $site_info['wallet_type']){
                $wallet_type = explode(',',$site_info['wallet_type']);
                if(in_array(1,$wallet_type)){
                    $eth_set = true;
                }
                if(in_array(2,$wallet_type)){
                    $eos_set = true;
                }
            }
        }
        $base_config['withdraw_conf']['is_withdraw_start'] = false;
        $config_list = $config_model->getQuery($config_server_condition, '*', '');
        foreach ($config_list as $k => $v) {
            switch ($v['key']) {
                case 'SEO_TITLE':
                case 'SEO_META' :
                case 'SEO_DESC' :
                    $base_config[strtolower($v['key'])] = $v['value'];
                    break;
                case 'WITHDRAW_BALANCE':
                    $info = json_decode($v['value'], true);
                    $base_config['withdraw_conf']['is_withdraw_start'] = $v['is_use'] ? true  : false;
                    $base_config['withdraw_conf']['lowest_withdraw'] = $info['withdraw_cash_min'] ? : 0;
                    $base_config['withdraw_conf']['withdraw_message'] = explode(',', $info['withdraw_message']) ? : [];
                    if($base_config['withdraw_conf']['withdraw_message'] && in_array(3,$base_config['withdraw_conf']['withdraw_message'])){
                        $info = $config->getAlipayConfig($this->website_id);
                        if($info['is_use']==0){
                            $base_config['withdraw_conf']['withdraw_message'] = array_merge(array_diff($base_config['withdraw_conf']['withdraw_message'], [3]));
                        }
                    }
                    if($base_config['withdraw_conf']['withdraw_message'] && in_array(2,$base_config['withdraw_conf']['withdraw_message'])){
                        $info = $config->getWpayConfig($this->website_id);
                        $wx_tw = $info['value']['wx_tw'];
                        if($wx_tw==0 || $info['is_use']==0){
                            $base_config['withdraw_conf']['withdraw_message'] = array_merge(array_diff($base_config['withdraw_conf']['withdraw_message'], [2]));
                        }
                    }
                    if($base_config['withdraw_conf']['withdraw_message'] && in_array(1,$base_config['withdraw_conf']['withdraw_message'])){
                        $info = $config->getTlConfig(0,$this->website_id);
                        $tl_tw = $info['value']['tl_tw'];
                        if($tl_tw==0 || $info['is_use']==0){
                            $base_config['withdraw_conf']['withdraw_message'] = array_merge(array_diff($base_config['withdraw_conf']['withdraw_message'], [1]));
                        }
                    }
                    break;
                case 'LOGINVERIFYCODE':
                    $info = json_decode($v['value'], true);
                    $base_config['captcha_code_type'] = $info['pc'] ? true : false;
                    break;
                case 'DPAY':
                    if ($v['is_use'] == 0) {
                        $base_config['dpay'] = false;
                        break;
                    }
                    $base_config['dpay'] = true;
                    break;
                case 'TLPAY':
                    if ($v['is_use'] == 0) {
                        $base_config['tlpay'] = false;
                        break;
                    }
                    $base_config['tlpay'] = true;
                    break;
                case 'ETHPAY':
                    if($eth_set){
                        if ($v['is_use'] == 0) {
                            $base_config['ethpay'] = false;
                            break;
                        }
                        $base_config['ethpay'] = true;
                        break;
                    }else{
                        $base_config['ethpay'] = false;
                        break;
                    }
                case 'EOSPAY':
                    if($eos_set){
                        if ($v['is_use'] == 0) {
                            $base_config['eospay'] = false;
                            break;
                        }
                        $base_config['eospay'] = true;
                        break;
                    }else{
                        $base_config['eospay'] = false;
                        break;
                    }
                case 'BPAY':
                    if ($v['is_use'] == 0) {
                        $base_config['bpay'] = false;
                        break;
                    }
                    $base_config['bpay'] = true;
                    break;
                case 'ALIPAY':
                    if ($v['is_use'] == 0) {
                        $base_config['ali_pay'] = false;
                        break;
                    }
                    //{"ali_partnerid":"","ali_seller":"","ali_key":""}
                    $info = json_decode($v['value'], true);
                    if (empty($info['ali_partnerid']) || empty($info['ali_seller']) || empty($info['ali_key'])) {
                        $base_config['ali_pay'] = false;
                        break;
                    }
                    $base_config['ali_pay'] = true;
                    break;
                case 'WPAY':
                    if ($v['is_use'] == 0) {
                        $base_config['wechat_pay'] = false;
                        break;
                    }
                    //{"appid":"","appkey":"","mch_id":"","mch_key":""}
                    $info = json_decode($v['value'], true);
                    if (empty($info['appid'])  || empty($info['mch_id']) || empty($info['mch_key'])) {
                        $base_config['wechat_pay'] = false;
                        break;
                    }
                    $base_config['wechat_pay'] = true;
                    break;
				case 'GPPAY':
                    if ($v['is_use'] == 0) {
                        $base_config['gppay'] = false;
                        break;
                    }
                    $info = json_decode($v['value'], true);
                    if (empty($info['appid'])  || empty($info['partner_code']) || empty($info['credential_code']) || empty($info['currency'])) {
                        $base_config['gppay'] = false;
                        break;
                    }
                    $base_config['gppay'] = true;
                    break;
				case 'GLOPAY':
                    if ($v['is_use'] == 0) {
                        $base_config['glopay'] = false;
                        break;
                    }
                    $info = json_decode($v['value'], true);
                    if (empty($info['partner_code']) || empty($info['credential_code']) || empty($info['currency'])) {
                        $base_config['glopay'] = false;
                        break;
                    }
                    $base_config['glopay'] = true;
                    break;	
                case 'WCHAT':
                    //{"APP_KEY":"","APP_SECRET":"","AUTHORIZE":"","CALLBACK":""}
                    if ($v['is_use'] == 0) {
                        $base_config['wechat_login'] = false;
                        break;
                    }
                    $info = json_decode($v['value'], true);
                    if (empty($info['APP_KEY']) || empty($info['APP_SECRET']) || empty($info['AUTHORIZE']) || empty($info['CALLBACK'])) {
                        $base_config['wechat_login'] = false;
                        break;
                    }
                    $base_config['wechat_login'] = true;
                    break;
                case 'QQLOGIN':
                    //{"APP_KEY":"","APP_SECRET":"","AUTHORIZE":"","CALLBACK":""}
                    if ($v['is_use'] == 0) {
                        $base_config['qq_login'] = false;
                        break;
                    }
                    $info = json_decode($v['value'], true);
                    if (empty($info['APP_KEY']) || empty($info['APP_SECRET']) || empty($info['AUTHORIZE']) || empty($info['CALLBACK'])) {
                        $base_config['qq_login'] = false;
                        break;
                    }
                    $base_config['qq_login'] = true;
                    break;
                case 'EMAILMESSAGE':
                    //{"email_host":"","email_addr":"","email_id":"","email_pass":"oqzvqvolpdvhbhjj","email_is_security":false}
                    if ($v['is_use'] == 0) {
                        $base_config['email_verification'] = false;
                        break;
                    }
                    $base_config['email_verification'] = true;
                    break;
                case 'MOBILEMESSAGE':
                    if ($v['is_use'] == 0) {
                        $base_config['mobile_verification'] = false;
                        break;
                    }
                    $base_config['mobile_verification'] = true;
                    break;
            }
        }
        $data['config'] = $base_config;
        $addons = new AddonsConfigModel();

        $custom_info = json_decode($addons->getInfo(['website_id' => $this->website_id, 'addons' => 'customform'], 'value')['value'], true);
        $custom = [];
        $custom['member_status'] = $custom_info['member_status'];
        $custom['order_status'] = $custom_info['order_status'];
        $custom['distributor_status'] = $custom_info['distributor_status'];
        $custom['shareholder_status'] = $custom_info['shareholder_status'];
        $custom['captain_status'] = $custom_info['captain_status'];
        $custom['channel_status'] = $custom_info['channel_status'];
        $custom['area_status'] = $custom_info['area_status'];
        $data['customform'] = $custom;
        $all_addons = $addons->getQuery(['website_id' => $this->website_id], '', '');
        foreach ($all_addons as $k => $v) {
            $data['addons'][$v['addons']] = getAddons($v['addons'], $this->website_id, $this->instance_id);
        }
        $other_addons = ['appshop', 'areabonus', 'coupontype', 'discount', 'fullcut', 'gift', 'giftvoucher', 'integral', 'pcport',
            'seckill', 'shop', 'teambonus', 'voucherpackage', 'distribution', 'bargin', 'channel', 'customform', 'store', 'microshop', 'poster','blockchain', 'taskcenter', 'credential','helpcenter'];
        foreach ($other_addons as $k => $v) {
            if (!isset($data['addons'][$v])){
                $data['addons'][$v] = getAddons($v, $this->website_id, $this->instance_id);
            }
        }
        if(getAddons('qlkefu', $this->website_id)){
            $qlkefu = new Qlkefu();
            $qlkefu_info = $qlkefu->qlkefuConfig($this->website_id,0);
            $data['config']['qlkefu_domain_port'] = $qlkefu_info['ql_domain'].':'.$qlkefu_info['ql_port'];
            if(empty($qlkefu_info['ql_domain']))$data['config']['qlkefu_domain_port'] = '';
            $is_qlkefu = $qlkefu->isQlkefuShop($this->website_id);
            $data['addons']['qlkefu'] = $is_qlkefu['is_use'];
        }
        return json(['code' => 1, 'message' => '成功获取', 'data' => $data]);
    }

    public function share()
    {
        $weixin = new WchatOauth($this->website_id);
        $url = request()->post('url', '');
        $wx_share = $weixin->shareWx(urldecode($url));
        return json(['code' => 1, 'message' => '成功获取', 'data' => $wx_share]);
    }
    //文案样式
    public function copyStyle(){
        $config = new ConfigServer();
        $copystyle = $config->getConfig(0,'COPYSTYLE');
        if($copystyle){
            $data['data']  = (object)json_decode($copystyle['value'],true);
            $data['code'] = '0';
            return json($data);
        }else{
            $copystyle['point_style'] = '积分';
            $copystyle['balance_style'] = '余额';
            $data['data'] = (object)$copystyle;
            $data['code'] = '0';
            return json($data);
        }
    }
    //app的基本设置和支付配置，第三方登录配置
    public function appConfig(){
        $config_model = new ConfigModel();
        $config_array = ['BPAYAPP', 'ALIPAYAPP','DPAYAPP','WPAYAPP', 'WCHATAPP'];
        $config_server_condition['key'] = ['IN', $config_array];
        $config_server_condition['website_id'] = $this->website_id;
        $config_list = $config_model->getQuery($config_server_condition, '*', '');
        $base_config['dpay'] = false;
        $base_config['bpay'] = false;
        $base_config['ali_pay'] = false;
        $base_config['wechat_pay'] = false;
        $base_config['wechat_login'] = false;
        foreach ($config_list as $k => $v) {
            switch ($v['key']) {
                case 'DPAYAPP':
                    if ($v['is_use'] == 0) {
                        $base_config['dpay'] = false;
                        break;
                    }
                    $base_config['dpay'] = true;
                    break;
                case 'BPAYAPP':
                    if ($v['is_use'] == 0) {
                        $base_config['bpay'] = false;
                        break;
                    }
                    $base_config['bpay'] = true;
                    break;
                case 'ALIPAYAPP':
                    if ($v['is_use'] == 0) {
                        $base_config['ali_pay'] = false;
                        break;
                    }
                    $info = json_decode($v['value'], true);
                    if (empty($info['ali_partnerid']) || empty($info['ali_seller']) || empty($info['ali_key'])) {
                        $base_config['ali_pay'] = false;
                        break;
                    }
                    $base_config['ali_pay'] = true;
                    break;
                case 'WPAYAPP':
                    if ($v['is_use'] == 0) {
                        $base_config['wechat_pay'] = false;
                        break;
                    }
                    $info = json_decode($v['value'], true);
                    if (empty($info['appid'])  || empty($info['mch_id']) || empty($info['mch_key'])) {
                        $base_config['wechat_pay'] = false;
                        break;
                    }
                    $base_config['wechat_pay'] = true;
                    break;
                case 'WCHATAPP':
                    if ($v['is_use'] == 0) {
                        $base_config['wechat_login'] = false;
                        break;
                    }
                    $info = json_decode($v['value'], true);
                    if (empty($info['APP_KEY']) || empty($info['APP_SECRET'])) {
                        $base_config['wechat_login'] = false;
                        break;
                    }
                    $base_config['wechat_login'] = true;
                    break;
            }
        }
        $addons_service = new AddonsConfig();
        $list['app_set'] = $addons_service->getAddonsConfig('appshop',$this->website_id);
        if($list['app_set']['value']){
            $base_config['app_set'] = json_decode($list['app_set']['value'],true);
        }else{
            $base_config['app_set'] = (object)[];
        }
        $data['config'] = $base_config;
        return json(['code' => 1, 'message' => '成功获取', 'data' => $data]);
    }
    //小程序支付配置设置，第三方登录配置
    public function mipConfig(){
        $config_model = new ConfigModel();
        $config_array = ['BPAYMP', 'DPAYMP','MPPAY','GPPAY'];
        $config_server_condition['key'] = ['IN', $config_array];
        $config_server_condition['website_id'] = $this->website_id;
        $config_list = $config_model->getQuery($config_server_condition, '*', '');
        $base_config['dpay'] = false;
        $base_config['bpay'] = false;
        $base_config['wechat_pay'] = false;
		$base_config['gppay'] = false;
        foreach ($config_list as $k => $v) {
            switch ($v['key']) {
                case 'DPAYMP':
                    if ($v['is_use'] == 0) {
                        $base_config['dpay'] = false;
                        break;
                    }
                    $base_config['dpay'] = true;
                    break;
                case 'BPAYMP':
                    if ($v['is_use'] == 0) {
                        $base_config['bpay'] = false;
                        break;
                    }
                    $base_config['bpay'] = true;
                    break;
                case 'MPPAY':
                    if ($v['is_use'] == 0) {
                        $base_config['wechat_pay'] = false;
                        break;
                    }
                    $info = json_decode($v['value'], true);
                    if (empty($info['appid'])  || empty($info['mchid']) || empty($info['mch_key'])) {
                        $base_config['wechat_pay'] = false;
                        break;
                    }
                    $base_config['wechat_pay'] = true;
                    break;
				case 'GPPAY':
                    if ($v['is_use'] == 0) {
                        $base_config['gppay'] = false;
                        break;
                    }
                    $info = json_decode($v['value'], true);
                    if (empty($info['appid'])  || empty($info['partner_code']) || empty($info['credential_code']) || empty($info['currency'])) {
                        $base_config['gppay'] = false;
                        break;
                    }
                    $base_config['gppay'] = true;
                    break;
            }
        }
        $data['config'] = $base_config;
        return json(['code' => 1, 'message' => '成功获取', 'data' => $data]);
    }
    //区号列表
    public function getCountryCode(){
        $countryCodeModel = new \data\model\SysCountryCodeModel();
        $countryCode = $countryCodeModel->getQuery([], '*', 'sort asc');
        if(!$countryCode){
            $data=[
                ['sort' => 1,'country' => '中国', 'country_code' => '86', 'country_code_long' => '0086'],
                ['sort' => 2,'country' => '香港', 'country_code' => '852', 'country_code_long' => '00852'],
                ['sort' => 3,'country' => '澳门', 'country_code' => '853', 'country_code_long' => '00853'],
                ['sort' => 4,'country' => '台湾', 'country_code' => '886', 'country_code_long' => '00886'],
                ['sort' => 5,'country' => '美国', 'country_code' => '1', 'country_code_long' => '001'],
                ['sort' => 6,'country' => '日本', 'country_code' => '81', 'country_code_long' => '0081'],
                ['sort' => 7,'country' => '韩国', 'country_code' => '82', 'country_code_long' => '0082'],
                ['sort' => 8,'country' => '新加坡', 'country_code' => '65', 'country_code_long' => '0065']
            ];
            $countryCodeModel->saveAll($data, true);
            $countryCode = $countryCodeModel->getQuery([], '*', 'sort asc');
        }
        
        return json(['code' => 1, 'message' => '成功获取', 'data' => $countryCode]);
    }
}