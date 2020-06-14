<?php

namespace addons\fullcut\service;

use addons\coupontype\model\VslCouponModel;
use addons\gift\model\VslMemberGiftModel;
use addons\giftvoucher\model\VslGiftVoucherRecordsModel;
use addons\shop\model\VslShopModel;
use data\model\AlbumPictureModel as AlbumPictureModel;
use addons\coupontype\model\VslCouponTypeModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslPointConfigModel;
use data\model\VslPromotionDiscountGoodsModel;
use data\model\VslPromotionDiscountModel;
use data\model\VslPromotionFullMailModel;
use data\model\VslPromotionMansongGoodsModel;
use data\model\VslPromotionMansongModel;
use addons\fullcut\model\VslPromotionGiftModel;
use data\model\VslPromotionMansongRuleModel;
use data\service\BaseService as BaseService;
use data\service\promotion\GoodsDiscount;
use data\service\promotion\GoodsMansong;
use addons\giftvoucher\model\VslGiftVoucherModel;
use think\Db;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;

/**
 * 店铺设置控制器
 *
 * @author  www.vslai.com
 *
 */
class Fullcut extends BaseService
{
    public $shopinfo;

    public function __construct($shopinfo = '')
    {
        parent::__construct();
        $this->shopinfo = $shopinfo;
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMemberAccountFlow::getPointConfig()
     */
    public function getPointConfig()
    {
        $point_model = new VslPointConfigModel();
        $count = $point_model->where([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ])->count();
        if ($count > 0) {
            $info = $point_model->get([
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id
            ]);
        } else {
            $data = array(
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
                'is_open' => 0,
                'desc' => '',
                'create_time' => time()
            );
            $point_model = new VslPointConfigModel();
            $res = $point_model->save($data);
            $info = $point_model->get([
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id
            ]);
        }

        return $info;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMemberAccountFlow::setPointConfig()
     */
    public function setPointConfig($convert_rate, $is_open, $desc)
    {
        $point_model = new VslPointConfigModel();
        $data = array(
            'convert_rate' => $convert_rate,
            'is_open' => $is_open,
            'desc' => $desc,
            'modify_time' => time()
        );
        $retval = $point_model->save($data, [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ]);
        return $retval;
    }


    /*
     * (non-PHPdoc)
     * @see \data\api\IPromote::updatePromotionGift()
     */
    public function updatePromotionGift($gift_id, $shop_id, $gift_name, $start_time, $end_time, $days, $max_num, $goods_id_array)
    {
        $promotion_gift = new VslPromotionGiftModel();
        $promotion_gift->startTrans();
        try {
            $data_gift = array(
                'gift_name' => $gift_name,
                'shop_id' => $shop_id,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'days' => $days,
                'max_num' => $max_num,
                'modify_time' => time()
            );
            $promotion_gift->save($data_gift, [
                'gift_id' => $gift_id
            ]);
            // 当前功能只能选择一种商品
            $promotion_gift_goods = new VslPromotionGiftGoodsModel();
            $promotion_gift_goods->destroy([
                'gift_id' => $gift_id
            ]);
            // 查询商品名称图片
            $goods = new VslGoodsModel();
            $goods_info = $goods->getInfo([
                'goods_id' => $goods_id_array
            ], 'goods_name,picture');
            $data_goods = array(
                'gift_id' => $gift_id,
                'goods_id' => $goods_id_array,
                'goods_name' => $goods_info['goods_name'],
                'goods_picture' => $goods_info['picture']
            );
            $promotion_gift_goods = new VslPromotionGiftGoodsModel();
            $promotion_gift_goods->save($data_goods);
            $promotion_gift->commit();
            return 1;
        } catch (\Exception $e) {
            $promotion_gift->rollback();
            return $e->getMessage();
        }

        // TODO Auto-generated method stub
    }


    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::getPromotionMansongList()
     */

    //获取最优满减活动
    public function getBestMansongInfo($type)
    {
        //1表示自营
        if ($type == '1') {
            $sql = 'select * from `vsl_promotion_mansong` where `status` =1 and `start_time`<' . time() . ' and `end_time` >' . time() . ' order by `range` asc,`level` asc limit 0 , 1';
        } else {
            $sql = 'select * from `vsl_promotion_mansong` where `status` =1 and `range` = 2 and `start_time`<' . time() . ' and `end_time` >' . time() . ' order by `level` asc limit 0 , 1';
        }
        $result = Db::query($sql);
        return $result;
    }

    //判断是否是满减商品

    public function check_is_mansong_product($goods_id, $mansong_id)
    {

        //print_r("select `id` from `vsl_promotion_mansong_goods` where `goods_id` = $goods_id and `mansong_id` = $mansong_id");exit;
        $result = Db::query("select `id` from `vsl_promotion_mansong_goods` where `goods_id` = $goods_id and `mansong_id` = $mansong_id");
        return $result;

    }


    //获取具体的满减规则
    public function getmansong_rule($mansong_id)
    {

        $sql = "select * from vsl_promotion_mansong_rule where `mansong_id` = $mansong_id";
        $result = Db::query($sql);
        return $result;
    }

    //判断平台是自营还是代理
    public function checkshoptype($goodsid, $website_id)
    {

        $sql = "select * from vsl_goods where `goods_id` = $goodsid and `website_id` = $website_id";
        $result = Db::query($sql);
        if ($result['0']['shop_id'] == '0') {
            return 1;
        } else {
            return 2;
        }
    }

    //更改活动状态
    public function update_mansong_status($data,$where){

            $promotion_discount = new VslPromotionMansongModel();
            $retval = $promotion_discount->save($data,$where);
            return $retval;

    }

    public function getPromotionMansongList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
    {
        $promotion_mansong = new VslPromotionMansongModel();

        $list = $promotion_mansong->pageQuery($page_index, $page_size, $condition, $order, '*');
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if ($v['status'] == 0) {
                    $list['data'][$k]['status_name'] = '未开始';
                }
                if ($v['status'] == 1) {
                    $list['data'][$k]['status_name'] = '进行中';
                }
                if ($v['status'] == 2) {
                    $list['data'][$k]['status_name'] = '已取消';
                }
                if ($v['status'] == 3) {
                    $list['data'][$k]['status_name'] = '已失效';
                }
                if ($v['status'] == 4) {
                    $list['data'][$k]['status_name'] = '已结束';
                }
            }
        }
        return $list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::addPromotionMansong()
     */
    public function addPromotionMansong($mansong_name, $start_time, $end_time, $shop_id, $remark, $type, $range_type, $rule, $goods_id_array, $range, $status, $level)
    {
        $promot_mansong = new VslPromotionMansongModel();
        $goods_mansong = new GoodsMansong();
        $promot_mansong->startTrans();
        try {
            $err = 0;
//            $count_quan = $goods_mansong->getQuanmansong($start_time, $end_time);
//            if ($count_quan > 0 && $range_type == 1) {
//                $err = 1;
//            }
            $shop_name = $this->shopinfo['instance_name'];
            $data = array(
                'mansong_name' => $mansong_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'shop_id' => $shop_id,
                'shop_name' => $shop_name,
                'remark' => $remark,
                'type' => $type,
                'range_type' => $range_type,
                'create_time' => time(),
                'range' => $range,
                'status' => $status,
                'level' => $level,
                'website_id' => $this->website_id
            );
            $promot_mansong->save($data);
            $mansong_id = $promot_mansong->mansong_id;
            // 添加活动规则表
            $rule_array = explode(';', $rule);
            foreach ($rule_array as $k => $v) {
                $get_rule = explode(',', $v);
                $data_rule = array(
                    'mansong_id' => $mansong_id,
                    'price' => $get_rule[0],
                    'discount' => $get_rule[1],
                    'free_shipping' => $get_rule[2],
                    'give_coupon' => $get_rule[3],
                    'gift_id' => $get_rule[4],
                    'gift_card_id' => $get_rule[5]
                );
                $promot_mansong_rule = new VslPromotionMansongRuleModel();
                $promot_mansong_rule->save($data_rule);
            }

            // 满减送商品表
            if ($range_type == 0 && !empty($goods_id_array)) {
                // 部分商品
                //$goods_id_array = explode(',', $goods_id_array);
                foreach ($goods_id_array as $k => $v) {
                    $promotion_mansong_goods = new VslPromotionMansongGoodsModel();
                    // 查询商品名称图片
                    $goods = new VslGoodsModel();
                    $goods_info = $goods->getInfo([
                        'goods_id' => $v
                    ], 'goods_name,picture');
                    $data_goods = array(
                        'mansong_id' => $mansong_id,
                        'goods_id' => $v,
                        'goods_name' => $goods_info['goods_name'],
                        'goods_picture' => $goods_info['picture'],
                        'status' => 0, // 状态重新设置
                        'start_time' => getTimeTurnTimeStamp($start_time),
                        'end_time' => getTimeTurnTimeStamp($end_time)
                    );
//                    $count = $goods_mansong->getGoodsIsMansong($v, $start_time, $end_time);
//                    if ($count > 0) {
//                        $err = 1;
//                    }
                    $promotion_mansong_goods->save($data_goods);
                }
            }
            if ($err > 0) {
                $promot_mansong->rollback();
                return ACTIVE_REPRET;
            } else {
                $promot_mansong->commit();
                return $mansong_id;
            }
        } catch (\Exception $e) {
            $promot_mansong->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::updatePromotionMansong()
     */
    public function updatePromotionMansong($mansong_id, $mansong_name, $start_time, $end_time, $remark, $type, $range_type, $rule, $goods_id_array, $range, $status, $level)
    {
        $promot_mansong = new VslPromotionMansongModel();
        $promot_mansong->startTrans();
        try {
            $err = 0;
            $data = array(
                'mansong_name' => $mansong_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'status' => 0, // 状态重新设置
                'range' => $range,
                'remark' => $remark,
                'type' => $type,
                'level' => $level,
                'range_type' => $range_type,
                'create_time' => time()
            );
            $promot_mansong->save($data, [
                'mansong_id' => $mansong_id
            ]);
            // 添加活动规则表
            $promot_mansong_rule = new VslPromotionMansongRuleModel();
            $promot_mansong_rule->destroy([
                'mansong_id' => $mansong_id
            ]);
            $rule_array = explode(';', $rule);
            foreach ($rule_array as $k => $v) {
                $promot_mansong_rule = new VslPromotionMansongRuleModel();
                $get_rule = explode(',', $v);
                $data_rule = array(
                    'mansong_id' => $mansong_id,
                    'price' => $get_rule[0],
                    'discount' => $get_rule[1],
                    'free_shipping' => $get_rule[2],
                    'give_coupon' => $get_rule[3],
                    'gift_id' => $get_rule[4],
                    'gift_card_id' => $get_rule[5]
                );
                $promot_mansong_rule->save($data_rule);
            }

            // 满减送商品表
            $promotion_mansong_goods = new VslPromotionMansongGoodsModel();
            if ($range_type == 0 && !empty($goods_id_array)) {
                // 部分商品
               // $goods_id_array = explode(',', $goods_id_array);
                $promotion_mansong_goods->destroy([
                    'mansong_id' => $mansong_id
                ]);
                foreach ($goods_id_array as $k => $v) {
                    // 查询商品名称图片
                    $goods_mansong = new GoodsMansong();
//                    $count = $goods_mansong->getGoodsIsMansong($v, $start_time, $end_time);
//                    if ($count > 0) {
//                        $err = 1;
//                    }
                    $promotion_mansong_goods = new VslPromotionMansongGoodsModel();
                    $goods = new VslGoodsModel();
                    $goods_info = $goods->getInfo([
                        'goods_id' => $v
                    ], 'goods_name,picture');
                    $data_goods = array(
                        'mansong_id' => $mansong_id,
                        'goods_id' => $v,
                        'goods_name' => $goods_info['goods_name'],
                        'goods_picture' => $goods_info['picture'],
                        'status' => 0, // 状态重新设置
                        'start_time' => getTimeTurnTimeStamp($start_time),
                        'end_time' => getTimeTurnTimeStamp($end_time)
                    );
                    $promotion_mansong_goods->save($data_goods);
                }
            }else{
                $promotion_mansong_goods->destroy([
                    'mansong_id' => $mansong_id
                ]);
            }
            if ($err > 0) {
                $promot_mansong->rollback();
                return ACTIVE_REPRET;
            } else {

                $promot_mansong->commit();
                return 1;
            }
        } catch (\Exception $e) {
            $promot_mansong->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::getPromotionMansongDetail()
     */
    public function getPromotionMansongDetail($mansong_id)
    {
        $promotion_mansong = new VslPromotionMansongModel();
        $data = $promotion_mansong->get($mansong_id);
        $promot_mansong_rule = new VslPromotionMansongRuleModel();
        $rule_list = $promot_mansong_rule->pageQuery(1, 0, 'mansong_id = ' . $mansong_id, '', '*');
        foreach ($rule_list['data'] as $k => $v) {
            if ($v['free_shipping'] == 1) {
                $rule_list['data'][$k]['free_shipping_name'] = "是";
            } else {
                $rule_list['data'][$k]['free_shipping_name'] = "否";
            }
            if ($v['give_coupon'] == 0) {
                $rule_list['data'][$k]['coupon_name'] = '';
            } else {
                $coupon_type = new VslCouponTypeModel();
                $coupon_name = $coupon_type->getInfo([
                    'coupon_type_id' => $v['give_coupon']
                ], 'coupon_name');
                $rule_list['data'][$k]['coupon_name'] = $coupon_name['coupon_name'];
            }
            if ($v['gift_id'] == 0) {
                $rule_list['data'][$k]['gift_name'] = '';
            } else {
                $gift = new VslPromotionGiftModel();
                $gift_name = $gift->getInfo([
                    'promotion_gift_id' => $v['gift_id']
                ], 'gift_name');
                $rule_list['data'][$k]['gift_name'] = $gift_name['gift_name'];
            }
            if($v['gift_card_id']>0){
                $gift_voucher = new VslGiftVoucherModel();
                $gift_voucher_name = $gift_voucher->getInfo([
                    'gift_voucher_id' => $v['gift_card_id']
                ], 'giftvoucher_name');
                $rule_list['data'][$k]['giftvoucher_name'] = $gift_voucher_name['giftvoucher_name'];
            }
        }
        $data['rule'] = $rule_list['data'];
        if ($data['range_type'] == 0) {
            $mansong_goods = new VslPromotionMansongGoodsModel();
            $list = $mansong_goods->getQuery([
                'mansong_id' => $mansong_id
            ], '*', '');
            if (!empty($list)) {
                foreach ($list as $k => $v) {
                    $goods = new VslGoodsModel();
                    $goods_info = $goods->getInfo([
                        'goods_id' => $v['goods_id']
                    ], 'price, stock,shop_id');
                    $picture = new AlbumPictureModel();
                    $pic_info = array();
                    $pic_info['pic_cover'] = '';
                    if (!empty($v['goods_picture'])) {
                        $pic_info = $picture->getInfo(['pic_id' =>$v['goods_picture']],'pic_cover,pic_cover_mid,pic_cover_micro');
                    }
                    if(getAddons('shop', $this->website_id)){
                        $shop = new VslShopModel();
                        if (!empty($goods_info['shop_id']) && $goods_info['shop_id'] > 0) {
                            $shop_info = $shop->getInfo(['shop_id' => $goods_info['shop_id']]);
                        } else {
                            $shop_info['shop_name'] = '自营店';
                        }
                    }else{
                        $shop_info['shop_name'] = '自营店';
                    }

                    if(!empty($shop_info['shop_name'])){
                        $v['shop_name'] = $shop_info['shop_name'];
                    }else{
                        $v['shop_name'] = '-';
                    }

                    $v['picture_info'] = $pic_info;
                    $v['price'] = $goods_info['price'];
                    $v['stock'] = $goods_info['stock'];
                }
            }
            $data['goods_list'] = $list;
            $goods_id_array = array();
            foreach ($list as $k => $v) {
                $goods_id_array[] = $v['goods_id'];
            }
            $data['goods_id_array'] = $goods_id_array;
        }
        return $data;
    }


    public function closePromotionMansong($mansong_id)
    {
        $promotion_mansong = new VslPromotionMansongModel();
        $retval = $promotion_mansong->save([
            'status' => 3
        ], [
            'mansong_id' => $mansong_id,
            'shop_id' => $this->instance_id
        ]);
        if ($retval == 1) {
            $promotion_mansong_goods = new VslPromotionMansongGoodsModel();

            $retval = $promotion_mansong_goods->save([
                'status' => 3
            ], [
                'mansong_id' => $mansong_id
            ]);
        }
        return $retval;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::closePromotionDiscount()
     */
    public function delPromotionMansong($mansong_id)
    {
        $promotion_mansong = new VslPromotionMansongModel();
        $promotion_mansong_goods = new VslPromotionMansongGoodsModel();
        $promot_mansong_rule = new VslPromotionMansongRuleModel();
        $promotion_mansong->startTrans();
        try {
            $mansong_id_array = explode(',', $mansong_id);
            foreach ($mansong_id_array as $k => $v) {
                $status = $promotion_mansong->getInfo([
                    'mansong_id' => $v
                ], 'status');
                if ($status['status'] == 1) {
                    $promotion_mansong->rollback();
                    return -1;
                }
                $promotion_mansong->destroy($v);
                $promotion_mansong_goods->destroy([
                    'mansong_id' => $v
                ]);
                $promot_mansong_rule->destroy([
                    'mansong_id' => $v
                ]);
            }
            $promotion_mansong->commit();
            return 1;
        } catch (Exception $e) {
            $promotion_mansong->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 得到店铺的满额包邮信息
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::getPromotionFullMail()
     */
    public function getPromotionFullMail($shop_id)
    {
        $promotion_fullmail = new VslPromotionFullMailModel();
        $mail_count = $promotion_fullmail->getCount([
            "shop_id" => $shop_id,
            "website_id" => $this->website_id
        ]);
        if ($mail_count == 0) {
            $data = array(
                'shop_id' => $shop_id,
                'is_open' => 0,
                'full_mail_money' => 0,
                'no_mail_province_id_array' => '',
                'no_mail_city_id_array' => '',
                'create_time' => time(),
                'website_id' => $this->website_id
            );
            $promotion_fullmail->save($data);
        }
        $mail_obj = $promotion_fullmail->getInfo([
            "shop_id" => $shop_id,
            "website_id" => $this->website_id
        ]);
        return $mail_obj;
    }

    /**
     * 更新或添加满额包邮的信息
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::updatePromotionFullMail()
     */
    public function updatePromotionFullMail($shop_id, $is_open, $full_mail_money, $no_mail_province_id_array, $no_mail_city_id_array)
    {
        $full_mail_model = new VslPromotionFullMailModel();
        $data = array(
            'is_open' => $is_open,
            'full_mail_money' => $full_mail_money,
            'modify_time' => time(),
            'no_mail_province_id_array' => $no_mail_province_id_array,
            'no_mail_city_id_array' => $no_mail_city_id_array
        );
        $full_mail_model->save($data, [
            "shop_id" => $shop_id,
            "website_id" => $this->website_id
        ]);
        return 1;
    }

    /*
     * 开启满减送设置
     *
     */
    public function setConfig($is_use)
    {
        $ConfigService = new AddonsConfigService();
        $ManSong_info = $ConfigService->getAddonsConfig("fullcut");
        if (!empty($ManSong_info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_use, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'fullcut'
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig('', '满减送设置', $is_use, 'fullcut');
        }
        return $res;
    }

    /*
     * 获取满减送设置
     *
     */
    public function getManSongSite($website_id)
    {
        $config = new AddonsConfigService();
        $manSong = $config->getAddonsConfig("fullcut");
        return $manSong;
    }

    /**
     * 获取满减送活动，no page no limit no
     */
    public function getManSong(array $condition = [])
    {
        $man_song_model = new VslPromotionMansongModel();
        return $man_song_model::all($condition);
    }

    /**
     * 根据购物车的商品信息或者对应的满减送活动优惠
     * @param array $cart_sku_info
     *
     * @return array $full_cut_info
     */
    public function getCartManSong(array $cart_sku_info)
    {
        if (!getAddons('fullcut', $this->website_id)) {
            return [];
        }
        $man_song_model = new VslPromotionMansongModel();
        $shop_id_array = [0];//为了能获取平台设置的全平台可用的活动
        $full_cut_info = [];//保存可以用的满减活动信息
        foreach ($cart_sku_info as $shop_id => $sku_info) {
            if (!in_array($shop_id, $shop_id_array)) {
                $shop_id_array[] = $shop_id;
            }
        }

        $condition['shop_id'] = ['IN', $shop_id_array];
        $condition['website_id'] = ['=', $this->website_id];
        $condition['status'] = 1;
        $condition['start_time'] = ['<=', time()];
        $condition['end_time'] = ['>=', time()];
        $man_song_info = $man_song_model::all($condition);
        unset($shop_id_array, $condition);

        //全平台的时候就要多循环2次$cart_sku_info，第一次计算每个店铺的总价格，第二次比较每个店铺的总价格
        //仅限本店使用的情况直接用循环$cart_sku_info[$info->shop_id]就可以计算总价格
        foreach ($man_song_info as $k => $info) {
            if ($info->range == 1 || $info->range == 3) {//仅本店可以使用
                if (empty($cart_sku_info[$info->shop_id])) {
                    continue;
                }
                if ($info->range_type == 1) {//全部商品可用
                    $total_price[$info->shop_id] = 0.00;
                    foreach ($cart_sku_info[$info->shop_id] as $sku) {
                        $total_price[$info->shop_id] += $sku['discount_price'] * $sku['num'];
                    }
                    //计算符合sku占满减优惠比率
                    if ($total_price[$info->shop_id] > 0) {
                        foreach ($cart_sku_info[$info->shop_id] as $sku_id => $sku_info) {
                            $full_cut_info[$info->shop_id]['discount_percent'][$sku_id] = round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$info->shop_id], 2);
                        }
                    }
                    foreach ($info->rules as $rule) {
                        if ($total_price[$info->shop_id] >= $rule->price) {//满足 满额的要求
                            if (empty($full_cut_info[$info->shop_id]) || ($info->shop_id == $full_cut_info[$info->shop_id]['full_cut']['shop_id'] && ($full_cut_info[$info->shop_id]['full_cut']['level'] < $info->level || ($full_cut_info[$info->shop_id]['full_cut']['price'] < $rule->price && $full_cut_info[$info->shop_id]['full_cut']['level'] == $info->level))) || ($info->shop_id == $info->shop_id && $full_cut_info[$info->shop_id]['full_cut']['shop_id'] != $info->shop_id)) {
                                //当前活动id == 当前保存活动的店铺id时 高等级 > price
                                //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                                $full_cut_info[$info->shop_id]['full_cut']['man_song_id'] = $info->mansong_id;
                                $full_cut_info[$info->shop_id]['full_cut']['rule_id'] = $rule->rule_id;
                                $full_cut_info[$info->shop_id]['full_cut']['man_song_name'] = $info->mansong_name;
                                $full_cut_info[$info->shop_id]['full_cut']['discount'] = $rule->discount;
                                $full_cut_info[$info->shop_id]['full_cut']['price'] = $rule->price;
                                $full_cut_info[$info->shop_id]['full_cut']['shop_id'] = $info->shop_id;
                                $full_cut_info[$info->shop_id]['full_cut']['goods_limit'] = [];
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                $full_cut_info[$info->shop_id]['full_cut']['give_point'] = $rule->give_point;
                                $full_cut_info[$info->shop_id]['full_cut']['range_type'] = $info->range_type;
                                $full_cut_info[$info->shop_id]['full_cut']['level'] = $info->level;
                                $full_cut_info[$info->shop_id]['full_cut']['give_coupon'] = $rule->give_coupon;
                                $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                                $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                                if ($rule->free_shipping == 1) {//包邮
                                    $full_cut_info[$info->shop_id]['shipping']['man_song_id'] = $info->mansong_id;
                                    $full_cut_info[$info->shop_id]['shipping']['rule_id'] = $rule->rule_id;
                                    $full_cut_info[$info->shop_id]['shipping']['man_song_name'] = $info->mansong_name;
                                    $full_cut_info[$info->shop_id]['shipping']['free_shipping'] = true;
                                    $full_cut_info[$info->shop_id]['shipping']['price'] = $rule->price;
                                    $full_cut_info[$info->shop_id]['shipping']['shop_id'] = $info->shop_id;
                                    $full_cut_info[$info->shop_id]['shipping']['goods_limit'] = [];
                                    $full_cut_info[$info->shop_id]['shipping']['range_type'] = $info->range_type;
                                } else {
                                    unset($full_cut_info[$info->shop_id]['shipping']);
                                }
                                if ($rule->give_coupon && getAddons('coupontype', $this->website_id)) {
                                    $coupon_type_model = new VslCouponTypeModel();
                                    $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                    $full_cut_info[$info->shop_id]['full_cut']['coupon_type_name'] = $coupon_type_model::get($rule->give_coupon)['coupon_name'];
                                } else {
                                    $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = '';
                                    $full_cut_info[$info->shop_id]['full_cut']['coupon_type_name'] = '';
                                }
                                //礼品券
                                if ($rule->gift_card_id && getAddons('giftvoucher', $this->website_id)) {
                                    $gift_voucher = new VslGiftVoucherModel();
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                                    $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'giftvoucher_name')['giftvoucher_name'];
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_voucher_name'] = $giftvoucher_name;//送优惠券
                                }else{
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = '';
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_voucher_name'] = '';
                                }
                                //赠品
                                if ($rule->gift_id && getAddons('giftvoucher', $this->website_id)) {
                                    $gift_mdl = new VslPromotionGiftModel();
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                                    $gift_name = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name')['gift_name'];
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_name'] = $gift_name;//送优惠券
                                }else{
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = '';
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_name'] = '';
                                }
                            }
                        }
                    }
                    continue;
                }
                if ($info->range_type == 0) {//部分商品可用
                    $goods_id_array = [];
                    foreach ($info->goods as $goods) {
                        $goods_id_array[] = $goods->goods_id;
                    }

                    $total_price[$info->shop_id] = 0.00;
                    $in_array_number = 0;
                    $all_goods_in_promotion = true;
                    foreach ($cart_sku_info[$info->shop_id] as $sku) {
                        if (in_array($sku['goods_id'], $goods_id_array)) {
                            $in_array_number++;
                            $total_price[$info->shop_id] += $sku['discount_price'] * $sku['num'];
                        } else {
                            $all_goods_in_promotion = false;
                        }
                    }

                    //计算符合sku占满减优惠比率
                    if ($total_price[$info->shop_id] > 0) {
                        foreach ($cart_sku_info[$info->shop_id] as $sku_id => $sku_info) {
                            if (in_array($sku_info['goods_id'], $goods_id_array)) {
                                $full_cut_info[$info->shop_id]['discount_percent'][$sku_id] = round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$info->shop_id], 2);
                            }
                        }
                    }
                    foreach ($info->rules as $rule) {
                        if ($total_price[$info->shop_id] >= $rule->price && $in_array_number > 0) {//满足 满额的要求 所有商品在满减活动指定商品列表中(1-24修改为活动内的商品满足满额要求就行了)
                            if (empty($full_cut_info[$info->shop_id]) ||
                                ($info->shop_id == $full_cut_info[$info->shop_id]['full_cut']['shop_id'] && ($full_cut_info[$info->shop_id]['full_cut']['level'] < $info->level || ($full_cut_info[$info->shop_id]['full_cut']['price'] < $rule->price && $full_cut_info[$info->shop_id]['full_cut']['level'] == $info->level))) ||
                                ($info->shop_id == $info->shop_id && $full_cut_info[$info->shop_id]['full_cut']['shop_id'] != $info->shop_id)) {
                                //当前活动id == 当前保存活动的店铺id时 高等级 > price
                                //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                                $full_cut_info[$info->shop_id]['full_cut']['man_song_id'] = $info->mansong_id;
                                $full_cut_info[$info->shop_id]['full_cut']['rule_id'] = $rule->rule_id;
                                $full_cut_info[$info->shop_id]['full_cut']['man_song_name'] = $info->mansong_name;
                                $full_cut_info[$info->shop_id]['full_cut']['discount'] = $rule->discount;
                                $full_cut_info[$info->shop_id]['full_cut']['price'] = $rule->price;
                                $full_cut_info[$info->shop_id]['full_cut']['shop_id'] = $info->shop_id;
                                $full_cut_info[$info->shop_id]['full_cut']['goods_limit'] = $goods_id_array;
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                $full_cut_info[$info->shop_id]['full_cut']['give_point'] = $rule->give_point;
                                $full_cut_info[$info->shop_id]['full_cut']['range_type'] = $info->range_type;
                                $full_cut_info[$info->shop_id]['full_cut']['level'] = $info->level;
                                $full_cut_info[$info->shop_id]['full_cut']['give_coupon'] = $rule->give_coupon;
                                $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                                $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                                if ($rule->free_shipping == 1) {//包邮
                                    $full_cut_info[$info->shop_id]['shipping']['man_song_id'] = $info->mansong_id;
                                    $full_cut_info[$info->shop_id]['shipping']['rule_id'] = $rule->rule_id;
                                    $full_cut_info[$info->shop_id]['shipping']['man_song_name'] = $info->mansong_name;
                                    $full_cut_info[$info->shop_id]['shipping']['free_shipping'] = true;
                                    $full_cut_info[$info->shop_id]['shipping']['price'] = $rule->price;
                                    $full_cut_info[$info->shop_id]['shipping']['shop_id'] = $info->shop_id;
                                    $full_cut_info[$info->shop_id]['shipping']['goods_limit'] = $goods_id_array;
                                    $full_cut_info[$info->shop_id]['shipping']['range_type'] = $info->range_type;
                                } else {
                                    unset($full_cut_info[$info->shop_id]['shipping']);
                                }
                                if ($rule->give_coupon && getAddons('coupontype', $this->website_id)) {
                                    $coupon_type_model = new VslCouponTypeModel();
                                    $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                    $full_cut_info[$info->shop_id]['full_cut']['coupon_type_name'] = $coupon_type_model::get($rule->give_coupon)['coupon_name'];
                                } else {
                                    $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = '';
                                    $full_cut_info[$info->shop_id]['full_cut']['coupon_type_name'] = '';
                                }
                                //礼品券
                                if ($rule->gift_card_id && getAddons('giftvoucher', $this->website_id)) {
                                    $gift_voucher = new VslGiftVoucherModel();
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                                    $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'giftvoucher_name')['giftvoucher_name'];
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_voucher_name'] = $giftvoucher_name;//送优惠券
                                }else{
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = '';
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_voucher_name'] = '';
                                }
                                //赠品
                                if ($rule->gift_id && getAddons('giftvoucher', $this->website_id)) {
                                    $gift_mdl = new VslPromotionGiftModel();
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                                    $gift_name = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name')['gift_name'];
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_name'] = $gift_name;//送优惠券
                                }else{
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = '';
                                    $full_cut_info[$info->shop_id]['full_cut']['gift_name'] = '';
                                }
                            }
                        }
                    }
                    continue;
                }
                continue;
            }

            if ($info->range == 2) {//全平台设置的全平台可用
                $total_price = [];
                if ($info->range_type == 1) {//全部商品可用
                    foreach ($cart_sku_info as $shop_id => $sku) {
                        $total_price[$shop_id] = 0.00;
                        foreach ($sku as $sku_id => $sku_info) {
                            $total_price[$shop_id] += $sku_info['discount_price'] * $sku_info['num'];
                        }
                        //计算每个sku占满减优惠比率
                        if ($total_price[$shop_id] > 0) {
                            foreach ($cart_sku_info[$shop_id] as $sku_id => $sku_info) {
                                $full_cut_info[$shop_id]['discount_percent'][$sku_id] = round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$shop_id], 2);
                            }
                        }
                    }
                    foreach ($info->rules as $rule) {
                        foreach ($total_price as $shop_id => $sub_total_price) {
                            if ($sub_total_price >= $rule->price) {//满足 满额的要求
                                if (empty($full_cut_info[$shop_id]) ||
                                    ($info->shop_id == $full_cut_info[$shop_id]['full_cut']['shop_id'] && ($full_cut_info[$shop_id]['full_cut']['level'] < $info->level || ($full_cut_info[$shop_id]['full_cut']['price'] < $rule->price && $full_cut_info[$shop_id]['full_cut']['level'] == $info->level))) ||
                                    ($info->shop_id == $shop_id && $full_cut_info[$shop_id]['full_cut']['shop_id'] != $shop_id)) {
                                    //当前活动id == 当前保存活动的店铺id时 高等级 > price
                                    //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                                    $full_cut_info[$shop_id]['full_cut']['man_song_id'] = $info->mansong_id;
                                    $full_cut_info[$shop_id]['full_cut']['rule_id'] = $rule->rule_id;
                                    $full_cut_info[$shop_id]['full_cut']['man_song_name'] = $info->mansong_name;
                                    $full_cut_info[$shop_id]['full_cut']['discount'] = $rule->discount;
                                    $full_cut_info[$shop_id]['full_cut']['price'] = $rule->price;
                                    $full_cut_info[$shop_id]['full_cut']['shop_id'] = $info->shop_id;
                                    $full_cut_info[$shop_id]['full_cut']['goods_limit'] = [];
                                    $full_cut_info[$shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                    $full_cut_info[$shop_id]['full_cut']['give_point'] = $rule->give_point;
                                    $full_cut_info[$shop_id]['full_cut']['range_type'] = $info->range_type;
                                    $full_cut_info[$shop_id]['full_cut']['level'] = $info->level;
                                    $full_cut_info[$shop_id]['full_cut']['give_coupon'] = $rule->give_coupon;
                                    $full_cut_info[$shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                                    $full_cut_info[$shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                                    if ($rule->free_shipping == 1) {
                                        $full_cut_info[$shop_id]['shipping']['man_song_id'] = $info->mansong_id;
                                        $full_cut_info[$shop_id]['shipping']['rule_id'] = $rule->rule_id;
                                        $full_cut_info[$shop_id]['shipping']['man_song_name'] = $info->mansong_name;
                                        $full_cut_info[$shop_id]['shipping']['free_shipping'] = true;
                                        $full_cut_info[$shop_id]['shipping']['price'] = $rule->price;
                                        $full_cut_info[$shop_id]['shipping']['shop_id'] = $info->shop_id;
                                        $full_cut_info[$shop_id]['shipping']['goods_limit'] = [];
                                        $full_cut_info[$shop_id]['shipping']['range_type'] = $info->range_type;
                                    } else {
                                        unset($full_cut_info[$shop_id]['shipping']);
                                    }
                                    if ($rule->give_coupon && getAddons('coupontype', $this->website_id)) {
                                        $coupon_type_model = new VslCouponTypeModel();
                                        $full_cut_info[$shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                        $full_cut_info[$shop_id]['full_cut']['coupon_type_name'] = $coupon_type_model::get($rule->give_coupon)['coupon_name'];
                                    } else {
                                        $full_cut_info[$shop_id]['full_cut']['coupon_type_id'] = '';
                                        $full_cut_info[$shop_id]['full_cut']['coupon_type_name'] = '';
                                    }
                                    //礼品券
                                    if ($rule->gift_card_id && getAddons('giftvoucher', $this->website_id)) {
                                        $gift_voucher = new VslGiftVoucherModel();
                                        $full_cut_info[$shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                                        $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'giftvoucher_name')['giftvoucher_name'];
                                        $full_cut_info[$shop_id]['full_cut']['gift_voucher_name'] = $giftvoucher_name;//送优惠券
                                    }else{
                                        $full_cut_info[$shop_id]['full_cut']['gift_card_id'] = '';
                                        $full_cut_info[$shop_id]['full_cut']['gift_voucher_name'] = '';
                                    }
                                    //赠品
                                    if ($rule->gift_id && getAddons('giftvoucher', $this->website_id)) {
                                        $gift_mdl = new VslPromotionGiftModel();
                                        $full_cut_info[$shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                                        $gift_name = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name')['gift_name'];
                                        $full_cut_info[$shop_id]['full_cut']['gift_name'] = $gift_name;//送优惠券
                                    }else{
                                        $full_cut_info[$shop_id]['full_cut']['gift_id'] = '';
                                        $full_cut_info[$shop_id]['full_cut']['gift_name'] = '';
                                    }
                                }
                            }
                        }
                    }
                    continue;
                }

                if ($info->range_type == 0) {//部分商品可用
                    $goods_id_array = [];
                    foreach ($info->goods as $goods) {
                        $goods_id_array[] = $goods->goods_id;
                    }
                    $total_price = [];
                    $in_array_number = 0;

                    foreach ($cart_sku_info as $shop_id => $sku) {
                        $total_price[$shop_id] = 0.00;
                        $all_goods_in_promotion = true;
                        foreach ($sku as $sku_id => $sku_info) {
                            if (in_array($sku_info['goods_id'], $goods_id_array)) {
                                $in_array_number++;
                                $total_price[$shop_id] += $sku_info['discount_price'] * $sku_info['num'];
                            } else {
                                $all_goods_in_promotion = false;
                            }
                        }
                        //计算符合sku占满减优惠比率
                        if ($total_price[$shop_id] > 0) {
                            foreach ($cart_sku_info[$shop_id] as $sku_id => $sku_info) {
                                if (in_array($sku_info['goods_id'], $goods_id_array)) {
                                    $full_cut_info[$shop_id]['discount_percent'][$sku_id] = round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$shop_id], 2);
                                }
                            }
                        }
                    }
                    foreach ($info->rules as $rule) {
                        foreach ($total_price as $shop_id => $sub_total_price) {
                            if ($sub_total_price >= $rule->price && $in_array_number > 0) {//满足 满额的要求 所有商品在满减活动指定商品列表中(1-24修改为活动内的商品满足满额要求就行了)
                                if (empty($full_cut_info[$shop_id]) ||
                                    ($info->shop_id == $full_cut_info[$shop_id]['full_cut']['shop_id'] && ($full_cut_info[$shop_id]['full_cut']['level'] < $info->level || ($full_cut_info[$shop_id]['full_cut']['price'] < $rule->price && $full_cut_info[$shop_id]['full_cut']['level'] == $info->level))) ||
                                    ($info->shop_id == $shop_id && $full_cut_info[$shop_id]['full_cut']['shop_id'] != $shop_id)) {
                                    //当前活动id == 当前保存活动的店铺id时 高等级 > price
                                    //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                                    $full_cut_info[$shop_id]['full_cut']['man_song_id'] = $info->mansong_id;
                                    $full_cut_info[$shop_id]['full_cut']['rule_id'] = $rule->rule_id;
                                    $full_cut_info[$shop_id]['full_cut']['man_song_name'] = $info->mansong_name;
                                    $full_cut_info[$shop_id]['full_cut']['discount'] = $rule->discount;
                                    $full_cut_info[$shop_id]['full_cut']['price'] = $rule->price;
                                    $full_cut_info[$shop_id]['full_cut']['shop_id'] = $info->shop_id;
                                    $full_cut_info[$shop_id]['full_cut']['goods_limit'] = $goods_id_array;
                                    $full_cut_info[$shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                    $full_cut_info[$shop_id]['full_cut']['give_point'] = $rule->give_point;
                                    $full_cut_info[$shop_id]['full_cut']['range_type'] = $info->range_type;
                                    $full_cut_info[$shop_id]['full_cut']['level'] = $info->level;

                                    if ($rule->free_shipping == 1) {
                                        $full_cut_info[$shop_id]['shipping']['man_song_id'] = $info->mansong_id;
                                        $full_cut_info[$shop_id]['shipping']['rule_id'] = $rule->rule_id;
                                        $full_cut_info[$shop_id]['shipping']['man_song_name'] = $info->mansong_name;
                                        $full_cut_info[$shop_id]['shipping']['free_shipping'] = true;
                                        $full_cut_info[$shop_id]['shipping']['price'] = $rule->price;
                                        $full_cut_info[$shop_id]['shipping']['shop_id'] = $info->shop_id;
                                        $full_cut_info[$shop_id]['shipping']['goods_limit'] = $goods_id_array;
                                        $full_cut_info[$shop_id]['shipping']['range_type'] = $info->range_type;
                                    } else {
                                        unset($full_cut_info[$shop_id]['shipping']);
                                    }
                                }

                            }
                        }
                    }
                    continue;
                }
                continue;
            }
        }
        return $full_cut_info;
    }

    /**
     * 结算页面满件送
     * @param array $cart_sku_info
     * @return array
     * @throws \think\Exception\DbException
     */
    public function getPaymentFullCut(array $cart_sku_info)
    {
        if (!getAddons('fullcut', $this->website_id)) {
            return [];
        }
        $man_song_model = new VslPromotionMansongModel();
        $shop_id_array = [0];//为了能获取平台设置的全平台可用的活动
        $full_cut_info = [];//保存可以用的满减活动信息
        foreach ($cart_sku_info as $shop_id => $sku_info) {
            if (!in_array($shop_id, $shop_id_array)) {
                $shop_id_array[] = $shop_id;
            }
        }

        $condition['shop_id'] = ['IN', $shop_id_array];
        $condition['website_id'] = ['=', $this->website_id];
        $condition['status'] = 1;
        $condition['start_time'] = ['<=', time()];
        $condition['end_time'] = ['>=', time()];
        $man_song_info = $man_song_model::all($condition);
//        p(Db::table('')->getLastSql());exit;
        unset($shop_id_array, $condition);

        //全平台的时候就要多循环2次$cart_sku_info，第一次计算每个店铺的总价格，第二次比较每个店铺的总价格
        //仅限本店使用的情况直接用循环$cart_sku_info[$info->shop_id]就可以计算总价格
        foreach ($man_song_info as $k => $info) {
            if ($info->range == 1 || $info->range == 3) {//仅本店可以使用
                if (empty($cart_sku_info[$info->shop_id])) {
                    continue;
                }
                if ($info->range_type == 1) {//全部商品可用
                    $total_price[$info->shop_id] = 0.00;
                    foreach ($cart_sku_info[$info->shop_id] as $sku) {
                        $total_price[$info->shop_id] += $sku['discount_price'] * $sku['num'];
                    }
                    //计算符合sku占满减优惠比率
                    if ($total_price[$info->shop_id] > 0) {
                        foreach ($cart_sku_info[$info->shop_id] as $sku_id => $sku_info) {
                            $full_cut_info[$info->shop_id]['discount_percent'][$sku_id] = round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$info->shop_id], 2);
                        }
                    }
                    foreach ($info->rules as $rule) {
                        if ($total_price[$info->shop_id] >= $rule->price) {//满足 满额的要求
                            if (empty($full_cut_info[$info->shop_id]) ||
                                ($info->shop_id == $full_cut_info[$info->shop_id]['shop_id'] && ($full_cut_info[$info->shop_id]['level'] < $info->level || ($full_cut_info[$info->shop_id]['price'] < $rule->price && $full_cut_info[$info->shop_id]['level'] == $info->level))) ||
                                ($info->shop_id == $info->shop_id && $full_cut_info[$info->shop_id]['shop_id'] != $info->shop_id)) {
                                //当前活动id == 当前保存活动的店铺id时 高等级 > price
                                //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                                $full_cut_info[$info->shop_id]['man_song_id'] = $info->mansong_id;
                                $full_cut_info[$info->shop_id]['rule_id'] = $rule->rule_id;
                                $full_cut_info[$info->shop_id]['man_song_name'] = $info->mansong_name;
                                $full_cut_info[$info->shop_id]['discount'] = $rule->discount;
                                $full_cut_info[$info->shop_id]['price'] = $rule->price;
                                $full_cut_info[$info->shop_id]['shop_id'] = $info->shop_id;
                                $full_cut_info[$info->shop_id]['goods_limit'] = [];
                                if(getAddons('coupontype', $this->website_id)){
                                    //判断当前优惠券是否还有领取数量
                                    $coupon = new VslCouponModel();
                                    $coupon_type = new VslCouponTypeModel();
                                    $coupon_type_num = $coupon_type->getInfo(['coupon_type_id' => $rule->give_coupon], 'count, max_fetch,coupon_name');
                                    //当前用户是否已经达到领取数目
                                    $user_coupon_total = $coupon->where(['uid'=>$this->uid, 'coupon_type_id'=>$rule->give_coupon])->count();
                                    //当前优惠券所有用户领取的总数
                                    $coupon_total = $coupon->where(['coupon_type_id'=>$rule->give_coupon])->count();
                                    //优惠券用户领取的数量不能大于每个用户的限制领取数量，否则不显示   或者所有用户领取的总量不能超过总数
                                    if($user_coupon_total < $coupon_type_num['max_fetch'] && $coupon_total < $coupon_type_num['count']){
                                        $full_cut_info[$info->shop_id]['coupon_type_id'] = $rule->give_coupon;//送优惠券
                                        $full_cut_info[$info->shop_id]['coupon_type_name'] = $coupon_type_num['coupon_name'];//送优惠券
                                    }else{
                                        $full_cut_info[$info->shop_id]['coupon_type_id'] = 0;//送优惠券
                                        $full_cut_info[$info->shop_id]['coupon_type_name'] = '';//送优惠券
                                    }
                                }
                                if(getAddons('giftvoucher', $this->website_id)){
                                    $gift_voucher = new VslGiftVoucherModel();
                                    $gift_voucher_record = new VslGiftVoucherRecordsModel();
                                    $giftvoucher_num = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'count, max_fetch, giftvoucher_name');
                                    //当前用户领取礼品券的总量  所有用户领取礼品券的总量
                                    $user_gift_voucher_total = $gift_voucher_record->where(['gift_voucher_id'=>$rule->gift_card_id, 'uid'=>$this->uid])->count();
                                    $gift_voucher_total = $gift_voucher_record->where(['gift_voucher_id'=>$rule->gift_card_id])->count();
                                    if(($user_gift_voucher_total < $giftvoucher_num['max_fetch'] && $gift_voucher_total < $giftvoucher_num['count']) || ($giftvoucher_num['max_fetch'] === 0 && $giftvoucher_num['count'] === 0)){
                                        $full_cut_info[$info->shop_id]['gift_card_id'] = $rule->gift_card_id;//送礼品券
                                        //获取礼品券的名字
                                        $full_cut_info[$info->shop_id]['gift_voucher_name'] = $giftvoucher_num['giftvoucher_name'];//送礼品券
                                    }else{
                                        $full_cut_info[$info->shop_id]['gift_card_id'] = 0;//送礼品券
                                        $full_cut_info[$info->shop_id]['gift_voucher_name'] = '';//送礼品券
                                    }
                                }else{
                                    $full_cut_info[$info->shop_id]['gift_voucher_name'] = '';//送礼品券
                                }
                                if(getAddons('gift', $this->website_id)){
                                    //获取赠品的名字
                                    $gift_mdl = new VslPromotionGiftModel();
                                    $member_gift = new VslMemberGiftModel();
                                    $gift_num = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name, stock');
                                    $gift_total = $member_gift->where(['promotion_gift_id'=>$rule->gift_id])->count();
                                    if($gift_num['stock']){
                                        $full_cut_info[$info->shop_id]['gift_id'] = $rule->gift_id;
                                        $full_cut_info[$info->shop_id]['gift_name'] = $gift_num['gift_name'];//送赠品
                                    }else{
                                        $full_cut_info[$info->shop_id]['gift_id'] = 0;
                                        $full_cut_info[$info->shop_id]['gift_name'] = '';//送赠品
                                    }
                                }else{
                                    $full_cut_info[$info->shop_id]['gift_name'] = '';//送赠品
                                }

                                $full_cut_info[$info->shop_id]['give_point'] = $rule->give_point;
                                $full_cut_info[$info->shop_id]['range_type'] = $info->range_type;
                                $full_cut_info[$info->shop_id]['free_shipping'] = $rule->free_shipping;
                                $full_cut_info[$info->shop_id]['level'] = $info->level;
                            }
                        }
                    }
                    continue;
                }
                if ($info->range_type == 0) {//部分商品可用
                    $goods_id_array = [];
                    foreach ($info->goods as $goods) {
                        $goods_id_array[] = $goods->goods_id;
                    }

                    $total_price[$info->shop_id] = 0.00;
                    $in_array_number = 0;
                    $all_goods_in_promotion = true;
                    $full_cut_count = [];
                    foreach ($cart_sku_info[$info->shop_id] as $sku) {
                        if (in_array($sku['goods_id'], $goods_id_array)) {
                            $in_array_number++;
                            $full_cut_count[] = $sku['sku_id'];
                            $total_price[$info->shop_id] += $sku['discount_price'] * $sku['num'];
                        } else {
                            $all_goods_in_promotion = false;
                        }
                    }
                    //计算符合sku占满减优惠比率
                    $count = count($full_cut_count);
                    $i = 0;
                    $allPercent = 0;
                    if ($total_price[$info->shop_id] > 0) {
                        foreach ($cart_sku_info[$info->shop_id] as $sku_id => $sku_info) {
                            
                            if (in_array($sku_info['goods_id'], $goods_id_array)) {
                                $i++;
                                if($i != $count){
                                    $allPercent += round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$info->shop_id], 2);
                                    $full_cut_info[$info->shop_id]['discount_percent'][$sku_id] = round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$info->shop_id], 2);
                                }else{
                                    $full_cut_info[$info->shop_id]['discount_percent'][$sku_id] = 1- $allPercent;
                                }
                                
                            }else{
                                $full_cut_info[$info->shop_id]['discount_percent'][$sku_id] = 0;
                            }
                        }
                    }
                    foreach ($info->rules as $rule) {
                        if ($total_price[$info->shop_id] >= $rule->price && $in_array_number > 0) {//满足 满额的要求 所有商品在满减活动指定商品列表中(1-24修改为活动内的商品满足满额要求就行了)
                            if (empty($full_cut_info[$info->shop_id]) ||
                                ($info->shop_id == $full_cut_info[$info->shop_id]['shop_id'] && ($full_cut_info[$info->shop_id]['level'] < $info->level || ($full_cut_info[$info->shop_id]['price'] < $rule->price && $full_cut_info[$info->shop_id]['level'] == $info->level))) ||
                                ($info->shop_id == $info->shop_id && $full_cut_info[$info->shop_id]['shop_id'] != $info->shop_id)) {
                                //当前活动id == 当前保存活动的店铺id时 高等级 > price
                                //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                                $full_cut_info[$info->shop_id]['man_song_id'] = $info->mansong_id;
                                $full_cut_info[$info->shop_id]['rule_id'] = $rule->rule_id;
                                $full_cut_info[$info->shop_id]['man_song_name'] = $info->mansong_name;
                                $full_cut_info[$info->shop_id]['discount'] = $rule->discount;
                                $full_cut_info[$info->shop_id]['price'] = $rule->price;
                                $full_cut_info[$info->shop_id]['shop_id'] = $info->shop_id;
                                $full_cut_info[$info->shop_id]['goods_limit'] = $goods_id_array;
                                if(getAddons('coupontype', $this->website_id)){
                                    //判断当前优惠券是否还有领取数量
                                    $coupon = new VslCouponModel();
                                    $coupon_type = new VslCouponTypeModel();
                                    $coupon_type_num = $coupon_type->getInfo(['coupon_type_id' => $rule->give_coupon], 'count, max_fetch,coupon_name');
                                    //当前用户是否已经达到领取数目
                                    $user_coupon_total = $coupon->where(['uid'=>$this->uid, 'coupon_type_id'=>$rule->give_coupon])->count();
                                    //当前优惠券所有用户领取的总数
                                    $coupon_total = $coupon->where(['coupon_type_id'=>$rule->give_coupon])->count();
                                    //优惠券用户领取的数量不能大于每个用户的限制领取数量，否则不显示   或者所有用户领取的总量不能超过总数
                                    if($user_coupon_total < $coupon_type_num['max_fetch'] && $coupon_total < $coupon_type_num['count']){
                                        $full_cut_info[$info->shop_id]['coupon_type_id'] = $rule->give_coupon;//送优惠券
                                        $full_cut_info[$info->shop_id]['coupon_type_name'] = $coupon_type_num['coupon_name'];//送优惠券
                                    }else{
                                        $full_cut_info[$info->shop_id]['coupon_type_id'] = 0;//送优惠券
                                        $full_cut_info[$info->shop_id]['coupon_type_name'] = '';//送优惠券
                                    }
                                }
                                if(getAddons('giftvoucher', $this->website_id)){
                                    $gift_voucher = new VslGiftVoucherModel();
                                    $gift_voucher_record = new VslGiftVoucherRecordsModel();
                                    $giftvoucher_num = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'count, max_fetch, giftvoucher_name');
                                    //当前用户领取礼品券的总量  所有用户领取礼品券的总量
                                    $user_gift_voucher_total = $gift_voucher_record->where(['gift_voucher_id'=>$rule->gift_card_id, 'uid'=>$this->uid])->count();
                                    $gift_voucher_total = $gift_voucher_record->where(['gift_voucher_id'=>$rule->gift_card_id])->count();
                                    if(($user_gift_voucher_total < $giftvoucher_num['max_fetch'] && $gift_voucher_total < $giftvoucher_num['count']) || ($giftvoucher_num['max_fetch'] === 0 && $giftvoucher_num['count'] === 0)){
                                        $full_cut_info[$info->shop_id]['gift_card_id'] = $rule->gift_card_id;//送礼品券
                                        //获取礼品券的名字
                                        $full_cut_info[$info->shop_id]['gift_voucher_name'] = $giftvoucher_num['giftvoucher_name'];//送礼品券
                                    }else{
                                        $full_cut_info[$info->shop_id]['gift_card_id'] = 0;//送礼品券
                                        $full_cut_info[$info->shop_id]['gift_voucher_name'] = '';//送礼品券
                                    }
                                }else{
                                    $full_cut_info[$info->shop_id]['gift_voucher_name'] = '';//送礼品券
                                }
                                if(getAddons('gift', $this->website_id)){
                                    //获取赠品的名字
                                    $gift_mdl = new VslPromotionGiftModel();
                                    $member_gift = new VslMemberGiftModel();
                                    $gift_num = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name, stock');
                                    $gift_total = $member_gift->where(['promotion_gift_id'=>$rule->gift_id])->count();
                                    if($gift_num['stock']){
                                        $full_cut_info[$info->shop_id]['gift_id'] = $rule->gift_id;
                                        $full_cut_info[$info->shop_id]['gift_name'] = $gift_num['gift_name'];//送赠品
                                    }else{
                                        $full_cut_info[$info->shop_id]['gift_id'] = 0;
                                        $full_cut_info[$info->shop_id]['gift_name'] = '';//送赠品
                                    }
                                }else{
                                    $full_cut_info[$info->shop_id]['gift_name'] = '';//送赠品
                                }

                                $full_cut_info[$info->shop_id]['give_point'] = $rule->give_point;
                                $full_cut_info[$info->shop_id]['range_type'] = $info->range_type;
                                $full_cut_info[$info->shop_id]['free_shipping'] = $rule->free_shipping;
                                $full_cut_info[$info->shop_id]['level'] = $info->level;
                            }
                        }
                    }
                    continue;
                }
                continue;
            }

            if ($info->range == 2) {//全平台设置的全平台可用
                $total_price = [];
                if ($info->range_type == 1) {//全部商品可用
                    foreach ($cart_sku_info as $shop_id => $sku) {
                        $total_price[$shop_id] = 0.00;
                        foreach ($sku as $sku_id => $sku_info) {
                            $total_price[$shop_id] += $sku_info['discount_price'] * $sku_info['num'];
                        }
                        //计算每个sku占满减优惠比率
                        $j = 0;
                        $count = count($cart_sku_info);
                        $allPercent = 0;
                        if ($total_price[$shop_id] > 0) {
                            foreach ($cart_sku_info[$shop_id] as $sku_id => $sku_info) {
                                $j++;
                                if($j != $count){
                                    $allPercent += round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$shop_id], 2);
                                    $full_cut_info[$shop_id]['discount_percent'][$sku_id] = round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$shop_id], 2);
                                }else{
                                    $full_cut_info[$shop_id]['discount_percent'][$sku_id] = 1 - $allPercent;
                                }
                                
                            }
                        }
                    }
                    foreach ($info->rules as $rule) {
                        foreach ($total_price as $shop_id => $sub_total_price) {
                            if ($sub_total_price >= $rule->price) {//满足 满额的要求
                                if (empty($full_cut_info[$shop_id]) ||
                                    ($info->shop_id == $full_cut_info[$shop_id]['shop_id'] && ($full_cut_info[$shop_id]['level'] < $info->level || ($full_cut_info[$shop_id]['price'] < $rule->price && $full_cut_info[$shop_id]['level'] == $info->level))) ||
                                    ($info->shop_id == $shop_id && $full_cut_info[$shop_id]['shop_id'] != $shop_id)) {
                                    //当前活动id == 当前保存活动的店铺id时 高等级 > price
                                    //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                                    $full_cut_info[$shop_id]['man_song_id'] = $info->mansong_id;
                                    $full_cut_info[$shop_id]['rule_id'] = $rule->rule_id;
                                    $full_cut_info[$shop_id]['man_song_name'] = $info->mansong_name;
                                    $full_cut_info[$shop_id]['discount'] = $rule->discount;
                                    $full_cut_info[$shop_id]['price'] = $rule->price;
                                    $full_cut_info[$shop_id]['shop_id'] = $info->shop_id;
                                    $full_cut_info[$shop_id]['goods_limit'] = [];
                                    if(getAddons('coupontype', $this->website_id)){
                                        //判断当前优惠券是否还有领取数量
                                        $coupon = new VslCouponModel();
                                        $coupon_type = new VslCouponTypeModel();
                                        $coupon_type_num = $coupon_type->getInfo(['coupon_type_id' => $rule->give_coupon], 'count, max_fetch,coupon_name');
                                        //当前用户是否已经达到领取数目
                                        $user_coupon_total = $coupon->where(['uid'=>$this->uid, 'coupon_type_id'=>$rule->give_coupon])->count();
                                        //当前优惠券所有用户领取的总数
                                        $coupon_total = $coupon->where(['coupon_type_id'=>$rule->give_coupon])->count();
                                        //优惠券用户领取的数量不能大于每个用户的限制领取数量，否则不显示   或者所有用户领取的总量不能超过总数
                                        if($user_coupon_total < $coupon_type_num['max_fetch'] && $coupon_total < $coupon_type_num['count']){
                                            $full_cut_info[$info->shop_id]['coupon_type_id'] = $rule->give_coupon;//送优惠券
                                            $full_cut_info[$info->shop_id]['coupon_type_name'] = $coupon_type_num['coupon_name'];//送优惠券
                                        }else{
                                            $full_cut_info[$info->shop_id]['coupon_type_id'] = 0;//送优惠券
                                            $full_cut_info[$info->shop_id]['coupon_type_name'] = '';//送优惠券
                                        }
                                    }
                                    if(getAddons('giftvoucher', $this->website_id)){
                                        $gift_voucher = new VslGiftVoucherModel();
                                        $gift_voucher_record = new VslGiftVoucherRecordsModel();
                                        $giftvoucher_num = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'count, max_fetch, giftvoucher_name');
                                        //当前用户领取礼品券的总量  所有用户领取礼品券的总量
                                        $user_gift_voucher_total = $gift_voucher_record->where(['gift_voucher_id'=>$rule->gift_card_id, 'uid'=>$this->uid])->count();
                                        $gift_voucher_total = $gift_voucher_record->where(['gift_voucher_id'=>$rule->gift_card_id])->count();
                                        if(($user_gift_voucher_total < $giftvoucher_num['max_fetch'] && $gift_voucher_total < $giftvoucher_num['count']) || ($giftvoucher_num['max_fetch'] === 0 && $giftvoucher_num['count'] === 0)){
                                            $full_cut_info[$info->shop_id]['gift_card_id'] = $rule->gift_card_id;//送礼品券
                                            //获取礼品券的名字
                                            $full_cut_info[$info->shop_id]['gift_voucher_name'] = $giftvoucher_num['giftvoucher_name'];//送礼品券
                                        }else{
                                            $full_cut_info[$info->shop_id]['gift_card_id'] = 0;//送礼品券
                                            $full_cut_info[$info->shop_id]['gift_voucher_name'] = '';//送礼品券
                                        }
                                    }else{
                                        $full_cut_info[$info->shop_id]['gift_voucher_name'] = '';//送礼品券
                                    }
                                    if(getAddons('gift', $this->website_id)){
                                        //获取赠品的名字
                                        $gift_mdl = new VslPromotionGiftModel();
                                        $member_gift = new VslMemberGiftModel();
                                        $gift_num = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name, stock');
                                        $gift_total = $member_gift->where(['promotion_gift_id'=>$rule->gift_id])->count();
                                        if($gift_num['stock']){
                                            $full_cut_info[$info->shop_id]['gift_id'] = $rule->gift_id;
                                            $full_cut_info[$info->shop_id]['gift_name'] = $gift_num['gift_name'];//送赠品
                                        }else{
                                            $full_cut_info[$info->shop_id]['gift_id'] = 0;
                                            $full_cut_info[$info->shop_id]['gift_name'] = '';//送赠品
                                        }
                                    }else{
                                        $full_cut_info[$info->shop_id]['gift_name'] = '';//送赠品
                                    }
                                    $full_cut_info[$shop_id]['give_point'] = $rule->give_point;
                                    $full_cut_info[$shop_id]['range_type'] = $info->range_type;
                                    $full_cut_info[$shop_id]['free_shipping'] = $rule->free_shipping;
                                    $full_cut_info[$shop_id]['level'] = $info->level;
                                }
                            }
                        }
                    }
                    continue;
                }
                if ($info->range_type == 0) {//部分商品可用
                    $goods_id_array = [];
                    foreach ($info->goods as $goods) {
                        $goods_id_array[] = $goods->goods_id;
                    }
                    $total_price = [];
                    $in_array_number = 0;

                    foreach ($cart_sku_info as $shop_id => $sku) {
                        $total_price[$shop_id] = 0.00;
                        $all_goods_in_promotion = true;
                        foreach ($sku as $sku_id => $sku_info) {
                            if (in_array($sku_info['goods_id'], $goods_id_array)) {
                                $in_array_number++;
                                $total_price[$shop_id] += $sku_info['discount_price'] * $sku_info['num'];
                            } else {
                                $all_goods_in_promotion = false;
                            }
                        }
                        //计算符合sku占满减优惠比率
                        if ($total_price[$shop_id] > 0) {
                            foreach ($cart_sku_info[$shop_id] as $sku_id => $sku_info) {
                                if (in_array($sku_info['goods_id'], $goods_id_array)) {
                                    $full_cut_info[$shop_id]['discount_percent'][$sku_id] = round($sku_info['discount_price'] * $sku_info['num'] / $total_price[$shop_id], 2);
                                }else{
                                    $full_cut_info[$shop_id]['discount_percent'][$sku_id] = 0;
                                }
                            }
                        }
                    }
                    foreach ($info->rules as $rule) {
                        foreach ($total_price as $shop_id => $sub_total_price) {
                            if ($sub_total_price >= $rule->price && $in_array_number > 0) {//满足 满额的要求 所有商品在满减活动指定商品列表中(1-24修改为活动内的商品满足满额要求就行了)
                                if (empty($full_cut_info[$shop_id]) ||
                                    ($info->shop_id == $full_cut_info[$shop_id]['shop_id'] && ($full_cut_info[$shop_id]['level'] < $info->level || ($full_cut_info[$shop_id]['price'] < $rule->price && $full_cut_info[$shop_id]['level'] == $info->level))) ||
                                    ($info->shop_id == $shop_id && $full_cut_info[$shop_id]['shop_id'] != $shop_id)) {
                                    //当前活动id == 当前保存活动的店铺id时 高等级 > price
                                    //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                                    $full_cut_info[$shop_id]['man_song_id'] = $info->mansong_id;
                                    $full_cut_info[$shop_id]['rule_id'] = $rule->rule_id;
                                    $full_cut_info[$shop_id]['man_song_name'] = $info->mansong_name;
                                    $full_cut_info[$shop_id]['discount'] = $rule->discount;
                                    $full_cut_info[$shop_id]['price'] = $rule->price;
                                    $full_cut_info[$shop_id]['shop_id'] = $info->shop_id;
                                    $full_cut_info[$shop_id]['goods_limit'] = $goods_id_array;
                                    if(getAddons('coupontype', $this->website_id)){
                                        //判断当前优惠券是否还有领取数量
                                        $coupon = new VslCouponModel();
                                        $coupon_type = new VslCouponTypeModel();
                                        $coupon_type_num = $coupon_type->getInfo(['coupon_type_id' => $rule->give_coupon], 'count, max_fetch,coupon_name');
                                        //当前用户是否已经达到领取数目
                                        $user_coupon_total = $coupon->where(['uid'=>$this->uid, 'coupon_type_id'=>$rule->give_coupon])->count();
                                        //当前优惠券所有用户领取的总数
                                        $coupon_total = $coupon->where(['coupon_type_id'=>$rule->give_coupon])->count();
                                        //优惠券用户领取的数量不能大于每个用户的限制领取数量，否则不显示   或者所有用户领取的总量不能超过总数
                                        if($user_coupon_total < $coupon_type_num['max_fetch'] && $coupon_total < $coupon_type_num['count']){
                                            $full_cut_info[$shop_id]['coupon_type_id'] = $rule->give_coupon;//送优惠券
                                            $full_cut_info[$shop_id]['coupon_type_name'] = $coupon_type_num['coupon_name'];//送优惠券
                                        }else{
                                            $full_cut_info[$shop_id]['coupon_type_id'] = 0;//送优惠券
                                            $full_cut_info[$shop_id]['coupon_type_name'] = '';//送优惠券
                                        }
                                    }
                                    if(getAddons('giftvoucher', $this->website_id)){
                                        $gift_voucher = new VslGiftVoucherModel();
                                        $gift_voucher_record = new VslGiftVoucherRecordsModel();
                                        $giftvoucher_num = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'count, max_fetch, giftvoucher_name');
                                        //当前用户领取礼品券的总量  所有用户领取礼品券的总量
                                        $user_gift_voucher_total = $gift_voucher_record->where(['gift_voucher_id'=>$rule->gift_card_id, 'uid'=>$this->uid])->count();
                                        $gift_voucher_total = $gift_voucher_record->where(['gift_voucher_id'=>$rule->gift_card_id])->count();
                                        if(($user_gift_voucher_total < $giftvoucher_num['max_fetch'] && $gift_voucher_total < $giftvoucher_num['count']) || ($giftvoucher_num['max_fetch'] === 0 && $giftvoucher_num['count'] === 0)){
                                            $full_cut_info[$shop_id]['gift_card_id'] = $rule->gift_card_id;//送礼品券
                                            //获取礼品券的名字
                                            $full_cut_info[$shop_id]['gift_voucher_name'] = $giftvoucher_num['giftvoucher_name'];//送礼品券
                                        }else{
                                            $full_cut_info[$shop_id]['gift_card_id'] = 0;//送礼品券
                                            $full_cut_info[$shop_id]['gift_voucher_name'] = '';//送礼品券
                                        }
                                    }else{
                                        $full_cut_info[$shop_id]['gift_voucher_name'] = '';//送礼品券
                                    }
                                    if(getAddons('gift', $this->website_id)){
                                        //获取赠品的名字
                                        $gift_mdl = new VslPromotionGiftModel();
                                        $member_gift = new VslMemberGiftModel();
                                        $gift_num = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name, stock');
                                        $gift_total = $member_gift->where(['promotion_gift_id'=>$rule->gift_id])->count();
                                        if($gift_num['stock']){
                                            $full_cut_info[$shop_id]['gift_id'] = $rule->gift_id;
                                            $full_cut_info[$shop_id]['gift_name'] = $gift_num['gift_name'];//送赠品
                                        }else{
                                            $full_cut_info[$shop_id]['gift_id'] = 0;
                                            $full_cut_info[$shop_id]['gift_name'] = '';//送赠品
                                        }
                                    }else{
                                        $full_cut_info[$shop_id]['gift_name'] = '';//送赠品
                                    }
                                    $full_cut_info[$shop_id]['give_point'] = $rule->give_point;
                                    $full_cut_info[$shop_id]['range_type'] = $info->range_type;
                                    $full_cut_info[$shop_id]['free_shipping'] = $rule->free_shipping;
                                    $full_cut_info[$shop_id]['level'] = $info->level;
                                }
                            }
                        }
                    }
                    continue;
                }
                continue;
            }
        }
        return $full_cut_info;
    }

    /**
     * 获取商品满减送
     * @param string/int $goods_id
     */
    public function goodsFullCut($goods_id)
    {
        $man_song_goods_model = new VslPromotionMansongGoodsModel();
        $man_song_model = new VslPromotionMansongModel();
        $goods_model = new VslGoodsModel();

        $shop_id = $goods_model::get($goods_id)['shop_id'];

        // 获取全商品满减送
        $conditions = [
            'start_time' => ['ELT', time()],
            'end_time' => ['EGT', time()],
            'range_type' => 1,// 全商品可用
            'status' => 1,// 活动状态
            'website_id' => $this->website_id,
        ];
//      商品所在店鋪满减送 OR 全平台可用满减送
//           SELECT * FROM `vsl_promotion_mansong` WHERE
//          `start_time` <= 1548402295  AND
//          `end_time` >= 1548402295  AND
//          `range_type` = 1  AND
//          `status` = 1  AND
//          `website_id` = 17  AND
//          (`shop_id` = 29 OR (`shop_id` = 0 AND `range` IN (2) ) )
        $whereOr['shop_id'] = $shop_id;
        $full_cut_list = $man_song_model::all(function ($query) use ($conditions, $whereOr) {
            $whereOrAnd['shop_id'] = 0;
            $whereOrAnd['range'] = ['IN', [2]];
            $query->where($conditions)->where(function ($q1) use ($whereOr, $whereOrAnd) {
                $q1->where($whereOr)->whereOr(function ($q2) use ($whereOrAnd) {
                    $q2->where($whereOrAnd);
                });
            });
        });
        unset($conditions);

        // 通过商品id获取到满减送类型
        $full_cut_id_list = $man_song_goods_model->getQuery([
            'goods_id' => $goods_id
        ], 'mansong_id', '');

        if ($full_cut_id_list) {
            $id = [];
            foreach ($full_cut_id_list as $k => $v) {
                $id[] = $v['mansong_id'];
            }
            $conditions = array(
                'mansong_id' => ['IN', $id],
                'start_time' => ['ELT', time()],
                'end_time' => ['EGT', time()],
                'range_type' => 0,
                'status' => 1,// 活动状态
                'website_id' => $this->website_id
            );
            $full_cut_list_again = $man_song_model::all($conditions);
            $full_cut_list = array_merge($full_cut_list, $full_cut_list_again);
        }

        // 剔除 店铺存在满减送时，平台的满减送
        $shop_flag = false;
        foreach ($full_cut_list as $v) {
            if ($v['shop_id'] == $shop_id && $shop_id != 0) {
                $shop_flag = true;
                break;
            }
        }

        if ($shop_flag) {
            foreach ($full_cut_list as $k => $v) {
                if ($v['shop_id'] != $shop_id) {
                    unset($full_cut_list[$k]);
                    continue;
                }
            }
            $full_cut_list = array_values($full_cut_list);
        }

        return $full_cut_list;
    }
    public function goodsFullCuts($goods_id)
    {
        $man_song_model = new VslPromotionMansongModel();
        $goods_model = new VslGoodsModel();

        $shop_id = $goods_model::get($goods_id)['shop_id'];

        // 获取全商品满减送
        $conditions = [
            'shop_id'=>$shop_id,
            'start_time' => ['ELT', time()],
            'end_time' => ['EGT', time()],
//            'range_type' => 1,// 全商品可用
            'status' => 1,// 活动状态
            'website_id' => $this->website_id,
        ];
        $man_song_info = $man_song_model::all($conditions);
        $full_cut_info = [];
        foreach ($man_song_info as $k => $info) {
            if ($info->range == 1 || $info->range == 3) {//仅本店可以使用
                if ($info->range_type == 1) {//全部商品可用
                    foreach ($info->rules as $rule) {
                        if (empty($full_cut_info[$info->shop_id]) || ($info->shop_id == $full_cut_info[$info->shop_id]['full_cut']['shop_id'] && ($full_cut_info[$info->shop_id]['full_cut']['level'] < $info->level || ($full_cut_info[$info->shop_id]['full_cut']['price'] < $rule->price && $full_cut_info[$info->shop_id]['full_cut']['level'] == $info->level))) || ($info->shop_id == $info->shop_id && $full_cut_info[$info->shop_id]['full_cut']['shop_id'] != $info->shop_id)) {
                            $full_cut_info[$info->shop_id]['full_cut']['man_song_id'] = $info->mansong_id;
                            $full_cut_info[$info->shop_id]['full_cut']['rule_id'] = $rule->rule_id;
                            $full_cut_info[$info->shop_id]['full_cut']['man_song_name'] = $info->mansong_name;
                            $full_cut_info[$info->shop_id]['full_cut']['discount'] = $rule->discount;
                            $full_cut_info[$info->shop_id]['full_cut']['price'] = $rule->price;
                            $full_cut_info[$info->shop_id]['full_cut']['shop_id'] = $info->shop_id;
                            $full_cut_info[$info->shop_id]['full_cut']['goods_limit'] = [];
                            $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                            $full_cut_info[$info->shop_id]['full_cut']['give_point'] = $rule->give_point;
                            $full_cut_info[$info->shop_id]['full_cut']['range_type'] = $info->range_type;
                            $full_cut_info[$info->shop_id]['full_cut']['level'] = $info->level;
                            $full_cut_info[$info->shop_id]['full_cut']['give_coupon'] = $rule->give_coupon;
                            $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                            $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                            if ($rule->free_shipping == 1) {//包邮
                                $full_cut_info[$info->shop_id]['shipping']['man_song_id'] = $info->mansong_id;
                                $full_cut_info[$info->shop_id]['shipping']['rule_id'] = $rule->rule_id;
                                $full_cut_info[$info->shop_id]['shipping']['man_song_name'] = $info->mansong_name;
                                $full_cut_info[$info->shop_id]['shipping']['free_shipping'] = true;
                                $full_cut_info[$info->shop_id]['shipping']['price'] = $rule->price;
                                $full_cut_info[$info->shop_id]['shipping']['shop_id'] = $info->shop_id;
                                $full_cut_info[$info->shop_id]['shipping']['goods_limit'] = [];
                                $full_cut_info[$info->shop_id]['shipping']['range_type'] = $info->range_type;
                            } else {
                                unset($full_cut_info[$info->shop_id]['shipping']);
                            }
                            if ($rule->give_coupon && $this->coupon_type) {
                                $coupon_type_model = new VslCouponTypeModel();
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_name'] = $coupon_type_model::get($rule->give_coupon)['coupon_name'];
                            } else {
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = '';
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_name'] = '';
                            }
                            //礼品券
                            if ($rule->gift_card_id && $this->gift_voucher) {
                                $gift_voucher = new VslGiftVoucherModel();
                                $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                                $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'giftvoucher_name')['giftvoucher_name'];
                                $full_cut_info[$info->shop_id]['full_cut']['gift_voucher_name'] = $giftvoucher_name;//送优惠券
                            }else{
                                $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = '';
                                $full_cut_info[$info->shop_id]['full_cut']['gift_voucher_name'] = '';
                            }
                            //赠品
                            if ($rule->gift_id && $this->gift_voucher) {
                                $gift_mdl = new VslPromotionGiftModel();
                                $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                                $gift_name = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name')['gift_name'];
                                $full_cut_info[$info->shop_id]['full_cut']['gift_name'] = $gift_name;//送优惠券
                            }else{
                                $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = '';
                                $full_cut_info[$info->shop_id]['full_cut']['gift_name'] = '';
                            }
                        }
                    }
                    continue;
                }
                if ($info->range_type == 0) {//部分商品可用
                    $goods_id_array = [];
                    foreach ($info->goods as $goods) {
                        $goods_id_array[] = $goods->goods_id;
                    }
                    foreach ($info->rules as $rule) {
                        if (empty($full_cut_info[$info->shop_id]) ||
                            ($info->shop_id == $full_cut_info[$info->shop_id]['full_cut']['shop_id'] && ($full_cut_info[$info->shop_id]['full_cut']['level'] < $info->level || ($full_cut_info[$info->shop_id]['full_cut']['price'] < $rule->price && $full_cut_info[$info->shop_id]['full_cut']['level'] == $info->level))) ||
                            ($info->shop_id == $info->shop_id && $full_cut_info[$info->shop_id]['full_cut']['shop_id'] != $info->shop_id)) {
                            //当前活动id == 当前保存活动的店铺id时 高等级 > price
                            //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                            $full_cut_info[$info->shop_id]['full_cut']['man_song_id'] = $info->mansong_id;
                            $full_cut_info[$info->shop_id]['full_cut']['rule_id'] = $rule->rule_id;
                            $full_cut_info[$info->shop_id]['full_cut']['man_song_name'] = $info->mansong_name;
                            $full_cut_info[$info->shop_id]['full_cut']['discount'] = $rule->discount;
                            $full_cut_info[$info->shop_id]['full_cut']['price'] = $rule->price;
                            $full_cut_info[$info->shop_id]['full_cut']['shop_id'] = $info->shop_id;
                            $full_cut_info[$info->shop_id]['full_cut']['goods_limit'] = $goods_id_array;
                            $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                            $full_cut_info[$info->shop_id]['full_cut']['give_point'] = $rule->give_point;
                            $full_cut_info[$info->shop_id]['full_cut']['range_type'] = $info->range_type;
                            $full_cut_info[$info->shop_id]['full_cut']['level'] = $info->level;
                            $full_cut_info[$info->shop_id]['full_cut']['give_coupon'] = $rule->give_coupon;
                            $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                            $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                            if ($rule->free_shipping == 1) {//包邮
                                $full_cut_info[$info->shop_id]['shipping']['man_song_id'] = $info->mansong_id;
                                $full_cut_info[$info->shop_id]['shipping']['rule_id'] = $rule->rule_id;
                                $full_cut_info[$info->shop_id]['shipping']['man_song_name'] = $info->mansong_name;
                                $full_cut_info[$info->shop_id]['shipping']['free_shipping'] = true;
                                $full_cut_info[$info->shop_id]['shipping']['price'] = $rule->price;
                                $full_cut_info[$info->shop_id]['shipping']['shop_id'] = $info->shop_id;
                                $full_cut_info[$info->shop_id]['shipping']['goods_limit'] = $goods_id_array;
                                $full_cut_info[$info->shop_id]['shipping']['range_type'] = $info->range_type;
                            } else {
                                unset($full_cut_info[$info->shop_id]['shipping']);
                            }
                            if ($rule->give_coupon && $this->coupon_type) {
                                $coupon_type_model = new VslCouponTypeModel();
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_name'] = $coupon_type_model::get($rule->give_coupon)['coupon_name'];
                            } else {
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_id'] = '';
                                $full_cut_info[$info->shop_id]['full_cut']['coupon_type_name'] = '';
                            }
                            //礼品券
                            if ($rule->gift_card_id && $this->gift_voucher) {
                                $gift_voucher = new VslGiftVoucherModel();
                                $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                                $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'giftvoucher_name')['giftvoucher_name'];
                                $full_cut_info[$info->shop_id]['full_cut']['gift_voucher_name'] = $giftvoucher_name;//送优惠券
                            }else{
                                $full_cut_info[$info->shop_id]['full_cut']['gift_card_id'] = '';
                                $full_cut_info[$info->shop_id]['full_cut']['gift_voucher_name'] = '';
                            }
                            //赠品
                            if ($rule->gift_id && $this->gift_voucher) {
                                $gift_mdl = new VslPromotionGiftModel();
                                $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                                $gift_name = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name')['gift_name'];
                                $full_cut_info[$info->shop_id]['full_cut']['gift_name'] = $gift_name;//送优惠券
                            }else{
                                $full_cut_info[$info->shop_id]['full_cut']['gift_id'] = '';
                                $full_cut_info[$info->shop_id]['full_cut']['gift_name'] = '';
                            }
                        }
                    }
                    continue;
                }
                continue;
            }
            if ($info->range == 2) {//全平台设置的全平台可用
                if ($info->range_type == 1) {//全部商品可用
                    foreach ($info->rules as $rule) {
                        if (empty($full_cut_info[$shop_id]) ||
                            ($info->shop_id == $full_cut_info[$shop_id]['full_cut']['shop_id'] && ($full_cut_info[$shop_id]['full_cut']['level'] < $info->level || ($full_cut_info[$shop_id]['full_cut']['price'] < $rule->price && $full_cut_info[$shop_id]['full_cut']['level'] == $info->level))) ||
                            ($info->shop_id == $shop_id && $full_cut_info[$shop_id]['full_cut']['shop_id'] != $shop_id)) {
                            //当前活动id == 当前保存活动的店铺id时 高等级 > price
                            //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                            $full_cut_info[$shop_id]['full_cut']['man_song_id'] = $info->mansong_id;
                            $full_cut_info[$shop_id]['full_cut']['rule_id'] = $rule->rule_id;
                            $full_cut_info[$shop_id]['full_cut']['man_song_name'] = $info->mansong_name;
                            $full_cut_info[$shop_id]['full_cut']['discount'] = $rule->discount;
                            $full_cut_info[$shop_id]['full_cut']['price'] = $rule->price;
                            $full_cut_info[$shop_id]['full_cut']['shop_id'] = $info->shop_id;
                            $full_cut_info[$shop_id]['full_cut']['goods_limit'] = [];
                            $full_cut_info[$shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                            $full_cut_info[$shop_id]['full_cut']['give_point'] = $rule->give_point;
                            $full_cut_info[$shop_id]['full_cut']['range_type'] = $info->range_type;
                            $full_cut_info[$shop_id]['full_cut']['level'] = $info->level;
                            $full_cut_info[$shop_id]['full_cut']['give_coupon'] = $rule->give_coupon;
                            $full_cut_info[$shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                            $full_cut_info[$shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                            if ($rule->free_shipping == 1) {
                                $full_cut_info[$shop_id]['shipping']['man_song_id'] = $info->mansong_id;
                                $full_cut_info[$shop_id]['shipping']['rule_id'] = $rule->rule_id;
                                $full_cut_info[$shop_id]['shipping']['man_song_name'] = $info->mansong_name;
                                $full_cut_info[$shop_id]['shipping']['free_shipping'] = true;
                                $full_cut_info[$shop_id]['shipping']['price'] = $rule->price;
                                $full_cut_info[$shop_id]['shipping']['shop_id'] = $info->shop_id;
                                $full_cut_info[$shop_id]['shipping']['goods_limit'] = [];
                                $full_cut_info[$shop_id]['shipping']['range_type'] = $info->range_type;
                            } else {
                                unset($full_cut_info[$shop_id]['shipping']);
                            }
                            if ($rule->give_coupon && $this->coupon_type) {
                                $coupon_type_model = new VslCouponTypeModel();
                                $full_cut_info[$shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                                $full_cut_info[$shop_id]['full_cut']['coupon_type_name'] = $coupon_type_model::get($rule->give_coupon)['coupon_name'];
                            } else {
                                $full_cut_info[$shop_id]['full_cut']['coupon_type_id'] = '';
                                $full_cut_info[$shop_id]['full_cut']['coupon_type_name'] = '';
                            }
                            //礼品券
                            if ($rule->gift_card_id && $this->gift_voucher) {
                                $gift_voucher = new VslGiftVoucherModel();
                                $full_cut_info[$shop_id]['full_cut']['gift_card_id'] = $rule->gift_card_id;
                                $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id'=>$rule->gift_card_id], 'giftvoucher_name')['giftvoucher_name'];
                                $full_cut_info[$shop_id]['full_cut']['gift_voucher_name'] = $giftvoucher_name;//送优惠券
                            }else{
                                $full_cut_info[$shop_id]['full_cut']['gift_card_id'] = '';
                                $full_cut_info[$shop_id]['full_cut']['gift_voucher_name'] = '';
                            }
                            //赠品
                            if ($rule->gift_id && $this->gift_voucher) {
                                $gift_mdl = new VslPromotionGiftModel();
                                $full_cut_info[$shop_id]['full_cut']['gift_id'] = $rule->gift_id;
                                $gift_name = $gift_mdl->getInfo(['promotion_gift_id'=>$rule->gift_id], 'gift_name')['gift_name'];
                                $full_cut_info[$shop_id]['full_cut']['gift_name'] = $gift_name;//送优惠券
                            }else{
                                $full_cut_info[$shop_id]['full_cut']['gift_id'] = '';
                                $full_cut_info[$shop_id]['full_cut']['gift_name'] = '';
                            }
                        }
                    }
                    continue;
                }

                if ($info->range_type == 0) {//部分商品可用
                    $goods_id_array = [];
                    foreach ($info->goods as $goods) {
                        $goods_id_array[] = $goods->goods_id;
                    }
                    foreach ($info->rules as $rule) {
                        if (empty($full_cut_info[$shop_id]) ||
                            ($info->shop_id == $full_cut_info[$shop_id]['full_cut']['shop_id'] && ($full_cut_info[$shop_id]['full_cut']['level'] < $info->level || ($full_cut_info[$shop_id]['full_cut']['price'] < $rule->price && $full_cut_info[$shop_id]['full_cut']['level'] == $info->level))) ||
                            ($info->shop_id == $shop_id && $full_cut_info[$shop_id]['full_cut']['shop_id'] != $shop_id)) {
                            //当前活动id == 当前保存活动的店铺id时 高等级 > price
                            //当前活动id == 当前目前商品店铺id时 && 取活动店铺id != 商品店铺id
                            $full_cut_info[$shop_id]['full_cut']['man_song_id'] = $info->mansong_id;
                            $full_cut_info[$shop_id]['full_cut']['rule_id'] = $rule->rule_id;
                            $full_cut_info[$shop_id]['full_cut']['man_song_name'] = $info->mansong_name;
                            $full_cut_info[$shop_id]['full_cut']['discount'] = $rule->discount;
                            $full_cut_info[$shop_id]['full_cut']['price'] = $rule->price;
                            $full_cut_info[$shop_id]['full_cut']['shop_id'] = $info->shop_id;
                            $full_cut_info[$shop_id]['full_cut']['goods_limit'] = $goods_id_array;
                            $full_cut_info[$shop_id]['full_cut']['coupon_type_id'] = $rule->give_coupon;
                            $full_cut_info[$shop_id]['full_cut']['give_point'] = $rule->give_point;
                            $full_cut_info[$shop_id]['full_cut']['range_type'] = $info->range_type;
                            $full_cut_info[$shop_id]['full_cut']['level'] = $info->level;

                            if ($rule->free_shipping == 1) {
                                $full_cut_info[$shop_id]['shipping']['man_song_id'] = $info->mansong_id;
                                $full_cut_info[$shop_id]['shipping']['rule_id'] = $rule->rule_id;
                                $full_cut_info[$shop_id]['shipping']['man_song_name'] = $info->mansong_name;
                                $full_cut_info[$shop_id]['shipping']['free_shipping'] = true;
                                $full_cut_info[$shop_id]['shipping']['price'] = $rule->price;
                                $full_cut_info[$shop_id]['shipping']['shop_id'] = $info->shop_id;
                                $full_cut_info[$shop_id]['shipping']['goods_limit'] = $goods_id_array;
                                $full_cut_info[$shop_id]['shipping']['range_type'] = $info->range_type;
                            } else {
                                unset($full_cut_info[$shop_id]['shipping']);
                            }
                        }



                    }
                    continue;
                }
                continue;
            }
        }
        return $full_cut_info;
    }
}
