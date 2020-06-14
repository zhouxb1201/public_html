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
 * 满减送活动商品表
 *  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  promotion_goods_mansong_id int(11) NOT NULL COMMENT '满减送ID',
  goods_id int(11) NOT NULL COMMENT '商品ID',
  goods_name varchar(50) NOT NULL DEFAULT '' COMMENT '商品名称',
  goods_picture varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片',
  PRIMARY KEY (id)
 */
class VslPromotionMansongGoodsModel extends BaseModel {

    protected $table = 'vsl_promotion_mansong_goods';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

}