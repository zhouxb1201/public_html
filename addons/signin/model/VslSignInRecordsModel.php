<?php

namespace addons\signin\model;

use data\model\BaseModel as BaseModel;

/**
 * 会员签到记录表
 * @author  www.vslai.com
 *
 */
class VslSignInRecordsModel extends BaseModel
{

    protected $table = 'vsl_sign_in_records';
    
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
     * 获取列表
     */
    public function getList($where ,$field, $order)
    {
        $list = $this->alias('vsir')
        ->join('sys_user su', 'su.uid = vsir.uid', 'left')
        ->where($where)->field($field)->order($order)->select();
        return $list;
    }
    /*
     * 获取数据
     */
    public function getViewQuery($page_index, $page_size, $where ,$field, $order)
    {
        $viewObj = $this->alias('vsir')
        ->join('sys_user su', 'su.uid = vsir.uid', 'left')
        ->field($field);
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $where, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getViewCount($where)
    {
        $viewObj = $this->alias('vsir')
        ->join('sys_user su', 'su.uid = vsir.uid', 'left');
        $count = $this->viewCount($viewObj,$where);
        return $count;
    }
    /*
     * 获取每日签到详情
     */
    public function getDetail($where,$field){
        $detail = $this->alias('vsir')
        ->join('sys_user su', 'su.uid = vsir.uid', 'left')
        ->where($where)->field($field)->order('sign_in_time desc')
        ->find();
        return $detail;
    }
}