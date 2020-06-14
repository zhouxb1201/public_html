<?php
/**
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace addons\gift\model;

use data\model\BaseModel as BaseModel;

class VslMemberGiftModel extends BaseModel {

    protected $table = 'vsl_member_gift';
    protected $rule = [
        'gift_id'  =>  '',
    ];
    protected $msg = [
        'gift_id'  =>  '',
    ];
    
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getMemberGiftViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getMemberGiftViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getMemberGiftViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getMemberGiftViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('vmg')
        ->join('vsl_promotion_gift vpg','vmg.promotion_gift_id = vpg.promotion_gift_id','left')
        ->join('sys_user su','vmg.uid = su.uid', 'left')
        ->field('vmg.no,su.user_tel,vmg.num,vmg.create_time,vmg.type');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if($list){
            foreach($list as $key => $val){
                $list[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
            }
        }
        return $list;
    }
    /*
     * 获取数量
     */
    public function getMemberGiftViewCount($condition)
    {
        $viewObj = $this->alias('vmg');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    

}