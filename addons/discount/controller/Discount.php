<?php
namespace addons\discount\controller;

use addons\shop\model\VslShopModel;
use addons\discount\Discount as baseDiscount;
use data\model\AlbumPictureModel as AlbumPictureModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslPromotionDiscountGoodsModel;
use data\model\VslPromotionDiscountModel;
use data\model\VslPromotionMansongGoodsModel;
use data\model\VslPromotionMansongModel;
use data\model\VslPromotionMansongRuleModel;
use data\service\promotion\GoodsDiscount;
use data\service\Goods as GoodsService;
use addons\discount\model\Discount as discountModel;
use think\Db;

/**
 * 店铺设置控制器
 *
 * @author  www.vslai.com
 *
 */
class Discount extends baseDiscount
{
    public $instance_id;
    public $instance_name;

    public function __construct(){
        parent::__construct();
    }
    
    public function discountList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
        $discount = new discountModel();

        $condition = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'discount_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        $list = $discount->getPromotionDiscountList($page_index, $page_size, $condition);
        $list = $this->object2array($list);
        //更新状态
        foreach ($list['data'] as $k=>$v){

            //未编辑
            if($v['start_time']>time() && $v['end_time']>time()){
                $discount->update_discount_status(['status'=>0],['discount_id'=>$v['discount_id']]);
                $list['data'][$k]['status'] = 0;
            }

            //开始
            if($v['start_time']<time() && $v['end_time']>time() && $v['status']==0){
                $discount->update_discount_status(['status'=>1],['discount_id'=>$v['discount_id']]);
                $list['data'][$k]['status'] = 1;
            }

            //结束
            if($v['start_time']<time() && $v['end_time']<time()){
                $discount->update_discount_status(['status'=>4],['discount_id'=>$v['discount_id']]);
                $list['data'][$k]['status'] = 4;
            }
        }
        return $list;
    }


    


    /**
     * 获取限时折扣详情
     */
    public function getDiscountDetail($discount_id = 0)
    {
        if (empty($discount_id)) {
            $this->error("没有获取到抢购信息");
        }
        $detail = $this->getPromotionDiscountDetail($discount_id);
        return $detail;
    }

    /**
     * 获取限时折扣详情
     */
    public function gettail()
    {
        $discount_id = request()->post('discount_id');
        if (empty($discount_id)) {
            $this->error("没有获取到抢购信息");
        }
        $detail = $this->getPromotionDiscountDetail($discount_id);
        return $detail;
    }



    //关闭限时折扣
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
                    'promotion_type' => 5,
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
            if($retval){
                $this->addUserLog('关闭限时抢购', $discount_id);
            }
            $promotion_discount->commit();
            return $retval;
        } catch (\Exception $e) {
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * 修改限时折扣
     */
    public function editdiscount()
    {
        $discount = new discountModel();
        $discount_id = isset($_POST['discount_id']) ? $_POST['discount_id'] : '';
        $discount_name = isset($_POST['discount_name']) ? $_POST['discount_name'] : '';
        $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : '';
        $end_time = isset($_POST['end_time']) ? $_POST['end_time']." 23:59:59" : '';
        $level = isset($_POST['level']) ? $_POST['level'] : '1';
        $range = isset($_POST['range']) ? $_POST['range'] : '1';
        $status = isset($_POST['status']) ? $_POST['status'] : '0';
        $range_type = isset($_POST['range_type']) ? $_POST['range_type'] : '2';
        $discount_num = isset($_POST['discount']) ? $_POST['discount'] : '10';
        $remark = isset($_POST['remark']) ? $_POST['remark'] : '';
        $goods_id_array = isset($_POST['goods_id_array']) ? $_POST['goods_id_array'] : '';
        if(strtotime($start_time)>strtotime($end_time)){
            $json['code'] = -1;
            $json['message'] = "开始时间不能大于结束时间";
            return json_encode($json);
        }

        //new
        $discount_type = isset($_POST['discount_type']) ? $_POST['discount_type'] : '1';
        $uniform_discount_type = isset($_POST['uniform_discount_type']) ? $_POST['uniform_discount_type'] : '0';
        $uniform_discount = isset($_POST['uniform_discount']) ? $_POST['uniform_discount'] : '';
        $integer_type = isset($_POST['integer_type']) ? $_POST['integer_type'] : '0';
        $uniform_price_type = isset($_POST['uniform_price_type']) ? $_POST['uniform_price_type'] : '0';
        $uniform_price = isset($_POST['uniform_price']) ? $_POST['uniform_price'] : '';

        $retval = $discount->updatePromotionDiscount($discount_id, $discount_name, $start_time, $end_time, $remark,$goods_id_array,$level,$range,$status,$range_type,$discount_num,$discount_type,$uniform_discount_type,$uniform_discount,$integer_type,$uniform_price_type,$uniform_price);
        return AjaxReturn($retval);
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
     * 获取限时折扣详情
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
                $promotion_goods_list[$k]['shop_name'] =  "自营店";
                if(getAddons('shop', $this->website_id)){
                    $shop = new VslShopModel();
                    $shop_info = $shop->alias('a')->join('vsl_goods b', 'a.shop_id = b.shop_id', 'left')->field('a.shop_name,a.shop_id')->where(['b.goods_id'=>$v['goods_id']])->find();
                    if ($shop_info['shop_id']>0) {
                        $promotion_goods_list[$k]['shop_name'] = $shop_info['shop_name'];
                    }
                }
            }
        }
        //print_r($promotion_detail);exit;
        $promotion_detail['goods_list'] = $promotion_goods_list;
        return $promotion_detail;
    }


    /**
     *
     * {@inheritdoc}
     *删除限时折扣
     * @see \data\api\IPromote::delPromotionDiscount()
     */
    public function delPromotionDiscount($discount_id)
    {
        $promotion_discount = new VslPromotionDiscountModel();
        $promotion_discount_goods = new VslPromotionDiscountGoodsModel();
        $promotion_discount->startTrans();
        try {
            $discount_id_array = explode(',', $discount_id);
            $retval = 1;
            foreach ($discount_id_array as $k => $v) {
                $goods_mdl = new VslGoodsModel();
                $promotion_detail = $promotion_discount->get($discount_id);
                if ($promotion_detail['status'] == 1) {
                    $promotion_discount->rollback();
                    return - 1;
                }
                //查出对应参加限时折扣的商品
                $goods_ids_list = $promotion_discount_goods->getQuery(['discount_id' => $v], 'goods_id','');
                $goods_ids_list = objToArr($goods_ids_list);
                if($goods_ids_list){
                    $goods_ids_arr = array_column($goods_ids_list,'goods_id');
                    $goods_condition['goods_id'] = ['in', $goods_ids_arr];
                    $goods_condition['promotion_type'] = 5;
                    $goods_condition['website_id'] = $this->website_id;
                    $goods_mdl->where($goods_condition)->update(['promotion_type'=>0]);
                }
                $promotion_discount->destroy($v);
                $retval = $promotion_discount_goods->destroy([
                    'discount_id' => $v
                ]);
                if(!$retval){
                    $retval = 0;
                }
            }
            if($retval){
                $this->addUserLog('删除限时抢购', $discount_id);
            }
            $promotion_discount->commit();
            return 1;
        } catch (\Exception $e) {
            $promotion_discount->rollback();
            return $e->getMessage();
        }
    }



    /**
     * 删除限时折扣
     */
    public function delDiscount()
    {
        $discount_id = isset($_POST['discount_id']) ? $_POST['discount_id'] : '';
        if (empty($discount_id)) {
            $this->error("没有获取到抢购信息");
        }
        $res = $this->delPromotionDiscount($discount_id);
        if($res){
            $this->addUserLog('删除限时抢购', $discount_id);
        }
        return AjaxReturn($res);
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
            $promotion_mansong->commit();
            return 1;
        } catch (Exception $e) {
            $promotion_mansong->rollback();
            return $e->getMessage();
        }
    }


    function object2array(&$object) {
        $object =  json_decode( json_encode( $object),true);
        return  $object;
    }


    public function closediscount(){

            $discount_id = isset($_POST['discount_id']) ? $_POST['discount_id'] : '';
            if (empty($discount_id)) {
                return AjaxReturn(0);
            }
            $res = $this->closePromotionDiscount($discount_id);
            return AjaxReturn($res);
    }
    /*
     * 取消限时折扣促销状态、参加的此档活动的商品
     * **/
    public function canclePromotionStatus()
    {
        $goods_mdl = new VslGoodsModel();
        $discount_goods = new VslPromotionDiscountGoodsModel();
        $goods_id = request()->post('goods_id', 0);
        $discount_id = request()->post('discount_id', 0);
        $discount_cond['goods_id'] = $goods_id;
        $discount_cond['discount_id'] = $discount_id;
        $discount_goods->where($discount_cond)->delete();
        $condition['goods_id'] = $goods_id;
        $condition['promotion_type'] = 5;
        $condition['website_id'] = $this->website_id;
        $checkGoods = $goods_mdl->getCount($condition);
        if(!$checkGoods){
            return AjaxReturn(1);
        }
        $data['promotion_type'] = 0;
        $bool = $goods_mdl->where($condition)->update($data);
        if(!$bool){
            return AjaxReturn(0);
        }
        return AjaxReturn(1);
    }
    /*
     * 动态获取在当前限时折扣中的商品
     * **/
    public function getCurrDiscountGoodsId()
    {
        $discount_id = $_REQUEST['discount_id']?$_REQUEST['discount_id']:'';
        $info = $this->getDiscountDetail($discount_id);
        $goods_id_array = [];
        if (!empty($info['goods_list'])) {
            foreach ($info['goods_list'] as $k => $v) {
                $goods_id_array[] = $v['goods_id'];
            }
        }
        return $goods_id_array;
    }
    public function getSerchGoodsList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $shop_range_type = request()->post('shop_range_type','');
        $search_text = isset($_POST['search_text'])? $_POST['search_text'] : "";
        $condition['goods_name'] = array(
            "like",
            "%" . $search_text . "%"
        );
        if ($shop_range_type == 1){
            $condition['shop_id'] = $this->instance_id;
        }else if ($shop_range_type == 2){
            
        }else{
            $condition['shop_id'] = $this->instance_id;
        }
        if($_REQUEST['seleted_goods']){
            $condition['goods_id'] = ['in',$_REQUEST['seleted_goods']];
        }
        $condition['website_id'] = $this->website_id;
        $goods_detail = new GoodsService();
        //所有商品信息
        $result = $goods_detail->getSearchGoodsList($page_index, $page_size, $condition);
        
        if(request()->post('discount_id')){
         //折扣信息
            $info = $this->getDiscountDetail(request()->post('discount_id'));
            //将折扣信息组装到商品列表里
            foreach ($result['data'] as $key=>$value){
                foreach ($info['goods_list'] as $k=>$v) {
                   // echo $value['goods_id']."==".$v['goods_id']."<br/>";
                    if ($value['goods_id'] == $v['goods_id']) {
//                        echo $v['goods_id']."----".$key."------".$v['discount']."<br/>";
                        $result['data'][$key]['discount'] = $v['discount'];
                        $result['data'][$key]['discount_type'] = $v['discount_type'];
                    }
                }
            }
        }
        return $result;
    }
    /**
     * 添加限时折扣
     */
    public function addDiscount()
    {
        if (request()->isAjax()) {
            $discount = new discountModel();
            $discount_name = isset($_POST['discount_name']) ? $_POST['discount_name'] : '';
            $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : '';
             $end_time = $_POST['end_time']." 23:59:59";
            $level = isset($_POST['level']) ? $_POST['level'] : '1';
            if(getTimeTurnTimeStamp($start_time)>getTimeTurnTimeStamp($end_time)){
                $json['code'] = -1;
                $json['message'] = "开始时间不能大于结束时间";
                ob_clean();
                print_r(json_encode($json));
                exit;
            }
            $range = isset($_POST['range']) ? $_POST['range'] : '1';
            $status = isset($_POST['status']) ? $_POST['status'] : '0';
            $range_type = isset($_POST['range_type']) ? $_POST['range_type'] : '2';
            $discount_num = isset($_POST['discount']) ? $_POST['discount'] : '10';
            $remark = isset($_POST['remark']) ? $_POST['remark'] : '';
            $goods_id_array = isset($_POST['goods_id_array']) ? $_POST['goods_id_array'] : '';
            //new
            $discount_type = isset($_POST['discount_type']) ? $_POST['discount_type'] : '1';
            $uniform_discount_type = isset($_POST['uniform_discount_type']) ? $_POST['uniform_discount_type'] : '0';
            $uniform_discount = isset($_POST['uniform_discount']) ? $_POST['uniform_discount'] : '';
            $integer_type = isset($_POST['integer_type']) ? $_POST['integer_type'] : '0';
            $uniform_price_type = isset($_POST['uniform_price_type']) ? $_POST['uniform_price_type'] : '0';
            $uniform_price = isset($_POST['uniform_price']) ? $_POST['uniform_price'] : '';

            $retval = $discount->addPromotiondiscount($discount_name, $start_time, $end_time, $remark, $goods_id_array,$level,$range,$status,$range_type,$discount_num,$this->instance_id,$discount_type,$uniform_discount_type,$uniform_discount,$integer_type,$uniform_price_type,$uniform_price);
            return AjaxReturn($retval);
        }
    }
    /**
     * 安装方法
     */
    public function install()
    {
        // TODO: Implement install() method.

        return true;
    }

    /**
     * 卸载方法
     */
    public function uninstall()
    {

        return true;
        // TODO: Implement uninstall() method.
    }

}
