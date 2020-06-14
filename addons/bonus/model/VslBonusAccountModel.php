<?php
namespace addons\bonus\model;

use data\model\BaseModel as BaseModel;
/**
 * 代理商账户表
 * @author  www.vslai.com
 *
 */
class VslBonusAccountModel extends BaseModel {
    protected $table = 'vsl_bonus_account';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
    public function getViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
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
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member su','nmar.uid = su.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->join('vsl_agent_level al','su.global_agent_level_id = al.id','left')
            ->field('nmar.* ,su.member_name,us.user_name,us.nick_name, su.real_name, su.mobile, al.level_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getViewQuery2($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member su','nmar.uid = su.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->join('vsl_agent_level al','su.team_agent_level_id = al.id','left')
            ->field('nmar.* ,su.member_name, su.real_name, us.user_name,us.nick_name,su.mobile, al.level_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nmar')
            ->join('vsl_member su','nmar.uid = su.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->field('nmar.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    public function getViewList1($page_index, $page_size, $condition, $order){
        $queryList = $this->getViewQuery1($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    public function getViewQuery1($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('vsl_member su','nmar.uid = su.uid','left')
            ->join('sys_user us','nmar.uid = us.uid','left')
            ->join('vsl_agent_level al','su.area_agent_level_id = al.id','left')
            ->field('nmar.* ,su.member_name, su.real_name,us.user_name,us.nick_name, su.mobile, al.level_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }


}