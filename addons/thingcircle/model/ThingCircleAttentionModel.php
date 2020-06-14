<?php

namespace addons\thingcircle\model;

use data\model\BaseModel as BaseModel;

/**
 * 关注表
 * @author  www.vslai.com
 *
 */
class ThingCircleAttentionModel extends BaseModel
{

    protected $table = 'vsl_thing_circle_attention';
    
    /*
     * 获取列表
     */
    public function getThingCircleAttentionList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getThingCircleViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getThingCircleViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    public function getThingCircleAttentionLists($page_index, $page_size, $condition, $order)
    {
        $count = $this->alias('tca')
            ->join('sys_user u', 'tca.uid = u.uid', 'LEFT')
            ->join('sys_user au', 'tca.attention_uid = au.uid', 'LEFT')
            ->where($condition)
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $list = $this->alias('tca')
            ->join('sys_user u', 'tca.uid = u.uid', 'LEFT')
            ->join('sys_user au', 'tca.attention_uid = au.uid', 'LEFT')
            ->field('tca.*,u.user_name,u.user_headimg,u.nick_name,u.user_tel,au.user_name auser_name,au.user_headimg auser_headimg,au.nick_name anick_name,au.user_tel anick_tel')
            ->where($condition)
            ->order($order)
            ->limit($offset, $page_size)
            ->select();
        //return $list;
        return ['code'=>0,
            'data'=>$list,
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }

    /*
     * 获取数据
     */
    public function getThingCircleViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('tca')
            ->join('sys_user u', 'tca.uid = u.uid', 'LEFT')
            ->join('sys_user au', 'tca.attention_uid = au.uid', 'LEFT')
            ->field('tca.*,u.user_name,u.user_headimg,au.user_name auser_name,au.user_headimg auser_headimg');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取各状态数量
     */
    public function getThingCircleNum($condition)
    {
        unset($condition['state']);
        $wholeCount = $this->getThingCircleViewCount($condition);
        $condition['state'] = 1;
        $stayCount = $this->getThingCircleViewCount($condition);
        $condition['state'] = 2;
        $startCount = $this->getThingCircleViewCount($condition);
        $condition['state'] = 3;
        $endCount = $this->getThingCircleViewCount($condition);
        $count['whole'] = $wholeCount;
        $count['stay'] = $stayCount;
        $count['start'] = $startCount;
        $count['end'] = $endCount;
        return $count;
    }
    /*
     * 获取数量
     */
    public function getThingCircleViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }

    public function getAttentionArray($condition){
        $arr = $this->field('attention_uid')->where($condition)->select();
        return $arr;
    }
}