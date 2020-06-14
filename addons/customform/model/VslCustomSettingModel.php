<?php

namespace addons\customform\model;

use data\model\BaseModel as BaseModel;

class VslCustomSettingModel extends BaseModel
{
    protected $table = 'vsl_custom_setting';
    protected $rule = [
        'custom_id' => '',
    ];
    protected $msg = [
        'custom_id' => '',
    ];

    public function custom()
    {
        return $this->belongsTo('VslCustomFormModel');
    }
}