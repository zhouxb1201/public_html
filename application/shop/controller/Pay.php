<?php
namespace app\shop\controller;
use addons\blockchain\service\Block;
use data\model\VslBankModel;
use data\model\VslOrderPaymentModel;
use think\Db;
use data\service\Config;
use data\service\Member as MemberService;
use data\service\Order;
use data\service\UnifyPay;
use data\service\WebSite;
use think\Controller;
use think\Log;
use data\service\BaseService;
use \think\Session as Session;
use \think\Request as Request;
use data\extend\custom\Common;
use data\service\Pay\GlobePay as globalpay;
\think\Loader::addNamespace('data', 'data/');

/**
 * 支付控制器
 *
 * @author  www.vslai.com
 *        
 */
class Pay extends Controller
{

    public $style;

    public $shop_config;
    
    protected $website_id;

    public $realm_ip;
    public $http = '';
    public function __construct()
    {
        $this->website_id = checkUrl();
        parent::__construct();
        $is_ssl = Request::instance()->isSsl();
        $this->http = "http://";
        if($is_ssl){
            $this->http = 'https://';
        }
        $base = new BaseService();
        $model = $base->getRequestModel();
        $base_file = Request::instance()->baseFile();
        $alipay = strpos($base_file, 'alipay.php');
        $weixinpay = strpos($base_file, 'weixinpay.php');
        
        if($alipay){
            $order = new Order();
            $orderDetail = $order->getOrderPaymentByOutTradeNo(request()->post('out_trade_no'));
            if(!$orderDetail){
                exit('fail');
            }
            $this->website_id = $orderDetail['website_id'];
            Session::set($model.'website_id', $this->website_id);
        }elseif($weixinpay){
            $postStr = file_get_contents('php://input');
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            file_put_contents('./application/test', json_encode($postStr),FILE_APPEND);
            $order = new Order();
            $orderDetail = $order->getOrderPaymentByOutTradeNo($postObj->out_trade_no);
            if(!$orderDetail){
                exit('fail');
            }
            $this->website_id = $orderDetail['website_id'];
        }elseif(Session::get($model.'website_id')){
            $this->website_id = Session::get($model.'website_id');
        }else{
            $this->error("参数错误，请检查域名");
        }
        
        if (!request()->isMobile()) {
            $com = new Common(0, $this->website_id);
            $dir_common = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/common';
            $bottom = $com->get_html_file($dir_common . '/bottom_html.php');
            $this->assign('bottom', $bottom);
        }
        $this->web_site = new WebSite();
        $config = new Config();
        $web_info = $this->web_site->getWebSiteInfo();
        if($web_info['realm_ip']){
            $this->realm_ip = $this->http.$web_info['realm_ip'];
        }else{
            $this->realm_ip = $this->http.$web_info['realm_two_ip'].'.'.top_domain($_SERVER['HTTP_HOST']);
        }
        $this->assign("web_info", $web_info);
        $this->assign("shopname", $web_info['title']);
        $this->assign("title", $web_info['title']);
        $this->style = "shop/new/";
        $this->assign("style", $this->style);
        // 购物设置
        $this->shop_config = $config->getShopConfig(0);
        $this->assign("shop_config", $this->shop_config);
        // 获取会员昵称
        $member = new MemberService();
        $member_info = $member->getMemberDetail();
        $unpaid_goback = isset($_SESSION['unpaid_goback']) ? $_SESSION['unpaid_goback'] : '';
        $this->assign("unpaid_goback", $unpaid_goback); // 返回到订单
        $this->assign('member_info', $member_info);
        $this->assign('website_id', $this->website_id);
    }

    /**
     * 获取支付相关信息
     */
    public function getPayValue()
    {
        $out_trade_no = request()->get('out_trade_no', '');
        if (empty($out_trade_no)) {
            $this->error("没有获取到支付信息");
            die;
        }
        
        $pay = new UnifyPay();
        $pay_config = $pay->getPayConfig();
        $this->assign("pay_config", $pay_config);
        $pay_value = $pay->getPayInfo($out_trade_no);
        $order_status = $this->getOrderStatusByOutTradeNo($out_trade_no);
        if (empty($pay_value)) {
            $this->error("订单主体信息已发生变动!", __URL(__URL__ . "/member/index"));
            die;
        }
        if ($pay_value['pay_status'] == 1) {
            // 订单已经支付
            $this->redirect(__URL(__URL__ . "/member/index"));
        }
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
        // 订单关闭状态下是不能继续支付的
        if ($order_status == 5) {
            $this->error("订单已关闭");
            die;
        }
        /*$zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 >= ($zero2 + ($this->shop_config['order_buy_close_time'] * 60))) {
            $orderService = new Order();
            $orderService->orderCloseByOutTradeNo($out_trade_no);
            $this->error("订单已关闭");
            die;
        } else {
            $this->assign('pay_value', $pay_value);
            return view($this->style . 'Member/checkStand');
        }*/
        $this->assign('pay_value', $pay_value);
        $member = new MemberService();
        $account_list = $member->getMemberBankAccount(0,['type'=>1]);
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
        $this->assign('account_list', $account_list);
        $bank = new VslBankModel();
        $bank_list = $bank->getQuery(['sort'=>['neq','']],'*','sort asc');
        $this->assign('bank_list',$bank_list);
        return view($this->style . 'Member/checkStand');
    }
    /*
     * 连接redis
     * **/
    public function connectRedis(){
        $host = config('redis.host');
        $pwd = config('redis.pass');
        $port = 6379;
        $redis = new \Redis();
//        $is_redis = objToArr($redis);
        $ip = $_SERVER['SERVER_ADDR'];//暂时用ip判断是本地还是正式环境
        if($ip && $ip != '127.0.0.1' && $ip == '172.16.243.83'){
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
        return view($this->style . "Member/carPayState");
        
    }
    /**
     * 订单微信支付(充值)
     */
    public function wchatRechargePay()
    {
        $out_trade_no = request()->get('no', '');
        if (! is_numeric($out_trade_no)) {
            $this->error("没有获取到支付信息");
        }
        $red_url = $this->realm_ip. "/wapapi/pay/wchatUrlBack";
        $pay = new UnifyPay();
        $res = $pay->wchatPay($out_trade_no, 'NATIVE', $red_url);
        if ($res["return_code"] == "SUCCESS") {
            if(empty($res['code_url'])){
                $code_url = "生成支付二维码失败!";
            }else{
                $code_url = $res['code_url'];
            }
            if(!empty($res["err_code"]) && $res["err_code"] == "ORDERPAID" && $res["err_code_des"] == "该订单已支付"){
                $this->redirect(__URL(__URL__ . "/member/index"));
            }
        }else{
            $code_url = "生成支付二维码失败!";
        }
        $path = getQRcode($code_url, "upload/".$this->website_id."/qrcode/pay", $out_trade_no);
        $this->assign("path",$path);
        $pay_value = $pay->getPayInfo($out_trade_no);
        $this->assign('pay_value', $pay_value);
        $this->assign('out_trade_no', $out_trade_no);
        return view($this->style . "Member/weChatRechargePay");
    }
	/**
     * 订单GLOBEPAY支付
     */
    public function globePay()
    {
        $out_trade_no = request()->get('no', '');
        if (!is_numeric($out_trade_no)) {
            $this->error("没有获取到支付信息");
        }
        $red_url = $this->realm_ip . "/wapapi/pay/gpayUrlBack";
        $pay = new UnifyPay();
		$gbpay = new globalpay();
		$config_service = new Config();
		$payconfig = $config_service->getGpayConfig($this->website_id);
		$data = $pay->globePayMir($out_trade_no);
		if(empty($data)){
			$this->error("没有获取到支付信息");
		}
		if($payconfig['value']['currency'] == 'CNY'){
			$orderprice=$data['pay_money']*100;
		}else{
			$orderprice=(int)$data['pay_money'];
		}
		$param=array('description'=>$data['pay_body'],'price'=>$orderprice,'currency'=>$payconfig['value']['currency'],'channel'=>'Wechat','notify_url'=>$red_url,'operator'=>$this->uid);
		$res=$gbpay->getpayurl($payconfig['value'],$out_trade_no,$param,3,$this->realm_ip);
        if($res['result_code']=='SUCCESS'){
		    if(empty($res['qrcode_img'])){
                $this->error("生成支付二维码失败!");
            }else{
                $qrcode_img = $res['qrcode_img'];
            }
            if(!empty($res["return_code"]) && $res["return_code"] == "ORDER_PAID"){
                $this->redirect(__URL(__URL__ . "/member/index"));
            }
        }else{
			if($res['return_code']=='ORDER_CLOSED'){
				$this->error("globepay订单支付超时已关闭，请重新下单!");
			}else{
				$this->error("生成支付二维码失败!");
			}           
        }
		$this->assign("path", $qrcode_img);
        $pay_value = $pay->getPayInfo($out_trade_no);
        $this->assign('pay_value', $pay_value);
        $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 > ($zero2 + ($this->shop_config['order_buy_close_time'] * 60))) {
            $this->error("订单已关闭");
        } 
        return view($this->style . "Member/globePay");
    }
	/**
     * GLOBEPAY二维码支付状态
     */
    public function globeQrcodePay()
    {
        if (request()->isAjax()) {
            $out_trade_no = request()->post("out_trade_no", "");
            $config_service = new Config();
			$gbpay = new globalpay();
			$payconfig = $config_service->getGpayConfig($this->website_id);
            $payResult = $gbpay->getOrderStatus($payconfig['value'],$out_trade_no);
            if ($payResult['result_code']=='PAY_SUCCESS') {
                return $retval = array(
                    "code" => 1,
                    "message" => ''
                );
            }
        }
    }
	/**
     * GLOBEPAY同步回调（不对订单处理）
     */
    public function globePayResult()
    {
        $out_trade_no = request()->get('out_trade_no', '');
        $msg = request()->get('msg', '');
        $this->assign("status", $msg);
        $order_no = $this->getOrderNoByOutTradeNo($out_trade_no);
        $this->assign("order_no", $order_no);
        return view($this->style . "Member/carPayState");
    }
    /**
     * 订单微信支付
     */
    public function wchatPay()
    {
        $out_trade_no = request()->get('no', '');
        if (! is_numeric($out_trade_no)) {
            $this->error("没有获取到支付信息");
        }
        $red_url = $this->realm_ip. "/wapapi/pay/wchatUrlBack";
        $pay = new UnifyPay();
        $res = $pay->wchatPay($out_trade_no, 'NATIVE', $red_url);
        if ($res["return_code"] == "SUCCESS") {
            if(empty($res['code_url'])){
                $code_url = "生成支付二维码失败!";
            }else{
                $code_url = $res['code_url'];
            }
            if(!empty($res["err_code"]) && $res["err_code"] == "ORDERPAID" && $res["err_code_des"] == "该订单已支付"){
                $this->redirect(__URL(__URL__ . "/member/index"));
            }
        }else{
            $code_url = "生成支付二维码失败!";
        }
        $path = getQRcode($code_url, "upload/".$this->website_id."/qrcode/pay", $out_trade_no);
        $this->assign("path", $path);
        $pay_value = $pay->getPayInfo($out_trade_no);
        $this->assign('pay_value', $pay_value);
        $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 > ($zero2 + ($this->shop_config['order_buy_close_time'] * 60))) {
            $this->error("订单已关闭");
        } 
        return view($this->style . "Member/weChatPay");
    }

    /**
     * 微信支付同步回调（不对订单处理）
     */
    public function wchatPayResult()
    {
        $out_trade_no = request()->get('out_trade_no', '');
        $msg = request()->get('msg', '');
        $this->assign("status", $msg);
        $order_no = $this->getOrderNoByOutTradeNo($out_trade_no);
        $this->assign("order_no", $order_no);
        return view($this->style . "Member/carPayState");
    }

    /**
     * 微信二维码支付状态
     */
    public function wchatQrcodePay()
    {
        if (request()->isAjax()) {
            $out_trade_no = request()->post("out_trade_no", "");
            $pay = new UnifyPay();
            $payResult = $pay->getPayInfo($out_trade_no);
            if ($payResult['pay_status']) {
                return $retval = array(
                    "code" => 1,
                    "message" => ''
                );
            }
        }
    }
    /**
     * 支付宝支付(充值)
     */
    public function aliRechargePay()
    {
        $out_trade_no = request()->get('no', '');
        if (! is_numeric($out_trade_no)) {
            $this->error("没有获取到支付信息");
        }
        $notify_url = $this->realm_ip . "/wapapi/pay/aliUrlBack";
        $return_url = $this->realm_ip. "/pay/aliPayReturn";
        $pay = new UnifyPay();
        $res = $pay->aliPayNew($out_trade_no, $notify_url, $return_url);
        echo "<meta charset='UTF-8'><script>window.location.href='" . $res . "'</script>";
    }
    /**
     * 支付宝支付
     */
    public function aliPay()
    {
        $out_trade_no = request()->get('no', '');
        if (! is_numeric($out_trade_no)) {
            $this->error("没有获取到支付信息");
        }
        $notify_url = $this->realm_ip . "/wapapi/pay/aliUrlBack";
        $return_url = $this->realm_ip. "/pay/aliPayReturn";
        $pay = new UnifyPay();
        $res = $pay->aliPayNew($out_trade_no, $notify_url, $return_url);
        echo "<meta charset='UTF-8'><script>window.location.href='" . $res . "'</script>";
    }

    /**
     * 支付宝支付同步会调（页面）（不对订单进行处理）
     */
    public function aliPayReturn()
    {
        $out_trade_no = request()->get('out_trade_no', '');
        
        $order_no = $this->getOrderNoByOutTradeNo($out_trade_no);
        
        $this->assign("order_no", $order_no);
        $pay = new UnifyPay();
        $verify_result = $pay->aliPayNewResult($out_trade_no);
        $result = json_decode(json_encode($verify_result),TRUE);
        if ($result['code']=='10000' && $result['trade_status']=='TRADE_SUCCESS') {
            $this->assign("orderNumber", $out_trade_no);
            $this->assign("msg", 1);
            $this->assign("status", 1);
        } else {
            $this->assign("orderNumber", $out_trade_no);
            $this->assign("msg", 0);
            $this->assign("status", 2);
        }
        return view($this->style . "Member/carPayState");
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
            return AjaxReturn($data);
        }
        $type = request()->post('type', '');
        if(empty($type)){
            $type = request()->get('type', 2);
        }
        $pay_ment = new VslOrderPaymentModel();
        $payment_info = $pay_ment->getInfo(['out_trade_no'=>$out_trade_no]);
        if($payment_info){
            $pay_ment->save(['pay_from'=>$type],['out_trade_no'=>$out_trade_no]);
        }
        $notify_url = $this->realm_ip . "/wapapi/pay/tlUrlBack";
        $pay = new UnifyPay();
        $res = $pay->payApplyAgree($id,$out_trade_no,$notify_url,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            if($res['trxstatus']==1999){
                $data['data'] =$res['thpinfo'];
                $data['code'] = 1;
                return AjaxReturn($data);
            }elseif($res['trxstatus']==0000){
                $data['code'] = 2;
                $data['message'] = '交易已完成';
                return AjaxReturn($data);
            }elseif($res['trxstatus']==2000 || $res['trxstatus']==2008){
                $data['code'] = 3;
                $data['message'] = '交易处理中';
                return AjaxReturn($data);
            }else{
                $data['code'] = -1;
                $data['message'] = $res['errmsg'];
                return AjaxReturn($data);
            }
        }else{
            $data['code'] = -1;
            $data['message'] = '支付申请失败';
            return AjaxReturn($data);
        }
    }
    /**
     * 银行卡支付重新获取支付短信
     */
    public function paySmsAgree()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        $pay = new UnifyPay();
        $res = $pay->paySmsAgree($out_trade_no,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            $data['code'] = 1;
            $data['message'] = '发送成功';
            return AjaxReturn($data);
        }else{
            $data['code'] = -1;
            $data['message'] = '发送失败';
            return AjaxReturn($data);
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
        $thpinfo = request()->post('thpinfo', '');
        $pay = new UnifyPay();
        $res = $pay->payAgreeConfirm($id,$smscode,$thpinfo,$out_trade_no,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            if($res['trxstatus']==1999){
                $data['data'] =$res['thpinfo'];
                $data['code'] = 2;
                return AjaxReturn($data);
            }elseif($res['trxstatus']==0000){
                $data['code'] = 1;
                $data['message'] = '交易成功';
//                $verify_result = $pay->tlpayNotify($res,$this->website_id);
//                if ($verify_result) { // 验证成功
//                    // 通联交易号
//                    $trade_no =$res['trxid'];
//                    if(strstr($out_trade_no, 'eos')){
//                        $block = new Block();
//                        $block->eosPayBack($out_trade_no);
//                    }else{
//                        $pay->onlinePay($out_trade_no, 3, $trade_no);
//                    }
//                }
                return AjaxReturn($data);
            }elseif($res['trxstatus']==2000 || $res['trxstatus']==2008){
                $data['code'] = 3;
                $data['message'] = '交易处理中';
                return AjaxReturn($data);
            }else{
                $data['code'] = -1;
                $data['message'] = $res['errmsg'];
                return AjaxReturn($data);
            }
        }else{
            $data['code'] = -1;
            $data['message'] = '支付申请失败';
            return AjaxReturn($data);
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
}