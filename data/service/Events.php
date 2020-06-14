<?php

namespace data\service;

use addons\bargain\model\VslBargainModel;
use addons\bargain\service\Bargain;
use addons\channel\model\VslChannelOrderModel;
use addons\channel\server\Channel;
use addons\cpsunion\server\Cpsunion;
use addons\distribution\model\SysMessagePushModel;
use addons\liveshopping\model\AnchorModel;
use addons\liveshopping\model\LiveModel;
use addons\liveshopping\service\Liveshopping;
use addons\miniprogram\model\WeixinAuthModel;
use addons\presell\service\Presell;
use addons\seckill\model\VslSeckGoodsModel;
use addons\seckill\model\VslSeckillModel;
use addons\taskcenter\model\VslGeneralPosterModel;
use addons\taskcenter\service\Taskcenter;
use data\extend\WchatOpen;
use data\extend\weixin\WxPayApi;
use data\model\AddonsConfigModel;
use data\model\RedPackFailRecordModel;
use data\model\UserTaskModel;
use data\model\VslIncreMentOrderModel;
use data\model\VslOrderCalculateModel;
use data\model\VslPromotionMansongModel;
use data\model\VslOrderModel;
use data\model\VslPromotionMansongGoodsModel;
use data\model\VslPromotionDiscountModel;
use data\model\VslPromotionDiscountGoodsModel;
use data\model\VslPresellModel;
use data\model\VslGoodsSkuModel;
use data\model\VslGoodsModel;
use addons\coupontype\model\VslCouponModel;
use addons\groupshopping\model\VslGroupShoppingRecordModel;
use addons\groupshopping\server\GroupShopping;
use addons\smashegg\model\VslSmashEggModel;
use addons\scratchcard\model\VslScratchCardModel;
use addons\wheelsurf\model\VslWheelsurfModel;
use addons\paygift\model\VslPayGiftModel;
use addons\followgift\model\VslFollowGiftModel;
use addons\festivalcare\model\VslFestivalCareModel;
use addons\festivalcare\model\VslFestivalCareRecordsModel;
use addons\festivalcare\server\FestivalCare;
use data\model\VslMemberPrizeModel;
use data\model\VslMemberViewModel;
use data\model\VslUserFollowModel;
use data\service\Order\Order as orderService;
use addons\globalbonus\service\GlobalBonus;
use data\model\VslMemberModel;
use think\log;
use think\Db;
use data\model\ActiveListModel;
use addons\seckill\server\Seckill as SeckillServer;

/**
 * 计划任务
 */
class Events extends BaseService {

    /**
     * (non-PHPdoc)
     * @see \data\api\IEvents::giftClose()
     */
    public function giftClose() {
        
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IEvents::mansongClose()
     */
    public function mansongOperation() {
        
        $mansong = new VslPromotionMansongModel();
        $mansong->startTrans();
        try {
            $time = time();
            $condition_close = array(
                'end_time' => array('LT', $time),
                'status' => array('NEQ', 3)
            );
            $condition_start = array(
                'start_time' => array('ELT', $time),
                'status' => 0
            );
            $mansong->save(['status' => 4], $condition_close);
            $mansong->save(['status' => 1], $condition_start);
            $mansong_goods = new VslPromotionMansongGoodsModel();
            $mansong_goods->save(['status' => 4], $condition_close);
            $mansong_goods->save(['status' => 1], $condition_start);
            $mansong->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $mansong->rollback();
            return $e->getMessage();
        }
    }
    /**
     * (non-PHPdoc)
     * @see \data\api\IEvents::ordersComment()
     * 订单完成后自动评论
     */
    public function ordersComment($website_id = 0){
        $order_model = new VslOrderModel();
        try {
            $config = new Config();
            $is_translation = $config->getConfigbyWebsiteId($website_id, 0, 'IS_TRANSLATION');
            $translation_time = $config->getConfigbyWebsiteId($website_id, 0, 'TRANSLATION_TIME');
            $translation_text = $config->getConfigbyWebsiteId($website_id, 0, 'TRANSLATION_TEXT');
            if($is_translation['value'] != 1){
                return 1;
            }
            if ($translation_time['value'] !== '') {
                $complete_time = $translation_time['value'];
            } else {
                $complete_time = 7; //7天
            }
            
            $time = time() - 3600 * 24 * $complete_time;
            $condition = array(
                'order_status' => 4, //订单完成
                'finish_time' => array('LT', $time),
                'website_id' => $website_id
            );
            $order_list = $order_model->getQuery($condition, 'order_id', '');
            if (!empty($order_list)) {
                $order = new Order();
                foreach ($order_list as $k => $v) {
                    if (!empty($v['order_id'])) {
                        $order->ordersComment($v['order_id'], $website_id,$translation_text['value']); 
                    }
                }
            }
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }
    }
    /**
     * (non-PHPdoc)
     * @see \data\api\IEvents::ordersClose()
     */
    public function ordersClose($website_id = 0, $task_mark = 0) {
        $order_model = new VslOrderModel();
            $config = new Config();
            $config_info = $config->getConfigbyWebsiteId($website_id, 0, 'ORDER_BUY_CLOSE_TIME');
            if (!empty($config_info['value'])) {
                $close_time = $config_info['value'];
            } else {
                $close_time = 60; //默认1分钟
            }
            $time = time() - $close_time * 60; //订单自动关闭
            $condition = array(
                'order_status' => 0,
                'create_time' => array('LT', $time),
                'order_type' => array('not in', '5,6,8'),
                'payment_type' => array('neq', 6),
                'website_id' => $website_id
            );
            $hasGroup = getAddons('groupshopping', $website_id);
            if ($hasGroup) {
                $condition['group_record_id'] = 0;
            }
            
            $order_list = $order_model->getQuery($condition, 'order_id, website_id, money_type, presell_id, order_type, buy_type', '');
            
            if (!empty($order_list)) {
                $order = new Order();
                foreach ($order_list as $k => $v) {
                    if (!empty($v['order_id'])) {
                       
                        $presell = getAddons('presell', $v['website_id']);
                        if($v['order_type'] == 7 && $v['money_type'] == 1 && $presell){
                            
                            $presell_mdl = new VslPresellModel();
                            $presell_list = $presell_mdl->getInfo(['id'=>$v['presell_id']], '*');
                            $pay_end_time = $presell_list['pay_end_time'];
                            if(time() <= $pay_end_time){ //如果预售订单当前时间小于尾款支付时间，则不让其关闭。
                               
                                continue;
                            }
                            
                        }
//                        //提货订单不让其关闭
//                        if($v['buy_type'] == 2){
//                            continue;
//                        }
                        $order->orderClose($v['order_id'], $task_mark);
                    }
                }
            }
    }
    /*
     * 秒杀订单关闭
     * **/
    public function seckillOrderClose($website_id = 0, $task_mark = 0)
    {
        $order_model = new VslOrderModel();
        $addons_conf = new AddonsConfigModel();
        $seckill_conf = json_decode($addons_conf->getInfo(['addons' => 'seckill', 'website_id' => $website_id], 'value')['value'], true);
        $close_time = $seckill_conf['pay_limit_time'];
        $time = time() - $close_time * 60; //秒杀订单自动关闭
        $condition = array(
            'order_status' => 0,
            'create_time' => array('LT', $time),
            'order_type' => 6,
            'payment_type' => array('neq', 6),
            'website_id' => $website_id
        );
        $order_list = $order_model->getQuery($condition, 'order_id, website_id', '');
        if (!empty($order_list)) {
            $order = new Order();
            foreach ($order_list as $k => $v) {
                if (!empty($v['order_id'])) {
                    $order->orderClose($v['order_id'], $task_mark);
                }
            }
        }
    }
    /*
     * 砍价订单关闭
     * **/
    public function bargainOrderClose($website_id = 0, $task_mark = 0)
    {
        $order_model = new VslOrderModel();
        $addons_conf = new AddonsConfigModel();
        $bargain_conf = json_decode($addons_conf->getInfo(['addons' => 'bargain', 'website_id' => $website_id], 'value')['value'], true);
        $close_time = $bargain_conf['pay_time_limit'];
        $time = time() - $close_time * 60; //砍价订单自动关闭
        $condition = array(
            'order_status' => 0,
            'create_time' => array('LT', $time),
            'order_type' => 8,
            'payment_type' => array('neq', 6),
            'website_id' => $website_id
        );
        $order_list = $order_model->getQuery($condition, 'order_id, website_id', '');
        if (!empty($order_list)) {
            $order = new Order();
            foreach ($order_list as $k => $v) {
                if (!empty($v['order_id'])) {
                    $order->orderClose($v['order_id'], $task_mark);
                }
            }
        }
    }

    /**
     * 增值应用订单关闭
     */
    public function incrementOrdersClose($website_id = 0) {
        $order_model = new VslIncreMentOrderModel();
        try {
            $close_time = 30; //默认30分钟
            $time = time() - $close_time * 60; //订单自动关闭
            $condition = array(
                'order_status' => 0,
                'create_time' => array('LT', $time),
                'website_id' => $website_id
            );
            $order_list = $order_model->getQuery($condition, 'order_id', '');
            if (!empty($order_list)) {
                foreach ($order_list as $k => $v) {
                    if (!empty($v['order_id'])) {
                        $order_model = new VslIncreMentOrderModel();
                        $order_model->save(['order_status' => 1], ['order_id' => $v['order_id']]);
                    }
                }
            }
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }
    }

    /**
     * 渠道商订单关闭
     */
    public function channelOrdersClose($website_id = 0) {
        $order_model = new VslOrderModel();
        try {
            $channel_status = getAddons('channel', $website_id);
            if ($channel_status) {
                $config = new Channel();
                $config_info = $config->getChannelConfig($website_id);
            }
            if (!empty($config_info)) {
                $close_time = $config_info['channel_order_close_time'];
            } else {
                $close_time = 1; //默认1分钟
            }
            $time = time() - $close_time * 60; //订单自动关闭
            $condition1 = array(
                'order_status' => 0,
                'create_time' => array('LT', $time),
                'payment_type' => array('neq', 6),
                'website_id' => $website_id,
                'buy_type' => 2
            );
            $condition2 = array(
                'order_status' => 0,
                'create_time' => array('LT', $time),
                'payment_type' => array('neq', 6),
                'website_id' => $website_id,
            );
            $order_list = $order_model->getQuery($condition1, 'order_id', '');
            if ($channel_status) {
                $channel_order_model = new VslChannelOrderModel();
                $channel_order_list = $channel_order_model->getQuery($condition2, 'order_id', '');
            }
            if (!empty($order_list) || !empty($channel_order_list)) {
                $order = new Order();
                //正常订单 零售的
                foreach ($order_list as $k => $v) {
                    if (!empty($v['order_id'])) {
                        $order->orderClose($v['order_id']);
                    }
                }
                //渠道商采购/出货
                foreach ($channel_order_list as $k => $v) {
                    if (!empty($v['order_id'])) {
                        $order->channelOrderClose($v['order_id']);
                    }
                }
            }
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IEvents::ordersComplete()
     */
    public function ordersComplete($website_id = 0) {
        try {
            $order_model = new VslOrderModel();
            $order_model->startTrans();
            $config = new Config();
            $config_info = $config->getConfigbyWebsiteId($website_id, 0, 'ORDER_DELIVERY_COMPLETE_TIME');
            if ($config_info['value'] !== '') {
                $complete_time = $config_info['value'];
            } else {
                $complete_time = 7; //7天
            }
            $time = time() - 3600 * 24 * $complete_time;
            $condition = array(
                'order_status' => 3,
                'sign_time' => array('LT', $time),
                'website_id' => $website_id
            );
            $order_list = $order_model->getQuery($condition, 'order_id', '');
            if (!empty($order_list)) {
                $order = new Order();
                foreach ($order_list as $k => $v) {
                    if (!empty($v['order_id'])) {
                        $order->orderComplete($v['order_id'], $website_id); 
                    }
                }
            }
            $order_model->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $order_model->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IEvents::discountOperation()
     */
    public function discountOperation() {
        $discount = new VslPromotionDiscountModel();
        $discount->startTrans();
        try {
            $time = time();
            $discount_goods = new VslPromotionDiscountGoodsModel();
            /*             * **********************************************************结束活动************************************************************* */
            $condition_close = array(
                'end_time' => array('LT', $time),
                'status' => array('NEQ', 3)
            );
            $discount->save(['status' => 4], $condition_close);
            $discount_close_goods_list = $discount_goods->getQuery($condition_close, '*', '');
            if (!empty($discount_close_goods_list)) {
                foreach ($discount_close_goods_list as $k => $discount_goods_item) {
                    $goods = new VslGoodsModel();

                    $data_goods = array(
                        'promotion_type' => 5,
                        'promote_id' => $discount_goods_item['discount_id']
                    );
                    $goods_id_list = $goods->getQuery($data_goods, 'goods_id', '');
                    if (!empty($goods_id_list)) {
                        foreach ($goods_id_list as $k => $goods_id) {
                            $goods_info = $goods->getInfo(['goods_id' => $goods_id['goods_id']], 'promotion_type,price');
                            $goods->save(['promotion_price' => $goods_info['price']], ['goods_id' => $goods_id['goods_id']]);
                            $goods_sku = new VslGoodsSkuModel();
                            $goods_sku_list = $goods_sku->getQuery(['goods_id' => $goods_id['goods_id']], 'price,sku_id', '');
                            foreach ($goods_sku_list as $k_sku => $sku) {
                                $goods_sku = new VslGoodsSkuModel();
                                $data_goods_sku = array(
                                    'promote_price' => $sku['price']
                                );
                                $goods_sku->save($data_goods_sku, ['sku_id' => $sku['sku_id']]);
                            }
                        }
                    }
                    $goods->save(['promotion_type' => 0, 'promote_id' => 0], $data_goods);
                }
            }
            $discount_goods->save(['status' => 4], $condition_close);
            /*             * **********************************************************结束活动************************************************************* */
            /*             * **********************************************************开始活动************************************************************* */
            $condition_start = array(
                'start_time' => array('ELT', $time),
                'status' => 0
            );
            //查询待开始活动列表
            $discount_goods_list = $discount_goods->getQuery($condition_start, '*', '');
            if (!empty($discount_goods_list)) {
                foreach ($discount_goods_list as $k => $discount_goods_item) {
                    $goods = new VslGoodsModel();
                    $goods_info = $goods->getInfo(['goods_id' => $discount_goods_item['goods_id']], 'promotion_type,price');
                    $data_goods = array(
                        'promotion_type' => 5,
                        'promote_id' => $discount_goods_item['discount_id'],
                        'promotion_price' => $goods_info['price'] * $discount_goods_item['discount'] / 10
                    );
                    $goods->save($data_goods, ['goods_id' => $discount_goods_item['goods_id']]);
                    $goods_sku = new VslGoodsSkuModel();
                    $goods_sku_list = $goods_sku->getQuery(['goods_id' => $discount_goods_item['goods_id']], 'price,sku_id', '');
                    foreach ($goods_sku_list as $k_sku => $sku) {
                        $goods_sku = new VslGoodsSkuModel();
                        $data_goods_sku = array(
                            'promote_price' => $sku['price'] * $discount_goods_item['discount'] / 10
                        );
                        $goods_sku->save($data_goods_sku, ['sku_id' => $sku['sku_id']]);
                    }
                }
            }
            $discount_goods->save(['status' => 1], $condition_start);
            $discount->save(['status' => 1], $condition_start);
            /*             * **********************************************************开始活动************************************************************* */
            $discount->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $discount->rollback();
            return $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IEvents::autoDeilvery()
     */
    public function autoDeilvery($website_id = 0) {
        $order_model = new VslOrderModel();
        try {
            $config = new Config();
            $config_info = $config->getConfigbyWebsiteId($website_id, 0, 'ORDER_AUTO_DELIVERY');
            if ($config_info['value'] || $config_info['value']==0) {
                $delivery_time = $config_info['value'];
            } else {
                $delivery_time = 7; //默认7天自动收货
            }
            $time = time() - 3600 * 24 *  $delivery_time; //订单自动收货
            $condition = array(
                'order_status' => 2,
                'consign_time' => array('LT', $time),
                'website_id' => $website_id
            );
            $order_list = $order_model->Query($condition, 'order_id');
            if (!empty($order_list)) {
                $order = new orderService();
                $orders = new Order();
                foreach ($order_list as $k => $v) {
                    if (!empty($v)) {
                        $order->orderAutoDelivery($v);
                        if($delivery_time==0){
                            $orders->orderComplete($v, $website_id);
                        }
                    }
                }
            }
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }
    }

    /**
     * 优惠券自动过期
     * {@inheritDoc}
     * @see \data\api\IEvents::autoCoupon()
     */
    public function autoCouponClose() {
        
        $vsl_coupon_model = new VslCouponModel();
        $vsl_coupon_model->startTrans();
        try {
            $condition['end_time'] = array('LT', time());
            $condition['state'] = array('NEQ', 2); //排成已使用的优惠券
//            $condition['website_id'] = $this->website_id;
            $count = $vsl_coupon_model->getCount($condition);
            $res = -1;
            if ($count) {
                $res = $vsl_coupon_model->save(['state' => 3], $condition);
            }
            $vsl_coupon_model->commit();
            return $res;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $vsl_coupon_model->rollback();
            return $e->getMessage();
        }
    }

    /*
     * 版本到期
     */

    public function sendMail($website_id) {
        $article = new Article();
        $web_site = new WebSite();
        $web_info = $web_site->getWebSiteInfo($website_id);
        $time1 = date("Y-m-d", strtotime($web_info['shop_validity_time']) - 3600 * 24 * 7);
        $time2 = date("Y-m-d", strtotime($web_info['shop_validity_time']) - 3600 * 24);
        $time3 = date("Y-m-d", strtotime($web_info['shop_validity_time']));
        $time = date("Y-m-d", time());
        if (strtotime($time1) == strtotime($time)) {
            if ($web_info['expiry_time'] != 3) {
                $title = '您的商城版本还有7天到期';
                $content = '<p>您的商城“' . $web_info['version'] . '”还有7天到期，请及时联系我们客服400-889-6625进行续费，以免影响商城正常运作</p>';
                $article->sendMail($title, $content, $website_id, 1, 2, 3);
            }
        }
        if (strtotime($time2) == strtotime($time)) {
            if ($web_info['expiry_time'] != 2) {
                $title = '您的商城版本还有1天到期';
                $content = '<p>您的商城“' . $web_info['version'] . '”还有1天到期，请及时联系我们客服400-889-6625进行续费，以免影响商城正常运作</p>';
                $article->sendMail($title, $content, $website_id, 1, 2, 2);
            }
        }
        if (strtotime($time3) == strtotime($time)) {
            if ($web_info['expiry_time'] != 1) {
                $title = '您的商城版本已经到期';
                $content = '<p>您的商城“' . $web_info['version'] . '”已过期，部分功能可能受到限制，请及时联系我们客服400-889-6625进行续费，以免影响商城正常运作</p>';
                $article->sendMail($title, $content, $website_id, 1, 2, 1);
            }
        }
    }

    /*
     * 秒杀结束修改商品促销类型
     * * */

    public function updateSeckillGoodsPromotionType($website_id) {
        if(!getAddons('seckill', $website_id)){
            return true;
        }
        $seckill_goods_mdl = new VslSeckGoodsModel();
        $seckill_mdl = new VslSeckillModel();
        $goods_mdl = new VslGoodsModel();
        //查找出过期的活动和商品
        $condition['s.website_id'] = $website_id;
        $condition['s.seckill_now_time'] = ['<', time() - 24 * 3600];
        $condition['sg.past_change_goods_promotion_status'] = 0;
        $past_seckill_list = $seckill_mdl->alias('s')
                ->where($condition)
                ->join('vsl_seckill_goods sg', 's.seckill_id=sg.seckill_id')
                ->group('sg.goods_id')
                ->select();
        //执行修改goods表
        foreach ($past_seckill_list as $k => $v) {
            //判断如果这个商品存在于未过期的活动中，则不修改
            $condition2['s.website_id'] = $website_id;
            $condition2['s.seckill_now_time'] = ['>=', time() - 24 * 3600];
            $condition2['sg.goods_id'] = $v['goods_id'];
            $condition2['sg.del_status'] = 1;
            $is_goods_seckill_list = $seckill_mdl->alias('s')
                    ->where($condition2)
                    ->join('vsl_seckill_goods sg', 's.seckill_id=sg.seckill_id')
                    ->group('sg.goods_id')
                    ->select();
            if (!$is_goods_seckill_list) {//如果这个商品确实是过期了，并且没有在未过期的活动中。
                $res = $goods_mdl->where(['goods_id' => $v['goods_id'], 'website_id' => $website_id, 'promotion_type' => 1])->update(['promotion_type' => 0]);
            }
            if ($res) {//用于筛选那些过期的没有更新商品状态的活动。
                $seckill_goods_mdl->where(['seckill_id' => $v['seckill_id'], 'goods_id' => $v['goods_id']])->update(['past_change_goods_promotion_status' => 1]);
            }
        }
    }

    /*
     * 秒杀结束修改未审核已开始商品促销类型
     * * */

    public function updateSeckillUncheckGoodsPromotionType($website_id) {
        if(!getAddons('seckill', $website_id)){
            return true;
        }
        $seckill_goods_mdl = new VslSeckGoodsModel();
        $seckill_mdl = new VslSeckillModel();
        $goods_mdl = new VslGoodsModel();
        //查找出过期的活动和商品
        $condition['s.website_id'] = $website_id;
        $condition['s.seckill_now_time'] = ['<', time()];
        $condition['sg.check_status'] = 0;
        $condition['sg.past_change_goods_promotion_status'] = 0;
        $past_seckill_list = $seckill_mdl->alias('s')
                ->where($condition)
                ->join('vsl_seckill_goods sg', 's.seckill_id=sg.seckill_id')
                ->group('sg.goods_id')
                ->select();
        //执行修改goods表
        foreach ($past_seckill_list as $k => $v) {
            //判断如果这个商品存在于未过期的活动中，则不修改
            $condition2['s.website_id'] = $website_id;
            $condition2['s.seckill_now_time'] = ['>=', time() - 24 * 3600];
            $condition2['sg.goods_id'] = $v['goods_id'];
            $is_goods_seckill_list = $seckill_mdl->alias('s')
                    ->where($condition2)
                    ->join('vsl_seckill_goods sg', 's.seckill_id=sg.seckill_id')
                    ->group('sg.goods_id')
                    ->select();
            if (!$is_goods_seckill_list) {//如果这个商品确实是过期了，并且没有在未过期的活动中。
                $res = $goods_mdl->where(['goods_id' => $v['goods_id'], 'website_id' => $website_id, 'promotion_type' => 1])->update(['promotion_type' => 0]);
            }
            if ($res) {//用于筛选那些过期的没有更新商品状态的活动。
                $seckill_goods_mdl->where(['seckill_id' => $v['seckill_id'], 'goods_id' => $v['goods_id']])->update(['past_change_goods_promotion_status' => 1]);
            }
        }
    }

    /*
     * 砍价结束修改商品促销类型
     * * */

    public function updateBargainGoodsPromotionType($website_id) {
        if(!getAddons('bargain', $website_id)){
            return true;
        }
        $bargain_mdl = new VslBargainModel();
        $goods_mdl = new VslGoodsModel();
        //查找出过期的活动和商品
        $condition['website_id'] = $website_id;
        $condition['past_change_goods_promotion_status'] = 0;
        $condition['end_bargain_time'] = ['<', time()];
        $past_bargain_list = $bargain_mdl->where($condition)->select();
        //执行修改goods表
        foreach ($past_bargain_list as $k => $v) {
            $res = $goods_mdl->where(['goods_id' => $v['goods_id'], 'website_id' => $website_id, 'promotion_type' => 4])->update(['promotion_type' => 0]);
            if ($res) {
                $bargain_mdl->where(['bargain_id' => $v['bargain_id']])->update(['past_change_goods_promotion_status' => 1]);
            }
        }
    }

    /*
     * 限时折扣结束修改商品类型
     * * */

    public function updateDiscountGoodsPromotionType($website_id) {
        if(!getAddons('discount', $website_id)){
            return true;
        }
        $discount_model = new VslPromotionDiscountGoodsModel();
        //查找出过期的活动和商品
        $condition['pdg.end_time'] = ['<', time()];
        $condition['pd.website_id'] = $website_id;
        $discount_goods_list = $discount_model
            ->alias('pdg')
            ->join('vsl_promotion_discount pd','pdg.discount_id = pd.discount_id')
            ->where($condition)
            ->select();
        //执行修改goods表
        if (!empty($discount_goods_list)) {
            foreach ($discount_goods_list as $k => $v) {
                $goods_mdl = new VslGoodsModel();
                $goods_mdl->where(['goods_id' => $v['goods_id'], 'promotion_type' => 5, 'website_id' => $website_id])->update(['promotion_type' => 0]);
            }
        }
    }

    /*
     * 预售结束修改商品类型
     * * */

    public function updatePresellGoodsPromotionType($website_id) {
        if(!getAddons('presell', $website_id)){
            return true;
        }
        $presell_mdl = new VslPresellModel();
        //查找出过期的活动和商品
        $condition['end_time'] = ['<', time()];
        $condition['website_id'] = $website_id;
        $presell_goods_list = $presell_mdl->where($condition)->select();
//        p($presell_goods_list);
        //执行修改goods表
        if (!empty($presell_goods_list)) {
            foreach ($presell_goods_list as $k => $v) {
                $presell = new Presell();
                //判断该商品是否有重新添加活动
                $new_cond['goods_id'] = $v['goods_id'];
                $is_presell = $presell->getPresellInfoByGoodsId($v['goods_id']);
                if(!$is_presell){
                    $goods_mdl = new VslGoodsModel();
                    $goods_mdl->where(['goods_id' => $v['goods_id'], 'website_id' => $website_id, 'promotion_type' => 3])->update(['promotion_type' => 0]);
                }
            }
        }
    }

    /*
     * 分销商自动降级
     */

    public function autoDownDistributorLevel($website_id) {
        $params = ['website_id' => $website_id];
        $distribution_status = getAddons('distribution', $website_id);
        if ($distribution_status == 1) {
            hook('autoDownDistributorLevel', $params);
        }
    }

    /*
     * 全球代理商自动降级
     */

    public function autoDownGlobalAgentLevel($website_id) {
        $params = ['website_id' => $website_id];
        $global_status = getAddons('globalbonus', $website_id);
        if ($global_status == 1) {
            hook('autoDownGlobalAgentLevel', $params);
        }
    }

    /*
     * 全球代理分红发放
     */

    public function autoGrantGlobalBonus($website_id) {
        $params['website_id'] = $website_id;
        $global_status = getAddons('globalbonus', $website_id);
        if ($global_status == 1) {
            hook('autoGrantGlobalBonus', $params);
        }
    }

    /*
     * 区域代理商自动降级
     */

    public function autoDownAreaAgentLevel($website_id) {
        $params = ['website_id' => $website_id];
        $area_status = getAddons('areabonus', $website_id);
        if ($area_status == 1) {
            hook('autoDownAreaAgentLevel', $params);
        }
    }

    /*
     * 区域代理分红发放
     */

    public function autoGrantAreaBonus($website_id) {
        $params['website_id'] = $website_id;
        $area_status = getAddons('areabonus', $website_id);
        if ($area_status == 1) {
            hook('autoGrantAreaBonus', $params);
        }
    }

    /*
     * 团队代理商自动降级
     */

    public function autoDownTeamAgentLevel($website_id) {
        $params = ['website_id' => $website_id];
        $team_status = getAddons('teambonus', $website_id);
        if ($team_status == 1) {
            hook('autoDownTeamAgentLevel', $params);
        }
    }

    /*
     * 渠道商自动降级
     */

    public function autoDownChannelAgentLevel($website_id) {
        $params = ['website_id' => $website_id];
        $channel_status = getAddons('channel', $website_id);
        if ($channel_status == 1) {
            hook('autoDownChannelAgentLevel', $params);
        }
    }
    /*
     * 微店自动降级
     */

    public function autoDownMicLevel($website_id) {
        $params = ['website_id' => $website_id];
        $microshop_status = getAddons('microshop', $website_id);
        
        if ($microshop_status == 1) {
            hook('autoDownMicLevel', $params);
        }
    }
    /*
     * 团队代理分红发放
     */

    public function autoGrantTeamBonus($website_id) {
        $params['website_id'] = $website_id;
        $team_status = getAddons('teambonus', $website_id);
        if ($team_status == 1) {
            hook('autoGrantTeamBonus', $params);
        }
    }

    /**
     * 更新小程序授权authorizer_access_token
     */
    public function refreshAuthAccessToken($website_id) {
        $wchat_open = new WchatOpen($website_id);
        if (getAddons('miniprogram', $website_id)) {
            //获取appid
            $weixin_auth_model = new WeixinAuthModel();
            $auth_list = $weixin_auth_model::all(['website_id' => $website_id]);
            if (!empty($auth_list) && is_array($auth_list)) {
                foreach ($auth_list as $v) {
                    $return = $wchat_open->get_access_token_crontab($v['authorizer_appid'], $v['authorizer_refresh_token']);
                    if ($return) {
                        $weixin_auth_model->save(
                                [
                            'authorizer_refresh_token' => $return['authorizer_refresh_token'],
                            'authorizer_access_token' => $return['authorizer_access_token'],
                            'update_token_time' => time()
                                ], [
                            'authorizer_appid' => $v['authorizer_appid']
                                ]
                        );
                    }
                }
            }
        }
    }

    /**
     * 拼团订单关闭
     */
    public function ordersCloseGroup($website_id = 0) {
        $hasGroup = getAddons('groupshopping', $website_id);
        if (!$hasGroup) {
            return 1;
        }
        $order_model = new VslOrderModel();
        try {
            $config = new AddonsConfig();
            $groupConfig = $config->getAddonsConfig('groupshopping', $website_id);
            $groupConfigValue = json_decode($groupConfig['value'], true);
            if (!empty($groupConfigValue['pay_time_limit'])) {
                $close_time = $groupConfigValue['pay_time_limit'];
            } else {
                $close_time = 1; //默认1分钟
            }
            $time = time() - $close_time * 60; //订单自动关闭
            $condition = array(
                'order_status' => 0,
                'create_time' => array('LT', $time),
                'payment_type' => array('neq', 6),
                'website_id' => $website_id,
                'group_record_id' => ['>', 0]
            );

            $order_list = $order_model->getQuery($condition, 'order_id', '');
            if (!empty($order_list)) {
                $order = new Order();
                foreach ($order_list as $k => $v) {
                    if (!empty($v['order_id'])) {
                        $order->orderClose($v['order_id']);
                    }
                }
            }
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }
    }

    /**
     * 未成团活动关闭，处理订单
     */
    public function groupShoppingClose($website_id = 0) {
        $hasGroup = getAddons('groupshopping', $website_id);
        if (!$hasGroup) {
            return 1;
        }
        $order_model = new VslOrderModel();
        $groupRecordModel = new VslGroupShoppingRecordModel();
        try {
            $config = new AddonsConfig();
            $groupConfig = $config->getAddonsConfig('groupshopping', $website_id);
            $groupConfigValue = json_decode($groupConfig['value'], true);
            if (!empty($groupConfigValue['pay_time_limit'])) {
                $close_time = $groupConfigValue['pay_time_limit'];
            } else {
                $close_time = 1; //默认1分钟
            }
            $time = time() - $close_time * 60; //订单自动关闭
            $condition = array(
                'order_status' => 0,
                'create_time' => array('LT', $time),
                'payment_type' => array('neq', 6),
                'website_id' => $website_id,
                'group_record_id' => ['>', 0]
            );

            $order_list = $order_model->getQuery($condition, 'order_id', '');
            if (!empty($order_list)) {
                $order = new Order();
                foreach ($order_list as $k => $v) {
                    if (!empty($v['order_id'])) {
                        $order->orderClose($v['order_id']);
                    }
                }
            }
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }
    }

    /*
     * 结束未成团活动
     */

    public function groupShoppingRecordClose($website_id = 0) {
        $hasGroup = getAddons('groupshopping', $website_id);
        if (!$hasGroup) {
            return 1;
        }
        $groupRecordModel = new VslGroupShoppingRecordModel();
        $groupServer = new GroupShopping();
        $groupRecordList = $groupRecordModel->getQuery(['finish_time' => ['<', time()], 'status' => 0, 'website_id' => $website_id], ['record_id'], 'create_time desc');
        if (!$groupRecordList) {
            return 1;
        }
        foreach ($groupRecordList as $grVal) {
            $groupServer->groupFail($grVal['record_id']);
        }
        return 1;
    }

    //关闭结束订单
//    public function presell_order_close($website_id = 0) {
//        $presell = getAddons('presell', $website_id);
//        if (!$presell) {
//            return 1;
//        }
//
//        $presell_model = new VslPresellModel();
//        $order = new VslOrderModel();
//        //循环关闭过了预付定金和未付尾款的订单
//        $nopay_firstmoney_list = $presell_model->getQuery(['end_time' => ['>', time()], 'website_id' => $website_id], '', '');
//        foreach ($nopay_firstmoney_list as $k => $v) {
//            $order->save(['order_status' => 5], ['presell_id' => $v['id'], 'money_type' => 0, 'pay_status' => 0]);
//        }
//
//        $no_pay_last_money = $presell_model->getQuery(['pay_end_time' => ['>', time()], 'website_id' => $website_id], '', '');
//        foreach ($no_pay_last_money as $k => $v) {
//            $order->save(['order_status' => 5], ['presell_id' => $v['id'], 'money_type' => 1, 'pay_status' => 0]);
//        }
//    }

    public function orderCalculate($website_id) {

        $order_calculate_model = new VslOrderCalculateModel();
        $distribution_list = $order_calculate_model::all(['website_id' => $website_id, 'had_cal' => 0]);
        $distribution_status = getAddons('distribution', $website_id);
        $global_status = getAddons('globalbonus', $website_id);
        $area_status = getAddons('areabonus', $website_id);
        $team_status = getAddons('teambonus', $website_id);
        $microshop_status = getAddons('microshop', $website_id);
        $had_cal_order_goods_id_array = [];
        if (!empty($distribution_list) && is_array($distribution_list)) {
            foreach ($distribution_list as $v) {
                if ($distribution_status) {
                    hook('orderCommissionCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'goods_id' => $v['goods_id'], 'buyer_id' => $v['buyer_id'], 'website_id' => $website_id]);
                    $had_cal_order_goods_id_array[] = $v['order_goods_id'];
                }
                if ($global_status) {
                    hook('orderGlobalBonusCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'goods_id' => $v['goods_id'], 'buyer_id' => $v['buyer_id'], 'website_id' => $website_id]);
                    $had_cal_order_goods_id_array[] = $v['order_goods_id'];
                }
                if ($area_status) {
                    hook('orderAreaBonusCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'goods_id' => $v['goods_id'], 'buyer_id' => $v['buyer_id'], 'website_id' => $website_id]);
                    $had_cal_order_goods_id_array[] = $v['order_goods_id'];
                }
                if ($team_status) {
                    hook('orderTeamBonusCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'goods_id' => $v['goods_id'], 'buyer_id' => $v['buyer_id'], 'website_id' => $website_id]);
                    $had_cal_order_goods_id_array[] = $v['order_goods_id'];
                }
                
                if ($microshop_status) {
                    hook('orderMicroShopCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'goods_id' => $v['goods_id'], 'buyer_id' => $v['buyer_id'], 'website_id' => $website_id]);
                    $had_cal_order_goods_id_array[] = $v['order_goods_id'];
                }
            }
            if (!empty($had_cal_order_goods_id_array)) {
                $order_calculate_model->save(['had_cal' => 1], ['order_goods_id' => ['IN', $had_cal_order_goods_id_array]]);
            }
        }
        $distribution_list = $order_calculate_model::all(['website_id' => $website_id, 'had_paid' => 1]);
        $order_goods_id = [];
        if (!empty($distribution_list) && is_array($distribution_list)) {
            foreach ($distribution_list as $v) {
                if ($distribution_status) {
                    // 执行钩子：计算分销佣金
                    hook('orderPayCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'website_id' => $website_id]);
                }
                if ($global_status) {
                    // 执行钩子：计算全球分红
                    hook('orderGlobalPayCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'website_id' => $website_id]);
                }
                if ($area_status) {
                    // 执行钩子：计算区域分红
                    hook('orderAreaPayCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'website_id' => $website_id]);
                }
                if ($team_status) {
                    // 执行钩子：计算团队分红
                    hook('orderTeamPayCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'website_id' => $website_id]);
                }
                if ($microshop_status) {
                    // 执行钩子：计算微店收益
                    hook('orderPayMicroShopCalculate', ['order_id' => $v['order_id'], 'order_goods_id' => $v['order_goods_id'], 'website_id' => $website_id]);
                }
                $order_goods_id[] = $v['order_goods_id'];
            }
        }

        if (!empty($order_goods_id)) {
            $order_calculate_model::destroy(['order_goods_id' => ['IN', $order_goods_id]]);
        }
    }

    /**
     * 砸金蛋活动
     */
    public function smashEgg($website_id) {
        if(!getAddons('smashegg', $website_id)){
            return true;
        }
        $smashegg = new VslSmashEggModel();
        $smashegg->startTrans();
        try {
            $time = time();
            $start = array(
                'website_id' => $website_id,
                'start_time' => array('elt', $time),
                'state' => 1
            );
            $end = array(
                'website_id' => $website_id,
                'end_time' => array('lt', $time),
            );
            $smashegg->save(['state' => 2], $start);
            $smashegg->save(['state' => 3], $end);
            $smashegg->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $smashegg->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 大转盘活动
     */
    public function wheelSurf($website_id) {
        if(!getAddons('wheelsurf', $website_id)){
            return true;
        }
        $wheelsurf = new VslWheelsurfModel();
        $wheelsurf->startTrans();
        try {
            $time = time();
            $start = array(
                'website_id' => $website_id,
                'start_time' => array('elt', $time),
                'state' => 1
            );
            $end = array(
                'website_id' => $website_id,
                'end_time' => array('lt', $time),
            );
            $wheelsurf->save(['state' => 2], $start);
            $wheelsurf->save(['state' => 3], $end);
            $wheelsurf->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $wheelsurf->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 刮刮卡活动
     */
    public function scratchCard($website_id) {
        if(!getAddons('scratchcard', $website_id)){
            return true;
        }
        $scratchcard = new VslScratchCardModel();
        $scratchcard->startTrans();
        try {
            $time = time();
            $start = array(
                'website_id' => $website_id,
                'start_time' => array('elt', $time),
                'state' => 1
            );
            $end = array(
                'website_id' => $website_id,
                'end_time' => array('lt', $time),
            );
            $scratchcard->save(['state' => 2], $start);
            $scratchcard->save(['state' => 3], $end);
            $scratchcard->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $scratchcard->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 奖品领取过期
     */
    public function memberPrize($website_id) {
        $prize = new VslMemberPrizeModel();
        $prize->startTrans();
        try {
            $time = time();
            $end = array(
                'website_id' => $website_id,
                'expire_time' => array('lt', $time),
                'state' => array('neq', 2)
            );
            $prize->save(['state' => 3], $end);
            $prize->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $prize->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 支付有礼活动
     */
    public function payGift($website_id) {
        if(!getAddons('paygift', $website_id)){
            return true;
        }
        $paygift = new VslPayGiftModel();
        $paygift->startTrans();
        try {
            $time = time();
            $start = array(
                'website_id' => $website_id,
                'start_time' => array('elt', $time),
                'state' => 1
            );
            $end = array(
                'website_id' => $website_id,
                'end_time' => array('lt', $time),
            );
            $paygift->save(['state' => 2], $start);
            $paygift->save(['state' => 3], $end);
            $paygift->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $paygift->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 关注有礼活动
     */
    public function followGift($website_id) {
        if(!getAddons('followgift', $website_id)){
            return true;
        }
        $followgift = new VslFollowGiftModel();
        $followgift->startTrans();
        try {
            $time = time();
            $start = array(
                'website_id' => $website_id,
                'start_time' => array('elt', $time),
                'state' => 1
            );
            $end = array(
                'website_id' => $website_id,
                'end_time' => array('lt', $time),
            );
            $followgift->save(['state' => 2], $start);
            $followgift->save(['state' => 3], $end);
            $followgift->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $followgift->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 节日关怀活动
     */
    public function festivalCare($website_id) {
        if(!getAddons('festivalcare', $website_id)){
            return true;
        }
        $festivalcare = new VslFestivalCareModel();
        $festivalcare_server = new FestivalCare();
        $festivalcare->startTrans();
        try {
            $time = time();
            $list = $festivalcare->getQuery(['website_id' => $website_id], '*', 'priority desc');
            if ($list) {
                $sign = 0;
                $member_view = new VslMemberViewModel();
                foreach ($list as $k => $v) {
                    $end_time = strtotime(date("Y-m-d", $v['start_time'])) + (86400 - 1);
                    if ($v['start_time'] <= $time && $end_time >= $time && $sign == 0 && $v['state'] == 1) {
                        $festivalcare->where(['festival_care_id' => $v['festival_care_id']])->update(['state' => 2]);
                        $sign = 1;
                        $member_list = $member_view->alias('vm')->join('sys_user su', 'su.uid = vm.uid', 'left')->where(['vm.website_id'=>$website_id])->field('vm.uid,su.wx_openid,vm.group_id')->select();
                        if ($member_list) {
                            foreach ($member_list as $k2 => $v2) {
                                if (!empty($v2['wx_openid'])) {
                                    if ($v['group_id'] > 0){
                                        if(!empty($v2['group_id']) && in_array($v['group_id'], explode(',', $v2['group_id']))){
                                            $festivalcare_server->createFestivalCareRecord($v['festival_care_id'],$v2['uid'],$v2['wx_openid']);
                                        }
                                    }else{
                                        $festivalcare_server->createFestivalCareRecord($v['festival_care_id'],$v2['uid'],$v2['wx_openid']);
                                    }
                                }
                            }
                        }
                    } else if ($end_time < $time) {
                        $festivalcare->where(['festival_care_id' => $v['festival_care_id']])->update(['state' => 3]);
                    }
                }
            }
            $festivalcare->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $festivalcare->rollback();
            return $e->getMessage();
        }
    }

    /*
     * 执行任务统计是否完成
     * **/
    public function userTaskRewards($website_id)
    {
        if(!getAddons('taskcenter', $website_id)){
            return true;
        }
        $task_center = new Taskcenter();
        //先查出各自的任务
        $condition['is_complete'] = 0;
        $condition['get_time'] = ['<=',time()];
        $condition['need_complete_time'] = ['>=',time()];
        $condition['website_id'] = $website_id;
        $user_task_mdl = new UserTaskModel();
        $general_poster_mdl = new VslGeneralPosterModel();
        $user_task_info = $user_task_mdl->where($condition)->select();
//        if($website_id == 45){
//            p($user_task_info);
//            exit;
//        }
        if(!$user_task_info){
            sleep(1);
        }
        foreach($user_task_info as $v){
            $general_poster_id = $v['general_poster_id'];
            $uid = $v['uid'];
            //判断该任务是否过期，或者（离任务结束时间 - 接任务时间）>= 规定任务完成时间*60
            $poster_cond['general_poster_id'] = $general_poster_id;
            $poster_cond['start_task_time'] = ['<=', time()];
            $poster_cond['end_task_time'] = ['>', time()];
            $general_poster_info = $general_poster_mdl->getInfo($poster_cond, '*');
            if($general_poster_info){
                $residue_finish_time = ($general_poster_info['end_task_time'] - $v['get_time'])>0 ? $general_poster_info['end_task_time'] - $v['get_time'] : 0;
                $limit_time = $general_poster_info['task_limit_time'] * 3600;
                //判断（离任务结束时间 - 接任务时间）>= 规定任务完成时间*60，若不大于，则取不大于的时间，或者limit_time为0则是取 活动结束 到 领取活动 的时间
                if($residue_finish_time < $limit_time || $limit_time == 0){
                    $limit_time = $residue_finish_time;
                }
                $need_complete_time = $v['get_time'] + $limit_time;
                //判断任务是否完成，发放奖励。
                $task_center->sendReward($general_poster_info, $uid, $v['get_time'], $need_complete_time);
            }
        }

    }

    /**
     * 重新发送 海报奖励发送失败的微信红包
     * @param $website_id
     * @throws \think\Exception\DbException
     */
    public function reSendRedPack($website_id)
    {
        $wx_pai_api = new WxPayApi();
        $red_pack_fail_record_model = new RedPackFailRecordModel();
        $records = $red_pack_fail_record_model::all(['website_id' => $website_id]);
        foreach ($records as $v) {
            $result = $wx_pai_api::sendRedPack($v['act_name'], $v['money'], $v['num'], $v['website_id'], $v['remark'], $v['wishing'], $v['openid'], $v['scene_id']);
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS' && $result['err_code'] == 'SUCCESS') {
                // 发送成功,删除记录
                $red_pack_fail_record_model->delData(['id' => $v['id']]);
            }
        }
    }

    public function updateIncrementOrder($website_id) {
        $increment = new VslIncreMentOrderModel();
        $ids = $increment->Query(['website_id'=>$website_id,'expire_time'=>['<=',time()]],'out_trade_no');
        foreach ($ids as $v){
            $increment = new VslIncreMentOrderModel();
            $increment->save(['expire_time'=>time()],['out_trade_no'=>$v]);
        }
    }
    /**
     * 定时抓取cps拼多多订单，一分钟执行一次
     */
    public function getPddCpsOrderTask($website_id)
    {
        try{
            $addons_config_model = new AddonsConfigModel();
            $value = $addons_config_model->Query(['website_id' => $website_id, 'addons' => 'cpsunion'], 'value')[0];
            if($value) {
                $value = json_decode($value,true);
                if($value['pdd_config'] && $value['pdd_config']['pdd_is_use']) {
                    $page_size = 40;
                    $data = [
                        'type' => 'pdd.ddk.order.list.increment.get',
                        'timestamp' => time(),
                        'start_update_time' => time() - 60,
                        'end_update_time' => time(),
                        'page_size' => $page_size,
                        'page' => 1,
                    ];
                    //获取请求结果
                    $cpsunion_server = new Cpsunion();
                    $res = $cpsunion_server->getPddGetSignAndUrl($data,$website_id);
                    if ($res['order_list_get_response']['total_count']) {
                        //算出总共有多少页
                        $page_count = ceil($res['order_list_get_response']['total_count'] / $page_size);
                        $cpsunion_server->getPddCpsOrder($page_count,$website_id);
                        return 1;
                    }
                }
            }
        }catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }

    }
    /**
     * 定时抓取cps京东订单，一分钟执行一次
     */
    public function getJdOrderTask($website_id,$page_index = 0)
    {
        try{
            $addons_config_model = new AddonsConfigModel();
            $value = $addons_config_model->Query(['website_id' => $website_id, 'addons' => 'cpsunion'], 'value')[0];
            if($value) {
                $value = json_decode($value,true);
                if($value['jd_config'] && $value['jd_config']['jd_is_use']) {
                    $pageIndex = $page_index + 1;
                    $param_json = [
                        'orderReq' => [
                            'pageIndex' => $pageIndex,
                            'pageSize' => 50,
                            'type' => 3,
                            'startTime' => date('Y-m-d H:i:s', time() - 60),
                            'endTime' => date('Y-m-d H:i:s', time()),
                        ]
                    ];
                    $method = 'jd.union.open.order.row.query';
                    //获取请求结果
                    $cpsunion_server = new Cpsunion();
                    $res = $cpsunion_server->getJdSignAndUrl($param_json, $method,$website_id);
                    if ($res['jd_union_open_order_row_query_response']['result']) {
                        $result = json_decode($res['jd_union_open_order_row_query_response']['result'], true);
                        if ($result['data']) {
                            $cpsunion_server->getJdCpsOrder($result['data'], $pageIndex, $result['hasMore'],$website_id);
                            return 1;
                        }
                    }
                }
            }
        }catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }
    }
    /**
     * 发放拼多多订单的奖金,每天执行一次
     */
    public function grantPddOrderBonus($website_id)
    {
        try{
            $addons_config_model = new AddonsConfigModel();
            $value = $addons_config_model->Query(['website_id' => $website_id, 'addons' => 'cpsunion'], 'value')[0];
            if($value) {
                $value = json_decode($value,true);
                if($value['pdd_config'] && $value['pdd_config']['pdd_is_use']) {
                    $now_day = date('j',time());
                    if($now_day == $value['pdd_config']['pdd_payment_day']) {
                        //判断是否到了后台设置的结算日
                        $cpsunion_server = new Cpsunion();
                        $condition = [
                            'co.order_create_time' => ['<', time()],
                            'co.order_from' => 'pdd',
                            'co.order_status' => 5,
                            'cob.grant_status' => 0,
                            'co.website_id' => $website_id,
                        ];
                        $condition1 = [
                            'order_from' => 'pdd',
                            'grant_status' => 0,
                            'website_id' => $website_id,
                        ];
                        $res = $cpsunion_server->grantPddOrderBonus($condition, $condition1,$website_id);
                        return $res;
                    }
                }
            }
        }catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }

    }
    /**
     * 发放京东订单的奖金,每天执行一次
     */
    public function grantJdOrderBonus($website_id)
    {
        try{
            $addons_config_model = new AddonsConfigModel();
            $value = $addons_config_model->Query(['website_id' => $website_id, 'addons' => 'cpsunion'], 'value')[0];
            if($value) {
                $value = json_decode($value,true);
                if ($value['jd_config'] && $value['jd_config']['jd_is_use']) {
                    $now_day = date('j', time());
                    if ($now_day == $value['jd_config']['jd_payment_day']) {
                        //判断是否到了后台设置的结算日
                        $cpsunion_server = new Cpsunion();
                        $condition = [
                            'co.order_create_time' => ['<', time()],
                            'co.order_from' => 'jd',
                            'co.order_status' => 17,
                            'cob.grant_status' => 0,
                            'co.website_id' => $website_id,
                        ];
                        $condition1 = [
                            'order_from' => 'jd',
                            'grant_status' => 0,
                            'website_id' => $website_id,
                        ];
                        $res = $cpsunion_server->grantJdOrderBonus($condition, $condition1,$website_id);
                        return $res;
                    }
                }
            }
        }catch (\Exception $e) {
            recordErrorLog($e);
            return $e->getMessage();
        }
    }
    /*
     * 检查直播间是否还剩10分钟开播
     * **/
    public function __checkLiveCountDown($condition, $website_id)
    {
        try{
            Db::startTrans();
            $live = new LiveModel();
            $anchor = new AnchorModel();
            $live_shopping = new Liveshopping();
            $message_push = new SysMessagePushModel();
            $user_follow = new VslUserFollowModel();
            $live_list = $live->pageQuery(1, 20, $condition, 'live_id desc', '*');
            foreach($live_list['data'] as $k=>$live_info){
                $anchor_id = $live_info['anchor_id'];
                $anchor_info = $anchor->getInfo(['anchor_id' => $anchor_id], 'uid');
                $uid = $anchor_info['uid'];
                //主播提醒
                $message_cond['template_type'] = 'advance_remaind';
                $message_cond['website_id'] = $website_id;
                $message_cond['type'] = 5;
                $push_info = $message_push->getInfo($message_cond, 'template_content, is_enable, template_type, template_title');
                $live_shopping->sendMessage($push_info, $anchor_id, $uid);
                //查询给我关注的那些人（想看直播的人）推送提醒信息
                $follow_cond['follow_uid'] = $uid; //follow_uid 被关注的人（主播）
                $follow_cond['status'] = 1;//获取关注的
                $follow_cond['follow_type'] = 2;//获取直播关注
                $follow_list = $user_follow->getQuery($follow_cond, 'uid', '');
                //获取推送的信息
                $message_cond2['template_type'] = 'live_play_notice';
                $message_cond2['website_id'] = $website_id;
                $message_cond2['type'] = 5;
                $push_info2 = $message_push->getInfo($message_cond2, 'template_content, is_enable, template_type, template_title');
                foreach($follow_list as $k1=>$follow_info){
                    $uid = $follow_info['uid'];
                    $live_shopping->sendMessage($push_info2, 0, $uid);
                }
                //将这条直播信息更新为已通知用户的状态
                $live_data['has_remind'] = 1;
                $live->save($live_data, ['live_id' => $live_info['live_id'], 'has_remind' => 0]);
            }
            Db::commit();
        }catch(\Exception $e){
            Db::rollback();
        }
    }
    /*
     * 每分钟检查是否有直播间断开的没有重新连接的
     * **/
    public function __actDisconnectLiveStatus($website_id)
    {
        $live_cond['status'] = 2;
        $live_cond['website_id'] = $website_id;
        $live_cond['disconnect_time'] = [
            ['<=', time()-10*60],
            ['neq', 0]
        ];
        $live = new LiveModel();
        $live_list = $live->getQuery($live_cond, '', '');
        foreach($live_list as $k=>$live_info){
            $data['status'] = 4;
            $live->save($data, ['live_id' => $live_info['live_id'], 'status' => 2]);
        }
    }
    /*
     * 定时任务解禁主播
     * **/
    public function __unforbidAnchor($website_id)
    {
        $anchor = new AnchorModel();
        $condition['status'] = 0;
        $condition['forbid_end_time'] = [['neq', 0],['<', time()]];
        $condition['website_id'] = $website_id;
        $data['status'] = 1;//改为正常
        $anchor_list = $anchor->pageQuery(1, 20, $condition, 'anchor_id desc', 'anchor_id');
        foreach($anchor_list['data'] as $k=>$anchor_info){
            $anchor->save($data, ['anchor_id' => $anchor_info['anchor_id']]);//
        }
    }
    /**
     * 手动修正分红结算(订单完成)
     */
    public function handUpdateOrderGlobalBonus()
    {
//        debugLog('我进来了分红计算2');
        $website_id = '4461';
        $create_time = 1584547200;
        $global_service = new \addons\globalbonus\service\GlobalBonus();
//        $order = new VslOrderModel();
//        $buyer_id = $order->getInfo(['website_id'=>$website_id,'order_id'=>$order_id],'buyer_id')['buyer_id'];
        $list = $global_service->getGlobalBonusSite($website_id);
        $agent = new VslOrderBonusModel();
//        $member = array_unique($agent->Query(['website_id'=>$website_id,'from_type'=>1,'order_id'=>$order_id, 'pay_status' => 1, 'cal_status' =>0],'uid'));
        $member = $agent->alias('ob')->field('ob.*')->join('vsl_order o', 'ob.order_id=o.order_id', 'left')->where(['o.order_status' => 4, 'o.create_time'=>['>=', $create_time], 'o.website_id' => $website_id, 'ob.pay_status'=>1, 'ob.cal_status' => 0, 'ob.from_type'=>1])->limit(0,300)->select();
//        $member2 = $agent->alias('ob')->field('sum(ob.bonus) as total_bonus')->join('vsl_order o', 'ob.order_id=o.order_id', 'left')->where(['o.order_status' => 4, 'o.create_time'=>['>=', $create_time], 'o.website_id' => $website_id, 'ob.pay_status'=>1, 'ob.cal_status' => 0])->select();
        $member = objToArr($member);
//        echo $agent->getLastSql();
//        p($member);
//        exit;
        if ($list && $list['is_use'] == 1) {//判断当前是否开启全球分红应用
            $accountRecord = [];
            $ungrantBonus = [];
            foreach ($member as $k => $v) {
                //订单交易完成状态
                $data['status'] = 1;
                $data['order_id'] = $v['order_id'];
                $data['website_id'] = $website_id;
                $data['uid'] = $v['uid'];
                $data['bonus'] = $agent->getSum(['order_id'=>$v['order_id'],'uid'=>$v['uid'],'from_type'=>1],'bonus');//分红
                $check_pay_status = $agent->getInfo(['order_id'=>$v['order_id'],'uid'=>$v['uid'],'from_type'=>1], 'cal_status');
                if($check_pay_status['cal_status'] == 1){
                    continue;
                }
                // 发放订单的全球分红
                $result = $global_service->addGlobalBonus($data);
                if($result['account_record']){
                    $accountRecord[] = $result['account_record'];
                }
                if($result['ungrant_bonus']){
                    $ungrantBonus[] = $result['ungrant_bonus'];
                }
                // 更新相关股东等级
//                $global_service->updateAgentLevelInfo($v['uid']);
            }
            $agentAccountRecord = new VslAgentAccountRecordsModel();

            $res = $agentAccountRecord->insertAll($accountRecord);
            $unGrantBonusModel = new VslUnGrantBonusOrderModel();
            $res2=$unGrantBonusModel->insertAll($ungrantBonus);
//            debugLog($accountRecord,'全球分红==>'.'结果：'.var_export($res, 1));
//            debugLog($ungrantBonus,'全球分红2==>结果2：'.var_export($res2, 1));

        }
    }
    /*
     * 更新股东等级
     */
    public function updateAgentLevelInfo($website_id = 0){
        $globalBonus = new GlobalBonus();
        $memberModel = new VslMemberModel();
        $agentList = $memberModel->Query(['is_global_agent' => 2, 'website_id' => $website_id], 'uid');
        if(!$agentList){
            return true;
        }
        foreach($agentList as $v){
            $globalBonus->updateAgentLevelInfo($v);
        }
        unset($v);
    }
}
