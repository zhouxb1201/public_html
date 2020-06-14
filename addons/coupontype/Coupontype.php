<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\coupontype;

use addons\Addons;
use addons\coupontype\model\VslCouponTypeModel;
use addons\coupontype\server\Coupon as CouponServer;
use data\model\ConfigModel;
use data\model\AddonsConfigModel;
use think\Db;

class Coupontype extends Addons
{
    public $info = array(
        'name' => 'coupontype',//插件名称标识
        'title' => '优惠券',//插件中文名
        'description' => '优惠券营销刺激消费',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'couponTypeList',//
        'config_admin_hook' => 'couponTypeList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197120.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782182.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782300.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '优惠券',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '优惠券营销刺激消费',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'couponTypeList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '优惠券列表',
            'parent_module_name' => '优惠券', //上级模块名称 确定上级目录
            'sort' => 1, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '普通的购物优惠券，可设置无门槛券、满减券、折扣券。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'couponTypeList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改优惠券',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateCouponType',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加优惠券',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addCouponType',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除优惠券',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteCouponType',
            'module' => 'platform'
        ],
        [
            'module_name' => '优惠券使用记录',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyCoupon',
            'module' => 'platform'
        ],
        [
            'module_name' => '优惠券详情',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'couponTypeInfo',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 2,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭优惠券后，已发出的优惠券也会无法使用。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'couponSetting',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '优惠券',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '优惠券营销刺激消费',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'couponTypeList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '优惠券列表',
            'parent_module_name' => '优惠券', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '普通的购物优惠券，可设置无门槛券、满减券、折扣券。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'couponTypeList',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改优惠券',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateCouponType',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加优惠券',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addCouponType',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除优惠券',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteCouponType',
            'module' => 'admin'
        ],
        [
            'module_name' => '优惠券使用记录',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyCoupon',
            'module' => 'admin'
        ],
        [
            'module_name' => '优惠券详情',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'couponTypeInfo',
            'module' => 'admin'
        ],
        [
            'module_name' => '我的优惠券',
            'parent_module_name' => '优惠券',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'couponList',
        ],
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        $this->assign('couponListUrl', __URL(addons_url('coupontype://Coupontype/couponList')));
        if ($this->module == 'platform') {
            $this->assign('getCouponTypeInfo', __URL(addons_url_platform('coupontype://Coupontype/getCouponTypeInfo')));
            $this->assign('couponTypeListUrl', __URL(addons_url_platform('coupontype://Coupontype/couponTypeList')));
            $this->assign('addCouponTypeUrl', __URL(addons_url_platform('coupontype://Coupontype/addCouponType')));
            $this->assign('updateCouponTypeUrl', __URL(addons_url_platform('coupontype://Coupontype/updateCouponType')));
            $this->assign('deleteCouponTypeUrl', __URL(addons_url_platform('coupontype://Coupontype/deleteCouponType')));
            $this->assign('historyCouponUrl', __URL(addons_url_platform('coupontype://Coupontype/historyCoupon')));
            $this->assign('saveCouponSettingUrl', __URL(addons_url_platform('coupontype://Coupontype/couponSetting')));
        } else if ($this->module == 'admin') {
            $this->assign('getCouponTypeInfo', __URL(addons_url_admin('coupontype://Coupontype/getCouponTypeInfo')));
            $this->assign('couponTypeListUrl', __URL(addons_url_admin('coupontype://Coupontype/couponTypeList')));
            $this->assign('addCouponTypeUrl', __URL(addons_url_admin('coupontype://Coupontype/addCouponType')));
            $this->assign('updateCouponTypeUrl', __URL(addons_url_admin('coupontype://Coupontype/updateCouponType')));
            $this->assign('deleteCouponTypeUrl', __URL(addons_url_admin('coupontype://Coupontype/deleteCouponType')));
            $this->assign('historyCouponUrl', __URL(addons_url_admin('coupontype://Coupontype/historyCoupon')));
        }
    }

    public function couponTypeList()
    {
        $this->assign('receiveUrl', __URLS('/wap/coupon/receive'));
        $this->fetch('template/' . $this->module . '/couponTypeList');
    }

    public function addCouponType()
    {
        //addCouponType和updateCouponType使用同一个模板html,这里传递一个string类型（updateCouponType的为object）的coupon_type_info让模板不必初始化couponType的数据
        $this->assign('coupon_type_info', json_encode(''));
        $this->fetch('template/' . $this->module . '/updateCouponType');
    }

    public function updateCouponType()
    {
        $coupon_type_id = $_GET['coupon_type_id'];
        $coupon_model = new CouponServer();
        $coupon_type_info = $coupon_model->getCouponTypeDetail($coupon_type_id);
        $this->assign('coupon_type_info', json_encode($coupon_type_info, JSON_UNESCAPED_UNICODE));
        $this->fetch('template/' . $this->module . '/updateCouponType');
    }

    public function couponSetting()
    {
        $configModel = new AddonsConfigModel();
        $coupon_info = $configModel->getInfo([
            'addons' => 'coupontype',
            'website_id' => $this->website_id
        ], 'is_use');
        
        $this->assign('is_use', $coupon_info['is_use']);
        $this->fetch('template/' . $this->module . '/couponSetting');
    }

    public function historyCoupon()
    {
        $this->assign('coupon_type_id', $_GET['coupon_type_id']);
        $this->fetch('template/' . $this->module . '/historyCoupon');
    }

    public function couponTypeInfo()
    {
        $this->assign('coupon_type_id', $_GET['coupon_type_id']);
        $this->fetch('template/' . $this->module . '/couponsDetails');
    }
    /**
     * PC端我的优惠券
     */
    public function couponList(){
        $this->fetch('template/shop/couponList');
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