<?php

namespace addons\seckill\model;

use data\model\BaseModel as BaseModel;

/**
 * 优惠券表
 * @author Administrator
 *
 */
class VslSeckillGoodsdelInfoModel extends BaseModel
{

    protected $table = 'vsl_seckill_delgoods_info';
    /*
     * 获取已拒绝的商品数量
     * **/
    public function refused_goods_count($condition){
        $refused_total = $this->alias('nsdi')
            ->field('nsg.goods_id')
            ->join('vsl_seckill ns', 'nsdi.seckill_id=ns.seckill_id')
            ->join('vsl_seckill_goods nsg', 'ns.seckill_id = nsg.seckill_id')
            ->join('vsl_goods g', 'nsdi.goods_id = g.goods_id')
            ->where($condition)
            ->group('nsg.seckill_id,nsg.goods_id')
            ->select();
//        $refused_total = objToArr($refused_total);
        $refused_total = count($refused_total);
        return $refused_total;
    }

    /*
     * 获取已拒绝的商品列表
     * **/
    public function refused_goods_list($page_index=1, $page_size, $condition, $order_by){
        $refused_total = $this->alias('nsdi')
            ->field('ns.seckill_id, g.goods_name, g.price, sap.pic_cover_big, nsg.seckill_price, nsg.seckill_num, nsg.remain_num, nsg.seckill_limit_buy, nsg.sku_id, g.goods_id, s.shop_name, ns.seckill_name, ns.seckill_time, nsdi.seckill_del_info')
            ->join('vsl_seckill ns', 'nsdi.seckill_id = ns.seckill_id', 'LEFT')
            ->join('vsl_seckill_goods nsg', 'ns.seckill_id = nsg.seckill_id', 'LEFT')
            ->join('vsl_goods g', 'nsg.goods_id = g.goods_id', 'LEFT')
            ->join('sys_album_picture sap', 'g.picture = sap.pic_id', 'LEFT')
            ->join('vsl_shop s', 'g.shop_id = s.shop_id AND g.website_id = s.website_id', 'LEFT')
            ->order($order_by)
            ->where($condition)
            ->group('nsg.seckill_id,nsg.goods_id')
            ->limit(($page_index-1)*$page_size, $page_size)
            ->select();
        return $refused_total;
    }
}