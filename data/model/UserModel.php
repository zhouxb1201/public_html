<?php

namespace data\model;

use think\Db;
use data\model\BaseModel as BaseModel;

/**
 * 用户表
 */
class UserModel extends BaseModel
{
    public $table = 'sys_user';
    protected $rule = [
        'uid' => '',
	    'user_headimg'  =>  'no_html_parse',
	    'mp_sub_message'  =>  'no_html_parse'
    ];
    protected $msg = [
        'uid' => '',
    ];

    public function province()
    {
        return $this->belongsTo('ProvinceModel', 'province_id', 'province_id');
    }

    public function city()
    {
        return $this->belongsTo('CityModel', 'city_id', 'city_id');
    }

    public function district()
    {
        return $this->belongsTo('DistrictModel', 'district_id', 'district_id');
    }

    public function member_info()
    {
        return $this->hasOne('VslMemberModel', 'uid', 'uid');
    }

    public function member_account()
    {
        return $this->hasOne('VslMemberAccountModel', 'uid', 'uid');
    }

    public function member_address()
    {
        return $this->hasMany('VslMemberExpressAddressModel', 'uid', 'uid');
    }

    /***
     * 手机是否已经绑定过（移动，小程序）
     * @param array $where
     * @return bool;
     */
    public function isMobileAssociate($where = [])
    {
        $res = Db::table($this->table)
            ->where($where)
            ->where('wx_openid|mp_open_id','NEQ','')
            ->find();
        return $res;
    }

    public function getModuleIdArray($condition)
    {
        $list = $this->alias('u')
            ->join('sys_user_admin ua', 'u.uid=ua.uid','inner')
            ->join('sys_user_group ug','ua.group_id_array=ug.group_id','left')
            ->field('ug.module_id_array')
            ->where($condition)
            ->find();
        return $list;
    }
    public function countryCode()
    {
        return $this->belongsTo('SysCountryCodeModel', 'country_code', 'country_code');
    }
}
