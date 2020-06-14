<?php

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 地区市表
 */
class CityModel extends BaseModel
{

    protected $table = 'sys_city';
    protected $primary_key = 'city_id';
    protected $rule = [
        'city_id' => '',
    ];
    protected $msg = [
        'city_id' => '',
    ];

    public function province()
    {
        return $this->belongsTo('ProvinceModel', 'province_id', 'province_id');
    }

    public function district()
    {
        return $this->hasMany('DistrictModel', $this->primary_key, $this->primary_key);
    }
}