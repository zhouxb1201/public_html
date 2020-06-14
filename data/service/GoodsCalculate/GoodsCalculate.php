<?php
namespace data\service\GoodsCalculate;

/**
 * 商品购销存
 */

use addons\bargain\model\VslBargainModel;
use addons\channel\model\VslChannelGoodsModel;
use addons\channel\model\VslChannelGoodsSkuModel;
use addons\integral\model\VslIntegralGoodsModel;
use addons\seckill\model\VslSeckGoodsModel;
use data\model\VslPresellModel;
use data\model\VslStoreGoodsModel;
use data\model\VslStoreGoodsSkuModel;
use data\service\BaseService as BaseService;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslOrderModel;
use data\model\VslOrderGoodsModel;

class GoodsCalculate extends BaseService
{
    /**
     * 添加商品库存(购销存使用)
     * @param unknown $sku_id
     * @param unknown $num
     */
    public function addGoodsStock($goods_id, $sku_id, $num)
    {
        $goods_model = new VslGoodsModel();
        $stock = $goods_model->getInfo(['goods_id' => $goods_id], 'stock');
        $goods_sku_model = new VslGoodsSkuModel();
        $sku_stock = $goods_sku_model->getInfo(['sku_id' => $sku_id], 'stock');
        $goods_model->save(['stock' => $stock['stock'] + $num], ['goods_id' => $goods_id]);
        $retval = $goods_sku_model->save(['stock' => $sku_stock['stock'] + $num], ['sku_id' => $sku_id]);
        return $retval;
    }
    /**
     * 减少商品库存(购买使用)
     * @param unknown $sku_id  //商品属性
     * @param unknown $num     //商品数量
     * @param unknown $cost_price  //减少成本价  通过加权统计
     */
    public function subGoodsStock($goods_id, $sku_id, $num)
    {
        $goods_model = new VslGoodsModel();
        $stock = $goods_model->getInfo(['goods_id' => $goods_id], 'stock');
        if($stock['stock'] < $num)
        {
            return LOW_STOCKS;
            exit();
        }
        $goods_sku_model = new VslGoodsSkuModel();
        $sku_stock = $goods_sku_model->getInfo(['sku_id' => $sku_id], 'stock');
        if($sku_stock['stock'] < $num)
        {
            return LOW_STOCKS;
            exit();
        }
        $goods_model->save(['stock' => $stock['stock']-$num], ['goods_id' => $goods_id]);
        $retval = $goods_sku_model->save(['stock' => $sku_stock['stock']-$num], ['sku_id' => $sku_id]);
        return $retval;
        
    }
    /**
     * 减少商品库存(购买使用)(门店下单)
     * @param unknown $sku_id //商品属性
     * @param unknown $num //商品数量
     * @param unknown $cost_price //减少成本价  通过加权统计
     */
    public function storeSubGoodsStock($goods_id, $sku_id, $num, $store_id)
    {
        if($store_id){
        $store_goods_model = new VslStoreGoodsModel();
        $stock = $store_goods_model->getInfo(['goods_id' => $goods_id, 'store_id' => $store_id], 'stock');
        if ($stock['stock'] < $num) {
            return LOW_STOCKS;
            exit();
        }
        $store_goods_sku_model = new VslStoreGoodsSkuModel();
        $sku_stock = $store_goods_sku_model->getInfo(['sku_id' => $sku_id, 'store_id' => $store_id], 'stock');
        if ($sku_stock['stock'] < $num) {
            return LOW_STOCKS;
            exit();
        }
        $store_goods_model->save(['stock' => $stock['stock'] - $num], ['goods_id' => $goods_id, 'store_id' => $store_id]);
        $retval = $store_goods_sku_model->save(['stock' => $sku_stock['stock'] - $num], ['sku_id' => $sku_id, 'store_id' => $store_id]);
        return $retval;
        }else{
            $goods_model = new VslGoodsModel();
            $stock = $goods_model->getInfo(['goods_id' => $goods_id], 'stock');
            if ($stock['stock'] < $num) {
                return LOW_STOCKS;
                exit();
            }
            $goods_sku_model = new VslGoodsSkuModel();
            $sku_stock = $goods_sku_model->getInfo(['sku_id' => $sku_id], 'stock');
            if ($sku_stock['stock'] < $num) {
                return LOW_STOCKS;
                exit();
            }
            $goods_model->save(['stock' => $stock['stock'] - $num], ['goods_id' => $goods_id]);
            $retval = $goods_sku_model->save(['stock' => $sku_stock['stock'] - $num], ['sku_id' => $sku_id]);
            return $retval;
        }
    }
    /**
     * 减少渠道商商品库存(购买使用)
     * @param unknown $sku_id  //商品属性
     * @param unknown $num     //商品数量
     * @param unknown $cost_price  //减少成本价  通过加权统计
     */
    public function subChannelGoodsStock($goods_id, $sku_id, $num, $channel_id)
    {
        $goods_model = new VslChannelGoodsModel();
        $stock = $goods_model->getInfo(['goods_id' => $goods_id, 'channel_id'=>$channel_id], 'stock');
        if($stock['stock'] < $num)
        {
            return LOW_STOCKS;
            exit();
        }
        $goods_sku_model = new VslChannelGoodsSkuModel();
        $sku_stock = $goods_sku_model->getInfo(['sku_id' => $sku_id, 'channel_id'=>$channel_id], 'stock');
        if($sku_stock['stock'] < $num)
        {
            return LOW_STOCKS;
            exit();
        }
        $goods_model->save(['stock' => $stock['stock']-$num], ['goods_id' => $goods_id, 'channel_id'=>$channel_id]);
        $retval = $goods_sku_model->save(['stock' => $sku_stock['stock']-$num], ['sku_id' => $sku_id, 'channel_id'=>$channel_id]);
        return $retval;
    }
    /**
     * 增加渠道商商品库存(购买使用)
     * @param unknown $sku_id  //商品属性
     * @param unknown $num     //商品数量
     * @param unknown $cost_price  //减少成本价  通过加权统计
     */
    public function addChannelGoodsStock($goods_id, $sku_id, $num, $channel_id)
    {

        $goods_model = new VslChannelGoodsModel();
        $stock = $goods_model->getInfo(['goods_id' => $goods_id, 'channel_id'=>$channel_id], 'stock');
        $goods_sku_model = new VslChannelGoodsSkuModel();
        $sku_stock = $goods_sku_model->getInfo(['sku_id' => $sku_id, 'channel_id'=>$channel_id], 'stock');
        $goods_model->save(['stock' => $stock['stock']+$num], ['goods_id' => $goods_id, 'channel_id'=>$channel_id]);
        $retval = $goods_sku_model->save(['stock' => $sku_stock['stock']+$num], ['sku_id' => $sku_id, 'channel_id'=>$channel_id]);
        return $retval;
    }
    /**
     * 获取商品属性库存
     * @param unknown $sku_id
     */
    public function getGoodsSkuStock($sku_id){
        $goods_sku_model = new VslGoodsSkuModel();
        $sku_stock = $goods_sku_model->getInfo(['sku_id' => $sku_id], 'stock');
        return $sku_stock['stock'];
    }
    /**
     * 添加商品销售(销售商品使用)
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function addGoodsSales($goods_id, $num)
    {
        $goods_model = new VslGoodsModel();
        $goods_sales = $goods_model->getInfo(['goods_id' => $goods_id], 'sales, real_sales');
//        $retval = $goods_model->save(['sales' => $goods_sales['sales'] + $num, 'real_sales' => $goods_sales['real_sales'] + $num], ['goods_id' => $goods_id]);
        $retval = $goods_model->save(['sales' => $goods_sales['sales'] + $num], ['goods_id' => $goods_id]);
        return $retval;
    }
    /**
     * 添加商品销售(销售商品使用)(门店下单)
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function storeAddGoodsSales($goods_id, $num, $store_id)
    {
        //商品表
//        $goods_model = new VslGoodsModel();
//        $goods_sales = $goods_model->getInfo(['goods_id' => $goods_id], 'sales, real_sales');
//        $retval = $goods_model->save(['sales' => $goods_sales['sales'] + $num, 'real_sales' => $goods_sales['real_sales'] + $num], ['goods_id' => $goods_id]);
//        $retval = $goods_model->save(['sales' => $goods_sales['sales'] + $num], ['goods_id' => $goods_id]);
        //门店商品表
        $store_goods_model = new VslStoreGoodsModel();
        $store_goods_sales = $store_goods_model->getInfo(['goods_id' => $goods_id, 'store_id' => $store_id], 'sales');
        $retval1 = $store_goods_model->save(['sales' => $store_goods_sales['sales'] + $num], ['goods_id' => $goods_id, 'store_id' => $store_id]);
        if ($retval1) {
            return 1;
        }
    }
    /**
     * 添加积分商品销售(销售商品使用)
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function addIntegralGoodsSales($goods_id, $num)
    {
        $goods_model = new VslIntegralGoodsModel();
        $goods_sales = $goods_model->getInfo(['goods_id' => $goods_id], 'sales, real_sales');
        $retval = $goods_model->save(['sales' => $goods_sales['sales'] + $num, 'real_sales' => $goods_sales['real_sales'] + $num], ['goods_id' => $goods_id]);
        return $retval;
    }
    /**
     * 添加门店商品销售(销售商品使用)
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function addStoreGoodsSales($goods_id, $num)
    {
        $goods_model = new VslStoreGoodsModel();
        $goods_sales = $goods_model->getInfo(['goods_id' => $goods_id], 'sales');
        $retval = $goods_model->save(['sales' => $goods_sales['sales'] + $num], ['goods_id' => $goods_id]);
        return $retval;
    }

    /*
     * 添加秒杀销量
     * **/
    public function addSeckillSkuSales($seckill_id, $sku_id, $num)
    {
        $seckill_goods_mdl = new VslSeckGoodsModel();
        $condition['seckill_id'] = $seckill_id;
        $condition['sku_id'] = $sku_id;
        $seckill_sales_list = $seckill_goods_mdl->field('seckill_sales')->where($condition)->find();
        if($seckill_sales_list){
            $seckill_sales_list->seckill_sales = $seckill_sales_list->seckill_sales+$num;
            $seckill_sales_list->save();
        }
    }
    /*
     * 添加预售销量
     * **/
    public function addPresellSkuSales($presell_id, $goods_id, $num)
    {
        $presell_mdl = new VslPresellModel();
        $condition['id'] = $presell_id;
        $condition['goods_id'] = $goods_id;
        $presell_sales_list = $presell_mdl->field('presell_sales')->where($condition)->find();
        if($presell_sales_list){
            $presell_sales_list->presell_sales = $presell_sales_list->presell_sales+$num;
            $presell_sales_list->save();
        }
    }
    /*
     * 减掉预售销量
     * **/
    public function subPresellSkuSales($presell_id, $goods_id, $num)
    {
        $presell_mdl = new VslPresellModel();
        $condition['id'] = $presell_id;
        $condition['goods_id'] = $goods_id;
        $presell_sales_list = $presell_mdl->field('presell_sales')->where($condition)->find();
        if($presell_sales_list){
            $presell_sales_list->presell_sales = $presell_sales_list->presell_sales-$num;
            $presell_sales_list->save();
        }
    }
    /*
     * 添加砍价销量
     * **/
    public function addBargainSkuSales($bargain_id, $goods_id, $num)
    {
        $bargain_mdl = new VslBargainModel();
        $condition['bargain_id'] = $bargain_id;
        $condition['goods_id'] = $goods_id;
        $bargain_sales_list = $bargain_mdl->field('bargain_sales')->where($condition)->find();
        if($bargain_sales_list){
            $bargain_sales_list->bargain_sales = $bargain_sales_list->bargain_sales+$num;
            $bargain_sales_list->save();
        }
    }
    /**
     * 添加渠道商商品销售(销售商品使用)
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function addChannelGoodsSales($goods_id, $num, $channel_id)
    {
        $goods_model = new VslChannelGoodsModel();
        $goods_sales = $goods_model->getInfo(['goods_id' => $goods_id, 'channel_id'=>$channel_id], 'sales, real_sales');
        $retval = $goods_model->save(['sales' => $goods_sales['sales'] + $num, 'real_sales' => $goods_sales['real_sales'] + $num], ['goods_id' => $goods_id, 'channel_id'=>$channel_id]);
        return $retval;
    }
    /**
     * 添加渠道商sku销售(销售商品使用)
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function addChannelSkuSales($sku_id, $num, $channel_id)
    {
        $channel_sku_model = new VslChannelGoodsSkuModel();
        $sku_sales = $channel_sku_model->getInfo(['sku_id' => $sku_id, 'channel_id'=>$channel_id], 'sku_sales');
        $retval = $channel_sku_model->save(['sku_sales' => $sku_sales['sku_sales'] + $num], ['sku_id' => $sku_id, 'channel_id'=>$channel_id]);
        return $retval;
    }
    /**
     * 减渠道商销售(销售商品使用)
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function subChannelSales($goods_id, $sku_id, $num, $channel_id)
    {
        $goods_model = new VslChannelGoodsModel();
        $goods_sales = $goods_model->getInfo(['goods_id' => $goods_id, 'channel_id'=>$channel_id], 'sales, real_sales');
        $goods_model->save(['sales' => $goods_sales['sales'] - $num, 'real_sales' => $goods_sales['real_sales'] - $num], ['goods_id' => $goods_id, 'channel_id'=>$channel_id]);
        $channel_sku_model = new VslChannelGoodsSkuModel();
        $sku_sales = $channel_sku_model->getInfo(['sku_id' => $sku_id, 'channel_id'=>$channel_id], 'sku_sales');
        $retval = $channel_sku_model->save(['sku_sales' => $sku_sales['sku_sales'] - $num], ['sku_id' => $sku_id, 'channel_id'=>$channel_id]);
        return $retval;
    }
    /**
     * 减少商品销售（订单关闭，冲账）
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function subGoodsSales($goods_id, $num)
    {
        $goods_model = new VslGoodsModel();
        $goods_sales = $goods_model->getInfo(['goods_id' => $goods_id], 'sales, real_sales');
        $retval = $goods_model->save(['sales' => $goods_sales['sales'] - $num], ['goods_id' => $goods_id]);
        return $retval;
    }
    /**
     * 减少秒杀销售（订单关闭，冲账）
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function subSeckillGoodsSales($seckill_id, $sku_id, $num)
    {
        $seckill_goods_model = new VslSeckGoodsModel();
        $seckill_sales = $seckill_goods_model->getInfo(['sku_id' => $sku_id, 'seckill_id' => $seckill_id], 'seckill_sales');
        $retval = $seckill_goods_model->save(['seckill_sales' => $seckill_sales['seckill_sales'] - $num], ['sku_id' => $sku_id, 'seckill_id' => $seckill_id]);
        return $retval;
    }
    /**
     * 减少砍价销售（订单关闭，冲账）
     * @param unknown $goods_id
     * @param unknown $num
     */
    public function subBargainGoodsSales($bargain_id, $goods_id, $num)
    {
        $bargain_model = new VslBargainModel();
        $bargain_sales = $bargain_model->getInfo(['goods_id' => $goods_id, 'bargain_id' => $bargain_id], 'bargain_sales');
        $retval = $bargain_model->save(['bargain_sales' => $bargain_sales['bargain_sales'] - $num], ['goods_id' => $goods_id, 'bargain_id' => $bargain_id]);
        return $retval;
    }
    /**
     * 获取一段时间内的商品销售详情
     */
    public function getGoodsSalesInfoList($page_index = 1, $page_size = 0, $condition = '', $order = ''){
        $goods_model = new VslGoodsModel();
        $goods_list = $goods_model->pageQuery($page_index, $page_size, $condition, $order, '*');
        //得到条件内的订单项
        $start_date = strtotime(date('Y-m-d', strtotime('-30 days')));
        $end_date = strtotime(date("Y-m-d H:i:s", time()));
        $order_condition["create_time"] = [[">=",$start_date ],["<=",$end_date ]];
        $order_condition["shop_id"] = $condition["shop_id"];
        $order_goods_list = $this->getOrderGoodsSelect($order_condition);
        //遍历商品
        foreach($goods_list["data"] as $k=>$v){
            $data= array();
            $goods_sales_num = $this->getGoodsSalesNum($order_goods_list, $v["goods_id"]);
            $goods_sales_money = $this->getGoodsSalesMoney($order_goods_list, $v["goods_id"]);
            $data["sales_num"] =  $goods_sales_num;
            $data["sales_money"] =  $goods_sales_money;
            $goods_list["data"][$k]["sales_info"] = $data;
        }
        return $goods_list;
    }  
    /**
     * 一段时间内的商品销售量
     * @param unknown $condition
     */
    public function getGoodsSalesNum($order_goods_list, $goods_id){
        $sales_num = 0;
        foreach( $order_goods_list as $k=>$v){
            if($v["goods_id"] ==$goods_id ){
                $sales_num = $sales_num + $v["num"];
            }
        }
        return $sales_num;
    }
    /**
     * 一段时间内的商品下单金额
     * @param unknown $condition
     */
    public function getGoodsSalesMoney($order_goods_list, $goods_id){
        $sales_money = 0;
        foreach( $order_goods_list as $k=>$v){
            if($v["goods_id"] ==$goods_id ){
                $sales_money = $sales_money + ($v["goods_money"] - $v["adjust_money"]);
            }
        }
        return $sales_money;
    }
    /**
     * 一段时间内的订单项
     * @param unknown $order_condition
     * @return multitype:NULL
     */
    public function getOrderGoodsSelect($order_condition){
        $order_model = new VslOrderModel();
        $order_array = $order_model->where($order_condition)->select();
        $order_goods_list =array();
        foreach($order_array as $t=>$b ){
            $order_item = new VslOrderGoodsModel();
            $item_array = $order_item->where(['order_id' => $b['order_id']])->select();
            $order_goods_list = array_merge($order_goods_list,$item_array);
        }
        return $order_goods_list;
    }
}
