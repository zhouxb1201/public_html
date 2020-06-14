<?php
namespace data\service;

/**
 * 商品分类服务层
 */
use data\service\BaseService as BaseService;
use data\model\VslGoodsCategoryModel as VslGoodsCategoryModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsBrandModel;
use data\model\VslGoodsCategoryBlockModel;
use data\model\VslGoodsViewModel;


class GoodsCategory extends BaseService
{

    private $goods_category;

    function __construct()
    {
        parent::__construct();
        $this->goods_category = new VslGoodsCategoryModel();
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::getGoodsCategoryList()
     */
    public function getGoodsCategoryList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $list = $this->goods_category->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::getGoodsCategoryListByParentId()
     */
    public function getGoodsCategoryListByParentId($pid)
    {
       // $cache_categortList_by_partent = Cache::get("GoodsCategoryListByParentId".$pid);
       // $this->addCacheKeyTag("GoodsCategoryListByParentId", $pid);
        $cache_categortList_by_partent = '';
        if(empty($cache_categortList_by_partent))
        {
            $list = $this->getGoodsCategoryList(1, 0, 'pid=' . $pid .' and website_id=' .$this->website_id, 'pid,sort');
            if (! empty($list)) {
                for ($i = 0; $i < count($list['data']); $i ++) {
                    $parent_id = $list['data'][$i]["category_id"];
                    $child_list = $this->getGoodsCategoryList(1, 1, 'pid=' . $parent_id, 'pid,sort');
                    if (! empty($child_list) && $child_list['total_count'] > 0) {
                        $list['data'][$i]["is_parent"] = 1;
                    } else {
                        $list['data'][$i]["is_parent"] = 0;
                    }
                }
            }
        //    Cache::set("GoodsCategoryListByParentId".$pid, $list['data']);
            $cache_categortList_by_partent = $list['data'];
        }
        return $cache_categortList_by_partent;
        // TODO Auto-generated method stub
    }

    /**
     * 获取格式化后的商品分类
     */
    public function getFormatGoodsCategoryList()
    {
        $one_list = $this->getCategoryTreeUseInShopIndex();
       /*  $one_list = $this->getGoodsCategoryListByParentId(0);
        if (! empty($one_list)) {
            foreach ($one_list as $k => $v) {
                $two_list = array();
                $two_list = $this->getGoodsCategoryListByParentId($v['category_id']);
                $v['child_list'] = $two_list;
                if (! empty($two_list)) {
                    foreach ($two_list as $k1 => $v1) {
                        $three_list = array();
                        $three_list = $this->getGoodsCategoryListByParentId($v1['category_id']);
                        $v1['child_list'] = $three_list;
                    }
                }
            }
        } */
        return $one_list;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::addOrEditGoodsCategory()
     */
    public function addOrEditGoodsCategory($category_id, $category_name, $short_name, $pid, $is_visible, $keywords = '', $description = '', $sort = 0, $category_pic, $attr_id = 0, $attr_name = '')
    {
       // $this->clearKeyCache("CategoryTreeList");
       // $this->clearKeyCache("GoodsCategoryListByParentId");
        if ($pid == 0) {
            $level = 1;
        } else {
            $level = $this->getGoodsCategoryDetail($pid)['level'] + 1;
        }
        if($category_id == 0){
            $data = array(
                'website_id' => $this->website_id,
                'category_name' => $category_name,
                'short_name' => $short_name,
                'pid' => $pid,
                'level' => $level,
                'is_visible' => $is_visible,
                'keywords' => $keywords,
                'description' => $description,
                'sort' => $sort,
                'category_pic' => $category_pic,
                'attr_id' => $attr_id,
                'create_time'=>time(),
                'attr_name' => $attr_name
            );
        }else{
            $data = array(
                'website_id' => $this->website_id,
                'category_name' => $category_name,
                'short_name' => $short_name,
                'pid' => $pid,
                'level' => $level,
                'is_visible' => $is_visible,
                'keywords' => $keywords,
                'description' => $description,
                'sort' => $sort,
                'category_pic' => $category_pic,
                'attr_id' => $attr_id,
                'update_time'=>time(),
                'attr_name' => $attr_name
            );
        }
        if ($category_id == 0) {
            $result = $this->goods_category->save($data);
            if ($result) {
                // 创建商品分类楼层
                $this->addGoodsCategoryBlock($this->goods_category->category_id,$this->instance_id);
                $data['category_id'] = $this->goods_category->category_id;
                hook("goodsCategorySaveSuccess", $data);
                $res = $this->goods_category->category_id;
            } else {
                $res = $this->goods_category->getError();
            }
        } else {
            $res = $this->goods_category->save($data, [
                'category_id' => $category_id,
                'website_id' => $this->website_id,
            ]);
            if ($res !== false) {
                $this->addGoodsCategoryBlock($category_id,$this->instance_id);
                $this->goods_category->save([
                    "level" => $level + 1
                ], [
                    "pid" => $category_id
                ]);
                $data['category_id'] = $category_id;
                hook("goodsCategorySaveSuccess", $data);
                return $res;
            } else {
                $res = $this->goods_category->getError();
            }
        }
        return $res;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::deleteGoodsCategory()
     */
    public function deleteGoodsCategory($category_id)
    {
        $this->clearKeyCache("CategoryTreeList");
        $this->clearKeyCache("GoodsCategoryListByParentId");
        $sub_list = $this->getGoodsCategoryListByParentId($category_id);
        if (! empty($sub_list)) {
            $res = SYSTEM_DELETE_FAIL;
        } else {
            $res = $this->goods_category->destroy($category_id);
            // 删除分类商品楼层
            $this->deleteGoodsCategoryBlock($category_id);
        }
        return $res;
        // TODO Auto-generated method stub
    }
    /*
     * 修改商品分类排序
     * **/
    public function updateGoodsCategorySort($category_id, $sort_val)
    {
        $res['sort'] = $sort_val;
        $bool = $this->goods_category->where(['category_id'=>$category_id])->update($res);
        return $bool;
    }
    /*
     * 修改商品分类名称
     * **/
    public function updateGoodsCategoryName($category_id, $category_name)
    {
        $res['short_name'] = $category_name;
        $bool = $this->goods_category->where(['category_id'=>$category_id])->update($res);
        return $bool;
    }
    /*
     * 修改商品分类是否显示
     * **/
    public function updateGoodsCategoryShow($category_id, $is_visible)
    {
        $res['is_visible'] = $is_visible;
        $bool = $this->goods_category->where(['category_id'=>$category_id])->update($res);
        return $bool;
    }
    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::getTreeCategoryList()
     */
    public function getTreeCategoryList($show_deep, $condition)
    {
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::getKeyWords()
     */
    public function getKeyWords($category_id)
    {
        $res = $this->goods_category->getInfo([
            'category_id' => $category_id
        ], 'keywords');
        return $res;
        // TODO Auto-generated method stub
    }

    /**
     * (non-PHPdoc)
     * 
     * @see \data\api\IGoodsCategory::getLevel()
     */
    public function getLevel($category_id)
    {
        $res = $this->goods_category->getInfo([
            'category_id' => $category_id
        ], 'level');
        return $res;
        // TODO Auto-generated method stub
    }

    /**
     * (non-PHPdoc)
     * 
     * @see \data\api\IGoodsCategory::getName()
     */
    public function getName($category_id)
    {
        $res = $this->goods_category->getInfo([
            'category_id' => $category_id
        ], 'category_name');
        return $res;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::getGoodsCategoryDetail()
     */
    public function getGoodsCategoryDetail($category_id)
    {
        $res = $this->goods_category->get($category_id);
        return $res;
        // TODO Auto-generated method stub
    }

    public function getGoodsCategoryTree($pid)
    {
        // 暂时 获取 两级
        $list = array();
        $one_list = $this->getGoodsCategoryListByParentId($pid);
        foreach ($one_list as $k1 => $v1) {
            $two_list = array();
            $two_list = $this->getGoodsCategoryListByParentId($v1['category_id']);
            $one_list[$k1]['child_list'] = $two_list;
        }
        $list = $one_list;
        return $list;
    }

    /**
     * 修改商品分类 单个字段
     * 
     * @param unknown $category_id            
     * @param unknown $order            
     */
    public function ModifyGoodsCategoryField($category_id, $field_name, $field_value)
    {
       // $this->clearKeyCache("CategoryTreeList");
      //  $this->clearKeyCache("GoodsCategoryListByParentId");
        $res = $this->goods_category->ModifyTableField('category_id', $category_id, $field_name, $field_value);
    
        $this->addGoodsCategoryBlock($category_id);
        return $res;
    }

    /**
     * 获取商品分类下的商品品牌(non-PHPdoc)
     * 
     * @see \data\api\IGoodsCategory::getGoodsCategoryBrands()
     */
    public function getGoodsCategoryBrands($category_id)
    {
        $goods_model = new VslGoodsModel();
        $condition = array(
            'category_id | category_id_1 | category_id_2 | category_id_3' => $category_id,
            'website_id' => $this->website_id
        );
        $brand_id_array = $goods_model->getQuery($condition, 'brand_id', '');
        $array = array();
        if (! empty($brand_id_array)) {
            foreach ($brand_id_array as $k => $v) {
                $array[] = $v['brand_id'];
            }
        }
        if (! empty($array)) {
            $brand_str = implode(',', $array);
            $goods_brand = new VslGoodsBrandModel();
            $condition = array(
                'brand_id' => array(
                    'in',
                    $brand_str
                ),
                'brand_recommend' => 1
            );
            $brand_list = $goods_brand->getQuery($condition, '*', 'brand_initial asc');
            return $brand_list;
        } else {
            return '';
        }
    }

    /**
     * 获取商品分类下的价格区间(non-PHPdoc)
     * 
     * @see \data\api\IGoodsCategory::getGoodsCategoryPriceGrades()
     */
    public function getGoodsCategoryPriceGrades($category_id)
    {
        $goods_model = new VslGoodsModel();
        $max_price = $goods_model->where([
            'category_id' => $category_id,
            'website_id' => $this->website_id
        ])->max('price');
        $min_price = $goods_model->where([
            'category_id' => $category_id,
            'website_id' => $this->website_id
        ])->min('price');
        $price_grade = 1;
        for ($i = 1; $i <= log10($max_price); $i ++) {
            $price_grade *= 10;
        }
        // 跨度
        $dx = (ceil(log10(($max_price - $min_price) / 3)) - 1) * $price_grade;
        if ($dx <= 0) {
            $dx = $price_grade;
        }
        $array = array();
        $j = 0;
        while ($j <= $max_price) {
            $array[] = array(
                $j,
                $j + $dx - 1
            );
            $j = $j + $dx;
        }
        
        return $array;
    }


    /**
     * 获取商品分类的子项列
     * 
     * @param unknown $category_id            
     * @return string|unknown
     */
    public function getCategoryTreeList($category_id)
    {
      // $cache_category_tree_list = Cache::get("CategoryTreeList".$category_id);
     //  $this->addCacheKeyTag("CategoryTreeList", $category_id);
       $cache_category_tree_list = '';
       if(empty($cache_category_tree_list))
       {
           $goods_goods_category = new VslGoodsCategoryModel();
           $level = $goods_goods_category->getInfo([
               'category_id' => $category_id
           ], 'level,category_id');
           if (! empty($level)) {
               $category_list = array();
               if ($level['level'] == 1) {
                   $child_list = $goods_goods_category->getQuery([
                       'pid' => $category_id
                   ], 'category_id,pid', '');
                   $category_list = $child_list;
                   if (! empty($child_list)) {
                       foreach ($child_list as $k => $v) {
                           $grandchild_list = $goods_goods_category->getQuery([
                               'pid' => $v['category_id']
                           ], 'category_id', '');
                           if (! empty($grandchild_list)) {
                               $category_list = array_merge($category_list, $grandchild_list);
                           }
                       }
                   }
               } elseif ($level['level'] == 2) {
                   $child_list = $goods_goods_category->getQuery([
                       'pid' => $category_id
                   ], 'category_id,pid', '');
                   $category_list = $child_list;
               }
               
               $array = array();
               if (! empty($category_list)) {
           
                   foreach ($category_list as $k => $v) {
                       $array[] = $v['category_id'];
                   }
               }
               if (! empty($array)) {
                   $id_list = implode(',', $array);
                  
                   $cache_category_tree_list = $id_list . ',' . $category_id;
               } else {
                   $cache_category_tree_list = $level['category_id'];
               }
           } else {
               $cache_category_tree_list = $level['category_id'];
           }
         //  Cache::set("CategoryTreeList".$category_id, $cache_category_tree_list);
       }
       return $cache_category_tree_list;
      
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::getCategoryParentQuery()
     */
    public function getCategoryParentQuery($category_id)
    {
        // TODO Auto-generated method stub
        $parent_category_info = array();
        $grandparent_category_info = array();
        $category_name = "";
        $parent_category_name = "";
        $grandparent_category_name = "";
        $goods_goods_category = new VslGoodsCategoryModel();
        $category_info = $goods_goods_category->getInfo([
            "category_id" => $category_id
        ], "category_id,category_name,pid");
        $level = $category_info["level"];
        $nav_name = array();
        if (! empty($category_info)) {
            $category_name = $category_info["category_name"];
            if ($level == 3) {
                $parent_category_info = $goods_goods_category->getInfo([
                    "category_id" => $category_info["pid"]
                ], "category_id,category_name,pid");
                
                if (! empty($parent_category_info)) {
                    $grandparent_category_info = $goods_goods_category->getInfo([
                        "category_id" => $parent_category_info["pid"]
                    ], "category_id,category_name,pid");
                }
                $nav_name = array(
                    $grandparent_category_info,
                    $parent_category_info,
                    $category_info
                );
            } else 
                if ($level == 2) {
                    $parent_category_info = $goods_goods_category->getInfo([
                        "category_id" => $category_info["pid"]
                    ], "category_id,category_name,pid");
                    $nav_name = array(
                        $parent_category_info,
                        $category_info
                    );
                } else {
                    $nav_name = array(
                        $category_info
                    );
                }
        }
        return $nav_name;
    }

    /**
     * 得到上级的分类组合
     * 
     * @param unknown $category_id            
     */
    public function getParentCategory($category_id)
    {
        $category_ids = $category_id;
        $category_names = "";
        $pid = 0;
        $goods_category = new VslGoodsCategoryModel();
        $category_obj = $goods_category->get($category_id);
        if (! empty($category_obj)) {
            $category_names = $category_obj["category_name"];
            $pid = $category_obj["pid"];
            while ($pid != 0) {
                $goods_category = new VslGoodsCategoryModel();
                $category_obj = $goods_category->get($pid);
                if (! empty($category_obj)) {
                    $category_ids = $category_ids . "," . $pid;
                    $category_name = $category_obj["category_name"];
                    $category_names = $category_names . "," . $category_name;
                    $pid = $category_obj["pid"];
                } else {
                    $pid = 0;
                }
            }
        }
        $category_id_str = explode(",", $category_ids);
        $category_names_str = explode(",", $category_names);
        $category_result_ids = "";
        $category_result_names = "";
        for ($i = count($category_id_str); $i >= 0; $i --) {
            if ($category_result_ids == "") {
                $category_result_ids = $category_id_str[$i];
            } else {
                $category_result_ids = $category_result_ids . "," . $category_id_str[$i];
            }
        }
        for ($i = count($category_names_str); $i >= 0; $i --) {
            if ($category_result_names == "") {
                $category_result_names = $category_names_str[$i];
            } else {
                $category_result_names = $category_result_names . ":" . $category_names_str[$i];
            }
        }
        $parent_Category = array(
            "category_ids" => $category_result_ids,
            "category_names" => $category_result_names
        );
        
        return $parent_Category;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::getGoodsCategoryBlock()
     */
    public function getGoodsCategoryBlock($shop_id)
    {
        // TODO Auto-generated method stub
        $goods_category_block = new VslGoodsCategoryBlockModel();
        $goods_category_block_query = $goods_category_block->getQuery([
            "website_id" => $this->website_id,
            "shop_id" => $shop_id
        ], "*", "sort desc,create_time desc");
        return $goods_category_block_query;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::setGoodsCategoryBlock()
     */
    public function setGoodsCategoryBlock($id, $shop_id, $data)
    {
        // TODO Auto-generated method stub
        $goods_category_block = new VslGoodsCategoryBlockModel();
        $result = $goods_category_block->save($data, [
            "shop_id" => $shop_id,
            "id" => $id
        ]);
        return $result;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoods::addGoodsCategoryBlock()
     */
    public function addGoodsCategoryBlock($category_id, $shop_id = 0)
    {
        // TODO Auto-generated method stub
        $goods_category = new VslGoodsCategoryModel();
        $goods_category_info = $goods_category->getInfo([
            "category_id" => $category_id,
            "website_id" => $this->website_id
        ], "*");
        if (! empty($goods_category_info)) {
            
            $goods_category_block = new VslGoodsCategoryBlockModel();
            $goods_category_block_info = $goods_category_block->getInfo([
                "category_id" => $category_id,
                "website_id" => $this->website_id
            ], "*");
            if (empty($goods_category_block_info) && $goods_category_info["pid"] == 0) {
                $data = array(
                    "shop_id" => $shop_id,
                    "website_id" => $this->website_id,
                    "category_id" => $category_id,
                    "category_name" => $goods_category_info["category_name"],
                    "category_alias" => $goods_category_info["category_name"],
                    "create_time" => time(),
                    "color" => "#FFFFFF",
                    "short_name" => mb_substr($goods_category_info["category_name"], 0, 4, 'utf-8')
                );
                $result = $goods_category_block->save($data);
                return $result;
            } else {
                if ($goods_category_info["pid"] > 0) {
                    $this->deleteGoodsCategoryBlock($category_id);
                    return 1;
                } else {
                    $data = array(
                        "category_name" => $goods_category_info["category_name"],
                        "category_alias" => $goods_category_info["category_name"],
                        "modify_time" => time(),
                        "short_name" => mb_substr($goods_category_info["category_name"], 0, 4, 'utf-8')
                    );
                    $result = $goods_category_block->save($data, [
                        "category_id" => $category_id,
                        "website_id" => $this->website_id
                    ]);
                    return $result;
                }
            }
        } else {
            return 0;
        }
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::getGoodsCategoryBlockList()
     */
    public function getGoodsCategoryBlockList($shop_id)
    {
        // TODO Auto-generated method stub
        $goods_category_block = new VslGoodsCategoryBlockModel();
        $goods = new VslGoodsViewModel();
        $goods_brand = new VslGoodsBrandModel();
        $goods_category = new VslGoodsCategoryModel();
        $goods_category_block_list = $goods_category_block->getQuery([
            "shop_id" => $shop_id,
            "website_id"=>$this->website_id,
            "is_show" => 1
        ], "*", "sort desc");
        foreach ($goods_category_block_list as $k => $v) {
            $goods_list = $goods->getGoodsViewList(1, 10, [
                "ng.category_id_1" => $v["category_id"],
                "ng.website_id" => $this->website_id,
                "ng.state" => 1
            ], "sort desc");
            $goods_category_block_list[$k]["goods_list"] = $goods_list["data"];
            // 是否显示品牌
            if ($v["is_show_brand"] == 1) {
                $goods_brnd_list = $goods_brand->pageQuery(1, 8, [
                    "category_id_1" => $v["category_id"],
                    "website_id" => $this->website_id
                ], "sort desc", "*");
                $goods_category_block_list[$k]["brand_list"] = $goods_brnd_list["data"];
            }
            // 是否显示二级分类
            if ($v["is_show_lower_category"]) {
                $second_category_list = $goods_category->getQuery([
                    "pid" => $v["category_id"],
                    "website_id" => $this->website_id
                ], "*", "sort desc");
                if (! empty($second_category_list)) {
                    foreach ($second_category_list as $t => $m) {
                        $goods_list = $goods->getGoodsViewList(1, 10, [
                            "ng.category_id_2" => $m["category_id"],
                            "ng.website_id" => $this->website_id,
                            "ng.state" => 1
                        ], "sort asc");
                        $second_category_list[$t]["goods_list"] = $goods_list["data"];
                    }
                    $goods_category_block_list[$k]["child_category"] = $second_category_list;
                }
            }
        }
        return $goods_category_block_list;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::getGoodsCategoryBlockQuery()
     */
    public function getGoodsCategoryBlockQuery($shop_id, $show_num = 4)
    {
        // TODO Auto-generated method stub
        $goods_category_block = new VslGoodsCategoryBlockModel();
        $goods = new VslGoodsViewModel();
        $goods_brand = new VslGoodsBrandModel();
        $goods_category = new VslGoodsCategoryModel();
        $goods_category_block_list = $goods_category_block->getQuery([
            "shop_id" => $shop_id,
            "is_show" => 1
        ], "*", "sort desc");
        foreach ($goods_category_block_list as $k => $v) {
            $goods_list = $goods->getGoodsViewList(1, $show_num, [
                "ng.category_id_1" => $v["category_id"],
                "ng.state" => 1
            ], "sort desc");
            $goods_category_block_list[$k]["goods_list"] = $goods_list["data"];
        }
        return $goods_category_block_list;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IGoodsCategory::deletetGoodsCategoryBlock()
     */
    public function deleteGoodsCategoryBlock($category_id)
    {
        // TODO Auto-generated method stub
        $goods_category_block = new VslGoodsCategoryBlockModel();
        $retval = $goods_category_block->destroy([
            "category_id" => $category_id,
            'website_id' => $this->website_id
        ]);
        return $retval;
    }

    /**
     * 品牌列表
     *
     * @param int $page_index            
     * @param int $page_size            
     * @param string $condition            
     * @param string $order            
     * @return array
     */
    public function getGoodsBrandList($page_index = 1, $page_size = 0, $condition = '', $order = "sort asc", $field = '*')
    {
        $goods_brand = new VslGoodsBrandModel();
        $goods_brand_list = $goods_brand->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $goods_brand_list;
    }
    /**
     * 获取商品分类列表应用在店铺端首页
     */
    public function getCategoryTreeUseInShopIndex(){
        $goods_category_model = new VslGoodsCategoryModel();
        
        $goods_category_one = $goods_category_model->getQuery([
            'level' => 1,
            'is_visible' => 1,
            'website_id' => $this->website_id
        ], 'category_id, category_name,short_name,pid,category_pic,sort,attr_name', 'sort');
        if(!empty($goods_category_one))
        {
            foreach ($goods_category_one as $k_cat_one => $v_cat_one)
            {
                $goods_category_two_list = $goods_category_model->getQuery([
            'level' => 2,
            'is_visible' => 1,
            'pid'        => $v_cat_one['category_id']
              ], 'category_id,category_name,short_name,pid,category_pic,sort,attr_name', 'sort');
               $v_cat_one['count'] = count($goods_category_two_list);
               if(!empty($goods_category_two_list))
               {
                   foreach ($goods_category_two_list as $k_cat_two => $v_cat_two )
                   {
                       $cat_three_list = $goods_category_model->getQuery(['level' => 3,
                        'is_visible' => 1,
                        'pid'        => $v_cat_two['category_id']],'category_id,category_name,short_name,pid,category_pic,sort,attr_name', 'sort');
                       
                       $v_cat_two['count'] = count($cat_three_list);
                       $v_cat_two['child_list'] = $cat_three_list;
                   }
                   
               }
               $v_cat_one['child_list'] = $goods_category_two_list;
               
            }
        }
        return $goods_category_one;
        
    }
    /**
     * 获取商品二级分类
     */
    public function getGoodsSecondCategoryTree()
    {
        $goods_category_model = new VslGoodsCategoryModel();
        
        $goods_category_two_list = $goods_category_model->getQuery([
            'level' => 2,
            'is_visible' => 1
        ], 'category_id, category_name,short_name,pid,category_pic', 'sort');
        if(!empty($goods_category_two_list))
        {
            foreach ($goods_category_two_list as $k_cat_two => $v_cat_two )
            {
                $cat_three_list = $goods_category_model->getQuery(['level' => 3,
                    'is_visible' => 1,
                    'pid'        => $v_cat_two['category_id']],'category_id,category_name,short_name,pid,category_pic', 'sort');
                 
                $v_cat_two['count'] = count($cat_three_list);
                $v_cat_two['child_list'] = $cat_three_list;
            }
             
        }
        return $goods_category_two_list;
        
        
    }
    
    /**
     * 获取商品分类列表应用后台
     */
    public function getCategoryTreeUseInAdmin(){
        $goods_category_model = new VslGoodsCategoryModel();
    
        $goods_category_one = $goods_category_model->getQuery([
            'level' => 1,
            'website_id' => $this->website_id,
            'is_visible' => 1,
        ], 'category_id, category_name,short_name,pid,category_pic,sort,attr_name,is_visible,attr_id', 'sort');
        if(!empty($goods_category_one))
        {
            foreach ($goods_category_one as $k_cat_one => $v_cat_one)
            {
                $goods_category_two_list = $goods_category_model->getQuery([
                    'level' => 2,
                    'pid'        => $v_cat_one['category_id']
                ], 'category_id,category_name,short_name,pid,category_pic,sort,attr_name,is_visible,attr_id', 'sort');
                $v_cat_one['count'] = count($goods_category_two_list);
                if(!empty($goods_category_two_list))
                {
                    foreach ($goods_category_two_list as $k_cat_two => $v_cat_two )
                    {
                        $cat_three_list = $goods_category_model->getQuery(['level' => 3,
                            'pid'        => $v_cat_two['category_id']],'category_id,category_name,short_name,pid,category_pic,sort,attr_name,is_visible,attr_id', 'sort');
                         
                        $v_cat_two['count'] = count($cat_three_list);
                        $v_cat_two['child_list'] = $cat_three_list;
                    }
                     
                }
                $v_cat_one['child_list'] = $goods_category_two_list;
                 
            }
        }
        return $goods_category_one;
    
    }
    /*
     * 获取分类名称（包括上级名称）
     */
    public function getCategoryNameLine($category_id = 0){
        if(!$category_id){
            return '';
        }
        $categoryName = '';
        $categoryModel = new VslGoodsCategoryModel();
        $category = $categoryModel->getInfo(['category_id' => $category_id, 'website_id' => $this->website_id],'pid');
        if(!$category){
            return '';
        }
        $goodsService = new Goods();
        if(!$category['pid']){
            $categoryName = $goodsService->getGoodsCategoryName($category_id, 0, 0);
            return $categoryName;
        }
        $parentCategory = $categoryModel->getInfo(['category_id' => $category['pid'], 'website_id' => $this->website_id],'pid');
        if(!$parentCategory['pid']){
            $categoryName = $goodsService->getGoodsCategoryName($category['pid'], $category_id, 0);
        }else{
            $categoryName = $goodsService->getGoodsCategoryName($parentCategory['pid'], $category['pid'], $category_id);
        }
         return $categoryName;
    }
}

?>