<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 平台订单资金记录
 * @author  www.vslai.com
 *
 */
class VslAccountOrderRecordsModel extends BaseModel {

    protected $table = 'vsl_account_order_records';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

}