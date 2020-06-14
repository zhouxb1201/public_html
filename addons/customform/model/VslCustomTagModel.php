<?php

namespace addons\customform\model;

use data\model\BaseModel as BaseModel;

class VslCustomTagModel extends BaseModel
{
    protected $table = 'vsl_custom_tag';
    protected $rule = [
        'custom_tag_id' => '',
    ];
    protected $msg = [
        'custom_tag_id' => '',
    ];

    public function custom()
    {
        return $this->belongsTo('VslCustomModel');
    }
}