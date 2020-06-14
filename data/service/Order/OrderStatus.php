<?php

namespace data\service\Order;

use data\service\BaseService as BaseService;

/**
 * 订单调度类
 */
class OrderStatus extends BaseService
{

    /**
     * 获取订单所有可能的订单状态
     */
    public static function getOrderCommonStatus($order_type = 1, $is_group_success = 0, $card_store_id = 0, $goods_type = 0)
    {
        $status = array(
            0 => [
                'status_id' => '0',
                'status_name' => '待付款',
                'is_refund' => 0, // 是否可以申请退款
                'operation' => array(
                    '0' => array(
                        'no' => 'pay',
                        'name' => '支付',
                        'icon_class' => 'icon icon-pay-l',
                    ),
//                    '1' => array(
//                        'no' => 'order_close',
//                        'name' => '交易关闭'
//                    ),
                    '1' => array(
                        'no' => 'adjust_price',
                        'name' => '调整价格',
                        'icon_class' => 'icon icon-edit-l',
                    ),
                    '2' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array(
                    '0' => array(
                        'no' => 'pay',
                        'name' => '支付',
                        'icon_class' => 'icon icon-pay-l',
                    ),
                    '1' => array(
                        'no' => 'close',
                        'name' => '关闭',
                        'icon_class' => 'icon icon-close-l',
                    )
                ),
                'channel_purchase_operation' => array(
                    '0' => array(
                        'no' => 'pay',
                        'name' => '支付',
                        'icon_class' => 'icon icon-pay-l',
                    ),
                    '1' => array(
                        'no' => 'close',
                        'name' => '取消订单',
                        'icon_class' => 'icon icon-close-l',
                    ),
                    '2' => array(
                        'no' => 'detail',
                        'name' => '订单详情',
                        'icon_class' => 'icon icon-order-l',
                    )
                ),
                'channel_output_operation' => array(
                    '0' => array(
                        'no' => 'close',
                        'name' => '取消订单',
                        'icon_class' => 'icon icon-close-l',
                    ),
                    '1' => array(
                        'no' => 'detail',
                        'name' => '详情',
                        'icon_class' => 'icon icon-order-l',
                    )
                ),
                'channel_retail_operation' => array(
                    '0' => array(
                        'no' => 'detail',
                        'name' => '详情',
                        'icon_class' => 'icon icon-order-l',
                    )
                ),
                'refund_member_operation' => []
            ],
            1 => [
                'status_id' => '1',
                'status_name' => '待发货',
                'is_refund' => 1,
                'operation' => array(
                    '0' => array(
                        'no' => 'delivery',
                        'name' => '发货',
                        'icon_class' => 'icon icon-deliver-l',
                    ),
                    '2' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    ),
                    '1' => array(
                        'no' => 'update_address',
                        'name' => '修改地址',
                        'icon_class' => 'icon icon-edit-l',
                    )
                ),
                'member_operation' => [],
                'refund_member_operation' => [
                    0 => [
                        'no' => 'refund',
                        'name' => '退款',
                        'icon_class' => 'icon icon-blacklist-l',
                    ]
                ]
            ],
            2 => [
                'status_id' => '2',
                'status_name' => '已发货',
                'is_refund' => 1,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    ),
                    '1' => array(
                        'no' => 'logistics',
                        'name' => '查看物流',
                        'icon_class' => 'icon icon-preview-l',
                    ),
                    '2' => array(
                        'no' => 'getdelivery',
                        'name' => '确认收货',
                        'icon_class' => 'icon icon-add-success-l',
                    ),
                    '3' => array(
                        'no' => 'update_shipping',
                        'name' => '修改物流',
                        'icon_class' => 'icon icon-edit-l',
                    )
                ),
                'member_operation' => [
                    0 => [
                        'no' => 'getdelivery',
                        'name' => '确认收货',
                        'icon_class' => 'icon icon-add-success-l',
                    ],
                    1 => [
                        'no' => 'logistics',
                        'name' => '查看物流',
                        'icon_class' => 'icon icon-preview-l',
                    ]
                ],
                'refund_member_operation' => [
                    0 => [
                        'no' => 'return',
                        'name' => '申请售后',
                        'icon_class' => 'icon icon-blacklist-l',

                    ]
                ]
            ],
            3 => [
                'status_id' => '3',
                'status_name' => '已收货',
                'is_refund' => 1,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    ),
                    '1' => array(
                        'no' => 'logistics',
                        'name' => '查看物流',
                        'icon_class' => 'icon icon-preview-l',

                    )
                ),
                'member_operation' => [
                    0 => [
                        'no' => 'logistics',
                        'name' => '查看物流',
                        'icon_class' => 'icon icon-preview-l',
                    ],
                    1 => [
                        'no' => 'evaluation',
                        'name' => '评价',
                        'icon_class' => 'icon icon-remarks-l',
                    ],
                    2 => [
                        'no' => 'buy_again',
                        'name' => '再次购买',
                        'icon_class' => 'icon icon-template-l',
                    ]
                ],
                'refund_member_operation' => [
                    0 => [
                        'no' => 'return',
                        'name' => '申请售后',
                        'icon_class' => 'icon icon-blacklist-l',
                    ]
                ]
            ],
            4 => [
                'status_id' => '4',
                'status_name' => '已完成',
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',

                    ),
                    '1' => array(
                        'no' => 'logistics',
                        'name' => '查看物流',
                        'icon_class' => 'icon icon-preview-l',
                    ),
                ),
                'member_operation' => [
                    0 => [
                        'no' => 'logistics',
                        'name' => '查看物流',
                        'icon_class' => 'icon icon-preview-l',
                    ],
                    1 => [
                        'no' => 'evaluation',
                        'name' => '评价',
                        'icon_class' => 'icon icon-remarks-l',

                    ],
                    2 => [
                        'no' => 'buy_again',
                        'name' => '再次购买',
                        'icon_class' => 'icon icon-location-l',
                    ],
                ],
                'refund_member_operation' => []
            ],
            5 => [
                'status_id' => '5',
                'status_name' => '已关闭',
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    ),
                    '1' => array(
                        'no' => 'delete_order',
                        'name' => '删除',
                        'icon_class' => 'icon icon-clean-l',
                    )
                ),
                'member_operation' => [
                    0 => [
                        'no' => 'delete_order',
                        'name' => '删除',
                        'icon_class' => 'icon icon-clean-l',

                    ],
                    1 => [
                        'no' => 'buy_again',
                        'name' => '再次购买',
                        'icon_class' => 'icon icon-location-l',
                    ]
                ],
                'refund_member_operation' => []
            ],
            6 => [
                'status_id' => '6',
                'status_name' => '链上处理中',
                'is_refund' => 0,
                'operation' => [],
                'member_operation' => [],
                'refund_member_operation' => []
            ],
            -1 => [
                'status_id' => '-1',
                'status_name' => '售后中',
                'is_refund' => 1,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => [],
                'refund_member_operation' => []
            ]
        );
        if ($order_type == 5 && $is_group_success > -1) {
            if ($is_group_success) {
                $status[1] = [
                    'status_id' => '1',
                    'status_name' => '已成团，待发货',
                    'is_refund' => 1,
                    'operation' => array(
                        '0' => array(
                            'no' => 'delivery',
                            'name' => '发货',
                            'icon_class' => 'icon icon-deliver-l',
                        ),
                        '1' => array(
                            'no' => 'seller_memo',
                            'name' => '备注',
                            'icon_class' => 'icon icon-remarks-l',
                        ),
                        '2' => array(
                            'no' => 'update_address',
                            'name' => '修改地址',
                            'icon_class' => 'icon icon-edit-l',
                        )
                    ),
                    'member_operation' => array(),
                    'refund_member_operation' => [
                        0 => [
                            'no' => 'refund',
                            'name' => '退款',
                            'icon_class' => 'icon icon-blacklist-l',
                        ]
                    ]
                ];
            } else {
                $status[1] = [
                    'status_id' => '1',
                    'status_name' => '待成团',
                    'is_refund' => 1,
                    'operation' => array(),
                    'member_operation' => array(),
                    'refund_member_operation' => [
                        0 => [
                            'no' => 'refund',
                            'name' => '退款',
                            'icon_class' => 'icon icon-blacklist-l',
                        ]
                    ]
                ];
            }
        }
        if ($card_store_id > 0) {
            $status[4] = [
                'status_id' => '4',
                'status_name' => '已完成',
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',

                    ),
                ),
                'member_operation' => [
                    0 => [
                        'no' => 'evaluation',
                        'name' => '评价',
                        'icon_class' => 'icon icon-remarks-l',

                    ],
                    1 => [
                        'no' => 'use_card',
                        'name' => '我要使用',
                        'icon_class' => 'icon icon-location-l',
                    ],
                ],
                'refund_member_operation' => []
            ];
        }
        if ($goods_type == 3) {//虚拟商品去掉部分操作
            unset($status[1]['operation']['1']);
            unset($status[2]['operation']['1']);
            unset($status[2]['operation']['3']);
            unset($status[2]['member_operation'][1]);
            unset($status[3]['operation']['1']);
            unset($status[3]['member_operation'][0]);
            unset($status[4]['operation']['1']);
            unset($status[4]['member_operation'][0]);
        }
        return $status;
    }

    /**
     * 获取自提订单相关状态
     */
    public static function getSinceOrderStatus($order_type = 1, $is_group_success = 0, $card_store_id = 0)
    {
        $status = array(
            0 => [
                'status_id' => '0',
                'status_name' => '待付款',
                'is_refund' => 0, // 是否可以申请退款
                'operation' => array(
                    '0' => array(
                        'no' => 'pay',
                        'name' => '线下支付',
                        'icon_class' => 'icon icon-pay-l',
                    ),
//                    '1' => array(
//                        'no' => 'close',
//                        'name' => '交易关闭'
//                    ),
                    '1' => array(
                        'no' => 'adjust_price',
                        'name' => '调整价格',
                        'icon_class' => 'icon icon-edit-l',
                    ),
                    '2' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array(
                    '0' => array(
                        'no' => 'pay',
                        'name' => '去支付',
                        'icon_class' => 'icon icon-pay-l',
                    ),
                    '1' => array(
                        'no' => 'close',
                        'name' => '关闭订单',
                        'icon_class' => 'icon icon-close-l',
                    )
                ),
                'refund_member_operation' => []
            ],
            1 => [
                'status_id' => '1',
                'status_name' => '待提货', //待收货
                'is_refund' => 1,
                'operation' => array(
//                    '0' => array(
//                        'no' => 'pickup',
//                        'name' => '提货'
//                    ),
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array(
                    '0' => array(
                        'no' => 'pickup',
                        'name' => '提货码',
                        'icon_class' => 'icon icon-pay-l',
                    ),
                ),
                'refund_member_operation' => [
                    0 => [
                        'no' => 'refund',
                        'name' => '退款',
                        'icon_class' => 'icon icon-blacklist-l',
                    ]
                ]
            ],
            3 => [
                'status_id' => '3',
                'status_name' => '已提货', //已收货
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    ),
//                    '1' => array(
//                        'no' => 'logistics',
//                        'name' => '查看物流'
//                    )
                ),
                'member_operation' => array(
                    0 => [
                        'no' => 'evaluation',
                        'name' => '评价',
                        'icon_class'=>'icon icon-remarks-l',
                        
                    ],
                ),
                'refund_member_operation' => [
                    0 => [
                        'no' => 'return',
                        'name' => '申请售后',
                        'icon_class' => 'icon icon-blacklist-l',
                    ]
                ]
            ],
            4 => [
                'status_id' => '4',
                'status_name' => '已完成',
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    ),
//                    '1' => array(
//                        'no' => 'logistics',
//                        'name' => '查看物流'
//                    )
                ),
                'member_operation' => array(
                    0 => [
                        'no' => 'evaluation',
                        'name' => '评价',
                        'icon_class'=>'icon icon-remarks-l',
                        
                    ],
                ),
                'refund_member_operation' => []
            ],
            5 => [
                'status_id' => '5',
                'status_name' => '已关闭',
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    ),
                    '1' => array(
                        'no' => 'delete_order',
                        'name' => '删除',
                        'icon_class' => 'icon icon-clean-l',
                    )
                ),
                'member_operation' => array(
                    '0' => array(
                        'no' => 'delete_order',
                        'name' => '删除',
                        'icon_class' => 'icon icon-clean-l',
                    )
                ),
                'refund_member_operation' => []
            ],
            6 => [
                'status_id' => '6',
                'status_name' => '链上处理中',
                'is_refund' => 0,
                'operation' => [],
                'member_operation' => [],
                'refund_member_operation' => []
            ],
            -1 => [
                'status_id' => '-1',
                'status_name' => '退款中',
                'is_refund' => 1,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array(),
                'refund_member_operation' => []
            ]
        );
        if ($order_type == 5 && $is_group_success > -1) {
            if ($is_group_success) {
                $status[1] = [
                    'status_id' => '1',
                    'status_name' => '待提货', //待收货
                    'is_refund' => 1,
                    'operation' => array(),
                    'member_operation' => array(
                        '0' => array(
                            'no' => 'pickup',
                            'name' => '提货码',
                            'icon_class' => 'icon icon-pay-l',
                        ),
                    ),
                    'refund_member_operation' => [
                        0 => [
                            'no' => 'refund',
                            'name' => '退款',
                            'icon_class' => 'icon icon-blacklist-l',
                        ]
                    ]
                ];
            } else {
                $status[1] = [
                    'status_id' => '1',
                    'status_name' => '待成团', //待收货
                    'is_refund' => 1,
                    'operation' => array(),
                    'member_operation' => array(),
                    'refund_member_operation' => [
                        0 => [
                            'no' => 'refund',
                            'name' => '退款',
                            'icon_class' => 'icon icon-blacklist-l',
                        ]
                    ]
                ];
            }
        }
        if ($card_store_id > 0) {
            $status[4] = [
                'status_id' => '4',
                'status_name' => '已完成',
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',

                    ),
                ),
                'member_operation' => [
                    0 => [
                        'no' => 'evaluation',
                        'name' => '评价',
                        'icon_class' => 'icon icon-remarks-l',

                    ],
                    1 => [
                        'no' => 'use_card',
                        'name' => '我要使用',
                        'icon_class' => 'icon icon-location-l',
                    ],
                ],
                'refund_member_operation' => []
            ];
        }
        return $status;
    }

    /**
     * 获取店员端订单相关状态
     */
    public static function getSinceOrderStatusForStore($order_type = 1, $is_group_success = 0)
    {
        $status = array(
            0 => [
                'status_id' => '0',
                'status_name' => '待付款',
                'is_refund' => 0, // 是否可以申请退款
                'operation' => array(
                    '0' => array(
                        'no' => 'pay',
                        'name' => '线下支付',
                        'icon_class' => 'icon icon-pay-l',
                    ),
//                    '1' => array(
//                        'no' => 'close',
//                        'name' => '交易关闭'
//                    ),
                    '1' => array(
                        'no' => 'adjust_price',
                        'name' => '调整价格',
                        'icon_class' => 'icon icon-edit-l',
                    ),
                    '2' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array(),
                'refund_member_operation' => []
            ],
            1 => [
                'status_id' => '1',
                'status_name' => '待提货', //待收货
                'is_refund' => 1,
                'operation' => array(
//                    '0' => array(
//                        'no' => 'pickup',
//                        'name' => '提货'
//                    ),
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array(
                    '0' => array(
                        'no' => 'pickup',
                        'name' => '提货码',
                        'icon_class' => 'icon icon-pay-l',
                    ),
                ),
                'refund_member_operation' => [

                ]
            ],
            3 => [
                'status_id' => '3',
                'status_name' => '已提货', //已收货
                'is_refund' => 0,
                'operation' => array(),
                'member_operation' => array(),
                'refund_member_operation' => [

                ]
            ],
            4 => [
                'status_id' => '4',
                'status_name' => '已完成',
                'is_refund' => 0,
                'operation' => array(),
                'member_operation' => array(),
                'refund_member_operation' => []
            ],
            5 => [
                'status_id' => '5',
                'status_name' => '已关闭',
                'is_refund' => 0,
                'operation' => array(),
                'member_operation' => array(),
                'refund_member_operation' => []
            ],
            -1 => [
                'status_id' => '-1',
                'status_name' => '退款中',
                'is_refund' => 1,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array(),
                'refund_member_operation' => []
            ],
            6 => [
                'status_id' => '6',
                'status_name' => '链上处理中',
                'is_refund' => 0,
                'operation' => [],
                'member_operation' => [],
                'refund_member_operation' => []
            ],
        );
        if ($order_type == 5 && $is_group_success > -1) {
            if ($is_group_success) {
                $status[1] = [
                    'status_id' => '1',
                    'status_name' => '待提货', //待收货
                    'is_refund' => 1,
                    'operation' => array(),
                    'member_operation' => array(
                        '0' => array(
                            'no' => 'pickup',
                            'name' => '提货码',
                            'icon_class' => 'icon icon-pay-l',
                        ),
                    ),
                    'refund_member_operation' => [
                        0 => [
                            'no' => 'refund',
                            'name' => '退款',
                            'icon_class' => 'icon icon-blacklist-l',
                        ]
                    ]
                ];
            } else {
                $status[1] = [
                    'status_id' => '1',
                    'status_name' => '待成团', //待收货
                    'is_refund' => 1,
                    'operation' => array(),
                    'member_operation' => array(),
                    'refund_member_operation' => [
                        0 => [
                            'no' => 'refund',
                            'name' => '退款',
                            'icon_class' => 'icon icon-blacklist-l',
                        ]
                    ]
                ];
            }
        }
        return $status;
    }

    /**
     * 获取发货操作状态
     */
    public static function getShippingStatus()
    {
        $shipping_status = array(
            array(
                'shipping_status' => '0',
                'status_name' => '待发货'
            ),
            array(
                'shipping_status' => '1',
                'status_name' => '已发货'
            ),
            array(
                'shipping_status' => '2',
                'status_name' => '已收货'
            ),
            array(
                'shipping_status' => '3',
                'status_name' => '备货中'
            )
        );
        return $shipping_status;
    }

    /**
     * 获取发货方式
     *
     * @param unknown $type_id
     */
    public static function getShippingType($type_id)
    {
        $shipping_type = array(
            array(
                'type_id' => '1',
                'type_name' => '商家快递'
            ),
            array(
                'type_id' => '2',
                'type_name' => '到店自提'
            )
        );
        $type_name = '';
        foreach ($shipping_type as $k => $v) {
            if ($v['type_id'] == $type_id) {
                $type_name = $v['type_name'];
            }
        }
        return $type_name;
    }

    /**
     * 获取订单类型
     *
     * @param unknown $type_id
     */
    public static function getOrderType($type_id)
    {
        $order_type = array(
            array(
                'type_id' => '1',
                'type_name' => '普通订单'
            ),
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
            ),
            array(
                'type_id' => '5',
                'type_name' => '拼团订单'
            ),
            array(
                'type_id' => '6',
                'type_name' => '秒杀订单'
            ),
            array(
                'type_id' => '7',
                'type_name' => '预售订单'
            ),
            array(
                'type_id' => '8',
                'type_name' => '砍价订单'
            ),
            array(
                'type_id' => '9',
                'type_name' => '奖品订单'
            ),
            array(
                'type_id' => '10',
                'type_name' => '兑换订单'
            ),
            array(
                'type_id' => '11',
                'type_name' => '微店订单'
            )
        );
        $type_name = '';
        foreach ($order_type as $k => $v) {
            if ($v['type_id'] == $type_id) {
                $type_name = $v['type_name'];
            }
        }
        return $type_name;
    }

    /**
     * 获取订单类型颜色
     *
     * @param unknown $type_id
     */
    public static function getOrderTypeColor($type_id)
    {
        $order_type = array(
            array(
                'type_id' => '1',
                'type_color' => '#fb6638'
            ),
            array(
                'type_id' => '2',
                'type_color' => '#5cb85c'
            ),
            array(
                'type_id' => '3',
                'type_color' => '#5cb85c'
            ),
            array(
                'type_id' => '4',
                'type_color' => '#5cb85c'
            ),
            array(
                'type_id' => '5',
                'type_color' => '#e84711'
            ),
            array(
                'type_id' => '6',
                'type_color' => '#d9534f'
            ),
            array(
                'type_id' => '7',
                'type_color' => '#5cb85c'
            ),
            array(
                'type_id' => '8',
                'type_color' => '#1d86d0'
            ),
            array(
                'type_id' => '9',
                'type_color' => '#1dd09d'
            ),
            array(
                'type_id' => '10',
                'type_color' => '#d0a01d'
            )
        );
        $type_color = '';
        foreach ($order_type as $k => $v) {
            if ($v['type_id'] == $type_id) {
                $type_color = $v['type_color'];
            }
        }
        return $type_color;
    }

    /**
     * 获取订单支付操作状态
     */
    public static function getPayStatus($pay_status_id = -100)
    {
        $pay_status = array(
            array(
                'pay_status' => '0',
                'status_name' => '待支付'
            ),
            array(
                'pay_status' => '1',
                'status_name' => '支付中'
            ),
            array(
                'pay_status' => '2',
                'status_name' => '已支付'
            ),
            array(
                'pay_status' => '3',
                'status_name' => '链上处理中'
            )
        );
        return $pay_status;
    }

    /**
     * 获取订单退款操作状态
     */
    public static function getRefundStatus()
    {
        $refund_status = array(
            1 => array(
                'status_id' => '1',
                'status_name' => '申请退款',
                'status_desc' => '发起了退款申请,等待卖家处理',
                'refund_operation' => array(
                    '0' => array(
                        'no' => 'agree',
                        'name' => '同意',
                    ),
                    '1' => array(
                        'no' => 'refuse',
                        'name' => '拒绝',
                    )
                ),
                'new_refund_operation' => [
                    0 => [
                        'no' => 'judge_refund',
                        'name' => '审核退款',
                        'icon_class' => 'icon icon-prohibit-l',
                    ],
                    1 => [
                        'no' => 'judge_return',
                        'name' => '处理退货申请',
                        'icon_class' => 'icon icon-prohibit-l',
                    ]
                ],
                'member_operation' => [
                    0 => [
                        'no' => 'refund_detail',
                        'name' => '售后情况',
                        'icon_class' => 'icon icon-template-l',
                    ]
                ]
            ),
            2 => array(
                'status_id' => '2',
                'status_name' => '等回寄',
                'status_desc' => '卖家已同意退款申请,等待买家退货',
                'refund_operation' => array(),
                'new_refund_operation' => [],
                'member_operation' => [
                    0 => [
                        'no' => 'refund_detail',
                        'name' => '售后情况',
                        'icon_class' => 'icon icon-template-l',
                    ]
                ]

            ),
            3 => array(
                'status_id' => '3',
                'status_name' => '待确认回寄',
                'status_desc' => '买家已退货,等待卖家确认收货',
                'refund_operation' => array(
                    '0' => array(
                        'no' => 'confirm_receipt',
                        'name' => '确认收货',
                        'icon_class' => 'icon icon-success-l',
                    )
                ),
                'new_refund_operation' => [
                    0 => [
                        'no' => 'confirm_receipt',
                        'name' => '处理回寄',
                        'icon_class' => 'icon icon-logistics-l',
                    ],
                    1 => [
                        'no' => 'logistics',
                        'name' => '查看物流',
                        'icon_class' => 'icon icon-preview-l',
                    ]
                ],
                'member_operation' => [
                    0 => [
                        'no' => 'refund_detail',
                        'name' => '售后情况',
                        'icon_class' => 'icon icon-template-l',
                    ]
                ]
            ),
            4 => array(
                'status_id' => '4',
                'status_name' => '待打款',
                'status_desc' => '卖家同意退款',
                'refund_operation' => array(
                    0 => array(
                        'no' => 'confirm_refund',
                        'name' => '确认退款',
                        'icon_class' => 'icon icon-success-l',
                    )
                ),
                'new_refund_operation' => [
                    0 => [
                        'no' => 'confirm_refund',
                        'name' => '审核打款',
                        'icon_class' => 'icon icon-prohibit-l',
                    ]
                ],
                'member_operation' => [
                    0 => [
                        'no' => 'refund_detail',
                        'name' => '售后情况',
                        'icon_class' => 'icon icon-template-l',
                    ]
                ]
            ),
            5 => array(
                'status_id' => '5',
                'status_name' => '退款成功',
                'status_desc' => '卖家退款给买家，本次维权结束',
                'refund_operation' => array(),
                'new_refund_operation' => [],
                'member_operation' => [
                    0 => [
                        'no' => 'refund_detail',
                        'name' => '售后情况',
                        'icon_class' => 'icon icon-template-l',
                    ]
                ]
            ),
            -1 => array(
                'status_id' => '-1',
                'status_name' => '拒绝退款',
                'status_desc' => '卖家永久拒绝退款，本次维权结束',
                'refund_operation' => array(),
                'new_refund_operation' => [],
                'member_operation' => [
                    0 => [
                        'no' => 'refund_detail',
                        'name' => '售后情况',
                        'icon_class' => 'icon icon-template-l',
                    ]
                ]
            ),
            0 => array(
                'status_id' => '0',
                'status_name' => '关闭退款',
                'status_desc' => '主动撤销退款，退款关闭',
                'refund_operation' => array(),
                'new_refund_operation' => [],
                'member_operation' => [
                    0 => [
                        'no' => 'refund_detail',
                        'name' => '售后情况',
                        'icon_class' => 'icon icon-template-l',
                    ]
                ]
            ),
            -3 => array(
                'status_id' => '-3',
                'status_name' => '拒绝售后',
                'status_desc' => '拒绝了本次退款申请,等待买家修改',
                'refund_operation' => array(),
                'new_refund_operation' => [],
                'member_operation' => [
                    0 => [
                        'no' => 'refund_detail',
                        'name' => '售后情况',
                        'icon_class' => 'icon icon-template-l',
                    ]
                ]
            )
        );
        return $refund_status;
    }

    /**
     * 获取支付方式
     *
     * @param unknown $type_id
     * @return string
     */
    public static function getPayType($type_id)
    {
        $pay_type = array(
            array(
                'type_id' => '0',
                'type_name' => '在线支付'
            ),
            array(
                'type_id' => '1',
                'type_name' => '微信支付'
            ),
            array(
                'type_id' => '2',
                'type_name' => '支付宝'
            ),
            array(
                'type_id' => '3',
                'type_name' => '银行卡'
            ),
            array(
                'type_id' => '4',
                'type_name' => '货到付款'
            ),
            array(
                'type_id' => '5',
                'type_name' => '余额支付'
            ),
            array(
                'type_id' => '6',
                'type_name' => '到店支付'
            ),
            array(
                'type_id' => '10',
                'type_name' => '线下支付'
            ),
            array(
                'type_id' => '16',
                'type_name' => 'ETH支付'
            ),
            array(
                'type_id' => '17',
                'type_name' => 'EOS支付'
            ),
			array(
                'type_id' => '20',
                'type_name' => 'globepay支付'
            ),
        );
        $type_name = '';
        foreach ($pay_type as $k => $v) {
            if ($v['type_id'] == $type_id) {
                $type_name = $v['type_name'];
            }
        }
        return $type_name;
    }

    /**
     * 获取订单来源
     *
     * @param unknown $order_from
     */
    public static function getOrderFrom($type_id)
    {
        $order_grom_type = array(
            array(
                'type_id' => '1',
                'type_name' => '微信端',
                'tag' => 'fa fa-weixin'
            ),
            array(
                'type_id' => '2',
                'type_name' => '手机端',
                'tag' => 'fa fa-mobile fa-2x'
            ),
            array(
                'type_id' => '3',
                'type_name' => 'pc端',
                'tag' => 'fa fa-television'
            ),
            array(
                'type_id' => '4',
                'type_name' => 'ios端',
                'tag' => 'fa fa-mobile fa-2x'
            ),
            array(
                'type_id' => '5',
                'type_name' => 'Android端',
                'tag' => 'fa fa-mobile fa-2x'
            ),
            array(
                'type_id' => '6',
                'type_name' => '小程序端',
                'tag' => 'fa fa-mobile fa-2x'
            )
        );
        $type_name = array();
        foreach ($order_grom_type as $k => $v) {
            if ($v['type_id'] == $type_id) {
                $type_name = $v;
            }
        }
        return $type_name;
    }

    /**
     * 获取虚拟订单所有可能的订单状态
     */
    public static function getVirtualOrderCommonStatus()
    {
        $status = array(
            array(
                'status_id' => '0',
                'status_name' => '待付款',
                'is_refund' => 0, // 是否可以申请退款
                'operation' => array(
                    '0' => array(
                        'no' => 'pay',
                        'name' => '线下支付',
                        'icon_class' => 'icon icon-pay-l',
                    ),
//                    '1' => array(
//                        'no' => 'close',
//                        'name' => '交易关闭'
//                    ),
                    '1' => array(
                        'no' => 'adjust_price',
                        'name' => '调整价格',
                        'icon_class' => 'icon icon-edit-l',
                    ),
                    '2' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array(
                    '0' => array(
                        'no' => 'pay',
                        'name' => '支付',
                        'icon_class' => 'icon icon-pay-l',
                    ),
                    '1' => array(
                        'no' => 'close',
                        'name' => '关闭',
                        'icon_class' => 'icon icon-close-l',
                    )
                )
            ),
            array(
                'status_id' => '6',
                'status_name' => '已付款',
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array()
            ),
            array(
                'status_id' => '4',
                'status_name' => '已完成',
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    )
                ),
                'member_operation' => array()
            ),
            array(
                'status_id' => '5',
                'status_name' => '已关闭',
                'is_refund' => 0,
                'operation' => array(
                    '0' => array(
                        'no' => 'seller_memo',
                        'name' => '备注',
                        'icon_class' => 'icon icon-remarks-l',
                    ),
                    '1' => array(
                        'no' => 'delete_order',
                        'name' => '删除',
                        'icon_class' => 'icon icon-clean-l',
                    )
                ),
                'member_operation' => array(
                    '0' => array(
                        'no' => 'delete_order',
                        'name' => '删除',
                        'icon_class' => 'icon icon-clean-l',
                    )
                )
            )
        );
        return $status;
    }

    /**
     * 订单发货单打印状态
     */
    public static function deliveryPrintStatus()
    {
        return [
            1 => [
                'status_id' => 1,
                'status_name' => '未打印',
            ],
            2 => [
                'status_id' => 2,
                'status_name' => '部分打印',
            ],
            3 => [
                'status_id' => 3,
                'status_name' => '已打印',
            ]
        ];
    }

    /**
     * 订单快递单打印状态
     */
    public static function expressPrintStatus()
    {
        return [
            1 => [
                'status_id' => 1,
                'status_name' => '未打印',
            ],
            2 => [
                'status_id' => 2,
                'status_name' => '部分打印',
            ],
            3 => [
                'status_id' => 3,
                'status_name' => '已打印',
            ]
        ];
    }
    
    /*
     * 退款原因
     */
    public static function getRefundReason($reason = 0){
        $realReason = '';
        switch ($reason) {
            case 1:
               $realReason = '拍错/多拍/不想要';
                break;
            case 2:
               $realReason = '协商一致退款';
                break;
            case 3:
               $realReason = '缺货';
                break;
            case 4:
               $realReason = '拍错/多拍/不想要';
                break;
            case 5:
               $realReason = '其他';
                break;
            default:
                $realReason = '';
                break;
        }
        return $realReason;
    }
}
