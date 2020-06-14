<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/2 0002
 * Time: 14:50
 */

namespace data\model;

use data\model\BaseModel;

class VslOrderMemoModel extends BaseModel
{
    protected $table = 'vsl_order_memo';
    protected $rule = [
        'order_memo_id' => '',
    ];
    protected $msg = [
        'order_memo_id' => '',
    ];

    public function order()
    {
        return $this->belongsTo('VslOrderModel', 'order_id', 'order_id');
    }

    public function user()
    {
        return $this->belongsTo('UserModel', 'uid', 'uid');
    }
}