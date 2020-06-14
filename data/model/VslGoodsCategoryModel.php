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
 * 商品分类表
 *    category_id int(11) NOT NULL AUTO_INCREMENT,
      category_name varchar(50) NOT NULL DEFAULT '',
      pid int(11) NOT NULL DEFAULT 0,
      level tinyint(4) NOT NULL DEFAULT 0,
      is_visible bit(1) NOT NULL DEFAULT b'1',
      keywords varchar(255) NOT NULL DEFAULT '',
      description varchar(255) NOT NULL DEFAULT '',
      sort tinyint(4) NOT NULL DEFAULT 0,
      PRIMARY KEY (category_id)
 * @author  www.vslai.com
 *
 */
class VslGoodsCategoryModel extends BaseModel {

    protected $table = 'vsl_goods_category';
    protected $rule = [
        'category_id'  =>  '',
    ];
    protected $msg = [
        'category_id'  =>  '',
    ];
}