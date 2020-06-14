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
/**
 * 限时折扣商品表
 *  discount_goods_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  discount_id int(11) NOT NULL COMMENT '对应活动',
  start_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
  end_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束时间',
  goods_id int(11) NOT NULL COMMENT '商品ID',
  status tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态',
  discount tinyint(1) NOT NULL COMMENT '活动折扣或者减现信息',
  PRIMARY KEY (discount_goods_id)
 */
class VslPromotionDiscountGoodsModel extends BaseModel {

    protected $table = 'vsl_promotion_discount_goods';
    protected $rule = [
        'discount_goods_id'  =>  '',
    ];
    protected $msg = [
        'discount_goods_id'  =>  '',
    ];

}