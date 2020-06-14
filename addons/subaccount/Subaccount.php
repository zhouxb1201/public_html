<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:35
 */

namespace addons\subaccount;

use addons\Addons;

class Subaccount extends Addons {

    public $dir;
    public $dirDefault;
    public $dir_common; //公共部分路径
    public $dir_shop_common; //店铺公共部分路径
    public $com;
    protected static $addons_name = 'wechat';
    public $info = [
        'name' => 'subaccount', //插件名称标识
        'title' => '分权管理系统', //插件中文名
        'description' => '按岗位划分权限，让员工各尽其职', //插件描述
        'status' => 1, //状态 1使用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', //版本号
        'has_addonslist' => 1, //是否有下级插件
        'content' => '', //插件的详细介绍或者使用方法
        'config_hook' => 'subaccountConfig', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554196996.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782079.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782213.png',
    ]; //设置文件单独的钩子
    public $menu_info = [
        //platform
        [
            'module_name' => '分权管理系统',
            'parent_module_name' => '应用', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '按岗位划分权限，让员工各尽其职', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'subaccountConfig',
            'module' => 'platform',
            'is_main' => 1
        ],
    ];

    public function __construct() {
        parent::__construct();
    }


    public function install() {
        return true;
    }

    public function uninstall() {
        return true;
    }

}
