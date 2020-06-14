<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 订单支付表
 *  
 */
class VslIncreMentOrderPaymentModel extends BaseModel {

    protected $table = 'vsl_increment_order_payment';
    protected $rule = [
        'out_trade_no'  =>  '',
    ];
    protected $msg = [
        'out_trade_no'  =>  '',
    ];

}