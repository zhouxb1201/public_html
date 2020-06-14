<?php

namespace app\wapapi\controller;

\think\Loader::addNamespace('data', 'data/');

use data\service\Album as Album;
use data\service\Config as WebConfig;
use data\service\Upload\AliOss;
use think\Controller;

/**
 * 图片上传控制器
 * goods（文件夹存放商品）
 * goods_id（每个商品的）
 * images（商品主图）
 * sku_img（sku图片）
 *
 * common文件夹（存放公共图）
 *
 * advertising文件夹（存放广告位图）
 *
 * avator文件夹（用户头像）
 *
 * pay文件夹（支付生成的图）
 *
 *
 * @author  www.vslai.com
 *
 */

use think\Config;
use think\Session;

// 存放公共图片、网站logo、独立图片、没有任何关联的图片
define('UPLOAD_COMMON', 'upload/' . Session::get('wapapiwebsite_id') . '/common/');
// 存放用户头像
define('UPLOAD_AVATOR', 'upload/' . Session::get('wapapiwebsite_id') . '/avator/');
// 商品评价图片
define('UPLOAD_EVALUATE', 'upload/' . Session::get('wapapiwebsite_id') . '/evaluate/');
// 自定义表单图片路径
define('UPLOAD_CUSTOMFORM', 'upload/' . Session::get('wapapiwebsite_id') . '/customform/');

class Upload extends Controller
{

    private $return = array();

    // 文件路径
    private $file_path = '';

    // 重新设置的文件路径
    private $reset_file_path = '';

    // 文件名称
    private $file_name = '';

    // 临时名称
    private $temp_file_name;

    // 文件大小
    private $file_size = 0;

    // 文件类型
    private $file_type = '';

    private $upload_type = 1;

    private $instance_id = '';

    public function __construct()
    {
        $config = new WebConfig();
        $this->upload_type = $config->getUploadType();
    }

    /**
     * 功能说明：文件(图片)上传(存入相册)
     */
    public function uploadImage()
    {
        $type = request()->post('type');
        switch ($type) {
            case 'avatar':
                $this->file_path = UPLOAD_AVATOR;
                break;
            case 'evaluate':
                $this->file_path = UPLOAD_EVALUATE;
                break;
            case 'customform':
                $this->file_path = UPLOAD_CUSTOMFORM;
                break;
            default:
                $this->file_path = UPLOAD_COMMON;
        }
        if ($this->file_path == '') {
            $this->return['message'] = '文件路径不能为空';
            return $this->ajaxFileReturn();
        }
        // 重新设置文件路径

        $this->resetFilePath();
        // 检测文件夹是否存在，不存在则创建文件夹
        if (!file_exists($this->reset_file_path)) {
            $mode = intval('0777', 8);
            mkdir($this->reset_file_path, $mode, true);
        }

        $this->file_name = $_FILES['file']['name']; // 文件原名
        $this->temp_file_name = $_FILES['file']['tmp_name']; //临时名称
        $this->file_size = $_FILES['file']['size']; // 文件大小
        $this->file_type = $_FILES['file']['type']; // 文件类型

        if ($this->file_size == 0) {
            $this->return['message'] = '文件大小为0MB';
            return $this->ajaxFileReturn();
        }

        // 验证文件
        if (!$this->validationFile()) {
            return $this->ajaxFileReturn();
        }
        $file_name_explode = explode('.', $this->file_name); // 图片名称
        $suffix = count($file_name_explode) - 1;
        $ext = '.' . $file_name_explode[$suffix]; // 获取后缀名
        $new_file_name = time() . mt_rand(1000, 9999) . $ext; // 重新命名文件 ,mt_rand防止上传多张时重命名为同一名称

        $ok = $this->moveUploadFile($this->temp_file_name, $this->reset_file_path . $new_file_name);
        if ($ok['code']) {
            $this->return['code'] = 1;
            $this->return['data'] = ['src' => getApiSrc($ok['path'])];
            $this->return['message'] = '上传成功';

            switch ($type) {
                case 'avatar':
                    $member_server = new \data\service\Member();
                    $condition['uid'] = getUserId();
                    $data['user_headimg'] = $ok['path'];
                    $member_server->updateUserNew($data, $condition);
                    break;
            }
            //删除本地的图片
            if($this->upload_type == 2){
                @unlink($this->reset_file_path . $new_file_name);
            }
        } else {
            // 强制将文件后缀改掉，文件流不同会导致上传文件失败
            $this->return['message'] = '请检查您的上传参数配置或上传的文件是否有误';
        }
        return $this->ajaxFileReturn();
    }

    public function resetFilePath()
    {
        if (strstr($this->file_path, 'goods/')) {
            $file_path = $this->file_path . date('Ymd') . '/';
        } elseif (strstr($this->file_path, 'goods_sku/')) {
            $file_path = $this->file_path . request()->post('goods_path', '') . '/';
        } else {
            $file_path = $this->file_path;
        }
        $this->reset_file_path = $file_path;
    }

    /**
     * 上传文件后，ajax返回信息
     *
     *
     * @param array $return
     */
    private function ajaxFileReturn()
    {
        if (empty($this->return['code']) || null == $this->return['code'] || '' == $this->return['code']) {
            $this->return['code'] = -1; // 错误码
        }

        if (empty($this->return['message']) || null == $this->return['message'] || '' == $this->return['message']) {
            $this->return['message'] = ''; // 消息
        }

        if (empty($this->return['data']) || null == $this->return['data'] || '' == $this->return['data']) {
            $this->return['data'] = ''; // 数据
        }
        return json($this->return);
    }

    /**
     *
     * @param unknown $this ->file_path
     *            文件路径
     * @param unknown $this ->file_size
     *            文件大小
     * @param unknown $this ->file_type
     *            文件类型
     * @return string|unknown|number|\think\false
     */
    private function validationFile()
    {
        $flag = true;
        if (strstr($this->file_path, 'goods/')) {
            if ($this->file_type != 'image/gif' && $this->file_type != 'image/png' && $this->file_type != 'image/jpeg' && $this->file_size > 5000000) {
                $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过5MB';
                $flag = false;
            }
        } elseif (strstr($this->file_path, 'video/')) {
            if ($this->file_type != 'video/mp4' && $this->file_size > 500000000) {
                $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过500MB';
                $flag = false;
            }
        } else {
            if ($this->file_type != 'image/gif' && $this->file_type != 'image/png' && $this->file_type != 'image/jpeg' && $this->file_size > 1000000) {
                $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过1MB';
                $flag = false;
            }
        }
        return $flag;
    }

    /**
     * 各类型图片生成
     * @param number $type
     */
    public function photoCreate($upFilePath, $photoPath, $ext, $type = 0, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id, $domain, $bucket, $upload_img)
    {
        $width1 = 0.6 * $width;
        $width2 = 0.4 * $width;
        $width3 = 0.2 * $width;
        $width4 = 0.1 * $width;
        $height1 = 0.6 * $height;
        $height2 = 0.4 * $height;
        $height3 = 0.2 * $height;
        $height4 = 0.1 * $height;
        $photoArray = array(
            'bigPath' => array(
                'path' => '',
                'width' => $width1,
                'height' => $height1,
                'type' => '1'
            ),
            'middlePath' => array(
                'path' => '',
                'width' => $width2,
                'height' => $height2,
                'type' => '2'
            ),
            'smallPath' => array(
                'path' => '',
                'width' => $width3,
                'height' => $height3,
                'type' => '3'
            ),
            'littlePath' => array(
                'path' => '',
                'width' => $width4,
                'height' => $height4,
                'type' => '4'
            )
        );

        $photoArray['bigPath']['path'] = $upFilePath . md5(time() . $pic_tag) . '1' . $ext;
        $photoArray['middlePath']['path'] = $upFilePath . md5(time() . $pic_tag) . '2' . $ext;
        $photoArray['smallPath']['path'] = $upFilePath . md5(time() . $pic_tag) . '3' . $ext;
        $photoArray['littlePath']['path'] = $upFilePath . md5(time() . $pic_tag) . '4' . $ext;
        // 循环生成4张大小不一的图
        foreach ($photoArray as $k => $v) {
            if (stristr($type, $v['type'])) {
                $result = $this->uploadThumbFile($photoPath, $v['path'], $v['width'], $v['height']);
                if ($result['code']) {
                    $photoArray[$k]['path'] = $result['path'];
                } else {
                    return 0;
                }
            }
        }

        $album = new Album();
        if ($pic_id == '') {
            $retval = $album->addPicture($pic_name, $pic_tag, $album_id, $upload_img, $width . ',' . $height, $width . ',' . $height, $photoArray['bigPath']['path'], $photoArray['bigPath']['width'] . ',' . $photoArray['bigPath']['height'], $photoArray['bigPath']['width'] . ',' . $photoArray['bigPath']['height'], $photoArray['middlePath']['path'], $photoArray['middlePath']['width'] . ',' . $photoArray['middlePath']['height'], $photoArray['middlePath']['width'] . ',' . $photoArray['middlePath']['height'], $photoArray['smallPath']['path'], $photoArray['smallPath']['width'] . ',' . $photoArray['smallPath']['height'], $photoArray['smallPath']['width'] . ',' . $photoArray['smallPath']['height'], $photoArray['littlePath']['path'], $photoArray['littlePath']['width'] . ',' . $photoArray['littlePath']['height'], $photoArray['littlePath']['width'] . ',' . $photoArray['littlePath']['height'], $this->instance_id, $this->upload_type, $domain, $bucket);
        } else {
            $album->ModifyAlbumPicture($pic_id, $upload_img, $width . ',' . $height, $width . ',' . $height, $photoArray['bigPath']['path'], $photoArray['bigPath']['width'] . ',' . $photoArray['bigPath']['height'], $photoArray['bigPath']['width'] . ',' . $photoArray['bigPath']['height'], $photoArray['middlePath']['path'], $photoArray['middlePath']['width'] . ',' . $photoArray['middlePath']['height'], $photoArray['middlePath']['width'] . ',' . $photoArray['middlePath']['height'], $photoArray['smallPath']['path'], $photoArray['smallPath']['width'] . ',' . $photoArray['smallPath']['height'], $photoArray['smallPath']['width'] . ',' . $photoArray['smallPath']['height'], $photoArray['littlePath']['path'], $photoArray['littlePath']['width'] . ',' . $photoArray['littlePath']['height'], $photoArray['littlePath']['width'] . ',' . $photoArray['littlePath']['height'], $this->instance_id, $this->upload_type, $domain, $bucket);
            $retval = $pic_id;
        }
        return $retval;
    }

    /**
     * 原图上传
     *
     * @param unknown $file
     * @param string $destination
     */
    public function moveUploadFile($file, $destination)
    {
        $ok = @move_uploaded_file($file, $destination);
        $result = [
            'code' => $ok,
            'path' => $destination,
            'domain' => '',
            'bucket' => ''
        ];
        if($ok){
            if($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/x-icon"){
                (new \data\service\ImgCompress($destination, 1))->compressImg($destination);
            }
            if ($this->upload_type == 2) {
                $alioss = new AliOss();
                $result = $alioss->setAliOssUplaod($destination, $destination);
                @unlink($_FILES['file_upload']);
            }
        }
        return $result;
    }

    /**
     * 用户缩略图上传
     * @param unknown $file_path
     * @param unknown $key
     */
    public function uploadThumbFile($photoPath, $key, $width, $height)
    {
        try {
            $image = \think\Image::open($photoPath);
            $image->thumb($width, $height, 1);
            $image->save($key, 'jpg');
            unset($image);
            $result = array('code' => true, 'path' => $key);
            if(file_exists($key)){
                (new \data\service\ImgCompress($key, 1))->compressImg($key);
            }
            return $result;
        } catch (\Exception $e) {
            return array('code' => false);
        }
    }
}