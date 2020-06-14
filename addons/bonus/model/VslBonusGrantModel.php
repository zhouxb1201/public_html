<?php
namespace addons\bonus\model;

use data\model\BaseModel as BaseModel;
/**
 * 分红发放记录表
 */
class VslBonusGrantModel extends BaseModel {

    protected $table = 'vsl_bonus_grant_records';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getViewList1($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQuery1($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getViewList2($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQuery2($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getViewListInfo($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQueryInfo($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getViewListInfo1($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQueryInfo1($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getViewListInfo2($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQueryInfo2($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getViewLists($page_index, $page_size, $condition, $order,$group){
        $queryList = $this->getViewQuerys($page_index, $page_size, $condition, $order,$group);
        $queryCount = count($queryList);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member sm','nmar.uid = sm.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->field('nmar.* ,sm.member_name, sm.real_name,us.user_name,us.nick_name, sm.mobile,sm.global_agent_level_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getViewQuery1($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member sm','nmar.uid = sm.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->field('nmar.* ,sm.member_name, sm.real_name, us.user_name,us.nick_name,sm.mobile,sm.area_agent_level_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getViewQuery2($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member sm','nmar.uid = sm.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->field('nmar.* ,sm.member_name, sm.real_name, us.user_name,us.nick_name,sm.mobile,sm.team_agent_level_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getViewQueryInfo($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member sm','nmar.uid = sm.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->field('nmar.* ,us.user_headimg,sm.member_name,us.user_name,us.nick_name, sm.real_name, sm.mobile,sm.global_agent_level_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
   }
    public function getViewQueryInfo1($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member sm','nmar.uid = sm.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->field('nmar.* ,us.user_headimg,sm.member_name,us.user_name,us.nick_name, sm.real_name, sm.mobile,sm.area_agent_level_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getViewQueryInfo2($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member sm','nmar.uid = sm.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->field('nmar.* ,us.user_headimg,sm.member_name, us.user_name,us.nick_name,sm.real_name, sm.mobile,sm.team_agent_level_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getViewQuerys($page_index, $page_size, $condition, $order,$group)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member sm','nmar.uid = sm.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->field('sm.*,nmar.type,nmar.bonus,nmar.grant_time,nmar.sn,sum(nmar.bonus) as bonus_total,us.user_name,us.nick_name');
        $list = $this->viewPageQuerys($viewObj, $page_index, $page_size, $condition, $order,$group);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nmar')
            ->join('vsl_member sm','nmar.uid = sm.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->field('nmar.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

}