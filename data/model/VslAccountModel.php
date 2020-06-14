<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商城金额统计
 * @author  www.vslai.com
 *
 */
class VslAccountModel extends BaseModel {

    protected $table = 'vsl_account';
    protected $rule = [
        'account_id'  =>  '',
    ];
    protected $msg = [
        'account_id'  =>  '',
    ];

}