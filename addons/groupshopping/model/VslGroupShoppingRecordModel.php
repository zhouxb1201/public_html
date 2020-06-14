<?php

namespace addons\groupshopping\model;

use data\model\BaseModel;
use addons\groupshopping\model\VslGroupGoodsModel;
use data\model\VslGoodsModel;

class VslGroupShoppingRecordModel extends BaseModel {

    protected $table = 'vsl_group_shopping_record';

    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getGroupRecordViewList($page_index, $page_size, $condition, $order) {
        $queryList = $this->getGroupRecordViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getGroupRecordViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    /*
     * 获取数据
     */

    public function getGroupRecordViewQuery($page_index, $page_size, $condition, $order) {
        $viewObj = $this->alias('ng')
                ->join('sys_user su', 'ng.uid = su.uid', 'left')
                ->join('vsl_member vm', 'su.uid = vm.uid', 'left')
                ->join('vsl_member_level vml', 'vm.member_level = vml.level_id', 'left')
                ->field('ng.record_id,ng.record_no,ng.status,ng.group_time,ng.group_num,ng.now_num,ng.create_time,ng.order_status,su.user_headimg,su.user_name,su.nick_name,vml.level_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
            }
        }
        return $list;
    }

    /*
     * 获取数量
     */

    public function getGroupRecordViewCount($condition) {
        $viewObj = $this->alias('ng')
                ->join('sys_user su', 'ng.uid = su.uid', 'left')
                ->join('vsl_member vm', 'su.uid = vm.uid', 'left')
                ->join('vsl_member_level vml', 'vm.member_level = vml.level_id', 'left')
                ->field('ng.record_id');
        $count = $this->viewCount($viewObj, $condition);
        return $count;
    }

    /*
     * 获取拼团详情
     */

    public function getRecordDetail($record_id) {
        if(!$record_id){
            return array('status'=>-1);
        }
        $recordDetail = $this->getInfo(['record_id' => $record_id], 'goods_id,record_no,group_num,status,uid,finish_time');
        $goodsModel = new VslGoodsModel();
        $recordDetail['goods'] = $goodsModel->alias('vg')->join('sys_album_picture sap', 'vg.picture = sap.pic_id', 'left')->where(['vg.goods_id' => $recordDetail['goods_id']])->field('vg.goods_name,sap.pic_cover_mid')->find();
        return $recordDetail;
    }

    /*
     * 获取已支付未成团订单
     */

    public function getPayedUnGroupOrder($shop_id = 0,$website_id = 0) {
        $orderList = $this->alias('ng')
        ->join('vsl_order vo', 'ng.record_id = vo.group_record_id', 'left')
        ->where(['vo.group_record_id' => ['>', 0], 'ng.status' => 0, 'vo.order_status' => 1, 'vo.shop_id' => $shop_id, 'vo.website_id' => $website_id])
        ->field('vo.order_id')->select();
        if(!$orderList){
            return array();
        }
        $orderIdList = array();
        foreach($orderList as $key => $val){
            $orderIdList[] = $val['order_id'];
        }
        return $orderIdList;
    }

}
