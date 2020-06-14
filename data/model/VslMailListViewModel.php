<?php
namespace data\model;

use data\model\BaseModel as BaseModel;

class VslMailListViewModel extends BaseModel
{
    protected $table = 'vsl_message_send';
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('ncc')
        ->join('vsl_message_info nm','ncc.message_info_id=nm.message_info_id','left')
        ->field('nm.*, ncc.*');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('ncc')
        ->join('vsl_message_info nm','ncc.message_info_id=nm.message_info_id','left')
        ->field('ncc.message_info_id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}

?>