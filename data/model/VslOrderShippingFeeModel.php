<?php

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 运费模板主表
 * @author  www.vslai.com
 *
 */
class VslOrderShippingFeeModel extends BaseModel
{

    protected $table = 'vsl_order_shipping_fee';
    protected $rule = [
        'shipping_fee_id' => '',
    ];
    protected $msg = [
        'shipping_fee_id' => '',
    ];

    public function express_company()
    {
        return $this->belongsTo('VslOrderExpressCompanyModel', 'co_id', 'co_id');
    }

    public function shipping_area()
    {
        return $this->hasMany('VslOrderShippingFeeAreaModel', 'shipping_fee_id', 'shipping_fee_id');
    }
}