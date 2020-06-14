<?php
namespace data\model;
use data\model\BaseModel as BaseModel;
/**
 * 微信公众号授权
 *
 */
class WeixinGroupModel extends BaseModel {
    protected $table = 'sys_weixin_fans_group';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}