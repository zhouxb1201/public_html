<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/13 0013
 * Time: 9:48
 */

namespace addons\delivery\model;

use data\model\BaseModel;

class VslFormTemplateModel extends BaseModel
{
    protected $table = 'vsl_form_template';

    public function form_express_company()
    {
        return $this->belongsTo('VslFormExpressCompanyModel', 'form_express_company_id', 'form_express_company_id');
    }

    public function form_style()
    {
        return $this->belongsTo('VslFormStyleModel', 'form_style_id', 'form_style_id');
    }
}