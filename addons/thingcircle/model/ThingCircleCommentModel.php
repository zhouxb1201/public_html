<?php

namespace addons\thingcircle\model;

use data\model\BaseModel as BaseModel;

/**
 * 评论表
 * @author  www.vslai.com
 *
 */
class ThingCircleCommentModel extends BaseModel
{

    protected $table = 'vsl_thing_circle_comment';
    
    /*
     * 获取列表
     */
    public function getThingCircleCommentList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getThingCircleCommentViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getThingCircleCommentViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    /*
     * 获取数据
     */
    public function getThingCircleCommentViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->field('*');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getThingCircleViewCount($condition)
    {
        $viewObj = $this->alias('tcc');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

    public function getThingCircleCommentByState($page_index, $page_size, $condition, $order)
    {
        $count = $this->alias('tcc')
            ->join('sys_user u', 'tcc.from_uid = u.uid', 'LEFT')
            ->join('sys_user u2', 'tcc.report_uid = u2.uid', 'LEFT')
            ->join('vsl_thing_circle_violation tcv', 'tcc.violation_id = tcv.violation_id', 'LEFT')
            ->where($condition)
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $list = $this->alias('tcc')
            ->join('sys_user u', 'tcc.from_uid = u.uid', 'LEFT')
            ->join('sys_user u2', 'tcc.report_uid = u2.uid', 'LEFT')
            ->join('vsl_thing_circle_violation tcv', 'tcc.violation_id = tcv.violation_id', 'LEFT')
            ->field('tcc.*,u.user_name,u.user_headimg,u.nick_name,u.user_tel,u2.user_name report_name,u2.user_headimg report_headimg,tcv.name,u2.nick_name report_nick_name')
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

    public function getThingCircleCommentById($page_index, $page_size, $condition, $order)
    {
        $count = $this->alias('tcc')
            ->join('sys_user u', 'tcc.from_uid = u.uid', 'LEFT')
            ->join('sys_user u2', 'tcc.to_uid = u2.uid', 'LEFT')
            ->join('vsl_thing_circle tc','tcc.thing_id = tc.id', 'LEFT')
            ->where($condition)
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $list = $this->alias('tcc')
            ->join('sys_user u', 'tcc.from_uid = u.uid', 'LEFT')
            ->join('sys_user u2', 'tcc.to_uid = u2.uid', 'LEFT')
            ->join('vsl_thing_circle tc','tcc.thing_id = tc.id', 'LEFT')
            ->field('tcc.id,tcc.content,tcc.from_uid,tcc.to_uid,tcc.comment_likes,tcc.is_check,tcc.create_time,tcc.thing_id,u.user_name,u.user_headimg,u.nick_name,u.user_tel,u2.user_name to_user_name,u2.nick_name to_nick_name,u2.user_tel to_user_tel,tc.user_id author_id,tc.media_val,tc.thing_type,tc.video_img')
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

    public function getThingCircleReplyCommentLists($page_index, $page_size, $condition, $order)
    {
        $count = $this->alias('tcc')
            ->join('sys_user u', 'tcc.from_uid = u.uid', 'LEFT')
            ->join('sys_user u2', 'tcc.to_uid = u2.uid', 'LEFT')
            ->join('vsl_thing_circle tc','tcc.thing_id = tc.id', 'LEFT')
            ->where($condition)
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $list = $this->alias('tcc')
            ->join('sys_user u', 'tcc.from_uid = u.uid', 'LEFT')
            ->join('sys_user u2', 'tcc.to_uid = u2.uid', 'LEFT')
            ->join('vsl_thing_circle tc','tcc.thing_id = tc.id', 'LEFT')
            ->field('tcc.*,u.user_name,u.user_headimg,u.nick_name,u.uid user_id,u.user_tel,u2.user_name to_name,u2.user_headimg to_headimg,u2.nick_name to_nick_name,u2.uid to_uid,u2.user_tel to_user_tel,tc.user_id author_id')
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

    public function getReplyCommentList($condition, $order, $limit = 0)
    {
        $list = $this->alias('tcc')
        ->join('sys_user u', 'tcc.from_uid = u.uid', 'LEFT')
        ->join('sys_user u2', 'tcc.to_uid = u2.uid', 'LEFT')
        ->join('vsl_thing_circle tc','tcc.thing_id = tc.id', 'LEFT')
        ->field('tcc.id,tcc.content,tcc.from_uid,tcc.to_uid,tcc.comment_likes,tcc.is_check,tcc.create_time,tcc.thing_id,u.user_name,u.user_headimg,u.nick_name,u.user_tel,u2.user_name to_name,u2.user_headimg to_headimg,u2.nick_name to_nick_name,u2.uid to_uid,u2.user_tel to_user_tel,tc.user_id author_id')
        ->where($condition);
        if($limit>0){
            $list = $list->limit(0, $limit);
        }
        $list = $list->order($order)
        ->select();
        return ['data'=>$list,'total_count' => $this->getThingCircleViewCount($condition)];
    }
}