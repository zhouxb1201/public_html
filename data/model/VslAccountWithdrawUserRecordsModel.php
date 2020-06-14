<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员针对平台的提现记录表
 * @author  www.vslai.com
 *
 */
class VslAccountWithdrawUserRecordsModel extends BaseModel {

    protected $table = 'vsl_account_withdraw_user_records';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

}