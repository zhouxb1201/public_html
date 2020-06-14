<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/13 0013
 * Time: 9:48
 */

namespace addons\delivery\model;

use data\model\BaseModel;

class VslFormExpressCompanyModel extends BaseModel
{
    protected $table = 'vsl_form_express_company';

    public function form_style()
    {
        return $this->hasMany('VslFormStyleModel', 'form_express_company_id', 'form_express_company_id');
    }
}