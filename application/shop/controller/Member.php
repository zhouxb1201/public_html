<?php

namespace app\shop\controller;

use addons\blockchain\model\VslBlockChainRecordsModel;
use addons\blockchain\service\Block;
use addons\coupontype\model\VslCouponModel;
use addons\discount\server\Discount;
use addons\fullcut\service\Fullcut;
use addons\seckill\server\Seckill;
use addons\shop\model\VslShopModel;
use addons\store\model\VslStoreModel;
use addons\store\server\Store as storeServer;
use data\model\AlbumPictureModel;
use data\model\VslBankModel;
use data\model\VslCartModel;
use data\model\AddonsConfigModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslMemberBankAccountModel;
use data\model\VslMemberExpressAddressModel;
use data\model\VslMemberLevelModel;
use data\model\VslMemberModel;
use data\model\VslOrderExpressCompanyModel;
use data\model\VslOrderGoodsExpressModel;
use data\model\VslOrderModel;
use data\model\VslOrderPaymentModel;
use data\model\VslPresellModel;
use data\model\VslStoreGoodsModel;
use data\model\VslStoreGoodsSkuModel as VslStoreGoodsSkuModel;
use data\model\WebSiteModel;
use data\service\Address;
use data\service\Config;
use data\service\Goods as GoodsService;
use data\service\Member\MemberAccount as MemberAccount;
use data\service\Member as MemberService;
use addons\coupontype\server\Coupon as CouponServer;
use data\service\Order\Order;
use data\service\Order\OrderGoods;
use data\service\Order as OrderService;
use data\service\promotion\GoodsPreference;
use data\service\UnifyPay;
use data\service\Upload\AliOss;
use think\Session;
use data\model\UserModel;
use data\service\promotion\GoodsExpress;
use data\service\WebSite as WebSite;
use addons\store\server\Store;
use data\service\Order\OrderStatus;
/**
 * 会员控制器
 */
class Member extends BaseController
{

    public $notice;
    public $alipay;
    public $wxpay;
    public $tlpay;
    public $realm_ip;
    public function __construct()
    {
        parent::__construct();
        // 如果没有登录的话让其先登录
        if ((request()->action()) != 'logout') {
            $this->checkLogin();
        }
        // 查询登陆用户信息
        if (!request()->isAjax()) {
            $cart_list = $this->getShoppingCart(); // 购物车列表
            // 选中id
            $curs = request()->get('curs', '1');
            $this->assign('curs', $curs);
            $this->assign("cart_list", $cart_list);
        }
         //是否开启支付宝和微信和余额支付
        $web_config = new Config();
//        $balance_config = $web_config->getBalanceWithdrawConfig(0);
//        if($balance_config['value']['withdraw_message']){
//            $withdraw_message = explode(',',$balance_config['value']['withdraw_message']);
//            if(in_array('3',$withdraw_message)){
//                $is_alipay_tw=1;
//                $this->assign("alipay_tw", $is_alipay_tw);
//            }
//            if(in_array('2',$withdraw_message)){
//                $wx_info = $web_config->getWpayConfig($this->website_id);
//                $wx_tw = $wx_info['value']['wx_tw'];
//                if($wx_tw==1 && $wx_info['is_use']){
//                    $is_wpy_tw = 1;
//                    $this->assign("wxpay_tw", $is_wpy_tw);
//                }
//            }
//            if(in_array('1',$withdraw_message)){
//                $tl_info = $web_config->getTlConfig(0,$this->website_id);
//                $tl_tw = $tl_info['value']['tl_tw'];
//                if($tl_tw==1 && $tl_info['is_use']){
//                    $is_tlpy_tw = 1;
//                    $this->assign("tlpay_tw", $is_tlpy_tw);
//                }
//            }
//        }
        $alipay = $web_config->getConfig(0,'ALIPAY');
        $this->alipay = (isset($alipay["is_use"])) ? $alipay["is_use"] : '0';
        $this->assign("alipay", $this->alipay);
        $wxpay = $web_config->getConfig(0,'WPAY');
        $this->wxpay = (isset($wxpay["is_use"])) ? $wxpay["is_use"] : '0';
        $this->assign("wxpay", $this->wxpay);
        $bpay = $web_config->getConfig(0,'BPAY');
        $this->assign("bpay", isset($bpay["is_use"]) ? $bpay["is_use"] : '0');
        $dpay = $web_config->getConfig(0,'DPAY');
        $this->assign("dpay", isset($dpay["is_use"]) ? $dpay["is_use"] : '0');
        $tlpay = $web_config->getConfig(0,'TLPAY');
        $this->tlpay = (isset($tlpay["is_use"])) ? $tlpay["is_use"] : '0';
        $this->assign("tlpay", $this->tlpay);
        $web_site = new WebSite();
        $web_info = $web_site->getWebSiteInfo();
        $this->assign("title", $web_info['mall_name']);
        if($web_info['realm_ip']){
            $this->realm_ip = $this->http.$web_info['realm_ip'];
        }else{
            $this->realm_ip = $_SERVER['HTTP_HOST'];
        }
        // 是否开启通知
        $noticeEmail = $web_config->getNoticeEmailConfig(0);
        $this->notice['noticeEmail'] = $noticeEmail[0]['is_use'];
        $this->assign("notice", $this->notice);
        //是否开启购物返积分
        $isPoint = $web_config->getConfig(0,'IS_POINT')['value'];
        $this->assign("isPoint", $isPoint);

    }
    /**
     * 检测用户
     */
    private function checkLogin()
    {
        $uid = $this->user->getSessionUid();
        if (empty($uid)) {
            $_SESSION['login_pre_url'] = __URL(__URL__ . $_SERVER['PATH_INFO']);
            $redirect = __URL(__URL__ . "/login");
            $this->redirect($redirect);
        }
        $is_member = $this->user->getSessionUserIsMember();
        if (empty($is_member)) {
            $redirect = __URL(__URL__ . "/login");
            $this->redirect($redirect);
        }
    }
    public function index()
    {
        $member = new MemberService();
        // 商品收藏
        $data_goods = array(
            "nmf.fav_type" => "goods",
            "nmf.uid" => $this->uid
        );
        $goods_collection_list = $member->getMemberGoodsFavoritesList(1, 0, $data_goods,'fav_time desc limit 7');
        $this->assign("goods_collection_list", $goods_collection_list["data"]);
        $this->assign("goods_collection_list_count", count($goods_collection_list["data"]));

        // 交易提醒 商品列表 商品数量
        $orderService = new OrderService();
        $condition = null;
        $condition['buyer_id'] = $this->uid;
        $order_status_num = $orderService->getOrderStatusNum($condition);
        $orderList = $orderService->getOrderList(1, 0, $condition, 'create_time desc limit 3');
        //优惠券数量
        $vouchersCount = 0;
        if ($this->isCouponOn){
            $coupon = new VslCouponModel();
            $vouchersCount = $coupon->getCouponCounts(['nc.website_id'=>$this->website_id,'nc.uid'=>$this->uid,'nc.state'=>1,'ct.end_time'=>['>=',time()]]);
        }
        $this->assign("vouchersCount", $vouchersCount);
        $this->assign("title_before", '会员中心');
//        $this->assign('user_notice', $user_notice);
        $this->assign("order_status_num", $order_status_num);
        $this->assign("orderList", $orderList);
        return view($this->style . 'Member/memberCenter');
    }
    /**
     * 收货地址列表
     */
    public function addressList()
    {
        if(request()->isPost()){
            $member = new MemberService();
            $page_index = request()->post('page_index', '1');
            $addresslist = $member->getMemberExpressAddressList($page_index, 9, '', '');
            return $addresslist;
        }else{
            $this->assign("title_before", '收货地址');
            return view($this->style . 'Member/address');
        }
    }

    /**
     * 会员地址管理
     * 添加地址
     *
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function addressInsert()
    {
        if (request()->isAjax()) {
            $member = new MemberService();
            $consigner = request()->post('consigner', '');
            $mobile = request()->post('mobile', '');
            $phone = request()->post('phone', '');
            $province = request()->post('province', '');
            $city = request()->post('city', '');
            $district = request()->post('district', '');
            $address = request()->post('address', '');
            $zip_code = request()->post('zip_code', '');
            $alias = request()->post('alias', '');
            $is_default = request()->post('is_default', '');// 设为默认
            $retval = $member->addMemberExpressAddress($consigner, $mobile, $phone, $province, $city, $district, $address, $zip_code, $alias,$is_default);
            return AjaxReturn($retval);
        }
    }

    /**
     * 编辑收货地址：
     */
    public function updateMemberAddress()
    {
        $id = request()->post('id', '');
        $consigner = request()->post('consigner', ''); // 收件人
        $mobile = request()->post('mobile', ''); // 电话
        $phone = request()->post('phone', ''); // 固定电话
        $province = request()->post('province', ''); // 省
        $city = request()->post('city', ''); // 市
        $district = request()->post('district', ''); // 区县
        $address = request()->post('address', ''); // 详细地址
        $zip_code = request()->post('zip_code', ''); // 邮编
        $alias = ""; // 城市别名
        $is_default = request()->post('is_default', ''); // 设为默认
        $member = new MemberService();
        // 修改
        $res = $member->updateMemberExpressAddress($id, $consigner, $mobile, $phone, $province, $city, $district, $address, $zip_code, $alias,$is_default);
        return AjaxReturn($res);
    }

    /**
     * 获取用户地址详情
     *
     * @return Ambigous <\think\static, multitype:, \think\db\false, PDOStatement, string, \think\Model, \PDOStatement, \think\db\mixed, multitype:a r y s t i n g Q u e \ C l o , \think\db\Query, NULL>
     */
    public function getMemberAddressDetail()
    {
        $address_id = request()->post('id', 0);
        $member = new MemberService();
        $info = $member->getMemberExpressAddressDetail($address_id);
        return $info;
    }

    /**
     * 会员地址删除
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function memberAddressDelete()
    {
        $id = request()->post('id', '');
        $member = new MemberService();
        $res = $member->memberAddressDelete($id);
        return AjaxReturn($res);
    }

    /**
     * 获取省列表
     */
    public function getProvince()
    {
        $address = new Address();
        $province_list = $address->getProvinceList();
        return json($province_list);
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

    /**
     * 获取选择地址
     *
     * @return unknown
     */
    public function getSelectAddress()
    {
        $address = new Address();
        $province_list = $address->getProvinceList();
        $province_id = request()->post('province_id', 0);
        $city_id = request()->post('city_id', 0);
        $city_list = $address->getCityList($province_id);
        $district_list = $address->getDistrictList($city_id);
        $data["province_list"] = $province_list;
        $data["city_list"] = $city_list;
        $data["district_list"] = $district_list;
        return $data;
    }

    /**
     * 我的订单
     */
    public function orderList()
    {
        $orderService = new OrderService();
        if(request()->isPost()){
            $status = request()->post('status', 'all');
            $page = request()->post('page_index', 1);
            $page_size = request()->post('page_size', '10');
            $search_text = request()->post('search_text', '');
            $condition['buyer_id'] = $this->uid;
            $condition["is_deleted"] = 0; // 未删除的订单
            $condition["buy_type"] = ['neq', 2]; // 不显示渠道商自提的订单
            if($search_text){
                $condition['order_no'] = $search_text;
            }
            if ($status != 'all') {
                switch ($status) {
                    case 0:
                        $condition['order_status'] = 0;
                        break;
                    case 1:
                        $condition['order_status'] = 1;
                        // 订单状态为待发货实际为已经支付未完成还未发货的订单
                        $condition['shipping_status'] = 0; // 0 待发货
                        $condition['pay_status'] = 2; // 2 已支付
                        $condition['order_status'] = array(
                            'neq',
                            4
                        ); // 4 已完成
                        $condition['order_status'] = array(
                            'neq',
                            5
                        ); // 5 关闭订单
                        break;
                    case 2:
                        $condition['order_status'] = 2;
                        break;
                    case 3:
                        $condition['order_status'] = 3;
                        break;
                    case 4:
                        $condition['order_status'] = array(
                            'in',
                            '-1,-2'
                        );
                        break;
                    case 5:
                        $condition['order_status'] = array(
                            'in',
                            '3,4'
                        );
                        $condition['is_evaluate'] = 0;
                        break;
                    default:
                        break;
                }
                if ($condition['order_status'] == array(
                        'in',
                        '-1,-2'
                    )) {
                    $orderList = $orderService->getOrderList($page, $page_size, $condition, 'create_time desc');
                    foreach ($orderList['data'] as $key => $item) {
                        $order_item_list = array();
                        $order_item_list = $orderList['data'][$key]['order_item_list'];
                        foreach ($order_item_list as $k => $value) {
                            if ($value['refund_status'] == 0 || $value['refund_status'] == -2) {
                                unset($order_item_list[$k]);
                            }
                        }
                        $orderList['data'][$key]['order_item_list'] = $order_item_list;
                    }
                } else {
                    $orderList = $orderService->getOrderList($page, $page_size, $condition, 'create_time desc');
                }
            } else {
                $orderList = $orderService->getOrderList($page, $page_size, $condition, 'create_time desc');
            }
            foreach ($orderList['data'] as $key => $item) {
                $orderList['data'][$key]['create_time'] = date('Y-m-d H:i:s',$orderList['data'][$key]['create_time']);
                //如果是预售订单
                if($item['presell_id'] && $item['money_type'] == 0){//付定金
                    $orderList['data'][$key]['order_money'] = $item['order_money'];
                }elseif($item['presell_id'] && $item['money_type'] == 1){//付尾款
                    $orderList['data'][$key]['order_money'] = $item['final_money'];
                }elseif($item['presell_id'] && $item['money_type'] == 2){//总额
                    $orderList['data'][$key]['order_money'] =  $item['order_money'];
                }
                if($item['presell_id'] !=0 && $item['money_type'] == 2){//付完尾款了
                    $orderList['data'][$key]['presell_status'] = 2;//已付尾款
                    $orderList['data'][$key]['pay_type_name']= OrderStatus::getPayType($item['payment_type']) .'+'. OrderStatus::getPayType($item['payment_type_presell']) ;
                    if($item['payment_type']==16 || $item['payment_type']==17){
                        if($item['payment_type_presell']!=16 && $item['payment_type_presell']!=17){
                            $block = new VslBlockChainRecordsModel();
                            $block_info = $block->getInfo(['data_id'=>$item['out_trade_no']],'*');
                            if($block_info && $block_info['from_type']==4){
                                $orderList['data'][$key]['order_money'] = $block_info['cash'].'ETH + ¥ '.$item['final_money'];
                            }else if($block_info && $block_info['from_type']==8){
                                $orderList['data'][$key]['order_money'] = $block_info['cash'].'EOS + ¥ '.$item['final_money'];
                            };
                        }
                        if($item['payment_type_presell']==16 || $item['payment_type_presell']==17){
                            $block = new VslBlockChainRecordsModel();
                            $block_info = $block->getInfo(['data_id'=>$item['out_trade_no']],'*');
                            $block_info1 = $block->getInfo(['data_id'=>$item['out_trade_no_presell']],'*');
                            if($block_info && $block_info['from_type']==4 && $block_info1 && $block_info1['from_type']==4){
                                $orderList['data'][$key]['order_money'] = $block_info['cash'].'ETH +'.$block_info1['cash'].'ETH';
                            }else if($block_info && $block_info['from_type']==4 && $block_info1 && $block_info1['from_type']==8){
                                $orderList['data'][$key]['order_money'] = $block_info['cash'].'ETH +'.$block_info1['cash'].'EOS';
                            }else if($block_info && $block_info['from_type']==8 && $block_info1 && $block_info1['from_type']==4){
                                $orderList['data'][$key]['order_money'] = $block_info['cash'].'EOS +'.$block_info1['cash'].'ETH';
                            }else if($block_info && $block_info['from_type']==8 && $block_info1 && $block_info1['from_type']==8){
                                $orderList['data'][$key]['order_money'] = $block_info['cash'].'EOS +'.$block_info1['cash'].'EOS';
                            }
                        }
                    }else if($item['payment_type_presell']==16 || $item['payment_type_presell']==17){
                        if($item['payment_type']!=16 && $item['payment_type']!=17){
                            $block = new VslBlockChainRecordsModel();
                            $block_info1 = $block->getInfo(['data_id'=>$item['out_trade_no_presell']],'*');
                            if($block_info1 && $block_info1['from_type']==4){
                                $orderList['data'][$key]['order_money'] = '¥ '.$item['pay_money'] .'+'.$block_info1['cash'].'ETH';
                            }else if($block_info1 && $block_info1['from_type']==8){
                                $orderList['data'][$key]['order_money'] = '¥ '.$item['pay_money'] .'+'.$block_info1['cash'].'EOS';
                            }
                        }
                    }else{
                        $order_list[$k]['order_real_money'] = '¥ '.$item['order_money'];
                    }
                }elseif($item['presell_id'] !=0 && $item['money_type'] == 0){//预售未付定金
                    $orderList['data'][$key]['presell_status'] = 0;//待付定金
                    $orderList['data'][$key]['order_real_money'] = '¥ '.$item['order_money'];
                }elseif($item['presell_id'] !=0 && $item['money_type'] == 1){//预售付完定金，待付尾款
                    $orderList['data'][$key]['order_money'] = '¥ '.$item['final_money'];
                    $orderList['data'][$key]['presell_status'] = 1;//待付尾款
                }else{
                    if($item['payment_type']==16){
                        $orderList['data'][$key]['order_real_money'] = $item['coin'].'ETH';
                    }elseif($item['payment_type']==17){
                        $orderList['data'][$key]['order_real_money'] = $item['coin'].'EOS';
                    }else{
                        $orderList['data'][$key]['order_real_money'] = '¥ '.$item['order_money'];
                    }
                }
            }
            return $orderList;
        }
        $condition1['buyer_id'] = $this->uid;
        $condition1["is_deleted"] = 0; // 未删除的订单
        // 查询个人用户的订单数量
        $orderStatusNum = $orderService->getOrderStatusNum($condition1);
        $this->assign("statusNum", $orderStatusNum);
        $this->assign("title_before", '我的订单');
        return view($this->style . 'Member/myOrder');
    }

    /**
     * 我的收藏-->商品收藏
     */
    public function goodsCollectionList()
    {
        if(request()->isPost()){
            $member = new MemberService();
            $page_index = request()->post('page_index', '1');
            $data = array(
                "nmf.fav_type" => 'goods',
                "nmf.uid" => $this->uid
            );
            $goods_collection_list = $member->getMemberGoodsFavoritesList($page_index, 8, $data);
            return $goods_collection_list;
        }else{
            $this->assign("title_before", '商品收藏');
            return view($this->style . 'Member/goodsCollection');
        }
    }


    /**
     * 取消收藏 商品
     */
    public function cancelCollGoodsOrShop()
    {
        $fav_id = request()->post('fav_id','');
        $fav_type = request()->post('fav_type','');
        $member = new MemberService();
        $result = $member->deleteMemberFavorites($fav_id, $fav_type);
        return AjaxReturn($result);
    }
    /**
     * 查询右侧边栏的店铺收藏
     *
     * @return unknown
     */
    public function queryShopOrGoodsCollections()
    {
        $member = new MemberService();
        $type = $_POST["type"];
        $data = array(
            "nmf.fav_type" => $type,
            "nmf.uid" => $this->uid
        );
        $list = null;
        if ($type == "shop") {
            $list = $member->getMemberShopsFavoritesList(1, 50, $data);
        } else {
            $list = $member->getMemberGoodsFavoritesList(1, 50, $data);
        }
        return $list["data"];
    }

    /**
     * 订单详情
     */
    public function orderDetail()
    {
        $order_id = request()->get('order_id', 0);
        if ($order_id == 0) {
            $this->error("没有获取到订单信息");
        }
        $order_service = new OrderService();
        $order_count = $order_service->getUserOrderDetailCount($this->uid, $order_id);
        if ($order_count == 0) {
            $this->error("没有获取到订单信息");
        }
        $detail = $order_service->getOrderDetail($order_id);
        if (empty($detail)) {
            $this->error("没有 获取到订单信息");
        }

        //代付定金
        if($detail['presell_id']!=0  && $detail['money_type']==0){
            $detail['status_name'] = "待付定金";
        }
        //待付尾款
        if($detail['presell_id']!=0  && $detail['money_type']==1){
            $detail['status_name'] = "待付尾款";
        }
        if($detail['payment_type'] && $detail['payment_type_presell']){
            $detail['payment_type_name'] = OrderStatus::getPayType($detail['payment_type']) .'+'. OrderStatus::getPayType($detail['payment_type_presell']);
        }
        if($detail['payment_type']==16 || $detail['payment_type']==17 || $detail['payment_type_presell']==16 || $detail['payment_type_presell']==17){
            $block = new VslBlockChainRecordsModel();
            $block_info = $block->getInfo(['data_id'=>$detail['out_trade_no']],'*');
            $block_info1 = $block->getInfo(['data_id'=>$detail['out_trade_no_presell']],'*');
            if($block_info['from_type']==4 && $block_info1['from_type']==4){
                $detail['first_money'] = $block_info['cash'].'ETH';
                $detail['final_money'] = $block_info1['cash'].'ETH';
                $detail['order_money'] = $block_info['cash'].'ETH +' .$block_info1['cash'].'ETH';
            }
            if($block_info['from_type']==8 && $block_info1['from_type']==4){
                $detail['first_money'] = $block_info['cash'].'EOS';
                $detail['final_money'] = $block_info1['cash'].'ETH';
                $detail['order_money'] = $block_info['cash'].'EOS +' .$block_info1['cash'].'ETH';
            }
            if($block_info['from_type']==4 && $block_info1['from_type']==8){
                $detail['first_money'] = $block_info1['cash'].'ETH';
                $detail['final_money'] = $block_info1['cash'].'EOS';
                $detail['order_money'] = $block_info['cash'].'ETH +' .$block_info1['cash'].'EOS';
            }
            if($block_info['from_type']==8 && $block_info1['from_type']==8){
                $detail['first_money'] = $block_info['cash'].'EOS';
                $detail['final_money'] = $block_info1['cash'].'EOS';
                $detail['order_money'] = $block_info['cash'].'EOS +' .$block_info1['cash'].'EOS';
            }
            if($block_info['from_type']==4 && $block_info1['from_type']!=8 && $block_info1['from_type']!=4){
                if($detail['presell_id'] && $detail['final_money']){
                    $detail['first_real_money'] = $block_info['cash'].'ETH';
                    $detail['final_real_money'] = '¥ '.$detail['final_money'];
                    $detail['order_real_money'] = $block_info['cash'].'ETH + ¥ '.$detail['final_money'];
                }else{
                    $detail['order_real_money'] = $detail['coin'].'ETH';
                }
            }
            if($block_info['from_type']!=8 && $block_info['from_type']!=4 && $block_info1['from_type']==8){
                if($detail['presell_id'] && $detail['first_money']){
                    $detail['final_real_money'] = $block_info['cash'].'EOS';
                    $detail['first_real_money'] = '¥ '.$detail['first_money'];
                    $detail['order_real_money'] = '¥ '.$detail['first_money'].'+' .$block_info['cash'].'EOS';
                }
            }
            if($block_info['from_type']==8 && $block_info1['from_type']!=8 && $block_info1['from_type']!=4){
                if($detail['presell_id'] && $detail['final_money']){
                    $detail['first_real_money'] = $block_info['cash'].'EOS';
                    $detail['final_real_money'] = '¥ '.$detail['final_money'];
                    $detail['order_real_money'] = $block_info['cash'].'EOS + ¥ '.$detail['final_money'];
                }else{
                    $detail['order_real_money'] = $detail['coin'].'EOS';
                }
            }
            if($block_info['from_type']!=4 && $block_info['from_type']!=8 && $block_info1['from_type']==4){
                if($detail['presell_id'] && $detail['first_money']){
                    $detail['final_real_money'] = $block_info['cash'].'ETH';
                    $detail['first_real_money'] = '¥ '.$detail['first_money'];
                    $detail['order_real_money'] = '¥ '.$detail['first_money'] .'+'.$block_info['cash'].'EOS';
                }
            }
            $detail['first_money'] = '¥ '.$detail['first_money'];
            $detail['final_money'] = '¥ '.$detail['final_money'];
        }else{
            if($detail['first_money']!=null){
                $detail['first_money'] = '¥ '.$detail['first_money'];
            }else{
                $detail['first_money'] = '';
            }
            if($detail['final_money']!=null){
                $detail['final_money'] = '¥ '.$detail['final_money'];
            }else{
                $detail['final_money'] = '';
            }
            $detail['order_money'] = '¥ '.$detail['order_money'];
        }
        if($detail['payment_type']==16 || $detail['payment_type']==17 || $detail['payment_type_presell']==16 || $detail['payment_type_presell']==17){
            $detail['promotion_status'] = true;
        }
        if($detail['order_status']==6){
            $detail['status_name'] = '链上处理中';
        }
        $this->assign("order", $detail);

        $config = new Config();
        $shopSet = $config->getShopConfig(0);
        $this->assign("order_buy_close_time", $shopSet['order_buy_close_time']);

        return view($this->style . 'Member/orderDetail');
    }

    /**
     * 查询包裹物流信息
     */
    public function getOrderGoodsExpressMessage()
    {
        $express_id = request()->post("express_no", 0); // 物流包裹id
        //查询收货人手机号后四位
        $order_goods_express = new VslOrderGoodsExpressModel();
        $order = new VslOrderModel();
        $order_id = $order_goods_express->getInfo(['express_no' => $express_id, 'website_id' => $this->website_id], 'order_id')['order_id'];
        $receiver_phone = $order->getInfo(['order_id' => $order_id], 'receiver_mobile')['receiver_mobile'];
        $res = -1;
        if ($express_id) {
            $res = getShipping($express_id, '', 'auto', $this->website_id, $receiver_phone);
        }
        return $res;
    }



    /**
     * 取消订单
     */
    public function orderClose()
    {
        $orderService = new OrderService();
        $order_id = request()->post('order_id', '');
        $order = $orderService->orderClose($order_id);
        return AjaxReturn($order);
    }

    /**
     * 获取购物车信息
     * @see \app\shop\controller\BaseController::getShoppingCart()
     */
    public function getShoppingCart()
    {
        $goods = new GoodsService();
        $cart_list = $goods->getCart($this->uid);
        return $cart_list;
    }

    /**
     * 立即购买
     */
    public function buyNowSession(&$msg = '', $store_id)
    {
        $order_sku_list = isset($_SESSION['order_sku_list']) ? $_SESSION['order_sku_list'] : '';
        if (empty($order_sku_list)) {
            $redirect = __URL(__URL__ . '/index');
            $this->redirect($redirect);
        }
        $goods_man_song_model =$this->is_full_cut ? new Fullcut():'';
        $cart_list = array();
        $order_sku_list = explode(':', $_SESSION['order_sku_list']);
        $sku_id = $order_sku_list[0];
        $num = $order_sku_list[1];

        // 获取商品sku信息
        $goods_server = new GoodsService();
        if ($store_id) {
            $goods_sku = new VslStoreGoodsSkuModel();
            $sku_info = $goods_sku::get(['sku_id' => $sku_id, 'store_id' => $store_id], ['goods']);
        } else {
            $goods_sku = new VslGoodsSkuModel();
        $sku_info = $goods_sku::get($sku_id, ['goods']);
        }
        $promotion_info['discount_num'] = 10;
        if($this->is_discount){
            $promotion = new Discount();
            $promotion_info = $promotion->getPromotionInfo($sku_info->goods_id, $sku_info->goods->shop_id, $sku_info->goods->website_id);
        }


//        $member_model = new VslMemberModel();
//        $member_level_info = $member_model->getInfo(['uid'=>$this->uid])['member_level'];
//        $member_level = new VslMemberLevelModel();
//        $member_info = $member_level->getInfo(['level_id'=>$member_level_info]);
//        $member_discount = $member_info['goods_discount'] / 10;
//        $member_is_label = $member_info['is_label'];


        if($this->is_seckill){
            //判断当前商品sku是否正在秒杀活动中
            $seckill_id = $_SESSION[$sku_id.'seckill_id'] ? : '';
            $is_seckill_condition['s.seckill_id'] = $seckill_id;
            $is_seckill_condition['nsg.sku_id'] = $sku_id;
            $seckill_server = new Seckill();
            $is_seckill = $seckill_server->isSeckillGoods($is_seckill_condition);
            if (!empty($seckill_id)) {
                if ($is_seckill) {
                    //秒杀价
                    $seckill_price = $is_seckill['seckill_price'];
                    //库存
                    $remain_num = $is_seckill['remain_num'];
                    //限购
                    $limit_buy = $is_seckill['seckill_limit_buy'];
                    $uid = $this->uid;
                    $website_id = $this->website_id;
                    //获取我已经买了多少个
                    $buy_num = $goods_server->getActivityOrderSku($uid, $sku_id, $website_id, $seckill_id);
//                $buy_num = 0;
                    $sku_info->stock = $remain_num;
                    $sku_info['max_buy'] = (($limit_buy - $buy_num) < 0) ? 0 : ($limit_buy - $buy_num);
                    $sku_info->goods->max_buy = $sku_info['max_buy'];
                    $sku_info['price'] = $seckill_price;
                    $num = $num > $sku_info['max_buy'] ? $sku_info['max_buy'] : $num;
                    $member_discount = 1;//秒杀是没有会员折扣的
                    $promotion_info['discount_num'] = 10;//秒杀是没有限时折扣的
                    if($num == 0){
                        return ['code'=>-1, 'message'=>$sku_info->goods->goods_name.' 商品秒杀您已达上限！'];
                    }
                }else{
                    $msg .= $sku_info->goods->goods_name.'商品秒杀活动已结束！已恢复商品原有价格'.PHP_EOL;
                }
            }
        }
        // 查询当前商品是否有SKU主图
        $order_goods_service = new OrderGoods();
        $picture = $order_goods_service->getSkuPictureBySkuId($sku_info);
        // 清除非法错误数据
        $cart = new VslCartModel();
        if (empty($sku_info)) {
            $cart->destroy([
                'buyer_id' => $this->uid,
                'sku_id' => $sku_id
            ]);
            $redirect = __URL(__URL__ . '/index');
            $this->redirect($redirect);
        }
        $cart_list['stock'] = $sku_info['stock']; // 库存
        $cart_list['sku_name'] = $sku_info['sku_name'];
        $cart_list['sku_id'] = $sku_info['sku_id'];
        $cart_list['seckill_id'] = $is_seckill['seckill_id']?:0;
        $cart_list['price'] = $sku_info['price'];//原始价格
        $member_discount = 1;
        $member_is_label = 0;

        if ($this->uid) {
            $goodsDiscountInfo = $goods_server->getGoodsInfoOfIndependentDiscount($sku_info['goods_id'], $sku_info['price']);
            if ($goodsDiscountInfo) {
                $member_price = $goodsDiscountInfo['member_price'];
                $member_discount = $goodsDiscountInfo['member_discount'];
                $member_is_label = $goodsDiscountInfo['member_is_label'];
            }
            $member_price = $member_price;
        }

        $cart_list['member_price'] = $member_price;
        $cart_list['member_discount'] = $member_discount;
        $cart_list['member_discount_label'] = $member_is_label;
        $cart_list['discount_id'] = $promotion_info['discount_id'] ?: '';
        $cart_list['promotion_discount'] = $promotion_info['discount_num'];
        $cart_list['promotion_shop_id'] = $promotion_info['shop_id'];


        if($promotion_info['integer_type'] == 1){
            $cart_list['promotion_price'] = round($sku_info['price'] * $promotion_info['discount_num'] / 10);
            $cart_list['discount_price'] = round( $cart_list['member_price'] * $promotion_info['discount_num'] / 10);
        }else{
            $cart_list['promotion_price'] = round($sku_info['price'] * $promotion_info['discount_num'] / 10, 2);
            $cart_list['discount_price'] = round( $cart_list['member_price'] * $promotion_info['discount_num'] / 10, 2);
        }
        if($promotion_info['discount_type'] == 2){
            $cart_list['promotion_discount'] = 1;
            $cart_list['promotion_price'] = $promotion_info['discount_num'];
            $cart_list['discount_price'] = $promotion_info['discount_num'];
        }
        $cart_list['goods_id'] = $sku_info->goods->goods_id;
        $cart_list['goods_name'] = $sku_info->goods->goods_name;
        $cart_list['max_buy'] = $sku_info->goods->max_buy; // 限购数量
        $cart_list['point_exchange_type'] = $sku_info->goods->point_exchange_type; // 积分兑换类型 0 非积分兑换 1 只能积分兑换
        $cart_list['point_exchange'] = $sku_info->goods->point_exchange; // 积分兑换
        $cart_list['goods_type'] = $sku_info['goods']['goods_type'];
        $cart_list['store_list'] = $sku_info['goods']['store_list'];
        if ($sku_info->goods->state != 1) {
            $redirect = __URL(__URL__ . '/index');
            $this->redirect($redirect);
        }
        $cart_list['num'] = $num;
        // 如果购买的数量超过限购，则取限购数量
        if ($sku_info->goods->max_buy != 0 && $sku_info->goods->max_buy < $num) {
            $num = $sku_info->goods->max_buy;
        }
        // 如果购买的数量超过库存，则取库存数量
        if ($sku_info['stock'] < $num) {
            $num = $sku_info['stock'];
        }
        // 获取图片信息，如果该商品有SKU主图，就用。否则用商品主图
        $album_picture_model = new AlbumPictureModel();
        $picture_info = $album_picture_model->get($picture == 0 ? $sku_info->goods->picture : $picture);
        $cart_list['picture_info'] = $picture_info;

        if (count($cart_list) == 0) {
            $redirect = __URL(__URL__ . '/index');
            $this->redirect($redirect);
        }
        if($this->shopStatus && $sku_info->goods->shop_id){
            $shop = new VslShopModel();
            $list[$sku_info->goods->shop_id]['shop']['shop_name'] = $shop->getInfo(['shop_id' => $sku_info->goods->shop_id, 'website_id' => $this->website_id])['shop_name'];
        }else{
            $list[$sku_info->goods->shop_id]['shop']['shop_name'] = '自营店';
        }
        $list[$sku_info->goods->shop_id]['sku'] = [$sku_id => $cart_list];
        $goods_sku_list = $sku_id . ':' . $num; // 商品skuid集合
        $cart_sku_info[$sku_info->goods->shop_id][$sku_id] = ['sku_id' => $sku_id, 'goods_id' => $sku_info['goods_id'], 'price' => $sku_info['price'], 'discount_price' => $cart_list['discount_price'], 'num' => $num];
        if(!$is_seckill && $this->is_full_cut){
            $res['full_cut_lists'] = $goods_man_song_model->getCartManSong($cart_sku_info);
        }
        $res['list'] = $list;
        $res['goods_sku_list'] = $goods_sku_list;
        if(!empty($res['full_cut_lists']) && !$is_seckill){
            foreach ($res['full_cut_lists'] as $shop_id => $full_cut_info) {
                if(!empty($full_cut_info['discount_percent'])) {
                    foreach ($full_cut_info['discount_percent'] as $sku_id => $discount_percent) {
                        if (!empty($full_cut_info['full_cut']) && $full_cut_info['full_cut']['discount'] > 0) {
                            $cart_sku_info[$shop_id][$sku_id]['full_cut_amount'] = $full_cut_info['full_cut']['discount'];
                            $cart_sku_info[$shop_id][$sku_id]['full_cut_percent'] = $full_cut_info['discount_percent'][$sku_id];
                            $cart_sku_info[$shop_id][$sku_id]['full_cut_percent_amount'] = round($full_cut_info['discount_percent'][$sku_id] * $full_cut_info['full_cut']['discount'], 2);
                        }
                    }
                }
            }
        }
        $res['cart_sku_info'] = $cart_sku_info;

        return $res;
    }
    //确认订单页面更新满减送信息
    public function getFullCutList(){
        if (!$this->is_full_cut){
            return [];
        }
        $post = request()->post('postdata/a', '');
        $goods_man_song_model = new Fullcut();
        $fullCutLists = $goods_man_song_model->getCartManSong($post);
        foreach($fullCutLists as $shop_id => $full_cut_info){
            if($full_cut_info['full_cut']){
                $fullCutLists[$shop_id]['full_cut']['goods_limit'] = implode(',', $full_cut_info['full_cut']['goods_limit']);
            }
            if($full_cut_info['shipping']){
                $fullCutLists[$shop_id]['shipping']['goods_limit'] = implode(',', $full_cut_info['shipping']['goods_limit']);
            }
            foreach ($full_cut_info['discount_percent'] as $sku_id => $discount_percent) {
                if (!empty($full_cut_info['full_cut']) && $full_cut_info['full_cut']['discount'] > 0) {
                    $cart_sku_info[$shop_id][$sku_id]['full_cut_amount'] = $full_cut_info['full_cut']['discount'];
                    $cart_sku_info[$shop_id][$sku_id]['full_cut_percent'] = $full_cut_info['discount_percent'][$sku_id];
                    $cart_sku_info[$shop_id][$sku_id]['full_cut_percent_amount'] = round($full_cut_info['discount_percent'][$sku_id] * $full_cut_info['full_cut']['discount'], 2);
                }
            }
        }
        return $fullCutLists;
    }
    //确认订单页面更新优惠券列表
    public function getCouponList(){
        if (!$this->isCouponOn){
            return [];
        }
        $post = request()->post('postdata/a', '');
        $couponServer = new CouponServer();
        $couponList = $couponServer->getMemberCouponListNew($post); // 获取优惠券
        foreach($couponList as $shop_id => $coupon){
            foreach($coupon['coupon_info'] as $key => $item){
                $couponList[$shop_id]['coupon_info'][$key]['goods_limit'] = $item['goods_limit'] ? implode(',', $item['goods_limit']) : '';
            }
        }
        return $couponList;
    }

    /**
     * 获取购物车商品
     */
    public function getCartSession(&$msg = '', $store_id, $cart_ids)
    {
        // 加入购物车
        $cart_id_array = isset($_SESSION['cart_id_array']) ? $_SESSION['cart_id_array'] : ''; // 用户所选择的商品
        if (empty($cart_id_array)) {
            $redirect = __URL(__URL__ . '/index');
            $this->redirect($redirect);
        }
        $goods = new GoodsService();
        $info = $goods->getCartListsNew($cart_id_array, $msg, $store_id, $cart_ids);
        if (count($info['shop_goods_lists']) == 0) {
            $redirect = __URL(__URL__ . '/index');
            $this->redirect($redirect);
        }
        $res['list'] = $info['shop_goods_lists'];
        $res['full_cut_lists'] = $info['full_cut_lists'];
        $res['goods_sku_list'] = $info['goods_sku_list'];
        $res['cart_sku_info'] = $info['cart_sku_info'];
        $res['shipping_lists'] = $info['shipping_lists'];
        return $res;
    }

    /**
     * 购买流程：待付款订单 第2步
     */
    public function paymentOrder()
    {
        $store_id = request()->post('store_id', '0');
        $cart_ids = request()->post('cart_ids/a', '');
        $shop_ids = request()->post('shop_id');
        $lng = request()->post('lng', '');
        $lat = request()->post('lat', '');
        $member = new MemberService();
        $couponServer = $this->isCouponOn ? new CouponServer() : '';
        $storeServer = $this->storeStatus ? new Store() : '';
        $Config = new Config();
        $order_tag = isset($_SESSION['order_tag']) ? $_SESSION['order_tag'] : '';
        if (empty($order_tag)) {
            $redirect = __URL(__URL__ . '/index');
            $this->redirect($redirect);
        }
        $this->assign('order_tag', $order_tag); // 标识：立即购买还是购物车中进来的
        $msg = '';
        switch ($order_tag) {
            case 'buy_now': // 从session获取
                $res = $this->buyNowSession($msg, $store_id);
                if($res['code'] == -1){
                    $this->error($res['message']);
                }
                break;
            case 'cart': // 从购物车获取
                $res = $this->getCartSession($msg, $store_id, $cart_ids);
                break;
        }
        $list = $res['list'];
        $cart_sku_info = $res['cart_sku_info'];

        $full_cut_lists = $res['full_cut_lists'];
        //$shipping_lists = $res['shipping_lists'];
        $count_point_exchange = 0;
        $goods_tpye = 1;
        $store_list = 0;
        $has_store = 0;
        $shop_has_store = 1;
        $total_has_store = 0;
        foreach ($list as $shop_id => $shop) {
            $storeSet = 0;
            if($this->storeStatus){
                $storeSet = $storeServer->getStoreSet($shop_id)['is_use'];
            }
            $list[$shop_id]['shop']['has_store'] = $storeSet ? : 0;
            if($list[$shop_id]['shop']['has_store']){
                $has_store = 1;
            }
            foreach ($shop['sku'] as $sku_id => $sku) {
                if ($sku['point_exchange_type'] == 1) {
                    if ($sku['point_exchange'] > 0) {
                        $count_point_exchange += $sku['point_exchange'] * $sku['num'];
                    }
                }
                if(($order_tag=='buy_now' && $sku['goods_type']==0)){
                    $goods_tpye = 0;//虚拟商品
                    $store_list = $sku['store_list'];
                    //虚拟商品不支持货到付款
                    $this->assign("dpay", 0);
                }
                if(($order_tag=='buy_now' && $sku['goods_type']==3)){
                    $goods_tpye = 3;
                }
                //处理此笔订单下的商品，如果门店没有此商品的库存，则不能选择此门店
                $storeGoodsModel = new VslStoreGoodsModel();
                $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
                $goodsModel = new VslGoodsModel();
                $have_stock_store_list = '';
                $no_stock_store_list = '';
                $store_list = $goodsModel->Query(['goods_id' => $sku['goods_id']], 'store_list')[0];
                if (empty($store_list)) {
                    $shop_has_store = 0;
                    $list[$shop_id]['shop']['has_store'] = 0;
                    $have_stock_store_list = '';
                    $no_stock_store_list = '';
                    break;
                } else {
                    $store_list = explode(',', $store_list);
                    foreach ($store_list as $k => $v) {
                        //没有库存或者此门店没有上架此商品都不能选择此门店
                        $stock_condition = [
                            'goods_id' => $sku['goods_id'],
                            'sku_id' => $sku['sku_id'],
                            'store_id' => $v,
                            'website_id' => $this->website_id
                        ];
                        $state_condition = [
                            'goods_id' => $sku['goods_id'],
                            'store_id' => $v,
                            'website_id' => $this->website_id
                        ];
                        $stock = $storeGoodsSkuModel->getInfo($stock_condition, 'stock');
                        $state = $storeGoodsModel->getInfo($state_condition, 'state');
                        if ($stock['stock'] <= 0 || $state['state'] == 0) {
                            $no_stock_store_list .= $v . ',';
                            unset($v);
                        }
                        if ($v) {
                            $have_stock_store_list .= $v . ',';
                        }
                    }
                }
            }
            if (empty($no_stock_store_list) && empty($have_stock_store_list)) {
                $store_list = 0;
                $shop_has_store = 0;
            } else {
                $no_stock_store_list = trim($no_stock_store_list, ',');
                $have_stock_store_list = trim($have_stock_store_list, ',');
                if (empty($no_stock_store_list)) {
                    //所有门店可自提
                    $have_stock_store_list = explode(',', $have_stock_store_list);
                    $have_stock_store_list = array_unique($have_stock_store_list);
                    $store_list = $have_stock_store_list;
                    $shop_has_store = 1;
                } elseif (empty($have_stock_store_list)) {
                    //没有门店可自提
                    $store_list = 0;
                    $shop_has_store = 0;
                    $list[$shop_id]['shop']['has_store'] = 0;
                } elseif ($no_stock_store_list && $have_stock_store_list) {
                    $no_stock_store_list = explode(',', $no_stock_store_list);
                    $no_stock_store_list = array_unique($no_stock_store_list);
                    $have_stock_store_list = explode(',', $have_stock_store_list);
                    $have_stock_store_list = array_unique($have_stock_store_list);
                    $arr = array_merge($no_stock_store_list, $have_stock_store_list);
                    $arr = array_unique($arr);
                    foreach ($arr as $key => $val) {
                        foreach ($no_stock_store_list as $k => $v) {
                            if ($val == $v) {
                                unset($val);
                            }
                        }
                        if ($val) {
                            $result[] = $val;
                        }
                    }
                    if ($result) {
                        $store_list = $result;
                        $shop_has_store = 1;
                    } else {
                        $store_list = 0;
                        $shop_has_store = 0;
                    }
                }
            }
            $total_has_store += $shop_has_store;
            if ($store_list) {
                $storeModel = new VslStoreModel();
                $address_service = new Address();
                $storeServer = new storeServer();
                foreach ($store_list as $k => $v) {
                    $store_info = $storeModel->getInfo(['store_id' => $v], '*');
                    $newList['store_id'] = $store_info['store_id'];
                    $newList['shop_id'] = $store_info['shop_id'];
                    $newList['website_id'] = $store_info['website_id'];
                    $newList['store_name'] = $store_info['store_name'];
                    $newList['start_time'] = $store_info['start_time'];
                    $newList['finish_time'] = $store_info['finish_time'];
                    $newList['store_tel'] = $store_info['store_tel'];
                    $newList['address'] = $store_info['address'];
                    $newList['lat'] = $store_info['lat'];
                    $newList['lng'] = $store_info['lng'];
                    $newList['province_name'] = $address_service->getProvinceName($store_info['province_id']);
                    $newList['city_name'] = $address_service->getCityName($store_info['city_id']);
                    $newList['dictrict_name'] = $address_service->getDistrictName($store_info['district_id']);
                    $data[] = $newList;
                }
                $list[$shop_id]['shop']['store_list'] = $data;
                unset($data);
            }
        }
        if ($total_has_store > 0) {
            $has_store = 1;
        } else {
            $has_store = 0;
        }
        if ($shop_ids >= 0 && $lng && $lat) {
            $place = [
                'lng' => $lng,
                'lat' => $lat
            ];
            foreach ($list as $k => $v) {
                if ($k == $shop_ids) {
                    foreach ($v['shop']['store_list'] as $k1 => $v1) {
                        $list[$k]['shop']['store_list'][$k1]['distance'] = $storeServer->sphere_distance(['lat' => $v1['lat'], 'lng' => $v1['lng']], $place);
                    }
                    return ['store_list'=>$list[$k]['shop']['store_list']];
                }
            }
        }
        if ($store_id) {
            return $list;
        }
//        p($list);die;
        $this->assign('store_list', $store_list);
        $this->assign('goods_type', $goods_tpye);
        $this->assign('has_store', $has_store);
        $this->assign('list', $list);
        //$this->assign("count_point_exchange", $count_point_exchange); // 总积分

        if (empty($list)) {
            $this->error('待支付订单中商品不可为空');
        }
        $this->assign('full_cut_lists', $full_cut_lists);
        //$this->assign('shipping_lists', $shipping_lists);

        $shop_config = $Config->getShopConfigNew(0);
        $order_invoice_content = explode(',', $shop_config['order_invoice_content']);
        $shop_config['order_invoice_content_list'] = array();
        foreach ($order_invoice_content as $v) {
            if (!empty($v)) {
                array_push($shop_config['order_invoice_content_list'], $v);
            }
        }
        $this->assign('shop_config', $shop_config); // 后台配置
        if($msg){
            $this->assign('msg', $msg);
        }
        $member_account = $member->getMemberAccount($this->uid); // 用户余额
        $this->assign('member_account', $member_account); // 用户余额、积分
        $point_deduction = $Config->getShopConfig(0,$this->website_id);
        $this->assign('point_deduction',$point_deduction);// 积分抵扣
        $coupon_list = $this->isCouponOn ? $couponServer->getMemberCouponListNew($cart_sku_info) : []; // 获取优惠券
        $this->assign('coupon_list', $coupon_list); // 优惠卷
        $member_server = new MemberService();
        $customform = $member_server->getOrderCustomForm();
        $this->assign('customform', $customform); // 自定义表单
        $this->assign('getStoreListUrl', __URL(addons_url('store://Store/getStoreList')));
        return view($this->style . 'Member/confirmOrder');
    }

    /**
     * 立即购买、加入购物车都存入session中，
     */
    public function orderCreateSession()
    {
        $tag = request()->post('tag', '');
        $seckill_id = request()->post('seckill_id', '');
        if (empty($tag)) {
            return ['code' => -1, 'message' => '缺少参数'];
        }
        switch ($tag) {
            case 'buy_now':
                // 立即购买
                $_SESSION['order_tag'] = 'buy_now';
                //判断redis中的库存，库存不足就不让其购买。
                $sku_id = request()->post('sku_id');
                if (!empty($seckill_id) && $this->is_seckill) {
                    $sec_service = new Seckill();
                    $sku_mdl = new VslGoodsSkuModel();
                    $_SESSION[$sku_id.'seckill_id'] = $seckill_id;
                    $condition_is_seckill['s.seckill_id'] = $seckill_id;
                    $condition_is_seckill['nsg.sku_id'] = $sku_id;
                    $is_seckill = $sec_service->isSeckillGoods($condition_is_seckill);
                    $goods_id = $sku_mdl->getInfo(['sku_id'=>$sku_id],'goods_id')['goods_id'];
                    if($is_seckill){
                        $redis = $this->connectRedis();
                        $redis_goods_sku_store_key = 'store_' . $goods_id . '_' . $sku_id;
                        $is_index = $redis->llen($redis_goods_sku_store_key);
                        if(!$is_index){
                            return ['code' => -1, 'message' => '商品秒杀库存不足'];
                        }
                    }
                }else{
                    $_SESSION[$sku_id.'seckill_id'] = 0;
                }
                $_SESSION['order_sku_list'] = request()->post('sku_id') . ':' . request()->post('num');
                break;
            case 'cart':
                // 加入购物车
                $_SESSION['order_tag'] = 'cart';
                $_SESSION['cart_id_array'] = $_POST['cart_id'];
                break;
        }
        return ['code' => 1, 'message' => ''];
    }

    /**
     * 退款/退货/维修订单列表
     */
    public function backList()
    {
        if(request()->isPost()){
            $orderService = new OrderService();
            $page = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text', '');
            if($search_text){
                $condition['order_no'] = $search_text;
            }
            $condition['buyer_id'] = $this->uid;
            $condition['refund_status'] = 'backList';
            $orderList = $orderService->getOrderList($page, $page_size, $condition, 'create_time desc');
            foreach ($orderList['data'] as $key => $item) {
                $order_item_list = $orderList['data'][$key]['order_item_list'];
                $orderList['data'][$key]['create_time'] = date('Y-m-d H:i:s',$orderList['data'][$key]['create_time']);
                foreach ($order_item_list as $k => $value) {
                    if ($value['refund_status'] == 0 || $value['refund_status'] == -2) {
                        unset($order_item_list[$k]);
                    }
                }
                $orderList['data'][$key]['order_item_list'] = $order_item_list;
            }
            return $orderList;
        }else{
            $this->assign("title_before", '退款/退货订单');
            return view($this->style . 'Member/refund');
        }

    }

    /**
     * 取消退款
     */
    public function cancelOrderRefund()
    {
        if (request()->isAjax()) {
            $orderService = new OrderService();
            $order_id = request()->post('order_id', '');
            $order_goods_id = request()->post('order_goods_id/a');
            $cancel_order = $orderService->orderGoodsCancel($order_id, $order_goods_id);
            return $cancel_order;
        }
    }

    /**
     * 商品评价/晒单
     */
    public function goodsEvaluationList($page = 1, $page_size = 10)
    {
        if(request()->isPost()){
            $order = new OrderService();
            $page = request()->post('page_index', '');
            $page_size = request()->post('page_size', 5);
            $search_text = request()->post('search_text', '');
            $condition['uid'] = $this->uid;
            if($search_text){
                $condition['order_no|goods_name|shop_name'] = array(
                    "like",
                    "%" . $search_text . "%"
                );
            }
            $goodsEvaluationList = $order->getOrderEvaluateDataList($page, $page_size, $condition, 'addtime desc');
            foreach ($goodsEvaluationList['data'] as $k => $v) {
                $goodsEvaluationList['data'][$k]['evaluationImg'] = (empty($v['image'])) ? '' : explode(',', $v['image']);
                $goodsEvaluationList['data'][$k]['addtime'] = date('Y-m-d H:i:s',$goodsEvaluationList['data'][$k]['addtime']);
                $goodsEvaluationList['data'][$k]['againEvaluationImg'] = (empty($v['again_image'])) ? '' : explode(',', $v['again_image']);
            }
            return $goodsEvaluationList;
        }
        $this->assign("title_before", '商品评价/晒单');
        return view($this->style . 'Member/goodsEvaluationList');
    }

    /**
     * 图片上传
     */
    public function moveUploadFile($file_path, $key)
    {
        $config = new Config();
        $upload_type = $config->getUploadType();
        if ($upload_type == 1) {
            $ok = @move_uploaded_file($file_path, $key);
            $result = [
                "code" => $ok,
                "path" => $key,
                "domain" => '',
                "bucket" => ''
            ];
        } elseif ($upload_type == 2) {
            $alioss = new AliOss();
            $result = $alioss->setAliOssUplaod($file_path, $key);
        }
        return $result;
    }

    /**
     * 用户信息
     */
    public function person()
    {
        if (request()->isPost()) {
            $user_name = request()->post('user_name', '');
            $nick_name = request()->post('nick_name', '');
            $post_data = request()->post('post_data', '');
            $user_qq = request()->post('user_qq', '');
            $real_name = request()->post('real_name', '');
            $sex = request()->post('sex', '');
            $birthday = request()->post('birthday', '');
            $province_id = request()->post('province_id', '');
            $city_id = request()->post('city_id', '');
            $district_id = request()->post('district_id', '');
            $birthday = date('Y-m-d', strtotime($birthday));
            // 把从前台显示的内容转变为可以存储到数据库中的数据
            $update_info_status = $this->user->updateMemberInformation($user_name,$nick_name, $user_qq, $real_name, $sex, $birthday, $province_id,$city_id,$district_id,"",$post_data);
            return AjaxReturn($update_info_status);
        }
        $addons = new AddonsConfigModel();
        $custom_info = json_decode($addons->getInfo(['website_id' => $this->website_id,'addons'=>'customform'],'value')['value'],true);
        if($custom_info['member_status']){
            $this->assign('custom_member_status',$custom_info['member_status']);
        }
        return view( $this->style.'Member/personInfo');
    }
    public function upLoad(){
        if ($_FILES && request()->post("")) {
            // var_dump($_FILES["user_headimg"]);
            if ((($_FILES["user_headimg"]["type"] == "image/gif") || ($_FILES["user_headimg"]["type"] == "image/jpeg") || ($_FILES["user_headimg"]["type"] == "image/pjpeg") || ($_FILES["user_headimg"]["type"] == "image/png")) && ($_FILES["user_headimg"]["size"] < 10000000)) {
                if ($_FILES["user_headimg"]["error"] > 0) {
                    // echo "错误： " . $_FILES["user_headimg"]["error"] . "<br />";
                }
                $file_name = date("YmdHis") . rand(0, date("is")); // 文件名
                $ext = explode(".", $_FILES["user_headimg"]["name"]);
                $file_name .= "." . $ext[1];
                // 检测文件夹是否存在，不存在则创建文件夹
                $path = 'upload/' . $this->website_id . '/avator/';
                if (!file_exists($path)) {
                    $mode = intval('0777', 8);
                    mkdir($path, $mode, true);
                }
                $img_result = $this->moveUploadFile($_FILES["user_headimg"]["tmp_name"], $path . $file_name);
                if ($img_result["code"]) {
                    // $user_headimg = $path . $file_name;
                    $user_headimg = $img_result["path"];
                    return AjaxReturn(1,$user_headimg);
                } else {
                    return AjaxReturn(-1,"头像上传失败!");
                }
                // move_uploaded_file($_FILES["user_headimg"]["tmp_name"], $path . $file_name);
            } else {
                return AjaxReturn(-1,"请上传图片!");
            }
        }
    }
    /**
     * base64位的图片处理（头像）
     */
    public function upLoads(){
        $data = request()->post('data', '');
        $user_headimg = $this->user->updateMemberHeading($data);
        if($user_headimg){
            return AjaxReturn(1,$user_headimg);
        }else{
            return AjaxReturn(-1);
        }
    }
    public function saveCustomImg(){
        $data = $_POST['imgarr'];
        $real_imgs = [];
        foreach ($data as $v) {
            $img = changeFile($v, $this->website_id, 'customform');
            if(strstr($img, 'http')){
                array_push($real_imgs,$img);
            }else{
                $img = '/'.$img;
                array_push($real_imgs,$img);
            }
        }
        if($real_imgs){
            $real_imgs = implode(',',$real_imgs);
        }
        return $real_imgs;
    }

    /**
     * 会员积分流水
     */
    public function integralList()
    {
        if(request()->isPost()){
            $page_index = request()->post('page_index', '1');
            $page_size = request()->post('page_size', PAGESIZE);
            $condition['nmar.uid'] = $this->uid;
            $condition['nmar.account_type'] = 1;
            // 查看用户在该商铺下的积分消费流水
            $list = $this->user->getAccountLists($page_index, $page_size, $condition);
            return $list;
        }
        $this->assign("title_before", '我的积分');
        return view($this->style . 'Member/points');
    }

    /**
     * 会员余额流水
     */
    public function balanceList()
    {
        if(request()->isPost()){
            $page_index = request()->post('page_index', '1');
            $page_size = request()->post('page_size', PAGESIZE);
            $condition['nmar.uid'] = $this->uid;
            $condition['nmar.account_type'] = 2;
            // 该账户的余额流水
            $list = $this->user->getAccountLists($page_index, $page_size, $condition);
            return $list;
        }
        $accountAccount = new MemberAccount();
        $accountSum = $accountAccount->getMemberAccount($this->uid);
        $this->assign("account",$accountSum );
        $config = new Config();
        $balanceConfig = $config->getBalanceWithdrawConfig(0);
        $this->assign('is_use', $balanceConfig['is_use']);
        $this->assign("title_before", '账号余额');
        return view($this->style . 'Member/balance');
    }

    /**
     * 提现记录
     */
    public function balanceWithdrawList()
    {
        $page_index = request()->get('page', '1');
        $shop_id = 0;
        $page_size = 10;
        $condition['nmar.uid'] = $this->uid;
        $condition['nmar.shop_id'] = $shop_id;
        $list = $this->user->getMemberBalanceWithdraw($page_index, $page_size, $condition, 'ask_for_date desc');
        foreach ($list['data'] as $k => $v) {
            if ($v['status'] == 1) {
                $list['data'][$k]['status'] = '已同意';
            } elseif ($v['status'] == 0) {
                $list['data'][$k]['status'] = '已申请';
            } else {
                $list['data'][$k]['status'] = '已拒绝';
            }
        }
        $account_type = 2;
        $accountAccount = new MemberAccount();
        $accountSum = $accountAccount->getMemberAccount($this->uid, $account_type);
        $this->assign("sum", number_format($accountSum, 2,'.',''));
        $this->assign('page_count', $list['page_count']);
        $this->assign('total_count', $list['total_count']);
        $this->assign("balances", $list);
        $this->assign('page', $page_index);
        return view($this->style . 'Member/balanceWithdrawList');
    }

    /**
     * 余额提现
     */
    public function balanceWithdrawals()
    {
        if (request()->isAjax()) {
            // 提现
            $uid = $this->uid;
            $withdraw_no = 'BT'.time() . rand(111, 999);
            $bank_account_id = request()->post('bank_id', '');
            $type = request()->post('type', '');
            $cash = request()->post('cash', '');
            $password = request()->post('payment_password', '');
            $member = new UserModel();
            $pay_password = $member->getInfo(['uid'=>$this->uid],'payment_password')['payment_password'];
            if(md5($password)!=$pay_password){
                $result['code'] = -2;
                $result['message'] = '支付密码错误';
                return $result;
            }
            $shop_id = 0;
            $member = new MemberService();
            $retval = $member->addMemberBalanceWithdraw($shop_id, $withdraw_no, $uid, $bank_account_id, $cash,$type);
            if($retval){
                $result['code'] = 1;
                $result['message'] = '提现申请成功';
                return $result;
            }
        } else {
            $member = new MemberService();
            $account_list = $member->getMemberBankAccount();
            // 获取会员余额
            $uid = $this->uid;
            $shop_id = 0;
            $members = new MemberAccount();
            $account = $members->getMemberBalance($uid);
            $this->assign('account', $account);
            $config = new Config();
            $balanceConfig = $config->getBalanceWithdrawConfig($shop_id);
            $cash = $balanceConfig['value']["withdraw_cash_min"];
            $this->assign('cash', $cash);
            $poundage = $balanceConfig['value']["member_withdraw_poundage"];
            $this->assign('poundage', $poundage);
            $withdrawals_begin = $balanceConfig['value']["withdrawals_begin"];
            $withdrawals_end = $balanceConfig['value']["withdrawals_end"];
            $this->assign('withdrawals_begin', $withdrawals_begin);
            $this->assign('withdrawals_end', $withdrawals_end);
            $withdraw_message = $balanceConfig['value']["withdraw_message"];
            $this->assign('withdraw_messages', $withdraw_message);
            if($withdraw_message){
                $withdraw_message = explode(',', $withdraw_message);
                $info1 = $config->getAlipayConfig($this->website_id);
                $info2 = $config->getWpayConfig($this->website_id);
                $info3 = $config->getTlConfig(0,$this->website_id);
                if($info1['is_use']==0){
                    $withdraw_message = array_merge(array_diff($withdraw_message, [3]));
                }
                if($info2['is_use']==0 || $info2['value']['wx_tw']==0){
                    $withdraw_message = array_merge(array_diff($withdraw_message, [2]));
                }
                if($info3['is_use']==0 || $info2['value']['tl_tw']==0){
                    $withdraw_message = array_merge(array_diff($withdraw_message, [1]));
                }
            }else{
                $withdraw_message = [];
            }
            $bank = new VslBankModel();
            $bank_list = $bank->getQuery(['sort'=>['neq','']],'*','sort asc');
            $this->assign('bank_list',$bank_list);
            $this->assign('withdraw_message', $withdraw_message);
            $is_examine = $balanceConfig['value']["is_examine"];
            $this->assign('is_examine', $is_examine);
            $this->assign('account_list', $account_list);
            return view($this->style . "Member/withdrawalBalance");
        }
    }

    /**
     * 账号列表
     */
    public function accountList()
    {
        $member = new MemberService();
        if (request()->isPost()) {
            $account_list = $member->getMemberBankAccount();
            if($account_list){
                $bank = new VslBankModel();
                foreach ($account_list as $k=>$v){
                    if($v['bank_code']){
                        $info = $bank->getInfo(['bank_code'=>$v['bank_code']],'*');
                        $account_list[$k]['bank_iocn']= $info['bank_iocn'];
                        $account_list[$k]['open_bank']= $info['bank_short_name'];
                    }else{
                        $account_list[$k]['bank_iocn'] = '';
                    }
                }
            }
            return $account_list;
        }else{
            $members = new MemberAccount();
            $account = $members->getMemberBalance($this->uid);
            $this->assign('account', $account);
            $config = new Config();
            $balanceConfig = $config->getBalanceWithdrawConfig(0);
            $withdraw_message = $balanceConfig['value']["withdraw_message"];
            if($withdraw_message){
                $withdraw_message = explode(',', $withdraw_message);
                $info1 = $config->getAlipayConfig($this->website_id);
                $info2 = $config->getWpayConfig($this->website_id);
                $info3 = $config->getTlConfig(0,$this->website_id);
                if($info1['is_use']==0){
                    $withdraw_message = array_merge(array_diff($withdraw_message, [3]));
                }
                if($info2['is_use']==0 || $info2['value']['wx_tw']==0){
                    $withdraw_message = array_merge(array_diff($withdraw_message, [2]));
                }
                if($info3['is_use']==0 || $info2['value']['tl_tw']==0){
                    $withdraw_message = array_merge(array_diff($withdraw_message, [1]));
                }
            }else{
                $withdraw_message = [];
            }
            $bank = new VslBankModel();
            $bank_list = $bank->getQuery(['sort'=>['neq','']],'*','sort asc');
            $this->assign('bank_list',$bank_list);
            $this->assign('withdraw_message', $withdraw_message);
            $this->assign("title_before", '提现账号');
            return view($this->style . "Member/withdrawalAccount");
        }
    }

    //新增提现账户
    public function addAccount()
    {
        if (request()->isPost()) {
            $member = new MemberService();
            $uid = $this->uid;
            $type = request()->post('type', '1');
            $account_number = request()->post('account_number', '');
            $bank_code = request()->post('bank_code', '');
            $bank_type = request()->post('bank_type', '00');
            $bank_username = request()->post('realname', '');
            $bank_card = request()->post('bank_card', '');
            $mobile = request()->post('mobile', '');
            $validdate = request()->post('valid_date', '');
            $cvv2 = request()->post('cvv2', '');
            $bank_name = request()->post('bank_name', '');
            $bank_account = new VslMemberBankAccountModel();
            $info = $bank_account->getInfo(['account_number'=>$account_number,'uid'=>$this->uid]);
            if($info){
                $retval = [
                    'code' => -1,
                    'message' => "该账号已存在"
                ];
                return $retval;
            }
            if($type==1 || $type==4){
                $url = "https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?cardNo=".$account_number."&cardBinCheck=true";
                $result =GetCurl($url);
                if(!$result['validated']){
                    $retval = [
                        'code' => -1,
                        'message' => "查询不到该卡号信息"
                    ];
                    return $retval;
                }
            }
            if($type==1){
                $pay = new UnifyPay();
                $res = $pay->tlSigning($bank_type,$account_number,$bank_card,$bank_username,$mobile,$validdate,$cvv2,$this->uid,$this->website_id);
                if($res['retcode']=='SUCCESS'){
                    if($res['trxstatus']==1999){
                        $retval = [
                            'code' => 1,
                            'message' => "验证码已发送",
                            'thpinfo'=>$res['thpinfo']
                        ];
                        return $retval;
                    }else{
                        $retval = [
                            'code' => -1,
                            'message' => $res['errmsg']
                        ];
                        return $retval;
                    }
                }else{
                    $retval = [
                        'code' => -1,
                        'message' => $res['retmsg']
                    ];
                    return $retval;
                }
            }else{
                $retval = $member->addMemberBankAccount($uid, $type, $account_number,$bank_code,$bank_type,$bank_username,$bank_card,$bank_name,$mobile,$validdate,$cvv2);
                if ($retval > 0) {
                    $retval = [
                        'code' => -1,
                        'message' => '添加成功'
                    ];
                    return $retval;
                } else {
                    $retval = [
                        'code' => -1,
                        'message' => '添加失败'
                    ];
                    return $retval;
                }
            }
        }else{
            $bank = new VslBankModel();
            $bank_list = $bank->getQuery(['sort'=>['neq','']],'*','sort asc');
            $this->assign('bank_list',$bank_list);
            $config = new Config();
            $balanceConfig = $config->getBalanceWithdrawConfig(0);
            $withdraw_message = $balanceConfig['value']["withdraw_message"];
            if($withdraw_message){
                $withdraw_message = explode(',', $withdraw_message);
                $info1 = $config->getAlipayConfig($this->website_id);
                $info2 = $config->getWpayConfig($this->website_id);
                $info3 = $config->getTlConfig(0,$this->website_id);
                if($info1['is_use']==0){
                    $withdraw_message = array_merge(array_diff($withdraw_message, [3]));
                }
                if($info2['is_use']==0 || $info2['value']['wx_tw']==0){
                    $withdraw_message = array_merge(array_diff($withdraw_message, [2]));
                }
                if($info3['is_use']==0 || $info3['value']['tl_tw']==0){
                    $withdraw_message = array_merge(array_diff($withdraw_message, [1]));
                }
            }else{
                $withdraw_message = [];
            }

            $this->assign('withdraw_message', $withdraw_message);
            return view($this->style . "Member/addAccount");
        }
    }

    /**
     * 银行卡用户签约重发签约验证码
     */
    public function tlAgreeSms()
    {
        $accttype = request()->post('bank_type', '00');
        $acctno = request()->post('account_number', '');
        $idno = request()->post('bank_card', '');
        $acctname = request()->post('realname', '');
        $mobile = request()->post('mobile', '');
        $validdate = request()->post('validdate', '');
        $cvv2 = request()->post('cvv2', '');
        $thpinfo = htmlspecialchars_decode(stripslashes(request()->post('thpinfo', '')));
        $pay = new UnifyPay();
        $res = $pay->tlAgreeSms($accttype,$acctno,$idno,$acctname,$mobile,$validdate,$cvv2,$thpinfo,$this->uid,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            $retval = [
                'code' => 1,
                'message' => '短信已发送'
            ];
            return $retval;
        }else{
            $retval = [
                'code' => -1,
                'message' => $res['errmsg']
            ];
            return $retval;
        }
    }

    /**
     * 银行卡用户签约申请确认
     */
    public function tlAgreeSigning()
    {
        $account_id = request()->post('account_id','');
        $account_number = request()->post('account_number', '');
        $bank_code = request()->post('bank_code', '');
        $bank_type = request()->post('bank_type', '00');
        $bank_username = request()->post('realname', '');
        $bank_card = request()->post('bank_card', '');
        $mobile = request()->post('mobile', '');
        $validdate = request()->post('valid_date', '');
        $cvv2 = request()->post('cvv2', '');
        $smscode = request()->post('smscode', '');
        $thpinfo = htmlspecialchars_decode(stripslashes(request()->post('thpinfo', '')));
        $pay = new UnifyPay();
        $res = $pay->tlAgreeSigning($bank_type,$account_number,$bank_card,$bank_username,$mobile,$validdate,$cvv2,$smscode,$thpinfo,$this->uid,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            if($res['trxstatus']=='0000'){
                if($bank_code){
                    $bank = new VslBankModel();
                    $open_bank = $bank->getInfo(['bank_code'=>$bank_code],'bank_short_name')['bank_short_name'];
                }
                $bank_account = new VslMemberBankAccountModel();
                if($account_id){
                    $bank_account->save(['cvv2'=>$cvv2,'valid_date'=>$validdate,'mobile'=>$mobile,'bank_type'=>$bank_type,'realname'=>$bank_username,'bank_card'=>$bank_card,'bank_code'=>$bank_code,'open_bank'=>$open_bank,'agree_id'=>$res['agreeid'],'modify_date'=>time(),'account_number'=>$account_number],['id'=>$account_id]);
                }else{
                    $bank_account->save(['uid'=>$this->uid,'cvv2'=>$cvv2,'valid_date'=>$validdate,'mobile'=>$mobile,'bank_type'=>$bank_type,'realname'=>$bank_username,'bank_card'=>$bank_card,'bank_code'=>$bank_code,'open_bank'=>$open_bank,'agree_id'=>$res['agreeid'],'account_number'=>$account_number,'type'=>1,'create_date'=>time(),'website_id'=>$this->website_id]);
                }
                $data['message'] = '签约成功';
                $data['code'] = 1;
            }else{
                $data['code'] = -1;
                $data['message'] = $res['errmsg'];
            }
            return $data;
        }else{
            $data['code'] = -1;
            $data['message'] = $res['retmsg'];
            return $data;
        }
    }

    /**
     * 银行卡用户解绑银行卡
     */
    public function tlUntying()
    {
        $id = request()->post('id', '');
        $pay = new UnifyPay();
        $res = $pay->tlUntying($id,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            $member = new VslMemberBankAccountModel();
            $member->delData(['id'=>$id]);
            $data['message'] = '解绑成功';
            $data['code'] = 1;
            return $data;
        }else{
            $data['code'] = -1;
            $data['message'] = $res['errmsg'];
            return $data;
        }
    }
    /**
     *  账户详情
     */
    public function accountDetail(){
        $member = new VslMemberBankAccountModel();
        $type = request()->get('type', '');
        $id = request()->get('id', '');
        $account_list = $member->getInfo(['id'=>$id]);
        if($type==1){
            $user = new UserModel();
            $user_info = $user->getInfo(['uid'=>$this->uid]);
            $this->assign('user_info', $user_info);
        }
        if($account_list){
            $bank = new VslBankModel();
            if($account_list['bank_code']){
                $info = $bank->getInfo(['bank_code'=>$account_list['bank_code']],'*');
                $account_list['bank_info']= $info;
                $account_list['open_bank']= $info['bank_short_name'];
            }
        }
        $bank = new VslBankModel();
        $bank_list = $bank->getQuery(['sort'=>['neq','']],'*','sort asc');
        $this->assign('bank_list',$bank_list);
        $this->assign('type', $type);
        $this->assign('account_list', $account_list);
        $config = new Config();
        $balanceConfig = $config->getBalanceWithdrawConfig(0);
        $withdraw_message = $balanceConfig['value']["withdraw_message"];
        if($withdraw_message){
            $withdraw_message = explode(',', $withdraw_message);
            $info1 = $config->getAlipayConfig($this->website_id);
            $info2 = $config->getWpayConfig($this->website_id);
            $info3 = $config->getTlConfig(0,$this->website_id);
            if($info1['is_use']==0){
                $withdraw_message = array_merge(array_diff($withdraw_message, [3]));
            }
            if($info2['is_use']==0 || $info2['value']['wx_tw']==0){
                $withdraw_message = array_merge(array_diff($withdraw_message, [2]));
            }
            if($info3['is_use']==0 || $info3['value']['tl_tw']==0){
                $withdraw_message = array_merge(array_diff($withdraw_message, [1]));
            }
        }else{
            $withdraw_message = [];
        }
        $this->assign('withdraw_message', $withdraw_message);
        return view($this->style . "accountDetail");
    }
    /**
     * 删除账户信息
     */
    public function delAccount()
    {
        if (request()->isAjax()) {
            $member = new MemberService();
            $account_id = request()->post('id', '');
            $retval = $member->delMemberBankAccount($account_id);
            return AjaxReturn($retval);
        }
    }
    //银行账户列表
    public function tl_bank_account()
    {
        $member = new MemberService();
        $account_list = $member->getMemberBankAccount(0,['type'=>['in','1,4']]);
        if($account_list){
            $bank = new VslBankModel();
            foreach ($account_list as $k=>$v){
                if($v['bank_code']){
                    $info = $bank->getInfo(['bank_code'=>$v['bank_code']],'*');
                    $account_list[$k]['bank_iocn']= $info['bank_iocn'];
                    $account_list[$k]['open_bank']= $info['bank_short_name'];
                }else{
                    $account_list[$k]['bank_iocn'] = '';
                }
            }
        }
        return $account_list;
    }
    /**
     * 获取要修改的银行账户信息
     */
    public function getbankinfo()
    {
        $member = new MemberService();
        $id = request()->post('id', '');
        $result = $member->getMemberBankAccountDetail($id);
        return $result;
        // return AjaxReturn($result);
    }

    //编辑提现账户
    public function updateBankAccount()
    {
        $member = new MemberService();
        $account_id = request()->post('account_id','');
        $type = request()->post('type', '');
        $account_number = request()->post('account_number', '');
        $bank_code = request()->post('bank_code', '');
        $bank_type = request()->post('bank_type', '00');
        $bank_username = request()->post('realname', '');
        $bank_card = request()->post('bank_card', '');
        $mobile = request()->post('mobile', '');
        $validdate = request()->post('valid_date', '');
        $cvv2 = request()->post('cvv2', '');
        $bank_name = request()->post('bank_name', '');
        if($type==1 || $type==4){
            $url = "https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?cardNo=".$account_number."&cardBinCheck=true";
            $result =GetCurl($url);
            if(!$result['validated']){
                $data['message'] = '查询不到该卡号信息';
                $data['code'] = -1;
                return $data;
            }
        }
        if($type==1){
            $pay = new UnifyPay();
            $res = $pay->tlSigning($bank_type,$account_number,$bank_card,$bank_username,$mobile,$validdate,$cvv2,$this->uid,$this->website_id);
            if($res['retcode']=='SUCCESS'){
                if($res['trxstatus']==1999){
                    $data['data']['thpinfo'] =$res['thpinfo'];
                    $data['message'] = '验证码已发送';
                    $data['code'] = 1;
                    return $data;
                }else{
                    $data['message'] =$res['errmsg'];
                    $data['code'] = -2;
                    return $data;
                }
            }else{
                $data['code'] = -2;
                $data['message'] = $res['retmsg'];
                return $data;
            }
        }else{
            $retval = $member->updateMemberBankAccount($account_id,$type,$account_number,$bank_code,$bank_type,$bank_username,$bank_card,$bank_name,$mobile,$validdate,$cvv2);
            if ($retval > 0) {
                $data['code'] = 0;
                $data['message'] = "修改成功";
                return $data;
            } else {
                $data['code'] = '-1';
                $data['message'] = "修改失败";
                return $data;
            }
        }
    }

    /**
     * 银行卡支付申请
     */
    public function tlPayApplyAgree()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        $id = request()->post('id', '');
        if (empty($out_trade_no)) {
            $data['code'] = -1;
            $data['data'] = '';
            $data['message'] = "没有获取到订单信息";
            return $data;
        }
        $type = request()->post('type', '');
        if(empty($type)){
            $type = request()->get('type', 2);
        }
        $bank = new VslMemberBankAccountModel();
        $bank_info = $bank->getInfo(['id'=>$id]);
        if (empty($bank_info['agree_id'])) {
            $data['code'] = -1;
            $data['data'] = '';
            $data['message'] = "银行卡未签约";
            return $data;
        }
        $pay_ment = new VslOrderPaymentModel();
        $payment_info = $pay_ment->getInfo(['out_trade_no'=>$out_trade_no]);
        if($payment_info){
            $pay_ment->save(['pay_from'=>$type],['out_trade_no'=>$out_trade_no]);
        }
        $notify_url = $this->realm_ip . "/wapapi/pay/tlUrlBack";
        $pay = new UnifyPay();
        $res = $pay->payApplyAgree($id,$out_trade_no,$notify_url,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            if($res['trxstatus']==1999){
                $data['thpinfo'] =$res['thpinfo'];
                $data['code'] = 1;
                return $data;
            }elseif($res['trxstatus']==0000){
                $data['code'] = 2;
                $data['message'] = '交易已完成';
                return $data;
            }elseif($res['trxstatus']==2000 || $res['trxstatus']==2008){
                $data['code'] = 3;
                $data['message'] = '交易处理中';
                return $data;
            }else{
                $data['code'] = -1;
                $data['message'] = $res['errmsg'];
                return $data;
            }
        }else{
            $data['code'] = -1;
            $data['message'] = '支付申请失败';
            return $data;
        }
    }
    /**
     * 银行卡支付重新获取支付短信
     */
    public function paySmsAgree()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        $thpinfo = htmlspecialchars_decode(stripslashes(request()->post('thpinfo', '')));
        $pay = new UnifyPay();
        $res = $pay->paySmsAgree($out_trade_no,$thpinfo,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            $data['code'] = 1;
            $data['message'] = '发送成功';
            return $data;
        }else{
            $data['code'] = -1;
            $data['message'] = '发送失败';
            return $data;
        }
    }
    /**
     * 银行卡支付申请确认
     */
    public function tlPay()
    {
        $out_trade_no = request()->post('out_trade_no', '');
        $id = request()->post('id', '');
        $smscode = request()->post('smscode', '');
        $thpinfo = htmlspecialchars_decode(stripslashes(request()->post('thpinfo', '')));
        $pay = new UnifyPay();
        $res = $pay->payAgreeConfirm($id,$smscode,$thpinfo,$out_trade_no,$this->website_id);
        if($res['retcode']=='SUCCESS'){
            if($res['trxstatus']==0000){
                $data['code'] = 1;
                $data['message'] = '支付成功';
                return $data;
            }else{
                $data['code'] = -1;
                $data['message'] = $res['errmsg'];
                return $data;
            }
        }else{
            $data['code'] = -1;
            $data['message'] = '支付失败';
            return $data;
        }
    }
    /**
     * 创建充值订单
     */
    public function createRechargeOrder()
    {
        $member = new MemberService();
        if (request()->isPost()) {
            //创建充值订单
            $recharge_money = request()->post('recharge_money', 0);
            $out_trade_no = request()->post('out_trade_no', '');
            $res = $member->createMemberRecharge($recharge_money, $this->uid, $out_trade_no);
            return AjaxReturn($res);
        }
        // 余额充值
        $pay = new UnifyPay();
        $pay_no = $pay->createOutTradeNo();
        $this->assign("pay_no", $pay_no);
        return view($this->style . "Member/rechargeBalance");

    }

    /**
     * 余额积分相互兑换
     */
    public function exchange()
    {
        $point = request()->post('amount', '');
        $point = (float)$point;
        $result = $this->user->memberPointToBalance($this->uid, $point);
        if ($result == 1) {
            return view($this->style . 'Member/exchangeSuccess');
        } else {
            return view($this->style . 'Member/integrallist');
        }
    }

    /**
     * 退出登录
     */
    public function logOut()
    {
        $member = new MemberService();
        $member->Logout();
        return AjaxReturn(1);
    }

    /**
     * 账号安全
     */
    public function userSecurity()
    {
        return view($this->style . "Member/accountSafety");
    }

    /*
     * 确认订单页面收货地址
     */
    public function orderAddressList(){
        // 获取买家的收货地址
        $member_express_address = new VslMemberExpressAddressModel();
        $address_lists = $member_express_address::all(['uid' => $this->uid], ['area_district.city.province']);
        $is_default = [];
        foreach ($address_lists as $k => $v) {
            $address_lists[$k]['province_name'] = $v['area_district']['city']['province']['province_name'];
            $address_lists[$k]['city_name'] = $v['area_district']['city']['city_name'];
            $address_lists[$k]['district_name'] = $v['area_district']['district_name'];
            $is_default[] = $v->is_default;
        }
        array_multisort($is_default, SORT_DESC, $address_lists);
        unset($is_default);
        return $address_lists;
    }

    /**
     * 商品评价
     */
    public function reviewCommodity()
    {
            $order_id = request()->get('order_id', '');
            // 判断该订单是否是属于该用户的
            $order_service = new OrderService();
            $condition['order_id'] = $order_id;
            $condition['buyer_id'] = $this->uid;
            $condition['review_status'] = 0;
            $condition['order_status'] = array(
                'in',
                '3,4'
            );
            $order_count = $order_service->getUserOrderCountByCondition($condition);
            if ($order_count == 0) {
                $this->error("对不起,您无权进行此操作");
            }

            $order = new Order();
            $list = $order->getOrderGoods($order_id);
            $orderDetail = $order->getDetail($order_id);
            $this->assign("order_no", $orderDetail['order_no']);
            $this->assign("order_id", $order_id);
            $this->assign("shop_name",  $orderDetail['shop_name']);
            $this->assign("shop_id",  $orderDetail['shop_id']);
            $this->assign("store_id",  $orderDetail['store_id']);
            $this->assign("storeStatus",  $this->storeStatus);
            $this->assign("list", $list);
            $this->assign("list_num", count($list));
            return view($this->style . 'Member/reviewCommodity');
    }
    //获取会员基本信息
    public function getMemberBaseInfo()
    {
        $member_server = new MemberService();
        $member_info = $member_server->getUserInfoNew(['uid' => $this->uid],['province', 'city', 'district']);
        if (empty($member_info)){
            return json(['code' =>-1, 'message' =>'信息为空']);
        }
        $base_info['avatar'] = getApiSrc($member_info['user_headimg']);
        $base_info['real_name'] = $member_info['real_name'];
        $base_info['sex'] = $member_info['sex'];
        $base_info['user_name'] = $member_info['user_name'];
        $base_info['user_tel'] = $member_info['user_tel'];
        $base_info['nick_name'] = $member_info['nick_name'];
        $base_info['birthday'] = $member_info['birthday'];
        $base_info['qq'] = $member_info['user_qq'];
        $base_info['province_id'] = $member_info['province_id'] ?: 0;
        $base_info['city_id'] = $member_info['city_id'] ?: 0;
        $base_info['district_id'] = $member_info['district_id'] ?: 0;
        $base_info['province_name'] = $member_info['province']['province_name'] ?: '';
        $base_info['city_name'] = $member_info['city']['city_name'] ?: '';
        $base_info['district_name'] = $member_info['district']['district_name'] ?: '';
        $base_info['area_code'] = $member_info['area_code'];
        $base_info['custom_person'] = json_decode(htmlspecialchars_decode($member_info['custom_person']));
        $base_info['custom_data'] = $member_server->getMemberCustomForm($this->website_id);

        return json(['code' => 1, 'message' => '获取成功', 'data' => $base_info]);
    }
    /**
     * 追评
     */
    public function reviewAgain()
    {

            $order_id = request()->get('order_id', '');
            // 判断该订单是否是属于该用户的
            $order_service = new OrderService();
            $condition['order_id'] = $order_id;
            $condition['buyer_id'] = $this->uid;
            $condition['is_evaluate'] = 1;
            $order_count = $order_service->getUserOrderCountByCondition($condition);
            if ($order_count == 0) {
                $this->error("对不起,您无权进行此操作");
            }

            $order = new Order();
            $list = $order->getOrderGoods($order_id);
            $orderDetail = $order->getDetail($order_id);
            $this->assign("order_no", $orderDetail['order_no']);
            $this->assign("order_id", $order_id);
            $this->assign("shop_name",  $orderDetail['shop_name']);
            $this->assign("shop_id",  $orderDetail['shop_id']);
            $this->assign("list", $list);
            $this->assign("list_num", count($list));
            return view($this->style . 'Member/reviewAgain');

    }



    /**
     * 功能：绑定手机
     */
    public function modifyMobile()
    {

        return view($this->style . "Member/bindPhone");

    }

    /**
     * 功能：绑定邮箱
     */
    public function bindEmail()
    {
        return view($this->style . "Member/bindEmail");
    }
    /**
     * 功能：修改邮箱
     */
    public function modifyEmail()
    {
        return view($this->style . "Member/modifyEmail");
    }
    /**
     * 功能：修改密码
     */
    public function modifyPassword()
    {

        return view($this->style . "Member/modifyPassword");

    }

    /**
     * 申请退款
     *
     * @return \think\response\View
     */
    public function refundMoney()
    {
        $order_goods_id = request()->get('order_goods_id');
        $order_id = request()->get('order_id');
        if (!is_numeric($order_goods_id) && !is_numeric($order_id)) {
            $this->error("没有获取到退款信息");
        }
        if ($order_goods_id){
            $condition['vsl_order_goods.order_goods_id'] = $order_goods_id;
        }
        if ($order_id){
            $condition['vsl_order_goods.order_id'] = $order_id;
        }
        $condition['vsl_order_goods.buyer_id'] = $this->uid;
        $order_service = new OrderService();
        $detail = $order_service->getOrderGoodsRefundInfoNew($condition);
        if (count($detail['goods_list']) == 0){
            $this->error("对不起,您无权进行此操作");
        }
        $detail['refund_eth_money'] = '';
        $detail['refund_eth_charge'] = '';
        $detail['refund_eos_money'] = '';
        $detail['refund_eth_val'] = '';
        $detail['refund_eos_charge'] = '';
        $detail['refund_eos_val'] = '';
        $order_model = new VslOrderModel();
        $payment_type = $order_model->getInfo(['order_id'=>$detail['order_id']],'*');
        $money = 0;
        if($payment_type['presell_id']){
            if($payment_type['payment_type']==16 && $payment_type['payment_type_presell']==16) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 4 && $block_info1 && $block_info1['from_type'] == 4) {
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge = $blocks->ethRefundMoney($money);//运费不退扣除
                    $real_coin1 = floatval($block_info['cash']) + $block_info1['cash']-$charge;
                    $detail['refund_eth_money'] = $real_coin1 . 'ETH';//退款金额
                    $real_coin2 = floatval($payment_type['coin_charge']) * 4;
                    $detail['refund_eth_charge'] = $real_coin2 . 'ETH';//手续费（预售的支付是两笔手续费，退款又会产生两笔手续费）
                    $real_coin3 = $real_coin1-$real_coin2;
                    $detail['refund_eth_val'] = $real_coin3 . 'ETH';
                    if ($real_coin3 <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==16 && $payment_type['payment_type_presell']==17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if($block_info && $block_info['from_type']==4 && $block_info1 && $block_info1['from_type']==8){
                    $detail['refund_eth_money'] = floatval($block_info['cash']).'ETH';
                    $real_coin1 = floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_charge'] = $real_coin1.'ETH';
                    $real_coin2= floatval($block_info['cash'])-$real_coin1;
                    $detail['refund_eth_val'] = $real_coin2.'ETH';
                    if($real_coin2<=0){
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                    }
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge1 = $blocks->eosRefundMoney($money);
                    $real_coin3 = $block_info1['cash']-$charge1;
                    $detail['refund_eos_money'] = $real_coin3.'EOS';
                    if($real_coin3<=0){
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==17 && $payment_type['payment_type_presell']==16) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if($block_info && $block_info['from_type']==8 && $block_info1 && $block_info1['from_type']==4){
                    $detail['refund_eos_money'] = floatval($block_info['cash']).'EOS';
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge = $blocks->ethRefundMoney($money);
                    $real_coin1 = $block_info1['cash']-$charge;
                    $detail['refund_eth_money'] = $real_coin1.'ETH';
                    $real_coin2 = floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_charge'] = $real_coin2.'ETH';
                    $real_coin3 =  $real_coin1-$real_coin2;
                    $detail['refund_eth_val'] = $real_coin3.'ETH';
                    if($real_coin3<=0){
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==17 && $payment_type['payment_type_presell']==17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if($block_info && $block_info['from_type']==8 && $block_info1 && $block_info1['from_type']==8){
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge = $blocks->eosRefundMoney($money);
                    $real_coin = floatval($block_info['cash'])+$block_info1['cash']-$charge;
                    $detail['refund_eos_money'] = $real_coin.'EOS';
                    if($real_coin<=0){
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                        $detail['eth_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==16 && $payment_type['payment_type_presell']!=16 && $payment_type['payment_type_presell']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    $detail['refund_eth_money'] = floatval($block_info['cash']) . 'ETH';
                    $detail['refund_eth_charge'] = (floatval($payment_type['coin_charge']) * 2) . 'ETH';
                    $real_coin = floatval($block_info['cash']) - floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_val'] = $real_coin . 'ETH';
                    if ($real_coin <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        if($payment_type['final_money']==0){
                            $detail['eos_status'] = true;
                        }
                    }
                }
            }
            if($payment_type['payment_type']==17 && $payment_type['payment_type_presell']!=16 && $payment_type['payment_type_presell']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    $money = $payment_type['shipping_money'];
                    $charge = $blocks->eosRefundMoney($money);
                    $real_coin = floatval($block_info['cash'])-$charge;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['eos_status'] = true;
                        $detail['refund_eos_money'] = '0EOS';
                        if($payment_type['final_money']==0){
                            $detail['eth_status'] = true;
                        }
                    }
                }
            }
            if($payment_type['payment_type_presell']==16 && $payment_type['payment_type']!=16 && $payment_type['payment_type']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    $money = $payment_type['shipping_money'];
                    $charge = $blocks->ethRefundMoney($money);
                    $detail['refund_eth_money'] = floatval($block_info['cash']) . 'ETH';
                    $detail['refund_eth_charge'] = (floatval($payment_type['coin_charge']) * 2) . 'ETH';
                    $real_coin = floatval($block_info['cash']) -$charge- floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_val'] = $real_coin . 'ETH';
                    if ($real_coin <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        if($payment_type['pay_money']==0){
                            $detail['eos_status'] = true;
                        }
                    }
                }
            }
            if($payment_type['payment_type_presell']==17 && $payment_type['payment_type']!=16 && $payment_type['payment_type']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    $money = $payment_type['shipping_money'];
                    $charge = $blocks->ethRefundMoney($money);
                    $real_coin = floatval($block_info['cash']) -$charge;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                        if($payment_type['pay_money']==0){
                            $detail['eth_status'] = true;
                        }
                    }
                }
            }
        }else{
            if($payment_type['payment_type']==16) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    if($payment_type['shipping_money'] && $payment_type['shipping_status']>=1){
                        $money = $payment_type['shipping_money'];
                    }
                    $detail['refund_eth_money'] = floatval($block_info['cash']) . 'ETH';
                    $detail['refund_eth_charge'] = (floatval($payment_type['coin_charge']) * 2) . 'ETH';
                    $real_coin = floatval($block_info['cash'])-floatval($payment_type['coin_charge'])*2;
                    if ($real_coin<= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    if($payment_type['shipping_money'] && $payment_type['shipping_status']>=1){
                        $money = $payment_type['shipping_money'];
                    }
                    $charge = $blocks->eosRefundMoney($money);
                    $real_coin = floatval($block_info['cash'])-$charge;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                        $detail['refund_eos_money'] = '0EOS';
                    }
                }
            }
        }
        $this->assign("detail", $detail);
        return view($this->style . "Member/fillReturnMoney");
    }
    /**
     * 重新申请退款
     *
     * @return \think\response\View
     */
    public function refundMoneya()
    {
        $order_goods_id = request()->get('order_goods_id');
        $order_id = request()->get('order_id');
        if (!is_numeric($order_goods_id) && !is_numeric($order_id)) {
            $this->error("没有获取到退款信息");
        }
        if ($order_goods_id){
            $condition['vsl_order_goods.order_goods_id'] = $order_goods_id;
        }
        if ($order_id){
            $condition['vsl_order_goods.order_id'] = $order_id;
        }
        $condition['vsl_order_goods.buyer_id'] = $this->uid;
        $order_service = new OrderService();
        $order_service->updateOrderStatus($order_goods_id,$order_id);
        $detail = $order_service->getOrderGoodsRefundInfoNew($condition);
        if (count($detail['goods_list']) == 0){
            $this->error("对不起,您无权进行此操作");
        }
        $detail['refund_eth_money'] = '';
        $detail['refund_eth_charge'] = '';
        $detail['refund_eos_money'] = '';
        $detail['refund_eth_val'] = '';
        $detail['refund_eos_charge'] = '';
        $detail['refund_eos_val'] = '';
        $order_model = new VslOrderModel();
        $payment_type = $order_model->getInfo(['order_id'=>$detail['order_id']],'*');
        $money = 0;
        if($payment_type['presell_id']){
            if($payment_type['payment_type']==16 && $payment_type['payment_type_presell']==16) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 4 && $block_info1 && $block_info1['from_type'] == 4) {
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge = $blocks->ethRefundMoney($money);//运费不退扣除
                    $real_coin1 = floatval($block_info['cash']) + $block_info1['cash']-$charge;
                    $detail['refund_eth_money'] = $real_coin1 . 'ETH';//退款金额
                    $real_coin2 = floatval($payment_type['coin_charge']) * 4;
                    $detail['refund_eth_charge'] = $real_coin2 . 'ETH';//手续费（预售的支付是两笔手续费，退款又会产生两笔手续费）
                    $real_coin3 = $real_coin1-$real_coin2;
                    $detail['refund_eth_val'] = $real_coin3 . 'ETH';
                    if ($real_coin3 <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==16 && $payment_type['payment_type_presell']==17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if($block_info && $block_info['from_type']==4 && $block_info1 && $block_info1['from_type']==8){
                    $detail['refund_eth_money'] = floatval($block_info['cash']).'ETH';
                    $real_coin1 = floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_charge'] = $real_coin1.'ETH';
                    $real_coin2= floatval($block_info['cash'])-$real_coin1;
                    $detail['refund_eth_val'] = $real_coin2.'ETH';
                    if($real_coin2<=0){
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                    }
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge1 = $blocks->eosRefundMoney($money);
                    $real_coin3 = $block_info1['cash']-$charge1;
                    $detail['refund_eos_money'] = $real_coin3.'EOS';
                    if($real_coin3<=0){
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==17 && $payment_type['payment_type_presell']==16) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if($block_info && $block_info['from_type']==8 && $block_info1 && $block_info1['from_type']==4){
                    $detail['refund_eos_money'] = floatval($block_info['cash']).'EOS';
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge = $blocks->ethRefundMoney($money);
                    $real_coin1 = $block_info1['cash']-$charge;
                    $detail['refund_eth_money'] = $real_coin1.'ETH';
                    $real_coin2 = floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_charge'] = $real_coin2.'ETH';
                    $real_coin3 =  $real_coin1-$real_coin2;
                    $detail['refund_eth_val'] = $real_coin3.'ETH';
                    if($real_coin3<=0){
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==17 && $payment_type['payment_type_presell']==17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if($block_info && $block_info['from_type']==8 && $block_info1 && $block_info1['from_type']==8){
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge = $blocks->eosRefundMoney($money);
                    $real_coin = floatval($block_info['cash'])+$block_info1['cash']-$charge;
                    $detail['refund_eos_money'] = $real_coin.'EOS';
                    if($real_coin<=0){
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                        $detail['eth_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==16 && $payment_type['payment_type_presell']!=16 && $payment_type['payment_type_presell']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    $detail['refund_eth_money'] = floatval($block_info['cash']) . 'ETH';
                    $detail['refund_eth_charge'] = (floatval($payment_type['coin_charge']) * 2) . 'ETH';
                    $real_coin = floatval($block_info['cash']) - floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_val'] = $real_coin . 'ETH';
                    if ($real_coin <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        if($payment_type['final_money']==0){
                            $detail['eos_status'] = true;
                        }
                    }
                }
            }
            if($payment_type['payment_type']==17 && $payment_type['payment_type_presell']!=16 && $payment_type['payment_type_presell']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    $money = $payment_type['shipping_money'];
                    $charge = $blocks->eosRefundMoney($money);
                    $real_coin = floatval($block_info['cash'])-$charge;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['eos_status'] = true;
                        $detail['refund_eos_money'] = '0EOS';
                        if($payment_type['final_money']==0){
                            $detail['eth_status'] = true;
                        }
                    }
                }
            }
            if($payment_type['payment_type_presell']==16 && $payment_type['payment_type']!=16 && $payment_type['payment_type']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    $money = $payment_type['shipping_money'];
                    $charge = $blocks->ethRefundMoney($money);
                    $detail['refund_eth_money'] = floatval($block_info['cash']) . 'ETH';
                    $detail['refund_eth_charge'] = (floatval($payment_type['coin_charge']) * 2) . 'ETH';
                    $real_coin = floatval($block_info['cash']) -$charge- floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_val'] = $real_coin . 'ETH';
                    if ($real_coin <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        if($payment_type['pay_money']==0){
                            $detail['eos_status'] = true;
                        }
                    }
                }
            }
            if($payment_type['payment_type_presell']==17 && $payment_type['payment_type']!=16 && $payment_type['payment_type']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    $money = $payment_type['shipping_money'];
                    $charge = $blocks->ethRefundMoney($money);
                    $real_coin = floatval($block_info['cash']) -$charge;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                        if($payment_type['pay_money']==0){
                            $detail['eth_status'] = true;
                        }
                    }
                }
            }
        }else{
            if($payment_type['payment_type']==16) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    if($payment_type['shipping_money'] && $payment_type['shipping_status']>=1){
                        $money = $payment_type['shipping_money'];
                    }
                    $detail['refund_eth_money'] = floatval($block_info['cash']) . 'ETH';
                    $detail['refund_eth_charge'] = (floatval($payment_type['coin_charge']) * 2) . 'ETH';
                    $real_coin = floatval($block_info['cash'])-floatval($payment_type['coin_charge'])*2;
                    if ($real_coin<= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    if($payment_type['shipping_money'] && $payment_type['shipping_status']>=1){
                        $money = $payment_type['shipping_money'];
                    }
                    $charge = $blocks->eosRefundMoney($money);
                    $real_coin = floatval($block_info['cash'])-$charge;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                        $detail['refund_eos_money'] = '0EOS';
                    }
                }
            }
        }
        $this->assign("detail", $detail);
        return view($this->style . "Member/fillReturnMoney");
    }
    /**
     * 申请退款或退货
     *
     * @return \think\response\View
     */
    public function refundDetail()
    {
        $order_goods_id = request()->get('order_goods_id');
        $order_id = request()->get('order_id');
        $type = request()->get('type', 0);
        if (!is_numeric($order_goods_id) && !is_numeric($order_id)) {
            $this->error("没有获取到退款信息");
        }
        $order_service = new OrderService();
        if ($order_goods_id){
            $condition['vsl_order_goods.order_goods_id'] = $order_goods_id;
        }
        if ($order_id){
            $condition['vsl_order_goods.order_id'] = $order_id;
        }
        $condition['vsl_order_goods.buyer_id'] = $this->uid;
        $detail = $order_service->getOrderGoodsRefundInfoNew($condition);
        if (count($detail['goods_list']) == 0){
            $this->error("对不起,您无权进行此操作");
        }
        $detail['goods_type'] = $detail['goods_list'][0]['goods_type'];
        $detail['refund_eth_money'] = '';
        $detail['refund_eth_charge'] = '';
        $detail['refund_eos_money'] = '';
        $detail['refund_eth_val'] = '';
        $detail['refund_eos_charge'] = '';
        $detail['refund_eos_val'] = '';
        $order_model = new VslOrderModel();
        $payment_type = $order_model->getInfo(['order_id'=>$detail['order_id']],'*');
        $money = 0;
        if($payment_type['presell_id']){
            if($payment_type['payment_type']==16 && $payment_type['payment_type_presell']==16) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 4 && $block_info1 && $block_info1['from_type'] == 4) {
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge = $blocks->ethRefundMoney($money);//运费不退扣除
                    $real_coin1 = floatval($block_info['cash']) + $block_info1['cash']-$charge;
                    $detail['refund_eth_money'] = $real_coin1 . 'ETH';//退款金额
                    $real_coin2 = floatval($payment_type['coin_charge']) * 4;
                    $detail['refund_eth_charge'] = $real_coin2 . 'ETH';//手续费（预售的支付是两笔手续费，退款又会产生两笔手续费）
                    $real_coin3 = $real_coin1-$real_coin2;
                    $detail['refund_eth_val'] = $real_coin3 . 'ETH';
                    if ($real_coin3 <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==16 && $payment_type['payment_type_presell']==17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if($block_info && $block_info['from_type']==4 && $block_info1 && $block_info1['from_type']==8){
                    $detail['refund_eth_money'] = floatval($block_info['cash']).'ETH';
                    $real_coin1 = floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_charge'] = $real_coin1.'ETH';
                    $real_coin2= floatval($block_info['cash'])-$real_coin1;
                    $detail['refund_eth_val'] = $real_coin2.'ETH';
                    if($real_coin2<=0){
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                    }
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge1 = $blocks->eosRefundMoney($money);
                    $real_coin3 = $block_info1['cash']-$charge1;
                    $detail['refund_eos_money'] = $real_coin3.'EOS';
                    if($real_coin3<=0){
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==17 && $payment_type['payment_type_presell']==16) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if($block_info && $block_info['from_type']==8 && $block_info1 && $block_info1['from_type']==4){
                    $detail['refund_eos_money'] = floatval($block_info['cash']).'EOS';
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge = $blocks->ethRefundMoney($money);
                    $real_coin1 = $block_info1['cash']-$charge;
                    $detail['refund_eth_money'] = $real_coin1.'ETH';
                    $real_coin2 = floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_charge'] = $real_coin2.'ETH';
                    $real_coin3 =  $real_coin1-$real_coin2;
                    $detail['refund_eth_val'] = $real_coin3.'ETH';
                    if($real_coin3<=0){
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==17 && $payment_type['payment_type_presell']==17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                $block_info1 = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if($block_info && $block_info['from_type']==8 && $block_info1 && $block_info1['from_type']==8){
                    $money = $payment_type['shipping_money'];//运费（预售退尾款时才退手续费）
                    $charge = $blocks->eosRefundMoney($money);
                    $real_coin = floatval($block_info['cash'])+$block_info1['cash']-$charge;
                    $detail['refund_eos_money'] = $real_coin.'EOS';
                    if($real_coin<=0){
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                        $detail['eth_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==16 && $payment_type['payment_type_presell']!=16 && $payment_type['payment_type_presell']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    $detail['refund_eth_money'] = floatval($block_info['cash']) . 'ETH';
                    $detail['refund_eth_charge'] = (floatval($payment_type['coin_charge']) * 2) . 'ETH';
                    $real_coin = floatval($block_info['cash']) - floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_val'] = $real_coin . 'ETH';
                    if ($real_coin <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        if($payment_type['final_money']==0){
                            $detail['eos_status'] = true;
                        }
                    }
                }
            }
            if($payment_type['payment_type']==17 && $payment_type['payment_type_presell']!=16 && $payment_type['payment_type_presell']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    $money = $payment_type['shipping_money'];
                    $charge = $blocks->eosRefundMoney($money);
                    $real_coin = floatval($block_info['cash'])-$charge;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['eos_status'] = true;
                        $detail['refund_eos_money'] = '0EOS';
                        if($payment_type['final_money']==0){
                            $detail['eth_status'] = true;
                        }
                    }
                }
            }
            if($payment_type['payment_type_presell']==16 && $payment_type['payment_type']!=16 && $payment_type['payment_type']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    $money = $payment_type['shipping_money'];
                    $charge = $blocks->ethRefundMoney($money);
                    $detail['refund_eth_money'] = floatval($block_info['cash']) . 'ETH';
                    $detail['refund_eth_charge'] = (floatval($payment_type['coin_charge']) * 2) . 'ETH';
                    $real_coin = floatval($block_info['cash']) -$charge- floatval($payment_type['coin_charge'])*2;
                    $detail['refund_eth_val'] = $real_coin . 'ETH';
                    if ($real_coin <= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        if($payment_type['pay_money']==0){
                            $detail['eos_status'] = true;
                        }
                    }
                }
            }
            if($payment_type['payment_type_presell']==17 && $payment_type['payment_type']!=16 && $payment_type['payment_type']!=17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no_presell']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    $money = $payment_type['shipping_money'];
                    $charge = $blocks->ethRefundMoney($money);
                    $real_coin = floatval($block_info['cash']) -$charge;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['refund_eos_money'] = '0EOS';
                        $detail['eos_status'] = true;
                        if($payment_type['pay_money']==0){
                            $detail['eth_status'] = true;
                        }
                    }
                }
            }
        }else{
            if($payment_type['payment_type']==16) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 4) {
                    if($payment_type['shipping_money'] && $payment_type['shipping_status']>=1){
                        $money = $payment_type['shipping_money'];
                    }
                    $detail['refund_eth_money'] = floatval($block_info['cash']) . 'ETH';
                    $detail['refund_eth_charge'] = (floatval($payment_type['coin_charge']) * 2) . 'ETH';
                    $real_coin = floatval($block_info['cash'])-floatval($payment_type['coin_charge'])*2;
                    if ($real_coin<= 0) {
                        $detail['refund_eth_val'] = '0ETH';
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                    }
                }
            }
            if($payment_type['payment_type']==17) {
                $data['is_all'] = 1;
                $block = new VslBlockChainRecordsModel();
                $blocks = new Block();
                $block_info = $block->getInfo(['data_id' => $payment_type['out_trade_no']], '*');
                if ($block_info && $block_info['from_type'] == 8) {
                    if($payment_type['shipping_money'] && $payment_type['shipping_status']>=1){
                        $money = $payment_type['shipping_money'];
                    }
                    $charge = $blocks->eosRefundMoney($money);
                    $real_coin = floatval($block_info['cash'])-$charge;
                    $detail['refund_eos_money'] = $real_coin . 'EOS';
                    if ($real_coin <= 0) {
                        $detail['eth_status'] = true;
                        $detail['eos_status'] = true;
                        $detail['refund_eos_money'] = '0EOS';
                    }
                }
            }
        }
        $this->assign("detail", $detail);
        // 物流公司
        $company = new VslOrderExpressCompanyModel();
        $companyList = $company->getViewList(1,0,'','');
        $this->assign("companyList", $companyList['data']);
        // 查询商家或者店铺地址
        $shop_info = $order_service->getShopReturn($detail['return_id'],$detail['shop_id'], $this->website_id);
        $address = new Address();
        $province_name = $address->getProvinceName($shop_info['province']);
        $city_name = $address->getCityName($shop_info['city']);
        $dictrict_name = $address->getDistrictName($shop_info['district']);
        $shop_info['address'] = $province_name.$city_name.$dictrict_name.$shop_info['address'];
        $this->assign("shop_info", $shop_info);
        if($type==1){
            return view($this->style . "Member/fillReturnMoney");
        }
        return view($this->style . "Member/fillReturnGood");
    }
    /**
     * 申请退款
     */
    public function orderGoodsRefundAskfor()
    {
        $order_id = request()->post('order_id', 0);
        $order_goods_id = request()->post('order_goods_id/a');
        $refund_type = request()->post('refund_type', 1);
        $refund_require_money = request()->post('refund_require_money', 0);
        $refund_reason = request()->post('refund_reason', '');
        $order_service = new OrderService();
        $retval = $order_service->orderGoodsRefundAskfor($order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason,$this->uid);
        return $retval;
    }

    /**
     * 买家退货
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function orderGoodsRefundExpress()
    {
        $order_id = request()->post('order_id', 0);
        $order_goods_id = request()->post('order_goods_id/a', 0);
        $refund_express_company = request()->post('refund_express_company', '');
        $refund_shipping_no = request()->post('refund_shipping_no', 0);
        $order_service = new OrderService();
        $retval = $order_service->orderGoodsReturnGoods($order_id, $order_goods_id, $refund_express_company, $refund_shipping_no);
        return AjaxReturn($retval);
    }
    /**
     * 买家退货和退款
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function orderRefundAsk()
    {
        $order_id = request()->post('order_id', 0);
        $order_goods_id = request()->post('order_goods_id/a');
        $refund_type = request()->post('refund_type', 2);
        $refund_require_money = request()->post('refund_require_money', 0);
        $refund_reason = request()->post('refund_reason', '');
        $order_service = new OrderService();
        $retval = $order_service->orderGoodsRefundAskfor($order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason,$this->uid);
        return $retval;
    }
    /**
     * 设置用户支付密码
     */
    public function setPayPassword()
    {
        $member = new MemberService();
        if (request()->isPost()) {
            $uid = $this->uid;
            $payment_password = request()->post("payment_password", '');
            $res = $member->setUserPaymentPassword($uid, $payment_password);
            return AjaxReturn($res);
        }else{
            $res = $member->getPaymentPassword();
            if($res){
                return view($this->style . "Member/updatePayPassword");
            }else{
                return view($this->style . "Member/setPayPassword");
            }
        }
    }

    /**
     * 修改用户支付密码
     */
    public function updatePayPassword()
    {
        if (request()->isAjax()) {
            $uid = $this->uid;
            $new_payment_password = request()->post("payment_password", '');
            $member = new MemberService();
            $res = $member->updateUserPaymentPassword($uid,$new_payment_password);
            $block = getAddons('blockchain',$this->website_id);
            if($block){
                $real_password = $this->getPassword();
            if($real_password){
                $block = new Block();
                $block->updatePass($this->uid,$real_password,$new_payment_password);
            }
            }
            return AjaxReturn($res);
        }else{
            return view($this->style . "Member/updatePayPassword");
        }
    }
    //获取用户支付明文密码
    public function getPassword(){
        $user = new usermodel();
        $condition['uid'] = $this->uid;
        $user_password = $user->getInfo($condition,'plain_password');
        return $user_password['plain_password'];
    }
    /**
     * 发送短信，修改密码
     */
    public function checkPasswordCode()
    {
        $send_type = request()->post("type", "");
        $send_param = request()->post("send_param", "");
        $shop_id = 0;
        if ($send_type == 'sms') {
            $params = array(
                "mobile" => $send_param,
                "shop_id" => $shop_id,
                "website_id" => $this->website_id
            );
            Session::set("codeMobile1", $send_param,300);
            $result = runhook("Notify", "changePasswordBySms", $params);
            Session::set('forgotVerificationCode', $result['param'],300);
            if ($result['code']==-1) {
                return $result = [
                    'code' => -1,
                    'message' => "发送失败"
                ];
            } else {
                return $result = [
                    'code' => 1,
                    'message' => "发送成功"
                ];
            }
        }
    }
    /**
     * 检测验证码
     */
    public function checkCode()
    {
        if (request()->isPost()) {
            $mobile_code = request()->post('mobile_code', '');
            $verification_code = Session::get('forgotVerificationCode');
            if ($mobile_code == $verification_code && !empty($verification_code)) {
                    $retval = [
                        'code' => 1,
                        'message' => "验证成功"
                    ];
                Session::delete('forgotVerificationCode');
                    return $retval;
            }else{
                $retval = [
                    'code' => -1,
                    'message' => "验证码错误"
                ];
                return $retval;
            }
        }
    }
    /**
     * 找回密码密码重置
     */
    public function setNewPasswordByrMobile()
    {
        $userInfo = request()->post('userInfo', '');
        $password = request()->post('password', '');
        $type = request()->post('type', '');
        $website = new WebSiteModel();
        $account_type = $website->getInfo(['website_id' => $this->website_id], 'account_type')['account_type'];
        if($account_type == 3){
            $mall_port = 4;
        }
        if ($type == "mobile") {
                $codeMobile = Session::get("codeMobile1");
                if ($userInfo != $codeMobile) {
                    return $retval = array(
                        "code" => -1,
                        "message" => "该手机号与验证手机不符"
                    );
                } else {
                    $res = $this->user->updateUserPasswordByMobile($userInfo, $password, $mall_port);
                    Session::delete("codeMobile");
                }
            }
        return AjaxReturn($res);
    }
    /**
     * 发送短信，验证当前手机
     */
    public function checkValidation()
    {
        $send_type = request()->post("type", "");
        $send_param = request()->post("send_param", "");
        $shop_id = 0;
        if ($send_type == 'sms') {
            $params = array(
                "mobile" => $send_param,
                "shop_id" => $shop_id,
                "website_id" => $this->website_id
            );
            $result = runhook("Notify", "bindEmailBySms", $params);
            Session::set('checkVerificationCode', $result['param'],300);
            if ($result['code']==-1) {
                return $result = [
                    'code' => -1,
                    'message' => "发送失败"
                ];
            } else {
                return $result = [
                    'code' => 1,
                    'message' => "发送成功"
                ];
            }
        }
    }
    /**
     * 发送短信，验证当前手机
     */
    public function payPasswordCode()
    {
        $send_param = request()->post("send_param", "");
        $shop_id = 0;
        $params = array(
            "mobile" => $send_param,
            "shop_id" => $shop_id,
            "website_id" => $this->website_id
        );
        $result = runhook("Notify", "changePayPasswordBySms", $params);
        Session::set('checkVerificationCode', $result['param'],300);
        if ($result['code']==-1) {
            return $result = [
                'code' => -1,
                'message' => "发送失败"
            ];
        } else {
            return $result = [
                'code' => 1,
                'message' => "发送成功"
            ];
        }
    }
    /*
     * 以下验证身份
     */
    public function checkMobile()
    {
        if (request()->isPost()) {
            $mobile_code = request()->post('mobile_code', '');
            $verification_code = Session::get('checkVerificationCode');
            if ($mobile_code == $verification_code && !empty($verification_code)) {
                Session::delete('checkVerificationCode');
                    $retval = [
                        'code' => 1,
                        'message' => "验证成功"
                    ];
                    return $retval;
            }else{
                $retval = [
                    'code' => -1,
                    'message' => "验证码错误"
                ];
                return $retval;
            }
        }
    }

    /*
      * 以下重新绑定手机
      */
    public function nbMobile()
    {
        if (request()->isPost()) {
            $member = new MemberService();
            $mobile = request()->post('mobile', '');
            $mobile_code = request()->post('send_param', '');
            $verification_code = Session::get('NbVerificationCode');
            if ($mobile_code == $verification_code && !empty($verification_code)) {
                Session::delete('NbVerificationCode');
                    $res = $member->setMobile($this->uid,$mobile);
                    return AjaxReturn($res);
            }else{
                $retval = [
                    'code' => -1,
                    'message' => "验证码错误"
                ];
                return $retval;
            }
        }
    }
    /**
     * 发送短信，更换手机
     */
    public function checkNewValidation()
    {
        $send_type = request()->post("type", "");
        $send_param = request()->post("send_param", "");
        $shop_id = 0;
        if ($send_type == 'sms') {
            $params = array(
                "mobile" => $send_param,
                "shop_id" => $shop_id,
                "website_id" => $this->website_id
            );
            $result = runhook("Notify", "bindMobileBySms", $params);
            Session::set('NbVerificationCode', $result['param'],300);
            if ($result['code']==-1) {
                return $result = [
                    'code' => -1,
                    'message' => "发送失败"
                ];
            } else {
                return $result = [
                    'code' => 1,
                    'message' => "发送成功"
                ];
            }
        }
    }
    /**
     * 发送邮箱验证码
     */
    public function sendEmailCode()
    {
        //$params['email'] = request()->post('email', '');
        $params['to_email'] = request()->post('email', '');
        $params['shop_id'] = 0;
        $params['website_id'] = $this->website_id;
        $params['expire'] = 5;
        $params['notify_type'] = 'user';
        $params['template_code'] = 'bind_email';
        $result = runhook('Notify', 'emailSend', $params);
        if (empty($result)) {
            return $result = [
                'code' => -1,
                'message' => "发送失败"
            ];
        } else if ($result['code'] < 0) {
            return $result = [
                'code' => $result['code'],
                'message' => $result['message']
            ];
        } else {
            Session::set('emailVerificationCode', $result['param'], $params['expire'] * 60);
            return $result = [
                'code' => 1,
                'message' => "发送成功"
            ];
        }
    }
    /*
     * 绑定邮箱
     */
    public function bindEmailCheck()
    {
        if (request()->isPost()) {
        $email = request()->post('email', '');
        $email_code = request()->post('email_code', '');
        $verification_code = Session::get('emailVerificationCode');
        if ($email_code == $verification_code && !empty($verification_code)) {
            $member = new MemberService();
            $retval = $member->modifyEmail($this->uid, $email);
            if ($retval == 1)
                Session::delete('emailVerificationCode');
            return AjaxReturn($retval);
        }else{
                return array(
                    'code' => -1,
                    'message' => '邮箱验证码输入错误'
                );
            }
        }

    }
    /**
     * 发送邮箱验证码(验证身份)
     */
    public function emailCode()
    {
        //$params['email'] = request()->post('email', '');
        $params['to_email'] = request()->post('email', '');
        $params['shop_id'] = 0;
        $params['website_id'] = $this->website_id;
        $params['notify_type'] = 'user';
        $params['template_code'] = 'register_validate';
        $params['expire'] = 5;
        $result = runhook('Notify', 'emailSend', $params);
        if (empty($result)) {
            return $result = [
                'code' => -1,
                'message' => "发送失败"
            ];
        } else if ($result['code'] < 0) {
            return $result = [
                'code' => $result['code'],
                'message' => $result['message']
            ];
        } else {
            Session::set('emailCheckVerificationCode', $result['param'], $params['expire'] * 60);
            return $result = [
                'code' => 0,
                'message' => "发送成功"
            ];
        }
    }
    /*
    * 以下验证邮箱身份
    */
    public function checkEmailCode()
    {
        if (request()->isPost()) {
            $email_code = request()->post('email_code', '');
            $verification_code = Session::get('emailCheckVerificationCode');
            if ($email_code == $verification_code && !empty($verification_code)) {
                $retval = [
                    'code' => 1,
                    'message' => "验证成功"
                ];
                Session::delete('emailCheckVerificationCode');
                return $retval;
            }else{
                $retval = [
                    'code' => -1,
                    'message' => "验证码错误"
                ];
                return $retval;
            }
        }
    }
    /**
     * 验证邮箱是否已注册
     */
    public function checkEmail()
    {
        // 获取数据库中的用户列表
        $email = request()->post('email', '');
        $user = new UserModel();
        $user_info = $user->getInfo(['user_email'=>$email,'is_member'=>1,'website_id'=>$this->website_id],'uid');
        return $user_info;
    }
    /**
     * 发送邮箱验证码，更换邮箱
     */
    public function checkNewEmailValidation()
    {
        //$params['email'] = request()->post('email', '');
        $params['to_email'] = request()->post('email', '');
        $params['shop_id'] = 0;
        $params['website_id'] = $this->website_id;
        $params['notify_type'] = 'user';
        $params['template_code'] = 'bind_email';
        $params['expire'] = 5;
        $result = runhook('Notify', 'emailSend', $params);
        if (empty($result)) {
            return $result = [
                'code' => -1,
                'message' => "发送失败"
            ];
        } else if ($result['code'] < 0) {
            return $result = [
                'code' => $result['code'],
                'message' => $result['message']
            ];
        } else {
            Session::set('emailCheckNewVerificationCode', $result['param'], $params['expire'] * 60);
            return $result = [
                'code' => 0,
                'message' => "发送成功"
            ];
        }
    }
    /*
      * 以下重新绑定邮箱
      */
    public function nbEmail()
    {
        if (request()->isPost()) {
            $member = new MemberService();
            $email = request()->post('email', '');
            $email_code = request()->post('email_code', '');
            $verification_code = Session::get('emailCheckNewVerificationCode');
            if ($email_code == $verification_code && !empty($verification_code)) {
                Session::delete('emailCheckNewVerificationCode');
                $res = $member->setEmail($this->uid,$email);
                return AjaxReturn($res);
            }else{
                $retval = [
                    'code' => -1,
                    'message' => "验证码错误"
                ];
                return $retval;
            }
        }
    }
/*--------------------------------------------------------------分红中心----------------------------------------------------------------------*/

    /**
     * 分红中心
     */
    public function bonusIndex()
    {
        if($this->globalStatus || $this->areaStatus || $this->teamStatus){
            $member = new MemberService();
            $website_info = $member->getBonusConfig();
            $this->assign('bonus_info', $website_info);
            $this->assign('globalBonusDetailUrl', __URL(addons_url('globalbonus://globalbonus/globalBonusDetail')));
            $this->assign('areaBonusDetailUrl', __URL(addons_url('areabonus://areabonus/areaBonusDetail')));
            $this->assign('teamBonusDetailUrl', __URL(addons_url('teambonus://teambonus/teamBonusDetail')));
            $config = new Config();
            $bonus_set = $config->getBonusSite($this->website_id);
            if($bonus_set){
                $this->assign("title_before", $bonus_set['bonus_name']);
            }
            return view($this->style . "Member/shareBonus");
        }else{
            $this->error('应用不存在');
        }

    }
    /**
     * 制作分红中心二维码
     */
    public function bonusQrcode()
    {
        $text = $this->http.$_SERVER['HTTP_HOST'].'/wap/bonus/centre';
        getQRcodeNotSave($text,'');
    }
    /*
     * 根据地址获取运费
     */
    public function resetShippingFee(){
        $goodsIds = request()->post('goodIds/a', array());
        $nums = request()->post('nums/a', array());
        $address_id = request()->post('address_id', 0);
        if(!$address_id || !$goodsIds){
            return AjaxReturn(0);
        }
        $goods = [];
        foreach($goodsIds as $key => $val){
            $goods[$val]['count'] += $nums[$key];
            $goods[$val]['goods_id'] = $val;
        }
        $addressModel = new VslMemberExpressAddressModel();
        $address = $addressModel->getInfo(['id'=>$address_id]);
        if(!$address){
            return AjaxReturn(0);
        }
        $goodsExpress = new GoodsExpress();
        $shippingFee = 0.00;

        $shippingFeeGet = $goodsExpress->getGoodsExpressTemplate($goods,$address['district'])['totalFee'];
        if($shippingFeeGet){
            $shippingFee = $shippingFeeGet;
        }
        return ['code' => 1, 'data' => $shippingFee];
    }

    /*
     * 验证支付密码
     */
    public function checkPayPw(){
        $payPw = md5(request()->post('payPw', 0));
        $result = $this->user->checkPayPw($this->uid,$payPw);
        return AjaxReturn($result);
    }
    /**
     * 获取分销中心二维码
     */
    public function getDistributionCode(){
        $text = $this->http.$_SERVER['HTTP_HOST'].'/wap/commission/centre';
        getQRcodeNotSave($text,'');
    }

}
