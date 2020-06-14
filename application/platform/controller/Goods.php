<?php

namespace app\platform\controller;
use addons\channel\model\VslChannelLevelModel;
use addons\discount\model\Discount;
use addons\distribution\model\VslDistributorLevelModel;
use addons\distribution\service\Distributor as DistributorService;
use addons\miniprogram\controller\Miniprogram;
use addons\miniprogram\model\WeixinAuthModel;
use data\extend\QRcode;
use data\extend\WchatOpen;
use data\model\AlbumPictureModel;
use data\model\SysAddonsModel;
use data\model\UserModel;
use data\model\VslGoodsAttributeModel;
use data\model\VslGoodsEvaluateModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSpecModel;
use data\model\VslGoodsViewModel;
use data\model\VslMemberLevelModel;
use data\model\WebSiteModel;
use data\service\AddonsConfig;
use data\service\AuthGroup;
use data\service\Express as Express;
use data\service\Goods as GoodsService;
use data\service\GoodsBrand as GoodsBrand;
use data\service\GoodsCategory as GoodsCategory;
use data\service\GoodsGroup as GoodsGroup;
use data\service\Address;
use data\model\VslGoodsTicketModel;
use think\Config;
use data\service\Album;
use data\service\Config as configService;
use addons\store\server\Store as storeService;
use think\db;
use data\service\Member as MemberService;
use data\model\VslGoodsDiscountModel as GoodsDiscount;
use think\Request;
use think\Session;
/**
 * 商品控制器
 */
class Goods extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        header("Content-Type:text/html;charset=utf-8");
    }
    /*
     * 获取商品分类用于选择链接
     */

    public function getCategoryListForLink()
    {
        $goods_category = new GoodsCategory();
        $id = request()->post('id', 0);
        $condition['website_id'] = $this->website_id;
        $condition['pid'] =  $id;
        $goods_category_list = $goods_category->getGoodsCategoryList(1, 0, $condition);
        return $goods_category_list;
    }
    /**
     * 根据商品ID查询单个商品，然后进行编辑操作
     */
    public function GoodsSelect()
    {
        $goods_detail = new GoodsService();
        $goods = $goods_detail->getGoodsDetail($_GET['goodsId'],1);
        return $goods;
    }

    /**
     * 自营商品列表
     */
    public function selfgoodsList()
    {
        $goodservice = new GoodsService();
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $start_date = request()->post('start_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('start_date'));
            $end_date = request()->post('end_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('end_date'));
            $goods_name = request()->post('goods_name', '');
            $goods_code = request()->post('code', '');
            $state = request()->post('state', '');
            $category_id_1 = request()->post('category_id_1', '');
            $category_id_2 = request()->post('category_id_2', '');
            $category_id_3 = request()->post('category_id_3', '');
            $type = request()->post('type', 0);
            $selectGoodsLabelId = request()->post('selectGoodsLabelId', '');
            $supplier_id = request()->post('supplier_id', '');
            $stock_warning = request()->post("stock_warning", 0); // 库存预警
            $is_distribution = request()->post('is_distribution', '');
            $is_bonus = request()->post('is_bonus', '');
            $label_list = request()->post('label_list', '');
            if (! empty($selectGoodsLabelId)) {
                $selectGoodsLabelIdArray = explode(',', $selectGoodsLabelId);
                $selectGoodsLabelIdArray = array_filter($selectGoodsLabelIdArray);
                $str = "FIND_IN_SET(" . $selectGoodsLabelIdArray[0] . ",ng.group_id_array)";
                for ($i = 1; $i < count($selectGoodsLabelIdArray); $i ++) {
                    $str .= "AND FIND_IN_SET(" . $selectGoodsLabelIdArray[$i] . ",ng.group_id_array)";
                }
                $condition[""] = [
                    [
                        "EXP",
                        $str
                    ]
                ];
            }

            if ($start_date != 0 && $end_date != 0) {
                $condition["ng.create_time"] = [
                    [
                        ">",
                        $start_date
                    ],
                    [
                        "<",
                        $end_date
                    ]
                ];
            } elseif ($start_date != 0 && $end_date == 0) {
                $condition["ng.create_time"] = [
                    [
                        ">",
                        $start_date
                    ]
                ];
            } elseif ($start_date == 0 && $end_date != 0) {
                $condition["ng.create_time"] = [
                    [
                        "<",
                        $end_date
                    ]
                ];
            }

            if ($state != "") {
                $condition["ng.state"] = $state;
            }
            if($type){
                switch ($type){
                    case 1:
                        $condition['ng.state'] = 1;
                        break;
                    case 2:
                        $condition['ng.state'] = 0;
                        break;
                    case 3:
                        $condition['ng.stock'] = ['<=','0'];
                        break;
                    case 4:
                        $condition['ng.min_stock_alarm'] = array(
                            "neq",
                            0
                        );
                        $condition['ng.stock'] = array(
                            "exp",
                            "<= ng.min_stock_alarm"
                        );
                        $condition['ng.state'] = 1;
                        break;
                    default :
                        break;
                }
            }
            if (! empty($goods_name)) {
                $condition["ng.goods_name"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
            }
            if (! empty($goods_code)) {
                $condition["ng.code"] = array(
                    "like",
                    "%" . $goods_code . "%"
                );
            }
            if ($category_id_3 != "") {
                $condition["ng.category_id_3"] = $category_id_3;
            } elseif ($category_id_2 != "") {
                $condition["ng.category_id_2"] = $category_id_2;
            } elseif ($category_id_1 != "") {
                $condition["ng.category_id_1"] = $category_id_1;
            }

            if ($supplier_id != '') {
                $condition['ng.supplier_id'] = $supplier_id;
            }

            $condition["ng.shop_id"] = 0;
            $condition["ng.website_id"] = $this->website_id;

            // 发货助手-商品简称一些查询参数
            $goods_type = request()->post('goods_type');
            $category_id = request()->post('category_id');
            if ($goods_type == 2) {
                // 已填写
                $condition['ng.short_name'] = ['NEQ', ''];
            } elseif ($goods_type == 3) {
                // 未填写
                $condition['ng.short_name'] = ['EQ', ''];
            }
            if ($category_id){
                $condition['ng.category_id_1|ng.category_id_2|ng.category_id_3'] = $category_id;
            }
            
            if($is_distribution==1){
                $condition['ng.is_distribution'] = 1;
            }else if($is_distribution==2){
                $condition['ng.is_distribution'] = 0;
            }else if($is_distribution==3){
                $condition['ng.is_distribution'] = 1;
                $condition['ng.distribution_rule'] = 1;
            }
            
            if($is_bonus==1){
                $condition['ng.is_bonus_global|ng.is_bonus_area|ng.is_bonus_team'] = 1;
            }else if($is_bonus==2){
                $condition['ng.is_bonus_global'] = 0;
                $condition['ng.is_bonus_area'] = 0;
                $condition['ng.is_bonus_team'] = 0;
            }else if($is_bonus==3){
                $condition['ng.is_bonus_global|ng.is_bonus_area|ng.is_bonus_team'] = 1;
                $condition['ng.bonus_rule'] = 1;
            }
            
            if($label_list){
                $label_list = explode(',',$label_list);
                foreach ($label_list as $key => $val) {
                    if($val==1)$condition['ng.is_recommend'] = 1;
                    if($val==2)$condition['ng.is_new'] = 1;
                    if($val==3)$condition['ng.is_hot'] = 1;
                    if($val==4)$condition['ng.is_promotion'] = 1;
                    if($val==5)$condition['ng.is_shipping_free'] = 1;
                }
            }
            // 库存预警
//            if ($stock_warning == 1) {
//                $condition['ng.min_stock_alarm'] = array(
//                    "neq",
//                    0
//                );
//                $condition['ng.stock'] = array(
//                    "exp",
//                    "<= ng.min_stock_alarm"
//                );
//            }
            $result = $goodservice->getGoodsList($page_index, $page_size, $condition, [
                'ng.create_time' => 'desc'
            ]);
            // 'ng.sort' => 'desc',

            // 根据商品分组id，查询标签名称
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['promotion_name'] = $goodservice->getGoodsPromotionType($v['promotion_type']);
                if (! empty($v['group_id_array'])) {
                    $goods_group_id = explode(',', $v['group_id_array']);
                    $goods_group_name = '';
                    foreach ($goods_group_id as $key => $val) {
                        $goods_group = new GoodsGroup();
                        $goods_group_info = $goods_group->getGoodsGroupDetail($val);
                        if (! empty($goods_group_info)) {
                            $goods_group_name .= $goods_group_info['group_name'] . ',';
                        }
                    }
                    $goods_group_name = rtrim($goods_group_name, ',');
                    $result["data"][$k]['goods_group_name'] = $goods_group_name;
                }
            }
            return $result;
        } else {
            $type = request()->get('type', '0');
            $this->assign("type", $type);
            $search_info = request()->get('search_info', '');
            $this->assign("search_info", $search_info);
            //判断pc端、小程序是否开启
            $addons_conf = new AddonsConfig();
            $pc_conf = $addons_conf->getAddonsConfig('pcport', $this->website_id);
            $is_minipro = getAddons('miniprogram', $this->website_id);
            if($is_minipro){
                $weixin_auth = new WeixinAuthModel();
                $new_auth_state = $weixin_auth->getInfo(['website_id' => $this->website_id], 'new_auth_state')['new_auth_state'];
                if(isset($new_auth_state) && $new_auth_state === 0){
                    $is_minipro = 1;
                }else{
                    $is_minipro = 0;
                }
            }
            $website_mdl = new WebSiteModel();
            //查看移动端的状态
            $wap_status = $website_mdl->getInfo(['website_id' => $this->website_id], 'wap_status')['wap_status'];
            $this->assign('wap_status', $wap_status);
            $this->assign('is_pc_use', $pc_conf['is_use']);
            $this->assign('is_minipro', $is_minipro);
            $this->assign("website_id", $this->website_id);
            return view($this->style . "Goods/selfGoodsList");
        }
    }
    public function getGoodsDetailQr(){
        $goods_id = request()->get('goods_id', 0);
        $coupon_type_id = request()->get('coupon_type_id', 0);
        $gift_voucher_id = request()->get('gift_voucher_id', 0);
        $voucher_package_id = request()->get('voucher_package_id', 0);
        $wheelsurf_id = request()->get('wheelsurf_id', 0);//大转盘
        $smash_egg_id = request()->get('smash_egg_id', 0);//砸金蛋
        $scratch_card_id = request()->get('scratch_card_id', 0);//刮刮乐
        $qr_type = request()->get('qr_type', 0);
        $wap_path = request()->get('wap_path', '');
        $mp_page = request()->get('mp_path', '');
        //直播id
        $live_id = request()->get('live_id', '');
        //主播id
        $anchor_id = request()->get('anchor_id', '');
        //小程序后台url参数获取
        $room_id = request()->get('room_id', 0);
        $type = request()->get('type', 0);
        $website_model = new WebSiteModel();
        $website_info = $website_model::get(['website_id' => $this->website_id]);
        $is_ssl = \think\Request::instance()->isSsl();
        $ssl = $is_ssl ? 'https://': 'http://';
        if ($website_info['realm_ip']) {
            $domain_name = $ssl . $website_info['realm_ip'];
        } else {
            $ip = top_domain($_SERVER['HTTP_HOST']);
            $domain_name = $ssl . top_domain($website_info['realm_two_ip']) .'.'. $ip;
        }
        ob_start();
        if($goods_id){
            $id = $goods_id;
        }elseif($coupon_type_id){
            $id = $coupon_type_id;
        }elseif($gift_voucher_id){//礼品券
            $id = $gift_voucher_id;
        }elseif($voucher_package_id){//券包
            $id = $voucher_package_id;
        }elseif($wheelsurf_id){//大转盘
            $id = $wheelsurf_id;
        }elseif($smash_egg_id){//砸金蛋
            $id = $smash_egg_id;
        }elseif($scratch_card_id){//刮刮乐
            $id = $scratch_card_id;
        } elseif($live_id) {
            $id = $live_id;
        }
        if($anchor_id) {
            $id2 = $anchor_id;
        }
        if($qr_type == 1){
            //拼接出手机端的商品详情链接
            $wap_url = $domain_name.$wap_path.$id;
            QRcode::png($wap_url, false, 'L', '10', 0, false);
            $obcode = ob_get_clean();
            $code = imagecreatefromstring($obcode);
            header("Content-type: image/png");
            imagepng($code);
        }elseif($qr_type == 3){
            //拼接出小程序的直播间二维码
            $mp_url = $mp_page;
            $params = [
                'scene' => -1,
                'page' => $mp_url,
            ];
            $wx_auth_model = new WeixinAuthModel();
            $wchat_open = new WchatOpen($this->website_id);
            $mp_info = $wx_auth_model->getInfo(['website_id' => $this->website_id], 'authorizer_access_token');
            if (empty($mp_info)) {
                return json(['code' => -1, 'message' => '参数错误！']);
            }
            ob_get_clean();
            $imgRes = $wchat_open->getSunCodeApi($mp_info['authorizer_access_token'], $params, 2);
            $code = imagecreatefromstring($imgRes);
            header("Content-type: image/png");
            imagepng($code);exit;
        } else {
            //拼接出小程序的商品详情链接
//            $mp_page = 'pages/goods/detail/index';
            $params = [
                'scene' => '-1_'.$id,
                'page' => $mp_page,
                'width' => 280
            ];
            if($id2){
                $params['scene'] =  '-1_' . $id. '_' . $id2;
            }
            $wx_auth_model = new WeixinAuthModel();
            $wchat_open = new WchatOpen($this->website_id);
            $mp_info = $wx_auth_model->getInfo(['website_id' => $this->website_id],'authorizer_access_token');
            if (empty($mp_info)) {
                return json(['code' => -1, 'message' => '参数错误！']);
            }
            ob_get_clean();
            $imgRes = $wchat_open->getSunCodeApi($mp_info['authorizer_access_token'], $params, 2);
            $code = imagecreatefromstring($imgRes);
            header("Content-type: image/png");
            imagepng($code);exit;
        }
    }

    /**
     * 入住店商品
     */
    public function shopgoodslist()
    {
        $goodservice = new GoodsService();
        if (request()->isAjax()) {

            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $start_date = request()->post('start_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('start_date'));
            $end_date = request()->post('end_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('end_date'));
            $goods_name = request()->post('goods_name', '');
            $goods_code = request()->post('code', '');
            $state = request()->post('state', '');
            $category_id_1 = request()->post('category_id_1', '');
            $category_id_2 = request()->post('category_id_2', '');
            $category_id_3 = request()->post('category_id_3', '');
            $type = request()->post('type', 0);
            $selectGoodsLabelId = request()->post('selectGoodsLabelId', '');
            $supplier_id = request()->post('supplier_id', '');
            $shop_name = request()->post('shop_name', '');
            $stock_warning = request()->post("stock_warning", 0); // 库存预警

            if (! empty($selectGoodsLabelId)) {
                $selectGoodsLabelIdArray = explode(',', $selectGoodsLabelId);
                $selectGoodsLabelIdArray = array_filter($selectGoodsLabelIdArray);
                $str = "FIND_IN_SET(" . $selectGoodsLabelIdArray[0] . ",ng.group_id_array)";
                for ($i = 1; $i < count($selectGoodsLabelIdArray); $i ++) {
                    $str .= "AND FIND_IN_SET(" . $selectGoodsLabelIdArray[$i] . ",ng.group_id_array)";
                }
                $condition[""] = [
                    [
                        "EXP",
                        $str
                    ]
                ];
            }

            if ($start_date != 0 && $end_date != 0) {
                $condition["ng.create_time"] = [
                    [
                        ">",
                        $start_date
                    ],
                    [
                        "<",
                        $end_date
                    ]
                ];
            } elseif ($start_date != 0 && $end_date == 0) {
                $condition["ng.create_time"] = [
                    [
                        ">",
                        $start_date
                    ]
                ];
            } elseif ($start_date == 0 && $end_date != 0) {
                $condition["ng.create_time"] = [
                    [
                        "<",
                        $end_date
                    ]
                ];
            }

            if ($state != "") {
                $condition["ng.state"] = $state;
            }
            if($type){
                switch ($type){
                    case 1:
                        $condition['ng.state'] = 1;
                        break;
                    case 2:
                        $condition['ng.state'] = 0;
                        break;
                    case 3:
                        $condition['ng.stock'] = ['<=','0'];
                        break;
                    case 4:
                        $condition['ng.min_stock_alarm'] = array(
                            "neq",
                            0
                        );
                        $condition['ng.stock'] = array(
                            "exp",
                            "<= ng.min_stock_alarm"
                        );
                        $condition['ng.state'] = 1;
                        break;
                    default :
                        break;
                }
            }
            if (! empty($goods_name)) {
                $condition["ng.goods_name"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
            }
            if (! empty($goods_code)) {
                $condition["ng.code"] = array(
                    "like",
                    "%" . $goods_code . "%"
                );
            }
            if ($category_id_3 != "") {
                $condition["ng.category_id_3"] = $category_id_3;
            } elseif ($category_id_2 != "") {
                $condition["ng.category_id_2"] = $category_id_2;
            } elseif ($category_id_1 != "") {
                $condition["ng.category_id_1"] = $category_id_1;
            }

            if ($supplier_id != '') {
                $condition['ng.supplier_id'] = $supplier_id;
            }
            if($shop_name!=''){
                $condition['nss.shop_name'] = $shop_name;
            }

            $condition["ng.shop_id"] = ['neq',0];
            $condition["ng.website_id"] = $this->website_id;

            // 库存预警
//            if ($stock_warning == 1) {
//                $condition['ng.min_stock_alarm'] = array(
//                    "neq",
//                    0
//                );
//                $condition['ng.stock'] = array(
//                    "exp",
//                    "<= ng.min_stock_alarm"
//                );
//            }
            $result = $goodservice->getGoodsList($page_index, $page_size, $condition, [
                'ng.create_time' => 'desc'
            ]);
            // 'ng.sort' => 'desc',

            // 根据商品分组id，查询标签名称
            foreach ($result['data'] as $k => $v) {
                if (! empty($v['group_id_array'])) {
                    $goods_group_id = explode(',', $v['group_id_array']);
                    $goods_group_name = '';
                    foreach ($goods_group_id as $key => $val) {
                        $goods_group = new GoodsGroup();
                        $goods_group_info = $goods_group->getGoodsGroupDetail($val);
                        if (! empty($goods_group_info)) {
                            $goods_group_name .= $goods_group_info['group_name'] . ',';
                        }
                    }
                    $goods_group_name = rtrim($goods_group_name, ',');
                    $result["data"][$k]['goods_group_name'] = $goods_group_name;
                }
            }
            return $result;
        } else {
            $goods_group = new GoodsGroup();
            $groupList = $goods_group->getGoodsGroupList(1, 0, [
                'shop_id' => ['neq',0],
                'website_id' => $this->website_id,
                'pid' => 0
            ]);
            if (! empty($groupList['data'])) {
                foreach ($groupList['data'] as $k => $v) {
                    $v['sub_list'] = $goods_group->getGoodsGroupList(1, 0, 'pid = ' . $v['group_id']);
                }
            }
            $type = request()->get('type','0');
            $this->assign("goods_group", $groupList['data']);
            $this->assign("type", $type);
            $search_info = request()->get('search_info', '');
            $this->assign("search_info", $search_info);
            // 查找一级商品分类
            $goodsCategory = new GoodsCategory();
            $oneGoodsCategory = $goodsCategory->getGoodsCategoryListByParentId(0);
            $this->assign("oneGoodsCategory", $oneGoodsCategory);
            // 上下架
            $state = request()->get("state", "");
            $this->assign("state", $state);
            // 库存预警
            $stock_warning = request()->get("stock_warning", 0);
            $this->assign("stock_warning", $stock_warning);
            //类型
            $type= $_REQUEST['type']?$_REQUEST['type']:'';
            $this->assign("type", $type);
            return view($this->style . "Goods/settledGoodsList");
        }
    }

    public function getCategoryByParentAjax()
    {
        if (request()->isAjax()) {
            $parentId = request()->post("parentId", '');
            $goodsCategory = new GoodsCategory();
            $res = $goodsCategory->getGoodsCategoryListByParentId($parentId);
            return $res;
        }
    }

    /**
     *
     * 功能说明：通过ajax来的得到页面的数据
     */
    public function SelectCateGetData()
    {
        $goods_category_id = request()->post("goods_category_id", ''); // 商品类目用
        $goods_category_name = request()->post("goods_category_name", ''); // 商品类目名称显示用
        $goods_attr_id = request()->post("goods_attr_id", ''); // 关联商品类型ID
        $quick = request()->post("goods_category_quick", ''); // JSON格式
        setcookie("goods_category_id", $goods_category_id, time() + 3600 * 24);
        setcookie("goods_category_name", $goods_category_name, time() + 3600 * 24);
        setcookie("goods_attr_id", $goods_attr_id, time() + 3600 * 24);
        setcookie("goods_category_quick", $quick, time() + 3600 * 24);
    }

    /**
     * 获取用户快速选择商品
     */
    public function getQuickGoods()
    {
        if (isset($_COOKIE["goods_category_quick"])) {
            return $_COOKIE["goods_category_quick"];
        } else {
            return - 1;
        }
    }

    /**
     * 选择商品
     */
    public function selectGoodsList()
    {
        if (request()->post()) {
            $goodservice = new GoodsService();
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $goods_name = request()->post('goods_name', '');
            if (!empty($goods_name)) {
                $condition["ng.goods_name"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
            }
            if($this->shopStatus==0){
                $condition['ng.shop_id'] = 0;
            }
            $condition['ng.state'] = 1;
            $condition['ng.website_id'] = $this->website_id;
            $result = $goodservice->getGoodsViewList($page_index, $page_size, $condition, [
                'ng.create_time' => 'desc'
            ]);
            return $result;
        }else{
            return view($this->style . "Goods/selectGoods");
        }
    }
    /**
     * 选择多个商品
     */
    public function selectNumGoodsList()
    {
        if (request()->post()) {
            $goodservice = new GoodsService();
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $goods_name = request()->post('goods_name', '');
            $ungoodsid = request()->post('ungoodsid', '');
            if (!empty($goods_name)) {
                $condition["ng.goods_name"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
            }
            if($ungoodsid){
                $condition['ng.goods_id'] = ['not in',$ungoodsid];
            }
            $condition['ng.shop_id'] = 0;
            $condition['ng.website_id'] = $this->website_id;
            $result = $goodservice->getGoodsViewList($page_index, $page_size, $condition, [
                'ng.create_time' => 'desc'
            ]);
            return $result;
        }else{
            $goodsid =  request()->get("goodsid", '');
            $ungoodsid =  request()->get("ungoodsid", '');
            $this->assign('goodsid',$goodsid);
            $this->assign('ungoodsid',$ungoodsid);
            return view($this->style . "Goods/selectNumGoods");
        }
    }
    public function selectNumGoodsInfo()
    {
        if (request()->post()) {
            $goods = new VslGoodsViewModel();
            $goods_id = request()->post('goods_id', '');
            $condition['ng.website_id'] = $this->website_id;
            $condition['ng.goods_id'] = ['in',explode(',',$goods_id)];
            if($this->shopStatus==0){
                $condition['ng.shop_id'] = 0;
            }
            $result = $goods->getGoodsViewQuery(1,0,$condition,'');
            return $result;
        }
    }
    /**
     * 发布商品
     */
    public function addGoods()
    {
        $goods_group = new GoodsGroup();
        $express = new Express();
        $goods = new GoodsService();
        $goodscate = new GoodsCategory();
        $goodsId = isset($_GET["goods_id"]) ? $_GET["goods_id"] : 0;
        $groupList = $goods_group->getGoodsGroupList(1, 0, [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
        ]);
        $oneGoodsCategory = $goodscate->getGoodsCategoryListByParentId(0);
        $this->assign("oneGoodsCategory", $oneGoodsCategory);

        //判断O2O应用是否存在
        if (getAddons('store', $this->website_id)) {
            $this->assign('storeListUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/storeList')));
            $this->assign('store',1);
        }else{
            $this->assign('store',0);
        }
        $goods_attribute_list = $goods->getAttributeServiceList(1, 0, ['website_id' => $this->website_id, 'is_use' => 1]);
        $this->assign("goods_attribute_list", $goods_attribute_list['data']); // 商品类型
        $shi_condition['website_id'] = $this->website_id;
        $shi_condition['shop_id'] = 0;
        $shi_condition['is_enabled'] = 1;
        $this->assign("shipping_list", $express->shippingFeeQuery($shi_condition)); // 物流
        $this->assign("group_list", $groupList['data']); // 分组
        $this->assign("goods_id", $goodsId);
        $this->assign("shop_type", 2);
        if($this->distributionStatus==1){
            $dis_level = new VslDistributorLevelModel();
            $level_ids = $dis_level->Query(['website_id' => $this->website_id],'id');
            $this->assign("level_ids", implode(',',$level_ids));
            $level_list = $dis_level->getQuery(['website_id' => $this->website_id],'level_name,id','id asc');
            $this->assign("level_list", objToArr($level_list));
        }
        // 会员等级
        $memberService = new MemberService();
        $memberLevelRes = $memberService->getMemberLevelList(1, 0, ['website_id' => $this->website_id, 'shop_id'=>$this->instance_id ], 'level_id ASC');
//        p($memberLevelRes['data']);exit;
        $this->assign('member_level', $memberLevelRes['data']);
        // 分销商等级
        $distributor = new DistributorService();
        $distributorRes = $distributor->getDistributorLevelList(1, 0, ['website_id'=>$this->website_id], 'id ASC');
        $this->assign('distributor_level', $distributorRes['data']);
        // 会员分组
//        $userGroupRes = $userGroup->getSystemUserGroupList(1, 0, ['website_id' => $this->website_id, 'instance_id' => $this->instance_id]);
        $member = new MemberService();
        $userTabRes = $member->getMemberGroupList(1, 0, ['website_id' => $this->website_id], 'group_id desc');
        $this->assign('user_group_level', $userTabRes['data']);
        //判断是否有微商中心应用
        if(getAddons('channel', $this->website_id)){
            //获取所有渠道商等级
            $channel_level_model = new VslChannelLevelModel();
            $channel_level_list = $channel_level_model->getQuery(['website_id' => $this->website_id],'channel_grade_id,channel_grade_name','channel_grade_id ASC');
            $this->assign('channel_level_list', $channel_level_list);
        }
        //判断是否有知识付费应用
        //知识付费的module_id
        $addons_model = new SysAddonsModel();
        $knowledgepayment_module_id = $addons_model->Query(['name' => 'knowledgepayment'],'module_id')[0];
        if(empty($knowledgepayment_module_id)) {
            $this->assign('have_knowledgepayment', 0);
        }else{
            //当前用户的权限组
            $model = Request::instance()->module();
            $auth_modules = explode(',',Session::get($model.'module_id_array'));
            if(!in_array($knowledgepayment_module_id,$auth_modules)) {
            $this->assign('have_knowledgepayment', 0);
            }else{
                $this->assign('have_knowledgepayment', 1);
            }
        }
        //编辑
        if ($goodsId > 0) {
            $this->assign("goodsid", $goodsId);
            $goods_info = $goods->getGoodsDetail($goodsId,1); 
            if(!$goods_info){
                $this->error('商品不存在');
            }
            $goods_info['sku_list'] = json_encode($goods_info['sku_list']);
            $goods_info['goods_group_list'] = json_encode($goods_info['goods_group_list']);
            $goods_info['img_list'] = json_encode($goods_info['img_list']);
            $goods_info['goods_attribute_list'] = json_encode($goods_info['goods_attribute_list']);
            if (trim($goods_info['goods_spec_format']) != "") {
                    
                    $goods_spec_array = json_decode($goods_info['goods_spec_format'], true);
                    $album = new Album();
                    if($goods_spec_array){
                        foreach ($goods_spec_array as $k => $v) {
                            foreach ($v["value"] as $t => $m) {
                                if (is_numeric($m["spec_value_data"]) && $v["show_type"] == 3) {
                                    $picture_detail = $album->getAlubmPictureDetail([
                                        "pic_id" => $m["spec_value_data"]
                                    ]);
                                    if (!empty($picture_detail)) {
                                        $goods_spec_array[$k]["value"][$t]["spec_value_data_src"] = $picture_detail["pic_cover_micro"];
                                    }
                                } elseif (!is_numeric($m["spec_value_data"]) && $v["show_type"] == 3) {
                                    $goods_spec_array[$k]["value"][$t]["spec_value_data_src"] = $m["spec_value_data"];
                                }
                            }
                        }
                    }
                    $goods_spec_format = json_encode($goods_spec_array, JSON_UNESCAPED_UNICODE);
                    
                }
            $goods_info['goods_spec_format'] = $goods_spec_format;
            if ($goods_info["group_id_array"] == "") {
                $this->assign("edit_group_array", array());
            } else {
                $this->assign("edit_group_array", explode(",", $goods_info["group_id_array"]));
            }
            //核销信息
            $goods_info['invalid_time'] = date('Y-m-d',$goods_info['invalid_time']);
            $goods_info['store_list'] = explode(',',$goods_info['store_list']);
            if(empty($goods_info['store_list'])){
                $goods_info['store_list'] = [];
            }else{
                $store_list = [];
                foreach ($goods_info['store_list'] as $k => $v) {
                    $store_list[$v] = $v;
                }
                $goods_info['store_list'] = $store_list;
            }
            if($goods_info['goods_type']==0){
                //卡券信息
                $goods_tickets = new VslGoodsTicketModel();
                $ticket_info = $goods_tickets->alias('a')->join('sys_album_picture b','a.card_pic_id=b.pic_id','left')->field('a.*,b.pic_cover')->where(['a.goods_id'=>$goodsId])->find();
                if($ticket_info){
                    $ticket_info['pic_cover'] = __IMG($ticket_info['pic_cover']);
                    $ticket_info['store_service'] = explode(',',$ticket_info['store_service']);
                }
                $this->assign('ticket_info',$ticket_info);
            }
            if($goods_info['distribution_rule_val']){
                $goods_info['distribution_rule_val'] = json_decode(htmlspecialchars_decode($goods_info['distribution_rule_val']),true);
            }
            //海报内容
            if($goods_info['poster_data']){
                $goods_info['poster_data'] = json_decode(htmlspecialchars_decode($goods_info['poster_data']),true);
            }
             //复购处理

             if ($goods_info['buyagain_distribution_val']) {
                $goods_info['buyagain_distribution_val'] = json_decode(htmlspecialchars_decode($goods_info['buyagain_distribution_val']), true);
            }
            if($goods_info['bonus_rule_val']){
                $goods_info['bonus_rule_val'] = json_decode(htmlspecialchars_decode($goods_info['bonus_rule_val']),true);
            }
            // 查询商品折扣
            $goodDiscount = new GoodsDiscount();
            $d_condition = [
                'goods_id' => $goodsId,
                'type' => 1,
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id
            ];
            $discountRes = $goodDiscount->getInfo($d_condition);
            $good_discount = json_decode($discountRes['value'], true);
            // 新增等级合并
            if ($good_discount['distributor_obj']['d_level_data'] && $distributorRes['data']) {
                $distributor_temp = [];
                foreach($distributorRes['data'] as $v) {
                    $distributor_temp[$v->id] = [
                        'name' => $v['level_name'],
                        'val' => ''
                    ];
                }
                $good_discount['distributor_obj']['d_level_data'] += $distributor_temp;
            }
            if ($good_discount['user_obj']['u_level_data'] && $memberLevelRes['data']) {
                $member_temp = [];
                foreach($memberLevelRes['data'] as $v) {
                    $member_temp[$v->level_id] = [
                        'name' => $v['level_name'],
                        'val' => ''
                    ];
                }
                $good_discount['user_obj']['u_level_data'] += $member_temp;
            }
            //知识付费商品的付费内容
            if ($goods_info['goods_type'] == 4) {
                $goodservice = new GoodsService();
                $payment_content = $goodservice->getKnowledgePaymentList($goodsId);
                $this->assign("payment_content", $payment_content);
            }
			$goods_info['description'] = str_replace(PHP_EOL, '', $goods_info['description']);
            $goods_info['description'] = str_replace("'", "", $goods_info['description']);
            $this->assign("good_discount", $good_discount);
            $this->assign("goods_info", $goods_info);
            return view($this->style . "Goods/updateGoods");
        } else {
            return view($this->style . 'Goods/addGoods');
        }
    }

    //图片空间列表
    public function pic_space(){
        return view($this->style .'common/pictureDialog');
    }

    //图片空间列表
    public function video_space(){
        return view($this->style .'common/videoDialog');
    }

    //图片空间列表
    public function picvideo_space()
    {
        return view($this->style . 'common/pictureVideoDialog');
    }
    //商品分类关联规格
    public function specDialog(){
        $goods = new GoodsService();
        $attr_id = isset($_GET['attr_id']) ? $_GET['attr_id'] : '';
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['is_visible'] = 1;
        $goodsguige = $goods->getGoodsSpecList(1, 0, $condition, 'sort ASC');
        $this->assign('goodsguige', $goodsguige);
        if (request()->isPost()) {
            $attr_id = isset($_POST['attr_id']) ? $_POST['attr_id'] : '';
            $spec_id_array = isset($_POST['spec_id']) ? $_POST['spec_id'] : '';
            $res = $goods->updateAttributeSpecService($attr_id, $spec_id_array);
            if(!$res){
                return AjaxReturn($res);
            }
            $spec_array = explode(',',$spec_id_array);
            foreach ($spec_array as $k=>$v){
                //检测当前绑定的规格是否有绑定品类
                $spec_model = new VslGoodsSpecModel();
                $goods_attr_id = $spec_model->getInfo(['spec_id'=>$v]);
                if(empty($goods_attr_id['goods_attr_id']) || $goods_attr_id['goods_attr_id']==0){
                    $spec_model->save(['goods_attr_id'=>$attr_id],['spec_id'=>$v]);
                }
            }
            $this->addUserLogByParam("修改商品品类关联规格",$res);
            return AjaxReturn($res);
        }
        //属性关联规格
        $attribute_detail = $goods->getAttributeServiceDetail($attr_id);
        $spec_array = explode(',',$attribute_detail['spec_id_array']);
        $this->assign('spec_array', $spec_array);
        return view($this->style .'Goods/specDialog');
    }
    //商品分类关联品牌
    public function brandDialog(){
        $goods = new GoodsService();
        if (request()->isPost()) {
            $attr_id = isset($_POST['attr_id']) ? $_POST['attr_id'] : '';
            $brand_id_array = isset($_POST['brand_id']) ? $_POST['brand_id'] : '';
            $res = $goods->updateAttributeBrandService($attr_id, $brand_id_array);
            if($res){
                $this->addUserLogByParam("修改品类关联品牌",$res);
            }
            return AjaxReturn($res);
        }
        $attr_id = isset($_GET['attr_id']) ? $_GET['attr_id'] : '';
        $brand_condition['website_id'] = $this->website_id;
        $brand_condition['brand_recommend'] = 1;
        $brand_list = $goods->getAllBrand(1, 0, $brand_condition, 'sort ASC');
        $this->assign('brand_list', json_encode($brand_list['data']));
        $this->assign('brand_array',$brand_list);
        $attribute_detail = $goods->getAttributeServiceDetail($attr_id);
        $brand_id = explode(',',$attribute_detail['brand_id_array']);
        $this->assign('brand_id', $brand_id);
        return view($this->style .'Goods/brandDialog');
    }
    //商品分类属性列表
    public function attribututeListDialog(){
        $goods = new GoodsService();
        if (request()->isPost()) {
            $attr_id = isset($_POST['attr_id']) ? $_POST['attr_id'] : '';
            $value_string = isset($_POST['data_obj_str']) ? $_POST['data_obj_str'] : '';
            $res = $goods->updateAttributeValueService($attr_id, $value_string);
            if($res){
                $this->addUserLogByParam("修改品类属性",$res);
            }
            return AjaxReturn($res);
        }
        $attr_id = isset($_GET['attr_id']) ? $_GET['attr_id'] : '';
        $attribute_detail = $goods->getAttributeServiceDetail($attr_id);
        $this->assign('info', $attribute_detail);
        return view($this->style .'Goods/attribututeListDialog');
    }
    //商品分类关联分类
    public function associateCategoryDialog(){
        $goods = new GoodsService();
        if (request()->isPost()) {
            $attr_id = isset($_POST['attr_id']) ? $_POST['attr_id'] : '';
            $cate_obj_arr = isset($_POST['cate_obj_arr']) ? $_POST['cate_obj_arr'] : '';
            $attr_name = isset($_POST['attr_name']) ? $_POST['attr_name'] : '';
            $res = $goods->updateAttributeCateService($attr_id, $attr_name, $cate_obj_arr);
            if($res){
                $this->addUserLogByParam("修改品类关联分类",$res);
            }
            return AjaxReturn($res);
        }
        $category = new GoodsCategory();
        $attr_id = isset($_GET['attr_id']) ? $_GET['attr_id'] : '';
        $goodsCategoryList = $category->getCategoryTreeUseInAdmin();
        $this->assign('goodsCategoryList',$goodsCategoryList);
        $this->assign('attr_id',$attr_id);
        return view($this->style .'Goods/associateCategoryDialog');
    }
    /**
     * 根据商品类型id查询，商品规格信息
     *
     */
    public function getGoodsSpecListByAttrId()
    {
        $goods = new GoodsService();
        $condition["attr_id"] = request()->post("attr_id", 0);
        $condition['is_use'] = 1;
        $list = $goods->getGoodsAttrSpecQuery($condition);
        return $list;
    }

    /**
     * 根据商品类型id查询，商品规格信息
     *
     */
    public function get_shipping_type(){

        $shipping_fee_id = $_REQUEST['attr_id'];
        $sql = "select `shipping_fee_id`,`weight_is_use`,`volume_is_use`,`bynum_is_use` from `vsl_order_shipping_fee` where shipping_fee_id = ".$shipping_fee_id;
        $data = Db::query($sql);
        return json_encode($data);
    }

    /**
     *
     * 功能说明：通过节点的ID查询得到某个节点下的子集
     */
    public function getChildCateGory()
    {
        $categoryID = $_POST["categoryID"];
        $goods_category = new GoodsCategory();
        $list = $goods_category->getGoodsCategoryListByParentId($categoryID);
        return $list;
    }

    /**
     * 修改商品
     */
    public function updataGoods()
    {
        return view($this->style . "Goods/addGoods");
    }

    /**
     * 删除商品
     */
    public function deleteGoods()
    {
        $goods_ids = request()->post('goods_ids');
        $goodservice = new GoodsService();
        $retval = $goodservice->deleteGoods($goods_ids);
        $this->addUserLogByParam("删除商品",$goods_ids);
        return AjaxReturn($retval);
    }

    /**
     * 删除回收站商品
     */
    public function emptyDeleteGoods()
    {
        $goods_ids = $_REQUEST['goodsId'];
        $goodsservice = new GoodsService();
        $goods_string = '';
        if(is_array($goods_ids)){
            foreach ($goods_ids as $k=>$v){
                $goods_string .= $v.',';
            }
            $goods_ids = substr($goods_string,0,-1);
        }
        $res = $goodsservice->deleteRecycleGoods($goods_ids);
        $this->addUserLogByParam("删除回收站商品",$goods_ids);
        return AjaxReturn($res);
    }

    /**
     * 商品品牌列表
     */
    public function goodsBrandList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $search_text = request()->post("search_text", "");
            $goodsbrand = new GoodsBrand();
            $result = $goodsbrand->getGoodsBrandList($page_index, $page_size, [
                'website_id' => $this->website_id,
                'brand_name' => array(
                    "like",
                    "%" . $search_text . "%"
                )
            ], "brand_initial asc");
            $goodsCatefory = new GoodsCategory();
            foreach ($result['data'] as $v) {
                $v['category_id_1_name'] = ! empty($goodsCatefory->getName($v['category_id_1'])['category_name']) ? $goodsCatefory->getName($v['category_id_1'])['category_name'] : "";
                $v['category_id_2_name'] = ! empty($goodsCatefory->getName($v['category_id_2'])['category_name']) ? $goodsCatefory->getName($v['category_id_2'])['category_name'] : "";
                $v['category_id_3_name'] = ! empty($goodsCatefory->getName($v['category_id_3'])['category_name']) ? $goodsCatefory->getName($v['category_id_3'])['category_name'] : "";
            }
            return $result;
        } else {
            return view($this->style . "Goods/goodsBrandList");
        }
    }

    /**
     * 添加商品品牌
     */
    public function addGoodsBrand()
    {
        if (request()->isAjax()) {
            $goodsbrand = new GoodsBrand();
            $shop_id = $this->instance_id;
            $brand_name = isset($_POST['brand_name']) ? $_POST['brand_name'] : '';
            $brand_initial = isset($_POST['brand_initial']) ? $_POST['brand_initial'] : '';
            $brand_pic = isset($_POST['brand_pic']) ? $_POST['brand_pic'] : '';
            $brand_recommend = isset($_POST['brand_recommend']) ? $_POST['brand_recommend'] : 0;
            $category_name = isset($_POST['category_name']) ? $_POST['category_name'] : '';
            $category_id_1 = isset($_POST['category_id_1']) ? $_POST['category_id_1'] : 0;
            $category_id_2 = isset($_POST['category_id_2']) ? $_POST['category_id_2'] : 0;
            $category_id_3 = isset($_POST['category_id_3']) ? $_POST['category_id_3'] : 0;
            $sort = isset($_POST['sort']) ? $_POST['sort'] : 1;
            $brand_category_name = '';
            $category_id_array = 1;
            $brand_ads = isset($_POST['brand_ads']) ? $_POST['brand_ads'] : '';
            $res = $goodsbrand->addOrUpdateGoodsBrand('', $shop_id, $brand_name, $brand_initial, '', $brand_pic, $brand_recommend, $sort, $brand_category_name, $category_id_array, $brand_ads, $category_name, $category_id_1, $category_id_2, $category_id_3);
            $this->addUserLogByParam("添加商品品牌",$res);
            return AjaxReturn($res);
        } else {
            $goodscategory = new GoodsCategory();
            $list = $goodscategory->getGoodsCategoryListByParentId(0);
            $this->assign('goods_category_list', $list);
            return view($this->style . "Goods/addGoodsBrand");
        }
    }

    /**
     * 选择商品分类
     */
    function changeCategory()
    {
        $pid = isset($_POST['pid']) ? $_POST['pid'] : 0;
        $list = array();
        if ($pid > 0) {
            $goodscategory = new GoodsCategory();
            $list = $goodscategory->getGoodsCategoryListByParentId($pid);
        }
        return $list;
    }

    /**
     * 刷新商品分类
     */
    function refresh_category()
    {

        $goodscategory = new GoodsCategory();
        $list = $goodscategory->getGoodsCategoryListByParentId(0);
        return $list;
    }
    /**
     * 修改商品品牌
     */
    public function updateGoodsBrand()
    {
        $goodsbrand = new GoodsBrand();
        if (request()->isAjax()) {
            $brand_id = isset($_POST['brand_id']) ? ($_POST['brand_id']) : "";
            $brand_name = isset($_POST['brand_name']) ? $_POST['brand_name'] : '';
            $brand_initial = isset($_POST['brand_initial']) ? $_POST['brand_initial'] : '';
            $brand_pic = isset($_POST['brand_pic']) ? $_POST['brand_pic'] : '';
            $brand_recommend = isset($_POST['brand_recommend']) ? $_POST['brand_recommend'] : 0;
            $category_name = isset($_POST['category_name']) ? $_POST['category_name'] : '';
            $category_id_1 = isset($_POST['category_id_1']) ? $_POST['category_id_1'] : '';
            $category_id_2 = isset($_POST['category_id_2']) ? $_POST['category_id_2'] : '';
            $category_id_3 = isset($_POST['category_id_3']) ? $_POST['category_id_3'] : '';
            $sort =  isset($_POST['sort']) ? $_POST['sort'] : '';
            $brand_category_name = '';
            $category_id_array = 1;
            $shopid = $this->instance_id;
            $brand_ads = isset($_POST['brand_ads']) ? $_POST['brand_ads'] : '';
            $res = $goodsbrand->addOrUpdateGoodsBrand($brand_id, $shopid, $brand_name, $brand_initial, '', $brand_pic, $brand_recommend, $sort, $brand_category_name, $category_id_array, $brand_ads, $category_name, $category_id_1, $category_id_2, $category_id_3);
            $this->addUserLogByParam("修改商品品牌",$res);
            return AjaxReturn($res);
        } else {
            $brand_id = $_GET['brand_id'];
            $brand_info = $goodsbrand->getGoodsBrandInfo($brand_id);
            if (empty($brand_info)) {
                return $this->error("没有查询到商品品牌信息");
            }
            $this->assign('brand_info', $brand_info);
            $goodscategory = new GoodsCategory();
            $list = $goodscategory->getGoodsCategoryListByParentId(0);
            $this->assign('goods_category_list', $list);
            return view($this->style . "Goods/editGoodsBrand");
        }
    }

    //上架下架品牌
    public function closeoropenBrand()
    {
        $goodsbrand = new GoodsBrand();
            $brand_id = isset($_POST['brand_id']) ? ($_POST['brand_id']) : "";
            $brand_recommend = isset($_POST['brand_recommend']) ? $_POST['brand_recommend'] : 0;
            $res = $goodsbrand->closeoropenBrand($brand_id,$brand_recommend);
            $this->addUserLogByParam("开启或关闭品牌",$res);
            return AjaxReturn($res);
    }

    /**
     * 删除商品品牌
     */
    public function deleteGoodsBrand()
    {
        $brand_id = $_POST['brand_id'];
        $goodsbrand = new GoodsBrand();
        $res = $goodsbrand->deleteGoodsBrand($brand_id);
        $this->addUserLogByParam("删除商品品牌",$res);
        return AjaxReturn($res);
    }

    /**
     * 获取绑定品牌
     */
    public function get_binding_brand()
    {
//        $goodsbrand = new GoodsBrand();
//
//        $condition['brand_recommend'] = 1;
//        $res = $goodsbrand->get_category_brand($condition);
//        ob_clean();
//        return json($res);
    }


    /**
     * 商品分类列表
     */
    public function goodsCategoryList()
    {
        $goodscate = new GoodsCategory();
        $one_list = $goodscate->getGoodsCategoryListByParentId(0);
        if (! empty($one_list)) {
            foreach ($one_list as $k => $v) {
                $two_list = array();
                $two_list = $goodscate->getGoodsCategoryListByParentId($v['category_id']);
                $v['child_list'] = $two_list;
                if (! empty($two_list)) {
                    foreach ($two_list as $k1 => $v1) {
                        $three_list = array();
                        $three_list = $goodscate->getGoodsCategoryListByParentId($v1['category_id']);
                        $v1['child_list'] = $three_list;
                    }
                }
            }
        }
        $this->assign("category_list", $one_list);
        return view($this->style . "Goods/goodsCategoryList");
    }


    function object2array(&$object) {
        $object =  json_decode( json_encode( $object),true);
        return  $object;
    }

    /**
     * 添加商品分类
     */
    public function addGoodsCategory()
    {
        $goodscate = new GoodsCategory();
        if (request()->isAjax()) {
            $category_name = request()->post("category_name", '');
            $pid = request()->post("pid", '');
            $is_visible = request()->post('is_visible', '');
            $keywords = request()->post("keywords", '');
            $description = request()->post("description", '');
            $sort = request()->post("sort", '');
            $category_pic = request()->post('category_pic', '');
            $attr_id = request()->post("attr_id", 0);
            $attr_name = request()->post("attr_name", '');
            $short_name = request()->post("short_name", '');
            $result = $goodscate->addOrEditGoodsCategory(0, $category_name, $short_name, $pid, $is_visible, $keywords, $description, $sort, $category_pic, $attr_id, $attr_name);
            $this->addUserLogByParam("添加商品分类",$result);
            return AjaxReturn($result);
        }
        $category_list = $goodscate->getGoodsCategoryTree(0);
        $this->assign('category_list', $category_list);
        $goods = new GoodsService();
        $condition['website_id'] = $this->website_id;
        $condition['is_use'] = 1;
        $category_id = $_REQUEST['category_id']?$_REQUEST['category_id']:'0';
        $this->assign('category_id',$category_id);
        $categoryDetail = $goodscate->getGoodsCategoryDetail($category_id);
        $this->assign('attr_id',$categoryDetail['attr_id'] ? : 0);
        $goodsAttributeList = $goods->getAttributeServiceList(1, 0,$condition);
        $this->assign("goodsAttributeList", $goodsAttributeList['data']);
        return view($this->style . "Goods/addGoodsCategory");
    }

    /**
     * 修改商品分类
     */
    public function updateGoodsCategory()
    {
        $goodscate = new GoodsCategory();
        if (request()->isAjax()) {
            $category_id = request()->post("category_id", '');
            $category_name = request()->post("category_name", '');
            $short_name = request()->post("short_name", '');
            $pid = request()->post("pid", '');
            $is_visible = request()->post('is_visible', '');
            $keywords = request()->post("keywords", '');
            $description = request()->post("description", '');
            $sort = request()->post("sort", '');
            $attr_id = request()->post("attr_id", 0);
            $attr_name = request()->post("attr_name", '');
            $category_pic = request()->post('category_pic', '');
            $goods_category_quick = request()->post("goods_category_quick", '');
            if ($goods_category_quick != '') {
                setcookie("goods_category_quick", $goods_category_quick, time() + 3600 * 24);
            }
            $result = $goodscate->addOrEditGoodsCategory($category_id, $category_name, $short_name, $pid, $is_visible, $keywords, $description, $sort, $category_pic, $attr_id, $attr_name);
            $this->addUserLogByParam("添加商品分类",$result);
            return AjaxReturn($result);
        } 
        $category_id = request()->get("category_id", '');
        $result = $goodscate->getGoodsCategoryDetail($category_id);
        $this->assign("data", $result);
        $this->assign("category_id", $category_id);
        $category_list = $goodscate->getGoodsCategoryTree(0);
        $this->assign('category_list', $category_list);
        $goods = new GoodsService();
        $goodsAttributeList = $goods->getAttributeServiceList(1, 0,['website_id'=>$this->website_id,'is_use' => 1]);
        $this->assign("goodsAttributeList", $goodsAttributeList['data']);
        return view($this->style . "Goods/updateGoodsCategory");
    }

    /**
     * 删除商品分类
     */
    public function deleteGoodsCategory()
    {
        $goodscate = new GoodsCategory();
        $category_id = $_POST['category_id'];
        $res = $goodscate->deleteGoodsCategory($category_id);
        if ($res > 0) {
            $goods_category_quick = request()->post("goods_category_quick", '');
            if ($goods_category_quick != '') {
                setcookie("goods_category_quick", $goods_category_quick, time() + 3600 * 24);
            }
        }
        $this->addUserLogByParam("删除商品分类",$category_id);
        return AjaxReturn($res);
    }
    /*
     * 修改商品分类排序
     * **/
    public function changeGoodsCategorySort()
    {
        $goodscate = new GoodsCategory();
        $category_id  = request()->post('category_id',0);
        $sort_val  = request()->post('sort_val',0);
        $bool = $goodscate->updateGoodsCategorySort($category_id, $sort_val);
        if($bool){
            $this->addUserLogByParam('修改商品分类排序', $category_id);
        }
        return AjaxReturn($bool);
    }

    /*
     * 修改商品分类名称
     * **/
    public function changeGoodsCategoryName()
    {
        $goodscate = new GoodsCategory();
        $category_id  = request()->post('category_id',0);
        $category_name  = request()->post('category_name',0);
        $bool = $goodscate->updateGoodsCategoryName($category_id, $category_name);
        if($bool){
            $this->addUserLogByParam('修改商品分类名称', $category_id);
        }
        return AjaxReturn($bool);
    }

    /*
     * 修改商品分类是否显示
     * **/
    public function changeGoodsCategoryShow()
    {
        $goodscate = new GoodsCategory();
        $category_id  = request()->post('category_id',0);
        $is_visible  = request()->post('is_visible',0);
        $bool = $goodscate->updateGoodsCategoryShow($category_id, $is_visible);
        if($bool){
            $this->addUserLogByParam('修改商品分类是否显示', $category_id);
        }
        return AjaxReturn($bool);
    }

    /**
     * 功能说明：查询商品属性
     */
    public function getGoodsAttributeList()
    {
        $goods = new GoodsService();
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $provList = $goods->getGoodsAttributeList($condition, '*', 'create_time desc');
        return $provList;
    }

    /**
     * 功能说明：商品属性规格获取
     */
    public function CateGoryPropsGet()
    {
        $name = $_POST["name"];
        $goodservice = new GoodsService();
        $res = $goodservice->addGoodsSpec($name);
        return $res;
    }

    /**
     * 功能说明：商品属性规格值获取
     */
    public function CateGoryPropvaluesGet()
    {
        $propId = $_POST["propId"];
        $value = $_POST["value"];
        $goodservice = new GoodsService();
        $res = $goodservice->addGoodsSpecValue($propId, $value);
        return $res;
    }

    /**
     * 设置规格属性是否启用
     */
    public function setIsvisible()
    {
        if (request()->isAjax()) {
            $spec_id = isset($_POST['spec_id']) ? $_POST['spec_id'] : '';
            $is_visible = isset($_POST['is_visible']) ? $_POST['is_visible'] : '';
            $goodservice = new GoodsService();
            $retval = $goodservice->updateGoodsSpecIsVisible($spec_id, $is_visible);
            $this->addUserLogByParam("更改规格启用状态",$spec_id);
            return AjaxReturn($retval);
        }
    }

    public function datatojson($code=0,$message=''){

        $data['code'] = $code;
        $data['message'] = $message;
        return json_encode($data);
    }
    /**
     * 功能说明：添加或更新商品时 ajax调用的函数
     */
    public function goodsCreateOrUpdate()
    {
        $product = $_POST['product'];
        if(!$product){
            return AjaxReturn(0);
        }
        $product = json_decode($product, true);
        //独立商品海报
        $product['poster_data'] = json_encode($product['poster_data'], JSON_UNESCAPED_UNICODE);
        if(!$product['imageArray']){
            $data['code'] = -1;
            $data['message'] = "图片不能为空";
            return json($data);
        }
        //计时计次商品
        $verificationinfo = array();
        $card_info = array();
        if($product['goods_type']==0){
            //核销信息
            $verificationinfo = $product['verificationinfo'];
            $verificationinfo['invalid_time'] = !empty($verificationinfo['end_time'])?strtotime($verificationinfo['end_time']) + (86400 - 1):'';
            //卡券信息
            if(empty($product['goods_id'])) {
                $card_info = $product['cardinfo'];
                $store_service = '';
                if(!empty($card_info['store_service'])) {
                    foreach ($card_info['store_service'] as $k => $v) {
                        $store_service .= $v . ',';
                    }
                }
                $card_info['store_service'] = substr($store_service, 0, -1);
            }
        }
        //核销门店
        $store_list = '';
        if(!empty($product['store_list'])) {
            foreach ($product['store_list'] as $k => $v) {
                $store_list .= $v . ',';
            }
        }
        $verificationinfo['store_list'] = substr($store_list,0,-1);
        $distribution_rule_val = $product['distribution_bonus']["distribution_val"];
        $distribution_rule = $product['distribution_bonus']["distribution_rule"];
        $is_distribution = $product['distribution_bonus']["is_distribution"];
        $is_bonus_global = $product['distribution_bonus']["is_global_bonus"];
        $is_bonus_team = $product['distribution_bonus']["is_team_bonus"];
        $is_bonus_area = $product['distribution_bonus']["is_area_bonus"];
        $bonus_rule_val = $product['distribution_bonus']["bonus_val"];
        $bonus_rule = $product['distribution_bonus']["bonus_rule"];
        //组装 复购信息 
        $buyagain = intval($product['distribution_bonus']["buyagain"]);
        $buyagain_level_rule = intval($product['distribution_bonus']["buyagain_level_rule"]);
        $buyagain_recommend_type = intval($product['distribution_bonus']["buyagain_recommend_type"]);
        $buyagain_distribution_val = $product['distribution_bonus']["buyagain_distribution_val"];

        $shopId = $this->instance_id;
        $goodservice = new GoodsService();
        $res = $goodservice->addOrEditGoods(
            $product["goodsId"], // 商品Id
            $product["title"], // 商品标题
            $shopId,
            $product["categoryId"], // 商品类目
            0,
            0,
            0,
            $product["supplierId"],
            $product["brandId"],
            $product["groupArray"], // 商品分组
            $product['goods_type'],
            $product["market_price"],
            $product["price"], // 商品现价
            $product["cost_price"],
            $product["point_exchange_type"],
            $product['integration_available_use'],
            $product['integration_available_give'],
            0,//会员折扣
            $product["shipping_fee"],
            $product["shipping_fee_id"],
            $product["stock"],
            $product['max_buy'],
            $product['min_buy'],
            $product["minstock"],
            $product["base_good"],
            $product["base_sales"],
            $collects = 0,
            $star = 0,
            $evaluates = 0,
            $product["base_share"],
            $product["province_id"],
            $product["city_id"],
            $product["picture"],
            $product['key_words'],
            $product["introduction"], // 商品简介，促销语
            $product["description"],
            $product['qrcode'], // 商品二维码
            $product["code"],
            $product["display_stock"],
            $product['is_hot'],
            $product['is_recommend'],
            $product['is_new'],
            $product['sort'],
            $product["imageArray"],
            $product["skuArray"],
            $product["state"], '', // $product["sku_img_array"]
            $product['goods_attribute_id'],
            $product['goods_attribute'],
            $product['goods_spec_format'],
            $product['goods_weight'],
            $product['goods_volume'],
            $product['shipping_fee_type'],
            $product['categoryExtendId'],
            $product["sku_picture_vlaues"],
            $product['item_no'],
            $distribution_rule_val,
            $distribution_rule,
            $is_distribution,
            $is_bonus_global,
            $is_bonus_area,
            $is_bonus_team,
            $bonus_rule_val,
            $bonus_rule,
            $product['is_promotion'],
            $product['is_shipping_free'],
            $product['is_wxcard'],
            $verificationinfo,   //核销信息,
            $card_info,
            $product['video_id'],
            $product['point_deduction_max'],
            $product['point_return_max'],
            $product["goods_count"],
            $product["single_limit_buy"],
            $buyagain,
            $buyagain_level_rule,
            $buyagain_recommend_type,
            $buyagain_distribution_val,
            $product["payment_content"],
            '',
            $product['is_goods_poster_open'],
            $product['poster_data'],
            $product['px_type']
        );
        $message = '操作失败';
        if ($res) {
            $goodsId = $res;
            $url = __URLS('APP_MAIN/goods/detail/'. $goodsId);
            $pay_qrcode = getQRcode($url, 'upload/' . $this->website_id . '/' . $this->instance_id . '/goods_qrcode', 'goods_qrcode_' . $goodsId);
            $goodservice->goods_QRcode_make($goodsId, $pay_qrcode);
            if ($product["goodsId"]) {
                $message = '编辑成功';
                $this->addUserLogByParam('更新商品', $product["goodsId"] . '-' . $product["title"]);
            } else {
                $message = '添加成功';
                $this->addUserLogByParam('添加商品', $product["title"]);
            }
        }
        // 商品权限折扣
        if ($discount_bonus = $product['discount_bonus']) {
            // 存入vsl_goods_discount
            $discount_data = [];
            if ($discount_bonus['discount_look_obj']) {// 浏览权限
                $discount_data['browse_auth_u'] = $discount_bonus['discount_look_obj']['member_level_id']? implode(',', $discount_bonus['discount_look_obj']['member_level_id']) : 0;
                $discount_data['browse_auth_d'] = $discount_bonus['discount_look_obj']['distributor_level_id'] ? implode(',', $discount_bonus['discount_look_obj']['distributor_level_id']) : 0;
                $discount_data['browse_auth_s'] = $discount_bonus['discount_look_obj']['user_group_level_id'] ? implode(',', $discount_bonus['discount_look_obj']['user_group_level_id']) : 0;
            }
            if ($discount_bonus['discount_buy_obj']) {//购买权限
                $discount_data['buy_auth_u'] = $discount_bonus['discount_buy_obj']['member_level_id2'] ? implode(',',$discount_bonus['discount_buy_obj']['member_level_id2']) : 0;
                $discount_data['buy_auth_d'] = $discount_bonus['discount_buy_obj']['distributor_level_id2'] ? implode(',',$discount_bonus['discount_buy_obj']['distributor_level_id2']) : 0;
                $discount_data['buy_auth_s'] = $discount_bonus['discount_buy_obj']['user_group_level_id2'] ? implode(',',$discount_bonus['discount_buy_obj']['user_group_level_id2']) : 0;
            }
            if ($discount_bonus['discount_channel_obj']) {//渠道商权限
                $discount_data['channel_auth'] = $discount_bonus['discount_channel_obj']['channel_level_id'] ? implode(',', $discount_bonus['discount_channel_obj']['channel_level_id']) : 0;
            }
            $discount_data['is_use'] = 0;
            if ($discount_bonus['is_member_discount_open'] == 1){// 开启会员折扣1开2关
                $discount_data['is_use'] = 1;
            }
            $discount_data['value'] = json_encode($discount_bonus);
            $discount = new GoodsDiscount();
            $d_condition = [
                'goods_id' => $product['goodsId'],
                'type' => 1,//权限折扣
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
            ];
            $oldDiscoutn = $discount->getInfo($d_condition);
            if ($product['goodsId'] > 0 && $oldDiscoutn) {//编辑
                $discount_data['update_time'] = time();
                $discount->save($discount_data, $d_condition);
            } else if($res){//新增
                $discount_data['goods_id'] = $res ?: $product['goodsId'];
                $discount_data['create_time'] = time();
                $discount_data['type'] = 1;
                $discount_data['shop_id'] =  $this->instance_id;
                $discount_data['website_id'] = $this->website_id;
                $discount->save($discount_data);
            } else {
                $message = '添加失败';
                $this->addUserLogByParam('添加商品折扣');
            }
            unset($d_condition);
        }
        $dataa['code'] = $res;
        $dataa['message'] = $message;
        return json($dataa);
    }

    /**
     * 获取省列表，商品添加时用户可以设置商品所在地
     */
    public function getProvince()
    {
        $address = new Address();
        $province_list = $address->getProvinceList();
        return $province_list;
    }


    public function returnJson($data){
        ob_clean();
        $result = json_encode($data);
        header('Content-Type:application/json');
        echo $result;exit;
    }

    /**
     * 获取城市列表
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:, array>
     */
    public function getCity()
    {
        $address = new Address();
        $province_id = isset($_POST['province_id']) ? $_POST['province_id'] : 0;
        $city_list = $address->getCityList($province_id);
        return $city_list;
    }

    /**
     * 商品标签列表
     */
    public function goodsGroupList()
    {
        if (request()->isAjax()) {
            $goodsgroup = new GoodsGroup();
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $condition = array('website_id'=>$this->website_id);
            $list = $goodsgroup->getGoodsGroupList($page_index, $page_size, $condition, "pid, sort");
            return $list;
        } else {
            return view($this->style . "Goods/goodsGroupList");
        }
    }

    /**
     * 添加商品标签
     */
    public function addGoodsGroup()
    {
        $goodsgroup = new GoodsGroup();
        if (request()->isAjax()) {
            $shop_id = $this->instance_id;
            $group_name = $_POST["group_name"];
            $pid = $_POST["pid"];
            $is_visible = $_POST['is_visible'];
            $sort = $_POST["sort"];
            $group_pic = $_POST['group_pic'];
            $result = $goodsgroup->addOrEditGoodsGroup(0, $shop_id, $group_name, $pid, $is_visible, $sort, $group_pic);
            $this->addUserLogByParam("添加商品标签",$result);
            return AjaxReturn($result);
        } else {
            $group_list = $goodsgroup->getGoodsGroupListByParentId($this->instance_id, 0);
            $this->assign("group_list", $group_list);
            return view($this->style . "Goods/addGoodsGroup");
        }
    }

    /**
     * 修改商品标签
     */
    public function updateGoodsGroup()
    {
        $goodsgroup = new GoodsGroup();
        if (request()->isAjax()) {
            $group_id = $_POST["group_id"];
            $shop_id = $this->instance_id;
            $group_name = $_POST["group_name"];
            $pid = $_POST["pid"];
            $is_visible = $_POST['is_visible'];
            $sort = $_POST["sort"];
            $group_pic = $_POST['group_pic'];
            $result = $goodsgroup->addOrEditGoodsGroup($group_id, $shop_id, $group_name, $pid, $is_visible, $sort, $group_pic);
            $this->addUserLogByParam("修改商品标签",$result);
            return AjaxReturn($result);
        } else {
            $group_id = $_GET['group_id'];
            $result = $goodsgroup->getGoodsGroupDetail($group_id);
            $this->assign("data", $result);
            return view($this->style . "Goods/updateGoodsGroup");
        }
    }

    /**
     * 删除商品标签
     */
    public function deleteGoodsGroup()
    {
        $goodsgroup = new GoodsGroup();
        $group_id = $_POST['group_id'];
        $res = $goodsgroup->deleteGoodsGroup($group_id, $this->instance_id);
        $this->addUserLogByParam("删除商品标签",$res);
        return AjaxReturn($res);
    }

    /**
     * 修改 商品 分类 单个字段
     */
    public function modifyGoodsCategoryField()
    {
        $goodscate = new GoodsCategory();
        $fieldid = request()->post('fieldid', '');
        $fieldname = request()->post('fieldname', '');
        $fieldvalue = request()->post('fieldvalue', '');
        $res = $goodscate->ModifyGoodsCategoryField($fieldid, $fieldname, $fieldvalue);
        return $res;
    }

    /**
     * 修改 商品 分组 单个字段
     */
    public function modifyGoodsGroupField()
    {
        $goodsgroup = new GoodsGroup();
        $fieldid = request()->post('fieldid', '');
        $fieldname = request()->post('fieldname', '');
        $fieldvalue = request()->post('fieldvalue', '');
        $res = $goodsgroup->ModifyGoodsGroupField($fieldid, $fieldname, $fieldvalue);
        return $res;
    }

    /**
     * 商品上架
     */
    public function ModifyGoodsOnline()
    {
        $condition = $_POST["goods_ids"]; // 将商品id用,隔开
        $goods_detail = new GoodsService();
        $result = $goods_detail->ModifyGoodsOnline($condition);
        $this->addUserLogByParam("商品上架",$_POST["goods_ids"]);
        return AjaxReturn($result);
    }

    /**
     * 商品下架
     */
    public function ModifyGoodsOutline()
    {
        $condition['goods_ids'] = $_POST["goods_ids"]; // 将商品id用,隔开
        $condition['reason'] = $_POST["reason"]; // 下架原因
        $goods_detail = new GoodsService();
        $result = $goods_detail->ModifyGoodsOutline($condition);
        $this->addUserLogByParam("商品违规下架",$_POST["goods_ids"]);
        return AjaxReturn($result);
    }

    /**
     * 商品下架
     */
    public function ModifyGoodsOffline()
    {
        $condition = $_POST["goods_ids"]; // 将商品id用,隔开
        $goods_detail = new GoodsService();
        $result = $goods_detail->ModifyGoodsOffline($condition);
        $this->addUserLogByParam("商品下架",$_POST["goods_ids"]);
        return AjaxReturn($result);
    }

    /**
     * 商品审核
     */
    public function ModifyGoodsAudit()
    {
        $condition['goods_ids'] = $_POST["goods_ids"]; // 将商品id用,隔开
        $condition['state'] = $_POST["state"] ? : 1; // 商品狀態，默認1
        $condition['reason'] = $_POST["reason"]; // 審核不通過理由
        $goods_detail = new GoodsService();
        $result = $goods_detail->ModifyGoodsAudit($condition);
        $this->addUserLogByParam("商品审核",$result);
        return AjaxReturn($result);
    }



    /**
     * 获取筛选后的商品
     *
     * @return unknown
     */
    public function getSerchGoodsList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $shop_range_type = request()->post('shop_range_type');
        $search_text = isset($_POST['search_text'])? $_POST['search_text'] : "";
        $condition['goods_name'] = array(
            "like",
            "%" . $search_text . "%"
        );
        if ($shop_range_type == 1){
            $condition['shop_id'] = $this->instance_id;
        }
        if($_REQUEST['shop_id']!=''){
            $condition['shop_id'] = $_REQUEST['shop_id'];
        }
        if($_REQUEST['seleted_goods']){
            $condition['goods_id'] = ['in',$_REQUEST['seleted_goods']];
        }
        //判断是否开启店铺应用
        if($this->shopStatus==0){
            $condition['shop_id'] = 0;
        }
        $condition['state'] = 1;//正常的商品 0-下架 1-正常 2-禁售
        $condition['website_id'] = $this->website_id;
        $goods_detail = new GoodsService();
        $result = $goods_detail->getSearchGoodsList($page_index, $page_size, $condition);
        return $result;
    }

    /**
     * 获取 商品分组一级分类
     *
     * @return Ambigous <number, unknown>
     */
    public function getGoodsGroupFristLevel()
    {
        $goods_group = new GoodsGroup();
        $list = $goods_group->getGoodsGroupListByParentId($this->instance_id, 0);
        return $list;
    }

    /**
     * 修改分组
     */
    public function ModifyGoodsGroup()
    {
        $goods_id = $_POST["goods_id"];
        $goods_type = $_POST["goods_type"];
        $goods_detail = new GoodsService();
        $result = $goods_detail->ModifyGoodsGroup($goods_id, $goods_type);
        $this->addUserLogByParam("修改分组",$result);
        return AjaxReturn($result);
    }

    /**
     * 商品规格
     */
    public function goodsSpecList()
    {
        $goods = new GoodsService();
        if (request()->isAjax()) {
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $page_size = isset($_POST['page_size']) ? $_POST['page_size'] : PAGESIZE;
            $list = $goods->getGoodsSpecList($page_index, $page_size, ['shop_id' => $this->instance_id], 'shop_id asc,sort asc, create_time desc');
            return $list;
        }
        return view($this->style . 'Goods/goodsSpecList');
    }

    /**
     * 修改商品规格单个属性值
     */
    public function setGoodsSpecField()
    {
        $goods = new GoodsService();
        $spec_id = request()->post("id");
        $field_name = request()->post("name");
        $field_value = request()->post("value");
        $retval = $goods->modifyGoodsSpecField($spec_id, $field_name, $field_value);
        if($retval){
            $this->addUserLogByParam("修改商品规格单个属性值",$retval);
        }
        return AjaxReturn($retval);
    }

    /**
     * 添加规格
     */
    public function addGoodsSpec()
    {
        $goods = new GoodsService();
        if (request()->isAjax()) {
            $spec_name = request()->post('spec_name', '');
            $is_visible = request()->post('is_visible', '');
            $sort = request()->post('sort', '');
            $show_type = request()->post('show_type', 0);
            $spec_value_str = request()->post('spec_value_str', '');
            $is_screen = request()->post('is_screen', 0);
            $attr_id = request()->post('attr_id/a', 0);
            if ($attr_id) {
                $attr_id = implode(',', $attr_id);
            }
            $res = $goods->addGoodsSpecService($this->instance_id, $spec_name, $show_type, $is_visible, $sort, $spec_value_str, $attr_id, $is_screen);
            if($res>0){
                $this->addUserLogByParam("添加商品规格",$res);
            }
            return AjaxReturn($res);
        }
        $goods_attribute_list = $goods->getAttributeServiceList(1, 0, ['website_id' => $this->website_id, 'is_use' => 1]);
        foreach ($goods_attribute_list['data'] as $key => $val) {
            $goods_attribute_list['data'][$key]['checked'] = 0;
        }
        unset($val);
        $this->assign("attribute_list", $goods_attribute_list['data']);
        return view($this->style . 'Goods/addGoodsSpec');
    }


    /**
     * 发布商品——————添加规格值
     *
     * @return multitype:unknown
     */
    public function addGoodsSpecValue(){
        $goods = new GoodsService();
        $spec_id = request()->post("spec_id", 0); // 规格id
        $spec_value_name = request()->post("spec_value_name", ""); // 规则值
        $spec_value_data = request()->post("spec_value_data", ""); // 规格值对应的颜色值、图片路径
        $is_visible = 1; // 是否可见，第一次添加，默认可见
        $retval = $goods->addGoodsSpecValueService($spec_id, $spec_value_name, $spec_value_data, $is_visible, '');
        if ($retval) {
            $this->addUserLogByParam("添加规格值",$spec_value_name);
        }
        return AjaxReturn($retval);
    }

    /**
     * 修改规格
     *
     * @return multitype:unknown
     */
    public function updateGoodsSpec()
    {
        $goods = new GoodsService();
        $spec_id = request()->get('spec_id', '');
        if (request()->isAjax()) {
            $spec_id = request()->post('spec_id', '');
            $spec_name = request()->post('spec_name', '');
            $is_visible = request()->post('is_visible', '');
            $show_type = request()->post('show_type', '');
            $sort = request()->post('sort', 0);
            $seleted_attr = request()->post('seleted_attr', '');
            $spec_value_str = request()->post('spec_value_str', '');
            $is_screen = request()->post('is_screen', 0);
            $attr_id = request()->post('attr_id/a', '');
            if ($attr_id) {
                $attr_id = implode(',', $attr_id);
            }
            $res = $goods->updateGoodsSpecService($spec_id, $this->instance_id, $spec_name, $show_type, $is_visible, $sort, $spec_value_str, $is_screen, $attr_id, $seleted_attr);
            if($res){
                $this->addUserLogByParam("修改规格",$res);
            }
            return AjaxReturn($res);
        }
        $detail = $goods->getGoodsSpecDetail($spec_id);
        $detail['spec_value_name_list'] = str_replace(',',chr(13).chr(10),$detail['spec_value_name_list']); 
        $this->assign('info', $detail);
        $attrCheck = explode(',', $detail['goods_attr_id']);
        $goods_attribute_list = $goods->getAttributeServiceList(1, 0, ['website_id' => $this->website_id, 'is_use' => 1]);
        foreach ($goods_attribute_list['data'] as $key => $val) {
            $goods_attribute_list['data'][$key]['checked'] = 0;
            if (in_array($val['attr_id'], $attrCheck)) {
                $goods_attribute_list['data'][$key]['checked'] = 1;
            }
        }
        unset($val);
        $this->assign("attribute_list", $goods_attribute_list['data']);
        return view($this->style . 'Goods/updateGoodsSpec');
    }

    /**
     * 修改商品规格属性
     * 备注：编辑商品时，也用到了这个方法，公共的啊
     */
    public function modifyGoodsSpecValueField()
    {
        $goods = new GoodsService();
        $spec_value_id = request()->post("spec_value_id");
        $field_name = request()->post('field_name');
        $field_value = request()->post('field_value');
        $retval = $goods->modifyGoodsSpecValueField($spec_value_id, $field_name, $field_value);
        $this->addUserLogByParam("修改商品规格属性",$retval);
        return AjaxReturn($retval);
    }

    /**
     * 删除商品规格
     */
    public function deleteGoodsSpec()
    {
        $spec_id = isset($_POST['spec_id']) ? $_POST['spec_id'] : 0;
        $goods = new GoodsService();
        $res = $goods->deleteGoodsSpec($spec_id);
        if($res==-1){
            $data['code'] = -1;
            $data['message'] = "正在使用的商品规格无法删除";
            return json($data);
        }else{
            $this->addUserLogByParam("删除商品规格",$res);
            return AjaxReturn($res);
        }

    }
    /**
     * 修改spec是否显示/启动
     */
    public function updateGoodsSpecShow()
    {
        $spec_id = isset($_POST['spec_id']) ? $_POST['spec_id'] : 0;
        $is_visible = isset($_POST['is_visible']) ? $_POST['is_visible'] : 0;
        $goods = new GoodsService();
        $res = $goods->updateGoodsSpecShow($spec_id, $is_visible);
        if($res){
            $this->addUserLogByParam("修改spec是否显示/启动", $spec_id);
        }

        return AjaxReturn($res);
    }
    /**
     * 修改品类是否显示/启动
     */
    public function updateGoodsAttrShow()
    {
        $attr_id = isset($_POST['attr_id']) ? $_POST['attr_id'] : 0;
        $is_use = isset($_POST['is_use']) ? $_POST['is_use'] : 0;
        $goods = new GoodsService();
        $res = $goods->updateGoodsAttrShow($attr_id, $is_use);
        if($res){
            $this->addUserLogByParam("修改品类是否显示/启动", $attr_id);
        }

        return AjaxReturn($res);
    }

    /**
     * 删除商品规格属性
     */
    public function deleteGoodsSpecValue()
    {
        $goods = new GoodsService();
        $spec_id = isset($_POST['spec_id']) ? $_POST['spec_id'] : 0;
        $spec_value_id = isset($_POST['spec_value_id']) ? $_POST['spec_value_id'] : 0;
        $res = $goods->deleteGoodsSpecValue($spec_id, $spec_value_id);
        $this->addUserLogByParam("删除商品规格属性",$res);
        return AjaxReturn($res);
    }

    /**
     * 商品类型
     */
    public function attributelist()
    {
        if (request()->isAjax()) {
            $page_index = request()->post('pageIndex', 1);
            $page_size = request()->post('page_size',PAGESIZE);
            $goods = new GoodsService();
            $condition = ['website_id'=>$this->website_id];
            $goodsEvaluateList = $goods->getAttributeServiceList($page_index, $page_size,$condition, 'sort');
            return $goodsEvaluateList;
        }
        return view($this->style . "Goods/attributelist");
    }

    /**
     * 添加商品属性
     */
    public function addAttributeServiceValue()
    {
        $goods = new GoodsService();
        $attr_id = request()->post('attr_id', '');
        $attr_name = request()->post('attr_value_name', '');
        if(!$attr_name){
            return AjaxReturn(0);
        }
        $res = $goods->addAttributeValueService($attr_id, $attr_name, 1, 1, 1, '');
        if($res){
            $this->addUserLogByParam("添加商品属性",$attr_name);
        }
        return AjaxReturn($res);
    }
    /**
     * 添加属性值
     */
    public function addAttributeValueName()
    {
        $goods = new GoodsService();
        $attrValueId = request()->post('attr_value_id');
        $attrValueName = request()->post('attr_value_name');
        if(!$attrValueId || !$attrValueName){
            return AjaxReturn(0);
        }
        $res = $goods->addAttributeValueName($attrValueId, $attrValueName);
        if($res){
            $this->addUserLogByParam("添加属性值",$attrValueName);
        }
        
        return AjaxReturn($res);
    }

    /**
     * 添加商品类型
     */
    public function addAttributeService()
    {
        $goods = new GoodsService();
        $category = new GoodsCategory();
        $condition['website_id'] = $this->website_id;
        $condition['is_visible'] = 1;
        $condition['shop_id'] = $this->instance_id;
        $brand_condition['website_id'] = $this->website_id;
        $brand_condition['brand_recommend'] = 1;
        $goodsguige = $goods->getGoodsSpecList(1, 0, $condition, 'sort ASC');
        $brand_list = $goods->getAllBrand(1, 0, $brand_condition, 'sort ASC');
        $goodsCategoryList = $category->getCategoryTreeUseInAdmin();
        $this->assign('brand_list', $brand_list);
        $this->assign('goodsCategoryList',$goodsCategoryList);
        $this->assign('goodsguige', $goodsguige);
        if (request()->isAjax()) {
            $attr_name = isset($_POST['attr_name']) ? $_POST['attr_name'] : '';
            $is_use = isset($_POST['is_visible']) ? $_POST['is_visible'] : '';
            $sort = isset($_POST['sort']) ? $_POST['sort'] : '';
            $spec_id_array = isset($_POST['spec_id']) ? $_POST['spec_id'] : '';
            $brand_id_id_array = isset($_POST['brand_id']) ? $_POST['brand_id'] : '';
            $value_string = isset($_POST['data_obj_str']) ? $_POST['data_obj_str'] : '';
            $cate_obj_arr = isset($_POST['cate_obj_arr']) ? $_POST['cate_obj_arr'] : [];
            $goodsAttribute = $goods->addAttributeService($attr_name, $is_use, $spec_id_array, $sort, $value_string,$brand_id_id_array,$cate_obj_arr);
            if($goodsAttribute){
                $this->addUserLogByParam("添加商品类型",$attr_name);
            }
            return AjaxReturn($goodsAttribute);
        }
        return view($this->style . 'Goods/addGoodsAttribute');
    }

    /**
     * 根据分类获取商品类型
     */
    public function getBindingAttr(){
        $cid = request()->post('cid',0);
        $categoryServer = new GoodsCategory();
        $category = $categoryServer->getGoodsCategoryDetail($cid);
        $attr_id = $category ? $category['attr_id'] : 0;
        return AjaxReturn(1,array('attr_id' => $attr_id));
    }


    /**
     * 删除一条商品类型属性
     */
    public function deleteAttributeValue()
    {
        $goods = new GoodsService();
        $attr_id = request()->post('attr_id', 0);
        $attr_value_id = request()->post('attr_value_id', 0);
        $res = $goods->deleteAttributeValueService($attr_id, $attr_value_id);
        $this->addUserLogByParam("删除一条商品类型属性",$res);
        return AjaxReturn($res);
    }

    //从商品里单个添加属性
    public function addgoodsattrbute(){

        $goods = new GoodsService();
        $attr_id = $_REQUEST['attr_id'];
        $attr_value_name = $_REQUEST['attr_value_name'];
        $value = $_REQUEST['attr_value'];
        $res = $goods->addAttributeValueService($attr_id, $attr_value_name,1, 1, 1, $value);
        if($res&&$_REQUEST['goods_id']){

            $goods_id = $_REQUEST['goods_id'];
            $time = time();
            $sql = "insert into `vsl_goods_attribute` (`goods_id`,`shop_id`,`attr_value_id`,`attr_value_name`,`attr_value`,`sort`,`create_time`,`website_id`) VALUES ($goods_id,$this->instance_id,$res,'$attr_value_name','$value','0',$time,$this->website_id)";
            Db::query($sql);
        }
        $data['code'] = $res;
        $data['attr_value_id'] = $res;
        $data['attr_value_name'] = $attr_value_name;
        $data['attr_value'] = $value;
        $this->addUserLogByParam("添加属性",$res);
        return AjaxReturn($res,$data);
    }

    /**
     * 修改商品类型
     */
    public function updateGoodsAttribute()
    {
        $goods = new GoodsService();
        $category = new GoodsCategory();
        $attr_id = isset($_GET['attr_id']) ? $_GET['attr_id'] : '';
        $condition['website_id'] = $this->website_id;
        $condition['is_visible'] = 1;
        $condition['shop_id'] = $this->instance_id;
        $brand_condition['website_id'] = $this->website_id;
        $brand_condition['brand_recommend'] = 1;
        $goodsguige = $goods->getGoodsSpecList(1, 0, $condition, 'sort ASC');
        $brand_list = $goods->getAllBrand(1, 0, $brand_condition, 'sort ASC');
        $goodsCategoryList = $category->getCategoryTreeUseInAdmin();
        $this->assign('brand_list', json_encode($brand_list['data']));
        $this->assign('brand_array',$brand_list);
        $this->assign('goodsCategoryList',$goodsCategoryList);
        $this->assign('goodsguige', $goodsguige);
        if (request()->isAjax()) {
            $attr_id = isset($_POST['attr_id']) ? $_POST['attr_id'] : '';
            $attr_name = isset($_POST['attr_name']) ? $_POST['attr_name'] : '';
            $is_use = isset($_POST['is_visible']) ? $_POST['is_visible'] : '';
            $sort = isset($_POST['sort']) ? $_POST['sort'] : '';
            $spec_id_array = isset($_POST['spec_id']) ? $_POST['spec_id'] : '';
            $brand_id_array = isset($_POST['brand_id']) ? $_POST['brand_id'] : '';
            $value_string = isset($_POST['data_obj_str']) ? $_POST['data_obj_str'] : '';
            $cate_obj_arr = isset($_POST['cate_obj_arr']) ? $_POST['cate_obj_arr'] : [];
            $res = $goods->updateAttributeServicePlatfom($attr_id, $attr_name, $is_use, $spec_id_array, $sort, $value_string,$brand_id_array,$cate_obj_arr);
            if(!$res){
                return AjaxReturn($res);
            }
            $spec_array = explode(',',$spec_id_array);
            foreach ($spec_array as $k=>$v){
                //检测当前绑定的规格是否有绑定品类
                $spec_model = new VslGoodsSpecModel();
                $goods_attr_id = $spec_model->getInfo(['spec_id'=>$v]);
                if(empty($goods_attr_id['goods_attr_id']) || $goods_attr_id['goods_attr_id']==0){
                    $spec_model->save(['goods_attr_id'=>$attr_id],['spec_id'=>$v]);
                }
            }
            $this->addUserLogByParam("修改商品类型",$res);
            return AjaxReturn($res);
        }
        //属性关联规格

        $attribute_detail = $goods->getAttributeServiceDetail($attr_id);
        $this->assign('info', $attribute_detail);
        $spec_array = explode(',',$attribute_detail['spec_id_array']);
        $brand_id = explode(',',$attribute_detail['brand_id_array']);
        $this->assign('spec_array', $spec_array);
        $this->assign('brand_id', $brand_id);
        $this->assign('attr_id', $attr_id);
        return view($this->style . 'Goods/updateGoodsAttribute');
    }

    /**
     * 修改商品类型单个属性
     */
    public function setAttributeField()
    {
        $goods = new GoodsService();
        $attr_id = request()->post("id");
        $field_name = request()->post("name");
        $field_value = request()->post("value");
        // var_dump($field_name);die;
        $reval = $goods->modifyAttributeFieldService($attr_id, $field_name, $field_value);
        $this->addUserLogByParam("修改商品属性",$reval);
        return AjaxReturn($reval);
    }

    /**
     * 搜索品牌或规格
     */
    public function search_brand_or_spec(){
        $goods = new GoodsService();
        if($_REQUEST['type']=='brand_search'){
            $condition = array(
                'brand_name' => ['like', '%' . $_REQUEST['brand_txt'] . '%'],
                'website_id' => $this->website_id
            );
            $selected_brand_id = $_REQUEST['select_brand_id']?:'';
            if (!empty($selected_brand_id) && is_array($selected_brand_id)) {
                $condition['brand_id'] = ['NOT IN', $selected_brand_id];
            }
            $brand_list = $goods->getAllBrand(1, 0, $condition, 'sort ASC');
            return AjaxReturn(1,$brand_list);

        }else{
            $condition['website_id'] = $this->website_id;
            $condition['is_visible'] = 1;
            $condition['spec_name'] = ['like', '%' . $_REQUEST['spec_txt'] . '%'];
            $selected_spec_id = $_REQUEST['select_specid']?:'';

            if (!empty($selected_spec_id) && is_array($selected_spec_id)) {
                $condition['spec_id'] = ['NOT IN', $selected_spec_id];
            }
            $goodsguige = $goods->getGoodsSpecList(1, 0, $condition, 'sort ASC');
            return AjaxReturn(1,$goodsguige);
        }
    }

    /**
     * 实时更新属性值
     */
    public function modifyAttributeValueService()
    {
        $goodsattribute = new GoodsService();
        $attr_value_id = request()->post('attr_value_id');
        $field_name = request()->post('field_name');
        $field_value = request()->post('field_value');
        $res = $goodsattribute->modifyAttributeValueService($attr_value_id, $field_name, $field_value);

        return $res;
    }

    /**
     * 删除商品类型
     */
    public function deleteAttr()
    {
        $attr_id = request()->post('attr_id');
        //先查询是否有使用
        $goods = new GoodsService();
        $goods_attr = new VslGoodsAttributeModel();
        $info = $goods_attr->alias('a')->join('vsl_attribute_value b','a.attr_value_id=b.attr_value_id','left')->where(['b.attr_id'=>$attr_id])->find();
        if(!empty($info)){
            $data['code'] = 0;
            $data['message'] = "商品正在使用中的品类无法删除";
            return json($data);
        }

        $res = $goods->deleteAttributeService($attr_id);
        $this->addUserLogByParam("删除商品类型",$res);
        return AjaxReturn($res);
    }

    /**
     * 商品评论
     */
    public function goodscomment()
    {
        if (request()->isAjax()) {
            $page_index = request()->post('page_index');
            $page_size = request()->post("page_size", PAGESIZE);

            $search = request()->post('search');
            $condition['goods_name'] = array(
                'like',
                "%" . $search . "%"
            );

            $member_name = request()->post('member_name', '');
            $start = strtotime('2010-1-1');
            $end = time();
            $start_date = request()->post('start_date') == '' ? $start : strtotime(request()->post('start_date'));
            $end_date = request()->post('end_date') == '' ? $end : strtotime(request()->post('end_date'));
            $explain_type = request()->post('explain_type', '');
            $condition["nm.addtime"] = [
                [
                    ">",
                    $start_date
                ],
                [
                    "<",
                    $end_date
                ]
            ];
            if ($explain_type != "") {
                $condition["nm.explain_type"] = $explain_type;
            }
            if (! empty($member_name)) {
                $condition["su.user_name"] = array(
                    "like",
                    "%" . $member_name . "%"
                );
            }

            $condition['nm.shop_id'] = 0;
            $condition['nm.website_id']=$this->website_id;
            $goods = new GoodsService();
            $goodsEvaluateList = $goods->getGoodsEvaluateList($page_index, $page_size, $condition, 'addtime desc');
            return $goodsEvaluateList;
        }
        //统计好评中评差评
        $haoping = Db::query("select count(*) as 'count' from `vsl_goods_evaluate` where `explain_type` = 5 and `website_id` = $this->website_id and `shop_id` = 0");
        $zhongping = Db::query("select count(*) as 'count' from `vsl_goods_evaluate` where `explain_type` = 3 and `website_id` = $this->website_id and `shop_id` = 0");
        $chaping = Db::query("select count(*) as 'count' from `vsl_goods_evaluate` where `explain_type` = 1 and `website_id` = $this->website_id and `shop_id` = 0");
        $this->assign("haoping",$haoping[0]['count']);
        $this->assign("zhongping",$zhongping[0]['count']);
        $this->assign("chaping",$chaping[0]['count']);
        // $goods = new GoodsService();
        // $goodsEvaluateList = $goods->getGoodsEvaluateList($page_index = 1, $page_size = 0);
        // //var_dump($goodsEvaluateList['data']);
        return view($this->style . "Goods/goodsComment");
    }

    /**
     * 删除商品评论
     */
    public function deleteGoodscomment()
    {
        if (request()->isAjax()) {
            $evaluate_id = request()->post('evaluate_id');
            $goods = new GoodsService();
            $goods_id = $goods->getGoodsEvaluateInfo(['id' => $evaluate_id], 'goods_id')['goods_id'];
            if (empty($goods_id)) {
                return json(['code' => -1, 'message' => '删除失败']);
            }
            $evaluateResult = $goodsEvaluateList = $goods->deleteEvaluate($evaluate_id);
            if ($evaluateResult){
                $goods = new VslGoodsModel();
                $goods->where(['goods_id' => $goods_id])->setDec('evaluates');
                return json(['code' => 1, 'message' => '删除成功']);
            }
            return json(['code' => -1, 'message' => '删除失败']);
        }
    }

    /**
     * 添加商品评价回复
     */
    public function replyEvaluateAjax()
    {
        if (request()->isAjax()) {
            $id = request()->post('evaluate_id');
            $replyType = request()->post('replyType');
            $replyContent = request()->post('evaluate_reply');
            $goods = new GoodsService();
            $res = $goods->addGoodsEvaluateReply($id, $replyContent, $replyType);
            $this->addUserLogByParam("添加商品评价回复",$res);
            return AjaxReturn($res);
        }
    }

    /**
     * 设置评价的显示状态
     */
    public function setEvaluteShowStatuAjax()
    {
        if (request()->isAjax()) {
            $id = request()->post('evaluate_id');
            $goods = new GoodsService();
            $res = $goods->setEvaluateShowStatu($id);
            $this->addUserLogByParam("设置评价显示状态",$res);
            return AjaxReturn($res);
        }
    }

    /**
     * 删除评价
     */
    public function deleteEvaluateAjax()
    {
        if (request()->isAjax()) {
            $id = request()->post('evaluate_id');
            $goods = new GoodsService();
            $res = $goods->deleteEvaluate($id);
            $this->addUserLogByParam("删除评价",$res);
            return AjaxReturn($res);
        }
    }

    /**
    修改商品规格值
     **/
    public function update_spec_value(){

        $goods = new GoodsService();
        $data['spec_value_data'] = $_REQUEST['pic_id'];
        $condition['spec_value_id'] = $_REQUEST['spec_id'];
        $res = $goods->updateGoodsSpecValueService($data,$condition);
        $this->addUserLogByParam("修改商品规格值",$res);
        return AjaxReturn($res);
    }


    /**
     * 商品回收站列表
     */
    public function recycleList()
    {
        if (request()->isAjax()) {
            $goodservice = new GoodsService();
            $page_index = request()->post("pageIndex", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $goods_name = request()->post('goods_name', '');
            $category_id_1 = request()->post('category_id_1', '');
            $category_id_2 = request()->post('category_id_2', '');
            $category_id_3 = request()->post('category_id_3', '');

            if (! empty($goods_name)) {
                $condition["ng.goods_name"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
            }
            if ($category_id_3 != "") {
                $condition["ng.category_id_3"] = $category_id_3;
            } else
                if ($category_id_2 != "") {
                    $condition["ng.category_id_2"] = $category_id_2;
                } else
                    if ($category_id_1 != "") {
                        $condition["ng.category_id_1"] = $category_id_1;
                    }
            $condition["ng.shop_id"] = $this->instance_id;
            $condition["ng.website_id"] = $this->website_id;
            $result = $goodservice->getGoodsDeletedList($page_index, $page_size, $condition, "ng.create_time desc");
            return $result;
        } else {
            $search_info = request()->post('search_info', '');
            $this->assign("search_info", $search_info);
            // 查找一级商品分类
            $goodsCategory = new GoodsCategory();
            $oneGoodsCategory = $goodsCategory->getGoodsCategoryListByParentId(0);
            $this->assign("oneGoodsCategory", $oneGoodsCategory);
            return view($this->style . 'Goods/recycleList');
        }
    }

    /**
     * 回收站商品恢复
     */
    public function regainGoodsDeleted()
    {
        if (request()->isAjax()) {
            $goods_ids = $_REQUEST['goodsId'];
            $goods_list = '';
            if(is_array($goods_ids)){
                foreach ($goods_ids as $k=>$v){
                    $goods_list .=$v.',';
                }
                $goods_list = substr($goods_list,0,-1);
            }else{
                $goods_list = $_REQUEST['goodsId'];
            }

            $goods = new GoodsService();
            $res = $goods->regainGoodsDeleted($goods_list);
            $this->addUserLogByParam("回收站商品恢复",$res);
            return AjaxReturn($res);
        }
    }

    /**
     * 拷贝商品
     */
    public function copyGoods()
    {
        $goods_id = request()->post('goods_id', '');
        $goodservice = new GoodsService();
        $res = $goodservice->copyGoodsInfo($goods_id);
//        if ($res > 0) {
//            $goodsId = $res;
//            $url = __URL(Config::get('view_replace_str.APP_MAIN') . '/goods/goodsdetail?id=' . $goodsId.'&website_id='.$this->website_id);
//            $pay_qrcode = getQRcode($url, 'upload/'.$this->website_id.'/goods_qrcode', 'goods_qrcode_' . $goodsId);
//            $goodservice->goods_QRcode_make($goodsId, $pay_qrcode);
//        }
        return AjaxReturn($res);
    }

    /**
     * 更改商品排序
     */
    public function updateGoodsSortAjax()
    {
        if (request()->isAjax()) {
            $goods_id = request()->post("goods_id", "");
            $sort = request()->post("sort", "");
            $goods = new GoodsService();
            $res = $goods->updateGoodsSort($goods_id, $sort);
            $this->addUserLogByParam("更改商品排序",$res);
            return AjaxReturn($res);
        }
    }

    /**
     * 生成商品二维码
     */
    public function updateGoodsQrcode()
    {
        $goods_ids = request()->post('goods_id', '');
        $goods_ids = explode(',', $goods_ids);
        if (! empty($goods_ids) && is_array($goods_ids)) {
            foreach ($goods_ids as $v) {
                $url = __URL(Config::get('view_replace_str.APP_MAIN') . '/goods/goodsdetail?id=' . $v.'&website_id='.$this->website_id);

//                $url = __URL(Config::get('view_replace_str.APP_MAIN') . '/goods/goodsdetail?id=' . $v);
                try {
                    $pay_qrcode = getQRcode($url, 'upload/'.$this->website_id.'/goods_qrcode', 'goods_qrcode_' . $v);
//                    $pay_qrcode = getQRcode($url, 'upload/'.$this->website_id.'/goods_qrcode', 'goods_qrcode_' . $v);
                } catch (\Exception $e) {
                    return AjaxReturn(UPLOAD_FILE_ERROR);
                }
                $goods = new GoodsService();
                $result = $goods->goods_QRcode_make($v, $pay_qrcode);
            }
        }
        return AjaxReturn($result);
    }

    /**
     * 修改商品名称或促销语
     */
    public function ajaxEditGoodsNameOrIntroduction()
    {
        if (request()->isAjax()) {
            $goods = new GoodsService();
            $goods_id = request()->post("goods_id", "");
            $up_type = request()->post("up_type", "");
            $up_content = request()->post("up_content", "");
            $res = $goods->updateGoodsNameOrIntroduction($goods_id, $up_type, $up_content);
            $this->addUserLogByParam("修改商品名称或促销语",$res);
            return AjaxReturn($res);
        }
    }

    /**开启店铺发布商品审核
     * @return \think\response\View
     */
    public function goodsConfig()
    {
        if(request()->isAjax()) {
            $value = request()->post('is_use',1);
            $shop_id = $this->instance_id;
            $config = new configService();
            $res = $config->setGoodsConfig($shop_id,$value);
            $this->addUserLogByParam("开启店铺发布商品审核",$res);
            return AjaxReturn($res);

        }
        $config = new configService();
        $goods_config_info = $config->getGoodsConfig();
        $this->assign('goods_config_info',$goods_config_info);

        return view($this->style . 'Goods/goodsConfig');
    }

    /**
     * 获取 商品 数量       全部    出售中  已审核  已下架
     */
    public function getGoodsCount(){
        $goods_count = new GoodsService();
        $goods_count_array = array();
        //全部
        $goods_count_array['all'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id]);
        //出售中
        $goods_count_array['sale'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>1,'shop_id'=>$this->instance_id]);
        //仓库中
        $goods_count_array['shelf'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>0,'shop_id'=>$this->instance_id]);
        //已售罄
        $goods_count_array['soldout'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id,'stock' => array("<=","0")]);
        //库存预警
        $goods_count_array['alarm'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>1,'shop_id'=>$this->instance_id,'min_stock_alarm' => array("neq", 0),'stock' => array("exp", "<= min_stock_alarm")]);
        return $goods_count_array;
    }

    public function goodsInfo()
    {
        $goods = new GoodsService();
        $keyword = request()->get("keyword", "");
        $condition["goods_name"] = array(
            'like',
            '%' . $keyword . '%'
        );

        $res = $goods->getGoodsInfo($condition);

        return $res;
    }

    public function quikly_edit(){

        $id = $_REQUEST['id'];
        if(isset($_REQUEST['market_price'])){
            if(!is_numeric($_REQUEST['market_price'])){
                $data['code'] = -1;
                $data['message'] = "请输入正确的数字";
                return json($data);
            }
        }
        if(isset($_REQUEST['price'])){
            if(!is_numeric($_REQUEST['price'])){
                $data['code'] = -1;
                $data['message'] = "请输入正确的数字";
                return json($data);
            }
        }
        if(isset($_REQUEST['stock'])){
            if(!is_numeric($_REQUEST['stock'])){
                $data['code'] = -1;
                $data['message'] = "请输入正确的数字";
                return json($data);
            }
        }
        
        $market_price = $_REQUEST['market_price']?$_REQUEST['market_price']:'';
        $price = $_REQUEST['price']?$_REQUEST['price']:'';
        $stock = $_REQUEST['stock']?$_REQUEST['stock']:'';
        $goods_name = $_REQUEST['goods_name']?$_REQUEST['goods_name']:'';
        $goods = new GoodsService();
        if(!empty($market_price)){
            $goods->updateGoodsNameOrIntroduction($id,'market_price',$market_price);
        }elseif(!empty($price)){
            $goods->updateGoodsNameOrIntroduction($id,'price',$price);
        }elseif(!empty($stock)){
            $goods->updateGoodsNameOrIntroduction($id,'stock',$stock);
        }elseif(!empty($goods_name)){
            $goods->updateGoodsNameOrIntroduction($id,'goods_name',$goods_name);
        }
    }

    public function category()
    {
        $goods_category = new GoodsCategory();
        $condition['website_id'] = $this->website_id;
        $goods_category_list = $goods_category->getGoodsCategoryList(1, 0, $condition);
        return $goods_category_list;
    }
    /*
     * 商品列表/详情 选择分类
     */
    public function selectCategory() {
        return view($this->style . 'Goods/selectCategory');
    }
    /*
     * 获取运费模板
     */
    public function getShippingFeeList(){
        $express = new Express();
        $list = $express->shippingFeeQuery(['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_enabled' => 1]);
        if(!$list){
            return [];
        }
        return $list;
    }
    /**
     * 修改标签
     */
    public function editLabel()
    {
        $goods = new GoodsService();
        $goods_id = request()->post("goods_id", 0);
        $labels = request()->post("label", "");
        $label = '';
        $res = 0;
        if($labels=='recommend'){
            $label = 'is_recommend';
        }
        if($labels=='new'){
            $label = 'is_new';
        }
        if($labels=='hot'){
            $label = 'is_hot';
        }
        if($labels=='promotion'){
            $label = 'is_promotion';
        }
        if($labels=='shipping_free'){
            $label = 'is_shipping_free';
        }
        if($label){
            $res = $goods->editLabel($goods_id, $label);
            $this->addUserLogByParam("修改商品标签",$res);
        }
        $res = ($res > 0)?SUCCESS:UPDATA_FAIL;
        return AjaxReturn($res);
    }
    
    /*
     * 修改商品品牌排序
     * **/
    public function changeGoodsBrandSort()
    {
        $goodscate = new GoodsBrand();
        $brand_id  = request()->post('brand_id',0);
        $sort_val  = request()->post('sort_val',0);
        $bool = $goodscate->ModifyGoodsBrandField($brand_id, 'sort', $sort_val);
        if($bool){
            $this->addUserLogByParam('修改商品品牌排序', $brand_id);
        }
        return AjaxReturn($bool);
    }
    
    /*
     * 查询新增修改商品里的应用状态
     * **/
    public function goodsAddons()
    {
        $addons = [];
        $addons['distributionStatus'] = getAddons('distribution', $this->website_id);
        $addons['globalStatus'] = getAddons('globalbonus', $this->website_id);
        $addons['areaStatus'] = getAddons('areabonus', $this->website_id);
        $addons['teamStatus'] = getAddons('teambonus', $this->website_id);
        return $addons;
    }

    /**
     * 后台 - 手动添加评论
     */
    public function addEvaluate()
    {
        if (request()->isAjax()) {
            $goods_id = request()->post('goods_id');
            $user_name = request()->post('user_name');
            $evaluate = request()->post('evaluate');
            $note = request()->post('note');
            $user_headimg_id = request()->post('user_headimg_id');
            $goods_pics = request()->post('goods_pics');
            $goods_name = stripslashes(request()->post('goods_name'));
            $goods_price = request()->post('goods_price');
            $album = new AlbumPictureModel();
            $user_headimg = $album->getInfo(['pic_id' =>$user_headimg_id], 'pic_cover_small')['pic_cover_small'];
            $goodsModel = new VslGoodsModel();
            $goods_img = $goodsModel::get(['goods_id' => $goods_id], ['album_picture'])['album_picture']['pic_cover_small'];

            if (!$user_name || !$user_headimg_id){
                //使用默认覆盖
                $condition = [
                    'user_name|nick_name' => ['NEQ', ''],
                    'user_headimg' => ['NEQ', '']
                ];
                $user = new UserModel();
                $randUser = $user->getRand($condition, 'user_name,nick_name,user_headimg');
                $user_name = $user_name ?: ($randUser['nick_name'] ?: $randUser['user_name']);
                $user_headimg = $user_headimg ?: $randUser['user_headimg'];
            }
            // 处理图片
            $albums = $album->getQuery(['pic_id'=>['IN', $goods_pics]], 'pic_cover_small', 'pic_id asc');
            $image = '';
            foreach ($albums as $pic) {
                $image .= $pic['pic_cover_small'].',';
            }
            $image = rtrim($image, ',');
            Db::startTrans();
            // 数据
            $dataArr = [
                'goods_id' => $goods_id,
                'goods_image' => $goods_img,
                'content' => $note,
                'image' => $image,
                'explain_type' => $evaluate,
                'user_name_default' => $user_name,
                'head_img_default' => $user_headimg,
                'website_id' => $this->website_id,
                'shop_id' => $this->instance_id,
                'addtime' => time(),
                'member_name' => $user_name,
                'goods_name' => $goods_name,
                'goods_price' => $goods_price,
            ];
            //入库
            $goodsEvaluate = new VslGoodsEvaluateModel();
            $evaluateResult = $goodsEvaluate->save($dataArr);
            if ($evaluateResult){
                Db::commit();
                $goods = new VslGoodsModel();
                $goods->where(['goods_id' => $goods_id])->setInc('evaluates');
                return json(['code' => 1, 'message' => '成功评价']);
            } else {
                Db::rollback();
                return json(['code' => -1, 'message' => '评价失败']);
            }
        }
    }
}