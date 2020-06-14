<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/23 0023
 * Time: 16:53
 */

namespace addons\registermarketing\model;

use data\model\BaseModel;

class VslRegisterMarketingModel extends BaseModel
{
    protected $table = 'vsl_register_marketing';

    public function coupons()
    {
        return $this->belongsToMany('addons\coupontype\model\VslCouponTypeModel', 'vsl_register_marketing_coupon_type', 'coupon_type_id', 'website_id');
    }

    public function gift_voucher()
    {
        return $this->belongsToMany('addons\giftvoucher\model\VslGiftVoucherModel', 'vsl_register_marketing_gift_voucher', 'gift_voucher_id', 'website_id');
    }
}