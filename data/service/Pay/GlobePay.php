<?php
/**
 * GlobePay.php
 *
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace data\service\Pay;

use data\extend\globepay\GlobePayApi;
use data\extend\globepay\GlobePayDataBase;
use data\extend\globepay\GlobePayException;
use data\model\VslOrderModel;
use think\Db;
use think\Log;
use data\service\config;
/**
 * 功能说明：GlobePay接口
 */
class GlobePay extends PayParam
{
	public function getparams($payconfig,$out_trade_no,$param)
    {
        $GlobePayApi = new GlobePayApi();
		$input = new GlobePayDataBase();
		$time=$GlobePayApi->getMillisecond();
		$nonce_str=$GlobePayApi->getNonceStr();
		$sign=$input->makeSign_new($payconfig,$time,$nonce_str);
		$url = "https://pay.globepay.co.jp/api/v1.0/gateway/partners/".$payconfig['partner_code']."/microapp_orders/".$out_trade_no.'?time='.$time.'&nonce_str='.$nonce_str.'&sign='.$sign;
		$response = $GlobePayApi->putJsonCurl($url, $param, 10);
		$result = json_decode($response, true);
        return $result;
    }
	public function getpayurl($payconfig,$out_trade_no,$param,$type,$realm_ip)
    {
        $GlobePayApi = new GlobePayApi();
		$input = new GlobePayDataBase();
		$time=$GlobePayApi->getMillisecond();
		$nonce_str=$GlobePayApi->getNonceStr();
		$sign=$input->makeSign_new($payconfig,$time,$nonce_str);
		if($type==1){
			$url = "https://pay.globepay.co.jp/api/v1.0/jsapi_gateway/partners/".$payconfig['partner_code']."/orders/".$out_trade_no.'?time='.$time.'&nonce_str='.$nonce_str.'&sign='.$sign;
		}
		if($type==2){
			$url = "https://pay.globepay.co.jp/api/v1.0/h5_payment/partners/".$payconfig['partner_code']."/orders/".$out_trade_no.'?time='.$time.'&nonce_str='.$nonce_str.'&sign='.$sign;
		}
		if($type==3){
			$url = "https://pay.globepay.co.jp/api/v1.0/gateway/partners/".$payconfig['partner_code']."/orders/".$out_trade_no.'?time='.$time.'&nonce_str='.$nonce_str.'&sign='.$sign;
		}
		$response = $GlobePayApi->putJsonCurl($url, $param, 10);
		$result = json_decode($response, true);
		if($result['result_code']=='SUCCESS'){
			$time=$GlobePayApi->getMillisecond();
			$nonce_str=$GlobePayApi->getNonceStr();
			$sign=$input->makeSign_new($payconfig,$time,$nonce_str);
			$result['pay_url']=$result['pay_url'].'?redirect='.urlencode($realm_ip . '/wap/pay/result?out_trade_no=' . $out_trade_no).'&time='.$time.'&nonce_str='.$nonce_str.'&sign='.$sign.'&directpay=true';
		}
        return $result;
    }
	public function checkSignMp($response, $sign,$website_id){
		$config = new config();
        $payconfig = $config->getGpayConfigMir($website_id)['value'];//小程序GlobePay配置
		$GlobePayApi = new GlobePayApi();
		$input = new GlobePayDataBase();
		$time=$response['time'];
		$nonce_str=$response['nonce_str'];
		$sign=$input->makeSign_new($payconfig,$time,$nonce_str);
		if($sign==$response['sign']){
			return true;
		}else{
			return false;
		}
	}
	public function checkSign($response, $sign,$website_id){
		$config = new config();
        $payconfig = $config->getGpayConfig($website_id)['value'];//GlobePay配置
		$GlobePayApi = new GlobePayApi();
		$input = new GlobePayDataBase();
		$time=$response['time'];
		$nonce_str=$response['nonce_str'];
		$sign=$input->makeSign_new($payconfig,$time,$nonce_str);
		if($sign==$response['sign']){
			return true;
		}else{
			return false;
		}
	}
	public function setGpRefund($refund_no, $out_trade_no, $refund_fee, $total_fee,$website_id)
    {
		$config = new config();
        $payconfig = $config->getGpayConfigMir($website_id)['value'];//小程序GlobePay配置
		if(empty($payconfig['partner_code'])){
			 $payconfig = $config->getGpayConfig($website_id)['value'];//GlobePay配置
		}
		$GlobePayApi = new GlobePayApi();
		$input = new GlobePayDataBase();
		$time=$GlobePayApi->getMillisecond();
		$nonce_str=$GlobePayApi->getNonceStr();
		$sign=$input->makeSign_new($payconfig,$time,$nonce_str);
		$url = "https://pay.globepay.co.jp/api/v1.0/gateway/partners/".$payconfig['partner_code']."/orders/".$out_trade_no."/refunds/".$refund_no.'?time='.$time.'&nonce_str='.$nonce_str.'&sign='.$sign;
		if($payconfig['currency'] == 'CNY'){
			$refund_fee=$refund_fee*100;
		}else{
			$refund_fee=(int)$refund_fee;
		}
		$response = $GlobePayApi->putJsonCurl($url, ['fee'=>$refund_fee], 10);
		$result = json_decode($response, true);
        return $result;
	}
	public function getOrderStatus($payconfig,$out_trade_no)
    {
		$config = new config();
		$GlobePayApi = new GlobePayApi();
		$input = new GlobePayDataBase();
		$time=$GlobePayApi->getMillisecond();
		$nonce_str=$GlobePayApi->getNonceStr();
		$sign=$input->makeSign_new($payconfig,$time,$nonce_str);
		$url = "https://pay.globepay.co.jp/api/v1.0/gateway/partners/".$payconfig['partner_code']."/orders/".$out_trade_no.'?time='.$time.'&nonce_str='.$nonce_str.'&sign='.$sign;
		$response = $GlobePayApi->getJsonCurl($url, 10);
		$result = json_decode($response, true);
        return $result;
	}
}