<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品表
 * @author  www.vslai.com
 *
 */
class VslGoodsModel extends BaseModel {

    protected $table = 'vsl_goods';
    protected $rule = [
        'goods_id'  =>  '',
        'description'  =>  'no_html_parse',
        'goods_spec_format'  =>  'no_html_parse'
    ];
    protected $msg = [
        'goods_id'  =>  '',
        'description'  =>  '',
        'goods_spec_format'  =>  ''
    ];

    public function album_picture()
    {
        return $this->belongsTo('AlbumPictureModel','picture','pic_id');
    }

    public function sku()
    {
        return $this->hasMany('VslGoodsSkuModel','goods_id','goods_id');
    }

    public function shop()
    {
        return $this->belongsTo('\addons\shop\model\VslShopModel','shop_id','shop_id');
    }

    public function shipping_company()
    {
        return $this->belongsTo('VslOrderExpressCompanyModel','shipping_fee_id','co_id');
    }

    /*
     * 获取秒杀商品信息
     * **/
    public function getSeckillGoodsInfo($condition){
//        $this->alias()->field()->select
    }

//    public function shipping_template()
//    {
//        return $this->belongsTo('VslOrderShippingFeeModel','shipping_fee_id','shipping_fee_id');
//    }
}