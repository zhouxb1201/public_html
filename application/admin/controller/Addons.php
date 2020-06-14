<?php

namespace app\admin\controller;

use think\Controller;
use data\service\WebSite;
use data\service\AdminUser as User;

/**
 * 插件执行默认控制器
 * Class Addons
 * @package think\addons
 */
class Addons extends Controller {

// 	public function _initialize(){
// 		/* 读取数据库中的配置 */
// 		$config = Cache::get('db_config_data');
// 		if(!$config){
// 			$config = api('Config/lists');
//             Cache::set('db_config_data',$config);
// 		}
// 		config($config); //添加配置
// 	}
    /**
     * 插件执行
     */
    public function execute($addons = null, $controller = null, $action = null, $addons_type = null) {
        if (!empty($addons) && !empty($controller) && !empty($action)) {
            $website = new WebSite();
            $user = new User();
            $module_info = $website->getModuleIdByModule('addonslist', $action);
            if(!$module_info || $module_info['is_control_auth']==0){
                $check_auth = 1;
            }else{
                $moduleid = $module_info['module_id'];
                $check_auth = $user->checkAuth($moduleid);
            }
            if(!$check_auth){
                return AjaxReturn(-1005);
            }
            // 获取类的命名空间
            if ($addons_type == null) {
                //addon
                $class = get_addon_class($addons, 'addon_controller', $controller);
            } else {
                //addons
                $class = get_addon_class($addons_type, $addons, $controller);
            }
            if (class_exists($class)) {
                if ($model === false) {
                    $this->error(lang('addon init fail'));
                }
                // 调用操作
                return \think\App::invokeMethod([$class, $action]);
            } else {
//                 $this->error(lang('控制器不存在'.$class));
            }
        }
        $this->error(lang('没有指定插件名称，控制器或操作！'));
    }

}
