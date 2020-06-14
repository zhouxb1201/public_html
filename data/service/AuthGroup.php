<?php
namespace data\service;
use data\model\ModuleModel;
use data\service\BaseService as BaseService;
use data\model\AuthGroupModel as AuthGroupModel;
use data\model\AdminUserModel;
class AuthGroup extends BaseService
{
    private $authgroup;
    public function __construct(){
        parent::__construct();
        $this->authgroup = new AuthGroupModel();
    }
    /**
     * 获取系统用户组
     * @param unknown $where
     * @param unknown $order
     * @param unknown $page_size
     * @param unknown $page_index
     */
    public function getSystemUserGroupList($page_index=1, $page_size=0, $condition='', $order='', $field = "*"){
       
        $list = $this->authgroup->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }
    
    /**
     * 添加系统用户组
     * @see \data\api\IAuthGroup::addSystemUserGroup()
     */
    public function addSystemUserGroup($group_name,$memo, $is_system, $module_id_array, $desc){
        
        $count = $this->authgroup->getCount(['group_name' => $group_name,'instance_id'=> $this->instance_id,'website_id' => $this->website_id]);
        if($count > 0)
        {
            return USER_GROUP_REPEAT;
        }
        $shop_module_id_array = $this->authgroup->getFirstData(['instance_id' => $this->instance_id,'website_id' => $this->website_id],'group_id asc');
        if($shop_module_id_array){
            $shop_module_id_array = $shop_module_id_array['shop_module_id_array'];
        }
        $data = array(
            'group_name' => $group_name,
            'memo' => $memo,
            'instance_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'is_system' => $is_system,
            'module_id_array' => $module_id_array,
            'shop_module_id_array' => $shop_module_id_array,
            'desc' => $desc,
            'create_time' => time()
        );
        $res = $this->authgroup->save($data);
        return $res;
    }
    
    /**
     * 修改系统用户组
     * @see \data\api\IAuthGroup::updateSystemUserGroup()
     */
    public function updateSystemUserGroup($group_id, $group_name,$memo, $group_status, $is_system, $module_id_array, $desc){
        $group_info = $this->authgroup->getInfo(['group_id' => $group_id], '*');
        if($group_name != $group_info['group_name'])
        {
            $count = $this->authgroup->getCount(['group_name' => $group_name, 'instance_id'=> $this->instance_id,'website_id' => $this->website_id]);
            if($count > 0)
            {
                return USER_GROUP_REPEAT;
            }
        }
        if($group_info['instance_id'] != $this->instance_id)
        {
            return USER_GROUP_REPEAT;
        }
        $data = array(
            'group_name' => $group_name,
            'memo' => $memo,
            'group_status' => $group_status,
            'is_system' => $is_system,
            'module_id_array' => $module_id_array,
            'desc' => $desc,
            'modify_time' => time()
        );
        $res = $this->authgroup->save($data,['group_id' => $group_id]);
        return $res;
    }
    
    /**
     * 修改系统用户组的状态
    */
    public function ModifyUserGroupStatus($group_id, $group_status){
        $data = array(
            'group_status' => $group_status
        );
        $res = $this->authgroup->save($data, ['group_id' => $group_id]);
        return $res;
    }  
    /**
     * 删除系统用户组
     */ 
    public function deleteSystemUserGroup($group_id){
        $count = $this->getUserGroupIsUse($group_id);
        if($count > 0)
        {
            return USER_GROUP_ISUSE;
        }else{
            $group_info = $this->authgroup->getInfo(['group_id' => $group_id], 'instance_id');
            if(empty($group_info) || $group_info['instance_id'] != $this->instance_id)
            {
                return NO_AITHORITY;
            }
            $res = $this->authgroup->where('group_id',$group_id)->delete();
            return $res;
        }
      
    }
    /**
     * 获取权限使用数量（0表示未使用）
     * @param unknown $group_id
     * @return unknown
     */
    private function getUserGroupIsUse($group_id)
    {
        $user_admin = new AdminUserModel();
        $count = $user_admin->getCount(['group_id_array' => $group_id]);
        return $count;
    }
    /**
     * {@inheritDoc}
     * @see \ata\api\IAuthGroup::getSystemUserGroupDetail()
     */
    public function getSystemUserGroupDetail($group_id){
        return $this->authgroup->get($group_id);
    }
	/* (non-PHPdoc)
     * @see \ata\api\IAuthGroup::getSystemUserGroupAll()
     */
    public function getSystemUserGroupAll($where)
    {
        // TODO Auto-generated method stub
        $all = $this->authgroup->all($where);
        return $all;
    }
    public function checkMethod($method){
      $authModel = new ModuleModel();
      $auth = $authModel->getInfo(['method'=>$method],'is_control_auth')['is_control_auth'];
      return $auth;
    }

}