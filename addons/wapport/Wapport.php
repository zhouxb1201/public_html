<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:35
 */

namespace addons\wapport;

use addons\Addons;

class Wapport extends Addons {

    public $dir;
    public $dirDefault;
    public $dir_common; //公共部分路径
    public $dir_shop_common; //店铺公共部分路径
    public $com;
    protected static $addons_name = 'wechat';
    public $info = [
        'name' => 'wapport', //插件名称标识
        'title' => 'H5商城', //插件中文名
        'description' => '移动微商城，轻松上手', //插件描述
        'status' => 1, //状态 1使用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', //版本号
        'has_addonslist' => 1, //是否有下级插件
        'content' => '', //插件的详细介绍或者使用方法
        'config_hook' => 'wapConfig', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554196996.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782079.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782213.png',
    ]; //设置文件单独的钩子
    public $menu_info = [
        //platform
        [
            'module_name' => 'H5商城',
            'parent_module_name' => '应用', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '移动微商城，轻松上手', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'wapConfig',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '商城信息',
            'parent_module_name' => 'H5商城', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '移动商城包含“H5商城”与“公众号商城”，使用H5商城之前，请在菜单找到“系统->商城配置”把“移动商城”开关打开。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'module' => 'platform',
            'hook_name' => 'wapConfig'
        ],
    ];

    public function __construct() {
        parent::__construct();
    }

    public function wapConfig() {
        $this->fetch('template/platform/wapConfig');
    }


    public function install() {
        return true;
    }

    public function uninstall() {
        return true;
    }

}
