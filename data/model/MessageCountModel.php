<?php

namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 第三方配置表
 */
class MessageCountModel extends BaseModel {

    protected $table = 'sys_message_count';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

}