<?php
/**
 * VslCmsArticleClassModel.php
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

namespace data\model;

use data\model\BaseModel as BaseModel;
use think\Validate;
/**
 cms文章分类表
 *  class_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类编号 ',
 *             name varchar(50) NOT NULL COMMENT '分类名称',
 *             sort tinyint(1) UNSIGNED NOT NULL DEFAULT 255 COMMENT '排序',
 */

class VslCmsArticleClassModel extends BaseModel{
    protected $table = 'vsl_cms_article_class';
    protected $rule = [
        'class_id'  =>  '',
//         'name'  =>  'require',
        
    ];
    protected $msg = [
        'class_id'  =>  '',
//         'name'  =>  '文章分类不能为空',
    ];
}