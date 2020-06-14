<?php
namespace addons\miniprogram\model;
use data\model\BaseModel as BaseModel;
/**
 * 小程序消息模板
 *
 */
class MpTemplateRelationModel extends BaseModel {
    protected $table = 'sys_mp_template_relation';
    public function message()
    {
        return $this->belongsTo('MpMessageTemplateModel', 'template_id', 'template_id');
    }
}