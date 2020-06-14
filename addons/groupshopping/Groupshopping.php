<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:35
 */

namespace addons\groupshopping;

use addons\Addons;
use data\model\AddonsConfigModel;
use addons\groupshopping\server\GroupShopping as groupServer;

class Groupshopping extends Addons {

    protected static $addons_name = 'groupshopping';
    public $info = [
        'name' => 'groupshopping', //插件名称标识
        'title' => '拼团', //插件中文名
        'description' => '多人拼团，裂变式营销', //插件描述
        'status' => 1, //状态 1使用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', //版本号
        'has_addonslist' => 1, //是否有下级插件
        'content' => '', //插件的详细介绍或者使用方法
        'config_hook' => 'groupShoppingList', //
        'config_admin_hook' => 'groupShoppingList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197084.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782139.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782262.png',
    ]; //设置文件单独的钩子
    public $menu_info = [
        //platform
        [
            'module_name' => '拼团',
            'parent_module_name' => '应用', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '多人拼团，裂变式营销', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '拼团列表',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '参与拼团的商品有两种购买方式，一种是按原价单独购买，另一种是拼团购买，拼团购买必须成团才可以发货，超出时限未成团则拼团失败，支付金额自动退款，参加拼团的商品不能参与“限时折扣、秒杀、砍价、预售”等活动。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingList',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 1, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '关闭后，所有拼团活动均不生效，可设置独立的分销分红规则，优先级为：商品独立>活动独立>分销/分红设置。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingSetting',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '添加拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addGroupShopping',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '修改拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'updateGroupShopping',
            'module' => 'platform'
        ],
        [
            'module_name' => '拼团记录',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '拼团记录', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupRecord',
            'module' => 'platform'
        ],
        [
            'module_name' => '拼团记录详情',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupRecordDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '拼团详情',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '关闭拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '关闭拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingOff',
            'module' => 'platform'
        ],
        [
            'module_name' => '开启拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '开启拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingOn',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '删除拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingDelete',
            'module' => 'platform'
        ],
        [
            'module_name' => '选择商品',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 0, //是否有控制权限
            'hook_name' => 'modalGroupShoppingGoodsList',
            'module' => 'platform'
        ],
        //admin
        [
            'module_name' => '拼团',
            'parent_module_name' => '应用', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '多人拼团，裂变式营销', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '拼团列表',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '参与拼团的商品有两种购买方式，一种是按原价单独购买，另一种是拼团购买，拼团购买必须成团才可以发货，超出时限未成团则拼团失败，支付金额自动退款，参加拼团的商品不能参与“限时折扣、秒杀、砍价、预售”等活动。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingList',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '添加拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addGroupShopping',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '修改拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'updateGroupShopping',
            'module' => 'admin'
        ],
        [
            'module_name' => '拼团记录',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '拼团记录', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupRecord',
            'module' => 'admin'
        ],
        [
            'module_name' => '拼团记录详情',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupRecordDetail',
            'module' => 'admin'
        ],
        [
            'module_name' => '拼团详情',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingDetail',
            'module' => 'admin'
        ],
        [
            'module_name' => '关闭拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '关闭拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingOff',
            'module' => 'admin'
        ],
        [
            'module_name' => '开启拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '开启拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingOn',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除拼团',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '删除拼团', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'groupShoppingDelete',
            'module' => 'admin'
        ],
        [
            'module_name' => '选择商品',
            'parent_module_name' => '拼团', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 0, //是否有控制权限
            'hook_name' => 'modalGroupShoppingGoodsList',
            'module' => 'admin'
        ],
    ];

    public function __construct() {
        parent::__construct();
        self::$addons_name = strtolower($this->info['name']);
        $distributionStatus = getAddons('distribution', $this->website_id);
        $globalStatus = getAddons('globalbonus', $this->website_id);
        $areaStatus = getAddons('areabonus', $this->website_id);
        $teamStatus = getAddons('teambonus', $this->website_id);
        $this->assign('has_distribution', $distributionStatus);
        $this->assign('has_global', $globalStatus);
        $this->assign('has_area', $areaStatus);
        $this->assign('has_team', $teamStatus);
        if($this->module=='platform' || $this->module == 'admin'){
            $this->assign('addGroupShoppingUrl', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/addGroupShopping')));
            $this->assign('modalGroupShoppingGoodsList', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/modalGroupShoppingGoodsList')));
            $this->assign('getGroupMemberListUrl', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/getGroupMemberList')));
            $this->assign('groupShoppingListUrl', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/groupShoppingList')));
            $this->assign('deleteGroupShoppingUrl', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/groupShoppingDelete')));
            $this->assign('closeGroupShoppingUrl', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/groupShoppingOff')));
            $this->assign('openGroupShoppingUrl', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/groupShoppingOn')));
            $this->assign('saveGroupShoppingSettingUrl', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/saveGroupShoppingSetting')));
            $this->assign('getSkuListUrl', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/getSkuList')));
            $this->assign('getGroupRecordListUrl', __URL(call_user_func('addons_url_' . $this->module, 'groupshopping://Groupshopping/getGroupRecordList')));
        }
    }

    public function groupShoppingList() {
        $this->fetch('template/' . $this->module . '/groupShoppingList');
    }

    public function groupShoppingSetting() {
        $addons_config_model = new AddonsConfigModel();
        $addons_info = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => self::$addons_name]);
        $addons_data = json_decode($addons_info['value'], true) ?: [];
        $addons_data['is_use'] = $addons_info['is_use'] ?: 0;
        $this->assign('addons_data', $addons_data);
        $this->fetch('template/' . $this->module . '/groupShoppingSetting');
    }

    public function addGroupShopping() {
        $this->fetch('template/' . $this->module . '/addGroupShopping');
    }

    public function updateGroupShopping() {
        $group_id = request()->get('group_id');
        $groupServer = new groupServer();
        $groupDetail = $groupServer->groupShoppingDetail($group_id);
        $this->assign('group_id', $group_id);
        $this->assign('groupDetail', $groupDetail);
        $this->fetch('template/' . $this->module . '/updateGroupShopping');
    }

    public function groupRecord() {
        $group_id = request()->get('group_id');
        $this->assign('group_id', $group_id);
        $groupServer = new groupServer();
        $groupDetail = $groupServer->groupShoppingDetail($group_id);
        $this->assign('groupDetail', $groupDetail);
        $this->fetch('template/' . $this->module . '/groupRecord');
    }

    public function groupShoppingDetail() {
        $group_id = request()->get('group_id');
        $groupServer = new groupServer();
        $groupDetail = $groupServer->groupShoppingDetail($group_id);
        $this->assign('groupDetail', $groupDetail);
        $this->assign('group_id', $group_id);
        $this->fetch('template/' . $this->module . '/groupShoppingDetail');
    }
    
    public function groupRecordDetail() {
        $record_id = request()->get('record_id');
        $groupServer = new groupServer();
        $groupRecord = $groupServer->groupRecordDetail($record_id);
        $this->assign('record_id', $record_id);
        $this->assign('groupRecord', $groupRecord);
        $this->fetch('template/' . $this->module . '/groupRecordDetail');
    }
    
    public function modalGroupShoppingGoodsList()
    {
        $this->fetch('template/' . $this->module . '/groupShoppingGoodsDialog');
    }

    public function install() {
        return true;
    }

    public function uninstall() {
        return true;
    }

}
