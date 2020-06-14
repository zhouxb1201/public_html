<?php

namespace addons\giftvoucher\model;

use data\model\BaseModel as BaseModel;
use think\Db;

/**
 * 礼品券活动表
 * @author  www.vslai.com
 *
 */
class VslGiftVoucherModel extends BaseModel
{

    protected $table = 'vsl_gift_voucher';
    
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getVoucherViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getVoucherViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getVoucherViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getVoucherViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('gv')
        ->join('vsl_promotion_gift pg','pg.promotion_gift_id = gv.promotion_gift_id','left')
        ->field('gv.*,pg.gift_name,pg.price')
        ->group('gv.gift_voucher_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getVoucherViewCount($condition)
    {
        $viewObj = $this->alias('gv')
        ->join('vsl_promotion_gift pg','pg.promotion_gift_id = gv.promotion_gift_id','left');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    /*
     * 获取礼品券详情
     */
    public function getVoucherDetail($condition){
        $voucherDetail = $this->getInfo($condition,'');
        if($voucherDetail){
            $gift = Db::table('vsl_promotion_gift')->alias('vpg')
            ->join('vsl_member_gift vmg','vpg.promotion_gift_id = vmg.promotion_gift_id','left')
            ->join('sys_album_picture sap','vpg.picture = sap.pic_id', 'left')
            ->where(['vpg.promotion_gift_id'=>$voucherDetail['promotion_gift_id']])
            ->field('vpg.promotion_gift_id,vpg.gift_name,vpg.price,vpg.stock,sum(vmg.num) as sended, sap.pic_cover_mid,sap.pic_cover_big')
            ->group('vpg.promotion_gift_id')->find();
            $gift['sended'] = intval($gift['sended']);
            $gift['pic_cover_big'] = __IMG($gift['pic_cover_big']);
            $gift['pic_cover_mid'] = __IMG($gift['pic_cover_mid']);
            $voucherDetail['gift'] = $gift;
        }
        return $voucherDetail;
    }
}