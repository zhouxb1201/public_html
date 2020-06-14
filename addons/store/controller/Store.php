<?php

namespace addons\store\controller;

use addons\bargain\service\Bargain;
use addons\channel\model\VslChannelGoodsSkuModel;
use addons\coupontype\model\VslCouponTypeModel;
use addons\discount\server\Discount;
use addons\fullcut\service\Fullcut;
use addons\gift\model\VslPromotionGiftModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\groupshopping\model\VslGroupGoodsModel;
use addons\groupshopping\server\GroupShopping as GroupShoppingServer;
use addons\presell\service\Presell as PresellService;
use addons\seckill\server\Seckill;
use addons\seckill\server\Seckill as SeckillServer;
use addons\store\model\VslStoreModel;
use addons\store\Store as baseStore;
use addons\store\server\Store as storeServer;
use data\model\VslCartModel;
use data\model\VslGoodsModel;
use data\model\VslMemberModel;
use data\model\VslStoreCartModel;
use data\model\VslStoreGoodsModel as VslStoreGoodsModel;
use data\model\VslStoreGoodsSkuModel as VslStoreGoodsSkuModel;
use data\service\Address;
use data\service\Config;
use data\service\Goods as GoodsService;
use data\service\Member as MemberService;
use data\service\Order as OrderService;
use data\service\Order\Order as OrderBusiness;
use data\service\Promotion;
use data\service\promotion\GoodsExpress;
use think\Session;
use think\Validate;
use data\service\AddonsConfig;

/**
 * o2o门店控制器
 * Class GoodHelper
 * @package addons\store\controller
 */
class Store extends baseStore
{
    protected $is_seckill = 1;
    protected $is_discount = 1;
    protected $is_store = 1;
    protected $is_bargain = 1;
    protected $is_presell = 1;
    protected $is_coupon_type = 1;
    protected $gift_voucher = 0;
    protected $is_gift = 0;
    protected $is_distribution = 0;

    public function __construct()
    {
        parent::__construct();
    }

    /*
     * 门店列表
     */
    public function storeList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $search_text = request()->post("search_text", '');
        $status = request()->post("status", '');
        $excepted_store_id = input('post.excepted_store_id/a');
        $storeServer = new storeServer();
        $condition = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'store_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        if ($status != '') {
            $condition['status'] = $status;
        }
        if ($excepted_store_id != '') {
            $condition['store_id'] = ['not in', $excepted_store_id];
        }
        $list = $storeServer->storeList($page_index, $page_size, $condition);
        $is_store = getAddons('store', $this->website_id);
        $addon_status['is_store'] = $is_store;
        $list['addon_status'] = $addon_status;
        return $list;
    }

    /*
     * 添加修改门店
     * **/
    public function addOrUpdateStore()
    {
        $storeServer = new storeServer();
        //验证group_shopping表
//        $validate = new Validate([
//            'store_name' => 'require',
//            'store_tel' => 'require',
//            'province_id' => 'require',
//            'city_id' => 'require',
//            'district_id' => 'require',
//            'address' => 'require',
//        ]);
//        if (!$validate->check(request()->post())) {
//            return ['code' => 0, 'message' => $validate->getError()];
//        }
        if (!request()->post('address', 0)) {
            return ['code' => 0, 'message' => '请输入门店详细地址'];
        }
        $data['store_id'] = request()->post('store_id', 0);
        $data['store_name'] = request()->post('store_name', '');
        $data['city_id'] = request()->post('city_id', 0);
        $data['store_tel'] = request()->post('store_tel', '');
        $data['province_id'] = request()->post('province_id', 0);
        $data['district_id'] = request()->post('district_id', 0);
        $data['address'] = request()->post('address', 0);
        $data['status'] = request()->post('status', 0);
        $data['lat'] = request()->post('store_lat', 0);
        $data['lng'] = request()->post('store_lng', 0);
        $data['start_time'] = request()->post('start_time', '');
        $data['finish_time'] = request()->post('finish_time', '');
        $data['imageArray'] = request()->post('img_id_array/a', array());
        $img = '';
        if ($data['imageArray']) {
            foreach ($data['imageArray'] as $v) {
                $img .= $v . ',';
            }
        }
        $data['img_id_array'] = substr($img, 0, -1);
        $data['picture'] = $data['imageArray'][0];
        if (request()->post('store_id', 0)) {
            $ret_val = $storeServer->updateStore($data);
        } else {
            $ret_val = $storeServer->addStore($data);
        }


        if ($ret_val <= 0) {
            return AjaxReturn($ret_val);
        }
        if (request()->post('store_id', 0)) {
            $this->addUserLog('更新门店', $ret_val);
        } else {
            $this->addUserLog('添加门店', $ret_val);
        }
        return AjaxReturn(1);
    }

    /*
     * 店员列表
     */
    public function assistantList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $search_text = request()->post("search_text", '');
        $storeServer = new storeServer();
        $condition = array(
            'vsa.website_id' => $this->website_id,
            'vsa.shop_id' => $this->instance_id,
            'vsa.assistant_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        $list = $storeServer->assistantList($page_index, $page_size, $condition);
        return $list;
    }

    /*
     * 添加修改店员
     * **/
    public function addOrUpdateAssistant()
    {
        $storeServer = new storeServer();
        $validate_data = [
            'store_id' => 'require',
            'jobs_id' => 'require',
            'assistant_name' => 'require',
            'assistant_tel' => 'require',
        ];
        if (!request()->post('assistant_id', 0)) {
            $validate_data['password'] = 'require';
        }
        //验证assistant表
        $validate = new Validate($validate_data);
        if (!$validate->check(request()->post())) {
            return ['code' => 0, 'message' => $validate->getError()];
        }
        if (request()->post('assistant_id', 0)) {
            $ret_val = $storeServer->updateAssistant(request()->post());
        } else {
            $ret_val = $storeServer->addAssistant(request()->post());
        }
        if ($ret_val <= 0) {
            return AjaxReturn($ret_val);
        }
        if (request()->post('assistant_id', 0)) {
            $this->addUserLog('更新店员', $ret_val);
        } else {
            $this->addUserLog('添加店员', $ret_val);
        }
        return AjaxReturn(1);
    }

    /*
     * 岗位列表
     */
    public function jobsList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $search_text = request()->post("search_text", '');
        $storeServer = new storeServer();
        $condition = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'jobs_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        $list = $storeServer->jobsList($page_index, $page_size, $condition);
        return $list;
    }

    /*
     * 添加修改岗位
     * **/
    public function addOrUpdateJobs()
    {
        $storeServer = new storeServer();
        //验证group_shopping表
        $validate = new Validate([
            'jobs_name' => 'require'
        ]);
        if (!$validate->check(request()->post())) {
            return ['code' => 0, 'message' => $validate->getError()];
        }
        //验证验证group_shopping_goods表
        if (request()->post('jobs_id', 0)) {
            $ret_val = $storeServer->updateJobs(request()->post());
        } else {
            $ret_val = $storeServer->addJobs(request()->post());
        }


        if ($ret_val <= 0) {
            return AjaxReturn($ret_val);
        }
        if (request()->post('jobs_id', 0)) {
            $this->addUserLog('更新岗位', $ret_val);
        } else {
            $this->addUserLog('添加岗位', $ret_val);
        }
        return AjaxReturn(1);
    }

    /*
     * 删除门店
     */
    public function deleteStore()
    {
        $store_id = request()->post("store_id", 0);
        if (!$store_id) {
            return AjaxReturn(0);
        }
        $storeServer = new storeServer();
        $retval = $storeServer->deleteStore($store_id);

        if($retval == -1) {
            return json(['code' => -1, 'message' => '此门店存在核销订单，不能删除']);
        }

        if ($retval <= 0) {
            return AjaxReturn($retval);
        }
        $this->addUserLog('删除门店', $retval);
        return AjaxReturn(1);
    }

    /*
     * 删除岗位
     */
    public function deleteJobs()
    {
        $jobs_id = request()->post("jobs_id", 0);
        if (!$jobs_id) {
            return AjaxReturn(0);
        }
        $storeServer = new storeServer();
        $retval = $storeServer->deleteJobs($jobs_id);
        if ($retval <= 0) {
            return AjaxReturn($retval);
        }
        $this->addUserLog('删除岗位', $retval);
        return AjaxReturn(1);
    }

    /*
     * 删除店员
     */
    public function deleteAssistant()
    {
        $assistant_id = request()->post("assistant_id", 0);
        if (!$assistant_id) {
            return AjaxReturn(0);
        }
        $storeServer = new storeServer();
        $retval = $storeServer->deleteAssistant($assistant_id);
        if ($retval <= 0) {
            return AjaxReturn($retval);
        }
        $this->addUserLog('删除店员', $retval);
        return AjaxReturn(1);
    }

    /*
     * 启用禁用店员
     */
    public function enableOrUnable()
    {
        $assistant_id = request()->post("assistant_id", 0);
        $enable = request()->post("enable", 0);
        if (!$assistant_id) {
            return AjaxReturn(0);
        }
        $storeServer = new storeServer();
        if ($enable) {
            $retval = $storeServer->enableAssistant($assistant_id);
            $operation = '启用';
        } else {
            $retval = $storeServer->unableAssistant($assistant_id);
            $operation = '禁用';
        }

        if ($retval <= 0) {
            return AjaxReturn($retval);
        }
        $this->addUserLog($operation . '店员-' . $assistant_id, $assistant_id);
        return AjaxReturn(1);
    }

    /**
     * 获取省列表
     */
    public function getProvince()
    {
        $address = new Address();
        $province_list = $address->getProvinceList();
        return $province_list;
    }

    /**
     * 获取城市列表
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:, array>
     */
    public function getCity()
    {
        $address = new Address();
        $province_id = request()->post('province_id', 0);
        $city_list = $address->getCityList($province_id);
        return $city_list;
    }

    /**
     * 获取区域地址
     */
    public function getDistrict()
    {
        $address = new Address();
        $city_id = request()->post('city_id', 0);
        $district_list = $address->getDistrictList($city_id);
        return $district_list;
    }

    /*
     * 根据店铺id获取门店列表
     */
    public function getStoreList()
    {
        $shop_id = request()->post("shop_id", 0);
        $lng = request()->post("lng", 0);
        $lat = request()->post("lat", 0);
        $store_list = request()->post("store_list");
        $storeServer = new storeServer();
        $condition = array(
            'website_id' => $this->website_id,
            'shop_id' => $shop_id,
            'status' => 1,
        );
        $place = [
            'lng' => $lng,
            'lat' => $lat
        ];
        if ($store_list) {
            $store_id = explode(',', $store_list);
            $condition['store_id'] = ['IN', $store_id];
        }
        $list = $storeServer->storeListForFront(1, 0, $condition, $place);
        return $list;
    }

    /*
     * wap根据订单id核销订单
     */
    public function pickupOrder()
    {
        $order_id = request()->post("order_id", 0);
        if (!$order_id) {
            return json(AjaxReturn(0));
        }
        $storeServer = new storeServer();
        $result = $storeServer->pickupOrder($order_id);
        if (!$result) {
            return json(AjaxReturn(0));
        }
        return json(['code' => 1, 'message' => '操作成功']);
    }

    /*
     * wap根据核销码获取订单信息
     */
    public function getOrderListByCode()
    {
        $code = request()->post("code", 0);
        if (!$code) {
            return json(AjaxReturn(0));
        }
        $condition['is_deleted'] = 0; // 未删除订单
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['verification_code'] = $code;
        $order_service = new OrderService();
        $list = $order_service->getOrderList(1, 0, $condition, 'create_time DESC');
        $order_list = [];
        foreach ($list['data'] as $k => $order) {
            $order_list[$k]['order_id'] = $order['order_id'];
            $order_list[$k]['order_no'] = $order['order_no'];
            $order_list[$k]['out_order_no'] = $order['out_trade_no'];
            $order_list[$k]['shop_id'] = $order['shop_id'];
            $order_list[$k]['shop_name'] = $order['shop_name'] ?: '自营店';
            $order_list[$k]['order_money'] = $order['order_money'];
            $order_list[$k]['order_status'] = $order['order_status'];
            $order_list[$k]['status_name'] = $order['status_name'];
            $order_list[$k]['pay_type_name'] = $order['pay_type_name'];
            $order_list[$k]['is_evaluate'] = $order['is_evaluate'];
            $order_list[$k]['verification_code'] = $order['verification_code'];
            $order_list[$k]['verification_qrcode'] = __IMG($order['verification_qrcode']);
            $order_list[$k]['member_operation'] = array_merge($order['member_operation'], [['no' => 'detail', 'name' => '订单详情']]);
            $order_list[$k]['promotion_status'] = ($order['promotion_money'] + $order['coupon_money'] > 0) ?: false;

            foreach ($order['order_item_list'] as $key_sku => $item) {
                $order_list[$k]['order_item_list'][$key_sku]['order_goods_id'] = $item['order_goods_id'];
                $order_list[$k]['order_item_list'][$key_sku]['goods_id'] = $item['goods_id'];
                $order_list[$k]['order_item_list'][$key_sku]['sku_id'] = $item['sku_id'];
                $order_list[$k]['order_item_list'][$key_sku]['goods_name'] = $item['goods_name'];
                $order_list[$k]['order_item_list'][$key_sku]['price'] = $item['price'];
                $order_list[$k]['order_item_list'][$key_sku]['num'] = $item['num'];
                $order_list[$k]['order_item_list'][$key_sku]['pic_cover'] = getApiSrc($item['picture']['pic_cover']);
                $order_list[$k]['order_item_list'][$key_sku]['spec'] = $item['spec'];
                $order_list[$k]['order_item_list'][$key_sku]['status_name'] = $item['status_name'];
                $order_list[$k]['order_item_list'][$key_sku]['refund_status'] = $item['refund_status'];
            }

            // 当订单需要进行整单售后时，这个字段取订单商品第一个商品的售后状态（目前正确情况，所有商品的refund_status一样），用于判断整单售后操作
            $order_list[$k]['order_refund_status'] = reset($order_list[$k]['order_item_list'])['refund_status'];

        }

        return json([
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'order_list' => $order_list,
            ]
        ]);
    }

    /*
     * wap获取门店订单
     */
    public function getStoreOrderList()
    {
        $store_id = request()->post("store_id", 0);
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size') ?: PAGESIZE;
        $order_status = request()->post('order_status');
        $search_text = request()->post('search_text');
        if (!$store_id) {
            return json(AjaxReturn(0));
        }
        $condition['is_deleted'] = 0; // 未删除订单
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['store_id'] = $store_id;
        $condition['shipping_type'] = 2;
        if (is_numeric($search_text)) {
            $condition['order_no'] = ['LIKE', '%' . $search_text . '%'];
        } elseif (!empty($search_text)) {
            $condition['or'] = true;
            $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
        }
        if ($order_status != '') {
            // $order_status 1 待提货
            if ($order_status == 1) {
                // 订单状态为待发货实际为已经支付未完成还未发货的订单
                $condition['shipping_status'] = 0; // 0 待提货
                $condition['pay_status'] = 2; // 2 已支付
                $condition['order_status'][] = ['neq', 4]; // 4 已完成
                $condition['order_status'][] = ['neq', 5]; // 5 关闭订单
                $condition['order_status'][] = ['neq', -1]; // -1 售后订单
            } else {
                $condition['order_status'] = $order_status;
            }
        }
        $order_service = new OrderService();
        $list = $order_service->getOrderList($page_index, $page_size, $condition, 'create_time DESC');
        $order_list = [];
        foreach ($list['data'] as $k => $order) {
            $order_list[$k]['order_id'] = $order['order_id'];
            $order_list[$k]['order_no'] = $order['order_no'];
            $order_list[$k]['out_order_no'] = $order['out_trade_no'];
            $order_list[$k]['shop_id'] = $order['shop_id'];
            $order_list[$k]['shop_name'] = $order['shop_name'] ?: '自营店';
            $order_list[$k]['order_money'] = $order['order_money'];
            $order_list[$k]['order_status'] = $order['order_status'];
            $order_list[$k]['status_name'] = $order['status_name'];
            $order_list[$k]['pay_type_name'] = $order['pay_type_name'];
            $order_list[$k]['is_evaluate'] = $order['is_evaluate'];
            $order_list[$k]['member_operation'] = array_merge($order['member_operation'], [['no' => 'detail', 'name' => '订单详情']]);
            $order_list[$k]['promotion_status'] = ($order['promotion_money'] + $order['coupon_money'] > 0) ?: false;

            foreach ($order['order_item_list'] as $key_sku => $item) {
                $order_list[$k]['order_item_list'][$key_sku]['order_goods_id'] = $item['order_goods_id'];
                $order_list[$k]['order_item_list'][$key_sku]['goods_id'] = $item['goods_id'];
                $order_list[$k]['order_item_list'][$key_sku]['sku_id'] = $item['sku_id'];
                $order_list[$k]['order_item_list'][$key_sku]['goods_name'] = $item['goods_name'];
                $order_list[$k]['order_item_list'][$key_sku]['price'] = $item['price'];
                $order_list[$k]['order_item_list'][$key_sku]['num'] = $item['num'];
                $order_list[$k]['order_item_list'][$key_sku]['pic_cover'] = getApiSrc($item['picture']['pic_cover']);
                $order_list[$k]['order_item_list'][$key_sku]['spec'] = $item['spec'];
                $order_list[$k]['order_item_list'][$key_sku]['status_name'] = $item['status_name'];
                $order_list[$k]['order_item_list'][$key_sku]['refund_status'] = $item['refund_status'];
            }

            // 当订单需要进行整单售后时，这个字段取订单商品第一个商品的售后状态（目前正确情况，所有商品的refund_status一样），用于判断整单售后操作
            $order_list[$k]['order_refund_status'] = reset($order_list[$k]['order_item_list'])['refund_status'];

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

    /*
     * wap根据店铺id获取门店列表
     */
    public function getStoreListForWap()
    {
        $order = request()->post('order');
        $sort = request()->post('sort') ?: 'DESC';
        $search_text = request()->post('search_text');
        $shop_id = request()->post("shop_id", 0);
        $lng = request()->post("lng", '');
        $lat = request()->post("lat", '');
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", 0);

        $storeServer = new storeServer();
        $condition = array(
            'website_id' => $this->website_id,
            'shop_id' => $shop_id,
            'status' => 1,
        );

        //模糊搜索门店
        if ($search_text) {
            $condition['store_name'] = ['LIKE', $search_text . '%'];
        }

        $place = [
            'lng' => $lng,
            'lat' => $lat
        ];

        $list = $storeServer->storeListForFront($page_index, $page_size, $condition, $place);

        //按距离排序
        if ($order == 'distance' && $sort == 'DESC') {
            array_multisort(array_column($list['store_list'], 'distance'), SORT_DESC, $list['store_list']);
        }
        if ($order == 'distance' && $sort == 'ASC') {
            array_multisort(array_column($list['store_list'], 'distance'), SORT_ASC, $list['store_list']);
        }

        //按人气排序
        if ($order == 'score' && $sort == 'DESC') {
            array_multisort(array_column($list['store_list'], 'score'), SORT_DESC, $list['store_list']);
        }
        if ($order == 'score' && $sort == 'ASC') {
            array_multisort(array_column($list['store_list'], 'score'), SORT_ASC, $list['store_list']);
        }

        //按销量排序
        if ($order == 'sales' && $sort == 'DESC') {
            array_multisort(array_column($list['store_list'], 'total_sales'), SORT_DESC, $list['store_list']);
        }
        if ($order == 'sales' && $sort == 'ASC') {
            array_multisort(array_column($list['store_list'], 'total_sales'), SORT_ASC, $list['store_list']);
        }

        if (!$list) {
            return json(AjaxReturn(0));
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => $list]);
    }

    /*
     * wap获取平台下所有门店列表
     */
    public function getAllStoreListForWap()
    {
        $order = request()->post('order');
        $sort = request()->post('sort') ?: 'DESC';
        $search_text = request()->post('search_text');
        $lng = request()->post("lng", '');
        $lat = request()->post("lat", '');
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", 0);

        $storeServer = new storeServer();
        $condition = array(
            'website_id' => $this->website_id,
            'status' => 1,
        );

        //模糊搜索门店
        if ($search_text) {
            $condition['store_name'] = ['LIKE', $search_text . '%'];
        }

        $place = [
            'lng' => $lng,
            'lat' => $lat
        ];

        $list = $storeServer->storeListForFront($page_index, $page_size, $condition, $place);

        //按距离排序
        if ($order == 'distance' && $sort == 'DESC') {
            array_multisort(array_column($list['store_list'], 'distance'), SORT_DESC, $list['store_list']);
        }
        if ($order == 'distance' && $sort == 'ASC') {
            array_multisort(array_column($list['store_list'], 'distance'), SORT_ASC, $list['store_list']);
        }

        //按人气排序
        if ($order == 'score' && $sort == 'DESC') {
            array_multisort(array_column($list['store_list'], 'score'), SORT_DESC, $list['store_list']);
        }
        if ($order == 'score' && $sort == 'ASC') {
            array_multisort(array_column($list['store_list'], 'score'), SORT_ASC, $list['store_list']);
        }

        //按销量排序
        if ($order == 'sales' && $sort == 'DESC') {
            array_multisort(array_column($list['store_list'], 'total_sales'), SORT_DESC, $list['store_list']);
        }
        if ($order == 'sales' && $sort == 'ASC') {
            array_multisort(array_column($list['store_list'], 'total_sales'), SORT_ASC, $list['store_list']);
        }

        if (!$list) {
            return json(AjaxReturn(0));
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => $list]);
    }

    /*
     * wap获取操作台权限
     */
    public function getStoreModule()
    {
        $lng = request()->post("lng", '');
        $lat = request()->post("lat", '');
        $storeServer = new storeServer();
        $condition = array(
            'website_id' => $this->website_id,
            'status' => 1,
        );
        $place = [
            'lng' => $lng,
            'lat' => $lat
        ];
        $list = $storeServer->storeListForFront(1, 0, $condition, $place);
        if (!$list) {
            return json(AjaxReturn(0));
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => $list]);
    }

    /*
     * 获取门店配置
     */
    public function getStoreSet()
    {
        $storeServer = new storeServer();
        $data = $storeServer->getStoreSet();
        return $data;
    }

    /*
     * 基础设置
     */
    public function storeSet()
    {
        $data = request()->post();
        $storeServer = new storeServer();
        $res = $storeServer->storeSet($data);
        return AjaxReturn($res);
    }

    /*
     * 门店首页
     */
    public function storeIndex()
    {
        $lng = request()->post("lng", '');
        $lat = request()->post("lat", '');
        $store_id = request()->post("store_id", 0);

        $place = [
            'lng' => $lng,
            'lat' => $lat
        ];

        $condition = array(
            'website_id' => $this->website_id,
            'status' => 1,
            'store_id' => $store_id
        );
        $storeServer = new storeServer();
        $data = $storeServer->storeIndex($condition, $place);

        if (!$data) {
            return json(AjaxReturn(0));
        }

        if (empty($data[0]['store_name'])) {
            return json([
                'code' => 0,
                'message' => '店铺不存在',
                'data' => []
            ]);
        }
        return json([
            'code' => 1,
            'message' => '获取成功',
            'data' => $data[0]
        ]);
    }

    /*
     * 门店首页返回此门店下的所有商品的一级分类
     */
    public function getStoreGoodsCategoryList()
    {
        $store_id = request()->post("store_id", 0);
        $condition = array(
            'website_id' => $this->website_id,
            'store_id' => $store_id,
            'state' => 1
        );
        $storeServer = new storeServer();
        $data = $storeServer->getStoreGoodsCategoryList($condition);
        return json([
            'code' => 1,
            'message' => '获取成功',
            'data' => $data
        ]);
    }

    /*
     * 门店首页获取某个分类下的所有商品
     */
    public function getStoreGoods()
    {
        $store_id = request()->post("store_id", 0);
        $category_id = request()->post("category_id", 0);
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $mic_goods = request()->post('mic_goods', 0);
        $seckill_id = request()->post('seckill_id', 0);
        $channel_id = request()->post('channel_id', 0);
        $bargain_id = request()->post('bargain_id', 0);
        $record_id = request()->post('record_id', 0);

        $condition = array(
            'website_id' => $this->website_id,
            'store_id' => $store_id,
            'category_id_1' => $category_id,
            'state' => 1,
        );
        $storeServer = new storeServer();
        $goods_info = $storeServer->getStoreGoods($page_index, $page_size, $condition);

        if (empty($goods_info['goods_list'])) {
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => $goods_info
            ]);
        }

        //开始处理活动信息
        $goods_server = new GoodsService();
        foreach ($goods_info['goods_list'] as $key => $value) {
            if ($this->is_seckill && !$mic_goods) {
                //判断当前商品是否是秒杀商品，并且没有过期
                $seckill_server = new SeckillServer();
                if ($seckill_id) {
                    $condition_is_seckill['s.seckill_id'] = $seckill_id;
                    $condition_is_seckill['nsg.goods_id'] = $value['goods_id'];
                    $is_seckill = $seckill_server->isSeckillGoods($condition_is_seckill);
                } else {
                    $condition_is_seckill['nsg.goods_id'] = $value['goods_id'];
                    $is_seckill = $seckill_server->isSkuStartSeckill($condition_is_seckill);
                    $seckill_id = $is_seckill['seckill_id'];
                }
            }

            if ($this->is_bargain && !$mic_goods) {
                $bargain_server = new Bargain();
                if (!empty($bargain_id)) {
                    //砍价是否过期
                    $condition_bargain['website_id'] = $this->website_id;
                    $condition_bargain['bargain_id'] = $bargain_id;
                    $is_bargain = $bargain_server->isBargain($condition_bargain, 0);
                } else {
                    $condition_bargain['website_id'] = $this->website_id;
                    $condition_bargain['goods_id'] = $value['goods_id'];
                    $condition_bargain['end_bargain_time'] = ['>=', time()];//未结束的
                    $is_bargain = $bargain_server->isBargainByGoodsId($condition_bargain, 0);
                }
            }

            $is_group = 0;
            $msg = '';
            if ($is_seckill && !$mic_goods) {
                $redis = $this->connectRedis();
                //入商品队列
                $condition_sekcill_sku['seckill_id'] = $seckill_id;
                $condition_sekcill_sku['goods_id'] = $value['goods_id'];
                $seckill_num_list = $seckill_server->getAllSeckillSkuList($condition_sekcill_sku);
                $seckill_num_arr = objToArr($seckill_num_list);
                $seckill_sales = 0;
                foreach ($seckill_num_arr as $k => $sku_item) {
                    $sku_id = $sku_item['sku_id'];
                    $store = $sku_item['remain_num'];
                    //如果登录了，则需要看该用户购买了多少个秒杀商品
                    $uid = $this->uid ?: 0;
                    if ($uid) {
                        $goods_service = new GoodsService();
                        $website_id = $this->website_id;
                        $buy_num = $goods_service->getActivityOrderSku($uid, $sku_id, $website_id, $seckill_id);
                        $sku_buy_num[$sku_id] = $buy_num;
                    }
                    //redis队列key值
                    $redis_goods_sku_store_key = 'store_' . $seckill_id . '_' . $value['goods_id'] . '_' . $sku_id;//每个活动的库存都不一样
                    $is_index = $redis->llen($redis_goods_sku_store_key);
                    if (!$is_index) {
                        for ($num = 0; $num < $store; $num++) {
                            $redis->rpush($redis_goods_sku_store_key, 1);
                        }
                    }
                }
                $condition_seckill['ns.website_id'] = $this->website_id;
                $condition_seckill['ns.seckill_id'] = $seckill_id;
                $condition_seckill['nsg.goods_id'] = $value['goods_id'];
                $seckill_sku_price_arrs = $seckill_server->getGoodsSkuArr($condition_seckill, 'nsg.sku_id, nsg.seckill_price, nsg.remain_num, nsg.seckill_limit_buy, nsg.seckill_sales');
                $seckill_id_arr = array_column($seckill_sku_price_arrs, 'sku_id');
                $seckill_price_arr = array_column($seckill_sku_price_arrs, 'seckill_price');
                $seckill_stock_arr = array_column($seckill_sku_price_arrs, 'remain_num');
                $seckill_limit_buy_arr = array_column($seckill_sku_price_arrs, 'seckill_limit_buy');
                $seckill_sales = array_column($seckill_sku_price_arrs, 'seckill_sales');
                $seckill_sku_price_arr = array_combine($seckill_id_arr, $seckill_price_arr);
                $seckill_sku_stock_arr = array_combine($seckill_id_arr, $seckill_stock_arr);
                $seckill_sku_limit_buy_arr = array_combine($seckill_id_arr, $seckill_limit_buy_arr);
            }

            if (getAddons('groupshopping', $this->website_id, $value['goods_detail']['shop_id'])) {
                $groupGoods = new VslGroupGoodsModel();
                $group_server = new GroupShoppingServer();
                $is_group = $is_seckill ? 0 : $group_server->isGroupGoods($value['goods_id']);//判断当前商品是否是拼团商品
            }

            $member_discount = $value['goods_detail']['member_discount'];
            $member_is_label = $value['goods_detail']['member_is_label'];// 是否取整

            $is_allow_buy = true;// 查询是否该用户有购买该商品权限by sgw
            $is_allow_browse = true;
            if ($this->uid) {
                $is_allow_buy = $goods_server->isAllowToBuyThisGoods($this->uid, $value['goods_id']);
                $is_allow_browse = $goods_server->isAllowToBrowse($this->uid, $value['goods_id']); // 是否有浏览权限
                //如果没有浏览权限就不返回
                if ($is_allow_browse == false) {
                    unset($goods_info['goods_list'][$key]);
                }
            }
            $discount_choice = $value['goods_detail']['discount_choice'] ?: 1; // todo... 不用返回，是否固定金额，如果是所有商品价格都取固定金额（包括sku价格）1折扣 2 固定金额

            // 获取限时折扣
            $promotion_info['discount_num'] = 10;
            if ($this->is_discount && !$mic_goods) {
                $discount_service = new Discount();
                $promotion_info = $discount_service->getPromotionInfo($value['goods_id'], $value['goods_detail']['shop_id'], $value['goods_detail']['website_id']);
            }

            $goods_detail['goods_id'] = $value['goods_id'];
            $goods_detail['state'] = $value['state'];
            $goods_detail['shop_id'] = $value['shop_id'];
            $goods_detail['goods_name'] = $value['goods_name'];
            $goods_detail['sales'] = $value['sales'];
            $goods_detail['goods_img'] = $value['goods_img'];
            $goods_detail['min_buy'] = $value['goods_detail']['min_buy'];
            $goods_detail['max_buy'] = $value['goods_detail']['max_buy'];
            $goods_detail['collects'] = $value['goods_detail']['collects'];
            $goods_detail['goods_type'] = $value['goods_detail']['goods_type'];
            $goods_detail['single_limit_buy'] = $value['goods_detail']['single_limit_buy'];

            if ($value['goods_detail']['shipping_fee_type'] == 0) {
                $goods_detail['shipping_fee'] = '包邮';
            } elseif ($value['goods_detail']['shipping_fee_type'] == 1) {
                $goods_detail['shipping_fee'] = $value['goods_detail']['shipping_fee'];
            } elseif ($value['goods_detail']['shipping_fee_type'] == 2) {
                $user_location = get_city_by_ip();
                if ($user_location['status'] == 1) {
                    // 定位成功，查询当前城市的运费
                    $goods_express = new GoodsExpress();
                    $address = new Address();
                    $city = $address->getCityId($user_location["city"]);
                    $district = $address->getCityFirstDistrict($city['city_id']);
                    $express = $goods_express->getGoodsExpressTemplate([['goods_id' => $value['goods_id'], 'count' => 1]], $district)['totalFee'];
                    $goods_detail['shipping_fee'] = $express;
                }
            }

            //预售活动信息
            $presell_list = [];
            if ($this->is_presell && !$mic_goods) {
                $presell = new PresellService();
                $presell_info = $presell->getPresellInfoByGoodsId($value['goods_id']);
                if (!empty($presell_info)) {
                    foreach ($presell_info as $p => $pv) {
                        //获取已购买的数量，减去得到剩余数量
                        $have_buy = $presell->get_presell_sku_num($presell_info[0]['presell_id'], $pv['sku_id']);
                        $temp_sku['over_num'] = $pv['presell_num'] - $have_buy;
                        $presell_list['presellnum'] += $temp_sku['over_num']; //预售数量
                        $presell_list['max_buy'] += $pv['max_buy']; //预售数量
                    }
                    //查出当前用户购买了多少个该商品
                    $presell_num = $goods_server->getActivityOrderSkuNum($this->uid ?: 0, $presell_info[0]['sku_id'], $this->website_id, '4', $presell_info[0]['id']);
                    $presell_list['name'] = $presell_info[0]['name'];
                    $presell_list['firstmoney'] = $presell_info[0]['firstmoney'];
                    $presell_list['allmoney'] = $presell_info[0]['allmoney'];
                    $presell_list['start_time'] = $presell_info[0]['start_time'];
                    $presell_list['end_time'] = $presell_info[0]['end_time'];
                    $presell_list['pay_start_time'] = $presell_info[0]['pay_start_time'];
                    $presell_list['pay_end_time'] = $presell_info[0]['pay_end_time'];
                    $presell_list['send_goods_time'] = $presell_info[0]['send_goods_time'];
                    $presell_list['maxbuy'] = ($presell_list['max_buy'] - $presell_num >= 0) ? $presell_list['max_buy'] - $presell_num : 0;   //限购数量
                    $presell_list['vrnum'] = $presell_info[0]['vrnum'];      //虚拟限购数量
                    $presell_list['presell_id'] = $presell_info[0]['id'];      //预售活动ID
                    $count_people = $presell->get_presell_buy_num($presell_list['presell_id']); //总购买人数
                    $user_buy_count = $presell->get_user_count($presell_list['presell_id']);    //当前用户购买
                    $presell_list['have_buy_now'] = $user_buy_count;
                    $presell_list['presell_count_people'] = $count_people['buy_num'] ? $count_people['buy_num'] : 0;
                    $goods_detail['sales'] += $presell_info[0]['presell_sales'];
                    //判断状态是进行中还是
                    if (time() > $presell_info[0]['start_time'] && time() < $presell_info[0]['end_time']) {
                        $presell_list['state'] = 1; //正在进行
                    } else if (time() < $presell_list['start_time']) {
                        $presell_list['state'] = 2;//没开始
                    } else {
                        $presell_list['state'] = 3;//结束了
                    }
                    $is_presell = 1;
                } else {
                    $presell_list['name'] = $presell_info[0]['name'];
                    $presell_list['firstmoney'] = '';
                    $presell_list['allmoney'] = '';
                    $presell_list['start_time'] = '';
                    $presell_list['end_time'] = '';
                    $presell_list['pay_start_time'] = '';
                    $presell_list['pay_end_time'] = '';
                    $presell_list['send_goods_time'] = '';
                    $presell_list['presellnum'] = '';
                    $presell_list['maxbuy'] = '';
                    $presell_list['vrnum'] = '';
                    $presell_list['presell_id'] = '';
                    $presell_list['presell_count_people'] = '';
                    $is_presell = 0;
                }
            } else {
                $is_presell = 0;
            }

            // 满减送
            $full_cut_list = [];
            if (/*$this->is_full_cut &&*/ !$mic_goods) {
                $full_cut_server = new Fullcut();
//                $full_cut_condition['shop_id'] = $goods_data['shop_id'];
//                $full_cut_condition['website_id'] = $this->website_id;
//                $full_cut_condition['status'] = 1;
//                $full_cut_condition['start_time'] = ['<=', time()];
//                $full_cut_condition['end_time'] = ['>=', time()];
                $full_cut_info = $full_cut_server->goodsFullCut($value['goods_id']);
                $full_cut_list = [];

                foreach ($full_cut_info as $k => $v) {
                    $full_cut_list[$k]['mansong_id'] = $v['mansong_id'];
                    $full_cut_list[$k]['mansong_name'] = $v['mansong_name'];
                    $full_cut_list[$k]['start_time'] = $v['start_time'];
                    $full_cut_list[$k]['end_time'] = $v['end_time'];
                    $full_cut_list[$k]['shop_id'] = $v['shop_id'];
                    $full_cut_list[$k]['shop_name'] = $v['shop_name'];
                    $full_cut_list[$k]['range'] = $v['range'];
                    $full_cut_list[$k]['rules'] = [];
                    foreach ($v->rules as $i => $r) {
                        $full_cut_list[$k]['rules'][$i]['price'] = $r['price'];
                        $full_cut_list[$k]['rules'][$i]['discount'] = $r['discount'];
                        $full_cut_list[$k]['rules'][$i]['free_shipping'] = $r['free_shipping'];
                        $full_cut_list[$k]['rules'][$i]['give_point'] = $r['give_point'];
                        if ($r['give_coupon'] && $this->is_coupon_type) {
                            $coupon_type_model = new VslCouponTypeModel();
                            $full_cut_list[$k]['rules'][$i]['coupon_type_id'] = $r['give_coupon'] ?: '';
                            $full_cut_list[$k]['rules'][$i]['coupon_type_name'] = $coupon_type_model::get($r['give_coupon'])['coupon_name'] ?: '';
                        } else {
                            $full_cut_list[$k]['rules'][$i]['coupon_type_id'] = '';
                            $full_cut_list[$k]['rules'][$i]['coupon_type_name'] = '';
                        }
                        //礼品券
                        if ($r['gift_card_id'] && $this->gift_voucher) {
                            $gift_voucher = new VslGiftVoucherModel();
                            $full_cut_list[$k]['rules'][$i]['gift_card_id'] = $r['gift_card_id'];
                            $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id' => $r['gift_card_id']], 'giftvoucher_name')['giftvoucher_name'];
                            $full_cut_list[$k]['rules'][$i]['gift_voucher_name'] = $giftvoucher_name;//送优惠券
                        } else {
                            $full_cut_list[$k]['rules'][$i]['gift_card_id'] = '';
                            $full_cut_list[$k]['rules'][$i]['gift_voucher_name'] = '';
                        }
                        //赠品
                        if ($r['gift_id'] && $this->is_gift) {

                            $gift_mdl = new VslPromotionGiftModel();
                            $full_cut_list[$k]['rules'][$i]['gift_id'] = $r['gift_id'];
                            $gift_name = $gift_mdl->getInfo(['promotion_gift_id' => $r['gift_id']], 'gift_name')['gift_name'];
                            $full_cut_list[$k]['rules'][$i]['gift_name'] = $gift_name;//送优惠券
                        } else {
                            $full_cut_list[$k]['rules'][$i]['gift_id'] = '';
                            $full_cut_list[$k]['rules'][$i]['gift_name'] = '';
                        }
                    }
                }
            }

            //秒杀活动
            $seckill_list = [];
            if ($this->is_seckill && !$mic_goods) {
                //获取即将进行的最近一场的seckill_id。 ['sg.goods_id'=>$goods_id,'s.seckill_now_time'=>['>=',time()-24*3600]]
                $seckill_id_condition['sg.goods_id'] = $value['goods_id'];
                $seckill_id_condition['s.seckill_now_time'] = ['>=', time() - 24 * 3600];
                $seckill_id = $seckill_server->getSeckillId($seckill_id_condition);
                if ($seckill_id) {
                    $condition_seckill['ns.website_id'] = $this->website_id;
                    $condition_seckill['ns.seckill_id'] = $seckill_id;
                    $condition_seckill['nsg.goods_id'] = $value['goods_id'];
                    $seckill_goods_list = $seckill_server->getWapSeckillGoodsList($condition_seckill, 'ns.seckill_now_time,nsg.seckill_num,nsg.remain_num');
                    $seckill_goods_arr = objToArr($seckill_goods_list);
                    $seckill_num = 0;
                    $remain_num = 0;
                    foreach ($seckill_goods_arr as $k => $seck_info) {
                        $seckill_num = $seckill_num + $seck_info['seckill_num'];
                        $remain_num = $remain_num + $seck_info['remain_num'];
                    }
                    $seckill_list['seckill_id'] = $seckill_id;
                    $seckill_list['seckill_num'] = $seckill_num;
                    $seckill_list['remain_num'] = $remain_num;
                    $seckill_now_time = $seckill_goods_arr[0]['seckill_now_time'];
                    $now_time = time();
                    //是今天的判断是否正在进行或者未开始，结束
                    if ($now_time >= $seckill_now_time && $now_time <= $seckill_now_time + 24 * 3600) {//正在进行
                        $seckill_list['seckill_day'] = '';
                        $seckill_list['seckill_status'] = 'going';
                        $seckill_list['seckill_time'] = '';
                        $seckill_list['start_time'] = '';
                        $seckill_list['end_time'] = $seckill_now_time + 24 * 3600;
                    } elseif ($now_time > $seckill_now_time + 24 * 3600) {//已结束
                        $seckill_list['seckill_day'] = 'ended';
                        $seckill_list['seckill_day'] = date('m-d', $seckill_now_time);
                        $seckill_list['seckill_time'] = date('H', $seckill_now_time);
                        $seckill_list['start_time'] = '';
                    } elseif ($now_time < $seckill_now_time) {//未开始
                        $today_date = date('Y-m-d', time());
                        $tomorrow_date = date('Y-m-d', strtotime('+1 day'));
                        $seckill_now_date = date('Y-m-d', $seckill_now_time);

                        if ($seckill_now_date == $today_date) {//今天还未开始
                            $seckill_list['seckill_day'] = 'today';
                            $seckill_list['seckill_status'] = 'unstart';
                            $seckill_list['seckill_time'] = date('H:i', $seckill_now_time);
                            $seckill_list['start_time'] = $seckill_now_time;
                        } elseif ($seckill_now_date == $tomorrow_date) {//明天还未开始
                            $seckill_list['seckill_day'] = 'tomorrow';
                            $seckill_list['seckill_status'] = 'unstart';
                            $seckill_list['seckill_time'] = date('H:i', $seckill_now_time);
                            $seckill_list['start_time'] = $seckill_now_time;
                        } else {//后天及以后的天数
                            $seckill_list['seckill_day'] = date('m-d', $seckill_now_time);
                            $seckill_list['seckill_status'] = 'unstart';
                            $seckill_list['seckill_time'] = date('H:i', $seckill_now_time);
                            $seckill_list['start_time'] = $seckill_now_time;
                        }
                    }
                }
            }

            $bargain_list = $is_bargain;

            if (empty($presell_list)) {
                $presell_list = (object)[];
            }
            if (empty($seckill_list)) {
                $seckill_list = (object)[];
            }
            if (empty($bargain_list)) {
                $bargain_list = (object)[];
            } else {
                $goods_detail['sales'] += $bargain_list['bargain_sales'];
            }

            //拼团详情
            $group_list = [];
            if ($is_group && !$mic_goods) {
                $group_list['group_id'] = $is_group;
                $group_list['group_name'] = $group_server->getGroupName($is_group);
                $group_list['record_id'] = $record_id;
                $group_list['group_record_list'] = $group_server->goodsGroupRecordListForWap($value['goods_id']);
                $group_list['group_record_count'] = $group_server->goodsGroupRecordCount($value['goods_id']);
                $regimentCount = $group_server->goodsRegimentCount($value['goods_id']);
                $group_list['regiment_count'] = !empty($regimentCount) ? $regimentCount['now_num'] * $regimentCount['group_num'] : 0;
            }

            if (empty($group_list)) {
                $group_list = (object)[];
            }

            $is_distributor = (
                $this->is_distribution &&
                $this->uid &&
                (VslMemberModel::get(['uid' => $this->uid])['isdistributor'] == 2)
            ) ? true : false;

            if ($msg) {
                $code = 1;
                $msg = $msg;
            } else {
                $code = 0;
                $msg = '成功获取';
            }

            if ($promotion_info['discount_type'] == 2) {
                $discount = 1;
                $member_discount = 1;
            } else {
                $discount = $promotion_info['discount_num'] / 10;
            }
            if ($promotion_info['integer_type'] == 1) {
                $member_is_label = $promotion_info['integer_type'];
            }

            $spec_obj = [];
            $goods_detail['sku']['tree'] = [];
            if($value['spec_list']) {
            foreach ($value['spec_list'] as $i => $spec_info) {
                $temp_spec = [];
                foreach ($spec_info['value'] as $s => $spec_value) {
                    $temp_spec['k'] = $spec_info['spec_name'];
                    $temp_spec['k_id'] = $spec_info['spec_id'];
                    $temp_spec['v'][$s]['id'] = $spec_value['spec_value_id'];
                    $temp_spec['v'][$s]['name'] = $spec_value['spec_value_name'];
                    $temp_spec['k_s'] = 's' . $i;
                    $spec_obj[$spec_info['spec_id']] = $temp_spec['k_s'];
                    $goods_detail['sku']['tree'][$spec_info['spec_id']] = $temp_spec;
                    }
                }
            }
            //接口需要tree是数组，不是对象，去除tree以spec_id为key的值
            $goods_detail['sku']['tree'] = array_values($goods_detail['sku']['tree']);
            $goods_info['goods_list'][$key]['goods_detail']['sku']['tree'] = $goods_detail['sku']['tree'];

            //sku信息
            $total_presell_num = 0;
            $temp_sku = [];
            $goods_detail['sku']['list'] = [];
            foreach ($value['sku_list'] as $k => $sku) {
                $temp_sku['id'] = $sku['sku_id'];
                $temp_sku['sku_name'] = $sku['sku_name'];
                $temp_sku['price'] = $sku['price'];
                $temp_sku['min_buy'] = 1;
                $temp_sku['group_price'] = '';
                $temp_sku['group_limit_buy'] = '';
                if ($is_seckill && !$mic_goods) {
                    $temp_sku['price'] = $seckill_sku_price_arr[$sku['sku_id']];
                    $temp_sku['max_buy'] = $seckill_sku_limit_buy_arr[$sku['sku_id']] - $sku_buy_num[$sku['sku_id']] < 0 ? 0 : $seckill_sku_limit_buy_arr[$sku['sku_id']] - $sku_buy_num[$sku['sku_id']];
                    //对上一行的处理
                    $temp_sku['max_buy'] = $temp_sku['max_buy'] > $seckill_sku_stock_arr[$sku['sku_id']] ? $seckill_sku_stock_arr[$sku['sku_id']] : $temp_sku['max_buy'];
                    $sku['price'] = $temp_sku['price'];
                    $goods_detail['sales'] += array_sum($seckill_sales);
                } elseif ($is_group && !$mic_goods) {
                    $groupSku = $groupGoods->getInfo(['sku_id' => $sku['sku_id'], 'goods_id' => $value['goods_id'], 'group_id' => $is_group], 'group_price,group_limit_buy');
                    $buyed = $goods_server->getActivityOrderSkuForGroup($this->uid ?: 0, $sku['sku_id'], $this->website_id, $is_group);
                    $temp_sku['group_price'] = $groupSku['group_price'] ?: '';
                    $temp_sku['group_limit_buy'] = $temp_sku['max_buy'] = $groupSku['group_limit_buy'] - $buyed >= 0 ? $groupSku['group_limit_buy'] - $buyed : 0;
                } elseif ($is_presell == 1 && !$mic_goods) {
                    $presell = new PresellService();
                    //获取当前商品的预售活动
                    $presell_info = $presell->getPresellInfoByGoodsId($value['goods_id']);
                    $where['presell_id'] = $presell_info[0]['presell_id'];
                    $where['sku_id'] = $sku['sku_id'];
                    $where['goods_id'] = $value['goods_id'];
                    $presell_sku = $presell->getPresellSkuinfo($where);
                    //查出当前用户购买了多少个该商品
                    $presell_num = $goods_server->getActivityOrderSkuNum($this->uid ?: 0, $sku['sku_id'], $this->website_id, '4', $where['presell_id']);
                    $temp_sku['max_buy'] = (($presell_sku['max_buy'] - $presell_num) >= 0) ? $presell_sku['max_buy'] - $presell_num : 0;
                    $temp_sku['first_money'] = $presell_sku['first_money'];
                    $temp_sku['all_money'] = $presell_sku['all_money'];
                    $temp_sku['presell_num'] = $presell_sku['presell_num'];
                    $temp_sku['vr_num'] = $presell_sku['vr_num'];
                    //获取已购买的数量，减去得到剩余数量
                    $have_buy = $presell->get_presell_sku_num($presell_info[0]['presell_id'], $sku['sku_id']);
                    $temp_sku['over_num'] = $presell_sku['presell_num'] - $have_buy;
                    $total_presell_num += $temp_sku['over_num'];
                }

                $temp_sku['market_price'] = $sku['market_price'];
                if ($is_seckill && !$mic_goods) {
                    $temp_sku['stock_num'] = $seckill_sku_stock_arr[$sku['sku_id']];
                } elseif ($is_bargain && !$mic_goods) {//砍价的商品库存
                    $temp_sku['stock_num'] = $is_bargain['bargain_stock'];
                } elseif ($channel_id && !$mic_goods) {
                    $channel_sku_mdl = new VslChannelGoodsSkuModel();
                    $channel_cond['channel_id'] = $channel_id;
                    $channel_cond['sku_id'] = $sku['sku_id'];
                    $channel_cond['website_id'] = $this->website_id;
                    $channel_stock = $channel_sku_mdl->getInfo($channel_cond, 'stock')['stock'];
                    $temp_sku['stock_num'] = $channel_stock ?: 0;
                    $temp_sku['max_buy'] = $channel_stock ?: 0;
                } elseif ($is_presell) {
                    $temp_sku['stock_num'] = $temp_sku['over_num'] ?: 0;
                } else {
                    $temp_sku['stock_num'] = $sku['stock'];
                }
                $temp_sku['attr_value_items'] = $sku['attr_value_items'];

                $sku_temp_spec_array = explode(';', $sku['attr_value_items']);
                $temp_sku['s'] = [];
                foreach ($sku_temp_spec_array as $spec_id => $spec_combination) {
                    $explode_spec = explode(':', $spec_combination);
                    $spec_id = $explode_spec[0];
                    $spec_value_id = $explode_spec[1];

                    // ios wants string
                    if ($spec_value_id) {
                        $temp_sku['s'][] = (string)$spec_value_id;
                        $temp_sku[$spec_obj[$spec_id] ?: 's0'] = (int)$spec_value_id;
                    }
                }

                $goods_detail['min_price'] = reset($value['sku_list'])['sku_id'] == $sku['sku_id']
                    ? $sku['price'] : ($goods_detail['min_price'] <= $sku['price'] ? $goods_detail['min_price'] : $sku['price']);
                if ($promotion_info['discount_type'] == 2) {
                    $goods_detail['min_price'] = $promotion_info['discount_num'];
                }
                $goods_detail['min_market_price'] = reset($value['sku_list'])['sku_id'] == $sku['sku_id']
                    ? $sku['market_price'] : ($goods_detail['min_market_price'] <= $sku['market_price'] ? $goods_detail['min_market_price'] : $sku['market_price']);
                $goods_detail['max_price'] = reset($value['sku_list'])['sku_id'] == $sku['sku_id']
                    ? $sku['price'] : ($goods_detail['max_price'] >= $sku['price'] ? $goods_detail['max_price'] : $sku['price']);
                $goods_detail['max_market_price'] = reset($value['sku_list'])['sku_id'] == $sku['sku_id']
                    ? $sku['market_price'] : ($goods_detail['max_market_price'] >= $sku['market_price'] ? $goods_detail['max_market_price'] : $sku['market_price']);

                $goods_detail['sku']['list'][] = $temp_sku;
            }
            $goods_info['goods_list'][$key]['goods_detail']['sku']['list'] = $goods_detail['sku']['list'];

            $goods_info['goods_list'][$key] = [
                'goods_detail' => $goods_detail,
                'full_cut_list' => $full_cut_list,
                'seckill_list' => $seckill_list,
                'bargain_list' => $bargain_list,
                'member_discount' => $member_discount,
                'limit_discount' => $discount,
                'group_list' => $group_list,
                'presell_list' => $presell_list,
                'is_allow_buy' => $is_allow_buy,
                'is_allow_browse' => $is_allow_browse,
                'member_is_label' => $member_is_label,
                'discount_choice' => $discount_choice,
                'limit_list' => $value['limit_discount_info']
            ];
        }

        return json([
            'code' => $code,
            'message' => $msg,
            'data' => $goods_info
        ]);
    }

    /*
     * 添加购物车
     */
    public function addCart()
    {
        $sku_id = request()->post('sku_id');
        $store_id = request()->post('store_id');
        $goods_id = request()->post('goods_id');
        $num = request()->post('num');
        $seckill_id = request()->post('seckill_id', 0);
        $channel_id = request()->post('channel_id', 0);

        if (empty($sku_id) || empty($num)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }

        $uid = getUserId() ?: 0;

        $storeServer = new storeServer();
        $storeGoodsModel = new VslStoreGoodsModel();
        $sku_model = new VslStoreGoodsSkuModel();

        $condition = array(
            'website_id' => $this->website_id,
            'store_id' => $store_id,
            'goods_id' => $goods_id
        );

        $sku_info = $sku_model->getQuery(['sku_id' => $sku_id], '*', '');

        $goods_info = $storeGoodsModel->getQuery($condition, '*', '');
        if (!$goods_info) {
            return json(['code' => -1, 'message' => '商品不存在']);
        }

        $goods_name = $goods_info[0]['goods_name'];
        $shop_id = $this->instance_id;
        $sku_name = $sku_info[0]['sku_name'];
        $msg = '';

        if (!empty($seckill_id) && $this->is_seckill) {
            $seckill_server = new seckillServer();
            //根据秒杀活动和sku_id获取秒杀sku的价格
            $seckill_condition['s.seckill_id'] = $seckill_id;
            $seckill_condition['nsg.sku_id'] = $sku_id;
            //判断活动是否开始 false 未开始  true 开始
            $seckill_sku_list = $seckill_server->isSeckillGoods($seckill_condition);
            if ($seckill_sku_list) {
                $price = $seckill_sku_list->seckill_price;
            } else {
                $msg = '该商品秒杀活动已结束，价格将恢复为商品原价';
                $price = $sku_info[0]['price'];
            }
        } else {
            $price = $sku_info[0]['price'];
        }

        $picture_id = $goods_info[0]['picture'];
        $_SESSION['order_tag'] = ""; // 清空订单
        $result = $storeServer->addCart($uid, $shop_id, $goods_id, $goods_name, $sku_id, $sku_name, $price, $num, $picture_id, 0, $seckill_id, $store_id);
        if ($result > 0) {
            if (empty($msg)) {
                return json(['code' => 0, 'message' => '添加成功', 'data' => ['cart_id' => $result]]);
            } else {
                return json(['code' => 1, 'message' => $msg, 'data' => ['cart_id' => $result]]);
            }
        } else {
            return json(AjaxReturn(ADD_FAIL));
        }
    }

    /*
     * 购物车页面
     */
    public function cart()
    {
        $store_id = request()->post('store_id');
        $page_size = request()->post('page_size') ?: PAGESIZE;  //分页大小
        $page_index = request()->post('page_index') ?: '1'; //分页索引

        $promotion = new Promotion();
        $storeServer = new storeServer();
        $msg = '';
        $cartlist = $storeServer->cart($this->uid, $msg, $store_id, $page_size, $page_index);

        // 店铺，店铺中的商品
        $list = Array();
        //重组结构,按店铺组合
        if (!empty($cartlist['cart_list'])) {
            foreach ($cartlist['cart_list'] as $i => $v) {
                $cartlist['cart_list'][$i]['bargain_id'] = 0;
                if ($this->is_seckill) {
                    //查出商品是否在秒杀活动
                    $goods_id = $v['goods_id'];
                    $condition_is_seckill['nsg.goods_id'] = $goods_id;
                    $seckill_server = new Seckill();
                    $is_seckill = $seckill_server->isSkuStartSeckill($condition_is_seckill);
                }
//                $list['shop_info'][$v['shop_id']]['goods_list'][] = $cartlist['cart_list'][$i];

                //满减信息
//                $fullcutinfo = $promotion->getBestMansongInfo($cartlist[$i]["shop_id"]);
//                $list['shop_info'][$v['shop_id']]['mansong_info'] = !empty($fullcutinfo) ? $fullcutinfo[0] : (object)array();

                //折扣信息
                $discount_info = $promotion->get_best_discount($v['shop_id']);
                $cartlist['cart_list'][$i]['discount_info'] = !empty($discount_info) ? $discount_info[0] : (object)array();
                //限时折扣
                if ($this->is_discount && !empty($discount_info) && $cartlist['cart_list'][$i]['promotion_type'] == 5) {
                    $discount_server = new Discount();
                    $promotion_discount = $discount_server->getPromotionInfo($v['goods_id'], $v['shop_id'], $v['website_id']);
                    if ($promotion_discount) {
                        if ($promotion_discount['integer_type'] == 1) {
                            $cartlist['cart_list'][$i]['price'] = round($cartlist['cart_list'][$i]['price'] * $promotion_discount['discount_num'] / 10);
                        } else {
                            $cartlist['cart_list'][$i]['price'] = round($cartlist['cart_list'][$i]['price'] * $promotion_discount['discount_num'] / 10, 2);
                        }
                        if ($promotion_discount['discount_type'] == 2) {
                            $cartlist['cart_list'][$i]['price'] = $promotion_discount['discount_num'];
                        }
                    }
                }
                if ($is_seckill) {
                    $cartlist['cart_list'][$i]['mansong_info'] = (object)array();
                    $cartlist['cart_list'][$i]['discount_info'] = (object)array();
                }
                //购物车暂时不考虑主播商品
                $cartlist['cart_list'][$i]['anchor_id'] = 0;
            }
            $goods_service = new storeServer();
            $payment_info = $goods_service->paymentData($cartlist['cart_list']);

        }
        //重新遍历，如果活动不一致则删除活动
        if (!empty($cartlist['cart_list'])) {
            foreach ($cartlist['cart_list'] as $k => $v) {
                //满减送
                $cartlist['cart_list'][$k]['mansong_info'] = $payment_info[$k]['full_cut'];
                if ($v['discount_info'] && is_array($v['discount_info'])) {
                    if ($v['discount_info']['range'] == 2) {
                        $is_active_goods = $promotion->check_is_discount_product($v['goods_id'], $v['shop_id']);
                        if (empty($is_active_goods)) {
                            $cartlist['cart_list'][$k]['discount_info'] = (object)array();
                        }
                    }
                } else {
                    $cartlist['cart_list'][$k]['discount_info'] = (object)array();
                }
            }
        }

        //计算购物车总价
        foreach ($cartlist['cart_list'] as $k => $v) {
            $cartlist['total_money'] += $v['price'] * $v['num'];
        }
        $cartlist['total_money'] = round($cartlist['total_money'], 2);

        if (empty($cartlist['cart_list'])) {
            return json([
                'message' => '购物车暂无数据',
                'code' => 0,
                'data' => []
            ]);
        }

        if (!empty($msg)) {
            return json([
                'message' => $msg,
                'code' => -1,
                'data' => []
            ]);
        } else {
            return json([
                'message' => "获取成功",
                'code' => 0,
                'data' => $cartlist
            ]);
        }
    }

    /*
     * 修改购物车数量
     */
    public function editCartNum()
    {
        $storeServer = new storeServer();
        $cart_id = request()->post('cart_id', '');
        $num = request()->post('num', '');
        if (empty($cart_id)) {
            $data['code'] = -1;
            $data['data'] = '';
            $data['message'] = "请选择购物车ID";
        }
        //判断当前商品是否是秒杀商品，若是，则不能超过最大限制购买量
        $cart_list = $storeServer->getCartList($cart_id);
        $sku_id = $cart_list[0]['sku_id'];
        $seckill_id = (int)$cart_list[0]['seckill_id'];
        if (!$seckill_id) {
            if ($this->is_seckill) {
                $sec_service = new SeckillServer();
                $condition_seckill['nsg.sku_id'] = $sku_id;
                $seckill_info = $sec_service->isSkuStartSeckill($condition_seckill);
                if ($seckill_info) {
                    $seckill_id = $seckill_info['seckill_id'];
                }
            }
        }
        if ($seckill_id !== 0 && $this->is_seckill) {
            $sec_service = new SeckillServer();
            //查询该商品的虚拟购买量，条件为 未过期
            $condition_seckill['s.website_id'] = $this->website_id;
            $condition_seckill['s.shop_id'] = $this->instance_id;
            $condition_seckill['s.seckill_id'] = $seckill_id;
            $condition_seckill['nsg.sku_id'] = $sku_id;
            $is_seckill = $sec_service->isSeckillGoods($condition_seckill);
            if ($is_seckill) {
                $sku_list = $sec_service->getSeckillSkuInfo(['seckill_id' => $seckill_id, 'sku_id' => $sku_id]);
                $seck_limit_buy = $sku_list->seckill_limit_buy;
                if ($num > $seck_limit_buy) {
                    return json(['code' => -1, 'message' => '秒杀活动商品最大购买量不能超过' . $seck_limit_buy . '件']);
                }
            }
        }

        $retval = $storeServer->cartAdjustNum($cart_id, $num);

        if ($retval) {
            $data['code'] = 0;
            $data['data'] = '';
            $data['message'] = "修改成功";
        } else {
            $data['code'] = 0;
            $data['data'] = '';
            $data['message'] = "修改失败";
        }

        return json($data);
    }

    /**
     * 删除购物车商品
     * */

    public function deleteCartGoods()
    {
        $cart_id = request()->post('cart_id');
        $cart_id_array = explode(',', $cart_id);
        $storeServer = new storeServer();
        $result = $storeServer->cartDelete($cart_id_array);
        if ($result) {
            $data['message'] = "操作成功";
            $data['data'] = "";
            $data['code'] = 0;
            return json($data);
        } else {
            $data['message'] = "系统繁忙";
            $data['data'] = "";
            $data['code'] = '-1';
            return json($data);
        }
    }

    /*
     * 确认订单
     */
    public function orderInfo()
    {
        $order_tag = request()->post('order_tag');
        $cart_id_list = request()->post('cart_id_list/a');
        $sku_list = request()->post('sku_list/a');
        $address_id = request()->post('address_id', 0);
        $record_id = request()->post('record_id');
        $group_id = request()->post('group_id');
        $presell_id = request()->post('presell_id', '');
        $shipping_type = request()->post('shipping_type', 1);
        $is_deduction = request()->post('is_deduction', 0);
        $lng = request()->post("lng", '');
        $lat = request()->post("lat", '');
        $return_data = [];
        $place = [
            'lng' => $lng,
            'lat' => $lat
        ];

        if ($order_tag == 'cart') {
            $cart_model = new VslStoreCartModel();
            $goods_model = new VslGoodsModel();
            $cart_info = $cart_model::all(['cart_id' => ['IN', $cart_id_list]]);
            $sku_lists = $sku_list;
            $sku_list = [];
            foreach ($cart_info as $v) {
                $temp_sku = [];
                //查询出当前商品是否在活动中
                $goods_id = $v['goods_id'];
                $goods_info = $goods_model->getInfo(['goods_id' => $goods_id], 'goods_name, promotion_type');
                if ($goods_info['promotion_type'] == 3) {
                    return json(['code' => -1, 'message' => $goods_info['goods_name'] . ' 为参加预售活动商品，预售活动商品只能单独结算。']);
                }
                $temp_sku['sku_id'] = $v['sku_id'];
                $temp_sku['num'] = $v['num'];
                $temp_sku['seckill_id'] = $v['seckill_id'];
                $temp_sku['price'] = $v['price'];//秒杀商品
                $temp_sku['shop_id'] = $v['shop_id'];
                $temp_sku['cart_id'] = $v['cart_id'];
                if ($sku_lists) {
                    $temp_sku['coupon_id'] = $coupon_id = 0;
                    foreach ($sku_lists as $k2 => $v2) {
                        if ($v2['coupon_id'] > 0 && $temp_sku['sku_id'] == $v2['sku_id']) {
                            if ($coupon_id == 0) $coupon_id = $v2['coupon_id'];
                        }
                    }
                    $temp_sku['coupon_id'] = $coupon_id;
                }
                $sku_list[] = $temp_sku;
            }
        }
        if (empty($sku_list)) {

            return json(['code' => -1, 'message' => '不存在商品信息']);
        }
        Session::set('order_tag', $order_tag);

        $storeServer = new storeServer();
        $msg = '';
        $payment_info = $storeServer->paymentData($sku_list, $msg, $record_id, $group_id, $presell_id);
        if ($payment_info['code'] == -2) {
            return json($payment_info);
        }
        $return_data['record_id'] = $record_id;
        $return_data['group_id'] = $group_id;

        // 收获地址
        if (empty($address_id)) {
            $address_condition['uid'] = $this->uid;
            $address_condition['is_default'] = 1;
        } else {
            $address_condition['id'] = $address_id;
        }
        $member_service = new MemberService();
        $address = $member_service->getMemberExpressAddress($address_condition, ['area_province', 'area_city', 'area_district']);
        if (!empty($address)) {
            $return_data['address']['address_id'] = $address['id'];
            $return_data['address']['consigner'] = $address['consigner'];
            $return_data['address']['mobile'] = $address['mobile'];
            $return_data['address']['province_name'] = $address['area_province']['province_name'];
            $return_data['address']['city_name'] = $address['area_city']['city_name'];
            $return_data['address']['district_name'] = $address['area_district']['district_name'];
            $return_data['address']['address_detail'] = $address['address'];
            $return_data['address']['zip_code'] = $address['zip_code'];
            $return_data['address']['alias'] = $address['alias'];
        } else {
            $return_data['address'] = (object)[];
        }
        //end 收获地址
        $goods_model = new VslGoodsModel();
        $return_data['total_shipping'] = 0;
        $order_business = new OrderBusiness();
        $deduction_data = [];
        $return_point_data = [];
        $goodsExpress = new GoodsExpress();
        $storeServer = $this->is_store ? new storeServer() : '';
        $has_store = 0;//订单商品所属店铺中是否有门店
        foreach ($payment_info as $shop_id => $shop_info) {
            $storeSet = 0;
            if ($this->is_store) {
                $storeSet = $storeServer->getStoreSet($shop_id)['is_use'];
            }
            $payment_info[$shop_id]['has_store'] = $storeSet ?: 0;
            if ($payment_info[$shop_id]['has_store']) {
                $has_store = 1;
            }
            $return_data['promotion_amount'] += $shop_info['member_promotion'] + $shop_info['discount_promotion'] + $shop_info['coupon_promotion'];
            if (isset($shop_info['full_cut']) && is_array($shop_info['full_cut'])) {
                $return_data['promotion_amount'] += ($shop_info['full_cut']['discount'] < $shop_info['total_amount']) ? $shop_info['full_cut']['discount'] : $shop_info['total_amount'];
            }
            $temp_goods = [];
            foreach ($shop_info['goods_list'] as $sku_id => $sku_info) {
                $return_data['goods_amount'] += $sku_info['price'] * $sku_info['num'];
                if (!empty($shop_info['full_cut']) &&
                    !empty((array)$shop_info['full_cut']) &&
                    $shop_info['full_cut']['free_shipping'] == 1 &&
                    (in_array($sku_info['goods_id'], $shop_info['full_cut']['goods_limit']) || $shop_info['full_cut']['range_type'] == 1)) {
                    // 有包邮的设定 && (商品在goods_limit里面 || 活动商品是全部商品)
                    $payment_info[$shop_id]['goods_list'][$sku_id]['shipping_fee'] = 0;
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
                    $payment_info[$shop_id]['goods_list'][$sku_id]['shipping_fee'] = $goodsExpress->getGoodsExpressTemplate($tempgoods, $address['district'])['totalFee'];
                }

                // 用户可购该商品最大数量
//                if ($this->uid) {
//                    $max_buy = $goods_service->getGoodsMaxBuyNums($sku_info['goods_id'], $sku_info['sku_id']);
//                    if ($max_buy <0) {
//                        $shop_info['goods_list'][$sku_id]['max_buy'] = -1;
//                    } else {
//                        $shop_info['goods_list'][$sku_id]['max_buy'] = $max_buy;
//                    }
//                }
            }

            //处理此笔订单下的商品，如果门店没有此商品的库存，则不能选择此门店
            $storeModel = new VslStoreModel();
            $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
            $goodsModel = new VslGoodsModel();
            $address_service = new Address();
            $storeServer = new storeServer();
            $new_store_list = '';
            foreach ($shop_info['goods_list'] as $key => $val) {
                $store_list = $goodsModel->Query(['goods_id' => $val['goods_id']], 'store_list')[0];
                if (empty($store_list)) {
                    $has_store=0;
                    break;
                }
                $store_list = explode(',', $store_list);
                foreach ($store_list as $k => $v) {
                    $condition = [
                        'goods_id' => $val['goods_id'],
                        'sku_id' => $val['sku_id'],
                        'store_id' => $v,
                        'website_id' => $this->website_id
                    ];
                    $stock = $storeGoodsSkuModel->getInfo($condition, 'stock');
                    if ($stock['stock'] <= 0) {
                        unset($v);
                    }
                    if ($v) {
                        $new_store_list .= $v . ',';
                    }
                }
            }
            $new_store_list = trim($new_store_list, ',');
            $new_store_list = explode(',', $new_store_list);
            $new_store_list = array_unique($new_store_list);
            foreach ($new_store_list as $k => $v) {
                $store_info = $storeModel->getInfo(['store_id' => $v], '*');
                $newList['distance'] = $storeServer->sphere_distance(['lat' => $store_info['lat'], 'lng' => $store_info['lng']], $place);
                $newList['store_id'] = $store_info['store_id'];
                $newList['shop_id'] = $store_info['shop_id'];
                $newList['website_id'] = $store_info['website_id'];
                $newList['store_name'] = $store_info['store_name'];
                $newList['store_tel'] = $store_info['store_tel'];
                $newList['address'] = $store_info['address'];
                $newList['province_name'] = $address_service->getProvinceName($store_info['province_id']);
                $newList['city_name'] = $address_service->getCityName($store_info['city_id']);
                $newList['dictrict_name'] = $address_service->getDistrictName($store_info['district_id']);
                $data[] = $newList;
            }
            $payment_info[$shop_id]['store_list'] = $data;

            // 计算邮费
            $shipping_fee = 0;
            if ($temp_goods && !empty($address['district'])) {
                $shipping_fee = $goodsExpress->getGoodsExpressTemplate($temp_goods, $address['district'])['totalFee'];
            }
            $is_presell_info = objToArr($shop_info['presell_info']);
            if ($is_presell_info && $shipping_type != 2) {
                $payment_info[$shop_id]['presell_info']['shipping_fee'] = $shipping_fee;
            }
            if ($is_presell_info || $shipping_type == 2) {//等于2为自提
                $shipping_fee = 0;
            }
            $payment_info[$shop_id]['shipping_fee'] = $shipping_fee;
            $payment_info[$shop_id]['total_amount'] += $shipping_fee;
            $return_data['total_shipping'] += $shipping_fee;
            //抵扣积分
            if ($presell_id > 0 && $is_presell_info) {
                $payment_info[$shop_id]['goods_list'][0]['price'] = $shop_info['presell_info']['allmoney'];
            }
            $point_deductio = $order_business->pointDeductionOrder($payment_info[$shop_id]['goods_list'], 1, $shipping_type);
            $deduction_data[] = $point_deductio;
            //返积分
            $point_return = $order_business->pointReturnOrder($point_deductio['sku_info'], $shipping_type);
            $return_point_data[] = $point_return;
            $payment_info[$shop_id]['goods_list'] = $point_return['sku_info'];
            $return_data['amount'] += $payment_info[$shop_id]['total_amount'];
        }
        $return_data['has_store'] = $has_store;
        //返积分
        $return_data['total_give_point'] = 0;
        if ($return_point_data) {
            foreach ($return_point_data as $k => $v) {
                if ($v['total_return_point'] > 0) {
                    $return_data['total_give_point'] += $v['total_return_point'];
                }
            }
        }
        //积分抵扣
        $return_data['deduction_point'] = [];
        $member_info = $member_service->getMemberAccount($this->uid);
        $return_data['deduction_point']['point'] = $member_info['point'];
        $return_data['deduction_point']['total_deduction_money'] = 0;
        $return_data['deduction_point']['total_deduction_point'] = 0;

        if ($deduction_data) {
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
        $config = new Config();
        $point_deduction = $config->getShopConfig(0, $this->website_id);
        $return_data['is_point_deduction'] = $point_deduction['is_point_deduction'];
        $return_data['is_point'] = $point_deduction['is_point'];
        $return_data['customform'] = $member_service->getOrderCustomForm();
        $return_data['shop'] = array_values($payment_info);
//        return $return_data;
        return json(['code' => 1, 'message' => $msg, 'data' => $return_data]);
    }
    /*
     * 小票机设置
     */
    public function saveSetting()
    {
        $config = new Config();
        $post_data = request()->post();
        if(!$post_data['user'] || !$post_data['ukey']){
            return AjaxReturn(-1006);
        }
        $params[0] = array(
            'instance_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'key' => 'PRINTER_INFO',
            'value' => $post_data,
            'desc' => '小票机设置',
            'is_use' => 1
        );
        $res = $config->setConfig($params);
        if(!$res){
            return AjaxReturn(0);
        }
        $this->addUserLog('小票机设置',$res);
        return AjaxReturn($res);
    }
}
