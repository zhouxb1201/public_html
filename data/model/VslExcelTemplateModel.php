<?php

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 导出模板表
 */
class VslExcelTemplateModel extends BaseModel
{
    public $table = 'vsl_excel_template';
    protected $rule = [
        'template_id' => '',
    ];
    protected $msg = [
        'template_id' => '',
    ];
    
    
    /**
     * 列表
     */
    public function getList($contdition)
    {
        $list = $this->where($contdition)->select();
        return $list;
    }
}
