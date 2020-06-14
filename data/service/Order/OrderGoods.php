<?php

namespace data\service\Order;

use addons\bargain\service\Bargain;
use addons\bonus\model\VslOrderBonusModel;
use addons\channel\model\VslChannelGoodsModel;
use addons\channel\model\VslChannelGoodsSkuModel;
use addons\channel\model\VslChannelOrderSkuRecordModel;
use addons\channel\server\Channel;
use addons\coupontype\model\VslCouponModel;
use addons\distribution\model\VslOrderDistributorCommissionModel;
use addons\integral\model\VslIntegralGoodsModel;
use addons\integral\model\VslIntegralGoodsSkuModel;
use addons\integral\service\Integral;
use addons\invoice\server\Invoice as InvoiceService;
use addons\store\server\Store as storeServer;
use data\model\AddonsConfigModel;
use data\model\AlbumPictureModel;
use data\model\VslActivityOrderSkuRecordModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslGoodsSkuPictureModel;
use data\model\VslOrderCalculateModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderGoodsPromotionDetailsModel;
use data\model\VslOrderModel;
use data\model\VslOrderRefundModel;
use data\model\UserModel;
use data\model\VslStoreGoodsModel;
use data\model\VslStoreGoodsSkuModel;
use data\service\BaseService;
use data\service\Goods;
use data\service\GoodsCalculate\GoodsCalculate;
use data\service\Member\MemberAccount;
use data\model\VslOrderGoodsViewModel;
use data\model\VslOrderGoodsExpressModel;
use data\service\Order as OrderService;
use data\model\VslGoodsSpecValueModel;
use addons\seckill\server\Seckill;
use addons\groupshopping\server\GroupShopping;
use think\Db;
use think\Session;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// use think\Model;

/**
 * 订单商品操作类
 */
class OrderGoods extends BaseService
{

    public $order_goods;

    // 订单主表
    function __construct()
    {
        parent::__construct();
        $this->order_goods = new VslOrderGoodsModel();
    }
    /**
     * 新增订单商品数据
     * @param $order_id
     * @param array $sku_lists [array] 商品sku
     * @param int $adjust_money [float] 调整金额
     * @param int $uid
     * @param int $website_id
     * @param int $order_type [int] 订单类型订单类型1为普通2成为微店店主3为微店店主续费4为微店店主升级，5拼团订单，6秒杀订单，7预售订单，8砍价订单，9奖品订单，10兑换订单,11微店订单 12门店订单
     * @param int $pay_money [float] 订单支付费用
     * @param int $order_from
     * @param int $store_id
     * @param $invoice_tax [float] 订单总税费
     * @return int|string
     */
    public function addOrderGoodsNew($order_id, array $sku_lists, $adjust_money = 0, $uid = 0, $website_id = 0, $order_type = 1, $pay_money = 1, $order_from = 1, $store_id=0, $invoice_tax =0)
    {
        
        $err = 0;
        $this->order_goods->startTrans();
        try {
            $order_mdl = new VslOrderModel();
            $order_data = $order_mdl->getInfo(['order_id' => $order_id], '*');
            if ($uid) {
                $this->uid = $uid;
            }else{
                $this->uid = $this->uid;
            }
            if($website_id){
                $this->website_id = $website_id;
            }else{
                $this->website_id = $this->website_id;
            }
            
            $goods = new Goods();
            
            $temp_total_goods_price = array_sum(array_column($sku_lists, 'real_money'));//店铺总商品价格
            $temp_total_shipping_price = array_sum(array_column($sku_lists, 'shipping_fee'));//店铺总运费
            $temp_total_price = $temp_total_goods_price - $temp_total_shipping_price > 0 ? $temp_total_goods_price - $temp_total_shipping_price : 0;
            $temp_goods_nums = count($sku_lists);//店铺总sku数
            $temp_nums = 0;
            $add_up_tax = 0;//累计税费
            $channel_money = 0;//结算给渠道商的金额
            foreach ($sku_lists as $sku_id => $sku_info) {
            //判断后台配置的是哪种库存方式 1:门店独立库存 2:店铺统一库存  默认为1
            $storeServer = new storeServer();
                $stock_type = $storeServer->getStoreSet($sku_info['shop_id'])['stock_type'] ? $storeServer->getStoreSet($sku_info['shop_id'])['stock_type'] : 1;

                $temp_nums ++;
                if($temp_nums == $temp_goods_nums) {//最后一个sku商品
                    $average_tax = $invoice_tax - $add_up_tax;
                } elseif($temp_total_price) {
                    $average_tax = round((($sku_info['real_money'] - $sku_info['shipping_fee'])/$temp_total_price) * $invoice_tax, 2);
                    $add_up_tax += $average_tax;
                }
                
                if($sku_info['order_type'] == 10 && getAddons('integral', $this->website_id, $this->instance_id)){
                    $goods_sku_model = new VslIntegralGoodsSkuModel();
                    $goods_model = new VslIntegralGoodsModel();
                    $fields = 'goods_name,price,goods_type,picture,point_exchange_type,give_point,point_exchange,goods_exchange_type';
                } elseif($order_data['buy_type'] == '2'){
                    $goods_sku_model = new VslChannelGoodsSkuModel();
                    $goods_model = new VslChannelGoodsModel();
                    $fields = 'goods_name,price,goods_type,picture';
                } else {
                    $goods_sku_model = new VslGoodsSkuModel();
                    $goods_model = new VslGoodsModel();
                    $fields = 'goods_name,price,goods_type,picture,point_exchange_type,give_point';
                }
                
                $coupon_model = getAddons('coupontype', $this->website_id) ? new VslCouponModel() : '';
                $goods_sku_info = $goods_sku_model->getInfo([
                    'sku_id' => $sku_info['sku_id']
                ], 'sku_id,goods_id,market_price,cost_price,stock,sku_name,attr_value_items');

                if ($goods_sku_info['attr_value_items']) {
                    $goods_spec_value = new VslGoodsSpecValueModel();
                    $spec_info = [];
                    $sku_spec_info = explode(';', $goods_sku_info['attr_value_items']);
                    foreach ($sku_spec_info as $k_spec => $v_spec) {
                        $spec_value_id = explode(':', $v_spec)[1];
                        $spec_info[$k_spec] = $this->getSpecInfo($spec_value_id,$sku_info['goods_id'], $order_type);
                    }
                    $sku_attr = json_encode($spec_info, JSON_UNESCAPED_UNICODE);
                } else {
                    $sku_attr = '';
                }

                // 如果当前商品有SKU图片，就用SKU图片。没有则用商品主图
                $picture = $this->getSkuPictureBySkuId($goods_sku_info);
                $goods_info = $goods_model->getInfo([
                    'goods_id' => $sku_info['goods_id']
                ], $fields);
                $real_money = $sku_info['discount_price'] * $sku_info['num'];
                $actual_price = $sku_info['discount_price'];//实付单价
                $profile_price = 0;//计算每个商品platform的利润

                if ($sku_info['discount_id'] && $sku_info['promotion_shop_id'] > 0){
                    $profile_price += $sku_info['member_price'] - $sku_info['discount_price'];
                }
                if (!empty($sku_info['coupon_sku_percent_amount']) && getAddons('coupontype', $this->website_id)) {
                    $real_money -= $sku_info['coupon_sku_percent_amount'];
                    // coupon_sku_percent_amount 这个sku在优惠券优惠金额的占比金额
                    $actual_price -= round($sku_info['coupon_sku_percent_amount'] / $sku_info['num'], 2);
                    $coupon_info = $coupon_model::get($sku_info['coupon_id'],['coupon_type']);
                    if ($coupon_info->coupon_type->shop_id > 0 || $coupon_info->coupon_type->shop_range_type==1 ){
                        $profile_price += round($sku_info['coupon_sku_percent_amount'] / $sku_info['num'], 2);
                    }
                }
                if (!empty($sku_info['full_cut_sku_percent']) && !empty($sku_info['full_cut_sku_amount'])) {
                    $real_money -= $sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'];
                    // full_cut_sku_percent 这个sku在满减优惠金额的占比比例 full_cut_sku_amount这个sku满减优惠金额总金额
                    $actual_price -= round($sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'] / $sku_info['num'], 2);
                    if ($sku_info['full_cut_shop_id'] > 0 || $sku_info['full_cut_range']){
                        $profile_price += round($sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'] / $sku_info['num'], 2);
                    }
                }
                $actual_price = ($actual_price > 0) ? $actual_price : 0;
                $real_money = ($real_money > 0) ? $real_money : 0;

                $channel_stock = $sku_info['channel_stock']?:0;//减了上级渠道商多少库存

                if($sku_info['presell_id']){
                    $order_type = 7;
                }
                if($sku_info['seckill_id']){
                    $order_type = 6;
                }
                if($sku_info['bargain_id']){
                    $order_type = 8;
                }
                if($sku_info['deduction_money']>0){
                    $real_money = $real_money - $sku_info['deduction_money'];
                }
                //组装购买主播的商品为多少个。
                $anchor_info = $sku_info['anchor_id'].':'.$sku_info['num'];
                $data_order_sku = array(
                    'order_id' => $order_id,
                    'goods_id' => $sku_info['goods_id'],
                    'goods_name' => $goods_info['goods_name'],
                    'sku_id' => $sku_info['sku_id'],
                    'sku_name' => $goods_sku_info['sku_name'],
                    'real_money' => $real_money + (($sku_info['is_free_shipping']) ? 0 : $sku_info['shipping_fee']),//商品应付总额
                    'actual_price' => $actual_price, //实际单价
                    'price' => $sku_info['price'],// 销售价
                    'market_price' => $goods_sku_info['market_price'],//原价（市场价）
                    'profile_price' => $profile_price, // 用于分销计算利润的单价
                    'num' => $sku_info['num'],
                    'adjust_money' => $adjust_money,
                    'cost_price' => $goods_sku_info['cost_price'],
                    'goods_money' => $sku_info['price'] * $sku_info['num'] - $adjust_money,
                    'goods_picture' => $picture != 0 ? $picture : $goods_info['picture'], // 如果当前商品有SKU图片，就用SKU图片。没有则用商品主图
                    'shop_id' => $sku_info['shop_id'],
                    'website_id' => $this->website_id,
                    'buyer_id' => $this->uid,
                    'goods_type' => $goods_info['goods_type'],
                    'promotion_id' => $sku_info['promote_id'] ?: 0,
                    'promotion_type_id' => $sku_info['promote_id'] ? 2 : 0,
                    'point_exchange_type' => $goods_info['point_exchange_type']?:0,
                    'order_type' => $order_type ?: 1, // 订单类型默认1
                    'give_point' => $sku_info['return_point'] ?: 0,
                    'discount_price' => $sku_info['discount_price'],
                    'member_price' => $sku_info['member_price'],
                    'shipping_fee' => $sku_info['shipping_fee'],
                    'promotion_free_shipping' => $sku_info['is_free_shipping'] ? $sku_info['shipping_fee'] : 0,
                    'channel_info' => $sku_info['channel_id']?:($sku_info['channel_info']?:0),
                    'seckill_id' => $sku_info['seckill_id']?:0,
                    'presell_id' => $sku_info['presell_id']?$sku_info['presell_id']:'',
                    'goods_point' => $sku_info['exchange_point']/$sku_info['num'],
                    'goods_exchange_type' => $goods_info['goods_exchange_type']?:0,
                    'sku_attr' => $sku_attr,
                    //计时/次商品
                    'card_store_id' => $sku_info['card_store_id']?$sku_info['card_store_id']:0,
                    'cancle_times' => $sku_info['cancle_times']?$sku_info['cancle_times']:'',
                    'cart_type' => $sku_info['cart_type']?$sku_info['cart_type']:0,
                    'invalid_time' => $sku_info['invalid_time']?$sku_info['invalid_time']:'',
                    'wx_card_id' => $sku_info['wx_card_id']?$sku_info['wx_card_id']:'',
                    'card_title' => $sku_info['card_title']?$sku_info['card_title']:'',
                    'deduction_money'=>$sku_info['deduction_money']?:0,
                    'deduction_point'=>$sku_info['deduction_point']?:0,
                    'deduction_freight_point'=>$sku_info['deduction_freight_point']?:0,
                    'return_freight_point' => $sku_info['return_freight_point'] ?: 0,
                    'invoice_tax' => $average_tax,
                    'channel_stock' => $channel_stock,
                    'anchor_info' => $anchor_info,
                ); // 积分数量默认0
                //判断平台开启的是什么零售结算节点
                if($channel_stock) {
                    $addons_config_model = new AddonsConfigModel();
                    $value = $addons_config_model->Query(['addons' => 'channel','website_id' => $this->website_id],'value')[0];
                    $value = json_decode($value,true);
                    if($value['settle_type'] == 1) {
                        //以商品售价结算 price
                        $channel_money += $sku_info['price'] * $channel_stock;
                    }elseif ($value['settle_type'] == 2) {
                        //以商品原价结算 market_price
                        $channel_money += $goods_sku_info['market_price'] * $channel_stock;
                    }elseif ($value['settle_type'] == 3) {
                        //以商品实付价结算 discount_price
                        if($sku_info['deduction_money']) {
                            //减去积分抵扣的钱
                            $channel_money += ($actual_price - round($sku_info['deduction_money']/$sku_info['num'],2)) * $channel_stock;
                        }else{
                        $channel_money += $actual_price * $channel_stock;
                        }
                    }
                }
                $order_mdl = new VslOrderModel();
                $order_data = $order_mdl->getInfo(['order_id'=>$order_id], '*');
                if (($order_data['pay_money'] == 0 && getAddons('channel', $this->website_id) && $order_data['buy_type'] == 2) || (getAddons('channel', $this->website_id) && $order_data['buy_type'] == 0 && !empty($sku_info['channel_id']) && $channel_stock)) {//渠道商自提的并且金额为0或者 零售的订单（有减库存就有记录）
                    //将订单商品
                    $channel = new Channel();
                    //进行插入channel_order_sku_record
                    $sku_record_mdl = new VslChannelOrderSkuRecordModel();
                    $sku_record_arr['uid'] = $this->uid;
                    $sku_record_arr['order_id'] = $order_id;
                    $sku_record_arr['order_no'] = $order_data['order_no'];
                    //获取的渠道商信息
                    $condition_channel['c.website_id'] = $this->website_id;
                    $condition_channel['c.uid'] = $this->uid;
                    $channel_info = $channel->getMyChannelInfo($condition_channel);
                    $buyer_channel_id = $sku_info['channel_id']?:($sku_info['channel_info']?:0);
                    $stock_list = $channel->getChannelSkuStore($sku_info['sku_id'], $buyer_channel_id, $this->website_id);
                    $sku_record_arr['my_channel_id'] = $channel_info['channel_id']?:0;
                    $sku_record_arr['channel_info'] = $buyer_channel_id;
                    $sku_record_arr['goods_id'] = $sku_info['goods_id'];
                    $sku_record_arr['sku_id'] = $sku_info['sku_id'];
                    $sku_record_arr['total_num'] = $channel_stock;
                    $sku_record_arr['num'] = $channel_stock;
                    $sku_record_arr['price'] = $sku_info['price'];
                    $sku_record_arr['real_money'] = $actual_price * $channel_stock + (($sku_info['is_free_shipping']) ? 0 : $sku_info['shipping_fee']);//商品应付总额
                    $sku_record_arr['shipping_fee'] = $sku_info['shipping_fee']?:0;
                    $sku_record_arr['channel_purchase_discount'] = $channel_info['purchase_discount']?:0;
//                    $goods_sku_model1 = new VslGoodsSkuModel();
//                    $goods_sku_info = $goods_sku_model1->getInfo([
//                        'sku_id' => $sku_info['sku_id'],
//                    ], '*');
                    $sku_arr = ['channel_id'=>$channel_info['channel_id'], 'sku_id' => $sku_info['sku_id'], 'price'=>0, 'market_price' => 0];
                    $goods->getChannelSkuPrice($sku_arr);
                    $sku_record_arr['platform_price'] = $sku_arr['price'];
                    //我剩余的所有该sku的库存
                    $sku_record_arr['remain_num'] = $stock_list['stock']?:0;
                    $buy_type = $order_data['buy_type']?:3;
                    if($buy_type == 3){//零售的要获取零售的是哪一批次的
                        //根据当前采购的数量去获取 批次id:num:bili
                        $batch_ratio_record = $channel->getPurchaseBatchRatio($buyer_channel_id, $channel_stock, $sku_info['sku_id'], $this->website_id);//p1:采购谁  p2:采购数量
                        $sku_record_arr['batch_ratio_record'] = $batch_ratio_record?:'';
                    }
                    $sku_record_arr['buy_type'] = $buy_type;//自提
                    $sku_record_arr['website_id'] = $this->website_id;
                    $sku_record_arr['create_time'] = time();
//                        $is_record = $sku_record_mdl->where(['order_no' => $order_data['order_no']])->find();
                        if ($buy_type == 3 || $buy_type == 2) {
                        $id = $sku_record_mdl->save($sku_record_arr);
                    }
                    //增加销量
                    $goods_calculate = new GoodsCalculate();
                    if($order_data['pay_money'] == 0){
                        $goods_calculate->addChannelGoodsSales($sku_info['goods_id'], $sku_info['num'], $buyer_channel_id);
                        //增加该渠道商sku的销量
                        $goods_calculate->addChannelSkuSales($sku_info['sku_id'], $sku_info['num'], $buyer_channel_id);
                    }
                }

                if (getAddons('groupshopping', $this->website_id, $this->instance_id)) {
                    $group_server = new GroupShopping();
                    $is_group = $group_server->isGroupGoods($sku_info['goods_id']);
                    if($is_group){
                        $data_order_sku['refund_require_money'] = $data_order_sku['real_money'] + $average_tax;//税费
                    }
                }
                
                $order_goods = new VslOrderGoodsModel();
                $order_goods->save($data_order_sku);
                if ($sku_info['num'] == 0) {
                    $err = 1;
                }
                $channel_id = $sku_info['channel_id']?:($sku_info['channel_info']?:0);
                $goods_calculate = new GoodsCalculate();
                $seckill_status = getAddons('seckill',$this->website_id);
                $order = new VslOrderModel();
                $order_type = $order->getInfo(['order_id'=>$order_id],'order_type')['order_type'];

                if (($channel_id && getAddons('channel', $this->website_id) && $order_data['buy_type'] == 2) || ($channel_id && getAddons('channel', $this->website_id) &&$channel_stock)) {//渠道商
                    //如果是渠道商商品，则减的是渠道商的商品库存，并且加入member的账户余额中
                    $goods_calculate->subChannelGoodsStock($sku_info['goods_id'], $sku_info['sku_id'], $channel_stock?:$sku_info['num'], $channel_id);
                    //渠道商带了秒杀等活动
                    if (!empty($sku_info['seckill_id']) && $seckill_status) {
                        $seckill_server = new Seckill();
                        $seckill_server->subSeckillGoodsStock($sku_info['seckill_id'], $sku_info['sku_id'], $sku_info['num']);
                        //如果是立即购买进来的有存session秒杀id，则去掉
                        if ($_SESSION[$sku_id . 'seckill_id']) {
                            unset($_SESSION[$sku_id . 'seckill_id']);
                        }
                        //加销量
                        if($pay_money == 0 && $order_from != 3){//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                            $goods_calculate = new GoodsCalculate();
                            $goods_calculate->addGoodsSales($sku_info['goods_id'], $sku_info['num']);
                            $goods_calculate->addSeckillSkuSales($sku_info['seckill_id'], $sku_info['sku_id'], $sku_info['num']);
                            if($store_id && $stock_type == 1){
                                $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'],$store_id);
                            }
                        }

                    } elseif (!empty($sku_info['bargain_id']) && getAddons('bargain', $this->website_id, $this->instance_id)) {
                        $bargain_server = new Bargain();
                        $bargain_server->subBargainGoodsStock($sku_info['bargain_id'], $sku_info['num']);
                        //加销量
                        if($pay_money == 0 && $order_from != 3){//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                            $goods_calculate = new GoodsCalculate();
                            $goods_calculate->addGoodsSales($sku_info['goods_id'], $sku_info['num']);
                            if($store_id && $stock_type == 1){
                                $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'],$store_id);
                            }
                        }
                    }
                }
                if (!empty($sku_info['seckill_id']) && $seckill_status && empty($channel_stock)) {//秒杀
                    $seckill_server = new Seckill();
                    $seckill_server->subSeckillGoodsStock($sku_info['seckill_id'], $sku_info['sku_id'], $sku_info['num']);
                    //如果是立即购买进来的有存session秒杀id，则去掉
                    if ($_SESSION[$sku_id . 'seckill_id']) {
                        unset($_SESSION[$sku_id . 'seckill_id']);
                    }
                    //加销量
                    if($pay_money == 0 && $order_from != 3){//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                        $goods_calculate = new GoodsCalculate();
                        $goods_calculate->addGoodsSales($sku_info['goods_id'], $sku_info['num']);
                        $goods_calculate->addSeckillSkuSales($sku_info['seckill_id'], $sku_info['sku_id'], $sku_info['num']);
                        if($store_id && $stock_type == 1){
                            $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'],$store_id);
                        }
                    }
                } elseif (!empty($sku_info['bargain_id']) && getAddons('bargain', $this->website_id, $this->instance_id) && empty($channel_stock)) {
                    $bargain_server = new Bargain();
                    $bargain_server->subBargainGoodsStock($sku_info['bargain_id'], $sku_info['num']);
                    //加销量
                    if($pay_money == 0 && $order_from != 3){//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                        $goods_calculate = new GoodsCalculate();
                        $goods_calculate->addGoodsSales($sku_info['goods_id'], $sku_info['num']);
                        $goods_calculate->addBargainSkuSales($sku_info['bargain_id'], $sku_info['goods_id'], $sku_info['num']);
                        if($store_id && $stock_type == 1){
                            $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'],$store_id);
                        }
                    }
                } elseif ($order_type == 10 && getAddons('integral', $this->website_id, $this->instance_id)) {//兑换订单
                    // 库存减少
                    $integral_server = new Integral();
                    $integral_server->subIntegralGoodsStock($sku_info['goods_id'], $sku_info['sku_id'], $sku_info['num']);
                    //付完款生成订单直接加销量
                    $goods_calculate = new GoodsCalculate();
                    $goods_calculate->addIntegralGoodsSales($sku_info['goods_id'], $sku_info['num']);

                } elseif (!empty($sku_info['presell_id']) && getAddons('presell', $this->website_id, $this->instance_id)) {//预售订单
                    if($pay_money == 0 && $order_from != 3){//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                        $goods_calculate = new GoodsCalculate();
                        $goods_calculate->addGoodsSales($sku_info['goods_id'], $sku_info['num']);
                        if($store_id && $stock_type == 1){
                            $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'],$store_id);
                        }
                    }
                } else {//正常
                    // 库存减少
                    $goods_calculate = new GoodsCalculate();
                    if($store_id && $stock_type == 1){
                        //减门店库存
                        $goods_calculate->storeSubGoodsStock($sku_info['goods_id'], $sku_info['sku_id'], $sku_info['num'],$store_id);
                    }else{
                        //减平台库存
                        $goods_calculate->subGoodsStock($sku_info['goods_id'], $sku_info['sku_id'], $sku_info['num'] - $channel_stock);
                    }
                    if($pay_money == 0 && $order_from != 3){//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                        $goods_calculate = new GoodsCalculate();
                        $goods_calculate->addGoodsSales($sku_info['goods_id'], $sku_info['num']);
                        if($store_id && $stock_type == 1){
                            $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'],$store_id);
                        }
                    }
                    if($store_id && $stock_type == 1){
                        $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'],$store_id);
                    }
                }
            }
            $order_save_data = [
                'channel_money' => $channel_money?:0,
                'normal_money' => $order_data['pay_money'] - $channel_money,
            ];
            $order_mdl -> save($order_save_data,['order_id' => $order_data['order_id']]);
            if ($err == 0) {
                $this->order_goods->commit();
                $order_calculate_data = [];
                foreach ($sku_lists as $sku_id => $sku_info) {
                    $order_goods = new VslOrderGoodsModel();
                    $order_goods_info = $order_goods->getInfo(['sku_id'=>$sku_info['sku_id'],'order_id'=> $order_id,'goods_id' => $sku_info['goods_id']]);
                    if($sku_info['channel_id'] && $order_data['buy_type'] == 2){
                        continue;
                    }
                    $had_insert = false;
                    if($order_type!=2 && $order_type!=3 && $order_type!=4 && $order_type!=10){
                        $microshop_status = getAddons('microshop', $website_id);
                        if($microshop_status && $had_insert === false){//微店计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $distribution_status = getAddons('distribution', $website_id);
                        if($distribution_status == 1 && $had_insert === false){
//                        hook('orderCommissionCalculate', ['order_id' => $order_id, 'order_goods_id' => $order_goods_info['order_goods_id'],'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功佣金计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $global_status= getAddons('globalbonus', $website_id);
                        if($global_status == 1 && $had_insert === false){
//                        hook('orderGlobalBonusCalculate', ['order_id' => $order_id,'order_goods_id' => $order_goods_info['order_goods_id'], 'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功全球分红计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $area_status= getAddons('areabonus', $website_id);
                        if($area_status == 1 && $had_insert === false) {
//                        hook('orderAreaBonusCalculate', ['order_id' => $order_id, 'order_goods_id' => $order_goods_info['order_goods_id'], 'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功区域分红计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $team_status= getAddons('teambonus', $website_id);
                        if($team_status == 1 && $had_insert === false) {
//                        hook('orderTeamBonusCalculate', ['order_id' => $order_id, 'order_goods_id' => $order_goods_info['order_goods_id'], 'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功团队分红计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                    }
                }
                if(!empty($order_calculate_data)){
                    $order_calculate_model = new VslOrderCalculateModel();
                    $order_calculate_model->saveAll($order_calculate_data);
                    if(class_exists('\swoole_client')){
                        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
                        $ret = $client->connect("127.0.0.1", 9501);
                        $task_path = 'http://'. $_SERVER["HTTP_HOST"].'/task/load_task_five';
                        if($ret){
                            $data = json_encode(['url'=> $task_path, 'website_id' => $this->website_id]);
                            $client->send($data);
                        }
                    }
                }
                return 1;
            } elseif ($err == 1) {
                $this->order_goods->rollback();
                return ORDER_GOODS_ZERO;
            }
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return $e->getMessage();
        }
    }
    public function addOrderGoodsForStore($order_id, array $sku_lists, $adjust_money = 0, $uid = 0, $website_id = 0,$order_type=1, $pay_money = 1, $order_from=1, $invoice_tax =0, $store_id)
    {
        $this->order_goods->startTrans();
        try {
            if($uid){
                $this->uid = $uid;
            }else{
                $this->uid = $this->uid;
            }
            if($website_id){
                $this->website_id = $website_id;
            }else{
                $this->website_id = $this->website_id;
            }
            $err = 0;
            $temp_total_goods_price = array_sum(array_column($sku_lists, 'real_money'));//店铺总商品价格
            $temp_total_shipping_price = array_sum(array_column($sku_lists, 'shipping_fee'));//店铺总运费
            $temp_total_price = $temp_total_goods_price - $temp_total_shipping_price > 0 ? $temp_total_goods_price - $temp_total_shipping_price : 0;
            $temp_goods_nums = count($sku_lists);//店铺总sku数
            $temp_nums = 0;
            $add_up_tax = 0;//累计税费
            foreach ($sku_lists as $sku_id => $sku_info) {
            //判断后台配置的是哪种库存方式 1:门店独立库存 2:店铺统一库存  默认为1
            $storeServer = new storeServer();
                $stock_type = $storeServer->getStoreSet($sku_info['shop_id'])['stock_type'] ? $storeServer->getStoreSet($sku_info['shop_id'])['stock_type'] : 1;

                $temp_nums ++;
                if($temp_nums == $temp_goods_nums) {//最后一个sku商品
                    $average_tax = $invoice_tax - $add_up_tax;
                } else {
                    $average_tax = round((($sku_info['real_money'] - $sku_info['shipping_fee'])/$temp_total_price) * $invoice_tax, 2);
                    $add_up_tax += $average_tax;
                }
                if($stock_type == 1) {
                $goods_sku_model = new VslStoreGoodsSkuModel();
                $goods_model = new VslStoreGoodsModel();
                $fields = 'goods_name,price,picture';
                $goods_sku_info = $goods_sku_model->getInfo([
                        'sku_id' => $sku_info['sku_id'],
                        'store_id' => $store_id
                    ], 'sku_id,goods_id,market_price,stock,sku_name,attr_value_items');
                }elseif ($stock_type == 2) {
                    $goods_sku_model = new VslGoodsSkuModel();
                    $goods_model = new VslGoodsModel();
                    $fields = 'goods_name,price,picture';
                    $goods_sku_info = $goods_sku_model->getInfo([
                    'sku_id' => $sku_info['sku_id']
                ], 'sku_id,goods_id,market_price,stock,sku_name,attr_value_items');
                }

                if ($goods_sku_info['attr_value_items']) {
                    $spec_info = [];
                    $sku_spec_info = explode(';', $goods_sku_info['attr_value_items']);
                    foreach ($sku_spec_info as $k_spec => $v_spec) {
                        $spec_value_id = explode(':', $v_spec)[1];
                        $spec_info[$k_spec] = $this->getSpecInfo($spec_value_id,$sku_info['goods_id'], $order_type);
                    }
                    $sku_attr = json_encode($spec_info, JSON_UNESCAPED_UNICODE);
                } else {
                    $sku_attr = '';
                }

                // 如果当前商品有SKU图片，就用SKU图片。没有则用商品主图
                $picture = $this->getSkuPictureBySkuId($goods_sku_info);
                $goods_info = $goods_model->getInfo([
                    'goods_id' => $sku_info['goods_id']
                ], $fields);
                $real_money = $sku_info['discount_price'] * $sku_info['num'];
                $actual_price = $sku_info['discount_price'];//实付单价
                $profile_price = 0;//计算每个商品platform的利润
                if($sku_info['deduction_money']>0){
                    $real_money = $real_money - $sku_info['deduction_money'];
                }
                $data_order_sku = array(
                    'order_id' => $order_id,
                    'goods_id' => $sku_info['goods_id'],
                    'goods_name' => $goods_info['goods_name'] ? : $sku_info['goods_name'],
                    'sku_id' => $sku_info['sku_id'],
                    'sku_name' => $goods_sku_info['sku_name'],
                    'real_money' => $real_money,//商品应付总额
                    'actual_price' => $actual_price, //实际单价
                    'price' => $sku_info['price'],// 销售价
                    'market_price' => $goods_sku_info['market_price'],//原价（市场价）
                    'profile_price' => $profile_price, // 用于分销计算利润的单价
                    'num' => $sku_info['num'],
                    'adjust_money' => $adjust_money,
                    'cost_price' => $goods_sku_info['cost_price'],
                    'goods_money' => $sku_info['price'] * $sku_info['num'] - $adjust_money,
                    'goods_picture' => $picture != 0 ? $picture : $goods_info['picture'], // 如果当前商品有SKU图片，就用SKU图片。没有则用商品主图
                    'shop_id' => $sku_info['shop_id'],
                    'website_id' => $this->website_id,
                    'buyer_id' => $this->uid,
                    'goods_type' => 1,
                    'promotion_id' => $sku_info['promote_id'] ?: 0,
                    'promotion_type_id' => $sku_info['promote_id'] ? 2 : 0,
                    'point_exchange_type' => 0,
                    'order_type' => $order_type?:1, // 订单类型默认1
                    'give_point' => $sku_info['return_point']?:0,
                    'discount_price' => $sku_info['discount_price'],
                    'member_price' => $sku_info['discount_price'],
                    'shipping_fee' => $sku_info['shipping_fee'],
                    'promotion_free_shipping' => $sku_info['is_free_shipping'] ? $sku_info['shipping_fee'] : 0,
                    'channel_info' => $sku_info['channel_id']?:($sku_info['channel_info']?:0),
//                    'channel_weight' => $channel_weight?:0,
                    'seckill_id' => $sku_info['seckill_id']?:0,
                    'presell_id' => $sku_info['presell_id']?$sku_info['presell_id']:'',
                    'goods_point' => 0,
                    'goods_exchange_type' => 0,
                    'sku_attr' => $sku_attr,
                    //计时/次商品
                    'card_store_id' => $sku_info['card_store_id']?$sku_info['card_store_id']:0,
                    'cancle_times' => $sku_info['cancle_times']?$sku_info['cancle_times']:'',
                    'cart_type' => $sku_info['cart_type']?$sku_info['cart_type']:0,
                    'invalid_time' => $sku_info['invalid_time']?$sku_info['invalid_time']:'',
                    'wx_card_id' => $sku_info['wx_card_id']?$sku_info['wx_card_id']:'',
                    'card_title' => $sku_info['card_title']?$sku_info['card_title']:'',
                    'deduction_money'=>$sku_info['deduction_money']?:0,
                    'deduction_point'=>$sku_info['deduction_point']?:0,
                    'deduction_freight_point'=>$sku_info['deduction_freight_point']?:0,
                    'return_freight_point'=>$sku_info['return_freight_point']?:0,
                    'invoice_tax' => $average_tax
                ); // 积分数量默认0
                
                $order_goods = new VslOrderGoodsModel();
                $order_goods->save($data_order_sku);
                if ($sku_info['num'] == 0) {
                    $err = 1;
                }
                $order = new VslOrderModel();
                $order_type = $order->getInfo(['order_id'=>$order_id],'order_type')['order_type'];

                $goods_calculate = new GoodsCalculate();
                if($stock_type == 1) {
                $storeServer = new \addons\store\server\Store();
                $storeServer->subStoreGoodsStock($sku_info['goods_id'], $sku_info['sku_id'], $sku_info['num']);
                //付完款生成订单直接加销量
                $goods_calculate->addStoreGoodsSales($sku_info['goods_id'], $sku_info['num']);
                }elseif ($stock_type == 2) {
                    //减平台库存
                    $goods_calculate->subGoodsStock($sku_info['goods_id'], $sku_info['sku_id'], $sku_info['num']);                                  //加平台销量
                    $goods_calculate->addGoodsSales($sku_info['goods_id'], $sku_info['num']);
                }
            }
            if ($err == 0) {
                $this->order_goods->commit();
                $order_calculate_data = [];
                foreach ($sku_lists as $sku_id => $sku_info) {
                    $order_goods = new VslOrderGoodsModel();
                    $order_goods_info = $order_goods->getInfo(['sku_id'=>$sku_info['sku_id'],'order_id'=> $order_id,'goods_id' => $sku_info['goods_id']]);
                    $had_insert = false;
                    if($order_type!=2 && $order_type!=3 && $order_type!=4 && $order_type!=10){
                        $microshop_status = getAddons('microshop', $website_id);
                        if($microshop_status && $had_insert === false){//微店计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $distribution_status = getAddons('distribution', $website_id);
                        if($distribution_status == 1 && $had_insert === false){
//                        hook('orderCommissionCalculate', ['order_id' => $order_id, 'order_goods_id' => $order_goods_info['order_goods_id'],'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功佣金计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $global_status= getAddons('globalbonus', $website_id);
                        if($global_status == 1 && $had_insert === false){
//                        hook('orderGlobalBonusCalculate', ['order_id' => $order_id,'order_goods_id' => $order_goods_info['order_goods_id'], 'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功全球分红计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $area_status= getAddons('areabonus', $website_id);
                        if($area_status == 1 && $had_insert === false) {
//                        hook('orderAreaBonusCalculate', ['order_id' => $order_id, 'order_goods_id' => $order_goods_info['order_goods_id'], 'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功区域分红计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $team_status= getAddons('teambonus', $website_id);
                        if($team_status == 1 && $had_insert === false) {
//                        hook('orderTeamBonusCalculate', ['order_id' => $order_id, 'order_goods_id' => $order_goods_info['order_goods_id'], 'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功团队分红计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                    }
                }
                if(!empty($order_calculate_data)){
                    $order_calculate_model = new VslOrderCalculateModel();
                    $order_calculate_model->saveAll($order_calculate_data);
                    if(class_exists('\swoole_client')){
                        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
                        $ret = $client->connect("127.0.0.1", 9501);
                        $task_path = 'http://'. $_SERVER["HTTP_HOST"].'/task/load_task_five';
                        if($ret){
                            $data = json_encode(['url'=> $task_path, 'website_id' => $this->website_id]);
                            $client->send($data);
                        }
                    }
                }
                return 1;
            } elseif ($err == 1) {
                $this->order_goods->rollback();
                return ORDER_GOODS_ZERO;
            }
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 根据商品规格信息查询SKU主图片
     *
     * @param 商品规格信息 $goods_sku_info
     * @return 0：没有查询到商品SKU图片，!0:查询到了商品SKU图片
     */
    public function getSkuPictureBySkuId($goods_sku_info)
    {
        $picture = 0;
        $attr_value_items = $goods_sku_info['attr_value_items'];
        if (!empty($attr_value_items)) {
            $attr_value_items_array = explode(";", $attr_value_items);
            foreach ($attr_value_items_array as $k => $v) {
                $temp_array = explode(":", $v); // 规格：规格值
                $condition['goods_id'] = $goods_sku_info['goods_id'];
                $condition['spec_id'] = $temp_array[0]; // 规格
                $condition['spec_value_id'] = $temp_array[1]; // 规格值
                $condition['shop_id'] = $this->instance_id;
                $goods_sku_picture_model = new VslGoodsSkuPictureModel();
                $sku_img_array = $goods_sku_picture_model->getInfo($condition, 'sku_img_array');
                if (!empty($sku_img_array['sku_img_array'])) {
                    $temp = explode(",", $sku_img_array['sku_img_array']);
                    $picture = $temp[0];
                    break;
                }
            }
        }

        return $picture;
    }

    /**
     * 订单项发货
     *
     * @param unknown $order_goods_id_array
     *            ','隔开
     */
    public function orderGoodsDelivery($order_id, $order_goods_id_array)
    {
        $this->order_goods->startTrans();
        try {
            $order_goods_id_array = explode(',', $order_goods_id_array);
            foreach ($order_goods_id_array as $k => $order_goods_id) {
                $order_goods_id = (int)$order_goods_id;
                $data = array(
                    'shipping_status' => 1,
                    'order_status' => 2
                );
                $order_goods = new VslOrderGoodsModel();
                $retval = $order_goods->save($data, [
                    'order_goods_id' => $order_goods_id
                ]);
            }

            $order = new Order();
            $order->orderDoDelivery($order_id);
            $this->order_goods->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return $e->getMessage();
        }

        return $retval;
    }

    /**
     * 买家退款申请
     *
     * @param unknown $order_id
     *            订单ID
     * @param unknown $order_goods_id_array
     *            订单项ID (','隔开)
     * @param unknown $refund_type
     * @param unknown $refund_require_money
     * @param unknown $refund_reason
     * @return number|Exception|Ambigous <number, \think\false>
     */
    public function orderGoodsRefundAskfor($order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason)
    {

        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        // 获取real_money 以计算每个的退款占比
        $order_goods_model = new VslOrderGoodsModel();
        $order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $order_goods_id]]);
        $total_real_money = 0;
        $order_goods_num_relation = [];
        foreach ($order_goods_info as $k => $v) {
            if (!in_array($v['refund_status'], [0, -3])) {
                // 未申请(0)和被拒绝该次(-3)可以申请售后
                return ['code' => -1, 'message' => '不可以重复申请售后'];
            }
            $v['real_money'] += $v['invoice_tax'];// 发票税费 by sgw
            $total_real_money += $v['real_money'];
            $order_goods_num_relation[$v['order_goods_id']] = $v['real_money'];
        }
        // 订单表更新售后信息

        // 订单商品表更新售后信息
        $this->order_goods->startTrans();
        try {
//            $order_model = new VslOrderModel();
//            $order_model->save(['refund_money' => $refund_require_money], ['order_id' => $order_id]);

            $status_id = OrderStatus::getRefundStatus()[1]['status_id'];
            // 订单项退款操作
            $count = count($order_goods_id);
            $retu = 0;
            foreach ($order_goods_id as $k => $v) {
                $order_goods = new VslOrderGoodsModel();
                if ($k == $count-1){
                    //最后一次  该商品获取退款金额剩余所有
                    $order_goods_data = array(
                        'refund_status' => $status_id,
                        'refund_time' => time(),
                        'refund_type' => $refund_type,
                        'refund_require_money' => ($total_real_money == 0) ? 0 : round($refund_require_money - $retu, 2),
                        'refund_reason' => $refund_reason,
                    );
                }else{
                    $order_goods_data = array(
                        'refund_status' => $status_id,
                        'refund_time' => time(),
                        'refund_type' => $refund_type,
                        'refund_require_money' => ($total_real_money == 0) ? 0 : round($refund_require_money * $order_goods_num_relation[$v] / $total_real_money, 2),
                        'refund_reason' => $refund_reason,
                    );
                }
                $retu += $order_goods_data['refund_require_money'];
                $order_goods->save($order_goods_data, [
                    'order_goods_id' => $v
                ]);
            }

            // 退款记录
            $this->addOrderRefundAction($order_goods_id, $status_id, 1, $this->uid);
            // 订单退款操作
            $order = new Order();
            $res = $order->orderGoodsRefundFinish($order_id);

            $this->order_goods->commit();
            return ['code' => 1, 'message' => '操作成功'];
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }

    /**
     * 买家取消退款
     */
    public function orderGoodsCancel($order_id, $order_goods_id)
    {
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $order_goods = new VslOrderGoodsModel();
        $order_goods_info = $order_goods::all(['order_goods_id' => ['IN', $order_goods_id]]);
        foreach ($order_goods_info as $k => $v) {
            if (!in_array($v['refund_status'], [1])) {
                // 已申请(1)可以取消售后
                return ['code' => -1, 'message' => '订单状态已改变，不可取消售后'];
            }
        }
        $this->order_goods->startTrans();
        try {
            $status_id = OrderStatus::getRefundStatus()[0]['status_id'];


            // 订单项退款操作
            $order_goods_data = array(
                'refund_status' => $status_id
            );
            $res = $order_goods->save($order_goods_data, [
                'order_goods_id' => ['IN', $order_goods_id],
                'buyer_id' => $this->uid
            ]);

            // 退款记录
            $this->addOrderRefundAction($order_goods_id, $status_id, 1, $this->uid);
            // 订单退款操作
            $order = new Order();
            $res = $order->orderGoodsRefundFinish($order_id,$order_goods_id);

            $this->order_goods->commit();
            return ['code' => 1, 'message' => '已取消售后'];
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 买家退货
     */
    public function orderGoodsReturnGoods($order_id, $order_goods_id, $refund_shipping_company, $refund_shipping_code)
    {
        Db::startTrans();
        try {
            $status_id = OrderStatus::getRefundStatus()[3]['status_id'];
            $order_goods = new VslOrderGoodsModel();
            // 订单项退款操作
            $data['refund_status'] = $status_id;
            $data['refund_shipping_company'] = $refund_shipping_company;
            $data['refund_shipping_code'] = $refund_shipping_code;
            $order_goods->save($data, ['order_goods_id' => ['IN', $order_goods_id]]);

            // 退款记录
            $this->addOrderRefundAction($order_goods_id, $status_id, 1, $this->uid);
            Db::commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            Db::rollback();
            return $e->getMessage();
        }
    }

    /**
     * 卖家同意买家退款申请
     */
    public function orderGoodsRefundAgree($order_id, $order_goods_id,$return_id=0)
    {
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $this->order_goods->startTrans();
        try {
            // 退款信息
            $orderGoodsInfo = VslOrderGoodsModel::all(['order_goods_id' => ['IN', $order_goods_id]]);
            $refund_type = reset($orderGoodsInfo)['refund_type'];

            if ($refund_type == 1) { // 仅退款
                $status_id = OrderStatus::getRefundStatus()[4]['status_id'];
            } else { // 退货退款
                $status_id = OrderStatus::getRefundStatus()[2]['status_id'];
            }
            // 用于之前 service/order 售后
            Session::set('refund_type', $refund_type);

            // 订单项退款操作
            $order_goods = new VslOrderGoodsModel();
            $order_goods_data = array(
                'refund_status' => $status_id,
                'return_id'=>$return_id
            );
            $res = $order_goods->save($order_goods_data, [
                'order_goods_id' => ['IN', $order_goods_id]
            ]);

            // 退款记录

            $this->addOrderRefundAction($order_goods_id, $status_id, 2, $this->uid);
            $this->order_goods->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 卖家永久拒绝本退款
     */
    public function orderGoodsRefuseForever($order_id, $order_goods_id, $reason = '')
    {
        $this->order_goods->startTrans();
        try {

            $status_id = OrderStatus::getRefundStatus()[-1]['status_id'];
            // 订单项退款操作
            $order_goods = new VslOrderGoodsModel();
            $order_goods_data = array(
                'refund_status' => $status_id
            );
            $res = $order_goods->save($order_goods_data, [
                'order_goods_id' => $order_goods_id
            ]);

            // 退款记录

            $this->addOrderRefundAction($order_goods_id, $status_id, 2, $this->uid, $reason);
            // 订单恢复正常操作
            $order = new Order();
            $res = $order->orderGoodsRefundFinish($order_id,$order_goods_id);

            $this->order_goods->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return $e;
        }
    }

    /**
     * 卖家拒绝本次退款
     */
    public function orderGoodsRefuseOnce($order_id, $order_goods_id, $reason = '')
    {
        $this->order_goods->startTrans();
        try {
            foreach ($order_goods_id as $v) {
                $status_id = OrderStatus::getRefundStatus()[-3]['status_id'];

                // 订单项退款操作
                $order_goods = new VslOrderGoodsModel();
                $order_goods_data = array(
                    'refund_status' => $status_id
                );
                $res = $order_goods->save($order_goods_data, [
                    'order_goods_id' => $v
                ]);
            }

            // 退款日志
            $this->addOrderRefundAction($order_goods_id, $status_id, 2, $this->uid, $reason);
            // 订单恢复正常操作
            $order = new Order();
            $res = $order->orderGoodsRefundFinish($order_id,$order_goods_id);
            $this->order_goods->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            //var_dump($e->getMessage());
            return $e;
        }
    }

    /** 
     * 卖家确认收货
     */
    public function orderGoodsConfirmRecieve($order_id, $order_goods_id)
    {
        
        $this->order_goods->startTrans();
        try {
            $status_id = OrderStatus::getRefundStatus()[4]['status_id'];

            // 订单项退款操作
            $order_goods = new VslOrderGoodsModel();
            $order_goods_data = array(
                'refund_status' => $status_id
            );
            $res = $order_goods->save($order_goods_data, [
                'order_goods_id' => ['IN',$order_goods_id]
            ]);
            if(getAddons('channel', $this->website_id)){
                //如果是渠道商订单则减去冻结金额。
                $channel = new Channel();
                
                $res = $channel->deleteMemberFreezingAccountBalance($order_id,$order_goods_id, $this->website_id); 
               
               
            }
            $order_goods_model = new VslOrderGoodsModel();
            $order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $order_goods_id]]);

            foreach ($order_goods_info as $v) {
                
                $this->addOrderRefundAction($v->order_goods_id, $status_id, 2, $this->uid);
            }
            // 退款记录
            $this->order_goods->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 卖家确认退款
     *
     * @param 订单id $order_id
     * @param 订单项id $order_goods_id
     * @param 实际退款金额 $refund_real_money
     * @param 退款余额 $refund_balance_money
     * @param 退款交易号 $refund_trade_no
     * @param 退款方式（1：微信，2：支付宝，10：线下） $refund_way
     * @param 备注 $refund_remark
     * @return number
     */
    public function orderGoodsConfirmRefund($order_id, $order_goods_id, $refund_real_money, $refund_balance_money, $refund_trade_no, $refund_way, $refund_remark)
    {
        $order_goods = VslOrderGoodsModel::get($order_goods_id);
        $order_goods->startTrans();
        try {
            $status_id = OrderStatus::getRefundStatus()[5]['status_id'];

            // 订单项退款操作
            $order_goods->refund_status = $status_id;
            $order_goods->refund_real_money = $refund_real_money; // 退款金额
            $order_goods->refund_balance_money = $refund_balance_money; // 商品最大可用退款余额(商品应付金额)
            $res = $order_goods->save();
            // 执行余额账户修正
            // 退款记录
            $this->addOrderRefundAction($order_goods_id, $status_id, 2, $this->uid);
            $order_model = new VslOrderModel();

            // 订单添加退款金额、余额
            $order_info = $order_model->getInfo([
                'order_id' => $order_id
            ], '*');

            $order = new Order();
            // 添加退款帐户记录
            if (empty($refund_remark)) {
                $remark = "订单编号:" . $order_info['order_no'] . "，退款方式为:[" . OrderStatus::getPayType($refund_way) . "]，退款金额:" . $refund_real_money . "元，退款余额：" . $refund_balance_money . "元";
            } else {
                $remark = $refund_remark;
            }
            $order->addOrderRefundAccountRecords($order_goods_id, $refund_trade_no, $refund_real_money, $refund_way, $order_info['buyer_id'], $remark);

            $order_model->save([
                'refund_money' => $order_info['refund_money'] + $refund_real_money,
                'refund_balance_money' => $order_info['refund_balance_money'] + $refund_balance_money
            ], [
                'order_id' => $order_id
            ]);
            //退款退回库存、销量
            $goods_calculate = new GoodsCalculate();
            if(getAddons('seckill', $order_info['website_id'], $this->instance_id)){
                //判断是否是秒杀商品，是的话加秒杀活动库存
                $seckill_server = new Seckill();
                $order_seckill_list = $seckill_server->orderSkuIsSeckill($order_id, $order_goods['sku_id']);
            }

            //渠道商
            if ($order_goods['channel_info'] && $order_goods['channel_stock']) {
                //加库存
                $goods_calculate->addChannelGoodsStock($order_goods['goods_id'], $order_goods['sku_id'], $order_goods['channel_stock'], $order_goods['channel_info']);
                //减销量
                $goods_calculate->subChannelSales($order_goods['goods_id'], $order_goods['sku_id'], $order_goods['channel_stock'], $order_goods['channel_info']);
            }
            if($order_seckill_list){
                $seckill_id = $order_seckill_list['promotion_id'];
                //加秒杀活动库存
                $seckill_server->addSeckillGoodsStock($seckill_id, $order_goods['sku_id'], $order_goods['num']);
                $goods_calculate->subSeckillGoodsSales($seckill_id, $order_goods['sku_id'], $order_goods['num']);
            } elseif ($order_info['bargain_id'] && getAddons('bargain', $order_info['website_id'], $this->instance_id)) {//判断是否是砍价的
                $bargain_server = new Bargain();
                $bargain_server->addBargainGoodsStock($order_info['bargain_id'],$order_goods['num']);
                $goods_calculate->subBargainGoodsSales($order_info['bargain_id'], $order_goods['goods_id'], $order_goods['num']);
            }elseif($order_goods['presell_id'] && getAddons('presell', $order_info['website_id'], $this->instance_id)){
                //去掉购买的记录
                $presell_cond['activity_id'] = $order_goods['presell_id'];
                $presell_cond['sku_id'] = $order_goods['sku_id'];
                $presell_cond['buy_type'] = 4;
                $presell_cond['website_id'] = $order_info['website_id'];
                $aosr_mdl = new VslActivityOrderSkuRecordModel();
                $aosr_list = $aosr_mdl->where($presell_cond)->find();
                $aosr_list->num = $aosr_list->num - $order_goods['num'];
                $aosr_list->save();
                $goods_calculate->subPresellSkuSales($order_goods['presell_id'], $order_goods['goods_id'], $order_goods['num']);
            }else{
                if($order_info['shipping_type'] == 1){
                    //快递配送
                $goods_model = new VslGoodsModel();
                $goods_sku_model = new VslGoodsSkuModel();
                $sku_stock = $goods_sku_model->getInfo(['sku_id' => $order_goods['sku_id']], 'stock')['stock'];
                $data_goods_sku = array(
                        'stock' => $sku_stock + $order_goods['num'] - $order_goods['channel_stock']
                );
                $goods_sku_model->save($data_goods_sku, [
                    'sku_id' => $order_goods['sku_id']
                ]);
                $count = $goods_sku_model->getSum([
                    'goods_id' => $order_goods['goods_id']
                ], 'stock');
                // 商品库存增加
                $goods_model->save([
                    'stock' => $count
                ], [
                    'goods_id' => $order_goods['goods_id']
                ]);
                //减掉商品销量
                    $goods_calculate->subGoodsSales($order_goods['goods_id'], $order_goods['num'] - $order_goods['channel_stock']);
                }elseif ($order_info['shipping_type'] == 2){
                    //线下自提
                    $store_goods_model = new VslStoreGoodsModel();
                    $store_goods_sku_model = new VslStoreGoodsSkuModel();
                    //门店sku表加库存
                    $sku_stock = $store_goods_sku_model->getInfo(['sku_id' => $order_goods['sku_id'],'store_id'=>$order_info['store_id']], 'stock')['stock'];
                    $data_sku_stock = array(
                        'stock' => $sku_stock + $order_goods['num']
                    );
                    $store_goods_sku_model->save($data_sku_stock, [
                        'sku_id' => $order_goods['sku_id'],'store_id'=>$order_info['store_id']
                    ]);
                    //门店商品表加库存
                    $goods_stock = $store_goods_model->getInfo(['goods_id' => $order_goods['goods_id'],'store_id'=>$order_info['store_id']], 'stock')['stock'];
                    $data_goods_stock = [
                        'stock' => $goods_stock + $order_goods['num']
                    ];
                    $store_goods_model->save($data_goods_stock, [
                        'goods_id' => $order_goods['goods_id'],'store_id'=>$order_info['store_id']
                    ]);
                    //减平台上的销量
                    $goods_calculate->subGoodsSales($order_goods['goods_id'], $order_goods['num']);
                    //减门店的销量
                    $goods_sales = $store_goods_model->getInfo(['goods_id' => $order_goods['goods_id'],'store_id'=>$order_info['store_id']], 'sales')['sales'];
                    $data_goods_sales = [
                        'sales' => $goods_sales - $order_goods['num']
                    ];
                    $store_goods_model->save($data_goods_sales, [
                        'goods_id' => $order_goods['goods_id'],'store_id'=>$order_info['store_id']
                    ]);
                }
            }
            $this->orderGoodsRefundExt($order_id, $order_goods_id, $refund_balance_money); 
            //移除该笔订单商品的佣金记录
            // if($order_goods_id){
            //     $order_commission = new VslOrderDistributorCommissionModel();
            //     $order_commission->where(['order_goods_id' => $order_goods_id])->update(['commissionA' => 0,'commissionB' => 0,'commissionC' => 0,'commission' => 0,'pointA' => 0,'pointB' => 0,'pointC' => 0,'point' => 0,]);
            // }
            //变更冻结佣金
            
            // 订单恢复正常操作
            $order->orderGoodsRefundFinish($order_id);
            $order_goods->commit();

            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $order_goods->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 订单项目退款处理
     *
     * @param unknown $order_id
     * @param unknown $order_goods_id
     * @param unknown $refund_balance_money
     */
    private function orderGoodsRefundExt($order_id, $order_goods_id, $refund_balance_money)
    {
        $order_model = new VslOrderModel();
        $order_info = $order_model->getInfo([
            'order_id' => $order_id
        ], '*');
        $member_account = new MemberAccount();
        if ($refund_balance_money > 0) {
            $member_account->addMemberAccountData(2, $order_info['buyer_id'], 1, $refund_balance_money, 2, $order_id, '订单退款');
        }
    }

    /**
     * 添加订单退款日志
     *
     * @param unknown $order_goods_id
     * @param unknown $refund_status
     * @param unknown $action
     * @param unknown $action_way
     * @param unknown $uid
     * @param unknown $user_name
     * @param string $reason
     */
    public function addOrderRefundAction($order_goods_id, $refund_status_id, $action_way, $uid, $reason = '')
    {
        $order_goods_id = is_array($order_goods_id) ? $order_goods_id : explode(',', $order_goods_id);
        $refund_status_name = $refund_status = OrderStatus::getRefundStatus()[$refund_status_id]['status_name'];
        $user = new UserModel();
        $user_name = $user->getInfo([
            'uid' => $uid
        ], 'user_name');
        $order_refund = new VslOrderRefundModel();
        $data_refund = [];
        foreach ($order_goods_id as $k => $v) {
            $data_refund[] = array(
                'order_goods_id' => $v,
                'refund_status' => $refund_status_id,
                'action' => $refund_status_name,
                'action_way' => $action_way,
                'action_userid' => $uid,
                'action_username' => $user_name['user_name'],
                'action_time' => time(),
                'reason' => $reason,
            );
        }
        $retval = $order_refund->saveAll($data_refund);
        return $retval;
    }

    /**
     * 订单项商品价格调整
     * @param int $order_id
     * @param string $order_goods_id_adjust_array
     *            订单项数列 order_goods_id,adjust_money;order_goods_id,adjust_money
     * @param float $order_adjust_money 统计订单金额变化
     * @param $float $shipping_fee
     */
    public function orderGoodsAdjustMoney($order_id, $order_goods_id_adjust_array, &$order_adjust_money, $shipping_fee)
    {
        $this->order_goods->startTrans();
        try {
            $order_goods_id_adjust_array = explode(';', $order_goods_id_adjust_array);
            if (!empty($order_goods_id_adjust_array)) {
                $order_commission = new VslOrderDistributorCommissionModel();
                if(getAddons('globalbonus', $this->website_id) || getAddons('areabonus', $this->website_id) || getAddons('teambonus', $this->website_id)){
                        $order_bonus = new VslOrderBonusModel();
                }
                $order_model = new VslOrderModel();
                $order_info = $order_model::get($order_id, ['order_goods']);
                $order_calculate_model = new VslOrderCalculateModel();
                $order_calculate_data = [];
                $order_shipping_fee = $order_info['shipping_money'] - $order_info['promotion_free_shipping'];
                $shipping_adjust_money = $shipping_fee - $order_shipping_fee;
                foreach ($order_goods_id_adjust_array as $k => $order_goods_id_adjust) {
                    $order_goods_adjust_array = explode(',', $order_goods_id_adjust);
                    $order_goods_id = $order_goods_adjust_array[0];
                    $adjust_money = $order_goods_adjust_array[1];
                    $order_goods_info = $this->order_goods->get($order_goods_id);

                    if ($order_shipping_fee == 0) {
                        // 如果订单创建时不需要运费,将变化的运费分配到商品
                        $order_goods_adjust_shipping_fee = round($shipping_adjust_money / count($order_info->order_goods), 2);
                    } else {
                        // 将运费按照商品的运费比例摊分
                        $order_goods_adjust_shipping_fee = round($shipping_adjust_money * ($order_goods_info['shipping_fee'] - $order_goods_info['promotion_free_shipping']) / $order_shipping_fee, 2);
                    }
                    $order_adjust_money += $adjust_money * $order_goods_info['num'];
                    $data = array(
                        'order_goods_id' => $order_goods_id,
                        'adjust_money' => $order_goods_info['adjust_money'] + $adjust_money,
                        'goods_money' => $order_goods_info['goods_money'] + $adjust_money * $order_goods_info['num'],
                        'actual_price' => $order_goods_info['actual_price'] + $adjust_money,
                        'real_money' => $order_goods_info['real_money'] + $adjust_money * $order_goods_info['num'] + $order_goods_adjust_shipping_fee,
                        'shipping_fee' => $order_goods_info['shipping_fee'] + $order_goods_adjust_shipping_fee,
                    );
//                    var_dump($data);
                    $result = $this->order_goods->data($data, true)->isupdate(true)->save();
                    if ($order_calculate_model::get(['order_goods_id' => $order_goods_id])) {
                        $order_calculate_model->delData(['order_goods_id' => $order_goods_id]);
                        //不存在分销分红的数据->已经计算了
                        //删除分销分红数据,并且重新计算分销分红等
                        $order_commission->delData(['order_goods_id' => $order_goods_id]);
                        if(getAddons('globalbonus', $this->website_id) || getAddons('areabonus', $this->website_id) || getAddons('teambonus', $this->website_id)){
                            $order_bonus->delData(['order_goods_id' => $order_goods_id]);
                        }
                        if ($order_info['order_type'] != 2 && $order_info['order_type'] != 3 && $order_info['order_type'] != 4 && $order_info['order_type'] != 10) {
                            $had_insert = false;
                            if (getAddons('microshop', $this->website_id) == 1 && $had_insert === false) {//微店计算
                                $temp_data = [];
                                $temp_data['order_id'] = $order_goods_info['order_id'];
                                $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                                $temp_data['goods_id'] = $order_goods_info['goods_id'];
                                $temp_data['buyer_id'] = $order_goods_info['buyer_id'];
                                $temp_data['website_id'] = $this->website_id;
                                $order_calculate_data[] = $temp_data;
                                $had_insert = true;
                            }
                            if (getAddons('distribution', $this->website_id) == 1 && $had_insert === false) {
                                $temp_data = [];
                                $temp_data['order_id'] = $order_goods_info['order_id'];
                                $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                                $temp_data['goods_id'] = $order_goods_info['goods_id'];
                                $temp_data['buyer_id'] = $order_goods_info['buyer_id'];
                                $temp_data['website_id'] = $this->website_id;
                                $order_calculate_data[] = $temp_data;
                                $had_insert = true;
                            }
                            if (getAddons('globalbonus', $this->website_id) == 1 && $had_insert === false) {
                                $temp_data = [];
                                $temp_data['order_id'] = $order_goods_info['order_id'];
                                $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                                $temp_data['goods_id'] = $order_goods_info['goods_id'];
                                $temp_data['buyer_id'] = $order_goods_info['buyer_id'];
                                $temp_data['website_id'] = $this->website_id;
                                $order_calculate_data[] = $temp_data;
                                $had_insert = true;
                            }
                            if (getAddons('areabonus', $this->website_id) == 1 && $had_insert === false) {
                                $temp_data = [];
                                $temp_data['order_id'] = $order_goods_info['order_id'];
                                $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                                $temp_data['goods_id'] = $order_goods_info['goods_id'];
                                $temp_data['buyer_id'] = $order_goods_info['buyer_id'];
                                $temp_data['website_id'] = $this->website_id;
                                $order_calculate_data[] = $temp_data;
                                $had_insert = true;
                            }
                            if (getAddons('teambonus', $this->website_id) == 1 && $had_insert === false) {
                                $temp_data = [];
                                $temp_data['order_id'] = $order_goods_info['order_id'];
                                $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                                $temp_data['goods_id'] = $order_goods_info['goods_id'];
                                $temp_data['buyer_id'] = $order_goods_info['buyer_id'];
                                $temp_data['website_id'] = $this->website_id;
                                $order_calculate_data[] = $temp_data;
                                $had_insert = true;
                            }
                        }
                    }
                }
                if (!empty($order_calculate_data)) {
                    $order_calculate_model->saveAll($order_calculate_data);
                }
            }
            $this->order_goods->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return $e;
        }
    }

    /**
     * 获取订单项实际可退款余额
     *
     * @param unknown $order_goods_id
     */
    public function orderGoodsRefundBalance($order_goods_id)
    {
        $order_goods = new VslOrderGoodsModel();
        $order_goods_info = $order_goods->getInfo([
            'order_goods_id' => $order_goods_id
        ], 'order_id,sku_id,goods_money');
        $order_goods_promotion = new VslOrderGoodsPromotionDetailsModel();
        $promotion_money = $order_goods_promotion->where([
            'order_id' => $order_goods_info['order_id'],
            'sku_id' => $order_goods_info['sku_id']
        ])->sum('discount_money');
        if (empty($promotion_money)) {
            $promotion_money = 0;
        }
        $money = $order_goods_info['goods_money'] - $promotion_money;
        // 计算其他方式支付金额
        $order = new VslOrderModel();
        $order_other_pay_money = $order->getInfo([
            'order_id' => $order_goods_info['order_id']
        ], 'order_money,point_money,user_money,coin_money,user_platform_money,tax_money,shipping_money');
        $order_goods_real_money = $order_other_pay_money['order_money'] - $order_other_pay_money['shipping_money'] - $order_other_pay_money['tax_money'];
        if ($order_goods_real_money != 0) {
            $refund_balance = $money / $order_goods_real_money * $order_other_pay_money['user_platform_money'];
            if ($refund_balance < 0) {
                $refund_balance = 0;
            }
        } else {
            $refund_balance = 0;
        }

        return $refund_balance;
    }

    /**
     * 查询订单项退款
     *
     * @param unknown $order_goods_id
     */
    public function getOrderGoodsRefundDetail($order_goods_id)
    {
        // 查询基础信息
        $order_goods_info = $this->order_goods->getInfo(['order_goods_id' => $order_goods_id], '*');
        // 查询商品sku表开始
        $goods_sku = new VslGoodsSkuModel();
        $goods_sku_info = $goods_sku->getInfo([
            'sku_id' => $order_goods_info['sku_id']
        ], 'code,attr_value_items');
        $order_goods_info['code'] = $goods_sku_info['code'];
        $order_goods_info['spec'] = [];
        if ($goods_sku_info['attr_value_items']) {
            $goods_spec_value = new VslGoodsSpecValueModel();
            $spec_info = [];
            $sku_spec_info = explode(';', $goods_sku_info['attr_value_items']);
            foreach ($sku_spec_info as $k_spec => $v_spec) {
                $spec_value_id = explode(':', $v_spec)[1];
                $sku_spec_value_info = $goods_spec_value::get($spec_value_id, ['goods_spec']);
                $spec_info[$k_spec]['spec_value_name'] = $sku_spec_value_info['spec_value_name'];
                $spec_info[$k_spec]['spec_name'] = $sku_spec_value_info['goods_spec']['spec_name'];
                //$order_item_list[$key_item]['spec'][$k_spec]['spec_value_name'] = $sku_spec_value_info['spec_value_name'];
                //$order_item_list[$key_item]['spec'][$k_spec]['spec_name'] = $sku_spec_value_info['goods_spec']['spec_name'];
            }
            $order_goods_info['spec'] = $spec_info;
            unset($sku_spec_value_info, $goods_sku_info, $sku_spec_info, $spec_info);
        }
        // 查询商品sku结束

        $picture = new AlbumPictureModel();
        // $order_item_list[$key_item]['picture'] = $picture->get($v_item['goods_picture']);
        $goods_picture = $picture->get($order_goods_info['goods_picture']);
        if (empty($goods_picture)) {
            $goods_picture = array(
                'pic_cover' => '',
                'pic_cover_big' => '',
                'pic_cover_mid' => '',
                'pic_cover_small' => '',
                'pic_cover_micro' => '',
                'upload_type' => 1,
                'domain' => ''
            );
        }
        $order_goods_info['picture'] = $goods_picture;
        if ($order_goods_info['refund_status'] != 0) {
            $order_refund_status = OrderStatus::getRefundStatus();
            foreach ($order_refund_status as $k_status => $v_status) {

                if ($v_status['status_id'] == $order_goods_info['refund_status']) {
                    $order_goods_info['refund_operation'] = $v_status['refund_operation'];
                    $order_goods_info['status_name'] = $v_status['status_name'];
                }
            }
            // 查询订单项的操作日志
            $order_refund = new VslOrderRefundModel();
            $refund_info = $order_refund->all([
                'order_goods_id' => $order_goods_id
            ]);
            $order_goods_info['refund_info'] = $refund_info;
        } else {
            $order_goods_info['refund_operation'] = '';
            $order_goods_info['status_name'] = '';
            $order_goods_info['refund_info'] = '';
        }
        return $order_goods_info;
    }

    /**
     * 获取出库单列表
     */
    public function getShippingList($order_ids)
    {
        $order_goods_view = new VslOrderGoodsViewModel();
        $condition = array(
            'nog.order_id' => array("in", $order_ids)
        );
        $list = $order_goods_view->getShippingList(1, 0, $condition, "");
        foreach ($list as $v) {
            $res = $order_goods_view->getOrderGoodsViewQuery(1, 0, [
                'no.order_id' => array("in", $order_ids),
                'nog.sku_id' => $v["sku_id"]
            ], "");
            $v["order_list"] = $res;
        }
        return $list;
    }

    /**
     * 添加打印时临时物流信息
     */
    public function addTmpExpressInformation($print_order_arr, $deliver_goods)
    {
        if (!empty($print_order_arr) && count($print_order_arr) > 0) {
            $vsl_order_goods = new VslOrderGoodsModel();
            $order_goods_express = new VslOrderGoodsExpressModel();
            $order = new OrderService();
            $vsl_order_goods->startTrans();
            try {
                foreach ($print_order_arr as $order_print_info) {
                    $vsl_order_goods->update([
                        "tmp_express_company" => $order_print_info["tmp_express_company_name"],
                        "tmp_express_company_id" => $order_print_info["tmp_express_company_id"],
                        "tmp_express_no" => $order_print_info["tmp_express_no"]
                    ], [
                        "order_id" => $order_print_info['order_id'],
                        "order_goods_id" => array("in", explode(",", $order_print_info["order_goods_ids"]))
                    ]);
                    //订单物流表
                    if ($order_print_info['is_devlier'] == 1) {
                        $order_goods_express->update([
                            "express_company_id" => $order_print_info["tmp_express_company_id"],
                            "express_company" => $order_print_info["tmp_express_company_name"],
                            "express_name" => $order_print_info["tmp_express_company_name"],
                            "express_no" => $order_print_info["tmp_express_no"]
                        ], [
                            "order_id" => $order_print_info['order_id'],
                            "id" => $order_print_info['express_id']
                        ]);
                    }
                    //订单发货
                    if ($order_print_info['is_devlier'] == 0 && $deliver_goods == 1) {
                        $order->orderDelivery($order_print_info['order_id'], $order_print_info['order_goods_ids'], $order_print_info["tmp_express_company_name"], 1, $order_print_info['tmp_express_company_id'], $order_print_info["tmp_express_no"]);
                    }
                }
                $vsl_order_goods->commit();
                return $retval = array(
                    "code" => 1,
                    "message" => "操作成功"
                );
            } catch (\Exception $e) {
            recordErrorLog($e);
                $vsl_order_goods->rollback();
                return $e->getMessage();
            }
        } else {
            return $retval = array(
                "code" => 0,
                "message" => "操作失败"
            );
        }

    }

    /*
     * 查询退款订单商品数量
     */
    public function getOrderGoodsCount($condition)
    {
        $order = new VslOrderGoodsViewModel();
        $count = $order->getAfterOrderViewCount($condition);
        return $count;
    }
    /*
     * 下单时从商品表获取相应的sku信息，避免规格删除之后订单商品规格为null
     */
    public function getSpecInfo($spec_value_id, $goods_id, $order_type = 1)
    {
        $specInfo = ['spec_value_name' => '', 'spec_name' => ''];
        if(!$spec_value_id || !$goods_id){
            return $specInfo;
        }
        if($order_type == 10){
            $goodsModel = new VslIntegralGoodsModel();
        }else{
            $goodsModel = new VslGoodsModel();
        }
        $goodsInfo = $goodsModel->getInfo(['goods_id' => $goods_id],'goods_spec_format');
        if(!$goodsInfo || !$goodsInfo['goods_spec_format']){
            return $specInfo;
        }
        $specFormatArr = json_decode($goodsInfo['goods_spec_format'],JSON_UNESCAPED_UNICODE);
        if(!is_array($specFormatArr)){
            return $specInfo;
        }
        $specFormatArrCol = array_column($specFormatArr,'value');//合并数组value值
        $specFormatArrFinal = array_reduce($specFormatArrCol, 'array_merge', array());//转换为一维数组
        foreach($specFormatArrFinal as $val){
            if($val['spec_value_id'] == $spec_value_id){
                $specInfo['spec_value_name'] = $val['spec_value_name'];
                $specInfo['spec_name'] = $val['spec_name'];
                break;
            }
        }
        return $specInfo;
    }
    /*
     * 门店
     */
    public function storeAddOrderGoodsNew($order_id, array $sku_lists, $adjust_money = 0, $uid = 0, $website_id = 0, $order_type = 1, $pay_money = 1, $order_from = 1, $store_id)
    {
        $this->order_goods->startTrans();
        try {
            if ($uid) {
                $this->uid = $uid;
            } else {
                $this->uid = $this->uid;
            }
            if ($website_id) {
                $this->website_id = $website_id;
            } else {
                $this->website_id = $this->website_id;
            }
            $err = 0;
            $goods = new Goods();
            foreach ($sku_lists as $sku_id => $sku_info) {
                if ($sku_info['order_type'] == 10 && getAddons('integral', $this->website_id, $this->instance_id)) {
                    $goods_sku_model = new VslIntegralGoodsSkuModel();
                    $goods_model = new VslIntegralGoodsModel();
                    $fields = 'goods_name,price,goods_type,picture,point_exchange_type,give_point,point_exchange,goods_exchange_type';
                } else {
                    $goods_sku_model = new VslGoodsSkuModel();
                    $goods_model = new VslGoodsModel();
                    $fields = 'goods_name,price,goods_type,picture,point_exchange_type,give_point';
                }
                $coupon_model = getAddons('coupontype', $this->website_id) ? new VslCouponModel() : '';
                $goods_sku_info = $goods_sku_model->getInfo([
                    'sku_id' => $sku_info['sku_id']
                ], 'sku_id,goods_id,market_price,cost_price,stock,sku_name,attr_value_items');
                if ($goods_sku_info['attr_value_items']) {
                    $goods_spec_value = new VslGoodsSpecValueModel();
                    $spec_info = [];
                    $sku_spec_info = explode(';', $goods_sku_info['attr_value_items']);
                    foreach ($sku_spec_info as $k_spec => $v_spec) {
                        $spec_value_id = explode(':', $v_spec)[1];
                        $spec_info[$k_spec] = $this->getSpecInfo($spec_value_id, $sku_info['goods_id'], $order_type);
                    }
                    $sku_attr = json_encode($spec_info, JSON_UNESCAPED_UNICODE);
                } else {
                    $sku_attr = '';
                }
                // 如果当前商品有SKU图片，就用SKU图片。没有则用商品主图
                $picture = $this->getSkuPictureBySkuId($goods_sku_info);
                $goods_info = $goods_model->getInfo([
                    'goods_id' => $sku_info['goods_id']
                ], $fields);
                $real_money = $sku_info['discount_price'] * $sku_info['num'];
                $actual_price = $sku_info['discount_price'];//实付单价
                $profile_price = 0;//计算每个商品platform的利润
                if ($sku_info['discount_id'] && $sku_info['promotion_shop_id'] > 0) {
                    $profile_price += $sku_info['member_price'] - $sku_info['discount_price'];
                }
                if (!empty($sku_info['coupon_sku_percent_amount']) && getAddons('coupontype', $this->website_id)) {
                    $real_money -= $sku_info['coupon_sku_percent_amount'];
                    // coupon_sku_percent_amount 这个sku在优惠券优惠金额的占比金额
                    $actual_price -= round($sku_info['coupon_sku_percent_amount'] / $sku_info['num'], 2);
                    $coupon_info = $coupon_model::get($sku_info['coupon_id'], ['coupon_type']);
                    if ($coupon_info->coupon_type->shop_id > 0 || $coupon_info->coupon_type->shop_range_type == 1) {
                        $profile_price += round($sku_info['coupon_sku_percent_amount'] / $sku_info['num'], 2);
                    }
                }
                if (!empty($sku_info['full_cut_sku_percent']) && !empty($sku_info['full_cut_sku_amount'])) {
                    $real_money -= $sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'];
                    // full_cut_sku_percent 这个sku在满减优惠金额的占比比例 full_cut_sku_amount这个sku满减优惠金额总金额
                    $actual_price -= round($sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'] / $sku_info['num'], 2);
                    if ($sku_info['full_cut_shop_id'] > 0 || $sku_info['full_cut_range']) {
                        $profile_price += round($sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'] / $sku_info['num'], 2);
                    }
                }
                $actual_price = ($actual_price > 0) ? $actual_price : 0;
                $real_money = ($real_money > 0) ? $real_money : 0;
//                if($sku_info['channel_id']){
//                    //取出当前渠道商的等级
//                    $channel_condition['c.channel_id'] = $sku_info['channel_id'];
//                    $channel_condition['c.website_id'] = $this->website_id;
//                    $channel = new Channel();
//                    $channel_info = $channel->getMyChannelInfo($channel_condition);
//                    $channel_weight = $channel_info['weight'];
//                }
                if ($sku_info['presell_id']) {
                    $order_type = 7;
                }
                if ($sku_info['seckill_id']) {
                    $order_type = 6;
                }
                if ($sku_info['bargain_id']) {
                    $order_type = 8;
                }
                if ($sku_info['deduction_money'] > 0) {
                    $real_money = $real_money - $sku_info['deduction_money'];
                }
                $data_order_sku = array(
                    'order_id' => $order_id,
                    'goods_id' => $sku_info['goods_id'],
                    'goods_name' => $goods_info['goods_name'],
                    'sku_id' => $sku_info['sku_id'],
                    'sku_name' => $goods_sku_info['sku_name'],
                    'real_money' => $real_money + (($sku_info['is_free_shipping']) ? 0 : $sku_info['shipping_fee']),//商品应付总额
                    'actual_price' => $actual_price, //实际单价
                    'price' => $sku_info['price'],// 销售价
                    'market_price' => $goods_sku_info['market_price'],//原价（市场价）
                    'profile_price' => $profile_price, // 用于分销计算利润的单价
                    'num' => $sku_info['num'],
                    'adjust_money' => $adjust_money,
                    'cost_price' => $goods_sku_info['cost_price'],
                    'goods_money' => $sku_info['price'] * $sku_info['num'] - $adjust_money,
                    'goods_picture' => $picture != 0 ? $picture : $goods_info['picture'], // 如果当前商品有SKU图片，就用SKU图片。没有则用商品主图
                    'shop_id' => $sku_info['shop_id'],
                    'website_id' => $this->website_id,
                    'buyer_id' => $this->uid,
                    'goods_type' => $goods_info['goods_type'],
                    'promotion_id' => $sku_info['promote_id'] ?: 0,
                    'promotion_type_id' => $sku_info['promote_id'] ? 2 : 0,
                    'point_exchange_type' => $goods_info['point_exchange_type'],
                    'order_type' => $order_type ?: 1, // 订单类型默认1
                    'give_point' => $sku_info['return_point'] ?: 0,
                    'discount_price' => $sku_info['discount_price'],
                    'member_price' => $sku_info['member_price'],
                    'shipping_fee' => $sku_info['shipping_fee'],
                    'promotion_free_shipping' => $sku_info['is_free_shipping'] ? $sku_info['shipping_fee'] : 0,
                    'channel_info' => $sku_info['channel_id'] ?: ($sku_info['channel_info'] ?: 0),
//                    'channel_weight' => $channel_weight?:0,
                    'seckill_id' => $sku_info['seckill_id'] ?: 0,
                    'presell_id' => $sku_info['presell_id'] ? $sku_info['presell_id'] : '',
                    'goods_point' => $sku_info['exchange_point'] / $sku_info['num'],
                    'goods_exchange_type' => $goods_info['goods_exchange_type'],
                    'sku_attr' => $sku_attr,
                    //计时/次商品
                    'card_store_id' => $sku_info['card_store_id'] ? $sku_info['card_store_id'] : 0,
                    'cancle_times' => $sku_info['cancle_times'] ? $sku_info['cancle_times'] : '',
                    'cart_type' => $sku_info['cart_type'] ? $sku_info['cart_type'] : 0,
                    'invalid_time' => $sku_info['invalid_time'] ? $sku_info['invalid_time'] : '',
                    'wx_card_id' => $sku_info['wx_card_id'] ? $sku_info['wx_card_id'] : '',
                    'card_title' => $sku_info['card_title'] ? $sku_info['card_title'] : '',
                    'deduction_money' => $sku_info['deduction_money'] ?: 0,
                    'deduction_point' => $sku_info['deduction_point'] ?: 0,
                    'deduction_freight_point' => $sku_info['deduction_freight_point'] ?: 0,
                    'return_freight_point' => $sku_info['return_freight_point'] ?: 0
                ); // 积分数量默认0
                $order_mdl = new VslOrderModel();
                $order_data = $order_mdl->getInfo(['order_id' => $order_id], '*');
                if (($order_data['pay_money'] == 0 && getAddons('channel', $this->website_id) && $order_data['buy_type'] == 2) || (getAddons('channel', $this->website_id) && $order_data['buy_type'] == 0 && !empty($sku_info['channel_id']))) {//渠道商自提的并且金额为0或者 零售的订单（有减库存就有记录）
                    //将订单商品
                    $channel = new Channel();
//                echo '<pre>';print_r(objToArr($channel_goods_info));exit;
                    //进行插入channel_order_sku_record
                    $sku_record_mdl = new VslChannelOrderSkuRecordModel();
                    $sku_record_arr['uid'] = $this->uid;
                    $sku_record_arr['order_id'] = $order_id;
                    $sku_record_arr['order_no'] = $order_data['order_no'];
                    //获取的渠道商信息
                    $condition_channel['c.website_id'] = $this->website_id;
                    $condition_channel['c.uid'] = $this->uid;
                    $channel_info = $channel->getMyChannelInfo($condition_channel);
                    $buyer_channel_id = $sku_info['channel_id'] ?: ($sku_info['channel_info'] ?: 0);
                    $stock_list = $channel->getChannelSkuStore($sku_info['sku_id'], $buyer_channel_id);
                    $sku_record_arr['my_channel_id'] = $channel_info['channel_id'] ?: 0;
                    $sku_record_arr['channel_info'] = $buyer_channel_id;
                    $sku_record_arr['goods_id'] = $sku_info['goods_id'];
                    $sku_record_arr['sku_id'] = $sku_info['sku_id'];
                    $sku_record_arr['total_num'] = $sku_info['num'];
                    $sku_record_arr['num'] = $sku_info['num'];
                    $sku_record_arr['price'] = $sku_info['price'];
                    $sku_record_arr['real_money'] = $real_money + (($sku_info['is_free_shipping']) ? 0 : $sku_info['shipping_fee']);//商品应付总额
                    $sku_record_arr['shipping_fee'] = $sku_info['shipping_fee'] ?: 0;
                    $sku_record_arr['channel_purchase_discount'] = $channel_info['purchase_discount'] ?: 0;
//                    $goods_sku_model1 = new VslGoodsSkuModel();
//                    $goods_sku_info = $goods_sku_model1->getInfo([
//                        'sku_id' => $sku_info['sku_id'],
//                    ], '*');
                    $sku_arr = ['channel_id' => $channel_info['channel_id'], 'sku_id' => $sku_info['sku_id'], 'price' => 0, 'market_price' => 0];
                    $goods->getChannelSkuPrice($sku_arr);
                    $sku_record_arr['platform_price'] = $sku_arr['price'];
                    //我剩余的所有该sku的库存
                    $sku_record_arr['remain_num'] = $stock_list['stock'];
                    $buy_type = $order_data['buy_type'] ?: 3;
                    if ($buy_type == 3) {//零售的要获取零售的是哪一批次的
                        //根据当前采购的数量去获取 批次id:num:bili
                        $batch_ratio_record = $channel->getPurchaseBatchRatio($buyer_channel_id, $sku_info['num'], $sku_info['sku_id']);//p1:采购谁  p2:采购数量
                        $sku_record_arr['batch_ratio_record'] = $batch_ratio_record ?: '';
                    }
                    $sku_record_arr['buy_type'] = $buy_type;//自提
                    $sku_record_arr['website_id'] = $this->website_id;
                    $sku_record_arr['create_time'] = time();
                    $is_record = $sku_record_mdl->where(['order_no' => $order_data['order_no']])->find();
                    if (!$is_record || $buy_type == 2) {
                        $id = $sku_record_mdl->save($sku_record_arr);
                    }
                    //增加销量
                    $goods_calculate = new GoodsCalculate();
                    if ($order_data['pay_money'] == 0) {
                        $goods_calculate->addChannelGoodsSales($sku_info['goods_id'], $sku_info['num'], $buyer_channel_id);
                        //增加该渠道商sku的销量
                        $goods_calculate->addChannelSkuSales($sku_info['sku_id'], $sku_info['num'], $buyer_channel_id);
                    }
                }
                if (getAddons('groupshopping', $this->website_id, $this->instance_id)) {
                    $group_server = new GroupShopping();
                    $is_group = $group_server->isGroupGoods($sku_info['goods_id']);
                    if ($is_group) {
                        $data_order_sku['refund_require_money'] = $data_order_sku['real_money'];
                    }
                }
                $order_goods = new VslOrderGoodsModel();
                $order_goods->save($data_order_sku);
                if ($sku_info['num'] == 0) {
                    $err = 1;
                }
                $channel_id = $sku_info['channel_id'] ?: ($sku_info['channel_info'] ?: 0);
                $goods_calculate = new GoodsCalculate();
                $seckill_status = getAddons('seckill', $this->website_id);
                $order = new VslOrderModel();
                $order_type = $order->getInfo(['order_id' => $order_id], 'order_type')['order_type'];
                if ($channel_id && getAddons('channel', $this->website_id)) {//渠道商
                    //如果是渠道商商品，则减的是渠道商的商品库存，并且加入member的账户余额中
                    $goods_calculate->subChannelGoodsStock($sku_info['goods_id'], $sku_info['sku_id'], $sku_info['num'], $channel_id);
                    //渠道商带了秒杀等活动
                    if (!empty($sku_info['seckill_id']) && $seckill_status) {
                        $seckill_server = new Seckill();
                        $seckill_server->subSeckillGoodsStock($sku_info['seckill_id'], $sku_info['sku_id'], $sku_info['num']);
                        //如果是立即购买进来的有存session秒杀id，则去掉
                        if ($_SESSION[$sku_id . 'seckill_id']) {
                            unset($_SESSION[$sku_id . 'seckill_id']);
                        }
                        //加销量
                        if ($pay_money == 0 && $order_from != 3) {//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                            $goods_calculate = new GoodsCalculate();
                            $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'], $store_id);
                            $goods_calculate->addSeckillSkuSales($sku_info['seckill_id'], $sku_info['sku_id'], $sku_info['num']);
                        }
                    } elseif (!empty($sku_info['bargain_id']) && getAddons('bargain', $this->website_id, $this->instance_id)) {
                        $bargain_server = new Bargain();
                        $bargain_server->subBargainGoodsStock($sku_info['bargain_id'], $sku_info['num']);
                        //加销量
                        if ($pay_money == 0 && $order_from != 3) {//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                            $goods_calculate = new GoodsCalculate();
                            $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'], $store_id);
                        }
                    }
                } elseif (!empty($sku_info['seckill_id']) && $seckill_status) {//秒杀
                    $seckill_server = new Seckill();
                    $seckill_server->subSeckillGoodsStock($sku_info['seckill_id'], $sku_info['sku_id'], $sku_info['num']);
                    //如果是立即购买进来的有存session秒杀id，则去掉
                    if ($_SESSION[$sku_id . 'seckill_id']) {
                        unset($_SESSION[$sku_id . 'seckill_id']);
                    }
                    //加销量
                    if ($pay_money == 0 && $order_from != 3) {//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                        $goods_calculate = new GoodsCalculate();
                        $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'], $store_id);
                        $goods_calculate->addSeckillSkuSales($sku_info['seckill_id'], $sku_info['sku_id'], $sku_info['num']);
                    }
                } elseif (!empty($sku_info['bargain_id']) && getAddons('bargain', $this->website_id, $this->instance_id)) {
                    $bargain_server = new Bargain();
                    $bargain_server->subBargainGoodsStock($sku_info['bargain_id'], $sku_info['num']);
                    //加销量
                    if ($pay_money == 0 && $order_from != 3) {//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                        $goods_calculate = new GoodsCalculate();
                        $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'], $store_id);
                        $goods_calculate->addBargainSkuSales($sku_info['bargain_id'], $sku_info['goods_id'], $sku_info['num']);
                    }
                } elseif ($order_type == 10 && getAddons('integral', $this->website_id, $this->instance_id)) {//兑换订单
                    // 库存减少
                    $integral_server = new Integral();
                    $integral_server->subIntegralGoodsStock($sku_info['goods_id'], $sku_info['sku_id'], $sku_info['num']);
                    //付完款生成订单直接加销量
                    $goods_calculate = new GoodsCalculate();
                    $goods_calculate->addIntegralGoodsSales($sku_info['goods_id'], $sku_info['num']);
                } elseif (!empty($sku_info['presell_id']) && getAddons('presell', $this->website_id, $this->instance_id)) {//预售订单
                    if ($pay_money == 0 && $order_from != 3) {//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                        $goods_calculate = new GoodsCalculate();
                        $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'], $store_id);
                    }
                } else {//正常
                    // 库存减少
                    $goods_calculate = new GoodsCalculate();
                    $goods_calculate->storeSubGoodsStock($sku_info['goods_id'], $sku_info['sku_id'], $sku_info['num'], $store_id);
                    if ($pay_money == 0 && $order_from != 3) {//订单金额为0的情况，不需要支付，在支付那里加的销量，如此就走不到支付那里了
                        $goods_calculate = new GoodsCalculate();
                        $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'], $store_id);
                    }
                    $goods_calculate->storeAddGoodsSales($sku_info['goods_id'], $sku_info['num'], $store_id);
                }
            }
            if ($err == 0) {
                $this->order_goods->commit();
                $order_calculate_data = [];
                foreach ($sku_lists as $sku_id => $sku_info) {
                    $order_goods = new VslOrderGoodsModel();
                    $order_goods_info = $order_goods->getInfo(['sku_id' => $sku_info['sku_id'], 'order_id' => $order_id, 'goods_id' => $sku_info['goods_id']]);
                    if ($sku_info['channel_id'] && $order_data['buy_type'] == 2) {
                        continue;
                    }
                    $had_insert = false;
                    if ($order_type != 2 && $order_type != 3 && $order_type != 4 && $order_type != 10) {
                        $microshop_status = getAddons('microshop', $website_id);
                        if ($microshop_status && $had_insert === false) {//微店计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $distribution_status = getAddons('distribution', $website_id);
                        if ($distribution_status == 1 && $had_insert === false) {
//                        hook('orderCommissionCalculate', ['order_id' => $order_id, 'order_goods_id' => $order_goods_info['order_goods_id'],'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功佣金计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $global_status = getAddons('globalbonus', $website_id);
                        if ($global_status == 1 && $had_insert === false) {
//                        hook('orderGlobalBonusCalculate', ['order_id' => $order_id,'order_goods_id' => $order_goods_info['order_goods_id'], 'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功全球分红计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $area_status = getAddons('areabonus', $website_id);
                        if ($area_status == 1 && $had_insert === false) {
//                        hook('orderAreaBonusCalculate', ['order_id' => $order_id, 'order_goods_id' => $order_goods_info['order_goods_id'], 'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功区域分红计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                        $team_status = getAddons('teambonus', $website_id);
                        if ($team_status == 1 && $had_insert === false) {
//                        hook('orderTeamBonusCalculate', ['order_id' => $order_id, 'order_goods_id' => $order_goods_info['order_goods_id'], 'goods_id' => $sku_info['goods_id'], 'buyer_id' => $this->uid, 'website_id' => $this->website_id]);//订单创建成功团队分红计算
                            $temp_data = [];
                            $temp_data['order_id'] = $order_id;
                            $temp_data['order_goods_id'] = $order_goods_info['order_goods_id'];
                            $temp_data['goods_id'] = $sku_info['goods_id'];
                            $temp_data['buyer_id'] = $this->uid;
                            $temp_data['website_id'] = $this->website_id;
                            $order_calculate_data[] = $temp_data;
                            $had_insert = true;
                        }
                    }
                }
                if (!empty($order_calculate_data)) {
                    $order_calculate_model = new VslOrderCalculateModel();
                    $order_calculate_model->saveAll($order_calculate_data);
                    if (class_exists('\swoole_client')) {
                        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
                        $ret = $client->connect("127.0.0.1", 9501);
                        $task_path = 'http://' . $_SERVER["HTTP_HOST"] . '/task/load_task_five';
                        if ($ret) {
                            $data = json_encode(['url' => $task_path, 'website_id' => $this->website_id]);
                            $client->send($data);
                        }
                    }
                }
                return 1;
            } elseif ($err == 1) {
                $this->order_goods->rollback();
                return ORDER_GOODS_ZERO;
            }
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order_goods->rollback();
            return $e->getMessage();
        }
    }
}