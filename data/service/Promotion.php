<?php

namespace data\service;

/**
 * 营销
 */

use addons\giftvoucher\model\VslGiftVoucherModel;
use data\api\IPromotion;
use data\model\AlbumPictureModel as AlbumPictureModel;
use addons\coupontype\model\VslCouponModel as VslCouponModel;
use addons\coupontype\model\VslCouponTypeModel as VslCouponTypeModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslPromotionDiscountGoodsModel;
use data\model\VslPromotionDiscountModel;
use data\model\VslPromotionFullMailModel;
use data\model\VslPromotionGiftGoodsModel;
use addons\gift\model\VslPromotionGiftModel;
use data\model\VslPromotionMansongGoodsModel;
use data\model\VslPromotionMansongModel;
use data\model\VslPromotionMansongRuleModel;
use data\service\BaseService as BaseService;
use data\service\promotion\GoodsDiscount;
use data\service\promotion\GoodsMansong;
use think\Db;

class Promotion extends BaseService
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromotion::getCouponTypeList()
     */
    public function getCouponTypeList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
    {
        $coupon_type = new VslCouponModel();
        $coupon_type_list = $coupon_type->pageQuery($page_index, $page_size, $condition, $order, 'coupon_type_id, coupon_name, money, count, max_fetch, at_least, need_user_level, range_type, start_time, end_time, create_time, update_time,is_show');
        /*
         * if(!empty($coupon_type_list['data']))
         * foreach ($coupon_type_list['data'] as $k => $v)
         * {
         * if($v['range_type'] == 0) //部分产品
         * {
         * $coupon_goods = new VslSeckGoodsModel();
         * $goods_list = $coupon_goods->getCouponTypeGoodsList($v['coupon_type_id']);
         * $coupon_type_list['data'][$k]['goods_list'] = $goods_list;
         * }
         * }
         */
        //
        return $coupon_type_list;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IPromote::getPromotionGiftList()
     */
    public function getPromotionGiftList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
    {
        $promotion_gift = new VslPromotionGiftModel();
        $list = $promotion_gift->pageQuery($page_index, $page_size, $condition, $order, '*');
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $start_time = $v['start_time'];
                $end_time = $v['end_time'];
                if ($end_time < time()) {
                    $list['data'][$k]['type'] = 2;
                    $list['data'][$k]['type_name'] = '已结束';
                } elseif ($start_time > time()) {
                    $list['data'][$k]['type'] = 0;
                    $list['data'][$k]['type_name'] = '未开始';
                } elseif ($start_time <= time() && time() <= $end_time) {
                    $list['data'][$k]['type'] = 1;
                    $list['data'][$k]['type_name'] = '进行中';
                }
            }
        }
        return $list;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IPromote::addPromotionGift()
     */
    public function addPromotionGift($shop_id, $gift_name, $start_time, $end_time, $days, $max_num, $goods_id_array)
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
                'create_time' => time(),
                'website_id' => $this->website_id
            );
            $promotion_gift->save($data_gift);
            $gift_id = $promotion_gift->gift_id;
            // 当前功能只能选择一种商品
            $promotion_gift_goods = new VslPromotionGiftGoodsModel();
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
            $promotion_gift_goods->save($data_goods);
            $promotion_gift->commit();
            return $gift_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $promotion_gift->rollback();
            return $e->getMessage();
        }
        // TODO Auto-generated method stub
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
            recordErrorLog($e);
            $promotion_gift->rollback();
            return $e->getMessage();
        }

        // TODO Auto-generated method stub
    }

    /**
     * 获取 赠品详情
     *
     * @param unknown $gift_id
     */
    public function getPromotionGiftDetail($gift_id)
    {
        $promotion_gift = new VslPromotionGiftModel();
        $data = $promotion_gift->get($gift_id);
        $promotion_gift_goods = new VslPromotionGiftGoodsModel();
        $gift_goods = $promotion_gift_goods->getGiftGoodsList($gift_id);
        foreach ($gift_goods as $k => $v) {
            $picture = new AlbumPictureModel();
            $pic_info = array();
            $pic_info['pic_cover'] = '';
            if (!empty($v['picture'])) {
                $pic_info = $picture->get($v['picture']);
            }
            $gift_goods[$k]['picture_info'] = $pic_info;
        }

        $data['gift_goods'] = $gift_goods;
        return $data;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::getPromotionMansongList()
     */
    //获取最优满减活动
    public function getBestMansongInfo($shopid)
    {
        if(!getAddons('fullcut', $this->website_id)){
            return [];
        }
        //先检测自营店和单独店铺的活动
        $time = time();
        if ($shopid == '0') {
            $sql = 'select a.* ,b.`free_shipping`,b.`price`,b.`give_point`,b.`give_coupon`,b.`discount` from `vsl_promotion_mansong` as a left join `vsl_promotion_mansong_rule` as b on a.`mansong_id` = b.`mansong_id` where a.`status` =1 and a.`start_time`<' . time() . ' and a.`end_time` >' . time() . ' and a.`range`= 1 and a.`website_id` = '.$this->website_id.' order by a.`level` DESC  limit 0 , 1';
        } else {
            $sql = 'select a.* ,b.`free_shipping`,b.`price`,b.`give_point`,b.`give_coupon`,b.`discount`,b.`gift_card_id`,b.`gift_id` from `vsl_promotion_mansong` as a left join `vsl_promotion_mansong_rule` as b on a.`mansong_id` = b.`mansong_id` where a.`status` =1 and a.`shop_id` != 0 and a.`range`=3 and a.`start_time`<' . time() . ' and a.`end_time` >' . time() . ' and a.`website_id` = '.$this->website_id.' order by a.`range` desc,a.`level` DESC  limit 0 , 1';
        }
        $result = DB::query($sql);
        //没有的话从全平台找
        if (empty($result)) {
            $sql = 'select a.* ,b.`free_shipping`,b.`price`,b.`give_point`,b.`give_coupon`,b.`discount`,b.`gift_card_id`,b.`gift_id` from `vsl_promotion_mansong` as a left join `vsl_promotion_mansong_rule` as b on a.`mansong_id` = b.`mansong_id` where a.`status` =1 and a.`range`=2 and a.`start_time`<' . time() . ' and a.`end_time` >' . time() . ' and a.`website_id` = '.$this->website_id.' order by a.`range` desc,a.`level` DESC  limit 0 , 1';
            $result = DB::query($sql);
        }
        //获取赠送礼品名字
//        p($result);exit;
        foreach($result as &$v){
            if ($v['give_coupon']) {
                $coupon_type_model = new VslCouponTypeModel();
                $v['coupon_type_id'] = $v['give_coupon'];
                $v['coupon_type_name'] = $coupon_type_model::get($v['give_coupon'])['coupon_name'];
            } else {
                $v['coupon_type_id'] = '';
                $v['coupon_type_name'] = '';
            }
            //礼品券
            if ($v['gift_card_id']) {
                $gift_voucher = new VslGiftVoucherModel();
                $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id'=>$v['gift_card_id']], 'giftvoucher_name')['giftvoucher_name'];
                $v['gift_voucher_name'] = $giftvoucher_name;//送优惠券
            }else{
                $v['gift_card_id'] = '';
                $v['gift_voucher_name'] = '';
            }
            //赠品
            if ($v['gift_id']) {
                $gift_mdl = new VslPromotionGiftModel();
                $gift_name = $gift_mdl->getInfo(['promotion_gift_id'=>$v['gift_id']], 'gift_name')['gift_name'];
                $v['gift_name'] = $gift_name;//送优惠券
            }else{
                $v['gift_id'] = '';
                $v['gift_name'] = '';
            }
        }
        return $result;

    }

    //判断是否是满减商品

    public function check_is_mansong_product($goods_id, $mansong_id)
    {

        $result = Db::query("select * from `vsl_promotion_mansong_goods` where `goods_id` = $goods_id and `mansong_id` = $mansong_id");
        return $result;

    }


    //获取具体的满减规则
    public function getmansong_rule($mansong_id)
    {

        $sql = "select * from vsl_promotion_mansong_rule where `mansong_id` = $mansong_id";
        $result = Db::query($sql);
        return $result;
    }

    //获取具体享受的满健规格
    public function get_detail_rule($condition){

        $user = new VslPromotionMansongRuleModel();
        $rule_list = $user->where($condition)
            ->select();
        return $rule_list;

    }

    //获取所有的满减商品
    public function get_mansong_product($condition){

        $user = new VslPromotionMansongGoodsModel();
        $goods_list = $user->where($condition)
            ->select();
        return $goods_list;

    }

    //判断平台是自营还是代理
    public function checkshoptype($goodsid, $website_id)
    {

        $sql = "select * from vsl_goods where `goods_id` = $goodsid and `website_id` = $website_id";
        $result = Db::query($sql);

        return $result['0']['shop_id'];  //0表自营
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
            $count_quan = $goods_mansong->getQuanmansong($start_time, $end_time);
            if ($count_quan > 0 && $range_type == 1) {
                $err = 1;
            }
            $shop_name = $this->instance_name;
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
                    'give_point' => $get_rule[3],
                    'give_coupon' => $get_rule[4],
                    'gift_id' => $get_rule[5]
                );
                $promot_mansong_rule = new VslPromotionMansongRuleModel();
                $promot_mansong_rule->save($data_rule);
            }

            // 满减送商品表
            if ($range_type == 0 && !empty($goods_id_array)) {
                // 部分商品
                $goods_id_array = explode(',', $goods_id_array);
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
                    $count = $goods_mansong->getGoodsIsMansong($v, $start_time, $end_time);
                    if ($count > 0) {
                        $err = 1;
                    }
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
            recordErrorLog($e);
            $promot_mansong->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::updatePromotionMansong()
     */
    public function updatePromotionMansong($mansong_id, $mansong_name, $start_time, $end_time, $shop_id, $remark, $type, $range_type, $rule, $goods_id_array, $range, $status, $level)
    {
        $promot_mansong = new VslPromotionMansongModel();
        $promot_mansong->startTrans();
        try {
            $err = 0;
            $data = array(
                'mansong_name' => $mansong_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'status' => $status, // 状态重新设置
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
                    'give_point' => $get_rule[3],
                    'give_coupon' => $get_rule[4],
                    'gift_id' => $get_rule[5]
                );
                $promot_mansong_rule->save($data_rule);
            }

            // 满减送商品表
            if ($range_type == 0 && !empty($goods_id_array)) {
                // 部分商品
                $goods_id_array = explode(',', $goods_id_array);
                $promotion_mansong_goods = new VslPromotionMansongGoodsModel();
                $promotion_mansong_goods->destroy([
                    'mansong_id' => $mansong_id
                ]);
                foreach ($goods_id_array as $k => $v) {
                    // 查询商品名称图片
                    $goods_mansong = new GoodsMansong();
                    $count = $goods_mansong->getGoodsIsMansong($v, $start_time, $end_time);
                    if ($count > 0) {
                        $err = 1;
                    }
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
            }
            if ($err > 0) {
                $promot_mansong->rollback();
                return ACTIVE_REPRET;
            } else {

                $promot_mansong->commit();
                return 1;
            }
        } catch (\Exception $e) {
            recordErrorLog($e);
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
                $coupon_type = new VslCouponModel();
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
                    'gift_id' => $v['gift_id']
                ], 'gift_name');
                $rule_list['data'][$k]['gift_name'] = $gift_name['gift_name'];
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
                    ], 'price, stock');
                    $picture = new AlbumPictureModel();
                    $pic_info = array();
                    $pic_info['pic_cover'] = '';
                    if (!empty($v['goods_picture'])) {
                        $pic_info = $picture->get($v['goods_picture']);
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

    public function updatepromotion_type($range, $range_type, $goodsid = '', $shopid = '', $website_id = '0')
    {
        if ($range == '1' && $range_type == '1') {
            $sql = "update `vsl_goods` set `promotion_type` = 2 where `shop_id` = 0 and `website_id` = $website_id"; // 1.	自营店==》全部商品
        } else if ($range == '2' && $range_type == '1') {
            $sql = "update `vsl_goods` set `promotion_type` = 2 where `website_id` = $website_id"; // 2.全平台==》全部商品
        } else if ($range == '3' && $range_type == '1') {
            $sql = "update `vsl_goods` set `promotion_type` = 2 where `shop_id` = $shopid and `website_id` = $website_id"; // 3.店铺端==》全部商品
        } else if ($goodsid != '0' || $range_type != '1') {
            $sql = "update `vsl_goods` set `promotion_type` = 2 where `goods_id` in($goodsid) and `website_id` = $website_id"; // 4.部分商品
        }
        return Db::query($sql);
    }
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::addPromotiondiscount()
     */

    //获取最优折扣活动
    public function get_best_discount($shopid)
    {
        $website_id = $this->website_id;
        //0表示自营
        if ($shopid == '0') {
            $sql = 'select * from `vsl_promotion_discount` where `status` =1 and `start_time`<' . time() . ' and `end_time` >' . time() . ' and (`range`= 1 OR `range`=2) and `website_id` = '.$website_id.' order by `level` asc limit 0 , 1';
        } else {
            $sql = 'select * from `vsl_promotion_discount` where `status` =1 and `shop_id` != 0 and (`range`=3 OR `range`=2) and `start_time`<' . time() . ' and `end_time` >' . time() . ' and `website_id` = '.$website_id.' order by `level` asc limit 0 , 1';
        }
        $result = Db::query($sql);
        return $result;
    }

    //活动价格
    public function get_discount_price($price, $discount_num, $goods_id = '')
    {
        $promotion_price = sprintf("%.2f", $price * $discount_num / 10);
        if ($goods_id) {
            $sql = "update `vsl_goods` set `promotion_price` = $promotion_price where `goods_id` = $goods_id";
            $sql = Db::query($sql);
            return $sql;
        } else {
            return $promotion_price;
        }
    }

    //判断是否是折扣商品
    //$type: 店铺ID，0表自营店，取范围为自营和全平台（1,2）
    //               !=0表店铺，取范围为全平台和店铺（2，3）
    public function check_is_discount_product($goods_id, $type)
    {
        if(empty($goods_id)){
            return "商品信息不存在";
        }
        $time = time();
        if ($type == '0') {
            $sql = "SELECT a.* ,b.`goods_id`,b.`goods_name`,b.`status`,b.`discount` FROM `vsl_promotion_discount` AS a LEFT JOIN `vsl_promotion_discount_goods` AS b ON a.`discount_id` = b.`discount_id` WHERE a.`website_id` = $this->website_id and ((b.`goods_id` = $goods_id and a.range = 2) or a.`range` = 1) and (a.`range_type` = 1 or a.`range_type` = 2) and a.`start_time` < $time and a.`end_time` > $time and a.`status` = 1 ORDER BY a.`level` asc LIMIT 0,1";
        } else {
            $sql = "SELECT a.* ,b.`goods_id`,b.`goods_name`,b.`status`,b.`discount` FROM `vsl_promotion_discount` AS a LEFT JOIN `vsl_promotion_discount_goods` AS b ON a.`discount_id` = b.`discount_id` WHERE a.`website_id` = $this->website_id and ((b.`goods_id` = $goods_id and a.range = 2) or a.`range` = 1) and (a.`range_type` = 3 or a.`range_type` = 2) and a.`start_time` < $time and a.`end_time` > $time and a.`status` = 1 ORDER BY a.`shop_id` desc, a.`level` asc LIMIT 0,1";
        }
        $result = Db::query($sql);
        return $result;

    }

//    //判断平台是自营还是代理
//    public function checkshoptype($goodsid,$website_id){
//
//        $sql = "select * from vsl_goods where `goods_id` = $goodsid and `website_id` = $website_id";
//        $result = Db::query($sql);
//
//        return $result['0']['shop_id'];  //0表自营
//    }


    public function addPromotiondiscount($discount_name, $start_time, $end_time, $remark, $goods_id_array, $level, $range, $status, $range_type, $discount_num)
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $promotion_discount->startTrans();
        try {
            //print_r($goods_id_array);exit;
            //$this->updatepromotion_type($range, $range_type, $goods_id_array, $this->instance_id, $this->website_id);
            $shop_name = $this->instance_name;
            $data = array(
                'discount_name' => $discount_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'shop_id' => $this->instance_id,
                'shop_name' => $shop_name,
                'status' => 0,
                'level' => $level,
                'range' => $range,
                'range_type' => $range_type,
                'discount_num' => $discount_num,
                'remark' => $remark,
                'create_time' => time(),
                'website_id' => $this->website_id
            );
            $promotion_discount->save($data);
            $discount_id = $promotion_discount->discount_id;
            $goods_id_array = explode(',', $goods_id_array);
            $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
            $promotion_discount_goods->destroy([
                'discount_id' => $discount_id
            ]);

            foreach ($goods_id_array as $k => $v) {
                //改版，检测关掉 2018.4.25
                // 添加检测考虑商品在一个时间段内只能有一种活动
                $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
                $discount_info = explode(':', $v);
                /*  $goods_discount = new GoodsDiscount();
                 $count = $goods_discount->getGoodsIsDiscount($discount_info[0], $start_time, $end_time);
                 // 查询商品名称图片

                 if ($count > 0) {
                     $promotion_discount->rollback();
                     return ACTIVE_REPRET;
                 }*/
                $goods = new VslGoodsModel();
                $goods_info = $goods->getInfo([
                    'goods_id' => $discount_info[0]
                ], 'goods_name,picture');
                $data_goods = array(
                    'discount_id' => $discount_id,
                    'goods_id' => $discount_info[0],
                    'discount' => $discount_num,
                    'status' => 0,
                    'start_time' => getTimeTurnTimeStamp($start_time),
                    'end_time' => getTimeTurnTimeStamp($end_time),
                    'goods_name' => $goods_info['goods_name'],
                    'goods_picture' => $goods_info['picture']
                );
                $price = Db::table('vsl_goods')->where('goods_id', $goods_info['goods_id'])->value('price');
                //print_r($goods_info);exit;
                $this->get_discount_price($price, $discount_num, $goods_info['goods_id']);
                $promotion_discount_goods->save($data_goods);
            }
            $promotion_discount->commit();
            return $discount_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::updatePromotionDiscount()
     */
    public function updatePromotionDiscount($discount_id, $discount_name, $start_time, $end_time, $remark, $goods_id_array, $level, $range, $status, $range_type, $discount_num)
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $promotion_discount->startTrans();
        try {
            $shop_name = $this->instance_name;
            $data = array(
                'discount_name' => $discount_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'shop_id' => $this->instance_id,
                'shop_name' => $shop_name,
                'status' => 0,
                'remark' => $remark,
                'level' => $level,
                'range' => $range,
                'status' => 0,
                'range_type' => $range_type,
                'discount_num' => $discount_num,
                'create_time' => time()
            );
            $promotion_discount->save($data, [
                'discount_id' => $discount_id
            ]);

            $goods_id_array = explode(',', $goods_id_array);
            $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
            $promotion_discount_goods->destroy([
                'discount_id' => $discount_id
            ]);
            foreach ($goods_id_array as $k => $v) {
                $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
                $discount_info = explode(':', $v);
                $goods_discount = new GoodsDiscount();
                $count = $goods_discount->getGoodsIsDiscount($discount_info[0], $start_time, $end_time);
                // 查询商品名称图片
                if ($count > 0) {
                    $promotion_discount->rollback();
                    return ACTIVE_REPRET;
                }
                // 查询商品名称图片
                $goods = new VslGoodsModel();
                $goods_info = $goods->getInfo([
                    'goods_id' => $discount_info[0]
                ], 'goods_name,picture');
                $data_goods = array(
                    'discount_id' => $discount_id,
                    'goods_id' => $discount_info[0],
                    'discount' => $discount_info[1],
                    'status' => 0,
                    'start_time' => getTimeTurnTimeStamp($start_time),
                    'end_time' => getTimeTurnTimeStamp($end_time),
                    'goods_name' => $goods_info['goods_name'],
                    'goods_picture' => $goods_info['picture']
                );
                $promotion_discount_goods->save($data_goods);
            }
            $promotion_discount->commit();
            return $discount_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::closePromotionDiscount()
     */
    public function closePromotionDiscount($discount_id)
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $promotion_discount->startTrans();
        try {
            $retval = $promotion_discount->save([
                'status' => 3
            ], [
                'discount_id' => $discount_id
            ]);
            if ($retval == 1) {
                $goods = new VslGoodsModel();

                $data_goods = array(
                    'promotion_type' => 2,
                    'promote_id' => $discount_id
                );
                $goods_id_list = $goods->getQuery($data_goods, 'goods_id', '');
                if (!empty($goods_id_list)) {

                    foreach ($goods_id_list as $k => $goods_id) {
                        $goods_info = $goods->getInfo([
                            'goods_id' => $goods_id['goods_id']
                        ], 'promotion_type,price');
                        $goods->save([
                            'promotion_price' => $goods_info['price']
                        ], [
                            'goods_id' => $goods_id['goods_id']
                        ]);
                        $goods_sku = new VslGoodsSkuModel();
                        $goods_sku_list = $goods_sku->getQuery([
                            'goods_id' => $goods_id['goods_id']
                        ], 'price,sku_id', '');
                        foreach ($goods_sku_list as $k_sku => $sku) {
                            $goods_sku = new VslGoodsSkuModel();
                            $data_goods_sku = array(
                                'promote_price' => $sku['price']
                            );
                            $goods_sku->save($data_goods_sku, [
                                'sku_id' => $sku['sku_id']
                            ]);
                        }
                    }
                }
                $goods->save([
                    'promotion_type' => 0,
                    'promote_id' => 0
                ], $data_goods);
                $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
                $retval = $promotion_discount_goods->save([
                    'status' => 3
                ], [
                    'discount_id' => $discount_id
                ]);
            }
            $promotion_discount->commit();
            return $retval;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::getPromotionDiscountList()
     */
    public function getPromotionDiscountList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $list = $promotion_discount->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::getPromotionDiscountDetail()
     */
    public function getPromotionDiscountDetail($discount_id)
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $promotion_detail = $promotion_discount->get($discount_id);
        $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
        $promotion_goods_list = $promotion_discount_goods->getQuery([
            'discount_id' => $discount_id
        ], '*', '');
        if (!empty($promotion_goods_list)) {
            foreach ($promotion_goods_list as $k => $v) {
                $goods = new VslGoodsModel();
                $goods_info = $goods->getInfo([
                    'goods_id' => $v['goods_id']
                ], 'price, stock');
                $picture = new AlbumPictureModel();
                $pic_info = array();
                $pic_info['pic_cover'] = '';
                if (!empty($v['goods_picture'])) {
                    $pic_info = $picture->get($v['goods_picture']);
                }
                $v['picture_info'] = $pic_info;
                $v['price'] = $goods_info['price'];
                $v['stock'] = $goods_info['stock'];
            }
        }
        $promotion_detail['goods_list'] = $promotion_goods_list;
        return $promotion_detail;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IPromote::delPromotionDiscount()
     */
    public function delPromotionDiscount($discount_id)
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
        $promotion_discount->startTrans();
        try {
            $discount_id_array = explode(',', $discount_id);
            foreach ($discount_id_array as $k => $v) {
                $promotion_detail = $promotion_discount->get($discount_id);
                if ($promotion_detail['status'] == 1) {
                    $promotion_discount->rollback();
                    return -1;
                }
                $promotion_discount->destroy($v);
                $promotion_discount_goods->destroy([
                    'discount_id' => $v
                ]);
            }
            $promotion_discount->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::closePromotionDiscount()
     */
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
}