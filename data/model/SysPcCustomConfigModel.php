<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * pc装修导航表
 */
class SysPcCustomConfigModel extends BaseModel {
    
    protected $table = 'sys_pc_custom_config';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}