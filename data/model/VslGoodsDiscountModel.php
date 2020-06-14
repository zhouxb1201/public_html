<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品折扣表
 * @author  www.vslai.com
 *
 */
class VslGoodsDiscountModel extends BaseModel {

    protected $table = 'vsl_goods_discount';
    protected $rule = [
        'value'  =>  'no_html_parse'
    ];
    protected $msg = [
    ];
}