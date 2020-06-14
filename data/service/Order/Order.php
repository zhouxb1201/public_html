<?php

namespace data\service\Order;

use addons\bargain\model\VslBargainRecordModel;
use addons\bargain\service\Bargain;
use addons\channel\model\VslChannelModel;
use addons\channel\model\VslChannelOrderGoodsModel;
use addons\channel\model\VslChannelOrderModel;
use addons\channel\model\VslChannelOrderSkuRecordModel;
use addons\channel\server\Channel;
use addons\coupontype\model\VslCouponTypeModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\invoice\server\Invoice as InvoiceServer;
use addons\seckill\server\Seckill;
use addons\store\server\Store;
use data\model\AddonsConfigModel;
use data\model\AlbumPictureModel;
use data\model\ConfigModel;
use data\model\VslActivityOrderSkuRecordModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslMemberAccountModel;
use data\model\VslOrderActionModel as VslOrderActionModel;
use data\model\VslOrderExpressCompanyModel;
use data\model\VslOrderGoodsExpressModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderGoodsPromotionDetailsModel;
use data\model\VslOrderModel;
use data\model\VslOrderPromotionDetailsModel;
use data\model\VslOrderShopReturnModel;
use data\model\VslPresellModel;
use data\model\VslPromotionMansongRuleModel;
use data\model\UserModel as UserModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslOrderRefundModel;
use data\model\VslStoreGoodsModel;
use data\model\VslStoreGoodsSkuModel;
use data\service\Address;
use data\service\BaseService;
use data\service\GoodsCalculate\GoodsCalculate;
use data\service\Member\MemberAccount;
use data\service\Member as MemberService;
use addons\coupontype\server\Coupon as CouponServer;
use data\service\UnifyPay;
use data\service\MemberCard;
use data\service\Config;
use data\service\Order as ServiceOrder;
use addons\store\model\VslStoreModel;
use think\Exception;
use think\Log;
use data\model\VslOrderRefundAccountRecordsModel;
use addons\shop\model\VslShopModel;
use addons\groupshopping\server\GroupShopping;
use addons\gift\server\Gift;
use addons\giftvoucher\server\GiftVoucher;
use addons\distribution\service\Distributor as  DistributorService;
use data\model\VslOrderCalculateModel;

/**
 * 订单操作类
 */
class Order extends BaseService
{

    public $order;

    // 订单主表
    function __construct()
    {
        parent::__construct();
        $this->order = new VslOrderModel();
    }

    /**
     * 创建订单
     * @param array $order_info
     * @return int|mixed
     */
    public function orderCreateNew(array $order_info)
    {
        
        $this->order->startTrans();
        
        $account_flow = new MemberAccount();
        $couponServer = getAddons('coupontype', $this->website_id) ? new CouponServer() : '';

        // 购物返积分是否开启
        $config = new ConfigModel();
        $config_info1 = $config->getInfo([
            'website_id' => $order_info['website_id'],
            'key' => 'IS_POINT'
        ], 'value');
        
        $is_point = $config_info1['value'];
        $give_point_type = 0;
        $give_point = 0;
        $convert_rate = 0;
        if ($is_point == 1) {
            // 积分返还状态类型
            $config_info2 = $config->getInfo([
                'website_id' => $order_info['website_id'],
                'key' => 'SHOPPING_BACK_POINTS'
            ], 'value');
            $give_point_type = $config_info2['value'];
            //积分换算金钱比例
            $config_info5 = $config->getInfo([
                'website_id' => $order_info['website_id'],
                'key' => 'POINT_DEDUCTION_NUM'
            ], 'value');
            $convert_rate = $config_info5['value'];
            $point_return = $this->pointReturnOrder($order_info['sku_info'],$order_info['shipping_type'],$order_info['website_id']);
            if($point_return['total_return_point']>0){
                $order_info['sku_info'] = $point_return['sku_info'];
                $give_point = $point_return['total_return_point'];
            }
        }
        try {
            if(empty($order_info['presell_id'])){
                $money_type = 0;
            }
            //            else{
            //                $money_type = 1;
            //            }
            $data_order = array(
                'custom_order'=>$order_info['custom_order'],
                'order_no' => $order_info['order_no'],
                'out_trade_no' => $order_info['out_trade_no'],
                'out_trade_no_presell' => $order_info['out_trade_no_presell'],
                'order_sn' => $order_info['order_sn'],
                'order_from' => $order_info['order_from'],
                'buyer_id' => $order_info['buyer_id'],
                'user_name' => $order_info['nick_name'],
                'buyer_ip' => $order_info['ip'],
                'buyer_message' => $order_info['leave_message'],
                'buyer_invoice' => $order_info['buyer_invoice']?:'',
                'shipping_time' => $order_info['shipping_time']?:0, // datetime NOT NULL COMMENT '买家要求配送时间',
                'receiver_mobile' => $order_info['receiver_mobile'], // varchar(11) NOT NULL DEFAULT '' COMMENT '收货人的手机号码',
                'receiver_province' => $order_info['receiver_province'], // int(11) NOT NULL COMMENT '收货人所在省',
                'receiver_city' => $order_info['receiver_city'], // int(11) NOT NULL COMMENT '收货人所在城市',
                'receiver_district' => $order_info['receiver_district'], // int(11) NOT NULL COMMENT '收货人所在街道',
                'receiver_address' => $order_info['receiver_address'], // varchar(255) NOT NULL DEFAULT '' COMMENT '收货人详细地址',
                'receiver_zip' => $order_info['receiver_zip'], // varchar(6) NOT NULL DEFAULT '' COMMENT '收货人邮编',
                'receiver_name' => $order_info['receiver_name'], // varchar(50) NOT NULL DEFAULT '' COMMENT '收货人姓名',
                'shop_id' => $order_info['shop_id'], // int(11) NOT NULL COMMENT '卖家店铺id',
                'shop_name' => $order_info['shop_name'], // varchar(100) NOT NULL DEFAULT '' COMMENT '卖家店铺名称',
                'point' => $order_info['point'], // int(11) NOT NULL COMMENT '订单消耗积分',
                'coupon_id' => $order_info['coupon']['coupon_id'] ?: 0, // int(11) NOT NULL COMMENT '订单代金券id',
                'give_point' => $give_point, // int(11) NOT NULL COMMENT '订单赠送积分',, // int(11) NOT NULL COMMENT '订单赠送积分',
                'create_time' => $order_info['create_time'],
                'website_id' => $order_info['website_id'],
                'shipping_company_id' => $order_info['shipping_company_id']?:0,

                'payment_type' => $order_info['pay_type'],
                'payment_type_presell' => 0,
                'shipping_type' => $order_info['shipping_type'],
                'order_type' => $order_info['order_type'],
                'order_status' => $order_info['order_status'], // tinyint(4) NOT NULL COMMENT '订单状态',
                'pay_status' => $order_info['pay_status'], // tinyint(4) NOT NULL COMMENT '订单付款状态',
                'shipping_status' => 0, // tinyint(4) NOT NULL COMMENT '订单配送状态',
                'review_status' => 0, // tinyint(4) NOT NULL COMMENT '订单评价状态',
                'feedback_status' => 0, // tinyint(4) NOT NULL COMMENT '订单维权状态',
                'give_point_type' => $give_point_type,

                'point_money' => 0.00, // decimal(10, 2) NOT NULL COMMENT '订单消耗积分抵多少钱',
                'coupon_money' => $order_info['coupon_reduction_amount']?:0, // _money decimal(10, 2) NOT NULL COMMENT '优惠券优惠金额',
                'user_money' => $order_info['user_money'], // decimal(10, 2) NOT NULL COMMENT '订单预存款支付金额',
                'user_platform_money' => $order_info['user_platform_money'], // 平台余额支付
                'promotion_money' => $order_info['promotion_money']?:0, // decimal(10, 2) NOT NULL COMMENT '满减送优惠金额',
                'shipping_money' => $order_info['shipping_fee']?:$order_info['shipping_money'], // decimal(10, 2) NOT NULL COMMENT '订单运费',
                'pay_money' => $order_info['pay_money'], // decimal(10, 2) NOT NULL COMMENT '订单实付金额',
                'channel_money' => $order_info['channel_money']?:0, // 渠道商金额
                'normal_money' => $order_info['normal_money']?:0, // 正常金额
                'final_money' => $order_info['final_money']?:0,//预售尾款
                'refund_money' => 0, // decimal(10, 2) NOT NULL COMMENT '订单退款金额',
                'coin_money' => $order_info['coin']?:0,
                'goods_money' => $order_info['shop_total_amount'], // decimal(19, 2) NOT NULL COMMENT '商品总价',
                'tax_money' => $order_info['tax_money']?:0, // 税费
                'order_money' => $order_info['order_money'], // decimal(10, 2) NOT NULL COMMENT '订单总价',
                'member_money' => $order_info['member_money'], // decimal(10,2)会员价总额,
                'platform_promotion_money' => $order_info['platform_promotion_money']?:0, // 平台优惠活动的优惠金额
                'shop_promotion_money' => $order_info['shop_promotion_money']?:0, //店铺优惠活动的优惠金额
                'promotion_free_shipping' => $order_info['promotion_free_shipping']?:0, //优惠活动减免的邮费，目前仅有满减送的包邮
                'group_id' => $order_info['group_id']?:0, //团购活动id
                'group_record_id' => $order_info['group_record_id']?:0, //发起团购记录id
                'buy_type' => $order_info['buy_type']?:0,
                'presell_id' => $order_info['presell_id']?:'',
				'store_id' => $order_info['store_id']?:0,
				'bargain_id' => $order_info['bargain_id']?:0,
                'verification_code' => $order_info['verification_code']?:0,
                'money_type' => $money_type,
                'point_convert_rate'=>$convert_rate,//积分兑换金额比例
                'shopkeeper_id'=>$order_info['shopkeeper_id']?:'',
                'card_store_id'=>$order_info['card_store_id']?:0,
                'deduction_money'=>$order_info['deduction_money']?:0,
                'deduction_point'=>$order_info['deduction_point']?:0,
                'shop_order_money' => $order_info['shop_order_money'], // decimal(10, 2) NOT NULL COMMENT '店铺实际到账金额',
                'sign_time' => $order_info['sign_time'] ? : 0,

                'invoice_tax' => $order_info['invoice_tax'] ?: 0,
                'invoice_type' => $order_info['invoice_type'] ?: 0,
                'order_types' => $order_info['order_types'] ?: '',
                'http_from' => isFromMp() ? 1: 0,
            ); // datetime NOT NULL DEFAULT 'CURRENT_TIMESTAMP' COMMENT '订单创建时间',
            
            if ($order_info['pay_status'] == 2) {
                $data_order['pay_time'] = time();
            }
            $order = new VslOrderModel();
            $order->save($data_order);
            
            $order_id = $order->order_id;
            $pay = new UnifyPay();
            $pay->createPayment($order_info['shop_id'], $order_info['out_trade_no'], $order_info['shop_name'] . '订单', $order_info['shop_name'] . '订单', $order_info['pay_money'], 1, $order_id, $order_info['create_time']);
            if($order_info['presell_id']){
                $pay->createPayment($order_info['shop_id'], $order_info['out_trade_no_presell'], $order_info['shop_name'] . '订单', $order_info['shop_name'] . '订单', $order_info['final_money'], 1, $order_id, $order_info['create_time']);
            }
           
            // 添加订单项
            $order_goods = new OrderGoods();
            if($order_info['order_type'] == 12){
                if(!$order_info['store_id']){
                    $order_info['store_id'] = 0;
                }
                $res_order_goods = $order_goods->addOrderGoodsForStore($order_id, $order_info['sku_info'], 0, $order_info['buyer_id'], $order_info['website_id'],$order_info['order_type'], $order_info['pay_money'], $order_info['order_from'], $order_info['invoice_tax'],$order_info['store_id']);
            }else{
                if(!$order_info['order_types']){
                    $order_info['order_types'] = '';
                }
                $store_id = $order_info['store_id'] ?:($order_info['card_store_id'] ?: 0);
                $res_order_goods = $order_goods->addOrderGoodsNew($order_id, $order_info['sku_info'], 0, $order_info['buyer_id'], $order_info['website_id'],$order_info['order_type'], $order_info['pay_money'], $order_info['order_from'], $store_id, $order_info['invoice_tax'], $order_info['order_types']);
            }
            if (!($res_order_goods > 0)) {
                $this->order->rollback();
                return $res_order_goods;
            }

            // 活动优惠情况
            $order_promotion_details = new VslOrderPromotionDetailsModel();
            $data_promotion_details = [];
            if (!empty($order_info['man_song_full_cut'])) {
                foreach ($order_info['man_song_full_cut'] as $man_song_id => $man_song_info) {
                    $data_promotion_details[] = [
                        'order_id' => $order_id,
                        'promotion_id' => $man_song_info['rule_id'],
                        'promotion_type_id' => 1,
                        'promotion_type' => 'MANJIAN',
                        'promotion_name' => '满减送活动',
                        'promotion_condition' => '满' . $man_song_info['price'] . '元，减' . $man_song_info['discount'],
                        'discount_money' => $man_song_info['discount'],
                        'used_time' => time()
                    ];
                }
            }

            if (!empty($order_info['coupon'])) {
                $data_promotion_details[] = [
                    'order_id' => $order_id,
                    'promotion_id' => $order_info['coupon']['coupon_id'],
                    'promotion_type_id' => 3,
                    'promotion_type' => 'COUPON',
                    'promotion_name' => '优惠券',
                    'promotion_condition' => ($order_info['coupon']['coupon_genre'] != 3) ?
                        '满' . $order_info['coupon']['price'] . '元，减' . $order_info['coupon']['money'] :
                        '满' . $order_info['coupon']['price'] . '元，打' . $order_info['coupon']['coupon_discount'] . '折',
                    'discount_money' => $order_info['coupon']['discount'],
                    'used_time' => time()
                ];
            }
            if (!empty($data_promotion_details)){
                $order_promotion_details->saveAll($data_promotion_details);
            }
            $order_goods_promotion_details = new VslOrderGoodsPromotionDetailsModel();
            $promotion_details = [];
            
            if (!empty($order_info['sku_info'])) {
                //$order_goods_promotion_details = new VslOrderGoodsPromotionDetailsModel();
                foreach ($order_info['sku_info'] as $key => $sku_info) {
                    // 添加到对应商品项优惠满减 满减送送优惠券、赠品、礼品券
                    if (!empty($sku_info['promotion_id'])) {
                        $promotion_details[] = array(
                            'order_id' => $order_id,
                            'promotion_id' => $sku_info['promotion_id'],
                            'sku_id' => $sku_info['sku_id'],
                            'promotion_type' => 'MANJIAN',
                            'discount_money' => round($sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'], 2),
                            'used_time' => time()
                        );
                    }

                    // 添加到对应商品项优惠优惠券使用详情
                    if (!empty($sku_info['coupon_id'])) {
                        $promotion_details[] = array(
                            'order_id' => $order_id,
                            'promotion_id' => $sku_info['coupon_id'],
                            'sku_id' => $sku_info['sku_id'],
                            'promotion_type' => 'COUPON',
                            'discount_money' => $sku_info['coupon_sku_percent_amount'],
                            'used_time' => time()
                        );
                    }

                    // 限时折扣商品项优惠
                    if (!empty($sku_info['discount_id'])){
                        $promotion_details[] = array(
                            'order_id' => $order_id,
                            'promotion_id' => $sku_info['discount_id'],
                            'sku_id' => $sku_info['sku_id'],
                            'promotion_type' => 'DISCOUNT',
                            'discount_money' => ($sku_info['member_price'] - $sku_info['discount_price']) * $sku_info['num'],
                            'used_time' => time()
                        );
                    }

                    //添加秒杀商品到订单商品优惠记录表、订单sku记录表
                    if (!empty($sku_info['seckill_id'])) {
                        $promotion_details[] = array(
                            'order_id' => $order_id,
                            'promotion_id' => $sku_info['seckill_id'],
                            'sku_id' => $sku_info['sku_id'],
                            'promotion_type' => 'SECKILL',
                            'discount_money' => $sku_info['price'],
                            'used_time' => time()
                        );
                        //将秒杀的购买数量存入订单sku记录表
                        $activity_order_sku_record_mdl= new VslActivityOrderSkuRecordModel();
                        $order_sku_data['activity_id'] = $sku_info['seckill_id'];
                        $order_sku_data['uid'] = $order_info['buyer_id'];
                        $order_sku_data['goods_id'] = $sku_info['goods_id'];
                        $order_sku_data['sku_id'] = $sku_info['sku_id'];
                        //1是秒杀
                        $order_sku_data['buy_type'] = 1;
                        $order_sku_data['website_id'] = $order_info['website_id'];
                        //创建时间
                        $order_sku_data['create_time'] = time();
                        //数量，先查出之前买了多少个
                        $activity_os_info = $this->getActivityOrderInfo($order_info['buyer_id'], $sku_info['sku_id'], $order_info['website_id'],1, $sku_info['seckill_id']);
                        if($activity_os_info){
                            $sku_info['num'] = $sku_info['num'] + $activity_os_info['num'];
                            $order_sku_data['num'] = $sku_info['num'];
                            $activity_order_sku_record_mdl->save($order_sku_data,['uid'=>$order_info['buyer_id'], 'sku_id'=>$sku_info['sku_id'], 'website_id'=>$order_info['website_id']]);
                        }else{
                            $order_sku_data['num'] = $sku_info['num'];
                            $activity_order_sku_record_mdl->save($order_sku_data);
                        }
                    }

                    if($order_info['group_id']){
                        //将拼团的购买数量存入订单sku记录表
                        $activity_order_sku_record_mdl= new VslActivityOrderSkuRecordModel();
                        //数量，先查出之前买了多少个
                        $activity_os_info = $this->getActivityOrderInfo($order_info['buyer_id'], $sku_info['sku_id'], $order_info['website_id'],2, $order_info['group_id']);
                        if($activity_os_info){
                            $sku_info['num'] = $sku_info['num'] + $activity_os_info['num'];
                            $order_sku_data['num'] = $sku_info['num'];
                            $activity_order_sku_record_mdl->save($order_sku_data,['order_sku_record_id'=>$activity_os_info['order_sku_record_id']]);
                        }else{
                            $order_sku_data['activity_id'] = $order_info['group_id'];
                            $order_sku_data['uid'] = $order_info['buyer_id'];
                            //2是拼团
                            $order_sku_data['buy_type'] = 2;
                            $order_sku_data['create_time'] = time();
                            $order_sku_data['goods_id'] = $sku_info['goods_id'];
                            $order_sku_data['sku_id'] = $sku_info['sku_id'];
                            $order_sku_data['num'] = $sku_info['num'];
                            $order_sku_data['website_id'] = $order_info['website_id'];
                            $activity_order_sku_record_mdl->save($order_sku_data);
                        }
                    }
                    //预售
                    if($sku_info['presell_id']){
                        //将预售的购买数量存入订单sku记录表
                        $activity_order_sku_record_mdl= new VslActivityOrderSkuRecordModel();
                        //数量，先查出之前买了多少个
                        $activity_os_info = $this->getActivityOrderInfo($order_info['buyer_id'], $sku_info['sku_id'], $order_info['website_id'],4, $sku_info['presell_id']);
                        if($activity_os_info){
                            $sku_info['num'] = $sku_info['num'] + $activity_os_info['num'];
                            $order_sku_data['num'] = $sku_info['num'];
                            $activity_order_sku_record_mdl->save($order_sku_data,['order_sku_record_id'=>$activity_os_info['order_sku_record_id']]);
                        }else{
                            $order_sku_data['activity_id'] = $sku_info['presell_id'];
                            $order_sku_data['uid'] = $order_info['buyer_id'];
                            //4是预售
                            $order_sku_data['buy_type'] = 4;
                            $order_sku_data['create_time'] = time();
                            $order_sku_data['goods_id'] = $sku_info['goods_id'];
                            $order_sku_data['sku_id'] = $sku_info['sku_id'];
                            $order_sku_data['num'] = $sku_info['num'];
                            $order_sku_data['website_id'] = $order_info['website_id'];
                            $activity_order_sku_record_mdl->save($order_sku_data);
                        }
                    }
                    //砍价
                    if($sku_info['bargain_id']){
                        //将拼团的购买数量存入订单sku记录表
                        $activity_order_sku_record_mdl= new VslActivityOrderSkuRecordModel();
                        //数量，先查出之前买了多少个
                        $activity_os_info = $this->getActivityOrderInfo($order_info['buyer_id'], $sku_info['sku_id'], $order_info['website_id'],3, $sku_info['bargain_id']);
                        if($activity_os_info){
                            $sku_info['num'] = $sku_info['num'] + $activity_os_info['num'];
                            $order_sku_data['num'] = $sku_info['num'];
                            $activity_order_sku_record_mdl->save($order_sku_data,['order_sku_record_id'=>$activity_os_info['order_sku_record_id']]);
                        }else{
                            $order_sku_data['activity_id'] = $sku_info['bargain_id'];
                            $order_sku_data['uid'] = $order_info['buyer_id'];
                            //3是砍价
                            $order_sku_data['buy_type'] = 3;
                            $order_sku_data['create_time'] = time();
                            $order_sku_data['goods_id'] = $sku_info['goods_id'];
                            $order_sku_data['sku_id'] = $sku_info['sku_id'];
                            $order_sku_data['num'] = $sku_info['num'];
                            $order_sku_data['website_id'] = $order_info['website_id'];
                            $activity_order_sku_record_mdl->save($order_sku_data);
                        }
                    }
                }
                if (!empty($promotion_details)){
                    $order_goods_promotion_details->saveAll($promotion_details);
                }
            }
            
            // 使用优惠券
            if ($order_info['coupon']['coupon_id'] > 0 && getAddons('coupontype', $this->website_id)) {
                $return_val = $couponServer->useCoupon($order_info['coupon']['coupon_id'], $order_id);
                if ($return_val <= 0) {
                    $this->order->rollback();
                    return $return_val;
                }
            }
            
            /*// 使用积分
            if ($order_info['point'] > 0) {
                $return_val_point = $account_flow->addMemberAccountData(1, $this->uid, 0, $order['point'], 1, $order_id, '商城订单');
                if ($return_val_point < 0) {
                    $this->order->rollback();
                    return ORDER_CREATE_LOW_POINT;
                }
            }*/

            //积分抵扣
             if ($order_info['deduction_point'] > 0) {
                 $return_val_point = $account_flow->addMemberAccountData(1, $order_info['buyer_id'], 0, -$order['deduction_point'], 31, $order_id, '订单创建，积分抵扣');
                 if ($return_val_point < 0) {
                     $this->order->rollback();
                     return ORDER_CREATE_LOW_POINT;
                 }
             }

            if ($order_info['user_money'] > 0) {
                $return_val_user_money = $account_flow->addMemberAccountData(2, $order_info['buyer_id'], 0, $order_info['user_money'], 1, $order_id, '商城订单');
                if ($return_val_user_money < 0) {
                    $this->order->rollback();
                    return ORDER_CREATE_LOW_USER_MONEY;
                }
            }
            if ($order_info['user_platform_money'] > 0) {
                $return_val_platform_money = $account_flow->addMemberAccountData(2, $order_info['buyer_id'], 0, $order_info['user_platform_money'], 1, $order_id, '商城订单，余额支付');
                if ($return_val_platform_money < 0) {
                    $this->order->rollback();
                    return ORDER_CREATE_LOW_PLATFORM_MONEY;
                }
            }
            $this->addOrderAction($order_id, $order_info['buyer_id'], '创建订单');
            $this->order->commit();
            
            return $order_id;

        } catch (\Exception $e) {
            
            recordErrorLog($e);
            $this->order->rollback();
            //return ORDER_CREATE_FAIL;
            return $e->getMessage();
        }
    }
    /**
     * 创建订单(领取)
     * @param array $order_info
     * @return int|mixed
     */
    public function orderCreateReceive(array $order_info)
    {
        $this->order->startTrans();
        try {
            $data_order = array(
                'custom_order'=>$order_info['custom_order'],
                'order_no' => $order_info['order_no'],
                'out_trade_no' => $order_info['out_trade_no'],
                'order_sn' => $order_info['order_sn'],
                'order_from' => $order_info['order_from'],
                'buyer_id' => $order_info['buyer_id'],
                'user_name' => $order_info['nick_name'],
                'buyer_ip' => $order_info['ip'],
                'buyer_message' => $order_info['leave_message'],
                'buyer_invoice' => $order_info['buyer_invoice']?:'',
                'shipping_time' => $order_info['shipping_time']?:0, // datetime NOT NULL COMMENT '买家要求配送时间',
                'receiver_mobile' => $order_info['receiver_mobile'], // varchar(11) NOT NULL DEFAULT '' COMMENT '收货人的手机号码',
                'receiver_province' => $order_info['receiver_province'], // int(11) NOT NULL COMMENT '收货人所在省',
                'receiver_city' => $order_info['receiver_city'], // int(11) NOT NULL COMMENT '收货人所在城市',
                'receiver_district' => $order_info['receiver_district'], // int(11) NOT NULL COMMENT '收货人所在街道',
                'receiver_address' => $order_info['receiver_address'], // varchar(255) NOT NULL DEFAULT '' COMMENT '收货人详细地址',
                'receiver_zip' => $order_info['receiver_zip'], // varchar(6) NOT NULL DEFAULT '' COMMENT '收货人邮编',
                'receiver_name' => $order_info['receiver_name'], // varchar(50) NOT NULL DEFAULT '' COMMENT '收货人姓名',
                'shop_id' => $order_info['shop_id'], // int(11) NOT NULL COMMENT '卖家店铺id',
                'shop_name' => $order_info['shop_name'], // varchar(100) NOT NULL DEFAULT '' COMMENT '卖家店铺名称',
                'point' => 0, // int(11) NOT NULL COMMENT '订单消耗积分',
                'coupon_id' => 0, // int(11) NOT NULL COMMENT '订单代金券id',
                'give_point' => 0, // int(11) NOT NULL COMMENT '订单赠送积分',, // int(11) NOT NULL COMMENT '订单赠送积分',
                'create_time' => $order_info['create_time'],
                'website_id' => $order_info['website_id'],
                'shipping_company_id' => 0,
                'payment_type' => 0,
                'shipping_type' => $order_info['shipping_type'],
                'order_type' => $order_info['order_type'],
                'order_status' => 1, // tinyint(4) NOT NULL COMMENT '订单状态',
                'pay_status' => 2, // tinyint(4) NOT NULL COMMENT '订单付款状态',
                'shipping_status' => 0, // tinyint(4) NOT NULL COMMENT '订单配送状态',
                'review_status' => 0, // tinyint(4) NOT NULL COMMENT '订单评价状态',
                'feedback_status' => 0, // tinyint(4) NOT NULL COMMENT '订单维权状态',
                'give_point_type' => 0,
                'shipping_money' => 0, // decimal(10, 2) NOT NULL COMMENT '订单运费',
                'pay_money' => 0, // decimal(10, 2) NOT NULL COMMENT '订单实付金额',
                'refund_money' => 0, // decimal(10, 2) NOT NULL COMMENT '订单退款金额',
                'goods_money' => $order_info['shop_total_amount'], // decimal(19, 2) NOT NULL COMMENT '商品总价',
                'tax_money' => $order_info['tax_money'], // 税费
                'order_money' => 0, // decimal(10, 2) NOT NULL COMMENT '订单总价',
                'member_money' =>0, // decimal(10,2)会员价总额,
                'buy_type' => $order_info['buy_type']?:0,
                'card_store_id'=>$order_info['card_store_id']?:0
            );
            $order = new VslOrderModel();
            $order->save($data_order);
            $order_id = $order->order_id;
            // 添加订单项
            if($order_info['gift_id']==0){
                $order_goods = new OrderGoods();
                $res_order_goods = $order_goods->addOrderGoodsNew($order_id, $order_info['sku_info'], 0);
                if (!($res_order_goods > 0)) {
                    $this->order->rollback();
                    return $res_order_goods;
                }
            }
            $this->addOrderAction($order_id, $this->uid, '创建订单');
            $this->order->commit();
            return $order_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order->rollback();
            return $e->getMessage();
        }
    }
    /*
     * 查出用户购买活动订单的某个sku数量
     * **/
    public function getActivityOrderSkuNum($uid, $goods_id, $website_id, $type, $activity_id)
    {
        $activity_order_sku_record_mdl = new VslActivityOrderSkuRecordModel();
        $activty_condition['uid'] = $uid;
        $activty_condition['goods_id'] = $goods_id;
        $activty_condition['website_id'] = $website_id;
        $activty_condition['buy_type'] = $type;
        $activty_condition['activity_id'] = $activity_id;
        $activity_og_info = $activity_order_sku_record_mdl->where($activty_condition)->sum('num');
        return $activity_og_info;
    }
    /*
     * 查出用户购买活动订单信息
     * **/
    public function getActivityOrderInfo($uid, $goods_id, $website_id, $type, $activity_id)
    {
        $activity_order_sku_record_mdl = new VslActivityOrderSkuRecordModel();
        $activty_condition['uid'] = $uid;
        $activty_condition['sku_id'] = $goods_id;
        $activty_condition['website_id'] = $website_id;
        $activty_condition['buy_type'] = $type;
        $activty_condition['activity_id'] = $activity_id;
        $activity_og_info = $activity_order_sku_record_mdl->getInfo($activty_condition,'num, order_sku_record_id');
        $activity_og_info = objToArr($activity_og_info);
        if($activity_og_info['order_sku_record_id'])
            return $activity_og_info;
        else
            return '';
    }

    /**
     * 订单支付
     *
     * @param unknown $order_pay_no
     * @param unknown $pay_type (10:线下支付)
     * @param unknown $status
     *            0:订单支付完成 1：订单交易完成
     * @param string $seller_memo
     * @return Exception
     */
    public function OrderPay($order_pay_no, $pay_type, $status)
    {
        $this->order->startTrans();
        try {
            // 改变订单状态
//            $this->order->where([
//                'out_trade_no' => $order_pay_no
//            ])->select();

            // 添加订单日志
            // 可能是多个订单
            $order_id_array = $this->order->where([
                'out_trade_no' => $order_pay_no
            ])->whereOr([
                'out_trade_no_presell' => $order_pay_no
            ])->column('order_id');

            foreach ($order_id_array as $k => $order_id) {
                $order_info = $this->order->getInfo([
                    'order_id' => $order_id
                ], '*');
                if($order_info['order_type'] == 5){
                    if(!getAddons('groupshopping', $order_info['website_id'])){
                        $this->order->rollback();
                        return false;
                    }else{
                        $group_server = new GroupShopping();
                        $checkGroup = $group_server->checkGroupIsCanByOrder($order_info['out_trade_no']);
                        if($checkGroup < 0){
                            $this->order->rollback();
                            return false;
                        }
                    }
                }
                if ($order_info['group_id']) {
                    $group_server = new GroupShopping();
                    $group_server->createGroupRecord($order_id);
                }
                //如果是预售的订单，则第一次不更改其支付状态，只更改money_type字段为1
                if ($order_info['money_type'] == 0 && $order_info['presell_id'] != 0) {
                    if($order_info['payment_type']==16 || $order_info['payment_type']==17){
                        $data_money_type['order_status'] = 0;
                        $data_money_type['pay_status'] = 0;
                    }
                    $data_money_type['money_type'] = 1;
                    $this->order->save($data_money_type,['order_id'=>$order_id]);
                    //将支付方式插入order表
                    $data = array(
                        'payment_type' => $pay_type,
                        'pay_time' => time(),
                        );
                    //尾款为0时
                    if($order_info['final_money']==0){
                        $data['pay_status'] = 2;
                        $data['money_type'] = 2;
                        $data['order_status'] = 1;
                    }
                    $order = new VslOrderModel();
                    $bool = $order->save($data, [
                        'order_id' => $order_id
                    ]);
                    $this->order->commit();
                    return 1;
                }
                if ($order_info['money_type'] == 1 && $order_info['presell_id'] != 0) {
                    $data = array(
                        'payment_type_presell' => $pay_type,
                        'money_type' => 2, //后台支付的时候改变其状态
                        'pay_status' => 2,
                        'pay_time' => time(),
                        'order_status' => 1,
                        'order_money' => $order_info['pay_money'] + $order_info['final_money'] - $order_info['invoice_tax'],//付完尾款将订单金额改为总金额 用于统计;尾款不显示税费
                    );
                    //预售付尾款的时候，判断有没有减上级渠道商的库存，如果有，则需要给上级渠道商结算
                    $order_goods_mdl = new VslOrderGoodsModel();
                    $order_goods_info = $order_goods_mdl->getInfo(['order_id' => $order_id],'channel_stock,sku_id,discount_price');
                    if($order_goods_info['channel_stock']) {
                        $channel_money = 0;
                        $sku_modle = new VslGoodsSkuModel();
                        $sku_info  = $sku_modle->getInfo(['sku_id' => $order_goods_info['sku_id']],'price,market_price');
                        //判断平台开启的是什么零售结算节点
                        $addons_config_model = new AddonsConfigModel();
                        $value = $addons_config_model->Query(['addons' => 'channel','website_id' => $order_info['website_id']],'value')[0];
                        $value = json_decode($value,true);
                        if($value['settle_type'] == 1) {
                            //以商品售价结算 price
                            $channel_money = $sku_info['price'] * $order_goods_info['channel_stock'];
                        }elseif ($value['settle_type'] == 2) {
                            //以商品原价结算 market_price
                            $channel_money = $sku_info['market_price'] * $order_goods_info['channel_stock'];
                        }elseif ($value['settle_type'] == 3) {
                            //以商品实付价结算 discount_price
                            $channel_money = $order_goods_info['discount_price'] * $order_goods_info['channel_stock'];
                        }
                        $data['channel_money'] = $channel_money;
                    }
                    $order = new VslOrderModel();
                    $order->save($data, [
                        'order_id' => $order_id
                    ]);
                }
                //获取order_id的sku_id
                $order_goods_info = $this->order->alias('o')
//                    ->where(['o.order_id'=>$order_id,'o.buy_type'=>2,'og.channel_info'=>['neq',0]])//这里不弄零售，是因为零售可以退款
                    ->where(['o.order_id'=>$order_id])
                    ->join('vsl_order_goods og','og.order_id=o.order_id','LEFT')
                    ->select();
                if ($pay_type == 10) {
                    // 线下支付
                    $this->addOrderAction($order_id, $this->uid, '线下支付');
                } else {
                    // 查询订单购买人ID
                    $this->addOrderAction($order_id, $order_info['buyer_id'], '订单支付');
                }
                // 增加会员累计消费
                $account = new MemberAccount();
                $account->addMmemberConsum(0, $order_info['buyer_id'], $order_info['pay_money']);
                if($order_info['order_type'] != 7){//这里加判断 是因为预售的会更改定金支付状态
                    // 修改订单状态
                    $data = array(
                        'payment_type' => $pay_type,
                        'pay_status' => 2,
                        'pay_time' => time(),
                        'order_status' => 1
                    ); // 订单转为待发货状态
                    $order = new VslOrderModel();
                    $bool = $order->save($data, [
                        'order_id' => $order_id
                    ]);
                }
                //支付完成后，分发满减送的东西
                $this->calculateOrderMansong($order_id);
                //付款成功后，判断当前订单是否是砍价订单
                if ($order_info['bargain_id'] && getAddons('bargain', $order_info['website_id'], $order_info['shop_id'])) {
                    $bargain_record_mdl = new VslBargainRecordModel();
                    $bargain_condition['bargain_id'] = $order_info['bargain_id'];
                    $bargain_condition['uid'] = $order_info['buyer_id'];
                    $change_fields['bargain_status'] = 2;//将状态变为2
                    $change_fields['order_id'] = $order_id;//订单id
                    $bargain_record_mdl->save($change_fields, $bargain_condition);
                }
                //付款成功后，判断当前订单是否是计时/次商品
                if ($order_info['card_store_id']>0 && getAddons('store', $order_info['website_id'], $order_info['shop_id'])) {
                    //消费卡发放
                    $member_card = new MemberCard();
                    $rs = $member_card->saveData($order_id);
                    if($rs){ 
                        // 修改订单状态
                        $order = new VslOrderModel();
                        $order->save(['order_status'=>4,'card_ids'=>$rs], ['order_id' => $order_id]);
                        $ServiceOrder = new ServiceOrder();
                        $ServiceOrder->orderComplete($order_id, 0, 1);
                    }
                }
                //如果是知识付费商品，订单状态直接改为已完成
                if(count($order_goods_info) == 1) {
                    if($order_goods_info[0]['goods_type'] == 4) {
                        $order = new VslOrderModel();
                        $order->save(['order_status' => 4], ['order_id' => $order_id]);
                        $ServiceOrder = new ServiceOrder();
                        $retval = $ServiceOrder->orderComplete($order_id, 0, 1);
                        if($retval == 1) {
                            //此笔订单完成后,查询还有没有同一种知识付费商品的其他待支付订单，如果有，则关闭其他订单
                            $order_goods_model = new VslOrderGoodsModel();
                            $order_goods_condition= [
                                'goods_id' => $order_goods_info[0]['goods_id'],
                                'goods_type'=>$order_goods_info[0]['goods_type'],
                                'buyer_id'=>$order_goods_info[0]['buyer_id'],
                            ];
                            $order_ids = $order_goods_model->getQuery($order_goods_condition,'order_id','');
                            if($order_ids) {
                                foreach ($order_ids as $key => $val) {
                                    $order_status = $order->Query(['order_id' => $val['order_id']],'order_status')[0];
                                    if($order_status == 0) {
                                        //执行订单关闭
                                        $ServiceOrder = new ServiceOrder();
                                        $ServiceOrder->orderClose($val['order_id'],1);
                                    }
                                }
                            }
                        }
                    }
                }
                //将订单商品
                foreach($order_goods_info as $k1=>$og){
                    $goods_calculate = new GoodsCalculate();
//                    if($og['buy_type'] == 2 && $og['channel_info'] != 0 && getAddons('channel', $this->website_id)){
                    if ($og['channel_info'] != 0 && getAddons('channel', $order_info['website_id']) && $og['channel_stock']) {
                        $channel = new Channel();
                        //进行插入channel_order_sku_record
                        $sku_record_mdl = new VslChannelOrderSkuRecordModel();
                        $sku_record_arr['uid'] = $og['buyer_id'];
                        $sku_record_arr['order_id'] = $order_id;
                        $sku_record_arr['order_no'] = $og['order_no'];
                        //获取的渠道商信息
                        $condition_channel['c.website_id'] = $order_info['website_id'];
                        $condition_channel['c.uid'] = $og['buyer_id'];
                        $channel_info = $channel->getMyChannelInfo($condition_channel);
                        $stock_list = $channel->getChannelSkuStore($og['sku_id'], $og['channel_info']);
//                var_dump(objToArr($stock_list));exit;
                        $sku_record_arr['my_channel_id'] = $channel_info['channel_id']?:0;
                        $sku_record_arr['channel_info'] = $og['channel_info'];
                        $sku_record_arr['goods_id'] = $og['goods_id'];
                        $sku_record_arr['sku_id'] = $og['sku_id'];
                        $sku_record_arr['total_num'] = $og['channel_stock'];
                        $sku_record_arr['num'] = $og['channel_stock'];
                        $sku_record_arr['price'] = $og['price'];
                        $sku_record_arr['shipping_fee'] = $og['shipping_fee']?:0;
                        $sku_record_arr['channel_purchase_discount'] = $channel_info['purchase_discount']?:0;
                        $goods_sku_model1 = new VslGoodsSkuModel();
                        $goods_sku_info = $goods_sku_model1->getInfo([
                            'sku_id' => $og['sku_id'],
                        ], '*');
                        $buy_type = ($og['buy_type'] == 2)?2:3;
                        if(!$goods_sku_info){
                            if ($buy_type == 2) {//提货
                                $goods_sku_info['price'] = 0;
                            }
                        }
                        $sku_record_arr['platform_price'] = $goods_sku_info['price'];
                        //我剩余的所有该sku的库存
                        $sku_record_arr['remain_num'] = $stock_list['stock'];

                        if($buy_type == 3){//零售的要获取零售的是哪一批次的
                            //根据当前采购的数量去获取 批次id:num:bili
                            $batch_ratio_record = $channel->getPurchaseBatchRatio($og['channel_info'], $og['channel_stock'], $og['sku_id']);//p1:采购谁  p2:采购数量
                            $sku_record_arr['batch_ratio_record'] = $batch_ratio_record?:'';
                        }
                        $sku_record_arr['buy_type'] = $buy_type;//零售
                        $sku_record_arr['website_id'] = $order_info['website_id'];
                        $sku_record_arr['create_time'] = time();
                        $is_record = $sku_record_mdl->where(['order_no'=>$og['order_no']])->find();
                        if(!$is_record || $buy_type == 2){
                            $id = $sku_record_mdl->save($sku_record_arr);
                        }
                        //增加销量
                        $goods_calculate = new GoodsCalculate();
                        if ($og['channel_info'] && $og['channel_stock']) {
                            $goods_calculate->addChannelGoodsSales($og['goods_id'], $og['channel_stock'], $og['channel_info']);
                            //增加该渠道商sku的销量
                            $goods_calculate->addChannelSkuSales($og['sku_id'], $og['channel_stock'], $og['channel_info']);
                        }
                    }
                    if ($og['seckill_id'] != 0 && getAddons('seckill', $order_info['website_id'])) {
                        $goods_calculate->addSeckillSkuSales($og['seckill_id'], $og['sku_id'], $og['num']);
                    } elseif ($og['bargain_id'] != 0 && getAddons('bargain', $order_info['website_id'])) {
                        $goods_calculate->addBargainSkuSales($og['bargain_id'], $og['goods_id'], $og['num']);
                    } elseif($og['presell_id'] != 0 && getAddons('presell', $order_info['website_id'])){
                        $goods_calculate->addPresellSkuSales($og['presell_id'], $og['goods_id'], $og['num']);
                    } else{
                        if ($og['channel_stock'] == 0) {
                            $goods_calculate->addGoodsSales($og['goods_id'], $og['num']);
                        }else{
                            $goods_calculate->addGoodsSales($og['goods_id'], $og['num'] - $og['channel_stock']);
                        }
                    }
                }
                //如果是门店订单就将订单信息推送到店员端
                if($order_info['store_id'] || $order_info['card_store_id']) {
                    $store_id = $order_info['store_id'] ?: $order_info['card_store_id'];
                    $store_server = new Store();
                    $store_server ->orderMessagePushToClerk($order_info['order_no'], $order_info['order_money'], 1, $store_id, $order_info['shop_id'], $order_info['website_id']);
                }
                if ($status == 1) {
                    $res = $this->orderComplete($order_id);
                    if (!($res > 0)) {
                        $this->order->rollback();
                        return $res;
                    }
                    // 执行订单交易完成
                }
            }
            $this->order->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order->rollback();
            Log::write('订单支付出错' . $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * 添加订单操作日志
     * order_id int(11) NOT NULL COMMENT '订单id',
     * action varchar(255) NOT NULL DEFAULT '' COMMENT '动作内容',
     * uid int(11) NOT NULL DEFAULT 0 COMMENT '操作人id',
     * user_name varchar(50) NOT NULL DEFAULT '' COMMENT '操作人',
     * order_status int(11) NOT NULL COMMENT '订单大状态',
     * order_status_text varchar(255) NOT NULL DEFAULT '' COMMENT '订单状态名称',
     * action_time datetime NOT NULL COMMENT '操作时间',
     * PRIMARY KEY (action_id)
     *
     * @param unknown $order_id
     * @param unknown $uid
     * @param unknown $action_text
     */
    public function addOrderAction($order_id, $uid, $action_text)
    {
        $this->order->startTrans();
        try {
            $order_status = $this->order->getInfo([
                'order_id' => $order_id
            ], 'order_status,website_id');
            if ($uid != 0) {
                $user = new UserModel();
                $user_info = $user->getInfo([
                    'uid' => $uid
                ], 'nick_name,user_name,user_tel');
                $action_name = $user_info['user_name'] ?$user_info['user_name']: $user_info['nick_name'];
                if(empty($action_name)){
                    $action_name = $user_info['user_tel'];
                }
            } else {
                $action_name = 'system';
            }

            $data_log = array(
                'order_id' => $order_id,
                'action' => $action_text,
                'uid' => $uid,
                'user_name' => $action_name,
                'order_status' => $order_status['order_status'],
                'order_status_text' => $this->getOrderStatusName($order_id),
                'action_time' => time(),
                'website_id' => $order_status['website_id']
            );
            $order_action = new VslOrderActionModel();
            $order_action->save($data_log);
            $this->order->commit();
            return $order_action->action_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 获取订单当前状态 名称
     *
     * @param unknown $order_id
     */
    public function getOrderStatusName($order_id)
    {
        $order_status = $this->order->getInfo([
            'order_id' => $order_id
        ], 'order_status');
        $status_array = OrderStatus::getOrderCommonStatus();
        foreach ($status_array as $k => $v) {
            if ($v['status_id'] == $order_status['order_status']) {
                return $v['status_name'];
            }
        }
        return false;
    }

    /**
     * 通过店铺id 得到订单的订单号
     *
     * @param int|string $shop_id
     *
     * @return string $order_no
     */
    public function createOrderNo($shop_id)
    {
        $billno = date('YmdHis') . mt_rand(100000, 999999);
        while (1) {
            $order_model = new VslOrderModel();
            if (!getAddons('channel', $this->website_id)){
                break;
            }
            $channel_order_model = new VslChannelOrderModel();
            $count = $order_model->getCount(['order_no'=>$billno]);
            $count1 = $channel_order_model->getCount(['order_no'=>$billno]);
            if ($count <= 0 && $count1 <= 0) {
                break;
            }
            $billno = date('YmdHis') . mt_rand(100000, 999999);
        }
        return $billno;
    }

    /**
     * 创建订单支付编号
     *
     * @param unknown $order_id
     */
    public function createOutTradeNo()
    {
        $pay_no = new UnifyPay();
        return $pay_no->createOutTradeNo();
    }

    /**
     * 订单重新生成订单号
     *
     * @param unknown $orderid
     */
    public function createNewOutTradeNo($orderid)
    {
        $order = new VslOrderModel();
        $new_no = $this->createOutTradeNo();
        $data = array(
            'out_trade_no' => $new_no
        );
        $retval = $order->save($data, [
            'order_id' => $orderid
        ]);
        if ($retval) {
            return $new_no;
        } else {
            return '';
        }
    }
    /**
     * 订单重新生成订单号
     *
     * @param unknown $orderid
     */
    public function createChannelNewOutTradeNo($orderid)
    {
        $order = new VslChannelOrderModel();
        $new_no = $this->createOutTradeNo();
        $new_no = 'QD'.$new_no;
        $data = array(
            'out_trade_no' => $new_no
        );
        $retval = $order->save($data, [
            'order_id' => $orderid
        ]);
        if ($retval) {
            return $new_no;
        } else {
            return '';
        }
    }

    /**
     * 订单发货(整体发货)(不考虑订单项)
     *
     * @param unknown $orderid
     */
    public function orderDoDelivery($orderid)
    {
        $this->order->startTrans();
        try {
            $order_item = new VslOrderGoodsModel();
            $count = $order_item->getCount([
                'order_id' => $orderid,
                'shipping_status' => 0,
                'refund_status' => array(
                    'ELT',
                    0
                )
            ]);
            if ($count == 0) {
                $data_delivery = array(
                    'shipping_status' => 1,
                    'order_status' => 2,
                    'consign_time' => time()
                );
                $order_model = new VslOrderModel();
                $order_model->save($data_delivery, [
                    'order_id' => $orderid
                ]);
                $this->addOrderAction($orderid, $this->uid, '订单发货');
            }

            $this->order->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);

            $this->order->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 订单收货
     *
     * @param unknown $orderid
     */
    public function OrderTakeDelivery($orderid)
    {
        $this->order->startTrans();
        try {
            $data_take_delivery = array(
                'shipping_status' => 2,
                'order_status' => 3,
                'sign_time' => time()
            );
            $order_model = new VslOrderModel();
            $order_model->save($data_take_delivery, [
                'order_id' => $orderid
            ]);
            $this->addOrderAction($orderid, $this->uid, '订单收货');
            // 判断是否需要在本阶段赠送积分
            $this->giveGoodsOrderPoint($orderid, 2);
            $this->order->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);

            $this->order->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 渠道商订单收货
     *
     * @param unknown $orderid
     */
    public function channellOrderTakeDelivery($orderid)
    {
        $this->order->startTrans();
        try {
            $data_take_delivery = array(
                'shipping_status' => 2,
                'order_status' => 3,
                'sign_time' => time()
            );
            $order_model = new VslchannelOrderModel();
            $order_model->save($data_take_delivery, [
                'order_id' => $orderid
            ]);
            $this->addOrderAction($orderid, $this->uid, '订单收货');
            // 判断是否需要在本阶段赠送积分
//            $this->giveGoodsOrderPoint($orderid, 2);
            $this->order->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);

            $this->order->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 订单自动收货
     *
     * @param unknown $orderid
     */
    public function orderAutoDelivery($orderid)
    {
        $this->order->startTrans();
        try {
            $data_take_delivery = array(
                'shipping_status' => 2,
                'order_status' => 3,
                'sign_time' => time()
            );
            $order_model = new VslOrderModel();
            $order_model->save($data_take_delivery, [
                'order_id' => $orderid
            ]);
            $this->addOrderAction($orderid, 0, '订单自动收货');
            // 判断是否需要在本阶段赠送积分
            $this->giveGoodsOrderPoint($orderid, 2);
            $this->order->commit();
            //发送确认收货消息
            runhook('Notify', 'orderCompleteBySms', ['order_id' => $orderid]);
            runhook('Notify', 'emailSend', ['order_id' => $orderid, 'notify_type' => 'user', 'template_code' => 'confirm_order']);
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);

            $this->order->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 执行订单交易完成
     *
     * @param int $orderid
     * @param array $order_data
     */
    public function orderComplete($order_id)
    {
        try {
            $data_complete = array(
                'order_status' => 4,
                'finish_time' => time()
            );
            $order_model = new VslOrderModel();
            $res = $order_model->save($data_complete, [
                'order_id' => $order_id,
                'order_status' => 3,
            ]);
            if($res){
                //处理加销量
//                $goods_calculate = new GoodsCalculate();
                $order_goods_list = $order_model->alias('o')->where(['o.order_id'=>$order_id])->join('vsl_order_goods og','o.order_id=og.order_id','LEFT')->select();
                foreach($order_goods_list as $k=>$og_info){
                    //暂不在这里插入渠道商零售记录
//                    if(!$og_info['buy_type'] && $og_info['channel_info'] && getAddons('channel', $this->website_id)){//纯渠道商商品 零售
//                        $goods_calculate->addChannelGoodsSales($og_info['goods_id'], $og_info['num'], $og_info['channel_info']);
//                        //增加该渠道商sku的销量
//                        $goods_calculate->addChannelSkuSales($og_info['sku_id'], $og_info['num'], $og_info['channel_info']);
//                        if($og_info['seckill_id'] && getAddons('seckill', $this->website_id, $this->instance_id)){
//                            //加秒杀库存
//                            $goods_calculate->addSeckillSkuSales($og_info['seckill_id'],$og_info['sku_id'], $og_info['num']);
//                        }
//                        //进行插入channel_order_sku_record
//                        $channel = new Channel();
//                        $sku_record_mdl = new VslChannelOrderSkuRecordModel();
//                        $sku_record_arr['uid'] = $og_info['buyer_id'];
//                        $sku_record_arr['order_id'] = $order_id;
//                        $sku_record_arr['order_no'] = $og_info['order_no'];
//                        //获取的渠道商信息
//                        $condition_channel['c.website_id'] = $og_info['website_id'];
//                        $condition_channel['c.uid'] = $og_info['buyer_id'];
//                        $channel_info = $channel->getMyChannelInfo($condition_channel);
//                        $stock_list = $channel->getChannelSkuStore($og_info['sku_id'], $og_info['channel_info'],$og_info['website_id']);
////                var_dump(objToArr($stock_list));exit;
//                        //获取零售该批次的信息
//                        $batch_ratio_record = '';
//                        //根据当前采购的数量去获取 批次id:num:bili
//                        $batch_ratio_record = $channel->getPurchaseBatchRatio($og_info['channel_info'], $og_info['num'], $og_info['sku_id']);//p1:采购谁  p2:采购数量
//                        $sku_record_arr['batch_ratio_record'] = $batch_ratio_record?:'';
//                        $sku_record_arr['my_channel_id'] = $channel_info['channel_id']?:0;
//                        $sku_record_arr['channel_info'] = $og_info['channel_info'];
//                        $sku_record_arr['goods_id'] = $og_info['goods_id'];
//                        $sku_record_arr['sku_id'] = $og_info['sku_id'];
//                        $sku_record_arr['num'] = $og_info['num'];
//                        //查询出平台价
//                        $goods_sku = new VslGoodsSkuModel();
//                        $platform_price = $goods_sku->getInfo(['sku_id'=>$og_info['sku_id']], 'price')['price'];
//                        $sku_record_arr['price'] = $platform_price;
//                        $sku_record_arr['real_money'] = $og_info['real_money'];
//                        $sku_record_arr['shipping_fee'] = $og_info['shipping_fee']?:0;
////                        $sku_record_arr['channel_purchase_discount'] = $channel_info['purchase_discount']?:0;
//                        $goods_sku_model1 = new VslGoodsSkuModel();
//                        $goods_sku_info = $goods_sku_model1->getInfo([
//                            'sku_id' => $og_info['sku_id'],
//                        ], '*');
//                        $sku_record_arr['platform_price'] = $platform_price;
//                        //我剩余的所有该sku的库存
//                        $sku_record_arr['remain_num'] = $stock_list['stock'];
//                        $buy_type = 3;
//                        $sku_record_arr['buy_type'] = $buy_type;//零售
//                        $sku_record_arr['website_id'] = $og_info['website_id'];
//                        $sku_record_arr['create_time'] = time();
//                        $is_record = $sku_record_mdl->where(['order_no'=>$og_info['order_no'],'sku_id'=>$og_info['sku_id']])->find();
//                        if(!$is_record){
//                            $id = $sku_record_mdl->save($sku_record_arr);
//                        }
//                    }else
                    if($og_info['seckill_id'] && getAddons('seckill', $this->website_id, $this->instance_id)){//纯秒杀商品
                        //加秒杀库存
//                        $goods_calculate->addSeckillSkuSales($og_info['seckill_id'],$og_info['sku_id'], $og_info['num']);
//                        $goods_calculate->addGoodsSales($og_info['goods_id'], $og_info['num']);
                    }else{//纯商品
//                        $goods_calculate->addGoodsSales($og_info['goods_id'], $og_info['num']);
                    }
                }
            }
            $order_info = $order_model->getInfo(['order_id'=>$order_id],'*');
            $uid = $order_info['buyer_id'];
            $this->addOrderAction($order_id, $uid, '交易完成');
            //赠送满减相关物品
//            $this->calculateOrderMansong($order_id); //暂时去掉，因为说是支付完成赠送。
            // 判断是否需要在本阶段赠送积分
            $this->giveGoodsOrderPoint($order_id, 1);
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 统计订单完成后赠送用户积分
     *
     * @param unknown $order_id
     */
    private function calculateOrderGivePoint($order_id)
    {
        $point = $this->order->getInfo([
            'order_id' => $order_id
        ], 'shop_id, give_point,buyer_id');
        $member_account = new MemberAccount();
        $member_account->addMemberAccountData(1, $point['buyer_id'], 1, $point['give_point'], 1, $order_id, '订单商品赠送积分');
    }

    /**
     * 订单完成后统计满减送赠送
     *
     * @param unknown $order_id
     */
    public function calculateOrderMansong($order_id)
    {
        $order_info = $this->order->getInfo([
            'order_id' => $order_id
        ], 'shop_id, buyer_id');
        $order_promotion_details = new VslOrderPromotionDetailsModel();
        // 查询满减送活动规则
        $list = $order_promotion_details->getQuery([
            'order_id' => $order_id,
            'promotion_type_id' => 1,
            'promotion_type' => 'MANJIAN'
        ], 'promotion_id,id', '');
        if (!empty($list)) {
            $promotion_mansong_rule = new VslPromotionMansongRuleModel();
            foreach ($list as $k => $v) {
                $mansong_data = $promotion_mansong_rule->getInfo([
                    'rule_id' => $v['promotion_id']
                ], 'give_coupon,gift_id,gift_card_id');
                if (!empty($mansong_data)) {
//                    // 满减送赠送积分
//                    if ($mansong_data['give_point'] != 0) {
//                        $member_account = new MemberAccount();
//                        $member_account->addMemberAccountData(1, $order_info['buyer_id'], 1, $mansong_data['give_point'], 1, $order_id, '订单满减送赠送积分');
//                    }
                    $goods_promotion = [];
                    $goods_promotion['id'] = $v['id'];
                    $promotion_detail = [];
                    // 满减送赠送优惠券
                    if ($mansong_data['give_coupon'] != 0 && getAddons('coupontype', $this->website_id)) {
                        $member_coupon = new CouponServer();
                        $member_coupon->userAchieveCoupon($order_info['buyer_id'], $mansong_data['give_coupon'], 1);
                        $coupon_type_model = new VslCouponTypeModel();
                        $give_coupon = $coupon_type_model::get($mansong_data['give_coupon']);
                        $promotion_detail['coupon'] = $give_coupon ? $give_coupon->toArray() : [];
                    }
                    // 满减送赠送赠品，并且应用在的时候
                    if ($mansong_data['gift_id'] != 0 && getAddons('gift', $this->website_id)){
                        $order = new VslOrderModel();
                        $order_no = $order->getInfo(['order_id'=>$order_id],'order_no')['order_no'];
                        $gift_model = new \addons\gift\model\VslPromotionGiftModel();
                        $promotionGift = $gift_model->getInfo(['promotion_gift_id' => $mansong_data['gift_id'],'stock' => ['>', 0]], 'stock');
                        if($promotionGift){
                            $gift = new Gift();
                            $gift_info['uid'] = $order_info['buyer_id'];
                            $gift_info['type'] = 1;
                            $gift_info['num'] = 1;
                            $gift_info['no'] = $order_no;
                            $gift_info['promotion_gift_id'] = $mansong_data['gift_id'];
                            $gift_info['website_id'] = $order_info['website_id'];
                            $gift->addGiftRecord($gift_info);
                            $give_gift = $gift_model::get($mansong_data['gift_id']);
                            $promotion_detail['gift'] = $give_gift ? $give_gift->toArray() : [];
                        }
                        
                    }
                    // 满减送赠送礼品券
                    if ($mansong_data['gift_card_id'] != 0 && getAddons('giftvoucher', $this->website_id)) {
                        $giftvoucher = new GiftVoucher();
                        $giftvoucher->getUserReceive($order_info['buyer_id'], $mansong_data['gift_card_id'], 1);
                        $gift_voucher_model = new VslGiftVoucherModel();
                        $give_gift_voucher = $gift_voucher_model::get($mansong_data['gift_card_id']);
                        $promotion_detail['gift_voucher'] = $give_gift_voucher ? $give_gift_voucher->toArray() : [];
                    }
                    if (!empty($promotion_detail)){
                        $goods_promotion['remark'] = json_encode($promotion_detail, JSON_UNESCAPED_UNICODE);
                        $order_promotion_details->data($goods_promotion, true)->isUpdate(true)->save();
                    }
                }
            }
        }
    }

    /**
     * 订单执行交易关闭
     *
     * @param unknown $orderid
     * @return Exception
     */
    public function orderClose($orderid, $task_mark = 0)
    {
        
        $this->order->startTrans();
        try {
            $order_info = $this->order->getInfo([
                'order_id' => $orderid
            ], 'order_status,pay_status,point, coupon_id, user_money, buyer_id,shop_id,user_platform_money, coin_money, bargain_id, website_id,deduction_point,shipping_type,store_id,order_type,money_type');
            $data_close = array(
                'order_status' => 5
            );
            $this->order->save($data_close, [
                'order_id' => $orderid
            ]);
            if(getAddons('channel', $order_info['website_id'])){
                //判断订单是否是渠道商零售相关的,是的话就删除这条零售记录
                $cosr_mdl = new VslChannelOrderSkuRecordModel();
                $channel_retail_info = $cosr_mdl->getInfo(['order_id'=>$orderid], "*");
                if($channel_retail_info){
                    $cosr_mdl->where(['order_id'=>$orderid])->delete();
                }
            }
            
            //预售订单 未支付尾款关闭的订单需要退冻结佣金
            $presell = getAddons('presell', $order_info['website_id']);
            
            if($order_info['order_type'] == 7 && $order_info['money_type'] == 1 && $presell){
                
                $Distributor = new DistributorService();
                $Distributor->addCommissionDistributionPresell($orderid); 
            }
            
            $account_flow = new MemberAccount();
//            if ($order_info['order_status'] == 0) {
//                // 会员余额返还
//                if ($order_info['user_money'] > 0) {
//                    $account_flow->addMemberAccountData(2, $order_info['buyer_id'], 1, $order_info['user_money'], 2, $orderid, '订单关闭返还用户余额');
//                }
//                // 平台余额返还
//
//                if ($order_info['user_platform_money'] > 0) {
//                    $account_flow->addMemberAccountData(2, $order_info['buyer_id'], 1, $order_info['user_platform_money'], 2, $orderid, '商城订单关闭返还平台余额');
//                }
//            }
            // 积分返还

            if ($order_info['point'] > 0 && $order_info['order_status'] == 0) {
                $account_flow->addMemberAccountData(1, $order_info['buyer_id'], 1, $order_info['point'], 2, $orderid, '订单关闭返还积分');
            }
            if ($order_info['deduction_point'] > 0 && $order_info['order_status'] == 0) {
                $account_flow->addMemberAccountData(1, $order_info['buyer_id'], 1, $order_info['deduction_point'], 2, $orderid, '订单关闭返还积分');
            }
            if ($order_info['coin_money'] > 0) {
                $coin_convert_rate = $account_flow->getCoinConvertRate();
                $account_flow->addMemberAccountData(3, $order_info['buyer_id'], 1, $order_info['coin_money'] / $coin_convert_rate, 2, $orderid, '订单关闭返还购物币');
            }

            // 优惠券返还
            if ($order_info['coupon_id'] > 0 && getAddons('coupontype', $this->website_id)) {
                $couponServer = new CouponServer();
                $couponServer->UserReturnCoupon($order_info['coupon_id']);
            }
            // 退回库存
            $order_goods = new VslOrderGoodsModel();
            $order_goods_list = $order_goods->getQuery([
                'order_id' => $orderid
            ], '*', '');
            foreach ($order_goods_list as $k => $v) {
                $return_stock = 0;
                $goods_sku_model = new VslGoodsSkuModel();
                $goods_sku_info = $goods_sku_model->getInfo([
                    'sku_id' => $v['sku_id']
                ], 'goods_id, stock, sku_id');
                if ($v['shipping_status'] != 1) {
                    // 卖家未发货
                    $return_stock = 1;
                } else {
                    // 卖家已发货,买家不退货
                    if ($v['refund_type'] == 1) {
                        $return_stock = 0;
                    } else {
                        $return_stock = 1;
                    }
                }
//                // 退货返回库存 定时任务走这里
                if($task_mark){ 
                    if ($return_stock == 1) {
                        $goods_calculate = new GoodsCalculate();
                        if(getAddons('seckill', $this->website_id, $this->instance_id)){
                            //判断是否是秒杀商品，是的话加秒杀活动库存
                            $seckill_server = new Seckill();
                            $order_seckill_list = $seckill_server->orderSkuIsSeckill($orderid, $v['sku_id']);
                        }

                        //渠道商
                        if ($v['channel_info'] && $v['channel_stock']) {
                            //加库存
                            $goods_calculate->addChannelGoodsStock($goods_sku_info['goods_id'], $goods_sku_info['sku_id'], $v['channel_stock'], $v['channel_info']);
                            //减商品销量
                            if ($order_info['pay_status'] == 2) {
                                $goods_calculate->subChannelSales($goods_sku_info['goods_id'], $v['sku_id'], $v['channel_stock'], $v['channel_info']);
                            }
                        }
                        if($order_seckill_list){
                            $seckill_id = $order_seckill_list['promotion_id'];
                            //加秒杀活动库存
                            $seckill_server->addSeckillGoodsStock($seckill_id, $v['sku_id'], $v['num']);
                            //减秒杀商品销量 lgq加
                            if($order_info['pay_status'] == 2){
                                $goods_calculate->subSeckillGoodsSales($seckill_id, $v['sku_id'], $v['num']);
                            }
                        } elseif ($order_info['bargain_id'] && getAddons('bargain', $this->website_id, $this->instance_id)) {//判断是否是砍价的
                            $bargain_server = new Bargain();
                            $bargain_server->addBargainGoodsStock($order_info['bargain_id'],$v['num']);
                            //减秒杀商品销量 lgq加
                            if($order_info['pay_status'] == 2){
                                $goods_calculate->subBargainGoodsSales($order_info['bargain_id'], $goods_sku_info['goods_id'], $v['num']);
                            }
                        }elseif($v['presell_id'] && getAddons('presell', $this->website_id, $this->instance_id)){
                            //去掉购买的记录
                            $presell_cond['activity_id'] = $v['presell_id'];
                            $presell_cond['sku_id'] = $v['sku_id'];
                            $presell_cond['buy_type'] = 4;
                            $presell_cond['website_id'] = $this->website_id;
                            $aosr_mdl = new VslActivityOrderSkuRecordModel();
                            $aosr_list = $aosr_mdl->where($presell_cond)->find();
                            $aosr_list->num = $aosr_list->num - $v['num'];
                            $aosr_list->save();
                            if($order_info['pay_status'] == 2){
                                //减掉销量
                                $presell_mdl = new VslPresellModel();
                                $presell_list = $presell_mdl->where(['id' => $v['presell_id']])->find();
                                $presell_list->presell_sales = ($presell_list->presell_sales - $v['num'] > 0)?$presell_list->presell_sales - $v['num']:0;
                                $presell_list->save();
                            }
                        }else{
                            if ($order_info['shipping_type'] == 1) {
                                //快递配送
                            $data_goods_sku = array(
                                    'stock' => $goods_sku_info['stock'] + $v['num'] - $v['channel_stock']
                            );
                            $goods_sku_model->save($data_goods_sku, [
                                'sku_id' => $v['sku_id']
                            ]);
                            $count = $goods_sku_model->getSum([
                                'goods_id' => $goods_sku_info['goods_id']
                            ], 'stock');
                            // 商品库存增加
                            $goods_model = new VslGoodsModel();
                            $goods_model->save([
                                'stock' => $count
                            ], [
                                'goods_id' => $goods_sku_info['goods_id']
                            ]);
                            if($order_info['pay_status'] == 2){
                                    $goods_calculate->subGoodsSales($goods_sku_info['goods_id'], $v['num'] - $v['channel_stock']);
                            }
                            } elseif ($order_info['shipping_type'] == 2) {
                                //线下自提
                                $store_goods_sku_model = new VslStoreGoodsSkuModel();
                                $store_sku_info = $store_goods_sku_model->getInfo([
                                    'sku_id' => $v['sku_id'], 'store_id' => $order_info['store_id']
                                ], 'goods_id, stock, sku_id');
                                //门店sku表库存增加
                                $data_sku_stock = array(
                                    'stock' => $store_sku_info['stock'] + $v['num']
                                );
                                $store_goods_sku_model->save($data_sku_stock, [
                                    'sku_id' => $v['sku_id'], 'goods_id' => $store_sku_info['goods_id'], 'store_id' => $order_info['store_id']
                                ]);
                                //门店商品表增加库存
                                $store_goods_model = new VslStoreGoodsModel();
                                $store_goods_stock = $store_goods_model->getInfo([
                                    'store_id' => $order_info['store_id'], 'goods_id' => $store_sku_info['goods_id']
                                ], 'stock');
                                $data_goods_stock = [
                                    'stock' => $store_goods_stock['stock'] + $v['num']
                                ];
                                $store_goods_model->save($data_goods_stock, [
                                    'store_id' => $order_info['store_id'], 'goods_id' => $store_sku_info['goods_id']
                                ]);
                                if ($order_info['pay_status'] == 2) {
                                    //减平台上的销量
                                    $goods_calculate->subGoodsSales($goods_sku_info['goods_id'], $v['num']);
                                    //减门店的销量
                                    $goods_sales = $store_goods_model->getInfo(['goods_id' => $store_sku_info['goods_id'], 'store_id' => $order_info['store_id']], 'sales')['sales'];
                                    $data_goods_sales = [
                                        'sales' => $goods_sales - $v['num']
                                    ];
                                    $store_goods_model->save($data_goods_sales, [
                                        'goods_id' => $store_sku_info['goods_id'], 'store_id' => $order_info['store_id']
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            $orderCal = new VslOrderCalculateModel();
            $orderCal->delData(['order_id' => $orderid]);
            $this->addOrderAction($orderid, $order_info['buyer_id'], '交易关闭');
            $this->order->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 订单执行交易关闭
     *
     * @param unknown $orderid
     * @return Exception
     */
    public function channelOrderClose($orderid)
    {
        $order = new VslChannelOrderModel();
        $order->startTrans();
        try {
            $order_info = $order->getInfo([
                'order_id' => $orderid
            ], 'order_status,pay_status, user_money, buyer_id,shop_id');
            $data_close = array(
                'order_status' => 5
            );
//            var_dump($orderid,$data_close);exit;
            $order->save($data_close, [
                'order_id' => $orderid
            ]);
            $account_flow = new MemberAccount();
            if ($order_info['order_status'] == 0) {
                // 会员余额返还
                if ($order_info['user_money'] > 0) {
                    $account_flow->addMemberAccountData(2, $order_info['buyer_id'], 1, $order_info['user_money'], 2, $orderid, '订单关闭返还用户余额');
                }
            }

            // 退回库存
            $order_goods = new VslChannelOrderGoodsModel();
            $order_goods_list = $order_goods->getQuery([
                'order_id' => $orderid
            ], '*', '');
            foreach ($order_goods_list as $k => $v) {
                $return_stock = 0;
                $goods_sku_model = new VslGoodsSkuModel();
                $goods_sku_info = $goods_sku_model->getInfo([
                    'sku_id' => $v['sku_id']
                ], 'goods_id, stock, sku_id');
                if ($v['shipping_status'] != 1) {
                    // 卖家未发货
                    $return_stock = 1;
                } else {
                    // 卖家已发货,买家不退货
                    if ($v['refund_type'] == 1) {
                        $return_stock = 0;
                    } else {
                        $return_stock = 1;
                    }
                }
                // 退货返回库存
                if ($return_stock == 1) {
                    $goods_calculate = new GoodsCalculate();
                    //如果是渠道商订单
                    if($v['channel_info'] != 'platform'){
                        //加库存
                        $goods_calculate->addChannelGoodsStock($goods_sku_info['goods_id'], $goods_sku_info['sku_id'], $v['num'], $v['channel_info']);
//                        //减销量
//                        $goods_calculate->subChannelSales($goods_sku_info['goods_id'], $goods_sku_info['sku_id'], $v['num'], $v['channel_info']);
                    }else{
                        $data_goods_sku = array(
                            'stock' => $goods_sku_info['stock'] + $v['num']
                        );
                        $goods_sku_model->save($data_goods_sku, [
                            'sku_id' => $v['sku_id']
                        ]);
                        $count = $goods_sku_model->getSum([
                            'goods_id' => $goods_sku_info['goods_id']
                        ], 'stock');
                        // 商品库存增加
                        $goods_model = new VslGoodsModel();
                        $goods_model->save([
                            'stock' => $count
                        ], [
                            'goods_id' => $goods_sku_info['goods_id']
                        ]);
//                        $goods_calculate->subGoodsSales($goods_sku_info['goods_id'], $v['num']);
                    }

                }
            }
            $this->addOrderAction($orderid, $this->uid, '渠道商订单交易关闭');
            $order->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            Log::write($e->getMessage());
            $order->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 订单状态变更
     *
     * @param unknown $order_id
     * @param unknown $order_goods_id
     */
    public function orderGoodsRefundFinish($order_id,$order_goods_id='')
    {
        $order_model = new VslOrderModel();
        $orderInfo = $order_model::get($order_id);
        $order_model->startTrans();
        try {
            $order_goods_model = new VslOrderGoodsModel();
            $refunding_count = $order_goods_model->where("order_id=$order_id AND refund_status != 5 AND refund_status>0")->count();
            $total_count = $order_goods_model->where("order_id=$order_id")->count();

            $refunded_count = $order_goods_model->where("order_id=$order_id AND refund_status=5")->count();
            $shipping_status = $orderInfo->shipping_status;
            $order_status = $orderInfo->order_status;
            $all_refund = 0;
            if ($refunded_count == $total_count) {
                $all_refund = 1;

            } elseif (($refunding_count + $refunded_count) == $total_count) {
                // 全部订单商品参与过售后，订单状态才是售后
                $orderInfo->order_status = OrderStatus::getOrderCommonStatus()[-1]['status_id']; // 售后中

            } elseif ($shipping_status == OrderStatus::getShippingStatus()[0]['shipping_status']) {
                $orderInfo->order_status = OrderStatus::getOrderCommonStatus()[1]['status_id']; // 待发货

            } elseif ($shipping_status == OrderStatus::getShippingStatus()[1]['shipping_status']) {
                $orderInfo->order_status = OrderStatus::getOrderCommonStatus()[2]['status_id']; // 已发货

            } elseif ($shipping_status == OrderStatus::getShippingStatus()[2]['shipping_status']) {
                $orderInfo->order_status = OrderStatus::getOrderCommonStatus()[3]['status_id']; // 已收货

            }
            //如果是最后一条售后处理，处理完才变更订单状态，非最后一条不予处理
            $refund_count = $order_goods_model->where("order_id=$order_id AND refund_status > 0 and refund_status < 5 ")->count();
            if($order_goods_id && $refund_count == 0 && $total_count > 1){
                foreach ($order_goods_id as $key => $value) {
                    $order_info = $order_goods_model->getInfo(['order_id' => $order_id,'order_goods_id' => $value], 'order_status'); //->count()
                    debugLog($order_info, '==>order_goods_id<==');
                    debugLog($order_status, '==>order_status<==');
                   //获取当前子商品状态
                   if($order_status == 2 && $order_info['order_status'] < 2){
                        $orderInfo->order_status = 1; //已发货状态修改为代发货状态
                   }
                }
            }
            // 订单恢复正常操作
            if ($all_refund == 0) {
                $retval = $orderInfo->save();
//                if ($refunding_count == 0) {
//                    $this->orderDoDelivery($order_id);
//                }
            } else {
                // 全部退款订单转化为交易关闭
                $retval = $this->orderClose($order_id);
            }
            $order_model->commit();
            return $retval;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $order_model->rollback();
            return $e->getMessage();
        }
        return $retval;
    }

    /**
     * 获取订单详情
     *
     * @param unknown $order_id
     */
    public function getDetail($order_id, $channel_status='')
    {
        $order_detail = $this->order->getInfo([
            "order_id" => $order_id,
            "is_deleted" => 0,
            "website_id" => $this->website_id
        ]);
        if (empty($order_detail)) {
            return array();
        }
        if(getAddons('shop', $this->website_id)){
            $shop = new VslShopModel();
            $detail = $shop->getInfo(['shop_id' => (int)$order_detail['shop_id'], 'website_id' => $this->website_id], 'shop_phone,shop_name');
            $order_detail['shop_name'] = $detail['shop_name'];
            $order_detail['shop_phone'] = $detail['shop_phone'];
            if(!$order_detail['shop_id']){
                $shop_return = new VslOrderShopReturnModel();
                $order_detail['shop_phone'] = $shop_return->getInfo(['website_id' => $this->website_id,'shop_id' => $order_detail['shop_id'],'is_default' => 1],'mobile')['mobile"'];
            }
        }else{
            $shop_return = new VslOrderShopReturnModel();
            $order_detail['shop_name'] = '自营店';
            $order_detail['shop_phone'] = $shop_return->getInfo(['website_id' => $this->website_id,'shop_id' => $order_detail['shop_id'],'is_default' => 1],'mobile')['mobile"'];
        }
        // 发票信息
        $temp_array = array();
        if ($order_detail["buyer_invoice"] != "") {
            $temp_array = explode("$", $order_detail["buyer_invoice"]);
        }
        $order_detail["buyer_invoice_info"] = $temp_array;
        if (empty($order_detail)) {
            return '';
        }
        $isGroupSuccess = 0;
        if(getAddons('groupshopping', $this->website_id, $this->instance_id)){
            $group_server = new GroupShopping();
            $isGroupSuccess = $group_server->groupRecordDetail($order_detail['group_record_id'])['status'];
        }
        $order_detail['payment_type_name'] = OrderStatus::getPayType($order_detail['payment_type']);
        if($order_detail['order_type'] == 10 && !(int)$order_detail['order_money']){//如果类型为积分兑换订单并且只用积分兑换的，则不显示支付方式
            $order_detail['payment_type_name'] = '';
        }
        $order_detail['payment_type_presell_name'] = OrderStatus::getPayType($order_detail['payment_type_presell']);
        $express_company_name = "";
        if ($order_detail['shipping_type'] == 1) {
            $order_detail['shipping_type_name'] = '商家配送';
            $express_company = new VslOrderExpressCompanyModel();

            $express_obj = $express_company->getInfo([
                "co_id" => $order_detail["shipping_company_id"]
            ], "company_name");
            if (!empty($express_obj["company_name"])) {
                $express_company_name = $express_obj["company_name"];
            }
        } elseif ($order_detail['shipping_type'] == 2) {
            $order_detail['shipping_type_name'] = '门店自提';
        } else {
            $order_detail['shipping_type_name'] = '';
        }
        $order_detail["shipping_company_name"] = $express_company_name;
        // 查询订单项表
        $order_detail['order_goods'] = $this->getOrderGoods($order_id, $channel_status);
//        p($order_detail['order_goods']);
        //查出该单是否是预售的
        if ($order_detail['presell_id'] != 0) {
            $price = $order_detail['order_goods'][0]['price'];
            $num = $order_detail['order_goods'][0]['num'];
            $goods_price = $price*$num;
//            $order_price = $goods_price + $order_detail['shipping_money'];
            $order_detail['goods_money'] = $goods_price;
            $order_detail['can_presell_pay'] = '';
            $order_detail['can_presell_pay_reason'] = '';
            if($order_detail['order_type'] == 7 && $order_detail['money_type'] == 0){//预售订单付定金
                $order_detail['first_money'] = $order_detail['pay_money'];
                $order_detail['final_money'] = $order_detail['final_money'];
//                $order_detail['order_money'] = $order_detail['order_money'];
                $order_detail['presell_status'] = 0;//未付定金
            }elseif($order_detail['order_type'] == 7 && $order_detail['money_type'] == 1){//预售付尾款
                $presell_mdl = new VslPresellModel();
                $presell_info = $presell_mdl->getInfo(['id' => $order_detail['presell_id']], 'pay_start_time, pay_end_time');
                if (time()<=$presell_info['pay_end_time'] && time()>=$presell_info['pay_start_time']) {
                    $order_detail['can_presell_pay'] = 1;
                }else{
                    $order_detail['can_presell_pay'] = 0;
                    $order_detail['can_presell_pay_reason'] = '预售订单付尾款时间：'.date('Y-m-d H:i:s',$presell_info['pay_start_time']).'-'.date('Y-m-d H:i:s',$presell_info['pay_end_time']);
                }
                $order_detail['first_money'] = $order_detail['pay_money'];
                $order_detail['final_money'] = $order_detail['final_money'];
//                $order_detail['pay_money'] = $order_detail['pay_money'];
//                $order_detail['order_money'] = $order_detail['final_money'];
                $order_detail['presell_status'] = 1;//已付定金
            }elseif($order_detail['order_type'] == 7 && $order_detail['money_type'] == 2){//预售付完尾款了
                $order_detail['first_money'] = $order_detail['pay_money'];
                $order_detail['final_money'] = $order_detail['final_money'];
                $order_detail['pay_money'] = $order_detail['final_money'] + $order_detail['pay_money'];
                $order_detail['order_money'] = $order_detail['final_money'] + $order_detail['first_money'];
                $order_detail['presell_status'] = 2;//已付尾款
            }
        }
        if ($order_detail['payment_type'] == 6 || $order_detail['shipping_type'] == 2) {
            $order_status = OrderStatus::getSinceOrderStatus();
        } else {
            // 查询操作项
            $order_status = OrderStatus::getOrderCommonStatus($order_detail['order_type'],$isGroupSuccess, $order_detail['card_store_id'], $order_detail['order_goods'] ? $order_detail['order_goods'][0]['goods_type'] : 0);
        }
//
        // 查询订单提货信息表
        if ($order_detail['shipping_type'] == 2) {
            $storeModel = new VslStoreModel();
            $store_id = ($order_detail['card_store_id']>0)?$order_detail['card_store_id']:$order_detail['store_id'];
            $store = $storeModel->getInfo([
                'store_id' => $store_id,
                'shop_id' => $order_detail['shop_id'],
                'website_id' => $order_detail['website_id']
            ], 'province_id,city_id,district_id,address,store_name,store_tel');
            $address = new Address();
            $store['province_name'] = $address->getProvinceName($store['province_id']);
            $store['city_name'] = $address->getCityName($store['city_id']);
            $store['dictrict_name'] = $address->getDistrictName($store['district_id']);
            $store['store_name'] = $store['store_name'];
            $store['store_tel'] = $store['store_tel'];
            $store['address'] = $store['address'];
            $order_detail['order_pickup'] = $store;
            $user = new UserModel();
            $order_detail['order_pickup']['user_tel'] = $user->getInfo(['uid' => $order_detail['buyer_id']],'user_tel')['user_tel'];
        } else {
            $order_detail['order_pickup'] = [];
        }
        // 查询订单操作
        foreach ($order_status as $k_status => $v_status) {

            //代付款，删除修改价格
            if($order_detail['pay_status']=='0' && $order_detail['presell_id']>0){
                unset($v_status['operation']['1']);
            }

            //已付定金，删除发货
            if($order_detail['pay_status']=='2' && $order_detail['money_type']=='1'){
                unset($v_status['operation']['0']);
            }

            if ($v_status['status_id'] == $order_detail['order_status']) {
                $order_detail['operation'] = $v_status['operation'];
                $order_detail['status_name'] = $v_status['status_name'];
            }


        }
        // 查询订单操作日志
        $order_action = new VslOrderActionModel();
        $order_detail['order_action'] = $order_action->getQuery(['order_id' => $order_id], '*', 'action_time desc,action_id desc'); 
        
        if($order_detail['order_action']){
            foreach($order_detail['order_action'] as $koa => $voa){
                if(!$voa['uid']){
                    continue;
                }
                $user = new UserModel();
                $order_detail['order_action'][$koa]['user_name'] = $user->getInfo(['uid' => $voa['uid']],'user_tel')['user_tel'];
            }
        }
        
        // 查询订单退款操作
        $order_refund = new VslOrderRefundModel();
        foreach($order_detail['order_goods'] as $kg => $vg){
            $order_detail['order_refund'][$vg['order_goods_id']] = $order_refund->getQuery(['order_goods_id' => $vg['order_goods_id']], '*', 'action_time desc,id desc');
            if($order_detail['order_refund'][$vg['order_goods_id']]){
                foreach($order_detail['order_refund'][$vg['order_goods_id']] as $koa => $voa){
                    $order_detail['order_refund'][$vg['order_goods_id']][$koa]['goods_name'] = $vg['goods_name'];
                    if(!$voa['action_userid']){
                        $order_detail['order_refund'][$vg['order_goods_id']][$koa]['user_name'] = '自动退款';
                        continue;
                    }
                    $user = new UserModel();
                    $order_detail['order_refund'][$vg['order_goods_id']][$koa]['user_name'] = $user->getInfo(['uid' => $voa['action_userid']],'user_tel')['user_tel'];  
                }
            }
        }
        $address_service = new Address();
        $order_detail['address'] = $address_service->getAddress($order_detail['receiver_province'], $order_detail['receiver_city'], $order_detail['receiver_district']);
        $order_detail['address'] .= $order_detail["receiver_address"];
        return $order_detail;
    }

    /**
     * 查询订单的订单项列表
     *
     * @param unknown $order_id
     */
    public function getOrderGoods($order_id, $channel_status = '')
    {
        $order_goods = new VslOrderGoodsModel();
        $goods_sku = new VslGoodsSkuModel();
        if(!$channel_status){
            $order_goods_list = $order_goods->all(['order_id' => $order_id]);
        }elseif($channel_status == 'channel_retail'){
            $order_goods_list = $order_goods->all(['order_id' => $order_id, 'channel_info'=>['neq',0]]);
        }
        foreach ($order_goods_list as $k => $v) {

            // 查询商品sku表开始

            $goods_sku_info = $goods_sku->getInfo([
                'sku_id' => $v['sku_id']
            ], 'code');
            $order_goods_list[$k]['code'] = $goods_sku_info['code'];
            $order_goods_list[$k]['spec'] = [];
            if ($v['sku_attr']) {
                $order_goods_list[$k]['spec'] = json_decode(html_entity_decode($v['sku_attr']), true);
            }
            // 查询商品sku结束
            $order_goods_list[$k]['express_info'] = $this->getOrderGoodsExpress($v['order_goods_id']);
            $shipping_status_array = OrderStatus::getShippingStatus();
            foreach ($shipping_status_array as $k_status => $v_status) {
                if ($v['shipping_status'] == $v_status['shipping_status']) {
                    $order_goods_list[$k]['shipping_status_name'] = $v_status['status_name'];
                }
            }
            // 商品图片
            $picture = new AlbumPictureModel();
            $picture_info = $picture->get($v['goods_picture']);
            if (empty($picture_info)){
                $picture_info['pic_cover'] = '';
                $picture_info['pic_cover_micro'] = '';
                $picture_info['pic_cover_big'] = '';
                $picture_info['pic_cover_small'] = '';
            }
            $order_goods_list[$k]['picture_info'] = $picture_info;
            if ($v['refund_status'] != 0) {
                $order_refund_status = OrderStatus::getRefundStatus();
                foreach ($order_refund_status as $k_status => $v_status) {

                    if ($v_status['status_id'] == $v['refund_status']) {
                        $order_goods_list[$k]['refund_operation'] = $v_status['refund_operation'];
                        $order_goods_list[$k]['status_name'] = $v_status['status_name'];
                    }
                }
            } else {
                $order_goods_list[$k]['refund_operation'] = '';
                $order_goods_list[$k]['status_name'] = '';
            }
        }

        return $order_goods_list;
    }

    /**
     * 获取订单的物流信息
     *
     * @param unknown $order_id
     */
    public function getOrderExpress($order_id)
    {
        $order_goods_express = new VslOrderGoodsExpressModel();
        $order_express_list = $order_goods_express->all([
            'order_id' => $order_id
        ]);
        return $order_express_list;
    }

    /**
     * 获取订单项的物流信息
     *
     * @param unknown $order_goods_id
     * @return multitype:|Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:, array>
     */
    private function getOrderGoodsExpress($order_goods_id)
    {
        $order_goods = new VslOrderGoodsModel();
        $order_goods_info = $order_goods->getInfo([
            'order_goods_id' => $order_goods_id
        ], 'order_id,shipping_status');
        if ($order_goods_info['shipping_status'] == 0) {
            return array();
        } else {
            $order_express_list = $this->getOrderExpress($order_goods_info['order_id']);
            foreach ($order_express_list as $k => $v) {
                $order_goods_id_array = explode(",", $v['order_goods_id_array']);
                if (in_array($order_goods_id, $order_goods_id_array)) {
                    return $v;
                }
            }
            return array();
        }
    }

    /**
     * 订单价格调整
     *
     * @param int $order_id
     * @param float $order_adjust_money
     *            调整的商品总额
     * @param float $shipping_fee
     *            调整后的运费
     */
    public function orderAdjustMoney($order_id, $order_adjust_money, $shipping_fee)
    {
        $this->order->startTrans();
        try {
            $order_model = new VslOrderModel();
            $order_info = $order_model->getInfo([
                'order_id' => $order_id
            ], 'goods_money,shipping_money,order_money,pay_money,promotion_free_shipping,shop_order_money,shop_id');

            // 邮费金额差额
            $shipping_fee_adjust = $shipping_fee - ($order_info['shipping_money'] - $order_info['promotion_free_shipping']);
            $order_money = $order_info['order_money'] + $order_adjust_money + $shipping_fee_adjust;
            $pay_money = $order_info['pay_money'] + $order_adjust_money + $shipping_fee_adjust;
            //调整价格后修改店铺到账金额
            $shop_order_money['shop_order_money'] = $order_info['shop_order_money'] ? $order_info['shop_order_money'] : 0;
            if($order_info['shop_id'] > 0){
                $shop_order_money['shop_order_money'] = $shop_order_money['shop_order_money'] + $order_adjust_money + $shipping_fee_adjust;
                if($shop_order_money['shop_order_money'] < 0){
                    $shop_order_money['shop_order_money'] = 0;
                }
            }
            $data = array(
//                'goods_money' => $goods_money,
                'order_money' => $order_money,
                'shipping_money' => $shipping_fee + $order_info['promotion_free_shipping'],
                'pay_money' => $pay_money,
                'shop_order_money' => $shop_order_money['shop_order_money']
            );
//            var_dump($data);exit;
            $retval = $order_model->save($data, [
                'order_id' => $order_id
            ]);
            $this->addOrderAction($order_id, $this->uid, '调整金额');
            // 修改税费
            if (getAddons('invoice', $this->website_id, $this->instance_id)) {
                $invoice = new InvoiceServer();
                $invoice->changeInvoiceTaxByOrderId($order_id);
            }
            $this->order->commit();
            return $retval;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order->rollback();
            return $e;
        }
    }

    /**
     * 获取订单整体商品金额(根据订单项)
     *
     * @param unknown $order_id
     */
    public function getOrderGoodsMoney($order_id)
    {
        $order_goods = new VslOrderGoodsModel();
        $money = $order_goods->getSum([
            'order_id' => $order_id
        ], 'goods_money');
        if (empty($money)) {
            $money = 0;
        }
        return $money;
    }

    /**
     * 获取具体订单项信息
     *
     * @param unknown $order_goods_id
     *            订单项ID
     */
    public function getOrderGoodsInfo($order_goods_id)
    {
        $order_goods = new VslOrderGoodsModel();
        return $order_goods->getInfo([
            'order_goods_id' => $order_goods_id
        ], 'goods_id,goods_name,goods_money,goods_picture,shop_id,website_id');
    }

    /**
     * 通过订单id 得到该订单的实际支付金额
     *
     * @param unknown $order_id
     */
    public function getOrderRealPayMoney($order_id)
    {
        $order_model = new VslOrderModel();
        $order_info = $order_model::get($order_id);
        if ($order_info) {
            return $order_info['order_money'];
        } else {
            return 0;
        }
    }
    /**
     * 通过订单id 得到该订单的实际店铺到账金额
     *
     * @param unknown $order_id
     */
    public function getOrderRealPayShopMoney($order_id)
    {
        $order_model = new VslOrderModel();
        $order_info = $order_model::get($order_id);
        if ($order_info) {
            return $order_info['shop_order_money'];
        } else {
            return 0;
        }
    }

    /**
     * 订单发放积分
     *
     * @param unknown $order_id
     */
    public function giveGoodsOrderPoint($order_id, $type)
    {
        // 判断是否需要在本阶段赠送积分
        $order_model = new VslOrderModel();
        $order_info = $order_model->getInfo([
            "order_id" => $order_id
        ], "give_point_type,shop_id,buyer_id,give_point,buyer_id");
        $member_account_records = new VslMemberAccountRecordsModel();
        $id = $member_account_records->getInfo(['uid' => $order_info['buyer_id'], 'website_id' => $this->website_id,'data_id'=>$order_id,'sign'=>1], 'id');
        if ($order_info["give_point_type"] == $type && empty($id)) {
            if ($order_info["give_point"] > 0) {
                $member_account = new MemberAccount();
                $text = "";
                if ($order_info["give_point_type"] == 1) {
                    $text = "商城订单完成赠送积分";
                } elseif ($order_info["give_point_type"] == 2) {
                    $text = "商城订单完成收货赠送积分";
                } elseif ($order_info["give_point_type"] == 3) {
                    $text = "商城订单完成支付赠送积分";
                }
                $member_account->addMemberAccountData(1, $order_info['buyer_id'], 1, $order_info['give_point'], 1, $order_id, $text);
            }
        }
    }

    /**
     * 添加订单退款账号记录
     *
     * {@inheritdoc}
     *
     * @see \data\api\IOrder::addOrderRefundAccountRecords()
     */
    public function addOrderRefundAccountRecords($order_goods_id, $refund_trade_no, $refund_money, $refund_way, $buyer_id, $remark)
    {
        $model = new VslOrderRefundAccountRecordsModel();

        $data = array(
            'order_goods_id' => $order_goods_id,
            'refund_trade_no' => $refund_trade_no,
            'refund_money' => $refund_money,
            'refund_way' => $refund_way,
            'buyer_id' => $buyer_id,
            'refund_time' => time(),
            'website_id' => $this->website_id,
            'remark' => $remark
        );
        $res = $model->save($data);
        return $res;
    }
    
    /**
     * 订单提货
     *
     * @param unknown $order_id
     */
    public function pickupOrder($order_id, $assistantId)
    {
        // 订单转为已收货状态
        $checked = $this->order->getInfo(['order_id' => $order_id],'order_id');
        if(!$checked){
            return 0;
        }
        $this->order->startTrans();
        try {
            $data_take_delivery = array(
                'shipping_status' => 2,
                'order_status' => 3,
                'sign_time' => time(),
                'assistant_id' => $assistantId
            );
            $order_model = new VslOrderModel();
            $order_model->save($data_take_delivery, [
                'order_id' => $order_id
            ]);
            $this->addOrderAction($order_id, $this->uid, '订单提货');
            $order_goods_model = new VslOrderGoodsModel();
            $order_goods_model->save([
                'shipping_status' => 2
            ], [
                'order_id' => $order_id
            ]);
            $this->order->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->order->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * 获取订单类型列表
     *
     * @param unknown $type_id
     */
    public function getOrderTypeList()
    {
        $order_type = array(
            array(
                'type_id' => '1',
                'type_name' => '普通订单'
            )
        );
        if (getAddons('microshop', $this->website_id, $this->instance_id)) {
            $order_type = array_merge($order_type, array(
                array(
                    'type_id' => '2',
                    'type_name' => '成为店主'
                ),
                array(
                    'type_id' => '3',
                    'type_name' => '店主续费'
                ),
                array(
                    'type_id' => '4',
                    'type_name' => '店主升级'
                )
            ));
        }
        if (getAddons('groupshopping', $this->website_id, $this->instance_id)) {
            $order_type = array_merge($order_type, array(
                array(
                    'type_id' => '5',
                    'type_name' => '拼团订单'
                )
            ));
        }
        if (getAddons('seckill', $this->website_id, $this->instance_id)) {
            $order_type = array_merge($order_type, array(
                array(
                    'type_id' => '6',
                    'type_name' => '秒杀订单'
                )
            ));
        }
        if (getAddons('presell', $this->website_id, $this->instance_id)) {
            $order_type = array_merge($order_type, array(
                array(
                    'type_id' => '7',
                    'type_name' => '预售订单'
                )
            ));
        }
        if (getAddons('bargain', $this->website_id, $this->instance_id)) {
            $order_type = array_merge($order_type, array(
                array(
                    'type_id' => '8',
                    'type_name' => '砍价订单'
                )
            ));
        }
        if (getAddons('smashegg', $this->website_id, $this->instance_id) || getAddons('scratchcard', $this->website_id, $this->instance_id) || getAddons('wheelsurf', $this->website_id, $this->instance_id) || getAddons('paygift', $this->website_id, $this->instance_id) || getAddons('followgift', $this->website_id) || getAddons('festivalcare', $this->website_id)) {
            $order_type = array_merge($order_type, array(
                array(
                    'type_id' => '9',
                    'type_name' => '奖品订单'
                )
            ));
        }
        if (getAddons('integral', $this->website_id, $this->instance_id)) {
            $order_type = array_merge($order_type, array(
                array(
                    'type_id' => '10',
                    'type_name' => '兑换订单'
                )
            ));
        }
        return $order_type;
    }


    /**
     * 订单积分抵扣计算
     *
     */
    public function pointDeductionOrder($sku_lists, $is_deduction, $shipping_type = 2, $website_id = '', $uid = '',$sub_point = 0, $discount = 0, $manual_amout = 0)
    {
        $config = new Config();
        
        if (!$website_id) $website_id = $this->website_id;
        if (!$uid) $uid = $this->uid;
        $info = $config->getShopConfig(0, $website_id);
        $member = new MemberService();
        $member_account = $member->getMemberAccount($uid, $website_id);
        $data = [];
        $member_account['point'] = $member_account['point'] - $sub_point; //多店铺抵扣时限制
        $data['total_deduction_money'] = $total_deduction_money = $total_price = 0;
        $data['total_deduction_point'] = $total_deduction_point = 0;
        
        if ($sku_lists && $info['is_point_deduction'] == 1 && $is_deduction == 1 && $member_account['point'] > 0) {
            foreach ($sku_lists as $sku_id => $sku_info) {
                $sku_lists[$sku_id]['deduction_money'] = 0;
                $sku_lists[$sku_id]['deduction_point'] = 0;
                $sku_lists[$sku_id]['deduction_freight_point'] = 0;
                $real_money = $sku_info['discount_price'] * $sku_info['num'];
                if (!empty($sku_info['coupon_sku_percent_amount']) && getAddons('coupontype', $this->website_id)) {
                    $real_money -= $sku_info['coupon_sku_percent_amount'];
                }
                if (!empty($sku_info['full_cut_sku_percent']) && !empty($sku_info['full_cut_sku_amount'])) {
                    $real_money -= $sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'];
                }
                if (!empty($sku_info['presell_id'])) {
                    $real_money = $sku_info['price'] * $sku_info['num'];
                }
                if ($shipping_type == 1) {
                    $real_money = $real_money + $sku_info['shipping_fee'];
                }
                
                $real_money = ($real_money > 0) ? $real_money : 0;
                $sku_lists[$sku_id]['real_money'] = $real_money;
                if ($sku_info['point_deduction_max'] > 0 || $sku_info['point_deduction_max'] == '') {
                    $price = 0;
                    if ($info['point_deduction_calculation'] == 1) {//订单金额
                        if ($shipping_type == 1) {
//                            $price = $sku_info['num'] * ($sku_info['price'] + $sku_info['shipping_fee']);
                            $price = $sku_info['num'] * $sku_info['price'] + $sku_info['shipping_fee'];
                        } else {
                            $price = $sku_info['num'] * $sku_info['price'];
                        }
                    } elseif ($info['point_deduction_calculation'] == 2) {//商品金额
                        $price = $sku_info['num'] * $sku_info['price'];
                    } elseif ($info['point_deduction_calculation'] == 3) {//支付金额
                        $price = $real_money;
                    }
                    
                    if ($sku_info['point_deduction_max'] > 0) {
                        $deduction_money = $price * $sku_info['point_deduction_max'] / 100;
                    } else {
                        $deduction_money = $price * $info['point_deduction_max'] / 100;
                    }
                    
                    $sku_lists[$sku_id]['deduction_money'] = round(floor($deduction_money * $info['convert_rate']) / $info['convert_rate'], 2);
                    $sku_lists[$sku_id]['deduction_point'] = floor($deduction_money * $info['convert_rate']);
                    //运费抵扣的积分
                    if($shipping_type == 1 && $sku_info['shipping_fee']>0){
                        $deduction_freight_money = $real_money - $sku_info['shipping_fee'] - $sku_lists[$sku_id]['deduction_money'];
                        if($deduction_freight_money<0 && ($info['point_deduction_calculation'] == 1 || $info['point_deduction_calculation'] == 3)){
                            $sku_lists[$sku_id]['deduction_freight_point'] = floor((0 - $deduction_freight_money) * $info['convert_rate']);
                        }
                    }
                }
                $total_price += $real_money;
                $total_deduction_money += $sku_lists[$sku_id]['deduction_money'];
                $total_deduction_point += $sku_lists[$sku_id]['deduction_point'];
            }
            if($manual_amout){
                $total_price = $total_price > $manual_amout ?  round($total_price - $manual_amout, 2) : 0;
                $total_deduction_money = $total_price > $total_deduction_money ?  $total_deduction_money : $total_price;
                $total_deduction_point = floor($total_deduction_money * $info['convert_rate']);
            }elseif($discount){
                $total_price = round($total_price * ($discount / 10), 2);
                $total_deduction_money = $total_price > $total_deduction_money ?  $total_deduction_money : $total_price;
                $total_deduction_point = floor($total_deduction_money * $info['convert_rate']);
            }
            if ($total_deduction_point > $member_account['point']) {//当用户积分不足
                $data['total_deduction_money'] = round($member_account['point'] / $info['convert_rate'], 2);
                $data['total_deduction_point'] = $member_account['point'];
                $deduction_money = $data['total_deduction_money'];
                foreach ($sku_lists as $sku_id => $sku_info) {
                    $sku_lists[$sku_id]['deduction_money'] = 0;
                    $sku_lists[$sku_id]['deduction_point'] = 0;
                    $sku_lists[$sku_id]['deduction_freight_point'] = 0;
                    if ($deduction_money > 0) {
                        $deduction_money = $sku_info['real_money'] - $deduction_money;
                        if ($deduction_money == 0) {
                            $sku_lists[$sku_id]['deduction_money'] = $sku_info['real_money'];
                            $deduction_money = 0;
                        } else if ($deduction_money > 0) {
                            $sku_lists[$sku_id]['deduction_money'] = $sku_info['real_money'] - $deduction_money;
                            $deduction_money = 0;
                        } else if ($deduction_money < 0) {
                            $sku_lists[$sku_id]['deduction_money'] = $sku_info['real_money'];
                            $deduction_money = 0 - $deduction_money;
                        }
                    }
                    $sku_lists[$sku_id]['deduction_point'] = floor($sku_lists[$sku_id]['deduction_money'] * $info['convert_rate']);
                    //运费抵扣的积分
                    if($shipping_type == 1 && $sku_info['shipping_fee']>0){
                        $deduction_freight_money = $sku_info['real_money'] - $sku_info['shipping_fee'] - $sku_lists[$sku_id]['deduction_money'];
                        if($deduction_freight_money<0 && ($info['point_deduction_calculation'] == 1 || $info['point_deduction_calculation'] == 3)){
                            $sku_lists[$sku_id]['deduction_freight_point'] = floor((0 - $deduction_freight_money) * $info['convert_rate']);
                        }
                    }
                    
                }
            } else if ($total_deduction_money > $total_price) {//大于实付重新计算
                $total_deduction_money = 0;
                $total_deduction_point = 0;
                foreach ($sku_lists as $sku_id => $sku_info) {
                    $sku_lists[$sku_id]['deduction_money'] = 0;
                    $sku_lists[$sku_id]['deduction_point'] = 0;
                    $sku_lists[$sku_id]['deduction_freight_point'] = 0;
                    if ($sku_info['point_deduction_max'] > 0 || $sku_info['point_deduction_max'] == '') {
                        if ($sku_info['point_deduction_max'] > 0) {
                            $deduction_money = $sku_info['real_money'] * $sku_info['point_deduction_max'] / 100;
                        } else {
                            $deduction_money = $sku_info['real_money'] * $info['point_deduction_max'] / 100;
                        }
                        $sku_lists[$sku_id]['deduction_money'] = round(floor($deduction_money * $info['convert_rate']) / $info['convert_rate'], 2);
                        $sku_lists[$sku_id]['deduction_point'] = floor($deduction_money * $info['convert_rate']);
                        //运费抵扣的积分
                        if($shipping_type == 1 && $sku_info['shipping_fee']>0){
                            $deduction_freight_money = $sku_info['real_money'] - $sku_info['shipping_fee'] - $sku_lists[$sku_id]['deduction_money'];
                            if($deduction_freight_money<0 && ($info['point_deduction_calculation'] == 1 || $info['point_deduction_calculation'] == 3)){
                                $sku_lists[$sku_id]['deduction_freight_point'] = floor((0 - $deduction_freight_money) * $info['convert_rate']);
                            }
                        }
                    }
                    $total_deduction_money += $sku_lists[$sku_id]['deduction_money'];
                    $total_deduction_point += $sku_lists[$sku_id]['deduction_point'];
                }
                $data['total_deduction_money'] = $total_deduction_money;
                $data['total_deduction_point'] = $total_deduction_point;
            } else {
                $data['total_deduction_money'] = $total_deduction_money;
                $data['total_deduction_point'] = $total_deduction_point;
            }
        }else{
			foreach ($sku_lists as $sku_id => $sku_info) {
                $sku_lists[$sku_id]['deduction_money'] = 0;
                $sku_lists[$sku_id]['deduction_point'] = 0;
                $sku_lists[$sku_id]['deduction_freight_point'] = 0;
                $real_money = $sku_info['discount_price'] * $sku_info['num'];
                if (!empty($sku_info['coupon_sku_percent_amount']) && getAddons('coupontype', $this->website_id)) {
                    $real_money -= $sku_info['coupon_sku_percent_amount'];
        }
                if (!empty($sku_info['full_cut_sku_percent']) && !empty($sku_info['full_cut_sku_amount'])) {
                    $real_money -= $sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'];
                }
                if (!empty($sku_info['presell_id'])) {
                    $real_money = $sku_info['price'] * $sku_info['num'];
                }
                if ($shipping_type == 1) {
                    $real_money = $real_money + $sku_info['shipping_fee'];
                }
                $real_money = ($real_money > 0) ? $real_money : 0;
                $sku_lists[$sku_id]['real_money'] = $real_money;
                $total_price += $real_money;
                $total_deduction_money += $sku_lists[$sku_id]['deduction_money'];
                $total_deduction_point += $sku_lists[$sku_id]['deduction_point'];
            }
		}
        $data['sku_info'] = $sku_lists;
        return $data;
    }

    /**
     * 订单积分返还计算
     *
     */
    public function pointReturnOrder($sku_lists, $shipping_type = 2, $website_id = '')
    {
        $config = new Config();
        if (!$website_id) $website_id = $this->website_id;
        $info = $config->getShopConfig(0, $website_id);
        $data = [];
        $data['total_return_point'] = $total_return_point = 0;
        if ($sku_lists && $info['is_point'] == 1) {
            foreach ($sku_lists as $sku_id => $sku_info) {
                $sku_lists[$sku_id]['return_point'] = 0;
                $sku_lists[$sku_id]['return_freight_point'] = 0;
                $real_money = $sku_info['discount_price'] * $sku_info['num'];
                if (!empty($sku_info['coupon_sku_percent_amount']) && getAddons('coupontype', $this->website_id)) {
                    $real_money -= $sku_info['coupon_sku_percent_amount'];
                }
                if (!empty($sku_info['full_cut_sku_percent']) && !empty($sku_info['full_cut_sku_amount'])) {
                    $real_money -= $sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'];
                }
                if (!empty($sku_info['presell_id'])) {
                    $real_money = $sku_info['price'] * $sku_info['num'];
                }
                if ($shipping_type == 1) {
                    $real_money = $real_money + $sku_info['shipping_fee'];
                }
                $real_money = ($real_money > 0) ? $real_money : 0;
                if ($sku_info['point_return_max'] > 0 || $sku_info['point_return_max'] == '') {
                    $price = 0;
                    if ($info['integral_calculation'] == 1) {//订单金额
                        if ($shipping_type == 1) {
                            $price = $sku_info['num'] * ($sku_info['price'] + $sku_info['shipping_fee']);
                        } else {
                            $price = $sku_info['num'] * $sku_info['price'];
                        }
                    } elseif ($info['integral_calculation'] == 2) {//商品金额
                        $price = $sku_info['num'] * $sku_info['price'];
                    } elseif ($info['integral_calculation'] == 3) {//支付金额
                        $price = $real_money;
                    }
                    if ($sku_info['point_return_max'] > 0) {
                        $return_money = $price * $sku_info['point_return_max'] / 100;
                    } else {
                        $return_money = $price * $info['point_invoice_tax'] / 100;
                    }
                    $sku_lists[$sku_id]['return_point'] = floor($return_money);
                    //运费抵扣的积分
                    if($shipping_type == 1 && $sku_info['shipping_fee']>0){
                        if($info['integral_calculation'] == 1 || $info['integral_calculation'] == 3){
                            if ($sku_info['point_return_max'] > 0) {
                                $sku_lists[$sku_id]['return_freight_point'] = $sku_info['shipping_fee'] * $sku_info['point_return_max'] / 100;
                            } else {
                                $sku_lists[$sku_id]['return_freight_point'] = $sku_info['shipping_fee'] * $info['point_invoice_tax'] / 100;
                            }
                        }
                    }
                }
                $total_return_point += $sku_lists[$sku_id]['return_point'];
            }
            $data['total_return_point'] = $total_return_point;
        }
        $data['sku_info'] = $sku_lists;
        return $data;
    }
}