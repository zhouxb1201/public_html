<?php
namespace addons\signin\server;

use data\service\BaseService;
use addons\signin\model\VslSignInModel;
use addons\signin\model\VslSignInRuleModel;
use addons\signin\model\VslSignInRecordsModel;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig;
use addons\coupontype\server\Coupon as CouponServer;
use addons\coupontype\model\VslCouponTypeModel;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use addons\giftvoucher\model\VslGiftVoucherModel;
use data\model\VslMemberModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\UserModel;

class signin extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }
    /**
     * 获取签到明细
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     */
    public function getSignInList($page_index, $page_size, $where ,$field, $order = 'record_id asc')
    {
        $record = new VslSignInRecordsModel();
        $recordlist = $record->getViewList($page_index, $page_size, $where ,$field, $order);
        $list = [];
        if($recordlist){
            foreach ($recordlist['data'] as $k=>$v){
                $list['data'][$k]['user_tel'] = $v['user_tel'];
                $list['data'][$k]['sign_in_time'] = $v['sign_in_time'];
                $type = $type_name = '';
                if($v['point']>0){
                    $type = '、积分';
                    $type_name = '、'.$v['point'].'积分';
                }
                if($v['money']>0){
                    $type = $type.'、余额';
                    $type_name = $type_name.'、'.$v['money'].'元';
                }
                if($v['gift_voucher_name']){
                    $type = $type.'、礼品券';
                    $type_name = $type_name.'、'.$v['gift_voucher_name'].'礼品券';
                }
                if($v['coupon_type_name']){
                    $type = $type.'、优惠券';
                    $type_name = $type_name.'、'.$v['coupon_type_name'].'优惠券';
                }
                $list['data'][$k]['type'] = mb_substr($type,1);
                $list['data'][$k]['type_name'] = mb_substr($type_name,1);
            }
        }
        $list['page_count'] = $recordlist['page_count'];
        $list['total_count'] = $recordlist['total_count'];
        return $list;
    }
    
    /**
     * 修改每日签到
     */
    public function updateSignIn($input ,$sign_in_id)
    {
        $signin = new VslSignInModel();
        $inputs = $where = [];
        $inputs['state'] = $input['state'];
        $signin->startTrans();
        try {
            if($sign_in_id>0){
                $inputs['update_time'] = time();
                $where['sign_in_id'] = $sign_in_id;
                $where['website_id'] = $input['website_id'];
                $res = $signin->save($inputs,$where);
            }else{
                $inputs['create_time'] = time();
                $inputs['shop_id'] = $input['shop_id'];
                $inputs['website_id'] = $input['website_id'];
                $res = $signin->save($inputs);
                $sign_in_id = $res;
            }
            if($res){
                $this->updateSignInRule($input,$sign_in_id);
            }
            $signin->commit();
            return 1;
        } catch (\Exception $e) {
            $signin->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * 修改签到规则
     */
    public function updateSignInRule($input ,$sign_in_id)
    {
        $signin_rule = new VslSignInRuleModel();
        $website_id = $input['website_id'];
        $inputs = $input['data'];
        $signin_rule->startTrans();
        try {
            $data = $where = [];
            $ids = $signin_rule->Query(['sign_in_id'=>$sign_in_id],'rule_id');
            foreach ($inputs as $k=>$v){
                $where['rule_id'] = $v['rule_id'];
                $data['days'] = $v['days'];
                $data['point'] = $v['point'];
                $data['money'] = $v['money'];
                $data['growth_num'] = $v['growth_num'];
                $data['gift_voucher_id'] = $v['gift_voucher_id'];
                $data['coupon_type_id'] = $v['coupon_type_id'];
                if($v['rule_id']>0){
                    $signin_rule->where($where)->update($data);
                    $ids = array_diff($ids, [$v['rule_id']]);
                }else{
                    $data['sign_in_id'] = $sign_in_id;
                    $data['website_id'] = $website_id;
                    $signin_rule->insert($data);
                    $ids = [];
                }
            }
            if($ids){
                foreach ($ids as $v2){
                    $signin_rule->delData(['rule_id'=>$v2]);
                }
            }
            $signin_rule->commit();
            return 1;
        } catch (\Exception $e) {
            $signin_rule->rollback();
            return $e->getMessage();
        }
    }
    
    public function saveConfig($is_signin)
    {
        $AddonsConfig = new AddonsConfig();
        $info = $AddonsConfig->getAddonsConfig("signin");
        if (!empty($info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_signin, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'signin'
            ]);
        } else {
            $res = $AddonsConfig->addAddonsConfig('', '每日签到设置', $is_signin, 'signin');
        }
        return $res;
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
        return $name;
    }
    
    /**
     * 获取签到详情
     */
    public function getSignInDetail($condition)
    {
        $signin = new VslSignInModel();
        $info = $signin->getDetail($condition);
        return $info;
    }
    
    /**
     * 获取签到规则
     */
    public function getSignInRule($condition)
    {
        $signin_rule = new VslSignInRuleModel();
        $lists = $signin_rule->getList($condition);
        $data = [];
        if($lists){
            foreach ($lists as $k=>$v){
                if($v['days']==0){
                    $data['rule_id'] = $v['rule_id'];
                    $data['days'] = $v['days'];
                    $data['point'] = $v['point'];
                    $data['money'] = $v['money'];
                    $data['growth_num'] = $v['growth_num'];
                    $data['gift_voucher_id'] = $v['gift_voucher_id'];
                    $data['coupon_type_id'] = $v['coupon_type_id'];
                }
            }
        }
        $list['list'] = $lists;
        $list['data'] = $data;
        return $list;
    }
    /**
     * 优惠券/礼品券
     */
    public function prizeList($condition)
    {
        $list = $where = [];
        $where['website_id'] = $condition['website_id'];
        $where['shop_id'] = $condition['shop_id'];
        $where['start_receive_time'] = ['elt',time()];
        $where['end_receive_time'] = ['egt',time()];
        $CouponServer = new CouponServer();
        $coupon = $CouponServer->getCouponTypeList(1, 10, $where);
        $list['coupontype'] = $coupon['data'];
        $where = [];
        $where['gv.website_id'] = $condition['website_id'];
        $where['gv.shop_id'] = $condition['shop_id'];
        $where['gv.start_receive_time'] = ['elt',time()];
        $where['gv.end_receive_time'] = ['egt',time()];
        $VoucherServer = new VoucherServer();
        $coupon = $VoucherServer->getGiftVoucherList(1, 10, $where);
        $list['giftvoucher'] = $coupon['data'];
        return $list;
    }
    /**
     *会员签到信息
     */
    public function userSignInInfo()
    {
        $where['vsir.uid'] = $this->uid;
        $where['vsir.website_id'] = $this->website_id;
        $where['vsir.shop_id'] = $this->instance_id;
        $field = 'vsir.continuous,vsir.continuous_rule,vsir.sign_in_time,su.user_tel,su.nick_name,su.user_name,su.user_headimg';
        $record = new VslSignInRecordsModel();
        $info = $record->getDetail($where,$field);
        if($info){
            $sign_in_time = strtotime(date('Y-m-d', $info['sign_in_time']));
            $time = strtotime(date('Y-m-d'));
            if($sign_in_time==$time){
                $info['is_signin'] = 1;
            }else{
                $info['is_signin'] = 0;
                $yesterday = strtotime(date('Y-m-d')) - 3600*24;
                if($sign_in_time!=$yesterday){
                    $info['continuous'] = 0;
                    $info['continuous_rule'] = 0;
                }
            }
            $info['sign_in_time'] = date('Y-m-d H:i:s', $info['sign_in_time']);
        }else{
            $info = [];
            $info['continuous'] = 0;
            $info['continuous_rule'] = 0;
            $info['is_signin'] = 0;
            $info['sign_in_time'] = "";
            $user_item = new UserModel();
            $user_item_info = $user_item->where(['uid' => $this->uid])->find();
            $info['user_tel'] = $user_item_info['user_tel'];
            $info['nick_name'] = $user_item_info['nick_name'];
            $info['user_name'] = $user_item_info['user_name'];
            $info['user_headimg'] = $user_item_info['user_headimg'];
        }
        return $info;
    }
    /**
     *会员签到时间
     */
    public function userSignInList($time)
    {
        $record = new VslSignInRecordsModel();
        $where['vsir.uid'] = $this->uid;
        $where['vsir.website_id'] = $this->website_id;
        $where['vsir.shop_id'] = $this->instance_id;
        if($time){
            $date_begin = date('Y-m-01', strtotime(date("Y-m-d",strtotime($time))));
        }else{//获取当月
            $date_begin = date('Y-m-01', strtotime(date("Y-m-d")));
        }
        $date_end = date('Y-m-d', strtotime("$date_begin +1 month -1 day"));
        $where['vsir.sign_in_time'] = array(['egt',strtotime($date_begin)],['elt',strtotime($date_end)],'and');
        $field = 'vsir.continuous,vsir.sign_in_time';
        $order = 'sign_in_time asc';
        $list = $record->getList($where ,$field, $order);
        return $list;
    }
    /**
     *会员签到
     */
    public function userSignIn()
    {
        $signin = new VslSignInModel();
        $signin->startTrans();
        try {
            $uid = $this->uid;
            $website_id = $this->website_id;
            $shop_id = $this->instance_id;
            $signin_info = $signin->getDetail(['shop_id'=>$shop_id,'website_id'=>$website_id]);
            if(!empty($signin_info) && getAddons('signin', $website_id)==1){
                $user_signin_info = $this->userSignInInfo();
                if($user_signin_info['is_signin']==1){
                    return ['code'=>-2,'message'=>'已签到'];
                }
                $time = strtotime(date('Y-m-d',strtotime($user_signin_info['sign_in_time'])));
                $yesterday = strtotime(date('Y-m-d')) - 3600*24;
                $rule_list = $this->getSignInRule(['sign_in_id'=>$signin_info['sign_in_id'],'website_id'=>$website_id]);
                $data = [];
                $data['uid'] = $uid;
                $data['shop_id'] = $shop_id;
                $data['website_id'] = $website_id;
                $data['point'] = $rule_list['data']['point'];
                $data['money'] = $rule_list['data']['money'];
                $data['growth_num'] = $rule_list['data']['growth_num'];
                $data['sign_in_time'] = time();
                $gift_voucher_id = $rule_list['data']['gift_voucher_id'];
                $coupon_type_id = $rule_list['data']['coupon_type_id'];
                $data['gift_voucher_name'] = '';
                $data['coupon_type_name'] = '';
                if($time==$yesterday){
                    $data['continuous'] = $user_signin_info['continuous']+1;
                    $data['continuous_rule'] = $user_signin_info['continuous_rule']+1;
                    if($signin_info['state']==1){
                        $tail_days = 0;//最后连续天数
                        foreach ($rule_list['list'] as $k=>$v){
                            if($v['days']>$tail_days){
                                $tail_days = $v['days'];
                            }
                        }
                        if($data['continuous_rule']>$tail_days){
                            $data['continuous_rule'] = 1;
                        }else{
                            foreach ($rule_list['list'] as $k=>$v){
                                if($data['continuous_rule']==$v['days']){
                                    $data['point'] = $v['point'];
                                    $data['money'] = $v['money'];
                                    $data['growth_num'] = $v['growth_num'];
                                    $gift_voucher_id = $v['gift_voucher_id'];
                                    $coupon_type_id = $v['coupon_type_id'];
                                }
                            }
                        }
                    }
                }else{
                    $data['continuous'] = 1;
                    $data['continuous_rule'] = 1;
                }
                $result = 1;
                if($data['point']>0){
                    $member_account = new VslMemberAccountModel();
                    $where = [];
                    $where['uid'] = $uid;
                    $where['website_id'] = $website_id;
                    $re = $member_account->where($where)->setInc('point', $data['point']);
                    if($re){
                        $res = new VslMemberAccountRecordsModel();
                        $record_data = [];
                        $record_data['uid'] = $uid;
                        $record_data['shop_id'] = 0;
                        $record_data['account_type'] = 1;
                        $record_data['sign'] = 0;
                        $record_data['number'] = $data['point'];
                        $record_data['from_type'] = 5;
                        $record_data['data_id'] = $signin_info['sign_in_id'];
                        $record_data['text'] = '签到获得积分';
                        $record_data['create_time'] = time();
                        $record_data['website_id'] = $website_id;
                        $record_data['records_no'] = 'Si'.getSerialNo();
                        $res->save($record_data);
                    }else{
                        $result = 0;
                    }
                }
                if($data['money']>0){
                    $member_account = new VslMemberAccountModel();
                    $where = [];
                    $where['uid'] = $uid;
                    $where['website_id'] = $website_id;
                    $re = $member_account->where($where)->setInc('balance', $data['money']);
                    if($re){
                        $res = new VslMemberAccountRecordsModel();
                        $record_data = [];
                        $record_data['uid'] = $uid;
                        $record_data['shop_id'] = 0;
                        $record_data['account_type'] = 2;
                        $record_data['sign'] = 0;
                        $record_data['number'] = $data['money'];
                        $record_data['from_type'] = 5;
                        $record_data['data_id'] = $signin_info['sign_in_id'];
                        $record_data['text'] = '签到获得余额';
                        $record_data['create_time'] = time();
                        $record_data['website_id'] = $website_id;
                        $record_data['records_no'] = 'Si'.getSerialNo();
                        $res->save($record_data);
                        $params = ['uid'=>$uid,'records_no'=>$record_data['records_no'],'money'=>$data['money']];
                        runhook('Notify', 'successSigninByTemplate', $params);
                    }else{
                        $result = 0;
                    }
                }
                if($data['growth_num']>0){
                    $member = new VslMemberModel();
                    $re = $member->where(['uid'=>$uid])->setInc('growth_num', $data['growth_num']);
                    if(!$re)$result = 0;
                }
                if($gift_voucher_id>0 && getAddons('giftvoucher', $website_id)){
                    $voucher = new VoucherServer();
                    $num = $voucher->getGiftVoucherType($gift_voucher_id,$uid);
                    if($num>0){
                        $re = $voucher->getUserReceive($uid, $gift_voucher_id,3);
                        $data['gift_voucher_name'] = $this->getPrizeName($gift_voucher_id,4);
                        if(!$re)$result = 0;
                    }else{
                        $result = 2;
                    }
                }
                if($coupon_type_id>0 && getAddons('coupontype', $website_id)){
                    $coupon = new CouponServer();
                    $num = $coupon->getRestCouponType($coupon_type_id,$uid);
                    if($num>0){
                        $re = $coupon->getUserReceive($uid, $coupon_type_id,9);
                        $data['coupon_type_name'] = $this->getPrizeName($coupon_type_id,3);
                        if(!$re)$result = 0;
                    }else{
                        $result = 2;
                    }
                }
                if($result==1){
                    $record = new VslSignInRecordsModel();
                    $record->save($data);
                    $signin->commit();
                    return ['code'=>1,'message'=>'签到成功','data'=>['continuous'=>$data['continuous'],'sign_in_time'=>date('Y-m-d H:i:s',$data['sign_in_time'])]];
                }else if($result==2){
                    $record = new VslSignInRecordsModel();
                    $record->save($data);
                    $signin->commit();
                    return ['code'=>1,'message'=>'签到成功，部分奖品已领完','data'=>['continuous'=>$data['continuous'],'sign_in_time'=>date('Y-m-d H:i:s',$data['sign_in_time'])]];
                }else{
                    return ['code'=>-1,'message'=>'签到失败'];
                }
            }
        } catch (\Exception $e) {
            $signin->rollback();
            return $e->getMessage();
        }
    }
    /**
     *会员签到明细
     */
    public function userSignInRecord()
    {
        $page_index = input('page_index',1);
        $page_size = input('page_size',PAGESIZE);
        $where['vsir.uid'] = $this->uid;
        $where['vsir.website_id'] = $this->website_id;
        $where['vsir.shop_id'] = $this->instance_id;
        $field = 'vsir.*';
        $record = new VslSignInRecordsModel();
        $lists = $record->getViewList($page_index,$page_size,$where ,$field, 'sign_in_time desc');
        $list = [];
        if($lists['data']){
            foreach ($lists['data'] as $k=>$v){
                $list['data'][$k]['sign_in_time'] = date('Y-m-d H:i:s', $v['sign_in_time']);
                $name = '';
                if($v['point']>0){
                    $name = '、+'.$v['point'].'积分';
                }
                if($v['money']>0){
                    $name = $name.'、+'.$v['money'].'元';
                }
                if($v['growth_num']>0){
                    $name = $name.'、+'.$v['growth_num'].'成长值';
                }
                if($v['gift_voucher_name']){
                    $name = $name.'、'.$v['gift_voucher_name'].'礼品券';
                }
                if($v['coupon_type_name']){
                    $name = $name.'、'.$v['coupon_type_name'].'优惠券';
                }
                $list['data'][$k]['name'] = mb_substr($name,1);
            }
        }
        $list['page_count'] = $lists['page_count'];
        $list['total_count'] = $lists['total_count'];
        return $list;
    }
}