<?php
namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 订单自提点管理
 * 
 * @author  www.vslai.com
 *        
 */
class VslOrderPickupModel extends BaseModel
{

    protected $table = 'vsl_order_pickup';

    protected $rule = [
        'id' => ''
    ];

    protected $msg = [
        'id' => ''
    ];
}