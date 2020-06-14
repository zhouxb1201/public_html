<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:39
 */

namespace addons\credential\service;
use addons\bonus\model\VslAgentLevelModel;
use addons\channel\model\VslChannelModel;
use addons\credential\model\CredentialModel;
use addons\credential\model\UserCredentialModel;
use addons\distribution\model\VslDistributorLevelModel;
use addons\microshop\model\VslMicroShopLevelModel;
use data\model\AddonsConfigModel;
use data\model\UserModel;
use data\model\VslMemberLevelModel;
use data\model\VslMemberViewModel;
use data\model\WebSiteModel;
use data\service\BaseService;
use think\Config;
use think\Db;


class Credential extends BaseService
{

    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }
    /*
     * 保存数据
     * **/
    public function saveData($cred_data)
    {
        try{
            Db::startTrans();
            $cred_mdl = new CredentialModel();
            if($cred_data['is_default'] == 1){
                //将该平台下的其它相同类型的默认取消
                $cred_cond['cred_type'] = $cred_data['cred_type'];
                $cred_cond['website_id'] = $this->website_id;
                $cred_mdl->where($cred_cond)->update(['is_default' => 0]);
            }
            if($cred_data['cred_id']){//编辑
                $bool = $cred_mdl->save($cred_data, ['cred_id'=>$cred_data['cred_id']]);
            }else{//插入
                $bool = $cred_mdl->save($cred_data);
            }
            Db::commit();
            if($bool){
                return 1;
            }else{
                return -1;
            }
        }catch(\Exception $e){
            Db::rollback();
            return -1;
//            return $e->getMessage();
        }
    }

    public function credList($page_index, $page_size, $condition, $order, $field = '*')
    {
        $cred_mdl = new CredentialModel();
        $list = $cred_mdl->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }
    /*
     * 获取证书图片
     * **/
    public function getCredImg($uid, $wchat_name, $cred_info)
    {
        try{
            $redis = $this->connectRedis();
            $user_model = new UserModel();
            $user_info = $user_model::get(['user_model.website_id' => $this->website_id, 'sys_user.uid' => $uid, 'is_member' => 1], ['member_info']);
            $user_info['nick_name'] = $user_info['nick_name'] ?: $user_info['user_name'];
            $user_info['user_headimg'] = $user_info['user_headimg'] ?: 'public/static/images/headimg.png';//使用默认头像
            $default_cred = Config::get('credential');
            $cred_path = 'upload' . DS . $this->website_id . DS . 'credential' .DS. $cred_info['cred_type'] . DS . $user_info['uid'] . DS;
            $website_model = new WebSiteModel();
            $website_info = $website_model::get(['website_id' => $this->website_id]);
            $is_ssl = \think\Request::instance()->isSsl();
            $protocol = $is_ssl ? "https://" : "http://";
            if ($website_info['realm_ip']) {
                $domain_name = $protocol . $website_info['realm_ip'];
            } else {
                $ip = top_domain($_SERVER['HTTP_HOST']);
                $domain_name = $protocol . top_domain($website_info['realm_two_ip']) .'.'. $ip;
            }
            $user_cred = new UserCredentialModel();
            $uc_info = $user_cred->getInfo(['uid' => $uid, 'cred_id' => $cred_info['cred_id'], 'website_id' => $this->website_id]);
            $ucred_no = $uc_info['cred_no'];
            //先判断是否存在缓存
            $key = $uc_info['cred_no'].'_'.$uid;
            $image_path = $redis->get($key);
            if($image_path){
                $cred_img = $cred_path . $cred_info['cred_id'] . '.' . $default_cred['default_ext'];
                if (is_file($cred_img)) {
                    return ['img_path' => $domain_name.'/'.$image_path];
                }
            }
            $base_service = new BaseService();
            $member = new VslMemberViewModel();
            //获取设置的图片内容
            $credential_data = $cred_info['credential_data'];
            $data_arr = json_decode($credential_data, true);
            if(!is_dir($cred_path)){
                mkdir($cred_path, 0777, true);
            }
            $is_user_cred = $user_cred->getInfo(['cred_no' => $ucred_no, 'website_id' => $this->website_id]);
            if(!$is_user_cred){
                //获取证书编号
                $cred_type = $cred_info['cred_type'];
                $cred_no = date('md'). '0'. $cred_type . rand(100000, 999999);
                while(true){
                    $is_user_cred = $user_cred->getInfo(['cred_no' => $cred_no, 'website_id' => $this->website_id]);
                    if(!$is_user_cred){
                        break;
                    }else{
                        $cred_no = date('md'). '0'. $cred_type . rand(100000, 999999);
                    }
                }
            }else{
                $cred_no = $ucred_no;
            }
            //获取二维码链接
            $qr_url = $domain_name.'/wap/credential/result?cred_no='.$cred_no;
            if(!empty($data_arr['bg'])){
                $bg = $base_service->imagePath($data_arr['bg'], $cred_path);
                $image = \think\Image::open($bg);
                $scale = $image->width() / $image->height();// 背景图比例
                $width_scale = $default_cred['default_width'] / $default_cred['default_height'];// 默认海报大小的比例
                if ($image->width() != $default_cred['default_width'] || $image->height() != $default_cred['default_height']) {
                    // 如果背景图的长宽比例和海报设置的比例差别大，使用白板作为基础图片,背景作为图片水印
                    // 带鱼图片(($scale > $width_scale + 0.1),缩小图片为水印图;长图片(($scale < $width_scale - 0.1),仅使用图片头部
                    $new_bg = $cred_path . 'water_' . uniqid() . '.' . $image->type();
                    $new_width = $default_cred['default_width'];
                    $new_height = ($scale > ($width_scale + 0.1)) ? $image->height() * 318 / $image->width() : $default_cred['default_height'];
                    $image->thumb($new_width, $new_height, $image::THUMB_SCALING, false)->save($new_bg);
                    unset($image);
                    $image = \think\Image::open($default_cred['default_bg']);
                    $image->water($new_bg, [0, 0]);
                    $this->unlinkImage($new_bg);
                }
                $this->unlinkImage($bg);
            }else{
                $image = \think\Image::open($default_cred['default_bg']);
            }
            //组合图片各个部分
            $member_info = $member->field('distributor_level_id, team_agent_level_id, global_agent_level_id, area_agent_level_id, microshop_level_id')
                ->where(['uid' => $uid])
                ->find();
            if($cred_info['cred_type'] == 1){//分销商
                $distri_level = new VslDistributorLevelModel();
                $level_name = $distri_level->getInfo(['id' => $member_info['distributor_level_id']], 'level_name')['level_name'];
            }elseif($cred_info['cred_type'] == 2){//团队队长
                $agent_level = new VslAgentLevelModel();
                $level_name = $agent_level->getInfo(['id' => $member_info['team_agent_level_id']], 'level_name')['level_name'];
            }elseif($cred_info['cred_type'] == 3){//区域代理
                $agent_level = new VslAgentLevelModel();
                $level_name = $agent_level->getInfo(['id' => $member_info['area_agent_level_id']], 'level_name')['level_name'];
            }elseif($cred_info['cred_type'] == 4){//全球股东
                $agent_level = new VslAgentLevelModel();
                $level_name = $agent_level->getInfo(['id' => $member_info['global_agent_level_id']], 'level_name')['level_name'];
            }elseif($cred_info['cred_type'] == 5){//渠道商
                $channel = new VslChannelModel();
                $channel_info = $channel->alias('c')->field('channel_grade_name')->join('vsl_channel_level cl', 'c.channel_grade_id = cl.channel_grade_id', 'left')->find();
                $level_name = $channel_info['channel_grade_name'];
            }elseif($cred_info['cred_type'] == 6){//微店
                $micro_level = new VslMicroShopLevelModel();
                $level_name = $micro_level->getInfo(['id' => $member_info['microshop_level_id']], 'level_name')['level_name'];
            }
            $level_name = $level_name?:'暂无等级';

            foreach($data_arr['items'] as $k=>$v){
                $from_left = intval($v['from_left']) * 2;
                $from_top = intval($v['from_top']) * 2;
                $br_left = $default_cred['default_width'] - $from_left;
                $v['width']  = intval($v['width']) * 2;
                $v['height'] = intval($v['height']) * 2;
                $v['size'] = intval($v['size']) * 2;
                if($v['color'] == 'undefined'){
                    $v['color'] = '#000';
                }
                switch($v['type']){
                    case 'words':
                        $content = $v['string'];
                        if(strpos($content, '昵称')){
                            $content = str_replace('昵称', $user_info['nick_name'], $content);
                        }
                        if(strpos($content, '微信号')){
                            $content = str_replace('微信号', $wchat_name, $content);
                        }
                        if(strpos($content, '等级')){
                            $content = str_replace('等级', $level_name, $content);
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
                    case 'img':
                        $img = $base_service->imagePath($v['img'], $cred_path, $v['width'], $v['height']);
                        $image->water($img, [$from_left, $from_top]);
                        $base_service->unlinkImage($img);
                        break;
                    case 'qr':
                        $qr_path = getQRcode($qr_url, $cred_path, 'poster_qr');
                        $img = $base_service->imagePath($qr_path, $cred_path, $v['width'], $v['height']);
                        $image->water($img, [$from_left, $from_top]);
                        $base_service->unlinkImage($img);
                        break;
                    case 'head':
                        if($from_left == 'auto' && $from_top == 'auto'){
                            $from_left = 0;
                            $from_top = 0;
                        }
                        $img = $base_service->imagePath($user_info['user_headimg'], $cred_path, $v['width'], $v['height']);
                        $image->water($img, [$from_left, $from_top]);
                        $base_service->unlinkImage($img);
                        break;
                    case 'nickname':
                        if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                            $v['size'] = '14';
                        }
                        if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                            $v['color'] = '#000';
                        }
                        $image->text($user_info['nick_name'], '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                        break;
                    case 'wx':
                        if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                            $v['size'] = '14';
                        }
                        if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                            $v['color'] = '#000';
                        }
                        $image->text($wchat_name, '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                        break;
                    case 'level':
                        if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                            $v['size'] = '14';
                        }
                        if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                            $v['color'] = '#000';
                        }
                        $image->text($level_name, '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                        break;
                    case 'phone':
                        if( !isset($v['size']) || $v['size'] == '' || $v['size'] == 'undefined'){
                            $v['size'] = '14';
                        }
                        if( !isset($v['color']) || $v['color'] == '' || $v['color'] == 'undefined'){
                            $v['color'] = '#000';
                        }
                        $image->text($user_info['user_tel'], '', $v['size'], $v['color'], [$from_left, $from_top], 0, 0, $br_left);
                        break;
                }
            }
            $image_path = $cred_path . $cred_info['cred_id'] . '.' . $default_cred['default_ext'];
            $bool = $image->save($image_path);
            if($bool){
                //将图片存入缓存中 website_id-cred_id-uid
                $redis->set($key, $image_path);
                //将用户信息插入用户证书表
                $user_cred_info['uid'] = $uid;
                $user_cred_info['cred_id'] = $cred_info['cred_id'];
                $user_cred_info['cred_no'] = $cred_no;
                $user_cred_info['wchat_name'] = $wchat_name;
                $user_cred_info['nickname'] = $user_info['nick_name'];
                $user_cred_info['user_tel'] = $user_info['user_tel'];
                $user_cred_info['website_id'] = $this->website_id;
                $user_cred_info['shop_id'] = $this->instance_id;
                $user_cred_info['create_time'] = time();
                $ucred_info = $user_cred->getInfo(['uid' => $uid, 'cred_id'=>$cred_info['cred_id'], 'website_id' => $this->website_id]);

                if(!$ucred_info){
                    $user_cred->save($user_cred_info);
                }
            }
            return ['img_path' => $domain_name.'/'.$image_path, 'cred_no' => $cred_no];
        }catch(\Exception $e){
            echo $e->getMessage();exit;
            return -1;
        }
    }
    /*
     * 获取用户证书图片信息
     * **/
    public function getUserCredential($condition)
    {
        $cred_mdl = new CredentialModel();
        $user_cred_info = $cred_mdl->alias('c')->join('vsl_user_credential uc', 'c.cred_id = uc.cred_id', 'LEFT')->where($condition)->find();
//        var_dump($user_cred_info);
//        echo $cred_mdl->getLastSql();exit;
        if(!$user_cred_info){
            return ['code' => -1, 'message' => '授权证书编号错误，请核对后重试！'];
        }
        //获取授权人，取商城名称
        $website = new WebSiteModel();
        $redis = $this->connectRedis();
        $website_info = $website->getInfo(['website_id' => $this->website_id]);
        $user_cred_info['mall_name'] = $website_info['mall_name'];
        //授权时间
        $user_cred_info['create_date'] = date('Y-m-d H:i:s', $user_cred_info['create_time']);
        //证书图片
//        $key = $condition['uc.cred_no'];
        $key = $condition['uc.cred_no'].'_'.$user_cred_info['uid'];
        $image_path = $redis->get($key);
        if(!$image_path){
            $image_path = $this->getCredImg($user_cred_info['uid'], $user_cred_info['wchat_name'], $user_cred_info);
        }
        //处理当前返回的证书类型 /证书类型 1-分销商 2-团队队长 3-区域代理 4-全球股东 5-渠道商 6-微店
        switch($user_cred_info['cred_type']){
            case '1':
                $user_cred_info['cred_type'] = '分销商授权证书';
                break;
            case '2':
                $user_cred_info['cred_type'] = '团队队长授权证书';
                break;
            case '3':
                $user_cred_info['cred_type'] = '区域代理授权证书';
                break;
            case '4':
                $user_cred_info['cred_type'] = '全球股东授权证书';
                break;
            case '5':
                $user_cred_info['cred_type'] = '渠道商授权证书';
                break;
            case '6':
                $user_cred_info['cred_type'] = '微店授权证书';
                break;
        }
        $website_model = new WebSiteModel();
        $website_info = $website_model::get(['website_id' => $this->website_id]);
        $is_ssl = \think\Request::instance()->isSsl();
        $protocol = $is_ssl ? "https://" : "http://";
        if ($website_info['realm_ip']) {
            $domain_name = $protocol . $website_info['realm_ip'];
        } else {
            $ip = top_domain($_SERVER['HTTP_HOST']);
            $domain_name = $protocol . top_domain($website_info['realm_two_ip']) .'.'. $ip;
        }
        $user_cred_info['image_path'] = $domain_name. '/'. $image_path;
        unset($user_cred_info['credential_data']);
        return [
            'code'=>0,
            'message' => '获取成功',
            'data' => $user_cred_info
        ];

    }
}