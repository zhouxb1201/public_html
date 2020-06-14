<?php
namespace addons\scratchcard;

use addons\Addons;
use data\model\AddonsConfigModel;
use addons\scratchcard\server\ScratchCard as ScratchServer;

class Scratchcard extends Addons
{
    public $info = array(
        'name' => 'scratchcard',//插件名称标识
        'title' => '刮刮乐',//插件中文名
        'description' => '消费积分刮刮卡，多元化奖品',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'scratchcardList',//
        'config_admin_hook' => 'scratchcardList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197054.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782101.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782226.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '刮刮乐',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '消费积分刮刮卡，多元化奖品',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'scratchcardList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '刮刮乐列表',
            'parent_module_name' => '刮刮乐', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '通过消耗积分刮卡抽奖为主题的活动，可设置多个不同类型的奖项。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'scratchcardList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改刮刮乐',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可添加最多十二个奖项（未中奖在内），奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateScratchcard',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加刮刮乐',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可添加最多十二个奖项（未中奖在内），奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addScratchcard',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除刮刮乐',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除刮刮乐',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteScratchcard',
            'module' => 'platform'
        ],
        [
            'module_name' => '刮刮乐中奖记录',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '刮刮乐中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyScratchcard',
            'module' => 'platform'
        ],
        [
            'module_name' => '刮刮乐详情',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'scratchcardDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭后，所有刮刮乐活动均不生效。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'scratchcardSetting',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '刮刮乐',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '消费积分刮刮卡，多元化奖品',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'scratchcardList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '刮刮乐列表',
            'parent_module_name' => '刮刮乐', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '通过消耗积分刮卡抽奖为主题的活动，可设置多个不同类型的奖项。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'scratchcardList',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改刮刮乐',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可添加最多十二个奖项（未中奖在内），奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateScratchcard',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加刮刮乐',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可添加最多十二个奖项（未中奖在内），奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”等。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addScratchcard',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除刮刮乐',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除刮刮乐',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteScratchcard',
            'module' => 'admin'
        ],
        [
            'module_name' => '刮刮乐中奖记录',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '刮刮乐中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyScratchcard',
            'module' => 'admin'
        ],
        [
            'module_name' => '刮刮乐详情',
            'parent_module_name' => '刮刮乐',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'scratchcardDetail',
            'module' => 'admin'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        $this->assign('receiveUrl', __URLS('/wap/scratchcard/centre'));
        if ($this->module == 'platform') {
            $this->assign('scratchcardListUrl', __URL(addons_url_platform('scratchcard://Scratchcard/scratchcardList')));
            $this->assign('addScratchcardUrl', __URL(addons_url_platform('scratchcard://Scratchcard/addScratchcard')));
            $this->assign('updateScratchcardUrl', __URL(addons_url_platform('scratchcard://Scratchcard/updateScratchcard')));
            $this->assign('deleteScratchcardUrl', __URL(addons_url_platform('scratchcard://Scratchcard/deleteScratchcard')));
            $this->assign('historyScratchcardUrl', __URL(addons_url_platform('scratchcard://Scratchcard/historyScratchcard')));
            $this->assign('prizeTypeUrl', __URL(addons_url_platform('scratchcard://Scratchcard/prizeType')));
            $this->assign('saveSettingUrl', __URL(addons_url_platform('scratchcard://Scratchcard/saveSetting')));
        } else if ($this->module == 'admin') {
            $this->assign('scratchcardListUrl', __URL(addons_url_admin('scratchcard://Scratchcard/scratchcardList')));
            $this->assign('addScratchcardUrl', __URL(addons_url_admin('scratchcard://Scratchcard/addScratchcard')));
            $this->assign('updateScratchcardUrl', __URL(addons_url_admin('scratchcard://Scratchcard/updateScratchcard')));
            $this->assign('deleteScratchcardUrl', __URL(addons_url_admin('scratchcard://Scratchcard/deleteScratchcard')));
            $this->assign('historyScratchcardUrl', __URL(addons_url_admin('scratchcard://Scratchcard/historyScratchcard')));
            $this->assign('prizeTypeUrl', __URL(addons_url_admin('scratchcard://Scratchcard/prizeType')));
        }
    }
    
    public function scratchcardList()
    {
        $this->fetch('template/' . $this->module . '/scratchcardList');
    }
    
    public function addScratchcard()
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
        $this->fetch('template/' . $this->module . '/updateScratchcard');
    }
    
    public function updateScratchcard()
    {
        $info = $setup = [];
        $condition['scratch_card_id'] = (int)input('get.scratch_card_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $ScratchServer = new ScratchServer();
        $info = $ScratchServer->getScratchCardDetail($condition,1);
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id, true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id, true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updateScratchcard');
    }
    
    public function scratchcardDetail()
    {
        $condition['scratch_card_id'] = (int)input('get.scratch_card_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $ScratchServer = new ScratchServer();
        $info = $ScratchServer->getScratchCardDetail($condition,2);
        $this->assign('info',$info);
        $this->fetch('template/' . $this->module . '/scratchcardDetails');
    }
    
    public function historyScratchcard()
    {
        $condition['scratch_card_id'] = (int)input('get.scratch_card_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $ScratchServer = new ScratchServer();
        $info = $ScratchServer->getScratchCardDetail($condition);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/historyScratchcard');
    }
    
    public function scratchcardSetting()
    {
        $configModel = new AddonsConfigModel();
        $info = $configModel->getInfo([
            'addons' => 'scratchcard',
            'website_id' => $this->website_id
        ], 'is_use');
        $this->assign('is_use', $info['is_use']);
       $this->fetch('template/' . $this->module . '/scratchcardSetting');
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