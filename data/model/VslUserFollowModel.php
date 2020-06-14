<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员关注表
 * @author  www.vslai.com
 *
 */
class VslUserFollowModel extends BaseModel {
    
    protected $table = 'vsl_user_follow';
    
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
        ->join('sys_user au', 'tca.follow_uid = au.uid', 'LEFT')
        ->where($condition)
        ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $list = $this->alias('tca')
        ->join('sys_user u', 'tca.uid = u.uid', 'LEFT')
        ->join('sys_user au', 'tca.follow_uid = au.uid', 'LEFT')
        ->field('tca.*,u.user_name,u.user_headimg,u.nick_name,u.user_tel,au.user_name auser_name,au.user_headimg auser_headimg,au.nick_name anick_name,au.user_tel auser_tel')
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
        ->join('sys_user au', 'tca.follow_uid = au.uid', 'LEFT')
        ->field('tca.*,u.user_name,u.user_headimg,au.user_name auser_name,au.user_headimg auser_headimg');
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
    
    public function getAttentionArray($condition){
        $arr = $this->field('follow_uid')->where($condition)->select();
        return $arr;
    }
}