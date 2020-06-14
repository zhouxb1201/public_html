<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品订单物流信息表（多次发货）
 *  id int(11) NOT NULL AUTO_INCREMENT,
 order_id int(11) NOT NULL COMMENT '订单id',
 order_goods_id_array varchar(255) NOT NULL COMMENT '订单项商品组合列表',
 express_name varchar(50) NOT NULL DEFAULT '' COMMENT '包裹名称  （包裹- 1 包裹 - 2）',
 shipping_type tinyint(4) NOT NULL COMMENT '发货方式1 需要物流 0无需物流',
 express_company_id int(11) NOT NULL COMMENT '快递公司id',
 express_no varchar(50) NOT NULL COMMENT '运单编号',
 shipping_time datetime NOT NULL COMMENT '发货时间',
 uid int(11) NOT NULL COMMENT '用户id',
 user_name varchar(50) NOT NULL COMMENT '用户名',
 memo varchar(255) NOT NULL COMMENT '备注',
 */
class VslOrderGoodsExpressModel extends BaseModel {

    protected $table = 'vsl_order_goods_express';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

    public function express_company()
    {
        return $this->belongsTo('VslOrderExpressCompanyModel','express_company_id','co_id');
    }

}