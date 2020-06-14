<?php

namespace addons\wheelsurf\model;

use data\model\BaseModel as BaseModel;
use addons\wheelsurf\model\VslWheelsurfPrizeModel;
use addons\wheelsurf\model\VslWheelsurfRecordsModel;

/**
 * 大转盘活动表
 * @author  www.vslai.com
 *
 */
class VslWheelsurfModel extends BaseModel
{

    protected $table = 'vsl_wheelsurf';
    
    /*
     * 获取列表
     */
    public function getWheelsurfViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getWheelsurfViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getWheelsurfViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getWheelsurfViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->field('wheelsurf_id,wheelsurf_name,max_partake,start_time,end_time,state');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if($list){
            $record = new VslWheelsurfRecordsModel();
            $list2 = $record->getQuery(['website_id'=>$condition['website_id'],'shop_id'=>$condition['shop_id']],'wheelsurf_id,uid,state','turn_time desc');
            $partake = [];
            foreach($list as $k => $v){
                $partakeNum = $winningNum = 0;
                if($list2){
                    foreach($list2 as $v2){
                        if($v2['wheelsurf_id']==$v['wheelsurf_id']){
                            if(empty($partake[$v['wheelsurf_id']][$v2['uid']])){
                                $partakeNum += 1;
                            }
                            $partake[$v['wheelsurf_id']][$v2['uid']] = 1;
                        }
                        if($v2['wheelsurf_id']==$v['wheelsurf_id'] && $v2['state']==1)$winningNum += 1;
                    }
                }
                $list[$k]['partakeNum'] = $partakeNum;
                $list[$k]['winningNum'] = $winningNum;
            }
        }
        return $list;
    }
    /*
     * 获取各状态数量
     */
    public function getWheelsurfNum($condition)
    {
        unset($condition['state']);
        $wholeCount = $this->getWheelsurfViewCount($condition);
        $condition['state'] = 1;
        $stayCount = $this->getWheelsurfViewCount($condition);
        $condition['state'] = 2;
        $startCount = $this->getWheelsurfViewCount($condition);
        $condition['state'] = 3;
        $endCount = $this->getWheelsurfViewCount($condition);
        $count['whole'] = $wholeCount;
        $count['stay'] = $stayCount;
        $count['start'] = $startCount;
        $count['end'] = $endCount;
        return $count;
    }
    /*
     * 获取数量
     */
    public function getWheelsurfViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }
    /*
     * 获取大转盘详情
     */
    public function getWheelsurfDetail($condition){
        $detail = $this->getInfo($condition,'');
        if($detail){
            $vsl_prize = new VslWheelsurfPrizeModel();
            unset($condition['shop_id']);
            unset($condition['website_id']);
            $prize = $vsl_prize->getPrizeQuery($condition);
            $prizeNum = $vsl_prize->getPrizeCount($condition);
            $detail['prize'] = $prize;
            $detail['prizeNum'] = $prizeNum;
        }
        return $detail;
    }
}