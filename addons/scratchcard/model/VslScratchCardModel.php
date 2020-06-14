<?php

namespace addons\scratchcard\model;

use data\model\BaseModel as BaseModel;
use addons\scratchcard\model\VslScratchCardPrizeModel;
use addons\scratchcard\model\VslScratchCardRecordsModel;

/**
 * 刮刮乐活动表
 * @author  www.vslai.com
 *
 */
class VslScratchCardModel extends BaseModel
{

    protected $table = 'vsl_scratch_card';
    
    /*
     * 获取列表
     */
    public function getScratchcardViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getScratchcardViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getScratchcardViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getScratchcardViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->field('scratch_card_id,scratchcard_name,max_partake,start_time,end_time,state');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if($list){
            $vsl_record = new VslScratchCardRecordsModel();
            $list2 = $vsl_record->getQuery(['website_id'=>$condition['website_id'],'shop_id'=>$condition['shop_id']],'scratch_card_id,uid,state','scratch_time desc');
            $partake = [];
            foreach($list as $k => $v){
                $partakeNum = $winningNum = 0;
                if($list2){
                    foreach($list2 as $v2){
                        if($v2['scratch_card_id']==$v['scratch_card_id']){
                            if(empty($partake[$v['scratch_card_id']][$v2['uid']])){
                                $partakeNum += 1;
                            }
                            $partake[$v['scratch_card_id']][$v2['uid']] = 1;
                        }
                        if($v2['scratch_card_id']==$v['scratch_card_id'] && $v2['state']==1)$winningNum += 1;
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
    public function getScratchcardNum($condition)
    {
        unset($condition['state']);
        $wholeCount = $this->getScratchcardViewCount($condition);
        $condition['state'] = 1;
        $stayCount = $this->getScratchcardViewCount($condition);
        $condition['state'] = 2;
        $startCount = $this->getScratchcardViewCount($condition);
        $condition['state'] = 3;
        $endCount = $this->getScratchcardViewCount($condition);
        $count['whole'] = $wholeCount;
        $count['stay'] = $stayCount;
        $count['start'] = $startCount;
        $count['end'] = $endCount;
        return $count;
    }
    /*
     * 获取数量
     */
    public function getScratchcardViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }
    /*
     * 获取刮刮乐详情
     */
    public function getScratchcardDetail($condition){
        $detail = $this->getInfo($condition,'');
        if($detail){
            $vsl_prize = new VslScratchCardPrizeModel();
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