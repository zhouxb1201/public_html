<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 用户日志模型
 */
class NoticeTemplateModel extends BaseModel {

    protected $table = 'sys_notice_template';
    protected $rule = [
        'template_id'  =>  '',
        'template_content'  =>  'no_html_parse'
    ];
    protected $msg = [
        'template_id'  =>  '',
        'template_content'  =>  ''
    ];

}