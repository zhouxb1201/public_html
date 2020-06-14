<?php

namespace addons\smashegg\model;

use data\model\BaseModel as BaseModel;

/**
 * 砸金蛋记录表
 * @author  www.vslai.com
 *
 */
class VslSmashEggRecordsModel extends BaseModel
{

    protected $table = 'vsl_smash_egg_records';
    
    /*
     * 获取列表
     */
    public function getPrizeHistory($page_index, $page_size, $where, $fields, $order)
    {
        $queryList = $this->getPrizeHistoryQuery($page_index, $page_size, $where, $fields, $order);
        $queryCount = $this->getPrizeHistoryCount($where);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getPrizeHistoryQuery($page_index, $page_size, $where, $fields, $order)
    {
        $viewObj = $this->alias('vser')
        ->join('sys_user su', 'su.uid = vser.uid', 'left')
        ->join('vsl_member_prize vmp', 'vmp.member_prize_id = vser.member_prize_id', 'left')
        ->field($fields);
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $where, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getPrizeHistoryCount($where)
    {
        $viewObj = $this->alias('vser')
        ->join('vsl_member_prize vmp', 'vmp.member_prize_id = vser.member_prize_id', 'left');
        $count = $this->viewCount($viewObj,$where);
        return $count;
    }
    /*
     * 获取各状态数量
     */
    public function getUserPrizeNum($condition)
    {
        $condition['vmp.state'] = 1;
        $notCount = $this->getPrizeHistoryCount($condition);
        $condition['vmp.state'] = 2;
        $alreadyCount = $this->getPrizeHistoryCount($condition);
        $condition['vmp.state'] = 3;
        $overdueCount = $this->getPrizeHistoryCount($condition);
        $count['not'] = $notCount;
        $count['already'] = $alreadyCount;
        $count['overdue'] = $overdueCount;
        return $count;
    }
}