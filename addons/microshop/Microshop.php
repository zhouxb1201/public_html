<?php
namespace addons\microshop;
use addons\Addons as Addo;
use addons\microshop\model\VslOrderMicroShopProfitModel;
use data\model\VslMemberModel;
use addons\microshop\service\MicroShop as  MicroShopService;
use data\model\VslOrderModel;
use think\Db;

class Microshop extends Addo
{
    public $info = array(
        'name' => 'microshop', // 插件名称标识
        'title' => '微店', // 插件中文名
        'description' => '成为店主轻松获得收益', // 插件概述
        'status' => 1, // 状态 1启用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', // 版本号
        'has_addonslist' => 1, // 是否有下级插件 例如：第三方登录插件下有 qq登录，微信登录
        'content' => '', // 插件的详细介绍或使用方法
        'config_hook' => 'microShopProfile',
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197104.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782164.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782285.png',
    ); // 设置文件单独的钩子

    public $menu_info = array(
        [
            'module_name' => '微店',
            'parent_module_name' => '应用', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '成为店主轻松获得收益', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'microShopProfile',
            'is_main' => 1
        ],

        [
            'module_name' => '微店概况',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '微店概况', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'microShopProfile'
        ],

        [
            'module_name' => '店主等级',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => 2, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店主可设置不同等级享受不同的收益，权重越大等级越高。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopkeeperLevelList'
        ],

        [
            'module_name' => '添加店主等级',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '添加店主等级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addShopkeeperLevel'
        ],

        [
            'module_name' => '修改店主等级',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '修改店主等级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateShopkeeperLevel'
        ],
        [
            'module_name' => '删除店主等级',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '删除店主等级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'deleteShopkeeperLevel'
        ],
        [
            'module_name' => '申请成为店主',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '申请成为店主', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopkeeperApply',
            'is_member'=>1
        ],
        [
            'module_name' => '修改店主状态',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '修改店主状态', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'setStatus'
        ],
        [
            'module_name' => '微店中心',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '微店中心', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'microshopIndex',
            'is_member'=>1
        ],
        [
            'module_name' => '店主列表',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => 1, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '微店店主可挑选平台商品进行销售从中获得收益，另外也可以通过自己够买平台商品与下线开店成为店主获得收益。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopkeeperList'
        ],
        [
            'module_name' => '店主详情',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店主详情', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopkeeperInfo'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => 4, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '可设置成为店主的条件，收益模式（最高三级）与成为店主的申请协议。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'microShopSetting'
        ],

        [
            'module_name' => '基本设置',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '可设置成为店主的条件，收益模式（最高三级）与成为店主的申请协议。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'basicMicroShopSetting'
        ],
        [
            'module_name' => '结算设置',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' =>0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '微店结算设置', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'settlementMicroShopSetting'
        ],
        [
            'module_name' => '申请协议',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店主申请协议', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'applicationMicroShopAgreement'
        ],
        [
            'module_name' => '收益提现',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '收益提现', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'profitWithdraw',
            'is_member'=>1
        ],
        [
            'module_name' => '收益明细',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '收益明细', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'profitDetail',
            'is_member'=>1
        ],
        [
            'module_name' => '提现详情',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '提现详情', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'profitWithdrawDetail',
            'is_member'=>1
        ],
        [
            'module_name' => '我的收益',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '我的收益', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'myProfit',
            'is_member'=>1
        ],
        [
            'module_name' => '收益流水',
            'parent_module_name' => '财务', // 上级模块名称 用来确定上级目录
            'sort' => 10, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '微店店长下线开店、会员通过微店购买商品、店长自己购买商品都可以产生收益记录，当账目有出入时可通过流水追溯原由。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'profitRecordsList'
        ],
        [
            'module_name' => '收益提现列表',
            'parent_module_name' => '财务', // 上级模块名称 用来确定上级目录
            'sort' => 9, // 菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '微店店长下线开店、会员通过微店购买商品、店长自己购买商品都可以产生收益记录，商家需要在该列表审核与打款，商家也可在微店应用“基础设置》结算设置”设置为自动审核与自动打款。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'profitWithdrawList'
        ],
        [
            'module_name' => '订单支付成功',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '订单支付成功', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderPayMicroShopCalculate',
            'is_member'=>1
        ],
        [
            'module_name' => '订单创建成功',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '订单创建成功', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderMicroShopCalculate',
            'is_member'=>1
        ],
        [
            'module_name' => '订单完成收益计算',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '订单完成收益计算', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateOrderMicroShop',
            'is_member'=>1
        ],
        [
            'module_name' => '退款完成收益计算',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '退款完成收益计算', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateMicroShopMoney',
            'is_member'=>1
        ],
        [
            'module_name' => '店主申请状态',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店主申请状态', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'shopkeeperStatus',
            'is_member'=>1
        ],
        [
            'module_name' => '成为店主续费升级',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '成为店主续费升级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderMicroShop',
            'is_member'=>1
        ],
        [
            'module_name' => '店主等级到期',
            'parent_module_name' => '微店', // 上级模块名称 用来确定上级目录
            'sort' => '', // 菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '店主等级到期降为默认', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'autoDownMicLevel',
            'is_member'=>1
        ]
    ) // 钩子名称（需要该钩子调用的页面）
    ;
     public function __construct(){
        parent::__construct();
         $config= new MicroShopService();
         $list = $config->getmicroshopSite($this->website_id);
         if($this->merchant_expire==1){
             $this->assign('merchant_expire',$this->merchant_expire);
         }
         $this->assign("website", $list);
         $this->assign('getWithdrawCountUrl', __URL(addons_url_platform('microshop://microshop/getWithdrawCount')));
         $this->assign('updateLevelUrl', __URL(addons_url_platform('microshop://microshop/updateLevel')));
        $this->assign('shopkeeperListUrl', __URL(addons_url_platform('microshop://microshop/shopKeeperList')));
        $this->assign('microShopProfitUrl', __URL(addons_url_platform('microshop://microshop/microShopProfit')));
        $this->assign('microShopOrderProfitUrl', __URL(addons_url_platform('microshop://microshop/microShopOrderProfit')));
        $this->assign('microShopSettingUrl', __URL(addons_url_platform('microshop://microshop/microShopSetting')));
        $this->assign('addShopkeeperLevelUrl', __URL(addons_url_platform('microshop://microshop/addShopkeeperLevel')));
        $this->assign('updateShopkeeperLevelUrl', __URL(addons_url_platform('microshop://microshop/updateShopkeeperLevel')));
        $this->assign('shopkeeperLevelListUrl', __URL(addons_url_platform('microshop://microshop/shopkeeperLevelList')));
        $this->assign('website_id', $this->website_id);
        $this->assign('basicMicroShopSettingUrl', __URL(addons_url_platform('microshop://microshop/basicMicroShopSetting')));
        $this->assign('settlementMicroShopSettingUrl', __URL(addons_url_platform('microshop://microshop/settlementMicroShopSetting')));
        $this->assign('applicationMicroShopAgreementUrl', __URL(addons_url_platform('microshop://microshop/applicationMicroShopAgreement')));
        $this->assign('deleteShopkeeperLevelUrl', __URL(addons_url_platform('microshop://microshop/deleteShopkeeperLevel')));
        $this->assign('shopkeeperInfoUrl', __URL(addons_url_platform('microshop://microshop/shopkeeperInfo')));
        $this->assign('setMicroShopStatusUrl', __URL(addons_url_platform('microshop://microshop/setStatus')));
        $this->assign('delShopkeeperUrl', __URL(addons_url_platform('microshop://microshop/delShopkeeper')));


        $this->assign('profitWithdrawListUrl', __URL(addons_url_platform('microshop://microshop/profitWithdrawList')));
        $this->assign('profitWithdrawListDataExcelUrl', __URL(addons_url_platform('microshop://microshop/profitWithdrawListDataExcel')));
        $this->assign('profitRecordsListUrl', __URL(addons_url_platform('microshop://microshop/profitRecordsList')));
        $this->assign('profitRecordsDataExcelUrl', __URL(addons_url_platform('microshop://microshop/profitRecordsDataExcel')));
        $this->assign('profitWithdrawInfoUrl', __URL(addons_url_platform('microshop://microshop/profitWithdrawInfo')));
        $this->assign('profitWithdrawAuditUrl', __URL(addons_url_platform('microshop://microshop/profitWithdrawAudit')));
        $this->assign('profitInfoUrl', __URL(addons_url_platform('microshop://microshop/profitInfo')));

        $this->assign('shopkeeperApplyUrl', __URL(addons_url('microshop://microshop/shopkeeperApply')));
        $this->assign('shopkeeperStatusUrl', __URL(addons_url('microshop://microshop/shopkeeperStatus')));
        $this->assign('microShopIndexUrl', __URL(addons_url('microshop://microshop/microshopIndex')));
        $this->assign('microShopOrderUrl', __URL(addons_url('microshop://microshop/microshopOrder')));
        $this->assign('profitDetailUrl', __URL(addons_url('microshop://microshop/profitDetail')));
        $this->assign('customerListUrl', __URL(addons_url('microshop://microshop/customerList')));
        $this->assign('profitWithdrawUrl', __URL(addons_url('microshop://microshop/profitWithdraw')));
        $this->assign('myProfitUrl', __URL(addons_url('microshop://microshop/myprofit')));
        $this->assign('withdrawDetailUrl', __URL(addons_url('microshop://microshop/withdrawDetail')));
    }
    /**
     * 微店概况
     */
    public function microShopProfile(){
        $month_begin = date('Y-m-01', strtotime(date("Y-m-d")));
        $month_end = date('Y-m-d', strtotime("$month_begin +1 week -1 day"));
        $this->assign("start_date", $month_begin);
        $this->assign("end_date", $month_end);
        $this->fetch('template/platform/microShopProfile');
    }
    /**
     * 店主列表
     */
    public function shopkeeperList()
    {
        $level = new MicroShopService();
        $Shopkeeper_level = $level->getShopkeeperLevelList(1,0,['website_id'=>$this->website_id]);
        $this->assign('Shopkeeper_level',$Shopkeeper_level['data']);
        $this->fetch('template/platform/shopkeeperList');
    }

    /**
     * 店主详情页面
     */
    public function shopkeeperInfo(){
        $member = new MicroShopService();
        $uid = $_GET['Shopkeeper_id'];
        $res= $member->getShopkeeperInfo($uid);
        $Shopkeeper_level = $member->getShopkeeperLevelList(1,0,['website_id'=>$this->website_id]);
        $this->assign('Shopkeeper_level',$Shopkeeper_level['data']);
        $this->assign('info',$res);
        $this->fetch('template/platform/shopkeeperInfo');
    }

    /**
     * 店主等级
     */
    public function shopkeeperLevelList(){

        $this->fetch('template/platform/shopkeeperLevelList');
    }

    /**
     * 微店设置
     */
    public function microShopSetting()
    {
        $this->basicMicroShopSetting();
    }
    /**
     * 基本设置
     */
    public function basicMicroShopSetting()
    {
        $config= new MicroShopService();
        $list = $config->getMicroShopSite($this->website_id);
        $this->assign("website", $list);
        $this->fetch('template/platform/basicSetting');
    }
    /**
     * 结算设置
     */
    public function settlementMicroShopSetting()
    {
        $config= new MicroShopService();
        $list = $config->getMicroShopSettlementSite($this->website_id);
        $this->assign("website", $list);
        $this->fetch('template/platform/settlementSetting');
    }
    /**
     * 申请协议
     */
    public function applicationMicroShopAgreement()
    {
        $config= new MicroShopService();
        $list = $config->getMicroShopAgreementSite($this->website_id);
        $this->assign("website", $list);
        $this->fetch('template/platform/applicationAgreement');
    }

    /**
     * 添加店主等级
     */
    public function addShopkeeperLevel(){
        $Shopkeeper = new MicroShopService();
        $level_weight= $Shopkeeper->getLevelWeight();
        $ungoodsid = $Shopkeeper->getLevelGoods();
        $this->assign("ungoodsid", $ungoodsid);
        $this->assign("level_weight", implode(',',$level_weight));
        $this->fetch('template/platform/addShopkeeperLevel');
    }
    /**
     * 修改店主等级
     */
    public function updateShopkeeperLevel(){
        $Shopkeeper = new MicroShopService();
        $id=isset($_GET['id'])?$_GET['id']:'';
        $Shopkeeper_level_info=$Shopkeeper->getShopkeeperLevelInfo($id);
        $this->assign('list',$Shopkeeper_level_info);
        $Shopkeeper = new MicroShopService();
        $level_weight=$Shopkeeper->getLevelWeight();
        $ungoodsid = $Shopkeeper->getLevelGoods($id);
        $this->assign("ungoodsid", $ungoodsid);
        $this->assign("level_weight", implode(',',$level_weight));
        $this->fetch('template/platform/updateShopkeeperLevel');
    }


    /**
     * 收益提现列表
     */
    public function profitWithdrawList(){
        $this->fetch('template/platform/profitWithdrawList');

    }
    public function profitWithdrawListDataExcel(){
        $this->fetch('template/platform/profitWithdrawListDataExcel');

    }
    /**
     * 收益流水列表
     */
    public function profitRecordsList(){
        $this->fetch('template/platform/profitRecordsList');
    }
    public function profitRecordsDataExcel(){
        $this->fetch('template/platform/profitRecordsDataExcel');

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
    
/*-------------------------------------------------------------------前端钩子开始-----------------------------------------------------------------------*/

     /**
     * 订单创建成功后收益计算
     */
    public function orderMicroShopCalculate($params)
    {
        $order= new VslOrderModel();
        $params['uid'] = $order->getInfo(['order_id'=>$params['order_id']],'buyer_id')['buyer_id'];
        $profitCalculate = new MicroShopService();
        $profitCalculate->orderMicroShopProfit($params);
    }
    /**
     * 订单支付成功后收益计算
     */
    public function orderPayMicroShopCalculate($params)
    {
        $order_profit = new VslOrderMicroShopProfitModel();
        $Shopkeeper_order_ids = $order_profit->Query(['website_id'=>$params['website_id']],'order_id');
        $orderids = array_unique($Shopkeeper_order_ids);
        $order= new VslOrderModel();
        $order_info = $order->getInfo(['order_id'=>$params['order_id']],'buyer_id,shopkeeper_id,order_type');
        $params['uid'] = $order_info['buyer_id'];
        if(in_array($params['order_id'],$orderids)) {
            $Shopkeeper = new VslMemberModel();
            $member = $Shopkeeper->getInfo(['uid'=>$params['uid']],'*');
            if($order_info['shopkeeper_id'] || $member['isshopkeeper']==2){//购买的微店商品或者自购
                $params['status'] = 3;
                $this->updateOrderMicroShop($params);
            }
        }
    }
    /**
     * 订单退款成功后需要重新计算订单的收益
     */
    public function updateMicroShopMoney($params)
    {
        $order_profit = new VslOrderMicroShopProfitModel();
        $Shopkeeper_order_ids = $order_profit->Query(['website_id'=>$params['website_id']],'order_id');
        $orderids = array_unique($Shopkeeper_order_ids);
        if(in_array($params['order_id'],$orderids)) {
            $order = new VslOrderModel();
            $order_info = $order->getInfo(['order_id' => $params['order_id']], 'buyer_id,shopkeeper_id');
            $params['uid'] = $order_info['buyer_id'];
            $Shopkeeper = new VslMemberModel();
            $member = $Shopkeeper->getInfo(['uid' => $params['uid']], '*');
            if ($order_info['shopkeeper_id'] || $member['isshopkeeper'] == 2) {//购买的微店商品或者自购
                // 重新计算微店收益
                $params['status'] = 2;
                $this->updateOrderMicroShop($params);
            }
        }
    }

    /**
     * 收益结算(订单完成)
     */
    public function updateOrderMicroShop($params)
    {
        $order_profit = new VslOrderMicroShopProfitModel();
        $Shopkeeper_order_ids = $order_profit->Query(['website_id'=>$params['website_id']],'order_id');
        $orderids = array_unique($Shopkeeper_order_ids);
        $config = new MicroShopService();
        if(in_array($params['order_id'],$orderids)) {
            $order = new VslOrderModel();
            $params['uid'] = $order->getInfo(['order_id' => $params['order_id']], 'buyer_id')['buyer_id'];
            $member = new VslMemberModel();
            $member_info = $member->getInfo(['uid' => $params['uid']], '*');
            $Shopkeeper = new MicroShopService();
            $order_model = new VslOrderModel();
            $order_info = $order_model->getInfo(['order_id' => $params['order_id']], '*');
            $order_status = $order_info["order_status"];//当前订单状态
            if($params['check_status'] == 1 && $order_status == 4){
                $order_status = 3; //手动执行已完成订单，强制变更订单状态
            }
            if ($member_info['isshopkeeper']==2|| $order_info['shopkeeper_id']) {//购买的微店商品或者自购
                $list = $config->getmicroshopSite($params['website_id']);
                $data['profitA'] = 0;
                $data['profitB'] = 0;
                $data['profitC'] = 0;
                $data['profit'] = 0;
                $order_profitA = $order_profit->Query(['order_id' => $params['order_id']], 'profitA');
                $order_profitA_id = $order_profit->getInfo(['order_id' => $params['order_id']], 'profitA_id');
                $order_profitB = $order_profit->Query(['order_id' => $params['order_id']], 'profitB');
                $order_profitB_id = $order_profit->getInfo(['order_id' => $params['order_id']], 'profitB_id');
                $order_profitC = $order_profit->Query(['order_id' => $params['order_id']], 'profitC');
                $order_profitC_id = $order_profit->getInfo(['order_id' => $params['order_id']], 'profitC_id');
                if($params['status'] == 3 || $params['status'] == 2) {
                    $order_profitA = $order_profit->Query(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'profitA');
                    $order_profitA_id = $order_profit->getInfo(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'profitA_id');
                    $order_profitB = $order_profit->Query(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'profitB');
                    $order_profitB_id = $order_profit->getInfo(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'profitB_id');
                    $order_profitC = $order_profit->Query(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'profitC');
                    $order_profitC_id = $order_profit->getInfo(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'profitC_id');
                }
                if ($order_profitA_id['profitA_id'] && $list['microshop_pattern'] >= 1) {
                    $data['uid'] = $order_profitA_id['profitA_id'];//一级收益对应的店主
                    // 订单交易完成
                    if ($order_status == 4) {
                        //订单交易完成状态
                        $data['status'] = 1;
                        $data['order_id'] = $params['order_id'];
                        $data['website_id'] = $order_info["website_id"];
                        $data['profit'] = array_sum($order_profitA);//一级收益
                        // 发放订单的一级收益
                        $Shopkeeper->addprofitmicroshop($data);
                    }
                    // 订单退款成功
                    if ($params['status'] == 2) {
                        // 得到订单用户id
                        $data['status'] = 2;
                        $data['order_id'] = $params['order_id'];
                        $data['website_id'] = $order_info["website_id"];
                        $data['profit'] = array_sum($order_profitA);//一级收益
                        // 订单的一级收益
                        $Shopkeeper->addprofitmicroshop($data);
                    }
                    // 订单支付成功
                    if ($params['status'] == 3) {
                        // 得到订单用户id
                        $data['status'] = 3;
                        $data['order_id'] = $params['order_id'];
                        $data['website_id'] = $order_info["website_id"];
                        $data['profit'] = array_sum($order_profitA);//一级收益
                        // 订单的一级收益
                        $Shopkeeper->addprofitmicroshop($data);
                    }
                }
                if ($order_profitB_id['profitB_id'] && $list['microshop_pattern'] >= 2) {
                    $data['uid'] = $order_profitB_id['profitB_id'];//二级收益对用的店主
                    // 订单交易完成
                    if ($order_status == 4) {
                        // 得到订单用户id
                        $data['status'] = 1;
                        $data['order_id'] = $params['order_id'];
                        $data['website_id'] = $order_info["website_id"];
                        $data['profit'] = array_sum($order_profitB);//二级收益
                        // 发放订单的二级收益
                        $Shopkeeper->addProfitmicroshop($data);
                    }
                    // 订单退款成功
                    if ($params['status'] == 2) {
                        // 得到订单用户id
                        $data['status'] = 2;
                        $data['order_id'] = $params['order_id'];
                        $data['website_id'] = $order_info["website_id"];
                        $data['profit'] = array_sum($order_profitB);//二级收益
                        // 订单的二级收益
                        $Shopkeeper->addProfitmicroshop($data);
                    }
                    // 订单支付成功
                    if ($params['status'] == 3) {
                        // 得到订单用户id
                        $data['status'] = 3;
                        $data['order_id'] = $params['order_id'];
                        $data['website_id'] = $order_info["website_id"];
                        $data['profit'] = array_sum($order_profitB);//二级收益
                        // 订单的二级收益
                        $Shopkeeper->addProfitmicroshop($data);
                    }
                }
                if ($order_profitC_id['profitC_id'] && $list['microshop_pattern'] >= 3) {
                        $data['uid'] = $order_profitC_id['profitC_id'];
                        // 订单交易完成
                        if ($order_status == 4) {
                            // 得到订单用户id
                            $data['status'] = 1;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['profit'] = array_sum($order_profitC);//三级收益
                            // 发放订单的三级收益
                            $Shopkeeper->addProfitmicroshop($data);
                        }
                        // 订单退款成功
                        if ($params['status'] == 2) {
                            // 得到订单用户id
                            $data['status'] = 2;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['profit'] = array_sum($order_profitC);//三级收益
                            // 订单的三级收益
                            $Shopkeeper->addProfitmicroshop($data);
                        }
                        // 订单支付成功
                        if ($params['status'] == 3) {
                            // 得到订单用户id
                            $data['status'] = 3;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['profit'] = array_sum($order_profitC);//三级收益
                            // 订单的三级收益
                            $Shopkeeper->addProfitmicroshop($data);
                        }
                    }
            }
        }
    }
    /**
     * 等级到期
     */
    public function autoDownMicLevel($params)
    {
        
        $profitCalculate = new MicroShopService();
        $profitCalculate->downLevel($params['website_id']);
    }

    /*-------------------------------------------------------------------前端页面开始-----------------------------------------------------------------------*/


}