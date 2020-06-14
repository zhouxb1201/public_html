<?php
namespace addons\festivalcare;

use addons\Addons;
use data\model\AddonsConfigModel;
use addons\festivalcare\server\FestivalCare as FestivalCareServer;
use data\service\Member as MemberService;

class Festivalcare extends Addons
{
    public $info = array(
        'name' => 'festivalcare',//插件名称标识
        'title' => '节日关怀',//插件中文名
        'description' => '根据不同节日发放不同的关怀礼品，激活会员',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'festivalcareList',//
        'config_admin_hook' => 'festivalcareList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197065.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782115.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782236.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '节日关怀',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '根据不同节日发放不同的关怀礼品，激活会员',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'festivalcareList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '活动列表',
            'parent_module_name' => '节日关怀', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '设置指定的时间通过微信公众号给会员推送奖品进行节日关怀。帮助商家增强客户粘性的新型营销应用。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'festivalcareList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改节日关怀',
            'parent_module_name' => '节日关怀',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可设置一个时间通过微信公众号给会员推送奖品，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”，若同时存在多档活动，则按优先级高的为准。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateFestivalcare',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加节日关怀',
            'parent_module_name' => '节日关怀',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可设置一个时间通过微信公众号给会员推送奖品，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”，若同时存在多档活动，则按优先级高的为准。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addFestivalcare',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除节日关怀',
            'parent_module_name' => '节日关怀',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除节日关怀',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteFestivalcare',
            'module' => 'platform'
        ],
        [
            'module_name' => '节日关怀中奖记录',
            'parent_module_name' => '节日关怀',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '节日关怀中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyFestivalcare',
            'module' => 'platform'
        ],
        [
            'module_name' => '节日关怀详情',
            'parent_module_name' => '节日关怀',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'festivalcareDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '节日关怀',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭后，所有节日关怀活动均不生效。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'festivalcareSetting',
            'module' => 'platform'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform') {
            $this->assign('festivalcareListUrl', __URL(addons_url_platform('festivalcare://Festivalcare/festivalcareList')));
            $this->assign('addFestivalcareUrl', __URL(addons_url_platform('festivalcare://Festivalcare/addFestivalcare')));
            $this->assign('updateFestivalcareUrl', __URL(addons_url_platform('festivalcare://Festivalcare/updateFestivalcare')));
            $this->assign('deleteFestivalcareUrl', __URL(addons_url_platform('festivalcare://Festivalcare/deleteFestivalcare')));
            $this->assign('historyFestivalcareUrl', __URL(addons_url_platform('festivalcare://Festivalcare/historyFestivalcare')));
            $this->assign('prizeTypeUrl', __URL(addons_url_platform('festivalcare://Festivalcare/prizeType')));
            $this->assign('saveSettingUrl', __URL(addons_url_platform('festivalcare://Festivalcare/saveSetting')));
        }
    }
    
    public function festivalcareList()
    {
        $this->fetch('template/' . $this->module . '/festivalcareList');
    }
    
    public function addFestivalcare()
    {
        $info = $setup = [];
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id, true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id, true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $member = new MemberService();
        $where['website_id'] = $this->website_id;
        $group_list = $member->getMemberGroupList(1, 0, $where, 'group_id desc');
        $this->assign('group_list', $group_list);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updateFestivalcare');
    }
    
    public function updateFestivalcare()
    {
        $info = $setup = [];
        $condition['festival_care_id'] = (int)input('get.festival_care_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $festival_server = new FestivalCareServer();
        $info = $festival_server->getFestivalCareDetail($condition,1);
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id, true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id, true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $member = new MemberService();
        $where['website_id'] = $this->website_id;
        $group_list = $member->getMemberGroupList(1, 0, $where, 'group_id desc');
        $this->assign('group_list', $group_list);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updateFestivalcare');
    }
    
    public function festivalcareDetail()
    {
        $condition['festival_care_id'] = (int)input('get.festival_care_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $festival_server = new FestivalCareServer();
        $info = $festival_server->getFestivalCareDetail($condition,2);
        $this->assign('info',$info);
        $this->fetch('template/' . $this->module . '/festivalcareDetails');
    }
    
    public function historyFestivalcare()
    {
        $condition['festival_care_id'] = (int)input('get.festival_care_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $festival_server = new FestivalCareServer();
        $info = $festival_server->getFestivalCareDetail($condition);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/historyFestivalcare');
    }
    
    public function festivalcareSetting()
    {
        $configModel = new AddonsConfigModel();
        $info = $configModel->getInfo([
            'addons' => 'festivalcare',
            'website_id' => $this->website_id
        ], 'is_use');
        $this->assign('is_use', $info['is_use']);
       $this->fetch('template/' . $this->module . '/festivalcareSetting');
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