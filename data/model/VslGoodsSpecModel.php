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
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商品属性规格表
 * @author  www.vslai.com
 *
 */
class VslGoodsSpecModel extends BaseModel {

    protected $table = 'vsl_goods_spec';
    protected $rule = [
        'spec_id'  =>  '',
    ];
    protected $msg = [
        'spec_id'  =>  '',
    ];

    public function goods_spec_value()
    {
        return $this->hasMany('VslGoodsSpecValueModel','spec_id','spec_id');
    }

}