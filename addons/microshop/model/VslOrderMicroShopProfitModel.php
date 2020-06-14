<?php
namespace addons\microshop\model;

use data\model\BaseModel as BaseModel;

class VslOrderMicroShopProfitModel extends BaseModel {

    protected $table = 'vsl_order_microshop_profit';
    protected $rule = [
        'order_goods_id'  =>  '',
    ];
    protected $msg = [
        'order_goods_id'  =>  '',
    ];
}