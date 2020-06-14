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
namespace data\service\Upload;
use data\service\BaseService;
use data\service\Config;
/**
 * 功能说明：第三方支付接口
 */
class UploadParam extends BaseService
{
    //实例ID
    protected $instance;
    /***********************************************七牛云存储参数*******************************************/
    protected $Accesskey;          //用于签名的公钥
    protected $Secretkey;     //用于签名的私钥
    protected $Bucket;          //存储空间
    protected $AliOssUrl;     //七牛用户自定义访问域名
    
    //构造函数如果是多用户系统需要传入对应的实例ID
    function __construct(){
        $this->getParam();
    }
    //获取支付所需参数
    protected function getParam(){
        $config = new Config();
        //获取微信支付参数(统一支付到平台)
        $alioss_config = $config->getAliOssConfig(); 
        $this->Accesskey = $alioss_config["Accesskey"];          //用于签名的公钥
        $this->Secretkey = $alioss_config["Secretkey"];     //用于签名的私钥
        $this->Bucket = $alioss_config["Bucket"];          //存储空间
        $this->AliOssUrl = $alioss_config["AliossUrl"];     //七牛用户自定义访问域名
      
    }
    
    /***************************************************获取七牛云存储参数*************************************/
    public function getAccesskey(){
        return $this->Accesskey;
    }
    public function getSecretkey(){
        return $this->Secretkey;
    }
    public function getBucket(){
        return $this->Bucket;
    }
    public function getAliOssUrl(){
        return $this->AliOssUrl;
    }
    /*************************************************获取七牛云存储参数************************************/
    
  
}
