<?php
/**
 * GoodsGift.php
 *
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace data\service\promotion;
use data\service\BaseService;
use data\model\VslPromotionGiftGoodsModel;
use data\model\AlbumPictureModel;
/**
 * 商品总赠品管理
 */
class GoodsGift extends BaseService{
    /**
     * 查询赠品的商品信息
     * @param unknown $gift_id
     */
    public function getGiftGoodsInfo($gift_id)
    {
        $gift = new VslPromotionGiftGoodsModel();
        $goods_info = $gift->getInfo(['gift_id' => $gift_id], 'goods_id,goods_name,goods_picture');
        $picture = new AlbumPictureModel();
        $picture_info = $picture->getInfo(['pic_id' => $goods_info['goods_picture']], 'pic_cover,pic_cover_mid,pic_cover_micro');
        $goods_info['picture_info'] = $picture_info;
        return $goods_info;
    }
    public function userAchieveGift($uid, $gift_id, $order){
        
    }
}