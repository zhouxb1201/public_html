<?php

namespace addons\seckill\model;

use data\model\BaseModel as BaseModel;
use think\Db;

/**
 * 优惠券类型表
 * @author Administrator
 *
 */
class VslSeckillModel extends BaseModel
{
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'vsl_seckill';
    /**
     * 获取对应秒杀的相关商品列表
     * @param array $where
     */
    public function getSeckillGoodsList($where)
    {
        $list = $this->alias('ns')
            ->join('vsl_seckill_goods nsg','nsg.seckill_id = ns.seckill_id','left')
            ->field('*')
            ->where($where)->select();
//        echo $this->getLastSql();exit;
        return $list;
    }
    /**
     * 获取对应条件的相关商品列表
     * @param unknown $coupon_type_id
     */
    public function getSeckillConGoodsList($page_index=1, $page_size, $condition, $order_by){
        $today_seckill_goods_list = $this->alias('ns')
            ->join('vsl_seckill_goods nsg', 'ns.seckill_id = nsg.seckill_id', 'LEFT')
            ->join('vsl_goods g', 'nsg.goods_id = g.goods_id', 'LEFT')
            ->join('vsl_goods_sku gs', 'g.goods_id=gs.goods_id', 'LEFT')
            ->join('sys_album_picture sap', 'g.picture = sap.pic_id', 'LEFT')
            ->join('vsl_shop s', 'g.shop_id = s.shop_id AND g.website_id = s.website_id', 'LEFT')
            ->join('vsl_goods_discount vgd', 'vgd.goods_id = g.goods_id', 'LEFT')
            ->field('ns.seckill_id, g.goods_name, g.price, g.shop_id, sap.pic_cover_big, nsg.seckill_price, nsg.seckill_num, nsg.remain_num, nsg.seckill_limit_buy, nsg.sku_id, gs.sku_name, g.goods_id, s.shop_name, ns.seckill_name, ns.seckill_time, ns.shop_id')
            ->order($order_by)
            ->where($condition)
            ->group('nsg.goods_id,nsg.seckill_id')
            ->limit(($page_index-1)*$page_size, $page_size)
            ->select();
        return $today_seckill_goods_list;
    }
    /**
     * 获取对应条件的相关商品总数
     * @param unknown $coupon_type_id
     */
    public function getSeckillConGoodsTotal($condition){
        $today_seckill_goods_total = $this->alias('ns')
            ->field('count(distinct nsg.goods_id,ns.seckill_id) AS goods_count')
            ->join('vsl_seckill_goods nsg', 'ns.seckill_id = nsg.seckill_id', 'LEFT')
            ->join('vsl_goods g', 'nsg.goods_id = g.goods_id', 'LEFT')
            ->join('sys_album_picture sap', 'g.picture = sap.pic_id', 'LEFT')
            ->join('vsl_shop s', 'g.shop_id = s.shop_id AND g.website_id = s.website_id', 'LEFT')
            ->join('vsl_goods_discount vgd', 'vgd.goods_id = g.goods_id', 'LEFT')
            ->where($condition)
            ->select();
        return $today_seckill_goods_total[0]->goods_count;
    }
    /**
     * 获取对应秒杀审核状态、未审核状态的统计
     * @param unknown $coupon_type_id
     */
    public function dateGoodsCount($condition){
        $res_count = $this->alias('ns')
            ->field('count(distinct nsg.goods_id,ns.seckill_id) AS goods_count')
            ->join('vsl_seckill_goods nsg','ns.seckill_id = nsg.seckill_id')
            ->join('vsl_goods g','nsg.goods_id = g.goods_id')
            ->where($condition)
            ->select();
        $goods_count = objToArr($res_count);
        return $goods_count;
    }
    /*
     * 查询某条件下秒杀商品的统计结果
     * [params] array $condition 条件数组
     * [return] str 返回查询统计结果
     * **/
    public function getSeckillGoodsCount($condition){
        $seckill_goods_total_arr = $this->alias('ns')
            ->field('ns.seckill_id,ns.seckill_name,count(distinct nsg.goods_id,ns.seckill_id) AS g_total')
            ->join('vsl_seckill_goods nsg','ns.seckill_id = nsg.seckill_id', 'LEFT')
            ->where($condition)
            ->group('ns.seckill_name')
            ->select();
        return $seckill_goods_total_arr;
    }
    /*
     * 得到条件字符串
     * [param] array $condition 条件
     * **/
    public function getCondition($condition){
        $where = '';
        foreach($condition as $k=>$v){
        $where .= $k .'='. $v.' AND ';
        }
        $where = trim($where, ' AND ');
        return $where;
    }
    /*
     * 秒杀商品数据统计
     * **/
    public function getSecGoodsCountInfo($condition, $order)
    {
        $goods_count_list = $this->alias('ns')
            ->field('sum( seckill_sales * seckill_price) AS store_price,
                        max(seckill_price) AS seckill_price,
                        sum(seckill_sales) AS store_num,
                        g.goods_name,
                        nsg.goods_id')
            ->join('vsl_seckill_goods nsg', 'ns.seckill_id = nsg.seckill_id', 'LEFT')
            ->join('vsl_goods g', 'g.goods_id = nsg.goods_id', 'LEFT')
            ->where($condition)
            ->group('nsg.goods_id')
            ->order($order)
            ->select();
        return $goods_count_list;
    }
    /*
     * 根据哪一天、秒杀点获取秒杀商品
     * **/
    public function getSeckGoodsData($condition)
    {
        $this->alias('ns')
            ->field('ns.seckill_id, g.goods_name, g.price, g.shop_id, sap.pic_cover_big, nsg.seckill_price, nsg.seckill_num, nsg.remain_num, nsg.seckill_limit_buy, nsg.sku_id, g.goods_id, s.shop_name, ns.seckill_name, ns.seckill_time, ns.shop_id')
            ->join('vsl_seckill_goods nsg', 'ns.seckill_id = nsg.seckill_id', 'LEFT')
            ->join('vsl_goods g', 'nsg.goods_id = g.goods_id', 'LEFT')
            ->join('sys_album_picture sap', 'g.picture = sap.pic_id', 'LEFT')
            ->group('ns.seckill_time,vsl_seckill_name')
            ->wehre($condition)
            ->select();
    }
    /*
     * 获取秒杀sku的信息
     * **/
    public function getSeckillSkuInfo($condition, $field)
    {
        $seckill_goods_sku_list = $this->alias('ns')
            ->field($field)
            ->join('vsl_seckill_goods nsg', 'ns.seckill_id = nsg.seckill_id', 'LEFT')
            ->join('vsl_goods ng', 'nsg.goods_id = ng.goods_id', 'LEFT')
            ->join('vsl_goods_sku ngs', 'nsg.sku_id = ngs.sku_id', 'LEFT')
            ->order('nsg.sku_id')
            ->where($condition)
            ->select();
        return $seckill_goods_sku_list;
    }
    /*
     * 得到wap端商品列表显示的数据
     * **/
    public function getWapSeckillGoodsList($condition, $field="*", $group='')
    {
//        var_dump($field);exit;
        $wapSeckillGoodsList = $this->alias('ns')
            ->join('vsl_seckill_goods nsg','ns.seckill_id = nsg.seckill_id','LEFT')
            ->join('vsl_goods g','g.goods_id = nsg.goods_id','LEFT')
            ->field($field)
            ->where($condition)
            ->group($group)
            ->select();
//        echo $this->getLastSql();exit;
        return $wapSeckillGoodsList;
    }
    /*
     * 获取秒杀活动是否开始
     * **/
    public function isSeckillStart($condition)
    {
        $seckill_now_time = $this->field('seckill_now_time')->where($condition)->find();
        return $seckill_now_time;
    }
    /*
     * 判断商品是否是秒杀商品获取秒杀活动是否开始
     * **/
    public function isSeckillGoods($condition)
    {
        $condition['nsg.check_status'] = 1;
        $condition['nsg.del_status'] = 1;
        $seckill_list = $this->alias('s')
            ->field('*')
            ->join('vsl_seckill_goods nsg','s.seckill_id = nsg.seckill_id','LEFT')
            ->where($condition)
            ->order('nsg.seckill_price')
            ->find();
        return $seckill_list;
    }











    /*public function couponsg()
    {
        return $this->hasMany('VslSeckillGoodsdelInfoModel', 'coupon_type_id', 'coupon_type_id');
    }

    public function goods()
    {
        return $this->belongsToMany('\data\model\VslGoodsModel', 'nsg_coupon_goods', 'goods_id', 'coupon_type_id');
    }*/

    /**
     * 获取对应优惠券类型的相关商品列表
     * @param int|string $coupon_type_id
     * @param string $fields
     *
     * @return array $list
     */
    /*public function getCouponList($coupon_type_id, $fields = '*')
    {
        $list = $this->alias('nct')
            ->join('vsl_coupon nc', 'nct.coupon_type_id = nc.coupon_type_id', 'left')
            ->field($fields)
            ->where(['nct.coupon_type_id' => $coupon_type_id])->select();
        return $list;
    }*/
}