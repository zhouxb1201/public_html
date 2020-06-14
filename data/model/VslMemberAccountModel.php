<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员账户表(积分，余额)
 * @author  www.vslai.com
 *
 */
class VslMemberAccountModel extends BaseModel {
    protected $table = 'vsl_member_account';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}