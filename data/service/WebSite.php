<?php
namespace data\service;
use addons\helpcenter\model\VslQuestionModel;
use data\model\SysAddonsModel;
use data\model\VslAccountModel;
use data\model\VslMemberLevelModel;
use data\service\BaseService as BaseService;
use data\model\WebSiteModel as WebSiteModel;
use data\model\ModuleModel as ModuleModel;
use data\model\AuthGroupModel as AuthGroupModel;
use data\model\InstanceModel as InstanceModel;
use data\model\InstanceTypeModel;
use data\model\MerchantVersionModel;
use data\model\UserModel;
use data\model\AdminUserModel;
use think\Session;
use data\model\SysUrlRouteModel;
use data\service\User as User;
use addons\shop\model\VslShopModel as VslShopModel;
use think\Cache;
use data\model\ConfigModel as ConfigModel;
use data\model\MerchantVersionLogModel;
use data\model\VslIncreMentOrderModel;
use data\service\Merchant as MerchantService;
class WebSite extends BaseService
{

    private $website;

    private $module;
    
    private $config_module;

    public function __construct()
    {
        parent::__construct();
        $this->website = new WebSiteModel();
        $this->module = new ModuleModel();
        $this->config_module = new ConfigModel();
    }

    /**
     * 基本设置
     * @param array $website_data
     * @return int $res
     */
    public function updateWebSite($website_data=array())
    {
        $this->website = new WebSiteModel();
        $res = $this->website->save($website_data, ['website_id' => $this->website_id]);
        if ($res) {
            Cache::rm('WEBSITEINFO'.$this->website_id);
        }
        return $res;
    }
    /**
     * @param string $website_id
     * 获取网站信息
     */
    public function getWebSiteInfo($website_id = '')
    {
        if (empty($website_id)){
            $website_id = $this->website_id;
        }
        $info = $this->website->getInfo(['website_id'=>$website_id],'*');
        $instance_type = new MerchantVersionModel();
        $merchant_versionid = $info['merchant_versionid'];
        $user = new UserModel();
        $user_info = $user->getInfo(['uid'=>$info['uid']],'user_tel,user_name');
        $info['user_tel'] =  $user_info['user_tel'];
        $info['version'] = $instance_type->getInfo(['merchant_versionid'=>$merchant_versionid],'type_name')['type_name'];
        $article = getAddons('helpcenter', $this->website_id) ? new VslQuestionModel() : '';
        $info['shop_validity_time'] = $info['shop_validity_time'] ? date('Y-m-d', $info['shop_validity_time']) : '';
        $info['pur_title'] = getAddons('helpcenter', $this->website_id) ? $article->getInfo(['question_id' => $info['pur_id']], 'title')['title'] : '';
        $info['reg_title'] = getAddons('helpcenter', $this->website_id) ? $article->getInfo(['question_id' => $info['reg_id']], 'title')['title'] : '';
        $info['realm_ips'] = $this->website->Query([],'realm_two_ip');
        $info['realm_two_ip_all'] = $info['realm_two_ip'].'.'.top_domain($_SERVER['SERVER_NAME']);
        $info['shop_create_time'] =  $info['create_time'] ? date('Y-m-d', $info['create_time']) : '';
//        cache('WEBSITEINFO'.$this->website_id, $info);
        return $info;
    }
    /**
     * 域名设置
     */
    public function updateRealmWebSite($data=array())
    {
        $website = new WebSiteModel();
        $oldInfo = $this->getWebSiteInfo();
        //更换域名，删除旧的配置文件
        if($oldInfo['conf'] && $oldInfo['conf'] != $data['conf']){
            shell_exec("rm /www/wdlinux/nginx-1.8.1/conf/vhost/" . $oldInfo['conf']);
        }
        if($oldInfo['realm']['ssl'] && $oldInfo['realm']['ssl'] != $data['ssl']){
            shell_exec("rm /www/wdlinux/nginx-1.8.1/conf/cert/" . $oldInfo['realm']['ssl']);
        }
        if($oldInfo['realm']['sslkey'] && $oldInfo['realm']['sslkey'] != $data['sslkey']){
            shell_exec("rm /www/wdlinux/nginx-1.8.1/conf/cert/" . $oldInfo['realm']['sslkey']);
        }
        $savedata = array(
            'website_id' => $this->website_id,
            'realm_ip' => $data['realm_ip'],
            'conf' => $data['conf'],
        );
        
        $res = $website->save($savedata, [
            'website_id' => $this->website_id
        ]);
        $info = array(
            'realm_ip' => $data['realm_ip'],
            'ssl' => $data['ssl'],
            'sslkey' => $data['sslkey']
        );
        $value = json_encode($info);
        $count = $this->config_module->where([
            'key' => 'REALMIP',
            'instance_id' => 0,
            'website_id' => $this->website_id
        ])->count();
        if ($count > 0) {
            $data = array(
                'value' => $value,
                'is_use' => 1,
                'modify_time' => time()
            );
            $res = $this->config_module->where([
                'key' => 'REALMIP',
                'instance_id' => 0,
                'website_id' => $this->website_id
            ])->update($data);
        } else {
            $data = array(
                'instance_id' => 0,
                'website_id' => $this->website_id,
                'key' => 'REALMIP',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $this->config_module->save($data);
        }
        return $res;
    }
    /*
     * (non-PHPdoc)
     * @see \data\api\IWebsite::getWebsiteCount()
     */
    public function getWebsiteCount($condition)
    {
        // TODO Auto-generated method stub
        $website = new WebSiteModel();
        $website_list = $website->getQuery($condition, 'count(website_id) as count', '');
        return $website_list[0]['count'];
    }
    /*
     * (non-PHPdoc)
     * @see \data\api\IWebsite::getWebsiteVersionCount()
     */
    public function getWebsiteVersionCount($condition)
    {
        // TODO Auto-generated method stub
        $website = new WebSiteModel();
        $count = $website->getCount($condition);
        return $count;
    }


    /**
     * (non-PHPdoc)
     * @see \data\api\IWebsite::updateWebSiteByMaster()
     */
    function updateWebSiteByMaster($merchant_versionid, $website_id, $shop_status, $shop_validity_time, $related_sales, $related_operating) {
        if (!$website_id) {
            return 0;
        }
        $unsetlog = false;
        $website = new WebSiteModel();
        $check = $website->getInfo(['website_id' => $website_id]);
        if($merchant_versionid == $check['merchant_versionid'] && $shop_validity_time == $check['shop_validity_time']){
            $unsetlog = true;//版本和到期时间没有变化，不需要插入版本变更记录
        }
        $website->startTrans();
        try {
            $merchantVersion = new MerchantVersionModel();
            $data = array(
                'merchant_versionid' => $merchant_versionid,
                'shop_status' => $shop_status,
                'shop_validity_time' => $shop_validity_time,
                'related_sales' => $related_sales,
                'related_operating' => $related_operating,
            );
            $res = $website->save($data, [
                'website_id' => $website_id
            ]);
            $web = $website->find([
                'website_id' => $website_id
                    ], 'uid');
            $adminUser = new AdminUserModel();
            $group = $adminUser->find([
                'uid' => $web['uid']
                    ], 'group_id_array');
            $omerchantVersion = $merchantVersion->find([
                'merchant_versionid' => $merchant_versionid
                    ], 'type_module_array,shop_type_module_array');
            if ($omerchantVersion) {
                $auth_group = new AuthGroupModel();
                $auth_group->save([
                    'module_id_array' => $omerchantVersion['type_module_array'],
                    'shop_module_id_array' => $omerchantVersion['shop_type_module_array']
                        ], [
                    'group_id' => $group['group_id_array']
                ]);
            }
            if ($res) {
                cache('WEBSITEINFO', null);
            }
            if(!$unsetlog){
                $merchant = new MerchantService();
                $data = [
                    'merchant_versionid' => $merchant_versionid,
                    'uid' => $this->uid,
                    'type' => 0,
                    'due_time' => $shop_validity_time,
                    'website_id' => $website_id
                ];
                $merchant->addVersionChangeLog($data);
            }
            $website->commit();
            return $website_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $website->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 添加系统模块
     *
     * @see \data\api\IWebsite::addSytemModule()
     */
    public function addSytemModule($module_name, $controller, $method, $pid, $url, $is_menu, $is_dev, $sort, $module_picture, $desc, $icon_class, $is_control_auth,$module,$jump,$isPlatform=0,$isAdmin=0)
    {
        // 查询level
        if ($pid == 0) {
            $level = 1;
        } else {
            $level = $this->getSystemModuleInfo($pid, $field = 'level')['level'] + 1;
        }
        $data = array(
            'module_name' => $module_name,
            'module' => $module,
            'controller' => $controller,
            'method' => $method,
            'pid' => $pid,
            'level' => $level,
            'url' => $url,
            'is_menu' => $is_menu,
            'is_control_auth' => $is_control_auth,
            'is_dev' => $is_dev,
            'sort' => $sort,
            'module_picture' => $module_picture,
            'desc' => $desc,
            'create_time' => time(),
            'icon_class' => $icon_class,
            'jump' => $jump,
        );
        $mod = new ModuleModel();
        $res = $mod->save($data);
        if($isPlatform){
            $user_group = new AuthGroupModel();
            $module_array = $user_group->getInfo(['is_system'=>1,'website_id'=>$this->website_id],'module_id_array')['module_id_array'];
            $module_real_array = $module_array.','.$res;
            $user_group->save(['module_id_array'=>$module_real_array],['is_system'=>1,'website_id'=>$this->website_id]);
        }
        if($isAdmin){
            $user_group = new AuthGroupModel();
            $shop_module_array = $user_group->getInfo(['is_system'=>1,'website_id'=>$this->website_id],'shop_module_id_array')['shop_module_id_array'];
            $shop_module_real_array = $shop_module_array.','.$res;
            $user_group->save(['shop_module_id_array'=>$shop_module_real_array],['is_system'=>1,'website_id'=>$this->website_id]);
        }
        $this->updateUserModule();
        return $res;
    }

    /**
     * 修改系统模块
     *
     * @see \data\api\IWebsite::updateSystemModule()
     */
    public function updateSystemModule($module_id, $module_name, $controller, $method, $pid, $url, $is_menu, $is_dev, $sort, $module_picture, $desc, $icon_class, $is_control_auth,$jump)
    {
        // 查询level
        if ($pid == 0) {
            $level = 1;
        } else {
            $level = $this->getSystemModuleInfo($pid, $field = 'level')['level'] + 1;
        }
        $data = array(
            'module_id' => $module_id,
            'module_name' => $module_name,
            'controller' => $controller,
            'method' => $method,
            'pid' => $pid,
            'level' => $level,
            'url' => $url,
            'is_menu' => $is_menu,
            'is_control_auth' => $is_control_auth,
            'is_dev' => $is_dev,
            'sort' => $sort,
            'module_picture' => $module_picture,
            'desc' => $desc,
            'modify_time' => time(),
            'icon_class' => $icon_class,
            'jump' => $jump
        );
        $mod = new ModuleModel();
        $res = $mod->allowField(true)->save($data, [
            'module_id' => $module_id
        ]);
        $this->updateUserModule();
        return $res;
    }

    /**
     * 删除系统模块
     *
     * @param unknown $module_id            
     */
    public function deleteSystemModule($module_id_array)
    {
        $sub_list = $this->getModuleListByParentId($module_id_array);
        if (! empty($sub_list)) {
            $res = SYSTEM_DELETE_FAIL;
        } else {
            $res = $this->module->destroy($module_id_array);
        }
        $this->updateUserModule();
        return $res;
    }
    /**
     * 清除菜单
     */
    private function updateUserModule(){
        $module = request()->module();
        Session::set('module_list.'.$module.'module_list_0', '');
        $mod = new ModuleModel();
        $module_id_list = $mod->getQuery('', 'module_id', '');
        foreach ($module_id_list as $k => $v)
        {
            Session::set('module_list.'.$module.'module_list_' . $v['module_id'], '');
        }
        
    }

    /**
     * 获取系统模块
     *
     * @param unknown $module_id            
     */
    public function getSystemModuleInfo($module_id, $field = '*')
    {
        $res = $this->module->getInfo(array(
            'module_id' => $module_id
        ), $field);
        return $res;
    }

    /**
     * 修改系统模块 单个字段
     *
     * @param unknown $module_id            
     * @param unknown $order            
     */
    public function ModifyModuleField($module_id, $field_name, $field_value)
    {
        $res = $this->module->ModifyTableField('module_id', $module_id, $field_name, $field_value);
        $this->updateUserModule();
        return $res;
    }

    /**
     * 获取系统模块列表
     *
     * @param unknown $where            
     * @param unknown $order            
     * @param unknown $page_size            
     * @param unknown $page_index            
     */
    public function getSystemModuleList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        // 针对开发者模式处理
        if (! config('app_debug')) {
            if (is_array($condition)) {
                $condition = array_merge($condition, [
                    'is_dev' => 0
                ]);
            } else {
                if (! empty($condition)) {
                    $condition = $condition . ' and is_dev=0 ';
                } else {
                    $condition = 'is_dev=0';
                }
            }
        }
        $res = $this->module->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $res;
    }

    /**
     * 根据当前实例查询权限列表
     *
     * @param unknown $instanceid            
     */
    public function getInstanceModuleQuery()
    {
        // 单用户查询全部
        $condition_module = array(
            'module' => \think\Request::instance()->module(),
            'is_control_auth' => 1
        );
        $moduelList = $this->getSystemModuleList(1, 0, $condition_module,'sort asc');
        return $moduelList['data'];
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IWebsite::addSystemInstance()
     */
    public function addSystemInstance($uid,$instance_name, $type)
    {
        $instance = new InstanceModel();
        $instance->startTrans();
        try {
            $instance_model = new InstanceModel();
            // 创建实例
            $data_instance = array(
                'instance_name' => $instance_name,
                'instance_typeid' => $type,
                'create_time' => time(),
                'website_id'=>$this->website_id
            );
            $instance_model->save($data_instance);
            $instance_id = $instance_model->instance_id;
            // 查询实例权限
            $instance_type_model = new InstanceTypeModel();
            $instance_type_info = $instance_type_model->get($type);
            // 创建管理员组
            $data_group = array(
                'instance_id' => $instance_id,
                'group_name' => '管理员组',
                'is_system' => 1,
                'module_id_array' => $instance_type_info['type_module_array'],
                'create_time' => time(),
                'website_id' => $this->website_id
            );
            $user_group = new AuthGroupModel();
            $user_group->save($data_group);
            // 调整用户属性
            $user = new UserModel();
            $user->save([
                'is_system' => 1,
                'port' => 'admin',
                'instance_id' => $instance_id
            ], [
                'uid' => $uid
            ]);
            // 添加后台用户
            $user_admin = new AdminUserModel();
            $data_admin = array(
                'uid' => $uid,
                'admin_name' => '',
                'group_id_array' => $user_group->group_id,
                'website_id'=>$this->website_id
            );
            $user_admin->save($data_admin);
            $instance->commit();
            return $instance_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $instance->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 修改系统实例
     */
    public function updateSystemInstance()
    {}

    /**
     * 获取系统实例
     *
     * @param unknown $instance_id            
     */
    public function getSystemInstance($instance_id)
    {
        $instance = new InstanceModel();
        $info = $instance->get($instance_id);
        return $info;
    }

    /**
     * 查询系统实例列表
     *
     * @param unknown $where            
     * @param unknown $order            
     * @param unknown $page_size            
     * @param unknown $page_index            
     */
    public function getSystemInstanceList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $instance = new InstanceModel();
        $instance_list = $instance->pageQuery($page_index, $page_size, $condition, $order, $field);
        if (! empty($instance_list['data'])) {
            foreach ($instance_list['data'] as $k => $v) {
                $instance_type = new InstanceTypeModel();
                $type_name = $instance_type->getInfo([
                    'instance_typeid' => $v['instance_typeid']
                ], 'type_name');
                if (! empty($type_name['type_name'])) {
                    $v['type_name'] = $type_name['type_name'];
                } else {
                    $v['type_name'] = '';
                }
                $instance_list['data'][$k] = $v;
            }
        }
        return $instance_list;
    }

    /**
     * 通过模块和方法查询权限(non-PHPdoc)
     *
     * @see \data\api\IWebsite::getModuleIdByModule()
     */
    public function getModuleIdByModule($controller, $action)
    {
        $res = $this->module->getModuleIdByModule($controller, $action);
        return $res;
    }

    /**
     * 查询权限节点的根节点
     *
     * @param unknown $module_id            
     */
    public function getModuleRoot($module_id)
    {
        $root_id = $this->module->getModuleRoot($module_id);
        return $root_id;
    }

    /**
     * 获取系统模块列表
     *
     * @param string $tpye
     *            0 debug模式 1 部署模式
     */
    public function getModuleListTree($type = 0)
    {
        $list = $this->module->order('pid,sort')->select();
        $new_list = $this->list_tree($list);
        
        return $new_list;
    }

    /**
     * 数组转化为树
     *
     * @param unknown $list            
     * @param string $p_id            
     * @return multitype:boolean
     */
    private function list_tree($list, $p_id = '0')
    {
        $tree = array();
        foreach ($list as $row) {
            if ($row['pid'] == $p_id) {
                $tmp = $this->list_tree($list, $row['module_id']);
                if ($tmp) {
                    $row['sub_menu'] = $tmp;
                } else {
                    $row['leaf'] = true;
                }
                $tree[] = $row;
            }
        }
        Return $tree;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IWebsite::getModuleListByParentId()
     */
    public function getModuleListByParentId($pid)
    {
        $list = $this->getSystemModuleList(1, 0, 'pid=' . $pid,'sort asc');
        return $list['data'];
    }

    /**
     * 获取当前节点的根节点以及二级节点项(non-PHPdoc)
     *
     * @see \ata\api\IWebsite::getModuleRootAndSecondMenu()
     */
    public function getModuleRootAndSecondMenu($module_id)
    {
        $count = $this->module->where([
            'module_id' => $module_id,
            'module' => \think\Request::instance()->module()
        ])
            ->count();
        if ($count == 0) {
            return array(
                0,
                0
            );
        }
        $info = $this->module->getInfo([
            'module_id' => $module_id,
            'module' => \think\Request::instance()->module(),
            'pid' => array(
                'neq',
                0
            )
        ], 'pid, level');
        if (empty($info)) {
            return array(
                $module_id,
                0
            );
        } else {
            if ($info['level'] == 2) {
                return array(
                    $info['pid'],
                    $module_id
                );
            } else {
                $pid = $info['pid'];
                while ($pid != 0) {
                    $module = $this->module->getInfo([
                        'module_id' => $pid,
                        'module' => \think\Request::instance()->module(),
                        'pid' => array(
                            'neq',
                            0
                        )
                    ], 'pid, module_id, level');
                    if ($module['level'] == 2) {
                        $pid = 0;
                        return array(
                            $module['pid'],
                            $module['module_id']
                        );
                    } else {
                        $pid = $module['pid'];
                    }
                }
            }
        }
    }

    /**
     * 获取模板样式(non-PHPdoc)
     *
     * @see \ata\api\IWebsite::getWebStyle()
     */
    public function getWebStyle()
    {
        $config_style = ''; // 根据用户实例从数据库中获取样式，以及项目
        $style = \think\Request::instance()->module() . '/' . $config_style;
        return $style;
    }

    /*
     * (non-PHPdoc)
     * @see \ata\api\IWebsite::getWebDetail()
     */
    public function getWebDetail($website_id=0)
    {
        if($website_id){
            $website = $website_id;
        }else{
            $website = $this->website_id;
        }
        $web_info = $this->website->getInfo(array(
            'website_id' => $website
        ));
        $merchantVersionModel = new MerchantVersionModel();
        $userAdminModel = new AdminUserModel();
        $web_info['merchant_version_name'] = $merchantVersionModel->getInfo([
            'merchant_versionid' => $web_info['merchant_versionid']
        ], 'type_name')['type_name'];
        $web_info['sales'] = $userAdminModel->getInfo([
            'uid' => $web_info['related_sales']
        ], 'user')['user']?:'';
        $web_info['operating'] = $userAdminModel->getInfo([
            'uid' => $web_info['related_operating']
        ], 'user')['user']?:'';
        return $web_info;
    }
    /**
     * (non-PHPdoc)
     * @see \data\api\IWebsite::getUrlRouteList()
     */
    public function getUrlRouteList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $url_route_model = new SysUrlRouteModel();
        $route_list = $url_route_model->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $route_list;
    }
    /**
     * 获取路由
     * @return Ambigous <mixed, \think\cache\Driver, boolean>
     */
    public function getUrlRoute(){
        $cache = Cache::get('url_route');
          if ($cache) {
            return $cache;
        } else {
            $url_route_model = new SysUrlRouteModel();
            $route_list = $url_route_model->pageQuery(1, 0, ['is_open' => 1], '', 'rule,route');
            Cache::set('url_route', $route_list);
            return $route_list;
        }
        
       
    }
    /**
     * (non-PHPdoc)
     * @see \data\api\IWebsite::addUrlRoute()
     */
    public function addUrlRoute($rule, $route, $is_open,$route_model = 1)
    {
        cache('url_route', null);
    }
    /**
     * (non-PHPdoc)
     * @see \data\api\IWebsite::updateUrlRoute()
     */
    public function updateUrlRoute($routeid, $rule, $route, $is_open, $route_model = 1)
    {
        cache('url_route', null);
    }
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::getShopList()
     */
    public function getWebsiteList($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $shop = new WebSiteModel();
        $shop_type = new MerchantVersionModel();
        $list = $shop->getWebsiteList($page_index, $page_size, $where, $order);
        foreach ($list['data'] as $k => $v) {
            $status = $this->updateWebSiteStatus($v['website_id']);
            if($status){
                $list['data'][$k]['shop_status'] = $status;
            }
        }
        return $list;
    }
    public function addWebsite($user_account, $user_pwd, $shop_name, $shop_type,$shop_status,$shop_validity_time,$note,$user_tel,$status = 2,$related_sales = 0,$related_operating = 0,$islk = 0,$ismobile = 0){ //dong:添加[,$islk = 0,$ismobile = 0]
        $shop_model = new WebSiteModel();
        $user_model = new UserModel();
        
        $checkUserRepeat = $user_model->getInfo(['user_tel'=>$user_tel,'port' => 'platform']);
        if($checkUserRepeat){
            return -8005;
        }
        $shop_model->startTrans();
        try {
            $user_service = new User();
            $retval = $user_service->add($user_tel, $user_pwd, '', $user_tel, 1,0, '', '', '', '', '', 0, 0, 'platform');
            if($retval >0){
                $realm_ip = $this->isArray();
                $res = $this->addWeb($shop_name, $shop_type, $retval, $user_account,$shop_status,$shop_validity_time,$note,$realm_ip,$related_sales,$related_operating);
            }
            
            $user_info = $user_model->getInfo(['uid'=>$retval],'*');
            $changeMerchantLog = [
                'merchant_versionid' => $shop_type,
                'pay_type' => 0,
                'pay_money' => '0.00',
                'due_time' => $shop_validity_time,
                'website_id' => $res
            ];//记录版本购买
            if($status==2){
                $changeMerchantLog['uid'] = $retval;
                $changeMerchantLog['type'] = 1;
                $user_service->initLoginInfo($user_info);
            }else{
                $changeMerchantLog['uid'] = $this->uid;
                $changeMerchantLog['type'] = 0;
            }
            if($res){
                if(strpos($_SERVER['SERVER_NAME'],'vslai.com.cn') !==false){
                    $merchant = new Merchant();
                    $merchant->addVersionChangeLog($changeMerchantLog);
                }
            }
            $shop_model->commit();
            return $res;
        }catch (\Exception $e){
            $shop_model->rollback();
            return $e->getMessage();
        }
    }
    public function isArray($realm){
        $realm_ip = 'shop'.rand(100000,999999);
        if(in_array($realm_ip,$realm)){
            $this->isArray($realm);
        }
        return $realm_ip;
    }
    public function addWeb($shop_name, $shop_type, $uid,$user_account,$shop_status,$shop_validity_time,$note,$realm_ip,$related_sales = 0,$related_operating = 0)
    {

        $web = new WebSiteModel();
        $condition = array(
            'uid' => $uid
        );
        $count = $web->getCount($condition);
        // 防止出现重复商户、重复提交问题
        if ($count > 0) {
            return - 1;
        }
        $web->startTrans();
        try {
            
            $data = array(
                'title' => $shop_name,
                'merchant_versionid' => $shop_type,
                'uid' => $uid,
                'create_time' => time(),
                'shop_status' => $shop_status,
                'shop_validity_time' => $shop_validity_time,
                'note' => $note,
                'related_sales' => $related_sales,
                'related_operating' => $related_operating,
                'wap_status' => 1,
                'wap_pop' => 1,
                'wap_pop_adv' => 'https://pic.vslai.com.cn/upload/default/20190423191330.png',
                'wap_pop_rule' => 1
            );
            // 添加商户
            $retval = $web->save($data);
            //更新用户表平台id
            if($retval){
                $member_service = new User();
                $member_service->updateUser($uid,$retval);
                $merchant_version_model = new MerchantVersionModel();
                $instance_type_info = $merchant_version_model->get($shop_type);
                // 创建管理员组
                $data_group = array(
                    'instance_id' => 0,
                    'website_id' => $retval,
                    'group_name' => '管理员组',
                    'is_system' => 1,
                    'module_id_array' => $instance_type_info['type_module_array'],
                    'shop_module_id_array' => $instance_type_info['shop_type_module_array'],
                    'create_time' => time()
                );
                $user_group = new AuthGroupModel();
                $user_group->save($data_group);
                // 添加前台用户默认等级
                $member_level = new VslMemberLevelModel();
                $data_member = array(
                    'level_name' => '默认等级',
                    'is_default'=>1,
                    'create_time' => time(),
                    'website_id' => $retval
                );
                $member_level->save($data_member);
                // 添加后台用户
                $user_admin = new AdminUserModel();
                $data_admin = array(
                    'uid' => $uid,
                    'admin_name' => $user_account,
                    'group_id_array' => $user_group->group_id,
                    'website_id' => $retval,
                    'user' => 'admin'
                );
                $user_admin->save($data_admin);
                $shop = new VslShopModel();
                $data_shop = array(
                    'shop_id' => 0,
                    'uid' => $uid,
                    'shop_name' => '自营店',
                    'shop_create_time' => time(),
                    'shop_state' => 1,
                    'website_id' =>$retval
                );
                // 添加店铺
                $shop->save($data_shop);
             
                $account = new VslAccountModel();
                $data_account = array(
                    'website_id' =>$retval
                );
                // 添加平台账户
                $account->save($data_account);
            }
            $web->commit();
            return $retval;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $web->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \data\api\IWebsite::getWebsiteVersionRoot()
     */
    
    public function getWebsiteVersionRoot(){
        $websiteModel = new WebSiteModel();
        $website = $websiteModel->getInfo(array(
            'website_id' => $this->website_id
        ),'merchant_versionid');
        if(!$website){
            return false;
        }
        $merchantVersionModel = new MerchantVersionModel();
        $merchantVersion = $merchantVersionModel->getInfo(array(
            'merchant_versionid' => $website['merchant_versionid']
        ),'type_module_array');
        if(!$merchantVersion){
            return false;
        }
        $auth_group= new AuthGroupModel();
        $system_auth = $auth_group->getInfo(['is_system'=>1,'instance_id'=>0,'website_id'=>$this->website_id],'order_id,group_id');
        if($system_auth['order_id']){
            $bonus_id = [];
            $unbonus_id = [];
            $module = new ModuleModel();
            $addonsmodel = new SysAddonsModel();
            $module_infoId = $module->getInfo(['method'=>'bonusRecordList','module'=>'platform'],'module_id')['module_id'];
            $area_moduleId = $addonsmodel->getInfo(['name'=>'areabonus'],'module_id')['module_id'];
            $global_moduleId = $addonsmodel->getInfo(['name'=>'globalbonus'],'module_id')['module_id'];
            $team_moduleId = $addonsmodel->getInfo(['name'=>'teambonus'],'module_id')['module_id'];
            $order_ids = explode(',',$system_auth['order_id']);
            $order = new VslIncreMentOrderModel();
            $module_id_arrays = '';
            $default_module_id_array = explode(',',$merchantVersion['type_module_array']);
            foreach ($order_ids as $value){
                $addons_id = $order->getInfo(['order_id'=>$value],'*');
                $module_id = $module->Query(['addons_sign'=>$addons_id['addons_id'],'module'=>'platform'],'module_id');
                if(in_array($global_moduleId,$module_id) || in_array($area_moduleId,$module_id) || in_array($team_moduleId,$module_id)){
                    $bonus_id[] = $module_infoId;
                }
                if($addons_id['expire_time']>time()){
                    $module_id_array = implode(',',$module_id);
                    $module_id_arrays .= ','.$module_id_array;
                    $merchantVersion['type_module_array'] = implode(',',$default_module_id_array).$module_id_arrays;
                }else{
                    if(in_array($global_moduleId,$module_id) || in_array($area_moduleId,$module_id) || in_array($team_moduleId,$module_id)){
                        $unbonus_id[] = $module_infoId;
                    }
                    $default_module_id_array = array_diff($default_module_id_array,$module_id);
                    $merchantVersion['type_module_array'] = implode(',',$default_module_id_array);
                }
            }
            if(count($bonus_id)==count($unbonus_id) && $bonus_id){
                $unid = [];
                $real_module_id_array = explode(',',$merchantVersion['type_module_array']);
                $unid[] = $bonus_id[0];
                $merchantVersion['type_module_array'] = implode(',',array_diff($real_module_id_array,$unid));
            }
        }
        $moduleModel = new ModuleModel();
        $moduleList = $moduleModel->where('module_id in ('.$merchantVersion['type_module_array'].')')->order('sort asc')->select();
        if(!$moduleList){
            return false;
        }
        return $moduleList;
    }
    
    public function updateWebSiteNote($website_id=0,$note=''){
        if($website_id){
            $websited = $website_id;
        }else{
            $websited = $this->website_id;
        }
        $savedata = array(
            'note' =>$note
        );
        $this->website = new WebSiteModel();
        $res = $this->website->save($savedata, [
            'website_id' => $website_id
        ]);
        if ($res) {
            cache('WEBSITEINFO'.$website_id, null);
        }
        return $res;
    }
    
    /*
     * 更新商家使用状态
     */
    public function updateWebSiteStatus($website_id=0){
        if(!$website_id){
            return false;
        }
        $websiteModel = new WebSiteModel();
        $website = $websiteModel->getInfo(array(
            'website_id' => $website_id
        ),'shop_status,shop_validity_time');
        if(!$website){
            return false;
        }
        if(($website['shop_status']==1 && $website['shop_validity_time'] > time() && $website['shop_validity_time'] != 0) || ($website['shop_status']==2 && $website['shop_validity_time'] <= time()) || $website['shop_status']==1 && $website['shop_validity_time']==0){
            return false;
        }
        if($website['shop_validity_time'] > time() || $website['shop_validity_time'] == 0){
            $res =$websiteModel->save(['shop_status'=>1], [
                'website_id' => $website_id
            ]);
            $status = 1;
        }
        if($website['shop_validity_time'] <= time() && $website['shop_validity_time'] != 0){
            $res = $websiteModel->save(['shop_status'=>2], [
                'website_id' => $website_id
            ]);
            $status = 2;
        }
        if ($res) {
            cache('WEBSITEINFO'.$website_id, null);
        }
        return $status;
    }
    
    /*
     * 查询二级域名或者独立域名是否存在
     */
    public function checkRealmIp($realIp,$realTwoIp){
        if(!$realIp){
            return -8010;
        }
        $websiteModel = new WebSiteModel();
        $checkRealIp = $websiteModel->getInfo(['website_id' => ['neq',$this->website_id],'realm_ip' => $realIp]);
        if($checkRealIp){
            return -8007;
        }
        return 1;
    }

    public function updateWebsitePart(array $data){
        $savedata = array(
            'note' =>$data['note'],
            'title' =>$data['title'],
        );
        $this->website = new WebSiteModel();
        $this->website->startTrans();
        try {
            $res = $this->website->save($savedata, [
                'website_id' => $data['website_id']
            ]);
            $userModel = new UserModel();
            $adminUserModel = new AdminUserModel();
            if ($res) {
                cache('WEBSITEINFO'.$data['website_id'], null);
                $userModel->save(['user_name' => $data['user_name']],['uid' => $data['uid']]);
                $adminUserModel->save(['admin_name' => $data['user_name']],['uid' => $data['uid']]);
            }
            $this->website->commit();
            return $res;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $this->website->rollback();
            return $e->getMessage();
        }
    }
    
    /*
     * 获取员工销售数量
     */
    public function saleWebsiteCountByEmployees($condition = []){
        $websiteModel = new WebSiteModel();
        $websiteList = $websiteModel->getQuery($condition, 'website_id', '');
        $data['related_website'] = $websiteModel->getCount($condition);//关联商家
        $data['renewal_website'] = 0;//续费商家，查询版本变更记录
        $data['value_website'] = 0;//增值商家，查询增值应用订购表
        if(!$websiteList){
            return $data;
        }
        $merchantLogModel = new MerchantVersionLogModel();
        $orderLogModel = new VslIncreMentOrderModel();
        foreach($websiteList as $val){
            $merchantLog = $merchantLogModel->getFirstData(['website_id' => $val['website_id']], '');
            if($merchantLog){
                $data['renewal_website']++;
            }
            $orderLog = $orderLogModel->getFirstData(['website_id' => $val['website_id'],'order_type' => 1], '');
            if($orderLog){
                $data['value_website']++;
            }
        }
        return $data;
    }
    
    /*
     * 增加论坛用户
     */

    public function addUcMember($user_tel, $password) {
        if (!$user_tel || !$password) {
            return false;
        }
        $data['user_name'] = $user_tel;
        $data['password'] = $password;
        $data['type'] = 'add';
        $url = 'http://bbs.vslai.com.cn/addorupdate.php';
        $res = $this->post_curls($url, $data); //返回json
        $result = json_decode($res, true);
        if($result['code'] != 1){
            return false;
        }
        return $result['result'];
    }
    
    
    public function getIp()
    {
        $ip = '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ip_arr = explode(',', $ip);
        return $ip_arr[0];
    }
    
    /*
     * 增加crm用户
     */

    public function addCRMMember($user_tel, $user_name, $user_pwd) {
        if (!$user_tel || !$user_name) {
            return false;
        }
        $data['name'] = $user_name;
        $data['phoneNumber'] = $user_tel;
        $data['username'] = $user_name;
        $data['usertype'] = 1;
        $data['ip'] = $this->getIp();
        $data['port'] = '新系统';
        $data['ismobile'] = 0;
        $data['password'] = $user_pwd;
        $url = 'http://shop.vslai.com/getmessage.php';
        $res = $this->post_curls($url, $data); //返回json
        $result = json_decode($res, true);
        if($result['user']){
            return true;
        }
        return false;
    }
    /*
     * 修改论坛用户密码
     */

    public function updateUcMemberPassword($ucid, $oldpw, $newpw) {
        if (!$ucid || !$oldpw || !$newpw) {
            return false;
        }
        $data['ucid'] = $ucid;
        $data['oldpw'] = $oldpw;
        $data['newpw'] = $newpw;
        $data['type'] = 'update';
        $url = 'http://bbs.vslai.com.cn/addorupdate.php';
        $res = $this->post_curls($url, $data); //返回json
        $result = json_decode($res, true);
        if($result['code'] != 1){
            return false;
        }
        return $result['result'];
    }
    /*
     * 重置论坛用户密码
     */

    public function resetUcMemberPassword($ucid, $newpw) {
        if (!$ucid || !$newpw) {
            return false;
        }
        $data['ucid'] = $ucid;
        $data['newpw'] = $newpw;
        $data['type'] = 'reset';
        $url = 'http://bbs.vslai.com.cn/addorupdate.php';
        $res = $this->post_curls($url, $data); //返回json
        $result = json_decode($res, true);
        if($result['code'] != 1){
            return false;
        }
        return $result['result'];
    }
    /*
     * 论坛用户登陆
     */

    public function ucMemberLogin($user_name, $password) {
        if (!$user_name || !$password) {
            return false;
        }
        $data['user_name'] = $user_name;
        $data['password'] = $password;
        $data['type'] = 'login';
        $url = 'http://bbs.vslai.com.cn/addorupdate.php';
        $res = $this->post_curls($url, $data); //返回json
        $result = json_decode($res, true);
        if($result['code'] != 1){
            return false;
        }
        return $result['result'];
    }

    /**
     * POST请求https接口返回内容
     * @param  string $url [请求的URL地址]
     * @param  string $post [请求的参数]
     * @return  string
     */
    public function post_curls($url, $post)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $res = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $res; // 返回数据，json格式
    }
    
    public function addDefaultGoods($website_id = 0){
        if(!$website_id){
            return;
        }
        $goodsModel = new \data\model\VslGoodsModel();
        $defaultGoods = \think\Db::table("vsl_goods")->where(['website_id'=>0])->field('*')->select();
        if(!$defaultGoods){
            return;
        }
        $defaultGoodsId = $goodsModel->Query(['website_id' => 0], 'goods_id');
        $deaultIdStr = '';
        if($defaultGoodsId){
            $deaultIdStr = implode(',', $defaultGoodsId);
        }
        $attr = new \data\model\VslAttributeModel();
        
        //处理品牌
        $defaultBrand = \think\Db::table("vsl_goods_brand")->where(['website_id'=>0])->field('*')->select();//默认品牌
        $newBrandArr = [];
        if($defaultBrand){
            foreach ($defaultBrand as $bv){
                $brand_id = $bv['brand_id'];
                unset($bv['brand_id']);
                $bv['website_id'] = $website_id;
                $goodsBrand = new \data\model\VslGoodsBrandModel();
                $newBrandArr[$brand_id] = $goodsBrand->isUpdate(false)->save($bv);
            }
        }
        //处理品类
        $defaultAttr = \think\Db::table("vsl_attribute")->where(['website_id'=>0])->field('*')->select();//默认品类
        $newAttrArr = [];
        if($defaultAttr){
            foreach ($defaultAttr as $av){
                $attr_id = $av['attr_id'];
                unset($av['attr_id']);
                $av['website_id'] = $website_id;
                $av['brand_id_array'] = isset($newBrandArr[$av['brand_id_array']])?$newBrandArr[$av['brand_id_array']]:0;//默认只关联一个品牌
                $attr = new \data\model\VslAttributeModel();
                $newAttrArr[$attr_id] = $attr->isUpdate(false)->save($av);
            }
        }
        //处理分类
        $defaultCategory = \think\Db::table("vsl_goods_category")->where(['website_id'=>0])->field('*')->select();//默认分类
        $newCateArr = [];
        if($defaultCategory){
            foreach ($defaultCategory as $cv){
                $category_id = $cv['category_id'];
                unset($cv['category_id']);
                $cv['website_id'] = $website_id;
                $cv['attr_id'] = isset($newAttrArr[$cv['attr_id']])?$newAttrArr[$cv['attr_id']]:0;
                $cv['pid'] = $newCateArr[$cv['pid']];
                $category = new \data\model\VslGoodsCategoryModel();
                $newCateArr[$category_id] = $category->isUpdate(false)->save($cv);
            }
        }
       
        
        
        //处理属性
        $defaultAttrValue = \think\Db::table("vsl_attribute_value")->where(['website_id'=>0])->field('*')->select();//默认属性
        $newAttrValueArr = [];
        if($defaultAttrValue){
            foreach ($defaultAttrValue as $avv){
                $attr_value_id = $avv['attr_value_id'];
                unset($avv['attr_value_id']);
                $avv['website_id'] = $website_id;
                $avv['attr_id'] = isset($newAttrArr[$avv['attr_id']])?$newAttrArr[$avv['attr_id']]:0;
                $attrValue = new \data\model\VslAttributeValueModel();
                $newAttrValueArr[$attr_value_id] = $attrValue->isUpdate(false)->save($avv);
            }
        }
        //处理规格
        $defaultSpec =  \think\Db::table("vsl_goods_spec")->where(['website_id'=>0])->field('*')->select();//默认规格
        $newSpecArr = [];
        if($defaultSpec){
            foreach($defaultSpec as $sv){
                $spec_id = $sv['spec_id'];
                unset($sv['spec_id']);
                $sv['website_id'] = $website_id;
                $sv['goods_attr_id'] = isset($newAttrArr[$sv['goods_attr_id']])?$newAttrArr[$sv['goods_attr_id']]:0;
                $spec = new \data\model\VslGoodsSpecModel();
                $newSpecArr[$spec_id] = $spec->isUpdate(false)->save($sv);
            }
        }
        //处理规格值
        if($defaultSpec){
            $defaultSpecVal = \think\Db::table("vsl_goods_spec_value")->where(['website_id'=>0,'spec_id' => ['in', array_column($defaultSpec, 'spec_id')]])->field('*')->select();//默认规格值
            $newSpecValArr = [];
            if($defaultSpecVal){
                foreach($defaultSpecVal as $svv){
                    $spec_value_id = $svv['spec_value_id'];
                    unset($svv['spec_value_id']);
                    $svv['website_id'] = $website_id;
                    $svv['spec_id'] = isset($newSpecArr[$svv['spec_id']])?$newSpecArr[$svv['spec_id']]:0;
                    $specValue = new \data\model\VslGoodsSpecValueModel();
                    $newSpecValArr[$spec_value_id] = $specValue->isUpdate(false)->save($svv);
                }
            }
        }
        //处理商品
        $newGoodsArr = [];
        foreach($defaultGoods as $gv){
            $goods_id = $gv['goods_id'];
            unset($gv['goods_id']);
            $gv['website_id'] = $website_id;
            $gv['category_id'] = isset($newCateArr[$gv['category_id']])?$newCateArr[$gv['category_id']]:0;
            $gv['category_id_1'] = isset($newCateArr[$gv['category_id_1']])?$newCateArr[$gv['category_id_1']]:0;
            $gv['category_id_2'] = isset($newCateArr[$gv['category_id_2']])?$newCateArr[$gv['category_id_2']]:0;
            $gv['category_id_3'] = isset($newCateArr[$gv['category_id_3']])?$newCateArr[$gv['category_id_3']]:0;
            $gv['brand_id'] = isset($newBrandArr[$gv['brand_id']])?$newBrandArr[$gv['brand_id']]:0;
            $gv['goods_attribute_id'] = isset($newAttrArr[$gv['goods_attribute_id']])?$newAttrArr[$gv['goods_attribute_id']]:0;
            $goods_spec_format = $this->dealSpec($gv['goods_spec_format'],$newSpecArr,$newSpecValArr);
            $gv['goods_spec_format'] = $goods_spec_format;
            $goodsModel = new \data\model\VslGoodsModel();
            $newGoodsArr[$goods_id] = $goodsModel->isUpdate(false)->save($gv);
        }
        //处理商品属性
        $defaultGoodsAttr = \think\Db::table("vsl_goods_attribute")->where(['goods_id' => ['in',$deaultIdStr]])->field('*')->select();
        if($defaultGoodsAttr){
            foreach($defaultGoodsAttr as $gav){
                unset($gav['attr_id']);
                $gav['website_id'] = $website_id;
                $gav['goods_id'] = isset($newGoodsArr[$gav['goods_id']])?$newGoodsArr[$gav['goods_id']]:0;
                $gav['attr_value_id'] = isset($newAttrValueArr[$gav['attr_value_id']])?$newAttrValueArr[$gav['attr_value_id']]:0;
                $goodsAttr = new \data\model\VslGoodsAttributeModel();
                $goodsAttr->isUpdate(false)->save($gav);
            }
        }
        //处理商品sku
       
        $defaultGoodsSku = \think\Db::table("vsl_goods_sku")->where(['goods_id' => ['in',$deaultIdStr]])->field('*')->select();
        if($defaultGoodsSku){
            foreach($defaultGoodsSku as $gsv){
                unset($gsv['sku_id']);
                $gsv['goods_id'] = isset($newGoodsArr[$gsv['goods_id']])?$newGoodsArr[$gsv['goods_id']]:0;
                $attrValueItems = $this->dealSpecForSku($gsv['attr_value_items'], $newSpecArr, $newSpecValArr);
                $gsv['attr_value_items'] = $attrValueItems;
                $gsv['attr_value_items_format'] = $attrValueItems;
                $goodsSku = new \data\model\VslGoodsSkuModel();
                $goodsSku->isUpdate(false)->save($gsv);
            }
        }
        //处理商品sku图片
        
        $defaultGoodsSkuPic = \think\Db::table("vsl_goods_sku_picture")->where(['goods_id' => ['in',$deaultIdStr]])->field('*')->select();
        if($defaultGoodsSkuPic){
            foreach($defaultGoodsSkuPic as $gspv){
                unset($gspv['id']);
                $gspv['goods_id'] = isset($newGoodsArr[$gspv['goods_id']])?$newGoodsArr[$gspv['goods_id']]:0;
                $gspv['spec_id'] = isset($newSpecArr[$gspv['spec_id']])?$newSpecArr[$gspv['spec_id']]:0;
                $gspv['spec_value_id'] = isset($newSpecValArr[$gspv['spec_value_id']])?$newSpecValArr[$gspv['spec_value_id']]:0;
                $goodsSkuPic = new \data\model\VslGoodsSkuPictureModel();
                $goodsSkuPic->isUpdate(false)->save($gspv);
            }
        }
        //更新品类关联的规格
        $attrIds = implode(',',$newAttrArr);
        $attribute = \think\Db::table("vsl_attribute")->where(['website_id'=>$website_id, 'attr_id' => ['in',$attrIds]])->field('spec_id_array,attr_id')->select();//已经增加的品类
        if($attribute){
            foreach($attribute as $nav){
                if(!$nav['spec_id_array']){
                    continue;
                }
                $data['spec_id_array']  = $this->dealSpecForAttr($nav['spec_id_array'],$newSpecArr);
                $attr->isUpdate(true)->save($data,['attr_id' => $nav['attr_id']]);
            }
        }
        return true;
        
    }
    
    //处理商品中的规格
    public function dealSpec($goods_spec_format, $specArr, $specValArr){
        $specList = json_decode($goods_spec_format, true);
        if(!$specList){
            return '[]';
        }
        foreach($specList as $key => $val){
            $specList[$key]['spec_id'] = isset($specArr[$val['spec_id']])?$specArr[$val['spec_id']]:0;
            if($val['value']){
                foreach($val['value'] as $vk => $vv){
                    $specList[$key]['value'][$vk]['spec_id'] = isset($specArr[$val['spec_id']])?$specArr[$val['spec_id']]:0;
                    $specList[$key]['value'][$vk]['spec_value_id'] = isset($specValArr[$vv['spec_value_id']])?$specValArr[$vv['spec_value_id']]:0;
                }
            }
        }
        return json_encode($specList,true);
    }
    
    //处理sku中的规格
    public function dealSpecForSku($attr_value_items,  $specArr, $specValArr){
        $attr_value_items_array = explode(';', $attr_value_items);
        if(!$attr_value_items_array){
            return '';
        }
        $newSpec = [];
        foreach ($attr_value_items_array as $key => $spec_combination) {
            $explode_spec = explode(':', $spec_combination);
            $newSpec[$key] = $specArr[$explode_spec[0]].':'.$specValArr[$explode_spec[1]];
        }
        if(!$newSpec){
            return '';
        }
        return implode(';', $newSpec);
    }
    //处理sku中的规格
    public function dealSpecForAttr($spec_id_str,  $specArr){
        if(!$spec_id_str){
            return;
        }
        $spec_id_array = explode(',', $spec_id_str);
        foreach($spec_id_array as $key => $val){
            $spec_id_array[$key] = $specArr[$val];
        }
        if(!$spec_id_array){
            return '';
        }
        return implode(',', $spec_id_array);
    }
    
    /*
     * 获取商家注册时间
     */
    public function getWebCreateTime($website_id = 0){
        $create_time = $this->website_create_time;
        if(!$create_time){
            $create_time = $this->website->getInfo(['website_id' => $website_id],'create_time')['create_time'];
        }
        $data['year'] = $create_time ? date("Y",$create_time) :'y'; 
        $data['month'] = $create_time ? date("m",$create_time) :'m'; 
        $data['day'] = $create_time ? date("d",$create_time) :'d'; 
        return $data;
    }
    
    /**
     * 添加模块
     *
     */
    public function addModule($module_name, $controller, $method, $parent_module, $parent_contro, $parent_act, $url, $is_menu, $sort, $desc, $is_control_auth, $module = 'platform')
    {
        $condition = array(
            'controller' => $controller,
            'method' => $method,
            'module' => $module
        );
        $checkModule = $this->module->getInfo($condition,'module_id');
        if($checkModule){
            return false;
        }
        // 查询level
        if (!$parent_contro) {
            $level = 1;
            $pid = 0;
        } else {
            $parentInfo = $this->module->getInfo(['module_name' => $parent_module, 'controller' => $parent_contro, 'method' => $parent_act], 'module_id,level');
            if(!$parentInfo){
                $level = 1;
                $pid = 0;
            }else{
                $level = $parentInfo['level'] + 1;
                $pid = $parentInfo['module_id'];
            }
        }
        $data = array(
            'module_name' => $module_name,
            'module' => $module,
            'controller' => $controller,
            'method' => $method,
            'pid' => $pid,
            'level' => $level,
            'url' => $url,
            'is_menu' => $is_menu,
            'is_control_auth' => $is_control_auth,
            'sort' => $sort,
            'desc' => $desc,
            'create_time' => time(),
        );
        $res = $this->module->save($data);
        if($module == 'platform'){
            $user_group = new AuthGroupModel();
            $module_array = $user_group->getInfo(['is_system'=>1,'website_id'=>$this->website_id],'module_id_array')['module_id_array'];
            $module_real_array = $module_array.','.$res;
            $user_group->save(['module_id_array'=>$module_real_array],['is_system'=>1,'website_id'=>$this->website_id]);
        }
        if($module == 'admin'){
            $user_group = new AuthGroupModel();
            $shop_module_array = $user_group->getInfo(['is_system'=>1,'website_id'=>$this->website_id],'shop_module_id_array')['shop_module_id_array'];
            $shop_module_real_array = $shop_module_array.','.$res;
            $user_group->save(['shop_module_id_array'=>$shop_module_real_array],['is_system'=>1,'website_id'=>$this->website_id]);
        }
        $this->updateUserModule();
        return $res;
    }

}