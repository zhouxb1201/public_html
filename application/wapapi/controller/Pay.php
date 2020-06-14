<?php
namespace app\wapapi\controller;

use addons\anticounterfeiting\model\AntiCounterfeitingBatchModel;
use addons\blockchain\model\VslEosOrderPayMentModel;
use addons\blockchain\service\Block;
use addons\anticounterfeiting\server\AntiCounterfeiting;
use addons\channel\model\VslChannelGoodsSkuModel;
use addons\channel\model\VslChannelOrderModel;
use addons\channel\model\VslChannelOrderPaymentModel;
use addons\channel\server\Channel;
use data\model\AddonsConfigModel;
use data\model\VslMemberRechargeModel;
use data\model\VslOrderModel;
use data\model\VslOrderPaymentModel;
use data\model\WebSiteModel;
use data\service\Order;
use data\service\Pay\tlPay;
use data\service\UnifyPay;
use think\Db;
use think\Debug;
use data\service\Pay\GlobePay as globalpay;

/**
 * 支付控制器
 *
 * @author  www.vslai.com
 *
 */
class Pay extends BaseController
{

    public $style;

    public $shop_config;

    protected $website_id;

    public function __construct()
    {
        parent::__construct();
    }

    /* 演示版本 */
    public function demoVersion()
    {
        return view($this->style . 'Pay/demoVersion');
    }

    //付尾款
    public function pay_last_money(){

        $last_money = request()->post('last_money','');
        $order_id = request()->post('order_id','');
        if(empty($last_money) || empty($order_id)){
            $data['code'] = -1;
            $data['message'] = "金额不能为空";
            $data['data'] = '';
        }
        //查出用于付尾款的交易号
        $order = new VslOrderModel();
        $order_payment_mdl = new VslOrderPaymentModel();
        $out_trade_no = $order->getInfo(['order_id'=>$order_id],'out_trade_no_presell')['out_trade_no_presell'];
        $pay = new UnifyPay();
        $order_payment_info = $order_payment_mdl->getInfo(['out_trade_no'=>$out_trade_no]);
        if (!$order_payment_info) {
            $pay->createPayment(0, $out_trade_no, '尾款支付', '商品尾款支付', $last_money, 1, $order_id);
        }
        $data['code'] = 0;
        $data['message'] = "支付单据创建成功";
        $data['data']['out_trade_no'] = $out_trade_no;
        return json($data);
    }

    /**
     * 获取支付相关信息
     */
    public function getPayValue()
    {
        $out_trade_no = request()->get('out_trade_no', '');
        if (empty($out_trade_no)) {
            $this->error("没有获取到支付信息");
        }
        $pay = new UnifyPay();
        $pay_config = $pay->getPayConfig();
        $this->assign("pay_config", $pay_config);
        //渠道商订单 前缀QD
        if( strstr($out_trade_no, 'QD') ){
            $pay_value = $pay->getChannelPayInfo($out_trade_no);
            $order_status = $this->getChannelOrderStatusByOutTradeNo($out_trade_no);
            //获取渠道商设置时间
            $addons_config_mdl = new AddonsConfigModel();
            $channel_setting_value = $addons_config_mdl->getInfo(['addons'=>'channel','website_id'=>$this->website_id], 'value')['value'];
            $channel_setting_arr = json_decode($channel_setting_value,true);
            $this->shop_config['order_buy_close_time'] = $channel_setting_arr['channel_order_close_time'];
        }else{
            $pay_value = $pay->getPayInfo($out_trade_no);
            $order_status = $this->getOrderStatusByOutTradeNo($out_trade_no);
        }
        if (empty($pay_value)) {
            $this->error("订单主体信息已发生变动!", __URL(__URL__ . "/member/index"));
        }
        // 订单关闭状态下是不能继续支付的
        if ($order_status == 5) {
            $this->error("订单已关闭");
        }
        $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 > ($zero2 + ($this->shop_config['order_buy_close_time'] * 60))) {
            $this->error("订单已关闭");
        } else {
            $this->assign('pay_value', $pay_value);
            if (request()->isMobile()) {
                return view($this->style . 'Pay/getPayValue'); // 手机端
            } else {
                return view($this->style . 'Pay/pcOptionPaymentMethod'); // PC端
            }
        }
    }

    /**
     * 支付完成后回调界面
     *
     * status 1 成功
     *
     * @return \think\response\View
     */
    public function payCallback()
    {
        $out_trade_no = request()->get('out_trade_no', ''); // 流水号
        $msg = request()->get('msg', ''); // 测试，-1：在其他浏览器中打开，1：成功，2：失败
        $this->assign("status", $msg);
        $order_no = $this->getOrderNoByOutTradeNo($out_trade_no);
        $this->assign("order_no", $order_no);
        if (request()->isMobile()) {
            return view($this->style . "Pay/payCallback");
        } else {
            return view($this->style . "Pay/payCallbackPc");
        }
    }

    //判断订单是否为尾款，是则更改状态
    public function check_is_last_money($out_trade_no){
        $pay = new UnifyPay();
        $order_info = $pay->get_order_info($out_trade_no);
        if($order_info['pay_status']==2&&$order_info['money_type']==1){
            $order = new VslOrderModel();
            $order->save(['money_type'=>2],['order_id'=>$order['order_id']]);
        }
    }
	/**
	 * GlobePay异步回调（只有异步回调对订单进行处理）
	 */
	public function gpayUrlBack()
	{
		$response = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
		if(empty($response)){
			$response = json_decode(file_get_contents('php://input'), true);
		}
		if (!empty($response)) {
			$out_trade_no=$response['partner_order_id'];
			$pay = new UnifyPay();
			$gbpay = new globalpay();
			if(strstr($out_trade_no, 'DH')){
				$key = 'integral_pay_' . $out_trade_no;
				$redis = $this->connectRedis();
				$integral_order = $redis->get($key);
				$integral_order_arr = json_decode($integral_order, true);
				$order_from['order_from'] = $integral_order_arr['type']; //支付来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
			}else if(strstr($out_trade_no, 'MD')){
				$key = 'store_pay_' . $out_trade_no;
				$redis = $this->connectRedis();
				$store_order = $redis->get($key);
				$store_order_arr = json_decode($store_order, true);
				$order_from = [];
				$order_from['order_from'] = $store_order_arr['order_from']; //支付来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
			}else if(strstr($out_trade_no, 'eos')){
				$order_eos = new VslEosOrderPayMentModel();
				$order_from = $order_eos->getInfo(['out_trade_no'=>"{$out_trade_no}",'type'=>1],'pay_from,website_id');
			}elseif(strstr($out_trade_no, 'QD')){
				$order = new VslChannelOrderPaymentModel();
				$order_from = $order->getInfo(['out_trade_no'=>"{$out_trade_no}",'type'=>1],'pay_from,website_id');
			} else{
				$order = new VslOrderPaymentModel();
				$order_from = $order->getInfo(['out_trade_no'=>$out_trade_no],'pay_from, website_id');
			}
			// 支付来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
			if($order_from){
				if($order_from['pay_from'] == 6 ) { //by sgw
					$check_sign = $gbpay->checkSignMp($response, $response['sign'],$order_from['website_id']);
				}else{
					$check_sign = $gbpay->checkSign($response, $response['sign'],$order_from['website_id']);
				}
			} else {
				$order_recharge = new VslMemberRechargeModel();
				$website_id = $order_recharge->getInfo(['out_trade_no'=>$out_trade_no],'website_id')['website_id'];
				$check_sign = $gbpay->checkSignMp($response, $response['sign'],$website_id);
			}
			
			if ($response['pay_time'] && $check_sign) {
				if(strstr($out_trade_no, 'eos')){
					$block = new Block();
					$block->eosPayBack("{$out_trade_no}");
				}else{
					$pay->onlinePay("{$out_trade_no}", 20, '');
				}
			}
		}

	}
    /**
     * 微信支付异步回调（只有异步回调对订单进行处理）
     */
    public function wchatUrlBack()
    {
            $postStr = file_get_contents('php://input');
            
            if (! empty($postStr)) {
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $pay = new UnifyPay();
                if(strstr($postObj->out_trade_no, 'DH')){
                    $out_trade_no = $postObj->out_trade_no;
                    $key = 'integral_pay_' . $out_trade_no;
                    $redis = $this->connectRedis();
                    $integral_order = $redis->get($key);
                    $integral_order_arr = json_decode($integral_order, true);
                    $order_from['order_from'] = $integral_order_arr['type']; //支付来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
                }else if(strstr($postObj->out_trade_no, 'MD')){
                    $out_trade_no = $postObj->out_trade_no;
                    $key = 'store_pay_' . $out_trade_no;
                    $redis = $this->connectRedis();
                    $store_order = $redis->get($key);
                    $store_order_arr = json_decode($store_order, true);
                    $order_from = [];
                    $order_from['order_from'] = $store_order_arr['order_from']; //支付来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
                }else if(strstr($postObj->out_trade_no, 'eos')){
                    $order_eos = new VslEosOrderPayMentModel();
                    $order_from = $order_eos->getInfo(['out_trade_no'=>"{$postObj->out_trade_no}",'type'=>1],'pay_from,website_id');
                }elseif(strstr($postObj->out_trade_no, 'QD')){
                    $order = new VslChannelOrderPaymentModel();
                    $order_from = $order->getInfo(['out_trade_no'=>"{$postObj->out_trade_no}",'type'=>1],'pay_from,website_id');
                } else{
                    $order = new VslOrderPaymentModel();
                    $order_from = $order->getInfo(['out_trade_no'=>$postObj->out_trade_no],'pay_from, website_id');
                }
                // 支付来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
                if($order_from){
                    if($order_from['pay_from']==4 || $order_from['pay_from']==5){
                        $check_sign = $pay->checkSignApp($postObj, $postObj->sign,$order_from['website_id']);
                    } elseif($order_from['pay_from'] == 6) { //by sgw
                        $check_sign = $pay->checkSignMp($postObj, $postObj->sign,$order_from['website_id']);
                    } else{
                        $check_sign = $pay->checkSign($postObj, $postObj->sign,$order_from['website_id']);
                    }
                } else{
                    $order_recharge = new VslMemberRechargeModel();
                    $website_id = $order_recharge->getInfo(['out_trade_no'=>$postObj->out_trade_no],'website_id')['website_id'];
                    $check_sign = $pay->checkSign($postObj, $postObj->sign,$website_id);
                }
                if ($postObj->result_code == 'SUCCESS' && $check_sign == 1) {
                    if(strstr($postObj->out_trade_no, 'eos')){
                        $block = new Block();
                        $block->eosPayBack("{$postObj->out_trade_no}");
                    }else{
                        $pay->onlinePay("{$postObj->out_trade_no}", 1, '');
                    }
                }
            }

    }

    /**
     * 支付宝支付异步回调
     */
    public function aliUrlBack()
    {
        $pay = new UnifyPay();
        $order = new VslOrderModel();
        $out_trade_no = request()->post('out_trade_no', '');
        if(strstr($out_trade_no, 'QD')){
            $channel_order = new VslChannelOrderModel();
            $website_id = $channel_order->getInfo(['out_trade_no'=>$out_trade_no],'website_id')['website_id'];
        }else{
            $website_id = $order->getInfo(['out_trade_no'=>$out_trade_no],'website_id')['website_id'];
        }
        if(!$website_id){
            $order_recharge = new VslMemberRechargeModel();
            $website_id = $order_recharge->getInfo(['out_trade_no'=>$out_trade_no],'website_id')['website_id'];
        }
        $verify_result = $pay->alipayNotify($_POST,$website_id);
        if ($verify_result) { // 验证成功
            $out_trade_no = request()->post('out_trade_no', '');
            // 支付宝交易号
            $trade_no = request()->post('trade_no', '');
            // 交易状态
            $trade_status = request()->post('trade_status', '');
            if ($trade_status == 'TRADE_SUCCESS' || $trade_status == 'TRADE_FINISHED') {
                if(strstr($out_trade_no, 'eos')){
                    $block = new Block();
                    $block->eosPayBack("{$out_trade_no}");
                }else{
                    $pay->onlinePay($out_trade_no, 2, $trade_no);
                }
            }
            echo "success";
        } else {
            // 验证失败
            echo "fail";
        }
    }

    /**
     * 根据流水号查询订单编号，
     *
     * @param unknown $out_trade_no            
     * @return string
     */
    public function getOrderNoByOutTradeNo($out_trade_no)
    {
        $order_no = "";
        $order = new Order();
        $list = $order->getOrderNoByOutTradeNo($out_trade_no);
        if (! empty($list)) {
            foreach ($list as $v) {
                $order_no .= $v['order_no'];
            }
        }
        return $order_no;
    }

    /**
     * 根据外部交易号查询订单状态，订单关闭状态下是不能继续支付的
     *
     * @param unknown $out_trade_no            
     * @return number
     */
    public function getOrderStatusByOutTradeNo($out_trade_no)
    {
        $order = new Order();
        $order_status = $order->getOrderStatusByOutTradeNo($out_trade_no);
        if (! empty($order_status)) {
            return $order_status['order_status'];
        }
        return 0;
    }
    /**
     * 根据外部交易号查询渠道商订单状态，订单关闭状态下是不能继续支付的
     *
     * @param unknown $out_trade_no
     * @return number
     */
    public function getChannelOrderStatusByOutTradeNo($out_trade_no)
    {
        if($this->is_channel){
            $channel_order = new Channel();
            $order_status = $channel_order->getOrderStatusByOutTradeNo($out_trade_no);
            if (! empty($order_status)) {
                return $order_status['order_status'];
            }
        }
        return 0;
    }
    /**
     * 通联支付回调
     *
     */
    public function tlUrlBack(){
        $pay = new UnifyPay();
        $order = new VslOrderModel();
        $out_trade_no = $_POST['outtrxid'];
        if(strstr($out_trade_no, 'QD')){
            $channel_order = new VslChannelOrderModel();
            $website_id = $channel_order->getInfo(['out_trade_no'=>$out_trade_no],'website_id')['website_id'];
        }else{
            $website_id = $order->getInfo(['out_trade_no'=>$out_trade_no],'website_id')['website_id'];
        }
        if(!$website_id){
            // $order_recharge = new VslMemberRechargeModel();
            $website_id = $order->getInfo(['out_trade_no_presell'=>$out_trade_no],'website_id')['website_id'];
        }
        $verify_result = $pay->tlpayNotifys($_POST,$website_id);
        if ($verify_result) { // 验证成功
            // 交易状态
            if ($_POST['trxstatus'] == '0000') {
                if(strstr($out_trade_no, 'eos')){
                    $block = new Block();
                    $block->eosPayBack("{$out_trade_no}");
                }else{
                    $trade_no =$_POST['trxid'];
                    $pay->onlinePay($out_trade_no, 3, $trade_no);
                }
            }
            echo "success";
        } else {
            // 验证失败
            echo "fail";
        }
    }
    /**
     * eth和eos提现回调
     *
     */
    public function withdrawUrlBack(){
        $appId = $_POST['appId'];
        $website = new WebSiteModel();
        $public_key = $website->getInfo(['website_id'=>$appId])['public_key'];
        if($_POST['sign']){
            $result = PublicDecrypt($_POST['sign'],$public_key);
            if($result){
                $block = new Block();
                $result  = explodeString($result);
                $trade_no = $result['outTradeNo'];
                if($_POST['status']==1){
                    $block->withdrawNotify($trade_no,1,$result['msg']);
                }else if($_POST['status']==2){
                    $block->withdrawNotify($trade_no,2,$result['msg']);
                }
            }
        }
    }
    /**
     * eth和eos退款回调
     *
     */
    public function refundUrlBack(){
        $appId = $_POST['appId'];
        $website = new WebSiteModel();
        $public_key = $website->getInfo(['website_id'=>$appId])['public_key'];
        if($_POST['sign']){
            $result = PublicDecrypt($_POST['sign'],$public_key);
            if($result){
                $block = new Block();
                $outTradeNo = explodeString($result)['outTradeNo'];
                if($_POST['status']==1){
                    $block->refundNotify($outTradeNo,1);
                }else{
                    $block->refundNotify($outTradeNo,2);
                }
            }
        }
    }
    /**
     * eth和eos兑换成积分支付回调
     *
     */
    public function payUrlBacks(){
        $appId = $_POST['appId'];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$appId]);
        $key = $website_info['common_key'];
        $public_key = $website_info['public_key'];
        $pay = new UnifyPay();
        $result = $pay->blockChainNotify($_POST,$public_key,$key,$_POST['sign']);
        if($result){
            $block = new Block();
            $trade_no = $_POST['outTradeNO'];
            if($_POST['status']==1){
                $block->payNotifys($trade_no,1,$_POST['msg']);
            }else if($_POST['status']==2){
                $block->payNotifys($trade_no,2,$_POST['msg']);
            }
        }
    }

    /**
     * eth和eos订单支付回调
     *
     */
    public function ethPayUrlBack(){
        $appId = $_POST['appId'];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$appId]);
        $key = $website_info['common_key'];
        $public_key = $website_info['public_key'];
        $pay = new UnifyPay();
        $result = $pay->blockChainNotify($_POST,$public_key,$key,$_POST['sign']);
        if($result){
            $out_trade_no = $_POST['outTradeNO'];
            $block = new Block();
            if($_POST['status']==1){
                $pay->onlinePay($out_trade_no, 16, '');
                $block->payNotify($out_trade_no,1);
            }else if($_POST['status']==2){
                $order = new VslOrderModel();
                $order->save(['order_status'=>5],['out_trade_no'=>$out_trade_no]);
                $block->payNotify($out_trade_no,2,$_POST['msg']);
            }
        }
    }
    public function eosPayUrlBack(){
        $appId = $_POST['appId'];
        $website = new WebSiteModel();
        $website_info = $website->getInfo(['website_id'=>$appId]);
        $key = $website_info['common_key'];
        $public_key = $website_info['public_key'];
        $pay = new UnifyPay();
        $result = $pay->blockChainNotify($_POST,$public_key,$key,$_POST['sign']);
        if($result){
            $out_trade_no = $_POST['outTradeNO'];
            $block = new Block();
            if($_POST['status']==1){
                $pay->onlinePay($out_trade_no, 17, '');
                $block->payNotify($out_trade_no,1);
            }else if($_POST['status']==2){
                $block->payNotify($out_trade_no,2,$_POST['msg']);
            }
        }
    }

    /**
     * eth上链回调
     *
     */
    public function ethChainUrlBack(){
        $appId = $_POST['appId'];
        $website = new WebSiteModel();
        $public_key = $website->getInfo(['website_id'=>$appId])['public_key'];
        if($_POST['sign']){
            $result = PublicDecrypt($_POST['sign'],$public_key);
            if($result){
                $anti = new AntiCounterfeiting();
                $outTradeNo = explodeString($result)['ipfsHash'];
                $chain_code = explodeString($result)['sourceCode'];
                if($_POST['status']==1){
                    $anti->chainNotify($outTradeNo,$chain_code,1);
                }else{
                    $anti->chainNotify($outTradeNo,$chain_code,2);
                }
            }
        }
    }
}