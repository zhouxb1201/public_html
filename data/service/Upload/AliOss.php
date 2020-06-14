<?php
/**
 * AliPay.php
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
require_once 'data/extend/aliyun_oss/autoload.php';

use data\service\Upload\UploadParam;
use think\Request;
use OSS\OssClient;
use OSS\Core\OssException;
use OSS\Http\RequestCore;
use OSS\Http\ResponseCore;
/**
 * 功能说明：七牛云存储上传
 */

class AliOss extends UploadParam{
    function __construct(){
        parent::__construct();
    }

    public function index(){
        //防止默认目录错误
    }
    /**
     * 阿里云基本设置
     * @return unknown
     */
    public function getAliOssConfig(){
         //用于签名的公钥
        $alioss_config['Accesskey']  = $this->Accesskey;
        //用于签名的私钥
        $alioss_config['Secretkey']  = $this->Secretkey;
        //存储空间名称
        $alioss_config['Bucket']     = $this->Bucket;
        //七牛用户自定义访问域名
        $alioss_config['AliOssUrl']   = $this->AliOssUrl;
        $alioss_config['endPoint']   = $this->endPoint;
        return $alioss_config;
    }
   /**
    * 上传图片
    * @param unknown $filePath  上传图片路径
    * @param unknown $key 上传到阿里后保存的文件名
    */
    public function setAliOssUplaod($filePath, $key){
            $config = $this->getAliOssConfig();
            //Access Key 和 Secret Key
            $accessKey = $config["Accesskey"];
            $secretKey = $config["Secretkey"];
            $bucket = $config["Bucket"];
            $url = 'http://oss-cn-hangzhou.aliyuncs.com';
            if($config["endPoint"]){
                $url = $config["endPoint"];
            }
            
            
            //构建鉴权对象
            try {
		$ossClient = new OssClient($accessKey, $secretKey, $url);
            } catch(OssException $e) {
                recordErrorLog($e);
                return $e->getMessage();
            }
            try{
                list($ret, $err) = $ossClient->uploadFile($bucket, $key, $filePath);
            } catch(OssException $e) {
                recordErrorLog($e);
                printf(__FUNCTION__ . ": FAILED\n");
                printf($e->getMessage() . "\n");
                return;
            }
            if ($err !== null) {
                return ["code"=>false,"path"=>"","domain"=>"", "bucket"=>""];
            } else {
                //返回图片的完整URL
                return ["code"=>true,"path"=>$this->AliOssUrl."/". $key,"domain"=>$this->AliOssUrl, "bucket"=>$this->Bucket];
            }
    }
   /**
    * 删除多个图片
    * @param unknown $filename_arr  图片数组
    */
    public function deleteAliOss($filename){
            $config = $this->getAliOssConfig();
            //Access Key 和 Secret Key
            $accessKey = $config["Accesskey"];
            $secretKey = $config["Secretkey"];
            $bucket = $config["Bucket"];
            $url = 'http://oss-cn-hangzhou.aliyuncs.com';
            if($config["endPoint"]){
                $url = $config["endPoint"];
            }
            if(strstr($filename, 'http')){
                if($config['AliOssUrl']){
                    $filename = str_replace($config['AliOssUrl'].'/','',$filename);
                }else{
                    $filename = str_replace($url.'/','',$filename);
                }
            }
            //构建鉴权对象
            try {
		$ossClient = new OssClient($accessKey, $secretKey, $url);
                list($ret, $err) = $ossClient->deleteObject($bucket, $filename);
            } catch(OssException $e) {
                recordErrorLog($e);
                printf(__FUNCTION__ . ": FAILED\n");
                printf($e->getMessage() . "\n");
                return ["code"=>0,"message"=>$e->getMessage()];
            }
            if ($err !== null) {
                return ["code"=>0,"message"=>"删除失败"];
            } else {
                //返回图片的完整URL
                return ["code"=>1,"message"=>"删除成功"];
            }
    }
    public function attachment_alioss_buctkets($key, $secret) {
        $config = $this->getAliOssConfig();
	$url = 'http://oss-cn-hangzhou.aliyuncs.com';
        if($config["endPoint"]){
            $url = $config["endPoint"];
        }
	try {
		$ossClient = new OssClient($key, $secret, $url);
	} catch(OssException $e) {
            recordErrorLog($e);
		return ACCESS_ERROR;
	}
	try{
            $bucketlistinfo = $ossClient->listBuckets();
	} catch(OssException $e) {
            recordErrorLog($e);
            return ACCESS_ERROR;
	}
	$bucketlistinfo = $bucketlistinfo->getBucketList();
	$bucketlist = array();
	foreach ($bucketlistinfo as &$bucket) {
		$bucketlist[$bucket->getName()] = array('name' => $bucket->getName(), 'location' => $bucket->getLocation());
	}
	return $bucketlist;
    }   
}