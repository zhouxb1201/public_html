<?php

namespace addons\thingcircle\model;

use data\model\BaseModel as BaseModel;

/**
 * 违规信息表
 * @author  www.vslai.com
 *
 */
class ThingCircleViolationModel extends BaseModel
{

    protected $table = 'vsl_thing_circle_violation';
    
    /*
     * 获取列表
     */
    public function getThingCircleViolationList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getThingCircleViolationViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getThingCircleViolationViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    /*
     * 获取数据
     */
    public function getThingCircleViolationViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->field('*');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getThingCircleViolationViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }

}