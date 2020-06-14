<?php
namespace data\service\Pay;
/* *
 * 功能：alipay.fund.trans.toaccount.transfer(单笔转账到支付宝账户接口)接口业务参数封装
 * 版本：2.0
 * 修改日期：2017-05-01
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */


class AlipayFundTransToaccountTransferBuilder
{

    // 商户转账唯一订单号。发起转账来源方定义的转账单据ID，用于将转账回执通知给来源方。 
    private $out_biz_no;

    // 收款方账户类型。可取值： 1、ALIPAY_USERID：支付宝账号对应的支付宝唯一用户号。以2088开头的16位纯数字组成。 2、ALIPAY_LOGONID：支付宝登录号，支持邮箱和手机号格式。
    private $payee_type;

    // 收款方账户
    private $payee_account;

    // 转账金额
    private $amount;

    // 付款方姓名
    private $payer_show_name;

    // 收款方真实姓名
    private $payee_real_name;
    
    // 转账备注
    private $remark;

    private $bizContentarr = array();

    private $bizContent = NULL;

    public function getBizContent()
    {
        if(!empty($this->bizContentarr)){
            $this->bizContent = json_encode($this->bizContentarr,JSON_UNESCAPED_UNICODE);
        }
        return $this->bizContent;
    }

    public function __construct()
    {
        $this->bizContentarr['product_code'] = "FAST_INSTANT_TRADE_PAY";
    }

    public function AlipayTradeWapPayContentBuilder()
    {
        $this->__construct();
    }

    public function getOutBizNo()
    {
        return $this->out_biz_no;
    }

    public function setOutBizNo($outbizno)
    {
        $this->out_biz_no = $outbizno;
        $this->bizContentarr['out_biz_no'] = $outbizno;
    }

    public function setPayeeType($payeetype)
    {
        $this->payee_type = $payeetype;
        $this->bizContentarr['payee_type'] = $payeetype;
    }

    public function getPayeeType()
    {
        return $this->payee_type;
    }

    public function getPayeeAccount()
    {
        return $this->payee_account;
    }

    public function setPayeeAccount($payeeAccount)
    {
        $this->payee_account = $payeeAccount;
        $this->bizContentarr['payee_account'] = $payeeAccount;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
        $this->bizContentarr['amount'] = $amount;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setPayerShowName($payerShowName)
    {
        $this->payer_show_name = $payerShowName;
        $this->bizContentarr['payer_show_name'] = $payerShowName;
    }

    public function getPayerShowName()
    {
        return $this->payer_show_name;
    }
    public function setPayeeRealName($payerRealName)
    {
        $this->payee_real_name = $payerRealName;
        $this->bizContentarr['payee_real_name'] = $payerRealName;
    }

    public function getPayeeRealName()
    {
        return $this->payee_real_name;
    }
    public function setRemark($remark)
    {
        $this->remark = $remark;
        $this->bizContentarr['remark'] = $remark;
    }

    public function getRemark()
    {
        return $this->remark;
    }

}

?>