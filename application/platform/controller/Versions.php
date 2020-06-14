<?php
namespace app\platform\controller;

use data\model\ModuleModel;
use data\service\Version;
use data\service\Extend as ExtendService;
use data\service\WebSite;
use data\model\SysAddonsClicksModel;
use data\service\Config as WebConfig;

class Versions extends BaseController
{
    protected $extend;

    public function __construct()
    {
        $this->extend = new ExtendService();
        parent::__construct();
        $website = new WebSite();
        $website->addModule('商标管理', 'versions', 'logoconfig', '系统', 'config', 'sysconfig', 'versions/logoconfig', 1, 4, '设置商城logo,版权信息', 1, 'platform');       
    }
    //版本信息
    public function versionInformation()
    {
        $version_data = include(ROOT_PATH.'/version.php');
        if(!file_exists(ROOT_PATH.'/version.php') || !file_exists(ROOT_PATH.'/data/service/Version.php')){
            $this->assign('version_message','系统核心配置文件丢失，请把配置文件恢复至原始内容再重试');
        }else{
            $param['version_id'] = $version_data['version_id'];
            $param['version_num'] = $version_data['version_num'];
            $param['secret_key'] = $version_data['secret_key'];
            $param['domain_name'] = $_SERVER['HTTP_HOST'];
            $version = new Version();
            $version_status = $version->getDataFromServer(1,$param);
			$ismade = $version->getDataFromServer(6,$param);
            if($version_status['code']>0){
                if(isset($version_status['data']['version_num'])){
                    $version_data['version_status_code'] = 1;
                    $version_data['version_new_num'] = $version_status['data']['version_num'];
                }else{
                    $version_data['version_status_code'] = 2;
                }
            }else{
                $version_data['version_message'] = $version_status['message'];
            }
            $version_log = $version->getDataFromServer(2,$param);
            if($version_log['code']>0){
                $version_data['version_log_code'] = $version_log['code'];
                $version_data['version_log'] = $version_log['data'];
            }else{
                $version_data['version_message'] = $version_log['message'];
            }
            if($version_log['code']>0 && $version_status['code']>0){
                $this->assign('version_data',$version_data);
            }else{
                $this->assign('version_message',$version_data['version_message']);
            }
			$alllogs='';
			$logdir=ROOT_PATH . 'logs';
			if(is_dir($logdir) && $head = opendir($logdir)){
				while(($file = readdir($head)) !== false){
					if($file != ".." && $file!="."){
						if(!is_dir($file)){
							$alllogs=$alllogs . PHP_EOL . file_get_contents($logdir.'/'.$file);
						}
					}
				}
				closedir($head);
			}
			$alllogs = str_replace(PHP_EOL,"</p><p>",$alllogs);
			$this->assign('alllogs',$alllogs);
			$this->assign('ismade',$ismade['ismade']);
        }
        return view($this->style . "System/versionInformation");
    }

    //版本更新、获取日志、版本状态
    public function versionUpdate() {
        $version_data = include(ROOT_PATH.'/version.php');
        $param['version_id'] = $version_data['version_id'];
        $param['version_num'] = $version_data['version_num'];
        $param['secret_key'] = $version_data['secret_key'];
        $param['domain_name'] = $_SERVER['HTTP_HOST'];
        $version = new Version();
        $data = $version->getDataFromServer(3,$param);
        if($data['code']>0 &&  $version_data && file_exists(ROOT_PATH.'/data/service/Version.php')){
            $res = $version->updateVersion($data);
            if($res<0){
                return AjaxReturn($res);
            }
            return AjaxReturn(1);
        }else{
            return AjaxReturn(-1);
        }
    }
    /**
     * 插件管理
     */
    public function addonsList() {
        if (request()->isAjax()) {
            $condition['title'] = request()->post("search_text", '');
            $condition['type'] = request()->post("type", 'all');
            $condition['version'] = request()->post("version", 0);
            $list = $this->extend->getAddonsList($condition);
            return $list;
        }
        return view($this->style . "System/addonList");
    }
    /**
     * 安装插件
     */
    public function install() {
        if (request()->isAjax() && request()->method() != 'GET') {
            $arr = [];
            $addon_name = trim(request()->post('addon_name', ''));
            $category_id = request()->post('category_id', 0);
            $class = get_addon_class($addon_name);
            if (!class_exists($class)) {
                return ['code' => 0, 'message' => '插件不存在'];
            }
            if (!$category_id) {
                return ['code' => 0, 'message' => '请选择分类'];
            }
            $addons = new $class();
            $info = $addons->info;
            if (!$info) { // 检测信息的正确性
                return ['code' => 0, 'message' => '插件信息缺失'];
            }
            session('addons_install_error', null);
            $install_flag = $addons->install();
            if (!$install_flag) {
                return ['code' => 0, 'message' => '执行插件预安装操作失败' . session('addons_install_error')];
            }

            // 判断是否有后台列表
            if (is_array($addons->admin_list) && $addons->admin_list !== array()) {
                $info['has_adminlist'] = 1;
            } else {
                $info['has_adminlist'] = 0;
            }
            // 获取菜单 如果有菜单配置则安装菜单
            $methods['name'] = [];
            $module_id = 0; //platform端应用入口菜单id,用于应用列表
            $admin_module_id = 0; //admin端营销入口菜单id,用于admin端应用列表
            if (is_array($addons->menu_info) && $addons->menu_info !== array()) {
                $menu = $addons->menu_info;
                $website = new WebSite();
                $module_model = new ModuleModel();
                $fail = false;
                foreach ($menu as $k => $v) {
                    $parent_module_info = $module_model->getInfo(['module_name' => $v['parent_module_name'], 'module' => $v['module'] ?: 'platform'], 'module_id');
                    if (empty($parent_module_info)) {
                        $parent_module_info['module_id'] = 0; //没有设置上级菜单，默认安装为一级菜单
                    }
                    $controller = 'addonslist';
                    $method = $v['hook_name'];
                    $module = $v['module'] ?: 'platform';
                    $url = $controller . '/menu_' . $controller . '?addons=' . $v['hook_name'];
                    if (!in_array($v['hook_name'], $methods['name'])) {
                        $methods['name'][] = $v['hook_name'];
                        $methods['desc'][] = $v['desc'];
                    }
                    if (!$v['is_member']) {
                        if($v['hook_name']=='globalBonusProfile' || $v['hook_name']=='areaBonusProfile' || $v['hook_name']=='teamBonusProfile'){
                            $module_model = new ModuleModel();
                            $parent_module_id = $module_model->getInfo(['method'=>'financialReconciliation','module'=>'platform','module_name'=>'财务'],'module_id')['module_id'];
                            $moduleInfo = $module_model->getInfo(['method'=>'bonusRecordList','module'=>'platform'],'*');
                            if(!$moduleInfo){
                                $website->addSytemModule('分红流水', 'Finance', 'bonusRecordList', $parent_module_id,'Finance/bonusRecordList',1,0, 8, '', '全球股东、区域代理、团队队长达到相应条件都可获得分红，目前分红发放是统一打到会员余额账户上，当账目有出入时可通过流水追溯原由。', '', 1,'platform', '');
                            }
                        }
                        $res = $website->addSytemModule($v['module_name'], $controller, $method, $parent_module_info['module_id'], $url, $v['is_menu'], $v['is_dev'], $v['sort'], $v['module_picture'], $v['desc'], $v['icon_class'], $v['is_control_auth'], $module, '');
                        $arr[] =  $res;
                        if ($v['is_main']) {
                            $module_id = $res;
                        }
                        if ($v['is_admin_main']) {
                            $admin_module_id = $res;
                        }
                        if (!$res) {
                            $addons->uninstall();
                            $fail = true;
                            break;
                        }
                    }
                }
                if ($fail) {
                    return ['code' => 0, 'message' => '安装菜单操作失败，请检查菜单配置'];
                }
            }

            $hooks = $this->extend->getHooksName();
            if ($hooks) {
                $needAddHook = array_diff($methods['name'], $hooks);
            } else {
                $needAddHook = $methods['name'];
            }
            if (is_array($needAddHook)) {
                foreach ($needAddHook as $hookKey => $hook) {
                    $this->extend->addHooks($hook, $methods['desc'][$hookKey], 2);
                }
                unset($hook);
            }
            // 获取配置文件
            $info['config'] = json_encode($addons->getOneConfig());
            // 插件添加到库
            $res = $this->extend->addAddons($info['name'], $info['title'], $info['description'], $info['status'], $info['config'], $info['author'], $info['version'], $info['has_adminlist'], $info['has_addonslist'], $info['config_hook'], $info['content'], $module_id, $admin_module_id, $info['config_admin_hook'], $info['no_set'] ?: 0, $category_id, isset($info['logo']) ? $info['logo'] : '',isset($info['logo_small']) ? $info['logo_small'] : '',isset($info['logo_often']) ? $info['logo_often'] : '');
            if ($res) { 
                if($arr){
                    foreach ($arr as $k => $v) {
                        $module_model = new ModuleModel();
                        $module_model->save(['addons_sign'=>$res],['module_id'=>$v]);
                    }
                }
                $this->extend->updateModule();
                $hooks_update = $this->extend->updateHooks($addon_name);
                if ($hooks_update) {
                    cache('hooks', null);
                    $this->addUserLogByParam('安装插件', $info['title']);
                    return ['code' => 1, 'message' => '安装成功'];
                } else {
                    $this->extend->deleteAddons([
                        'name' => $addon_name
                    ]);
                    return ['code' => 0, 'message' => '更新钩子处插件失败,请卸载后尝试重新安装'];
                }
            } else {
                return ['code' => 0, 'message' => '写入插件数据失败'];
            }
        }
        $addon_name = trim(request()->get('addon_name', ''));
        $category_list = $this->extend->getAddonsCategoryList(1, 0, [], 'sort asc', '*');
        $this->assign('category_list', $category_list['data']);
        $this->assign('type', 'install');
        $this->assign('addon_name', $addon_name);
        return view($this->style . "modal");
    }
    /**
     * 卸载插件
     */
    public function uninstall() {
        $id = trim(request()->get('id', 0));
        $db_addons = $this->extend->getAddonsInfo([
            'id' => $id
                ], '*');
        $class = get_addon_class($db_addons['name']);
        // $this->assign ( 'jumpUrl', url ( 'index' ) );
        if (!$db_addons || !class_exists($class))
            $this->error('插件不存在');
        session('addons_uninstall_error', null);
        $addons = new $class();
        $uninstall_flag = $addons->uninstall();
        if (!$uninstall_flag)
            $this->error('执行插件预卸载操作失败' . session('addons_uninstall_error'));
        // 判断是否有菜单，有的话需要删除
        if (is_array($addons->menu_info) && $addons->menu_info !== array()) {
            $menu = $addons->menu_info;
            $module_model = new ModuleModel();
            foreach ($menu as $k => $v) {
                $method = $v['hook_name'];
                $module_model->destroy([
                    'module_name' => $v['module_name'],
                    'method' => $method
                ]);
                if($v['hook_name']=='globalBonusProfile' || $v['hook_name']=='areaBonusProfile' || $v['hook_name']=='teamBonusProfile'){
                    $module_model = new ModuleModel();
                    $moduleInfo = $module_model->getInfo(['method'=>'bonusRecordList','module'=>'platform'],'*');
                    $globalBonus = $module_model->getInfo(['method'=>'globalBonusProfile','module'=>'platform'],'*');
                    $areaBonus = $module_model->getInfo(['method'=>'areaBonusProfile','module'=>'platform'],'*');
                    $teamBonus= $module_model->getInfo(['method'=>'teamBonusProfile','module'=>'platform'],'*');
                    if(empty($globalBonus) && empty($areaBonus) && empty($teamBonus)){
                        $module_model->destroy([
                            'module_id' => $moduleInfo['module_id'],
                        ]);
                    }
                }
            }
        }
        $addonsClick = new SysAddonsClicksModel();
        $addonsClick->delData(['addons_id' => $id]); //删除应用点击量
        $hooks_update = $this->extend->removeHooks($db_addons['name']);
        if ($hooks_update === false) {
            $this->error('卸载插件所挂载的钩子数据失败');
        }
        cache('hooks', null);
        $delete = $this->extend->deleteAddons([
            'name' => $db_addons['name']
        ]);
        if ($delete === false) {
            $this->error('卸载插件失败');
        } else {
            // 删除移动的资源文件
            // $File = new \com\File();
            // $File->del_dir('./static/addons/'.$db_addons ['name']);
            $this->addUserLogByParam('卸载插件', $db_addons['name']);
            $this->success('卸载成功');
        }
    }
    /**
     * 商标管理
     */
    public function logoConfig() {
        $config_service = new WebConfig();
        if (request()->isAjax()) {
            $params = [];
            $params['platform_logo'] = request()->post('platform_logo','');
            $params['opera_logo'] = request()->post('opera_logo','');
            $params['forgot_logo'] = request()->post('forgot_logo','');
            $params['admin_logo'] = request()->post('admin_logo','');
            $params['login_mall_name'] = request()->post('login_mall_name','');
            $params['title_word'] = request()->post('title_word','');
            $params['login_copyright'] = request()->post('login_copyright','');
            $params['opera_copyright'] = request()->post('opera_copyright','');
            $params['platform_word'] = request()->post('platform_word','');
            $res = $config_service->addLogoConfig($params);
            if($res){
                $this->addUserLogByParam('图标版权配置信息');
            }
            return AjaxReturn($res);
        }
	$logoConfig = $config_service->getLogoConfig();
        $this->assign('logo_config', $logoConfig);
        return view($this->style . "System/logoConfig");
    }
}