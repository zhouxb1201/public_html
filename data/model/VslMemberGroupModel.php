<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员标签列表
 * @author  www.vslai.com
 *
 */
class VslMemberGroupModel extends BaseModel {
    protected $table = 'vsl_member_group';
    protected $rule = [
        'group_id'  =>  '',
    ];
    protected $msg = [
        'group_id'  =>  '',
    ];
}