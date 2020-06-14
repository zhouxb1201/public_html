<?php

namespace addons\giftvoucher\model;

use data\model\BaseModel as BaseModel;
use think\Db;
/**
 * 礼品券记录表
 * @author  www.vslai.com
 *
 */
class VslGiftVoucherRecordsModel extends BaseModel
{

    protected $table = 'vsl_gift_voucher_records';

    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getVoucherHistory($page_index, $page_size, $where, $fields, $order)
    {
        $queryList = $this->getVoucherHistoryQuery($page_index, $page_size, $where, $fields, $order);
        $queryCount = $this->getVoucherHistoryCount($where);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getVoucherHistoryQuery($page_index, $page_size, $where, $fields, $order)
    {
        $viewObj = $this->alias('vgvr')
        ->join('vsl_shop vs', 'vs.shop_id = vgvr.shop_id and vs.website_id = vgvr.website_id', 'left')
        ->join('sys_user su', 'su.uid = vgvr.uid', 'left')
        ->join('vsl_gift_voucher vgv', 'vgv.gift_voucher_id = vgvr.gift_voucher_id', 'left')
        ->join('vsl_promotion_gift vpg','vpg.promotion_gift_id = vgv.promotion_gift_id','left')
        ->join('sys_album_picture sap','sap.pic_id = vpg.picture', 'left')
        ->field($fields);
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $where, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getVoucherHistoryCount($where)
    {
        $viewObj = $this->alias('vgvr')
        ->join('vsl_shop vs', 'vs.shop_id = vgvr.shop_id and vs.website_id = vgvr.website_id', 'left')
        ->join('sys_user su', 'su.uid = vgvr.uid', 'left')
        ->join('vsl_gift_voucher vgv', 'vgv.gift_voucher_id = vgvr.gift_voucher_id', 'left');
        $count = $this->viewCount($viewObj,$where);
        return $count;
    }
    /*
     * 获取详情
     */
    public function getVoucherHistoryDetail($condition){
        $detail = $this->getInfo($condition,'');
        if($detail){
            $info = Db::table('vsl_gift_voucher')->alias('vgv')
            ->join('vsl_promotion_gift vpg','vpg.promotion_gift_id = vgv.promotion_gift_id', 'left')
            ->join('sys_album_picture sap','vpg.picture = sap.pic_id', 'left')
            ->where(['vgv.gift_voucher_id'=>$detail['gift_voucher_id']])
            ->field('vgv.start_time,vgv.end_time,vgv.desc,vgv.giftvoucher_name,vpg.promotion_gift_id,vpg.gift_name,vpg.price, sap.pic_cover_mid,sap.pic_cover_big')
            ->group('vpg.promotion_gift_id')->find();
            $detail['info'] = $info;
        }
        return $detail;
    }
}