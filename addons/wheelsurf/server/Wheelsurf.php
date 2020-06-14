<?php
namespace addons\wheelsurf\server;

use addons\wheelsurf\model\VslWheelsurfModel;
use addons\wheelsurf\model\VslWheelsurfPrizeModel;
use addons\wheelsurf\model\VslWheelsurfRecordsModel;
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
use data\service\Member\MemberAccount;
use data\model\VslMemberAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslMemberPrizeModel;

class Wheelsurf extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }
    /**
     * 获取大转盘列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     */
    public function getWheelsurfList($page_index, $page_size, $condition, $order = 'create_time desc')
    {
        $vsl_wheelsurf = new VslWheelsurfModel();
        $list = $vsl_wheelsurf->getWheelsurfViewList($page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * @param array $input
     * @return int
     */
    public function addWheelsurf($input)
    {
        $vsl_wheelsurf = new VslWheelsurfModel();
        $vsl_wheelsurf->startTrans();
        try {
            $res = $vsl_wheelsurf->save($input);
            $vsl_wheelsurf->commit();
            return $res;
        } catch (\Exception $e) {
            $vsl_wheelsurf->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * @param array $input
     * @return int
     */
    public function addWheelsurfPrize($input)
    {
        $vsl_prize = new VslWheelsurfPrizeModel();
        $vsl_prize->startTrans();
        try {
            $vsl_prize->saveAll($input);
            $vsl_prize->commit();
            return 1;
        } catch (\Exception $e) {
            $vsl_prize->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * @param array $input
     * @return int
     */
    public function updateWheelsurf($input ,$where)
    {
        $vsl_wheelsurf = new VslWheelsurfModel();
        $vsl_wheelsurf->startTrans();
        try {
            $vsl_wheelsurf->save($input,$where);
            $vsl_wheelsurf->commit();
            return 1;
        } catch (\Exception $e) {
            $vsl_wheelsurf->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * @param array $input
     * @return int
     */
    public function updateWheelsurfPrize($input,$wheelsurf_id)
    {
        $vsl_prize = new VslWheelsurfPrizeModel();
        $vsl_prize->startTrans();
        try {
            $data = $where = [];
            $ids = $vsl_prize->Query(['wheelsurf_id'=>$wheelsurf_id],'prize_id');
            foreach ($input as $k=>$v){
                $where['prize_id'] = $v['prize_id'];
                $data['term_name'] = $v['term_name'];
                $data['prize_type'] = $v['prize_type'];
                $data['prize_name'] = $v['prize_name'];
                $data['num'] = $v['num'];
                $data['probability'] = $v['probability'];
                $data['prize_type_id'] = $v['prize_type_id'];
                $data['prize_point'] = $v['prize_point'];
                $data['prize_money'] = $v['prize_money'];
                $data['prize_pic'] = $v['prize_pic'];
                $data['expire_time'] = $v['expire_time'];
                $data['sort'] = $v['sort'];
                $vsl_prize->where($where)->update($data);
                $ids = array_diff($ids, [$v['prize_id']]);
            }
            if($ids){
                foreach ($ids as $v2){
                    $vsl_prize->delData(['prize_id'=>$v2]);
                }
            }
            $vsl_prize->commit();
            return 1;
        } catch (\Exception $e) {
            $vsl_prize->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * 删除大转盘
     * @return int 1
     */
    public function deleteWheelsurf($condition)
    {
        $vsl_wheelsurf = new VslWheelsurfModel();
        $vsl_wheelsurf->startTrans();
        try {
            $info = $vsl_wheelsurf->getWheelsurfViewCount($condition);
            if ($info == 1) {
                $prize = new VslWheelsurfPrizeModel();
                $record = new VslWheelsurfRecordsModel();
                $vsl_wheelsurf::destroy(['wheelsurf_id' => $condition['wheelsurf_id']]);
                $prize::destroy(['wheelsurf_id' => $condition['wheelsurf_id']]);
                $record::destroy(['wheelsurf_id' => $condition['wheelsurf_id']]);
                $vsl_wheelsurf->commit();
                return 1;
            }
            return -1;
        } catch (\Exception $e) {
            $vsl_wheelsurf->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * 获取大转盘详情
     */
    public function getWheelsurfDetail($condition,$type=0)
    {
        $vsl_wheelsurf = new VslWheelsurfModel();
        $info = $vsl_wheelsurf->getWheelsurfDetail($condition);
        if($type==1 && $info['prize']){
            foreach($info['prize'] as $k => $v){
                $info['prize'][$k]['goods'] = [];
                if($v['prize_type']==5){
                    $goods = [];
                    $vsl_goods = new VslGoodsModel();
                    $vslgoods = $vsl_goods->getInfo(['goods_id'=>$v['prize_type_id']],'goods_id,goods_name,price,img_id_array,picture');
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
                    $info['prize'][$k]['goods'] = $goods;
                }
                $info['prize'][$k]['gift'] = [];
                if($v['prize_type']==6){
                    $gift = [];
                    $gift_server = new GiftServer();
                    $vslgift = $gift_server->giftDetail($v['prize_type_id']);
                    $gift["promotion_gift_id"] = $vslgift['promotion_gift_id'];
                    $gift["gift_name"] = $vslgift['gift_name'];
                    $gift["price"] = $vslgift['price'];
                    $gift["pic_cover"] = $vslgift['picture_detail']['pic_cover_mid'];
                    $info['prize'][$k]['gift'] = $gift;
                }
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
        }else if($type==2 && $info['prize']){
            foreach($info['prize'] as $k => $v){
                $info['prize'][$k]['name'] = '';
                if($v['prize_type']==3 || $v['prize_type']==4 || $v['prize_type']==5 || $v['prize_type']==6){
                    $info['prize'][$k]['name'] = $this->getPrizeName($v['prize_type_id'],$v['prize_type']);
                }
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
    public function getWheelsurfHistory($page_index, $page_size, $where, $fields, $order)
    {
        $record = new VslWheelsurfRecordsModel();
        $list = $record->getPrizeHistory($page_index, $page_size, $where, $fields, $order);
        return $list;
    }
    
    public function saveConfig($is_wheelsurf)
    {
        $AddonsConfig = new AddonsConfig();
        $info = $AddonsConfig->getAddonsConfig("wheelsurf");
        if (!empty($info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_wheelsurf, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'wheelsurf'
            ]);
        } else {
            $res = $AddonsConfig->addAddonsConfig('', '大转盘设置', $is_wheelsurf, 'wheelsurf');
        }
        return $res;
    }
    
    /**
     * 用户当天次数
     */
    public function userFrequency($wheelsurf_id)
    {
        $vsl_wheelsurf = new VslWheelsurfModel();
        $info = $vsl_wheelsurf->getInfo(['wheelsurf_id'=>$wheelsurf_id],'');
        $where['wheelsurf_id'] = $wheelsurf_id;
        $where['uid'] = $this->uid;
        $record = new VslWheelsurfRecordsModel();
        $usercount =  $record->getCount($where);
        $time1 = strtotime(date('Y-m-d'));
        $time2 = strtotime(date('Y-m-d')) + (86400 - 1);
        $where['turn_time'] = array(['egt',$time1],['elt',$time2],'and');
        $userday =  $record->getCount($where);
        $data['frequency'] = 0;
        if($info['max_partake']>$usercount && $info['max_partake_daily']>$userday){
            $surplus = $info['max_partake'] - $usercount;
            if($info['max_partake_daily']>$surplus){
                $data['frequency'] = $surplus;
            }else{
                $data['frequency'] = $info['max_partake_daily'] - $userday;
            }
        }
        if($info['max_partake']==0 && $info['max_partake_daily']>$userday){//max_partake 无限制
            $data['frequency'] = $info['max_partake_daily'] - $userday;
        }
        if($info['max_partake']>$usercount && $info['max_partake_daily']==0){//max_partake_daily 无限制
            $data['frequency'] = $info['max_partake'] - $usercount;
        }
        if($info['max_partake']==0 && $info['max_partake_daily']==0){//max_partake max_partake_daily 无限制
            $data['frequency'] = -9999;
        }
        $data['state'] = $info['state'];
        $data['wheelsurf_name'] = $info['wheelsurf_name'];
        return $data;
    }
    /**
     * 中奖名单
     */
    public function prizeRecords($wheelsurf_id)
    {
        $page_index = input('page_index',1);
        $page_size = input('page_size',PAGESIZE);
        $where['vwr.state'] = 1;
        $where['vwr.wheelsurf_id'] = $wheelsurf_id;
        $where['vwr.website_id'] = $this->website_id;
        $where['vwr.shop_id'] = $this->instance_id;
        $fields = 'vmp.term_name,vmp.prize_name,vmp.prize_time,su.user_tel,su.nick_name';
        $record = new VslWheelsurfRecordsModel();
        $list = $record->getPrizeHistory($page_index, $page_size, $where, $fields,'vwr.turn_time desc');
        return $list;
    }
    /**
     * 活动详情
     */
    public function wheelsurfInfo($wheelsurf_id)
    {
        $vsl_wheelsurf = new VslWheelsurfModel();
        $info = $vsl_wheelsurf->getWheelsurfDetail(['wheelsurf_id'=>$wheelsurf_id]);
        $data = [];
        if($info){
            $data['wheelsurf_id'] = $info['wheelsurf_id'];
            $data['shop_id'] = $info['shop_id'];
            $data['wheelsurf_name'] = $info['wheelsurf_name'];
            $data['start_time'] = $info['start_time'];
            $data['end_time'] = $info['end_time'];
            $data['desc'] = $info['desc'];
            $data['background_pic'] = $info['background_pic'];
            $data['pointer_pic'] = $info['pointer_pic'];
            if($info['prize']){
                foreach($info['prize'] as $k => $v){
                    $data['prize'][$k]['prize_id'] = $v['prize_id'];
                    $data['prize'][$k]['term_name'] = $v['term_name'];
                    $data['prize'][$k]['prize_name'] = $v['prize_name'];
                    $data['prize'][$k]['num'] = $v['num'];
                    $data['prize'][$k]['prize_type'] = $v['prize_type'];
                    $data['prize'][$k]['prize_pic'] = $v['prize_pic'];
                }
            }
        }
        return $data;
    }
    /**
     * 转盘抽奖
     */
    public function userWheelsurf($wheelsurf_id)
    {
        $vsl_wheelsurf = new VslWheelsurfModel();
        $vsl_wheelsurf->startTrans();
        try {
            $uid = $this->uid;
            $website_id = $this->website_id;
            $info = $vsl_wheelsurf->getWheelsurfDetail(['wheelsurf_id'=>$wheelsurf_id]);
            if(!empty($info)){
                if($info['start_time']>time()){
                    return ['code'=>-1,'message'=>'活动未开始'];
                }
                if($info['end_time']<time()){
                    return ['code'=>-2,'message'=>'活动已结束'];
                }
                $frequency = $this->userFrequency($wheelsurf_id);   
                if($frequency['frequency']==0){
                    return ['code'=>-3,'message'=>'没抽奖机会'];
                }
                if($info['point']>0){
                    $account = new MemberAccount();
                    $point = $account->getMemberPoint($uid);
                    if($info['point']>$point){
                        return ['code'=>-4,'message'=>'积分不足'];
                    }
                    $member_account = new VslMemberAccountModel();
                    $where['uid'] = $uid;
                    $where['website_id'] = $website_id;
                    $result = $member_account->where($where)->setDec('point', $info['point']);
                    $result = $member_account->where($where)->setInc('member_sum_point', $info['point']);
                    if($result){
                        $records = new VslMemberAccountRecordsModel();
                        $data['uid'] = $uid;
                        $data['shop_id'] = 0;
                        $data['account_type'] = 1;
                        $data['sign'] = 0;
                        $data['number'] = '-'.$info['point'];
                        $data['from_type'] = 16;
                        $data['data_id'] = $info['wheelsurf_id'];
                        $data['text'] = '大转盘消费积分';
                        $data['create_time'] = time();
                        $data['website_id'] = $website_id;
                        $data['records_no'] = 'Ac'.getSerialNo();
                        $records->save($data);
                    }
                }
                $data = [];
                $count = 0;
                foreach($info['prize'] as $k => $v){
                    $data[] = $v['probability']/100;
                    $count += $v['probability']/100;
                }
                $result = getAliasMethod($data);
                $records = new VslWheelsurfRecordsModel();
                $data = [];
                $data['wheelsurf_id'] = $wheelsurf_id;
                $data['shop_id'] = $info['shop_id'];
                $data['uid'] = $uid;
                $data['turn_time'] = time();
                $data['website_id'] = $website_id;
                if($info['prize'][$result] && $info['prize'][$result]['num']>0 && $info['prize'][$result]['prize_type']!=0){
                    $prize = $info['prize'][$result];
                    $record_prize = 1;
                    if($prize['prize_type']==3){//优惠券
                        $record_prize = 0;
                        if(getAddons('coupontype', $website_id,$info['shop_id'])){
                            $coupon = new CouponServer();
                            $num = $coupon->getRestCouponType($prize['prize_type_id'],$uid);
                            if($num>0){
                                $record_prize = $coupon->getUserReceive($uid, $prize['prize_type_id'],8,-1);
                            }
                        }
                    }
                    if($prize['prize_type']==4){//礼品券
                        $record_prize = 0;
                        if(getAddons('giftvoucher', $website_id,$info['shop_id'])){
                            $voucher = new VoucherServer();
                            $num = $voucher->getGiftVoucherType($prize['prize_type_id'],$uid);
                            if($num>0){
                                $record_prize = $voucher->getUserReceive($uid, $prize['prize_type_id'],2,-1);
                            }
                        }
                    }
                    if($record_prize>0){
                        $prizes = [];
                        $prizes['prize_id'] = $prize['prize_id'];
                        $prizes['term_name'] = $prize['term_name'];
                        $prizes['prize_name'] = $prize['prize_name'];
                        $vsl_prize = new VslWheelsurfPrizeModel();
                        $vsl_prize->where(['prize_id'=>$prize['prize_id']])->setDec('num', 1);
                        $member_prize = new VslMemberPrizeModel();
                        $mprize = [];
                        $mprize['uid'] = $uid;
                        $mprize['activity_id'] = $wheelsurf_id;
                        $mprize['activity_type'] = 2;
                        $mprize['prize_name'] = $prize['prize_name'];
                        $mprize['term_name'] = $prize['term_name'];
                        $mprize['type'] = $prize['prize_type'];
                        $mprize['type_id'] = ($prize['prize_type']==3 || $prize['prize_type']==4)?$record_prize:$prize['prize_type_id'];
                        $mprize['point'] = $prize['prize_point'];
                        $mprize['money'] = $prize['prize_money'];
                        $mprize['pic'] = $prize['prize_pic'];
                        $mprize['state'] = 1;
                        $mprize['prize_time'] = time();
                        $mprize['expire_time'] = $prize['expire_time'];
                        $mprize['shop_id'] = $info['shop_id'];
                        $mprize['website_id'] = $website_id;
                        if($prize['prize_type']==1){
                            $mprize['name'] = $prize['prize_money'].'元';
                        }else if($prize['prize_type']==2){
                            $mprize['name'] = $prize['prize_point'].'积分';
                        }else if($prize['prize_type']==3 || $prize['prize_type']==4 || $prize['prize_type']==5 || $prize['prize_type']==6){
                            $mprize['name'] = $this->getPrizeName($prize['prize_type_id'],$prize['prize_type']);
                        }
                        $result = $member_prize->save($mprize);
                        $data['member_prize_id'] = $result;
                        $data['state'] = 1;
                        $records->save($data);
                        $vsl_wheelsurf->commit();
                        return ['code'=>1,'message'=>'参与成功','data'=>$prizes];
                    }else{
                        //取一个未中奖
                        $prizes = [];
                        foreach($info['prize'] as $k => $v){
                            if($v['prize_type']==0 && empty($prizes)){
                                $prizes['prize_id'] = $v['prize_id'];
                                $prizes['term_name'] = $v['term_name'];
                                $prizes['prize_name'] = $v['prize_name'];
                            }
                        }
                        $data['state'] =  $data['member_prize_id'] = 0;
                        $records->save($data);
                        $vsl_wheelsurf->commit();
                        return ['code'=>0,'message'=>'参与成功','data'=>$prizes];
                    }
                }else if($info['prize'][$result] && $info['prize'][$result]['prize_type']==0){
                    $prize = $info['prize'][$result];
                    $prizes = [];
                    $prizes['prize_id'] = $prize['prize_id'];
                    $prizes['term_name'] = $prize['term_name'];
                    $prizes['prize_name'] = $prize['prize_name'];
                    $data['state'] =  $data['member_prize_id'] = 0;
                    $records->save($data);
                    $vsl_wheelsurf->commit();
                    return ['code'=>0,'message'=>'参与成功','data'=>$prizes];
                }else if($info['prize'][$result] && $info['prize'][$result]['num']==0 && $info['prize'][$result]['prize_type']!=0){//中奖，但num为0
                    //取一个未中奖
                    $prizes = [];
                    foreach($info['prize'] as $k => $v){
                        if($v['prize_type']==0 && empty($prizes)){
                            $prizes['prize_id'] = $v['prize_id'];
                            $prizes['term_name'] = $v['term_name'];
                            $prizes['prize_name'] = $v['prize_name'];
                        }
                    }
                    $data['state'] =  $data['member_prize_id'] = 0;
                    $records->save($data);
                    $vsl_wheelsurf->commit();
                    return ['code'=>0,'message'=>'参与成功','data'=>$prizes];
                }else{
                    return ['code'=>-5,'message'=>'操作失败'];
                }
            }
            return ['code'=>-6,'message'=>'没有相关活动'];
        } catch (\Exception $e) {
            $vsl_wheelsurf->rollback();
            return $e->getMessage();
        }
    }
}