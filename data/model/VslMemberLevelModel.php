<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员等级列表
 * @author  www.vslai.com
 *
 */
class VslMemberLevelModel extends BaseModel {
    protected $table = 'vsl_member_level';
    protected $rule = [
        'level_id'  =>  '',
    ];
    protected $msg = [
        'level_id'  =>  '',
    ];
}