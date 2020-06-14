<?php
namespace data\model;
use data\extend\database;
use data\model\BaseModel as BaseModel;
/**
 * 后台用户view列表
 */
class AdminUserViewModel extends BaseModel {
    protected $table = 'sys_user_admin';
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getAdminUserViewList($page_index, $page_size, $condition, $order){
        
        $queryList = $this->getAdminUserViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getAdminUserViewCount($condition);
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
     public function getAdminUserViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('sua')
        ->join('sys_user sur', 'sur.uid=sua.uid','inner')
        ->join('sys_user_group su','sua.group_id_array=su.group_id','left')
        ->join('sys_website web','sua.website_id=web.website_id','left')
        ->field('sua.uid, sur.user_name as admin_name,sua.user, sua.is_admin, sur.user_status, su.group_name, sur.user_headimg, sur.user_email, sur.user_tel,su.website_id,web.uid as adminid');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getAdminUserViewCount($condition)
    {
        $viewObj = $this->alias('sua')
        ->join('sys_user sur', 'sur.uid=sua.uid','inner')
        ->join('sys_user_group su','sua.group_id_array=su.group_id','left')
        ->field('sua.ua_id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

    public function getAdminUser($condition)
    {
        $list = $this->alias('sua')
            ->join('sys_user sur', 'sur.uid=sua.uid','inner')
            ->join('sys_user_group su','sua.group_id_array=su.group_id','left')
            ->join('sys_website web','sua.website_id=web.website_id','left')
            ->field('sua.uid, sur.user_name as admin_name,sua.user, sua.is_admin, sur.user_status, su.group_name, sur.user_headimg, sur.user_email, sur.user_tel, sur.thing_circle_uid,su.website_id,web.uid as adminid')
            ->where($condition)
            ->find();
        return $list;
    }
}