<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 物流公司表
 * @author  www.vslai.com
 *
 */
class VslOrderExpressCompanyModel extends BaseModel {
    
    protected $table = 'vsl_express_company';
    protected $rule = [
        'co_id'  =>  '',
    ];
    protected $msg = [
        'co_id'  =>  '',
    ];

//    public function shop()
//    {
//        return $this->belongsTo('\addons\shop\model\VslShopModel','shop_id','shop_id');
//    }

    public function shipping_fee()
    {
        return $this->hasMany('VslOrderShippingFeeModel','co_id','co_id');
    }

    public function shop()
    {
        return $this->hasMany('VslExpressCompanyShopRelationModel','co_id','co_id');
    }
    public function getViewList($page_index, $page_size, $condition, $order){

        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nca')
            ->field('nca.*');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }

    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nca')
            ->field('nca.*');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}