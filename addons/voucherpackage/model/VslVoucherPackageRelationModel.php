<?php

namespace addons\voucherpackage\model;

use data\model\BaseModel as BaseModel;


class VslVoucherPackageRelationModel extends BaseModel
{

    protected $table = 'vsl_voucher_package_relation';

    public function voucher_relation()
    {
        return $this->morphMany('VslVoucherPackageRelationModel', 'relation');
    }

}