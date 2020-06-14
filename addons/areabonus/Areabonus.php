<?php
namespace addons\areabonus;
use addons\Addons as Addo;
use addons\areabonus\service\AreaBonus as  areaBonusService;
use data\model\UserModel;
use addons\bonus\model\VslOrderBonusModel;
use addons\distribution\service\Distributor as  DistributorService;
use data\service\Config;

class areaBonus extends Addo
{
    public $info = array(
        'name' => 'areabonus', // 插件名称标识
        'title' => '区域分红', // 插件中文名
        'description' => '区域代理分红所代理区域所有订单', // 插件概述
        'status' => 1, // 状态 1启用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', // 版本号
        'has_addonslist' => 1, // 是否有下级插件 例如：第三方登录插件下有 qq登录，微信登录
        'content' => '', // 插件的详细介绍或使用方法
        'config_hook' => 'areaBonusProfile',
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197491.jpg',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782144.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782265.png',
    ); // 设置文件单独的钩子

    public $menu_info = array(
        [
            'module_name' => '区域分红',
            'parent_module_name' => '应用', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理分红所代理区域所有订单', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaBonusProfile',
            'is_main' => 1
        ],

        [
            'module_name' => '分红概况',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分红概况', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaBonusProfile'
        ],

        [
            'module_name' => '区代等级',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => 2, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理可设置不同等级享受不同分红返利，权重越大等级越高。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaAgentLevelList'
        ],

        [
            'module_name' => '添加代理等级',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理可设置不同等级享受不同的分红返利，权重越大等级越高，可在商品（分销分红）或活动（基础设置）单独设置返佣比例，优先级为 商品>活动>区域代理等级。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addAreaAgentLevel'
        ],

        [
            'module_name' => '修改代理等级',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理可设置不同等级享受不同的分红返利，权重越大等级越高，可在商品（分销分红）或活动（基础设置）单独设置返佣比例，优先级为 商品>活动>区域代理等级。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateAreaAgentLevel'
        ],
        [
            'module_name' => '删除代理等级',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '删除代理等级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'deleteAreaAgentLevel'
        ],
        [
            'module_name' => '修改代理状态',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '修改代理状态', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'setAreaAgentStatus'
        ],
        [
            'module_name' => '区代列表',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => 1, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理可获得所代理区域的所有订单一定比例的分红奖励。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaAgentList'
        ],
        [
            'module_name' => '代理详情',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaAgentInfo'
        ],

        [
            'module_name' => '基础设置',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => 5, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域分红的基础相关设置，可设置成为区域代理条件、内购、跳降级、结算、申请协议等设置。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaBonusSetting'
        ],
        [
            'module_name' => '分红结算单',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => 3, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理待分红的结算单，分红默认为手动发放，可在“基础设置》结算设置”设置为按周期自动发放。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaBonusBalance'
        ],
        [
            'module_name' => '分红订单',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分红订单', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaAgentOrderList'
        ],
        [
            'module_name' => '分红订单详情',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaAgentOrderDetail'
        ],
        [
            'module_name' => '分红明细',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => 4, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '可查看区域代理分红打款的数据信息。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaBonusDetail'
        ],
        [
            'module_name' => '分红明细详情',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaBonusInfo'
        ],
        [
            'module_name' => '分红基本设置',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分红基本设置', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaBasicSetting'
        ],
        [
            'module_name' => '分红结算设置',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' =>0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分红结算设置', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaSettlementSetting'
        ],
        [
            'module_name' => '代理申请协议',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '代理申请协议', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'areaApplicationAgreement'
        ],
        [
            'module_name' => '区域代理自动降级',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理自动降级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'autoDownAreaAgentLevel',
            'is_member'=>1
        ],
        [
            'module_name' => '区域代理订单支付成功',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理订单支付成功', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderAreaPayCalculate',
            'is_member'=>1
        ],
        [
            'module_name' => '区域代理订单创建成功',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理订单创建成功', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderAreaBonusCalculate',
            'is_member'=>1
        ],
        [
            'module_name' => '区域代理订单完成分红计算',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理订单完成分红计算', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateOrderAreaBonus',
            'is_member'=>1
        ],
        [
            'module_name' => '区域代理退款完成分红计算',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域代理退款完成分红计算', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateAreaBonusMoney',
            'is_member'=>1
        ],
        [
            'module_name' => '区域分红自动发放',
            'parent_module_name' => '区域分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '区域分红自动发放', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'autoGrantAreaBonus',
            'is_member'=>1
        ]
    ); // 钩子名称（需要该钩子调用的页面）

     public function __construct(){
        parent::__construct();
         $config= new areaBonusService();
         $list = $config->getSettlementSite($this->website_id);
         $this->assign("website", $list);
         if($this->merchant_expire==1){
             $this->assign('merchant_expire',$this->merchant_expire);
         }
        $this->assign('areaAgentListUrl', __URL(addons_url_platform('areabonus://areabonus/areaAgentList')));
        $this->assign('areaBonusProfileUrl', __URL(addons_url_platform('areabonus://areabonus/areaBonusProfile')));
        $this->assign('areaBonusOrderProfileUrl', __URL(addons_url_platform('areabonus://areabonus/areaBonusOrderProfile')));
        $this->assign('areaBonusSettingUrl', __URL(addons_url_platform('areabonus://areaBonus/areaBonusSetting')));
        $this->assign('addAreaAgentLevelUrl', __URL(addons_url_platform('areabonus://areabonus/addAreaAgentLevel')));
        $this->assign('updateAreaAgentLevelUrl', __URL(addons_url_platform('areabonus://areabonus/updateAreaAgentLevel')));
         $this->assign('deleteAreaAgentLevelUrl', __URL(addons_url_platform('areabonus://areabonus/deleteAreaAgentLevel')));
        $this->assign('areaAgentLevelListUrl', __URL(addons_url_platform('areabonus://areabonus/areaAgentLevelList')));
         $this->assign('areaMessagePushListUrl', __URL(addons_url_platform('areabonus://areabonus/areaMessagePushList')));
         $this->assign('areaEditMessageUrl', __URL(addons_url_platform('areabonus://areabonus/areaEditMessage')));
         $this->assign('addAreaMessageUrl', __URL(addons_url_platform('areabonus://areabonus/addAreaMessage')));
        $this->assign('website_id', $this->website_id);
        $this->assign('pageshow','10');
        $this->assign('areaBasicSettingUrl', __URL(addons_url_platform('areabonus://areabonus/areaBasicSetting')));
        $this->assign('areaSettlementSettingUrl', __URL(addons_url_platform('areabonus://areabonus/areaSettlementSetting')));
        $this->assign('areaApplicationAgreementUrl', __URL(addons_url_platform('areabonus://areabonus/areaApplicationAgreement')));
        $this->assign('deleteAgentLevelUrl', __URL(addons_url_platform('areabonus://areabonus/deleteAreaAgentLevel')));
        $this->assign('areaAgentInfoUrl', __URL(addons_url_platform('areabonus://areabonus/areaAgentInfo')));
        $this->assign('setAreaAgentStatusUrl', __URL(addons_url_platform('areabonus://areabonus/setAreaAgentStatus')));
        $this->assign('delAreaAgentUrl', __URL(addons_url_platform('areabonus://areabonus/delAreaAgent')));
        $this->assign('updateAreaAgentInfoUrl', __URL(addons_url_platform('areabonus://areabonus/updateAreaAgentInfo')));
        $this->assign('areaBonusBalanceUrl', __URL(addons_url_platform('areabonus://areabonus/areaBonusBalance')));
        $this->assign('areaBonusListUrl', __URL(addons_url_platform('areabonus://areabonus/areaBonusList')));
        $this->assign('areaBonusGrantUrl', __URL(addons_url_platform('areabonus://areabonus/areaBonusGrant')));
        $this->assign('areaBonusInfoUrl', __URL(addons_url_platform('areabonus://areabonus/areaBonusInfo')));
        $this->assign('areaAgentOrderListUrl', __URL(addons_url_platform('areabonus://areabonus/areaAgentOrderList')));
        $this->assign('areaAgentOrderDetailUrl', __URL(addons_url_platform('areabonus://areabonus/areaAgentOrderDetail')));
        $this->assign('getProvinceUrl', __URL(addons_url_platform('areabonus://areabonus/getProvince')));
        $this->assign('getCityUrl', __URL(addons_url_platform('areabonus://areabonus/getCity')));
        $this->assign('getDistrictUrl', __URL(addons_url_platform('areabonus://areabonus/getDistrict')));
    }

    /**
     * 代理列表
     */
    public function areaAgentList()
    {
        $level = new areaBonusService();
        $agent_level = $level->getagentLevel();
        $this->assign('agent_level',$agent_level);
        $this->fetch('template/platform/areaAgentList');
    }
    /**
     * 区域分红结算单
     */
    public function areaBonusBalance()
    {
        $this->fetch('template/platform/areaBonusBalance');
    }
    /**
     * 区域分红明细
     */
    public function areaBonusDetail()
    {
        $this->fetch('template/platform/areaBonusDetail');
    }
    /**
     * 区域分红明细
     */
    public function areaBonusInfo()
    {
        $member = new areaBonusService();
        $agent_level = $member->getagentLevel();
        $this->assign('agent_level',$agent_level);
        $this->assign('sn',$_GET['sn']);
        $this->fetch('template/platform/areaBonusInfo');
    }
    /**
     * 代理详情页面
     */
    public function areaAgentInfo(){
        $member = new areaBonusService();
        $uid = $_GET['agent_id'];
        $res= $member->getAgentInfo($uid);
        $agent_level = $member->getagentLevel();
        $this->assign('agent_level',$agent_level);
        $this->assign('info',$res);
        $this->fetch('template/platform/areaAgentInfo');
    }
    /**
     * 代理分红订单
     */
    public function areaAgentOrderList(){
        $uid = $_GET['agent_id'];
        $member = new UserModel();
        $user_info  = $member->getInfo(['uid' => $uid],'user_name,nick_name');
//        if($user_info['user_name']){
//            $member_name = $user_info['user_name'];
//        }else{
        $member_name = $user_info['nick_name'];
//        }
        $this->assign("member_id", $uid);
        $this->assign("user_name", $member_name);
        $this->fetch('template/platform/areaAgentOrderList');
    }
    /**
     * 代理分红订单详情
     */
    public function areaAgentOrderDetail(){
        $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : "";
        $uid = isset($_GET['uid']) ? $_GET['uid'] : "";
        $distributor = new areaBonusService();
        $order_detail = $distributor->getOrderDetail($order_id,$uid);
        $this->assign("order", $order_detail);
        $this->fetch('template/platform/areaAgentOrderDetail');
    }
    /**
     * 区域分红概况
     */
    public function areaBonusProfile(){
        $month_begin = date('Y-m-01', strtotime(date("Y-m-d")));
        $month_end = date('Y-m-d', strtotime("$month_begin +1 week -1 day"));
        $this->assign("start_date", $month_begin);
        $this->assign("end_date", $month_end);
        $this->fetch('template/platform/areaBonusProfile');
    }

    /**
     * 代理等级
     */
    public function areaAgentLevelList(){
        $this->fetch('template/platform/areaAgentLevelList');
    }


    /**
     * 区域分红设置
     */
    public function areaBonusSetting()
    {
        $this->areaBasicSetting();
    }
    /**
     * 基本设置
     */
    public function areaBasicSetting()
    {
        $config= new areaBonusService();
        $list = $config->getAreaBonusSite($this->website_id);
        $this->assign("website", $list);
        $this->fetch('template/platform/areaBasicSetting');
    }
    /**
     * 结算设置
     */
    public function areaSettlementSetting()
    {
        $config= new areaBonusService();
        $list = $config->getSettlementSite($this->website_id);
        $this->assign("website", $list);
        $this->fetch('template/platform/areaSettlementSetting');
    }
    /**
     * 申请协议
     */
    public function areaApplicationAgreement()
    {
        $config= new areaBonusService();
        $list = $config->getAgreementSite($this->website_id);
        $configs = new Config();
        $lists= $configs->getBonusSite($this->website_id);
        $type=isset($_GET['type'])?$_GET['type']:'0';
        $this->assign("type", $type);
        $this->assign("websites", $lists);
        $this->assign("website", $list);
        $this->fetch('template/platform/areaApplicationAgreement');
    }

    /**
     * 添加代理等级
     */
    public function addAreaAgentLevel(){
        $agent = new areaBonusService();
        $agent_level = $agent->getAgentWeight();
        $level = new DistributorService();
        $level_info =  $level->getDistributorLevel();
        $this->assign("level_info", $level_info);
        $this->assign('level_weight',implode(',',$agent_level));
        $this->fetch('template/platform/addAreaAgentLevel');
    }
    /**
     * 修改代理等级
     */
    public function updateAreaAgentLevel(){
        $agent = new areaBonusService();
        $id=isset($_GET['id'])?$_GET['id']:'';
        $agent_level_info=$agent->getagentLevelInfo($id);
        $this->assign('id',$id);
        $this->assign('list',$agent_level_info);
        $agent_level = $agent->getAgentWeight();
        $level = new DistributorService();
        $level_info =  $level->getDistributorLevel();
        $this->assign("level_info", $level_info);
        $this->assign('level_weight',implode(',',$agent_level));
        $this->fetch('template/platform/updateAreaAgentLevel');
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
    
/*-------------------------------------------------------------------前端钩子开始-----------------------------------------------------------------------*/

     /**
     * 订单创建成功后区域分红计算
     */
    public function orderAreaBonusCalculate($params)
    {
        $bonusCalculate = new areaBonusService();
        $bonusCalculate->orderAgentBonus($params);
    }
    /**
     * 订单支付成功后分红计算
     */
    public function orderAreaPayCalculate($params)
    {
        $area_service = new areaBonusService();
        $list = $area_service->getAreaBonusSite($params['website_id']);
        $agent = new VslOrderBonusModel();
        $member = array_unique($agent->Query(['website_id'=>$params['website_id'],'from_type'=>2,'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']],'uid'));
        if($list && $list['is_use'] == 1){
            foreach($member as $k=>$v){
                $data['status'] = 3;
                $data['order_id'] = $params['order_id'];
                $data['uid'] = $v;
                $data['bonus'] = $agent->getSum(['order_id'=>$params['order_id'],'uid'=>$v,'from_type'=>2],'bonus');//分红
                $data['website_id'] = $params['website_id'];
                $area_service->addAreaBonus($data);
            }
        }
    }
    /**
     * 订单退款成功后需要重新计算订单的分红
     */
    public function updateAreaBonusMoney($params)
    {
        $area_service = new areaBonusService();
        $list = $area_service->getAreaBonusSite($params['website_id']);
        $agent = new VslOrderBonusModel();
        $member = array_unique($agent->Query(['website_id'=>$params['website_id'],'from_type'=>2,'order_goods_id'=>$params['order_goods_id'],'order_id'=>$params['order_id']],'uid'));
        if($list && $list['is_use'] == 1){
            foreach($member as $k=>$v){
                $data['status'] = 2;
                $data['order_id'] = $params['order_id'];
                $data['uid'] = $v;
                $data['bonus'] = $agent->getSum(['order_id'=>$params['order_id'],'uid'=>$v,'from_type'=>2],'bonus');//分红
                $data['website_id'] = $params['website_id'];
                $area_service->addAreaBonus($data);
            }
        }
    }

    /**
     * 分红结算(订单完成)
     */
    public function updateOrderAreaBonus($params)
    {
        $area_service = new areaBonusService();
        $list = $area_service->getAreaBonusSite($params['website_id']);
        $agent = new VslOrderBonusModel();
        $member = array_unique($agent->Query(['website_id'=>$params['website_id'],'from_type'=>2,'order_id'=>$params['order_id']],'uid'));
        if ($list && $list['is_use'] == 1) {//判断当前是否开启区域分红应用
            foreach ($member as $k => $v) {
                //订单交易完成状态
                $data['status'] = 1;
                $data['order_id'] = $params['order_id'];
                $data['website_id'] = $params["website_id"];
                $data['uid'] = $v;
                $data['bonus'] = $agent->getSum(['order_id'=>$params['order_id'],'uid'=>$v,'from_type'=>2],'bonus');//分红
                // 发放订单的区域分红
                $area_service->addAreaBonus($data);
                // 更新相关代理等级
                $area_service->updateAgentLevelInfo($v);
            }
        }
    }

    /**
     * 代理自动降级
     */
    public function autoDownAreaAgentLevel($params){
        $config= new areaBonusService();
        $list = $config->getAreaBonusSite($params['website_id']);
        if($list && $list['is_use']==1){
            $agentLevel = new areaBonusService();
            $agentLevel->autoDownagentLevel($params['website_id']);
        }
    }
    /**
     * 区域分红自动发放
     */
    public function autoGrantAreaBonus($params){
        $config= new areaBonusService();
        $config->autoGrantAreaBonus($params);
    }
}