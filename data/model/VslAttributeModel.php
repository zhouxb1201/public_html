<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品相关属性表
 * @author  www.vslai.com
 *
 */
class VslAttributeModel extends BaseModel {

    protected $table = 'vsl_attribute';
    protected $rule = [
        'attr_id'  =>  '',
    ];
    protected $msg = [
        'attr_id'  =>  '',
    ];

}