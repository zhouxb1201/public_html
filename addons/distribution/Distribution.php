<?php
namespace addons\distribution;
use addons\Addons as Addo;
use addons\distribution\model\VslOrderDistributorCommissionModel;
use data\model\VslMemberModel;
use addons\distribution\service\Distributor as  DistributorService;
use data\model\VslOrderModel;
use data\model\UserModel;
use data\service\Goods as GoodsService;
use data\service\Config;
use data\service\GoodsCategory;

class Distribution extends Addo
{
    public $info = array(
        'name' => 'distribution', // 插件名称标识
        'title' => '全网分销', // 插件中文名
        'description' => '全网分销，下线下单上线返佣', // 插件概述
        'status' => 1, // 状态 1启用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', // 版本号
        'has_addonslist' => 1, // 是否有下级插件 例如：第三方登录插件下有 qq登录，微信登录
        'content' => '', // 插件的详细介绍或使用方法
        'config_hook' => 'distributionProfile',
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197096.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563781881.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563781913.png',
    ); // 设置文件单独的钩子

    public $menu_info = array(
        [
            'module_name' => '全网分销',
            'parent_module_name' => '应用', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '全网分销，下线下单上线返佣', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributionProfile',
            'is_main' => 1
        ],

        [
            'module_name' => '分销概况',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributionProfile'
        ],

        [
            'module_name' => '分销商等级',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => 2, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销商可设置不同等级享受不同的佣金返利，权重越大等级越高。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributorLevelList'
        ],

        [
            'module_name' => '添加分销商等级',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '根据等级可给分销商设置不同的返佣比例，权重越大等级越高，可在商品（分销分红）或活动（基础设置）单独设置返佣比例，优先级为 商品>活动>分销商等级。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addDistributorLevel'
        ],

        [
            'module_name' => '修改分销商等级',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '根据等级可给分销商设置不同的返佣比例，权重越大等级越高，可在商品（分销分红）或活动（基础设置）单独设置返佣比例，优先级为 商品>活动>分销商等级。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateDistributorLevel'
        ],
        [
            'module_name' => '删除分销商等级',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '删除分销商等级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'deleteDistributorLevel'
        ],
        [
            'module_name' => '申请成为分销商',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '申请成为分销商', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributorApply',
            'is_member'=>1
        ],
        [
            'module_name' => '分销中心',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销中心', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributionIndex',
            'is_member'=>1
        ],
        [
            'module_name' => '分销商列表',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => 1, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销商可发展最多三级的下线，下线购买商品可以获得相应的佣金。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributorList'
        ],
        [
            'module_name' => '分销商详情',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributorInfo'
        ],
        [
            'module_name' => '分销商订单',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '查看分销商三级内分销的订单及对应获得的佣金情况。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributorOrderList'
        ],

        [
            'module_name' => '分销商订单详情',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributorOrderDetail'
        ],
        [
            'module_name' => '下级分销商列表',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'lowerDistributorList'
        ],
        [
            'module_name' => '分销商排行',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => 4, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '排行信息。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributionRanking'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => 4, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销商的相关基础设置，可设置分销模式、佣金结算方式、申请协议等。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributionSetting'
        ],

        [
            'module_name' => '基本设置',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '基本设置', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'basicSetting'
        ],
        [
            'module_name' => '结算设置',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' =>0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销设置', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'settlementSetting'
        ],
        [
            'module_name' => '申请协议',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '申请协议', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'applicationAgreement'
        ],
        [
            'module_name' => '分销商自动降级',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销商自动降级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'autoDownDistributorLevel',
            'is_member'=>1
        ],

        [
            'module_name' => '佣金提现',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '佣金提现', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'commissionWithdraw',
            'is_member'=>1
        ],
        [
            'module_name' => '佣金明细',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '佣金明细', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'commissionDetail',
            'is_member'=>1
        ],
        [
            'module_name' => '提现详情',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'withdrawDetail',
            'is_member'=>1
        ],
        [
            'module_name' => '分销订单',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销订单', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributionOrder',
            'is_member'=>1
        ],
        [
            'module_name' => '我的客户',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '我的客户', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'customerList',
            'is_member'=>1
        ],
        [
            'module_name' => '我的团队',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '我的团队', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'teamList',
            'is_member'=>1
        ],
        [
            'module_name' => '我的佣金',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '我的佣金', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'myCommission',
            'is_member'=>1
        ],
        [
            'module_name' => '佣金流水',
            'parent_module_name' => '财务', // 上级模块名称 用来确定上级目录
            'sort' => 7, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销商下线购买商品、分销商自己购买商品都可产生佣金记录，当账目有出入时可通过流水追溯原由。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'commissionRecordsList'
        ],
        [
            'module_name' => '佣金提现列表',
            'parent_module_name' => '财务', // 上级模块名称 用来确定上级目录
            'sort' => 6, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销商下线所产生的订单的都会产生佣金，订单完成后佣金即可提现，商家需要在该列表审核与打款，商家也可在分销应用“基础设置》结算设置”设置自动审核自动打款。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'commissionWithdrawList'
        ],
        [
            'module_name' => '订单支付成功',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '订单支付成功', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderPayCalculate',
            'is_member'=>1
        ],
        [
            'module_name' => '订单创建成功',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '订单创建成功', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderCommissionCalculate',
            'is_member'=>1
        ],
        [
            'module_name' => '订单完成佣金计算',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '订单完成佣金计算', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateOrderCommission',
            'is_member'=>1
        ],
        [
            'module_name' => '退款完成佣金计算',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '退款完成佣金计算', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateCommissionMoney',
            'is_member'=>1
        ],
        [
            'module_name' => '分销商申请状态',
            'parent_module_name' => '全网分销', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分销商申请状态', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'distributorStatus',
            'is_member'=>1
        ]
    ) // 钩子名称（需要该钩子调用的页面）
    ;
     public function __construct(){
        parent::__construct();
         $config= new DistributorService();
         $list = $config->getDistributionSite($this->website_id);
         if($this->merchant_status==1){
             $this->assign('merchant_status',$this->merchant_status);
         }
         if($this->merchant_expire==1){
             $this->assign('merchant_expire',$this->merchant_expire);
         }
         $this->assign("website", $list);
         $this->assign('getWithdrawCountUrl', __URL(addons_url_platform('distribution://distribution/getWithdrawCount')));
         $this->assign('updateDistributorInfoUrl', __URL(addons_url_platform('distribution://distribution/updateDistributorInfo')));
        $this->assign('distributorListUrl', __URL(addons_url_platform('distribution://distribution/distributorList')));
        $this->assign('distributionProfileUrl', __URL(addons_url_platform('distribution://distribution/distributionProfile')));
        $this->assign('distributionOrderProfileUrl', __URL(addons_url_platform('distribution://distribution/distributionOrderProfile')));
        $this->assign('distributionSettingUrl', __URL(addons_url_platform('distribution://distribution/distributionSetting')));
        $this->assign('addDistributorLevelUrl', __URL(addons_url_platform('distribution://distribution/addDistributorLevel')));
        $this->assign('updateDistributorLevelUrl', __URL(addons_url_platform('distribution://distribution/updateDistributorLevel')));
        $this->assign('distributorLevelListUrl', __URL(addons_url_platform('distribution://distribution/distributorLevelList')));
        $this->assign('website_id', $this->website_id);
        $this->assign('basicSettingUrl', __URL(addons_url_platform('distribution://distribution/basicSetting')));
        $this->assign('settlementSettingUrl', __URL(addons_url_platform('distribution://distribution/settlementSetting')));
        $this->assign('applicationAgreementUrl', __URL(addons_url_platform('distribution://distribution/applicationAgreement')));
        $this->assign('deleteDistributorLevelUrl', __URL(addons_url_platform('distribution://distribution/deleteDistributorLevel')));
        $this->assign('distributorInfoUrl', __URL(addons_url_platform('distribution://distribution/distributorInfo')));
        $this->assign('setStatusUrl', __URL(addons_url_platform('distribution://distribution/setStatus')));
        $this->assign('updateRefereeDistributorUrl', __URL(addons_url_platform('distribution://distribution/updateRefereeDistributor')));
        $this->assign('delDistributorUrl', __URL(addons_url_platform('distribution://distribution/delDistributor')));
        $this->assign('lowerDistributorListUrl', __URL(addons_url_platform('distribution://distribution/lowerDistributorList')));
        $this->assign('distributorOrderListUrl', __URL(addons_url_platform('distribution://distribution/distributorOrderList')));
        $this->assign('refereeDistributorListUrl', __URL(addons_url_platform('distribution://distribution/refereeDistributorList')));
        $this->assign('commissionWithdrawListUrl', __URL(addons_url_platform('distribution://distribution/commissionWithdrawList')));
        $this->assign('commissionWithdrawListDataExcelUrl', __URL(addons_url_platform('distribution://distribution/commissionWithdrawListDataExcel')));
        $this->assign('commissionRecordsListUrl', __URL(addons_url_platform('distribution://distribution/commissionRecordsList')));
        $this->assign('commissionRecordsDataExcelUrl', __URL(addons_url_platform('distribution://distribution/commissionRecordsDataExcel')));
        $this->assign('commissionWithdrawInfoUrl', __URL(addons_url_platform('distribution://distribution/commissionWithdrawInfo')));
        $this->assign('commissionWithdrawAuditUrl', __URL(addons_url_platform('distribution://distribution/commissionWithdrawAudit')));
        $this->assign('commissionInfoUrl', __URL(addons_url_platform('distribution://distribution/commissionInfo')));
         $this->assign('getProvinceUrl', __URL(addons_url_platform('distribution://distribution/getProvince')));
         $this->assign('getCityUrl', __URL(addons_url_platform('distribution://distribution/getCity')));
         $this->assign('getDistrictUrl', __URL(addons_url_platform('distribution://distribution/getDistrict')));
         $this->assign('checkDistributorUrl', __URL(addons_url_platform('distribution://distribution/checkDistributor')));
         $this->assign('updateLowerRefereeDistributorUrl', __URL(addons_url_platform('distribution://distribution/updateLowerRefereeDistributor')));
         $this->assign('messagePushListUrl', __URL(addons_url_platform('distribution://distribution/messagePushList')));
         $this->assign('editMessageUrl', __URL(addons_url_platform('distribution://distribution/editMessage')));
         $this->assign('addMessageUrl', __URL(addons_url_platform('distribution://distribution/addMessage')));

        $this->assign('distributorApplyUrl', __URL(addons_url('distribution://distribution/distributorApply')));
        $this->assign('distributorStatusUrl', __URL(addons_url('distribution://distribution/distributorStatus')));
        $this->assign('distributionIndexUrl', __URL(addons_url('distribution://distribution/distributionIndex')));
        $this->assign('distributionOrderUrl', __URL(addons_url('distribution://distribution/distributionOrder')));
        $this->assign('commissionDetailUrl', __URL(addons_url('distribution://distribution/commissionDetail')));
        $this->assign('customerListUrl', __URL(addons_url('distribution://distribution/customerList')));
        $this->assign('commissionWithdrawUrl', __URL(addons_url('distribution://distribution/commissionWithdraw')));
        $this->assign('myCommissionUrl', __URL(addons_url('distribution://distribution/myCommission')));
        $this->assign('showUserQrcodeUrl', __URL(addons_url('distribution://distribution/showUserQrcode')));
        $this->assign('teamListUrl', __URL(addons_url('distribution://distribution/teamList')));
        $this->assign('withdrawDetailUrl', __URL(addons_url('distribution://distribution/withdrawDetail')));
        $this->assign('commissionDetailsUrl', __URL(addons_url('distribution://distribution/commissionDetails')));

        $this->assign('distributionRankingUrl', __URL(addons_url_platform('distribution://distribution/ranking')));//排行信息
    }
    /**
     * 分销商排行
     */
    public function distributionRanking(){
        $this->fetch('template/platform/goodsAnalysis');
    }
    /**
     * 实现第三方钩子
     *
     * @param array $params            
     */


    /**
     * 分销商列表
     */
    public function distributorList()
    {
        $level = new DistributorService();
        $distributor_level = $level->getDistributorLevel();
        $this->assign('distributor_level',$distributor_level);
        $this->fetch('template/platform/distributorList');
    }

    /**
     * 下级分销商列表
     */
    public function lowerDistributorList()
    {
        $level = new DistributorService();
        $distributor_level = $level->getDistributorLevel();
        $this->assign('distributor_level',$distributor_level);
        $this->assign('distributor_id',$_GET['distributor_id']);
        $types = request()->get('types', 1);
        $this->assign('types',$_GET['types']);
        $this->fetch('template/platform/lowerDistributorList');
    }

    /**
     * 分销商详情页面
     */
    public function distributorInfo(){
        $member = new DistributorService();
        $uid = $_GET['distributor_id'];
        $res= $member->getDistributorInfo($uid);
        if($res['area_type']){
            $area_type = explode(',',$res['area_type']);
            $res['area_type'] = $area_type[0];
        }
        $distributor_level = $member->getDistributorLevel();
        $areaStatus = getAddons('areabonus',$this->website_id);
        $globalStatus = getAddons('globalbonus',$this->website_id);
        $teamStatus = getAddons('teambonus',$this->website_id);
        if($areaStatus || $globalStatus || $teamStatus){
            $agent_info = $member->getAgentInfo();
            $this->assign('agent_info',$agent_info);
        }
        $this->assign('distributor_level',$distributor_level);
        $this->assign('info',$res);
        $this->fetch('template/platform/distributorInfo');
    }

    /**
     * 分销订单
     */
    public function distributorOrderList(){
        $member_id = request()->get('distributor_id', '');
        $member = new userModel();
        $member_info = $member->getInfo(['uid' => $member_id],'user_name,nick_name');
        if(empty($member_info['user_name'])){
            $member_name = $member_info['nick_name'];
        }else{
            $member_name = $member_info['user_name'];
        }
        $this->assign("member_id", $member_id);
        $this->assign("member_name", $member_name);
        $this->fetch('template/platform/distributorOrderList');
    }

    /**
     * 分销概况
     */
    public function distributionProfile(){
        $month_begin = date('Y-m-01', strtotime(date("Y-m-d")));
        $month_end = date('Y-m-d', strtotime("$month_begin +1 week -1 day"));
        $this->assign("start_date", $month_begin);
        $this->assign("end_date", $month_end);
        $this->fetch('template/platform/distributionProfile');
    }

    /**
     * 分销商等级
     */
    public function distributorLevelList(){

        $this->fetch('template/platform/distributorLevelList');
    }

    /**
     * 分销设置
     */
    public function distributionSetting()
    {
        $this->basicSetting();
    }
    /**
     * 基本设置
     */
    public function basicSetting()
    {
        $config= new DistributorService();
        $list = $config->getDistributionSite($this->website_id);
        $this->assign("website", $list);
        $this->fetch('template/platform/basicSetting');
    }
    /**
     * 结算设置
     */
    public function settlementSetting()
    {
        $config= new DistributorService();
        $list = $config->getSettlementSite($this->website_id);
        $this->assign("website", $list);
        $this->fetch('template/platform/settlementSetting');
    }
    /**
     * 申请协议
     */
    public function applicationAgreement()
    {
        $config= new DistributorService();
        $list = $config->getAgreementSite($this->website_id);
        $pc_set = getAddons('pcport',$this->website_id);
        $this->assign("pc_set", $pc_set);
        $type=isset($_GET['type'])?$_GET['type']:'0';
        $this->assign("type", $type);
        $this->assign("website", $list);
        $this->fetch('template/platform/applicationAgreement');
    }

    /**
     * 添加分销商等级
     */
    public function addDistributorLevel(){
        $distributor = new DistributorService();
        $level_weight=$distributor->getLevelWeight();
        $this->assign("level_weight", implode(',',$level_weight));
        $level_info = $distributor->getDistributorLevel();
        $this->assign("level_info", $level_info);
        $this->fetch('template/platform/addDistributorLevel');
    }
    /**
     * 修改分销商等级
     */
    public function updateDistributorLevel(){
        $distributor = new DistributorService();
        $id=isset($_GET['id'])?$_GET['id']:'';
        $distributor_level_info=$distributor->getDistributorLevelInfo($id);
        $this->assign('list',$distributor_level_info);
        $distributor = new DistributorService();
        $level_weight=$distributor->getLevelWeight();
        $level_info = $distributor->getDistributorLevel();
        $this->assign("level_info", $level_info);
        $this->assign("level_weight", implode(',',$level_weight));
        $this->fetch('template/platform/updateDistributorLevel');
    }

    /**
     * 分销商订单详情
     *
     */
    public function distributorOrderDetail()
    {
        $distributor_id = isset($_GET['distributor_id']) ? $_GET['distributor_id'] : "";
        $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : "";
        $distributor = new DistributorService();
        $order_detail = $distributor->getOrderDetail($order_id,$distributor_id);
        $this->assign("order", $order_detail);
        $this->fetch("template/platform/distributorOrderDetail");
    }



    /**
     * 佣金提现列表
     */
    public function commissionWithdrawList(){
        $this->fetch('template/platform/commissionWithdrawList');

    }
    public function commissionWithdrawListDataExcel(){
        $this->fetch('template/platform/commissionWithdrawListDataExcel');
    }

    /**
     * 佣金流水列表
     */
    public function commissionRecordsList(){
        $this->fetch('template/platform/commissionRecordsList');
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
     * 订单创建成功后佣金计算
     */
    public function orderCommissionCalculate($params)
    {
        $order= new VslOrderModel();
        $params['uid'] = $order->getInfo(['order_id'=>$params['order_id']],'buyer_id')['buyer_id'];
        $distributor = new VslMemberModel();
        $member = $distributor->getInfo(['uid'=>$params['buyer_id']],'*');
        if($member['isdistributor']==2 || $member['referee_id']){
            $commissionCalculate = new DistributorService();
            $commissionCalculate->OrderDistributorCommission($params);
        }
    }
    /**
     * 订单支付成功后佣金计算
     */
    public function orderPayCalculate($params)
    {
        $config= new DistributorService();
        $order= new VslOrderModel();
        $params['uid'] = $order->getInfo(['order_id'=>$params['order_id']],'buyer_id')['buyer_id'];
        $distributor = new VslMemberModel();
        $member = $distributor->getInfo(['uid'=>$params['uid']],'*');
        if($member['isdistributor']==2 || $member['referee_id']){
            $params['status'] = 3;
            $this->updateOrderCommission($params);
        }
        $config->becomeLower($params['uid']); 
    }
    /**
     * 订单退款成功后需要重新计算订单的佣金
     */
    public function updateCommissionMoney($params)
    {
        $order = new VslOrderModel();
        $params['uid'] = $order->getInfo(['order_id' => $params['order_id']], 'buyer_id')['buyer_id'];
        $distributor = new VslMemberModel();
        $member = $distributor->getInfo(['uid' => $params['uid']], '*');
        if ($member['isdistributor'] == 2 || $member['referee_id']) {
            // 重新计算分销佣金
            $params['status'] = 2;
            
            $this->updateOrderCommission($params);
        }
    }

    /**
     * 佣金结算(订单完成)
     */
    public function updateOrderCommission($params)
    {
        $order_commission = new VslOrderDistributorCommissionModel();
        $distributor_order_ids =  $order_commission->Query(['website_id'=>$params['website_id']],'order_id');
        $orderids = array_unique($distributor_order_ids);
        $order = new VslOrderModel();
        $params['uid'] = $order->getInfo(['order_id' => $params['order_id']], 'buyer_id')['buyer_id'];
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid' => $params['uid']], '*');
        $distributor = new DistributorService();
        $order_model = new VslOrderModel();
        $order_info = $order_model->getInfo(['order_id' => $params['order_id']], '*');
        $order_status = $order_info["order_status"];//当前订单状态
        
        if(in_array($params['order_id'],$orderids)) {
            if ($member_info['isdistributor'] == 2 || $member_info['referee_id']) {//判断当前用户是否是分销商或者有推荐人
                $config = new DistributorService();
                $list = $config->getDistributionSite($params['website_id']);
                    $data['commissionA'] = 0;
                    $data['commissionB'] = 0;
                    $data['commissionC'] = 0;
                    $data['commission'] = 0;
                    $order_commissionA = $order_commission->Query(['order_id' => $params['order_id'],'return_status' => 0], 'commissionA');
                    $order_pointA = $order_commission->Query(['order_id' => $params['order_id'],'return_status' => 0], 'pointA');
                    $order_commissionA_id = $order_commission->getInfo(['order_id' => $params['order_id']], 'commissionA_id');
                    $order_commissionB = $order_commission->Query(['order_id' => $params['order_id'],'return_status' => 0], 'commissionB');
                    $order_pointB = $order_commission->Query(['order_id' => $params['order_id'],'return_status' => 0], 'pointB');
                    $order_commissionB_id = $order_commission->getInfo(['order_id' => $params['order_id']], 'commissionB_id');
                    $order_commissionC = $order_commission->Query(['order_id' => $params['order_id'],'return_status' => 0], 'commissionC');
                    $order_pointC = $order_commission->Query(['order_id' => $params['order_id'],'return_status' => 0], 'pointC');
                    $order_commissionC_id = $order_commission->getInfo(['order_id' => $params['order_id']], 'commissionC_id');
                    if($params['status'] == 3 || $params['status'] == 2){
                        $order_commissionA = $order_commission->Query(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'commissionA');
                        $order_commissionA_id = $order_commission->getInfo(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'commissionA_id');
                        $order_commissionB = $order_commission->Query(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'commissionB');
                        $order_commissionB_id = $order_commission->getInfo(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'commissionB_id');
                        $order_commissionC = $order_commission->Query(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'commissionC');
                        $order_commissionC_id = $order_commission->getInfo(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'commissionC_id');
                    }
                    //获取佣金状态
                    if($params['status'] == 3){
                        $check_pay_status = $order_commission->getInfo(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']], 'pay_status');
                        $check_pay_status = $check_pay_status['pay_status'];
                        if($check_pay_status == 1){
                            return;
                        }
                    }
                    if($order_status == 4){
                        $check_cal_status = $order_commission->getInfo(['buyer_id'=>$params['uid'],'order_id'=>$params['order_id']], 'cal_status');
                        $check_cal_status = $check_cal_status['cal_status'];
                        if($check_cal_status == 1){
                            return;
                        }
                    }
                    
                    if ($order_commissionA_id['commissionA_id'] && $list['distribution_pattern'] >= 1) {
                        $data['uid'] = $order_commissionA_id['commissionA_id'];//一级佣金对应的分销商
                        // 订单交易完成
                        if ($order_status == 4) {
                            //订单交易完成状态
                            $data['status'] = 1;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['commission'] = array_sum($order_commissionA);//一级佣金
                            $data['point'] = array_sum($order_pointA);//一级积分
                            // 发放订单的一级分销佣金
                            $distributor->addCommissionDistribution($data);
                            // 更新当前用户的分销商等级
                            $distributor->updateDistributorLevelInfo($data['uid']);
                        }
                        // 订单退款成功
                        if ($params['status'] == 2) {
                            // 得到订单用户id
                            $data['status'] = 2;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['commission'] = array_sum($order_commissionA);//一级佣金
                            // 订单的一级分销佣金
                            $distributor->addCommissionDistribution($data);
                        }
                        // 订单支付成功
                        if ($params['status'] == 3) {
                            // 得到订单用户id
                            $data['status'] = 3;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['commission'] = array_sum($order_commissionA);//一级佣金
                            // 订单的一级分销佣金
                            $distributor->addCommissionDistribution($data);
                        }
                    }
                    if ($order_commissionB_id['commissionB_id'] && $list['distribution_pattern'] >= 2) {
                        $data['uid'] = $order_commissionB_id['commissionB_id'];//二级佣金对用的分销商
                        // 订单交易完成
                        if ($order_status == 4) {
                            // 得到订单用户id
                            $data['status'] = 1;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['commission'] = array_sum($order_commissionB);//二级佣金
                            $data['point'] = array_sum($order_pointB);//二级积分
                            // 发放订单的二级分销佣金
                            $distributor->addCommissionDistribution($data);
                            // 更新当前用户的分销商等级
                            $distributor->updateDistributorLevelInfo($data['uid']);
                        }
                        // 订单退款成功
                        if ($params['status'] == 2) {
                            // 得到订单用户id
                            $data['status'] = 2;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['commission'] = array_sum($order_commissionB);//二级佣金
                            // 订单的二级分销佣金
                            $distributor->addCommissionDistribution($data);
                        }
                        // 订单支付成功
                        if ($params['status'] == 3) {
                            // 得到订单用户id
                            $data['status'] = 3;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['commission'] = array_sum($order_commissionB);//二级佣金
                            // 订单的二级分销佣金
                            $distributor->addCommissionDistribution($data);
                        }
                    }
                    if ($order_commissionC_id['commissionC_id'] && $list['distribution_pattern'] >= 3) {
                        $data['uid'] = $order_commissionC_id['commissionC_id'];
                        // 订单交易完成
                        if ($order_status == 4) {
                            // 得到订单用户id
                            $data['status'] = 1;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['commission'] = array_sum($order_commissionC);//三级佣金
                            $data['point'] = array_sum($order_pointC);//三级积分
                            // 发放订单的三级分销佣金
                            $distributor->addCommissionDistribution($data);
                            // 更新当前用户的分销商等级
                            $distributor->updateDistributorLevelInfo($data['uid']);
                        }
                        // 订单退款成功
                        if ($params['status'] == 2) {
                            // 得到订单用户id
                            $data['status'] = 2;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['commission'] = array_sum($order_commissionC);//三级佣金
                            // 订单的三级分销佣金
                            $distributor->addCommissionDistribution($data);
                        }
                        // 订单支付成功
                        if ($params['status'] == 3) {
                            // 得到订单用户id
                            $data['status'] = 3;
                            $data['order_id'] = $params['order_id'];
                            $data['website_id'] = $order_info["website_id"];
                            $data['commission'] = array_sum($order_commissionC);//三级佣金
                            // 订单的三级分销佣金
                            $distributor->addCommissionDistribution($data);
                        }
                    }
                    //变更佣金状态
                    if($params['status'] == 3){
                        $order_commission = new VslOrderDistributorCommissionModel();
                        $order_commission->save(['pay_status' => 1], ['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']]);
                    }
                    if($order_status == 4){
                        $order_commission = new VslOrderDistributorCommissionModel();
                        $order_commission->save(['cal_status' => 1], ['buyer_id'=>$params['uid'],'order_id'=>$params['order_id']]);
                    }
                    //该记录发生退款
                    if($params['order_goods_id'] && $params['status'] == 2){
                        $order_commission = new VslOrderDistributorCommissionModel();
                        $re_data = array(
                            'return_status' => 1,
                        );
                        $order_commission->save($re_data, ['buyer_id'=>$params['uid'],'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']]);
                    }
            }
        }
        if($order_status == 4 && $member_info['isdistributor'] == 2) {
            $distributor->updateDistributorLevelInfo($params['uid']);
        }
        // 当前用户不是分销商，满足成为分销商条件
        if ($order_status == 4 && $member_info['isdistributor'] != 2) {
            $distributor->becomeDistributor($order_info['buyer_id'], $params['order_id']);
        }
    }

    /**
     * 分销商自动降级
     */
    public function autoDownDistributorLevel($params){
        $distributorLevel = new DistributorService();
        $distributorLevel->autoDownDistributorLevel($params['website_id']);
    }
    /*-------------------------------------------------------------------前端页面开始-----------------------------------------------------------------------*/
    /**
     * 前台申请分销商
     */
    public function distributorApply($params){

        $uid = $this->uid;
        $user = new UserModel();
        $user_info = $user->getInfo(['uid'=>$uid],'real_name,user_tel');
        $user_info['code'] = 0;
        return json($user_info);
    }


    /**
     * 前台分销中心
     */
    public function distributionIndex($params){
        $params['uid'] = $this->uid;
        $params['website_id'] = $this->website_id;
        $this->assign('params',$params);
        $member = new DistributorService();
        $dis_set = $member->getAgreementSite($params['website_id']);
        if($dis_set){
            $this->assign("distribution_name", $dis_set['distribution_name']);
            $this->assign("total_commission", $dis_set['total_commission']);
            $this->assign("frozen_commission", $dis_set['frozen_commission']);
            $this->assign("withdrawable_commission", $dis_set['withdrawable_commission']);
        }
        $this->fetch('template/shop/distributionIndex');
    }








    /**
     * 佣金详情
     */
    public function myCommission($params)
    {
        $params['uid'] = $this->uid;
        $commission = new DistributorService();
        $uid = $params['uid'];
        $my_commission = $commission->getCommissionWithdrawConfig($uid);
        $data = array();
        //可提现佣金
        if($my_commission['commission']){
            $data['data']['commission'] = $my_commission['commission'];
        }else{
            $data['data']['commission'] = '0.00';
        }

        //累积佣金
        if($my_commission['commission']) {
            $data['data']['total_money'] = $my_commission['commission'] + $my_commission['withdrawals'];
        }else{
            $data['data']['total_money'] = '0.00';
        }

        //已提现佣金
        if($my_commission['withdrawals']){
            $data['data']['withdrawals'] = $my_commission['withdrawals'];
        }else{
            $data['data']['withdrawals'] = '0.00';
        }

        //体现中
        if($my_commission['apply_withdraw']){
            $data['data']['apply_withdraw'] = $my_commission['apply_withdraw'];
        }else{
            $data['data']['apply_withdraw'] = '0.00';
        }

        //冻结中
        if($my_commission['freezing_commission']){
            $data['data']['freezing_commission'] = $my_commission['freezing_commission'];
        }else{
            $data['data']['freezing_commission'] = '0.00';
        }

        $data['code'] = 0;
        return json($data);
    }
    /**
     * 佣金提现
     */
    public function commissionWithdraw($params)
    {
        $params['uid'] = $this->uid;
        $distribution = new DistributorService();
        $info= $distribution->getDistributionSite($params['website_id']);
        $this->assign('info', $info);
        $member_info = $distribution->getDistributorInfo($params['uid']);
        $this->assign('member_info',$member_info);
        $commission = new DistributorService();
        $uid = $params['uid'];
        $commission_config = $commission->getCommissionWithdrawConfig($uid);
        $commission_config['withdrawals_type'] = explode(',',$commission_config['withdrawals_type']);
        $this->assign('account_list', $commission_config['account_list']);
        $this->assign('commission_config', $commission_config);
        $this->assign("params", $params);
        if($params['port']=='shop'){
            $this->fetch("template/shop/commissionWithdraw");
        }
    }


    //获取模板信息
    public function getCustoTemplate($range,$shop_id='0'){
        $config = new Config();
        //自定义模板查询
        $teplate_info = $config->getCustomTemplateInfo(["shop_id"=>$shop_id,"is_enable"=>1,"range"=>$range]);
        if(!empty($teplate_info)){
            $goods = new GoodsService();
            $custom_template_info = json_decode($teplate_info["template_data"], true);
            foreach($custom_template_info as $k=>$v){
                $custom_template_info[$k]["style_data"] = json_decode($v["control_data"], true);
            }
            //给数组排序
            $sort = array(
                'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'sort',       //排序字段
            );
            $arrSort = array();
            foreach($custom_template_info as $uniqid => $row){
                foreach($row as $key=>$value){
                    $arrSort[$key][$uniqid] = $value;
                }
            }
            if($sort['direction']){
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $custom_template_info);
            }
            foreach($custom_template_info as $k=>$v){
                if($v["control_name"] == "GoodsList"){
                    //商品列表
                    $list = json_decode($v["style_data"]['goods_list'],true);
                    if($list["goods_source"] > 0){
                        $goods_list = $goods->getGoodsList(1, $list["goods_limit_count"] , ["ng.category_id"=>$list["goods_source"]], "ng.create_time desc");

                        $goods_query = array();
                        if(!empty($goods_list)){
                            foreach($goods_list['data'] as $good=>$vals){
                                $goods_list['data'][$good]['url'] = __URL(APP_MAIN.'/goods/goodsdetail','id='.$vals['goods_id']);
                            }
                            $goods_query = $goods_list["data"];
                        }
                        $custom_template_info[$k]["goods_list"] = $goods_query;
                        $custom_template_info[$k]["style_data"] = $list;
                    }
                }elseif($v["control_name"] == "ImgAd"){
                    //图片广告
                    if(trim($v["style_data"]["img_ad"]) != ""){
                        $custom_template_info[$k]["style_data"]["img_ad"] = json_decode($v["style_data"]["img_ad"], true);
                    }else{
                        $custom_template_info[$k]["style_data"]["img_ad"] = array();
                    }
                }elseif($v["control_name"] == "GoodsSearch"){
                    //图片广告
                    if(trim($v["style_data"]["goods_search"]) != ""){
                        $custom_template_info[$k]["style_data"] = json_decode($v["style_data"]["goods_search"], true);
                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                }elseif($v["control_name"] == "Footer"){
                    //底部菜单
                    if(trim($v["style_data"]["footer"]) != ""){
                        $custom_template_info[$k]["style_data"] = json_decode($v["style_data"]["footer"], true);
                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                }elseif($v["control_name"] == "NavHybrid"){
                    //图片导航
                    if(trim($v["style_data"]["nav_hybrid"]) != ""){
                        $custom_template_info[$k]["style_data"]["nav_hybrid"] = json_decode($v["style_data"]["nav_hybrid"], true);

                    }else{
                        $custom_template_info[$k]["style_data"]["nav_hybrid"] = array();
                    }
                }elseif($v["control_name"] == "Notice"){
                    //公告
                    if(trim($v["style_data"]["notice"]) != ""){
                        $custom_template_info[$k]["style_data"] = json_decode($v["style_data"]["notice"], true);

                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                    if($custom_template_info[$k]["style_data"]['text_align']==1){
                        $custom_template_info[$k]['style_data']['text_align']='left';
                    }elseif($custom_template_info[$k]["style_data"]['text_align']==2){
                        $custom_template_info[$k]['style_data']['text_align']='center';
                    }elseif($custom_template_info[$k]["style_data"]['text_align']==3){
                        $custom_template_info[$k]['style_data']['text_align']='right';
                    }else{
                        $custom_template_info[$k]['style_data']['text_align']='left';
                    }
                }elseif($v["control_name"] == "GoodsClassify"){
                    //商品分类
                    if(trim($v["style_data"]["goods_classify"]) != ""){
                        $category = new GoodsCategory();
                        $category_array = json_decode($v["style_data"]["goods_classify"], true);
                        foreach($category_array as $t=>$m){
                            $category_info = $category->getGoodsCategoryDetail($m["id"]);
                            $category_array[$t]["name"] = $category_info["short_name"];
                            $goods_list = $goods->getGoodsList(1, $m["show_count"] , ["ng.category_id"=>$m["id"]], "ng.create_time desc");
                            $category_array[$t]["goods_list"] = $goods_list["data"];
                        }
                        $custom_template_info[$k]["style_data"]["goods_classify"] = $category_array;

                    }else{
                        $custom_template_info[$k]["style_data"]["goods_classify"] = array();
                    }
                }elseif($v["control_name"] == "Footer"){
                    //首页
                    if(trim($v["style_data"]["footer"]) != ""){
                        $custom_template_info[$k]["style_data"]["footer"]  = json_decode($v["style_data"]["footer"], true);
                    }else{
                        $custom_template_info[$k]["style_data"]["footer"] = array();
                    }
                }elseif($v["control_name"] == "Title"){
                    if(trim($v["style_data"]["title"]) != ""){
                        $custom_template_info[$k]["style_data"]  = json_decode($v["style_data"]["title"], true);
                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                    if($custom_template_info[$k]["style_data"]['text_align']==1){
                        $custom_template_info[$k]['style_data']['text_align']='left';
                    }elseif($custom_template_info[$k]["style_data"]['text_align']==2){
                        $custom_template_info[$k]['style_data']['text_align']='center';
                    }elseif($custom_template_info[$k]["style_data"]['text_align']==3){
                        $custom_template_info[$k]['style_data']['text_align']='right';
                    }else{
                        $custom_template_info[$k]['style_data']['text_align']='left';
                    }

                    if($custom_template_info[$k]["style_data"]['whether_bold']==1){
                        $custom_template_info[$k]['style_data']['whether_bold']='bold';
                    }else{
                        $custom_template_info[$k]['style_data']['whether_bold']='normal';
                    }
                }elseif($v["control_name"] == "ShowCase"){
                    //橱窗
                    if(trim($v["style_data"]["show_case"]) != ""){
                        $custom_template_info[$k]["style_data"]  = json_decode($v["style_data"]["show_case"], true);
                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                }elseif($v["control_name"] == "Video"){
                    //视频
                    if(trim($v["style_data"]["video"]) != ""){
                        $custom_template_info[$k]["style_data"]  = json_decode($v["style_data"]["video"], true);
                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                }elseif($v["control_name"] == "Coupons"){
                    //视频
                    if(trim($v["style_data"]["coupons"]) != ""){
                        $custom_template_info[$k]["style_data"]  = json_decode($v["style_data"]["coupons"], true);
                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                }elseif($v["control_name"] == "TextNavigation"){
                    //视频
                    if(trim($v["style_data"]["text_navigation"]) != ""){
                        $custom_template_info[$k]["style_data"]  = json_decode($v["style_data"]["text_navigation"], true);
                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                    if($custom_template_info[$k]["style_data"]['text_align']==1){
                        $custom_template_info[$k]['style_data']['text_align']='left';
                    }elseif($custom_template_info[$k]["style_data"]['text_align']==2){
                        $custom_template_info[$k]['style_data']['text_align']='center';
                    }elseif($custom_template_info[$k]["style_data"]['text_align']==3){
                        $custom_template_info[$k]['style_data']['text_align']='right';
                    }else{
                        $custom_template_info[$k]['style_data']['text_align']='left';
                    }
                }elseif($v["control_name"] == "AuxiliaryLine"){
                    //辅助线
                    if(trim($v["style_data"]["auxiliary_line"]) != ""){
                        $custom_template_info[$k]["style_data"]  = json_decode($v["style_data"]["auxiliary_line"], true);
                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                }elseif($v["control_name"] == "AuxiliaryBlank"){
                    //辅助空白
                    if(trim($v["style_data"]["auxiliary_blank"]) != ""){
                        $custom_template_info[$k]["style_data"]  = json_decode($v["style_data"]["auxiliary_blank"], true);
                    }else{
                        $custom_template_info[$k]["style_data"] = array();
                    }
                }
            }
        }else{
            $custom_template_info = array();
        }

        return $custom_template_info;
    }

}