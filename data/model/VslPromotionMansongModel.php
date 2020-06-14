<?php
namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 满减送活动表
 *  mansong_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '满送活动编号',
 * mansong_name varchar(50) NOT NULL COMMENT '活动名称',
 * start_time datetime NOT NULL COMMENT '活动开始时间',
 * end_time datetime NOT NULL COMMENT '活动结束时间',
 * shop_id int(10) UNSIGNED NOT NULL COMMENT '店铺编号',
 * shop_name varchar(50) NOT NULL COMMENT '店铺名称',
 * status tinyint(1) UNSIGNED NOT NULL COMMENT '活动状态(0-未发布/1-正常/2-取消/3-失效/4-结束)',
 * remark varchar(200) NOT NULL COMMENT '备注',
 * type tinyint(1) NOT NULL DEFAULT 1 COMMENT '1.普通优惠  2.多级优惠',
 * range_type tinyint(1) NOT NULL DEFAULT 0 COMMENT '0全部商品 1.部分商品',
 * create_time datetime NOT NULL COMMENT '创建时间',
 * modify_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
 */
class VslPromotionMansongModel extends BaseModel
{

    protected $table = 'vsl_promotion_mansong';
    protected $rule = [
        'mansong_id' => '',
    ];
    protected $msg = [
        'mansong_id' => '',
    ];

    public function goods()
    {
        return $this->hasMany('VslPromotionMansongGoodsModel', 'mansong_id', 'mansong_id');
    }

    public function rules()
    {
        return $this->hasMany('VslPromotionMansongRuleModel', 'mansong_id', 'mansong_id');
    }
}