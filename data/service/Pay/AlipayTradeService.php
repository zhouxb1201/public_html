<?php

namespace data\service\Pay;

use data\service\Config;
use data\service\Pay\PayParam;
use data\extend\aop\alipay_api\AlipayTradePagePayRequest;
use data\extend\aop\AopClient;
use data\extend\aop\alipay_api\AlipayTradeCloseRequest;
use data\extend\aop\alipay_api\AlipayTradeWapPayRequest;
use data\extend\aop\alipay_api\AlipayTradeAppPayRequest;
use data\extend\aop\alipay_api\AlipayTradeFastpayRefundQueryRequest;
use data\extend\aop\alipay_api\AlipayTradeQueryRequest;
use data\extend\aop\alipay_api\AlipayTradeRefundRequest;
use data\extend\aop\alipay_api\AlipayFundTransToaccountTransferRequest;
use data\extend\aop\alipay_api\AlipayDataDataserviceBillDownloadurlQueryRequest;
use think\Db;
use data\extend\aop\alipay_api\AlipayTradePrecreateRequest;
use data\extend\aop\alipay_api\AlipayF2FPrecreateResult;
class AlipayTradeService extends PayParam {

    //支付宝网关地址
    public $gateway_url = "https://openapi.alipay.com/gateway.do";
    //支付宝公钥
    public $alipay_public_key;
    //商户私钥
    public $private_key;
    //应用id
    public $app_id;
    //编码格式
    public $charset = "UTF-8";
    public $token = NULL;
    //返回数据格式
    public $format = "json";
    //签名方式
    public $signtype = "RSA2";

    function __construct($website_id = 0) {
        parent::__construct($website_id);
        $alipay_configs = $this->getAlipayConfigForPc();
        $this->gateway_url = $alipay_configs['gatewayUrl'];
        $this->app_id = $alipay_configs['app_id'];
        $this->private_key = $alipay_configs['merchant_private_key'];
        $this->alipay_public_key = $alipay_configs['alipay_public_key'];
        $this->charset = $alipay_configs['charset'];
        $this->signtype = $alipay_configs['sign_type'];

        if (empty($this->app_id) || trim($this->app_id) == "") {
            return false;
        }
        if (empty($this->private_key) || trim($this->private_key) == "") {
            return false;
        }
        if (empty($this->alipay_public_key) || trim($this->alipay_public_key) == "") {
            return false;
        }
        if (empty($this->charset) || trim($this->charset) == "") {
            return false;
        }
        if (empty($this->gateway_url) || trim($this->gateway_url) == "") {
            return false;
        }
    }

    /**
     * alipay.trade.page.pay
     * @param $builder 业务参数，使用buildmodel中的对象生成。
     * @param $return_url 同步跳转地址，公网可以访问
     * @param $notify_url 异步通知地址，公网可以访问
     * @return $response 支付宝返回的信息
     */
    function pagePay($builder, $return_url, $notify_url) {

        $biz_content = $builder->getBizContent();
        //打印业务参数
        $this->writeLog($biz_content);

        $request = new AlipayTradePagePayRequest();

        $request->setNotifyUrl($notify_url);
        $request->setReturnUrl($return_url);
        $request->setBizContent($biz_content);

        // 首先调用支付api
        $response = $this->aopclientRequestExecute($request, true);
        // $response = $response->alipay_trade_wap_pay_response;
        return $response;
    }
    //当面付2.0预下单(生成二维码,带轮询)
    public function qrPay($req,$notify_url) {

        $bizContent = $req->getBizContent();
        $this->writeLog($bizContent);

        $request = new AlipayTradePrecreateRequest();
        $request->setBizContent ( $bizContent );
        $request->setNotifyUrl ( $notify_url );

        // 首先调用支付api
        $response = $this->aopclientRequestExecutes ($request);
        $response = $response->alipay_trade_precreate_response;
        $result = new AlipayF2FPrecreateResult($response);
        if(!empty($response)&&("10000"==$response->code)){
            $result->setTradeStatus("SUCCESS");
        } elseif($this->tradeError($response)){
            $result->setTradeStatus("UNKNOWN");
        } else {
            $result->setTradeStatus("FAILED");
        }

        return $result;

    }
    /**
     * alipay.trade.wap.pay
     * @param $builder 业务参数，使用buildmodel中的对象生成。
     * @param $return_url 同步跳转地址，公网可访问
     * @param $notify_url 异步通知地址，公网可以访问
     * @return $response 支付宝返回的信息
     */
    public function wapPay($builder,$return_url,$notify_url) {

        $biz_content=$builder->getBizContent();
        //打印业务参数
        $this->writeLog($biz_content);

        $request = new AlipayTradeWapPayRequest();

        $request->setNotifyUrl($notify_url);
        $request->setReturnUrl($return_url);
        $request->setBizContent ( $biz_content );
        // 首先调用支付api
        $response = $this->aopclientRequestExecute ($request,true,2);
        // $response = $response->alipay_trade_wap_pay_response;
        return $response;
    }
    public function appPay($builder,$return_url,$notify_url) {
        $biz_content=$builder->getBizContent();
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayTradeAppPayRequest();
        $request->setNotifyUrl($notify_url);
        $request->setReturnUrl($return_url);
        $request->setBizContent ( $biz_content );
        // 首先调用支付api
        $response = $this->aopclientRequestExecuteApp($request);
        return $response;
    }
    /**
     * 支付宝基本设置
     *
     * @return unknown
     */
    public function getAlipayConfigForPc() {
        $alipay_config = array(
            //应用ID,您的APPID。
            'app_id' => $this->ali_appid, //'2018091961444301',
            //商户私钥
            'merchant_private_key' => $this->ali_private_key,//"MIIEpAIBAAKCAQEA1Cbzbo0fkCLpM5znc/z1UyPH2KH2Syp8Bgkl9KUfWlp2GVWpAyLHW7b+zBLiT7ULYM/C1EMRhCKwq29jcF+ltIAvH9lzsr1FjbG+N+VkwZFgMlaX+hiP4qY/EXJSHRtY//GyTmSCslqyd0YnRrFmT9DeWYdqa9GG/3HOfeR7ANVZVx7O5oJ8KN4dcT/utesz1E+6MRgHc2wAsFD5xPXDvaePUEn0WWxOZbnjCEVLLarak1Ic9j2OwysDNRmy5ZM+TMPEdqXkiLy2jP0QcN/YaHvMqEQRKX0oHrgyKQzEk8bhXCflTiq/zHCthuWNWt3TJsOU80cmJFR2TEh3QsNp5wIDAQABAoIBAG74EMz6tE/IcwK0R7y7y/a4+Iev8AxRJJ5jmp7k4Al619tYmxcw0eZ/SbelCQt4P4NcKSSuEDN1kcOaeAEHhr1rbzrRm1sa9Y6wMjc6ngFB8XdjJAuFXX3IR6Twj1L6QwtdeU7X9CUmm8MXxuOLV2DYd/WMh3XuGxbyiHgBUvWTryJiC8lNUPYq5pf9IJF42nxJ1WLf3kAmUYBurQN7swDkSXbtAiAbzxQri6idGhYGUTK6f9HFoLGlg+6ipfdIFsOlt9ou2i2g+4yEQBxxyEgl3knzIdFsgI7XtKEBbysMZbwAir+r6MCjkAneewaVydFE60mAk+VwPG2h0mpzZAECgYEA89zZ4gNuqTb/FI9SEMSIABkg5Nr7cFEgJayo6hKH05aUw54k379ybWPDhwoucbcnUU+WvWS40MGkzm9Vf8fx9PNDu4ZbFs56gYL/S786ZhljqjitRBS/EViCGtFebhg+MvVEXqDTx1sdRY3bmss9YVUw38SMRG3gmc/Cxscbx8ECgYEA3rYQPtRune0uslpIu3tDsBQMd+wlyE104wd6jlYuNAvlQPKt0zQ/qkhwmGvzlKzRRJyaMl7gEj/ZkOzoqyAnXXSHxoE9NZZy46CqVQdN09r3lgpfkfX8vrALaJaLoCwbw222XpZ303zDqKJcxl93slkzJOoOXMx3jSnKdBxC26cCgYEAh7VUz1EnqSWA6Hklq00jfiJ9yr4OhR+waybdzX1IdzhqSz5buOR6kmOdcS36ULAjQj2vXnCJ5SqOQ49zniuv+6fQ/q+zS0rWo/I5jna50g25CAaIbcW52rZNmQ0ApvX4zzTsulh34o5TCNz74/XMj7jv/OcNRBt9jTswYpx1WkECgYEAyyFl+diKOBMAF36Pfii0mSIAKVVDNMmpBfVpS+/A3onHBRETiGLMesTtpag4l+90Q89OOQkd+KcyCqR6prKCFRRXTq/MI1dg3MtK8Jjj3IqIbdpyRtAFQeuRzEgbe/EfNYWY4/b7vfK7BtFoKysiIpKHOnEcvnljxWZLmNG6DVsCgYBqp8XmXxxndplJDc+BN+mvv6VB0y2JEgJYyx1dcmPdYuwhHm3HU4Ncr3R3/mRirfBNNzB1kmguJNV7MSj5xPHBi0UlJVpg5dUL+T8pWsVV47Fa8G8eG6HnyjiAmCcXgxKYxIOMmSKAgD06BZCVWYEFhINevux4Uyw3uFlz87nq3w==",
            //编码格式
            'charset' => "UTF-8",
            //签名方式
            'sign_type' => "RSA2",
            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => $this->ali_public_key,//"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwZuVVxG83SZKH8hCJoInJ+tlM1tK/ILy6SXSGyc3pLyxpMgGdcLE5PEGBvMCGi0xVUNg7B1N9CMtSFjxJ+w3Jc310Vxoa3lOZlHSxU9beEHeg7Z+sFW/0KB/gE3AwsEGKUs91qBgSWJ9ihchfq9AkcAEsh1ONqRmJYBgTYRQrlVylYospOr2EUiY4lKnllr+iAj+q8kybigNTksE6D/QM6n+rJ5k8WzuiW31LVtUaJne+zrBZG02JymFTujAatqeW8H5ugrxE1IKkH/ldRkxRNUHRTo8DczSccnZaRa0JGgZIkI57bf0dH7RaQGYe2Gg+k1KpSJc6r/pUlqqxtDDfQIDAQAB",
        );
        return $alipay_config;
    }

    /**
     * sdkClient
     * @param $request 接口请求参数对象。
     * @param $ispage  是否是页面接口，电脑网站支付是页面表单接口。
     * @return $response 支付宝返回的信息
     */
    function aopclientRequestExecute($request, $ispage = false,$type=1) {

        $aop = new AopClient ();
        $aop->gatewayUrl = $this->gateway_url;
        $aop->appId = $this->app_id;
        $aop->rsaPrivateKey = $this->private_key;
        $aop->alipayrsaPublicKey = $this->alipay_public_key;
        $aop->apiVersion = "1.0";
        $aop->postCharset = $this->charset;
        $aop->format = $this->format;
        $aop->signType = $this->signtype;
        // 开启页面信息输出
        $aop->debugInfo = true;
        if ($ispage) {
            $result = $aop->pageExecute($request, "post");
            if($type==1){
              echo $result;
            }
        } else {
            $result = $aop->Execute($request);
        }
        return $result;
    }
    function aopclientRequestExecuteApp($request) {

        $aop = new AopClient ();
        $aop->gatewayUrl = $this->gateway_url;
        $aop->appId = $this->app_id;
        $aop->rsaPrivateKey = $this->private_key;
        $aop->alipayrsaPublicKey = $this->alipay_public_key;
        $aop->apiVersion = "1.0";
        $aop->postCharset = $this->charset;
        $aop->format = $this->format;
        $aop->signType = $this->signtype;
        // 开启页面信息输出
        $aop->debugInfo = true;
        $result = $aop->sdkExecute($request);
        //打开后，将报文写入log文件
        $this->writeLog("response: " . var_export($result, true));
        return $result;
    }
    /**
     * 使用SDK执行提交页面接口请求
     * @param unknown $request
     * @param string $token
     * @param string $appAuthToken
     * @return string $$result
     */
    function aopclientRequestExecutes($request) {
        $aop = new AopClient ();
        $config = new Config();
        $ali_config = $config->getAlipayConfigs()['value'];
        $aop->gatewayUrl = $this->gateway_url;
        $aop->appId =  $ali_config['appid'];
        $aop->rsaPrivateKey = $ali_config['ali_private_key'];
        $aop->alipayrsaPublicKey = $ali_config['ali_public_key'];
        $aop->apiVersion = "1.0";
        $aop->postCharset = $this->charset;
        $aop->format = $this->format;
        $aop->signType = $this->signtype;
        // 开启页面信息输出
        $aop->debugInfo = true;
        $result = $aop->Execute($request);
        //打开后，将报文写入log文件
        $this->writeLog("response: " . var_export($result, true));
        return $result;
    }
    /**
     * alipay.trade.query (统一收单线下交易查询)
     * @param $builder 业务参数，使用buildmodel中的对象生成。
     * @return $response 支付宝返回的信息
     */
    function Query($builder) {
        $biz_content = $builder->getBizContent();
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayTradeQueryRequest();
        $request->setBizContent($biz_content);

        $response = $this->aopclientRequestExecute($request);
        $response = $response->alipay_trade_query_response;
        return $response;
    }
    /**
     * alipay.fund.trans.toaccount.transfer(单笔转账到支付宝账户接口)
     * @param $builder 业务参数，使用buildmodel中的对象生成。
     * @return $response 支付宝返回的信息
     */
    function Transfer($builder) {
        $biz_content = $builder->getBizContent();
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayFundTransToaccountTransferRequest();
        $request->setBizContent($biz_content);

        $response = $this->aopclientRequestExecute($request);
        $response = $response->alipay_fund_trans_toaccount_transfer_response;
        return $response;
    }
// 交易异常，或发生系统错误
    protected function tradeError($response){
        return empty($response)||
            $response->code == "20000";
    }
    /**
     * alipay.trade.refund (统一收单交易退款接口)
     * @param $builder 业务参数，使用buildmodel中的对象生成。
     * @return $response 支付宝返回的信息
     */
    function Refund($builder) {
        $biz_content = $builder->getBizContent();
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayTradeRefundRequest();
        $request->setBizContent($biz_content);

        $response = $this->aopclientRequestExecute($request);
        $response = $response->alipay_trade_refund_response;
        return $response;
    }

    /**
     * alipay.trade.close (统一收单交易关闭接口)
     * @param $builder 业务参数，使用buildmodel中的对象生成。
     * @return $response 支付宝返回的信息
     */
    function Close($builder) {
        $biz_content = $builder->getBizContent();
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayTradeCloseRequest();
        $request->setBizContent($biz_content);

        $response = $this->aopclientRequestExecute($request);
        $response = $response->alipay_trade_close_response;
        return $response;
    }

    /**
     * 退款查询   alipay.trade.fastpay.refund.query (统一收单交易退款查询)
     * @param $builder 业务参数，使用buildmodel中的对象生成。
     * @return $response 支付宝返回的信息
     */
    function refundQuery($builder) {
        $biz_content = $builder->getBizContent();
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayTradeFastpayRefundQueryRequest();
        $request->setBizContent($biz_content);

        $response = $this->aopclientRequestExecute($request);
        return $response;
    }

    /**
     * alipay.data.dataservice.bill.downloadurl.query (查询对账单下载地址)
     * @param $builder 业务参数，使用buildmodel中的对象生成。
     * @return $response 支付宝返回的信息
     */
    function downloadurlQuery($builder) {
        $biz_content = $builder->getBizContent();
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayDataDataserviceBillDownloadurlQueryRequest();
        $request->setBizContent($biz_content);

        $response = $this->aopclientRequestExecute($request);
        $response = $response->alipay_data_dataservice_bill_downloadurl_query_response;
        return $response;
    }

    /**
     * 验签方法
     * @param $arr 验签支付宝返回的信息，使用支付宝公钥。
     * @return boolean
     */
    function check($arr,$website_id) {
        $aop = new AopClient();
        $config = new Config();
        $alipay_config = $config->getAlipayConfig($website_id);
        $aop->alipayrsaPublicKey = $alipay_config['value']['ali_public_key'];
        $result = $aop->rsaCheckV1($arr, $alipay_config['value']['ali_public_key'], $this->signtype);
        return $result;
    }
    function checks($arr) {
        $aop = new AopClient();
        $config = new Config();
        $alipay = $config->getAlipayConfigs();
        $aop->alipayrsaPublicKey = $alipay['value']['ali_public_key'];
        $result = $aop->rsaCheckV1($arr, $alipay['value']['ali_public_key'], $this->signtype);
        return $result;
    }
    function checkKey($data,$public_key,$key,$sign) {
        $aop = new AopClient();
        $result = $aop->verifyKeys($data,$public_key,$key,$sign);
        return $result;
    }
    /**
     * 请确保项目文件有可写权限，不然打印不了日志。
     */
    function writeLog($text) {
        // $text=iconv("GBK", "UTF-8//IGNORE", $text);
        //$text = characet ( $text );
        file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . "log.txt", date("Y-m-d H:i:s") . "  " . $text . "\r\n", FILE_APPEND);
    }
    function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
        $content = urlencode($content);
        $image = '<img src="http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content.'"  widht="'.$size.'" height="'.$size.'" />';
        return $image;
    }
}

?>