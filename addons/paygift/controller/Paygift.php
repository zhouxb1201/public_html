<?php
namespace addons\paygift\controller;

use addons\paygift\Paygift as basePaygift;
use addons\paygift\model\VslPayGiftModel;
use addons\paygift\model\VslPayGiftRecordsModel;
use addons\paygift\server\PayGift as PayGiftServer;
use addons\coupontype\server\Coupon as CouponServer;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use data\model\VslGoodsModel;
use addons\gift\model\VslPromotionGiftModel;
use data\model\AlbumPictureModel;

class Paygift extends basePaygift
{
    public function __construct()
    {
        parent::__construct();

    }
    public function paygiftList()
    {
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $search_text = input('post.search_text','');
        $state = input('post.state','');
        $PayGiftServer = new PayGiftServer();
        if ($search_text) {
            $condition['paygift_name'] = ['LIKE', '%' . $search_text . '%'];
        }
        if($state){
            $condition['state'] = $state;
        }
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $list = $PayGiftServer->getPayGiftList($page_index, $page_size, $condition, 'priority desc,start_time desc');
        if($list['data']){
            foreach($list['data'] as $k => $v){
                $list['data'][$k]['goods_name'] = '';
                if($v['modes']==2){
                    $vsl_goods = new VslGoodsModel();
                    $goods = $vsl_goods->getInfo(['goods_id'=>$v['modes_id']],'goods_name');
                    $list['data'][$k]['goods_name'] = $goods['goods_name'];
                }
            }
        }
        $vsl_paygift = new VslPayGiftModel();
        $count = $vsl_paygift->getPaygiftNum($condition);
        $list['count'] = $count;
        return $list;
    }
    /**
     * 优惠券/礼品券/商品列表
     */
    public function prizeType()
    {
        $type = input('type',0);
        $list = [];
        if($type==3){
            $is_use = getAddons('coupontype', $this->website_id, $this->instance_id);
            if($is_use==1){
                $condition['website_id'] = $this->website_id;
                $condition['shop_id'] = $this->instance_id;
                $condition['start_receive_time'] = ['elt',time()];
                $condition['end_receive_time'] = ['egt',time()];
                $CouponServer = new CouponServer();
                $coupon = $CouponServer->getCouponTypeList(1, 10, $condition);
                $list['code'] = 1;
                $list['data'] = $coupon['data'];
                }else{
                    $list['code'] = 0;
                    $list['data']['is_use'] = $is_use;
                }
            return $list;
        }
        if($type==4){
            $is_use = getAddons('giftvoucher', $this->website_id, $this->instance_id);
            if($is_use==1){
                $condition['gv.website_id'] = $this->website_id;
                $condition['gv.shop_id'] = $this->instance_id;
                $condition['gv.start_receive_time'] = ['elt',time()];
                $condition['gv.end_receive_time'] = ['egt',time()];
                $VoucherServer = new VoucherServer();
                $coupon = $VoucherServer->getGiftVoucherList(1, 10, $condition);
                $list['code'] = 1;
                $list['data'] = $coupon['data'];
            }else{
                $list['code'] = 0;
                $list['data']['is_use'] = $is_use;
            }
            return $list;
        }
        if($type==5){
            $sort = input('sort');
            if (request()->post('page_index')) {
                $page_index = input('post.page_index',1);
                $page_size = input('post.page_size',PAGESIZE);
                $search_text = input('post.search_text','');
                if ($search_text) {
                    $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
                }
                $condition['website_id'] = $this->website_id;
                $condition['shop_id'] = $this->instance_id;
                $condition['stock'] = ['gt',0];
                $condition['goods_type'] = ['<>',4];
                $goods = new VslGoodsModel();
                $list = $goods->pageQuery($page_index, $page_size, $condition,'create_time desc','goods_id,goods_name,description,price,img_id_array,picture');
                $goods_list = [];
                if( !empty($list['data']) ){
                    foreach($list['data'] as $k => $v){
                        $goods_list[$k]['goods_id'] = $v['goods_id'];
                        $goods_list[$k]['goods_name'] = $v['goods_name'];
                        $goods_list[$k]['description'] = $v['description'];
                        $goods_list[$k]['price'] = $v['price'];
                        
                        // 查询图片表
                        $goods_img = new AlbumPictureModel();
                        $order = "instr('," . $v['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
                        $goods_img_list = $goods_img->getQuery([
                            'pic_id' => [
                                "in",
                                $v['img_id_array']
                            ]
                        ], '*', $order);
                        if (trim($v['img_id_array']) != "") {
                            $img_temp_array = array();
                            $img_array = explode(",", $v['img_id_array']);
                            foreach ($img_array as $ki => $vi) {
                                if (!empty($goods_img_list)) {
                                    foreach ($goods_img_list as $t => $m) {
                                        if ($m["pic_id"] == $vi) {
                                            $img_temp_array[] = $m;
                                        }
                                    }
                                }
                            }
                        }
                        if($img_temp_array){
                            foreach($img_temp_array as $kk => $vv){
                                $img_temp_array[$kk]['pic_cover'] = __IMG($vv['pic_cover']);
                            }
                        }
                        $goods_list[$k]["img_temp_array"] = $img_temp_array;
                        $goods_list[$k]['pic_cover'] = __IMG($img_temp_array[0]['pic_cover']);
                    }
                }
                $list['data'] = $goods_list;
                return $list;
            }
            $this->assign('sort', $sort);
            $this->fetch('template/' . $this->module . '/goodsDialog');
        }
        if($type==6){
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
                return $list;
            }
            header("Content-type: text/html; charset=utf-8");
            $this->fetch('template/' . $this->module . '/giftlistDialog');
        }
    }
    
    public function addPaygift()
    {
        $input =[];
        $input['paygift_name'] = input('post.paygift_name');
        $input['modes'] = input('post.modes');
        $input['modes_id'] = (int)input('post.modes_id');
        $input['modes_money'] = input('post.modes_money',0);
        $input['grant_node'] = input('post.grant_node');
        $input['grant_state'] = input('post.grant_state');
        $input['prize_name'] = input('post.prize_name');
        $input['prize_type'] = input('post.prize_type');
        $input['prize_type_id'] = (int)input('post.prize_type_id');
        $input['prize_point'] = input('post.prize_point',0);
        $input['prize_money'] = input('post.prize_money',0);
        $input['prize_num'] = input('post.prize_num');
        $input['prize_pic'] = input('post.prize_pic','');
        $input['create_time'] = time();
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['end_time'] = strtotime(input('post.end_time')) + (86400 - 1);
        $input['expire_time'] = strtotime(input('post.expire_time')) + (86400 - 1);
        if($input['start_time']>time())$input['state'] = 1;
        if($input['start_time']<=time() && $input['end_time']>=time())$input['state'] = 2;
        if($input['end_time']<time())$input['state'] = 3;
        $input['shop_id'] = $condition['shop_id'] = $this->instance_id;
        $input['website_id'] = $condition['website_id'] = $this->website_id;
        $input['priority'] = $condition['priority'] = input('post.priority',0);
        $vsl_paygift = new VslPayGiftModel();
        $condition['end_time'] = ['egt',time()];
        $count = $vsl_paygift->getPaygiftViewCount($condition);
        if($count>0){
            return ['code' => -1,'message' => '优先等级已存在'];
        }
        $PayGiftServer = new PayGiftServer();
        $res = $PayGiftServer->addPaygift($input);
        if($res){
            $this->addUserLog('添加支付有礼', $res);
        }
        return AjaxReturn($res);
    }
    public function updatePaygift()
    {
        $input =[];
        $input['paygift_name'] = input('post.paygift_name');
        $input['modes'] = input('post.modes');
        $input['modes_id'] = (int)input('post.modes_id');
        $input['modes_money'] = input('post.modes_money',0);
        $input['grant_node'] = input('post.grant_node');
        $input['grant_state'] = input('post.grant_state');
        $input['prize_name'] = input('post.prize_name');
        $input['prize_type'] = input('post.prize_type');
        $input['prize_type_id'] = (int)input('post.prize_type_id');
        $input['prize_point'] = input('post.prize_point',0);
        $input['prize_money'] = input('post.prize_money',0);
        $input['prize_num'] = input('post.prize_num');
        $input['prize_pic'] = input('post.prize_pic','');
        $input['update_time'] = time();
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['end_time'] = strtotime(input('post.end_time')) + (86400 - 1);
        $input['expire_time'] = strtotime(input('post.expire_time')) + (86400 - 1);
        if($input['start_time']>time())$input['state'] = 1;
        if($input['start_time']<=time() && $input['end_time']>=time())$input['state'] = 2;
        if($input['end_time']<time())$input['state'] = 3;
        $where['pay_gift_id'] = input('post.pay_gift_id');
        $where['shop_id'] = $condition['shop_id'] = $this->instance_id;
        $where['website_id'] = $condition['website_id'] = $this->website_id;
        $input['priority'] = $condition['priority'] = input('post.priority',0);
        $vsl_paygift = new VslPayGiftModel();
        $condition['end_time'] = ['egt',time()];
        $condition['pay_gift_id'] = ['neq',$where['pay_gift_id']];
        $count = $vsl_paygift->getPaygiftViewCount($condition);
        if($count>0){
            return ['code' => -1,'message' => '优先等级已存在'];
        }
        $PayGiftServer = new PayGiftServer();
        $res = $PayGiftServer->updatePaygift($input,$where);
        if($res){
            $this->addUserLog('修改支付有礼', $res);
        }
        return AjaxReturn($res);
    }
    public function deletePaygift()
    {
        $pay_gift_id = (int)input('post.pay_gift_id');
        $condition['pay_gift_id'] = $pay_gift_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $condition['end_time'] = ['lt',time()];
        if (empty($pay_gift_id)) {
            return ['code' => -1,'message' => '没有获取到支付有礼信息'];
        }
        $PayGiftServer = new PayGiftServer();
        $res = $PayGiftServer->deletePayGift($condition);
        if($res){
            $this->addUserLog('删除支付有礼', $pay_gift_id);
        }
        return AjaxReturn($res);
    }
    public function historyPaygift()
    {
        $PayGiftServer = new PayGiftServer();
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $where['vmp.state'] = input('post.state');
        $where['vpgr.pay_gift_id'] = (int)input('get.pay_gift_id');
        $where['vpgr.website_id'] = $this->website_id;
        $where['vpgr.shop_id'] = $this->instance_id;
        $start_time = input('post.start_time');
        $end_time = input('post.end_time');
        if($start_time){
            $start_time = strtotime($start_time);
            $where['vmp.prize_time'] = ['egt',$start_time];
        }
        if($end_time){
            $end_time = strtotime($end_time) + (86400 - 1);
            $where['vmp.prize_time'] = ['elt',$end_time];
        }
        if($start_time && $end_time){
            $where['vmp.prize_time'] = array(['egt',$start_time],['elt',$end_time],'and');
        }
        $fields = 'vmp.term_name,vmp.prize_name,vmp.prize_time,vmp.state,su.user_tel,su.nick_name';
        $list = $PayGiftServer->getPaygiftHistory($page_index, $page_size, $where, $fields,'vmp.prize_time desc');
        $record = new VslPayGiftRecordsModel();
        $count = $record->getUserPrizeNum($where);
        $list['count'] = $count;
        return $list;
    }

    public function saveSetting()
    {
        $PayGiftServer = new PayGiftServer();
        $is_paygift = (int)input('post.is_paygift');
        $result = $PayGiftServer->saveConfig($is_paygift);
        if($result){
            $this->addUserLog('修改支付有礼设置', $result);
        }
        setAddons('paygift', $this->website_id, $this->instance_id);
        return AjaxReturn($result);
    }
}