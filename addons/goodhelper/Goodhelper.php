<?php

namespace addons\goodhelper;

use addons\Addons;

/**
 * Class Goodhelper
 * @package addons\goodhelper
 */
class Goodhelper extends Addons
{
    public $info = array(
        'name' => 'goodhelper',//插件名称标识
        'title' => '商品助手',//插件中文名
        'description' => '一键导入商品轻松开店',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'no_set' => 1,//不需要应用设置
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'goodImportHelper',//
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197026.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782156.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782276.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '商品助手',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '一键导入商品轻松开店',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'goodImportHelper',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '数据包导入',
            'parent_module_name' => '商品助手', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '目前支持淘宝数据包、微商来商城数据包数据导入。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'goodImportHelper',
            'module' => 'platform'
        ],
        [
            'module_name' => '确认导入',
            'parent_module_name' => '商品助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'goodConfirmImport',
            'module' => 'platform'
        ],
        [
            'module_name' => '商品采集',
            'parent_module_name' => '商品助手',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '支持淘宝与天猫的商品数据采集。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'goodGatherList',
            'module' => 'platform'
        ],
        [
            'module_name' => '采集内容',
            'parent_module_name' => '商品助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'getGoodGather',
            'module' => 'platform'
        ],
        [
            'module_name' => '导入进度',
            'parent_module_name' => '商品助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 0,//是否有控制权限
            'hook_name' => 'progress',
            'module' => 'platform'
        ],
        //admin
        [
            'module_name' => '商品助手',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '一键导入商品轻松开店',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'goodImportHelper',
            'module' => 'admin',
            'is_admin_main' => 1
        ],
        [
            'module_name' => '数据包导入',
            'parent_module_name' => '商品助手', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '目前支持淘宝数据包、微商来商城数据包数据导入。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'goodImportHelper',
            'module' => 'admin'
        ],
        [
            'module_name' => '确认导入',
            'parent_module_name' => '商品助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'goodConfirmImport',
            'module' => 'admin'
        ],
        [
            'module_name' => '商品采集',
            'parent_module_name' => '商品助手',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '支持淘宝与天猫、京东的商品数据采集。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'goodGatherList',
            'module' => 'admin'
        ],
        [
            'module_name' => '采集内容',
            'parent_module_name' => '商品助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'getGoodGather',
            'module' => 'admin'
        ],
        [
            'module_name' => '导入进度',
            'parent_module_name' => '商品助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 0,//是否有控制权限
            'hook_name' => 'progress',
            'module' => 'admin'
        ],
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform' || $this->module == 'admin') {
            $this->assign('goodConfirmImportUrl', __URL(call_user_func('addons_url_' . $this->module, 'goodhelper://Goodhelper/goodConfirmImport')));
            $this->assign('getGoodGatherUrl', __URL(call_user_func('addons_url_' . $this->module, 'goodhelper://Goodhelper/getGoodGather')));
            $this->assign('setGoodDescUrl', __URL(call_user_func('addons_url_' . $this->module, 'goodhelper://Goodhelper/setGoodDesc')));
            $is_ssl = \think\Request::instance()->isSsl();
            $http = "http://";
            if($is_ssl){
                $http = 'https://';
            }
            $this->assign('getGatherStatusUrl', $http.'47.52.71.120/crawler/getStatusByBatchNO/');
            $this->assign('progressUrl', __URL(call_user_func('addons_url_' . $this->module, 'goodhelper://Goodhelper/progress')));
            $this->assign('setGoodGatherUrl', __URL(call_user_func('addons_url_' . $this->module, 'goodhelper://Goodhelper/setGoodGather')));//test
            $this->assign('delGoodsHelpUrl', __URL(call_user_func('addons_url_' . $this->module, 'goodhelper://Goodhelper/delGoodsHelp')));
        }
    }

    public function goodImportHelper()
    {
        $this->fetch('template/' . $this->module . '/goodImportHelper');
    }

    public function goodGatherList()
    {
        $web_info = $this->website->getWebSiteInfo();

        if($web_info['realm_ip']){
            $link = $this->http.$web_info['realm_ip'];
        }else{
            $link = $this->http.$web_info['realm_two_ip'].'.'.top_domain($_SERVER['HTTP_HOST']);
        }
        $this->assign('realm_ip',$link);
        $this->fetch('template/' . $this->module . '/goodGatherList');
    }
    /**
     * 导入进度
     */
    public function progress()
    {
        return $this->fetch('/template/' . $this->module . '/progress');
    }

    /**
     * 安装方法
     */
    public function install()
    {
        // TODO: Implement install() method.


        return true;
    }

    /**
     * 卸载方法
     */
    public function uninstall()
    {

        return true;
        // TODO: Implement uninstall() method.
    }
}