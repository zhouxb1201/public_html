<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/26 0026
 * Time: 14:44
 */
namespace addons\integral\model;

use data\model\BaseModel;

class VslIntegralGoodsAttributeModel extends BaseModel
{
    protected $table = 'vsl_integral_goods_attribute';

    public function attribute_value()
    {
        return $this->belongsTo('\data\model\VslAttributeValueModel', 'attr_value_id', 'attr_value_id');
    }
}
