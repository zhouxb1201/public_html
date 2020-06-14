<?php
namespace addons\giftvoucher;

use addons\Addons;
use data\model\AddonsConfigModel;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use think\Db;

class Giftvoucher extends Addons
{
    public $info = array(
        'name' => 'giftvoucher',//插件名称标识
        'title' => '礼品券',//插件中文名
        'description' => '线下门店礼品/赠品核销券',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'giftvoucherList',//
        'config_admin_hook' => 'giftvoucherList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197074.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782127.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782249.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '礼品券',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '线下门店礼品/赠品核销券',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'giftvoucherList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '礼品券列表',
            'parent_module_name' => '礼品券', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '礼品券需要搭配赠品应用于O2O门店应用使用，会员可以到线下门店核销礼品券领取对应的赠品。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'giftvoucherList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改礼品券',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '需要先在赠品应用，添加赠品。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateGiftvoucher',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加礼品券',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '需要先在赠品应用，添加赠品。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addGiftvoucher',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除礼品券',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除礼品券',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteGiftvoucher',
            'module' => 'platform'
        ],
        [
            'module_name' => '礼品券使用记录',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '查看O2O门店核销礼品券记录。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyGiftvoucher',
            'module' => 'platform'
        ],
        [
            'module_name' => '礼品券详情',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'giftvoucherDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭礼品券后，已发出的礼品券均会失效，无法核销。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'giftvoucherSetting',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '礼品券',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '线下门店礼品/赠品核销券',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'giftvoucherList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '礼品券列表',
            'parent_module_name' => '礼品券', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '礼品券需要搭配赠品应用于O2O门店应用使用，会员可以到线下门店核销礼品券领取对应的赠品。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'giftvoucherList',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改礼品券',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '需要先在赠品应用，添加赠品。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateGiftvoucher',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加礼品券',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '需要先在赠品应用，添加赠品。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addGiftvoucher',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除礼品券',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除礼品券',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteGiftvoucher',
            'module' => 'admin'
        ],
        [
            'module_name' => '礼品券使用记录',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '查看O2O门店核销礼品券记录。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyGiftvoucher',
            'module' => 'admin'
        ],
        [
            'module_name' => '礼品券详情',
            'parent_module_name' => '礼品券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'giftvoucherDetail',
            'module' => 'admin'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        $this->assign('receiveUrl', __URLS('/wap/giftvoucher/receive'));
        if ($this->module == 'platform') {
            $this->assign('giftvoucherListUrl', __URL(addons_url_platform('giftvoucher://Giftvoucher/giftvoucherList')));
            $this->assign('addGiftvoucherUrl', __URL(addons_url_platform('giftvoucher://Giftvoucher/addGiftvoucher')));
            $this->assign('updateGiftvoucherUrl', __URL(addons_url_platform('giftvoucher://Giftvoucher/updateGiftvoucher')));
            $this->assign('deleteGiftvoucherUrl', __URL(addons_url_platform('giftvoucher://Giftvoucher/deleteGiftvoucher')));
            $this->assign('modalGiftListUrl', __URL(addons_url_platform('giftvoucher://Giftvoucher/modalGiftList')));
            $this->assign('historygiftvoucherUrl', __URL(addons_url_platform('giftvoucher://Giftvoucher/historyGiftvoucher')));
            $this->assign('saveSettingUrl', __URL(addons_url_platform('giftvoucher://Giftvoucher/saveSetting')));
        } else if ($this->module == 'admin') {
            $this->assign('giftvoucherListUrl', __URL(addons_url_admin('giftvoucher://Giftvoucher/giftvoucherList')));
            $this->assign('addGiftvoucherUrl', __URL(addons_url_admin('giftvoucher://Giftvoucher/addGiftvoucher')));
            $this->assign('updateGiftvoucherUrl', __URL(addons_url_admin('giftvoucher://Giftvoucher/updateGiftvoucher')));
            $this->assign('modalGiftListUrl', __URL(addons_url_admin('giftvoucher://Giftvoucher/modalGiftList')));
            $this->assign('deleteGiftvoucherUrl', __URL(addons_url_admin('giftvoucher://Giftvoucher/deleteGiftvoucher')));
            $this->assign('historygiftvoucherUrl', __URL(addons_url_admin('giftvoucher://Giftvoucher/historyGiftvoucher')));
        }
    }
    
    public function giftvoucherList()
    {
        $this->fetch('template/' . $this->module . '/giftvoucherList');
    }
    
    public function addGiftvoucher()
    {
        $giftvoucher_info['gift'] = [];
        $this->assign('giftvoucher_info', $giftvoucher_info);
        $this->fetch('template/' . $this->module . '/updateGiftvoucher');
    }
    
    public function updateGiftvoucher()
    {
        $condition['gift_voucher_id'] = (int)input('get.gift_voucher_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $VoucherServer = new VoucherServer();
        $giftvoucher_info = $VoucherServer->getGiftVoucherDetail($condition);
        $this->assign('giftvoucher_info',$giftvoucher_info);
        $this->fetch('template/' . $this->module . '/updateGiftvoucher');
    }
    
    public function giftvoucherDetail()
    {
        $condition['gift_voucher_id'] = (int)input('get.gift_voucher_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $VoucherServer = new VoucherServer();
        $giftvoucher_info = $VoucherServer->getGiftVoucherDetail($condition);
        $this->assign('giftvoucher_info',$giftvoucher_info);
        $this->fetch('template/' . $this->module . '/giftvoucherDetails');
    }
    
    public function historyGiftvoucher()
    {
        $this->assign('gift_voucher_id', (int)input('get.gift_voucher_id'));
        $this->fetch('template/' . $this->module . '/historyGiftvoucher');
    }
    
    public function giftvoucherSetting()
    {
        $configModel = new AddonsConfigModel();
        $info = $configModel->getInfo([
            'addons' => 'giftvoucher',
            'website_id' => $this->website_id
        ], 'is_use');
        $this->assign('is_use', $info['is_use']);
       $this->fetch('template/' . $this->module . '/giftvoucherSetting');
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