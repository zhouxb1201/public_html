<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/13 0013
 * Time: 9:48
 */

namespace addons\delivery\model;

use data\model\BaseModel;

class VslSenderTemplateModel extends BaseModel
{
    protected $table = 'vsl_sender_template';

    public function province()
    {
        return $this->belongsTo('\data\model\ProvinceModel', 'province_id', 'province_id');
    }

    public function city()
    {
        return $this->belongsTo('\data\model\CityModel', 'city_id', 'city_id');
    }

    public function district()
    {
        return $this->belongsTo('\data\model\DistrictModel', 'district_id', 'district_id');
    }
}