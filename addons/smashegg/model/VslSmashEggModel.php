<?php

namespace addons\smashegg\model;

use data\model\BaseModel as BaseModel;
use addons\smashegg\model\VslSmashEggPrizeModel;
use addons\smashegg\model\VslSmashEggRecordsModel;

/**
 * 砸金蛋活动表
 * @author  www.vslai.com
 *
 */
class VslSmashEggModel extends BaseModel
{

    protected $table = 'vsl_smash_egg';
    
    /*
     * 获取列表
     */
    public function getSmasheggViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getSmasheggViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getSmasheggViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getSmasheggViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->field('smash_egg_id,smashegg_name,max_partake,start_time,end_time,state');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if($list){
            $record = new VslSmashEggRecordsModel();
            $list2 = $record->getQuery(['website_id'=>$condition['website_id'],'shop_id'=>$condition['shop_id']],'smash_egg_id,uid,state','smash_time desc');
            $partake = [];
            foreach($list as $k => $v){
                $partakeNum = $winningNum = 0;
                if($list2){
                    foreach($list2 as $v2){
                        if($v2['smash_egg_id']==$v['smash_egg_id']){
                            if(empty($partake[$v['smash_egg_id']][$v2['uid']])){
                                $partakeNum += 1;
                            }
                            $partake[$v['smash_egg_id']][$v2['uid']] = 1;
                        }
                        if($v2['smash_egg_id']==$v['smash_egg_id'] && $v2['state']==1)$winningNum += 1;
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
    public function getSmasheggNum($condition)
    {
        unset($condition['state']);
        $wholeCount = $this->getSmasheggViewCount($condition);
        $condition['state'] = 1;
        $stayCount = $this->getSmasheggViewCount($condition);
        $condition['state'] = 2;
        $startCount = $this->getSmasheggViewCount($condition);
        $condition['state'] = 3;
        $endCount = $this->getSmasheggViewCount($condition);
        $count['whole'] = $wholeCount;
        $count['stay'] = $stayCount;
        $count['start'] = $startCount;
        $count['end'] = $endCount;
        return $count;
    }
    /*
     * 获取数量
     */
    public function getSmasheggViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }
    /*
     * 获取砸金蛋详情
     */
    public function getSmasheggDetail($condition){
        $detail = $this->getInfo($condition,'');
        if($detail){
            $vsl_prize = new VslSmashEggPrizeModel();
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