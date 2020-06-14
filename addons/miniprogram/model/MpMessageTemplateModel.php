<?php
namespace addons\miniprogram\model;
use data\model\BaseModel as BaseModel;
/**
 * 小程序消息模板
 *
 */
class MpMessageTemplateModel extends BaseModel {
    protected $table = 'sys_mp_message_template';
    public function _relation()
    {
        return $this->hasMany('MpTemplateRelationModel', 'template_id', 'template_id');
    }
}