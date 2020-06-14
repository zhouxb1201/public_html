<?php
namespace addons\distribution\model;

use data\model\BaseModel as BaseModel;
/**
 * 分销商等级
 * @author  www.vslai.com
 *
 */
class VslDistributorLevelModel extends BaseModel {

    protected $table = 'vsl_distributor_level';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

/**
 * 多字段查询
 *
 */
    public function findBy($where=[], $field=['*'])
    {
         return $this->field($field)->where($where)->find();
    }
}