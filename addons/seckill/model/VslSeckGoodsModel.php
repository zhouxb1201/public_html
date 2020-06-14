<?php
/**
 * ModuleModel.php
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
namespace addons\seckill\model;

use data\model\BaseModel as BaseModel;
/**
 * 优惠券商品表
 * @author Administrator
 *
 */
class VslSeckGoodsModel extends BaseModel {

    protected $table = 'vsl_seckill_goods';
    /*
     * 获取秒杀sku的价格
     * **/
    public function getSeckillSkuInfo($condition)
    {
        $sku_price_list = $this->field('seckill_price, seckill_limit_buy, seckill_num, remain_num, seckill_sales')->where($condition)->find();
        return $sku_price_list;
    }

    /*
     * 获取所有秒杀sku的信息
     * **/
    public function getAllSeckillSkuList($condition)
    {
        $all_sku_price_list = $this->field('sku_id, seckill_num, remain_num, seckill_sales')->where($condition)->select();
        return $all_sku_price_list;
    }

}