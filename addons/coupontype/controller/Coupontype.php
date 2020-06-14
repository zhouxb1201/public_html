<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\coupontype\controller;

use addons\coupontype\Coupontype as baseCoupon;
use addons\coupontype\model\VslCouponModel;
use addons\coupontype\server\Coupon as CouponServer;
use addons\miniprogram\model\WeixinAuthModel;
use addons\shop\model\VslShopModel;
use data\model\WebSiteModel;
use data\service\AddonsConfig;

class Coupontype extends baseCoupon
{
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 我的优惠券
     */
    public function couponList()
    {
        // 获取该用户的所有已领取未使用的优惠券列表

        $couponServer = new CouponServer();
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $type = request()->post('type', 1);
        $list = $couponServer->getUserCouponList($type, '', $page_index, $page_size);
        return $list;
    }

    public function couponTypeList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $end_receive_time = request()->post('end_receive_time');

        $couponServer = new CouponServer();
        $condition = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id
        );
        if ($end_receive_time) {
            $condition['end_receive_time'] = ['GT', time()];
        }
        if (input('post.search_text')){
            $condition['coupon_name'] = ['like', '%' . input('post.search_text') . '%'];
        }
        if (input('post.not_expired')) {
            $condition['end_time'] = ['GT', time()];
        }
        if (input('post.excepted_coupon_type_id/a')) {
            $condition['coupon_type_id'] = ['NOT IN', input('post.excepted_coupon_type_id/a')];
        }
        $list = $couponServer->getCouponTypeList($page_index, $page_size, $condition, 'create_time desc,coupon_type_id desc', '*');
        //判断pc端、小程序、pc、app是否开启
        $addons_conf = new AddonsConfig();
        $pc_conf = $addons_conf->getAddonsConfig('pcport', $this->website_id);
        $is_minipro = getAddons('miniprogram', $this->website_id);
        if($is_minipro){
            $weixin_auth = new WeixinAuthModel();
            $new_auth_state = $weixin_auth->getInfo(['website_id' => $this->website_id], 'new_auth_state')['new_auth_state'];
            if(isset($new_auth_state) && $new_auth_state == 0){
                $is_minipro = 1;
            }else{
                $is_minipro = 0;
            }
        }
        $website_mdl = new WebSiteModel();
        //查看移动端的状态
        $wap_status = $website_mdl->getInfo(['website_id' => $this->website_id], 'wap_status')['wap_status'];
        // app商城状态
        $is_app = getAddons('appshop', $this->website_id);
        if ($is_app) {
            $app_conf = $addons_conf->getAddonsConfig('appshop', $this->website_id);
        }

        $addon_status['wap_status'] = $wap_status;
        $is_coupontype = getAddons('coupontype', $this->website_id);
        $addon_status['is_pc_use'] = $pc_conf['is_use'];
        $addon_status['is_minipro'] = $is_minipro;
        $addon_status['is_coupontype'] = $is_coupontype;
        $addon_status['is_appshop'] = $app_conf['is_use'];
        $list['addon_status'] = $addon_status;
        return $list;
    }

    public function getCouponTypeInfo()
    {
        $coupon_type_id = $_POST['coupon_type_id'];
        $coupon = new CouponServer();
        $coupon_type = $coupon->getCouponTypeDetail($coupon_type_id);

        return $coupon_type;
    }

    public function addCouponType()
    {
        $input = $_POST;
        $input['start_receive_time'] = strtotime($_POST['start_receive_time']);
        $input['end_receive_time'] = strtotime($_POST['end_receive_time']) + (86400 - 1);
        $input['start_time'] = strtotime($_POST["start_time"]);
        $input['end_time'] = strtotime($_POST["end_time"]) + (86400 - 1);
        $input['create_time'] = time();
        $input['shop_id'] = $this->instance_id;
        $input['website_id'] = $this->website_id;
        $coupon = new CouponServer();

        $ret_val = $coupon->addCouponType($input);
        if ($ret_val) {
            $this->addUserLog('添加优惠券', $ret_val);
        }

        return AjaxReturn($ret_val);
    }

    public function updateCouponType()
    {
        $input = $_POST;
        $input['start_receive_time'] = strtotime($input['start_receive_time']);
        $input['end_receive_time'] = strtotime($input['end_receive_time']) + (86400 - 1);
        $input['start_time'] = strtotime($input["start_time"]);
        $input['end_time'] = strtotime($input["end_time"]) + (86400 - 1);
        $input['update_time'] = time();
        $coupon = new CouponServer();

        $ret_val = $coupon->updateCouponType($input);
        if ($ret_val) {
            $this->addUserLog('修改优惠券', $ret_val);
        }
        return AjaxReturn($ret_val);
    }

    public function deleteCouponType()
    {
        $coupon_type_id = request()->post('coupon_type_id', '');
        if (empty($coupon_type_id)) {
            $this->error("没有获取到优惠券信息");
        }
        $coupon = new CouponServer();
        $res = $coupon->deleteCouponType($coupon_type_id);
        if ($res) {
            $this->addUserLog('删除优惠券', $coupon_type_id);
        }
        return AjaxReturn($res);
    }

    public function historyCoupon()
    {
        $promotion = new CouponServer();
        $search_text = $_POST['search_text'];
        $page_index = $_POST['page_index'] ?: 1;
        $page_size = $_POST['page_size'] ?: PAGESIZE;
        $fields = 'nc.coupon_id,nc.coupon_code,nc.money,nc.discount,nc.use_time,su.user_tel,su.nick_name,no.order_no,no.shop_id,ns.shop_name';
        // 使用的历史记录,state = 2
        $where['nc.state'] = 2;
        $where['nc.coupon_type_id'] = $_GET['coupon_type_id'];
        $where['nc.website_id'] = $this->website_id;
        $where['nc.shop_id'] = $this->instance_id;
        $condition = [];
        if ($search_text) {
            $condition['nc.coupon_code'] = ['LIKE', '%' . $search_text . '%', 'or'];
            $condition['ns.shop_name'] = ['LIKE', '%' . $search_text . '%', 'or'];
            $condition['su.nick_name'] = ['LIKE', '%' . $search_text . '%', 'or'];
            $condition['nct.coupon_name'] = ['LIKE', '%' . $search_text . '%', 'or'];
        }

        $list = $promotion->getCouponHistory($page_index, $page_size, $condition, $where, $fields);
        return $list;
    }

    public function couponSetting()
    {
        $couponServer = new CouponServer();
        $is_coupon = $_POST['is_coupon'] ?: 0;

        $result = $couponServer->saveCouponConfig($is_coupon);
        if ($result) {
            $this->addUserLog('添加优惠券设置', $result);
        }
        setAddons('coupontype', $this->website_id, $this->instance_id);
        setAddons('coupontype', $this->website_id, $this->instance_id, true);
        return AjaxReturn($result);

    }

    public function getGoodsCouponType()
    {
        $coupon = new CouponServer();
        $goods_id_array = request()->post('goods_id_array/a');
        $coupon_type_array = $coupon->getGoodsCoupon($goods_id_array, $this->uid);

        return $coupon_type_array;
    }

    /**
     * wap 商品详情、购物车优惠券列表接口
     */
    public function goodsCouponList()
    {
        if (!getAddons('coupontype', $this->website_id)) {
            return json(['code' => 1, 'message' => '应用已关闭', 'data' => []]);
        }
        $coupon = new CouponServer();
        $goods_id_array = request()->post('goods_id_array/a');
        if (empty($goods_id_array)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $coupon_type_array = $coupon->getGoodsCoupon($goods_id_array, $this->uid);
        $coupon_type_list = [];
        foreach ($coupon_type_array as $k => $v) {
            $temp = [];
            $temp['coupon_type_id'] = $v['coupon_type_id'];
            $temp['coupon_name'] = $v['coupon_name'];
            $temp['coupon_genre'] = $v['coupon_genre'];
            $temp['shop_range_type'] = $v['shop_range_type'];
            $temp['at_least'] = $v['at_least'];
            $temp['money'] = $v['money'];
            $temp['discount'] = $v['discount'];
            $temp['start_time'] = $v['start_time'];
            $temp['end_time'] = $v['end_time'];
            $temp['shop_id'] = $v['shop_id'];

            $coupon_type_list[] = $temp;
        }

        return json(['code' => 1, 'message' => '获取成功', 'data' => $coupon_type_list]);
    }

    public function userArchiveCoupon()
    {
        $coupon = new CouponServer();
        $coupon_type_id = input('coupon_type_id');
        $get_type = input('get_type');
        if (empty($this->uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        if ($coupon->isCouponTypeReceivable($coupon_type_id, $this->uid)) {
            $result = $coupon->userAchieveCoupon($this->uid, $coupon_type_id, $get_type);
            return json(AjaxReturn($result));
        } else {
            return json(AjaxReturn(NO_COUPON));
        }
    }

    /**
     * wap订单结算优惠券列表
     */
    public function confirmOrderCouponList()
    {
        $post = request()->post('post_data/a', '');
        if (empty($post)) {
            return json(['code' => 1, 'message' => '空数据', 'data' => []]);
        }
        // 重新处理post的数据结构，将shop_id 和 sku_id作为数组的key
        $new_data = [];
        foreach ($post as $v) {
            $new_data[$v['shop_id']][$v['sku_id']] = $v;
        }
        $couponServer = new CouponServer();
        $couponList = $couponServer->getMemberCouponListNew($new_data); // 获取优惠券
        $return_data = [];
        foreach ($couponList as $shop_id => $coupon) {
            foreach ($coupon['coupon_info'] as $key => $item) {
                $temp_coupon = [];
                $temp_coupon['coupon_id'] = $item['coupon_id'];
                $temp_coupon['shop_id'] = $item['coupon_type']['shop_id'];
                $temp_coupon['coupon_type_id'] = $item['coupon_type_id'];
                $temp_coupon['discount'] = $item['discount'];
                $temp_coupon['money'] = $item['money'];
                $temp_coupon['goods_limit'] = $item['goods_limit'];
                $temp_coupon['start_time'] = $item['coupon_type']['start_time'];
                $temp_coupon['end_time'] = $item['coupon_type']['end_time'];
                $temp_coupon['coupon_name'] = $item['coupon_type']['coupon_name'];
                $temp_coupon['coupon_genre'] = $item['coupon_type']['coupon_genre'];
                $temp_coupon['at_least'] = $item['coupon_type']['at_least'];
                //$temp_coupon['shop_range_type'] = $item['coupon_type']['shop_range_type'];
                $temp_coupon['range_type'] = $item['coupon_type']['range_type'];
                $temp_coupon['use_shop_id'] = $shop_id;

                $return_data[] = $temp_coupon;
            }
        }

        return json(['code' => 1, 'message' => '获取成功', 'data' => $return_data]);
    }

    /**
     * 领券中心
     */
    public function couponCentre()
    {
        $page_index = input('post.page_index');
        if (empty($page_index)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $page_size = input('post.page_size') ?: PAGESIZE;
        $search_text = request()->post('search_text', '');
        $couponServer = new CouponServer();
        $coupon_model = new VslCouponModel();
        $website_model = new WebSiteModel();
        $is_shop = getAddons('shop', $this->website_id);
        $shop_model = $is_shop ? new VslShopModel() : '';
        $condition = array(
            'website_id' => $this->website_id,
            'is_fetch' => 1,
            'start_receive_time' => ['ELT', time()],
            'end_receive_time' => ['EGT', time()],
            'coupon_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        $list = $couponServer->getCouponTypeList($page_index, $page_size, $condition, 'start_time desc', '*');
        $return_data['list'] = [];
        $return_data['total_count'] = $list['total_count'];
        $return_data['page_count'] = $list['page_count'];
        $temp_shop_info = [];// 临时保存店铺的信息
        foreach ($list['data'] as $v) {
            $temp = [];
            $temp['coupon_type_id'] = $v['coupon_type_id'];
            $temp['coupon_name'] = $v['coupon_name'];
            if ($v['shop_range_type'] == 1) {
                $temp['shop_range'] = '本店可用';
            } else {
                $temp['shop_range'] = '全平台可用';
            }
            if ($v['range_type'] == 1) {
                $temp['goods_range'] = '全部商品可用';
            } else {
                $temp['goods_range'] = '部分商品可用';
            }
            $temp['coupon_genre'] = $v['coupon_genre'];
            $temp['at_least'] = $v['at_least'];
            $temp['money'] = $v['money'];
            $temp['count'] = $v['count'];
            $temp['max_fetch'] = $v['max_fetch'];
            $temp['discount'] = $v['discount'];
            $temp['start_time'] = $v['start_time'];
            $temp['end_time'] = $v['end_time'];
            $temp['receive_times'] = $coupon_model->getCount(['coupon_type_id' => $v['coupon_type_id']]);
            if (!isset($temp_shop_info[$v['shop_id']])) {
                if ($v['shop_id'] == 0) {
                    // website logo
                    $temp_shop_logo = getApiSrc($website_model::get(['website_id' => $v['website_id']])['logo']);
                } else {
                    // shop logo
                    $temp_shop_logo = getApiSrc($shop_model::get(['vsl_shop_model.shop_id' => $v['shop_id']], ['logo'])->logo->pic_cover);
                }
                $temp_shop_info[$v['shop_id']]['logo'] = $temp_shop_logo ?: '';
            }
            $temp['shop_logo'] = $temp_shop_info[$v['shop_id']]['logo'];

            $return_data['list'][] = $temp;
        }
        return json(['code' => 1, 'data' => $return_data]);
    }

    
    /**
     * wap优惠券详情
     */
    public function couponDetail(){
        $coupon_type_id = (int)input('post.coupon_type_id');
        $coupon = new CouponServer();
        $coupon_info = $coupon->getCouponTypeDetail($coupon_type_id);
        $data = $info = [];
        if($coupon_info){
            $info['is_coupon'] = $coupon->isCouponTypeReceivable($coupon_type_id, $this->uid);
            $info['coupon_type_id'] = $coupon_info['coupon_type_id'];
            $info['coupon_name'] = $coupon_info['coupon_name'];
            $info['shop_id'] = $coupon_info['shop_id'];
            $website_model = new WebSiteModel();
            $temp_shop_logo = getApiSrc($website_model::get(['website_id' => $this->website_id])['logo']);
            $info['shop_name'] = '自营店';
            $info['shop_logo'] = $temp_shop_logo;
            if(getAddons('shop', $this->website_id)){
                $shop_model = new VslShopModel();
                $shop_info = $shop_model->getInfo(['shop_id' => $coupon_info['shop_id'],'website_id'=>$this->website_id], 'shop_name,shop_logo');
                if($coupon_info['shop_id'] > 0){
                    $info['shop_logo'] = $shop_info['shop_logo'] ? getApiSrc($shop_info['shop_logo']) : '';
                }
                $info['shop_name'] = ($coupon_info['shop_id']==0)?'自营店':$shop_info['shop_name'];
            }
            
            $info['coupon_genre'] = $coupon_info['coupon_genre'];
            $info['at_least'] = $coupon_info['at_least'];
            $info['discount'] = $coupon_info['discount'];
            $info['money'] = $coupon_info['money'];
            $info['start_time'] = $coupon_info['start_time'];
            $info['end_time'] = $coupon_info['end_time'];
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $info;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    
    /**
     * wap优惠券适用的商品列表
     */
    public function couponGoodsList(){
        $coupon_type_id = (int)input('coupon_type_id');
        $page_index = input('post.page_index');
        $page_size = input('post.page_size',PAGESIZE);
        $order = (input('post.order'))?input('post.order'):'create_time';
        $sort = (input('post.sort'))?input('post.sort'):'DESC';
        $min_price = input('post.max_price');
        $max_price = input('post.max_price');
        $is_recommend = request()->post('is_recommend',0);
        $is_new = request()->post('is_new',0);
        $is_hot = request()->post('is_hot',0);
        $is_promotion = request()->post('is_promotion',0);
        $is_shipping_free = request()->post('is_shipping_free',0);
        if (empty($page_index)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $group = 'ng.goods_id';
        $order_sort = 'ng.' . $order . ' ' . $sort;
        $condition['ng.state'] = 1;
        $condition['ng.website_id'] = $this->website_id;
        if ($min_price != '') {
            $condition['ngs.price'][] = ['>=', $min_price];
        }
        if ($max_price != '') {
            $condition['ngs.price'][] = ['<=', $max_price];
        }
        if ($free_shipping_fee) {
            $condition['ng.shipping_fee'] = 0;
        }
        if ($new_goods) {
            $condition['ng.create_time'] = ['>=', time() - 10 * 24 * 60 * 60];
        }
        if($is_recommend==1){
            $condition['ng.is_recommend'] = 1;
        }
        if($is_new==1){
            $condition['ng.is_new'] = 1;
        }
        if($is_hot==1){
            $condition['ng.is_hot'] = 1;
        }
        if($is_promotion==1){
            $condition['ng.is_promotion'] = 1;
        }
        if($is_shipping_free==1){
            $condition['ng.is_shipping_free'] = 1;
        }
        $condition['vs.shop_state'] = 1;
        $condition['coupon_type_id'] = $coupon_type_id;
        $coupon = new CouponServer();
        $list = $coupon->couponGoodsList($page_index, $page_size, $condition, 'ng.goods_id, ng.goods_name, ng.sales,ng.real_sales, sap.pic_cover, ng.price as goods_price,ngs.market_price as market_price', $order_sort, $group);
        $goods_list = [];
        if($list['data']){
            foreach ($list['data'] as $k => $v) {
                $goods_list[$k]['goods_id'] = $v['goods_id'];
                $goods_list[$k]['goods_name'] = $v['goods_name'];
                $goods_list[$k]['price'] = $v['goods_price'];
                $goods_list[$k]['market_price'] = $v['market_price'];
                $goods_list[$k]['sales'] = $v['sales'] + $v['real_sales'];
                $goods_list[$k]['pic_cover'] = $v['pic_cover'] ? getApiSrc($v['pic_cover']) : '';
            }
        }
        $data['code'] = 1;
        $data['message'] = "获取成功";
        $data['data']['goods_list'] = $goods_list;
        $data['data']['page_count'] = $list['page_count'];
        $data['data']['total_count'] = $list['total_count'];
        return json($data);
    }
}