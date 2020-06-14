<?php

namespace app\admin\controller;

use addons\blockchain\model\VslBlockChainRecordsModel;
use addons\invoice\server\Invoice as InvoiceService;
use data\model\VslOrderGoodsExpressModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderModel;
use data\service\Address;
use data\service\Address as AddressService;
use data\service\Express as ExpressService;
use data\service\Order\OrderGoods;
use data\service\Order\OrderStatus;
use data\service\Order as OrderService;
use data\service\Config;
use addons\groupshopping\server\GroupShopping;
use data\service\Order\Order as OrderType;
use think\Db;
use think\Exception;
use data\service\Excel;

/**
 * 订单控制器
 *
 * @author  www.vslai.com
 *
 */
class Order extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 订单列表
     */
    public function orderList() {
         $order_service = new OrderService();
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $start_create_date = request()->post('start_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_create_date'));
            $end_create_date = request()->post('end_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_create_date'));
            $start_pay_date = request()->post('start_pay_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_pay_date'));
            $end_pay_date = request()->post('end_pay_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_pay_date'));
            $start_send_date = request()->post('start_send_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_send_date'));
            $end_send_date = request()->post('end_send_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_send_date'));
            $start_finish_date = request()->post('start_finish_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_finish_date'));
            $end_finish_date = request()->post('end_finish_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_finish_date'));
            $user = request()->post('user', '');
            $order_no = request()->post('order_no', '');
            $order_status = request()->post('order_status', '');
            $payment_type = request()->post('payment_type', '');
            $express_no = request()->post('express_no', '');
            $goods_name = request()->post('goods_name', '');
            $order_type = request()->post('order_type', '');
            $order_id_array = request()->post('order_id_array/a');
            $delivery_order_status = request()->post('delivery_order_status');
            $express_order_status = request()->post('express_order_status');
            $shipping_type = request()->post('shipping_type');//订单配送方式
            $condition['is_deleted'] = 0; // 未删除订单
            if ($express_no) {
                $condition['express_no'] = ['LIKE', '%' . $express_no . '%'];
            }
            if ($goods_name) {
                $condition['goods_name'] = ['LIKE', '%' . $goods_name . '%'];
            }
            if ($order_type) {
                $condition['order_type'] = $order_type;
            }
            if ($start_create_date) {
                $condition['create_time'][] = ['>=', $start_create_date];
            }
            if ($end_create_date) {
                $condition['create_time'][] = ['<=', $end_create_date + 86399];
            }
            if ($start_pay_date) {
                $condition['pay_time'][] = ['>=', $start_pay_date];
            }
            if ($end_pay_date) {
                $condition['pay_time'][] = ['<=', $end_pay_date + 86399];
            }
            if ($start_send_date) {
                $condition['consign_time'][] = ['>=', $start_send_date];
            }
            if ($end_send_date) {
                $condition['consign_time'][] = ['<=', $end_send_date + 86399];
            }
            if ($start_finish_date) {
                $condition['finish_time'][] = ['>=', $start_finish_date];
            }
            if ($end_finish_date) {
                $condition['finish_time'][] = ['<=', $end_finish_date + 86399];
            }
            if ($order_status != '') {
                // $order_status 1 待发货
                if ($order_status == 1) {
                    // 订单状态为待发货实际为已经支付未完成还未发货的订单
                    $condition['shipping_status'] = 0; // 0 待发货
                    $condition['pay_status'] = 2; // 2 已支付
                    //$condition['store_id'] = 0; // 2 已支付
                    $condition['order_status'][] = array(
                        'neq',
                        4
                    ); // 4 已完成
                    $condition['order_status'][] = array(
                        'neq',
                        5
                    ); // 5 关闭订单
                    $condition['order_status'][] = array(
                        'neq',
                        -1
                    ); // -1 售后
                    //$condition['vgsr_status'] = 2;
                } elseif ($order_status == 10) {// 拼团，已支付未成团订单
                    $condition['vgsr_status'] = 1;
                } elseif ($order_status == 11) {// 拼团，已支付未成团订单
                    $condition['store_id'] = ['>', 0];
                    $condition['order_status'] = 1;
                } else {
                    $condition['order_status'] = $order_status;
                }
            } else {
//                //不包括售后订单
//                $condition['order_status'] = array(
//                    '>=',
//                    0
//                );
            }
            if (!empty($payment_type)) {
                $condition['payment_type'] = $payment_type;
            }
            if (!empty($user)) {
                $condition['receiver_name|receiver_mobile|user_name|buyer_id'] = array(
                    'like',
                    '%' . $user . '%'
                );
            }
            if (!empty($order_no)) {
                $condition['order_no'] = array(
                    'like',
                    '%' . $order_no . '%'
                );
            }
            if ($delivery_order_status){
                $condition['delivery_order_status'] = $delivery_order_status;
            }
            if ($express_order_status){
                $condition['express_order_status'] = $express_order_status;
            }
            if ($order_id_array) {
                $condition['order_id'] = ['IN', $order_id_array];
            }
            if ($shipping_type) {
                $condition['shipping_type'] = $shipping_type;
            }
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            if (request()->post('order_amount')){
                $condition['order_amount'] = true;
            }
            if (request()->post('order_memo')){
                $condition['order_memo'] = true;
            }
            $list = $order_service->getOrderList($page_index, $page_size, $condition, 'create_time desc');
            return $list;
        } else {
            $status = request()->get('order_status', '9');
            $order_no = request()->get('order_no', '');
            $this->assign('status', $status);
            $this->assign('order_no', $order_no);
            $orderServer = new OrderType();
            $orderTypeList = $orderServer->getOrderTypeList();
            $this->assign('orderTypeList', $orderTypeList);
            return view($this->style . 'Order/orderList');
        }
    }

    /*     * 售后订单
     */

    public function afterOrderList() {
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $start_date = request()->post('start_date') == '' ? 0 : strtotime(request()->post('start_date'));
            $end_date = request()->post('end_date') == '' ? 0 : strtotime(request()->post('end_date'));
            $pay_start_date = request()->post('pay_start_date') == '' ? 0 : strtotime(request()->post('pay_start_date'));
            $pay_end_date = request()->post('pay_end_date') == '' ? 0 : strtotime(request()->post('pay_end_date'));
            $consign_start_date = request()->post('consign_start_date') == '' ? 0 : strtotime(request()->post('consign_start_date'));
            $consign_end_date = request()->post('consign_end_date') == '' ? 0 : strtotime(request()->post('consign_end_date'));
            $finish_start_date = request()->post('finish_start_date') == '' ? 0 : strtotime(request()->post('finish_start_date'));
            $finish_end_date = request()->post('finish_end_date') == '' ? 0 : strtotime(request()->post('finish_end_date'));
            $user = request()->post('user', '');
            $order_no = request()->post('order_no', '');
            $refund_status = request()->post('refund_status', '9');
            $payment_type = request()->post('payment_type', 1);
            $express_no = request()->post('express_no', '');
            $goods_name = request()->post('goods_name', '');
            $order_type = request()->post('order_type', '');
            $condition['is_deleted'] = 0; // 未删除订单
            if ($express_no) {
                $condition['express_no'] = ['LIKE', '%' . $express_no . '%'];
            }
            if ($goods_name) {
                $condition['goods_name'] = ['LIKE', '%' . $goods_name . '%'];
            }
            if ($order_type) {
                $condition['order_type'] = $order_type;
            }
            if ($start_date) {
                $condition['create_time'][] = ['>=', $start_date];
            }
            if ($end_date) {
                $condition['create_time'][] = ['<=', $end_date + 86399];
            }
            if ($pay_start_date) {
                $condition['pay_time'][] = ['>=', $pay_start_date];
            }
            if ($pay_end_date) {
                $condition['pay_time'][] = ['<=', $pay_end_date + 86399];
            }
            if ($consign_start_date) {
                $condition['consign_time'][] = ['>=', $consign_start_date];
            }
            if ($consign_end_date) {
                $condition['consign_time'][] = ['<=', $consign_end_date + 86399];
            }
            if ($finish_start_date) {
                $condition['finish_time'][] = ['>=', $finish_start_date];
            }
            if ($finish_end_date) {
                $condition['finish_time'][] = ['<=', $finish_end_date + 86399];
            }
            /* 筛选退款状态
             * 待买家操作 2,-3
             * 待卖家操作 1 3 4
             * 已退款  5
             * 已拒绝 -3,-1
             */
            switch ($refund_status) {
                case '':
                    $condition['refund_status'] = [1, 2, 3, 4, 5, -3];
                    break;
                case 0:
                    $condition['refund_status'] = [2, -3];
                    break;
                case 1:
                    $condition['refund_status'] = [1, 3, 4];
                    break;
                case 2:
                    $condition['refund_status'] = [5];
                    break;
                case 3:
                    $condition['refund_status'] = [-1, -3];
                    break;
                default :
                    //全部列表只显示需要售后操作的订单商品,2018/12/06 改为还是显示全部
                    $condition['refund_status'] = [1, 2, 3, 4, 5, -3];
            }

            if (!empty($payment_type)) {
                $condition['payment_type'] = $payment_type;
            }
            if (!empty($user)) {
                $condition['receiver_name|receiver_mobile|user_name|buyer_id'] = array(
                    'like',
                    '%' . $user . '%'
                );
            }
            if (!empty($order_no)) {
                $condition['order_no'] = array(
                    'like',
                    '%' . $order_no . '%'
                );
            }
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            $order_service = new OrderService();
            $list = $order_service->getOrderList($page_index, $page_size, $condition, 'create_time desc');

            return $list;
        } else {
            $refund_status = request()->get('refund_status', '9');
            $this->assign("refund_status", $refund_status);
            $orderServer = new OrderType();
            $orderTypeList = $orderServer->getOrderTypeList();
            $this->assign('orderTypeList', $orderTypeList);
            return view($this->style . "Order/afterOrderList");
        }
    }

    public function orderStatus()
    {
        $type = request()->post('type/a');
        $return_data = [];
        if (in_array('common', $type)) {
            $status = OrderStatus::getOrderCommonStatus();
            // -1：售后；5：已关闭
            unset($status[-1], $status[5]);
            $return_data['common'] = $status;
        }
        if (in_array('delivery_print', $type)) {
            $status = OrderStatus::deliveryPrintStatus();
            $return_data['delivery_print'] = array_merge(['0' => ['status_id' => 0, 'status_name' => '全部']], $status);
        }
        if (in_array('express_print', $type)) {
            $status = OrderStatus::expressPrintStatus();
            $return_data['express_print'] = array_merge(['0' => ['status_id' => 0, 'status_name' => '全部']], $status);
        }
        return $return_data;
    }

    /**
     * 发货助手 收件人列表
     */
    public function orderReceiverList()
    {
        $start_create_date = request()->post('start_create_date') == "" ? 0 : strtotime(request()->post('start_create_date'));
        $end_create_date = request()->post('end_create_date') == "" ? 0 : strtotime(request()->post('end_create_date'));
        $user = request()->post('user', '');
        $order_no = request()->post('order_no', '');
        $order_status = request()->post('order_status', '');
        $express_no = request()->post('express_no', '');
        $goods_name = request()->post('goods_name', '');
        $express_order_status = request()->post('express_order_status');
        $delivery_order_status = request()->post('delivery_order_status');
        $condition['is_deleted'] = 0; // 未删除订单
        if ($express_no) {
            $condition['express_no'] = ['LIKE', '%' . $express_no . '%'];
        }
        if ($goods_name) {
            $condition['goods_name'] = ['LIKE', '%' . $goods_name . '%'];
        }
        if ($start_create_date) {
            $condition['create_time'][] = ['>=', $start_create_date];
        }
        if ($end_create_date) {
            $condition['create_time'][] = ['<=', $end_create_date + 86399];
        }
        if ($order_status != '') {
            // $order_status 1 待发货
            if ($order_status == 1) {
                // 订单状态为待发货实际为已经支付未完成还未发货的订单
                $condition['shipping_status'] = 0; // 0 待发货
                $condition['pay_status'] = 2; // 2 已支付
                $condition['store_id'] = 0; // 2 已支付
                $condition['order_status'][] = array(
                    'neq',
                    4
                ); // 4 已完成
                $condition['order_status'][] = array(
                    'neq',
                    5
                ); // 5 关闭订单
                $condition['order_status'][] = array(
                    'neq',
                    -1
                ); // 5 售后订单
                $condition['vgsr_status'] = 2;
            } elseif ($order_status == 10) {// 拼团，已支付未成团订单
                $condition['vgsr_status'] = 1;
            } elseif ($order_status == 11) {// 拼团，已支付未成团订单
                $condition['store_id'] = ['>', 0];
                $condition['order_status'] = 1;
            } else {
                $condition['order_status'] = $order_status;
            }
        }
        if (!empty($user)) {
            $condition['receiver_name|receiver_mobile|user_name'] = array(
                'like',
                '%' . $user . '%'
            );
        }
        if (!empty($order_no)) {
            $condition['order_no'] = array(
                'like',
                '%' . $order_no . '%'
            );
        }
        if ($express_order_status) {
            $condition['express_order_status'] = $express_order_status;
        }
        if ($delivery_order_status) {
            $condition['delivery_order_status'] = $delivery_order_status;
        }
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $fields = '';
        $order_service = new OrderService();
        $list = $order_service->getOrderReceiverList(1, 0, $condition, 'create_time desc', $fields);
        return $list;
    }

    /**
     * 发货助手获取打印数据
     * @return array|\multitype
     */
    public function printOrderList()
    {
        $order_goods_id_array = request()->post('order_goods_id_array/a');
        if (empty($order_goods_id_array)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $order_service = new OrderService();
        $list = $order_service->printOrderList(['order_goods_id' => ['IN', $order_goods_id_array]], ['order']);
        return $list;
    }

    /**
     * 发货助手增加打印次数
     */
    public function addPrintTimes()
    {
        $type = request()->post('type');
        $order_goods_id_array = request()->post('order_goods_id_array/a');
        if (empty($type) || empty($order_goods_id_array)){
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $order_service = new OrderService();
        $result = $order_service->addPrintTimes($type,$order_goods_id_array);
        return AjaxReturn($result);
    }

    /**
     * 功能说明：获取打印出货单预览信息
     */
    public function getOrderInvoiceView() {
        $shop_id = $this->instance_id;
        // 获取值
        $orderIdArray = request()->get('ids', '');
        // 操作
        $order_service = new OrderService();
        $goods_express_list = $order_service->getOrderGoodsExpressDetail($orderIdArray, $shop_id);
        // 返回信息
        return $goods_express_list;
    }

    /**
     * 订单详情
     *
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function orderDetail() {
        $order_id = request()->get('order_id', 0);
        if ($order_id == 0) {
            $this->error("没有获取到订单信息");
        }
        $order_service = new OrderService();
        $detail = $order_service->getOrderDetail($order_id);
//        if (empty($detail)) {
//            $this->error("没有获取到订单信息");
//        }
//        if (!empty($detail['operation'])) {
//            $operation_array = $detail['operation'];
//            foreach ($operation_array as $k => $v) {
//                if ($v['no'] == 'logistics' || $v['no'] == 'order_close' || $v['no'] == 'adjust_price' || $v['no'] == 'delete_order') {
//                    unset($operation_array[$k]);
//                }
//            }
//            $detail['operation'] = $operation_array;
//        }
        $detail['order_money'] += $detail['invoice_tax'];
        $detail['order_money'] =  '¥' .$detail['order_money'];
        if($detail['presell_id']){
            if($detail['payment_type']==16 && $detail['money_type']==1){
                $member_account = new VslBlockChainRecordsModel();
                $pay_cash = $member_account->getInfo(['data_id'=>$detail['out_trade_no']],'cash')['cash'];
                $detail['pay_money'] = $pay_cash.'ETH';
            }
            if($detail['payment_type']==17 && $detail['money_type']==1){
                $member_account = new VslBlockChainRecordsModel();
                $pay_cash = $member_account->getInfo(['data_id'=>$detail['out_trade_no']],'cash')['cash'];
                $detail['pay_money'] = $pay_cash.'EOS';
            }
            if($detail['payment_type']==16 && $detail['money_type']==2){
                $member_account = new VslBlockChainRecordsModel();
                $pay_cash = $member_account->getInfo(['data_id'=>$detail['out_trade_no']],'cash')['cash'];
                if($detail['payment_type_presell']==16){
                    $pay_cash1 = $member_account->getInfo(['data_id'=>$detail['out_trade_no_presell']],'cash')['cash'];
                    $detail['order_money'] = $pay_cash.'ETH +'.$pay_cash1.'ETH';
                } else if($detail['payment_type_presell']==17){
                    $pay_cash1 = $member_account->getInfo(['data_id'=>$detail['out_trade_no_presell']],'cash')['cash'];
                    $detail['order_money'] = $pay_cash.'ETH +'.$pay_cash1.'EOS';
                }else{
                    $detail['order_money'] = $pay_cash.'ETH + ¥ '. $detail['final_money'];
                }
            }
            if($detail['payment_type_presell']==16 && $detail['money_type']==2){
                if($detail['payment_type']!=16 && $detail['payment_type']!=17){
                    $member_account = new VslBlockChainRecordsModel();
                    $pay_cash1 = $member_account->getInfo(['data_id'=>$detail['out_trade_no_presell']],'cash')['cash'];
                    $detail['order_money'] = '¥ ' .$detail['first_money'].'+'.$pay_cash1.'ETH';
                }
            }
            if($detail['payment_type_presell']==17 && $detail['money_type']==2){
                if($detail['payment_type']!=16 && $detail['payment_type']!=17){
                    $member_account = new VslBlockChainRecordsModel();
                    $pay_cash1 = $member_account->getInfo(['data_id'=>$detail['out_trade_no_presell']],'cash')['cash'];
                    $detail['order_money'] = '¥ ' .$detail['first_money'].'+'.$pay_cash1.'EOS';
                }
            }
            if($detail['payment_type']==17 && $detail['money_type']==2){
                $member_account = new VslBlockChainRecordsModel();
                $pay_cash = $member_account->getInfo(['data_id'=>$detail['out_trade_no']],'cash')['cash'];
                if($detail['payment_type_presell']==16){
                    $pay_cash1 = $member_account->getInfo(['data_id'=>$detail['out_trade_no_presell']],'cash')['cash'];
                    $detail['order_money'] = $pay_cash.'EOS +'.$pay_cash1.'ETH';
                } else if($detail['payment_type_presell']==17){
                    $pay_cash1 = $member_account->getInfo(['data_id'=>$detail['out_trade_no_presell']],'cash')['cash'];
                    $detail['order_money'] = $pay_cash.'EOS +'.$pay_cash1.'EOS';
                }else{
                    $detail['order_money'] = $pay_cash.'EOS + ¥ '. $detail['final_money'];
                }
            }
        }else{
            if($detail['payment_type']==16){
                $detail['order_money'] = $detail['coin'].'ETH';
            }
            if($detail['payment_type']==17){
                $detail['order_money'] = $detail['coin'].'EOS';
            }
        }
        if (empty($detail)) {
            $this->error("没有获取到订单信息");
        }
        if (!empty($detail['operation'])) {
            $operation_array = $detail['operation'];
            foreach ($operation_array as $k => $v) {
                if ($v['no'] == 'logistics' || $v['no'] == 'order_close' || $v['no'] == 'adjust_price' || $v['no'] == 'delete_order') {
                    unset($operation_array[$k]);
                }
            }
            $detail['operation'] = $operation_array;
        }

        $this->assign('pre_url', $_SERVER['HTTP_REFERER']);
        $this->assign("order", $detail);
        return view($this->style . "Order/orderDetail");
    }

    /**
     * 订单退款详情
     */
    public function orderRefundDetail() {
        $order_goods_id = request()->get('itemid', 0);
        if ($order_goods_id == 0) {
            $this->error("没有获取到退款信息");
        }
        $order_service = new OrderService();
        $info = $order_service->getOrderGoodsRefundInfo($order_goods_id);
        $refund_account_records = $order_service->getOrderRefundAccountRecordsByOrderGoodsId($order_goods_id);
        $remark = ''; // 退款备注，只有在退款成功的状态下显示
        if (!empty($refund_account_records)) {
            if (!empty($refund_account_records['remark'])) {

                $remark = $refund_account_records['remark'];
            }
        }
        $order_goods = new OrderGoods();
        // 退款余额
        $refund_balance = $order_goods->orderGoodsRefundBalance($order_goods_id);
        $this->assign("refund_balance", sprintf("%.2f", $refund_balance));
        $this->assign('order_goods', $info);
        $this->assign("remark", $remark);

        return view($this->style . "Order/orderRefundDetail");
    }

    /**
     * 线下支付
     */
    public function orderOffLinePay() {
        $order_service = new OrderService();
        $order_id = request()->post('order_id', '');
        $payment_type = request()->post('payment_type', '');
        $seller_memo = request()->post('seller_memo', '');
        if (empty($order_id) || empty($payment_type)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }

//        $order_info = $order_service->getOrderInfo($order_id);
//        if ($order_info['payment_type'] == 6) {
//            $res = $order_service->orderOffLinePay($order_id, 6, 0);
//        } else {
//            $res = $order_service->orderOffLinePay($order_id, 10, 0);
//        }
        $retval = $order_service->orderOffLinePay($order_id, $payment_type, 0);
        if ($retval['code'] > 0) {
            $this->addUserLog('后台操作线下支付', $order_id);
        }
        if ($seller_memo){
            $memo_data['order_id'] = $order_id;
            $memo_data['uid'] = $this->uid;
            $memo_data['memo'] = $seller_memo;
            $memo_data['create_time'] = time();
            $order_service->addOrderSellerMemoNew($memo_data);
        }

        return $retval;
    }

    /**
     * 交易完成
     *
     * @param unknown $orderid
     * @return Exception
     */
    public function orderComplete() {
        $order_service = new OrderService();
        $order_id = request()->post('order_id', '');
        $retval = $order_service->orderComplete($order_id);
        if ($retval) {
            $this->addUserLog('后台操作交易完成', $order_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 交易关闭
     */
    public function orderClose() {
        $order_service = new OrderService();
        $order_id = request()->post('order_id', '');
        $retval = $order_service->orderClose($order_id);
        if ($retval) {
            $this->addUserLog('后台操作交易关闭', $order_id);
        }
        return AjaxReturn($retval);
    }

    public function orderDeliveryModal()
    {
        $order_service = new OrderService();
        $express_service = new ExpressService();
        $address_service = new AddressService();
        $order_id = request()->get('order_id');
        $order_info = $order_service->getOrderDetail($order_id);
        $order_info['address'] = $address_service->getAddress($order_info['receiver_province'], $order_info['receiver_city'], $order_info['receiver_district']);
        // 快递公司列表
        $express_company_list = $express_service->getExpressCompanyList(1, 0, $this->website_id, $this->instance_id, ['nr.website_id' => $this->website_id, 'nr.shop_id' => $this->instance_id], '')['data'];
        // 订单商品项
        $order_goods_list = $order_service->getOrderGoods($order_id);
        $this->assign('order_info', $order_info);
        $this->assign('express_company_list', $express_company_list);
        $this->assign('order_goods_list', $order_goods_list);
        return view($this->style . "Order/sendGoods");
    }

    public function orderUpdateShippingModal()
    {
        $order_service = new OrderService();
        $express_service = new ExpressService();
        $address_service = new AddressService();
        $order_id = request()->get('order_id', '');
        $order_info = $order_service->getOrderDetail($order_id);
        $order_info['address'] = $address_service->getAddress($order_info['receiver_province'], $order_info['receiver_city'], $order_info['receiver_district']);
        // 快递公司列表
        $express_company_list = $express_service->getExpressCompanyList(1, 0, $this->website_id, $this->instance_id, ['nr.website_id' => $this->website_id, 'nr.shop_id' => $this->instance_id], '')['data'];
        // 订单商品项
        $order_goods_list = $order_service->getOrderGoods($order_id);
        $package_list = [];
        foreach ($order_info['goods_packet_list'] as $v) {
            $package_list[$v['express_id']] = $v;
        }
        $this->assign('package_list', $package_list);
        $this->assign('order_info', $order_info);
        //var_dump(reset($order_info['goods_packet_list']));exit;
        $this->assign('express_company_list', $express_company_list);
        $this->assign('order_goods_list', $order_goods_list);
        return view($this->style . "Order/updateShipping");
    }

    /**
     * 订单发货 所需数据
     */
    public function orderDeliveryData() {
        $order_service = new OrderService();
        $express_service = new ExpressService();
        $address_service = new AddressService();
        $order_id = request()->post('order_id', '');
        $order_info = $order_service->getOrderDetail($order_id);
        $order_info['address'] = $address_service->getAddress($order_info['receiver_province'], $order_info['receiver_city'], $order_info['receiver_district']);
        // 快递公司列表
        $express_company_list = $express_service->getExpressCompanyList(1, 0, $this->website_id, $this->instance_id, ['nr.website_id' => $this->website_id, 'nr.shop_id' => $this->instance_id], '')['data'];
        // 订单商品项
        $order_goods_list = $order_service->getOrderGoods($order_id);
        $data['order_info'] = $order_info;
        $data['express_company_list'] = $express_company_list;
        $data['order_goods_list'] = $order_goods_list;
        return $data;
    }

    /**
     * 订单发货
     */
    public function orderDelivery() {
        $order_service = new OrderService();
        $order_id = request()->post('order_id', '');
        $order_goods_id_array = request()->post('order_goods_id_array', '');//$order_goods_id_array是一个逗号隔开的string，不是数组
        $express_name = request()->post('express_name', '');
        $shipping_type = request()->post('shipping_type', '');
        $express_company_id = request()->post('express_company_id', '');
        $express_no = trim(request()->post('express_no', ''));
        if(!$express_no){
            return AjaxReturn(0);
        }
        $express_no = str_replace(' ', '', $express_no);
        $memo = request()->post('seller_memo');
        $order_info = $order_service->getOrderDetail($order_id);
        if($order_info['order_status'] !=1){
            return  ['code' => -1,'message' => '操作失败，订单状态已改变'];
        }
        if ($shipping_type == 1) {
            //需要物流
            $retval = $order_service->orderDelivery($order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no);
            if ($retval) {
                $this->addUserLog('订单发货需要物流', '订单id(' . $order_id . '),订单商品id(' . $order_goods_id_array . '),物流信息(' . $express_no . ')');
            }
        } else {
            //无需物流
            $retval = $order_service->orderGoodsDelivery($order_id, $order_goods_id_array);
            if ($retval) {
                $this->addUserLog('订单发货无需物流', '订单id(' . $order_id . '),订单商品id(' . $order_goods_id_array . ')');
            }
        }
        if (!empty($memo)) {
            $data['order_id'] = $order_id;
            $data['memo'] = $memo;
            $data['uid'] = $this->uid;
            $data['create_time'] = time();
            $order_service->addOrderSellerMemoNew($data);
        }

        return AjaxReturn($retval);
    }

    /**
     * 订单修改物流信息
     */
    public function updateOrderDelivery() {
        $order_service = new OrderService();
        $id = request()->post('id');
        $order_id = request()->post('order_id');
        $express_company_id = request()->post('express_company_id');
        $express_company = request()->post('express_company');
        $express_no = trim(request()->post('express_no', ''));
        if(!$express_no){
            return AjaxReturn(0);
        }
        $express_no = str_replace(' ', '', $express_no);
        $express_name = request()->post('express_name');

        $data['express_company_id'] = $express_company_id;
        $data['express_company'] = $express_company;
        $data['express_name'] = $express_name;
        $data['express_no'] = $express_no;
        $retval = $order_service->updateDelivery($id, $data);
        if ($retval) {
            $this->addUserLog('订单修改物流信息', '订单id(' . $order_id . '),' . '物流单号(' . $express_no . ')');
        }
        unset($data);
        $memo = request()->post('seller_memo');
        if (!empty($memo)) {
            $data['order_id'] = $order_id;
            $data['memo'] = $memo;
            $data['uid'] = $this->uid;
            $data['create_time'] = time();
            $order_service->addOrderSellerMemoNew($data);
        }
        return AjaxReturn($retval);
    }

    /**
     * 获取订单大订单项
     */
    public function getOrderGoods() {
        $order_id = request()->post('order_id', '');
        $order_service = new OrderService();
        $order_goods_list = $order_service->getOrderGoods($order_id);
        $order_info = $order_service->getOrderInfo($order_id);
        $list[0] = $order_goods_list;
        $list[1] = $order_info;
        return $list;
    }

    /**
     * 获取订单数量
     *
     * @return array
     * 待买家操作 2,-3
     * 待卖家操作 1 3 4
     * 已退款  5
     * 已拒绝 -3,-1
     *
     */
    public function getOrderGoodsCount()
    {
        $orderGoods = new OrderGoods();
        $order_count_array = array();
        $order_count_array['waiting_buyer_operation'] = $orderGoods->getOrderGoodsCount(['nog.refund_status' => ['IN', [2, -3]], 'no.website_id' => $this->website_id, 'no.shop_id' => $this->instance_id, 'no.is_deleted' => 0]); //待买家操作
        $order_count_array['waiting_seller_operation'] = $orderGoods->getOrderGoodsCount(['nog.refund_status' => ['IN', [1, 3, 4]], 'no.website_id' => $this->website_id, 'no.shop_id' => $this->instance_id, 'no.is_deleted' => 0]); //待卖家操作
        $order_count_array['have_refunded'] = $orderGoods->getOrderGoodsCount(['nog.refund_status' => 5, 'no.website_id' => $this->website_id, 'no.shop_id' => $this->instance_id, 'no.is_deleted' => 0]); //已退款
        $order_count_array['have_refused'] = $orderGoods->getOrderGoodsCount(['nog.refund_status' => ['IN', [-1, -3]], 'no.website_id' => $this->website_id, 'no.shop_id' => $this->instance_id, 'no.is_deleted' => 0]); //已拒绝
        //$order_count_array['close'] = $orderGoods->getOrderGoodsCount(['nog.refund_status' => -2, 'no.website_id' => $this->website_id, 'no.shop_id' => $this->instance_id, 'no.is_deleted' => 0]);//已关闭
        $order_count_array['all'] = $orderGoods->getOrderGoodsCount(['nog.refund_status' => ['IN', [-1, -3, 1, 2, 3, 4, 5]], 'no.website_id' => $this->website_id, 'no.shop_id' => $this->instance_id, 'no.is_deleted' => 0]); //全部
        return $order_count_array;
    }

    /**
     * 订单价格调整
     */
    public function orderAdjustMoney() {
        $order_id = request()->post('order_id', '');
        $order_goods_id_adjust_array = request()->post('order_goods_id_adjust_array', '');
        $shipping_fee = request()->post('shipping_fee', '');
        $memo = request()->post('memo');
        $order_service = new OrderService();
        $retval = $order_service->orderMoneyAdjust($order_id, $order_goods_id_adjust_array, $shipping_fee);
        if ($retval) {
            $this->addUserLog('订单价格调整', '订单id(' . $order_id . ')');
        }
        if (!empty($memo)) {
            $data['order_id'] = $order_id;
            $data['memo'] = $memo;
            $data['uid'] = $this->uid;
            $data['create_time'] = time();
            $order_service->addOrderSellerMemoNew($data);
        }
        return AjaxReturn($retval);
    }

    /**
     * 卖家同意买家退款申请
     *
     * @return number
     */
    public function orderGoodsRefundAgree() {
        $order_id = request()->post('order_id');
        $order_goods_id = request()->post('order_goods_id/a');
        $return_id = request()->post('return_id', '');
        if (empty($order_goods_id) || empty(reset($order_goods_id))){
            $order_goods_model = new VslOrderGoodsModel();
            $order_goods_info = $order_goods_model::all(['order_id' => $order_id]);
            $order_goods_id = [];
            foreach ($order_goods_info as $v){
                $order_goods_id[] = $v->order_goods_id;
            }
        }
        if (empty($order_id) || empty($order_goods_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $order_service = new OrderService();
        $retval = $order_service->orderGoodsRefundAgree($order_id, $order_goods_id,$return_id);
        if ($retval) {
            $this->addUserLog('卖家同意买家退款申请', '订单id(' . $order_id . ')');
        }
        // 修改发票状态
        if (getAddons('invoice', $this->website_id, $this->instance_id)) {
            $invoice = new InvoiceService();
            $invoice->updateOrderStatusByOrderId($order_id, 2);//关闭发票状态
        }
        return AjaxReturn($retval);
    }

    /**
     * 卖家永久拒绝本次退款
     *
     * @return Ambigous <number, Exception>
     */
    public function orderGoodsRefuseForever() {
        $order_id = request()->post('order_id');
        $order_goods_id = request()->post('order_goods_id/a');
        if (empty($order_goods_id) || empty(reset($order_goods_id))){
            $order_goods_model = new VslOrderGoodsModel();
            $order_goods_info = $order_goods_model::all(['order_id' => $order_id]);
            $order_goods_id = [];
            foreach ($order_goods_info as $v){
                $order_goods_id[] = $v->order_goods_id;
            }
        }
        $reason = request()->post('reason');
        if (empty($order_id) || empty($order_goods_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $order_service = new OrderService();
        $retval = $order_service->orderGoodsRefuseForever($order_id, $order_goods_id, $reason);
        if ($retval) {
            $this->addUserLog('卖家永久拒绝本次退款', '订单id(' . $order_id . ')');
        }
        return AjaxReturn($retval);
    }

    /**
     * 卖家拒绝本次退款
     *
     * @return Ambigous <number, Exception>
     */
    public function orderGoodsRefuseOnce() {
        $order_id = request()->post('order_id', '');
        $order_goods_id = request()->post('order_goods_id/a', '');
        if (empty($order_goods_id) || empty(reset($order_goods_id))){
            $order_goods_model = new VslOrderGoodsModel();
            $order_goods_info = $order_goods_model::all(['order_id' => $order_id]);
            $order_goods_id = [];
            foreach ($order_goods_info as $v){
                $order_goods_id[] = $v->order_goods_id;
            }
        }
        $reason = request()->post('reason');
        if (empty($order_id) || empty($order_goods_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $order_service = new OrderService();
        $retval = $order_service->orderGoodsRefuseOnce($order_id, $order_goods_id, $reason);
        if ($retval) {
            $this->addUserLog('卖家拒绝本次退款', '订单id(' . $order_id . ')');
        }
        return AjaxReturn($retval);
    }

    /**
     * 卖家确认收货(从买家回退的商品)
     *
     * @return Ambigous <number, Exception>
     */
    public function orderGoodsConfirmReceive() {
        $order_service = new OrderService();
        $order_id = request()->post('order_id');
        $order_goods_id = request()->post('order_goods_id/a');
        if (empty($order_goods_id) || empty(reset($order_goods_id))){
            $order_goods_model = new VslOrderGoodsModel();
            $order_goods_info = $order_goods_model::all(['order_id' => $order_id]);
            $order_goods_id = [];
            foreach ($order_goods_info as $v){
                $order_goods_id[] = $v->order_goods_id;
            }
        }
        if (empty($order_id) || empty($order_goods_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $retval = $order_service->orderGoodsConfirmReceive($order_id, $order_goods_id);
        if ($retval) {
            $this->addUserLog('卖家确认收货(从买家回退的商品)', '订单id(' . $order_id . ')');
        }
        return AjaxReturn($retval);
    }

    /**
     * 卖家确认退款
     *
     * @return array <Exception, unknown>
     */
    public function orderGoodsConfirmRefund() {
        $order_id = request()->post('order_id');
        $order_goods_id = request()->post('order_goods_id/a');
        $refundtype = 0;
        if (empty($order_goods_id) || empty(reset($order_goods_id))){
            $order_goods_model = new VslOrderGoodsModel();
            $order_goods_info = $order_goods_model::all(['order_id' => $order_id]);
            $order_goods_id = [];
            
            foreach ($order_goods_info as $v){
                $order_goods_id[] = $v->order_goods_id;
            }
            
            $refundtype = 1;
        }else{
            //单商品 订单 导致误以为部分退款
            $order_goods_model_good = new VslOrderGoodsModel();
            $order_goods_info_good = $order_goods_model_good::all(['order_id' => $order_id]);
            $order_goods_id_good = [];
            foreach ($order_goods_info_good as $v){
                $order_goods_id_good[] = $v->order_goods_id;
            }
            if(count($order_goods_id_good) == 1){
                $refundtype = 1;
            }
        }
        if (empty($order_id) || empty($order_goods_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $order_service = new OrderService();
        
        
        if($order_id){
            $order_model = new VslOrderModel();
            $payment_type = $order_model->getInfo(['order_id'=>$order_id],'payment_type,payment_type_presell');
            if($payment_type['payment_type']==16 || $payment_type['payment_type']==17 || $payment_type['payment_type_presell']==16 || $payment_type['payment_type_presell']==17){
                $retval = $order_model->save(['shop_after'=>1],['order_id'=>$order_id]);
                if($retval){
                    return ['code' => 2, 'message' => 'eth支付和eos支付的订单退款需要商家处理'];
                }
            }
        }
        $retval = $order_service->orderGoodsConfirmRefund($order_id, $order_goods_id,$refundtype);
        if ($retval['code'] == 1) {
            $this->addUserLog('卖家确认退款', '订单id(' . $order_id . ')');
        }
        // 修改发票状态
        if (getAddons('invoice', $this->website_id, $this->instance_id)) {
            $invoice = new InvoiceService();
            $invoice->updateOrderStatusByOrderId($order_id, 2);//关闭发票状态
        }
        return $retval;
    }

    /**
     * 获取物流信息
     */
    public function logisticsInfo()
    {
        $no = request()->post('no');
        //查询收货人手机号后四位
        $order_goods_express = new VslOrderGoodsExpressModel();
        $order = new VslOrderModel();
        $order_id = $order_goods_express->getInfo(['express_no' => $no, 'website_id' => $this->website_id], 'order_id')['order_id'];
        $receiver_phone = $order->getInfo(['order_id' => $order_id], 'receiver_mobile')['receiver_mobile'];
        $com = request()->post('com');
        $result = getShipping($no,$com,'auto',$this->website_id, $receiver_phone);
        return $result;
    }


    /**
     * 订单列表物流信息modal
     */
    public function logisticsModal()
    {
        if (input('post.order_id')) {
            $return_data = [];
            $order_model = new VslOrderModel();
            $receiver_mobile = $order_model->getInfo(['order_id' => input('post.order_id')], 'receiver_mobile')['receiver_mobile'];
            //获取手机号后四位
            $receiver_phone = substr($receiver_mobile, 7);
            if(input('post.order_goods_id')){
                $order_goods_model = new VslOrderGoodsModel();
                $logistics_list = $order_goods_model::all(['order_id' => input('post.order_id'), 'order_goods_id' => input('post.order_goods_id'), 'website_id' => $this->website_id]);
                foreach ($logistics_list as $v){
                    $return_data[] = getShipping($v['refund_shipping_code'],$v['refund_shipping_company'],'auto',$this->website_id, $receiver_phone);
                }
            }else{
                $order_goods_express_model = new VslOrderGoodsExpressModel();
                $logistics_list = $order_goods_express_model::all(['order_id' => input('post.order_id'), 'website_id' => $this->website_id]);
                foreach ($logistics_list as $v){
                    $return_data[] = getShipping($v['express_no'],$v['express_company_id'],'auto',$this->website_id, $receiver_phone);
                }
            }
            return $return_data;
        }
        $this->assign('order_id', input('get.order_id'));
        $this->assign('order_goods_id', input('get.order_goods_id'));
        return view($this->style . 'Order/logisticsList');
    }

    /**
     * 添加备注
     */
    public function addMemo() {
        $order_service = new OrderService();
        $data['order_id'] = request()->post('order_id');
        $data['memo'] = request()->post('memo');
        $data['uid'] = $this->uid;
        $data['create_time'] = time();
        //$result = $order_service->addOrderSellerMemo($order_id, $memo);
        $retval = $order_service->addOrderSellerMemoNew($data);
        if ($retval) {
            $this->addUserLog('添加备注', '订单id(' . $data['order_id'] . ')');
        }
        return AjaxReturn($retval);
    }

    /**
     * 获取订单备注信息
     *
     * @return unknown
     */
    public function getOrderSellerMemo() {
        $order_service = new OrderService();
        $order_id = request()->post('order_id');
        $res = $order_service->getOrderMemoNew(['order_id' => $order_id]);
        return $res;
    }

    /**
     * 获取修改收货地址的信息
     *
     * @return string
     */
    public function getOrderUpdateAddress() {
        $order_service = new OrderService();
        $order_id = request()->post('order_id');
        $res = $order_service->getOrderReceiveDetail($order_id);
        return $res;
    }

    /**
     * 修改收货地址的信息
     *
     * @return string
     */
    public function updateOrderAddress() {
        $order_service = new OrderService();
        $order_id = request()->post('order_id', '');
        $receiver_name = request()->post('receiver_name', '');
        $receiver_mobile = request()->post('receiver_mobile', '');
        $receiver_zip = request()->post('receiver_zip', '');
        $receiver_province = request()->post('seleAreaNext', '');
        $receiver_city = request()->post('seleAreaThird', '');
        $receiver_district = request()->post('seleAreaFouth', '');
        $receiver_address = request()->post('address_detail', '');
        $retval = $order_service->updateOrderReceiveDetail($order_id, $receiver_mobile, $receiver_province, $receiver_city, $receiver_district, $receiver_address, $receiver_zip, $receiver_name);
        if ($retval) {
            $this->addUserLog('修改收货地址的信息', '订单id(' . $order_id . ')');
        }
        return AjaxReturn($retval);
    }

    /**
     * 发货助手打印时更新改动的收货人信息-单个打印
     */
    public function updateOrdersAddress()
    {
        $order_id_array = request()->post('order_id_array/a');
        $receiver_info = request()->post('receiver_info/a');
        if (empty($order_id_array) || empty($receiver_info)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $order_service = new OrderService();
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['order_id'] = ['IN', $order_id_array];

        $data['receiver_mobile'] = $receiver_info['mobile'];
        $data['receiver_province'] = $receiver_info['province_id'];
        $data['receiver_city'] = $receiver_info['city_id'];
        $data['receiver_district'] = $receiver_info['district_id'];
        $data['receiver_address'] = $receiver_info['address'];
        $data['receiver_name'] = $receiver_info['name'];
        $result = $order_service->updateOrder($condition, $data);
        return AjaxReturn($result);
    }

    /**
     * 获取省列表
     */
    public function getProvince() {
        $address = new Address();
        $province_list = $address->getProvinceList();
        return $province_list;
    }

    /**
     * 获取城市列表
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:, array>
     */
    public function getCity() {
        $address = new Address();
        $province_id = request()->post('province_id', 0);
        $city_list = $address->getCityList($province_id);
        return $city_list;
    }

    /**
     * 获取区域地址
     */
    public function getDistrict() {
        $address = new Address();
        $city_id = request()->post('city_id', 0);
        $district_list = $address->getDistrictList($city_id);
        return $district_list;
    }

    /**
     * excel
     */
    public function dataExcel()
    {
        $basic = [
            0=>['order_type_name','订单类型'],
            1=>['order_no','订单编号'],
            2=>['goods_name','商品名称'],
            3=>['sku_name','商品规格'],
            4=>['price','商品售价'],
            5=>['num','商品数量'],
            6=>['goods_money','商品小计'],
            7=>['member_price','会员折扣优惠'],
            8=>['discount_price','限时折扣优惠'],
            9=>['manjian_money','满减优惠'],
            10=>['manjian_remark','满额赠送'],
            11=>['coupon_money','优惠券优惠'],
            12=>['adjust_money','商品调价'],
            13=>['shipping_fee','商品运费'],
            14=>['real_money','商品实付金额'],
            15=>['shipping_money','订单运费'],
            16=>['pay_money','订单实付'],
            17=>['order_status','订单状态'],
            18=>['pay_type_name','支付方式'],
            19=>['out_trade_no','支付单号'],
            20=>['user_name','会员昵称'],
            21=>['user_tel','会员手机号码'],
            22=>['receiver_name','收货人姓名'],
            23=>['receiver_mobile','收货人电话'],
            24=>['receiver_address','收货地址（完整地址）'],
            25=>['express_name','快递公司'],
            26=>['express_no','快递单号'],
            27=>['store_name','核销门店'],
            28=>['assistant_name','核销员'],
            29=>['buyer_message','订单备注'],
            30=>['create_time','下单时间'],
            31=>['pay_time','付款时间'],
            32=>['consign_time','发货时间'],
            33=>['sign_time','收货时间'],
            34=>['finish_time','完成时间']
        ];
        $more[47] = ['receiver_addressB','收货地址（省市区独立）'];
        $more[48] = ['cost_price','成本价'];
        $more[49] = ['goods_code','商品编号'];
        $more[50] = ['item_no','商品货号'];
        $this->assign("basic", $basic);
        $this->assign("more", $more);
        return view('admin/Order/orderExportDialog');
    }
    
    /**
     * excel模板
     */
    public function excelTemplate()
    {
        $excel = new Excel();
        $condition['template_type'] = 1;
        $result = $excel->getExcelList($condition);
        $list = [];
        if($result){
            foreach ($result as $k=>$v) {
                $list[$v['template_id']] = $v;
                $list[$v['template_id']]['ids'] = explode(',',substr($v['ids'], 0, -1));
            }
        }
        return ['code' => 1,'message' => '获取成功','data' => $list];
    }
    
    /**
     * excel模板
     */
    public function editExcelTemplate()
    {
        $excel = new Excel();
        $template_id = (int)input('post.template_id');
        $input['template_name'] = input('post.template_name');
        $input['ids'] = input('post.ids');
        if($template_id>0){
            $where['template_id'] = $template_id;
            $result = $excel->updateExcel($input,$where);
        }else{
            $input['template_type'] = 1;
            $input['shop_id'] = $this->instance_id;
            $input['website_id'] = $this->website_id;
            $result =  $excel->addExcel($input);
        }
        return AjaxReturn($result);
    }
    
    /**
     * 删除excel模板
     */
    public function deleteExcel()
    {
        $excel = new Excel();
        $condition['template_id'] = (int)input('post.template_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $result = $excel->deleteExcel($condition);
        return AjaxReturn($result);
    }
    
    /**
     * 订单数据excel导出
     */
    public function orderDataExcel() {
        try {
            $xlsName = '订单数据列表';
            $xlsCells = [
                0=>['order_type_name','订单类型'],
                1=>['order_no','订单编号'],
                2=>['goods_name','商品名称'],
                3=>['sku_name','商品规格'],
                4=>['price','商品售价'],
                5=>['num','商品数量'],
                6=>['goods_money','商品小计'],
                7=>['member_price','会员折扣优惠'],
                8=>['discount_price','限时折扣优惠'],
                9=>['manjian_money','满减优惠'],
                10=>['manjian_remark','满额赠送'],
                11=>['coupon_money','优惠券优惠'],
                12=>['adjust_money','商品调价'],
                13=>['shipping_fee','商品运费'],
                14=>['real_money','商品实付金额'],
                15=>['shipping_money','订单运费'],
                16=>['pay_money','订单实付'],
                17=>['order_status','订单状态'],
                18=>['pay_type_name','支付方式'],
                19=>['out_trade_no','支付单号'],
                20=>['user_name','会员昵称'],
                21=>['user_tel','会员手机号码'],
                22=>['receiver_name','收货人姓名'],
                23=>['receiver_mobile','收货人电话'],
                24=>['receiver_address','收货地址（完整地址）'],
                25=>['express_name','快递公司'],
                26=>['express_no','快递单号'],
                27=>['store_name','核销门店'],
                28=>['assistant_name','核销员'],
                29=>['buyer_message','订单备注'],
                30=>['create_time','下单时间'],
                31=>['pay_time','付款时间'],
                32=>['consign_time','发货时间'],
                33=>['sign_time','收货时间'],
                34=>['finish_time','完成时间'],
                47=>['receiver_addressB','收货地址（省市区独立）'],
                48 => ['cost_price','成本价'],
                49 => ['goods_code','商品编号'],
                50 => ['item_no','商品货号']
            ];
            $start_date = request()->get('start_date') == '' ? 0 : strtotime(request()->get('start_date'));
            $end_date = request()->get('end_date') == '' ? 0 : strtotime(request()->get('end_date'));
            $pay_start_date = request()->get('pay_start_date') == '' ? 0 : strtotime(request()->get('pay_start_date'));
            $pay_end_date = request()->get('pay_end_date') == '' ? 0 : strtotime(request()->get('pay_end_date'));
            $consign_start_date = request()->get('consign_start_date') == '' ? 0 : strtotime(request()->get('consign_start_date'));
            $consign_end_date = request()->get('consign_end_date') == '' ? 0 : strtotime(request()->get('consign_end_date'));
            $finish_start_date = request()->get('finish_start_date') == '' ? 0 : strtotime(request()->get('finish_start_date'));
            $finish_end_date = request()->get('finish_end_date') == '' ? 0 : strtotime(request()->get('finish_end_date'));
            $user_info = request()->get('user_info', '');
            $order_no = request()->get('order_no', '');
            $order_status = request()->get('order_status', '');
            $payment_type = request()->get('payment_type', 1);
            $express_no = request()->get('express_no', '');
            $goods_name = request()->get('goods_name', '');
            $refund_status = request()->get('refund_status');
            $ids = request()->get('ids', '');
            $type = request()->get('type', '');
            if ($express_no) {
                $condition['express_no'] = ['LIKE', '%' . $express_no . '%'];
            }
            if ($goods_name) {
                $condition['goods_name'] = ['LIKE', '%' . $goods_name . '%'];
            }
            if ($start_date) {
                $condition['create_time'][] = ['>=', $start_date];
            }
            if ($end_date) {
                $condition['create_time'][] = ['<=', $end_date + 86399];
            }
            if ($pay_start_date) {
                $condition['pay_time'][] = ['>=', $pay_start_date];
            }
            if ($pay_end_date) {
                $condition['pay_time'][] = ['<=', $pay_end_date + 86399];
            }
            if ($consign_start_date) {
                $condition['consign_time'][] = ['>=', $consign_start_date];
            }
            if ($consign_end_date) {
                $condition['consign_time'][] = ['<=', $consign_end_date + 86399];
            }
            if ($finish_start_date) {
                $condition['finish_time'][] = ['>=', $finish_start_date];
            }
            if ($finish_end_date) {
                $condition['finish_time'][] = ['<=', $finish_end_date + 86399];
            }
            $xlsCell = [];
            if($ids){
                $ids = explode(',',$ids);
                foreach ($ids as $v) {
                    if(!empty($xlsCells[$v]))$xlsCell[] = $xlsCells[$v];
                }
            }
            if ($order_status) {
                // $order_status 1 待发货
                if ($order_status == 1) {
                    // 订单状态为待发货实际为已经支付未完成还未发货的订单
                    $condition['shipping_status'] = 0; // 0 待发货
                    $condition['pay_status'] = 2; // 2 已支付
                    $condition['order_status'][] = ['neq', 4]; // 4 已完成
                    $condition['order_status'][] = ['neq', 5]; // 5 关闭订单
                    $condition['order_status'][] = ['neq', -1]; // -1 售后订单
                } else {
                    $condition['order_status'] = $order_status;
                }
            }
            if (!empty($payment_type)) {
                $condition['payment_type'] = $payment_type;
            }
            if (!empty($user_info)) {
                if (is_numeric($user_info)) {
                    $condition['receiver_mobile'] = $user_info;
                } else {
                    $condition['receiver_name'] = $user_info;
                }
            }
            if (!empty($order_no)) {
                $condition['order_no'] = $order_no;
            }
            if($type==3){//售后订单
                $xlsName = "售后订单数据列表";
                $refund_status = request()->get('refund_status','');
                switch ($refund_status) {
                    case '':
                        break;
                    case 0:
                        $condition['refund_status'] = [2, -3];
                        break;
                    case 1:
                        $condition['refund_status'] = [1, 3, 4];
                        break;
                    case 2:
                        $condition['refund_status'] = [5];
                        break;
                    case 3:
                        $condition['refund_status'] = [-1, -3];
                        break;
                    default :
                        //全部列表只显示需要售后操作的订单商品,2018/12/06 改为还是显示全部
                        $condition['refund_status'] = [1, 2, 3, 4, 5, -3,-1];
                }
            }
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            $condition['is_deleted'] = 0; // 未删除订单
            $order_service = new OrderService();
            $list = $order_service->getOrderList(1, 0, $condition, 'create_time desc');
            $data = [];
            $key = 0;
            foreach ($list["data"] as $k => $v) {
                $data[$key]["shipping_money"] = $v['shipping_money'];
                $data[$key]["pay_money"] = ($v['user_platform_money']>0)?$v['user_platform_money']:$v['pay_money'];
                $data[$key]["order_status"] = $v['status_name'];
                $data[$key]["user_name"] = $v['user_name'];
                $data[$key]["user_tel"] = $v['user_tel']."\t";
                $data[$key]["receiver_name"] = $v['receiver_name'];
                $data[$key]["receiver_mobile"] = $v['receiver_mobile']."\t";
                $data[$key]["receiver_address"] = $v['receiver_province_name'].$v['receiver_city_name'].$v['receiver_district_name'].$v['receiver_address'];
                $data[$key]["buyer_message"] = $v['buyer_message'];
                $data[$key]["create_time"] = getTimeStampTurnTime($v["create_time"]);
                $data[$key]["pay_time"] = getTimeStampTurnTime($v["pay_time"]);
                $data[$key]["consign_time"] = getTimeStampTurnTime($v["consign_time"]);
                $data[$key]["sign_time"] = getTimeStampTurnTime($v["sign_time"]);
                $data[$key]["finish_time"] = getTimeStampTurnTime($v["finish_time"]);
                $data[$key]["store_name"] = $v["store_name"];
                $data[$key]["assistant_name"] = $v["assistant_name"];
                $data[$key]["manjian_remark"] = $v["manjian_remark"];
                $data[$key]["pay_type_name"] = $v["pay_type_name"];
                $data[$key]["out_trade_no"] = $v["out_trade_no"]."\t";
                $data[$key]["receiver_addressB"]['receiver_province_name'] = $v["receiver_province_name"];
                $data[$key]["receiver_addressB"]['receiver_city_name'] = $v["receiver_city_name"];
                $data[$key]["receiver_addressB"]['receiver_district_name'] = $v["receiver_district_name"];
                $data[$key]["receiver_addressB"]['receiver_address'] = $v["receiver_address"];
                foreach ($v["order_item_list"] as $t => $m) {
                    $data[$key]["order_type_name"] = $v["order_type_name"];
                    $data[$key]["order_no"] = $v["order_no"]."\t";
                    $data[$key]["goods_name"] = $m["goods_name"];
                    $data[$key]["express_name"] = $m["express_name"];
                    $data[$key]["express_no"] = $m["express_no"]."\t";
                    $data[$key]["sku_name"] = $m["sku_name"];
                    $data[$key]["price"] = $m["price"];
                    $data[$key]["num"] = $m["num"];
                    $data[$key]["goods_money"] = $m["goods_money"];
                    $data[$key]["member_price"] = $m["member_price"]-$m["price"];
                    $data[$key]["discount_price"] = $m["member_price"]-$m["discount_price"];
                    $data[$key]["manjian_money"] = $m["manjian_money"];
                    $data[$key]["coupon_money"] = $m["coupon_money"];
                    $data[$key]["adjust_money"] = $m["adjust_money"];
                    $data[$key]["shipping_fee"] = $m["shipping_fee"];
                    $data[$key]["real_money"] = $m["real_money"];
                    $data[$key]["cost_price"] = $m["cost_price"];
                    $data[$key]["goods_code"] = $m["goods_code"]."\t";
                    $data[$key]["item_no"] = $m["item_no"]."\t";
                    if(isset($v["order_item_list"][$t+1]))$key +=1;
                }
                $key +=1;
            }
            dataExcel($xlsName, $xlsCell, $data);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     * 收货
     */
    public function orderTakeDelivery() {
        $order_service = new OrderService();
        $order_id = request()->post('order_id', '');
        $retval = $order_service->OrderTakeDelivery($order_id);
        if ($retval) {
            $this->addUserLog('收货', '订单id(' . $order_id . ')');
        }
        return AjaxReturn($retval);
    }

    /**
     * 删除订单
     */
    public function deleteOrder() {
        if (request()->isAjax()) {
            $order_service = new OrderService();
            $order_id = request()->post("order_id", '');
            $retval = $order_service->deleteOrder($order_id, 1, $this->instance_id);
            if ($retval) {
                $this->addUserLog('删除订单', '订单id(' . $order_id . ')');
            }
            return AjaxReturn($retval);
        }
    }

    /**
     * 获取出货商品列表
     */
    public function getShippingList() {
        if (request()->isAjax()) {
            $order_ids = request()->post("order_ids", '');
            $order_goods = new OrderGoods();
            $list = $order_goods->getShippingList($order_ids);
            return $list;
        }
    }

    /**
     * 出货单打印页面
     */
    public function printpreviewOfInvoice() {
        $order_ids = request()->get("order_ids", '');
        $order_goods = new OrderGoods();
        $list = $order_goods->getShippingList($order_ids);
        $this->assign("list", $list);
        $webSiteInfo = $this->website->getWebSiteInfo();
        if (empty($webSiteInfo["title"])) {
            $ShopName = "微商来商城";
        } else {
            $ShopName = $webSiteInfo["title"];
        }
        $this->assign("ShopName", $ShopName);
        $this->assign("now_time", time());
        return view($this->style . "Order/printpreviewOfInvoice");
    }

    /**
     * 添加临时物流信息
     */
    public function addTmpExpressInformation() {
        $order_goods = new OrderGoods();
        $print_order_arr = request()->post("print_order_arr", '');
        $deliver_goods = request()->post("deliver_goods", 0);
        $print_order_arr = json_decode($print_order_arr, true);
        $retval = $order_goods->addTmpExpressInformation($print_order_arr, $deliver_goods);
        if ($retval) {
            $this->addUserLog('添加临时物流信息', $print_order_arr);
        }
        return $retval;
    }

    /**
     * 获取未发货的订单
     */
    public function getNotshippedOrderList() {
        $order_ids = request()->post("ids", '');
        $order = new OrderService();
        $list = $order->getNotshippedOrderByOrderId($order_ids);
        return $list;
    }

    /**
     * 提货
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function pickupOrder()
    {
        $order_id = request()->post("order_id", 0);
        if(!$order_id){
            return AjaxReturn(0);
        }
        $order_service = new OrderService();
        $retval = $order_service->pickupOrder($order_id);
        return AjaxReturn($retval);
    }
    
    /**
     * 获取订单数量
     *
     * @return unknown
     */
    public function getOrderCount()
    {
        $order = new OrderService();
        $order_count_array = array();
        $buyer_id = request()->post('buyer_id', '');
        if ($buyer_id) {
            $order_count_array['daifukuan'] = $order->getOrderCount(['order_status' => 0, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //代付款
            $order_count_array['daifahuo'] = $order->getOrderCount(['order_status' => 1, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //代发货
            $order_count_array['yifahuo'] = $order->getOrderCount(['order_status' => 2, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //已发货
            $order_count_array['yishouhuo'] = $order->getOrderCount(['order_status' => 3, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //已收货
            $order_count_array['yiwancheng'] = $order->getOrderCount(['order_status' => 4, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //已完成
            $order_count_array['yiguanbi'] = $order->getOrderCount(['order_status' => 5, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //已关闭
            $order_count_array['tuikuanzhong'] = $order->getOrderCount(['order_status' => -1, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //退款中
            $order_count_array['yituikuan'] = $order->getOrderCount(['order_status' => -2, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //已退款
            $order_count_array['all'] = $order->getOrderCount(['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //全部
            $order_count_array['chuli'] = $order->getOrderCount(['order_status' => 6, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'buyer_id' => $buyer_id, 'is_deleted' => 0]); //链上处理中
        } else {
            $order_count_array['daifukuan'] = $order->getOrderCount(['order_status' => 0, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0]); //代付款
            $order_count_array['daifahuo'] = $order->getOrderCount(['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0,'shipping_status' => 0, 'pay_status' => 2,'order_status' =>[['neq',4],['neq',5],['neq',-1]]]); //代发货
            $order_count_array['yifahuo'] = $order->getOrderCount(['order_status' => 2, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0]); //已发货
            $order_count_array['yishouhuo'] = $order->getOrderCount(['order_status' => 3, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0]); //已收货
            $order_count_array['yiwancheng'] = $order->getOrderCount(['order_status' => 4, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0]); //已完成
            $order_count_array['yiguanbi'] = $order->getOrderCount(['order_status' => 5, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0]); //已关闭
            $order_count_array['tuikuanzhong'] = $order->getOrderCount(['order_status' => -1, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0]); //退款中
            $order_count_array['yituikuan'] = $order->getOrderCount(['order_status' => -2, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0]); //已退款
            $order_count_array['all'] = $order->getOrderCount(['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0]); //全部
            $order_count_array['chuli'] = $order->getOrderCount(['order_status' => 6, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_deleted' => 0]); //链上处理中
//            if ($this->groupStatus) {
//                $group_server = new GroupShopping();
//                $unGroupOrderIds = $group_server->getPayedUnGroupOrder($this->instance_id,$this->website_id);
//                $order_count_array['group'] = count($unGroupOrderIds);
//                $order_count_array['daifahuo'] = $order->getOrderCount(['order_status' => 1, 'website_id' => $this->website_id, 'shop_id' => 0, 'is_deleted' => 0, 'store_id' => 0, 'order_id' => ['not in', implode(',', $unGroupOrderIds)]]); //代发货
//            }
//            if ($this->storeStatus) {
//                $order_count_array['daiquhuo'] = $order->getOrderCount(['order_status' => 1, 'website_id' => $this->website_id, 'shop_id' => 0, 'is_deleted' => 0, 'store_id' => ['>', 0]]); //代发货
//            }
        }

        return $order_count_array;
    }
}
