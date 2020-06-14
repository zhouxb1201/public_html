<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/12 0012
 * Time: 14:05
 */

namespace data\model;

use data\model\BaseModel as BaseModel;

class VslOrderShippingFeeAreaModel extends BaseModel
{
    protected $table = 'vsl_order_shipping_fee_area';

    public function shipping_fee()
    {
        return $this->belongsTo('VslOrderShippingFeeModel', 'shipping_fee_id', 'shipping_fee_model');
    }
}