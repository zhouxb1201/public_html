<?php
namespace addons\miniprogram\model;
use data\model\BaseModel as BaseModel;
/**
 * 微信公众号授权
 *
 */
class WeixinAuthModel extends BaseModel {
    protected $table = 'sys_weixin_auth';

    public function public_program()
    {
        return $this->hasMany('MiniProgramPublicModel','auth_id','weinxin_auth_id');
    }
}