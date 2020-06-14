<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商城的资金记录
 * @author  www.vslai.com
 *
 */
class VslAccountRecordsModel extends BaseModel {

    protected $table = 'vsl_account_records';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
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
    public function getRecordsCommissionQuery($page_index, $page_size, $condition, $order,$group,$field)
    {
        $viewObj = $this->alias('no')
        ->join('vsl_order sp','no.type_alis_id=sp.order_no','left')
        ->field($field)
        ->group($group);
        $queryList = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        $queryCount = count($queryList);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
}