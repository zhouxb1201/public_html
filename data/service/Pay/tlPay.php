<?php
/**
 * tlPay.php
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

use addons\shop\model\VslShopBankAccountModel;
use data\model\ConfigModel;
use data\model\VslMemberBankAccountModel;

/**
 * 功能说明：通联支付接口
 */
class tlPay extends PayParam
{
    protected $bOptimize = FALSE;

    public $bFormatted = FALSE;
    function __construct($instance = 0)
    {
        parent::__construct($instance);
    }
    //用户签约申请
   public function setTlSigning($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$uid,$website_id){
        $config = new ConfigModel();
        $tl_config = $config->getInfo(['website_id'=>$website_id,'key'=>'TLPAY'],'value');
        $info= json_decode($tl_config['value'], true);
        $params = array();
        $params["cusid"] = $info['tl_cusid'];//商户号 必填
        $params["appid"] = $info['tl_appid'];//应用id 必填
        $params["version"] = 11;
        $params["reqip"] = $this->getIp();
        $params["randomstr"] = $this->getNonceStr();//随机字符串 必填
        $params["meruserid"] = $uid;//商户用户号 必填
        $params["accttype"] = $accttype;//卡类型 00：借记卡 02：准贷记卡/贷记卡 必填
        $params["acctno"] = $acctno;//银行卡号 必填
        $params["idno"] = $idno;//身份证号 必填 最后一位为X必须大写
        $params["acctname"] = $acctname;//用户名 必填
        $params["mobile"] = $mobile;//手机号码 必填
        $params["validdate"] = $validdate;//有效期 信用卡不能为空
        $params["cvv2"] = $cvv2;//信用卡不能为空
        $params["sign"] = $this->SignArray($params,$info['tl_key']);//签名
        $paramsStr = $this->ToUrlParams($params);
        $url = "https://vsp.allinpay.com/apiweb/qpay/agreeapply";
        $rsp =tl_request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray,$info['tl_key'])){
           return $rspArray;
        }
    }

    //用户签约申请确认
    public function setTlAgreeSigning($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$smscode,$thpinfo,$uid,$website_id){
        $config = new ConfigModel();
        $tl_config = $config->getInfo(['website_id'=>$website_id,'key'=>'TLPAY'],'value');
        $info= json_decode($tl_config['value'], true);
        $params = array();
        $params["cusid"] = $info['tl_cusid'];//商户号 必填
        $params["appid"] = $info['tl_appid'];//应用id 必填
        $params["version"] = 11;
        $params["reqip"] = $this->getIp();
        $params["randomstr"] = $this->getNonceStr();//随机字符串 必填
        $params["meruserid"] = $uid;//商户用户号 必填
        $params["accttype"] = $accttype;//卡类型 00：借记卡 02：准贷记卡/贷记卡 必填
        $params["acctno"] = $acctno;//银行卡号 必填
        $params["idno"] = $idno;//身份证号 必填 最后一位为X必须大写
        $params["acctname"] = $acctname;//用户名 必填
        $params["mobile"] = $mobile;//手机号码 必填
        $params["validdate"] = $validdate;//有效期 信用卡不能为空
        $params["cvv2"] = $cvv2;//信用卡不能为空
        $params["smscode"] = $smscode;//短信验证码
        $params["thpinfo"] = $thpinfo;//交易透传信息
        $params["sign"] = $this->SignArray($params,$info['tl_key']);//签名
        $paramsStr = $this->ToUrlParams($params);
        $url = "https://vsp.allinpay.com/apiweb/qpay/agreeconfirm";
        $rsp = tl_request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray,$info['tl_key'])){
            return $rspArray;
        }
    }

    //重发签约短信
    public function setTlAgreeSms($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$thpinfo,$uid,$website_id){
        $config = new ConfigModel();
        $tl_config = $config->getInfo(['website_id'=>$website_id,'key'=>'TLPAY'],'value');
        $info= json_decode($tl_config['value'], true);
        $params = array();
        $params["cusid"] = $info['tl_cusid'];//商户号 必填
        $params["appid"] = $info['tl_appid'];//应用id 必填
        $params["version"] = 11;
        $params["reqip"] = $this->getIp();
        $params["reqtime"] = date("Y-m-d H:i:s",time());
        $params["randomstr"] = $this->getNonceStr();//随机字符串 必填
        $params["meruserid"] = $uid;//商户用户号 必填
        $params["accttype"] = $accttype;//卡类型 00：借记卡 02：准贷记卡/贷记卡 必填
        $params["acctno"] = $acctno;//银行卡号 必填
        $params["idno"] = $idno;//身份证号 必填 最后一位为X必须大写
        $params["acctname"] = $acctname;//用户名 必填
        $params["mobile"] = $mobile;//手机号码 必填
        $params["validdate"] = $validdate;//有效期 信用卡不能为空
        $params["cvv2"] = $cvv2;//信用卡不能为空
        $params["thpinfo"] = $thpinfo;//交易透传信息
        $params["sign"] = $this->SignArray($params,$info['tl_key']);//签名
        $paramsStr = $this->ToUrlParams($params);
        $url = "https://vsp.allinpay.com/apiweb/qpay/agreesms";
        $rsp = tl_request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray,$info['tl_key'])){
            return $rspArray;
        }
    }

    //用户签约解绑
    public function tlUntying($id,$website_id){
        $config = new ConfigModel();
        $tl_config = $config->getInfo(['website_id'=>$website_id,'key'=>'TLPAY'],'value');
        $info= json_decode($tl_config['value'], true);
        $bank = new VslMemberBankAccountModel();
        $bank_info = $bank->getInfo(['id'=>$id]);
        $params = array();
        $params["agreeid"] = $bank_info['agree_id'];
        $params["cusid"] = $info['tl_cusid'];//商户号 必填
        $params["appid"] = $info['tl_appid'];//应用id 必填
        $params["version"] = 11;
        $params["reqip"] = $this->getIp();
        $params["reqtime"] = date("Y-m-d H:i:s",time());
        $params["randomstr"] = $this->getNonceStr();//随机字符串 必填
        $params["sign"] = $this->SignArray($params,$info['tl_key']);//签名
        $paramsStr = $this->ToUrlParams($params);
        $url = "https://vsp.allinpay.com/apiweb/qpay/unbind";
        $rsp = tl_request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray,$info['tl_key'])){
            return $rspArray;
        }
    }

    //商户支付申请
    public function payApplyAgree($id,$pay_money,$pay_detail,$out_trade_no,$red_url,$website_id){
        $config = new ConfigModel();
        $tl_config = $config->getInfo(['website_id'=>$website_id,'key'=>'TLPAY'],'value');
        $info= json_decode($tl_config['value'], true);
        $bank = new VslMemberBankAccountModel();
        $bank_info = $bank->getInfo(['id'=>$id]);
        $params = array();
        $params["cusid"] = $info['tl_cusid'];
        $params["appid"] = $info['tl_appid'];
        $params["version"] = 11;
        $params["reqip"] = $this->getIp();
        $params["reqtime"] = time();
        $params["randomstr"] = $this->getNonceStr();//随机字符串
        $params["orderid"] = $out_trade_no;
        $params["agreeid"] = $bank_info['agree_id'];//
        $params["amount"] = $pay_money;//
        $params["currency"] = "CNY";//
        $params["subject"] = $pay_detail;//
        $params["notifyurl"] = $red_url;//
        $params["sign"] = $this->SignArray($params,$info['tl_key']);//签名
        $paramsStr = $this->ToUrlParams($params);
        $url = "https://vsp.allinpay.com/apiweb/qpay/payapplyagree";
        $rsp = tl_request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray,$info['tl_key'])){
            return $rspArray;
        }
    }
    //商户支付申请确认
    public function payAgree($id,$smscode,$thpinfo,$out_trade_no,$website_id){
        $config = new ConfigModel();
        $tl_config = $config->getInfo(['website_id'=>$website_id,'key'=>'TLPAY'],'value');
        $info= json_decode($tl_config['value'], true);
        $bank = new VslMemberBankAccountModel();
        $bank_info = $bank->getInfo(['id'=>$id]);
        $params = array();
        $params["cusid"] = $info['tl_cusid'];
        $params["appid"] = $info['tl_appid'];
        $params["version"] = 11;
        $params["reqip"] = $this->getIp();
        $params["reqtime"] = time();
        $params["randomstr"] = $this->getNonceStr();//随机字符串
        $params["orderid"] = $out_trade_no;
        $params["agreeid"] = $bank_info['agree_id'];//
        $params["smscode"] = $smscode;//
        $params["thpinfo"] = $thpinfo;//
        $params["sign"] = $this->SignArray($params,$info['tl_key']);//签名
        $paramsStr = $this->ToUrlParams($params);
        $url = "https://vsp.allinpay.com/apiweb/qpay/payagreeconfirm";
        $rsp = tl_request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray,$info['tl_key'])){
            return $rspArray;
        }
    }

    //商户支付重新获取支付短信
    public function paySmsAgree($out_trade_no,$thpinfo,$website_id){
        $config = new ConfigModel();
        $tl_config = $config->getInfo(['website_id'=>$website_id,'key'=>'TLPAY'],'value');
        $info= json_decode($tl_config['value'], true);
        $params = array();
        $params["cusid"] = $info['tl_cusid'];
        $params["appid"] = $info['tl_appid'];
        $params["version"] = 11;
        $params["reqip"] = $this->getIp();
        $params["reqtime"] = time();
        $params["randomstr"] = $this->getNonceStr();//随机字符串
        $params["orderid"] = $out_trade_no;
        $params["thpinfo"] = $thpinfo;
        $params["sign"] = $this->SignArray($params,$info['tl_key']);//签名
        $paramsStr = $this->ToUrlParams($params);
        $url = "https://vsp.allinpay.com/apiweb/qpay/paysmsagree";
        $rsp = tl_request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray,$info['tl_key'])){
            return $rspArray;
        }
    }
    //通联通单笔代付
    public function tlWithdraw($withdraw_no,$uid,$bank_id,$money,$shop_id=0){
        $config = new ConfigModel();
        if($shop_id){
            $bank = new VslShopBankAccountModel();
            $bank_info = $bank->getInfo(['id'=>$bank_id]);
        }else{
            $bank = new VslMemberBankAccountModel();
            $bank_info = $bank->getInfo(['id'=>$bank_id]);
        }
        $config_info = $config->getInfo(['key'=>'TLPAY','website_id'=>$bank_info['website_id']]);
        $info= json_decode($config_info['value'], true);
        if(!$info){
            $retval['is_success'] = -1;
            $retval['msg'] = '银行卡提现缺少参数';
            return $retval;
        }
        $params = array(
            'INFO' => array(
                'TRX_CODE' => '100014',//交易代码
                'VERSION' => '05',
                'DATA_TYPE' => '2',
                'LEVEL' => '5',
                'USER_NAME' => $info['tl_username'],//用户名
                'USER_PASS' => $info['tl_password'],//用户密码
                'REQ_SN' => $withdraw_no,//请求流水号
            ),
            'TRANS' => array(
                'BUSINESS_CODE' => '09900',
                'MERCHANT_ID' => $info['tl_id'],//商户id
                'E_USER_CODE' => $uid,
                'BANK_CODE' => $bank_info['bank_code'],//银行代码
                'ACCOUNT_TYPE' => $bank_info['bank_type'],//账号类型00银行卡02信用卡
                'ACCOUNT_NO' => $bank_info['account_number'],//银行卡号
                'ACCOUNT_NAME' => $bank_info['realname'],//姓名
                'ACCOUNT_PROP' => $bank_info['bank_prop'],//账号属性0私人1公司
                'ID' => $bank_info['bank_card'],//持卡人身份证号
                'AMOUNT' => $money*100,//金额（分）
                'CURRENCY' => 'CNY',
            ),
        );
       //发起请求
        $result = $this->send($params,$info);
        if($result!=FALSE){
            if($result['AIPG']['INFO']['RET_CODE']==0000){
                $retval['is_success'] = 1;
            }else{
                $retval['is_success'] = -1;
                $retval['msg'] = $result['AIPG']['INFO']['ERR_MSG'];
            }
            return $retval;
//            echo  '验签通过，请对返回信息进行处理';
            //下面商户自定义处理逻辑，此处返回一个数组
        }else{
            $retval['msg'] = '验签失败，请检查通联公钥证书是否正确';
            $retval['is_success'] = -1;
            return $retval;
//            print_r("验签结果：验签失败，请检查通联公钥证书是否正确");
        }
    }
    //通联快捷支付退款
    public function tlRefund($withdraw_no,$money,$website_id,$trade_no){
        $config = new ConfigModel();
        $config_info = $config->getInfo(['key'=>'TLPAY','website_id'=>$website_id]);
        $info= json_decode($config_info['value'], true);
        if(!$info){
            $retval['is_success'] = -1;
            $retval['msg'] = '银行卡退款缺少参数';
            return $retval;
        }
        $params = array();
        $params["cusid"] = $info['tl_cusid'];
        $params["appid"] = $info['tl_appid'];
        $params["version"] = 11;
        $params["reqip"] = $this->getIp();
        $params["reqtime"] = time();
        $params["randomstr"] = $this->getNonceStr();//随机字符串
        $params["orderid"] = $withdraw_no;
        $params["trxamt"] = $money;
        $params["oldorderid"] = "";
        $params["oldtrxid"] = $trade_no;
        $params["sign"] = $this->SignArray($params,$info['tl_key']);//签名
        $paramsStr = $this->ToUrlParams($params);
        $url = "https://vsp.allinpay.com/apiweb/qpay/refund";
        $rsp = tl_request($url, $paramsStr);
        $rspArray = json_decode($rsp, true);
        if($this->validSign($rspArray,$info['tl_key'])){
            return $rspArray;
        }
    }
    public function get_client_ip()
    {
        $cip = "unknown";
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv('REMOTE_ADDR')) {
            $cip = getenv('REMOTE_ADDR');
        }
        return $cip;
    }

    public function getIp()
    {
        $ip = '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ip_arr = explode(',', $ip);
        return $ip_arr[0];
    }

    /**
     * 产生随机字符串，不长于32位
     *
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i ++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public  function SignArray(array $array,$appkey){
        $array['key'] = $appkey;// 将key放到数组中一起进行排序和组装
        ksort($array);
        $blankStr = $this->ToUrlParams($array);
        $sign = md5($blankStr);
        return $sign;
    }

    public  function ToUrlParams(array $array)
    {
        $buff = "";
        foreach ($array as $k => $v)
        {
            if($v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 校验签名
     * @param array 参数
     * @param unknown_type appkey
     */
    function validSign($array,$appkey){
        if($array["retcode"]=="SUCCESS"){
            $signRsp = strtolower($array["sign"]);
            $array["sign"] = "";
            $sign =  strtolower($this->SignArray($array, $appkey));
            if($sign==$signRsp){
                return TRUE;
            }
            else {
//                echo "验签失败:".$signRsp."--".$sign;
                return FALSE;
            }
        }
        else{
//          echo $array["retmsg"];
            return $array;
        }

        return FALSE;
    }
    /**
     * 校验签名
     * @param array 参数
     * @param unknown_type appkey
     */
    function validSigns($array,$appkey){
        $signRsp = strtolower($array["sign"]);
        $array["sign"] = "";
        $sign =  strtolower($this->SignArray($array, $appkey));
        if($sign==$signRsp){
            return TRUE;
        }
        else {
            return FALSE;
        }
    }
    public function parseString( $sXml , $bOptimize = FALSE) {
        $oXml = new \XMLReader();
        $this -> bOptimize = (bool) $bOptimize;
        try {
            // Set String Containing XML data
            $oXml->XML($sXml);

            // Parse Xml and return result
            return $this->parseXml($oXml);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

    }
    public function parseFile( $sXmlFilePath , $bOptimize = false ) {
        $oXml = new \XMLReader();
        $this -> bOptimize = (bool) $bOptimize;

        try {
            // Open XML file
            $oXml->open($sXmlFilePath);

            // Parse Xml and return result
            return $this->parseXml($oXml);
        } catch (\Exception $e) {
            echo $e->getMessage(). ' | Try open file: '.$sXmlFilePath;
        }
    }
    protected function parseXml( \XMLReader $oXml ) {
        $aAssocXML = null;

        $iDc = -1;

        while($oXml->read()){
            switch ($oXml->nodeType) {

                case \XMLReader::END_ELEMENT:

                    if ($this->bOptimize) {
                        $this->optXml($aAssocXML);
                    }
                    return $aAssocXML;

                case \XMLReader::ELEMENT:

                    if(!isset($aAssocXML[$oXml->name])) {
                        if($oXml->hasAttributes) {
                            $aAssocXML[$oXml->name][] = $oXml->isEmptyElement ? '' : $this->parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$oXml->name] = '';
                            } else {
                                $aAssocXML[$oXml->name] = $this->parseXML($oXml);
                            }
                        }
                    } elseif (is_array($aAssocXML[$oXml->name])) {
                        if (!isset($aAssocXML[$oXml->name][0]))
                        {
                            $temp = $aAssocXML[$oXml->name];
                            foreach ($temp as $sKey=>$sValue)
                                unset($aAssocXML[$oXml->name][$sKey]);
                            $aAssocXML[$oXml->name][] = $temp;
                        }

                        if($oXml->hasAttributes) {
                            $aAssocXML[$oXml->name][] = $oXml->isEmptyElement ? '' : $this->parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$oXml->name][] = '';
                            } else {
                                $aAssocXML[$oXml->name][] = $this->parseXML($oXml);
                            }
                        }
                    } else {
                        $mOldVar = $aAssocXML[$oXml->name];
                        $aAssocXML[$oXml->name] = array($mOldVar);
                        if($oXml->hasAttributes) {
                            $aAssocXML[$oXml->name][] = $oXml->isEmptyElement ? '' : $this->parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$oXml->name][] = '';
                            } else {
                                $aAssocXML[$oXml->name][] = $this->parseXML($oXml);
                            }
                        }
                    }

                    if($oXml->hasAttributes) {
                        $mElement =& $aAssocXML[$oXml->name][count($aAssocXML[$oXml->name]) - 1];
                        while($oXml->moveToNextAttribute()) {
                            $mElement[$oXml->name] = $oXml->value;
                        }
                    }
                    break;
                case \XMLReader::TEXT:
                case \XMLReader::CDATA:

                    $aAssocXML[++$iDc] = $oXml->value;

            }
        }

        return $aAssocXML;
    }
    public function optXml(&$mData) {
        if (is_array($mData)) {
            if (isset($mData[0]) && count($mData) == 1 ) {
                $mData = $mData[0];
                if (is_array($mData)) {
                    foreach ($mData as &$aSub) {
                        $this->optXml($aSub);
                    }
                }
            } else {
                foreach ($mData as &$aSub) {
                    $this->optXml($aSub);
                }
            }
        }
    }
    public function fixCDATA($string) {
        //fix CDATA tags
        $find[]     = '&lt;![CDATA[';
        $replace[] = '<![CDATA[';
        $find[]     = ']]&gt;';
        $replace[] = ']]>';

        $string = str_ireplace($find, $replace, $string);
        return $string;
    }
    public function is_assoc( $array ) {
        return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
    }
    public function toXml($data, $rootNodeName = 'data', &$xml=null)
    {
        // turn off compatibility mode as simple xml throws a wobbly if you don't.
        if ( ini_get('zend.ze1_compatibility_mode') == 1 ) ini_set ( 'zend.ze1_compatibility_mode', 0 );
        if ( is_null( $xml ) ) {
            $xml = simplexml_load_string(stripslashes("<?xml version='1.0' encoding='UTF-8'?><$rootNodeName></$rootNodeName>"));
        }

        // loop through the data passed in.
        foreach( $data as $key => $value ) {

            // no numeric keys in our xml please!
            $numeric = false;
            if ( is_numeric( $key ) ) {
                $numeric = 1;
                $key = $rootNodeName;
            }

            // delete any char not allowed in XML element names
            $key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

            //check to see if there should be an attribute added (expecting to see _id_)
            $attrs = false;

            //if there are attributes in the array (denoted by attr_**) then add as XML attributes
            if ( is_array( $value ) ) {
                foreach($value as $i => $v ) {
                    $attr_start = false;
                    $attr_start = stripos($i, 'attr_');
                    if ($attr_start === 0) {
                        $attrs[substr($i, 5)] = $v; unset($value[$i]);
                    }
                }
            }


            // if there is another array found recursively call this function
            if ( is_array( $value ) ) {
                if ( $this->is_assoc( $value ) || $numeric ) {
                    // older SimpleXMLElement Libraries do not have the addChild Method
                    if (method_exists('SimpleXMLElement','addChild')) {
                        $node = $xml->addChild( $key, null);
                        if ($attrs) {
                            foreach($attrs as $key => $attribute) {
                                $node->addAttribute($key, $attribute);
                            }
                        }
                    }

                }else{
                    $node =$xml;
                }

                // recrusive call.
                if ( $numeric ) $key = 'anon';

                $this->toXml( $value, $key, $node );
            } else {

                // older SimplXMLElement Libraries do not have the addChild Method
                if (method_exists('SimpleXMLElement','addChild')) {
                    $childnode = $xml->addChild( $key, $value);
                    if ($attrs) {
                        foreach($attrs as $key => $attribute) {
                            $childnode->addAttribute($key, $attribute);
                        }
                    }
                }
            }
        }

        if ($this->bFormatted) {
            // if you want the XML to be formatted, use the below instead to return the XML
            $doc = new \DOMDocument('1.0');
            $doc->preserveWhiteSpace = false;
            @$doc->loadXML( $this->fixCDATA($xml->asXML()) );
            $doc->formatOutput = true;

            return $doc->saveXML();
        }

        // pass back as unformatted XML
        return $xml->asXML();
    }
    public function toXmlGBK($data, $rootNodeName = 'data', &$xml=null)
    {
        return mb_convert_encoding(str_replace('<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="GBK"?>', $this->toXml($data, $rootNodeName, $xml)), 'GBK', 'UTF-8');
    }
    public function hextobin($hexstr) {
        $n = strlen($hexstr);
        $sbin = "";
        $i = 0;

        while($i < $n) {
            $a = substr($hexstr, $i, 2);
            $c = pack("H*",$a);
            if ($i==0) {
                $sbin = $c;
            } else {
                $sbin .= $c;
            }

            $i+=2;
        }

        return $sbin;
    }

    /**
     * 验签
     */
    public function verifyXml($xmlResponse,$info){
        // 本地反馈结果验证签名开始
        $signature = '';
        if (preg_match('/<SIGNED_MSG>(.*)<\/SIGNED_MSG>/i', $xmlResponse, $matches)) {
            $signature = $matches[1];
        }
        $xmlResponseSrc = preg_replace('/<SIGNED_MSG>.*<\/SIGNED_MSG>/i', '', $xmlResponse);
//        $xmlResponseSrc1 = mb_convert_encoding(str_replace('<','&lt;',$xmlResponseSrc), "UTF-8", "GBK");
//        print_r ('验签原文');
//        print_r ($xmlResponseSrc1);
        $pubKeyId = openssl_get_publickey(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.$info['tl_public']));
        $flag = (bool) openssl_verify($xmlResponseSrc, hex2bin($signature), $pubKeyId);
        openssl_free_key($pubKeyId);
        //echo '<br/>'+$flag;
        if ($flag) {
//            echo '<br/>Verified: <font color=green>Passed</font>.';
            // 变成数组，做自己相关业务逻辑
            $xmlResponse = mb_convert_encoding(str_replace('<?xml version="1.0" encoding="GBK"?>', '<?xml version="1.0" encoding="UTF-8"?>', $xmlResponseSrc), 'UTF-8', 'GBK');
            $results = $this->parseString( $xmlResponse , TRUE);
//            echo "<br/><br/><font color=blue>-------------华丽丽的分割线--------------------</font><br/><br/>";
//		    echo $results;
            return $results;
        } else {
//            echo '<br/>Verified: <font color=red>Failed</font>.';
            return FALSE;
        }
    }

    /**
     * 验签
     */
    public function verifyStr($orgStr,$signature){
        echo '签名原文:'.$orgStr;
        $pubKeyId = openssl_get_publickey(file_get_contents('./data/allinpay-pds.pem'));
        $flag = (bool) openssl_verify($orgStr, hex2bin($signature), $pubKeyId);
        openssl_free_key($pubKeyId);

        if ($flag) {
//            echo '<br/>Verified: <font color=red>SUCC</font>.';
            return TRUE;
        } else {
//            echo '<br/>Verified: <font color=red>Failed</font>.';
            return FALSE;
        }
    }

    /**
     * 签名
     */
    public function signXml($params,$info){
        $xmlSignSrc = $this->toXmlGBK($params, 'AIPG');
        $xmlSignSrc=str_replace("TRANS_DETAIL2", "TRANS_DETAIL",$xmlSignSrc);
        $privateKey = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.$info['tl_private']);
        $pKeyId = openssl_pkey_get_private($privateKey, $info['tl_password']);
        openssl_sign($xmlSignSrc, $signature, $pKeyId);
        openssl_free_key($pKeyId);
        $params['INFO']['SIGNED_MSG'] = bin2hex($signature);
        $xmlSignPost = $this->toXmlGBK($params, 'AIPG');
        return  $xmlSignPost;
    }
    /**
     * 发送请求
     */
    public function send($params,$info){
        $xmlSignPost=$this->signXml($params,$info);
        if($xmlSignPost){
            $xmlSignPost=str_replace("TRANS_DETAIL2", "TRANS_DETAIL",$xmlSignPost);
            $response = cURL::factory()->post("https://tlt.allinpay.com/aipg/ProcessServlet", $xmlSignPost);
            if (! isset($response['body'])) {
                die('Error: HTTPS REQUEST Bad.');
            }
            //获取返回报文
            $xmlResponse = $response['body'];
            //验证返回报文
            $result=$this->verifyXml($xmlResponse,$info);
            return $result;
        }else{
            return false;
        }
    }
}