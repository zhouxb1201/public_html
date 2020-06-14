<?php
namespace addons\distribution\model;

use data\model\BaseModel as BaseModel;
/**
 * 佣金账户流水表
 * @author  www.vslai.com
 *
 */
class VslDistributorAccountRecordsModel extends BaseModel {
    protected $table = 'vsl_distributor_account_records';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}