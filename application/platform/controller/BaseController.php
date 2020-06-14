<?php
namespace app\platform\controller;
\think\Loader::addNamespace('data', 'data/');

use data\model\MerchantVersionModel;
use think\session;
use data\model\ModuleModel;
use data\model\SysAddonsModel;
use data\service\AdminUser as User;
use data\service\WebSite as WebSite;
use think\Controller;
use data\service\Config;
class BaseController extends Controller
{
    protected $user = null;

    protected $website = null;

    protected $uid;

    protected $instance_id;
    
    protected $website_id;

    protected $instance_name;

    protected $user_name;

    protected $user_headimg;

    protected $module = null;

    protected $controller = null;

    protected $action = null;

    protected $module_info = null;

    protected $rootid = null;

    protected $moduleid = null;

    protected $shopStatus;
    
    protected $integralStatus;

    protected $merchant_status;
    
    protected $merchant_expire;
    
    protected $globalStatus = 0;
    
    protected $areaStatus = 0;
    
    protected $teamStatus = 0;
    
    protected $distributionStatus = 0;
    
    protected $groupStatus = 0;
    
    protected $storeStatus = 0;
    
    protected $pcportStatus = 0;
    
    protected $miniprogramStatus = 0;

    protected $microshopStatus = 0;
    
    protected $goodhelperStatus = 0;
    
    protected $http = '';
    /**
     * 当前版本的路径
     *
     * @var string
     */
    protected $style = null;

    public function __construct()
    {
        parent::__construct();
        $is_ssl = \think\Request::instance()->isSsl();
        $this->http = "http://";
        if($is_ssl){
            $this->http = 'https://';
            $this->assign('ssl','https');
        }
        $this->user = new User();
        $this->website = new WebSite();
        $this->init();
        //增加前台首页
        $this->assign("website_index",'http://'.$_SERVER['HTTP_HOST']);
        $this->assign("pageshow", PAGESHOW);
        $this->assign("pagesize", PAGESIZE);
        

    }
    

    
    /**
     * 功能说明：action基类 调用 加载头部数据的方法
     */
    public function init()
    {
        $this->uid = $this->user->getSessionUid();
        $is_system = $this->user->getSessionUserIsSystem();
        $action = request()->action();
        if (empty($this->uid) && !in_array($action, ['wchaturlback','aliurlback'])) {
            if (request()->isAjax()) {
                echo json_encode(AjaxReturn(NO_LOGIN));
                exit();
            } else {
                $this->redirect(__URL(__URL__.'/'. PLATFORM_MODULE . "/login"));
            }
        }
        if (empty($is_system) && !in_array($action, ['wchaturlback','aliurlback'])) {
            $this->redirect(__URL(__URL__.'/'. PLATFORM_MODULE. "/login"));
        }
        $this->instance_id = $this->user->getSessionInstanceId();
        $this->website_id = $this->user->getSessionWebsiteId();
        $this->instance_name = $this->user->getInstanceName();
        $this->module = \think\Request::instance()->module();
        $this->controller = \think\Request::instance()->controller();
        $this->action = \think\Request::instance()->action();
        if (strpos($this->action, 'menu_') !== false) {
            $action_array = explode('_', $this->action);
            session('controller', $action_array[1]);
            $param = request()->param();
            foreach($param as $kp => $vp){
                    if(is_array($vp)){
                            unset($param[$kp]);
                    }
            }
            $params = http_build_query($param);
            //print_r($params);DIE;
            $redirect = __URL(__URL__ . '/' . PLATFORM_MODULE . "/Menu/addonmenu", $params);
            $this->redirect($redirect,$params);
        }
        if($this->controller=='Menu'){
            $this->controller='addonslist';
            $this->action= request()->param('addons');
        }
        
        
        $this->module_info = $this->website->getModuleIdByModule($this->controller, $this->action);
        
        // 过滤控制权限 为0
        if (empty($this->module_info)) {
            $this->moduleid = 0;
            $check_auth = 1;
        } elseif ($this->module_info["is_control_auth"] == 0) {
            $this->moduleid = $this->module_info['module_id'];
            $check_auth = 1;
        } else {
            $this->moduleid = $this->module_info['module_id'];
            $check_auth = $this->user->checkAuth($this->moduleid);
        }
        $module = new ModuleModel();
        $addons_sign = $module->getInfo(['module_id' => $this->moduleid],'addons_sign')['addons_sign'];
        $addons = new SysAddonsModel();
        $up_status = $addons->getInfo(['id'=>$addons_sign],'up_status')['up_status'];
        if($up_status==2){
            $check_auth = 0;
        }
        $web_info = $this->website->getWebSiteInfo();
        
        if ($check_auth) {
            $this->style = 'platform/';
            if (! request()->isAjax()) {
                //查询是否有相关应用
                $this->checkHasAddons();
                $config_service = new Config();
                $logoConfig = $config_service->getLogoConfig();
                $this->assign('logo_config', $logoConfig);
                $user_info = $this->user->getUserInfo();
                $root_array = $this->website->getModuleRootAndSecondMenu($this->moduleid);
                $this->rootid = $root_array[0];
                $this->second_menu_id = $root_array[1];
                $this->getNavigation();
                $root_module_info = $this->website->getSystemModuleInfo($this->rootid, 'module_name,url,controller');
                $second_menu = $this->website->getSystemModuleInfo($this->second_menu_id, 'module_name,url,controller');
                if ($this->rootid != 0) {
                    $second_menu_list = $this->user->getchildModuleQuery($this->rootid);
                } else {
                    $second_menu_list = '';
                }
                if($this->second_menu_id != 0){
                    $three_menu_list = $this->user->getchildModuleQuery($this->second_menu_id);
                }else{
                    $three_menu_list = '';
                }
                
                $this->assign('web_info',$web_info);
                $this->assign('uid',$this->uid);
                $this->assign('three_menu_list', $three_menu_list);
                $this->user_name = $user_info['user_tel'];
                $this->assign("headid", $this->rootid);
                $this->assign("second", $second_menu);
                $this->assign("moduleid", $this->moduleid);
                $this->assign("pid", $this->second_menu_id);
                $this->assign("title_name", $this->instance_name);
                $this->assign("username", $this->user_name);
                $this->assign("frist_menu", $root_module_info);
                $this->assign("second_menu", $this->module_info);
                $this->assign('second_menu_list', $second_menu_list);
                $this->assign('controller', $this->controller);
                $this->assign('website_id', $this->website_id);
                $this->assign('action', $this->action);
                $group = new User();
                $groupInfo = $group->getAdminUserInfo(['uid'=>$this->uid]);
                $this->assign("user_info",$groupInfo);
                $module = new ModuleModel();
                $module_auth_status = $module->getInfo(['module'=>'platform','controller'=>'auth','method'=>'userlist'],'module_id')['module_id'];
                if($groupInfo['module_id_array'] && in_array($module_auth_status,explode(',',$groupInfo['module_id_array']))){
                    $this->assign('auth_status',1);
                }
                $addons = new SysAddonsModel();
                $addons_sign_module = '';
                if(Session::get('addons_sign_module')==null || Session::get('addons_sign_module')==[]){
                    $up_status_ids = $addons->Query(['up_status'=>2],'id');
                    if($up_status_ids){
                        foreach ($up_status_ids as $v){
                            $addons_sign_module .= ','.implode(',',$module->Query(['addons_sign' => $v],'module_id'));
                        }
                        if($addons_sign_module){
                            $addons_sign_modules = explode(',',$addons_sign_module);
                            foreach($addons_sign_modules as $k=>$v){
                                if( !$v )
                                    unset($addons_sign_modules[$k] );
                            }
                            Session::set('addons_sign_module', $addons_sign_modules);
                        }
                    }
                }
            }
        } else {
            if (request()->isAjax()) {
                echo json_encode(AjaxReturn(NO_AITHORITY));
                exit();
            } elseif($this->module_info['pid'] == 0) {
                $this->error("当前用户没有操作权限");
            } else{
                //没权限跳转到该父菜单下另一个子菜单
                $jump = $module->where(['module_id' => ['IN', Session::get($this->module . 'module_id_array')], 'is_menu' => 1, 'pid' => $this->module_info['pid'], 'module' => $this->module])->order('sort', 'asc')->field('url')->find();
                if(!$jump){
                    $this->error("当前用户没有操作权限");
                }
                $redirect = __URL(__URL__ . '/' . PLATFORM_MODULE . "/".$jump['url']);
                $this->redirect($redirect);
            } 
        }
    }

    /*
     * 查询是否有相关应用
     */
    public function checkHasAddons(){
        $this->distributionStatus = getAddons('distribution', $this->website_id);
        $this->globalStatus = getAddons('globalbonus', $this->website_id);
        $this->areaStatus = getAddons('areabonus', $this->website_id);
        $this->teamStatus = getAddons('teambonus', $this->website_id);
        $this->groupStatus = getAddons('groupshopping', $this->website_id);
        $this->storeStatus = getAddons('store', $this->website_id);
        $this->pcportStatus = getAddons('pcport', $this->website_id);
        $this->miniprogramStatus = getAddons('miniprogram', $this->website_id);
        $this->shopStatus = getAddons('shop',$this->website_id);
        $this->integralStatus = getAddons('integral', $this->website_id);
        $this->microshopStatus = getAddons('microshop', $this->website_id);
        $this->goodhelperStatus = getAddons('goodhelper', $this->website_id,0,true);
        $this->blockchainStatus = getAddons('blockchain', $this->website_id);
        $this->assign('distributionStatus', $this->distributionStatus);
        $this->assign('globalStatus', $this->globalStatus);
        $this->assign('areaStatus', $this->areaStatus);
        $this->assign('teamStatus', $this->teamStatus);
        $this->assign('hasGroup', $this->groupStatus);
        $this->assign('hasStore', $this->storeStatus);
        $this->assign('pcportStatus', $this->pcportStatus);
        $this->assign('miniprogramStatus', $this->miniprogramStatus);
        $this->assign("shopStatus", $this->shopStatus);
        $this->assign("microshopStatus", $this->microshopStatus);
        $this->assign("goodhelperStatus", $this->goodhelperStatus);
        $this->assign("blockchainStatus", $this->blockchainStatus);
        $distributionExist = getAddons('distribution', $this->website_id, 0, true);
        $globalExist = getAddons('globalbonus', $this->website_id, 0, true);
        $areaExist = getAddons('areabonus', $this->website_id, 0, true);
        $teamExist = getAddons('teambonus', $this->website_id, 0, true);
        $this->assign('distributionExist', $distributionExist);
        $this->assign('globalExist', $globalExist);
        $this->assign('areaExist', $areaExist);
        $this->assign('teamExist', $teamExist);
    }
    /**
     * 添加操作日志（通过方法参数添加，当前考虑所有操作），
     */
    public function addUserLogByParam($operation,$target='')
    {
        $this->user->addUserLog($this->uid, 1, $this->controller, $this->action, \think\Request::instance()->ip(), $operation.':'.$target,$operation);
    }

    /**
     * 获取导航
     */
    public function getNavigation()
    {
        $first_list = $this->user->getchildModuleQuery(0);
        $list = array();
        $addons_sign_module = Session::get('addons_sign_module')?:[];
        foreach ($first_list as $k => $v) {
            if(!in_array($first_list [$k]['module_id'], $addons_sign_module)){
                $submenu = $this->user->getchildModuleQuery($v['module_id']);
                $list[$k]['data'] = $v;
                $list[$k]['sub_menu'] = $submenu;
            }
        }
        $addons_sign_module = Session::get('addons_sign_module')?:[];
        $this->assign("nav_list", $list);
        $this->assign("addons_sign_module",  $addons_sign_module );
    }
    public function getOperationTips($tips){
        $tips_array = array();
        if(!empty($tips)){
            $tips_array = explode("///", $tips);
        }
        $this->assign("tips", $tips_array);
    }
    /**
     * 获取三级菜单
     * 目前只有固定模板和自定义模板用
     */
    public function getThreeLevelModule()
    {
        $child_menu_list_old = $this->user->getchildModuleQuery($this->second_menu_id);
        $child_menu_list = [];
        foreach ($child_menu_list_old as $k => $v) {
            $active = 0;
            $param = request()->param();
            if (strpos(strtolower(request()->pathinfo()), strtolower($v['url']))) {
                $active = 1;
            } else 
                if (! empty($param['addonslist']) && strpos(strtolower($v['url']), strtolower($param['addonslist'])) !== false) {
                    $active = 1;
                }
            $child_menu_list[] = array(
                'url' => $v['url'],
                'menu_name' => $v['module_name'],
                'active' => $active
            );
        }
        $this->assign('child_menu_list', $child_menu_list);
    }
}
