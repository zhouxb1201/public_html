<?php
namespace addons\globalbonus;
use addons\Addons as Addo;
use addons\globalbonus\service\GlobalBonus as  globalBonusService;
use data\model\VslOrderModel;
use addons\bonus\model\VslOrderBonusModel;
use addons\distribution\service\Distributor as  DistributorService;
use data\service\Config;
use addons\bonus\model\VslUnGrantBonusOrderModel;
use addons\bonus\model\VslAgentAccountRecordsModel;

class GlobalBonus extends Addo
{
    public $info = array(
        'name' => 'globalbonus', // 插件名称标识
        'title' => '全球分红', // 插件中文名
        'description' => '全球股东分红商城所有订单', // 插件概述
        'status' => 1, // 状态 1启用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', // 版本号
        'has_addonslist' => 1, // 是否有下级插件 例如：第三方登录插件下有 qq登录，微信登录
        'content' => '', // 插件的详细介绍或使用方法
        'config_hook' => 'globalBonusProfile',
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197092.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782148.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782268.png',
    ); // 设置文件单独的钩子

    public $menu_info = array(
        [
            'module_name' => '全球分红',
            'parent_module_name' => '应用', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '全球股东分红商城所有订单', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalBonusProfile',
            'is_main' => 1
        ],

        [
            'module_name' => '分红概况',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => 0, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分红概况', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalBonusProfile'
        ],

        [
            'module_name' => '股东等级',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => 2, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '股东可设置不同等级享受不同的分红返利，权重越大等级越高。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalAgentLevelList'
        ],

        [
            'module_name' => '添加股东等级',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '股东可设置不同等级享受不同的分红返利，权重越大等级越高，可在商品（分销分红）或活动（基础设置）单独设置返佣比例，优先级为 商品>活动>股东等级。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addGlobalAgentLevel'
        ],

        [
            'module_name' => '修改股东等级',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '股东可设置不同等级享受不同的分红返利，权重越大等级越高，可在商品（分销分红）或活动（基础设置）单独设置返佣比例，优先级为 商品>活动>股东等级。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateGlobalAgentLevel'
        ],
        [
            'module_name' => '删除股东等级',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '删除股东等级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'deleteGlobalAgentlevel'
        ],
        [
            'module_name' => '修改股东状态',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '修改股东状态', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'setGlobalAgentStatus'
        ],
        [
            'module_name' => '股东列表',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => 1, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '全球股东可获得平台所有订单一定比例的分红奖励。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalAgentList'
        ],
        [
            'module_name' => '股东详情',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalAgentInfo'
        ],

        [
            'module_name' => '基础设置',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => 5, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '全球分红的基础相关设置，可设置成为股东条件、跳降级、结算、申请协议等设置。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalBonusSetting'
        ],
        [
            'module_name' => '分红结算单',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => 3, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '全球股东待分红的结算单，分红默认为手动发放，可在“基础设置》结算设置”设置为按周期自动发放。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalBonusBalance'
        ],
        [
            'module_name' => '分红明细',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => 4, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '可查看全球股东分红打款的数据信息。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalBonusDetail'
        ],
        [
            'module_name' => '分红明细详情',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalBonusInfo'
        ],
        [
            'module_name' => '分红基本设置',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分红基本设置', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalBasicSetting'
        ],
        [
            'module_name' => '分红结算设置',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' =>0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '分红结算设置', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalSettlementSetting'
        ],
        [
            'module_name' => '股东申请协议',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '股东申请协议', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'globalApplicationAgreement'
        ],
        [
            'module_name' => '股东自动降级',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '股东自动降级', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'autoDownGlobalAgentLevel',
            'is_member'=>1
        ],
        [
            'module_name' => '股东订单支付成功',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '股东订单支付成功', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderGlobalPayCalculate',
            'is_member'=>1
        ],
        [
            'module_name' => '股东订单创建成功',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '股东订单创建成功', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderGlobalBonusCalculate',
            'is_member'=>1
        ],
        [
            'module_name' => '股东订单完成分红计算',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '股东订单完成分红计算', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateOrderGlobalBonus',
            'is_member'=>1
        ],
        [
            'module_name' => '股东退款完成分红计算',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '股东退款完成分红计算', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'updateGlobalBonusMoney',
            'is_member'=>1
        ],
        [
            'module_name' => '全球分红自动发放',
            'parent_module_name' => '全球分红', // 上级模块名称 用来确定上级目录
            'sort' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '全球分红自动发放', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'autoGrantGlobalBonus',
            'is_member'=>1
        ]
    ); // 钩子名称（需要该钩子调用的页面）

     public function __construct(){
        parent::__construct();
         $config= new globalBonusService();
         $list = $config->getGlobalBonusSite($this->website_id);
         if($this->merchant_expire==1){
             $this->assign('merchant_expire',$this->merchant_expire);
         }
         $this->assign("website", $list);
         $this->assign('deleteGlobalAgentLevelUrl', __URL(addons_url_platform('globalbonus://globalbonus/deleteGlobalAgentLevel')));
        $this->assign('globalAgentListUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalAgentList')));
        $this->assign('globalBonusProfileUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalBonusProfile')));
        $this->assign('globalBonusOrderProfileUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalBonusOrderProfile')));
        $this->assign('globalBonusSettingUrl', __URL(addons_url_platform('globalbonus://globalBonus/globalBonusSetting')));
        $this->assign('addGlobalAgentLevelUrl', __URL(addons_url_platform('globalbonus://globalbonus/addGlobalAgentLevel')));
        $this->assign('updateGlobalAgentLevelUrl', __URL(addons_url_platform('globalbonus://globalbonus/updateGlobalAgentLevel')));
        $this->assign('globalAgentLevelListUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalAgentLevelList')));
         $this->assign('globalMessagePushListUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalMessagePushList')));
         $this->assign('globalEditMessageUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalEditMessage')));
         $this->assign('addGlobalMessageUrl', __URL(addons_url_platform('globalbonus://globalbonus/addGlobalMessage')));
        $this->assign('website_id', $this->website_id);
        $this->assign('pageshow','10');
        $this->assign('globalBasicSettingUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalBasicSetting')));
        $this->assign('globalSettlementSettingUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalSettlementSetting')));
        $this->assign('globalApplicationAgreementUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalApplicationAgreement')));
        $this->assign('deleteGlobalAgentlevelUrl', __URL(addons_url_platform('globalbonus://globalbonus/deleteGlobalAgentlevel')));
        $this->assign('globalAgentInfoUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalAgentInfo')));
        $this->assign('setGlobalAgentStatusUrl', __URL(addons_url_platform('globalbonus://globalbonus/setGlobalAgentStatus')));
        $this->assign('delGlobalAgentUrl', __URL(addons_url_platform('globalbonus://globalbonus/delGlobalAgent')));
        $this->assign('updateGlobalAgentInfoUrl', __URL(addons_url_platform('globalbonus://globalbonus/updateGlobalAgentInfo')));
        $this->assign('globalBonusBalanceUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalBonusBalance')));
        $this->assign('globalBonusListUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalBonusList')));
        $this->assign('globalBonusGrantUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalBonusGrant')));
        $this->assign('globalBonusInfoUrl', __URL(addons_url_platform('globalbonus://globalbonus/globalBonusInfo')));
    }

    /**
     * 实现第三方钩子
     *
     * @param array $params            
     */


    /**
     * 股东列表
     */
    public function globalAgentList()
    {
        $level = new globalBonusService();
        $agent_level = $level->getagentLevel();
        $this->assign('agent_level',$agent_level);
        $this->fetch('template/platform/globalAgentList');
    }
    /**
     * 全球分红结算单
     */
    public function globalBonusBalance()
    {
        $this->fetch('template/platform/globalBonusBalance');
    }
    /**
     * 全球分红明细
     */
    public function globalBonusDetail()
    {
        $this->fetch('template/platform/globalBonusDetail');
    }
    /**
     * 全球分红明细
     */
    public function globalBonusInfo()
    {
        $member = new globalBonusService();
        $agent_level = $member->getagentLevel();
        $this->assign('agent_level',$agent_level);
        $this->assign('sn',$_GET['sn']);
        $this->fetch('template/platform/globalBonusInfo');
    }
    /**
     * 股东详情页面
     */
    public function globalAgentInfo(){
        $member = new globalBonusService();
        $uid = $_GET['agent_id'];
        $res= $member->getAgentInfo($uid);
        $agent_level = $member->getagentLevel();
        $this->assign('agent_level',$agent_level);
        $this->assign('info',$res);
        $this->fetch('template/platform/globalAgentInfo');
    }
    
    /**
     * 全球分红概况
     */
    public function globalBonusProfile(){
        $month_begin = date('Y-m-01', strtotime(date("Y-m-d")));
        $month_end = date('Y-m-d', strtotime("$month_begin +1 week -1 day"));
        $this->assign("start_date", $month_begin);
        $this->assign("end_date", $month_end);
        $this->fetch('template/platform/globalBonusProfile');
    }

    /**
     * 股东等级
     */
    public function globalAgentLevelList(){
        $this->fetch('template/platform/globalAgentLevelList');
    }


    /**
     * 全球分红设置
     */
    public function globalBonusSetting()
    {
        $this->globalBasicSetting();
    }
    /**
     * 基本设置
     */
    public function globalBasicSetting()
    {
        $config= new globalBonusService();
        $list = $config->getGlobalBonusSite($this->website_id);
        $this->assign("website", $list);
        $this->fetch('template/platform/globalBasicSetting');
    }
    /**
     * 结算设置
     */
    public function globalSettlementSetting()
    {
        $config= new globalBonusService();
        $list = $config->getSettlementSite($this->website_id);
        $this->assign("website", $list);
        $this->fetch('template/platform/globalSettlementSetting');
    }
    /**
     * 申请协议
     */
    public function globalApplicationAgreement()
    {
        $config= new globalBonusService();
        $list = $config->getAgreementSite($this->website_id);
        $this->assign("website", $list);
        $configs = new Config();
        $lists= $configs->getBonusSite($this->website_id);
        $this->assign("websites", $lists);
        $type=isset($_GET['type'])?$_GET['type']:'0';
        $this->assign("type", $type);
        $this->fetch('template/platform/globalApplicationAgreement');
    }

    /**
     * 添加股东等级
     */
    public function addGlobalAgentLevel(){
        $agent = new globalBonusService();
        $agent_level = $agent->getAgentWeight();
        $this->assign('level_weight',implode(',',$agent_level));
        $level = new DistributorService();
        $level_info =  $level->getDistributorLevel();
        $this->assign("level_info", $level_info);
        $this->fetch('template/platform/addGlobalAgentLevel');
    }
    /**
     * 修改股东等级
     */
    public function updateGlobalAgentLevel(){
        $agent = new globalBonusService();
        $id=isset($_GET['id'])?$_GET['id']:'';
        $agent_level_info=$agent->getagentLevelInfo($id);
        $this->assign('id',$id);
        $this->assign('list',$agent_level_info);
        $agent_level = $agent->getAgentWeight();
        $this->assign('level_weight',implode(',',$agent_level));
        $level = new DistributorService();
        $level_info =  $level->getDistributorLevel();
        $this->assign("level_info", $level_info);
        $this->fetch('template/platform/updateGlobalAgentLevel');
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
     * 订单创建成功后全球分红计算
     */
    public function orderGlobalBonusCalculate($params)
    {
        $bonusCalculate = new globalBonusService();
        $bonusCalculate->orderAgentBonus($params);
    }
    /**
     * 订单支付成功后分红计算
     */
    public function orderGlobalPayCalculate($params)
    {
        $global_service = new globalBonusService();
        $list = $global_service->getGlobalBonusSite($params['website_id']);
        $agent = new VslOrderBonusModel();
        $member = array_unique($agent->Query(['website_id'=>$params['website_id'],'from_type'=>1,'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']],'uid'));
        if($list && $list['is_use'] == 1){
            
            $data=[];
            foreach($member as $k=>$v){ 
                $rec_bonus = $agent->getInfo(['order_id'=>$params['order_id'],'uid'=>$v,'order_goods_id'=>$params['order_goods_id'],'from_type'=>1],'bonus,id,pay_status');
                $data['status'] = 3;
                $data['order_id'] = $params['order_id'];
                $data['uid'] = $v;
                $data['bonus'] = $rec_bonus['bonus'];
                $data['website_id'] = $params['website_id'];
                $data['pay_status'] = $rec_bonus['pay_status'];
                $data['rec_bonus_id'] = $rec_bonus['id'];
                if($data['pay_status'] == 1){
                    continue;
                }
                $global_service->addGlobalBonus($data);
            }
            
        }
    }
    /**
     * 订单退款成功后需要重新计算订单的分红
     */
    public function updateGlobalBonusMoney($params)
    {
        $global_service = new globalBonusService();
        $list = $global_service->getGlobalBonusSite($params['website_id']);
        $agent = new VslOrderBonusModel();
        $member = array_unique($agent->Query(['website_id'=>$params['website_id'],'from_type'=>1,'order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id']],'uid'));
        if($list && $list['is_use'] == 1){
            foreach($member as $k=>$v){
                $data['status'] = 2;
                $data['order_id'] = $params['order_id'];
                $data['uid'] = $v;
                $data['bonus'] = $agent->getInfo(['order_id'=>$params['order_id'],'order_goods_id'=>$params['order_goods_id'],'uid'=>$v,'from_type'=>1],'bonus')['bonus'];
                $data['website_id'] = $params['website_id'];
                $global_service->addGlobalBonus($data);
            }
        }
    }

    /**
     * 分红结算(订单完成)
     */
    public function updateOrderGlobalBonus($params)
    {
        $global_service = new globalBonusService();
        $order = new VslOrderModel();
        $buyer_id = $order->getInfo(['website_id'=>$params['website_id'],'order_id'=>$params['order_id']],'buyer_id')['buyer_id'];
        $list = $global_service->getGlobalBonusSite($params['website_id']);
        $orderBonus = new VslOrderBonusModel();
        $member = array_unique($orderBonus->Query(['website_id'=>$params['website_id'],'from_type'=>1,'order_id'=>$params['order_id']],'uid'));
        if ($list && $list['is_use'] == 1) {//判断当前是否开启全球分红应用
            $accountRecord = [];
            $ungrantBonus = [];
            foreach ($member as $k => $v) {
                //订单交易完成状态
                $data['status'] = 1;
                $data['order_id'] = $params['order_id'];
                $data['website_id'] = $params["website_id"];
                $data['uid'] = $v;
                $data['bonus'] = $orderBonus->getSum(['order_id'=>$params['order_id'],'uid'=>$v,'from_type'=>1],'bonus');//分红
                $check_pay_status = $orderBonus->getInfo(['order_id'=>$params['order_id'],'uid'=>$v,'from_type'=>1], 'cal_status');
                if($check_pay_status['cal_status'] == 1){
                    continue;
                }
                // 发放订单的全球分红
                $result = $global_service->addGlobalBonus($data);
                if($result['account_record']){
                    $accountRecord[] = $result['account_record'];
                }
                if($result['ungrant_bonus']){
                    $ungrantBonus[] = $result['ungrant_bonus'];
                }
                
                // 更新相关股东等级
                //$global_service->updateAgentLevelInfo($v);

            }
            $agentAccountRecord = new VslAgentAccountRecordsModel();
            $agentAccountRecord->insertAll($accountRecord);
            $unGrantBonusModel = new VslUnGrantBonusOrderModel();
            $unGrantBonusModel->insertAll($ungrantBonus);
            $global_service->becomeAgent($buyer_id);//成为股东
        }
    }
    /**
     * 手动修正分红结算(订单完成)
     */
    public function handUpdateOrderGlobalBonus($params)
    {
        $global_service = new globalBonusService();
        $order = new VslOrderModel();
        $buyer_id = $order->getInfo(['website_id'=>$params['website_id'],'order_id'=>$params['order_id']],'buyer_id')['buyer_id'];
        $list = $global_service->getGlobalBonusSite($params['website_id']);
        $agent = new VslOrderBonusModel();
        $member = array_unique($agent->Query(['website_id'=>$params['website_id'],'from_type'=>1,'order_id'=>$params['order_id']],'uid'));
        if ($list && $list['is_use'] == 1) {//判断当前是否开启全球分红应用
            foreach ($member as $k => $v) {
                //订单交易完成状态
                $data['status'] = 1;
                $data['order_id'] = $params['order_id'];
                $data['website_id'] = $params["website_id"];
                $data['uid'] = $v;
                $data['bonus'] = $agent->getSum(['order_id'=>$params['order_id'],'uid'=>$v,'from_type'=>1],'bonus');//分红
                $check_pay_status = $agent->getInfo(['order_id'=>$params['order_id'],'uid'=>$v,'from_type'=>1], 'cal_status');
                if($check_pay_status['cal_status'] == 1){
                    continue;
                }
                // 发放订单的全球分红
                $global_service->addGlobalBonus($data);
                // 更新相关股东等级
                $global_service->updateAgentLevelInfo($v);
            }
            $global_service->becomeAgent($buyer_id);//成为股东
        }
    }

    /**
     * 股东自动降级
     */
    public function autoDownGlobalAgentLevel($params){
        $agentLevel = new globalBonusService();
        $agentLevel->autoDownagentLevel($params['website_id']);
    }

    /**
     * 全球分红自动发放
     */
    public function autoGrantGlobalBonus($params){
        $config= new globalBonusService();
        $config->autoGrantGlobalBonus($params);
    }
}