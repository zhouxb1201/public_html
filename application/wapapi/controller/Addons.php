<?php

namespace app\wapapi\controller;

use data\service\WebSite;
use think\Controller;
use think\Request;
use think\Config;
use think\Loader;
use think\Cache;

/**
 * 插件执行默认控制器
 * Class Addons
 * @package think\addons
 */
class Addons extends BaseController
{
// 	public function _initialize(){
// 		/* 读取数据库中的配置 */
// 		$config = Cache::get('db_config_data');
// 		if(!$config){
// 			$config = api('Config/lists');
//             Cache::set('db_config_data',$config);
// 		}
// 		config($config); //添加配置
// 	}

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 插件执行
     */
    public function execute($addons = null, $controller = null, $action = null, $addons_type = null)
    {
        if(!empty($addons)){
            //获取插件的中文名称
            $addons_service = new \data\service\Addons();
            $addons_info = $addons_service->getAddonsInfo(['name' => $addons], 'title');
            if($addons_info){
                $addons_name = $addons_info['title'];
            }
        }
        if (!empty($addons) && !empty($controller) && !empty($action)) {
            $website_id = checkUrl();
            if (!getAddons($addons, $website_id)) {
                switch ($addons){
                    case 'shop':
                        return json(['code' => NO_SHOP, 'message' =>  '店铺应用不存在']);
                    case 'distribution':
                        return json(['code' => -1, 'message' =>  '商城暂未开启分销']);
                    default:
                        return json(['code' => -1, 'message' => $addons_name . '：应用已关闭或不存在']);
                }
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
                // 调用操作
                return \think\App::invokeMethod([$class, $action]);
            } else {
                switch ($addons){
                    case 'shop':
                        return json(['code' => NO_SHOP, 'message' =>  '店铺应用不存在']);
                    case 'distribution':
                        return json(['code' => -1, 'message' =>  '商城暂未开启分销']);
                    default:
                        return json(['code' => -1, 'message' => $addons_name . '：应用已关闭或不存在']);
                }
            }
        }
        switch ($addons){
            case 'shop':
                return json(['code' => NO_SHOP, 'message' =>  '店铺应用不存在']);
            case 'distribution':
                return json(['code' => -1, 'message' =>  '商城暂未开启分销']);
            default:
                return json(['code' => -1, 'message' => $addons_name . '：应用已关闭或不存在']);
        }
    }


}
