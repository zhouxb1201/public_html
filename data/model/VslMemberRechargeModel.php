<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员等级列表
 * @author  www.vslai.com
 *
 */
class VslMemberRechargeModel extends BaseModel {
    protected $table = 'vsl_member_recharge';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}