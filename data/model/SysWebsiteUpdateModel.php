<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 插件表
 */
class SysWebsiteUpdateModel extends BaseModel {
    
    protected $table = 'sys_website_update';
    protected $rule = [
        'update_id'  =>  '',
    ];
    protected $msg = [
        'update_id'  =>  '',
    ];
}