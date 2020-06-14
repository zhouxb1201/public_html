<?php
namespace addons\qlkefu\controller;

use addons\qlkefu\Qlkefu as baseQlkefu;
use addons\qlkefu\server\Qlkefu as QlkefuServer;
use data\model\AddonsConfigModel;
use data\model\VslGoodsModel;

class Qlkefu extends baseQlkefu
{
    public function __construct()
    {
        parent::__construct();

    }
    /**
     * 客服系统设置
     */
    public function saveSetting()
    {
        $qlkefu_server = new QlkefuServer();
        $is_use = input('post.is_use',0);
        $seller_code = input('post.seller_code');
        if(!$seller_code){
            return ['code' => -1,'message' => '客服标识不能为空'];
        }
        $info = $qlkefu_server->getQlkefuDetail(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id]);
        $input = [];
        $input['is_use'] = $is_use;
        $input['seller_code'] = $seller_code;
        $input['shop_id'] = $this->instance_id;
        $input['website_id'] = $this->website_id;
        if(empty($info)){
            $res = $qlkefu_server->addQlkefu($input);
        }else{
            $res = $qlkefu_server->updateQlkefu($input,['qlkefu_id'=>$info['qlkefu_id']]);
        }
        if($res){
            $addonsconfig = new AddonsConfigModel();
            $is_use = $addonsconfig->getInfo(['addons' => 'qlkefu','website_id' => $this->website_id], 'is_use');
            if($is_use['is_use']!=1){
                //默认开启
                $qlkefu_server->saveConfig(1);
                setAddons('qlkefu', $this->website_id, 0);
            }
            $this->addUserLog((empty($info)?'添加':'编辑').'客服', $res);
        }
        return AjaxReturn($res);
    }
    
    /**
     * 添加客服标识
     */
    public function addQlkefu()
    {
        $qlkefu_server = new QlkefuServer();
        $seller_code = input('post.seller_code');
        if(!$seller_code){
            return ['code' => -1,'message' => '客服标识不能为空'];
        }
        $verify = $qlkefu_server->verifyQlkefu($seller_code);
        if(!$verify)return ['code' => -1,'message' => '操作失败，无法验证'];
        if($verify['code']==-1)return $verify;
        $info = $qlkefu_server->getQlkefuDetail(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id]);
        $input = [];
        $input['is_use'] = 1;
        $input['seller_code'] = $seller_code;
        $input['shop_id'] = $this->instance_id;
        $input['website_id'] = $this->website_id;
        if(empty($info)){
            $res = $qlkefu_server->addQlkefu($input);
        }else{
            $res = $qlkefu_server->updateQlkefu($input,['qlkefu_id'=>$info['qlkefu_id']]);
        }
        if($res){
            $addonsconfig = new AddonsConfigModel();
            $is_use = $addonsconfig->getInfo(['addons' => 'qlkefu','website_id' => $this->website_id], 'is_use');
            if($is_use['is_use']!=1){
                //默认开启
                $qlkefu_server->saveConfig(1);
                setAddons('qlkefu', $this->website_id, 0);
            }
            $this->addUserLog((empty($info)?'添加':'编辑').'客服', $res);
        }
        return AjaxReturn($res);
    }
    
    /**
     * 客服消息列表
     */
    public function chatList()
    {
        $uid = $this->uid;
        if (empty($uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $qlkefu_server = new QlkefuServer();
        $qlkefu_list =  $qlkefu_server->getQlkefuList(['is_use'=>1],'seller_code');
        $seller_codes = '';
        if(!empty($qlkefu_list)){
            foreach($qlkefu_list as $k => $v){
                $seller_codes .= $v['seller_code'].',';
            }
        }else{
            return json(['code' => 1,'data' => []]);
        }
        $res = $qlkefu_server->chatList($uid,$seller_codes);
        if(!empty($res['data'])){
            return json(['code' => 1,'data' => $res['data']]);
        }else{
            return json(['code' => 1,'data' => []]);
        }
    }
    
    /**
     * 客服信息
     */
    public function qlkefuInfo()
    {
        $shops_id = input('post.shop_id');
        $goods_id = input('post.goods_id');
        if($shops_id=='' && $goods_id==''){
            $shop_id = -1;
        }else{
            $goods_model = new VslGoodsModel();
            $goods_info = $goods_model->getInfo(['goods_id' => ''], 'shop_id, website_id');
            if($goods_info){
                $website_id = $goods_info['website_id'];
                $shop_id = $goods_info['shop_id'];
            }else{
                $website_id = $this->website_id;
                $shop_id = ($shops_id>0)?$shops_id:0;
            }
        }
        $data = [];
        $qlkefu_server = new QlkefuServer();
        $qlkefu = $qlkefu_server->qlkefuConfig($website_id,$shop_id);
        if(!empty($qlkefu['ql_domain'])){
            $data['domain'] = $qlkefu['ql_domain'];
            $data['port'] = $qlkefu['ql_port'];
        }else{
            $data['domain'] = '';
            $data['port'] = '';
        }
        if($qlkefu['is_use']==1){
            $data['is_use'] = 1;
            $data['seller_code'] = $qlkefu['seller_code'];
        }else{
            if($shop_id!=-1){
                $data['is_use'] = 0;
                $data['seller_code'] = '';
            }
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => $data]);
    }
}