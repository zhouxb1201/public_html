<?php
namespace app\platform\controller;
use data\service\Album as Album;
use data\service\GoodsCategory as GoodsCategory;
use think\Controller;
use think\Session;

class Select extends Controller {
    /**
     * 图片选择
     */
    public function dialogAlbumList()
    {
        $website_id = request()->get('website_id', '');
        $number = request()->get('number', 1);
        $spec_id = request()->get('spec_id', 0);
        $spec_value_id = request()->get('spec_value_id', 0);
        $upload_type = request()->get('upload_type', 1);
        $this->assign("number", $number);
        $this->assign("spec_id", $spec_id);
        $this->assign("spec_value_id", $spec_value_id);
        $this->assign("upload_type", $upload_type);
        $this->assign("website_id",$website_id);
        $album = new Album();
        $default_album_detail = $album -> getDefaultAlbumDetail();
        $this->assign('default_album_id', $default_album_detail['album_id']);
        return view("platform/System/dialogAlbumListA");
    }
    /**
     * 商品分类选择
     *
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function dialogSelectCategory()
    {
        $category_id = request()->get("category_id", 0);
        $goodsid = request()->get("goodsid", 0);
        $flag = request()->get("flag", 'category');
        $this->assign("flag", $flag);
        $this->assign("goodsid", $goodsid);
        $goods_category = new GoodsCategory();
        $list = $goods_category->getGoodsCategoryListByParentId(0);
        $this->assign("cateGoryList", $list);
        $category_select_ids = "";
        $category_select_names = "";
        if ($category_id != 0) {
            $category_select_result = $goods_category->getParentCategory($category_id);
            $category_select_ids = $category_select_result["category_ids"];
            $category_select_names = $category_select_result["category_names"];
        }
        $this->assign("category_select_ids", $category_select_ids);
        $this->assign("category_select_names", $category_select_names);
        return view('platform/Goods/dialogSelectCategory');
    }
    /**
     * 商品规格dialog插件
     */
    public function controlDialogSku()
    {
        $attr_id = request()->get("attr_id", 0);
        $this->assign("attr_id", $attr_id);
        return view('platform/Goods/controlDialogSku');
    }
}