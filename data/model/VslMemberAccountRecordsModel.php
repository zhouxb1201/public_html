<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员账户流水表(积分，余额)
 * @author  www.vslai.com
 *
 */
class VslMemberAccountRecordsModel extends BaseModel {
    protected $table = 'vsl_member_account_records';
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
    public function getRecordsPonitQuery($page_index, $page_size, $condition, $order,$group,$field)
    {
        $viewObj = $this
        ->field($field)
        ->group($group);
        $queryList = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        $queryCount = count($queryList);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
}