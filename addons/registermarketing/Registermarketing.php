<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 14:30
 */

namespace addons\registermarketing;

use addons\Addons;
use addons\registermarketing\server\RegisterMarketing as serverRegisterMarketing;
use data\service\AddonsConfig as AddonsConfigService;
class Registermarketing extends Addons
{
    public $info = array(
        'name' => 'registermarketing',//插件名称标识
        'title' => '注册营销',//插件中文名
        'description' => '注册营销',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 0,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'registerMarketing',//
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197136.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782194.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782316.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        [
            'module_name' => '注册营销',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '注册营销',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'registerMarketing',
            'module' => 'platform',
            'is_main' => 1//是否入口菜单
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '注册营销',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可设置一个时间段，给新注册的会员赠送一定的奖励，目前支持积分、优惠券、礼品券。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'registerMarketing',
            'module' => 'platform'
        ]

    );

    public function __construct()
    {
        parent::__construct();
        if ($this->module == 'platform') {
            $this->assign('saveRegisterMarketingUrl', __URL(addons_url_platform('registermarketing://Registermarketing/saveRegisterMarketing')));
        } /*else if ($this->module == 'admin') {

        }*/
    }
    public function registerMarketing()
    {
        $registerMarketing = new serverRegisterMarketing();
        $info = $registerMarketing->getRegSetting();
        $this->assign('is_use', $info);
        $with = [];
        $coupon_type_status = getAddons('coupontype', $this->website_id);
        $gift_voucher_status = getAddons('giftvoucher', $this->website_id);
        $is_coupon_type = getAddons('coupontype', $this->website_id,0,true);
        $is_gift_voucher = getAddons('giftvoucher', $this->website_id,0,true);
        if ($is_coupon_type) {
            $with[] = 'coupons';
            $this->assign('getCouponTypeListUrl', __URL(call_user_func('addons_url_' . $this->module, 'coupontype://Coupontype/couponTypeList')));
        }
        if ($is_gift_voucher) {
            $with[] = 'gift_voucher';
            $this->assign('getGiftVoucherListUrl', __URL(call_user_func('addons_url_' . $this->module, 'giftvoucher://Giftvoucher/giftvoucherList')));
        }
        $register_marketing_info = $registerMarketing->registerMarketingInfo($this->website_id, $with);
        $register_marketing_info = $register_marketing_info ?: '';
        $this->assign('coupon_type_status', $coupon_type_status);
        $this->assign('gift_voucher_status', $gift_voucher_status);
        $this->assign('is_coupon_type', $is_coupon_type);
        $this->assign('is_gift_voucher', $is_gift_voucher);
        $this->assign('register_marketing_info', json_encode($register_marketing_info, JSON_UNESCAPED_UNICODE));
        $this->fetch('template/' . $this->module . '/registermarketing');
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