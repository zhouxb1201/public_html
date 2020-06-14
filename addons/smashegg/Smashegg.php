<?php
namespace addons\smashegg;

use addons\Addons;
use data\model\AddonsConfigModel;
use addons\smashegg\server\SmashEgg as SmashEggServer;

class Smashegg extends Addons
{
    public $info = array(
        'name' => 'smashegg',//插件名称标识
        'title' => '疯狂砸金蛋',//插件中文名
        'description' => '消费积分砸金蛋，多元化奖品',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'smasheggList',//
        'config_admin_hook' => 'smasheggList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197126.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782185.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782307.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '疯狂砸金蛋',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '消费积分砸金蛋，多元化奖品',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'smasheggList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '砸金蛋列表',
            'parent_module_name' => '疯狂砸金蛋', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '通过消耗积分以砸金蛋为主题的活动。可设置多个不同类型的奖项。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'smasheggList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改砸金蛋',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可添加最多十二个奖项（未中奖在内），奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateSmashegg',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加砸金蛋',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可添加最多十二个奖项（未中奖在内），奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addSmashegg',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除砸金蛋',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除砸金蛋',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteSmashegg',
            'module' => 'platform'
        ],
        [
            'module_name' => '砸金蛋中奖记录',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '砸金蛋中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historySmashegg',
            'module' => 'platform'
        ],
        [
            'module_name' => '砸金蛋详情',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'smasheggDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭后，所有砸金蛋活动均不生效。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'smasheggSetting',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '疯狂砸金蛋',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '消费积分砸金蛋，多元化奖品',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'smasheggList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '砸金蛋列表',
            'parent_module_name' => '疯狂砸金蛋', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '通过消耗积分以砸金蛋为主题的活动。可设置多个不同类型的奖项。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'smasheggList',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改砸金蛋',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可添加最多十二个奖项（未中奖在内），奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateSmashegg',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加砸金蛋',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可添加最多十二个奖项（未中奖在内），奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addSmashegg',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除砸金蛋',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除砸金蛋',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteSmashegg',
            'module' => 'admin'
        ],
        [
            'module_name' => '砸金蛋中奖记录',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '砸金蛋中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historySmashegg',
            'module' => 'admin'
        ],
        [
            'module_name' => '砸金蛋详情',
            'parent_module_name' => '疯狂砸金蛋',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'smasheggDetail',
            'module' => 'admin'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        $this->assign('receiveUrl', __URLS('/wap/smashegg/centre'));
        if ($this->module == 'platform') {
            $this->assign('smasheggListUrl', __URL(addons_url_platform('smashegg://Smashegg/smasheggList')));
            $this->assign('addSmasheggUrl', __URL(addons_url_platform('smashegg://Smashegg/addSmashegg')));
            $this->assign('updateSmasheggUrl', __URL(addons_url_platform('smashegg://Smashegg/updateSmashegg')));
            $this->assign('deleteSmasheggUrl', __URL(addons_url_platform('smashegg://Smashegg/deleteSmashegg')));
            $this->assign('historySmasheggrUrl', __URL(addons_url_platform('smashegg://Smashegg/historySmashegg')));
            $this->assign('prizeTypeUrl', __URL(addons_url_platform('smashegg://Smashegg/prizeType')));
            $this->assign('saveSettingUrl', __URL(addons_url_platform('smashegg://Smashegg/saveSetting')));
        } else if ($this->module == 'admin') {
            $this->assign('smasheggListUrl', __URL(addons_url_admin('smashegg://Smashegg/smasheggList')));
            $this->assign('addSmasheggUrl', __URL(addons_url_admin('smashegg://Smashegg/addSmashegg')));
            $this->assign('updateSmasheggUrl', __URL(addons_url_admin('smashegg://Smashegg/updateSmashegg')));
            $this->assign('deleteSmasheggUrl', __URL(addons_url_admin('smashegg://Smashegg/deleteSmashegg')));
            $this->assign('historySmasheggrUrl', __URL(addons_url_admin('smashegg://Smashegg/historySmashegg')));
            $this->assign('prizeTypeUrl', __URL(addons_url_admin('smashegg://Smashegg/prizeType')));
        }
    }
    
    public function smasheggList()
    {
        $this->fetch('template/' . $this->module . '/smasheggList');
    }
    
    public function addSmashegg()
    {
        $info = $setup = [];
        $info['prizeNum'] = 0;
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id, true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id, true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updateSmashegg');
    }
    
    public function updateSmashegg()
    {
        $info = $setup = [];
        $condition['smash_egg_id'] = (int)input('get.smash_egg_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $SmashEggServerr = new SmashEggServer();
        $info = $SmashEggServerr->getSmashEggDetail($condition,1);
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id, true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id, true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updateSmashegg');
    }
    
    public function smasheggDetail()
    {
        $condition['smash_egg_id'] = (int)input('get.smash_egg_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $SmashEggServerr = new SmashEggServer();
        $info = $SmashEggServerr->getSmashEggDetail($condition,2);
        $this->assign('info',$info);
        $this->fetch('template/' . $this->module . '/smasheggDetails');
    }
    
    public function historySmashegg()
    {
        $condition['smash_egg_id'] = (int)input('get.smash_egg_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $SmashEggServerr = new SmashEggServer();
        $info = $SmashEggServerr->getSmashEggDetail($condition);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/historySmashegg');
    }
    
    public function smasheggSetting()
    {
        $configModel = new AddonsConfigModel();
        $info = $configModel->getInfo([
            'addons' => 'smashegg',
            'website_id' => $this->website_id
        ], 'is_use');
        $this->assign('is_use', $info['is_use']);
       $this->fetch('template/' . $this->module . '/smasheggSetting');
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