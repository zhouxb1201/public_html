<?php

namespace app\wapapi\controller;

use addons\blockchain\model\VslEosOrderPayMentModel;
use addons\blockchain\service\Block;
use addons\channel\model\VslChannelOrderModel;
use addons\channel\model\VslChannelOrderPaymentModel;
use addons\coupontype\model\VslCouponModel;
use addons\distribution\service\Distributor;
use addons\giftvoucher\model\VslGiftVoucherRecordsModel;
use addons\invoice\server\Invoice as InvoiceServer;
use addons\miniprogram\model\WeixinAuthModel;
use data\model\AddonsConfigModel;
use data\model\ConfigModel;
use data\model\UserModel;
use data\model\VslBankModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslMemberBankAccountModel;
use data\model\VslMemberCardModel;
use data\model\VslMemberLevelModel;
use data\model\VslMemberRechargeModel;
use data\model\VslOrderModel;
use data\model\VslOrderPaymentModel;
use data\model\VslPresellModel;
use data\model\WebSiteModel;
use data\model\WeixinFansModel;
use data\service\Config;
use data\service\Member\MemberAccount as MemberAccount;
use data\service\Member as MemberService;
use addons\coupontype\server\Coupon as CouponServer;
use data\service\Order as OrderService;
use data\service\Pay\tlPay;
use data\service\UnifyPay;
use data\service\User;
use data\service\Config as WebConfig;
use data\service\MemberCard;
use data\service\WeixinCard;
use think\Cookie;
use think\Db;
use data\model\VslMemberModel;
use think\Session;
use data\model\VslMemberAccountRecordsViewModel;
use addons\groupshopping\server\GroupShopping;
use data\service\AddonsConfig as AddonsConfigService;
use data\model\VslGoodsModel;
use addons\store\server\Store;
use think\config as Configs;
use data\service\Pay\GlobePay as globalpay;
/**
 * 会员
 *
 * @author  www.vslai.com
 *
 */
class Member extends BaseController
{

    public $notice;
    public $login_verify_code;
    private $rpcType='';

    public function __construct()
    {
        parent::__construct();
        $this->rpcType = Configs::get('blockchain.rpcType');
        $action = request()->action();
        if($_REQUEST['type']=='callback' || $action=='wchatpay' || $action=='alipay'){

        }else{
            parent::__construct();
            $this->uid = getUserId();
            if (!$this->uid) {
                $data['code'] = -1000;
                $data['message'] = '登录信息已过期，请重新登录!';
                if (request()->get('app_test')){
                    $data['user_token'] = $_SERVER['HTTP_USER_TOKEN'];
                    $data['session'] = Session::get();
                }
                echo json_encode($data,JSON_UNESCAPED_UNICODE);
                exit;
            }

        }
    }


    //会员中心首页
    public function memberIndex()
    {
        // 拉黑不能登录
        $user_status = $this->user->getUserStatus($this->uid);
        if ($user_status == USER_LOCK) {
            echo json_encode(['code' => USER_LOCK, 'message' => '用户被锁定'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $json_data = [];
        if (empty($this->uid)) {
            $json_data['code'] = '-1';
            $json_data['message'] = "获取失败";
            return json($json_data);
        }

        $member = new MemberService();
        $member_info = $member->getMemberDetail();

        if(empty($member_info)){
            $data['code'] = -1000;
            $data['message'] = '登录信息已过期，请重新登录!';
            return json($data);
        }

        // 头像
        if (!empty($member_info['user_info']['user_headimg'])) {
            $member_img = getApiSrc($member_info['user_info']['user_headimg']);
        } else {
            $member_img = '';
        }
        // 待支付订单数量
        $order = new OrderService();
        $unpaidOrder = $order->getOrderNumByOrderStatu([
            'order_status' => 0,
            "buyer_id" => $this->uid,
            "buy_type" => ['neq', 2],
        ]);
        // 待发货订单数量
        $shipmentPendingOrder = $order->getOrderNumByOrderStatu([
            'order_status' => 1,
            "buyer_id" => $this->uid,
            "buy_type" => ['neq', 2],
        ]);
        // 待收货订单数量
        $goodsNotReceivedOrder = $order->getOrderNumByOrderStatu([
            'order_status' => 2,
            "buyer_id" => $this->uid,
            "buy_type" => ['neq', 2],
        ]);
        // 退款订单
        $condition['order_status'] = array(
            'in',
            [
                -1,
                -2
            ]
        );

        //优惠券数量
/*      这里为了整合之前获取资产“asset（）”接口 by guowei
        $condition_coup['state'] = 1;
        $condition_coup['end_time'] = ['EGT', time()];
        $condition_coup['uid'] = $this->uid;

        $sql = "select a.*,b.`coupon_id`,b.`uid`,b.`state`,c.`shop_name`,c.`shop_id` from `vsl_coupon_type` as a left JOIN  `vsl_coupon` as b on a.coupon_type_id = b.coupon_type_id left join `vsl_shop` as c on b.`shop_id` = c.`shop_id` AND a.`website_id`= c.`website_id` where b.`uid` = " . $this->uid . " and a.`website_id` = " . $this->website_id . " and b.`state` = 1 and a.end_time > " .time();
        $count = Db::query($sql);*/
        if($this->is_coupon_type){
            $couponModel = new VslCouponModel();
            $couponCondition = [
                'nc.uid' => $this->uid,
                'nc.website_id' => $this->website_id,
                'nc.state' => 1,
                'ct.end_time' => ['GT', time()],
            ];
            
            $viewObj = $couponModel->alias('nc')
            ->join('vsl_coupon_type ct','nc.coupon_type_id = ct.coupon_type_id','left');
            $coupon_num = $couponModel->viewCount($viewObj,$couponCondition);
        }
        if($this->gift_voucher){
        // 礼品券数量
            $giftRecordsModel = new VslGiftVoucherRecordsModel();
            $giftRecordsCondition = [
                'vgvr.uid' => $this->uid,
                'vgvr.website_id' =>$this->website_id,
                'vgvr.state' => 1,
                'vgv.end_time' => ['GT', time()]
            ];
            $giftvoucher_num = $giftRecordsModel->getVoucherHistoryCount($giftRecordsCondition);
        }
        if($this->is_store){
        // 门店消费卡数量
            $memberCardModel = new VslMemberCardModel();
            $memberCardCondition1 = [
                'uid' => $this->uid,
                'website_id' => $this->website_id,
                'invalid_time' => ['GT', time()],
                'count_num' => -999
            ];
            $memberCardCondition2 = [
                'uid' => $this->uid,
                'website_id' => $this->website_id,
                'invalid_time' => ['GT', time()],
                'count_num' => ['GT', 'num']
            ];
            $store_card_num = $memberCardModel->getMemberCardCount($memberCardCondition1, $memberCardCondition2);
        }
        $condition['buyer_id'] = $this->uid;
        $condition['buy_type'] = ['neq', 2];
        $refundOrder = $order->getOrderNumByOrderStatu($condition);

        // 待评价
        $un_evaluate = $order->getOrderNumByOrderStatu([
            'order_status' => ['in', [3, 4]],
            'is_evaluate' => 0,
            'is_deleted' => 0,
            "buyer_id" => $this->uid,
            "buy_type" => ['neq', 2]
        ]);

        //返回所需数据
        $json_data['uid'] = $member_info["user_info"]['uid'];
        $json_data['wx_openid'] = '';
        if($member_info["user_info"]['wx_openid']){
            $json_data['wx_openid'] = $member_info["user_info"]['wx_openid'];
        }else if($member_info["user_info"]['wx_openid']){
            $json_data['wx_openid'] = $member_info["user_info"]['mp_open_id'];
        }

        $json_data['username'] = $member_info["user_info"]['username'];
        $json_data['user_name'] = $member_info["user_info"]['user_name'];
        $json_data['user_tel'] = $member_info["user_info"]['user_tel'];
        $json_data['nick_name'] = $member_info["user_info"]['nick_name'];
        $json_data['reg_time'] = $member_info["user_info"]['reg_time'];
        $json_data['level_name'] = $member_info["level_name"];
        $json_data['balance'] = $member_info['balance'];
        $json_data['point'] = $member_info["point"];
        $json_data['member_discount_label'] = $member_info["member_discount_label"];
        $json_data['unpaidOrder'] = $unpaidOrder;
        $json_data['shipmentPendingOrder'] = $shipmentPendingOrder;
        $json_data['goodsNotReceivedOrder'] = $goodsNotReceivedOrder;
        $json_data['refundOrder'] = $refundOrder;

        $json_data['member_img'] = $member_img;

        $json_data['isdistributor'] = $member_info["isdistributor"];
        
        $json_data['is_global_agent'] =$member_info["is_global_agent"];
        $json_data['is_area_agent'] =$member_info["is_area_agent"];
        $json_data['is_team_agent'] =$member_info["is_team_agent"];
        //判断是否设置了余额支付密码
        $uid = $this->uid;
        $user_mdl = new UserModel();
        $is_pay_pass = $user_mdl->getInfo(['uid' => $uid], 'payment_password')['payment_password'];
        $json_data['is_password_set'] = $is_pay_pass ? 1 : 0;
        $json_data['coupon_num'] = $coupon_num ? : 0;
        $json_data['giftvoucher_num'] = $giftvoucher_num ? : 0;
        $json_data['store_card_num'] = $store_card_num ? : 0;
        $json_data['un_evaluate_num'] = $un_evaluate ? : 0;
        $json_data['digital_assets'] = '';
        $blockchain = getAddons('blockchain',$this->website_id);
        $digital_assets = [];
        if($blockchain){
            $site = new Block();
            $site_info = $site->getBlockChainSite($this->website_id);
            if($site_info['is_use']==1 && $site_info['wallet_type']){
                $wallet_type = explode(',',$site_info['wallet_type']);
                if(in_array(1,$wallet_type)){
                    $digital_assets[] = 1;
                }
                if(in_array(2,$wallet_type)){
                    $digital_assets[] = 2;
                }
            }
        }
        if(count($digital_assets)>0){
            $json_data['digital_assets'] = count($digital_assets);
        }

        // 是否关注
        $userService = new User();
        $json_data['is_subscribe'] = $userService->checkUserIsSubscribe($this->uid);
        
        //app上架审核状态
        $json_data['app_audit'] = 0;
        if(getAddons('appshop', $this->website_id)){
            $addonsModel = new AddonsConfigModel();
            $appConfig = $addonsModel->getInfo(['addons'=>'appshop', 'website_id'=>$this->website_id],'value')['value'];
            $appConfigArr = json_decode($appConfig,true);
            $json_data['app_audit'] = (int)$appConfigArr['audit'] ? : 0;
        }
        $json_data['extend_code'] = $member_info['extend_code'];
        $data['data'] = $json_data;
        $data['code'] = 0;
        return json($data);

    }


    //资产  (已弃用！)
    public function asset()
    {
        $coup_num = Db::table('vsl_coupon')->where(['uid'=>$this->uid,'website_id'=>$this->website_id,'state'=>1])->where('end_time','>',time())->count();
        $giftvoucher_num = Db::table('vsl_gift_voucher_records')->alias('vgvr')->join('vsl_gift_voucher vgv', 'vgv.gift_voucher_id = vgvr.gift_voucher_id', 'left')->where(['vgvr.uid'=>$this->uid,'vgvr.website_id'=>$this->website_id,'state'=>1])->where('vgv.end_time','>',time())->count();
        $store_card_num = Db::table('vsl_member_card')->where(['uid'=>$this->uid,'website_id'=>$this->website_id])->where('invalid_time','>',time())->where('count_num > num or count_num = -999')->count();
        $member = new MemberService();
        $member_info = $member->getMemberDetail();
        $data['balance'] = $member_info['balance'];
        $data['point'] = $member_info['point'];
        $data['coun_num'] = $coup_num;
        $data['giftvoucher_num'] = $giftvoucher_num;
        $data['store_card_num'] = $store_card_num;
        $dataa['data'] = $data;
        $dataa['code'] = 0;
        return json($dataa);
    }

    /**
     * 会员余额
     */
    public function balance()
    {
        $accountAccount = new MemberAccount();
        $accountSum = $accountAccount->getMemberAccount($this->uid);
        //判断是否开启提现
        $config_model = new Config();
        $config_info = $config_model->getConfig($this->instance_id,'WITHDRAW_BALANCE');
        if($config_info['is_use']==1){
            $data['is_use'] = 1;
        }else{
            $data['is_use'] = 0;
        }

        $data['balance'] = $accountSum['balance']+$accountSum['freezing_balance'];   //总金额
        $data['can_use_money'] = $accountSum['balance'];
        $data['freezing_balance'] = $accountSum['freezing_balance'];//冻结金额


        $result['data'] = $data;
        $result['code'] = 0;

        return json($result);
    }


    /**
     * 用户充值余额
     */
    public function recharge()
    {
        $pay = new UnifyPay();
        $pay_no = $pay->createOutTradeNo();

        if (!empty($pay_no)) {
            $data['data']['out_trade_no'] = $pay_no;
            $data['code'] = 0;
            $data['message'] = "获取成功";
            return json($data);
        } else {
            $data['code'] = '-1';
            $data['data'] = "";
            $data['message'] = "系统繁忙";
            return json($data, '-1');
        }
    }

    /**
     * 创建充值订单
     */
    public function createRechargeOrder()
    {
        $recharge_money = request()->post('recharge_money', 0);
        $out_trade_no = request()->post('out_trade_no', '');
        $form_id = request()->post('form_id', '');
        $type = request()->post('type', 0); //5:货款充值
        if (empty($recharge_money) || empty($out_trade_no)) {
            $data['message'] = "订单号或充值金额不能为空";
            $data['code'] = -1;
            return json($data);
        } else   {
            $member = new MemberService();
            $retval = $member->createMemberRecharge($recharge_money, $this->uid, $out_trade_no, $form_id, $type);
            if ($retval > 0) {
                $data['code'] = 0;
                $data['message'] = "订单创建成功";
                $data['data']['out_trade_no'] = $out_trade_no;
                return json($data);
            } else {
                $data['code'] = '-1';
                $data['data'] = "";
                $data['message'] = "系统繁忙";
                return json($data);
            }

        }
    }

    /**
     * 获取支付相关信息
     */
    public function getPayValue()
    {
        $config = new AddonsConfigService();
        $out_trade_no = request()->post('out_trade_no', '');
        if(strstr($out_trade_no,'QD')){
            $res = $this->getChannelPayValue($out_trade_no);
            return $res;
        }
        if (empty($out_trade_no)) {
            $data['code'] = 0;
            $data['message'] = '没有获取到支付信息';
            $data['data'] = null;
            return json($data);
        }
        $order_mdl = new VslOrderModel();
        $order_info = $order_mdl->field('presell_id, money_type, order_type, website_id, shop_id')->where(['out_trade_no' => $out_trade_no])->whereOr(['out_trade_no_presell' => $out_trade_no])->find();
        if($this->groupshopping){
            $group_server = new GroupShopping();
            $checkGroup = $group_server->checkGroupIsCanByOrder($out_trade_no);
            if($checkGroup < 0){
                return json(AjaxReturn($checkGroup));
            }
        }
        $pay = new UnifyPay();
        $pay_value = $pay->getPayInfo($out_trade_no);
        //初步思路，等待后台订单处理完成再去支付，本身是想加上php多线程的
        /*if(empty($pay_value['pay_money'])){
            $redis = new \Redis();
            $redis->connect('127.0.0.1',6379);
            while(!$order_create_status){
                $pay_info = $redis->get($out_trade_no);
            }
        }
        if($pay_info === 'fail'){
            $data['code'] = 0;
            $data['message'] = '订单秒杀异常，请重新下单';
            $data['data'] = '';
            return json($data);
        }elseif($pay_info === 'success'){
            $pay_value = $pay->getPayInfo($out_trade_no);
            //并且将该redis流水号的key删掉
            $redis->del($out_trade_no);
        }
        */
        if(empty($pay_value['pay_money'])){
            $redis = $this->connectRedis();
            $pay_info = $redis->get($out_trade_no);
            if(!empty($pay_info)){
                $pay_arr = unserialize($pay_info);
                $pay_value['create_time'] = $pay_arr['create_time'];
                $pay_value['order_type'] = 6;
                $pay_value['pay_money'] = $pay_arr['all_should_paid_amount'];
                if($pay_arr['all_should_paid_amount'] == 0){
                    $data['code'] = 2;
                    $data['message'] = '订单价格为0.00，无需再次支付!';
                    $data['data'] = null;
                    return json($data);
                }
                $pay_value['out_trade_no'] = $out_trade_no;
                if (empty($order_info)) {
                    $order_info['order_type'] = 6;
                    $order_info['website_id'] = $this->website_id;
                }
                $redis->del($out_trade_no);
            }
        }else{
            $checkPayStatus = $pay->checkPayStatus($out_trade_no);//检测订单是否已经支付
            if(!$checkPayStatus){
                $data['code'] = 2;
                $data['message'] = '订单已经支付或者订单价格为0.00，无需再次支付!';
                $data['data'] = null;
                return json($data);
            }
        }
        $config_service = new Config();
        $shop_config = $config_service->getShopConfig(0);
        $order_status = $this->getOrderStatusByOutTradeNo($out_trade_no);

        if (empty($pay_value)) {
            $data['code'] = 0;
            $data['message'] = '订单主体信息已发生变动';
            $data['data'] = null;
            return json($data);
        }
        // 订单关闭状态下是不能继续支付的
        if ($order_status == 5) {
            $data['code'] = 0;
            $data['message'] = '订单已关闭.00，无需再次支付!';
            $data['data'] = null;
            return json($data);
        }
        
        if($order_info['order_type'] == 7 && $order_info['money_type'] == 1){//预售订单并且已付定金
            $presell_mdl = new VslPresellModel();
            $pay_end_time = $presell_mdl->getInfo(['id' => $order_info['presell_id']], 'pay_end_time')['pay_end_time'];
            if(time() > $pay_end_time){
                $data['code'] = 0;
                $data['message'] = '订单支付时间已过期';
                $data['data'] = null;
                return json($data);
            }
        }elseif($order_info['order_type'] == 5){//拼团订单，关闭时间不同
            $info = $config->getAddonsConfig('groupshopping',$order_info['website_id']);
            $groupConfig = json_decode($info['value'], true);
            if(time() > $pay_value['create_time'] + $groupConfig['pay_time_limit'] * 60){
                $data['code'] = 0;
                $data['message'] = '订单支付时间已过期';
                $data['data'] = null;
                return json($data);
            }
        }elseif($order_info['order_type'] == 6){//秒杀
            $info = $config->getAddonsConfig('seckill',$order_info['website_id']);
            $seckill_config = json_decode($info['value'], true);
            if(time() > $pay_value['create_time'] + $seckill_config['pay_limit_time'] * 60){
                $data['code'] = 0;
                $data['message'] = '订单支付时间已过期';
                $data['data'] = null;
                return json($data);
            }
        }elseif($order_info['order_type'] == 8){//砍价
            $info = $config->getAddonsConfig('bargain',$order_info['website_id']);
            $bargain_config = json_decode($info['value'], true);
            if(time() > $pay_value['create_time'] + $bargain_config['pay_time_limit'] * 60){
                $data['code'] = 0;
                $data['message'] = '订单支付时间已过期';
                $data['data'] = null;
                return json($data);
            }
        }else{
            $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
            $zero2 = $pay_value['create_time'];
            if ($zero1 > ($zero2 + ($shop_config['order_buy_close_time'] * 60))) {
                $data['code'] = 0;
                $data['message'] = '订单支付时间已过期';
                $data['data'] = null;
                return json($data);
            }
        }
        //获取余额
        $member = new MemberAccount();
        $member_account = $member->getMemberAccount($this->uid); // 用户余额

        $password = $this->get_user_password();
        if(empty($password)){
            $data['data']['pay_password'] = 0;
        }else{
            $data['data']['pay_password'] = 1;
        }
        // 处理税费
        if ($order_info['order_type'] !== 7) {
            $taxs = $order_mdl->getSum(['out_trade_no'=> $out_trade_no], 'invoice_tax');
            $pay_value['pay_money'] += $taxs;
        }
        $data['code'] = 1;
        $data['message'] = '选择支付方式';
        $data['data']['pay_money'] = $pay_value['pay_money'];
        $data['data']['now_time'] = time();
        if($order_info['order_type'] == 7 && $order_info['money_type'] == 1){//预售订单并且已付定金
            $data['data']['end_time'] = $pay_end_time;
        }elseif($order_info['order_type'] == 5){//拼团订单
            $data['data']['end_time'] = $pay_value['create_time'] + $groupConfig['pay_time_limit'] * 60;
        }elseif($order_info['order_type'] == 6){
            $data['data']['end_time'] = $pay_value['create_time'] + $seckill_config['pay_limit_time'] * 60;
        }elseif($order_info['order_type'] == 8){
            $data['data']['end_time'] = $pay_value['create_time'] + $bargain_config['pay_time_limit'] * 60;
        }else{
            $data['data']['end_time'] = $zero2 + ($shop_config['order_buy_close_time'] * 60);
        }
        $data['data']['balance'] = $member_account['balance'];
        /*if (isset($isChain) && in_array($isChain, ['eth', 'eos'])) {
            $data['code'] = 1;
            $data['message'] = '请求成功';
            return json(array_merge($data, getBlockChain($this->uid, $this->website_id, $data['data']['pay_money'])));
        }*/
        return json($data);

    }

    /*
     * 用于渠道商支付
     * **/
    public function getChannelPayValue($out_trade_no)
    {
        if (empty($out_trade_no)) {
            $data['code'] = 0;
            $data['message'] = '没有获取到支付信息';
            $data['data'] = '';
            return json($data);
        }
        $pay = new UnifyPay();
        $pay_value = $pay->getChannelPayInfo($out_trade_no);
        if(empty($pay_value['pay_money'])){
            $redis = $this->connectRedis();
            $pay_info = $redis->get($out_trade_no);
            if(!empty($pay_info)){
                $pay_arr = unserialize($pay_info);
                $pay_value['create_time'] = $pay_arr['create_time'];
                $pay_value['pay_money'] = $pay_arr['all_should_paid_amount'];
                $pay_value['out_trade_no'] = $out_trade_no;
                $redis->del($out_trade_no);
            }
        }
        $addons_mdl = new AddonsConfigModel();
        $chanenl_setting_val = $addons_mdl->getInfo(['addons'=>'channel', 'website_id'=>$this->website_id],'value')['value'];
        $chanenl_setting_val_arr = json_decode($chanenl_setting_val,true);
        $shop_config['order_buy_close_time'] = $chanenl_setting_val_arr['channel_order_close_time']?:60;
        $order_status = $this->getOrderStatusByOutTradeNo($out_trade_no);

        if (empty($pay_value)) {
            $data['code'] = 0;
            $data['message'] = '订单主体信息已发生变动';
            $data['data'] = '';
            return json($data);
        }

        if ($pay_value['pay_status'] == 1) {
            // 订单已经支付
            $data['code'] = 0;
            $data['message'] = '订单已经支付或者订单价格为0.00，无需再次支付!';
            $data['data'] = '';
            return json($data);
        }

        // 订单关闭状态下是不能继续支付的
        if ($order_status == 5) {
            $data['code'] = 0;
            $data['message'] = '订单已关闭.00，无需再次支付!';
            $data['data'] = '';
            return json($data);
        }

        $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 > ($zero2 + ($shop_config['order_buy_close_time'] * 60))) {
            $data['code'] = 0;
            $data['message'] = '订单已关闭';
            $data['data'] = '';
            return json($data);
        } else {
            //获取余额
            $member = new MemberAccount();
            $member_account = $member->getMemberAccount($this->uid); // 用户余额

            $password = $this->get_user_password();
            if(empty($password)){
                $data['data']['pay_password'] = 0;
            }else{
                $data['data']['pay_password'] = 1;
            }
            $data['code'] = 1;
            $data['message'] = '选择支付方式';
            $data['data']['pay_money'] = $pay_value['pay_money'];
            $data['data']['end_time'] = $zero2 + ($shop_config['order_buy_close_time'] * 60);
            //判断商城开启的是哪种支付方式
            $addons_config_model = new AddonsConfigModel();
            $value = $addons_config_model->Query(['addons' => 'channel', 'website_id' => $this->website_id], 'value')[0];
            $value = json_decode($value, true);
            if ($value['pay_type'] == 0) {
                //商城支付方式
            $data['data']['balance'] = $member_account['balance'];
                $data['data']['is_proceeds'] = false;
            } elseif ($value['pay_type'] == 1) {
                //货款支付
                $data['data']['balance'] = $member_account['proceeds'];
                $data['data']['is_proceeds'] = true;
            }

            return json($data);
        }
    }
    /*
     * 连接redis
     * **/
    public function connectRedis(){
        $config = new WebConfig();
        $redis_config = $config->getRedisConfig(0);
        $host = '';
        $pwd = '';
        if($redis_config){
            $host = $redis_config['host'];
            $pwd = $redis_config['pass'];
        }
        $port = 6379;
        $redis = new \Redis();
        if($host && $pwd){
            if ($redis->connect($host, $port) == false) {
                return json(['code'=>-1, 'message'=>'redis服务连接失败']);
            }
            if ($redis->auth($pwd) == false) {
                return json(['code'=>-1, 'message'=>'redis密码错误']);
            }
        }else{
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
        }
        return $redis;
    }
    /**
     * 订单微信支付
     */
    public function wchatPay()
    {
//        $form_id = request()->post('form_id', '');
        $out_trade_no = request()->post('out_trade_no', '');
        // 支付来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
        $type = request()->post('type', 3);
        if (empty($out_trade_no)) {
            $data['code'] = 0;
            $data['data'] = '';
            $data['message'] = "没有获取到订单信息";
            return json($data);
        }
        if(strstr($out_trade_no, 'QD')){
            $qdpay_ment = new VslChannelOrderPaymentModel();
            $qdpayment_info = $qdpay_ment->getInfo(['out_trade_no'=>$out_trade_no]);
            if($qdpayment_info){
                $qdpay_ment->save(['pay_from'=>$type],['out_trade_no'=>$out_trade_no]);
            }
        }
        if(strstr($out_trade_no, 'eos')){
            $order_eos = new VslEosOrderPayMentModel();
            $order_eos->save(['pay_from'=>$type],['out_trade_no'=>"{$out_trade_no}",'type'=>1]);
        }
        $pay_ment = new VslOrderPaymentModel();
        $payment_info = $pay_ment->getInfo(['out_trade_no'=>$out_trade_no]);
        if($payment_info){
            $pay_ment->save(['pay_from'=>$type],['out_trade_no'=>$out_trade_no]);
        }
        $order_recharge = new VslMemberRechargeModel();
        $recharge_info = $order_recharge->getInfo(['out_trade_no'=>$out_trade_no],'*');
        $order = new VslOrderModel();
        $orderInfo = $order->getInfo(['out_trade_no'=>$out_trade_no],'shop_id,website_id,order_type');
        if($orderInfo['order_type'] == 5 && $this->groupshopping){
            $group_server = new GroupShopping();
            $checkGroup = $group_server->checkGroupIsCanByOrder($out_trade_no);
            if($checkGroup < 0){
                return json(AjaxReturn($checkGroup));
            }
        }
        $red_url = $this->realm_ip."/wapapi/pay/wchatUrlBack";
        $pay = new UnifyPay();
        if($type==1){
                $res = $pay->wchatPay($out_trade_no, 'JSAPI',$red_url);
                if($res["return_code"]  && $res["return_code"] == "SUCCESS" && $res["result_code"] && $res["result_code"] == "SUCCESS"){
                    $retval = $pay->getWxJsApi($res);
                    $data['data'] = json_decode($retval,true);
                    $data['code'] = 1;
                 //修改发票状态
                $this->paySuccess2UpdataInvoiceInfo($out_trade_no);

                return json($data);
            } else {
                $data['data'] = $res;
                $data['code'] = -1;
                $data['message'] = '支付失败,'.$res['err_code_des'];
                return json($data);
            }
        }
        if($type==2){
            if (strstr($out_trade_no, 'eos')) {//购买eos内存订单
                $call_url = urlencode($this->realm_ip . '/wap/pay/create?out_trade_no=' . $out_trade_no);
            }else{
                $call_url = urlencode($this->realm_ip . '/wap/pay/result?out_trade_no=' . $out_trade_no);
            }
            $res = $pay->wchatPay($out_trade_no, 'MWEB', $red_url);
            if($res["return_code"] && $res["return_code"] == "SUCCESS"){
                $res['mweb_url'] = $res['mweb_url']."&redirect_url=".$call_url;
                $data['data'] = $res;
                $data['data']['type'] = "h5";
                $data['code'] = 0;
                //修改发票状态
                $this->paySuccess2UpdataInvoiceInfo($out_trade_no);
                return json($data);
            }else{
                $data['code'] = -1;
                $data['message'] = '支付失败,'.$res['err_code_des'];
                return json($data);
            }
        }
        if($type==6){
            // todo... 添加小程序form_id
//            $pay_ment->save(['form_id' => $form_id], ['out_trade_no' => $out_trade_no, 'website_id' => $this->website_id]);
            $res = $pay->wchatPayMir($out_trade_no, 'JSAPI',$red_url);
            if($res["return_code"]  && $res["return_code"] == "SUCCESS"){
                $order = new VslOrderModel();
                $order_from = $order->getInfo(['out_trade_no|out_trade_no_presell' => $out_trade_no], 'website_id');
                if (empty($order_from)) {
                    $website_id = $recharge_info['website_id'];
                }else{
                    $website_id = $order_from['website_id'];
                }
                if (strstr($out_trade_no, 'eos')) {//购买eos内存订单
                    $order_eos = new VslOrderModel();
                    $website_id = $order_eos->getInfo(['out_trade_no'=>$out_trade_no],'website_id')['website_id'];
                }
                $auth = new WeixinAuthModel();
                $app_id = $auth->getInfo(['shop_id' => $this->instance_id, 'website_id' => $website_id],'authorizer_appid')['authorizer_appid'];
                $retval = $pay->getWxJsApiMir($res,$app_id);
                $data['data'] = json_decode($retval,true);
                $data['code'] = 1;
                return json($data);
            }else{
                $data['data'] = $res;
                $data['code'] = -1;
                $data['message'] = '支付失败,'.$res['err_code_des'];
                return json($data);
            }
        }
        if($type==4 || $type==5){
            $res = $pay->wchatPay($out_trade_no, 'APP',$red_url);
            if($res["return_code"] && $res["return_code"] == "SUCCESS"){
                $retval = $pay->getWxJsApiApp($res);;
                $data['data'] = json_decode($retval,true);
                $data['code'] = 1;
                return json($data);
            }else{
                $data['code'] = -1;
                $data['data'] = $res;
                $data['message'] = '支付失败,'.$res['err_code_des'];
                return json($data);
            }
        }
    }
	/**
     * GlobePay支付
     */
    public function GlobePay()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        // 支付来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
        $type = request()->post('type', 6);
        if (empty($out_trade_no)) {
            $data['code'] = 0;
            $data['data'] = '';
            $data['message'] = "没有获取到订单信息";
            return json($data);
        }
        if (strstr($out_trade_no, 'QD')) {
            $qdpay_ment = new VslChannelOrderPaymentModel();
            $qdpayment_info = $qdpay_ment->getInfo(['out_trade_no' => $out_trade_no]);
            if ($qdpayment_info) {
                $qdpay_ment->save(['pay_from' => $type], ['out_trade_no' => $out_trade_no]);
            }
        }
        if (strstr($out_trade_no, 'eos')) {
            $order_eos = new VslEosOrderPayMentModel();
            $order_eos->save(['pay_from' => $type], ['out_trade_no' => "{$out_trade_no}", 'type' => 1]);
        }
        $pay_ment = new VslOrderPaymentModel();
        $payment_info = $pay_ment->getInfo(['out_trade_no' => $out_trade_no]);
        if ($payment_info) {
            $pay_ment->save(['pay_from' => $type], ['out_trade_no' => $out_trade_no]);
        }
        $order_recharge = new VslMemberRechargeModel();
        $recharge_info = $order_recharge->getInfo(['out_trade_no' => $out_trade_no], '*');
        $order = new VslOrderModel();
        $orderInfo = $order->getInfo(['out_trade_no' => $out_trade_no], 'shop_id,website_id,order_type');
        if ($orderInfo['order_type'] == 5 && $this->groupshopping) {
            $group_server = new GroupShopping();
            $checkGroup = $group_server->checkGroupIsCanByOrder($out_trade_no);
            if ($checkGroup < 0) {
                return json(AjaxReturn($checkGroup));
            }
        }
        $red_url = $this->realm_ip . "/wapapi/pay/gpayUrlBack";
        $pay = new UnifyPay();
		$gbpay = new globalpay();
        if ($type == 6) {			
			$data = $pay->globePayMir($out_trade_no);			
			if ($data) {
                $user  = new UserModel();
				$openid = $user->getInfo(['uid'=>$this->uid],'mp_open_id')['mp_open_id'];
				$config_service = new WebConfig();
				$payconfig = $config_service->getGpayConfigMir($this->website_id);							
				if($payconfig['value']['currency'] == 'CNY'){
					$orderprice=$data['pay_money']*100;
				}else{
					$orderprice=(int)$data['pay_money'];
				}
				$param=array('url'=>$url,'description'=>$data['pay_body'],'price'=>$orderprice,'currency'=>$payconfig['value']['currency'],'notify_url'=>$red_url,'operator'=>$this->uid,'appid'=>$payconfig['value']['appid'],'customer_id'=>$openid);
				$res=$gbpay->getparams($payconfig['value'],$out_trade_no,$param);
				if($res['result_code']=='SUCCESS'){
					$data['data'] = $res;
					$data['code'] = 1;
					return json($data);
				}else{
					$data['code'] = -1;
					$data['data'] = [];
					$data['message'] = $res['return_msg'];
					return json($data);
				}
				
            } else {
                $data['code'] = -1;
                $data['data'] = [];
                $data['message'] = '支付失败';
                return json($data);
            }
        }else{
			$data = $pay->globePayMir($out_trade_no);			
			if ($data) {
				$config_service = new WebConfig();
				$payconfig = $config_service->getGpayConfig($this->website_id);							
				if($payconfig['value']['currency'] == 'CNY'){
					$orderprice=$data['pay_money']*100;
				}else{
					$orderprice=(int)$data['pay_money'];
				}
				$param=array('description'=>$data['pay_body'],'price'=>$orderprice,'currency'=>$payconfig['value']['currency'],'channel'=>'Wechat','notify_url'=>$red_url,'operator'=>$this->uid);
				if($type == 1){
					$res=$gbpay->getpayurl($payconfig['value'],$out_trade_no,$param,1,$this->realm_ip);
				}
				if($type == 2){
					$res=$gbpay->getpayurl($payconfig['value'],$out_trade_no,$param,2,$this->realm_ip);
				}
				if($res['result_code']=='SUCCESS'){
					$data['data'] = $res;
					$data['code'] = 1;
					return json($data);
				}else{
					$data['code'] = -1;
					$data['data'] = [];
					$data['message'] = $res['return_msg'];
					return json($data);
				}
			} else {
                $data['code'] = -1;
                $data['data'] = [];
                $data['message'] = '支付接口请求失败';
                return json($data);
            }
		}
    }
    /**
     * 支付宝支付
     */
    public function aliPay()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        if(empty($out_trade_no)){
            $out_trade_no = request()->get('out_trade_no', '');
        }
        $type = request()->post('type', '');
        if(empty($type)){
            $type = request()->get('type', 2);
        }
        if (empty($out_trade_no)) {
            $data['code'] = 0;
            $data['data'] = '';
            $data['message'] = "没有获取到订单信息";
            return json($data);
        }
        if(strstr($out_trade_no, 'QD')){
            $qdpay_ment = new VslChannelOrderPaymentModel();
            $qdpayment_info = $qdpay_ment->getInfo(['out_trade_no'=>$out_trade_no]);
            if($qdpayment_info){
                $qdpay_ment->save(['pay_from'=>$type],['out_trade_no'=>$out_trade_no]);
            }
        }
        if(strstr($out_trade_no, 'eos')){
            $order_eos = new VslEosOrderPayMentModel();
            $order_eos->save(['pay_from'=>$type],['out_trade_no'=>"{$out_trade_no}",'type'=>1]);
        }
        $pay_ment = new VslOrderPaymentModel();
        $payment_info = $pay_ment->getInfo(['out_trade_no'=>$out_trade_no]);
        if($payment_info){
            $pay_ment->save(['pay_from'=>$type],['out_trade_no'=>$out_trade_no]);
        }
        if($this->groupshopping){
            $group_server = new GroupShopping();
            $checkGroup = $group_server->checkGroupIsCanByOrder($out_trade_no);
            if($checkGroup < 0){
                return json(AjaxReturn($checkGroup));
            }
        }
        $notify_url = $this->realm_ip . "/wapapi/pay/aliUrlBack";
        if (strstr($out_trade_no, 'eos')) {//购买eos内存订单
            $return_url = $this->realm_ip . '/wap/pay/create';
        }else{
            $return_url = $this->realm_ip. "/wap/pay/result";
        }
        if($type==2) {
            $pay = new UnifyPay();
            $res = $pay->aliPayNewWap($out_trade_no, $notify_url, $return_url);
            if($res){
                $data['data'] = $res;
                $data['code'] = 1;
                //修改发票状态
                $this->paySuccess2UpdataInvoiceInfo($out_trade_no);
                return json($data);
            }else{
                $data['code'] = -1;
                $data['message'] = '支付失败,'.$res['sub_msg'];
                return json($data);
            }
        }
        if($type==4 || $type==5){
            $pay = new UnifyPay();
            $res = $pay->aliPayNewApp($out_trade_no, $notify_url, $return_url);
            if($res){
                $data['data'] = $res;
                $data['code'] = 1;
                return json($data);
            }else{
                $data['code'] = -1;
                $data['message'] = '支付失败,'.$res['sub_msg'];
                return json($data);
            }
        }
    }


    /**
     * 银行卡支付申请
     */
    public function tlPayApplyAgree()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        $id = request()->post('id', '');
        if (empty($out_trade_no)) {
            $data['code'] = 0;
            $data['data'] = '';
            $data['message'] = "没有获取到订单信息";
            return json($data);
        }
        $type = request()->post('type', '');
        if(empty($type)){
            $type = request()->get('type', 2);
        }
        $bank = new VslMemberBankAccountModel();
        $bank_info = $bank->getInfo(['id'=>$id]);
        if (empty($bank_info['agree_id'])) {
            $data['code'] = 0;
            $data['data'] = '';
            $data['message'] = "银行卡未签约";
            return json($data);
        }
        $pay_ment = new VslOrderPaymentModel();
        $payment_info = $pay_ment->getInfo(['out_trade_no'=>$out_trade_no]);
        if($payment_info){
            $pay_ment->save(['pay_from'=>$type],['out_trade_no'=>$out_trade_no]);
        }
        if(strstr($out_trade_no, 'eos')){
            $order_eos = new VslEosOrderPayMentModel();
            $order_eos->save(['pay_from'=>$type],['out_trade_no'=>"{$out_trade_no}",'type'=>1]);
        }
        if($this->groupshopping){
            $group_server = new GroupShopping();
            $checkGroup = $group_server->checkGroupIsCanByOrder($out_trade_no);
            if($checkGroup < 0){
                return json(AjaxReturn($checkGroup));
            }
        }
        $notify_url = $this->realm_ip . "/wapapi/pay/tlUrlBack";
        $pay = new UnifyPay();
        $res = $pay->payApplyAgree($id,$out_trade_no,$notify_url,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            if($res['trxstatus']==1999){
                $data['data']['thpinfo'] = $res['thpinfo'];
                $data['message'] = '验证码已发送';
                $data['code'] = 1;
                return json($data);
            }elseif($res['trxstatus']==0000){
                $data['code'] = 2;
                $data['message'] = '交易已完成';
                return json($data);
            }elseif($res['trxstatus']==2000 || $res['trxstatus']==2008){
                $data['code'] = 3;
                $data['message'] = '交易处理中';
                return json($data);
            }else{
                $data['code'] = -1;
                $data['message'] = $res['errmsg'];
                return json($data);
            }
        }else{
            $data['code'] = -1;
            $data['message'] = '支付申请失败';
            return json($data);
        }
    }
    /**
     * 银行卡支付重新获取支付短信
     */
    public function paySmsAgree()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        $thpinfo = htmlspecialchars_decode(stripslashes(request()->post('thpinfo', '')));
        $pay = new UnifyPay();
        $res = $pay->paySmsAgree($out_trade_no,$thpinfo,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            $data['code'] = 1;
            $data['message'] = '发送成功';
            return json($data);
        }else{
            $data['code'] = -1;
            $data['message'] = '发送失败';
            return json($data);
        }
    }
    /**
     * 银行卡支付申请确认
     */
    public function tlPay()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        $id = request()->post('id', '');
        $smscode = request()->post('smscode', '');
        $thpinfo = htmlspecialchars_decode(stripslashes(request()->post('thpinfo', '')));
        $pay = new UnifyPay();
        $res = $pay->payAgreeConfirm($id,$smscode,$thpinfo,$out_trade_no,$this->website_id);
        if($res['retcode']=='SUCCESS'){
          if($res['trxstatus']==0000){
                $data['code'] = 1;
                $data['message'] = '支付成功';
                return json($data);
            }else{
                $data['code'] = -1;
                $data['message'] = $res['errmsg'];
                return json($data);
            }
        }else{
            $data['code'] = -1;
            $data['message'] = '支付失败';
            return json($data);
        }
    }

    //余额支付
    public function balance_pay()
    {
        $member = new MemberAccount();
        $member_account = $member->getMemberAccount($this->uid); // 用户余额
        $balance = $member_account['balance'];
        $out_trade_no = request()->post('out_trade_no');
        $pay_money = request()->post('pay_money');
        if(empty($out_trade_no)){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
//        $pay_ment = new VslOrderPaymentModel();
//        $pay_ment->save(['form_id' => $form_id], ['out_trade_no' => $out_trade_no, 'website_id' => $this->website_id]);
        if($balance<$pay_money){
            $data['code'] = -1;
            $data['message'] = "余额不足。";
            return json($data);
        }else{
            try{
                if(strstr($out_trade_no, 'eos')){
                    $block = new Block();
                    $res = $block->balancePay($out_trade_no,$this->uid);
                    if($res>0){
                        $data['code'] = 0;
                        $data['message'] = "支付成功";
                        return json($data);
                    }else{
                        $data['code'] = 0;
                        $data['message'] = "支付失败";
                        return json($data);
                    }
                }else{
                    $order_service = new OrderService();
                    $order = new VslOrderModel();
                    $unify_pay_server = new UnifyPay();
                    if( strstr($out_trade_no, 'QD') ){
                        $res = $unify_pay_server->updateChannelPay($out_trade_no, 5, '');
                        $order = new VslChannelOrderModel();
                        $text = '渠道商采购';
                        $from_type = 27;
                    }else{ 
                        $order_info = $order->getInfo(['out_trade_no|out_trade_no_presell' => $out_trade_no], 'order_id, order_type');
                        $order_type = $order_info['order_type'];
                        if($order_type == 5){
                            if(!$this->groupshopping){
                                $data['code'] = 0;
                                $data['message'] = '拼团已关闭';
                                return json($data);
                            }else{
                                $group_server = new GroupShopping();
                                $checkGroup = $group_server->checkGroupIsCanByOrder($out_trade_no);
                                if($checkGroup < 0){
                                    return json(AjaxReturn($checkGroup));
                                }
                            }
                        }
                        if($order_info){
                            $res = $order_service->orderOnLinePay($out_trade_no, 5, 0, 1);
                        }
                        $from_type = 1;
                    }
                    if($res==1){
                        $account_flow = new MemberAccount();
                        if( !strstr($out_trade_no, 'QD') ){
                            $order_id_list = $order->field('order_id, pay_money, presell_id, money_type, order_money, final_money, shipping_money, invoice_tax,deduction_money')->where(['out_trade_no' => $out_trade_no])->whereor(['out_trade_no_presell' => $out_trade_no])->select();
                            foreach ($order_id_list as $k=>$v){
                                if($v['presell_id'] != 0 && $this->is_presell){
                                    $text = '预售';
                                }
                                if($v['presell_id'] != 0 && $this->is_presell && $v['money_type'] == 2){
                                    $presell_mdl = new VslPresellModel();
                                    $presell_info = $presell_mdl->getInfo(['id'=>$v['presell_id']],'*');
                                    $allmoney = $presell_info['allmoney'];
                                    $firstmoney = $presell_info['firstmoney'];
//                                $firstmoney = $v['order_money'] - ($v['final_money'] - $v['shipping_money']);
                                    $totalmoney = $allmoney*$v['pay_money']/$firstmoney;
                                    $deduct_point = $v['deduction_money'] > 0 ? $v['deduction_money'] : 0;
                                    $v['pay_money'] = $totalmoney - $v['pay_money'] + $v['shipping_money'] + $v['invoice_tax'] - $deduct_point;//余额流水加上税费,抵扣积分
                                }
                                if ($v['presell_id'] == 0) {
                                    $v['pay_money'] = $v['pay_money'] + $v['invoice_tax'];
                                }
                                $account_flow->addMemberAccountData(2, $this->uid, 0, $v['pay_money'], $from_type, $v['order_id'], '商城'.$text.'订单，余额支付');
                            }
                        }else{
                            $order_id_list = $order->field('order_id, pay_money')->where(['out_trade_no'=>$out_trade_no])->select();
                            foreach ($order_id_list as $k=>$v){
                                $account_flow->addMemberAccountData(2, $this->uid, 0, $v['pay_money'], $from_type, $v['order_id'], '商城'.$text.'订单，余额支付');
                            }
                        }
                        $this->paySuccess2UpdataInvoiceInfo($out_trade_no);
                        $data['code'] = 0;
                        $data['message'] = "支付成功";
                        return json($data);
                    }else{
                        $data['code'] = -1;
                        $data['message'] = "支付失败";
                        return json($data);
                    }
                }
                //修改账户余额
            } catch (\Exception $e) {
                $data['code'] = -2;
                $data['message'] = $e->getMessage();
                return json($data);
            }
        }
    }
    //货款支付(渠道商采购用)
    public function proceeds_pay()
    {
//        $form_id = request()->post('form_id', '');
        $out_trade_no = request()->post('out_trade_no');
        $pay_money = request()->post('pay_money');
        if (empty($out_trade_no)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $member = new MemberAccount();
        $member_account = $member->getMemberAccount($this->uid); // 用户货款
        $balance = $member_account['proceeds'];
        $pay_ment = new VslOrderPaymentModel();
//        $pay_ment->save(['form_id' => $form_id], ['out_trade_no' => $out_trade_no, 'website_id' => $this->website_id]);
        if ($balance < $pay_money) {
            $data['code'] = -1;
            $data['message'] = "余额不足。";
            return json($data);
        } else {
            try {
                $order = new VslChannelOrderModel();
                $unify_pay_server = new UnifyPay();
                if (strstr($out_trade_no, 'QD')) {
                    $res = $unify_pay_server->updateChannelPay($out_trade_no, 5, '');
                    $text = '渠道商采购';
                    $from_type = 27;
                }
                if ($res == 1) {
                    $account_flow = new MemberAccount();
                    $order_id_list = $order->field('order_id, pay_money')->where(['out_trade_no' => $out_trade_no])->select();
                    foreach ($order_id_list as $k => $v) {
                        $account_flow->addMemberAccountData(5, $this->uid, 0, $v['pay_money'], $from_type, $v['order_id'], '商城' . $text . '订单，货款支付');
                    }
                    $data['code'] = 0;
                    $data['message'] = "支付成功";
                    return json($data);
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
                exit;
                $data['code'] = -2;
                $data['message'] = "服务器内部错误。";
                return json($data);
            }
        }
    }
    //货到付款
    public function dPay(){
        $out_trade_no = request()->post('out_trade_no');
        $order = new VslOrderModel();
        
        //如果是预售订单不支持货到付款
        $order_info = $order->getInfo(['out_trade_no'=>$out_trade_no],'order_type,order_id');
        if($order_info['order_type'] == 7){
            $data['code'] = -1;
            $data['message'] = "预售订单不支持货到付款";
            return json($data);
        }
        $res = $order->save(['payment_type'=>4,'order_status'=>1],['out_trade_no'=>$out_trade_no,'website_id'=>$this->website_id]);
        
        if($res){
            //订单提交成功 佣金写入
            $order_service = new OrderService();
            //延时执行，避免并发写入 ，后期优化定时任务
            sleep(2);
            $res=  $order_service->orderOnLinePay($out_trade_no, 4);

            //修改发票状态
            if(getAddons('invoice', $this->website_id)) {
                $invoice = new InvoiceServer();
                $invoice->updateOrderStatusByOutTradeNo($out_trade_no, 1);
            }
            $data['code'] = 0;
            $data['message'] = "提交成功";
        }else{
            $data['code'] = -1;
            $data['message'] = "服务器内部错误";
        }
        return json($data);
    }
    //eth支付
    public function ethPay(){
        $out_trade_no = request()->post('out_trade_no', '');
        $password = request()->post('password', '');
        $site = new Block();
        $site_info = $site->getBlockChainSite($this->website_id);
        $gas = $site_info['eth_gwei'];
        if (empty($out_trade_no)) {
            $data['code'] = 0;
            $data['data'] = '';
            $data['message'] = "没有获取到订单信息";
            return json($data);
        }
        $order = new VslOrderModel();
        $order_info1 = $order->getInfo(['out_trade_no'=>$out_trade_no],'order_status,order_id');
        $order_info2 = $order->getInfo(['out_trade_no_presell'=>$out_trade_no],'order_status,order_id');
        if($order_info1){
            if ($order_info1['order_status']==5) {
                $data['code'] = 0;
                $data['data'] = '';
                $data['message'] = "订单已关闭";
                return json($data);
            }
            $block = new Block();
            $res = $block->ethPay($out_trade_no,$password,$gas,$this->uid,$this->realm_ip . "/wapapi/pay/ethPayUrlBack",'',2);
            if($res>0){
                $order->save(['payment_type'=>16,'pay_status'=>3,'order_status'=>6],['out_trade_no'=>$out_trade_no]);
                $data['message'] = '支付处理中';
                $data['code'] = 1;
                return json($data);
            }else{
                $order_service = new OrderService\Order();
                $order_service->orderClose($order_info1['order_id']);
                $data['code'] = -1;
                $data['message'] = getErrorInfo($res);
                return json($data);
            }
        }
        if($order_info2){
            if ($order_info2['order_status']==5) {
                $data['code'] = 0;
                $data['data'] = '';
                $data['message'] = "订单已关闭";
                return json($data);
            }
            $block = new Block();
            $res = $block->ethPay($out_trade_no,$password,$gas,$this->uid,$this->realm_ip . "/wapapi/pay/ethPayUrlBack",'',2);
            if($res>0){
                $order->save(['payment_type_presell'=>16,'pay_status'=>3,'order_status'=>6],['out_trade_no_presell'=>$out_trade_no]);
                $data['message'] = '支付处理中';
                $data['code'] = 1;
                return json($data);
            }else{
                $order_service = new OrderService\Order();
                $order_service->orderClose($order_info2['order_id']);
                $data['code'] = -1;
                $data['message'] = getErrorInfo($res);
                return json($data);
            }
        }
    }
    //eos支付
    public function eosPay(){
        $out_trade_no = request()->post('out_trade_no', '');
        $password = request()->post('password', '');
        if (empty($out_trade_no)) {
            $data['code'] = 0;
            $data['data'] = '';
            $data['message'] = "没有获取到订单信息";
            return json($data);
        }
        $order = new VslOrderModel();
        $order_info1 = $order->getInfo(['out_trade_no'=>$out_trade_no],'order_status,order_id');
        $order_info2 = $order->getInfo(['out_trade_no_presell'=>$out_trade_no],'order_status,order_id');
        if($order_info1){
            if ($order_info1['order_status']==5) {
                $data['code'] = 0;
                $data['data'] = '';
                $data['message'] = "订单已关闭";
                return json($data);
            }
            $block = new Block();
            $res = $block->eosPay($out_trade_no,$password,$this->uid,$this->realm_ip . "/wapapi/pay/eosPayUrlBack",'',2);
            if($res['code'] > 0 || $res> 0){
                $order->save(['payment_type'=>17,'pay_status'=>3,'order_status'=>6],['out_trade_no'=>$out_trade_no]);
                $data['message'] = '支付处理中';
                $data['code'] = 1;
                return json($data);
            }else{
                $order_service = new OrderService\Order();
                $order_service->orderClose($order_info1['order_id']);
                $data['code'] = -1;
                $data['message'] = $res['msg'];
                return json($data);
            }
        }
        if($order_info2){
            if ($order_info2['order_status']==5) {
                $data['code'] = 0;
                $data['data'] = '';
                $data['message'] = "订单已关闭";
                return json($data);
            }
            $block = new Block();
            $res = $block->eosPay($out_trade_no,$password,$this->uid,$this->realm_ip . "/wapapi/pay/eosPayUrlBack",'',2);
            if($res>0){
                $order->save(['payment_type_presell'=>17,'pay_status'=>3,'order_status'=>6],['out_trade_no_presell'=>$out_trade_no]);
                $data['message'] = '支付处理中';
                $data['code'] = 1;
                return json($data);
            }else{
                $order_service = new OrderService\Order();
                $order_service->orderClose($order_info2['order_id']);
                $data['code'] = -1;
                $data['message'] = getErrorInfo($res);
                return json($data);
            }
        }
    }
    /**
     * 根据外部交易号查询订单状态，订单关闭状态下是不能继续支付的
     *
     * @param unknown $out_trade_no
     * @return number
     */
    public function getOrderStatusByOutTradeNo($out_trade_no)
    {
        $order = new OrderService();
        $order_status = $order->getOrderStatusByOutTradeNo($out_trade_no);
        if (! empty($order_status)) {
            return $order_status['order_status'];
        }
        return 0;
    }


    //判断支付密码是否正确
    public function check_pay_password(){

        $real_password = $this->get_user_password();
        $password = request()->post('password', '');

        if($real_password != md5($password)){
            $data['code'] = '-1';
            $data['data'] = '';
            $data['message'] = "支付密码错误";
            return json($data);
        }else{
            $data['code'] = 0;
            $data['message'] = "验证成功";
            $data['data'] = '';
            return json($data);
        }
    }

    //提现
    public function withdraw()
    {

        $uid = $this->uid;
        $withdraw_no = 'BT'.time() . rand(111, 999);
        $bank_account_id = request()->post('bank_account_id', '');
        $cash = request()->post('cash', '');
        $type = request()->post('type', '');//1银行卡2微信3支付宝
        $password = request()->post('password', '');
//        $form_id = request()->post('form_id', ''); // 小程序模板消息

        //验证是否开启通联
        $config = new ConfigModel();
        $config_info = $config->getInfo(['key'=>'TLPAY','website_id'=>$this->website_id]);
        $info= json_decode($config_info['value'], true);
        if(!empty($info) && $config_info['is_use'] == 0 && $type == 1){
            $data['code'] = '-1';
            $data['message'] = "请先开启通联配置";
            return json($data);
        }

        //先验证支付密码
        $real_password = $this->get_user_password();
        if(empty($real_password)){
            $data['code'] = '-1';
            $data['message'] = "请先设置支付密码";
            return json($data);
        }
        //判断账号体系，若是第三种并且设置了不绑定手机 就不验证密码
        $website = new WebSiteModel();
        $webste_info = $website->getInfo(['website_id' => $this->website_id], 'account_type, is_bind_phone');
        if($webste_info['account_type'] != 3 || ($webste_info['account_type'] == 3 && $webste_info['is_bind_phone'] == 1)){
            if(empty($type) || (empty($bank_account_id) && $type!=2) || empty($cash) || empty($password)){
                $data['code'] = '-1';
                $data['message'] = "提交参数有误";
                return json($data);
            }

            if($real_password != md5($password)){
                $data['code'] = '-1';
                $data['message'] = "支付密码错误";
                return json($data);
            }
        }
        $shop_id = $this->instance_id;
        $member = new MemberService();
        $retval = $member->addMemberBalanceWithdraw($shop_id, $withdraw_no, $uid, $bank_account_id, $cash, $type);
        if ($retval > 0) {
            $data['code'] = 0;
            $data['message'] = "提交申请成功";
            return json($data);
        } else {
            $data['code'] = '-1';
            $data['message'] = getErrorInfo($retval);
            return json($data);
        }

    }
    //提现表单页
    public function withdraw_form(){
        $user = new usermodel();
        $condition['uid'] = $this->uid;
        $user_info = $user->getInfo($condition,'payment_password,wx_openid,mp_open_id');
        $member = new MemberService();
        $member_info = $member->getMemberDetail();
        $data['code'] = 0;
        $data['data']['balance'] = $member_info['balance'];
        //提现相关设置
        $config_service = new WebConfig();
        $list = $config_service->getBalanceWithdrawConfig(0);
        $data['data']['balance'] = $member_info['balance'];
        $data['data']['is_start'] = $list['is_use'];
        $data['data']['withdraw_cash_min'] = $list['value']['withdraw_cash_min']?$list['value']['withdraw_cash_min']:'';
        if(empty($user_info['payment_password'])){
            $data['data']['set_password'] = 1;
        }else{
            $data['data']['set_password'] = 0;
        }
        $data['data']['is_bank'] = 0;
        $data['data']['is_alipay'] = 0;
        $data['data']['is_wpy'] = 0;
        $data['data']['wx_openid'] = "";
        if($list['value']['withdraw_message']){
            $withdraw_message = explode(',',$list['value']['withdraw_message']);
            if(in_array('1',$withdraw_message)){
                $data['data']['is_bank'] = 1;
            }
            if(in_array('4',$withdraw_message)){
                $data['data']['is_bank'] = 1;
            }
            if(in_array('3',$withdraw_message)){
                $alipay = $config_service->getConfig(0,'ALIPAY');
                $data['data']['is_alipay'] = ($alipay['is_use']==1)?1:0;
            }
            if(in_array('2',$withdraw_message)){
                $wpy = $config_service->getConfig(0,'WPAY');
                $data['data']['is_wpy'] = ($wpy['is_use']==1)?1:0;
                if($data['data']['is_wpy']==1){
                    if($user_info['wx_openid']){
                        $data['code'] = 1;
                        $data['data']['wx_openid'] = $user_info['wx_openid'];
                    }else{
                        $data['code'] = 1;
                        $data['data']['wx_openid'] = $user_info['mp_open_id'];
                    }
                }
            }
        }
        return json($data);
    }


    //获取用户支付密码
    public function get_user_password(){
        $user = new usermodel();
        $condition['uid'] = $this->uid;
        $user_password = $user->getInfo($condition,'payment_password');
        return $user_password['payment_password'];
    }
    //获取用户支付明文密码
    public function getPassword(){
        $user = new usermodel();
        $condition['uid'] = $this->uid;
        $user_password = $user->getInfo($condition,'plain_password');
        return $user_password['plain_password'];
    }

    //余额流水
    public function balancewater()
    {

        $member = new MemberService();
        $uid = $this->uid;
        $page_index = request()->post('page_index')?request()->post('page_index'):1;
        $condition['nmar.uid'] = $uid;
        $condition['nmar.account_type'] = 2;
        $list = $member->getAccountLists($page_index,PAGESIZE, $condition);
        $data['data'] = $list;
        $data['data']['page_index'] = $page_index;
        $data['code'] = 0;
        return json($data);
    }
    //货款流水
    public function proceedsWater()
    {
        $member = new MemberService();
        $uid = $this->uid;
        $page_index = request()->post('page_index') ? request()->post('page_index') : 1;
        $condition['nmar.uid'] = $uid;
        $condition['nmar.account_type'] = 5;
        $list = $member->getAccountLists($page_index, PAGESIZE, $condition);
        $data['data'] = $list;
        $data['data']['page_index'] = $page_index;
        $data['code'] = 0;
        return json($data);
    }
    /*
     * 余额流水详情
     * */
    public function balanceDetail(){
        $member = new MemberService();
        $id = request()->post('id', '');
        $condition['nmar.id'] = $id;
        $list = $member->getAccountLists(1,0, $condition);
        $data['code'] = 0;
        $data['data'] = $list['data'][0];
        return json($data);
    }
    //店铺流水
    public function integralWater()
    {
        $shop_id = $this->instance_id;
        $condition['nmar.shop_id'] = $shop_id;
        $condition['nmar.uid'] = $this->uid;
        $condition['nmar.account_type'] = 1;
        // 查看用户在该商铺下的积分消费流水
        $page_index = $_REQUEST['page_index'] ? $_REQUEST['page_index'] : 1;
        $member_point_list = $this->getAccountList($page_index, 0, $condition);
        // 查看积分总数
        $member = new MemberService();
        $menber_info = $member->getMemberDetail();
        $data['point'] = $menber_info['point'];
        $data['point_detail'] = $this->object2array($member_point_list);
        foreach ($data['point_detail']['data'] as $k=>$v){
            if($v['sign']==1 && $v['from_type'] != 34 && $v['from_type'] != 35 && $v['from_type'] != 50 && $v['from_type'] != 51 && $v['from_type'] != 52){
                $data['point_detail']['data'][$k]['number'] = "+".$v['number'];
            }
        }
        $data['point_detail']['page_index'] = $page_index;
        $result['code'] = 0;
        $result['data'] = $data;

        return json($result);
    }

    //获取优惠券列表
    public function getcouplist()
    {

        $state = request()->post('state') ? request()->post('state') : 1;
        $page_index = request()->post('page_index')?request()->post('page_index'):1;
        $page_size = request()->post('page_size')?request()->post('page_size'):PAGESIZE;
        $start = ($page_index-1)*$page_size;

        if($state==1){
            $sql = "select a.*,b.`coupon_id`,b.`uid`,b.`state`,c.`shop_name`,c.`shop_id` from `vsl_coupon_type` as a left JOIN  `vsl_coupon` as b on a.coupon_type_id = b.coupon_type_id left join `vsl_shop` as c on b.`shop_id` = c.`shop_id` AND a.`website_id`= c.`website_id` where b.`uid` = " . $this->uid . " and a.`website_id` = " . $this->website_id . " and b.`state` = " . $state ." and a.end_time > " .time();
            $count = Db::query($sql);
            $sql = "select a.*,b.`coupon_id`,b.`uid`,b.`state`,c.`shop_name`,c.`shop_id` from `vsl_coupon_type` as a left JOIN  `vsl_coupon` as b on a.coupon_type_id = b.coupon_type_id left join `vsl_shop` as c on b.`shop_id` = c.`shop_id` AND a.`website_id`= c.`website_id` where b.`uid` = " . $this->uid . " and a.`website_id` = " . $this->website_id . " and b.`state` = " . $state ." and a.end_time > " .time() ." limit $start , $page_size";
            $result = Db::query($sql);
        }
        if($state==2){
            $sql = "select a.*,b.`coupon_id`,b.`uid`,b.`state`,c.`shop_name`,c.`shop_id` from `vsl_coupon_type` as a left JOIN  `vsl_coupon` as b on a.coupon_type_id = b.coupon_type_id left join `vsl_shop` as c on b.`shop_id` = c.`shop_id` AND a.`website_id`= c.`website_id` where b.`uid` = " . $this->uid . " and a.`website_id` = " . $this->website_id . " and b.`state` = " . $state;
            $count = Db::query($sql);
            $sql = "select a.*,b.`coupon_id`,b.`uid`,b.`state`,c.`shop_name`,c.`shop_id` from `vsl_coupon_type` as a left JOIN  `vsl_coupon` as b on a.coupon_type_id = b.coupon_type_id left join `vsl_shop` as c on b.`shop_id` = c.`shop_id` AND a.`website_id`= c.`website_id` where b.`uid` = " . $this->uid . " and a.`website_id` = " . $this->website_id . " and b.`state` = " . $state ." limit $start , $page_size";
            $result = Db::query($sql);
        }
        if($state==3){
            $sql = "select a.*,b.`coupon_id`,b.`uid`,b.`state`,c.`shop_name`,c.`shop_id` from `vsl_coupon_type` as a left JOIN  `vsl_coupon` as b on a.coupon_type_id = b.coupon_type_id left join `vsl_shop` as c on b.`shop_id` = c.`shop_id` AND a.`website_id`= c.`website_id` where b.`uid` = " . $this->uid . " and a.`website_id` = " . $this->website_id . " and a.end_time < " .time();
            $count = Db::query($sql);
            $sql = "select a.*,b.`coupon_id`,b.`uid`,b.`state`,c.`shop_name`,c.`shop_id` from `vsl_coupon_type` as a left JOIN  `vsl_coupon` as b on a.coupon_type_id = b.coupon_type_id left join `vsl_shop` as c on b.`shop_id` = c.`shop_id` AND a.`website_id`= c.`website_id` where b.`uid` = " . $this->uid . " and a.`website_id` = " . $this->website_id . " and a.end_time < " .time() ." limit $start , $page_size";
            $result = Db::query($sql);
        }

        //拼接优惠券名 状态
        foreach ($result as $k=>$v){

            $range_name = $v['range_type'] > 0 ? "全部商品" : "部分商品";
            if ($v['shop_id'] == 0) {
                $result[$k]['show_name'] = ($v['shop_range_type']==1?"自营店-":"全平台-") . $range_name;
            } else {
                $result[$k]['show_name'] = $v['shop_name'] . "-" . $range_name;
            }
            $result[$k]['state'] = $state;
        }

        $total_count = count($count);
        $total_page = ceil($total_count/$page_size);
        $data['data']['page_index'] = $page_index;
        $data['data']['total_page'] = $total_page;
        $data['data']['total_count'] = $total_count;
        $data['data']['list'] = $result;
        $data['code'] = 0;
        return json($data);
    }

    //提现账户列表
    public function bank_account()
    {
        $member = new MemberService();
        $account_list = $member->getMemberBankAccount();
        if($account_list){
            $bank = new VslBankModel();
            foreach ($account_list as $k=>$v){
                if($v['bank_code']){
                    $info = $bank->getInfo(['bank_code'=>$v['bank_code']],'*');
                    $account_list[$k]['bank_iocn']= $info['bank_iocn'];
                    if($v['bank_type']=='00'){
                        $account_list[$k]['once_money']= $info['deposit_once'];
                        $account_list[$k]['day_money']= $info['deposit_day'];
                    }
                    if($v['bank_type']=='02'){
                        $account_list[$k]['once_money']= $info['credit_once'];
                        $account_list[$k]['day_money']= $info['credit_day'];
                    }
                }else{
                    $account_list[$k]['bank_iocn'] = '';
                }
            }
        }
        $data['code'] =1;
        $data['data'] = $account_list;
        return json($data);
    }
    //银行账户列表
    public function tl_bank_account()
    {
        $member = new MemberService();
        $account_list = $member->getMemberBankAccount(0,['type'=>['in','1,4']]);
        if($account_list){
            $bank = new VslBankModel();
            foreach ($account_list as $k=>$v){
                if($v['bank_code']){
                    $info = $bank->getInfo(['bank_code'=>$v['bank_code']],'*');
                    $account_list[$k]['bank_iocn']= $info['bank_iocn'];
                    $account_list[$k]['open_bank']= $info['bank_short_name'];
                }else{
                    $account_list[$k]['bank_iocn'] = '';
                }
            }
        }
        $data['code'] =0;
        $data['data'] = $account_list;
        return json($data);
    }
    //银行列表
    public function bank_list()
    {
        $bank = new VslBankModel();
        $bank_list = $bank->getQuery([], '*', 'sort asc');
        if (empty($bank_list))
        {
            $resBank = $bank->setBankList();
            if ($resBank)
            {
                $bank_list = $bank->getQuery([], '*', 'sort asc');
            }
        }
        $data['data'] = $bank_list;
        $data['code'] = 1;
        return json($data);
    }
    //新增提现账户
    public function add_bank_account()
    {
        $member = new MemberService();
        $uid = $this->uid;
        $type = request()->post('type', '1');
        $account_number = request()->post('account_number', '');
        $bank_code = request()->post('bank_code', '');
        $bank_type = request()->post('bank_type', '00');
        $bank_username = request()->post('realname', '');
        $bank_card = request()->post('bank_card', '');
        $mobile = request()->post('mobile', '');
        $validdate = request()->post('valid_date', '');
        $cvv2 = request()->post('cvv2', '');
        $bank_name = request()->post('bank_name', '');
        $bank_account = new VslMemberBankAccountModel();
        $info = $bank_account->getInfo(['account_number'=>$account_number,'uid'=>$this->uid]);
        if($info){
            $data['message'] = '该账号已存在';
            $data['code'] = -1;
            return json($data);
        }
        if($type==1 || $type==4){
            $url = "https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?cardNo=".$account_number."&cardBinCheck=true";
            $result =GetCurl($url);
            if($result['validated'] == false){
                $data['message'] = '银行卡号信息有误，请核实';
                $data['code'] = -1;
                return json($data);
            }
        }
        if($type==1){
            $pay = new UnifyPay();
            $res = $pay->tlSigning($bank_type,$account_number,$bank_card,$bank_username,$mobile,$validdate,$cvv2,$this->uid,$this->website_id);
            if($res['retcode']=='SUCCESS'){
                if($res['trxstatus']==1999){
                    $data['data']['thpinfo'] =$res['thpinfo'];
                    $data['message'] = '验证码已发送';
                    $data['code'] = 1;
                    return json($data);
                }else{
                    $data['message'] =$res['errmsg'];
                    $data['code'] = -2;
                    return json($data);
                }
            }else{
                $data['code'] = -2;
                $data['message'] = $res['retmsg'];
                return json($data);
            }
        }else{
            $retval = $member->addMemberBankAccount($uid, $type, $account_number,$bank_code,$bank_type,$bank_username,$bank_card,$bank_name,$mobile,$validdate,$cvv2);
            if ($retval > 0) {
                $data['code'] = 0;
                $data['message'] = "添加成功";
                return json($data);
            } else {
                $data['code'] = -1;
                $data['message'] = "添加失败";
                return json($data);
            }
        }
    }

    /**
     * 银行卡用户签约重发签约验证码
     */
    public function tlSigning()
    {
        $accttype = request()->post('bank_type', '00');
        $acctno = request()->post('account_number', '');
        $idno = request()->post('bank_card', '');
        $acctname = request()->post('realname', '');
        $mobile = request()->post('mobile', '');
        $validdate = request()->post('validdate', '');
        $cvv2 = request()->post('cvv2', '');
        $thpinfo = htmlspecialchars_decode(stripslashes(request()->post('thpinfo', '')));
        $pay = new UnifyPay();
        $res = $pay->tlAgreeSms($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$thpinfo,$this->uid,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            $data['message'] = '短信已发送';
            $data['code'] = 1;
            return json($data);
        }else{
            $data['code'] = -1;
            $data['message'] = $res['errmsg'];
            return json($data);
        }
    }

    /**
     * 银行卡用户签约申请确认
     */
    public function tlAgreeSigning()
    {
        $account_id = request()->post('account_id', '');
        $account_number = request()->post('account_number', '');
        $bank_code = request()->post('bank_code', '');
        $bank_type = request()->post('bank_type', '00');
        $bank_username = request()->post('realname', '');
        $bank_card = request()->post('bank_card', '');
        $mobile = request()->post('mobile', '');
        $validdate = request()->post('valid_date', '');
        $cvv2 = request()->post('cvv2', '');
        $smscode = request()->post('smscode', '');
        $thpinfo = htmlspecialchars_decode(stripslashes(request()->post('thpinfo', '')));
        $pay = new UnifyPay();
        $res = $pay->tlAgreeSigning($bank_type, $account_number, $bank_card, $bank_username, $mobile, $validdate, $cvv2, $smscode, $thpinfo, $this->uid, $this->website_id);
        if ($res['retcode'] == 'SUCCESS') {
            if ($res['trxstatus'] == '0000') {
                if ($bank_code) {
                    $bank = new VslBankModel();
                    $open_bank = $bank->getInfo(['bank_name' => $res['bankname']], 'bank_short_name')['bank_short_name'];
                }
                $bank_account = new VslMemberBankAccountModel();
                $info = $bank_account->getInfo(['account_number' => $account_number, 'uid' => $this->uid]);
                if ($account_id) {
                    $bank_account->save(['cvv2' => $cvv2, 'type' => 1, 'valid_date' => $validdate, 'mobile' => $mobile, 'bank_type' => $bank_type, 'realname' => $bank_username, 'bank_card' => $bank_card, 'bank_code' => $res['bankcode'], 'open_bank' => $res['bankname'], 'agree_id' => $res['agreeid'], 'modify_date' => time(), 'account_number' => $account_number], ['id' => $account_id]);
                } elseif ($info) {
                    $bank_account->save(['cvv2' => $cvv2, 'valid_date' => $validdate, 'mobile' => $mobile, 'bank_type' => $bank_type, 'realname' => $bank_username, 'bank_card' => $bank_card, 'bank_code' => $res['bankcode'], 'open_bank' => $res['bankname'], 'agree_id' => $res['agreeid'], 'modify_date' => time(), 'account_number' => $account_number], ['account_number' => $account_number, 'uid' => $this->uid]);
                } else {
                    $bank_account->save(['uid' => $this->uid, 'cvv2' => $cvv2, 'valid_date' => $validdate, 'mobile' => $mobile, 'bank_type' => $bank_type, 'realname' => $bank_username, 'bank_card' => $bank_card, 'bank_code' => $res['bankcode'], 'open_bank' => $res['bankname'], 'agree_id' => $res['agreeid'], 'account_number' => $account_number, 'type' => 1, 'create_date' => time(), 'website_id' => $this->website_id]);
                }
                $data['message'] = '签约成功';
                $data['code'] = 1;
            } else {
                $data['code'] = -1;
                $data['message'] = $res['errmsg'];
            }
            return json($data);
        } else {
            $data['code'] = -1;
            $data['message'] = $res['retmsg'];
            return json($data);
        }
    }

    /**
     * 银行卡用户解绑银行卡
     */
    public function tlUntying()
    {
        $id = request()->post('id', '');
        $pay = new UnifyPay();
        $res = $pay->tlUntying($id,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            $member = new VslMemberBankAccountModel();
            $member->delData(['id'=>$id]);
            $data['message'] = '解绑成功';
            $data['code'] = 1;
            return json($data);
        }else{
            $data['code'] = -1;
            $data['message'] = $res['errmsg'];
            return json($data);
        }
    }

    //编辑提现账户
    public function update_account()
    {
        $member = new MemberService();
        $account_id = request()->post('account_id','');
        $type = request()->post('type', '');
        $account_number = request()->post('account_number', '');
        $bank_code = request()->post('bank_code', '');
        $bank_type = request()->post('bank_type', '00');
        $bank_username = request()->post('realname', '');
        $bank_card = request()->post('bank_card', '');
        $mobile = request()->post('mobile', '');
        $validdate = request()->post('valid_date', '');
        $cvv2 = request()->post('cvv2', '');
        $bank_name = request()->post('bank_name', '');
        if($type==1 || $type==4){
            $url = "https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?cardNo=".$account_number."&cardBinCheck=true";
            $result =GetCurl($url);
            if($result['validated'] == false){
                $data['message'] = '查询不到该卡号信息';
                $data['code'] = -1;
                return json($data);
            }
        }
        if($type==1){
            $pay = new UnifyPay();
            $res = $pay->tlSigning($bank_type,$account_number,$bank_card,$bank_username,$mobile,$validdate,$cvv2,$this->uid,$this->website_id);
            if($res['retcode']=='SUCCESS'){
                if($res['trxstatus']==1999){
                    $data['data']['thpinfo'] =$res['thpinfo'];
                    $data['message'] = '验证码已发送';
                    $data['code'] = 1;
                    return json($data);
                }else{
                    $data['message'] =$res['errmsg'];
                    $data['code'] = -2;
                    return json($data);
                }
            }else{
                $data['code'] = -2;
                $data['message'] = $res['retmsg'];
                return json($data);
            }
        }else{
            $retval = $member->updateMemberBankAccount($account_id,$type,$account_number,$bank_code,$bank_type,$bank_username,$bank_card,$bank_name,$mobile,$validdate,$cvv2);
            if ($retval > 0) {
                $data['code'] = 0;
                $data['message'] = "修改成功";
                return json($data);
            } else {
                $data['code'] = '-1';
                $data['message'] = "修改失败";
                return json($data);
            }
        }
    }

    //删除提现账户
    public function del_account()
    {
        $member = new MemberService();
        $account_id = request()->post('account_id','');
        $retval = $member->delMemberBankAccount($account_id);
        if ($retval > 0) {
            $data['code'] = 0;
            $data['message'] = "删除成功";
            return json($data);
        } else {
            $data['code'] = '-1';
            $data['message'] = "删除失败";
            return json($data);
        }
    }

    //对象转数组
    function object2array(&$object)
    {
        $object = json_decode(json_encode($object), true);
        return $object;
    }


    //获取流水
    public function getAccountList($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $member_account = new VslMemberAccountRecordsViewModel();
        $list = $member_account->getViewList($page_index, $page_size, $condition, 'nmar.create_time desc');
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type_name'] = MemberAccount::getMemberAccountRecordsName($v['from_type']);
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
        }
        return $list;
    }


    public function getOrderCustom()
    {
        $member_server = new MemberService();
        $base_info['customform'] = $member_server->getOrderCustomForm();
        return json(['code' => 1, 'message' => '获取成功', 'data' => $base_info]);
    }

    public function getMemberBaseInfo()
    {
        $member_server = new MemberService();
        $member_info = $member_server->getUserInfoNew(['uid' => $this->uid], ['province', 'city', 'district','countryCode']);
        if (empty($member_info)) {
            return json(['code' => -1, 'message' => '信息为空']);
        }
        $base_info['avatar'] = getApiSrc($member_info['user_headimg']);
        $base_info['real_name'] = $member_info['real_name'];
        $base_info['sex'] = $member_info['sex'];
        $base_info['user_name'] = $member_info['user_name'];
        $base_info['user_tel'] = $member_info['user_tel'];
        $base_info['nick_name'] = $member_info['nick_name'];
        $base_info['birthday'] = $member_info['birthday'];
        $base_info['qq'] = $member_info['user_qq'];
        $base_info['province_id'] = $member_info['province_id'] ?: 0;
        $base_info['city_id'] = $member_info['city_id'] ?: 0;
        $base_info['district_id'] = $member_info['district_id'] ?: 0;
        $base_info['province_name'] = $member_info['province']['province_name'] ?: '';
        $base_info['city_name'] = $member_info['city']['city_name'] ?: '';
        $base_info['district_name'] = $member_info['district']['district_name'] ?: '';
        $base_info['area_code'] = $member_info['area_code'];
        $base_info['custom_person'] = json_decode(htmlspecialchars_decode($member_info['custom_person']));
        $base_info['custom_data'] = $member_server->getMemberCustomForm($this->website_id);
        $base_info['country_code'] = $member_info['country_code'];
        $base_info['country'] = $member_info['countryCode']['country'] ?: '';

        return json(['code' => 1, 'message' => '获取成功', 'data' => $base_info]);
    }

    public function saveMemberBaseInfo()
    {
        $data['real_name'] = request()->post('real_name');
        $data['sex'] = request()->post('sex');
        if (request()->post('user_name')) {
            $data['user_name'] = request()->post('user_name');
        }
        $data['nick_name'] = request()->post('nick_name');
        $data['birthday'] = request()->post('birthday');
        $data['user_qq'] = request()->post('qq');
        $data['province_id'] = request()->post('province_id');
        $data['city_id'] = request()->post('city_id');
        $data['district_id'] = request()->post('district_id');
        if (request()->post('area_code') != ''){
            $data['area_code'] = request()->post('area_code');
        }
        $data['custom_person'] = request()->post('post_data');
        $member_server = new MemberService();
        $condition['uid'] = $this->uid;
        $result = $member_server->updateUserNew($data, $condition);
        if ($result) {
            return json(['code' => 1, 'message' => '修改成功']);
        } else {
            return json(['code' => -1, 'message' => '修改失败']);
        }
    }

    public function updatePassword()
    {
        $password = request()->post('password');
        if (empty($password)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        if (empty(Session::get('sendMobile'))) {
            return json(['code' => 0, 'message' => '手机信息已失效，请重新获取验证码']);
        }
        $member_server = new MemberService();
        $condition['website_id'] = $this->website_id;
        $condition['user_tel'] = Session::get('sendMobile');
        $condition['is_member'] = 1;

        $data['user_password'] = md5($password);

        $result = $member_server->updateUserNew($data, $condition);
        if ($result) {
            Session::delete(['sendMobile']);
            return json(['code' => 1, 'message' => '密码修改成功']);
        } else {
            return json(['code' => -1, 'message' => '密码修改失败']);
        }
    }

    public function updatePaymentPassword()
    {
        $payment_password = request()->post('payment_password');
        if (empty($payment_password)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        if (empty(Session::get('sendMobile'))) {
            return json(['code' => 0, 'message' => '手机信息已失效，请重新获取验证码']);
        }
        $member_server = new MemberService();
        $condition['website_id'] = $this->website_id;
        $condition['user_tel'] = Session::get('sendMobile');
        $condition['is_member'] = 1;
        if($this->uid){
            $condition['uid'] = $this->uid;
        }
        $block = getAddons('blockchain',$this->website_id);
        if($block){
            $real_password = $this->getPassword();
            if($real_password) {
                $block = new Block();
                $block->updatePass($this->uid, $real_password, $payment_password);
            }
        }
        $data['payment_password'] = md5($payment_password);
        $data['plain_password'] = $payment_password;
        $result = $member_server->updateUserNew($data, $condition);
        if ($result) {
            Session::delete(['sendMobile']);
            return json(['code' => 1, 'message' => '支付密码修改成功']);
        } else {
            return json(['code' => -1, 'message' => '支付密码修改失败']);
        }
    }

    public function updateMobile()
    {
        //$mobile = Session::get('sendMobile');
        $mobile = request()->post('mobile');
        $country_code = request()->post('country_code');
        // 判断是哪种账号体系
        $website = new WebSiteModel();
        $account_type = $website->getInfo(['website_id' => $this->website_id], 'account_type')['account_type'];
        if($account_type == 3){
            $mall_port = Session::get('wap_port') ? : 0;
        }else{
            $mall_port = 0;
        }
        $verification_code = request()->post('verification_code');
        if (empty($mobile)) {
            return json(['code' => -1, 'message' => '手机号码为空']);
        }
        if ($mobile != Session::get('sendMobile')){
            return json(['code' => -1, 'message' => '手机号码和获取手机验证码的手机号不一致']);
        }
        if ($verification_code != Session::get('mobileVerificationCode')){
            return json(['code' => -1, 'message' => '手机验证码错误']);
        }
        $member_server = new MemberService();
        $condition0 = ['website_id' => $this->website_id, 'user_tel' => $mobile];
        if($mall_port){
            $condition0['mall_port'] = $mall_port;
        }
        $info = $member_server->getUserInfoNew($condition0);
        if ($info) {
            if ($info['uid'] == $this->uid) {
                return json(['code' => -1, 'message' => '新手机和旧手机一致']);
            } else {
                return json(['code' => -1, 'message' => '该手机已存在']);
            }
        }
        $condition['uid'] = $this->uid;
        $data['user_tel'] = $mobile;
        $data['country_code'] = $country_code?:86;
        $result = $member_server->updateUserNew($data, $condition);
        if ($result) {
            Session::delete(['sendMobile', 'mobileVerificationCode']);
            return json(['code' => 1, 'message' => '支付手机号码修改成功']);
        } else {
            return json(['code' => -1, 'message' => '支付手机号码修改失败']);
        }
    }

    public function updateEmail()
    {
        //$email = Session::get('sendEmail');
        $email = request()->post('email');
        $email_verification = request()->post('email_verification');
        if (empty($email)) {
            return json(['code' => -1, 'message' => '邮箱为空']);
        }
        if ($email != Session::get('sendEmail')){
            return json(['code' => -1, 'message' => '邮箱和获取邮箱验证码的邮箱不一致']);
        }
        if ($email_verification != Session::get('EmailVerificationCode')){
            return json(['code' => -1, 'message' => '邮箱验证错误']);
        }
        $member_server = new MemberService();
        $info = $member_server->getUserInfoNew(['website_id' => $this->website_id, 'user_email' => $email]);
        if ($info) {
            if ($info['uid'] == $this->uid) {
                return json(['code' => -1, 'message' => '新邮箱和旧邮箱一致']);
            } else {
                return json(['code' => -1, 'message' => '该邮箱已存在']);
            }
        }
        $condition['uid'] = $this->uid;
        $data['user_email'] = $email;
        $result = $member_server->updateUserNew($data, $condition);
        if ($result) {
            Session::delete(['sendEmail', 'EmailVerificationCode']);
            return json(['code' => 1, 'message' => '邮箱修改成功']);
        } else {
            return json(['code' => -1, 'message' => '邮箱修改失败']);
        }
    }

    /**
     * 保存收货地址
     */
    public function saveReceiverAddress()
    {
        $id = request()->post('id');
        $consigner = request()->post('consigner'); // 收件人
        $mobile = request()->post('mobile'); // 电话
        $province = request()->post('province'); // 省
        $city = request()->post('city'); // 市
        $district = request()->post('district'); // 区县
        $address = request()->post('address'); // 详细地址
        $zip_code = request()->post('zip_code'); // 邮编
        $is_default = request()->post('is_default'); // 设为默认
        if (empty($consigner) || empty($mobile) || empty($province) || empty($city) || empty($district) || empty($address) || $is_default == '') {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $member = new MemberService();
        $data = array(
            'consigner' => $consigner,
            'mobile' => $mobile,
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'address' => $address,
            'zip_code' => $zip_code,
            'is_default'=>$is_default
        );
        if (request()->post('area_code') != ''){
            $data['area_code'] = request()->post('area_code');
        }
        // 修改
        if ($id) {
            //$result = $member->updateMemberExpressAddress($id, $consigner, $mobile, '', $province, $city, $district, $address, $zip_code, '', $is_default);
            $result = $member->updateMemberExpressAddressNew($data, $id);
        } else {
            $data['uid'] = $this->uid;
            $result = $member->addMemberExpressAddressNew($data);
        }
        if ($result) {
            return json(['code' => 1, 'message' => '保存成功!', 'data' => ['id' => $result]]);

        } else {
            return json(['code' => -1, 'message' => '保存失败']);
        }
    }

    /**
     * 设为默认收货地址
     */
    public function setDefaultAddress()
    {
        $id = request()->post('id');
        if (empty($id)){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $member_service = new MemberService();
        $retval = $member_service->updateAddressDefault($id);
        if ($retval){
            return json(AjaxReturn(SUCCESS));
        } else {
            return json(AjaxReturn(UPDATA_FAIL));
        }
    }

    /**
     * 删除收货地址
     */
    public function deleteAddress()
    {
        $id = request()->post('id');
        if (empty($id)){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $member_service = new MemberService();
        $retval = $member_service->memberAddressDelete($id);
        return json(AjaxReturn($retval));
    }

    /**
     * 收货地址列表
     */
    public function receiverAddressList()
    {
        $member = new MemberService();
        $page_index = request()->post('page_index');
        $page_size = request()->post('page_size') ?: PAGESIZE;
        if (empty($page_index)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $list = $member->getMemberExpressAddressList($page_index, $page_size, '', '');
        $address_list = [];
        foreach ($list['data'] as $k => $v) {
            $address_list[$k]['id'] = $v['id'];
            $address_list[$k]['consigner'] = $v['consigner'];
            $address_list[$k]['mobile'] = $v['mobile'];
            $address_list[$k]['province'] = $v['province'];
            $address_list[$k]['city'] = $v['city'];
            $address_list[$k]['district'] = $v['district'];
            $address_list[$k]['address'] = $v['address'];
            $address_list[$k]['is_default'] = $v['is_default'];
            $address_list[$k]['area_code'] = $v['area_code'];
            $temp_area_info = explode('&nbsp;', $v['address_info']);
            $address_list[$k]['province_name'] = $temp_area_info[0] ?: '';
            $address_list[$k]['city_name'] = $temp_area_info[1] ?: '';
            $address_list[$k]['district_name'] = $temp_area_info[2] ?: '';
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => ['address_list' => $address_list]]);
    }

    /**
     * 获取收货地址详情
     */
    public function receiverAddressDetail()
    {
        $id = request()->post('id');
        if (empty($id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $member = new MemberService();
        $data = $member->getMemberExpressAddressDetail($id);
        if ($data) {
            $temp_area_info = explode('&nbsp;', $data['address_info']);
            unset($data['alias'], $data['website_id'], $data['address_info'], $data['uid']);
            $data['province_name'] = $temp_area_info[0] ?: '';
            $data['city_name'] = $temp_area_info[1] ?: '';
            $data['district_name'] = $temp_area_info[2] ?: '';
            return json(['code' => 1, 'message' => '获取成功', 'data' => $data]);
        } else {
            return json(['code' => -1, 'message' => '获取失败']);
        }
    }

    /**
     * 我的收藏-商品
     */
    public function myGoodsCollection()
    {
        $member = new MemberService();
        $page_index = request()->post('page_index');
        $page_size = request()->post('page_size') ?: PAGESIZE;
        if (empty($page_index)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $condition = array(
            'nmf.fav_type' => 'goods',
            'nmf.uid' => $this->uid
        );
        $goods_collection_list = $member->getMemberGoodsFavoritesList($page_index, $page_size, $condition, 'fav_time desc');
        $goods_list = [];
        foreach ($goods_collection_list['data'] as $k => $v) {
            if ($v['status'] == 0){
                continue;
            }
            $temp = [];
            $temp['goods_id'] = $v['fav_id'];
            $temp['seckill_id'] = $v['seckill_id'];
            $temp['goods_name'] = $v['goods_name'];
            $temp['price'] = $v['log_price'];
            $temp['status'] = $v['status'];
            $temp['pic_cover'] = getApiSrc($v['goods_image']);

            $goods_list[] = $temp;
//            $goods_list[$k]['goods_id'] = $v['fav_id'];
//            $goods_list[$k]['seckill_id'] = $v['seckill_id'];
//            $goods_list[$k]['goods_name'] = $v['goods_name'];
//            $goods_list[$k]['price'] = $v['log_price'];
//            $goods_list[$k]['status'] = $v['status'];
//            $goods_list[$k]['pic_cover'] = getApiSrc($v['goods_image']);
        }
        return json([
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'goods_list' => $goods_list,
                'page_count' => $goods_collection_list['page_count'],
                'total_count' => $goods_collection_list['total_count']
            ]
        ]);
    }

    /**
     * 关联账号列表
     */
    public function associationList()
    {
        $user_service = new User();
        $data = ['qq' => true, 'wechat' => true];
        $user_info = $user_service->getUserInfoNew(['website_id' => $this->website_id, 'uid' => $this->uid]);
        if (empty($user_info['qq_openid'])) {
            $data['qq'] = false;
        }
        if (empty($user_info['wx_openid']) && empty($user_info['wx_unionid'])) {
            $data['wechat'] = false;
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => $data]);
    }


    //全球分红---申请成为代理商，条件展示
    public function applyagent(){
        $uid = $this->uid;
        $member = new MemberService();
        $config_info = $member->getBonusConfig();
        $customform = $member->getCustomForm($this->website_id);
        unset($config_info['member_info']);
        if(empty($config_info['team_bonus_agreement'])){
            $config_info['team_bonus_agreement']['website_id'] = '';
            $config_info['team_bonus_agreement']['logo'] = '';
            $config_info['team_bonus_agreement']['content'] = '';
        }
        if(empty($config_info['area_bonus_agreement'])){
            $config_info['area_bonus_agreement']['website_id'] = '';
            $config_info['area_bonus_agreement']['logo'] = '';
            $config_info['area_bonus_agreement']['content'] = '';
        }
        if(empty($config_info['global_bonus_agreement'])){
            $config_info['global_bonus_agreement']['website_id'] = '';
            $config_info['global_bonus_agreement']['logo'] = '';
            $config_info['global_bonus_agreement']['content'] = '';
        }
        if($customform['teambonus']){
            $config_info['team_bonus_agreement']['customform'] = $customform['teambonus'];
        }else{
            $config_info['team_bonus_agreement']['customform'] = (object)[];
        }
        if($customform['areabonus']){
            $config_info['area_bonus_agreement']['customform'] = $customform['areabonus'];
        }else{
            $config_info['area_bonus_agreement']['customform'] = (object)[];
        }
        if($customform['globalbonus']){
            $config_info['global_bonus_agreement']['customform'] = $customform['globalbonus'];
        }else{
            $config_info['global_bonus_agreement']['customform']= (object)[];
        }

        $user = new UserModel();
        $user_info = $user->getInfo(['uid'=>$uid],'real_name,user_tel');
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid'=>$uid],'is_team_agent,is_area_agent,is_global_agent');
        if(empty($member_info['is_team_agent'])){
            $config_info['is_team_agent'] = 0;
        }else{
            $config_info['is_team_agent'] = $member_info['is_team_agent'];
        }
        if(empty($member_info['is_area_agent'])){
            $config_info['is_area_agent'] = 0;
        }else{
            $config_info['is_area_agent'] = $member_info['is_area_agent'];
        }
        if(empty($member_info['is_global_agent'])){
            $config_info['is_global_agent'] = 0;
        }else{
            $config_info['is_global_agent'] = $member_info['is_global_agent'];
        }
        $config_info['user_tel'] = $user_info['user_tel'];
        $config_info['real_name'] = $user_info['real_name'];
        $data['data'] = $config_info;
        $data['code'] = 0;
        return json($data);
    }

    /*
     * 分红中心
     */
    public function bonusIndex(){
        $member = new MemberService();
        $config_info = $member->getBonusConfig();
        $data['data']['global_is_start'] = $config_info['global_bonus']['is_use'];
        $data['data']['area_is_start'] = $config_info['area_bonus']['is_use'];
        $data['data']['team_is_start'] = $config_info['team_bonus']['is_use'];
        if($data['data']['team_is_start']!=1 && $data['data']['area_is_start']!=1 && $data['data']['global_is_start']!=1){
            $data['code'] = -1;
            $data['data'] = '';
            $data['message'] = '至少需要开启一个分红应用';
            return json($data);
        }
        //已发放分红
        $data['data']['grant_bonus'] = $config_info['member_info']['grant_bonus']?$config_info['member_info']['grant_bonus']:'0.00';
        //代发放分红
        $data['data']['ungrant_bonus'] = $config_info['member_info']['ungrant_bonus']?$config_info['member_info']['ungrant_bonus']:'0.00';
        //冻结分红
        $data['data']['freezing_bonus'] = $config_info['member_info']['freezing_bonus']?$config_info['member_info']['freezing_bonus']:'0.00';


        $data['data']['global_level_name'] = $config_info['member_info']['global_level_name'];

        $data['data']['area_level_name'] = $config_info['member_info']['area_level_name'];
        if($config_info['member_info']['is_team_agent']){
            $data['data']['is_team_agent'] = $config_info['member_info']['is_team_agent'];
            if($config_info['member_info']['is_team_agent']==2 && $config_info['member_info']['complete_datum_team']==0 && $config_info['team_bonus']['teamagent_data']==1){
                $data['data']['complete_datum_team'] = 1;
            }else if($config_info['member_info']['is_team_agent']==3 && $config_info['member_info']['complete_datum_team']==0){
                $data['data']['complete_datum_team'] = 1;
            }else{
                $data['data']['complete_datum_team'] = 0;
            }
        }else{
            $data['data']['is_team_agent'] = 0;
            $data['data']['complete_datum_team'] = 0;
        }
        if($config_info['member_info']['is_area_agent']){
            $data['data']['is_area_agent'] = $config_info['member_info']['is_area_agent'];
        }else{
            $data['data']['is_area_agent'] = 0;
        }
        if($config_info['member_info']['is_global_agent']){
            $data['data']['is_global_agent'] = $config_info['member_info']['is_global_agent'];
            if($config_info['member_info']['is_global_agent']==2 && $config_info['member_info']['complete_datum_global']==0 && $config_info['global_bonus']['globalagent_data']==1){
                $data['data']['complete_datum_global'] = 1;
            }else if($config_info['member_info']['is_global_agent']==3 && $config_info['member_info']['complete_datum_global']==0 ){
                $data['data']['complete_datum_global'] = 1;
            }else{
                $data['data']['complete_datum_global'] = 0;
            }
        }else{
            $data['data']['is_global_agent'] = 0;
            $data['data']['complete_datum_global'] = 0;
        }
        $data['data']['team_level_name'] = $config_info['member_info']['team_level_name'];
        $data['data']['uid'] = $config_info['member_info']['uid'];
        $user = new UserModel();
        $userinfo = $user->getInfo(['uid'=>$data['data']['uid']],'user_headimg,user_name,nick_name');
        if(empty($userinfo['user_name'])){
            $data['data']['member_name'] = $userinfo['nick_name'];
        }else{
            $data['data']['member_name'] = $userinfo['user_name'];
        }
        $data['data']['user_headimg'] = getApiSrc($userinfo['user_headimg']);
        $data['code'] = 0;

        return json($data);


    }


    /*
    * 我的分红
    */
    public function myBonus(){
        $member = new MemberService();
        $config_info = $member->getBonusConfig();

        if(empty($config_info)){
            $data['code'] = '-1';
            $data['message'] = '服务器繁忙';
            return json($data);
        }
        //提取所需数据

        //已发放分红
        $data['data']['grant_bonus'] = $config_info['member_info']['grant_bonus']?$config_info['member_info']['grant_bonus']:'0.00';
        //代发放分红
        $data['data']['ungrant_bonus'] = $config_info['member_info']['ungrant_bonus']?$config_info['member_info']['ungrant_bonus']:'0.00';
        //冻结分红
        $data['data']['freezing_bonus'] = $config_info['member_info']['freezing_bonus']?$config_info['member_info']['freezing_bonus']:'0.00';

        //全球分红数据
        $data['data']['global']['grant_bonus'] = $config_info['global_account_bonus']['grant_bonus']?$config_info['global_account_bonus']['grant_bonus']:0;
        $data['data']['global']['ungrant_bonus'] = $config_info['global_account_bonus']['ungrant_bonus']?$config_info['global_account_bonus']['ungrant_bonus']:0;
        $data['data']['global']['freezing_bonus'] = $config_info['global_account_bonus']['freezing_bonus']?$config_info['global_account_bonus']['freezing_bonus']:0;

        //区域分红
        $data['data']['area']['grant_bonus'] = $config_info['area_account_bonus']['grant_bonus']?$config_info['area_account_bonus']['grant_bonus']:0;
        $data['data']['area']['ungrant_bonus'] = $config_info['area_account_bonus']['ungrant_bonus']?$config_info['area_account_bonus']['ungrant_bonus']:0;
        $data['data']['area']['freezing_bonus'] = $config_info['area_account_bonus']['freezing_bonus']?$config_info['area_account_bonus']['freezing_bonus']:0;

        //区域分红
        $data['data']['team']['grant_bonus'] = $config_info['team_account_bonus']['grant_bonus']?$config_info['team_account_bonus']['grant_bonus']:0;
        $data['data']['team']['ungrant_bonus'] = $config_info['team_account_bonus']['ungrant_bonus']?$config_info['team_account_bonus']['ungrant_bonus']:0;
        $data['data']['team']['freezing_bonus'] = $config_info['team_account_bonus']['freezing_bonus']?$config_info['team_account_bonus']['freezing_bonus']:0;
        $data['code'] = 0;
        return json($data);
    }


    /*
     * 分红详情
     * */
    public function bonus_detail(){

        $member = new MemberService();
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $condition = array();
        $list = $member->getBonusGrantList($page_index, $page_size,$condition,'');
        if(!empty($list)){
            $list['page_index'] = $page_index;
            $list['page_size'] = $page_size;
            $data['code'] = 0;
            $data['data'] = $list;
            return json($data);
        }else{
            $list['code'] = '-1';
            $list['message'] = "暂无数据";
            return json($list);
        }
    }

    /*
     * 分红订单
     * */
    public function bonus_order(){

        $member = new MemberService();
        $page_index = request()->post('page', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $status = request()->post('status', '');
        $condition['buyer_id'] =$this->uid;
        if($status){
            $condition['order_status'] =$status;
        }
        $condition['website_id'] = $this->website_id;
        $list = $member->getBonusOrderList($page_index, $page_size, $condition,  'create_time desc');
        if(empty($list['data'])){
            $data['code'] = '0';
            $data['data']['page_index'] = $page_index;
            $data['data']['page_size'] = $page_size;
            $data['data']['page_count'] = 0;
            $data['data'] = array();
            $data['message'] = '暂无数据';
        }else{
            $data['code'] = '0';
            $data['data']['page_index'] = $page_index;
            $data['data']['page_size'] = $page_size;
            $data['data'] = $list;
        }
        return json($data);

    }

    //分红设置
    public function bonusSet(){
        $config = new Config();
        $list = [];
        $bonus = $config->getBonusSite($this->website_id);
        if($bonus){
            $list['common'] = $bonus;
            if(!isset($bonus['bonus_name'])){
                $list['common'] = array(
                    'bonus_name' => '分红中心',
                    'bonus' => '分红',
                    'withdrawals_bonus' => '已发放分红',
                    'withdrawal_bonus' => '待发放分红',
                    'frozen_bonus' => '冻结分红',
                    'bonus_details' => '分红明细',
                    'bonus_money' => '分红金额',
                    'bonus_order' => '分红订单'
                );
            }
        }else{
            $list['common'] = array(
                'bonus_name' => '分红中心',
                'bonus' => '分红',
                'withdrawals_bonus' => '已发放分红',
                'withdrawal_bonus' => '待发放分红',
                'frozen_bonus' => '冻结分红',
                'bonus_details' => '分红明细',
                'bonus_money' => '分红金额',
                'bonus_order' => '分红订单'
            );
        }
        $global =$config->getConfig(0,'GLOBALAGREEMENT',$this->website_id);
        if($global){
            $list['global'] = json_decode($global['value'],true);
            if($list['global'] && !isset($list['global']['withdrawals_global_bonus'])){
                $list['global'] = array(
                    'withdrawals_global_bonus' => '已发放分红',
                    'withdrawal_global_bonus' => '待发放分红',
                    'frozen_global_bonus' => '冻结分红',
                    'apply_global' => '申请全球股东',
                    'global_agreement' => '全球股东'
                );
            }
        }else{
            $list['global'] = array(
                'withdrawals_global_bonus' => '已发放分红',
                'withdrawal_global_bonus' => '待发放分红',
                'frozen_global_bonus' => '冻结分红',
                'apply_global' => '申请全球股东',
                'global_agreement' => '全球股东'
            );
        }
        $area =$config->getConfig(0,'AREAAGREEMENT',$this->website_id);
        if($area){
            $list['area'] = json_decode($area['value'],true);
            if($list['area'] && !isset($list['area']['withdrawals_area_bonus'])){
                $list['area'] = array(
                    'withdrawals_area_bonus' => '已发放分红',
                    'withdrawal_area_bonus' => '待发放分红',
                    'frozen_area_bonus' => '冻结分红',
                    'apply_area' => '申请区域代理',
                    'area_agreement' => '区域代理'
                );
            }
        }else{
            $list['area'] = array(
                'withdrawals_area_bonus' => '已发放分红',
                'withdrawal_area_bonus' => '待发放分红',
                'frozen_area_bonus' => '冻结分红',
                'apply_area' => '申请区域代理',
                'area_agreement' => '区域代理'
            );
        }
        $team =$config->getConfig(0,'TEAMAGREEMENT',$this->website_id);
        if($team){
            $list['team'] = json_decode($team['value'],true);
            if($list['team'] && !isset($list['team']['withdrawals_team_bonus'])){
                $list['team'] = array(
                    'withdrawals_team_bonus' => '已发放分红',
                    'withdrawal_team_bonus' => '待发放分红',
                    'frozen_team_bonus' => '冻结分红',
                    'apply_team' => '申请团队队长',
                    'team_agreement' => '团队队长'
                );
            }
        }else{
            $list['team'] = array(
                'withdrawals_team_bonus' => '已发放分红',
                'withdrawal_team_bonus' => '待发放分红',
                'frozen_team_bonus' => '冻结分红',
                'apply_team' => '申请团队队长',
                'team_agreement' => '团队队长'
            );
        }
        if(empty($list)){
            $list = (object)[];
        }else{
            $list = (object)$list;
        }
        $data['code'] = '0';
        $data['data'] = $list;
        return json($data);
    }
    //领券中心
    public function coupon_center(){

        $coup = new CouponServer();
    }
    //查看是否有邀请码
    public function checkReferee(){
        //查询是否有分销应用
        $extend_code = request()->post('extend_code', '');//邀请码
        $poster_id = request()->post('poster_id', 0) ? request()->post('poster_id', 0) : request()->post('general_poster_id', 0);//海报id

        $poster_type = request()->post('poster_type', 0);//海报类型
        $uid = $this->uid;
        $member = new MemberService();
        $distribution = new Distributor();
        $member_info = $member->getDistributionInfo();//查询是否是分销商
        $list = $distribution->getDistributionSite($this->website_id);
        $member_referee = $member_info['referee_id'];
        $referee_id = $member->getUidInfo($extend_code, $this->website_id);
        if($poster_id && $poster_type){
            //增加扫描纪录
            $member->addScanRecords($uid, $referee_id, $poster_id, $poster_type);
        }
        $distributionStatus = getAddons('distribution', $this->website_id);
        //推荐人不能是自己
        
        if ($distributionStatus == 1 && $member_referee === null && $referee_id != $uid && $referee_id && $uid && ($member_info['isdistributor'] != 2 ||  ($list['distributor_condition']==3 && empty($member_info['referee_id'])) )) {
            $res = $member->updateMemberInfo($referee_id);
            if($res==1){
                return json(['code' => 1, 'message' => '操作成功','data'=>1]);
            }elseif($res==2){
                return json(['code' => 0, 'message' => '不符合推荐条件']);
            }elseif($res==3){
                return json(['code' => 0, 'message' => '不符合推荐条件']);
            }else{
                return json(['code' => 2, 'message' => '操作失败']);
            }
        }else{
            return json(['code' => 0, 'message' => '不符合推荐条件']);
        }
    }
    //获取邀请码
    public function qrcode(){
        $member = new MemberService();
        $result = $member->getMemberInfo();
        $extend_code = '';
        if ($result) {
            $extend_code = $result['extend_code'];
        }
        if($extend_code){
            $data['code'] = 0;
            $data['message'] = "获取成功";
            $data['data']['extend_code'] = $extend_code;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * 我的奖品
     */
    public function myPrize()
    {
        $page_index = input('page_index',1);
        $page_size = input('page_size',PAGESIZE);
        $state = input('state',0);
        $condition['uid'] = $this->uid;
        $condition['website_id'] = $this->website_id;
        $condition['state'] = $state;
        $member = new MemberService();
        $list = $member->getMemberPrize($page_index, $page_size, $condition, 'prize_time desc');
        if($list){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $list;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * 我的奖品详情
     */
    public function prizeDetail()
    {
        $member_prize_id = (int)input('member_prize_id');
        $member = new MemberService();
        $info = $member->prizeDetail($member_prize_id);
        if($info){
            if($info['type']==5 || $info['type']==6){
                if($info['type']==5){
                    $goods = new VslGoodsModel();
                    $goods_info = $goods->getInfo(['goods_id' => $info['type_id']],'goods_type,store_list,website_id,shop_id');
                    if($goods_info){
                        $info['goods_type'] = $goods_info['goods_type'];
                        if($info['goods_type']==0 && getAddons('store', $goods_info['website_id'], $goods_info['shop_id'])){
                            $store = new Store();
                            $store_list = $goods_info['store_list'];
                            $info['store_list'] = [];
                            if(!empty($store_list)){
                                $store_id = explode(',',$store_list); //适用的门店ID
                                $condition = [];
                                $condition['website_id'] = $goods_info['website_id'];
                                $condition['store_id'] = ['IN',$store_id];
                                $lng = input('lng',0);
                                $lat = input('lat',0);
                                $place = ['lng' => $lng,'lat' => $lat];
                                $store_list = $store->storeListForFront(1, 20, $condition,$place);
                                if(!empty($store_list)){
                                    $info['store_list'] = $store_list['store_list'];
                                }
                            }
                        }
                    }
                }
                // 收获地址
                $address_id = request()->post('address_id', 0);
                $address_condition = [];
                if (empty($address_id)) {
                    $address_condition['uid'] = $this->uid;
                    $address_condition['is_default'] = 1;
                } else {
                    $address_condition['id'] = $address_id;
                }
                $member_service = new MemberService();
                $address = $member_service->getMemberExpressAddress($address_condition, ['area_province', 'area_city', 'area_district']);
                if (!empty($address)){
                    $info['address']['address_id'] = $address['id'];
                    $info['address']['consigner'] = $address['consigner'];
                    $info['address']['mobile'] = $address['mobile'];
                    $info['address']['province_name'] = $address['area_province']['province_name'];
                    $info['address']['city_name'] = $address['area_city']['city_name'];
                    $info['address']['district_name'] = $address['area_district']['district_name'];
                    $info['address']['address_detail'] = $address['address'];
                    $info['address']['zip_code'] = $address['zip_code'];
                    $info['address']['alias'] = $address['alias'];
                } else {
                    $info['address'] = (object)[];
                }
            }
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
     * 领奖品
     */
    public function acceptPrize()
    {
        $member_prize_id = (int)input('member_prize_id');
        $member = new MemberService();
        $result = $member->acceptPrize($member_prize_id);
        return json($result);
    }
    
    /**
     * 消费卡
     */
    public function consumerCard()
    {
        $page_index = input('page_index',1);
        $page_size = input('page_size',PAGESIZE);
        $state = input('state',0);
        $condition['uid'] = $this->uid;
        $condition['website_id'] = $this->website_id;
        $condition['state'] = $state;
        $member_card = new MemberCard();
        $list = $member_card->getMemberCardList($page_index, $page_size, $condition, 'create_time desc');
        if($list){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $list;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * 消费卡详情
     */
    public function consumerCardDetail()
    {
        $card_id = (int)input('card_id');
        $wx_card_id = input('wx_card_id');
        $member_card = new MemberCard();
        $info = $member_card->getCardDetail($card_id,'',$wx_card_id);
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
     * 消费卡核销记录
     */
    public function consumerCardRecord()
    {
        $card_id = (int)input('card_id');
        $page_index = input('page_index',1);
        $page_size = input('page_size',PAGESIZE);
        $member_card = new MemberCard();
        $list = $member_card->getCardRecordList($page_index, $page_size,$card_id);
        if($list){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $list;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * 消费卡微信卡券领取
     */
    public function getwxCard()
    {
        $weixin_card = new WeixinCard();
        $member_card = new MemberCard();
        $cards_id = input('cards_id');
        $card_list = $member_card->getCardsList($cards_id);
        $info = $weixin_card->addCard($card_list);
        if($info){
            $data['data'] = $info;
            $data['code'] = 1;
            $data['message'] = "获取成功";
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * 消费卡微信卡券领取成功
     */
    public function getwxCardUse()
    {
        $cards_id = input('cards_id');
        $member_card = new MemberCard();
        $result = $member_card->getwxCardUse($cards_id);
        return json(AjaxReturn($result));
    }
    
    /**
     * 会员等级
     */
    public function memberLevel()
    {
        $member = new MemberService();
        $member_info = $member->getMemberDetail();

        if (empty($member_info)) {
            $data['code'] = -1000;
            $data['message'] = '登录信息已过期，请重新登录!';
            return json($data);
        }

        // 头像
        if (!empty($member_info['user_info']['user_headimg'])) {
            $member_img = getApiSrc($member_info['user_info']['user_headimg']);
        } else {
            $member_img = '';
        }

        $memberLevelModel = new VslMemberLevelModel();
        //查找当前会员对应的折扣
        $member_discount = $memberLevelModel->Query(['level_id' => $member_info['member_level']], 'goods_discount')[0];
        //会员等级列表
        $member_level_list = $memberLevelModel->getQuery(['website_id' => $this->website_id], 'level_id,level_name,growth_num,goods_discount', '');
        //当前会员属于哪个等级就在哪个等级的数组里面加个标识
        foreach ($member_level_list as $k => $v) {
            if ($v['level_id'] == $member_info['member_level']) {
                $v['is_current_member_level'] = 1;
            }
        }

        //有没有开启倍数递增
        $websiteModel = new WebSiteModel();
        $recharge_multiple = $websiteModel->Query(['website_id' => $this->website_id], 'recharge_multiple')[0];
        $order_multiple = $websiteModel->Query(['website_id' => $this->website_id], 'order_multiple')[0];

        //充值成功加成长值
        $recharge_money = $websiteModel->Query(['website_id' => $this->website_id], 'recharge_money')[0];
        $recharge_num = $websiteModel->Query(['website_id' => $this->website_id], 'recharge_num')[0];

        //单次消费满加成长值
        $order_money = $websiteModel->Query(['website_id' => $this->website_id], 'order_money')[0];
        $pay_num = $websiteModel->Query(['website_id' => $this->website_id], 'pay_num')[0];

        //订单完成加成长值
        $complete_num = $websiteModel->Query(['website_id' => $this->website_id], 'complete_num')[0];

        $member_info = [
            'uid' => $member_info['uid'],
            'user_name' => $member_info['user_name'],
            'username' => $member_info['user_info']['username'],
            'member_img' => $member_img,
            'growth_num' => $member_info['growth_num'],
            'member_discount' => $member_discount,
            'recharge_multiple' => $recharge_multiple,
            'order_multiple' => $order_multiple,
            'recharge_money' => $recharge_money,
            'recharge_num' => $recharge_num,
            'order_money' => $order_money,
            'pay_num' => $pay_num,
            'complete_num' => $complete_num,
            'member_level_list' => $member_level_list
        ];

        return [
            'code' => 1,
            'message' => '获取成功',
            'data' => $member_info
        ];
    }
    /**
     * 获取手续费
     * money金额 
     * ETH 转账  兑换需要传gas
     * types 1余额提现、2余额转账、3余额兑换积分、4ETH转账、5ETH兑换、6EOS转账、7EOS兑换、8余额支付、9ETH支付、10EOS支付、11ETH积分兑换 ,12积分兑换余额
     * 1  ==> 余额支付
        2  ==> 余额提现
        3  ==> 余额转账
        4  ==> 余额兑换积分
        5  ==> 积分兑换余额
        6  ==> 佣金提现
        7  ==> 收益提现
        8  ==> ETH支付
        9  ==> ETH转账
        10 ==> ETH兑换积分
        11 ==> 积分兑换ETH
        12 ==> EOS支付
        13 ==> EOS转账
        14 ==> EOS兑换积分
        15 ==> 积分兑换EOS
     */
    public function chargeService(){
        $money = input('money','');
        $types = input('types','');
        $address = input('address','');
        $gas = $money;
        
        if(empty($money) || empty($types)){
            $data['code'] = '-1';
            $data['message'] = "金额或者类型不能为空";
            return json($data);
        }
        switch ($types) {
            case 1:
                //余额支付
                $charge = 0;
            break;
            case 2:
                //余额提现
                // 得到本店的提现设置 注意缓存 
                $config = new Config();
                $withdraw_info = $config->getBalanceWithdrawConfig($this->instance_id);
                $charge = 0;
                //提现手续费
                if($withdraw_info['value']['member_withdraw_poundage']) {
                    $charge = abs($money) * $withdraw_info['value']['member_withdraw_poundage']/100;//手续费
                    if($withdraw_info['value']['withdrawals_end'] && $withdraw_info['value']['withdrawals_begin']){
                        if (abs($money) <= $withdraw_info['value']['withdrawals_end'] && abs($money) >=  $withdraw_info['value']['withdrawals_begin']) {
                            $charge = 0;//免手续费区间
                        }
                    }
                }
            break;
            case 3:
                //余额转账
                $config = new Config();
                $is_transfer_charge = $config->getConfig($this->instance_id, 'IS_TRANSFER_CHARGE', $this->website_id);
                $is_transfer_charge = empty($is_transfer_charge['value']) ? 0 : $is_transfer_charge['value'];
                if($is_transfer_charge != 1){
                    $charge = 0;//没有开启手续费
                }else{
                    $charge_type = $config->getConfig($this->instance_id, 'CHARGE_TYPE', $this->website_id);
                    if($charge_type['value'] == 1){
                        //比例费率
                        $charge_pares = $config->getConfig($this->instance_id, 'CHARGE_PARES', $this->website_id);
                        $charge_pares_min = $config->getConfig($this->instance_id, 'CHARGE_PARES_MIN', $this->website_id);
                        $charge = abs($money) * $charge_pares['value'] / 100;
                        if($charge < $charge_pares_min['value']){
                            $charge = $charge_pares_min['value'];
                        }
                    }else{
                        //固定费率
                        $charge_pares2 = $config->getConfig($this->instance_id, 'CHARGE_PARES2', $this->website_id);
                        $charge = $charge_pares2['value'];
                    }
                }
            break;
            case 4:
                //余额兑换积分
                $config = new Config();
                $is_point_transfer_charge = $config->getConfig($this->instance_id, 'IS_POINT_TRANSFER_CHARGE', $this->website_id);
                $is_point_transfer_charge = empty($is_point_transfer_charge['value']) ? 0 : $is_point_transfer_charge['value'];
                if($is_point_transfer_charge != 1){
                    $charge = 0;//没有开启手续费
                }else{
                    $point_charge_type = $config->getConfig($this->instance_id, 'POINT_CHARGE_TYPE', $this->website_id);
                    if($point_charge_type['value'] == 1){
                        //比例费率
                        $point_charge_pares = $config->getConfig($this->instance_id, 'POINT_CHARGE_PARES', $this->website_id);
                        $point_charge_pares_min = $config->getConfig($this->instance_id, 'POINT_CHARGE_PARES_MIN', $this->website_id);
                        $charge = abs($money) * $point_charge_pares['value'] / 100;
                        if($charge < $point_charge_pares_min['value']){
                            $charge = $point_charge_pares_min['value'];
                        }
                    }else{
                        //固定费率
                        $point_charge_pares2 = $config->getConfig($this->instance_id, 'POINT_CHARGE_PARES2', $this->website_id);
                        $charge = $point_charge_pares2['value'];
                    }
                }
                
            break;
            case 5:
                //积分兑换余额
                $config = new Config();
                $is_point_transfer_charge = $config->getConfig($this->instance_id, 'IS_POINT_TRANSFER_CHARGE', $this->website_id);
                $is_point_transfer_charge = empty($is_point_transfer_charge['value']) ? 0 : $is_point_transfer_charge['value'];
                if($is_point_transfer_charge != 1){
                    $charge = 0;//没有开启手续费
                }else{
                    //获取积分汇率
                    $convert_rate = $config->getConfig($this->instance_id, 'POINT_DEDUCTION_NUM', $this->website_id);
                    $convert_rate_charge = $convert_rate['value'] ? $convert_rate['value'] : 1;
                    $money = substr(sprintf("%.2f", $money/$convert_rate_charge),0,-1);
                   
                    $point_charge_type = $config->getConfig($this->instance_id, 'POINT_CHARGE_TYPE', $this->website_id);
                    if($point_charge_type['value'] == 1){
                        //比例费率
                        $point_charge_pares = $config->getConfig($this->instance_id, 'POINT_CHARGE_PARES', $this->website_id);
                        $point_charge_pares_min = $config->getConfig($this->instance_id, 'POINT_CHARGE_PARES_MIN', $this->website_id);
                        $charge = abs($money) * $point_charge_pares['value'] / 100;
                        if($charge < $point_charge_pares_min['value']){
                            $charge = $point_charge_pares_min['value'];
                        }
                    }else{
                        //固定费率
                        $point_charge_pares2 = $config->getConfig($this->instance_id, 'POINT_CHARGE_PARES2', $this->website_id);
                        $charge = $point_charge_pares2['value'];
                    }
                }
            break;
            case 6:
                //佣金提现 扣除个人所得税 == 费率
                $config = new Config();
                $commission_withdraw_set = json_decode($config->getConfig(0,'SETTLEMENT',$this->website_id)['value'],true);
                //佣金个人所得税
                $charge = 0;
                if($commission_withdraw_set['poundage']) {
                    $charge = twoDecimal(abs($money) * $commission_withdraw_set['poundage']/100);//佣金个人所得税
                    if($commission_withdraw_set['withdrawals_end'] && $commission_withdraw_set['withdrawals_begin']){
                        if (abs($money) <= $commission_withdraw_set['withdrawals_end'] && abs($money) >= $commission_withdraw_set['withdrawals_begin']) {
                            $charge = 0;//免打税区间
                        }
                    }
                }
            break;
            case 7:
                //收益提现 扣除个人所得税 == 费率
                $config = new Config();
                $Profit_withdraw_set = json_decode($config->getConfig(0,'SETMICROSHOPTLEMENT',$this->website_id)['value'],true);
                //收益个人所得税
                $charge = 0;
                if($Profit_withdraw_set['poundage']) {
                    $charge = twoDecimal($money * $Profit_withdraw_set['poundage']/100);//个人所得税
                    if($Profit_withdraw_set['withdrawals_end'] && $Profit_withdraw_set['withdrawals_begin']){
                        if (abs($money) <= $Profit_withdraw_set['withdrawals_end'] && abs($money) >= $Profit_withdraw_set['withdrawals_begin']) {
                            $charge = 0;//免打税区间
                        }
                    }
                }
            break;
            case 8:
                //ETH支付
                $pay_money = $money;
                $blocks = new Block();
                $market_eth = $blocks->ethRmb();
                $base = $blocks->getBlockChainSite($this->website_id);
                $charge = substr(sprintf("%.7f", $pay_money*$base['service_charge']/$market_eth/100),0,-1);
                
            break;
            case 9:
                //ETH转账
                $blocks = new Block();
                $result = $blocks->ethGasCharge(1,3,$this->uid,$address); 
                
                if($result['code']==1){
                    $charge = $blocks->decimalNotation($result['data']['gasFee']);
                }else{
                    $data['code'] = '-1';
                    $data['message'] = $result['msg'];
                    return json($data);
                }
            break;
            case 10:
                //5ETH兑换
                $blocks = new Block();
                
                $result = $blocks->ethGasCharge(1,2,$this->uid);
               
                if($result['code']==1){
                    $charge = $blocks->decimalNotation($result['data']['gasFee']);
                    
                }else{
                    $data['code'] = '-1';
                    $data['message'] = $result['msg'];
                    return json($data);
                }
            break;
            case 11:
                //积分兑换成eth 
                $blocks = new Block();
                $result = $blocks->ethGasCharge($gas,1,$this->uid);
                if($result['code']==1){
                    $charge = $blocks->decimalNotation($result['data']['gasFee']);
                }else{
                    $data['code'] = '-1';
                    $data['message'] = $result['msg'];
                    return json($data);
                }
            break;
            case 12:
                //EOS支付
                $pay_money = $money;
                $blocks = new Block();
                $market_eth = $blocks->eosRmb();
                $base = $blocks->getBlockChainSite($this->website_id);
                $charge = substr(sprintf("%.7f", $pay_money*$base['service_charge']/$market_eth/100),0,-1);
                $url1 = Configs::get('blockchain.cpu_url')."?rpcType=".$this->rpcType."&isPrefix=false";
                $result1 = GetCurl($url1);
                if($result1['code']==200){
                    $cpuPrices =  $result1['data']['cpuPrice'];
                    $cpucharge = $charge * $cpuPrices;
                }else{
                    $cpucharge = 0;
                }
                $url2 = Configs::get('blockchain.net_url')."?rpcType=".$this->rpcType."&isPrefix=false";
                $result2 = GetCurl($url2);
                if($result2['code']==200){
                    $netPrice =  $result2['data']['netPrice'];
                    $netcharge = $charge * $netPrice;
                    
                }else{
                    $netcharge = 0;
                }
            break;
            case 13:
                //EOS转账
                $url1 = Configs::get('blockchain.cpu_url')."?rpcType=".$this->rpcType."&isPrefix=false";
                $result1 = GetCurl($url1);
                if($result1['code']==200){
                    $cpuPrices =  $result1['data']['cpuPrice'];
                    $cpucharge = $money * $cpuPrices;
                }else{
                    $cpucharge = 0;
                }
                $url2 = Configs::get('blockchain.net_url')."?rpcType=".$this->rpcType."&isPrefix=false";
                $result2 = GetCurl($url2);
                if($result2['code']==200){
                    $netPrice =  $result2['data']['netPrice'];
                    $netcharge = $money * $netPrice;
                    
                }else{
                    $netcharge = 0;
                }
            break;
            case 14:
                //14EOS积分兑换
                $site = new Block();
                $list = $site->pointExMoney($money,2,1);
                $money = $list['number'];
                $url1 = Configs::get('blockchain.cpu_url')."?rpcType=".$this->rpcType."&isPrefix=false"; 
                $result1 = GetCurl($url1);
                if($result1['code']==200){
                    $cpuPrices =  $result1['data']['cpuPrice'];
                    $cpucharge = $money * $cpuPrices;
                }else{
                    $cpucharge = 0;
                }
                $url2 = Configs::get('blockchain.net_url')."?rpcType=".$this->rpcType."&isPrefix=false";
                $result2 = GetCurl($url2);
                if($result2['code']==200){
                    $netPrice =  $result2['data']['netPrice'];
                    $netcharge = $money * $netPrice;
                }else{
                    $netcharge = 0;
                }
            break;
            case 15:
                //15EOS兑换积分
                $url1 = Configs::get('blockchain.cpu_url')."?rpcType=".$this->rpcType."&isPrefix=false"; 
                $result1 = GetCurl($url1);
                if($result1['code']==200){
                    $cpuPrices =  $result1['data']['cpuPrice'];
                    $cpucharge = $money * $cpuPrices;
                }else{
                    $cpucharge = 0;
                }
                $url2 = Configs::get('blockchain.net_url')."?rpcType=".$this->rpcType."&isPrefix=false";
                $result2 = GetCurl($url2);
                if($result2['code']==200){
                    $netPrice =  $result2['data']['netPrice'];
                    $netcharge = $money * $netPrice;
                }else{
                    $netcharge = 0;
                }
            break;
        }
       
        if($types == 14 || $types == 15 || $types == 13){
            $rdata = array(
                'cpucharge' => $cpucharge,
                'netcharge' => $netcharge,
                'charge' => 0
            );
        }else if($types == 12){
            $rdata = array(
                'cpucharge' => $blocks->decimalNotation($cpucharge),
                'netcharge' => $blocks->decimalNotation($netcharge),
                'charge' => $charge
            );
        }else{
            $rdata = array(
                'charge' => $charge
            );
        }
        $data['code'] = 1;
        $data['message'] = "获取成功";
        $data['data'] = $rdata;
        return json($data);
    }
    /**
     * 余额转账
     * user_id受让人id
     * point转账余额
     * uid本人id
     */
    public function transBalance(){
        $user_id = request()->post('user_id', '');
        $mobile = request()->post('mobile', '');
        $money = request()->post('money', '');
        $remark = request()->post('remark', '');
        
        $uid = $this->uid;
        if(empty($user_id) && empty($mobile)){
            $data['code'] = '-1';
            $data['message'] = "受让人ID或手机号不能为空";
            return json($data);
        }
       
        if(empty($money)){
            $data['code'] = '-1';
            $data['message'] = "转账余额不能为空";
            return json($data);
        }
        //查询会员是否存在
        if($user_id){
            $user_service = new User();
            $user_info = $user_service->getUserInfoByUid($user_id);
            if(empty($user_info)){
                $data['code'] = '-1';
                $data['message'] = "受让人不存在,请确认无误后重新操作";
                return json($data);
            }
        }else{
            //通过手机号或者用户信息
            $user_service = new User();
            $user_info = $user_service->getUserInfoByMobile($mobile);
            
            if(empty($user_info)){
                $data['code'] = '-1';
                $data['message'] = "受让人不存在,请确认无误后重新操作";
                return json($data);
            }
            $user_id = $user_info['uid'];
        }
        
        if($user_id == $uid){
            
            $data['code'] = '-1';
            $data['message'] = "受让人不能为自己";
            return json($data);
        }
        
        $member = new MemberService(); 
        // 查看积分总数
        $menber_info = $member->getMemberDetail();
        if($menber_info['balance'] == 0 || $menber_info['balance'] < $money){
            $data['code'] = '-1';
            $data['message'] = "余额不足";
            return json($data);
        }
        //获取费率
        $config = new Config();
        $is_transfer_charge = $config->getConfig($this->instance_id, 'IS_TRANSFER_CHARGE', $this->website_id);
        $is_transfer_charge = empty($is_transfer_charge['value']) ? 0 : $is_transfer_charge['value'];
        $charge = 0;
        if($is_transfer_charge != 1){
            $charge = 0;//没有开启手续费
        }else{
            $charge_type = $config->getConfig($this->instance_id, 'CHARGE_TYPE', $this->website_id);
            if($charge_type['value'] == 1){
                //比例费率
                $charge_pares = $config->getConfig($this->instance_id, 'CHARGE_PARES', $this->website_id);
                $charge_pares_min = $config->getConfig($this->instance_id, 'CHARGE_PARES_MIN', $this->website_id);
                $charge = abs($money) * $charge_pares['value'] / 100;
                if($charge < $charge_pares_min['value']){
                    $charge = $charge_pares_min['value'];
                }
            }else{
                //固定费率
                $charge_pares2 = $config->getConfig($this->instance_id, 'CHARGE_PARES2', $this->website_id);
                $charge = $charge_pares2['value'];
            }
        }
        
        if(($money+$charge) > $menber_info['balance']){
            $data['code'] = '-1';
            $data['message'] = "本次转账不足以扣除手续费，请减少转账金额后重新操作";
            return json($data);
        }
        //扣除本人家覅  //补充写入转账记录
        $cash = $money+$charge;
        $text1 = "余额转账减少-".$remark;
        $retval1 = $member->addMemberAccount2(2, $uid, -$cash, $text1,50,$charge); 
        
        //转入受让积分
        $text = "余额转账获得-".$remark;
        $retval = $member->addMemberAccount2(2, $user_id, $money, $text,50);

        $data['code'] = '0';
        $data['message'] = "余额转账成功";
        return json($data);
    }
    /**
     * 余额积分兑换
     * types 1余额兑换积分 2积分兑换余额
     */
    public function transBalancePoint(){
        $types = request()->post('types', '');
        $money = request()->post('money', '');
        $uid = $this->uid;
        if(empty($types)){
            $data['code'] = '-1';
            $data['message'] = "类型不能为空";
            return json($data);
        }
        if(empty($money)){
            $data['code'] = '-1';
            $data['message'] = "兑换金额不能为空";
            return json($data);
        }
        if($types == 1){
            //余额兑换积分
            $member = new MemberService(); 
            // 查看余额总数
            $menber_info = $member->getMemberDetail();
            if($menber_info['balance'] == 0 || $menber_info['balance'] < $money){
                $data['code'] = '-1';
                $data['message'] = "余额不足";
                return json($data);
            }
            $config = new Config();
            $is_transfer_charge = $config->getConfig($this->instance_id, 'IS_TRANSFER_CHARGE', $this->website_id);
            $is_transfer_charge = empty($is_transfer_charge['value']) ? 0 : $is_transfer_charge['value'];
            if($is_transfer_charge != 1){
                $charge = 0;//没有开启手续费
            }else{
                $charge_type = $config->getConfig($this->instance_id, 'CHARGE_TYPE', $this->website_id);
                if($charge_type['value'] == 1){
                    //比例费率
                    $charge_pares = $config->getConfig($this->instance_id, 'CHARGE_PARES', $this->website_id);
                    $charge_pares_min = $config->getConfig($this->instance_id, 'CHARGE_PARES_MIN', $this->website_id);
                    $charge = abs($money) * $charge_pares['value'] / 100;
                    if($charge < $charge_pares_min['value']){
                        $charge = $charge_pares_min['value'];
                    }
                }else{
                    //固定费率
                    $charge_pares2 = $config->getConfig($this->instance_id, 'CHARGE_PARES2', $this->website_id);
                    $charge = $charge_pares2['value'];
                }
            }
            if(($money+$charge) > $menber_info['balance']){
                $data['code'] = '-1';
                $data['message'] = "本次余额兑换积分不足以扣除手续费，请减少兑换金额后重新操作";
                return json($data);
            }
            //扣除本人家覅  //补充写入转账记录
            $cash = $money+$charge;
            $text1 = "余额兑换积分减少";
            $retval1 = $member->addMemberAccount2(2, $uid, -$cash, $text1,51,$charge);
            
            //转入受让积分
            //积分汇率
            $convert_rate = $config->getConfig($this->instance_id, 'POINT_DEDUCTION_NUM', $this->website_id);
            $convert_rate_charge = $convert_rate['value'] ? $convert_rate['value'] : 1;
            $points = $money * $convert_rate_charge;
            $text = "余额兑换积分获得";

            $retval = $member->addMemberAccount2(1, $uid, $points, $text,51);

            $data['code'] = '0';
            $data['message'] = "额兑换积分成功";
            return json($data);
            
        }else{
            //积分兑换余额
            $config = new Config();
            //积分汇率转换成金额
            $convert_rate = $config->getConfig($this->instance_id, 'POINT_DEDUCTION_NUM', $this->website_id);
            $convert_rate_charge = $convert_rate['value'] ? $convert_rate['value'] : 1;
            $points = $money;
            $money = substr(sprintf("%.2f", $money/$convert_rate_charge),0,-1);
            
            $member = new MemberService(); 
            // 查看余额总数
            $menber_info = $member->getMemberDetail();
            // if($menber_info['balance'] == 0){
            //     $data['code'] = '-1';
            //     $data['message'] = "余额不足";
            //     return json($data);
            // }
            
            $is_transfer_charge = $config->getConfig($this->instance_id, 'IS_TRANSFER_CHARGE', $this->website_id);
            $is_transfer_charge = empty($is_transfer_charge['value']) ? 0 : $is_transfer_charge['value'];
            if($is_transfer_charge != 1){
                $charge = 0;//没有开启手续费
            }else{
                $charge_type = $config->getConfig($this->instance_id, 'CHARGE_TYPE', $this->website_id);
                if($charge_type['value'] == 1){
                    //比例费率
                    $charge_pares = $config->getConfig($this->instance_id, 'CHARGE_PARES', $this->website_id);
                    $charge_pares_min = $config->getConfig($this->instance_id, 'CHARGE_PARES_MIN', $this->website_id);
                    $charge = abs($money) * $charge_pares['value'] / 100;
                    if($charge < $charge_pares_min['value']){
                        $charge = $charge_pares_min['value'];
                    }
                }else{
                    //固定费率
                    $charge_pares2 = $config->getConfig($this->instance_id, 'CHARGE_PARES2', $this->website_id);
                    $charge = $charge_pares2['value'];
                }
            }
            if($charge > ($menber_info['balance']+$money)){
                $data['code'] = '-1';
                $data['message'] = "本次积分兑换余额不足以扣除手续费，请减少兑换金额后重新操作";
                return json($data);
            }
            //查询可用积分
            if($menber_info['point'] <= 0 || $money > $menber_info['point']){
                $data['code'] = '-1';
                $data['message'] = "可用积分不足";
                return json($data);
            }
            //扣除本人家覅  //补充写入转账记录
            //转入受让积分
            $text = "积分兑换余额获得";
            $retval = $member->addMemberAccount2(2, $uid, $money, $text,52);

            $cash = $money+$charge;
            $text1 = "积分兑换余额减少";
            $retval1 = $member->addMemberAccount2(1, $uid, -$points, $text1,52);
            $retval1 = $member->addMemberAccount2(2, $uid, -$charge, $text1,52,$charge);

            $data['code'] = '0';
            $data['message'] = "积分兑换余额成功";
            return json($data);
        }
    }
    /**
     * 主动锁定会员
     */
    public function CancellMember(){
        $website_id = $this->website_id;
        $uid = isset($_POST["id"]) ? $_POST["id"] : '';
        if($uid != $this->uid || empty($uid)){
            $data['code'] = -1;
            $data['message'] = "非法操作!";
            return json($data);
        }else{
            $member = new MemberService(); 
            $retval = $member->userWapLock($uid);
            $user = new User();
            $user->addUserLog($this->uid, 1, $this->controller, $this->action, \think\Request::instance()->ip(), "用户申请锁定".':'.$target,$retval);
            $data['code'] = 0;
            $data['message'] = "操作成功!";
            return json($data);
        }
    }
    
    /**
     * 支付完成修改发票状态
     * @param $out_trade_no
     */
    public function paySuccess2UpdataInvoiceInfo($out_trade_no)
    {
        if (!$out_trade_no) {return;}
        //修改发票状态
        if (getAddons('invoice', $this->website_id, $this->instance_id)) {
            $order = new VslOrderModel();
            $order_condition = [
                'out_trade_no | out_trade_no_presell' => $out_trade_no
            ];
            $order_lists = $order->getQuery($order_condition, 'order_no,order_money, presell_id, money_type, invoice_tax, order_status,invoice_type','order_id desc');
            if (!$order_lists) {return;}
            $invoice = new InvoiceServer();
            foreach ($order_lists as $order_list) {
                if (!$order_list['invoice_type']){
                    continue;
                }
                // 预售付尾款，修改invoice表发票价格
                if ($order_list['presell_id'] && $order_list['money_type'] == 2){
                    $data = [
                        'price' => $order_list['order_money'] + $order_list['invoice_tax']
                    ];
                } else {
                    $data = [
                        'order_status' => 1
                    ];
                }
                $invoice->updateInvoiceInfoByOrderNo($order_list['order_no'], $data);
            }
        }
        return;
    }
}