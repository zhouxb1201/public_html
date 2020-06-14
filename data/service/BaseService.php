<?php

namespace data\service;

use addons\miniprogram\controller\Miniprogram;
use addons\poster\model\PosterModel;
use addons\poster\Poster;
use addons\taskcenter\model\VslGeneralPosterModel;
use addons\taskcenter\service\Taskcenter;
use data\model\ConfigModel;
use data\model\UserModel;
use data\model\UserTaskModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslMemberModel;
use data\model\WebSiteModel;
use data\model\WeixinFansModel;
use think\Db;
use think\Exception;
use \think\Session as Session;
use think\Cache;
use think\Request;
use think\Config;
use data\service\Config as ConfigService;

class BaseService
{
    protected $uid;
    protected $instance_id;  //店铺id
    protected $is_admin;
    protected $is_member;
    protected $website_id;
    protected $module_id_array;
    protected $instance_name;
    protected $is_system;
    protected $website_create_time;

    public function __construct()
    {
        $this->init();
    }

    /**
     * 初始化数据
     */
    private function init()
    {
        $model = $this->getRequestModel();
        $this->uid = Session::get($model . 'uid') ?: getUserId();
        $this->instance_id = Session::get($model . 'instance_id');
        $this->website_id = Session::get($model . 'website_id');
        if ($this->instance_id == null || $this->instance_id == false) {
            $this->instance_id = 0;
        }
        $this->is_admin = Session::get($model . 'is_admin');
        $this->module_id_array = Session::get($model . 'module_id_array');
        $this->instance_name = Session::get($model . 'instance_name');
        $this->is_member = Session::get($model . $this->website_id . 'is_member');
        $this->is_system = Session::get($model . 'is_system');
        $this->website_create_time = Session::get($model . 'website_create_time');
    }

    /*
     * 连接redis
     * **/
    public function connectRedis(){
        $config = new ConfigService();
        $redis_config = $config->getRedisConfig(0);
        $host = '';
        $pwd = '';
        if($redis_config){
            $host = $redis_config['host'];
            $pwd = $redis_config['pass'];
        }
        $port = 6379;
        $redis = new \Redis();
        if($host && $pwd){
            if ($redis->connect($host, $port) == false) {
                return json(['code'=>-1, 'message'=>'redis服务连接失败']);
            }
            if ($redis->auth($pwd) == false) {
                return json(['code'=>-1, 'message'=>'redis密码错误']);
            }
        }else{
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
        }
        return $redis;
    }

    /**
     * 把返回的数据集转换成Tree
     * @param array $list 要转换的数据集
     * @param string $pid parent标记字段
     * @param string $level level标记字段
     * @return array
     */
    function listToTree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
    {
        for ($k = 0; $k < count($list); $k++) {
            $list[$k][$child] = array();
        }
        // 创建Tree  
        for ($i = count($list) - 1; $i >= 0; $i--) {
            for ($j = 0; $j < count($list); $j++) {
                if ($list[$j][$pk] == $list[$i][$pid]) {
                    if (empty($list[$j][$child])) {
                        $list[$j][$child][0] = $list[$i];
                    } else {
                        $list[$j][$child] = array_push($list[$j][$child], $list[$i]);
                    }


                }
            }

        }
        return $list;
    }

    /**
     * 添加缓存key值
     * @param unknown $key
     * @param unknown $value
     */
    public function addCacheKeyTag($key, $tag)
    {
        $key_list = Cache::get($key);
        if (empty($key_list)) {
            $key_list = array();

        }
        if (!in_array($tag, $key_list)) {
            $key_list[] = $tag;
            Cache::set($key, $key_list);
        }
    }

    /**
     * 清除对应key相关tag的所有缓存
     * @param unknown $key
     */
    public function clearKeyCache($key)
    {
        $key_list = Cache::get($key);
        if (!empty($key_list)) {
            foreach ($key_list as $k => $v) {
                Cache::set($v, '');
            }
        }

    }

    /**
     * 获取model
     * @return Ambigous <string, \think\Request>
     */
    public function getRequestModel()
    {
        $model = Request::instance()->module();
        return $model;
    }

    /**
     * 获取海报
     * @param array $poster 海报数据
     * @param int $poster_id 海报id
     * @param string $type 超级海报 poster、任务海报task_poster
     * @param string $open_id 关注类型海报用户open_id
     * @param int $goods_id 商品类型海报goods_id
     * @return array
     */
    public function posterImage($poster, $poster_id, $type = 'task_poster', $open_id, $goods_id = 0, $uid = 0)
    {
        try {
            // 区分小程序
            $is_mp = false;
            if (isset($poster['is_mp']) && $poster['is_mp'] == 1) {
                $is_mp = true;
            }

            $user_model = new UserModel();
            if($open_id){
                $user_info = $user_model::get(['user_model.website_id' => $this->website_id, 'wx_openid' => $open_id, 'is_member' => 1], ['member_info']);
            }else{
                $user_info = $user_model::get(['user_model.website_id' => $this->website_id, 'sys_user.uid' => $uid, 'is_member' => 1], ['member_info']);
            }
            if (empty($user_info)) {
//                return ['code' => -1, 'message' => '空的用户信息'];
                return ['code' => -2, 'message' => '您的用户信息不存在'];
            }
            if($type == 'poster' && $poster['type'] == 2 && $goods_id){//是商品海报，判断是否设置了独立海报
                //获取每个类型的默认数据
                $goods_model = new VslGoodsModel();
                $is_goods_poster = $goods_model->getInfo(['goods_id' => $goods_id], 'is_goods_poster_open, poster_data, px_type');
                if($is_goods_poster['is_goods_poster_open'] == 1){//开启了商品独立海报
                    $poster['poster_data'] = htmlspecialchars_decode($is_goods_poster['poster_data']);
                    $poster['px_type'] = $is_goods_poster['px_type'];
                }

            }
            $poster_config = Config::get('poster');//修改从数据库取
            $px_type = isset($poster['px_type']) ? $poster['px_type'] : 1;
            if($px_type == 2){
                $poster_config['default_width'] = 1080;
                $poster_config['default_height'] = 1920;
                $poster_config['default_bg'] = 'public' . DS . 'static' . DS . 'images' . DS . 'poster' . DS . 'default_poster_1080_1920.jpg';
            }

//            $poster_path = 'upload' . DS . $this->website_id . DS . $type . DS . $open_id . DS;
            $poster_path = $is_mp
                ? 'upload' . DS . $this->website_id . DS . $type .'_mp'. DS . $user_info['uid'] . DS
                : 'upload' . DS . $this->website_id . DS . $type . DS . $user_info['uid'] . DS;
            if (!is_dir($poster_path)) {
                $mode = intval('0777', 8);
                mkdir($poster_path, $mode, true);
                chmod($poster_path, $mode);
            }

            $website_model = new WebSiteModel();
            $weixin_service = new Weixin();
            $website_info = $website_model::get(['website_id' => $this->website_id]);
            $is_ssl = \think\Request::instance()->isSsl();
            $protocol = $is_ssl ? "https://" : "http://";
            if ($website_info['realm_ip']) {
                $domain_name = $protocol . $website_info['realm_ip'];
            } else {
                $ip = top_domain($_SERVER['HTTP_HOST']);
                $domain_name = $protocol . top_domain($website_info['realm_two_ip']) .'.'. $ip;
            }
            if ($type == 'poster') {
                //判断是否分销商才可以获取海报
                if($poster['is_perm'] == 0 && $poster['type'] == 3){//关注海报不是分销商不可以生成海报
                    if($user_info['member_info']['isdistributor'] != 2){//不是分销商
                        return ['code' => -2, 'message' => $poster['customer_service_message']];
                    }
                }
                $poster_data = json_decode($poster['poster_data'], true);
                // 超级海报
                switch ($poster['type']) {
                    // 1:商城 2:商品 3:关注 4:微店
                    case 1:
                        $image_path = $poster_path . $poster_id . '.' . $poster_config['default_ext'];
                        $qr_url = $domain_name . '/wap/mall/index';
                        if ($user_info['member_info']['isdistributor'] == 2 && !empty($user_info['member_info']['extend_code'])) {
                            $qr_url .= '?extend_code=' . $user_info['member_info']['extend_code'] . '&poster_id=' . $poster_id . '&poster_type=1';
                        }
                        break;
                    case 2:
                        $image_path = $poster_path . $poster_id . '_' . $goods_id . '.' . $poster_config['default_ext'];
                        $goods_model = new VslGoodsModel();
                        $goods_info = $goods_model::get($goods_id, ['album_picture']);
                        if (empty($goods_info)) {
//                            return ['code' => -1, 'message' => '空的商品信息'];
                            return ['code' => -2, 'message' => '商品信息不存在'];
                        }
                        //用于海报显示价格
                        //获取sku最小的价格
                        $goods_service = new Goods();
                        //获取sku最小的价格 用于计算限时折扣
                        $min_sku_price = $goods_service->getMinSkuPrice($goods_id);
                        $goods_info['price'] = $goods_service->getPromotionPrice($goods_info['promotion_type'], $goods_id, $min_sku_price, $goods_info['shop_id'], $goods_info['website_id']) ? : $min_sku_price;
                        $qr_url = $domain_name . '/wap/goods/detail/' . $goods_id;
                        if ($user_info['member_info']['isdistributor'] == 2 && !empty($user_info['member_info']['extend_code'])) {
                            $qr_url .= '?extend_code=' . $user_info['member_info']['extend_code'] . '&poster_id=' . $poster_id . '&poster_type=1';
                        }
                        break;
                    case 3:
                        $image_path =  $poster_path . $poster_id . '.' . $poster_config['default_ext'];
                        if ($poster['is_perm'] == 0 && $user_info['member_info']['isdistributor'] != 2) {
                            // 分销商才能获取关注海报
                            return ['code' => -2, 'message' => $poster['customer_service_message'] . PHP_EOL . $poster['explanation_link']];
                        }
                        break;
                    case 4:
                        $qr_url = $domain_name .'/wap/microshop/previewshop?shopkeeper_id='.$user_info['uid'];
                        $image_path = $poster_path . $poster_id . '.' . $poster_config['default_ext'];
//                        $qr_url = $domain_name . '/wap/mall/index';// 微店还没对接不知道链接
                        if ($user_info['member_info']['isdistributor'] == 2 && !empty($user_info['member_info']['extend_code'])) {
                            $qr_url .= '&extend_code=' . $user_info['member_info']['extend_code'] . '&poster_id=' . $poster_id . '&poster_type=1';
                        }
                        break;
                    default:
//                        return ['code' => -1, 'message' => '无效的海报类型'];
                        return ['code' => -2, 'message' => '无效的海报类型'];
                }
            } else {
                $task_service = new Taskcenter();
                $general_poster = new VslGeneralPosterModel();
                $general_poster_cond['general_poster_id'] = $poster_id;
                $general_poster_info = $general_poster->getInfo($general_poster_cond, '*');
                if(!$general_poster_info){
                    return ['code' => -2, 'message' => '海报任务不存在'];
                }else{
                    if($general_poster_info['task_status'] == 0){
                        return ['code' => -2, 'message' => '任务海报已关闭'];
                    }
                    if($general_poster_info['end_task_time'] < time()){
                        $warning = $general_poster_info['task_ended_warning'];
                        $start_date = date('Y-m-d H:i:s', $general_poster_info['start_task_time']);
                        $end_date = date('Y-m-d H:i:s', $general_poster_info['end_task_time']);
                        $warning = str_replace('任务开始时间', $start_date, $warning);
                        $warning = str_replace('任务结束时间', $end_date, $warning);
                        return ['code' => -2, 'message' => $warning];
                    }elseif($general_poster_info['start_task_time'] > time()){
                        $warning = $general_poster_info['task_unstart_warning'];
                        $start_date = date('Y-m-d H:i:s', $general_poster_info['start_task_time']);
                        $end_date = date('Y-m-d H:i:s', $general_poster_info['end_task_time']);
                        $warning = str_replace('任务开始时间', $start_date, $warning);
                        $warning = str_replace('任务结束时间', $end_date, $warning);
                        return ['code' => -2, 'message' => $warning];
                    }
                    //判断是否设置了开启分销商才能获取海报
                    if($general_poster_info['perm_setting'] == 0 && $user_info['member_info']['isdistributor'] != 2){
                        return ['code' => -2, 'message' => $poster['customer_service_message']];
                    }
                }
                //判断当前用户是否在规定时间内领取了该任务
                $uid = $user_info['uid'];
                $user_task_cond['uid'] = $uid;
                $user_task_cond['general_poster_id'] = $poster_id;
                $user_task_cond['is_complete'] = 0;
                $user_task_cond['get_time'] = ['<=', time()];
                $user_task_cond['need_complete_time'] = ['>=', time()];
                $user_task = new UserTaskModel();
                $user_task_info = $user_task->getInfo($user_task_cond,'*');
                if(!$user_task_info){
                    $task_service->getPosterTask($poster_id, $uid, $this->website_id, 1);
                }
                $poster_data = json_decode($poster['poster_design'], true);
                // 任务海报
                $image_path = $poster_path . $poster_id . '.' . $poster_config['default_ext'];
                if ($poster['start_task_time'] > time()) {
                    return ['code' => -2, 'message' => date('Y-m-d H:i:s',$poster['start_task_time']).' '.date('Y-m-d H:i:s',$poster['end_task_time'])];
                }
                if ($poster['end_task_time'] < time()) {
                    return ['code' => -2, 'message' => date('Y-m-d H:i:s',$poster['start_task_time']).' '.date('Y-m-d H:i:s',$poster['end_task_time'])];
                }
            }
            if (is_file($image_path)) {
                return ['code' => 1, 'poster' => $image_path];
            }
            if (!empty($poster_data['bg'])) {
                $bg = $this->imagePath($poster_data['bg'], $poster_path);
                $image = \think\Image::open($bg);
//                $new_bg = $poster_path . 'water_' . uniqid() . '.' . $image->type();
//                $new_width = $poster_config['default_width'];
//                $new_height = $poster_config['default_width'] * $image->height() / $image->width();
//                $image->thumb($new_width, $new_height, $image::THUMB_SCALING, false)->save($new_bg);
//                unset($image);
//                $image = \think\Image::open($new_bg);
//                $this->unlinkImage($new_bg);
                $scale = $image->width() / $image->height();// 背景图比例
                $width_scale = $poster_config['default_width'] / $poster_config['default_height'];// 默认海报大小的比例
                if ($image->width() != $poster_config['default_width'] || $image->height() != $poster_config['default_height']) {
                    // 如果背景图的长宽比例和海报设置的比例差别大，使用白板作为基础图片,背景作为图片水印
                    // 带鱼图片(($scale > $width_scale + 0.1),缩小图片为水印图;长图片(($scale < $width_scale - 0.1),仅使用图片头部
                    $new_bg = $poster_path . 'water_' . uniqid() . '.' . $image->type();
                    $new_width = $poster_config['default_width'];
                    $new_height = ($scale > ($width_scale + 0.1)) ? $image->height() * $poster_config['default_width'] / $image->width() : $poster_config['default_height'];
                    $image->thumb($new_width, $new_height, $image::THUMB_SCALING, false)->save($new_bg);
                    unset($image);
                    $image = \think\Image::open($poster_config['default_bg']);
                    $image->water($new_bg, [0, 0]);
                    $this->unlinkImage($new_bg);
                }
                $this->unlinkImage($bg);
            } else {
                $image = \think\Image::open($poster_config['default_bg']);
            }
            if (isset($poster_data['items']) && !empty($poster_data['items'])) {
                if($px_type == 2){
                    $beishu = 3;
                }elseif($px_type == 1){
                    $beishu = 2;
                }
                $member = new VslMemberModel();
                foreach ($poster_data['items'] as $v) {
                    $from_left = intval($v['from_left']) * $beishu;
                    $from_top = intval($v['from_top']) * $beishu;
//                    $br_left = $poster_config['default_width'] - $from_left;
                    $v['width']  = intval($v['width']) * $beishu;
                    $v['height'] = intval($v['height']) * $beishu;
                    $br_left = $from_left + $v['width'];
//                    $v['size'] = intval($v['size']) * $beishu;
                    switch ($v['type']) {
                        case 'img':
                            if($v['width'] == 'auto'){
                                $v['width'] = 0;
                            }
                            if($v['height'] == 'auto'){
                                $v['height'] = 0;
                            }
                            // 原图大小和在海报设置的大小不一致，water不能先缩放原图再添加水印，先生成指定大小的图片，再添加水印，最后删除生成的图片
                            $img = $this->imagePath($v['img'], $poster_path, $v['width'], $v['height']);
                            $image->water($img, [$from_left, $from_top]);
                            $this->unlinkImage($img);
                            break;
                        case 'head':
                            // 头像
                            if (!empty($user_info['user_headimg'])) {
                                $img = $this->imagePath($user_info['user_headimg'], $poster_path, $v['width'], $v['height']);
                                $image->water($img, [$from_left, $from_top]);
                                $this->unlinkImage($img);
                            }
                            break;
                        case 'goods-img':
                            // 商品图片
                            if (isset($goods_info) && !empty($goods_info->album_picture->pic_cover)) {
                                $img = $this->imagePath($goods_info->album_picture->pic_cover, $poster_path, $v['width'], $v['height']);
                                $image->water($img, [$from_left, $from_top]);
                                $this->unlinkImage($img);
                            }
                            break;
                        case 'thumb':
                            // 商品图片
                            if (isset($goods_info) && !empty($goods_info->album_picture->pic_cover)) {
                                $img = $this->imagePath($goods_info->album_picture->pic_cover, $poster_path, $v['width'], $v['height']);
                                $image->water($img, [$from_left, $from_top]);
                                $this->unlinkImage($img);
                            }
                            break;
                        case 'qr':
                            $qr_path = '';
                            // 二维码
                            if (isset($qr_url)){
                                // 小程序码url
                                if ($is_mp) {
                                    $mpController = new Miniprogram();
                                    $code = !empty($user_info['member_info']['extend_code']) ? $user_info['member_info']['extend_code'] : -1 ;
                                    $goods_id = !empty($goods_id) ? '_' . $goods_id : '';
                                    $poster_id = !empty($poster_id) ? '_' . $poster_id : '';
                                    $poster_type = !empty($poster['poster_type']) ? '_' . $poster['poster_type'] : '';
                                    if($poster['type'] == 4){
                                        $poster_uid = !empty($user_info['uid']) ? '_' . $user_info['uid'] : '';
                                    }
                                    $params = [
                                        'scene' => $code .$goods_id. $poster_uid .$poster_id .$poster_type,
                                        'page' => $poster['mp_page'] ?: Config::get('mp_route')[0],
                                    ];

                                    $result = $mpController->getUnLimitMpCode($params);
                                    $mp_result = json_decode($result->getContent(),true);
                                    if ($mp_result['code'] == 1) {
                                        $qr_path = $mp_result['data'];
                                    }
                                } else {
                                    // 存在$qr_url即是普通链接二维码
                                    $qr_path = getQRcode($qr_url, $poster_path, 'poster_qr');
                                }
                            } else {
                                // 公众号二维码
                                if($type == 'poster'){
                                    //获取关注海报的id
/*                                    $sub_info = new PosterModel();
                                    $suc_con['is_default'] = 1;
                                    $suc_con['type'] = 3;
                                    $suc_con['website_id'] = $this->website_id;
                                    $sub_poster_id = $sub_info->getInfo($suc_con, 'poster_id')['poster_id'];*/
                                    $sub_poster_id = $poster_id;
                                }else{
                                    $sub_poster_id = $poster_id;
                                }

                                $scene_array['poster_type'] = $poster['poster_type'];
                                $scene_array['uid'] = $user_info['uid'];
//                                $scene_array['poster_id'] = $poster_id;
                                $scene_array['poster_id'] = $sub_poster_id;
                                $scene['scene_str'] = json_encode($scene_array);
                                $qr_path = $weixin_service->getUserWchatQrcode($scene,'QR_LIMIT_STR_SCENE');
                            }
                            $img = $this->imagePath($qr_path, $poster_path, $v['width'], $v['height']);
                            $image->water($img, [$from_left, $from_top]);
                            $this->unlinkImage($img);
                            break;
                        case 'nickname':
                            $user_info['nick_name'] = $user_info['nick_name'] ? $user_info['nick_name'] :($user_info['user_name'] ? $user_info['user_name'] : $user_info['user_tel']);
                            if($user_info['nick_name']){
                                if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                                    $v['size'] = '14';
                                }
                                if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                                    $v['color'] = '#000';
                                }
                                // 昵称
                                $image->text($user_info['nick_name'], '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                            }
                            break;
                        case 'title':
                            if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                                $v['size'] = '14';
                            }
                            if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                                $v['color'] = '#000';
                            }
                            // 商品名称
                            if (isset($goods_info)) {
                                $image->text($goods_info['goods_name'], '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                            }
                            break;
                        case 'words':
                            $content = $v['string'];
                            if(strpos($content, '昵称')){
                                $content = str_replace('昵称', $user_info['nick_name'], $content);
                            }
                            if(strpos($content, '等级')){
                                //获取会员等级
                                $member_level_arr = $member->alias('m')->field('level_name')->join('vsl_member_level ml', 'm.member_level=ml.level_id', 'left')->where(['m.uid'=>$user_info['uid']])->find();
                                $content = str_replace('等级', $member_level_arr['level_name'], $content);
                            }
                            if(strpos($content, '手机号')){
                                $content = str_replace('手机号', $user_info['user_tel'], $content);
                            }
                            if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                                $v['size'] = '14';
                            }
                            if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                                $v['color'] = '#000';
                            }
                            //加入水印
                            $image->text($content, '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                            break;
                        case 'productprice':
                            if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                                $v['size'] = '14';
                            }
                            if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                                $v['color'] = '#000';
                            }
                            // 商品原价
                            if (isset($goods_info)) {
                                $image->text($goods_info['market_price'], '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                            }
                            break;
                        case 'marketprice':
                            if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                                $v['size'] = '14';
                            }
                            if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                                $v['color'] = '#000';
                            }
                            // 商品现价
                            if (isset($goods_info)) {
                                $image->text($goods_info['price'], '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                            }
                            break;
                        case 'ptime':
                            if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                                $v['size'] = '14';
                            }
                            if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                                $v['color'] = '#000';
                            }
                            // 结束时间
                            $image->text(date('Y-m-d H:i:s', $poster['end_task_time']), '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                            break;
                    }
                }
            }
            $image->save($image_path);
            return ['code' => 1, 'poster' => $image_path];
        } catch (\Exception $e) {
            recordErrorLog($e);
            echo $e->getMessage();exit;
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }

    /**
     * 生成指定大小的图片，如果图片本身符合大小返回处理之后的图片路径
     * @param string $image 原图
     * @param string $base_path 基础路径
     * @param int $width 设置宽度
     * @param int $height 设置高度
     * @return bool|string
     */
    public function imagePath($image, $base_path = '', $width = 0, $height = 0)
    {
        if ($image[0] === '/') {
            // /upload/17/avatar/xxxx.png -> upload/17/avatar/xxxx.png
            $image = substr($image, 1);
        }
        $down_load = false;
        if (stristr($image, 'http://') || stristr($image, 'https://')) {
            $down_load_img = $base_path . 'img_' . uniqid() . '.' . 'png';
            $this->downFile($image, $down_load_img);
            $image = $down_load_img;
            $file = new \SplFileInfo($image);
            $info = @getimagesize($file->getPathname());
            if($info['mime'] == 'image/x-ms-bmp'){
                $res = $this->changeBMPtoJPG($image);
                $image = $res;
                
            }
            $down_load = true;
        }
        $img_info = \think\Image::open($image);
        if (($width && $height) && ($img_info->width() != $width || $img_info->height() != $height)) {
            // 图片实际大小和设置的大小不一致
            if ($down_load) {
                // 删除下载的图片
                $this->unlinkImage($image);
            }
            $image = $base_path . 'img_' . uniqid() . '.' . $img_info->type();
            $img_info->thumb($width, $height, \think\Image::THUMB_FIXED)->save($image);
        }
        return $image;
    }

    function ImageCreateFromBMP_private($filename) {
        if (!$f1 = fopen($filename, "rb"))
            return false;

        $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
        if ($FILE['file_type'] != 19778)
            return false;

        $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
            '/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
            '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
        if ($BMP['size_bitmap'] == 0)
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - (4 * $BMP['decal']);
        if ($BMP['decal'] == 4)
            $BMP['decal'] = 0;

        $PALETTE = array();
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
        }

        $IMG = fread($f1, $BMP['size_bitmap']);
        $VIDE = chr(0);

        $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
        $P = 0;
        $Y = $BMP['height'] - 1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                switch ($BMP['bits_per_pixel']) {
                    case 32:
                        $COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
                        break;
                    case 24:
                        $COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
                        break;
                    case 16:
                        $COLOR = unpack("n", substr($IMG, $P, 2));
                        $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                        break;
                    case 8:
                        $COLOR = unpack("n", $VIDE . substr($IMG, $P, 1));
                        $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                        break;
                    case 4:
                        $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                        if (($P * 2) % 2 == 0)
                            $COLOR[1] = ($COLOR[1] >> 4);
                        else
                            $COLOR[1] = ($COLOR[1] & 0x0F);
                        $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                        break;
                    case 1:
                        $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                        if (($P * 8) % 8 == 0)
                            $COLOR[1] = $COLOR[1] >> 7;
                        elseif (($P * 8) % 8 == 1)
                            $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                        elseif (($P * 8) % 8 == 2)
                            $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                        elseif (($P * 8) % 8 == 3)
                            $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                        elseif (($P * 8) % 8 == 4)
                            $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                        elseif (($P * 8) % 8 == 5)
                            $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                        elseif (($P * 8) % 8 == 6)
                            $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                        elseif (($P * 8) % 8 == 7)
                            $COLOR[1] = ($COLOR[1] & 0x1);
                        $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                        break;
                    default:
                        return false;
                        break;
                }

                imagesetpixel($res, $X, $Y, $COLOR[1]);
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P+=$BMP['decal'];
        }
        fclose($f1);
        return $res;
    }

    function changeBMPtoJPG($srcPathName){
        $srcFile=$srcPathName;
        $dstFile = str_replace('.bmp', '.jpg', $srcPathName);
        $photoSize = GetImageSize($srcFile);
        $pw = $photoSize[0];
        $ph = $photoSize[1];
        $dstImage = ImageCreateTrueColor($pw, $ph);
        $white = imagecolorallocate($dstImage, 255, 255, 255);
        //用 $white 颜色填充图像
        imagefill( $dstImage, 0, 0, $white);
        //读取图片
        $srcImage = $this->ImageCreateFromBMP_private($srcFile);
        //合拼图片

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $pw, $ph, $pw, $ph);
        $judge = imagejpeg($dstImage, $dstFile, 90);
        imagedestroy($dstImage);
        if($judge){
            return $dstFile;
        }else{
            return false;
        }
    }


    /**
     * 下载海报中外部图片：微信头像
     * @param $file_url
     * @param $save_to
     */
    public function downFile($file_url, $save_to)
    {
        if (stristr($file_url, 'alicdn') !== false) {
            // 阿里云的图片用curl下载打不开
            file_put_contents($save_to, file_get_contents($file_url));
            return true;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_ENCODING, ""); //加速 这个地方留空就可以了
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $file = curl_exec($ch);
        curl_close($ch);
        $resource = fopen($save_to, 'w');
        fwrite($resource, $file);
        fclose($resource);
    }

    /**
     * 删除图片
     * @param string $image
     */
    public function unlinkImage($image)
    {
        $img_info = \think\Image::open($image);
        if (in_array($img_info->type(), ['jpg', 'png', 'jpeg', 'gif'])) {
            $bool = unlink($image);
        }
        return $bool;
    }

    /**
     * 绑定用户查看关注事件
     * @param int $uid
     * @param string $open_id 新用户的open_id
     * @param int $website_id
     */
    public function subEvent($uid, $open_id, $website_id)
    {
        $fans_model = new WeixinFansModel();
        $fan_info = $fans_model::get(['openid' => $open_id, 'website_id' => $website_id]);
        if (empty($fan_info)) {
            return ['code' => -1, 'message' => '空的信息'];
        }
        if (!empty($fan_info['source_uid']) && !empty($fan_info['scene']) && !empty($fan_info['scene_id'])) {
            switch ($fan_info['scene']) {
                case 'poster':
                    $poster_service = new \addons\poster\service\Poster();
                    $poster_service->successRecommend(['poster_id' => $fan_info['scene_id']], $fan_info['source_uid'], $uid);
                    break;
                case 'task_poster':
                    //关注是否领取海报任务
                    $weixin_fans_mdl = new WeixinFansModel();
                    $user_mdl = new UserModel();
                    $user_info = $user_mdl->getInfo(['wx_openid'=>$open_id,'website_id'=>$website_id],'uid');
                    if($user_info){
                        $uid = $user_info['uid'];
                        $weixin_fans_info = $weixin_fans_mdl->getInfo(['openid'=>$open_id, 'website_id'=>$website_id],'scene, scene_id');
                        if($weixin_fans_info['scene'] == 'task_poster'){
                            $task_center = new Taskcenter();
                            $task_center->getPosterTask($weixin_fans_info['scene_id'], $uid, $website_id);
                        }
                    }
                    $task_poster_service = new Taskcenter();
                    $task_poster_service->successRecommend(['general_poster_id' => $fan_info['scene_id']], $fan_info['source_uid'], $uid);
                    break;
                default:
                    return ['code' => -1, 'message' => '未定义的场景'];
            }
        }
    }
    /*
     * 可以发送get和post的请求方式
     * **/
    function curlRequest($url, $method, $headers=[], $params=''){
        if (is_array($params)) {
            $requestString = http_build_query($params);
        } else {
            $requestString = $params ? : '';
        }
        if (empty($headers)) {
            $headers = array('Content-type: text/json');
        } elseif (!is_array($headers)) {
            parse_str($headers,$headers);
        }
        // setting the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        // setting the POST FIELD to curl
        switch ($method){
            case "GET" : curl_setopt($ch, CURLOPT_HTTPGET, 1);break;
            case "POST": curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;
            case "PUT" : curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;
            case "DELETE":  curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;
        }
        // getting response from server
        $response = curl_exec($ch);

        //close the connection
        curl_close($ch);

        //return the response
        if (stristr($response, 'HTTP 404') || $response == '') {
            return array('Error' => '请求错误');
        }
        return $response;
    }
}