<?php
namespace data\service;

/**
 * 统一支付接口服务层
 */

use addons\blockchain\model\VslEosOrderPayMentModel;
use addons\channel\model\VslChannelOrderPaymentModel;
use addons\channel\server\Channel;
use addons\integral\service\Integral;
use addons\invoice\server\Invoice as InvoiceServer;
use data\model\AuthGroupModel;
use data\model\ConfigModel;
use data\model\SysAddonsModel;
use data\model\UserModel;
use data\model\VslIncreMentOrderModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslOrderModel;
use data\service\BaseService as BaseService;
use data\model\VslOrderPaymentModel;
use data\service\Pay\AlipayTradeWapPayContentBuilder;
use data\service\Pay\tlPay;
use data\service\Pay\WeiXinPay;
use data\service\Pay\AliPay;
use think\Db;
use think\Log;
use think\Cache;
use data\service\Pay\AlipayTradeAppPayContentBuilder;
use data\service\Pay\AlipayTradePagePayContentBuilder;
use data\service\Pay\AlipayTradeQueryContentBuilder;
use data\service\Pay\AlipayTradeRefundContentBuilder;
use data\service\Pay\AlipayTradeService;
use data\service\Pay\AlipayTradePrecreateContentBuilder;
use data\model\VslIncreMentOrderPaymentModel;
use data\extend\weixin\WxPayApi as WxPayApi;
use data\extend\weixin\WxPayData\WxPayOrderQuery;
use think\Session;

class UnifyPay extends BaseService
{

    function __construct()
    {
        parent::__construct();
    }
    /**
     * (non-PHPdoc)
     * @see \data\api\IUnifyPay::createOutTradeNo()
     */
     public function createOutTradeNo()
    {
        $cache = Cache::get("RB".time());
        if(empty($cache))
        {
            Cache::set("RB".time(), 1000);
            $cache = Cache::get("RB".time());
        }else{
            $cache = $cache+1;
            Cache::set("RB".time(), $cache);
        }
        $no = time().rand(1000,9999).$cache;
        return $no;
    }
    /**
     * 获取支付配置(non-PHPdoc)
     * @see \data\api\IUnifyPay::getPayConfig()
     */
    public function getPayConfig()
    {
        $instance_id = 0;
        $config = new Config();
        $wchat_pay = $config->getWpayConfig($instance_id);
        $ali_pay = $config->getAlipayConfig($instance_id);
		$globe_pay = $config->getGpayConfig($this->website_id);
        $data_config = array(
            'wchat_pay_config' => $wchat_pay,
            'ali_pay_config'   => $ali_pay,
			'globe_pay_config' => $globe_pay
        );
        return $data_config;
    }
    public function getPayConfigs()
    {
        $config = new Config();
        $wchat_pay = $config->getWpayConfigs();
        $ali_pay = $config->getAlipayConfigs();
		$globe_pay = $config->getGpayConfig($this->website_id);
        $data_config = array(
            'wchat_pay_config' => $wchat_pay,
            'ali_pay_config'   => $ali_pay,
			'globe_pay_config' => $globe_pay
        );
        return $data_config;
    }
    /**
 * 创建待支付单据
 * @param unknown $pay_no
 * @param unknown $pay_body
 * @param unknown $pay_detail
 * @param unknown $pay_money
 * @param unknown $type  订单类型  1. 商城订单  4.充值
 * @param unknown $pay_money
 */
    public function createPayment($shop_id, $out_trade_no, $pay_body, $pay_detail, $pay_money, $type, $type_alis_id, $create_time='')
    {
        if(empty($create_time)){
            $create_time = time();
        }
        $pay = new VslOrderPaymentModel();
        $data = array(
            'shop_id'       => $shop_id,
            'out_trade_no'  => $out_trade_no,
            'type'          => $type,
            'type_alis_id'  => $type_alis_id,
            'pay_body'      => $pay_body,
            'pay_detail'    => $pay_detail,
            'pay_money'     => $pay_money,
            'create_time'   => $create_time,
            'website_id'    => $this->website_id,
        );
        if($pay_money <= 0)
        {
            $data['pay_status'] = 1;
        }
        $res = $pay->save($data);
        return $res;
    }

    /**
     * 创建渠道商待支付单据
     * @param unknown $pay_no
     * @param unknown $pay_body
     * @param unknown $pay_detail
     * @param unknown $pay_money
     * @param unknown $type  订单类型  1. 商城订单  4.充值
     * @param unknown $pay_money
     */
    public function createChannelPayment($shop_id, $out_trade_no, $pay_body, $pay_detail, $pay_money, $type, $type_alis_id, $create_time='')
    {
        if(empty($create_time)){
            $create_time = time();
        }
        $pay = new VslChannelOrderPaymentModel();
        $data = array(
            'shop_id'       => $shop_id,
            'out_trade_no'  => $out_trade_no,
            'type'          => $type,
            'type_alis_id'  => $type_alis_id,
            'pay_body'      => $pay_body,
            'pay_detail'    => $pay_detail,
            'pay_money'     => $pay_money,
            'create_time'   => $create_time,
            'website_id'    => $this->website_id
        );
        if($pay_money <= 0)
        {
            $data['pay_status'] = 1;
        }
        $res = $pay->save($data);
        return $res;
    }
    /**
     * (non-PHPdoc)
     * @see \data\api\IUnifyPay::updatePayment()
     */
    public function updatePayment($out_trade_no,$shop_id, $pay_body, $pay_detail, $pay_money, $type, $type_alis_id)
    {
        $pay = new VslOrderPaymentModel();
        $data = array(
            'shop_id'       => $shop_id,
            'type'          => $type,
            'type_alis_id'  => $type_alis_id,
            'pay_body'      => $pay_body,
            'pay_detail'    => $pay_detail,
            'pay_money'     => $pay_money,
            'modify_time'   => time(),
            'website_id'    => $this->website_id
        );
        if($pay_money <= 0)
        {
            $data['pay_status'] = 1;
        }
        $res = $pay->save($data,['out_trade_no'=>$out_trade_no]);
        return $res;
    }
    
    /**
     * (non-PHPdoc)
     * @see \data\api\IUnifyPay::delPayment()
     */
    public function delPayment($out_trade_no){
        $pay = new VslOrderPaymentModel();
        $res = $pay->where('out_trade_no',$out_trade_no)->delete();
        return $res;
    }
    /**
     * 线上支付主动根据支付方式执行支付成功的通知(购买增值应用)
     * @param unknown $out_trade_no
     */
    public function onlinePays($out_trade_no, $pay_type, $trade_no)
    {
        $pay = new VslIncreMentOrderPaymentModel();
        try{
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no]);
            if($pay_info['pay_status'] == 1)
            {
                return 1;
                exit();
            }
            $data = array(
                'pay_status'     => 1,
                'pay_type'       => $pay_type,
                'pay_time'       => time(),
                'trade_no'      => $trade_no
            );
            $pay->save($data, ['out_trade_no' => $out_trade_no]);
            //订单状态改变
            $order = new VslIncreMentOrderModel();
            $order_info = $order->getInfo(['out_trade_no' => $out_trade_no],'*');
            if($order_info['order_type'] == 1){//增值应用订单
                $expire_time = 0;
                if($order_info['circle_time']==1){//一个月
                    $expire_time = 30*24*3600;
                }elseif($order_info['circle_time']==2){//三个月
                    $expire_time = 30*24*3600*3;
                }elseif($order_info['circle_time']==3){//五个月
                    $expire_time = 30*24*3600*5;
                }elseif($order_info['circle_time']==4){//一年
                    $expire_time = 365*24*3600;
                }elseif($order_info['circle_time']==5){//两年
                    $expire_time = 365*24*3600*2;
                }elseif($order_info['circle_time']==6){//三年
                    $expire_time = 365*24*3600*3;
                }elseif($order_info['circle_time']==7){//四年
                    $expire_time = 365*24*3600*4;
                }
                $order_infos = $order->getFirstData(['addons_id'=>$order_info['addons_id'],'order_status'=>2,'order_id'=>['neq',$order_info['order_id']], 'website_id' => $pay_info['website_id']],'order_id desc') ;
                if($order_infos && $order_infos['expire_time']>time()){//还未到期
                    $expire_times = $expire_time+$order_infos['expire_time'];
                }else{
                    $expire_times = time()+$expire_time;
                }
                $res = $order->save(['order_status'=>2,'expire_time'=>$expire_times,'payment_type'=>$pay_type], ['out_trade_no' => $out_trade_no]);
                $order_id  = $order_info['order_id'];
                //应用权限改变
                $use = new AuthGroupModel();
                $user_info = $use->getInfo(['is_system'=>1,'website_id'=>$pay_info['website_id'],'instance_id'=>0]);
                if($user_info['order_id']){
                    $default_order_id = explode(',',$user_info['order_id']);
                    if($order_infos){
                        if($order_infos['order_id']){
                            $arr_flip = array_flip($default_order_id);
                            unset($arr_flip[$order_infos['order_id']]);
                            $string_to_array = array_flip($arr_flip);
                            $string_end = implode(",", $string_to_array);
                            $order_ids = $string_end .','.$order_id;
                        }else{
                            $order_ids = $user_info['order_id'].','.$order_id;
                        }
                    }else{
                        $order_ids = $user_info['order_id'].','.$order_id;
                    }
                    
                }else{
                    $order_ids= $order_id;
                }
                $use->save(['order_id'=>$order_ids],['is_system'=>1,'website_id'=>$pay_info['website_id'],'instance_id'=>0]);
                //支付完成,重新setAddons
                $addons_model = new SysAddonsModel();
                $addons = $addons_model->Query(['id' => $order_info['addons_id']],'name')[0];
                setAddons($addons,$pay_info['website_id']);
            }elseif($order_info['order_type'] == 2){//充值完成，更改商家短信余额
                $res = $order->save(['order_status'=>2,'payment_type'=>$pay_type], ['out_trade_no' => $out_trade_no]);
                $messageServer = new Addons();
                $messageServer->addMessageCount($order_info['circle_time'], $pay_info['website_id']);
            }
            return $res;
        }catch(\Exception $e)
        {
            recordErrorLog($e);
            Log::write("weixin-------------------------------".$e->getMessage());
            return $e->getMessage();
        }
    }
    /**
     * 线上支付主动根据支付方式执行支付成功的通知
     * @param unknown $out_trade_no
     */
    public function onlinePay($out_trade_no, $pay_type, $trade_no)
    {
        //渠道商订单 QD前缀
        if( strstr($out_trade_no, 'QD') ){
            $res = $this->updateChannelPay($out_trade_no, $pay_type, $trade_no);
            return $res;
        } elseif (strstr($out_trade_no, 'DH')) {
            $res = $this->updateIntegralPay($out_trade_no, $pay_type, $trade_no);
            return $res;
        } elseif (strstr($out_trade_no, 'MD')) {
            $res = $this->updateStorePay($out_trade_no, $pay_type, $trade_no);
            return $res;
        }
        $pay = new VslOrderPaymentModel();
        $order = new VslOrderModel();
        try{
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no]);
            if($pay_info['pay_status'] == 1)
            {
                return 1;
                exit();
            }
            //因为会回调多次，所以预售的订单支付状态会更改多次，直到最终状态，这是不对的。如果是预售订单，则通过'out_trade_no'查一下，查出money_type为1则说明已经支付过定金了
            $is_presell_order_pay = $order->where(['out_trade_no' => $out_trade_no])->find();
            if($is_presell_order_pay  && $is_presell_order_pay['presell_id'] != 0 && $is_presell_order_pay['money_type'] == 1){
                return 1;
                exit;
            }
//            $order_info = $order->getInfo(['out_trade_no' => $out_trade_no]);
            $order_info = $order->where(['out_trade_no' => $out_trade_no])->whereOr(['out_trade_no_presell'=>$out_trade_no])->find();
            if (!($order_info['money_type'] == 0 && $order_info['presell_id'] != 0)) {//付款类型为0并且为预售订单，第一次付款不能更新order_payment
                $data = array(
                    'pay_status'     => 1,
                    'pay_type'       => $pay_type,
                    'pay_time'       => time(),
                    'trade_no'      => $trade_no
                );
                $pay->save($data, ['out_trade_no' => $out_trade_no]);
            }
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'type');
            switch ( $pay_info['type']){
                case 1:
                    //订单
                    $order = new Order();
                    $order->orderOnLinePay($out_trade_no, $pay_type);
                    break;
                case 2:
//                    $assistant = new NbsBusinessAssistant();
//                    $assistant->payOnlineBusinessAssistantApply($out_trade_no);
                    break;
                case 4:
                    //余额充值
                    $member = new Member();
                    $member->payMemberRecharge($out_trade_no, $pay_type);
                    break;
                case 5:
                    //货款充值
                    $member = new Member();
                    $member->payMemberRecharge($out_trade_no, $pay_type, 1);
                    break;
                default:
                    break;
            }
            //修改发票状态
            if(getAddons('invoice', $this->website_id)){
            $invoice = new InvoiceServer();
            $invoice->updateOrderStatusByOutTradeNo($out_trade_no, 1);
            }
            return 1;
        }catch(\Exception $e)
        {
            recordErrorLog($e);
            Log::write("订单支付-------------------------------".$e->getMessage());
            return $e->getMessage();
        }
    
    }
    /*
     * 兑换订单支付成功，进行订单的创建
     * **/
    public function updateIntegralPay($out_trade_no, $pay_type, $trade_no)
    {
        try{
            /*$pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no]);
            if($pay_info['pay_status'] == 1)
            {
                return 1;
                exit();
            }*/
            $pay = new VslOrderPaymentModel();
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no]);
            if($pay_info['pay_status'] == 1){
                return 1;
            }
            //创建兑换订单
            $redis = $this->connectRedis();
            $key = 'integral_pay_'.$out_trade_no;
            $order_str = $redis->get($key);
            $order_data = json_decode($order_str, true);
            if(getAddons('integral', $this->website_id, $this->instance_id)){
                $integral_server = new Integral();
                $order_id = $integral_server->createIntegralOrder($order_data);
            }
            if($order_id == 0){
                return 0;
            }
            //更新order_payment表
            $payment_data = array(
                'pay_status'     => 1,
                'pay_type'       => $pay_type,
                'pay_time'       => time(),
            );
            $pay->save($payment_data, ['out_trade_no' => $out_trade_no]);
            $data = array(
                'payment_type' => $pay_type,
            );
            $order = new VslOrderModel();
            $out_trade_no = json_decode(json_encode($out_trade_no), true)[0];
            $order->save($data, ['out_trade_no' => $out_trade_no]);
            //扣除积分
            $exchange_point = $order_data['goods_list']['exchange_point'];
            $this->calculateMemberPoint($order_data['uid'], $order_data['website_id'], $exchange_point, $order_id);
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'type');
            switch ($pay_info['type']) {
                case 1:
                    //订单
                    $order = new Order();
                    $order->orderOnLinePay($out_trade_no, $pay_type);
                    break;
                case 2:
//                    $assistant = new NbsBusinessAssistant();
//                    $assistant->payOnlineBusinessAssistantApply($out_trade_no);
                    break;
                default:
                    break;
            }
            return 1;
        }catch(\Exception $e)
        {
            recordErrorLog($e);
            Log::write("weixin-------------------------------".$e->getMessage());
            return $e->getMessage();
        }
    }
    /*
     * 扣除用户的购买积分并记录流水
     * **/
    public function calculateMemberPoint($uid, $website_id, $point, $order_id)
    {
        try{
            $member_account = new VslMemberAccountModel();
            $member_account_record = new VslMemberAccountRecordsModel();
            $member_account_list = $member_account->where(['uid'=>$uid,'website_id'=>$website_id])->find();
            $member_point = $member_account_list->point;
            $member_account_list->point = $member_point-$point;
            $member_account_list->save();
            //添加会员账户流水
            $data = array(
                'uid' => $uid,
                'shop_id' => 0,
                'records_no' => 'Dh' . getSerialNo(),
                'sign' => 0,
                'number' => -$point,
                'from_type' => 3,
                'data_id' => $order_id,
                'text' => '积分兑换',
                'create_time' => time(),
                'website_id' => $website_id,
                'account_type' => 1,//余额
            );
            $res = $member_account_record->save($data);
            return $res;
        }catch(\Exception $e){
            recordErrorLog($e);
            echo $e->getMessage();exit;
        }
    }
    /*
     * 渠道商订单支付成功，后续更新订单以及通知
     * **/
    public function updateChannelPay($out_trade_no, $pay_type, $trade_no)
    {
        $pay = new VslChannelOrderPaymentModel();
        try{
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no]);
            if($pay_info['pay_status'] == 1)
            {
                return 1;
                exit();
            }
            $data = array(
                'pay_status'     => 1,
                'pay_type'       => $pay_type,
                'pay_time'       => time(),
                'trade_no'      => $trade_no
            );
            $pay->save($data, ['out_trade_no' => $out_trade_no]);

            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'type');
            switch ( $pay_info['type']){
                case 1:
                    if(getAddons('channel', $this->website_id)){
                        //订单
                        $channel_server = new Channel();
                        $channel_server->channelOrderUpdateStatus($out_trade_no, $pay_type);
                    }
                    break;
                case 2:
//                    $assistant = new NbsBusinessAssistant();
//                    $assistant->payOnlineBusinessAssistantApply($out_trade_no);
                    break;
                case 4:
                    //充值
                    $member = new Member();
                    $member->payMemberRecharge($out_trade_no, $pay_type);
                    break;
                default:
                    break;
            }
            return 1;
        }catch(\Exception $e)
        {
            recordErrorLog($e);
            Log::write("weixin-------------------------------".$e->getMessage());
            return $e->getMessage();
        }

    }
    /**
     * 只是执行单据支付，不进行任何处理用于执行支付后被动调用
     * @param unknown $out_trade_no
     * @param unknown $pay_type
     */
    public function offLinePay($out_trade_no, $pay_type)
    {
        $pay = new VslOrderPaymentModel();
        $data = array(
            'pay_status'     => 1,
            'pay_type'       => $pay_type,
            'pay_time'   => time()
        );
        $retval = $pay->save($data, ['out_trade_no' => $out_trade_no]);
        return $retval;
    }
    /**
     * 获取支付信息
     * @param unknown $out_trade_no
     */
    public function getPayInfo($out_trade_no)
    {
        $pay = new VslOrderPaymentModel();
        $info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'pay_body,pay_detail,create_time,pay_status,sum(pay_money) as pay_money,out_trade_no,website_id');
        return $info;
    }
    /**
     * 检测支付状态
     * @param unknown $out_trade_no
     */
    public function checkPayStatus($out_trade_no)
    {
        $pay = new VslOrderPaymentModel();
        $list = $pay->getQuery(['out_trade_no' => $out_trade_no, 'pay_status' => 0], 'out_trade_no','');
        if(!$list){
            return false;
        }
        return true;
    }
    /**
     * 获取预售支付信息
     * @param unknown $out_trade_no
     */
    public function getPresellPayInfo($out_trade_no)
    {
        $pay = new VslOrderPaymentModel();
        $info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'pay_body,pay_detail,create_time,pay_status,pay_money,out_trade_no');
        return $info;
    }
    /**
     * 获取订购增值支付信息
     * @param unknown $out_trade_no
     */
    public function getIncrementPayInfo($out_trade_no)
    {
        $pay = new VslIncrementOrderPaymentModel();
        $info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'pay_body,pay_detail,create_time,pay_status,sum(pay_money) as pay_money,out_trade_no');
        return $info;
    }
    /**
     * 获取购买内存支付信息
     * @param unknown $out_trade_no
     */
    public function getEosOrderPayInfo($out_trade_no)
    {
        $pay = new VslEosOrderPayMentModel();
        $info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'pay_body,pay_detail,create_time,pay_status,sum(pay_money) as pay_money,out_trade_no');
        return $info;
    }
    /**
     * 创建订购增值支付信息
     * @param unknown $out_trade_no
     */
    public function createIncrementPayment($out_trade_no,$pay_type,$order_id,$pay_body,$pay_detail,$pay_money,$create_time)
    {
        if(empty($create_time)){
            $create_time = time();
        }
        $pay = new VslIncreMentOrderPaymentModel();
        $data = array(
            'out_trade_no'  => $out_trade_no,
            'pay_type'      => $pay_type,
            'type_alis_id'  => $order_id,
            'pay_body'      => $pay_body,
            'pay_detail'    => $pay_detail,
            'pay_money'     => $pay_money,
            'create_time'   => $create_time,
            'website_id'    => $this->website_id
        );
        if($pay_money <= 0)
        {
            $data['pay_status'] = 1;
        }
        $res = $pay->save($data);
        return $res;
    }
    //获取交易号的订单信息
    public function get_order_info($out_trade_no){

        $pay = new VslOrderPaymentModel();
        $info = $pay->alias('a')->join('vsl_order b','a.type_alis_id=b.order_id')->field('b.pay_status,b.money_type,b.order_id')->where(['a.out_trade_no'=>$out_trade_no])->find();
        return $info;

    }
    /**
     * 获取渠道商支付信息
     * @param unknown $out_trade_no
     */
    public function getChannelPayInfo($out_trade_no)
    {
        $pay = new VslChannelOrderPaymentModel();
        $info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'pay_body,pay_detail,create_time,pay_status,sum(pay_money) as pay_money,out_trade_no');
        return $info;
    }
    /**
     * 获取eos支付信息
     * @param unknown $out_trade_no
     */
    public function getEosPayInfo($out_trade_no)
    {
        $pay = new VslEosOrderPayMentModel();
        $info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'pay_body,pay_detail,create_time,pay_status,pay_money,out_trade_no');
        return $info;
    }
    /**
     * 获取积分商城支付信息
     * @param unknown $out_trade_no
     */
    public function getIntegralPayInfo($out_trade_no)
    {
        $redis = $this->connectRedis();
        $key = 'integral_pay_'.$out_trade_no;
        $pay_str = $redis->get($key);
        $order_data = json_decode($pay_str,true);
        $num = $order_data['goods_list']['num'];
        $price = $order_data['goods_list']['price'];
        $shipping_fee = $order_data['goods_list']['shipping_fee'];
        $pay_data['pay_body'] = '兑换订单';
        $pay_data['pay_detail'] = '兑换订单';
        $pay_data['pay_time'] = time();
        //支付金额
        $pay_data['pay_money'] = $num * $price + $shipping_fee;
        $pay_data['out_trade_no'] = $out_trade_no;
        return $pay_data;
    }
    /**
     * 重新设置编号，用于修改价格订单
     * @param unknown $order_id
     * @param unknown $new_no
     * @return Ambigous <number, \think\false, boolean, string>
     */
    public function modifyNo($order_id, $new_no)
    {
        $pay = new VslOrderPaymentModel();
        $data = array(
            "out_trade_no" => $new_no
        );
        $retval = $pay->where(['type_alis_id' => $order_id])->update($data);
        return $retval;
    }
    /**
     * 重新设置编号，用于修改价格订单
     * @param unknown $order_id
     * @param unknown $new_no
     * @return Ambigous <number, \think\false, boolean, string>
     */
    public function modifyChannelNo($order_id, $new_no)
    {
        $pay = new VslChannelOrderPaymentModel();
        $data = array(
            "out_trade_no" => $new_no
        );
        $retval = $pay->where(['type_alis_id' => $order_id])->update($data);
        return $retval;
    }
    /**
     * 修改支付价格
     * @param unknown $out_trade_no
     */
    public function modifyPayMoney(array $condition, $pay_money)
    {
        $pay = new VslOrderPaymentModel();
        $data = array(
            'pay_money'       => $pay_money
        );
        $retval = $pay->save($data, $condition);
    }
    public function payApplyAgree($id,$out_trade_no,$notify_url,$website_id)
    {
        //渠道商订单号 QD前缀
        if (strstr($out_trade_no, 'QD')) {//渠道商订单
            $data = $this->getChannelPayInfo($out_trade_no);
        } elseif (strstr($out_trade_no, 'DH')) {//兑换订单
            $data = $this->getIntegralPayInfo($out_trade_no);
        } elseif (strstr($out_trade_no, 'eos')) {//购买eos内存订单
            $data = $this->getEosPayInfo($out_trade_no);
        }else {
            $data = $this->getPayInfo($out_trade_no);
        }
        if($data < 0)
        {
            return $data;
        }
        $tl_pay = new tlPay();
        $retval = $tl_pay->payApplyAgree($id,$data['pay_money']*100,$data['pay_detail'], $out_trade_no,$notify_url,$website_id);
        return $retval;

        // TODO Auto-generated method stub

    }
    public function payAgreeConfirm($id,$smscode,$thpinfo,$out_trade_no,$website_id)
    {
        $tl_pay = new tlPay();
        $retval = $tl_pay->payAgree($id,$smscode,$thpinfo,$out_trade_no,$website_id);
        return $retval;

        // TODO Auto-generated method stub

    }
    public function paySmsAgree($out_trade_no,$thpinfo,$website_id)
    {
        $tl_pay = new tlPay();
        $retval = $tl_pay->paySmsAgree($out_trade_no,$thpinfo,$website_id);
        return $retval;

        // TODO Auto-generated method stub

    }
    public function tlSigning($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$uid,$website_id)
    {
        $tl_pay = new tlPay();
        $retval = $tl_pay->setTlSigning($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$uid,$website_id);
        return $retval;
        // TODO Auto-generated method stub
    }
    public function tlAgreeSms($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$thpinfo,$uid,$website_id)
    {
        $tl_pay = new tlPay();
        $retval = $tl_pay->setTlAgreeSms($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$thpinfo,$uid,$website_id);
        return $retval;
        // TODO Auto-generated method stub
    }
    public function tlAgreeSigning($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$smscode,$thpinfo,$uid,$website_id)
    {
        $tl_pay = new tlPay();
        $retval = $tl_pay->setTlAgreeSigning($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$smscode,$thpinfo,$uid,$website_id);
        return $retval;
        // TODO Auto-generated method stub
    }
    public function tlUntying($id,$website_id)
    {
        $tl_pay = new tlPay();
        $retval = $tl_pay->tlUntying($id,$website_id);
        return $retval;
        // TODO Auto-generated method stub
    }
	/* (non-PHPdoc)
     * @see \data\api\IUnifyPay::wchatPay()
     */
    public function wchatPay($out_trade_no, $trade_type, $red_url)
    {
        //渠道商订单号 QD前缀
        if (strstr($out_trade_no, 'QD')) {//渠道商订单
            $data = $this->getChannelPayInfo($out_trade_no);
        } elseif (strstr($out_trade_no, 'DH')) {//兑换订单
            $data = $this->getIntegralPayInfo($out_trade_no);
        } elseif (strstr($out_trade_no, 'eos')) {//购买eos内存订单
            $data = $this->getEosPayInfo($out_trade_no);
        }else {
            $data = $this->getPayInfo($out_trade_no);
        }

        if($data < 0)
        {
            return $data;
        }
        $weixin_pay = new WeiXinPay();
        if($trade_type == 'JSAPI')
        {
//            $openid = $weixin_pay->get_openid();
            $user  = new UserModel();
            $openid = $user->getInfo(['uid'=>$this->uid],'wx_openid')['wx_openid'];
            $product_id = '';
        }
        if($trade_type == 'NATIVE')
        {
            $openid = '';
            $product_id = $out_trade_no;
        }
        if($trade_type == 'MWEB')
        {
            $openid = '';
            $product_id = $out_trade_no;
        }
        if($trade_type == 'APP')
        {
            $openid = '';
            $product_id = $out_trade_no;
        }
        if($trade_type == 'APP'){
            $retval = $weixin_pay->setWeiXinApp($data['pay_body'], $data['pay_detail'], $data['pay_money']*100, $out_trade_no, $red_url, $trade_type, $openid, $product_id);
        }else{
            $retval = $weixin_pay->setWeiXinPay($data['pay_body'], $data['pay_detail'], $data['pay_money']*100, $out_trade_no, $red_url, $trade_type, $openid, $product_id);
        }
        return $retval;
        
        // TODO Auto-generated method stub
        
    }
    public function wchatPayMir($out_trade_no, $trade_type, $red_url, $website_id = 0)
    {
        //渠道商订单号 QD前缀
        if (strstr($out_trade_no, 'QD')) {//渠道商订单
            $data = $this->getChannelPayInfo($out_trade_no);
        } elseif (strstr($out_trade_no, 'DH')) {//兑换订单
            $data = $this->getIntegralPayInfo($out_trade_no);
        }elseif (strstr($out_trade_no, 'eos')) {//购买eos内存订单
            $data = $this->getEosPayInfo($out_trade_no);
        }else {
            $data = $this->getPayInfo($out_trade_no);
        }

        if($data < 0)
        {
            return $data;
        }
        $weixin_pay = new WeiXinPay();
        if($trade_type == 'JSAPI')
        {
//            $openid = $weixin_pay->get_openid();
            $user  = new UserModel();
            $openid = $user->getInfo(['uid'=>$this->uid],'mp_open_id')['mp_open_id'];
            $product_id = '';
        }
        $retval = $weixin_pay->setWeiXinPayMir($data['pay_body'], $data['pay_detail'], $data['pay_money']*100, $out_trade_no, $red_url, $trade_type, $openid, $product_id, $website_id);
        return $retval;

        // TODO Auto-generated method stub

    }
	public function globePayMir($out_trade_no)
    {
        //渠道商订单号 QD前缀
        if (strstr($out_trade_no, 'QD')) {//渠道商订单
            $data = $this->getChannelPayInfo($out_trade_no);
        } elseif (strstr($out_trade_no, 'DH')) {//兑换订单
            $data = $this->getIntegralPayInfo($out_trade_no);
        }elseif (strstr($out_trade_no, 'eos')) {//购买eos内存订单
            $data = $this->getEosPayInfo($out_trade_no);
        }else {
            $data = $this->getPayInfo($out_trade_no);
        }
        return $data;

        // TODO Auto-generated method stub

    }
    public function wchatPays($out_trade_no, $trade_type, $red_url)
    {

        $data = $this->getIncrementPayInfo($out_trade_no);
        if(strstr($out_trade_no, 'eos')){
            $data = $this->getEosPayInfo($out_trade_no);
        }elseif(strstr($out_trade_no, 'MD')){//门店扫码订单
            $data = $this->getStorePayInfo($out_trade_no);
        }
        if($data < 0)
        {
            return $data;
        }
        $weixin_pay = new WeiXinPay();
        $openid = '';
        $product_id = $out_trade_no;
        if(strstr($out_trade_no, 'MD')){
            $retval = $weixin_pay->setWeiXinPay($data['pay_body'], $data['pay_detail'], $data['pay_money']*100, $out_trade_no, $red_url, $trade_type, $openid, $product_id);
        }else{
            $retval = $weixin_pay->setWeiXinPays($data['pay_body'], $data['pay_detail'], $data['pay_money']*100, $out_trade_no, $red_url, $trade_type, $openid, $product_id);
        }
        return $retval;

        // TODO Auto-generated method stub

    }
	/* (non-PHPdoc)
     * @see \data\api\IUnifyPay::aliPay()
     */
    public function aliPayNew($out_trade_no, $notify_url, $return_url)
    {
        if (strstr($out_trade_no, 'DH')) {
            $data = $this->getIntegralPayInfo($out_trade_no);
        }elseif (strstr($out_trade_no, 'eos')) {//购买eos内存订单
            $data = $this->getEosPayInfo($out_trade_no);
        } else {
            $data = $this->getPayInfo($out_trade_no);
        }
        if(!$data)
        {
            return false;
        }

        //订单名称，必填
        $subject = $data['pay_body'];

        //付款金额，必填
        $total_amount = $data['pay_money'];

        //商品描述，可空
        $body = $data['pay_detail'];

	//构造参数
	$payRequestBuilder = new AlipayTradePagePayContentBuilder();
	$payRequestBuilder->setBody($body);
	$payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
	$payRequestBuilder->setOutTradeNo($out_trade_no);
        $ali_pay = new AlipayTradeService();
        $retval = $ali_pay->pagePay($payRequestBuilder,$return_url,$notify_url);
        return $retval;
        // TODO Auto-generated method stub
        
    }
    public function aliPayNewWap($out_trade_no, $notify_url, $return_url)
    {
        if (strstr($out_trade_no, 'QD')) {//渠道商订单
            $data = $this->getChannelPayInfo($out_trade_no);
        }elseif (strstr($out_trade_no, 'DH')) {
            $data = $this->getIntegralPayInfo($out_trade_no);
        }elseif (strstr($out_trade_no, 'eos')) {//购买eos内存订单
            $data = $this->getEosPayInfo($out_trade_no);
        } else {
            $data = $this->getPayInfo($out_trade_no);
        }
        if(!$data)
        {
            return false;
        }

        //订单名称，必填
        $subject = $data['pay_body'];

        //付款金额，必填
        $total_amount = $data['pay_money'];

        //商品描述，可空
        $body = $data['pay_detail'];

        //构造参数
        $payRequestBuilder = new AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $ali_pay = new AlipayTradeService();
        $retval = $ali_pay->wapPay($payRequestBuilder,$return_url,$notify_url);
        return $retval;
        // TODO Auto-generated method stub
    }
    public function aliPayNewApp($out_trade_no, $notify_url,$return_url)
    {
        if (strstr($out_trade_no, 'QD')) {//渠道商订单
            $data = $this->getChannelPayInfo($out_trade_no);
        }elseif (strstr($out_trade_no, 'DH')) {
            $data = $this->getIntegralPayInfo($out_trade_no);
        }elseif (strstr($out_trade_no, 'eos')) {//购买eos内存订单
            $data = $this->getEosPayInfo($out_trade_no);
        } else {
            $data = $this->getPayInfo($out_trade_no);
        }
        if(!$data)
        {
            return false;
        }

        //订单名称，必填
        $subject = $data['pay_body'];

        //付款金额，必填
        $total_amount = $data['pay_money'];

        //商品描述，可空
        $body = $data['pay_detail'];

        //构造参数
        $payRequestBuilder = new AlipayTradeAppPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $ali_pay = new AlipayTradeService();
        $retval = $ali_pay->appPay($payRequestBuilder,$return_url,$notify_url);
        return $retval;
        // TODO Auto-generated method stub
    }
    /* (non-PHPdoc)
     * @see 当面付
     */
    public function aliPayNews($out_trade_no, $notify_url)
    {
        $data = $this->getIncrementPayInfo($out_trade_no);
        if (strstr($out_trade_no, 'eos')) {//购买eos内存订单
            $data = $this->getEosPayInfo($out_trade_no);
        }elseif (strstr($out_trade_no, 'MD')) {
            $data = $this->getStorePayInfo($out_trade_no);
        }
        if(!$data)
        {
            return false;
        }
        $outTradeNo = $out_trade_no;
        $subject = $data['pay_body'];
        $totalAmount = $data['pay_money'];
        $undiscountableAmount = "0.01";
        $body = $data['pay_detail'];
        $operatorId = "test_operator_id";
        $storeId = "test_store_id";

        $timeExpress = "5m";
        $goodsDetailList = array();
        $qrPayRequestBuilder = new AlipayTradePrecreateContentBuilder();
        $qrPayRequestBuilder->setOutTradeNo($outTradeNo);
        $qrPayRequestBuilder->setTotalAmount($totalAmount);
//        $qrPayRequestBuilder->setTimeExpress($timeExpress);
        $qrPayRequestBuilder->setSubject($subject);
//        $qrPayRequestBuilder->setBody($body);
//        $qrPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
//        $qrPayRequestBuilder->setGoodsDetailList($goodsDetailList);
//        $qrPayRequestBuilder->setStoreId($storeId);
//        $qrPayRequestBuilder->setOperatorId($operatorId);
        // 调用qrPay方法获取当面付应答
        $qrPay = new AlipayTradeService();
        $qrPayResult = $qrPay->qrPay($qrPayRequestBuilder,$notify_url);
        //	根据状态值进行业务处理
        switch ($qrPayResult->getTradeStatus()){
            case "SUCCESS":
//                echo "支付宝创建订单二维码成功:";
                $response = $qrPayResult->getResponse();
                $qrcode = $response->qr_code;
                return $qrcode;
                break;
            case "FAILED":
//                echo "支付宝创建订单二维码失败!!!";
                return -1;
                break;
            case "UNKNOWN":
//                echo "系统异常，状态未知!!!";
                return -2;
                break;
            default:
//                echo "不支持的返回状态，创建订单二维码返回异常!!!";
                return -3;
                break;
        }
        // TODO Auto-generated method stub

    }
    public function aliPayNewResult($out_trade_no)
    {
        $data = $this->getPayInfo($out_trade_no);
        if(!$data)
        {
            return false;
        }
	//构造参数
	$payRequestBuilder = new AlipayTradeQueryContentBuilder();
	$payRequestBuilder->setOutTradeNo($out_trade_no);
        $ali_pay = new AlipayTradeService();
        $retval = $ali_pay->Query($payRequestBuilder);
        return $retval;
        // TODO Auto-generated method stub
        
    }
    //请求验签
    public function tlpayNotify($a,$website_id){
        $tlpaySevice = new tlPay();
        $config = new ConfigModel();
        $tl_config = $config->getInfo(['website_id'=>$website_id,'key'=>'TLPAY'],'value');
        $info= json_decode($tl_config['value'], true);
        $result = $tlpaySevice->validSign($a,$info['tl_key']);
        return $result;
    }
    //回调验签
    public function tlpayNotifys($a,$website_id){
        $tlpaySevice = new tlPay();
        $config = new ConfigModel();
        $tl_config = $config->getInfo(['website_id'=>$website_id,'key'=>'TLPAY'],'value');
        $info= json_decode($tl_config['value'], true);
        $result = $tlpaySevice->validSigns($a,$info['tl_key']);
        return $result;
    }
    public function alipayNotify($a,$website_id){
        $alipaySevice = new AlipayTradeService();
        $result = $alipaySevice->check($a,$website_id);
        return $result;
    }
    public function alipayNotifys($a){
        $alipaySevice = new AlipayTradeService();
        $result = $alipaySevice->checks($a);
        return $result;
    }
    public function blockChainNotify($data,$public_key,$key,$sign){
        $alipaySevice = new AlipayTradeService();
        $result = $alipaySevice->checkKey($data,$public_key,$key, $sign);
        return $result;
    }
    /**
     * (non-PHPdoc)
     * @see \data\api\IUnifyPay::getWxJsApi()
     */
    public function getWxJsApi($UnifiedOrderResult)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->GetJsApiParameters($UnifiedOrderResult);
        return $retval;
    }
    public function getWxJsApiApp($UnifiedOrderResult)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->GetJsApiParameterApp($UnifiedOrderResult);
        return $retval;
    }
    public function getWxJsApiMir($UnifiedOrderResult,$appid)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->GetJsApiParametersMir($UnifiedOrderResult,$appid);
        return $retval;
    }
    /**
     * (non-PHPdoc)
     * @see \data\api\IOrder::getVerifyResult()
     */
    public function getVerifyResult($type){
        $pay = new AliPay();
        $verify = $pay->getVerifyResult($type);
        return $verify;
    }
    /**
     * 微信支付检测签名串
     * @param unknown $post_obj
     * @param unknown $sign
     */
    public function checkSign($post_obj, $sign,$website_id=0)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->checkSign($post_obj,$sign,$website_id);
        return $retval;
    }
    /**
     * 小程序支付检测签名串
     * @param $post_obj
     * @param $sign
     * @param int $website_id
     */
    public function checkSignMp($post_obj, $sign,$website_id=0)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->checkSignMp($post_obj,$sign,$website_id);
        return $retval;
    }
    public function checkSignApp($post_obj, $sign,$website_id=0)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->checkSignApp($post_obj, $sign,$website_id);
        return $retval;
    }
    public function checkSigns($post_obj, $sign,$website_id=0)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->checkSigns($post_obj, $sign);
        return $retval;
    }
    /**
     * 微信退款
     * @param unknown $refund_no
     * @param unknown $out_trade_no
     * @param unknown $refund_fee
     * @param unknown $total_fee
     */
    public function setWeiXinRefund($refund_no, $out_trade_no, $refund_fee, $total_fee)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->setWeiXinRefund($refund_no, $out_trade_no, $refund_fee, $total_fee);
        return $retval;
    }
    /**
     * 支付宝原路退款
     * @param unknown $refund_no
     * @param unknown $out_trade_no商户订单号不是支付流水号
     * @param unknown $refund_fee
     */
    public function aliPayRefund($refund_no, $out_trade_no, $refund_fee)
    {
        $pay = new AliPay();
        $retval = $pay->aliPayRefund($refund_no, $out_trade_no, $refund_fee);
        return $retval;
    }
    public function aliPayNewRefund($refund_no, $out_trade_no, $refund_fee)
    {
        $data = $this->getPayInfo($out_trade_no);
        if(!$data)
        {
            return false;
        }
	//构造参数
	$payRequestBuilder = new AlipayTradeRefundContentBuilder();
	$payRequestBuilder->setOutTradeNo($out_trade_no);
	$payRequestBuilder->setRefundAmount($refund_fee);
	$payRequestBuilder->setOutRequestNo($refund_no);
        $ali_pay = new AlipayTradeService($data['website_id']);
        $retval = $ali_pay->Refund($payRequestBuilder);
        return $retval;
    }
    
    /*
     * 微信支付订单查询
     */
    public function orderQuery($data = []){
        $orderModel = new VslOrderModel();
        $paymentType = $orderModel->getInfo(['out_trade_no' => $data['out_trade_no']],'payment_type')['payment_type'];
        if($paymentType != 1){
            return $data;
        }
        $WxPayApi = new WxPayApi();
        $input = new WxPayOrderQuery();
        $input->SetOut_trade_no($data['out_trade_no']);
        $res = $WxPayApi->orderQuery($input,5);
        if ($res["return_code"] && $res["return_code"] == "SUCCESS" && $res["result_code"] && $res["result_code"] == "SUCCESS") {
            if($res['trade_state'] == 'SUCCESS'){
                $data['pay_status'] = 1;
            }
        }
        return $data;
    }
    
    /**
     * 获取门店扫码订单支付信息
     * @param unknown $out_trade_no
     */
    public function getStorePayInfo($out_trade_no)
    {
        $redis = $this->connectRedis();
        $key = 'store_pay_'.$out_trade_no;
        $pay_str = $redis->get($key);
        $order_data = json_decode($pay_str,true);
        $pay_data['pay_body'] = '门店订单';
        $pay_data['pay_detail'] = '门店订单';
        $pay_data['pay_time'] = time();
        //支付金额
        $pay_data['pay_money'] = $order_data['total_amount'];
        $pay_data['out_trade_no'] = $out_trade_no;
        return $pay_data;
    }
    
    /*
     * 门店订单支付成功，进行订单的创建
     * **/
    public function updateStorePay($out_trade_no, $pay_type, $trade_no)
    {
        try{
            /*$pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no]);
            if($pay_info['pay_status'] == 1)
            {
                return 1;
                exit();
            }*/
            $pay = new VslOrderPaymentModel();
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no]);
            if($pay_info['pay_status'] == 1){
                return 1;
            }
            //创建兑换订单
            $redis = $this->connectRedis();
            $key = 'store_pay_'.$out_trade_no;
            $order_str = $redis->get($key);
            $order_data = json_decode($order_str, true);
            $storeServer = new \addons\store\server\Store();
            $order_id = $storeServer->createStoreOrder($order_data);
            
            if($order_id == 0){
                return 0;
            }
            //更新order_payment表
            $payment_data = array(
                'pay_status'     => 1,
                'pay_type'       => $pay_type,
                'pay_time'       => time(),
            );
            $pay->save($payment_data, ['out_trade_no' => $out_trade_no]);
            $data = array(
                'payment_type' => $pay_type,
            );
            $order = new VslOrderModel();
            $out_trade_no = json_decode(json_encode($out_trade_no), true)[0];
            $order->save($data, ['out_trade_no' => $out_trade_no]);
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'type');
            switch ($pay_info['type']) {
                case 1:
                    //订单
                    $order = new Order();
                    $order->orderOnLinePay($out_trade_no, $pay_type);
                    break;
                case 2:
//                    $assistant = new NbsBusinessAssistant();
//                    $assistant->payOnlineBusinessAssistantApply($out_trade_no);
                    break;
                default:
                    break;
            }
            return 1;
        }catch(\Exception $e)
        {
            recordErrorLog($e);
            Log::write("weixin-------------------------------".$e->getMessage());
            return $e->getMessage();
        }
    }
    

}
