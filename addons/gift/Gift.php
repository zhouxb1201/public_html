<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\gift;

use addons\Addons;
use addons\gift\server\Gift as giftServer;
use data\model\AddonsConfigModel;
use data\service\Goods as GoodsService;
use data\service\Member as MemberService;
use data\extend\custom\Common;
use addons\shop\service\Shop as ShopService;
use data\model\SysPcCustomConfigModel;

class Gift extends Addons {

    public $info = array(
        'name' => 'gift', //插件名称标识
        'title' => '赠品', //插件中文名
        'description' => '搭配营销，多元化赠送礼品', //插件描述
        'status' => 1, //状态 1使用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', //版本号
        'has_addonslist' => 1, //是否有下级插件
        'content' => '', //插件的详细介绍或者使用方法
        'config_hook' => 'giftList', //
        'config_admin_hook' => 'giftList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197129.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782188.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782310.png',
    ); //设置文件单独的钩子
    public $menu_info = array(
        //platform
        [
            'module_name' => '赠品',
            'parent_module_name' => '应用', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '搭配营销，多元化赠送礼品', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'giftList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '赠品列表',
            'parent_module_name' => '赠品', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0, // 是否为开发者模式可见
            'desc' => '赠品需要搭配其他营销应用使用。例：满减送、礼品券、疯狂砸金蛋、刮刮乐、幸运大转盘、支付有礼、关注有礼、节日关怀等应用搭配使用。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'giftList',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '赠品', //上级模块名称 确定上级目录
            'sort' => 1, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0, // 是否为开发者模式可见
            'desc' => '关闭后，已参加其他活动的赠品均不赠送。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'giftSetting',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改赠品',
            'parent_module_name' => '赠品', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'updateGift',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加赠品',
            'parent_module_name' => '赠品', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addGift',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除赠品',
            'parent_module_name' => '赠品', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'deleteGift',
            'module' => 'platform'
        ],
        [
            'module_name' => '赠品记录',
            'parent_module_name' => '赠品', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'giftRecord',
            'module' => 'platform'
        ],
        //admin
        [
            'module_name' => '赠品',
            'parent_module_name' => '应用', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '搭配营销，多元化赠送礼品', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'giftList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '赠品列表',
            'parent_module_name' => '赠品', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0, // 是否为开发者模式可见
            'desc' => '赠品需要搭配其他营销应用使用。例：满减送、礼品券、疯狂砸金蛋、刮刮乐、幸运大转盘、支付有礼、关注有礼、节日关怀等应用搭配使用。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'giftList',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改赠品',
            'parent_module_name' => '赠品', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'updateGift',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加赠品',
            'parent_module_name' => '赠品', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addGift',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除赠品',
            'parent_module_name' => '赠品', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'deleteGift',
            'module' => 'admin'
        ],
        [
            'module_name' => '赠品记录',
            'parent_module_name' => '赠品', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'giftRecord',
            'module' => 'admin'
        ],
        [
            'module_name' => '赠品详情',
            'parent_module_name' => '赠品',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '我的优惠券',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 0,//是否有控制权限
            'hook_name' => 'giftDetail',
        ],
    );

    public function __construct() {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform' || $this->module == 'admin') {
            $this->assign('modalGiftGoodsList', __URL(call_user_func('addons_url_' . $this->module, 'gift://Gift/modalGiftGoodsList')));
            $this->assign('addOrUpdateGiftUrl', __URL(call_user_func('addons_url_' . $this->module, 'gift://Gift/addOrUpdateGift')));
        }
    }
    /*
     * 赠品列表
     */
    public function giftList() {
        $this->assign('giftListUrl', __URL(call_user_func('addons_url_' . $this->module, 'gift://Gift/giftList')));
        $this->assign('deleteGiftUrl', __URL(call_user_func('addons_url_' . $this->module, 'gift://Gift/deleteGift')));
        $this->fetch('template/' . $this->module . '/giftList');
    }

    /*
     * 添加赠品
     */
    public function addGift() {
        $this->fetch('template/' . $this->module . '/addGift');
    }

    /*
     * 修改赠品
     */
    public function updateGift() {
        $giftId = (int)request()->get('gift_id');
        $giftServer = new giftServer();
        $giftDetail = $giftServer->giftDetail($giftId);
        $this->assign('gift_info', $giftDetail);
        $this->fetch('template/' . $this->module . '/addGift');
    }
    /*
     * 赠品记录
     */
    public function giftRecord() {
        $giftId = request()->get('gift_id');
        $giftServer = new giftServer();
        $giftDetail = $giftServer->giftDetail($giftId);
        $this->assign('giftId', $giftId);
        $this->assign('giftDetail', $giftDetail);
        $this->assign('giftRecordUrl', __URL(call_user_func('addons_url_' . $this->module, 'gift://Gift/giftRecord')));
        $this->fetch('template/' . $this->module . '/giftRecord');
    }
    
    /*
     * 赠品设置
     */
    public function giftSetting()
    {
        $configModel = new AddonsConfigModel();
        $giftInfo = $configModel->getInfo([
            'addons' => 'gift',
            'website_id' => $this->website_id
        ], 'is_use');
        $this->assign('is_use', $giftInfo['is_use']);
        $this->assign('saveGiftSettingUrl', __URL(call_user_func('addons_url_' . $this->module, 'gift://Gift/giftSetting')));
        $this->fetch('template/' . $this->module . '/giftSetting');
    }
    
    /**
     * PC端赠品详情
     */
    public function giftDetail(){
        $giftId = (int)request()->get('gift_id',0);
        $goods = new GoodsService();
        $member = new MemberService();
        $giftServer = new giftServer();
        $giftDetail = $giftServer->giftDetail($giftId);
        $shop_id = 0;
        if (getAddons('shop', $this->website_id)){
        // 店铺详情
            $shop = new ShopService();
            $shop_info = $shop->getShopDetail($giftDetail["shop_id"]);
            $shop_id = $giftDetail['shop_id'];
            $this->assign("shop_info", $shop_info);
            $this->assign("shop_id", $shop_id);
            $this->assign("shopStatus", getAddons('shop', $this->website_id));
            // 当前用户是否收藏了该店铺
            $is_member_fav_shop = $member->getIsMemberFavorites($this->uid, $shop_id, 'shop');
            $this->assign("is_member_fav_shop", $is_member_fav_shop);
        }
        $default_gallery_img = ""; // 图片必须都存在才行
        
        for ($i = 0; $i < count($giftDetail["img_list"]); $i++) {
            if ($i == 0) {
                $default_gallery_img = $giftDetail["img_list"][$i]["pic_cover_big"];
            }
        }
        $this->assign("default_gallery_img", $default_gallery_img);
        $this->assign("uid", $this->uid);
        // 店内商品销量排行榜
        $goods_rank = $goods->getGoodsViewList(1, 10, array(
            "ng.website_id" => $this->website_id,
            "ng.shop_id" => $shop_id
                ), "ng.sales desc");
        $this->assign("goods_rank", $goods_rank["data"]);

        // 店内商品收藏数排行榜
        $goods_collection = $goods->getGoodsViewList(1, 10, array(
            "ng.website_id" => $this->website_id,
            "ng.shop_id" => $shop_id
                ), "ng.collects desc");
        $this->assign("goods_collection", $goods_collection["data"]);
        $this->assign('gift_info', $giftDetail);
        $this->fetch('template/shop/giftDetail');
    }

    /**
     * 安装方法
     */
    public function install() {
        // TODO: Implement install() method.


        return true;
    }

    /**
     * 卸载方法
     */
    public function uninstall() {

        return true;
        // TODO: Implement uninstall() method.
    }

}
