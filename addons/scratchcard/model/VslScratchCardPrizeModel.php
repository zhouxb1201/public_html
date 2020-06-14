<?php

namespace addons\scratchcard\model;

use data\model\BaseModel as BaseModel;

/**
 * 刮刮乐奖项表
 * @author  www.vslai.com
 *
 */
class VslScratchCardPrizeModel extends BaseModel
{

    protected $table = 'vsl_scratch_card_prize';
    
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