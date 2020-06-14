<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:35
 */

namespace addons\credential;

use addons\Addons;
use addons\credential\model\CredBannerModel;
use data\model\WebSiteModel;
use data\service\Config as ConfigService;
use think\Request;
use \addons\credential\service\Credential AS CredentialService;

class Credential extends Addons
{
    protected static $addons_name = 'credential';

    public $info = [
        'name' => 'credential',//插件名称标识
        'title' => '授权证书',//插件中文名
        'description' => '授权证书',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'credential',//
        'config_admin_hook' => '', //
        'no_set' => 1,
        'logo' => 'https://pic.vslai.com.cn/upload/common/1572504359.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1572504366.pnga',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1572504371.png',
    ];//设置文件单独的钩子

    public $menu_info = [
        //platform
        [
            'module_name' => '授权证书',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '授权证书',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'credentialList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '证书列表',
            'parent_module_name' => '授权证书',//上级模块名称 用来确定上级目录
            'sort' => 1,//子菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '用户满足成为某角色的条件后，且已添加对应角色的默认授权证书，则用户可获得授权证书',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'credentialList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加证书',
            'parent_module_name' => '授权证书',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addCredential',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改证书',
            'parent_module_name' => '授权证书',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateCredential',
            'module' => 'platform'
        ],
        [
            'module_name' => '证书设置',
            'parent_module_name' => '授权证书',//上级模块名称 用来确定上级目录
            'sort' => 2,//子菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '设置查询页面的头部广告图',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'credentialSetting',
            'module' => 'platform'
        ],
        //admin
    ];

    public $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new CredentialService();
    }

    public function credentialList()
    {
        $this->assign('credentialDialogUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/credentialDialog')));
        $this->assign('deleteCredCacheUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/deleteCredCache')));
        $this->assign('credentialListUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/credentialList')));
        $this->assign('defaultCredUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/defaultCred')));
        $this->assign('deleteCredUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/deleteCred')));
        $this->fetch('template/' . $this->module . '/credentialList');
    }
    /*
     * 添加页面
     * **/
    public function addCredential()
    {
        $this->assign('saveCredentialUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/saveCredential')));
        $this->fetch('template/'. $this->module . '/addCredential');
    }

    /*
     * 编辑页面
     * **/
    public function updateCredential()
    {
        $this->assign('getCredInfoUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/getCredInfo')));
        $this->assign('saveCredentialUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/saveCredential')));
        $cred_id = request()->get('cred_id', 0);
        $this->assign('cred_id', $cred_id);
        $this->fetch('template/'. $this->module . '/updateCredential');
//        $this->assign('isRepeatKeyword', __URL(call_user_func('addons_url_' . $this->module,'poster://Poster/isRepeatKeyword')));
//        $this->fetch('template/' . $this->module . '/poster');
    }
    /*
     * 证书设置
     * **/
    public function credentialSetting()
    {
        $this->assign('saveBannerUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/saveBanner')));
        $this->assign('getBannerUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/getBanner')));
        $this->assign('deleteCredBannerUrl', __URL(call_user_func('addons_url_' . $this->module, 'credential://credential/deleteCredBanner')));
        $cred_banner = new CredBannerModel();
        $cred_baninfo = $cred_banner->getquery([], '', '');
        $this->assign('cred_baninfo', $cred_baninfo[0]);
        $this->fetch('template/'. $this->module . '/credentialSetting');
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }
}