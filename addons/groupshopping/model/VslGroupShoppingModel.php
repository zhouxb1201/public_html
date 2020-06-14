<?php

namespace addons\groupshopping\model;

use data\model\BaseModel;
use addons\groupshopping\model\VslGroupGoodsModel;
use data\model\VslGoodsModel;
use data\model\VslOrderModel;

class VslGroupShoppingModel extends BaseModel
{
    protected $table = 'vsl_group_shopping';
    
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getGroupViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getGroupViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getGroupViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getGroupViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('ng')
        ->join('vsl_goods vg','ng.goods_id = vg.goods_id','left')
        ->join('vsl_group_shopping_record vgsr','ng.group_id = vgsr.group_id','left')
        ->join('sys_album_picture sap','vg.picture = sap.pic_id', 'left')
        ->join('vsl_goods_discount vgd', 'vgd.goods_id = vg.goods_id', 'LEFT')
        ->field('ng.group_id,ng.status,ng.group_name,ng.group_time,vg.goods_id,vg.goods_name,vg.price,sap.pic_cover_mid,sap.pic_cover')
        ->group('ng.group_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if(!empty($list))
        {
            $groupSku = new VslGroupGoodsModel();
            foreach ($list as $k=>$v)
            {
                $list[$k]['sku_price'] = $groupSku->where(['goods_id'=>$v['goods_id'],'group_id' => $v['group_id']])->field('min(group_price) as min_price,max(group_price) as max_price')->find();
            }
        }
        return $list;
    }
    /*
     * 获取数量
     */
    public function getGroupViewCount($condition)
    {
        $viewObj = $this->alias('ng')
        ->join('vsl_goods vg','ng.goods_id = vg.goods_id','left')
        ->join('vsl_goods_discount vgd', 'vgd.goods_id = vg.goods_id', 'LEFT');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    /*
     * 获取拼团详情
     */
    public function getGroupDetail($group_id){
        $groupDetail = $this->getInfo(['group_id'=>$group_id],'goods_id,group_name,group_num,group_time,status');
        $goodsModel = new VslGoodsModel();
        $groupDetail['goods'] = $goodsModel->alias('vg')->join('sys_album_picture sap','vg.picture = sap.pic_id', 'left')->where(['vg.goods_id'=>$groupDetail['goods_id']])->field('vg.goods_name,vg.price,sap.pic_cover_mid')->find();
        $groupSku = new VslGroupGoodsModel();
        $groupPrice = $groupSku->where(['goods_id'=>$groupDetail['goods_id']])->field('min(group_price) as min_price,max(group_price) as max_price')->find();//取最大值和最小值
        $groupDetail['goods']['min_price'] = $groupPrice['min_price'];
        $groupDetail['goods']['max_price'] = $groupPrice['max_price'];
        return $groupDetail;
    }
     /*
     * 判断商品是否是拼团商品
     * **/
    public function isGroupGoods($goods_id)
    {
        $isGroup = $this->getInfo(['goods_id'=>$goods_id, 'status' => ['<',2]],'goods_id,group_id');
        if(!$isGroup){
            return 0;
        }
        return $isGroup['group_id'];
    }
}