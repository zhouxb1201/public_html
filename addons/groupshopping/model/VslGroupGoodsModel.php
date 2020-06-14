<?php
/**
 * ModuleModel.php
 *
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace addons\groupshopping\model;

use data\model\BaseModel as BaseModel;
/**
 * 拼团商品表
 * @author Administrator
 *
 */
class VslGroupGoodsModel extends BaseModel {

    protected $table = 'vsl_group_shopping_goods';
    /*
     * 获取拼团sku的价格
     * **/
    public function getGroupSkuInfo($condition)
    {
        $group_sku_info = $this->field('group_price, group_limit_buy')->where($condition)->find();
        return $group_sku_info;
    }

}
