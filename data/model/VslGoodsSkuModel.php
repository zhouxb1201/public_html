<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品sku表
 * @author  www.vslai.com
 *
 */
class VslGoodsSkuModel extends BaseModel {

    protected $table = 'vsl_goods_sku';
    protected $rule = [
        'sku_id'  =>  '',
    ];
    protected $msg = [
        'sku_id'  =>  '',
    ];

    public function goods()
    {
        return $this->belongsTo('VslGoodsModel','goods_id','goods_id');
    }

}