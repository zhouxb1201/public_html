<?php
namespace addons\bonus\model;

use data\model\BaseModel as BaseModel;

class VslOrderBonusModel extends BaseModel {

    protected $table = 'vsl_order_bonus';
    protected $rule = [
        'order_goods_id'  =>  '',
    ];
    protected $msg = [
        'order_goods_id'  =>  '',
    ];
    public function getViewList($page_index, $page_size, $condition, $order,$field,$group){
        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order,$field,$group);
        $queryCount = $this->getViewCount($condition,$group,$field);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    public function getViewQuery($page_index, $page_size, $condition, $order,$field,$group)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')->group($group)
            ->field($field);
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }

    public function getViewCount($condition,$group,$field)
    {
        $viewObj = $this->alias('nmar')->group($group)
            ->field($field);
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}