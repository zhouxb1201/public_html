<?php
namespace data\model;
use data\model\BaseModel as BaseModel;

/**
 * 产品表
 *
 */
class VslProductModel extends BaseModel
{

    protected $table = 'vsl_product';
    
    /**
     * 获取列表
     */
    public function getProductViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getProductViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getProductViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取数据
     */
    public function getProductViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('vp')->field('vp.*');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取数量
     */
    public function getProductViewCount($condition)
    {
        $viewObj = $this->alias('vp');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}