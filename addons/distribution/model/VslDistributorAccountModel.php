<?php
namespace addons\distribution\model;

use data\model\BaseModel as BaseModel;
/**
 * 分销商账户表
 * @author  www.vslai.com
 *
 */
class VslDistributorAccountModel extends BaseModel {
    protected $table = 'vsl_distributor_account';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}