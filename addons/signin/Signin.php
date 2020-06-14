<?php
namespace addons\signin;

use addons\Addons;
use data\model\AddonsConfigModel;
use addons\signin\server\Signin as SigninServer;


class Signin extends Addons
{
    public $info = array(
        'name' => 'signin',//插件名称标识
        'title' => '每日签到',//插件中文名
        'description' => '每日签到奖励巩固会员',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'signinSetting',//
        'config_admin_hook' => 'signinSetting', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/20190713141653.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782136.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782259.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '每日签到',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '每日签到奖励巩固会员',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'signinSetting',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '签到设置',
            'parent_module_name' => '每日签到',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭后，所有每日签到均不生效。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'signinSetting',
            'module' => 'platform'
        ],
        [
            'module_name' => '签到明细',
            'parent_module_name' => '每日签到', //上级模块名称 确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '签到明细', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'signinList',
            'module' => 'platform'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform') {
            $this->assign('saveSettingUrl', __URL(addons_url_platform('signin://Signin/saveSetting')));
            $this->assign('signinListUrl', __URL(addons_url_platform('signin://Signin/signinList')));
        }
    }
    
    public function signinList()
    {
        $this->fetch('template/' . $this->module . '/signinList');
    }
    
    public function signinSetting()
    {
        $configModel = new AddonsConfigModel();
        $config = $configModel->getInfo(['addons' => 'signin','website_id' => $this->website_id], 'is_use');
        $signin_server = new SigninServer();
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $info = $signin_server->getSignInDetail($condition);
        $info['is_use'] = $config['is_use'];
        $this->assign('info',$info);
        $condition['sign_in_id'] = $info['sign_in_id'];
        $list = $signin_server->getSignInRule($condition);
        $this->assign('list',$list);
        $prize['coupontype']['is_use'] = getAddons('coupontype', $this->website_id);
        $prize['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id);
        $prizelist = $signin_server->prizeList($condition);
        $prize['coupontype']['list'] = $prizelist['coupontype'];
        $prize['giftvoucher']['list'] = $prizelist['giftvoucher'];
        $this->assign('prize',$prize);
       $this->fetch('template/' . $this->module . '/signinSetting');
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