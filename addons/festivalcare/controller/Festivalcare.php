<?php
namespace addons\festivalcare\controller;

use addons\festivalcare\Festivalcare as baseFestivalcare;
use addons\festivalcare\model\VslFestivalCareModel;
use addons\festivalcare\model\VslFestivalCareRecordsModel;
use addons\festivalcare\server\FestivalCare as FestivalCareServer;
use addons\coupontype\server\Coupon as CouponServer;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use data\model\VslGoodsModel;
use addons\gift\model\VslPromotionGiftModel;
use data\model\AlbumPictureModel;

class Festivalcare extends baseFestivalcare
{
    public function __construct()
    {
        parent::__construct();

    }
    public function festivalcareList()
    {
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $search_text = input('post.search_text','');
        $state = input('post.state','');
        $festivalcare_server = new FestivalCareServer();
        if ($search_text) {
            $condition['festivalcare_name'] = ['LIKE', '%' . $search_text . '%'];
        }
        if($state){
            $condition['state'] = $state;
        }
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $list = $festivalcare_server->getFestivalCareList($page_index, $page_size, $condition, 'start_time desc');
        $festivalcare = new VslFestivalCareModel();
        $count = $festivalcare->getFestivalcareNum($condition);
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
    
    public function addFestivalcare()
    {
        $input =[];
        $input['festivalcare_name'] = input('post.festivalcare_name');
        $input['group_id'] = (int)input('post.group_id');
        $input['weixin_msg'] = input('post.weixin_msg');
        if(strpos($input['weixin_msg'],"[奖品名称]")== false || strpos($input['weixin_msg'],"[奖品链接]")== false || strpos($input['weixin_msg'],"[奖品过期时间]")== false){
            return ['code' => -1,'message' => '微信推送内容格式错误'];
        }
        $input['prize_name'] = input('post.prize_name');
        $input['prize_type'] = input('post.prize_type');
        $input['prize_type_id'] = (int)input('post.prize_type_id');
        $input['prize_point'] = input('post.prize_point',0);
        $input['prize_money'] = input('post.prize_money',0);
        $input['prize_pic'] = input('post.prize_pic','');
        $input['create_time'] = time();
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['expire_time'] = strtotime(input('post.expire_time')) + (86400 - 1);
        $end_time = strtotime(date("Y-m-d",$input['start_time'])) + (86400 - 1);
        if($input['start_time']>time())$input['state'] = 1;
        if($input['start_time']<=time() && $end_time>=time())$input['state'] = 2;
        if($end_time<time())$input['state'] = 3;
        $input['shop_id'] = $condition['shop_id'] = $this->instance_id;
        $input['website_id'] = $condition['website_id'] = $this->website_id;
        $input['priority'] = $condition['priority'] = input('post.priority',0);
        $festivalcare = new VslFestivalCareModel();
        $condition['state'] = ['neq',3];
        $count = $festivalcare->getFestivalcareViewCount($condition);
        if($count>0){
            return ['code' => -1,'message' => '优先等级已存在'];
        }
        $festivalcare_server = new FestivalCareServer();
        $res = $festivalcare_server->addFestivalcare($input);
        if($res){
            $this->addUserLog('添加节日关怀', $res);
        }
        return AjaxReturn($res);
    }
    public function updateFestivalcare()
    {
        $input =[];
        $input['festivalcare_name'] = input('post.festivalcare_name');
        $input['group_id'] = (int)input('post.group_id');
        $input['weixin_msg'] = input('post.weixin_msg');
        if(strpos($input['weixin_msg'],"[奖品名称]")== false || strpos($input['weixin_msg'],"[奖品链接]")== false || strpos($input['weixin_msg'],"[奖品过期时间]")== false){
            return ['code' => -1,'message' => '微信推送内容格式错误'];
        }
        $input['prize_name'] = input('post.prize_name');
        $input['prize_type'] = input('post.prize_type');
        $input['prize_type_id'] = (int)input('post.prize_type_id');
        $input['prize_point'] = input('post.prize_point',0);
        $input['prize_money'] = input('post.prize_money',0);
        $input['prize_pic'] = input('post.prize_pic','');
        $input['update_time'] = time();
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['expire_time'] = strtotime(input('post.expire_time')) + (86400 - 1);
        $end_time = strtotime(date("Y-m-d",$input['start_time'])) + (86400 - 1);
        if($input['start_time']>time())$input['state'] = 1;
        if($input['start_time']<=time() && $end_time>=time())$input['state'] = 2;
        if($end_time<time())$input['state'] = 3;
        $where['festival_care_id'] = input('post.festival_care_id');
        $where['shop_id'] = $condition['shop_id'] = $this->instance_id;
        $where['website_id'] = $condition['website_id'] = $this->website_id;
        $input['priority'] = $condition['priority'] = input('post.priority',0);
        $festivalcare = new VslFestivalCareModel();
        $condition['state'] = ['neq',3];
        $condition['festival_care_id'] = ['neq',$where['festival_care_id']];
        $count = $festivalcare->getFestivalcareViewCount($condition);
        if($count>0){
            return ['code' => -1,'message' => '优先等级已存在'];
        }
        $festivalcare_server = new FestivalCareServer();
        $res = $festivalcare_server->updateFestivalcare($input,$where);
        if($res){
            $this->addUserLog('修改节日关怀', $res);
        }
        return AjaxReturn($res);
    }
    public function deleteFestivalcare()
    {
        $festival_care_id = (int)input('post.festival_care_id');
        $condition['festival_care_id'] = $festival_care_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $time = strtotime(date("Y-m-d"));
        $condition['start_time'] = ['lt',$time];
        if (empty($festival_care_id)) {
            return ['code' => -1,'message' => '没有获取到节日关怀信息'];
        }
        $festivalcare_server = new FestivalCareServer();
        $res = $festivalcare_server->deleteFestivalCare($condition);
        if($res){
            $this->addUserLog('删除节日关怀', $festival_care_id);
        }
        return AjaxReturn($res);
    }
    public function historyFestivalcare()
    {
        $festivalcare_server = new FestivalCareServer();
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $where['vmp.state'] = input('post.state');
        $where['vfcr.festival_care_id'] = (int)input('get.festival_care_id');
        $where['vfcr.website_id'] = $this->website_id;
        $where['vfcr.shop_id'] = $this->instance_id;
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
        $fields = 'vmp.term_name,vmp.prize_name,vmp.prize_time,vmp.state,su.uid,su.user_tel,su.nick_name,su.user_name';
        $list = $festivalcare_server->getFestivalcareHistory($page_index, $page_size, $where, $fields,'vmp.prize_time desc');
        $record = new VslFestivalCareRecordsModel();
        $count = $record->getUserPrizeNum($where);
        $list['count'] = $count;
        return $list;
    }

    public function saveSetting()
    {
        $festivalcare_server = new FestivalCareServer();
        $is_festivalcare = (int)input('post.is_festivalcare');
        $result = $festivalcare_server->saveConfig($is_festivalcare);
        if($result){
            $this->addUserLog('修改节日关怀设置', $result);
        }
        setAddons('festivalcare', $this->website_id, $this->instance_id);
        return AjaxReturn($result);
    }
    /**
     * 领取奖品
     */
    public function acceptFestivalcare()
    {
        $prize_id= (int)input('prize_id');
        $festivalcare_server = new FestivalCareServer();
        $info = $festivalcare_server->acceptFestivalcare($prize_id);
        return json($info);
    }
}