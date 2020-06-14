<?php

namespace addons\poster\model;

use data\model\BaseModel as BaseModel;

/**
 * 海报
 *
 */
class PosterAwardModel extends BaseModel
{
    protected $table = 'vsl_poster_award';

    public function poster()
    {
        return $this->belongsTo('VslPosterModel', 'poster_id', 'poster_id');
    }

    public function coupon_type()
    {
        return $this->belongsTo('addons\coupontype\model\VslCouponTypeModel', 'coupon_type_id', 'coupon_type_id');
    }

    public function gift_voucher()
    {
        return $this->belongsTo('addons\giftvoucher\model\VslGiftVoucherModel', 'gift_voucher_id', 'gift_voucher_id');
    }
}