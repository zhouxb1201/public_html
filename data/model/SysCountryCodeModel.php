<?php

namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 第三方配置表
 */
class SysCountryCodeModel extends BaseModel {

    protected $table = 'sys_country_code';
    protected $rule = [
        'code_id'  =>  '',
    ];
    protected $msg = [
        'code_id'  =>  '',
    ];

}