<?php

namespace app\shop\controller;

use addons\coupontype\server\Coupon;
use addons\seckill\server\Seckill;
use data\model\VslGoodsModel;
use data\model\VslOrderModel;
use addons\shop\model\VslShopModel;
use data\model\UserModel;
use data\service\Member;
use data\service\Order as OrderService;
use data\service\Order\Order as OrderBusiness;
use data\service\Config;
use data\model\VslGoodsTicketModel;
use addons\store\server\Store;
use think\Log;
use think\Session;

/**
 * 订单控制器
 */
class Order extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     * 创建订单
     */
    public function orderCreate()
    {
        $order_data = $_POST['post_data'];
        $order_service = new OrderService();
        $order_business = new OrderBusiness();
        $check_result = $order_service->checkBeforeOrderCreate($order_data);
        if ($check_result['result'] == false) {
            return $check_result;
        }
        //判断是否有秒杀商品
        $order_type = $this->isSeckillOrder($order_data['order']);
        //如果是秒杀订单则加入秒杀订单队列
        $ip = get_ip();
        if ($order_type) {
            $uid = $this->uid;
            $sec_service = new Seckill();
            $redis = $this->connectRedis();
            foreach ($order_data['order'] as $shop_id => $v) {
                foreach ($v['sku'] as $sku => $sku_list) {
                    $goods_id = $sku_list['goods_id'];
                    $goods_mdl = new VslGoodsModel();
                    $goods_name = $goods_mdl->getInfo(['goods_id'=>$goods_id],'goods_name')['goods_name'];
                    $sku_id = $sku_list['sku_id'];
                    $num = $sku_list['num'];
                    $seckill_id = $sku_list['seckill_id'];
                    $condition_is_seckill['s.seckill_id'] = $seckill_id;
                    $condition_is_seckill['nsg.sku_id'] = $sku_id;
                    $is_seckill = $sec_service->isSeckillGoods($condition_is_seckill);
                    //判断活动库存
                    $condition_seckill_sku['seckill_id'] = $seckill_id;
                    $condition_seckill_sku['sku_id'] = $sku_id;
                    //先模拟非秒杀商品
                    if ($is_seckill) {
                        $redis_goods_sku_store_key = 'store_' . $goods_id . '_' . $sku_id;
                        for ($i = 0; $i < $num; $i++) {
                            $is_store = $redis->lpop($redis_goods_sku_store_key);
                        }
                        if (!$is_store) {
                            return json(['code' => -1, 'message' => $goods_name . '库存不足，已抢购完毕']);
                        }
                        //新建一个key用来存用户购买某个sku的总数,用来判断用户总共购买的商品sku不超过限购数。
                        /*$user_buy_sku_num_key = 'buy_'.$seckill_id.'_'.$uid.'_'.$sku_id.'_num';
                        $user_buy_sku_num = $redis->get($user_buy_sku_num_key);
                        $new_total_num = $user_buy_sku_num + $num;
                        $redis->set($user_buy_sku_num_key, $new_total_num);*/
                    }else{
                        $order_data['order'][$shop_id]['sku'][$sku]['seckill_id'] = 0;
                    }
                }
            }
            $redis_key = getcwd();
            $redis_seckill_order_key = 'vslai_seckill_pc_order_'.$redis_key;
            // 获取支付编号
            $out_trade_no = $order_service->getOrderTradeNo();
            $order_data['out_trade_no'] = $out_trade_no;
            $order_data['ip'] = $ip;
            $order_data['create_time'] = time();
            $order_data['website_id'] = $this->website_id;
            $seckill_shop_order_data = serialize([$uid => $order_data]);
            //$res = $this->seckillOrderCreateByPc($seckill_shop_order_data);
            $redis->rpush($redis_seckill_order_key, $seckill_shop_order_data);
            //组支付需要的数据
            $all_should_paid_amount = 0;
            foreach ($order_data['shop'] as $k => $money) {
                $all_should_paid_amount += $money['shop_should_paid_amount'];
            }
            $pay_info = serialize(['out_trade_no' => $out_trade_no, 'create_time' => $order_data['create_time'], 'all_should_paid_amount' => $all_should_paid_amount]);
            $redis->set($out_trade_no, $pay_info);
            return AjaxReturn($out_trade_no);
        }

        $user_model = new UserModel();
        $buyer_info = $user_model::get($this->uid);
        // 订单来源
        $order_from = 3; // 电脑
        // 获取支付编号
        $out_trade_no = $order_service->getOrderTradeNo();

        $member = new Member();
        $shipping_time = time();
        $address = [];
        if($order_data['address_id']){
            $address = $member->getMemberExpressAddressDetail($order_data['address_id']);
        }
        foreach ($order_data['order'] as $shop_id => $v) {
            if($this->shopStatus && $shop_id){
                $shop_model = new VslShopModel();
                $shop_info = $shop_model::get(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
            }else{
                $shop_info['shop_name'] = '自营店';
            }
            $order_info = [];
            //自定义表单数据
            $order_info['custom_order'] = $order_data['custom_order'];
            $order_info['shop_id'] = $shop_id;
            $order_info['website_id'] = $this->website_id;
            $order_info['shop_name'] = $shop_info['shop_name'];
            $order_info['order_from'] = $order_from;
            if ($order_data['pay_type'] == 5) {
                $order_info['pay_money'] = 0;
                $order_info['user_platform_money'] = $order_data['shop'][$shop_id]['shop_should_paid_amount'];
            } else {
                $order_info['pay_money'] = $order_data['shop'][$shop_id]['shop_should_paid_amount'];
                $order_info['user_platform_money'] = 0;
            }

            $order_info['user_money'] = 0;

            $order_info['member_money'] = $order_data['shop'][$shop_id]['member_amount'];
            $order_info['coupon_reduction_amount'] = $order_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'] ?: 0;
            $order_info['shop_total_amount'] = $order_data['shop'][$shop_id]['shop_total_amount'];
            $order_info['shop_should_paid_amount'] = $order_data['shop'][$shop_id]['shop_should_paid_amount'];
            $order_info['promotion_money'] = $order_data['shop'][$shop_id]['man_song_amount'] ?: 0;
            $order_info['order_money'] = $order_info['pay_money'] + $order_info['user_platform_money'];
            $order_info['shipping_fee'] = $order_data['shop'][$shop_id]['shipping_fee'];
            $order_info['promotion_free_shipping'] = $order_data['shop'][$shop_id]['promotion_free_shipping'];

            $order_info['platform_promotion_money'] = 0;
            $order_info['shop_promotion_money'] = 0;
            if ($order_data['shop'][$shop_id]['man_song_amount']) {
                if ($order_data['shop'][$shop_id]['man_song_shop_id'] == 0) {
                    //平台优惠总额
                    $order_info['platform_promotion_money'] += $order_data['shop'][$shop_id]['man_song_amount'];
                } elseif ($order_data['shop'][$shop_id]['man_song_shop_id']) {
                    //店铺优惠总额
                    $order_info['shop_promotion_money'] += $order_data['shop'][$shop_id]['man_song_amount'];
                }
            }
            if ($order_data['coupon_reduction_amount']) {
                if ($order_data['promotion'][$shop_id]['coupon']['coupon_shop_id'] == 0) {
                    //平台优惠总额
                    $order_info['platform_promotion_money'] += $order_info['coupon_reduction_amount'];
                } elseif ($order_data['promotion'][$shop_id]['coupon']['coupon_shop_id']) {
                    //店铺优惠总额
                    $order_info['shop_promotion_money'] += $order_info['coupon_reduction_amount'];
                }
            }
            if ($order_data['shop'][$shop_id]['promotion_free_shipping']) {
                if ($order_data['shop'][$shop_id]['man_song_shipping_shop_id'] == 0) {
                    //平台优惠总额
                    $order_info['platform_promotion_money'] += $order_data['shop'][$shop_id]['promotion_free_shipping'];
                } elseif ($order_data['shop'][$shop_id]['man_song_shipping_shop_id']) {
                    //店铺优惠总额
                    $order_info['shop_promotion_money'] += $order_info['shop'][$shop_id]['promotion_free_shipping'];
                }
            }
            foreach ($v['sku'] as $sku_id => $sku_info) {
                if ($sku_info['discount_id'] || ($sku_info['member_price'] != $sku_info['price'])) {
                    //存在限时折扣或者平台会员价
                    if ($sku_info['promotion_shop_id'] == 0) {
                        //平台优惠总额 会员折扣也是
                        $order_info['platform_promotion_money'] += round(($sku_info['price'] - $sku_info['discount_price']) * $sku_info['num'], 2);
                    } elseif ($sku_info['promotion_shop_id']) {
                        //店铺优惠总额
                        $order_info['shop_promotion_money'] += round(($sku_info['member_price'] - $sku_info['discount_price']) * $sku_info['num'], 2);
                    }
                }
            }
            if ($order_info['pay_money'] != 0) {
                $order_info['order_status'] = 0;
                $order_info['pay_status'] = 0;
            } else {
                $order_info['order_status'] = 1;
                $order_info['pay_status'] = 2;
            }

            $order_info['order_type'] = 1;
            $order_info['out_trade_no'] = $out_trade_no;
            $order_info['order_sn'] = $out_trade_no;
            $order_info['order_no'] = $order_business->createOrderNo($shop_id);
            $order_info['pay_type'] = $order_data['pay_type'];
            $order_info['shipping_type'] = ($order_data['shop'][$shop_id]['store_id'] || $order_data['shop'][$shop_id]['card_store_id']) ? $order_data['shipping_type'] : 1;
            $order_info['ip'] = $ip;
            $order_info['leave_message'] = $order_data['shop'][$shop_id]['leave_message'] ?: '';
            $order_info['store_id'] = $order_data['shop'][$shop_id]['store_id'] ?: 0;
            $order_info['card_store_id'] = $order_data['shop'][$shop_id]['card_store_id'] ?: 0;
            if(($order_data['shop'][$shop_id]['store_id'] && $this->storeStatus) || ($order_data['shop'][$shop_id]['card_store_id'] && $this->storeStatus)){
                $store = new Store();
                if($order_data['shop'][$shop_id]['store_id']){
                    $store_info = $store->storeDetail($order_data['shop'][$shop_id]['store_id']);
                    $order_info['store_id'] = $order_data['shop'][$shop_id]['store_id'];
                    $order_info['verification_code'] = $store->createVerificationCode();
                }else{
                    $store_info = $store->storeDetail($order_data['shop'][$shop_id]['card_store_id']);
                }
                $address['province'] = $store_info['province_id'];
                $address['city'] = $store_info['city_id'];
                $address['district'] = $store_info['district_id'];
                $address['address'] = $store_info['address'];
            }
            $order_info['buyer_invoice'] = '';
            $order_info['shipping_time'] = $shipping_time;
            $order_info['receiver_mobile'] = $address['mobile'];
            $order_info['receiver_province'] = $address['province'];
            $order_info['receiver_city'] = $address['city'];
            $order_info['receiver_district'] = $address['district'];
            $order_info['receiver_address'] = $address['address'];
            $order_info['receiver_zip'] = $address['zip_code'];
            $order_info['receiver_name'] = $address['consigner'];
            //执行到这，说明秒杀活动已经失效了
            foreach ($v['sku'] as $sku1 => $sku_list1) {
                if(!empty($sku_list1['seckill_id'])){
                    $v['sku'][$sku1]['seckill_id'] = 0;
                }
                //计时计次商品信息
                $card_goods_id = $sku_list1['goods_id'];
                if($order_info['card_store_id']>0 && $card_goods_id>0){
                    $goods_mdl = new VslGoodsModel();
                    $goods_info = $goods_mdl->getInfo(['goods_id'=>$card_goods_id],'cancle_times,cart_type,invalid_time,valid_days,is_wxcard,wx_card_id');
                    $v['sku'][$sku1]['card_store_id'] = $order_info['card_store_id'];
                    $v['sku'][$sku1]['cancle_times'] = $goods_info['cancle_times'];
                    $v['sku'][$sku1]['cart_type'] = $goods_info['cart_type'];
                    if($goods_info['valid_type']==1){
                        $v['sku'][$sku1]['invalid_time'] = time()+$goods_info['valid_days']*24*60*60;
                    }else{
                        $v['sku'][$sku1]['invalid_time'] = $goods_info['invalid_time'];
                    }
                    if($goods_info['is_wxcard']==1){
                        $v['sku'][$sku1]['wx_card_id'] = $goods_info['wx_card_id'];
                        $ticket = new VslGoodsTicketModel();
                        $ticket_info = $ticket->getInfo(['goods_id'=>$card_goods_id]);
                        $v['sku'][$sku1]['card_title'] = $ticket_info['card_title'];
                    }
                }
            }
            $order_info['sku_info'] = $v['sku'];
            $order_info['pick_up_id'] = 0;
            $order_info['create_time'] = time();

            $order_info['buyer_id'] = $this->uid;
            $order_info['nick_name'] = $buyer_info['nick_name'];
            $order_info['is_deduction'] = ($order_data['is_deduction']==1)?1:0;
            if (!empty($order_data['promotion'][$shop_id]['man_song'])) {
                foreach ($order_data['promotion'][$shop_id]['man_song'] as $man_song_id => $man_song_info) {
                    $order_info['man_song_full_cut'][$man_song_id]['rule_id'] = $man_song_info['full_cut']['rule_id'];
                    $order_info['man_song_full_cut'][$man_song_id]['price'] = $man_song_info['full_cut']['price'];
                    $order_info['man_song_full_cut'][$man_song_id]['discount'] = $man_song_info['full_cut']['discount'];

                    if ($man_song_info['free_shipping_fee'] == true) {
                        if ($man_song_info['shop_id'] == 0) {
                            //平台优惠总额
                            $order_info['platform_promotion_money'] += $order_info['shipping_fee'];
                        } elseif ($man_song_info['shop_id']) {
                            //店铺优惠总额
                            $order_info['shop_promotion_money'] += $order_info['shipping_fee'];
                        }
                    }
                }
            }
            if (!empty($order_data['promotion'][$shop_id]['coupon'])) {
                $order_info['coupon']['coupon_id'] = $order_data['promotion'][$shop_id]['coupon']['coupon_id'];
                $order_info['coupon']['discount'] = $order_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'];//优惠券优惠多少钱
                $order_info['coupon']['coupon_genre'] = $order_data['promotion'][$shop_id]['coupon']['coupon_genre'];
                $order_info['coupon']['money'] = $order_data['promotion'][$shop_id]['coupon']['money'];//优惠券设置的满减金额
                $order_info['coupon']['coupon_discount'] = $order_data['promotion'][$shop_id]['coupon']['discount'];//优惠券设置折扣
                $order_info['coupon']['price'] = $order_data['promotion'][$shop_id]['coupon']['at_least'];
            }
            $order_business = new OrderBusiness();
            $point_deduction = $order_business->pointDeductionOrder($order_info['sku_info'],$order_info['is_deduction'],$order_info['shipping_type']);
            $order_info['deduction_money'] = $point_deduction['total_deduction_money'];
            $order_info['deduction_point'] = $point_deduction['total_deduction_point'];
            $order_info['sku_info'] = $point_deduction['sku_info'];
            if($order_info['deduction_money']>0){
                if($order_info['pay_money']>0){
                    $order_info['pay_money'] = $order_info['pay_money'] - $order_info['deduction_money'];
                }
                if($order_info['user_platform_money']>0){
                    $order_info['user_platform_money'] = $order_info['user_platform_money'] - $order_info['deduction_money'];
                }
                $order_info['order_money'] = $order_info['order_money'] - $order_info['deduction_money'];
            }
            $order_id = $order_business->orderCreateNew($order_info);
            
            $config = new Config();
            $shopConfig = $config->getShopConfig(0);
            // 针对特殊订单执行支付处理
            if ($order_id > 0 && is_numeric($order_id)) {
                if ($order_info['pay_money'] > 0) {
                    // 还需要支付的订单发送已创建待支付订单短信 邮件通知
                    $timeout = date('Y-m-d H:i:s', $order_info['create_time'] + ($shopConfig['order_buy_close_time'] * 60));
                    runhook('Notify', 'orderCreateBySms', array('order_id' => $order_id, 'time_out' => $timeout));
                    runhook('Notify', 'emailSend', ['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'order_id' => $order_id, 'notify_type' => 'user', 'time_out' => $timeout, 'template_code' => 'create_order']);
                }
                $order_model = new VslOrderModel();
                $order_info = $order_model->getInfo(['order_id' => $order_id], '*');
                if (!empty($order_info)) {
                    if ($order_info['user_platform_money'] != 0) {
                        if ($order_info['pay_money'] == 0) {
                            sleep(1);
                            $order_service->orderOnLinePay($order_info['out_trade_no'], 5, $order_id);
                        }
                    } else {
                        if ($order_info['pay_money'] == 0) { 
                            sleep(1);
                            $order_service->orderOnLinePay($order_info['out_trade_no'], 1, $order_id); // 默认微信支付
                        }
                    }
                    if($order_info['payment_type'] == 4){
                        $order_service = new OrderService();
                        //延时执行，避免并发写入 ，后期优化定时任务 
                        sleep(1);
                        $order_service->orderOnLinePay($out_trade_no, 4);
                    }
                }
                
            } else {
                
                return AjaxReturn($order_id);
            }
            Log::write($order_id);
        }
            if ($order_id > 0 && Session::get('order_tag') == 'cart') {
            if($order_data['order']){
                foreach($order_data['order'] as $key => $val){
                    foreach ($val['sku'] as $k => $v){
                        $sku_id_array[] = $v['sku_id'];
                    }
                }
            }
                $delete_cart_condition['buyer_id'] = $this->uid;
                $delete_cart_condition['sku_id'] = ['IN', $sku_id_array];
                $order_service->deleteCartNew($delete_cart_condition);
        }
        return AjaxReturn($out_trade_no);
    }


    /**
     *
     * 创建订单
     */
    public function seckillOrderCreateByPc($redis_pc_order_data)
    {
        $order_service = new OrderService();
        $order_business = new OrderBusiness();
        $storeServer = new Store();
        $order_data_arr = unserialize($redis_pc_order_data);
        $uid = array_keys($order_data_arr)[0];
        $order_data = array_values($order_data_arr)[0];
        $user_model = new UserModel();
        $buyer_info = $user_model::get($uid);
        $ip = $order_data['ip'];
        $website_id = $order_data['website_id'];
        $create_time = $order_data['create_time'];

        // 订单来源
        $order_from = 3; // 电脑
        // 获取支付编号
        $out_trade_no = $order_service->getOrderTradeNo();

        $member = new Member();
        $shipping_time = time();
        $address = [];
        if($order_data['address_id']){
            $address = $member->getMemberExpressAddressDetail($order_data['address_id'],$uid);
        }
        $err = 0;
        $count_shop = count($order_data['order']);
        try{
            foreach ($order_data['order'] as $shop_id => $v) {
                if($this->shopStatus && $shop_id){
                    $shop_model = new VslShopModel();
                    $shop_info = $shop_model::get(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
                }else{
                    $shop_info['shop_name'] = '自营店';
                }
                $order_info = [];
                //自定义表单数据
                $order_info['custom_order'] = $order_data['custom_order'];
                $order_info['website_id'] = $website_id;
                $order_info['shop_id'] = $shop_id;
                $order_info['shop_name'] = $shop_info['shop_name'];
                $order_info['order_from'] = $order_from;
                if ($order_data['pay_type'] == 5) {
                    $order_info['pay_money'] = 0;
                    $order_info['user_platform_money'] = $order_data['shop'][$shop_id]['shop_should_paid_amount'];
                } else {
                    $order_info['pay_money'] = $order_data['shop'][$shop_id]['shop_should_paid_amount'];
                    $order_info['user_platform_money'] = 0;
                }
                $order_info['user_money'] = 0;

                $order_info['member_money'] = $order_data['shop'][$shop_id]['member_amount'];
                $order_info['coupon_reduction_amount'] = $order_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'] ?: 0;
                $order_info['shop_total_amount'] = $order_data['shop'][$shop_id]['shop_total_amount'];
                $order_info['shop_should_paid_amount'] = $order_data['shop'][$shop_id]['shop_should_paid_amount'];
                $order_info['promotion_money'] = $order_data['shop'][$shop_id]['man_song_amount'] ?: 0;
                $order_info['order_money'] = $order_info['pay_money'] + $order_info['user_platform_money'];
                $order_info['shipping_fee'] = $order_data['shop'][$shop_id]['shipping_fee'];
                $order_info['promotion_free_shipping'] = $order_data['shop'][$shop_id]['promotion_free_shipping'];

                $order_info['platform_promotion_money'] = 0;
                $order_info['shop_promotion_money'] = 0;
                //post_data['shop'][shop_id]['man_song_amount']
                if ($order_data['shop'][$shop_id]['man_song_amount']) {
                    if ($order_data['shop'][$shop_id]['man_song_shop_id'] == 0) {
                        //平台优惠总额
                        $order_info['platform_promotion_money'] += $order_data['shop'][$shop_id]['man_song_amount'];
                        //('promotion_money:',$order_data['shop'][$shop_id]['man_song_amount']);
                    } elseif ($order_data['shop'][$shop_id]['man_song_shop_id']) {
                        //店铺优惠总额
                        $order_info['shop_promotion_money'] += $order_data['shop'][$shop_id]['man_song_amount'];
                    }
                }
                if ($order_data['coupon_reduction_amount']) {
                    if ($order_data['promotion'][$shop_id]['coupon']['coupon_shop_id'] == 0) {
                        //平台优惠总额
                        $order_info['platform_promotion_money'] += $order_info['coupon_reduction_amount'];
                    } elseif ($order_data['promotion'][$shop_id]['coupon']['coupon_shop_id']) {
                        //店铺优惠总额
                        $order_info['shop_promotion_money'] += $order_info['coupon_reduction_amount'];
                    }
                }

                //post_data['shop'][shop_id]['man_song_shipping_shop_id']
                //post_data['shop'][shop_id]['promotion_free_shipping']
                if ($order_data['shop'][$shop_id]['promotion_free_shipping']) {
                    if ($order_data['shop'][$shop_id]['man_song_shipping_shop_id'] == 0) {
                        //平台优惠总额
                        $order_info['platform_promotion_money'] += $order_data['shop'][$shop_id]['promotion_free_shipping'];
                        //var_dump('shipping:',$order_data['shop'][$shop_id]['promotion_free_shipping']);
                    } elseif ($order_data['shop'][$shop_id]['man_song_shipping_shop_id']) {
                        //店铺优惠总额
                        $order_info['shop_promotion_money'] += $order_info['shop'][$shop_id]['promotion_free_shipping'];
                    }
                }
                //post_data['order'][shop_id]['sku'][temp_sku_id]['promotion_shop_id']
                foreach ($v['sku'] as $sku_id => $sku_info) {
                    if ($sku_info['discount_id'] || ($sku_info['member_price'] != $sku_info['price'])) {
                        //存在限时折扣或者平台会员价
                        if ($sku_info['promotion_shop_id'] == 0) {
                            //平台优惠总额 会员折扣也是
                            $order_info['platform_promotion_money'] += round(($sku_info['price'] - $sku_info['discount_price']) * $sku_info['num'], 2);
                            //var_dump('discount:',round(($sku_info['member_price'] - $sku_info['discount_price']) * $sku_info['num'], 2));
                        } elseif ($sku_info['promotion_shop_id']) {
                            //店铺优惠总额
                            $order_info['shop_promotion_money'] += round(($sku_info['member_price'] - $sku_info['discount_price']) * $sku_info['num'], 2);
                        }
                    }
                }
                if ($order_info['pay_money'] != 0) {
                    $order_info['order_status'] = 0;
                    $order_info['pay_status'] = 0;
                } else {
                    $order_info['order_status'] = 1;
                    $order_info['pay_status'] = 2;
                }

                $order_info['order_type'] = 6;
                $order_info['out_trade_no'] = $out_trade_no;
                $order_info['order_sn'] = $out_trade_no;
                $order_info['order_no'] = $order_business->createOrderNo($shop_id);
                $order_info['pay_type'] = $order_data['pay_type'];
                $order_info['shipping_type'] = $order_data['shipping_type'];
                $order_info['ip'] = $ip;
                $order_info['leave_message'] = $order_data['shop'][$shop_id]['leave_message'] ?: '';
                $order_info['store_id'] = $order_data['shop'][$shop_id]['store_id'] ?: 0;
                if($order_data['shop'][$shop_id]['store_id']){
                    $order_info['verification_code'] = $storeServer->createVerificationCode();
                }
                $order_info['buyer_invoice'] = '';
                $order_info['shipping_time'] = $shipping_time;
                $order_info['receiver_mobile'] = (int)$address['mobile']?:'';
                $order_info['receiver_province'] = $address['province'];
                $order_info['receiver_city'] = $address['city'];
                $order_info['receiver_district'] = $address['district'];
                $order_info['receiver_address'] = $address['address'];
                $order_info['receiver_zip'] = $address['zip_code'];
                $order_info['receiver_name'] = $address['consigner'];

                $order_info['sku_info'] = $v['sku'];
                $order_info['pick_up_id'] = 0;
                $order_info['create_time'] = $create_time;

                $order_info['buyer_id'] = $uid;
                $order_info['nick_name'] = $buyer_info['nick_name'];
                $order_info['is_deduction'] = ($order_data['is_deduction']==1)?1:0;
                
//            post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['full_cut']['man_song_id']
//            post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['shipping']['man_song_info'] = true;
                if (!empty($order_data['promotion'][$shop_id]['man_song'])) {
                    foreach ($order_data['promotion'][$shop_id]['man_song'] as $man_song_id => $man_song_info) {
                        $order_info['man_song_full_cut'][$man_song_id]['rule_id'] = $man_song_info['full_cut']['rule_id'];
                        $order_info['man_song_full_cut'][$man_song_id]['price'] = $man_song_info['full_cut']['price'];
                        $order_info['man_song_full_cut'][$man_song_id]['discount'] = $man_song_info['full_cut']['discount'];
                        if ($man_song_info['free_shipping_fee'] == true) {
                            if ($man_song_info['shop_id'] == 0) {
                                //平台优惠总额
                                $order_info['platform_promotion_money'] += $order_info['shipping_fee'];
                            } elseif ($man_song_info['shop_id']) {
                                //店铺优惠总额
                                $order_info['shop_promotion_money'] += $order_info['shipping_fee'];
                            }
                        }
                    }
                    //如果是秒杀商品，则将上面加上秒杀优惠的运费减掉。
                    foreach ($v['sku'] as $sku_id => $sku_info) {
                        if ($man_song_info['shop_id'] == 0) {
                            if (!empty($sku_info['seckill_id'])) {
                                $order_info['platform_promotion_money'] -= $sku_info['shipping_fee'];
                            }
                        } elseif ($man_song_info['shop_id']) {
                            if (!empty($sku_info['seckill_id'])) {
                                $order_info['shop_promotion_money'] -= $sku_info['shipping_fee'];
                            }
                        }
                    }
                }
                if (!empty($order_data['promotion'][$shop_id]['coupon'])) {
                    $order_info['coupon']['coupon_id'] = $order_data['promotion'][$shop_id]['coupon']['coupon_id'];
                    $order_info['coupon']['discount'] = $order_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'];//优惠券优惠多少钱
                    $order_info['coupon']['coupon_genre'] = $order_data['promotion'][$shop_id]['coupon']['coupon_genre'];
                    $order_info['coupon']['money'] = $order_data['promotion'][$shop_id]['coupon']['money'];//优惠券设置的满减金额
                    $order_info['coupon']['coupon_discount'] = $order_data['promotion'][$shop_id]['coupon']['discount'];//优惠券设置折扣
                    $order_info['coupon']['price'] = $order_data['promotion'][$shop_id]['coupon']['at_least'];
                }
                $order_business = new OrderBusiness();
                //积分抵扣
                $point_deduction = $order_business->pointDeductionOrder($order_info['sku_info'],$order_info['is_deduction'],$order_info['shipping_type'],$order_info['website_id'],$uid);
                $order_info['deduction_money'] = $point_deduction['total_deduction_money'];
                $order_info['deduction_point'] = $point_deduction['total_deduction_point'];
                $order_info['sku_info'] = $point_deduction['sku_info'];
                if($order_info['deduction_money']>0){
                    if($order_info['pay_money']>0){
                        $order_info['pay_money'] = $order_info['pay_money'] - $order_info['deduction_money'];
                    }
                    if($order_info['user_platform_money']>0){
                        $order_info['user_platform_money'] = $order_info['user_platform_money'] - $order_info['deduction_money'];
                    }
                    $order_info['order_money'] = $order_info['order_money'] - $order_info['deduction_money'];
                }
                $order_id = $order_business->orderCreateNew($order_info);
                //满减送积分 > 0 送积分
//            if ($order_data['shop'][$shop_id]['man_song_point'] > 0) {
//                $memberAccount = new Member\MemberAccount();
//                $memberAccount->addMemberAccountData(1, $this->uid, 1, $order_data['shop'][$shop_id]['man_song_point'],
//                    1, $this->website_id, '注册营销,注册得积分');
//            }
                //满减送有设置优惠券 送优惠券
                if ($order_data['shop'][$shop_id]['man_song_coupon_type_id'] > 0) {
                    $coupon_server = new Coupon();
                    if ($coupon_server->isCouponTypeReceivable($order_data['shop'][$shop_id]['man_song_coupon_type_id'], $this->uid)) {
                        $coupon_server->userAchieveCoupon($this->uid, $order_data['shop'][$shop_id]['man_song_coupon_type_id'], 1);
                    }
                }
                $config = new Config();
                $shopConfig = $config->getShopConfig(0, $website_id);
                // 针对特殊订单执行支付处理
                if ($order_id > 0 && is_numeric($order_id)) {
                    if ($order_info['pay_money'] > 0) {
                        // 还需要支付的订单发送已创建待支付订单短信 邮件通知
                        $timeout = date('Y-m-d H:i:s', $order_info['create_time'] + ($shopConfig['order_buy_close_time'] * 60));
                        runhook('Notify', 'orderCreateBySms', array('order_id' => $order_id, 'time_out' => $timeout));
                        runhook('Notify', 'emailSend', ['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'order_id' => $order_id, 'notify_type' => 'user', 'time_out' => $timeout, 'template_code' => 'create_order']);
                    }

                    $order_model = new VslOrderModel();
                    $order_info = $order_model->getInfo(['order_id' => $order_id], '*');
                    if (!empty($order_info)) {
                        if ($order_info['user_platform_money'] != 0) {
                            if ($order_info['pay_money'] == 0) {
                                $order_service->orderOnLinePay($order_info['out_trade_no'], 5, $order_id);
                            }
                        } else {
                            if ($order_info['pay_money'] == 0) {
                                $order_service->orderOnLinePay($order_info['out_trade_no'], 1, $order_id); // 默认微信支付
                            }
                        }
                    }
                } else {
                    return AjaxReturn($order_id);
                }
                Log::write($order_id);
                if ($order_id > 0) {
                    foreach ($v['sku'] as $sku_id => $sku_info) {
                        if (!empty($sku_info['seckill_id'])) {
                            $redis = $this->connectRedis();
                            $num = $sku_info['num'];
                            //新建一个key用来存用户购买某个sku的总数,用来判断用户总共购买的商品sku不超过限购数。
                            $user_buy_sku_num_key = 'buy_' . $sku_info['seckill_id'] . '_' . $uid . '_' . $sku_info['sku_id'] . '_num';
                            $user_buy_sku_num = $redis->get($user_buy_sku_num_key);
                            $new_total_num = $user_buy_sku_num + $num;
                            $redis->set($user_buy_sku_num_key, $new_total_num);
                        }
                    }
                    $sku_id_array = array_keys($v['sku']);
                    $delete_cart_condition['buyer_id'] = $this->uid;
                    $delete_cart_condition['sku_id'] = ['IN', $sku_id_array];
                    $order_service->deleteCartNew($delete_cart_condition);
                }else{
                    $err++;
                }
            }
        }catch(\Exception $e){
//            echo $e->getMessage();exit;
        }

        if ($err == $count_shop) {
            return ['status' => false, 'out_trade_no' => $out_trade_no];
        } else {
            return ['status' => true, 'out_trade_no' => $out_trade_no];
        }
    }

    /*
     * 判断是否有秒杀商品
     * **/
    public function isSeckillOrder($order_shop)
    {
        if($this->is_seckill){
            $sec_service = new Seckill();
            $order_type = '';
            foreach ($order_shop as $shop_id => $v) {
                foreach ($v['sku'] as $sku => $sku_list) {
                    $sku_id = $sku_list['sku_id'];
                    $seckill_id = $sku_list['seckill_id'];
                    $condition_is_seckill['s.seckill_id'] = $seckill_id;
                    $condition_is_seckill['nsg.sku_id'] = $sku_id;
                    $is_seckill = $sec_service->isSeckillGoods($condition_is_seckill);
                    if ($is_seckill) {
                        $order_type = 'seckill_order';
                    }
                }
            }
        }
        return $order_type;
    }
    /**
     * 获取当前会员的订单列表
     */
    public function myOrderList()
    {
        $status = request()->get('status', 'all');
        if (request()->isAjax()) {
            $status = isset($_POST['status']) ? $_POST['status'] : 'all';
            $condition['buyer_id'] = $this->uid;
            if ($status != 'all') {
                switch ($status) {
                    case 0:
                        $condition['order_status'] = 0;
                        break;
                    case 1:
                        $condition['order_status'] = 1;
                        break;
                    case 2:
                        $condition['order_status'] = 2;
                        break;
                    case 3:
                        $condition['order_status'] = 3;
                        break;
                    case 4:
                        $condition['order_status'] = array(
                            'in', [-1, -2, 4]
                        );
                        break;
                    default:
                        break;
                }
            }
            // 还要考虑状态逻辑

            $order = new OrderService();
            $order_list = $order->getOrderList(1, 0, $condition, 'create_time desc');
            return $order_list['data'];
        } else {
            $this->assign("status", $status);
            return view($this->style . 'Order/myOrderList');
        }
    }

    /**
     * 订单后期支付页面
     */
    public function orderPay()
    {
        $order_id = request()->get('id', 0);
        $out_trade_no = request()->get('out_trade_no', 0);
        $order_service = new OrderService();
        $order = new VslOrderModel();
        if ($order_id != 0) {
            $order_list = $order->getInfo(['order_id'=>$order_id], '*');
            if(!$order_list['presell_id']){
                // 更新支付流水号
                $new_out_trade_no = $order_service->getOrderNewOutTradeNo($order_id);
            }else{
                if($order_list['presell_id'] && $order_list['money_type'] == 0){//付定金
                    $new_out_trade_no = $order_list['out_trade_no'];
                }elseif($order_list['presell_id'] && $order_list['money_type'] == 1){//付尾款
                    $new_out_trade_no = $order_list['out_trade_no_presell'];
                }
            }
            $url = __URL(__URL__ . '/pay/getpayvalue?out_trade_no=' . $new_out_trade_no);
            header("Location: " . $url);
            exit();
        } else {
            // 待结算订单处理
            if ($out_trade_no != 0) {
                $url = __URL(__URL__ . '/pay/getpayvalue?out_trade_no=' . $out_trade_no);
                exit();
            } else {
                $this->error("没有获取到支付信息");
            }
        }
    }

    /**
     * 收货
     */
    public function orderTakeDelivery()
    {
        $order_service = new OrderService();
        $order_id = request()->post('order_id', '');
        $res = $order_service->OrderTakeDelivery($order_id);
        return AjaxReturn($res);
    }

    /**
     * 商品评价
     */
    public function addGoodsEvaluate()
    {
        $order = new OrderService();
        $order_id = intval(request()->post('order_id', 0));
        $order_no = request()->post('order_no', '');
        $goods = $_POST['goodsEvaluate'];
        $shop_desc = request()->post('shop_desc', '');
        $shop_service = request()->post('shop_service', 0);
        $shop_stic = request()->post('shop_stic', 0);
        $shop_id = request()->post('shop_id', 0);
        $store_service = request()->post('store_service',0);
        if (empty($order_id) || empty($goods)) {
            return AjaxReturn(0);
        }
        $order_model = new VslOrderModel();
        $order_info = $order_model::get($order_id);
        if ($shop_desc || $shop_service || $shop_stic) {
            $data_shop = array(
                'order_id' => $order_id,
                'order_no' => $order_info['order_no'],
                'website_id' => $this->website_id,
                'shop_id' => $order_info['shop_id'],
                'shop_desc' => $shop_desc,
                'shop_service' => $shop_service,
                'shop_stic' => $shop_stic,
                'add_time' => time(),
                'member_name' => isset($this->member_info['user_info']['user_name']) ? $this->member_info['user_info']['user_name'] : $this->member_info['user_info']['nick_name'],
                'uid' => $this->uid,
            );
            $order->addShopEvaluate($data_shop);
        }
        if(getAddons('store', $this->website_id, $order_info['shop_id']) && $order_info['store_id']){
            $storeServer = new Store();
            $data_store = array(
                'order_id' => $order_id,
                'order_no' => $order_info['order_no'],
                'website_id' => $this->website_id,
                'shop_id' => $order_info['shop_id'],
                'store_id' => $order_info['store_id'],
                'store_service' => ($store_service > 5 || $store_service < 0) ? 5 : $store_service,
                'add_time' => time(),
                'member_name' => isset($this->member_info['user_info']['user_name']) ? $this->member_info['user_info']['user_name'] : $this->member_info['user_info']['nick_name'],
                'uid' => $this->uid,
            );
            $storeServer->addStoreEvaluate($data_store);
        }
        $goodsEvaluateArray = $goods;
        $dataArr = array();
        foreach ($goodsEvaluateArray as $key => $goodsEvaluate) {
            $orderGoods = $order->getOrderGoodsInfo($goodsEvaluate['order_goods_id']);
            if($goodsEvaluate['imgs']){
                foreach ($goodsEvaluate['imgs'] as $key1 => $img) {
                    $goodsEvaluate['imgs'][$key1] = changeFile($img, $this->website_id,'evaluate');
                }
            }
            $data = array(
                'order_id' => $order_id,
                'order_no' => $order_no,
                'order_goods_id' => intval($goodsEvaluate['order_goods_id']),
                'website_id' => $orderGoods['website_id'],
                'goods_id' => $orderGoods['goods_id'],
                'goods_name' => $orderGoods['goods_name'],
                'goods_price' => $orderGoods['goods_money'],
                'goods_image' => $orderGoods['goods_picture'],
                'shop_id' => $orderGoods['shop_id'],
                'content' => $goodsEvaluate['content'],
                'addtime' => time(),
                'image' => (!empty($goodsEvaluate['imgs']) && is_array($goodsEvaluate['imgs'])) ? implode(',', $goodsEvaluate['imgs']) : '',
                'member_name' => isset($this->member_info['user_info']['user_name']) ? $this->member_info['user_info']['user_name'] : $this->member_info['user_info']['nick_name'],
                'explain_type' => $goodsEvaluate['explain_type'],
                'uid' => $this->uid,
            );
            $dataArr[] = $data;
        }
        return $order->addGoodsEvaluate($dataArr, $order_id);
    }

    /**
     * 商品-追加评价
     */
    public function addGoodsEvaluateAgain()
    {
        $order = new OrderService();
        $order_id = request()->post('order_id', '');
        $order_id = intval($order_id);
        $goods = $_POST['goodsEvaluate'];
        $goodsEvaluateArray = $goods;
        $result = 1;
        foreach ($goodsEvaluateArray as $key => $goodsEvaluate) {
            if($goodsEvaluate['imgs']){
                foreach ($goodsEvaluate['imgs'] as $key1 => $img) {
                    $goodsEvaluate['imgs'][$key1] = changeFile($img, $this->website_id,'evaluate');
                }
                $goodsEvaluate['imgs'] = (!empty($goodsEvaluate['imgs']) && is_array($goodsEvaluate['imgs'])) ? implode(',', $goodsEvaluate['imgs']) : '';
            }
            $res = $order->addGoodsEvaluateAgain($goodsEvaluate['content'], $goodsEvaluate['imgs'], $goodsEvaluate['order_goods_id']);
            if ($res == false) {
                $result = false;
                break;
            }
        }
        if ($result == 1) {
            $data = array(
                'is_evaluate' => 2
            );
            $result = $order->modifyOrderInfo($data, $order_id);
        }

        return $result;
    }

    /**
     * 删除订单
     */
    public function deleteOrder()
    {
        if (request()->isAjax()) {
            $order_service = new OrderService();
            $order_id = request()->post("order_id", "");
            $res = $order_service->deleteOrder($order_id, 2, $this->uid);
            return AjaxReturn($res);
        }
    }
    
    /**
     * 订单积分抵扣计算
     */
    public function pointDeduction()
    {
        $order_business = new OrderBusiness();
        $order_data = $_POST['post_data'];
        $data = [];
        $deduction = [];
        $return = [];
        if($order_data['order']){
            foreach ($order_data['order'] as $k => $v) {
                $deduction[] = $order_business->pointDeductionOrder($v['sku'],1,$order_data['shipping_type']);
                $return[] = $order_business->pointReturnOrder($v['sku'],$order_data['shipping_type']);
            }
        }
        $data['data']['deduction'] = $deduction;
        $data['data']['return'] = $return;
        $data['code'] = 1;
        return $data;
    }
}