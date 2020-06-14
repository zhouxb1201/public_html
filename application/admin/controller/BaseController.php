<?php

namespace app\admin\controller;

\think\Loader::addNamespace('data', 'data/');

use data\model\ModuleModel;
use data\model\SysAddonsModel;
use data\service\AdminUser as User;
use addons\shop\service\Shop;
use data\service\WebSite as WebSite;
use think\Controller;
use \think\Session as Session;
use data\service\Order as OrderService;
use addons\distribution\service\Distributor as  DistributorService;
class BaseController extends Controller {

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
    protected $second_menu_id = null;

    //新增分销分红状态

    protected $globalStatus = 0;

    protected $areaStatus = 0;

    protected $teamStatus = 0;

    protected $distributionStatus = 0;

    // 二级菜单module_id 手机自定义模板临时添加，用来查询三级菜单

    /**
     * 当前版本的路径
     *
     * @var string
     */
    protected $style = null;
    protected $pcportStatus = 0;
    protected $miniprogramStatus = 0;
    protected $seckill_status = 0;
    protected $bargain_status = 0;
    protected $channel_status = 0;
    protected $integral_status = 0;
    protected $goodhelperStatus = 0;
    public function __construct() {
        parent::__construct();
        $this->user = new User();
        $this->website = new WebSite();
        $this->init();
        $this->assign("pageshow", PAGESHOW);
        $this->assign("pagesize", PAGESIZE);
    }

    /**
     * 创建时间：2016-10-27
     * 功能说明：action基类 调用 加载头部数据的方法
     */
    public function init() {
        $this->website_id = $this->user->getSessionWebsiteId();
        $this->uid = $this->user->getSessionUid();
        $is_system = $this->user->getSessionUserIsSystem();
        $this->instance_id = $this->user->getSessionInstanceId();
        $model = \think\Request::instance()->module();
        if( Session::get($model . 'website_ids')){
            $this->website_id = Session::get($model . 'website_ids');
        }
        if(\think\Request::instance()->action() =='businessCenter'){
            $this->businessCenter();
        }
        if (empty($this->uid)) {
            $this->redirect(__URL("ADMIN_MAIN/login?website_id=").$this->website_id);
        }
        if (empty($is_system)) {
            $this->redirect(__URL("ADMIN_MAIN/login?website_id=").$this->website_id);
        }
        //获取是否拥有分销分红权限
        $this->distributionStatus = getAddons('distribution', $this->website_id);
        if($this->distributionStatus){
            //获取分销基础设置，是否开启店铺分销
            $config= new DistributorService();
            $distributionStatusAdmin = $config->getDistributionSite($this->website_id);
            if($distributionStatusAdmin['distribution_admin_status'] == 0){
                $this->distributionStatus = 0;
            }
        }
        $this->assign('distributionStatus', $this->distributionStatus);
        $this->globalStatus = getAddons('globalbonus', $this->website_id);
        $this->assign('globalStatus', $this->globalStatus);
        $this->areaStatus = getAddons('areabonus', $this->website_id);
        $this->assign('areaStatus', $this->areaStatus);
        $this->teamStatus = getAddons('teambonus', $this->website_id);
        $this->assign('teamStatus', $this->teamStatus);
        
        $this->shopStatus = getAddons('shop', $this->website_id);
        if (!$this->shopStatus) {
            $this->error('没有店铺应用，无法访问', __URL("ADMIN_MAIN/login?website_id=").$this->website_id);
        }
        $this->instance_name = $this->user->getInstanceName();
        $this->module = \think\Request::instance()->module();
        $this->controller = \think\Request::instance()->controller();
        $this->action = \think\Request::instance()->action();
        $this->pcportStatus = getAddons('pcport', $this->website_id);
        $this->miniprogramStatus = getAddons('miniprogram', $this->website_id);
        $this->seckill_status = getAddons('seckill', $this->website_id);
        $this->bargain_status = getAddons('bargain', $this->website_id);
        $this->channel_status = getAddons('channel', $this->website_id);
        $this->integral_status = getAddons('integral', $this->website_id);
        $this->goodhelperStatus = getAddons('goodhelper', $this->website_id,$this->instance_id,true);
        $this->blockchainStatus = getAddons('blockchain', $this->website_id);
        $this->assign('goodhelperStatus', $this->goodhelperStatus);
        $this->assign('pcportStatus', $this->pcportStatus);
        $this->assign('miniprogramStatus', $this->miniprogramStatus);
        $this->assign('blockchainStatus', $this->blockchainStatus);
        Session::set($model . 'website_ids',$this->website_id);
        // 判断是否是插件菜单
        if (strpos($this->action, 'menu_') !== false) {
            $action_array = explode('_', $this->action);
            session('controller', $action_array[1]);
            $params = http_build_query(request()->get());
            $redirect = __URL(__URL__ . '/' . ADMIN_MODULE . "/Menu/addonmenu", $params);
            $this->redirect($redirect);
        }
        if ($this->controller == 'Menu') {
            $this->controller = 'addonslist';
            $this->action = request()->param('addons');
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
        if ($check_auth) {
            // 网站信息
            

            $this->style = 'admin/';
            //$this->getSystemConfig();
            $this->assign("instance_id", $this->instance_id);

            if (!request()->isAjax()) {
                /* 店铺导航 */
                $shop = new Shop();

                // 用户信息
                $user_info = $this->user->getUserInfo();
                $shop_info = $shop->getShopDetail($user_info['instance_id']);
                if($shop_info['base_info']['shop_state']!=1){
                    $this->error('店铺已关闭，请联系商家', __URL("ADMIN_MAIN/login?website_id=").$this->website_id);
                }
                $config_service = new Config();
                $logoConfig = $config_service->getLogoConfig();
                $this->assign('logo_config', $logoConfig);
                $web_info = $this->website->getWebSiteInfo();
                $this->assign("web_info", $web_info);
                $this->assign("shop_info", $shop_info);
                if ($user_info['last_login_time'] == "0000-00-00 00:00:00") {
                    $user_info['last_login_time'] = "--";
                }
                if ($user_info['last_login_ip'] == "0.0.0.0") {
                    $user_info['last_login_ip'] = "--";
                }
                $this->assign("user_info", $user_info);
                $root_array = $this->website->getModuleRootAndSecondMenu($this->moduleid);
                $this->rootid = $root_array[0];
                $second_menu_id = $root_array[1];
                $root_module_info = $this->website->getSystemModuleInfo($this->rootid, 'module_name,url,module_picture');
                $second_menu = $this->website->getSystemModuleInfo($second_menu_id, 'module_name,url,controller');
                $first_menu_list = $this->user->getchildModuleQuery(0);

                if ($this->rootid != 0) {
                    $second_menu_list = $this->user->getchildModuleQuery($this->rootid);
                } else {
                    $second_menu_list = '';
                }
                if ($second_menu_id != 0) {
                    $three_menu_list = $this->user->getchildModuleQuery($second_menu_id);
                } else {
                    $three_menu_list = '';
                }
                $this->assign('three_menu_list', $three_menu_list);
                $this->user_name = $user_info['user_name'];
                $this->user_headimg = $user_info['user_headimg'];
                $this->assign("headid", $this->rootid);
                $this->assign("second_menu_id", $second_menu_id);
                $this->assign("second", $second_menu);
                $this->assign("moduleid", $this->moduleid);
                $this->assign("title_name", $this->instance_name);
                $this->assign("user_name", $this->user_name);
                $this->assign("user_headimg", $this->user_headimg);
                $this->assign("headlist", $first_menu_list);
                $this->assign("leftlist", $second_menu_list);
                $this->assign("frist_menu", $root_module_info); // 当前选中的导航菜单
                $this->assign("second_menu", $this->module_info);
                $this->assign('second_menu_list', $second_menu_list);
                $this->second_menu_id = $second_menu_id; // 临时添加，用来查询3级菜单 手机端自定义模板
                $this->assign("website_id", $this->website_id);
                $this->assign('action', $this->action);
                $this->getNavigation();
            }
        } else {
            if (request()->isAjax()) {
                echo json_encode(AjaxReturn(NO_AITHORITY));
                exit();
            } else {
                $this->error("当前用户没有操作权限");
            }
        }
    }

    /**
     * 添加操作日志（当前考虑所有操作），
     */
    public function addUserLog($operation, $target = '') {
        $this->user->addUserLog($this->uid, 1, $this->controller, $this->action, \think\Request::instance()->ip(), $operation . ':' . $target, $operation);
    }
    public function getOrderCount(){
        $order = new OrderService();
        $order_count_array = array();
        $order_count_array['daifahuo'] = $order->getOrderCount(['order_status'=>1,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id]);//待发货
        $order_count_array['tuikuanzhong'] = $order->getOrderCount(['order_status'=>-1,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id]);//退款中
        return $order_count_array;
    }
    /**
     * 商家中心
     */
    public function businessCenter(){
        $uid = request()->get('uid');
        $website_id = request()->get('website_id');
        if(!$uid){
            $this->redirect(__URLS("SHOP_MAIN/login/index",'',['website_id'=>$website_id]));
        }
        if(!getAddons('shop', $website_id)){
            $this->redirect(__URLS("SHOP_MAIN/index/index",'',['website_id'=>$website_id]));
        }
        $auth = new User();
        $userInfo = $auth->getUserInfoByUid($uid);
        if(!$userInfo['is_system']){
            $this->redirect(__URLS('ADDONS_SHOP_MAIN','addons=applyIndex',['website_id'=>$website_id]));
            exit();
        }
        $this->user->Logout();
        $this->user->login($userInfo['user_tel'], $userInfo['user_password'], 2, $website_id);
		$this->redirect(__URL("ADMIN_MAIN"));
		exit();
        
    }

    /**
     * 获取导航
     */
    public function getNavigation() {
        $first_list = $this->user->getchildModuleQuery(0);
        $list = array();
        foreach ($first_list as $k => $v) {
            $module = new ModuleModel();
            $addons_sign = $module->getInfo(['module_id' => $this->moduleid],'addons_sign')['addons_sign'];
            $addons = new SysAddonsModel();
            $up_status = $addons->getInfo(['id'=>$addons_sign],'up_status')['up_status'];
            if($up_status!=2){
                $submenu = $this->user->getchildModuleQuery($v['module_id']);
                $list[$k]['data'] = $v;
                $list[$k]['sub_menu'] = $submenu;
            }
        }
        $this->assign("nav_list", $list);
    }

    /**
     * 获取操作提示是否显示
     *
     * @return mixed|boolean|void
     */
    public function getWarmPromptIsShow() {
        $is_show = cookie("warm_promt_is_show");
        if ($is_show == null) {
            $is_show = 'show';
        }
        return $is_show;
    }

    /**
     * 获取系统信息
     */
    public function getSystemConfig() {
        $system_config['os'] = php_uname(); // 服务器操作系统
        $system_config['server_software'] = $_SERVER['SERVER_SOFTWARE']; // 服务器环境
        $system_config['upload_max_filesize'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow'; // 文件上传限制
        $system_config['gd_version'] = gd_info()['GD Version']; // GD（图形处理）版本
        $system_config['max_execution_time'] = ini_get("max_execution_time") . "秒"; // 最大执行时间
        $system_config['port'] = $_SERVER['SERVER_PORT']; // 端口
        $system_config['dns'] = $_SERVER['HTTP_HOST']; // 服务器域名
        $system_config['php_version'] = PHP_VERSION; // php版本
        $system_config['ip'] = $_SERVER['SERVER_ADDR']; // 服务器ip
        $this->assign("system_config", $system_config);
    }

    /**
     * 获取三级菜单
     * 目前只有固定模板和自定义模板用
     */
    public function getThreeLevelModule() {
        $child_menu_list_old = $this->user->getchildModuleQuery($this->second_menu_id);
        $child_menu_list = [];
        foreach ($child_menu_list_old as $k => $v) {
            $active = 0;
            $param = request()->param();
            if (strpos(strtolower(request()->pathinfo()), strtolower($v['url']))) {
                $active = 1;
            } else
            if (!empty($param['addons']) && strpos(strtolower($v['url']), strtolower($param['addons'])) !== false) {
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
