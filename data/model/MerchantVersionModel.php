<?php
namespace data\model;
use think\Db;
use data\model\BaseModel as BaseModel;
/**
 * 系统实例类型表(店铺类型)
 */
class MerchantVersionModel extends BaseModel {

    protected $table = 'sys_merchant_version';
    protected $rule = [
        'merchant_versionid'  =>  '',
    ];
    protected $msg = [
        'merchant_versionid'  =>  '',
    ];
}