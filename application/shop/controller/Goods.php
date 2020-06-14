<?php

namespace app\shop\controller;

use addons\coupontype\model\VslCouponTypeModel;
use addons\discount\server\Discount;
use addons\distribution\service\Distributor;
use addons\fullcut\service\Fullcut;
use addons\gift\model\VslPromotionGiftModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\seckill\model\VslSeckillModel;
use addons\seckill\server\Seckill;
use data\model\UserModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslMemberModel;
use data\service\Address;
use data\service\Album;
use data\service\Config;
use data\service\Goods as GoodsService;
use data\service\GoodsBrand as GoodsBrand;
use data\service\GoodsCategory as GoodsCategoryService;
use data\service\GoodsGroup as GoodsGroupService;
use data\service\Member as MemberService;
use data\service\Order as OrderService;
use data\service\promotion\GoodsExpress;
use addons\shop\service\Shop as ShopService;
use data\service\Promotion;
use addons\coupontype\server\Coupon as CouponServer;
use data\extend\custom\Common;
use data\model\SysPcCustomConfigModel;
use think\cache;
use addons\qlkefu\server\Qlkefu;
/**
 * 商品控制器
 */
class Goods extends BaseController {

    // 商品
    private $goods = null;
    private $goods_group = null;
    // 店铺
    private $shop = null;
    // 会员、个人
    private $member = null;
    // 商品分类
    private $goods_category = null;
    // 平台
    private $platform = null;
    // 优惠券
    private $coupon = null;

    public function __construct() {
        parent::__construct();
    }

    public function _empty($name) {
        
    }

    /**
     * 商品详情
     * @return \think\response\View
     */
    public function goodsinfo($goodsid) {
        $this->goods_category = new GoodsCategoryService();
        $web_info = $this->web_site->getWebSiteInfo();
        if (empty($goodsid)) {
            $goodsid = request()->get('goodsid');
        }
        $goodsid = (int) $goodsid;
        $this->goods = new GoodsService();
        // todo... by sgw 商品是否有权限
        $is_allow_browse = 1;
        $is_allow_buy = 1;
        if ($this->uid) {
            $is_allow_buy = $this->goods->isAllowToBuyThisGoods($this->uid, $goodsid);//购买权限
            $is_allow_browse = $this->goods->isAllowToBrowse($this->uid, $goodsid); // 是否有浏览权限
        }
        $Good = new VslGoodsModel();
        $goods_id = $Good->getInfo(['goods_id'=>$goodsid]);
        if (!empty($goodsid) && $goods_id) {
            $fullcut_config = getAddons('fullcut',$this->website_id);
            if($fullcut_config==1) {
                //根据店铺类型获取满足条件的活动信息
                $full_cut_server = new Fullcut();
                $full_cut_info = $full_cut_server->goodsFullCut($goodsid);
                $full_cut_list = [];
                if($full_cut_info){
                    foreach ($full_cut_info as $k => $v) {
                        $full_cut_list[$k]['mansong_id'] = $v['mansong_id'];
                        $full_cut_list[$k]['mansong_name'] = $v['mansong_name'];
                        $full_cut_list[$k]['start_time'] = $v['start_time'];
                        $full_cut_list[$k]['end_time'] = $v['end_time'];
                        $full_cut_list[$k]['rules'] = [];
                        foreach ($v->rules as $i => $r) {
                            $full_cut_list[$k]['rules'][$i]['price'] = $r['price'];
                            $full_cut_list[$k]['rules'][$i]['discount'] = $r['discount'];
                            $full_cut_list[$k]['rules'][$i]['free_shipping'] = $r['free_shipping'];
                            $full_cut_list[$k]['rules'][$i]['give_point'] = $r['give_point'];
                            if ($r['give_coupon'] && $this->isCouponOn) {
                                $coupon_type_model = new VslCouponTypeModel();
                                $full_cut_list[$k]['rules'][$i]['coupon_type_id'] = $r['give_coupon'];
                                $full_cut_list[$k]['rules'][$i]['coupon_type_name'] = $coupon_type_model::get($r['give_coupon'])['coupon_name'];
                            } else {
                                $full_cut_list[$k]['rules'][$i]['coupon_type_id'] = '';
                                $full_cut_list[$k]['rules'][$i]['coupon_type_name'] = '';
                            }
                            //礼品券
                            if ($r['gift_card_id'] && $this->gift_voucher) {
                                $gift_voucher = new VslGiftVoucherModel();
                                $full_cut_list[$k]['rules'][$i]['gift_card_id'] = $r['gift_card_id'];
                                $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id'=>$r['gift_card_id']], 'giftvoucher_name')['giftvoucher_name'];
                                $full_cut_list[$k]['rules'][$i]['gift_voucher_name'] = $giftvoucher_name;//送优惠券
                            }else{
                                $full_cut_list[$k]['rules'][$i]['gift_card_id'] = '';
                                $full_cut_list[$k]['rules'][$i]['gift_voucher_name'] = '';
                            }
                            //赠品
                            if ($r['gift_id'] && $this->is_gift) {

                                $gift_mdl = new VslPromotionGiftModel();
                                $full_cut_list[$k]['rules'][$i]['gift_id'] = $r['gift_id'];
                                $gift_name = $gift_mdl->getInfo(['promotion_gift_id'=>$r['gift_id']], 'gift_name')['gift_name'];
                                $full_cut_list[$k]['rules'][$i]['gift_name'] = $gift_name;//送优惠券
                            }else{
                                $full_cut_list[$k]['rules'][$i]['gift_id'] = '';
                                $full_cut_list[$k]['rules'][$i]['gift_name'] = '';
                            }
                        }
                    }
                    $this->assign('fullcutinfo',$full_cut_list);
                }
//                $fullcut = new Promotion();
//                $fullcutinfo = $fullcut->getBestMansongInfo($goods_id['shop_id']);
//                if (!empty($fullcutinfo)) {
                    /*$rule_arr = [];
                    foreach($fullcutinfo as $v0){
                        foreach($v0->rules as $v){
                            $rule_arr[$v['mansong_id']]['mansong_name'] = $v0['mansong_name'];
                            $rule_arr[$v['mansong_id']]['price'] = $v['price'];
                            $rule_arr[$v['mansong_id']]['discount'] = $v['discount'];
                            $rule_arr[$v['mansong_id']]['free_shipping'] = $v['free_shipping'];
                            $rule_arr[$v['mansong_id']]['give_coupon'] = $v['give_coupon'];
                            $coupon_type_model = new VslCouponTypeModel();
                            $gift_voucher = new VslGiftVoucherModel();
                            $coupon_type_name = $coupon_type_model->getInfo(['coupon_type_id'=>$v['give_coupon']], 'coupon_name')['coupon_name'];
                            $rule_arr[$v['mansong_id']]['coupon_type_name'] = $coupon_type_name;
                            $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id'=>$v['gift_card_id']], 'giftvoucher_name')['giftvoucher_name'];
                            $rule_arr[$v['mansong_id']]['gift_card_id'] = $v['gift_card_id'];
                            $rule_arr[$v['mansong_id']]['gift_card_name'] = $giftvoucher_name;
                            $gift_mdl = new VslPromotionGiftModel();
                            $gift_name = $gift_mdl->getInfo(['promotion_gift_id'=>$v['gift_id']], 'gift_name')['gift_name'];
                            $rule_arr[$v['mansong_id']]['gift_id'] = $v['gift_id'];
                            $rule_arr[$v['mansong_id']]['gift_name'] = $gift_name;

                        }
                    }
                    $this->assign('mansong_info', $rule_arr);*/
//                    $this->assign('price', $rule_arr['0']['price']);
//                    $this->assign('discount', $rule_arr['0']['discount']);
//                    $this->assign('mansong_name', $fullcutinfo['0']['mansong_name']);
//                    $this->assign('baoyou_name', "满" . $rule_arr['0']['price'] . "元包邮");
                    //判断活动是全部商品或部分商品存在
//                    if ($fullcutinfo['0']['range_type'] == 1 || !empty($fullcut->check_is_mansong_product($goodsid, $fullcutinfo['0']['mansong_id']))) {
//                        $this->assign('price', $fullcutinfo['0']['price']);
//                        $this->assign('discount', $fullcutinfo['0']['discount']);
//                        $this->assign('mansong_name', $fullcutinfo['0']['mansong_name']);
//                        if ($fullcutinfo['0']['free_shipping'] == '1') {
//                            $this->assign('baoyou_name', "满" . $fullcutinfo['0']['price'] . "元包邮");
//                        }
//                    }
//                }
            }
            
            $default_client = request()->cookie("default_client", "");
            if ($default_client == "shop") {
                
            } elseif (request()->isMobile() && $web_info['wap_status'] != 2) {
                $redirect = __URL(__URL__ . "/wap/goods/detail/" . $goodsid);
                $this->redirect($redirect);
                exit();
            }

            // 当切换到PC端时，隐藏右下角返回手机端按钮
            if (!request()->isMobile() && $default_client == "shop") {
                $default_client = "";
            }
            $this->goods_group = new GoodsGroupService();
            
            $this->member = new MemberService();
            if($this->isCouponOn){
                $this->coupon = new CouponServer();
            }
            if($this->is_seckill){
                $seckill_server = new Seckill();
                $condition_seckill['nsg.goods_id'] = $goodsid;
                //判断是否是秒杀商品
                $is_seckill = $seckill_server->isSkuStartSeckill($condition_seckill);
            }
            $this->assign('goods_id', $goodsid); // 将商品id传入方便查询当前商品的评论
            $this->member->addMemberViewHistory($goodsid);
            // 商品详情
            $goods_info = $this->goods->getGoodsDetail($goodsid);
            $goods_info['is_allow_browse'] = $is_allow_browse;
            $goods_info['is_allow_buy'] = $is_allow_buy;
            $discount_num = 1;
            if ($goods_info['is_show_member_price'] == 1) {
                $goods_info['price'] = $goods_info['member_price'];
                $discount_num = $goods_info['member_discount'];
            }

            //若是秒杀商品修改价格和库存
            if($is_seckill){
                $goods_stock = 0;
                foreach($goods_info['sku_list'] as $k=>$v){
                    $seckill_sku_condition['sku_id'] = $v['sku_id'];
                    $seckill_sku_condition['seckill_id'] = $is_seckill['seckill_id'];
                    $seckill_sku_info = $seckill_server->getSeckillSkuInfo($seckill_sku_condition);
                    $redis = $this->connectRedis();
                    $sku_id = $v['sku_id'];
                    $store = $seckill_sku_info['remain_num'];
                    //redis队列key值
                    $redis_goods_sku_store_key = 'store_' . $goodsid . '_' . $sku_id;
                    $is_index = $redis->llen($redis_goods_sku_store_key);
                    if (!$is_index) {
//                        for($num=0;$num<$store;$num++){
//                            $redis->lpop($redis_goods_sku_store_key);
//                        }
                        for ($num = 0; $num < $store; $num++) {
                            $redis->rpush($redis_goods_sku_store_key, 1);
                        }
                    }
                    $goods_info['sku_list'][$k]['promote_price'] = $seckill_sku_info['seckill_price'];
                    $goods_info['sku_list'][$k]['price'] = $seckill_sku_info['seckill_price'];
                    $goods_info['sku_list'][$k]['member_price'] = $seckill_sku_info['seckill_price'];
                    //通过库存取找到当前可以该买的数量用于限制
                    $remain_num = $seckill_sku_info['remain_num'];
//                    $limit_buy = $seckill_sku_info['seckill_limit_buy'];
                    $goods_info['sku_list'][$k]['stock'] = $remain_num;
                    $goods_stock +=  $remain_num;
                }
                $goods_info['price'] = $is_seckill['seckill_price'];
                $goods_info['stock'] = $goods_stock;
                $goods_info['sales'] += $seckill_sku_info['seckill_sales'];
                //返回结束时间
                $seckill_info['end_time'] = $is_seckill['seckill_now_time'] + 24*3600;
                $this->assign('seckill_info',$seckill_info);
                $this->assign('seckill_id', $is_seckill['seckill_id']);
            }
            $promotion = new Promotion();
            //检测是否是折扣商品
           
            if($this->is_discount){
                $discount = new Discount();
                $discount_info = $discount->getPromotionInfo($goodsid,$goods_info->shop_id,$this->website_id);
                // $discount_info = $promotion->check_is_discount_product($goodsid, $type);
                if (!empty($discount_info)) {
//                    //判断是全部折扣还是部分
//                    if($discount_info[0]['range']=='1'){
//                        $goods_info['promote_price'] = $promotion->get_discount_price($goods_info['price'], $discount_info[0]['discount_num']);
//                    }else{
//                        $goods_info['promote_price'] = $promotion->get_discount_price($goods_info['price'], $discount_info[0]['discount']);
//                    }
                    $discount_num = $discount_info['discount_num'] * $discount_num;
                    if($discount_info['discount_type'] == 2){
                        $goods_info['promote_price'] = $discount_info['discount_num'];
                    }else{
                        if($discount_info['discount_type'] == 1){
                            $goods_info['promote_price'] = round($promotion->get_discount_price($goods_info['price'], $discount_info['discount_num']));
                        }else{
                            $goods_info['promote_price'] = $promotion->get_discount_price($goods_info['price'], $discount_info['discount_num']);
                        }
                    }
                    $this->assign("promote_price", $goods_info['promote_price']);
                    $this->assign("discount_info", $discount_info);
                }
            }

            $shop_id = 0;
            if($this->shopStatus){
            // 店铺详情
                $this->shop = new ShopService();
                $shop_info = $this->shop->getShopDetail($goods_info["shop_id"]);
                $shop_id = $goods_info['shop_id'];
                $this->assign("shop_info", $shop_info);
                $this->assign("shop_id", $shop_id);
                // 当前用户是否收藏了该店铺
                $is_member_fav_shop = $this->member->getIsMemberFavorites($this->uid, $shop_id, 'shop');
                $this->assign("is_member_fav_shop", $is_member_fav_shop);
            }
            // 获取当前时间
            $current_time = $this->getCurrentTime();
            $this->assign('ms_time', $current_time);
            if (empty($goods_info)) {
                $redirect = __URL(__URL__ . '/index');
                $this->redirect($redirect);
            }
            // 规格图片
            // 判断规格数组中图片路径是id还是路径
            $spec_list = $goods_info["spec_list"];
            if (!empty($spec_list)) {
                $album = new Album();
                foreach ($spec_list as $k => $v) {
                    foreach ($v["value"] as $t => $m) {
                        if ($v["show_type"] == 3) {
                            if (is_numeric($m["spec_value_data"])) {
                                $picture_detail = $album->getAlubmPictureDetail([
                                    "pic_id" => $m["spec_value_data"]
                                ]);
                                
                                if (!empty($picture_detail)) {
                                    $spec_list[$k]["value"][$t]["picture_id"] = $picture_detail['pic_id'];
                                    $spec_list[$k]["value"][$t]["spec_value_data"] = __IMG($picture_detail["pic_cover_micro"]);
                                    $spec_list[$k]["value"][$t]["spec_value_data_big_src"] = __IMG($picture_detail["pic_cover_big"]);
                                } else {
                                    $spec_list[$k]["value"][$t]["spec_value_data"] = '';
                                    $spec_list[$k]["value"][$t]["spec_value_data_big_src"] = '';
                                    $spec_list[$k]["value"][$t]["picture_id"] = 0;
                                }
                            } else {
                                $spec_list[$k]["value"][$t]["spec_value_data_big_src"] = $m["spec_value_data"];
                                $spec_list[$k]["value"][$t]["picture_id"] = 0;
                            }
                        }
                    }
                }
                $goods_info['spec_list'] = $spec_list;
            }
            // 把属性值相同的合并
            $goods_attribute_list = $goods_info['goods_attribute_list'];
            $goods_attribute_list_new = array();
            foreach ($goods_attribute_list as $item) {
                $attr_value_name = '';
                foreach ($goods_attribute_list as $key => $item_v) {
                    if ($item_v['attr_value_id'] == $item['attr_value_id']) {
                        $attr_value_name .= $item_v['attr_value_name'] . ',';
                        unset($goods_attribute_list[$key]);
                    }
                }
                if (!empty($attr_value_name)) {
                    array_push($goods_attribute_list_new, array(
                        'attr_value_id' => $item['attr_value_id'],
                        'attr_value' => $item['attr_value'],
                        'attr_value_name' => rtrim($attr_value_name, ',')
                    ));
                }
            }

            $goods_info['goods_attribute_list'] = $goods_attribute_list_new;

            $goods_info['member_price'] = sprintf("%.2f", $goods_info['member_price']);
            $Config = new Config();
            $seoconfig = $Config->getSeoConfig($this->instance_id);
            if (!empty($goods_info['keywords'])) {
                $seoconfig['seo_meta'] = $goods_info['keywords']; // 关键词
            }
            $seoconfig['seo_desc'] = $goods_info['goods_name'];
            // 标题title(商品详情页面)
            $this->assign("title_before", $goods_info['goods_name']);
            $this->assign("seoconfig", $seoconfig);
            $this->assign("goods_sku_count", count($goods_info["sku_list"]));
            $this->assign("spec_list", count($goods_info["spec_list"]));
            $sku_list = $goods_info["sku_list"];
            foreach($sku_list as $skey => $sval){
                if(!$sval['attr_value_items']){
                    continue;
                }
                $sku_item = explode(';', $sval['attr_value_items']);
                $new_sku_item = ',';
                foreach($sku_item as $sival){
                    $new_sku_item .=  explode(':', $sival)[1].',';
                }
                $sku_list[$skey]['attr_value_items'] = $new_sku_item;
                $goods_info["sku_list"][$skey]['price'] = $sval['price']*$discount_num/10;
                if($goods_info["sku_list"][$skey]['price']<0.01){
                    $goods_info["sku_list"][$skey]['price'] = 0.01;
                }
            }
            $this->assign("sku_list", json_encode($goods_info["sku_list"]?:[]));
            $this->assign("speclist", json_encode($goods_info["spec_list"]?:[]));


            $default_gallery_img = ""; // 图片必须都存在才行

            for ($i = 0; $i < count($goods_info["img_list"]); $i++) {
                if ($i == 0) {
                    $default_gallery_img = $goods_info["img_list"][$i]["pic_cover_big"];
                }
            }
            $this->assign("default_gallery_img", $default_gallery_img);
            // 店内商品销量排行榜
            $goods_rank = $this->goods->getSearchGoodsList(1, 10, array(
                "category_id" => $goods_info["category_id"],
                "website_id" => $this->website_id,
                "shop_id" => $shop_id
                    ), "real_sales desc",'goods_id,goods_name, sales, real_sales, price,picture,shop_id');
            $this->assign("goods_rank", $goods_rank["data"]);

            // 店内商品收藏数排行榜
            $goods_collection = $this->goods->getSearchGoodsList(1, 10, array(
                "category_id" => $goods_info["category_id"],
                "website_id" => $this->website_id,
                "shop_id" => $shop_id,
                "collects" => ['>',0]
                    ), "collects desc",'goods_id,goods_name,collects,price,picture,shop_id');
            $this->assign("goods_collection", $goods_collection["data"]);
            // 当前用户是否收藏了该商品,uid是从baseController获取到的
            $is_member_fav_goods = -1;
            if (isset($this->uid)) {
                $is_member_fav_goods = $this->member->getIsMemberFavorites($this->uid, $goodsid, 'goods');
            }
            $this->assign("is_member_fav_goods", $is_member_fav_goods);
            
            // 评价数量
            $evaluates_count = $this->goods->getGoodsEvaluateCount($goodsid);
            $this->assign('evaluates_count', $evaluates_count);
            $goods_info['evaluates'] = $this->goods->getGoodsEvaluateDetail($goodsid);
            $integral_flag = 0; // 是否是积分商品

            if ($goods_info["point_exchange_type"] == 1) {
                $integral_flag++;
                // 积分中心-->商品详情界面
            }
            $this->assign("integral_flag", $integral_flag);
            $user_location = get_city_by_ip();
            $this->assign("user_location", get_city_by_ip()); // 获取用户位置信息
            if ($user_location['status'] == 1) {
                // 定位成功，查询当前城市的运费
                $goods_express = new GoodsExpress();
                $address = new Address();
                $province = $address->getProvinceId($user_location["province"]);
                $city = $address->getCityId($user_location["city"]);
                $district = $address->getCityFirstDistrict($city['city_id']);
                $express = $goods_express->getGoodsExpressTemplate(array(array('goods_id'=>$goodsid,'count'=>1)), $district)['totalFee'];
                $goods_info["shipping_fee_name"] = $express;
            }

            $web_info = $this->web_site->getWebSiteInfo();
            $this->assign('shipping_name', $goods_info["shipping_fee_name"]);
            if (!$goods_info["category_id"] == "") {
                $category_name = $this->goods_category->getCategoryParentQuery($goods_info["category_id"]);
            } else {
                $category_name = "全部分类";
            }
            $this->assign("category_name", $category_name);

            //获取商品的优惠劵
            if (!$is_seckill && $this->isCouponOn) {
                $goods_coupon_list = $this->coupon->getGoodsCoupon([$goodsid], $this->uid);
                $this->assign("goods_coupon_list", $goods_coupon_list);
                // 获取商品优惠券数量
                $coupon_count = count($goods_coupon_list);
                $this->assign('coupon_count', $coupon_count);
                $this->assign('fetchCouponTypeUrl', __URL(addons_url('coupontype://Coupontype/userArchiveCoupon')));
            }
            $this->assign("goods_info", $goods_info);
            // 商品品牌
            $goods_brand = new GoodsBrand();
            $brand_detial = $goods_brand->getGoodsBrandInfo($goods_info['brand_id']);
            $this->assign("brand_detial", $brand_detial);
            $com = new Common($shop_id, $this->website_id);
            $pcCustomConfig = new SysPcCustomConfigModel();
            if (!request()->isMobile()) {
                //使用模板
                $usedTem = $pcCustomConfig->getInfo(['type' => 2, 'template_type' => 'goods_templates', 'shop_id' => $shop_id, 'website_id' => $this->website_id], 'code');
                $suffix = (isset($usedTem['code']) ? trim($usedTem['code']) : '');
                if (empty($suffix)) {
                    //默认模板
                    $defaultTem = $pcCustomConfig->getInfo(['type' => 1, 'template_type' => 'goods_templates', 'shop_id' => $shop_id, 'website_id' => $this->website_id], 'code');
                    $suffix = (isset($defaultTem['code']) ? trim($defaultTem['code']) : '');
                }
            }
            $dir = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/shop_' . $shop_id . '/goods_templates/' . $suffix;
            $dir_common = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/common';
            $dir_shop_common = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/shop_' . $shop_id . '/common';

            if (!request()->isMobile() && $suffix && file_exists($dir)) {
                $page = $com->get_html_file($dir . '/pc_html.php');
                $nav_page = $com->get_html_file($dir . '/nav_html.php');
                $bottom = $com->get_html_file($dir_common . '/bottom_html.php');
                $logo_pic = $com->getLogo($suffix);
                $categories_pro = $com->get_category_tree_leve_one(0);
                $ntype = 'index';
                if($this->shopStatus){
                    $shopBanner = $com->get_html_file($dir_shop_common . '/shopbanner_html.php');
                    $ntype = 'shop';
                }
                $navigator_list = $com->get_navigator($ntype);
                $pc_page['tem'] = $suffix;
                $this->assign('pc_page', $pc_page);
                $this->assign('nav_page', $nav_page);
                $this->assign('page', $page);
                $this->assign('logo_pic', $logo_pic);
                $this->assign('categories_pro', $categories_pro);
                $this->assign('navigator_list', $navigator_list);
                $this->assign('shopBanner', $shopBanner);
                $this->assign('bottom', $bottom);
                $this->assign('ntype', $ntype);
            }
            //返积分
            $config = new Config();
            $config_info = $config->getShopConfig(0,$this->website_id);
            $give_point = [];
            $give_point['is_point'] = $config_info['is_point'];
            $give_point['point'] = 0;
            if($config_info['is_point']==1){
                if($goods_info['point_return_max']>0 || $goods_info['point_return_max']==''){
                    $price = 0;
                    if ($config_info['integral_calculation'] == 1 || $config_info['integral_calculation'] == 3) {
                        if($is_seckill){
                            $price = $goods_info['price'] + $goods_info['shipping_fee'];
                        }else{
                            $price = ($goods_info['member_price']*$discount_num / 10) + $goods_info['shipping_fee'];
                        }
                    } elseif ($config_info['integral_calculation'] == 2) {
                        if($is_seckill){
                            $price = $goods_info['price'];
                        }else{
                            $price = $goods_info['member_price']*$discount_num / 10;
                        }
                    }
                    if($goods_info['point_return_max']>0){
                        $return_point = $price*$goods_info['point_return_max']/100;
                    }else{
                        $return_point = $price*$config_info['point_invoice_tax']/100;
                    }
                    $give_point['point'] = floor($return_point);
                }else{
                    $give_point['is_point'] = 0;
                }
            }
            $this->assign('give_point', $give_point);
            if($this->uid){
                if($is_seckill){
                    $price = $goods_info['price'] + $goods_info['shipping_fee'];
                }else{
                    $price = ($goods_info['member_price']*$discount_num / 10) + $goods_info['shipping_fee'];  
                }
                $member = new VslMemberModel();
                $distribution=$member->getInfo(['uid'=>$this->uid],'isdistributor');
                if($distribution['isdistributor']==2){
                    $distribution = new Distributor();
                    $info = $distribution->getGoodsCommission($this->website_id,$goodsid,$this->uid,$price);
                    $commission = $info['commission'];
                    $dis_point = $info['point'];
                }else{
                    $commission = '';
                    $dis_point = '';
                }
            }else{
                $commission = '';
                $dis_point = '';
            }
            $this->assign('commission', $commission);
            $this->assign('dis_point', $dis_point);
            //客服系统
            $this->assign("is_qlkefu", $this->is_qlkefu);
            if($this->is_qlkefu){
                $website_id = $this->website_id;
                $config = new Qlkefu();
                $qlkefu = $config->qlkefuConfig($website_id,$goods_info['shop_id']);
                $seller_domain = '';
                if($qlkefu['w_domain'] && $qlkefu['seller_code'] && $qlkefu['is_use']==1){
                    $seller_domain = $qlkefu['w_domain'].'/index/index/chatBoxJs/u/'.$qlkefu['seller_code'];
                }
                $this->assign("seller_domain", $seller_domain);
            }
            // 基础-->商品详情界面
            return view($this->style . 'ShopList/shopDetails');
            
        } else {
            $redirect = __URLS(__URL__ . '/index');
            $this->redirect($redirect);
        }
    }

    /**
     * 根据定位查询当前商品的运费
     */
    public function getShippingFeeNameByLocation() {
        $goods_id = request()->post("goods_id", "");
        $goods_sku_list = request()->post("goods_sku_list", "");

        $res = [];
        if (!empty($goods_id)) {
            $goods_express = new GoodsExpress();
            $address = new Address();
            $order = new OrderService();
            $promotion = new Promotion();

            $user_location = get_city_by_ip();
            $res['user_location'] = $user_location;

            if ($user_location['status'] == 1) {

                // 定位成功，查询当前城市的运费
                $province = $address->getProvinceId($user_location["province"]);
                $city = $address->getCityId($user_location["city"]);
                $district = $address->getCityFirstDistrict($city['city_id']);
                $express = $goods_express->getGoodsExpressTemplate(array(array('goods_id'=>$goods_id,'count'=>1)), $district)['totalFee'];
                $res['express'] = $express;

                $count_money = $order->getGoodsSkuListPrice($goods_sku_list); // 商品金额
                $promotion_full_mail = $promotion->getPromotionFullMail($this->instance_id);
                $no_mail = checkIdIsinIdArr($city['city_id'], $promotion_full_mail['no_mail_city_id_array']);
                if ($no_mail) {
                    $promotion_full_mail['is_open'] = 0;
                }

                if ($promotion_full_mail['is_open'] == 1) {
                    //满额包邮开启
                    if ($count_money >= $promotion_full_mail["full_mail_money"]) {
                        $res['express'] = "免邮";
                    }
                }
            }
        }
        return $res;
    }
    /**
     * 根据地区获取物流模板
     */
    public function selcectexpress() {
        $goods_express = new GoodsExpress();
        $order = new OrderService();
        $promotion = new Promotion();
        $goods_id = request()->post("goods_id", '');
        $province_id = request()->post("province_id", '');
        $city_id = request()->post("city_id", '');
        $district_id = request()->post("disctrict_id", 0);
        $count = request()->post("count", 0);
        $goods_sku_list = request()->post("goods_sku_list", "");
        $express = $goods_express->getGoodsExpressTemplate(array(array('goods_id'=>$goods_id,'count'=>$count)), $district_id)['totalFee'];
        $count_money = $order->getGoodsSkuListPrice($goods_sku_list); // 商品金额
        $promotion_full_mail = $promotion->getPromotionFullMail($this->instance_id);
        $no_mail = checkIdIsinIdArr($city_id, $promotion_full_mail['no_mail_city_id_array']);
        if ($no_mail) {
            $promotion_full_mail['is_open'] = 0;
        }

        if ($promotion_full_mail['is_open'] == 1) {
            //满额包邮开启
            if ($count_money >= $promotion_full_mail["full_mail_money"]) {
                $express = "免邮";
            }
        }
        return $express;
    }

    /**
     * 根据地址获取邮费
     */
    public function getExpressFee() {
        $goods_express = new GoodsExpress();
        $promotion = new Promotion();
        $order = new OrderService();
        $goods_id = request()->post('goods_id', '');
        $province = request()->post('province_id', '');
        $goods_sku_list = request()->post("goods_sku_list", "");
        $city = request()->post('city_id', '');
        $district_id = request()->post("disctrict_id", 0);
        $express = $goods_express->getGoodsExpressTemplate($goods_id,$district_id)['totalFee'];
        $count_money = $order->getGoodsSkuListPrice($goods_sku_list); // 商品金额
        $promotion_full_mail = $promotion->getPromotionFullMail($this->instance_id);
        $no_mail = checkIdIsinIdArr($city, $promotion_full_mail['no_mail_city_id_array']);
        if ($no_mail) {
            $promotion_full_mail['is_open'] = 0;
        }

        if ($promotion_full_mail['is_open'] == 1) {
            //满额包邮开启
            if ($count_money >= $promotion_full_mail["full_mail_money"]) {
                $express = "免邮";
            }
        }
        return $express;
    }

    /**
     * 商品列表
     * @return \think\response\View
     */
    public function goodsList() {
        $category_id = htmlspecialchars(request()->get('category_id', '')); // 商品分类
        $keyword = request()->get('keyword', ''); // 关键词
        $shipping_fee = request()->get('shipping_fee', ''); // 是否包邮，0：包邮；1：运费价格
        $stock = request()->get('stock', ''); // 仅显示有货，大于0
        $page = request()->get('page', '1'); // 当前页
        $order = request()->get('order', '');
        $sort = request()->get('sort', 'desc');
        $brand_id = request()->get('brand_id', '');
        $brand_name = request()->get('brand_name', ''); // 品牌名牌
        $min_price = request()->get('min_price', ''); // 价格区间,最小
        $max_price = request()->get('max_price', ''); // 最大
        $platform_proprietary = request()->get('platform_proprietary', ''); // 平台自营 shopid== 1
        $province_id = request()->get('province_id', ''); // 商品所在地
        $province_name = request()->get('province_name', ''); // 所在地名称
        // 属性筛选get参数
        $attr = request()->get('attr', ''); // 属性值
        $spec = request()->get('spec', ''); // 规格值
        $this->assign("attr_str", $attr);
        $this->assign("spec_str", $spec);
        // 将属性条件字符串转化为数组
        $attr_array = $this->stringChangeArray($attr);
        $this->assign("attr_array", $attr_array);
        // 规格转化为数组
        if ($spec != "") {
            $spec_array = explode(";", $spec);
        } else {
            $spec_array = array();
        }
        $spec_remove_array = array();
        foreach ($spec_array as $k => $v) {
            $spec_remove_array[] = explode(":", $v);
        }
        $orderby = ""; // 排序方式 默认按排序号倒序，创建时间倒序排列
        if ($order != "") {
            $orderby = $order . " " . $sort;
        } else {
            $orderby = "ng.sort desc,ng.create_time desc";
        }
        $this->assign("order", $order); // 要排序的字段
        $this->assign("sort", $sort); // 升序降序
        $this->goods_category = new GoodsCategoryService();
        if ($category_id != "") {
            // 获取商品分类下的品牌列表、价格区间
            // 查询品牌列表，用于筛选
            $category_brands = $this->goods_category->getGoodsCategoryBrands($category_id);
            // 查询价格区间，用于筛选
            $category_price_grades = $this->goods_category->getGoodsCategoryPriceGrades($category_id);
            $category_count = 0; // 默认没有数据
            if ($category_brands != "") {
                $category_count = 1; // 有数据
            }
            $this->goods = new GoodsService();
            $goods_category_info = $this->goods_category->getGoodsCategoryDetail($category_id);
            $attr_id = $goods_category_info["attr_id"];
            // 查询商品分类下的属性和规格集合
            $goods_attribute = $this->goods->getAttributeInfo([
                "attr_id" => $attr_id,
                "website_id" => $this->website_id
            ]);
            $attribute_detail = $this->goods->getAttributeServiceDetail($attr_id, [
                'is_search' => 1,
                'website_id' => $this->website_id
            ]);
            $attribute_list = array();
            if (!empty($attribute_detail['value_list']['data'])) {
                $attribute_list = $attribute_detail['value_list']['data'];
                foreach ($attribute_list as $k => $v) {
                    $is_unset = 0;
                    if (!empty($attr_array)) {
                        foreach ($attr_array as $t => $m) {
                            if (trim($v["attr_value_id"]) == trim($m[2])) {
                                unset($attribute_list[$k]);
                                $is_unset = 1;
                            }
                        }
                    }
                    if ($is_unset == 0) {
                        $value_items = explode(",", $v['value']);
                        $attribute_list[$k]['value'] = trim($v["value"]);
                        $attribute_list[$k]['value_items'] = $value_items;
                    }
                }
            }
            $attr_list = $attribute_list;

            // 查询本商品类型下的关联规格
            $goods_spec_array = array();
            if ($goods_attribute["spec_id_array"] != "") {
                $goods_spec_array = $this->goods->getGoodsSpecQuery([
                    "spec_id" => [
                        "in",
                        $goods_attribute["spec_id_array"]
                    ],
                    "website_id" => $this->website_id
                ]);
                foreach ($goods_spec_array as $k => $v) {
                    if (!empty($spec_remove_array)) {
                        foreach ($spec_remove_array as $t => $m) {
                            if ($v["spec_id"] == $m[0]) {
                                $spec_remove_array[$t][2] = $v["spec_name"];
                                foreach ($v["values"] as $z => $c) {
                                    if ($c["spec_value_id"] == $m[1]) {
                                        $spec_remove_array[$t][3] = $c["spec_value_name"];
                                    }
                                }
                                unset($goods_spec_array[$k]);
                            }
                        }
                    }
                }
                sort($goods_spec_array);
            }
            $this->assign("attr_or_spec", $attr_list);
            $this->assign("category_brands", $category_brands);
            $this->assign("category_count", $category_count);
            $this->assign("category_price_grades", $category_price_grades);
            $this->assign("category_price_grades_count", count($category_price_grades));
            $this->assign("goods_spec_array", $goods_spec_array); // 分类下的规格
        }
        // 新品推荐
        $this->goods = new GoodsService();
        if ($category_id != "") {
            $goods_new_list = $this->goods->getSearchGoodsList(1, 5, [
                "state" => 1,
                "category_id" => $category_id,
                "website_id" => $this->website_id
                    ], "create_time desc",'goods_id,goods_name,price,sales, real_sales, picture,shop_id');
        } else {
            $goods_new_list = $this->goods->getSearchGoodsList(1, 5, [
                "state" => 1,
                "website_id" => $this->website_id
                    ], "create_time desc",'goods_id,goods_name,price,real_sales,sales,picture,shop_id');
        }
        $this->assign("goods_new_list", $goods_new_list['data']);
        // -----------------查询条件筛选---------------------
        $this->assign("category_id", $category_id); // 商品分类ID
        $this->assign("brand_id", $brand_id); // 品牌ID
        $this->assign("brand_name", $brand_name); // 品牌ID
        $this->assign("min_price", $min_price); // 最小
        $this->assign("max_price", $max_price); // 最大
        $this->assign("shipping_fee", $shipping_fee); // 是否包邮
        $this->assign("stock", $stock); // 仅显示有货
        $this->assign("platform_proprietary", $platform_proprietary); // 平台自营
        $this->assign("province_name", $province_name);
        $page_size = 24;
        // -----------------查询条件筛选----------------------

        $url = request()->url(true); // get参数
        $url_parameter = explode('?', $url); // 筛选属性参数
        if (!empty($url_parameter[1])) {
            $url_parameter_array = explode("&", $url_parameter[1]);
        } else {
            $url_parameter_array = array();
        }

        // 去除参数中的规格 属性参数
        foreach ($url_parameter_array as $k => $v) {
            if (strpos($v, "attr") === 0) {
                unset($url_parameter_array[$k]);
            } else if (strpos($v, "spec") === 0) {
                unset($url_parameter_array[$k]);
            } else if(strpos($v, "s=/") === 0){
                unset($url_parameter_array[$k]);
            } else if(strpos($v, "website_id") === 0){
                unset($url_parameter_array[$k]);
            }
        }
        $url_parameter_array = array_unique($url_parameter_array);
        $url_parameter = implode("&", $url_parameter_array);
        $attr_url = "";

        if ($attr != "") {
            $attr_url .= "&attr=$attr";
        }
        if ($spec != "") {
            $attr_url .= "&spec=$spec";
        }

        $this->assign("attr_url", $attr_url);
        $url_parameter_not_shipping = str_replace("&shipping_fee=0", "", $url_parameter); // 筛选：排除包邮
        $url_parameter_not_price = str_replace("&min_price=$min_price&max_price=$max_price", "", $url_parameter); // 筛选：排除价格区间
        $url_brand_name = str_replace("%2C", ",", rawurlencode($brand_name));
        $url_parameter_not_brand = str_replace("&brand_id=$brand_id&brand_name=" . $url_brand_name . "", "", $url_parameter); // 筛选：排除品牌
        $url_parameter_not_stock = str_replace("&stock=$stock", "", $url_parameter); // 筛选：排除仅显示有货
        $url_parameter_not_platform_proprietary = str_replace("&platform_proprietary=$platform_proprietary", "", $url_parameter); // 筛选：排除平台自营
        $url_parameter_not_order = str_replace("&order=$order&sort=$sort", "", $url_parameter); // 排序，排除之前的排序规则，防止重复，
        $url_parameter_not_province_id = str_replace("&province_id=$province_id&province_name=" . urlencode($province_name) . "", "", $url_parameter); // 排序，排除之前的排序规则，防止重复，

        $this->assign("url_parameter", $url_parameter); // 正常
        $this->assign("url_parameter_not_order", $url_parameter_not_order); // 排序，排除之前的排序规则，防止重复，
        $this->assign("url_parameter_not_shipping", $url_parameter_not_shipping); // 筛选：排除包邮
        $this->assign("url_parameter_not_price", $url_parameter_not_price . $attr_url); // 筛选：排除价格，
        $this->assign("url_parameter_not_brand", $url_parameter_not_brand . $attr_url); // 筛选：排除品牌
        $this->assign("url_parameter_not_stock", $url_parameter_not_stock); // 筛选：排除仅显示有货
        $this->assign("url_parameter_not_platform_proprietary", $url_parameter_not_platform_proprietary); // 筛选：排除平台自营
        $this->assign("url_parameter_not_province_id", $url_parameter_not_province_id); // 筛选：排除平台自营
        $this->assign("user_location", get_city_by_ip()); // 获取用户位置信息
        $goods_list = $this->getGoodsListByConditions($category_id, $brand_id, $min_price, $max_price, $keyword, $page, $page_size, $orderby, $shipping_fee, $stock, $platform_proprietary, $province_id, $attr_array, $spec_array);
        $this->assign("goods_list", $goods_list); // 返回商品列表
        $category_name = "";
        if (!$category_id == "") {
            $category = $this->goods_category->getCategoryParentQuery($category_id);
            $category_name = reset($category)['category_name'];
        } else {
            $category_name = "全部分类";
        }
        $this->assign("title_before", $keyword ? $keyword : $category_name);
        // if (count($goods_list["data"]) > 0) {
        // $category_name = $goods_list["data"][0]["category_name"]; // 面包屑
        // }
//        print_r($goods_list['data'][0]);die;
        $this->assign("spec_array", $spec_remove_array);
        $this->assign("category_name", $category_name);
        $this->assign('page_count', $goods_list['page_count']);
        $this->assign('total_count', $goods_list['total_count']);
        $this->assign('page', $page);
        return view($this->style . 'ShopList/shopSearch');
    }

    /**
     * 将属性字符串转化为数组
     *
     * @param unknown $string
     * @return multitype:multitype: |multitype:
     */
    private function stringChangeArray($string) {
        if (trim($string) != "") {
            $temp_array = explode(";", $string);
            $attr_array = array();
            foreach ($temp_array as $k => $v) {
                $v_array = array();
                if (strpos($v, ",") === false) {
                    $attr_array = array();
                    break;
                } else {
                    $v_array = explode(",", $v);
                    if (count($v_array) != 3) {
                        $attr_array = array();
                        break;
                    } else {
                        $attr_array[] = $v_array;
                    }
                }
            }
            return $attr_array;
        } else {
            return array();
        }
    }

    /**
     * 获取所有地址：省市县
     */
    public function getProvince() {
        // 省
        $data = cache('province');
        if(!$data){
            $address = new Address();
            $province_list = $address->getProvinceList();
            cache('province', $province_list);
        }
        return $data;
    }
    /**
     * 获取所有地址：省市县
     */
    public function getCity() {
        $data = cache('city');
        
        if(!$data){
            $address = new Address();
            $city_list = $address->getCityList();
            cache('city', $city_list);
        }
        return $data;
    }
    /**
     * 获取所有地址：省市县
     */
    public function getDistrict() {
        $city_id = request()->post('city_id', 0);
        $address = new Address();
        // 区县
        $district_list = $address->getDistrictList($city_id);
        return $district_list;
    }

    /**
     * 查询商品的sku信息
     */
    public function getGoodsSkuInfo() {
        $goods_id = request()->post('goods_id', '');
        $this->goods = new GoodsService();
        return $this->goods->getGoodsAttribute($goods_id);
    }

    /**
     * 右侧边栏-->我看过的
     */
    public function getMemberHistories() {
        // 浏览历史
        $this->member = new MemberService();
        $member_histrorys = $this->member->getMemberViewHistory();
        return $member_histrorys;
    }
    /**
     * 右侧边栏-->我看过的
     */
    public function getGuessMemberLikes() {
        // 浏览历史
        $this->member = new MemberService();
        $member_likes = $this->member->getGuessMemberLikes();
        return $member_likes;
    }

    /**
     * 功能：ajax删除浏览记录
     */
    public function deleteMemberHistory() {
        $this->member = new MemberService();
        $this->member->deleteMemberViewHistory();
        return AjaxReturn(1);
    }

    /**
     * 根据条件查询商品列表：商品分类查询，关键词查询，价格区间查询，品牌查询
     */
    public function getGoodsListByConditions($category_id, $brand_id, $min_price, $max_price, $keyword, $page, $page_size, $order, $shipping_fee, $stock, $platform_proprietary, $province_id, $attr_array, $spec_array) {
        $this->goods = new GoodsService();
        $condition = null;
        $condition["ng.website_id"] = $this->website_id;
        if ($category_id != "") {
            // 商品分类Id
            $condition["ng.category_id"] = $category_id;
        }
        // 品牌Id
        if ($brand_id != "") {
            $condition["ng.brand_id"] = array(
                "in",
                $brand_id
            );
        }

        // 价格区间
        if ($max_price != "") {
            $condition["ng.promotion_price"] = [
                [
                    ">=",
                    $min_price
                ],
                [
                    "<=",
                    $max_price
                ]
            ];
        }
        // 关键词
        if ($keyword != "") {
            $condition["ng.goods_name"] = array(
                "like",
                "%" . $keyword . "%"
            );
        }

        // 包邮
        if ($shipping_fee != "") {
            $condition["ng.shipping_fee"] = $shipping_fee;
        }

        // 仅显示有货
        if ($stock != "") {
            $condition["ng.stock"] = array(
                ">",
                $stock
            );
        }

        // 平台直营
        if ($platform_proprietary != "") {
            $condition["ng.shop_id"] = $platform_proprietary;
        }
        // 商品所在地
        if ($province_id != "") {
            $condition["ng.province_id"] = $province_id;
        }
        // 属性 (条件拼装)
        $array_count = count($attr_array);
        $goodsid_str = "";
        $attr_str_where = "";
        if (!empty($attr_array)) {
            // 循环拼装sql属性条件
            foreach ($attr_array as $k => $v) {
                if ($attr_str_where == "") {
                    $attr_str_where = "(attr_value_id = '$v[2]' and attr_value_name='$v[1]')";
                } else {
                    $attr_str_where = $attr_str_where . " or " . "(attr_value_id = '$v[2]' and attr_value_name='$v[1]')";
                }
            }
            if ($attr_str_where != "") {
                $attr_query = $this->goods->getGoodsAttributeQuery($attr_str_where);

                $attr_array = array();
                foreach ($attr_query as $t => $b) {
                    $attr_array[$b["goods_id"]][] = $b;
                }
                $goodsid_str = "0";
                foreach ($attr_array as $z => $x) {
                    if (count($x) == $array_count) {
                        if ($goodsid_str == "") {
                            $goodsid_str = $z;
                        } else {
                            $goodsid_str = $goodsid_str . "," . $z;
                        }
                    }
                }
            }
        }

        // 规格条件拼装
        $spec_count = count($spec_array);
        $spec_where = "";
        if ($spec_count > 0) {
            foreach ($spec_array as $k => $v) {
                if ($spec_where == "") {
                    $spec_where = " attr_value_items_format like '%{$v}%' ";
                } else {
                    $spec_where = $spec_where . " or " . " attr_value_items_format like '%{$v}%' ";
                }
            }

            if ($spec_where != "") {

                $goods_query = $this->goods->getGoodsSkuQuery($spec_where);
                $temp_array = array();
                foreach ($goods_query as $k => $v) {
                    $temp_array[] = $v["goods_id"];
                }
                $goods_query = array_unique($temp_array);
                if (!empty($goods_query)) {
                    if ($goodsid_str != "") {
                        $attr_con_array = explode(",", $goodsid_str);
                        $goods_query = array_intersect($attr_con_array, $goods_query);
                        $goods_query = array_unique($goods_query);
                        $goodsid_str = "0," . implode(",", $goods_query);
                    } else {
                        $goodsid_str = "0,";
                        $goodsid_str .= implode(",", $goods_query);
                    }
                } else {
                    $goodsid_str = "0";
                }
            }
        }
        if ($goodsid_str != "") {
            $condition["ng.goods_id"] = [
                "in",
                $goodsid_str
            ];
        }

        $condition['ng.state'] = 1;
        if($this->shopStatus==0){
            $condition["ng.shop_id"] = 0;
        }
        $list = $this->goods->getGoodsList($page, $page_size, $condition, $order);
        if (!empty($list['data'])) {
            if($this->is_seckill){
                $seckill_server = new Seckill();
            }
            // 用户针对商品的收藏
            foreach ($list['data'] as $k => $v) {
                if (!empty($this->uid)) {
                    $this->member = new MemberService();
                    $list['data'][$k]['is_favorite'] = $this->member->getIsMemberFavorites($this->uid, $v['goods_id'], 'goods');
                } else {
                    $list['data'][$k]['is_favorite'] = 0;
                }
                if($this->is_seckill){
                    //判断商品是否是秒杀商品
                    $goods_id = $v['goods_id'];
                    $seckill_condition['nsg.goods_id'] = $goods_id;
                    $is_seckill = $seckill_server->isSkuStartSeckill($seckill_condition);
                    if ($is_seckill) {//如果是秒杀商品，则将其价格改为其中sku的最低价格
                        $list['data'][$k]['price'] = $is_seckill['seckill_price'];
                        //将sku的库存信息修改
                        $seckill_mdl = new VslSeckillModel();
                        foreach($v['sku_list'] as $k1=>$v1){
                            $sku_id = $v1['sku_id'];
                            $seckill_id = $is_seckill['seckill_id'];
                            $condition_seckill['nsg.sku_id'] = $sku_id;
                            $condition_seckill['ns.seckill_id'] = $seckill_id;
                            //获取秒杀sku信息
                            $seckill_sku_info = $seckill_mdl->getSeckillGoodsList($condition_seckill);
                            $seckill_limit_buy = $seckill_sku_info[0]['seckill_limit_buy'];
                            $seckill_stock = $seckill_sku_info[0]['remain_num'];
                            $stock = $seckill_limit_buy>$seckill_stock?$seckill_stock:$seckill_limit_buy;
                            $list['data'][$k]['sku_list'][$k1]['stock'] = $stock;
                            $list['data'][$k]['sku_list'][$k1]['price'] = $seckill_sku_info[0]['seckill_price'];
                            $list['data'][$k]['sales'] += $seckill_sku_info[0]['seckill_sales'];
                        }
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 根据关键词返回商品列表
     */
    public function getGoodsListByKeyWord() {
        $page_index = 1;
        $page_size = 0;
        $keyword = request()->get('keyword');
        $order = "";
        $list = null;
        $condition = [];
        $this->goods = new GoodsService();
        $condition["ng.website_id"] = $this->website_id;
        if($this->shopStatus==0){
            $condition["ng.shop_id"] = 0;
        }
        if ($keyword) {
            $page_index = request()->get('page_index', 1);
            $page_size = request()->get('page_size', 0);
            $condition = array(
                "ng.goods_name" => array(
                    "like",
                    "%" . $keyword . "%"
                ),
                "ng.website_id" => $this->website_id
            );
            $order = request()->get('order', '');
            $list = $this->goods->getGoodsViewList($page_index, $page_size,$condition, $order);
        } else {
            // 没有条件，查询全部
            $list = $this->goods->getGoodsViewList($page_index, $page_size, $condition, $order);
        }
        return $list;
    }

    /**
     * 获取销量排行榜的商品列表
     */
    public function getSalesGoodsList($category_id) {
        $this->goods = new GoodsService();
        $list = $this->goods->getGoodsViewList(1, 3, [
            "ng.state" => 1,
            "ng.website_id" => $this->website_id
                ], "sales desc");
        return $list["data"];
    }

    /**
     * 全部商品分类
     *
     * @return \think\response\View
     */
    public function category() {
        return view($this->style . 'GoodSort/goodSort');
    }

    /**
     * 商品信息
     */
    public function getGoodsInfo() {
        $this->member = new MemberService();
        $list = $this->member->getMemberViewHistory();
    }

    /**
     * 功能：商品评论
     */
    public function getGoodsComments() {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', 0);
        $goods_id = request()->post('goods_id', '');
        $comments_type = request()->post('comments_type', '');
        $order = new OrderService();
        $condition['goods_id'] = $goods_id;
        switch ($comments_type) {
            case 1:
                $condition['explain_type'] = 5;
                break;
            case 2:
                $condition['explain_type'] = 3;
                break;
            case 3:
                $condition['explain_type'] = 1;
                break;
            case 4:
                $condition['image|again_image'] = array(
                    'NEQ',
                    ''
                );
                break;
        }
        $condition['is_show'] = 1;
        $goodsEvaluationList = $order->getOrderEvaluateDataList($page_index, $page_size, $condition, 'addtime desc');
        $memberService = new UserModel();
        foreach ($goodsEvaluationList['data'] as $K=>$v) {
            $info = $memberService->getInfo(['uid'=>$v['uid']],'*');
            $goodsEvaluationList['data'][$K]["user_img"] = $info['user_headimg'];
            if(empty($info["user_name"])){
                $goodsEvaluationList['data'][$K]["member_name"] = $info["nick_name"];
            }else{
                $goodsEvaluationList['data'][$K]["member_name"] = $info["user_name"];
            }
        }
        return $goodsEvaluationList;
    }
    
    /**
     * 获取商品详情
     */
    public function getGoodsDetail() {
        $goods = new GoodsService();
        $goods_id = request()->post('goods_id', '');
        $goods_detail = $goods->getGoodsDetail($goods_id);
        return $goods_detail;
    }

    /**
     * 添加购物车
     */
    public function addCart() {
        $goods = new GoodsService();
        $uid = $this->uid;
        $cart_detail = $_POST['cart_detail'];
        $seckill_id = $_POST['seckill_id']?:0;
        if (is_string($cart_detail)) {
            $cart_detail = json_decode($cart_detail, true);
        }
        $goods_id = $cart_detail['goods_id'];
        $goods_name = $cart_detail['goods_name'];
        $shop_id = $this->instance_id;
        $count = $cart_detail['count'];
        $sku_id = $cart_detail['sku_id'];
        $sku_name = $cart_detail['sku_name'];
        $price = $cart_detail['price'];
        $cost_price = $cart_detail['cost_price'];
        $picture_id = $cart_detail['picture_id'];
        $_SESSION['order_tag'] = ""; // 清空订单
        $retval = $goods->addCart($uid, $shop_id, $goods_id, $goods_name, $sku_id, $sku_name, $price, $count, $picture_id, 0, $seckill_id);
        return $retval;
    }
    /**
     * 再次购买
     */
    public function againCart() {
        $goods = new GoodsService();
        $uid = $this->uid;
        $cart_detail = $_POST['cart_detail'];
        if (is_string($cart_detail)) {
            $cart_detail = json_decode($cart_detail, true);
        }
        foreach ($cart_detail  as $k=>$v){
            $shop_id = $this->instance_id;
            $count = $v['num'];
            $sku_id = $v['sku_id'];
            $skuModel = new VslGoodsSkuModel();
            $sku_info = $skuModel::get(['sku_id'=>$v['sku_id']],['goods']);
            $sku_name = $sku_info['sku_name'];
            $price = $sku_info['price'];
            $picture_id = $sku_info['goods']['picture'];
            $goods_id = $sku_info['goods_id'];
            $good = new VslGoodsModel();
            $goods_info = $good->getInfo(['goods_id'=>$goods_id,'state'=>['neq',1]]);
            if($goods_info){
                return -2;
            }
            $goods_name = $sku_info['goods']['goods_name'];
            $_SESSION['order_tag'] = ""; // 清空订单
            $retval = $goods->addCart($uid, $shop_id, $goods_id, $goods_name, $sku_id, $sku_name, $price, $count, $picture_id, 0);
        }
        return $retval;
    }
    /**
     * 购物车
     * @return \think\response\View
     */
    public function cart() {
        $goods = new GoodsService();
        $cart_list = $goods->getCart($this->uid);
        $discount_service = $this->is_discount ? new Discount() : '';
        $list = Array();
        for ($i = 0; $i < count($cart_list); $i++) {
            $list[$cart_list[$i]["shop_id"] . ',' . $cart_list[$i]["shop_name"]][] = $cart_list[$i];
        }
        foreach ($list as $key => &$value) {
            foreach ($value as $k => &$v) {
                if ($this->is_discount){
                    $limit_discount_info = $discount_service->getPromotionInfo($v['goods_id'], $v['shop_id'], $v['website_id']);
                    if($limit_discount_info['integer_type'] == 1){
                        $v['price'] = round($limit_discount_info['discount_num'] / 10 * $v['price']);
                    }else{
                        $v['price'] = round($limit_discount_info['discount_num'] / 10 * $v['price'], 2);
                    }
                    if($limit_discount_info['discount_type'] ==2){
                        $v['price'] = $limit_discount_info['discount_num'];
                    }
                }
            }
            unset($v);
        }
        unset($value);
        $this->assign("list", $list);
        $this->assign("cart_list", $cart_list);
        $this->assign("title_before", "购物车");
        $this->assign('getGoodsCouponTypeUrl', __URL(addons_url('coupontype://Coupontype/getGoodsCouponType')));
        $this->assign('fetchCouponTypeUrl', __URL(addons_url('coupontype://Coupontype/userArchiveCoupon')));
        return view($this->style . 'Cart/cart');
    }

    /**
     * 获取购物车信息
     * {@inheritdoc}
     *
     * @see \app\shop\controller\BaseController::getShoppingCart()
     */
    public function getShoppingCart() {
        $goods = new GoodsService();
        $cart_list = $goods->getCart($this->uid);
        return json($cart_list);
    }

    /**
     * 根据cartid删除购物车中的商品
     *
     * @return unknown
     */
    public function deleteShoppingCartById() {
        $goods = new GoodsService();
        $cart_id_array = request()->post('cart_id_array', '');
        $res = $goods->cartDelete($cart_id_array);
        $_SESSION['order_tag'] = ""; // 清空订单
        return AjaxReturn($res);
    }

    /**
     * 更新购物车中商品数量
     *
     * @return unknown
     */
    public function updateCartGoodsNumber() {
        $goods = new GoodsService();
        $cart_id = request()->post('cart_id', '');
        $num = request()->post('num', '');
        $_SESSION['order_tag'] = ""; // 清空订单
        $res = $goods->cartAdjustNum($cart_id, $num);
        return $res;
    }

    /**
     * 随机获取商品列表
     */
    public function getRandGoodsListAjax() {
        if (request()->isAjax()) {
            $goods = new GoodsService();
            $res = $goods->getRandGoodsList();
            return $res;
        }
    }

    /**
     * 领取商品优惠劵
     */
    public function receiveGoodsCoupon() {
        if (request()->isAjax()) {
            $couponServer = new CouponServer();
            $coupon_type_id = request()->post("coupon_type_id", '');
            $res = $couponServer->userAchieveCoupon($this->uid, $coupon_type_id, 3);
            return AjaxReturn($res);
        }
    }

    /**
     * 得到当前时间戳的毫秒数
     *
     * @return number
     */
    public function getCurrentTime() {
        $time = time();
        $time = $time * 1000;
        return $time;
    }
    
    /*
     * 获取商品描述
     */
    public function getDescription(){
        $goods = new VslGoodsModel();
        $goods_id = request()->post('goods_id', '');
        $goodsDes = $goods->getInfo(['goods_id' => $goods_id], 'description')['description'];
        return $goodsDes;
    }
    
    public function getBaidu(){
        $cdn = request()->get('cdnversion');
        $file = file_get_contents('http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion='.$cdn);
        $file = str_replace('http:', 'https:', $file);
        echo $file;
        die;
    }
}
