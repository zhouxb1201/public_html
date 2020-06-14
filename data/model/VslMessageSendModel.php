<?php
namespace data\model;
use data\model\BaseModel as BaseModel;
use think\Validate;
class VslMessageSendModel extends BaseModel{
    
    protected $table = 'vsl_message_send';
    protected $rule = [
        'message_send_id'  =>  ''
    ];
    protected $msg = [
        'message_send_id'  =>  ''
    ];   
}