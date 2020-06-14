<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 订单支付表
 *  
 */
class VslIncreMentOrderModel extends BaseModel {

    protected $table = 'vsl_increment_order';
    protected $rule = [
        'order_id'  =>  '',
    ];
    protected $msg = [
        'order_id'  =>  '',
    ];
    
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getOrderRecordViewList($page_index, $page_size, $condition, $order){
        
        $queryList = $this->getOrderRecordViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getOrderRecordViewCount($condition);
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
     public function getOrderRecordViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('vio')
        ->join('sys_website sw', 'sw.website_id=vio.website_id','left')
        ->join('sys_addons sa','sa.id=vio.addons_id','left')
        ->join('sys_user su','su.uid=sw.uid','left')
        ->field('vio.*, su.user_tel,sa.title');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getOrderRecordViewCount($condition)
    {
        $viewObj = $this->alias('vio')
        ->join('sys_website sw', 'sw.website_id=vio.website_id','left')
        ->join('sys_addons sa','sa.id=vio.addons_id','left')
        ->join('sys_user su','su.uid=sw.uid','left')
        ->field('vio.order_id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

}