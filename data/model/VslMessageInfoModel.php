<?php
namespace data\model;
use data\model\BaseModel as BaseModel;
use think\Validate;
class VslMessageInfoModel extends BaseModel{
    
    protected $table = 'vsl_message_info';
    protected $rule = [
        'message_info_id'  =>  '',
        'title' => '',
        'content'  =>  'no_html_parse',
    ];
    protected $msg = [
        'message_info_id'  =>  '',
        'title' => '',
        'content'  =>  'no_html_parse',
    ];   
}