<?php
namespace addons\qlkefu;

use addons\Addons;
use addons\qlkefu\server\Qlkefu as QlkefuServer;

class Qlkefu extends Addons
{
    public $info = array(
        'name' => 'qlkefu',//插件名称标识
        'title' => '客服系统',//插件中文名
        'description' => '实时聊天，在线沟通',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'qlkefuSetting',//
        'config_admin_hook' => 'qlkefuSetting', //
        'logo' => 'http://pic.vslai.com.cn/upload/common/20190628155206.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782124.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782246.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '客服系统',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '实时聊天，在线沟通',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'qlkefuSetting',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '客服系统',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '开启客服之后，需要前往客服管理员端口进行配置，配置完成后使用更便捷。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'qlkefuSetting',
            'module' => 'platform'
        ],
        
        //admin
        [
            'module_name' => '客服系统',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '实时聊天，在线沟通',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'qlkefuSetting',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '客服系统',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '开启客服之后，需要前往客服管理员端口进行配置，配置完成后使用更便捷。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'qlkefuSetting',
            'module' => 'admin'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        if ($this->module == 'platform') {
            $this->assign('addQlkefuUrl', __URL(addons_url_platform('qlkefu://Qlkefu/addQlkefu')));
            $this->assign('saveSettingUrl', __URL(addons_url_platform('qlkefu://Qlkefu/saveSetting')));
        }else{
            $this->assign('addQlkefuUrl', __URL(addons_url_admin('qlkefu://Qlkefu/addQlkefu')));
            $this->assign('saveSettingUrl', __URL(addons_url_admin('qlkefu://Qlkefu/saveSetting')));
        }
    }
    
    public function qlkefuSetting()
    {
        $QlkefuServer = new QlkefuServer();
        $info = $QlkefuServer->qlkefuConfig($this->website_id, $this->instance_id);
        $info['seller_url'] = $info['ql_domain'].'/seller/login/index';
        $info['service_url'] = $info['ql_domain'].'/service/login/index';
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/qlkefuSetting');
    }
    
    /**
     * 安装方法
     */
    public function install()
    {
        return true;
    }

    /**
     * 卸载方法
     */
    public function uninstall()
    {
        return true;
    }
}