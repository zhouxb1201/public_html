<?php

namespace addons\channel\model;

use data\model\BaseModel as BaseModel;
use think\Db;

/**
 * 优惠券类型表
 * @author Administrator
 *
 */
class VslChannelOrderModel extends BaseModel
{
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'vsl_channel_order';

    public function order_goods()
    {
        return $this->hasMany('VslChannelOrderGoodsModel', 'order_id', 'order_id');
    }

    public function buyer()
    {
        return $this->belongsTo('\data\model\UserModel', 'buyer_id', 'uid');
    }
}