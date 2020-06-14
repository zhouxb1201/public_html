<?php

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 限时折扣表
 *  discount_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
 * shop_id int(11) NOT NULL DEFAULT 1 COMMENT '店铺ID',
 * shop_name varchar(50) NOT NULL DEFAULT '' COMMENT '店铺名称',
 * discount_name varchar(255) NOT NULL DEFAULT '' COMMENT '活动名称',
 * start_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
 * end_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束时间',
 * status tinyint(1) NOT NULL DEFAULT 0 COMMENT '活动状态(0-未发布/1-正常/2-取消/3-失效/4-结束)',
 * create_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
 * modify_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
 * remark text NOT NULL COMMENT '备注',
 */
class VslPromotionDiscountModel extends BaseModel
{

    protected $table = 'vsl_promotion_discount';
    protected $rule = [
        'discount_id' => '',
    ];
    protected $msg = [
        'discount_id' => '',
    ];

    public function goods()
    {
        return $this->hasMany('VslPromotionDiscountGoodsModel', 'discount_id', 'discount_id');
    }
}