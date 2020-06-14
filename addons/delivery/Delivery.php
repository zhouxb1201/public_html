<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/10 0010
 * Time: 15:03
 */

namespace addons\delivery;

use addons\Addons;
use addons\shop\service\Shop;
use data\model\ConfigModel;
use think\Session;

class Delivery extends Addons
{
    protected static $addons_name = 'delivery';

    public $info = [
        'name' => 'delivery',//插件名称标识
        'title' => '发货助手',//插件中文名
        'description' => '快速打印，一键发货',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'formList',//
        'config_admin_hook' => 'formList', //
        'no_set' => 1,
        'logo' => 'https://pic.vslai.com.cn/upload/common/fh170.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/fh48.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/fh481.png',
    ];//设置文件单独的钩子

    public $menu_info = [
        // platform
        [
            'module_name' => '发货助手',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '快速打印，一键发货',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'formList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '面单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 1,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '面单模版分为电子面单模版、快递单模版、发货单模版、发货人模版，电子面单对接的是快递鸟公司。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'formList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加面单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addFormTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改面单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateFormTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除面单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteFormTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑快递单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateDeliveryTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加发货单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addDeliveryTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改发货单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateDeliveryTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除发货单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteDeliveryTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加发货人模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addSenderTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改发货人模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateSenderTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加发货人模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteSenderTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改快递单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateExpressTemplate',
            'module' => 'platform'
        ],
        [
            'module_name' => '商品简称',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 2,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '商品简称用于解决打印时商品信息过长导致打印溢出的商品。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'goodsShortName',
            'module' => 'platform'
        ],
        [
            'module_name' => '打印配置',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 3,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '需要到快递鸟申请一个账号，把相应配置填充到该页面，具体操作请看详情。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'printSetting',
            'module' => 'platform'
        ],
        [
            'module_name' => '单个打印',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 4,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '以会员收货信息为中心，筛选订单数据打印。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'singlePrint',
            'module' => 'platform'
        ],
        [
            'module_name' => '批量打印',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 5,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '以订单为中心，筛选订单数据打印。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'batchPrint',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '发货助手',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '快速打印，一键发货',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'formList',
            'module' => 'admin',
            'is_admin_main' => 1
        ],
        [
            'module_name' => '面单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 1,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '面单模版分为电子面单模版、快递单模版、发货单模版、发货人模版，电子面单对接的是快递鸟公司。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'formList',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加面单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addFormTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改面单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateFormTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除面单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteFormTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '编辑快递单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateDeliveryTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加发货单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addDeliveryTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改发货单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateDeliveryTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '删除发货单模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteDeliveryTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加发货人模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addSenderTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '修改发货人模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateSenderTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加发货人模板',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteSenderTemplate',
            'module' => 'admin'
        ],
        [
            'module_name' => '商品简称',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 2,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '商品简称用于解决打印时商品信息过长导致打印溢出的商品。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'goodsShortName',
            'module' => 'admin'
        ],
        [
            'module_name' => '打印配置',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 3,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '需要到快递鸟申请一个账号，把相应配置填充到该页面，具体操作请看详情。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'printSetting',
            'module' => 'admin'
        ],
        [
            'module_name' => '单个打印',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 4,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '以会员收货信息为中心，筛选订单数据打印。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'singlePrint',
            'module' => 'admin'
        ],
        [
            'module_name' => '批量打印',
            'parent_module_name' => '发货助手',//上级模块名称 用来确定上级目录
            'sort' => 5,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '以订单为中心，筛选订单数据打印。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'batchPrint',
            'module' => 'admin'
        ]
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function formList()
    {
        $this->assign('formListUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/formList')));
        $this->assign('expressListUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/expressList')));
        $this->assign('deliveryListUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/deliveryList')));
        $this->assign('senderListUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/senderList')));

        $this->assign('deleteFormTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/deleteFormTemplate')));
        $this->assign('deleteDeliveryTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/deleteDeliveryTemplate')));
        $this->assign('deleteSenderTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/deleteSenderTemplate')));

        $this->assign('defaultFormUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/defaultForm')));
        $this->assign('defaultExpressUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/defaultExpress')));
        $this->assign('defaultDeliveryUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/defaultDelivery')));
        $this->assign('defaultSenderUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/defaultSender')));

        $this->fetch('template/' . $this->module . '/formList');
    }

    public function addFormTemplate()
    {
        $this->assign('formExpressCompanyListUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/formExpressCompanyList')));
        $this->assign('saveFormTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/saveFormTemplate')));
        $this->fetch('template/' . $this->module . '/formTemplate');
    }

    public function updateFormTemplate()
    {
        $id = request()->get('id');
        $this->assign('id', $id);
        $this->assign('formTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/formTemplateDetail')));
        $this->assign('formExpressCompanyListUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/formExpressCompanyList')));
        $this->assign('saveFormTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/saveFormTemplate')));
        $this->fetch('template/' . $this->module . '/formTemplate');
    }

    public function addDeliveryTemplate()
    {
        $this->assign('saveDeliveryTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/saveDeliveryTemplate')));
        $this->fetch('template/' . $this->module . '/deliveryTemplate');
    }

    public function updateDeliveryTemplate()
    {
        $id = request()->get('id');
        $this->assign('id', $id);
        $this->assign('saveDeliveryTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/saveDeliveryTemplate')));
        $this->assign('deliveryTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/deliveryTemplateDetail')));
        $this->fetch('template/' . $this->module . '/deliveryTemplate');
    }

    public function addSenderTemplate()
    {
        $this->assign('saveSenderTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/saveSenderTemplate')));
        $this->assign('areaUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/area')));
        $this->fetch('template/' . $this->module . '/senderTemplate');
    }

    public function updateSenderTemplate()
    {
        $id = request()->get('id');
        $this->assign('id', $id);
        $this->assign('senderTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/senderTemplateDetail')));
        $this->assign('saveSenderTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/saveSenderTemplate')));
        $this->assign('areaUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/area')));
        $this->fetch('template/' . $this->module . '/senderTemplate');
    }

    public function updateExpressTemplate()
    {
        $id = request()->get('id');
        $this->assign('id', $id);
        $this->assign('saveExpressTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/saveExpressTemplate')));
        $this->assign('expressTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/expressTemplateDetail')));
        $this->fetch('template/' . $this->module . '/expressTemplate');
    }


    public function goodsShortName()
    {
        if ($this->module == 'platform') {
            $this->assign('categoryUrl', __URL('PLATFORM_MAIN/goods/category'));
            $this->assign('goodsListUrl', __URL('PLATFORM_MAIN/goods/selfgoodslist'));
            $this->assign('ajaxEditGoodsUrl', __URL('PLATFORM_MAIN/goods/ajaxEditGoodsNameOrIntroduction'));
        } else {
            $this->assign('ajaxEditGoodsUrl', __URL('ADMIN_MAIN/goods/ajaxEditGoodsDetail'));
            $this->assign('categoryUrl', __URL('ADMIN_MAIN/goods/category'));
            $this->assign('goodsListUrl', __URL('ADMIN_MAIN/goods/goodslist'));
        }
        $this->fetch('template/' . $this->module . '/goodsShortName');
    }

    public function printSetting()
    {
        $config_model = new ConfigModel();
        $info = $config_model::get(['website_id' => $this->website_id, 'instance_id' => $this->instance_id, 'key' => 'DELIVERY_ASSISTANT']);
        $this->assign('info', json_decode($info['value'], true));
        $this->assign('saveSettingUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/printSetting')));
        $this->assign('formPrintUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/formPrint')));
        if(getAddons('shop', $this->website_id)){
            $shop_service = new Shop();
            $this->assign('mall_name', $shop_service->getShopInfo($this->instance_id, 'shop_name')['shop_name']);
        }
        $this->fetch('template/' . $this->module . '/printSetting');
    }

    public function singlePrint()
    {
        $config_model = new ConfigModel();
        $delivery_info = $config_model::get(['website_id' => $this->website_id, 'instance_id' => $this->instance_id, 'key' => 'DELIVERY_ASSISTANT']);
        $this->assign('portUrl', json_decode($delivery_info['value'])->port);
        $this->assign('areaUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/area')));
        $this->assign('deliveryTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/deliveryTemplateDetail')));
        $this->assign('senderTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/senderTemplateDetail')));
        $this->assign('expressTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/expressTemplateDetail')));
        $this->assign('formPrintUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/formPrint')));
        $this->assign('orderDeliveryModal', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/orderDeliveryModal')));
        $this->assign('orderDelivery',__URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/orderDelivery')));
        if(getAddons('shop', $this->website_id)){
            $shop_service = new Shop();
            $this->assign('mall_name', $shop_service->getShopInfo($this->instance_id, 'shop_name')['shop_name']);
        }
        $this->fetch('template/' . $this->module . '/singlePrint');
    }

    public function batchPrint()
    {
        $config_model = new ConfigModel();
        $delivery_info = $config_model::get(['website_id' => $this->website_id, 'instance_id' => $this->instance_id, 'key' => 'DELIVERY_ASSISTANT']);
        $this->assign('portUrl', json_decode($delivery_info['value'])->port);
        $this->assign('areaUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/area')));
        $this->assign('deliveryTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/deliveryTemplateDetail')));
        $this->assign('senderTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/senderTemplateDetail')));
        $this->assign('expressTemplateDetailUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/expressTemplateDetail')));
        $this->assign('formPrintUrl', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/formPrint')));
        $this->assign('orderDeliveryModal', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/orderDeliveryModal')));
        $this->assign('orderDelivery',__URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/orderDelivery')));
        if(getAddons('shop', $this->website_id)){
            $shop_service = new Shop();
            $this->assign('mall_name', $shop_service->getShopInfo($this->instance_id, 'shop_name')['shop_name']);
        }
        $this->fetch('template/' . $this->module . '/batchPrint');
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