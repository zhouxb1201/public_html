<?php

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 会员计时/次商品核销记录表
 */
class VslMemberCardRecordsModel extends BaseModel
{
    public $table = 'vsl_member_card_records';
    /*
     * 获取分页列表
     */
    public function getViewList($page_index, $page_size, $where ,$field, $order)
    {
        $queryList = $this->getViewQuery($page_index, $page_size, $where ,$field, $order);
        $queryCount = $this->getViewCount($where);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getViewQuery($page_index, $page_size, $where ,$field, $order)
    {
        $viewObj = $this->alias('vmcr')
        ->join('vsl_member_card vmc', 'vmc.card_id = vmcr.card_id', 'left')
        ->join('vsl_store vs','vs.store_id = vmc.store_id','left')
        ->field($field);
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $where, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getViewCount($where)
    {
        $viewObj = $this->alias('vmcr');
        $count = $this->viewCount($viewObj,$where);
        return $count;
    }
}
