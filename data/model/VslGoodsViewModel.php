<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
use data\model\VslGoodsGroupModel as VslGoodsGroupModel;
use data\model\VslGoodsSkuModel as VslGoodsSkuModel;
use think\Db;

/**
 * 商品表视图
 * @author  www.vslai.com
 *
 */
class VslGoodsViewModel extends BaseModel {

    protected $table = 'vsl_goods';
    
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getGoodsViewList($page_index, $page_size, $condition, $order, $field="*"){
        $condition['ng.for_store'] = 0;
        $queryList = $this->getGoodsViewQuery($page_index, $page_size, $condition, $order, $field);
        $queryCount = $this->getGoodsrViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
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
            ->join('vsl_goods_category ngc','ng.category_id = ngc.category_id','left')
            ->join('vsl_goods_brand ngb','ng.brand_id = ngb.brand_id','left')
            ->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')
            ->join('vsl_shop nss','ng.website_id = nss.website_id','left')
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
        ->join('vsl_goods_category ngc','ng.category_id = ngc.category_id','left')
        ->join('vsl_goods_brand ngb','ng.brand_id = ngb.brand_id','left')
        ->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')
        ->join('vsl_shop nss','ng.shop_id = nss.shop_id and ng.website_id = nss.website_id','left')
        ->field('ng.wurl,ng.wplatForm,ng.goods_id, ng.illegal_reason, ng.goods_name, ng.shop_id, ng.category_id, ng.brand_id, ng.group_id_array,
             ng.promotion_type, ng.goods_type, ng.market_price, ng.price, ng.promotion_price, 
            ng.cost_price, ng.point_exchange_type, ng.point_exchange, ng.give_point, 
            ng.is_member_discount, ng.shipping_fee, ng.shipping_fee_id, ng.stock, ng.max_buy, 
            ng.min_stock_alarm, ng.clicks, ng.sales, ng.collects, ng.star, ng.evaluates, 
            ng.shares, ng.province_id, ng.city_id, ng.picture, ng.keywords, ng.introduction, 
            ng.description, ng.QRcode, ng.code, ng.is_stock_visible, ng.is_hot, ng.is_recommend, 
            ng.is_new, ng.is_pre_sale, ng.is_bill, ng.state, ng.sale_date, ng.create_time, 
            ng.update_time, ng.sort,nss.shop_state, ng.real_sales, ng.short_name, ngb.brand_name,
            ngb.brand_pic, ngc.category_id, ngc.category_name,sap.pic_cover, sap.pic_cover_micro,
            sap.pic_cover_mid,sap.pic_cover_small,nss.shop_name,nss.shop_type,sap.pic_id,sap.upload_type,
            sap.domain, sap.bucket,ng.goods_spec_format,ng.is_distribution,ng.distribution_rule,ng.is_bonus_global,ng.is_bonus_area,
            ng.is_bonus_team,ng.bonus_rule,ng.is_promotion,ng.is_shipping_free');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if(!empty($list))
        {
            $goods_group_model = new VslGoodsGroupModel();
            $goods_sku = new VslGoodsSkuModel();
            foreach ($list as $k=>$v)
            {
                //获取group列表
                $group_name_query = $goods_group_model->all($v['group_id_array']);
               
                $list[$k]['group_query'] = $group_name_query;
                //获取sku列表
                $sku_list = $goods_sku->where(['goods_id'=>$v['goods_id']])->select();
                if(!$sku_list){
                    $goods_sku->save(['goods_id' => $v['goods_id'],'market_price' => $v['market_price'],'price' => $v['price'],'promote_price' => $v['price'],'cost_price' => $v['cost_price'],'stock' => $v['stock'],'create_date' => time(),'code' => $v['code'], 'QRcode' => $v['QRcode']]);
                    $sku_list = $goods_sku->where(['goods_id'=>$v['goods_id']])->select();
                }
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
        ->join('vsl_goods_category ngc','ng.category_id = ngc.category_id','left')
        ->join('vsl_goods_brand ngb','ng.brand_id = ngb.brand_id','left')
        ->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')
        ->join('vsl_shop nss','ng.shop_id = nss.shop_id and ng.website_id = nss.website_id','left')
        ->field('ng.goods_id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

    public function wapGoods($page_index = 1, $page_size = PAGESIZE, $condition = [], $field = '*', $order = '', $group = '')
    {
        $condition['ng.for_store'] = 0;
        $view_obj = $this->alias('ng')
            ->join('vsl_goods_sku ngs', 'ng.goods_id = ngs.goods_id', 'LEFT')
            ->join('sys_album_picture sap', 'ng.picture = sap.pic_id', 'LEFT')
            ->join('vsl_shop vs', 'ng.shop_id = vs.shop_id and ng.website_id = vs.website_id', 'LEFT')
            ->join('vsl_goods_discount vgd', 'vgd.goods_id = ng.goods_id', 'LEFT')
            ->field($field);
        $query_list = $this->viewPageQuerys($view_obj, $page_index, $page_size, $condition, $order, $group);
        $query_count = $this->alias('ng')
            ->join('vsl_goods_sku ngs', 'ng.goods_id = ngs.goods_id', 'LEFT')
            ->join('vsl_shop vs', 'ng.shop_id = vs.shop_id and ng.website_id = vs.website_id', 'LEFT')
            ->join('vsl_goods_discount vgd', 'vgd.goods_id = ng.goods_id', 'LEFT')
            ->field('COUNT(ng.goods_id)')
            ->where($condition)
            ->group($group)
            ->select();
        $query_count = count($query_count);

        $list = $this->setReturnList($query_list, $query_count, $page_size);
        return $list;
    }
}