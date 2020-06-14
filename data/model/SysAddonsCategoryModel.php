<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 插件表
 */
class SysAddonsCategoryModel extends BaseModel {
    
    protected $table = 'sys_addons_category';
    protected $rule = [
        'category_id'  =>  '',
    ];
    protected $msg = [
        'category_id'  =>  '',
    ];
}