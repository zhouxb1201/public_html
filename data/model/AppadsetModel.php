<?php

namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 第三方配置表
 */
class AppadsetModel extends BaseModel {

    protected $table = 'vsl_appadset';

    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}