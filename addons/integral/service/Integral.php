<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/26 0026
 * Time: 14:41
 */

namespace addons\integral\service;

use addons\coupontype\model\VslCouponModel;
use addons\coupontype\model\VslCouponTypeModel;
use addons\coupontype\server\Coupon;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\giftvoucher\server\GiftVoucher;
use addons\integral\model\VslIntegralCategoryModel;
use addons\integral\model\VslIntegralUserModel;
use addons\shop\model\VslShopModel;
use data\model\AddonsConfigModel;
use data\model\AlbumPictureModel;
use data\model\UserModel;
use data\model\VslAttributeModel;
use data\model\VslGoodsAttributeDeletedModel;
use data\model\VslGoodsAttributeModel;
use data\model\VslGoodsGroupModel;
use data\model\VslGoodsSkuPictureDeleteModel;
use data\model\VslGoodsSkuPictureModel;
use data\model\VslGoodsSpecModel;
use data\model\VslGoodsSpecValueModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberExpressAddressModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderShippingFeeModel;
use data\service\BaseService;
use data\service\Goods;
use data\service\Member;
use data\service\Order;
use data\service\Pay\WeiXinPay;
use data\service\promotion\GoodsExpress;
use data\service\promotion\GoodsPreference;
use data\service\UnifyPay;
use phpDocumentor\Reflection\Types\Object;
use think\Cookie;
use think\Db;
use addons\integral\model\VslIntegralGoodsAttributeDeletedModel;
use addons\integral\model\VslIntegralGoodsAttributeModel;
use addons\integral\model\VslIntegralGoodsDeletedModel;
use addons\integral\model\VslIntegralGoodsModel;
use addons\integral\model\VslIntegralGoodsSkuDeletedModel;
use addons\integral\model\VslIntegralGoodsSkuModel;
use addons\integral\model\VslIntegralGoodsSkuPictureDeletedModel;
use addons\integral\model\VslIntegralGoodsSkuPictureModel;
use data\service\Order\Order as OrderBusiness;

class Integral extends BaseService
{
    public $addons_config_module;
    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
        $this->integral_cate_mdl = new VslIntegralCategoryModel();
    }
    //获取分类列表
    public function getIntegralCategoryList($condition)
    {
        $integral_category_list = $this->integral_cate_mdl->where($condition)->order('sort DESC')->select();
        return $integral_category_list;
    }
    /*
     * 修改积分分类排序
     * **/
    public function updateIntegralCategorySort($category_id, $sort_val)
    {
        $res['sort'] = $sort_val;
        $bool = $this->integral_cate_mdl->where(['integral_category_id'=>$category_id])->update($res);
        return $bool;
    }

    /**
     * 获取积分商品的分类列表
     * @param int $page_index
     * @param int $page_size
     * @param array $condition
     * @param string $order
     * @param string $field
     *
     * @return array
     */
    public function integralCategory($page_index = 1, $page_size = 0, array $condition = [], $order = 'sort DESC', $field = '*')
    {
        $list = $this->integral_cate_mdl->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }

    /*
    * (non-PHPdoc)
    * @see \data\api\IGoods::getGoodsList()
    */
    public function getIntegralGoodsList($page_index = 1, $page_size = 0, $condition = '', $order = 'ng.sort desc,ng.create_time desc')
    {
        $goods_view = new VslIntegralGoodsModel();
//        $goods_view = new VslGoodsViewModel();
        $list = $goods_view->getGoodsViewList($page_index, $page_size, $condition, $order);
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
                // 查询商品单品活动信息
//                $goods_preference = new GoodsPreference();
//                $goods_promotion_info = $goods_preference->getGoodsPromote($v['goods_id']);
//                $list["data"][$k]['promotion_info'] = $goods_promotion_info;

                // 查询商品标签
                $vsl_goods_group = new VslGoodsGroupModel();
                $group_id = 0;
                $group_name = "";
                if (!empty($v['group_id_array'])) {
                    $group_id_array = explode(",", $v['group_id_array']);
                    $group_id = $group_id_array[count($group_id_array) - 1];
                    $group_info = $vsl_goods_group->getInfo([
                        "group_id" => $group_id
                    ], "group_name");
                    if (!empty($group_info)) {
                        $group_name = $group_info['group_name'];
                    }
                }
                $list["data"][$k]['group_name'] = $group_name;
            }
        }
        return $list;

        // TODO Auto-generated method stub
    }

    /**
     * 修改积分商城商品名称或促销语
     */
    public function updateIntegralGoodsNameOrIntroduction($goods_id, $up_type, $up_content)
    {
        $integral_goods_mdl = new VslIntegralGoodsModel();
        $condition = array(
            "goods_id" => $goods_id,
            "website_id" => $this->website_id
        );
        if ($up_type == "goods_name") {
            return $integral_goods_mdl->save([
                "goods_name" => $up_content
            ], $condition);
        } elseif ($up_type == "price") {
            $goods_sku = new VslIntegralGoodsSkuModel();
            $res = $goods_sku->save([
                "price" => $up_content
            ], $condition);
            if (!$res) {
                return -1;
            }
            return $integral_goods_mdl->save([
                "price" => $up_content
            ], $condition);
        } elseif ($up_type == "market_price") {
            $goods_sku = new VslIntegralGoodsSkuModel();
            $res = $goods_sku->save([
                "market_price" => $up_content
            ], $condition);
            if (!$res) {
                return -1;
            }
            return $integral_goods_mdl->save([
                "market_price" => $up_content
            ], $condition);
        } elseif ($up_type == "stock") {
            $goods_sku = new VslIntegralGoodsSkuModel();
            $res = $goods_sku->save([
                "stock" => $up_content
            ], $condition);
            if (!$res) {
                return -1;
            }
            return $integral_goods_mdl->save([
                "stock" => $up_content
            ], $condition);
        } elseif ($up_type == 'short_name'){
            return $integral_goods_mdl->save([
                'short_name' => $up_content
            ], $condition);
        }
    }

    /*
    * (non-PHPdoc)
    * @see \data\api\IGoods::getGoodsCount()
    */
    public function getIntegralGoodsCount($condition)
    {
        $integral_goods_mdl = new VslIntegralGoodsModel();
        $count = $integral_goods_mdl->where($condition)->count();
        return $count;

        // TODO Auto-generated method stub
    }

    /*
     * 修改积分商城
     */
    public function modifyIntegralGoodsOnline($condition)
    {
        $integral_goods_mdl = new VslIntegralGoodsModel();
        $data = array(
            "state" => 1,
            'update_time' => time()
        );
        $result = $integral_goods_mdl->save($data, "goods_id  in($condition)");
        if ($result > 0) {
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    /**
     * 商品删除以前 将商品挪到 回收站中
     *
     * @param unknown $goods_ids
     */
    private function addIntegralGoodsDeleted($goods_ids)
    {
        $integral_goods_mdl = new VslIntegralGoodsModel();
        $integral_goods_mdl->startTrans();
        try {
            $goods_id_array = explode(',', $goods_ids);
            foreach ($goods_id_array as $k => $v) {
                // 得到商品的信息 备份商品
                $goods_info = $integral_goods_mdl->get($v);
                $goods_delete_model = new VslIntegralGoodsDeletedModel();
                $goods_info = json_decode(json_encode($goods_info), true);
                $goods_delete_obj = $goods_delete_model->getInfo([
                    "goods_id" => $v
                ]);
                if (empty($goods_delete_obj)) {
                    $goods_info["update_time"] = time();
                    $id = $goods_delete_model->save($goods_info);
                    // 商品的sku 信息备份
                    $goods_sku_model = new VslIntegralGoodsSkuModel();
                    $goods_sku_list = $goods_sku_model->getQuery([
                        "goods_id" => $v
                    ], "*", "");
                    foreach ($goods_sku_list as $goods_sku_obj) {
                        $goods_sku_deleted_model = new VslIntegralGoodsSkuDeletedModel();
                        $goods_sku_obj = json_decode(json_encode($goods_sku_obj), true);
                        $goods_sku_obj["update_date"] = time();
                        $goods_sku_deleted_model->save($goods_sku_obj);
                    }
                    // 商品的属性 信息备份
                    $goods_attribute_model = new VslIntegralGoodsAttributeModel();
                    $goods_attribute_list = $goods_attribute_model->getQuery([
                        'goods_id' => $v
                    ], "*", "");
                    foreach ($goods_attribute_list as $goods_attribute_obj) {
                        $goods_attribute_delete_model = new VslGoodsAttributeDeletedModel();
                        $goods_attribute_obj = json_decode(json_encode($goods_attribute_obj), true);
                        $goods_attribute_delete_model->save($goods_attribute_obj);
                    }
                    // 商品的sku图片备份
                    $goods_sku_picture = new VslIntegralGoodsSkuPictureModel();
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
            $integral_goods_mdl->commit();
            return 1;
        } catch (\Exception $e) {
            $integral_goods_mdl->rollback();
            return $e->getMessage();
        }
    }

    /*
   * (non-PHPdoc)
   * @see \data\api\IGoods::getGoodsDetail()
   */
    public function getIntegralGoodsDetail($goods_id)
    {
        // 查询商品主表
        $goods_service = new Goods();
        $goods = new VslIntegralGoodsModel();
        $goods_detail = $goods->get(['website_id' => $this->website_id, 'goods_id' => $goods_id]);
        if ($goods_detail == null) {
            return null;
        }
        $goods_preference = new GoodsPreference();
        if (!empty($this->uid)) {
            $member_discount = $goods_preference->getMemberLevelDiscount($this->uid);
        } else {
            $member_discount = 1;
        }
        // 查询商品会员价
        if ($member_discount == 1) {
            $goods_detail['is_show_member_price'] = 0;
        } else {
            $goods_detail['is_show_member_price'] = 1;
        }
        $member_price = $member_discount * $goods_detail['price'];
        $goods_detail['member_price'] = $member_price;

        // sku多图数据
        $sku_picture_list = $this->getIntegralGoodsSkuPicture($goods_id);
//        p($sku_picture_list);exit;
        $goods_detail["sku_picture_list"] = $sku_picture_list;

        // 查询商品分组表
        $goods_group = new VslGoodsGroupModel();
        $goods_group_list = $goods_group->all($goods_detail['group_id_array']);
        $goods_detail['goods_group_list'] = $goods_group_list;
        // 查询商品sku表
        $goods_sku = new VslIntegralGoodsSkuModel();
        $goods_sku_detail = $goods_sku->where('goods_id=' . $goods_id)->select();

        foreach ($goods_sku_detail as $k => $goods_sku) {
            $goods_sku_detail[$k]['member_price'] = $goods_sku['price'] * $member_discount;
        }

        $goods_spec = new VslGoodsSpecModel();
        $goods_detail['sku_list'] = $goods_sku_detail;
        $spec_list = json_decode($goods_detail['goods_spec_format'], true);
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
                    if (empty($t["spec_show_type"])) {
                        $spec_list[$k]["value"][$m]["spec_show_type"] = 1;
                    }
                    // 查询SKU规格主图，没有返回0
                    $spec_list[$k]["value"][$m]["picture"] = $goods_service->getGoodsSkuPictureBySpecId($goods_id, $spec_list[$k]["value"][$m]['spec_id'], $spec_list[$k]["value"][$m]['spec_value_id']);
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
        if($goods_detail['video_id']){
            $goods_detail['video'] = $goods_img->get($goods_detail['video_id'])?$goods_img->get($goods_detail['video_id'])['pic_cover'] : '';
        }
        // 查询分类名称
        $category_name = $goods_service->getGoodsCategoryName($goods_detail['category_id_1'], $goods_detail['category_id_2'], $goods_detail['category_id_3']);
        $cate_arr = explode(">", $category_name);
        $goods_detail['category_name_1'] = $cate_arr[0] ? trim($cate_arr[0]) : '';
        $goods_detail['category_name_2'] = $cate_arr[1] ? trim($cate_arr[1]) : '';
        $goods_detail['category_name_3'] = $cate_arr[2] ? trim($cate_arr[2]) : '';
        $goods_detail['category_name'] = $category_name;

        // 查询商品类型相关信息
        if ($goods_detail['goods_attribute_id'] > 0 || $goods_detail['goods_attribute_id'] == 0) {
            $integral_goods_attribute_model = new VslIntegralGoodsAttributeModel();
            $list = $integral_goods_attribute_model::all(['goods_id' => $goods_id], ['attribute_value']);
            $goods_attribute_list = [];
            foreach ($list as $v){
                $temp['attr_value_id'] = $v->attribute_value->attr_value_id;
                $temp['attr_value'] = $v->attribute_value->attr_value_name;
                $temp['attr_value_name'] = $v->attr_value_name;

                $goods_attribute_list[] = $temp;
            }

            $goods_detail['goods_attribute_list'] = $goods_attribute_list;
        } else {
            $goods_detail['goods_attribute_list'] = [];
        }

        // 查询商品单品活动信息
        /*$goods_preference = new GoodsPreference();
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
        }*/
        //$goods_express = new GoodsExpress();
        //$goods_detail['shipping_fee_name'] = $goods_express->getGoodsExpressTemplate($goods_id, 1, 1, 1);

        $shop_model = new VslShopModel();
        $shop_name = $shop_model->getInfo(array(
            'shop_id' => $goods_detail['shop_id'],
            'website_id' => $goods_detail['website_id']
        ), 'shop_name');
        $goods_detail['shop_name'] = $shop_name['shop_name'];
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
        return $goods_detail;
        // TODO Auto-generated method stub
    }

    public function goodsAttribute(array $condition, array $with = [])
    {
        $goods_attribute_model = new VslIntegralGoodsAttributeModel();
        $list = $goods_attribute_model::all($condition, $with);
        $return_data = [];
        foreach ($list as $v){
            $temp['attr_value'] = $v->attribute_value->attr_value_name;
            $temp['attr_value_name'] = $v->attr_value_name;

            $return_data[] = $temp;
        }
        return $return_data;
    }

    /**
     * 查询sku多图数据
     *
     * {@inheritdoc}
     *
     * @see \data\api\IGoods::getGoodsSkuPicture()
     */
    public function getIntegralGoodsSkuPicture($goods_id)
    {
        $goods_sku = new VslIntegralGoodsSkuPictureModel();
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

    private function deleteIntegralSkuItem($goods_id, $sku_list_array)
    {
        $sku_item_list_array = array();
        foreach ($sku_list_array as $k => $sku_item_array) {
            $sku_item = explode('¦', $sku_item_array);
            $sku_item_list_array[] = $sku_item[0];
        }
        $goods_sku = new VslIntegralGoodsSkuModel();
        $list = $goods_sku->where('goods_id=' . $goods_id)->select();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (!in_array($v['attr_value_items'], $sku_item_list_array)) {
                    $goods_sku->destroy($v['sku_id']);
                }
            }
        }
    }

    /**
     * 添加修改积分商城商品
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
     *
     * @return \data\model|number
     */
    public function addOrEditIntegralGoods($integral_goods_id, $goods_name, $shopid, $category_id, $category_id_1, $category_id_2, $category_id_3, $supplier_id, $brand_id, $group_id_array, $goods_type, $market_price, $price, $cost_price, $point_exchange, $give_point, $is_member_discount, $shipping_fee, $shipping_fee_id, $stock, $max_buy, $min_buy, $min_stock_alarm, $clicks, $sales, $collects, $star, $evaluates, $shares, $province_id, $city_id, $picture, $keywords, $introduction, $description, $QRcode, $code, $is_stock_visible, $is_hot, $is_recommend, $is_new, $sort, $image_array, $sku_array, $state, $sku_img_array, $goods_attribute_id, $goods_attribute, $goods_spec_format, $goods_weight, $goods_volume, $shipping_fee_type, $extend_category_id, $sku_picture_values, $item_no, $coupon_type_id, $gift_voucher_id, $balance_setting, $goods_exchange_type, $point_exchange_type, $conversion_point,
                                           $conversion_price, $video_id, $limit_num, $day_num, $is_shipping_free='', $verificationinfo=[], $goods_count = 0)
    {
        $error = 0;
        $category_mdl = new VslIntegralCategoryModel();
        $integral_goods_mdl = new VslIntegralGoodsModel();
        $category_list = $category_mdl->getInfo(['integral_category_id'=>$category_id],'*');
        $integral_goods_mdl->startTrans();
        try {
            $data_goods = array(
                'website_id' => $this->website_id,
                'goods_name' => $goods_name,
                'shop_id' => $shopid,
                'category_id' => $category_list['integral_category_id'],
                'category_id_1' => $category_list['integral_category_id'],
                'category_id_2' => 0,
                'category_id_3' => 0,
                'supplier_id' => $supplier_id?:0,
                'brand_id' => $brand_id?:0,
                'group_id_array' => $group_id_array?:'',
                'goods_type' => $goods_type,
                'market_price' => $market_price,
                'price' => $conversion_price?:0,
                'promotion_price' => $conversion_price,
                'cost_price' => $conversion_price,
                'point_exchange_type' => $point_exchange_type,
                'point_exchange' => $conversion_point,
                'give_point' => $give_point?:0,
                'is_member_discount' => $is_member_discount,
                'shipping_fee' => $shipping_fee,
                'shipping_fee_id' => $shipping_fee_id?:0,
                'stock' => $stock,
                'max_buy' => $max_buy?:0,
                'min_buy' => $min_buy?:0,
                'min_stock_alarm' => $min_stock_alarm?:0,
                'clicks' => $clicks?:0,
//                'sales' => $sales?:0,
                'collects' => $collects?:0,
                'star' => $star,
                'evaluates' => $evaluates,
                'shares' => $shares?:0,
                'province_id' => $province_id?:0,
                'city_id' => $city_id?:0,
                'picture' => $picture,
                'keywords' => $keywords?:'',
                'introduction' => $introduction?:'',
                'description' => $description,
                'QRcode' => $QRcode?:'',
                'code' => $code,
                'is_stock_visible' => $is_stock_visible?:0,
                'is_hot' => $is_hot,
                'is_recommend' => $is_recommend,
                'is_new' => $is_new,
                'is_promotion' => '',
                'is_shipping_free' => $is_shipping_free,
                'sort' => $sort?:0,
                'img_id_array' => $image_array,
                'state' => $state,
                'sku_img_array' => $sku_img_array,
                'goods_attribute_id' => $goods_attribute_id,
                'goods_spec_format' => $goods_spec_format?:'',
                'goods_weight' => $goods_weight,
                'goods_volume' => $goods_volume,
                'shipping_fee_type' => $shipping_fee_type,
                'extend_category_id' => $category_list['integral_category_id'],
                'extend_category_id_1' => $extend_category_id_1s?:0,
                'extend_category_id_2' => $extend_category_id_2s?:0,
                'extend_category_id_3' => $extend_category_id_3s?:0,
                'item_no' => $item_no,
                'coupon_type_id' => $coupon_type_id?:0,
                'gift_voucher_id' => $gift_voucher_id?:0,
                'balance' => $balance_setting,
                'goods_exchange_type' => $goods_exchange_type,
//                'point_exchange_type' => $point_exchange_type,
                'video_id' => $video_id,
                'limit_num' => $limit_num,
                'day_num' => $day_num,
                'goods_count' => $goods_count,
            );
//            echo '<pre>';var_dump($data_goods);exit;
            if ($integral_goods_id == 0) {
                $data_goods['create_time'] = time();
                $data_goods['sale_date'] = time();
                $res = $integral_goods_mdl->save($data_goods);
                $data_goods['goods_id'] = $integral_goods_mdl->goods_id;
                $integral_goods_id = $integral_goods_mdl->goods_id;
                // 添加sku
                if (!empty($sku_array)) {
                    $sku_list_array = explode('§', $sku_array);
                    if(empty($sku_list_array[0])){
                        unset($sku_list_array[0]);//删掉空数据
                    }
                    foreach ($sku_list_array as $k => $v) {
                        $res = $this->addOrUpdateIntegralGoodsSkuItem($integral_goods_id, $v);
                        if (!$res) {
                            $error = 1;
                        }
                    }
                    // sku图片添加
                    $sku_picture_array = array();
                    if (!empty($sku_picture_values)) {
                        $sku_picture_array = json_decode($sku_picture_values, true);
                        foreach ($sku_picture_array as $k => $v) {
                            $res = $this->addIntegralGoodsSkuPicture($shopid, $integral_goods_id, $v["spec_id"], $v["spec_value_id"], $v["img_ids"]);
                            if (!$res) {
                                $error = 1;
                            }
                        }
                    }
                } else {
                    $goods_sku = new VslIntegralGoodsSkuModel();

                    // 添加一条skuitem
                    $sku_data = array(
                        'goods_id' => $integral_goods_id,
                        'sku_name' => '',
                        'market_price' => $market_price,
                        'price' => $conversion_price?:0,
                        'promote_price' => $conversion_price,
                        'cost_price' => $cost_price?:0,
                        'exchange_point' => $conversion_point?:0,
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
            } else {
                $data_goods['update_time'] = time();
                $res = $integral_goods_mdl->save($data_goods, [
                    'goods_id' => $integral_goods_id
                ]);
                $data_goods['goods_id'] = $integral_goods_id;
                if (!empty($sku_array)) {

                    $sku_list_array = explode('§', $sku_array);
                    if(empty($sku_list_array[0])){
                        unset($sku_list_array[0]);//删掉空数据
                    }
                    $this->deleteIntegralSkuItem($integral_goods_id, $sku_list_array);
                    foreach ($sku_list_array as $k => $v) {
                        $res = $this->addOrUpdateIntegralGoodsSkuItem($integral_goods_id, $v);
                        if (!$res) {
                            $error = 1;
                        }
                    }
                    $goods_sku = new VslIntegralGoodsSkuModel();
                    $del_sku = $goods_sku->destroy([
                        'goods_id' => $integral_goods_id,
                        'sku_name' => array(
                            'EQ',
                            ''
                        )
                    ]);

                    // 修改时先删除原来的规格图片
                    $this->deleteIntegralGoodsSkuPicture([
                        "goods_id" => $integral_goods_id
                    ]);
                    // sku图片添加
                    $sku_picture_array = array();
                    if (!empty($sku_picture_values)) {
                        $sku_picture_array = json_decode($sku_picture_values, true);
                        foreach ($sku_picture_array as $k => $v) {
                            $res = $this->addIntegralGoodsSkuPicture($shopid, $integral_goods_id, $v["spec_id"], $v["spec_value_id"], $v["img_ids"]);
                            if (!$res) {
                                $error = 1;
                            }
                        }
                    }
                } else {
                    $sku_data = array(
                        'goods_id' => $integral_goods_id,
                        'sku_name' => '',
                        'market_price' => $market_price,
                        'price' => $conversion_price?:0,
                        'promote_price' => $conversion_price,
                        'exchange_point' => $conversion_point?:0,
                        'cost_price' => $cost_price?:0,
                        'stock' => $stock,
                        'picture' => 0,
                        'code' => $code,
                        'QRcode' => '',
                        'update_date' => time()
                    );

                    $goods_sku = new VslIntegralGoodsSkuModel();
                    $count = $goods_sku->getCount([
                        'goods_id' => $integral_goods_id
                    ]); // 当前SKU没有则添加，否则修改

                    if ($count > 0) {
                        $goods_sku->destroy([
                            'goods_id' => $integral_goods_id]);
                        $res = $goods_sku->save($sku_data);
                    } else {
                        $res = $goods_sku->save($sku_data);
                    }
                }
//                $this->modifyGoodsPromotionPrice($goods_id);
            }
            // 每次都要重新更新商品属性
            $goods_attribute_model = new VslIntegralGoodsAttributeModel();
            $goods_attribute_model->destroy([
                'goods_id' => $integral_goods_id
            ]);
            if (!empty($goods_attribute)) {
                if (!is_array($goods_attribute)) {
                    $goods_attribute_array = json_decode($goods_attribute, true);
                } else {
                    $goods_attribute_array = $goods_attribute;
                }
                if (!empty($goods_attribute_array)) {
                    foreach ($goods_attribute_array as $k => $v) {
                        if($v['attr_value_name']){
                            $goods_attribute_model = new VslIntegralGoodsAttributeModel();
                            $data = array(
                                'goods_id' => $integral_goods_id,
                                'shop_id' => $shopid,
                                'attr_value_id' => $v['attr_value_id'],
                                'attr_value' => $v['attr_value'],
                                'attr_value_name' => $v['attr_value_name'],
                                'sort' => $v['sort'],
                                'create_time' => time(),
                                'website_id' => $this->website_id,
                            );
                            $id = $goods_attribute_model->save($data);
                        }
                    }
                }
            }
//            var_dump($goods_id);exit;
            if ($error == 0) {
                $integral_goods_mdl->commit();
                return $integral_goods_id;
            } else {
                $integral_goods_mdl->rollback();
                return 0;
            }
        } catch (\Exception $e) {
            $integral_goods_mdl->rollback();
            return $e->getMessage();
        }
        return 0;

        // TODO Auto-generated method stub
    }

    /**
     * 积分商城回收商品的分页查询
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::getGoodsDeletedList()
     */
    public function getIntegralGoodsDeletedList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        // 针对商品分类
//        if (!empty($condition['ng.category_id'])) {
//            $goods_category = new GoodsCategory();
//            $category_list = $goods_category->getCategoryTreeList($condition['ng.category_id']);
//            $condition['ng.category_id'] = array(
//                'in',
//                $category_list
//            );
//        }
        $goods_view = new VslIntegralGoodsDeletedModel();

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

    /*
    * 删除积分商城商品到回收站
    */
    public function deleteIntegralGoods($goods_id)
    {
        $integral_goods_mdl = new VslIntegralGoodsModel();
        $integral_goods_mdl->startTrans();
        try {
            // 商品删除之前钩子
//            hook("goodsDeleteBefore", [
//                'goods_id' => $goods_id
//            ]);
            // 将商品信息添加到商品回收库中
            $this->addIntegralGoodsDeleted($goods_id);
            $condition = array(
                'shop_id' => $this->instance_id,
                'goods_id' => $goods_id
            );
            $res = $integral_goods_mdl->destroy($goods_id);

            if ($res > 0) {
                $goods_id_array = explode(',', $goods_id);
                $goods_sku_model = new VslIntegralGoodsSkuModel();
                $goods_attribute_model = new VslIntegralGoodsAttributeModel();
                $goods_sku_picture = new VslIntegralGoodsSkuPictureModel();
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
            $integral_goods_mdl->commit();
            if ($res > 0) {
                // 商品删除成功钩子
//                hook("goodsDeleteSuccess", [
//                    'goods_id' => $goods_id
//                ]);
                return SUCCESS;
            } else {
                return DELETE_FAIL;
            }
        } catch (\Exception $e) {
            $integral_goods_mdl->rollback();
            return DELETE_FAIL;
        }
    }

    /**
     * 添加积分商城商品sku列表
     *
     * @param unknown $goods_id
     * @param unknown $sku_item_array
     * @return Ambigous <number, \think\false, boolean, string>
     */
    private function addOrUpdateIntegralGoodsSkuItem($goods_id, $sku_item_array)
    {
        $goods_service = new Goods();
        $sku_item = explode('¦', $sku_item_array);
        $goods_sku = new VslIntegralGoodsSkuModel();
        $sku_name = $goods_service->createSkuName($sku_item[0]);
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
                'exchange_point' => $sku_item[3],
                'stock' => $sku_item[4],
                'picture' => 0,
                'code' => $sku_item[5],
                'QRcode' => '',
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
                'exchange_point' => $sku_item[3],
                'stock' => $sku_item[4],
                'code' => $sku_item[5],
                'QRcode' => '',
                'update_date' => time()
            );
            $res = $goods_sku->save($data, [
                'sku_id' => $sku_count['sku_id']
            ]);
            return $res;
        }
    }

    /**
     * 组装sku name
     *
     * @param unknown $pvs
     * @return string
     */
    private function createSkuName($pvs)
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
            $name = $name . $value_name['spec_value_name'] . ' ';
        }
        return $name;
    }

    /**
     * 积分商城回收站商品恢复
     * (non-PHPdoc)
     *
     * @see \data\api\IGoods::regainGoodsDeleted()
     */
    public function regainIntegralGoodsDeleted($goods_ids)
    {
        $integral_goods_mdl = new VslIntegralGoodsModel();
        if(!is_array($goods_ids)){
            $goods_array = explode(",", $goods_ids);
        }else{
            $goods_array = $goods_ids;
        }

        $integral_goods_mdl->startTrans();
        try {
            foreach ($goods_array as $goods_id) {
                $goods_delete_model = new VslIntegralGoodsDeletedModel();
                $goods_delete_obj = $goods_delete_model->getInfo([
                    "goods_id" => $goods_id
                ]);
                $goods_delete_obj = json_decode(json_encode($goods_delete_obj), true);
                $goods_model = new VslIntegralGoodsModel();
                $goods_model->save($goods_delete_obj);
                $goods_delete_model->where("goods_id=$goods_id")->delete();
                // sku 恢复
                $goods_sku_delete_model = new VslIntegralGoodsSkuDeletedModel();
                $sku_delete_list = $goods_sku_delete_model->getQuery([
                    "goods_id" => $goods_id
                ], "*", "");
                foreach ($sku_delete_list as $sku_obj) {
                    $sku_obj = json_decode(json_encode($sku_obj), true);
                    $sku_model = new VslIntegralGoodsSkuModel();
                    $sku_model->save($sku_obj);
                }
                $goods_sku_delete_model->where("goods_id=$goods_id")->delete();
                // 属性恢复
                $goods_attribute_delete_model = new VslIntegralGoodsAttributeDeletedModel();
                $attribute_delete_list = $goods_attribute_delete_model->getQuery([
                    "goods_id" => $goods_id
                ], "*", "");
                foreach ($attribute_delete_list as $attribute_delete_obj) {
                    $attribute_delete_obj = json_decode(json_encode($attribute_delete_obj), true);
                    $attribute_model = new VslIntegralGoodsAttributeModel();
                    $attribute_model->save($attribute_delete_obj);
                }
                $goods_attribute_delete_model->where("goods_id=$goods_id")->delete();
                // sku图片恢复
                $goods_sku_picture_delete = new VslIntegralGoodsSkuPictureDeletedModel();
                $goods_sku_picture_delete_list = $goods_sku_picture_delete->getQuery([
                    'goods_id' => $goods_id
                ], "*", "");
                foreach ($goods_sku_picture_delete_list as $goods_sku_picture_list_delete_obj) {
                    $goods_sku_picture = new VslIntegralGoodsSkuPictureModel();
                    $goods_sku_picture_list_delete_obj = json_decode(json_encode($goods_sku_picture_list_delete_obj), true);
                    $goods_sku_picture->save($goods_sku_picture_list_delete_obj);
                }
                $goods_sku_picture_delete->where("goods_id=$goods_id")->delete();
            }
            $integral_goods_mdl->commit();
            return SUCCESS;
        } catch (\Exception $e) {
            $integral_goods_mdl->rollback();
            return UPDATA_FAIL;
        }
    }

    /**
     * 删除积分商城回收站商品
     *
     * @param unknown $goods_id
     * @return string
     */
    public function deleteRecycleIntegralGoods($goods_id)
    {
        $goods_id = explode(',', $goods_id);
        if (count($goods_id) > 1) {
            $id = '';
            foreach ($goods_id as $k => $v) {
                $id .= $v . ',';
            }
            $id = substr($id, 0, -1);
        } else {
            $id = $goods_id[0];
        }
        $goods_delete = new VslIntegralGoodsDeletedModel();
        $goods_delete->startTrans();
        try {
            $res = $goods_delete->where("goods_id in ($id) and shop_id=$this->instance_id ")->delete();
            if ($res > 0) {
                $goods_id_array = $goods_id;
                $goods_sku_model = new VslIntegralGoodsSkuDeletedModel();
                $goods_attribute_model = new VslIntegralGoodsAttributeDeletedModel();
                $goods_sku_picture_delete = new VslIntegralGoodsSkuPictureDeletedModel();
                foreach ($goods_id_array as $k => $v) {
                    // 删除商品sku
                    $goods_sku_model->where("goods_id = $v")->delete();
                    // 删除商品属性
                    $goods_attribute_model->where("goods_id = $v")->delete();
                    // 删除
                    $goods_sku_picture_delete->where("goods_id = $v")->delete();
                }
            }
            $goods_delete->commit();
            if ($res > 0) {
                return SUCCESS;
            } else {
                return DELETE_FAIL;
            }
        } catch (\Exception $e) {
            $goods_delete->rollback();
            return DELETE_FAIL;
        }
    }

    /*
    * 添加积分商城商品规格图片
    */
    public function addIntegralGoodsSkuPicture($shop_id, $goods_id, $spec_id, $spec_value_id, $sku_img_array)
    {
        // TODO Auto-generated method stub
        $goods_sku_picture = new VslIntegralGoodsSkuPictureModel();
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
    public function deleteIntegralGoodsSkuPicture($condition)
    {
        // TODO Auto-generated method stub
        $goods_sku_picture = new VslIntegralGoodsSkuPictureModel();
        $retval = $goods_sku_picture->destroy($condition);
        return $retval;
    }

    /**
     * 违规下架
     */
    public function ModifyIntegralGoodsOutline($condition)
    {
        $integral_goods_mdl = new VslIntegralGoodsModel();
        $data = array(
            "state" => 0,
            'update_time' => time()
        );
        $result = $integral_goods_mdl->save($data, "goods_id  in($condition)");
        if ($result > 0) {
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    /**
     * 暂时是移动端/app结算页面的数据获取/计算
     * @param array $sku_list
     * @param string $msg
     *
     * @return array $return_data
     */
    public function paymentData(array $sku_list, &$msg = '')
    {
        // 获取非秒杀,团购商品,各个类型所需的数据结构
        // $return_data 全部数据
        // $return_data[$shop_id]['total_amount'] 店铺应付金额
        // $return_data[$shop_id]['goods_list'] 店铺商品
        $integral_sku_model = new VslIntegralGoodsSkuModel();
        $goods_spec_value = new VslGoodsSpecValueModel();
        $coupon_type_model = new VslCouponTypeModel();
        $gift_voucher_model = new VslGiftVoucherModel();
        $new_sku_list = $return_data = $shipping_sku = $record_sku = [];
        foreach ($sku_list as $k => $v) {
            $new_sku_list[$v['sku_id']] = $v;
            $sku_detail[$k] = $integral_sku_model::get(['sku_id' => $v['sku_id']], ['goods']);
        }
        //获取还可以购买多少个
        $least_num = $this->getUserCanBuyNum($sku_detail[0]['goods_id'], $sku_detail[0]['goods']['day_num'], $sku_detail[0]['goods']['limit_num']);
        if($sku_list[0]['num'] > $least_num && $least_num != -1){
            return ['code' => -2, 'message' => $v->goods->goods_name . '您已达商品最大购买量' . PHP_EOL];
        }
        $shop = new VslShopModel();
        $album_picture_model = new AlbumPictureModel();
        foreach ($sku_detail as $k => $v) {
            $temp_sku = [];
            if ($v->stock <= 0) {
                return ['code' => -2, 'message' => $v->goods->goods_name . '商品库存不足' . PHP_EOL];
            }
            if ($v->goods->state != 1) {
                return ['code' => -2, 'message' => $v->goods->goods_name . '商品为不可购买状态' . PHP_EOL];
            }
            if ($v->goods->max_buy != 0 && $v->goods->max_buy < $v->num) {
                $temp_sku['num'] = $v->goods->max_buy;
                $msg .= $v->goods->goods_name . '商品该sku规格购买量大于最大购买量，购买数量已更改' . PHP_EOL;
            }
            if ($v->stock < $new_sku_list[$v->sku_id]['num']) {
                $temp_sku['num'] = $v->stock;
                $msg .= $v->goods->goods_name . '商品该sku规格购买量大于剩余库存，购买数量已更改' . PHP_EOL;
            }

            if (mb_strlen($v->goods->goods_name) > 10) {
                $temp_sku['goods_name'] = mb_substr($v->goods->goods_name, 0, 10) . '...';
            } else {
                $temp_sku['goods_name'] = $v->goods->goods_name;
            }

            $temp_sku['max_buy'] = $v->goods->max_buy;
            $temp_sku['price'] = $v->price;
//            $temp_sku['point_exchange'] = 10;
            $temp_sku['point_exchange'] = $v->exchange_point;
            $temp_sku['stock'] = $v->stock > $least_num ? ($least_num == -1 ? $v->stock : $least_num) : $v->stock;
            $temp_sku['min_buy'] = 1;
            $now_time = time();
            switch ($v->goods->goods_exchange_type) {
                case 0:
                    $return_data[$v->goods->shop_id]['need_address'] = true;
                    $temp_sku['coupon'] = $temp_sku['gift_voucher'] = (object)[];
                    break;
                case 1:
                    if (!getAddons('coupontype', $this->website_id)) {
                        return ['code' => -2, 'message' => '优惠券应用已关闭'];
                    }
                    //判断优惠券是否过期
                    $coupon = new VslCouponTypeModel();
                    $end_time = $coupon->getInfo(['coupon_type_id' => $v->goods->coupon_type_id], 'end_time')['end_time'];
                    if($now_time > $end_time){
                        return ['code' => -1, 'message' => '优惠券已过期'];
                    }
                    if (!isset($return_data[$v->goods->shop_id]['need_address'])){
                        $return_data[$v->goods->shop_id]['need_address'] = false;
                    }
                    $temp_sku['coupon']['coupon_type_id'] = $v->goods->coupon_type_id;
                    $temp_sku['coupon']['coupon_name'] = $coupon_type_model::get($v->goods->coupon_type_id)['coupon_name'];
                    $temp_sku['gift_voucher'] = (object)[];
                    break;
                case 2:
                    if (!getAddons('giftvoucher', $this->website_id)) {
                        return ['code' => -2, 'message' => '礼品券应用已关闭'];
                    }
                    //判断礼品券是否过期
                    $giftvoucher = new VslGiftVoucherModel();
                    $end_time = $giftvoucher->getInfo(['gift_voucher_id' => $v->goods->gift_voucher_id], 'end_time')['end_time'];
                    if($now_time > $end_time){
                        return ['code' => -1, 'message' => '礼品券已过期'];
                    }
                    if (!isset($return_data[$v->goods->shop_id]['need_address'])){
                        $return_data[$v->goods->shop_id]['need_address'] = false;
                    }
                    $temp_sku['gift_voucher']['gift_voucher_id'] = $v->goods->gift_voucher_id;
                    $temp_sku['gift_voucher']['gift_voucher_name'] = $gift_voucher_model::get($v->goods->gift_voucher_id)['giftvoucher_name'];
                    $temp_sku['coupon'] = (object)[];
                    break;
                case 3:
                    if (!isset($return_data[$v->goods->shop_id]['need_address'])){
                        $return_data[$v->goods->shop_id]['need_address'] = false;
                    }
                    $temp_sku['coupon'] = $temp_sku['gift_voucher'] = (object)[];
                    break;
            }
            $temp_sku['goods_exchange_type'] = $v->goods->goods_exchange_type;
            $temp_sku['balance'] = $v->goods->balance;

            $return_data[$v->goods->shop_id]['shop_id'] = $v->goods->shop_id;
            if (empty($return_data[$v->goods->shop_id]['shop_name'])) {
                $return_data[$v->goods->shop_id]['shop_name'] = $shop->getInfo(['shop_id' => $v->goods->shop_id, 'website_id' => $v->goods->website_id])['shop_name'];
            }

            $temp_sku['sku_id'] = $v->sku_id;
            $temp_sku['num'] = $new_sku_list[$v->sku_id]['num'];
            $temp_sku['goods_id'] = $v->goods_id;
            $temp_sku['shop_id'] = $v->goods->shop_id;
            $picture = $this->getSkuPictureBySkuId($v);
            $picture_info = $album_picture_model->get($picture == 0 ? $v->goods->picture : $picture);
            $temp_sku['goods_pic'] = $picture_info ? getApiSrc($picture_info->pic_cover) : '';


            $return_data[$v->goods->shop_id]['total_amount'] += $temp_sku['price'] * $temp_sku['num'];
            $return_data[$v->goods->shop_id]['total_point'] += $temp_sku['point_exchange'] * $temp_sku['num'];

            // 规格
            $spec_info = [];
            if ($v['attr_value_items']) {
                $sku_spec_info = explode(';', $v['attr_value_items']);
                foreach ($sku_spec_info as $k_spec => $v_spec) {
                    $spec_value_id = explode(':', $v_spec)[1];
                    $sku_spec_value_info = $goods_spec_value::get($spec_value_id, ['goods_spec']);
                    $spec_info[$k_spec]['spec_value_name'] = $sku_spec_value_info['spec_value_name'];
                    $spec_info[$k_spec]['spec_name'] = $sku_spec_value_info['goods_spec']['spec_name'];
                }
            }
            $temp_sku['spec'] = $spec_info;

            $return_data[$v->goods->shop_id]['goods_list'][] = $temp_sku;
        }

        foreach ($return_data as &$v) {
            $v['total_amount'] = ($v['total_amount'] > 0) ? $v['total_amount'] : 0;
        }
        unset($v);

        return $return_data;
    }

    /**
     * 根据商品规格信息查询SKU主图片
     *
     * @param 商品规格信息 $goods_sku_info
     * @return 0：没有查询到商品SKU图片，!0:查询到了商品SKU图片
     */
    public function getSkuPictureBySkuId($goods_sku_info)
    {
        $picture = 0;
        $attr_value_items = $goods_sku_info['attr_value_items'];
        if (!empty($attr_value_items)) {
            $attr_value_items_array = explode(";", $attr_value_items);
            foreach ($attr_value_items_array as $k => $v) {
                $temp_array = explode(":", $v); // 规格：规格值
                $condition['goods_id'] = $goods_sku_info['goods_id'];
                $condition['spec_id'] = $temp_array[0]; // 规格
                $condition['spec_value_id'] = $temp_array[1]; // 规格值
                $condition['shop_id'] = $this->instance_id;
                $goods_sku_picture_model = new VslIntegralGoodsSkuPictureModel();
                $sku_img_array = $goods_sku_picture_model->getInfo($condition, 'sku_img_array');
                if (!empty($sku_img_array['sku_img_array'])) {
                    $temp = explode(",", $sku_img_array['sku_img_array']);
                    $picture = $temp[0];
                    break;
                }
            }
        }

        return $picture;
    }

    /*
     * 验证提交订单数据
     * **/
    public function validateData($order_data)
    {
        //验证商品是否存在库存
        $integral_goods_mdl = new VslIntegralGoodsModel();
        $integral_goods_sku_mdl = new VslIntegralGoodsSkuModel();
        $member_account_mdl = new VslMemberAccountModel();
        $goods_id = $order_data['goods_list']['goods_id'];
        $sku_id = $order_data['goods_list']['sku_id'];
        $integral_goods_list = $integral_goods_mdl->getInfo(['goods_id'=>$goods_id],'*');
        if(empty($integral_goods_list)){
            return ['code' => -1, 'message' => '商品不存在'];
        }
        $integral_goods_sku_list = $integral_goods_sku_mdl->getInfo(['sku_id'=>$sku_id],'*');
        $stock = $integral_goods_sku_list['stock'];
        $num = $order_data['goods_list']['num'];
        //判断应用是否开启
        $goods_type = $order_data['goods_type'];
        $website_id = $this->website_id;
        if(!getAddons('integral',$website_id)){
            return ['code' => -1, 'message' => '积分商城应用已关闭'];
        }
        if ($goods_type == 1) {//优惠券
            if(!getAddons('coupontype',$website_id)){
                return ['code' => -1, 'message' => '优惠券应用已关闭'];
            }
        } elseif ($goods_type == 2) {//礼品券
            if(!getAddons('giftvoucher',$website_id)){
                return ['code' => -1, 'message' => '礼品券应用已关闭'];
            }
        }
        if ($num > $stock) {
            return ['code' => -1, 'message' => '商品：' . $integral_goods_list['goods_name'] . ' 规格库存不足'];
        }
        //兑换方式
        $member_list = $member_account_mdl->getInfo(['uid' => $this->uid], '*');
        $my_point = $member_list['point'];
        $exchange_point = $order_data['goods_list']['exchange_point'];
        //验证商品积分
        if ($exchange_point > $my_point) {
            return ['code' => -1, 'message' => '您的积分不足'];
        }
//        if($integral_goods_list['point_exchange_type'] == 2 || $integral_goods_list['point_exchange_type'] == 3){
//            //判断商品是否是实物的,获取运费
//            if($integral_goods_list['goods_exchange_type'] == 0){
//                if ($integral_goods_list['shipping_fee_type'] == 0) {
//                    $shipping_fee = 0;
//                } elseif ($integral_goods_list['shipping_fee_type'] == 1) {
//                    $shipping_fee = $integral_goods_list['shipping_fee'];
//                } elseif ($integral_goods_list['shipping_fee_type'] == 2) {
//                    // 定位成功，查询当前城市的运费
//                    $address_id = $order_data['address_id'];
//                    $member_addr = new VslMemberExpressAddressModel();
//                    $temp_goods = [['count'=>$num, 'goods_id' =>$goods_id ]];
//                    $member_addr_info = $member_addr->getInfo(['id' => $address_id]);
//                    $shipping_fee = $this->getIntegralGoodsExpressTemplate($temp_goods, $member_addr_info['district']);
//                }
//            }else{
//                $shipping_fee = 0;
//            }

            //验证商品价格
            $price = $order_data['goods_list']['price'];
//            $origin_price = $integral_goods_sku_list['price'] + $shipping_fee;
            $origin_price = $integral_goods_sku_list['price'];
            if($price != $origin_price){
                return ['code' => -3, 'message' => '商品兑换价格调整'];
            }
//        }
    }
    /*
     * 验证积分支付密码
     * **/
    public function check_pay_password($password){

        $user = new usermodel();
        $condition['uid'] = $this->uid;
        $user_password = $user->getInfo($condition,'payment_password');
        $real_password = $user_password['payment_password'];

        if($real_password != md5($password)){
            return false;
        }else{
            $data['code'] = 0;
            $data['message'] = "验证成功";
            $data['data'] = '';
            return json($data);
        }
    }

    /*
     * 获得运费
     * **/
    public function getGoodsExpressTemplate($goods_ids = array(), $district_id = 0)
    {
        if (!$goods_ids || !is_array($goods_ids)) {
            return array();
        }
        $goods_express = new GoodsExpress();
        $totalFee = 0.00;
        foreach ($goods_ids as $goods_id) {
            $goods = new VslIntegralGoodsModel();
            $goodsInfo = $goods->getInfo([
                'goods_id' => $goods_id['goods_id']
            ], 'shop_id, shipping_fee,shipping_fee_type,shipping_fee_id,goods_weight,goods_volume');
            if ($goodsInfo['shipping_fee_type'] == 0) {
                $totalFee += 0.00;
            } elseif ($goodsInfo['shipping_fee_type'] == 1) {
                $totalFee += $goodsInfo['shipping_fee'];
            } else {
                $shippingModel = new VslOrderShippingFeeModel();
                $shippingFee = $shippingModel->getInfo(['shipping_fee_id' => $goodsInfo['shipping_fee_id']]);
                $totalFee += $goods_express->getGoodsFee($goodsInfo, $goods_id['count'], $shippingFee, $district_id);
            }
        }
        return $totalFee;
    }

    /*
     * 创建积分商城订单
     * **/
    public function createIntegralOrder($order_data)
    {
        //下单数据
        try{
            $is_order_key = md5(json_encode($order_data));
            $order_info['order_no'] = $order_data['order_no'];//订单编号
            $order_info['out_trade_no'] = $order_data['out_trade_no'];//外部交易号
            $order_info['order_type'] = 10;//订单类型订单类型1为普通2成为微店店主3为微店店主续费4为微店店主升级，5拼团订单，6秒杀订单，7预售订单，8砍价订单，9奖品订单，10兑换订单
            $order_info['payment_type'] = $order_data['pay_type']?:0;//支付类型
            $order_info['shipping_type'] = 1;//订单配送方式
            $order_info['order_from'] = $order_data['type']?:1;//订单来源
            $order_info['buyer_id'] = $order_data['uid'];
            $user_model = new UserModel();
            $buyer_info = $user_model::get($order_data['uid']);
            $order_info['nick_name'] = $buyer_info['nick_name'];
            $order_info['ip'] = $order_data['ip'];
            $order_info['leave_message'] = $order_data['leave_message']?:'';//买家附言
            $member = new Member();
            if($order_data['address_id']){
                $address = $member->getMemberExpressAddressDetail($order_data['address_id'], $order_data['uid']);
            }
            $order_info['shipping_type'] = 1;
            $order_info['receiver_mobile'] = $address['mobile']?:'';
            $order_info['receiver_province'] = $address['province']?:0;//省id
            $order_info['receiver_city'] = $address['city']?:0;
            $order_info['receiver_district'] = $address['district']?:0;
            $order_info['receiver_address'] = $address['address']?:'';
            $order_info['receiver_zip'] = $address['zip_code']?:0;
            $order_info['receiver_name'] = $address['consigner']?:'';
            $order_info['shop_id'] = 0;
            $shop_condition['shop_id'] = 0;
            $shop_condition['website_id'] = $order_data['website_id'];
            $shop_mdl = new VslShopModel();
            $shop_name = $shop_mdl->getInfo($shop_condition,'shop_name')['shop_name'];
            $order_info['shop_name'] = $shop_name;
            $goods_money = $order_data['goods_list']['num']*$order_data['goods_list']['price'];
            $order_info['shop_total_amount'] = $goods_money;
            $order_money = $goods_money+$order_data['goods_list']['shipping_fee'];
            $order_info['order_money'] = $order_money;
            $order_info['member_money'] = $goods_money;//会员价总额
            $order_info['point'] = $order_data['goods_list']['exchange_point'];//订单消耗积分
            $order_info['point_money'] = 0;//订单消耗积分抵多少钱
            $order_info['user_money'] = 0;//订单余额支付金额
            //用户平台余额支付
            if ($order_data['pay_type'] == 5) {
                $order_info['pay_money'] = 0;
                $order_info['user_platform_money'] = $order_money;
            } else {
                $order_info['pay_money'] = $order_money;
                $order_info['user_platform_money'] = 0;
            }
            $order_info['shipping_fee'] = $order_data['goods_list']['shipping_fee'];//订单运费
            $order_info['pay_money'] = $order_money;
            $order_info['shop_should_paid_amount'] = $order_money;//应付金额
            //判断商品类型是虚拟的就不用发货了直接完成
            if($order_data['goods_type'] === '0'){
                $order_info['order_status'] = 1;//订单状态 0->未支付，1->已付款，2->已发货，3->确认收货,4->已完成,5->已关闭
            }else{
                $order_info['order_status'] = 4;//订单状态 0->未支付，1->已付款，2->已发货，3->确认收货,4->已完成,5->已关闭
            }
            $order_info['pay_status'] = 2;//订单付款状态,0->待支付，1->支付中，2->已支付
            $order_info['pay_time'] = time();
            $order_info['create_time'] = time();
            $order_info['website_id'] = $order_data['website_id'];
            $order_info['order_sn'] = $order_data['out_trade_no'];
            $order_info['custom_order'] = $order_data['custom_order']?:'';//自定义订单内容
//          `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '门店id',
            $order_info['store_id'] = $order_data['store_id']?:0;
            $order_data['goods_list']['member_price'] = $order_data['goods_list']['price'];
            $order_data['goods_list']['order_type'] = 10;
            $order_data['goods_list']['discount_price'] = $order_data['goods_list']['price'];
            $order_info['sku_info'][] = $order_data['goods_list'];
            $order_business = new OrderBusiness();
            $order_id = $order_business->orderCreateNew($order_info);
//            $order_id = 150;
            if ($order_id < 0) {
                //订单创建失败，将订单金额返回给用户
                Db::startTrans();
                $payment_type = $order_data['pay_type'];
                $refund_fee = $order_money;
                $refund_trade_no = date("YmdHis") . rand(100000, 999999);
                if ($payment_type == 5) {
                    // 退还会员的账户余额
                    $order_server = new Order();
                    $retval = $order_server->updateMemberAccount($order_id, $order_data['uid'], $refund_fee);
                    if (!is_numeric($retval)) {
                        Db::rollback();
                    }else{
                        Db::commit();
                    }
                } else {
                    if ($payment_type == 1) {
                        // 微信退款
                        $weixin_pay = new WeiXinPay();
                        $retval = $weixin_pay->setWeiXinRefund($refund_trade_no, $order_data['out_trade_no'], $refund_fee * 100, $order_money * 100, $order_data['website_id']);
                    } elseif ($payment_type == 2) {
                        // 支付宝退款
                        $ali_pay = new UnifyPay();
                        $retval = $ali_pay->aliPayNewRefund($refund_trade_no, $order_data['out_trade_no'], $refund_fee);
                        $result = json_decode(json_encode($retval), TRUE);
                        if ($result['code'] == '10000' && $result['msg'] == 'Success') {

                        } else {
                            $retval = array(
                                "is_success" => 0,
                                'msg' => $result['msg']
                            );
                        }
                    }
                    if($retval['is_success'] != 0){
                        Db::commit();
                    }else{
                        Db::rollback();
                    }
                }
                return 0;
            }else{
                Db::startTrans();
                //将用户购买积分商品记录插入表
                $integral_user = new VslIntegralUserModel();
                $iu_data['uid'] = $order_data['uid'];
                $iu_data['order_id'] = $order_id;
                $iu_data['goods_id'] = $order_data['goods_list']['goods_id'];
                $iu_data['sku_id'] = $order_data['goods_list']['sku_id'];
                $iu_data['num'] = $order_data['goods_list']['num'];
                $iu_data['shop_id'] = $this->instance_id;
                $iu_data['website_id'] = $this->website_id;
                $iu_data['day_time'] = strtotime(date('Ymd'));
                $iu_cond['order_id'] = $order_id;
                $iu_cond['goods_id'] = $order_data['goods_list']['goods_id'];
                $iu_cond['website_id'] = $this->website_id;
                $iu_cond['day_time'] = strtotime(date('Ymd'));
                $iu_list = $integral_user->getInfo($iu_cond);
                if(!$iu_list){
                    $integral_user->save($iu_data);
                }
                //判断当前商品是什么类型，然后发放给用户
                $res = $this->sendIntegralGoods($order_data['goods_type'], $order_data['goods_list']);
                if($res <= 0){
                    Db::rollback();
                }
                Db::commit();
                $cookie_set_data['create_time'] = time();
                $cookie_set_data['out_trade_no'] = $order_data['out_trade_no'];
                $cookie_set_data['order_id'] = $order_id;
                $cookie_set_data = serialize($cookie_set_data);
                Cookie::set($is_order_key, $cookie_set_data, 15);
                return $order_id;
            }
        }catch(\Exception $e){
            echo $e->getMessage();
        }

    }
    /*
     * 最后一步， 发放虚拟商品
     * **/
    public function sendIntegralGoods($goods_type, $goods_list)
    {
        try{
            $integral_goods = new VslIntegralGoodsModel();
            //买了多少个
            $num = $goods_list['num'];
            switch($goods_type){//1-优惠券 2-礼品券 3-余额
                case '1':
                    //获取优惠券id
                    $coupon_type_id = $integral_goods->getInfo(['goods_id' => $goods_list['goods_id'], 'website_id' => $this->website_id], 'coupon_type_id')['coupon_type_id'];
                    if ($coupon_type_id != 0 && getAddons('coupontype', $this->website_id)) {
                        $member_coupon = new Coupon();
                        for($i=1;$i<=$num;$i++){
                            $member_coupon->userAchieveCoupon($this->uid, $coupon_type_id, 1);
                        }
                    }
                    break;
                case '2':
                    //获取礼品券id
                    $gift_voucher_id = $integral_goods->getInfo(['goods_id' => $goods_list['goods_id'], 'website_id' => $this->website_id], 'gift_voucher_id')['gift_voucher_id'];
                    if ($gift_voucher_id != 0 && getAddons('giftvoucher', $this->website_id)) {
                        $giftvoucher = new GiftVoucher();
                        for($i=1;$i<=$num;$i++){
                            $giftvoucher->getUserReceive($this->uid, $gift_voucher_id, 1);
                        }
                    }
                    break;
                case '3':
                    //获取余额
                    $balance = $integral_goods->getInfo(['goods_id' => $goods_list['goods_id'], 'website_id' => $this->website_id], 'balance')['balance'];
                    $balance = $balance * $num;
                    if($balance){
//                        $member_account = new VslMemberAccountModel();
//                        $member_res = $member_account->where(['uid' => $this->uid, 'website_id' => $this->website_id])->find();
//                        $member_res->balance = $member_res->balance + $balance;
//                        $member_res->save();
                        //加流水
                        $memberAccount = new Member\MemberAccount();
                        $memberAccount->addMemberAccountData(2, $this->uid, 1, $balance,
                            30, $this->website_id, '积分商城兑换余额');
                        //加通知
                        runhook("Notify", "balanceChangeByTemplate", ["website_id" => $this->website_id, "uid" => $this->uid, "change_money" => $balance, 'change_str' => '积分商城兑换余额到账', 'type_desc' => '积分商城兑换余额']);
                    }
                    break;
            }
            return 1;
        }catch(\Exception $e){
            return 0;
            return $e->getMessage();
        }
    }

    public function demo()
    {
        // 满减送赠送优惠券

        // 满减送赠送赠品，并且应用在的时候

        // 满减送赠送礼品券

    }
    /**
     * 减少商品库存(购买使用)
     * @param unknown $sku_id  //商品属性
     * @param unknown $num     //商品数量
     * @param unknown $cost_price  //减少成本价  通过加权统计
     */
    public function subIntegralGoodsStock($goods_id, $sku_id, $num)
    {
        $goods_model = new VslIntegralGoodsModel();
        $stock = $goods_model->getInfo(['goods_id' => $goods_id], 'stock');
        if($stock['stock'] < $num)
        {
            return LOW_STOCKS;
            exit();
        }
        $goods_sku_model = new VslIntegralGoodsSkuModel();
        $sku_stock = $goods_sku_model->getInfo(['sku_id' => $sku_id], 'stock');
        if($sku_stock['stock'] < $num)
        {
            return LOW_STOCKS;
            exit();
        }
        $goods_model->save(['stock' => $stock['stock']-$num], ['goods_id' => $goods_id]);
        $retval = $goods_sku_model->save(['stock' => $sku_stock['stock']-$num], ['sku_id' => $sku_id]);
        return $retval;

    }
    /*
     * 获取商品的运费
     * **/
    public function getIntegralGoodsExpressTemplate($goods_ids = array(), $district_id = 0)
    {
        if(!$goods_ids || !is_array($goods_ids)){
            return array();
        }
        $totalFee = 0.00;
        /*
         * 修复bug 运费模板体积重量件数叠加计算运费
         * $author ljt 2018-08-05
         */
        $goods = new VslIntegralGoodsModel();
        $ids = [];
        foreach($goods_ids as $goods_id){
            $goodsInfo = $goods->getInfo([
                'goods_id' => $goods_id['goods_id']
            ], 'shop_id, shipping_fee,shipping_fee_type,shipping_fee_id,goods_weight,goods_volume,goods_count');
            if ($goodsInfo['shipping_fee_type'] == 0) {
                $totalFee += 0.00;
            } elseif($goodsInfo['shipping_fee_type'] == 1){
                $totalFee += $goodsInfo['shipping_fee'];
            }else {
                $ids[$goodsInfo['shipping_fee_id']]['goods_weight'] += $goodsInfo['goods_weight'] * $goods_id['count'];
                $ids[$goodsInfo['shipping_fee_id']]['goods_volume'] += $goodsInfo['goods_volume'] * $goods_id['count'];
                $ids[$goodsInfo['shipping_fee_id']]['goods_count'] += $goodsInfo['goods_count'] * $goods_id['count'];
            }
        }
        /*
         * 如果有使用运费模板
         */
        if($ids){
            $shippingModel = new VslOrderShippingFeeModel();
            foreach($ids as $shipping_fee_id => $val){
                $shippingFee = $shippingModel->getInfo(['shipping_fee_id'=>$shipping_fee_id]);
                $val['shipping_fee_id'] = $shipping_fee_id;
		$goods_express = new GoodsExpress();
                $totalFee += $goods_express->getGoodsFee($val,$shippingFee,$district_id);
            }
        }
        return $totalFee;
    }
    /*
     * 获取积分商城商品类型
     * **/
    public function getIntegralGoodsType($integral_type)
    {
        if($integral_type == 0){
            $type = '商品';
        }elseif($integral_type == 1){
            $type = '优惠券';
        }elseif($integral_type == 2){
            $type = '礼品券';
        }else{
            $type = '余额';
        }
        return $type;
    }
    /*
     * 判断用户还可以购买多少
     * **/
    public function getUserCanBuyNum($goods_id, $day_num, $limit_num)
    {
        if($day_num == 0 || $limit_num == 0){ //当设置的限购数量有一个为0，说明不限制
            return -1;
        }
        //如果用户登录了，查询用户还能买多少个该商品
        //这个商品今天卖了多少个
        $integral_user = new VslIntegralUserModel();
        $day_cond['goods_id'] = $goods_id;
        $day_cond['day_time'] = strtotime(date('Ymd'));
        $day_count = $integral_user->where($day_cond)->sum('num');
        //今天还剩多少个
        $count = ($day_num - $day_count > 0) ? $day_num - $day_count : 0;
        //这个商品用户已经买了多少个了
        if($this->uid){
            $user_cond['uid'] = $this->uid;
            $user_cond['goods_id'] = $goods_id;
            /*//用户今天购买了多少件
            $day_time = strtotime(date('Y-m-d'));
            $user_cond['day_time'] = $day_time;*/
            $user_count = $integral_user->where($user_cond)->sum('num');
            //还可以买多少个
            $user_remain_num = ($limit_num-$user_count > 0)? $limit_num-$user_count :0;
            $count = $count > $user_remain_num ? $user_remain_num : $count;
        }
        return $count;
    }
}