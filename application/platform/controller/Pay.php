<?php
namespace app\platform\controller;
use addons\blockchain\model\VslEosOrderPayMentModel;
use data\service\Member as MemberService;
use data\service\Order;
use data\service\UnifyPay;
use think\Log;
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

    public $realm_ip;
    protected $http;
    public function __construct()
    {
        parent::__construct();
        // 获取会员昵称
        $member = new MemberService();
        $member_info = $member->getMemberDetail();
        $this->assign('member_info', $member_info);
    }

    /**
     * 获取支付相关信息
     */
    public function getPayValue()
    {
        $order_id = request()->get("order_id");
        $order = new Order();
        $order_info = $order->getIncrementOrderInfo($order_id);
        $this->assign("order_info", $order_info);
        $out_trade_no= $order_info['out_trade_no'];
        if (empty($out_trade_no)) {
            $this->error("没有获取到支付信息", __URL(__URL__ . "/platform/addonslist/incrementOrderList"));
            die;
        }
        $pay = new UnifyPay();
        $pay_configs = $pay->getPayConfigs();
        $this->assign("pay_configs", $pay_configs);
        $pay_value = $pay->getIncrementPayInfo($out_trade_no);
        $order_status = $this->getOrderStatusByOutTradeNo($out_trade_no);
        if (empty($pay_value)) {
            $this->error("订单主体信息已发生变动!", __URL(__URL__ . "/platform/addonslist/incrementOrderList"));
            die;
        }
        if ($pay_value['pay_status'] == 1) {
            // 订单已经支付
            $this->redirect(__URL(__URL__ . "/platform/addonslist/addonslist"));
        }
        // 订单关闭状态下是不能继续支付的
        if ($order_status == 2) {
            $this->error("订单已关闭", __URL(__URL__ . "/platform/addonslist/incrementOrderList"));
            die;
        }
        $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 >= ($zero2 + (30 * 60))) {
            $orderService = new Order();
            $orderService->incrementOrderCloseByOutTradeNo($out_trade_no);
            $this->error("订单已关闭", __URL(__URL__ . "/platform/addonslist/incrementOrderList"));
            die;
        } else {
            $this->assign('order_info', $pay_value);
            $pay = new UnifyPay();
            $pay_config = $pay->getPayConfigs();
            $this->assign("pay_config", $pay_config);
            return view($this->style . 'Addons/payNow');
        }
    }

    /**
     * 订单微信支付(生成二维码)
     */
    public function wchatPay()
    {
        $out_trade_no = request()->post('no', '');
        if (!isset($out_trade_no)) {
            return AjaxReturn(-1);
        }
        $pay = new UnifyPay();
        if(strstr($out_trade_no, 'eos')){

        }else{
            $pay_value = $pay->getIncrementPayInfo($out_trade_no);
            $this->assign('pay_value', $pay_value);
            $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
            $zero2 = $pay_value['create_time'];
            if ($zero1 > ($zero2 + (30 * 60))) {
                return AjaxReturn(-1);
            }
        }
        $red_url = $this->http.$_SERVER['HTTP_HOST']. "/platform/pay/wchatUrlBack";
        $res = $pay->wchatPays($out_trade_no, 'NATIVE', $red_url);
        if ($res["return_code"] == "SUCCESS") {
            if(empty($res['code_url'])){
                return AjaxReturn(-3);
            }else{
                $code_url = $res['code_url'];
                $path = getQRcode($code_url, "upload/0/qrcode/pay", $out_trade_no.'wx');
                $real_path = __ROOT__.$path;
                return $retval = array(
                    "code" => 1,
                    "message" => $real_path
                );
            }
        }else{
            return AjaxReturn(-1);
        }

    }
    /**
     * 微信支付异步回调（只有异步回调对订单进行处理）
     */
    public function wchatUrlBack()
    {
        try{
            $postStr = file_get_contents('php://input');
            if (! empty($postStr)) {
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $pay = new UnifyPay();
                $check_sign = $pay->checkSigns($postObj, $postObj->sign);
                if ($postObj->result_code == 'SUCCESS' && $check_sign == 1) {
                    if(strstr($postObj->out_trade_no, 'eos')){
                        $order_eos = new VslEosOrderPayMentModel();
                        $pay_ment = $order_eos->getInfo(['out_trade_no'=>"{$postObj->out_trade_no}"],'id')['id'];
                        if($pay_ment){
                            $order_eos->save(['pay_status'=>1],['out_trade_no'=>"{$postObj->out_trade_no}"]);
                        }
                    }else{
                        $pay->onlinePays($postObj->out_trade_no, 1, '');
                    }
                }
            }
        }catch(\Exception $e){
            debugLog($e->getMessage());
        }

    }
    /**
     * 二维码支付状态
     */
    public function wchatQrcodePay()
    {
        if (request()->isAjax()) {
            $out_trade_no = request()->post("out_trade_no", "");
            $pay = new UnifyPay();
            $payResult = $pay->getIncrementPayInfo($out_trade_no);
            if ($payResult['pay_status'] > 0) {
                $system = new System();
                $system->deleteCache();

                return $retval = array(
                    "code" => 1,
                    "message" => ''
                );
            }
        }
    }

    /**
     * 支付宝支付（当面付生成二维码）
     */
    public function aliPay()
    {
        $out_trade_no = request()->post('no', '');
        if (!$out_trade_no) {
            return AjaxReturn(-1);
        }
        $notify_url = $this->http.$_SERVER['HTTP_HOST']."/alipays.php";
        $pay = new UnifyPay();
        $qrPayResult = $pay->aliPayNews($out_trade_no, $notify_url);
        if($qrPayResult==-1 || $qrPayResult==-2 || $qrPayResult==-3){
            return AjaxReturn(-1);
        }else{
            $path = getQRcode($qrPayResult, "upload/0/qrcode/pay", $out_trade_no.'al');
            $real_path = __ROOT__.$path;
            return $retval = array(
                "code" => 1,
                "message" => $real_path
            );
        }
    }

    /**
     * 支付宝支付异步回调
     */
    public function aliUrlBack()
    {
        Log::write("支付宝购买增值------------------------------------进入回调用");
        $pay = new UnifyPay();
        $verify_result = $pay->alipayNotifys($_POST);
        if ($verify_result) { // 验证成功
            $out_trade_no = request()->post('out_trade_no', '');
            // 支付宝交易号
            $trade_no = request()->post('trade_no', '');

            // 交易状态
            $trade_status = request()->post('trade_status', '');

            Log::write("支付宝购买增值------------------------------------交易状态：" . $trade_status);
            if ($trade_status == 'TRADE_FINISHED') {
                // 判断该笔订单是否在商户网站中已经做过处理
                // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                // 如果有做过处理，不执行商户的业务程序
                // 注意：
                // 退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                // 调试用，写文本函数记录程序运行情况是否正常
                // logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                if(strstr($out_trade_no, 'eos')){
                    $order_eos = new VslEosOrderPayMentModel();
                    $pay_ment = $order_eos->getInfo(['out_trade_no'=>"{$out_trade_no}",'type'=>2]);
                    if($pay_ment){
                        $retval = $order_eos->save(['pay_status'=>1],['out_trade_no'=>"{$out_trade_no}",'type'=>2]);
                    }
                    Log::write("支付宝购买eos内存------------------------------------retval：" . $retval);
                }else{
                    $retval = $pay->onlinePays($out_trade_no, 2, $trade_no);
                }
                Log::write("支付宝购买增值------------------------------------retval：" . $retval);
                // $res = $order->orderOnLinePay($out_trade_no, 2);
            } else
                if ($trade_status == 'TRADE_SUCCESS') {
                    // 判断该笔订单是否在商户网站中已经做过处理
                    // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    // 如果有做过处理，不执行商户的业务程序

                    // 注意：
                    // 付款完成后，支付宝系统发送该交易状态通知
                    if(strstr($out_trade_no, 'eos')){
                        $order_eos = new VslEosOrderPayMentModel();
                        $pay_ment = $order_eos->getInfo(['out_trade_no'=>"{$out_trade_no}",'type'=>2]);
                        if($pay_ment){
                            $retval = $order_eos->save(['pay_status'=>1],['out_trade_no'=>"{$out_trade_no}",'type'=>2]);
                        }
                        Log::write("支付宝购买eos内存------------------------------------retval：" . $retval);
                    }else{
                        $retval = $pay->onlinePays($out_trade_no, 2, $trade_no);
                    }
                    Log::write("支付宝购买增值------------------------------------retval：" . $retval);
                    // $res = $order->orderOnLinePay($out_trade_no, 2);
                    // 调试用，写文本函数记录程序运行情况是否正常
                    // logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                }

            // ——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success"; // 请不要修改或删除

            // $this->assign("status", 1);
            // $this->assign("out_trade_no", $out_trade_no);
            // return view($this->style . "Pay/payCallback");

            // ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            // 验证失败
            echo "fail";

            // $this->assign("status", 2);
            // $this->assign("out_trade_no", $out_trade_no);
            // return view($this->style . "Pay/payCallback");
            // 调试用，写文本函数记录程序运行情况是否正常
        } // logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
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
        $list = $order->getIntermentOrderNoByOutTradeNo($out_trade_no);
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
        $order_status = $order->getIntermentOrderStatusByOutTradeNo($out_trade_no);
        return $order_status;
    }
}