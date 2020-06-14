<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品属性值
 * @author  www.vslai.com
 *
 */
class VslAttributeValueModel extends BaseModel {

    protected $table = 'vsl_attribute_value';
    protected $rule = [
        'attr_value_id'  =>  '',
    ];
    protected $msg = [
        'attr_value_id'  =>  '',
    ];

    public function goods_attribute()
    {
        return $this->hasMany('VslAttributeValueModel','attr_value_id','attr_value_id');
    }

}