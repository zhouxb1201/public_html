<?php
namespace addons\bonus\model;

use data\model\BaseModel as BaseModel;
/**
 * 发放时间周期
 * @author  www.vslai.com
 *
 */
class VslGrantTimeModel extends BaseModel {

    protected $table = 'sys_grant_time';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

}