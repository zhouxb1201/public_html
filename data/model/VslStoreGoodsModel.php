<?php

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 门店商品表
 * @author  www.vslai.com
 *
 */
class VslStoreGoodsModel extends BaseModel
{
    protected $table = 'vsl_store_goods';
    protected $rule = [
        'id' => '',
    ];
    protected $msg = [
        'id' => '',
    ];

    public function goods(){
        return $this->hasMany('VslGoodsModel','goods_id','goods_id');
    }
    
}