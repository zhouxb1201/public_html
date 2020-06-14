<?php

namespace addons\miniprogram\model;

use data\model\BaseModel as BaseModel;

/**
 * 小程序提交审核历史
 *
 */
class MpSubmitModel extends BaseModel
{
    protected $table = 'sys_mp_submit';

    public function auth()
    {
        return $this->belongsTo('WeixinAuthModel', 'auth_id', 'auth_id');
    }
}