<?php

namespace addons\wheelsurf\model;

use data\model\BaseModel as BaseModel;

/**
 * 大转盘奖项表
 * @author  www.vslai.com
 *
 */
class VslWheelsurfPrizeModel extends BaseModel
{

    protected $table = 'vsl_wheelsurf_prize';
    
    /*
     * 获取奖项
     */
    public function getPrizeQuery($condition)
    {
        $list = $this->getQuery($condition, '', 'sort asc');
        return $list;
    }
    /*
     * 获取数量
     */
    public function getPrizeCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }
}