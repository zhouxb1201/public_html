<?php
namespace data\model;
use data\model\BaseModel as BaseModel;
/**
 * 系统通知模板集合
 */
class NoticeTemplateTypeModel extends BaseModel {
    protected $table = 'sys_notice_template_type';
    protected $rule = [
        'type_id'  =>  '',
    ];
    protected $msg = [
        'type_id'  =>  '',
    ];
}