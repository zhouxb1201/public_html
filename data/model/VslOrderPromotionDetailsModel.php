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
 * 订单优惠详情
 *  id int(11) NOT NULL AUTO_INCREMENT,
  order_id int(11) NOT NULL COMMENT '订单ID',
  promotion_type_id int(11) NOT NULL COMMENT '优惠类型ID',
  promotion_id int(11) NOT NULL COMMENT '优惠ID',
  promotion_type varchar(255) NOT NULL COMMENT '优惠类型',
  promotion_name varchar(50) NOT NULL COMMENT '该优惠活动的名称',
  promotion_condition varchar(255) NOT NULL DEFAULT '' COMMENT '优惠使用条件说明',
  discount_money decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '优惠的金额，单位：元，精确到小数点后两位',
  used_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '使用时间',
 */
class VslOrderPromotionDetailsModel extends BaseModel {

    protected $table = 'vsl_order_promotion_details';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

}