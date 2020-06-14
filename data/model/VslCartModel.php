<?php
/**
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 购物车
 *  cart_id int(11) NOT NULL AUTO_INCREMENT COMMENT '购物车id',
 * buyer_id int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '买家id',
 * shop_id int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '店铺id',
 * shop_name varchar(100) NOT NULL DEFAULT '' COMMENT '店铺名称',
 * goods_id int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品id',
 * goods_name varchar(200) NOT NULL COMMENT '商品名称',
 * sku_id int(11) NOT NULL DEFAULT 0 COMMENT '商品的skuid',
 * sku_name varchar(200) NOT NULL DEFAULT '' COMMENT '商品的sku名称',
 * price decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '商品价格',
 * num smallint(5) UNSIGNED NOT NULL DEFAULT 1 COMMENT '购买商品数量',
 * goods_picture int(11) NOT NULL DEFAULT 0 COMMENT '商品图片',
 * bl_id mediumint(8) UNSIGNED NOT NULL DEFAULT 0 COMMENT '组合套装ID',
 * PRIMARY KEY (cart_id),
 * @author  www.vslai.com
 *
 */
class VslCartModel extends BaseModel
{

    protected $table = 'vsl_cart';
    protected $rule = [
        'cart_id' => '',
    ];
    protected $msg = [
        'cart_id' => '',
    ];

    public function buyer()
    {
        return $this->belongsTo('UserModel', 'buyer_id', 'uid');
    }

    public function goods()
    {
        return $this->belongsTo('VslGoodsModel', 'goods_id', 'goods_id');
    }

    public function shop()
    {
        return $this->belongsTo('\addons\shop\model\VslShopModel', 'shop_id', 'shop_id');
    }

    public function website()
    {
        return $this->belongsTo('WebsiteModel', 'website_id', 'website_id');
    }

    public function sku()
    {
        return $this->belongsTo('VslGoodsSkuModel', 'sku_id', 'sku_id');
    }

    public function goods_picture()
    {
        return $this->belongsTo('AlbumPictureModel', 'goods_picture', 'pic_id');
    }

}