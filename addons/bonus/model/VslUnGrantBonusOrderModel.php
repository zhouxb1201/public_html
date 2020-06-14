<?php
namespace addons\bonus\model;

use data\model\BaseModel as BaseModel;

class VslUnGrantBonusOrderModel extends BaseModel {

    protected $table = 'vsl_ungrant_bonus_order';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
    public function getViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member su','nmar.uid = su.uid','left')
            ->join('vsl_agent_level al','su.global_agent_level_id = al.id','left')
            ->field('nmar.* ,su.member_name, su.real_name, su.mobile, al.level_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }

    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nmar')
            ->join('vsl_member su','nmar.uid = su.uid','left')
            ->field('nmar.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    public function getViewLists($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    public function getViewQuerys($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member su','nmar.uid = su.uid','left')
            ->join('vsl_agent_level al','su.area_agent_level_id = al.id','left')
            ->field('nmar.* ,su.member_name, su.real_name, su.mobile, al.level_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }

    public function getViewCounts($condition)
    {
        $viewObj = $this->alias('nmar')
            ->join('vsl_member su','nmar.uid = su.uid','left')
            ->field('nmar.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}