<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 插件表
 */
class SysAddonsClicksModel extends BaseModel {
    
    protected $table = 'sys_addons_clicks';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}