<?php

namespace app\platform\controller;

\think\Loader::addNamespace('data', 'data/');

use data\service\Album as Album;
use data\service\Config as WebConfig;
use data\service\WebSite;
use think\Controller;
/**
 * 图片上传控制器     
 */
use data\service\Upload\AliOss;
use think\Config;
use \think\Session as Session;

class Upload extends Controller {

    private $return = array();
    // 文件路径
    private $file_path = "";
    // 重新设置的文件路径
    private $reset_file_path = "";
    // 文件名称
    private $file_name = "";
    // 文件大小
    private $file_size = 0;
    // 文件类型
    private $file_type = "";
    private $upload_type = 1;
    private $instance_id = "";
    //缩略类型
    private $thumb_type = 1;
    //是否开启水印设置
    private $watermark = 1;
    //水印透明度
    private $watermark_font = "";
    //水印图片位置
    private $watermark_position = "";
    //水印图片路径
    private $watermark_logo = "";
    // 允许的文件类型
    private $allow_file_ext;

    public function __construct() {
        $instanceid = $this->instance_id;
        $config = new WebConfig();
        $this->upload_type = $config->getUploadType();
        $this->allow_file_ext = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'image/gif', 'image/png', 'image/jpeg', 'image/jpp'],
            'pem' => ['pem', 'application/octet-stream','application/x-x509-ca-cert'],
            'txt' => ['txt']
        ];
    }

    /**
     * 功能说明：文件(图片)上传(存入相册)
     */
    public function uploadFile() {
        $data = array();
        $year = date('Y',time());
      	$month = date('m',time());
      	$day = date('d',time());
      	$hour = date('H',time());
        $this->file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/' . Session::get(request()->module() . 'instance_id') . '/'.$year.'/'.$month.'/'.$day.'/'.$hour.'/';
        if ($this->file_path == "") {
            $this->return['message'] = "文件路径不能为空";
            return $this->ajaxFileReturn();
        }
        $file_type = request()->post('file_type',0);
        // 重新设置文件路径
        $this->resetFilePath();
        // 检测文件夹是否存在，不存在则创建文件夹
        if (!file_exists($this->reset_file_path)) {
            $mode = intval('0777', 8);
            mkdir($this->reset_file_path, $mode, true);
        }

        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型

        if ($this->file_size == 0) {
            $this->return['message'] = "文件大小为0MB";
            return $this->ajaxFileReturn();
        }
        $validationType = 1;
        if ($file_type) {
            $validationType = 4; //视频文件验证不同
        }
        // 验证文件
        if (!$this->validationFile($validationType)) {
            return $this->ajaxFileReturn();
        }
        $guid = time().rand(100, 999);
        $file_name_explode = explode(".", $this->file_name); // 图片名称
        $suffix = count($file_name_explode) - 1;
        $ext = "." . $file_name_explode[$suffix]; // 获取后缀名
        $newfile = $guid . $ext; // 重新命名文件
        // 特殊 判断如果是商品图
        $ok = $this->moveUploadFile($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $newfile);
        if ($ok["code"]) {
            // 文件上传成功执行下边的操作
            if (!$file_type) {
                @unlink($_FILES['file_upload']);
                $image_size = getimagesize($ok["path"]); // 获取图片尺寸
                $album = new Album();
                if ($image_size) {
                    $width = $image_size[0];
                    $height = $image_size[1];
                    $name = $file_name_explode[0];
                    $type = request()->post("type", 0);
                    $pic_name = request()->post("pic_name", $guid);
                    $album_id = $_REQUEST['album_id'];
                    if ($album_id == '' || $album_id == 'null') {

                        $album_id = $album->getDefaultAlbumDetail()['album_id'];
                    }
                    $pic_tag = request()->post("pic_tag", $name);
                    $pic_id = intval($_REQUEST['pic_id']) > 0 ? intval($_REQUEST['pic_id']) : '';
                    $upload_flag = request()->post("upload_flag", "");
                    if ($this->watermark == 2) {
                        $res = $this->uploadThumbFile1($this->reset_file_path . $newfile);
                    } else {
                        $res['path'] = $this->reset_file_path . $newfile;
                    }
                    if ($this->file_type == 'image/x-icon') {
                        $retval = $album->addPicture($pic_name, $pic_tag, $album_id, $ok["path"], $width . "," . $height, $width . "," . $height, $ok["path"], $width . "," . $height, $width . "," . $height, $ok["path"], $width . "," . $height, $width . "," . $height, $ok["path"], $width . "," . $height, $width . "," . $height, $ok["path"], $width . "," . $height, $width . "," . $height, $this->instance_id, $this->upload_type, $ok["domain"], $ok["bucket"]);
                        $data['file_id'] = $retval;
                        $data['file_name'] = $ok["path"];
                        $data['origin_file_name'] = $this->file_name;
                        $data['file_path'] = $this->reset_file_path . $newfile;
                        $data['state'] = '1';
                        $data['message'] = "上传成功";
                        return $data;
                    }

                    // 上传到相册管理，生成四张大小不一的图
                    $retval = $this->photoCreate($this->reset_file_path, $res['path'], "." . $file_name_explode[$suffix], $type, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id, $ok["domain"], $ok["bucket"], $ok["path"]);
                    if ($retval > 0) {
                        $data['origin_file_name'] = $this->file_name;
                        $data['file_name'] = $this->reset_file_path . $newfile;
                        $data['state'] = '1';
                        $data['message'] = "上传成功";
                        $data['file_id'] = $retval;
                    } else {
                        $data['state'] = '0';
                        $data['message'] = "图片上传失败";
                    }
                } else {

                    // 强制将文件后缀改掉，文件流不同会导致上传文件失败
                    $this->return['message'] = "请检查您的上传参数配置或上传的文件是否有误";
                }
            } else {
                @unlink($_FILES['file_upload']);
                $album = new Album();
                $name = $file_name_explode[0];
                $pic_name = request()->post("pic_name", $guid);
                $album_id = $_REQUEST['album_id'];
                if ($album_id == '' || $album_id == 'null') {
                    $album_id = $album->getDefaultAlbumDetail()['album_id'];
                }
                $pic_tag = request()->post("pic_tag", $name);
                $pic_id = intval($_REQUEST['pic_id']) > 0 ? intval($_REQUEST['pic_id']) : '';
                $upload_flag = request()->post("upload_flag", "");
                $res['path'] = $this->reset_file_path . $newfile;

                // 上传到相册管理，生成四张大小不一的图
                if($pic_id){
                    $retval = $album->ModifyAlbumPicture($pic_id, $ok["path"], '', '', $ok["path"], '', '', $ok["path"], '', '', $ok["path"], '', '', $ok["path"], '', '', $this->instance_id, $this->upload_type, $ok["domain"], $ok["bucket"]);
                }else{
                    $retval = $album->addPicture($pic_name, $pic_tag, $album_id, $ok["path"], '', '', $ok["path"], '', '', $ok["path"], '', '', $ok["path"], '', '', $ok["path"], '', '', $this->instance_id, $this->upload_type, $ok["domain"], $ok["bucket"], '', $file_type);
                }
                if ($retval > 0) {
                    $data['file_id'] = $retval;
                    $data['file_name'] = $ok["path"];
                    $data['origin_file_name'] = $this->file_name;
                    $data['file_path'] = $this->reset_file_path . $newfile;
                    $data['state'] = '1';
                    $data['message'] = "上传成功";
                } else {
                    $data['state'] = '0';
                    $data['message'] = "视频上传失败";
                }
                // 视频文件
            }
            //删除本地的图片
            if ($this->upload_type == 2) {
                @unlink($this->reset_file_path . $newfile);
            }
        } else {
            // 强制将文件后缀改掉，文件流不同会导致上传文件失败
            $data['state'] = '0';
            $data['message'] = "图片上传失败";
//            $this->return['message'] = "请检查您的上传参数配置或上传的文件是否有误";
        }
        return $data;
//        return $this->ajaxFileReturn();
    }

    /**
     * 功能说明：文件上传
     */
    public function uploadFiles() {
        // 验证文件
        $this->file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/' . Session::get(request()->module() . 'instance_id') . '/mp/';
        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型

        if ($this->file_size == 0) {
            $this->return['message'] = "文件大小为0MB";
            return $this->ajaxFileReturn();
        }
        if (!$this->validationFile(3)) {
            return $this->ajaxFileReturn();
        }
        $data = array();
        $cert = request()->get("cert", "");
        $certkey = request()->get("certkey", "");
        $type = request()->get("certtype", "");
        if ($type == 1) {
            $data['certtype'] = 1;
        }
        if ($type == 2) {
            $data['certtype'] = 2;
        }
        if (!empty($cert)) {
            if (legalFile($cert, $this->allow_file_ext['pem'])) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $cert);
            }
        }
        if (!empty($certkey)) {
            if (legalFile($certkey, $this->allow_file_ext['pem'])) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $certkey);
            }
            $data['type'] = 2;
        }
        if ($this->file_path == "") {
            $this->return['message'] = "文件路径不能为空";
            return $this->ajaxFileReturn();
        }
        // 重新设置文件路径
        $this->resetFilePath(1);

        // 检测文件夹是否存在，不存在则创建文件夹
        if (!file_exists($this->reset_file_path)) {
            $mode = intval('0777', 8);
            mkdir($this->reset_file_path, $mode, true);
        }
        $ok = $this->moveUploadFiles($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $this->file_name);
        if ($ok["code"]) {
            @unlink($_FILES['file_upload']);
            $data['filename'] = $this->file_name;
            $data['filesrc'] = '/' . $ok["path"];
            $data['state'] = 1;
            $data['message'] = "文件上传成功";
        } else {
            $data['state'] = '0';
            $data['message'] = "文件上传失败";
        }
        return $data;
    }
    //通联证书文件
    public function uploadTlFiles() {
        // 验证文件
        $this->file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/' . Session::get(request()->module() . 'instance_id') . '/tl/';
        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型
        $data = array();
        $cert = request()->get("cert", "");
        $certkey = request()->get("certkey", "");
        $type = request()->get("certtype", "");
        $tl_password = request()->get("tl_password", "");
        if ($type == 1) {
            $data['certtype'] = 1;
            $file_names =  substr($this->file_name,0,strrpos($this->file_name,"."));
        }
        if ($type == 2) {
            $data['certtype'] = 2;
            $file_names =  substr($this->file_name,0,strrpos($this->file_name,"."));
        }
        if (!empty($cert)) {
            if (legalFile($cert, $this->allow_file_ext['pem'])) {
                @unlink(getcwd()."/".$cert);
            }
        }
        if (!empty($certkey)) {
            if (legalFile($certkey, $this->allow_file_ext['pem'])) {
                @unlink(getcwd()."/".$certkey);
            }
            $data['type'] = 2;
        }
        if ($this->file_path == "") {
            $this->return['message'] = "文件路径不能为空";
            return $this->ajaxFileReturn();
        }
        // 重新设置文件路径
        $this->resetFilePath(3);

        // 检测文件夹是否存在，不存在则创建文件夹
        if (!file_exists($this->reset_file_path)) {
            $mode = intval('0777', 8);
            mkdir($this->reset_file_path, $mode, true);
        }
        $ok = $this->moveUploadFiles($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $this->file_name);
        if ($ok["code"]) {
            @unlink($_FILES['file_upload']);
            if($type == 1){
                shell_exec("openssl x509 -inform DER -in ".getcwd()."/".$this->reset_file_path . $this->file_name."  -out ".getcwd()."/".$this->reset_file_path . $file_names.".pem");
                @unlink(getcwd()."/".$this->reset_file_path . $this->file_name);
            }
            if($type == 2){
                shell_exec("openssl pkcs12  -in  ".getcwd()."/".$this->reset_file_path . $this->file_name." -passin pass:".$tl_password."  -out ".getcwd()."/".$this->reset_file_path . $file_names.".pem -nodes");
                @unlink(getcwd()."/".$this->reset_file_path . $this->file_name);
            }
            $data['filename'] = $file_names;
            $data['filesrc'] = $this->reset_file_path . $file_names.".pem";
            $data['state'] = 1;
            $data['message'] = "文件上传成功";
        } else {
            $data['state'] = '0';
            $data['message'] = "文件上传失败";
        }
        return $data;
    }
    /**
     * 功能说明：文件上传()
     */
    public function uploadTxt() {
        // 验证文件
        $this->file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/';
        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型
        if ($this->file_type != 'text/plain') {
            $this->return['message'] = "文件类型必须是.txt";
            return $this->ajaxFileReturn();
        }
        $data = array();
        $wx_public = request()->get("wx_public", "");
        if (!empty($wx_public)) {
            if (legalFile($wx_public, $this->allow_file_ext['txt'])) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $wx_public);
            }
        }
        if ($this->file_path == "") {
            $this->return['message'] = "文件路径不能为空";
            return $this->ajaxFileReturn();
        }
        // 重新设置文件路径
        $this->resetFilePath(2);
        // 检测文件夹是否存在，不存在则创建文件夹
        if (!file_exists($this->reset_file_path)) {
            $mode = intval('0777', 8);
            mkdir($this->reset_file_path, $mode, true);
        }
        $ok = $this->moveUploadFiles($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $this->file_name);
        if ($ok["code"]) {
            @unlink($_FILES['file_upload']);
            $data['filename'] = $this->file_name;
            $data['filesrc'] = '/' . $ok["path"];
            $data['state'] = 1;
            $data['message'] = "文件上传成功";
        } else {
            $data['state'] = '0';
            $data['message'] = "文件上传失败";
        }
        return $data;
    }

    /**
     * 功能说明：文件上传
     */
    public function uploadSsl() {
        // 验证文件
        
        $data = array();
        $cert = request()->get("ssl", "");
        $certkey = request()->get("sslkey", "");
        $realm_ip = request()->get("realm_ip", "");
        $realm_two_ip = request()->get("realm_two_ip", "");
        $website = new WebSite();
        $check = $website->checkRealmIp($realm_ip, $realm_two_ip);
        if ($check <= 0) {
            if ($check == -8007) {
                $data['message'] = "独立域名已存在，请重新填写";
            } else {
                $data['message'] = "二级域名已存在，请重新填写";
            }
            $data['state'] = 0;
            return $data;
        }
        $type = request()->get("certtype", "");
        if ($type == 1) {
            $data['certtype'] = 1;
            $realm_ip = $realm_ip . '.pem';
            $type = substr(strrchr($_FILES["file_upload"]["name"], '.'), 1);
            if ($type != 'pem') {
                $data['message'] = "文件类型错误";
                $data['state'] = 0;
                return $data;
            }
        }
        if ($type == 2) {
            $data['certtype'] = 2;
            $realm_ip = $realm_ip . '.key';
            $type = substr(strrchr($_FILES["file_upload"]["name"], '.'), 1);
            if ($type != 'key') {
                $data['message'] = "文件类型错误";
                $data['state'] = 0;
                return $data;
            }
        }
        if (!empty($cert)) {
            @unlink($_SERVER['DOCUMENT_ROOT'] . $cert);
        }
        if (!empty($certkey)) {
            @unlink($_SERVER['DOCUMENT_ROOT'] . $certkey);
            $data['type'] = 2;
        }
        $this->file_path = '/www/wdlinux/nginx-1.8.1/conf/cert/';
        $ip = $_SERVER['SERVER_ADDR'];
        if($ip == '172.16.243.84'){
            $this->file_path = '/www/server/panel/vhost/cert/client/';
        }
        if ($this->file_path == "") {
            $data['message'] = "文件路径不能为空";
            $data['state'] = 0;
            return $data;
        }

        // 重新设置文件路径
        $this->resetFilePath(2);
        // 检测文件夹是否存在，不存在则创建文件夹
        if (!file_exists($this->reset_file_path)) {
            $mode = intval('0777', 8);
            mkdir($this->reset_file_path, $mode, true);
        }
        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型
        if ($this->file_size == 0) {
            $data['message'] = "文件大小为0MB";
            $data['state'] = 0;
            return $data;
        }
        if (!$this->validationFile(3)) {
            return $this->ajaxFileReturn();
        }
        $ok = $this->moveUploadFiles($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $realm_ip);
        if ($ok["code"]) {
            @unlink($_FILES['file_upload']);
            $data['filename'] = $realm_ip;
            $data['filesrc'] = $ok["path"];
            $data['state'] = 1;
            $data['message'] = "文件上传成功";
        } else {
            $data['state'] = '0';
            $data['message'] = "文件上传失败";
        }
        return $data;
    }

    public function resetFilePath($type = 0) {
        switch ($type) {
            case 0:
                $year = date('Y',time());
                $month = date('m',time());
                $day = date('d',time());
                $hour = date('H',time());
                $file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/'.$year.'/'.$month.'/'.$day.'/'.$hour.'/';
                if (Session::get(request()->module() . 'instance_id')) {
                    $file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/' . Session::get(request()->module() . 'instance_id') . '/'.$year.'/'.$month.'/'.$day.'/'.$hour.'/';
                }
                break;
            case 1:
                $file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/mp/';
                if (Session::get(request()->module() . 'instance_id')) {
                    $file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/' . Session::get(request()->module() . 'instance_id') . '/mp/';
                }
                break;
            case 3:
                $file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/tl/';
                if (Session::get(request()->module() . 'instance_id')) {
                    $file_path = 'upload/' . Session::get(request()->module() . 'website_id') . '/' . Session::get(request()->module() . 'instance_id') . '/tl/';
                }
                break;
            default:
                $file_path = $this->file_path;
                break;
        }
        $this->reset_file_path = $file_path;
    }

    /**
     * 上传文件后，ajax返回信息
     * @param array $return            
     */
    private function ajaxFileReturn() {
        if (empty($this->return['code']) || null == $this->return['code'] || "" == $this->return['code']) {
            $this->return['code'] = 0; // 错误码
        }

        if (empty($this->return['message']) || null == $this->return['message'] || "" == $this->return['message']) {
            $this->return['message'] = ""; // 消息
        }

        if (empty($this->return['data']) || null == $this->return['data'] || "" == $this->return['data']) {
            $this->return['data'] = ""; // 数据
        }
        return $this->return;
    }

    /**
     *
     * @param unknown $this->file_path
     *            文件路径
     * @param unknown $this->file_size
     *            文件大小
     * @param unknown $this->file_type
     *            文件类型
     * @return string|unknown|number|\think\false
     */
    private function validationFile($type) {
        $flag = true;
        switch ($type) {
            case 1:
                if (($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/x-icon" && $this->file_type != "image/jpeg") || $this->file_size > 5000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过5MB';
                    $flag = false;
                }
                // 公共
                break;
            case 2:
                if (($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg") || $this->file_size > 5000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过5MB';
                    $flag = false;
                }
                // 微信证书
                break;
            case 3:
                if (!in_array($this->file_type, $this->allow_file_ext['pem']) || $this->file_size > 5000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过5MB';
                    $flag = false;
                }
                // 小程序支付证书
                break;
            case 4:
                if ($this->file_type != 'video/mp4' || $this->file_size > 5000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过5MB';
                    $flag = false;
                }
                // 视频文件
                break;
        }
        return $flag;
    }

    /**
     * 删除文件
     */
    public function removeFile() {
        $filename = request()->post("filename", "");
        $res = array();
        $success_count = 0;
        $error_count = 0;
        if ($filename != "") {
            $filename_arr = explode(",", $filename);
            foreach ($filename_arr as $v) {
                if ($v != "") {
                    if (@unlink($v)) {
                        $success_count ++;
                    } else {
                        $error_count ++;
                    }
                }
            }
        }
        $res['success_count'] = $success_count;
        $res['error_count'] = $error_count;
        return $res;
    }

    /**
     * 各类型图片生成
     *
     * @param unknown $photoPath            
     * @param unknown $ext            
     * @param number $type            
     */
    public function photoCreate($upFilePath, $photoPath, $ext, $type, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id, $domain, $bucket, $upload_img) {
        $width1 = 0.6 * $width;
        $width2 = 0.4 * $width;
        $width3 = 0.2 * $width;
        $width4 = 0.1 * $width;
        $height1 = 0.6 * $height;
        $height2 = 0.4 * $height;
        $height3 = 0.2 * $height;
        $height4 = 0.1 * $height;
        $photoArray = array(
            "bigPath" => array(
                "path" => '',
                "width" => $width1,
                "height" => $height1,
                'type' => '1'
            ),
            "middlePath" => array(
                "path" => '',
                "width" => $width2,
                "height" => $height2,
                'type' => '2'
            ),
            "smallPath" => array(
                "path" => '',
                "width" => $width3,
                "height" => $height3,
                'type' => '3'
            ),
            "littlePath" => array(
                "path" => '',
                "width" => $width4,
                "height" => $height4,
                'type' => '4'
            )
        );

        $photoArray["bigPath"]["path"] = $upFilePath . md5(time() . $pic_tag) . "1" . $ext;
        $photoArray["middlePath"]["path"] = $upFilePath . md5(time() . $pic_tag) . "2" . $ext;
        $photoArray["smallPath"]["path"] = $upFilePath . md5(time() . $pic_tag) . "3" . $ext;
        $photoArray["littlePath"]["path"] = $upFilePath . md5(time() . $pic_tag) . "4" . $ext;
        // 循环生成4张大小不一的图
        foreach ($photoArray as $k => $v) {
            if (stristr($type, $v['type'])) {
            $result = $this->uploadThumbFile($photoPath, $v["path"], $v["width"], $v["height"]);
            if ($result["code"]) {
                $photoArray[$k]["path"] = $result["path"];
            } else {
                return 0;
            }
            }
        }
        $album = new Album();
        if ($pic_id == "") {
            $retval = $album->addPicture($pic_name, $pic_tag, $album_id, $upload_img, $width . "," . $height, $width . "," . $height, $photoArray["bigPath"]["path"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["middlePath"]["path"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["smallPath"]["path"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["littlePath"]["path"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $this->instance_id, $this->upload_type, $domain, $bucket);
        } else {
            $retval = $album->ModifyAlbumPicture($pic_id, $upload_img, $width . "," . $height, $width . "," . $height, $photoArray["bigPath"]["path"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["middlePath"]["path"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["smallPath"]["path"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["littlePath"]["path"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $this->instance_id, $this->upload_type, $domain, $bucket);
            $retval = $pic_id;
        }
        return $retval;
    }

    /**
     * 用于相册多图上传
     *
     * @return string|multitype:string NULL Ambigous <unknown, boolean, number, \think\false, string>
     */
    public function photoAlbumUpload() {
        $data = array();
        $this->file_path = request()->post("file_path", "");
        if ($this->file_path == "") {
            $data['state'] = '0';
            $data['message'] = "文件路径不能为空";
            $data['origin_file_name'] = $this->file_name;
            return $data;
        }
        // 重新设置文件路径
        $this->resetFilePath();
        // 检测文件夹是否存在，不存在则创建文件夹
        if (!file_exists($this->reset_file_path)) {
            $mode = intval('0777', 8);
            mkdir($this->reset_file_path, $mode, true);
        }

        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型

        if ($this->file_size == 0) {
            $data['state'] = '0';
            $data['message'] = "文件大小为0MB";
            $data['origin_file_name'] = $this->file_name;
            return $data;
        }
        if ($this->file_size > 5000000) {
            $data['state'] = '0';
            $data['message'] = "文件大小不能超过5MB";
            $data['origin_file_name'] = $this->file_name;
            return $data;
        }

        // 验证文件
        if (!$this->validationFile(1)) {
            return $this->ajaxFileReturn();
        }
        $guid = time();
        $file_name_explode = explode(".", $this->file_name); // 图片名称
        $suffix = count($file_name_explode) - 1;
        $ext = "." . $file_name_explode[$suffix]; // 获取后缀名
        // 获取原文件名
        $tmp_array = $file_name_explode;
        unset($tmp_array[$suffix]);
        $file_new_name = implode(".", $tmp_array);
        $newfile = md5($file_new_name . $guid) . $ext; // 重新命名文件
        // $ok = @move_uploaded_file($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $newfile);

        $ok = $this->moveUploadFile($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $newfile);
        if ($ok["code"]) {
            @unlink($_FILES['file_upload']);
            $image_size = @getimagesize($ok["path"]); // 获取图片尺寸
            if ($image_size) {
                $width = $image_size[0];
                $height = $image_size[1];
                $name = $file_name_explode[0];
                $type = request()->post("type", "");
                $pic_name = request()->post("pic_name", $file_new_name . $guid);
                $album_id = request()->post("album_id", 0);
                $pic_tag = request()->post("pic_tag", $file_new_name);
                $pic_id = request()->post("pic_id", "");
                $upload_flag = request()->post("upload_flag", "");

                // 上传到相册管理，生成四张大小不一的图
                $retval = $this->photoCreate($this->reset_file_path, $this->reset_file_path . $newfile, "." . $file_name_explode[$suffix], $type, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id, $ok["domain"], $ok["bucket"], $ok["path"]);
                if ($retval > 0) {
//                     $album = new Album();
//                     $picture_info = $album->getAlubmPictureDetail([
//                         "pic_id" => $retval
//                     ]);
                    $data['file_id'] = $retval;
//                     $data['file_name'] = $picture_info["pic_cover_mid"];
                    $data['file_name'] = $ok["path"];
                    $data['origin_file_name'] = $this->file_name;
                    $data['file_path'] = $this->reset_file_path . $newfile;
                    $data['state'] = '1';
                } else {
                    $data['state'] = '0';
                    $data['message'] = "图片上传失败";
                    $data['origin_file_name'] = $this->file_name;
                }
            } else {
                $data['state'] = '0';
                $data['message'] = "图片上传失败";
                $data['origin_file_name'] = $this->file_name;
            }
            //删除本地的图片
            if ($this->upload_type == 2) {
                @unlink($this->reset_file_path . $newfile);
            }
        } else {
            $data['state'] = '0';
            $data['message'] = "图片上传失败";
            $data['origin_file_name'] = $this->file_name;
        }
        return $data;
    }

    /**
     * 商品规格图片上传
     *
     * @return multitype:string |string
     */
    public function specImgUpload() {
        $data = array();
        $this->file_path = request()->post("file_path", "");
        if ($this->file_path == "") {
            $data['code'] = '0';
            $data['message'] = "文件路径不能为空";
            return json_encode($data);
        }
        // 重新设置文件路径
        $this->resetFilePath();
        // 检测文件夹是否存在，不存在则创建文件夹
        if (!file_exists($this->reset_file_path)) {
            $mode = intval('0777', 8);
            mkdir($this->reset_file_path, $mode, true);
        }

        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型

        if ($this->file_size == 0) {
            $data['code'] = '0';
            $data['message'] = "文件大小为0MB";
            return json_encode($data);
        }
        if ($this->file_size > 5000000) {
            $data['code'] = '0';
            $data['message'] = "文件大小不能超过5MB";
            return json_encode($data);
        }

        // 验证文件
        if (!$this->validationFile(1)) {
            return $this->ajaxFileReturn();
        }
        $guid = time();
        $file_name_explode = explode(".", $this->file_name); // 图片名称
        $suffix = count($file_name_explode) - 1;
        $ext = "." . $file_name_explode[$suffix]; // 获取后缀名
        // 获取原文件名
        $tmp_array = $file_name_explode;
        unset($tmp_array[$suffix]);
        $file_new_name = implode(".", $tmp_array);
        $newfile = md5($file_new_name . $guid) . $ext; // 重新命名文件
        $ok = @move_uploaded_file($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $newfile);

        if ($ok) {
            @unlink($_FILES['file_upload']);
            $image_size = @getimagesize($this->reset_file_path . $newfile); // 获取图片尺寸
            if ($image_size) {
                $image = \think\Image::open($this->reset_file_path . $newfile);
                $image->thumb(60, 60, \think\Image::THUMB_CENTER)->save($this->reset_file_path . md5(time() . $file_new_name) . "4" . $ext);
                $data['code'] = 1;
                $data['file_path'] = $this->reset_file_path . md5(time() . $file_new_name) . "4" . $ext;
                $data['message'] = "图片上传成功";
                return json_encode($data);
            } else {
                $data['code'] = 0;
                $data['message'] = "图片上传失败";
                return json_encode($data);
            }
        } else {
            $data['code'] = '0';
            $data['message'] = "图片上传失败";
            return json_encode($data);
        }
    }

    /**
     * 原图上传(上传到外网的同时,也会在本地生成图片(在缩略图生成使用后会被删除))
     *
     * @param unknown $file_path            
     * @param unknown $key            
     */
    public function moveUploadFile($file_path, $key) {
        $ok = @move_uploaded_file($file_path, $key);
        $result = [
            "code" => $ok,
            "path" => $key,
            "domain" => '',
            "bucket" => ''
        ];
        if ($ok) {
            if($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/x-icon"){
                (new \data\service\ImgCompress($key, 1))->compressImg($key);
            }
            
            if ($this->upload_type == 2) {
                $alioss = new AliOss();
                $result = $alioss->setAliOssUplaod($key, $key);
            }
        }
        return $result;
    }

    public function moveUploadFiles($file_path, $key) {
        $ok = @move_uploaded_file($file_path, $key);
        $result = [
            "code" => $ok,
            "path" => $key,
            "domain" => '',
            "bucket" => ''
        ];
        if ($ok) {
            return $result;
        }
    }

    /**
     * 用户缩略图上传
     * @param unknown $file_path
     * @param unknown $key
     */
    public function uploadThumbFile($photoPath, $key, $width, $height) {
        try {
            $image = \think\Image::open($photoPath);
            $image->thumb($width, $height, 1);
            $image->save($key, "jpg");
            unset($image);
            $result = array("code" => true, "path" => $key);
            if(file_exists($key)){
                (new \data\service\ImgCompress($key, 1))->compressImg($key);
            }
            if ($this->upload_type == 2) {
                $alioss = new AliOss();
                $result = $alioss->setAliOssUplaod($key, $key);
                @unlink($key);
            }
            return $result;
        } catch (\Exception $e) {
            return array("code" => false);
        }
    }

    /**
     * 用户上传水印处理
     * @param unknown $file_path
     * @param unknown $key
     */
    public function uploadThumbFile1($source) {
        try {
            $image = \think\Image::open($source);
            $data = $image->water(substr($this->watermark_logo, 1), $this->watermark_position, $this->watermark_font);
            $image->save($source, "png");
            unset($image);
            $result = array("code" => true, "path" => $source);
            if ($this->upload_type == 2) {
                $alioss = new AliOss();
                $result = $alioss->setAliOssUplaod($source, $source);
            }
            return $result;
        } catch (\Exception $e) {
            return array("code" => false);
        }
    }

    /**
     * sql文件上传读取
     */
    public function uploadSql() {
        $data = array();
        $this->file_name = $_FILES["file_upload_sql"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload_sql"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload_sql"]["type"]; // 文件类型
        if ($this->file_size == 0) {
            $data['code'] = '0';
            $data['message'] = "文件大小为0MB";
            return json_encode($data);
        }
        if ($this->file_size > 102400) {
            $data['code'] = '0';
            $data['message'] = "文件大小不能超过100k";
            return json_encode($data);
        }


        $name = $_FILES["file_upload_sql"]["tmp_name"];
        $str = "";
        if (file_exists($name)) {
            $fp = fopen($name, "r");
            $str = "";
            while (!feof($fp)) {
                $str .= fgets($fp); //逐行读取。如果fgets不写length参数，默认是读取1k。
            }
        }
        $data = array("code" => 1, "sql_str" => $str);
        return json_encode($data);
    }

}
