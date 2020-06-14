<?php
namespace addons\followgift;

use addons\Addons;
use data\model\AddonsConfigModel;
use addons\followgift\server\FollowGift as FollowGiftServer;

class Followgift extends Addons
{
    public $info = array(
        'name' => 'followgift',//插件名称标识
        'title' => '关注有礼',//插件中文名
        'description' => '首次关注公众号后赠送礼品',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'followgiftList',//
        'config_admin_hook' => 'followgiftList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197057.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782106.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782230.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '关注有礼',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '首次关注公众号后赠送礼品',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'followgiftList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '活动列表',
            'parent_module_name' => '关注有礼', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '用户首次关注公众号成功后赠送礼品的活动，帮助商家增强客户粘性的新型营销应用。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'followgiftList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改关注有礼',
            'parent_module_name' => '关注有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '目前支持首次关注公众号成功后送礼，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”，若同时存在多档活动，则按优先级高的为准。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateFollowgift',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加关注有礼',
            'parent_module_name' => '关注有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '目前支持首次关注公众号成功后送礼，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”，若同时存在多档活动，则按优先级高的为准。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addFollowgift',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除关注有礼',
            'parent_module_name' => '关注有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除关注有礼',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteFollowgift',
            'module' => 'platform'
        ],
        [
            'module_name' => '关注有礼中奖记录',
            'parent_module_name' => '关注有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关注有礼中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyFollowgift',
            'module' => 'platform'
        ],
        [
            'module_name' => '关注有礼详情',
            'parent_module_name' => '关注有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'followgiftDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '关注有礼',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭后，所有关注有礼活动均不生效。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'followgiftSetting',
            'module' => 'platform'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform') {
            $this->assign('followgiftListUrl', __URL(addons_url_platform('followgift://Followgift/followgiftList')));
            $this->assign('addFollowgiftUrl', __URL(addons_url_platform('followgift://Followgift/addFollowgift')));
            $this->assign('updateFollowgiftUrl', __URL(addons_url_platform('followgift://Followgift/updateFollowgift')));
            $this->assign('deleteFollowgiftUrl', __URL(addons_url_platform('followgift://Followgift/deleteFollowgift')));
            $this->assign('historyFollowgiftUrl', __URL(addons_url_platform('followgift://Followgift/historyFollowgift')));
            $this->assign('prizeTypeUrl', __URL(addons_url_platform('followgift://Followgift/prizeType')));
            $this->assign('saveSettingUrl', __URL(addons_url_platform('followgift://Followgift/saveSetting')));
        }
    }
    
    public function followgiftList()
    {
        $this->fetch('template/' . $this->module . '/followgiftList');
    }
    
    public function addFollowgift()
    {
        $info = $setup = [];
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id, true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id, true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updateFollowgift');
    }
    
    public function updateFollowgift()
    {
        $info = $setup = [];
        $condition['follow_gift_id'] = (int)input('get.follow_gift_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $follow_server = new FollowGiftServer();
        $info = $follow_server->getFollowGiftDetail($condition,1);
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id,true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id,true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updateFollowgift');
    }
    
    public function followgiftDetail()
    {
        $condition['follow_gift_id'] = (int)input('get.follow_gift_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $follow_server = new FollowGiftServer();
        $info = $follow_server->getFollowGiftDetail($condition,2);
        $this->assign('info',$info);
        $this->fetch('template/' . $this->module . '/followgiftDetails');
    }
    
    public function historyFollowgift()
    {
        $condition['follow_gift_id'] = (int)input('get.follow_gift_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $follow_server = new FollowGiftServer();
        $info = $follow_server->getFollowGiftDetail($condition);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/historyFollowgift');
    }
    
    public function followgiftSetting()
    {
        $configModel = new AddonsConfigModel();
        $info = $configModel->getInfo([
            'addons' => 'followgift',
            'website_id' => $this->website_id
        ], 'is_use');
        $this->assign('is_use', $info['is_use']);
       $this->fetch('template/' . $this->module . '/followgiftSetting');
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