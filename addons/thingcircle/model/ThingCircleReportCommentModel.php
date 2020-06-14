<?php

namespace addons\thingcircle\model;

use data\model\BaseModel as BaseModel;

/**
 * 评论表
 * @author  www.vslai.com
 *
 */
class ThingCircleReportCommentModel extends BaseModel
{

    protected $table = 'vsl_thing_circle_report_comment';
    
    /*
     * 获取列表
     */
    public function getThingCircleReportCommentList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getThingCircleCommentViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getThingCircleCommentViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    /*
     * 获取数据
     */
    public function getThingCircleReportCommentViewQuery($page_index, $page_size, $condition, $order)
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
        $count = $this->getCount($condition);
        return $count;
    }

    public function getThingCircleReportCommentByState($page_index, $page_size, $condition, $order)
    {
        $count = $this->alias('tcrc')
            ->join('sys_user u', 'tcrc.report_uid = u.uid', 'LEFT')
            ->join('vsl_thing_circle_comment tcc', 'tcrc.comment_id = tcc.id', 'LEFT')
            ->join('sys_user u2', 'tcc.from_uid = u2.uid', 'LEFT')
            ->join('vsl_thing_circle_violation tcv', 'tcrc.violation_id = tcv.violation_id', 'LEFT')
            ->where($condition)
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $list = $this->alias('tcrc')
            ->join('sys_user u', 'tcrc.report_uid = u.uid', 'LEFT')
            ->join('vsl_thing_circle_comment tcc', 'tcrc.comment_id = tcc.id', 'LEFT')
            ->join('sys_user u2', 'tcc.from_uid = u2.uid', 'LEFT')
            ->join('vsl_thing_circle_violation tcv', 'tcrc.violation_id = tcv.violation_id', 'LEFT')
            ->field('tcrc.*,u.user_name,u.user_headimg,u.nick_name,u2.user_name report_name,u2.user_headimg report_headimg,u2.nick_name report_nick_name,tcv.name,tcc.content,u.user_tel,u2.user_tel report_tel,u.uid,u2.uid report_uid')
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

    public function getReportById($condition){
        $list = $this->alias('rc')
            ->join('vsl_thing_circle_violation v','rc.violation_id = v.violation_id', 'LEFT')
            ->field('rc.*,v.name')
            ->where($condition)
            ->select();

        return $list;
    }

}