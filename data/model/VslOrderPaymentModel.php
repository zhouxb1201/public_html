<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 订单支付表
 *  
 */
class VslOrderPaymentModel extends BaseModel {

    protected $table = 'vsl_order_payment';
    protected $rule = [
        'out_trade_no'  =>  '',
    ];
    protected $msg = [
        'out_trade_no'  =>  '',
    ];

}