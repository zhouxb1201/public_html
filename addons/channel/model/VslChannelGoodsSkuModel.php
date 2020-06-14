<?php

namespace addons\channel\model;

use data\model\BaseModel as BaseModel;
use think\Db;

/**
 * 优惠券类型表
 * @author Administrator
 *
 */
class VslChannelGoodsSkuModel extends BaseModel
{
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'vsl_channel_goods_sku';

    public function goods()
    {
        return $this->belongsTo('VslChannelGoodsModel','goods_id','goods_id');
    }
}