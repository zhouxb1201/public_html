<?php
/**
 * GoodsExpress.php
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

use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\service\BaseService;
use data\model\VslOrderShippingFeeModel;
use data\model\VslOrderExpressCompanyModel;
use data\model\CityModel;
use data\model\DistrictModel;
use data\service\Config;
use data\model\VslOrderShippingFeeAreaModel;
/**
 * 商品邮费操作类
 *
 * @author  www.vslai.com
 *
 */
class GoodsExpress extends BaseService
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取商品运费
     * @param array $goods_ids [商品id]
     * @param int $district_id [区域id]
     * @return array
     */
    public function getGoodsExpressTemplate($goods_ids = array(), $district_id = 0)
    {
        if(!$goods_ids || !is_array($goods_ids)){
            return array();
        }
        $totalFee = 0.00;
        /*
         * 修复bug 运费模板体积重量件数叠加计算运费
         * $author ljt 2018-08-05
         */
        $goods = new VslGoodsModel();
        $ids = [];
        $unShippingTypeCount = 0;//没有运费模板的商品总数量
        $unShippingTypeMoney = 0;//没有运费模板的商品总运费
        foreach($goods_ids as $goods_id){
            $goodsInfo = $goods->getInfo([
                'goods_id' => $goods_id['goods_id']
            ], 'shop_id, shipping_fee,shipping_fee_type,shipping_fee_id,goods_weight,goods_volume,goods_count');
            if ($goodsInfo['shipping_fee_type'] == 0) {
                $unShippingTypeCount += $goods_id['count'];
                $totalFee += 0.00;
            } elseif($goodsInfo['shipping_fee_type'] == 1){
                $totalFee += $goodsInfo['shipping_fee'];
                $unShippingTypeCount += $goods_id['count'];
                $unShippingTypeMoney += $goodsInfo['shipping_fee'];
            }else {
                $ids[$goodsInfo['shipping_fee_id']]['goods_weight'] += $goodsInfo['goods_weight'] * $goods_id['count'];
                $ids[$goodsInfo['shipping_fee_id']]['goods_volume'] += $goodsInfo['goods_volume'] * $goods_id['count'];
                $ids[$goodsInfo['shipping_fee_id']]['goods_count'] += $goodsInfo['goods_count'] * $goods_id['count'];
                
            }
        }
        /*
         * 如果有使用运费模板
         */
        if($ids){
            foreach($ids as $shipping_fee_id => $val){
                $shippingModel = new VslOrderShippingFeeModel();
                $shippingFee = $shippingModel->getInfo(['shipping_fee_id'=>$shipping_fee_id]);
                $val['shipping_fee_id'] = $shipping_fee_id;
                $totalFee += $this->getGoodsFee($val,$shippingFee,$district_id);
            }
        }
        return ['unShippingTypeMoney' => $unShippingTypeMoney, 'totalFee' => $totalFee, 'unShippingType' => $unShippingTypeCount];
    }
    
    public function getGoodsFee($goodsInfo,$shippingFee,$district_id){
        $shippingAreaModel = new VslOrderShippingFeeAreaModel(); 
        $shippingArea = $shippingAreaModel->getQuery(['shipping_fee_id'=>$goodsInfo['shipping_fee_id']],'*','is_default_area desc');
        if(!$shippingArea){
            return 0.00;
        }
        $shippingAreaGet = array();
        foreach($shippingArea as &$val){
            $districts = explode(',', $val['district_id_array']);
            if(in_array($district_id, $districts)){
                $shippingAreaGet = $val;
                break;
            }
        }
        if(!$shippingAreaGet){
            $shippingAreaGet = $shippingArea[0];
        }
        if ($shippingFee['calculate_type'] == 1) {//按重量计费
            if (($goodsInfo['goods_weight'] <= $shippingAreaGet['main_level_num']) || ($shippingAreaGet['extra_level_num'] == 0)) {
                return $shippingAreaGet['main_level_fee'];
            } else {
                return $shippingAreaGet['main_level_fee'] + ceil(($goodsInfo['goods_weight']  - $shippingAreaGet['main_level_num']) / $shippingAreaGet['extra_level_num']) * $shippingAreaGet['per_extra_level_fee'];
            }
        } elseif ($shippingFee['calculate_type'] == 3) {//按体积计费
            if (($goodsInfo['goods_volume'] <= $shippingAreaGet['main_level_num']) || ($shippingAreaGet['extra_level_num'] == 0)) {
                return $shippingAreaGet['main_level_fee'];
            } else {
                return $shippingAreaGet['main_level_fee'] + ceil(($goodsInfo['goods_volume'] - $shippingAreaGet['main_level_num']) / $shippingAreaGet['extra_level_num']) * $shippingAreaGet['per_extra_level_fee'];
            }
        } else {
            if (($goodsInfo['goods_count'] <= $shippingAreaGet['main_level_num']) || ($shippingAreaGet['extra_level_num'] == 0)) {
                return $shippingAreaGet['main_level_fee'];
            } else {
                return $shippingAreaGet['main_level_fee'] + ceil(($goodsInfo['goods_count'] - $shippingAreaGet['main_level_num']) / $shippingAreaGet['extra_level_num']) * $shippingAreaGet['per_extra_level_fee'];
            }
        }
    }
}