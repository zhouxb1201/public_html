<?php

namespace addons\signin\model;

use data\model\BaseModel as BaseModel;

/**
 * 每日签到规则表
 * @author  www.vslai.com
 *
 */
class VslSignInRuleModel extends BaseModel
{
    protected $table = 'vsl_sign_in_rule';
    
    /*
     * 获取列表
     */
    public function getList($condition)
    { 
        $list = $this->getQuery(['website_id'=>$condition['website_id'],'sign_in_id'=>$condition['sign_in_id']],'*','rule_id asc');
        return $list;
    }
}