<?php
namespace addons\shop\model;

use data\model\BaseModel as BaseModel;

/**
 * 店铺表
 * @author  www.vslai.com
 *
 */
class VslShopModel extends BaseModel
{

    protected $table = 'vsl_shop';
    protected $rule = [
        'shop_id' => '',
    ];
    protected $msg = [
        'shop_id' => '',
    ];

    public function logo()
    {
        return $this->belongsTo('data\model\AlbumPictureModel','shop_logo','pic_id');
    }
}