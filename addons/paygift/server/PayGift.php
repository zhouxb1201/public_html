<?php
namespace addons\paygift\server;

use addons\paygift\model\VslPayGiftModel;
use addons\paygift\model\VslPayGiftRecordsModel;
use addons\coupontype\server\Coupon as CouponServer;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use addons\gift\server\Gift as GiftServer;
use addons\coupontype\model\VslCouponTypeModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\coupontype\model\VslCouponModel;
use addons\giftvoucher\model\VslGiftVoucherRecordsModel;
use data\model\VslGoodsModel;
use data\model\AlbumPictureModel;
use data\service\BaseService;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig;
use data\model\VslOrderModel;
use data\model\VslOrderGoodsModel;
use data\model\VslMemberPrizeModel;
use data\model\UserModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberAccountRecordsModel;

class PayGift extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }
    /**
     * 获取支付有礼列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     */
    public function getPayGiftList($page_index, $page_size, $condition, $order = 'create_time desc')
    {
        $vsl_paygift = new VslPayGiftModel();
        $list = $vsl_paygift->getPaygiftViewList($page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * @param array $input
     * @return int
     */
    public function addPaygift($input)
    {
        $vsl_paygift = new VslPayGiftModel();
        $vsl_paygift->startTrans();
        try {
            $res = $vsl_paygift->save($input);
            $vsl_paygift->commit();
            return $res;
        } catch (\Exception $e) {
            $vsl_paygift->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * @param array $input
     * @return int
     */
    public function updatePaygift($input ,$where)
    {
        $vsl_paygift = new VslPayGiftModel();
        $vsl_paygift->startTrans();
        try {
            $vsl_paygift->save($input,$where);
            $vsl_paygift->commit();
            return 1;
        } catch (\Exception $e) {
            $vsl_paygift->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 删除支付有礼
     * @return int 1
     */
    public function deletePayGift($condition)
    {
        $vsl_paygift = new VslPayGiftModel();
        $vsl_paygift->startTrans();
        try {
            $info = $vsl_paygift->getPaygiftViewCount($condition);
            if ($info == 1) {
                $record = new VslPayGiftRecordsModel();
                $vsl_paygift::destroy(['pay_gift_id' => $condition['pay_gift_id']]);
                $record::destroy(['pay_gift_id' => $condition['pay_gift_id']]);
                $vsl_paygift->commit();
                return 1;
            }
            return -1;
        } catch (\Exception $e) {
            $vsl_paygift->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * 获取支付有礼详情
     */
    public function getPayGiftDetail($condition,$type=0)
    {
        $vsl_paygift = new VslPayGiftModel();
        $info = $vsl_paygift->getPaygiftDetail($condition);
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
    public function getPaygiftHistory($page_index, $page_size, $where, $fields, $order)
    {
        $record = new VslPayGiftRecordsModel();
        $list = $record->getPrizeHistory($page_index, $page_size, $where, $fields, $order);
        return $list;
    }
    
    public function saveConfig($is_paygift)
    {
        $AddonsConfig = new AddonsConfig();
        $info = $AddonsConfig->getAddonsConfig("paygift");
        if (!empty($info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_paygift, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'paygift'
            ]);
        } else {
            $res = $AddonsConfig->addAddonsConfig('', '支付有礼设置', $is_paygift, 'paygift');
        }
        return $res;
    }
    
    /**
     * 创建领取记录
     */
    function createPaygiftRecord($order_id){
        $website_id = $this->website_id;
        $order_model = new VslOrderModel();
        $order_info = $order_model->getInfo(['order_id' => $order_id], "order_id,goods_money,pay_money,user_platform_money,final_money,money_type,shop_id,pay_gift_status,buyer_id");
        if($order_info['pay_gift_status']==1)return;
        $uid = $order_info['buyer_id'];
        $condition['shop_id'] = $order_info['shop_id'];
        $condition['website_id'] = $website_id;
        $condition['start_time'] = ['elt',time()];
        $condition['end_time'] =  ['egt',time()];
        $vsl_paygift = new VslPayGiftModel();
        $paygift_list = $vsl_paygift->getQuery($condition,'*','priority desc');
        if($paygift_list){
            $sign = 0;
            $records = new VslPayGiftRecordsModel();
            foreach ($paygift_list as $k => $v) {
                if($sign == 0){
                    $record_prize = 1;
                    if($v['prize_type']==3){//优惠券
                        $record_prize = 0;
                        $coupon = new CouponServer();
                        $num = $coupon->getRestCouponType($v['prize_type_id'],$uid);
                        
                        if($num>0){
                            $record_prize = $coupon->getUserReceive($uid, $v['prize_type_id'],8,-1);
                        }
                    }
                    if($v['prize_type']==4){//礼品券
                        $record_prize = 0;
                        $voucher = new VoucherServer();
                        $num = $voucher->getGiftVoucherType($v['prize_type_id'],$uid);
                        if($num>0){
                            $record_prize = $voucher->getUserReceive($uid, $v['prize_type_id'],2,-1);
                        }
                    }
                    
                    if($record_prize>0){
                        $data = [];
                        $data['pay_gift_id'] = $v['pay_gift_id'];
                        $data['shop_id'] = $v['shop_id'];
                        $data['uid'] = $uid;
                        $data['prize_time'] = time();
                        $data['order_id'] = $order_id;
                        $data['website_id'] = $website_id;
                        $mprize = [];
                        $mprize['uid'] = $uid;
                        $mprize['activity_id'] = $v['pay_gift_id'];
                        $mprize['activity_type'] = 4;
                        $mprize['prize_name'] = $v['prize_name'];
                        $mprize['term_name'] = '支付有礼';
                        $mprize['type'] = $v['prize_type'];
                        $mprize['type_id'] = ($v['prize_type']==3 || $v['prize_type']==4)?$record_prize:$v['prize_type_id'];
                        $mprize['point'] = $v['prize_point'];
                        $mprize['money'] = $v['prize_money'];
                        $mprize['pic'] = $v['prize_pic'];
                        $mprize['activity_order_id'] = $order_id;
                        $mprize['state'] = 1;
                        $mprize['prize_time'] = time();
                        $mprize['expire_time'] = $v['expire_time'];
                        $mprize['shop_id'] = $v['shop_id'];
                        $mprize['website_id'] = $website_id;
                        if($v['prize_type']==1){
                            $mprize['name'] = $v['prize_money'].'元';
                        }else if($v['prize_type']==2){
                            $mprize['name'] = $v['prize_point'].'积分';
                        }else if($v['prize_type']==3 || $v['prize_type']==4 || $v['prize_type']==5 || $v['prize_type']==6){
                            $mprize['name'] = $this->getPrizeName($v['prize_type_id'],$v['prize_type']);
                        }
                        if($v['modes']==1 && $v['prize_num']>0){
                            $money = 0;
                            if($order_info['pay_money']>0){
                                $money = $order_info['pay_money'];
                            }else if($order_info['user_platform_money']){
                                $money = $order_info['user_platform_money'];
                            }
                            if($order_info['order_type']==7){//预售
                                $money = 0;
                                if($order_info['money_type']==2){
                                    if($order_info['pay_money']>0){
                                        $money = $order_info['final_money'] + $order_info['pay_money'];
                                    }else if($order_info['user_platform_money']){
                                        $money = $order_info['final_money'] + $order_info['user_platform_money'];
                                    }
                                }
                            }
                            if($money>=$v['modes_money']){
                                $sign = 1;
                                $member_prize = new VslMemberPrizeModel();
                                $result = $member_prize->save($mprize);
                                if($result){
                                    $vsl_paygift->where(['pay_gift_id'=>$v['pay_gift_id']])->setDec('prize_num', 1);
                                    $data['member_prize_id'] = $result;
                                    $records->save($data);
                                }
                            }
                        }else if($v['modes']==2 && $v['prize_num']>0){
                            $order_goods = new VslOrderGoodsModel();
                            $goods_list = $order_goods->getQuery(['order_id'=>$order_id],'goods_id','');
                            foreach ($goods_list as $k2 => $v2) {
                                if($v2['goods_id']==$v['modes_id']){
                                    $sign = 1;
                                    $member_prize = new VslMemberPrizeModel();
                                    $result = $member_prize->save($mprize);
                                    if($result){
                                        $vsl_paygift->where(['pay_gift_id'=>$v['pay_gift_id']])->setDec('prize_num', 1);
                                        $data['member_prize_id'] = $result;
                                        $records->save($data);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if($sign==1){
                $order_model->save(['pay_gift_status'=>1],['order_id'=>$order_id]);
                $this->grantPrize($order_id);
            }
        }
        return;
    }
    
    /**
     * 退款退还礼品
     */
    function returnPayGift($order_id){
        $member_prize = new VslMemberPrizeModel();
        $prize_info = $member_prize->getInfo(['activity_order_id' => $order_id]);
        if($prize_info){
            $res = $member_prize->where('member_prize_id',$prize_info['member_prize_id'])->delete();
            if($res){
                if($prize_info['prize_type']==3){//优惠券
                    $coupon = new VslCouponModel();
                    $coupon->where('coupon_id',$prize_info['receive_id'])->delete();
                }
                if($prize_info['prize_type']==4){//礼品券
                    $voucher = new VslGiftVoucherRecordsModel();
                    $num = $voucher->where('record_id',$prize_info['receive_id'])->delete();
                }
            }
        }
        return;
    }
    
    /**
     * 自动发放奖品
     */
    function grantPrize($order_id){
        $member_prize = new VslMemberPrizeModel();
        $info = $member_prize->where(['activity_order_id'=>$order_id])->select();
        if(!empty($info)){
            foreach ($info as $k => $v) {
                if($v['activity_type']==4 && in_array($v['type'],[1,2,3,4])){
                    $paygift = new VslPayGiftModel();
                    $paygift_info = $paygift->getInfo(['pay_gift_id' => $v['activity_id']], "grant_state");
                    if($paygift_info && $paygift_info['grant_state']==1){
                        $this->acceptPrize($v['member_prize_id']);
                    }
                }
            }
        }
        return;
    }
    
    /**
     * 发放奖品
     */
    public function acceptPrize($member_prize_id)
    {
        $member_prize = new VslMemberPrizeModel();
        $condition = [];
        $condition['member_prize_id'] = $member_prize_id;
        $info = $member_prize->getInfo($condition,'uid,website_id,type,type_id,point,money,state,expire_time,shop_id,member_prize_id,activity_id,activity_type,activity_order_id');
        $member_prize->startTrans();
        try {
            if(!empty($info)){
                $state = 1;
                $uid = $info['uid'];
                $website_id = $info['website_id'];
                if($info['activity_type']!=4)$state = -1;
                $user_model = new UserModel();
                $user_info = $user_model::get($uid, ['member_info.level', 'member_account', 'member_address']);
                if ($user_info->user_status == 0) {
                    $state = -1;
                }
                $vsl_paygift = new VslPayGiftModel();
                $paygift_info = $vsl_paygift->getInfo(['pay_gift_id'=>$info['activity_id']],'grant_node');
                if(!empty($paygift_info)){
                    $order_model = new VslOrderModel();
                    $order_info = $order_model->getInfo(['order_id' => $info['activity_order_id']], "order_status,order_no");
                    if($order_info['order_status']==5){
                        $state = -1;
                    }
                    if($paygift_info['grant_node']==1 && $order_info['order_status']==0){
                        $state = -1;
                    }
                    if($paygift_info['grant_node']==2 && $order_info['order_status']!=4){
                        $state = -1;
                    }
                }
                $activity_name = '支付有礼';
                if($info['state']==1 && $info['expire_time']>=time() && $state==1){
                    if($info['type']==1){//余额
                        $member_account = new VslMemberAccountModel();
                        $where = [];
                        $where['uid'] = $uid;
                        $where['website_id'] = $website_id;
                        $result = $member_account->where($where)->setInc('balance', $info['money']);
                        if($result){
                            $records = new VslMemberAccountRecordsModel();
                            $data['uid'] = $uid;
                            $data['shop_id'] = 0;
                            $data['account_type'] = 2;
                            $data['sign'] = 0;
                            $data['number'] = $info['money'];
                            $data['from_type'] = 18;
                            $data['data_id'] = $member_prize_id;
                            $data['text'] = $activity_name.'获得余额';
                            $data['create_time'] = time();
                            $data['website_id'] = $website_id;
                            $data['records_no'] = 'Ac'.getSerialNo();
                            $result = $records->save($data);
                            $params = ['uid'=>$uid,'records_no'=>$data['records_no'],'money'=>$info['money']];
                            runhook('Notify', 'successacceptPrizeByTemplate', $params);
                        }
                    }
                    if($info['type']==2){//积分
                        $member_account = new VslMemberAccountModel();
                        $where = [];
                        $where['uid'] = $uid;
                        $where['website_id'] = $website_id;
                        $result = $member_account->where($where)->setInc('point', $info['point']);
                        if($result){
                            $records = new VslMemberAccountRecordsModel();
                            $data['uid'] = $uid;
                            $data['shop_id'] = 0;
                            $data['account_type'] = 1;
                            $data['sign'] = 0;
                            $data['number'] = $info['point'];
                            $data['from_type'] = 17;
                            $data['data_id'] = $member_prize_id;
                            $data['text'] = $activity_name.'获得积分';
                            $data['create_time'] = time();
                            $data['website_id'] = $website_id;
                            $data['records_no'] = 'Ac'.getSerialNo();
                            $result = $records->save($data);
                        }
                    }
                    if($info['type']==3){//优惠券
                        $coupon = new CouponServer();
                        $result = $coupon->getUserThaw($uid,$info['type_id']);
                    }
                    if($info['type']==4){//礼品券
                        $voucher = new VoucherServer();
                        $result = $voucher->getUserThaw($uid,$info['type_id']);
                    }
                    if($result>0){
                        $member_prize->where($condition)->update(['state' => 2,'receive_id'=>$result]);
                        $member_prize->commit();
                    }
                }else{
                    $member_prize->commit();
                }
            }
        } catch (\Exception $e) {
            recordErrorLog($e);
            $member_prize->rollback();
        }
        return;
    }
}