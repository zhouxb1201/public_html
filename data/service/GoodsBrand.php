<?php
namespace data\service;
/**
 * 商品品牌服务层
 */
use data\service\BaseService as BaseService;
use data\model\VslGoodsBrandModel as VslGoodsBrand;
class GoodsBrand extends BaseService
{
    private $goods_brand;
    function __construct(){
        parent::__construct();
        $this->goods_brand =  new VslGoodsBrand();
    
    }
	/* (non-PHPdoc)
     * @see \data\api\IGoodsBrand::getGoodsBrandList()
     */
    public function getGoodsBrandList($page_index = 1, $page_size = 0, $condition = [], $order = 'brand_initial asc', $field = '*')
    {
        $list = $this->goods_brand->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
        // TODO Auto-generated method stub
        
    }

    public function closeoropenBrand($brand_id,$brand_recommend){
        $data = array();
        $data['brand_recommend'] = $brand_recommend;
        $res = $this->goods_brand->save($data,["brand_id"=>$brand_id]);
        return $res;
    }
	/* (non-PHPdoc)
     * @see \data\api\IGoodsBrand::addOrUpdateGoodsBrand()
     */
    public function addOrUpdateGoodsBrand($brand_id, $shop_id=0, $brand_name, $brand_initial, $brand_class, $brand_pic, $brand_recommend, $sort, $brand_category_name = '', $category_id_array = '', $brand_ads, $category_name, $category_id_1, $category_id_2, $category_id_3)
    {
        $data = array(
            'shop_id' => $shop_id,
            'website_id' => $this->website_id,
            'brand_name' => $brand_name,
            'brand_initial' => $brand_initial,
            'brand_pic' => $brand_pic,
            'brand_recommend' => $brand_recommend,
            'sort' => $sort,
            'brand_ads' => $brand_ads,
            'category_name' => $category_name,
            'category_id_1' => $category_id_1,
            'category_id_2' => $category_id_2,
            'category_id_3' => $category_id_3
        );
        //没有值的话删除掉不保存
        if(empty($data['category_id_1'])){
            unset($data['category_id_1']);
        }
        if(empty($data['category_id_2'])){
            unset($data['category_id_2']);
        }
        if(empty($data['category_id_3'])){
            unset($data['category_id_3']);
        }
        if(empty($data['category_name'])){
            unset($data['category_name']);
        }
        if($brand_id == "")
        {
            $res = $this->goods_brand->save($data);
            $data['brand_id'] = $this->goods_brand->brand_id;
            hook("goodsBrandSaveSuccess", $data);
            return $this->goods_brand->brand_id;
        }else{
            $res = $this->goods_brand->save($data,["brand_id"=>$brand_id]);
            $data['brand_id'] = $brand_id;
            hook("goodsBrandSaveSuccess", $data);
            return $res;
        }
        
        // TODO Auto-generated method stub
        
    }

	/* (non-PHPdoc)
     * @see \data\api\IGoodsBrand::ModifyGoodsBrandSort()
     */
    public function ModifyGoodsBrandSort($brand_id, $sort)
    {
        $data = array(
             
        );
        $res = $this->goods_brand->save($data,['brand_id'=>$brand_id]);
        return $res;
        // TODO Auto-generated method stub
        
    }

	/* (non-PHPdoc)
     * @see \data\api\IGoodsBrand::ModifyGoodsBrandRecomend()
     */
    public function ModifyGoodsBrandRecomend($brand_id, $brand_recommend)
    {
        $data = array(
             
        );
        $res = $this->goods_brand->save($data,['brand_id'=>$brand_id]);
        return $res;
        // TODO Auto-generated method stub
        
    }

	/* (non-PHPdoc)
     * @see \data\api\IGoodsBrand::deleteGoodsBrand()
     */
    public function deleteGoodsBrand($brand_id_array)
    {
        $res = $this->goods_brand->destroy($brand_id_array);
        hook("goodsBrandDeleteSuccess", ['brand_id' => $brand_id_array]);
        return $res;
        // TODO Auto-generated method stub
        
    }
    
    /**
     * 获取系统模块
     * @param unknown $module_id
     */
    public function getGoodsBrandInfo($brand_id, $field='*'){
        $info = $this->goods_brand->getInfo(array('brand_id'=>$brand_id,'website_id'=>$this->website_id), $field);
        return $info;
    }

    //获取分类品牌
    public function get_category_brand($condition){

        $list = $this->goods_brand->getQuery($condition,'*','brand_id');
        return $list;
    }
    //通过品类id获取分类品牌
    public function getBrandListByAttrId($attr_id){
        if(!$attr_id){
            return [];
        }
        $goodsServer = new Goods();
        $goods_attribute = $goodsServer->getAttributeInfo(['attr_id' => $attr_id, 'website_id' => $this->website_id]);
        if(!$goods_attribute['brand_id_array']){
            return [];
        }
        $brand_condition['brand_id'] = array("in", $goods_attribute['brand_id_array']);
        $brand_list = $this->goods_brand->getQuery($brand_condition,'brand_id,brand_name','');
        return $brand_list;
    }
    
    /**
     * 修改商品品牌 单个字段
     * 
     * @param unknown $brand_id         
     */
    public function ModifyGoodsBrandField($brand_id, $field_name, $field_value)
    {
        $res = $this->goods_brand->ModifyTableField('brand_id', $brand_id, $field_name, $field_value);
        return $res;
    }
}