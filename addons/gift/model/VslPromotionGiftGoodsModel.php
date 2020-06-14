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
namespace addons\gift\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品赠品表
 * id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id ',
      gift_id int(10) UNSIGNED NOT NULL COMMENT '赠品id ',
      goods_id int(10) UNSIGNED NOT NULL COMMENT '商品id',
      goods_name varchar(50) NOT NULL COMMENT '商品名称',
      goods_picture varchar(100) NOT NULL COMMENT '商品图片',
 */
class VslPromotionGiftGoodsModel extends BaseModel {

    protected $table = 'vsl_promotion_gift_goods';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
    
    /**
     * 获取对应优惠券类型的相关商品列表
     * @param unknown $gift_id
     */
    public function getGiftGoodsList($gift_id)
    {
        $list = $this->alias('npgg')
                ->join('vsl_goods ng','npgg.goods_id = ng.goods_id','left')
                ->field(' npgg.gift_id, npgg.goods_id, ng.goods_name, ng.stock, ng.picture, ng.shop_id, ng.price')
                ->where(['gift_id'=>$gift_id])->select();
        return $list;    
    }
    

}