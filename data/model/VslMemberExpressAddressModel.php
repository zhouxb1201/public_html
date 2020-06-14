<?php

namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员物流地址管理
 *  id int(11) NOT NULL AUTO_INCREMENT,
  uid int(11) NOT NULL COMMENT '会员基本资料表ID',
  consigner varchar(255) NOT NULL DEFAULT '' COMMENT '收件人',
  mobile varchar(11) NOT NULL DEFAULT '' COMMENT '手机',
  phone varchar(20) NOT NULL DEFAULT '' COMMENT '固定电话',
  province int(11) NOT NULL DEFAULT 0 COMMENT '省',
  city int(11) NOT NULL DEFAULT 0 COMMENT '市',
  district int(11) NOT NULL DEFAULT 0 COMMENT '区县',
  address varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  zip_code varchar(6) NOT NULL DEFAULT '' COMMENT '邮编',
  alias varchar(50) NOT NULL DEFAULT '' COMMENT '地址别名',
  is_default bit(1) NOT NULL DEFAULT b'0' COMMENT '默认收货地址',
 * @author  www.vslai.com
 *
 */
class VslMemberExpressAddressModel extends BaseModel {
    protected $table = 'vsl_member_express_address';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

    // district的命名和表内district字段命名的重复了，导致前者覆盖后者，所以加了‘area_’以区分
    public function area_district()
    {
        return $this->belongsTo('DistrictModel','district','district_id');
    }

    // city的命名和表内city字段命名的重复了，导致前者覆盖后者，所以加了‘area_’以区分
    public function area_city()
    {
        return $this->belongsTo('CityModel','city','city_id');
    }

    // province的命名和表内province字段命名的重复了，导致前者覆盖后者，所以加了‘area_’以区分
    public function area_province()
    {
        return $this->belongsTo('ProvinceModel','province','province_id');
    }
}