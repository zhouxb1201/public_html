<?php

namespace addons\qlkefu\model;

use data\model\BaseModel as BaseModel;

/**
 * 商户设置表
 *
 */
class VslQlkefuModel extends BaseModel
{
    protected $table = 'vsl_qlkefu';
    /*
     * 获取详情
     */
    public function getDetail($condition){
        $detail = $this->getInfo($condition);
        return $detail;
    }
    /*
     * 获取列表
     */
    public function getList($where ,$field){
        $list = $this->where($where)->field($field)->select();
        return $list;
    }
}