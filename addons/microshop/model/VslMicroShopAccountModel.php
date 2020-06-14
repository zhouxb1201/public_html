<?php
namespace addons\microshop\model;

use data\model\BaseModel as BaseModel;
/**
 * 分销商账户表
 * @author  www.vslai.com
 *
 */
class VslMicroShopAccountModel extends BaseModel {
    protected $table = 'vsl_microshop_account';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}