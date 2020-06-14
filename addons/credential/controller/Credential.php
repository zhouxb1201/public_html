<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:37
 */

namespace addons\credential\controller;
use addons\credential\model\CredBannerModel;
use addons\credential\model\CredentialModel;
use addons\credential\model\UserCredentialModel;
use addons\credential\service\Credential As CredService;
use addons\poster\Poster as basePoster;
use data\model\WebSiteModel;


class Credential extends basePoster
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new CredService;
    }
    /*
     * 弹框
     * **/
    public function credentialDialog()
    {
        $this->assign('credentialSystemDefaultTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/credentialSystemDefaultTemplate')));
        $this->fetch('/template/platform/credentialDialog');
    }
    /*
     * 获取弹框数据
     * **/
    public function credentialSystemDefaultTemplate()
    {

        return  json([
                0 => [ // 数组下标
                    'poster_id' => '',
                    'poster_name' => '空白模板',
                    'template_logo' => '/public/static/images/customPC/blankTemplate.png',
                ],
            ]);
    }
    
    /*
     * 添加/编辑证书数据
     * **/
    public function saveCredential()
    {
        $cred_service = new CredService();
        $credential_data = request()->post();
        $credential_data['website_id'] = $this->website_id;
        $credential_data['shop_id'] = $this->shop_id?:0;
        $credential_data['create_time'] = time();
        $credential_data['credential_data'] = json_encode($credential_data['credential_data'], JSON_UNESCAPED_UNICODE);
        $res = $cred_service->saveData($credential_data);
        return ajaxReturn($res);
    }
    /*
     * 获取证书数据
     * **/
    public function credentialList()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $cred_name = input('post.cred_name', '');
        $type = input('post.type');
        $condition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ];
        if ($cred_name) {
            $condition['cred_name'] = ['like', "%" . $cred_name . "%"];
        }
        if ($type) {
            $condition['cred_type'] = $type;
        }
        $order = 'cred_id DESC';
        $list = $this->service->credList($page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 设为默认
     * **/
    public function defaultCred()
    {
        $cred_id = request()->post('cred_id', 0);
        $cred_type = request()->post('type', 0);
        if($cred_id && $cred_type){
            //将所有的该类型证书都设为非默认
            $cred_mdl = new CredentialModel();
            $cred_mdl->save(['is_default' => 0], ['cred_type' => $cred_type, 'website_id' => $this->website_id]);
            $up_data['is_default'] = 1;
            $bool = $cred_mdl->save($up_data, ['cred_id' => $cred_id]);
            return ajaxReturn($bool);
        }else{
            return ajaxReturn(-1);
        }
    }
    /*
     * 得到编辑渲染的数据
     * **/
    public function getCredInfo()
    {
        $cred_id = request()->post('cred_id', 0);
        $cred_mdl = new CredentialModel();
        $cred_info = $cred_mdl->getInfo(['cred_id' => $cred_id]);
        return json($cred_info);
    }
    /*
     * 删除证书
     * **/
    public function deleteCred()
    {
        $cred_id = request()->post('cred_id', 0);
        $cred_mdl = new CredentialModel();
        $bool = $cred_mdl->destroy(['cred_id' => $cred_id]);
        return ajaxReturn($bool);
    }
    /*
     * 获取用户有没有设置微信号
     * **/
    public function getUserWchat()
    {
        $uid = $this->uid;
        if(!$uid){
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $user_cre = new UserCredentialModel();
        $wchat_info = $user_cre->getInfo(['uid' => $uid], 'wchat_name');
        if($wchat_info){
            return ['code' => 1, 'wchat_name' => $wchat_info['wchat_name']];
        }else{
            return ['code' => 0];
        }
    }
    /*
     * 获取用户的授权证书
     * **/
    public function getUserCredential()
    {
        try{
            $type = request()->post('type', 0);
            $role_type = request()->post('role_type', 0);
            $wchat_name = request()->post('wchat_name', 0);
//            debugFile(request()->post(), '打印请求参数', 'public/weidian_cred.txt');
            if(!$type || !$wchat_name){
                return AjaxReturn(LACK_OF_PARAMETER);
            }
            switch($type){
                case 1:
                    $cred_type = 1;
                    break;
                case 2:
                    if($role_type == 1){//团队队长 为2
                        $cred_type = 2;
                    }elseif($role_type == 2){//区域分红 为3
                        $cred_type = 3;
                    }else{//全球股东 为4
                        $cred_type = 4;
                    }
                    break;
                case 3://传过来的是3 处理成对应数据库保存值 为5
                    $cred_type = 5;
                    break;
                case 4:
                    $cred_type = 6;
                    break;
            }
            $uid = $this->uid;
            if(!$uid){
                return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
            }
            //获取该类型默认的证书
            $cred_mdl = new CredentialModel();
            $user_cred = new UserCredentialModel();
            $cred_info = $cred_mdl->getInfo(['cred_type' => $cred_type, 'website_id' => $this->website_id, 'is_default' => 1]);
//            debugFile(request()->post(), '打印请求参数2', 'public/weidian_cred.txt');
            if(!$cred_info){
                return json(['code' => -1, 'message' => '该类型证书数据不存在']);
            }
//            debugFile(request()->post(), '打印请求参数3', 'public/weidian_cred.txt');
            $img_info = $this->service->getCredImg($uid, $wchat_name, $cred_info);
            $img_path = $img_info['img_path'];
            $cred_no = $user_cred->getInfo(['uid' => $uid, 'cred_id' => $cred_info['cred_id']])['cred_no'];
            if($img_path != -1){
                return json([
                    'code' => 0,
                    'message' => '获取成功',
                    'data' => [
                        'img_path' => $img_path,
                        'cred_no' => $cred_no,
                    ]
                ]);
            }else{
                return json([
                    'code' => -1,
                    'message' => '获取失败',
                ]);
            }
        }catch(\Exception $e){
//            debugFile(request()->post(), '打印请求参数4', 'public/weidian_cred.txt');
        }
    }
    /*
     * 清除证书缓存
     * **/
    public function deleteCredCache()
    {
        $key = 'credential' . $this->website_id . '_*';
        $redis = $this->connectRedis();
        $keys = $redis->keys($key);
        foreach($keys as $v){
            $redis->del( $v );
        }
        //删除目录下的图片
        $cred_path = getcwd() . DS . 'upload' . DS . $this->website_id . DS . 'credential' .DS;
        if(is_dir($cred_path)){
            /*//linux环境
        $exec_cli = 'rm -rf '.$cred_path;
        exec($exec_cli);*/
            $bool = $this->deleteDirFile($cred_path);
        }
        return ajaxReturn(1);

    }
    /*
     * 封装一个删除指定目录下的目录及其文件
     * **/
    public function deleteDirFile($dir)
    {
        if ($handle = opendir($dir)){
            while(false !== ($item = readdir($handle))){
                if($item != '.' && $item != '..'){
                    $dir = str_replace('\\', '/', $dir );
                    $dir = '/' . trim($dir, '/') . '/';
                    if(is_dir($dir.$item)){
                        $this->deleteDirFile($dir.$item);
//                        if(count(scandir($dir.$item))==2){ // 目录为空,=2是因为. 和 ..存在
//                            rmdir($dir.$item);             // 删除空目录
//                        }
                    }else{
                        unlink($dir.$item);
                    }
                }
            }
            closedir($handle);
            return 1;
        }
    }
    /*
     * 查询授权证书
     * **/
    public function searchUserCredential()
    {
        $cred_no = request()->post('cred_no', '');
        if(!$cred_no){
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition['uc.cred_no'] = $cred_no;
        $ucred_data = $this->service->getUserCredential($condition);
        return $ucred_data;
    }
    /*
     * 保存查询页面的banner图
     * **/
    public function saveBanner()
    {
        try{
            $banner_data = request()->post('banner_data/a', '');
            if($banner_data){
                $banner= [];
                $cred_ban_id = $banner_data['cred_ban_id'];
                $banner['img_path'] = $banner_data['img']?:'';
                $banner['img_link'] = $banner_data['link']?:'';
                $banner['website_id'] = $this->website_id;
                $cred_banner = new CredBannerModel();
                if(!$cred_ban_id){
                    $banner['create_time'] = time();
                    $cred_banner->save($banner);
                }else{
                    $banner['update_time'] = time();
                    $cred_banner->save($banner, ['cred_ban_id'=>$cred_ban_id]);
                }
            }
            return ajaxReturn(1);
        }catch(\Exception $e){
            return ajaxReturn(-1);
        }
    }
    /*
     * 删除轮播图
     * **/
    public function deleteCredBanner()
    {
        $cred_ban_id = request()->post('cred_ban_id', 0);
        $cred_banmdl = new CredBannerModel();
        $cred_banmdl->destroy(['cred_ban_id' => $cred_ban_id]);
    }
    /*
     * 查询授权证书页面
     * **/
    public function searchUserCredentialPage()
    {
        //获取轮播图
        $cred_banmdl = new CredBannerModel();
        $banner_list = $cred_banmdl->getInfo('','img_path, img_link');
        //获取logo
        $website = new WebSiteModel();
        $logo = $website->getInfo(['website_id' => $this->website_id])['logo'];
        $banner_page['banner_list'] = $banner_list;
        $banner_page['logo'] = $logo;
        return json(['code' => 0, 'message' => '获取成功', 'data' => $banner_page]);
    }
}