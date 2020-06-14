<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品表 回收站表
 * @author  www.vslai.com
 *
 */
class VslGoodsDeletedModel extends BaseModel {

    protected $table = 'vsl_goods_deleted';
    
    protected $rule = [
        'goods_id'  =>  '',
        'description'  =>  'no_html_parse',
        'goods_spec_format'  =>  'no_html_parse'
    ];
    protected $msg = [
        'goods_id'  =>  '',
        'description'  =>  '',
        'goods_spec_format'  =>  'no_html_parse'
    ];
    

}