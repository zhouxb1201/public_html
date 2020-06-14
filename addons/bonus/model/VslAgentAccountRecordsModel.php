<?php
namespace addons\bonus\model;

use data\model\BaseModel as BaseModel;
/**
 * 分红账户流水表
 * @author  www.vslai.com
 *
 */
class VslAgentAccountRecordsModel extends BaseModel {
    protected $table = 'vsl_agent_account_records';
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
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nmar')
            ->join('sys_user su','nmar.uid = su.uid','left')
            ->field('nmar.*,su.nick_name,su.user_name,su.user_tel');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nmar')
            ->join('sys_user su','nmar.uid = su.uid','left')
            ->field('nmar.id,su.nick_name,su.user_name,su.user_tel');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}