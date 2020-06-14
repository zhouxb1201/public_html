<?php

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 订单商品表
 *  order_goods_id int(11) DEFAULT NULL,
 * order_id int(11) DEFAULT NULL,
 * goods_id int(11) DEFAULT NULL,
 * goods_name varchar(50) DEFAULT NULL,
 * sku_id int(11) DEFAULT NULL,
 * sku_name varchar(50) DEFAULT NULL,
 * price decimal(19, 2) DEFAULT NULL,
 * num varchar(255) DEFAULT NULL,
 * adjust_money varchar(255) DEFAULT NULL,
 * goods_money varchar(255) DEFAULT NULL,
 * goods_picture varchar(255) DEFAULT NULL,
 * shop_id int(11) DEFAULT NULL,
 * buyer_id int(11) DEFAULT NULL,
 * goods_type varchar(255) DEFAULT NULL,
 * promotion_id int(11) DEFAULT NULL,
 * promotion_type_id int(11) DEFAULT NULL,
 * order_type varchar(255) DEFAULT NULL,
 * order_status varchar(255) DEFAULT NULL,
 * give_point varchar(255) DEFAULT NULL,
 * shipping_status varchar(255) DEFAULT NULL,
 * refund_time varchar(255) DEFAULT NULL,
 * refund_type varchar(255) DEFAULT NULL,
 * refund_require_money varchar(255) DEFAULT NULL,
 * refund_reason varchar(255) DEFAULT NULL,
 * refund_shipping_code varchar(255) DEFAULT NULL,
 * refund_shipping_company varchar(255) DEFAULT NULL,
 * refund_real_money varchar(255) DEFAULT NULL,
 * refund_status varchar(255) DEFAULT NULL,
 * memo varchar(255) DEFAULT NULL
 */
class VslOrderGoodsModel extends BaseModel
{

    protected $table = 'vsl_order_goods';
    protected $rule = [
        'order_goods_id' => '',
    ];
    protected $msg = [
        'order_goods_id' => '',
    ];

    public function order()
    {
        return $this->belongsTo('VslOrderModel', 'order_id', 'order_id');
    }

    public function order_goods_refund()
    {
        return $this->hasMany('VslOrderRefundModel', 'order_goods_id', 'order_goods_id');
    }

    public function goods_sku()
    {
        return $this->belongsTo('VslGoodsSkuModel', 'sku_id', 'sku_id');
    }

    public function goods_pic()
    {
        return $this->belongsTo('AlbumPictureModel', 'goods_picture', 'pic_id');
    }
}