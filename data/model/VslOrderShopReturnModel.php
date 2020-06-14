<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 店铺退货设置
 * @author  www.vslai.com
 *
 */
class VslOrderShopReturnModel extends BaseModel {

    protected $table = 'vsl_order_shop_return';
    protected $rule = [
        'shop_id'  =>  '',
    ];
    protected $msg = [
        'shop_id'  =>  '',
    ];
    
}