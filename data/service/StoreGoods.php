<?php

namespace data\service;

/**
 * 门店商品服务层
 */

use addons\gift\model\VslPromotionGiftModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\giftvoucher\model\VslGiftVoucherRecordsModel;
use addons\store\model\VslStoreAssistantModel;
use data\model\VslMemberCardModel;
use data\model\VslMemberCardRecordsModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderModel;
use data\service\BaseService as BaseService;
use data\model\VslGoodsModel as VslGoodsModel;
use data\model\VslStoreGoodsModel as VslStoreGoodsModel;
use data\model\AlbumPictureModel as AlbumPictureModel;
use data\model\VslStoreGoodsSkuModel as VslStoreGoodsSkuModel;
use data\model\VslGoodsSkuModel as VslGoodsSkuModel;
use data\model\VslGoodsCategoryModel as VslGoodsCategoryModel;
use data\service\Order\OrderStatus;
use think\Db;
use think\Request;

class StoreGoods extends BaseService
{
    function __construct()
    {
        parent::__construct();

    }

    /*
     * 商品管理-已上架商品/仓库中商品
     */
    public function getGoodsList($page_index, $page_size, $condition, $field, $order = '')
    {
        if ($condition['store_id']) {
        //从门店商品表中筛选出的商品
        $storeGoodsModel = new VslStoreGoodsModel();
        $list = $storeGoodsModel->pageQuery($page_index, $page_size, $condition, $order, $field);
        } else {
            //从门店所属店铺中筛选出的商品
            $goodsModel = new VslGoodsModel();
            $list = $goodsModel->pageQuery($page_index, $page_size, $condition, $order, $field);
        }
        $goods_info = $list['data'];

        //关联相册表，查出商品对应的图片
        $albumPictureModel = new AlbumPictureModel();
        foreach ($goods_info as $key => $val) {
            $condition = [
                'pic_id' => $val['picture']
            ];
            $arr = $albumPictureModel->Query($condition, 'pic_cover_small')[0];
            $goods_info[$key]['goods_img'] = __IMG($arr);
            unset($arr);
        }

        return [
            'goods_info' => $goods_info,
            'page_count' => $list['page_count'],
            'total_count' => $list['total_count']
        ];
    }

    /*
     * 商品管理-商品上架
     */
    public function goodsOnline($condition)
    {
        //上架前先判断库存以及平台的状态
        $storeGoodsModel = new VslStoreGoodsModel();
        $goodsModel = new VslGoodsModel();
        $stock = $storeGoodsModel->Query($condition, 'stock')[0];
        $state = $goodsModel->Query(['goods_id' => $condition['goods_id'], 'website_id' => $this->website_id], 'state')[0];
        if ($stock > 0 && $state == 1) {
            $data = [
                'state' => 1
            ];
            $res = $storeGoodsModel->save($data, $condition);
            if ($res > 0) {
                return SUCCESS;
            } else {
                return UPDATA_FAIL;
            }
        } elseif ($stock <= 0) {
            return 0;
        } elseif ($state != 1) {
            return -1;
        }

    }

    /*
     * 商品管理-商品下架
     */
    public function goodsOffline($condition)
    {
        $data = [
            'state' => 0
        ];
        $storeGoodsModel = new VslStoreGoodsModel();
        $res = $storeGoodsModel->save($data, $condition);
        if ($res > 0) {
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    /*
     * 商品管理-仓库中商品移除
     */
    public function goodsDel($condition)
    {
        //门店商品表中删除
        $storeGoodsModel = new VslStoreGoodsModel();
        $res = $storeGoodsModel->delData($condition);

        //门店商品sku表中删除
        $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
        $res1 = $storeGoodsSkuModel->delData($condition);

        //取消此商品对应的核销门店
        $goodsModel = new VslGoodsModel();
        $store_list = $goodsModel->getInfo(['goods_id' => $condition['goods_id']], 'store_list');
        $store_list = explode(',', $store_list['store_list']);
        $new_store_list = '';
        foreach ($store_list as $k => $v) {
            if ($v == $condition['store_id']) {
                unset($v);
            }
            $new_store_list .= $v . ',';
        }
        $new_store_list = trim($new_store_list, ',');
        $res2 = $goodsModel->save(['store_list' => $new_store_list], ['goods_id' => $condition['goods_id']]);
        if ($res > 0 && $res1 > 0 && $res2 > 0) {
            return SUCCESS;
        } else {
            return DELETE_FAIL;
        }
    }

    /*
    * 商品管理-编辑商品
    */
    public function goodsEdit($condition)
    {
        //先在sku表中查询
        $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
        $sku_list = $storeGoodsSkuModel->getQuery($condition, '*', 'id ASC');

        //从门店商品表查询要修改的商品信息
        $storeGoodsModel = new VslStoreGoodsModel();
        $data = $storeGoodsModel->getQuery($condition, '*', '');
        unset($data[0]['price']);
        unset($data[0]['market_price']);
        unset($data[0]['stock']);

        //关联相册表，查出商品对应的图片
        $albumPictureModel = new AlbumPictureModel();
        $data[0]['img_id_array'] = explode(',', $data[0]['img_id_array']);
        foreach ($data[0]['img_id_array'] as $k => $v) {
            $condition = [
                'pic_id' => $v
            ];
            $arr[] = $albumPictureModel->Query($condition, 'pic_cover_small')[0];
        }
        $data[0]['goods_img'] = $arr;

        $data[0]['sku_list'] = $sku_list;

        return $data;
    }

    /*
    * 商品管理-保存商品
    */
    public function goodsSave($data, $condition)
    {
        //先判断保存的商品是否存在门店商品表里
        $storeGoodsModel = new VslStoreGoodsModel();
        $goods_info = $storeGoodsModel->getInfo(['goods_id' => $data['goods_id'], 'store_id' => $condition['store_id']], '*');
        if (empty($goods_info)) {
            //保存到门店商品表
            $goodsModel = new VslGoodsModel();
            $goods = $goodsModel->getInfo(['goods_id' => $data['goods_id']], 'goods_name,category_id,category_id_1,category_id_2,category_id_3,picture,sales,img_id_array');
            $goods['goods_id'] = $data['goods_id'];
            $goods['state'] = $data['state'];
            $goods['shop_id'] = $condition['shop_id'];
            $goods['store_id'] = $condition['store_id'];
            $goods['website_id'] = $condition['website_id'];
            $goods['create_time'] = time();
            foreach ($data['sku_list'] as $k => $v) {
                $goods['price'] = $v['price'];
                $goods['market_price'] = $v['market_price'];
                $goods['stock'] = $v['stock'];
                $goods['bar_code'] = $v['bar_code'];
            }
            $res = $storeGoodsModel->save($goods);

            //保存到门店商品sku表
            $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
            $goodsSkuModel=new VslGoodsSkuModel();
            foreach ($data['sku_list'] as $k => $v) {
                $sku_info = $goodsSkuModel->getInfo(['sku_id' => $v['sku_id']], 'sku_name,attr_value_items');
                $info[] = [
                    'goods_id' => $data['goods_id'],
                    'shop_id' => $condition['shop_id'],
                    'store_id' => $condition['store_id'],
                    'website_id' => $condition['website_id'],
                    'create_time' => time(),
                    'sku_id' => $v['sku_id'],
                    'sku_name' => $sku_info['sku_name'],
                    'attr_value_items' => $sku_info['attr_value_items'],
                    'price' => $v['price'],
                    'market_price' => $v['market_price'],
                    'stock' => $v['stock'],
                    'bar_code' => $v['bar_code'],
                ];
            }
            $res1 = $storeGoodsSkuModel->saveAll($info, 'true');

            //把添加的商品对应的门店存到goods表
            $goodsModel = new VslGoodsModel();
            $old_store_list = $goodsModel->Query(['goods_id' => $data['goods_id']], 'store_list')[0];
            if (empty($old_store_list)) {
                $new_store_list = $condition['store_id'];
            } else {
                $new_store_list = $old_store_list . ',' . $condition['store_id'];
            }
            $res2 = $goodsModel->save(['store_list' => $new_store_list], ['goods_id' => $data['goods_id']]);
            if ($res && $res1 && $res2) {
                return 1;
            }
        } else {
            //更新storeGoodsSku表
            foreach ($data['sku_list'] as $k => $v) {
                $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
                $res = $storeGoodsSkuModel->save($v, ['sku_id' => $v['sku_id'], 'store_id' => $condition['store_id']]);
            }

            //更新storeGoods表
            $storeGoodsModel = new VslStoreGoodsModel();
            foreach ($data['sku_list'] as $k => $v) {
                unset($v['sku_id']);
                $v['state'] = $data['state'];
                $res1 = $storeGoodsModel->save($v, $condition);
            }

            if ($res == 1 && $res1 == 1) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    /*
     * 售后订单
     */
    public function afterOrderList($search_text, $page_index, $page_size, $condition, $order, $order_status)
    {
        $orderModel = new VslOrderModel();
        $orderGoodsModel = new VslOrderGoodsModel();
        if ($order_status == 0) {
            //查询所有申请过售后的商品对应的order_id
            $where = [
                'refund_status' => ['<>', 0],
                'website_id' => $this->website_id
            ];
            if (!empty($search_text) && !is_numeric($search_text)) {
                $where['goods_name'] = $condition['goods_name'];
            }
        } elseif ($order_status == 1) {
            //查询所有待处理售后的商品对应的order_id
            $where = [
                'refund_status' => ['IN', [1, 2, 3, 4, -3, -1]],
                'website_id' => $this->website_id
            ];
            if (!empty($search_text) && !is_numeric($search_text)) {
                $where['goods_name'] = $condition['goods_name'];
            }
        } elseif ($order_status == 2) {
            //查询所有退款成功的商品对应的order_id
            $where = [
                'refund_status' => ['IN', [5]],
                'website_id' => $this->website_id
            ];
            if (!empty($search_text) && !is_numeric($search_text)) {
                $where['goods_name'] = $condition['goods_name'];
            }
        }
        $order_ids = $orderGoodsModel->getQuery($where, 'order_id', 'order_id DESC');
        if ($order_ids) {
            $order_ids = array_unique($order_ids);
            foreach ($order_ids as $key => $val) {
                $order_id[] = $val['order_id'];
            }

            if (!empty($search_text) && !is_numeric($search_text)) {
                unset($condition['goods_name']);
            }

            $condition['order_id'] = ['IN', $order_id];
            $data = $orderModel->pageQuery($page_index, $page_size, $condition, $order, 'order_id,order_no,shop_id,shop_name,promotion_money,coupon_money');
            $order_info = $data['data'];

            $order_status = new OrderStatus();
            foreach ($order_info as $k => $v) {
                //判断是否是整单退
                if ($v['promotion_money'] + $v['coupon_money'] > 0) {
                    //整单退
                    $order_info[$k]['refund_require_money'] = 0;
                    $order_info[$k]['order_item_list'] = $orderGoodsModel->getQuery(['order_id' => $v['order_id']], 'order_goods_id,sku_id,goods_name,price,num,goods_picture,sku_name,refund_status,refund_require_money', '');
                    foreach ($order_info[$k]['order_item_list'] as $k1 => $v1) {
                        //查询商品图片
                        $albumPictureModel = new AlbumPictureModel();
                        $order_info[$k]['order_item_list'][$k1]['picture'] = $albumPictureModel->Query(['pic_id' => $v1['goods_picture']], 'pic_cover_small')[0];
                        //获取状态名
                        $order_info[$k]['order_item_list'][$k1]['status_name'] = $order_status->getRefundStatus()[$v1['refund_status']]['status_name'];
                        if ($v1['refund_status'] == 1) {
                            $order_info[$k]['member_operation'] = [['no' => 'agree_refund', 'name' => '同意审核并打款'], ['no' => 'refuse', 'name' => '拒绝']];
                            $order_info[$k]['no_operation'] = '';
                            $order_info[$k]['order_item_list'][$k1]['member_operation'] = [];
                            $order_info[$k]['order_item_list'][$k1]['no_operation'] = '';
                        } else {
                            $order_info[$k]['member_operation'] = [];
                            $order_info[$k]['no_operation'] = '该订单正在通过线上处理售后，门店无法操作';
                            $order_info[$k]['order_item_list'][$k1]['member_operation'] = [];
                            $order_info[$k]['order_item_list'][$k1]['no_operation'] = '';
                        }
                        $order_info[$k]['refund_require_money'] += $v1['refund_require_money'];
                    }
                } else {
                    //单独退款
                    $order_info[$k]['member_operation'] = [];
                    $order_info[$k]['no_operation'] = '';
                    $order_info[$k]['refund_require_money'] = 0;
                    $order_info[$k]['order_item_list'] = $orderGoodsModel->getQuery(['order_id' => $v['order_id']], 'order_goods_id,sku_id,goods_name,price,num,goods_picture,sku_name,refund_status,refund_require_money', '');
                    foreach ($order_info[$k]['order_item_list'] as $k1 => $v1) {
                        //查询商品图片
                        $albumPictureModel = new AlbumPictureModel();
                        $order_info[$k]['order_item_list'][$k1]['picture'] = $albumPictureModel->Query(['pic_id' => $v1['goods_picture']], 'pic_cover_small')[0];
                        //获取状态名
                        $order_info[$k]['order_item_list'][$k1]['status_name'] = $order_status->getRefundStatus()[$v1['refund_status']]['status_name'];
                        if ($v1['refund_status'] == 1) {
                            $order_info[$k]['order_item_list'][$k1]['member_operation'] = [['no' => 'agree_refund', 'name' => '同意审核并打款'], ['no' => 'refuse', 'name' => '拒绝']];
                            $order_info[$k]['order_item_list'][$k1]['no_operation'] = '';
                            $order_info[$k]['refund_require_money'] += $v1['refund_require_money'];
                        } else {
                            $order_info[$k]['order_item_list'][$k1]['member_operation'] = [];
                            $order_info[$k]['order_item_list'][$k1]['no_operation'] = '该订单正在通过线上处理售后，门店无法操作';
                            $order_info[$k]['refund_require_money'] += $v1['refund_require_money'];
                        }
                    }
                }
            }

            return [
                'order_info' => $order_info,
                'page_count' => $data['page_count'],
                'total_count' => $data['total_count'],
            ];
        } else {
            return [
                'order_info' => [],
                'page_count' => 0,
                'total_count' => 0,
            ];
        }
    }


    /*
     * 核销记录
     */
    public function verificationLog($page_index, $page_size, $status, $search_text, $condition)
    {
        if ($status == 0) {
            //所有类型
            //订单类型
            $order_condition = [
                'is_deleted' => 0,
                'shipping_type' => 2,
                'order_status' => ['IN',[3,4]],
                'store_id' => $condition['store_id']
            ];
            if (!empty($search_text) && is_numeric($search_text)) {
                $order_condition['order_no'] = ['LIKE', '%' . $search_text . '%'];
            }
            $orderModel = new VslOrderModel();
            $orderGoodsModel = new VslOrderGoodsModel();
            $storeAssistantModel = new VslStoreAssistantModel();
            $order_info = $orderModel->getQuery($order_condition, 'order_id,order_no,order_status,shipping_type,store_id,assistant_id,sign_time', 'sign_time DESC');
            if ($order_info) {
                if (!empty($search_text) && !is_numeric($search_text)) {
                    $order_goods_condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
                }
                foreach ($order_info as $k => $v) {
                    //查询核销员名称
                    $order_info[$k]['assistant_name'] = $storeAssistantModel->Query(['assistant_id' => $v['assistant_id']], 'assistant_name')[0];
                    if (empty($order_info[$k]['assistant_name'])) {
                        $order_info[$k]['assistant_name'] = '';
                    }
                    //用于后面排序的字段
                    $order_info[$k]['verification_time'] = $v['sign_time'];
                    $order_info[$k]['type'] = 1;
                    //查询此订单下的商品信息
                    $order_goods_condition['order_id'] = $v['order_id'];
                    $order_info[$k]['order_item_list'] = $orderGoodsModel->getQuery($order_goods_condition, 'order_goods_id,goods_id,goods_name,sku_name,price,num,goods_picture', '');
                    if (empty($order_info[$k]['order_item_list'])) {
                        unset($order_info[$k]);
                    } else {
                        foreach ($order_info[$k]['order_item_list'] as $k1 => $v1) {
                            //查询商品图片
                            $albumPictureModel = new AlbumPictureModel();
                            $order_info[$k]['order_item_list'][$k1]['picture'] = $albumPictureModel->Query(['pic_id' => $v1['goods_picture']], 'pic_cover_small')[0];
                        }
                    }
                }
            } else {
                $order_info = [];
            }

            //礼品券类型
            $gift_condition = [
                'website_id' => $condition['website_id'],
                'shop_id' => $condition['shop_id'],
                'store_id' => $condition['store_id'],
                'state' => 2,
            ];
            if (!empty($search_text)) {
                $gift_condition['gift_voucher_code'] = ['LIKE', '%' . $search_text . '%'];
            }
            $gift_voucher_records_model = new VslGiftVoucherRecordsModel();
            $gift_voucher_model = new VslGiftVoucherModel();
            $promotion_gift_model = new VslPromotionGiftModel();
            $gift_voucher_records_info = $gift_voucher_records_model->getQuery($gift_condition, 'record_id,gift_voucher_id,gift_voucher_code,use_time,state,store_id,assistant_id', 'use_time DESC');
            if ($gift_voucher_records_info) {
                foreach ($gift_voucher_records_info as $k => $v) {
                    $fields = $gift_voucher_model->getInfo(['gift_voucher_id' => $v['gift_voucher_id']], 'giftvoucher_name,promotion_gift_id');
                    $gift_voucher_records_info[$k]['giftvoucher_name'] = $fields['giftvoucher_name'];

                    //查询核销员名称
                    $gift_voucher_records_info[$k]['assistant_name'] = $storeAssistantModel->Query(['assistant_id' => $v['assistant_id']], 'assistant_name')[0];
                    if (empty($gift_voucher_records_info[$k]['assistant_name'])) {
                        $gift_voucher_records_info[$k]['assistant_name'] = '';
                    }

                    //查询礼品券图片
                    $gift_picture = $promotion_gift_model->Query(['promotion_gift_id' => $fields['promotion_gift_id']], 'picture')[0];
                    $albumPictureModel = new AlbumPictureModel();
                    $gift_voucher_records_info[$k]['gift_picture'] = $albumPictureModel->Query(['pic_id' => $gift_picture], 'pic_cover_small')[0];

                    //用于后面排序的字段
                    $gift_voucher_records_info[$k]['verification_time'] = $v['use_time'];
                    $gift_voucher_records_info[$k]['type'] = 2;
                }
            } else {
                $gift_voucher_records_info = [];
            }

            //消费卡类型
            $card_condition = [
                'website_id' => $condition['website_id'],
                'store_id' => $condition['store_id'],
                'num' => ['>', 0],
            ];
            if (!empty($search_text)) {
                $card_condition['card_code'] = ['LIKE', '%' . $search_text . '%'];
            }
            $member_card_model = new VslMemberCardModel();
            $member_card_records_model = new VslMemberCardRecordsModel();
            $card_info = $member_card_model->getQuery($card_condition, 'card_id,goods_name,goods_picture,store_id,card_code', 'create_time DESC');
            if ($card_info) {
                foreach ($card_info as $k => $v) {
                    $data[] = $member_card_records_model->getQuery(['card_id' => $v['card_id']], 'record_id,num,create_time,assistant_id', 'create_time DESC');
                    $card_list = $data[0];
                    foreach ($card_list as $key => $val) {
                        $albumPictureModel = new AlbumPictureModel();
                        $card_list[$key]['card_picture'] = $albumPictureModel->Query(['pic_id' => $v['goods_picture']], 'pic_cover_small')[0];
                        $card_list[$key]['goods_name'] = $v['goods_name'];
                        $card_list[$key]['store_id'] = $v['store_id'];
                        $card_list[$key]['card_code'] = $v['card_code'];
                        //查询核销员名称
                        $card_list[$key]['assistant_name'] = $storeAssistantModel->Query(['assistant_id' => $val['assistant_id']], 'assistant_name')[0];
                        if (empty($card_list[$key]['assistant_name'])) {
                            $card_list[$key]['assistant_name'] = '';
                        }
                        //用于后面排序的字段
                        $card_list[$key]['verification_time'] = $val['create_time'];
                        $card_list[$key]['type'] = 3;
                    }
                }
            } else {
                $card_list = [];
            }

            //整合数组，重新排序
            if ($order_info || $gift_voucher_records_info || $card_info) {
                $list = array_merge($order_info, $gift_voucher_records_info, $card_list);
                //分页
                $page_index = (empty($page_index)) ? '1' : $page_index;
                $start = ($page_index - 1) * $page_size;
                $totals = count($list);
                $page_count = ceil($totals / $page_size);
                $pagedata = array_slice($list, $start, $page_size);

                return [
                    'data' => $pagedata,
                    'page_count' => $page_count,
                    'total_count' => $totals
                ];

            } elseif (empty($order_info) && empty($gift_voucher_records_info) && empty($card_info)) {
                return [
                    'data' => [],
                    'page_count' => 0,
                    'total_count' => 0
                ];
            }
        } elseif ($status == 1) {
            //订单
            $order_condition = [
                'is_deleted' => 0,
                'shipping_type' => 2,
                'order_status' => ['IN',[3,4]],
                'store_id' => $condition['store_id']
            ];
            if (!empty($search_text) && is_numeric($search_text)) {
                $order_condition['order_no'] = ['LIKE', '%' . $search_text . '%'];
            }
            $orderModel = new VslOrderModel();
            $orderGoodsModel = new VslOrderGoodsModel();
            $storeAssistantModel = new VslStoreAssistantModel();
            $order_data = $orderModel->pageQuery($page_index, $page_size, $order_condition, 'sign_time DESC', 'order_id,order_no,order_status,shipping_type,store_id,assistant_id,sign_time');
            $order_info = $order_data['data'];
            if ($order_info) {
                if (!empty($search_text) && !is_numeric($search_text)) {
                    $order_goods_condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
                }
                foreach ($order_info as $k => $v) {
                    //查询核销员名称
                    $order_info[$k]['assistant_name'] = $storeAssistantModel->Query(['assistant_id' => $v['assistant_id']], 'assistant_name')[0];
                    if (empty($order_info[$k]['assistant_name'])) {
                        $order_info[$k]['assistant_name'] = '';
                    }
                    //查询此订单下的商品信息
                    $order_goods_condition['order_id'] = $v['order_id'];
                    $order_info[$k]['order_item_list'] = $orderGoodsModel->getQuery($order_goods_condition, 'order_goods_id,goods_id,goods_name,sku_name,price,num,goods_picture', '');
                    if (empty($order_info[$k]['order_item_list'])) {
                        unset($order_info[$k]);
                    } else {
                        foreach ($order_info[$k]['order_item_list'] as $k1 => $v1) {
                            //查询商品图片
                            $albumPictureModel = new AlbumPictureModel();
                            $order_info[$k]['order_item_list'][$k1]['picture'] = $albumPictureModel->Query(['pic_id' => $v1['goods_picture']], 'pic_cover_small')[0];
                        }
                    }
                    $order_info[$k]['type'] = 1;
                }
                return [
                    'data' => $order_info,
                    'page_count' => $order_data['page_count'],
                    'total_count' => $order_data['total_count']
                ];
            } else {
                return [
                    'data' => [],
                    'page_count' => 0,
                    'total_count' => 0
                ];
            }
        } elseif ($status == 2) {
            //礼品券
            $gift_condition = [
                'website_id' => $condition['website_id'],
                'shop_id' => $condition['shop_id'],
                'store_id' => $condition['store_id'],
                'state' => 2,
            ];
            if (!empty($search_text)) {
                $gift_condition['gift_voucher_code'] = ['LIKE', '%' . $search_text . '%'];
            }
            $storeAssistantModel = new VslStoreAssistantModel();
            $gift_voucher_records_model = new VslGiftVoucherRecordsModel();
            $gift_voucher_model = new VslGiftVoucherModel();
            $promotion_gift_model = new VslPromotionGiftModel();
            $gift_data = $gift_voucher_records_model->pageQuery($page_index, $page_size, $gift_condition, 'use_time DESC', 'record_id,gift_voucher_id,gift_voucher_code,use_time,state,store_id,assistant_id');
            $gift_voucher_records_info = $gift_data['data'];
            if ($gift_voucher_records_info) {
                foreach ($gift_voucher_records_info as $k => $v) {
                    $fields = $gift_voucher_model->getInfo(['gift_voucher_id' => $v['gift_voucher_id']], 'giftvoucher_name,promotion_gift_id');
                    $gift_voucher_records_info[$k]['giftvoucher_name'] = $fields['giftvoucher_name'];

                    //查询核销员名称
                    $gift_voucher_records_info[$k]['assistant_name'] = $storeAssistantModel->Query(['assistant_id' => $v['assistant_id']], 'assistant_name')[0];
                    if (empty($gift_voucher_records_info[$k]['assistant_name'])) {
                        $gift_voucher_records_info[$k]['assistant_name'] = '';
                    }

                    //查询礼品券图片
                    $gift_picture = $promotion_gift_model->Query(['promotion_gift_id' => $fields['promotion_gift_id']], 'picture')[0];
                    $albumPictureModel = new AlbumPictureModel();
                    $gift_voucher_records_info[$k]['gift_picture'] = $albumPictureModel->Query(['pic_id' => $gift_picture], 'pic_cover_small')[0];
                    $gift_voucher_records_info[$k]['type'] = 2;
                }
                return [
                    'data' => $gift_voucher_records_info,
                    'page_count' => $gift_data['page_count'],
                    'total_count' => $gift_data['total_count']
                ];
            } else {
                return [
                    'data' => [],
                    'page_count' => 0,
                    'total_count' => 0
                ];
            }
        } elseif ($status == 3) {
            //消费卡
            $card_condition = [
                'website_id' => $condition['website_id'],
                'store_id' => $condition['store_id'],
                'num' => ['>', 0],
            ];
            if (!empty($search_text)) {
                $card_condition['card_code'] = ['LIKE', '%' . $search_text . '%'];
            }
            $storeAssistantModel = new VslStoreAssistantModel();
            $member_card_model = new VslMemberCardModel();
            $member_card_records_model = new VslMemberCardRecordsModel();
            $card_info = $member_card_model->getQuery($card_condition, 'card_id,goods_name,goods_picture,store_id,card_code', 'create_time DESC');
            if ($card_info) {
                foreach ($card_info as $k => $v) {
                    $data[] = $member_card_records_model->getQuery(['card_id' => $v['card_id']], 'record_id,num,create_time,assistant_id', 'create_time DESC');
                    $card_list = $data[0];
                    foreach ($card_list as $key => $val) {
                        $albumPictureModel = new AlbumPictureModel();
                        $card_list[$key]['card_picture'] = $albumPictureModel->Query(['pic_id' => $v['goods_picture']], 'pic_cover_small')[0];
                        $card_list[$key]['goods_name'] = $v['goods_name'];
                        $card_list[$key]['store_id'] = $v['store_id'];
                        $card_list[$key]['card_code'] = $v['card_code'];
                        //查询核销员名称
                        $card_list[$key]['assistant_name'] = $storeAssistantModel->Query(['assistant_id' => $val['assistant_id']], 'assistant_name')[0];
                        if (empty($card_list[$key]['assistant_name'])) {
                            $card_list[$key]['assistant_name'] = '';
                        }
                        $card_list[$key]['type'] = 3;
                    }
                }
                $page_index = (empty($page_index)) ? '1' : $page_index;
                $start = ($page_index - 1) * $page_size;
                $totals = count($card_list);
                $page_count = ceil($totals / $page_size);
                $pagedata = array_slice($card_list, $start, $page_size);

                return [
                    'data' => $pagedata,
                    'page_count' => $page_count,
                    'total_count' => $totals
                ];
            } else {
                return [
                    'data' => [],
                    'page_count' => 0,
                    'total_count' => 0
                ];
            }
        }
    }

    /*
     * 商品管理-添加商品-获取一级分类
     */
    public function getAddGoodsCategoryList($condition)
    {
        //查询商品表获取一级分类id
        $goodsModel = new VslGoodsModel();
        $goodsCategoryIds = $goodsModel->Query($condition, 'category_id_1');
        $goodsCategoryIds = array_unique($goodsCategoryIds);

        //查询一级分类名称
        $goodsCategoryModel = new VslGoodsCategoryModel();
        foreach ($goodsCategoryIds as $k => $v) {
            $arr['category_id'] = $v;
            $arr['category_name'] = $goodsCategoryModel->Query(['category_id' => $v], 'category_name')[0];
            if (empty($arr['category_name'])) {
                $arr['category_name'] = '未分类';
            }
            $arr['short_name'] = $goodsCategoryModel->Query(['category_id' => $v], 'short_name')[0];
            if (empty($arr['short_name'])) {
                $arr['short_name'] = '未分类';
            }
            $data[] = $arr;
        }
        return $data;
    }

    /*
    * 商品管理-添加商品-获取商品列表
    */
    public function getAddGoodsList($page_index, $page_size, $condition, $store_id)
    {
        //从商品表中筛选出的商品
        $goodsModel = new VslGoodsModel();
        $list = $goodsModel->pageQuery($page_index, $page_size, $condition, '', 'goods_id,goods_name,price,stock,sales,picture,store_list');
        $goods_info = $list['data'];
        //如果已经在门店中存在的商品则不返回
        foreach ($goods_info as $k => $v) {
            if ($v['store_list']) {
                $v['store_list'] = explode(',', $v['store_list']);
                if (in_array($store_id, $v['store_list'])) {
                    unset($goods_info[$k]);
                }
            }
            unset($v['store_list']);
        }
        $goods_info = array_values($goods_info);

        //关联相册表，查出商品对应的图片
        $albumPictureModel = new AlbumPictureModel();
        foreach ($goods_info as $key => $val) {
            $condition = [
                'pic_id' => $val['picture']
            ];
            $arr = $albumPictureModel->Query($condition, 'pic_cover_small')[0];
            $goods_info[$key]['goods_img'] = __IMG($arr);
            unset($arr);
        }

        return [
            'goods_info' => $goods_info,
            'page_count' => $list['page_count'],
            'total_count' => $list['total_count']
        ];
    }

    /*
     * 商品管理-添加商品-添加
     */
    public function addGoods($goods_id)
    {
        $goodsModel = new VslGoodsModel();
        $goods_info = $goodsModel->getInfo(['goods_id' => $goods_id], 'goods_id,goods_name,state,img_id_array');

        //关联相册表，查出商品对应的图片
        $albumPictureModel = new AlbumPictureModel();
        $goods_info['img_id_array'] = explode(',', $goods_info['img_id_array']);
        foreach ($goods_info['img_id_array'] as $key => $val) {
            $condition = [
                'pic_id' => $val
            ];
            $arr[] = $albumPictureModel->Query($condition, 'pic_cover_small')[0];
        }
        $goods_info['goods_img'] = $arr;

        //查询sku信息
        $goodsSkuModel = new VslGoodsSkuModel();
        $goods_info['sku_list'] = $goodsSkuModel->getQuery(['goods_id' => $goods_id], 'sku_id,sku_name,attr_value_items,market_price,price,stock,code', '');

        return $goods_info;
    }
}