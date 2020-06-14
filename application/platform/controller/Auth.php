<?php
namespace app\platform\controller;
/**
 * 用户权限控制器
 */
use data\model\AdminUserViewModel;
use data\model\WebSiteModel;
use data\service\AuthGroup as AuthGroup;

class Auth extends BaseController
{

    private $auth_group;

    public function __construct()
    {
        parent::__construct();
        $this->auth_group = new AuthGroup();
    }

    /**
     * 子账号列表列表
     */
    public function userList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
            $condition = array(
                'sur.instance_id' => $this->instance_id,
                'su.website_id' => $this->website_id,
                'sur.user_name|sua.uid|sua.user' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
            $user_list = $this->user->adminUserList($page_index, $page_size, $condition);
            return $user_list;
        } else {
            return view($this->style . 'Auth/userList');
        }
    }
    /*
     * 修改用户是否启用
     * **/
    public function changeUserStatus()
    {
        $user_status = request()->post('user_status', 0);
        $uid = request()->post('uid', 0);
        $bool = $this->user->changeUserStatus($uid,$user_status);
        if($bool){
            $this->addUserLogByParam('修改用户是否启用', '用户id：'.$uid);
        }
        return AjaxReturn($bool);
    }
    /**
     * 添加 子账号
     */
    public function addUser()
    {
        if (request()->isAjax()) {
            $admin_name = request()->post('admin_name', '');
            $group_id = request()->post('group_id', '');
            $user_password = request()->post('user_password', '123456');
            $user = request()->post('user', '');
            $mobile = request()->post('mobile', '');
            $check = $this->user->selectAdminUser($mobile,$this->website_id);
            if($check){
                return AjaxReturn(-3);
            }
            $res = $this->user->checkAdminUser(['user_tel'=>$mobile,'website_id'=>$this->website_id,'port'=>'platform']);
            if($res<0){
                $retval = $this->user->addAdminUser($admin_name, $group_id, $user_password,'', $user, $this->instance_id,'platform',$mobile);
                if($retval){
                    $this->addUserLogByParam('添加后台用户', $retval);
                }
                return AjaxReturn($retval);
            }else{
                return AjaxReturn(-2);
            }
        } else {
            $condition["instance_id"] = $this->instance_id;
            $condition["website_id"] = $this->website_id;
            $list = $this->auth_group->getSystemUserGroupAll($condition);
            $this->assign('auth_group', $list);
            return view($this->style . 'Auth/addUser');
        }
    }

    /**
     * 修改 子账号
     */
    public function editUser()
    {
        if (request()->isAjax()) {
            $uid = $_POST['uid'];
            $admin_name = $_POST['admin_name'];
            $group_id = $_POST['group_id'];
            $user = $_POST['user'];
            $mobile = request()->post('mobile', '');
            $check = $this->user->selectAdminUser($mobile,$this->website_id);
            if($check){
                return AjaxReturn(-3);
            }
            $res = $this->user->checkAdminUser(['uid'=>['neq',$uid],'user_tel'=>$mobile,'website_id'=>$this->website_id,'port'=>'platform']);
            if($res<0){
                $retval = $this->user->editAdminUser($uid, $admin_name, $group_id, '', $user,$mobile);
                if($retval){
                    $this->addUserLogByParam('添加后台用户', $retval);
                }
                return AjaxReturn($retval);
            }else{
                return AjaxReturn(-2);
            }
        } else {
            $uid = isset($_GET['uid']) ? $_GET['uid'] : 0;
            if ($uid == 0) {
                $this->error("没有获取到用户信息");
            }
            $user_info = $this->user->getAdminUserInfo("uid = " . $uid, $field = "*");
            $user_info['admin_name'] = substr($user_info['admin_name'],strpos($user_info['admin_name'],':')+1);
            $this->assign("user_info", $user_info);
            $condition["instance_id"] = $this->instance_id;
            $condition["website_id"] = $this->website_id;
            $list = $this->auth_group->getSystemUserGroupAll($condition);
            $this->assign('auth_group', $list);
            return view($this->style . 'Auth/editUser');
        }
    }
    /**
     * 验证子账号手机号是否存在
     */
    public function checkUserMobile($mobiles)
    {

        if($mobiles){
            $mobile = $mobiles;
        }else{
            $mobile = $_POST['mobile'];
        }
        $condition['user_tel'] = $mobile;
        $condition['website_id'] = $this->website_id;
        $condition['port'] = 'platform';
        $retval = $this->user->checkAdminUser($condition);
        return AjaxReturn($retval);
    }
    /**
     * 删除 子账号
     */
    public function delUser()
    {
        if (request()->isAjax()) {
            $uid = $_POST['uid'];
            $retval = $this->user->deleteAdminUser($uid);
            if($retval){
                $this->addUserLogByParam('删除后台用户', $uid);
            }
            return AjaxReturn($retval);
        }
    }


    /**
     * 角色列表
     */
    public function authGroupList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $list = $this->auth_group->getSystemUserGroupList($page_index, $page_size, [
                'instance_id' => $this->instance_id,
                'website_id' => $this->website_id
            ]);
            return $list;
        } else {
            $permissionList = $this->website->getInstanceModuleQuery();
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
            return view($this->style . 'Auth/roleList');
        }
    }
    /**
     * 添加角色
     */
    public function addAuthGroup()
    {
        if (request()->isAjax()) {
            $group_name = isset($_POST['group_name']) ? $_POST['group_name'] : '';
            $memo = isset($_POST['memo']) ? $_POST['memo'] : '';
            $module_id_array = isset($_POST['select_box']) ? $_POST['select_box'] : '';
            $retval = $this->auth_group->addSystemUserGroup($group_name,$memo, false, $module_id_array, '');
            if($retval){
                $this->addUserLogByParam('添加用户组', $retval);
            }
            return AjaxReturn($retval);
        }
        // 获取权限
        $p = $this->getPermissionList();
        $this->assign("list", $p);
        return view($this->style . "Auth/addRole");
    }
    
    
    /**
     * 编辑角色
     */
    public function updateAuthGroup()
    {
        if (request()->isAjax()) {
            $group_id = isset($_POST['group_id']) ? $_POST['group_id'] : 0;
            $memo = isset($_POST['memo']) ? $_POST['memo'] :'';
            $group_name = isset($_POST['group_name']) ? $_POST['group_name'] : '';
            $module_id_array = isset($_POST['select_box']) ? $_POST['select_box'] : '';
            $res = $this->auth_group->updateSystemUserGroup($group_id, $group_name,$memo, 1, false, $module_id_array, '');
            if($res){
                $this->addUserLogByParam('编辑用户组', $res);
            }
            return AjaxReturn($res);
        }
        $group_id = isset($_GET['group_id']) ? $_GET['group_id'] : '';
        $info = $this->auth_group->getSystemUserGroupDetail($group_id);
        $this->assign('info', $info);
        // 获取权限
        $p = $this->getPermissionList();
        $this->assign("list", $p);
        return view($this->style . "Auth/updateRole");
    }
    /**
     * 查询权限列表
     *
     * @return unknown[][]
     */
    public function getPermissionList()
    {
        // 查询权限
        $permissionList = $this->website->getSystemModuleList(1, 0, [
            'module' => \think\Request::instance()->module()
        ],'sort')['data'];
        // 查询版本权限
        $websiteVersionRoot = $this->website->getWebsiteVersionRoot();
        if($websiteVersionRoot){
            $permissionList = $websiteVersionRoot;
        }
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
        return $p;
    }

    /**
     * 删除角色
     */
    public function deleteSystemUserGroup()
    {
        $group_id = isset($_POST['group_id']) ? $_POST['group_id'] : '';
        $retval = $this->auth_group->deleteSystemUserGroup($group_id);
        if($retval){
            $this->addUserLogByParam('删除系统用户组', $retval);
        }
        return AjaxReturn($retval);
    }

    /**
     * 子账号 停用
     */
    public function userLock()
    {
        $uid = isset($_POST['uid']) ? $_POST['uid'] : 0;
        if ($uid > 0) {
            $res = $this->user->userLock($uid);
        }
        if($res){
            $this->addUserLogByParam('用户的锁定', $res);
        }
        return AjaxReturn($res);
    }

    /**
     * 子账号 解锁
     */
    public function userUnlock()
    {
        $uid = isset($_POST['uid']) ? $_POST['uid'] : 0;
        $user_info = $this->user->getUserInfo();
        $check = $this->user->selectAdminUser($user_info['user_tel'],$this->website_id);
        if($check){
            return AjaxReturn(-3);
        }
        if ($uid > 0) {
            $res = $this->user->userUnlock($uid);
        }
        return AjaxReturn($res);
    }

    /**
     * 修改密码
     *
     * @return unknown[]
     */
    public function resetUserPassword()
    {
        $pass_new = isset($_POST['pass_new']) ? $_POST['pass_new'] : '';
        $pass = isset($_POST['pass']) ? $_POST['pass'] : '';
        $res = $this->user->updateUserPassword($pass_new,$pass);
        return $res;
    }


    /**
     * 修改会员 用户名和商家名称
     *
     * @return unknown[]
     */
    public function modifyUserName()
    {
        $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : '';
        $res = $this->user->ModifyUserName($this->website_id, $user_name);
        return AjaxReturn($res);
    }

}