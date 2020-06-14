<?php

namespace addons\voucherpackage\model;

use data\model\BaseModel as BaseModel;

class VslVoucherPackageModel extends BaseModel
{

    protected $table = 'vsl_voucher_package';

    public function voucher_relation()
    {
        return $this->hasMany('VslVoucherPackageRelationModel', 'voucher_package_id', 'voucher_package_id');
    }

    public function history()
    {
        return $this->hasMany('VslVoucherPackageHistoryModel', 'voucher_package_id', 'voucher_package_id');
    }
}