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
namespace addons\coupontype\model;

use data\model\BaseModel as BaseModel;
/**
 * 优惠券商品表
 * @author  www.vslai.com
 *
 */
class VslCouponGoodsModel extends BaseModel {

    protected $table = 'vsl_coupon_goods';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

    /**
     * 获取对应优惠券类型的相关商品列表
     * @param unknown $coupon_type_id
     */
    public function getCouponTypeGoodsList($coupon_type_id)
    {
        $list = $this->alias('ncg')
                ->join('vsl_goods ng','ncg.goods_id = ng.goods_id','left')
                ->field(' ncg.coupon_type_id, ncg.goods_id, ng.goods_name, ng.stock, ng.picture, ng.shop_id, ng.price')
                ->where(['coupon_type_id'=>$coupon_type_id])->select();
        return $list;    
    }

    
    /**
     * 获取对应优惠券类型的相关分页商品列表
     */
    public function getCouponGoodsList($page_index, $page_size, $condition,$field, $order,$group)
    {
        $view_obj = $this->alias('ncg')
        ->join('vsl_goods ng', 'ng.goods_id = ncg.goods_id', 'LEFT')
        ->join('vsl_goods_sku ngs', 'ng.goods_id = ngs.goods_id', 'LEFT')
        ->join('sys_album_picture sap', 'ng.picture = sap.pic_id', 'LEFT')
        ->join('vsl_shop vs', 'ng.shop_id = vs.shop_id and ng.website_id = vs.website_id', 'LEFT')
        ->field($field);
        $query_list = $this->viewPageQuerys($view_obj, $page_index, $page_size, $condition, $order, $group);
        $query_count = $this->alias('ncg')
        ->join('vsl_goods ng', 'ng.goods_id = ncg.goods_id', 'LEFT')
        ->join('vsl_goods_sku ngs', 'ng.goods_id = ngs.goods_id', 'LEFT')
        ->join('vsl_shop vs', 'ng.shop_id = vs.shop_id and ng.website_id = vs.website_id', 'LEFT')
        ->where($condition)
        ->group($group)
        ->select();
        $query_count = count($query_count);
        $list = $this->setReturnList($query_list, $query_count, $page_size);
        return $list;
    }
}