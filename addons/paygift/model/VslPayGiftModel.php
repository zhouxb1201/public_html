<?php

namespace addons\paygift\model;

use data\model\BaseModel as BaseModel;
use data\model\VslGoodsModel;
use data\model\AlbumPictureModel;

/**
 * 支付有礼活动表
 * @author  www.vslai.com
 *
 */
class VslPayGiftModel extends BaseModel
{

    protected $table = 'vsl_pay_gift';
    
    /*
     * 获取列表
     */
    public function getPaygiftViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getPaygiftViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getPaygiftViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getPaygiftViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->field('pay_gift_id,paygift_name,start_time,end_time,state,modes,modes_id,modes_money');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取各状态数量
     */
    public function getPaygiftNum($condition)
    {
        unset($condition['state']);
        $wholeCount = $this->getPaygiftViewCount($condition);
        $condition['state'] = 1;
        $stayCount = $this->getPaygiftViewCount($condition);
        $condition['state'] = 2;
        $startCount = $this->getPaygiftViewCount($condition);
        $condition['state'] = 3;
        $endCount = $this->getPaygiftViewCount($condition);
        $count['whole'] = $wholeCount;
        $count['stay'] = $stayCount;
        $count['start'] = $startCount;
        $count['end'] = $endCount;
        return $count;
    }
    /*
     * 获取数量
     */
    public function getPaygiftViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }
    /*
     * 获取支付有礼详情
     */
    public function getPaygiftDetail($condition){
        $detail = $this->getInfo($condition,'');
        $detail['modes_goods'] = [];
        if($detail['modes']==2){
            $goods = [];
            $vsl_goods = new VslGoodsModel();
            $vslgoods = $vsl_goods->getInfo(['goods_id'=>$detail['modes_id']],'goods_id,goods_name,price,img_id_array,picture');
            $goods["goods_id"] = $vslgoods['goods_id'];
            $goods["goods_name"] = $vslgoods['goods_name'];
            $goods["price"] = $vslgoods['price'];
            // 查询图片表
            $goods_img = new AlbumPictureModel();
            $order = "instr('," . $vslgoods['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
            $goods_img_list = $goods_img->getQuery([
                'pic_id' => [
                    "in",
                    $vslgoods['img_id_array']
                ]
            ], '*', $order);
            if (trim($vslgoods['img_id_array']) != "") {
                $img_temp_array = [];
                $img_array = explode(",", $vslgoods['img_id_array']);
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
            $goods['pic_cover'] = __IMG($img_temp_array[0]['pic_cover']);
            $detail['modes_goods'] = $goods;
        }
        return $detail;
    }
}