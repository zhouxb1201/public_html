<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/26 0026
 * Time: 14:44
 */
namespace addons\integral\model;

use addons\coupontype\model\VslCouponTypeModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\integral\service\Integral;
use data\model\BaseModel;
use data\model\VslGoodsGroupModel;

class VslIntegralGoodsModel extends BaseModel
{
    protected $table = 'vsl_integral_goods';

    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getGoodsViewList($page_index, $page_size, $condition, $order){

        $queryList = $this->getGoodsViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getGoodsrViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 积分商城首页商品类别
     * **/
    public function wapIntegralGoods($condition = [], $field = '*')
    {
        $goods_list = $this->alias('ng')
            ->join('vsl_integral_goods_sku ngs', 'ng.goods_id = ngs.goods_id', 'LEFT')
            ->join('sys_album_picture sap', 'ng.picture = sap.pic_id', 'LEFT')
            ->join('vsl_shop vs', 'ng.shop_id = vs.shop_id and ng.website_id = vs.website_id', 'LEFT')
            ->where($condition)
            ->field($field)
            ->select();
        if(!$goods_list){
            return [];
        }
        foreach($goods_list  as $k=>$v){
            //将积分组合成一个数组
            $price_arr[$v['goods_id']][$k] = $v['price'];
            $point_arr[$v['goods_id']][$k] = $v['point_exchange'];
        }
        foreach($goods_list as $k1=>$v1){
            $now_time = time();
            if($v1['goods_exchange_type'] == 1){//优惠券
                //判断时间是否过期
                $coupon = new VslCouponTypeModel();
                $coupon_info = $coupon->getInfo(['coupon_type_id' => $v1['coupon_type_id']], 'end_time');
                if ($now_time > $coupon_info['end_time']) {//过期了
                    continue;
                }
            }elseif ($v1['goods_exchange_type'] == 2){//礼品券
                //判断时间是否过期
                $giftvoucher = new VslGiftVoucherModel();
                $giftvoucher_info = $giftvoucher->getInfo(['gift_voucher_id' => $v1['gift_voucher_id']], 'end_time');
                if ($now_time > $giftvoucher_info['end_time']) {//过期了
                    continue;
                }
            }
            //得到最低价格和积分
            $lowest_price = min($price_arr[$v1['goods_id']]);
            $lowest_point = min($point_arr[$v1['goods_id']]);
            $goods_list_arr[$v1['goods_id']]['goods_id'] = $v1['goods_id'];
            $goods_list_arr[$v1['goods_id']]['goods_name'] = $v1['goods_name'];
            $goods_list_arr[$v1['goods_id']]['price'] = $lowest_price;
            $goods_list_arr[$v1['goods_id']]['point'] = $lowest_point;
            $goods_list_arr[$v1['goods_id']]['market_price'] = $v1['market_price'];
            $goods_list_arr[$v1['goods_id']]['sales'] = $v1['sales'];
            $goods_list_arr[$v1['goods_id']]['logo'] = $v1['pic_cover'] ? getApiSrc($v['pic_cover']) : '';
            if($v1['goods_exchange_type'] == 0){
                $goods_type = '正常商品';
            }elseif($v1['goods_exchange_type'] == 1){
                $goods_type = '优惠券';
            }elseif($v1['goods_exchange_type'] == 2){
                $goods_type = '礼品券';
            }elseif($v1['goods_exchange_type'] == 3){
                $goods_type = '余额';
            }
            $goods_list_arr[$v1['goods_id']]['type'] = $goods_type;
            $goods_list_arr[$v1['goods_id']]['goods_exchange_type'] = $v1['goods_exchange_type'];//0-正常商品 1-优惠券 2-礼品券 3-余额
        }
        foreach($goods_list_arr as $k2=>$v2){
            $goods_lists[] = $v2;
        }
        return $goods_lists;
    }

    /**
     * 查询商品的视图
     * @param unknown $condition
     * @param unknown $field
     * @param unknown $order
     * @return unknown
     */
    public function getGoodsViewQueryField($condition, $field, $order=""){
        $viewObj = $this->alias('ng')
            ->join('vsl_integral_category ngc','ng.category_id = ngc.integral_category_id','left')
            ->join('vsl_goods_brand ngb','ng.brand_id = ngb.brand_id','left')
            ->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')
            ->field($field);
        $list = $viewObj->where($condition)
            ->order($order)
            ->select();
        return $list;
    }
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getGoodsViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('ng')
            ->join('vsl_integral_category ngc','ng.category_id = ngc.integral_category_id','left')
            ->join('vsl_goods_brand ngb','ng.brand_id = ngb.brand_id','left')
            ->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')
            ->join('vsl_shop nss','ng.shop_id = nss.shop_id and ng.website_id = nss.website_id','left')
            ->field('ng.goods_id, ng.goods_name, ng.shop_id, ng.category_id, ng.brand_id, ng.group_id_array,
             ng.promotion_type, ng.goods_type, ng.market_price, ng.price, ng.promotion_price, 
            ng.cost_price, ng.point_exchange_type, ng.point_exchange, ng.give_point, 
            ng.is_member_discount, ng.shipping_fee, ng.shipping_fee_id, ng.stock, ng.max_buy, 
            ng.min_stock_alarm, ng.clicks, ng.sales, ng.collects, ng.star, ng.evaluates, 
            ng.shares, ng.province_id, ng.city_id, ng.picture, ng.keywords, ng.introduction, 
            ng.description, ng.QRcode, ng.code, ng.is_stock_visible, ng.is_hot, ng.is_recommend, 
            ng.is_new, ng.is_pre_sale, ng.is_bill, ng.state, ng.sale_date, ng.create_time, 
            ng.update_time, ng.sort, ng.real_sales, ng.short_name, ngb.brand_name, ngb.brand_pic, ngc.integral_category_id, ngc.category_name,sap.pic_cover, sap.pic_cover_micro,sap.pic_cover_mid,sap.pic_cover_small,nss.shop_name,nss.shop_type,sap.pic_id,sap.upload_type, sap.domain, sap.bucket, ng.goods_spec_format,ng.goods_exchange_type,coupon_type_id,balance,gift_voucher_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if(!empty($list))
        {
            $goods_group_model = new VslGoodsGroupModel();
            $goods_sku = new VslIntegralGoodsSkuModel();
            foreach ($list as $k=>$v)
            {
                //获取group列表
                $group_name_query = $goods_group_model->all($v['group_id_array']);

                $list[$k]['group_query'] = $group_name_query;
                //获取sku列表
                $sku_list = $goods_sku->where(['goods_id'=>$v['goods_id']])->select();
                //循环出该商品最低的积分
                $point_arr = [];
                foreach($sku_list as $k1=>$v1){
                    $point_arr[] = $v1['exchange_point'];
                }
                $min_point = 0;
                $min_point = min($point_arr);
                $list[$k]['goods_point'] = $min_point;
                $list[$k]['sku_list'] = $sku_list;
            }
        }
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getGoodsrViewCount($condition)
    {
        $viewObj = $this->alias('ng')
            ->join('vsl_integral_category ngc','ng.category_id = ngc.integral_category_id','left')
            ->join('vsl_goods_brand ngb','ng.brand_id = ngb.brand_id','left')
            ->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')
            ->join('vsl_shop nss','ng.shop_id = nss.shop_id and ng.website_id = nss.website_id','left')
            ->field('ng.goods_id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

    public function wapGoods($page_index = 1, $page_size = PAGESIZE, $condition = [], $field = '*', $order = '', $group = '')
    {
        $view_obj = $this->alias('ng')
            ->join('vsl_integral_goods_sku ngs', 'ng.goods_id = ngs.goods_id', 'LEFT')
            ->join('sys_album_picture sap', 'ng.picture = sap.pic_id', 'LEFT')
            ->field($field);
        $query_list = $this->viewPageQuerys($view_obj, $page_index, $page_size, $condition, $order, $group);
        //获取积分商品的最低积分和最低价格
        $query_arr = objToArr($query_list);
        $price_arr = [];
        $point_arr = [];
        foreach($query_arr as $v){
            $price_arr[$v['goods_id']][] = $v['price'];
            $point_arr[$v['goods_id']][] = $v['exchange_point'];
        }
        $integral = new Integral();
        foreach($query_arr as $k1=>$v1){
            $query_arr1[$v1['goods_id']]['goods_id'] = $v1['goods_id'];
            $query_arr1[$v1['goods_id']]['goods_name'] = $v1['goods_name'];
            $query_arr1[$v1['goods_id']]['sales'] = $v1['sales'];
            $query_arr1[$v1['goods_id']]['pic_cover'] = $v1['pic_cover'];
            $query_arr1[$v1['goods_id']]['price'] = min($price_arr[$v1['goods_id']]);
            $query_arr1[$v1['goods_id']]['exchange_point'] = min($point_arr[$v1['goods_id']]);
            $type = $integral->getIntegralGoodsType($v1['goods_exchange_type']);
            $query_arr1[$v1['goods_id']]['type'] = $type;
        }
        $query_arr1 = array_values($query_arr1);
        $query_count = $this->alias('ng')
            ->join('vsl_goods_sku ngs', 'ng.goods_id = ngs.goods_id', 'LEFT')
            ->field('COUNT(ng.goods_id)')
            ->where($condition)
            ->group($group)
            ->select();
        $query_count = count($query_count);
        //var_dump(Db::table('')->getLastSql());

        $list = $this->setReturnList($query_arr1, $query_count, $page_size);
        return $list;
    }

    public function album_picture()
    {
        return $this->belongsTo('\data\model\AlbumPictureModel','picture','pic_id');
    }

    public function sku()
    {
        return $this->hasMany('VslIntegralGoodsSkuModel','goods_id','goods_id');
    }

    public function shop()
    {
        return $this->belongsTo('\addons\shop\model\VslShopModel','shop_id','shop_id');
    }

    public function shipping_company()
    {
        return $this->belongsTo('VslOrderExpressCompanyModel','shipping_fee_id','co_id');
    }
}
