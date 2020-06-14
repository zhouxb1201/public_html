<?php
/**
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace data\model;

use data\model\BaseModel as BaseModel;

class VslOrderGoodsViewModel extends BaseModel {

    protected $table = 'vsl_order_goods';
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getOrderGoodsViewList($page_index, $page_size, $condition, $order){
    
        $queryList = $this->getOrderGoodsViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getOrderGoodsViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取商品排行返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getOrderGoodsRankList($page_index, $page_size, $condition, $order){

        $queryList = $this->getOrderGoodsRankQuery($page_index, $page_size, $condition, $order);

        if ($page_size == 0){
            $queryCount = count($queryList);//$this->getOrderGoodsRankCount($condition);
        }else{
            $queryCount = count($this->getOrderGoodsRankQuery($page_index, 0, $condition, $order));
        }
        $list['data'] = $this->setReturnList($queryList, $queryCount, $page_size);
        $goods = new VslGoodsModel();
        $condition1['website_id'] = $condition['no.website_id'];
        $condition1['state'] = 1;
        if($condition['no.shop_id']){
            $condition1['shop_id'] = $condition['no.shop_id'];
        }
        $list['data1'] = $goods->pageQuery(1,0, $condition1, 'real_sales desc','*');
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
    public function getOrderGoodsViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('nog')
        ->join('vsl_order no','nog.order_id=no.order_id','left')
        ->field('nog.goods_name, nog.sku_name, nog.num, no.pay_time, no.create_time, no.user_name, no.order_no');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
        
    }
    /**
     * 获取排行
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getOrderGoodsRankQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('nog')
        ->join('vsl_order no','nog.order_id=no.order_id','left')
        ->join('sys_album_picture sap','nog.goods_picture=sap.pic_id','left')
        ->field('nog.goods_name, sum(nog.num) as sumCount,sum(no.order_money) as sumMoney,nog.goods_id,sap.pic_cover_micro')
        ->group('nog.goods_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
        
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getOrderGoodsViewCount($condition)
    {
        $viewObj = $this->alias('nog')
        ->join('vsl_order no','nog.order_id=no.order_id','left')
        ->field('nog.goods_name, nog.sku_name, nog.num, no.pay_time');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

    /**
     * 获取售后订单数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getAfterOrderViewCount($condition)
    {
        $viewObj = $this->alias('nog')
            ->join('vsl_order no','nog.order_id=no.order_id','left')
            ->field('count(1)')
            ->where($condition)
            ->group('nog.order_id')
            ->select();
        //$list = $this->query($viewObj,$condition);
        return count($viewObj);
    }

    
    public function getShippingList($page_index, $page_size, $condition, $order){
        $viewObj = $this->alias("nog")
        ->join('vsl_goods_sku ngs','nog.sku_id = ngs.sku_id','left')
        ->field('nog.goods_name,nog.sku_id,nog.sku_name,SUM(nog.num) as num,ngs.code,ngs.stock')
        ->group('nog.sku_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取某个用户，某个商品的订单量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getAllGoodsOrders($condition){
        $viewObj = $this->alias('nog')
            ->field('count(1)')
            ->where($condition)
            ->group('nog.order_id')
            ->select();
        return count($viewObj);
    }
}
