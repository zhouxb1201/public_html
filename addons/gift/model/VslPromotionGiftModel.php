<?php
/**
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace addons\gift\model;

use data\model\BaseModel as BaseModel;
/**
 * 赠品活动表
 *  gift_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '赠品活动id ',
      start_time datetime NOT NULL COMMENT '赠品有效期开始时间',
      days int(10) UNSIGNED NOT NULL COMMENT '领取有效期(多少天)',
      end_time datetime NOT NULL COMMENT '赠品有效期结束时间',
      max_num varchar(50) NOT NULL COMMENT '领取限制(次/人 (0表示不限领取次数))',
      shop_id varchar(100) NOT NULL COMMENT '店铺id',
      shop_name varchar(255) NOT NULL COMMENT '店铺名称',
      create_time tinyint(3) UNSIGNED NOT NULL COMMENT '创建时间',
 */
class VslPromotionGiftModel extends BaseModel {

    protected $table = 'vsl_promotion_gift';
    protected $rule = [
        'promotion_gift_id'  =>  '',
        'description'  =>  'no_html_parse',
    ];
    protected $msg = [
        'promotion_gift_id'  =>  '',
        'description'  =>  'no_html_parse',
    ];
    
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getGiftViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getGiftViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getGiftViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getGiftViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('vpg')
        ->join('vsl_member_gift vmg','vpg.promotion_gift_id = vmg.promotion_gift_id','left')
        ->join('sys_album_picture sap','vpg.picture = sap.pic_id', 'left')
        ->field('vpg.promotion_gift_id,vpg.gift_name,vpg.price,vpg.stock,sum(vmg.num) as sended, sap.pic_cover_mid,sap.pic_cover_big')
        ->group('vpg.promotion_gift_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if($list){
            foreach($list as $key => $val){
                $list[$key]['sended'] = intval($val['sended']);
                $list[$key]['pic_cover_big'] = __IMG($val['pic_cover_big']);
                $list[$key]['pic_cover_mid'] = __IMG($val['pic_cover_mid']);
            }
        }
        return $list;
    }
    /*
     * 获取数量
     */
    public function getGiftViewCount($condition)
    {
        $viewObj = $this->alias('vpg');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

}