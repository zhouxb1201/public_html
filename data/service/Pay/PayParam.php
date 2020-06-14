<?php
/**
 * PayParam.php
 *
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace data\service\Pay;

use data\service\BaseService;
use data\service\Config;
use think\Log;

/**
 * 功能说明：第三方支付接口
 */
class PayParam extends BaseService
{
    // 实例ID
    protected $instance;

    /**
     * *********************************************微信公众平台参数******************************************
     */
    protected $appid;
 // 用于微信公众号appid
    protected $appsecret;
 // 用于微信支公众号appkey
    /**
     * ********************************************微信公众平台结束******************************************
     */
    
    /**
     * *********************************************微信支付参数******************************************
     */
    protected $pay_appid;
 // 用于微信支付的公众号appid
    protected $pay_appsecret;
 // 用于微信支付的公众号appkey（在jsapi支付中使用获取openid，扫码支付不使用）
    protected $pay_mchid;
 // 用于微信支付的商户号
    protected $pay_mchkey;
 // 用于微信支付的商户秘钥
    /**
     * *********************************************微信支付参数 - 小程序 ******************************************
     */
    protected $mp_pay_appid;
    // 用于微信支付的公众号appid
    protected $mp_pay_appsecret;
    // 用于微信支付的公众号appkey（在jsapi支付中使用获取openid，扫码支付不使用）
    protected $mp_pay_mchid;
    // 用于微信支付的商户号
    protected $mp_pay_mchkey;
    // 用于微信支付的商户秘钥
    /**
     * ********************************************微信支付参数结束****************************************
     */
    
    /**
     * ********************************************支付宝支付参数******************************************
     */
    protected $ali_partnerid;
 // 支付宝商户id 以2088开始的纯数字
    protected $ali_seller;
 // 支付宝商户账号(邮箱)
    protected $ali_key;
 // 支付宝商户秘钥
    protected $apiclient_cert;
 // 数字证书密钥
    protected $apiclient_key;
 // 数字证书key
    protected $ali_public_key;
 // 支付宝公钥
    protected $ali_appid;
 // 应用id
    protected $ali_private_key;
 // 商户私钥
    
    /**
     * ********************************************支付宝支付参数结束****************************************
     */
    
    // 构造函数如果是多用户系统需要传入对应的实例ID
    function __construct($instance = 0)
    {
        parent::__construct();
        $this->getParam($instance);
    }
    // 获取支付所需参数
    protected function getParam($instance)
    {
        $config = new Config();
        $this->appid = '';
        $this->appsecret = '';
        // 获取微信支付参数(统一支付到平台)
        $wchat_config = $config->getWpayConfig($instance);
        $this->pay_appid = $wchat_config['value']['appid'];
        $this->pay_appsecret = $wchat_config['value']['appkey'];
        $this->pay_mchid = $wchat_config['value']['mch_id'];
        $this->pay_mchkey = $wchat_config['value']['mch_key'];
        //小程序
        $mp_wchat_config = $config->getWpayConfigMir($instance);
        $this->mp_pay_appid = $mp_wchat_config['value']['appid'];
        $this->mp_pay_mchid = $mp_wchat_config['value']['mchid'];//注意这里是mchid不是mch_id
        $this->mp_pay_mchkey = $mp_wchat_config['value']['mch_key'];
        // 获取支付宝支付参数(统一支付到平台账户)
        $alipay_config = $config->getAlipayConfig($instance);
        $this->ali_partnerid = $alipay_config['value']['ali_partnerid'];
        $this->ali_seller = $alipay_config['value']['ali_seller'];
        $this->ali_key = $alipay_config['value']['ali_key'];
        $this->ali_public_key = $alipay_config['value']['ali_public_key'];
        $this->ali_private_key= $alipay_config['value']['ali_private_key'];
        $this->ali_appid = $alipay_config['value']['appid'];
    }

    /**
     * *************************************************获取微信公众号参数************************************
     */
    public function getAppid()
    {
        return $this->appid;
    }

    public function getAppsecret()
    {
        return $this->appsecret;
    }

    /**
     * ***********************************************获取微信公众号参数结束************************************
     */
    
    /**
     * *************************************************获取微信支付参数************************************
     */
    public function getPayAppid()
    {
        return $this->pay_appid;
    }

    public function getPayAppSecret()
    {
        return $this->pay_appsecret;
    }

    public function getPayMchid()
    {
        return $this->pay_mchid;
    }

    public function getPayMchkey()
    {
        return $this->pay_mchkey;
    }

    public function getApiClientCert()
    {
        return $this->apiclient_cert;
    }

    public function getApiClientKey()
    {
        return $this->apiclient_key;
    }

    /*************************************************获取微信支付参数 - 小程序 ************************************/
    public function getMpPayAppid()
    {
        return $this->mp_pay_appid;
    }
    public function getMpPayMchid()
    {
        return $this->mp_pay_mchid;
    }
    public function getMpPayMchkey()
    {
        return $this->mp_pay_mchkey;
    }
    /**
     * ***********************************************获取微信支付参数结束************************************
     */
    
    /**
     * ***********************************************获取支付宝支付参数开始***********************************
     */
    public function getAliPartnerid()
    {
        return $this->ali_partnerid;
    }

    public function getAliSeller()
    {
        return $this->ali_seller;
    }

    public function getAliKey()
    {
        return $this->ali_key;
    }
/**
 * ***********************************************获取支付宝支付参数结束***********************************
 */
}
