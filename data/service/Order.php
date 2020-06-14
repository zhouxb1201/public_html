<?php

namespace data\service;
/**
 * 订单
 */
use addons\bargain\service\Bargain;
use addons\blockchain\model\VslBlockChainRecordsModel;
use addons\blockchain\service\Block;
use addons\channel\model\VslChannelGoodsModel;
use addons\channel\model\VslChannelGoodsSkuModel;
use addons\channel\model\VslChannelModel;
use addons\channel\model\VslChannelOrderGoodsModel;
use addons\channel\model\VslChannelOrderModel;
use addons\channel\model\VslChannelOrderSkuRecordModel;
use addons\channel\server\Channel;
use addons\coupontype\model\VslCouponModel;
use addons\integral\model\VslIntegralGoodsModel;
use addons\integral\model\VslIntegralGoodsSkuModel;
use addons\invoice\server\Invoice as InvoiceService;
use addons\microshop\service\MicroShop as MicroShopService;
use addons\seckill\server\Seckill;
use addons\shop\model\VslShopInfoModel;
use addons\shop\service\Shop;
use addons\store\server\Store as storeServer;
use data\model\AddonsConfigModel;
use data\model\AlbumPictureModel;
use data\model\CityModel;
use data\model\DistrictModel;
use data\model\SysAddonsModel;
use data\model\UserModel;
use data\model\VslAccountRecordsModel;
use data\model\VslCartModel;
use data\model\VslGoodsDeletedModel;
use data\model\VslGoodsEvaluateModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslGoodsSpecValueModel;
use data\model\VslIncreMentOrderPaymentModel;
use data\model\VslIncreMentOrderModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslMemberModel;
use data\model\VslOrderCalculateModel;
use data\model\VslOrderExpressCompanyModel;
use data\model\VslOrderGoodsExpressModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderMemoModel;
use data\model\VslOrderModel;
use data\model\VslOrderPaymentModel;
use data\model\VslOrderRefundModel;
use data\model\VslOrderShopReturnModel;
use data\model\VslPresellModel;
use data\model\VslPromotionDiscountModel;
use data\model\VslPromotionMansongRuleModel;
use data\model\VslGoodsTicketModel;
use addons\shop\model\VslShopModel;
use data\model\ProvinceModel; 
use data\model\VslStoreCartModel;
use data\model\VslStoreGoodsSkuModel;
use data\service\Goods as GoodsService;
use data\service\GoodsCalculate\GoodsCalculate;
use data\service\Order\Order as OrderBusiness;
use data\service\Order\OrderAccount;
use data\service\Order\OrderExpress;
use data\service\Order\OrderGoods;
use data\service\Order\OrderStatus;
use data\service\Pay\tlPay;
use data\service\Pay\WeiXinPay;
use data\service\promotion\GoodsExpress;
use data\service\promotion\GoodsPreference;
use Symfony\Component\Yaml\Tests\B;
use think\Cookie;
use think\Db;
use think\Log;
use data\model\VslOrderRefundAccountRecordsModel;
use addons\distribution\model\VslOrderDistributorCommissionModel;
use addons\bonus\model\VslOrderBonusModel;
use addons\presell\service\Presell as PresellService;
use addons\seckill\server\Seckill as SeckillServer;
use data\model\VslShopEvaluateModel;
use think\Session;
use addons\groupshopping\server\GroupShopping;
use addons\store\server\Store;
use addons\paygift\server\PayGift;
use addons\store\model\VslStoreModel;
use addons\store\model\VslStoreAssistantModel;
use addons\microshop\model\VslOrderMicroShopProfitModel;
use data\model\VslOrderPromotionDetailsModel;
use data\model\VslOrderGoodsPromotionDetailsModel;
use data\service\Config;
use addons\distribution\service\Distributor as  DistributorService;
use addons\invoice\server\Invoice as InvoiceServer;
use data\service\Pay\GlobePay;
class Order extends BaseService
{


    function __construct()
    {
        parent::__construct();

    }

    /*
     * (non-PHPdoc)订单详情
     * @see \data\api\IOrder::getOrderDetail()
     */
    public function getOrderDetail($order_id, $channel_status = '')
    {
        // 查询主表信息
        $order = new OrderBusiness();
        $detail = $order->getDetail($order_id, $channel_status);
        
        if (empty($detail)) {
            return array();
        }
        //查询订单分红
        if (getAddons('globalbonus', $this->website_id) || getAddons('teambonus', $this->website_id) || getAddons('areabonus', $this->website_id)) {
            $order_bonus = new VslOrderBonusModel();
            $detail['global_bonus'] = $order_bonus->getSum(['order_id' => $order_id, 'from_type' => 1], 'bonus');
            $detail['area_bonus'] = $order_bonus->getSum(['order_id' => $order_id, 'from_type' => 2], 'bonus');
            $detail['team_bonus'] = $order_bonus->getSum(['order_id' => $order_id, 'from_type' => 3], 'bonus');
        }

        //查询订单佣金
        if (getAddons('distribution', $this->website_id)){
            $order_commission = new VslOrderDistributorCommissionModel();
            $member = new UserModel();
            $orders = $order_commission->Query(['order_id' => $order_id,'return_status' => 0], '*');
            foreach ($orders as $key1 => $value) {
                if($value['commissionA_id']){
                    $detail['commissionA_id'] = $value['commissionA_id'];
                    $commissionA_info = $member->getInfo(['uid' => $value['commissionA_id']], '*');
                    if ($commissionA_info['user_name']) {
                        $detail['commissionA_name'] = $commissionA_info['user_name'];
                    } else {
                        $detail['commissionA_name'] = $commissionA_info['nick_name'];
                    }
                    $detail['commissionA_user_headimg'] = $commissionA_info['user_headimg'];
                    $detail['commissionA_mobile'] = $commissionA_info['user_tel'];
                    $detail['commissionA'] += $value['commissionA'];
                    $detail['pointA'] += $value['pointA'];
                }
                if($value['commissionB_id']){
                    $detail['commissionB_id'] = $value['commissionB_id'];
                    $commissionB_info = $member->getInfo(['uid' => $value['commissionB_id']], '*');
                    if ($commissionB_info['user_name']) {
                        $detail['commissionB_name'] = $commissionB_info['user_name'];
                    } else {
                        $detail['commissionB_name'] = $commissionB_info['nick_name'];
                    }
                    $detail['commissionB_user_headimg'] = $commissionB_info['user_headimg'];
                    $detail['commissionB_mobile'] = $commissionB_info['user_tel'];
                    $detail['commissionB'] += $value['commissionB'];
                    $detail['pointB'] += $value['pointB'];
                }
                if($value['commissionC_id']){
                    $detail['commissionC_id'] = $value['commissionC_id'];
                    $commissionC_info = $member->getInfo(['uid' => $value['commissionC_id']], '*');
                    if ($commissionC_info['user_name']) {
                        $detail['commissionC_name'] = $commissionC_info['user_name'];
                    } else {
                        $detail['commissionC_name'] = $commissionC_info['nick_name'];
                    }
                    $detail['commissionC_user_headimg'] = $commissionC_info['user_headimg'];
                    $detail['commissionC_mobile'] = $commissionC_info['user_tel'];
                    $detail['commissionC'] += $value['commissionC'];
                    $detail['pointC'] += $value['pointC'];
                }
            }
        }
        if (getAddons('microshop', $this->website_id, $this->instance_id)){
            $order_profit = new VslOrderMicroShopProfitModel();
            $member = new UserModel();
            $orders = $order_profit->Query(['order_id' => $order_id], '*');
            foreach ($orders as $key1 => $value) {
                if($value['profitA_id']){
                    $detail['profitA_id'] = $value['profitA_id'];
                    $profitA_info = $member->getInfo(['uid' => $value['profitA_id']], '*');
                    if ($profitA_info['user_name']) {
                        $detail['profitA_name'] = $profitA_info['user_name'];
                    } else {
                        $detail['profitA_name'] = $profitA_info['nick_name'];
                    }
                    $detail['profitA_user_headimg'] = $profitA_info ['user_headimg'];
                    $detail['profitA_mobile'] = $profitA_info['user_tel'];
                    $detail['profitA'] += $value['profitA'];
                    $detail['pointA'] += $value['pointA'];
                }
                if($value['profitB_id']){
                    $detail['profitB_id'] = $value['profitB_id'];
                    $profitB_info = $member->getInfo(['uid' => $value['profitB_id']], '*');
                    if ($profitB_info['user_name']) {
                        $detail['profitB_name'] = $profitB_info['user_name'];
                    } else {
                        $detail['profitB_name'] = $profitB_info['nick_name'];
                    }
                    $detail['profitB_user_headimg'] = $profitB_info['user_headimg'];
                    $detail['profitB_mobile'] = $profitB_info['user_tel'];
                    $detail['profitB'] += $value['profitB'];
                    $detail['pointB'] += $value['pointB'];
                }
                if($value['profitC_id']){
                    $detail['profitC_id'] = $value['profitC_id'];
                    $profitC_info = $member->getInfo(['uid' => $value['profitC_id']], '*');
                    if ($profitC_info['user_name']) {
                        $detail['profitC_name'] = $profitC_info['user_name'];
                    } else {
                        $detail['profitC_name'] = $profitC_info['nick_name'];
                    }
                    $detail['profitC_user_headimg'] = $profitC_info['user_headimg'];
                    $detail['profitC_mobile'] = $profitC_info['user_tel'];
                    $detail['profitC'] += $value['profitC'];
                    $detail['pointC'] += $value['pointC'];
                }
            }
        }
        $detail['pay_status_name'] = $this->getPayStatusInfo($detail['pay_status'])['status_name'];
        $detail['shipping_status_name'] = $this->getShippingInfo($detail['shipping_status'])['status_name'];
        $detail['order_type_name'] = OrderStatus::getOrderType($detail['order_type'])?:'商品订单';
        $express_list = $this->getOrderGoodsExpressList($order_id);
        // 未发货的订单项
        $order_goods_list = array();
        // 已发货的订单项
        $order_goods_delive = array();
        // 没有配送信息的订单项
        $order_goods_exprss = array();
        $detail['order_adjust_money'] = 0;// 订单金额调整
        $detail['goods_type'] = 0;
        if($detail['order_goods']){
            $detail['goods_type'] = $detail["order_goods"][0]['goods_type'];
        }
        foreach ($detail["order_goods"] as $order_goods_obj) {
            $detail['order_adjust_money'] += $order_goods_obj['adjust_money'] * $order_goods_obj['num'];
            $order_goods_obj['order_goods_promotion_money'] = ($order_goods_obj['price'] - $order_goods_obj['actual_price'] + $order_goods_obj['adjust_money']) * $order_goods_obj['num'];
            if($order_goods_obj['presell_id']){
                $order_goods_obj['order_goods_promotion_money'] = 0;
            }
            if ($order_goods_obj["shipping_status"] == 0) {
                // 未发货
                $order_goods_list[] = $order_goods_obj;
            } else {
                $order_goods_delive[] = $order_goods_obj;
            }
        }
        // 订单优惠金额
        
        // $n4 = bcsub($n1,$n2,2); //php高精度运算

        $detail['order_promotion_money'] = bcsub(($detail['goods_money'] + $detail['shipping_money'] + $detail['order_adjust_money']),($detail['promotion_free_shipping'] + $detail['order_money']),2);
        
        if($detail['deduction_money']>0){
            $detail['order_promotion_money'] = "{$detail['order_promotion_money']}" - "{$detail['deduction_money']}";
        }
        
        $detail["order_goods_no_delive"] = $order_goods_list;
        // 没有配送信息的订单项
        if (!empty($order_goods_delive) && count($order_goods_delive) > 0) {
            foreach ($order_goods_delive as $goods_obj) {
                $is_have = false;
                $order_goods_id = $goods_obj["order_goods_id"];
                foreach ($express_list as $express_obj) {
                    $order_goods_id_array = $express_obj["order_goods_id_array"];
                    $goods_id_str = explode(",", $order_goods_id_array);
                    if (in_array($order_goods_id, $goods_id_str)) {
                        $is_have = true;
                    }
                }
                if (!$is_have) {
                    $order_goods_exprss[] = $goods_obj;
                }
            }
        }
        $goods_packet_list = array();
        if (count($order_goods_exprss) > 0) {
            $packet_obj = array(
                "packet_name" => "无需物流",
                "express_name" => "",
                "express_code" => "",
                "express_id" => 0,
                "is_express" => 0,
                "order_goods_list" => $order_goods_exprss
            );
            $goods_packet_list[] = $packet_obj;
        }
        if (!empty($express_list) && count($express_list) > 0 && count($order_goods_delive) > 0) {
            $packet_num = 1;
            foreach ($express_list as $express_obj) {
                $packet_goods_list = array();
                $order_goods_id_array = $express_obj["order_goods_id_array"];
                $goods_id_str = explode(",", $order_goods_id_array);
                foreach ($order_goods_delive as $delive_obj) {
                    $order_goods_id = $delive_obj["order_goods_id"];
                    if (in_array($order_goods_id, $goods_id_str)) {
                        $packet_goods_list[] = $delive_obj;
                    }
                }
                $packet_obj = array(
                    'packet_name' => '包裹' . $packet_num,
                    'express_name' => $express_obj['express_name'],
                    'express_company_id' => $express_obj['express_company_id'],
                    'express_code' => $express_obj['express_no'],
                    'express_id' => $express_obj['id'],
                    'is_express' => 1,
                    'order_goods_list' => $packet_goods_list
                );
                //获取收货人后四位手机号
                $receiver_phone = substr($detail['receiver_mobile'], 7);
                $shipping_info = getShipping($express_obj['express_no'], $express_obj['express_company_id'], 'auto', $this->website_id, $receiver_phone);
                if ($shipping_info['code'] == 1) {
                    $packet_obj['shipping_info'] = $shipping_info['data'];
                }
                $packet_num = $packet_num + 1;
                $goods_packet_list[] = $packet_obj;
            }
        }
        $detail["goods_packet_list"] = $goods_packet_list;
        $detail["goods_packet_num"] = count($goods_packet_list);
        $memo_model = new VslOrderMemoModel();
        $memo_lists = $memo_model::all(['order_id' => $order_id], ['user']);
        $detail['memo_lists'] = [];
        foreach ($memo_lists as $k => $v) {
            $memo_data['order_memo_id'] = $v['order_memo_id'];
            $memo_data['order_id'] = $v['order_id'];
            $memo_data['memo'] = $v['memo'];
            $memo_data['create_time'] = $v['create_time'];
            $memo_data['create_date'] = date('Y-m-d H:i:s', $v['create_time']);
            $memo_data['uid'] = $v['uid'];
            $memo_data['user_name'] = $v['user']['user_name'];
            //取回的数据以id顺序，所以在数组头部插入数据让最新的数据出现在头部
            array_unshift($detail['memo_lists'], $memo_data);
        }
        $detail['unrefund'] = 0;
        $detail['unrefund_reason'] = '';
        if(getAddons('groupshopping', $this->website_id) && $detail['group_record_id']){
            $groupServer = new GroupShopping();
            $record = $groupServer->groupRecordDetail($detail['group_record_id']);
            if($record['status'] == 0){
                $detail['unrefund'] = 1;//待成团订单不能退款
                $detail['unrefund_reason'] = '拼团订单暂时无法退款，若在'.time_diff(time(), $record['finish_time']).'未成团，将自动退款！';//成团时限
            }
        }
        //货到付款订单，在未确认收货情况下不能申请退款退货
        if($detail['order_status'] != 5){
            if($detail['payment_type']==4 && ($detail['order_status']<3 || $detail['order_status']>4) && $detail['order_status'] != -1){
                $detail['unrefund'] = 1;
                $detail['unrefund_reason'] = '货到付款订单，在未确认收货情况下不能申请退款退货！';
            }
        }
        
        //预售优惠金额为0
        if($detail['presell_id']>0){
            $detail['order_promotion_money'] = 0;
        }
        return $detail;
        // TODO Auto-generated method stub
    }

    /**
     * 获取订单基础信息
     *
     * @param unknown $order_id
     */
    public function getOrderInfo($order_id)
    {
        $order_model = new VslOrderModel();
        $order_info = $order_model->get($order_id);
        return $order_info;
    }
    public function getIncrementOrderInfo($order_id)
    {
        $order_model = new VslIncreMentOrderModel();
        $order_info = $order_model->getInfo(['order_id'=>$order_id]);
        return $order_info;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::getOrderList()
     */
    public function getOrderList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {//global_bonus
        $wapStore = false;
        if($condition['wapstore']){
            $wapStore = true; 
            unset($condition['wapstore']);
        }
        $order_model = new VslOrderModel();
        //如果有订单表以外的字段，则先按条件查询其他表的orderid，并取出数据的交集，组装到原有查询条件里
        $query_order_ids = 'uncheck';
        $un_query_order_ids = array();
        $checkOthers = false;
        $isGroup = false;
        if ($condition['express_no']) {
            $checkOthers = true;
            $expressNo = $condition['express_no'];
            $orderGoodsExpressModel = new VslOrderGoodsExpressModel();
            $orderGoodsExpressList = $orderGoodsExpressModel->pageQuery($page_index, $page_size, ['express_no' => $expressNo, 'website_id' => $condition['website_id']], '', 'order_id');
            unset($condition['express_no']);
            $express_order_ids = array();
            if ($orderGoodsExpressList['data']) {
                foreach ($orderGoodsExpressList['data'] as $keyEx => $valEx) {
                    $express_order_ids[] = $valEx['order_id'];
                }
                unset($valEx);
            }
            $query_order_ids = $express_order_ids;
        }

        // 接口用
        if ($condition['or'] && $condition['goods_name'] && $condition['shop_name']) {
            $checkOthers = true;
            $orderGoodsModel = new VslOrderGoodsModel();
            $order_goods_condition = ['website_id' => $this->website_id];
            $goods_condition = [
                'goods_name|code' =>  $condition['goods_name']
            ];
            $goods_model = new VslGoodsModel();
            $goods_name = $goods_model->Query($goods_condition,'goods_name')[0];
            $order_goods_condition['goods_name'] = $goods_name;

            $orderGoodsList = $orderGoodsModel->pageQuery(1, 0, $order_goods_condition, '', 'order_id');
            $goods_order_ids = array();
            if ($orderGoodsList['data']) {
                foreach ($orderGoodsList['data'] as $keyG => $valG) {
                    $goods_order_ids[] = $valG['order_id'];
                }
            }

            $order_condition['website_id'] = $condition['website_id'];
            $order_condition['shop_name'] = $condition['shop_name'];
            $order_list = $order_model->pageQuery(1, 0, $order_condition, '', 'order_id');
            if ($order_list['data']) {
                foreach ($order_list['data'] as $valG) {
                    $goods_order_ids[] = $valG['order_id'];
                }
            }
            if ($query_order_ids != 'uncheck') {
                $query_order_ids = array_intersect($query_order_ids, $goods_order_ids);
            } else {
                $query_order_ids = $goods_order_ids;
            }
            unset($condition['or'], $condition['goods_name'], $condition['shop_name'], $order_condition, $order_list);
        }
        if ($condition['goods_name'] || $condition['refund_status']) {
            $checkOthers = true;
            $orderGoodsModel = new VslOrderGoodsModel();
            $order_goods_condition = ['website_id' => $this->website_id];
            if ($condition['goods_name']) {
                if(is_numeric($condition['goods_name'])) {
                    //商品编号搜索
                $goods_condition = [
                        'code' =>  $condition['goods_name'],
                        'website_id' =>  $this->website_id,
                ];
                $goods_model = new VslGoodsModel();
                $goods_name = $goods_model->Query($goods_condition,'goods_name')[0];
                $order_goods_condition['goods_name'] = $goods_name;
                }else{
                    $order_goods_condition['goods_name'] = $condition['goods_name'];
                }
            }
            if ($condition['refund_status']) {
                if ($condition['refund_status'] == 'backList') {
                    $order_goods_condition['refund_status'] = ['neq', 0];
                } else {
                    $order_goods_condition['refund_status'] = ['IN', $condition['refund_status']];
                }
            }
            if ($condition['buyer_id']) {
                $order_goods_condition['buyer_id'] = $condition['buyer_id'];
            }
            $orderGoodsList = $orderGoodsModel->pageQuery(1, 0, $order_goods_condition, '', 'order_id');
            unset($condition['goods_name'], $condition['refund_status']);
            $goods_order_ids = array();
            if ($orderGoodsList['data']) {
                foreach ($orderGoodsList['data'] as $keyG => $valG) {
                    $goods_order_ids[] = $valG['order_id'];
                }
                unset($valG);
            }
            if ($query_order_ids != 'uncheck') {
                $query_order_ids = array_intersect($query_order_ids, $goods_order_ids);
            } else {
                $query_order_ids = $goods_order_ids;
            }
        }
        if ($condition['vgsr_status']) {
            $isGroup = true;
            $checkOthers = true;
            $vgsr_status = $condition['vgsr_status'];
            $group_server = new GroupShopping();
            $unGroupOrderIds = $group_server->getPayedUnGroupOrder($this->instance_id,$this->website_id);

            if ($vgsr_status == 1) {
                if ($query_order_ids != 'uncheck') {
                    $query_order_ids = array_intersect($query_order_ids, $unGroupOrderIds);
                } else {
                    $query_order_ids = $unGroupOrderIds;
                }
            }
            if ($vgsr_status == 2 && $unGroupOrderIds) {
                $un_query_order_ids = $unGroupOrderIds;
            }
            unset($condition['vgsr_status']);
        }
        if ($checkOthers) {
            if ($query_order_ids != 'uncheck') {
                $condition['order_id'] = ['in', implode(',', $query_order_ids)];
            } elseif ($un_query_order_ids) {
                $condition['order_id'] = ['not in', implode(',', $un_query_order_ids)];
            }
        }

        if ($condition['order_memo']){
            $order_memo = true;
            unset($condition['order_memo']);
        }
        if ($condition['order_amount']){
            unset($condition['order_amount']);
            $order_amount = $order_model->getSum($condition,'order_money');
        }
        // 查询主表
        
        $order_list = $order_model->pageQuery($page_index, $page_size, $condition, $order, '*');
        $shop = getAddons('shop', $this->website_id);
        $globalbonus = getAddons('globalbonus', $this->website_id);
        $areabonus = getAddons('areabonus', $this->website_id);
        $teambonus = getAddons('teambonus', $this->website_id);
        $distribution = getAddons('distribution', $this->website_id);
        $store = getAddons('store', $this->website_id);
        $fullcut = getAddons('fullcut', $this->website_id);
        $coupontype = getAddons('coupontype', $this->website_id);
        $groupshopping = getAddons('groupshopping', $this->website_id, $this->instance_id);
        $microshop = getAddons('microshop', $this->website_id, $this->instance_id);
        $order_bonus = ($globalbonus || $areabonus || $teambonus) ? new VslOrderBonusModel() : '';
        $order_commission = $distribution ? new VslOrderDistributorCommissionModel() : '';
        $order_profit = $microshop ?  new VslOrderMicroShopProfitModel() : '';
        $order_memo_model = new VslOrderMemoModel();
        $order_item = new VslOrderGoodsModel();
        $goods_express_model = new VslOrderGoodsExpressModel();
        $province = new ProvinceModel();
        $city = new CityModel();
        $user = new UserModel();
        $district = new DistrictModel();
        if (!empty($order_list['data'])) {
            foreach ($order_list['data'] as $k => $v) {
                if ($v['presell_id'] == 0) {
                $order_list['data'][$k]['pay_money'] += $order_list['data'][$k]['invoice_tax'];
                    $order_list['data'][$k]['order_money'] += $order_list['data'][$k]['invoice_tax'];
                }
                $order_list['data'][$k]['order_point'] = $v['point'];
                //查询订单是否满足满减送的条件
                $order_list['data'][$k]['promotion_status'] = ($order_list['data'][$k]['promotion_money'] + $order_list['data'][$k]['coupon_money'] > 0) ? 1 : 0;
                //预售的应该是定金加上尾款
                $order_list['data'][$k]['first_money'] = $v['pay_money'];
                if ($v['presell_id'] && $v['money_type'] == 2) {
                    $order_list['data'][$k]['order_money'] = $v['pay_money'] + $v['final_money'];
                    $order_list['data'][$k]['pay_money'] = $v['pay_money'] + $v['final_money'];
                }
                $order_list['data'][$k]['global_bonus'] = 0;
                $order_list['data'][$k]['area_bonus'] = 0;
                $order_list['data'][$k]['team_bonus'] = 0;
                if ($groupshopping && $v['group_record_id']) {
                    $group_server = new GroupShopping();
                    $isGroupSuccess = $group_server->groupRecordDetail($v['group_record_id'])['status'];
                }
                //查询订单分红
                if ($globalbonus) {
                    $order_list['data'][$k]['global_bonus'] = $order_bonus->getSum(['website_id' => $this->website_id,'order_id' => $v['order_id'], 'from_type' => 1], 'bonus');
                }
                if ($areabonus) {
                    $order_list['data'][$k]['area_bonus'] = $order_bonus->getSum(['website_id' => $this->website_id,'order_id' => $v['order_id'], 'from_type' => 2], 'bonus');
                }
                if ($teambonus) {
                    $order_list['data'][$k]['team_bonus'] = $order_bonus->getSum(['website_id' => $this->website_id,'order_id' => $v['order_id'], 'from_type' => 3], 'bonus');
                }

                $order_list['data'][$k]['bonus'] = $order_list['data'][$k]['global_bonus'] + $order_list['data'][$k]['area_bonus'] + $order_list['data'][$k]['team_bonus'];
                //查询订单佣金和积分
                $order_list['data'][$k]['commission'] = 0;
                $order_list['data'][$k]['commissionA'] = 0;
                $order_list['data'][$k]['commissionB'] = 0;
                $order_list['data'][$k]['commissionC'] = 0;
                $order_list['data'][$k]['point'] = 0;
                $order_list['data'][$k]['pointA'] = 0;
                $order_list['data'][$k]['pointB'] = 0;
                $order_list['data'][$k]['pointC'] = 0;
                $order_list['data'][$k]['profit'] = 0;
                $order_list['data'][$k]['profitA'] = 0;
                $order_list['data'][$k]['profitB'] = 0;
                $order_list['data'][$k]['profitC'] = 0;
                if ($distribution) {
                    if ($this->instance_id) {
                        $orders = $order_commission->Query(['order_id' => $v['order_id'], 'shop_id' => $this->instance_id,'return_status' => 0], '*');
                    } else {
                        $orders = $order_commission->Query(['order_id' => $v['order_id'],'return_status' => 0], '*');
                    }
                    if($orders){
                        foreach ($orders as $key1 => $value) {
                            if ($value['commissionA_id'] == $v['buyer_id']) {
                                $order_list['data'][$k]['commission'] += $value['commissionA'];
                                $order_list['data'][$k]['point'] += $value['pointA'];
                            }
                            if ($value['commissionB_id'] == $v['buyer_id']) {
                                $order_list['data'][$k]['commission'] += $value['commissionB'];
                                $order_list['data'][$k]['point'] += $value['pointB'];
                            }
                            if ($value['commissionC_id'] == $v['buyer_id']) {
                                $order_list['data'][$k]['commission'] += $value['commissionC'];
                                $order_list['data'][$k]['point'] += $value['pointC'];
                            }
                            if($value['commissionA_id']){
                                $order_list['data'][$k]['commissionA_id'] = $value['commissionA_id'];
                                $member_A = $user->getInfo(['uid' => $value['commissionA_id']], 'user_name,nick_name');
                                if($member_A['user_name']){
                                    $order_list['data'][$k]['commissionA_name'] = $member_A['user_name'];
                                }else{
                                    $order_list['data'][$k]['commissionA_name'] =  $member_A['nick_name'];
                                }
                                $order_list['data'][$k]['commissionA'] += $value['commissionA'];
                                $order_list['data'][$k]['pointA'] += $value['pointA'];
                            }
                            if($value['commissionB_id']){
                                $order_list['data'][$k]['commissionB_id'] = $value['commissionB_id'];
                                $member_B = $user->getInfo(['uid' => $value['commissionB_id']], 'user_name,nick_name');
                                if($member_B['user_name']){
                                    $order_list['data'][$k]['commissionB_name'] = $member_B['user_name'];
                                }else{
                                    $order_list['data'][$k]['commissionB_name'] =  $member_B['nick_name'];
                                }
                                $order_list['data'][$k]['commissionB'] += $value['commissionB'];
                                $order_list['data'][$k]['pointB'] += $value['pointB'];
                            }
                            if($value['commissionC_id']){
                                $order_list['data'][$k]['commissionC_id'] = $value['commissionC_id'];
                                $member_C = $user->getInfo(['uid' => $value['commissionC_id']], 'user_name,nick_name');
                                if($member_C['user_name']){
                                    $order_list['data'][$k]['commissionC_name'] = $member_C['user_name'];
                                }else{
                                    $order_list['data'][$k]['commissionC_name'] =  $member_C['nick_name'];
                                }
                                $order_list['data'][$k]['commissionC'] += $value['commissionC'];
                                $order_list['data'][$k]['pointC'] += $value['pointC'];
                            }
                            $order_list['data'][$k]['commission'] = $order_list['data'][$k]['commissionA'] + $order_list['data'][$k]['commissionB'] + $order_list['data'][$k]['commissionC'];
                        }
                        unset($value);
                    }
                }
                if ($microshop) {
                    $orders = $order_profit->Query(['order_id' => $v['order_id']], '*');
                    foreach ($orders as $key1 => $value) {
                        if ($value['profitA_id'] == $v['buyer_id']) {
                            $order_list['data'][$k]['profit'] += $value['profitA'];
                        }
                        if ($value['profitB_id'] == $v['buyer_id']) {
                            $order_list['data'][$k]['profit'] += $value['profitB'];
                        }
                        if ($value['profitC_id'] == $v['buyer_id']) {
                            $order_list['data'][$k]['profit'] += $value['profitC'];
                        }
                        $order_list['data'][$k]['profitA_id'] = $value['profitA_id'];
                        $order_list['data'][$k]['profitA_name'] = $user->getInfo(['uid' => $value['profitA_id']], 'user_tel')['user_tel'];
                        $order_list['data'][$k]['profitA'] += $value['profitA'];
                        $order_list['data'][$k]['profitB_id'] = $value['profitB_id'];
                        $order_list['data'][$k]['profitB_name'] = $user->getInfo(['uid' => $value['profitB_id']], 'user_tel')['user_tel'];
                        $order_list['data'][$k]['profitB'] += $value['profitB'];
                        $order_list['data'][$k]['profitC_id'] = $value['profitC_id'];
                        $order_list['data'][$k]['profitC_name'] = $user->getInfo(['uid' => $value['profitC_id']], 'user_tel')['user_tel'];
                        $order_list['data'][$k]['profitC'] += $value['profitC'];
                        $order_list['data'][$k]['profit'] = $order_list['data'][$k]['profitA'] + $order_list['data'][$k]['profitB'] + $order_list['data'][$k]['profitC'];
                    }
                    unset($value);
                }
                // 查询订单项表
                
                $order_item_list = $order_item->where([
                    'order_id' => $v['order_id']
                ])->select();

                // 查询最新的卖家备注
                if (isset($order_memo) && $order_memo){
                    $order_list['data'][$k]['order_memo'] = $order_memo_model->where(['order_id' => $v['order_id']])->order('order_memo_id DESC')->limit(1)->find()['memo'];
                }
                $province_info = $province->getInfo(array(
                    "province_id" => $v["receiver_province"]
                ), "province_name");
                $order_list['data'][$k]['receiver_province_name'] = $province_info ? $province_info["province_name"] : '';
                
                $city_info = $city->getInfo(array(
                    "city_id" => $v["receiver_city"]
                ), "city_name");
                $order_list['data'][$k]['receiver_city_name'] = $city_info ? $city_info["city_name"] : '';
                $district_info = $district->getInfo(array(
                    "district_id" => $v["receiver_district"]
                ), "district_name");
                $order_list['data'][$k]['receiver_district_name'] = $district_info ? $district_info["district_name"] : '';
                $order_list['data'][$k]['operation'] = [];
                // 订单来源名称
                $order_from = OrderStatus::getOrderFrom($v['order_from']);
                $order_list['data'][$k]['order_type_name'] = OrderStatus::getOrderType($v['order_type']);
                $order_list['data'][$k]['order_type_color'] = OrderStatus::getOrderTypeColor($v['order_type']);
                $order_list['data'][$k]['order_from_name'] = $order_from['type_name'];
                $order_list['data'][$k]['order_from_tag'] = $order_from['tag'];
                $order_list['data'][$k]['pay_type_name'] = OrderStatus::getPayType($v['payment_type']);
                $order_list['data'][$k]['unrefund'] = 0;
                $order_list['data'][$k]['unrefund_reason'] = '';
                if ($groupshopping && $v['group_record_id']) {
                    $groupServer = new GroupShopping();
                    $record = $groupServer->groupRecordDetail($v['group_record_id']);
                    if($record['status'] == 0){
                        $order_list['data'][$k]['unrefund'] = 1;//待成团订单不能退款
                        $order_list['data'][$k]['unrefund_reason'] = '拼团订单暂时无法退款，若在'.time_diff(time(), $record['finish_time']).'未成团，将自动退款！';
                    }
                }
                //货到付款订单 订单未确认收货前不支持退款退货 edit by 2019/10/12
                if($v['order_status'] != 5){
                    if($v['payment_type']==4 && ($v['order_status'] < 3 || $v['order_status'] > 4) && $v['order_status'] != -1){
                        $order_list['data'][$k]['unrefund'] = 1;//
                        $order_list['data'][$k]['unrefund_reason'] = '货到付款订单在未确认收货前是无法退款的订单！';
                    }
                }

                if ($microshop) {
                    if ($v['order_type'] == 2 || $v['order_type'] == 3 || $v['order_type'] == 4) {
                        $order_list['data'][$k]['unrefund'] = 1;//微店店主续费升级成为店主订单不能退款
                        $order_list['data'][$k]['unrefund_reason'] = '微店店主续费升级和成为店主是无法退款的订单！';
                    }
                }
                if ($shop) {
                    $shop_model = new VslShopModel();
                    $shop_info = $shop_model->getInfo(['shop_id' => $order_list['data'][$k]['shop_id']], 'shop_name');
                    $order_list['data'][$k]['shop_name'] = $shop_info['shop_name'];
                }else{
                    $order_list['data'][$k]['shop_name'] = '自营店';
                }
                if ($order_list['data'][$k]['shipping_type'] == 1) {
                    $order_list['data'][$k]['shipping_type_name'] = '商家配送';
                } elseif ($order_list['data'][$k]['shipping_type'] == 2) {
                    $order_list['data'][$k]['shipping_type_name'] = '门店自提';
                } else {
                    $order_list['data'][$k]['shipping_type_name'] = '';
                }
                // 根据订单类型判断订单相关操作
                if($wapStore){
                    $order_status = OrderStatus::getSinceOrderStatusForStore($order_list['data'][$k]['order_type'], $isGroupSuccess);
                }else{
                    if ($order_list['data'][$k]['payment_type'] == 6 || $order_list['data'][$k]['shipping_type'] == 2) {
                        $order_status = OrderStatus::getSinceOrderStatus($order_list['data'][$k]['order_type'],$isGroupSuccess,$order_list['data'][$k]['card_store_id']);
                    } else {
                        
                        $order_status = OrderStatus::getOrderCommonStatus($order_list['data'][$k]['order_type'],$isGroupSuccess,$order_list['data'][$k]['card_store_id'],$order_item_list?$order_item_list[0]['goods_type']:0);
                    }
                }
                
                $order_list['data'][$k]['excel_order_money'] = $v['goods_money'] + $v['shipping_money'] - $v['promotion_free_shipping'];
                $refund_member_operation = [];
                // 查询订单操作
                foreach ($order_status as $k_status => $v_status) {
                    if ($v_status['status_id'] == $v['order_status']) {
                        //代付定金
                        if($v['presell_id']!=0 && $v['pay_status']==0 && $v['money_type']==0 && $v['order_status'] != 5){
                            $v_status['status_name'] = "待付定金";
                            unset($v_status['operation'][1]);//调整价格 去掉
                        }
                        //待付尾款
                        if($v['presell_id']!=0 && $v['pay_status']==0 && $v['money_type']==1 && $v['order_status'] != 5){
                            $v_status['status_name'] = "待付尾款";
                            unset($v_status['operation'][1]);//调整价格 去掉
                        }

                        //已付定金，去掉定金退款按钮
                        if($v['presell_id']!=0 && $v['pay_status']==0 && $v['money_type']==1 ){
                            $v_status['refund_member_operation'] = [];
                        }
                        //积分订单没有支付、退款
                        if($v['order_type'] == 10){
                            $v_status['refund_member_operation'] = '';
                            //判断当前商品是否是虚拟商品，虚拟商品是没有物流信息的
                            $goods_exchange_type = $order_item_list[0]['goods_exchange_type'];
                            if($goods_exchange_type != 0 ){//是虚拟商品
                                if($v_status['member_operation']){
                                    foreach($v_status['member_operation'] as $s_k=>$s_v){
                                        if($s_v['no'] == 'logistics'){
                                            unset($v_status['member_operation'][$s_k]);
                                        }elseif($s_v['no'] == 'evaluation'){
                                            unset($v_status['member_operation'][$s_k]);
                                        }elseif($s_v['no'] == 'buy_again'){
                                            unset($v_status['member_operation'][$s_k]);
                                        }
                                    }
                                    foreach($v_status['operation'] as $s_k=>$s_v){
                                        if($s_v['no'] == 'logistics'){
                                            unset($v_status['operation'][$s_k]);
                                        }
                                    }
                                }
                            }
                        }
                        //知识付费商品去掉查看物流、再次购买
                        if(count($order_item_list) == 1) {
                            if($order_item_list[0]['goods_type'] == 4) {
                                if ($v_status['member_operation']) {
                                    foreach ($v_status['member_operation'] as $s_k => $s_v) {
                                        if ($s_v['no'] == 'logistics') {
                                            unset($v_status['member_operation'][$s_k]);
                                        } elseif ($s_v['no'] == 'buy_again') {
                                            unset($v_status['member_operation'][$s_k]);
                                        }
                                    }
                                }
                            }
                        }

                        $order_list['data'][$k]['operation'] = $v_status['operation']?:[];
                        $order_list['data'][$k]['member_operation'] = $v_status['member_operation'];
                        $order_list['data'][$k]['status_name'] = $v_status['status_name'];
                        
                        $order_list['data'][$k]['is_refund'] =  $v_status['is_refund'];
                        $refund_member_operation = $v_status['refund_member_operation'];

                    }
                    if($order_list['data'][$k]['order_type']==9){
                        $refund_member_operation = [];
                    }
                }
                unset($v_status);
                $temp_refund_operation = [];// 将需要整单进行售后的操作保存在operation（卖家操作）里面

                //查询物流
                
                $order_express_info = $goods_express_model::all(['order_id' => $v['order_id']]);
                //获取发货数目和总数目判断是否部分发货
                $express_num = 0;
                foreach ($order_item_list as $key_item => $v_item) {
                    if ($order_express_info) {
                        foreach ($order_express_info as $express_info) {
                            $express_order_goods_id_array = explode(',', $express_info['order_goods_id_array']);
                            if (in_array($v_item['order_goods_id'], $express_order_goods_id_array)) {
                                $order_item_list[$key_item]['express_no'] = $express_info['express_no'];
                                $order_item_list[$key_item]['express_name'] = $express_info['express_name'];
                                $express_num++;
                            }else{
                                $order_item_list[$key_item]['express_no'] = '';
                                $order_item_list[$key_item]['express_name'] = '';
                            }
                        }
                    } else {
                        $order_item_list[$key_item]['express_no'] = '';
                        $order_item_list[$key_item]['express_name'] = '';
                    }
                    // 查询商品sku表开始
                    if($v_item['order_type'] == 10){
                        $goods_model = new VslIntegralGoodsModel();
                        $goods_sku = new VslIntegralGoodsSkuModel();
                    }else{
                        $goods_model = new VslGoodsModel();
                        $goods_sku = new VslGoodsSkuModel();
                    }
                    $goods_sku_info = $goods_sku->getInfo([
                        'sku_id' => $v_item['sku_id']
                    ], 'code');
                    $order_item_list[$key_item]['code'] = $goods_sku_info['code'];
                    $goods_info = $goods_model->getInfo([
                        'goods_id' => $v_item['goods_id']
                    ], 'cost_price,code,item_no');
                    $order_item_list[$key_item]['goods_code'] = $goods_info['code'];
                    $order_item_list[$key_item]['item_no'] = $goods_info['item_no'];
                    $order_item_list[$key_item]['spec'] = [];
                    $order_item_list[$key_item]['real_refund_reason'] = OrderStatus::getRefundReason($v_item['refund_reason']);
                    if ($v_item['sku_attr']) {
                        $order_item_list[$key_item]['spec'] = json_decode(html_entity_decode($v_item['sku_attr']), true);
                    }
                    // 查询商品sku结束

                    $picture = new AlbumPictureModel();
                    // $order_item_list[$key_item]['picture'] = $picture->get($v_item['goods_picture']);
                    $goods_picture = $picture->getInfo(['pic_id' =>$v_item['goods_picture']],'pic_cover,pic_cover_mid,pic_cover_micro');
                    if (empty($goods_picture)) {
                        $goods_picture = array(
                            'pic_cover_micro' => '',
                        );
                    }
                    $order_item_list[$key_item]['picture'] = $goods_picture;

                    $order_item_list[$key_item]['refund_type'] = $v_item['refund_type'];
                    $order_item_list[$key_item]['refund_operation'] = [];
                    $order_item_list[$key_item]['new_refund_operation'] = [];
                    $order_item_list[$key_item]['member_operation'] = [];
                    $order_item_list[$key_item]['status_name'] = '';
                    $temp_member_refund_operation = [];
                    if($v['payment_type']==16 || $v['payment_type']==17 || $v['payment_type_presell']==16 || $v['payment_type_presell']==17){
                        $order_list['data'][$k]['promotion_status'] = 1;
                    }
                    if (!in_array($v['order_type'], [2, 3, 4, 10])) {
                        // 2,3,4微店订单 不参与售后
                        if ($v_item['refund_status'] != 0) {
                            $order_refund_status = OrderStatus::getRefundStatus()[$v_item['refund_status']];
                            if ($v_item['refund_type'] == 1 && $order_refund_status['status_id'] == 1) {
                                //去除处理退货申请
                                unset($order_refund_status['new_refund_operation'][1]);
                            } elseif ($v_item['refund_type'] == 2 && $order_refund_status['status_id'] == 1) {
                                //去除处理退款申请
                                unset($order_refund_status['new_refund_operation'][0]);
                            }
                            $order_item_list[$key_item]['refund_operation'] = $order_refund_status['refund_operation'];
                            if ($order_list['data'][$k]['promotion_status'] == 1) {//活动商品
                                $order_item_list[$key_item]['member_operation'] = [];
                                $temp_member_refund_operation = $order_refund_status['member_operation'];
                            } else {
                                $order_item_list[$key_item]['member_operation'] = $order_refund_status['member_operation'];
                            }
                            $order_item_list[$key_item]['new_refund_operation'] = $temp_refund_operation = array_values($order_refund_status['new_refund_operation']);
                            $order_item_list[$key_item]['status_name'] = $order_refund_status['status_name'];
                        } elseif ($order_list['data'][$k]['promotion_status'] != 1) {//普通商品
                            if ($v_item['invoice_tax'] > 0) {//todo... 税费
                                $order_refund_status = OrderStatus::getRefundStatus()[$v_item['refund_status']];
                                $temp_member_refund_operation = $order_refund_status;
                            }
                            $order_item_list[$key_item]['member_operation'] = $refund_member_operation;
                        }
                    }
                    //知识付费商品去掉申请售后
                    if($v_item['goods_type'] == 4) {
                        $order_item_list[$key_item]['member_operation'] = [];
                    }
                    //优惠
                    $ordergoods_promotion = new VslOrderGoodsPromotionDetailsModel();
                    $ordergoods_promotion_info = $ordergoods_promotion->where(['order_id' => $v['order_id'],'sku_id' => $v_item['sku_id']])->find();
                    $order_item_list[$key_item]['manjian_money'] = $order_item_list[$key_item]['coupon_money'] = '';
                    if ($ordergoods_promotion_info['promotion_type'] == 'MANJIAN' && $fullcut) {
                        $order_item_list[$key_item]['manjian_money'] = $ordergoods_promotion_info['discount_money'];
                    }
                    if ($ordergoods_promotion_info['promotion_type'] == 'COUPON' && $coupontype) {
                        $order_item_list[$key_item]['coupon_money'] = $ordergoods_promotion_info['discount_money'];
                    }
                    
                    //分销信息
                    $order_item_list[$key_item]['commission'] = '';
                    $order_item_list[$key_item]['commissionA'] = '';
                    $order_item_list[$key_item]['commissionB'] = '';
                    $order_item_list[$key_item]['commissionC'] = '';
                    if ($distribution) {
                        if ($this->instance_id) {
                            $order_commission_info = $order_commission->where(['order_id' => $v['order_id'], 'order_goods_id' => $v_item['order_goods_id'], 'shop_id' => $this->instance_id])->find();
                            $order_item_list[$key_item]['commission'] = $order_commission_info['commission'];
                            $order_item_list[$key_item]['commissionA'] = $order_commission_info['commissionA'];
                            $order_item_list[$key_item]['commissionB'] = $order_commission_info['commissionB'];
                            $order_item_list[$key_item]['commissionC'] = $order_commission_info['commissionC'];
                        } else {
                            $order_commission_info = $order_commission->where(['order_id' => $v['order_id'], 'order_goods_id' => $v_item['order_goods_id']])->find();
                            $order_item_list[$key_item]['commission'] = $order_commission_info['commission'];
                            $order_item_list[$key_item]['commissionA'] = $order_commission_info['commissionA'];
                            $order_item_list[$key_item]['commissionB'] = $order_commission_info['commissionB'];
                            $order_item_list[$key_item]['commissionC'] = $order_commission_info['commissionC'];
                        }
                    }
                    
                    //分红信息
                    $order_item_list[$key_item]['bonus'] = 0.00;
                    $order_item_list[$key_item]['bonusA'] = 0;
                    $order_item_list[$key_item]['bonusB'] = 0;
                    $order_item_list[$key_item]['bonusC'] = 0;
                    if ($globalbonus || $teambonus || $areabonus) {
                        $order_item_list[$key_item]['bonusA'] = $order_bonus->getSum(['website_id' => $this->website_id,'order_id' => $v['order_id'], 'order_goods_id' => $v_item['order_goods_id'],'from_type' => 1],'bonus');
                        $order_item_list[$key_item]['bonusB'] = $order_bonus->getSum(['website_id' => $this->website_id,'order_id' => $v['order_id'], 'order_goods_id' => $v_item['order_goods_id'],'from_type' => 2],'bonus');
                        $order_item_list[$key_item]['bonusC'] = $order_bonus->getSum(['website_id' => $this->website_id,'order_id' => $v['order_id'], 'order_goods_id' => $v_item['order_goods_id'],'from_type' => 3],'bonus');
                        $order_item_list[$key_item]['bonus'] = $order_bonus->getSum(['website_id' => $this->website_id,'order_id' => $v['order_id'], 'order_goods_id' => $v_item['order_goods_id'],'from_type' => ['in',[1,2,3]]],'bonus');
                    }
                    
                    //收益信息
                    $order_item_list[$key_item]['profit'] = '';
                    $order_item_list[$key_item]['profitA'] = '';
                    $order_item_list[$key_item]['profitB'] = '';
                    $order_item_list[$key_item]['profitC'] = '';
                    if ($microshop) {
                        $order_profit = new VslOrderMicroShopProfitModel();
                        $order_profit_info = $order_profit->where(['order_id' => $v['order_id'],'order_goods_id' => $v_item['order_goods_id']])->find();
                        $order_item_list[$key_item]['profit'] = $order_profit_info['profit'];
                        $order_item_list[$key_item]['profitA'] = $order_profit_info['profitA'];
                        $order_item_list[$key_item]['profitB'] = $order_profit_info['profitB'];
                        $order_item_list[$key_item]['profitC'] = $order_profit_info['profitC'];
                    }
                }
                unset($v_item);
                $order_list['data'][$k]['all_express'] = ($express_num >= count($order_item_list)) ?: false;
                $order_list['data'][$k]['order_item_list'] = $order_item_list?:[];

                //订单优惠
                $order_list['data'][$k]['order_adjust_money'] = 0;// 订单金额调整    
                foreach ($order_item_list as $order_goods_obj) {
                    $order_list['data'][$k]['order_adjust_money'] += $order_goods_obj['adjust_money'] * $order_goods_obj['num'];
                }
                if(!$v['presell_id']){
                    $order_list['data'][$k]['order_promotion_money'] = $v['goods_money'] + $v['shipping_money'] - $v['promotion_free_shipping'] - ($v['pay_money'] - $v['invoice_tax']) + $order_list['data'][$k]['order_adjust_money'];
                    if($v['deduction_money']>0){
                        $order_list['data'][$k]['order_promotion_money'] = "{$order_list['data'][$k]['order_promotion_money']}" - "{$v['deduction_money']}";
                    }
                }else{
                    $order_list['data'][$k]['order_promotion_money'] = 0;
                }
                
                //查询会员信息
                $user_item_info = $user->getInfo(['uid' => $v['buyer_id']], 'user_tel,nick_name,user_name,uid');
                $order_list['data'][$k]['user_tel'] = $user_item_info['user_tel'];
                $order_list['data'][$k]['buyer_name'] = ($user_item_info['nick_name'])?$user_item_info['nick_name']:($user_item_info['user_name']?$user_item_info['user_name']:($user_item_info['user_tel']?$user_item_info['user_tel']:$user_item_info['uid']));
                
                //查询核销门店信息
                $order_list['data'][$k]['store_name'] = '';
                $order_list['data'][$k]['assistant_name'] = '';
                if ($store) {
                    $storeModel = new VslStoreModel();
                    $store_assistant = new VslStoreAssistantModel();
                    $order_list['data'][$k]['store_name'] = $storeModel->where(['store_id' => $v['store_id']])->value('store_name');
                    $order_list['data'][$k]['assistant_name'] = $store_assistant->where(['assistant_id' => $v['assistant_id']])->value('assistant_name');
                }
                
                //查询满额赠送
                $order_list['data'][$k]['manjian_remark'] = '';
                if ($fullcut) {
                    $order_promotion = new VslOrderPromotionDetailsModel();
                    $manjian_remark = $order_promotion->where(['order_id' => $v['order_id'],'promotion_type'=>'MANJIAN'])->value('remark');
                    if(!empty($manjian_remark['coupon'])){
                        $order_list['data'][$k]['manjian_remark'] .= $manjian_remark['coupon']['coupon_name'];
                    }
                    if(!empty($manjian_remark['gift'])){
                        $order_list['data'][$k]['manjian_remark'] .= $manjian_remark['gift']['gift_name'];
                    }
                    if(!empty($manjian_remark['gift_voucher'])){
                        $order_list['data'][$k]['manjian_remark'] .= $manjian_remark['gift_voucher']['giftvoucher_name'];
                    }
                }
                // 将需要整单进行售后的 售后操作 放到 非售后操作数组内 因为后者的位置就是位于 th = 操作的 那一列
                if ($temp_refund_operation && $order_list['data'][$k]['promotion_status'] == 1) {
                    $order_list['data'][$k]['operation'] = array_merge($order_list['data'][$k]['operation'], $temp_refund_operation);
                    //$order_list['data'][$k]['refund_operation_goods'] = array_column($order_list['data'][$k]['order_item_list'], 'order_goods_id');
                }
                //积分兑换订单是没有售后的
                if (!in_array($v['order_status'], [4, 5]) && $v['order_type'] != 10) {
                    // 已完成，已关闭没有售后
                    if ($order_list['data'][$k]['promotion_status'] == 1) {
                        if (!empty($temp_member_refund_operation)) {
                            $order_list['data'][$k]['member_operation'] = array_merge($order_list['data'][$k]['member_operation'], $temp_member_refund_operation);
                        }
                        $order_list['data'][$k]['member_operation'] = array_merge($order_list['data'][$k]['member_operation'], $refund_member_operation);
                    } else {
                        // 将common里面的售后操作放到订单商品里面
                        foreach ($order_list['data'][$k]['order_item_list'] as &$v_item) {
                            if ($v_item['refund_status'] == 0 && $v_item['goods_type'] != 4) {
                                $v_item['member_operation'] = $refund_member_operation;
                            }
                            if ($v['invoice_type'] > 0) {//todo... 税费
                                $v_item['member_operation'] = [];
                            }
                        }
                        if ($v['invoice_type'] > 0) {//todo... 税费
                            $order_list['data'][$k]['member_operation'] = array_merge($order_list['data'][$k]['member_operation'], $refund_member_operation);
                        }
                        unset($v_item);
                    }
                }
                if($v['shop_after']){
                    $order_list['data'][$k]['operation'] = [];
                }
                if($this->website_id == 4794 || $this->website_id == 1086){
                        $order_list['data'][$k]['user_tel'] = '演示系统手机已加密';
                        $order_list['data'][$k]['receiver_mobile'] = '演示系统手机已加密';
                }
            }
            
        }
        if (isset($order_amount)){
            $order_list['order_amount'] = $order_amount;
        }
        return $order_list;
    }
    
    /**
     * 订单收件人列表，收件人信息（姓名手机）匹配相当于一个收件人
     * @param int $page_index
     * @param int $page_size
     * @param string $condition
     * @param string $order
     * @return array
     */
    public function getOrderReceiverList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $order_model = new VslOrderModel();
        //如果有订单表以外的字段，则先按条件查询其他表的orderid，并取出数据的交集，组装到原有查询条件里
        $query_order_ids = 'uncheck';
        $un_query_order_ids = array();
        $checkOthers = false;

        if ($condition['goods_name']) {
            $checkOthers = true;
            $orderGoodsModel = new VslOrderGoodsModel();
            $order_goods_condition = ['website_id' => $this->website_id];
            if ($condition['goods_name']) {
                $order_goods_condition['goods_name'] = $condition['goods_name'];
            }
            if ($condition['buyer_id']) {
                $order_goods_condition['buyer_id'] = $condition['buyer_id'];
            }
            $orderGoodsList = $orderGoodsModel->pageQuery(1, 0, $order_goods_condition, '', 'order_id');
            unset($condition['goods_name'], $condition['refund_status']);
            $goods_order_ids = array();
            if ($orderGoodsList['data']) {
                foreach ($orderGoodsList['data'] as $keyG => $valG) {
                    $goods_order_ids[] = $valG['order_id'];
                }
                unset($valG);
            }
            if ($query_order_ids != 'uncheck') {
                $query_order_ids = array_intersect($query_order_ids, $goods_order_ids);
            } else {
                $query_order_ids = $goods_order_ids;
            }
        }
        if ($condition['vgsr_status']) {
            $isGroup = true;
            $checkOthers = true;
            $vgsr_status = $condition['vgsr_status'];
            $group_server = new GroupShopping();
            $unGroupOrderIds = $group_server->getPayedUnGroupOrder($this->instance_id,$this->website_id);

            if ($vgsr_status == 1) {
                if ($query_order_ids != 'uncheck') {
                    $query_order_ids = array_intersect($query_order_ids, $unGroupOrderIds);
                } else {
                    $query_order_ids = $unGroupOrderIds;
                }
            }
            if ($vgsr_status == 2 && $unGroupOrderIds) {
                $un_query_order_ids = $unGroupOrderIds;
            }
            unset($condition['vgsr_status']);
        }
        if ($checkOthers) {
            if ($query_order_ids != 'uncheck') {
                $condition['order_id'] = ['in', implode(',', $query_order_ids)];
            } elseif ($un_query_order_ids) {
                $condition['order_id'] = ['not in', implode(',', $un_query_order_ids)];
            }
        }
        // 查询主表
        $order_list = $order_model->pageQuery($page_index, $page_size, $condition, $order, $field);
        $province_model = new ProvinceModel();
        $city_model = new CityModel();
        $district_model = new DistrictModel();

        $return_list = [];
        $key_list = [];
        if (!empty($order_list['data'])) {
            foreach ($order_list['data'] as $k => $v) {
                $key = md5($v['receiver_name'] . $v['receiver_mobile'] . $v['receiver_province'] . $v['receiver_city'] . $v['receiver_district'] . $v['receiver_address'] . $v['receiver_zip']);

                if (!in_array($key, $key_list)) {
                    $return_list[$key]['receiver_name'] = $v['receiver_name'];
                    $return_list[$key]['receiver_mobile'] = $v['receiver_mobile'];
                    $return_list[$key]['receiver_province_id'] = $v['receiver_province'];
//                    $return_list[$key]['receiver_province_name'] = $province_model::get($v['receiver_province'])['province_name'];
                    $return_list[$key]['receiver_city_id'] = $v['receiver_city'];
//                    $return_list[$key]['receiver_city_name'] = $city_model::get($v['receiver_city'])['city_name'];
                    $return_list[$key]['receiver_district_id'] = $v['receiver_district'];
//                    $return_list[$key]['receiver_district_name'] = $district_model::get($v['receiver_district'])['district_name'];
                    $return_list[$key]['receiver_address'] = $v['receiver_address'];
                    $return_list[$key]['receiver_zip_code'] = $v['receiver_zip'];
                    $key_list[] = $key;
                }

                $return_list[$key]['order_id_array'][] = $v['order_id'];
                $return_list[$key]['order_num']++;
                $return_list[$key]['order_amount'] += $v['order_money'];
            }
        }
        return $return_list;
    }

    public function printOrderList(array $condition = [],array $with = [])
    {
        $order_goods_model = new VslOrderGoodsModel();
        $province_model = new ProvinceModel();
        $city_model = new CityModel();
        $district_model = new DistrictModel();
        $goods_model = new VslGoodsModel();
        $order_goods_list = $order_goods_model::all($condition, $with);

        $return_data = [];
        foreach ($order_goods_list as $v){
            $return_data[$v->order_id]['order_id'] = $v->order_id;
            $return_data[$v->order_id]['order_no'] = $v->order->order_no;
            $return_data[$v->order_id]['receiver_name'] = $v->order->receiver_name;
            $return_data[$v->order_id]['receiver_mobile'] = $v->order->receiver_mobile;
            $return_data[$v->order_id]['receiver_province_id'] = $v->order->receiver_province;
            $return_data[$v->order_id]['receiver_province_name'] = $province_model::get($return_data[$v->order_id]['receiver_province_id'])['province_name'];
            $return_data[$v->order_id]['receiver_city_id'] = $v->order->receiver_city;
            $return_data[$v->order_id]['receiver_city_name'] = $city_model::get($return_data[$v->order_id]['receiver_city_id'])['city_name'];
            $return_data[$v->order_id]['receiver_district_id'] = $v->order->receiver_district;
            $return_data[$v->order_id]['receiver_district_name'] = $district_model::get($return_data[$v->order_id]['receiver_district_id'])['district_name'];
            $return_data[$v->order_id]['receiver_zip_code'] = $v->order->receiver_zip;
            $return_data[$v->order_id]['receiver_address'] = $v->order->receiver_address;

            $return_data[$v->order_id]['goods_list'][$v['order_goods_id']]['order_goods_id'] = $v['order_goods_id'];
            $return_data[$v->order_id]['goods_list'][$v['order_goods_id']]['goods_name'] = $v['goods_name'];
            $return_data[$v->order_id]['goods_list'][$v['order_goods_id']]['goods_id'] = $v['goods_id'];
            $return_data[$v->order_id]['goods_list'][$v['order_goods_id']]['sku_id'] = $v['sku_id'];
            $return_data[$v->order_id]['goods_list'][$v['order_goods_id']]['short_name'] = $goods_model::get($v['goods_id'])['short_name'];
            $return_data[$v->order_id]['goods_list'][$v['order_goods_id']]['price'] = $v['price'];
            $return_data[$v->order_id]['goods_list'][$v['order_goods_id']]['num'] = $v['num'];
        }

        return $return_data;
    }

    public function deliveryOrderList(array $condition = [],array $with = [])
    {
        $order_goods_model = new VslOrderGoodsModel();
        $order_goods_express_model = new VslOrderGoodsExpressModel();
        $order_goods_list = $order_goods_model::all($condition, $with);
        $return_data = [];
        $order_id_array = [];
        $i = 1;
        foreach ($order_goods_list as $v){
            $return_data[$v->order_id]['i'] = $i++;
            $return_data[$v->order_id]['order_id'] = $v->order_id;
            $return_data[$v->order_id]['order_no'] = $v->order->order_no;
            $return_data[$v->order_id]['order_goods_id_array'][] = $v->order_goods_id;
            $return_data[$v->order_id]['order_status'] = $v->order->order_status;
            $return_data[$v->order_id]['order_status_name'] = OrderStatus::getOrderCommonStatus()[$v->order->order_status]['status_name'];

            if (!in_array($v->order_id,$order_id_array)){
                $order_id_array[] = $v->order_id;
            }
        }

        $order_goods_express = $order_goods_express_model::all(['order_id' => ['IN', $order_id_array]]);
        foreach ($order_goods_express as $e) {
            $temp_order_goods_id_array = explode(',', $e->order_goods_id_array);
            $return_data[$e->order_id]['company_name'] = $e->express_name;
            $return_data[$e->order_id]['express_no'] = $e->express_no;
            foreach ($return_data[$e->order_id]['order_goods_id_array'] as $k => $order_goods_id) {
                if (in_array($order_goods_id, $temp_order_goods_id_array)) {
                    // 删除已发货的订单商品
                    unset($return_data[$e->order_id]['order_goods_id_array'][$k]);
                }
            }
        }
        return $return_data;
    }

    public function addPrintTimes($type, $order_goods_id_array)
    {
        try {
            Db::startTrans();
            if ($type == 'express') {
                $order_goods_field = 'express_print_num';
                $order_field = 'express_order_status';
            } else {
                $order_goods_field = 'delivery_print_num';
                $order_field = 'delivery_order_status';
            }
            $order_goods_model = new VslOrderGoodsModel();
            $order_model = new VslOrderModel();
            $order_goods_model->where(['order_goods_id' => ['IN', $order_goods_id_array]])->setInc($order_goods_field);
            $order_goods_list = $order_goods_model::all(['order_goods_id' => ['IN', $order_goods_id_array]]);
            foreach ($order_goods_list as $v) {
                if ($v['order'][$order_field] == 3) {
                    // 全部已打印
                    continue;
                }
                if ($v['order'][$order_field] == 1) {
                    // 未打印
                    if ($order_goods_model->where(['order_id' => $v['order_id'], $order_goods_field => ['EQ', 0]])->count() > 0) {
                        // 部分打印
                        $order_model->save([$order_field => 2], ['order_id' => $v['order_id']]);
                    } else {
                        // 全部打印
                        $order_model->save([$order_field => 3], ['order_id' => $v['order_id']]);
                    }
                }
            }
            Db::commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            return UPDATA_FAIL;
        }
    }

    public function checkBeforeOrderCreate(array $order_data)
    {
        $now_time = time();
        $man_song_rule_model = getAddons('fullcut', $this->website_id) ? new VslPromotionMansongRuleModel() : '';
        $sku_model = new VslGoodsSkuModel();
        $coupon_model = getAddons('coupontype', $this->website_id) ? new VslCouponModel() : '';
        $promotion_discount_model = getAddons('discount', $this->website_id) ? new VslPromotionDiscountModel() : '';
        $sec_service = getAddons('seckill', $this->website_id, $this->instance_id) ? new seckillServer() : '';
        $storeServer = getAddons('store', $this->website_id, $this->instance_id) ? new Store() : '';
        $user_model = new UserModel();
        $user_info = $user_model::get($this->uid, ['member_info.level', 'member_account']);
        if ($order_data['pay_type'] == 5 && $user_info->member_account->balance < $order_data['total_pay_amount']) {
            return ['result' => false, 'message' => '账号余额不足'];
        }
        if (empty($order_data['address_id']) && $order_data['shipping_type']==1) {
            return ['result' => false, 'message' => '缺少收货地址'];
        }
        $web_config = new Config();
        $bpay = $web_config->getConfig(0, 'BPAY')['is_use'];
        if ($order_data['pay_type'] == 5 && $bpay != 1) {
            return ['result' => false, 'message' => '没有开启余额支付'];
        }
        $member_discount = $user_info->member_info->level->goods_discount ? ($user_info->member_info->level->goods_discount / 10) : 1;
        $member_is_label = $user_info->member_info->level->is_label;
        foreach ($order_data['order'] as $shop_id => $order_sku) {
            if (empty($order_sku['sku'])) {
                return ['result' => false, 'message' => '没有商品信息'];
            }
            $has_store = getAddons('store', $this->website_id, $this->instance_id) ? $storeServer->getStoreSet($shop_id)['is_use'] : 0;
            if($has_store && empty($order_sku['store_id']) && $order_data['address_id']){
                $has_store = 0;
            }
            if ($order_data['shipping_type'] == 2 && $has_store && !$order_data['shop'][$shop_id]['store_id'] && !$order_data['shop'][$shop_id]['card_store_id']) {
                return ['result' => false, 'message' => '没有选择门店'];
            }
            if (empty($order_data['address_id']) && $order_data['shipping_type']==2 && !$has_store) {
                return ['result' => false, 'message' => '缺少收货地址'];
            }
            foreach ($order_sku['sku'] as $sku_id => $sku_info) {
                $sku_id = $sku_info['sku_id'];
                if(getAddons('seckill', $this->website_id, $this->instance_id)){
                    $seckill_id = $sku_info['seckill_id'];
                    $condition_is_seckill['s.seckill_id'] = $seckill_id;
                    $condition_is_seckill['nsg.sku_id'] = $sku_id;
                    $is_seckill = $sec_service->isSeckillGoods($condition_is_seckill);
                    //获取秒杀商品的价格、库存、最大购买量
                    $condition_sku_info['seckill_id'] = $seckill_id;
                    $condition_sku_info['sku_id'] = $sku_id;
                    $sku_info_list = $sec_service->getSeckillSkuInfo($condition_sku_info);
                    $sku_info_arr = objToArr($sku_info_list);
                }
                if($sku_info['store_id']){
                    $store_goods_sku_model = new VslStoreGoodsSkuModel();
                    $sku_info_db = $store_goods_sku_model::get(['sku_id' => $sku_id,'store_id' => $sku_info['store_id']], ['goods']);
                }else{
                $sku_info_db = $sku_model::get($sku_id, ['goods']);
                }
                $goods_name = $sku_info_db->goods->goods_name;
                //秒杀商品入口
                if ($seckill_id && getAddons('seckill', $this->website_id, $this->instance_id)) {
                    if ($is_seckill) {
                        if ($sku_info['num'] > $sku_info_arr['remain_num']) {
                            return ['result' => false, 'message' => $goods_name . ' 购买数目超出秒杀库存', 'operation' => 'refresh'];
                        }
                        $goods_service = new Goods();
                        //通过用户累计购买量判断，先判断redis是否有内容
                        $uid = $this->uid;
                        $website_id = $this->website_id;
                        $buy_num = $goods_service->getActivityOrderSku($uid, $sku_id, $website_id, $seckill_id);
                        if ($sku_info_arr['seckill_limit_buy'] != 0 && ($sku_info['num'] + $buy_num > $sku_info_arr['seckill_limit_buy'])) {
                            return ['result' => false, 'message' => $goods_name . ' 该商品规格您总共购买数目超出最大秒杀限购数目', 'operation' => 'refresh'];
                        }
                        if ($sku_info['price'] != $sku_info_arr['seckill_price']) {
                            return ['result' => false, 'message' => $goods_name . ' 商品价格变动', 'operation' => 'refresh'];
                        }
                    }
                }
                else {
                    //sku 信息检查
                    if ($sku_info_db->goods->state != 1) {
                        return ['result' => false, 'message' => $goods_name . ' 物品为不可购买状态'];
                    }
                    if ($sku_info['num'] > $sku_info_db->stock) {
                        return ['result' => false, 'message' => $goods_name . ' 购买数目超出库存'];
                    }
                    if (($sku_info['num'] > $sku_info_db->goods->max_buy) && $sku_info_db->goods->max_buy != 0) {
                        return ['result' => false, 'message' => $goods_name . ' 购买数目超出最大购买数目'];
                    }
                    if ($sku_info['price'] != $sku_info_db->price) {
                        return ['result' => false, 'message' => $goods_name . ' 商品价格变动', 'operation' => 'refresh'];
                    }
                    // todo... by sgw 修改规格商品价格为会员折扣价格
                    if ($this->uid) {
                        // 查询商品是否有开启会员折扣
                        $goods_server = new GoodsService();
                        $goodsDiscountInfo = $goods_server->getGoodsInfoOfIndependentDiscount($sku_info_db['goods_id'], $sku_info_db['price']);
                        if ($goodsDiscountInfo) {
                            $member_discount = $goodsDiscountInfo['member_discount'];
                            $member_is_label = $goodsDiscountInfo['member_is_label'];
                            if ($member_discount == 1) {
                                $sku_info['price'] = $goodsDiscountInfo['member_price'];//固定价格
                            }
                        }
                    }
                    //折扣价格检查 包括 会员折扣 限时折扣
                    if (!empty($sku_info['discount_id'])) {
                        if (!getAddons('discount', $this->website_id)) {
                            return ['result' => false, 'message' => '限时折扣应用已关闭', 'operation' => 'refresh'];
                        }
                        $promotion_discount_info_db = $promotion_discount_model::get($sku_info['discount_id'], ['goods']);
                        if ($promotion_discount_info_db->status != 1) {
                            return ['result' => false, 'message' => $goods_name . ' 限时折扣状态不可用', 'operation' => 'refresh'];
                        }
                        if ($promotion_discount_info_db->start_time > $now_time || $promotion_discount_info_db->end_time < $now_time) {
                            return ['result' => false, 'message' => '限时折扣不在可用时间内', 'operation' => 'refresh'];
                        }
                        if (($promotion_discount_info_db->range_type == 1 || $promotion_discount_info_db->range_type == 3) &&
                            ($promotion_discount_info_db->shop_id != $shop_id)) {
                            return ['result' => false, 'message' => $goods_name . ' 限时折扣不在可用范围内', 'operation' => 'refresh'];
                        }
                        if ($promotion_discount_info_db->range == 2) {
                            if ($promotion_discount_info_db->goods()->where(['goods_id' => ['=', $sku_info['goods_id']]])->count() == 0) {
                                return ['result' => false, 'message' => $goods_name . ' 商品不在限时折扣指定商品范围内'];
                            }
                        }
                        // 限时折扣主表的折扣
                        if($member_is_label){//开启会员折扣取整
                            $member_discount_price = round($member_discount * $sku_info['price']);
                        }else{
                            $member_discount_price = round($member_discount * $sku_info['price'],2);
                        }
                        $discount_price_1 = round(($promotion_discount_info_db->discount_num / 10) * $member_discount_price, 2);
                        // 限时折扣商品表的折扣
                        $goods_discount = $promotion_discount_info_db->goods()->where(['goods_id' => $sku_info['goods_id']])->find();
                        if ($goods_discount) {
                            $promotion_discount = $promotion_discount_model->where(['discount_id' => $goods_discount['goods_id']])->find();

                            if($promotion_discount['integer_type'] == 1){
                                $discount_price_2 = round($goods_discount['discount'] / 10 * $member_discount_price);
                            }else{
                                $discount_price_2 = round($goods_discount['discount'] / 10 * $member_discount_price, 2);
                            }
                            if($goods_discount['discount_type'] == 2){
                                $discount_price_2 = $goods_discount['discount'];
                            }
                        }
                        if ($sku_info['discount_price'] != $discount_price_1 && $sku_info['discount_price'] != $discount_price_2) {
                            return ['result' => false, 'message' => $goods_name . ' 商品折扣价格变化', 'operation' => 'refresh'];
                        }
                    } else {
                        if($member_is_label){//开启会员折扣取整
                            $discount_price = round($member_discount * $sku_info['price']);
                        }else{
                            $discount_price = round($member_discount * $sku_info['price'],2);
                        }
                        $sku_info['discount_price'] = round($sku_info['discount_price'],2);
                        if ($sku_info['discount_price'] != $discount_price) {
                            return ['result' => false, 'message' => $goods_name . ' 商品折扣价格变化', 'operation' => 'refresh'];
                        }
                    }
                }
            }
            //满减送信息
            if (!empty($order_data['promotion'][$shop_id]['man_song'])) {
                if (!getAddons('fullcut', $this->website_id)) {
                    return ['result' => false, 'message' => '满减送应用已关闭', 'operation' => 'refresh'];
                }
                foreach ($order_data['promotion'][$shop_id]['man_song'] as $man_song_rule_id => $man_song_info) {
                    $rule_db_info = $man_song_rule_model::get($man_song_rule_id, ['promotion_man_song.goods']);
                    if (!empty($man_song_info['full_cut']) || !empty($man_song_info['shipping'])) {
                        if ($rule_db_info->promotion_man_song->status != 1) {
                            return ['result' => false, 'message' => '满减送活动状态不可用', 'operation' => 'refresh'];
                        }
                        if ($rule_db_info->promotion_man_song->start_time > $now_time || $rule_db_info->promotion_man_song->end_time < $now_time) {
                            return ['result' => false, 'message' => '满减送活动不在可用时间内', 'operation' => 'refresh'];
                        }
                        if ($rule_db_info->promotion_man_song->range == 1 && $rule_db_info->promotion_man_song->shop_id != $shop_id) {
                            return ['result' => false, 'message' => '满减送活动不在可用店铺范围内', 'operation' => 'refresh'];
                        }
                        $man_song_compare_amount = 0.00;
                        if ($rule_db_info->promotion_man_song->range_type == 0) {
                            if ($rule_db_info->promotion_man_song->goods()->where(['goods_id' => ['IN', $order_data['shop'][$shop_id]['goods_id_array']]])->count() == 0) {
                                return ['result' => false, 'message' => '满减送活动指定可用商品变化', 'operation' => 'refresh'];
                            }
                            foreach ($order_sku['sku'] as $sku_id => $sku_info) {
                                if (in_array($sku_info['goods_id'], $man_song_info['full_cut']['goods_limit'])) {
                                    $man_song_compare_amount += $sku_info['discount_price'] * $sku_info['num'];
                                }
                            }
                        } else {
                            $man_song_compare_amount = $order_data['shop'][$shop_id]['discount_amount'];
                        }
                        if ($rule_db_info->price > $man_song_compare_amount) {
                            return ['result' => false, 'message' => '满减送活动达不到金额要求', 'operation' => 'refresh'];
                        }
                        if (!empty($man_song_info['full_cut']['discount']) && $rule_db_info->discount != $man_song_info['full_cut']['discount']) {
                            return ['result' => false, 'message' => '满减送活动优惠金额变化', 'operation' => 'refresh'];
                        }
                        if (!empty($man_song_info['shipping']['free_shipping_fee']) && $man_song_info['shipping']['free_shipping_fee'] && $rule_db_info->free_shipping != 1) {
                            return ['result' => false, 'message' => '满减送活动包邮活动变化', 'operation' => 'refresh'];
                        }
                    }
                }
            }

            //优惠券信息
            if (!empty($order_data['promotion'][$shop_id]['coupon'])) {
                if (!getAddons('coupontype', $this->website_id)) {
                    return ['result' => false, 'message' => '优惠券应用已关闭', 'operation' => 'refresh'];
                }
                $coupon_info_db = $coupon_model::get($order_data['promotion'][$shop_id]['coupon']['coupon_id'], ['coupon_type.goods']);
                if ($coupon_info_db->state != 1) {
                    return ['result' => false, 'message' => '优惠券状态不可用', 'operation' => 'refresh'];
                }
                if ($coupon_info_db->coupon_type->start_time > $now_time || $coupon_info_db->coupon_type->end_time < $now_time) {
                    return ['result' => false, 'message' => '优惠券不在可用时间内', 'operation' => 'refresh'];
                }
                if ($coupon_info_db->coupon_type->shop_range_type == 1 && $coupon_info_db->coupon_type->shop_id != $shop_id) {
                    return ['result' => false, 'message' => '优惠券不在可用店铺范围内', 'operation' => 'refresh'];
                }
                if ($coupon_info_db->uid != $this->uid) {
                    return ['result' => false, 'message' => '优惠券持有者错误', 'operation' => 'refresh'];
                }
                if ($order_data['promotion'][$shop_id]['coupon']['coupon_genre'] == 1 || $order_data['promotion'][$shop_id]['coupon']['coupon_genre'] == 2) {
                    if ($order_data['promotion'][$shop_id]['coupon']['money'] != $coupon_info_db->coupon_type->money) {
                        return ['result' => false, 'message' => '优惠券优惠金额变化', 'operation' => 'refresh'];
                    }
                } elseif ($order_data['promotion'][$shop_id]['coupon']['coupon_genre'] == 3) {
                    if ($order_data['promotion'][$shop_id]['coupon']['discount'] != $coupon_info_db->coupon_type->discount) {
                        return ['result' => false, 'message' => '优惠券折扣变化', 'operation' => 'refresh'];
                    }
                } else {
                    return ['result' => false, 'message' => '优惠券类型不存在'];
                }
                $coupon_compare_amount = 0.00;
                if ($coupon_info_db->coupon_type->range_type == 0) {
                    if ($coupon_info_db->coupon_type->goods()->where(['goods_id' => ['IN', $order_data['shop'][$shop_id]['goods_id_array']]])->count() == 0) {
                        return ['result' => false, 'message' => '优惠券活动指定可用商品变化', 'operation' => 'refresh'];
                    }
                    foreach ($order_sku['sku'] as $sku_id => $sku_info) {
                        if (in_array($sku_info['goods_id'], $order_data['promotion'][$shop_id]['coupon']['goods_limit'])) {
                            $coupon_compare_amount += $sku_info['discount_price'] * $sku_info['num'];
                        }
                        if ($sku_info['full_cut_sku_percent'] > 0 && $sku_info['full_cut_sku_amount'] > 0) {
                            $coupon_compare_amount -= $sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'];
                        }
                    }
                } else {
                    foreach ($order_sku['sku'] as $sku_id => $sku_info) {
                        $coupon_compare_amount += $sku_info['discount_price'] * $sku_info['num'];
                        if ($sku_info['full_cut_sku_percent'] > 0 && $sku_info['full_cut_sku_amount'] > 0) {
                            $coupon_compare_amount -= $sku_info['full_cut_sku_percent'] * $sku_info['full_cut_sku_amount'];
                        }
                    }
                }
                if ($coupon_info_db->coupon_type->at_least > $coupon_compare_amount) {
                    return ['result' => false, 'message' => '优惠券达不到金额要求', 'operation' => 'refresh'];
                }
            }
        }

        return ['result' => true, 'message' => 'ok'];
    }
    /**
     * 计算移动端/app 提交创建订单 获取会员折扣 限时折扣 分销等级折扣
     * @param int $sku_id 规格项id
     */
    public function getDiscountPrice($sku_id){
        //规格项id 换取当前商品规格的售价 ，已经折扣设置等 
        $user_model = new UserModel();
        $sku_model = new VslGoodsSkuModel();
        $sku_db_info = $sku_model::get($sku_id, ['goods']);
        $price = $sku_db_info->goods->price;
        // 是否开启会员折扣 会员折扣顺序: 独立分销等级折扣>独立会员等级折扣>会员折扣
        $is_member_discount = $sku_db_info->goods->is_member_discount;
        
        $goods_id = $sku_db_info->goods->goods_id;

        // 查询商品是否有开启会员折扣
        $GoodsService = new GoodsService();
        $goodsPower = $GoodsService->getGoodsPowerDiscount($goods_id);
        $discountVal = json_decode($goodsPower['value'],true);

        $is_user_obj_open = $discountVal['is_user_obj_open']; //会员折扣是否开启 
        $is_distributor_obj_open = $discountVal['is_distributor_obj_open']; //分销这块是否开启
        // $distributor_independent = $sku_db_info->goods->distributor_independent;
        $user_info = $user_model::get($this->uid, ['member_info.level', 'member_account', 'member_address']);
        
        if($is_member_discount == 0){
            $member_discount = $user_info->member_info->level->goods_discount ? ($user_info->member_info->level->goods_discount / 10) : 1; //会员折扣
            $member_is_label = $user_info->member_info->level->is_label;
            //会员折扣金额 
            if($member_is_label){
                $member_price = round($member_discount * $price);
            }else{
                $member_price = round($member_discount * $price,2);
            }

            //独立会员折扣
            if($is_user_obj_open == 1 && $discountVal['user_obj']['u_discount_choice'] == 1){ //折扣
                //获取当前会员等级
                $member_level = $user_info->member_info->member_level;
                $member_discount = '';
                foreach ($discountVal['user_obj']['u_level_data'] as $key => $value) {
                    if($key == $member_level){
                        $member_discount = $value['val'];
                    }
                }
                if($discountVal['user_obj']['u_is_label']){
                    $member_price = empty($member_discount) ? $price : round($member_discount * $price);
                }else{
                    $member_price = empty($member_discount) ? $price : round($member_discount * $price,2);
                }

            }else if($is_user_obj_open == 1 && $discountVal['user_obj']['u_discount_choice'] == 2){//固定金额
                //获取当前会员等级
                $member_level = $user_info->member_info->member_level;
                $member_price = $price;
                foreach ($discountVal['user_obj']['u_level_data'] as $key => $value) {
                    if($key == $member_level){
                        $member_price = $value['val'];
                    }
                }
            }
            //独立分销折扣
            if($is_distributor_obj_open == 1 && $discountVal['distributor_obj']['d_discount_choice'] == 1){ //折扣
                //获取会员分销折扣
                $distributor_level_id = $user_info->member_info->distributor_level_id;
                $isdistributor = $user_info->member_info->isdistributor;
                if($isdistributor == 2){
                    $member_discount = '';
                    foreach ($discountVal['distributor_obj']['d_level_data'] as $key => $value) {
                        if($key == $distributor_level_id){
                            $member_discount = $value['val'];
                        }
                    }
                    if($discountVal['distributor_obj']['d_is_label']){
                        $member_price = empty($member_discount) ? $price : round($member_discount * $price);
                    }else{
                        $member_price = empty($member_discount) ? $price : round($member_discount * $price,2);
                    }
                }
            }else if($is_distributor_obj_open == 1 && $discountVal['distributor_obj']['d_discount_choice'] == 2){//固定金额
                //获取当前会员等级
                $distributor_level_id = $user_info->member_info->distributor_level_id;
                $isdistributor = $user_info->member_info->isdistributor;
                if($isdistributor == 2){
                    foreach ($discountVal['distributor_obj']['d_level_data'] as $key => $value) {
                        if($key == $member_level){
                            $member_price = $value['val'];
                        }
                    }
                }
                
            }
        }
        return $member_price;
        //是否开启独立会员等级折扣

        //是否开启独立分销等级折扣
        
    }
    /**
     * 重组创建订单所需数组
     */
    public function calculateCreateOrderDataTesy(array $order_data)
    {
        $now_time = time();
        $man_song_rule_model = getAddons('fullcut', $this->website_id) ? new VslPromotionMansongRuleModel() : '';
        $coupon_model = getAddons('coupontype', $this->website_id) ? new VslCouponModel() : '';
        $promotion_discount_model = new VslPromotionDiscountModel();
        $user_model = new UserModel();
        $sec_service = getAddons('seckill', $this->website_id, $this->instance_id) ? new seckillServer() : '';
        $goodsExpress = new GoodsExpress();
        $group_server = getAddons('groupshopping', $this->website_id, $this->instance_id) ? new GroupShopping() : '';
        $storeServer = getAddons('store', $this->website_id, $this->instance_id) ? new Store() : '';
        $user_info = $user_model::get($this->uid, ['member_info.level', 'member_account', 'member_address']);
        $return_data['address_id'] = $order_data['address_id'];
        $return_data['group_id'] = $order_data['group_id'];
        $return_data['record_id'] = $order_data['record_id'];
        $return_data['shipping_type'] = $order_data['shipping_type'];
        $return_data['is_deduction'] = $order_data['is_deduction'];
       
        foreach ($order_data['shop_list'] as $shop) {
        //判断后台配置的是哪种库存方式 1:门店独立库存 2:店铺统一库存  默认为1
        $storeServer = new storeServer();
            $stock_type = $storeServer->getStoreSet($shop['shop_id'])['stock_type'] ? $storeServer->getStoreSet($shop['shop_id'])['stock_type'] : 1;

            $shop_id = $shop['shop_id'];
            $return_data['shop'][$shop_id]['store_id'] = $shop['store_id'] ?: 0;
            $return_data['shop'][$shop_id]['card_store_id'] = (empty($shop['card_store_id']))?0:$shop['card_store_id'];
            $return_data['shop'][$shop_id]['shop_channel_amount'] = 0;
            $return_data['shop'][$shop_id]['leave_message'] = $shop['leave_message'] ?: '';
            
            //处理店铺商品
            foreach ($shop['goods_list'] as $k => $sku_info) {
                //循环 处理单商品信息
                if($shop['card_store_id'] && $stock_type == 1){
                    //计时计次商品
                    $sku_model = new VslStoreGoodsSkuModel();
                    $sku_db_info = $sku_model::get(['sku_id' => $sku_info['sku_id'],'store_id'=>$shop['card_store_id']], ['goods']);
                }elseif ($shop['store_id'] && $stock_type == 1) {
                    //线下自提
                    $sku_model = new VslStoreGoodsSkuModel();
                    $sku_db_info = $sku_model::get(['sku_id' => $sku_info['sku_id'],'store_id'=>$shop['store_id']], ['goods']);
                }else{
                    $sku_model = new VslGoodsSkuModel();
                $sku_db_info = $sku_model::get($sku_info['sku_id'], ['goods']);
                }
                
                $temp_sku_id = $sku_info['sku_id'];
                $return_data['order'][$shop_id]['sku'][$k]['sku_id'] = $temp_sku_id;
                $return_data['order'][$shop_id]['sku'][$k]['goods_id'] = $sku_info['goods_id'];
                $return_data['order'][$shop_id]['sku'][$k]['channel_id'] = $sku_info['channel_id'];
                $return_data['order'][$shop_id]['sku'][$k]['seckill_id'] = $sku_info['seckill_id'];
                $return_data['order'][$shop_id]['sku'][$k]['seckill_id'] = $sku_info['seckill_id'];
                if($sku_info['presell_id'] && getAddons('presell', $this->website_id, $this->instance_id)){
                    //判断预售是否关闭或者过期
                    $presell_id = $sku_info['presell_id'];
                    //如果是预售的商品，则更改其单价为预售价
                    $presell_mdl = new VslPresellModel();
                    $presell_condition['p.id'] = $presell_id;
                    $presell_condition['p.start_time'] = ['<', time()];
                    $presell_condition['p.end_time'] = ['>=', time()];
                    $presell_condition['p.status'] = ['neq', 3];
                    $presell_condition['pg.sku_id'] = $sku_info['sku_id'];
                    $presell_goods_info = $presell_mdl->alias('p')->where($presell_condition)->join('vsl_presell_goods pg', 'p.id = pg.presell_id', 'LEFT')->find();
                    if(!$presell_goods_info){
                        return ['result' => -2, 'message' => '预售活动已过期或已关闭'];
                    }
                    $return_data['order'][$shop_id]['sku'][$k]['presell_id'] = $sku_info['presell_id'];
                }else{
                    $return_data['order'][$shop_id]['sku'][$k]['presell_id'] = 0;
                }

                $return_data['order'][$shop_id]['sku'][$k]['bargain_id'] = $sku_info['bargain_id'];
                $return_data['order'][$shop_id]['sku'][$k]['price'] = $sku_info['price'];
                //会员价
                $return_data['order'][$shop_id]['sku'][$k]['member_price'] = $sku_info['member_price'];

                $return_data['order'][$shop_id]['sku'][$k]['discount_id'] = $sku_info['discount_id'];
                $return_data['order'][$shop_id]['sku'][$k]['discount_price'] = $sku_info['discount_price'];
                $return_data['order'][$shop_id]['sku'][$k]['num'] = $sku_info['num'];
                $return_data['order'][$shop_id]['sku'][$k]['shop_id'] = $sku_db_info->goods->shop_id;
                $return_data['order'][$shop_id]['sku'][$k]['point_deduction_max'] = $sku_db_info->goods->point_deduction_max;
                $return_data['order'][$shop_id]['sku'][$k]['point_return_max'] = $sku_db_info->goods->point_return_max;
                
                $return_data['shop'][$shop_id]['goods_id_array'][] = $sku_info['goods_id'];
                $return_data['shop'][$shop_id]['member_amount'] += $return_data['order'][$shop_id]['sku'][$k]['member_price'] * $sku_info['num'];
                $return_data['shop'][$shop_id]['shop_total_amount'] += $sku_info['price'] * $sku_info['num'];
                $return_data['shop'][$shop_id]['shop_discount_amount'] += $sku_info['discount_price'] ? ($sku_info['discount_price'] * $sku_info['num']) : 0;

                if (getAddons('groupshopping', $this->website_id, $this->instance_id)) {
                    $is_group = $group_server->isGroupGoods($sku_info['goods_id']);//是否团购商品
                    $group_sku_info_obj = $group_server->getGroupSkuInfo(['sku_id' => $sku_info['sku_id'], 'goods_id' => $sku_info['goods_id'], 'group_id' => $is_group]);
                    $group_sku_info_arr = objToArr($group_sku_info_obj);//商品团购信息
                }
                //快递配送或者无需物流，如果上级渠道商有库存，则优先扣上级渠道商的库存
                if($return_data['shipping_type'] == 1 || empty($return_data['shipping_type'])) {
                if (!empty($sku_info['channel_id']) && getAddons('channel', $this->website_id) ) {
                    $website_id = $this->website_id;
                    $channel_gs_mdl = new VslChannelGoodsSkuModel();
                    $channel_gs_info = $channel_gs_mdl->getInfo(['website_id' => $website_id, 'channel_id' => $sku_info['channel_id'], 'sku_id' => $sku_info['sku_id']], 'stock');
                        //判断的是当前购买量有没有超过平台库存+上级渠道商库存
                        if ($sku_info['num'] > $channel_gs_info['stock'] + $sku_db_info->stock) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . '商品库存不足'];
                        }else{
                            $cut_channel_stock = 0;
                            $sku_db_info->stock = $channel_gs_info['stock'] + $sku_db_info->stock;
                            //判断渠道商的库存够不够扣此次的购买量，不够就扣平台的
                            if($sku_info['num'] - $channel_gs_info['stock'] <= 0) {
                                //全部扣渠道商的库存
                                $cut_channel_stock = $sku_info['num'];
                            }elseif ($sku_info['num'] - $channel_gs_info['stock'] > 0) {
                                //部分扣渠道商的
                                $cut_channel_stock = $channel_gs_info['stock'];
                            }
                            $return_data['order'][$shop_id]['sku'][$k]['channel_stock'] =  $cut_channel_stock;
                        }
                    }
                }
                if (!empty($sku_info['seckill_id']) && getAddons('seckill', $this->website_id, $this->instance_id)) {
                    
                    //判断秒杀商品是否过期
                    $sku_id = $sku_info['sku_id'];
                    $seckill_id = $sku_info['seckill_id'];
                    $condition_is_seckill['s.seckill_id'] = $seckill_id;
                    $condition_is_seckill['nsg.sku_id'] = $sku_id;
                    $is_seckill = $sec_service->isSeckillGoods($condition_is_seckill);
                    
                    if ($is_seckill) {
                        //获取秒杀商品的价格、库存、最大购买量
                        $condition_sku_info['seckill_id'] = $seckill_id;
                        $condition_sku_info['sku_id'] = $sku_id;
                        $sku_info_list = $sec_service->getSeckillSkuInfo($condition_sku_info);
                        
                        $sku_info_arr = objToArr($sku_info_list);
                        
                        //获取限购量
                        $goods_service = new Goods();
                        //通过用户累计购买量判断，先判断redis是否有内容
                        $uid = $this->uid;
                        $website_id = $this->website_id;
                        
                        $buy_num = $goods_service->getActivityOrderSku($uid, $sku_id, $website_id, $sku_info['seckill_id']);
                        
                        if ($sku_info['num'] > $is_seckill['seckill_num']) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出库存'];
                        }
                        //判断是否超过限购
                        if ($sku_info_arr['seckill_limit_buy'] != 0 && ($sku_info['num'] + $buy_num > $sku_info_arr['seckill_limit_buy'])) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 该商品规格购买数目已经超出最大秒杀限购数目'];
                        }
                        //价格
                        if ($is_seckill['seckill_price'] != $sku_info['price']) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 秒杀商品价格变动'];
                        }
                    }else{
                        return ['result' => -2, 'message' =>  $sku_db_info->goods->goods_name . '商品秒杀已结束，将恢复正常商品价格。'];
                    }
                    

                } elseif ($is_group && $order_data['group_id'] && getAddons('groupshopping', $this->website_id, $this->instance_id)) { //拼团 不确定 待测试
                    
                    if ($sku_info['num'] > $sku_db_info->stock) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出库存'];
                    }
                    if ($order_data['record_id']) {
                        $checkGroup = $group_server->checkGroupIsCan($order_data['record_id'], $this->uid);//判断该团购是否能参加
                        if ($checkGroup < 0) {
                            return ['result' => false, 'message' => '该团无法参加，请选择其他团或者自己发起团购'];
                        }
                    }
                    $goods_service = new Goods();
                    //通过用户累计购买量判断
                    $uid = $this->uid;
                    $website_id = $this->website_id;
                    $buy_num = $goods_service->getActivityOrderSkuForGroup($uid, $sku_info['sku_id'], $website_id, $order_data['group_id']);
                    if ($group_sku_info_arr['group_limit_buy'] != 0 && ($sku_info['num'] + $buy_num > $group_sku_info_arr['group_limit_buy'])) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 该商品规格您总共购买数目超出最大拼团限购数目'];
                    }
                    if ($sku_info['price'] != $group_sku_info_arr['group_price']) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 团购商品价格变动'];
                    }

                    

                } elseif (!empty($sku_info['bargain_id']) && getAddons('bargain', $this->website_id, $this->instance_id)) {//砍价
                    $bargain = new Bargain();
                    $order_server = new \data\service\Order\Order();
                    $condition_bargain['bargain_id'] = $sku_info['bargain_id'];
                    $uid = $this->uid;
                    $condition_bargain['website_id'] = $this->website_id;
                    $is_bargain = $bargain->isBargain($condition_bargain, $uid);
                    if($is_bargain){
                        $return_data['bargain_id'] = $sku_info['bargain_id'];
                        //库存
                        $bargain_stock = $is_bargain['bargain_stock'];
                        $limit_buy = $is_bargain['limit_buy'];
                        $price = $is_bargain['my_bargain']['now_bargain_money'];
                        $buy_num = $order_server->getActivityOrderSkuNum($uid, $sku_info['sku_id'], $this->website_id, 3, $sku_info['bargain_id']);
                        if($sku_info['num']>$bargain_stock){
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出砍价活动库存'];
                        }
                        if($sku_info['num'] + $buy_num > $limit_buy){
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 该商品规格您总共购买数目超出最大砍价限购数目'];
                        }
                        //价格
                        if ($sku_info['price'] != $price) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 砍价商品价格变动'];
                        }
                        
                    }else{
                        return ['result' => -2, 'message' => '砍价活动已过期或已关闭'];
                    }
                } elseif (!empty($sku_info['presell_id']) && getAddons('presell', $this->website_id, $this->instance_id)) {
                    $presell_service = new PresellService();
                    $count_people = $presell_service->get_presell_buy_num($sku_info['presell_id']);
                    $presell_info = $presell_service->get_presell_by_sku($sku_info['presell_id'], $sku_info['sku_id']);
                    if ($presell_info['presellnum'] < $count_people) {
                        return ['result' => false, 'message' => '预售商品库存不足'];
                    }
                    $user_buy = $presell_service->get_user_count($sku_info['presell_id']);//当前用户购买数
                    if($user_buy > $presell_info['maxbuy']){//当前用户购买数大于每人限购
                        return ['result' => false, 'message' => '您已达到商品预售购买上限'];
                    }
                    //付定金去掉运费
                    $shipping_fee_all_last = $shipping_fee_all[$shop_id];
                    $shipping_fee_all[$shop_id] = 0;

                   
                } else {
                    // 普通商品
                    //sku 信息检查
                    if ($sku_db_info->goods->state != 1) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 物品为不可购买状态'];
                    }
                    if ($sku_info['num'] > $sku_db_info->stock) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出库存'];
                    }
                    if (($sku_info['num'] > $sku_db_info->goods->max_buy) && $sku_db_info->goods->max_buy != 0) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出最大购买数目'];
                    }

                    if ($sku_info['price'] != $sku_db_info->price) {
                        return ['result' => false, 'message' => $sku_info->goods->goods_name . ' 商品价格变动'];
                    }
                    
                }
                
                // 限时折扣
                $discount_price = $sku_info['discount_price'];
                if (!empty($sku_info['discount_id'])) {
                    if (!getAddons('discount', $this->website_id)) {
                        return ['result' => false, 'message' => '限时折扣应用已关闭'];
                    }
                    $promotion_discount_info_db = $promotion_discount_model::get($sku_info['discount_id'], ['goods']);
                    if ($promotion_discount_info_db->status != 1) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . '限时折扣状态不可用'];
                    }
                    if ($promotion_discount_info_db->start_time > $now_time || $promotion_discount_info_db->end_time < $now_time) {
                        return ['result' => false, 'message' => '限时折扣不在可用时间内'];
                    }
                    if (($promotion_discount_info_db->range_type == 1 || $promotion_discount_info_db->range_type == 3) &&
                        ($promotion_discount_info_db->shop_id != $shop_id)) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 限时折扣不在可用范围内'];
                    }
                    if ($promotion_discount_info_db->range == 2) {
                        if ($promotion_discount_info_db->goods()->where(['goods_id' => ['=', $sku_db_info->goods_id]])->count() == 0) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 商品不在限时折扣指定商品范围内'];
                        }
                    }
                    // todo... 会员折扣 by sgw
                    $member_discount_price = $sku_info['discount_price'];
                    $discount_price_1 = round(($promotion_discount_info_db->discount_num / 10) * $member_discount_price, 2);
                    // 限时抢购商品表的折扣
                    $goods_discount = $promotion_discount_info_db->goods()->where(['goods_id' => $sku_info['goods_id']])->find();
                    if ($goods_discount) {
                        $promotion_discount = $promotion_discount_model->where(['discount_id' => $goods_discount['goods_id']])->find();
                        if($promotion_discount['integer_type'] == 1){
                            $discount_price_2 = round($goods_discount['discount'] / 10 * $member_discount_price);
                        }else{
                            $discount_price_2 = round($goods_discount['discount'] / 10 * $member_discount_price, 2);
                        }

                        if($goods_discount['discount_type'] == 2){
                            $discount_price_2 = $goods_discount['discount'];
                        }
                    }
                    if ($discount_price != $discount_price_1 && $discount_price != $discount_price_2) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 商品折扣价格变化'];
                    }
                    $return_data['order'][$shop_id]['sku'][$k]['promotion_shop_id'] = $promotion_discount_info_db->shop_id;// 限时折扣店铺id，用于识别优惠来源
                }
                //计时/次商品
                $goods_model = new VslGoodsModel();
                $goods_info = $goods_model->getInfo(['goods_id'=>$sku_info['goods_id']]);
                $return_data['order'][$shop_id]['sku'][$k]['shipping_fee'] = $goods_info['shipping_fee_type'] == 1 ? ($goods_info['shipping_fee'] ? : 0) : 0;
                $return_data['order'][$shop_id]['sku'][$k]['shipping_fee_type'] = $goods_info['shipping_fee_type'];
                if(getAddons('store', $this->website_id, $this->instance_id) && $goods_info['goods_type']==0){
                    $return_data['address_id'] = 0 ;
                    if($return_data['shop'][$shop_id]['card_store_id']==0){
                        return ['result' => false, 'message' => '请选择使用门店'];
                    }
                    $return_data['order'][$shop_id]['sku'][$k]['card_store_id'] = $return_data['shop'][$shop_id]['card_store_id'];
                    $return_data['order'][$shop_id]['sku'][$k]['cancle_times'] = $goods_info['cancle_times'];
                    $return_data['order'][$shop_id]['sku'][$k]['cart_type'] = $goods_info['cart_type'];
                    if($goods_info['valid_type']==1){
                        $return_data['order'][$shop_id]['sku'][$k]['invalid_time'] = time()+$goods_info['valid_days']*24*60*60;
                    }else{
                        $return_data['order'][$shop_id]['sku'][$k]['invalid_time'] = $goods_info['invalid_time'];
                    }
                    if($goods_info['is_wxcard']==1){
                        $return_data['order'][$shop_id]['sku'][$k]['wx_card_id'] = $goods_info['wx_card_id'];
                        $ticket = new VslGoodsTicketModel();
                        $ticket_info = $ticket->getInfo(['goods_id'=>$sku_info['goods_id']]);
                        $return_data['order'][$shop_id]['sku'][$k]['card_title'] = $ticket_info['card_title'];
                    }
                }
            } 
            
        }
        return $return_data;
        
    }
    /**
     * 计算移动端/app 提交创建订单所需数据
     * @param array $order_data
     */
    public function calculateCreateOrderData(array $order_data)
    {
        $now_time = time();
        $man_song_rule_model = getAddons('fullcut', $this->website_id) ? new VslPromotionMansongRuleModel() : '';
        $sku_model = new VslGoodsSkuModel();
        $coupon_model = getAddons('coupontype', $this->website_id) ? new VslCouponModel() : '';
        $promotion_discount_model = new VslPromotionDiscountModel();
        $user_model = new UserModel();
        $sec_service = getAddons('seckill', $this->website_id, $this->instance_id) ? new seckillServer() : '';
        $goodsExpress = new GoodsExpress();
        $group_server = getAddons('groupshopping', $this->website_id, $this->instance_id) ? new GroupShopping() : '';
        $storeServer = getAddons('store', $this->website_id, $this->instance_id) ? new Store() : '';
        $user_info = $user_model::get($this->uid, ['member_info.level', 'member_account', 'member_address']);
        if ($user_info->user_status == 0) {
            return ['result' => false, 'message' => '当前用户状态不能购买'];
        }
        if (empty($order_data['address_id']) && $order_data['shipping_type']==1) {
            return ['result' => false, 'message' => '缺少收货地址'];
        }
        $return_data['address_id'] = $order_data['address_id'];
        $return_data['group_id'] = $order_data['group_id'];
        $return_data['record_id'] = $order_data['record_id'];
        $return_data['shipping_type'] = $order_data['shipping_type'];
        $return_data['is_deduction'] = $order_data['is_deduction'];
        $member_discount = $user_info->member_info->level->goods_discount ? ($user_info->member_info->level->goods_discount / 10) : 1;
        $member_is_label = $user_info->member_info->level->is_label;
        $return_data['order'] = [];
        $return_data['promotion'] = [];
        $return_data['shop'] = [];
        if (empty($order_data['shop_list'])) {
            return ['result' => false, 'message' => '空的商品信息'];
        }
        // 准备一些数据
        // 计算运费        满件送/优惠券每个sku优惠占比  每个店铺购买商品总数，计算每个sku邮费占比
        $shipping_goods = $promotion_shop_amount = $shop_goods_num = [];
        foreach ($order_data['shop_list'] as $shop) {
            $shop_id = $shop['shop_id'];
            $promotion_shop_amount[$shop_id] = 0;
            $shop_goods_num[$shop_id] = 0;
            $has_store = getAddons('store', $this->website_id, $this->instance_id) ? $storeServer->getStoreSet($shop_id)['is_use'] : 0;
            if ($order_data['shipping_type'] == 2 && $has_store && !$shop['store_id'] && empty($shop['card_store_id'])) {
                return ['result' => false, 'message' => '没有选择门店'];
            }
            if (empty($order_data['address_id']) && $order_data['shipping_type']==2 && !$has_store && empty($shop['card_store_id'])) {
                return ['result' => false, 'message' => '缺少收货地址'];
            }
            $return_data['shop'][$shop_id]['leave_message'] = $shop['leave_message'] ?: '';
            $return_data['shop'][$shop_id]['store_id'] = $shop['store_id'] ?: 0;
            $return_data['shop'][$shop_id]['card_store_id'] = (empty($shop['card_store_id']))?0:$shop['card_store_id'];
            foreach ($shop['goods_list'] as $sku_info) {
                $goods_id = $sku_info['goods_id'];
                $shop_goods_num[$shop_id] += $sku_info['num'];
                if (empty($shipping_goods[$shop_id][$goods_id])) {
                    $shipping_goods[$shop_id][$goods_id]['goods_id'] = $goods_id;
                    $shipping_goods[$shop_id][$goods_id]['count'] = $sku_info['num'];
                } else {
                    $shipping_goods[$shop_id][$goods_id]['count'] += $sku_info['num'];
                }
                if (empty($sku_info['seckill_id']) && empty($order_data['record_id'])) {
                    $promotion_shop_amount[$shop_id] += $sku_info['discount_price'] * $sku_info['num'];
                }
            }
        }
        // 获取以每个店铺,每个店铺下面每个商品(goods_id)的邮费,邮费与goods_id绑定
        $district_id = $user_info->member_address()->where(['id' => $order_data['address_id']])->find()['district'];
        $shipping_fee_all = [];
        $unShippingTypeCount = [];//没使用运费模板的数量
        $unShippingTypeMoney = [];//没使用运费模板的运费金额
        if (!empty($shipping_goods)) {
            /*
            * 修复bug 运费模板体积重量件数叠加计算运费
            * $author ljt 2018-08-05
            */
            foreach ($shipping_goods as $shop_id => $goods) {
                $return_data['shop'][$shop_id]['shipping_fee'] = 0;
                 $tempgoods = [];
                foreach ($goods as $goods_id => $info) {
                    $tempgoods[$goods_id]['count'] = $info['count'];
                    $tempgoods[$goods_id]['goods_id'] = $info['goods_id'];
                }
                $fee = $goodsExpress->getGoodsExpressTemplate($tempgoods, $district_id);
                $shippingType = $goodsExpress->getGoodsExpressTemplate($tempgoods, $district_id);
                $fee = $shippingType['totalFee'];
                if($order_data['shipping_type'] == 2){
                    $fee = 0;
                }
                //$shipping_goods[$shop_id][$goods]['fee'] = $fee;
                $shipping_fee_all[$shop_id] = $fee;
                $unShippingTypeCount[$shop_id] = $shippingType['unShippingType'];
                $unShippingTypeMoney[$shop_id] = $shippingType['unShippingTypeMoney'];
                
            }
        }
        
        // start 计算,校验
        foreach ($order_data['shop_list'] as $shop) {
            $shop_id = $shop['shop_id'];
            $return_data['shop'][$shop_id]['shop_channel_amount'] = 0;
            foreach ($shop['goods_list'] as $k => $sku_info) {
                $sku_db_info = $sku_model::get($sku_info['sku_id'], ['goods']);
                $temp_sku_id = $sku_info['sku_id'];
                $return_data['order'][$shop_id]['sku'][$k]['sku_id'] = $temp_sku_id;
                $return_data['order'][$shop_id]['sku'][$k]['goods_id'] = $sku_info['goods_id'];
                $return_data['order'][$shop_id]['sku'][$k]['channel_id'] = $sku_info['channel_id'];
                $return_data['order'][$shop_id]['sku'][$k]['seckill_id'] = $sku_info['seckill_id'];
                $return_data['order'][$shop_id]['sku'][$k]['seckill_id'] = $sku_info['seckill_id'];
                if($sku_info['presell_id'] && getAddons('presell', $this->website_id, $this->instance_id)){
                    //判断预售是否关闭或者过期
                    $presell_id = $sku_info['presell_id'];
                    //如果是预售的商品，则更改其单价为预售价
                    $presell_mdl = new VslPresellModel();
                    $presell_condition['p.id'] = $presell_id;
                    $presell_condition['p.start_time'] = ['<', time()];
                    $presell_condition['p.end_time'] = ['>=', time()];
                    $presell_condition['p.status'] = ['neq', 3];
                    $presell_condition['pg.sku_id'] = $sku_info['sku_id'];
                    $presell_goods_info = $presell_mdl->alias('p')->where($presell_condition)->join('vsl_presell_goods pg', 'p.id = pg.presell_id', 'LEFT')->find();
                    if(!$presell_goods_info){
                        return ['result' => -2, 'message' => '预售活动已过期或已关闭'];
                    }
                    $return_data['order'][$shop_id]['sku'][$k]['presell_id'] = $sku_info['presell_id'];
                }else{
                    $return_data['order'][$shop_id]['sku'][$k]['presell_id'] = 0;
                }

                $return_data['order'][$shop_id]['sku'][$k]['bargain_id'] = $sku_info['bargain_id'];
                $return_data['order'][$shop_id]['sku'][$k]['price'] = $sku_info['price'];
                
                //获取会员折扣 auth 拉登&2019/10/17
                $return_data['order'][$shop_id]['sku'][$k]['member_price'] = $this->getDiscountPrice($sku_info['sku_id']);

                $return_data['order'][$shop_id]['sku'][$k]['discount_id'] = $sku_info['discount_id'];
                $return_data['order'][$shop_id]['sku'][$k]['discount_price'] = $sku_info['discount_price'];
                $return_data['order'][$shop_id]['sku'][$k]['num'] = $sku_info['num'];
                $return_data['order'][$shop_id]['sku'][$k]['shop_id'] = $sku_db_info->goods->shop_id;
                $return_data['order'][$shop_id]['sku'][$k]['point_deduction_max'] = $sku_db_info->goods->point_deduction_max;
                $return_data['order'][$shop_id]['sku'][$k]['point_return_max'] = $sku_db_info->goods->point_return_max;
                //$return_data['order'][$shop_id]['sku'][$temp_sku_id]['shipping_fee'] = $goodsExpress->getGoodsExpressTemplate(['goods_id'=>], $address['district']);

                $return_data['shop'][$shop_id]['goods_id_array'][] = $sku_info['goods_id'];
                $return_data['shop'][$shop_id]['member_amount'] += $return_data['order'][$shop_id]['sku'][$k]['member_price'] * $sku_info['num'];
                $return_data['shop'][$shop_id]['shop_total_amount'] += $sku_info['price'] * $sku_info['num'];
                $return_data['shop'][$shop_id]['shop_discount_amount'] += $sku_info['discount_price'] * $sku_info['num'];
                if (getAddons('groupshopping', $this->website_id, $this->instance_id)){
                    $is_group = $group_server->isGroupGoods($sku_info['goods_id']);//是否团购商品
                    $group_sku_info_obj = $group_server->getGroupSkuInfo(['sku_id' => $sku_info['sku_id'], 'goods_id' => $sku_info['goods_id'], 'group_id' => $is_group]);
                    $group_sku_info_arr = objToArr($group_sku_info_obj);//商品团购信息
                }
//                $return_data['shop'][$shop_id]['shipping_fee'] += 1;
                if (!empty($sku_info['channel_id']) && getAddons('channel', $this->website_id) ) {
                    $website_id = $this->website_id;
                    $channel_gs_mdl = new VslChannelGoodsSkuModel();
                    $channel_gs_info = $channel_gs_mdl->getInfo(['website_id' => $website_id, 'channel_id' => $sku_info['channel_id'], 'sku_id' => $sku_info['sku_id']], 'stock');
//                    echo '<pre>';print_r(objToArr($channel_gs_info));exit;
                    if ($sku_info['num'] > $channel_gs_info['stock']) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . '商品渠道商库存不足'];
                    }
                    $sku_db_info->stock = $channel_gs_info['stock'];
                    //用于计算渠道商金额的字段 (shop_channel_amount/shop_discount_amount)*shop_should_paid_amount
                    $return_data['shop'][$shop_id]['shop_channel_amount'] += $sku_info['discount_price'] * $sku_info['num'];
//                    $return_data['shop'][$shop_id]['shop_should_paid_amount'] += $sku_info['discount_price'] * $sku_info['num'];
//                    var_dump($sku_info['discount_price'] , $sku_info['num']);echo '<br>';
                }
                if (!empty($sku_info['seckill_id']) && getAddons('seckill', $this->website_id, $this->instance_id)) {
                    //判断秒杀商品是否过期
                    $sku_id = $sku_info['sku_id'];
                    $seckill_id = $sku_info['seckill_id'];
                    $condition_is_seckill['s.seckill_id'] = $seckill_id;
                    $condition_is_seckill['nsg.sku_id'] = $sku_id;
                    $is_seckill = $sec_service->isSeckillGoods($condition_is_seckill);
                    if ($is_seckill) {
                        //获取秒杀商品的价格、库存、最大购买量
                        $condition_sku_info['seckill_id'] = $seckill_id;
                        $condition_sku_info['sku_id'] = $sku_id;
                        $sku_info_list = $sec_service->getSeckillSkuInfo($condition_sku_info);
                        $sku_info_arr = objToArr($sku_info_list);
//                        echo '<pre>';print_r($sku_info_arr);exit;
                        //获取限购量
                        $goods_service = new Goods();
                        //通过用户累计购买量判断，先判断redis是否有内容
                        $uid = $this->uid;
                        $website_id = $this->website_id;
                        $buy_num = $goods_service->getActivityOrderSku($uid, $sku_id, $website_id, $sku_info['seckill_id']);
//                        $buy_num = 0;
                        if ($sku_info['num'] > $is_seckill['seckill_num']) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出库存'];
                        }
                        //判断是否超过限购
                        if ($sku_info_arr['seckill_limit_buy'] != 0 && ($sku_info['num'] + $buy_num > $sku_info_arr['seckill_limit_buy'])) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 该商品规格购买数目已经超出最大秒杀限购数目'];
                        }
                        //价格
                        if ($is_seckill['seckill_price'] != $sku_info['price']) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 秒杀商品价格变动'];
                        }
                    }else{
                        return ['result' => -2, 'message' =>  $sku_db_info->goods->goods_name . '商品秒杀已结束，将恢复正常商品价格。'];
                    }
                    $return_data['shop'][$shop_id]['shop_should_paid_amount'] += $sku_info['discount_price'] * $sku_info['num'];

                } elseif ($is_group && $order_data['group_id'] && getAddons('groupshopping', $this->website_id, $this->instance_id)) {
                    if ($sku_info['num'] > $sku_db_info->stock) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出库存'];
                    }
                    if ($order_data['record_id']) {
                        $checkGroup = $group_server->checkGroupIsCan($order_data['record_id'], $this->uid);//判断该团购是否能参加
                        if ($checkGroup < 0) {
                            return ['result' => false, 'message' => '该团无法参加，请选择其他团或者自己发起团购'];
                        }
                    }
                    $goods_service = new Goods();
                    //通过用户累计购买量判断
                    $uid = $this->uid;
                    $website_id = $this->website_id;
                    $buy_num = $goods_service->getActivityOrderSkuForGroup($uid, $sku_info['sku_id'], $website_id, $order_data['group_id']);
                    if ($group_sku_info_arr['group_limit_buy'] != 0 && ($sku_info['num'] + $buy_num > $group_sku_info_arr['group_limit_buy'])) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 该商品规格您总共购买数目超出最大拼团限购数目'];
                    }
                    if ($sku_info['price'] != $group_sku_info_arr['group_price']) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 团购商品价格变动'];
                    }

                    $return_data['shop'][$shop_id]['shop_should_paid_amount'] += $sku_info['discount_price'] * $sku_info['num'];

                } elseif (!empty($sku_info['bargain_id']) && getAddons('bargain', $this->website_id, $this->instance_id)) {//砍价
                    $bargain = new Bargain();
                    $order_server = new \data\service\Order\Order();
                    $condition_bargain['bargain_id'] = $sku_info['bargain_id'];
                    $uid = $this->uid;
                    $condition_bargain['website_id'] = $this->website_id;
                    $is_bargain = $bargain->isBargain($condition_bargain, $uid);
                    if($is_bargain){
                        $return_data['bargain_id'] = $sku_info['bargain_id'];
                        //库存
                        $bargain_stock = $is_bargain['bargain_stock'];
                        $limit_buy = $is_bargain['limit_buy'];
                        $price = $is_bargain['my_bargain']['now_bargain_money'];
                        $buy_num = $order_server->getActivityOrderSkuNum($uid, $sku_info['sku_id'], $this->website_id, 3, $sku_info['bargain_id']);
                        if($sku_info['num']>$bargain_stock){
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出砍价活动库存'];
                        }
                        if($sku_info['num'] + $buy_num > $limit_buy){
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 该商品规格您总共购买数目超出最大砍价限购数目'];
                        }
                        //价格
                        if ($sku_info['price'] != $price) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 砍价商品价格变动'];
                        }
                        $return_data['shop'][$shop_id]['shop_should_paid_amount'] += $price * $sku_info['num'];
                    }else{
                        return ['result' => -2, 'message' => '砍价活动已过期或已关闭'];
                    }
                } elseif (!empty($sku_info['presell_id']) && getAddons('presell', $this->website_id, $this->instance_id)) {
                    $presell_service = new PresellService();
                    $count_people = $presell_service->get_presell_buy_num($sku_info['presell_id']);
                    $presell_info = $presell_service->get_presell_by_sku($sku_info['presell_id'], $sku_info['sku_id']);
                    if ($presell_info['presellnum'] < $count_people) {
                        return ['result' => false, 'message' => '预售商品库存不足'];
                    }
                    $user_buy = $presell_service->get_user_count($sku_info['presell_id']);//当前用户购买数
                    if($user_buy > $presell_info['maxbuy']){//当前用户购买数大于每人限购
                        return ['result' => false, 'message' => '您已达到商品预售购买上限'];
                    }
//                    $return_data['shop'][$shop_id]['shop_should_paid_amount'] = $sku_info['num'] * $presell_info['firstmoney'] + $return_data['shop'][$shop_id]['shipping_fee'];
                    //付定金去掉运费
                    $shipping_fee_all_last = $shipping_fee_all[$shop_id];
                    $shipping_fee_all[$shop_id] = 0;
                    $return_data['shop'][$shop_id]['shop_should_paid_amount'] = $sku_info['num'] * $presell_info['firstmoney'];
                } else {
                    // 普通商品
                    //sku 信息检查
                    if ($sku_db_info->goods->state != 1) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 物品为不可购买状态'];
                    }
                    if ($sku_info['num'] > $sku_db_info->stock) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出库存'];
                    }
                    if (($sku_info['num'] > $sku_db_info->goods->max_buy) && $sku_db_info->goods->max_buy != 0) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出最大购买数目'];
                    }
                    // todo... 会员折扣 by sgw 那购物车价格   $sku_db_info(数据库中sku) $sku_info(post过来的)
                    /*$goodsPrice = $sku_db_info->price;
                    $cart = new VslCartModel();
                    $cart_condition = [
                        'website_id' => $this-> website_id,
                        'goods_id' => $sku_db_info->goods_id
                    ];
                    $cartRes = $cart->getInfo($cart_condition, 'price');
                    if ($cartRes) {//购物车中购买
                        $goodsPrice = $cartRes['price'];
                    } else {//直接购买
                        $goods_server = new GoodsService();
                        $goodsDiscountInfo = $goods_server->getGoodsInfoOfIndependentDiscount($sku_db_info->goods_id, $sku_db_info->price);//计算会员折扣价
                        if ($goodsDiscountInfo) {
                            $goodsPrice = $goodsDiscountInfo['member_price'];
                        }
                    }*/

                    if ($sku_info['price'] != $sku_db_info->price) {
                        return ['result' => false, 'message' => $sku_info->goods->goods_name . ' 商品价格变动'];
                    }
                    $return_data['shop'][$shop_id]['shop_should_paid_amount'] += $sku_info['discount_price'] * $sku_info['num'];
                }

                // 限时折扣
                $discount_price = $sku_info['discount_price'];
                // $discount_price = $return_data['order'][$shop_id]['sku'][$k]['member_price'];
                //#
                if (!empty($sku_info['discount_id'])) {
                    if (!getAddons('discount', $this->website_id)) {
                        return ['result' => false, 'message' => '限时折扣应用已关闭'];
                    }
                    $promotion_discount_info_db = $promotion_discount_model::get($sku_info['discount_id'], ['goods']);
                    if ($promotion_discount_info_db->status != 1) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . '限时折扣状态不可用'];
                    }
                    if ($promotion_discount_info_db->start_time > $now_time || $promotion_discount_info_db->end_time < $now_time) {
                        return ['result' => false, 'message' => '限时折扣不在可用时间内'];
                    }
                    if (($promotion_discount_info_db->range_type == 1 || $promotion_discount_info_db->range_type == 3) &&
                        ($promotion_discount_info_db->shop_id != $shop_id)) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 限时折扣不在可用范围内'];
                    }
                    if ($promotion_discount_info_db->range == 2) {
                        if ($promotion_discount_info_db->goods()->where(['goods_id' => ['=', $sku_db_info->goods_id]])->count() == 0) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 商品不在限时折扣指定商品范围内'];
                        }
                    }
                    // 限时折扣主表的折扣

                    $member_discount_price = $sku_info['discount_price'];
                    // debugLog($member_discount_price, '==>member_discount_price<==');
                    // debugLog($promotion_discount_info_db->discount_num, '==>discount_num<==');
                    $discount_price_1 = round(($promotion_discount_info_db->discount_num / 10) * $member_discount_price, 2);
                    // 限时抢购商品表的折扣
                    $goods_discount = $promotion_discount_info_db->goods()->where(['goods_id' => $sku_info['goods_id']])->find();
                    if ($goods_discount) {
                        // debugLog($goods_discount['discount'], '==>goods_discount<==');
                        $promotion_discount = $promotion_discount_model->where(['discount_id' => $goods_discount['goods_id']])->find();
                        if($promotion_discount['integer_type'] == 1){
                            $discount_price_2 = round($goods_discount['discount'] / 10 * $member_discount_price);
                        }else{
                            $discount_price_2 = round($goods_discount['discount'] / 10 * $member_discount_price, 2);
                        }

                        if($goods_discount['discount_type'] == 2){
                            $discount_price_2 = $goods_discount['discount'];
                        }
                    }
                    if ($discount_price != $discount_price_1 && $discount_price != $discount_price_2) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 商品折扣价格变化'];
                    }
                    $return_data['order'][$shop_id]['sku'][$k]['promotion_shop_id'] = $promotion_discount_info_db->shop_id;// 限时折扣店铺id，用于识别优惠来源
                }

                /*//判断是否预售商品
                if (!empty($sku_info['presell_id']) && getAddons('presell', $this->website_id, $this->instance_id)) {
                    $presell_service = new PresellService();
                    $count_people = $presell_service->get_presell_buy_num($sku_info['presell_id']);
                    $presell_info = $presell_service->get_presell_by_sku($sku_info['presell_id'], $sku_info['sku_id']);
                    if ($presell_info['presellnum'] > $count_people) {
                        return ['result' => false, 'message' => '预售商品库存不足'];
                    }
//                    $return_data['shop'][$shop_id]['shop_should_paid_amount'] = $sku_info['num'] * $presell_info['firstmoney'] + $return_data['shop'][$shop_id]['shipping_fee'];
                    $return_data['shop'][$shop_id]['shop_should_paid_amount'] = $sku_info['num'] * $presell_info['firstmoney'];
                }*/
                
                //计时/次商品
                $goods_model = new VslGoodsModel();
                $goods_info = $goods_model->getInfo(['goods_id'=>$sku_info['goods_id']]);
                $return_data['order'][$shop_id]['sku'][$k]['shipping_fee'] = $goods_info['shipping_fee_type'] == 1 ? ($goods_info['shipping_fee'] ? : 0) : 0;
                $return_data['order'][$shop_id]['sku'][$k]['shipping_fee_type'] = $goods_info['shipping_fee_type'];
                if(getAddons('store', $this->website_id, $this->instance_id) && $goods_info['goods_type']==0){
                    $return_data['address_id'] = 0 ;
                    if($return_data['shop'][$shop_id]['card_store_id']==0){
                        return ['result' => false, 'message' => '请选择使用门店'];
                    }
                    $return_data['order'][$shop_id]['sku'][$k]['card_store_id'] = $return_data['shop'][$shop_id]['card_store_id'];
                    $return_data['order'][$shop_id]['sku'][$k]['cancle_times'] = $goods_info['cancle_times'];
                    $return_data['order'][$shop_id]['sku'][$k]['cart_type'] = $goods_info['cart_type'];
                    if($goods_info['valid_type']==1){
                        $return_data['order'][$shop_id]['sku'][$k]['invalid_time'] = time()+$goods_info['valid_days']*24*60*60;
                    }else{
                        $return_data['order'][$shop_id]['sku'][$k]['invalid_time'] = $goods_info['invalid_time'];
                    }
                    if($goods_info['is_wxcard']==1){
                        $return_data['order'][$shop_id]['sku'][$k]['wx_card_id'] = $goods_info['wx_card_id'];
                        $ticket = new VslGoodsTicketModel();
                        $ticket_info = $ticket->getInfo(['goods_id'=>$sku_info['goods_id']]);
                        $return_data['order'][$shop_id]['sku'][$k]['card_title'] = $ticket_info['card_title'];
                    }
                }
            }

            // 满减送
            if (!empty($shop['rule_id'])) {
                if (!getAddons('fullcut', $this->website_id)) {
                    return ['result' => false, 'message' => '满减送应用已关闭'];
                }
                $rule_db_info = $man_song_rule_model::get($shop['rule_id'], ['promotion_man_song.goods']);

                if ($rule_db_info->promotion_man_song->status != 1) {
                    return ['result' => false, 'message' => '满减送活动状态不可用'];
                }
                if ($rule_db_info->promotion_man_song->start_time > $now_time || $rule_db_info->promotion_man_song->end_time < $now_time) {
                    return ['result' => false, 'message' => '满减送活动不在可用时间内'];
                }
                if (($rule_db_info->promotion_man_song->range == 1 || $rule_db_info->promotion_man_song->range == 3) && $rule_db_info->promotion_man_song->shop_id != $shop_id) {
                    return ['result' => false, 'message' => '满减送活动不在可用店铺范围内'];
                }
                $man_song_compare_amount = 0.00;
                // 满减送活动指定商品范围
                $full_cut_goods_id_array = [];
                foreach ($rule_db_info->promotion_man_song->goods as $goods) {
                    $full_cut_goods_id_array[] = $goods->goods_id;
                }
                if ($rule_db_info->promotion_man_song->range_type == 0) {
                    // 部分商品
                    foreach ($shop['goods_list'] as $sku_id => $sku_info) {
                        if (in_array($sku_info['goods_id'], $full_cut_goods_id_array)) {
                            $man_song_compare_amount += $sku_info['discount_price'] * $sku_info['num'];
                        }
                    }
                } else {
                    $man_song_compare_amount = $promotion_shop_amount[$shop_id];
                }
                if ($rule_db_info->price > $man_song_compare_amount) {
                    return ['result' => false, 'message' => '满减送活动达不到金额要求'];
                }
                $return_data['promotion'][$shop_id]['man_song'][$rule_db_info->mansong_id]['rule_id'] = $rule_db_info->rule_id;
                $return_data['promotion'][$shop_id]['man_song'][$rule_db_info->mansong_id]['price'] = $rule_db_info->price;
                $return_data['promotion'][$shop_id]['man_song'][$rule_db_info->mansong_id]['discount'] = $rule_db_info->discount;
                $return_data['promotion'][$shop_id]['man_song'][$rule_db_info->mansong_id]['shop_id'] = $rule_db_info->promotion_man_song->shop_id;
                $return_data['promotion'][$shop_id]['man_song'][$rule_db_info->mansong_id]['free_shipping_fee'] = $rule_db_info->free_shipping;
                $return_data['shop'][$shop_id]['shop_should_paid_amount'] -= $rule_db_info->discount;
                $return_data['shop'][$shop_id]['man_song_amount'] = $rule_db_info->discount;
                $return_data['shop'][$shop_id]['man_song_coupon_type_id'] = $rule_db_info->give_coupon;
                $return_data['shop'][$shop_id]['man_song_point'] = $rule_db_info->give_point;
                // 计算每个sku的满减送优惠占比,邮费占比
                $all_full_count = 0;
                $all_full_count_sku = [];
                foreach ($return_data['order'][$shop_id]['sku'] as $key => $goods_1) {
                    if (empty($goods_1['seckill_id']) && empty($order_data['record_id'])) {
                        if(in_array($goods_1['goods_id'], $full_cut_goods_id_array) || $rule_db_info->promotion_man_song->range_type == 1){
                            $all_full_count_sku[] = $goods_1['sku_id'];
                            $all_full_count += $goods_1['discount_price'] * $goods_1['num'];
                        }
                    }
                }
                
                $j = 0;
                    $partPercent = 0;
                $tempgoods2 = [];
                $temp_nmu = $shop_goods_num[$shop_id];// 订单商品件数
                $temp_shipping_fee = 0;
                $unMansong = [];
                $allFee = $shipping_fee_all[$shop_id]; //总运费

                foreach ($return_data['order'][$shop_id]['sku'] as $key => $goods_1) {
                    if (empty($goods_1['seckill_id']) && empty($order_data['record_id'])) {
                        
                        $fullCount = count($all_full_count_sku);
                        
                        if (in_array($goods_1['goods_id'], $full_cut_goods_id_array) || $rule_db_info->promotion_man_song->range_type == 1) {// 全部商品
                            $j++;
                            $return_data['order'][$shop_id]['sku'][$key]['promotion_id'] = $rule_db_info->promotion_man_song->mansong_id;
                            $percent = round(($goods_1['discount_price'] * $goods_1['num'] / $all_full_count),2);
                            
                            if($j != $fullCount){
                                $partPercent += $percent;
                                $return_data['order'][$shop_id]['sku'][$key]['full_cut_sku_percent'] = $percent;
                            }else{
                                $return_data['order'][$shop_id]['sku'][$key]['full_cut_sku_percent'] = 1 - $partPercent;
                            }
                            
                            $return_data['order'][$shop_id]['sku'][$key]['full_cut_sku_amount'] = $rule_db_info->discount;
                            $return_data['order'][$shop_id]['sku'][$key]['full_cut_shop_id'] = $rule_db_info->promotion_man_song->shop_id;
                            $return_data['order'][$shop_id]['sku'][$key]['full_cut_range'] = $rule_db_info->promotion_man_song->range;
                             //  todo... 包邮
                            if($rule_db_info->free_shipping == 1){
                                $return_data['order'][$shop_id]['sku'][$key]['is_free_shipping'] = 1;// sku 满减送包邮信息
                                $return_data['order'][$shop_id]['sku'][$key]['free_shipping_shop_id'] = $rule_db_info->promotion_man_song->shop_id;
                                $return_data['order'][$shop_id]['sku'][$key]['shipping_fee'] = 0;
                                $temp_shipping_fee += $goods_1['shipping_fee'];
                                $temp_nmu -= $goods_1['num'];
                                $shipping_fee_all[$shop_id] -= $goods_1['shipping_fee'];
                            }else{
                                $return_data['order'][$shop_id]['sku'][$key]['is_free_shipping'] = 0;
                                $return_data['order'][$shop_id]['sku'][$key]['free_shipping_shop_id'] = 0;
                                if($tempgoods2[$goods_1['goods_id']]){
                                    $tempgoods2[$goods_1['goods_id']]['count'] += $goods_1['num'];
                                }else{
                                    $tempgoods2[$goods_1['goods_id']]['goods_id'] = $goods_1['goods_id'];
                                    $tempgoods2[$goods_1['goods_id']]['count'] = $goods_1['num'];
                                }
                                // todo... 运费均分（剔除满减包邮的情况）

                                if (($shop_goods_num[$shop_id] > $unShippingTypeCount[$shop_id]) && ($shipping_fee_all[$shop_id] > $unShippingTypeMoney[$shop_id]) && $goods_1['shipping_fee_type'] == 2) {
                                    $sku_shipping_fee = round(($goods_1['num'] / ($shop_goods_num[$shop_id] - $unShippingTypeCount[$shop_id])) * ($shipping_fee_all[$shop_id] - $unShippingTypeMoney[$shop_id]), 2);
                                $return_data['order'][$shop_id]['sku'][$key]['shipping_fee'] = $sku_shipping_fee;
                                }
                            }
                            
                         }else{
                             $return_data['order'][$shop_id]['sku'][$key]['promotion_id'] = 0;
                             $return_data['order'][$shop_id]['sku'][$key]['full_cut_sku_percent'] = 0;
                             $return_data['order'][$shop_id]['sku'][$key]['full_cut_sku_amount'] = 0;
                             $return_data['order'][$shop_id]['sku'][$key]['full_cut_shop_id'] = 0;
                             $return_data['order'][$shop_id]['sku'][$key]['full_cut_range'] = 0;
                             $return_data['order'][$shop_id]['sku'][$key]['is_free_shipping'] = 0;
                            $return_data['order'][$shop_id]['sku'][$key]['free_shipping_shop_id'] = 0;
                             if($tempgoods2[$goods_1['goods_id']]){
                                 $tempgoods2[$goods_1['goods_id']]['count'] += $goods_1['num'];
                             }else{
                                 $tempgoods2[$goods_1['goods_id']]['goods_id'] = $goods_1['goods_id'];
                                 $tempgoods2[$goods_1['goods_id']]['count'] = $goods_1['num'];
                             }
                             if($goods_1['shipping_fee_type'] == 2){
                                 $unMansong[$key] = $goods_1;
                             }else{
                                 $shipping_fee_all[$shop_id] -= $return_data['order'][$shop_id]['sku'][$key]['shipping_fee'];
                             }

                             // 邮费均分(剔除满减包邮的件数)
                             //$sku_shipping_fee = round(($goods_1['num'] / $temp_nmu) * $shipping_fee_all[$shop_id], 2);
                             //print_r($return_data['order'][$shop_id]['sku'][$key]);die;
                             //$return_data['order'][$shop_id]['sku'][$key]['shipping_fee'] = $sku_shipping_fee;
                         }

                        }
                    }

                unset($goods_1);
                $countUnMansong = count($unMansong);
                $i = 1;
                $nowShipping = 0;
                foreach($unMansong as $unkey => $unval){
                    if($i < $countUnMansong){
                        $return_data['order'][$shop_id]['sku'][$unkey]['shipping_fee'] = round(($unval['num'] / count($unMansong)) * ($allFee - $unShippingTypeMoney[$shop_id]), 2);
                        $nowShipping += round(($unval['num'] / count($unMansong)) * ($allFee - $unShippingTypeMoney[$shop_id]), 2);
                    }else{

                        $return_data['order'][$shop_id]['sku'][$unkey]['shipping_fee'] = round(($allFee  - $unShippingTypeMoney[$shop_id]) - $nowShipping);
                    }
                    $i++;
                }
                unset($unval);
            } elseif($return_data['shipping_type'] != 2) {
                // 没有包邮，快递配送,计算每个sku邮费占比,
                foreach ($return_data['order'][$shop_id]['sku'] as $key => $goods_1) {
                    $t_shipping_fee = 0;
                    if($goods_1['shipping_fee_type'] == 2){
                        if($goods_1['presell_id']){
                            $t_shipping_fee = 1; //作用于区分预售运费
                            $sku_shipping_fee = round(($goods_1['num'] / ($shop_goods_num[$shop_id] - $unShippingTypeCount[$shop_id])) * ($shipping_fee_all_last - $unShippingTypeMoney[$shop_id]), 2);
                        }else{
                            $sku_shipping_fee = round(($goods_1['num'] / ($shop_goods_num[$shop_id] - $unShippingTypeCount[$shop_id])) * ($shipping_fee_all[$shop_id] - $unShippingTypeMoney[$shop_id]), 2);
                        }
                        
                        $return_data['order'][$shop_id]['sku'][$key]['shipping_fee'] = $sku_shipping_fee;
                    }

                }
                unset($goods_1);
                if($t_shipping_fee == 1){
                    $return_data['shop'][$shop_id]['shipping_fee'] = $shipping_fee_all_last;
                }else{
                    $return_data['shop'][$shop_id]['shipping_fee'] = $shipping_fee_all[$shop_id];
                }
                $return_data['shop'][$shop_id]['shop_should_paid_amount'] += $shipping_fee_all[$shop_id];
            }else{
                //门店自提
                //没有满减送
                //门店自提，若门店没有开启自提点，需物流运费,并且没有满减送
                if($shop['has_store'] > 0){
                    $return_data['shop'][$shop_id]['shipping_fee'] = 0;
                }else{
                    foreach ($return_data['order'][$shop_id]['sku'] as $key => $goods_1) {
                        if($goods_1['shipping_fee_type'] == 2){ //运费模板
                            //获取当前商品运费模板
                            $tempgoods_2 = [];
                            $s_goods_id = $goods_1['goods_id'];
                            $tempgoods_2[$s_goods_id]['count'] = $goods_1['num'];
                            $tempgoods_2[$s_goods_id]['goods_id'] = $goods_1['goods_id'];
                            $return_data['shop'][$shop_id]['shipping_fee'] += $goodsExpress->getGoodsExpressTemplate($tempgoods2, $district_id)['totalFee'];
                           
                        }else if($goods_1['shipping_fee_type'] == 1) {//固定运费
                            $return_data['shop'][$shop_id]['shipping_fee'] += $goods_1['shipping_fee'];
                        }else{//包邮
                            $return_data['shop'][$shop_id]['shipping_fee'] += 0;
                        }
                    } 
                    $return_data['shop'][$shop_id]['shop_should_paid_amount'] += $return_data['shop'][$shop_id]['shipping_fee'];
                }
            }

            
            if ($tempgoods2 && !empty($district_id) && ($return_data['shipping_type'] != 2 || ($return_data['shipping_type'] == 2 && $shop['has_store'] == 0))){//非门店自提 或者门店自提，但是没有开启可自提门店
                $return_data['shop'][$shop_id]['shipping_fee'] = $goodsExpress->getGoodsExpressTemplate($tempgoods2, $district_id)['totalFee'];
                $tempgoods2 = [];
                $return_data['shop'][$shop_id]['shop_should_paid_amount'] += $return_data['shop'][$shop_id]['shipping_fee'];
                $return_data['shop'][$shop_id]['promotion_free_shipping'] += round($allFee - $return_data['shop'][$shop_id]['shipping_fee'], 2);
            }

            if (!empty($shop['coupon_id'])) {
                if (!getAddons('coupontype', $this->website_id)) {
                    return ['result' => false, 'message' => '优惠券应用已关闭'];
                }
                $coupon_db_info = $coupon_model::get($shop['coupon_id'], ['coupon_type.goods']);
                if ($coupon_db_info->state != 1) {
                    return ['result' => false, 'message' => '优惠券状态为不可用'];
                }
                if ($coupon_db_info->coupon_type->start_time > $now_time || $coupon_db_info->coupon_type->end_time < $now_time) {
                    return ['result' => false, 'message' => '优惠券不在可用时间内'];
                }
                if ($coupon_db_info->coupon_type->shop_range_type == 1 && $coupon_db_info->coupon_type->shop_id != $shop_id) {
                    return ['result' => false, 'message' => '优惠券不在可用店铺范围内'];
                }
                $couponGoods = [];
                if ($coupon_db_info->coupon_type->range_type == 0) {
                    $couponGoodsModel = new \addons\coupontype\model\VslCouponGoodsModel();
                    $couponGoods = $couponGoodsModel->Query(['coupon_type_id' => $coupon_db_info->coupon_type_id, 'website_id' => $this->website_id], 'goods_id');
                    // 部分商品可用
                    foreach ($shop['goods_list'] as $k => $sku_info) {
                        if ($coupon_db_info->coupon_type->goods()->where(['goods_id' => $sku_info['goods_id']])->count() == 0 && empty($sku_info['seckill_id']) && empty($sku_info['record_id'])) {
                            // 有商品不在活动方位内时
                            return ['result' => false, 'message' => '优惠券活动指定可用商品变化'];
                        }
                    }
                }
                
                $amount_for_coupon_discount = 0;// 计算优惠券的优惠金额，比较是否达到门槛要求
                
                foreach ($return_data['order'][$shop_id]['sku'] as $k => $sku_info) {
                    if($couponGoods){
                        if(in_array($sku_info['goods_id'], $couponGoods) && empty($sku_info['seckill_id']) && empty($sku_info['record_id'])) {
                            $amount_for_coupon_discount += $sku_info['discount_price'] * $sku_info['num'];
                        }
                    }else{
                        if(empty($sku_info['seckill_id']) && empty($sku_info['record_id'])) {
                            $amount_for_coupon_discount += $sku_info['discount_price'] * $sku_info['num'];
                        }
                    }
                    
                    if ($sku_info['full_cut_sku_amount'] > 0 && $sku_info['full_cut_sku_percent'] > 0) {
                        $amount_for_coupon_discount -= $sku_info['full_cut_sku_amount'] * $sku_info['full_cut_sku_percent'];
                    }
                }
                
                if (($coupon_db_info->coupon_type->at_least > $amount_for_coupon_discount) && ($coupon_db_info->coupon_type->coupon_genre == 2 || $coupon_db_info->coupon_type->coupon_genre == 3)) {
                    // 门槛要求类型
                    return ['result' => false, 'message' => '优惠券达不到门槛要求'];
                }
                if ($coupon_db_info->coupon_type->coupon_genre == 3) {
                    // 折扣类型
                    $coupon_amount = round($amount_for_coupon_discount * (1 - $coupon_db_info->coupon_type->discount / 10), 2);
                    
                } else {
                    // 满减无门槛类型
                    $coupon_amount = $coupon_db_info->coupon_type->money;
                }
                
                $return_data['shop'][$shop_id]['shop_should_paid_amount'] -= $coupon_amount;
                $return_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'] = $coupon_amount;
                $return_data['promotion'][$shop_id]['coupon']['shop_id'] = $coupon_db_info->coupon_type->shop_id;
                $return_data['promotion'][$shop_id]['coupon']['coupon_id'] = $coupon_db_info->coupon_id;
                $return_data['promotion'][$shop_id]['coupon']['coupon_genre'] = $coupon_db_info->coupon_type->coupon_genre;
                $return_data['promotion'][$shop_id]['coupon']['money'] = $coupon_db_info->coupon_type->money;
                $return_data['promotion'][$shop_id]['coupon']['discount'] = $coupon_db_info->coupon_type->discount;
                $return_data['promotion'][$shop_id]['coupon']['at_least'] = $coupon_db_info->coupon_type->at_least;
                
                // 计算每个sku的优惠券优惠占比
                $i = 0;//优惠券金额叠加,最后一个不计算,直接用剩下的优惠
                $skuCount = count($return_data['order'][$shop_id]['sku']);
                $partCoupon = 0;
                foreach ($return_data['order'][$shop_id]['sku'] as $ke => $sku_info) {
                    $i++;
                    if($couponGoods){
                        if (empty($sku_info['seckill_id']) && empty($order_data['record_id']) && in_array($sku_info['goods_id'], $couponGoods)) {
                            $return_data['order'][$shop_id]['sku'][$ke]['coupon_id'] = $coupon_db_info->coupon_id;
                            $percent = round(($sku_info['discount_price'] * $sku_info['num'] - $sku_info['full_cut_sku_amount'] * $sku_info['full_cut_sku_percent'])  / $amount_for_coupon_discount,2);
                            $return_data['order'][$shop_id]['sku'][$ke]['coupon_sku_percent'] = $percent;
                            $return_data['order'][$shop_id]['sku'][$ke]['coupon_sku_percent_amount'] = round($percent * $return_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'], 2);       
                            if($i != $skuCount){
                                $partCoupon += round($percent * $return_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'], 2); 
                            }else{
                                $return_data['order'][$shop_id]['sku'][$ke]['coupon_sku_percent_amount'] = round($coupon_amount - $partCoupon, 2);
                            }
                        }
                    }else{
                        if (empty($sku_info['seckill_id']) && empty($order_data['record_id'])) {
                            $return_data['order'][$shop_id]['sku'][$ke]['coupon_id'] = $coupon_db_info->coupon_id;
                            $percent = round(($sku_info['discount_price'] * $sku_info['num'] - $sku_info['full_cut_sku_amount'] * $sku_info['full_cut_sku_percent']) / $amount_for_coupon_discount,2);
                            $return_data['order'][$shop_id]['sku'][$ke]['coupon_sku_percent'] = $percent;
                            $return_data['order'][$shop_id]['sku'][$ke]['coupon_sku_percent_amount'] = round($percent * $return_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'], 2);       
                            if($i != $skuCount){
                                $partCoupon += round($percent * $return_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'], 2); 
                            }else{
                                $return_data['order'][$shop_id]['sku'][$ke]['coupon_sku_percent_amount'] = round($coupon_amount - $partCoupon, 2);
                            }
                        }
                    }
                    
                }
                unset($sku_info);
            }
            
            if ($return_data['shop'][$shop_id]['shop_should_paid_amount'] < 0) {
                $return_data['shop'][$shop_id]['shop_should_paid_amount'] = 0;
            }
            if ($shop['shop_amount'] < 0) {
                $shop['shop_amount'] = 0;
            }
            // 店铺总额不相等


            if($return_data['order_type']!=2 && $return_data['order_type']!=3 && $return_data['order_type']!=4) {//不享受任何优惠不用验证
                if (round($return_data['shop'][$shop_id]['shop_should_paid_amount'], 2) != round($shop['shop_amount'], 2)) {
                    return ['result' => false, 'message' => '店铺应付金额不匹配'];
                }
            }
        }

        //end 计算,校验
        return ['result' => true, 'data' => $return_data];
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IOrder::getOrderTradeNo()
     */
    public function getOrderTradeNo()
    {
        $order = new OrderBusiness();
        $no = $order->createOutTradeNo();
        return $no;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderDelivery()
     */
    public function orderDelivery($order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no)
    {
        
        $order_express = new OrderExpress();
        $retval = $order_express->delivery($order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no); 
        
        if ($retval) {
            $params = [
                'order_id' => $order_id,
                'order_goods_id_array' => $order_goods_id_array,
                'express_name' => $express_name,
                'shipping_type' => $shipping_type,
                'express_company_id' => $express_company_id,
                'express_no' => $express_no
            ];
            //邮件通知
            runhook('Notify', 'emailSend', ['website_id' => $this->website_id, 'shop_id' => 0, 'order_id' => $order_id, 'notify_type' => 'user', 'template_code' => 'order_deliver']);
        }
        return $retval;
    }

    /**
     * 发货助手批量发货
     * @param array $order_list
     */
    public function ordersDelivery(array $order_list)
    {
        try {
            foreach ($order_list as $v) {
                if (!empty($v['order_goods_id_array'])){
                    $this->orderDelivery($v['order_id'], implode(',', $v['order_goods_id_array']), $v['express_name'], 1, $v['express_company_id'], $v['express_no']);
                }
            }
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            return UPDATA_FAIL;
        }
    }

    /**
     * 更新物流信息
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateDelivery($id, array $data)
    {
        $order_express = new OrderExpress();
        return $order_express->updateDelivery($id, $data);
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderGoodsDelivery()
     */
    public function orderGoodsDelivery($order_id, $order_goods_id_array)
    {
        $order_goods = new OrderGoods();
        $retval = $order_goods->orderGoodsDelivery($order_id, $order_goods_id_array);
        if ($retval) {
            $params = [
                'order_id' => $order_id,
                'order_goods_id_array' => $order_goods_id_array
            ];
            hook('orderDeliverySuccess', $params);
            //邮件通知
            runhook('Notify', 'emailSend', ['website_id' => $this->website_id, 'shop_id' => 0, 'order_id' => $order_id, 'notify_type' => 'user', 'template_code' => 'order_deliver']);
        }
        return $retval;
    }

    /*
     * 订单超时关闭
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderClose()
     */
    public function orderClose($order_id, $task_mark = 0)
    {
        $order = new OrderBusiness();
        $retval = $order->orderClose($order_id, $task_mark);
        if ($retval) {
            $params = array(
                "order_id" => $order_id,
                "website_id" => $this->website_id,
                "shop_id" => $this->instance_id,
            );
            runhook("Notify", "orderCancelByTemplate", $params);
            runhook("MpMessage", "orderCloseMpByTemplate", $params);
            runhook("Notify", "orderCancelBySms", $params);
            runhook('Notify', 'emailSend', ['order_id' => $order_id, 'notify_type' => 'user', 'template_code' => 'cancel_order']);
            // 修改发票状态
            if (getAddons('invoice', $this->website_id, $this->instance_id)) {
                $invoice = new InvoiceService();
                $invoice->updateOrderStatusByOrderId($order_id, 2);//关闭发票状态
            }
        }
        return $retval;
    }
    public function channelOrderClose($order_id)
    {
        $order = new OrderBusiness();
        $retval = $order->channelOrderClose($order_id);
        if ($retval) {
            $params = array(
                "order_id" => $order_id,
                "channel_status" => 1,
                "website_id" => $this->website_id,
                "shop_id" => $this->instance_id,
            );
            runhook("Notify", "orderCancelByTemplate", $params);
            runhook("MpMessage", "orderCloseMpByTemplate", $params);
            runhook("Notify", "orderCancelBySms", $params);
            runhook('Notify', 'emailSend', ['order_id' => $order_id, 'notify_type' => 'user', 'template_code' => 'cancel_channel_order', 'is_channel' => 1]);
        }
        return $retval;
    }
    /**
     * 订单完成后自动评论
     */
    public function ordersComment($orderid, $website_id = 0, $text = ''){
        if ($website_id) {
            $websiteid = $website_id;
        } else {
            $websiteid = $this->website_id;
        }
        if(empty($orderid)){
            return;
        }
        //获取订单详情
        $order_model = new VslOrderModel();
        $order_info = $order_model->getInfo(['order_id' => $orderid]);
        $buyer_id = $order_info['buyer_id'];
        $user_model = new UserModel();
        $user_info = $user_model::get($buyer_id);
        //获取订单是否已经产生评论 //没有则订单本人自动好评
        $goods_evalute = new VslGoodsEvaluateModel();
        $condition['order_id'] = $orderid;
        $condition['uid'] = $buyer_id;
        $condition['website_id'] = $websiteid;
        $comment_count = $goods_evalute->where($condition)->count();
        if($comment_count > 0){ 
            return;
        }
        //获取该订单是否存在店铺 存在则写入店铺评价
        if(getAddons('shop', $websiteid)){
            $data_shop = array(
                'order_id' => $orderid,
                'order_no' => $order_info['order_no'],
                'website_id' => $websiteid,
                'shop_id' => $order_info['shop_id'],
                'shop_desc' => 5,
                'shop_service' => 5,
                'shop_stic' => 5,
                'add_time' => time(),
                'member_name' => $user_info['user_name'],
                'uid' => $buyer_id,
            );
            $this->addShopEvaluate($data_shop);
        }
        //存在门店，则写入门店评价
        if (getAddons('store', $websiteid, $order_info['shop_id']) && $order_info['store_id']) {
            $storeServer = new Store();
            $data_store = array(
                'order_id' => $orderid,
                'order_no' => $order_info['order_no'],
                'website_id' => $websiteid,
                'shop_id' => $order_info['shop_id'],
                'store_id' => $order_info['store_id'],
                'store_service' => 5,
                'add_time' => time(),
                'member_name' => $user_info['user_name'],
                'uid' => $buyer_id,
            );
            $storeServer->addStoreEvaluate($data_store);
        }
        //活动订单商品信息 写入商品评论
        // 查询订单项表
        $order_item = new VslOrderGoodsModel();
        $order_item_list = $order_item->where([
            'order_id' => $orderid
        ])->select();
        foreach ($order_item_list as $key_item => $v_item) {
            $orderGoods = $this->getOrderGoodsInfo($v_item['order_goods_id']);
            $data = array(
                'order_id' => $orderid,
                'order_no' => $order_info['order_no'],
                'order_goods_id' => $v_item['order_goods_id'],
                'website_id' => $orderGoods['website_id'],
                'goods_id' => $orderGoods['goods_id'],
                'goods_name' => $orderGoods['goods_name'],
                'goods_price' => $orderGoods['goods_money'],
                'goods_image' => $orderGoods['goods_picture'],
                'shop_id' => $orderGoods['shop_id'],
                'content' => $text, //内容 
                'addtime' => time(),
                'image' => '',
                'member_name' => $user_info['user_name'],
                'explain_type' => 5,
                'uid' => $buyer_id,
            );
            $dataArr[] = $data;
        }
        $result = $this->addGoodsEvaluate($dataArr, $orderid);
        //不作成功与失败处理，执行完直接返回
        return;
        
    }
    /*
     * 订单完成的函数
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderComplete()
     */
    public function orderComplete($orderid, $website_id = 0, $types = 0)
    {
        try {
            $order = new OrderBusiness();
            $retval = $order->orderComplete($orderid);
            if ($website_id) {
                $websiteid = $website_id;
            } else {
                $websiteid = $this->website_id;
            }
            $distribution_status = getAddons('distribution', $websiteid);
            $global_status = getAddons('globalbonus', $websiteid);
            $area_status = getAddons('areabonus', $websiteid);
            $team_status = getAddons('teambonus', $websiteid);
            $channel_status = getAddons('channel', $websiteid);
            $microshop_status = getAddons('microshop', $websiteid);
            $shop_status = getAddons('shop', $websiteid);
            $paygift_status = getAddons('paygift', $websiteid);
            if ($retval == 1) {
                //暂时防止立即完成的订单与支付完成的定时任务重复执行
                if($types == 1){
                    sleep(1);
                }

                if ($microshop_status == 1) {
                    //执行钩子：微店结算
                    hook('updateOrderMicroShop', ['order_id' => $orderid, 'website_id' => $websiteid]);
                }
                if ($distribution_status == 1) {
                    //执行钩子：分销佣金结算
                    hook('updateOrderCommission', ['order_id' => $orderid, 'website_id' => $websiteid]);
                }
                if ($global_status == 1) {
                    //执行钩子：全球分红结算
                    hook('updateOrderGlobalBonus', ['order_id' => $orderid, 'website_id' => $websiteid]);
                }
                if ($area_status == 1) {
                    //执行钩子：区域分红结算
                    hook('updateOrderAreaBonus', ['order_id' => $orderid, 'website_id' => $websiteid]);
                }
                if ($team_status == 1) {
                    //执行钩子：团队分红结算
                    hook('updateOrderTeamBonus', ['order_id' => $orderid, 'website_id' => $websiteid]);
                }
                if ($channel_status == 1) {
                    //渠道商佣金解冻
                    $channel = new Channel();
                    $channel->updateMemberAccountBalance($orderid, $websiteid);
                }
                // 处理店铺的账户资金
                if($shop_status == 1){
                    $this->dealShopAccount_OrderComplete("", $orderid);
                }
                //支付有礼自动领奖
                if($paygift_status==1){
                    $paygift_server = new PayGift();
                    $paygift_server->grantPrize($orderid);
                }
                // 处理平台的账户资金
                $this->updateAccountOrderComplete($orderid);
                $user_service = new User();
                // 更新会员的等级
                $order_model = new VslOrderModel();
                $order_detail = $order_model->getInfo([
                    "order_id" => $orderid
                ], "buyer_id,order_money");
                // 更新会员的成长值
                $user_service->updateUserGrowthNum(3,$order_detail["buyer_id"],0,$orderid);
                $user_service->updateUserLevel($order_detail["buyer_id"]);
                $user_service->updateUserLabel($order_detail["buyer_id"],$websiteid);
            }
        }catch (\Exception $e) {
            recordErrorLog($e);
            Log::write($e->getMessage());
            throw new \Exception($e->getMessage());
        }
        return $retval;
        // TODO Auto-generated method stub
    }

    /*
     * 订单在线支付
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderOnLinePay()
     */
    public function orderOnLinePay($order_pay_no, $pay_type, $order_id = 0, $is_yue = 0)
    {
        $order = new OrderBusiness();
        $order_model = new VslOrderModel();
        if(!strstr($order_pay_no,'DH') && !strstr($order_pay_no,'MD')){
            $retval = $order->OrderPay($order_pay_no, $pay_type, 0);
        }else{
            $retval = 1;//如果是兑换就不用更改订单状态了
        }
        try {
            if ($retval > 0) {
                if ($order_id) {
                    $order_info = $order_model->getInfo(['order_id'=>$order_id]);
                    $buyer_id = $order_info['buyer_id'];
                    if (getAddons('microshop', $order_info['website_id'], $order_info['shop_id'])) {
                        if ($order_info['order_type'] == 2 || $order_info['order_type'] == 3 || $order_info['order_type'] == 4) {
                            $config = new MicroShopService();
                            $config->becomeShopKeeper($buyer_id,$order_id);
                        }
                    }
                    $user = new User();
                    $shop_account = new ShopAccount();
                    // 平台订单积分抵扣金额
                    if($order_info["deduction_money"]>0){
                        if($order_info['order_type']==7){//判断预售订单
                            $account_model = new VslAccountRecordsModel();
                            $orderInfo = $account_model->getInfo(['type_alis_id'=>$order_id,'remark'=>'预售订单支付完成,积分抵扣金额']);
                            if(!$orderInfo){
                                // 处理平台的账户的积分抵扣金额
                                if($order_info['deduction_money']){
                                    $shop_account->updateAccountOrderPoint($order_info['deduction_money'],$order_info['website_id']);
                                }
                                $shop_account->addAccountRecords($order_info['shop_id'], $buyer_id, '订单支付完成', $order_info["deduction_money"], 33, $order_id, "预售订单支付完成,积分抵扣金额", $order_info["website_id"]);
                            }
                        }else{
                            // 处理平台的账户的积分抵扣金额
                            if($order_info['deduction_money']){
                                $shop_account->updateAccountOrderPoint($order_info['deduction_money'],$order_info['website_id']);
                            }
                            $shop_account->addAccountRecords($order_info['shop_id'], $buyer_id, '订单支付完成', $order_info["deduction_money"], 33, $order_id, "订单支付完成,积分抵扣金额", $order_info["website_id"]);
                        }
                    }
                    // 平台订单优惠金额 
                    $platform_promotion_money = $order_info["platform_promotion_money"];
                    
                    if($platform_promotion_money>0){
                        $shop_account->addAccountRecords($order_info['shop_id'], $buyer_id, '订单支付完成', $platform_promotion_money, 23, $order_id, "订单支付完成，平台优惠金额",$order_info["website_id"]);
                    }
                    //会员成长值
                    $user->updateUserGrowthNum(1,$buyer_id,0,$order_id);
                    //会员自动打标签
                    $user->updateUserLabel($buyer_id, $order_info['website_id']);
                    //处理店铺资金账户
                    if (getAddons('shop', $order_info['website_id']) == 1) {
                        $this->dealShopAccount_OrderPay('', $order_id);
                    }
                    // 处理平台的资金账户
                    $this->dealPlatformAccountOrderPay('', $order_id);
                    runhook("Notify", "orderPayBySms", array(
                        "order_id" => $order_id
                    ));
                    runhook("Notify", "orderPayByTemplate", array(
                        "order_id" => $order_id
                    ));
                    runhook("MpMessage", "orderPayMpByTemplate", array(
                        "order_id" => $order_id,
                        "website_id" => $order_info['website_id'],
                        "shop_id" => $order_info['shop_id'],
                    ));
                    if ($is_yue == 1) {
                        runhook("MpMessage", "balanceChangeByMpTemplate", array(
                            "order_id" => $order_id,
                            "website_id" => $order_info['website_id'],
                            "shop_id" => $order_info['shop_id'],
                            "type" => 1
                        ));
                    }
                    runhook('Notify', 'orderRemindBusinessBySms', [
                        "order_id" => $order_id,
                        "shop_id" => 0,
                        "website_id" => $order_info['website_id']
                    ]); // 订单提醒
                    // 邮件通知 - 用户
                    runhook('Notify', 'emailSend', [
                        'website_id' => $order_info['website_id'],
                        'shop_id' => 0,
                        'order_id' => $order_id,
                        'notify_type' => 'user',
                        'template_code' => 'pay_success'
                    ]);
                    // 邮件通知 - 卖家
                    runhook('Notify', 'emailSend', [
                        'website_id' => $order_info['website_id'],
                        'shop_id' => 0,
                        'order_id' => $order_id,
                        'notify_type' => 'business',
                        'template_code' => 'order_remind'
                    ]);
                    $order_info = $order_model->getInfo(['order_id' => $order_id], "order_id,group_id,group_record_id,verification_code,shop_id,pay_status");
                    if ($order_info['group_id']) {
                        
                        $group_server = new GroupShopping();
                        $group_server->createGroupRecord($order_id);
                    }
                    if ($order_info['verification_code']) {
                        $url = __URLS('/clerk/verify/order/'. $order_info['verification_code']);
                        //根据核销码生成核销二维码
                        $verification_qrcode = getQRcode($url, 'upload/' . $order_info['website_id'] . '/verification_code', 'verification_code_' . $order_info['verification_code']);
                        if ($order_info['shop_id']) {
                            $verification_qrcode = getQRcode($url, 'upload/' . $order_info['website_id'] . '/' . $order_info['shop_id'] . '/verification_code', 'verification_code_' . $order_info['verification_code']);
                        }
                        $store_server = new Store();

                        $store_server->orderVerCodeSet($verification_qrcode,$order_id);
                    }
                    if (getAddons('paygift', $order_info['website_id'], $order_info['shop_id'])) {
                        $paygift_server = new PayGift();
                        $paygift_server->createPaygiftRecord($order_id);
                    }
                    if($order_info['pay_status']==2){
                        // 判断是否需要在本阶段赠送积分
                        $order = new OrderBusiness();
                        $order->giveGoodsOrderPoint($order_id, 3);
                    }
                } else {
                    $condition = "out_trade_no='" . $order_pay_no . "' OR out_trade_no_presell = '" .$order_pay_no ."'";
                    $order_list = $order_model->getQuery($condition, "order_id,group_id,group_record_id,verification_code,shop_id,pay_status,money_type,order_type,website_id,shop_id", "");
                    foreach ($order_list as $k => $v) {
                        if($v['pay_status']==2 && $v['money_type']==1){
                            //修改支付状态
                            //$pay = new VslOrderPaymentModel();
                            //$pay->save(['pay_status'=>2],['out_trade_no'=>$order_pay_no]);
                            $order = new VslOrderModel();
                            $order->save(['money_type'=>2],['order_id'=>$v['order_id']]);
                        }
                    }
                    $this->dealPlatformAccountOrderPay($order_pay_no);
                    $user = new User();
                    $orders = $order_model->getInfo(['out_trade_no'=>$order_pay_no],'order_id');
                    if(empty($orders)){
                        $orders = $order_model->getInfo(['out_trade_no_presell'=>$order_pay_no],'order_id');
                    }
                    $order_id = $orders['order_id'];
                    $order_info = $order_model->getInfo(['order_id'=>$order_id]);
                    if(getAddons('shop',  $order_info['website_id']) == 1){
                        $this->dealShopAccount_OrderPay($order_pay_no);
                    }
                    $buyer_id = $order_info['buyer_id'];
                    if(getAddons('microshop',  $order_info['website_id'],  $order_info['shop_id'])){
                        if($order_info['order_type']==2|| $order_info['order_type']==3 || $order_info['order_type']==4){
                            $config = new MicroShopService();
                            $config->becomeShopKeeper($buyer_id,$order_id);
                        }
                    }
                    // 平台订单积分抵扣金额
                    if($order_info["deduction_money"]>0){
                        $shop_account = new ShopAccount();
                        if($order_info['order_type']==7){//判断预售订单
                            $account_model = new VslAccountRecordsModel();
                            $orderInfo = $account_model->getInfo(['type_alis_id'=>$order_id,'remark'=>'预售订单支付完成,积分抵扣金额']);
                            if(!$orderInfo){
                                // 处理平台的账户的积分抵扣金额
                                if($order_info['deduction_money']){
                                    $shop_account->updateAccountOrderPoint($order_info['deduction_money'],$order_info['website_id']);
                                }
                                $shop_account->addAccountRecords($order_info['shop_id'], $buyer_id, '订单支付完成', $order_info["deduction_money"], 33, $order_id, "预售订单支付完成,积分抵扣金额", $order_info["website_id"]);
                            }
                        }else{
                            // 处理平台的账户的积分抵扣金额
                            if($order_info['deduction_money']){
                                $shop_account->updateAccountOrderPoint($order_info['deduction_money'],$order_info['website_id']);
                            }
                            $shop_account->addAccountRecords($order_info['shop_id'], $buyer_id, '订单支付完成', $order_info["deduction_money"], 33, $order_id, "订单支付完成,积分抵扣金额", $order_info["website_id"]);
                        }

                    }
                    // 平台订单优惠金额 
                    $platform_promotion_money = $order_info["platform_promotion_money"];
                    if($platform_promotion_money>0){
                        $shop_account = new ShopAccount();
                        $shop_account->addAccountRecords($order_info['shop_id'], $buyer_id, '订单支付完成', $platform_promotion_money, 23, $order_id, "订单支付完成，平台优惠金额",$order_info["website_id"]);
                    }
                    
                    $user->updateUserGrowthNum(1,$buyer_id,0,$order_id);
                    $user->updateUserLabel($buyer_id, $order_info['website_id']);
                    foreach ($order_list as $k => $v) {

                        if($v['money_type'] == 0 || $v['money_type'] == 2){
                            runhook("Notify", "orderPayBySms", array(
                                "order_id" => $v["order_id"],
                                "order_type" => $order_list[0]['order_type']
                            ));
                            runhook("Notify", "orderPayByTemplate", array(
                                "order_id" => $v["order_id"],
                                "order_type" => $order_list[0]['order_type']
                            ));
                            runhook("MpMessage", "orderPayMpByTemplate", array(
                                "order_id" => $order_id,
                                "website_id" => $v['website_id'],
                                "shop_id" => $v['shop_id'],
                            ));
                            // 微信支付不用
                            if ($is_yue == 1) {
                                runhook("MpMessage", "balanceChangeByMpTemplate", array(
                                    "order_id" => $order_id,
                                    "website_id" => $v['website_id'],
                                    "shop_id" => $v['shop_id'],
                                    "type" => 1
                                ));
                            }
                            runhook('Notify', 'orderRemindBusinessBySms', [
                                "order_id" => $v["order_id"],
                                "shop_id" => 0,
                                "website_id" => $v['website_id'],
                                "order_type" => $order_list[0]['order_type']
                            ]); // 订单提醒
                            // 邮件通知 - 用户
                            runhook('Notify', 'emailSend', [
                                'website_id' => $v['website_id'],
                                'shop_id' => 0,
                                'order_id' => $v['order_id'],
                                'notify_type' => 'user',
                                'template_code' => 'pay_success',
                                "order_type" => $order_list[0]['order_type']
                            ]);
                            // 邮件通知 - 卖家
                            runhook('Notify', 'emailSend', [
                                'website_id' => $v['website_id'],
                                'shop_id' => 0,
                                'order_id' => $v['order_id'],
                                'notify_type' => 'business',
                                'template_code' => 'order_remind',
                                "order_type" => $order_list[0]['order_type']
                            ]);
                        }
                        if ($v['group_id']) {
                            $group_server = new GroupShopping();
                            $group_server->createGroupRecord($v['order_id']);
                        }
                        if ($v['verification_code']) {
                            $url = __URLS('/clerk/verify/order/' . $v['verification_code']);
                            $verification_qrcode = getQRcode($url, 'upload/' . $v['website_id'] . '/verification_code', 'verification_code_' . $v['verification_code']);
                            if ($v['shop_id']) {
                                $verification_qrcode = getQRcode($url, 'upload/' . $v['website_id'] . '/' . $v['shop_id'] . '/verification_code', 'verification_code_' . $v['verification_code']);
                            }
                            $store_server = new Store();
                            $store_server->orderVerCodeSet($verification_qrcode,$v['order_id']);
                        }
                        if (getAddons('paygift', $v['website_id'], $v['shop_id'])) {
                            $paygift_server = new PayGift();
                            $paygift_server->createPaygiftRecord($v['order_id']);
                        }
                        if($v['pay_status']==2){
                            // 判断是否需要在本阶段赠送积分
                            $order = new OrderBusiness();
                            $res = $order->giveGoodsOrderPoint($v["order_id"], 3);
                        }
                    }
                }
                
            }
        } catch (\Exception $e) {
            recordErrorLog($e);
            Log::write($e->getMessage());
        }
        return $retval;
    }

    /*
     * 订单线下支付
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderOffLinePay()
     */
    public function orderOffLinePay($order_id, $pay_type, $status)
    {
        $order = new OrderBusiness();
        $order_model = new VslOrderModel();
        if ($order_model::get($order_id)['order_status'] != 0) {
            return ['code' => -1, 'message' => '订单非未支付状态'];
        }

        //查询出该订单是否是预售的订单
        $is_presell_order = $order_model->getInfo(['order_id' => $order_id], 'presell_id, order_type, money_type, out_trade_no, out_trade_no_presell');
        if ($is_presell_order['order_type'] == 7 && !empty($is_presell_order['presell_id'])) {
            if ($is_presell_order['money_type'] == 0) {//付定金
                $order_model->save(['offline_pay_presell' => 2], ['order_id' => $order_id]);
                $new_no = $is_presell_order['out_trade_no'];
            } elseif ($is_presell_order['money_type'] == 1) {
                $new_no = $is_presell_order['out_trade_no_presell'];
                $order_model->save(['offline_pay' => 2], ['order_id' => $order_id]);
            }
        } else {
            $order_model->save(['offline_pay' => 2], ['order_id' => $order_id]);
            $new_no = $this->getOrderNewOutTradeNo($order_id);
            if (!$new_no) {
                return ['code' => -1, 'message' => '创建新的交易号失败'];
            }
        }
        $retval = $order->OrderPay($new_no, $pay_type, $status);
        if ($retval > 0) {
            $pay = new UnifyPay();
            $pay->offLinePay($new_no, $pay_type);
            $order_info = $order_model->getInfo(['order_id'=>$order_id]);
            $buyer_id = $order_info['buyer_id'];
            $user = new User();
            $user->updateUserGrowthNum(1,$buyer_id,0,$order_id);
            $user->updateUserLabel($buyer_id,$this->website_id);
            if(getAddons('microshop', $this->website_id, $this->instance_id)){
                if($order_info['order_type']==2|| $order_info['order_type']==3 || $order_info['order_type']==4){
                    $config = new MicroShopService();
                    $config->becomeShopKeeper($buyer_id,$order_id);
                }
            }
            if(getAddons('microshop', $this->website_id, $this->instance_id)){
                if($order_info['order_type']==2|| $order_info['order_type']==3 || $order_info['order_type']==4){
                    $config = new MicroShopService();
                    $config->becomeShopKeeper($buyer_id,$order_id);
                }
            }
            // 处理店铺的账户资金
            if (getAddons('shop', $this->website_id) == 1) {
                $this->dealShopAccount_OrderPay('', $order_id);
            }
            // 处理平台的资金账户
            $this->dealPlatformAccountOrderPay('', $order_id);
			if ($order_info['verification_code']) {
				$url = __URLS('/clerk/verify/order/'. $order_info['verification_code']);
				//根据核销码生成核销二维码
				$verification_qrcode = getQRcode($url, 'upload/' . $this->website_id . '/verification_code', 'verification_code_' . $order_info['verification_code']);
				if($order_info['shop_id']){
					$verification_qrcode = getQRcode($url, 'upload/' . $this->website_id . '/' . $order_info['shop_id'] . '/verification_code', 'verification_code_' . $order_info['verification_code']);
				}
				$store_server = new Store();

				$store_server->orderVerCodeSet($verification_qrcode,$order_id);
			}
            if(getAddons('paygift', $this->website_id, $order_info['shop_id'])){
                $paygift_server = new PayGift();
                $paygift_server->createPaygiftRecord($order_id);
            }
            // 判断是否需要在本阶段赠送积分
            $order = new OrderBusiness();
            $order->giveGoodsOrderPoint($order_id, 3);
//                $pay_type_name = OrderStatus::getPayType($pay_type);
            hook('orderOffLinePaySuccess', [
                'order_id' => $order_id
            ]);
            //完善之后开启
            /*   runhook('Notify', 'orderRemindBusiness', [
                 "out_trade_no" => $new_no,
                 "shop_id" => 0
             ]); // 订单提醒   */
            
        }
        return ['code' => $retval, 'message' => '操作成功'];

    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IOrder::getOrderNewOutTradeNo()
     */
    public function getOrderNewOutTradeNo($order_id)
    {
        $order_model = new VslOrderModel();
        $order = new OrderBusiness();
        $new_no = $order->createNewOutTradeNo($order_id);
        $order_model->where(['order_id' => $order_id])->update(['out_trade_no' => $new_no]);
        $pay = new UnifyPay();
        $pay->modifyNo($order_id, $new_no);
        return $new_no;
    }

    /**
     * 重新定义新的渠道商订单外部交易号
     */
    public function getChannelOrderNewOutTradeNo($order_id)
    {
        $order_model = new VslChannelOrderModel();
        $order = new OrderBusiness();
        $new_no = $order->createChannelNewOutTradeNo($order_id);
        $order_model->where(['order_id' => $order_id])->update(['out_trade_no' => $new_no]);
        $pay = new UnifyPay();
        $pay->modifyChannelNo($order_id, $new_no);
        return $new_no;
    }

    /**
     * 订单调整金额(non-PHPdoc)
     *
     * @see \data\api\IOrder::orderMoneyAdjust() 
     */
    public function orderMoneyAdjust($order_id, $order_goods_id_adjust_array, $shipping_fee)
    {
        // 调整订单
        Db::startTrans();
        try{
            $order_goods = new OrderGoods();
            $order_adjust_money = 0;//统计订单金额变化
            $retval = $order_goods->orderGoodsAdjustMoney($order_id, $order_goods_id_adjust_array, $order_adjust_money, $shipping_fee); 

            if ($retval >= 0) {
                // 计算整体商品调整金额
                //$new_no = $this->getOrderNewOutTradeNo($order_id);
                $order = new OrderBusiness();
                $retval_order = $order->orderAdjustMoney($order_id, $order_adjust_money, $shipping_fee);
                $order_model = new VslOrderModel();
                $order_money = $order_model->getInfo([
                    'order_id' => $order_id
                ], 'pay_money');
                $pay = new UnifyPay();
                $pay->modifyPayMoney(['type' => 1, 'type_alis_id' => $order_id], $order_money['pay_money']);
                Db::commit();
                return $retval_order;
            } else {
                return $retval;
            }
        } catch (\Exception $e){
            Db::rollback();
            return $e->getMessage();
        }

    }

    /**
     * 查询订单项退款信息(non-PHPdoc)
     *
     * @see \data\api\IOrder::getOrderGoodsRefundInfo()
     */
    public function getOrderGoodsRefundInfo($order_goods_id)
    {
        $order_goods = new OrderGoods();
        $order_goods_info = $order_goods->getOrderGoodsRefundDetail($order_goods_id);
        return $order_goods_info;
    }

    /**
     * 重新申请退款修改退款状态
     */
    public function updateOrderStatus($order_goods_id, $order_id)
    {
        $order_goods = new VslOrderGoodsModel();
        if ($order_goods_id) {
            $order_goods->save(['refund_status' => 0], ['order_goods_id' => $order_goods_id]);
        }
        if ($order_id) {
            $order_goods->save(['refund_status' => 0], ['order_id' => $order_id]);
        }
    }

    public function getOrderGoodsRefundInfoNew(array $condition,$types = 0)
    {
        $order_goods_model = new VslOrderGoodsModel();
        $order_goods_info = $order_goods_model::all($condition, ['goods_sku', 'goods_pic']);
        
        $refund_info['refund_max_money'] = 0;
        $refund_info['require_refund_money'] = 0;
        $refund_info['refund_point'] = 0;  
        $refund_info['refund_status'] = reset($order_goods_info)['refund_status'];
        $refund_info['order_status'] = reset($order_goods_info)->order->order_status;
        if($types == 1){
            $refund_info['order_status'] = reset($order_goods_info)['order_status'];
        }
//        $order_refund_status = OrderStatus::getRefundStatus();
//        $refund_info['status_name'] = $order_refund_status[$refund_info['refund_status']]['status_name'];
//        $refund_info['new_refund_operation'] = $order_refund_status[$refund_info['refund_status']]['new_refund_operation'];
        foreach ($order_goods_info as $k => $v) {
            // 处理发票退费问题
            $refund_tax = 0;
            if (getAddons('invoice', $this->website_id, $v['shop_id'])) {
                $invoice = new InvoiceServer();
                $invoiceConfig = $invoice->getInvoiceConfig(['website_id' =>$this->website_id, 'shop_id' => $v['shop_id']] , 'is_refund');
                if ($invoiceConfig) {
                    $is_refund = $invoiceConfig['is_refund'];
                    if ($is_refund > 0) {//退款
                        $refund_tax = $v['invoice_tax'];
                    }
                }
            }
            if($refund_info['order_status'] == 2 || $refund_info['order_status'] == 3 || $refund_info['order_status'] == 4){
                $refund_info['refund_max_money'] += $v['real_money'] - $v['shipping_fee'] + $refund_tax;
            }else{
                //货到付款订单 退款需要扣除运费
                $order_model = new VslOrderModel();
                $payment_type = $order_model->getInfo(['order_id'=>$v['order_id']],'*');
                if($payment_type['payment_type'] == 4){
                    $refund_info['refund_max_money'] += $v['real_money']-$v['shipping_fee'];
                }else{
                    $refund_info['refund_max_money'] += $v['real_money'] + $refund_tax;
                }   
                // $refund_info['refund_max_money'] += $v['real_money'];
            }
            if(in_array($refund_info['order_status'],[2,3,4,5]) && $v['deduction_freight_point']>0){
                $refund_info['refund_point'] += $v['deduction_point'] - $v['deduction_freight_point'];
            }else{
                $refund_info['refund_point'] += $v['deduction_point'];
            }
            $refund_info['require_refund_money'] += $v['refund_require_money'];
            $refund_info['refund_type'] = $v['refund_type'];
            $refund_info['refund_shipping_company'] = $v['refund_shipping_company'];
            $refund_info['refund_shipping_code'] = $v['refund_shipping_code'];
            $refund_info['refund_shipping_company_name'] = VslOrderExpressCompanyModel::get($v['refund_shipping_company'])['company_name'] ?: '';

            $temp_info['order_goods_id'] = $v['order_goods_id'];
            $temp_info['order_id'] = $v['order_id'];
            $temp_info['real_money'] = $v['real_money'];
            $temp_info['num'] = $v['num'];
            $temp_info['goods_id'] = $v['goods_id'];
            $temp_info['goods_name'] = $v['goods_name'];
            $temp_info['sku_id'] = $v['sku_id'];
            $temp_info['refund_type'] = $v['refund_type'];
            $temp_info['goods_type'] = $v['goods_type'];
            $temp_info['pic_cover'] = $v->goods_pic->pic_cover;
//            $temp_info['refund_shipping_company'] = $v['refund_shipping_company'];
//            $temp_info['refund_shipping_code'] = $v['refund_shipping_code'];
//            $temp_info['refund_shipping_company_name'] = VslOrderExpressCompanyModel::get($v['refund_shipping_company'])['company_name'] ?: '';

            if ($v->goods_sku->attr_value_items) {
                $goods_spec_value = new VslGoodsSpecValueModel();
                $spec_info = [];
                $sku_spec_info = explode(';', $v->goods_sku->attr_value_items);
                foreach ($sku_spec_info as $k_spec => $v_spec) {
                    $spec_value_id = explode(':', $v_spec)[1];
                    $sku_spec_value_info = $goods_spec_value::get($spec_value_id, ['goods_spec']);
                    $spec_info[$k_spec]['spec_value_name'] = $sku_spec_value_info['spec_value_name'];
                    $spec_info[$k_spec]['spec_name'] = $sku_spec_value_info['goods_spec']['spec_name'];
                    //$order_item_list[$key_item]['spec'][$k_spec]['spec_value_name'] = $sku_spec_value_info['spec_value_name'];
                    //$order_item_list[$key_item]['spec'][$k_spec]['spec_name'] = $sku_spec_value_info['goods_spec']['spec_name'];
                }
                $temp_info['spec'] = $spec_info;
                unset($sku_spec_value_info, $goods_sku_info, $sku_spec_info, $spec_info);
            }

            // 卖家拒绝时的理由
            $refund_goods_info = $v->order_goods_refund()->where(['action_way' => 2])->select();
            $temp_info['refund_reason'] = $v->refund_reason;

            $refund_info['shop_id'] = $v['shop_id'];
            $refund_info['order_id'] = $v->order_id;
            $refund_info['presell_id'] = $v->presell_id;
            $refund_info['reason'] = end($refund_goods_info)['reason'];
            $refund_info['refund_reason'] = $temp_info['refund_reason'];
            $refund_info['goods_list'][] = $temp_info;
            $refund_info['return_id'] = $v['return_id'];
        }

        return $refund_info;
    }

    /**
     * 查询订单的订单项列表
     *
     * @param unknown $order_id
     */
    public function getOrderGoods($order_id)
    {
        $order = new OrderBusiness();
        return $order->getOrderGoods($order_id);
    }

    /**
     * 查询订单的订单项列表
     *
     * @param unknown $order_id
     */
    public function getOrderGoodsInfo($order_goods_id)
    {
        $order = new OrderBusiness();
        $picture = new AlbumPictureModel();
        $order_goods_info = $order->getOrderGoodsInfo($order_goods_id);
        $order_goods_info['goods_picture'] = $picture->getInfo(['pic_id' => $order_goods_info['goods_picture']],'pic_cover')['pic_cover'];
        return $order_goods_info;
    }

    /**
     * 查询订单的模板消息详情
     *
     * @param unknown $order_id
     */
    public function getOrderMessage($order_id, $channel_status = NULL, $website_id = 0)
    {
        $order = new VslOrderModel();
        if (!$channel_status) {
            $order_goods = new VslOrderGoodsModel();
        } elseif ($channel_status == 1 && getAddons('channel', $website_id)) {//关闭渠道商订单
            $order = new VslChannelOrderModel();
            $order_goods = new VslChannelOrderGoodsModel();
        }
        $order_info = $order->getInfo(['order_id' => $order_id], '*');
        $address = new Address();
        $address_info = $address->getAddress($order_info['receiver_province'], $order_info['receiver_city'], $order_info['receiver_district']);
        $address_infos = $address_info . ' ' . $order_info['receiver_address'];
        $order_goods_info = $order_goods->Query(['order_id' => $order_id], 'goods_name');
        $info = [];
        $info['goods_name'] = implode(' ', $order_goods_info);
        $info['receiver_address'] = $address_infos;
        $info['order_money'] = $order_info['order_money'];
        $info['final_money'] = $order_info['final_money'];
        $info['order_no'] = $order_info['order_no'];
        $info['buyer_id'] = $order_info['buyer_id'];
        $info['order_time'] = $order_info['create_time'];
        $info['website_id'] = $order_info['website_id'];
        $info['form_id'] = $order_info['form_id'] ?: '';
        return $info;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderGoodsRefundAskfor()
     */
    public function orderGoodsRefundAskfor($order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason, $uid)
    {
        $order_goods = new OrderGoods();
        $order_model = new VslOrderModel();
        $order_info = $order_model->getInfo(['order_id' => $order_id], 'offline_pay,order_status,order_money,payment_type,order_no,shop_id,website_id,store_id');
        if($order_info['order_status']==4){
            return ['code' => -1, 'message' => '该订单已完成，不能再申请退款、退货操作'];
        }
        if($order_info['order_status']==5){
            return ['code' => -1, 'message' => '该订单已关闭！'];
        }
        if($order_info['offline_pay']==2){
            return ['code' => -1, 'message' => '该订单为后台操作付款，不能在线操作售后，请联系客服'];
        }
        if($order_info['order_status'] != 5){
            if($order_info['payment_type']==4 && ($order_info['order_status']<3 || $order_info['order_status']>4) && $order_info['order_status'] != -1){
                return ['code' => -1, 'message' => '货到付款订单，在未确认收货情况下不能申请退款退货！'];
            }
        }
        
        if(strstr($refund_require_money, 'ETH') || strstr($refund_require_money, 'EOS')){
            $refund_require_money = $order_info['order_money'];
        }
        $retval = $order_goods->orderGoodsRefundAskfor($order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason);
        if ($retval['code'] > 0) {
            //如果是门店订单,就推送订单信息到店员端
            if($order_info['store_id']) {
                $store_server = new Store();
                $store_server->orderMessagePushToClerk($order_info['order_no'],$refund_require_money,2,$order_info['store_id'],$order_info['shop_id'],$order_info['website_id']);
            }
           runhook("Notify", "orderRefoundBusinessBySms", [
                "shop_id" => 0,
                "website_id" => $this->website_id,
                "order_id" => $order_id
            ]); // 商家退款提醒
            runhook('Notify', 'emailSend', ['shop_id' => 0, 'website_id' => $this->website_id, 'notify_type' => 'business', 'order_id' => $order_id, 'template_code' => 'refund_order']);
        }
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderGoodsCancel()
     */
    public function orderGoodsCancel($order_id, $order_goods_id)
    {
        $order_goods = new OrderGoods();
        $retval = $order_goods->orderGoodsCancel($order_id, $order_goods_id);
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderGoodsReturnGoods()
     */
    public function orderGoodsReturnGoods($order_id, $order_goods_id, $refund_shipping_company, $refund_shipping_code)
    {
        $order_goods = new OrderGoods();
        $retval = $order_goods->orderGoodsReturnGoods($order_id, $order_goods_id, $refund_shipping_company, $refund_shipping_code);
        if ($retval) {
            runhook("Notify", "orderRefoundBusinessBySms", [
                "shop_id" => 0,
                "website_id" => $this->website_id,
                "order_id" => $order_id
            ]); // 商家退款提醒
            runhook('Notify', 'emailSend', ['shop_id' => 0, 'website_id' => $this->website_id, 'notify_type' => 'business', 'order_id' => $order_id, 'template_code' => 'refund_order']);
        }
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderGoodsRefundAgree()
     */
    public function orderGoodsRefundAgree($order_id, $order_goods_id,$return_id=0)
    {
        $order_goods = new OrderGoods();
        $retval = $order_goods->orderGoodsRefundAgree($order_id, $order_goods_id,$return_id);
        if ($retval == 1) {
            //同意退款退货短信提醒
            $refund_type = Session::pull('refund_type');
            $params['order_id'] = $order_id;
            $params['order_goods_id'] = $order_goods_id;
            $params['refund_type'] = $refund_type;
            $params['website_id'] = $this->website_id;
            $params['shop_id'] = $this->instance_id;
            runhook('Notify', 'agreeRefundOrReturnBySms', $params);
            if ($refund_type == 1) {
                // 仅退款
                runhook('Notify', 'emailSend', ['shop_id' => 0, 'website_id' => $this->website_id, 'refund_type' => $refund_type, 'order_goods_id' => $order_goods_id, 'notify_type' => 'user', 'template_code' => 'agree_refund']);
            } else {
                // 退货退款
                runhook('Notify', 'agreedReturnByTemplate', ['website_id' => $this->website_id, 'order_goods_id' => $order_goods_id]);
                runhook('MpMessage', 'agreedReturnByMpTemplate', $params);
                runhook('Notify', 'emailSend', ['shop_id' => 0, 'website_id' => $this->website_id, 'order_goods_id' => $order_goods_id, 'notify_type' => 'user', 'template_code' => 'agree_return']);
            }

        }
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderGoodsRefuseForever()
     */
    public function orderGoodsRefuseForever($order_id, $order_goods_id, $reason = '')
    {
        $order_goods = new OrderGoods();
        $retval = $order_goods->orderGoodsRefuseForever($order_id, $order_goods_id, $reason);
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderGoodsRefuseOnce()
     */
    public function orderGoodsRefuseOnce($order_id, $order_goods_id, $reason = '')
    {
        $order_goods = new OrderGoods();
        $retval = $order_goods->orderGoodsRefuseOnce($order_id, $order_goods_id, $reason);
        if ($retval) {
            //拒绝退款退货短信提醒
            $params['order_id'] = $order_id;
            $params['order_goods_id'] = $order_goods_id;
            $params['refusal'] = $reason;
            $params['website_id'] = $this->website_id;
            $params['type'] = 1;
            runhook('Notify', 'refundFailedByTemplate', $params);
            runhook('Notify', 'refundFailedByMpTemplate', $params);
            runhook('Notify', 'refuseRefundOrReturnBySms', $params);
            runhook('Notify', 'refuseReturnByTemplate', $params);
            runhook('MpMessage', 'refundAfterSaleByMpTemplate', $params);
            runhook('Notify', 'emailSend', ['shop_id' => 0, 'website_id' => $this->website_id, 'refuse_reason' => $reason, 'order_goods_id' => $order_goods_id, 'notify_type' => 'user']);
        }
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderGoodsConfirmRecieve()
     */
    public function orderGoodsConfirmReceive($order_id, $order_goods_id)
    {
        $order_goods = new OrderGoods();
        $retval = $order_goods->orderGoodsConfirmRecieve($order_id, $order_goods_id);
        return $retval;
    }

    /*  卖家确认退款
     * (non-PHPdoc)
     * @see \data\api\IOrder::orderGoodsConfirmRefund()
     */
    public function orderGoodsConfirmRefund($order_id, $order_goods_id,$refundtype = 0)
    {
        Db::startTrans();
        $member_account_record = new VslMemberAccountRecordsModel();
        // 已经退款的状态
        $had_refund_status_id = OrderStatus::getRefundStatus()[5]['status_id'];
        $order_goods_model = new VslOrderGoodsModel();
        $order = new VslOrderModel();
        $order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $order_goods_id], 'refund_status' => ['NEQ', $had_refund_status_id]], ['order']);
        $order_info = $order->getInfo(['order_id' => $order_id], 'bargain_id,pay_gift_status,website_id');//判断是否是砍价订单
        if (empty($order_goods_info)) {
            return ['code' => 2, 'message' => '商品已退款'];
        }

        $payment_type = reset($order_goods_info)->order->payment_type;//1微信 2支付宝 3银行卡 4货到付款 5余额支付 10线下支付 16eth支付 17eos支付  20globepay
        $order_type = reset($order_goods_info)->order->order_type;
        $order_status = reset($order_goods_info)->order->order_status;
        $shipping_status = reset($order_goods_info)->order->shipping_status;

        //货到付款，退款到余额
        if($payment_type == 4 && $order_type != 5){
            $payment_type = 5;
        }
        if($payment_type == 4 && $order_type == 5 && ($order_status == 3 || $order_status == 4 || $order_status == 5 || $order_status == -1)){
            $payment_type = 5;
        }
        $refund_real_money = 0;
        $refund_point = $refund_point2 =  0;
        $deduction_point = $deduction_point2 = 0;

        foreach ($order_goods_info as $k => $v) {
            // 处理发票退费问题
            $is_refund = 0;
            if (getAddons('invoice', $v['website_id'], $v['shop_id'])) {
                $invoice = new InvoiceService();
                $invoiceConfig = $invoice->getInvoiceConfig(['website_id' =>$v['website_id'], 'shop_id' => $v['shop_id']] , 'is_refund');
                if ($invoiceConfig) {
                    $is_refund = $invoiceConfig['is_refund'];
                }
            }
            $invoice_tax = $is_refund == 0 ? $v['invoice_tax'] : 0;
            $refund_real_money += $v['refund_require_money'] - $invoice_tax;
            //赠送的积分
            if(in_array($shipping_status,[1,2,3]) && $v['return_freight_point']){
                $refund_point += $v['give_point'] - $v['return_freight_point'];
            }else{
                $refund_point += $v['give_point'];
            }
            $refund_point2 += $v['give_point'];
            //积分抵扣的
            if(in_array($shipping_status,[1,2,3]) && $v['deduction_freight_point']){
                $deduction_point += $v['deduction_point'] - $v['deduction_freight_point'];
            }else{
                $deduction_point += $v['deduction_point'];
            }
            $deduction_point2 += $v['deduction_point'];
        }
        //退款如果订单有积分则扣除 并且是在 2-已收货，3-已支付的节点要扣除获得的积分
        $give_point_type= reset($order_goods_info)->order->give_point_type;
        if ($refund_point > 0 && ($give_point_type == 2 || $give_point_type == 3)) {
            $uid = reset($order_goods_info)->order->buyer_id;
            $website_id = reset($order_goods_info)->order->website_id;
            $order_id = reset($order_goods_info)->order->order_id;
            //判断是否真实已获得积分
            $refund_point_info = $member_account_record->getInfo(['uid' => $uid,'website_id' => $website_id,'account_type'=>1,'sign'=>1,'number'=>$refund_point2,'data_id'=>$order_id]);
            if(!empty($refund_point_info)){
                $member_mdl = new VslMemberAccountModel();
                $convert_rate = reset($order_goods_info)->order->point_convert_rate;//积分兑换金额
                $all_info = $member_mdl->getInfo(['uid' => $uid, 'website_id' => $website_id], '*');
                if (empty($all_info)) {
                    $member_all_point = 0;
                } else {
                    $member_all_point = $all_info['point'];
                }
                //更新对应会员账户
                $data_member['point'] = $member_all_point - $refund_point;
                if ($data_member['point'] < 0) {//如果会员积分不足以抵扣退款积分，则将其换成金钱
                    $data_member['point'] = 0;
                    $change_point = abs($data_member['point']);
                    //换算 积分不足兑换成金钱
                    $change_money = $change_point / $convert_rate;
                    //减掉不足的积分兑换成金钱减掉
                    $refund_real_money = $refund_real_money - $change_money;
                }
                $data = array(
                    'records_no' => getSerialNo(),
                    'account_type' => 1,
                    'uid' => $uid,
                    'sign' => 0,
                    'number' => '-'.$refund_point,
                    'from_type' => 2,//订单退还
                    'data_id' => $order_id,
                    'text' => '订单退款扣除会员获得的相应积分',
                    'create_time' => time(),
                    'website_id' => $website_id
                );
                $member_account_record->insert($data);

                //计算会员累计积分
                $data_member['member_sum_point'] = $member_all_point - $refund_point;
                $member_mdl->save($data_member, ['uid' => $uid, 'website_id' => $website_id]);
             }
        }
        if ($deduction_point > 0) {
            $uid = reset($order_goods_info)->order->buyer_id;
            $website_id = reset($order_goods_info)->order->website_id;
            $order_id = reset($order_goods_info)->order->order_id;
            //判断是否真实已抵扣积分
            $deduction_point_info = $member_account_record->getInfo(['uid' => $uid,'website_id' => $website_id,'account_type'=>1,'sign'=>0,'number'=>'-'.$deduction_point2,'data_id'=>$order_id]);
            if(!empty($deduction_point_info)){
                $member_mdl = new VslMemberAccountModel();
                $all_info = $member_mdl->getInfo(['uid' => $uid, 'website_id' => $website_id], '*');
                if (empty($all_info)) {
                    $member_all_point = 0;
                } else {
                    $member_all_point = $all_info['point'];
                }
                $data = array(
                    'records_no' => getSerialNo(),
                    'account_type' => 1,
                    'uid' => $uid,
                    'sign' => 1,
                    'number' => $deduction_point,
                    'from_type' => 2,//订单退还
                    'data_id' => $order_id,
                    'text' => '订单退款退还积分抵扣的积分',
                    'create_time' => time(),
                    'website_id' => $website_id
                );
                $member_account_record->insert($data);
                //更新对应会员账户
                $data_member = [];
                $data_member['point'] = $member_all_point + $deduction_point;
                $member_mdl->save($data_member, ['uid' => $uid, 'website_id' => $website_id]);
            }
        }
        //退礼品
        if($order_info['pay_gift_status']==1){
            $paygift_server = new PayGift();
            $paygift_server->returnPayGift($order_id);
        }
        //减掉渠道商的冻结余额
        if(getAddons('channel', $this->website_id)){
            //如果是渠道商订单则减去冻结金额。
            $channel = new Channel();
            $channel->deleteMemberFreezingAccountBalance($order_id, $order_goods_id, $this->website_id);
            sleep(1);
        }
        $refund_trade_no = date("YmdHis") . rand(100000, 999999);

        // 支付方式也是退款方式
        $presell_id= reset($order_goods_info)->order->presell_id;
        $money_type= reset($order_goods_info)->order->money_type;
        if($presell_id){//预售退款
            if($money_type == 2 && getAddons('presell', $this->website_id, $this->instance_id)){
                $first_money = reset($order_goods_info)->order->pay_money;
                $final_money = reset($order_goods_info)->order->final_money;
                if(reset($order_goods_info)->order->shipping_status == 1){//如果发货了，则运费不退了
                    $after_final_money = reset($order_goods_info)->order->final_money - reset($order_goods_info)->order->shipping_money;
                }else{
                    $after_final_money = reset($order_goods_info)->order->final_money;
                }
                //算出各个付款方式付的金额
                if($first_money>0 || $after_final_money>0){
                    $refund_first_money = round($refund_real_money * ($first_money / ($first_money + $after_final_money)), 2);
                }else{
                    $refund_first_money = 0;
                }
                $refund_final_money = $refund_real_money - $refund_first_money;
                $payment_type_presell = reset($order_goods_info)->order->payment_type_presell;
                $payment_arr = ['payment_type' => $payment_type, 'payment_type_presell' => $payment_type_presell];
                foreach($payment_arr as $key => $payment){
                    $refund_trade_no = date("YmdHis") . rand(100000, 999999);
                    $refund_real_money = 0;
                    if($key == 'payment_type'){
                        $refund_real_money = $refund_first_money;
                        $pay_money = $first_money;
                        $refund_first_money = 0;
                    }else{
                        $pay_money = $final_money;
                        $refund_real_money = $refund_final_money;
                    }
                    if($refund_real_money>0){
                        if ($payment == 5) {
                            // 退还会员的账户余额
                            $retval = $this->updateMemberAccount($order_id, reset($order_goods_info)->order->buyer_id, $refund_real_money);
                            if (!is_numeric($retval)) {
                                Db::rollback();
                                return ['code' => -1, 'message' => '余额退款失败'];
                            }
                        } else if($payment == 1 || $payment == 2){
                            // 在线原路退款（微信/支付宝）
                            $refund = $this->onlineOriginalRoadRefund($order_id, $refund_real_money, $payment, $refund_trade_no, $pay_money, $key);
                            if ($refund['is_success'] != 1) {
                                Db::rollback();
                                return ['code' => -1, 'message' => $refund['msg']];
                            }
                        }else if($payment == 3){
                            // 在线原路退款（银行卡）
                            $refund = $this->onlineOriginalRoadRefund($order_id, $refund_real_money, $payment, $refund_trade_no, $pay_money, $key);
                            if ($refund['is_success'] != 1) {
                                Db::rollback();
                                return ['code' => -1, 'message' => $refund['msg']];
                            }
                        } else if ($payment == 20) {
                            // 在线原路退款（GlobyPay）
                            $refund = $this->onlineOriginalRoadRefund($order_id, $refund_real_money, $payment, $refund_trade_no, $pay_money, $key);
                            if ($refund['is_success'] != 1) {
                                Db::rollback();
                                return ['code' => -1, 'message' => $refund['msg']];
                            }
                        }
                    }
                }
            }else{
                Db::rollback();
                return ['code' => -1, 'message' => '退款失败'];
            }

        }else{//正常流程退款
            if($refund_real_money>0){
                if ($payment_type == 5) {
                    // 退还会员的账户余额
                    $retval = $this->updateMemberAccount($order_id, reset($order_goods_info)->order->buyer_id, $refund_real_money);
                    if (!is_numeric($retval)) {
                        Db::rollback();
                        return ['code' => -1, 'message' => '余额退款失败'];
                    }
                } else if($payment_type == 1 || $payment_type == 2){
                    // 在线原路退款（微信/支付宝）
                    $refund = $this->onlineOriginalRoadRefund($order_id, $refund_real_money, $payment_type, $refund_trade_no, reset($order_goods_info)->order->pay_money);
                    if ($refund['is_success'] != 1) {
                        Db::rollback();
                        return ['code' => -1, 'message' => $refund['msg']];
                    }
                }else if($payment_type == 3){
                    // 在线原路退款（银行卡）
                    $refund = $this->onlineOriginalRoadRefund($order_id, $refund_real_money, $payment_type, $refund_trade_no, reset($order_goods_info)->order->pay_money);
                    if ($refund['is_success'] != 1) {
                        Db::rollback();
                        return ['code' => -1, 'message' => $refund['msg']];
                    }
                } else if ($payment_type ==20) {
                    // 在线原路退款（GlobyPay）
                    $refund = $this->onlineOriginalRoadRefund($order_id, $refund_real_money, $payment_type, $refund_trade_no, reset($order_goods_info)->order->pay_money);
                    if ($refund['is_success'] != 1) {
                        Db::rollback();
                        return ['code' => -1, 'message' => $refund['msg']];
                    }
                }
            }
        }
        if($refundtype == 1){
            //全单退款
            foreach ($order_goods_info as $k => $v) {
                $payment_typeg = $v->order->payment_type;
                if($payment_typeg == 4 && $order_type != 5){
                    $payment_typeg = 5;
                }
                if($payment_type == 4 && $order_type == 5 && ($order_status == 3 || $order_status == 4 || $order_status == 5 || $order_status == -1)){
                    $payment_type = 5;
                }
                $order_goods = new OrderGoods();
                $order_goods->orderGoodsConfirmRefund($order_id, $v->order_goods_id, $v->refund_require_money, $v->real_money, $refund_trade_no, $payment_typeg, '');
                // 计算店铺的账户
                $order_goods_id = $v->order_goods_id;
                if($v->shop_id > 0){
                    $shop_id = $v->shop_id;
                }else{
                    $shop_id = 0;
                }
                // 计算平台的账户
                
                $this->updateAccountOrderRefund($v->order_goods_id); //处理分销分红应用信息
    
            }
            // 计算店铺的账户
            if ($shop_id > 0) {
                $this->updateShopAccount_OrderRefund_All($order_id,$refund_real_money);
            }
        }else{
            foreach ($order_goods_info as $k => $v) {
                $payment_typeg = $v->order->payment_type;
                if($payment_typeg == 4 && $order_type != 5){
                    $payment_typeg = 5;
                }
                if($payment_type == 4 && $order_type == 5 && ($order_status == 3 || $order_status == 4 || $order_status == 5 || $order_status == -1)){
                    $payment_type = 5;
                }
                $order_goods = new OrderGoods();
                $order_goods->orderGoodsConfirmRefund($order_id, $v->order_goods_id, $v->refund_require_money, $v->real_money, $refund_trade_no, $payment_typeg, '');
               // 计算店铺的账户
                if ($v->shop_id > 0) {
                    $this->updateShopAccount_OrderRefund($v->order_goods_id);
                }
                // 计算平台的账户
                
                $this->updateAccountOrderRefund($v->order_goods_id);
    
            }
        }
        
        $website_id = reset($order_goods_info)->order->website_id;
        if(getAddons('channel', $website_id)){
            //判断订单是否是渠道商零售相关的,是的话就删除这条零售记录
            $cosr_mdl = new VslChannelOrderSkuRecordModel();
            if($refundtype == 1) {
                //整单退
                $channel_retail_info = $cosr_mdl->getInfo(['order_id' => $order_id], "*");
                if ($channel_retail_info) {
                    $cosr_mdl->where(['order_id' => $order_id])->delete();
                }
            }else{
                if (is_int($order_goods_id) || is_string($order_goods_id)) {
                    $goods_id = $order_goods_model->getInfo(['order_goods_id' => $order_goods_id],'goods_id');
                    $channel_retail_info = $cosr_mdl->getInfo(['order_id' => $order_id,'goods_id' => $goods_id['goods_id']], "*");
                    if ($channel_retail_info) {
                        $cosr_mdl->where(['order_id' => $order_id,'goods_id' => $goods_id['goods_id']])->delete();
                    }
                } else {
            foreach ($order_goods_id as $k => $v) {
                $goods_id = $order_goods_model->getInfo(['order_goods_id' => $v],'goods_id');
                $channel_retail_info = $cosr_mdl->getInfo(['order_id' => $order_id,'goods_id' => $goods_id['goods_id']], "*");
            if($channel_retail_info){
                    $cosr_mdl->where(['order_id' => $order_id,'goods_id' => $goods_id['goods_id']])->delete();
                        }
                    }
                }
            }
        }
        Db::commit();
        //确认退款提醒
        $params['website_id'] = $order_info['website_id'] ?: $this->website_id;
        $params['shop_id'] = 0;
        $params['order_id'] = $order_id;
        $params['uid'] = reset($order_goods_info)->order->buyer_id;
        $params['order_goods_id'] = $order_goods_id;
        $params['notify_type'] = 'user';
        $params['template_code'] = 'return_success';
        $params['refund_money'] = $refund_real_money;
        runhook('Notify', 'confirmRefundBySms', $params);
        runhook('Notify', 'emailSend', $params);
        runhook('Notify', 'refundSuccessByTemplate', $params);
        runhook('MpMessage', 'refundAfterSaleByMpTemplate', $params);
        return ['code' => 1, 'message' => '退款成功'];
    }
    public function orderGoodsConfirmRefunds($order_id, $password)
    {
        if (!isset($password['ethPassword']) && !isset($password['eosPassword'])) {
            $password = isset($password[0]) ? $password[0] : $password;
        }

        $order_goods_model = new VslOrderGoodsModel();
        $order_goods_info = $order_goods_model::all(['order_id' => $order_id]);
        $order_goods_id = [];
        foreach ($order_goods_info as $v) {
            $order_goods_id[] = $v->order_goods_id;
        }
        // 已经退款的状态
        $had_refund_status_id = OrderStatus::getRefundStatus()[5]['status_id'];
        $order_goods_model = new VslOrderGoodsModel();
        $order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $order_goods_id], 'refund_status' => ['NEQ', $had_refund_status_id]], ['order']);
        if (empty($order_goods_info)) {
            return ['code' => 2, 'message' => '商品已退款'];
        }
        $order = new VslOrderModel();
        $money= 0;
        $order_info = $order->getInfo(['order_id'=>$order_id],'*');
        if(($order_info['order_status']==-1 && $order_info['shipping_money'] && $order_info['shipping_status']>=1) || ($order_info['order_status']==2 && $order_info['shipping_money'])){
            $money = $order_info['shipping_money'];
        }
        if ($order_info['presell_id']) {
            if ($order_info['payment_type'] == 16 && $order_info['payment_type_presell'] == 16) {
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info1 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no'],'from_type'=>4]);
                $eth_info2 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no_presell'],'from_type'=>4]);
                $coin_cash = floatval($eth_info1['cash'] + $eth_info2['cash']);
                $coin_gas = floatval($eth_info1['gas'] + $eth_info2['gas']);
                $result2 = $block->ethPayRefund($order_info['out_trade_no'],$order_info['out_trade_no_presell'], $password,$money,$coin_cash,$coin_gas);
                if($result2['code']==200){
                    $order = new VslOrderModel();
                    $order->save(['coin_after'=>1],['order_id'=>$order_id]);
                }else if($result2['code']==1000){
                }else{
                    return ['code' => -1, 'message' => $result2['msg']];
                }
                return ['code' => 1, 'message' => '链上处理中'];
            }
            if($order_info['payment_type']==16 && $order_info['payment_type_presell']==17){
                if (!isset($password['ethPassword']) && !isset($password['eosPassword'])) {
                    return ['code' => -1, 'message' => LACK_OF_PARAMETER];
                }
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info1 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no'],'from_type'=>4]);
                $eth_info2 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no_presell'],'from_type'=>8]);
                $result1 = $block->ethPayRefund($order_info['out_trade_no'],$order_info['out_trade_no'], $password['ethPassword'],0,$eth_info1['cash'],$eth_info1['gas']);
                $result2 = $block->eosPayRefund($order_info['out_trade_no_presell'],$order_info['out_trade_no_presell'], $password['eosPassword'],$money,$eth_info2['cash']);

                if(($result1['code']==200 || $result1['code'] == 1012) && ($result2['code']==200 || $result2['code'] == 1012)){
                    $order = new VslOrderModel();
                    $order->save(['coin_after'=> 1 ],['order_id'=>$order_id]);
                }else{
                    if ($result1['code'] != 200 && $result1['code'] != 1012) {
                        return ['code' => -1, 'message' => $result1['msg'], 'msgs' => 123];
                    }
                    if ($result2['code'] != 200 && $result2['code'] != 1012) {
                        return ['code' => -1, 'message' => $result2['msg'], 'msgs' => 456];
                    }
                }
                return ['code' => 1, 'message' => '链上处理中'];
            }
            if($order_info['payment_type']==17 && $order_info['payment_type_presell']==16){
                if (!isset($password['ethPassword']) && !isset($password['eosPassword'])) {
                    return ['code' => -1, 'message' => LACK_OF_PARAMETER];
                }
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info1 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no'],'from_type'=>8]);
                $eth_info2 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no_presell'],'from_type'=>4]);
                $result1 = $block->eosPayRefund($order_info['out_trade_no'],$order_info['out_trade_no'], $password['eosPassword'],0,$eth_info1['cash']);
                $result2 = $block->ethPayRefund($order_info['out_trade_no_presell'],$order_info['out_trade_no_presell'], $password['ethPassword'],$money,$eth_info2['cash'],$eth_info2['gas']);

                if(($result1['code']==200 || $result1['code'] == 1012) && ($result2['code']==200 || $result2['code'] == 1012)){
                    $order = new VslOrderModel();
                    $order->save(['coin_after'=> 1 ],['order_id'=>$order_id]);
                }else{
                    if ($result1['code'] != 200 && $result1['code'] != 1012) {
                        return ['code' => -1, 'message' => $result1['msg'], 'msgs' => 123];
                    }
                    if ($result2['code'] != 200 && $result2['code'] != 1012) {
                        return ['code' => -1, 'message' => $result2['msg'], 'msgs' => 456];
                    }
                }
                return ['code' => 1, 'message' => '链上处理中'];
            }
            if ($order_info['payment_type'] == 17 && $order_info['payment_type_presell'] == 17) {
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info1 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no'],'from_type'=>8]);
                $eth_info2 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no_presell'],'from_type'=>8]);
                $result1 = $block->eosPayRefund($order_info['out_trade_no'],$order_info['out_trade_no'], $password,0,$eth_info1['cash']);
                if($result1['code']==200){
                    $order->save(['coin_after'=>1],['order_id'=>$order_id]);
                }else if($result1['code']==1000){
                }else{
                    return ['code' => -1, 'message' => $result1['msg']];
                }
                $result2 = $block->eosPayRefund($order_info['out_trade_no_presell'],$order_info['out_trade_no_presell'], $password,$money,$eth_info2['cash']);
                if($result2['code']==200){
                    $order = new VslOrderModel();
                    $order->save(['coin_after'=>1],['order_id'=>$order_id]);
                }else if($result2['code']==1000){
                }else{
                    return ['code' => -1, 'message' => $result2['msg']];
                }
                return ['code' => 1, 'message' => '链上处理中'];
            }
            if ($order_info['payment_type'] == 16 && $order_info['payment_type_presell'] != 16 && $order_info['payment_type_presell'] != 17) {
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info1 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no'],'from_type'=>4]);
                $result = $block->ethPayRefund($order_info['order_no'],$order_info['out_trade_no'], $password,0,$eth_info1['cash'],$eth_info1['gas']);//付定金不应该传运费
                if($result['code']==200){
                    $order->save(['coin_after'=>1],['order_id'=>$order_id]);
                    return ['code' => 1, 'message' => '链上处理中'];
                }else{
                    return ['code' => -1, 'message' => $result['msg']];
                }
            }
            if ($order_info['payment_type'] == 17 && $order_info['payment_type_presell'] != 16 && $order_info['payment_type_presell'] != 17) {
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info2 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no'],'from_type'=>8]);
                $result = $block->eosPayRefund($order_info['order_no'],$order_info['out_trade_no'], $password,0,$eth_info2['cash']);//付定金不应该传运费
                if($result['code']==200){
                    $order->save(['coin_after'=>1],['order_id'=>$order_id]);
                    return ['code' => 1, 'message' => '链上处理中'];
                }else{
                    return ['code' => -1, 'message' => $result['msg']];
                }
            }
            if ($order_info['payment_type_presell'] == 16 && $order_info['payment_type'] != 16 && $order_info['payment_type'] != 17) {
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info2 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no_presell'],'from_type'=>4]);
                $result = $block->ethPayRefund($order_info['out_trade_no_presell'],$order_info['out_trade_no_presell'], $password,$money,$eth_info2['cash'],$eth_info2['gas']);
                if($result['code']==200){
                    $order->save(['coin_after'=>1],['order_id'=>$order_id]);
                    return ['code' => 1, 'message' => '链上处理中'];
                }else{
                    return ['code' => -1, 'message' => $result['msg']];
                }
            }
            if ($order_info['payment_type_presell'] == 17 && $order_info['payment_type'] != 16 && $order_info['payment_type'] != 17) {
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info2 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no_presell'],'from_type'=>8]);//4为eth支付,8为eos支付
                $result = $block->eosPayRefund($order_info['out_trade_no_presell'],$order_info['out_trade_no_presell'], $password,$money,$eth_info2['cash']);
                if($result['code']==200){
                    $order->save(['coin_after'=>1],['order_id'=>$order_id]);
                    return ['code' => 1, 'message' => '链上处理中'];
                }else{
                    return ['code' => -1, 'message' => $result['msg']];
                }
            }
        } else {
            if ($order_info['payment_type'] == 16) {
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info1 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no'],'from_type'=>4]);
                $result = $block->ethPayRefund($order_info['order_no'],$order_info['out_trade_no'], $password,$money,$eth_info1['cash'],$eth_info1['gas']);//付定金不应该传运费
                if($result['code']==200){
                    $order->save(['coin_after'=>1],['order_id'=>$order_id]);
                    return ['code' => 1, 'message' => '链上处理中'];
                }else{
                    return ['code' => -1, 'message' => $result['msg']];
                }
            }
            if ($order_info['payment_type'] == 17) {
                $block = new Block();
                $member_account_record = new VslBlockChainRecordsModel();
                $eth_info2 = $member_account_record->getInfo(['data_id'=>$order_info['out_trade_no'],'from_type'=>8]);
                $result = $block->eosPayRefund($order_info['order_no'],$order_info['out_trade_no'], $password,$money,$eth_info2['cash']);
                if($result['code']==200){
                    $order->save(['coin_after'=>1],['order_id'=>$order_id]);
                    return ['code' => 1, 'message' => '链上处理中'];
                }else{
                    return ['code' => -1, 'message' => $result['msg']];
                }
            }
        }
    }
    /**
     * /**
     * 在线原路退款（虚拟币退款回调）
     */
    public function orderRefundBack($order_id)
    {
        try{
            Db::startTrans();
            $order_goods_model = new VslOrderGoodsModel();
            $order_goods_info = $order_goods_model::all(['order_id' => $order_id]);
            $order_goods_id = [];
            foreach ($order_goods_info as $v) {
                $order_goods_id[] = $v->order_goods_id;
            }
            $payment_type = reset($order_goods_info)->order->payment_type;
            $shipping_status = reset($order_goods_info)->order->shipping_status;
            // 已经退款的状态
            $had_refund_status_id = OrderStatus::getRefundStatus()[5]['status_id'];
            $order_goods_model = new VslOrderGoodsModel();
            $order = new VslOrderModel();
            $order_goods_info = $order_goods_model::all(['order_goods_id' => ['IN', $order_goods_id], 'refund_status' => ['NEQ', $had_refund_status_id]], ['order']);
            $order_info = $order->getInfo(['order_id'=>$order_id],'bargain_id,pay_gift_status,website_id');//判断是否是砍价订单
            if (empty($order_goods_info)) {
                return ['code' => 2, 'message' => '商品已退款'];
            }
            $website_id = $order_info['website_id'];
            $refund_real_money = 0;
            $refund_point = $refund_point2 = 0;
            $deduction_point = $deduction_point2 = 0;
            foreach ($order_goods_info as $k => $v) {
                $refund_real_money += $v['refund_require_money'];
                //赠送的积分
                if(in_array($shipping_status,[1,2,3]) && $v['return_freight_point']>0){
                    $refund_point += $v['give_point'] - $v['return_freight_point'];
                }else{
                    $refund_point += $v['give_point'];
                }
                $refund_point2 += $v['give_point'];
                //积分抵扣的
                if(in_array($shipping_status,[1,2,3]) && $v['deduction_freight_point']>0){
                    $deduction_point += $v['deduction_point'] - $v['deduction_freight_point'];
                }else{
                    $deduction_point += $v['deduction_point'];
                }
                $deduction_point2 += $v['deduction_point'];
            }
            $member_account_record = new VslMemberAccountRecordsModel();
            //退款如果订单有积分则扣除 并且是在 2-已收货，3-已支付的节点要扣除获得的积分
            $give_point_type= reset($order_goods_info)->order->give_point_type;
            if ($refund_point > 0 && ($give_point_type == 2 || $give_point_type == 3)) {
                $uid = reset($order_goods_info)->order->buyer_id;
                $website_id = reset($order_goods_info)->order->website_id;
                $order_id = reset($order_goods_info)->order->order_id;
                //判断是否真实已获得积分
                $refund_point_info = $member_account_record->getInfo(['uid' => $uid,'website_id' => $website_id,'account_type'=>1,'sign'=>1,'number'=>$refund_point2,'data_id'=>$order_id]);
                if(!empty($refund_point_info)){
                    $member_mdl = new VslMemberAccountModel();
                    $convert_rate = reset($order_goods_info)->order->point_convert_rate;//积分兑换金额
                    $all_info = $member_mdl->getInfo(['uid' => $uid, 'website_id' => $website_id], '*');
                    if (empty($all_info)) {
                        $member_all_point = 0;
                    } else {
                        $member_all_point = $all_info['point'];
                    }
                    $data = array(
                        'records_no' => getSerialNo(),
                        'account_type' => 1,
                        'uid' => $uid,
                        'sign' => 0,
                        'number' => '-'.$refund_point,
                        'from_type' => 2,//订单退还
                        'data_id' => $order_id,
                        'text' => '订单退款扣除会员获得的相应积分',
                        'create_time' => time(),
                        'website_id' => $website_id
                    );
                    $member_account_record->insert($data);
                    //更新对应会员账户
                    $data_member['point'] = $member_all_point - $refund_point;
                    if ($data_member['point'] < 0) {//如果会员积分不足以抵扣退款积分，则将其换成金钱
                        $data_member['point'] = 0;
                        $change_point = abs($data_member['point']);
                        //换算 积分不足兑换成金钱
                        $change_money = $change_point / $convert_rate;
                        //减掉不足的积分兑换成金钱减掉
                        $refund_real_money = $refund_real_money - $change_money;
                    }
                    //计算会员累计积分
                    $data_member['member_sum_point'] = $member_all_point - $refund_point;
                    $member_mdl->save($data_member, ['uid' => $uid, 'website_id' => $website_id]);
                }
            }
            if ($deduction_point > 0) {
                $uid = reset($order_goods_info)->order->buyer_id;
                $website_id = reset($order_goods_info)->order->website_id;
                $order_id = reset($order_goods_info)->order->order_id;
                //判断是否真实已抵扣积分
                $deduction_point_info = $member_account_record->getInfo(['uid' => $uid,'website_id' => $website_id,'account_type'=>1,'sign'=>0,'number'=>'-'.$deduction_point2,'data_id'=>$order_id]);
                if(!empty($deduction_point_info)){
                    $member_mdl = new VslMemberAccountModel();
                    $all_info = $member_mdl->getInfo(['uid' => $uid, 'website_id' => $website_id], '*');
                    if (empty($all_info)) {
                        $member_all_point = 0;
                    } else {
                        $member_all_point = $all_info['point'];
                    }
                    $data = array(
                        'records_no' => getSerialNo(),
                        'account_type' => 1,
                        'uid' => $uid,
                        'sign' => 1,
                        'number' => $deduction_point,
                        'from_type' => 2,//订单退还
                        'data_id' => $order_id,
                        'text' => '订单退款退还积分抵扣的积分',
                        'create_time' => time(),
                        'website_id' => $website_id
                    );
                    $member_account_record->insert($data);
                    //更新对应会员账户
                    $data_member = [];
                    $data_member['point'] = $member_all_point + $deduction_point;
                    $member_mdl->save($data_member, ['uid' => $uid, 'website_id' => $website_id]);
                }
            }
            //退礼品
            if($order_info['pay_gift_status']==1){
                $paygift_server = new PayGift();
                $paygift_server->returnPayGift($order_id);
            }
            $refund_trade_no = date("YmdHis") . rand(100000, 999999);
            $presell_id= reset($order_goods_info)->order->presell_id;
            $money_type= reset($order_goods_info)->order->money_type;
            // 支付方式也是退款方式
            if($presell_id){//预售退款
                if($money_type == 2 && getAddons('presell', $website_id, 0)){
                    $first_money = reset($order_goods_info)->order->pay_money;
                    $final_money = reset($order_goods_info)->order->final_money;
                    if(reset($order_goods_info)->order->shipping_status == 1){//如果发货了，则运费不退了
                        $after_final_money = reset($order_goods_info)->order->final_money - reset($order_goods_info)->order->shipping_money;
                    }else{
                        $after_final_money = reset($order_goods_info)->order->final_money;
                    }
                    //算出各个付款方式付的金额
                    if($first_money>0 || $after_final_money>0){
                        $refund_first_money = round($refund_real_money * ($first_money / ($first_money + $after_final_money)), 2);
                    }else{
                        $refund_first_money = 0;
                    }
                    $refund_final_money = $refund_real_money - $refund_first_money;
                    $payment_type_presell = reset($order_goods_info)->order->payment_type_presell;
                    $payment_arr = ['payment_type' => $payment_type, 'payment_type_presell' => $payment_type_presell];
                    foreach($payment_arr as $key => $payment){
                        $refund_trade_no = date("YmdHis") . rand(100000, 999999);
                        if($key == 'payment_type'){
                            $refund_real_money = $refund_first_money;
                            $pay_money = $first_money;
                        }else{
                            $pay_money = $final_money;
                            $refund_real_money = $refund_final_money;
                        }
                        if($refund_real_money>0){
                            if ($payment == 5) {
                                // 退还会员的账户余额
                                $retval = $this->updateMemberAccount($order_id, reset($order_goods_info)->order->buyer_id, $refund_real_money);
                                if (!is_numeric($retval)) {
                                    Db::rollback();
                                    return ['code' => -1, 'message' => '余额退款失败'];
                                }
                            } else if($payment == 1 || $payment == 2){
                                // 在线原路退款（微信/支付宝）
                                $refund = $this->onlineOriginalRoadRefund($order_id, $refund_real_money, $payment, $refund_trade_no, $pay_money, $key);
                                if ($refund['is_success'] != 1) {
                                    Db::rollback();
                                    return ['code' => -1, 'message' => $refund['msg']];
                                }
                            }else if($payment == 3){
                                // 在线原路退款（银行卡）
                                $refund = $this->onlineOriginalRoadRefund($order_id, $refund_real_money, $payment, $refund_trade_no, $pay_money, $key);
                                if ($refund['is_success'] != 1) {
                                    Db::rollback();
                                    return ['code' => -1, 'message' => $refund['msg']];
                                }
                            }
                        }
                    }
                }else{
                    Db::rollback();
                    return ['code' => -1, 'message' => '退款失败'];
                }
            }
            foreach ($order_goods_info as $k => $v) {
                $order_goods = new OrderGoods();
                $order_goods->orderGoodsConfirmRefund($order_id, $v->order_goods_id, $v->refund_require_money, $v->real_money, $refund_trade_no, $v->order->payment_type, '');
                // 计算店铺的账户
                if ($v->shop_id > 0) {
                    $this->updateShopAccount_OrderRefund($v->order_goods_id);
                }
                // 计算平台的账户
                $this->updateAccountOrderRefund($v->order_goods_id);
            }
            //判断订单是否是渠道商零售相关的,是的话就删除这条零售记录
            $cosr_mdl = new VslChannelOrderSkuRecordModel();
            $channel_retail_info = $cosr_mdl->getInfo(['order_id'=>$order_id], "*");
            if($channel_retail_info){
                $cosr_mdl->where(['order_id'=>$order_id])->delete();
            }
            Db::commit();
            //确认退款提醒
            $params['website_id'] = $order_info['website_id'] ?: $this->website_id;
            $params['shop_id'] = 0;
            $params['order_id'] = $order_id;
            $params['uid'] = reset($order_goods_info)->order->buyer_id;
            $params['order_goods_id'] = $order_goods_id;
            $params['notify_type'] = 'user';
            $params['template_code'] = 'return_success';
            $params['refund_money'] = $refund_real_money;
            runhook('Notify', 'confirmRefundBySms', $params);
            runhook('Notify', 'emailSend', $params);
            runhook('Notify', 'refundSuccessByTemplate', $params);
            runhook('MpMessage', 'refundAfterSaleByMpTemplate', $params);
            return ['code' => 1, 'message' => '退款成功'];
        }catch(\Exception $e){
            Db::rollback();
        }
    }
    /**
     * /**
     * 在线原路退款（微信、支付宝）
     * @param 订单id $order_id
     * @param 退款金额 $refund_fee
     * @param 退款方式（1：微信，2：支付宝，10：线下） $refund_way
     * @param 退款交易号 $refund_trade_no
     * @param 订单总金额 $total_fee
     * @return number[]|string[]|\data\extend\weixin\成功时返回，其他抛异常|mixed[]
     */
    private function onlineOriginalRoadRefund($order_id, $refund_fee, $refund_way, $refund_trade_no, $total_fee, $is_presell_pay = 'payment_type')
    {
        // 1.根据订单id查询外部交易号
        $order_model = new VslOrderModel();
        $out_trade_no = $order_model->getInfo([
            'order_id' => $order_id
        ], "out_trade_no, out_trade_no_presell,website_id");
        if($is_presell_pay == 'payment_type'){
            $out_trade_no['out_trade_no'] = $out_trade_no['out_trade_no'];
        }elseif($is_presell_pay == 'payment_type_presell'){
            $out_trade_no['out_trade_no'] = $out_trade_no['out_trade_no_presell'];
        }
        if($refund_fee == 0){
            return array(
                "is_success" => 0,
                'msg' => "退款金额不能为0"
            );
        }
        // 2.根据外部交易号查询trade_no（交易号）支付宝支付会返回一个交易号，微信传空
//        $vsl_order_payment_model = new VslOrderPaymentModel();
//        $trade_no = $vsl_order_payment_model->getInfo([
//            "out_trade_no" => $out_trade_no['out_trade_no']
//        ], 'trade_no');
        // 3.根据用户选择的退款方式，进行不同的原路退款操作
        if ($refund_way == 1) {
            // 微信退款
            $weixin_pay = new WeiXinPay();
            $retval = $weixin_pay->setWeiXinRefund($refund_trade_no, $out_trade_no['out_trade_no'], $refund_fee * 100, $total_fee * 100, $out_trade_no['website_id']);
        } elseif ($refund_way == 2) {
            // 支付宝退款
            $ali_pay = new UnifyPay();
            $retval = $ali_pay->aliPayNewRefund($refund_trade_no, $out_trade_no['out_trade_no'], $refund_fee);
            $result = json_decode(json_encode($retval), TRUE);
            if ($result['code'] == '10000' && $result['msg'] == 'Success') {
                $retval = array(
                    "is_success" => 1,
                    'msg' => ""
                );
            } else {
                $retval = array(
                    "is_success" => 0,
                    'msg' => $result['msg']
                );
            }

        } elseif($refund_way == 3){
            $tl = new tlPay();
            $order_payment = new VslOrderPaymentModel();
            $trade_no = $order_payment->getInfo(['out_trade_no'=>$out_trade_no['out_trade_no']],'trade_no')['trade_no'];
            $retval = $tl->tlRefund($out_trade_no['out_trade_no'],$refund_fee * 100,$out_trade_no['website_id'],$trade_no);
            if ( $retval['retcode'] == 'SUCCESS' && $retval['trxstatus'] == '0000') {
                $retval = array(
                    "is_success" => 1,
                    'msg' => ""
                );
            } else {
                $retval = array(
                    "is_success" => 0,
                    'msg' => $retval['errmsg']
                );
            }
        } elseif($refund_way == 20) {
            // GlobePay退款 
            $globepay = new GlobePay();
            $retval = $globepay->setGpRefund($refund_trade_no, $out_trade_no['out_trade_no'], $refund_fee, $total_fee, $out_trade_no['website_id']);
			if ($retval['result_code'] == 'SUCCESS') {
                $retval = array(
                    "is_success" => 1,
                    'msg' => ""
                );
            } else {
                $retval = array(
                    "is_success" => 0,
                    'msg' => $retval['return_msg']
                );
            }
        } else {

            // 线下操作，直接通过
            $retval = array(
                "is_success" => 1,
                'msg' => ""
            );
        }

        return $retval;
    }

    /**
     * 在线原路退款（账户余额）
     *
     * @param unknown $goods_sku_list
     */
    public function updateMemberAccount($order_id, $uid, $refund_real_money)
    {

        $member_account_record = new VslMemberAccountRecordsModel();
        $member_account_record->startTrans();
        try {
            if($refund_real_money == 0){
                return "退款金额不能为0";
            }
            $member = new VslMemberAccountModel();
            $account = $member->getInfo(['uid' => $uid], '*');
            if ($account) {
                $balance = $account['balance'] + $refund_real_money;
                $member->save(['balance' => $balance], ['uid' => $uid]);
                //添加会员账户流水
                $data = array(
                    'records_no' => 'Br' . getSerialNo(),
                    'account_type' => 2,
                    'uid' => $uid,
                    'sign' => 0,
                    'number' => $refund_real_money,
                    'point'=>$account['point'],
                    'balance'=>$balance,
                    'from_type' => 2,
                    'data_id' => $order_id,
                    'text' => '订单余额退款，会员可用余额增加',
                    'create_time' => time(),
                    'website_id' => $account['website_id']
                );
                $res = $member_account_record->save($data);
            }
            
            $member_account_record->commit();
            return $res;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $member_account_record->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 获取对应sku列表价格
     *
     * @param unknown $goods_sku_list
     */
    public function getGoodsSkuListPrice($goods_sku_list)
    {
        $goods_preference = new GoodsPreference();
        $money = $goods_preference->getGoodsSkuListPrice($goods_sku_list);
        return $money;
    }

    /**
     *
     * 确认收货
     * @see \data\api\IOrder::OrderTakeDelivery()
     */
    public function OrderTakeDelivery($order_id)
    {
        $order = new OrderBusiness();
        $res = $order->OrderTakeDelivery($order_id);
        //发送确认收货消息
        runhook('Notify', 'orderCompleteBySms', ['order_id' => $order_id]);
        runhook('Notify', 'emailSend', ['shop_id' => 0, 'website_id' => $this->website_id, 'order_id' => $order_id, 'notify_type' => 'user', 'template_code' => 'confirm_order']);
//        $config = new Config();
//        $config_info = $config->getConfigbyWebsiteId($this->website_id, 0, 'ORDER_DELIVERY_COMPLETE_TIME');
//        if($config_info['value'] == 0){
//            // 立即完成订单
//            $this->orderComplete($order_id);
//        }
        return $res;
    }

    /**
     * 删除购物车的商品
     * @param array $condition
     */
    public function deleteCartNew(array $condition)
    {
        $cart_model = new VslCartModel();
        $cart_model::destroy($condition);
        unset($_SESSION['user_cart'], $_SESSION['order_tag']);
    }

    /**
     * 删除门店购物车的商品
     * @param array $condition
     */
    public function deleteStoreCartNew(array $condition)
    {
        $cart_model = new VslStoreCartModel();
        $cart_model::destroy($condition);

        unset($_SESSION['user_cart'], $_SESSION['order_tag']);
    }

    /**
     *
     * @ERROR!!!
     *
     * @see \data\api\IOrder::getOrderCount()
     */
    public function getOrderCount($condition)
    {
        $order = new VslOrderModel();
        $count = $order->where($condition)->count();
        return $count;
    }

    public function getMemberOrderCount($condition)
    {
        $order = new VslOrderModel();
        $list = $order->where($condition)->group('buyer_id')->select();
        return count($list);
    }

    public function getOrderMoneySum($condition, $filed)
    {
        $order_model = new VslOrderModel();
        $money_sum = $order_model->where($condition)->sum($filed);
        return $money_sum;
    }

    /**
     * 获取具体配送状态信息
     *
     * @param unknown $shipping_status_id
     * @return Ambigous <NULL, multitype:string >
     */
    public static function getShippingInfo($shipping_status_id)
    {
        $shipping_status = OrderStatus::getShippingStatus();
        $info = null;
        foreach ($shipping_status as $shipping_info) {
            if ($shipping_status_id == $shipping_info['shipping_status']) {
                $info = $shipping_info;
                break;
            }
        }
        return $info;
    }

    /**
     * 获取具体支付状态信息
     *
     * @param unknown $pay_status_id
     * @return multitype:multitype:string |string
     */
    public static function getPayStatusInfo($pay_status_id)
    {
        $pay_status = OrderStatus::getPayStatus();
        $info = null;
        foreach ($pay_status as $pay_info) {
            if ($pay_status_id == $pay_info['pay_status']) {
                $info = $pay_info;
                break;
            }
        }
        return $info;
    }

    /**
     * 获取订单各状态数量
     */
    public static function getOrderStatusNum($condition = '')
    {
        $order = new VslOrderModel();
        $orderStatusNum['all'] = $order->where($condition)->count(); // 全部
        $condition['order_status'] = 0; // 待付款
        $orderStatusNum['wait_pay'] = $order->where($condition)->count();
        $condition['order_status'] = 1; // 待发货
        $orderStatusNum['wait_delivery'] = $order->where($condition)->count();
        $condition['order_status'] = 2; // 待收货
        $orderStatusNum['wait_recieved'] = $order->where($condition)->count();
        $condition['order_status'] = 3; // 已收货
        $orderStatusNum['recieved'] = $order->where($condition)->count();
        $condition['order_status'] = 4; // 交易成功
        $orderStatusNum['success'] = $order->where($condition)->count();
        $condition['order_status'] = 5; // 已关闭
        $orderStatusNum['closed'] = $order->where($condition)->count();
        $condition['order_status'] = -1; // 退款中
        $orderStatusNum['refunding'] = $order->where($condition)->count();
        $condition['order_status'] = -2; // 已退款
        $orderStatusNum['refunded'] = $order->where($condition)->count();
        $condition['order_status'] = array(
            'in',
            '3,4'
        ); // 已收货
        $condition['is_evaluate'] = 0; // 未评价
        $orderStatusNum['wait_evaluate'] = $order->where($condition)->count(); // 待评价
        $condition['order_status'] = -3; // 退货
        $orderStatusNum['refunded_goods'] = $order->where($condition)->count();
        return $orderStatusNum;
    }

    /**
     * 店铺评价-添加
     */
    public function addShopEvaluate($data)
    {
        $goodsEvaluate = new VslShopEvaluateModel();
        $res = $goodsEvaluate->save($data);

        $shop_model = new VslShopModel();
        $shop_service = new Shop();
        $shop_evaluate = $shop_service->getShopEvaluate($data['shop_id']);
        $shop_data['shop_desccredit'] = number_format(($shop_evaluate['shop_desc'] * $shop_evaluate['count'] + $data['shop_desc']) / ($shop_evaluate['count'] + 1), 1);
        $shop_data['shop_servicecredit'] = number_format(($shop_evaluate['shop_service'] * $shop_evaluate['count'] + $data['shop_service']) / ($shop_evaluate['count'] + 1), 1);
        $shop_data['shop_deliverycredit'] = number_format(($shop_evaluate['shop_stic'] * $shop_evaluate['count'] + $data['shop_stic']) / ($shop_evaluate['count'] + 1), 1);
        $shop_data['comprehensive'] = number_format(($shop_data['shop_desccredit'] + $shop_data['shop_servicecredit'] + $shop_data['shop_deliverycredit']) / 3, 1);
        $shop_model->save($shop_data, ['shop_id' => $data['shop_id'], 'website_id' => $data['website_id']]);

        return $res;
    }

    /**
     * 商品评价-添加
     *
     * @param unknown $dataList
     *            评价内容的 数组
     * @return Ambigous <multitype:, \think\false>
     */
    public function addGoodsEvaluate($dataArr, $order_id)
    {
        $goodsEvaluate = new VslGoodsEvaluateModel();
        $goods = new VslGoodsModel();
        $res = $goodsEvaluate->saveAll($dataArr);
        $result = false;
        if ($res != false) {
            // 修改订单评价状态
            $order = new VslOrderModel();
            $data = array(
                'is_evaluate' => 1
            );
            $result = $order->save($data, [
                'order_id' => $order_id
            ]);
        }
        foreach ($dataArr as $item) {

            $goods->where([
                'goods_id' => $item['goods_id']
            ])->setInc('evaluates');


        }
        hook("goodsEvaluateSuccess", [
            'order_id' => $order_id,
            'data' => $dataArr
        ]);

        return $result;
    }

    /**
     * 商品评价-追评
     *
     * @param unknown $again_content
     *            追评内容
     * @param unknown $againImageList
     *            传入追评图片的 数组
     * @param unknown $ordergoodsid
     *            订单项ID
     * @return Ambigous <number, \think\false>
     */
    public function addGoodsEvaluateAgain($again_content, $againImageList, $order_goods_id)
    {
        $goodsEvaluate = new VslGoodsEvaluateModel();
        $data = array(
            'again_content' => $again_content,
            'again_addtime' => time(),
            'again_image' => $againImageList
        );
        $res = $goodsEvaluate->save($data, [
            'order_goods_id' => $order_goods_id
        ]);
        hook("goodsEvaluateAgainSuccess", [
            'again_content' => $again_content,
            'againImageList' => $againImageList,
            'order_goods_id' => $order_goods_id
        ]);
        return $res;
    }

    /**
     * 评价信息 分页
     *
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return number
     */
    public function getOrderEvaluateDataList($page_index, $page_size, $condition, $order)
    {
        $goodsEvaluate = new VslGoodsEvaluateModel();
        $list = $goodsEvaluate->pageQuery($page_index, $page_size, $condition, $order, "*");
        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]['spec'] = [];
            $list['data'][$k]['del_status'] = 0;
            $order_item = new VslOrderGoodsModel();
            $order_item_list = $order_item->getInfo(['order_goods_id' => $v['order_goods_id']], 'sku_id');
            $goods_del = new VslGoodsDeletedModel();
            $del_status = $goods_del->getInfo(['goods_id'=>$v['goods_id']]);
            if($del_status){
                $list['data'][$k]['del_status'] = 1;
            }
            // 查询商品sku表开始
            $goods_sku = new VslGoodsSkuModel();
            $goods_sku_info = $goods_sku->getInfo([
                'sku_id' => $order_item_list['sku_id']
            ], 'code,attr_value_items');
            $goods_spec_value = new VslGoodsSpecValueModel();
            $sku_spec_info = explode(';', $goods_sku_info['attr_value_items']);
            foreach ($sku_spec_info as $k_spec => $v_spec) {
                $spec_value_id = explode(':', $v_spec)[1];
                $sku_spec_value_info = $goods_spec_value::get($spec_value_id, ['goods_spec']);
                $list['data'][$k]['spec'][$k_spec]['spec_value_name'] = $sku_spec_value_info['spec_value_name'];
                $list['data'][$k]['spec'][$k_spec]['spec_name'] = $sku_spec_value_info['goods_spec']['spec_name'];
            }
        }
        return $list;
    }

    /**
     * 修改订单数据
     *
     * @param unknown $order_id
     * @param unknown $data
     */
    public function modifyOrderInfo($data, $order_id)
    {
        $order = new VslOrderModel();
        return $order->save($data, [
            'order_id' => $order_id
        ]);
    }


    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IOrder::getShopOrderStatics()
     */
    public function getShopOrderStatics($shop_id, $start_time, $end_time)
    {
        $order_account = new OrderAccount();
        $order_sum = $order_account->getShopOrderSum($shop_id, $start_time, $end_time);
        $order_refund_sum = $order_account->getShopOrderSumRefund($shop_id, $start_time, $end_time);
        $order_sum_account = $order_sum - $order_refund_sum;
        $array = array(
            'order_sum' => $order_sum,
            'order_refund_sum' => $order_refund_sum,
            'order_account' => $order_sum_account
        );
        return $array;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IOrder::getShopOrderAccountDetail()
     */
    public function getShopOrderAccountDetail($shop_id)
    {
        // 获取总销售统计
        $account_all = $this->getShopOrderStatics($shop_id, '2015-1-1', '3050-1-1');
        // 获取今日销售统计
        $date_day_start = date("Y-m-d", time());
        $date_day_end = date("Y-m-d H:i:s", time());
        $account_day = $this->getShopOrderStatics($shop_id, $date_day_start, $date_day_end);
        // 获取周销售统计（7天）
        $date_week_start = date('Y-m-d', strtotime('-7 days'));
        $date_week_end = $date_day_end;
        $account_week = $this->getShopOrderStatics($shop_id, $date_week_start, $date_week_end);
        // 获取月销售统计(30天)
        $date_month_start = date('Y-m-d', strtotime('-30 days'));
        $date_month_end = $date_day_end;
        $account_month = $this->getShopOrderStatics($shop_id, $date_month_start, $date_month_end);
        $array = array(
            'day' => $account_day,
            'week' => $account_week,
            'month' => $account_month,
            'all' => $account_all
        );
        return $array;
    }

    /*
     * (non-PHPdoc)
     *
     * @see \data\api\IOrder::getShopAccountCountInfo()
     */
    public function getShopAccountCountInfo($shop_id)
    {
        // 本月第一天
        $date_month_start = getTimeTurnTimeStamp(date('Y-m-d', strtotime('-30 days')));
        $date_month_end = getTimeTurnTimeStamp(date("Y-m-d H:i:s", time()));
        // 下单金额
        $order_account = new OrderAccount();
        $condition["create_time"] = [
            [
                ">=",
                $date_month_start
            ],
            [
                "<=",
                $date_month_end
            ]
        ];
        $condition['order_status'] = array(
            'NEQ',
            0
        );
        $condition['order_status'] = array(
            'NEQ',
            5
        );
        if ($shop_id >= 0) {
            $condition['shop_id'] = $shop_id;
        }

        $order_money = $order_account->getShopSaleSum($condition);
        // var_dump($order_money);
        // 下单会员
        $order_user_num = $order_account->getShopSaleUserSum($condition);
        // 下单量
        $order_num = $order_account->getShopSaleNumSum($condition);
        // 下单商品数
        $order_goods_num = $order_account->getShopSaleGoodsNumSum($condition);
        // 平均客单价
        if ($order_user_num > 0) {
            $user_money_average = $order_money / $order_user_num;
        } else {
            $user_money_average = 0;
        }
        // 平均价格
        if ($order_goods_num > 0) {
            $goods_money_average = $order_money / $order_goods_num;
        } else {
            $goods_money_average = 0;
        }
        $array = array(
            "order_money" => sprintf('%.2f', $order_money),
            "order_user_num" => $order_user_num,
            "order_num" => $order_num,
            "order_goods_num" => $order_goods_num,
            "user_money_average" => sprintf('%.2f', $user_money_average),
            "goods_money_average" => sprintf('%.2f', $goods_money_average)
        );
        return $array;
    }

    /*
     * (non-PHPdoc)
     *
     * @see \data\api\IOrder::getShopGoodsSalesList()
     */
    public function getShopGoodsSalesList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        // $goods_calculate = new GoodsCalculate();
        // $goods_sales_list = $goods_calculate->getGoodsSalesInfoList($page_index, $page_size , $condition , $order );
        // return $goods_sales_list;
        $goods_model = new VslGoodsModel();
        $start_date = $condition["start_date"];
        $end_date = $condition["end_date"];
        unset($condition['start_date']);
        unset($condition['end_date']);
        $tmp_array = $condition;
        if (!empty($condition["order_status"])) {
            $order_condition["order_status"] = $condition["order_status"];
            unset($tmp_array["order_status"]);
        }
        $goods_list = $goods_model->pageQuery($page_index, $page_size, $tmp_array, $order, '*');
        // 条件

        if ($start_date != "" && $end_date != "") {
            $order_condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ],
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        } else {
            if ($start_date != "" && $end_date == "") {
                $order_condition["create_time"] = [
                    [
                        ">",
                        getTimeTurnTimeStamp($start_date)
                    ]
                ];
            } else {
                if ($start_date == "" && $end_date != "") {
                    $order_condition["create_time"] = [
                        [
                            "<",
                            getTimeTurnTimeStamp($end_date)
                        ]
                    ];
                }
            }
        }


        $order_condition["shop_id"] = $condition["shop_id"];
        $goods_calculate = new GoodsCalculate();
        // 得到条件内的订单项
        $order_goods_list = $goods_calculate->getOrderGoodsSelect($order_condition);
        // 遍历商品
        foreach ($goods_list["data"] as $k => $v) {
            $data = array();
            $goods_sales_num = $goods_calculate->getGoodsSalesNum($order_goods_list, $v["goods_id"]);
            $goods_sales_money = $goods_calculate->getGoodsSalesMoney($order_goods_list, $v["goods_id"]);
            $data["sales_num"] = $goods_sales_num;
            $data["sales_money"] = $goods_sales_money;
            $goods_list["data"][$k]["sales_info"] = $data;
        }
        return $goods_list;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IOrder::getShopGoodsSalesAll()
     */
    public function getShopGoodsSalesQuery($shop_id, $start_date, $end_date, $condition)
    {
        // TODO Auto-generated method stub
        // 商品
        $goods_model = new VslGoodsModel();
        $goods_list = $goods_model->getQuery($condition, "*", '');
        // 订单项
        $condition['create_time'] = [
            'between',
            [
                $start_date,
                $end_date
            ]
        ];
        $order_condition["create_time"] = [
            [
                ">=",
                $start_date
            ],
            [
                "<=",
                $end_date
            ]
        ];
        $order_condition['order_status'] = array(
            'NEQ',
            0
        );
        $order_condition['order_status'] = array(
            'NEQ',
            5
        );
        if ($shop_id != '') {
            $order_condition["shop_id"] = $shop_id;
        }
        $goods_calculate = new GoodsCalculate();
        $order_goods_list = $goods_calculate->getOrderGoodsSelect($order_condition);
        // 遍历商品
        foreach ($goods_list as $k => $v) {
            $data = array();
            $goods_sales_num = $goods_calculate->getGoodsSalesNum($order_goods_list, $v["goods_id"]);
            $goods_sales_money = $goods_calculate->getGoodsSalesMoney($order_goods_list, $v["goods_id"]);
            $goods_list[$k]["sales_num"] = $goods_sales_num;
            $goods_list[$k]["sales_money"] = $goods_sales_money;
        }
        return $goods_list;
    }

    /**
     * 查询一段时间内的店铺下单金额
     *
     * @param unknown $shop_id
     * @param unknown $start_date
     * @param unknown $end_date
     * @return Ambigous <\data\service\Order\unknown, number, unknown>
     */
    public function getShopSaleSum($condition)
    {
        $order_account = new OrderAccount();
        $sales_num = $order_account->getShopSaleSum($condition);
        return $sales_num;
    }


    /**
     * 查询一段时间内的店铺下单量
     *
     * @param unknown $shop_id
     * @param unknown $start_date
     * @param unknown $end_date
     * @return unknown
     */
    public function getShopSaleNumSum($condition)
    {
        $order_account = new OrderAccount();
        $sales_num = $order_account->getShopSaleNumSum($condition);
        return $sales_num;
    }
    /**
     * 查询一段时间内的店铺下单金额
     *
     * @param unknown $shop_id
     * @param unknown $start_date
     * @param unknown $end_date
     * @return Ambigous <\data\service\Order\unknown, number, unknown>
     */
    public function getShopSaleMemberNumSum($condition)
    {
        $order_account = new OrderAccount();
        $sales_num = $order_account->getShopSaleUserSum($condition);
        return $sales_num;
    }

    /**
     * ***********************************************店铺账户--Start******************************************************
     */
    /**
     * 订单支付的时候 调整店铺账户
     *
     * @param string $order_out_trade_no
     * @param number $order_id
     */
    private function dealShopAccount_OrderPay($order_out_trade_no = "", $order_id = 0)
    {
        $order_model = new VslOrderModel();
        if ($order_out_trade_no != "" && $order_id == 0) {
            $condition = ["out_trade_no"=>$order_out_trade_no];
            $order_list = $order_model->Query($condition, "order_id");
            if(!$order_list){
                $condition2 = ["out_trade_no_presell"=>$order_out_trade_no];
                $order_list = $order_model->Query($condition2, "order_id");
            }
            foreach ($order_list as $k => $v) {
                $shop_id = $order_model->getInfo(['order_id' => $v],'shop_id')['shop_id'];
                if ($shop_id > 0) {
                    $this->updateShopAccount_OrderPay($v);
                }
            }
        } else if ($order_out_trade_no == "" && $order_id != 0) {
            $shop_id = $order_model->getInfo(['order_id' => $order_id], 'shop_id')['shop_id'];
            if ($shop_id > 0) {
                $this->updateShopAccount_OrderPay($order_id);
            }
        }
    }

    /**
     * 订单完成的时候调整账户金额
     *
     * @param string $order_out_trade_no
     * @param number $order_id
     */
    private function dealShopAccount_OrderComplete($order_out_trade_no = "", $order_id = 0)
    {
        $order_model = new VslOrderModel();
        if ($order_out_trade_no != "" && $order_id == 0) {
            $condition = " out_trade_no=" . $order_out_trade_no;
            $order_list = $order_model->getQuery($condition, "order_id", "");
            foreach ($order_list as $k => $v) {
                $shop_id = $order_model->getInfo(['order_id' => $v['order_id']], 'shop_id')['shop_id'];
                if ($shop_id > 0) {
                    $this->updateShopAccount_OrderComplete($v["order_id"]);
                }
            }
        } else {
            if ($order_out_trade_no == "" && $order_id != 0) {
                $shop_id = $order_model->getInfo(['order_id' => $order_id], 'shop_id')['shop_id'];
                if ($shop_id > 0) {
                    $this->updateShopAccount_OrderComplete($order_id);
                }

            }
        }

    }

    /**
     * 订单支付
     *
     * @param unknown $order_id
     */
    private function updateShopAccount_OrderPay($order_id)
    {
        $order_model = new VslOrderModel();
        $shop_account = new ShopAccount();
        $order = new OrderBusiness();
        $order_model->startTrans();
        try {
            $order_obj = $order_model->getInfo(['order_id' => $order_id], '*');
            if($order_obj['presell_id'] != 0 && getAddons('presell', $this->website_id, $this->instance_id) && $order_obj['money_type'] == 2){
                $presell_mdl = new VslPresellModel();
                $presell_info = $presell_mdl->getInfo(['id'=>$order_obj['presell_id']],'*');
                $firstmoney = $presell_info['firstmoney'];
                $allmoney = $presell_info['allmoney'];
                $secondmoney = $allmoney-$firstmoney;
                $num = $order_obj['pay_money']/$firstmoney;
                $pay_money = $secondmoney*$num +$order_obj['shipping_money'];
            }else{
                // 订单的实际付款金额
                $pay_money = $order->getOrderRealPayMoney($order_id);
                $pay_money_all = $order->getOrderRealPayShopMoney($order_id);
            } 
            // 店铺id
            $shop_id = $order_obj["shop_id"];
            // 订单号
            $order_no = $order_obj["order_no"];
            // 添加店铺的整体资金流水
            //变更 订单号：xxxx，实付金额x元，平台优惠x元，店铺优惠x元，实际到账x元，已进入冻结账户。
            $shop_account->updateShopAccountTotalMoney($shop_id, $pay_money_all);

            $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, $pay_money_all, 1, $order_id, "订单支付金额" . $pay_money . "元, 订单号为：" . $order_no . "，平台优惠".$order_obj["platform_promotion_money"]."元，店铺优惠".$order_obj["shop_promotion_money"]."元，实际到账".$pay_money_all."元，已进入冻结账户。, 支付方式【在线支付】, 已入账户。", "订单支付完成，资金入账");

            $shop_account->addAccountRecords($shop_id, $order_obj['buyer_id'], '订单支付完成', $pay_money_all, 50, $order_id, "订单支付完成", $order_obj["website_id"]);
            $order_model->commit(); 
        } catch (\Exception $e) {
            recordErrorLog($e);
            Log::write("错误updateShopAccount_OrderPay" . $e->getMessage());
            $order_model->rollback();
        }
    }
    /**
     * 店铺整笔订单项退款
     *
     * @param unknown $order_goods_id
     */
    private function updateShopAccount_OrderRefund_All($order_id = 0,$refund_real_money)
    {
        // $order_goods_model = new VslOrderGoodsModel();
        
        $order_model = new VslOrderModel();
        $shop_account = new ShopAccount();
        $order_model->startTrans();
        try {
            // 查询订单项的信息
            // $order_goods_obj = $order_goods_model->get($order_goods_id);
            // // 退款金额
            $refund_money = $refund_real_money;
            // // 订单id
            // $order_id = $order_goods_obj["order_id"];
            // 订单信息
            $order_obj = $order_model->get($order_id);
            // 订单的支付方式
            $payment_type = $order_obj["payment_type"];
            // 店铺优惠金额
            $shop_promotion_money = $order_obj["shop_promotion_money"];
            // 店铺id
            $shop_id = $order_obj["shop_id"];
            // 订单号
            $order_no = $order_obj["order_no"];
            // 修改店铺的入账金额
            $shop_account->updateShopAccountTotalMoney($shop_id, (-1) * abs($refund_money));
            // 修改店铺的到账金额 整单退款 需要
            $getmoney = $order_obj["pay_money"] - $refund_money;
            if($getmoney > 0){
                $shop_account->updateShopAccountMoney($shop_id, $getmoney);
                // 添加店铺的整体资金流水
                $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, $getmoney, 5, $order_id, "订单号为：" . $order_no .",订单退款金额" . $refund_money ."元, 剩余到账" . $getmoney .  "元, 支付方式【在线支付】, 已入账户。", "订单退款完成，资金入账", $order_obj['website_id']);
            }

            // 添加店铺的整体资金流水
            $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, (-1) * abs($refund_money), 3, $order_id, "订单退款金额" . $refund_money . "元, 订单号为：" . $order_no . ", 退款方式【原路退款】。", "订单退款账户金额减少",$order_obj['website_id']);
            //退款记录注释店铺优惠相关

            // if ($shop_promotion_money > 0) {
            //     $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, (-1)*$shop_promotion_money, 4, $order_id, "订单退还优惠金额" . $shop_promotion_money . "元, 订单号为：" . $order_no . ", 退款方式【原路退款。", "订单退款优惠金额减少",$order_obj['website_id']);
            // }
            $order_model->commit();
        } catch (\Exception $e) {
            recordErrorLog($e);
            Log::write("错误updateShopAccount_OrderRefund:" . $e->getMessage());
            $order_model->rollback();
        }
    }
    /**
     * 订单项退款
     *
     * @param unknown $order_goods_id
     */
    private function updateShopAccount_OrderRefund($order_goods_id)
    {
        $order_goods_model = new VslOrderGoodsModel();
        $order_model = new VslOrderModel();
        $shop_account = new ShopAccount();
        $order_goods_model->startTrans();
        try {
            // 查询订单项的信息
            $order_goods_obj = $order_goods_model->get($order_goods_id);
            // 退款金额
            $refund_money = $order_goods_obj["refund_require_money"];
            // 订单id
            $order_id = $order_goods_obj["order_id"];
            // 订单信息
            $order_obj = $order_model->get($order_id);
            // 订单的支付方式
            $payment_type = $order_obj["payment_type"];
            // 店铺优惠金额
            $shop_promotion_money = $order_obj["shop_promotion_money"];
            // 店铺id
            $shop_id = $order_obj["shop_id"];
            // 订单号
            $order_no = $order_obj["order_no"];
            // 修改店铺的入账金额
            $shop_account->updateShopAccountTotalMoney($shop_id, (-1) * abs($refund_money));
            // 添加店铺的整体资金流水
            $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, (-1) * abs($refund_money), 3, $order_id, "订单退款金额" . $refund_money . "元, 订单号为：" . $order_no . ", 退款方式【原路退款】。", "订单退款账户金额减少",$order_obj['website_id']);

            // if ($shop_promotion_money > 0) {
            //     $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, (-1)*$shop_promotion_money, 4, $order_id, "订单退还优惠金额" . $shop_promotion_money . "元, 订单号为：" . $order_no . ", 退款方式【原路退款。", "订单退款优惠金额减少",$order_obj['website_id']);
            // }

            $order_goods_model->commit();
        } catch (\Exception $e) {
            recordErrorLog($e);
            Log::write("错误updateShopAccount_OrderRefund:" . $e->getMessage());
            $order_goods_model->rollback();
        }
    }

    /**
     * 订单完成（店铺收入）
     *
     * @param unknown $order_id
     */
    private function updateShopAccount_OrderComplete($order_id)
    {
        $order_model = new VslOrderModel();
        $shop_account = new ShopAccount();
        $order = new OrderBusiness();
        $order_model->startTrans();
        try {
            #订单的信息 
            $order_obj = $order_model->get($order_id);
            $order_sataus = $order_obj["order_status"];
            #判断当前订单的状态是否 已经交易完成 或者 已退款的状态
            if ($order_sataus == ORDER_COMPLETE_SUCCESS || $order_sataus == ORDER_COMPLETE_REFUND || $order_sataus == ORDER_COMPLETE_SHUTDOWN) {
                #订单的实际付款金额

                $pay_money = $order->getOrderRealPayShopMoney($order_id); 

                // 多商品订单 获取订单是否发生单商品退款
                $order_goods_model = new VslOrderGoodsModel();
                $order_goods_info = $order_goods_model::all(['order_id' => $order_id,'refund_status'=>5]);
                $m = 0;
                foreach ($order_goods_info as $v) {
                    $m += $v->refund_require_money;
                }
                
                $pay_money -= $m;
                #订单的支付方式
                $payment_type = $order_obj["payment_type"];
                #店铺id
                $shop_id = $order_obj["shop_id"];
                #订单号
                $order_no = $order_obj["order_no"];
                // 店铺优惠金额
                $shop_promotion_money = $order_obj["shop_promotion_money"];
                // 修改店铺的优惠金额
                $shop_account->updateShopPromotionMoney($shop_id, $shop_promotion_money);

                //先查询分销设置，是否开启店铺分销 是则有该店铺负责佣金 否则还是由平台负责
                $distribution_admin_status = 0;
                $isdistributionStatus = getAddons('distribution', $order_obj['website_id']);

                if($isdistributionStatus){
                    //获取分销基础设置，是否开启店铺分销
                    $config= new distributorService();
                    $distributionStatusAdmin = $config->getDistributionSite($order_obj['website_id']);
                    $distribution_admin_status = $distributionStatusAdmin['distribution_admin_status'];
                }
                
                //修改 订单号：xxxx，订单已完成，x元冻结余额解冻。
                if($distribution_admin_status == 0){
                    // 修改店铺的入账金额
                    
                    $shop_account->updateShopAccountMoney($shop_id, $pay_money);
                    // if ($shop_promotion_money > 0) {
                    //     $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, $shop_promotion_money, 2, $order_id, "订单优惠金额" . $shop_promotion_money . "元, 订单号为：" . $order_no . "", "订单完成，店铺优惠金额增加", $order_obj['website_id']);
                    // }
                    // 添加店铺的整体资金流水
                    $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, $pay_money, 5, $order_id, "订单号为：" . $order_no .",订单已完成，" . $pay_money ."元冻结余额已解冻" . ", 支付方式【在线支付】, 已入账户。", "订单完成，资金入账", $order_obj['website_id']);
                    
                }else{
                    //查询该笔订单是否启用分销 有则扣除相应的(佣金 积分换算成金额) 再写入店铺收益
                    //获取订单佣金信息 获取平台汇率设置
                    $order_commission = new VslOrderDistributorCommissionModel();
                    $orders = $order_commission->Query(['order_id' => $order_id], '*');
                    
                    $order_detail = array();
                    foreach ($orders as $key1 => $value) {
                        if($value['commissionA_id']){
                            $order_detail['commissionA'] += $value['commissionA'];
                            $order_detail['pointA'] += $value['pointA'];
                        }
                        if($value['commissionB_id']){
                            $order_detail['commissionB'] += $value['commissionB'];
                            $order_detail['pointB'] += $value['pointB'];
                        }
                        if($value['commissionC_id']){
                            $order_detail['commissionC'] += $value['commissionC'];
                            $order_detail['pointC'] += $value['pointC'];
                        }
                    }
                    
                    $order_detail['commission'] = $order_detail['commissionA'] + $order_detail['commissionB'] + $order_detail['commissionC'];
                    $order_detail['point'] = $order_detail['pointA'] + $order_detail['pointB'] + $order_detail['pointC'];
                    $commission_money = 0;
                    if(floatval($order_detail['commission']) > 0 || floatval($order_detail['point']) > 0 ){
                        //返积分
                        $config = new Config();
                        $config_info = $config->getShopConfig(0,$order_obj['website_id']);
                        $convert_rate = $config_info['convert_rate'] ? $config_info['convert_rate'] : 1; //汇率后台不设置 默认比例为1:1
                        $commission_point_money = floor($order_detail['point'] / $convert_rate);
                        $commission_money = $commission_point_money + $order_detail['commission'];
                        
                    }
                    // 修改店铺的入账金额
                    $real_pay_money = $pay_money - $commission_money;
                    $shop_account->updateShopAccountMoney($shop_id, $real_pay_money);
                    // if ($shop_promotion_money > 0) {
                    //     $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, $shop_promotion_money, 2, $order_id, "订单优惠金额" . $shop_promotion_money . "元, 订单号为：" . $order_no . "", "订单完成，店铺优惠金额增加", $order_obj['website_id']);
                    // }
                    // 添加店铺的整体资金流水
                    if($commission_money){
                        //订单号为：20190000000，订单实付金额100元，扣除发放佣金10元，到账90元。
                        $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, $real_pay_money, 5, $order_id, " 订单号为：" . $order_no .",订单冻结余额" . $pay_money . "元,扣除发放佣金" . $commission_money . " 元,到账" . $real_pay_money . "元,支付方式【在线支付】, 已入账户。", "订单完成，资金入账", $order_obj['website_id']);
                        
                    }else{

                        $shop_account->addShopAccountRecords(getSerialNo(), $shop_id, $real_pay_money, 5, $order_id, "订单号为：" . $order_no .",订单已完成，" . $pay_money ."元冻结余额已解冻" . ", 支付方式【在线支付】, 已入账户。", "订单完成，资金入账", $order_obj['website_id']);
                    }
                }
                
            }
            $order_model->commit();
        } catch (\Exception $e) {
            recordErrorLog($e);
            Log::write("错误updateShopAccount_OrderComplete:" . $e->getMessage());
            $order_model->rollback();
        }
    }

    /**
     * ***********************************************店铺账户--End******************************************************
     */

    /**
     * ***********************************************平台账户计算--Start******************************************************
     */
    /**
     * 订单支付时处理 平台的账户
     *
     * @param string $order_out_trade_no
     * @param number $order_id
     */
    public function dealPlatformAccountOrderPay($order_out_trade_no = "", $order_id = 0)
    {
        if ($order_out_trade_no != "" && $order_id == 0) {
            $order_model = new VslOrderModel();
            $order_list = $order_model->Query(["out_trade_no"=>$order_out_trade_no],"order_id");
            if(!$order_list){
                $order_list = $order_model->Query(["out_trade_no_presell"=>$order_out_trade_no],"order_id");
            }
            foreach ($order_list as $k => $v) {
                $this->updateAccountOrderPay($v);
            }
        } else
            if ($order_out_trade_no == "" && $order_id != 0) {
                $this->updateAccountOrderPay($order_id);
            }
    }


    //通过外部交易号或者状态
    public function get_status_by_outno($out_no)
    {

        $order = new VslOrderModel();
        $info = $order->getInfo(['out_trade_no|out_trade_no_presell'=>$out_no]);
        return $info;
    }

    //通过外部交易号或者状态查询渠道商的订单状态
    public function getChannelStatusByOutno($out_no)
    {
        $order = new VslChannelOrderModel();
        $info = $order->getInfo(['out_trade_no'=>$out_no]);
        return $info;
    }

    /**
     * 订单支付成功后处理 平台账户
     *
     * @param unknown $orderid
     */
    public function updateAccountOrderPay($order_id)
    {
        $order_model = new VslOrderModel();
        $order_goods_model = new VslOrderGoodsModel();
        $shop_account = new ShopAccount();
        $order = new OrderBusiness();
        $order_model->startTrans();
        $order_obj = $order_model->getInfo(['order_id' => $order_id], '*');
        $website_id = $order_obj['website_id'];
        try {
            // 订单的实际付款金额
            if($order_obj['presell_id'] != 0 && getAddons('presell', $website_id, $this->instance_id) && $order_obj['money_type'] == 2){
                $presell_mdl = new VslPresellModel();
                $presell_info = $presell_mdl->getInfo(['id'=>$order_obj['presell_id']],'*');
                $firstmoney = $presell_info['firstmoney'];
                $allmoney = $presell_info['allmoney'];
                $secondmoney = $allmoney-$firstmoney;
                $num = $order_obj['pay_money']/$firstmoney;
                $pay_money = $secondmoney*$num + $order_obj['shipping_money'];
            }else{
                
                //变更 实际收入变更为店铺
                if($order_obj['shop_id']>0){
                    $pay_money = $order->getOrderRealPayShopMoney($order_id);
                }else{
                    $pay_money = $order->getOrderRealPayMoney($order_id);
                }
                
            }
            //todo... 税费
            $pay_money += $order_obj['invoice_tax'];
//            $channel_money = (float)$order_obj['channel_money'] - (float)$order_obj['shipping_money'];
            $channel_money = $order_obj['channel_money'];
            // 订单的类型
            $order_type = $order_obj["order_type"];
            // 订单的支付方式
            $payment_type = $order_obj["payment_type"];
            // 店铺id
            $shop_id = $order_obj["shop_id"];
            // 订单号
            $order_no = $order_obj["order_no"];
            // 用户id
            $uid = $order_obj["buyer_id"];
            //查出当前订单是渠道商订单并且购买的是谁的
            $channel_id = $order_goods_model->getInfo(['order_id' => $order_id, 'channel_info' => ['neq', 0]], 'channel_info')['channel_info'];
            if(getAddons('channel', $website_id)){
                $channel = new Channel();
                $channel_mdl = new VslChannelModel();
                $channel_uid = $channel_mdl->getInfo(['channel_id' => $channel_id], 'uid')['uid'];
            }
            if ($payment_type != ORDER_REFUND_STATUS) {
                // 在线支付 处理平台的资金账户
                if ($payment_type == 5) {//余额支付
                    //零售的渠道商的商品，钱也是到平台，提现才分钱。
                    $shop_account->updateAccountOrderBalance($pay_money);
                    $shop_account->addAccountRecords($shop_id, $uid, '订单支付', $pay_money, 14, $order_id, "订单余额支付成功，余额支付总额增加", $website_id);
                    if (getAddons('channel', $website_id)) {
                        //如果渠道商的金额不为0，则更新用户账户表
                        if ($channel_money) {
                            $channel->updateMemberAccountFreezingBalance($channel_money, $channel_uid);
                            //26是代表渠道商订单
                            $channel->addMemberAccountRecords($channel_uid, "渠道商订单余额支付成功，冻结金额增加", $channel_money, $order_id, $website_id);
                        }
                    }
                } elseif ($payment_type == 1) {//微信支付
                    $shop_account->updateAccountOrderMoney($pay_money);
                    $shop_account->addAccountRecords($shop_id, $uid, '订单支付', $pay_money, 15, $order_id, "订单微信支付成功，入账总额增加", $website_id);
                    if (getAddons('channel', $website_id)) {
                        //如果渠道商的金额不为0，则更新用户账户表
                        if ($channel_money) {
                            $channel = new Channel();
                            //更新渠道商的账户表
                            $channel->updateMemberAccountFreezingBalance($channel_money, $channel_uid);
                            //26是代表渠道商订单
                            $channel->addMemberAccountRecords($channel_uid, "渠道商订单微信支付成功，冻结金额增加", $channel_money, $order_id, $website_id);
                        }
                    }
                } elseif ($payment_type == 2) {//支付宝支付
                    $shop_account->updateAccountOrderMoney($pay_money);
                    $shop_account->addAccountRecords($shop_id, $uid, '订单支付', $pay_money, 16, $order_id, "订单支付宝支付成功，入账总额增加", $website_id);
                    if (getAddons('channel', $website_id)) {
                        //如果渠道商的金额不为0，则更新用户账户表
                        if ($channel_money) {
                            $channel = new Channel();
                            //更新渠道商的账户表
                            $channel->updateMemberAccountFreezingBalance($channel_money, $channel_uid);
                            //26是代表渠道商订单
                            $channel->addMemberAccountRecords($channel_uid, "渠道商订单支付宝支付成功，冻结金额增加", $channel_money, $order_id, $website_id);
                        }
                    }
                }elseif ($payment_type == 3) {//银行卡支付
                    $shop_account->updateAccountOrderMoney($pay_money);
                    $shop_account->addAccountRecords($shop_id, $uid, '订单支付', $pay_money, 42, $order_id, "订单银行卡支付成功，入账总额增加", $website_id);
                    if (getAddons('channel', $website_id)) {
                        //如果渠道商的金额不为0，则更新用户账户表
                        if ($channel_money) {
                            $channel = new Channel();
                            //更新渠道商的账户表
                            $channel->updateMemberAccountFreezingBalance($channel_money, $channel_uid);
                            //26是代表渠道商订单
                            $channel->addMemberAccountRecords($channel_uid, "渠道商订单银行卡支付成功，冻结金额增加", $channel_money, $order_id, $website_id);
                        }
                    }
                }elseif ($payment_type == 16) {//eth支付
                    $shop_account->updateAccountOrderMoney($pay_money);
                    $shop_account->addAccountRecords($shop_id, $uid, '订单支付', $pay_money, 43, $order_id, "订单eth支付成功，入账总额增加", $website_id);
                    if (getAddons('channel', $website_id)) {
                        //如果渠道商的金额不为0，则更新用户账户表
                        if ($channel_money) {
                            $channel = new Channel();
                            //更新渠道商的账户表
                            $channel->updateMemberAccountFreezingBalance($channel_money, $channel_uid);
                            //26是代表渠道商订单
                            $channel->addMemberAccountRecords($channel_uid, "渠道商订单eth支付成功，冻结金额增加", $channel_money, $order_id, $website_id);
                        }
                    }
                }elseif ($payment_type == 17) {//eos支付
                    $shop_account->updateAccountOrderMoney($pay_money);
                    $shop_account->addAccountRecords($shop_id, $uid, '订单支付', $pay_money, 44, $order_id, "订单eos支付成功，入账总额增加", $website_id);
                    if (getAddons('channel', $website_id)) {
                        //如果渠道商的金额不为0，则更新用户账户表
                        if ($channel_money) {
                            $channel = new Channel();
                            //更新渠道商的账户表
                            $channel->updateMemberAccountFreezingBalance($channel_money, $channel_uid);
                            //26是代表渠道商订单
                            $channel->addMemberAccountRecords($channel_uid, "渠道商订单eos支付成功，冻结金额增加", $channel_money, 26);
                        }
                    }
                }elseif($payment_type == 4) {//货到付款
                    //零售的渠道商的商品，钱也是到平台，提现才分钱。
                    if (getAddons('channel', $website_id)) {
                        //如果渠道商的金额不为0，则更新用户账户表
                        if ($channel_money) {
                            $channel->updateMemberAccountFreezingBalance($channel_money, $channel_uid);
                            $channel->addMemberAccountRecords($channel_uid, "渠道商订单货到付款，冻结金额增加", $channel_money, $order_id, $website_id);
                        }
                    }
                } elseif ($payment_type == 20) {//GlobePay支付
                    $shop_account->updateAccountOrderMoney($pay_money);
                    $shop_account->addAccountRecords($shop_id, $uid, '订单支付', $pay_money, 46, $order_id, "订单GlobePay支付成功，入账总额增加");
                    if (getAddons('channel', $website_id)) {
                        //如果渠道商的金额不为0，则更新用户账户表
                        if ($channel_money) {
                            $channel = new Channel();
                            //更新渠道商的账户表
                            $channel->updateMemberAccountFreezingBalance($channel_money, $channel_uid);
                            //26是代表渠道商订单
                            $channel->addMemberAccountRecords($channel_uid, "渠道商订单GlobePay支付成功，冻结金额增加", $channel_money, $order_id, $website_id);
                        }
                    }
                }
                $order_id_array = [];
                if($order_type!=2 && $order_type!=3 && $order_type!=4 && $order_type!=10){
                    if (getAddons('distribution', $website_id) == 1) {// 计算分销佣金
                        $order_id_array[] = $order_id;
                    }
                    if (getAddons('globalbonus', $website_id) == 1) {// 计算全球分红
                        $order_id_array[] = $order_id;
                    }
                    if (getAddons('areabonus', $website_id) == 1) {// 计算区域分红
                        $order_id_array[] = $order_id;
                    }
                    if (getAddons('teambonus', $website_id)) {// 计算团队分红
                        $order_id_array[] = $order_id;
                    }
                    if (getAddons('microshop', $website_id) == 1) {// 微店计算
                        $order_id_array[] = $order_id;
                    }
                }
                // debugLog($order_id_array, '==>货到付款订单提交-order_id_array<==');
                if (!empty($order_id_array)) {
                    $order_calculate_model = new VslOrderCalculateModel();
                    $order_calculate_model->save(['had_paid' => 1], ['order_id' => ['IN', $order_id_array]]);
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
            }
            $order_model->commit();
        } catch (\Exception $e) {
            recordErrorLog($e);
            Log::write("错误updateAccountOrderPay:" . $e->getMessage());
            $order_model->rollback();
        }
    }
    /**
     * 订单完成时 处理平台的抽成
     *
     * @param unknown $order_id
     */
    public function updateAccountOrderComplete($order_id)
    {
        $order_model = new VslOrderModel();
        $order = new OrderBusiness();
        $order_obj = $order_model->getInfo(['order_id' => $order_id], '*');
        $order_sataus = $order_obj["order_status"];
        #判断当前订单的状态是否 已经交易完成
        if ($order_sataus == ORDER_COMPLETE_SUCCESS || $order_sataus == ORDER_COMPLETE_REFUND || $order_sataus == ORDER_COMPLETE_SHUTDOWN) {
            if (!empty($order_obj)) {
                $shop_id = $order_obj["shop_id"];
                // 订单的实际付款金额
                $pay_money = $order->getOrderRealPayMoney($order_id);
                // 用户id
                $uid = $order_obj["buyer_id"];
                $account_service = new ShopAccount();
                // 添加平台的整体资金流水和订单流水
//                $account_service->addAccountRecords($shop_id, $uid, '订单完成', $pay_money, 50, $order_id, "订单完成", $order_obj["website_id"]);
            }
        }

    }

    /**
     * 订单退款 更新平台的订单支付金额
     *
     * @param unknown $order_goods_id
     */
    public function updateAccountOrderRefund($order_goods_id)
    {
        $order_goods_model = new VslOrderGoodsModel();
        $order_model = new VslOrderModel();
        $shop_account = new ShopAccount();
        $order_goods_model->startTrans();
        try {
            // 查询订单项的信息
            $order_goods_obj = $order_goods_model->getInfo(['order_goods_id' => $order_goods_id], '*');
            // 退款金额
            $refund_money = $order_goods_obj["refund_require_money"];
            // 订单id
            $order_id = $order_goods_obj["order_id"];
            // 订单信息
            $order_obj = $order_model->getInfo(['order_id' => $order_id], '*');
            // 订单的支付方式
            $payment_type = $order_obj["payment_type"];
            // 店铺id
            $shop_id = $order_obj["shop_id"];
            // 订单号
            $order_no = $order_obj["order_no"];
            // 用户id
            $uid = $order_obj["buyer_id"];
            $real_refund_money = (-1) * $refund_money;
            // 在线退款 处理平台的资金账户
            if ($payment_type == 5) {//余额支付退款
                $shop_account->updateAccountMoney($real_refund_money,$order_obj['website_id']);
                $shop_account->updateAccountOrderBalance($real_refund_money,$order_obj['website_id']);
            } else {
                $shop_account->updateAccountMoney($real_refund_money,$order_obj['website_id']);
            }
            //处理平台抽取店铺利润
            if(getAddons('shop', $order_goods_obj['website_id'])==1){
                $shop_account->updateShopOrderGoodsReturnRecords($order_id, $order_goods_id, $shop_id);
            }
            // 添加平台的整体资金流水和订单流水
//            $shop_account->addAccountOrderRecords($shop_id, -$refund_money, 2, $order_goods_id, "订单项退款金额" . $refund_money . "元, 订单号：" . $order_no . "。", $uid,$this->website_id);
            $shop_account->addAccountRecords($shop_id, $uid, '订单退款', -$refund_money, 18, $order_id, "订单退款成功",$order_goods_obj['website_id']);
            if (getAddons('distribution',$order_obj['website_id']) == 1) {
                // 执行钩子:重新计算订单的佣金情况
                hook('updateCommissionMoney', ['order_id' => $order_id, 'website_id' => $order_goods_obj['website_id'], 'order_goods_id' => $order_goods_id]); 
            }
            if (getAddons('globalbonus',$order_obj['website_id']) == 1) {
                // 执行钩子:重新计算全球分红情况
                hook('updateGlobalBonusMoney', ['order_id' => $order_id, 'website_id' => $order_goods_obj['website_id'], 'order_goods_id' => $order_goods_id]);
            }
            if (getAddons('areabonus',$order_obj['website_id']) == 1) {
                // 执行钩子:重新计算区域分红情况
                hook('updateAreaBonusMoney', ['order_id' => $order_id, 'website_id' => $order_goods_obj['website_id'], 'order_goods_id' => $order_goods_id]);
            }
            if (getAddons('teambonus',$order_obj['website_id'])) {
                // 执行钩子:重新计算团队分红情况
                hook('updateTeamBonusMoney', ['order_id' => $order_id, 'website_id' => $order_goods_obj['website_id'], 'order_goods_id' => $order_goods_id]);
            }
            if (getAddons('microshop',$order_obj['website_id'], $this->instance_id)) {
                // 执行钩子:重新计算微店情况
                hook('updateMicroShopMoney', ['order_id' => $order_id, 'website_id' => $order_goods_obj['website_id'], 'order_goods_id' => $order_goods_id]);
            }

            $order_goods_model->commit();
        } catch (\Exception $e) {
            recordErrorLog($e);
            $order_goods_model->rollback();
        }

    }

    /**
     * ***********************************************平台账户计算--End******************************************************
     */
    /**
     * 查询店铺的退货地址列表
     * @param $shop_id
     * @param $website_id
     */
    public function getShopReturnList($shop_id, $website_id)
    {
        $shop_return = new VslOrderShopReturnModel();
        $list = $shop_return->getQuery([
            'shop_id' => $shop_id,
            'website_id' => $website_id
        ], '*', 'is_default desc');
        if($list){
            $address = new Address();
            foreach ($list as $k => $v) {
                $list[$k]['province_name'] = $address->getProvinceName($v['province']);
                $list[$k]['city_name'] = $address->getCityName($v['city']);
                $list[$k]['dictrict_name'] = $address->getDistrictName($v['district']);
            }
        }
        return $list;
    }
    /**
     * 查询店铺的退货设置
     * @param $return_id
     * @param $shop_id
     * @param $website_id
     */
    public function getShopReturn($return_id,$shop_id, $website_id)
    {
        $shop_return = new VslOrderShopReturnModel();
        $shop_return_obj = $shop_return->getInfo(['return_id'=>$return_id,'shop_id' => $shop_id, 'website_id' => $website_id]);
        return $shop_return_obj;
    }

    /**
     *
     * 更新店铺的退货信息
     * (non-PHPdoc)
     */
    public function updateShopReturnSet($shop_id,$return_id,$consigner,$mobile,$province,$city,$district,$address,$zip_code,$is_default)
    {
        $shop_return = new VslOrderShopReturnModel();
        $data = array(
            "consigner" => $consigner,
            "mobile" => $mobile,
            "province" => $province,
            "city" => $city,
            "district" => $district,
            "address" => $address,
            "zip_code" => $zip_code,
            "is_default" =>$is_default
        );
        if($is_default==1){
            $shop_return->save(['is_default'=>0], ['shop_id' => $shop_id,'website_id'=>$this->website_id]);
        }
        if($return_id>0){
            $data['modify_time'] = time();
            $result = $shop_return->save($data, ['return_id' => $return_id]);
        }else{
            $data['shop_id'] = $shop_id;
            $data['website_id'] = $this->website_id;
            $data['create_time'] = time();
            $result = $shop_return->insert($data);
        }
        return $result;
    }
    
    /**
     * 删除店铺的退货信息
     */
    public function deleteShopReturnSet($shop_id,$return_id)
    {
        $shop_return = new VslOrderShopReturnModel();
        $result = $shop_return->where(['return_id' => $return_id,'shop_id'=>$shop_id])->delete();
        return $result;
    }

    /**
     * 得到订单的发货信息
     *
     * @param unknown $order_ids
     */
    public function getOrderGoodsExpressDetail($order_ids, $shop_id)
    {
        $order_goods_model = new VslOrderGoodsModel();
        $order_model = new VslOrderModel();
        $order_goods_express = new VslOrderGoodsExpressModel();
        // 查询订单的订单项的商品信息
        $order_goods_list = $order_goods_model->where(" order_id in ($order_ids)")->select();

        for ($i = 0; $i < count($order_goods_list); $i++) {
            $order_id = $order_goods_list[$i]["order_id"];
            $order_goods_id = $order_goods_list[$i]["order_goods_id"];
            $order_obj = $order_model->get($order_id);
            $order_goods_list[$i]["order_no"] = $order_obj["order_no"];
            $goods_express_obj = $order_goods_express->where("FIND_IN_SET($order_goods_id,order_goods_id_array)")->select();
            if (!empty($goods_express_obj)) {
                $order_goods_list[$i]["express_company"] = $goods_express_obj[0]["express_company"];
                $order_goods_list[$i]["express_no"] = $goods_express_obj[0]["express_no"];
            } else {
                $order_goods_list[$i]["express_company"] = "";
                $order_goods_list[$i]["express_no"] = "";
            }
        }
        return $order_goods_list;
    }

    /**
     * 通过订单id 得到 该订单的发货物流
     *
     * @param unknown $order_id
     */
    public function getOrderGoodsExpressList($order_id)
    {
        $order_goods_express_model = new VslOrderGoodsExpressModel();
        $express_list = $order_goods_express_model->getQuery([
            "order_id" => $order_id
        ], "*", "");
//        $express_list = $order_goods_express_model::all(['order_id' => $order_id],['express_company']);
        return $express_list;
    }

    /**
     * 添加卖家对订单的备注
     * @param array $data
     *
     * @return int $ret_val
     */
    public function addOrderSellerMemoNew(array $data)
    {
        $order_memo_model = new VslOrderMemoModel();
        $ret_val = $order_memo_model->save($data);
        return $ret_val;
    }

    /**
     * 获取订单备注信息
     *
     * @ERROR!!!
     *
     * @see \data\api\IOrder::getOrderRemark()
     */
    public function getOrderSellerMemo($order_id)
    {
        $order = new VslOrderModel();
        $res = $order->getQuery([
            'order_id' => $order_id
        ], "seller_memo", '');
        $seller_memo = "";
        if (!empty($res[0]['seller_memo'])) {
            $seller_memo = $res[0]['seller_memo'];
        }
        return $seller_memo;
    }

    /**
     * 获取订单备注
     * @param array $condition
     * @param string $order
     *
     * @return $memo_lists
     */
    public function getOrderMemoNew(array $condition, $order = 'id DESC')
    {
        $order_memo_model = new VslOrderMemoModel();
        $memo_lists = $order_memo_model->getQuery($condition, '*', $order);
        return $memo_lists;
    }

    /**
     * 得到订单的收货地址
     *
     * @param unknown $order_id
     * @return unknown
     */
    public function getOrderReceiveDetail($order_id)
    {
        $order = new VslOrderModel();
        $res = $order->getInfo([
            'order_id' => $order_id
        ], "order_id,receiver_mobile,receiver_province,receiver_city,receiver_district,receiver_address,receiver_zip,receiver_name", '');
        return $res;
    }

    /**
     * 更新订单的收货地址
     *
     * @param unknown $order_id
     * @param unknown $receiver_mobile
     * @param unknown $receiver_province
     * @param unknown $receiver_city
     * @param unknown $receiver_district
     * @param unknown $receiver_address
     * @param unknown $receiver_zip
     * @param unknown $receiver_name
     */
    public function updateOrderReceiveDetail($order_id, $receiver_mobile, $receiver_province, $receiver_city, $receiver_district, $receiver_address, $receiver_zip, $receiver_name)
    {
        $order = new VslOrderModel();
        $data = array(
            'receiver_mobile' => $receiver_mobile,
            'receiver_province' => $receiver_province,
            'receiver_city' => $receiver_city,
            'receiver_district' => $receiver_district,
            'receiver_address' => $receiver_address,
            'receiver_zip' => $receiver_zip,
            'receiver_name' => $receiver_name
        );
        $retval = $order->save($data, [
            'order_id' => $order_id
        ]);
        return $retval;
    }

    public function getOrderNumByOrderStatu($condition)
    {
        $order = new VslOrderModel();
        return $order->getCount($condition);
    }

//    /**
//     * 评论送积分
//     */
//    public function commentPoint($order_id)
//    {
//        // 给记录表添加记录
//        $goods_comment = new VslGoodsCommentModel();
//        $rewardRule = new PromoteRewardRule();
//        // 查询评论赠送积分数量，然后叠加
//        $shop_id = 0;
//        $website_id = $this->website_id;
//        $uid = $this->uid;
//        $info = $rewardRule->getRewardRuleDetail($shop_id);
//        $data = array(
//            'shop_id' => $shop_id,
//            'website_id' => $website_id,
//            'uid' => $uid,
//            'order_id' => $order_id,
//            'status' => 1,
//            'number' => $info['comment_point'],
//            'create_time' => time()
//        );
//        $retval = $goods_comment->save($data);
//        if ($retval > 0) {
//            // 给总记录表加记录
//            $result = $rewardRule->addMemberPointData($shop_id, $uid, $info['comment_point'], 20, '评论赠送积分');
//        }
//    }

    /**
     *
     * 查询会员的某个订单的条数
     * (non-PHPdoc)
     *
     * @see \data\api\IOrder::getUserOrderDetailCount()
     */
    public function getUserOrderDetailCount($user_id, $order_id)
    {
        $orderModel = new VslOrderModel();
        $condition = array(
            "buyer_id" => $user_id,
            "order_id" => $order_id
        );
        $order_count = $orderModel->getCount($condition);
        return $order_count;
    }

    /**
     * 查询会员某个条件的订单的条数
     *
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getUserOrderCountByCondition($condition)
    {
        $orderModel = new VslOrderModel();
        $order_count = $orderModel->getCount($condition);
        return $order_count;
    }

    /**
     * 删除订单
     *
     * @param unknown $order_id
     *            订单id
     * @param unknown $operator_type
     *            操作人类型 1商家 2用户
     * @param unknown $operator_id
     *            操作人id
     */
    public function deleteOrder($order_id, $operator_type, $operator_id)
    {
        $order_model = new VslOrderModel();
        $data = array(
            "is_deleted" => 1,
            "operator_type" => $operator_type,
            "operator_id" => $operator_id
        );
        $order_id_array = explode(',', $order_id);
        if ($operator_type == 1) {
            // 商家删除 目前之针对已关闭订单
            $res = $order_model->save($data, [
                "order_status" => 5,
                "order_id" => [
                    "in",
                    $order_id_array
                ],
                "shop_id" => $operator_id
            ]);
        } elseif ($operator_type == 2) {
            // 用户删除
            $res = $order_model->save($data, [
                "order_status" => 5,
                "order_id" => [
                    "in",
                    $order_id_array
                ],
                "buyer_id" => $operator_id
            ]);
        }
        return 1;
    }

    /**
     * 根据外部交易号查询订单编号，为了兼容多店版。所以返回一个数组
     *
     * @ERROR!!!
     *
     * @see \data\api\IOrder::getOrderNoByOutTradeNo()
     */
    public function getOrderNoByOutTradeNo($out_trade_no)
    {
        if (!empty($out_trade_no)) {
            $order_model = new VslOrderModel();
            $list = $order_model->getQuery([
                'out_trade_no' => $out_trade_no
            ], 'order_no', '');
            return $list;
        }
        return [];
    }
    /**
     * 根据外部交易号查询订单编号，为了兼容多店版。所以返回一个数组
     *
     * @ERROR!!!
     *
     * @see \data\api\IOrder::getOrderNoByOutTradeNo()
     */
    public function getIntermentOrderNoByOutTradeNo($out_trade_no)
    {
        if (!empty($out_trade_no)) {
            $order_model = new VslIncreMentOrderModel();
            $list = $order_model->getQuery([
                'out_trade_no' => $out_trade_no
            ], 'order_no', '');
            return $list;
        }
        return [];
    }
    /**
     *
     * 根据外部交易号查询订单状态
     *
     * @ERROR!!!
     *
     * @see \data\api\IOrder::getOrderStatusByOutTradeNo()
     */
    public function getOrderStatusByOutTradeNo($out_trade_no)
    {
        if (!empty($out_trade_no)) {
            if(strstr($out_trade_no,'QD')){
                $order_model = new VslChannelOrderModel();
            }else{
                $order_model = new VslOrderModel();
            }
            $order_status = $order_model->getInfo([
                'out_trade_no' => $out_trade_no
            ], 'order_status', '');
            return $order_status;
        }
        return 0;
    }
    /**
     *
     * 根据外部交易号查询增值订单状态
     *
     * @ERROR!!!
     *
     * @see \data\api\IOrder::getOrderStatusByOutTradeNo()
     */
    public function getIntermentOrderStatusByOutTradeNo($out_trade_no)
    {
        if (!empty($out_trade_no)) {
            $order_model = new VslIncreMentOrderModel();
            $order_status = $order_model->getInfo([
                'out_trade_no' => $out_trade_no
            ], 'order_status');
            return $order_status;
        }
        return 0;
    }
    /**
     * 根据订单项id查询订单退款账户记录
     *
     * {@inheritdoc}
     *
     * @see \data\api\IOrder::getOrderRefundAccountRecordsByOrderGoodsId()
     */
    public function getOrderRefundAccountRecordsByOrderGoodsId($order_goods_id)
    {
        $model = new VslOrderRefundAccountRecordsModel();
        $info = $model->getInfo([
            "order_goods_id" => $order_goods_id
        ], "*");
        return $info;
    }

    /**
     * 获取快递单打印内容
     * @param unknown $order_ids
     * @param unknown $shop_id
     */
    public function getOrderPrint($order_ids, $shop_id)
    {
        $order_goods_model = new VslOrderGoodsModel();
        $order_model = new VslOrderModel();
        $order_goods_express = new VslOrderGoodsExpressModel();
        // 查询订单的订单项的商品信息
        $order_id_array = explode(',', $order_ids);
        $order_goods_list = array();
        foreach ($order_id_array as $order_id) {
            $order_express_list = $order_goods_express->getQuery(["order_id" => $order_id], "*", "");
            if (!empty($order_express_list) && count($order_express_list) > 0) {
                $express_order_goods_ids = "";
                foreach ($order_express_list as $order_express_obj) {
                    $order_goods_id_array = $order_express_obj["order_goods_id_array"];
                    if (!empty($express_order_goods_ids)) {
                        $express_order_goods_ids .= "," . $order_goods_id_array;
                    } else {
                        $express_order_goods_ids = $order_goods_id_array;
                    }
                    $order_goods_list_print = $order_goods_model->where("FIND_IN_SET(order_goods_id, '$order_goods_id_array') and order_id=$order_id and shop_id=$shop_id")->select();
                    $order_print_item = $this->dealPrintOrderGoodsList($order_id, $order_goods_list_print, 1, $order_express_obj["express_company_id"], $order_express_obj["express_name"], $order_express_obj["express_no"], $order_express_obj["id"]);
                    $order_goods_list[] = $order_print_item;
                }
                $order_goods_list_print = $order_goods_model->where("FIND_IN_SET(order_goods_id, '$express_order_goods_ids')=0 and order_id=$order_id and shop_id=$shop_id")->select();
                if (!empty($order_goods_list_print) && count($order_goods_list_print) > 0) {
                    $order_print_item = $this->dealPrintOrderGoodsList($order_id, $order_goods_list_print, 0, 0, "", "");
                    $order_goods_list[] = $order_print_item;
                }
            } else {
                $order_goods_list_print = $order_goods_model->where("order_id=$order_id and shop_id=$shop_id")->select();
                $order_print_item = $this->dealPrintOrderGoodsList($order_id, $order_goods_list_print, 0, 0, "", "");
                $order_goods_list[] = $order_print_item;
            }
        }
        return $order_goods_list;
    }

    /**
     * 处理订单打印数据
     * @param unknown $order_id
     * @param unknown $order_goods_list_print
     * @param unknown $is_express
     * @param unknown $express_company_id
     * @param unknown $express_company_name
     * @param unknown $express_no
     * @param number $express_id
     */
    public function dealPrintOrderGoodsList($order_id, $order_goods_list_print, $is_express, $express_company_id, $express_company_name, $express_no, $express_id = 0)
    {
        $order_goods_item_obj = array();
        $order_goods_ids = "";
        $print_goods_array = array();
        $is_print = 1;
        $tmp_express_company = "";
        $tmp_express_company_id = 0;
        $tmp_express_no = "";
        foreach ($order_goods_list_print as $k => $order_goods_print_obj) {
            $print_goods_array[] = array(
                "goods_id" => $order_goods_print_obj["goods_id"],
                "goods_name" => $order_goods_print_obj["goods_name"],
                "sku_id" => $order_goods_print_obj["sku_id"],
                "sku_name" => $order_goods_print_obj["sku_name"]
            );
            if (!empty($order_goods_ids)) {
                $order_goods_ids = $order_goods_ids . "," . $order_goods_print_obj["order_goods_id"];
            } else {
                $order_goods_ids = $order_goods_print_obj["order_goods_id"];
            }
            if ($k == 0) {
                $tmp_express_company = $order_goods_print_obj["tmp_express_company"];
                $tmp_express_company_id = $order_goods_print_obj["tmp_express_company_id"];
                $tmp_express_no = $order_goods_print_obj["tmp_express_no"];
            }
        }
        if (empty($tmp_express_company) || empty($tmp_express_company_id) || empty($tmp_express_no)) {
            $is_print = 0;
        }
        if ($is_express == 0) {
            $express_company_id = $tmp_express_company_id;
            $express_company_name = $tmp_express_company;
            $express_no = $tmp_express_no;
        }
        $order_model = new VslOrderModel();
        $order_obj = $order_model->get($order_id);
        $order_goods_item_obj = array(
            "order_id" => $order_id,
            "order_goods_ids" => $order_goods_ids,
            "goods_array" => $print_goods_array,
            "is_devlier" => $is_express,
            "is_print" => $is_print,
            "express_company_id" => $express_company_id,
            "express_company_name" => $express_company_name,
            "express_no" => $express_no,
            "order_no" => $order_obj["order_no"],
            "express_id" => $express_id
        );
        return $order_goods_item_obj;
    }

    /**
     * 通过订单id获取未发货订单项
     * @param unknown $order_ids
     */
    public function getNotshippedOrderByOrderId($order_ids)
    {
        $order_goods_model = new VslOrderGoodsModel();
        $order_id_array = explode(',', $order_ids);
        $order_goods_list = array();
        foreach ($order_id_array as $order_id) {
            $order_goods_list_print = $order_goods_model->getQuery(["order_id" => $order_id, "shipping_status" => 0, "refund_status" => 0], "*", "");
            $order_goods_item = $this->dealPrintOrderGoodsList($order_id, $order_goods_list_print, 0, $order_goods_list_print[0]["tmp_express_company_id"], $order_goods_list_print[0]["tmp_express_company"], $order_goods_list_print[0]["tmp_express_no"]);
            $order_goods_list[] = $order_goods_item;
        }
        return $order_goods_list;
    }

    public function getBusinessProfileList($start_date, $end_date, $condition)
    {
        // TODO Auto-generated method stub
        $order_model = new VslOrderModel();
        $order_list = $order_model->getQuery($condition, '(pay_money+user_platform_money) as allmoney,create_time,order_status', '');
        $list = array();
        for ($start = $start_date; $start <= $end_date; $start += 24 * 3600) {
            $list[date("Y-m-d", $start)] = array();
            $allCount = 0;
            $payCount = 0;
            $returnCount = 0;
            $sum = 0.00;
            foreach ($order_list as $v) {
                if (date("Y-m-d", $v["create_time"]) == date("Y-m-d", $start)) {
                    $allCount = $allCount + 1;
                    if ($v['order_status'] > 0 && $v['order_status'] < 5) {
                        $sum = $sum + $v['allmoney'];
                        $payCount = $payCount + 1;
                    }
                    if ($v['order_status'] < 0) {
                        $returnCount = $returnCount + 1;
                    }
                }
            }
            $list[date("Y-m-d", $start)]['count'] = $allCount;
            $list[date("Y-m-d", $start)]['paycount'] = $payCount;
            $list[date("Y-m-d", $start)]['returncount'] = $returnCount;
            $list[date("Y-m-d", $start)]['sum'] = $sum;
        }
        return $list;
    }

    public function getOrderPaymentByOutTradeNo($out_trade_no)
    {
        if (!empty($out_trade_no)) {
            $order_model = new VslOrderPaymentModel();
            $order = $order_model->getInfo([
                'out_trade_no' => $out_trade_no
            ], '*');
            return $order;
        }
        return false;
    }
    public function getIncrementOrderPaymentByOutTradeNo($out_trade_no)
    {
        if (!empty($out_trade_no)) {
            $order_model = new VslIncreMentOrderPaymentModel();
            $order = $order_model->getInfo([
                'out_trade_no' => $out_trade_no
            ], '*');
            return $order;
        }
        return false;
    }
    /*
     * 根据订单交易号关闭订单
     */
    public function orderCloseByOutTradeNo($out_trade_no)
    {
        if (!empty($out_trade_no)) {
            $order_model = new VslOrderModel();
            $orderList = $order_model->getQuery(['out_trade_no' => $out_trade_no, 'website_id' => $this->website_id, 'order_status' => 0], 'order_id', 'create_time desc');
            if (!$orderList) {
                return false;
            }
            foreach ($orderList as $val) {
                $this->orderClose($val['order_id']);
            }
            return true;
        }
        return false;
    }
    /*
     * 根据订单交易号关闭订单
     */
    public function incrementOrderCloseByOutTradeNo($out_trade_no)
    {
        if (!empty($out_trade_no)) {
            $order_model = new VslIncreMentOrderModel();
            $orderList = $order_model->getQuery(['out_trade_no' => $out_trade_no, 'website_id' => $this->website_id, 'order_status' => 0], 'order_id', 'create_time desc');
            if (!$orderList) {
                return false;
            }
            foreach ($orderList as $val) {
                $this->orderClose($val['order_id']);
            }
            return true;
        }
        return false;
    }
    public function cancelOrder($order_id)
    {
        $order_model = new VslIncreMentOrderModel();
        $res = $order_model->save(['order_status' => 1], ['order_id'=>$order_id,'website_id' => $this->website_id]);
        return $res;
    }
    public function getIncrementOrderList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $order_model = new VslIncreMentOrderModel();
        $moduleModel = new \data\model\ModuleModel();
        // 查询主表
        $order_list = $order_model->pageQuery($page_index, $page_size, $condition, $order, '*');
        if (!empty($order_list['data'])) {
            foreach ($order_list['data'] as $k => $v) {
                if($v['order_type'] == 1){
                    $addons = new SysAddonsModel();
                    $module_info = $addons->getInfo(['id' => $v['addons_id']]);
                    $module = $moduleModel->getInfo(['module_id' => $module_info['module_id']]);
                    $info['cycle_price'] = $module_info['cycle_price'] ? json_decode(str_replace("&quot;", "\"", $module_info['cycle_price']), true) : '';
                    $order_list['data'][$k]['module_name'] = $module_info['title'];
                    $order_list['data'][$k]['logo'] = $module_info['logo'];
                    $order_list['data'][$k]['logo_small'] = $module_info['logo_small'];
                    $order_list['data'][$k]['title'] = $module_info['title'];
                    $order_list['data'][$k]['url'] = $module['url'];
                    if ($info['cycle_price']) {
                        foreach ($info['cycle_price'] as $k1 => $value) {
                            if ($v['circle_time'] == $value['cycle']) {
                                $order_list['data'][$k]['market_price'] = $value['market_price'];
                            }
                        }
                    }
                    if($v['circle_time']==1){
                        $order_list['data'][$k]['time'] =  '一个月';
                    }
                    if($v['circle_time']==2){
                        $order_list['data'][$k]['time'] =  '三个月';
                    }
                    if($v['circle_time']==3){
                        $order_list['data'][$k]['time'] =  '五个月';
                    }
                    if($v['circle_time']==4){
                        $order_list['data'][$k]['time'] =  '一年';
                    }
                    if($v['circle_time']==5){
                        $order_list['data'][$k]['time'] =  '两年';
                    }
                    if($v['circle_time']==6){
                        $order_list['data'][$k]['time'] =  '三年';
                    }
                    if($v['circle_time']==7){
                        $order_list['data'][$k]['time'] =  '四年';
                    }
                }elseif($v['order_type'] == 2){
                    $order_list['data'][$k]['module_name'] = '短信套餐';
                    $order_list['data'][$k]['logo'] = '';
                    $order_list['data'][$k]['title'] = '短信套餐';
                    $order_list['data'][$k]['market_price'] = $v['order_money'];
                    $order_list['data'][$k]['time'] = $v['circle_time'].'条';
                }
                if($v['order_status']==1){
                    $order_list['data'][$k]['status_name'] =  '已关闭';
                }
                if($v['order_status']==0){
                    $order_list['data'][$k]['status_name'] =  '待付款';
                }
                if($v['order_status']==2){
                    $order_list['data'][$k]['status_name'] =  '已支付';
                }
            }
        }
        return $order_list;
    }
    public function getIncrementOrderDetail($order_id)
    {
        $order_model = new VslIncreMentOrderModel();
        // 查询主表
        $order_list = $order_model->getInfo(['order_id'=>$order_id],'*');
        $order_list['close_time'] = date('Y-m-d H:i:s',$order_list['create_time'] + (30 * 60));
        if($order_list['order_type'] == 1){
            $addons = new SysAddonsModel();
            $module_info = $addons->getInfo(['id'=>$order_list['addons_id']]);
            $info['cycle_price'] = $module_info['cycle_price']?json_decode(str_replace ("&quot;", "\"", $module_info['cycle_price'] ),true):'';
            $order_list['module_name'] =  $module_info['title'];
            $order_list['logo'] = $module_info['logo'];
            $order_list['description'] = $module_info['description'];
            foreach ($info['cycle_price'] as $k1=>$value){
                if($order_list['circle_time']==$value['cycle']){
                    $order_list['market_price'] = $value['market_price'];
                }
            }
            if($order_list['circle_time']==1){
                $order_list['time'] =  '一个月';
            }
            if($order_list['circle_time']==2){
                $order_list['time'] =  '三个月';
            }
            if($order_list['circle_time']==3){
                $order_list['time'] =  '五个月';
            }
            if($order_list['circle_time']==4){
                $order_list['time'] =  '一年';
            }
            if($order_list['circle_time']==5){
                $order_list['time'] =  '两年';
            }
            if($order_list['circle_time']==6){
                $order_list['time'] =  '三年';
            }
            if($order_list['circle_time']==7){
                $order_list['time'] =  '四年';
            }
        }elseif($order_list['order_type']){
            $order_list['module_name'] = '短信套餐';
            $order_list['logo'] = '';
            $order_list['market_price'] = $order_list['order_money'];
        }
        if($order_list['order_status']==1){
            $order_list['status_name'] =  '已关闭';
        }
        if($order_list['order_status']==0){
            $order_list['status_name'] =  '待付款';
        }
        if($order_list['order_status']==2){
            $order_list['status_name'] =  '已支付';
        }
        return $order_list;
    }

    public function updateOrder(array $condition, array $data)
    {
        $order_model = new VslOrderModel();
        return $order_model->save($data, $condition);
    }
    
    /*
     * 根据外部交易号获取订单id
     */
    public function getOrderIdByOutno($out_trade_no)
    {
        $orderModel = new VslOrderModel();
        $orderIds = $orderModel->Query(['out_trade_no' => $out_trade_no], 'order_id');
        if(count($orderIds) > 1){//多单暂时不需要返回
            return 0;
        }
        return $orderIds[0];
    }

    /**
     * 通过订单order_id查询订单支付对应的form_id
     * @param $order_id
     * @return string $form_id [小程序模板消息提交form_id]
     */
    public function getOrderPaymentFormIdByOrderId($order_id)
    {
        if (empty($order_id)) {
            return false;
        }
        $orderModel = new VslOrderModel();
        $trade_result = $orderModel->getInfo(['website_id' => $this->website_id, 'order_id' => $order_id], 'out_trade_no');
        if (empty($trade_result)) {
            return false;
        }
        $orderPaymentModel = new VslOrderPaymentModel();
        $form_result = $orderPaymentModel->getInfo(['website_id' => $this->website_id, 'out_trade_no' => $trade_result['out_trade_no']], 'form_id');
        return $form_result['form_id'];
    }

    /**
     * 通过订单order_id设置订单支付对应的form_id
     * @param $order_id
     * @param $form_id
     * @return string $form_id [小程序模板消息提交form_id]
     */
    public function setOrderPaymentFormIdByOrderId($order_id, $form_id)
    {
        $orderModel = new VslOrderModel();
        $trade_result = $orderModel->getInfo(['website_id' => $this->website_id, 'order_id' => $order_id], 'out_trade_no');
        if (empty($trade_result)) {
            return false;
        }
        $orderPaymentModel = new VslOrderPaymentModel();
        $result = $orderPaymentModel->save(['form_id' => $form_id], ['website_id' => $this->website_id, 'out_trade_no' => $trade_result['out_trade_no']]);

        return $result;
    }

    /**
     * 通过订单商品表订单商品表id获取form_id
     * @param $order_goods_id [订单商品表id]
     * @return string
     */
    public function getOrderRefundFormIdByOrderGoodsId($order_goods_id)
    {
        $order_goods = new VslOrderGoodsModel();
        $result = $order_goods->getInfo(['website_id' => $this->website_id, 'order_goods_id' => $order_goods_id], 'form_id');
        if ($result['form_id']) {
            return $result['form_id'];
        }
        return '';
    }
    /**
     * 重组创建门店订单所需数组
     */
    public function calculateCreateStoreOrderDataTesy(array $order_data)
    {
        $now_time = time();
        $man_song_rule_model = getAddons('fullcut', $this->website_id) ? new VslPromotionMansongRuleModel() : '';
        $coupon_model = getAddons('coupontype', $this->website_id) ? new VslCouponModel() : '';
        $promotion_discount_model = new VslPromotionDiscountModel();
        $user_model = new UserModel();
        $sec_service = getAddons('seckill', $this->website_id, $this->instance_id) ? new seckillServer() : '';
        $goodsExpress = new GoodsExpress();
        $group_server = getAddons('groupshopping', $this->website_id, $this->instance_id) ? new GroupShopping() : '';
        $storeServer = getAddons('store', $this->website_id, $this->instance_id) ? new Store() : '';
        $user_info = $user_model::get($this->uid, ['member_info.level', 'member_account', 'member_address']);
        $return_data['address_id'] = $order_data['address_id'];
        $return_data['group_id'] = $order_data['group_id'];
        $return_data['record_id'] = $order_data['record_id'];
        $return_data['shipping_type'] = $order_data['shipping_type'];
        $return_data['is_deduction'] = $order_data['is_deduction'];
        foreach ($order_data['shop_list'] as $shop) {
            $shop_id = $shop['shop_id'];
            $return_data['shop'][$shop_id]['store_id'] = $shop['store_id'] ?: 0;
            $return_data['shop'][$shop_id]['card_store_id'] = (empty($shop['card_store_id'])) ? 0 : $shop['card_store_id'];
            $return_data['shop'][$shop_id]['shop_channel_amount'] = 0;
            $return_data['shop'][$shop_id]['leave_message'] = $shop['leave_message'] ?: '';
            //处理店铺商品
            foreach ($shop['goods_list'] as $k => $sku_info) {
                //循环 处理单商品信息
                if($shop['store_id']){
                    $sku_model = new VslStoreGoodsSkuModel();
                    $sku_db_info = $sku_model::get(['sku_id' => $sku_info['sku_id'],'store_id'=>$shop['store_id']], ['goods']);
                }elseif($shop['card_store_id']){
                    //计时计次商品
                    $sku_model = new VslStoreGoodsSkuModel();
                    $sku_db_info = $sku_model::get(['sku_id' => $sku_info['sku_id'],'store_id'=>$shop['card_store_id']], ['goods']);
                }else{
                    $sku_model = new VslGoodsSkuModel();
                    $sku_db_info = $sku_model::get($sku_info['sku_id'], ['goods']);
                }
                $temp_sku_id = $sku_info['sku_id'];
                $return_data['order'][$shop_id]['sku'][$k]['sku_id'] = $temp_sku_id;
                $return_data['order'][$shop_id]['sku'][$k]['goods_id'] = $sku_info['goods_id'];
                $return_data['order'][$shop_id]['sku'][$k]['channel_id'] = $sku_info['channel_id'];
                $return_data['order'][$shop_id]['sku'][$k]['seckill_id'] = $sku_info['seckill_id'];
                $return_data['order'][$shop_id]['sku'][$k]['seckill_id'] = $sku_info['seckill_id'];
                if ($sku_info['presell_id'] && getAddons('presell', $this->website_id, $this->instance_id)) {
                    //判断预售是否关闭或者过期
                    $presell_id = $sku_info['presell_id'];
                    //如果是预售的商品，则更改其单价为预售价
                    $presell_mdl = new VslPresellModel();
                    $presell_condition['p.id'] = $presell_id;
                    $presell_condition['p.start_time'] = ['<', time()];
                    $presell_condition['p.end_time'] = ['>=', time()];
                    $presell_condition['p.status'] = ['neq', 3];
                    $presell_condition['pg.sku_id'] = $sku_info['sku_id'];
                    $presell_goods_info = $presell_mdl->alias('p')->where($presell_condition)->join('vsl_presell_goods pg', 'p.id = pg.presell_id', 'LEFT')->find();
                    if (!$presell_goods_info) {
                        return ['result' => -2, 'message' => '预售活动已过期或已关闭'];
                    }
                    $return_data['order'][$shop_id]['sku'][$k]['presell_id'] = $sku_info['presell_id'];
                } else {
                    $return_data['order'][$shop_id]['sku'][$k]['presell_id'] = 0;
                }
                $return_data['order'][$shop_id]['sku'][$k]['bargain_id'] = $sku_info['bargain_id'];
                $return_data['order'][$shop_id]['sku'][$k]['price'] = $sku_info['price'];
                //会员价
                $return_data['order'][$shop_id]['sku'][$k]['member_price'] = $sku_info['member_price'];
                $return_data['order'][$shop_id]['sku'][$k]['discount_id'] = $sku_info['discount_id'];
                $return_data['order'][$shop_id]['sku'][$k]['discount_price'] = $sku_info['discount_price'];
                $return_data['order'][$shop_id]['sku'][$k]['num'] = $sku_info['num'];
                $return_data['order'][$shop_id]['sku'][$k]['shop_id'] = $sku_db_info->goods->shop_id;
                $return_data['order'][$shop_id]['sku'][$k]['point_deduction_max'] = $sku_db_info->goods->point_deduction_max;
                $return_data['order'][$shop_id]['sku'][$k]['point_return_max'] = $sku_db_info->goods->point_return_max;
                $return_data['shop'][$shop_id]['goods_id_array'][] = $sku_info['goods_id'];
                $return_data['shop'][$shop_id]['member_amount'] += $return_data['order'][$shop_id]['sku'][$k]['member_price'] * $sku_info['num'];
                $return_data['shop'][$shop_id]['shop_total_amount'] += $sku_info['price'] * $sku_info['num'];
                $return_data['shop'][$shop_id]['shop_discount_amount'] += $sku_info['discount_price'] * $sku_info['num'];
                if (getAddons('groupshopping', $this->website_id, $this->instance_id)) {
                    $is_group = $group_server->isGroupGoods($sku_info['goods_id']);//是否团购商品
                    $group_sku_info_obj = $group_server->getGroupSkuInfo(['sku_id' => $sku_info['sku_id'], 'goods_id' => $sku_info['goods_id'], 'group_id' => $is_group]);
                    $group_sku_info_arr = objToArr($group_sku_info_obj);//商品团购信息
                }
                if (!empty($sku_info['channel_id']) && getAddons('channel', $this->website_id)) {
                    $website_id = $this->website_id;
                    $channel_gs_mdl = new VslChannelGoodsSkuModel();
                    $channel_gs_info = $channel_gs_mdl->getInfo(['website_id' => $website_id, 'channel_id' => $sku_info['channel_id'], 'sku_id' => $sku_info['sku_id']], 'stock');
                    if ($sku_info['num'] > $channel_gs_info['stock']) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . '商品渠道商库存不足'];
                    }
                    $sku_db_info->stock = $channel_gs_info['stock'];
                    $return_data['shop'][$shop_id]['shop_channel_amount'] += $sku_info['discount_price'] * $sku_info['num'];
                }
                if (!empty($sku_info['seckill_id']) && getAddons('seckill', $this->website_id, $this->instance_id)) {
                    //判断秒杀商品是否过期
                    $sku_id = $sku_info['sku_id'];
                    $seckill_id = $sku_info['seckill_id'];
                    $condition_is_seckill['s.seckill_id'] = $seckill_id;
                    $condition_is_seckill['nsg.sku_id'] = $sku_id;
                    $is_seckill = $sec_service->isSeckillGoods($condition_is_seckill);
                    if ($is_seckill) {
                        //获取秒杀商品的价格、库存、最大购买量
                        $condition_sku_info['seckill_id'] = $seckill_id;
                        $condition_sku_info['sku_id'] = $sku_id;
                        $sku_info_list = $sec_service->getSeckillSkuInfo($condition_sku_info);
                        $sku_info_arr = objToArr($sku_info_list);
                        //获取限购量
                        $goods_service = new Goods();
                        //通过用户累计购买量判断，先判断redis是否有内容
                        $uid = $this->uid;
                        $website_id = $this->website_id;
                        $buy_num = $goods_service->getActivityOrderSku($uid, $sku_id, $website_id, $sku_info['seckill_id']);
                        if ($sku_info['num'] > $is_seckill['seckill_num']) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出库存'];
                        }
                        //判断是否超过限购
                        if ($sku_info_arr['seckill_limit_buy'] != 0 && ($sku_info['num'] + $buy_num > $sku_info_arr['seckill_limit_buy'])) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 该商品规格购买数目已经超出最大秒杀限购数目'];
                        }
                        //价格
                        if ($is_seckill['seckill_price'] != $sku_info['price']) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 秒杀商品价格变动'];
                        }
                    } else {
                        return ['result' => -2, 'message' => $sku_db_info->goods->goods_name . '商品秒杀已结束，将恢复正常商品价格。'];
                    }
                } elseif ($is_group && $order_data['group_id'] && getAddons('groupshopping', $this->website_id, $this->instance_id)) { //拼团 不确定 待测试
                    if ($sku_info['num'] > $sku_db_info->stock) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出库存'];
                    }
                    if ($order_data['record_id']) {
                        $checkGroup = $group_server->checkGroupIsCan($order_data['record_id'], $this->uid);//判断该团购是否能参加
                        if ($checkGroup < 0) {
                            return ['result' => false, 'message' => '该团无法参加，请选择其他团或者自己发起团购'];
                        }
                    }
                    $goods_service = new Goods();
                    //通过用户累计购买量判断
                    $uid = $this->uid;
                    $website_id = $this->website_id;
                    $buy_num = $goods_service->getActivityOrderSkuForGroup($uid, $sku_info['sku_id'], $website_id, $order_data['group_id']);
                    if ($group_sku_info_arr['group_limit_buy'] != 0 && ($sku_info['num'] + $buy_num > $group_sku_info_arr['group_limit_buy'])) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 该商品规格您总共购买数目超出最大拼团限购数目'];
                    }
                    if ($sku_info['price'] != $group_sku_info_arr['group_price']) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 团购商品价格变动'];
                    }
                } elseif (!empty($sku_info['bargain_id']) && getAddons('bargain', $this->website_id, $this->instance_id)) {//砍价
                    $bargain = new Bargain();
                    $order_server = new \data\service\Order\Order();
                    $condition_bargain['bargain_id'] = $sku_info['bargain_id'];
                    $uid = $this->uid;
                    $condition_bargain['website_id'] = $this->website_id;
                    $is_bargain = $bargain->isBargain($condition_bargain, $uid);
                    if ($is_bargain) {
                        $return_data['bargain_id'] = $sku_info['bargain_id'];
                        //库存
                        $bargain_stock = $is_bargain['bargain_stock'];
                        $limit_buy = $is_bargain['limit_buy'];
                        $price = $is_bargain['my_bargain']['now_bargain_money'];
                        $buy_num = $order_server->getActivityOrderSkuNum($uid, $sku_info['sku_id'], $this->website_id, 3, $sku_info['bargain_id']);
                        if ($sku_info['num'] > $bargain_stock) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出砍价活动库存'];
                        }
                        if ($sku_info['num'] + $buy_num > $limit_buy) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 该商品规格您总共购买数目超出最大砍价限购数目'];
                        }
                        //价格
                        if ($sku_info['price'] != $price) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 砍价商品价格变动'];
                        }
                    } else {
                        return ['result' => -2, 'message' => '砍价活动已过期或已关闭'];
                    }
                } elseif (!empty($sku_info['presell_id']) && getAddons('presell', $this->website_id, $this->instance_id)) {
                    $presell_service = new PresellService();
                    $count_people = $presell_service->get_presell_buy_num($sku_info['presell_id']);
                    $presell_info = $presell_service->get_presell_by_sku($sku_info['presell_id'], $sku_info['sku_id']);
                    if ($presell_info['presellnum'] < $count_people) {
                        return ['result' => false, 'message' => '预售商品库存不足'];
                    }
                    $user_buy = $presell_service->get_user_count($sku_info['presell_id']);//当前用户购买数
                    if ($user_buy > $presell_info['maxbuy']) {//当前用户购买数大于每人限购
                        return ['result' => false, 'message' => '您已达到商品预售购买上限'];
                    }
                    //付定金去掉运费
                    $shipping_fee_all_last = $shipping_fee_all[$shop_id];
                    $shipping_fee_all[$shop_id] = 0;
                } else {
                    // 普通商品
                    //sku 信息检查
                    if ($sku_db_info->goods->state != 1) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 物品为不可购买状态'];
                    }
                    if ($sku_info['num'] > $sku_db_info->stock) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出库存'];
                    }
                    if (($sku_info['num'] > $sku_db_info->goods->max_buy) && $sku_db_info->goods->max_buy != 0) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 购买数目超出最大购买数目'];
                    }
                    if ($sku_info['price'] != $sku_db_info->price) {
                        return ['result' => false, 'message' => $sku_info->goods->goods_name . ' 商品价格变动'];
                    }
                }
                // 限时折扣
                $discount_price = $sku_info['discount_price'];
                if (!empty($sku_info['discount_id'])) {
                    if (!getAddons('discount', $this->website_id)) {
                        return ['result' => false, 'message' => '限时折扣应用已关闭'];
                    }
                    $promotion_discount_info_db = $promotion_discount_model::get($sku_info['discount_id'], ['goods']);
                    if ($promotion_discount_info_db->status != 1) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . '限时折扣状态不可用'];
                    }
                    if ($promotion_discount_info_db->start_time > $now_time || $promotion_discount_info_db->end_time < $now_time) {
                        return ['result' => false, 'message' => '限时折扣不在可用时间内'];
                    }
                    if (($promotion_discount_info_db->range_type == 1 || $promotion_discount_info_db->range_type == 3) &&
                        ($promotion_discount_info_db->shop_id != $shop_id)) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 限时折扣不在可用范围内'];
                    }
                    if ($promotion_discount_info_db->range == 2) {
                        if ($promotion_discount_info_db->goods()->where(['goods_id' => ['=', $sku_db_info->goods_id]])->count() == 0) {
                            return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 商品不在限时折扣指定商品范围内'];
                        }
                    }
                    // todo... 会员折扣 by sgw
                    $member_discount_price = $sku_info['discount_price'];
                    $discount_price_1 = round(($promotion_discount_info_db->discount_num / 10) * $member_discount_price, 2);
                    // 限时抢购商品表的折扣
                    $goods_discount = $promotion_discount_info_db->goods()->where(['goods_id' => $sku_info['goods_id']])->find();
                    if ($goods_discount) {
                        $promotion_discount = $promotion_discount_model->where(['discount_id' => $goods_discount['goods_id']])->find();
                        if ($promotion_discount['integer_type'] == 1) {
                            $discount_price_2 = round($goods_discount['discount'] / 10 * $member_discount_price);
                        } else {
                            $discount_price_2 = round($goods_discount['discount'] / 10 * $member_discount_price, 2);
                        }
                        if ($goods_discount['discount_type'] == 2) {
                            $discount_price_2 = $goods_discount['discount'];
                        }
                    }
                    if ($discount_price != $discount_price_1 && $discount_price != $discount_price_2) {
                        return ['result' => false, 'message' => $sku_db_info->goods->goods_name . ' 商品折扣价格变化'];
                    }
                    $return_data['order'][$shop_id]['sku'][$k]['promotion_shop_id'] = $promotion_discount_info_db->shop_id;// 限时折扣店铺id，用于识别优惠来源
                }
                //计时/次商品
                $goods_model = new VslGoodsModel();
                $goods_info = $goods_model->getInfo(['goods_id' => $sku_info['goods_id']]);
                $return_data['order'][$shop_id]['sku'][$k]['shipping_fee'] = $goods_info['shipping_fee_type'] == 1 ? ($goods_info['shipping_fee'] ?: 0) : 0;
                $return_data['order'][$shop_id]['sku'][$k]['shipping_fee_type'] = $goods_info['shipping_fee_type'];
                if (getAddons('store', $this->website_id, $this->instance_id) && $goods_info['goods_type'] == 0) {
                    $return_data['address_id'] = 0;
                    if ($return_data['shop'][$shop_id]['card_store_id'] == 0) {
                        return ['result' => false, 'message' => '请选择使用门店'];
                    }
                    $return_data['order'][$shop_id]['sku'][$k]['card_store_id'] = $return_data['shop'][$shop_id]['card_store_id'];
                    $return_data['order'][$shop_id]['sku'][$k]['cancle_times'] = $goods_info['cancle_times'];
                    $return_data['order'][$shop_id]['sku'][$k]['cart_type'] = $goods_info['cart_type'];
                    if ($goods_info['valid_type'] == 1) {
                        $return_data['order'][$shop_id]['sku'][$k]['invalid_time'] = time() + $goods_info['valid_days'] * 24 * 60 * 60;
                    } else {
                        $return_data['order'][$shop_id]['sku'][$k]['invalid_time'] = $goods_info['invalid_time'];
                    }
                    if ($goods_info['is_wxcard'] == 1) {
                        $return_data['order'][$shop_id]['sku'][$k]['wx_card_id'] = $goods_info['wx_card_id'];
                        $ticket = new VslGoodsTicketModel();
                        $ticket_info = $ticket->getInfo(['goods_id' => $sku_info['goods_id']]);
                        $return_data['order'][$shop_id]['sku'][$k]['card_title'] = $ticket_info['card_title'];
                    }
                }
            }
        }
        return $return_data;
    }
    /**
     * 检测订单是否存在退款状态
     * 存在退款状态订单不允许确认收货
     */
    public function checkReturn($order_id){
        $order = new VslOrderModel();
        $order_info = $order->getInfo(['order_id' => $order_id], '*'); //->count()
        $orderGoods = new VslOrderGoodsModel();
        // $order_info = $order->getInfo(['order_id' => $order_id], '*'); //->count()
        $order_goods_count = $orderGoods->where(" order_id = ($order_id)")->count();
        if($order_goods_count <= 1){
            return false;
        }
        $order_goods_list = $orderGoods->where(" order_id = ($order_id)")->select();
        //获取所有，查询是否存在售后商品
        // 1	买家申请	发起了退款申请,等待卖家处理
        // 2	等待买家退货	卖家已同意退款申请,等待买家退货
        // 3	等待卖家确认收货	买家已退货,等待卖家确认收货
        // 4	等待卖家确认退款	卖家同意退款
        // 0	退款已成功	卖家退款给买家，本次维权结束
        // -1	退款已拒绝	卖家拒绝本次退款，本次维权结束
        // -2	退款已关闭	主动撤销退款，退款关闭
        // -3	退款申请不通过	拒绝了本次退款申请,等待买家修改
        foreach ($order_goods_list as $key => $value) {
            if($value['refund_status'] > 0 && $value['refund_status'] != 5){
                //查询售后记录
                return true;
            }else{
                continue;
            }
        }
        return false;
        exit;
    }
}