<?php

namespace data\service\promotion;

use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\service\BaseService;
use data\service\Goods;
use data\model\VslPromotionDiscountModel;
use data\model\VslPointConfigModel;
use data\model\VslMemberModel;
use data\model\VslMemberLevelModel;
use addons\coupontype\model\VslCouponGoodsModel;
use addons\coupontype\model\VslCouponTypeModel;
use data\service\Config;
use data\service\Member\MemberCoupon;

/**
 * 商品优惠价格操作类(运费，商品优惠)(没有考虑订单优惠活动例如满减送)
 *
 */
class GoodsPreference extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }
    /*****************************************************************************************订单商品管理开始***************************************************/
    /**
     * 获取商品sku列表价格
     * @param unknown $goods_sku_list skuid:1,skuid:2,skuid:3
     */
    public function getGoodsSkuListPrice($goods_sku_list)
    {
        $price = 0;
        if (!empty($goods_sku_list)) {

            $goods_sku_list_array = explode(",", $goods_sku_list);
            foreach ($goods_sku_list_array as $k => $v) {
                $sku_data = explode(":", $v);
                $sku_id = $sku_data[0];
                $sku_count = $sku_data[1];
                $sku_price = $this->getGoodsSkuPrice($sku_id);
                $price += $sku_price * $sku_count;
            }
            return $price;

        } else {
            return $price;
        }

    }

    /**
     * 获取商品sku列表购买后可得积分
     * @param unknown $goods_sku_list
     */
    public function getGoodsSkuListGivePoint($goods_sku_list)
    {
        $point = 0;
        if (!empty($goods_sku_list)) {
            $goods_sku_list_array = explode(",", $goods_sku_list);
            foreach ($goods_sku_list_array as $k => $v) {
                $sku_data = explode(":", $v);
                $sku_id = $sku_data[0];
                $sku_count = $sku_data[1];
                $goods = new Goods();
                $goods_id = $goods->getGoodsId($sku_id);
                $give_point = $goods->getGoodsGivePoint($goods_id);
                $point += $give_point * $sku_count;

            }
            return $point;

        } else {
            return $point;
        }
    }

    /**
     * 获取商品对应sku的价格
     * @param unknown $sku_id
     */
    public function getGoodsSkuPrice($sku_id)
    {
        $goods_sku = new VslGoodsSkuModel();
        $goods_sku_info = $goods_sku->getInfo(['sku_id' => $sku_id], 'goods_id,price,promote_price');
        if (!empty($this->uid)) {
            $member_price = $this->getGoodsSkuMemberPrice($sku_id, $this->uid);
            if ($member_price < $goods_sku_info['promote_price']) {
                return $member_price;
            } else {
                return $goods_sku_info['promote_price'];
            }
        } else {
            return $goods_sku_info['promote_price'];
        }

    }

    /**
     * 获取商品sku的积分兑换值
     * @param unknown $sku_id
     * @return Ambigous <number, unknown>
     */
    public function getGoodsSkuExchangePoint($sku_id)
    {
        $goods_sku = new VslGoodsSkuModel();
        $goods_sku_info = $goods_sku->getInfo(['sku_id' => $sku_id], 'goods_id');
        $goods = new Goods();
        $point = $goods->getGoodsPointExchange($goods_sku_info['goods_id']);
        return $point;
    }

    /**
     * 获取商品列表总积分
     * @param unknown $goods_sku_list
     * @return Ambigous <\data\service\promotion\Ambigous, number, unknown>
     */
    public function getGoodsListExchangePoint($goods_sku_list)
    {
        $goods_sku_list_array = explode(",", $goods_sku_list);
        $point = 0;
        foreach ($goods_sku_list_array as $k => $v) {
            //获取sku总价
            $sku_data = explode(':', $v);
            $sku_id = $sku_data[0];
            $sku_point = $this->getGoodsSkuExchangePoint($sku_id);
            $point += $sku_point * $sku_data[1];

        }
        return $point;
    }
    /**
     * 获取积分对应金额
     * @param unknown $point
     * @param unknown $shop_id
     */
    /*   public function getPointMoney($point, $shop_id)
      {
          $point_config = new VslPointConfigModel();
          $config = $point_config->getInfo(['shop_id'=> $shop_id], 'is_open, convert_rate');
          if(!empty($config))
          {
              $money = $point*$config['convert_rate'];
          }else{
              $money = 0;
          }
          return $money;
      } */
    /**
     * 获取商品当前单品优惠活动
     * @param unknown $goods_id
     */
    public function getGoodsPromote($goods_id)
    {
        $goods = new VslGoodsModel();
        $promote_info = $goods->getInfo(['goods_id' => $goods_id], 'promotion_type,promote_id');
        if ($promote_info['promotion_type'] == 0) {
            //无促销活动
            return '';
        } else if ($promote_info['promotion_type'] == 1) {
            //团购(注意查询活动时间)
            return '团购';
        } else if ($promote_info['promotion_type'] == 2) {
            //限时折扣(注意查询活动时间)
            return '限时折扣';
        }
    }

    /**
     * 获取商品sku列表的商品列表形式
     * @param unknown $goods_sku_list array(array(goods_id,skuid,num))
     */
    public function getGoodsSkuListGoods($goods_sku_list)
    {
        $array = array();
        if (!empty($goods_sku_list)) {
            $goods_sku_list_array = explode(',', $goods_sku_list);
            foreach ($goods_sku_list_array as $k => $v) {
                $sku_item = explode(":", $v);
                //获取商品goods_id
                $goods = new Goods();
                $goods_id = $goods->getGoodsId($sku_item[0]);
                $array[] = array($goods_id, $sku_item[0], $sku_item[1]);
            }

        }
        return $array;

    }

    /**
     * 获取商品列表所属店铺(只针对单店)
     * @param unknown $goods_sku_list
     */
    public function getGoodsSkuListShop($goods_sku_list)
    {
        if (!empty($goods_sku_list)) {
            $goods_sku_list_array = explode(',', $goods_sku_list);
            $v = $goods_sku_list_array[0];
            $sku_item = explode(":", $v);
            //获取商品goods_id
            $goods = new Goods();
            $goods_id = $goods->getGoodsId($sku_item[0]);
            $shop_id = $goods->getGoodsShopid($goods_id);
            return $shop_id;
            // $array[] = array($goods_id, $sku_item[0], $sku_item[1]);

        } else {
            return 0;
        }
    }

    /**
     * 获取商品列表所属店铺(只针对多店版)
     * @param unknown $goods_sku_list
     */
    public function getGoodsSkuListShops($goods_sku_list)
    {
        $array = array();
        if (!empty($goods_sku_list)) {
            $goods_sku_list_array = explode(',', $goods_sku_list);
            foreach ($goods_sku_list_array as $k => $v) {
                $v = $goods_sku_list_array[0];
                $sku_item = explode(":", $v);
                //获取商品goods_id
                $goods = new Goods();
                $goods_id = $goods->getGoodsId($sku_item[0]);
                $shop_id = $goods->getGoodsShopid($goods_id);
                $array[] = array($shop_id, $goods_id, $sku_item[0], $sku_item[1]);
            }
        }
        return $array;
    }

    /**
     * 查询会员等级折扣
     * @param unknown $uid
     */
    public function getMemberLevelDiscount($uid)
    {
        $member_model = new VslMemberModel();
        $member_level_info = $member_model::get($uid, ['level']);
        if (!empty($member_level_info->level->goods_discount)) {
            return $member_level_info->level->goods_discount / 10;
        } else {
            return 1;
        }
    }

    /**
     * 获取商品会员价
     * @param unknown $goods_sku_id
     * @param unknown $uid
     */
    public function getGoodsSkuMemberPrice($goods_sku_id, $uid)
    {
        //查询sku相关信息
        $goods_sku = new VslGoodsSkuModel();
        $sku_info = $goods_sku->getInfo(['sku_id' => $goods_sku_id], 'price');
        $member_level_discount = $this->getMemberLevelDiscount($uid);
        return $sku_info['price'] * $member_level_discount;
    }

    /**
     * 获取自提点运费
     * @param unknown $goods_sku_list
     */
    public function getPickupMoney($goods_sku_list_price)
    {
        $config_service = new Config();
        $config_info = $config_service->getConfig($this->instance_id, $this->website_id, 'PICKUPPOINT_FREIGHT');
        if (!empty($config_info)) {
            $pick_up_info = json_decode($config_info['value'], true);
            if ($pick_up_info['is_enable'] == 1 && $goods_sku_list_price <= $pick_up_info['manjian_freight']) {
                $pick_money = $pick_up_info['pickup_freight'];
            } else {
                $pick_money = 0;
            }
        } else {
            $pick_money = 0;
        }
        return $pick_money;

    }

    /*****************************************************************************************订单商品管理结束***************************************************/

}