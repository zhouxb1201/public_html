<?php

namespace app\wapapi\controller;

use addons\blockchain\model\VslBlockChainRecordsModel;
use addons\blockchain\model\VslEosOrderPayMentModel;
use addons\blockchain\service\Block;
use addons\invoice\controller\Invoice as InvoiceController;
use addons\invoice\server\Invoice as InvoiceServer;
use addons\presell\service\Presell;
use data\model\AddonsConfigModel;
use data\model\DistrictModel;
use data\model\VslMemberRechargeModel;
use data\model\VslOrderExpressCompanyModel;
use data\model\VslOrderModel;
use data\model\UserModel;
use data\model\VslOrderPaymentModel;
use data\model\VslPresellModel;
use data\model\VslMemberCardModel;
use data\service\AddonsConfig;
use data\service\Member;
use \data\service\Order as OrderService;
use data\service\Order\Order as OrderBusiness;
use data\service\UnifyPay;
use addons\shop\model\VslShopModel;
use addons\coupontype\server\Coupon;
use think\Cookie;
use think\Db;
use think\Log;
use think\config;
use addons\seckill\server\Seckill as SeckillServer;
use data\service\Goods as GoodsService;
use addons\store\server\Store;
use think\Session;
use addons\groupshopping\server\GroupShopping;
use data\model\VslExpressCompanyShopRelationModel;
use data\model\VslExpressCompanyModel;
use data\service\Address;
use data\service\Order\OrderStatus;
use data\model\VslGoodsModel;
use data\service\promotion\GoodsExpress;
use addons\store\server\Store as storeServer;
use data\service\Pay\WeiXinPay;
use data\model\VslStoreGoodsModel;

/**
 * 商品控制器
 */
class Order extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        if (!IS_CLI) {
            if (!getUserId()) {
                echo json_encode(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登陆'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $this->uid = getUserId();
        }

    }

    /**
     * 订单列表
     *
     */
    public function orderList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size') ?: PAGESIZE;
        $order_status = request()->post('order_status');
        $search_text = request()->post('search_text');
        $condition['is_deleted'] = 0; // 未删除订单
        $condition['buy_type'][] = ['NEQ', 2];// 不显示渠道商自提订单
        $condition['buyer_id'] = $this->uid;
        $condition['website_id'] = $this->website_id;
        if (is_numeric($search_text)) {
            $condition['order_no'] = ['LIKE', '%' . $search_text . '%'];
        } elseif (strstr($search_text, 'DH')) {
            $condition['order_no'] = ['LIKE', '%' . $search_text . '%'];
        } elseif (!empty($search_text)) {
            $condition['or'] = true;
            $condition['shop_name'] = ['LIKE', '%' . $search_text . '%'];
            $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
        }

        if ($order_status != '') {
            // $order_status 1 待发货
            if ($order_status == 1) {
                // 订单状态为待发货实际为已经支付未完成还未发货的订单
                $condition['shipping_status'] = 0; // 0 待发货
                $condition['pay_status'] = 2; // 2 已支付
                $condition['order_status'][] = ['neq', 4]; // 4 已完成
                $condition['order_status'][] = ['neq', 5]; // 5 关闭订单
                $condition['order_status'][] = ['neq', -1]; // -1 售后订单
            } elseif ($order_status == -1) {
                //售后订单
                //$condition['order_status'] = ['<', 0];
                $condition['refund_status'] = [-1, -3, 1, 2, 3, 4, 5, 6];
            } elseif ($order_status == 0) {    //调试，记得换回
                //待付款
                $condition['order_status'] = 0;
            } elseif ($order_status == -2) {
                // 待评价
                $condition['is_evaluate'] = 0;
                $condition['order_status'] = ['in', [3, 4]];
            } else {
                $condition['order_status'] = $order_status;
            }
        }
        $order_service = new OrderService();
        $list = $order_service->getOrderList($page_index, $page_size, $condition, 'create_time DESC');
        $order_list = [];
        foreach ($list['data'] as $k => $order) {
            $order_list[$k]['order_id'] = $order['order_id'];
            $order_list[$k]['offline_pay'] = $order['offline_pay'];
            $order_list[$k]['order_no'] = $order['order_no'];
            if (!empty($order['presell_id']) && $this->is_presell) {
                $order_list[$k]['out_order_no'] = $order['out_trade_no'];
                $order_list[$k]['out_trade_no_presell'] = $order['out_trade_no_presell'];
            } else {
                $order_list[$k]['out_order_no'] = $order['out_trade_no'];
                $order_list[$k]['out_trade_no_presell'] = '';
            }
            $order_list[$k]['shop_id'] = $order['shop_id'];
            $order_list[$k]['shop_name'] = $order['shop_name'] ?: '';
            $order_list[$k]['pay_type_name'] = $order['pay_type_name'];
            if ($order['presell_id'] != 0 && $order['money_type'] == 2 && $this->is_presell) {//付完尾款了
                $presell_mdl = new VslPresellModel();
                $presell_info = $presell_mdl->getInfo(['id' => $order['presell_id']], '*');
                $order_list[$k]['presell_status'] = 2;//已付尾款
                $order_list[$k]['pay_type_name'] = OrderStatus::getPayType($order['payment_type']) . '+' . OrderStatus::getPayType($order['payment_type_presell']);
                if ($order['payment_type'] == 16 || $order['payment_type'] == 17) {
                    if ($order['payment_type_presell'] != 16 && $order['payment_type_presell'] != 17) {
                        $block = new VslBlockChainRecordsModel();
                        $block_info = $block->getInfo(['data_id' => $order['out_trade_no']], '*');
                        if ($block_info && $block_info['from_type'] == 4) {
                            $order_list[$k]['order_real_money'] = floatval($block_info['cash']) . 'ETH + ¥ ' . $order['final_money'];
                        } else if ($block_info && $block_info['from_type'] == 8) {
                            $order_list[$k]['order_real_money'] = floatval($block_info['cash']) . 'EOS + ¥ ' . $order['final_money'];
                        }
                        $order_list[$k]['order_money'] = $order['order_money'];
                    }
                    if ($order['payment_type_presell'] == 16 || $order['payment_type_presell'] == 17) {
                        $block = new VslBlockChainRecordsModel();
                        $block_info = $block->getInfo(['data_id' => $order['out_trade_no']], '*');
                        $block_info1 = $block->getInfo(['data_id' => $order['out_trade_no_presell']], '*');
                        if ($block_info && $block_info['from_type'] == 4 && $block_info1 && $block_info1['from_type'] == 4) {
                            $order_list[$k]['order_real_money'] = floatval($block_info['cash']) . 'ETH +' . $block_info1['cash'] . 'ETH';
                        } else if ($block_info && $block_info['from_type'] == 4 && $block_info1 && $block_info1['from_type'] == 8) {
                            $order_list[$k]['order_real_money'] = floatval($block_info['cash']) . 'ETH +' . $block_info1['cash'] . 'EOS';
                        } else if ($block_info && $block_info['from_type'] == 8 && $block_info1 && $block_info1['from_type'] == 4) {
                            $order_list[$k]['order_real_money'] = floatval($block_info['cash']) . 'EOS +' . $block_info1['cash'] . 'ETH';
                        } else if ($block_info && $block_info['from_type'] == 8 && $block_info1 && $block_info1['from_type'] == 8) {
                            $order_list[$k]['order_real_money'] = floatval($block_info['cash']) . 'EOS +' . $block_info1['cash'] . 'EOS';
                        }
                        $order_list[$k]['order_money'] = $order['order_money'];
                    }
                } else if ($order['payment_type_presell'] == 16 || $order['payment_type_presell'] == 17) {
                    if ($order['payment_type'] != 16 && $order['payment_type'] != 17) {
                        $block = new VslBlockChainRecordsModel();
                        $block_info1 = $block->getInfo(['data_id' => $order['out_trade_no_presell']], '*');
                        if ($block_info1 && $block_info1['from_type'] == 4) {
                            $order_list[$k]['order_real_money'] = '¥ ' . $order['pay_money'] . '+' . $block_info1['cash'] . 'ETH';
                        } else if ($block_info1 && $block_info1['from_type'] == 8) {
                            $order_list[$k]['order_real_money'] = '¥ ' . $order['pay_money'] . '+' . $block_info1['cash'] . 'EOS';
                        }
                        $order_list[$k]['order_money'] = $order['order_money'];
                    }
                } else {
                    $order_list[$k]['order_real_money'] = '¥ ' . $order['order_money'];
                    $order_list[$k]['order_money'] = $order['order_money'];
                }
            } elseif ($order['presell_id'] != 0 && $order['money_type'] == 0 && $this->is_presell) {//预售未付定金
                $order_list[$k]['presell_status'] = 0;//待付定金
                $order_list[$k]['order_money'] = $order['pay_money'];
                $order_list[$k]['order_real_money'] = '¥ ' . $order['order_money'];
            } elseif ($order['presell_id'] != 0 && $order['money_type'] == 1 && $this->is_presell) {//预售付完定金，待付尾款
                $order_list[$k]['order_money'] = $order['final_money'];
                $order_list[$k]['order_real_money'] = '¥ ' . $order['final_money'];
                $order_list[$k]['presell_status'] = 1;//待付尾款
            } else {
                $order_list[$k]['order_money'] = $order['order_money'];
                if ($order['payment_type'] == 16) {
                    $order_list[$k]['order_real_money'] = $order['coin'] . 'ETH';
                } elseif ($order['payment_type'] == 17) {
                    $order_list[$k]['order_real_money'] = $order['coin'] . 'EOS';
                } else {
                    $order_list[$k]['order_real_money'] = '¥ ' . $order['order_money'];
                }
            }
            $order_list[$k]['order_point'] = $order['order_point'];
            $order_list[$k]['order_type'] = $order['order_type'];
            $order_list[$k]['order_status'] = $order['order_status'];
            $order_list[$k]['card_store_id'] = $order['card_store_id'];
            if (!empty($order['status_name'])) {
                $order_list[$k]['status_name'] = $order['status_name'];
            }
            $order_list[$k]['is_evaluate'] = $order['is_evaluate'];
            if (isset($order['member_operation'])) {
                $order_list[$k]['member_operation'] = array_merge($order['member_operation'], [['no' => 'detail', 'name' => '订单详情']]);
                //如果已付定金，显示付尾款按钮
                if ($order['money_type'] == 1 && $order['pay_status'] == 0 && $order['order_status'] != 3 && $order['order_status'] != 5) {
                    $order_list[$k]['member_operation'] = array(['no' => 'last_money', 'name' => '付尾款'], ['no' => 'detail', 'name' => '订单详情']);
                }
            }
            if ($order['payment_type'] == 16 || $order['payment_type'] == 17) {
                $block = new VslBlockChainRecordsModel();
                $block_status = $block->getInfo(['data_id' => $order['out_trade_no']], 'status')['status'];
                if ($block_status == 0) {
                    $order_list[$k]['status_name'] = '链上处理中';
                    $order_list[$k]['member_operation'] = array_merge([], [['no' => 'detail', 'name' => '订单详情']]);
                }
            }
            if ($order['payment_type_presell'] == 16 || $order['payment_type_presell'] == 17) {
                $block = new VslBlockChainRecordsModel();
                $block_status = $block->getInfo(['data_id' => $order['out_trade_no_presell']], 'status')['status'];
                if ($block_status == 0) {
                    $order_list[$k]['status_name'] = '链上处理中';
                    $order_list[$k]['member_operation'] = array_merge([], [['no' => 'detail', 'name' => '订单详情']]);
                }
            }
            $order_list[$k]['promotion_status'] = ($order['promotion_money'] + $order['coupon_money'] > 0) ?: false;
            foreach ($order['order_item_list'] as $key_sku => $item) {
                $order_list[$k]['order_item_list'][$key_sku]['order_goods_id'] = $item['order_goods_id'];
                $order_list[$k]['order_item_list'][$key_sku]['goods_id'] = $item['goods_id'];
                $order_list[$k]['order_item_list'][$key_sku]['sku_id'] = $item['sku_id'];
                $order_list[$k]['order_item_list'][$key_sku]['goods_name'] = $item['goods_name'];
                $order_list[$k]['order_item_list'][$key_sku]['price'] = $item['price'];
                $order_list[$k]['order_item_list'][$key_sku]['goods_point'] = $item['goods_point'];
                $order_list[$k]['order_item_list'][$key_sku]['num'] = $item['num'];
                $order_list[$k]['order_item_list'][$key_sku]['pic_cover'] = getApiSrc($item['picture']['pic_cover']);
                $order_list[$k]['order_item_list'][$key_sku]['spec'] = $item['spec'];
                $order_list[$k]['order_item_list'][$key_sku]['status_name'] = $item['status_name'];
                $order_list[$k]['order_item_list'][$key_sku]['refund_status'] = $item['refund_status'];

                $order_list[$k]['order_item_list'][$key_sku]['member_operation'] = $item['member_operation'] ? $item['member_operation'] : [];
                //判断是否是预售
                if (!empty($order['presell_id']) && $this->is_presell) {
                    $presell = new Presell();
                    $presell_info = $presell->get_presell_by_sku($order['presell_id'], $item['sku_id']);
                    $order_list[$k]['pay_start_time'] = $presell_info['pay_start_time'];
                    $order_list[$k]['pay_end_time'] = $presell_info['pay_end_time'];
//                    $order_list[$k]['pay_money'] = $item['num']*($presell_info['allmoney']-$presell_info['firstmoney']) + $order['shipping_money'];
                }
            }
            $order_list[$k]['can_presell_pay'] = '';
            $order_list[$k]['can_presell_pay_reason'] = '';
            // 当订单需要进行整单售后时，这个字段取订单商品第一个商品的售后状态（目前正确情况，所有商品的refund_status一样），用于判断整单售后操作
            $order_list[$k]['order_refund_status'] = 0;
            if ($order_list[$k]['order_item_list']) {
                $order_list[$k]['order_refund_status'] = reset($order_list[$k]['order_item_list'])['refund_status'];
            }
            $order_list[$k]['unrefund'] = $order['unrefund'];
            $order_list[$k]['unrefund_reason'] = $order['unrefund_reason'];
            if (!empty($order['presell_id']) && $this->is_presell) {
                if (time() <= $presell_info['pay_end_time'] && time() >= $presell_info['pay_start_time']) {
                    $order_list[$k]['can_presell_pay'] = 1;
                } else {
                    $order_list[$k]['can_presell_pay'] = 0;
                    $order_list[$k]['can_presell_pay_reason'] = '预售订单付尾款时间：' . date('Y-m-d H:i:s', $order_list[$k]['pay_start_time']) . '-' . date('Y-m-d H:i:s', $order_list[$k]['pay_end_time']);
                }
            }
            $order_list[$k]['verification_code'] = '';
            $order_list[$k]['verification_qrcode'] = '';
            $order_list[$k]['store_id'] = 0;
            if ($order['store_id']) {
                $order_list[$k]['verification_code'] = $order['verification_code'];
                $order_list[$k]['verification_qrcode'] = __IMG($order['verification_qrcode']);
                $order_list[$k]['store_id'] = $order['store_id'];
            }
            //货到付款订单，在未确认收货情况下不能申请退款退货
            if ($order['order_status'] != 5) {
                if ($order['payment_type'] == 4 && ($order['order_status'] < 3 || $order['order_status'] > 4) && $order['order_status'] != -1) {
                    $order_list[$k]['unrefund'] = 1;
                    $order_list[$k]['unrefund_reason'] = '货到付款订单，在未确认收货情况下不能申请退款退货！';
                }
            }

            if ($order['order_type'] == 2 || $order['order_type'] == 3 || $order['order_type'] == 4) {
                $order_list[$k]['unrefund'] = 1;
                $order_list[$k]['unrefund_reason'] = '成为微店店主、店主等级续费和升级不能售后！';
            }
            if ($order['payment_type'] == 16 || $order['payment_type'] == 17) {
                $order_list[$k]['promotion_status'] = true;
            }
        }
        return json([
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'order_list' => $order_list,
                'page_count' => $list['page_count'],
                'total_count' => $list['total_count']
            ]
        ]);
    }

    public function getBlockChainBalance()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        if (empty($out_trade_no) || !isset($out_trade_no)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $pay = new UnifyPay();
        $pay_value = $pay->getPayInfo($out_trade_no);
        if (empty($pay_value)) {
            $data['code'] = 0;
            $data['message'] = '订单主体信息已发生变动';
            return json($data);
        }
        $data['code'] = 1;
        $data['message'] = '请求成功';

        return json(array_merge($data, getBlockChain($this->uid, $this->website_id, $pay_value['pay_money'])));
    }

    /**
     * 订单列表支付接口
     * 创建新的out_trade_no
     */
    public function orderPay()
    {
        $order_mdl = new VslOrderModel();
        $order_id = request()->post('order_id');
        $order_type_list = $order_mdl->getInfo(['order_id' => $order_id], 'order_type, money_type, out_trade_no, out_trade_no_presell, presell_id,website_id,shop_id,invoice_tax');
        if ($order_type_list['order_type'] == 7 && !$this->is_presell) {
            $data['code'] = 0;
            $data['message'] = '预售已关闭';
            return json($data);
        }
        if ($order_type_list['order_type'] == 5) {
            if (!$this->groupshopping) {
                $data['code'] = 0;
                $data['message'] = '拼团已关闭';
                return json($data);
            } else {
                $group_server = new GroupShopping();
                $checkGroup = $group_server->checkGroupIsCanByOrder($order_type_list['out_trade_no']);
                if ($checkGroup < 0) {
                    return json(AjaxReturn($checkGroup));
                }
            }
        }
        if (empty($order_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $service_order = new OrderService();
        $pay = new UnifyPay();
        if ($order_type_list['order_type'] == 7 && $this->is_presell) {//预售订单
            if ($order_type_list['money_type'] == 0) {//待付定金
                $pay_value = $pay->getPresellPayInfo($order_type_list['out_trade_no']);
                $new_out_trade_no = $order_type_list['out_trade_no'];
            } elseif ($order_type_list['money_type'] == 1) {//待付尾款
                $pay_value = $pay->getPresellPayInfo($order_type_list['out_trade_no_presell']);
                $new_out_trade_no = $order_type_list['out_trade_no_presell'];
            } elseif ($order_type_list['money_type'] == 2) {
                $data['code'] = 2;
                $data['message'] = '订单已经支付!';
                return json($data);
            }
        } else {
            $new_out_trade_no = $service_order->getOrderNewOutTradeNo($order_id);
            $pay_value = $pay->getPayInfo($new_out_trade_no);
        }
        if ($order_type_list['presell_id'] == 0) {//加上税费
            $pay_value['pay_money'] += $order_type_list['invoice_tax'];
        }
        $config_service = new \data\service\Config();
        $shop_config = $config_service->getShopConfig(0);

        $order_status = $service_order->getOrderStatusByOutTradeNo($new_out_trade_no);
        if (empty($pay_value)) {
            $data['code'] = 0;
            $data['message'] = '订单主体信息已发生变动';
            return json($data);
        }

        if ($pay_value['pay_status'] == 1) {
            // 订单已经支付
            $data['code'] = 2;
            $data['message'] = '订单已经支付或者订单价格为0.00，无需再次支付!';
            return json($data);
        }

        // 订单关闭状态下是不能继续支付的
        if ($order_status == 5) {
            $data['code'] = 0;
            $data['message'] = '订单已关闭，无需再次支付!';
            return json($data);
        }
        $config = new AddonsConfig();
        $is_presell = getAddons('presell', $order_type_list['website_id']);
        $is_seckill = getAddons('seckill', $order_type_list['website_id']);
        $is_groupshopping = getAddons('groupshopping', $order_type_list['website_id']);
        $is_bargain = getAddons('bargain', $order_type_list['website_id']);
        $data['code'] = 1;
        $data['message'] = '选择支付方式';
        $data['data']['pay_money'] = $pay_value['pay_money'];
        if ($order_type_list['order_type'] == 7 && $order_type_list['money_type'] == 1 && $is_presell) {//如果订单类型为预售并且为付尾款状态
            $presell_mdl = new VslPresellModel();
            $pay_end_time = $presell_mdl->getInfo(['id' => $order_type_list['presell_id']], 'pay_end_time')['pay_end_time'];
            if (time() > $pay_end_time) {
                $service_order->orderClose($order_id);
                $data['code'] = 0;
                $data['message'] = '预售订单支付时间已过期';
                return json($data);
            } else {
                $data['data']['end_time'] = $pay_end_time;
            }
        } elseif ($order_type_list['order_type'] == 6 && $is_seckill) {
            $info = $config->getAddonsConfig('seckill', $order_type_list['website_id']);
            $seckill_config = json_decode($info['value'], true);
            if (time() > $pay_value['create_time'] + $seckill_config['pay_limit_time'] * 60) {
                $service_order->orderClose($order_id);
                $data['code'] = 0;
                $data['message'] = '订单支付时间已过期';
                $data['data'] = null;
                return json($data);
            } else {
                $data['data']['end_time'] = $pay_value['create_time'] + $seckill_config['pay_limit_time'] * 60;
            }

        } elseif ($order_type_list['order_type'] == 5 && $is_groupshopping) {//拼团订单，关闭时间不同
            $info = $config->getAddonsConfig('groupshopping', $order_type_list['website_id']);
            $groupConfig = json_decode($info['value'], true);
            if (time() > $pay_value['create_time'] + $groupConfig['pay_time_limit'] * 60) {
                $service_order->orderClose($order_id);
                $data['code'] = 0;
                $data['message'] = '订单支付时间已过期';
                $data['data'] = null;
                return json($data);
            } else {
                $data['data']['end_time'] = $pay_value['create_time'] + $groupConfig['pay_time_limit'] * 60;
            }
        } elseif ($order_type_list['order_type'] == 8 && $is_bargain) {//砍价
            $info = $config->getAddonsConfig('bargain', $order_type_list['website_id']);
            $bargain_config = json_decode($info['value'], true);
            if (time() > $pay_value['create_time'] + $bargain_config['pay_time_limit'] * 60) {
                $service_order->orderClose($order_id);
                $data['code'] = 0;
                $data['message'] = '订单支付时间已过期';
                $data['data'] = null;
                return json($data);
            } else {
                $data['data']['end_time'] = $pay_value['create_time'] + $bargain_config['pay_time_limit'] * 60;
            }
        } else {
            $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
            $zero2 = $pay_value['create_time'];
            if ($zero1 > ($zero2 + ($shop_config['order_buy_close_time'] * 60))) {
                $data['code'] = 0;
                $data['message'] = '支付时间已过期！';
                return json($data);
            } else {
                $data['data']['end_time'] = $zero2 + ($shop_config['order_buy_close_time'] * 60);
            }
        }
        $data['data']['out_trade_no'] = $new_out_trade_no;
        $user_model = new UserModel();
        $user_info = $user_model::get(['user_model.uid' => $this->uid], ['member_account']);
        $data['data']['balance'] = $user_info->member_account->balance ?: 0;
        $data['data']['pay_password'] = !empty($user_info->payment_password) ? 1 : 0;
        /*if (isset($isChain) && in_array($isChain, ['eth', 'eos'])) {
            $data['code'] = 1;
            $data['message'] = '请求成功';
            return json(array_merge($data, getBlockChain($this->uid, $this->website_id, $data['data']['pay_money'])));
        }*/
        return json($data);
    }

    /**
     * 渠道商订单支付接口
     * 创建新的out_trade_no
     */
    public function channelOrderPay()
    {
        $order_id = request()->post('order_id');
        if (empty($order_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $service_order = new OrderService();
        $new_out_trade_no = $service_order->getChannelOrderNewOutTradeNo($order_id);

        $pay = new UnifyPay();
        $pay_value = $pay->getChannelPayInfo($new_out_trade_no);
        //获取渠道商设置时间
        $addons_config_mdl = new AddonsConfigModel();
        $channel_setting_value = $addons_config_mdl->getInfo(['addons' => 'channel', 'website_id' => $this->website_id], 'value')['value'];
        $channel_setting_arr = json_decode($channel_setting_value, true);
        $order_buy_close_time = $channel_setting_arr['channel_order_close_time'] * 60;
        $order_status = $service_order->getOrderStatusByOutTradeNo($new_out_trade_no);
        if (empty($pay_value)) {
            $data['code'] = 0;
            $data['message'] = '订单主体信息已发生变动';
            return json($data);
        }
        if ($pay_value['pay_status'] == 1) {
            // 订单已经支付
            $data['code'] = 2;
            $data['message'] = '订单已经支付或者订单价格为0.00，无需再次支付!';
            return json($data);
        }

        // 订单关闭状态下是不能继续支付的
        if ($order_status == 5) {
            $data['code'] = 0;
            $data['message'] = '订单已关闭，无需再次支付!';
            return json($data);
        }

        $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 > ($zero2 + $order_buy_close_time)) {
            $data['code'] = 0;
            $data['message'] = '订单已关闭';
            return json($data);
        } else {
            $data['code'] = 1;
            $data['message'] = '选择支付方式';
            $data['data']['pay_money'] = $pay_value['pay_money'];
            $data['data']['end_time'] = $zero2 + $order_buy_close_time;
            $data['data']['out_trade_no'] = $new_out_trade_no;

            $user_model = new UserModel();
            $user_info = $user_model::get(['user_model.uid' => $this->uid], ['member_account']);
            //判断商城开启的是哪种支付方式
            $addons_config_model = new AddonsConfigModel();
            $value = $addons_config_model->Query(['addons' => 'channel', 'website_id' => $this->website_id], 'value')[0];
            $value = json_decode($value, true);
            if ($value['pay_type'] == 0) {
                //商城支付方式
                $data['data']['balance'] = $user_info->member_account->balance ?: 0;
                $data['data']['is_proceeds'] = false;
            } elseif ($value['pay_type'] == 1) {
                //货款支付
                $data['data']['balance'] = $user_info->member_account->proceeds ?: 0;
                $data['data']['is_proceeds'] = true;
            }
            $data['data']['pay_password'] = !empty($user_info->payment_password) ? 1 : 0;
            return json($data);
        }

    }

    /**
     * 订单详情
     */
    public function orderDetail()
    {
        $order_id = request()->post('order_id');
        if (empty($order_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $district_model = new DistrictModel();
        $order_service = new OrderService();
        $order_info = $order_service->getOrderDetail($order_id);
        if ($order_info['presell_id'] == 0 || $order_info['money_type'] != 2) {//预售商品 尾款加上税费
            $order_info['order_money'] += $order_info['invoice_tax'];
        }
        if ($order_info['payment_type'] && $order_info['payment_type_presell']) {
            $order_info['payment_type_name'] = OrderStatus::getPayType($order_info['payment_type']) . '+' . OrderStatus::getPayType($order_info['payment_type_presell']);
        }
        if ($order_info['payment_type'] == 16 || $order_info['payment_type'] == 17 || $order_info['payment_type_presell'] == 16 || $order_info['payment_type_presell'] == 17) {
            $block = new VslBlockChainRecordsModel();
            $block_info = $block->getInfo(['data_id' => $order_info['out_trade_no']], '*');
            $block_info1 = $block->getInfo(['data_id' => $order_info['out_trade_no_presell']], '*');
            if ($block_info['from_type'] == 4 && $block_info1['from_type'] == 4) {
                $order_info['first_real_money'] = floatval($block_info['cash']) . 'ETH';
                $order_info['final_real_money'] = $block_info1['cash'] . 'ETH';
                $order_info['order_real_money'] = floatval($block_info['cash']) . 'ETH +' . $block_info1['cash'] . 'ETH';
            }
            if ($block_info['from_type'] == 8 && $block_info1['from_type'] == 4) {
                $order_info['first_real_money'] = floatval($block_info['cash']) . 'EOS';
                $order_info['final_real_money'] = $block_info1['cash'] . 'ETH';
                $order_info['order_real_money'] = floatval($block_info['cash']) . 'EOS +' . $block_info1['cash'] . 'ETH';
            }
            if ($block_info['from_type'] == 4 && $block_info1['from_type'] == 8) {
                $order_info['first_real_money'] = floatval($block_info['cash']) . 'ETH';
                $order_info['final_real_money'] = $block_info1['cash'] . 'EOS';
                $order_info['order_real_money'] = floatval($block_info['cash']) . 'ETH +' . $block_info1['cash'] . 'EOS';
            }
            if ($block_info['from_type'] == 8 && $block_info1['from_type'] == 8) {
                $order_info['first_real_money'] = floatval($block_info['cash']) . 'EOS';
                $order_info['final_real_money'] = $block_info1['cash'] . 'EOS';
                $order_info['order_real_money'] = floatval($block_info['cash']) . 'EOS +' . $block_info1['cash'] . 'EOS';
            }
            if ($block_info['from_type'] == 4 && $block_info1['from_type'] != 8 && $block_info1['from_type'] != 4) {
                if ($order_info['presell_id'] && $order_info['final_money']) {
                    $order_info['first_real_money'] = floatval($block_info['cash']) . 'ETH';
                    $order_info['final_real_money'] = '¥ ' . $order_info['final_money'];
                    $order_info['order_real_money'] = floatval($block_info['cash']) . 'ETH + ¥ ' . $order_info['final_money'];
                } else {
                    $order_info['order_real_money'] = $order_info['coin'] . 'ETH';
                }
            }
            if ($block_info['from_type'] != 8 && $block_info['from_type'] != 4 && $block_info1['from_type'] == 8) {
                if ($order_info['presell_id'] && $order_info['first_money']) {
                    $order_info['final_real_money'] = floatval($block_info1['cash']) . 'EOS';//第二次订单状态
                    $order_info['first_real_money'] = '¥ ' . $order_info['first_money'];
                    $order_info['order_real_money'] = '¥ ' . $order_info['first_money'] . '+' . floatval($block_info1['cash']) . 'EOS';
                }
            }
            if ($block_info['from_type'] == 8 && $block_info1['from_type'] != 8 && $block_info1['from_type'] != 4) {
                if ($order_info['presell_id'] && $order_info['final_money']) {
                    $order_info['first_real_money'] = floatval($block_info['cash']) . 'EOS';
                    $order_info['final_real_money'] = '¥ ' . $order_info['final_money'];
                    $order_info['order_real_money'] = floatval($block_info['cash']) . 'EOS + ¥ ' . $order_info['final_money'];
                } else {
                    $order_info['order_real_money'] = $order_info['coin'] . 'EOS';
                }
            }
            if ($block_info['from_type'] != 4 && $block_info['from_type'] != 8 && $block_info1['from_type'] == 4) {
                if ($order_info['presell_id'] && $order_info['first_money']) {
                    $order_info['final_real_money'] = floatval($block_info1['cash']) . 'ETH';
                    $order_info['first_real_money'] = '¥ ' . $order_info['first_money'];
                    $order_info['order_real_money'] = '¥ ' . $order_info['first_money'] . '+' . floatval($block_info1['cash']) . 'EOS';//第二次订单状态修改
                }
            }
            $order_info['first_money'] = '¥ ' . $order_info['first_money'];
            $order_info['final_money'] = '¥ ' . $order_info['final_money'];
        } else {
            if ($order_info['first_money'] != null) {
                $order_info['first_real_money'] = '¥ ' . $order_info['first_money'];
            } else {
                $order_info['first_real_money'] = '';
            }
            if ($order_info['final_money'] != null) {
                $order_info['final_real_money'] = '¥ ' . $order_info['final_money'];
            } else {
                $order_info['final_real_money'] = '';
            }
            $order_info['order_real_money'] = '¥ ' . $order_info['order_money'];
        }
        $order_detail['order_id'] = $order_info['order_id'];
        $order_detail['order_no'] = $order_info['order_no'];
        $order_detail['shop_name'] = $order_info['shop_name'];
        $order_detail['shop_id'] = $order_info['shop_id'];
        $order_detail['order_status'] = $order_info['order_status'];
        $order_detail['offline_pay'] = $order_info['offline_pay'];
        $order_detail['payment_type_name'] = $order_info['payment_type_name'];
        $order_detail['payment_type'] = $order_info['payment_type'];
        $order_detail['promotion_status'] = ($order_info['promotion_money'] + $order_info['coupon_money'] > 0) ?: false;
        $order_detail['order_refund_status'] = reset($order_info['order_goods'])['refund_status'];
        $order_detail['is_evaluate'] = $order_info['is_evaluate'];
        $order_detail['first_money'] = $order_info['first_money'];
        $order_detail['final_money'] = $order_info['final_money'];
        $order_detail['first_real_money'] = $order_info['first_real_money'];
        $order_detail['final_real_money'] = $order_info['final_real_money'];

        $order_detail['order_money'] = $order_info['order_money'];
        $order_detail['invoice_tax'] = $order_info['invoice_tax'];
        $order_detail['order_real_money'] = $order_info['order_real_money'];
        $order_detail['goods_money'] = $order_info['goods_money'];
        $order_detail['shipping_fee'] = $order_info['shipping_money'] - $order_info['promotion_free_shipping'];
        $order_detail['promotion_money'] = 0;
        //订单时间信息
        $order_detail['create_time'] = $order_info['create_time'];
        $order_detail['pay_time'] = $order_info['pay_time'];
        $order_detail['consign_time'] = $order_info['consign_time'];
        $order_detail['finish_time'] = $order_info['finish_time'];
        $address_info = $district_model::get($order_info['receiver_district'], ['city.province']);
        $order_detail['receiver_name'] = $order_info['receiver_name'];
        $order_detail['receiver_mobile'] = $order_info['receiver_mobile'];
        $order_detail['receiver_province'] = $address_info->city->province->province_name;
        $order_detail['receiver_city'] = $address_info->city->city_name;
        $order_detail['receiver_district'] = $address_info->district_name;
        $order_detail['receiver_address'] = $order_info['receiver_address'];
        $order_detail['buyer_message'] = $order_info['buyer_message'];
        $order_detail['group_id'] = $order_info['group_id'];
        $order_detail['group_record_id'] = $order_info['group_record_id'];
        $order_detail['store_id'] = 0;
        $order_detail['card_store_id'] = $order_info['card_store_id'];
        $order_detail['verification_code'] = '';
        $order_detail['verification_qrcode'] = '';
        $order_detail['presell_status'] = $order_info['presell_status'];
        $order_detail['order_type'] = $order_info['order_type'];
        $order_detail['deduction_money'] = $order_info['deduction_money'];
        //积分兑换订单
        $order_detail['order_point'] = $order_info['point'];
        $order_detail['can_presell_pay'] = $order_info['can_presell_pay'];
        $order_detail['can_presell_pay_reason'] = $order_info['can_presell_pay_reason'];
        if ($order_info['store_id']) {
            $order_detail['store_id'] = $order_info['store_id'];
            $order_detail['verification_code'] = $order_info['verification_code'];
            $order_detail['verification_qrcode'] = __IMG($order_info['verification_qrcode']);
            $order_detail['store_name'] = $order_info['order_pickup']['store_name'];
            $order_detail['store_tel'] = $order_info['order_pickup']['store_tel'];
            $order_detail['receiver_province'] = $order_info['order_pickup']['province_name'];
            $order_detail['receiver_city'] = $order_info['order_pickup']['city_name'];
            $order_detail['receiver_district'] = $order_info['order_pickup']['dictrict_name'];
            $order_detail['receiver_address'] = $order_info['order_pickup']['address'];
        }
        if ($order_info['payment_type'] == 6 || $order_info['shipping_type'] == 2) {
            $order_status_info = OrderService\OrderStatus::getSinceOrderStatus()[$order_info['order_status']];
        } else {
            $order_status_info = OrderService\OrderStatus::getOrderCommonStatus(1, 0, $order_info['card_store_id'], $order_info['order_goods'] ? $order_info['order_goods'][0]['goods_type'] : 0)[$order_info['order_status']];
        }
        $order_detail['is_virtual'] = 0;//是否是积分虚拟商品，用于去掉订单详情物流信息
        //积分订单 虚拟商品是没有物流信息的
        if ($order_info['order_type'] == 10) {
            if ($order_info['order_goods'][0]['goods_exchange_type'] != 0) {//都为虚拟商品
                $order_detail['is_virtual'] = 1;
                foreach ($order_status_info['member_operation'] as $m_k => $m_v) {
                    if ($m_v['no'] == 'logistics') {
                        unset($order_status_info['member_operation'][$m_k]);
                    }
                    if ($m_v['no'] == 'evaluation') {
                        unset($order_status_info['member_operation'][$m_k]);
                    }
                    if ($m_v['no'] == 'buy_again') {
                        unset($order_status_info['member_operation'][$m_k]);
                    }
                }
                $order_status_info['member_operation'] = array_values($order_status_info['member_operation']);
            }
        }
        //知识付费商品
        if ($order_info['order_goods'] && count($order_info['order_goods']) == 1) {
            if ($order_info['order_goods'][0]['goods_type'] == 4) {
                foreach ($order_status_info['member_operation'] as $m_k => $m_v) {
                    if ($m_v['no'] == 'logistics') {
                        unset($order_status_info['member_operation'][$m_k]);
                    }
                    if ($m_v['no'] == 'buy_again') {
                        unset($order_status_info['member_operation'][$m_k]);
                    }
                }
                $order_status_info['member_operation'] = array_values($order_status_info['member_operation']);
            }
        }
        if ($order_info['money_type'] == 1 && $order_info['order_type'] == 7 && $order_info['order_status'] != 5) {
            $order_status_info['member_operation'] = array(['no' => 'last_money', 'name' => '付尾款', 'icon_class' => 'icon icon-pay-l']);
        } elseif ($order_info['money_type'] == 2 && $order_info['order_type'] == 7 && $order_info['order_status'] != 5) {
        } elseif ($order_info['order_status'] == 1 && $order_info['goods_packet_list']) {
            $order_status_info['member_operation'] = array(['no' => 'logistics', 'name' => '查看物流', 'icon_class' => 'icon icon-preview-l',]);
        }
        $order_detail['member_operation'] = $order_status_info['member_operation'];
        $order_detail['no_delivery_id_array'] = [];
        foreach ($order_info['order_goods_no_delive'] as $v_goods) {
            $order_detail['no_delivery_id_array'][] = $v_goods['order_goods_id'];
        }

        $goods_packet_list = [];
        foreach ($order_info['goods_packet_list'] as $k => $v_packet) {
            $goods_packet_list[$k]['packet_name'] = $v_packet['packet_name'];
//            $goods_packet_list[$k]['express_name'] = $v_packet['express_name'];
//            $goods_packet_list[$k]['express_code'] = $v_packet['express_code'];
            $goods_packet_list[$k]['shipping_info'] = $v_packet['shipping_info'];
            $goods_packet_list[$k]['order_goods_id_array'] = [];
            foreach ($v_packet['order_goods_list'] as $k_o => $v_goods) {
                $goods_packet_list[$k]['order_goods_id_array'][] = $v_goods['order_goods_id'];
            }
        }
        $order_detail['goods_packet_list'] = $goods_packet_list;
        $order_detail['goods_type'] = 0;
        if ($order_info['order_goods']) {
            $order_detail['goods_type'] = $order_info['order_goods'][0]['goods_type'];
        }
        $order_goods = [];
        foreach ($order_info['order_goods'] as $k => $v) {
            $order_goods[$k]['order_goods_id'] = $v['order_goods_id'];
            $order_goods[$k]['goods_id'] = $v['goods_id'];
            $order_goods[$k]['goods_name'] = $v['goods_name'];
            $order_goods[$k]['sku_id'] = $v['sku_id'];
            $order_goods[$k]['sku_name'] = $v['sku_name'];
            $order_goods[$k]['price'] = $v['price'];
            $order_goods[$k]['goods_point'] = $v['goods_point'];
            $order_goods[$k]['num'] = $v['num'];
            $order_goods[$k]['refund_status'] = $v['refund_status'];
            $order_goods[$k]['spec'] = $v['spec'];
            $order_goods[$k]['pic_cover'] = $v['picture_info']['pic_cover'] ? getApiSrc($v['picture_info']['pic_cover']) : '';
            $order_goods[$k]['member_operation'] = [];
            if ($order_info['payment_type'] == 16 || $order_info['payment_type'] == 17 || $order_info['payment_type_presell'] == 16 || $order_info['payment_type_presell'] == 17) {
                $order_detail['promotion_status'] = true;
            }
            $order_detail['promotion_money'] += round(($v['price'] - $v['actual_price'] + $v['adjust_money']) * $v['num'], 2) + $v['promotion_free_shipping'];
            //积分兑换订单是没有售后的
            if (!in_array($order_info['order_type'], [2, 3, 4, 10]) && !in_array($order_info['order_status'], [4, 5])) {
                if ($v['refund_status'] != 0) {
                    $refund_info = OrderService\OrderStatus::getRefundStatus()[$v['refund_status']];
                    if (!$order_detail['promotion_status']) {
                        $order_goods[$k]['member_operation'] = $refund_info['member_operation'];
                    } else {
                        $temp_member_refund_operation = $refund_info['member_operation'];
                    }
                } else {
                    if ($order_info['invoice_type'] > 0) {//税费，一笔订单多个商品不能单个商品退款
                        $temp_member_refund_operation = array_merge($order_goods[$k]['member_operation'], $order_status_info['refund_member_operation']);
                        $order_goods[$k]['member_operation'] = [];
                    } else if (!$order_detail['promotion_status'] && is_array($order_status_info['refund_member_operation'])) {
                        $order_goods[$k]['member_operation'] = array_merge($order_goods[$k]['member_operation'], $order_status_info['refund_member_operation']);
                    } else {
                        $temp_member_refund_operation = $order_status_info['refund_member_operation'];
                    }
                }
            }
        }
        //修正订单优惠金额显示异常
        if ($order_info['presell_id'] != 0 && $order_info['money_type'] == 1) {//预售定金不扣除税费
            $order_detail['promotion_money'] = $order_info['goods_money'] + $order_info['shipping_money'] - $order_info['promotion_free_shipping'] - $order_info['order_money'] + $order_info['order_adjust_money'];
        } else {
            $order_detail['promotion_money'] = $order_info['goods_money'] + $order_info['shipping_money'] - $order_info['promotion_free_shipping'] - ($order_info['order_money'] - $order_info['invoice_tax']) + $order_info['order_adjust_money'];//上面总价计算时候加了税费，优惠计算需要除去税费计算
        }

        if ($order_info['deduction_money'] > 0) {
            $order_detail['promotion_money'] = "{$order_detail['promotion_money']}" - "{$order_info['deduction_money']}";
        }

        if (!empty($temp_member_refund_operation)) {
            $order_detail['member_operation'] = array_merge($order_detail['member_operation'], $temp_member_refund_operation);
        }
        if ($order_info['order_status'] == 6) {
            $order_detail['member_operation'] = [];
            $order_detail['status_name'] = '链上处理中';
        }
        $order_detail['order_goods'] = $order_goods;
        $order_detail['unrefund'] = $order_info['unrefund'];
        $order_detail['unrefund_reason'] = $order_info['unrefund_reason'];
        //获取发票信息
        if (getAddons('invoice', $this->website_id, $this->instance_id)) {
            $invoiceController = new InvoiceController();
            $order_detail['invoice'] = $invoiceController->getInvoiceInfoByOrderNo($order_detail['order_no']);
        }
        return json(['code' => 1,
            'message' => '获取成功',
            'data' => $order_detail
        ]);
    }

    public function orderShippingInfo()
    {
        $order_id = request()->post('order_id');
        if (empty($order_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }

        $order_service = new OrderService();
        $express_company_model = new VslOrderExpressCompanyModel();
        $order_info = $order_service->getOrderDetail($order_id);
        $order_detail['order_no'] = $order_info['order_no'];

        $order_detail['no_delivery_id_array'] = [];
        foreach ($order_info['order_goods_no_delive'] as $v_goods) {
            $order_detail['no_delivery_id_array'][] = $v_goods['order_goods_id'];
        }

        $goods_packet_list = [];
        foreach ($order_info['goods_packet_list'] as $k => $v_packet) {
            $goods_packet_list[$k]['packet_name'] = $v_packet['packet_name'];
//            $goods_packet_list[$k]['express_name'] = $v_packet['express_name'];
//            $goods_packet_list[$k]['express_code'] = $v_packet['express_code'];
            $express_company_info = $express_company_model::get(['company_name' => $v_packet['shipping_info']['expTextName']]);
            $goods_packet_list[$k]['express_company_logo'] = $express_company_info['express_logo'];
            $goods_packet_list[$k]['shipping_info'] = $v_packet['shipping_info'] ?: (object)[];
            $goods_packet_list[$k]['order_goods_id_array'] = [];
            foreach ($v_packet['order_goods_list'] as $k_o => $v_goods) {
                $goods_packet_list[$k]['order_goods_id_array'][] = $v_goods['order_goods_id'];
            }
        }
        $order_detail['goods_packet_list'] = $goods_packet_list;

        $order_goods = [];
        foreach ($order_info['order_goods'] as $k => $v) {
            $order_goods[$k]['order_goods_id'] = $v['order_goods_id'];
            $order_goods[$k]['goods_id'] = $v['goods_id'];
            $order_goods[$k]['goods_name'] = $v['goods_name'];
            $order_goods[$k]['sku_id'] = $v['sku_id'];
            $order_goods[$k]['sku_name'] = $v['sku_name'];
            $order_goods[$k]['price'] = $v['price'];
            $order_goods[$k]['num'] = $v['num'];
            $order_goods[$k]['spec'] = $v['spec'];
            $order_goods[$k]['pic_cover'] = $v['picture_info']['pic_cover'] ? getApiSrc($v['picture_info']['pic_cover']) : '';
        }

        $order_detail['order_goods'] = $order_goods;

        return json(['code' => 1,
            'message' => '获取成功',
            'data' => $order_detail
        ]);
    }

    /**
     * 订单评价
     */
    public function addOrderEvaluate()
    {
        $order = new OrderService();
        $order_id = request()->post('order_id');
        $goods_evaluate = request()->post('goods_evaluate/a');
        $shop_desc = request()->post('shop_desc');
        $shop_service = request()->post('shop_service');
        $store_service = request()->post('store_service');
        $shop_stic = request()->post('shop_stic');
        if (empty($order_id) || empty($goods_evaluate)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $user_model = new UserModel();
        $order_model = new VslOrderModel();
        $order_info = $order_model::get($order_id);
        $user_info = $user_model::get($this->uid);
        if ($shop_desc || $shop_service || $shop_stic) {
            if ($this->is_shop) {
                $data_shop = array(
                    'order_id' => $order_id,
                    'order_no' => $order_info['order_no'],
                    'website_id' => $this->website_id,
                    'shop_id' => $order_info['shop_id'],
                    'shop_desc' => ($shop_desc > 5 || $shop_desc < 0) ? 5 : $shop_desc,
                    'shop_service' => ($shop_service > 5 || $shop_service < 0) ? 5 : $shop_service,
                    'shop_stic' => ($shop_stic > 5 || $shop_stic < 0) ? 5 : $shop_stic,
                    'add_time' => time(),
                    'member_name' => $user_info['user_name'],
                    'uid' => $this->uid,
                );
                $order->addShopEvaluate($data_shop);
            }

        }
        if (getAddons('store', $this->website_id, $order_info['shop_id']) && ($order_info['store_id'] || $order_info['card_store_id'])) {
            $storeServer = new Store();
            $data_store = array(
                'order_id' => $order_id,
                'order_no' => $order_info['order_no'],
                'website_id' => $this->website_id,
                'shop_id' => $order_info['shop_id'],
                'store_id' => $order_info['store_id'] ?: $order_info['card_store_id'],
                'store_service' => ($store_service > 5 || $store_service < 0) ? 5 : $store_service,
                'add_time' => time(),
                'member_name' => $user_info['user_name'],
                'uid' => $this->uid,
            );
            $storeServer->addStoreEvaluate($data_store);
        }
        $dataArr = array();
        foreach ($goods_evaluate as $key => $evaluate) {
            $orderGoods = $order->getOrderGoodsInfo($evaluate['order_goods_id']);
            $data = array(
                'order_id' => $order_id,
                'order_no' => $order_info['order_no'],
                'order_goods_id' => $evaluate['order_goods_id'],
                'website_id' => $orderGoods['website_id'],
                'goods_id' => $orderGoods['goods_id'],
                'goods_name' => $orderGoods['goods_name'],
                'goods_price' => $orderGoods['goods_money'],
                'goods_image' => $orderGoods['goods_picture'],
                'shop_id' => $orderGoods['shop_id'],
                'content' => $evaluate['content'],
                'addtime' => time(),
                'image' => (!empty($evaluate['images']) && is_array($evaluate['images'])) ? implode(",", $evaluate['images']) : '',
                'member_name' => $user_info['user_name'],
                'explain_type' => ($evaluate['explain_type'] > 5 || $evaluate['explain_type'] < 0) ? 1 : $evaluate['explain_type'],
                'uid' => $this->uid,
            );
            $dataArr[] = $data;
        }
        //var_dump($dataArr);exit;
        $result = $order->addGoodsEvaluate($dataArr, $order_id);
        if ($result) {
            return json(['code' => 1, 'message' => '成功评价']);
        } else {
            return json(['code' => -1, 'message' => '评价失败']);
        }
    }

    /**
     * 点单追评
     */
    public function addOrderEvaluateAgain()
    {
        $order = new OrderService();
        $order_id = request()->post('order_id');
        $goods_evaluate = request()->post('goods_evaluate/a');
        if (empty($order_id) || empty($goods_evaluate)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }

        $result = true;
        foreach ($goods_evaluate as $key => $evaluate) {
            $images = (!empty($evaluate['images']) && is_array($evaluate['images'])) ? implode(",", $evaluate['images']) : '';

            $res = $order->addGoodsEvaluateAgain($evaluate['content'], $images, $evaluate['order_goods_id']);
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
        if ($result) {
            return json(['code' => 1, 'message' => '成功评价']);
        } else {
            return json(['code' => -1, 'message' => '评价失败']);
        }
    }

    /**
     * 删除订单
     */
    public function deleteOrder()
    {
        $order_service = new OrderService();
        $order_id = request()->post('order_id');
        if (empty($order_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $res = $order_service->deleteOrder($order_id, 2, $this->uid);
        if ($res) {
            return json(['code' => 1, 'message' => '删除成功']);
        } else {
            return json(['code' => -1, 'message' => '删除失败']);
        }
    }

    /**
     * 收货
     */
    public function orderTakeDelivery()
    {
        $order_service = new OrderService();
        $order_id = request()->post('order_id');
        if (empty($order_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        //查询是否存在售后订单未完成 refund_status 0 正常状态 1已提交申请 -3已拒绝 4同意退款 -3拒绝打款  5同意打款而且关闭订单
        $check_status = $order_service->checkReturn($order_id);
        if ($check_status == true) {
            return ['code' => -1, 'message' => '操作失败，订单存在进行中的售后'];
        }
        $res = $order_service->OrderTakeDelivery($order_id);
        if ($res) {
            return json(['code' => 1, 'message' => '成功收货']);
        } else {
            return json(['code' => -1, 'message' => '收货失败']);
        }
    }

    /**
     * 交易关闭
     */
    public function orderClose()
    {
        $order_service = new OrderService();
        $order_id = request()->post('order_id');
        if (empty($order_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $res = $order_service->orderClose($order_id, 1);
        if ($res) {
            return json(['code' => 1, 'message' => '成功关闭']);
        } else {
            return json(['code' => -1, 'message' => '关闭失败']);
        }
    }

    /**
     * 售后页面
     */
    public function refundDetail()
    {
        $order_goods_id = request()->post('order_goods_id');
        $order_id = request()->post('order_id');
        $presell_id = request()->post('presell_id', '');
        if (!is_numeric($order_goods_id) && !is_numeric($order_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $order_service = new OrderService();
        if ($order_goods_id) {
            $condition['vsl_order_goods.order_goods_id'] = $order_goods_id;
        }
        $data['is_all'] = 0;
        if ($order_id) {
            $condition['vsl_order_goods.order_id'] = $order_id;
        }
        $condition['vsl_order_goods.buyer_id'] = $this->uid;
        if ($order_id) {
            $detail = $order_service->getOrderGoodsRefundInfoNew($condition);
        } else {
            $detail = $order_service->getOrderGoodsRefundInfoNew($condition, 1);
        }

        if (count($detail['goods_list']) == 0) {
            return json(['code' => -1, 'message' => '对不起,您无权进行此操作']);
        }
        $detail['goods_type'] = $detail['goods_list'][0]['goods_type'];
        $order_model = new VslOrderModel();
        $payment_type = $order_model->getInfo(['order_id' => $detail['order_id']], '*');
        //货到付款订单 退款需要扣除运费
        // if($payment_type['payment_type'] == 4){
        //     $detail['refund_max_money'] -= $payment_type['shipping_money'];
        // }
        foreach ($detail['goods_list'] as &$v) {
            $v['pic_cover'] = getApiSrc($v['pic_cover']);
            //如果是预售的话拿尾款金额，规格ID和预售ID获取信息
            if (!empty($order_model['presell_id'])) {
                $presell_id = $order_model['presell_id'];
                $presell = new Presell();
                $rule = $presell->get_presell_by_sku($presell_id, $v['sku_id']);
                if (!empty($rule)) {
                    $detail['refund_max_money'] = ($rule['allmoney'] - $rule['firstmoney']) * $v['num'];
                }
            }
        }
        $detail['refund_eth_money'] = '';
        $detail['refund_eth_charge'] = '';
        $detail['refund_eos_money'] = '';
        $detail['refund_eth_val'] = '';
        $detail['refund_eos_charge'] = '';
        $detail['refund_eos_val'] = '';
        $detail['eth_status'] = false;
        $detail['eos_status'] = false;
        $money = 0;
        if (($payment_type['order_status'] == -1 && $payment_type['shipping_money'] && $payment_type['shipping_status'] >= 1) || ($payment_type['order_status'] == 2 && $payment_type['shipping_money'])) {
            $money = $payment_type['shipping_money'];
        }
        $blockchain = getAddons('blockchain', $this->website_id);
        if ($blockchain) {
            $blocks = new Block();
            $block = new VslBlockChainRecordsModel();
            $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
            if ($money > 0) {
                $charge = $blocks->ethRefundMoney($money); //eth换算的运费
                $charge1 = $blocks->eosRefundMoney($money); //eos换算的运费
            } else {
                $charge = 0;
                $charge1 = 0;
            }
            $site_info = $blocks->getBlockChainSite(['website_id' => $block_info['website_id']]);
            $gas = $site_info['eth_gwei'];
            $uid = $payment_type['buyer_id'];
            $gwei1 = $blocks->ethGasFee($gas, $uid);
            $gwei = $blocks->decimalNotation($gwei1);
        }
        if ($payment_type['presell_id']) {
            if ($payment_type['payment_type'] != 16 && $payment_type['payment_type'] != 17) {
                $detail['refund_max_money'] = $payment_type['pay_money'];
            }
            if ($payment_type['payment_type_presell'] != 16 && $payment_type['payment_type_presell'] != 17) {
                $detail['refund_max_money'] = $payment_type['final_money'] - $money;
            }
            if ($payment_type['payment_type'] != 16 && $payment_type['payment_type'] != 17 && $payment_type['payment_type_presell'] != 16 && $payment_type['payment_type_presell'] != 17) {
                $detail['refund_max_money'] = $payment_type['final_money'] + $payment_type['pay_money'] - $money;
            }
            if ($payment_type['payment_type'] == 16 && $payment_type['payment_type_presell'] == 16) {
                $data['is_all'] = 1;
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 4 && $block_info1 && $block_info1['from_type'] == 4) {
                    $to_charge = floatval($block_info['to_charge'] + $block_info1['to_charge']); //商品手续费
                    $real_coin1 = floatval($block_info['gas'] + $block_info1['gas']);
                    $real_coin2 = $blocks->decimalNotation(floatval($block_info['cash'] + $block_info1['cash'] - $charge - $to_charge - $real_coin1));
                    $detail['refund_eth_money'] = $real_coin2 . 'ETH';//退款金额
                    $detail['refund_eth_charge'] = $gwei . 'ETH';//手续费（预售的支付是两笔手续费，退款又会产生两笔手续费）
                    $real_coin3 = $real_coin2 - $gwei;
                    $detail['refund_eth_val'] = ($blocks->decimalNotation($real_coin3)) . 'ETH';
                    if ($real_coin3 <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                    }
                }
            }
            if ($payment_type['payment_type'] == 16 && $payment_type['payment_type_presell'] == 17) {
                $data['is_all'] = 1;
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 4 && $block_info1 && $block_info1['from_type'] == 8) {
                    $detail['refund_eth_charge'] = $gwei . 'ETH';
                    $real_coin1 = $blocks->decimalNotation(floatval($block_info['cash'] - $block_info['gas'] - $block_info['to_charge']));
                    $detail['refund_eth_money'] = $real_coin1 . 'ETH';
                    $real_coin2 = $real_coin1 - $gwei;
                    $detail['refund_eth_val'] = ($blocks->decimalNotation($real_coin2)) . 'ETH';
                    if ($real_coin2 <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                    }
                    $real_coin3 = $block_info1['cash'] - $charge1 - $block_info1['gas'];
                    $detail['refund_eos_money'] = $real_coin3 . 'EOS';
                    if ($real_coin3 <= 0) {
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                    }
                }
            }
            if ($payment_type['payment_type'] == 17 && $payment_type['payment_type_presell'] == 16) {
                $data['is_all'] = 1;
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 8 && $block_info1 && $block_info1['from_type'] == 4) {
                    $detail['refund_eos_money'] = (floatval($block_info['cash']) - floatval($block_info['gas'])) . 'EOS';//eos退款的钱
                    $real_coin1 = $blocks->decimalNotation(floatval($block_info1['cash'] - $charge - $block_info1['gas'] - $block_info1['to_charge']));//eth退款的钱
                    $detail['refund_eth_money'] = $real_coin1 . 'ETH';
                    $detail['refund_eth_charge'] = $gwei . 'ETH';
                    $real_coin2 = $real_coin1 - $gwei;
                    $detail['refund_eth_val'] = ($blocks->decimalNotation($real_coin2)) . 'ETH';
                    if ($real_coin2 <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                    }
                }
            }
            if ($payment_type['payment_type'] == 17 && $payment_type['payment_type_presell'] == 17) {
                $data['is_all'] = 1;
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 8 && $block_info1 && $block_info1['from_type'] == 8) {
                    $real_coin1 = floatval($block_info['gas'] + $block_info1['gas']);
                    $real_coin2 = floatval($block_info['cash'] + $block_info1['cash']) - $charge1 - $real_coin1;
                    $detail['refund_eos_money'] = $real_coin2 . 'EOS';
                    if ($real_coin2 <= 0) {
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                        $detail['eth_status'] = true;
                    }
                }
            }
            if ($payment_type['payment_type'] == 16 && $payment_type['payment_type_presell'] != 16 && $payment_type['payment_type_presell'] != 17) {
                $data['is_all'] = 1;
                if ($block_info && $block_info['from_type'] == 4) {
                    $real_coin1 = $blocks->decimalNotation(floatval($block_info['cash'] - $block_info['gas'] - $block_info['to_charge']));
                    $detail['refund_eth_money'] = $real_coin1 . 'ETH';
                    $detail['refund_eth_charge'] = $gwei . 'ETH';
                    $real_coin2 = $real_coin1 - $gwei;
                    $detail['refund_eth_val'] = ($blocks->decimalNotation($real_coin2)) . 'ETH';
                    if ($real_coin2 <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        if ($payment_type['final_money'] == 0) {
                            $detail['eos_status'] = true;
                        }
                    }
                }
            }
            if ($payment_type['payment_type'] == 17 && $payment_type['payment_type_presell'] != 16 && $payment_type['payment_type_presell'] != 17) {
                $data['is_all'] = 1;
                if ($block_info && $block_info['from_type'] == 8) {
                    $real_coin = floatval($block_info['cash'] - $block_info['gas']);
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['eos_status'] = true;
                        $detail['refund_eos_money'] = '0EOS';
                        if ($payment_type['final_money'] == 0) {
                            $detail['eth_status'] = true;
                        }
                    }
                }
            }
            if ($payment_type['payment_type_presell'] == 16 && $payment_type['payment_type'] != 16 && $payment_type['payment_type'] != 17) {
                $data['is_all'] = 1;
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    $real_coin1 = $blocks->decimalNotation(floatval($block_info['cash'] - $block_info['gas'] - $block_info['to_charge'] - $charge));
                    $detail['refund_eth_money'] = $real_coin1 . 'ETH';
                    $detail['refund_eth_charge'] = $gwei . 'ETH';
                    $real_coin2 = $real_coin1 - $gwei;
                    $detail['refund_eth_val'] = ($blocks->decimalNotation($real_coin2)) . 'ETH';
                    if ($real_coin2 <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        if ($payment_type['pay_money'] == 0) {
                            $detail['eos_status'] = true;
                        }
                    }
                }
            }
            if ($payment_type['payment_type_presell'] == 17 && $payment_type['payment_type'] != 16 && $payment_type['payment_type'] != 17) {
                $data['is_all'] = 1;
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    $real_coin = floatval($block_info['cash'] - $block_info['gas']) - $charge1;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                        if ($payment_type['pay_money'] == 0) {
                            $detail['eth_status'] = true;
                        }
                    }
                }
            }

            //上面预售不知道为什么重新判断一次，其实获取订单退款信息时候已经计算好了。所以这里重新计算一次税费
            $refund_tax = 0;
            if (getAddons('invoice', $this->website_id, $this->instance_id)) {
                $invoice = new InvoiceServer();
                $invoiceConfig = $invoice->getInvoiceConfig(['website_id' => $this->website_id, 'shop_id' => $this->instance_id], 'is_refund');
                if ($invoiceConfig) {
                    $is_refund = $invoiceConfig['is_refund'];
                    if ($is_refund == 0) {//不退款，扣除税费（因为预售尾款包含了税费）
                        $refund_tax = $payment_type['invoice_tax'];
                    }
                }
            }
            $detail['refund_max_money'] -= $refund_tax;
        } else {
            if ($payment_type['payment_type'] == 16) {
                $data['is_all'] = 1;
                if ($block_info && $block_info['from_type'] == 4) {
                    $real_coin3 = $blocks->decimalNotation(floatval($block_info['cash'] - $block_info['gas'] - $block_info['to_charge']));
                    $real_coin1 = $blocks->decimalNotation(($real_coin3 - $charge));
                    $detail['refund_eth_money'] = ($real_coin1 < 0 ? $real_coin3 : $real_coin1) . 'ETH';////
                    $detail['refund_eth_charge'] = $gwei . 'ETH'; /////
                    $real_coin2 = $real_coin1 - $gwei;
                    $detail['refund_eth_val'] = ($blocks->decimalNotation($real_coin2)) . 'ETH';
                    if ($real_coin2 <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                    }
                }
            }
            if ($payment_type['payment_type'] == 17) {
                $data['is_all'] = 1;
                if ($block_info && $block_info['from_type'] == 8) {
                    $real_coin = floatval($block_info['cash']) - $charge1 - floatval($block_info['gas']);
                    $detail['refund_eos_money'] = ($real_coin < 0 ? floatval($block_info['cash'] - $block_info['gas']) : $real_coin) . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                        $detail['refund_eos_money'] = '0EOS';
                    }
                }
            }
        }

        unset($v);
        $data['refund_detail'] = $detail;
        $data['company_list'] = [];
        if ($detail['refund_type'] == 2) {
            // 物流公司,只显示后台启用的物流公司
            $comShopRela = new VslExpressCompanyShopRelationModel();
            $usedCompany = $comShopRela->Query(['website_id' => $this->website_id, 'shop_id' => $detail['shop_id']], 'co_id');
            if ($usedCompany) {
                $company = new VslOrderExpressCompanyModel();
                $data['company_list'] = $company->getViewList(1, 0, ['co_id' => ['in', $usedCompany]], '')['data'];
            }
        }
        // 查询商家或者店铺地址
        $shop_info = $order_service->getShopReturn($detail['return_id'], $detail['shop_id'], $this->website_id);
        $address = new Address();
        $province_name = $address->getProvinceName($shop_info['province']);
        $city_name = $address->getCityName($shop_info['city']);
        $dictrict_name = $address->getDistrictName($shop_info['district']);
        $shop_info['address'] = $province_name . $city_name . $dictrict_name . $shop_info['address'];
        unset($shop_info['create_time'], $shop_info['modify_time'], $shop_info['website_id'], $shop_info['city'], $shop_info['district'], $shop_info['province'], $shop_info['is_default']);
        $data['shop_info'] = $shop_info;

        return json(
            [
                'code' => 1,
                'message' => '成功获取',
                'data' => $data
            ]
        );
    }

    /**
     * 取消退款
     */
    public function cancelOrderRefund()
    {
        $orderService = new OrderService();
        $order_id = request()->post('order_id', '');
        $order_goods_id = request()->post('order_goods_id/a');
        if (empty($order_id) || empty($order_goods_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }

        $cancel_order = $orderService->orderGoodsCancel($order_id, $order_goods_id);
        return json($cancel_order);
    }

    /**
     * 退款/退货申请提交
     */
    public function refundAsk()
    {
        $order_id = request()->post('order_id');
        $order_goods_id = request()->post('order_goods_id/a');
        $refund_type = request()->post('refund_type');
        $refund_require_money = request()->post('refund_require_money');
        $refund_reason = request()->post('refund_reason');
        if (empty($order_id) || empty($order_goods_id) || empty($refund_type) || empty($refund_reason)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $order_service = new OrderService();
        $retval = $order_service->orderGoodsRefundAskfor($order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason, $this->uid);
        return json($retval);
    }

    /**
     * 买家退货
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function orderGoodsRefundExpress()
    {
        $order_id = request()->post('order_id');
        $order_goods_id = request()->post('order_goods_id/a');
        $refund_express_company = request()->post('refund_express_company');
        $refund_shipping_no = request()->post('refund_shipping_no');
        if (empty($order_id) || empty($order_goods_id) || empty($refund_shipping_no)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $order_service = new OrderService();
        $retval = $order_service->orderGoodsReturnGoods($order_id, $order_goods_id, $refund_express_company, $refund_shipping_no);
        if ($retval > 0) {
            return json(AjaxReturn($retval));
        } else {
            return json(AjaxReturn(SYSTEM_ERROR));
        }
    }

    /**
     * 创建订单
     */
    public function orderCreate($post_data = '')
    {
        $order_service = new OrderService();
        $user_model = new UserModel();
        if (empty($post_data)) {
            $post_data = request()->post('order_data/a');
        }
        //格初始提交参数
        $shipping_type = intval($post_data['shipping_type']);
        $is_deduction = intval($post_data['is_deduction']);
        $return_data['address_id'] = $post_data['address_id'];
        $order_data['group_id'] = $return_data['group_id'] = $post_data['group_id'];
        $order_data['record_id'] = $return_data['record_id'] = $post_data['record_id'];
        $return_data['shipping_type'] = $post_data['shipping_type'];
        $return_data['is_deduction'] = $post_data['is_deduction'];
        $cart_from = $post_data['cart_from'] ?: 1;//是平台购物车还是门店购物车,1平台购物车,2门店购物车
        $order_type = $post_data['order_type'];
        if ($order_type == 2 || $order_type == 3 || $order_type == 4) {
            $un_order = 1;
        } else {
            $un_order = 0;
        }
        $user_info = $user_model::get($this->uid, ['member_info.level', 'member_account', 'member_address']);
        if ($user_info->user_status == 0) {
            return ['result' => false, 'message' => '当前用户状态不能购买'];
        }
        $is_order_key = md5(json_encode($post_data));
        //判断是否是网络延迟造成多次请求
        if (Cookie::get($is_order_key)) {//有session说明创建成功了
            $cookie_data = unserialize(Cookie::get($is_order_key));
            $time = $cookie_data['create_time'] + 5;
            if (time() <= $time) {
                $message['code'] = 0;
                $message['message'] = "订单提交成功";
                $message['data']['out_trade_no'] = $cookie_data['out_trade_no'];
                Cookie::delete($is_order_key);
                return json($message);
            }
        }
        $new_sku = [];
        $presell_id = '';
        if ($post_data['shop_list']) {
            foreach ($post_data['shop_list'] as $keys => $values) {
                if ($values['store_id']) {
                    //线下自提
                    foreach ($values['goods_list'] as $k => $v) {
                        if ($v['presell_id']) {
                            $presell_id = intval($v['presell_id']);
                        }
                        if (intval($values['coupon_id'])) {
                            $rdata['coupon_id'] = intval($values['coupon_id']);
                        }
                        if (intval($v['seckill_id'])) {
                            $rdata['seckill_id'] = intval($v['seckill_id']);
                        }
                        if (intval($v['channel_id'])) {
                            $rdata['channel_id'] = 0;
                        }
                        if (intval($v['bargain_id'])) {//砍价
                            $rdata['bargain_id'] = intval($v['bargain_id']);
                            $order_data['bargain_id'] = $rdata['bargain_id'];
                        }
                        if ($v['anchor_id']) {
                            $rdata['anchor_id'] = intval($v['anchor_id']);
                        }
                        $rdata['store_id'] = $values['store_id'];
                        $rdata['sku_id'] = intval($v['sku_id']);
                        $rdata['num'] = intval($v['num']);
                        array_push($new_sku, $rdata);
                    }
                } elseif ($values['card_store_id']) {
                    //计时计次商品
                    foreach ($values['goods_list'] as $k => $v) {
                        if ($v['presell_id']) {
                            $presell_id = intval($v['presell_id']);
                        }
                        if (intval($values['coupon_id'])) {
                            $rdata['coupon_id'] = intval($values['coupon_id']);
                        }
                        if (intval($v['seckill_id'])) {
                            $rdata['seckill_id'] = intval($v['seckill_id']);
                        }
                        if (intval($v['bargain_id'])) {//砍价
                            $rdata['bargain_id'] = intval($v['bargain_id']);
                            $order_data['bargain_id'] = $rdata['bargain_id'];
                        }
                        if (intval($v['channel_id'])) {
                            $rdata['channel_id'] = 0;
                        }
                        if ($v['anchor_id']) {
                            $rdata['anchor_id'] = intval($v['anchor_id']);
                        }
                        $rdata['store_id'] = $values['card_store_id'];
                        $rdata['sku_id'] = intval($v['sku_id']);
                        $rdata['num'] = intval($v['num']);
                        array_push($new_sku, $rdata);
                    }
                } else {
                    //快递配送
                    foreach ($values['goods_list'] as $k => $v) {
                        if ($v['presell_id']) {
                            $presell_id = intval($v['presell_id']);
                        }
                        if (intval($values['coupon_id'])) {
                            $rdata['coupon_id'] = intval($values['coupon_id']);
                        }
                        if (intval($v['seckill_id'])) {
                            $rdata['seckill_id'] = intval($v['seckill_id']);
                        }
                        if (intval($v['bargain_id'])) {
                            $rdata['bargain_id'] = intval($v['bargain_id']);
                            $order_data['bargain_id'] = $rdata['bargain_id'];
                        }
                        if (intval($v['channel_id'])) {
                            $rdata['channel_id'] = intval($v['channel_id']);
                        }
                        if ($v['anchor_id']) {
                            $rdata['anchor_id'] = intval($v['anchor_id']);
                        }
                        $rdata['sku_id'] = intval($v['sku_id']);
                        $rdata['num'] = intval($v['num']);
                        array_push($new_sku, $rdata);
                        if (intval($v['channel_id'])) {
                            unset($rdata['channel_id']);
                        }
                    }
                }
                unset($rdata);
            }
        }
        $goods_service = new GoodsService();
        $record_id = $post_data['record_id'] ? intval($post_data['record_id']) : '';
        $group_id = $post_data['group_id'] ? intval($post_data['group_id']) : '';
        $msg = '';

        if($shipping_type == 2) {
            //线下自提
        $store_server = new storeServer();
            $payment_info = $store_server->paymentData($new_sku, $msg, $record_id, $group_id, $presell_id, $un_order);
        } else {
            $payment_info = $goods_service->paymentData($new_sku, $msg, $record_id, $group_id, $presell_id, $un_order);
        }
        $storeServer = getAddons('store', $this->website_id, $this->instance_id) ? new Store() : '';
        foreach ($post_data['shop_list'] as $shop) {
            $shop_id = $shop['shop_id'];
            $has_store = getAddons('store', $this->website_id, $this->instance_id) ? $storeServer->getStoreSet($shop_id)['is_use'] : 0;
            if ($has_store && empty($shop['store_id']) && $post_data['address_id'] && !$shop['has_store']) {
                $has_store = 0;
            }
            if ($post_data['shipping_type'] == 2 && $has_store && !$shop['store_id'] && empty($shop['card_store_id'])) {
                return ['result' => false, 'message' => '没有选择门店'];
            }
            if (empty($post_data['address_id']) && $post_data['shipping_type'] == 1 && !$has_store && empty($shop['card_store_id'])) {
                return ['result' => false, 'message' => '缺少收货地址'];
            }
            $payment_info[$shop_id]['store_id'] = $shop['store_id'] ?: 0;
            $payment_info[$shop_id]['card_store_id'] = (empty($shop['card_store_id'])) ? 0 : $shop['card_store_id'];
            $payment_info[$shop_id]['invoice_tax'] = isset($shop['invoice']['invoice_tax']) ? $shop['invoice']['invoice_tax'] : 0;
            $payment_info[$shop_id]['invoice_type'] = isset($shop['invoice']['type']) ? $shop['invoice']['type'] : 0;
        }
        //获取订单数据 准备处理
        if ($payment_info['code'] == -2) {
            return json($payment_info);
        }
        // 收获地址
        $address_condition['id'] = $post_data['address_id'];
        $member_service = new Member();
        $address = $member_service->getMemberExpressAddress($address_condition, ['area_province', 'area_city', 'area_district']);
        $goods_model = new VslGoodsModel();
        $return_data['total_shipping'] = 0;
        $order_business = new OrderBusiness();
        $deduction_data = [];
        $return_point_data = [];
        $goodsExpress = new GoodsExpress();
        $storeServer = $this->is_store ? new Store() : '';
        $has_store = 0;//订单商品所属店铺中是否有门店
        foreach ($payment_info as $shop_id => $shop_info) {
            //修正多店铺商品列表
            $payment_infos = $payment_info;
            foreach ($payment_infos as $kks => $vvs) {
                $storeSet = 0;
                if ($this->is_store) {
                    $storeSet = $storeServer->getStoreSet($kks)['is_use'];
                }
                $payment_info[$kks]['has_store'] = $storeSet ?: 0;
                if ($payment_info[$kks]['has_store']) {
                    $has_store = 1;
                }
                $return_data['promotion_amount'] += $vvs['member_promotion'] + $vvs['discount_promotion'] + $vvs['coupon_promotion'];
                if (isset($vvs['full_cut']) && is_array($vvs['full_cut'])) {
                    $return_data['promotion_amount'] += ($vvs['full_cut']['discount'] < $vvs['total_amount']) ? $vvs['full_cut']['discount'] : $vvs['total_amount'];
                }
                if (empty($vvs['store_id'])) {
                    $payment_info[$kks]['has_store'] = 0;
                }
                $temp_goods = [];
                foreach ($vvs['goods_list'] as $sku_id => $sku_info) {
                    $return_data['goods_amount'] += $sku_info['price'] * $sku_info['num'];
                    $payment_info[$kks]['goods_list'][$sku_id]['shipping_fee_total'] = 0;
                    if (!empty($vvs['full_cut']) &&
                        !empty((array)$vvs['full_cut']) &&
                        $vvs['full_cut']['free_shipping'] == 1 &&
                        (in_array($sku_info['goods_id'], $vvs['full_cut']['goods_limit']) || $vvs['full_cut']['range_type'] == 1)) {
                        // 有包邮的设定 && (商品在goods_limit里面 || 活动商品是全部商品)
                        $payment_info[$kks]['goods_list'][$sku_id]['shipping_fee'] = 0;
                        //获取包邮前的邮费金额
                        $temp_goodsc = [];
                        if (empty($temp_goodsc[$sku_info['goods_id']])) {
                            $temp_goodsc[$sku_info['goods_id']]['count'] = $sku_info['num'];
                            $temp_goodsc[$sku_info['goods_id']]['goods_id'] = $sku_info['goods_id'];
                        } else {
                            $temp_goodsc[$sku_info['goods_id']]['count'] += $sku_info['num'];
                        }
                        if (!empty($address['district'])) {
                            $tempgoodsc = [];
                            $tempgoodsc[$sku_info['goods_id']]['count'] = $temp_goodsc[$sku_info['goods_id']]['count'];
                            $tempgoodsc[$sku_info['goods_id']]['goods_id'] = $temp_goodsc[$sku_info['goods_id']]['goods_id'];
                            $payment_info[$kks]['goods_list'][$sku_id]['shipping_fee_total'] = $goodsExpress->getGoodsExpressTemplate($tempgoodsc, $address['district'])['totalFee'];
                        }
                        continue;
                    }
                    if (empty($temp_goods[$sku_info['goods_id']])) {
                        $temp_goods[$sku_info['goods_id']]['count'] = $sku_info['num'];
                        $temp_goods[$sku_info['goods_id']]['goods_id'] = $sku_info['goods_id'];
                    } else {
                        $temp_goods[$sku_info['goods_id']]['count'] += $sku_info['num'];
                    }
                    if (!empty($address['district'])) {
                        $tempgoods = [];
                        $tempgoods[$sku_info['goods_id']]['count'] = $temp_goods[$sku_info['goods_id']]['count'];
                        $tempgoods[$sku_info['goods_id']]['goods_id'] = $temp_goods[$sku_info['goods_id']]['goods_id'];
                        $payment_info[$kks]['goods_list'][$sku_id]['shipping_fee'] = $goodsExpress->getGoodsExpressTemplate($tempgoods, $address['district'])['totalFee'];
                    }
                }

                // 计算邮费
                $shipping_fee = 0;
                if ($temp_goods && !empty($address['district'])) {
                    $shipping_fee = $goodsExpress->getGoodsExpressTemplate($temp_goods, $address['district'])['totalFee'];
                }

                //重新划分商品运费
                $ids = [];
                $sub_point = 0;//初始积分
                if (count($payment_info[$kks]['goods_list']) > 1) {
                    foreach ($payment_info[$kks]['goods_list'] as $kv => $vv) {
                        if ($vv['shipping_fee'] > 0 && $vv['shipping_fee_type'] == 2) {
                            //存在运费 且是运费模板商品
                            //获取运费
                            $goodsInfo = $goods_model->getInfo([
                                'goods_id' => $vv['goods_id']
                            ], 'shop_id, shipping_fee,shipping_fee_type,shipping_fee_id,goods_weight,goods_volume,goods_count');
                            //重组
                            $new_ship = array(
                                'goods_id' => $vv['goods_id'],
                                'shipping_fee_id' => $goodsInfo['shipping_fee_id'],
                                'num' => $vv['num'],
                                'goods_weight' => $goodsInfo['goods_weight'] * $vv['num'],
                                'goods_volume' => $goodsInfo['goods_volume'] * $vv['num'],
                                'goods_count' => $goodsInfo['goods_count'] * $vv['num'],
                            );
                            $payment_info[$kks]['goods_list'][$kv]['goods_weight'] = $goodsInfo['goods_weight'] * $vv['num'];
                            $payment_info[$kks]['goods_list'][$kv]['goods_volume'] = $goodsInfo['goods_volume'] * $vv['num'];
                            $payment_info[$kks]['goods_list'][$kv]['goods_count'] = $goodsInfo['goods_count'] * $vv['num'];
                            $payment_info[$kks]['goods_list'][$kv]['shipping_fee_id'] = $goodsInfo['shipping_fee_id'];
                            array_push($ids, $new_ship);
                        } else {
                            $payment_info[$kks]['goods_list'][$kv]['goods_weight'] = 0;
                            $payment_info[$kks]['goods_list'][$kv]['goods_volume'] = 0;
                            $payment_info[$kks]['goods_list'][$kv]['goods_count'] = 0;
                            $payment_info[$kks]['goods_list'][$kv]['shipping_fee_id'] = 0;
                        }
                    }
                    if ($ids) {
                        foreach ($ids as $keys => $values) {
                            $resq[$values['shipping_fee_id']][] = $values;
                        }
                        foreach ($resq as $keyp => $valuep) {
                            if (count($valuep) > 1) {
                                $checktempgoods = [];
                                $goods_weight = 0;
                                $goods_volume = 0;
                                $goods_count = 0;
                                foreach ($valuep as $keyt => $valuet) {
                                    $checktempgoods[$valuet['goods_id']]['count'] = $valuet['num'];
                                    $checktempgoods[$valuet['goods_id']]['goods_id'] = $valuet['goods_id'];
                                    $goods_weight += $valuet['goods_weight'] > 0 ? $valuet['goods_weight'] : 1;
                                    $goods_volume += $valuet['goods_volume'] > 0 ? $valuet['goods_volume'] : 1;
                                    $goods_count += $valuet['goods_count'] > 0 ? $valuet['goods_count'] : 1;
                                }
                                $shipping_fee_valuet = $goodsExpress->getGoodsExpressTemplate($checktempgoods, $address['district'])['totalFee'];
                                //获取平均值
                                foreach ($payment_info[$kks]['goods_list'] as $kb => $vb) {
                                    //处理体积 重量为0的情况
                                    $vb['goods_weight'] = $vb['goods_weight'] > 0 ? $vb['goods_weight'] : 1;
                                    $vb['goods_volume'] = $vb['goods_volume'] > 0 ? $vb['goods_volume'] : 1;
                                    $vb['goods_count'] = $vb['goods_count'] > 0 ? $vb['goods_count'] : 1;
                                    if ($vb['shipping_fee_id'] == $keyp) {
                                        if ($goods_weight > 0) {
                                            $payment_info[$kks]['goods_list'][$kb]['shipping_fee'] = round($shipping_fee_valuet / $goods_weight * $vb['goods_weight'], 2);
                                        }
                                        if ($goods_volume > 0) {
                                            $payment_info[$kks]['goods_list'][$kb]['shipping_fee'] = round($shipping_fee_valuet / $goods_volume * $vb['goods_volume'], 2);
                                        }
                                        if ($goods_count > 0) {
                                            $payment_info[$kks]['goods_list'][$kb]['shipping_fee'] = round($shipping_fee_valuet / $goods_count * $vb['goods_count'], 2);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $is_presell_info = objToArr($vvs['presell_info']);
                    if ($is_presell_info && $shipping_type != 2) {
                        $payment_info[$kks]['presell_info']['shipping_fee'] = $shipping_fee;
                    }
                    //预售商品 线下自提 补充商品运费信息
                    if ($is_presell_info && ($shipping_type == 2 && $payment_info[$kks]['has_store'] > 0)) {
                        $payment_info[$kks]['presell_info']['shipping_fee'] = 0;
                    }
                    //预售商品 线下自提 没有开启自提门店
                    // $payment_info[$shop_id]['has_store'] 店铺开启线下自提与否 待确认是否修改
                    if ($is_presell_info || ($shipping_type == 2 && $payment_info[$kks]['has_store'] > 0)) {//等于2为自提
                        $shipping_fee = 0;
                    }
                    $payment_info[$kks]['shipping_fee'] = $shipping_fee;
                    $payment_info[$kks]['total_amount'] += $shipping_fee;
                    $return_data['total_shipping'] += $shipping_fee;
                    //抵扣积分
                    if ($presell_id > 0 && $is_presell_info) {
                        $payment_info[$kks]['goods_list'][0]['price'] = $vvs['presell_info']['allmoney'];
                    }
                    if ($post_data['shopkeeper_id']) {
                        $is_deductions = 0;
                    } else {
                        $is_deductions = 1;
                    }
                    $point_deductio = $order_business->pointDeductionOrder($payment_info[$kks]['goods_list'], $is_deductions, $shipping_type, $this->website_id, $this->uid, $sub_point);
                    $sub_point = $point_deductio['total_deduction_point'] + $sub_point;
                    $payment_info[$kks]['point_deductio'] = $point_deductio;
                    $deduction_data[] = $point_deductio;
                    //返积分
                    $point_return = $order_business->pointReturnOrder($point_deductio['sku_info'], $shipping_type);
                    $return_point_data[] = $point_return;
                    $payment_info[$kks]['goods_list'] = $point_return['sku_info'];
                    $return_data['amount'] += $payment_info[$kks]['total_amount'];
                    if ($return_point_data) {
                        foreach ($return_point_data as $k => $v) {
                            if ($v['total_return_point'] > 0) {
                                $return_data['total_give_point'] += $v['total_return_point'];
                            }
                        }
                    }
                } else {
                    $is_presell_info = objToArr($vvs['presell_info']);
                    if ($is_presell_info && $shipping_type != 2) {
                        $payment_info[$kks]['presell_info']['shipping_fee'] = $shipping_fee;
                    }
                    //预售商品 线下自提 补充商品运费信息
                    if ($is_presell_info && ($shipping_type == 2 && $payment_info[$kks]['has_store'] > 0)) {
                        $payment_info[$kks]['presell_info']['shipping_fee'] = 0;
                    }
                    //预售商品 线下自提 没有开启自提门店
                    // $payment_info[$shop_id]['has_store'] 店铺开启线下自提与否 待确认是否修改
                    if ($is_presell_info || ($shipping_type == 2 && $payment_info[$kks]['has_store'] > 0)) {//等于2为自提
                        $shipping_fee = 0;
                    }
                    $payment_info[$kks]['shipping_fee'] = $shipping_fee;
                    $payment_info[$kks]['total_amount'] += $shipping_fee;
                    $return_data['total_shipping'] += $shipping_fee;
                    //抵扣积分
                    if ($presell_id > 0 && $is_presell_info) {
                        $payment_info[$kks]['goods_list'][0]['price'] = $vvs['presell_info']['allmoney'];
                    }
                    if ($post_data['shopkeeper_id']) {
                        $is_deductions = 0;
                    } else {
                        $is_deductions = 1;
                    }
                    $point_deductio = $order_business->pointDeductionOrder($payment_info[$kks]['goods_list'], $is_deductions, $shipping_type, $this->website_id, $this->uid, $sub_point);
                    $sub_point = $point_deductio['total_deduction_point'] + $sub_point;
                    $payment_info[$kks]['point_deductio'] = $point_deductio;
                    $deduction_data[] = $point_deductio;
                    //返积分
                    $point_return = $order_business->pointReturnOrder($point_deductio['sku_info'], $shipping_type);
                    $return_point_data[] = $point_return;
                    $payment_info[$kks]['goods_list'] = $point_return['sku_info'];
                    $return_data['amount'] += $payment_info[$kks]['total_amount'];
                    $return_data['total_give_point'] = 0;
                    if ($return_point_data) {
                        foreach ($return_point_data as $k => $v) {
                            if ($v['total_return_point'] > 0) {
                                $return_data['total_give_point'] += $v['total_return_point'];
                            }
                        }
                    }
                }
            }
            $return_data['has_store'] = $has_store;
            $member_service = new Member();
            //积分抵扣
            $return_data['deduction_point'] = [];
            $member_info = $member_service->getMemberAccount($this->uid);
            $return_data['deduction_point']['point'] = $member_info['point'];
            $return_data['deduction_point']['total_deduction_money'] = 0;
            $return_data['deduction_point']['total_deduction_point'] = 0;
            if ($deduction_data && $is_deduction == 1) {
                $points = 1;
                foreach ($deduction_data as $k => $v) {
                    if ($v['total_deduction_money'] > 0 && $points == 1) {
                        $return_data['deduction_point']['total_deduction_money'] += $v['total_deduction_money'];
                        $return_data['deduction_point']['total_deduction_point'] += $v['total_deduction_point'];
                    }
                    if ($v['total_deduction_point'] >= $member_info['point']) $points = 0;
                }
                if ($return_data['deduction_point']['total_deduction_money'] > 0 && $is_deduction == 1 && !$presell_id) {
                    $return_data['amount'] = "{$return_data['amount']}" - "{$return_data['deduction_point']['total_deduction_money']}";
                }
            }
            $return_data['customform'] = $member_service->getOrderCustomForm();
            $return_data['shop'] = array_values($payment_info);
            //开始组装创建订单数据
            //组装商品信息
            //组装商品列表提供给秒杀判断
            //组装
            //判断下 预售 拼团 秒杀等是否超时或者其他的限制  post_data
            // $calculate_result = $order_service->calculateCreateOrderDataTesy($return_data['shop']);
            $calculate_result = $order_service->calculateCreateOrderDataTesy($post_data);
            if ($calculate_result['result'] === false) {
                return json(['code' => -1, 'message' => $calculate_result['message']]);
            }
            if ($calculate_result['result'] === -2) {
                return json(['code' => -2, 'message' => $calculate_result['message']]);
            }
            $order_data['order'] = array();
            foreach ($return_data['shop'] as $key => $value) {
                $shop_id = $value['shop_id'];
                foreach ($value['goods_list'] as $ks => $vs) {
                    $temp_sku_id = $vs['sku_id'];
                    $order_data['order'][$shop_id]['sku'][$k]['sku_id'] = $temp_sku_id;
                    $order_data['order'][$shop_id]['sku'][$k]['num'] = $vs['num'];
                    $order_data['order'][$shop_id]['sku'][$k]['goods_name'] = $vs['goods_name'];
                    $order_data['order'][$shop_id]['sku'][$k]['goods_id'] = $vs['goods_id'];
                    $order_data['order'][$shop_id]['sku'][$k]['seckill_id'] = $vs['seckill_id'];
                }
                $order_data['order'][$shop_id]['shop_name'] = $value['shop_name'];
                //组装优惠金额
                $order_data['shop'][$shop_id]['member_amount'] = $value['member_promotion'];
                $order_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'] = $value['coupon_promotion'];
            }
            //重新组装
            //先忽略下面所有操作 ，先搭建订单支付数据
            // 订单来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
            $order_from = $post_data['order_from'] ?: 2;
            $uid = $this->uid;
            $ip = get_ip();
            //判断传过来的商品信息是否有秒杀状态商品
            $order_type = $this->isSeckillOrder($order_data['order']);
            //如果是秒杀订单则加入秒杀订单队列
            if ($order_type) {
                $sec_service = new seckillServer();
                $redis = $this->connectRedis();
                foreach ($order_data['order'] as $shop_id => $v) {
                    foreach ($v['sku'] as $sku => $sku_list) {
                        $goods_id = $sku_list['goods_id'];
                        $sku_id = $sku_list['sku_id'];
                        $num = $sku_list['num'];
                        $goods_name = $sku_list['goods_name'];
                        $seckill_id = $sku_list['seckill_id'];
                        $condition_is_seckill['s.seckill_id'] = $seckill_id;
                        $condition_is_seckill['nsg.sku_id'] = $sku_id;
                        $is_seckill = $sec_service->isSeckillGoods($condition_is_seckill);
                        //判断活动库存
                        $condition_seckill_sku['seckill_id'] = $seckill_id;
                        $condition_seckill_sku['sku_id'] = $sku_id;
                        if ($is_seckill) {
                            $redis_goods_sku_store_key = 'store_' . $seckill_id . '_' . $goods_id . '_' . $sku_id;
                            for ($i = 0; $i < $num; $i++) {
                                $is_store = $redis->lpop($redis_goods_sku_store_key);
                            }
                            if (!$is_store) {
                                return json(['code' => -1, 'message' => $goods_name . '库存不足，已抢购完毕']);
                            }
                        }
                        //是否积分抵扣
                        $point_deduction = $order_business->pointDeductionOrder($v['sku'], $order_data['is_deduction'], $order_data['shipping_type']);
                        $deduction_money = $point_deduction['total_deduction_money'];
                        $order_data['deduction_point'] = $point_deduction['total_deduction_point'];
                        $order_data['shop'][$shop_id]['shop_should_paid_amount'] = $return_data['shop'][$shop_id]['total_amount'];
                        // debugLog($deduction_money, '==>deduction_money<==');
                        if ($order_data['shop'][$shop_id]['shop_should_paid_amount'] > 0) {
                            $order_data['shop'][$shop_id]['shop_should_paid_amount'] = $order_data['shop'][$shop_id]['shop_should_paid_amount'] - $deduction_money;
                        }
                        $order_data['order'][$shop_id]['sku'] = $point_deduction['sku_info'];
                    }
                }
                $redis_key = getcwd();
                $redis_seckill_order_key = 'vslai_seckill_order_' . $redis_key;
                // 获取支付编号
                $out_trade_no = $order_service->getOrderTradeNo();
                $order_data['out_trade_no'] = $out_trade_no;
                $order_data['ip'] = $ip;
                $order_data['create_time'] = time();
                $order_data['order_from'] = $order_from;
                $order_data['website_id'] = $this->website_id;
                //组装秒杀所需数据
                $order_data['address_id'] = $post_data['address_id'];
                //重组秒杀订单
                $serialize_data = [
                    $uid => $return_data['shop'],
                    $uid + 1 => $calculate_result,
                    $uid + 2 => $payment_info,
                    $uid + 3 => $out_trade_no,
                    $uid + 4 => $this->website_id,
                    $uid + 5 => $ip,
                    $uid + 6 => $order_from,
                    $uid + 7 => $shipping_type,
                    $uid + 8 => $is_deduction,
                    $uid + 9 => $post_data['shop_list'],/*发票数据*/
                ];
                $seckill_shop_order_data = serialize($serialize_data);
                $is_redis_data = $redis->rpush($redis_seckill_order_key, $seckill_shop_order_data);
                //组支付需要的数据
                $all_should_paid_amount = 0;
                foreach ($return_data['shop'] as $k => $money) {
                    // todo... 积分抵扣
                    $point_de = 0;
                    if ($is_deduction == 1) {
                        $point_de = $money['point_deductio']['total_deduction_point'];
                    }
                    $kill_invoice_tax = $money['invoice_tax'] ?: 0;//税费
                    $money['total_amount'] += $kill_invoice_tax - $point_de;
                    $all_should_paid_amount += $money['total_amount'];
                }
                $pay_info = serialize(['out_trade_no' => $out_trade_no, 'create_time' => $order_data['create_time'], 'all_should_paid_amount' => $all_should_paid_amount]);
                $redis->set($out_trade_no, $pay_info);
                if ($is_redis_data) {
                    $cookie_set_data['create_time'] = time();
                    $cookie_set_data['out_trade_no'] = $out_trade_no;
                    $cookie_set_data = serialize($cookie_set_data);
                    Cookie::set($is_order_key, $cookie_set_data, 15);
                }
                $message['code'] = 0;
                $message['message'] = "订单提交成功";
                $message['data']['out_trade_no'] = $out_trade_no;
                return json($message);
            }
            $buyer_info = $user_model::get($this->uid);
            // 获取支付编号
            $out_trade_no = $order_service->getOrderTradeNo();
            $shipping_time = time();
            //组装订单信息 准备写入数据表 --》 start
            foreach ($return_data['shop'] as $kk => $vv) {
                $order_info = [];
                $shop_id = $vv['shop_id'];
                if ($this->is_shop) {
                    $shop_model = new VslShopModel();
                    $shop_info = $shop_model::get(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
                    $order_info['shop_name'] = $shop_info['shop_name'];
                } else {
                    $order_info['shop_name'] = '自营店';
                }
                //自定义表单数据
                $order_info['custom_order'] = $post_data['custom_order'];
                $order_info['website_id'] = $this->website_id;
                $order_info['shop_id'] = $shop_id;
                $order_info['order_from'] = $order_from;
                //开始组装金额相关
                if ($order_data['pay_type'] == 5) {  //余额支付
                    $order_info['pay_money'] = 0;
                    $order_info['user_platform_money'] = $vv['total_amount'];
                } else {
                    $order_info['pay_money'] = $vv['total_amount'];
                    $order_info['user_platform_money'] = 0;
                }
                //计算渠道商的金额 因为要将渠道商的金额分离，加到渠道商的账户里面
                $order_info['channel_money'] = $calculate_result['shop'][$shop_id]['shop_channel_amount'] ?: 0;
                $order_info['normal_money'] = $vv['total_amount'] - $order_info['channel_money'];
                $order_info['user_money'] = 0;
                //会员优惠
                $order_info['member_money'] = $calculate_result['shop'][$shop_id]['member_amount'];
                //优惠券优惠金额
                //根据卷id获取返回值
                $coupon_reduction_amount = 0;
                $coupon_id = 0;
                $shipping_fee = 0;//初始运费
                $shipping_fee_total = 0;
                $allgoodsprice = 0;
                foreach ($vv['goods_list'] as $key => $value) {
                    $coupon_id = intval($value['coupon_id']);
                    $shipping_fee += $value['shipping_fee'];
                    $shipping_fee_total += $value['shipping_fee_total'];
                    $allgoodsprice += $value['price'] * $value['num'];
                }
                if ($coupon_id) {
                    $coupon_data = [];
                    foreach ($vv['coupon_list'] as $keys => $values) {
                        if ($values['coupon_id'] == $coupon_id) {
                            $coupon_reduction_amount = $payment_info[$shop_id]['coupon_promotion'];
                            $coupon_shop_id = $values['shop_id'];
                            $coupon_data = $vv['coupon_list'][$keys];
                        }
                    }
                }
                $order_info['coupon_reduction_amount'] = $coupon_reduction_amount;
                //其他的一些汇总金额
                $order_info['shop_total_amount'] = $calculate_result['shop'][$shop_id]['shop_total_amount'];
                $order_info['shop_should_paid_amount'] = $vv['total_amount'];
                if (!empty((array)($vv['full_cut']))) {
                    $order_info['promotion_money'] = $vv['full_cut']['discount'] ?: 0;
                }
                //订单金额
                $order_info['order_money'] = $order_info['pay_money'] + $order_info['user_platform_money'];
                // 订单金额需要 加上平台优惠 减去店铺优惠
                $order_info['shop_order_money'] = $order_info['order_money'];
                // $order_info['shop_order_money'] = $order_info['shop_order_money'] + $vv['platform_member_price'] - $vv['shop_member_price'];
                $order_info['shop_order_money'] = $order_info['shop_order_money'] + $vv['platform_member_price'];
                //运费 -- 满减送优惠
                //预售不参与满减
                if (!empty((array)($vv['full_cut'])) && $vv['full_cut']['free_shipping']) {
                    $order_info['shipping_fee'] = 0;
                    $order_info['promotion_free_shipping'] = $shipping_fee;
                } else {
                    $order_info['shipping_fee'] = $shipping_fee;
                    $order_info['promotion_free_shipping'] = 0;
                }
                //优惠金额 汇总店铺优惠
                $order_info['platform_promotion_money'] = 0; //平台优惠总额
                $order_info['platform_promotion_money'] += $vv['platform_member_price']; //平台优惠总额
                $order_info['shop_promotion_money'] = 0;  //店铺优惠总额
                $order_info['shop_promotion_money'] += $vv['shop_member_price'];  //店铺优惠总额
                //满减
                if (!empty((array)($vv['full_cut'])) && $vv['full_cut']['shop_id'] == 0) {
                    $order_info['platform_promotion_money'] += $vv['full_cut']['discount'];
                    $order_info['platform_promotion_money'] += $shipping_fee_total;//运费满减
                    $order_info['shop_order_money'] = $order_info['shop_order_money'] + $vv['full_cut']['discount'];
                } else if (!empty((array)($vv['full_cut'])) && $vv['full_cut']['shop_id']) {
                    $order_info['shop_promotion_money'] += $vv['full_cut']['discount'];
                    $order_info['shop_promotion_money'] += $shipping_fee_total;//运费满减
                    // $order_info['shop_order_money'] = $order_info['shop_order_money'] - $vv['full_cut']['discount'];
                }
                //优惠卷 $payment_info[$shop_id]['coupon_promotion'];
                if ($coupon_data) {
                    if ($coupon_data['shop_id']) {
                        $order_info['shop_promotion_money'] += $vv['coupon_promotion'];
                        //店铺优惠 需要去除
                        // $order_info['shop_order_money'] = $order_info['shop_order_money'] - $order_info['shop_promotion_money'];
                    } else {
                        $order_info['platform_promotion_money'] += $vv['coupon_promotion'];
                        $order_info['shop_order_money'] = $order_info['shop_order_money'] + $vv['coupon_promotion'];
                    }
                }
                //coupon_shop_id
                // $order_info['platform_promotion_money'] += $vv['member_promotion'];
                // $order_info['platform_promotion_money'] += $vv['discount_promotion'];
                //预售处理
                //如果 折扣后金额大于原商品总价，价格变更为商品总价
                if (($allgoodsprice + $payment_info[$shop_id]['shipping_fee']) < $order_info['shop_order_money']) {
                    $order_info['shop_order_money'] = $allgoodsprice;
                }
                if ($order_info['shop_order_money'] < 0) {
                    $order_info['shop_order_money'] = 0;
                }
                //order_money 展示的是订单实收金额 shop_order_money为店铺实际收入金额
                $presell_id = '';
                foreach ($vv['goods_list'] as $ke => $va) {
                    $order_info['bargain_id'] = $va['bargain_id'] ?: 0;
                    //判断是否有预售
                    if (!empty($va['presell_id'])) {
                        $presell_id = $va['presell_id'];
                        //如果是预售的商品，则更改其单价为预售价
                        $presell_mdl = new VslPresellModel();
                        $presell_condition['p.id'] = $presell_id;
                        $presell_condition['pg.sku_id'] = $va['sku_id'];
                        $presell_goods_info = $presell_mdl->alias('p')->where($presell_condition)->join('vsl_presell_goods pg', 'p.id = pg.presell_id', 'LEFT')->find();
                        if ($presell_goods_info) {
                            //预售商品 查看是否自提 自提门店是否开启 待处理 商品service可能已经处理
                            $v['sku'][$sku_id]['price'] = $presell_goods_info['allmoney'];
                            $v['sku'][$sku_id]['discount_price'] = $presell_goods_info['allmoney'];
                            $out_trade_no2 = $order_service->getOrderTradeNo();
                            $order_info['out_trade_no_presell'] = $out_trade_no2;
                            if ($shipping_type == 2 && $payment_info[$shop_id]['has_store'] > 0) {//等于2为自提
                                $order_info['shipping_fee'] = 0;
                            }
                            $order_info['final_money'] = $presell_goods_info['all_money'] * $va['num'] - $order_info['pay_money'] + $order_info['shipping_fee'] + $vv['invoice_tax'];
                        }
                    }
                }
                //计时/次商品
                if ($payment_info[$shop_id]['card_store_id'] > 0) {
                    $order_info['card_store_id'] = $payment_info[$shop_id]['card_store_id'];
                    foreach ($calculate_result['order'][$shop_id]['sku'] as $kc => $vc) {
                        foreach ($vv['point_deductio']['sku_info'] as $kc2 => $vc2) {
                            if ($vc['sku_id'] == $vc2['sku_id']) {
                                $vv['point_deductio']['sku_info'][$kc2]['card_store_id'] = $vc['card_store_id'] ? $vc['card_store_id'] : 0;
                                $vv['point_deductio']['sku_info'][$kc2]['cancle_times'] = $vc['cancle_times'] ? $vc['cancle_times'] : '';
                                $vv['point_deductio']['sku_info'][$kc2]['cart_type'] = $vc['cart_type'] ? $vc['cart_type'] : 0;
                                $vv['point_deductio']['sku_info'][$kc2]['invalid_time'] = $vc['invalid_time'] ? $vc['invalid_time'] : '';
                                $vv['point_deductio']['sku_info'][$kc2]['wx_card_id'] = $vc['wx_card_id'] ? $vc['wx_card_id'] : '';
                                $vv['point_deductio']['sku_info'][$kc2]['card_title'] = $vc['card_title'] ? $vc['card_title'] : '';
                            }
                        }
                    }
                }
                //订单初始状态
                if ($order_info['pay_money'] != 0) {
                    $order_info['order_status'] = 0;
                    $order_info['pay_status'] = 0;
                } else {
                    $order_info['order_status'] = 1;
                    $order_info['pay_status'] = 2;
                }
                //订单类型
                if ($post_data['order_type']) {
                    $order_info['order_type'] = $post_data['order_type'];
                } else if ($order_data['group_id']) {
                    $order_info['order_type'] = 5;
                } else if ($order_data['bargain_id']) {
                    $order_info['order_type'] = 8;//砍价订单
                } else if ($presell_id) {
                    $order_info['order_type'] = 7;//预售订单
                } else {
                    $order_info['order_type'] = 1;
                }
                if ($post_data['order_type'] == 2) {
                    $order_info['order_type'] = 2;
                }
                if ($post_data['shopkeeper_id'] && $post_data['order_type'] != 3 && $post_data['order_type'] != 4 && $post_data['order_type'] != 2) {
                    $order_info['shopkeeper_id'] = $post_data['shopkeeper_id'];
                }
                if ($post_data['order_type'] == 2) {
                    $order_info['order_type'] = 2;//成为店主
                }
                if ($post_data['shopkeeper_id'] && $post_data['order_type'] == 3) {
                    $order_info['shopkeeper_id'] = $post_data['shopkeeper_id'];
                    $order_info['order_type'] = 3;//店主续费
                }
                if ($post_data['shopkeeper_id'] && $post_data['order_type'] == 4) {
                    $order_info['shopkeeper_id'] = $post_data['shopkeeper_id'];
                    $order_info['order_type'] = 4;//店主升级
                }
                //自提门店/计时次商品地址
                if ($payment_info[$shop_id]['store_id'] || $payment_info[$shop_id]['card_store_id']) {
                    $store = new Store();
                    if ($payment_info[$shop_id]['store_id']) {
                        $store_info = $store->storeDetail($payment_info[$shop_id]['store_id']);
                        $order_info['store_id'] = $payment_info[$shop_id]['store_id'];
                        $order_info['verification_code'] = $store->createVerificationCode();
                    } else {
                        $store_info = $store->storeDetail($payment_info[$shop_id]['card_store_id']);
                    }
                    $address['province'] = $store_info['province_id'];
                    $address['city'] = $store_info['city_id'];
                    $address['district'] = $store_info['district_id'];
                    $address['address'] = $store_info['address'];
                } else {
                    // 收获地址
                    $address_condition['id'] = $post_data['address_id'];
                    $member_service = new Member();
                    $address = $member_service->getMemberExpressAddress($address_condition, ['area_province', 'area_city', 'area_district']);
                }
                //订单编号等
                $order_info['out_trade_no'] = $out_trade_no;
                $order_info['order_sn'] = $out_trade_no;
                $order_info['order_no'] = $order_business->createOrderNo($shop_id);
                $order_info['pay_type'] = "0";
                $order_info['shipping_type'] = ($payment_info[$shop_id]['store_id'] || $payment_info[$shop_id]['card_store_id']) ? 2 : 1;
                $shipping_type = $order_info['shipping_type'];//下架商品导致运费没有写入
                $order_info['ip'] = $ip;
                // 其他的一些数据
                $order_info['leave_message'] = $calculate_result['shop'][$shop_id]['leave_message'] ?: '';
                $order_info['buyer_invoice'] = '';
                $order_info['shipping_time'] = $shipping_time;
                $order_info['receiver_mobile'] = isset($address['mobile']) ? $address['mobile'] : '';
                $order_info['receiver_province'] = isset($address['province']) ? $address['province'] : '';
                $order_info['receiver_city'] = isset($address['city']) ? $address['city'] : '';
                $order_info['receiver_district'] = isset($address['district']) ? $address['district'] : '';
                $order_info['receiver_address'] = isset($address['address']) ? $address['address'] : '';
                $order_info['receiver_zip'] = isset($address['zip_code']) ? $address['zip_code'] : '';
                $order_info['receiver_name'] = isset($address['consigner']) ? $address['consigner'] : '';
                $order_info['pick_up_id'] = 0;
                $order_info['create_time'] = time();
                //拼团
                $order_info['buyer_id'] = $this->uid;
                $order_info['nick_name'] = $buyer_info['nick_name'];
                $order_info['group_id'] = $order_data['group_id'];
                $order_info['presell_id'] = $presell_id;
                $order_info['group_record_id'] = $order_data['record_id'];//如果参加了团，订单标记是哪个团
                //满减送信息
                if (!empty((array)($vv['full_cut']))) {
                    $order_info['man_song_full_cut'][$vv['full_cut']['man_song_id']] = $vv['full_cut'];
                }
                //优惠卷信息
                if ($coupon_data) {
                    $order_info['coupon'] = $coupon_data;
                    $order_info['coupon']['discount'] = $vv['coupon_promotion'];//优惠券优惠多少钱
                    $order_info['coupon']['price'] = $coupon_data['at_least'];
                    $order_info['coupon']['coupon_discount'] = $coupon_data['discount'];//优惠券设置折扣
                }
                if ($shop_id == 0) {
                    foreach ($calculate_result['order'] as $k1 => $v1) {
                        if ($k1 == $shop_id) {
                            foreach ($v1['sku'] as $v2) {
                                if ($v2['channel_stock']) {
                                    foreach ($vv['point_deductio']['sku_info'] as $k3 => $v3) {
                                        if ($v2['sku_id'] == $v3['sku_id']) {
                                            $vv['point_deductio']['sku_info'][$k3]['channel_stock'] = $v2['channel_stock'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                //商品信息
                $order_info['sku_info'] = $vv['point_deductio']['sku_info'];
                if ($is_deduction == 1) {
                    $order_info['deduction_money'] = $vv['point_deductio']['total_deduction_money'];
                    $order_info['deduction_point'] = $vv['point_deductio']['total_deduction_point'];
                } else {
                    foreach ($order_info['sku_info'] as $keys => $values) {
                        $order_info['sku_info'][$keys]['deduction_money'] = 0;
                        $order_info['sku_info'][$keys]['deduction_point'] = 0;
                    }
                }

                foreach ($order_info['sku_info'] as $keys => $values) {
                    if ($shipping_type == 2) {
                        $order_info['shipping_fee'] -= $order_info['sku_info'][$keys]['shipping_fee'];
                        $order_info['sku_info'][$keys]['shipping_fee'] = 0;
                    }
                    if (empty($values['discount_price']) && $presell_id) {
                        $order_info['sku_info'][$keys]['discount_price'] = $values['price'];
                    }

                }

                if ($order_info['shipping_fee'] < 0) {
                    $order_info['shipping_fee'] = 0;
                }

                if ($order_info['deduction_money'] > 0) {
                    if (!empty($order_info['sku_info'][0]['presell_id'])) {//预售处理
                        $order_info['final_money'] = $order_info['final_money'] - $order_info['deduction_money'];
                        if ($order_info['final_money'] <= 0) {
                            $order_info['order_money'] = $order_info['order_money'] + $order_info['final_money'];
                            if ($order_info['pay_money'] > 0) {
                                $order_info['pay_money'] = $order_info['pay_money'] + $order_info['final_money'];
                                $order_info['final_money'] = 0;
                            }
                            if ($order_info['user_platform_money'] > 0) {
                                $order_info['user_platform_money'] = $order_info['user_platform_money'] + $order_info['final_money'];
                                $order_info['final_money'] = 0;
                            }
                        }
                        if ($order_info['pay_money'] == 0 && $order_info['final_money'] == 0) {
                            $order_info['money_type'] = 2;
                        }
                    } else {
                        if ($order_info['pay_money'] > 0) {
                            $order_info['pay_money'] = $order_info['pay_money'] - $order_info['deduction_money'];
                        }
                        if ($order_info['user_platform_money'] > 0) {
                            $order_info['user_platform_money'] = $order_info['user_platform_money'] - $order_info['deduction_money'];
                        }
                        $order_info['order_money'] = $order_info['order_money'] - $order_info['deduction_money'];

                    }
                    //积分抵扣算进平台优惠

                    $order_info['platform_promotion_money'] += $order_info['deduction_money'];
                    $order_info['shop_order_money'] += $order_info['deduction_money'] - $order_info['shop_promotion_money'];

                    if ($order_info['pay_money'] != 0) {
                        $order_info['order_status'] = 0;
                        $order_info['pay_status'] = 0;
                    } else {
                        $order_info['order_status'] = 1;
                        $order_info['pay_status'] = 2;
                    }
                }

                $order_info['invoice_tax'] = isset($vv['invoice_tax']) ? $vv['invoice_tax'] : 0;//发票
                $order_info['invoice_type'] = isset($vv['invoice_type']) ? $vv['invoice_type'] : 0;//发票类型

                //提交处理
                $order_business = new OrderBusiness();
                $order_id = $order_business->orderCreateNew($order_info);

                $config = new \data\service\Config();
                $shopConfig = $config->getShopConfig(0);
                // 针对特殊订单执行支付处理
                if ($order_id > 0 && is_numeric($order_id)) {
                    if ($order_info['pay_money'] > 0) {
                        // 还需要支付的订单发送已创建待支付订单短信 邮件通知
                        // 当创建多店铺订单时阿里云短信发送会将市区设为GMT，所以手动设为PRC
                        date_default_timezone_set("PRC");
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

                //发票数据处理
                $invoice_goods_detail = '';
                $invoice_goods_category = '';
                foreach ($post_data['shop_list'] as $keys => $values) {
                    if ($values['shop_id'] != $vv['shop_id']) {
                        continue;
                    }
                    $temp_shop_amount = $values['shop_amount'];
                    if ($temp_shop_amount <= 0) {
                        continue;
                    }
                    foreach ($values['goods_list'] as $k => $v) {
                        //商品明细 （名字 价格/ 分类 价格）
                        $goodsCategoryName = $goods_service->getGoodsCategoryNameByGoodId($v['goods_id']);
                        $invoice_goods_detail .= $v['goods_name'];//eg 冬日棉衣 520¥__     (双下划线)
                        $invoice_goods_category .= $goodsCategoryName;//eg 服装>上衣 520¥__
                    }
                    if (getAddons('invoice', $this->website_id, $this->instance_id)) {
                        if (!isset($values['invoice']['type'])) {
                            continue;
                        }

                        $order_model = new VslOrderModel();
                        $order_no = $order_model->getInfo(['order_id' => $order_id], 'order_no')['order_no'];
                        //发票数据入库
                        $invoice = new InvoiceController();
                        $values['invoice']['shop_id'] = $values['shop_id'];
                        $values['invoice']['website_id'] = $this->website_id;
                        $values['invoice']['order_no'] = $order_no;
                        $values['invoice']['invoice_goods_detail'] = rtrim($invoice_goods_detail, '__');
                        $values['invoice']['invoice_goods_category'] = rtrim($invoice_goods_category, '__');
                        $values['invoice']['price'] = $temp_shop_amount;//订单价格
//                        $values['invoice']['status'] = $is_presell_info ? 3 :0;//预售未付尾款
                        $invoice->postInvoiceByOrderCreate($values['invoice']);
                    }
                }
            }
            if ($order_id > 0) {
                if (Session::get('order_tag') == 'cart' && $cart_from == 2) {
                    $sku_id_array = [];
                    if ($post_data['shop_list']) {
                        foreach ($post_data['shop_list'] as $keys => $values) {
                            foreach ($values['goods_list'] as $k => $v) {
                                $sku_id_array[] = $v['sku_id'];
                            }
                        }
                    }
                    $delete_cart_condition['buyer_id'] = $this->uid;
                    $delete_cart_condition['sku_id'] = ['IN', $sku_id_array];
                    $order_service->deleteStoreCartNew($delete_cart_condition);
                } elseif (Session::get('order_tag') == 'cart' && $cart_from == 1) {
                    $sku_id_array = [];
                    if ($post_data['shop_list']) {
                        foreach ($post_data['shop_list'] as $keys => $values) {
                            foreach ($values['goods_list'] as $k => $v) {
                                $sku_id_array[] = $v['sku_id'];
                            }
                        }
                    }
                    $delete_cart_condition['buyer_id'] = $this->uid;
                    $delete_cart_condition['sku_id'] = ['IN', $sku_id_array];
                    $order_service->deleteCartNew($delete_cart_condition);
                }

                $cookie_set_data['create_time'] = time();
                $cookie_set_data['out_trade_no'] = $out_trade_no;
                $cookie_set_data['order_id'] = $order_id;
                $cookie_set_data = serialize($cookie_set_data);
                Cookie::set($is_order_key, $cookie_set_data, 15);
                $message['code'] = 0;
                $message['message'] = "订单提交成功";
                $message['data']['out_trade_no'] = $out_trade_no;
                //todo... 订单弹幕暂不提交
                if (getAddons('orderbarrage', $this->website_id, $this->instance_id)) {
                    $data = [
                        'website_id' => $this->website_id,
                        'shop_id' => $this->instance_id,
                        'uid' => $this->uid,
                        'order_id' => $order_id
                    ];
                    runhook("OrderBarrage", "postOrderBarrageTrueQueue", $data);//订单弹幕
                }
                return json($message);
            } else {
                $message['code'] = -1;
                $message['message'] = "订单提交失败";
                $message['data'] = '';
                return json($message);
            }
        }
    }

    /*
     * 判断是否有秒杀商品
     * **/
    public function isSeckillOrder($order_shop)
    {
        if ($this->is_seckill) {
            $sec_service = new seckillServer();
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

    //获取订单结果
    public function get_pay_result_info()
    {
        $order = new OrderService();
        $member_recharge = new VslMemberRechargeModel();
        $payment = new VslOrderPaymentModel();
        $out_trade_no = request()->post('out_trade_no', '');
        if (empty($out_trade_no)) {
            $data['code'] = -1;
            $data['message'] = "外部交易号不能为空";
            return json($data);
        }
        if (strstr($out_trade_no, 'eos')) {
            $data['code'] = 0;
            $block = new Block();
            $status = $block->checkOrderStatus($out_trade_no);
            if ($status == 1) {
                $data['data']['pay_status'] = 2;
            } else {
                $data['data']['pay_status'] = 0;
            }
            return json($data);
        } else {
            $condition['out_trade_no'] = $out_trade_no;
            $recharge_info = $member_recharge->getInfo($condition);
            $info = $order->get_status_by_outno($out_trade_no);
            $orderId = $order->getOrderIdByOutno($out_trade_no);//获取订单id，多单返回0；
            $payment_info = $payment->getInfo($condition);
            if (strstr($out_trade_no, 'QD')) {
                $info = $order->getChannelStatusByOutno($out_trade_no);
            }
            //计时/次商品使用门店信息
            $card_store = [];
            $card_store['shop_name'] = "";
            $card_store['store_name'] = "";
            $card_store['store_tel'] = "";
            $card_store['address'] = "";
            if (!empty($info)) {
                $data['code'] = 0;
                if ($info['money_type'] == 1) {//预售第一次支付成功
                    $data['data']['pay_status'] = 2;
                } else {
                    $data['data']['pay_status'] = $info['pay_status'];
                }
                $data['data']['presell_id'] = $info['presell_id'];  //预售ID
                $data['data']['is_integral_order'] = $info['order_type'] == 10 ? 1 : 0;  //预售ID
                $data['data']['group_id'] = $info['group_id'];      //拼团活动ID
                $data['data']['group_record_id'] = $info['group_record_id'];    //拼团记录ID
                $data['data']['pay_gift_status'] = $info['pay_gift_status'];    //支付有礼状态
                if (strstr($out_trade_no, 'QD')) {
                    $data['data']['is_channel'] = 'purchase';
                } else {
                    $data['data']['is_channel'] = $info['buy_type'] == 2 ? 'pickupgoods' : '';
                }
                $wx_card_state = 0;
                if ($info['card_store_id'] > 0) {
                    $member_card = new VslMemberCardModel();
                    if ($info['card_ids']) {
                        $card_ids = explode(',', $info['card_ids']);
                        foreach ($card_ids as $k => $v) {
                            $card_info = $member_card->getDetail(['vmc.card_id' => $v]);
                            if ($card_info['wx_card_state'] == 1 || $card_info['card_type'] == 1) $wx_card_state = 1;
                        }
                    }
                    $storeServer = new Store();
                    $store_info = $storeServer->storeDetail($info['card_store_id']);
                    $card_store['shop_name'] = $store_info['shop_name'];
                    $card_store['store_name'] = $store_info['store_name'];
                    $card_store['store_tel'] = $store_info['store_tel'];
                    $card_store['address'] = $store_info['detailed_address'];
                }
                $data['data']['card_store'] = $card_store;  //计时/次商品使用门店信息
                $data['data']['wx_card_state'] = $wx_card_state;  //计时/次商品微信卡券领取状态
                $data['data']['card_ids'] = $info['card_ids']; //计时/次商品微信卡券领取id
                $data['data']['order_type'] = $info['order_type']; //订单类型
                $data['data']['shipping_type'] = $info['shipping_type']; //配送类型
                $data['data']['order_from'] = 1; //商城订单
                $data['data']['order_id'] = $orderId; //订单id
                return json($data);
            } elseif (!empty($recharge_info)) {
                $data['code'] = 0;
                if ($recharge_info['is_pay'] == 1) {
                    $data['data']['pay_status'] = 2;
                } else {
                    $data['data']['pay_status'] = 0;
                }
                $data['data']['presell_id'] = '';  //预售ID
                $data['data']['group_id'] = '';      //拼团活动ID
                $data['data']['group_record_id'] = '';    //拼团记录ID
                $data['data']['pay_gift_status'] = '';    //支付有礼状态
                $data['data']['is_channel'] = '';
                $data['data']['card_store'] = $card_store; //计时/次商品使用门店信息
                $data['data']['wx_card_state'] = ''; //计时/次商品微信卡券领取状态
                $data['data']['card_ids'] = ''; //计时/次商品微信卡券领取id
                $data['data']['order_type'] = 1; //订单类型
                $data['data']['order_from'] = 2; //充值订单
                $data['data']['shipping_type'] = 1;
                $data['data']['order_id'] = 0; //订单id
                return json($data);
            } else if (!empty($payment_info)) {
                $data['code'] = 0;
                $data['data']['pay_status'] = $payment_info['pay_status'];
                $data['data']['presell_id'] = '';  //预售ID
                $data['data']['group_id'] = '';      //拼团活动ID
                $data['data']['group_record_id'] = '';    //拼团记录ID
                $data['data']['pay_gift_status'] = '';    //支付有礼状态
                $data['data']['is_channel'] = '';
                $data['data']['card_store'] = $card_store; //计时/次商品使用门店信息
                $data['data']['wx_card_state'] = ''; //计时/次商品微信卡券领取状态
                $data['data']['card_ids'] = ''; //计时/次商品微信卡券领取id
                $data['data']['order_type'] = 1; //订单类型
                $data['data']['order_from'] = 1; //商城订单
                $data['data']['shipping_type'] = 1;
                $data['data']['order_id'] = 0; //订单id
                return json($data);
            } else {
                return json(['code' => -1, 'message' => '错误的交易号']);

            }
        }

    }

    /**
     *
     * 创建秒杀订单
     */
    public function seckillOrderCreate_c($redis_order_data)
    {
        $order_service = new OrderService();
        $order_business = new OrderBusiness();
        $user_model = new UserModel();
        $order_data_arr = unserialize($redis_order_data);
        $uid = array_keys($order_data_arr)[0];
        $order_data = array_values($order_data_arr)[0];


        $buyer_info = $user_model::get($uid);
        $order_from = $order_data['order_from']; // 手机
        // 获取支付编号
        $out_trade_no = $order_data['out_trade_no'];
        $member = new Member();
        $shipping_time = time();
        $ip = $order_data['ip'];
        $website_id = $order_data['website_id'];
        $create_time = $order_data['create_time'];
        if ($order_data['address_id']) {
            $address = $member->getMemberExpressAddressDetail($order_data['address_id'], $uid);
        }
        $err = 0;
        $count_shop = count($order_data['order']);

        foreach ($order_data['order'] as $shop_id => $v) {
            if ($this->is_shop) {
                $shop_model = new VslShopModel();
                $shop_info = $shop_model::get(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
            } else {
                $shop_info['shop_name'] = '自营店';
            }
            $order_info = [];
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

            $order_info['member_money'] = $order_data['shop'][$shop_id]['member_amount'];//会员价
            $order_info['coupon_reduction_amount'] = $order_data['promotion'][$shop_id]['coupon']['coupon_reduction_amount'] ?: 0;//优惠券优惠金额
            $order_info['shop_total_amount'] = $order_data['shop'][$shop_id]['shop_total_amount'];//店铺总金额
            $order_info['shop_should_paid_amount'] = $order_data['shop'][$shop_id]['shop_should_paid_amount'];//店铺优惠后的总金额
            $order_info['promotion_money'] = $order_data['shop'][$shop_id]['man_song_amount'] ?: 0;//满送优惠金额 不包括满减运费
            $order_info['order_money'] = $order_info['pay_money'] + $order_info['user_platform_money'];//要支付的金额加上平台账户余额 必须有一个是0
            $order_info['shipping_fee'] = $order_data['shop'][$shop_id]['shipping_fee'];//
            $order_info['promotion_free_shipping'] = $order_data['shop'][$shop_id]['promotion_free_shipping'];//减掉满减的运费

            $order_info['platform_promotion_money'] = 0;//
            $order_info['shop_promotion_money'] = 0;//
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
                    //平台优惠券优惠总额
                    $order_info['platform_promotion_money'] += $order_info['coupon_reduction_amount'];
                } elseif ($order_data['promotion'][$shop_id]['coupon']['coupon_shop_id']) {
                    //店铺优惠券优惠总额
                    $order_info['shop_promotion_money'] += $order_info['coupon_reduction_amount'];
                }
            }

            //post_data['shop'][shop_id]['man_song_shipping_shop_id']
            //post_data['shop'][shop_id]['promotion_free_shipping']
            if ($order_data['shop'][$shop_id]['promotion_free_shipping']) {
                if ($order_data['shop'][$shop_id]['man_song_shipping_shop_id'] == 0) {
                    //平台运费优惠总额
                    $order_info['platform_promotion_money'] += $order_data['shop'][$shop_id]['promotion_free_shipping'];
                    //var_dump('shipping:',$order_data['shop'][$shop_id]['promotion_free_shipping']);
                } elseif ($order_data['shop'][$shop_id]['man_song_shipping_shop_id']) {
                    //店铺运费优惠总额
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
            if ($order_data['shop'][$shop_id]['store_id']) {
                $store = new Store();
                $store_info = $store->storeDetail($order_data['shop'][$shop_id]['store_id']);
                $address['province'] = $store_info['province_id'];
                $address['city'] = $store_info['city_id'];
                $address['district'] = $store_info['district_id'];
                $address['address'] = $store_info['address'];
                $address['mobile'] = '';
                $address['zip_code'] = '';
                $address['consigner'] = '';
                $order_info['store_id'] = $order_data['shop'][$shop_id]['store_id'];
                $order_info['verification_code'] = $store->createVerificationCode();
            }
            $order_info['out_trade_no'] = $out_trade_no;
            $order_info['order_sn'] = $out_trade_no;
            $order_info['order_no'] = $order_business->createOrderNo($shop_id);
            $order_info['pay_type'] = "0";
            $order_info['shipping_type'] = $order_data['shipping_type'] ?: 1;
            $order_info['order_from'] = $order_from;
            $order_info['ip'] = $ip;
            $order_info['leave_message'] = $order_data['shop'][$shop_id]['leave_message'] ?: '';
            $order_info['buyer_invoice'] = '';
            $order_info['shipping_time'] = $shipping_time;
            $order_info['receiver_mobile'] = isset($address['mobile']) ? $address['mobile'] : '';
            $order_info['receiver_province'] = isset($address['province']) ? $address['province'] : '';
            $order_info['receiver_city'] = isset($address['city']) ? $address['city'] : '';
            $order_info['receiver_district'] = isset($address['district']) ? $address['district'] : '';
            $order_info['receiver_address'] = isset($address['address']) ? $address['address'] : '';
            $order_info['receiver_zip'] = isset($address['zip_code']) ? $address['zip_code'] : '';
            $order_info['receiver_name'] = isset($address['consigner']) ? $address['consigner'] : '';

            $order_info['sku_info'] = $v['sku'];
            $order_info['pick_up_id'] = 0;
            $order_info['create_time'] = $create_time;

            $order_info['buyer_id'] = $uid;
            $order_info['nick_name'] = $buyer_info['nick_name'];
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
            try {
                $order_business = new OrderBusiness();
                //            echo '<pre>';print_r($order_info);exit;
                $order_id = $order_business->orderCreateNew($order_info);
                //只要有异常就不能插入数据库，将队列订单数据请求压回去
                if ($order_id < 0) {
                    $err++;
                }
                //满减送积分 > 0 送积分
                if ($order_data['shop'][$shop_id]['man_song_point'] > 0) {
                    $memberAccount = new Member\MemberAccount();
                    $memberAccount->addMemberAccountData(1, $this->uid, 1, $order_data['shop'][$shop_id]['man_song_point'],
                        1, $this->website_id, '注册营销,注册得积分');
                }

                //满减送有设置优惠券 送优惠券
                if ($order_data['shop'][$shop_id]['man_song_coupon_type_id'] > 0) {
                    $coupon_server = new Coupon();
                    if ($coupon_server->isCouponTypeReceivable($order_data['shop'][$shop_id]['man_song_coupon_type_id'], $this->uid)) {
                        $coupon_server->userAchieveCoupon($this->uid, $order_data['shop'][$shop_id]['man_song_coupon_type_id'], 1);
                    }
                }

                $config = new \data\service\Config();
                $shopConfig = $config->getShopConfig(0, $website_id);
                // 针对特殊订单执行支付处理
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
                    if ($order_info['pay_money'] > 0) {
                        // 还需要支付的订单发送已创建待支付订单短信 邮件通知
//                        date_default_timezone_set('PRC');
                        $timeout = date('Y-m-d H:i:s', $order_info['create_time'] + ($shopConfig['order_buy_close_time'] * 60));
                        runhook('Notify', 'orderCreateBySms', array('order_id' => $order_id, 'time_out' => $timeout));
                        runhook('Notify', 'emailSend', ['website_id' => $website_id, 'shop_id' => 0, 'order_id' => $order_id, 'notify_type' => 'user', 'time_out' => $timeout, 'template_code' => 'create_order']);
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
                    //如果$order_id存在，则删除购物车的东西
                    foreach ($v['sku'] as $k1 => $v1) {
                        $sku_id_array[] = $v1['sku_id'];
                    }
                    $delete_cart_condition['buyer_id'] = $uid;
                    $delete_cart_condition['sku_id'] = ['IN', $sku_id_array];
                    $order_service->deleteCartNew($delete_cart_condition);
                }
            } catch (\Exception $e) {
                $e->getMessage();
            }

//            Log::write($order_id);

        }
        if ($err == $count_shop) {
            return ['status' => false, 'out_trade_no' => $out_trade_no];
        } else {
            return ['status' => true, 'out_trade_no' => $out_trade_no];
        }
    }

    /**
     *
     * 创建秒杀订单
     */
    public function seckillOrderCreate($redis_order_data)
    {
        $order_service = new OrderService();
        $order_business = new OrderBusiness();
        $user_model = new UserModel();
        $order_data_arr = unserialize($redis_order_data);
        //数据

        $uid = array_keys($order_data_arr)[0];
        $this->uid = $uid;
        // $order_data = array_values($order_data_arr)[0];

        $return_data['shop'] = array_values($order_data_arr)[0];

        $calculate_result = array_values($order_data_arr)[1];

        $payment_info = array_values($order_data_arr)[2];

        $order_data['out_trade_no'] = array_values($order_data_arr)[3];

        $this->website_id = array_values($order_data_arr)[4];

        $ip = array_values($order_data_arr)[5];
        $order_from = array_values($order_data_arr)[6];

        $shipping_type = array_values($order_data_arr)[7];
        $is_deduction = array_values($order_data_arr)[8];
        $invoice_list = array_values($order_data_arr)[9];

        $buyer_info = $user_model::get($uid);

        // $order_from = $order_data['order_from']; // 手机
        // 获取支付编号
        $out_trade_no = $order_data['out_trade_no'];

        $member = new Member();
        $shipping_time = time();
        // $ip = $order_data['ip'];
        // $website_id = $order_data['website_id'];
        // $create_time = $order_data['create_time'];
        $address_id = $calculate_result['address_id'];
        if ($address_id) {
            $address = $member->getMemberExpressAddressDetail($address_id, $uid);
        }
        $err = 0;
        $count_shop = count($return_data['shop']);
        foreach ($return_data['shop'] as $kk => $vv) {

            $order_info = [];
            $shop_id = $vv['shop_id'];
            if ($this->is_shop) {
                $shop_model = new VslShopModel();
                $shop_info = $shop_model::get(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
                $order_info['shop_name'] = $shop_info['shop_name'];
            } else {
                $order_info['shop_name'] = '自营店';
            }
            //自定义表单数据
            $order_info['custom_order'] = $post_data['custom_order'];
            $order_info['website_id'] = $this->website_id;
            $order_info['shop_id'] = $shop_id;
            $order_info['order_from'] = $order_from;
            //开始组装金额相关
            if ($order_data['pay_type'] == 5) {  //余额支付
                $order_info['pay_money'] = 0;
                $order_info['user_platform_money'] = $vv['total_amount'];
            } else {
                $order_info['pay_money'] = $vv['total_amount'];
                $order_info['user_platform_money'] = 0;
            }
            //计算渠道商的金额 因为要将渠道商的金额分离，加到渠道商的账户里面
            $order_info['channel_money'] = $calculate_result['shop'][$shop_id]['shop_channel_amount'] ?: 0;
            $order_info['normal_money'] = $vv['total_amount'] - $order_info['channel_money'];
            $order_info['user_money'] = 0;
            //会员优惠
            $order_info['member_money'] = $calculate_result['shop'][$shop_id]['member_amount'];
            //优惠券优惠金额
            //根据卷id获取返回值
            $coupon_reduction_amount = 0;
            $coupon_id = 0;

            $shipping_fee = 0;//初始运费
            $shipping_fee_total = 0;

            $allgoodsprice = 0;
            foreach ($vv['goods_list'] as $key => $value) {
                $coupon_id = intval($value['coupon_id']);
                $shipping_fee += $value['shipping_fee'];
                $shipping_fee_total += $value['shipping_fee_total'];

                $allgoodsprice += $value['price'] * $value['num'];
            }

            if ($coupon_id) {
                $coupon_data = [];
                foreach ($vv['coupon_list'] as $keys => $values) {
                    if ($values['coupon_id'] == $coupon_id) {
                        $coupon_reduction_amount = $payment_info[$shop_id]['coupon_promotion'];
                        $coupon_shop_id = $values['money'];
                        $coupon_data = $vv['coupon_list'][$keys];
                    }
                }
            }

            $order_info['coupon_reduction_amount'] = $coupon_reduction_amount;
            //其他的一些汇总金额  
            $order_info['shop_total_amount'] = $calculate_result['shop'][$shop_id]['shop_total_amount'];
            $order_info['shop_should_paid_amount'] = $vv['total_amount'];

            if (!empty((array)($vv['full_cut']))) {

                $order_info['promotion_money'] = $vv['full_cut']['discount'] ?: 0;
            }

            //订单金额
            $order_info['order_money'] = $order_info['pay_money'] + $order_info['user_platform_money'];

            // 订单金额需要 加上平台优惠 减去店铺优惠
            $order_info['shop_order_money'] = $order_info['order_money'];

            // $order_info['shop_order_money'] = $order_info['shop_order_money'] + $vv['platform_member_price'] - $vv['shop_member_price'];
            $order_info['shop_order_money'] = $order_info['shop_order_money'] + $vv['platform_member_price'];

            //运费 -- 满减送优惠
            //预售不参与满减
            //shop_promotion_money 

            if (!empty((array)($vv['full_cut'])) && $vv['full_cut']['free_shipping']) {
                $order_info['shipping_fee'] = 0;
                $order_info['promotion_free_shipping'] = $shipping_fee;
            } else {
                $order_info['shipping_fee'] = $shipping_fee;
                $order_info['promotion_free_shipping'] = 0;
            }

            //优惠金额 汇总店铺优惠
            $order_info['platform_promotion_money'] = 0; //平台优惠总额
            $order_info['platform_promotion_money'] += $vv['platform_member_price']; //平台优惠总额
            $order_info['shop_promotion_money'] = 0;  //店铺优惠总额
            $order_info['shop_promotion_money'] += $vv['shop_member_price'];  //店铺优惠总额
            //满减

            if (!empty((array)($vv['full_cut'])) && $vv['full_cut']['shop_id'] == 0) {
                $order_info['platform_promotion_money'] += $vv['full_cut']['discount'];
                $order_info['platform_promotion_money'] += $shipping_fee_total;//运费满减

                $order_info['shop_order_money'] = $order_info['shop_order_money'] + $vv['full_cut']['discount'];

            } else if (!empty((array)($vv['full_cut'])) && $vv['full_cut']['shop_id']) {
                $order_info['shop_promotion_money'] += $vv['full_cut']['discount'];
                $order_info['shop_promotion_money'] += $shipping_fee_total;//运费满减
                $order_info['shop_order_money'] = $order_info['shop_order_money'] - $vv['full_cut']['discount'];

            }

            //优惠卷
            if ($coupon_data) {
                if ($coupon_data['shop_id']) {
                    $order_info['shop_promotion_money'] += $vv['coupon_promotion'];
                    //店铺优惠 需要去除
                    // $order_info['shop_order_money'] = $order_info['shop_order_money'] - $order_info['shop_promotion_money']; 
                } else {
                    $order_info['platform_promotion_money'] += $vv['coupon_promotion'];
                    $order_info['shop_order_money'] = $order_info['shop_order_money'] + $vv['coupon_promotion'];
                }

            }

            //coupon_shop_id
            // $order_info['platform_promotion_money'] += $vv['member_promotion'];
            // $order_info['platform_promotion_money'] += $vv['discount_promotion'];
            //预售处理


            //如果 折扣后金额大于原商品总价，价格变更为商品总价
            if (($allgoodsprice + $payment_info[$shop_id]['shipping_fee']) < $order_info['shop_order_money']) {

                $order_info['shop_order_money'] = $allgoodsprice;
            }
            if ($order_info['shop_order_money'] < 0) {
                $order_info['shop_order_money'] = 0;
            }

            //order_money 展示的是订单实收金额 shop_order_money为店铺实际收入金额
            $presell_id = '';
            foreach ($vv['goods_list'] as $ke => $va) {
                $order_info['bargain_id'] = $va['bargain_id'] ?: 0;
                //判断是否有预售
                if (!empty($va['presell_id'])) {
                    $presell_id = $va['presell_id'];
                    //如果是预售的商品，则更改其单价为预售价
                    $presell_mdl = new VslPresellModel();
                    $presell_condition['p.id'] = $presell_id;
                    $presell_condition['pg.sku_id'] = $va['sku_id'];
                    $presell_goods_info = $presell_mdl->alias('p')->where($presell_condition)->join('vsl_presell_goods pg', 'p.id = pg.presell_id', 'LEFT')->find();
                    if ($presell_goods_info) {
                        //预售商品 查看是否自提 自提门店是否开启 待处理 商品service可能已经处理
                        $v['sku'][$sku_id]['price'] = $presell_goods_info['allmoney'];
                        $v['sku'][$sku_id]['discount_price'] = $presell_goods_info['allmoney'];
                        $out_trade_no2 = $order_service->getOrderTradeNo();
                        $order_info['out_trade_no_presell'] = $out_trade_no2;

                        if ($shipping_type == 2 && $payment_info[$shop_id]['has_store'] > 0) {//等于2为自提
                            $order_info['shipping_fee'] = 0;
                        }
                        $order_info['final_money'] = $presell_goods_info['allmoney'] * $va['num'] - $order_info['pay_money'] + $order_info['shipping_fee'] + $vv['invoice_tax'];

                    }
                }

            }
            //计时/次商品
            if ($payment_info[$shop_id]['card_store_id'] > 0) {
                $order_info['card_store_id'] = $payment_info[$shop_id]['card_store_id'];
                foreach ($calculate_result['order'][$shop_id]['sku'] as $kc => $vc) {
                    foreach ($vv['point_deductio']['sku_info'] as $kc2 => $vc2) {
                        if ($vc['sku_id'] == $vc2['sku_id']) {
                            $vv['point_deductio']['sku_info'][$kc2]['card_store_id'] = $vc['card_store_id'] ? $vc['card_store_id'] : 0;
                            $vv['point_deductio']['sku_info'][$kc2]['cancle_times'] = $vc['cancle_times'] ? $vc['cancle_times'] : '';
                            $vv['point_deductio']['sku_info'][$kc2]['cart_type'] = $vc['cart_type'] ? $vc['cart_type'] : 0;
                            $vv['point_deductio']['sku_info'][$kc2]['invalid_time'] = $vc['invalid_time'] ? $vc['invalid_time'] : '';
                            $vv['point_deductio']['sku_info'][$kc2]['wx_card_id'] = $vc['wx_card_id'] ? $vc['wx_card_id'] : '';
                            $vv['point_deductio']['sku_info'][$kc2]['card_title'] = $vc['card_title'] ? $vc['card_title'] : '';
                        }
                    }
                }
            }

            //订单初始状态
            if ($order_info['pay_money'] != 0) {
                $order_info['order_status'] = 0;
                $order_info['pay_status'] = 0;
            } else {
                $order_info['order_status'] = 1;
                $order_info['pay_status'] = 2;
            }
            //订单类型
            $order_info['order_type'] = 6;
            //自提门店/计时次商品地址
            if ($payment_info[$shop_id]['store_id'] || $payment_info[$shop_id]['card_store_id']) {
                $store = new Store();
                if ($payment_info[$shop_id]['store_id']) {
                    $store_info = $store->storeDetail($payment_info[$shop_id]['store_id']);
                    $order_info['store_id'] = $payment_info[$shop_id]['store_id'];
                    $order_info['verification_code'] = $store->createVerificationCode();
                } else {
                    $store_info = $store->storeDetail($payment_info[$shop_id]['card_store_id']);
                }

                $address['province'] = $store_info['province_id'];
                $address['city'] = $store_info['city_id'];
                $address['district'] = $store_info['district_id'];
                $address['address'] = $store_info['address'];
            }

            //订单编号等

            $order_info['out_trade_no'] = $out_trade_no;
            $order_info['order_sn'] = $out_trade_no;
            $order_info['order_no'] = $order_business->createOrderNo($shop_id);
            $order_info['pay_type'] = "0";
            $order_info['shipping_type'] = ($payment_info[$shop_id]['store_id'] || $payment_info[$shop_id]['card_store_id']) ? $shipping_type : 1;
            $order_info['ip'] = $ip;
            // 其他的一些数据
            $order_info['leave_message'] = $calculate_result['shop'][$shop_id]['leave_message'] ?: '';
            $order_info['buyer_invoice'] = '';
            $order_info['shipping_time'] = $shipping_time;
            $order_info['receiver_mobile'] = isset($address['mobile']) ? $address['mobile'] : '';
            $order_info['receiver_province'] = isset($address['province']) ? $address['province'] : '';
            $order_info['receiver_city'] = isset($address['city']) ? $address['city'] : '';
            $order_info['receiver_district'] = isset($address['district']) ? $address['district'] : '';
            $order_info['receiver_address'] = isset($address['address']) ? $address['address'] : '';
            $order_info['receiver_zip'] = isset($address['zip_code']) ? $address['zip_code'] : '';
            $order_info['receiver_name'] = isset($address['consigner']) ? $address['consigner'] : '';
            $order_info['pick_up_id'] = 0;
            $order_info['create_time'] = time();

            //拼团
            $order_info['buyer_id'] = $this->uid;
            $order_info['nick_name'] = $buyer_info['nick_name'];
            $order_info['group_id'] = $order_data['group_id'];
            $order_info['presell_id'] = $presell_id;
            $order_info['group_record_id'] = $order_data['record_id'];//如果参加了团，订单标记是哪个团

            //满减送信息
            if (!empty((array)($vv['full_cut']))) {
                $order_info['man_song_full_cut'][$vv['full_cut']['man_song_id']] = $vv['full_cut'];
            }
            //优惠卷信息
            if ($coupon_data) {
                $order_info['coupon'] = $coupon_data;

                $order_info['coupon']['discount'] = $vv['coupon_promotion'];//优惠券优惠多少钱
                $order_info['coupon']['price'] = $coupon_data['at_least'];
                $order_info['coupon']['coupon_discount'] = $coupon_data['discount'];//优惠券设置折扣
            }

            if ($shop_id == 0) {
                foreach ($calculate_result['order'] as $k1 => $v1) {
                    if ($k1 == $shop_id) {
                        foreach ($v1['sku'] as $v2) {
                            if ($v2['channel_stock']) {
                                foreach ($vv['point_deductio']['sku_info'] as $k3 => $v3) {
                                    if ($v2['sku_id'] == $v3['sku_id']) {
                                        $vv['point_deductio']['sku_info'][$k3]['channel_stock'] = $v2['channel_stock'];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //商品信息
            $order_info['sku_info'] = $vv['point_deductio']['sku_info'];


            if ($is_deduction == 1) {
                $order_info['deduction_money'] = $vv['point_deductio']['total_deduction_money'];
                $order_info['deduction_point'] = $vv['point_deductio']['total_deduction_point'];
            } else {
                foreach ($order_info['sku_info'] as $keys => $values) {
                    $order_info['sku_info'][$keys]['deduction_money'] = 0;
                    $order_info['sku_info'][$keys]['deduction_point'] = 0;
                }
            }
            foreach ($order_info['sku_info'] as $keys => $values) {
                if ($shipping_type == 2) {
                    $order_info['shipping_fee'] -= $order_info['sku_info'][$keys]['shipping_fee'];
                    $order_info['sku_info'][$keys]['shipping_fee'] = 0;
                }
                if (empty($values['discount_price']) && $presell_id) {
                    $order_info['sku_info'][$keys]['discount_price'] = $values['price'];
                }

            }
            if ($order_info['shipping_fee'] < 0) {
                $order_info['shipping_fee'] = 0;
            }

            if ($order_info['deduction_money'] > 0) {
                if (!empty($order_info['sku_info'][0]['presell_id'])) {//预售处理
                    $order_info['final_money'] = $order_info['final_money'] - $order_info['deduction_money'];
                    if ($order_info['final_money'] <= 0) {
                        $order_info['order_money'] = $order_info['order_money'] + $order_info['final_money'];
                        if ($order_info['pay_money'] > 0) {
                            $order_info['pay_money'] = $order_info['pay_money'] + $order_info['final_money'];
                            $order_info['final_money'] = 0;
                        }
                        if ($order_info['user_platform_money'] > 0) {
                            $order_info['user_platform_money'] = $order_info['user_platform_money'] + $order_info['final_money'];
                            $order_info['final_money'] = 0;
                        }
                    }
                    if ($order_info['pay_money'] == 0 && $order_info['final_money'] == 0) {
                        $order_info['money_type'] = 2;
                    }
                } else {
                    if ($order_info['pay_money'] > 0) {
                        $order_info['pay_money'] = $order_info['pay_money'] - $order_info['deduction_money'];
                    }
                    if ($order_info['user_platform_money'] > 0) {
                        $order_info['user_platform_money'] = $order_info['user_platform_money'] - $order_info['deduction_money'];
                    }
                    // $order_info['order_money'] = $order_info['order_money'] - $order_info['deduction_money'];

                }
                //积分抵扣算进平台优惠

                $order_info['platform_promotion_money'] += $order_info['deduction_money'];
                $order_info['shop_order_money'] += $order_info['deduction_money'];

                if ($order_info['pay_money'] != 0) {
                    $order_info['order_status'] = 0;
                    $order_info['pay_status'] = 0;
                } else {
                    $order_info['order_status'] = 1;
                    $order_info['pay_status'] = 2;
                }
            }

            // 税费
            $order_info['invoice_tax'] = isset($vv['invoice_tax']) ? $vv['invoice_tax'] : 0;
            try {
                $order_business = new OrderBusiness();
                $order_id = $order_business->orderCreateNew($order_info);
                //只要有异常就不能插入数据库，将队列订单数据请求压回去
                if ($order_id < 0) {
                    $err++;
                }
                //满减送积分 > 0 送积分
                if ($order_data['shop'][$shop_id]['man_song_point'] > 0) {
                    $memberAccount = new Member\MemberAccount();
                    $memberAccount->addMemberAccountData(1, $this->uid, 1, $order_data['shop'][$shop_id]['man_song_point'],
                        1, $this->website_id, '注册营销,注册得积分');
                }

                //满减送有设置优惠券 送优惠券
                if ($order_data['shop'][$shop_id]['man_song_coupon_type_id'] > 0) {
                    $coupon_server = new Coupon();
                    if ($coupon_server->isCouponTypeReceivable($order_data['shop'][$shop_id]['man_song_coupon_type_id'], $this->uid)) {
                        $coupon_server->userAchieveCoupon($this->uid, $order_data['shop'][$shop_id]['man_song_coupon_type_id'], 1);
                    }
                }

                $config = new \data\service\Config();
                $shopConfig = $config->getShopConfig(0, $this->website_id);
                // 针对特殊订单执行支付处理
                // debugLog($order_info['sku_info'], '==>秒杀order_info-try写入数据后循环处理<==');
                if ($order_id > 0) {
                    foreach ($order_info['sku_info'] as $sku_id => $sku_info) {
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
                    if ($order_info['pay_money'] > 0) {
                        // 还需要支付的订单发送已创建待支付订单短信 邮件通知
                        $timeout = date('Y-m-d H:i:s', $order_info['create_time'] + ($shopConfig['order_buy_close_time'] * 60));
                        runhook('Notify', 'orderCreateBySms', array('order_id' => $order_id, 'time_out' => $timeout));
                        runhook('Notify', 'emailSend', ['website_id' => $this->website_id, 'shop_id' => 0, 'order_id' => $order_id, 'notify_type' => 'user', 'time_out' => $timeout, 'template_code' => 'create_order']);
                    }

                    //如果$order_id存在，则删除购物车的东西
                    foreach ($order_info['sku_info'] as $k1 => $v1) {
                        $sku_id_array[] = $v1['sku_id'];
                    }
                    $delete_cart_condition['buyer_id'] = $uid;
                    $delete_cart_condition['sku_id'] = ['IN', $sku_id_array];
                    $order_service->deleteCartNew($delete_cart_condition);


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
                    //发票数据处理 todo... 注意上面这块代码在order_id创建后判断写的位置与orderCreate()的不一致
                    $invoice_goods_detail = '';
                    $invoice_goods_category = '';
                    foreach ($invoice_list as $keys => $values) {
                        if ($values['shop_id'] != $vv['shop_id']) {
                            continue;
                        }
                        $temp_shop_amount = $values['shop_amount'];
                        if ($temp_shop_amount <= 0) {
                            continue;
                        }
                        foreach ($values['goods_list'] as $k => $v) {
                            //商品明细 （名字 价格/ 分类 价格）
                            $goods_service = new GoodsService();
                            $goodsCategoryName = $goods_service->getGoodsCategoryNameByGoodId($v['goods_id']);
                            $invoice_goods_detail .= $v['goods_name'];//eg 冬日棉衣 520¥__     (双下划线)
                            $invoice_goods_category .= $goodsCategoryName;//eg 服装>上衣 520¥__
                        }
                        if (getAddons('invoice', $this->website_id, $this->instance_id)) {
                            if (!isset($values['invoice']['type'])) {
                                continue;
                            }
                            $order_model = new VslOrderModel();
                            $order_no = $order_model->getInfo(['order_id' => $order_id], 'order_no')['order_no'];
                            //发票数据入库
                            $invoice = new InvoiceController();
                            $values['invoice']['shop_id'] = $values['shop_id'];
                            $values['invoice']['website_id'] = $this->website_id;
                            $values['invoice']['order_no'] = $order_no;
                            $values['invoice']['invoice_goods_detail'] = rtrim($invoice_goods_detail, '__');
                            $values['invoice']['invoice_goods_category'] = rtrim($invoice_goods_category, '__');
                            $values['invoice']['price'] = $temp_shop_amount;//订单价格
                            $invoice->postInvoiceByOrderCreate($values['invoice']);
                        }
                    }

                }
            } catch (\Exception $e) {
                debugLog($e->getMessage(), '===>普通秒杀错误信息<===');
            }

//            Log::write($order_id);

        }
        if ($err == $count_shop) {
            return ['status' => false, 'out_trade_no' => $out_trade_no];
        } else {
            return ['status' => true, 'out_trade_no' => $out_trade_no];
        }
    }

    /**
     *
     * 获取物流公司列表
     * page_index 页数
     * page_size 页数量
     * search_text 公司名
     */
    public function getvExpressCompany()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');

        $orderby = 'use_num desc, is_default desc,co_id asc';
        $group = '';
        $field = 'company_name,co_id';

        if ($search_text) {
            $condition['company_name'] = array('like', '%' . $search_text . '%');

        }

        $comShopRela = new VslExpressCompanyModel();
        $express_all = $comShopRela->getExpressCompanyQuery($page_index, $page_size, $condition, $orderby, $group, $field);
        $page_count = $express_all['page_count'];
        $total_count = $express_all['total_count'];
        $expressList = array();
        foreach ($express_all['data'] as $v) {
            if ($v['company_name'] && $v['co_id']) {
                $rdata = array(
                    'company_name' => $v['company_name'],
                    'co_id' => $v['co_id'],
                );
                array_push($expressList, $rdata);
            } else {
                $total_count -= 1;
            }
        }
        $data['code'] = 1;
        $data['data']['expressList'] = $expressList;
        $data['data']['page_count'] = $page_count;
        $data['data']['total_count'] = $total_count;
        $data['message'] = "获取成功";
        return json($data);
    }

    /**
     * 创建门店订单
     */
    public function StoreOrderCreate()
    {
        $post_data = request()->post('order_data/a');
        return $this->orderCreate($post_data);
    }
}