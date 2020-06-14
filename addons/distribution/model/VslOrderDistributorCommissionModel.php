<?php
namespace addons\distribution\model;

use data\model\BaseModel as BaseModel;

class VslOrderDistributorCommissionModel extends BaseModel {

    protected $table = 'vsl_order_distributor_commission';
    protected $rule = [
        'order_goods_id'  =>  '',
    ];
    protected $msg = [
        'order_goods_id'  =>  '',
    ];
    /**
     * 获取订单分布
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @param unknown $group
     * @param unknown $field
     * @return \data\model\multitype:number
     */ 
    public function getOrderCommissionQuery($page_index, $page_size, $condition, $order,$group,$field)
    {
        $viewObj = $this->alias('no')
        ->join('vsl_order sp','no.order_id=sp.order_id','left')
        ->field($field)
        ->group($group);
        $queryList = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        $queryCount = count($queryList);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
}