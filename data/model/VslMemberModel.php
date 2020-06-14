<?php

namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 前台会员表
 * @author  www.vslai.com
 *
 */
class VslMemberModel extends BaseModel {
    protected $table = 'vsl_member';
    protected $rule = [
        'uid'  =>  '',
    ];
    protected $msg = [
        'uid'  =>  '',
    ];

    public function level()
    {
        return $this->hasOne('VslMemberLevelModel','level_id','member_level');
    }

    public function member_account()
    {
        return $this->hasOne('VslMemberAccountModel','uid','uid');
    }
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
        $viewObj = $this->alias('nm')
            ->join('sys_user su','nm.uid= su.uid','left')
            ->field('nm.custom_area,nm.custom_global,nm.custom_team,nm.distributor_apply,su.uid,su.user_headimg, su.user_name, su.user_tel,su.nick_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nm')
            ->join('sys_user su','nm.uid= su.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}
