<?php
namespace addons\shop;
use addons\Addons as Addo;
use addons\shop\service\Shop as ShopService;
use data\model\ModuleModel;
use data\model\SysAddonsModel;
use data\service\WebSite as WebSite;
use \think\Session as Session;
use data\service\Order as OrderService;
use data\service\Goods as Goods;
use data\extend\custom\Common as Common;
use data\model\SysPcCustomConfigModel;
use data\service\Member;
use data\service\Promotion;
use data\service\GoodsGroup as GoodsGroupService;
use data\service\User;
use app\shop\controller\Member as memberController;
use data\service\Order\Order as OrderType;
use data\service\AddonsConfig;
use addons\miniprogram\model\WeixinAuthModel;
use data\model\WebSiteModel;

class Shop extends Addo
{
    protected $shop_id = 0;
    protected $goods_group = '';
    protected $goods_group_id = 0;
    protected $member = '';
    public $info = array(
        'name' => 'shop', // 插件名称标识
        'title' => '多商户系统', // 插件中文名
        'description' => '卖家入驻，搭建多店铺平台', // 插件概述
        'status' => 1, // 状态 1启用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', // 版本号
        'has_addonslist' => 1, // 是否有下级插件 例如：第三方登录插件下有 qq登录，微信登录
        'content' => '', // 插件的详细介绍或使用方法
        'config_hook' => 'shopList',
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197013.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782091.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782222.png',
    ); // 设置文件单独的钩子

    public $menu_info = array(
        [
            'module_name' => '多商户系统',
            'parent_module_name' => '应用', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '卖家入驻，搭建多店铺平台', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopList',
            'is_main' => 1
        ],
        [
            'module_name' => '店铺列表',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '多商户系统即把原先的单店版（B2C）拓展为多店版（B2B2C），平台可通过店铺提现抽成向入驻店铺收取服务费用。', // 菜单描述   
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopList'
        ],
        [
            'module_name' => '店铺添加',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addPlatformShop'
        ],
        [
            'module_name' => '修改店铺',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateShop'
        ],
        [
            'module_name' => '店铺版本',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 1, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '根据不同的店铺运营性质可分配多个版本，版本与店铺关联。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopLevelList'
        ],
        [
            'module_name' => '添加店铺版本',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addShopLevel'
        ],
        [
            'module_name' => '修改店铺版本',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateShopLevel'
        ],
        [
            'module_name' => '店铺分类',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 3, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店铺街可以商家设定的店铺分类进行筛选。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopGroupList'
        ],
        [
            'module_name' => '添加店铺分类',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addShopGroup'
        ],
        [
            'module_name' => '修改店铺分类',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateShopGroup'
        ],
        
        [
            'module_name' => '店铺审核',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 2, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '前端会员提交申请入驻商城后，商家需要在此完成审核。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopApplyList'
        ],

        [
            'module_name' => '审核详情',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopVerify'
        ],
        [
            'module_name' => '店铺协议',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 4, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '会员入驻商城时需要阅读同意的入驻协议，建议谨慎填写。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopProtocol'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 5, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '若关闭应用，前端涉及多店应用的模块均不显示。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopSetting'
        ],
        [
            'module_name' => '店铺提现列表',
            'parent_module_name' => '财务', // 上级模块名称 用来确定上级目录
            'sort' => 4, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '入驻店铺销售商品产生的资金都会先打到平台方的账户上，店铺跟平台方结算时产生的提现需要在该页面审核与打款，平台方也可在“系统》商城配置》提现设置”设置为自动审核与自动打款。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopAccountWithdrawList'
        ],
        [
            'module_name' => '店铺提现详情',
            'parent_module_name' => '财务', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopAccountWithdrawInfo',
            'is_member' => 1
        ],
        [
            'module_name' => '店铺账目列表',
            'parent_module_name' => '财务', // 上级模块名称 用来确定上级目录
            'sort' => 5, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '实时查看入驻店铺交易情况，合理安排账户资金运作。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopAccountList'
        ],
        [
            'module_name' => '入驻店订单',
            'parent_module_name' => '订单', // 上级模块名称 用来确定上级目录
            'sort' => 2, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopOrderList'
        ],
        [
            'module_name' => '入驻店商品',
            'parent_module_name' => '商品', // 上级模块名称 用来确定上级目录
            'sort' => 8, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '为了营造良好的平台环境，可把入驻店违法、违规商品下架或删除。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'goodsList'
        ],
        [
            'module_name' => '店铺街',
            'parent_module_name' => '', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店铺街', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 0, // 是否有控制权限
            'hook_name' => 'shopStreet',
            'is_member' => 1
        ],
        [
            'module_name' => '店铺首页',
            'parent_module_name' => '', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店铺首页', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 0, // 是否有控制权限
            'hook_name' => 'shopIndex',
            'is_member' => 1
        ],
        [
            'module_name' => '店铺商品列表',
            'parent_module_name' => '', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店铺商品列表', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 0, // 是否有控制权限
            'hook_name' => 'shopGoodList',
            'is_member' => 1
        ],
        [
            'module_name' => '同意协议',
            'parent_module_name' => '', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '商家入驻第一步：同意协议', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 0, // 是否有控制权限
            'hook_name' => 'applyFristAgreement',
            'is_member' => 1
        ],
        [
            'module_name' => '公司信息认证',
            'parent_module_name' => '', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '商家入驻第二步：公司信息认证', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 0, // 是否有控制权限
            'hook_name' => 'applySecondCompanyInfo',
            'is_member' => 1
        ],
        [
            'module_name' => '店铺信息认证',
            'parent_module_name' => '', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '商家入驻第三步：店铺信息认证', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 0, // 是否有控制权限
            'hook_name' => 'applyThirdStoreInfo',
            'is_member' => 1
        ],
        [
            'module_name' => '商家入驻',
            'parent_module_name' => '', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '商家入驻，等待审核', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 0, // 是否有控制权限
            'hook_name' => 'applyFinish',
            'is_member' => 1
        ],
        [
            'module_name' => '商家入驻首页',
            'parent_module_name' => '', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '商家入驻首页', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 0, // 是否有控制权限
            'hook_name' => 'applyIndex',
            'is_member' => 1
        ],
        [
            'module_name' => '店铺收藏',
            'parent_module_name' => '多商户系统', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店铺收藏', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 0, // 是否有控制权限
            'hook_name' => 'shopCollectionList',
            'is_member' => 1
        ]
    ) // 钩子名称（需要该钩子调用的页面）
    ;
     public function __construct(){
        parent::__construct();
        $this->assign('shopListUrl', __URL(addons_url_platform('shop://shop/shopList')));
         $this->assign('getWithdrawCountUrl', __URL(addons_url_platform('shop://shop/getWithdrawCount')));
        $this->assign('updateShopListUrl', __URL(addons_url_platform('shop://shop/updateShopList')));
        $this->assign('setStatusUrl', __URL(addons_url_platform('shop://shop/setStatus')));
        $this->assign('setRecommentUrl', __URL(addons_url_platform('shop://shop/setRecomment')));
        $this->assign('setIsvisibleUrl', __URL(addons_url_platform('shop://shop/setIsvisible')));
        $this->assign('deleteShopLevelUrl', __URL(addons_url_platform('shop://shop/deleteShopLevel')));
        $this->assign('shopLevelListUrl', __URL(addons_url_platform('shop://shop/shopLevelList')));
        $this->assign('shopApplyListUrl', __URL(addons_url_platform('shop://shop/shopApplyList')));
        $this->assign('shopGroupListUrl', __URL(addons_url_platform('shop://shop/shopGroupList')));
        $this->assign('updateShopGroupUrl', __URL(addons_url_platform('shop://shop/updateShopGroup')));
        $this->assign('ajax_shopVerifyUrl', __URL(addons_url_platform('shop://shop/ajax_shopVerify')));
        $this->assign('delShopGroupUrl', __URL(addons_url_platform('shop://shop/delShopGroup')));
        $this->assign('updateShopLevelUrl', __URL(addons_url_platform('shop://shop/updateShopLevel')));
        $this->assign('updateShopUrl', __URL(addons_url_platform('shop://shop/updateShop')));
        $this->assign('updateShopApplyUrl', __URL(addons_url_platform('shop://shop/updateShopApply')));
        $this->assign('shopAccountWithdrawListUrl', __URL(addons_url_platform('shop://shop/shopAccountWithdrawList')));
        $this->assign('shopAccountWithdrawListDataExcelUrl', __URL(addons_url_platform('shop://shop/shopAccountWithdrawListDataExcel')));
        $this->assign('shopAccountWithdrawInfoUrl', __URL(addons_url_platform('shop://shop/shopAccountWithdrawInfo')));
        $this->assign('shopAccountWithdrawAuditUrl', __URL(addons_url_platform('shop://shop/shopAccountWithdrawAudit')));
        $this->assign('pickupPointListUrl', __URL(addons_url_platform('shop://shop/pickupPointList')));
        $this->assign('getProvinceUrl', __URL(addons_url_platform('shop://shop/getProvince')));
        $this->assign('getCityUrl', __URL(addons_url_platform('shop://shop/getCity')));
        $this->assign('getDistrictUrl', __URL(addons_url_platform('shop://shop/getDistrict')));
        $this->assign('getSelectAddressUrl', __URL(addons_url_platform('shop://shop/getSelectAddress')));
        $this->assign('addPlatformShopUrl', __URL(addons_url_platform('shop://shop/addPlatformShop')));
        $this->assign('arrivalGuideUrl', __URL(addons_url_platform('shop://shop/arrivalGuide')));
        $this->assign('addGuideUrl', __URL(addons_url_platform('shop://shop/addGuide')));
        $this->assign('updateGuideUrl', __URL(addons_url_platform('shop://shop/updateGuide')));
        $this->assign('website_id', $this->website_id);
        $this->assign('pageshow','10');
        $this->assign('shopAccountListUrl', __URL(addons_url_platform('shop://shop/shopAccountList')));
        $this->assign('shopOrderListUrl', __URL(addons_url_platform('shop://shop/shopOrderList')));
        $this->assign('goodsListUrl', __URL(addons_url_platform('shop://shop/goodsList')));
        $this->assign('setShopProtocolUrl', __URL(addons_url_platform('shop://shop/setShopProtocol')));
        $this->assign('setShopSettingUrl', __URL(addons_url_platform('shop://shop/setShopSetting')));
         $this->assign('shopCollectionListUrl', __URL(addons_url('shop://shop/shopCollectionList')));
         $this->assign('cancelCollGoodsOrShopUrl', __URL(addons_url('shop://shop/cancelCollGoodsOrShop')));
         $this->assign('getApplyDetailUrl', __URL(addons_url('shop://shop/getApplyDetail')));
         $this->assign('getShopGoodsCountUrl', __URL(addons_url('shop://shop/getShopGoodsCount')));
         $this->assign('shopApplyModalUrl', __URL(addons_url('shop://shop/shopApplyModal')));
        $web_site=new WebSite();
        $web_info = $web_site->getWebSiteInfo();
        $this->assign('web_info', $web_info);
        $ConfigService = new AddonsConfig();
        $pc_info = $ConfigService->getAddonsConfig('pcport',$this->website_id);
        $pc_info = json_decode($pc_info['value'],true);
        $this->assign("pc_info", $pc_info);
        $this->shop_id = request()->get('shop_id',0);
        $this->member = new Member();
        if($this->module = 'shop'){
            $this->member->uvRecord($this->uid,$this->shop_id);
        }
        $this->goods_group_id =  request()->get('goods_group_id',0);
        // 店内分类
        $this->goods_group = new GoodsGroupService();
        $goods_group_list = $this->goods_group->getGoodsGroupQuery($this->shop_id);
        $this->assign("goods_group_list", $goods_group_list);
        // 店铺信息
        $shop = new ShopService();
        $shop_info = $shop->getShopDetail($this->shop_id);
         $this->assign('uid', $this->uid);
        $this->assign('shop_info', $shop_info);
        $this->assign('title', $shop_info['base_info']['shop_name']);
        $this->assign('shopStatus',1);
        // 店铺是否被收藏
        if (! empty($this->uid)) {
            $is_favorites = $this->member->getIsMemberFavorites($this->uid, $this->shop_id, 'shop');
            $this->assign('is_favorites', $is_favorites);
        } else {
            $this->assign('is_favorites', '-1');
        }
        $this->assign('goods_group_id', $this->goods_group_id);
        
    }

    /**
     * 实现第三方钩子
     *
     * @param array $params            
     */
    
    public function shopList(){
        $shop = new ShopService();
        $shop_type_list = $shop->getShopTypeList(1, 0,['website_id'=> $this->website_id]);
        $this->assign('shop_type_list',$shop_type_list['data']);
        $this->fetch('template/platform/shopList');
    }


    /**
     * 店铺版本
     * @return multitype:number unknown
     */
    public function shopLevelList(){
        $this->fetch('template/platform/shopLevel');
    }
    /**
     * 店铺申请列表
     * @return multitype:number unknown
     */
    public function shopApplyList(){
        $this->fetch('template/platform/shopApplyList');
    }
    /**
     * 店铺申请列表
     * @return multitype:number unknown
     */
    public function shopGroupList(){
        $this->fetch('template/platform/shopGroup');
        
    }
    
    /**
     * 添加店铺分组
     * @return \think\response\View
     */
    public function addShopGroup(){
            $this->fetch('template/platform/addShopGroup');
    }
    
    /**
     * 修改店铺分组
     */
    public function updateShopGroup(){
        $shop=new ShopService();
        $shop_group_id=request()->get('shop_group_id',0);
        $shop_group_info=$shop->getShopGroupDetail($shop_group_id);
        $this->assign('shop_group_info',$shop_group_info);
        $this->fetch('template/platform/addShopGroup');
    }
    /**
     * 审核店铺
     */
    public function shopVerify(){
        $apply_id = request()->get('id',0);
        $shop = new ShopService();
        $result=$shop->getShopApplyDetail($apply_id);
        $this->assign('result',$result);
        $this->fetch('template/platform/shopApplyDetail');
    }

    /**
     * 添加店铺版本
     * @return \think\response\View
     */
    public function addShopLevel(){
            $web_site=new WebSite();
            $model = $this->user->getRequestModel();
            $first_list = Session::get($model.'shop_module_id_array');
            $module_list_one=$web_site->getSystemModuleList(1,0,['module'=>'admin','level'=>1,'module_id'=>['in',$first_list]],'sort');
            $module_list_two=$web_site->getSystemModuleList(1,0,['module'=>'admin','level'=>2,'module_id'=>['in',$first_list]],'sort');
            $module_list_three=$web_site->getSystemModuleList(1,0,['module'=>'admin','level'=>3,'module_id'=>['in',$first_list]],'sort');
            $this->assign('module_list_one',$module_list_one['data']);
            $this->assign('module_list_two',$module_list_two['data']);
            $this->assign('module_list_three',$module_list_three['data']);
            $this->fetch('template/platform/addShopLevel');
    }
    /**
     * 修改店铺版本
     * @return multitype:unknown
     */
    public function updateShopLevel(){
        $shop=new ShopService();
        $instance_typeid=isset($_GET['instance_typeid'])?$_GET['instance_typeid']:'';
        $shop_level_info=$shop->getShopTypeDetail($instance_typeid);
        $this->assign('shop_level_info',$shop_level_info);

        $web_site=new WebSite();
        $model = $this->user->getRequestModel();
        $first_list = Session::get($model.'shop_module_id_array');
        if($first_list){
            $first_lists = [];
            $models = explode(',',$first_list);
            foreach ($models as $k=>$v){
                $module = new ModuleModel();
                $addons_sign = $module->getInfo(['module_id' => $v],'addons_sign')['addons_sign'];
                $addons = new SysAddonsModel();
                $up_status = $addons->getInfo(['id'=>$addons_sign],'up_status')['up_status'];
                if($up_status!=2){
                    $first_lists[] = $v;
                }
            }
            $first_list = implode(',',$first_lists);
        }
        $module_list_one=$web_site->getSystemModuleList(1,0,['module'=>'admin','level'=>1,'module_id'=>['in',$first_list]],'sort');
        $module_list_two=$web_site->getSystemModuleList(1,0,['module'=>'admin','level'=>2,'module_id'=>['in',$first_list]],'sort');
        $module_list_three=$web_site->getSystemModuleList(1,0,['module'=>'admin','level'=>3,'module_id'=>['in',$first_list]],'sort');
        $this->assign('module_list_one',$module_list_one['data']);
        $this->assign('module_list_two',$module_list_two['data']);
        $this->assign('module_list_three',$module_list_three['data']);

        //return view($this->style."Shop/updateShopLevel");
        $this->fetch('template/platform/addShopLevel');
        
    }
    /**
     * 修改店铺
     */
    public function updateShop(){
        $shop_id = isset($_GET['shop_id']) ? $_GET['shop_id'] : 0;
        $shop = new ShopService();
        $group_list = $shop->getShopGroup(1, 0,['website_id'=> $this->website_id],'group_sort asc');
        $this->assign('group_list', $group_list['data']);
        $type_list = $shop->getShopTypeList(1, 0,['website_id'=> $this->website_id]);
        $this->assign('type_list', $type_list['data']);
        $info = $shop->getShopDetail($shop_id);
        $user = new User();
        $user_info = $user->getUserInfoByUid($info['base_info']['uid']);
        $info['base_info']['user_tel'] = $user_info['user_tel'];
        $this->assign('info', $info['base_info']);
        $shop_info = $shop->getShopInfoDetail($shop_id);
        $this->assign('shop_info', $shop_info);
        $this->fetch('template/platform/addShop');
    }


    /**
     * 店铺提现列表
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function shopAccountWithdrawList(){
        $this->fetch('template/platform/shopAccountWithdrawList');
        
    }
    public function shopAccountWithdrawListDataExcel(){
        $this->fetch('template/platform/shopAccountWithdrawListDataExcel');

    }
    /**
     * 添加店铺
     */
    public function addPlatformShop(){
            $shop = new ShopService();
            $group_list = $shop->getShopGroup(1, 0,['website_id'=> $this->website_id], 'group_sort ASC');
            $this->assign('group_list', $group_list['data']);
            $type_list = $shop->getShopTypeList(1, 0,['website_id'=> $this->website_id]);
            $this->assign('type_list', $type_list['data']);
            $this->fetch('template/platform/addShop');
        
    }
    /**
     * 店铺账目列表
     */
    public function shopAccountList(){

        $this->fetch('template/platform/shopAccountList');
    }

    /**
     * 入驻店订单
     */
    public function shopOrderList(){
        $orderServer = new OrderType();
        $orderTypeList = $orderServer->getOrderTypeList();
        $this->assign('orderTypeList', $orderTypeList);
        $this->fetch('template/platform/shopOrderList');
    }

    /**
     * 店铺商品列表
     */
    public function goodsList(){
        $state = request()->get('state', 1);
        $this->assign('state', $state);
         //判断pc端、小程序是否开启
        $addons_conf = new AddonsConfig();
        $pc_conf = $addons_conf->getAddonsConfig('pcport', $this->website_id);
        $is_minipro = getAddons('miniprogram', $this->website_id);
        if ($is_minipro) {
            $weixin_auth = new WeixinAuthModel();
            $new_auth_state = $weixin_auth->getInfo(['website_id' => $this->website_id], 'new_auth_state')['new_auth_state'];
            if (isset($new_auth_state) && $new_auth_state === 0) {
                $is_minipro = 1;
            } else {
                $is_minipro = 0;
            }
        }
        $website_mdl = new WebSiteModel();
        //查看移动端的状态
        $wap_status = $website_mdl->getInfo(['website_id' => $this->website_id], 'wap_status')['wap_status'];
        $this->assign('wap_status', $wap_status);
        $this->assign('is_pc_use', $pc_conf['is_use']);
        $this->assign('is_minipro', $is_minipro);
        $this->fetch('template/platform/settledGoodsList');
    }
    /**
     * 店铺协议
     */
    public function shopProtocol(){
        $shop = new ShopService();
        $shopProtocol = array();
        $shopProtocol['direction'] = $shop->getShopProtocol('DIRECTION');
        $shopProtocol['standard'] = $shop->getShopProtocol('STANDARD');
        $shopProtocol['demand'] = $shop->getShopProtocol('DEMAND');
        $shopProtocol['cost'] = $shop->getShopProtocol('COST');
        $shopProtocol['join'] = $shop->getShopProtocol('JOIN');
        $this->assign("shopProtocol", $shopProtocol);
        $this->fetch('template/platform/shopProtocol');
    }
    /**
     * 店铺设置
     */
    public function shopSetting()
    {
        $shop= new ShopService();
        $shopSetting = $shop->getShopSetting();
        $this->assign("shopSetting", $shopSetting);
        $this->fetch('template/platform/shopSetting');
    }
    
    /*-------------------------------------------------------------------前端钩子开始-----------------------------------------------------------------------*/
    
    /**
     * 商家入驻首页
     * @return \think\response\View
     */
    public function applyIndex($param = array())
    {
        $member = new Member();
        $is_system = $member->getSessionUserIsSystem();
        $this->assign("is_system", $is_system);
        $apply_state = $member->getMemberIsApplyShop($this->uid);
        $this->assign("apply_state", $apply_state);
        $user_info = $member->getUserInfo();
        $this->assign("member_info", $user_info);
        //获取申请相关说明信息
        $shop = new ShopService();
        $shopProtocol = array();
        $shopProtocol['direction'] = $shop->getShopProtocol('DIRECTION');
        $shopProtocol['standard'] = $shop->getShopProtocol('STANDARD');
        $shopProtocol['demand'] = $shop->getShopProtocol('DEMAND');
        $shopProtocol['cost'] = $shop->getShopProtocol('COST');
        $this->assign("shopProtocol", $shopProtocol);
        $this->fetch('template/shop/applyIndex');
    }

    /**
     * 功能：店铺街
     */
    public function shopStreet($param=array())
    {
        $shop = new ShopService();
        $condition['website_id'] = $this->website_id;
        $shop_name = isset($param['shop_name']) ? $param['shop_name'] : ''; // 店铺名称
        $shop_group_id = isset($param['shop_group_id']) ? $param['shop_group_id'] : ''; // 店铺分类
        $order_type = isset($param['order_type']) ? $param['order_type'] : ''; // 排序类型 为1销售排行2信誉排行
        $sort = isset($param['sort']) ? $param['sort'] : 'asc'; // 倒排正排
        $page = isset($param['page']) ? $param['page'] : '1'; // pageindex
        $path_info = substr(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "", 1);
        $get_array = request()->get();
        $query_string = '';
        if (array_key_exists('page', $get_array)) {
            $tag = '&';
        } else {
            if (! empty($get_array)) {
                $tag = '&';
            } else{
                $tag = '?';
            }
        }
        foreach ($get_array as $k => $v) {
            if ($k != 'page') {
                $query_string .= $tag . $k . '=' . $v;
            }
        }
        $this->assign('path_info', $path_info);
        $this->assign('query_string', $query_string);
        $order = "shop_sort " . $sort;
        if ($order_type == 1) {
            $order = "shop_sales " . $sort;
        } else 
            if ($order_type == 2) {
                $order = "shop_credit " . $sort;
            }
        
        $condition['shop_state'] = 1;
        if (! empty($shop_group_id)) {
            $condition['shop_group_id'] = $shop_group_id;
        }
        
        if (! empty($shop_name)) {
            $condition['shop_name'] = array(
                "like",
                "%" . $shop_name . "%"
            );
        }
        if($sort=='desc'){
            $newsort = 'asc';
        }else{
            $newsort = 'desc';
        }
        
        $shop_list = $shop->getShopList($page, 6, $condition, $order); // 店铺查询
        $shop_group_list = $shop->getShopGroup(1,0,['website_id'=>$this->website_id],'group_sort asc'); // 店铺分类
        $assign_get_list = array(
            'order_type' => $order_type, // 排序类型
            'shop_group_id' => $shop_group_id, // 店铺类型
            'shop_name' => $shop_name, // 搜索名称
            'page' => $page, // 当前页
            'sort' => $sort, // 排序
            'newsort' => $newsort,
            'shop_list' => $shop_list['data'], // 店铺列表
            'page_count' => $shop_list['page_count'], // 总页数
            'total_count' => $shop_list['total_count'], // 总条数
            'shop_group_list' => $shop_group_list['data']
        ); // 店铺分页
        
        foreach ($assign_get_list as $key => $value) {
            $this->assign($key, $value);
        }
        $this->assign('page_num', 5);
        $this->fetch('template/shop/shopList');
    }

    /**
     * 功能：店铺首页
     */
    public function shopIndex($param=array())
    {
        if(!isset($param['shop_id'])){
            header("location:".__URL('SHOP_MAIN'));
            return;
        }
        $shop_id = $param['shop_id'];
        $com = new Common($shop_id, $this->website_id);
        $pcCustomConfig = new SysPcCustomConfigModel();
        if (!request()->isMobile()) {
            //使用模板
            $usedTem = $pcCustomConfig->getInfo(['type'=>2,'template_type'=>'shop_templates','shop_id'=>$shop_id,'website_id'=>$this->website_id],'code');
            $suffix = (isset($usedTem['code']) ? trim($usedTem['code']) : '');
            if (empty($suffix)) {
                //默认模板
                $defaultTem = $pcCustomConfig->getInfo(['type'=>1,'template_type'=>'shop_templates','shop_id'=>$shop_id,'website_id'=>$this->website_id],'code');
                $suffix = (isset($defaultTem['code']) ? trim($defaultTem['code']) : '');
            }
        }
        $dir = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$shop_id.'/shop_templates/'.$suffix;
        $dir_common = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/common';
        $dir_shop_common = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$shop_id.'/common';
        if(!$suffix){
            $com->createTem();
        }
        if (!request()->isMobile() && $suffix && file_exists($dir)) {
            $page = $com->get_html_file($dir . '/pc_html.php');
            $nav_page = $com->get_html_file($dir . '/nav_html.php');
            $topBanner = $com->get_html_file($dir . '/topBanner.php');
            $shopBanner = $com->get_html_file($dir_shop_common . '/shopbanner_html.php');
            $bottom = $com->get_html_file($dir_common . '/bottom_html.php');
            $logo_pic = $com->getLogo($suffix);
            $categories_pro = $com->get_category_tree_leve_one(0);
            $ntype = 'shop';
            $navigator_list = $com->get_navigator($ntype);
            $pc_page['tem'] = $suffix;
            $this->assign('pc_page', $pc_page);
            $this->assign('nav_page', $nav_page);
            $this->assign('page', $page);
            $this->assign('logo_pic', $logo_pic);
            $this->assign('categories_pro', $categories_pro);
            $this->assign('navigator_list', $navigator_list);
            $this->assign('topBanner', $topBanner);
            $this->assign('shopBanner', $shopBanner);
            $this->assign('bottom', $bottom);
            $this->assign('ntype', $ntype);
            $this->fetch('template/shop/shopIndex');
        }
    }

    /**
     * 功能：店铺商品分类
     */
    public function shopGoodList($param=array())
    {
        $goods = new Goods();
        $shop_id = isset($param['shop_id']) ? $param['shop_id'] : 0;
        $keyword = isset($param["keyword"]) ? $param["keyword"] : '';
        $order_type = isset($param['order_type']) ? $param['order_type'] : ''; // 1销量2价钱3评论
        $sort = isset($param['sort']) ? $param['sort'] : 'asc'; // 倒排正排
        $is_shipping_fee = isset($param['is_shipping_fee']) ? $param['is_shipping_fee'] : ''; // 1就是免运费
        $is_stock = isset($param['is_stock']) ? $param['is_stock'] : ''; // 是否有库存
        $page =  isset($param['page']) ? $param['page'] : '1';
        $page_size = 24;
        $path_info = substr(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "", 1);
        $get_array = request()->get();
        $query_string = '';
        if (array_key_exists('page', $get_array)) {
            $tag = '&';
        } else {
            if (! empty($get_array)) {
                $tag = '&';
            } else{
                $tag = '?';
            }
        }
        foreach ($get_array as $k => $v) {
            if ($k != 'page') {
                $query_string .= $tag . $k . '=' . $v;
            }
        }
        $this->assign('path_info', $path_info);
        $this->assign('query_string', $query_string);
        // 排序
        $order = "";
        if($order_type){
            switch ($order_type) {
                case 1:
                    $order = ' ng.create_time ';
                    break;
                case 2:
                    $order = ' ng.sales ';
                    break;
                case 3:
                    $order = ' ng.price ';
                    break;
            }
            $order = $order . $sort;
        }
        
        // 条件筛选
        $condition = array();
        // 1.关键词搜索
        if (! empty($keyword)) {
            $condition['ng.goods_name'] = array(
                "like",
                "%" . $keyword . "%"
            );
        }
        // 2.免运费
        if (! empty($is_shipping_fee)) {
            $condition['ng.shipping_fee'] = '0';
        }
        // 3.有库存
        if (! empty($is_stock)) {
            $condition['ng.stock'] = array(
                'GT',
                0
            );
        }
        // 新品推荐
        $goods_new_list = $goods->getSearchGoodsList(1, 5, [
            "state" => 1,
            "shop_id" => $param['shop_id'],
            "website_id" => $this->website_id
                ], "create_time desc",'goods_id,goods_name,price,sales,picture,shop_id');
        
        $this->assign("goods_new_list", $goods_new_list['data']);
        // 一级筛选条件（排序）
        $screen_list_two = array(
            array(
                'order_name' => '新品',
                'order_type' => 1
            ),
            array(
                'order_name' => '销量',
                'order_type' => 2
            ),
            array(
                'order_name' => '价格',
                'order_type' => 3
            )
        );
        
        $condition['ng.shop_id'] = $param['shop_id'];
        $condition['ng.website_id'] = $this->website_id;
        $good_list = $goods->getGoodsList($page, $page_size, $condition, $order);
        
        // 拼接链接参数
        $condition_url = '';
        $condition_url_par = array(
            'is_shipping_fee' => $is_shipping_fee,
            'is_stock' => $is_stock,
            'keyword' => $keyword
        );
        foreach ($condition_url_par as $key => $value) {
            if (! empty($value)) {
                $condition_url .= '&' . $key . '=' . $value;
            }
        }
        
        $assign_get_list = array(
            'shop_id' => $param['shop_id'], // 店铺id
            'website_id' => $this->website_id, // 商户平台id
            'sort' => $sort, // 排序
            'order_type' => $order_type, // 排序类型
            'is_shipping_fee' => $is_shipping_fee, // 是否包邮
            'is_stock' => $is_stock, // 库存
            'good_list' => $good_list['data'], // 列表
            'condition_url' => rtrim($condition_url, '&'), // 链接所需的url参数
            'screen_list_two' => $screen_list_two
        ); // 一级筛选条件列表
        //首页限时折扣限时
        $Promotion = new Promotion;
        $discount_num = $Promotion->get_best_discount($param['shop_id']);
        $discount_num = $discount_num['0']['discount_num'];  //折扣
        foreach ($good_list['data'] as $k=>$v){
            //重新赋值活动价格
            if(!empty($Promotion->check_is_discount_product($v['goods_id'],$param['shop_id']))){
                $good_list['data'][$k]['promotion_price'] = $Promotion->get_discount_price($v['price'],$discount_num);
            }
        }
        foreach ($assign_get_list as $key => $value) {
            $this->assign($key, $value);
        }
        $this->assign('page', $page);
        $this->assign('page_count', $good_list['page_count']);
        $this->assign('total_count', $good_list['total_count']);
        $this->assign('page_num', 5);
        $this->fetch('template/shop/shopSearch');
    }

    /**
     * 商家入驻第一步：同意协议
     */
    public function applyFristAgreement($param=array())
    {
        $shop = new ShopService();
        $join = $shop->getShopProtocol('JOIN');
        $this->assign("join", $join);
        $this->assign("is_read", isset($param['is_read']) ? $param['is_read'] : 0);
        $this->fetch('template/shop/applyFirstAgreement');
    }

    /**
     * 商家入驻第二步：公司信息认证
     * @return \think\response\View
     */
    public function applySecondCompanyInfo($param=array())
    {
        $shop = new ShopService();
        $member = new Member();
        $shop_type_list = $shop->getShopTypeList(1,0,['website_id'=>$this->website_id]);
        $this->assign('shop_type_list', $shop_type_list['data']);
        $shop_group = $shop->getShopGroup(1,0,['website_id'=>$this->website_id],'group_sort asc');
        $this->assign('shop_group', $shop_group['data']);
        $apply_state = $member->getMemberIsApplyShop($this->uid);
        $refuse_reason = $shop->getApplyRefuseReason($this->uid);
        $this->assign("apply_state", $apply_state);
        $this->assign("refuse_reason", $refuse_reason);
        $this->assign("is_read", isset($param['is_read']) ? $param['is_read'] : 0);
        $this->fetch('template/shop/applySecondCompanyInfo');
    }

    /**
     * 商家入驻第三步：店铺信息认证
     * @return \think\response\View
     */
    public function applyThirdStoreInfo($param=array())
    {
        $this->fetch('template/shop/applyThirdStoreInfo');
    }

    /**
     * 商家入驻，等待审核
     * @return \think\response\View
     */
    public function applyFinish($param=array())
    {
        $this->fetch('template/shop/applyFinish');
    }

    /**
     * 我的收藏-->店铺收藏
     */
    public function shopCollectionList()
    {
        $this->fetch('template/shop/shopCollectionList');
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