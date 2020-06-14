<?php

namespace addons\festivalcare\model;

use data\model\BaseModel as BaseModel;

/**
 * 节日关怀记录表
 * @author  www.vslai.com
 *
 */
class VslFestivalCareRecordsModel extends BaseModel
{

    protected $table = 'vsl_festival_care_records';
    
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
        $viewObj = $this->alias('vfcr')
        ->join('sys_user su', 'su.uid = vfcr.uid', 'left')
        ->join('vsl_member_prize vmp', 'vmp.member_prize_id = vfcr.member_prize_id', 'left')
        ->field($fields);
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $where, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getPrizeHistoryCount($where)
    {
        $viewObj = $this->alias('vfcr')
        ->join('vsl_member_prize vmp', 'vmp.member_prize_id = vfcr.member_prize_id', 'left');
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
    /*
     * 获取节日关怀详情
     */
    public function getFestivalcareDetail($prize_id){
        $detail = $this->alias('vfcr')
        ->join('vsl_festival_care vfc', 'vfc.festival_care_id = vfcr.festival_care_id', 'left')
        ->where(['vfcr.record_id'=>$prize_id])->find();
        return $detail;
    }
}