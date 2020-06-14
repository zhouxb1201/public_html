<?php
namespace addons\qlkefu\controller;

use think\Controller;
use think\Config;

class BaseController extends Controller {


    public function __construct() {
        parent::__construct();
        Config::load('addons/qlkefu/config.php');
        $this->salt = Config::get('qlkefu.salt');
        $this->respond();
    }

    //响应前台的请求
    private function respond() {
        //验证身份
        $timestamp = input('post.timestamp','');
        $nonce_str = input('post.nonce_str','');
        $signature = input('post.signature','');
        $str = $this->arithmetic($timestamp, $nonce_str);
        if (!$timestamp || !$nonce_str || !$signature || $str != $signature) {
            echo json_encode(['code'=>-1, 'message'=>'身份验证失败'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * @param $timestamp //时间戳
     * @param $nonce_str //随机字符串
     * @return string //返回签名
     */
    public function arithmetic($timestamp, $nonce_str) {
        $arr = [];
        $arr['timestamp'] = $timestamp;
        $arr['nonce_str'] = $nonce_str;
        $arr['salt'] = $this->salt;
        //按照首字母大小写顺序排序
        sort($arr, SORT_STRING);
        //拼接成字符串
        $str = implode($arr);
        //进行加密,转换成大写
        $signature = strtoupper(md5(sha1($str)));
        return $signature;
    }
}
