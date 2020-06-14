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
 * 订单退款账户记录
   id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  refund_trade_no varchar(55) NOT NULL,
  refund_money decimal(10, 2) NOT NULL COMMENT '退款金额',
  refund_way int(11) NOT NULL COMMENT '退款方式（1：微信，2：支付宝，10：线下）',
  buyer_id int(11) NOT NULL COMMENT '买家id',
  refund_time int(11) NOT NULL COMMENT '退款时间',
  remark varchar(255) NOT NULL COMMENT '备注',
  PRIMARY KEY (id)
 */
class VslOrderRefundAccountRecordsModel extends BaseModel {

    protected $table = 'vsl_order_refund_account_records';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

    public function order_goods()
    {
        return $this->belongsTo('VslOrderGoodsModel', 'order_goods_id', 'order_goods_id');
    }

    public function buyer()
    {
        return $this->belongsTo('UserModel', 'buyer_id', 'uid');
    }

}