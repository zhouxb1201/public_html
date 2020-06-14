<?php

namespace addons\coupontype\model;

use data\model\BaseModel as BaseModel;

/**
 * 优惠券类型表
 * @author  www.vslai.com
 *
 */
class VslCouponTypeModel extends BaseModel
{

    protected $table = 'vsl_coupon_type';

    public function coupons()
    {
        return $this->hasMany('VslCouponModel', 'coupon_type_id', 'coupon_type_id');
    }

    public function goods()
    {
        return $this->belongsToMany('\data\model\VslGoodsModel', 'vsl_coupon_goods', 'goods_id', 'coupon_type_id');
    }

    /**
     * 获取对应优惠券类型的相关商品列表
     * @param int|string $coupon_type_id
     * @param string $fields
     *
     * @return array $list
     */
    public function getCouponList($coupon_type_id, $fields = '*')
    {
        $list = $this->alias('nct')
            ->join('vsl_coupon nc', 'nct.coupon_type_id = nc.coupon_type_id', 'left')
            ->field($fields)
            ->where(['nct.coupon_type_id' => $coupon_type_id])->select();
        return $list;
    }
}