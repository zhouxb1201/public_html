<?php
namespace data\extend\globepay;
/**
 * 申请退款对象
 * @author Leijid
 */
class GlobePayApplyRefund
{
    /**
     * 设置商户支付订单号，同一商户唯一
     * @param string $value
     **/
	protected $Values = array(); 
    public function setOrderId($value)
    {
        $this->Values['order_id'] = $value;
    }

    /**
     * 获取商户支付订单号
     * @return 值
     **/
    public function getOrderId()
    {
        return $this->Values['order_id'];
    }

    /**
     * 判断商户支付订单号是否存在
     * @return true 或 false
     **/
    public function isOrderIdSet()
    {
        return array_key_exists('order_id', $this->Values);
    }

    /**
     * 设置商户退款单号
     * @param string $value
     **/
    public function setRefundId($value)
    {
        $this->Values['refund_id'] = $value;
    }

    /**
     * 获取商户退款单号
     * @return 值
     **/
    public function getRefundId()
    {
        return $this->Values['refund_id'];
    }

    /**
     * 判断商户退款单号是否存在
     * @return true 或 false
     **/
    public function isRefundIdSet()
    {
        return array_key_exists('refund_id', $this->Values);
    }

    /**
     * 设置退款金额，单位是货币最小单位
     * @param string $value
     **/
    public function setFee($value)
    {
        $this->Values['fee'] = $value;
    }

    /**
     * 获取退款金额
     * @return 值
     **/
    public function getFee()
    {
        return $this->Values['fee'];
    }

    /**
     * 判断退款金额是否存在
     * @return true 或 false
     **/
    public function isFeeSet()
    {
        return array_key_exists('fee', $this->Values);
    }
}
