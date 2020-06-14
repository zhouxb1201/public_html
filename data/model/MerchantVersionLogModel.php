<?php
namespace data\model;
use think\Db;
use data\model\BaseModel as BaseModel;
/**
 * 版本变更记录表
 */
class MerchantVersionLogModel extends BaseModel {

    protected $table = 'sys_merchant_version_log';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}