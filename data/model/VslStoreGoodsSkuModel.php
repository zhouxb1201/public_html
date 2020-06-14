<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 门店商品sku表
 * @author  www.vslai.com
 *
 */
class VslStoreGoodsSkuModel extends BaseModel
{
    protected $table = 'vsl_store_goods_sku';
    protected $rule = [
        'sku_id' => '',
    ];
    protected $msg = [
        'sku_id' => '',
    ];

    public function storeGoods(){
        return $this->belongsTo('VslStoreGoodsModel','goods_id','goods_id');
    }
    public function goods()
    {
        return $this->belongsTo('VslGoodsModel','goods_id','goods_id');
    }
}