<?php

namespace addons\followgift\model;

use data\model\BaseModel as BaseModel;

/**
 * 关注有礼活动表
 * @author  www.vslai.com
 *
 */
class VslFollowGiftModel extends BaseModel
{

    protected $table = 'vsl_follow_gift';
    
    /*
     * 获取列表
     */
    public function getFollowgiftList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getFollowgiftViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getFollowgiftViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getFollowgiftViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->field('follow_gift_id,followgift_name,start_time,end_time,state,modes');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取各状态数量
     */
    public function getFollowgiftNum($condition)
    {
        unset($condition['state']);
        $wholeCount = $this->getFollowgiftViewCount($condition);
        $condition['state'] = 1;
        $stayCount = $this->getFollowgiftViewCount($condition);
        $condition['state'] = 2;
        $startCount = $this->getFollowgiftViewCount($condition);
        $condition['state'] = 3;
        $endCount = $this->getFollowgiftViewCount($condition);
        $count['whole'] = $wholeCount;
        $count['stay'] = $stayCount;
        $count['start'] = $startCount;
        $count['end'] = $endCount;
        return $count;
    }
    /*
     * 获取数量
     */
    public function getFollowgiftViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }
    /*
     * 获取关注有礼详情
     */
    public function getFollowgiftDetail($condition){
        $detail = $this->getInfo($condition,'');
        return $detail;
    }
}