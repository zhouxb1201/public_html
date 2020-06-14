<?php
namespace addons\microshop\model;

use data\model\BaseModel as BaseModel;
/**
 * 佣金账户流水表
 * @author  www.vslai.com
 *
 */
class VslMicroShopAccountRecordsModel extends BaseModel {
    protected $table = 'vsl_microshop_account_records';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}