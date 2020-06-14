<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/30 0030
 * Time: 11:03
 */

namespace addons\discount\server;

use data\model\AddonsConfigModel;
use data\model\ConfigModel;
use data\model\VslPromotionDiscountModel;
use data\service\BaseService;
use think\Db;

class Discount extends BaseService
{
    private $config_module;

    function __construct()
    {
        parent::__construct();
        $this->config_module = new ConfigModel();
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * 商品所属平台（店铺）的优先级最高
     */
    public function getPromotionInfo($goods_id, $shop_id, $website_id, $time = NULL)
    {
        if (!getAddons('discount',$website_id)){
            return ['discount_num' => 10];
        }
        $promotion_discount_model = new VslPromotionDiscountModel();
        if (!$time) {
            $time = time();
        }
        $condition['start_time'] = ['<=', $time];
        $condition['end_time'] = ['>=', $time];
        $condition['status'] = ['=', 1];
        $condition['website_id'] = $website_id;
        $promotion_discount_lists = $promotion_discount_model::all($condition);
        $promotion_discount_info = [];
        foreach ($promotion_discount_lists as $k => $list) {
            if (($list->range_type == 1 || $list->range_type == 3) && $list->shop_id != $shop_id) {
                //仅本店使用
                continue;
            }
            if ($list->range == 1) {//全部商品可用
                //当前list.shop_id匹配 或者 目前promotion_discount_info.shop_id不匹配，取level值大的
                if (empty($promotion_discount_info) ||
                    ($list->shop_id == $shop_id && $promotion_discount_info['shop_id'] == $shop_id && ($promotion_discount_info['level'] < $list->level)) ||
                    ($promotion_discount_info['shop_id'] != $shop_id && $list->shop_id == $shop_id)) {
                    $promotion_discount_info['level'] = $list->level;
                    $promotion_discount_info['discount_num'] = $list->discount_num;
                    $promotion_discount_info['discount_id'] = $list->discount_id;
                    $promotion_discount_info['discount_name'] = $list->discount_name;
                    $promotion_discount_info['shop_id'] = $list->shop_id;
                    $promotion_discount_info['start_time'] = $list->start_time;
                    $promotion_discount_info['end_time'] = $list->end_time;
                    $promotion_discount_info['discount_type'] = $list->discount_type;
                    $promotion_discount_info['integer_type'] = $list->integer_type;
                    $promotion_discount_info['status'] = 1;
                }
            } elseif ($list->range == 2) {//部分商品可用
                if ($list->goods()->where('goods_id', $goods_id)->count() > 0) {
                    if (empty($promotion_discount_info) ||
                        ($list->shop_id == $shop_id && $promotion_discount_info['shop_id'] == $shop_id && ($promotion_discount_info['level'] < $list->level)) ||
                        ($promotion_discount_info['shop_id'] != $shop_id && $list->shop_id == $shop_id)) {
                        $promotion_discount_info['level'] = $list->level;
                        $promotion_discount_info['discount_num'] = $list->goods()->where('goods_id', $goods_id)->find()['discount'] ?: 10;
                        $promotion_discount_info['discount_id'] = $list->discount_id;
                        $promotion_discount_info['discount_name'] = $list->discount_name;
                        $promotion_discount_info['shop_id'] = $list->shop_id;
                        $promotion_discount_info['start_time'] = $list->start_time;
                        $promotion_discount_info['end_time'] = $list->end_time;
                        $promotion_discount_info['discount_type'] = $list->discount_type;
                        $promotion_discount_info['integer_type'] = $list->integer_type;
                        $promotion_discount_info['status'] = 1;
                    }
                }
            }
        }
        return empty($promotion_discount_info) ? ['discount_num' => 10] : $promotion_discount_info;
    }
    //更新限时折扣
    public function updatePromotionDiscount($discount_id, $discount_name, $start_time, $end_time, $remark, $goods_id_array)
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $promotion_discount->startTrans();
        try {

            $shop_name = $this->instance_name;
            $data = array(
                'discount_name' => $discount_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'shop_id' => $this->instance_id,
                'shop_name' => $shop_name,
                'status' => 0,
                'remark' => $remark,
                'create_time' => time()
            );
            $promotion_discount->save($data, [
                'discount_id' => $discount_id
            ]);
            $goods_id_array = explode(',', $goods_id_array);
            $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
            $promotion_discount_goods->destroy([
                'discount_id' => $discount_id
            ]);
            foreach ($goods_id_array as $k => $v) {
                $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
                $discount_info = explode(':', $v);
                $goods_discount = new GoodsDiscount();
                $count = $goods_discount->getGoodsIsDiscount($discount_info[0], $start_time, $end_time);
                // 查询商品名称图片
                if ($count > 0) {
                    $promotion_discount->rollback();
                    return ACTIVE_REPRET;
                }
                // 查询商品名称图片
                $goods = new VslGoodsModel();
                $goods_info = $goods->getInfo([
                    'goods_id' => $discount_info[0]
                ], 'goods_name,picture');
                $data_goods = array(
                    'discount_id' => $discount_id,
                    'goods_id' => $discount_info[0],
                    'discount' => $discount_info[1],
                    'status' => 0,
                    'start_time' => getTimeTurnTimeStamp($start_time),
                    'end_time' => getTimeTurnTimeStamp($end_time),
                    'goods_name' => $goods_info['goods_name'],
                    'goods_picture' => $goods_info['picture']
                );
                $promotion_discount_goods->save($data_goods);
            }
            $promotion_discount->commit();
            return $discount_id;
        } catch (\Exception $e) {
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }
}