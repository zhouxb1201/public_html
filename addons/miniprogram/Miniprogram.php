<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:35
 */

namespace addons\miniprogram;

use addons\Addons;
use addons\miniprogram\model\WeixinAuthModel;
use data\extend\WchatOpen;
use data\model\AddonsConfigModel;
use data\model\WebSiteModel;
use data\service\Config as ConfigService;
use think\Request;
use think\Session;
use addons\miniprogram\service\MiniProgram as miniProgramService;
use data\model\SysAddonsModel;
use addons\miniprogram\controller\Miniprogram as miniprogramController;

class MiniProgram extends Addons
{
    protected static $addons_name = 'miniprogram';

    public $info = [
        'name' => 'miniprogram',//插件名称标识
        'title' => '小程序',//插件中文名
        'description' => '在线生成发布，唾手可得',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'miniprogram',//
        'config_admin_hook' => 'miniProgramManage', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197114.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782173.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782293.png',
    ];//设置文件单独的钩子

    public $menu_info = [
        //platform
        [
            'module_name' => '小程序',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '在线生成发布，唾手可得',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'miniProgramManage',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '小程序管理',
            'parent_module_name' => '小程序',//上级模块名称 用来确定上级目录
            'sort' => 1,//子菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '任何时候您都可以通过绑定体验者来查看小程序最新效果。请务必先体验，效果满意后再保存发布！',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'miniProgramManage',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '小程序',//上级模块名称 用来确定上级目录
            'sort' => 3,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可对小程序授权、微信支付配置、微信消息模版通知配置。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'miniProgramSetting',
            'module' => 'platform'
        ],
        [
            'module_name' => '小程序装修',
            'parent_module_name' => '小程序',//上级模块名称 用来确定上级目录
            'sort' => 2,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '小程序商城支持商城首页、店铺首页（需购买多店应用）、商品详情页、会员中心、自定义页装修。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'miniProgramCustomList',
            'module' => 'platform'
        ],
        [
            'module_name' => '装修小程序',
            'parent_module_name' => '小程序',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'miniProgramCustom',
            'module' => 'platform'
        ],
        [
            'module_name' => '授权小程序',
            'parent_module_name' => '小程序',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '小程序授权',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'miniProgramAuth',
            'module' => 'platform'
        ],
        [
            'module_name' => '回调小程序',
            'parent_module_name' => '小程序',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '小程序回调',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 0,//是否有控制权限
            'hook_name' => 'miniProgramCallback',
            'module' => 'platform'
        ],
        [
            'module_name' => '小程序商城',
            'parent_module_name' => '商城', //上级模块名称 用来确定上级目录
            'sort' => 4, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '小程序商城支持商城首页、店铺首页（需购买多店应用）、商品详情页、会员中心、自定义页装修。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'miniProgramManage',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '小程序',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '在线生成发布，唾手可得',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'miniProgramCustomList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '小程序装修',
            'parent_module_name' => '小程序',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '小程序商城支持店铺首页、商品详情页、自定义页装修。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'miniProgramCustomList',
            'module' => 'admin'
        ],
        [
            'module_name' => '装修小程序',
            'parent_module_name' => '小程序',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'miniProgramCustom',
            'module' => 'admin'
        ],
    ];
    public $mini_program_service;
    public $wchat_open;

    public function __construct()
    {
        parent::__construct();
        $this->mini_program_service = new miniProgramService();
        $this->wchat_open = new WchatOpen($this->website_id);
    }

    public function miniProgramManage()
    {
        //$auth_id = request()->get('auth_id');
        // 目前一个website_id对象只有一个小程序
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $mini_program_info = $this->mini_program_service->miniProgramInfo($condition);
        $this->assign('mplive', isExistAddons('mplive', $this->website_id));
        if($this->module=='platform' || $this->module == 'admin'){
        if (empty($mini_program_info)) {
            // 绑定新的小程序
            $callback = Request::instance()->domain() . '/' . $this->module . '/Menu/addonmenu?addons=miniProgramCallback';
            $auth_url = $this->wchat_open->auth($callback); //授权认证
            $this->assign('auth_url', $auth_url);
            $this->assign('miniProgramManageUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniProgram/miniProgramManage')));
            $this->assign('miniProgramAuth', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniProgram/miniProgramAuth')));
            $this->assign('commitUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/commitMp')));
            $this->fetch('template/' . $this->module . '/miniProgramAuth');
        } else {
            $this->assign('mp_info', $mini_program_info);
            $this->assign('bindMpTesterUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/bindMpTester')));
            $this->assign('testerListModalUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/testerList')));
//            $this->assign('testQrCodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/testerQrCode')));
            $this->assign('commitUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/commitMp')));
            $this->assign('submitModalUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/submitModal')));
            $this->assign('submitUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/submit')));
            $this->assign('editMpUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniProgram/editMpTemplateName')));
            $this->assign('sunCodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/getLimitMpCode')));
            $this->assign('downSunCodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/downSunCode')));
            $this->assign('submitListUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/submitList')));
            $this->assign('getPublicStatusUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/getPublicStatus')));
            $this->assign('isUseMiniProgramUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/isUseAndHasCategory')));
            $this->assign('isHasAppSecretUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/isHasAppSecret')));
            $this->assign('recallcommitMpUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/recallcommitMp')));
            $this->assign('status', $this->mini_program_service->getLastSumitStatus($condition));
            // 体验码
            $mini_program_controller =  new miniprogramController();
            $res = $mini_program_controller->testerQrCode();
            $this->assign('testQrCodeUrl', $res);
            $this->fetch('template/' . $this->module . '/miniProgramManage');
            }
        }
    }

    public function miniProgramCustomList()
    {
        $count = $this->mini_program_service->getCustomTemplateCount(['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
        if ($count == 0) {
            $this->mini_program_service->initCustomTemplate($this->website_id, $this->instance_id);
        }
        $this->assign('mpCustomTemplateListUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/miniProgramCustomList')));
        $this->assign('deleteMpCustomTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/deleteMpCustomTemplate')));
        $this->assign('useMpCustomTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/useMpCustomTemplate')));
        $this->assign('mpTemplateDialogUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/mpTemplateDialog')));
        $this->assign('mpSystemDefaultTemplateUrl',__URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/mpSystemDefaultTemplate')));
        $this->assign('editMpUrl',__URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/editMpTemplateName')));
        $this->fetch('template/' . $this->module . '/mpCustomTemplateList');
    }

    public function miniProgramCustom()
    {
        $website_model = new WebSiteModel();
        $id = request()->get('id');
        if ($id) {
            $custom_template_info = $this->mini_program_service->customTemplateInfo(['id' => $id]);
            $template_data = $custom_template_info['template_data'];
            $type = $custom_template_info['type'];
            $template_name = $custom_template_info['template_name'];
            //底部
            $bar_info = $custom_template_info = $this->mini_program_service->customTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 7]);
            $tab_bar = $bar_info['template_data'];
            //版权
            $copyright_info = $custom_template_info = $this->mini_program_service->customTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 8]);
            $copyright = $copyright_info['template_data'];
        } else {
            $template_data = '';
            $template_name = '';
            $tab_bar = '';
            $type = 1;
            $copyright = '';
        }
        $addons = new SysAddonsModel();
        $allAddons = $addons->getQuery(['status' => 1], 'name', '');
        $addonsIsUse = [];
        if($allAddons){
            foreach($allAddons as $val){
                $addonsIsUse[$val['name']] = getAddons($val['name'], $this->website_id);
            }
        }
        $this->assign('useMpCustomTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/useMpCustomTemplate')));
        $this->assign('createCustomTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/createCustomTemplate')));
        // 增加装修页面左上角
        $condition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'type' => ['NOT IN', [7, 8, 10]]
        ];
        $list = $this->mini_program_service->customTemplateList(1, 0, $condition);
        $this->assign('template_list', $list['data']);

        $this->assign('shop_id', $this->instance_id);
        $this->assign('id', $id);
        $this->assign('type', $type);
        $this->assign('template_data', $template_data);
        $this->assign('tabbar', $tab_bar);
        $this->assign('copyright', $copyright);
        $this->assign('template_name', $template_name);
        $this->assign('addonsIsUse', json_encode($addonsIsUse));
        $this->assign('default_version',$website_model::get($this->website_id,['merchant_version'])['merchant_version']['is_default']);
        $this->fetch('template/' . $this->module . '/miniProgramCustom');
    }

    public function miniProgramSetting()
    {
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $mini_program_info = $this->mini_program_service->miniProgramInfo($condition);
        if (empty($mini_program_info)) {
            // 绑定新的小程序
            $callback = Request::instance()->domain() . '/' . $this->module . '/Menu/addonmenu?addons=miniProgramCallback';
            $auth_url = $this->wchat_open->auth($callback);
            $this->assign('auth_url', $auth_url);
            $this->assign('miniProgramManageUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniProgram/miniProgramManage')));
            $this->assign('miniProgramAuth', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniProgram/miniProgramAuth')));
            $this->assign('commitUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/commitMp')));
            $this->fetch('template/' . $this->module . '/miniProgramAuth');
        } else {
            $addons_config_model = new AddonsConfigModel();
            $addons_setting = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => self::$addons_name]);
            $mini_program_info['is_use'] = $addons_setting['is_use'] ?: 0;
            // 调接口获取类目
            $category_list = $this->mini_program_service->getMpCategoryForCommit($mini_program_info['authorizer_access_token']);
            $weixin_auth_model = new WeixinAuthModel();
            $weixin_auth_model->save(['category' => $category_list],['website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
            $mini_program_info['category_array'] = json_decode($category_list, true);
            $this->assign('base_info', $mini_program_info);
            $this->assign('mp_info', $mini_program_info);
            $callback = Request::instance()->domain() . '/' . $this->module . '/Menu/addonmenu?addons=miniProgramCallback';
            $auth_url = $this->wchat_open->auth($callback);
            $this->assign('auth_url', $auth_url);

            $config_service = new ConfigService();
            $wx_set = $config_service->getWpayConfig($this->website_id)['value'];
            $this->assign("wx",$wx_set);
            $pay_info = $config_service->getConfigNew(['website_id' => $this->website_id, 'instance_id' => $this->instance_id, 'key' => 'MPPAY']);
            $pay_info['value_array'] = $pay_info['value'] ? json_decode($pay_info['value'], true) : [];
            $this->assign('pay_info', $pay_info);
            $this->assign('saveMpPayUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://Miniprogram/saveMpPay')));
            $this->assign('addMtRelationUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://Miniprogram/addMessageTemplateRelation')));
            $this->assign('addMtRelationKeyUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://Miniprogram/addMessageTemplateRelationKey')));
            $this->assign('deleteMtRelationUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://Miniprogram/deleteMessageTemplateRelation')));
            $this->assign('downSunCodeUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/downSunCode')));
            $this->assign('saveMiniProgramSettingUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://Miniprogram/miniProgramSetting')));
            $this->assign('getMpSettingUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://Miniprogram/getMpSetting')));
            $this->assign('getNewMpBaseInfoUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://Miniprogram/getNewMpBaseInfo')));
            $this->assign('postDomainToMpUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://Miniprogram/postDomainToMp')));

            $template_data = $this->mini_program_service->mpTemplateList(['shop_id' => 0, 'website_id' => 0]);
            $this->assign('template_list', $template_data);
            $this->assign('website_id', $this->website_id);
            $relation_list = $this->mini_program_service->relation(['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
            $this->assign('relation_list', $relation_list);
            $this->assign('sunCodeUrl', $mini_program_info['sun_code_url']);
            $this->assign('payConfigMirUrl', __URL(call_user_func('addons_url_' . $this->module,'miniprogram://Miniprogram/payConfigMir')));
            $this->assign('payWxConfigMirUrl', __URL(call_user_func('addons_url_' . $this->module,'miniprogram://Miniprogram/payWxConfigMir')));
            $this->assign('payBConfigMirUrl', __URL(call_user_func('addons_url_' . $this->module,'miniprogram://Miniprogram/payBConfigMir')));
            $this->assign('payDConfigMirUrl', __URL(call_user_func('addons_url_' . $this->module,'miniprogram://Miniprogram/payDConfigMir')));
			$this->assign('payGpConfigMirUrl', __URL(call_user_func('addons_url_' . $this->module,'miniprogram://Miniprogram/payGpConfigMir')));
            $this->fetch('template/' . $this->module . '/miniProgramSetting');			
        }

    }

    public function miniProgramCallback()
    {
        $auth_code = request()->get('auth_code');
        $expires_in = request()->get('expires_in');
        Session::set('auth_code', $auth_code, $expires_in);
        // 获取接口调用凭据和授权信息
        $auth_info = $this->wchat_open->get_query_auth($auth_code);
        $authorization_info = $auth_info->authorization_info;
        if ($authorization_info) {
            $auth_data['shop_id'] = $this->instance_id;
            $auth_data['authorizer_appid'] = $authorization_info->authorizer_appid;
            $auth_data['authorizer_refresh_token'] = $authorization_info->authorizer_refresh_token;
            $auth_data['authorizer_access_token'] = $authorization_info->authorizer_access_token;
            $auth_data['func_info'] = json_encode($authorization_info->func_info, true);
            $auth_data['update_token_time'] = time();
            $auth_data['uid'] = $this->uid;
            $auth_data['website_id'] = $this->website_id;

            // 基本信息（授权流程）
            $auth_base_info = $this->wchat_open->get_authorizer_info($auth_data['authorizer_appid']);
            $authorizer_info = $auth_base_info->authorizer_info;
            if ($authorizer_info) {
                $auth_data['nick_name'] = $authorizer_info->nick_name;
                $auth_data['head_img'] = $authorizer_info->head_img;
                $auth_data['user_name'] = $authorizer_info->user_name;
                $auth_data['alias'] = $authorizer_info->alias;
                $auth_data['qrcode_url'] = $authorizer_info->qrcode_url;
                $auth_data['signature'] = $authorizer_info->signature;
                $auth_data['category'] = json_encode($authorizer_info->MiniProgramInfo->categories, JSON_UNESCAPED_UNICODE);
                $auth_data['real_name_status'] = $authorizer_info->verify_type_info->id;
            }

            // 获取类目
            if (empty($authorizer_info->MiniProgramInfo->categories)) {
                $category_list = $this->mini_program_service->getMpCategoryList($auth_data['authorizer_access_token']);
                $auth_data['category'] = $category_list;
            }
            // 小程序授权时需要添加的一些额外参数
            $auth_data['new_auth_state'] = 1;// 授权就设置1，生成太阳码后改成0，为了标记新太阳码生成

            $app_id_data = $this->mini_program_service->miniProgramInfo(['authorizer_appid' => $auth_data['authorizer_appid']]);

            if ($app_id_data) {
                $this->mini_program_service->saveWeixinAuth($auth_data, ['authorizer_appid' => $auth_data['authorizer_appid']]);
            } else {

                $auth_data['auth_time'] = time();
                // 只存在一个小程序,当授权新的小程序时先删除旧的小程序
                $this->mini_program_service->weixinAuthCheck(['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
                $this->mini_program_service->saveWeixinAuth($auth_data);
            }

            // 获取太阳码
            $mini_program_controller =  new miniprogramController();
            $mini_program_controller->getLimitMpCode();
            // 添加域名到小程序
            $mini_program_controller->postDomainToMp();
            // 开启小程序商城
            $this->mini_program_service->openMpShop();

            setAddons('miniprogram', $this->website_id, $this->instance_id);
            // 删除session里的access_token and app_id
            Session::delete(['authorizer_access_token', 'app_id']);
            if ($this->module == 'platform') {

                $this->assign('getMpAppIdUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/getMiniProgramAppId')));
                $this->assign('saveMpAppSecretUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/saveMpAppSecret')));
                $this->fetch('template/' . $this->module . '/miniProgramConfigurate');

            } elseif ($this->module == 'admin') {
                header('location:' . __URL('ADDONS_ADMIN_MAINminiProgramSetting'));
            }
        }
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    /**
     * 处理微信只返回true/false的接口返回
     */
    public function wchatReturn($json)
    {
        if ($json->errcode == 0) {
            return ['code' => 1, 'message' => '操作成功'];
        } else {
            return ['code' => -1, 'message' => $json->errmsg];
        }
    }
}
