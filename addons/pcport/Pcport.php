<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:35
 */

namespace addons\pcport;

use addons\Addons;
use data\extend\custom\Common;
use data\model\SysPcCustomCodeLogoModel;
use data\model\SysPcCustomNavModel;
use data\model\SysPcCustomStyleConfigModel;

class Pcport extends Addons {

    public $dir;
    public $dirDefault;
    public $dir_common; //公共部分路径
    public $dir_shop_common; //店铺公共部分路径
    public $com;
    protected static $addons_name = 'pcport';
    public $info = [
        'name' => 'pcport', //插件名称标识
        'title' => 'PC商城', //插件中文名
        'description' => '管理维护PC商城', //插件描述
        'status' => 1, //状态 1使用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', //版本号
        'has_addonslist' => 1, //是否有下级插件
        'content' => '', //插件的详细介绍或者使用方法
        'config_hook' => 'pcCustomTemplateList', //
        'config_admin_hook' => 'pcCustomTemplateList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554196996.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782079.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782213.png',
    ]; //设置文件单独的钩子
    public $menu_info = [
        //platform
        [
            'module_name' => 'PC商城',
            'parent_module_name' => '应用', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '管理维护PC商城', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'pcCustomTemplateList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => 'PC商城装修',
            'parent_module_name' => 'PC商城', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => 'PC商城支持商城首页、店铺首页（需购买多店应用）、商品详情页、会员中心、自定义页装修。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'pcCustomTemplateList',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => 'PC商城', //上级模块名称 用来确定上级目录
            'sort' => 1, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '可手动开启与关闭PC商城，关闭商城需要填写原因，会员访问商城时会显示商城关闭原因。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'pcSetting',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加页面',
            'parent_module_name' => 'PC商城', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '添加页面', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'createTemplateDialog',
            'module' => 'platform'
        ],
        [
            'module_name' => '页面装修',
            'parent_module_name' => 'PC商城', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'pcCustomTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除页面',
            'parent_module_name' => 'PC商城', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '删除页面', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'deletePcCustomTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => 'PC商城',
            'parent_module_name' => '商城', //上级模块名称 用来确定上级目录
            'sort' => 1, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => 'PC商城支持商城首页、店铺首页（需购买多店应用）、商品详情页 、自定义页装修。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'pcCustomTemplateList',
            'module' => 'platform'
        ],
        //admin
        [
            'module_name' => 'PC商城',
            'parent_module_name' => '应用', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '管理维护PC商城', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'pcCustomTemplateList',
            'module' => 'admin',
            'is_admin_main' => 1
        ],
        [
            'module_name' => 'PC商城装修',
            'parent_module_name' => 'PC商城', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => 'PC商城支持店铺首页、商品详情页、自定义页装修。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'pcCustomTemplateList',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加页面',
            'parent_module_name' => 'PC商城', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '添加页面', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'createTemplateDialog',
            'module' => 'admin'
        ],
        [
            'module_name' => '页面装修',
            'parent_module_name' => 'PC商城', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'pcCustomTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除页面',
            'parent_module_name' => 'PC商城', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '删除页面', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'deletePcCustomTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => 'PC商城',
            'parent_module_name' => '店铺', //上级模块名称 用来确定上级目录
            'sort' => 2, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => 'PC商城支持店铺首页、商品详情页、自定义页装修。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'pcCustomTemplateList',
            'module' => 'admin'
        ]
    ];

    public function __construct() {
        parent::__construct();
        self::$addons_name = strtolower($this->info['name']);
        $hasHelpcenter = getAddons('helpcenter', $this->website_id); //帮助中心应用是否存在
        $this->assign("apply_realm", $this->realm_ip);
        $this->assign('hasHelpcenter', $hasHelpcenter);
        $this->assign('instance_id', $this->instance_id);
        $this->dir = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/shop_' . $this->instance_id;
        $this->dirDefault = ROOT_PATH . 'public/static/custompc/data/default/tem';
        $this->dir_shop_common = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/shop_' . $this->instance_id . '/common';
        $this->dir_common = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/common';
        $this->com = new Common($this->instance_id, $this->website_id);
        if ($this->module == 'platform' || $this->module == 'admin') {
            $this->assign('pcSettingUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/pcsetting')));
            $this->assign('pcCustomTemplateListUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/pccustomtemplatelist')));
            $this->assign('createTemplateDialogUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/createtemplatedialog')));
            $this->assign('pcDefaultTemplateListUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/pcdefaulttemplatelist')));
            $this->assign('createTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/createTemplate')));
            $this->assign('deletepccustomtemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/deletepccustomtemplate')));
            $this->assign('setdefaultcustomtemplatepcUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/setdefaultcustomtemplatepc')));
            $this->assign('savePcSettingUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/savepcportsetting')));
            $this->assign('downloadmodalUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/downloadmodal')));
            $this->assign('backmodalUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/backmodal')));
            $this->assign('deletepccustomtemplatetopUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/deletepccustomtemplatetop')));
            $this->assign('hotUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/hot')));
            $this->assign('homeAdvUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/homeAdv')));
            $this->assign('homefloorUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/homefloor')));
            $this->assign('customUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/custom')));
            $this->assign('homeBannerUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/homeBanner')));
            $this->assign('bannerUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/banner')));
            $this->assign('singlebannerUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/singlebanner')));
            $this->assign('navmodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/navmode')));
            $this->assign('homeheadermodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/homeheadermode')));
            $this->assign('helpmodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/helpmode')));
            $this->assign('rightmodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/rightmode')));
            $this->assign('linkmodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/linkmode')));
            $this->assign('copymodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/copymode')));
            $this->assign('goodsinfoUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/goodsinfo')));
            $this->assign('servicemodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/servicemode')));
            $this->assign('shopheadermodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/shopheadermode')));
            $this->assign('homeBannerResponseUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/homeBannerResponse')));
            $this->assign('navmodebackUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/navmodeback')));
            $this->assign('homeheadermodebackUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/homeheadermodeback')));
            $this->assign('addmoduleUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/addmodule')));
            $this->assign('addsinglebannerUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/addsinglebanner')));
            $this->assign('homefloorresponseUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/homefloorresponse')));
            $this->assign('homeadvinsertUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/homeadvinsert')));
            $this->assign('homeshopUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/homeshop')));
            $this->assign('changedgoodsUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/changedgoods')));
            $this->assign('servicemodebackUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/servicemodeback')));
            $this->assign('helpmodebackUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/helpmodeback')));
            $this->assign('linkmodebackUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/linkmodeback')));
            $this->assign('copymodebackUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/copymodeback')));
            $this->assign('rightmodebackUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/rightmodeback')));
            $this->assign('fileputvisualUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/fileputvisual')));
            $this->assign('navremoveUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/navremove')));
            $this->assign('pageEditModalUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/pageeditmodal')));
            $this->assign('changestyleUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/changeStyle')));
            $this->assign('edittemUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/editTem')));
            $this->assign('seoConfigUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/seoConfig')));
            $this->assign('loginWeiXinConfigUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/loginWeiXinConfig')));
        }
    }

    public function pcSetting() {
        $this->fetch('template/' . $this->module . '/pcportSetting');
    }

    /**
     * pc端自定义模板
     *
     * @return list
     */
    public function pcCustomTemplateList() {
        $this->com->createTem();
        $typeList = $this->com->getTemplateTypeName('', 1);
        $this->assign("typeList", $typeList);
        $this->assign("instance_id", $this->instance_id);
        $this->fetch('template/' . $this->module . '/customTemplateListPc');
    }

    /**
     * pc端自定义模板装修
     *
     * @return list
     */
    public function pcCustomTemplate() {
        $code = trim(request()->get('code', ''));
        $type = trim(request()->get('type', ''));
        $ntype = 'index';
        if ($type != 'home_templates') {
            $ntype = 'shop';
        }
         if ($type == 'goods_templates') {
            $goods_style = 1;
            $this->assign('goods_style', $goods_style );
        }
        $shop_name = request()->get('shop_name', '');
        $des = $this->dir . '/' . $type . '/';
        $yl_url = __URLS("SHOP_MAIN/index/index", "suffix=" . $code . "&temp_type=" . $type . "&website_id=" . $this->website_id . "&instance_id=" . $this->instance_id);
        if (!empty($code)) {
            $codeLogo = new SysPcCustomCodeLogoModel();
            $setpic = $codeLogo->getInfo(['code' => $code, 'website_id' => $this->website_id]);
            if (!empty($setpic['logo'])) {
                $logo_pic = __IMG($setpic['logo']);
            } else {
                $list = $this->website->getWebSiteInfo();
                $logo_pic = __IMG($list['logo']);
            }
        } else {
            $logo_pic = __ROOT__ . '/public/static/images/Logo.png';
        }
        //        $this->get_down_hometemplates($code);
        if (!file_exists($des . '/' . $code . '/nav_html.php') && !file_exists($des . '/' . $code . '/temp/nav_html.php')) {
            $nav = new SysPcCustomNavModel();
            $navigator = $nav->getQuery(['type' => $ntype, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id], 'id,name,ifshow,vieworder,opennew,url,type', '');
        }
        $styleModel = new SysPcCustomStyleConfigModel();
        $style = $styleModel->getInfo(['website_id' => $this->website_id]);
        $this->assign('style', $style['style']);
        $info = $this->com->get_seller_template_info($code, $type);
        if ($info) {
            $shop_name = $info['name'];
        }

        $filename = '';
        $is_temp = 0;
        if (file_exists($des . '/' . $code . '/temp/pc_page.php')) {
            $filename = $des . '/' . $code . '/temp/pc_page.php';
            $is_temp = 1;
        } else {
            $filename = $des . '/' . $code . '/pc_page.php';
        }
        $filenamebottom = '';
        if ($this->instance_id) {
            if (file_exists($this->dir_common . '/bottom_html.php')) {
                $filenamebottom = $this->dir_common . '/bottom_html.php';
            }
        } else {
            if (file_exists($this->dir_common . '/temp/bottom.php')) {
                $filenamebottom = $this->dir_common . '/temp/bottom.php';
                $is_temp = 1;
            } else {
                $filenamebottom = $this->dir_common . '/bottom.php';
            }
        }

        $filenamebanner = '';
        if (file_exists($this->dir_shop_common . '/temp/shopbanner.php')) {
            $filenamebanner = $this->dir_shop_common . '/temp/shopbanner.php';
            $is_temp = 1;
        } else {
            $filenamebanner = $this->dir_shop_common . '/shopbanner.php';
        }
        $arr['tem'] = $code;
        $arr['out'] = $this->com->get_html_file($filename);
        $arr['bottom'] = $this->com->get_html_file($filenamebottom);
        $arr['shopbanner'] = $this->com->get_html_file($filenamebanner);
        $pc_page = $arr;
        $vis_section = 'vis_home';
        $this->assign('shop_name', $shop_name);
        $this->assign('vis_section', $vis_section);
        $this->assign('pc_page', $pc_page);
        $this->assign('navigator', $navigator);
        $this->assign('yl_url', $yl_url);
        $this->assign('is_temp', $is_temp);
        $this->assign('code', $code);
        $this->assign('type', $type);
        $this->assign('logo_pic', $logo_pic);
        $this->fetch('template/' . $this->module . '/customTemplatePc');
    }

    public function install() {
        return true;
    }

    public function uninstall() {
        return true;
    }

}
