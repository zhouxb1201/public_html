<?php
namespace addons\giftvoucher\controller;

use addons\giftvoucher\Giftvoucher as baseGiftvoucher;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use addons\gift\model\VslPromotionGiftModel;
use addons\miniprogram\model\WeixinAuthModel;
use addons\shop\model\VslShopModel;
use addons\store\server\Store as storeServer;
use data\model\WebSiteModel;
use data\service\AddonsConfig;

class Giftvoucher extends baseGiftvoucher
{
    public function __construct()
    {
        parent::__construct();

    }
    public function giftvoucherList()
    {
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $search_text = input('post.search_text','');
        $end_receive_time = request()->post('end_receive_time');

        $VoucherServer = new VoucherServer();
        if ($search_text) {
            $condition['gv.giftvoucher_name'] = ['LIKE', '%' . $search_text . '%'];
        }
        $condition['gv.website_id'] = $this->website_id;
        $condition['gv.shop_id'] = $this->instance_id;
        if ($end_receive_time) {
            $condition['end_receive_time'] = ['GT', time()];
        }
        if (input('post.not_expired')) {
            $condition['gv.end_time'] = ['GT', time()];
        }
        if (input('post.excepted_gift_voucher_id/a')) {
            $condition['gift_voucher_id'] = ['NOT IN', input('post.excepted_gift_voucher_id/a')];
        }
        //判断pc端、小程序是否开启
        $addons_conf = new AddonsConfig();
        $pc_conf = $addons_conf->getAddonsConfig('pcport', $this->website_id);
        $is_minipro = getAddons('miniprogram', $this->website_id);
        if($is_minipro){
            $weixin_auth = new WeixinAuthModel();
            $new_auth_state = $weixin_auth->getInfo(['website_id' => $this->website_id], 'new_auth_state')['new_auth_state'];
            if(isset($new_auth_state) && $new_auth_state === 0){
                $is_minipro = 1;
            }else{
                $is_minipro = 0;
            }
        }
        $is_giftvoucher = getAddons('giftvoucher', $this->website_id);
        //查看移动端的状态 by sgw
        $website_mdl = new WebSiteModel();
        $wap_status = $website_mdl->getInfo(['website_id' => $this->website_id], 'wap_status')['wap_status'];
        // app商城状态
        $is_app = getAddons('appshop', $this->website_id);
        if ($is_app) {
            $app_conf = $addons_conf->getAddonsConfig('appshop', $this->website_id);
        }

        $addon_status['wap_status'] = $wap_status;
        $addon_status['is_pc_use'] = $pc_conf['is_use'];
        $addon_status['is_minipro'] = $is_minipro;
        $addon_status['is_giftvoucher'] = $is_giftvoucher;
        $addon_status['is_appshop'] = $app_conf['is_use'];
        $list = $VoucherServer->getGiftVoucherList($page_index, $page_size, $condition, 'create_time desc');
        $list['addon_status'] = $addon_status;
        return $list;
    }
    /**
     * 赠品选择
     */
    public function modalGiftList()
    {
        if (request()->post('page_index')) {
            $page_index = input('post.page_index',1);
            $page_size = input('post.page_size',PAGESIZE);
            $search_text = input('post.search_text','');
            if ($search_text) {
                $condition['vpg.gift_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['vpg.website_id'] = $this->website_id;
            $condition['vpg.shop_id'] = $this->instance_id;
            $condition['vpg.stock'] = ['gt','sended'];
            $giftModel = new VslPromotionGiftModel();
            $list = $giftModel->getGiftViewList($page_index, $page_size, $condition, 'vpg.create_time desc');
            $list['addon_status']['is_gift'] = getAddons('gift', $this->website_id,$this->instance_id);
            if($list['data']){
                foreach($list['data'] as $k => $v){
                    $list['data'][$k]['pic_cover_big'] = __IMG($v['pic_cover_big']);
                    $list['data'][$k]['pic_cover_mid'] = __IMG($v['pic_cover_mid']);
                }
            }
            return $list;
        }
        header("Content-type: text/html; charset=utf-8");
        $this->fetch('template/' . $this->module . '/giftlistDialog');
    }
    
    public function addGiftvoucher()
    {
        $input = input('post.');
        $input['start_receive_time'] = strtotime(input('post.start_receive_time'));
        $input['end_receive_time'] = strtotime(input('post.end_receive_time')) + (86400 - 1);
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['end_time'] = strtotime(input('post.end_time')) + (86400 - 1);
        $input['create_time'] = time();
        $input['shop_id'] = $this->instance_id;
        $input['website_id'] = $this->website_id;
        $VoucherServer = new VoucherServer();
        $ret_val = $VoucherServer->addGiftVoucher($input);
        if($ret_val){
            $this->addUserLog('添加礼品券', $ret_val);
        }

        return AjaxReturn($ret_val);
    }
    public function updateGiftvoucher()
    {
        $input = input('post.');
        $input['start_receive_time'] = strtotime(input('post.start_receive_time'));
        $input['end_receive_time'] = strtotime(input('post.end_receive_time')) + (86400 - 1);
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['end_time'] = strtotime(input('post.end_time')) + (86400 - 1);
        $input['update_time'] = time();
        $where['gift_voucher_id'] = input('post.gift_voucher_id');
        $where['shop_id'] = $this->instance_id;
        $VoucherServer = new VoucherServer();
        $ret_val = $VoucherServer->updateGiftVoucher($input,$where);
        if($ret_val){
            $this->addUserLog('修改礼品券', $ret_val);
        }
        return AjaxReturn($ret_val);
    }
    public function deleteGiftvoucher()
    {
        $gift_voucher_id = (int)input('post.gift_voucher_id');
        $condition['vgvr.gift_voucher_id'] = $gift_voucher_id;
        $condition['vgvr.state'] = 1;
        $condition['vgvr.shop_id'] = $this->instance_id;
        $condition['vgvr.website_id'] = $this->website_id;
        $condition['vgv.end_time'] = ['>',time()];
        if (empty($gift_voucher_id)) {
            return ['code' => -1,'message' => '没有获取到礼品券信息'];
        }
        $VoucherServer = new VoucherServer();
        $res = $VoucherServer->deleteGiftVoucher($condition);
        if($res){
            $this->addUserLog('删除礼品券', $gift_voucher_id);
        }
        return AjaxReturn($res);
    }
    public function historyGiftvoucher()
    {
        $VoucherServer = new VoucherServer();
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $search_text = input('post.search_text','');
        $where['vgvr.state'] = 2;
        $where['vgvr.gift_voucher_id'] = (int)input('get.gift_voucher_id');
        $where['vgvr.website_id'] = $this->website_id;
        $where['vgvr.shop_id'] = $this->instance_id;
        $fields = 'vgvr.*,su.user_tel,su.nick_name,vs.shop_name,vpg.gift_name,vpg.price';
        if ($search_text) {
            $where['vs.shop_name|su.nick_name'] = ['like', '%' . $search_text . '%'];
        }
        $list = $VoucherServer->getGiftVoucherHistory($page_index, $page_size, $where, $fields,'vgvr.use_time desc');
        return $list;
    }

    public function saveSetting()
    {
        $VoucherServer = new VoucherServer();
        $is_giftvoucher = (int)input('post.is_giftvoucher');

        $result = $VoucherServer->saveConfig($is_giftvoucher);
        if($result){
            $this->addUserLog('添加礼品券设置', $result);
        }
        setAddons('giftvoucher', $this->website_id, $this->instance_id);
        return AjaxReturn($result);
    }
    
    /**
     * 领取礼品券
     */
    public function giftvoucherReceive()
    {
        if (empty($this->uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $gift_voucher_id = (int)input('gift_voucher_id');
        $get_type = input('get_type',1);
        $VoucherServer = new VoucherServer();
        $condition['gift_voucher_id'] = $gift_voucher_id;
        $condition['website_id'] = $this->website_id;
        $isgiftvoucher = $VoucherServer->isGiftVoucherReceive($condition);
        $result = 0;
        if($isgiftvoucher>0){
            $result = $VoucherServer->getUserReceive($this->uid,$gift_voucher_id,$get_type);
            if($result){
                $this->addUserLog('领取礼品券', $result);
            }
        }
        if($result>0){
            $data['code'] = 1;
            $data['message'] = "领取成功";
        }else{
            $data['code'] = -1;
            $data['message'] = "领取失败";
        }
        return json($data);
    }
    /**
     * wab用户礼品券
     */
    public function userGiftvoucher()
    {
        $page_index = input('page_index',1);
        $page_size = input('page_size',PAGESIZE);
        $state = input('state',1);
        $VoucherServer = new VoucherServer();
        $list = $VoucherServer->getUserGiftVoucher($state,$page_index, $page_size);
        $rs = [
            'code' => 1,
            'message' => '获取成功',
            'data' => $list
        ];
        return json($rs);
    }
    /**
     * wab用户礼品券详情
     */
    public function userGiftvoucherInfo()
    {
        $record_id = (int)input('record_id');
        $VoucherServer = new VoucherServer();
        $info = $VoucherServer->getUserGiftvoucherInfo($record_id,'');
        if($info){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $info;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * wab礼品券详情
     */
    public function giftvoucherDetail()
    {
        $gift_voucher_id = (int)input('gift_voucher_id');
        $VoucherServer = new VoucherServer();
        $voucher_info = $VoucherServer->getGiftVoucherDetail(['gift_voucher_id'=>$gift_voucher_id]);
        $data = $info = [];
        if($voucher_info){
            $info['is_giftvoucher'] = $VoucherServer->isGiftVoucherReceive(['gift_voucher_id'=>$gift_voucher_id,'website_id'=>$voucher_info['website_id']]);
            $info['gift_voucher_id'] = $voucher_info['gift_voucher_id'];
            $info['giftvoucher_name'] = $voucher_info['giftvoucher_name'];
            $info['pic_cover_big'] = $voucher_info['gift']['pic_cover_big'];
            $info['pic_cover_mid'] = $voucher_info['gift']['pic_cover_mid'];
            $info['shop_id'] = $voucher_info['shop_id'];
            $shop_model = new VslShopModel();
            $shop_info = $shop_model->getInfo(['shop_id' => $voucher_info['shop_id'],'website_id'=>$this->website_id], 'shop_name,shop_logo');
            $info['shop_name'] = ($voucher_info['shop_id']==0)?'自营店':$shop_info['shop_name'];
            $info['shop_logo'] = $shop_info['shop_logo'] ? getApiSrc($shop_info['shop_logo']) : '';
            $info['start_time'] = $voucher_info['start_time'];
            $info['end_time'] = $voucher_info['end_time'];
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $info;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * wab礼品券适用门店
     */
    public function giftvoucherStore()
    {
        $result = 0;
        $gift_voucher_id = (int)input('gift_voucher_id');
        $data = $list = [];
        if(getAddons('store', $this->website_id)){
            $lng = input('post.lng','');
            $lat = input('post.lat','');
            $page_index = input('post.page_index',1);
            $page_size = input('post.page_size',PAGESIZE);
            $VoucherServer = new VoucherServer();
            $voucher_info = $VoucherServer->getGiftVoucherDetail(['gift_voucher_id'=>$gift_voucher_id]);
            $storeServer = new storeServer();
            $condition = array(
                'shop_id' => $voucher_info['shop_id'],
                'website_id' => $voucher_info['website_id'],
                'status' => 1,
            );
            $place = [
                'lng' => $lng,
                'lat' => $lat
            ];
            $list = $storeServer->storeListForFront($page_index, $page_size, $condition,$place);
            $result = 1;
        }
        if($result){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $list;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
}