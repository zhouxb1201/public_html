<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\voucherpackage;

use addons\Addons;
use addons\voucherpackage\model\VslVoucherPackageModel;
use addons\voucherpackage\model\VslVoucherPackageRelationModel;
use data\model\AddonsConfigModel;
use addons\voucherpackage\service\VoucherPackage as VoucherPackageService;
use phpDocumentor\Reflection\Types\This;

class Voucherpackage extends Addons
{
    public $info = array(
        'name' => 'voucherpackage',//插件名称标识
        'title' => '券包',//插件中文名
        'description' => '搭配不同类型的优惠/礼品券打包派送',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'voucherPackageList',//
        'config_admin_hook' => 'voucherPackageList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197068.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782118.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782240.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '券包',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '搭配不同类型的优惠/礼品券打包派送',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'voucherPackageList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '券包列表',
            'parent_module_name' => '券包', //上级模块名称 确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '通过打包的形式把优惠券和礼品券做成一个券包，会员领取后可获得券包里面的所有票券。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'voucherPackageList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改券包',
            'parent_module_name' => '券包',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '只能选择有效的优惠券或礼品券，请保证每张券的数量一致。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateVoucherPackage',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加券包',
            'parent_module_name' => '券包',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '只能选择有效的优惠券或礼品券，请保证每张券的数量一致。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addVoucherPackage',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除券包',
            'parent_module_name' => '券包',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除券包',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteVoucherPackage',
            'module' => 'platform'
        ],
        [
            'module_name' => '券包详情',
            'parent_module_name' => '券包',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'voucherPackageInfo',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '券包',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭后，所有券包均不生效。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'voucherPackageSetting',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '券包',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '搭配不同类型的优惠/礼品券打包派送',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'voucherPackageList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '券包列表',
            'parent_module_name' => '券包', //上级模块名称 确定上级目录
            'sort' => 0, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '通过打包的形式把优惠券和礼品券做成一个券包，会员领取后可获得券包里面的所有票券。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'voucherPackageList',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改券包',
            'parent_module_name' => '券包',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '只能选择有效的优惠券或礼品券，请保证每张券的数量一致。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateVoucherPackage',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加券包',
            'parent_module_name' => '券包',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '只能选择有效的优惠券或礼品券，请保证每张券的数量一致。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addVoucherPackage',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除券包',
            'parent_module_name' => '券包',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除券包',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteVoucherPackage',
            'module' => 'admin'
        ],
        [
            'module_name' => '券包详情',
            'parent_module_name' => '券包',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'voucherPackageInfo',
            'module' => 'admin'
        ],
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function voucherPackageList()
    {
        $this->assign('addVoucherPackageUrl', __URL(call_user_func('addons_url_' .$this->module,'voucherpackage://Voucherpackage/addVoucherPackage')));
        $this->assign('updateVoucherPackageUrl', __URL(call_user_func('addons_url_' .$this->module,'voucherpackage://Voucherpackage/updateVoucherPackage')));
        $this->assign('deleteVoucherPackageUrl', __URL(call_user_func('addons_url_' .$this->module,'voucherpackage://Voucherpackage/deleteVoucherPackage')));
        $this->assign('voucherPackageListUrl', __URL(call_user_func('addons_url_' .$this->module,'voucherpackage://Voucherpackage/VoucherPackageList')));
        $this->assign('receiveUrl', __URLS('/wap/voucherpackage/'));
        $this->fetch('template/' . $this->module . '/voucherPackageList');
    }

    public function addVoucherPackage()
    {
        $this->assign('selectModalUrl', __URL(call_user_func('addons_url_' .$this->module,'voucherpackage://Voucherpackage/selectModal')));
        $this->assign('addVoucherPackageUrl',__URL(call_user_func('addons_url_' .$this->module,'voucherpackage://Voucherpackage/addVoucherPackage')));
        $this->assign('is_coupon_type',getAddons('coupontype',$this->website_id));
        $this->assign('is_gift_voucher',getAddons('giftvoucher',$this->website_id));
        $this->assign('voucher_package_info', json_encode(''));
        $this->fetch('template/' . $this->module . '/updateVoucherPackage');
    }

    public function updateVoucherPackage()
    {
        $voucher_package_id = input('get.voucher_package_id');
        $voucher_service = new VoucherPackageService();
        $voucher_package_info = $voucher_service->getVoucherPackageDetail(['voucher_package_id' => $voucher_package_id], ['voucher_relation']);
        $this->assign('voucher_package_info', json_encode($voucher_package_info, JSON_UNESCAPED_UNICODE));
        $this->assign('selectModalUrl', __URL(call_user_func('addons_url_' .$this->module,'voucherpackage://Voucherpackage/selectModal')));
        $this->assign('updateVoucherPackageUrl',__URL(call_user_func('addons_url_' .$this->module,'voucherpackage://Voucherpackage/updateVoucherPackage')));
        $this->assign('is_coupon_type',getAddons('coupontype',$this->website_id));
        $this->assign('is_gift_voucher',getAddons('giftvoucher',$this->website_id));
        $this->fetch('template/' . $this->module . '/updateVoucherPackage');
    }

    public function voucherPackageSetting()
    {
        $configModel = new AddonsConfigModel();
        $voucher_package_info = $configModel->getInfo([
            'addons' => 'voucherpackage',
            'website_id' => $this->website_id
        ], 'is_use');
        $this->assign('saveAddonsSettingUrl', __URL(call_user_func('addons_url_' .$this->module,'voucherpackage://Voucherpackage/voucherPackageSetting')));
        $this->assign('is_use', $voucher_package_info['is_use']);
        $this->fetch('template/' . $this->module . '/voucherPackageSetting');
    }

    public function voucherPackageInfo()
    {
        $this->assign('voucher_package_id', input('get.voucher_package_id'));
        $this->assign('getVoucherPackageInfo', __URL(call_user_func('addons_url_' . $this->module, 'voucherpackage://Voucherpackage/getVoucherPackageInfo')));
        $this->fetch('template/' . $this->module . '/voucherPackageDetails');
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