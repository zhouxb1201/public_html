<?php

namespace addons\customform\model;

use data\model\BaseModel as BaseModel;

class VslCustomModel extends BaseModel
{
    protected $table = 'vsl_custom';
    protected $rule = [
        'custom_id' => '',
    ];
    protected $msg = [
        'custom_id' => '',
    ];

    public function custom_tag()
    {
        return $this->hasOne('VslCustomTagModel','id','tagid');
    }

    /**
     * 获取列表
     * @param $page_index
     * @param $page_size
     * @param $condition
     * @param $order
     * @return array|\data\model\multitype
     */
    public function getList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->viewPageQuery($this,$page_index,$page_size,$condition,$order);
        $queryCount = $this->viewCount($this,$condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
}