<?php
namespace addons\wheelsurf;

use addons\Addons;
use data\model\AddonsConfigModel;
use addons\wheelsurf\server\Wheelsurf as WheelsurfServer;

class Wheelsurf extends Addons
{
    public $info = array(
        'name' => 'wheelsurf',//插件名称标识
        'title' => '幸运大转盘',//插件中文名
        'description' => '抽奖转盘，增强粘度，中奖概率轻松定',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'wheelsurfList',//
        'config_admin_hook' => 'wheelsurfList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197117.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782178.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782296.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '幸运大转盘',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '抽奖转盘，增强粘度，中奖概率轻松定',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'wheelsurfList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '大转盘列表',
            'parent_module_name' => '幸运大转盘', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '通过消耗积分参与大转盘抽奖为主题的活动，可设置多个奖品及多种奖品类型进行选择。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'wheelsurfList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改大转盘',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可选择6个奖项、8个奖项、10个奖项、12个奖项的转盘活动，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateWheelsurf',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加大转盘',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可选择6个奖项、8个奖项、10个奖项、12个奖项的转盘活动，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addWheelsurf',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除大转盘',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除大转盘',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteWheelsurf',
            'module' => 'platform'
        ],
        [
            'module_name' => '大转盘中奖记录',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '大转盘中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyWheelsurf',
            'module' => 'platform'
        ],
        [
            'module_name' => '大转盘详情',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'wheelsurfDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭后，所有大转盘活动均不生效。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'wheelsurfSetting',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '幸运大转盘',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '抽奖转盘，增强粘度，中奖概率轻松定',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'wheelsurfList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '大转盘列表',
            'parent_module_name' => '幸运大转盘', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '通过消耗积分参与大转盘抽奖为主题的活动，可设置多个奖品及多种奖品类型进行选择。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'wheelsurfList',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改大转盘',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可选择6个奖项、8个奖项、10个奖项、12个奖项的转盘活动，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateWheelsurf',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加大转盘',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可选择6个奖项、8个奖项、10个奖项、12个奖项的转盘活动，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addWheelsurf',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除大转盘',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除大转盘',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteWheelsurf',
            'module' => 'admin'
        ],
        [
            'module_name' => '大转盘中奖记录',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '大转盘中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyWheelsurf',
            'module' => 'admin'
        ],
        [
            'module_name' => '大转盘详情',
            'parent_module_name' => '幸运大转盘',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'wheelsurfDetail',
            'module' => 'admin'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        $this->assign('receiveUrl', __URLS('/wap/wheelsurf/centre'));
        if ($this->module == 'platform') {
            $this->assign('wheelsurfListUrl', __URL(addons_url_platform('wheelsurf://Wheelsurf/wheelsurfList')));
            $this->assign('addWheelsurfUrl', __URL(addons_url_platform('wheelsurf://Wheelsurf/addWheelsurf')));
            $this->assign('updateWheelsurfUrl', __URL(addons_url_platform('wheelsurf://Wheelsurf/updateWheelsurf')));
            $this->assign('deleteWheelsurfUrl', __URL(addons_url_platform('wheelsurf://Wheelsurf/deleteWheelsurf')));
            $this->assign('historyWheelsurfrUrl', __URL(addons_url_platform('wheelsurf://Wheelsurf/historyWheelsurf')));
            $this->assign('prizeTypeUrl', __URL(addons_url_platform('wheelsurf://Wheelsurf/prizeType')));
            $this->assign('saveSettingUrl', __URL(addons_url_platform('wheelsurf://Wheelsurf/saveSetting')));
        } else if ($this->module == 'admin') {
            $this->assign('wheelsurfListUrl', __URL(addons_url_admin('wheelsurf://Wheelsurf/wheelsurfList')));
            $this->assign('addWheelsurfUrl', __URL(addons_url_admin('wheelsurf://Wheelsurf/addWheelsurf')));
            $this->assign('updateWheelsurfUrl', __URL(addons_url_admin('wheelsurf://Wheelsurf/updateWheelsurf')));
            $this->assign('deleteWheelsurfUrl', __URL(addons_url_admin('wheelsurf://Wheelsurf/deleteWheelsurf')));
            $this->assign('historyWheelsurfrUrl', __URL(addons_url_admin('wheelsurf://Wheelsurf/historyWheelsurf')));
            $this->assign('prizeTypeUrl', __URL(addons_url_admin('wheelsurf://Wheelsurf/prizeType')));
        }
    }
    
    public function wheelsurfList()
    {
        $this->fetch('template/' . $this->module . '/wheelsurfList');
    }
    
    public function addWheelsurf()
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
        $this->fetch('template/' . $this->module . '/updateWheelsurf');
    }
    
    public function updateWheelsurf() 
    {
        $info = $setup = [];
        $condition['wheelsurf_id'] = (int)input('get.wheelsurf_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $WheelsurfServer = new WheelsurfServer();
        $info = $WheelsurfServer->getWheelsurfDetail($condition,1);
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id, true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id, true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updateWheelsurf');
    }
    
    public function wheelsurfDetail()
    {
        $condition['wheelsurf_id'] = (int)input('get.wheelsurf_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $WheelsurfServer = new WheelsurfServer();
        $info = $WheelsurfServer->getWheelsurfDetail($condition,2);
        $this->assign('info',$info);
        $this->fetch('template/' . $this->module . '/wheelsurfDetails');
    }
    
    public function historyWheelsurf()
    {
        $condition['wheelsurf_id'] = (int)input('get.wheelsurf_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $WheelsurfServer = new WheelsurfServer();
        $info = $WheelsurfServer->getWheelsurfDetail($condition);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/historyWheelsurf');
    }
    
    public function wheelsurfSetting()
    {
        $configModel = new AddonsConfigModel();
        $info = $configModel->getInfo([
            'addons' => 'wheelsurf',
            'website_id' => $this->website_id
        ], 'is_use');
        $this->assign('is_use', $info['is_use']);
       $this->fetch('template/' . $this->module . '/wheelsurfSetting');
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