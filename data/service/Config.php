<?php

namespace data\service;

/**
 * 系统配置业务层
 */

use addons\appshop\model\AppCustomTemplate;
use data\model\ConfigModel as ConfigModel;
use data\model\NoticeModel;
use data\model\NoticeTemplateItemModel;
use data\model\NoticeTemplateModel;
use data\model\NoticeTemplateTypeModel;
use data\model\WeixinFansModel;
use data\model\WeixinGroupModel;
use data\service\BaseService as BaseService;
use think\Cache;
use think\Db;
use think\Log;
use data\model\CustomTemplateModel;

class Config extends BaseService
{

    private $config_module;

    function __construct()
    {
        parent::__construct();
        $this->config_module = new ConfigModel();
    }
    public function getStyleConfig($shop_id)
    {
        $res = $this->config_module->getInfo(['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'COPYSTYLE'])['value'];
        if($res){
            $res = json_decode($res,true);
        }
        return $res;
    }
    public function setRedisConfig($shop_id,$host, $pass)
    {
            $value = array(
                'host' => $host,
                'pass' => $pass,
            );
            $data = array(
                'instance_id' => $shop_id,
                'website_id' => $this->website_id,
                'key' => 'REDIS',
                'value' => json_encode($value),
                'desc' => 'redis配置',
                'is_use' => 1
            );
            $info= $this->config_module->getInfo(['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'REDIS']);
            if(empty($info)){
                $res = $this->config_module->save($data);
            }else{
                $res = $this->config_module->save($data,['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'REDIS']);
            }

        return $res;
    }
    /*
     * 微信开放平台设置
     */
    public function setWechatOpenConfig($shop_id,$open_appid, $open_secrect, $open_key, $open_token)
    {
            $value = array(
                'open_appid' => $open_appid,
                'open_secrect' => $open_secrect,
                'open_key' => $open_key,
                'open_token' => $open_token,
            );
            $data = array(
                'instance_id' => $shop_id,
                'website_id' => $this->website_id,
                'key' => 'WECHATOPEN',
                'value' => json_encode($value),
                'desc' => '微信开放平台配置',
                'is_use' => 1
            );
            $info= $this->config_module->getInfo(['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'WECHATOPEN']);
            if(empty($info)){
                $res = $this->config_module->save($data);
            }else{
                $res = $this->config_module->save($data,['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'WECHATOPEN']);
            }

        return $res;
    }
    public function getRedisConfig($shop_id)
    {
        $res = $this->config_module->getInfo(['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'REDIS'])['value'];
        if($res){
            $res = json_decode($res,true);
        }
        return $res;
    }
    public function getWechatOpenConfig($shop_id)
    {
        $res = $this->config_module->getInfo(['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'WECHATOPEN'])['value'];
        if($res){
            $res = json_decode($res,true);
        }
        return $res;
    }
    public function getWechatOpen($appid, $shop_id)
    {
        $res = $this->config_module->getInfo(['instance_id' => $shop_id,'value' => ['like', '%'.$appid.'%'],'key'=>'WECHATOPEN'])['value'];
        if($res){
            $res = json_decode($res,true);
        }
        return $res;
    }
    /*
     * (non-PHPdoc)
     * @see \data\api\IConfig::getWchatConfig()
     */
    public function setSeoConfig($shop_id, $seo_title, $seo_meta, $seo_desc)
    {
        $array[0] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'SEO_TITLE',
            'value' => $seo_title,
            'desc' => '网页标题',
            'is_use' => 1
        );
        $array[1] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'SEO_META',
            'value' => $seo_meta,
            'desc' => '商城关键词',
            'is_use' => 1
        );
        $array[2] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'SEO_DESC',
            'value' => $seo_desc,
            'desc' => '关键词描述',
            'is_use' => 1
        );
        $res = $this->setConfig($array);
        Cache::set("seo_config" . $shop_id . $this->website_id, '');
        return $res;
    }

    public function getSeoConfig($shop_id)
    {
        $seo_config = Cache::get("seo_config" . $shop_id . $this->website_id);
        if (empty($seo_config)) {
            $seo_title = $this->getConfig($shop_id, 'SEO_TITLE');
            $seo_meta = $this->getConfig($shop_id, 'SEO_META');
            $seo_desc = $this->getConfig($shop_id, 'SEO_DESC');
            if (empty($seo_title) || empty($seo_meta) || empty($seo_desc)) {
                $this->SetSeoConfig($shop_id, '', '', '');
                $array = array(
                    'seo_title' => '',
                    'seo_meta' => '',
                    'seo_desc' => ''
                );
            } else {
                $array = array(
                    'seo_title' => $seo_title['value'],
                    'seo_meta' => $seo_meta['value'],
                    'seo_desc' => $seo_desc['value']
                );
            }
            Cache::set("seo_config" . $shop_id . $this->website_id, $array);
            $seo_config = $array;
        }

        return $seo_config;
    }
    public function getRealIpConfig()
    {
        $info = $this->config_module->getInfo([
            'key' => 'REALMIP',
            'instance_id' => 0,
            'website_id' => $this->website_id
        ], 'id,value,is_use');
        if (empty($info['value'])) {
            $realip_config = array(
                    'realm_ip' => '',
                    'realm_two_ip' => '',
                    'ssl' => '',
                    'sslkey' => ''
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            $realip_config = $info['value'];
        }
        return $realip_config;
    }
    public function getWchatConfig($instance_id)
    {
        $info = $this->config_module->getInfo([
            'key' => 'WCHAT',
            'instance_id' => $instance_id,
            'website_id' => $this->website_id
        ], 'id,value,is_use');
        if (empty($info['value'])) {
            $wchat_config = array(
                'value' => array(
                    'APP_KEY' => '',
                    'APP_SECRET' => '',
                    'AUTHORIZE' => '',
                    'CALLBACK' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            $wchat_config = $info;
        }
        return $wchat_config;
        // TODO Auto-generated method stub
    }
    public function getWchatConfigApp($instance_id)
    {
        $info = $this->config_module->getInfo([
            'key' => 'WCHATAPP',
            'instance_id' => $instance_id,
            'website_id' => $this->website_id
        ], 'id,value,is_use');
        if (empty($info['value'])) {
            $wchat_config = array(
                'value' => array(
                    'APP_KEY' => '',
                    'APP_SECRET' => '',
                    'AUTHORIZE' => '',
                    'CALLBACK' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            $wchat_config = $info;
        }
        return $wchat_config;
        // TODO Auto-generated method stub
    }
    public function getQQConfig($instance_id)
    {
        $info = $this->config_module->getInfo([
            'key' => 'QQLOGIN',
            'instance_id' => $instance_id,
            'website_id' => $this->website_id
        ], 'id,value,is_use');
        if (empty($info['value'])) {
            $qq_config = array(
                'value' => array(
                    'APP_KEY' => '',
                    'APP_SECRET' => '',
                    'AUTHORIZE' => '',
                    'CALLBACK' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            $qq_config = $info;
        }
        return $qq_config;

    }
    public function getLoginConfig()
    {
        $instance_id = 0;
        $wchat_config = $this->getWchatConfig($instance_id);
        $qq_config = $this->getQQConfig($instance_id);

        $mobile_config = $this->getMobileMessage($instance_id);
        $email_config = $this->getEmailMessage($instance_id);
        $data = array(
            'wchat_login_config' => $wchat_config,
            'qq_login_config' => $qq_config,
            'mobile_config' => $mobile_config,
            'email_config' => $email_config
        );
        return $data;
    }
    public function getWpayConfig($website_id)
    {
        if($website_id){
            $info = $this->config_module->getInfo([
                'instance_id' => 0,
                'website_id' => $website_id,
                'key' => 'WPAY'
            ], 'value,is_use');
        }else{
            $info = $this->config_module->getInfo([
                'instance_id' => 0,
                'website_id' => $this->website_id,
                'key' => 'WPAY'
            ], 'value,is_use');
        }
        if (empty($info)) {
            return array(
                'value' => array(
                    'appid' => '',
                    'mch_id' => '',
                    'mch_key' => '',
                    'certkey' => '',
                    'cert' => '',
                    'wx_tw'=>0
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getWpayConfigs()
    {
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => 0,
            'key' => 'WPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appid' => '',
                    'appkey' => '',
                    'mch_id' => '',
                    'mch_key' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getAlipayConfigs()
    {
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => 0,
            'key' => 'ALIPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'ali_partnerid' => '',
                    'ali_seller' => '',
                    'ali_key' => '',
                    'appid' => '',
                    'ali_public_key' => '',
                    'ali_private_key' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getAlipayConfig($website_id=null)
    {
       if(empty($website_id)){
           $website_ids = $this->website_id;
       }else{
           $website_ids = $website_id;
       }
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => $website_ids,
            'key' => 'ALIPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'ali_partnerid' => '',
                    'ali_seller' => '',
                    'ali_key' => '',
                    'appid' => '',
                    'ali_public_key' => '',
                    'ali_private_key' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getBpayConfig($instance_id)
    {
        $info = $this->config_module->getInfo([
            'instance_id' => $instance_id,
            'website_id' => $this->website_id,
            'key' => 'BPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'ali_partnerid' => '',
                    'ali_seller' => '',
                    'ali_key' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getDpayConfig($instance_id)
    {
        $info = $this->config_module->getInfo([
            'instance_id' => $instance_id,
            'website_id' => $this->website_id,
            'key' => 'DPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getEthConfig($instance_id,$website_id=null)
    {
        if(empty($website_id)){
            $website_ids = $this->website_id;
        }else{
            $website_ids = $website_id;
        }
        $info = $this->config_module->getInfo([
            'instance_id' => $instance_id,
            'website_id' => $website_ids,
            'key' => 'ETHPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getEosConfig($instance_id,$website_id=null)
    {
        if(empty($website_id)){
            $website_ids = $this->website_id;
        }else{
            $website_ids = $website_id;
        }
        $info = $this->config_module->getInfo([
            'instance_id' => $instance_id,
            'website_id' => $website_ids,
            'key' => 'EOSPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'tl_cusid' => '',
                    'tl_appid' => '',
                    'tl_key' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getTlConfig($instance_id,$website_id=null)
    {
        if(empty($website_id)){
            $website_ids = $this->website_id;
        }else{
            $website_ids = $website_id;
        }
        $info = $this->config_module->getInfo([
            'instance_id' => $instance_id,
            'website_id' => $website_ids,
            'key' => 'TLPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'tl_cusid' => '',
                    'tl_appid' => '',
                    'tl_key' => '',
                    'tl_tw'=>0
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getWpayConfigApp($website_id)
    {
        if($website_id){
            $website_ids = $website_id;
        }else{
            $website_ids = $this->website_id;
        }
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => $website_ids,
            'key' => 'WPAYAPP'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appid' => '',
                    'mch_id' => '',
                    'mch_key' => '',
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getAlipayConfigApp($website_id)
    {
        if(empty($website_id)){
            $website_ids = $this->website_id;
        }else{
            $website_ids = $website_id;
        }
        $info = $this->config_module->getInfo([
            'instance_id' =>0,
            'website_id' => $website_ids,
            'key' => 'ALIPAYAPP'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'ali_partnerid' => '',
                    'ali_seller' => '',
                    'ali_key' => '',
                    'appid' => '',
                    'ali_public_key' => '',
                    'ali_private_key' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getBpayConfigApp($website_id)
    {
        if(empty($website_id)){
            $website_ids = $this->website_id;
        }else{
            $website_ids = $website_id;
        }
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => $website_ids,
            'key' => 'BPAYAPP'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'is_use' => 0
            );
        } else {
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getDpayConfigApp($website_id)
    {
        if(empty($website_id)){
            $website_ids = $this->website_id;
        }else{
            $website_ids = $website_id;
        }
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => $website_ids,
            'key' => 'DPAYAPP'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getWpayConfigMir($website_id)
    {
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => $website_id,
            'key' => 'MPPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appid' => '',
                    'mchid' => '',
                    'mch_key' => '',
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getBpayConfigMir($website_id)
    {
        if(empty($website_id)){
            $website_ids = $this->website_id;
        }else{
            $website_ids = $website_id;
        }
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => $website_ids,
            'key' => 'BPAYMP'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'is_use' => 0
            );
        } else {
            return $info;
        }
        // TODO Auto-generated method stub
    }
    public function getDpayConfigMir($website_id)
    {
        if(empty($website_id)){
            $website_ids = $this->website_id;
        }else{
            $website_ids = $website_id;
        }
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => $website_ids,
            'key' => 'DPAYMP'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    /**
     * 设置商城设置
     */
    public function setWchatConfig($instance_id, $appid, $appsecret, $url, $call_back_url, $is_use)
    {
        $info = array(
            'APP_KEY' => $appid,
            'APP_SECRET' => $appsecret,
            'AUTHORIZE' => $url,
            'CALLBACK' => $call_back_url
        );
        $value = json_encode($info);
        $count = $this->config_module->where([
            'key' => 'WCHAT',
            'instance_id' => $instance_id,
            'website_id' => $this->website_id
        ])->count();
        if ($count > 0) {
            $data = array(
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $this->config_module->where([
                'key' => 'WCHAT',
                'instance_id' => $instance_id,
                'website_id' => $this->website_id
            ])->update($data);
            if ($res == 1) {
                return SUCCESS;
            } else {
                return UPDATA_FAIL;
            }
        } else {
            $data = array(
                'instance_id' => $instance_id,
                'website_id' => $this->website_id,
                'key' => 'WCHAT',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $this->config_module->save($data);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setWchatConfigApp($instance_id, $appid, $appsecret, $url, $call_back_url, $is_use)
    {
        $info = array(
            'APP_KEY' => $appid,
            'APP_SECRET' => $appsecret,
            'AUTHORIZE' => $url,
            'CALLBACK' => $call_back_url
        );
        $value = json_encode($info);
        $count = $this->config_module->where([
            'key' => 'WCHATAPP',
            'instance_id' => $instance_id,
            'website_id' => $this->website_id
        ])->count();
        if ($count > 0) {
            $data = array(
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $this->config_module->where([
                'key' => 'WCHATAPP',
                'instance_id' => $instance_id,
                'website_id' => $this->website_id
            ])->update($data);
            if ($res == 1) {
                return SUCCESS;
            } else {
                return UPDATA_FAIL;
            }
        } else {
            $data = array(
                'instance_id' => $instance_id,
                'website_id' => $this->website_id,
                'key' => 'WCHATAPP',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $this->config_module->save($data);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setAlipayConfig($instanceid, $partnerid, $seller, $ali_key, $is_use, $appid,$ali_public_key, $ali_private_key)
    {
        $data = array(
            'ali_partnerid' => $partnerid,
            'ali_seller' => $seller,
            'ali_key' => $ali_key,
            'appid' => $appid,
            'ali_public_key' => $ali_public_key,
            'ali_private_key' => $ali_private_key,
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'ALIPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'ALIPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'ALIPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'ALIPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setAlipayConfigs($partnerid, $seller, $ali_key, $is_use, $appid,$ali_public_key, $ali_private_key)
    {
        $data = array(
            'ali_partnerid' => $partnerid,
            'ali_seller' => $seller,
            'ali_key' => $ali_key,
            'appid' => $appid,
            'ali_public_key' => $ali_public_key,
            'ali_private_key' => $ali_private_key,
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'ALIPAY',
            'instance_id' => 0,
            'website_id' => 0
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => 0,
                'website_id' => 0,
                'key' => 'ALIPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'ALIPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => 0,
                'website_id' => 0,
                'key' => 'ALIPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setBpayConfig($instanceid, $partnerid, $seller, $ali_key, $is_use)
    {
        $data = array(
            'ali_partnerid' => $partnerid,
            'ali_seller' => $seller,
            'ali_key' => $ali_key
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'BPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'BPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'BPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'BPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setDpayConfig($instanceid, $partnerid, $seller, $ali_key, $is_use)
    {
        $data = array(
            'ali_partnerid' => $partnerid,
            'ali_seller' => $seller,
            'ali_key' => $ali_key
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'DPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'DPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'DPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'DPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setWpayConfig($instanceid, $appid, $mch_id, $mch_key, $is_use,$certkey,$cert,$wx_tw)
    {
        $info = $this->config_module->getInfo([
            'key' => 'WPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
        ], 'value');
        $data_info = array(
            'appid' => $appid,
            'mch_id' => $mch_id,
            'mch_key' => $mch_key,
            'certkey' => $certkey,
            'cert' => $cert,
            'wx_tw'=>$wx_tw
        );
        if (empty($info)) {
            $value = json_encode($data_info);
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'WPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $value = json_encode($data_info);
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'WPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'WPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setWpayConfigs($appid, $appkey, $mch_id, $mch_key, $is_use,$certkey,$cert)
    {
        $data = array(
            'appid' => $appid,
            'appkey' => $appkey,
            'mch_id' => $mch_id,
            'mch_key' => $mch_key,
            'certkey' => $certkey,
            'cert' => $cert
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'WPAY',
            'instance_id' => 0,
            'website_id' => 0,
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => 0,
                'website_id' => 0,
                'key' => 'WPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'WPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => 0,
                'website_id' => 0,
                'key' => 'WPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setEthConfig($instanceid,$is_use)
    {
        $info = $this->config_module->getInfo([
            'key' => 'ETHPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
        ], 'value');
        $data_info = array();
        if (empty($info)) {
            $value = json_encode($data_info);
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'ETHPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $value = json_encode($data_info);
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'ETHPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'ETHPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setEosConfig($instanceid,$is_use)
    {
        $info = $this->config_module->getInfo([
            'key' => 'EOSPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
        ], 'value');
        $data_info = array();
        if (empty($info)) {
            $value = json_encode($data_info);
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'EOSPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $value = json_encode($data_info);
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'EOSPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'EOSPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setTlConfig($instanceid,$tl_id,$tl_cusid, $tl_appid, $tl_key,$tl_username,$tl_password,$tl_public, $tl_private,$tl_tw, $is_use)
    {
        $info = $this->config_module->getInfo([
            'key' => 'TLPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
        ], 'value');
        $data_info = array(
            'tl_id' => $tl_id,
            'tl_cusid' => $tl_cusid,
            'tl_appid' => $tl_appid,
            'tl_key' => $tl_key,
            'tl_username' => $tl_username,
            'tl_password' => $tl_password,
            'tl_public' => $tl_public,
            'tl_private' => $tl_private,
            'tl_tw' => $tl_tw,
        );
        if (empty($info)) {
            $value = json_encode($data_info);
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'TLPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $value = json_encode($data_info);
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'TLPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'TLPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setStyleConfig($type=0,$shop_id,$point_style, $balance_style)
    {
        $info= $this->config_module->getInfo(['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'COPYSTYLE']);
        if($type==1){
            $value = array(
                'point_style' => $point_style,
                'balance_style' => $balance_style,
            );
            $data = array(
                'instance_id' => $shop_id,
                'website_id' => $this->website_id,
                'key' => 'COPYSTYLE',
                'value' => json_encode($value),
                'desc' => '文案样式',
                'is_use' => 1
            );
            if(empty($info)){
                $res = $this->config_module->save($data);
            }else{
                $res = $this->config_module->save($data,['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'COPYSTYLE']);
            }
        }else{
            $value = array(
                'point_style' => $point_style,
                'balance_style' => $balance_style,
            );
            $data = array(
                'instance_id' => $shop_id,
                'website_id' => $this->website_id,
                'key' => 'COPYSTYLE',
                'value' => json_encode($value),
                'desc' => '文案样式',
                'is_use' => 1
            );
            if(empty($info)){
                $res = $this->config_module->save($data);
            }else{
                $res = $this->config_module->save($data,['instance_id' => $shop_id,'website_id' => $this->website_id,'key'=>'COPYSTYLE']);
            }
        }
        return $res;
    }

    public function setCompanyConfig($keyValue)
    {
        $config = new ConfigModel();
        $info = $config->getInfo(['key' => 'COMPANYCONFIGSET', 'website_id' => $this->website_id], '*');
        if($info){
            $array = array(
                'instance_id' => 0,
                'website_id' => $this->website_id,
                'key' => 'COMPANYCONFIGSET',
                'value' => $keyValue,
                'modify_time'=>time(),
                'desc' => '物流配置',
                'is_use' => 1
            );
            $res = $config->save($array,['id'=>$info['id']]);
        }else{
            $array = array(
                'instance_id' => 0,
                'website_id' => $this->website_id,
                'key' => 'COMPANYCONFIGSET',
                'value' => $keyValue,
                'create_time'=>time(),
                'desc' => '物流配置',
                'is_use' => 1
            );
            $res = $config->save($array);
        }
        return $res;
    }
    public function setQQConfig($instance_id, $appkey, $appsecret, $url, $call_back_url, $is_use)
    {
        Cache::set("qq_config" . $instance_id . $this->website_id, '');
        $info = array(
            'APP_KEY' => $appkey,
            'APP_SECRET' => $appsecret,
            'AUTHORIZE' => $url,
            'CALLBACK' => $call_back_url
        );
        $value = json_encode($info);
        $count = $this->config_module->where([
            'key' => 'QQLOGIN',
            'instance_id' => $instance_id,
            'website_id' => $this->website_id
        ])->count();
        if ($count > 0) {
            $data = array(
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $this->config_module->where([
                'key' => 'QQLOGIN',
                'instance_id' => $instance_id,
                'website_id' => $this->website_id
            ])->update($data);
            if ($res == 1) {
                return SUCCESS;
            } else {
                return UPDATA_FAIL;
            }
        } else {
            $data = array(
                'instance_id' => $instance_id,
                'website_id' => $this->website_id,
                'key' => 'QQLOGIN',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $this->config_module->save($data);
            return $res;
        }
        // TODO Auto-generated method stub
    }
    public function setBpayConfigApp($instanceid,$is_use)
    {
        $data = [];
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'BPAYAPP',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'BPAYAPP',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'BPAYAPP',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'BPAYAPP'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setDpayConfigApp($instanceid,$is_use)
    {
        $data = [];
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'DPAYAPP',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'DPAYAPP',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'DPAYAPP',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'DPAYAPP'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setWpayConfigApp($instanceid, $appid, $mch_id,$mch_key,$is_use)
    {
        $data = array(
            'appid' => $appid,
            'mch_id' => $mch_id,
            'mch_key' => $mch_key,
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'WPAYAPP',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'WPAYAPP',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'WPAYAPP',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'WPAYAPP'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setAlipayConfigApp($instanceid, $partnerid, $seller, $ali_key, $is_use, $appid,$ali_public_key, $ali_private_key)
    {
        $data = array(
            'ali_partnerid' => $partnerid,
            'ali_seller' => $seller,
            'ali_key' => $ali_key,
            'appid' => $appid,
            'ali_public_key' => $ali_public_key,
            'ali_private_key' => $ali_private_key,
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'ALIPAYAPP',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'ALIPAYAPP',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'ALIPAYAPP',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'ALIPAYAPP'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setWpayConfigMir($instanceid, $appid, $mch_id,$mch_key,$is_use)
    {
        $data = array(
            'appid' => $appid,
            'mchid' => $mch_id,
            'mch_key' => $mch_key,
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'MPPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'MPPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'MPPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'MPPAY'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setBpayConfigMir($instanceid,$is_use)
    {
        $data = [];
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'BPAYMP',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'BPAYMP',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'BPAYMP',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'BPAYMP'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function setDpayConfigMir($instanceid,$is_use)
    {
        $data = [];
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'DPAYMP',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'DPAYMP',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'DPAYMP',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'DPAYMP'
            ]);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    /**
     *
     * {@inheritdoc}
     *
     * @see \ata\api\IConfig::setUserNotice()
     */
    public function setUserNotice($instanceid, $keywords, $is_use)
    {
        $data = $keywords;
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'USERNOTICE',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'USERNOTICE',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'USERNOTICE'
            ]);
        }
        return $res;
    }
    /**
     * 设置微信和支付宝开关状态
     */
    public function setWpayStatusConfig($instanceid, $is_use, $type)
    {
        $config_module = new ConfigModel();
        $result = $config_module->getInfo([
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
            'key' => $type
        ], 'value');
        if (empty($result['value'])) {
            $info = array();
            $value = json_encode($info);
            $data = array(
                'is_use' => $is_use,
                'modify_time' => time(),
                'value' => $value
            );
        } else {
            $data = array(
                'is_use' => $is_use,
                'modify_time' => time()
            );
        }
        $res = $config_module->save($data, [
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
            'key' => $type
        ]);
        return $res;
    }
    /**
     *
     * {@inheritdoc}
     *
     * @see \ata\api\IConfig::getEmailMessage()
     */
    public function getEmailMessage($instanceid)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $info = $this->config_module->getInfo([
            'key' => 'EMAILMESSAGE',
            'instance_id' => $instanceid,
            'website_id' => $websiteid,
        ], 'value, is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'email_host' => '',
                    'email_port' => '',
                    'email_addr' => '',
                    'email_pass' => '',
                    'email_id' => '',
                    'email_is_security' => false
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \ata\api\IConfig::setEmailMessage()
     */
    public function setEmailMessage($instanceid, $email_host, $email_port, $email_addr, $email_id, $email_pass, $is_use, $email_is_security)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $data = array(
            'email_host' => $email_host,
            'email_port' => $email_port,
            'email_addr' => $email_addr,
            'email_id' => $email_id,
            'email_pass' => $email_pass,
            'email_is_security' => $email_is_security
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'EMAILMESSAGE',
            'instance_id' => $instanceid,
            'website_id' => $websiteid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $websiteid,
                'key' => 'EMAILMESSAGE',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'EMAILMESSAGE',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $websiteid,
                'key' => 'EMAILMESSAGE'
            ]);
        }
        return $res;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \ata\api\IConfig::getMobileMessage()
     */
    public function getMobileMessage($instanceid,$website_id=0)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = $website_id;
        }

        $info = $this->config_module->getInfo([
            'key' => 'MOBILEMESSAGE',
            'instance_id' => $instanceid,
            'website_id' => $websiteid
        ], 'value, is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appKey' => '',
                    'secretKey' => '',
                    'freeSignName' => '',
                    'album_num' => '',
                    'album_mobile' => '',
                    'user_type' => 2,
                    'international' => 2,
                    'int_sign_name' => 2,
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \ata\api\IConfig::setMobileMessage()
     */
    public function setMobileMessage($info = array())
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $data = array(
            'appKey' => $info['appKey'],
            'secretKey' => $info['secretKey'],
            'freeSignName' => $info['freeSignName'],
            'user_type' => $info['user_type'],
            'alarm_num' => $info['alarm_num'],
            'alarm_mobile' => $info['alarm_mobile'],
            'jd_sign_name' => $info['jd_sign_name'],
            'international' => $info['international'],
            'int_sign_name' => $info['int_sign_name'],
        );
        $value = json_encode($data);
        $messageInfo = $this->config_module->getInfo([
            'key' => 'MOBILEMESSAGE',
            'instance_id' => $info['instance_id'],
            'website_id' => $websiteid
        ], 'value');
        if (empty($messageInfo)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $info['instance_id'],
                'website_id' => $websiteid,
                'key' => 'MOBILEMESSAGE',
                'value' => $value,
                'is_use' => $info['is_use'],
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'MOBILEMESSAGE',
                'value' => $value,
                'is_use' => $info['is_use'],
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $info['instance_id'],
                'website_id' => $websiteid,
                'key' => 'MOBILEMESSAGE'
            ]);
        }
        return $res;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \ata\api\IConfig::getWinxinOpenPlatformConfig()
     */
    public function getWinxinOpenPlatformConfig($instanceid)
    {
        $info = $this->config_module->getInfo([
            'key' => 'WXOPENPLATFORM',
            'instance_id' => $instanceid
        ], 'value, is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appId' => '',
                    'appsecret' => '',
                    'encodingAesKey' => '',
                    'tk' => ''
                ),
                'is_use' => 1
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \ata\api\IConfig::setWinxinOpenPlatformConfig()
     */
    public function setWinxinOpenPlatformConfig($instanceid, $appid, $appsecret, $encodingAesKey, $tk)
    {
        $data = array(
            'appId' => $appid,
            'appsecret' => $appsecret,
            'encodingAesKey' => $encodingAesKey,
            'tk' => $tk
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'WXOPENPLATFORM',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'WXOPENPLATFORM',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'WXOPENPLATFORM',
                'value' => $value,
                'is_use' => 1,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'WXOPENPLATFORM'
            ]);
        }
        return $res;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::getLoginVerifyCodeConfig()
     */
    public function getLoginVerifyCodeConfig($instanceid)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $verify_config = Cache::get("LoginVerifyCodeConfig" . $instanceid . $websiteid);
        if (empty($verify_config)) {
            $info = $this->config_module->getInfo([
                'key' => 'LOGINVERIFYCODE',
                'instance_id' => $instanceid,
                'website_id' => $websiteid
            ], 'value, is_use');
            if (empty($info['value'])) {
                $verify_config = array(
                    'value' => array(
                        'pc' => 0
                    ),
                    'is_use' => 1
                );
                Cache::set("LoginVerifyCodeConfig" . $instanceid . $websiteid, $verify_config);
            } else {
                $info['value'] = json_decode($info['value'], true);
                $verify_config = $info;
                Cache::set("LoginVerifyCodeConfig" . $instanceid . $websiteid, $verify_config);
            }
        }
        return $verify_config;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::setLoginVerifyCodeConfig()
     */
    public function setLoginVerifyCodeConfig($instanceid, $pc)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $data = array(
            'pc' => $pc
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'LOGINVERIFYCODE',
            'instance_id' => $instanceid,
            'website_id' => $websiteid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $websiteid,
                'key' => 'LOGINVERIFYCODE',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'LOGINVERIFYCODE',
                'value' => $value,
                'is_use' => 1,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $websiteid,
                'key' => 'LOGINVERIFYCODE'
            ]);
        }
        Cache::set("LoginVerifyCodeConfig" . $instanceid . $websiteid, '');
        return $res;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::setInstanceWchatConfig()
     */
    public function setInstanceWchatConfig($type, $appid, $appsecret, $token,$public_name,$encodingAESKey)
    {
        $info = $this->config_module->getInfo([
            'key' => 'SHOPWCHAT',
            'instance_id' => 0,
            'website_id' => $this->website_id
        ], 'value');
        $data_info  = json_decode($info['value'],true);
        if($type==1){
            $data = array(
                'appid' => $data_info['appid'],
                'public_name' => $data_info['public_name'],
                'appsecret' => $data_info['appsecret'],
                'encodingAESKey' => $encodingAESKey,
                'token' => $data_info['token']
            );
        }else{
            $data = array(
                'appid' => $appid,
                'public_name' => $public_name,
                'appsecret' => $appsecret,
                'encodingAESKey' => $encodingAESKey,
                'token' => $token
            );
        }
        $value = json_encode($data);
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => 0,
                'website_id' => $this->website_id,
                'key' => 'SHOPWCHAT',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'SHOPWCHAT',
                'value' => $value,
                'is_use' => 1,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => 0,
                'website_id' => $this->website_id,
                'key' => 'SHOPWCHAT'
            ]);
        }
        if($info){
            $fans = new WeixinFansModel();
            $fans->delData([ 'website_id' => $this->website_id]);
            $group = new WeixinGroupModel();
            $group->delData([ 'website_id' => $this->website_id]);
        }
        return $res;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::getInstanceWchatConfig()
     */
    public function getInstanceWchatConfig($instance_id, $website_id)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $info = $this->config_module->getInfo([
            'key' => 'SHOPWCHAT',
            'instance_id' => $instance_id,
            'website_id' => $websiteid
        ], 'value');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appid' => '',
                    'appsecret' => '',
                    'token' => '',
                    'encodingAesKey' => ''
                ),
                'is_use' => 1
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::getOtherPayTypeConfig()
     */
    public function getOtherPayTypeConfig()
    {
        $info = $this->config_module->getInfo([
            'key' => 'OTHER_PAY',
            'instance_id' => 0
        ], 'value');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'is_coin_pay' => 0,
                    'is_balance_pay' => 0
                ),
                'is_use' => 1
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::setOtherPayTypeConfig()
     */
    public function setOtherPayTypeConfig($is_coin_pay, $is_balance_pay)
    {
        $data = array(
            'is_coin_pay' => $is_coin_pay,
            'is_balance_pay' => $is_balance_pay
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'OTHER_PAY',
            'instance_id' => 0
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => 0,
                'key' => 'OTHER_PAY',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'OTHER_PAY',
                'value' => $value,
                'is_use' => 1,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => 0,
                'key' => 'OTHER_PAY'
            ]);
        }
        return $res;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::setNotice()
     */
    public function setNotice($shopid, $notice_message, $is_enable)
    {
        $notice = new NoticeModel();
        $data = array(
            'notice_message' => $notice_message,
            'is_enable' => $is_enable,
            'website_id' => $this->website_id
        );
        $res = $notice->save($data, [
            'shopid' => $shopid,
            'website_id' => $this->website_id,
        ]);
        return $res;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::getNotice()
     */
    public function getNotice($shopid)
    {
        $notice = new NoticeModel();
        $notice_info = $notice->getInfo([
            'shopid' => $shopid,
            'website_id' => $this->website_id
        ]);
        if (empty($notice_info)) {
            $data = array(
                'shopid' => $shopid,
                'website_id' => $this->website_id,
                'notice_message' => '',
                'is_enable' => 0
            );
            $notice->save($data);
            $notice_info = $notice->getInfo([
                'shopid' => $shopid,
                'website_id' => $this->website_id
            ]);
        }
        return $notice_info;
    }
    /**
     * 分红文案设置
     */
    public function setSite($bonus_name,$bonus,$withdrawals_bonus,$withdrawal_bonus,$frozen_bonus,$bonus_details,$bonus_money,$bonus_order)
    {
        $agreement = $this->getConfig(0,"BONUSCOPYWRITING",$this->website_id);
        $value = array(
            'bonus_name' => $bonus_name,
            'bonus' => $bonus,
            'withdrawals_bonus' => $withdrawals_bonus,
            'withdrawal_bonus' => $withdrawal_bonus,
            'frozen_bonus' => $frozen_bonus,
            'bonus_details' => $bonus_details,
            'bonus_money' => $bonus_money,
            'bonus_order' => $bonus_order
        );
        if (! empty($agreement)) {
            $data = array(
                "value" => json_encode($value)
            );
            $res = $this->config_module->save($data, [
                "instance_id" => 0,
                "website_id" => $this->website_id,
                "key" => "BONUSCOPYWRITING"
            ]);
        } else {
            $res = $this->addConfig(0, "BONUSCOPYWRITING", $value, "分红文案", 1);
        }
        return $res;
    }
    /*
     * 获取分红文案设置
     *
     */
    public function getBonusSite($website_id){
        $bonus =$this->getConfig(0,"BONUSCOPYWRITING",$website_id);
        $bonus_info = json_decode($bonus['value'], true);
        return $bonus_info;
    }
    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::getConfig()
     */
    public function getConfig($instance_id, $key, $website_id = 0)
    {
        if ($website_id) {
            $websiteid = $website_id;
        } else {
            $websiteid = $this->website_id;
        }
        $config = new ConfigModel();
        $info = $config->getInfo([
            'instance_id' => $instance_id,
            'website_id' => $websiteid,
            'key' => $key
        ]);
        return $info;
    }

    /**
     * 只获取一条数据
     * @param $condition
     * @return ConfigModel
     * @throws \think\Exception\DbException
     */
    public function getConfigNew($condition)
    {
        $config_model = new ConfigModel();
        return $config_model::get($condition);
    }

    /**
     * 只获取一条数据
     * @param $condition
     * @return mixed
     * @throws \think\Exception\DbException
     */
    public function allConfigNew($condition)
    {
        $config_model = new ConfigModel();
        return $config_model::all($condition);
    }

    /**
     * 保存config信息
     * @param $data
     * @param array $condition
     *
     * @return int
     */
    public function saveConfigNew($data, array $condition = [])
    {
        $config_model = new ConfigModel();
        if (!empty($condition) && $config_model::where($condition)->count() == 1) {
            //update
            $data['modify_time'] = time();
            return $config_model->save($data, $condition);
        } else {
            $data['create_time'] = time();
            return $config_model->save($data);
        }
    }

    /*
     * 定时任务使用，获取设置
     */
    public function getConfigbyWebsiteId($website_id,$instance_id,$key)
    {
        $config = new ConfigModel();
        $info = $config->getInfo([
            'instance_id' => $instance_id,
            'website_id' => $website_id,
            'key' => $key
        ]);
        return $info;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::getConfig()
     */
    public function getBankList()
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config = new ConfigModel();
        $info = $config->getInfo([
            'website_id' => $websiteid,
        ]);
        return $info;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::setConfig()
     */
    public function setConfig($params)
    {
        foreach ($params as $key => $value) {
            if ($this->checkConfigKeyIsset($value['instance_id'], $value['key'])) {
                $res = $this->updateConfig($value['instance_id'], $value['key'], $value['value'], $value['desc'], $value['is_use']);
            } else {
                $res = $this->addConfig($value['instance_id'], $value['key'], $value['value'], $value['desc'], $value['is_use']);
            }
        }
        return $res;
    }

    /**
     * 添加设置
     *
     * @param unknown $instance_id
     * @param unknown $key
     * @param unknown $value
     * @param unknown $desc
     * @param unknown $is_use
     */
    public function addConfig($instance_id, $key, $value, $desc, $is_use)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config = new ConfigModel();
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $data = array(
            'instance_id' => $instance_id,
            'website_id' => $websiteid,
            'key' => $key,
            'value' => $value,
            'desc' => $desc,
            'is_use' => $is_use,
            'create_time' => time()
        );
        $res = $config->save($data);
        return $res;
    }

    /**
     * 修改配置
     *
     * @param unknown $instance_id
     * @param unknown $key
     * @param unknown $value
     * @param unknown $desc
     * @param unknown $is_use
     */
    public function updateConfig($instance_id, $key, $value, $desc, $is_use)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config = new ConfigModel();
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $data = array(
            'value' => $value,
            'desc' => $desc,
            'is_use' => $is_use,
            'modify_time' => time()
        );
        $res = $config->save($data, [
            'instance_id' => $instance_id,
            'website_id' => $websiteid,
            'key' => $key
        ]);
        return $res;
    }

    /**
     * 判断当前设置是否存在
     * 存在返回 true 不存在返回 false
     *
     * @param unknown $instance_id
     * @param unknown $key
     */
    public function checkConfigKeyIsset($instance_id, $key)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config = new ConfigModel();
        $num = $config->where([
            'instance_id' => $instance_id,
            'website_id' => $websiteid,
            'key' => $key
        ])->count();
        return $num > 0 ? true : false;
    }

    /**
     *
     * 得到店铺的系统通知的详情
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::getNoticeTemplateDetail()
     */
    public function getNoticeTemplateDetail($template_code, $type, $notify_type)
    {
        $notice_template_model = new NoticeTemplateModel();
        $condition = array(
            "template_type" => $type,
            "notify_type" => $notify_type,
            "template_code" => $template_code,
            'website_id' => $this->website_id
        );
        $template_list = $notice_template_model->getInfo($condition, "*");
        return $template_list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::getNoticeTemplateOneDetail()
     */
    public function getNoticeTemplateOneDetail($shop_id, $template_type, $template_code)
    {
        $notice_template_model = new NoticeTemplateModel();
        $info = $notice_template_model->getInfo([
            'instance_id' => $shop_id,
            'template_type' => $template_type,
            'template_code' => $template_code
        ]);
        return $info;
    }

    public function getTemplateTypeDetail($type_id)
    {
        $notice_template_model = new NoticeTemplateTypeModel();
        $info = $notice_template_model->getInfo([
            'type_id' => $type_id
        ]);
        return $info;
    }

    /**
     * 更新通知模板的信息
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::updateNoticeTemplate()
     */
    public function updateNoticeTemplate($is_enable, $template_type,$template_code, $template_content, $template_title, $notify_type, $notification_mode,$int_template_title)
    {

        $notice_template_model = new NoticeTemplateModel();
        $count = $notice_template_model->getCount([
            "instance_id" => 0,
            "template_type" => $template_type,
            "template_code" => $template_code,
            "notify_type" => $notify_type,
            "website_id" => $this->website_id
        ]);
        if ($count > 0) {
            // 更新
            $data = array(
                "template_title" => $template_title,
                "int_template_title" => $int_template_title,
                "template_content" => $template_content,
                "is_enable" => $is_enable,
                "modify_time" => time(),
                "notification_mode" => $notification_mode
            );
            $res = $notice_template_model->save($data, [
                "instance_id" => 0,
                "template_type" => $template_type,
                "template_code" => $template_code,
                "notify_type" => $notify_type,
                "website_id" => $this->website_id
            ]);
        } else {
            // 添加
            $data = array(
                "instance_id" => 0,
                "template_type" => $template_type,
                "template_code" => $template_code,
                "template_title" => $template_title,
                "int_template_title" => $int_template_title,
                "template_content" => $template_content,
                "is_enable" => $is_enable,
                "modify_time" => time(),
                "notify_type" => $notify_type,
                "notification_mode" => $notification_mode,
                "website_id" => $this->website_id
            );
            $res = $notice_template_model->save($data);
        }
        return $res;
    }



    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::getNoticeConfig()
     */
    public function getMobileConfig($shop_id)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config_model = new ConfigModel();
        $condition = array(
            'instance_id' => $shop_id,
            'website_id' => $websiteid,
            'key' => 'MOBILEMESSAGE'
        );
        $notify_list = $config_model->getQuery($condition, "*", "");
        if (!empty($notify_list)) {
            if ($notify_list["key"] == "MOBILEMESSAGE") {
                $notify_list["notify_name"] = "短信通知";
            }

            return $notify_list;
        } else {
            return null;
        }
    }

    /**
     * 得到店铺的email的配置信息
     *
     * @param unknown $shop_id
     */
    public function getNoticeEmailConfig($shop_id)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config_model = new ConfigModel();
        $condition = array(
            'instance_id' => $shop_id,
            'website_id' => $websiteid,
            'key' => 'EMAILMESSAGE'
        );
        $email_detail = $config_model->getQuery($condition, "*", "");
        return $email_detail;
    }

    /**
     * 得到店铺的短信配置信息
     *
     * @param unknown $shop_id
     */
    public function getNoticeMobileConfig($shop_id)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config_model = new ConfigModel();
        $condition = array(
            'instance_id' => $shop_id,
            'website_id' => $websiteid,
            'key' => 'MOBILEMESSAGE'
        );
        $mobile_detail = $config_model->getQuery($condition, "*", "");
        return $mobile_detail;
    }

    /**
     * 得到店铺的邮件发送项
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::getNoticeSendItem()
     */
    public function getNoticeTemplateItem($template_code,$item_type)
    {
        $notice_model = new NoticeTemplateItemModel();
        $item_list = $notice_model->where("FIND_IN_SET('" . $template_code . "', type_ids)")->where(['item_type' => ['IN', $item_type]])->select();
        return $item_list;
    }

    /**
     * 得到店铺模板的集合
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::getNoticeTemplateType()
     */
    public function getNoticeTemplateType($notify_type, $template_code)
    {
        $notice_type_model = new NoticeTemplateTypeModel();
        $condition['notify_type'] = $notify_type;
        $condition['template_code'] = $template_code;
        $type_list = $notice_type_model->getInfo($condition, '*');
        return $type_list;
    }

    public function getTemplateType()
    {
        $notice_type_model = new NoticeTemplateTypeModel();
        $anti = $notice_type_model->where(['template_code' => 'anti_forgot_password'])->find();
        if(!$anti){
            $this->setDefaultAnti();
        }
	$checkSendPass = $notice_type_model->getInfo(['template_code' => 'send_password'],'type_id');
        if(!$checkSendPass){
            $notice_type_model->save([
                'template_name' => '发送会员密码',
                'template_code' => 'send_password',
                'template_type' => 'sms',
                'website_id' => 0,
                'sort' => 29,
                'is_platform' => 1,
                'sms_type' => 2,
                'notify_type' => 'user',
                'sample' => '您的会员密码是${password}。',
            ]);
        }
        $type_list = $notice_type_model->getQuery(['is_platform' => 1], '*', 'sort asc');
        $notice_model = new NoticeTemplateModel();
        foreach ($type_list as $k => $v) {
            $is_sms_enable = $notice_model->getInfo(['website_id' => $this->website_id, 'template_type' => 'sms', 'template_code' => $v['template_code']], 'is_enable')['is_enable'];
            if($v['sms_type'] == 1 && !$is_sms_enable){
                $is_sms_enable = $this->setTemplateDefault($v['template_code']);//没有数据的话，默认开启验证码类型短信
            }
            $type_list[$k]['is_sms_enable'] = $is_sms_enable;
            $type_list[$k]['is_email_enable'] = $notice_model->getInfo(['website_id' => $this->website_id, 'template_type' => 'email', 'template_code' => $v['template_code']], 'is_enable')['is_enable'];
        }
        return $type_list;
    }
    
    public function setTemplateDefault($template_code = ''){
        if(!$template_code){
            return 0;
        }
        $notice_template_model = new NoticeTemplateModel();
        $notice_template = $notice_template_model->getInfo(['template_code' => $template_code, 'website_id' => $this->website_id]);
        if($notice_template){
            return 0;//有数据，只是没开启
        }
        $notice_type_model = new NoticeTemplateTypeModel();
        $notice_type = $notice_type_model->getInfo(['template_code' => $template_code],'*');
        $data = [
            'template_type' => 'sms',
            'template_code' => $template_code,
            'template_content' => $notice_type['sample'],
            'is_enable' => 1,
            'modify_time' => time(),
            'notify_type' => $notice_type['notify_type'],
            'website_id' => $this->website_id,
            'instance_id' => $this->instance_id
        ];
        $res = $notice_template_model->save($data);
        if(!$res){
            return 0;
        }
        return 1;
    }
    /*
     * 更改模板是否开启短信、邮箱验证
     * **/
    public function updateNoticeTemplateEnable($condition, $is_enable)
    {
        $notice_model = new NoticeTemplateModel();
        $res['is_enable'] = $is_enable;
        $bool = $notice_model->where($condition)->update($res);
        return $bool;
    }
    /*
     * 更改支付方式是否可用
     * **/
    public function updateConfigIsuse($condition, $is_use)
    {
        $config_model = new ConfigModel();
        $res['is_use'] = $is_use;
        $bool = $config_model->where($condition)->update($res);
        return $bool;
    }
    /**
     * WAP、PC和微信支付的通知项
     *
     * @param unknown $shop_id
     * @return string|NULL
     */
    public function getPayConfig($shop_id)
    {
        $config_model = new ConfigModel();
        $b_info = $config_model->getInfo(['key'=>'BPAY','website_id' => $this->website_id],'*');
        if(empty($b_info)){
            $this->setBpayConfig($shop_id, '', '', '', 0);
        }
        $d_info = $config_model->getInfo(['key'=>'DPAY','website_id' => $this->website_id],'*');
        if(empty($d_info)){
            $this->setDpayConfig($shop_id, '', '', '', 0);
        }
        $wx_info = $config_model->getInfo(['key'=>'WPAY','website_id' => $this->website_id],'*');
        if(empty($wx_info)){
            $this->setWpayConfig($shop_id, '',  '', '', 0,'','',0);
        }
        $ali_info = $config_model->getInfo(['key'=>'ALIPAY','website_id' => $this->website_id],'*');
        if(empty($ali_info)){
            $this->setAlipayConfig($shop_id, '', '', '', 0,'','','');
        }
        $tl_info = $config_model->getInfo(['key'=>'TLPAY','website_id' => $this->website_id],'*');
        if(empty($tl_info)){
            $this->setTlConfig($shop_id, '', '', '', '','','','','',0,0);
        }
        $eth_info = $config_model->getInfo(['key'=>'ETHPAY','website_id' => $this->website_id],'*');
        if(empty($eth_info)){
            $this->setEthConfig($shop_id,0);
        }
        $eos_info = $config_model->getInfo(['key'=>'EOSPAY','website_id' => $this->website_id],'*');
        if(empty($eos_info)){
            $this->setEosConfig($shop_id,0);
        }
		$glopay_info = $config_model->getInfo(['key'=>'GLOPAY','website_id' => $this->website_id],'*');
        if(empty($glopay_info)){
            $this->setGpayConfig(0, '',  '', '','', 0);
        }
        $condition = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => array(
                'in',
                'WPAY,ALIPAY,BPAY,DPAY,ETHPAY,EOSPAY,TLPAY,GLOPAY'
            )
        );
        $notify_list = $config_model->getQuery($condition, "*", "");
        if (!empty($notify_list)) {
            for ($i = 0; $i < count($notify_list); $i++) {
                if ($notify_list[$i]["key"] == "WPAY") {
                    $notify_list[$i]["logo"] = "/public/platform/static/images/wechat-pay.png";
                    $notify_list[$i]["pay_name"] = "微信支付";
                    $notify_list[$i]["desc"] = "该支付方式适用于网页端(微信端、H5端、PC端)与全渠道提现";
                } elseif ($notify_list[$i]["key"] == "ALIPAY") {
                    $notify_list[$i]["pay_name"] = "支付宝支付";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/alipay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于网页端(微信端、H5端、PC端)与全渠道提现";
                } elseif ($notify_list[$i]["key"] == "BPAY") {
                    $notify_list[$i]["pay_name"] = "余额支付";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/balance-pay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于网页端(微信端、H5端、PC端)";
                } elseif ($notify_list[$i]["key"] == "TLPAY") {
                    $notify_list[$i]["pay_name"] = "通联支付(银行卡)";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/bank-pay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于网页端(微信端、H5端、PC端)与全渠道提现";
                } elseif ($notify_list[$i]["key"] == "DPAY") {
                    $notify_list[$i]["pay_name"] = "货到付款";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/to-pay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于网页端(微信端、H5端、PC端)";
                }elseif ($notify_list[$i]["key"] == "ETHPAY") {
                    $notify_list[$i]["pay_name"] = "ETH付款";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/eth-pay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于网页端(微信端、H5端、PC端)";
                }elseif ($notify_list[$i]["key"] == "EOSPAY") {
                    $notify_list[$i]["pay_name"] = "EOS付款";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/eos-pay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于网页端(微信端、H5端、PC端)";
                }elseif ($notify_list[$i]["key"] == "GLOPAY") {
                    $notify_list[$i]["pay_name"] = "GlobePay";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/logo_horizontal.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于网页端(微信端、H5端、PC端)";
                }
            }
            return $notify_list;
        } else {
            $this->setBpayConfig($shop_id, '', '', '', 0);
            $this->setDpayConfig($shop_id, '', '', '', 0);
            $this->setWpayConfig($shop_id, '', '', '', '', 0,'',0);
            $this->setAlipayConfig($shop_id, '', '', '', 0,'','','');
            $this->setTlConfig($shop_id, '', '', '', '','','','','',0,0);
            $this->setEthConfig($shop_id,0);
            $this->setEosConfig($shop_id, 0);
			$this->setGpayConfig(0, '',  '', '','', 0);
            return $this->getPayConfig($shop_id);
        }

    }
    /**
     * app支付的通知项
     *
     * @param unknown $shop_id
     * @return string|NULL
     */
    public function getAppPayConfig($shop_id)
    {
        $config_model = new ConfigModel();
        $b_info = $config_model->getInfo(['key'=>'BPAYAPP','website_id' => $this->website_id],'*');
        if(empty($b_info)){
            $this->setBpayConfigApp(0,  0);
        }
        $d_info = $config_model->getInfo(['key'=>'DPAYAPP','website_id' => $this->website_id],'*');
        if(empty($d_info)){
            $this->setDpayConfigApp(0, 0);
        }
        $wx_info = $config_model->getInfo(['key'=>'WPAYAPP','website_id' => $this->website_id],'*');
        if(empty($wx_info)){
            $this->setWpayConfigApp(0, '',  '', '', 0);
        }
        $ali_info = $config_model->getInfo(['key'=>'ALIPAYAPP','website_id' => $this->website_id],'*');
        if(empty($ali_info)){
            $this->setAlipayConfigApp($shop_id, '', '', '', 0,'','','');
        }
        $condition = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => array(
                'in',
                'WPAYAPP,ALIPAYAPP,BPAYAPP,DPAYAPP'
            )
        );
        $notify_list = $config_model->getQuery($condition, "*", "");
        if (!empty($notify_list)) {
            for ($i = 0; $i < count($notify_list); $i++) {
                if ($notify_list[$i]["key"] == "WPAYAPP") {
                    $notify_list[$i]["logo"] = "/public/platform/static/images/wechat-pay.png";
                    $notify_list[$i]["pay_name"] = "微信支付";
                    $notify_list[$i]["desc"] = "该支付方式适用于APP端支付";
                } elseif ($notify_list[$i]["key"] == "ALIPAYAPP") {
                    $notify_list[$i]["pay_name"] = "支付宝支付";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/alipay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于APP端支付";
                } elseif ($notify_list[$i]["key"] == "BPAYAPP") {
                    $notify_list[$i]["pay_name"] = "余额支付";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/balance-pay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于APP端支付";
                } elseif ($notify_list[$i]["key"] == "DPAYAPP") {
                    $notify_list[$i]["pay_name"] = "货到付款";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/to-pay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于APP端支付";
                }
            }
            return $notify_list;
        } else {
            $this->setBpayConfigApp(0,0);
            $this->setDpayConfigApp(0, 0);
            $this->setWpayConfigApp(0, '', '', '', 0);
            $this->setAlipayConfigApp(0, '', '', '', 0,'','','');
            return $this->getAppPayConfig($shop_id);
        }

    }
    /**
     * 小程序支付的通知项
     *
     * @param unknown $shop_id
     * @return string|NULL
     */
    public function getPayConfigMir($shop_id)
    {
        $config_model = new ConfigModel();
        $b_info = $config_model->getInfo(['key'=>'BPAYMP','website_id' => $this->website_id],'*');
        if(empty($b_info)){
            $this->setBpayConfigMir(0,  0);
        }
        $d_info = $config_model->getInfo(['key'=>'DPAYMP','website_id' => $this->website_id],'*');
        if(empty($d_info)){
            $this->setDpayConfigMir(0, 0);
        }
        $wx_info = $config_model->getInfo(['key'=>'MPPAY','website_id' => $this->website_id],'*');
        if(empty($wx_info)){
            $this->setWpayConfigMir(0, '',  '', '', 0);
        }
		$gp_info = $config_model->getInfo(['key'=>'GPPAY','website_id' => $this->website_id],'*');
        if(empty($gp_info)){
            $this->setGpayConfigMir(0, '',  '', '','', 0);
        }
        $condition = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => array(
                'in',
                'BPAYMP,DPAYMP,MPPAY,GPPAY'
            )
        );
        $notify_list = $config_model->getQuery($condition, "*", "");
        if (!empty($notify_list)) {
            for ($i = 0; $i < count($notify_list); $i++) {
                if ($notify_list[$i]["key"] == "MPPAY") {
                    $notify_list[$i]["logo"] = "/public/platform/static/images/wechat-pay.png";
                    $notify_list[$i]["pay_name"] = "微信支付";
                    $notify_list[$i]["desc"] = "该支付方式适用于小程序端支付";
                } elseif ($notify_list[$i]["key"] == "BPAYMP") {
                    $notify_list[$i]["pay_name"] = "余额支付";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/balance-pay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于小程序端支付";
                } elseif ($notify_list[$i]["key"] == "DPAYMP") {
                    $notify_list[$i]["pay_name"] = "货到付款";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/to-pay.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于小程序端支付";
                } elseif ($notify_list[$i]["key"] == "GPPAY") {
                    $notify_list[$i]["pay_name"] = "GlobePay";
                    $notify_list[$i]["logo"] = "/public/platform/static/images/logo_horizontal.png";
                    $notify_list[$i]["desc"] = "该支付方式适用于小程序端支付";
                }
            }
            return $notify_list;
        } else {
            $this->setBpayConfigMir(0,0);
            $this->setDpayConfigMir(0, 0);
            $this->setWpayConfigMir(0, '', '', '', 0);
			$this->setGpayConfigMir(0, '', '', '','', 0);
            return $this->getPayConfigMir($shop_id);
        }

    }
    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::getBalanceWithdrawConfig()
     */
    public function getBalanceWithdrawConfig($shop_id)
    {
        $key = 'WITHDRAW_BALANCE';
        $info = $this->getConfig($shop_id, $key);
        if($info){
            $info['value'] = json_decode($info['value'], true);
        }else{
            $params[0] = array(
                'instance_id' => $shop_id,
                'website_id' => $this->website_id,
                'key' => $key,
                'value' =>[],
                'desc' => '提现设置',
                'is_use' => 0
            );
            $this->setConfig($params);
        }
        return $info;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::setBalanceWithdrawConfig()
     */
    public function setBalanceWithdrawConfig($shop_id, $key, $value, $is_use)
    {
        $params[0] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => $key,
            'value' => array(
                'withdraw_cash_min' => $value['withdraw_cash_min'],
                'member_withdraw_poundage' => $value['member_withdraw_poundage'],
                'withdraw_poundage' => $value['withdraw_poundage'],
                'withdraw_message' => $value['withdraw_message'],
                'is_examine' => $value['is_examine'],
                'make_money' => $value['make_money'],
                'withdrawals_begin' => $value['withdrawals_begin'],
                'withdrawals_end' => $value['withdrawals_end']
            ),
            'desc' => '提现设置',
            'is_use' => $is_use
        );
        $res = $this->setConfig($params);
        return $res;
    }

    public function getShopConfigNew($shop_id, array $default_return_key_array = [])
    {
        $config_model = new ConfigModel();
        $website_id = $this->website_id ?: 0;
        if (empty($default_return_key_array)) {
            $default_return_key_array = ['ORDER_AUTO_DELIVERY', 'ORDER_BALANCE_PAY', 'ORDER_DELIVERY_COMPLETE_TIME', 'ORDER_SHOW_BUY_RECORD', 'ORDER_INVOICE_TAX',
                'ORDER_INVOICE_CONTENT', 'ORDER_DELIVERY_PAY', 'BUYER_SELF_LIFTING', 'SHOPPING_BACK_POINTS', 'POINT_INVOICE_TAX', 'IS_POINT', 'INTEGRAL_CALCULATION',
                'ORDER_BUY_CLOSE_TIME', 'ORDER_IS_LOGISTICS', 'ORDER_SELLER_DISPATCHING'];
        }
        $config_info = $config_model::all(['instance_id' => $shop_id, 'website_id' => $website_id, 'key' => ['IN', $default_return_key_array]]);

        $return_array = [];
        foreach ($config_info as $c) {
            if (in_array($c['key'], $default_return_key_array)) {
                switch ($c['key']) {
                    case 'ORDER_BUY_CLOSE_TIME' :
                        $return_array['order_buy_close_time'] = $c['value'] ?: 60;
                        break;
                    case 'ORDER_IS_LOGISTICS' :
                        $return_array['is_logistics'] = $c['value'];
                        break;
                    case 'ORDER_SELLER_DISPATCHING':
                        $return_array['seller_dispatching'] = $c['value'] ?: '';
                        break;
                    default:
                        // 数组key == 字段key转小写 && 否定时为’‘ 的情况使用
                        $return_array[strtolower($c['key'])] = $c['value'] ?: '';
                }
                //得到 没有设置值 的数组，这些数组来源于function getShopConfig
                unset($default_return_key_array[array_search($c['key'], $default_return_key_array)]);
            }
        }
        //有些店铺没设置一些包含在default_return_key_array里面的key，这种情况有可能导致使用的时候出现index不存在的错误，所以在这里给这些值设置为空
        foreach ($default_return_key_array as $rest) {
            $return_array[strtolower($rest)] = '';
        }

        return $return_array;
    }
    /**
     * @param int $convert_rate 积分抵扣 定义多少积分等于多少钱，用于退款退货
     * @param int $shopping_back_points 购物返积分节点 1-订单已完成 2-已收货 3-支付完成
     * @param int $point_invoice_tax 购物返积分比例
     * @param int $is_point 购物返积分是否开启 0-未开启 1-开启
     * @param int $integral_calculation 积分计算方式 1-订单总价 2-商品总价 3-实际支付金额
     *
    */
    public function setShopConfig($shop_id, $order_auto_delivery,$convert_rate, $order_delivery_complete_time, $order_buy_close_time, $shopping_back_points, $point_invoice_tax, $is_point, $integral_calculation,$is_point_deduction,$point_deduction_calculation,$point_deduction_max,$is_translation,$translation_time,$translation_text, $is_transfer,$is_transfer_charge,$charge_type,$charge_pares,$charge_pares_min,$charge_pares2, $is_point_transfer,$is_point_transfer_charge,$point_charge_type,$point_charge_pares,$point_charge_pares_min,$point_charge_pares2,$has_express)
    {
        $array[0] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'ORDER_AUTO_DELIVERY',
            'value' => $order_auto_delivery,
            'desc' => '订单多长时间自动收货',
            'is_use' => 1
        );
        $array[1] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'ORDER_DELIVERY_COMPLETE_TIME',
            'value' => $order_delivery_complete_time,
            'desc' => '收货后多长时间自动完成',
            'is_use' => 1
        );
        $array[2] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'ORDER_BUY_CLOSE_TIME',
            'value' => $order_buy_close_time,
            'desc' => '订单自动关闭时间',
            'is_use' => 1
        );
        $array[3] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'POINT_DEDUCTION_NUM',
            'value' => $convert_rate,
            'desc' => '积分抵扣额度',
            'is_use' => 1
        );
        $array[4] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'IS_POINT',
            'value' => $is_point,
            'desc' => '购物返积分是否开启',
            'is_use' => 1
        );
        $array[5] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'SHOPPING_BACK_POINTS',
            'value' => $shopping_back_points,
            'desc' => '购物返积分节点设置',//1-订单已完成 2-已收货 3-支付完成
            'is_use' => 1
        );
        $array[6] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'INTEGRAL_CALCULATION',
            'value' => $integral_calculation,
            'desc' => '积分计算方式',//1-订单总价 2-商品总价 3-实际支付金额
            'is_use' => 1
        );
        $array[7] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'POINT_INVOICE_TAX',
            'value' => $point_invoice_tax,
            'desc' => '购物返积分利率设置',
            'is_use' => 1
        );
        $array[8] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'IS_POINT_DEDUCTION',
            'value' => $is_point_deduction,
            'desc' => '积分抵扣是否开启',
            'is_use' => 1
        );
        $array[9] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'POINT_DEDUCTION_CALCULATION',
            'value' => $point_deduction_calculation,
            'desc' => '积分抵扣计算方式',//1-订单总价 2-商品总价 3-实际支付金额
            'is_use' => 1
        );
        $array[10] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'POINT_DEDUCTION__MAX',
            'value' => $point_deduction_max,
            'desc' => '积分最大抵扣',
            'is_use' => 1
        );
        //自动评论设置 $is_translation,$translation_time,$translation_text
        $array[11] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'IS_TRANSLATION',
            'value' => $is_translation,
            'desc' => '是否开启自动评论',
            'is_use' => 1
        );
        $array[12] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'TRANSLATION_TIME',
            'value' => $translation_time,
            'desc' => '自动评论时间',
            'is_use' => 1
        );
        $array[13] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'TRANSLATION_TEXT',
            'value' => $translation_text,
            'desc' => '自动评论内容',
            'is_use' => 1
        );
        //余额转账设置  
        
        $array[14] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'IS_TRANSFER',
            'value' => $is_transfer,
            'desc' => '余额转账设置',
            'is_use' => 1
        );
        $array[15] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'IS_TRANSFER_CHARGE',
            'value' => $is_transfer_charge,
            'desc' => '余额转账费率设置',
            'is_use' => 1
        );
        $array[16] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'CHARGE_TYPE',
            'value' => $charge_type,
            'desc' => '余额转账费率类型',
            'is_use' => 1
        );
        $array[17] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'CHARGE_PARES',
            'value' => $charge_pares,
            'desc' => '余额转账费率比例',
            'is_use' => 1
        );
        $array[18] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'CHARGE_PARES_MIN',
            'value' => $charge_pares_min,
            'desc' => '余额转账费率最低限制',
            'is_use' => 1
        );
        $array[19] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'CHARGE_PARES2',
            'value' => $charge_pares2,
            'desc' => '余额转账费率固定',
            'is_use' => 1
        );

        $array[20] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'IS_POINT_TRANSFER',
            'value' => $is_point_transfer,
            'desc' => '积分余额转账设置',
            'is_use' => 1
        );
        $array[21] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'IS_POINT_TRANSFER_CHARGE',
            'value' => $is_point_transfer_charge,
            'desc' => '积分余额转账费率设置',
            'is_use' => 1
        );
        $array[22] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'POINT_CHARGE_TYPE',
            'value' => $point_charge_type,
            'desc' => '积分余额转账费率类型',
            'is_use' => 1
        );
        $array[23] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'POINT_CHARGE_PARES',
            'value' => $point_charge_pares,
            'desc' => '积分余额转账费率比例',
            'is_use' => 1
        );
        $array[24] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'POINT_CHARGE_PARES_MIN',
            'value' => $point_charge_pares_min,
            'desc' => '积分余额转账费率最低限制',
            'is_use' => 1
        );
        $array[25] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'POINT_CHARGE_PARES2',
            'value' => $point_charge_pares2,
            'desc' => '积分余额转账费率固定',
            'is_use' => 1
        );
        $array[25] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'HAS_EXPRESS',
            'value' => $has_express,
            'desc' => '是否开启快递配送',//0:不开启  1:开启
            'is_use' => 1
        );
        $res = $this->setConfig($array);
        return $res;
    }
    public function getShopConfig($shop_id, $website_id = '')
    {
        $order_auto_delivery = $this->getConfig($shop_id, 'ORDER_AUTO_DELIVERY' ,$website_id);//
        $order_delivery_complete_time = $this->getConfig($shop_id, 'ORDER_DELIVERY_COMPLETE_TIME', $website_id);//
        $convert_rate = $this->getConfig($shop_id, 'POINT_DEDUCTION_NUM', $website_id);//
        $order_buy_close_time = $this->getConfig($shop_id, 'ORDER_BUY_CLOSE_TIME', $website_id);
        $shopping_back_points = $this->getConfig($shop_id, 'SHOPPING_BACK_POINTS', $website_id);
        $point_invoice_tax = $this->getConfig($shop_id, 'POINT_INVOICE_TAX', $website_id);
        $is_point = $this->getConfig($shop_id, 'IS_POINT', $website_id);
        $integral_calculation = $this->getConfig($shop_id, 'INTEGRAL_CALCULATION', $website_id);
        $is_point_deduction = $this->getConfig($shop_id, 'IS_POINT_DEDUCTION', $website_id);
        $point_deduction_calculation = $this->getConfig($shop_id, 'POINT_DEDUCTION_CALCULATION', $website_id);
        $point_deduction_max = $this->getConfig($shop_id, 'POINT_DEDUCTION__MAX', $website_id);
        //获取自动评论设置 
        $is_translation = $this->getConfig($shop_id, 'IS_TRANSLATION', $website_id);
        $translation_time = $this->getConfig($shop_id, 'TRANSLATION_TIME', $website_id);
        $translation_text = $this->getConfig($shop_id, 'TRANSLATION_TEXT', $website_id);
        //获取余额转账设置
        $is_transfer = $this->getConfig($shop_id, 'IS_TRANSFER', $website_id);
        $is_transfer_charge = $this->getConfig($shop_id, 'IS_TRANSFER_CHARGE', $website_id);
        $charge_type = $this->getConfig($shop_id, 'CHARGE_TYPE', $website_id);
        $charge_pares = $this->getConfig($shop_id, 'CHARGE_PARES', $website_id);
        $charge_pares_min = $this->getConfig($shop_id, 'CHARGE_PARES_MIN', $website_id);
        $charge_pares2 = $this->getConfig($shop_id, 'CHARGE_PARES2', $website_id);
        //获取积分余额转账设置
        $is_point_transfer = $this->getConfig($shop_id, 'IS_POINT_TRANSFER', $website_id);
        $is_point_transfer_charge = $this->getConfig($shop_id, 'IS_POINT_TRANSFER_CHARGE', $website_id);
        $point_charge_type = $this->getConfig($shop_id, 'POINT_CHARGE_TYPE', $website_id);
        $point_charge_pares = $this->getConfig($shop_id, 'POINT_CHARGE_PARES', $website_id);
        $point_charge_pares_min = $this->getConfig($shop_id, 'POINT_CHARGE_PARES_MIN', $website_id);
        $point_charge_pares2 = $this->getConfig($shop_id, 'POINT_CHARGE_PARES2', $website_id);
        //是否有快递配送
        $has_express = $this->getConfig($shop_id, 'HAS_EXPRESS', $website_id);
        $array = array(
            'order_auto_delivery' => $order_auto_delivery['value'] ? $order_auto_delivery['value'] : 0,
            'order_delivery_complete_time' => $order_delivery_complete_time['value'],
            'convert_rate' => $convert_rate['value'] ? $convert_rate['value'] : '',
            'order_buy_close_time' => $order_buy_close_time['value'] ? $order_buy_close_time['value'] : '',
            'shopping_back_points' => $shopping_back_points['value'] ? $shopping_back_points['value'] : 0,
            'point_invoice_tax' => $point_invoice_tax['value'] ? $point_invoice_tax['value'] : 0,
            'is_point' => $is_point['value'],
            'integral_calculation' => $integral_calculation['value'] ? $integral_calculation['value'] : 0,
            'is_point_deduction' => $is_point_deduction['value'],
            'point_deduction_calculation' => $point_deduction_calculation['value'] ? $point_deduction_calculation['value'] : 0,
            'point_deduction_max' => $point_deduction_max['value'] ? $point_deduction_max['value'] : 0,
            'is_translation' => $is_translation['value'] ? $is_translation['value'] : 0,
            'translation_time' => $translation_time['value'] ? $translation_time['value'] : 0,
            'translation_text' => $translation_text['value'] ? $translation_text['value'] : '',

            'is_transfer' => $is_transfer['value'] ? $is_transfer['value'] : 0,
            'is_transfer_charge' => $is_transfer_charge['value'] ? $is_transfer_charge['value'] : 0,
            'charge_type' => $charge_type['value'] ? $charge_type['value'] : 1,
            'charge_pares' => $charge_pares['value'] ? $charge_pares['value'] : 0,
            'charge_pares_min' => $charge_pares_min['value'] ? $charge_pares_min['value'] : 0,
            'charge_pares2' => $charge_pares2['value'] ? $charge_pares2['value'] : 0,

            'is_point_transfer' => $is_point_transfer['value'] ? $is_point_transfer['value'] : 0,
            'is_point_transfer_charge' => $is_point_transfer_charge['value'] ? $is_point_transfer_charge['value'] : 0,
            'point_charge_type' => $point_charge_type['value'] ? $point_charge_type['value'] : 1,
            'point_charge_pares' => $point_charge_pares['value'] ? $point_charge_pares['value'] : 0,
            'point_charge_pares_min' => $point_charge_pares_min['value'] ? $point_charge_pares_min['value'] : 0,
            'point_charge_pares2' => $point_charge_pares2['value'] ? $point_charge_pares2['value'] : 0,

            'has_express' => $has_express['value']
        );

        if ($array['order_buy_close_time'] == 0) {
            $array['order_buy_close_time'] = 60;
        }

        return $array;
    }

    public function SetIntegralConfig($shop_id, $register, $sign, $share)
    {
        $array[0] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'REGISTER_INTEGRAL',
            'value' => $register,
            'desc' => '注册送积分',
            'is_use' => 1
        );
        $array[1] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'SIGN_INTEGRAL',
            'value' => $sign,
            'desc' => '签到送积分',
            'is_use' => 1
        );
        $array[2] = array(
            'instance_id' => $shop_id,
            'website_id' => $this->website_id,
            'key' => 'SHARE_INTEGRAL',
            'value' => $share,
            'desc' => '分享送积分',
            'is_use' => 1
        );
        $res = $this->setConfig($array);
        return $res;
    }

    public function getIntegralConfig($shop_id)
    {
        $register_integral = $this->getConfig($shop_id, 'REGISTER_INTEGRAL');
        $sign_integral = $this->getConfig($shop_id, 'SIGN_INTEGRAL');
        $share_integral = $this->getConfig($shop_id, 'SHARE_INTEGRAL');
        if (empty($register_integral) || empty($sign_integral) || empty($share_integral)) {
            $this->SetIntegralConfig($shop_id, '', '', '');
            $array = array(
                'register_integral' => '',
                'sign_integral' => '',
                'share_integral' => ''
            );
        } else {
            $array = array(
                'register_integral' => $register_integral['value'],
                'sign_integral' => $sign_integral['value'],
                'share_integral' => $share_integral['value']
            );
        }
        return $array;
    }

    /**
     * 修改状态
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::updateConfigEnable()
     */
    public function updateConfigEnable($id, $is_use)
    {
        $config_model = new ConfigModel();
        $data = array(
            "is_use" => $is_use,
            "modify_time" => time()
        );
        $retval = $config_model->save($data, [
            "id" => $id
        ]);
        return $retval;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::getRegisterAndVisit()
     */
    public function getRegisterAndVisit($shop_id)
    {
        $register_and_visit = $this->getConfig($shop_id, 'REGISTERANDVISIT');
        if (empty($register_and_visit) || $register_and_visit == null) {
            // 按照默认值显示生成
            $value_array = array(
                'is_register' => "1",
                'register_info' => "plain",
                'name_keyword' => "",
                'pwd_len' => "5",
                'pwd_complexity' => "",
                'terms_of_service' => "",
                'is_requiretel' => 0
            );

            $data = array(
                'instance_id' => $shop_id,
                'website_id' => $this->website_id,
                'key' => 'REGISTERANDVISIT',
                'value' => json_encode($value_array),
                'create_time' => time(),
                'is_use' => "1"
            );

            $config_model = new ConfigModel();
            $res = $config_model->save($data);
            if ($res > 0) {
                $register_and_visit = $this->getConfig($shop_id, 'REGISTERANDVISIT');
            }
        }
        return $register_and_visit;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::setRegisterAndVisit()
     */
    public function setRegisterAndVisit($shop_id, $is_register, $register_info, $name_keyword, $pwd_len, $pwd_complexity, $terms_of_service, $is_requiretel, $is_use)
    {
        $value_array = array(
            'is_register' => $is_register,
            'register_info' => $register_info,
            'name_keyword' => $name_keyword,
            'pwd_len' => $pwd_len,
            'pwd_complexity' => $pwd_complexity,
            'is_requiretel' => $is_requiretel,
            'terms_of_service' => $terms_of_service
        );

        $data = array(
            'value' => json_encode($value_array),
            'modify_time' => time(),
            'is_use' => $is_use
        );

        $config_model = new ConfigModel();
        $res = $config_model->save($data, [
            'key' => 'REGISTERANDVISIT',
            'instance_id' => $shop_id,
            'website_id' => $this->website_id
        ]);
        return $res;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IConfig::databaseList()
     */
    public function getDatabaseList()
    {
        // TODO Auto-generated method stub
        $databaseList = Db::query("SHOW TABLE STATUS");
        return $databaseList;
    }

    /**
     * 获取当前使用的手机模板
     */
    public function getUseWapTemplate($instanceid)
    {
        $config_model = new ConfigModel();
        $res = $config_model->getInfo([
            'key' => 'USE_WAP_TEMPLATE',
            'instance_id' => $instanceid
        ], 'value', '');
        return $res;
    }

    /**
     * 设置要使用手机模板
     *
     * @param 实例id $instanceid
     * @param 模板文件夹名称 $template_name
     */
    public function setUseWapTemplate($instanceid, $folder)
    {
        $res = 0;
        $config_model = new ConfigModel();
        $info = $this->config_module->getInfo([
            'key' => 'USE_WAP_TEMPLATE',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $data['instance_id'] = $instanceid;
            $data['key'] = 'USE_WAP_TEMPLATE';
            $data['value'] = $folder;
            $data['create_time'] = time();
            $data['modify_time'] = time();
            $data['desc'] = '当前使用的手机端模板文件夹';
            $data['is_use'] = 1;
            $res = $config_model->save($data);
        } else {
            $data['instance_id'] = $instanceid;
            $data['value'] = $folder;
            $data['modify_time'] = time();
            $res = $config_model->save($data, [
                'key' => 'USE_WAP_TEMPLATE'
            ]);
        }
        return $res;
    }

    /**
     * 获取当前使用的PC端模板
     *
     * {@inheritdoc}
     *
     * @see \data\api\IConfig::getUsePCTemplate()
     */
    public function getUsePCTemplate($instanceid)
    {
        $user_pc_template = Cache::get("user_pc_template" . $instanceid);
        if (empty($user_pc_template)) {
            $config_model = new ConfigModel();
            $user_pc_template = $config_model->getInfo([
                'key' => 'USE_PC_TEMPLATE',
                'instance_id' => $instanceid
            ], 'value', '');
            Cache::set("user_pc_template" . $instanceid, $user_pc_template);
        }
        return $user_pc_template;
    }

    /**
     * 设置要使用的PC端模板
     *
     * @param 实例id $instanceid
     * @param 模板文件夹名称 $template_name
     */
    public function setUsePCTemplate($instanceid, $folder)
    {
        Cache::set("user_pc_template" . $instanceid, '');
        $res = 0;
        $config_model = new ConfigModel();
        $info = $this->config_module->getInfo([
            'key' => 'USE_PC_TEMPLATE',
            'instance_id' => $instanceid
        ], 'value');

        $data['instance_id'] = $instanceid;
        $data['key'] = 'USE_PC_TEMPLATE';
        $data['value'] = $folder;
        $data['create_time'] = time();
        $data['modify_time'] = time();
        if (empty($info)) {
            $data['desc'] = '当前使用的PC端模板文件夹';
            $data['is_use'] = 1;
            $res = $config_model->save($data);
        } else {
            $res = $config_model->save($data, [
                'key' => 'USE_PC_TEMPLATE'
            ]);
        }
        return $res;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::setPickupPointFreight()
     */
    public function setPickupPointFreight($is_enable, $pickup_freight, $manjian_freight)
    {
        $config_value = array(
            'is_enable' => $is_enable,
            'pickup_freight' => $pickup_freight,
            'manjian_freight' => $manjian_freight
        );
        $config_key = 'PICKUPPOINT_FREIGHT';
        $config_info = $this->getConfig($this->instance_id, $config_key);
        if (empty($config_info)) {
            $res = $this->addConfig($this->instance_id, $config_key, json_encode($config_value), '自提点运费菜单配置', 1);
        } else {
            $res = $this->updateConfig($this->instance_id, $config_key, json_encode($config_value), '自提点运费菜单配置', 1);
        }
        return $res;
    }

    /**
     * 开启关闭自定义模板
     *
     * @param 店铺id $shop_id
     * @param 1：开启，0：禁用 $is_enable
     */
    public function setIsEnableCustomTemplate($shop_id, $is_enable)
    {
        $res = 0;
        $config_model = new ConfigModel();
        $info = $this->config_module->getInfo([
            'key' => 'WAP_CUSTOM_TEMPLATE_IS_ENABLE',
            'instance_id' => $shop_id,
            'website_id' => $this->website_id
        ], 'value');
        if (empty($info)) {
            $data['website_id'] = $this->website_id;
            $data['instance_id'] = $shop_id;
            $data['key'] = 'WAP_CUSTOM_TEMPLATE_IS_ENABLE';
            $data['value'] = $is_enable;
            $data['is_use'] = 1;
            $data['create_time'] = time();
            $res = $config_model->save($data);
        } else {
            $data['instance_id'] = $shop_id;
            $data['website_id'] = $this->website_id;
            $data['value'] = $is_enable;
            $data['modify_time'] = time();
            $res = $config_model->save($data, [
                'key' => 'WAP_CUSTOM_TEMPLATE_IS_ENABLE',
                'instance_id' => $shop_id
            ]);
        }
        return $res;
    }

    /**
     * 获取自定义模板是否启用，0 不启用 1 启用
     *
     * @param unknown $shop_id
     * @return number|unknown
     */
    public function getIsEnableCustomTemplate($shop_id)
    {
        $is_enable = 0;
        $config_model = new ConfigModel();
        $value = $config_model->getInfo([
            'key' => 'WAP_CUSTOM_TEMPLATE_IS_ENABLE',
            'instance_id' => $shop_id,
            'website_id' => $this->website_id
        ], 'value');
        if (!empty($value)) {
            $is_enable = $value["value"];
        }
        return $is_enable;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IConfig::getUploadType()
     */
    public function getUploadType($website_id=0)
    {
        // TODO Auto-generated method stub
        $upload_type = $this->config_module->getInfo([
            "key" => "UPLOAD_TYPE",
            "instance_id" => 0,
            "website_id" => $website_id,
        ], "*");
        if (empty($upload_type)) {
            $sqlData = array(
                'instance_id' => 0,
                'website_id' => $website_id,
                'key' => "UPLOAD_TYPE",
                'value' => 1,
                'desc' => "上传方式 1 本地  2 阿里云",
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $this->config_module->save($sqlData);
            return 1;
        } else {
            return $upload_type['value'];
        }
    }
    /*
     * (non-PHPdoc)
     */
    public function getAliOssConfig($website_id=0)
    {
        // TODO Auto-generated method stub
        $alioss_info = $this->config_module->getInfo([
            "key" => "ALIOSS_CONFIG",
            "instance_id" => 0,
            "website_id" => $website_id,
        ], "*");
        if (empty($alioss_info)) {
            $data = array(
                "Accesskey" => "",
                "Secretkey" => "",
                "Bucket" => "",
                "AliossUrl" => "",
                "endPoint" => ""
            );
            $sqlData = array(
                'instance_id' => 0,
                'website_id' => 0,
                'key' => "ALIOSS_CONFIG",
                'value' => json_encode($data),
                'desc' => "阿里云云存储参数配置",
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $this->config_module->save($sqlData);
            if (!$res > 0) {
                return null;
            } else {
                $alioss_info = $this->config_module->getInfo([
                    "key" => "ALIOSS_CONFIG",
                    "instance_id" => 0,
                    "website_id" => $website_id
                ], "*");
            }
        }
        $value = json_decode($alioss_info["value"], true);
        return $value;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IConfig::setUploadType()
     */
    public function setUploadType($value,$website_id=0)
    {
        $upload_info = $this->config_module->getInfo([
            "key" => "UPLOAD_TYPE",
            "instance_id" => 0,
            "website_id" => $website_id
        ], "*");
        if (!empty($upload_info)) {
            $data = array(
                "value" => $value
            );
            $res = $this->config_module->save($data, [
                "instance_id" => 0,
                "website_id" => $website_id,
                "key" => "UPLOAD_TYPE"
            ]);
        } else {
            $data = array(
                'instance_id' => 0,
                'website_id' => $website_id,
                'key' => "UPLOAD_TYPE",
                'value' => json_encode($value),
                'desc' => "上传方式 1 本地  2 阿里云",
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $this->config_module->save($data);
        }
        // TODO Auto-generated method stub
        return $res;
    }

    public function setAliossConfig($value,$website_id=0)
    {
        $alioss_info = $this->config_module->getInfo([
            "key" => "ALIOSS_CONFIG",
            "instance_id" => 0,
            "website_id" => $website_id
        ], "*");
        if (empty($alioss_info)) {
            $data = array(
                "Accesskey" => "",
                "Secretkey" => "",
                "Bucket" => "",
                "AliossUrl" => "",
                "endPoint" => "",
            );
            $sqlData = array(
                'instance_id' => 0,
                'website_id' => $website_id,
                'key' => "ALIOSS_CONFIG",
                'value' => json_encode($data),
                'desc' => "阿里云云存储参数配置",
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $this->config_module->save($sqlData);
        } else {
            $data = array(
                "value" => $value
            );
            $res = $this->config_module->save($data, [
                "key" => "ALIOSS_CONFIG",
                "instance_id" => 0,
                "website_id" => $website_id
            ]);
        }
        return $res;
    }







    /**
     * (non-PHPdoc)
     * @see \data\api\IConfig::getOperateConfig()
     */
    public function getOperateConfig()
    {

        $operate_config_info = $this->getConfig($this->instance_id, 'OPERATE_CONFIG');

        if (empty($operate_config_info)) {
            $operate_config = array(
                'is_discount_open' => 0,
                'is_discount_toExamine' => 0,
                'is_mansong_open' => 0,
                'is_mansong_toExamine' => 0,
                'is_groups_open' => 0,
                'is_groups_toExamine' => 0,
                'is_pickuPpoint_open' => 0
            );
            $this->addConfig($this->instance_id, 'OPERATE_CONFIG', json_encode($operate_config), '运营配置信息', 1);
        } else {
            $operate_config = json_decode($operate_config_info['value']);
        }
        return $operate_config;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IConfig::updateOperateConfig()
     */
    public function updateOperateConfig($config_value)
    {
        $res = $this->updateConfig($this->instance_id, 'OPERATE_CONFIG', $config_value, '运营配置信息', 1);
        return $res;
    }

    /**
     * 设置商品设置
     */
    public function setGoodsConfig($shop_id, $value)
    {
        $data = [
            "is_use" => $value,
        ];
        $res = $this->config_module->save($data, [
            "key" => "GOODS_CONFIG",
            "instance_id" => $shop_id
        ]);

        return $res;
    }

    /**
     * 获取商品设置信息
     */
    public function getGoodsConfig()
    {
        $shop_id = $this->instance_id;
        $website_id = $this->website_id;
        $key = 'GOODS_CONFIG';
        $goods_config_info = $this->config_module->getInfo([
            "key" => $key,
            "instance_id" => $shop_id,
            "website_id" => $website_id
        ], "*");
        if (empty($goods_config_info)) {
            $data = [];
            $this->addConfig($shop_id, $key, json_encode($data), "商品审核设置", 0);
        } else {
            return $goods_config_info;
        }
    }


    /**
     * 获取手机端自定义模板
     *
     * {@inheritdoc}
     *
     * @see \data\api\IConfig::getCustomTeplateList()
     */
    function getCustomTemplateInfo($condition)
    {
        $custom_template_model = new CustomTemplateModel();
        $res = $custom_template_model->getInfo($condition, '*');
        return $res;
    }

    /**
     * 新增模板页面
     * @param array $data
     *
     * @return int $res
     */
    public function createCustomTemplate(array $data)
    {
        $custom_template_model = new CustomTemplateModel();
        $res = $custom_template_model->save($data);
        return $res;
    }

    /**
     * 添加手机端自定义模板
     *
     * {@inheritdoc}
     *
     * @see \data\api\IConfig::addCustomTemplate()
     */
    public function saveCustomTemplate(array $data, $id, $type = '')
    {
        $custom_template_model = new CustomTemplateModel();
        if (!$id) {
            $return = $custom_template_model->save($data);
        } else {
            $return = $custom_template_model->save($data, ['id' => $id]);
            if($type == 9){
                $app_custom_template_model = new AppCustomTemplate();
                $default_conf0['type'] = 9;
                $default_conf0['is_default'] = 1;
                $default_conf0['in_use'] = 1;
                $default_conf0['website_id'] = $this->website_id;
                //判断是否存在type为9的装修数据
                $app_custom_info = $app_custom_template_model->getInfo($default_conf0);
                if(!$app_custom_info){
                    //取出移动端默认的数据装修名字
                    $default_conf['type'] = 9;
                    $default_conf['is_system_default'] = 1;
                    $template_name = $custom_template_model->getInfo($default_conf, 'template_name')['template_name'];
                    $appcustom['template_name'] = $template_name;
                    $appcustom['template_data'] = $data['template_data'];
                    $appcustom['create_time'] = time();
                    $appcustom['modify_time'] = time();
                    $appcustom['website_id'] = $this->website_id;
                    $appcustom['type'] = 9;
                    $appcustom['is_default'] = 1;
                    $appcustom['in_use'] = 1;
                    $app_custom_template_model->save($appcustom);
                }
            }
        }
        return $return;
    }

    /**
     * 获取手机端自定义模板列表
     *
     * {@inheritdoc}
     *
     * @see \data\api\IConfig::addCustomTemplate()
     */
    public function getCustomTemplateList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $custom_template_model = new CustomTemplateModel();
        return $custom_template_model->pageQuery($page_index, $page_size, $condition, $order, $field);
    }

    /**
     * 获取模板数目
     *
     * @param $condition
     */
    public function getCustomTemplateCount($condition)
    {
        $custom_template_model = new CustomTemplateModel();
        return $custom_template_model->getCount($condition);
    }

    /**
     * 初始化店铺(平台)装修模板
     *
     * @param $website_id
     * @param $shop_id
     */
    public function initCustomTemplate($website_id, $shop_id)
    {
        $custom_template_model = new CustomTemplateModel();
        $condition['is_system_default'] = 1;
        if ($shop_id > 0) {
            // 店铺的初始化可装修的页面只有店铺首页和商品详情页
            $condition['type'] = ['IN', [2, 3]];
        }
        $system_default_list = $custom_template_model->getQuery($condition, '*', '');
        $data = [];
        foreach ($system_default_list as $k => $v) {
            if ($v['is_default'] == 1){
                $data[$k]['template_name'] = $v['template_name'];
                $data[$k]['template_data'] = $v['template_data'];
                $data[$k]['website_id'] = $website_id;
                $data[$k]['shop_id'] = $shop_id;
                $data[$k]['create_time'] = time();
                $data[$k]['modify_time'] = time();
                $data[$k]['type'] = $v['type'];
                $data[$k]['is_default'] = 1;
                $data[$k]['in_use'] = 1;
            }
        }
        if (!empty($data)) {
            $custom_template_model->saveAll($data);
        }
    }

    /**
     * 设置手机端默认模板
     * @param int|string $id
     * @param int|string $type
     * @param int|string $shop_id
     * @param int|string $website_id
     *
     * @return int result
     *
     * @see \data\api\IConfig::useCustomTemplate()
     */
    public function useCustomTemplate($id, $type, $shop_id, $website_id)
    {
        $custom_config = new CustomTemplateModel();
        $custom_config->startTrans();
        try {
            $custom_config->save(['in_use' => 0], ['type' => $type, 'in_use' => 1, 'shop_id' => $shop_id, 'website_id' => $website_id, 'id' => ['NEQ', $id]]);
            $custom_config->save(['in_use' => 1], ['id' => $id]);
            $custom_config->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            //var_dump($e->getMessage());
            $custom_config->rollback();
            return UPDATA_FAIL;
        }
    }

    /**
     * 设置商品详情模板
     * 2018年5月23日 11:16:34
     *
     * {@inheritdoc}
     *
     * @see \data\api\IConfig::setDefaultCustomTemplate()
     */
    public function setGoodsdetailTemplate($id, $shop_id)
    {
        if (!$id) {
            return UPDATA_FAIL;
        }
        $custom_template_model = new CustomTemplateModel();
        $data['modify_time'] = time();
        $data['is_enable'] = 0;
        $condition['shop_id'] = $shop_id;
        $condition['range'] = 2;
        $condition['website_id'] = $this->website_id;
        $result = $custom_template_model->save($data, $condition);
        if (!$result) {
            return UPDATA_FAIL;
        }
        $condition['id'] = $id;
        $res = $custom_template_model->save(['modify_time' => time(), 'is_enable' => 1], $condition);
        return $res;
    }

    /**
     * 设置会员中心模板
     * 2018年5月23日 11:16:34
     *
     * {@inheritdoc}
     *
     * @see \data\api\IConfig::setDefaultCustomTemplate()
     */
    public function customtemplate_membercenter($id, $shop_id)
    {
        if (!$id) {
            return UPDATA_FAIL;
        }
        $custom_template_model = new CustomTemplateModel();
        $data['modify_time'] = time();
        $data['is_enable'] = 0;
        $condition['shop_id'] = $shop_id;
        $condition['range'] = 3;
        $condition['website_id'] = $this->website_id;
        $result = $custom_template_model->save($data, $condition);
        if (!$result) {
            return UPDATA_FAIL;
        }
        $condition['id'] = $id;
        $res = $custom_template_model->save(['modify_time' => time(), 'is_enable' => 1], $condition);
        return $res;
    }

    /**
     * 设置分销中心模版
     * 2018年5月23日 11:16:34
     *
     * {@inheritdoc}
     *
     * @see \data\api\IConfig::setDefaultCustomTemplate()
     */
    public function customtemplate_distribution($id, $shop_id)
    {
        if (!$id) {
            return UPDATA_FAIL;
        }
        $custom_template_model = new CustomTemplateModel();
        $data['modify_time'] = time();
        $data['is_enable'] = 0;
        $condition['shop_id'] = $shop_id;
        $condition['range'] = 4;
        $condition['website_id'] = $this->website_id;
        $result = $custom_template_model->save($data, $condition);
        if (!$result) {
            return UPDATA_FAIL;
        }
        $condition['id'] = $id;
        $res = $custom_template_model->save(['modify_time' => time(), 'is_enable' => 1], $condition);
        return $res;
    }

    /**
     * 删除自定义模板
     * 2017年7月31日 11:16:34
     *
     * {@inheritdoc}
     *
     * @see \data\api\IConfig::deleteCustomTemplateById()
     */
    public function deleteCustomTemplateById($condition)
    {

        $custom_template_model = new CustomTemplateModel();
        return $custom_template_model->destroy($condition);
    }
    /**
     * 通过website_id获取第三方配置信息
     * @param string $website_id
     * @return mixed
     */
    public function getWechatOpenByWebsiteId($website_id = '')
    {
        $res = $this->config_module->getInfo(['website_id' => $website_id, 'is_use' => 1,  'key'=>'WECHATOPEN'])['value'];
        if($res){
            $res = json_decode($res,true);
        }
        return $res;
    }
    /*
     * 装修设置弹窗广告
     */
    public function setPopAdvConfig($value,$website_id=0)
    {
        $popadvConfig = $this->config_module->getInfo([
            "key" => "POPADV_CONFIG",
            "instance_id" => 0,
            "website_id" => $website_id
        ], "*");
        if (empty($popadvConfig)) {
            $data = array(
                "advshow" => 0,
                "advimg" => "",
                "advlink" => "",
                "advrule" => 1
            );
            $sqlData = array(
                'instance_id' => 0,
                'website_id' => $website_id,
                'key' => "POPADV_CONFIG",
                'value' => json_encode($data),
                'desc' => "首页弹窗广告配置",
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $this->config_module->save($sqlData);
        } else {
            $data = array(
                "value" => $value
            );
            $res = $this->config_module->save($data, [
                "key" => "POPADV_CONFIG",
                "instance_id" => 0,
                "website_id" => $website_id
            ]);
        }
        return $res;
    }
    /*
     * 获取弹窗广告设置
     */
    public function getPopAdvConfig($website_id=0)
    {
        // TODO Auto-generated method stub
        $popadvConfig = $this->config_module->getInfo([
            "key" => "POPADV_CONFIG",
            "instance_id" => 0,
            "website_id" => $website_id,
        ], "*");
        if (empty($popadvConfig)) {
            $data = array(
                "advshow" => 0,
                "advimg" => "",
                "advlink" => "",
                "advrule" => 1
            );
            $sqlData = array(
                'instance_id' => 0,
                'website_id' => $website_id,
                'key' => "POPADV_CONFIG",
                'value' => json_encode($data),
                'desc' => "首页弹窗广告配置",
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $this->config_module->save($sqlData);
            if (!$res) {
                return null;
            } else {
                $popadvConfig = $this->config_module->getInfo([
                    "key" => "POPADV_CONFIG",
                    "instance_id" => 0,
                    "website_id" => $website_id
                ], "*");
            }
        }
        return $popadvConfig["value"];
    }

    public function setDefaultAnti()
    {
        $notice_type_model = new NoticeTemplateTypeModel();
        //$sort = $notice_type_model->max('sort');
        $condition[0] = array('template_name' => '操作员忘记密码','template_code' => 'anti_forgot_password','template_type' => 'sms','notify_type' => 'operator','sort' => 0,'sample' => '您的验证码是${code}，请保管好验证码并在15分钟内进行验证。','is_platform' => 1,'website_id' => 0,'sms_type' => 1);
        $notice_type_model->saveAll($condition);
        $item_model = new NoticeTemplateItemModel();
        $res = $item_model->where(['item_name' => '验证码'])->find();
        $type_ids = $res['type_ids'].',anti_forgot_password';
        $item_model->where(['item_name' => '验证码'])->update(['type_ids' => $type_ids]);
    }
    public function setGpayConfigMir($instanceid, $appid, $partner_code,$credential_code,$currency,$is_use)
    {
        $data = array(
            'appid' => $appid,
            'partner_code' => $partner_code,
            'credential_code' => $credential_code,
			'currency' => $currency,
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'GPPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'GPPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'GPPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'GPPAY'
            ]);
        }
        return $res;
    }
	public function getGpayConfigMir($website_id)
    {
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => $website_id,
            'key' => 'GPPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appid' => '',
                    'partner_code' => '',
                    'credential_code' => '',
					'currency' => 'JPY',
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
    /*
     * 获取图标版权设置
     */
    public function getLogoConfig()
    {

        $logo_config_info = $this->getConfigbyWebsiteId(0,0, 'LOGO_CONFIG');
        if (empty($logo_config_info)) {
            $logo_config = array(
                'platform_logo' => '',
                'opera_logo' => '',
                'forgot_logo' => '',
                'admin_logo' => '',
                'login_mall_name' => '',
                'title_word' => '',
                'login_copyright' => '',
                'opera_copyright' => '',
                'platform_word' => ''
            );
            $this->addLogoConfig($logo_config);
        } else {
            $logo_config = json_decode($logo_config_info['value'],true);
            
        }
        return $logo_config;
    }
    
    /*
     * 添加商标设置
     */
    public function addLogoConfig($value = []){
        $logo_config_info = $this->getConfigbyWebsiteId(0,0, 'LOGO_CONFIG');
        $config = new ConfigModel();
        if (is_array($value)) {
            $value = json_encode($value);
        }
       
        if(empty($logo_config_info)){
             $data = array(
                'instance_id' => 0,
                'website_id' => 0,
                'key' => 'LOGO_CONFIG',
                'value' => $value,
                'desc' => '图标版权配置信息',
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config->save($data);
        }else{
            $data = array(
                'value' => $value,
                'modify_time' => time()
            );
            $res = $config->save($data,['website_id' => 0, 'instance_id' => 0, 'key' => 'LOGO_CONFIG']);
        }
        return $res;
    }
    	public function setGpayConfig($instanceid, $appid, $partner_code,$credential_code,$currency,$is_use)
    {
        $data = array(
            'appid' => $appid,
            'partner_code' => $partner_code,
            'credential_code' => $credential_code,
			'currency' => $currency,
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'GLOPAY',
            'instance_id' => $instanceid,
            'website_id' => $this->website_id,
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'GLOPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'GLOPAY',
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'website_id' => $this->website_id,
                'key' => 'GLOPAY'
            ]);
        }
        return $res;
    }
	public function getGpayConfig($website_id)
    {
        $info = $this->config_module->getInfo([
            'instance_id' => 0,
            'website_id' => $website_id,
            'key' => 'GLOPAY'
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appid' => '',
                    'partner_code' => '',
                    'credential_code' => '',
					'currency' => 'CNY',
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
        // TODO Auto-generated method stub
    }
}