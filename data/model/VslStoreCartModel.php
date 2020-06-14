<?php


namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 门店购物车表
 * @author  www.vslai.com
 *
 */

class VslStoreCartModel extends BaseModel
{
    protected $table = 'vsl_store_cart';
    protected $rule = [
        'cart_id' => '',
    ];
    protected $msg = [
        'cart_id' => '',
    ];

}