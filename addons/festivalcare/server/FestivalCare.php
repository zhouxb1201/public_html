<?php
namespace addons\festivalcare\server;

use addons\festivalcare\model\VslFestivalCareModel;
use addons\festivalcare\model\VslFestivalCareRecordsModel;
use addons\coupontype\server\Coupon as CouponServer;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use addons\gift\server\Gift as GiftServer;
use addons\coupontype\model\VslCouponTypeModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use data\model\VslGoodsModel;
use data\model\AlbumPictureModel;
use data\service\BaseService;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig;
use data\model\VslMemberPrizeModel;
use data\model\VslMemberGroupModel;
use data\extend\WchatOauth;

class FestivalCare extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }
    /**
     * 获取节日关怀列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     */
    public function getFestivalCareList($page_index, $page_size, $condition, $order = 'create_time desc')
    {
        $festivalcare = new VslFestivalCareModel();
        $list = $festivalcare->getFestivalcareList($page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * @param array $input
     * @return int
     */
    public function addFestivalcare($input)
    {
        $festivalcare = new VslFestivalCareModel();
        $festivalcare->startTrans();
        try {
            $res = $festivalcare->save($input);
            $festivalcare->commit();
            return $res;
        } catch (\Exception $e) {
            $festivalcare->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * @param array $input
     * @return int
     */
    public function updateFestivalcare($input ,$where)
    {
        $festivalcare = new VslFestivalCareModel();
        $festivalcare->startTrans();
        try {
            $festivalcare->save($input,$where);
            $festivalcare->commit();
            return 1;
        } catch (\Exception $e) {
            $festivalcare->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 删除节日关怀
     * @return int 1
     */
    public function deleteFestivalCare($condition)
    {
        $festivalcare = new VslFestivalCareModel();
        $festivalcare->startTrans();
        try {
            $info = $festivalcare->getFestivalcareViewCount($condition);
            if ($info == 1) {
                $record = new VslFestivalCareRecordsModel();
                $festivalcare::destroy(['festival_care_id' => $condition['festival_care_id']]);
                $record::destroy(['festival_care_id' => $condition['festival_care_id']]);
                $festivalcare->commit();
                return 1;
            }
            return -1;
        } catch (\Exception $e) {
            $festivalcare->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * 获取节日关怀详情
     */
    public function getFestivalCareDetail($condition,$type=0)
    {
        $festivalcare = new VslFestivalCareModel();
        $info = $festivalcare->getFestivalcareDetail($condition);
        if($type==1 && $info){
            $info['prize_goods'] = [];
            if($info['prize_type']==5){
                $goods = [];
                $vsl_goods = new VslGoodsModel();
                $vslgoods = $vsl_goods->getInfo(['goods_id'=>$info['prize_type_id']],'goods_id,goods_name,price,img_id_array,picture');
                $goods["goods_id"] = $vslgoods['goods_id'];
                $goods["goods_name"] = $vslgoods['goods_name'];
                $goods["price"] = $vslgoods['price'];
                // 查询图片表
                $goods_img = new AlbumPictureModel();
                $order = "instr('," . $vslgoods['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
                $goods_img_list = $goods_img->getQuery([
                    'pic_id' => [
                        "in",
                        $vslgoods['img_id_array']
                    ]
                ], '*', $order);
                if (trim($vslgoods['img_id_array']) != "") {
                    $img_temp_array = [];
                    $img_array = explode(",", $vslgoods['img_id_array']);
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
                $goods['pic_cover'] = __IMG($img_temp_array[0]['pic_cover']);
                $info['prize_goods'] = $goods;
            }
            $info['prize_gift'] = [];
            if($info['prize_type']==6){
                $gift = [];
                $gift_server = new GiftServer();
                $vslgift = $gift_server->giftDetail($info['prize_type_id']);
                $gift["promotion_gift_id"] = $vslgift['promotion_gift_id'];
                $gift["gift_name"] = $vslgift['gift_name'];
                $gift["price"] = $vslgift['price'];
                $gift["pic_cover"] = $vslgift['picture_detail']['pic_cover_mid'];
                $info['prize_gift'] = $gift;
            }
            if(getAddons('coupontype', $this->website_id, $this->instance_id, true)){
                $condition = ['website_id' => $this->website_id,'shop_id' => $this->instance_id];
                $condition['start_receive_time'] = ['elt',time()];
                $condition['end_receive_time'] = ['egt',time()];
                $CouponServer = new CouponServer();
                $coupon = $CouponServer->getCouponTypeList(1, 20, $condition);
                $info['coupon'] = $coupon['data'];
            }
            if(getAddons('giftvoucher', $this->website_id, $this->instance_id, true)){
                $condition = ['gv.website_id' => $this->website_id,'gv.shop_id' => $this->instance_id];
                $condition['gv.start_receive_time'] = ['elt',time()];
                $condition['gv.end_receive_time'] = ['egt',time()];
                $VoucherServer = new VoucherServer();
                $coupon = $VoucherServer->getGiftVoucherList(1, 20, $condition);
                $info['giftvoucher'] = $coupon['data'];
            }
        }else if($type==2 && $info){
            $member_group = new VslMemberGroupModel();
            $group = $member_group->getInfo(['group_id'=>$info['group_id']],'group_name');
            $info['group_name'] = $group['group_name']?$group['group_name']:'所有标签';
            $info['name'] = '';
            if($info['prize_type']==3 || $info['prize_type']==4 || $info['prize_type']==5 || $info['prize_type']==6){
                $info['name'] = $this->getPrizeName($info['prize_type_id'],$info['prize_type']);
            }
        }
        return $info;
    }
    
    /**
     * 奖品名称
     */
    public function getPrizeName($prize_type_id, $prize_type)
    {
        $name = '';
        if($prize_type==3){
            $vsl_coupontype = new VslCouponTypeModel();
            $coupontype = $vsl_coupontype->getInfo(['coupon_type_id'=>$prize_type_id],'coupon_name');
            $name = $coupontype['coupon_name'];
        }
        if($prize_type==4){
            $vsl_giftvoucher = new VslGiftVoucherModel();
            $giftvoucher = $vsl_giftvoucher->getInfo(['gift_voucher_id'=>$prize_type_id],'giftvoucher_name');
            $name = $giftvoucher['giftvoucher_name'];
        }
        if($prize_type==5){
            $vsl_goods = new VslGoodsModel();
            $goods = $vsl_goods->getInfo(['goods_id'=>$prize_type_id],'goods_name');
            $name = $goods['goods_name'];
        }
        if($prize_type==6){
            $gift_server = new GiftServer();
            $gift = $gift_server->giftDetail($prize_type_id);
            $name = $gift['gift_name'];
        }
        return $name;
    }
    
    /**
     * 获取中奖记录
     */
    public function getFestivalcareHistory($page_index, $page_size, $where, $fields, $order)
    {
        $record = new VslFestivalCareRecordsModel();
        $list = $record->getPrizeHistory($page_index, $page_size, $where, $fields, $order);
        return $list;
    }
    
    public function saveConfig($is_festivalcare)
    {
        $AddonsConfig = new AddonsConfig();
        $info = $AddonsConfig->getAddonsConfig("festivalcare");
        if (!empty($info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_festivalcare, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'festivalcare'
            ]);
        } else {
            $res = $AddonsConfig->addAddonsConfig('', '节日关怀设置', $is_festivalcare, 'festivalcare');
        }
        return $res;
    }
    
    /**
     * 创建领取记录
     */
    public function createFestivalCareRecord($festival_care_id,$uid,$wx_openid)
    {
        $festivalcare = new VslFestivalCareModel();
        $records = new VslFestivalCareRecordsModel();
        $info = $festivalcare->getInfo(['festival_care_id'=>$festival_care_id]);
        if($info){
            $data = [];
            $data['festival_care_id'] = $info['festival_care_id'];
            $data['shop_id'] = $info['shop_id'];
            $data['uid'] = $uid;
            $data['prize_time'] = time();
            $data['website_id'] = $info['website_id'];
            $data['member_prize_id'] = 0;
            $res = $records->insert($data);
            if ($res) {
                $wchat = new WchatOauth($info['website_id']);
                $weixin_msg = $info['weixin_msg'];
                $weixin_msg = str_replace("[奖品名称]", $info['prize_name'], $weixin_msg);
                $weixin_msg = str_replace("[奖品链接]", '<a href="'.__URLS('/wap/festivalcare/centre','',['website_id'=>$info['website_id']]).'/'.$res.'">点击领取</a>', $weixin_msg);
                $expire_time = date('Y-m-d H:i:s', $info['expire_time']);
                $content = str_replace("[奖品过期时间]", $expire_time, $weixin_msg);
                $wchat->send_message($wx_openid, 'text', $content);
            }
        }
        return;
    }
    
    /**
     * 领取奖品
     */
    public function acceptFestivalcare($prize_id)
    {
        $records = new VslFestivalCareRecordsModel();
        $records->startTrans();
        try {
            $uid = $this->uid;
            $website_id = $this->website_id;
            $detail = $records->getFestivalcareDetail($prize_id);
            $res = ['code' => -1,'message' => '没有获取到节日关怀信息','detail'=>[]];
            if($detail){
                $info = [];
                $info['festival_care_id'] = $detail['festival_care_id'];
                $info['festivalcare_name'] = $detail['festivalcare_name'];
                $info['prize_name'] = $detail['prize_name'];
                $info['prize_pic'] = $detail['prize_pic'];
                $info['start_time'] = date('Y-m-d H:i:s', $detail['start_time']);
                $info['expire_time'] = date('Y-m-d H:i:s', $detail['expire_time']);
                if($detail['prize_type']==1){
                    $info['name'] = $detail['prize_money'].'元';
                }else if($detail['prize_type']==2){
                    $info['name'] = $detail['prize_point'].'积分';
                }else if($detail['prize_type']==3 || $detail['prize_type']==4 || $detail['prize_type']==5 || $detail['prize_type']==6){
                    $info['name'] = $this->getPrizeName($detail['prize_type_id'],$detail['prize_type']);
                }
                $res['data'] = $info;
                if($detail['member_prize_id']==0 && $detail['state']==2){
                    $record_prize = 1;
                    if($detail['prize_type']==3){//优惠券
                        $record_prize = 0;
                        if(getAddons('coupontype', $website_id,$detail['shop_id'])){
                            $coupon = new CouponServer();
                            $num = $coupon->getRestCouponType($detail['prize_type_id'],$uid);
                            if($num>0){
                                $record_prize = $coupon->getUserReceive($uid, $detail['prize_type_id'],8,-1);
                            }
                        }
                    }
                    if($detail['prize_type']==4){//礼品券
                        $record_prize = 0;
                        if(getAddons('giftvoucher', $website_id,$detail['shop_id'])){
                            $voucher = new VoucherServer();
                            $num = $voucher->getGiftVoucherType($detail['prize_type_id'],$uid);
                            if($num>0){
                                $record_prize = $voucher->getUserReceive($uid, $detail['prize_type_id'],2,-1);
                            }
                        }
                    }
                    if($record_prize>0){
                        $mprize = [];
                        $mprize['uid'] = $uid;
                        $mprize['activity_id'] = $detail['festival_care_id'];
                        $mprize['activity_type'] = 6;
                        $mprize['prize_name'] = $detail['prize_name'];
                        $mprize['term_name'] = '节日关怀';
                        $mprize['type'] = $detail['prize_type'];
                        $mprize['type_id'] = ($detail['prize_type']==3 || $detail['prize_type']==4)?$record_prize:$detail['prize_type_id'];
                        $mprize['point'] = $detail['prize_point'];
                        $mprize['money'] = $detail['prize_money'];
                        $mprize['pic'] = $detail['prize_pic'];
                        $mprize['state'] = 1;
                        $mprize['prize_time'] = time();
                        $mprize['expire_time'] = $detail['expire_time'];
                        $mprize['shop_id'] = $detail['shop_id'];
                        $mprize['website_id'] = $website_id;
                        $mprize['name'] = $info['name'];
                        $member_prize = new VslMemberPrizeModel();
                        $result = $member_prize->save($mprize);
                        if($result){
                            $data['uid'] = $uid;
                            $data['member_prize_id'] = $result;
                            $records->save($data,['record_id'=>$prize_id]);
                            $records->commit();
                            $res['code'] = 1;
                            $res['message'] = '领取成功';
                        }else{
                            $res['message'] = '领取失败';
                        }
                    }else{
                        $res['message'] = '领取失败';
                    }
                }else if($detail['member_prize_id']>0){
                    $res['code'] = 0;
                    $res['message'] = '已领取';
                }else if($detail['state']==3){
                    $res['code'] = 0;
                    $res['message'] = '活动已结束';
                }
            }
            return $res;
        } catch (\Exception $e) {
            $records->rollback();
            return $e->getMessage();
        }
    }
}