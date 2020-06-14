<?php

namespace addons\thingcircle\model;

use data\model\BaseModel as BaseModel;

/**
 * 消息表
 * @author  www.vslai.com
 *
 */
class MsgReminderModel extends BaseModel
{

    protected $table = 'vsl_msg_reminder';
    
    /*
     * 获取列表
     */
    public function getThingCircleMsgList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getThingCircleViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getThingCircleViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    /*
     * 获取数据
     */
    public function getThingCircleViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('tcm')
            ->field('tcm.*');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getThingCircleViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }
}