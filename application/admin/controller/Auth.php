<?php

/**
 * Auth.php
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */

namespace app\admin\controller;

/**
 * 用户权限控制器
 */
use data\model\AuthGroupModel;
use data\model\ModuleModel;
use data\model\VslIncreMentOrderModel;
use data\service\AuthGroup as AuthGroup;
use addons\shop\service\Shop as Shop;

class Auth extends BaseController {

    private $auth_group;

    public function __construct() {
        parent::__construct();
        $this->auth_group = new AuthGroup();
    }

    /**
     * 用户列表
     *
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function userList() {
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text', '');
            $condition = [];
            if (!empty($search_text)) {
                $condition = array(
                    'sur.user_name|sua.uid' => array(
                        'like',
                        '%' . $search_text . '%'
                    )
                );
            }
            $condition['sur.instance_id'] = $this->instance_id;
            $user_list = $this->user->adminUserList($page_index, $page_size, $condition);
            $shopService = new Shop();
            if ($user_list['data']) {
                foreach ($user_list['data'] as $key => $val) {
                    $checkIsAdmin = $shopService->getShopByUid($val['uid']);
                    $user_list['data'][$key]['is_admin'] = $checkIsAdmin;
                }
                unset($val);
            }
            return $user_list;
        } else {
            return view($this->style . 'Auth/userList');
        }
    }

    /**
     * 用户日志，Hidden
     *
     * @return unknown|\think\response\View
     */
    public function userLoglist() {
        if (request()->isAjax()) {
            $page_index = request()->post('pageIndex', 1);
            $condition = "";
            $list = $this->user->getUserLogList($page_index, PAGESIZE, $condition, "create_time desc");
            return $list;
        } else {
            return view($this->style . "Auth/userLoglist");
        }
    }

    /**
     * 用户组列表
     */
    public function authGroupList() {
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $list = $this->auth_group->getSystemUserGroupList($page_index, $page_size, [
                'instance_id' => $this->instance_id
            ]);
            return $list;
        } else {
            return view($this->style . 'Auth/authGroupList');
        }
    }

    /**
     * 添加或者编辑用户组
     */
    public function addUserGroup() {
        if (request()->isAjax()) {
            $group_id = request()->post('group_id', 0);
            $module_id_array = request()->post('select_box', '');
            $role_name = request()->post('group_name', '');
            $desc = request()->post('desc', '');
            $operation = '';
            if ($group_id != 0) {
                $retval = $this->auth_group->updateSystemUserGroup($group_id, $role_name, '', 1, false, $module_id_array, $desc);
                $operation = '添加用户组';
            } else {
                $retval = $this->auth_group->addSystemUserGroup($role_name, '', false, $module_id_array, $desc);
                $operation = '编辑用户组';
            }
            if ($retval) {
                $this->addUserLog($operation, $role_name);
            }
            return AjaxReturn($retval);
        } else {
            $group_id = isset($_GET['group_id']) ? $_GET['group_id'] : '';
            if ($group_id) {
                $info = $this->auth_group->getSystemUserGroupDetail($group_id);
                $this->assign('info', $info);
            }
            $permissionList = $this->website->getInstanceModuleQuery();
            $auth_group= new AuthGroupModel();
            $system_auth = $auth_group->getInfo(['is_system'=>1,'instance_id'=>0,'website_id'=>$this->website_id],'order_id,group_id,module_id_array');
            $default_module_id_array='';
            if($system_auth['order_id']){
                $module = new ModuleModel();
                $order_ids = explode(',',$system_auth['order_id']);
                $order = new VslIncreMentOrderModel();
                foreach ($order_ids as $value){
                    $addons_id = $order->getInfo(['order_id'=>$value],'*');
                    $shop_module_id = $module->Query(['addons_sign'=>$addons_id['addons_id'],'module'=>'admin'],'module_id');
                    if($addons_id['expire_time']<time()){
                        $default_module_id_array.= implode(',',$shop_module_id);
                    }
                }
            }
            $default_module_id_array = explode(',',$default_module_id_array);
            $firstArray = array();
            $p = array();
            for ($i = 0; $i < count($permissionList); $i ++) {
                $per = $permissionList[$i];
                if ($per["pid"] == 0 && $per["module_name"] != null) {
                    $firstArray[] = $per;
                }
            }
            for ($i = 0; $i < count($firstArray); $i ++) {
                $first_per = $firstArray[$i];
                $secondArray = array();
                for ($y = 0; $y < count($permissionList); $y ++) {
                    $childPer = $permissionList[$y];
                    if ($childPer["pid"] == $first_per["module_id"]) {
                        $secondArray[] = $childPer;
                    }
                }
                $first_per['child'] = $secondArray;
                for ($j = 0; $j < count($secondArray); $j ++) {
                    $second_per = $secondArray[$j];
                    $threeArray = array();
                    for ($z = 0; $z < count($permissionList); $z ++) {
                        $three_per = $permissionList[$z];
                        if ($three_per["pid"] == $second_per["module_id"]) {
                            $threeArray[] = $three_per;
                        }
                    }
                    $second_per['child'] = $threeArray;
                }
                $p[] = $first_per;
                $first_per = array();
            }
            $this->assign("list", $p);
        }
        $this->assign("default_module_id_array", $default_module_id_array);
        return view($this->style . "Auth/addUserGroup");
    }

    /**
     * 添加 后台用户
     */
    public function addUser() {
        if (request()->isAjax()) {
            $admin_name = request()->post('admin_name', '');
            $group_id = request()->post('group_id', '');
            $user_password = request()->post('user_password', '123456');
            $desc = request()->post('desc', '');
            $user = request()->post('user', '');
            $mobile = request()->post('mobile', '');
            $res = $this->user->checkAdminUser(['user_tel'=>$mobile,'website_id'=>$this->website_id,'port'=>'admin']);
            if($res<0){
                $retval = $this->user->addAdminUser($admin_name, $group_id, $user_password, $desc, $user, $this->instance_id, 'admin',$mobile);
                if($retval){
                    $this->addUserLog('添加后台用户', $admin_name);
                }
                return AjaxReturn($retval);
            }else{
                return AjaxReturn(-2);
            }
        } else {
            $condition["instance_id"] = $this->instance_id;
            $list = $this->auth_group->getSystemUserGroupAll($condition);
            $this->assign('auth_group', $list);
            return view($this->style . 'Auth/addUser');
        }
    }

    /**
     * 修改后台用户
     */
    public function editUser() {
        if (request()->isAjax()) {
            $uid = request()->post('uid', '');
            $admin_name = request()->post('admin_name', '');
            $group_id = request()->post('group_id', '');
            $desc = request()->post('desc', '');
            $user = request()->post('user', '');
            $mobile = request()->post('mobile', '');
            $res = $this->user->checkAdminUser(['uid'=>['neq',$uid],'user_tel'=>$mobile,'website_id'=>$this->website_id,'port'=>'port']);
            if ($uid == '' || $admin_name == '' || $group_id == '' || $user == '') {
                return AjaxReturn(-1006);
            }
            if($res<0){
                $retval = $this->user->editAdminUser($uid, $admin_name, $group_id, $desc, $user,$mobile);
                if ($retval) {
                    $this->addUserLog('修改后台用户', $admin_name);
                }
                return AjaxReturn($retval);
            }else{
                return AjaxReturn(-2);
            }
        } else {
            $uid = request()->get('uid', 0);
            if ($uid == 0) {
                $this->error("没有获取到用户信息");
            }
            $ua_info = $this->user->getAdminUserInfo("uid = " . $uid, $field = "*");
            $ua_info['admin_name'] = substr($ua_info['admin_name'], strpos($ua_info['admin_name'], ':') + 1);
            $this->assign("ua_info", $ua_info);
            $condition["instance_id"] = $this->instance_id;
            $list = $this->auth_group->getSystemUserGroupAll($condition);
            $this->assign('auth_group', $list);
            return view($this->style . 'Auth/editUser');
        }
    }

    /**
     * 删除系统用户组
     */
    public function deleteSystemUserGroup() {
        $group_id = request()->post('group_id', '');
        if (!is_numeric($group_id)) {
            return AjaxReturn(-1006);
        }
        $retval = $this->auth_group->deleteSystemUserGroup($group_id);
        if ($retval) {
            $this->addUserLog('删除系统用户组', $group_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 用户的 锁定
     */
    public function userLock() {
        $uid = request()->post('uid', 0);
        if ($uid > 0) {
            $retval = $this->user->userLock($uid);
        }
        if ($retval) {
            $this->addUserLog('用户锁定', $uid);
        }
        return AjaxReturn($retval);
    }

    /**
     * 解锁
     */
    public function userUnlock() {
        $uid = request()->post('uid', 0);
        if ($uid > 0) {
            $retval = $this->user->userUnlock($uid);
        }
        if ($retval) {
            $this->addUserLog('用户解锁', $uid);
        }
        return AjaxReturn($retval);
    }

    /**
     * 重置密码
     *
     * @return unknown[]
     */
    public function resetUserPassword() {
        $uid = request()->post('uid', 0);
        if ($uid > 0) {
            $retval = $this->user->resetUserPassword($uid);
        }
        if ($retval) {
            $this->addUserLog('重置密码', $uid);
        }
        return AjaxReturn($retval);
    }

    /**
     * 删除 后台会员
     */
    public function deleteAdminUserAjax() {
        if (request()->isAjax()) {
            $uid = request()->post("uid", "");
            if (!empty($uid)) {
                $res = $this->user->deleteAdminUser($uid);
            }
            return AjaxReturn($res);
        }
    }

}
