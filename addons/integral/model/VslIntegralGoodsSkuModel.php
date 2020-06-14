<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/26 0026
 * Time: 14:44
 */
namespace addons\integral\model;

use data\model\BaseModel;

class VslIntegralGoodsSkuModel extends BaseModel
{
    protected $table = 'vsl_integral_goods_sku';

    public function goods()
    {
        return $this->belongsTo('VslIntegralGoodsModel', 'goods_id', 'goods_id');
    }
}
