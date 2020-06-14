<?php
namespace addons\paygift;

use addons\Addons;
use data\model\AddonsConfigModel;
use addons\paygift\server\PayGift as PayGiftServer;

class Paygift extends Addons
{
    public $info = array(
        'name' => 'paygift',//插件名称标识
        'title' => '支付有礼',//插件中文名
        'description' => '支付完成可赠送实物或虚拟礼品',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'paygiftList',//
        'config_admin_hook' => 'paygiftList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197133.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782191.png  ',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782313.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '支付有礼',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '支付完成可赠送实物或虚拟礼品',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'paygiftList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '活动列表',
            'parent_module_name' => '支付有礼', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '商家根据会员消费情况参与送礼活动，帮助商家促进留存，提高复购率的新型营销应用。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'paygiftList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改支付有礼',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '目前支持购满一定金额和购买指定商品送礼，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”，若同时存在多档活动，则按优先级高的为准。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updatePaygift',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加支付有礼',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '目前支持购满一定金额和购买指定商品送礼，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”，若同时存在多档活动，则按优先级高的为准。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addPaygift',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除支付有礼',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除支付有礼',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deletePaygift',
            'module' => 'platform'
        ],
        [
            'module_name' => '支付有礼中奖记录',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '支付有礼中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyPaygift',
            'module' => 'platform'
        ],
        [
            'module_name' => '支付有礼详情',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'paygiftDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭后，所有支付有礼活动均不生效。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'paygiftSetting',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '支付有礼',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '支付完成可赠送实物或虚拟礼品',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'paygiftList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '活动列表',
            'parent_module_name' => '支付有礼', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '商家根据会员消费情况参与送礼活动，帮助商家促进留存，提高复购率的新型营销应用。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'paygiftList',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改支付有礼',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '目前支持购满一定金额和购买指定商品送礼，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”，若同时存在多档活动，则按优先级高的为准。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updatePaygift',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加支付有礼',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '目前支持购满一定金额和购买指定商品送礼，奖品可设置“余额、积分、优惠券、礼品券、商品、赠品”，若同时存在多档活动，则按优先级高的为准。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addPaygift',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除支付有礼',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除支付有礼',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deletePaygift',
            'module' => 'admin'
        ],
        [
            'module_name' => '支付有礼中奖记录',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '支付有礼中奖记录',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'historyPaygift',
            'module' => 'admin'
        ],
        [
            'module_name' => '支付有礼详情',
            'parent_module_name' => '支付有礼',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'paygiftDetail',
            'module' => 'admin'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform') {
            $this->assign('paygiftListUrl', __URL(addons_url_platform('paygift://Paygift/paygiftList')));
            $this->assign('addPaygiftUrl', __URL(addons_url_platform('paygift://Paygift/addPaygift')));
            $this->assign('updatePaygiftUrl', __URL(addons_url_platform('paygift://Paygift/updatePaygift')));
            $this->assign('deletePaygiftUrl', __URL(addons_url_platform('paygift://Paygift/deletePaygift')));
            $this->assign('historyPaygiftUrl', __URL(addons_url_platform('paygift://Paygift/historyPaygift')));
            $this->assign('prizeTypeUrl', __URL(addons_url_platform('paygift://Paygift/prizeType')));
            $this->assign('saveSettingUrl', __URL(addons_url_platform('paygift://Paygift/saveSetting')));
        } else if ($this->module == 'admin') {
            $this->assign('paygiftListUrl', __URL(addons_url_admin('paygift://Paygift/paygiftList')));
            $this->assign('addPaygiftUrl', __URL(addons_url_admin('paygift://Paygift/addPaygift')));
            $this->assign('updatePaygiftUrl', __URL(addons_url_admin('paygift://Paygift/updatePaygift')));
            $this->assign('deletePaygiftUrl', __URL(addons_url_admin('paygift://Paygift/deletePaygift')));
            $this->assign('historyPaygiftUrl', __URL(addons_url_admin('paygift://Paygift/historyPaygift')));
            $this->assign('prizeTypeUrl', __URL(addons_url_admin('paygift://Paygift/prizeType')));
        }
    }
    
    public function paygiftList()
    {
        $this->fetch('template/' . $this->module . '/paygiftList');
    }
    
    public function addPaygift()
    {
        $info = $setup = [];
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id, true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id, true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updatePaygift');
    }
    
    public function updatePaygift()
    {
        $info = $setup = [];
        $condition['pay_gift_id'] = (int)input('get.pay_gift_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $PayGiftServer = new PayGiftServer();
        $info = $PayGiftServer->getPayGiftDetail($condition,1);
        $setup['coupontype']['is_use'] = getAddons('coupontype', $this->website_id, $this->instance_id);
        $setup['coupontype']['is_state'] = getAddons('coupontype', $this->website_id, $this->instance_id, true);
        $setup['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['giftvoucher']['is_state'] = getAddons('giftvoucher', $this->website_id, $this->instance_id, true);
        $setup['gift']['is_use'] = 0;//getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $setup['gift']['is_state'] = 0;
        $this->assign('setup', $setup);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/updatePaygift');
    }
    
    public function paygiftDetail()
    {
        $condition['pay_gift_id'] = (int)input('get.pay_gift_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $PayGiftServer = new PayGiftServer();
        $info = $PayGiftServer->getPayGiftDetail($condition,2);
        $this->assign('info',$info);
        $this->fetch('template/' . $this->module . '/paygiftDetails');
    }
    
    public function historyPaygift()
    {
        $condition['pay_gift_id'] = (int)input('get.pay_gift_id');
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $PayGiftServer = new PayGiftServer();
        $info = $PayGiftServer->getPayGiftDetail($condition);
        $this->assign('info', $info);
        $this->fetch('template/' . $this->module . '/historyPaygift');
    }
    
    public function paygiftSetting()
    {
        $configModel = new AddonsConfigModel();
        $info = $configModel->getInfo([
            'addons' => 'paygift',
            'website_id' => $this->website_id
        ], 'is_use');
        $this->assign('is_use', $info['is_use']);
       $this->fetch('template/' . $this->module . '/paygiftSetting');
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