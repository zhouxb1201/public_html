<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品评价表
 * @author  www.vslai.com
 *
 */
class VslGoodsEvaluateModel extends BaseModel {

    protected $table = 'vsl_goods_evaluate';
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
        $viewObj = $this->alias('nm')
            ->join('sys_user su','nm.uid= su.uid','left')
            ->field('nm.*,su.uid,su.user_headimg,su.user_tel, su.user_name,su.nick_name');
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
        $viewObj = $this->alias('nm')
            ->join('sys_user su','nm.uid= su.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}