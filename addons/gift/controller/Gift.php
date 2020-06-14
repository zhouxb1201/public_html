<?php

namespace addons\gift\controller;

use addons\gift\Gift as baseGift;
use addons\gift\server\Gift as giftServer;
use data\service\Goods;
use data\model\VslGoodsModel;
use data\model\AlbumPictureModel;

/**
 * Class Customform
 * @package addons\customform\controller
 */
class Gift extends baseGift {
    public function __construct() {
        parent::__construct();
    }

    public function giftList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $search_text = request()->post("search_text", '');
        $giftServer = new giftServer();
        $condition = array(
            'vpg.website_id' => $this->website_id,
            'vpg.shop_id' => $this->instance_id,
            'vpg.gift_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        $list = $giftServer->giftList($page_index, $page_size, $condition);
        return $list;
    }

    /**
     * 添加或修改赠品
     * @return \multitype|void
     */
    public function addOrUpdateGift() {
        $data['gift_name'] = request()->post('gift_name', '');
        $data['gift_id'] = request()->post('gift_id', 0);
        $data['description'] = request()->post('description', '');
        $data['price'] = request()->post('price', '');
        $data['stock'] = request()->post('stock', 0);
        $data['imageArray'] = request()->post('imageArray/a', array());
        $img = '';
        if($data['imageArray']){
            foreach ($data['imageArray'] as $k=>$v){
                $img .= $v.',';
            }
        }
        $data['img_id_array'] = substr($img,0,-1);
        $data['picture'] = $data['imageArray'][0];
        $gift = new giftServer();
        if($data['gift_id']){
            $result = $gift->updateGift($data);
            if ($result > 0) {
                $this->addUserLog('修改赠品', $result);
            }
        }else{
            $result = $gift->addGift($data);
            if ($result > 0) {
                $this->addUserLog('添加赠品', $result);
            }
        }
        return AjaxReturn($result);
    }

    /**
     * 修改赠品
     */
    public function updateGift() {
        $data['title'] = request()->post('title', '');
        $data['gift_id'] = request()->post('gift_id', '');
        $data['cate_id'] = request()->post('cate_id', 0);
        $data['content'] = request()->post('content', '');
        $data['sort'] = request()->post('sort', 0);
        $data['status'] = request()->post('status', 0);
        $article = new giftServer();
        $result = $article->updateGift($data);
        if ($result > 0) {
            $this->addUserLog('修改赠品', $result);
        }
        return AjaxReturn($result);
    }
    
    /*
     * 删除赠品
     */
    public function deleteGift(){
        $giftId = request()->post("gift_id",0);
        if(!$giftId){
            return AjaxReturn(0);
        }
        $giftServer = new giftServer();
        $retval = $giftServer->deleteGift($giftId);
        if($retval <= 0){
            return AjaxReturn($retval);
        }
        $this->addUserLog('删除赠品', $retval);
        return AjaxReturn(1);
    }
    
    /*
     * 赠品设置
     */
    public function giftSetting()
    {
        $giftServer = new giftServer();
        $is_gift = request()->post('is_gift',0);

        $result = $giftServer->saveGiftConfig($is_gift);
        if($result){
            $this->addUserLog('添加赠品设置', $result);
        }
        setAddons('gift', $this->website_id, $this->instance_id);
        return AjaxReturn($result);

    }
    /**
     * 赠品商品选择
     */
    public function modalGiftGoodsList()
    {
        if (request()->post('page_index')) {
            $index = request()->post('page_index', 1);
            $search_text = request()->post('search_text');
            if ($search_text) {
                $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            $condition['goods_type'] = ['<>',4];
            $goods = new VslGoodsModel();
            $list = $goods->pageQuery($index, PAGESIZE, $condition,'create_time desc','goods_id,goods_name,description,price,img_id_array,picture');
            if( !empty($list['data']) ){
                foreach($list['data'] as $k => $v){
                    $goods_list[$k]['goods_id'] = $v['goods_id'];
                    $goods_list[$k]['goods_name'] = $v['goods_name'];
                    $goods_list[$k]['description'] = $v['description'];
                    $goods_list[$k]['price'] = $v['price'];
                    
                    // 查询图片表
                    $goods_img = new AlbumPictureModel();
                    $order = "instr('," . $v['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
                    $goods_img_list = $goods_img->getQuery([
                        'pic_id' => [
                            "in",
                            $v['img_id_array']
                        ]
                    ], '*', $order);
                    if (trim($v['img_id_array']) != "") {
                        $img_temp_array = array();
                        $img_array = explode(",", $v['img_id_array']);
                        foreach ($img_array as $ki => $vi) {
                            if (!empty($goods_img_list)) {
                                foreach ($goods_img_list as $t => $m) {
                                    if ($m["pic_id"] == $vi) {
                                        $img_temp_array[] = $m;
                                    }
                                }
                            }
                        }
                    }
                    if($img_temp_array){
                        foreach($img_temp_array as $kk => $vv){
                            $img_temp_array[$kk]['pic_cover'] = __IMG($vv['pic_cover']);
                        }
                    }
                    $goods_list[$k]["img_temp_array"] = $img_temp_array;
                    $goods_list[$k]['pic_cover'] = __IMG($img_temp_array[0]['pic_cover']);
                }
            }else{
                $goods_list = [];
            }
            if( !empty($goods_list) ){
                $list['data'] = $goods_list;
            }else{
                $list['data'] = '';
            }
            return $list;
        }
        $this->fetch('template/' . $this->module . '/giftGoodsDialog');
    }
    
    /*
     * 赠品记录
     */
    public function giftRecord() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $gift_id = request()->post("gift_id", 0);
        $giftServer = new giftServer();
        $condition = array(
            'vmg.website_id' => $this->website_id,
//            'vmg.shop_id' => $this->instance_id,
            'vmg.promotion_gift_id' => $gift_id,
        );
        $list = $giftServer->giftRecord($page_index, $page_size, $condition);
        return $list;
    }
    /**
     * 移动端赠品详情
     */
    public function giftDetail(){
        $giftId = (int)request()->post('gift_id',0);
        if(!$giftId){
            return json(['code' => 0, 'message' => '获取失败']);
        }
        $giftServer = new giftServer();
        $giftDetail = $giftServer->giftDetail($giftId);
        //重新组装数据
        $gift = [
            'gift_name' =>$giftDetail['gift_name'],
            'price' =>$giftDetail['price'],
            'description' =>$giftDetail['description'],
            'img_list' =>$giftDetail['img_list'],
        ];
        return json(['code' => 1, 'message' => '获取成功', 'data' => $gift]);
    }
}
