<?php

namespace addons\festivalcare\model;

use data\model\BaseModel as BaseModel;

/**
 * 节日关怀活动表
 * @author  www.vslai.com
 *
 */
class VslFestivalCareModel extends BaseModel
{

    protected $table = 'vsl_festival_care';
    
    /*
     * 获取列表
     */
    public function getFestivalcareList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getFestivalcareViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getFestivalcareViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getFestivalcareViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->field('festival_care_id,festivalcare_name,start_time,state');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取各状态数量
     */
    public function getFestivalcareNum($condition)
    {
        unset($condition['state']);
        $wholeCount = $this->getFestivalcareViewCount($condition);
        $condition['state'] = 1;
        $stayCount = $this->getFestivalcareViewCount($condition);
        $condition['state'] = 2;
        $startCount = $this->getFestivalcareViewCount($condition);
        $condition['state'] = 3;
        $endCount = $this->getFestivalcareViewCount($condition);
        $count['whole'] = $wholeCount;
        $count['stay'] = $stayCount;
        $count['start'] = $startCount;
        $count['end'] = $endCount;
        return $count;
    }
    /*
     * 获取数量
     */
    public function getFestivalcareViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }
    /*
     * 获取节日关怀详情
     */
    public function getFestivalcareDetail($condition){
        $detail = $this->getInfo($condition);
        return $detail;
    }
}