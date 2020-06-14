<?php

namespace addons\smashegg\model;

use data\model\BaseModel as BaseModel;

/**
 * 砸金蛋奖项表
 * @author  www.vslai.com
 *
 */
class VslSmashEggPrizeModel extends BaseModel
{

    protected $table = 'vsl_smash_egg_prize';
    
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