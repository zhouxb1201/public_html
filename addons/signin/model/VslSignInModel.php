<?php

namespace addons\signin\model;

use data\model\BaseModel as BaseModel;

/**
 * 每日签到表
 * @author  www.vslai.com
 *
 */
class VslSignInModel extends BaseModel
{

    protected $table = 'vsl_sign_in';
    /*
     * 获取每日签到详情
     */
    public function getDetail($condition){
        $detail = $this->getInfo($condition);
        return $detail;
    }
}