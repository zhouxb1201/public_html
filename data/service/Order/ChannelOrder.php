<?php

namespace data\service\Order;

use addons\channel\model\VslChannelOrderModel;
use addons\seckill\model\VslSeckGoodsModel;
use addons\seckill\server\Seckill;
use data\model\AlbumPictureModel;
use data\model\ConfigModel;
use data\model\VslActivityOrderSkuRecordModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslGoodsSpecValueModel;
use data\model\VslOrderActionModel as VslOrderActionModel;
use data\model\VslOrderExpressCompanyModel;
use data\model\VslOrderGoodsExpressModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderGoodsPromotionDetailsModel;
use data\model\VslOrderModel;
use data\model\VslOrderPickupModel;
use data\model\VslOrderPromotionDetailsModel;
use data\model\VslOrderSkuRecordModel;
use data\model\VslPickupPointModel;
use data\model\VslPromotionFullMailModel;
use data\model\VslPromotionMansongRuleModel;
use data\model\UserModel as UserModel;
use data\service\Address;
use data\service\BaseService;
use data\service\Config;
use data\service\GoodsCalculate\GoodsCalculate;
use data\service\Member\MemberAccount;
use addons\coupontype\server\Coupon as CouponServer;
use data\service\Order\OrderStatus;
use data\service\promotion\GoodsExpress;
use data\service\promotion\GoodsMansong;
use data\service\promotion\GoodsPreference;
use data\service\UnifyPay;
use data\service\WebSite;
use think\Db;
use think\Log;
use data\model\VslOrderRefundAccountRecordsModel;
use addons\shop\model\VslShopModel;

/**
 * 订单操作类
 */
class ChannelOrder extends BaseService
{

    public $order;

    // 订单主表
    function __construct()
    {
        parent::__construct();
        $this->order = new VslOrderModel();
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
            $this->order->where([
                'out_trade_no' => $order_pay_no
            ])->select();

            // 添加订单日志
            // 可能是多个订单
            $order_id_array = $this->order->where([
                'out_trade_no' => $order_pay_no
            ])->column('order_id');
            foreach ($order_id_array as $k => $order_id) {
                // 赠送赠品
                $uid = $this->order->getInfo([
                    'order_id' => $order_id
                ], 'buyer_id,pay_money');
                if ($pay_type == 10) {
                    // 线下支付
                    $this->addOrderAction($order_id, $this->uid, '线下支付');
                } else {
                    // 查询订单购买人ID
                    $this->addOrderAction($order_id, $uid['buyer_id'], '订单支付');
                }
                // 增加会员累计消费
                $account = new MemberAccount();
                $account->addMmemberConsum(0, $uid['buyer_id'], $uid['pay_money']);
                // 修改订单状态
                $data = array(
                    'payment_type' => $pay_type,
                    'pay_status' => 2,
                    'pay_time' => time(),
                    'order_status' => 1
                ); // 订单转为待发货状态

                $order = new VslOrderModel();
                $order->save($data, [
                    'order_id' => $order_id
                ]);
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
        $this->order->startTrans();
        try {
            $data_complete = array(
                'order_status' => 4,
                'finish_time' => time()
            );
            $order_model = new VslOrderModel();
            $order_model->save($data_complete, [
                'order_id' => $order_id
            ]);
            $uid = $order_model->getInfo(['order_id'=>$order_id],'buyer_id')['uid'];
            $this->addOrderAction($order_id, $uid, '交易完成');
//            $this->calculateOrderGivePoint($order_id);
            $this->calculateOrderMansong($order_id);
            // 判断是否需要在本阶段赠送积分
//            $this->giveGoodsOrderPoint($order_id, 1);
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
    public function orderClose($orderid)
    {
        $this->order->startTrans();
        try {
            $order_info = $this->order->getInfo([
                'order_id' => $orderid
            ], 'order_status,pay_status,point, coupon_id, user_money, buyer_id,shop_id,user_platform_money, coin_money,deduction_point');
            $data_close = array(
                'order_status' => 5
            );
            $order_model = new VslOrderModel();
            $order_model->save($data_close, [
                'order_id' => $orderid
            ]);
            $account_flow = new MemberAccount();
            if ($order_info['order_status'] == 0) {
                // 会员余额返还
                if ($order_info['user_money'] > 0) {
                    $account_flow->addMemberAccountData(2, $order_info['buyer_id'], 1, $order_info['user_money'], 2, $orderid, '订单关闭返还用户余额');
                }
                // 平台余额返还

                if ($order_info['user_platform_money'] > 0) {
                    $account_flow->addMemberAccountData(2, $order_info['buyer_id'], 1, $order_info['user_platform_money'], 2, $orderid, '商城订单关闭返还平台余额');
                }
            }
            // 积分返还

            if ($order_info['point'] > 0) {
                $account_flow->addMemberAccountData(1, $order_info['buyer_id'], 1, $order_info['point'], 2, $orderid, '订单关闭返还积分');
            }
            if ($order_info['deduction_point'] > 0) {
                $account_flow->addMemberAccountData(1, $order_info['buyer_id'], 1, $order_info['deduction_point'], 2, $orderid, '订单关闭返还积分');
            }
            if ($order_info['coin_money'] > 0) {
                $coin_convert_rate = $account_flow->getCoinConvertRate();
                $account_flow->addMemberAccountData(3, $order_info['buyer_id'], 1, $order_info['coin_money'] / $coin_convert_rate, 2, $orderid, '订单关闭返还购物币');
            }

            // 优惠券返还
            $couponServer = new CouponServer();
            if ($order_info['coupon_id'] > 0) {
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
                ], 'goods_id, stock');
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
                    if(getAddons('seckill', $this->website_id, $this->instance_id)){
                        //判断是否是秒杀商品，是的话加秒杀活动库存
                        $seckill_server = new Seckill();
                        $order_seckill_list = $seckill_server->orderSkuIsSeckill($orderid, $v['sku_id']);
                    }
                    if($order_seckill_list){
                        $seckill_id = $order_seckill_list['promotion_id'];
                        //加秒杀活动库存
                        $seckill_server->addSeckillGoodsStock($seckill_id, $v['sku_id'], $v['num']);
                        //减商品销量 lgq加
                        $goods_calculate->subGoodsSales($goods_sku_info['goods_id'], $v['num']);
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
                        $goods_calculate->subGoodsSales($goods_sku_info['goods_id'], $v['num']);
                    }

                }
            }
            $this->addOrderAction($orderid, $this->uid, '交易关闭');
            $this->order->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            Log::write($e->getMessage());
            $this->order->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 订单状态变更
     *
     * @param unknown $order_id
     * @param unknown $order_goods_id
     */
    public function orderGoodsRefundFinish($order_id)
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
            $all_refund = 0;
            if (($refunding_count + $refunded_count) == $total_count) {
                // 全部订单商品参与过售后，订单状态才是售后
                $orderInfo->order_status = OrderStatus::getOrderCommonStatus()[-1]['status_id']; // 售后中

            } elseif ($refunded_count == $total_count) {
                $all_refund = 1;

            } elseif ($shipping_status == OrderStatus::getShippingStatus()[0]['shipping_status']) {
                $orderInfo->order_status = OrderStatus::getOrderCommonStatus()[1]['status_id']; // 待发货

            } elseif ($shipping_status == OrderStatus::getShippingStatus()[1]['shipping_status']) {
                $orderInfo->order_status = OrderStatus::getOrderCommonStatus()[2]['status_id']; // 已发货

            } elseif ($shipping_status == OrderStatus::getShippingStatus()[2]['shipping_status']) {
                $orderInfo->order_status = OrderStatus::getOrderCommonStatus()[3]['status_id']; // 已收货

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
    public function getDetail($order_id)
    {
        // 查询主表
        $order_detail = $this->order->getInfo([
            "order_id" => $order_id,
            "is_deleted" => 0
        ]);
        if (empty($order_detail)) {
            return array();
        }
        if(getAddons('shop', $this->website_id)){
            $shop = new VslShopModel();
            $detail = $shop->getInfo(['shop_id' => $order_detail['shop_id'], 'website_id' => $this->website_id], 'shop_phone,shop_name');
            $order_detail['shop_name'] = $detail['shop_name'];
            $order_detail['shop_phone'] = $detail['shop_phone'];
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
        $order_detail['payment_type_name'] = OrderStatus::getPayType($order_detail['payment_type']);
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
        $order_detail['order_goods'] = $this->getOrderGoods($order_id);
        if ($order_detail['payment_type'] == 6 || $order_detail['shipping_type'] == 2) {
            $order_status = OrderStatus::getSinceOrderStatus();
        } else {
            // 查询操作项
            $order_status = OrderStatus::getOrderCommonStatus();
        }
        // 查询订单提货信息表
        if ($order_detail['shipping_type'] == 2) {
            $order_pickup_model = new VslOrderPickupModel();
            $order_pickup_info = $order_pickup_model->getInfo([
                'order_id' => $order_id
            ], '*');
            $address = new Address();
            $order_pickup_info['province_name'] = $address->getProvinceName($order_pickup_info['province_id']);
            $order_pickup_info['city_name'] = $address->getCityName($order_pickup_info['city_id']);
            $order_pickup_info['dictrict_name'] = $address->getDistrictName($order_pickup_info['district_id']);
            $order_detail['order_pickup'] = $order_pickup_info;
        } else {
            $order_detail['order_pickup'] = '';
        }
        // 查询订单操作
        foreach ($order_status as $k_status => $v_status) {

            if ($v_status['status_id'] == $order_detail['order_status']) {
                $order_detail['operation'] = $v_status['operation'];
                $order_detail['status_name'] = $v_status['status_name'];
            }
        }
        // 查询订单操作日志
        $order_action = new VslOrderActionModel();
        $order_action_log = $order_action->getQuery([
            'order_id' => $order_id
        ], '*', 'action_time desc');
        $order_detail['order_action'] = $order_action_log;

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
    public function getOrderGoods($order_id)
    {
        $order_goods = new VslOrderGoodsModel();
        $order_goods_list = $order_goods->all(['order_id' => $order_id]);
        foreach ($order_goods_list as $k => $v) {

            // 查询商品sku表开始
            $goods_sku = new VslGoodsSkuModel();
            $goods_sku_info = $goods_sku->getInfo([
                'sku_id' => $v['sku_id']
            ], 'code,attr_value_items');
            $order_goods_list[$k]['code'] = $goods_sku_info['code'];
            $order_goods_list[$k]['spec'] = [];
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
                $order_goods_list[$k]['spec'] = $spec_info;
                unset($sku_spec_value_info, $goods_sku_info, $sku_spec_info, $spec_info);
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
     * @param unknown $order_id
     * @param unknown $goods_money
     *            调整后的商品总价
     * @param unknown $shipping_fee
     *            调整后的运费
     */
    public function orderAdjustMoney($order_id, $goods_money, $shipping_fee)
    {
        $this->order->startTrans();
        try {
            $order_model = new VslOrderModel();
            $order_info = $order_model->getInfo([
                'order_id' => $order_id
            ], 'goods_money,shipping_money,order_money,pay_money');
            // 商品金额差额
            $goods_money_adjust = $goods_money - $order_info['goods_money'];
            $shipping_fee_adjust = $shipping_fee - $order_info['shipping_money'];
            $order_money = $order_info['order_money'] + $goods_money_adjust + $shipping_fee_adjust;
            $pay_money = $order_info['pay_money'] + $goods_money_adjust + $shipping_fee_adjust;
            $data = array(
                'goods_money' => $goods_money,
                'order_money' => $order_money,
                'shipping_money' => $shipping_fee,
                'pay_money' => $pay_money
            );
            $retval = $order_model->save($data, [
                'order_id' => $order_id
            ]);
            $this->addOrderAction($order_id, $this->uid, '调整金额');
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
            return $order_info['pay_money'] + $order_info['user_platform_money'];
        } else {
            return 0;
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
            'remark' => $remark
        );
        $res = $model->save($data);
        return $res;
    }
}