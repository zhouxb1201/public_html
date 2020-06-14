<?php
namespace addons\shop\model;

use data\model\BaseModel as BaseModel;
/**
 * 店铺表
 * @author  www.vslai.com
 *
 */
class VslShopWithdrawModel extends BaseModel {

    protected $table = 'vsl_shop_withdraw';

    public function getViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_shop sp','nmar.shop_id = sp.shop_id','left')
            ->field('nmar.*,sp.shop_name ,sp.shop_logo');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nmar')
            ->join('vsl_shop sp','nmar.shop_id = sp.shop_id','left')
            ->field('nmar.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

}