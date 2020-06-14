<?php
namespace addons\fullcut\controller;

use addons\fullcut\Fullcut as BaseFullCut;
use addons\coupontype\model\VslSeckillModel;
use addons\shop\model\VslShopModel;
use data\model\AlbumPictureModel as AlbumPictureModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslPromotionDiscountGoodsModel;
use data\model\VslPromotionDiscountModel;
use data\model\VslPromotionGiftModel;
use data\model\VslPromotionFullMailModel;
use data\model\VslPromotionMansongGoodsModel;
use data\model\VslPromotionMansongModel;
use data\model\VslPromotionMansongRuleModel;
use data\service\Promotion as PromotionService;
use data\service\promotion\GoodsDiscount;
use data\service\promotion\GoodsMansong;
use data\service\Goods as GoodsService;
use think\Db;
use addons\fullcut\service\Fullcut as mansong;
/**
 * 店铺设置控制器
 *
 * @author  www.vslai.com
 *
 */
class Fullcut extends BaseFullCut
{
//    public $instance_id;
//    public $website_id;
//    public $instance_name;

    public function __construct(){
        parent::__construct();
        //$this->website_id = request()->post('website_id',0);
    }



    /*
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
            if (! empty($v['picture'])) {
                $pic_info = $picture->getInfo(['pic_id' =>$v['picture']],'pic_cover,pic_cover_mid,pic_cover_micro');
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
    public function getBestMansongInfo($type){
        //1表示自营
        $website_id = $this->website_id;
        if($type=='1') {
            $sql = 'select * from `vsl_promotion_mansong` where `status` =1 and `start_time`<' . time() . ' and `end_time` >' . time() . ' and `website_id` = '.$website_id.' order by `range` asc,`level` desc limit 0 , 1';
        }else{
            $sql = 'select * from `vsl_promotion_mansong` where `status` =1 and `range` = 2 and `start_time`<' . time() . ' and `end_time` >' . time() . ' and `website_id` = '.$website_id.' order by `level` desc limit 0 , 1';
        }
        $result = Db::query($sql);
        $mansong_info = '';
        foreach ($result as $k=>$v){
            if(!empty($v['mansong_id'])){

            }
        }
        return $result;
    }

    //判断是否是满减商品

    public function check_is_mansong_product($goods_id,$mansong_id){

        $result = Db::query( "select `id` from `vsl_promotion_mansong_goods` where `goods_id` = $goods_id and `mansong_id` = $mansong_id");
        return $result;

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
            $condition['website_id'] = $this->website_id;
        }
        if($_REQUEST['shop_id']!=''){
            $condition['shop_id'] = $_REQUEST['shop_id'];
        }
        if($_REQUEST['seleted_goods']){
            $condition['goods_id'] = ['in',$_REQUEST['seleted_goods']];
        }
        $goods_detail = new GoodsService();
        $result = $goods_detail->getSearchGoodsList($page_index, $page_size, $condition);
        return $result;
    }


    /**
     * 优惠券类型列表
     *
     * @return multitype:number unknown |Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function couponTypeList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $search_text = request()->post('search_text', '');
            $coupon = new PromotionService();
            $condition = array(
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
                'coupon_name' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
            $list = $coupon->getCouponTypeList($page_index, $page_size, $condition);
            return $list;
        } else {
            return view($this->style . "Promotion/couponTypeList");
        }
    }


    //获取具体的满减规则
    public function getmansong_rule($mansong_id){

        $sql = "select * from vsl_promotion_mansong_rule where `mansong_id` = $mansong_id";
        $result = Db::query($sql);
        return $result;
    }

    //判断平台是自营还是代理
    public function checkshoptype($goodsid,$website_id){

        $sql = "select * from vsl_goods where `goods_id` = $goodsid and `website_id` = $website_id";
        $result = Db::query($sql);
        if($result['0']['shop_id']=='0'){
            return 1;
        }else{
            return 2;
        }
    }

    //获取满送详情
    public function getMansongDetail()
    {
        $mansong_id = isset($_GET['mansong_id']) ? $_GET['mansong_id'] : '';
        if (empty($mansong_id)) {
            return false;
        }
        $detail = $this->getPromotionMansongDetail($mansong_id);
        return $detail;
    }


    /**
     * 删除满减送活动
     *
     * @return unknown[]
     */
    public function delMansong()
    {
        $mansong_id = isset($_POST['mansong_id']) ? $_POST['mansong_id'] : '';
        if (empty($mansong_id)) {
            return false;
        }
        $res = $this->delPromotionMansong($mansong_id);
        if($res){
            $this->addUserLog('删除满减送活动', $mansong_id);
        }
        return AjaxReturn($res);
    }

    /**
     * 关闭满减送活动
     *
     * @return unknown[]
     */
    public function closeMansong()
    {
        $mansong_id = isset($_POST['mansong_id']) ? $_POST['mansong_id'] : '';
        if (empty($mansong_id)) {
            return false;
        }
        $res = $this->closePromotionMansong($mansong_id);
        if($res){
            $this->addUserLog('关闭满减送活动', $mansong_id);
        }
        return AjaxReturn($res);
    }



    public function getPromotionMansongList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
    {
        $promotion_mansong = new VslPromotionMansongModel();

        $list = $promotion_mansong->pageQuery($page_index, $page_size, $condition, $order, '*');
        if (! empty($list['data'])) {
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
     *添加满减送
     * @see \data\api\IPromote::addPromotionMansong()
     */
    public function addPromotionMansong($mansong_name, $start_time, $end_time, $shop_id, $remark, $type, $range_type, $rule, $goods_id_array,$range,$status,$level)
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
                'website_id'=>$this->website_id
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
            if ($range_type == 0 && ! empty($goods_id_array)) {
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
                $this->addUserLog('添加满减送', $mansong_id);
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
    public function updatePromotionMansong($mansong_id, $mansong_name, $start_time, $end_time, $shop_id, $remark, $type, $range_type, $rule, $goods_id_array,$range,$status,$level)
    {
        $promot_mansong = new VslPromotionMansongModel();
        $promot_mansong->startTrans();
        try {
            $err = 0;
            $shop_name = $this->instance_name;
            $data = array(
                'mansong_name' => $mansong_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'shop_id' => $this->instance_id,
                'shop_name' => $shop_name,
                'status' => 0, // 状态重新设置
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
            if ($range_type == 0 && ! empty($goods_id_array)) {
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
                $this->addUserLog('修改满减送', $err);
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
                $coupon_type = new VslSeckillModel();
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
            if (! empty($list)) {
                foreach ($list as $k => $v) {
                    $goods = new VslGoodsModel();
                    $goods_info = $goods->getInfo([
                        'goods_id' => $v['goods_id']
                    ], 'price, stock');
                    $picture = new AlbumPictureModel();
                    $pic_info = array();
                    $pic_info['pic_cover'] = '';
                    if (! empty($v['goods_picture'])) {
                        $pic_info = $picture->getInfo(['pic_id' =>$v['goods_picture']],'pic_cover,pic_cover_mid,pic_cover_micro');
                    }
                    $v['picture_info'] = $pic_info;
                    $v['price'] = $goods_info['price'];
                    $v['stock'] = $goods_info['stock'];
                    if(getAddons('shop', $this->website_id)){
                        $shop = new VslShopModel();
                        if (!empty($v['shop_id']) && $v['shop_id'] > 0) {
                            $shop_info = $shop->get(['shop_id' => $v['shop_id']]);
                        } else {
                            $shop_info['shop_name'] = '自营店';
                        }
                    }else{
                        $shop_info['shop_name'] = '自营店';
                    }
                    
                    $list[$k]['shop_name'] = $shop_info['shop_name'];
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

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::addPromotiondiscount()
     */
    public function addPromotiondiscount($discount_name, $start_time, $end_time, $remark, $goods_id_array)
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
                'create_time' => time(),
                'website_id'=>$this->website_id
            );
            $promotion_discount->save($data);
            $discount_id = $promotion_discount->discount_id;
            $goods_id_array = explode(',', $goods_id_array);
            $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
            $promotion_discount_goods->destroy([
                'discount_id' => $discount_id
            ]);
            foreach ($goods_id_array as $k => $v) {
                // 添加检测考虑商品在一个时间段内只能有一种活动

                $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
                $discount_info = explode(':', $v);
                $goods_discount = new GoodsDiscount();
                $count = $goods_discount->getGoodsIsDiscount($discount_info[0], $start_time, $end_time);
                // 查询商品名称图片
                if ($count > 0) {
                    $promotion_discount->rollback();
                    return ACTIVE_REPRET;
                }
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
            $this->addUserLog('添加限时折扣', $discount_id);
            $promotion_discount->commit();
            return $discount_id;
        } catch (\Exception $e) {
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }

    //添加满减送
    public function addmansong()
    {
        if (request()->isAjax()) {
            $mansong_name = $_POST['mansong_name'];
            $level = $_POST['level'];
            $remark = $_POST['remark'];
            $status = $_POST['status'];
            $range = $_POST['range'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $shop_id = $this->instance_id;
            $type = $_POST['type'];
            $range_type = $_POST['range_type'];
            $rule = $_POST['rule'];
            $goods_id_array = $_POST['goods_id_array'];
            $res = $this->addPromotionMansong($mansong_name, $start_time, $end_time, $shop_id, $remark, $type, $range_type, $rule, $goods_id_array,$range,$status,$level);
            if($res){
                $this->addUserLog('添加满减送', $res);
            }
            return AjaxReturn($res);
        } else {
            $this->fetch('template/addMansong');
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::updatePromotionDiscount()
     */
    public function updatePromotionDiscount($discount_id, $discount_name, $start_time, $end_time, $remark, $goods_id_array)
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
            $this->addUserLog('修改限时折扣', $discount_id);
            $promotion_discount->commit();
            return $discount_id;
        } catch (\Exception $e) {
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
                if (! empty($goods_id_list)) {

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
            $this->addUserLog('删除限时折扣', $discount_id);
            $promotion_discount->commit();
            return $retval;
        } catch (\Exception $e) {
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }



    /**
     * (non-PHPdoc)
     * 关闭满减送
     * @see \data\api\IPromote::closePromotionDiscount()
     */
    public function closePromotionMansong($mansong_id)
    {
        $promotion_mansong = new VslPromotionMansongModel();
        $retval = $promotion_mansong->save([
            'status' => 3
        ], [
            'mansong_id' => $mansong_id,
        ]);
        if ($retval == 1) {
            $promotion_mansong_goods = new VslPromotionMansongGoodsModel();

            $retval = $promotion_mansong_goods->save([
                'status' => 3
            ], [
                'mansong_id' => $mansong_id
            ]);
        }

        if($retval){
            $this->addUserLog('关闭满减送', $mansong_id);
        }
        return $retval;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::closePromotionDiscount()
     */
    public function delPromotionMansong()
    {
        $mansong_id = $_REQUEST['mansong_id'];
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
                    return - 1;
                }
                $promotion_mansong->destroy($v);
                $promotion_mansong_goods->destroy([
                    'mansong_id' => $v
                ]);
                $promot_mansong_rule->destroy([
                    'mansong_id' => $v
                ]);
            }
            $this->addUserLog('删除满减送', $mansong_id);
            $promotion_mansong->commit();
            $data['code'] = 1;
            return json($data);
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
            "website_id"=>$this->website_id
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
            "website_id"=>$this->website_id
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
            "website_id"=>$this->website_id
        ]);
        $this->addUserLog('更新或添加满额包邮的信息', 1);
        return 1;
    }
    public function setConfig()
    {
        $Server = new mansong();
        $is_use = $_POST['is_use'] ?: 0;
        $result = $Server->setConfig($is_use);
        if($result){
            $this->addUserLog('保存满减送设置', $result);
        }
        setAddons('fullcut', $this->website_id, $this->instance_id);
        return AjaxReturn($result);

    }

    public function confirmOrderFullCut()
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
        $goods_man_song_model = new mansong();
        $fullCutLists = $goods_man_song_model->getCartManSong($new_data);
        //var_dump($fullCutLists);
        $return_data = [];
        foreach ($fullCutLists as $shop_id => $full_cut_info) {
            $temp_mansong = [];
            $temp_mansong['man_song_id'] = $full_cut_info['full_cut']['man_song_id'];
            $temp_mansong['rule_id'] = $full_cut_info['full_cut']['rule_id'];
            $temp_mansong['man_song_name'] = $full_cut_info['full_cut']['man_song_name'];
            $temp_mansong['discount'] = $full_cut_info['full_cut']['discount'];
            $temp_mansong['price'] = $full_cut_info['full_cut']['price'];
            $temp_mansong['shop_id'] = $full_cut_info['full_cut']['shop_id'];
            $temp_mansong['use_shop_id'] = $shop_id;
            $temp_mansong['goods_limit'] = $full_cut_info['full_cut']['goods_limit'];
            $temp_mansong['free_shipping'] = $full_cut_info['shipping']['free_shipping'] ? true : false;

            $return_data[] = $temp_mansong;
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => $return_data]);
    }
}
