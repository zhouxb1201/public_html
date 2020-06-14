<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:35
 */

namespace addons\poster;

use addons\Addons;
use addons\coupontype\model\VslCouponTypeModel;
use addons\coupontype\server\Coupon;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\poster\model\PosterModel;
use data\model\AddonsConfigModel;
use data\model\WebSiteModel;
use data\service\Config as ConfigService;
use think\Request;
use addons\poster\service\Poster as PosterService;

class Poster extends Addons
{
    protected static $addons_name = 'poster';

    public $info = [
        'name' => 'poster',//插件名称标识
        'title' => '超级海报',//插件中文名
        'description' => '海报锁粉，获得奖励',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'poster',//
        'config_admin_hook' => '', //
        'no_set' => 1,
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197008.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782087.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782218.png',
    ];//设置文件单独的钩子

    public $menu_info = [
        //platform
        [
            'module_name' => '超级海报',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '海报锁粉，获得奖励',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'posterList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '海报列表',
            'parent_module_name' => '超级海报',//上级模块名称 用来确定上级目录
            'sort' => 1,//子菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'posterList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改海报',
            'parent_module_name' => '超级海报',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'poster',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除海报',
            'parent_module_name' => '超级海报',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deletePoster',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除海报缓存',
            'parent_module_name' => '超级海报',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单_
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deletePosterCache',
            'module' => 'platform'
        ],
        [
            'module_name' => '设置默认海报',
            'parent_module_name' => '超级海报',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'defaultPoster',
            'module' => 'platform'
        ],
        [
            'module_name' => '扫描记录',
            'parent_module_name' => '超级海报',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'posterRecord',
            'module' => 'platform'
        ],

        //admin
    ];

    public $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PosterService();
    }

    public function posterList()
    {
        $this->assign('deletePosterCacheUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/deletePosterCache')));
        $this->assign('deletePosterUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/deletePoster')));
        $this->assign('defaultPosterUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/defaultPoster')));
        $this->assign('posterDialogUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/posterDialog')));
        $this->assign('posterSystemDefaultTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/posterSystemDefaultTemplate')));
        $this->assign('posterListUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/posterList')));
        $this->fetch('template/' . $this->module . '/posterList');
    }

    public function poster()
    {
        $poster_id = request()->get('poster_id');

        $is_coupon_type = getAddons('coupontype', $this->website_id);
        $is_gift_voucher = getAddons('giftvoucher', $this->website_id);
        if ($is_coupon_type) {
            $coupon_condition = [];
            $coupon_condition['website_id'] = $this->website_id;
            $coupon_condition['shop_id'] = $this->instance_id;
            $coupon_condition['end_time'] = ['>=', time()];
            $coupon_type_model = new VslCouponTypeModel();
            $coupon_type_list = $coupon_type_model::all($coupon_condition);
            $coupon_type_list = array_merge([['coupon_type_id' => 0, 'coupon_name' => '请选择']], $coupon_type_list);
        } else {
            $coupon_type_list = [];
        }

        if ($is_gift_voucher) {
            $gift_voucher_condition = [];
            $gift_voucher_condition['website_id'] = $this->website_id;
            $gift_voucher_condition['shop_id'] = $this->instance_id;
            $gift_voucher_condition['end_time'] = ['>=', time()];
            $gift_voucher_model = new VslGiftVoucherModel();
            $gift_voucher_list = $gift_voucher_model::all($gift_voucher_condition);
            $gift_voucher_list = array_merge([['gift_voucher_id' => 0, 'giftvoucher_name' => '请选择']], $gift_voucher_list);
        } else {
            $gift_voucher_list = [];
        }
        $this->assign('coupon_type_list', json_encode($coupon_type_list, JSON_UNESCAPED_UNICODE));
        $this->assign('gift_voucher_list', json_encode($gift_voucher_list, JSON_UNESCAPED_UNICODE));
        $this->assign('poster_id', $poster_id);
        $this->assign('is_coupon_type', $is_coupon_type);
        $this->assign('is_gift_voucher', $is_gift_voucher);
        $this->assign('is_micro_shop', getAddons('microshop', $this->website_id));
        $this->assign('posterUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/poster')));
        $this->assign('savePosterUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/savePoster')));
        $this->assign('isRepeatKeyword', __URL(call_user_func('addons_url_' . $this->module,'poster://Poster/isRepeatKeyword')));
        $this->fetch('template/' . $this->module . '/poster');
    }

    public function posterRecord()
    {
        $this->assign('poster_id', input('get.poster_id'));
        $poster_id = input('get.poster_id');
        $poster = new PosterModel();
        if($poster_id){
            $type = $poster->getInfo(['poster_id' => $poster_id], 'type')['type'];
            $this->assign('type', $type);
        }
        $this->assign('recordListUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/recordList')));
        $this->fetch('template/' . $this->module . '/posterRecord');
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }
}