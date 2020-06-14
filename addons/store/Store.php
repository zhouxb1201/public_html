<?php

namespace addons\store;

use addons\Addons;
use addons\store\server\Store as storeServer;
use data\service\Config;

/**
 * Class Goodhelper
 * @package addons\goodhelper
 */
class Store extends Addons
{
    public $info = array(
        'name' => 'store',//插件名称标识
        'title' => 'O2O门店',//插件中文名
        'description' => '线上消费线下体验核销',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'no_set' => 1,//是否有应用设置
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'storeList',//
        'config_admin_hook' => 'storeList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554196943.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782074.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782208.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => 'O2O门店',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '线上消费线下体验核销',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'storeList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '门店列表',
            'parent_module_name' => 'O2O门店', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '当门店列表有“营业”状态的门店时，商城购物将可选择“线下自提”的收货方式', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'storeList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加门店',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addStore',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑门店',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'editStore',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除门店',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteStore',
            'module' => 'platform'
        ],
        [
            'module_name' => '店员列表',
            'parent_module_name' => 'O2O门店', //上级模块名称 确定上级目录
            'sort' => 1, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '店员账户用于登录“O2O店员端”，店员端可核销订单、核销礼品、核销消费卡等。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'assistantList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加店员',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addAssistant',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑店员',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'editAssistant',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除店员',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteAssistant',
            'module' => 'platform'
        ],
        [
            'module_name' => '岗位列表',
            'parent_module_name' => 'O2O门店', //上级模块名称 确定上级目录
            'sort' => 2, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '岗位主要控制店员在O2O店员端里面的权限，可根据实际情况划分。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'jobsList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加岗位',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addJobs',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑岗位',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'editJobs',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除岗位',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteJobs',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => 'O2O门店', //上级模块名称 确定上级目录
            'sort' => 3, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '基础设置', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'storeSetting',
            'module' => 'platform'
        ],
        [
            'module_name' => 'O2O门店',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '线上消费线下体验核销',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'storeList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '门店列表',
            'parent_module_name' => 'O2O门店', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '当门店列表有“营业”状态的门店时，商城购物将可选择“线下自提”的收货方式', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'storeList',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加门店',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addStore',
            'module' => 'admin'
        ],
        [
            'module_name' => '编辑门店',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'editStore',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除门店',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteStore',
            'module' => 'admin'
        ],
        [
            'module_name' => '店员列表',
            'parent_module_name' => 'O2O门店', //上级模块名称 确定上级目录
            'sort' => 1, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '店员账户用于登录“O2O店员端”，店员端可核销订单、核销礼品、核销消费卡等。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'assistantList',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加店员',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addAssistant',
            'module' => 'admin'
        ],
        [
            'module_name' => '编辑店员',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'editAssistant',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除店员',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteAssistant',
            'module' => 'admin'
        ],
        [
            'module_name' => '岗位列表',
            'parent_module_name' => 'O2O门店', //上级模块名称 确定上级目录
            'sort' => 2, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '岗位主要控制店员在O2O店员端里面的权限，可根据实际情况划分。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'jobsList',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加岗位',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addJobs',
            'module' => 'admin'
        ],
        [
            'module_name' => '编辑岗位',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'editJobs',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除岗位',
            'parent_module_name' => 'O2O门店',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteJobs',
            'module' => 'admin'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => 'O2O门店', //上级模块名称 确定上级目录
            'sort' => 3, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '基础设置', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'storeSetting',
            'module' => 'admin'
        ],
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform' || $this->module == 'admin') {
            $this->assign('storeListUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/storeList')));
            $this->assign('assistantListUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/assistantList')));
            $this->assign('jobsListUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/jobsList')));
            $this->assign('deleteStoreUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/deleteStore')));
            $this->assign('deleteAssistantUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/deleteAssistant')));
            $this->assign('deleteJobsUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/deleteJobs')));
            $this->assign('addOrUpdateAssistantUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/addOrUpdateAssistant')));
            $this->assign('addOrUpdateStoreUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/addOrUpdateStore')));
            $this->assign('addOrUpdateJobsUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/addOrUpdateJobs')));
            $this->assign('getProvinceUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/getProvince')));
            $this->assign('getCityUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/getCity')));
            $this->assign('getDistrictUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/getDistrict')));
            $this->assign('enableOrUnableUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/enableOrUnable')));
            $this->assign('getStoreSetUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/getStoreSet')));
            $this->assign('storeSetUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/storeSet')));
            $this->assign('saveSettingUrl', __URL(call_user_func('addons_url_' . $this->module, 'store://Store/saveSetting')));
        }
    }
    /*
     * 门店列表
     */
    public function storeList()
    {
        $this->fetch('template/' . $this->module . '/storeList');
    }
    /*
     * 添加门店
     */
    public function addStore()
    {
        $this->fetch('template/' . $this->module . '/addStore');
    }
    /**
     * 编辑门店
     */
    public function editStore(){
        $store_id = request()->get('store_id');
        $storeServer = new storeServer();
        $storeInfo = $storeServer->storeDetail($store_id);
        $this->assign('store_info', $storeInfo);
        $this->fetch('template/' . $this->module . '/addStore');
    }
    /*
     * 店员列表
     */
    public function assistantList()
    {
        $this->fetch('template/' . $this->module . '/assistantList');
    }
    /*
     * 添加店员
     */
    public function addAssistant()
    {
        $storeServer = new storeServer();
        $storeList = $storeServer->storeList(1, 0, ['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
        $this->assign("store_list", $storeList["data"]);
        $jobsList = $storeServer->jobsList(1, 0, ['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
        $this->assign("jobs_list", $jobsList["data"]);
        $this->fetch('template/' . $this->module . '/addAssistant');
    }
    /**
     * 编辑店员
     */
    public function editAssistant(){
        $assistant_id = request()->get('assistant_id');
        $storeServer = new storeServer();
        $storeList = $storeServer->storeList(1, 0, ['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
        $this->assign("store_list", $storeList["data"]);
        $jobsList = $storeServer->jobsList(1, 0, ['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
        $this->assign("jobs_list", $jobsList["data"]);
        $assistantInfo = $storeServer->assistantDetail($assistant_id);
        $this->assign('assistant_info', $assistantInfo);
        $this->fetch('template/' . $this->module . '/addAssistant');
    }
    /*
     * 岗位列表
     */
    public function jobsList()
    {
        $this->fetch('template/' . $this->module . '/jobsList');
    }
    /*
     * 添加岗位
     */
    public function addJobs()
    {
        $this->fetch('template/' . $this->module . '/addJobs');
    }
    /**
     * 编辑岗位
     */
    public function editJobs(){
        $jobs_id = request()->get('jobs_id');
        $storeServer = new storeServer();
        $jobInfo = $storeServer->jobDetail($jobs_id);
        $this->assign('jobs_info', $jobInfo);
        $this->fetch('template/' . $this->module . '/addJobs');
    }
    /**
     * 基础设置
     */
    public function storeSetting()
    {
        $storeServer = new storeServer();
        $store_set = $storeServer->getStoreSet($this->instance_id);
        $config = new Config();
        $printerInfo = [];
        $printConfig = $config->getConfig($this->instance_id,'PRINTER_INFO');
        if($printConfig){
            $printerInfo = json_decode($printConfig['value'], true);
        }
        $this->assign('info', $store_set);
        $this->assign('printerInfo', $printerInfo);
        $this->fetch('template/' . $this->module . '/storeSetting');
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