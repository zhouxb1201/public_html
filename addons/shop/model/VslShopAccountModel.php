<?php
namespace addons\shop\model;

use data\model\BaseModel as BaseModel;
/**
 * 店铺账户表
 * @author  www.vslai.com
 *
 */
class VslShopAccountModel extends BaseModel {

    protected $table = 'vsl_shop_account';
    protected $rule = [
        'shop_id'  =>  '',
    ];
    protected $msg = [
        'shop_id'  =>  '',
    ];

}