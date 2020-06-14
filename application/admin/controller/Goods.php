<?php

namespace app\admin\controller;

use addons\distribution\service\Distributor as DistributorService;
use addons\miniprogram\model\WeixinAuthModel;
use data\extend\QRcode;
use data\extend\WchatOpen;
use data\model\AlbumPictureModel;
use data\model\UserModel;
use data\model\VslGoodsDiscountModel as GoodsDiscount;
use data\model\VslGoodsEvaluateModel;
use data\model\VslGoodsModel;
use data\model\WebSiteModel;
use data\service\AddonsConfig;
use data\service\Album;
use data\service\Express as Express;
use data\service\Goods as GoodsService;
use data\service\GoodsBrand as GoodsBrand;
use data\service\GoodsCategory as GoodsCategory;
use data\service\GoodsGroup as GoodsGroup;
use data\model\VslGoodsTicketModel;
use data\service\Member as MemberService;
use think\Config;
use data\service\Config as WebConfig;
use addons\distribution\model\VslDistributorLevelModel;
use data\model\VslGoodsViewModel;
use think\Db;
/**
 * 商品控制器
 */
class Goods extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 根据商品ID查询单个商品，然后进行编辑操作
     */
    public function GoodsSelect() {
        $goods_detail = new GoodsService();
        $goods = $goods_detail->getGoodsDetail(request()->get('goodsId'),1);
        return $goods;
    }

    /**
     * 商品列表
     */
    public function goodsList() {
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
            $selectGoodsLabelId = request()->post('selectGoodsLabelId', '');
            $supplier_id = request()->post('supplier_id', '');
            $type = request()->post('type', 1);
            $stock_warning = request()->post("stock_warning", 0); // 库存预警
            $is_distribution = request()->post('is_distribution', '');
            $is_bonus = request()->post('is_bonus', '');
            $label_list = request()->post('label_list', '');
            
            if (!empty($selectGoodsLabelId)) {
                $selectGoodsLabelIdArray = explode(',', $selectGoodsLabelId);
                $selectGoodsLabelIdArray = array_filter($selectGoodsLabelIdArray);
                $str = "FIND_IN_SET(" . $selectGoodsLabelIdArray[0] . ",ng.group_id_array)";
                for ($i = 1; $i < count($selectGoodsLabelIdArray); $i++) {
                    $str .= "AND FIND_IN_SET(" . $selectGoodsLabelIdArray[$i] . ",ng.group_id_array)";
                }
                $condition[""] = [
                    [
                        "EXP",
                        $str
                    ]
                ];
            }
            switch ($type) {
                case 1:
                    $condition['ng.state'] = 1;
                    break;
                case 2:
                    $condition['ng.state'] = 0;
                    break;
                case 3:
                    $condition['ng.stock'] = 0;
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
                    break;
                case 5:
                    $condition['ng.state'] = 11;
                    break;
                case 6:
                    $condition['ng.state'] = 12;
                    break;
                case 7:
                    $condition['ng.state'] = 10;
                    break;
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
            if (!empty($goods_name)) {
                $condition["ng.goods_name"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
            }
            if (!empty($goods_code)) {
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

            $condition["ng.shop_id"] = $this->instance_id;
            $condition["ng.website_id"] = $this->website_id;

            // 库存预警
            if ($stock_warning == 1) {
                $condition['ng.min_stock_alarm'] = array(
                    "neq",
                    0
                );
                $condition['ng.stock'] = array(
                    "exp",
                    "<= ng.min_stock_alarm"
                );
            }

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
            $result = $goodservice->getGoodsList($page_index, $page_size, $condition, [
                'ng.create_time' => 'desc'
            ]);
            // 'ng.sort' => 'desc',
            // 根据商品分组id，查询标签名称
            foreach ($result['data'] as $k => $v) {
                $v['goods_spec_format'] = json_decode($v['goods_spec_format'], true);
                if(!$v['goods_spec_format']){
                    $result['data'][$k]['no_sku'] = 1;
                }else{
                    $result['data'][$k]['no_sku'] = 0;
                }
                $result['data'][$k]['promotion_name'] = $goodservice->getGoodsPromotionType($v['promotion_type']);
                if (!empty($v['group_id_array'])) {
                    $goods_group_id = explode(',', $v['group_id_array']);
                    $goods_group_name = '';
                    foreach ($goods_group_id as $key => $val) {
                        $goods_group = new GoodsGroup();
                        $goods_group_info = $goods_group->getGoodsGroupDetail($val);
                        if (!empty($goods_group_info)) {
                            $goods_group_name .= $goods_group_info['group_name'] . ',';
                        }
                    }
                    $goods_group_name = rtrim($goods_group_name, ',');
                    $result["data"][$k]['goods_group_name'] = $goods_group_name;
                }
            }
            return $result;
        } else {
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
            $this->assign('is_pc_use', $pc_conf['is_use']);
            $this->assign('is_minipro', $is_minipro);
            return view($this->style . "Goods/goodsList");
        }
    }

    public function getCategoryByParentAjax() {
        if (request()->isAjax()) {
            $parentId = intval(request()->post("parentId", ''));
            $goodsCategory = new GoodsCategory();
            $res = $goodsCategory->getGoodsCategoryListByParentId($parentId);
            return $res;
        }
    }

    /**
     * 功能说明：通过ajax来的得到页面的数据
     */
    public function SelectCateGetData() {
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
    public function getQuickGoods() {
        if (isset($_COOKIE["goods_category_quick"])) {
            return $_COOKIE["goods_category_quick"];
        } else {
            return -1;
        }
    }

    public function getGoodsGroupList() {
        $goods_group = new GoodsGroup();
        return $goods_group->getGroupGroup();
    }

    public function selectNumGoodsInfo()
    {
        if (request()->post()) {
            $goods = new VslGoodsViewModel();
            $goods_id = request()->post('goods_id', '');
            $condition['ng.website_id'] = $this->website_id;
            $condition['ng.goods_id'] = ['in', explode(',', $goods_id)];
            if ($this->shopStatus == 0) {
                $condition['ng.shop_id'] = 0;
            }
            $result = $goods->getGoodsViewQuery(1, 0, $condition, '');
            return $result;
        }
    }
    
    /**
     * 添加商品
     */
    public function addGoods() { 
        
        $config = new WebConfig();
        $sysConfig= $config->getShopConfig(0,$this->website_id);
        $convert_rate = $sysConfig['convert_rate'];
        $this->assign("convert_rate", $convert_rate); // 积分汇率
        
        $goods_group = new GoodsGroup();
        $express = new Express();
        $goods = new GoodsService();
        $goodsId = request()->get('goodsId', 0);
        $groupList = $goods_group->getGoodsGroupList(1, 0, [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ]);
        $this->assign("shipping_list", $express->shippingFeeQuery(['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_enabled' => 1])); // 物流
        $this->assign("group_list", $groupList['data']); // 分组
        if (empty($groupList['data'])) {
            $this->assign("group_str", '');
        } else {
            $this->assign("group_str", json_encode($groupList['data']));
        }
        $this->assign("goods_id", $goodsId);
        $this->assign("shop_type", 2);
        // 相册列表
        $album = new Album(); 
        $detault_album_detail = $album->getDefaultAlbumDetail();
        $this->assign('detault_album_id', $detault_album_detail['album_id']);
        //商品品牌
        //获取分销列表
        if($this->distributionStatus==1){

            //获取分销基本设置

            $dis_level = new VslDistributorLevelModel();

            $level_ids = $dis_level->Query(['website_id' => $this->website_id],'id');

            $this->assign("level_ids", implode(',',$level_ids));

            $level_list = $dis_level->getQuery(['website_id' => $this->website_id],'level_name,id','id asc');
            
            $this->assign("level_list", objToArr($level_list));

        }
        //判断O2O应用是否存在
        if (getAddons('store', $this->website_id, $this->instance_id)) {
            $this->assign('storeListUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/storeList')));
            $this->assign('store',1);
        }else{
            $this->assign('store',0);
        }
        //判断是否有知识付费应用
        if(getAddons('knowledgepayment',$this->website_id,$this->instance_id)) {
            $this->assign('have_knowledgepayment', 1);
        }else{
            $this->assign('have_knowledgepayment', 0);
        }

        // 会员等级
        $memberService = new MemberService();
        $memberLevelRes = $memberService->getMemberLevelList(1, 0, ['website_id' => $this->website_id], 'level_id ASC');//店铺没有会员等级用的平台的
        $this->assign('member_level', $memberLevelRes['data']);
        // 分销商等级
        $distributor = new DistributorService();
        $distributorRes = $distributor->getDistributorLevelList(1, 0, ['website_id'=>$this->website_id], 'id ASC');
        $this->assign('distributor_level', $distributorRes['data']);
        // 会员分组
        $member = new MemberService();
        $userTabRes = $member->getMemberGroupList(1, 0, ['website_id' => $this->website_id], 'group_id desc');
        $this->assign('user_group_level', $userTabRes['data']);
        if ($goodsId > 0) {
            if (!is_numeric($goodsId)) {
                $this->error("参数错误");
            }
            $this->assign("goodsid", $goodsId);
            $goods_info = $goods->getGoodsDetail($goodsId,1);
            if (!empty($goods_info)) {
                $goods_info['sku_list'] = json_encode($goods_info['sku_list']);
                $goods_info['goods_group_list'] = json_encode($goods_info['goods_group_list']);
                $goods_info['img_list'] = json_encode($goods_info['img_list']);
                $goods_info['goods_attribute_list'] = json_encode($goods_info['goods_attribute_list']);
                // 判断规格数组中图片路径是id还是路径
                if (trim($goods_info['goods_spec_format']) != "") {
                    $album = new Album();
                    $goods_spec_array = json_decode($goods_info['goods_spec_format'], true);
                    if($goods_spec_array){
                        foreach ($goods_spec_array as $k => $v) {
                            foreach ($v["value"] as $t => $m) {
                                if (is_numeric($m["spec_value_data"]) && $m["spec_show_type"] == 3) {
                                    $picture_detail = $album->getAlubmPictureDetail([
                                        "pic_id" => $m["spec_value_data"]
                                    ]);
                                    if (!empty($picture_detail)) {
                                        $goods_spec_array[$k]["value"][$t]["spec_value_data_src"] = $picture_detail["pic_cover_micro"];
                                    }
                                } elseif (!is_numeric($m["spec_value_data"]) && $m["spec_show_type"] == 3) {
                                    $goods_spec_array[$k]["value"][$t]["spec_value_data_src"] = $m["spec_value_data"];
                                }
                            }
                        }
                    }
                    
                    $goods_spec_format = json_encode($goods_spec_array, JSON_UNESCAPED_UNICODE);
                    $goods_info['goods_spec_format'] = $goods_spec_format;
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
               
                $extent_sort = count($goods_info["extend_category"]);
                $this->assign("extent_sort", $extent_sort);
                if ($goods_info["group_id_array"] == "") {
                    $this->assign("edit_group_array", array());
                } else {
                    $this->assign("edit_group_array", explode(",", $goods_info["group_id_array"]));
                }
                
                $goods_info['description'] = str_replace(PHP_EOL, '', $goods_info['description']);
                $this->assign("goods_info", $goods_info);

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
                $this->assign("good_discount", $good_discount);
                //知识付费商品的付费内容
                if ($goods_info['goods_type'] == 4) {
                    $goodservice = new GoodsService();
                    $payment_content = $goodservice->getKnowledgePaymentList($goodsId);
                    $this->assign("payment_content", $payment_content);
                }
                return view($this->style . "Goods/updateGoods");
            } else {
                $this->error("商品不存在");
            }
        } else {
            return view($this->style . 'Goods/addGoods');
        }
    }

    /**
     * 获取商品品牌列表，商品编辑时用到
     */
    public function getGoodsBrandList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $brand_name = request()->post("brand_name", "");
        $search_name = request()->post("search_name", "");
        $brand_id = request()->post("brand_id", "");
        // 排除当前选中的品牌，然后模糊查询
        $condition = array(
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'brand_name|brand_initial' => array(
                [
                    "like",
                    "%$search_name%"
                ],
                [
                    'eq',
                    $brand_name
                ],
                'or'
            )
        );
        // 判断当时编辑商品还是添加商品，如果存在品牌id，则排除该品牌，防止搜索结果出现重复数据
        if (!empty($brand_id)) {
            $condition['brand_id'] = [
                'neq',
                $brand_id
            ];
        }
        $goodsbrand = new GoodsBrand();
        $goods_brand_list = $goodsbrand->getGoodsBrandList($page_index, $page_size, $condition, '', 'brand_id,brand_name');
        return $goods_brand_list;
    }

    /**
     * 根据商品类型id查询，商品规格信息
     */
    public function getGoodsSpecListByAttrId() {
        $goods = new GoodsService();
        $condition["attr_id"] = request()->post("attr_id", 0);
        $list = $goods->getGoodsAttrSpecQuery($condition);
        return $list;
    }

    /**
     * 功能说明：通过节点的ID查询得到某个节点下的子集
     */
    public function getChildCateGory() {
        $categoryID = request()->post('categoryID', '');
        $goods_category = new GoodsCategory();
        $list = $goods_category->getGoodsCategoryListByParentId($categoryID);
        return $list;
    }

    /**
     * 删除商品
     */
    public function deleteGoods() {
        $goods_ids = request()->post('goods_ids');
        $goodservice = new GoodsService();
        $retval = $goodservice->deleteGoods($goods_ids);
        if ($retval) {
            $this->addUserLog('删除商品', $goods_ids);
        }
        return AjaxReturn($retval);
    }

    /**
     * 删除回收站商品
     */
    public function emptyDeleteGoods() {
        $goods_ids = request()->post('goods_ids');
        $goodsservice = new GoodsService();
        $retval = $goodsservice->deleteRecycleGoods($goods_ids);
        if ($retval) {
            $this->addUserLog('删除回收站商品', $goods_ids);
        }
        return AjaxReturn($retval);
    }

    /**
     * 功能说明：添加或更新商品时 ajax调用的函数
     */
    public function GoodsCreateOrUpdate() {
        $res = 0;
        $product = $_POST['product'];
        if (!empty($product)) {
            $product = json_decode($product, true);
            //独立商品海报
            $product['poster_data'] = json_encode($product['poster_data'], JSON_UNESCAPED_UNICODE);
            $shopId = $this->instance_id;

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
            //获取分销设置
            $distribution_rule_val = $product['distribution_bonus']["distribution_val"];
            $distribution_rule = $product['distribution_bonus']["distribution_rule"];
            $is_distribution = $product['distribution_bonus']["is_distribution"];
            //核销门店
            $store_list = '';
            if(!empty($product['store_list'])) {
                foreach ($product['store_list'] as $k => $v) {
                    $store_list .= $v . ',';
                }
            }
            $verificationinfo['store_list'] = substr($store_list,0,-1);
            $goodservice = new GoodsService();
            $res = $goodservice->addOrEditGoods(
                    $product["goodsId"], // 商品Id
                    $product["title"], // 商品标题
                    $shopId, $product["categoryId"], // 商品类目
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
                    0, //会员折扣
                    $product["shipping_fee"], 
                    $product["shipping_fee_id"], 
                    $product["stock"], 
                    $product['max_buy'], 
                    $product['min_buy'], 
                    $product["minstock"], 
                    $product["base_good"], 
                    $product["base_sales"], 
                    0, 
                    0, 
                    0, 
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
                    $product["is_sale"], 
                    '', // $product["sku_img_array"]
                    $product['goods_attribute_id'], 
                    $product['goods_attribute'], 
                    $product['goods_spec_format'] ? $product['goods_spec_format'] : '[]', 
                    $product['goods_weight'], 
                    $product['goods_volume'], 
                    $product['shipping_fee_type'], 
                    $product['categoryExtendId'], 
                    $product["sku_picture_vlaues"],
                    $product["item_no"],
                    $distribution_rule_val,
                    $distribution_rule,
                    $is_distribution,
                    1,
                    1,
                    1,
                    '',
                    2,
                    $product["is_promotion"],
                    $product['is_shipping_free'],
                    $product['card_switch'], //是否开启
                    $verificationinfo,   //核销信息,
                    $card_info,          //卡券信息
                    $product['video_id'],
                    $product['point_deduction_max'],
                    $product['point_return_max'],
                    $product['goods_count'],
                    $product["single_limit_buy"],
                    $buyagain = '',
                    $buyagain_level_rule = '',
                    $buyagain_recommend_type = '',
                    $buyagain_distribution_val = '',
                    $product["payment_content"],
                    '',
                    $product['is_goods_poster_open'],
                    $product['poster_data'],
                    $product['px_type']
            );
            // sku编码分组

            if ($res > 0) {
                $goodsId = $res;
                $url = __URLS('APP_MAIN/goods/detail/'. $goodsId);
                $pay_qrcode = getQRcode($url, 'upload/' . $this->website_id . '/' . $this->instance_id . '/goods_qrcode', 'goods_qrcode_' . $goodsId);
                $goodservice->goods_QRcode_make($goodsId, $pay_qrcode);
            }
            if ($res) {
                if ($product["goodsId"]) {
                    $this->addUserLog('更新商品', $product["goodsId"] . '-' . $product["title"]);
                } else {
                    $this->addUserLog('添加商品', $product["title"]);
                }
            }
        }
        $message = $res>0?'添加成功':'添加失败';
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
//            p($discount_data);exit;
            $oldDiscoutn = $discount->getInfo($d_condition);
            if ($product['goodsId'] > 0 && $oldDiscoutn) {//编辑
                $discount_data['update_time'] = time();
                $discount->save($discount_data, $d_condition);
//                echo Db::getLastSql();exit;
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
     * 商品上架
     */
    public function ModifyGoodsOnline() {
        $condition = request()->post('goods_ids', '');
        $goods_detail = new GoodsService();
        $retval = $goods_detail->ModifyGoodsOnline($condition);
        if ($retval) {
            $this->addUserLog('商品上架', $condition);
        }
        return AjaxReturn($retval);
    }

    /**
     * 商品下架
     */
    public function ModifyGoodsOffline() {
        $condition = request()->post('goods_ids', '');
        $goods_detail = new GoodsService();
        $retval = $goods_detail->ModifyGoodsOffline($condition);
        if ($retval) {
            $this->addUserLog('商品下架', $condition);
        }
        return AjaxReturn($retval);
    }

    /**
     * 获取筛选后的商品
     *
     * @return unknown
     */
    public function getSearchGoodsList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $search_text = request()->post("search_text", "");
        $condition = array(
            "goods_name" => ["like", "%$search_text%"]
        );
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $goods_detail = new GoodsService();
        $result = $goods_detail->getSearchGoodsList($page_index, $page_size, $condition);
        return $result;
    }

    /**
     * 商品属性
     */
    public function goodsSpecList() {
        $goods = new GoodsService();
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $condition = [];
            $condition['shop_id'] = array(['=', 0], ['=', $this->instance_id], 'or');
            $list = $goods->getGoodsSpecList($page_index, $page_size, $condition, 'shop_id desc,sort asc, create_time desc');
            return $list;
        }
        return view($this->style . 'Goods/goodsSpecList');
    }

    /**
     * 修改商品规格单个属性值
     */
    public function setGoodsSpecField() {
        $goods = new GoodsService();
        $spec_id = request()->post("id", '');
        $field_name = request()->post("name", '');
        $field_value = request()->post("value", '');
        $retval = $goods->modifyGoodsSpecField($spec_id, $field_name, $field_value);
        if ($retval) {
            $this->addUserLog('修改商品规格单个属性值', $field_name . ':' . $field_value);
        }
        return AjaxReturn($retval);
    }

    /**
     * 添加规格
     */
    public function addGoodsSpec() {
        $goods = new GoodsService();
        if (request()->isAjax()) {
            $spec_name = request()->post('spec_name', '');
            $is_visible = request()->post('is_visible', '');
            $sort = request()->post('sort', '');
            $show_type = request()->post('show_type', '');
            $spec_value_str = request()->post('spec_value_str', '');
            $is_screen = request()->post('is_screen', 0);
            $attr_id = request()->post('attr_id/a', 0);
            if ($attr_id) {
                $attr_id = implode(',', $attr_id);
            }
            $retval = $goods->addGoodsSpecService($this->instance_id, $spec_name, $show_type, $is_visible, $sort, $spec_value_str, $attr_id, $is_screen);
            if ($retval) {
                $this->addUserLog('添加规格', $spec_name);
            }
            return AjaxReturn($retval);
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
     * 修改规格
     *
     * @return multitype:unknown
     */
    public function updateGoodsSpec() {
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
            $retval = $goods->updateGoodsSpecService($spec_id, $this->instance_id, $spec_name, $show_type, $is_visible, $sort, $spec_value_str, $is_screen, $attr_id, $seleted_attr);
            if ($retval) {
                $this->addUserLog('修改规格:' . $spec_name, $retval);
            }
            return AjaxReturn($retval);
        }
        $detail = $goods->getGoodsSpecDetail($spec_id);
        $detail['spec_value_name_list'] = str_replace(',',chr(13).chr(10),$detail['spec_value_name_list']); 
        $detail['spec_value_name_list_platform'] = str_replace(',',chr(13).chr(10),$detail['spec_value_name_list_platform']); 
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
        return view($this->style . 'Goods/addGoodsSpec');
    }

    /**
     * 修改商品规格属性
     * 备注：编辑商品时，也用到了这个方法，公共的啊 
     */
    public function modifyGoodsSpecValueField() {
        $goods = new GoodsService();
        $spec_value_id = request()->post("spec_value_id", '');
        $field_name = request()->post('field_name', '');
        $field_value = request()->post('field_value', '');
        $retval = $goods->modifyGoodsSpecValueField($spec_value_id, $field_name, $field_value);
        if ($retval) {
            $this->addUserLog('修改商品规格属性', $field_name . ':' . $field_value);
        }
        return AjaxReturn($retval);
    }

    /**
     * 删除商品规格
     */
    public function deleteGoodsSpec() {
        $spec_id = request()->post('spec_id', 0);
        $goods = new GoodsService();
        $retval = $goods->deleteGoodsSpec($spec_id);
        if ($retval) {
            $this->addUserLog('删除商品规格', $spec_id);
        }
        return AjaxReturn($retval);
    }
    /**
     * 删除商品属性
     */
    public function deleteGoodsAttr() {
        $attr_value_id = request()->post('attr_value_id', 0);
        if(!$attr_value_id){
            return AjaxReturn(-1006);
        }
        $goods = new GoodsService();
        $retval = $goods->deleteGoodsAttr($attr_value_id);
        if ($retval) {
            $this->addUserLog('删除商品属性', $attr_value_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 删除商品规格属性
     */
    public function deleteGoodsSpecValue() {
        $goods = new GoodsService();
        $spec_id = request()->post('spec_id', 0);
        $spec_value_id = request()->post('spec_value_id', 0);
        $retval = $goods->deleteGoodsSpecValue($spec_id, $spec_value_id);
        if ($retval) {
            $this->addUserLog('删除商品规格属性', $spec_value_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 添加一条商品属性值
     */
    public function addAttributeServiceValue() {
        $goods = new GoodsService();
        $attr_id = request()->post('attr_id', '');
        $attr_name = request()->post('attr_value_name', '');
        $retval = $goods->addAttributeValueService($attr_id, $attr_name, 1, 1, 1, '');
        if ($retval) {
            $this->addUserLog('添加一条商品属性值', $attr_name);
        }
        return AjaxReturn($retval);
    }

    /**
     * 商品评论
     */
    public function goodscomment() {
        if (request()->isAjax()) {
            $page_index = request()->post('page_index');
            $page_size = request()->post('page_size');
            $member_name = request()->post('member_name', '');
            $start_date = request()->post('start_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('start_date'));
            $end_date = request()->post('end_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('end_date'));
            $explain_type = request()->post('explain_type', '');
            if ($start_date != 0 && $end_date != 0) {
                $condition["addtime"] = [
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
                $condition["addtime"] = [
                    [
                        ">",
                        $start_date
                    ]
                ];
            } elseif ($start_date == 0 && $end_date != 0) {
                $condition["addtime"] = [
                    [
                        "<",
                        $end_date
                    ]
                ];
            }
            if ($explain_type) {
                $condition["explain_type"] = $explain_type;
            }
            if (!empty($member_name)) {
                $condition["member_name"] = array(
                    "like",
                    "%" . $member_name . "%"
                );
            }
            $condition["shop_id"] = array("=", $this->instance_id);
            $goods = new GoodsService();
            $goodsEvaluateList = $goods->getGoodsEvaluateList($page_index, $page_size, $condition, 'addtime desc');
            return $goodsEvaluateList;
        }
        $goods = new GoodsService();
        $EvaluateCount = $goods->getEvaluateCount($this->instance_id);
        $this->assign('evaluate_count', $EvaluateCount);
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
    public function replyEvaluateAjax() {
        if (request()->isAjax()) {
            $id = request()->post('evaluate_id');
            $replyType = request()->post('replyType');
            $replyContent = request()->post('evaluate_reply');
            $goods = new GoodsService();
            $retval = $goods->addGoodsEvaluateReply($id, $replyContent, $replyType);
            if ($retval) {
                $this->addUserLog('添加商品评价回复', $replyContent);
            }
            return AjaxReturn($retval);
        }
    }

    /**
     * 添加 一条商品规格属性
     * 备注：编辑商品的时候也需要添加规格值，方法不能限制死，要共用
     */
    public function addGoodsSpecValue() {
        $goods = new GoodsService();
        $spec_id = request()->post("spec_id", 0); // 规格id
        $spec_value_name = request()->post("spec_value_name", ""); // 规则值
        $spec_value_data = request()->post("spec_value_data", ""); // 规格值对应的颜色值、图片路径
        $is_visible = 1; // 是否可见，第一次添加，默认可见
        $retval = $goods->addGoodsSpecValueService($spec_id, $spec_value_name, $spec_value_data, $is_visible, '');
        if ($retval) {
            $this->addUserLog('添加规格值', $spec_value_name);
        }
        return AjaxReturn($retval);
    }

    /**
     * 商品回收站列表
     */
    public function recycleList() {
        if (request()->isAjax()) {
            $goodservice = new GoodsService();
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $start_date = request()->post('start_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('start_date'));
            $end_date = request()->post('end_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('end_date'));
            $goods_name = request()->post('goods_name', '');
            $category_id_1 = request()->post('category_id_1', '');
            $category_id_2 = request()->post('category_id_2', '');
            $category_id_3 = request()->post('category_id_3', '');
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
            if (!empty($goods_name)) {
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
    public function regainGoodsDeleted() {
        if (request()->isAjax()) {
            $goods_ids = request()->post('goods_ids');
            $goods = new GoodsService();
            $retval = $goods->regainGoodsDeleted($goods_ids);
            if ($retval) {
                $this->addUserLog('回收站商品恢复', $goods_ids);
            }
            return AjaxReturn($retval);
        }
    }

    /**
     * 拷贝商品
     */
    public function copyGoods() {
        $goods_id = request()->post('goods_id', '');
        $goodservice = new GoodsService();
        $res = $goodservice->copyGoodsInfo($goods_id);
        if ($res > 0) {
            $goodsId = $res;
            $this->addUserLog('拷贝商品', $goodsId);
            $url = __URL(Config::get('view_replace_str.APP_MAIN') . '/goods/goodsdetail?id=' . $goodsId . '&website_id=' . $this->website_id);
            $pay_qrcode = getQRcode($url, 'upload/' . $this->website_id . '/' . $this->instance_id . '/goods_qrcode', 'goods_qrcode_' . $goodsId);
            $goodservice->goods_QRcode_make($goodsId, $pay_qrcode);
        }
        return AjaxReturn($res);
    }

    /**
     * 获取一级商品分类
     */
    public function getCategoryOne() {
        // 查找一级商品分类
        $goodsCategory = new GoodsCategory();
        $oneGoodsCategory = $goodsCategory->getGoodsCategoryListByParentId(0);
        return $oneGoodsCategory;
    }
    /**
     * 修改商品名称或促销语
     */
    public function ajaxEditGoodsDetail()
    {
        if (request()->isAjax()) {
            $goods = new GoodsService();
            $goods_id = request()->post("goods_id", "");
            $up_type = request()->post("up_type", "");
            $up_content = request()->post("up_content", "");
            $res = $goods->updateGoodsNameOrIntroduction($goods_id, $up_type, $up_content);
            return AjaxReturn($res);
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
     * 根据分类获取商品类型
     */
    public function getBindingAttr(){
        $cid = request()->post('cid',0);
        $categoryServer = new GoodsCategory();
        $category = $categoryServer->getGoodsCategoryDetail($cid);
        $attr_id = $category ? $category['attr_id'] : 0;
        return AjaxReturn(1,array('attr_id' => $attr_id));
    }
    
    /*
     * 获取运费模板
     */
    public function getShippingFeeList(){
        $express = new Express();
        $list = $express->shippingFeeQuery(['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'is_enabled' => 1]);
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
            $this->addUserLog("修改商品标签",$res);
        }
        $res = ($res > 0)?SUCCESS:UPDATA_FAIL;
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
            $this->addUserLog("添加属性值",$attrValueName);
        }
        
        return AjaxReturn($res);
    }
    //店铺端获取二维码、太阳码
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
        }
        if($qr_type == 1){
            //拼接出手机端的商品详情链接
            $wap_url = $domain_name.$wap_path.$id;
            QRcode::png($wap_url, false, 'L', '10', 0, false);
            $obcode = ob_get_clean();
            $code = imagecreatefromstring($obcode);
            header("Content-type: image/png");
            imagepng($code);
        }else{
            //拼接出小程序的商品详情链接
//            $mp_page = 'pages/goods/detail/index';
            $params = [
                'scene' => '-1_'.$id,
                'page' => $mp_page,
            ];
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
                $condition = [
                    'user_name|nick_name' => ['NEQ', ''],
                    'user_headimg' => ['NEQ', '']
                ];
                //使用默认覆盖
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
