<?php
namespace data\service;

use data\model\AdminUserModel as AdminUserModel;
use data\model\AuthGroupModel;
use data\model\ModuleModel as ModuleModel;
use data\service\User as User;
use data\model\AdminUserViewModel as AdminUserViewModel;
use \think\Session as Session;
use data\model\UserLogModel;
use data\model\UserModel;
use data\model\SysAddonsModel;
class AdminUser extends User
{

    function __construct()
    {
        parent::__construct();
        $this->admin_user = new AdminUserModel();
    }

    /**
     * 获取权限列表(non-PHPdoc)
     *
     * @see \data\api\IAdmin::getchildModuleQuery()
     */
    public function getchildModuleQuery($moduleid)
    {
        $client_model = \think\Request::instance()->module();
        $module_list = Session::get($client_model.'module_list.module_list_' . $moduleid);
        if (empty($module_list) || $module_list == false) {
            $auth_group = new ModuleModel();
            if ($this->is_admin) {
                $list = $auth_group->getAuthList($moduleid);
                $new_list = $list;
            } else {
                $addons = new SysAddonsModel();
                $list = $auth_group->getAuthList($moduleid); 
                $module_id_array = explode(',', $this->module_id_array);
                $new_list = array();
                $addons_sign_module = Session::get('addons_sign_module')?:[];
                if ($moduleid != 0) {
                    foreach ($list as $k => $v) {
                        if (in_array($list[$k]['module_id'], $module_id_array) && !in_array($list[$k]['module_id'], $addons_sign_module)) {
                            $new_list[] = $list[$k];
                        }
                    }
                } else {
                    $addons_sign_module = Session::get('addons_sign_module')?:[];
                    foreach ($list as $k => $v) {
                        $v['no_addons'] = 0;
                        $v['up_status'] = 0;
                        $v['is_value_add'] = 0;
                        $v['addons_type'] = 0;
                        $check_module_id = $auth_group->getModuleIdByModule($v['controller'], $v['method']);
                        $check_auth = $this->checkAuth($check_module_id['module_id']);
                        if ($check_auth == 0) {
                            $sub_menu = $this->getchildModuleQuery($v['module_id']);
                            if (! empty($sub_menu[0])) {
                                $v['url'] = $sub_menu[0]['url'];
                            }
                        }
                        if (in_array($v['module_id'], $module_id_array) && !in_array($v['module_id'],$addons_sign_module)) {
                            $new_list[] = $v;
                        }elseif($v['addons_sign']){
                            $v['no_addons'] = 1;
                            $addonsInfo = $addons->getInfo(['id' => $v['addons_sign']],'up_status,is_value_add');
                            $v['up_status'] = isset($addonsInfo['up_status'])?$addonsInfo['up_status']:0;
                            $v['is_value_add'] = isset($addonsInfo['is_value_add'])?$addonsInfo['is_value_add']:0;
                            if($addonsInfo['is_value_add'] && $addonsInfo['up_status']!=2){
                                $v['addons_type'] = 1;
                            }elseif($addonsInfo['up_status']==2){
                                $v['addons_type'] = 3;
                            }else{
                                $v['addons_type'] = 2;
                            }
                            $new_list[] = $v;
                        }
                    }
                }
            }
            if (config('app_debug')) {
                Session::set('module_list.module_list_' . $moduleid, $new_list);
                return $new_list;
            } else {
                $arrange_list = array();
                foreach ($new_list as $k => $v) {
                    if ($v['is_dev'] == 0) {
                        $arrange_list[] = $new_list[$k];
                    }
                }
                Session::set($client_model.'module_list.module_list_' . $moduleid, $arrange_list);
                return $arrange_list;
            }
        } else {
            return $module_list;
        }
    }

    /**
     * 后台操作用户列表(non-PHPdoc)
     *
     * @see \data\api\IAdmin::adminUserList()
     */
    public function adminUserList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $admin_user = new AdminUserViewModel();
        $res = $admin_user->getAdminUserViewList($page_index, $page_size, $condition, $order);
        return $res;
    }
    /*
     * 修改用户状态
     * **/
    public function changeUserStatus($uid, $user_status){
        $user_mdl = new userModel();
        $res['user_status'] = $user_status;
        $bool = $user_mdl->where(['uid'=>$uid])->update($res);
        return $bool;
    }

    /**
     * 添加后台用户
     */
    public function addAdminUser($user_name, $group_id, $user_password, $desc, $user, $instance_id = 0, $port = 'master', $mobile='', $qq = '')
    {
        $uid = $this->add($user_name, $user_password, '', $mobile, 1, 0,'', $qq, '', '', '', $instance_id, $this->website_id, $port);

        if ($uid <= 0) {
            return $uid;
        }
        $data_admin = array(
            'uid' => $uid,
            'admin_name' => $user_name,
            'group_id_array' => $group_id,
            'admin_status' => 1,
            'desc' => $desc,
            'user' => $user,
            'website_id'=>$this->website_id
        );
        $this->admin_user->save($data_admin);
        return $uid;
    }
    /**
     * 删除单个用户
     * @param unknown $uid
     */
    public function deleteAdminUser($uid)
    {
        $admin_user_info = $this->admin_user->getInfo(['uid' => $uid]);
        $user_model = new UserModel();
        $user_info = $user_model->getInfo(['uid' => $uid], 'instance_id');
        if(empty($user_info)|| $user_info['instance_id'] != $this->instance_id)
        {
            return NO_AITHORITY;
        }
        if($admin_user_info['is_admin'] == 0)
        {
            $retval = $this->admin_user->destroy($uid);
            $user_model->destroy($uid);
            return $retval;
        }else{
            return 0;
        }
      
        
    }
    /**
     * 验证子账号手机号是否存在
     */
    public function checkAdminUser($condition)
    {
        $user = new UserModel();
        $admin_user_info = $user->getInfo($condition, $field = "*");
        if($admin_user_info){
            return 1;
        }else{
            return -2;
        }
    }
    /**
     * 验证子账号手机号是否存在
     */
    public function selectAdminUser($mobile,$website_id)
    {
        $user = new UserModel();
        $admin_user_info = $user->getInfo(['user_tel'=>$mobile,'port'=>'platform','website_id'=>['neq',$website_id]], "website_id");
        if($admin_user_info){
            return 1;
        }else{
            return [];
        }
    }
    /**
     * 获取单个后台用户信息
     */
    public function getAdminUserInfo($condition, $field = "*")
    {
        $admin_user_info = $this->admin_user->getInfo($condition, $field = "*");
        $user = new UserModel();
        $user_info = $user->getInfo($condition,'user_password,user_tel,user_qq');
        $group = new AuthGroupModel();
        $groupInfo = $group->getInfo(['group_id'=>$admin_user_info['group_id_array']],'group_name, is_system,module_id_array');
        $admin_user_info ['group_name']= $groupInfo['group_name'];
        $admin_user_info ['user_password'] = $user_info['user_password'];
        $admin_user_info ['mobile'] = $user_info['user_tel'];
        $admin_user_info ['user_qq'] = $user_info['user_qq'];
        $admin_user_info ['is_system'] = $groupInfo['is_system'];
        $admin_user_info ['module_id_array'] = $groupInfo['module_id_array'];
        return $admin_user_info;
    }

    /**
     * 编辑后台用户
     */
    public function editAdminUser($uid, $user_name, $group_id, $desc, $user, $mobile = '', $qq = '')
    {
        $res = $this->ModifyUserName($uid, $user_name);
        if ($res) {
            $data1 = array(
                'user_tel' => $mobile,
                'user_qq' => $qq,
            );
            $userModel = new UserModel();
            $userModel->save($data1,['uid'=>$uid]);
            $data = array(
                'admin_name' => $user_name,
                'group_id_array' => $group_id,
                'admin_status' => 1,
                'desc' => $desc,
                'user' => $user
            );
            $res = $this->admin_user->save($data, [
                "uid" => $uid
            ]);
        }
        return $res;
    }

    /*
     * (non-PHPdoc)
     * @see \ata\api\IAdmin::getUserLogList()
     */
    public function getUserLogList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        // TODO Auto-generated method stub
        $user_log = new UserLogModel();
        $list = $user_log->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
    }
    
    /**
     * (non-PHPdoc)
     * @see \ata\api\IAdmin::getAdminUserCountByGroupIdArray()
     */
    public function getAdminUserCountByGroupIdArray($condition){
        $admin_user = new AdminUserViewModel();
        $num = $admin_user->getAdminUserViewCount($condition);
        return $num;
    }
    
    /*
     * 设置手机号密码
     */
    public function setAccount($password = '', $user_tel = ''){
        if(!$this->uid){
            return NO_LOGIN;
        }
        $user = new UserModel();
        $checkHasTel = $user->getInfo(['user_tel' => $user_tel],'uid');
        if($checkHasTel){
            return USER_MOBILE_REPEAT;
        }
        $userInfo = $user->getInfo(['uid' => $this->uid],'user_tel');
        if(!$userInfo || $userInfo['user_tel']){
            return false;
        }
        $res = $user->save(['user_tel' => $user_tel, 'user_password' => md5($password)],['uid' => $this->uid]);
        return $res;
    }
    
}