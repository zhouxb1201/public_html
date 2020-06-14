<?php
/**
 * Goods.php
 *
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 *
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace data\service;

/**
 * 商品服务层
 */

use addons\bargain\service\Bargain;
use addons\channel\model\VslChannelGoodsModel;
use addons\channel\model\VslChannelGoodsSkuModel;
use addons\channel\model\VslChannelModel;
use addons\channel\model\VslChannelOrderSkuRecordModel;
use addons\channel\server\Channel;
use addons\coupontype\server\Coupon;
use addons\discount\server\Discount;
use addons\fullcut\service\Fullcut;
use addons\groupshopping\model\VslGroupGoodsModel;
use addons\groupshopping\server\GroupShopping;
use addons\integral\model\VslIntegralGoodsModel;
use addons\presell\service\Presell;
use addons\seckill\server\Seckill AS SeckillServer;
use addons\seckill\server\Seckill;
use addons\store\server\Store;
use addons\store\server\Store as storeServer;
use data\model\AlbumPictureModel as AlbumPictureModel;
use data\model\UserModel;
use data\model\VslActivityOrderSkuRecordModel;
use data\model\VslAttributeModel;
use data\model\VslAttributeValueModel;
use data\model\VslCartModel;
use data\model\VslClickFabulousModel;
use data\model\VslGoodsAttributeDeletedModel;
use data\model\VslGoodsAttributeModel;
use data\model\VslGoodsBrandModel;
use data\model\VslGoodsCategoryModel as VslGoodsCategoryModel;
use data\model\VslGoodsDeletedModel;
use data\model\VslGoodsDeletedViewModel;
use data\model\VslGoodsDiscountModel;
use data\model\VslGoodsEvaluateModel;
use data\model\VslGoodsGroupModel as VslGoodsGroupModel;
use data\model\VslGoodsModel as VslGoodsModel;
use data\model\VslGoodsSkuDeletedModel;
use data\model\VslGoodsSkuModel as VslGoodsSkuModel;
use data\model\VslGoodsSkuPictureDeleteModel;
use data\model\VslGoodsSkuPictureModel;
use data\model\VslGoodsSpecModel as VslGoodsSpecModel;
use data\model\VslGoodsSpecValueModel as VslGoodsSpecValueModel;
use data\model\VslGoodsViewModel as VslGoodsViewModel;
use data\model\VslKnowledgePaymentContentModel;
use data\model\VslMemberLevelModel;
use data\model\VslMemberModel;
use data\model\VslOrderGoodsModel;
use data\model\VslGoodsTicketModel;
use data\model\VslOrderModel;
use data\model\VslPresellGoodsModel;
use data\model\VslPromotionDiscountModel;
use addons\shop\model\VslShopModel;
use addons\groupshopping\server\GroupShopping as GroupShoppingServer;
use data\model\VslStoreCartModel;
use data\model\VslStoreGoodsSkuModel;
use data\service\BaseService as BaseService;
use data\service\Order\OrderGoods;
use data\service\promotion\GoodsDiscount;
use data\service\promotion\GoodsExpress;
use data\service\promotion\GoodsMansong;
use data\service\promotion\GoodsPreference;
use data\service\promotion\PromoteRewardRule;
use addons\presell\service\Presell as PresellService;
use data\service\Order\Order as orderServer;
use data\service\WeixinCard;
use think\Db;
use think\Request;
use data\model\VslStoreGoodsModel as VslStoreGoodsModel;

class Goods extends BaseService
{

    private $goods;
    protected $http = '';

    function __construct()
    {
        parent::__construct();
        $is_ssl = Request::instance()->isSsl();
        $this->http = "http://";
        if ($is_ssl) {
            $this->http = 'https://';
        }
        $this->goods = new VslGoodsModel();
        $this->goods_spec_model = new VslGoodsSpecModel();
        $this->goods_spec_value_model = new VslGoodsSpecValueModel();
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsList()
     */
    public function getGoodsList($page_index = 1, $page_size = 0, $condition = [], $order = 'ng.sort desc,ng.create_time desc')
    {
        $goods_view = new VslGoodsViewModel();
        $condition['nss.shop_state'] = 1;
        // 针对商品分类
        if (!empty($condition['ng.category_id'])) {
            $goods_category = new GoodsCategory();
            $category_list = $goods_category->getCategoryTreeList($condition['ng.category_id']);
            unset($condition['ng.category_id']);
            $query_goods_ids = "";
            $goods_list = $goods_view->getGoodsViewQueryField($condition, "ng.goods_id");
            if (!empty($goods_list) && count($goods_list) > 0) {
                foreach ($goods_list as $goods_obj) {
                    if ($query_goods_ids === "") {
                        $query_goods_ids = $goods_obj["goods_id"];
                    } else {
                        $query_goods_ids = $query_goods_ids . "," . $goods_obj["goods_id"];
                    }
                }
                $extend_query = "";
                $category_str = explode(",", $category_list);
                foreach ($category_str as $category_id) {
                    if ($extend_query === "") {
                        $extend_query = " FIND_IN_SET( " . $category_id . ",ng.extend_category_id) ";
                    } else {
                        $extend_query = $extend_query . " or FIND_IN_SET( " . $category_id . ",ng.extend_category_id) ";
                    }
                }
                $condition = " ng.goods_id in (" . $query_goods_ids . ") and ( ng.category_id in (" . $category_list . ") or " . $extend_query . ")";
            }
        }
        $list = $goods_view->getGoodsViewList($page_index, $page_size, $condition, $order);

        if (!empty($list['data'])) {
            // 用户针对商品的收藏
            foreach ($list['data'] as $k => $v) {
                if (!empty($this->uid) && $this->is_member) {
                    $member = new Member();
                    $list['data'][$k]['is_favorite'] = $member->getIsMemberFavorites($this->uid, $v['goods_id'], 'goods');
                } else {
                    $list['data'][$k]['is_favorite'] = 0;
                }
                if (!$v['pic_id']) {
                    $list['data'][$k]['pic_id'] = 0;
                }
                // 查询商品单品活动信息
//                $goods_preference = new GoodsPreference();
//                $goods_promotion_info = $goods_preference->getGoodsPromote($v['goods_id']);
//                $list["data"][$k]['promotion_info'] = $goods_promotion_info;

                // 查询商品标签
//                $vsl_goods_group = new VslGoodsGroupModel();
//                $group_id = 0;
//                $group_name = "";
//                if (!empty($v['group_id_array'])) {
//                    $group_id_array = explode(",", $v['group_id_array']);
//                    $group_id = $group_id_array[count($group_id_array) - 1];
//                    $group_info = $vsl_goods_group->getInfo([
//                        "group_id" => $group_id
//                    ], "group_name");
//                    if (!empty($group_info)) {
//                        $group_name = $group_info['group_name'];
//                    }
//                }
//                $list["data"][$k]['group_name'] = $group_name;
            }
        }
        return $list;

        // TODO Auto-generated method stub
    }

    /*
         * (non-PHPdoc)
         * @see \data\api\IGoods::getGoodsList()
         */
    public function getIntegralGoodsList($page_index = 1, $page_size = 0, $condition = [], $order = 'ng.sort desc,ng.create_time desc')
    {
        $integral_goods_view = new VslIntegralGoodsModel();
        $list = $integral_goods_view->getGoodsViewList($page_index, $page_size, $condition, $order);
//        echo $goods_view->getLastSql();exit;
        if (!empty($list['data'])) {
            // 用户针对商品的收藏
            foreach ($list['data'] as $k => $v) {
                if (!empty($this->uid)) {
                    $member = new Member();
                    $list['data'][$k]['is_favorite'] = $member->getIsMemberFavorites($this->uid, $v['goods_id'], 'goods');
                } else {
                    $list['data'][$k]['is_favorite'] = 0;
                }
                if (!$v['pic_id']) {
                    $list['data'][$k]['pic_id'] = 0;
                }
            }
        }
        return $list;

        // TODO Auto-generated method stub
    }

    /**
     * 直接查询商品列表
     *
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getGoodsViewList($page_index = 1, $page_size = 0, $condition = '', $order = 'ng.sort desc,ng.create_time desc')
    {
        $goods_view = new VslGoodsViewModel();
        $list = $goods_view->getGoodsViewList($page_index, $page_size, $condition, $order);
        return $list;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsCount()
     */
    public function getGoodsCount($condition)
    {
        $count = $this->goods->where($condition)->count();
        return $count;

        // TODO Auto-generated method stub
    }

    /*
 * (non-PHPdoc)
 * @see \data\api\IGoods::getGoodsInfo()
 */
    public function getGoodsInfo($condition)
    {
        $res = $this->goods->where($condition)->select();
        return $res;

        // TODO Auto-generated method stub
    }

    //获取所有图片
    public function get_all_pic($album_id)
    {

        $sql = "select a.*,b.* from `sys_album_class` as a left JOIN  `sys_album_picture` as b on a.`album_id` = b.`album_id` where a.`album_id` = $album_id";
        return Db::query($sql);
    }


    //相册分类
    public function get_album_class($website_id, $shop_id)
    {

        $sql = "select * from `sys_album_class` where `website_id` = $website_id and `shop_id` = $shop_id ";
        return Db::query($sql);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::goods_QRcode_make()
     */
    function goods_QRcode_make($goodsId, $url)
    {
        $data = array(
            'QRcode' => $url
        );
        $result = $this->goods->save($data, [
            'goods_id' => $goodsId
        ]);
        if ($result > 0) {
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    /**
     * 添加修改商品
     * goods_id '商品id(SKU)',
     * goods_name '商品名称',
     * shop_id '店铺id',
     * category_id '商品分类id',
     * category_id_1 '一级分类id',
     * category_id_2 '二级分类id',
     * category_id_3 '三级分类id',
     * brand_id int(10) '品牌id',
     * group_id_array '店铺分类id 首尾用,隔开',
     * goods_type '实物或虚拟商品标志 1实物商品 0 虚拟商品 2 F码商品',
     * market_price '市场价',
     * price '商品原价格',
     * promotion_price '商品促销价格',
     * cost_price '成本价',
     * point_exchange_type '积分兑换类型 0 非积分兑换 1 只能积分兑换 ',
     * point_exchange '积分兑换',
     * give_point '购买商品赠送积分',
     * is_member_discount '参与会员折扣',
     * shipping_fee '运费 0为免运费',
     * shipping_fee_id '售卖区域id 物流模板id vsl_order_shipping_fee 表id',
     * stock '商品库存',
     * max_buy '限购 0 不限购',
     * clicks'商品点击数量',
     * min_stock_alarm '库存预警值',
     * sales '销售数量',
     * collects '收藏数量',
     * star '好评星级',
     * evaluates '评价数',
     * shares '分享数',
     * province_id '一级地区id',
     * city_id '二级地区id',
     * picture '商品主图',
     * keywords '商品关键词',
     * introduction '商品简介',
     * description '商品详情',
     * QRcode '商品二维码',
     * code '商家编号',
     * is_stock_visible '页面不显示库存',
     *
     * state '商品状态 0下架，1正常，10违规（禁售）',
     * sale_date '上下架时间',
     * create_time '商品添加时间',
     * update_time '商品编辑时间',
     * sort '排序',
     * img_id_array '商品图片序列',
     * sku_img_array '商品sku应用图片列表 属性,属性值，图片ID',
     * match_point '实物与描述相符（根据评价计算）',
     * match_ratio '实物与描述相符（根据评价计算）百分比',
     * real_sales '实际销量',
     * goods_attribute '商品类型',
     * goods_spec_format '商品规格',
     * single_limit_buy 单次限购
     *
     * @return \data\model\VslGoodsModel|number
     */
    public function addOrEditGoods($goods_id, $goods_name, $shopid, $category_id, $category_id_1, $category_id_2, $category_id_3, $supplier_id, $brand_id,
                                   $group_id_array, $goods_type, $market_price, $price, $cost_price, $point_exchange_type, $point_exchange, $give_point, $is_member_discount,
                                   $shipping_fee, $shipping_fee_id, $stock, $max_buy, $min_buy, $min_stock_alarm, $clicks, $sales, $collects, $star, $evaluates, $shares, $province_id,
                                   $city_id, $picture, $keywords, $introduction, $description, $QRcode, $code, $is_stock_visible, $is_hot, $is_recommend, $is_new, $sort, $image_array,
                                   $sku_array, $state, $sku_img_array, $goods_attribute_id, $goods_attribute, $goods_spec_format, $goods_weight, $goods_volume, $shipping_fee_type, $extend_category_id,
                                   $sku_picture_values, $item_no, $distribution_rule_val, $distribution_rule, $is_distribution, $is_bonus_global, $is_bonus_area, $is_bonus_team, $bonus_rule_val,
                                   $bonus_rule, $is_promotion = '', $is_shipping_free = '', $is_wxcard = '', $verificationinfo = '', $card_info = '', $video_id = 0, $point_deduction_max = '', $point_return_max = '', $goods_count = 0,
                                   $single_limit_buy, $buyagain = 0, $buyagain_level_rule = 2, $buyagain_recommend_type = 1, $buyagain_distribution_val = '', $payment_content = '', $for_store = 0, $is_goods_poster_open = 0, $poster_data = '', $px_type = 1)
    {
        $error = 0;
        $category_list = $this->getGoodsCategoryId($category_id);
        // 1级扩展分类
        $extend_category_id_1s = "";
        // 2级扩展分类
        $extend_category_id_2s = "";
        // 3级扩展分类
        $extend_category_id_3s = "";
        if (!empty($extend_category_id)) {
            $extend_category_id_str = explode(",", $extend_category_id);
            foreach ($extend_category_id_str as $extend_id) {
                $extend_category_list = $this->getGoodsCategoryId($extend_id);

                if ($extend_category_id_1s === "") {
                    $extend_category_id_1s = $extend_category_list[0];
                } else {
                    $extend_category_id_1s = $extend_category_id_1s . "," . $extend_category_list[0];
                }
                if ($extend_category_id_2s === "") {
                    $extend_category_id_2s = $extend_category_list[1];
                } else {
                    $extend_category_id_2s = $extend_category_id_2s . "," . $extend_category_list[1];
                }
                if ($extend_category_id_3s === "") {
                    $extend_category_id_3s = $extend_category_list[2];
                } else {
                    $extend_category_id_3s = $extend_category_id_3s . "," . $extend_category_list[2];
                }
            }
        }
        $this->goods->startTrans();
        try {
            $goods_info = $this->goods->getInfo(['goods_id' => $goods_id]);
            if ($verificationinfo['valid_type'] == 1) {
                $verificationinfo['invalid_time'] = time() + ($verificationinfo['valid_days'] * 3600 * 24);
            }
            $data_goods = array(
                'website_id' => $this->website_id,
                'goods_name' => $goods_name,
                'shop_id' => $shopid,
                'category_id' => $category_id,
                'category_id_1' => $category_list[0],
                'category_id_2' => $category_list[1],
                'category_id_3' => $category_list[2],
                'supplier_id' => $supplier_id,
                'brand_id' => $brand_id,
                'group_id_array' => $group_id_array,
                'goods_type' => $goods_type,
                'market_price' => $market_price,
                'price' => $price,
                'promotion_price' => $price,
                'cost_price' => $cost_price,
                'point_exchange_type' => $point_exchange_type,
                'point_exchange' => $point_exchange,
                'give_point' => $give_point,
                'is_member_discount' => $is_member_discount,
                'shipping_fee' => $shipping_fee,
                'shipping_fee_id' => $shipping_fee_id,
                'stock' => $stock,
                'max_buy' => $max_buy,
                'min_buy' => $min_buy,
                'min_stock_alarm' => $min_stock_alarm,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'picture' => $picture,
                'keywords' => $keywords,
                'introduction' => $introduction,
                'description' => $description,
                'QRcode' => $QRcode,
                'real_sales' => $sales,
                'code' => $code,
                'is_stock_visible' => $is_stock_visible,
                'is_hot' => $is_hot,
                'is_recommend' => $is_recommend,
                'is_new' => $is_new,
                'is_promotion' => $is_promotion,
                'is_shipping_free' => $is_shipping_free,
                'is_wxcard' => $is_wxcard,
                'sort' => $sort,
                'img_id_array' => $image_array,
                'state' => $state,
                'sku_img_array' => $sku_img_array,
                'goods_attribute_id' => $goods_attribute_id,
                'goods_spec_format' => $goods_spec_format,
                'goods_weight' => $goods_weight,
                'goods_volume' => $goods_volume,
                'shipping_fee_type' => $shipping_fee_type,
                'extend_category_id' => $extend_category_id,
                'extend_category_id_1' => $extend_category_id_1s,
                'extend_category_id_2' => $extend_category_id_2s,
                'extend_category_id_3' => $extend_category_id_3s,
                'item_no' => $item_no,
                'is_distribution' => $is_distribution,
                'distribution_rule_val' => $distribution_rule_val,
                'distribution_rule' => $distribution_rule,
                'is_bonus_global' => $is_bonus_global,
                'is_bonus_area' => $is_bonus_area,
                'is_bonus_team' => $is_bonus_team,
                'bonus_rule_val' => $bonus_rule_val,
                'bonus_rule' => $bonus_rule,
                'cancle_times' => $verificationinfo['verification_num'],
                'cart_type' => $verificationinfo['card_type'],
                'valid_type' => $verificationinfo['valid_type'],
                'invalid_time' => $verificationinfo['invalid_time'],
                'store_list' => $verificationinfo['store_list'],
                'valid_days' => $verificationinfo['valid_days'],
                'video_id' => $video_id,
                'point_deduction_max' => $point_deduction_max,
                'point_return_max' => $point_return_max,
                'goods_count' => $goods_count,
                'single_limit_buy' => $single_limit_buy,
                'buyagain' => $buyagain,
                'buyagain_level_rule' => $buyagain_level_rule,
                'buyagain_recommend_type' => $buyagain_recommend_type,
                'buyagain_distribution_val' => $buyagain_distribution_val,
                'for_store' => $for_store,
                'is_goods_poster_open' => $is_goods_poster_open,
                'poster_data' => $poster_data,
                'px_type' => $px_type
            );
            // 商品保存之前钩子
            hook("goodsSaveBefore", $data_goods);
            $specArray = $this->changeSpec(json_decode($goods_spec_format,true));
            if ($goods_id == 0) {

                $data_goods['create_time'] = time();
                $data_goods['sale_date'] = time();
                $res = $this->goods->save($data_goods);
                if (empty($this->goods->goods_id)) {
                    $this->goods->goods_id = $res;
                }
                $data_goods['goods_id'] = $this->goods->goods_id;
                hook("goodsSaveSuccess", $data_goods);
                $goods_id = $this->goods->goods_id;
                // 添加sku

                if (!empty($sku_array)) {
                    $sku_list_array = explode('§', $sku_array);
                    if (empty($sku_list_array[0])) {
                        unset($sku_list_array[0]);//删掉空数据
                    }
                    foreach ($sku_list_array as $k => $v) {
                        $res = $this->addOrUpdateGoodsSkuItem($this->goods->goods_id, $v, $specArray);
                        if (!$res) {
                            $error = 1;
                        }
                    }
                    // sku图片添加
                    $sku_picture_array = array();
                    if (!empty($sku_picture_values)) {
                        $sku_picture_array = json_decode($sku_picture_values, true);
                        foreach ($sku_picture_array as $k => $v) {
                            $res = $this->addGoodsSkuPicture($shopid, $goods_id, $v["spec_id"], $v["spec_value_id"], $v["img_ids"]);
                            if (!$res) {
                                $error = 1;
                            }
                        }
                    }
                } else {
                    $goods_sku = new VslGoodsSkuModel();

                    // 添加一条skuitem
                    $sku_data = array(
                        'goods_id' => $this->goods->goods_id,
                        'sku_name' => '',
                        'market_price' => $market_price,
                        'price' => $price,
                        'promote_price' => $price,
                        'cost_price' => $cost_price,
                        'stock' => $stock,
                        'picture' => 0,
                        'code' => $code,
                        'QRcode' => '',
                        'create_date' => time(),
                        'website_id' => $this->website_id
                    );
                    $res = $goods_sku->save($sku_data);
                    if (!$res) {
                        $error = 1;
                    }
                }
                //知识付费商品
                if ($goods_type == 4) {
                    $knowledge_payment_content_model = new VslKnowledgePaymentContentModel();
                    foreach ($payment_content as $k => $v) {
                        $payment_content[$k]['goods_id'] = $goods_id;
                        $payment_content[$k]['website_id'] = $this->website_id;
                        $payment_content[$k]['shop_id'] = $this->instance_id;
                        $payment_content[$k]['create_time'] = time();
                    }
                    $knowledge_payment_content_model->saveAll($payment_content, true);
                }
                //如果有o2o应用，就需要将商品添加到勾选的对应门店，存进门店商品表
                if ($verificationinfo['store_list']) {
                    $arr = [];
                    $verificationinfo['store_list'] = explode(',', $verificationinfo['store_list']);
                    $store_goods_data = [
                        'goods_id' => $goods_id,
                        'website_id' => $this->website_id,
                        'goods_name' => $goods_name,
                        'shop_id' => $shopid,
                        'category_id' => $category_id,
                        'category_id_1' => $category_list[0],
                        'category_id_2' => $category_list[1],
                        'category_id_3' => $category_list[2],
                        'picture' => $picture,
                        'stock' => $stock,
                        'market_price' => $market_price,
                        'price' => $price,
                        'img_id_array' => $image_array,
                        'state' => 0,
                        'sales' => $sales,
                        'create_time' => time()
                    ];
                    $storeGoodsModel = new VslStoreGoodsModel();
                    for ($i = 0; $i < count($verificationinfo['store_list']); $i++) {
                        $store_goods_data['store_id'] = $verificationinfo['store_list'][$i];
                        $arr[] = $store_goods_data;
                    }
                    $storeGoodsModel->saveAll($arr, true);
                }

                //如果是虚拟商品，微信卡券开启则添加卡券
                if ($goods_type == 0 && $is_wxcard == 1) {
                    $ticket = new VslGoodsTicketModel();
                    $weixin_card = new WeixinCard();
                    //图片要先上传至微信图片库
                    $album = new AlbumPictureModel();
                    $pic = $album->getInfo(['pic_id' => $card_info['card_pic_id']], 'pic_cover,domain');
                    //需要将外链图片先存放到服务器，再传入微信后再删除掉
                    $need_delete = 0;
                    $check_url = substr($pic['pic_cover'], 0, 4);
                    if ($check_url == 'http') {
                        $dir = './upload/' . $this->website_id . '/wx_ticket_pic/';
                        if (!is_dir($dir)) {
                            $res = mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
                        }
                        $file_name = time() . '.jpg';
                        $this->saveImage($pic['pic_cover'], $dir . $file_name);
                        $need_delete = 1;
                        $pic_url = '/upload/' . $this->website_id . '/wx_ticket_pic/' . $file_name;
                    } else {
                        $pic_url = __IMG($pic['pic_cover']);
                    }
                    $card_pic = $weixin_card->uploadLogo($pic_url);
                    if ($need_delete == 1) {
                        unlink('.' . $pic_url);
                    }
                    if (!empty($card_pic['url'])) {
                        $card_info['icon_url'] = $card_pic['url'];
                    }
                    $website = new WebSite();
                    $web_info = $website->getWebSiteInfo();
                    if ($web_info) {
                        $card_info['brand_name'] = $web_info['mall_name'];
                        if ($web_info['logo']) {
                            $logo = substr($web_info['logo'], 0, 4);
                        } else {
                            $web_info['logo'] = "public/static/images/card_logo.png";
                            $logo = substr($web_info['logo'], 0, 4);
                        }
                        //需要将外链图片先存放到服务器，再传入微信后再删除掉
                        $need_delete = 0;
                        $check_url = substr($logo, 0, 4);
                        if ($check_url == 'http') {
                            $dir = './upload/' . $this->website_id . '/wx_ticket_pic/';
                            if (!is_dir($dir)) {
                                $res = mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
                            }
                            $file_name = time() . '.jpg';
                            $this->saveImage($web_info['logo'], $dir . $file_name);
                            $need_delete = 1;
                            $logo_url = '/upload/' . $this->website_id . '/wx_ticket_pic/' . $file_name;
                        } else {
                            $logo_url = __IMG($web_info['logo']);
                        }
                        $card_pic = $weixin_card->uploadLogo($logo_url);
                        if ($need_delete == 1) {
                            unlink('.' . $logo_url);
                        }
                        if (!empty($card_pic['url'])) {
                            $card_info['logo_url'] = $card_pic['url'];
                        }
                    }
                    $custom_url = getDomain($this->website_id) . '/wap/consumercard/detail/0';
                    $card_info['custom_url'] = $custom_url;
                    if (getAddons('shop', $this->website_id)) {
                        //获取店铺电话
                        $shop_model = new VslShopModel();
                        $shop_info = $shop_model::get(['shop_id' => $shopid]);
                        $card_info['service_phone'] = $shop_info['shop_phone'];
                    } else {
                        $card_info['service_phone'] = '';
                    }
                    if ($verificationinfo['valid_type'] == 1) {
                        $card_info['type'] = 'DATE_TYPE_FIX_TERM';
                        $card_info['fixed_begin_term'] = 0;
                        $card_info['fixed_term'] = $verificationinfo['valid_days'];
                    } else {
                        $card_info['type'] = 'DATE_TYPE_FIX_TIME_RANGE';
                        $card_info['begin_timestamp'] = time();
                        $card_info['end_timestamp'] = $verificationinfo['invalid_time'];
                    }
                    $card_info['quantity'] = $stock;
                    $ticket_result = $weixin_card->createCard($card_info);
                    //判断是否创建成功
                    if (empty($ticket_result['card_id'])) {
                        return 0;
                    }
                    $ticket_data = array(
                        'goods_id' => $this->goods->goods_id,
                        'card_title' => $card_info['card_title'],
                        'card_color' => $card_info['card_color'],
                        'card_pic_id' => $card_info['card_pic_id'],
                        'card_descript' => $card_info['card_descript'],
                        'store_service' => $card_info['store_service'],
                        'op_tips' => $card_info['op_tips'],
                        'send_set' => $card_info['send_set']
                    );
                    $ticket->save($ticket_data);
                    $this->goods->save(['wx_card_id' => $ticket_result['card_id']], ['goods_id' => $goods_id]);
                }
                //如果有勾选核销门店,将商品sku信息添加到门店商品sku表中
                if ($verificationinfo['store_list']) {
                    $skuModel = new VslGoodsSkuModel();
                    $sku_list = $skuModel->getQuery(['goods_id' => $this->goods->goods_id], '*', '');
                    $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
                    $res = [];
                    foreach ($verificationinfo['store_list'] as $key => $val) {
                        foreach ($sku_list as $k => $v) {
                            $data = [
                                'goods_id' => $this->goods->goods_id,
                                'website_id' => $this->website_id,
                                'shop_id' => $this->instance_id,
                                'sku_id' => $v['sku_id'],
                                'sku_name' => $v['sku_name'],
                                'attr_value_items' => $v['attr_value_items'],
                                'price' => $v['price'],
                                'market_price' => $v['market_price'],
                                'stock' => $v['stock'],
                                'store_id' => $val,
                                'create_time' => time(),
                                'bar_code' => empty($v['sku_name']) ? $item_no : $v['code']
                            ];
                            $res[] = $data;
                        }
                    }
                    $storeGoodsSkuModel->saveAll($res, true);
                }
            } else {
                //先获取到修改前此商品对应的门店列表
                $condition = [
                    'goods_id' => $goods_id
                ];
                $before_stroe_list = $this->goods->Query($condition, 'store_list');
                $before_stroe_list = explode(',', $before_stroe_list[0]);
                $data_goods['update_time'] = time();
                $res = $this->goods->save($data_goods, [
                    'goods_id' => $goods_id
                ]);
                $data_goods['goods_id'] = $goods_id;
                //知识付费商品
                if ($goods_type == 4) {
                    //编辑知识付费商品时，每次都重新更新
                    $knowledge_payment_content_model = new VslKnowledgePaymentContentModel();
                    $del_condition = [
                        'website_id' => $this->website_id,
                        'shop_id' => $this->instance_id,
                        'goods_id' => $goods_id,
                    ];
                    $knowledge_payment_content_model->delData($del_condition);
                    foreach ($payment_content as $k => $v) {
                        $knowledge_payment_content_model = new VslKnowledgePaymentContentModel();
                        $v['goods_id'] = $goods_id;
                        $v['website_id'] = $this->website_id;
                        $v['shop_id'] = $this->instance_id;
                        $v['create_time'] = time();
                        $knowledge_payment_content_model->save($v);
                    }
                }
                //修改商品时，判断有没有修改核销门店，取消勾选了就要删除对应门店的商品，添加新勾选门店对应的商品
                if ($verificationinfo['store_list']) {
                    $storeGoodsModel = new VslStoreGoodsModel();
                    $skuModel = new VslGoodsSkuModel();
                    $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
                    $verificationinfo['store_list'] = explode(',', $verificationinfo['store_list']);
                    foreach ($verificationinfo['store_list'] as $k => $v){
                        //每次编辑都同步商品名称，主图，图片数组到门店
                        $storeGoodsModel = new VslStoreGoodsModel();
                        $update_data = [
                            'goods_name' => $data_goods['goods_name'],
                            'picture' => $data_goods['picture'],
                            'img_id_array' => $data_goods['img_id_array']
                        ];
                        $storeGoodsModel->save($update_data,['goods_id' => $goods_id,'store_id' => $v]);
                    }
                    $condition = [
                        'goods_id' => $goods_id
                    ];
                    $store_id_list = $storeGoodsModel->Query($condition, 'store_id');
                    //如果有差异，说明修改了核销门店
                    if (count($verificationinfo['store_list']) != count($store_id_list)) {
                        if (count($verificationinfo['store_list']) > count($store_id_list)) {
                            $diff = array_diff_assoc($verificationinfo['store_list'], $store_id_list);
                            $diff = array_values($diff);
                            foreach ($diff as $key => $val) {
                                $list = [
                                    'goods_id' => $goods_id,
                                    'website_id' => $data_goods['website_id'],
                                    'shop_id' => $data_goods['shop_id'],
                                    'goods_name' => $data_goods['goods_name'],
                                    'category_id' => $data_goods['category_id'],
                                    'category_id_1' => $data_goods['category_id_1'],
                                    'category_id_2' => $data_goods['category_id_2'],
                                    'category_id_3' => $data_goods['category_id_3'],
                                    'picture' => $data_goods['picture'],
                                    'stock' => $data_goods['stock'],
                                    'market_price' => $data_goods['market_price'],
                                    'price' => $data_goods['price'],
                                    'img_id_array' => $data_goods['img_id_array'],
                                    'sales' => $data_goods['real_sales'],
                                    'state' => 0,
                                    'store_id' => $val,
                                    'create_time' => time()
                                ];
                                $arr[] = $list;
                                //增加sku信息
                                $sku_list = $skuModel->getQuery(['goods_id' => $goods_id], '*', '');
                                foreach ($sku_list as $k => $v) {
                                    $data = [
                                        'goods_id' => $goods_id,
                                        'website_id' => $this->website_id,
                                        'shop_id' => $data_goods['shop_id'],
                                        'sku_id' => $v['sku_id'],
                                        'sku_name' => $v['sku_name'],
                                        'attr_value_items' => $v['attr_value_items'],
                                        'price' => $v['price'],
                                        'market_price' => $v['market_price'],
                                        'stock' => $v['stock'],
                                        'store_id' => $val,
                                        'create_time' => time(),
                                        'bar_code' => empty($v['sku_name']) ? $data_goods['item_no'] : $v['code']
                                    ];
                                    $lists[] = $data;
                                }
                            }
                            $storeGoodsSkuModel->saveAll($lists, true);
                            $storeGoodsModel->saveAll($arr, true);
                        } else {
                            //删除取消勾选的门店
                            $diff = array_diff_assoc($store_id_list, $verificationinfo['store_list']);
                            $diff = array_values($diff);
                            if ($diff) {
                                foreach ($diff as $key => $val) {
                                    $data = [
                                        'store_id' => $val,
                                        'goods_id' => $goods_id
                                    ];
                                    $storeGoodsSkuModel->delData($data);
                                    $storeGoodsModel->delData($data);
                                }
                            }
                            //增加新勾选的门店
                            $differ = array_diff_assoc($verificationinfo['store_list'], $before_stroe_list);
                            $differ = array_values($differ);
                            if ($differ) {
                                foreach ($differ as $key => $val) {
                                    $list = [
                                        'goods_id' => $goods_id,
                                        'website_id' => $data_goods['website_id'],
                                        'shop_id' => $data_goods['shop_id'],
                                        'goods_name' => $data_goods['goods_name'],
                                        'category_id' => $data_goods['category_id'],
                                        'category_id_1' => $data_goods['category_id_1'],
                                        'category_id_2' => $data_goods['category_id_2'],
                                        'category_id_3' => $data_goods['category_id_3'],
                                        'picture' => $data_goods['picture'],
                                        'stock' => $data_goods['stock'],
                                        'market_price' => $data_goods['market_price'],
                                        'price' => $data_goods['price'],
                                        'img_id_array' => $data_goods['img_id_array'],
                                        'sales' => $data_goods['real_sales'],
                                        'state' => 0,
                                        'store_id' => $val,
                                        'create_time' => time()
                                    ];
                                    $arr[] = $list;
                                    //增加sku信息
                                    $sku_list = $skuModel->getQuery(['goods_id' => $goods_id], '*', '');
                                    foreach ($sku_list as $k => $v) {
                                        $data = [
                                            'goods_id' => $goods_id,
                                            'website_id' => $this->website_id,
                                            'shop_id' => $data_goods['shop_id'],
                                            'sku_id' => $v['sku_id'],
                                            'sku_name' => $v['sku_name'],
                                            'attr_value_items' => $v['attr_value_items'],
                                            'price' => $v['price'],
                                            'market_price' => $v['market_price'],
                                            'stock' => $v['stock'],
                                            'store_id' => $val,
                                            'create_time' => time(),
                                            'bar_code' => empty($v['sku_name']) ? $data_goods['item_no'] : $v['code']
                                        ];
                                        $lists[] = $data;
                                    }
                                }
                                $storeGoodsSkuModel->saveAll($lists, true);
                                $storeGoodsModel->saveAll($arr, true);
                            }
                        }
                    } else {
                        //此种情况是取消n个门店的同时又增加n个门店
                        //删除取消勾选的门店
                        $del_diff = array_diff_assoc($before_stroe_list, $verificationinfo['store_list']);
                        $del_diff = array_values($del_diff);
                        if ($del_diff) {
                            foreach ($del_diff as $key => $val) {
                                $data = [
                                    'store_id' => $val,
                                    'goods_id' => $goods_id
                                ];
                                $storeGoodsSkuModel->delData($data);
                                $storeGoodsModel->delData($data);
                            }
                        }
                        //增加新勾选的门店
                        $add_diff = array_diff_assoc($verificationinfo['store_list'], $store_id_list);
                        $add_diff = array_values($add_diff);
                        if ($add_diff) {
                            foreach ($add_diff as $key => $val) {
                                $list = [
                                    'goods_id' => $goods_id,
                                    'website_id' => $data_goods['website_id'],
                                    'shop_id' => $data_goods['shop_id'],
                                    'goods_name' => $data_goods['goods_name'],
                                    'category_id' => $data_goods['category_id'],
                                    'category_id_1' => $data_goods['category_id_1'],
                                    'category_id_2' => $data_goods['category_id_2'],
                                    'category_id_3' => $data_goods['category_id_3'],
                                    'picture' => $data_goods['picture'],
                                    'stock' => $data_goods['stock'],
                                    'market_price' => $data_goods['market_price'],
                                    'price' => $data_goods['price'],
                                    'img_id_array' => $data_goods['img_id_array'],
                                    'sales' => $data_goods['real_sales'],
                                    'state' => 0,
                                    'store_id' => $val,
                                    'create_time' => time()
                                ];
                                $arr[] = $list;
                                //增加sku信息
                                $sku_list = $skuModel->getQuery(['goods_id' => $goods_id], '*', '');
                                foreach ($sku_list as $k => $v) {
                                    $data = [
                                        'goods_id' => $goods_id,
                                        'website_id' => $this->website_id,
                                        'shop_id' => $data_goods['shop_id'],
                                        'sku_id' => $v['sku_id'],
                                        'sku_name' => $v['sku_name'],
                                        'attr_value_items' => $v['attr_value_items'],
                                        'price' => $v['price'],
                                        'market_price' => $v['market_price'],
                                        'stock' => $v['stock'],
                                        'store_id' => $val,
                                        'create_time' => time(),
                                        'bar_code' => empty($v['sku_name']) ? $data_goods['item_no'] : $v['code']
                                    ];
                                    $lists[] = $data;
                                }
                            }
                            $storeGoodsSkuModel->saveAll($lists, true);
                            $storeGoodsModel->saveAll($arr, true);
                        }
                    }
                }
                //此种情况是取消了所有的核销门店
                if ($before_stroe_list && empty($verificationinfo['store_list'])) {
                    $storeGoodsModel = new VslStoreGoodsModel();
                    $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
                    $where = ['goods_id' => $goods_id];
                    $storeGoodsModel->delData($where);
                    $storeGoodsSkuModel->delData($where);
                }
                hook("goodsSaveSuccess", $data_goods);
                if (!empty($sku_array)) {

                    $sku_list_array = explode('§', $sku_array);
                    if (empty($sku_list_array[0])) {
                        unset($sku_list_array[0]);//删掉空数据
                    }
                    $this->deleteSkuItem($goods_id, $sku_list_array);
                    foreach ($sku_list_array as $k => $v) {
                        $res = $this->addOrUpdateGoodsSkuItem($goods_id, $v, $specArray);
                        if ($res > 1) {
                            //如果此商品有对应的核销门店，修改时就要更新sku信息到门店商品sku表
                            if ($verificationinfo['store_list']) {
                                $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
                                $sku_item = explode('¦', $v);
                                $sku_name = $this->createSkuName($sku_item[0], $specArray);
                                foreach ($verificationinfo['store_list'] as $key => $val) {
                                    $data = [
                                        'goods_id' => $goods_id,
                                        'website_id' => $this->website_id,
                                        'shop_id' => $this->instance_id,
                                        'sku_id' => $res,
                                        'sku_name' => $sku_name,
                                        'attr_value_items' => $sku_item[0],
                                        'price' => $sku_item[1],
                                        'market_price' => $sku_item[2],
                                        'stock' => $sku_item[4],
                                        'store_id' => $val,
                                        'create_time' => time(),
                                        'bar_code' => $sku_item[5]
                                    ];
                                    $result[] = $data;
                                    //更新门店商品表
                                    $data2 = [
                                        'price' => $sku_item[1],
                                        'market_price' => $sku_item[2],
                                        'stock' => $sku_item[4],
                                    ];
                                    $store_goods_model = new VslStoreGoodsModel();
                                    $store_goods_model->save($data2, ['website_id' => $this->website_id, 'goods_id' => $goods_id, 'store_id' => $val]);
                                }
                            }
                        }
                        if (!$res) {
                            $error = 1;
                        }
                    }
                    if ($result){
                        $storeGoodsSkuModel->saveAll($result, true);
                    }
                    //如果此商品有对应的核销门店，修改时就要更新商品货号到门店商品sku表
                    if ($verificationinfo['store_list']) {
                        $skuModel = new VslGoodsSkuModel();
                        $sku_list = $skuModel->getQuery(['goods_id' => $goods_id], '*', '');
                        foreach ($sku_list as $k => $v) {
                            $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
                            $data = [
                                'bar_code' => $v['code']
                            ];
                            $storeGoodsSkuModel->save($data,['sku_id' => $v['sku_id'],'website_id' => $this->website_id]);
                        }
                    }
                    $goods_sku = new VslGoodsSkuModel();
                    $del_sku = $goods_sku->destroy([
                        'goods_id' => $goods_id,
                        'sku_name' => array(
                            'EQ',
                            ''
                        )
                    ]);

                    // 修改时先删除原来的规格图片
                    $this->deleteGoodsSkuPicture([
                        "goods_id" => $goods_id
                    ]);
                    // sku图片添加
                    $sku_picture_array = array();
                    if (!empty($sku_picture_values)) {
                        $sku_picture_array = json_decode($sku_picture_values, true);
                        foreach ($sku_picture_array as $k => $v) {
                            $res = $this->addGoodsSkuPicture($shopid, $goods_id, $v["spec_id"], $v["spec_value_id"], $v["img_ids"]);
                            if (!$res) {
                                $error = 1;
                            }
                        }
                    }
                } else {
                    $sku_data = array(
                        'goods_id' => $goods_id,
                        'sku_name' => '',
                        'market_price' => $market_price,
                        'price' => $price,
                        'promote_price' => $price,
                        'cost_price' => $cost_price,
                        'stock' => $stock,
                        'picture' => 0,
                        'code' => $code,
                        'QRcode' => '',
                        'update_date' => time(),
                        'website_id' => $this->website_id
                    );

                    $goods_sku = new VslGoodsSkuModel();
                    $sku = $goods_sku->getQuery([
                        'goods_id' => $goods_id
                    ], 'sku_id,sku_name', ''); // 当前SKU没有则添加，否则修改
                    if (count($sku) > 1 || (count($sku) == 1 && $sku[0]['sku_name'])) {//多规格商品改为无规格商品，删除原规格数据，新增一条
                        $goods_sku->destroy(['goods_id' => $goods_id]);
                        $res = $goods_sku->save($sku_data);
                        //如果此商品有对应的核销门店，修改时就要更新sku信息到门店商品sku表
                        if ($verificationinfo['store_list']) {
                            $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
                            $result = [];
                            $del_sku_condition = [
                                'website_id' => $this->website_id,
                                'shop_id' => $this->instance_id,
                                'goods_id' => $goods_id,
                            ];
                            $storeGoodsSkuModel->delData($del_sku_condition);
                            foreach ($verificationinfo['store_list'] as $key => $val) {
                                $data = [
                                    'goods_id' => $goods_id,
                                    'website_id' => $this->website_id,
                                    'shop_id' => $this->instance_id,
                                    'sku_id' => $goods_sku->sku_id,
                                    'sku_name' => '',
                                    'attr_value_items' => '',
                                    'price' => $price,
                                    'market_price' => $market_price,
                                    'stock' => $stock,
                                    'store_id' => $val,
                                    'create_time' => time(),
                                    'bar_code' => $item_no,
                                ];
                                $result[] = $data;
                            }
                            $storeGoodsSkuModel->saveAll($result, true);
                        }
                    } elseif (count($sku) == 1 && !$sku[0]['sku_name']) {
                        $res = $goods_sku->save($sku_data, ['sku_id' => $sku[0]['sku_id']]);
                        if ($verificationinfo['store_list']) {
                            foreach ($verificationinfo['store_list'] as $key => $val) {
                                $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
                                $data = [
                                    'bar_code' => $item_no,
                                ];
                                $storeGoodsSkuModel->save($data,['store_id' => $val,'goods_id' => $goods_id,'website_id' => $this->website_id]);
                            }
                        }
                    } else {
                        $res = $goods_sku->save($sku_data);
                    }
                }
                //如果是虚拟商品，微信卡券开启则修改卡券
                if ($goods_type == 0 && $is_wxcard == 1) {
                    if ($goods_info['wx_card_id']) {
                        $ticket = new VslGoodsTicketModel();
                        $weixin_card = new WeixinCard();
                        $card_info['card_id'] = $goods_info['wx_card_id'];
                        if ($verificationinfo['valid_type'] == 1) {
                            $card_info['type'] = 'DATE_TYPE_FIX_TERM';
                            $card_info['fixed_begin_term'] = 0;
                            $card_info['fixed_term'] = $verificationinfo['valid_days'];
                        } else {
                            $card_info['type'] = 'DATE_TYPE_FIX_TIME_RANGE';
                            $card_info['end_timestamp'] = $verificationinfo['invalid_time'];
                        }
                        $card_info['quantity'] = $stock;
                        $card_info['quantity2'] = $goods_info['stock'];
                        $ticket_result = $weixin_card->updateCard($card_info);
                        //判断是否修改成功
                        if ($ticket_result['errmsg'] != 'ok') {
                            return 0;
                        }
                    }
                }
                $this->modifyGoodsPromotionPrice($goods_id);

                        }

            // 每次都要重新更新商品属性
            $goods_attribute_model = new VslGoodsAttributeModel();
            $goods_attribute_model->destroy([
                'goods_id' => $goods_id
            ]);
            if (!empty($goods_attribute)) {
                if (!is_array($goods_attribute)) {
                    $goods_attribute_array = json_decode($goods_attribute, true);
                } else {
                    $goods_attribute_array = $goods_attribute;
                }
                if (!empty($goods_attribute_array)) {
                    foreach ($goods_attribute_array as $k => $v) {
                        $goods_attribute_model = new VslGoodsAttributeModel();
                        $data = array(
                            'goods_id' => $goods_id,
                            'shop_id' => $shopid,
                            'attr_value_id' => $v['attr_value_id'],
                            'attr_value' => $v['attr_value'],
                            'attr_value_name' => $v['attr_value_name'],
                            'sort' => $v['sort'],
                            'create_time' => time(),
                            'website_id' => $this->website_id,
                        );
                        $goods_attribute_model->save($data);
                    }
                }
            }

            if ($error == 0) {
                $this->goods->commit();
                return $goods_id;
            } else {
                $this->goods->rollback();
                return 0;
            }
        } catch
        (\Exception $e) {
            recordErrorLog($e);
            $this->goods->rollback();
            return $e->getMessage();
        }
        return 0;

        // TODO Auto-generated method stub
    }


    /**
     * 从网上下载图片保存到服务器
     * @param $path 图片网址
     * @param $image_name 保存到服务器的路径 './public/upload/users_avatar/'.time()
     */
    //保存图片
    function saveImage($path, $image_name)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $img = curl_exec($ch);
        curl_close($ch);
        //$image_name就是要保存到什么路径,默认只写文件名的话保存到根目录
        $fp = fopen($image_name, 'w');//保存的文件名称用的是链接里面的名称
        fwrite($fp, $img);
        fclose($fp);

    }

    /**
     *  获取商品详情页模板
     *
     * @return list
     */

    public function customGoodsInfo()
    {

        $sql = "select * from sys_custom_template where `range` = 2 and `is_enable` = 1";
        return Db::query($sql);
    }


    /**
     * 修改 商品的 促销价格
     *
     * @param unknown $goods_id
     */
    protected function modifyGoodsPromotionPrice($goods_id)
    {
        $discount_goods = new GoodsDiscount();
        $goods = new VslGoodsModel();
        $goods_sku = new VslGoodsSkuModel();
        $discount = $discount_goods->getDiscountByGoodsid($goods_id);
        if ($discount == -1) {
            // 当前商品没有参加活动
        } else {
            // 当前商品有正在进行的活动
            // 查询出商品的价格进行修改
            $goods_price = $goods->getInfo([
                'goods_id' => $goods_id
            ], 'price');
            $goods->save([
                'promotion_price' => $goods_price['price'] * $discount / 10
            ], [
                'goods_id' => $goods_id
            ]);
            // 查询出所有的商品sku价格进行修改
            $goods_sku_list = $goods_sku->getQuery([
                'goods_id' => $goods_id
            ], 'sku_id, price', '');
            foreach ($goods_sku_list as $k => $v) {
                $goods_sku = new VslGoodsSkuModel();
                $goods_sku->save([
                    'promote_price' => $v['price'] * $discount / 10
                ], [
                    'sku_id' => $v['sku_id']
                ]);
            }
        }
    }

    /**
     * 获取单个商品的sku属性
     *
     * {@inheritdoc}
     *
     * @see \data\api\IGoods::getGoodsSkuAll()
     */
    public function getGoodsAttribute($goods_id)
    {
        // 查询商品主表
        $goods = new VslGoodsModel();
        $goods_detail = $goods->get($goods_id);
        $spec_list = array();
        if (!empty($goods_detail) && !empty($goods_detail['goods_spec_format']) && $goods_detail['goods_spec_format'] != "[]") {
            $spec_list = json_decode($goods_detail['goods_spec_format'], true);
            if (!empty($spec_list)) {
                foreach ($spec_list as $k => $v) {
                    foreach ($v["value"] as $m => $t) {
                        if (empty($v["show_type"])) {
                            $spec_list[$k]["show_type"] = 1;
                        }

                        $spec_list[$k]["value"][$m]["picture"] = $this->getGoodsSkuPictureBySpecId($goods_id, $spec_list[$k]["value"][$m]['spec_id'], $spec_list[$k]["value"][$m]['spec_value_id']);
                    }
                }
            }
        }
        return $spec_list;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsSku()
     */
    public function getGoodsSku($goods_id)
    {
        $goods_sku = new VslGoodsSkuModel();
        $list = $goods_sku->get([
            'goods_id' => $goods_id
        ]);
        return $list;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::editGoodsSku()
     */
    public function ModifyGoodsSku($goods_id, $sku_list)
    {
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsImg()
     */
    public function getGoodsImg($goods_id)
    {
        // TODO Auto-generated method stub
        $goods_info = $this->goods->getInfo([
            'goods_id' => $goods_id
        ], 'picture');
        $pic_info = array();
        if (!empty($goods_info)) {
            $picture = new AlbumPictureModel();
            $pic_info['pic_cover'] = '';
            if (!empty($goods_info['picture'])) {
                $pic_info = $picture->get($goods_info['picture']);
            }
        }
        return $pic_info;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::editGoodsOffline()
     */
    public function ModifyGoodsOffline($condition)
    {
        $data = array(
            "state" => 0,
            'update_time' => time()
        );
        $result = $this->goods->save($data, "goods_id  in($condition)");
        if ($result > 0) {
            // 商品下架成功钩子
            hook("goodsOfflineSuccess", [
                'goods_id' => $condition
            ]);
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::editGoodsOnline()
     */
    public function ModifyGoodsOnline($condition)
    {
        $data = array(
            "state" => 1,
            'update_time' => time()
        );
        if ($this->instance_id) {
            $shop = new \addons\shop\service\Shop();
            $shop_info = $shop->getShopDetail($this->instance_id);
            if ($shop_info['base_info']['shop_audit']) {
                $data['state'] = 11;
            }
        }
        $result = $this->goods->save($data, "goods_id  in($condition)");
        if ($result > 0) {
            // 商品上架成功钩子
            hook("goodsOnlineSuccess", [
                'goods_id' => $condition
            ]);
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    //检测是否是活动商品
    public function check_is_promotion_goods($goods_id)
    {

        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id' => $goods_id], 'promotion_type');
        if ($goods_info['promotion_type'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::deleteGoods()
     */
    public function deleteGoods($goods_id)
    {
        $storeGoods = new VslStoreGoodsModel();
        $storeGoodsSku = new VslStoreGoodsSkuModel();
        $this->goods->startTrans();
        try {
            // 商品删除之前钩子
            hook("goodsDeleteBefore", [
                'goods_id' => $goods_id
            ]);
            //循环判断商品是否是活动商品
            if (strpos($goods_id, ',') === false) {
                if ($this->check_is_promotion_goods($goods_id) == false) {
                    return DELETE_PROMOTIONGOODS_FAIL;
                };
            } else {
                $check_goods_array = explode(',', $goods_id);
                foreach ($check_goods_array as $k => $v) {
                    if ($this->check_is_promotion_goods($goods_id) == false) {
                        return DELETE_PROMOTIONGOODS_FAIL;
                    };
                }

            }
            // 将商品信息添加到商品回收库中
            $this->addGoodsDeleted($goods_id);
            $condition = array(
                'shop_id' => $this->instance_id,
                'goods_id' => $goods_id
            );
            $res = $this->goods->destroy($goods_id);
            //从门店商品表中删除
            $storeGoods->delData(['goods_id' => $goods_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
            //从门店商品sku表中删除
            $storeGoodsSku->delData(['goods_id' => $goods_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);

            //如果是知识付费商品，则需把付费内容删除
            $knowledge_payment_content_model = new VslKnowledgePaymentContentModel();
            $knowledge_payment_content_model->delData(['goods_id' => $goods_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);

            if ($res > 0) {
                $goods_id_array = explode(',', $goods_id);
                $goods_sku_model = new VslGoodsSkuModel();
                $goods_attribute_model = new VslGoodsAttributeModel();
                $goods_sku_picture = new VslGoodsSkuPictureModel();
                foreach ($goods_id_array as $k => $v) {
                    // 删除商品sku
                    $goods_sku_model->destroy([
                        'goods_id' => $v
                    ]);
                    // 删除商品属性
                    $goods_attribute_model->destroy([
                        'goods_id' => $v
                    ]);
                    // 删除规格图片
                    $goods_sku_picture->destroy([
                        'goods_id' => $v
                    ]);
                }
            }
            $this->goods->commit();
            if ($res > 0) {
                // 商品删除成功钩子
                hook("goodsDeleteSuccess", [
                    'goods_id' => $goods_id
                ]);
                return SUCCESS;
            } else {
                return DELETE_FAIL;
            }
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->goods->rollback();
            return DELETE_FAIL;
        }
    }

    /**
     * 商品删除以前 将商品挪到 回收站中
     *
     * @param unknown $goods_ids
     */
    private function addGoodsDeleted($goods_ids)
    {
        $this->goods->startTrans();
        try {
            $goods_id_array = explode(',', $goods_ids);
            foreach ($goods_id_array as $k => $v) {
                // 得到商品的信息 备份商品
                $goods_info = $this->goods->get($v);
                $goods_delete_model = new VslGoodsDeletedModel();
                $goods_info = json_decode(json_encode($goods_info), true);
                $goods_delete_obj = $goods_delete_model->getInfo([
                    "goods_id" => $v
                ]);
                if (empty($goods_delete_obj)) {
                    $goods_info["update_time"] = time();
                    $goods_delete_model->save($goods_info);
                    // 商品的sku 信息备份
                    $goods_sku_model = new VslGoodsSkuModel();
                    $goods_sku_list = $goods_sku_model->getQuery([
                        "goods_id" => $v
                    ], "*", "");
                    foreach ($goods_sku_list as $goods_sku_obj) {
                        $goods_sku_deleted_model = new VslGoodsSkuDeletedModel();
                        $goods_sku_obj = json_decode(json_encode($goods_sku_obj), true);
                        $goods_sku_obj["update_date"] = time();
                        $goods_sku_deleted_model->save($goods_sku_obj);
                    }
                    // 商品的属性 信息备份
                    $goods_attribute_model = new VslGoodsAttributeModel();
                    $goods_attribute_list = $goods_attribute_model->getQuery([
                        'goods_id' => $v
                    ], "*", "");
                    foreach ($goods_attribute_list as $goods_attribute_obj) {
                        $goods_attribute_delete_model = new VslGoodsAttributeDeletedModel();
                        $goods_attribute_obj = json_decode(json_encode($goods_attribute_obj), true);
                        $goods_attribute_delete_model->save($goods_attribute_obj);
                    }
                    // 商品的sku图片备份
                    $goods_sku_picture = new VslGoodsSkuPictureModel();
                    $goods_sku_picture_list = $goods_sku_picture->getQuery([
                        'goods_id' => $v
                    ], "*", "");
                    foreach ($goods_sku_picture_list as $goods_sku_picture_list_obj) {
                        $goods_sku_picture_delete = new VslGoodsSkuPictureDeleteModel();
                        $goods_sku_picture_list_obj = json_decode(json_encode($goods_sku_picture_list_obj), true);
                        $goods_sku_picture_delete->save($goods_sku_picture_list_obj);
                    }
                }
            }
            $this->goods->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->goods->rollback();
            return $e->getMessage();
        }
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::deleteGoodImages()
     */
    public function deleteGoodImages($goods_id)
    {
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsDetail()
     * types 1平台或者店铺端操作 0移动接口请求
     */
    public function getGoodsDetail($goods_id,$types = 0,$mic_goods = '')
    {
        // 查询商品主表
        $goods = new VslGoodsModel();
        $model = \think\Request::instance()->module();
        $condition = ['website_id' => $this->website_id, 'goods_id' => $goods_id];
        if ($model == 'platform' || $model == 'admin') {
            $condition['shop_id'] = $this->instance_id;
        }
        $goods_detail = $goods->get($condition);
        if (!$goods_detail) {
            return null;
        }

        $discount_service = getAddons('discount', $this->website_id) ? new Discount() : '';
        $limit_discount_info = getAddons('discount', $this->website_id) ? $discount_service->getPromotionInfo($goods_id, $this->instance_id, $this->website_id) : ['discount_num' => 10];
        
        $member_price = $goods_detail['price'];
        $member_discount = 1;
        $goods_detail['discount_choice'] = 1;
        $goods_detail['member_is_label'] = 0;
        $goods_detail['is_show_member_price'] = 0;
        $goods_detail['price_type'] = 0;
        if ($this->uid) {
            // 查询商品是否有开启会员折扣

            // 查询商品是否有开启会员折扣
            $goodsDiscountInfo = $this->getGoodsInfoOfIndependentDiscount($goods_id, $member_price);
            if ($goodsDiscountInfo) {
                if($goodsDiscountInfo['is_use'] == 1){
                    $goods_detail['price_type'] = 1; //会员折扣
                }
                $member_price = $goodsDiscountInfo['member_price'];
                $member_discount = $goodsDiscountInfo['member_discount'];
                $goods_detail['discount_choice'] = $goodsDiscountInfo['discount_choice'];
                $goods_detail['is_show_member_price'] = $goodsDiscountInfo['is_show_member_price'];
                $goods_detail['member_is_label'] = $goodsDiscountInfo['member_is_label'];
            }
        }
        $goods_detail['member_price'] = $member_price;
        $goods_detail['member_discount'] = $member_discount;
        // sku多图数据
        $sku_picture_list = $this->getGoodsSkuPicture($goods_id);
        $goods_detail["sku_picture_list"] = $sku_picture_list;

        // 查询商品分组表
        $goods_group = new VslGoodsGroupModel();
        $goods_group_list = $goods_group->all($goods_detail['group_id_array']);
        $goods_detail['goods_group_list'] = $goods_group_list;
        // 查询商品sku表
        $goods_sku = new VslGoodsSkuModel();
        $goods_sku_detail = $goods_sku->where('goods_id=' . $goods_id)->select();
        
        foreach ($goods_sku_detail as $k => $goods_sku) {
            //$goods_sku_detail[$k]['member_price'] = $goods_sku['price'] * $member_discount;
            $goods_sku_detail[$k]['promote_price'] = $goods_sku_detail[$k]['price']; //商品原价
            $pprice =  $goods_sku_detail[$k]['price'];
            $goods_sku_detail[$k]['member_price'] = $member_price;
            if ($goods_detail['discount_choice'] == 2) {
                $goods_detail['price_type'] = 1; //会员折扣
                $goods_sku_detail[$k]['price'] = $member_price;
            }
            if ($goods_detail['discount_choice'] == 1) {
                $goods_detail['price_type'] = 1; //会员折扣
                $goods_sku_detail[$k]['price'] = $pprice * $goodsDiscountInfo['member_discount'];
            }
            if(!$this->uid){ //未登陆
                $goods_sku_detail[$k]['price'] = $pprice;
            }
            if ($limit_discount_info['discount_type'] == 1) {
                $goods_detail['price_type'] = 2; //限时折扣
                $goods_sku_detail[$k]['price'] = $pprice * $limit_discount_info['discount_num'] / 10;
            }
            if ($limit_discount_info['discount_type'] == 2) {
                $goods_detail['price_type'] = 2; //限时折扣
                $goods_sku_detail[$k]['price'] = $limit_discount_info['discount_num'];
            }
            if($types == 1){
                $goods_sku_detail[$k]['price'] = $pprice;
            }
            if($mic_goods == 1){
                $goods_sku_detail[$k]['price'] = $pprice;
            }
        }
        
        if($limit_discount_info['discount_num'] == 10){
            $limit_discount_info = (object)[];
        }
        $goods_detail['limit_discount_info'] = $limit_discount_info; 
        $goods_spec = new VslGoodsSpecModel();
        $goods_detail['sku_list'] = $goods_sku_detail;
        $spec_list = json_decode($goods_detail['goods_spec_format'], true);
        $album = new Album();
        if (!empty($spec_list)) {
            foreach ($spec_list as $k => $v) {
                $sort = $goods_spec->getInfo([
                    "spec_id" => $v['spec_id']
                ], "sort");
                $spec_list[$k]['sort'] = 0;
                if (!empty($sort)) {

                    $spec_list[$k]['sort'] = $sort['sort'];
                }
                foreach ($v["value"] as $m => $t) {
                    if (empty($v['show_type'])) {
                        $spec_list[$k]['show_type'] = 1;
                    }
                    // 查询SKU规格主图，没有返回0
                    $spec_list[$k]["value"][$m]["picture"] = $this->getGoodsSkuPictureBySpecId($goods_id, $spec_list[$k]["value"][$m]['spec_id'], $spec_list[$k]["value"][$m]['spec_value_id']);
                    if (is_numeric($t["spec_value_data"]) && $v["show_type"] == 3) {
                        $picture_detail = $album->getAlubmPictureDetail([
                            "pic_id" => $t["spec_value_data"]
                        ]);
                        if (!empty($picture_detail)) {
                            $spec_list[$k]["value"][$m]["spec_value_data_src"] = __IMG($picture_detail["pic_cover"]);
                        } else {
                            $spec_list[$k]["value"][$m]["spec_value_data_src"] = null;
                        }
                        $spec_list[$k]["value"][$m]["spec_value_data"] = $this->getGoodsSkuPictureBySpecId($goods_id, $spec_list[$k]["value"][$m]['spec_id'], $spec_list[$k]["value"][$m]['spec_value_id']);
                    } else {
                        $spec_list[$k]["value"][$m]["spec_value_data_src"] = null;
                    }
                }
            }
            // 排序字段
            $sort = array(
                'field' => 'sort'
            );

            $arrSort = array();
            foreach ($spec_list as $uniqid => $row) {
                foreach ($row as $key => $value) {
                    $arrSort[$key][$uniqid] = $value;
                }
            }
            array_multisort($arrSort[$sort['field']], SORT_ASC, $spec_list);
        }
        $goods_detail['spec_list'] = $spec_list;
        // 查询图片表
        $goods_img = new AlbumPictureModel();
        $order = "instr('," . $goods_detail['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
        $goods_img_list = $goods_img->getQuery([
            'pic_id' => [
                "in",
                $goods_detail['img_id_array']
            ]
        ], '*', $order);
        if (trim($goods_detail['img_id_array']) != "") {
            $img_temp_array = array();
            $img_array = explode(",", $goods_detail['img_id_array']);
            foreach ($img_array as $k => $v) {
                if (!empty($goods_img_list)) {
                    foreach ($goods_img_list as $t => $m) {
                        if ($m["pic_id"] == $v) {
                            $img_temp_array[] = $m;
                        }
                    }
                }
            }
        }
        $goods_picture = $goods_img->get($goods_detail['picture']);
        $goods_detail["img_temp_array"] = $img_temp_array;
        $goods_detail['img_list'] = $goods_img_list;
        $goods_detail['picture_detail'] = $goods_picture;
        $goods_detail['video'] = '';
        if ($goods_detail['video_id']) {
            $goods_detail['video'] = $goods_img->get($goods_detail['video_id']) ? $goods_img->get($goods_detail['video_id'])['pic_cover'] : '';
        }
        // 查询分类名称
        $category_name = $this->getGoodsCategoryName($goods_detail['category_id_1'], $goods_detail['category_id_2'], $goods_detail['category_id_3']);
        $cate_arr = explode(">", $category_name);
        $goods_detail['category_name_1'] = $cate_arr[0] ? trim($cate_arr[0]) : '';
        $goods_detail['category_name_2'] = $cate_arr[1] ? trim($cate_arr[1]) : '';
        $goods_detail['category_name_3'] = $cate_arr[2] ? trim($cate_arr[2]) : '';
        $goods_detail['category_name'] = $category_name;
        // 扩展分类
        $extend_category_array = array();

        if (!empty($goods_detail['extend_category_id'])) {
            $extend_category_ids = $goods_detail['extend_category_id'];
            $extend_category_id_1s = $goods_detail['extend_category_id_1'];
            $extend_category_id_2s = $goods_detail['extend_category_id_2'];
            $extend_category_id_3s = $goods_detail['extend_category_id_3'];
            $extend_category_id_str = explode(",", $extend_category_ids);
            $extend_category_id_1s_str = explode(",", $extend_category_id_1s);
            $extend_category_id_2s_str = explode(",", $extend_category_id_2s);
            $extend_category_id_3s_str = explode(",", $extend_category_id_3s);
            foreach ($extend_category_id_str as $k => $v) {
                $extend_category_name = $this->getGoodsCategoryName($extend_category_id_1s_str[$k], $extend_category_id_2s_str[$k], $extend_category_id_3s_str[$k]);
                $extend_category_array[] = array(
                    "extend_category_name" => $extend_category_name,
                    "extend_category_id" => $v,
                    "extend_category_id_1" => $extend_category_id_1s_str[$k],
                    "extend_category_id_2" => $extend_category_id_2s_str[$k],
                    "extend_category_id_3" => $extend_category_id_3s_str[$k]
                );
            }
        }
        $goods_detail['extend_category_name'] = "";
        $goods_detail['extend_category'] = $extend_category_array;

        // 查询商品类型相关信息
        if ($goods_detail['goods_attribute_id'] > 0 || $goods_detail['goods_attribute_id'] == 0) {
            $attribute_model = new VslAttributeModel();
            $attribute_info = $attribute_model->getInfo([
                'attr_id' => $goods_detail['goods_attribute_id']
            ], 'attr_name');
            $goods_detail['goods_attribute_name'] = $attribute_info['attr_name'];
            $goods_attribute_model = new VslGoodsAttributeModel();
            //修改排序，先按属性表的排序，再按商品属性排序

            $sql = "select a.`sort` as `sort`,b.`sort` as `value_sort`,b.`attr_id`,b.`goods_id`,b.`shop_id`,b.`attr_value_id`,b.`attr_value`,b.`attr_value_name`,b.`create_time`  from `vsl_attribute_value` as a right join `vsl_goods_attribute` as b on a.`attr_value_id` = b.`attr_value_id` where b.`goods_id` = $goods_id";
            $goods_attribute_list = Db::query($sql);

            $goods_detail['goods_attribute_list'] = $goods_attribute_list;
        } else {
            $goods_detail['goods_attribute_name'] = '';
            $goods_detail['goods_attribute_list'] = array();
        }

        // 查询商品单品活动信息
        $goods_preference = new GoodsPreference();
        $goods_promotion_info = $goods_preference->getGoodsPromote($goods_id);
        if (!empty($goods_promotion_info)) {
            $goods_discount_info = new VslPromotionDiscountModel();
            $goods_detail['promotion_detail'] = $goods_discount_info->getInfo([
                'discount_id' => $goods_detail['promote_id']
            ], 'start_time, end_time,discount_name,discount_num');
        }
        // 判断活动内容是否为空
        if (!empty($goods_detail['promotion_detail'])) {
            $goods_detail['promotion_info'] = $goods_promotion_info;
        } else {
            $goods_detail['promotion_info'] = "";
        }
        // 查询商品满减送活动
        $goods_mansong = new GoodsMansong();
        $goods_detail['mansong_name'] = $goods_mansong->getGoodsMansongName($goods_id);

        // 查询包邮活动
        $full = new Promotion();
        $baoyou_info = $full->getPromotionFullMail($goods_detail['shop_id']);
        if ($baoyou_info['is_open'] == 1) {
            if ($baoyou_info['full_mail_money'] == 0) {
                $goods_detail['baoyou_name'] = '全场包邮';
            } else {
                $goods_detail['baoyou_name'] = '满' . $baoyou_info['full_mail_money'] . '元包邮';
            }
        } else {
            $goods_detail['baoyou_name'] = '';
        }
        //$goods_express = new GoodsExpress();
        //$goods_detail['shipping_fee_name'] = $goods_express->getGoodsExpressTemplate($goods_id, 1, 1, 1);
        if (getAddons('shop', $this->website_id)) {
            $shop_model = new VslShopModel();
            $shop_name = $shop_model->getInfo(array(
                'shop_id' => $goods_detail['shop_id'],
                'website_id' => $goods_detail['website_id']
            ), 'shop_name');
            $goods_detail['shop_name'] = $shop_name['shop_name'];
        } else {
            $goods_detail['shop_name'] = '自营店';
        }
        // 查询商品规格图片
        $goos_sku_picture = new VslGoodsSkuPictureModel();
        $goos_sku_picture_query = $goos_sku_picture->getQuery([
            "goods_id" => $goods_id
        ], "*", '');

        $album_picture = new AlbumPictureModel();
        foreach ($goos_sku_picture_query as $k => $v) {
            if ($v["sku_img_array"] != "") {
                $spec_name = '';
                $spec_value_name = '';
                foreach ($spec_list as $t => $m) {
                    if ($m["spec_id"] == $v["spec_id"]) {
                        foreach ($m["value"] as $c => $b) {
                            if ($b["spec_value_id"] == $v["spec_value_id"]) {
                                $spec_name = $b["spec_name"];
                                $spec_value_name = $b["spec_value_name"];
                            }
                        }
                    }
                }
                $goos_sku_picture_query[$k]["spec_name"] = $spec_name;
                $goos_sku_picture_query[$k]["spec_value_name"] = $spec_value_name;
                $tmp_img_array = $album_picture->getQuery([
                    "pic_id" => [
                        "in",
                        $v["sku_img_array"]
                    ]
                ], "*", '');
                $pic_id_array = explode(',', (string)$v["sku_img_array"]);
                $goos_sku_picture_query[$k]["sku_picture_query"] = array();
                // var_dump($pic_id_array);
                $sku_picture_query_array = array();
                foreach ($pic_id_array as $t => $m) {
                    foreach ($tmp_img_array as $q => $z) {
                        if ($m == $z["pic_id"]) {
                            // var_dump($z);
                            $sku_picture_query_array[] = $z;
                        }
                    }
                }
                $goos_sku_picture_query[$k]["sku_picture_query"] = $sku_picture_query_array;
                // $goos_sku_picture_query[$k]["sku_picture_query"] = $album_picture->getQuery(["pic_id"=>["in",$v["sku_img_array"]]], "*", '');
            } else {
                unset($goos_sku_picture_query[$k]);
            }
        }
        sort($goos_sku_picture_query);
        $goods_detail["sku_picture_array"] = $goos_sku_picture_query;

        // 查询商品的已购数量
        $orderGoods = new VslOrderGoodsModel();
        $num = $orderGoods->getSum([
            "goods_id" => $goods_id,
            "buyer_id" => $this->uid,
            "order_status" => array(
                "neq",
                5
            )
        ], "num");
        $goods_detail["purchase_num"] = $num;
        //如果是知识付费商品,要判断当前用户有没有购买过
        if($goods_detail['goods_type'] == 4) {
            $uid = getUserId();
            if(empty($uid)){
                //没有登录,默认没有购买过
                $goods_detail['is_buy'] = false;
            }else{
                $order_goods_model = new VslOrderGoodsModel();
                $order_model = new VslOrderModel();
                $data = [
                    'website_id' => $this->website_id,
                    'buyer_id' => $uid,
                    'goods_id' => $goods_id
                ];
                $order_list = $order_goods_model->getQuery($data, 'order_id','order_id ASC');
                if ($order_list) {
                    foreach ($order_list as $k => $v) {
                        $order_status = $order_model->getInfo(['order_id' => $v['order_id']], 'order_status');
                        if($order_status['order_status'] == 4) {
                            $goods_detail['is_buy'] = true;
                            continue;
                        }else{
                            $goods_detail['is_buy'] = false;
                        }
                    }
                } else {
                    $goods_detail['is_buy'] = false;
                }
            }
        }
        return $goods_detail;
        // TODO Auto-generated method stub
    }

    /*
     * 微商中心-根据分类id得到商品列表
     * $channel_id 用于判断是取平台的商品还是渠道商的商品
     * $channel_goods_type 用于判断当前是采购的商品还是自提的商品，自提不用乘进货折扣
     */
    public function getChannelGoodsList($page_index, $page_size, $condition, $order, $channel_id, $uid, $buy_type)
    {
        $channel_server = new Channel();
        $goods_discount_model = new VslGoodsDiscountModel();
        $channel_model = new VslChannelModel();
        $page_size = 4;
        $page_offset = ($page_index - 1) * $page_size;
        // 查询商品主表
        if ($channel_id == 'platform') {
            $goods_mdl = new VslGoodsModel();
            $goods_sku_mdl = new VslGoodsSkuModel();
            $channel_id = 'platform';
        } else {
            $goods_mdl = new VslChannelGoodsModel();
            $goods_sku_mdl = new VslChannelGoodsSkuModel();
            $channel_id = $channel_id;
            $condition['channel_id'] = $channel_id;
        }
        $condition['g.state'] = 1;
        $goods_spec = new VslGoodsSpecModel();
        $total_count = $goods_mdl->alias('g')
            ->field('goods_id')
            ->where($condition)
            ->order($order)
            ->count();
        $page_count = ceil($total_count / $page_size);
        $goods_list = $goods_mdl->alias('g')
            ->field('goods_id,goods_name,goods_spec_format,img_id_array')
            ->where($condition)
            ->limit($page_offset, $page_size)
            ->order($order)
            ->select();
        $goods_arr = objToArr($goods_list);
        //获取我的渠道商的等级进货比例
        $condition1['c.website_id'] = $this->website_id;
        $condition1['c.uid'] = $uid;
        $channel_info = $channel_server->getMyChannelInfo($condition1);
        $purchase_discount = $channel_info['purchase_discount'] ?: 1;
        $my_weight = $channel_info['weight'];
        foreach ($goods_arr as $k => $goods_info) {
            $goods_id = $goods_info['goods_id'];
            //获取图片
            $goods_img = new AlbumPictureModel();
            $order = "instr('," . $goods_info['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
            $goods_img_list = $goods_img->getQuery([
                'pic_id' => [
                    "in",
                    $goods_info['img_id_array']
                ]
            ], 'pic_cover', $order);
            $goods_img_arr = objToArr($goods_img_list);
            $temp_img_arr = [];
            foreach ($goods_img_arr as $k2 => $img) {
                $temp_img_arr[] = getApiSrc($img['pic_cover']);
            }
            //处理一下图片
            $goods_arr[$k]['img_list'] = $temp_img_arr;

            //spec_list sku的内容
            $spec_list = json_decode($goods_info['goods_spec_format'], true);
//            var_dump($spec_list);exit;
            if (!empty($spec_list)) {
                foreach ($spec_list as $k3 => $v) {
                    $sort = $goods_spec->getInfo([
                        "spec_id" => $v['spec_id']
                    ], "sort");
                    $spec_list[$k3]['sort'] = 0;
                    if (!empty($sort)) {
                        $spec_list[$k3]['sort'] = $sort['sort'];
                    }
                    foreach ($v["value"] as $m => $t) {
                        if (empty($t["spec_show_type"])) {
                            $spec_list[$k3]["value"][$m]["spec_show_type"] = 1;
                        }
                        // 查询SKU规格主图，没有返回0
                        $spec_list[$k3]["value"][$m]["picture"] = $this->getGoodsSkuPictureBySpecId($goods_id, $spec_list[$k3]["value"][$m]['spec_id'], $spec_list[$k3]["value"][$m]['spec_value_id']);
                    }
                }
                // 排序字段
                $sort = array(
                    'field' => 'sort'
                );

                $arrSort = array();
                foreach ($spec_list as $uniqid => $row) {
                    foreach ($row as $key => $value) {
                        $arrSort[$key][$uniqid] = $value;
                    }
                }
                array_multisort($arrSort[$sort['field']], SORT_ASC, $spec_list);
            }
            $spec_list = $spec_list ?: [];
//            $goods_detail['spec_list'] = $spec_list;
            if ($channel_id == 'platform') {
                $sku_conditon['goods_id'] = $goods_id;
            } else {
                $sku_conditon['goods_id'] = $goods_id;
                $sku_conditon['channel_id'] = $channel_id;
            }
            //获取sku
            $goods_id = $goods_info['goods_id'];
            $goods_sku_list = $goods_sku_mdl->where($sku_conditon)->select();
            $goods_sku_arr = objToArr($goods_sku_list);
            $goods_sku_detail = $this->getChannelGoodSkuInfo($goods_sku_arr, $spec_list, $purchase_discount, $buy_type, $uid, $my_weight);
            $goods_arr[$k]['channel_info'] = $channel_id;
            $goods_arr[$k]['min_price'] = $goods_sku_detail['min_price'];
            $goods_arr[$k]['max_price'] = $goods_sku_detail['max_price'];
            $goods_arr[$k]['min_market_price'] = $goods_sku_detail['min_market_price'];
            $goods_arr[$k]['max_market_price'] = $goods_sku_detail['max_market_price'];
            $goods_arr[$k]['sku'] = $goods_sku_detail['sku'];
            //将原来的不需要的值去掉
            unset($goods_arr[$k]['img_id_array']);
            unset($goods_arr[$k]['goods_spec_format']);
            if($buy_type == 'purchase') {
                //如果当前渠道商的等级与商品勾选的渠道商等级不一致则删除
                $channel_auth = $goods_discount_model->Query(['goods_id' => $goods_id, 'website_id' => $this->website_id], 'channel_auth')[0];
                if (empty($channel_auth)) {
                    unset($goods_arr[$k]);
                } else {
                    $channel_auth = explode(',', $channel_auth);
                    //获取当前用户的渠道商权限
                    $user_channel_level = $channel_model->Query(['uid' => $uid, 'website_id' => $this->website_id], 'channel_grade_id')[0];
                    if (!in_array($user_channel_level, $channel_auth)) {
                        unset($goods_arr[$k]);
                    }
                }
            }
        }
        $goods_arr = array_values($goods_arr);
        return [
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'goods_list' => $goods_arr,
                'total_count' => $total_count,
                'page_count' => $page_count,
            ]
        ];
    }

    /*
     * 获取商品的sku规格
     * **/
    public function getChannelGoodSkuInfo($goods_sku_arr, $spec_list, $purchase_discount, $buy_type, $uid, $my_weight)
    {
//        echo '<pre>';print_r($goods_sku_arr);exit;
        $channel_server = new Channel();
        $temp_sku_list = $goods_sku_arr;
        $spec_obj = [];
        $goods_detail['sku']['tree'] = [];
        foreach ($spec_list as $i => $spec_info) {
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
        //接口需要tree是数组，不是对象，去除tree以spec_id为key的值
        $goods_detail['sku']['tree'] = array_values($goods_detail['sku']['tree']);
        foreach ($goods_sku_arr as $k => $sku) {
            $temp_sku['id'] = $sku['sku_id'];
            $temp_sku['sku_name'] = $sku['sku_name'];
//            if ($buy_type == 'purchase') {
            $channel_con = new \addons\channel\controller\Channel();
            $up_grade_channel_id = $channel_con->getUpChannelInfo();
            $temp_arr = ['channel_id' => $up_grade_channel_id, 'sku_id' => $sku['sku_id'], 'price' => $sku['price'], 'markent_price' => 0];
            $this->getChannelSkuPrice($temp_arr);
            $sku['price'] = $temp_arr['price'];
//            }
            //采购或者出货将价格处理成采购价，自提价格不变。
            if ($buy_type == 'purchase') {
                $sku['price'] = $sku['price'] * $purchase_discount;
            }
            $temp_sku['price'] = $sku['price'];
            $temp_sku['min_buy'] = 1;
            $temp_sku['group_price'] = '';
            $temp_sku['group_limit_buy'] = '';
            $temp_sku['market_price'] = $sku['market_price'];
            //自提要限制单次购买量不超过它本身的库存
            if ($buy_type == 'purchase') {
                $channel_server = new Channel();
                $stock = $channel_server->myAllRefereeChannelSkuStore($uid, $my_weight, $sku['sku_id']);
                //获取sku的所有上级渠道商的库存
                $temp_sku['max_buy'] = $stock;
                $temp_sku['stock_num'] = $stock;
            } else {
                $temp_sku['max_buy'] = 0;
                $temp_sku['stock_num'] = $sku['stock'];
            }
            $temp_sku['attr_value_items'] = $sku['attr_value_items'];
            $sku_temp_spec_array = explode(';', $sku['attr_value_items_format']);
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
            $goods_detail['sku']['list'][] = $temp_sku;
            $goods_detail['min_price'] = reset($temp_sku_list)['sku_id'] == $sku['sku_id']
                ? $sku['price'] : ($goods_detail['min_price'] <= $sku['price'] ? $goods_detail['min_price'] : $sku['price']);
            $goods_detail['max_price'] = reset($temp_sku_list)['sku_id'] == $sku['sku_id']
                ? $sku['price'] : ($goods_detail['max_price'] >= $sku['price'] ? $goods_detail['max_price'] : $sku['price']);
            $goods_detail['min_market_price'] = reset($temp_sku_list)['sku_id'] == $sku['sku_id']
                ? $sku['market_price'] : ($goods_detail['min_market_price'] <= $sku['market_price'] ? $goods_detail['min_market_price'] : $sku['market_price']);
            $goods_detail['max_market_price'] = reset($temp_sku_list)['sku_id'] == $sku['sku_id']
                ? $sku['market_price'] : ($goods_detail['max_market_price'] >= $sku['market_price'] ? $goods_detail['max_market_price'] : $sku['market_price']);
        }
        return $goods_detail;
    }

    //获取渠道商的商品单价，如果删除了的情况
    public function getChannelSkuPrice(&$sku)
    {
        //不管是采购还是自提，都显示商品表里面的平台价,如果删除了查不到则展示批次的最高平台价
        $platform_goods = new VslGoodsSkuModel();
        $platform_list = $platform_goods->getInfo(['sku_id' => $sku['sku_id']], 'price, market_price');
        if ($platform_list) {
            $sku['price'] = $platform_list['price'];
            $sku['market_price'] = $platform_list['market_price'];
        } else {
            if (getAddons('channel', $this->website_id)) {
                $channel_record = new VslChannelOrderSkuRecordModel();
                $sku_record['my_channel_id'] = $sku['channel_id'];
                $sku_record['sku_id'] = $sku['sku_id'];
                $sku_record['buy_type'] = 1;
                $sku_record['website_id'] = $this->website_id;
                $sku_record_list = objToArr($channel_record->getQuery($sku_record, 'platform_price', ''));
                $platform_price_arr = array_column($sku_record_list, 'platform_price') ?: [$sku['price']];
                $sku['price'] = max($platform_price_arr);
            }
        }
    }
    /*
     * 获取微商中心商品分类列表
     * **/
    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsDetail()
     */
    public function getChannelGoodsCategoryList($condition, $order, $channel_id)
    {
        //偏移量
//        $page_offset = ($page_index - 1) * $page_size;
        if ($channel_id == 'platform') {
            $goods_mdl = new VslGoodsModel();
        } else {
            $goods_mdl = new VslChannelGoodsModel();
        }
//        $category_count = $goods_mdl->alias('g')
//            ->field('gc.*')
//            ->where(['gc.level' => 1])
//            ->join('vsl_goods_category gc', 'g.category_id_1=gc.category_id', 'LEFT')
//            ->group('gc.category_id')
//            ->select();
//        $total_count = count($category_count);
        //页数
//        $page_num = ceil($total_count / $page_size);
        $category_list = $goods_mdl->alias('g')
            ->field('gc.category_id,gc.category_name,gc.short_name')
            ->where(['gc.level' => 1, 'g.website_id' => $this->website_id])
            ->join('vsl_goods_category gc', 'g.category_id_1=gc.category_id', 'LEFT')
            ->order('gc.sort DESC')
//            ->limit($page_offset, $page_size)
            ->group('gc.category_id')
            ->select();

        return [
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'category_list' => $category_list,
//                'total_count' => $total_count,
//                'page_count' => $page_num,
            ]
        ];
    }

    /**
     * 查询sku多图数据
     *
     * {@inheritdoc}
     *
     * @see \data\api\IGoods::getGoodsSkuPicture()
     */
    public function getGoodsSkuPicture($goods_id)
    {
        $goods_sku = new VslGoodsSkuPictureModel();
        $sku_picture_list = $goods_sku->getQuery([
            "goods_id" => $goods_id
        ], "*", "");
        foreach ($sku_picture_list as $k => $v) {
            $sku_img_array = $v["sku_img_array"];
            $album_picture_list = array();
            if (!empty($sku_img_array)) {
                $sku_img_str = explode(",", $sku_img_array);
                foreach ($sku_img_str as $img_id) {
                    $picture_model = new AlbumPictureModel();
                    $picture_obj = $picture_model->getInfo([
                        "pic_id" => $img_id
                    ], "*");
                    if (isset($picture_obj) && !empty($picture_obj)) {
                        $album_picture_list[] = $picture_obj;
                    }
                }
            }
            $sku_picture_list[$k]["album_picture_list"] = $album_picture_list;
        }
        return $sku_picture_list;
    }

    /**
     * 根据商品id、规格id、规格值id查询
     * {@inheritdoc}
     *
     * @see \data\api\IGoods::getGoodsSkuPictureBySpecId()
     */
    public function getGoodsSkuPictureBySpecId($goods_id, $spec_id, $spec_value_id)
    {
        $picture = 0;

        $goods_sku = new VslGoodsSkuPictureModel();
        $sku_img_array = $goods_sku->getInfo([
            "goods_id" => $goods_id,
            "spec_id" => $spec_id,
            "spec_value_id" => $spec_value_id
        ], "sku_img_array");
        if (!empty($sku_img_array)) {
            $array = explode(",", $sku_img_array['sku_img_array']);
            $picture = $array[0];
        }
        return $picture;
    }

    /**
     * 商品规格列表(non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsAttributeList()
     */
    public function getGoodsAttributeList($condition, $field, $order)
    {
        $spec = new VslGoodsSpecModel();
        $list = $spec->getQuery($condition, $field, $order);
        return $list;
    }

    /**
     * 商品规格值列表(non-PHPdoc)
     *
     *
     * @see \data\api\IGoods::getGoodsAttributeValueList()
     */
    public function getGoodsAttributeValueList($condition, $field)
    {
        $attribute = new VslGoodsSpecValueModel();
        $list = $attribute->where($condition)->clumn($field);
        return $list;
    }

    /*
     * 添加商品规格
     * (non-PHPdoc)
     * @see \data\api\IGoods::addGoodsSpec()
     */
    public function addGoodsSpec($spec_name, $sort = 0)
    {
        $attribute = new VslGoodsSpecModel();
        $data = array(
            'shop_id' => $this->instance_id,
            'spec_name' => $spec_name,
            'sort' => 0,
            'create_time' => time()
        );
        $find_id = $attribute->get([
            'spec_name' => $spec_name
        ]);
        if (!empty($find_id)) {
            return $find_id['spec_id'];
        } else {
            $res = $attribute->save($data);
            return $attribute->spec_id;
        }

        // TODO Auto-generated method stub
    }

    /*
     * 添加商品规格值
     * (non-PHPdoc)
     * @see \data\api\IGoods::addGoodsSpecValue()
     */
    public function addGoodsSpecValue($spec_id, $spec_value, $sort = 0)
    {
        $spec_value_model = new VslGoodsSpecValueModel();
        $data = array(
            'spec_id' => $spec_id,
            'website_id' => $this->website_id,
            'spec_value_name' => $spec_value,
            'sort' => $sort,
            'create_time' => time()
        );
        $find_id = $spec_value_model->get([
            'spec_value_name' => $spec_value,
            'website_id' => $this->website_id,
            'spec_id' => $spec_id
        ]);
        if (!empty($find_id)) {
            return $find_id['spec_value_id'];
        } else {
            $res = $spec_value_model->save($data);
            return $spec_value_model->spec_value_id;
        }

        // TODO Auto-generated method stub
    }

    /**
     * 添加商品sku列表
     *
     * @param unknown $goods_id
     * @param unknown $sku_item_array
     * @return Ambigous <number, \think\false, boolean, string>
     */
    private function addOrUpdateGoodsSkuItem($goods_id, $sku_item_array, $specArray = [])
    {
        $sku_item = explode('¦', $sku_item_array);
        $goods_sku = new VslGoodsSkuModel();
        $sku_name = $this->createSkuName($sku_item[0], $specArray);
        $condition = array(
            'goods_id' => $goods_id,
            'attr_value_items' => $sku_item[0]
        );
        $sku_count = $goods_sku->where($condition)->find();

        if (empty($sku_count)) {
            $data = array(
                'goods_id' => $goods_id,
                'sku_name' => $sku_name,
                'attr_value_items' => $sku_item[0],
                'attr_value_items_format' => $sku_item[0],
                'price' => $sku_item[1],
                'promote_price' => $sku_item[1],
                'market_price' => $sku_item[2],
                'cost_price' => $sku_item[3],
                'stock' => $sku_item[4],
                'picture' => 0,
                'code' => $sku_item[5],
                'QRcode' => '',
                'website_id' => $this->website_id,
                'create_date' => time()
            );
            $goods_sku->save($data);
            return $goods_sku->sku_id;
        } else {
            $data = array(
                'goods_id' => $goods_id,
                'sku_name' => $sku_name,
                'price' => $sku_item[1],
                'promote_price' => $sku_item[1],
                'market_price' => $sku_item[2],
                'cost_price' => $sku_item[3],
                'stock' => $sku_item[4],
                'code' => $sku_item[5],
                'QRcode' => '',
                'website_id' => $this->website_id,
                'update_date' => time()
            );
            $res = $goods_sku->save($data, [
                'sku_id' => $sku_count['sku_id']
            ]);
            return $res;
        }
    }

    private function deleteSkuItem($goods_id, $sku_list_array)
    {
        $sku_item_list_array = array();
        foreach ($sku_list_array as $k => $sku_item_array) {
            $sku_item = explode('¦', $sku_item_array);
            $sku_item_list_array[] = $sku_item[0];
        }
        $goods_sku = new VslGoodsSkuModel();
        $storeGoodsSkuModel = new VslStoreGoodsSkuModel();
        $list = $goods_sku->where('goods_id=' . $goods_id)->select();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (!in_array($v['attr_value_items'], $sku_item_list_array)) {
                    $goods_sku->destroy($v['sku_id']);
                    //门店商品sku表也要删除
                    $storeGoodsSkuModel->delData(['sku_id' => $v['sku_id']]);
                }
            }
        }
    }

    /**
     * 组装sku name
     *
     * @param unknown $pvs
     * @return string
     */
    public function createSkuName($pvs,$specArray = [])
    {
        $name = '';
        $pvs_array = explode(';', $pvs);
        foreach ($pvs_array as $k => $v) {
            $value = explode(':', $v);
            $prop_id = $value[0];
            $prop_value = $value[1];
            $goods_spec_value_model = new VslGoodsSpecValueModel();
            $value_name = $goods_spec_value_model->getInfo([
                'spec_value_id' => $prop_value
            ], 'spec_value_name');
            if(isset($specArray[$prop_value]['name'])){
                $value_name['spec_value_name'] = $specArray[$prop_value]['name'];
            }
            $name = $name . $value_name['spec_value_name'] . ' ';
        }
        return $name;
    }

    /**
     * 根据当前分类ID查询商品分类的三级分类ID
     *
     * @param unknown $category_id
     */
    private function getGoodsCategoryId($category_id)
    {
        // 获取分类层级
        $goods_category = new VslGoodsCategoryModel();
        $info = $goods_category->get($category_id);
        if ($info['level'] == 1) {
            return array(
                $category_id,
                0,
                0
            );
        }
        if ($info['level'] == 2) {
            // 获取父级
            return array(
                $info['pid'],
                $category_id,
                0
            );
        }
        if ($info['level'] == 3) {
            $info_parent = $goods_category->get($info['pid']);
            // 获取父级
            return array(
                $info_parent['pid'],
                $info['pid'],
                $category_id
            );
        }
    }

    /**
     * 根据当前商品分类组装分类名称
     *
     * @param unknown $category_id_1
     * @param unknown $category_id_2
     * @param unknown $category_id_3
     */
    /**
     * category_name 转换为 short_name
     */
    public function getGoodsCategoryName($category_id_1, $category_id_2, $category_id_3)
    {
        $name = '';
        $goods_category = new VslGoodsCategoryModel();
        $info_1 = $goods_category->getInfo([
            'category_id' => $category_id_1
        ], 'short_name');
        $info_2 = $goods_category->getInfo([
            'category_id' => $category_id_2
        ], 'short_name');
        $info_3 = $goods_category->getInfo([
            'category_id' => $category_id_3
        ], 'short_name');
        if (!empty($info_1['short_name'])) {
            $lg_symbol = !empty($info_2['short_name']) ? '>' : '';
            $name = $info_1['short_name'] . $lg_symbol;
        }
        if (!empty($info_2['short_name'])) {
            $lg_symbol = !empty($info_3['short_name']) ? '>' : '';
            $name = $name . '' . $info_2['short_name'] . $lg_symbol;
        }
        if (!empty($info_3['short_name'])) {
            $name = $name . '' . $info_3['short_name'];
        }
        return $name;
    }

    /**
     * 获取条件查询出商品
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getSearchGoodsList()
     */
    public function getSearchGoodsList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        if (!getAddons('shop', $this->website_id)) {
            $condition['shop_id'] = $this->instance_id;
        }

        $result = $this->goods->pageQuery($page_index, $page_size, $condition, $order, $field);
        foreach ($result['data'] as $k => $v) {
            $picture = new AlbumPictureModel();
            $pic_info = array();
            $pic_info['pic_cover'] = '';
            if (!empty($v['picture'])) {
                $pic_info = $picture->getInfo(['pic_id' => $v['picture']], 'pic_cover,pic_cover_mid,pic_cover_micro');
            }
            $result['data'][$k]['picture_info'] = $pic_info;
            if (getAddons('shop', $this->website_id) && $v['shop_id']) {
                $shop = new VslShopModel();
                $shop_info = $shop->getInfo(['shop_id' => $v['shop_id']]);
            } else {
                $shop_info['shop_name'] = '自营店';
            }
            $result['data'][$k]['shop_name'] = $shop_info['shop_name'];
            if (isset($v['promotion_type'])) {
                $result['data'][$k]['promotion_name'] = $this->getGoodsPromotionType($v['promotion_type']);
            }
        }
        return $result;
    }

    /**
     * 修改商品分组(non-PHPdoc)
     *
     * @see \data\api\IGoods::ModifyGoodsGroup()
     */
    public function ModifyGoodsGroup($goods_id, $goods_type)
    {
        $data = array(
            "group_id_array" => $goods_type,
            "update_time" => time()
        );
        $result = $this->goods->save($data, "goods_id  in($goods_id)");
        if ($result > 0) {
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    /**
     * 修改商品 推荐 1=热销 2=推荐 3=新品
     */
    public function ModifyGoodsRecommend($goods_ids, $goods_type)
    {
        $goods = new VslGoodsModel();
        $goods->startTrans();
        try {
            $goods_id_array = explode(',', $goods_ids);
            $goods_type = explode(',', $goods_type);
            $data = array(
                "is_new" => $goods_type[0],
                "is_recommend" => $goods_type[1],
                "is_hot" => $goods_type[2]
            );
            foreach ($goods_id_array as $k => $v) {
                $goods = new VslGoodsModel();
                $goods->save($data, [
                    'goods_id' => $v
                ]);
            }
            $goods->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $goods->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 获取商品可得积分
     *
     * @param unknown $goods_id
     */
    public function getGoodsGivePoint($goods_id)
    {
        $goods = new VslGoodsModel();
        $point_info = $goods->getInfo([
            'goods_id' => $goods_id
        ], 'give_point');
        return $point_info['give_point'];
    }

    /**
     * 通过商品skuid查询goods_id
     *
     * @param unknown $sku_id
     */
    public function getGoodsId($sku_id)
    {
        $goods_sku = new VslGoodsSkuModel();
        $sku_info = $goods_sku->getInfo([
            'sku_id' => $sku_id
        ], 'goods_id');
        return $sku_info['goods_id'];
    }

    /**
     * 获取购物车中项目
     *
     * @param array $cart_id_array
     *
     * return array $cart_lists
     */
    public function getCartList(array $cart_id_array, &$msg = '')
    {
        $cart = new VslCartModel();
        $cart_lists = $cart::all(['cart_id' => ['IN', $cart_id_array]], ['goods', 'sku', 'goods_picture']);
        foreach ($cart_lists as $k => $v) {
            $goods_name = $v->goods->goods_name;
            if (mb_strlen($v->goods->goods_name) > 10) {
                $goods_name = mb_substr($v->goods->goods_name, 0, 10) . '...';
            }
            if (empty($v->sku)) {
                $cart->destroy(['cart_id' => $v->cart_id]);
                unset($cart_lists[$k]);
                $msg .= $goods_name . "商品该sku规格不存在，已移除" . PHP_EOL;
                continue;
            }
            if ($v->sku->stock <= 0) {
//                $cart->destroy(['cart_id' => $v->cart_id]);
//                unset($cart_lists[$k]);
                $msg .= $goods_name . "商品该sku规格库存不足" . PHP_EOL;
                continue;
            }
            if ($v->goods->state != 1) {
//                $this->cartDelete($v->cart_id);
//                unset($cart_lists[$k]);
                $msg .= $goods_name . "商品该sku规格已下架" . PHP_EOL;
                continue;
            }
            $num = $v->num;
            if ($v->goods->max_buy != 0 && $v->goods->max_buy < $v->num) {
                $num = $v->goods->max_buy;
                $msg .= $goods_name . "商品该sku规格购买量大于最大购买量，购买数量已更改" . PHP_EOL;
            }

            if ($v->sku->stock < $num) {
                $num = $v->sku->stock;
            }
            if ($num != $v->num) {
                // 更新购物车
                $this->cartAdjustNum($v->cart_id, $v->sku->stock);
                $v->num = $num;
                $msg .= $goods_name . "商品该sku规格购买量大于库存，购买数量已更改" . PHP_EOL;
            }
            $v->stock = $v->sku->stock;
            $v->max_buy = $v->goods->max_buy;
            $v->point_exchange_type = $v->goods->point_exchange_type;
            $v->point_exchange = $v->goods->point_exchange;
            $v->picture_info = $v->goods_picture;
            //如果是秒杀商品并且没有结束，则取秒杀价
            if (!$v->seckill_id && getAddons('seckill', $this->website_id, $this->instance_id)) {
                $sec_server = new SeckillServer();
                $sku_id = $v->sku->sku_id;
                $condition_seckill['nsg.sku_id'] = $sku_id;
                $seckill_info = $sec_server->isSkuStartSeckill($condition_seckill);
                if ($seckill_info) {
                    $v->seckill_id = $seckill_info['seckill_id'];
                }
            }
            if (!empty($v->seckill_id) && getAddons('seckill', $this->website_id, $this->instance_id)) {
                $sec_server = new SeckillServer();
                //判断当前秒杀活动的商品是否已经开始并且没有结束
                $condition_seckill['s.website_id'] = $this->website_id;
                $condition_seckill['s.seckill_id'] = $v->seckill_id;
                $condition_seckill['nsg.sku_id'] = $v->sku->sku_id;
                $is_seckill = $sec_server->isSeckillGoods($condition_seckill);
                if (!$is_seckill) {
                    $v->price = $v->sku->price;
                    $v->seckill_id = 0;
                    $this->cartAdjustSec($v->cart_id, 0);
                    $msg .= $goods_name . "商品该sku规格秒杀活动已经结束，已更改为正常状态商品价格" . PHP_EOL;
                } else {
                    //取该商品该用户购买了多少
                    $sku_id = $v->sku->sku_id;
                    $uid = $this->uid;
                    $website_id = $this->website_id;
                    $buy_num = $this->getActivityOrderSku($uid, $sku_id, $website_id, $v->seckill_id);
                    $sec_sku_info_list = $sec_server->getSeckillSkuInfo(['seckill_id' => $v->seckill_id, 'sku_id' => $v->sku->sku_id]);
                    $v->stock = $sec_sku_info_list->remain_num;
                    $v->max_buy = (($sec_sku_info_list->seckill_limit_buy - $buy_num) < 0) ? 0 : $sec_sku_info_list->seckill_limit_buy - $buy_num;
                    $v->price = $sec_sku_info_list->seckill_price;
                }
            } else {
                $v->price = $v->sku->price;
            }
            unset($cart_lists[$k]->good, $cart_lists[$k]->sku, $cart_lists[$k]->goods_picture);
        }
        return $cart_lists;
    }

    /**
     * 获取购物车项目(PC端)
     * @param array $cart_id_array
     *
     * @return $shop_goods_lists
     */
    public function getCartListsNew(array $cart_id_array, &$msg = '', $store_id, $cart_ids)
    {
        $cart = new VslCartModel();
        $store_goods_sku_model = new VslStoreGoodsSkuModel();
        $goods_sku_model = new VslGoodsSkuModel();
        $promotion = getAddons('discount', $this->website_id) ? new Discount() : '';
        $goods_man_song_model = getAddons('fullcut', $this->website_id) ? new Fullcut() : '';
        $seckill_server = getAddons('seckill', $this->website_id, $this->instance_id) ? new Seckill() : '';
        if ($store_id && $cart_ids) {
            foreach ($cart_ids as $v) {
                $data = $cart::get(['cart_id' => $v], ['goods', 'goods_picture']);
                $store_sku = $store_goods_sku_model->getInfo(['sku_id' => $data['sku_id'], 'store_id' => $store_id], '*');
                if (empty($store_sku)) {
                    $sku = $goods_sku_model->getInfo(['sku_id' => $data['sku_id']], '*');
                    $data['sku'] = $sku;
                } else {
                    $data['sku'] = $store_sku;
                }
                $cart_lists[] = $data;
            }
        } else {
        $cart_lists = $cart::all(['cart_id' => ['IN', $cart_id_array]], ['goods', 'sku', 'goods_picture']);
        }
        $shop_goods_lists = [];
        $cart_sku_info = [];
        $shipping_lists = [];
        $goods_sku_list = '';
        $shop_goods = [];
        $member_model = new VslMemberModel();
        $member_level_info = $member_model->getInfo(['uid' => $this->uid])['member_level'];
        $member_level = new VslMemberLevelModel();
        $member_info = $member_level->getInfo(['level_id' => $member_level_info]);
        $member_discount = $member_info['goods_discount'] / 10;
        $member_is_label = $member_info['is_label'];
        //获取购物车sku的一些信息
        foreach ($cart_lists as $k => $v) {
            if (empty($v->sku)) {
                $cart->destroy(['cart_id' => $v->cart_id]);
                unset($cart_lists[$k]);
                continue;
            }
            if ($v->sku['stock'] <= 0) {
                $cart->destroy(['cart_id' => $v->cart_id]);
                unset($cart_lists[$k]);
                continue;
            }
            if ($v->goods->state != 1) {
                $this->cartDelete($v->cart_id);
                unset($cart_lists[$k]);
                continue;
            }
            $shop_id_array[] = $v['shop_id'];
            $num = $v->num;
            if ($v->goods->max_buy != 0 && $v->goods->max_buy < $v->num) {
                $num = $v->goods->max_buy;
            }

            if ($v->sku['stock'] < $num) {
                $num = $v->sku['stock'];
            }
            if ($num != $v->num) {
                // 更新购物车
                $this->cartAdjustNum($v->cart_id, $v->sku->stock);
                $v->num = $num;
            }
            if (getAddons('discount', $this->website_id)) {
                $promotion_info = $promotion->getPromotionInfo($v->goods_id, $v->goods->shop_id, $v->goods->website_id);
            } else {
                $promotion_info['discount_num'] = 10;
            }
            $seckill_id = $v->seckill_id;
            if (!empty($seckill_id) && getAddons('seckill', $this->website_id, $this->instance_id)) {
                $seckill_server = new Seckill();
                //判断该sku是否在秒杀活动内
                $is_seckill_condition['s.seckill_id'] = $seckill_id;
                $is_seckill_condition['nsg.sku_id'] = $v->sku_id;
                $is_seckill = $seckill_server->isSeckillGoods($is_seckill_condition);
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
                    $buy_num = $this->getActivityOrderSku($uid, $v->sku_id, $website_id, $seckill_id);
//                $buy_num = 0;
                    $v->stock = $remain_num;
                    $v->max_buy = (($limit_buy - $buy_num) < 0) ? 0 : ($limit_buy - $buy_num);
                    $v->price = $seckill_price;
                    //秒杀不享受其它优惠
                    $v->sku->price = $seckill_price;
                    $member_discount = 1;
                    $promotion_info['discount_num'] = 10;
                } else {
                    $v->stock = $v->sku['stock'];
                    $v->max_buy = $v->goods->max_buy;
                    $v->price = $v->sku['price'];
                    $msg .= $v->goods->goods_name . '商品秒杀活动已结束，已恢复商品原有价格' . PHP_EOL;
                }
            } else {
                $v->stock = $v->sku['stock'];
                $v->max_buy = $v->goods->max_buy;
                $v->price = $v->sku['price'];
            }

            $v->point_exchange_type = $v->goods->point_exchange_type;
            $v->point_exchange = $v->goods->point_exchange;
            $v->picture_info = $v->goods_picture;
            $v->member_dicount = $member_discount;
            if ($member_is_label) {
                $v->member_price = round($v->sku->price * $member_discount);
            } else {
                $v->member_price = round($v->sku->price * $member_discount, 2);
            }
//            // todo... by sgw
            if ($this->uid) {
                $goodsDiscountInfo = $this->getGoodsInfoOfIndependentDiscount($v['goods_id'], $v->price);
                if ($goodsDiscountInfo) {
                    $v->member_price = $goodsDiscountInfo['member_price'];
                    $v->member_dicount = $goodsDiscountInfo['member_discount'];
                }
            }
            $v->discount_id = $promotion_info['discount_id'] ?: '';
            $v->promotion_discount = round($promotion_info['discount_num'] / 10, 2);
            $v->promotion_shop_id = $promotion_info['shop_id'];
            //限时抢购new
            if ($promotion_info['integer_type'] == 1) {
                $v->promotion_price = round($v->sku->price * $promotion_info['discount_num'] / 10);
                $v->discount_price = round($v->member_price * $promotion_info['discount_num'] / 10);
            } else {
                $v->promotion_price = round($v->sku->price * $promotion_info['discount_num'] / 10, 2);
                $v->discount_price = round($v->member_price * $promotion_info['discount_num'] / 10, 2);
            }
            if ($promotion_info['discount_type'] == 2) {
                $v->promotion_price = $promotion_info['discount_num'];
                $v->discount_price = $promotion_info['discount_num'];
            }
            if (getAddons('shop', $this->website_id) && $v->shop_id) {
                $shop = new VslShopModel();
                $shop_goods_lists[$v->shop_id]['shop']['shop_name'] = $shop->getInfo(['shop_id' => $v->shop_id, 'website_id' => $this->website_id])['shop_name'];
            } else {
                $shop_goods_lists[$v->shop_id]['shop']['shop_name'] = '自营店';
            }

//            $shop_goods[$v->shop_id][$v->goods_id]['count'] += $v->num;
//            $shop_goods[$v->shop_id][$v->goods_id]['goods_id'] = $v->goods_id;
            unset($cart_lists[$k]->good, $cart_lists[$k]->sku, $cart_lists[$k]->goods_picture, $cart_lists[$k]->shop, $cart_lists[$k]->website);
            $shop_goods_lists[$v->shop_id]['sku'][$v->sku_id] = $v->toArray();
            $goods_sku_list .= ',' . $v->sku_id . ':' . $v->num;
            $cart_sku_info[$v->shop_id][$v->sku_id] = ['sku_id' => $v->sku_id, 'goods_id' => $v->goods_id, 'price' => $v->price, 'discount_price' => $v->discount_price, 'num' => $v->num];
        }
//        $goods_express = new GoodsExpress();
//        foreach($shop_goods as $shop_id => $goods){
//               $shipping_lists[$shop_id]['shop_shipping_fee'] = $goods_express->getGoodsExpressTemplate($goods, 0);
//        }
//        $return['shipping_lists'] = $shipping_lists;
        $return['goods_sku_list'] = substr($goods_sku_list, 1); // 商品sku列表
        $return['shop_goods_lists'] = $shop_goods_lists;
        $return['full_cut_lists'] = getAddons('fullcut', $this->website_id) ? $goods_man_song_model->getCartManSong($cart_sku_info) : [];

        foreach ($return['full_cut_lists'] as $shop_id => $full_cut_info) {
            foreach ($full_cut_info['discount_percent'] as $sku_id => $discount_percent) {
                if (!empty($full_cut_info['full_cut']) && $full_cut_info['full_cut']['discount'] > 0) {
                    $cart_sku_info[$shop_id][$sku_id]['full_cut_amount'] = $full_cut_info['full_cut']['discount'];
                    $cart_sku_info[$shop_id][$sku_id]['full_cut_percent'] = $full_cut_info['discount_percent'][$sku_id];
                    $cart_sku_info[$shop_id][$sku_id]['full_cut_percent_amount'] = round($full_cut_info['discount_percent'][$sku_id] * $full_cut_info['full_cut']['discount'], 2);
                }
            }
        }
        $return['cart_sku_info'] = $cart_sku_info;

        return $return;
    }

    /**
     * 暂时是移动端/app结算页面的数据获取/计算
     * @param array $sku_list
     * @param string $msg
     *
     * @return array $return_data
     */
    public function paymentData(array $sku_list, &$msg = '', $record_id = '', $group_id = '', $presell_id = '', $un_order = 0)
    {
        // 获取非秒杀,团购商品,各个类型所需的数据结构
        // $promotion_sku_list 需要计算折扣,满减,优惠券的商品,即非秒杀,团购商品
        // $return_data 全部数据
        // $return_data[$shop_id]['total_amount'] 店铺应付金额
        // $return_data[$shop_id]['goods_list'] 店铺商品
        // $return_data[$shop_id]['full_cut'] 店铺满减
        // $return_data[$shop_id]['coupon_list'] 店铺优惠券列表
        // $return_data[$shop_id]['member_promotion'] 店铺会员优惠总金额
        // $return_data[$shop_id]['discount_promotion'] 店铺限时折扣优惠总金额
        // $return_data[$shop_id]['full_cut_promotion'] 店铺满减送优惠总金额
        // $promotion_sku_list 获取满减送，优惠券信息的商品数据
        $sku_model = new VslGoodsSkuModel();
        $new_sku_list = $return_data = $sku_id_array = $seckill_sku = $shipping_sku = $record_sku = $promotion_sku_list = [];
        foreach ($sku_list as $k => $v) {
            $new_sku_list[$v['sku_id']] = $v;
            $sku_id_array[] = $v['sku_id'];
            $sku_detail[$k] = $sku_model::get(['sku_id' => $v['sku_id']], ['goods']);
            $sku_detail[$k]['channel_id'] = $v['channel_id'] ?: 0;
            $sku_detail[$k]['bargain_id'] = $v['bargain_id'] ?: 0;
            $sku_detail[$k]['coupon_id'] = empty($v['coupon_id']) ? 0 : $v['coupon_id'];
            $sku_detail[$k]['num'] = $v['num'] ?: 0;
        }
        $discount_service = getAddons('discount', $this->website_id) ? new Discount() : '';
        $full_cut_service = getAddons('fullcut', $this->website_id) ? new Fullcut() : '';
        $shop = getAddons('shop', $this->website_id) ? new VslShopModel() : '';
        $order_goods_service = new OrderGoods();
        $album_picture_model = new AlbumPictureModel();
        $sec_server = getAddons('seckill', $this->website_id, $this->instance_id) ? new SeckillServer() : '';
        $goods_service = new Goods();
        $group_server = getAddons('groupshopping', $this->website_id, $this->instance_id) ? new GroupShoppingServer() : '';
        $cart_model = new VslCartModel();
        $goods_spec_value = new VslGoodsSpecValueModel();
//        $member_model = new VslMemberModel();
//        $member_level_info = $member_model->getInfo(['uid'=>$this->uid])['member_level'];
//        $member_level = new VslMemberLevelModel();
//        $member_info = $member_level->getInfo(['level_id'=>$member_level_info]);
//        $member_discount = $member_info['goods_discount'] / 10;
//        $member_is_label = $member_info['is_label'];
        $total_account = 0;

        $shop_member_price = 0;
        $platform_member_price = 0;
        foreach ($sku_detail as $k => $v) {
            $presell_shop_id = $v->goods->shop_id;
            //砍价活动id
            $bargain_id = $new_sku_list[$v->sku_id]['bargain_id'] ?: 0;
            $channel_id = $new_sku_list[$v->sku_id]['channel_id'] ?: 0;
            $temp_sku = [];
            $temp_sku['goods_name'] = $v->goods->goods_name;
            if (getAddons('presell', $this->website_id, $this->instance_id) && !$un_order) {
                $presell = new PresellService();
                $is_presell = $presell->getPresellInfoByGoodsIdIng($v->goods_id);
                $presell_arr = objToArr($is_presell);
                $presell_id = $presell_arr[0]['id'];
            }

            $is_group = getAddons('groupshopping', $this->website_id, $this->instance_id) && $group_server->isGroupGoods($v->goods_id);
            //判断此用户有没有上级渠道商，如果有，库存显示平台库存+直属上级渠道商的库存
            if($v->goods->shop_id == 0) {
                if(getAddons('channel',$this->website_id,0)) {
                    if(empty($channel_id)) {
                        $member_model = new VslMemberModel();
                        $referee_id = $member_model->Query(['uid'=>$this->uid,'website_id'=>$this->website_id],'referee_id')[0];
                        if($referee_id) {//如果有上级，判断是不是渠道商
                            $channel_model = new VslChannelModel();
                            $is_channel = $channel_model->Query(['uid'=>$referee_id,'website_id'=>$this->website_id],'channel_id')[0];
                            if($is_channel) {//如果上级是渠道商，判断上级渠道商有没有采购过这个商品
                                $channel_goods_model = new VslChannelGoodsModel();
                                $channel_goods_id = $channel_goods_model->Query(['goods_id'=>$v->goods_id,'channel_id'=>$is_channel,'website_id'=>$this->website_id],'goods_id')[0];
                                if($channel_goods_id) {
                                    $channel_id = $is_channel;
                                }
                            }
                        }
                    }
                }
            }
            // 活动影响的内容 是 价格、限购、库存
            //判断当前秒杀活动的商品是否已经开始并且没有结束
            if (!empty($new_sku_list[$v->sku_id]['seckill_id']) && getAddons('seckill', $this->website_id, $this->instance_id) && !$un_order) {
                $seckill_id = $new_sku_list[$v->sku_id]['seckill_id'];
                //判断当前秒杀活动的商品是否已经开始并且没有结束
                $condition_seckill['s.website_id'] = $this->website_id;
                $condition_seckill['nsg.sku_id'] = $v->sku_id;
                $condition_seckill['s.seckill_id'] = $seckill_id;
                $is_seckill = $sec_server->isSeckillGoods($condition_seckill);
                if (!$is_seckill && !$un_order) {
                    $temp_sku['price'] = $v->price;
                    $temp_sku['seckill_id'] = 0;
                    if (!empty($new_sku_list[$v->sku_id]['cart_id'])) {
                        $this->cartAdjustSec($new_sku_list[$v->sku_id]['cart_id'], 0);
                    }
                    $msg .= $v->goods->goods_name . "商品该sku规格秒杀活动已经结束，已更改为正常状态商品价格" . PHP_EOL;
                } else {
                    //取该商品该用户购买了多少
                    $sku_id = $v->sku_id;
                    $uid = $this->uid;
                    $website_id = $this->website_id;
                    $buy_num = $this->getActivityOrderSku($uid, $sku_id, $website_id, $new_sku_list[$v->sku_id]['seckill_id']);
                    $sec_sku_info_list = $sec_server->getSeckillSkuInfo(['seckill_id' => $seckill_id, 'sku_id' => $v->sku_id]);
                    $temp_sku['stock'] = $sec_sku_info_list->remain_num;
                    $temp_sku['max_buy'] = (($sec_sku_info_list->seckill_limit_buy - $buy_num) < 0) ? 0 : (($sec_sku_info_list->seckill_limit_buy - $buy_num) > $temp_sku['stock'] ? $temp_sku['stock'] : $sec_sku_info_list->seckill_limit_buy - $buy_num);
                    $new_sku_list[$v->sku_id]['num'] = $new_sku_list[$v->sku_id]['num'] > $temp_sku['max_buy'] ? $temp_sku['max_buy'] : $new_sku_list[$v->sku_id]['num'];
                    $temp_sku['price'] = $sec_sku_info_list->seckill_price;
                    $temp_sku['member_price'] = $sec_sku_info_list->seckill_price;
                    $temp_sku['discount_price'] = $sec_sku_info_list->seckill_price;
                    $temp_sku['channel_id'] = $channel_id?:0;
                }
            } elseif ((!empty($group_id) || !empty($record_id))) {
                if (!$un_order) {
                    if (!$is_group) {
                        return ['code' => -2, 'message' => '拼团活动已结束或已关闭'];
                    }
                    $group_sku_info = $group_server->getGroupSkuInfo(['sku_id' => $v->sku_id, 'goods_id' => $v->goods_id, 'group_id' => $group_id]);
                    $uid = $this->uid;
                    $website_id = $this->website_id;
                    $buy_num = $goods_service->getActivityOrderSkuForGroup($uid, $v->sku_id, $website_id, $group_id);

                    $temp_sku['price'] = $group_sku_info->group_price;
                    $temp_sku['max_buy'] = $group_sku_info->group_limit_buy - $buy_num; // 限购数量
                    if ($temp_sku['max_buy'] < 0) {
                        $temp_sku['max_buy'] = 0;
                    }
                    $temp_sku['member_price'] = $group_sku_info->group_price;
                    $temp_sku['discount_price'] = $group_sku_info->group_price;
                    if($channel_id) {
                        //如果有上级渠道商，库存显示平台库存+直属上级渠道商的库存
                        $sku_id = $v->sku_id;
                        $channel_sku_mdl = new VslChannelGoodsSkuModel();
                        $channel_cond['channel_id'] = $channel_id;
                        $channel_cond['sku_id'] = $sku_id;
                        $channel_cond['website_id'] = $this->website_id;
                        $channel_stock = $channel_sku_mdl->getInfo($channel_cond, 'stock')['stock'];
                        $temp_sku['stock'] = $v->stock + $channel_stock;
                    }else{
                    $temp_sku['stock'] = $v->stock;
                    }
                    $temp_sku['channel_id'] = $channel_id?:0;
                }
            } elseif (getAddons('presell', $this->website_id, $this->instance_id) && !empty($presell_id) && !$un_order) {
                $temp_sku['presell_id'] = $presell_id;
                $temp_sku['channel_id'] = $channel_id?:0;
            } elseif (!empty($bargain_id) && getAddons('bargain', $this->website_id, $this->instance_id) && !$un_order) {//砍价活动
                $bargain_server = new Bargain();
                $condition_bargain['bargain_id'] = $bargain_id;
                $condition_bargain['website_id'] = $this->website_id;
                $sku_id = $v->sku_id;
                $uid = $this->uid;
                $website_id = $this->website_id;
                $is_bargain = $bargain_server->isBargain($condition_bargain, $uid);
                if ($is_bargain && !$un_order) {
                    $orderService = new orderServer();
                    $buy_num = $orderService->getActivityOrderSkuNum($uid, $sku_id, $website_id, 3, $bargain_id);
                    $bargain_stock = $is_bargain['bargain_stock'];
                    $max_buy = $is_bargain['limit_buy'] - $buy_num;
                    $temp_sku['max_buy'] = ($max_buy > 0) ? ($max_buy > $bargain_stock ? $bargain_stock : $max_buy) : 0; // 限购数量
                    $temp_sku['price'] = $is_bargain['my_bargain']['now_bargain_money'];
                    $temp_sku['discount_price'] = $is_bargain['my_bargain']['now_bargain_money'];
                    $temp_sku['stock'] = $bargain_stock;
                    $temp_sku['bargain_id'] = $bargain_id;
                    $temp_sku['channel_id'] = $channel_id?:0;
                } else {
                    return ['code' => -2, 'message' => '砍价活动已结束或已关闭'];
                }
            } else {
                //普通商品
                if ($v->stock <= 0 && empty($new_sku_list[$v->sku_id]['seckill_id']) && empty($channel_id)) {
//                    if (!empty($new_sku_list[$v->sku_id]['cart_id'])) {
//                        $cart_model->destroy($new_sku_list[$v->sku_id]['cart_id']);
//                    }
                    return ['code' => -2, 'message' => $v->goods->goods_name . '商品库存不足' . PHP_EOL];
                }
                if ($v->goods->state != 1) {
//                    if (!empty($new_sku_list[$v->sku_id]['cart_id'])) {
//                        $cart_model->destroy($new_sku_list[$v->sku_id]['cart_id']);
//                    }
                    return ['code' => -2, 'message' => $v->goods->goods_name . '商品为不可购买状态' . PHP_EOL];
                }
                if ($v->goods->max_buy != 0 && $v->goods->max_buy < $v->num && empty($presell_id) && empty($channel_id)) {
                    $temp_sku['num'] = $v->goods->max_buy;
                    $msg .= $v->goods->goods_name . '商品该sku规格购买量大于最大购买量，购买数量已更改' . PHP_EOL;
                }
                if ($v->stock < $new_sku_list[$v->sku_id]['num'] && empty($presell_id) && empty($channel_id)) {
                    $temp_sku['num'] = $v->stock;
                    $msg .= $v->goods->goods_name . '商品该sku规格购买量大于剩余库存，购买数量已更改' . PHP_EOL;
                }
//                // todo... by sgw返回max_buy
                $max_buy = $this->getGoodsMaxBuyNums($v['goods_id'], $v['sku_id']);
                $temp_sku['max_buy'] = ($max_buy - $new_sku_list[$v->sku_id]['num']) > 0 ? $max_buy - $new_sku_list[$v->sku_id]['num'] : 0;//暂时
                $temp_sku['stock'] = $v->stock;
                if (!empty($channel_id) && getAddons('channel', $this->website_id) && !$un_order) {
                    $sku_id = $v->sku_id;
                    $channel_sku_mdl = new VslChannelGoodsSkuModel();
                    $channel_cond['channel_id'] = $channel_id;
                    $channel_cond['sku_id'] = $sku_id;
                    $channel_cond['website_id'] = $this->website_id;
                    $channel_stock = $channel_sku_mdl->getInfo($channel_cond, 'stock')['stock'];
                    $temp_sku['max_buy'] = $channel_stock + $v->stock;
                    $temp_sku['stock'] = $channel_stock + $v->stock;
                    $temp_sku['channel_id'] = $channel_id;
                }
                $temp_sku['price'] = $v->price;   // todo....
                $limit_discount_info = getAddons('discount', $this->website_id) ? $discount_service->getPromotionInfo($v->goods_id, $v->goods->shop_id, $v->goods->website_id) : ['discount_num' => 10];

                // 会员折扣 by sgw商品价格计算
                $goodsDiscountInfo = $this->getGoodsInfoOfIndependentDiscount($v->goods_id, $v->price);//计算会员折扣价

                //如果是限时折扣是店铺设置的 需要店铺负责
                // $return_data[$v->goods->shop_id]['shop_member_price'] += $goodsDiscountInfo['shop_member_price'] * $new_sku_list[$v->sku_id]['num'];
                // $return_data[$v->goods->shop_id]['platform_member_price'] += $goodsDiscountInfo['platform_member_price'] * $new_sku_list[$v->sku_id]['num'];

                if ($goodsDiscountInfo) {
                    $temp_sku['member_price'] = $goodsDiscountInfo['member_price'];
                    //如果存在限时折扣 则会员价为原价
                    if($limit_discount_info['discount_id']){
                        $temp_sku['member_price'] = $temp_sku['price'];
                    }else{
                        $return_data[$v->goods->shop_id]['shop_member_price'] += $goodsDiscountInfo['shop_member_price'] * $new_sku_list[$v->sku_id]['num'];
                        $return_data[$v->goods->shop_id]['platform_member_price'] += $goodsDiscountInfo['platform_member_price'] * $new_sku_list[$v->sku_id]['num'];
                    }
                }
                if ($limit_discount_info['integer_type'] == 1) {
                    $temp_sku['discount_price'] = round($temp_sku['member_price'] * $limit_discount_info['discount_num'] / 10);
                } else {
                    $temp_sku['discount_price'] = round($temp_sku['member_price'] * $limit_discount_info['discount_num'] / 10, 2);
                }
                if ($limit_discount_info['discount_type'] == 2) {
                    $temp_sku['discount_price'] = $limit_discount_info['discount_num'];
                }
                if ($limit_discount_info['shop_id'] > 0) {
                    $return_data[$v->goods->shop_id]['shop_member_price'] += ($temp_sku['member_price'] - $temp_sku['discount_price']) * $new_sku_list[$v->sku_id]['num'];
                } else {
                    $return_data[$v->goods->shop_id]['platform_member_price'] += ($temp_sku['member_price'] - $temp_sku['discount_price']) * $new_sku_list[$v->sku_id]['num'];
                }
            }
            $temp_sku['min_buy'] = 1;

            $return_data[$v->goods->shop_id]['shop_id'] = $v->goods->shop_id;

            if (empty($return_data[$v->goods->shop_id]['shop_name'])) {
                if (getAddons('shop', $this->website_id) && $v->goods->shop_id) {
                    $return_data[$v->goods->shop_id]['shop_name'] = $shop->getInfo(['shop_id' => $v->goods->shop_id, 'website_id' => $v->goods->website_id])['shop_name'];
                } else {
                    $return_data[$v->goods->shop_id]['shop_name'] = '自营店';
                }
            }


            $temp_sku['sku_id'] = $v->sku_id;
            $temp_sku['num'] = $new_sku_list[$v->sku_id]['num'];
            $temp_sku['goods_id'] = $v->goods_id;
            $temp_sku['shop_id'] = $v->goods->shop_id;
            $temp_sku['goods_type'] = $v->goods->goods_type;
            $temp_sku['point_deduction_max'] = $v->goods->point_deduction_max;
            $temp_sku['point_return_max'] = $v->goods->point_return_max;
            $temp_sku['shipping_fee_type'] = $v->goods->shipping_fee_type;
            //暂时取商品的图片，不取规格图
            /*$picture = $order_goods_service->getSkuPictureBySkuId($v);
            $picture_info = $album_picture_model->get($picture == 0 ? $v->goods->picture : $picture);*/
            $picture_info = $album_picture_model->get($v->goods->picture);
            $temp_sku['goods_pic'] = $picture_info ? getApiSrc($picture_info->pic_cover) : '';

            $temp_sku['discount_id'] = $limit_discount_info['discount_id'] ?: '';
            $temp_sku['seckill_id'] = $new_sku_list[$v->sku_id]['seckill_id'] ?: '';
            if (empty($is_bargain) && empty($temp_sku['seckill_id']) && (empty($group_id) && empty($record_id)) && empty($presell_id) && !$un_order) {
                $promotion_sku_list[$v->goods->shop_id][$v->sku_id] = $temp_sku;
                $return_data[$v->goods->shop_id]['total_amount'] += $temp_sku['discount_price'] * $temp_sku['num'];
                // 用于计算 折扣 类型优惠券的总额
                $return_data[$v->goods->shop_id]['amount_for_coupon_discount'] += $temp_sku['discount_price'] * $temp_sku['num'];
                // 店铺会员优惠总金额
                $return_data[$v->goods->shop_id]['member_promotion'] += ($temp_sku['price'] - $temp_sku['member_price']) * $temp_sku['num'];
                // 店铺限时折扣优惠总金额
                $return_data[$v->goods->shop_id]['discount_promotion'] += ($temp_sku['member_price'] - $temp_sku['discount_price']) * $temp_sku['num'];
            } else {
                $return_data[$v->goods->shop_id]['total_amount'] += $temp_sku['price'] * $temp_sku['num'];
                // 将显示的价格全部设置为discount_price
                $temp_sku['discount_price'] = $temp_sku['price'];
                // 店铺会员优惠总金额
                $return_data[$v->goods->shop_id]['member_promotion'] += 0;
                // 店铺限时折扣优惠总金额
                $return_data[$v->goods->shop_id]['discount_promotion'] += 0;
            }
            // 规格
            $spec_info = [];
            if ($v['attr_value_items']) {
                $sku_spec_info = explode(';', $v['attr_value_items']);
                foreach ($sku_spec_info as $k_spec => $v_spec) {
                    $spec_value_id = explode(':', $v_spec)[1];
                    $spec_info[$k_spec] = $order_goods_service->getSpecInfo($spec_value_id, $temp_sku['goods_id']);
                }
            }
            $temp_sku['spec'] = $spec_info;
            //判断是否有传预售ID
            if (!$presell_id) {
                $return_data[$presell_shop_id]['presell_info'] = null;
            } else {
                if (getAddons('presell', $this->website_id, $this->instance_id) && !$un_order) {
                    //从SKUID和预售ID找到相关信息
                    $presell = new PresellService();
                    $sku_id = $sku_list[0]['sku_id'];
                    $info = $presell->get_presell_by_sku($presell_id, $sku_id);
                    if ($info) {
                        //判断当前用户购买了多少件该活动商品
                        $uid = $this->uid;
                        $p_cond['activity_id'] = $presell_id;
                        $p_cond['uid'] = $uid;
                        $p_cond['sku_id'] = $v->sku_id;
                        $p_cond['buy_type'] = 4;
                        $p_cond['website_id'] = $this->website_id;
                        $aosr_mdl = new VslActivityOrderSkuRecordModel();
                        $user_already_buy = $aosr_mdl->getInfo($p_cond, 'num')['num'];
                        $return_data[$presell_shop_id]['presell_info']['maxbuy'] = ($info['maxbuy'] - $user_already_buy) > 0 ? ($info['maxbuy'] - $user_already_buy) : 0;
                        $return_data[$presell_shop_id]['presell_info']['firstmoney'] = $info['firstmoney'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['allmoney'] = $info['allmoney'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['presellnum'] = $info['presellnum'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['vrnum'] = $info['vrnum'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['pay_start_time'] = $info['pay_start_time'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['pay_end_time'] = $info['pay_end_time'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['goods_num'] = $sku_list[0]['num'] ?: 0;
                        $return_data[$presell_shop_id]['total_amount'] = $info['firstmoney'] * $sku_list[0]['num'] ?: 0;
                        $have_buy = $presell->get_presell_sku_num($presell_id, $temp_sku['sku_id']);
                        $return_data[$presell_shop_id]['presell_info']['over_num'] = $info['presellnum'] - $have_buy;  //已购买人数
                        $total_account = $info['firstmoney'] * $new_sku_list[$v->sku_id]['num'];
                        $temp_sku['price'] = $info['firstmoney'];
                    }
                }
            }

            //优惠券
            if (getAddons('coupontype', $this->website_id) && !$un_order) {
                $temp_sku['coupon_id'] = $v->coupon_id;
            }

            $return_data[$v->goods->shop_id]['goods_list'][] = $temp_sku;
            // 下面的满减送和优惠券可能不进去循环，先初始化一些数据
            $return_data[$v->goods->shop_id]['full_cut'] = (object)[];
            $return_data[$v->goods->shop_id]['coupon_list'] = [];
            $return_data[$v->goods->shop_id]['coupon_num'] = 0;

            //卡券核销门店
            if (getAddons('store', $this->website_id, $this->instance_id) && $v['goods']['goods_type'] == 0 && !$un_order) {
                $store = new Store();
                //判断是否开启了门店自提
                $storeSet = $store->getStoreSet($v->goods->shop_id)['is_use'];
                if($storeSet) {
                $store_list = $v['goods']['store_list'];
                if (empty($store_list)) {
                    $return_data[$v->goods->shop_id]['store_list'] = [];
                } else {
                    $store_id = explode(',', $store_list); //适用的门店ID
                    $condition = [];
                    $condition['website_id'] = $v['website_id'];
                    $condition['store_id'] = ['IN', $store_id];
                    $lng = input('lng', 0);
                    $lat = input('lat', 0);
                    $place = ['lng' => $lng, 'lat' => $lat];
                    $store_list = $store->storeListForFront(1, 20, $condition, $place);
                    if (empty($store_list)) {
                        $return_data[$v->goods->shop_id]['store_list'] = [];
                    } else {
                        $return_data[$v->goods->shop_id]['store_list'] = $store_list['store_list'];
                    }
                }
                }else{
                    $return_data[$v->goods->shop_id]['store_list'] = [];
                }
            }
        }
        // 满减送
        if (getAddons('fullcut', $this->website_id) && !$un_order) {
            $full_cut_lists = $full_cut_service->getPaymentFullCut($promotion_sku_list);
            foreach ($full_cut_lists as $kk => $vv) {
                if (empty($vv['man_song_id'])) {
                    unset($full_cut_lists[$kk]);
                }
            }
            $full_cut_limit = [];
            foreach ($full_cut_lists as $shop_id => $full_cut_info) {
                foreach ($full_cut_info['discount_percent'] as $sku_id => $discount_percent) {
                    if (!empty($full_cut_info) && $full_cut_info['discount'] > 0) {
                        // 计算优惠券需要的信息
                        $promotion_sku_list[$shop_id][$sku_id]['full_cut_amount'] = $full_cut_info['discount'];
                        $promotion_sku_list[$shop_id][$sku_id]['full_cut_percent'] = $full_cut_info['discount_percent'][$sku_id];
                        $promotion_sku_list[$shop_id][$sku_id]['full_cut_percent_amount'] = round($full_cut_info['discount_percent'][$sku_id] * $full_cut_info['discount'], 2);
                    }
                }
                $return_data[$shop_id]['total_amount'] -= $full_cut_info['discount'];
                $return_data[$shop_id]['amount_for_coupon_discount'] -= $full_cut_info['discount'];
                $full_cut_limit[$shop_id] = $full_cut_info['goods_limit'];
                unset($full_cut_info['discount_percent']);
                $return_data[$shop_id]['full_cut'] = $full_cut_info ?: (object)[];
                if (!empty($presell_id)) {
                    $return_data[$shop_id]['full_cut'] = (object)[];
                }
            }

            if (empty($presell_id)) {
                $full_cut_compute = [];
                foreach ($promotion_sku_list as $k => $v) {
                    foreach ($v as $k2 => $v2) {
                        $full_cut_compute[$k2]['full_cut_amount'] = $v2['full_cut_amount'];
                        $full_cut_compute[$k2]['full_cut_percent'] = $v2['full_cut_percent'];
                        $full_cut_compute[$k2]['full_cut_percent_amount'] = $v2['full_cut_percent_amount'];
                    }
                }
                foreach ($return_data as $k => $v) {
                    $full_cut_goods = [];
                    if (!empty($full_cut_limit[$k])) {
                        foreach ($full_cut_limit[$k] as $k3 => $v3) {
                            $full_cut_goods[$v3] = 1;
                        }
                        foreach ($v['goods_list'] as $k2 => $v2) {
                            if ($full_cut_goods[$v2['goods_id']] == 1) {
                                $return_data[$k]['goods_list'][$k2]['full_cut_sku_amount'] = $full_cut_compute[$v2['sku_id']]['full_cut_amount'];
                                $return_data[$k]['goods_list'][$k2]['full_cut_sku_percent'] = $full_cut_compute[$v2['sku_id']]['full_cut_percent'];
                                $return_data[$k]['goods_list'][$k2]['full_cut_sku_percent_amount'] = $full_cut_compute[$v2['sku_id']]['full_cut_percent_amount'];
                            }
                        }
                    } else {
                        foreach ($v['goods_list'] as $k2 => $v2) {
                            $return_data[$k]['goods_list'][$k2]['full_cut_sku_amount'] = $full_cut_compute[$v2['sku_id']]['full_cut_amount'];
                            $return_data[$k]['goods_list'][$k2]['full_cut_sku_percent'] = $full_cut_compute[$v2['sku_id']]['full_cut_percent'];
                            $return_data[$k]['goods_list'][$k2]['full_cut_sku_percent_amount'] = $full_cut_compute[$v2['sku_id']]['full_cut_percent_amount'];
                        }
                    }
                }
            }
        }
        //end 满减送

        // 优惠券
        if (getAddons('coupontype', $this->website_id) && !$un_order) {
            $coupon_service = new Coupon();
            $coupon_list = $coupon_service->getMemberCouponListNew($promotion_sku_list); // 获取优惠券

            $coupon_compute = [];
            foreach ($coupon_list as $shop_id => $v) {
                foreach ($v['coupon_info'] as $coupon_id => $c) {
                    $temp_coupon = [];
                    $temp_coupon['coupon_id'] = $c['coupon_id'];
                    $temp_coupon['coupon_name'] = $c['coupon_type']['coupon_name'];
                    $temp_coupon['coupon_genre'] = $c['coupon_type']['coupon_genre'];
                    $temp_coupon['shop_range_type'] = $c['coupon_type']['shop_range_type'];
                    $temp_coupon['at_least'] = $c['coupon_type']['at_least'];
                    $temp_coupon['money'] = $c['coupon_type']['money'];
                    $temp_coupon['discount'] = $c['coupon_type']['discount'];
                    $temp_coupon['start_time'] = $c['coupon_type']['start_time'];
                    $temp_coupon['end_time'] = $c['coupon_type']['end_time'];

                    $temp_coupon['shop_id'] = $c['coupon_type']['shop_id'];

                    $return_data[$shop_id]['coupon_list'][] = $temp_coupon;
                }
                $return_data[$shop_id]['coupon_num'] = count($v['coupon_info']);
                $coupon_compute[$shop_id] = $v['sku_percent'];
                //有预售则清空
                if (!empty($presell_id)) {
                    $return_data[$shop_id]['coupon_list'][] = [];
                    $return_data[$shop_id]['coupon_num'] = 0;
                }
            }
            if (empty($presell_id)) {
                foreach ($return_data as $k => $v) {
                    $return_data[$k]['coupon_promotion'] = 0;
                    foreach ($v['goods_list'] as $k2 => $v2) {
                        if ($v2['coupon_id'] > 0) {
                            $return_data[$k]['goods_list'][$k2]['coupon_sku_percent'] = $coupon_compute[$k][$v2['coupon_id']][$v2['sku_id']]['coupon_percent'];
                            $return_data[$k]['goods_list'][$k2]['coupon_sku_percent_amount'] = $coupon_compute[$k][$v2['coupon_id']][$v2['sku_id']]['coupon_percent_amount'];
                            $return_data[$k]['coupon_promotion'] += $return_data[$k]['goods_list'][$k2]['coupon_sku_percent_amount'];
                        }
                    }

                    $return_data[$k]['total_amount'] -= $return_data[$k]['coupon_promotion'];
                }
            }
        }

        foreach ($return_data as &$v) {
            if ($total_account != 0) {
                $v['total_amount'] = $total_account;
            } else {
                $v['total_amount'] = ($v['total_amount'] > 0) ? $v['total_amount'] : 0;
            }
            $v['amount_for_coupon_discount'] = ($v['amount_for_coupon_discount'] > 0) ? $v['amount_for_coupon_discount'] : 0;
        }
        unset($v);

        return $return_data;
    }

    /**
     * 获取购物车
     *
     * @param unknown $uid
     */
    public function getCart($uid, $shop_id = 0, &$msg = '')
    {
        if ($uid > 0) {
            $cart = new VslCartModel();
            $cart_goods_list = null;
            if ($shop_id == 0) {
                $cart_goods_list = $cart->getQuery([
                    'buyer_id' => $this->uid,
                    'website_id' => $this->website_id
                ], '*', '');
            } else {

                $cart_goods_list = $cart->getQuery([
                    'buyer_id' => $this->uid,
                    'shop_id' => $shop_id,
                    'website_id' => $this->website_id
                ], '*', '');
            }
        } else {
            $cart_goods_list = cookie('cart_array' . $this->website_id);
            if (empty($cart_goods_list)) {
                $cart_goods_list = array();
            } else {
                $cart_goods_list = json_decode($cart_goods_list, true);
            }
        }
        if (!empty($cart_goods_list)) {
            foreach ($cart_goods_list as $k => $v) {
                $goods = new VslGoodsModel();
                $goods_info = $goods->getInfo([
                    'goods_id' => $v['goods_id']
                ], 'max_buy,state,point_exchange_type,point_exchange,goods_name,price, picture, min_buy, promotion_type');
                //获取当前商品是否在什么活动中
                $promotion_type = $goods_info['promotion_type'];
                $cart_goods_list[$k]['promotion_type'] = $promotion_type;
                // 获取商品sku信息
                $goods_sku = new VslGoodsSkuModel();
                $sku_info = $goods_sku->getInfo([
                    'sku_id' => $v['sku_id']
                ], 'stock, price, sku_name, promote_price');
                $goods_name = $goods_info['goods_name'];
                if (mb_strlen($goods_info['goods_name']) > 10) {
                    $goods_name = mb_substr($v->goods['goods_name'], 0, 10) . '...';
                }
                // 验证商品或sku是否存在,不存在则从购物车移除
                if ($uid > 0) {
                    if (empty($goods_info)) {
                        $cart->destroy([
                            'goods_id' => $v['goods_id'],
                            'buyer_id' => $uid
                        ]);
                        unset($cart_goods_list[$k]);
                        $msg .= "购物车内商品发上变化，已重置购物车" . PHP_EOL;
                        continue;
                    }
                    if (empty($sku_info)) {
                        unset($cart_goods_list[$k]);
                        $cart->destroy([
                            'buyer_id' => $uid,
                            'sku_id' => $v['sku_id']
                        ]);
                        $msg .= $goods_name . "商品无sku规格信息，已移除" . PHP_EOL;
                        continue;
                    }
                } else {
                    if (empty($goods_info)) {
                        unset($cart_goods_list[$k]);
                        $this->cartDelete($v['cart_id']);
                        $msg .= "购物车内商品发上变化，已重置购物车" . PHP_EOL;
                        continue;
                    }
                    if (empty($sku_info)) {
                        unset($cart_goods_list[$k]);
                        $this->cartDelete($v['cart_id']);
                        $msg .= $goods_name . "商品无sku规格信息，已移除" . PHP_EOL;
                        continue;
                    }
                }
                if ($goods_info['state'] != 1) {
//                    unset($cart_goods_list[$k]);
//                    // 更新cookie购物车
//                    $this->cartDelete($v['cart_id']);
                    $msg .= $goods_name . "商品该sku规格已下架" . PHP_EOL;
                    continue;
                }
                $num = $v['num'];

                //判断此用户有没有上级渠道商，如果有，库存显示平台库存+直属上级渠道商的库存
                $channel_stock = 0;
                if(getAddons('channel',$this->website_id,0)) {
                    $member_model = new VslMemberModel();
                    $referee_id = $member_model->Query(['uid'=>$this->uid,'website_id'=>$this->website_id],'referee_id')[0];
                    if($referee_id) {//如果有上级，判断是不是渠道商
                        $channel_model = new VslChannelModel();
                        $is_channel = $channel_model->Query(['uid'=>$referee_id,'website_id'=>$this->website_id],'channel_id')[0];
                        if($is_channel) {//如果上级是渠道商，判断上级渠道商有没有采购过这个商品
                            $channel_sku_mdl = new VslChannelGoodsSkuModel();
                            $channel_cond['channel_id'] = $is_channel;
                            $channel_cond['sku_id'] = $v['sku_id'];
                            $channel_cond['website_id'] = $this->website_id;
                            $channel_stock = $channel_sku_mdl->getInfo($channel_cond, 'stock')['stock'];
                        }
                    }
                }
                if ($sku_info['stock'] + $channel_stock < $num) {
                    $num = $sku_info['stock'] + $channel_stock;
                }
                // 商品最小购买数大于现购买数
                if ($goods_info['min_buy'] > 0 && $num < $goods_info['min_buy']) {
                    $num = $goods_info['min_buy'];
                    $msg .= $goods_name . "商品该sku规格现购买数小于最小购买数，已修改购物数量" . PHP_EOL;
                }
                // 商品最小购买数大于现有库存
                if ($goods_info['min_buy'] > $sku_info['stock'] + $channel_stock) {
//                    unset($cart_goods_list[$k]);
//                    // 更新cookie购物车
//                    $this->cartDelete($v['cart_id']);
                    $msg .= $goods_name . "商品该sku规格最小购买数大于现有库存，已修改购物数量" . PHP_EOL;
                    continue;
                }
                if ($num != $v['num']) {
                    // 更新购物车
                    $cart_goods_list[$k]['num'] = $num;
                    $this->cartAdjustNum($v['cart_id'], $num);
                }
                if((getAddons('presell', $this->website_id, $this->instance_id))){
                    //判断当前商品是否在预售活动中
                    $presell = new Presell();
                    $is_presell = $presell->getIsInPresell($v['goods_id']);
                }
                // 为cookie信息完善商品和sku信息
                if ($uid > 0) {
                    // 查看用户会员价

                    // todo... 会员折扣 by sgw商品价格计算
                    $goodsDiscountInfo = $this->getGoodsInfoOfIndependentDiscount($v->goods_id, $sku_info['price']);//计算会员折扣价
                    if ($goodsDiscountInfo) {
                        $member_price = $goodsDiscountInfo['member_price'];
                    }
                    if (getAddons('seckill', $this->website_id, $this->instance_id)) {
                        //判断是否有秒杀的商品并且是否过期，若有直接取秒杀价
                        $sec_server = new SeckillServer();
                        if (!empty($v['seckill_id'])) {
                            $condition_seckill['s.seckill_id'] = $v['seckill_id'];
                            $condition_seckill['nsg.sku_id'] = $v['sku_id'];
                            $is_seckill = $sec_server->isSeckillGoods($condition_seckill);
                        } else {
                            $condition_seckill['nsg.sku_id'] = $v['sku_id'];
                            $is_seckill = $sec_server->isSkuStartSeckill($condition_seckill);
                            if ($is_seckill) {
                                $v['seckill_id'] = $is_seckill['seckill_id'];
                                $seckill_data['cart_id'] = $v["cart_id"];
                                $seckill_data['seckill_id'] = $is_seckill['seckill_id'];
                                $cart->data($seckill_data, true)->isupdate(true)->save();
                            }
                        }
                    }


                    if ($is_seckill) {
                        //取该商品该用户购买了多少
                        $sku_id = $v['sku_id'];
                        $uid = $this->uid;
                        $website_id = $this->website_id;
                        $buy_num = $this->getActivityOrderSku($uid, $sku_id, $website_id, $v['seckill_id']);
                        $sec_sku_info_list = $sec_server->getSeckillSkuInfo(['seckill_id' => $v->seckill_id, 'sku_id' => $sku_id]);

                        $goods_info['max_buy'] = (($sec_sku_info_list->seckill_limit_buy - $buy_num) < 0) ? $sec_sku_info_list->seckill_limit_buy : $sec_sku_info_list->seckill_limit_buy - $buy_num;
                        $goods_info['max_buy'] = $goods_info['max_buy'] > $sku_info['stock'] ? $sku_info['stock'] : $goods_info['max_buy'];
                        //如果最大购买数小于购物车的数量并且不等于0
                        if ($goods_info['max_buy'] != 0 && $goods_info['max_buy'] < $v['num']) {
                            // 更新购物车
                            $cart_goods_list[$k]['num'] = $goods_info['max_buy'];
                            $this->cartAdjustNum($v['cart_id'], $goods_info['max_buy']);
                        }
                        if ($goods_info['max_buy'] == 0) {
                            unset($cart_goods_list[$k]);
                            $this->cartDelete($v['cart_id']);
                            $msg .= $goods_name . "商品已达上限" . PHP_EOL;
                            continue;
                        }
                        $sku_info['stock'] = $goods_info['max_buy'];
                        $price = (float)$sec_sku_info_list->seckill_price;
                    }elseif($is_presell){
                        $can_buy = $presell->getMeCanBuy($is_presell['presell_id'], $v['sku_id']);
                        $sku_info['stock'] = $can_buy;
                        $goods_info['max_buy'] = $can_buy;
                        $price = $is_presell['all_money'];
                    } else {
                        $cart_goods_list[$k]['promotion_type'] =0;
                        $price = $member_price;
                    }
//                    var_dump($is_seckill);
                    $update_data = array(
                        "goods_name" => $goods_info["goods_name"],
                        "sku_name" => $sku_info["sku_name"],
                        "goods_picture" => $v['goods_picture'], // $goods_info["picture"],
                        "price" => $price
                    );
                    // 更新数据
                    $cart->save($update_data, [
                        "cart_id" => $v["cart_id"]
                    ]);
                    $cart_goods_list[$k]["price"] = $price;
                    $cart_goods_list[$k]["oprice"] = $sku_info['price'];
                    $cart_goods_list[$k]["goods_name"] = $goods_info["goods_name"];
                    $cart_goods_list[$k]["sku_name"] = $sku_info["sku_name"];
                    $cart_goods_list[$k]["goods_picture"] = $v['goods_picture']; // $goods_info["picture"];
                    $cart_goods_list[$k]['stock'] = $sku_info['stock'] + $channel_stock;
                    $cart_goods_list[$k]['max_buy'] = $goods_info['max_buy'];
                    $cart_goods_list[$k]['state'] = $goods_info['state'];
                } else {
                    if (!empty($v['seckill_id']) && getAddons('seckill', $this->website_id, $this->instance_id)) {
                        //判断是否有秒杀的商品并且是否过期，若有直接取秒杀价
                        $condition_seckill['s.seckill_id'] = $v['seckill_id'];
                        $condition_seckill['nsg.sku_id'] = $v['sku_id'];
                        $sec_server = new SeckillServer();
                        $is_seckill = $sec_server->isSeckillGoods($condition_seckill);
                        if ($is_seckill) {
                            $cart_goods_list[$k]["price"] = $is_seckill['seckill_price'];
                            $remain_num = $is_seckill['remain_num'];
                            $limit_buy = $is_seckill['seckill_limit_buy'];
                            $cart_goods_list[$k]['stock'] = $remain_num;
                            $cart_goods_list[$k]['max_buy'] = $limit_buy;
                        } else {
                            $cart_goods_list[$k]["price"] = $sku_info["price"];
                            $cart_goods_list[$k]['stock'] = $sku_info['stock'];
                            $cart_goods_list[$k]['max_buy'] = $goods_info['max_buy'];
                        }
                    }elseif($is_presell){
                        $cart_goods_list[$k]["price"] = $is_presell["all_money"];
                        $presell_goods = new VslPresellGoodsModel();
                        $presell_sku_info = $presell_goods->getInfo(['presell_id' => $is_presell['presell_id'], 'sku_id' => $v['sku_id']]);
                        $cart_goods_list[$k]['stock'] = $presell_sku_info['presell_num'];
                        $cart_goods_list[$k]['max_buy'] = $presell_sku_info['max_buy'];
                    } else {
                        $cart_goods_list[$k]["price"] = $sku_info["price"];
                        $cart_goods_list[$k]['stock'] = $sku_info['stock'];
                        $cart_goods_list[$k]['max_buy'] = $goods_info['max_buy'];
                    }
                    $cart_goods_list[$k]["goods_name"] = $goods_info["goods_name"];
                    $cart_goods_list[$k]["sku_name"] = $sku_info["sku_name"];
                    $cart_goods_list[$k]["goods_picture"] = $v['goods_picture']; // $goods_info["picture"];
                }
                $cart_goods_list[$k]['min_buy'] = $goods_info['min_buy'];
                $cart_goods_list[$k]['point_exchange_type'] = $goods_info['point_exchange_type'];
                $cart_goods_list[$k]['point_exchange'] = $goods_info['point_exchange'];
                $cart_goods_list[$k]['sku_name_arr'] = array_filter(explode(' ', $sku_info["sku_name"]));
            }
            // 为购物车图片
            foreach ($cart_goods_list as $k => $v) {
                $picture = new AlbumPictureModel();
                $picture_info = $picture->getInfo(['pic_id' => $v['goods_picture']], 'pic_cover, pic_cover_small,pic_cover_micro,pic_cover_mid');
                $cart_goods_list[$k]['picture_info'] = $picture_info;
            }
            sort($cart_goods_list);
        }

        return $cart_goods_list;

    }

    /**
     * 添加购物车(non-PHPdoc)
     *
     * @see \data\api\IGoods::addCart()
     */
    public function addCart($uid, $shop_id, $goods_id, $goods_name, $sku_id, $sku_name, $price, $num, $picture, $bl_id, $seckill_id = 0)
    {
        if (getAddons('seckill', $this->website_id, $this->instance_id)) {
            //判断是否有seckill_id并且是否已经开始
            $sec_server = new SeckillServer();
            //判断当前商品是否为秒杀商品并且已经开始未结束
            $condition_seckill['s.seckill_id'] = $seckill_id;
            $condition_seckill['nsg.goods_id'] = $goods_id;
            $is_seckill = $sec_server->isSeckillGoods($condition_seckill);
        }
        $stock = $this->getSkuBySkuid($sku_id);//获取规格库存
        if ($is_seckill) {
            //获取限购数量
            $seckill_sku_list = $sec_server->getSeckillSkuInfo(['seckill_id' => $seckill_id, 'sku_id' => $sku_id]);
            $limit_buy = $seckill_sku_list->seckill_limit_buy;
            $seckill_id = $seckill_id;
            if ($limit_buy != 0) {
                if ($num > $limit_buy) {
                    $num = $limit_buy;
                }
            }
            //如果库存不足了
            $redis = $this->connectRedis();
            $redis_goods_sku_store_key = 'store_' . $seckill_id . '_' . $goods_id . '_' . $sku_id;
            $is_index = $redis->llen($redis_goods_sku_store_key);
            if (!$is_index) {
                return -2;
            }
        } else {
            $seckill_id = 0;
        }
        // 检测当前购物车中是否存在产品
        if ($uid > 0) {
            $cart = new VslCartModel();
            $condition = array(
                'buyer_id' => $uid,
                'sku_id' => $sku_id
            );
            //多用户shopid重新获取
            $shop_id = $this->getGoodsShopid($goods_id);
            if (getAddons('shop', $this->website_id) && $shop_id) {
                //获取店铺名称
                $shop_model = new VslShopModel();
                $shop_info = $shop_model::get(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
                $shop_name = $shop_info['shop_name'];
            } else {
                $shop_name = '自营店';
            }
            $count = $cart->where($condition)->count();

            if ($count == 0 || empty($count)) {
                $data = array(
                    'buyer_id' => $uid,
                    'shop_id' => $shop_id,
                    'shop_name' => $shop_name,
                    'goods_id' => $goods_id,
                    'goods_name' => $goods_name,
                    'sku_id' => $sku_id,
                    'sku_name' => $sku_name,
                    'price' => $price,
                    'num' => $num,
                    'goods_picture' => $picture,
                    'bl_id' => $bl_id,
                    'website_id' => $this->website_id,
                    'seckill_id' => $seckill_id
                );
                $cart->save($data);
                $retval = $cart->cart_id;
            } else {
                $cart = new VslCartModel();
                // 查询商品限购
                $goods = new VslGoodsModel();
                $get_num = $cart->getInfo($condition, 'cart_id,num');
                $max_buy = $goods->getInfo([
                    'goods_id' => $goods_id
                ], 'max_buy');

                $new_num = $num + $get_num['num'];
                if ($new_num > $stock) {
                    return -2;
                }
                if ($is_seckill) {
                    $price = $seckill_sku_list->seckill_price;
                    if ($limit_buy != 0) {
                        if ($new_num > $limit_buy) {
                            $new_num = $limit_buy;
                        }
                    }
                    $data['seckill_id'] = $seckill_id;
                    $data['num'] = $new_num;
                    $data['price'] = $price;
                } else {
                    if ($max_buy['max_buy'] != 0) {

                        if ($new_num > $max_buy['max_buy']) {

                            $new_num = $max_buy['max_buy'];
                        }
                    }
//                    $data['seckill_id'] = $seckill_id;
                    $data = array(
                        'num' => $new_num
                    );
                }
                $retval = $cart->save($data, $condition);
                if ($retval) {
                    $retval = $get_num['cart_id'];
                }
            }
        } else {
            $cart_array = cookie('cart_array' . $this->website_id);
            $shop_id = $this->getGoodsShopid($goods_id);
            if (getAddons('shop', $this->website_id) && $shop_id) {
                //获取店铺名称
                $shop_model = new VslShopModel();
                $shop_info = $shop_model::get(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
                $shop_name = $shop_info['shop_name'];
            } else {
                $shop_name = '自营店';
            }
            $data = array(
                'shop_id' => $shop_id,
                'shop_name' => $shop_name,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
                'num' => $num,
                'goods_picture' => $picture
            );
            if ($is_seckill) {
                $data['seckill_id'] = $seckill_id;
            }
            $cart_array = json_decode($cart_array, true);
            if (!empty($cart_array)) {
                $tmp_array = array();
                foreach ($cart_array as $k => $v) {
                    $tmp_array[] = $v['cart_id'];
                }
                $cart_id = max($tmp_array) + 1;
                $is_have = true;
                foreach ($cart_array as $k => $v) {
                    if ($v["goods_id"] == $goods_id && $v["sku_id"] == $sku_id) {
                        $is_have = false;
                        if (($data["num"] + $v["num"]) > $stock) {
                            return -2;
                        }
                        $cart_array[$k]["num"] = $data["num"] + $v["num"];
                    }
                }
                if ($is_have) {
                    $data["cart_id"] = $cart_id;
                    $cart_array[] = $data;
                }
            } else {
                $data["cart_id"] = 1;
                $cart_array[] = $data;
            }
            $cart_array_string = json_encode($cart_array);
            try {
                cookie('cart_array' . $this->website_id, $cart_array_string, 3600);
                return 1;
            } catch (\Exception $e) {
                recordErrorLog($e);
                return 0;
            }
            $retval = 1;
        }
        return $retval;
    }

    /**
     * 购物车数量修改(non-PHPdoc)
     *
     * @see \data\api\IGoods::cartAdjustNum()
     */
    public function cartAdjustNum($cart_id, $num)
    {
        if ($this->uid > 0) {
            $cart = new VslCartModel();
            $data = array(
                'num' => $num
            );
            $retval = $cart->save($data, [
                'cart_id' => $cart_id
            ]);
            return $retval;
        } else {
            $result = $this->updateCookieCartNum($cart_id, $num);
            return $result;
        }
    }

    /**
     * 购物车秒杀商品修改(non-PHPdoc)
     *
     * @see \data\api\IGoods::cartAdjustNum()
     */
    public function cartAdjustSec($cart_id, $seckill_id)
    {
        if ($this->uid > 0) {
            $cart = new VslCartModel();
            $data = array(
                'seckill_id' => $seckill_id
            );
            $retval = $cart->save($data, [
                'cart_id' => $cart_id
            ]);
            return $retval;
        }
    }

    /**
     * 门店购物车秒杀商品修改(non-PHPdoc)
     *
     * @see \data\api\IGoods::cartAdjustNum()
     */
    public function storeCartAdjustSec($cart_id, $seckill_id)
    {
        if ($this->uid > 0) {
            $cart = new VslStoreCartModel();
            $data = array(
                'seckill_id' => $seckill_id
            );
            $retval = $cart->save($data, [
                'cart_id' => $cart_id
            ]);
            return $retval;
        }
    }
    /**
     * 购物车项目删除(non-PHPdoc)
     *
     * @see \data\api\IGoods::cartDelete()
     */
    public function cartDelete($cart_id_array)
    {
        if ($this->uid > 0) {
            $cart = new VslCartModel();
            $retval = $cart->destroy($cart_id_array);
            return $retval;
        } else {
            $result = $this->deleteCookieCart($cart_id_array);
            return $result;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGroupGoodsList()
     */
    public function getGroupGoodsList($goods_group_id, $condition = '', $num = 0, $order = '')
    {
        $goods_list = array();
        $goods = new VslGoodsModel();
        $condition['state'] = 1;
        $list = $goods->getQuery($condition, '*', $order);
        foreach ($list as $k => $v) {
            $picture = new AlbumPictureModel();
            $picture_info = $picture->getInfo(['pic_id' => $v['picture']], 'pic_cover,pic_cover_mid,pic_cover_micro');
            $v['picture_info'] = $picture_info;
            $group_id_array = explode(',', $v['group_id_array']);
            if (in_array($goods_group_id, $group_id_array) || $goods_group_id == 0) {
                $goods_list[] = $v;
            }
        }
        foreach ($goods_list as $k => $v) {
            if (!empty($this->uid)) {
                $member = new Member();
                $goods_list[$k]['is_favorite'] = $member->getIsMemberFavorites($this->uid, $v['goods_id'], 'goods');
            } else {
                $goods_list[$k]['is_favorite'] = 0;
            }

            $goods_sku = new VslGoodsSkuModel();
            // 获取sku列表
            $sku_list = $goods_sku->where([
                'goods_id' => $v['goods_id']
            ])->select();
            $goods_list[$k]['sku_list'] = $sku_list;

            // 查询商品单品活动信息
            $goods_preference = new GoodsPreference();
            $goods_promotion_info = $goods_preference->getGoodsPromote($v['goods_id']);
            $goods_list[$k]['promotion_info'] = $goods_promotion_info;
        }
        if ($num == 0) {
            return $goods_list;
        } else {
            $count_list = count($goods_list);
            if ($count_list > $num) {
                return array_slice($goods_list, 0, $num);
            } else {
                return $goods_list;
            }
        }
    }

    /**
     * 获取限时折扣的商品
     *
     * @param number $page_index
     * @param number $page_size
     * @param unknown $condition
     * @param string $order
     */
    public function getDiscountGoodsList($page_index = 1, $page_size = 0, $condition = array(), $order = '')
    {
        $goods_discount = new GoodsDiscount();
        $goods_list = $goods_discount->getDiscountGoodsList($page_index, $page_size, $condition, $order);
        return $goods_list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsEvaluate()
     */
    public function getGoodsEvaluate($goods_id)
    {
        $goodsEvaluateModel = new VslGoodsEvaluateModel();
        $condition['goods_id'] = $goods_id;
        $field = 'order_id, orderno, order_goods_id, goods_id, goods_name, goods_price, goods_image, storeid, storename, content, addtime, image, explain_first, member_name, uid, is_anonymous, scores, again_content, again_addtime, again_image, again_explain';
        return $goodsEvaluateModel->getQuery($condition, $field, 'id ASC');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsEvaluateList()
     */
    public function getGoodsEvaluateList($page_index = 1, $page_size = 0, $condition = array(), $order = '', $field = '*')
    {
        $goodsEvaluateModel = new VslGoodsEvaluateModel();
        $evaluates = $goodsEvaluateModel->getViewList($page_index, $page_size, $condition, $order);
        foreach ($evaluates['data'] as &$evaluate) {
            $evaluate['nick_name'] = $evaluate['nick_name'] ?: ($evaluate['user_name'] ?: ($evaluate['user_name_default'] ?: '匿名'));
            $evaluate['user_img'] = $evaluate['user_headimg'] ?: $evaluate['head_img_default'];
        }
        return $evaluates;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsShopid()
     */
    public function getGoodsShopid($goods_id)
    {
        $goods_model = new VslGoodsModel();
        $goods_info = $goods_model->getInfo([
            'goods_id' => $goods_id
        ], 'shop_id');
        return $goods_info['shop_id'];
    }

    /**
     * (non-PHPdoc)
     * @evaluate_count总数量 @imgs_count带图的数量 @praise_count好评数量 @center_count中评数量 bad_count差评数量
     *
     * @see \data\api\IGoods::getGoodsEvaluateCount()
     */
    public function getGoodsEvaluateCount($goods_id)
    {
        $goods_evaluate = new VslGoodsEvaluateModel();
        $evaluate_count_list['evaluate_count'] = $goods_evaluate->where([
            'goods_id' => $goods_id,
            'is_show' => 1,
            'website_id' => $this->website_id
        ])->count();

        $evaluate_count_list['again_count'] = $goods_evaluate->where([
            'goods_id' => $goods_id,
            'is_show' => 1,
            'website_id' => $this->website_id,
            'again_content' => ['NEQ', '']
        ])->count();

        $evaluate_count_list['imgs_count'] = $goods_evaluate->where([
            'goods_id' => $goods_id,
            'is_show' => 1,
            'website_id' => $this->website_id
        ])
            ->where('image|again_image', 'NEQ', '')
            ->count();

        $evaluate_count_list['praise_count'] = $goods_evaluate->where([
            'goods_id' => $goods_id,
            'explain_type' => 5,
            'is_show' => 1,
            'website_id' => $this->website_id
        ])->count();
        $evaluate_count_list['center_count'] = $goods_evaluate->where([
            'goods_id' => $goods_id,
            'explain_type' => 3,
            'is_show' => 1,
            'website_id' => $this->website_id
        ])->count();
        $evaluate_count_list['bad_count'] = $goods_evaluate->where([
            'goods_id' => $goods_id,
            'explain_type' => 1,
            'is_show' => 1,
            'website_id' => $this->website_id
        ])->count();
        return $evaluate_count_list;
    }

    /**
     * (non-PHPdoc)
     * @point平均分数 @ratio展示百分比
     *
     */
    public function getGoodsEvaluateDetail($goods_id)
    {
        $goodsEvaluateData = ['point' => 0, 'ratio' => 0];
        $goods_evaluate = new VslGoodsEvaluateModel();
        $count = $goods_evaluate->where([
            'goods_id' => $goods_id,
            'is_show' => 1,
            'website_id' => $this->website_id
        ])->count();
        if (!$count) {
            return $goodsEvaluateData;
        }
        $goodsEvaluateData['point'] = number_format($goods_evaluate->getSum(['goods_id' => $goods_id, 'is_show' => 1, 'website_id' => $this->website_id], 'explain_type') / $count, 1, '.', '');
        $goodsEvaluateData['ratio'] = intval($goodsEvaluateData['point'] * 20);
        return $goodsEvaluateData;
    }

    /**
     * 获取商家或店铺的商品评论总数(non-PHPdoc)
     * @praise_count好评数量 @center_count中评数量 bad_count差评数量
     *
     * @see \data\api\IGoods::getGoodsEvaluateCount()
     */
    public function getEvaluateCount($shop_id = 0)
    {
        $goods_evaluate = new VslGoodsEvaluateModel();

        if ($shop_id > 0) {
            $evaluate_count_list['praise_count'] = $goods_evaluate->where([
                'shop_id' => $shop_id,
                'explain_type' => 5,
                'website_id' => $this->website_id
            ])->count();
            $evaluate_count_list['center_count'] = $goods_evaluate->where([
                'shop_id' => $shop_id,
                'explain_type' => 3,
                'website_id' => $this->website_id
            ])->count();
            $evaluate_count_list['bad_count'] = $goods_evaluate->where([
                'shop_id' => $shop_id,
                'explain_type' => 1,
                'website_id' => $this->website_id
            ])->count();

        } else {
            $evaluate_count_list['praise_count'] = $goods_evaluate->where([
                'explain_type' => 5,
                'website_id' => $this->website_id
            ])->count();
            $evaluate_count_list['center_count'] = $goods_evaluate->where([
                'explain_type' => 3,
                'website_id' => $this->website_id
            ])->count();
            $evaluate_count_list['bad_count'] = $goods_evaluate->where([
                'explain_type' => 1,
                'website_id' => $this->website_id
            ])->count();
        }


        return $evaluate_count_list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsRank()
     */
    public function getGoodsRank($condition)
    {
        $goods = new VslGoodsModel();
        $goods_list = $goods->where($condition)
            ->order(" real_sales desc ")
            ->limit(6)
            ->select();
        return $goods_list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsExpressTemplate()
     */
    public function getGoodsExpressTemplate($goods_id, $province_id, $city_id, $district_id)
    {
        $goods_express = new GoodsExpress();
        $retval = $goods_express->getGoodsExpressTemplate($goods_id, $province_id, $city_id, $district_id)['totalFee'];
        return $retval;
    }


    /**
     * (non-PHPdoc)
     * 获取所有商品品牌
     * @see \data\api\IGoods::getGoodsExpressTemplate()
     */
    public function getAllBrand($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $goods_brand = new VslGoodsBrandModel();
        $brand_list = $goods_brand->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $brand_list;
    }


    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsSpecList()
     */
    public function getGoodsSpecList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $goods_spec = new VslGoodsSpecModel();
        $goods_spec_value = new VslGoodsSpecValueModel();
        $goods_attr = new VslAttributeModel();
        $condition['website_id'] = $this->website_id;
        $goods_spec_list = $goods_spec->pageQuery($page_index, $page_size, $condition, $order, $field);
        if (!empty($goods_spec_list['data'])) {
            foreach ($goods_spec_list['data'] as $ks => $vs) {
                $attrValue = '';
                if ($vs['goods_attr_id']) {
                    $attrCheck = explode(',', $vs['goods_attr_id']);
                    foreach ($attrCheck as $ka => $va) {
                        $attrValue .= $goods_attr->getInfo(['attr_id' => $va], 'attr_name')['attr_name'] . ',';
                    }
                    unset($va);
                    $attrValue = substr($attrValue, 0, strlen($attrValue) - 1);
                }
                $goods_spec_value_name = '';
                $condition = ['spec_id' => $vs['spec_id']];
                if ($this->instance_id == 0) {
                    $condition['shop_id'] = $this->instance_id;
                } else {
                    $condition['shop_id'] = array(['=', 0], ['=', $this->instance_id], 'or');
                }
                $spec_value_list = $goods_spec_value->getQuery($condition, '*', '');
                foreach ($spec_value_list as $kv => $vv) {
                    $goods_spec_value_name = $goods_spec_value_name . ',' . $vv['spec_value_name'];
                }
                $goods_spec_list['data'][$ks]['spec_value_list'] = $spec_value_list;
                $goods_spec_value_name = $goods_spec_value_name == '' ? '' : substr($goods_spec_value_name, 1);
                $goods_spec_list['data'][$ks]['spec_value_name_list'] = $goods_spec_value_name;
                $goods_spec_list['data'][$ks]['attr_value_list'] = $attrValue;
            }
        }
        return $goods_spec_list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsSpecDetail()
     */
    public function getGoodsSpecDetail($spec_id)
    {
        $goods_spec = new VslGoodsSpecModel();
        $goods_spec_value = new VslGoodsSpecValueModel();
        $info = $goods_spec->getInfo([
            'spec_id' => $spec_id
        ], '*');
        $goods_spec_value_name = '';
        $goods_spec_value_name_platform = '';//c端把bc端规格值分离
        if (!empty($info)) {
            // 去除规格属性空值
            $goods_spec_value->destroy([
                'spec_id' => $info['spec_id'],
                'spec_value_name' => ''
            ]);
            $condition = ['spec_id' => $info['spec_id'], 'website_id' => $this->website_id];
            if ($this->instance_id == 0) {
                $condition['shop_id'] = $this->instance_id;
            } else {
                $condition['shop_id'] = array(['=', 0], ['=', $this->instance_id], 'or');
            }
            $spec_value_list = $goods_spec_value->getQuery($condition, '*', '');
            foreach ($spec_value_list as $kv => $vv) {
                if ($this->instance_id) {
                    if ($vv['shop_id']) {
                        $goods_spec_value_name = $goods_spec_value_name . ',' . $vv['spec_value_name'];
                    } else {
                        $goods_spec_value_name_platform = $goods_spec_value_name_platform . ',' . $vv['spec_value_name'];
                    }
                } else {
                    $goods_spec_value_name = $goods_spec_value_name . ',' . $vv['spec_value_name'];
                }

            }
        }
        $info['spec_value_name_list'] = substr($goods_spec_value_name, 1);
        $info['spec_value_name_list_platform'] = substr($goods_spec_value_name_platform, 1);
        $info['spec_value_list'] = $spec_value_list;
        return $info;
    }


    public function get_all_attr($attr)
    {

        $attribute = new VslAttributeModel();
        $attribute_info = $attribute->Query([
            "attr_id" => ['in', $attr]
        ], "*");

        return $attribute_info;
    }

    /*
     * 删除属性表里的规格ID
     */
    public function deleteSpecFromAttr($spec_id, $attr_id)
    {

        $attribute = new VslAttributeModel();
        $attribute_info = $attribute->getInfo([
            "attr_id" => $attr_id
        ], "*");
        $spec_array = explode(',', $attribute_info['spec_id_array']);
        foreach ($spec_array as $k => $v) {
            if ($spec_id == $v) {
                unset($spec_array[$k]);
            }
        }

        $new_spec = implode(',', $spec_array);
        return $attribute->save(['spec_id_array' => $new_spec], ['attr_id' => $attr_id]);
    }

    /*
     * 删除规格表里的品类id
     */
    public function deleteAttrFromSpec($spec_id, $attr_id)
    {

        $specModel = new VslGoodsSpecModel();
        $specInfo = $specModel->getInfo([
            "spec_id" => $spec_id
        ], "goods_attr_id");
        $attrArr = explode(',', $specInfo['goods_attr_id']);
        foreach ($attrArr as $k => $v) {
            if ($attr_id == $v) {
                unset($attrArr[$k]);
            }
        }
        $newAttrArr = implode(',', $attrArr);
        return $specModel->save(['goods_attr_id' => $newAttrArr], ['spec_id' => $spec_id]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::addGoodsSpec()
     */
    public function addGoodsSpecService($shop_id, $spec_name, $show_type, $is_visible, $sort, $spec_value_str, $attr_id, $is_screen)
    {
        $model = Request::instance()->module();
        $goods_spec = new VslGoodsSpecModel();
        $checkRepeat = $goods_spec->getInfo(['spec_name' => $spec_name, 'website_id' => $this->website_id, 'shop_id' => $shop_id]);
        if ($checkRepeat) {
            return -10031;
        }
        $goods_spec->startTrans();
        try {
            if ($model == 'platform') {
                $is_platform = 1;
            } else {
                $is_platform = 0;
            }
            $data = array(
                'shop_id' => $shop_id,
                'website_id' => $this->website_id,
                'spec_name' => $spec_name,
                'show_type' => $show_type,
                'is_visible' => $is_visible,
                'sort' => $sort,
                "is_screen" => $is_screen,
                'create_time' => time(),
                'goods_attr_id' => $attr_id,
                'is_platform' => $is_platform
            );
            $goods_spec->save($data);
            $spec_id = $goods_spec->spec_id;
            // 添加规格并修改上级分类关联规格
            if ($attr_id > 0) {
                $attribute = new VslAttributeModel();
                $attribute_info = $attribute->getInfo([
                    "attr_id" => $attr_id
                ], "*");
                if ($attribute_info["spec_id_array"] == '') {
                    $attribute->save([
                        "spec_id_array" => $spec_id
                    ], [
                        "attr_id" => $attr_id
                    ]);
                } else {
                    $attribute->save([
                        "spec_id_array" => $attribute_info["spec_id_array"] . "," . $spec_id
                    ], [
                        "attr_id" => $attr_id
                    ]);
                }
            }
            $spec_value_array = explode(',', $spec_value_str);
            $spec_value_array = array_filter($spec_value_array); // 去空
            $spec_value_array = array_unique($spec_value_array); // 去重复
            foreach ($spec_value_array as $k => $v) {
                $spec_value = array();
                if ($show_type == 2) {
                    $spec_value = explode(':', $v);
                    $this->addGoodsSpecValueService($spec_id, $spec_value[0], $spec_value[1], 1, 255);
                } else {
                    $this->addGoodsSpecValueService($spec_id, $v, '', 1, 255);
                }
            }
            $goods_spec->commit();
            $data['spec_id'] = $spec_id;
            hook("goodsSpecSaveSuccess", $data);
            return $spec_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $goods_spec->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::updateGoodsSpecService()
     */
    public function updateGoodsSpecService($spec_id, $shop_id, $spec_name, $show_type, $is_visible, $sort, $spec_value_str, $is_screen, $attr_id, $seleted_attr_str)
    {

        $goods_spec = new VslGoodsSpecModel();
        $checkRepeat = $goods_spec->getInfo(['spec_name' => $spec_name, 'website_id' => $this->website_id, 'shop_id' => $shop_id, 'spec_id' => ['<>', $spec_id]]);
        if ($checkRepeat) {
            return -10031;
        }
        $goods_spec->startTrans();
        try {
            $data = array(
                'spec_name' => $spec_name,
                'show_type' => $show_type,
                'is_visible' => $is_visible,
                'is_screen' => $is_screen,
                'goods_attr_id' => $attr_id,
                'sort' => $sort
            );
            $res = $goods_spec->save($data, [
                'spec_id' => $spec_id
            ]);
            if (!empty($spec_value_str)) {
                $specValueModel = new VslGoodsSpecValueModel();
                $specValueNotIn = $specValueModel->Query(['spec_value_name' => ['not in', $spec_value_str], 'spec_id' => $spec_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id], 'spec_value_id');
                if ($specValueNotIn) {//删除数据库中不属于前台提交的规格值
                    $specValueModel->delData(['spec_value_id' => ['in', implode(',', $specValueNotIn)]]);
                }
                $spec_value_array = explode(',', $spec_value_str);
                $spec_value_array = array_filter($spec_value_array); // 去空
                $spec_value_array = array_unique($spec_value_array); // 去重复
                foreach ($spec_value_array as $k => $v) {
                    $spec_value = array();
                    if ($show_type == 2) {
                        $spec_value = explode(':', $v);
                        $this->addGoodsSpecValueService($spec_id, $spec_value[0], $spec_value[1], 1, 255);
                    } elseif ($v) {
                        $this->addGoodsSpecValueService($spec_id, $v, '', 1, 255);
                    }
                }
            } else {
                $specValueModel = new VslGoodsSpecValueModel();
                $specValueModel->delData(['spec_id' => $spec_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
            }
            $data['spec_id'] = $spec_id;
            hook("goodsSpecSaveSuccess", $data);
            $new_attr_id = explode(',', $attr_id);//表单提交过来的ID
            $seleted_attr = explode(',', $seleted_attr_str); //提交前的ID
            $attribute = new VslAttributeModel();
            //循环传过来的属性ID，判断规格是否在此属性ID里面，无则增加，少则删除
            foreach ($new_attr_id as $k => $v) {
                $attribute_info = $attribute->Query([
                    "attr_id" => $v
                ], "*");
                //单个属性规格信息
                foreach ($attribute_info as $value) {
                    //拿到规格字符串
                    $spec_str = explode(',', $value['spec_id_array']);
                    //判断规格ID，没有则增加
                    if (!in_array($spec_id, $spec_str)) {
                        $spec_str = $value['spec_id_array'] . ',' . $spec_id;
                        $attribute->save(['spec_id_array' => $spec_str], ['attr_id' => $value['attr_id']]);
                    }
                }
                unset($value);
            }
            unset($v);
            //循环最初的属性ID，如果没有在提交后的ID里，则表示删除ID
            foreach ($seleted_attr as $v1) {
                if (!in_array($v1, $new_attr_id)) {
                    //删除
                    $this->deleteSpecFromAttr($spec_id, $v1);
                }
            }
            $goods_spec->commit();
            return $res;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $goods_spec->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::addGoodsSpecValue()
     */
    public function addGoodsSpecValueService($spec_id, $spec_value_name, $spec_value_data, $is_visible, $sort = 0)
    {
        $goods_spec_value = new VslGoodsSpecValueModel();
        $goodsSpecModel = new VslGoodsSpecModel();
        $checkSpecHas = $goodsSpecModel->getCount(['spec_id' => $spec_id]);//检查规格是否已经被删除了，删除了不能添加规格值
        if (!$checkSpecHas) {
            return -10032;
        }
        $data = array(
            'spec_id' => $spec_id,
            'spec_value_name' => $spec_value_name,
            'spec_value_data' => $spec_value_data,
            'is_visible' => $is_visible,
            'sort' => $sort,
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'create_time' => time()
        );
        $checkCondition = ['spec_value_name' => $spec_value_name, 'shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'spec_id' => $spec_id];
        if ($this->instance_id) {
            $checkCondition['shop_id'] = array(['=', 0], ['=', $this->instance_id], 'or');
        }
        $check = $goods_spec_value->getCount($checkCondition);
        if ($check) {
            return -10012;
        }
        $goods_spec_value->save($data);
        return $goods_spec_value->spec_value_id;
    }


    public function updateGoodsSpecValueService($data, $condition)
    {

        $goods_spec_value = new VslGoodsSpecValueModel();
        $retval = $goods_spec_value->save($data, $condition);
        return $retval;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::checkGoodsSpecIsUse()
     */
    public function checkGoodsSpecIsUse($spec_id)
    {
        // 1.查询所有当前规格下，所有的商品属性，组成字符串
        $goods_spec_value = new VslGoodsSpecValueModel();
        $goods_sku = new VslGoodsSkuModel();
        $goods_sku_delete = new VslGoodsSkuDeletedModel();
        $spec_value_list = $goods_spec_value->getQuery([
            'spec_id' => $spec_id
        ], '*', '');
        if (!empty($spec_value_list)) {
            $check_str = '';
            $res = 0;
            foreach ($spec_value_list as $k => $v) {
                $check_str = $spec_id . ':' . $v['spec_value_id'] . ';';
                $res += $goods_sku->where(" CONCAT(attr_value_items, ';') like '%" . $check_str . "%'")->count();
                $res += $goods_sku_delete->where(" CONCAT(attr_value_items, ';') like '%" . $check_str . "%'")->count();
                if ($res > 0) {
                    return true;
                    break;
                }
            }
            if ($res == 0) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::checkGoodsSpecValueIsUse()
     */
    public function checkGoodsSpecValueIsUse($spec_id, $spec_value_id)
    {
        $check_str = $spec_id . ':' . $spec_value_id . ';';
        $goods_sku = new VslGoodsSkuModel();
        $goods_sku_delete = new VslGoodsSkuDeletedModel();
        // 商品sku
        $res = $goods_sku->where(" CONCAT(attr_value_items, ';') like '%" . $check_str . "%'")->count();
        // 商品回收站sku
        $res_delete = $goods_sku_delete->where(" CONCAT(attr_value_items, ';') like '%" . $check_str . "%'")->count();
        if (($res + $res_delete) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function addGoodsEvaluateReply($id, $replyContent, $replyType)
    {
        $goodsEvaluate = new VslGoodsEvaluateModel();
        if ($replyType == 1) {
            return $goodsEvaluate->save([
                'explain_first' => $replyContent,
                'explain_time' => time()
            ], [
                'id' => $id
            ]);
        } elseif ($replyType == 2) {
            return $goodsEvaluate->save([
                'again_explain' => $replyContent,
                'again_explain_time' => time()
            ], [
                'id' => $id
            ]);
        }
    }

    public function setEvaluateShowStatu($id)
    {
        $goodsEvaluate = new VslGoodsEvaluateModel();
        $showStatu = $goodsEvaluate->getInfo([
            'id' => $id
        ], 'is_show');
        if ($showStatu['is_show'] == 1) {
            return $goodsEvaluate->save([
                'is_show' => 0
            ], [
                'id' => $id
            ]);
        } elseif ($showStatu['is_show'] == 0) {
            return $goodsEvaluate->save([
                'is_show' => 1
            ], [
                'id' => $id
            ]);
        }
    }

    public function deleteEvaluate($id)
    {
        $goodsEvaluate = new VslGoodsEvaluateModel();
        return $goodsEvaluate->destroy($id);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::deleteGoodsSpecValue()
     */
    public function deleteGoodsSpecValue($spec_id, $spec_value_id)
    {
        // 检测是否使用
        //$res = $this->checkGoodsSpecValueIsUse($spec_id, $spec_value_id);
        // 检测规格属性数量
        $result = $this->getGoodsSpecValueCount([
            'spec_id' => $spec_id
        ]);

        if ($result == 1) {
            return -2;
        } else {
            $goods_spec_value = new VslGoodsSpecValueModel();
            return $goods_spec_value->destroy($spec_value_id);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsSpecValueCount()
     */
    public function getGoodsSpecValueCount($condition)
    {
        $spec_value = new VslGoodsSpecValueModel();
        $count = $spec_value->where($condition)->count();
        return $count;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::deleteGoodsSpec()
     */
    public function deleteGoodsSpec($spec_id)
    {
        $goods_spec = new VslGoodsSpecModel();
        $goods_spec_value = new VslGoodsSpecValueModel();
        $goods_spec->startTrans();
        try {
            $spec_id_array = explode(',', $spec_id);
            foreach ($spec_id_array as $k => $v) {
                $goods_spec->destroy($v);
                $goods_spec_value->destroy([
                    'spec_id' => $v
                ]);
            }

            $goods_spec->commit();
            hook("goodsSpecDeleteSuccess", $spec_id);
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $goods_spec->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     * 删除商品属性
     * @see \data\api\IGoods::deleteGoodsSpec()
     */
    public function deleteGoodsAttr($attr_value_id)
    {
        $attrValueModel = new VslAttributeValueModel();
        $res = $attrValueModel->delData(['attr_value_id' => $attr_value_id]);
        return $res;
    }

    /*
     * 修改商品规格是否启动
     * **/
    public function updateGoodsSpecShow($spec_id, $is_visible)
    {
        $goods_spec = new VslGoodsSpecModel();
        $res['is_visible'] = $is_visible;
        $bool = $goods_spec->where(['spec_id' => $spec_id])->update($res);
        return $bool;
    }

    /*
     * 修改商品品类是否启动
     * **/
    public function updateGoodsAttrShow($attr_id, $is_use)
    {
        $goods_spec = new VslAttributeModel();
        $res['is_use'] = $is_use;
        $bool = $goods_spec->where(['attr_id' => $attr_id])->update($res);
        return $bool;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::modifyGoodsSpecField()
     */
    public function modifyGoodsSpecField($spec_id, $field_name, $field_value)
    {
        $goods_spec = new VslGoodsSpecModel();
        return $goods_spec->save([
            "$field_name" => $field_value
        ], [
            'spec_id' => $spec_id
        ]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::modifyGoodsSpecValueField()
     */
    public function modifyGoodsSpecValueField($spec_value_id, $field_name, $field_value)
    {
        $goods_spec_value = new VslGoodsSpecValueModel();
        return $goods_spec_value->save([
            "$field_name" => $field_value
        ], [
            'spec_value_id' => $spec_value_id
        ]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::updateAttributeIsUse()
     */
    public function updateAttributeIsUse($attr_id, $is_use)
    {
        $goods_spec = new VslAttributeModel();
        return $goods_spec->save([
            'is_use' => $is_use
        ], [
            'attr_id' => $attr_id
        ]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsAttributeServiceList()
     */
    public function getAttributeServiceList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $attribute = new VslAttributeModel();
        $attribute_value = new VslAttributeValueModel();
        $categoryModel = new VslGoodsCategoryModel();
        $caegoryServer = new GoodsCategory();
        $list = $attribute->pageQuery($page_index, $page_size, $condition, $order, $field);
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $new_array = $attribute_value->getQuery([
                    'attr_id' => $v['attr_id']
                ], 'attr_value_name', '');
                $value_str = '';
                foreach ($new_array as $kn => $vn) {
                    $value_str = $value_str . ',' . $vn['attr_value_name'];
                }
                $value_str = substr($value_str, 1);
                $list['data'][$k]['value_str'] = $value_str;


                $value_stra = '';
                if (!empty($list['data'][$k]['spec_id_array'])) {
                    $goods_spec_model = new VslGoodsSpecModel();
                    $spec_value = $goods_spec_model::all(['spec_id' => ['IN', $list['data'][$k]['spec_id_array']]]);
                    foreach ($spec_value as $a => $n) {
                        $value_stra = $value_stra . ',' . $n['spec_name'];
                    }
                    $value_stra = mb_substr($value_stra, 1);
                    $list['data'][$k]['spec_value'] = $value_stra;
                }
                $categoryList = $categoryModel->getQuery(['attr_id' => $v['attr_id'], 'website_id' => $this->website_id], 'category_id', 'sort asc');
                if ($categoryList) {
                    foreach ($categoryList as $ck => $cv) {
                        $categoryList[$ck]['category_names'] = $caegoryServer->getCategoryNameLine($cv['category_id']);
                    }
                }
                $list['data'][$k]['categorys'] = $categoryList;
            }
        }
        return $list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::addGoodsAttributeService()
     */
    public function addAttributeService($attr_name, $is_use, $spec_id_array, $sort, $value_string, $brand_id_array = '', $cate_obj_arr = [])
    {
        $attribute = new VslAttributeModel();
        $attribute->startTrans();
        try {
            $data = array(
                "attr_name" => $attr_name,
                "is_use" => $is_use,
                "website_id" => $this->website_id,
                "spec_id_array" => $spec_id_array ?: '',
                "sort" => $sort,
                "create_time" => time(),
                "brand_id_array" => $brand_id_array ?: ''
            );
            $attribute->save($data);
            $attr_id = $attribute->attr_id;
            $checkArray = [];
            if (!empty($value_string)) {
                $value_array = explode(';', $value_string);
                foreach ($value_array as $k => $v) {
                    $new_array = array();
                    $new_array = explode('|', $v);
                    if (in_array($new_array[0], $checkArray)) {
                        return -10017;
                    }
                    $checkArray[] = $new_array[0];
                    $this->addAttributeValueService($attr_id, $new_array[0], $new_array[1], $new_array[2], $new_array[3], $new_array[4]);
                }
            }
            if ($cate_obj_arr) {
                foreach ($cate_obj_arr as $val) {
                    $goodsCategory = new VslGoodsCategoryModel();
                    $goodsCategory->save(['attr_id' => $attr_id, 'attr_name' => $attr_name], ['category_id' => $val]);
                }
                unset($val);
            }
            $attribute->commit();
            $data['attr_id'] = $attr_id;
            hook("goodsAttributeSaveSuccess", $data);
            return $attr_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $attribute->rollback();
            return $e->getMessage();
        }
    }

    //获取关联规格
    public function get_all_spec()
    {

        return Db::query("select * from `vsl_attribute` where `website_id` = " . $this->website_id);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::updateAttributeService()
     */
    public function updateAttributeService($attr_id, $attr_name, $is_use, $spec_id_array, $sort, $value_string)
    {
        $attribute = new VslAttributeModel();
        $attribute->startTrans();
        try {
            //删除旧的数据
            Db::query("delete from `vsl_attribute_value` where attr_id = " . $attr_id);
            $data = array(
                "attr_name" => $attr_name,
                "is_use" => $is_use,
                "spec_id_array" => $spec_id_array,
                "sort" => $sort,
                "modify_time" => time()
            );
            $res = $attribute->save($data, [
                'attr_id' => $attr_id
            ]);
            if (!empty($value_string)) {
                $value_array = explode(';', $value_string);
                foreach ($value_array as $k => $v) {
                    $new_array = array();
                    $new_array = explode('|', $v);
                    $this->addAttributeValueService($attr_id, $new_array[0], $new_array[1], $new_array[2], $new_array[3], $new_array[4]);
                }
            }
            $attribute->commit();
            $data['attr_id'] = $attr_id;
            hook("goodsAttributeSaveSuccess", $data);
            return $res;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $attribute->rollback();
            return $e->getMessage();
        }
    }


    //B端修改属性
    public function updateAttributeServicePlatfom($attr_id, $attr_name, $is_use, $spec_id_array, $sort, $value_string, $brand_id_array = '', $cate_obj_arr = [])
    {
        $attribute = new VslAttributeModel();
        $attribute->startTrans();
        try {
            $data = array(
                "attr_name" => $attr_name,
                "is_use" => $is_use,
                "spec_id_array" => $spec_id_array,
                "sort" => $sort,
                "modify_time" => time(),
                "brand_id_array" => $brand_id_array
            );
            $res = $attribute->save($data, [
                'attr_id' => $attr_id
            ]);
            //删除没有提交过来的ID
            $attribute_value = new VslAttributeValueModel();
            $condition['attr_id'] = $attr_id;
            $value_list = $attribute_value->getQuery($condition, 'attr_value_id', '');
            //变成一维数组
            $attr_value_list = array();
            foreach ($value_list as $k => $v) {
                $attr_value_list[] = $v['attr_value_id'];
            }
            $goods_attr = array();
            $checkArray = [];

            if (!empty($value_string)) {
                $value_array = explode(';', $value_string);
                foreach ($value_array as $k => $v) {
                    $new_array = array();
                    $new_array = explode('|', $v);
                    $new_array[5] = (int)$new_array[5];
                    if (in_array($new_array[0], $checkArray)) {
                        return -10017;
                    }
                    $checkArray[] = $new_array[0];
                    if (!empty($new_array[5])) {
                        $goods_attr[] = $new_array[5];
                        $this->addAttributeValueService($attr_id, $new_array[0], $new_array[1], $new_array[2], $new_array[3], $new_array[4], $new_array[5]);
                    } else {
                        $this->addAttributeValueService($attr_id, $new_array[0], $new_array[1], $new_array[2], $new_array[3], $new_array[4]);
                    }

                }
            }

            //循环数据库的attr_value和传过来的，多出来的则删除
            foreach ($attr_value_list as $k => $v) {
                if (!in_array($v, $goods_attr)) {
                    $condition['attr_value_id'] = $v;
                    $attribute_value->delData($condition);
                }
            }
            if (!in_array($new_array[5], $attr_value_list) && !empty($new_array[5])) {
                $attribute_value->delData(['attr_value_id' => $new_array[5]]);
            }
            $goodsCategory = new VslGoodsCategoryModel();
            $goodsCategory->save(['attr_id' => 0, 'attr_name' => ''], ['attr_id' => $attr_id]);
            if ($cate_obj_arr) {
                foreach ($cate_obj_arr as $val) {
                    $goodsCategory = new VslGoodsCategoryModel();
                    $goodsCategory->isUpdate(true)->save(['attr_id' => $attr_id, 'attr_name' => $attr_name], ['category_id' => $val]);
                }
                unset($val);
            }
            $attribute->commit();
            $data['attr_id'] = $attr_id;
            hook("goodsAttributeSaveSuccess", $data);
            return $res;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $attribute->rollback();
            return $e->getMessage();
        }
    }


    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::addAttributeValueService()
     */
    public function addAttributeValueService($attr_id, $attr_value_name, $type, $sort, $is_search, $value, $attr_value_id = '')
    {

        $attribute_value = new VslAttributeValueModel();
        $data = array(
            'attr_id' => $attr_id,
            'attr_value_name' => $attr_value_name,
            'type' => $type,
            'sort' => $sort,
            'is_search' => $is_search,
            'value' => $value,
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
        );
        if (empty($attr_value_id)) {
            $attribute_value->save($data);
            return $attribute_value->attr_value_id;
        } else {
            $condition['attr_value_id'] = $attr_value_id;
            $attribute_value->save($data, $condition);
            return $attr_value_id;

        }
    }

    /*
     * 添加属性值
     */
    public function addAttributeValueName($attr_value_id, $attr_value_name)
    {
        $attributeValue = new VslAttributeValueModel();
        $checkAttr = $attributeValue->getInfo(['attr_value_id' => $attr_value_id], 'attr_value_id,value');
        if (!$checkAttr) {
            return 0;
        }
        $attrValue = $checkAttr['value'];
        if (!$attrValue) {
            $attrValue = $attr_value_name;//没有属性
        } else {
            $arrValue = explode(',', $attrValue);
            if (in_array($attr_value_name, $arrValue)) {
                return QUESTION_NAME_REPEAT;
            }
            array_push($arrValue, $attr_value_name);//已有属性
            $attrValue = implode(',', $arrValue);
        }
        $res = $attributeValue->isUpdate(true)->save(['value' => $attrValue], ['attr_value_id' => $checkAttr['attr_value_id']]);
        return $res;
    }


    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getAttributeServiceDetail()
     */
    public function getAttributeServiceDetail($attr_id, $condition = [])
    {
        $attribute = new VslAttributeModel();
        $info = $attribute->get($attr_id);
        $array = Array();
        if (!empty($info)) {
            $condition['attr_id'] = $attr_id;
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = array(['=', 0], ['=', $this->instance_id], 'or');
            $array = $this->getAttributeValueServiceList(1, 0, $condition, 'sort');
            $info['value_list'] = $array;
        } else {
            $condition['attr_id'] = $attr_id;
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = array(['=', 0], ['=', $this->instance_id], 'or');
            $array = $this->getAttributeValueServiceList(1, 0, $condition, 'sort');
            $info['value_list'] = $array;
        }
        return $info;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getAttributeValueServiceList()
     */
    public function getAttributeValueServiceList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $attribute_value = new VslAttributeValueModel();
        return $attribute_value->pageQuery($page_index, $page_size, $condition, $order, $field);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::deleteAttributeService()
     */
    public function deleteAttributeService($attr_id)
    {
        $attribute = new VslAttributeModel();
        $attribute_value = new VslAttributeValueModel();
        $res = $attribute->destroy($attr_id);
        $attribute_value->destroy([
            'attr_id' => $attr_id
        ]);
        hook("goodsAttributeDeleteSuccess", [
            'attr_id' => $attr_id
        ]);
        return $res;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::deleteAttributeValueService()
     */
    public function deleteAttributeValueService($attr_id, $attr_value_id)
    {
        $attribute_value = new VslAttributeValueModel();
        // 检测类型属性数量
        $result = $this->getGoodsAttrValueCount([
            'attr_id' => $attr_id
        ]);
        if ($result == 1) {
            return -2;
        } else {
            return $attribute_value->destroy($attr_value_id);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsAttrValueCount()
     */
    public function getGoodsAttrValueCount($condition)
    {
        $attr_value = new VslAttributeValueModel();
        $count = $attr_value->where($condition)->count();
        return $count;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::modifyAttributeValueService()
     */
    public function modifyAttributeValueService($attr_value_id, $field_name, $field_value)
    {
        $attribute_value = new VslAttributeValueModel();
        return $attribute_value->save([
            "$field_name" => $field_value
        ], [
            'attr_value_id' => $attr_value_id
        ]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::modifyAttributeFieldService()
     */
    public function modifyAttributeFieldService($attr_id, $field_name, $field_value)
    {
        $attribute = new VslAttributeModel();
        return $attribute->save([
            "$field_name" => $field_value
        ], [
            'attr_id' => $attr_id
        ]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::checkGoodsSpecValueNameIsUse()
     */
    public function checkGoodsSpecValueNameIsUse($spec_id, $value_name)
    {
        $goods_spec_value = new VslGoodsSpecValueModel();
        $num = $goods_spec_value->where([
            'spec_id' => $spec_id,
            'spec_value' => $value_name
        ])->count();
        return $num > 0 ? true : false;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getAttributeInfo()
     */
    public function getAttributeInfo($condition)
    {
        // TODO Auto-generated method stub
        $attribute = new VslAttributeModel();
        $info = $attribute->getInfo($condition, "*");
        return $info;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsSpecQuery()
     */
    public function getGoodsSpecQuery($condition)
    {
        // TODO Auto-generated method stub
        $goods_spec = new VslGoodsSpecModel();
        $album = new Album();
        if ($this->instance_id == 0) {
            $condition['shop_id'] = $this->instance_id;
        } else {
            $condition['shop_id'] = array(['=', 0], ['=', $this->instance_id], 'or');
        }
        $goods_spec_query = $goods_spec->getQuery($condition, "*", 'sort');
        $goods_spec_value = new VslGoodsSpecValueModel();
        foreach ($goods_spec_query as $k => $v) {
            $condition_spec = ["spec_id" => $v["spec_id"], "website_id" => $this->website_id];
            if ($this->instance_id == 0) {
                $condition_spec['shop_id'] = $this->instance_id;
            } else {
                $condition_spec['shop_id'] = array(['=', 0], ['=', $this->instance_id], 'or');
            }
            $goods_spec_value_query = $goods_spec_value->getQuery($condition_spec, "*", 'sort desc');
            foreach ($goods_spec_value_query as $key => $val) {
                $goods_spec_value_query[$key]['pic'] = '';
                if ($v['show_type'] == '3' && $val['spec_value_data']) {
                    $pic = $album->getAlubmPictureDetail([
                        "pic_id" => $val["spec_value_data"]
                    ]);
                    $goods_spec_value_query[$key]['pic'] = $pic['pic_cover_micro'];
                }

            }
            unset($val);
            $goods_spec_query[$k]["values"] = $goods_spec_value_query;
        }
        unset($v);
        return $goods_spec_query;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsAttrSpecQuery()
     */
    public function getGoodsAttrSpecQuery($condition)
    {
        $brand = new VslGoodsBrandModel();
        // TODO Auto-generated method stub
        if ($condition["attr_id"] == 0) {
            $spec_list = $this->getGoodsSpecQuery(['is_visible' => 1, 'website_id' => $this->website_id, 'goods_attr_id' => [['=', 0], ['=', ' '], 'or'], 'shop_id' => $this->instance_id]);
        } else {
            $goods_attribute = $this->getAttributeInfo($condition);
            $condition_spec["spec_id"] = array(
                "in",
                $goods_attribute['spec_id_array']
            );
            $condition_spec["is_visible"] = 1;
            $condition_spec["website_id"] = $this->website_id;
            $spec_list = $this->getGoodsSpecQuery($condition_spec); // 商品规格
            //获取品牌
//            $brand_condition['brand_id'] = array("in", $goods_attribute['brand_id_array']);
//            $brand_condition['brand_recommend'] = 1;
//            $brand_list = $brand->getQuery($brand_condition,'','');

        }
        $brand_list = $brand->getQuery(['website_id' => $this->website_id, 'brand_id' => ['in', $goods_attribute['brand_id_array']]], '', '');
        $attribute_detail = $this->getAttributeServiceDetail($condition["attr_id"], [
            'is_search' => 1
        ]);
        $attribute_list = $attribute_detail['value_list']['data'];

        foreach ($attribute_list as $k => $v) {
            $value_items = explode(",", $v['value']);
            $attribute_list[$k]['value_items'] = $value_items;
        }
        $list["spec_list"] = $spec_list; // 商品规格集合
        $list["attribute_list"] = $attribute_list; // 商品属性集合
        $list['brand_list'] = $brand_list ?: [];
        return $list;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsAttributeQuery()
     */
    public function getGoodsAttributeQuery($condition)
    {
        // TODO Auto-generated method stub
        $goods_attribute = new VslGoodsAttributeModel();
        $query = $goods_attribute->getQuery($condition, "*", "");
        return $query;
    }

    /**
     * 回收商品的分页查询
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsDeletedList()
     */
    public function getGoodsDeletedList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        // 针对商品分类
        if (!empty($condition['ng.category_id'])) {
            $goods_category = new GoodsCategory();
            $category_list = $goods_category->getCategoryTreeList($condition['ng.category_id']);
            $condition['ng.category_id'] = array(
                'in',
                $category_list
            );
        }
        $goods_view = new VslGoodsDeletedViewModel();

        $list = $goods_view->getGoodsViewList($page_index, $page_size, $condition, $order);
        if (!empty($list['data'])) {
            // 用户针对商品的收藏
            foreach ($list['data'] as $k => $v) {
                if (!empty($this->uid)) {
                    $member = new Member();
                    $list['data'][$k]['is_favorite'] = $member->getIsMemberFavorites($this->uid, $v['goods_id'], 'goods');
                } else {
                    $list['data'][$k]['is_favorite'] = 0;
                }
                // 查询商品单品活动信息
                $goods_preference = new GoodsPreference();
                $goods_promotion_info = $goods_preference->getGoodsPromote($v['goods_id']);
                $list["data"][$k]['promotion_info'] = $goods_promotion_info;
            }
        }
        return $list;
    }


    /**
     * 商品恢复
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::regainGoodsDeleted()
     */
    public function regainGoodsDeleted($goods_ids)
    {
        $goods_array = explode(",", $goods_ids);
        $this->goods->startTrans();
        try {
            foreach ($goods_array as $goods_id) {
                $goods_delete_model = new VslGoodsDeletedModel();
                $goods_delete_obj = $goods_delete_model->getInfo([
                    "goods_id" => $goods_id
                ]);
                $goods_delete_obj = json_decode(json_encode($goods_delete_obj), true);
                $goods_model = new VslGoodsModel();
                $goods_model->save($goods_delete_obj);
                $goods_delete_model->where("goods_id=$goods_id")->delete();
                // sku 恢复
                $goods_sku_delete_model = new VslGoodsSkuDeletedModel();
                $sku_delete_list = $goods_sku_delete_model->getQuery([
                    "goods_id" => $goods_id
                ], "*", "");
                foreach ($sku_delete_list as $sku_obj) {
                    $sku_obj = json_decode(json_encode($sku_obj), true);
                    $sku_model = new VslGoodsSkuModel();
                    $sku_model->save($sku_obj);
                }
                $goods_sku_delete_model->where("goods_id=$goods_id")->delete();
                // 属性恢复
                $goods_attribute_delete_model = new VslGoodsAttributeDeletedModel();
                $attribute_delete_list = $goods_attribute_delete_model->getQuery([
                    "goods_id" => $goods_id
                ], "*", "");
                foreach ($attribute_delete_list as $attribute_delete_obj) {
                    $attribute_delete_obj = json_decode(json_encode($attribute_delete_obj), true);
                    $attribute_model = new VslGoodsAttributeModel();
                    $attribute_model->save($attribute_delete_obj);
                }
                $goods_attribute_delete_model->where("goods_id=$goods_id")->delete();
                // sku图片恢复
                $goods_sku_picture_delete = new VslGoodsSkuPictureDeleteModel();
                $goods_sku_picture_delete_list = $goods_sku_picture_delete->getQuery([
                    'goods_id' => $goods_id
                ], "*", "");
                foreach ($goods_sku_picture_delete_list as $goods_sku_picture_list_delete_obj) {
                    $goods_sku_picture = new VslGoodsSkuPictureModel();
                    $goods_sku_picture_list_delete_obj = json_decode(json_encode($goods_sku_picture_list_delete_obj), true);
                    $goods_sku_picture->save($goods_sku_picture_list_delete_obj);
                }
                $goods_sku_picture_delete->where("goods_id=$goods_id")->delete();
            }
            $this->goods->commit();
            return SUCCESS;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->goods->rollback();
            return UPDATA_FAIL;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::copyGoodsInfo()
     */
    public function copyGoodsInfo($goods_id)
    {
        $goods_detail = $this->getGoodsDetail($goods_id);
        $goods_attribute = $this->getGoodsAttribute($goods_id);
        $goods_attribute_arr = array();
        foreach ($goods_detail['goods_attribute_list'] as $item) {
            $item_arr = array(
                'attr_value_id' => $item['attr_value_id'],
                'attr_value' => $item['attr_value'],
                'attr_value_name' => $item['attr_value_name'],
                'sort' => $item['sort']
            );
            array_push($goods_attribute_arr, $item_arr);
        }
        $skuArray = '';
        foreach ($goods_detail['sku_list'] as $item) {
            if (!empty($item['attr_value_items'])) {
                $skuArray .= $item['attr_value_items'] . '¦' . $item['price'] . "¦" . $item['market_price'] . "¦" . $item['cost_price'] . "¦" . $item['stock'] . "¦" . $item['code'] . '§';
            }
        }
        $skuArray = rtrim($skuArray, '§');
        // sku规格图片
        $goods_sku_picture = new VslGoodsSkuPictureModel();
        $goods_sku_picture_query = $goods_sku_picture->getQuery([
            "goods_id" => $goods_id
        ], "goods_id, shop_id, spec_id, spec_value_id, sku_img_array", '');
        $goods_sku_picture_query_array = array();
        foreach ($goods_sku_picture_query as $k => $v) {
            $goods_sku_picture_query_array[$k]["spec_id"] = $v["spec_id"];
            $goods_sku_picture_query_array[$k]["spec_value_id"] = $v["spec_value_id"];
            $goods_sku_picture_query_array[$k]["img_ids"] = $v["sku_img_array"];
        }
        if (empty($goods_sku_picture_query_array)) {
            $goods_sku_picture_str = "";
        } else {
            $goods_sku_picture_str = json_encode($goods_sku_picture_query_array);
        }
        $res = $this->addOrEditGoods(0, $goods_detail['goods_name'] . '-副本', $goods_detail['shop_id'], $goods_detail['category_id'], $goods_detail['category_id_1'], $goods_detail['category_id_2'], $goods_detail['category_id_3'], $goods_detail['supplier_id'], $goods_detail['brand_id'], $goods_detail['group_id_array'], $goods_detail['goods_type'], $goods_detail['market_price'], $goods_detail['price'], $goods_detail['cost_price'], $goods_detail['point_exchange_type'], $goods_detail['point_exchange'], $goods_detail['give_point'], $goods_detail['is_member_discount'], $goods_detail['shipping_fee'], $goods_detail['shipping_fee_id'], $goods_detail['stock'], $goods_detail['max_buy'], $goods_detail['min_buy'], $goods_detail['min_stock_alarm'], $goods_detail['clicks'], $goods_detail['sales'], $goods_detail['collects'], $goods_detail['star'], $goods_detail['evaluates'], $goods_detail['shares'], $goods_detail['province_id'], $goods_detail['city_id'], $goods_detail['picture'], $goods_detail['keywords'], $goods_detail['introduction'], $goods_detail['description'], '', $goods_detail['code'], $goods_detail['is_stock_visible'], $goods_detail['is_hot'], $goods_detail['is_recommend'], $goods_detail['is_new'], $goods_detail['sort'], $goods_detail['img_id_array'], $skuArray, 0, $goods_detail['sku_img_array'], $goods_detail['goods_attribute_id'], json_encode($goods_attribute_arr), $goods_detail['goods_spec_format'], $goods_detail['goods_weight'], $goods_detail['goods_volume'], $goods_detail['shipping_fee_type'], $goods_detail['extend_category_id'], $goods_sku_picture_str);
        return $res;
    }

    /**
     * 删除回收站商品
     *
     * @param unknown $goods_id
     * @return string
     */
    public function deleteRecycleGoods($goods_id)
    {
        $goods_id = explode(',', $goods_id);
        $goods_list = [];
        if (count($goods_id) > 1) {
            $id = '';
            foreach ($goods_id as $k => $v) {
                //先判断是否是活动商品
                $goods = new VslGoodsModel();
                $goods_info = $goods->getInfo(['goods_id' => $v]);
                $goods_list[] = $goods_info;
                if ($goods_info['promotion_type'] != 0) {
                    return DELETE_FAIL;
                }
                $id .= $v . ',';
            }
            $id = substr($id, 0, -1);
        } else {
            $id = $goods_id[0];
            //先判断是否是活动商品
            $goods = new VslGoodsModel();
            $goods_info = $goods->getInfo(['goods_id' => $id]);
            $goods_list[] = $goods_info;
            if ($goods_info['promotion_type'] != 0) {
                return DELETE_FAIL;
            }
        }


        $goods_delete = new VslGoodsDeletedModel();
        $goods_delete->startTrans();
        try {
            $res = $goods_delete->where("goods_id in ($id) and shop_id=$this->instance_id ")->delete();
            if ($res > 0) {
                $goods_id_array = $goods_id;
                $goods_sku_model = new VslGoodsSkuDeletedModel();
                $goods_attribute_model = new VslGoodsAttributeDeletedModel();
                $goods_sku_picture_delete = new VslGoodsSkuPictureDeleteModel();
                foreach ($goods_id_array as $k => $v) {
                    // 删除商品sku
                    $goods_sku_model->where("goods_id = $v")->delete();
                    // 删除商品属性
                    $goods_attribute_model->where("goods_id = $v")->delete();
                    // 删除
                    $goods_sku_picture_delete->where("goods_id = $v")->delete();
                }
                //删除微信卡券
                foreach ($goods_list as $key => $value) {
                    if (!empty($value) && !empty($value['wx_card_id']) && $value['is_wxcard'] == 1) {
                        $weixin_card = new WeixinCard();
                        $weixin_card->cardDelete($value['wx_card_id']);
                    }
                }
            }
            $goods_delete->commit();
            if ($res > 0) {
                return SUCCESS;
            } else {
                return DELETE_FAIL;
            }
        } catch (\Exception $e) {
            recordErrorLog($e);
            $goods_delete->rollback();
            return DELETE_FAIL;
        }
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::deleteCookieCart()删除cookie购物车
     */
    private function deleteCookieCart($cart_id_array)
    {
        // TODO Auto-generated method stub
        // 获取删除条件拼装
        $cart_id_array = trim($cart_id_array);
        if (empty($cart_id_array) && $cart_id_array != 0) {
            return 0;
        }
        // 获取购物车
        $cart_goods_list = cookie('cart_array' . $this->website_id);
        if (empty($cart_goods_list)) {
            $cart_goods_list = array();
        } else {
            $cart_goods_list = json_decode($cart_goods_list, true);
        }
        foreach ($cart_goods_list as $k => $v) {
            if (strpos((string)$cart_id_array, (string)$v["cart_id"]) !== false) {
                unset($cart_goods_list[$k]);
            }
        }
        if (empty($cart_goods_list)) {
            cookie('cart_array' . $this->website_id, null);
            return 1;
        } else {
            sort($cart_goods_list);
            try {
                cookie('cart_array' . $this->website_id, json_encode($cart_goods_list), 3600);
                return 1;
            } catch (\Exception $e) {
                recordErrorLog($e);
                return 0;
            }
        }
    }

    /**
     * 修改cookie购物车的数量
     *
     * @param unknown $cart_id
     * @param unknown $num
     * @return number
     */
    private function updateCookieCartNum($cart_id, $num)
    {
        // 获取购物车
        $cart_goods_list = cookie('cart_array' . $this->website_id);
        if (empty($cart_goods_list)) {
            $cart_goods_list = array();
        } else {
            $cart_goods_list = json_decode($cart_goods_list, true);
        }
        foreach ($cart_goods_list as $k => $v) {
            if ($v["cart_id"] == $cart_id) {
                $cart_goods_list[$k]["num"] = $num;
            }
        }
        sort($cart_goods_list);
        try {
            cookie('cart_array' . $this->website_id, json_encode($cart_goods_list), 3600);
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            return 0;
        }
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::syncUserCart()
     */
    public function syncUserCart($uid)
    {
        // TODO Auto-generated method stub
        $cart = new VslCartModel();
        $cart_query = $cart->getQuery([
            "buyer_id" => $uid
        ], '*', '');
        // 获取购物车
        $cart_goods_list = cookie('cart_array' . $this->website_id);
//        $cart_goods_list = '[{"shop_id":0,"goods_id":26,"sku_id":"48","num":4,"goods_picture":107,"seckill_id":33,"cart_id":1}]';
        if (empty($cart_goods_list)) {
            $cart_goods_list = array();
        } else {
            $cart_goods_list = json_decode($cart_goods_list, true);
        }
        $goodsmodel = new VslGoodsModel();
        $web_site = new WebSite();
        $goods_sku = new VslGoodsSkuModel();

        // 遍历cookie购物车
        if (!empty($cart_goods_list)) {
            foreach ($cart_goods_list as $k => $v) {
                // 商品信息
                $goods_info = $goodsmodel->getInfo([
                    'goods_id' => $v['goods_id']
                ], 'picture, goods_name, price');
                // sku信息
                $sku_info = $goods_sku->getInfo([
                    'sku_id' => $v['sku_id']
                ], 'price, sku_name, promote_price');
                if (empty($goods_info)) {
                    break;
                }
                if (empty($sku_info)) {
                    break;
                }
                // 查看用户会员价
                $goods_preference = new GoodsPreference();
                if (!empty($this->uid)) {
                    $member_model = new VslMemberModel();
                    $member_level_info = $member_model->getInfo(['uid' => $uid])['member_level'];
                    $member_level = new VslMemberLevelModel();
                    $member_info = $member_level->getInfo(['level_id' => $member_level_info]);
                    $member_discount = $member_info['goods_discount'] / 10;
                    $member_is_label = $member_info['is_label'];
                } else {
                    $member_discount = 1;
                }
                //未登录加入秒杀商品进入购物车的情况
                if (!empty($v['seckill_id']) && getAddons('seckill', $this->website_id, $this->instance_id)) {
                    $condition_seckill['sku_id'] = $v['sku_id'];
                    $condition_seckill['seckill_id'] = $v['seckill_id'];
                    $seckill_server = new SeckillServer();
                    $price = $seckill_server->getSeckillSkuInfo($condition_seckill);
                } else {
                    if ($member_is_label) {
                        $member_price = round($member_discount * $sku_info['price']);
                    } else {
                        $member_price = round($member_discount * $sku_info['price'], 2);
                    }
                    if ($member_price > $sku_info["promote_price"]) {
                        $price = $sku_info["promote_price"];
                    } else {
                        $price = $member_price;
                    }
                }

                // 判断此用户有无购物车
                if (empty($cart_query)) {
                    // 获取商品sku信息
                    $this->addCart($uid, $this->instance_id, $v["goods_id"], $goods_info["goods_name"], $v["sku_id"], $sku_info["sku_name"], $price, $v["num"], $goods_info["picture"], 0, $v['seckill_id']);
                } else {
                    $is_have = true;
                    foreach ($cart_query as $t => $m) {
                        if ($m["sku_id"] == $v["sku_id"] && $m["goods_id"] == $v["goods_id"]) {
                            $is_have = false;
                            $num = $m["num"] + $v["num"];
                            $this->cartAdjustNum($m["cart_id"], $num);
                            break;
                        }
                    }
                    if ($is_have) {
                        $this->addCart($uid, $this->instance_id, $v["goods_id"], $goods_info["goods_name"], $v["sku_id"], $sku_info["sku_name"], $price, $v["num"], $goods_info["picture"], 0, $v['seckill_id']);
                    }
                }
            }
        }
        cookie('cart_array' . $this->website_id, null);
    }

    /**
     * 更改商品排序
     *
     * @param unknown $goods_id
     * @param unknown $sort
     * @return boolean
     */
    public function updateGoodsSort($goods_id, $sort)
    {
        $goods = new VslGoodsModel();
        return $goods->save([
            'sort' => $sort
        ], [
            'goods_id' => $goods_id
        ]);
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::addGoodsSkuPicture()
     */
    public function addGoodsSkuPicture($shop_id, $goods_id, $spec_id, $spec_value_id, $sku_img_array)
    {
        // TODO Auto-generated method stub
        $goods_sku_picture = new VslGoodsSkuPictureModel();
        $data = array(
            "shop_id" => $shop_id,
            "goods_id" => $goods_id,
            "spec_id" => $spec_id,
            "spec_value_id" => $spec_value_id,
            "sku_img_array" => $sku_img_array,
            "create_time" => time(),
            "modify_time" => time()
        );
        $retval = $goods_sku_picture->save($data);
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::deleteGoodsSkuPicture()
     */
    public function deleteGoodsSkuPicture($condition)
    {
        // TODO Auto-generated method stub
        $goods_sku_picture = new VslGoodsSkuPictureModel();
        $retval = $goods_sku_picture->destroy($condition);
        return $retval;
    }

    /**
     * 获取随机商品
     *
     * {@inheritdoc}
     *
     * @see \data\api\IGoods::getRandGoodsList()
     */
    public function getRandGoodsList()
    {
        if (getAddons('shop', $this->website_id)) {
            $result = $this->goods->getQuery([
                'state' => 1,
                'website_id' => $this->website_id
            ], 'goods_id', '');
        } else {
            $result = $this->goods->getQuery([
                'state' => 1,
                'shop_id' => 0,
                'website_id' => $this->website_id
            ], 'goods_id', '');
        }
        $res = array_rand($result, 12);
        $goods_id_list = array();
        foreach ($res as $v) {
            $goods_id_list[] = $result[$v];
        }
        $goodsList = array();
        foreach ($goods_id_list as $g) {
            $goodsList[] = $this->getGoodsDetail($g['goods_id']);
        }
        return $goodsList;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsSkuQuery()
     */
    public function getGoodsSkuQuery($condition)
    {
        // TODO Auto-generated method stub
        $goods_sku_model = new VslGoodsSkuModel();
        $goods_query = $goods_sku_model->getQuery($condition, "goods_id", "");
        return $goods_query;
    }

    /**
     * 设置点赞送积分
     */
    public function setGoodsSpotFabulous($shop_id, $uid, $goods_id)
    {
        $click_goods = new VslClickFabulousModel();
        // 点赞成功送积分
        $rewardRule = new PromoteRewardRule();
        // 查询点赞赠送积分数量，然后叠加
        $info = $rewardRule->getRewardRuleDetail($shop_id);
        $data = array(
            'shop_id' => $shop_id,
            'website_id' => $this->website_id,
            'uid' => $uid,
            'goods_id' => $goods_id,
            'status' => 1,
            'number' => $info['click_point'],
            'create_time' => time()
        );
        $retval = $click_goods->save($data);
        if ($retval > 0) {
            $res = $rewardRule->addMemberPointData($shop_id, $uid, $info['click_point'], 19, '点赞赠送积分');
        }
        return $retval;
    }

    /**
     * 查询点赞状态
     *
     * @param unknown $shop_id
     * @param unknown $uid
     * @param unknown $goods_id
     */
    public function getGoodsSpotFabulous($shop_id, $uid, $goods_id)
    {
        $click_goods = new VslClickFabulousModel();
        $start_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $end_time = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $condition = array(
            'shop_id' => $shop_id,
            'uid' => $uid,
            'goods_id' => $goods_id,
            'create_time' => array(
                'between',
                [
                    $start_time,
                    $end_time
                ]
            )
        );

        $retval = $click_goods->getInfo($condition);
        return $retval;
    }

    /**
     * 修改商品名称或促销语
     */
    public function updateGoodsNameOrIntroduction($goods_id, $up_type, $up_content)
    {
        $condition = array(
            "goods_id" => $goods_id,
            "website_id" => $this->website_id
        );
        if ($up_type == "goods_name") {
            $this->goods->save([
                "goods_name" => $up_content
            ], $condition);
            //判断这个商品有没有核销门店，如果有，就要同步到核销门店
            $store_list = $this->goods->Query($condition, 'store_list')[0];
            if ($store_list) {
                $store_list = explode(',', $store_list);
                foreach ($store_list as $k => $v) {
                    $store_goods_model = new VslStoreGoodsModel();
                    $store_condition = [
                        "goods_id" => $goods_id,
                        "website_id" => $this->website_id,
                        "store_id" => $v
                    ];
                    $store_goods_model->save(["goods_name" => $up_content], $store_condition);
                }
            }
        } elseif ($up_type == "price") {
            $goods_sku = new VslGoodsSkuModel();
            $res = $goods_sku->save([
                "price" => $up_content
            ], ['goods_id' => $goods_id]);
            if (!$res) {
                return -1;
            }
            return $this->goods->save([
                "price" => $up_content
            ], $condition);
        } elseif ($up_type == "market_price") {
            $goods_sku = new VslGoodsSkuModel();
            $res = $goods_sku->save([
                "market_price" => $up_content
            ], ['goods_id' => $goods_id]);
            if (!$res) {
                return -1;
            }
            return $this->goods->save([
                "market_price" => $up_content
            ], $condition);
        } elseif ($up_type == "stock") {
            $goods_sku = new VslGoodsSkuModel();
            $res = $goods_sku->save([
                "stock" => $up_content
            ], ['goods_id' => $goods_id]);
            if (!$res) {
                return -1;
            }
            return $this->goods->save([
                "stock" => $up_content
            ], $condition);
        } elseif ($up_type == 'short_name') {
            return $this->goods->save([
                'short_name' => $up_content
            ], $condition);
        }
    }

    /**
     * 查询商品积分兑换(non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsPointExchange()
     */
    public function getGoodsPointExchange($goods_id)
    {
        $goods_model = new VslGoodsModel();
        $goods_info = $goods_model->getInfo([
            'goods_id' => $goods_id
        ], 'point_exchange_type,point_exchange');
        if ($goods_info['point_exchange_type'] == 0) {
            return 0;
        } else {
            return $goods_info['point_exchange'];
        }
    }

    /**
     * 修改商品属性表属性排序
     *
     * {@inheritdoc}
     *
     * @see \data\api\IGoods::updateGoodsAttributeSort()
     */
    public function updateGoodsAttributeSort($attr_value_id, $sort, $shop_id)
    {
        $goods_attribute = new VslGoodsAttributeModel();
        return $goods_attribute->save([
            "sort" => $sort
        ], [
            "attr_value_id" => $attr_value_id,
            "shop_id" => $shop_id
        ]);
    }

    /*
     * 获取商品对应的type、sku值
     * **/
    public function getGoodSku($condition)
    {
        return $this->goods_spec_value_model->all($condition, 'goods_spec');
    }

    /*
     * 获取规格展示方式图片对应的路径
     * [params] array $condition 查询条件
     * return str 对应的图片路径
     * **/
    public function getGoodSkuPic($condition)
    {
        $sku_pic_mdl = new AlbumPictureModel();
        $sku_pic_list = $sku_pic_mdl->where($condition)->find();
        $sku_pic_arr = objToArr($sku_pic_list);
        $pic_cover_small = $sku_pic_arr['pic_cover_small'];
        $pic_cover_small = getApiSrc($pic_cover_small);
        return $pic_cover_small;
    }

    /*
     * 获取商品归属平台还是店铺
     * [param] str $goods_id 商品id
     * return str 0-店铺 1-平台
     * **/
    public function getGoodsType($goods_id)
    {
        $goods_mdl = new VslGoodsModel();
        $goods_type_list = $goods_mdl->field('website_id, shop_id')->where(['goods_id' => $goods_id])->find();
        $website_id = $goods_type_list->website_id;
        $shop_id = $goods_type_list->shop_id;
        if ($shop_id === 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /*
     * 获取店铺id
     * **/
    public function getSkuShopId($condition)
    {
        $goods_mdl = new VslGoodsModel();
        $goods_sku_info = $goods_mdl->alias('g')->join('vsl_goods_sku gs', 'g.goods_id = gs.goods_id', 'left')->where($condition)->find();
        return $goods_sku_info;
    }

    /*
     * 获取商品的信息
     * **/
    public function getGoodsName($goods_id)
    {
        $goods_mdl = new VslGoodsModel();
        $goods_list = $goods_mdl->where(['goods_id' => $goods_id])->find();
        return objToArr($goods_list);
    }

    /*
     * 获取商品sku最大购买量
     * **/
    public function getActivityOrderSku($uid, $sku_id, $website_id, $seckill_id)
    {
        $redis = $this->connectRedis();
        $user_buy_sku_num_key = 'buy_' . $seckill_id . '_' . $uid . '_' . $sku_id . '_num';
        $buy_num = $redis->get($user_buy_sku_num_key);
        if (!$buy_num) {
            $activity_os_mdl = new VslActivityOrderSkuRecordModel();
            $activity_os_info = $activity_os_mdl->where(['uid' => $uid, 'sku_id' => $sku_id, 'website_id' => $website_id, 'buy_type' => 1, 'activity_id' => $seckill_id])->find();
//                    echo $activity_os_mdl->getLastSql();exit;
            $buy_num = $activity_os_info['num'];
            $redis->set($user_buy_sku_num_key, $buy_num);
        }
        return $buy_num;
    }

    /*
     * 拼团获取商品sku最大购买量
     * **/
    public function getActivityOrderSkuForGroup($uid, $sku_id, $website_id, $group_id)
    {
        $activity_os_mdl = new VslActivityOrderSkuRecordModel();
        $buy_num = $activity_os_mdl->getSum(['uid' => $uid, 'sku_id' => $sku_id, 'website_id' => $website_id, 'buy_type' => 2, 'activity_id' => $group_id], 'num');
        return $buy_num;
    }

    /*
     * 活动获取商品sku最大购买量
     * **/
    public function getActivityOrderSkuNum($uid, $sku_id, $website_id, $buy_type, $activity_id)
    {
        $activity_os_mdl = new VslActivityOrderSkuRecordModel();
        $activity_os_info = $activity_os_mdl->where(['uid' => $uid, 'sku_id' => $sku_id, 'website_id' => $website_id, 'buy_type' => $buy_type, 'activity_id' => $activity_id])->find();
        $buy_num = $activity_os_info['num'];
        return $buy_num;
    }

    /*
     * 根据sku_id获取库存
     * **/
    public function getSkuBySkuid($sku_id)
    {
        $goodsSkuModel = new VslGoodsSkuModel();
        $stock = $goodsSkuModel->getSum(['sku_id' => $sku_id], 'stock');
        return $stock;
    }

    /**
     * 获取运费模板在goods和goods 回收站的数目
     * @param array $condition
     * @return int
     */
    public function freightTemplateCount(array $condition)
    {
        $goods_count = $this->goods->where($condition)->count();
        $goods_delete_model = new VslGoodsDeletedModel();
        $goods_delete_count = $goods_delete_model->where($condition)->count();
        return $goods_count + $goods_delete_count;
    }

    /*
     * 获取模态框的商品列表
     * **/
    public function getModalGoodsList($index, $condition, $seckill_date = '')
    {
        $list = $this->getgoodslist($index, PAGESIZE, $condition);
//        p($list);exit;
//        unset($list['data'][3]);
        if (!empty($list['data'])) {
            //处理删除第一个是空sku，第二个为有sku的情况
            foreach ($list['data'] as $k => $v) {
                if (!empty($v['sku_list'][0]['attr_value_items']) || !empty($v['sku_list'][1]['attr_value_items'])) {
                    unset($list['data'][$k]['sku_list'][0]);
                }
                if (!empty($list['data'][$k])) {
                    $goods_list[$k]['goods_id'] = $v['goods_id'];
                }
            }
            //删除多余的字段
            $sku_list = [];
            if (!empty($list['data'])) {
//                p($list['data']);exit;
                foreach ($list['data'] as $k => $v) {
                    $goods_spec_format = $v['goods_spec_format'];
                    $goods_spec_arr = json_decode($goods_spec_format, true);
                    $goods_list[$k]['goods_id'] = $v['goods_id'];
                    $goods_list[$k]['goods_name'] = $v['goods_name'];
                    $goods_list[$k]['price'] = $v['price'];
                    if ($v['promotion_type'] == 1) {//如果是秒杀类型的商品，判断24小时内是否有该活动商品
                        if ($seckill_date) {
                            $seckill_server = new SeckillServer();
                            $is_add_seckill = $seckill_server->IsSeckillInTwentyFour($v['goods_id'], $seckill_date);
                            if ($is_add_seckill) {
                                $goods_list[$k]['promotion_type'] = $v['promotion_type'];
                                $goods_list[$k]['promotion_name'] = $this->getGoodsPromotionType($v['promotion_type']);
                            }
                        } else {
                            $goods_list[$k]['promotion_type'] = $v['promotion_type'];
                            $goods_list[$k]['promotion_name'] = $this->getGoodsPromotionType($v['promotion_type']);
                        }
                    } else {
                        $goods_list[$k]['promotion_type'] = $v['promotion_type'];
                        $goods_list[$k]['promotion_name'] = $this->getGoodsPromotionType($v['promotion_type']);
                    }
                    //处理skulist对象
                    $v['sku_list'][0]['attr_value_items'] = trim($v['sku_list'][0]['attr_value_items']);
                    if (!empty($v['sku_list'][0]['attr_value_items'])) {
                        foreach ($v['sku_list'] as $sku_key => $sku_value) {
                            $sku_val_item = $sku_value['attr_value_items'];
                            $sku_val_arr = explode(';', $sku_val_item);
                            $th_name_str = '';
                            $show_value_str = '';
                            $show_type_str = '';
                            foreach ($sku_val_arr as $sku_val_key => $sku_val_value) {
                                $sku_val_value_arr = explode(':', $sku_val_value);
                                //按照规格规则中的顺序定义tr头
                                $sku_tr_id = $sku_val_value_arr[1];
                                //这里屏蔽掉是因为 如果规格删掉了，则从规格表里面取不到规格值了，导致商品报错。
                                /*$val_type = $this->getGoodSku(['spec_value_id' => $sku_tr_id]);
                                $val_type_arr = $val_type[0]->toArray();
                                p($val_type_arr);exit;*/
                                $val_type_arr = '';
                                foreach ($goods_spec_arr as $k0 => $v0) {
                                    foreach ($v0['value'] as $k01 => $v01) {
                                        if ($v01['spec_value_id'] == $sku_tr_id) {
                                            $val_type_arr['goods_spec']['show_type'] = $v01['spec_show_type'];
                                            $val_type_arr['goods_spec']['spec_name'] = $v01['spec_name'];
                                            $val_type_arr['spec_value_name'] = $v01['spec_value_name'];
                                        }
                                    }
                                }
                                $show_type = $val_type_arr['goods_spec']['show_type'];
                                //根据show_type，获取规格的值，如图片的路径
                                if ($show_type == '3') {//图片
                                    /*$pic_id = $val_type_arr['spec_value_data'];
                                    $val_type_str = $this->getGoodSkuPic(['pic_id' => $pic_id]);
                                    if (empty($val_type_str)) {
                                        $val_type_str = '暂无图片';
                                    }*/
                                    $val_type_str = $val_type_arr['spec_value_name'];//暂时展示中文。
                                } else if ($show_type == '2') {//颜色
                                    $val_type_str = $val_type_arr['spec_value_name'];
                                } else {
                                    $val_type_str = $val_type_arr['spec_value_name'];
                                }
                                //拼接所有规格展示类型对应的值
                                $show_value_str .= $val_type_str . '§';
                                //拼接th的名字
                                $th_name_str .= $val_type_arr['goods_spec']['spec_name'] . ' ';
                                //拼接展示类型
                                $show_type_str .= $show_type . ' ';
                            }
                            $th_name_str = trim($th_name_str);
                            $show_type_str = trim($show_type_str);
                            $show_value_str = trim($show_value_str, '§');
                            $sku_list = $sku_value->toArray();
                            //处理sku的id对应value
                            $sku_id_str = $sku_list['attr_value_items'];
                            $sku_id_str_arr = explode(';', $sku_id_str);
                            $sku_value_str = trim($show_value_str);
                            $sku_value_str_arr = explode('§', $sku_value_str);
                            $im_str = '';
                            $new_im_str = '';
                            for ($i = 0; $i < count($sku_value_str_arr); $i++) {
                                $im_str .= $sku_id_str_arr[$i] . ';';
                                $im_str = trim($im_str, ';');
                                $new_im_str .= $im_str . '=' . $sku_value_str_arr[$i] . '§';
                            }
                            $new_im_str = trim($new_im_str, '§');
                            $v['sku_list'][$sku_key]['new_im_str'] = $new_im_str;
                            $v['sku_list'][$sku_key]['th_name_str'] = $th_name_str;
                            $v['sku_list'][$sku_key]['show_type_str'] = $show_type_str;
//                            if($k == 3){
////                                p($v['sku_list']);exit;
////                            }
                        }
                        /*************************当sku规格错乱的时候排序****************************/
                        $temp = [];
                        foreach ($v['sku_list'] as $k1 => $sort_sku) {
                            $sort_arr = explode('§', $sort_sku['new_im_str']);
                            $sort_str = $sort_arr[0];
                            $temp[$sort_str][$k1] = $sort_sku;
                        }
                        $i = 0;
                        $sku_temp = [];
                        foreach ($temp as $k2 => $r) {
                            foreach ($r as $last_val) {
                                $sku_temp[$i] = $last_val;
                                $i++;
                            }
                        }
                        $v['sku_list'] = $sku_temp;
                    } else {
                        $v['sku_list'] = $v['sku_list'][0];
                    }

                    $goods_list[$k]['shop_name'] = $v['shop_name'] ?: '自营店';
                    $goods_list[$k]['pic_cover'] = getApiSrc($v['pic_cover']);
                    $goods_list[$k]['sku_list'] = $v['sku_list'];
                }
            }
        } else {
            $goods_list = [];
        }

        //处理sku字符串
        if (!empty($goods_list)) {
            foreach ($goods_list as $sku_key2 => $sku_value2) {
                $goods_list[$sku_key2]['sku_list'] = json_encode($sku_value2['sku_list']);
            }
            $list['data'] = $goods_list;
        } else {
            $list['data'] = '';
            $list['page_count'] = 0;
            $list['total_count'] = 0;
        }

        return $list;
    }

    public function goodsAttribute(array $condition, array $with = [])
    {
        $goods_attribute_model = new VslGoodsAttributeModel();
        $list = $goods_attribute_model::all($condition, $with);
        $return_data = [];
        foreach ($list as $v) {
            if (!$v->attr_value_name || !$v->attribute_value->attr_value_name) {
                continue;
            }
            $temp['attr_value'] = $v->attribute_value->attr_value_name;
            $temp['attr_value_name'] = $v->attr_value_name;
            $temp['attr_value_id'] = $v->attr_value_id;
            $temp['sort'] = $v->sort;
            $return_data[] = $temp;
        }
        unset($v);
        return $return_data;
    }

    /**
     * 获取商品活动类型
     *
     * @param unknown $type_id
     */
    public function getGoodsPromotionType($type_id)
    {
        if (!$type_id) {
            return '';
        }
        $order_type = array(
            array(
                'type_id' => '1',
                'type_name' => '秒杀活动'
            ),
            array(
                'type_id' => '2',
                'type_name' => '拼团活动'
            ),
            array(
                'type_id' => '3',
                'type_name' => '预售活动'
            ),
            array(
                'type_id' => '4',
                'type_name' => '砍价活动'
            ),
            array(
                'type_id' => '5',
                'type_name' => '限时折扣'
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
     * 违规下架
     */
    public function ModifyGoodsOutline($condition)
    {
        $goods_ids = $condition['goods_ids'];
        if (!$goods_ids) {
            return UPDATA_FAIL;
        }
        $data = array(
            "state" => 10,
            'update_time' => time(),
            'illegal_reason' => $condition['reason'],
        );
        $result = $this->goods->save($data, "goods_id in($goods_ids)");
        if ($result > 0) {
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    /**
     * 商品審核
     */
    public function ModifyGoodsAudit($condition)
    {
        $goods_ids = $condition['goods_ids'];
        if (!$goods_ids) {
            return UPDATA_FAIL;
        }
        $data = array(
            "state" => $condition['state'],
            'update_time' => time(),
            'illegal_reason' => $condition['reason'],
        );
        $result = $this->goods->save($data, "goods_id in($goods_ids)");
        if ($result > 0) {
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    /**
     * 修改标签
     */
    public function editLabel($goods_id, $label)
    {
        $goods_mdl = new VslGoodsModel();
        $value = $goods_mdl->where(['goods_id' => $goods_id])->value($label);
        $data = [];
        $data[$label] = ($value == 1) ? 0 : 1;
        $res = $goods_mdl->where(['goods_id' => $goods_id])->update($data);
        return $res;
    }

    /**
     * 上传图片到云
     */
    public function modifyImageUrl2AliOss($url = '')
    {
        // 是本域名的就不要上传云
        $domain = parse_url($url);
        $domain_name = $domain['scheme'] . '://' . $domain['host'];//https://www.baidu.com
        if ($domain_name == Request::instance()->domain()) {
            return $url;
        }
        // 先下载本地，再上传云
        $ext = substr(strrchr($url, '.'), 1);
        $ext_name = basename($url, "." . $ext);//不带后缀文件名
        $image = https_request($url);
        $path = 'upload/network/' . $this->website_id . '/';//云存储的地址

        // 上传云
        return getImageFromYun($image, $path, $ext_name);
    }

    /**压缩图片并转base64
     * @param string $url 图片url
     * @return string [base64]
     */
    public function thumbAndTransBase64Code($url = '')
    {
        if (empty($url)) {
            return;
        }
        // 存本地并压缩
        $image = https_request($url);
        $file_name = transAndThumbImg($image, 'upload/temp/', 'temp', 600, 300);
        // 图片转base64
        $base64_img = base64EncodeImage($file_name);
        @unlink($file_name);
        return $base64_img;
    }

    /*
     * 品类列表修改属性值
     */
    public function updateAttributeValueService($attr_id, $value_string)
    {
        $attribute_value = new VslAttributeValueModel();
        $condition['attr_id'] = $attr_id;
        $value_list = $attribute_value->getQuery($condition, 'attr_value_id', '');
        //变成一维数组
        $attr_value_list = array();
        foreach ($value_list as $k => $v) {
            $attr_value_list[] = $v['attr_value_id'];
        }
        $goods_attr = array();
        $checkArray = [];
        if (!empty($value_string)) {
            $value_array = explode(';', $value_string);
            foreach ($value_array as $k => $v) {
                $new_array = array();
                $new_array = explode('|', $v);
                if (in_array($new_array[0], $checkArray)) {
                    return -10017;
                }
                $checkArray[] = $new_array[0];
                if (!empty($new_array[5])) {
                    $goods_attr[] = $new_array[5];
                    $this->addAttributeValueService($attr_id, $new_array[0], $new_array[1], $new_array[2], $new_array[3], $new_array[4], $new_array[5]);
                } else {
                    $this->addAttributeValueService($attr_id, $new_array[0], $new_array[1], $new_array[2], $new_array[3], $new_array[4]);
                }

            }
        }
        //循环数据库的attr_value和传过来的，多出来的则删除
        foreach ($attr_value_list as $k => $v) {
            if (!in_array($v, $goods_attr)) {
                $condition['attr_value_id'] = $v;
                $attribute_value->delData($condition);
            }
        }
        if (!in_array($new_array[5], $attr_value_list) && !empty($new_array[5])) {
            $attribute_value->delData(['attr_value_id' => $new_array[5]]);
        }
        return 1;
    }

    /*
     * 品类列表修改关联规格
     */
    public function updateAttributeSpecService($attr_id, $spec_id_array)
    {
        $attribute = new VslAttributeModel();
        $attr = $attribute->getInfo(['attr_id' => $attr_id], ['spec_id_array']);
        if (!$attr) {
            return 0;
        }
        $attribute->startTrans();
        try {
            $data = array(
                "spec_id_array" => $spec_id_array,
                "modify_time" => time()
            );
            $res = $attribute->save($data, [
                'attr_id' => $attr_id
            ]);
            //如果本来有关联规格，检查是否有取消的规格，并从规格表删掉关联的品类
            if ($attr['spec_id_array']) {
                $specIdArr = explode(',', $attr['spec_id_array']);
                $newSpecIdArr = explode(',', $spec_id_array);
                foreach ($specIdArr as $spec_id) {
                    if (!in_array($val, $newSpecIdArr)) {
                        $this->deleteAttrFromSpec($spec_id, $attr_id);
                    }
                }
            }
            $attribute->commit();
            return $res;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $attribute->rollback();
            return DELETE_FAIL;
        }

    }

    /*
     * 品类列表修改关联规格
     */
    public function updateAttributeBrandService($attr_id, $brand_id_array)
    {
        $attribute = new VslAttributeModel();
        $data = array(
            "brand_id_array" => $brand_id_array,
            "modify_time" => time()
        );
        $res = $attribute->save($data, [
            'attr_id' => $attr_id
        ]);
        return $res;
    }

    /*
     * 品类列表修改关联分类
     */
    public function updateAttributeCateService($attr_id, $attr_name, $cate_obj_arr = [])
    {
        $goodsCategory = new VslGoodsCategoryModel();
        $goodsCategory->save(['attr_id' => 0, 'attr_name' => ''], ['attr_id' => $attr_id]);
        if ($cate_obj_arr) {
            foreach ($cate_obj_arr as $val) {
                $goodsCategory = new VslGoodsCategoryModel();
                $goodsCategory->save(['attr_id' => $attr_id, 'attr_name' => $attr_name], ['category_id' => $val]);
            }
            unset($val);
        }
        return 1;
    }

    /**
     * 是否开启独立折扣的浏览权限
     * @param $goods_id
     */
    public function isIndependentBrowse($goods_id)
    {
        $goodsDiscount = new VslGoodsDiscountModel();
        $condition['goods_id'] = $goods_id;
        $condition['website_id'] = $this->website_id;
        $res = $goodsDiscount->getInfo($condition);
        if (!isset($res['browse_auth_u']) && !isset($res['browse_auth_d']) && !isset($res['browse_auth_s'])) {
            return false;// 未设置
        }
        return true;
    }

    /**
     * 是否开启独立折扣的浏览权限
     * @param $goods_id
     */
    public function isIndependentBuy($goods_id)
    {
        $goodsDiscount = new VslGoodsDiscountModel();
        $condition['goods_id'] = $goods_id;
        $condition['website_id'] = $this->website_id;
        $res = $goodsDiscount->getInfo($condition);
        if (!isset($res['buy_auth_u']) && !isset($res['buy_auth_d']) && !isset($res['buy_auth_s'])) {
            return false;// 未设置
        }
        return true;
    }

    /**
     * 购买该商品权限
     * @param $uid [int] 用户id
     * @param $goods_id [int] 商品id
     */
    public function isAllowToBuyThisGoods($uid, $goods_id)
    {
        $is_set_bug = $this->isIndependentBuy($goods_id);
        if (!$is_set_bug) {
            return true; //该商品未设置就不用判断购买权限了
        }
        $goodsPower = $this->getGoodsPowerDiscount($goods_id);
        if (!isset($goodsPower['buy_auth_u']) && !isset($goodsPower['buy_auth_d']) && !isset($goodsPower['buy_auth_s'])) {
            return true;
        }
        if ($goodsPower['buy_auth_u'] == '' && $goodsPower['buy_auth_d'] == '' && $goodsPower['buy_auth_s'] == '') {
            return true;
        }
        // 用户购买权限
        $userService = new User(); 
        $userLevle = $userService->getUserLevelAndGroupLevel($uid);//优先级: user_level会员 > distributor_level分销商 > member_group会员标签;
        if (empty($userLevle)) {
            return true;
        }
        if ($userLevle['user_level']) {
            if (!isset($goodsPower['buy_auth_u']) || $goodsPower['buy_auth_u'] == '') {//不设权限表示都可以购买
                return true;
            }
            $id = $userLevle['user_level'];
            if (in_array($id, explode(',', $goodsPower['buy_auth_u']))) {
                return true;
            }
        }
        if ($userLevle['distributor_level']) {
            if (!isset($goodsPower['buy_auth_d']) || $goodsPower['buy_auth_d'] == '') {
                return true;
            }
            $id = $userLevle['distributor_level'];
            if (in_array($id, explode(',', $goodsPower['buy_auth_d']))) {
                return true;
            }
        }
        if ($userLevle['member_group']) {
            if (!isset($goodsPower['buy_auth_s']) || $goodsPower['buy_auth_s'] == '') {
                return true;
            }
            $ids = $userLevle['member_group'];// eg: '3,2'
            $ids = explode(',', $ids);
            if ($temp = array_values(array_intersect($ids, explode(',', $goodsPower['buy_auth_s'])))) {//两个数组交集
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * 浏览该商品权限
     * @param $uid
     * @param $goods_id
     */
    public function isAllowToBrowse($uid, $goods_id)
    {
        if (empty($uid)) {
            return true;
        }
        $is_set_browse = $this->isIndependentBrowse($goods_id);
        if (!$is_set_browse) {
            return true; //该商品未设置就不用判断浏览权限了
        }
        // 获取该用户的权限
        $userService = new User();
        $userLevle = $userService->getUserLevelAndGroupLevel($uid);// member_group会员标签; distributor_level分销商; user_level会员
        if (!empty($userLevle)) {
            $sql1 = '';
            $sql2 = '(';
            // 会员权限
            if ($userLevle['user_level']) {
                $u_id = $userLevle['user_level'];
                $sql1 .= "instr(CONCAT( ',',browse_auth_u, ',' ), '," . $u_id . ",' ) OR ";
                $sql2 .= "browse_auth_u IS NULL OR browse_auth_u = '' ";
            }
            // 分销商权限
            if ($userLevle['distributor_level']) {
                $d_id = $userLevle['distributor_level'];
                $sql1 .= "instr(CONCAT( ',', browse_auth_d, ',' ), '," . $d_id . ",' ) OR ";
                $sql2 .= " OR browse_auth_d IS NULL OR browse_auth_d = '' ";
            }
            // 标签权限
            if ($userLevle['member_group']) {
                $g_ids = explode(',', $userLevle['member_group']);
                foreach ($g_ids as $g_id) {
                    $sql1 .= "instr(CONCAT( ',', browse_auth_s, ',' ), '," . $g_id . ",' ) OR ";
                    $sql2 .= " OR browse_auth_s IS NULL OR browse_auth_s = '' ";
                }
            } else {
                $sql1 .= " ";
            }
            $sql2 .= " )";
            $condition[] = ['exp', $sql1 . $sql2];
        }

        $condition['goods_id'] = $goods_id;
        $condition['website_id'] = $this->website_id;
        $goodsDiscount = new VslGoodsDiscountModel();
        $goodsDiscountRes = $goodsDiscount->getInfo($condition, 'id');
        if ($goodsDiscountRes) {
            return true;
        }
        return false;
    }

    /**
     * 查询商品拥有的权限折扣
     * @param $goods_id
     */
    public function getGoodsPowerDiscount($goods_id)
    {
        $goodsDiscount = new VslGoodsDiscountModel();
        $condition = [
            'type' => 1,
            'goods_id' => $goods_id,
            'website_id' => $this->website_id,
        ];
        $res = $goodsDiscount->getInfo($condition);
        return $res;
    }

    /**
     * 获取商品会员折扣以及会员独立后的信息
     * @param $goods_id
     * @param $goods_price 商品原价格
     * @return member_price     // 折扣后价格
     * @return member_discount   // 折扣率 例如 1就是没有折扣 0.9折扣90%
     * @return is_show_member_price //是否显示折后价
     * @return member_is_label   // 是否取整
     * @return discount_choice   // 折扣方式选择 1：折扣  2：固定金额（折扣率为1）
     */
    public function getGoodsInfoOfIndependentDiscount($goods_id, $goods_price = 0)
    {
        if (empty($goods_price)) {
            $goods = new VslGoodsModel();
            $condition = ['website_id' => $this->website_id, 'goods_id' => $goods_id];

            $goods_detail = $goods->getInfo($condition, 'price');
            $goods_price = $goods_detail['price'];
        }
        // 查询商品是否有开启会员折扣
        $goodsPower = $this->getGoodsPowerDiscount($goods_id);

        // 默认会员价（不管是分销商还是会员）
        $member_model = new VslMemberModel();
        $member_info = $member_model::get($this->uid)->level;
        $goods_detail['discount_choice'] = 1;
        $goods_detail['member_discount'] = $member_info['goods_discount'] / 10 > 0 ? $member_info['goods_discount'] / 10 : 1;
        $goods_detail['member_is_label'] = $member_info['is_label'] ?: 0;
        if ($member_info['is_label'] == 1) {
            $goods_detail['member_price'] = round($goods_detail['member_discount'] * $goods_price);
        } else {
            $goods_detail['member_price'] = round($goods_detail['member_discount'] * $goods_price, 2);
        }
        //平台总会员折扣
        $goods_detail['platform_member_price'] = $goods_price - $goods_detail['member_price'];
        // $goods_detail['shop_member_price'] = $goods_price - $goods_detail['member_price'];
        if ($goods_detail['member_discount'] == 1) {
            $goods_detail['is_show_member_price'] = 0;
        } else {
            $goods_detail['is_show_member_price'] = 1;
        }
        if ($goodsPower && $goodsPower['is_use'] == 0) { //关闭会员折扣，则商品不参与折扣
            $goods_detail['member_price'] = $goods_price;
            $goods_detail['member_discount'] = 1;
            $goods_detail['member_is_label'] = 0;
            $goods_detail['is_show_member_price'] = 0;
            //平台总会员折扣
            $goods_detail['platform_member_price'] = 0;
            $goods_detail['shop_member_price'] = 0;
        } else if ($goodsPower && $goodsPower['is_use'] == 1) {//开启会员折扣
            $goods_detail['is_show_member_price'] = 1;
            // 查询会员的等级
            $userService = new User();
            $userLevle = $userService->getUserLevelAndGroupLevel($this->uid);//distributor_level分销商; user_level会员
            $value = json_decode($goodsPower['value'], TRUE);

            // 用户，分销商折扣关闭就使用原会员折扣
            if ($value['is_user_obj_open'] == 2 && $value['is_distributor_obj_open'] == 2) {

            } else if ($userLevle['distributor_level']) {// 客户是分销商且开启分销商独立折扣
                if ($value['is_distributor_obj_open'] == 1 && $value['distributor_obj']) {
                    $id = $userLevle['distributor_level'];
                    $is_label = $value['distributor_obj']['d_is_label'];//是否取整1取，0不取
                    $discount_choice = $value['distributor_obj']['d_discount_choice'];//折扣方式选择
                    if ($discount_choice == 1) {//折扣
                        $member_discount_val = $value['distributor_obj']['d_level_data'][$id]['val'];
                        $goods_detail['member_discount'] = $member_discount_val / 10 ?: 1;
                        if ($is_label == 1) {
                            $goods_detail['member_price'] = round($goods_detail['member_discount'] * $goods_price);
                        } else {
                            $goods_detail['member_price'] = round($goods_detail['member_discount'] * $goods_price, 2);
                        }
                        $goods_detail['member_is_label'] = $is_label ?: 0;
                        $goods_detail['discount_choice'] = 1;
                    }
                    if ($discount_choice == 2) {//固定金额
                        $goods_detail['member_price'] = $value['distributor_obj']['d_level_data'][$id]['val'] ?: $goods_price;
                        $goods_detail['member_discount'] = 1;
                        $goods_detail['member_is_label'] = 0;
                        $goods_detail['discount_choice'] = 2;
                    }


                } else if ($value['is_distributor_obj_open'] == 2 && $value['is_user_obj_open'] == 1 && $value['user_obj']) {// 关闭取会员独立折扣
                    $id = $userLevle['user_level']; //分销商取（会员等级）
                    $is_label = $value['user_obj']['u_is_label'];//是否取整1取，0不取
                    $discount_choice = $value['user_obj']['u_discount_choice'];//折扣方式选择
                    if ($discount_choice == 1) {//折扣
                        $member_discount_val = $value['user_obj']['u_level_data'][$id]['val'];//折扣
                        $goods_detail['member_discount'] = $member_discount_val / 10 ?: 1;
                        if ($is_label == 1) {
                            $goods_detail['member_price'] = round($goods_detail['member_discount'] * $goods_price);
                        } else {
                            $goods_detail['member_price'] = round($goods_detail['member_discount'] * $goods_price, 2);
                        }
                        $goods_detail['member_is_label'] = $is_label ?: 0;
                        $goods_detail['discount_choice'] = 1;
                    }
                    if ($discount_choice == 2) {//固定金额
                        $goods_detail['member_price'] = $value['user_obj']['u_level_data'][$id]['val'] ?: $goods_price;
                        $goods_detail['member_discount'] = 1;
                        $goods_detail['member_is_label'] = 0;
                        $goods_detail['discount_choice'] = 2;
                    }
                }
                //平台总会员折扣
                $goods_detail['platform_member_price'] = 0;
                $goods_detail['shop_member_price'] = $goods_price - $goods_detail['member_price'];
            } else if ($userLevle['user_level'] && $value['is_user_obj_open'] == 1 && $value['user_obj']) {// 客户是会员且开启会员折扣
                $id = $userLevle['user_level'];
                $is_label = $value['user_obj']['u_is_label'];//是否取整1取，0不取
                $discount_choice = $value['user_obj']['u_discount_choice'];//折扣方式选择
                if ($discount_choice == 1) {//折扣
                    $member_discount_val = $value['user_obj']['u_level_data'][$id]['val'];
                    $goods_detail['member_discount'] = $member_discount_val / 10 ?: 1;
                    if ($is_label == 1) {
                        $goods_detail['member_price'] = round($goods_detail['member_discount'] * $goods_price);
                    } else {
                        $goods_detail['member_price'] = round($goods_detail['member_discount'] * $goods_price, 2);
                    }
                    $goods_detail['member_is_label'] = $is_label ?: 0;
                    $goods_detail['discount_choice'] = 1;
                }
                if ($discount_choice == 2) {//固定金额
                    $goods_detail['member_price'] = $value['user_obj']['u_level_data'][$id]['val'] ?: $goods_price;
                    $goods_detail['member_discount'] = 1;
                    $goods_detail['member_is_label'] = 0;
                    $goods_detail['discount_choice'] = 2;
                }
                //平台总会员折扣
                $goods_detail['platform_member_price'] = 0;
                $goods_detail['shop_member_price'] = $goods_price - $goods_detail['member_price'];
            }

        }

        return $goods_detail;
    }

    /**
     * 该商品用户目前最大可购买数量
     * @param $goods_id
     * @param int $sku_id
     * @return int|mixed  -1:不能购买/不能增加数量, 0:无限购, 数字表示最大限购数
     */
    public function getGoodsMaxBuyNums($goods_id, $sku_id = 0)
    {
        // 查询商品库存
        $goods = new VslGoodsModel();
        $good_sku = new VslGoodsSkuModel();
        $g_condition = [
            'goods_id' => $goods_id,
            'website_id' => $this->website_id
        ];
        $goods = $goods->getInfo($g_condition, 'stock, max_buy, single_limit_buy');
        if ($sku_id) {
            $sku_condition = [
                'sku_id' => $sku_id
            ];
            $goods_sku = $good_sku->getInfo($sku_condition, 'stock');
            if ($goods_sku) {
                $goods['stock'] = $goods_sku['stock'];
            }
        }
        if ($goods['max_buy'] == 0) {
            return 0;
        }
        return $goods['stock'];// todo... 暂时这样处理，后面修改

    }

    /**
     * 查询商品主图
     * @param $goods_id int [商品id]
     * @param bool $default bool [是否默认取第一个主图]
     * @return array [商品图片]
     */
    public function getGoodsMasterImg($goods_id, $default = false)
    {
        if ($default) {
            $goods_info = $this->goods->getInfo([
                'goods_id' => $goods_id
            ], 'img_id_array');

            // 获取商品所有主图
            $goods_img = new AlbumPictureModel();
            $order = "instr('," . $goods_info['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
            $goodsImg = $goods_img->getQuery([
                'pic_id' => [
                    "in",
                    $goods_info['img_id_array']
                ]
            ], '*', $order);
        } else {
            $goodsImg[] = $this->getGoodsImg($goods_id);
        }
        if ($goodsImg) {
            $baseImg = [];
            foreach ($goodsImg as $key => $pic) {
                $baseImg[$key] = $this->thumbAndTransBase64Code(getApiSrc($pic['pic_cover']));
            }
        }

        return $baseImg;
    }
    /**
     * 暂时是移动端/app结算页面的数据获取/计算
     * @param array $sku_list
     * @param string $msg
     *
     * @return array $return_data
     */
    public function storePaymentData(array $sku_list, &$msg = '', $record_id = '', $group_id = '', $presell_id = '', $un_order = 0)
    {
        // 获取非秒杀,团购商品,各个类型所需的数据结构
        // $promotion_sku_list 需要计算折扣,满减,优惠券的商品,即非秒杀,团购商品
        // $return_data 全部数据
        // $return_data[$shop_id]['total_amount'] 店铺应付金额
        // $return_data[$shop_id]['goods_list'] 店铺商品
        // $return_data[$shop_id]['full_cut'] 店铺满减
        // $return_data[$shop_id]['coupon_list'] 店铺优惠券列表
        // $return_data[$shop_id]['member_promotion'] 店铺会员优惠总金额
        // $return_data[$shop_id]['discount_promotion'] 店铺限时折扣优惠总金额
        // $return_data[$shop_id]['full_cut_promotion'] 店铺满减送优惠总金额
        // $promotion_sku_list 获取满减送，优惠券信息的商品数据

        $new_sku_list = $return_data = $sku_id_array = $seckill_sku = $shipping_sku = $record_sku = $promotion_sku_list = [];
        foreach ($sku_list as $k => $v) {
            $new_sku_list[$v['sku_id']] = $v;
            $sku_id_array[] = $v['sku_id'];
            if ($v['store_id']) {
                $sku_model = new VslstoreGoodsSkuModel();
                $sku_detail[$k] = $sku_model::get(['sku_id' => $v['sku_id'], 'store_id' => $v['store_id']], ['goods']);
            } else {
                $sku_model = new VslGoodsSkuModel();
            $sku_detail[$k] = $sku_model::get(['sku_id' => $v['sku_id']], ['goods']);
            }
            $sku_detail[$k]['channel_id'] = $v['channel_id'] ?: 0;
            $sku_detail[$k]['bargain_id'] = $v['bargain_id'] ?: 0;
            $sku_detail[$k]['coupon_id'] = empty($v['coupon_id']) ? 0 : $v['coupon_id'];
            $sku_detail[$k]['num'] = $v['num'] ?: 0;
        }
        $discount_service = getAddons('discount', $this->website_id) ? new Discount() : '';
        $full_cut_service = getAddons('fullcut', $this->website_id) ? new Fullcut() : '';
        $shop = getAddons('shop', $this->website_id) ? new VslShopModel() : '';
        $order_goods_service = new OrderGoods();
        $album_picture_model = new AlbumPictureModel();
        $sec_server = getAddons('seckill', $this->website_id, $this->instance_id) ? new SeckillServer() : '';
        $goods_service = new Goods();
        $group_server = getAddons('groupshopping', $this->website_id, $this->instance_id) ? new GroupShoppingServer() : '';
        $cart_model = new VslStoreCartModel();
        $goods_spec_value = new VslGoodsSpecValueModel();
//        $member_model = new VslMemberModel();
//        $member_level_info = $member_model->getInfo(['uid'=>$this->uid])['member_level'];
//        $member_level = new VslMemberLevelModel();
//        $member_info = $member_level->getInfo(['level_id'=>$member_level_info]);
//        $member_discount = $member_info['goods_discount'] / 10;
//        $member_is_label = $member_info['is_label'];
        $total_account = 0;
        $shop_member_price = 0;
        $platform_member_price = 0;
        foreach ($sku_detail as $k => $v) {
            $presell_shop_id = $v->goods->shop_id;
            //砍价活动id
            $bargain_id = $new_sku_list[$v->sku_id]['bargain_id'] ?: 0;
            $channel_id = $new_sku_list[$v->sku_id]['channel_id'] ?: 0;
            $temp_sku = [];
            $temp_sku['goods_name'] = $v->goods->goods_name;
            if (getAddons('presell', $this->website_id, $this->instance_id) && !$un_order) {
                $presell = new PresellService();
                $is_presell = $presell->getPresellInfoByGoodsIdIng($v->goods_id);
                $presell_arr = objToArr($is_presell);
                $presell_id = $presell_arr[0]['id'];  //预售
            }
            $is_group = getAddons('groupshopping', $this->website_id, $this->instance_id) && $group_server->isGroupGoods($v->goods_id);
            // 活动影响的内容 是 价格、限购、库存
            //判断当前秒杀活动的商品是否已经开始并且没有结束
            if (!empty($new_sku_list[$v->sku_id]['seckill_id']) && getAddons('seckill', $this->website_id, $this->instance_id) && !$un_order) {
                $seckill_id = $new_sku_list[$v->sku_id]['seckill_id'];
                //判断当前秒杀活动的商品是否已经开始并且没有结束
                $condition_seckill['s.website_id'] = $this->website_id;
                $condition_seckill['nsg.sku_id'] = $v->sku_id;
                $condition_seckill['s.seckill_id'] = $seckill_id;
                $is_seckill = $sec_server->isSeckillGoods($condition_seckill);
                if (!$is_seckill && !$un_order) {
                    $temp_sku['price'] = $v->price;
                    $temp_sku['seckill_id'] = 0;
                    if (!empty($new_sku_list[$v->sku_id]['cart_id'])) {
                        $this->storeCartAdjustSec($new_sku_list[$v->sku_id]['cart_id'], 0);
                    }
                    $msg .= $v->goods->goods_name . "商品该sku规格秒杀活动已经结束，已更改为正常状态商品价格" . PHP_EOL;
                } else {
                    //取该商品该用户购买了多少
                    $sku_id = $v->sku_id;
                    $uid = $this->uid;
                    $website_id = $this->website_id;
                    $buy_num = $this->getActivityOrderSku($uid, $sku_id, $website_id, $new_sku_list[$v->sku_id]['seckill_id']);
                    $sec_sku_info_list = $sec_server->getSeckillSkuInfo(['seckill_id' => $seckill_id, 'sku_id' => $v->sku_id]);
                    $temp_sku['stock'] = $sec_sku_info_list->remain_num;
                    $temp_sku['max_buy'] = (($sec_sku_info_list->seckill_limit_buy - $buy_num) < 0) ? 0 : (($sec_sku_info_list->seckill_limit_buy - $buy_num) > $temp_sku['stock'] ? $temp_sku['stock'] : $sec_sku_info_list->seckill_limit_buy - $buy_num);
                    $new_sku_list[$v->sku_id]['num'] = $new_sku_list[$v->sku_id]['num'] > $temp_sku['max_buy'] ? $temp_sku['max_buy'] : $new_sku_list[$v->sku_id]['num'];
                    $temp_sku['price'] = $sec_sku_info_list->seckill_price;
                    $temp_sku['member_price'] = $sec_sku_info_list->seckill_price;
                    $temp_sku['discount_price'] = $sec_sku_info_list->seckill_price;
                }
            } elseif ((!empty($group_id) || !empty($record_id))) {
                if (!$un_order) {
                    if (!$is_group) {
                        return ['code' => -2, 'message' => '拼团活动已结束或已关闭'];
                    }
                    $group_sku_info = $group_server->getGroupSkuInfo(['sku_id' => $v->sku_id, 'goods_id' => $v->goods_id, 'group_id' => $group_id]);
                    $uid = $this->uid;
                    $website_id = $this->website_id;
                    $buy_num = $goods_service->getActivityOrderSkuForGroup($uid, $v->sku_id, $website_id, $group_id);
                    $temp_sku['price'] = $group_sku_info->group_price;
                    $temp_sku['max_buy'] = $group_sku_info->group_limit_buy - $buy_num; // 限购数量
                    if ($temp_sku['max_buy'] < 0) {
                        $temp_sku['max_buy'] = 0;
                    }
                    $temp_sku['member_price'] = $group_sku_info->group_price;
                    $temp_sku['discount_price'] = $group_sku_info->group_price;
                    $temp_sku['stock'] = $v->stock;
                }
            } elseif (getAddons('presell', $this->website_id, $this->instance_id) && !empty($presell_id) && !$un_order) {
                $temp_sku['presell_id'] = $presell_id;
            } elseif (!empty($bargain_id) && getAddons('bargain', $this->website_id, $this->instance_id) && !$un_order) {//砍价活动
                $bargain_server = new Bargain();
                $condition_bargain['bargain_id'] = $bargain_id;
                $condition_bargain['website_id'] = $this->website_id;
                $sku_id = $v->sku_id;
                $uid = $this->uid;
                $website_id = $this->website_id;
                $is_bargain = $bargain_server->isBargain($condition_bargain, $uid);
                if ($is_bargain && !$un_order) {
                    $orderService = new orderServer();
                    $buy_num = $orderService->getActivityOrderSkuNum($uid, $sku_id, $website_id, 3, $bargain_id);
                    $bargain_stock = $is_bargain['bargain_stock'];
                    $max_buy = $is_bargain['limit_buy'] - $buy_num;
                    $temp_sku['max_buy'] = ($max_buy > 0) ? ($max_buy > $bargain_stock ? $bargain_stock : $max_buy) : 0; // 限购数量
                    $temp_sku['price'] = $is_bargain['my_bargain']['now_bargain_money'];
                    $temp_sku['discount_price'] = $is_bargain['my_bargain']['now_bargain_money'];
                    $temp_sku['stock'] = $bargain_stock;
                    $temp_sku['bargain_id'] = $bargain_id;
                } else {
                    return ['code' => -2, 'message' => '砍价活动已结束或已关闭'];
                }
            } else {
                //普通商品
                if ($v->stock <= 0 && empty($new_sku_list[$v->sku_id]['seckill_id']) && empty($channel_id)) {
//                    if (!empty($new_sku_list[$v->sku_id]['cart_id'])) {
//                        $cart_model->destroy($new_sku_list[$v->sku_id]['cart_id']);
//                    }
                    return ['code' => -2, 'message' => $v->goods->goods_name . '商品库存不足' . PHP_EOL];
                }
                if ($v->goods->state != 1) {
//                    if (!empty($new_sku_list[$v->sku_id]['cart_id'])) {
//                        $cart_model->destroy($new_sku_list[$v->sku_id]['cart_id']);
//                    }
                    return ['code' => -2, 'message' => $v->goods->goods_name . '商品为不可购买状态' . PHP_EOL];
                }
                if ($v->goods->max_buy != 0 && $v->goods->max_buy < $v->num && empty($presell_id) && empty($channel_id)) {
                    $temp_sku['num'] = $v->goods->max_buy;
                    $msg .= $v->goods->goods_name . '商品该sku规格购买量大于最大购买量，购买数量已更改' . PHP_EOL;
                }
                if ($v->stock < $new_sku_list[$v->sku_id]['num'] && empty($presell_id) && empty($channel_id)) {
                    $temp_sku['num'] = $v->stock;
                    $msg .= $v->goods->goods_name . '商品该sku规格购买量大于剩余库存，购买数量已更改' . PHP_EOL;
                }
//                // todo... by sgw返回max_buy
                $max_buy = $this->getGoodsMaxBuyNums($v['goods_id'], $v['sku_id']);
                $temp_sku['max_buy'] = ($max_buy - $new_sku_list[$v->sku_id]['num']) > 0 ? $max_buy - $new_sku_list[$v->sku_id]['num'] : 0;
                $temp_sku['stock'] = $v->stock;
                if (!empty($channel_id) && getAddons('channel', $this->website_id) && !$un_order) {
                    $sku_id = $v->sku_id;
                    $channel_sku_mdl = new VslChannelGoodsSkuModel();
                    $channel_cond['channel_id'] = $channel_id;
                    $channel_cond['sku_id'] = $sku_id;
                    $channel_cond['website_id'] = $this->website_id;
                    $channel_stock = $channel_sku_mdl->getInfo($channel_cond, 'stock')['stock'];
                    $temp_sku['max_buy'] = $channel_stock;
                    $temp_sku['stock'] = $channel_stock;
                    $temp_sku['channel_id'] = $channel_id;
                }
                $temp_sku['price'] = $v->price;   // todo....
                //限时折扣
                $limit_discount_info = getAddons('discount', $this->website_id) ? $discount_service->getPromotionInfo($v->goods_id, $v->goods->shop_id, $v->goods->website_id) : ['discount_num' => 10];
//                if($member_is_label){
//                    $temp_sku['member_price'] = round($v->price * $member_discount);
//                }else{
//                    $temp_sku['member_price'] = round($v->price * $member_discount, 2);
//                }
//                $temp_sku['discount_price'] = round($temp_sku['member_price'] * $limit_discount_info['discount_num'] / 10, 2);
//                p($temp_sku);exit;
                // todo... 会员折扣 by sgw 商品价格直接查询购物车表就行
//                $cart = new VslCartModel();
//                $cart_condition = [
//                    'goods_id' => $v->goods_id,
//                    'website_id' => $this->website_id
//                ];
//                $cartRes = $cart->getInfo($cart_condition, 'price');
//                if ($cartRes) { //会员折扣价
//                    $temp_sku['member_price'] = $cartRes['price'];
//                } else {
//                    $goodsDiscountInfo = $this->getGoodsInfoOfIndependentDiscount($v->goods_id, $v->price);//计算会员折扣价
//                    if ($goodsDiscountInfo) {
//                        $temp_sku['member_price'] = $goodsDiscountInfo['member_price'];
//                    }
//                }
                // todo... 会员折扣 by sgw商品价格计算
                $goodsDiscountInfo = $this->getGoodsInfoOfIndependentDiscount($v->goods_id, $v->price);//计算会员折扣价
                //如果是限时折扣是店铺设置的 需要店铺负责
                // $return_data[$v->goods->shop_id]['shop_member_price'] += $goodsDiscountInfo['shop_member_price'] * $new_sku_list[$v->sku_id]['num'];
                // $return_data[$v->goods->shop_id]['platform_member_price'] += $goodsDiscountInfo['platform_member_price'] * $new_sku_list[$v->sku_id]['num'];
                //会员折扣后的价格
                if ($goodsDiscountInfo) {
                    $temp_sku['member_price'] = $goodsDiscountInfo['member_price'];
                    //如果存在限时折扣 则会员价为原价
                    if($limit_discount_info['discount_id']){
                        $temp_sku['member_price'] = $temp_sku['price'];
                    }else{
                        $return_data[$v->goods->shop_id]['platform_member_price'] += $goodsDiscountInfo['platform_member_price'] * $new_sku_list[$v->sku_id]['num'];
                    }
                }
                //限时折扣处理
                if ($limit_discount_info['integer_type'] == 1) {
                    $temp_sku['discount_price'] = round($temp_sku['member_price'] * $limit_discount_info['discount_num'] / 10);
                } else {
                    $temp_sku['discount_price'] = round($temp_sku['member_price'] * $limit_discount_info['discount_num'] / 10, 2);
                }
                if ($limit_discount_info['discount_type'] == 2) {
                    $temp_sku['discount_price'] = $limit_discount_info['discount_num'];
                }
                if ($limit_discount_info['shop_id'] > 0) {
                    $return_data[$v->goods->shop_id]['shop_member_price'] += ($temp_sku['member_price'] - $temp_sku['discount_price']) * $new_sku_list[$v->sku_id]['num'];
                } else {
                    $return_data[$v->goods->shop_id]['platform_member_price'] += ($temp_sku['member_price'] - $temp_sku['discount_price']) * $new_sku_list[$v->sku_id]['num'];
                }
            }
            $temp_sku['min_buy'] = 1;
            $return_data[$v->goods->shop_id]['shop_id'] = $v->goods->shop_id;
            if (empty($return_data[$v->goods->shop_id]['shop_name'])) {
                if (getAddons('shop', $this->website_id) && $v->goods->shop_id) {
                    $return_data[$v->goods->shop_id]['shop_name'] = $shop->getInfo(['shop_id' => $v->goods->shop_id, 'website_id' => $v->goods->website_id])['shop_name'];
                } else {
                    $return_data[$v->goods->shop_id]['shop_name'] = '自营店';
                }
            }
            $temp_sku['sku_id'] = $v->sku_id;
            $temp_sku['num'] = $new_sku_list[$v->sku_id]['num'];
            $temp_sku['goods_id'] = $v->goods_id;
            $temp_sku['channel_id'] = $v['channel_id'];
            $temp_sku['shop_id'] = $v->goods->shop_id;
            $temp_sku['goods_type'] = $v->goods->goods_type;
            $temp_sku['point_deduction_max'] = $v->goods->point_deduction_max;
            $temp_sku['point_return_max'] = $v->goods->point_return_max;
            $temp_sku['shipping_fee_type'] = $v->goods->shipping_fee_type;
            //暂时取商品的图片，不取规格图
            /*$picture = $order_goods_service->getSkuPictureBySkuId($v);
			$picture_info = $album_picture_model->get($picture == 0 ? $v->goods->picture : $picture);*/
            $picture_info = $album_picture_model->get($v->goods->picture);
            $temp_sku['goods_pic'] = $picture_info ? getApiSrc($picture_info->pic_cover) : '';
            $temp_sku['discount_id'] = $limit_discount_info['discount_id'] ?: '';
            $temp_sku['seckill_id'] = $new_sku_list[$v->sku_id]['seckill_id'] ?: '';
            if (empty($is_bargain) && empty($temp_sku['seckill_id']) && (empty($group_id) && empty($record_id)) && empty($presell_id) && !$un_order) {
                //普通商品进入 、、 各类金额汇总-》总价 等等 待处理
                $promotion_sku_list[$v->goods->shop_id][$v->sku_id] = $temp_sku;
                $return_data[$v->goods->shop_id]['total_amount'] += $temp_sku['discount_price'] * $temp_sku['num'];
                // 用于计算 折扣 类型优惠券的总额
                $return_data[$v->goods->shop_id]['amount_for_coupon_discount'] += $temp_sku['discount_price'] * $temp_sku['num'];
                // 店铺会员优惠总金额   会员折扣不写入优惠金额 -- 待定
                $return_data[$v->goods->shop_id]['member_promotion'] += ($temp_sku['price'] - $temp_sku['member_price']) * $temp_sku['num'];
                // 店铺限时折扣优惠总金额
                $return_data[$v->goods->shop_id]['discount_promotion'] += ($temp_sku['member_price'] - $temp_sku['discount_price']) * $temp_sku['num'];
            } else {
                $return_data[$v->goods->shop_id]['total_amount'] += $temp_sku['price'] * $temp_sku['num'];
                // 将显示的价格全部设置为discount_price
                $temp_sku['discount_price'] = $temp_sku['price'];
                // 店铺会员优惠总金额
                $return_data[$v->goods->shop_id]['member_promotion'] += 0;
                // 店铺限时折扣优惠总金额
                $return_data[$v->goods->shop_id]['discount_promotion'] += 0;
            }
            // 规格
            $spec_info = [];
            if ($v['attr_value_items']) {
                $sku_spec_info = explode(';', $v['attr_value_items']);
                foreach ($sku_spec_info as $k_spec => $v_spec) {
                    $spec_value_id = explode(':', $v_spec)[1];
                    $spec_info[$k_spec] = $order_goods_service->getSpecInfo($spec_value_id, $temp_sku['goods_id']);
                }
            }
            $temp_sku['spec'] = $spec_info;
            //判断是否有传预售ID
            if ($presell_id) {
                $return_data[$presell_shop_id]['presell_info'] = null;
                if (getAddons('presell', $this->website_id, $this->instance_id) && !$un_order) {
                    //从SKUID和预售ID找到相关信息
                    $presell = new PresellService();
                    $sku_id = $sku_list[0]['sku_id'];
                    $info = $presell->get_presell_by_sku($presell_id, $sku_id);
                    if ($info) {
                        //判断当前用户购买了多少件该活动商品
                        $uid = $this->uid;
                        $p_cond['activity_id'] = $presell_id;
                        $p_cond['uid'] = $uid;
                        $p_cond['sku_id'] = $v->sku_id;
                        $p_cond['buy_type'] = 4;
                        $p_cond['website_id'] = $this->website_id;
                        $aosr_mdl = new VslActivityOrderSkuRecordModel();
                        $user_already_buy = $aosr_mdl->getInfo($p_cond, 'num')['num'];
                        $return_data[$presell_shop_id]['presell_info']['maxbuy'] = ($info['maxbuy'] - $user_already_buy) > 0 ? ($info['maxbuy'] - $user_already_buy) : 0;
                        $return_data[$presell_shop_id]['presell_info']['firstmoney'] = $info['firstmoney'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['allmoney'] = $info['allmoney'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['presellnum'] = $info['presellnum'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['vrnum'] = $info['vrnum'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['pay_start_time'] = $info['pay_start_time'] ?: 0;
                        $return_data[$presell_shop_id]['presell_info']['pay_end_time'] = $info['pay_end_time'] ?: 0;
                        $return_data[$presell_shop_id]['total_amount'] = $info['firstmoney'] * $sku_list[0]['num'] ?: 0;
                        $have_buy = $presell->get_presell_sku_num($presell_id, $temp_sku['sku_id']);
                        $return_data[$presell_shop_id]['presell_info']['over_num'] = $info['presellnum'] - $have_buy;  //已购买人数
                        $total_account = $info['firstmoney'] * $new_sku_list[$v->sku_id]['num'];
                        $temp_sku['price'] = $info['firstmoney'];
                    }
                }
            }
            //优惠券
            if (getAddons('coupontype', $this->website_id) && !$un_order) {
                $temp_sku['coupon_id'] = $v->coupon_id;
            }
            $return_data[$v->goods->shop_id]['goods_list'][] = $temp_sku;
            // 下面的满减送和优惠券可能不进去循环，先初始化一些数据
            $return_data[$v->goods->shop_id]['full_cut'] = (object)[];
            $return_data[$v->goods->shop_id]['coupon_list'] = [];
            $return_data[$v->goods->shop_id]['coupon_num'] = 0;
            //卡券核销门店
            if (getAddons('store', $this->website_id, $this->instance_id) && $v['goods']['goods_type'] == 0 && !$un_order) {
                $store = new Store();
                $store_list = $v['goods']['store_list'];
                if (empty($store_list)) {
                    $return_data[$v->goods->shop_id]['store_list'] = [];
                } else {
                    $store_id = explode(',', $store_list); //适用的门店ID
                    $condition = [];
                    $condition['website_id'] = $v['website_id'];
                    $condition['store_id'] = ['IN', $store_id];
                    $lng = input('lng', 0);
                    $lat = input('lat', 0);
                    $place = ['lng' => $lng, 'lat' => $lat];
                    $store_list = $store->storeListForFront(1, 20, $condition, $place);
                    if (empty($store_list)) {
                        $return_data[$v->goods->shop_id]['store_list'] = [];
                    } else {
                        $return_data[$v->goods->shop_id]['store_list'] = $store_list['store_list'];
                    }
                }
            }
        }
        // 满减送
        if (getAddons('fullcut', $this->website_id) && !$un_order) {
            $full_cut_lists = $full_cut_service->getPaymentFullCut($promotion_sku_list); //异常点
            foreach ($full_cut_lists as $kk => $vv) {
                if (empty($vv['man_song_id'])) {
                    unset($full_cut_lists[$kk]);
                }
            }
            $full_cut_limit = [];
            foreach ($full_cut_lists as $shop_id => $full_cut_info) {
                if ($full_cut_info['discount_percent']) {
                    foreach ($full_cut_info['discount_percent'] as $sku_id => $discount_percent) {
                        if (!empty($full_cut_info) && $full_cut_info['discount'] > 0) {
                            // 计算优惠券需要的信息
                            $promotion_sku_list[$shop_id][$sku_id]['full_cut_amount'] = $full_cut_info['discount'];
                            $promotion_sku_list[$shop_id][$sku_id]['full_cut_percent'] = $full_cut_info['discount_percent'][$sku_id];
                            $promotion_sku_list[$shop_id][$sku_id]['full_cut_percent_amount'] = round($full_cut_info['discount_percent'][$sku_id] * $full_cut_info['discount'], 2);
                        }
                    }
                }
                $return_data[$shop_id]['total_amount'] -= $full_cut_info['discount'];
                $return_data[$shop_id]['amount_for_coupon_discount'] -= $full_cut_info['discount'];
                $full_cut_limit[$shop_id] = $full_cut_info['goods_limit'];
                unset($full_cut_info['discount_percent']);
                $return_data[$shop_id]['full_cut'] = $full_cut_info ?: (object)[];
                if (!empty($presell_id)) {
                    $return_data[$shop_id]['full_cut'] = (object)[];
                }
            }
            if (empty($presell_id)) {
                $full_cut_compute = [];
                foreach ($promotion_sku_list as $k => $v) {
                    foreach ($v as $k2 => $v2) {
                        $full_cut_compute[$k2]['full_cut_amount'] = $v2['full_cut_amount'];
                        $full_cut_compute[$k2]['full_cut_percent'] = $v2['full_cut_percent'];
                        $full_cut_compute[$k2]['full_cut_percent_amount'] = $v2['full_cut_percent_amount'];
                    }
                }
                foreach ($return_data as $k => $v) {
                    $full_cut_goods = [];
                    if (!empty($full_cut_limit[$k])) {
                        foreach ($full_cut_limit[$k] as $k3 => $v3) {
                            $full_cut_goods[$v3] = 1;
                        }
                        if ($v['goods_list']) {
                            foreach ($v['goods_list'] as $k2 => $v2) {
                                if ($full_cut_goods[$v2['goods_id']] == 1) {
                                    $return_data[$k]['goods_list'][$k2]['full_cut_sku_amount'] = $full_cut_compute[$v2['sku_id']]['full_cut_amount'];
                                    $return_data[$k]['goods_list'][$k2]['full_cut_sku_percent'] = $full_cut_compute[$v2['sku_id']]['full_cut_percent'];
                                    $return_data[$k]['goods_list'][$k2]['full_cut_sku_percent_amount'] = $full_cut_compute[$v2['sku_id']]['full_cut_percent_amount'];
                                }
                            }
                        }
                    } else {
                        if ($v['goods_list']) {
                            foreach ($v['goods_list'] as $k2 => $v2) {
                                $return_data[$k]['goods_list'][$k2]['full_cut_sku_amount'] = $full_cut_compute[$v2['sku_id']]['full_cut_amount'];
                                $return_data[$k]['goods_list'][$k2]['full_cut_sku_percent'] = $full_cut_compute[$v2['sku_id']]['full_cut_percent'];
                                $return_data[$k]['goods_list'][$k2]['full_cut_sku_percent_amount'] = $full_cut_compute[$v2['sku_id']]['full_cut_percent_amount'];
                            }
                        }
                    }
                }
            }
        }
        //end 满减送
        // 优惠券
        if (getAddons('coupontype', $this->website_id) && !$un_order) {
            $coupon_service = new Coupon();
            $coupon_list = $coupon_service->getMemberCouponListNew($promotion_sku_list); // 获取优惠券
            $coupon_compute = [];
            foreach ($coupon_list as $shop_id => $v) {
                foreach ($v['coupon_info'] as $coupon_id => $c) {
                    $temp_coupon = [];
                    $temp_coupon['coupon_id'] = $c['coupon_id'];
                    $temp_coupon['coupon_name'] = $c['coupon_type']['coupon_name'];
                    $temp_coupon['coupon_genre'] = $c['coupon_type']['coupon_genre'];
                    $temp_coupon['shop_range_type'] = $c['coupon_type']['shop_range_type'];
                    $temp_coupon['at_least'] = $c['coupon_type']['at_least'];
                    $temp_coupon['money'] = $c['coupon_type']['money'];
                    $temp_coupon['discount'] = $c['coupon_type']['discount'];
                    $temp_coupon['start_time'] = $c['coupon_type']['start_time'];
                    $temp_coupon['end_time'] = $c['coupon_type']['end_time'];
                    $return_data[$shop_id]['coupon_list'][] = $temp_coupon;
                }
                $return_data[$shop_id]['coupon_num'] = count($v['coupon_info']);
                $coupon_compute[$shop_id] = $v['sku_percent'];
                //有预售则清空
                if (!empty($presell_id)) {
                    $return_data[$shop_id]['coupon_list'][] = [];
                    $return_data[$shop_id]['coupon_num'] = 0;
                }
            }
            if (empty($presell_id)) {
                foreach ($return_data as $k => $v) {
                    $return_data[$k]['coupon_promotion'] = 0;
                    if ($v['goods_list']) {
                        foreach ($v['goods_list'] as $k2 => $v2) {
                            if ($v2['coupon_id'] > 0) {
                                $return_data[$k]['goods_list'][$k2]['coupon_sku_percent'] = $coupon_compute[$k][$v2['coupon_id']][$v2['sku_id']]['coupon_percent'];
                                $return_data[$k]['goods_list'][$k2]['coupon_sku_percent_amount'] = $coupon_compute[$k][$v2['coupon_id']][$v2['sku_id']]['coupon_percent_amount'];
                                $return_data[$k]['coupon_promotion'] += $return_data[$k]['goods_list'][$k2]['coupon_sku_percent_amount'];
                            }
                        }
                        $return_data[$k]['total_amount'] -= $return_data[$k]['coupon_promotion'];
                    }
                }
            }
        }
        foreach ($return_data as &$v) {
            if ($total_account != 0) {
                $v['total_amount'] = $total_account;
            } else {
                $v['total_amount'] = ($v['total_amount'] > 0) ? $v['total_amount'] : 0;
            }
            $v['amount_for_coupon_discount'] = ($v['amount_for_coupon_discount'] > 0) ? $v['amount_for_coupon_discount'] : 0;
        }
        unset($v);
        return $return_data;
    }
    /*
     * 编辑知识付费商品时，读取付费内容列表
     */
    public function getKnowledgePaymentList($goods_id)
    {
        $knowledge_payment_content_model = new VslKnowledgePaymentContentModel();
        $res = $knowledge_payment_content_model->getQuery(['goods_id' => $goods_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id], '*', 'id ASC');
        return $res;
    }
    /*
     * 前端读取付费内容列表
     */
    public function wapGetKnowledgePaymentList($goods_id, $uid)
    {
        if ($uid) {
            $order_model = new VslOrderModel();
            $order_goods_model = new VslOrderGoodsModel();
            //判断当前用户有没有购买此商品
            $condition = [
                'website_id' => $this->website_id,
                'buyer_id' => $uid,
                'goods_id' => $goods_id
            ];
            $order_id = $order_goods_model->getInfo($condition, 'order_id');
            if ($order_id) {
                $order_status = $order_model->getInfo(['order_id' => $order_id['order_id']], 'order_status');
                if ($order_status >= 1) {
                    $is_buy = true;
                } else {
                    $is_buy = false;
                }
            } else {
                $is_buy = false;
            }
        } else {
            $is_buy = false;
        }
        $knowledge_payment_content_model = new VslKnowledgePaymentContentModel();
        $res = $knowledge_payment_content_model->getQuery(['goods_id' => $goods_id, 'website_id' => $this->website_id], '*', 'id ASC');
        foreach ($res as $k => $v) {
            $data = [
                'knowledge_payment_id' => $v['id'],
                'knowledge_payment_name' => $v['name'],
                'knowledge_payment_type' => $v['type'],
                'knowledge_payment_is_see' => $v['is_see'],
            ];
            $konwledge_payment_list[] = $data;
        }
        return [
            'konwledge_payment_list' => $konwledge_payment_list,
            'is_buy' => $is_buy
        ];
    }
    /**
     * 前台会员试看或者观看知识付费商品内容
     */
    public function seeKnowledgePayment($knowledge_payment_id, $uid)
    {
        $knowledge_payment_content_model = new VslKnowledgePaymentContentModel();
        $order_model = new VslOrderModel();
        $order_goods_model = new VslOrderGoodsModel();
        $goods_model = new VslGoodsModel();
        $albumPictureModel = new AlbumPictureModel();
        $goods_id = $knowledge_payment_content_model->getInfo(['id' => $knowledge_payment_id], 'goods_id');
        //判断当前用户有没有购买此商品
        $condition = [
            'website_id' => $this->website_id,
            'buyer_id' => $uid,
            'goods_id' => $goods_id['goods_id']
        ];
        $order_id = $order_goods_model->getInfo($condition, 'order_id');
        if ($order_id) {
            $order_status = $order_model->getInfo(['order_id' => $order_id['order_id']], 'order_status');
            if ($order_status['order_status'] >= 1) {
                $is_buy = true;
            } else {
                $is_buy = false;
            }
        } else {
            $is_buy = false;
        }
        //查询当前点击的付费内容
        $list = $knowledge_payment_content_model->getInfo(['id' => $knowledge_payment_id], '*');
        //查询商品信息
        $goods_info = $goods_model->getInfo(['goods_id' => $goods_id['goods_id']], 'goods_name,picture');
        $list['goods_picture'] = $albumPictureModel->getInfo(['pic_id' => $goods_info['picture']], 'pic_cover')['pic_cover'];
        $list['goods_name'] = $goods_info['goods_name'];
        $list['total_count'] = $knowledge_payment_content_model->getCount(['goods_id' => $goods_id['goods_id']]);
        $list['is_buy'] = $is_buy;
        return $list;
    }
    /*
     * 会员中心->我的课程
     */
    public function myCourse($search_text, $page_index, $page_size, $uid)
    {
        $order_model = new VslOrderModel();
        $order_goods_model = new VslOrderGoodsModel();
        $albumPictureModel = new AlbumPictureModel();
        $knowledge_payment_content_model = new VslKnowledgePaymentContentModel();
        //查询此用户所有购买过的知识付费商品
        $condition = [
            'website_id' => $this->website_id,
            'buyer_id' => $uid,
            'goods_type' => 4
        ];
        if ($search_text) {
            $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
        }
        $order_info = $order_goods_model->pageQuery($page_index, $page_size, $condition, 'order_id DESC', 'order_id,goods_id,goods_name,goods_picture');
        if ($order_info['data']) {
            foreach ($order_info['data'] as $k => $v) {
                $order_status = $order_model->getInfo(['order_id' => $v['order_id']], 'order_status');
                if ($order_status['order_status'] == 4) {
                    $data['goods_id'] = $v['goods_id'];
                    $data['goods_name'] = $v['goods_name'];
                    $data['goods_picture'] = $albumPictureModel->getInfo(['pic_id' => $v['goods_picture']], 'pic_cover')['pic_cover'];
                    $data['total_count'] = $knowledge_payment_content_model->getCount(['goods_id' => $v['goods_id']]);
                    $knowledge_payment_list[] = $data;
                }
            }
        }
        if($knowledge_payment_list) {
            return [
                'knowledge_payment_list' => $knowledge_payment_list,
                'page_count' => $order_info['page_count'],
                'total_count' => $order_info['total_count'],
            ];
        } else {
            return [
                'knowledge_payment_list' => [],
                'page_count' => 0,
                'total_count' => 0,
            ];
        }
    }
    /*
     * 去学习
     */
    public function goLearn($goods_id)
    {
        $knowledge_payment_content_model = new VslKnowledgePaymentContentModel();
        $goods_model = new VslGoodsModel();
        $albumPictureModel = new AlbumPictureModel();
        //默认取第一条数据
        $list = $knowledge_payment_content_model->getQuery(['goods_id' => $goods_id, 'website_id' => $this->website_id], '*', 'id ASC')[0];
        $goods_info = $goods_model->getInfo(['goods_id' => $goods_id], 'goods_name,picture');
        $list['goods_picture'] = $albumPictureModel->getInfo(['pic_id' => $goods_info['picture']], 'pic_cover')['pic_cover'];
        $list['goods_name'] = $goods_info['goods_name'];
        $list['total_count'] = $knowledge_payment_content_model->getCount(['goods_id' => $goods_id]);
        $list['is_buy'] = true;
        return $list;
    }
    /**
     * 新的获取商品基本信息以及对应的活动信息的接口，后面多个地方可通用
     */
    public function getGoodsBasicInfo($goods_id,$store_id)
    {
        //判断后台配置的是哪种库存方式 1:门店独立库存 2:店铺统一库存  默认为1
        $storeServer = new storeServer();
        $stock_type = $storeServer->getStoreSet(0)['stock_type'] ? $storeServer->getStoreSet(0)['stock_type'] : 1;
        if($store_id && $stock_type == 1){
            //门店自提
            $store_goods_model = new VslStoreGoodsModel();
            $store_goods_sku_model = new VslStoreGoodsSkuModel();
            $store_goods_condition = [
                'goods_id' =>  $goods_id,
                'store_id' =>  $store_id,
                'website_id' => $this->website_id
            ];
            $goods_info = $store_goods_model->getInfo($store_goods_condition,'*');
            $goods_sku_detail = $store_goods_sku_model->getQuery($store_goods_condition, '*', '');
        }else{
            //快递配送
            $goods_model = new VslGoodsModel();
            $goods_sku_model = new VslGoodsSkuModel();
            $goods_condition = [
                'goods_id' =>  $goods_id,
            ];
            $goods_info = $goods_model->getInfo($goods_condition,'*');
            $goods_sku_detail = $goods_sku_model->getQuery($goods_condition, '*', '');
        }
        //sku信息
        $goods_model = new VslGoodsModel();
        $goods_spec = new VslGoodsSpecModel();
        $goods_detail = $goods_model->getInfo(['goods_id' => $goods_info['goods_id']], '*');
        $spec_list = json_decode($goods_detail['goods_spec_format'], true);
        $album = new Album();
        if (!empty($spec_list)) {
            foreach ($spec_list as $k1 => $v1) {
                $sort = $goods_spec->getInfo([
                    "spec_id" => $v1['spec_id']
                ], "sort");
                $spec_list[$k1]['sort'] = 0;
                if (!empty($sort)) {
                    $spec_list[$k1]['sort'] = $sort['sort'];
                }
                foreach ($v1["value"] as $m => $t) {
                    if (empty($v1['show_type'])) {
                        $spec_list[$k1]['show_type'] = 1;
                    }
                    // 查询SKU规格主图，没有返回0
                    $spec_list[$k1]["value"][$m]["picture"] = $this->getGoodsSkuPictureBySpecId($goods_id, $spec_list[$k1]["value"][$m]['spec_id'], $spec_list[$k1]["value"][$m]['spec_value_id']);
                    if (is_numeric($t["spec_value_data"]) && $v1["show_type"] == 3) {
                        $picture_detail = $album->getAlubmPictureDetail([
                            "pic_id" => $t["spec_value_data"]
                        ]);
                        if (!empty($picture_detail)) {
                            $spec_list[$k1]["value"][$m]["spec_value_data_src"] = __IMG($picture_detail["pic_cover_micro"]);
                        } else {
                            $spec_list[$k1]["value"][$m]["spec_value_data_src"] = null;
                        }
                        $spec_list[$k1]["value"][$m]["spec_value_data"] = $this->getGoodsSkuPictureBySpecId($goods_id, $spec_list[$k1]["value"][$m]['spec_id'], $spec_list[$k1]["value"][$m]['spec_value_id']);
                    } else {
                        $spec_list[$k1]["value"][$m]["spec_value_data_src"] = null;
                    }
                }
            }
            // 排序字段
            $sort = array(
                'field' => 'sort'
            );
            $arrSort = array();
            foreach ($spec_list as $uniqid => $row) {
                foreach ($row as $key => $value) {
                    $arrSort[$key][$uniqid] = $value;
                }
            }
            array_multisort($arrSort[$sort['field']], SORT_ASC, $spec_list);
        }
        $goods_info['spec_list'] = $spec_list;
        //关联相册表，查出商品对应的图片
        $albumPictureModel = new AlbumPictureModel();
        $order = "instr('," . $goods_detail['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
        $goods_img_list = $albumPictureModel->getQuery([
            'pic_id' => [
                "in",
                $goods_detail['img_id_array']
            ]
        ], '*', $order);
        foreach ($goods_img_list as $k => $pic) {
            $goods_info['goods_images'][] = getApiSrc($pic['pic_cover']);
        }
        // 处理图片域名,替换后上传云服务器（图片域名为第三方的）,目的是为了图片域名必须在小程序downloaddomain中
        if (!empty($goods_img_list[0])) {
            $upload_url = $this->modifyImageUrl2AliOss($goods_info['goods_images'][0]);
            $goods_info['goods_image_yun'] = $upload_url;
        }
        //视频
        $goods_info['video'] = '';
        if ($goods_detail['video_id']) {
            $goods_info['video'] = $albumPictureModel->get($goods_detail['video_id']) ? $albumPictureModel->get($goods_detail['video_id'])['pic_cover'] : '';
        }
        //计算会员折扣
        $discount_service = getAddons('discount', $this->website_id) ? new Discount() : '';
        $limit_discount_info = getAddons('discount', $this->website_id) ? $discount_service->getPromotionInfo($goods_id, $this->instance_id, $this->website_id) : ['discount_num' => 10];
        $member_price = $goods_info['price'];
        $member_discount = 1;
        $goods_info['discount_choice'] = 1;
        $goods_info['member_is_label'] = 0;
        $goods_info['is_show_member_price'] = 0;
        $goods_info['price_type'] = 0;
        if ($this->uid) {
            // 查询商品是否有开启会员折扣
            $goodsDiscountInfo = $this->getGoodsInfoOfIndependentDiscount($goods_id, $member_price);
            if ($goodsDiscountInfo) {
                if($goodsDiscountInfo['is_use'] == 1){
                    $goods_info['price_type'] = 1; //会员折扣
                }
                $member_price = $goodsDiscountInfo['member_price'];
                $member_discount = $goodsDiscountInfo['member_discount'];
                $goods_info['discount_choice'] = $goodsDiscountInfo['discount_choice'];
                $goods_info['is_show_member_price'] = $goodsDiscountInfo['is_show_member_price'];
                $goods_info['member_is_label'] = $goodsDiscountInfo['member_is_label'];
            }
        }
        $goods_info['member_price'] = $member_price;
        $goods_info['member_discount'] = $member_discount;
        //处理sku的价格
        foreach ($goods_sku_detail as $k => $goods_sku) {
            $pprice =  $goods_sku_detail[$k]['price'];
            $goods_sku_detail[$k]['member_price'] = $member_price;
            if ($goods_info['discount_choice'] == 2) {
                $goods_info['price_type'] = 1; //会员折扣
                $goods_sku_detail[$k]['price'] = $member_price;
            }
            if ($goods_info['discount_choice'] == 1) {
                $goods_info['price_type'] = 1; //会员折扣
                $goods_sku_detail[$k]['price'] = $pprice * $goodsDiscountInfo['member_discount'];
            }
            if ($limit_discount_info['discount_type'] == 1) {
                $goods_info['price_type'] = 2; //限时折扣
                $goods_sku_detail[$k]['price'] = $pprice * $limit_discount_info['discount_num'] / 10;
            }
            if ($limit_discount_info['discount_type'] == 2) {
                $goods_info['price_type'] = 2; //限时折扣
                $goods_sku_detail[$k]['price'] = $limit_discount_info['discount_num'];
            }
        }
        if($limit_discount_info['discount_num'] == 10){
            $limit_discount_info = (object)[];
        }
        $goods_info['limit_discount_info'] = $limit_discount_info;
        $goods_info['sku_list'] = $goods_sku_detail;
        // 查询商品单品活动信息
        $goods_preference = new GoodsPreference();
        $goods_promotion_info = $goods_preference->getGoodsPromote($goods_id);
        if (!empty($goods_promotion_info)) {
            $goods_discount_info = new VslPromotionDiscountModel();
            $goods_info['promotion_detail'] = $goods_discount_info->getInfo([
                'discount_id' => $goods_info['promote_id']
            ], 'start_time, end_time,discount_name,discount_num');
        }
        // 判断活动内容是否为空
        if (!empty($goods_info['promotion_detail'])) {
            $goods_info['promotion_info'] = $goods_promotion_info;
        } else {
            $goods_info['promotion_info'] = "";
        }
        // 查询商品满减送活动
        $goods_mansong = new GoodsMansong();
        $goods_info['mansong_name'] = $goods_mansong->getGoodsMansongName($goods_id);
        // 查询包邮活动
        $full = new Promotion();
        $baoyou_info = $full->getPromotionFullMail($goods_info['shop_id']);
        if ($baoyou_info['is_open'] == 1) {
            if ($baoyou_info['full_mail_money'] == 0) {
                $goods_info['baoyou_name'] = '全场包邮';
            } else {
                $goods_info['baoyou_name'] = '满' . $baoyou_info['full_mail_money'] . '元包邮';
            }
        } else {
            $goods_info['baoyou_name'] = '';
        }
        // 查询商品的已购数量
        $orderGoods = new VslOrderGoodsModel();
        $num = $orderGoods->getSum([
            "goods_id" => $goods_id,
            "buyer_id" => $this->uid,
            "order_status" => array(
                "neq",
                5
            )
        ], "num");
        $goods_info["purchase_num"] = $num;
        $goods_info["goods_detail"] = $goods_detail;
        return $goods_info;
    }
    /**
     * 购物车编辑规格或数量（新版购物车）
     */
    public function newGetCartList($cart_id, $num, $store_id, $sku_list,$shop_id, &$msg = '')
    {
        $cart = new VslCartModel();
        $cart_lists = $cart::get(['cart_id' => $cart_id], ['goods', 'goods_picture', 'sku']);
        $goods_name = $cart_lists['goods_name'];
        if (mb_strlen($cart_lists['goods']['goods_name']) > 10) {
            $goods_name = mb_substr($cart_lists['goods']['goods_name'], 0, 10) . '...';
        }
        //如果是秒杀商品并且没有结束，则取秒杀价
        if (!$cart_lists['seckill_id'] && getAddons('seckill', $this->website_id, $this->instance_id)) {
            $sec_server = new SeckillServer();
            $sku_id = $cart_lists['sku']['sku_id'];
            $condition_seckill['nsg.sku_id'] = $sku_id;
            $seckill_info = $sec_server->isSkuStartSeckill($condition_seckill);
            if ($seckill_info) {
                $cart_lists['seckill_id'] = $seckill_info['seckill_id'];
            }
        }
        if (!empty($cart_lists['seckill_id']) && getAddons('seckill', $this->website_id, $this->instance_id)) {
            $sec_server = new SeckillServer();
            //判断当前秒杀活动的商品是否已经开始并且没有结束
            $condition_seckill['s.website_id'] = $this->website_id;
            $condition_seckill['s.seckill_id'] = $cart_lists['seckill_id'];
            $condition_seckill['nsg.sku_id'] = $cart_lists['sku']['sku_id'];
            $is_seckill = $sec_server->isSeckillGoods($condition_seckill);
            if (!$is_seckill) {
                $cart_lists['price'] = $cart_lists['sku']['price'];
                $cart_lists['seckill_id'] = 0;
                $this->cartAdjustSec($cart_id, 0);
                $msg .= $goods_name . "商品该sku规格秒杀活动已经结束，已更改为正常状态商品价格" . PHP_EOL;
            } else {
                //取该商品该用户购买了多少
                $sku_id = $cart_lists['sku']['sku_id'];
                $uid = $this->uid;
                $website_id = $this->website_id;
                $buy_num = $this->getActivityOrderSku($uid, $sku_id, $website_id, $cart_lists['seckill_id']);
                $sec_sku_info_list = $sec_server->getSeckillSkuInfo(['seckill_id' => $cart_lists['seckill_id'], 'sku_id' => $cart_lists['sku']['sku_id']]);
                $cart_lists['stock'] = $sec_sku_info_list['remain_num'];
                $cart_lists['max_buy'] = (($sec_sku_info_list['seckill_limit_buy'] - $buy_num) < 0) ? 0 : $sec_sku_info_list['seckill_limit_buy'] - $buy_num;
                $cart_lists['max_buy'] = $cart_lists['max_buy'] > $cart_lists['stock'] ? $cart_lists['stock'] : $cart_lists['max_buy'];
                $cart_lists['price'] = $sec_sku_info_list['seckill_price'];
            }
        } else {
            $cart_lists['price'] = $cart_lists['sku']['price'];
        }

        if (getAddons('presell', $this->website_id, $this->instance_id)) {
            $presell = new Presell();
            $is_presell = $presell->getIsInPresell($cart_lists['sku']['goods_id']);
            if($is_presell){
                $can_buy = $presell->getMeCanBuy($is_presell['presell_id'], $cart_lists['sku']['sku_id']);
                $presell_goods = new VslPresellGoodsModel();
                $presell_sku_info = $presell_goods->getInfo(['sku_id' => $cart_lists['sku']['sku_id'], 'presell_id' => $is_presell['presell_id']]);
                $cart_lists['stock'] = $presell_sku_info['presell_num'];
                $cart_lists['max_buy'] = $can_buy > $cart_lists['stock'] ? $cart_lists['stock'] : $can_buy;
                $cart_lists['price'] = $is_presell['all_money'];
            }
        }

        if($is_seckill){
            $max_buy = $cart_lists['max_buy'];
            $stock = $cart_lists['max_buy'];
        }elseif($is_presell){
            $max_buy = $cart_lists['max_buy'];
            $stock = $cart_lists['max_buy'];
        }else{
            $max_buy = 0;
            $stock = $cart_lists['sku']['stock'];
        }

        //判断后台配置的是哪种库存方式 1:门店独立库存 2:店铺统一库存  默认为1
        $storeServer = new storeServer();
        $stock_type = $storeServer->getStoreSet(0)['stock_type'] ? $storeServer->getStoreSet(0)['stock_type'] : 1;
        if(empty($num)){
            //只改规格
            if($store_id && $stock_type == 1) {
                $store_goods_sku_model = new VslStoreGoodsSkuModel();
                $cart_lists['sku'] = $store_goods_sku_model->getInfo(['sku_id'=>$sku_list['sku_id'],'store_id'=>$store_id],'');
            }else{
                $goods_model =  new VslGoodsSkuModel();
                $cart_lists['sku'] = $goods_model->getInfo(['sku_id'=>$sku_list['sku_id']],'');
            }

            if ($stock <= 0) {
                $msg .= $goods_name . "商品该sku规格库存不足" . PHP_EOL;
            }
            if ($max_buy != 0 && $max_buy < $sku_list['num']) {
                $sku_list['num'] = $max_buy;
                $msg .= $goods_name . "商品该sku规格购买量大于最大购买量，购买数量已更改" . PHP_EOL;
            }
            //执行修改
            if($this->uid){
                $goods_model =  new VslGoodsSkuModel();
                //计算此规格的会员价
                $platform_sku_info = $goods_model->getInfo(['sku_id'=>$sku_list['sku_id']],'goods_id,price');
                $goodsDiscountInfo = $this->getGoodsInfoOfIndependentDiscount($platform_sku_info['goods_id'], $platform_sku_info['price']);//计算会员折扣价
                if ($goodsDiscountInfo) {
                    $update_data['price'] = $goodsDiscountInfo['member_price'];
                }else{
                    $update_data['price'] = $platform_sku_info['price'];
                }
                $update_data['sku_id'] = $sku_list['sku_id'];
                $update_data['sku_name'] = $cart_lists['sku']['sku_name'];
                $update_data['num'] = $sku_list['num'];
                $cart->save($update_data,['cart_id'=>$cart_id,'website_id'=>$this->website_id]);
            }
            $cart_lists['sku_id'] = $sku_list['sku_id'];
        }elseif(empty($sku_list)){
            //只改数量
            if($store_id && $stock_type == 1){
                $store_goods_sku_model = new VslStoreGoodsSkuModel();
                $cart_lists['sku'] = $store_goods_sku_model->getInfo(['sku_id'=>$cart_lists['sku_id'],'store_id'=>$store_id],'');
            }else{
                $goods_model =  new VslGoodsSkuModel();
                $cart_lists['sku'] = $goods_model->getInfo(['sku_id'=>$cart_lists['sku_id']],'');
            }
            if (empty($cart_lists['sku'])) {
                $cart->destroy(['cart_id' => $cart_lists['cart_id']]);
                $msg .= $goods_name . "商品该sku规格不存在，已移除" . PHP_EOL;
            }
            if ($stock <= 0) {
                $msg .= $goods_name . "商品该sku规格库存不足" . PHP_EOL;
            }
            if ($cart_lists['goods']['state'] != 1) {
                $msg .= $goods_name . "商品该sku规格已下架" . PHP_EOL;
            }
            if ($max_buy != 0 && $max_buy < $num) {
                $num = $max_buy;
                $this->cartAdjustNum($cart_id, $num);
                $msg .= $goods_name . "商品该sku规格购买量大于最大购买量，购买数量已更改" . PHP_EOL;
            }
            //快递配送,判断此用户有没有上级渠道商，如果有，库存显示平台库存+直属上级渠道商的库存
            $new_stock = 0;
            if(getAddons('channel',$this->website_id,0)) {
                if(empty($store_id)) {
                    $member_model = new VslMemberModel();
                    $referee_id = $member_model->Query(['uid'=>$this->uid,'website_id'=>$this->website_id],'referee_id')[0];
                    if($referee_id) {//如果有上级，判断是不是渠道商
                        $channel_model = new VslChannelModel();
                        $is_channel = $channel_model->Query(['uid'=>$referee_id,'website_id'=>$this->website_id],'channel_id')[0];
                        if($is_channel) {//如果上级是渠道商，判断上级渠道商有没有采购过这个商品
                            $channel_sku_mdl = new VslChannelGoodsSkuModel();
                            $channel_cond['channel_id'] = $is_channel;
                            $channel_cond['sku_id'] = $cart_lists['sku_id'];
                            $channel_cond['website_id'] = $this->website_id;
                            $channel_stock = $channel_sku_mdl->getInfo($channel_cond, 'stock')['stock'];
                            $new_stock = $cart_lists['sku']['stock'] + $channel_stock;
                        }
                    }
                }
            }
            if($new_stock) {
                if($num >= $new_stock){
                    $this->cartAdjustNum($cart_id,$new_stock);
                    $msg .= $goods_name . "商品该sku规格购买量大于库存，购买数量已更改" . PHP_EOL;
                }
            }else{
                if($num >= $stock){
                    $this->cartAdjustNum($cart_id, $cart_lists['sku']['stock']);
                    $msg .= $goods_name . "商品该sku规格购买量大于库存，购买数量已更改" . PHP_EOL;
                }
            }
            //执行修改
            if(empty($msg)){
                $this->cartAdjustNum($cart_id, $num);
            }
        }
        unset($cart_lists['goods'], $cart_lists['sku'], $cart_lists['goods_picture']);
        return $cart_lists;
    }
    
    /*
     * 改变商品规格存储,即便规格值被删除,sku仍然能够从商品使用的规格获取到相应规格值
     */
    private function changeSpec($specArray = []){
        if(!$specArray){
            return [];
        }
        $newArray = [];
        foreach($specArray as $val){
            
            if(!$val['value'] || !is_array($val['value'])){
                continue;
            }
            foreach($val['value'] as $v){
                $newArray[$v['spec_value_id']]['name'] = $v['spec_value_name'];
            }
            unset($v);
        }
        unset($val);
        return $newArray;
    }
    /**
     * 返回商品分类
     * @param $good_id int [商品id]
     * @return $categoryName string [分类拼接字串]
     */
    public function getGoodsCategoryNameByGoodId($good_id)
    {
        $goodModel = new VslGoodsModel();
        $goodsRes = $goodModel->getInfo(['goods_id' => $good_id], 'category_id, category_id_1, category_id_2, category_id_3');
        if (!$goodsRes) {
            return;
        }
        $categoryName = $this->getGoodsCategoryName($goodsRes['category_id_1'], $goodsRes['category_id_2'], $goodsRes['category_id_3'] );
        return $categoryName;
    }
    /*
     * 通过goods_id获取sku_id
     */
    public function getSkuIdByGoodsId($goods_id = 0){
        $goodsSkuModel = new VslGoodsSkuModel();
        $sku = $goodsSkuModel->getInfo(['goods_id' => $goods_id], 'sku_id');
        if(!$sku){
            return 0;
        }
        return $sku['sku_id'];
    }
    /*
     * 获取活动的价格
     * **/
    public function getPromotionPrice($promotion_type, $goods_id, $price = 0, $shop_id=0, $website_id = 0)
    {
        if(!$promotion_type || !$goods_id){
            return 0;
        }
        $promotion_price = 0;
        switch($promotion_type){
            case 1://秒杀
                if(getAddons('seckill', $this->website_id)){
                    //获取商品是否在秒杀中
                    $condition_is_seckill['nsg.goods_id'] = $goods_id;
                    $seckill_server = new Seckill();
                    $is_seckill = $seckill_server->isSkuStartSeckill($condition_is_seckill);
                    if($is_seckill){
                        $seckill_id = $is_seckill['seckill_id'];
                        $condition_seckill['ns.website_id'] = $this->website_id;
                        $condition_seckill['ns.seckill_id'] = $seckill_id;
                        $condition_seckill['nsg.goods_id'] = $goods_id;
                        $seckill_sku_price_arrs = $seckill_server->getGoodsSkuArr($condition_seckill, 'nsg.seckill_price');
                        $seckill_price_arr = array_column($seckill_sku_price_arrs, 'seckill_price');
                        $promotion_price = min($seckill_price_arr);
                    }
                }

                break;
            case 2://团购
                if(getAddons('groupshopping', $this->website_id)){
                    $group_server = new GroupShopping();
                    $group_goods_mdl = new VslGroupGoodsModel();
                    $is_group = $group_server->isGroupGoods($goods_id);
                    $group_goods_arr = objToArr($group_goods_mdl->where(['group_id' => $is_group, 'goods_id'=>$goods_id])->select());
                    $group_price_arr = array_column($group_goods_arr, 'group_price');
                    $promotion_price = min($group_price_arr);
                }

                break;
            case 3://预售
                if(getAddons('presell', $this->website_id)){
                    $presell = new Presell();
                    $presell_goods = new VslPresellGoodsModel();
                    //获取当前商品的预售活动
                    $presell_info = $presell->getPresellInfoByGoodsId($goods_id);
                    $where['presell_id'] = $presell_info[0]['presell_id'];
                    $where['goods_id'] = $goods_id;
                    $presell_goods_arr = objToArr($presell_goods->where($where)->select());
                    $presell_price_arr = array_column($presell_goods_arr, 'all_money');
                    $promotion_price = min($presell_price_arr);
                }

                break;
            case 5://限时折扣
                if(getAddons('discount', $this->website_id)){
                    $discount = new Discount();
                    $limit_discount_info = $discount->getPromotionInfo($goods_id, $shop_id, $website_id);
                    if($limit_discount_info['discount_type'] == 1){
                        $promotion_price = $limit_discount_info['discount_num']/10 * $price;
                    }elseif($limit_discount_info['discount_type'] == 2){
                        $promotion_price = $limit_discount_info['discount_num'];
                    }
                    break;
                }
        }
        return $promotion_price;
    }

    public function getMinSkuPrice($goods_id)
    {
        $goods_sku = new VslGoodsSkuModel();
        $goods_sku_list = $goods_sku->where(['goods_id' => $goods_id])->select();
        $goods_sku_price = array_column(objToArr($goods_sku_list), 'price');
        $min_sku_price = min($goods_sku_price);
        return $min_sku_price;
    }

    /**
     * 商品评价信息
     * @param $condition
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getGoodsEvaluateInfo($condition, $field = '*')
    {
        $goodsEvaluate = new VslGoodsEvaluateModel();
        return $goodsEvaluate->getInfo($condition, $field);
    }
}
