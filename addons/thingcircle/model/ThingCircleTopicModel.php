<?php

namespace addons\thingcircle\model;

use data\model\BaseModel as BaseModel;

/**
 * 话题表
 * @author  www.vslai.com
 *
 */
class ThingCircleTopicModel extends BaseModel
{

    protected $table = 'vsl_thing_circle_topic';
    
    /*
     * 获取列表
     */
    public function getThingCircleTopicList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getThingCircleTopicViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getThingCircleTopicViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getThingCircleTopicViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('tct')
            ->field('tct.*');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getThingCircleTopicViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }

    public function getThingCircleTopicByParentId($topic_id)
    {
        $list = $this->where(['superiors_id' => $topic_id])->select();
        return $list;
    }
    
    public function getThingCircleTopicQuery($condition, $field, $order)
    {
        $list = $this->getQuery($condition, $field, $order);
        return $list;
    }

    public function getTopicLists()
    {
        $list = $this->field('*')->select();
        return $list;
    }
}