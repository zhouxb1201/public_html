<?php
/**
 * GoodsDiscount.php
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
use data\model\VslPromotionDiscountGoodsModel;
use data\model\VslPromotionDiscountGoodsViewModel;
use data\service\BaseService;
use data\model\AlbumPictureModel;
use data\model\VslPromotionDiscountModel;
use addons\shop\model\VslShopModel;
use think\Model;
/**
 * 商品显示折扣活动
 * @author  www.vslai.com
 *
 */
class GoodsDiscount extends BaseService{
    /**
     * 查询商品在某一时间段是否有限时折扣活动
     * @param unknown $goods_id
     */
    public function getGoodsIsDiscount($goods_id, $start_time, $end_time)
    {
        $discount_goods = new VslPromotionDiscountGoodsModel();
        $condition_1 = array(
            'start_time'=> array('ELT', $end_time),
            'end_time'  => array('EGT', $end_time),
            'status'     => array('NEQ', 3),
            'goods_id'  => $goods_id
        );
        $condition_2 = array(
            'start_time'=> array('ELT', $start_time),
            'end_time'  => array('EGT', $start_time),
            'status'     => array('NEQ', 3),
            'goods_id'  => $goods_id
        );
        $condition_3 = array(
            'start_time'=> array('EGT', $start_time),
            'end_time'  => array('ELT', $end_time),
            'status'     => array('NEQ', 3),
            'goods_id'  => $goods_id
        );
        $count_1 = $discount_goods->where($condition_1)->count();
        $count_2 = $discount_goods->where($condition_2)->count();
        $count_3 = $discount_goods->where($condition_3)->count();
        $count = $count_1 + $count_2 + $count_3;
        return $count;
    }
    /**
     * 查询限时折扣的商品
     * @param number $page_index
     * @param number $page_size
     * @param array $condition  注意传入数组
     * @param string $order
     */
    public function getDiscountGoodsList($page_index = 1, $page_size = 0, $condition = array(), $order = ''){
        $discount_goods = new VslPromotionDiscountGoodsViewModel();
        $goods_list = $discount_goods->getViewList($page_index, $page_size, $condition, $order);
        if(!empty($goods_list['data']))
        {
            foreach ($goods_list['data'] as $k => $v)
            {
                $discount = new VslPromotionDiscountModel();
                $discount_info = $discount->getInfo(['discount_id'=>$v['discount_id']], 'shop_id, shop_name, discount_name,discount_num');
                $goods_list['data'][$k]['shop_name'] = $discount_info['shop_name'];
                $goods_list['data'][$k]['discount_name'] = $discount_info['discount_name'];
                if(getAddons('shop', $this->website_id)){
                    $shop = new VslShopModel();
                    $shop_info = $shop->getInfo(['shop_id'=>$discount_info['shop_id']], 'shop_credit');
                    $goods_list['data'][$k]['shop_credit'] = $shop_info['shop_credit'];
                }
                $picture = new AlbumPictureModel();
                $picture_info = $picture->get($v['goods_picture']);
                $goods_list['data'][$k]['discount_num'] = $discount_info['discount_num'];
                $goods_list['data'][$k]['picture'] = $picture_info;
            }
        }
        return $goods_list;
    }
    
    /**
     * 获取 一个商品的 限时折扣信息
     * @param unknown $goods_id
     */
    public function getDiscountByGoodsid($goods_id){
        $discount_goods = new VslPromotionDiscountGoodsModel();
        $discount = $discount_goods->getInfo(['goods_id'=>$goods_id, 'status'=>1], 'discount');
        if(empty($discount)){
            return -1;
        }else{
            return $discount['discount'];
        }
    }
}