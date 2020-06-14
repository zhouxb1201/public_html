<?php
namespace addons\discount\model;
/**
 * 应用
 */
use data\model\AlbumPictureModel as AlbumPictureModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslPromotionDiscountGoodsModel;
use data\model\VslPromotionDiscountModel;
use data\model\VslPromotionMansongGoodsModel;
use data\model\VslPromotionMansongModel;
use data\service\BaseService as BaseService;
use data\service\promotion\GoodsDiscount;
use think\Db;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
class Discount extends BaseService
{

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }


    //修改商品活动类型
    public function updatepromotion_type($range, $goodsid='', $discount_id){

        if($goodsid!=0 || $range==2){
//            $sql = "update `vsl_goods` set `promotion_type` = 5 where `goods_id` in($goodsid)"; // 4.部分商品
            $goods_mdl = new VslGoodsModel();
            $condition['goods_id'] = ['in', $goodsid];
            $data['promotion_type'] = 5;
            $data['promote_id'] = $discount_id;
            $res = $goods_mdl->where($condition)->update($data);
            return $res;
        }
    }
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IPromote::addPromotiondiscount()
     */

    //获取最优折扣活动
    public function get_best_discount($shopid){
        //0表示自营
        if($shopid=='0') {
            $sql = 'select * from `vsl_promotion_discount` where `status` =1 and `start_time`<' . time() . ' and `end_time` >' . time() . ' and (`range`= 1 OR `range`=2) and `website_id` = '.$this->website_id.' order by `level` desc limit 0 , 1';
        }else{
            $sql = 'select * from `vsl_promotion_discount` where `status` =1 and `shop_id` != 0 and (`range`=3 OR `range`=2) and `start_time`<' . time() . ' and `end_time` >' . time() . ' and `website_id` = '.$this->website_id.' order by `level` desc limit 0 , 1';
        }
        $result = Db::query($sql);
        return $result;
    }

    //活动价格

    public function get_discount_price($price,$discount_num){

        return number_format($price*$discount_num/10,1,'.','');
    }


    //判断是否是折扣商品
    //$type: 店铺ID，0表自营店，取范围为自营和全平台（1,2）
    //               !=0表店铺，取范围为全平台和店铺（2，3）
    public function check_is_discount_product($goods_id,$type){

        $time = time();
        if($type=='0') {
            $sql = "SELECT a.* ,b.`goods_id`,b.`goods_name`,b.`status` FROM `vsl_promotion_discount` AS a LEFT JOIN `vsl_promotion_discount_goods` AS b ON a.`discount_id` = b.`discount_id` WHERE (b.`goods_id` = $goods_id or a.`range` = 1) and (a.`range_type` = 1 or a.`range_type` = 2) and a.`start_time` < $time and a.`end_time` > $time ORDER BY a.`level` asc LIMIT 0,1";
        }else{
            $sql = "SELECT a.* ,b.`goods_id`,b.`goods_name`,b.`status` FROM `vsl_promotion_discount` AS a LEFT JOIN `vsl_promotion_discount_goods` AS b ON a.`discount_id` = b.`discount_id` WHERE (b.`goods_id` = $goods_id or a.`range` = 1) and (a.`range_type` = 3 or a.`range_type` = 2) and a.`start_time` < $time and a.`end_time` > $time ORDER BY a.`shop_id` desc, a.`level` asc LIMIT 0,1";
        }
        $result = Db::query($sql);
        return $result;

    }


    public function addPromotiondiscount($discount_name, $start_time, $end_time, $remark, $goods_id_array,$level,$range,$status,$range_type,$discount_num,$shop_id,$discount_type,$uniform_discount_type,$uniform_discount,$integer_type,$uniform_price_type,$uniform_price)
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $promotion_discount->startTrans();
        try {
            //print_r($goods_id_array);exit;
            $shop_name = $this->instance_name;
            $data = array(
                'discount_name' => $discount_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'shop_id' => $shop_id,
                'shop_name' => $shop_name,
                'status' => $status,
                'level' => $level,
                'range' => $range,
                'range_type'=>$range_type,
                'discount_num'=>$discount_num,
                'remark' => $remark,
                'create_time' => time(),
                'website_id'=>$this->website_id,
                'discount_type'=>$discount_type,
                'uniform_discount_type'=>$uniform_discount_type,
                'uniform_discount'=>$uniform_discount,
                'integer_type'=>$integer_type,
                'uniform_price_type'=>$uniform_price_type,
                'uniform_price'=>$uniform_price
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
                ], 'goods_name,picture,price');

                $dis = $discount_info[1];
                //new
                if($uniform_discount_type == 1){
                    $dis = $uniform_discount;
                }elseif($uniform_price_type == 1){
                    $dis = $uniform_price;
                }

                $data_goods = array(
                    'discount_id' => $discount_id,
                    'goods_id' => $discount_info[0],
                    'discount' => $dis,
                    'status' => 0,
                    'start_time' => getTimeTurnTimeStamp($start_time),
                    'end_time' => getTimeTurnTimeStamp($end_time),
                    'goods_name' => $goods_info['goods_name'],
                    'goods_picture' => $goods_info['picture'],
                    'discount_type'=>$discount_type
                );
                $this->updatepromotion_type($range, $discount_info[0], $discount_id);
                $promotion_discount_goods->save($data_goods);
            }
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
     * @see \data\api\IPromote::updatePromotionDiscount()
     */
    public function updatePromotionDiscount($discount_id, $discount_name, $start_time, $end_time, $remark, $goods_id_array,$level,$range,$status,$range_type,$discount_num,$discount_type,$uniform_discount_type,$uniform_discount,$integer_type,$uniform_price_type,$uniform_price)
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $promotion_discount->startTrans();
        try {
            $shop_name = $this->instance_name;
            $data = array(
                'discount_name' => $discount_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'shop_name' => $shop_name,
                'status' => 0,
                'remark' => $remark,
                'level' => $level,
                'range' => $range,
                'status' => 0,
                'range_type' => $range_type,
                'discount_num' => $discount_num,
                'create_time' => time(),
                'discount_type'=>$discount_type,
                'uniform_discount_type'=>$uniform_discount_type,
                'uniform_discount'=>$uniform_discount,
                'integer_type'=>$integer_type,
                'uniform_price_type'=>$uniform_price_type,
                'uniform_price'=>$uniform_price
            );
            $promotion_discount->save($data, [
                'discount_id' => $discount_id
            ]);
            $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
            
            //先将旧的商品prmotion清0，再重新加
            $old_id_array = $promotion_discount_goods->getQuery(['discount_id'=>$discount_id],'goods_id','');
            
            foreach ($old_id_array as $ks=>$vs){
                $goods_model = new VslGoodsModel();
                $goods_model->isUpdate(false)->save(['promotion_type'=>0],['goods_id'=>$vs['goods_id']]);
            }
            $goods_id_array = explode(',', $goods_id_array);
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

                $dis = $discount_info[1];
                //new
                if($uniform_discount_type == 1){
                    $dis = $uniform_discount;
                }elseif($uniform_price_type == 1){
                    $dis = $uniform_price;
                }

                $data_goods = array(
                    'discount_id' => $discount_id,
                    'goods_id' => $discount_info[0],
                    'discount' => $dis,
                    'status' => 0,
                    'start_time' => getTimeTurnTimeStamp($start_time),
                    'end_time' => getTimeTurnTimeStamp($end_time),
                    'goods_name' => $goods_info['goods_name'],
                    'goods_picture' => $goods_info['picture'],
                    'discount_type'=>$discount_type
                );
                $this->updatepromotion_type($range,$discount_info[0],$discount_id);
                $promotion_discount_goods->save($data_goods);
            }
            $promotion_discount->commit();
            return $discount_id;
        } catch (\Exception $e) {
            $promotion_discount->rollback();
             print_r($e->getMessage());exit;
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
            $promotion_discount->commit();
            return $retval;
        } catch (\Exception $e) {
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
        if (! empty($promotion_goods_list)) {
            foreach ($promotion_goods_list as $k => $v) {
                $goods = new VslGoodsModel();
                $goods_info = $goods->getInfo([
                    'goods_id' => $v['goods_id']
                ], 'price, stock');
                $picture = new AlbumPictureModel();
                $pic_info = array();
                $pic_info['pic_cover'] = '';
                if (! empty($v['goods_picture'])) {
                    $pic_info = $picture->getInfo(['pic_id' => $v['goods_picture']],'pic_cover,pic_cover_mid,pic_cover_micro');
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
                    return - 1;
                }
                $promotion_discount->destroy($v);
                $promotion_discount_goods->destroy([
                    'discount_id' => $v
                ]);
            }
            $promotion_discount->commit();
            return 1;
        } catch (\Exception $e) {
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }


    /*
       * 开启限时折扣设置
       *
       */
    public function discountSet($is_use)
    {
        $ConfigService = new AddonsConfigService();
        $ManSong_info = $ConfigService ->getAddonsConfig("discount");
        if (!empty($ManSong_info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_use,'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'discount'
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig('', '限时抢扣', $is_use,'discount');
        }
        return $res;
    }

    /*
     * 获取限时折扣设置
     *
     */
    public function getDiscountSite($website_id){
        $config = new AddonsConfigService();
        $manSong = $config->getAddonsConfig("discount");
        return $manSong;
    }

    /*
     * 更新活动状态
     *
     */
    public function update_discount_status($data,$where){

        $promotion_discount = new VslPromotionDiscountModel();
        $retval = $promotion_discount->save($data,$where);
        return $retval;

    }

}