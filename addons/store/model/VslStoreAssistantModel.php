<?php

namespace addons\store\model;

use data\model\BaseModel;

class VslStoreAssistantModel extends BaseModel
{
    protected $table = 'vsl_store_assistant';
    
    /**
     * 获取店员列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getAssistantViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getAssistantViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getAssistantViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getAssistantViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('vsa')
        ->join('vsl_store vs','vsa.store_id = vs.store_id','left')
        ->join('vsl_store_jobs vsj','vsa.jobs_id = vsj.jobs_id','left')
        ->field('vsa.assistant_id,vsa.store_id,vsa.jobs_id,vsa.shop_id,vsa.website_id,vsa.assistant_name,vsa.assistant_tel,vsa.status,vsa.assistant_headimg,vs.store_name,vsj.jobs_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getAssistantViewCount($condition)
    {
        $viewObj = $this->alias('vsa');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}