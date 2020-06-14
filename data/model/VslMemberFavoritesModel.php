<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
use think\Db;

/**
 * 会员收藏表
 * @author  www.vslai.com
 *
 */
class VslMemberFavoritesModel extends BaseModel {
    protected $table = 'vsl_member_favorites';
    protected $rule = [
        'log_id'  =>  '',
    ];
    protected $msg = [
        'log_id'  =>  '',
    ];
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getGoodsFavouitesViewList($page_index, $page_size, $condition, $order){
    
        $queryList = $this->getGoodsFavouitesViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getGoodsFavouitesViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    
    /**
     * 获取商品收藏列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getGoodsFavouitesViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('nmf')
        ->field('nmf.*');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getGoodsFavouitesViewCount($condition)
    {
        $viewObj = $this->alias('nmf')
        ->field('nmf.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getShopsFavouitesViewList($page_index, $page_size, $condition, $order){
    
        $queryList = $this->getShopsFavouitesViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getShopsFavouitesViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    
    /**
     * 获取店铺收藏列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getShopsFavouitesViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('nmf')
        ->field('nmf.*');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);          
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getShopsFavouitesViewCount($condition)
    {
        $viewObj = $this->alias('nmf')
        ->field('nmf.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}