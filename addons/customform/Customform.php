<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\customform;

use addons\Addons;
use addons\customform\model\VslCustomModel;
use addons\customform\model\VslCustomSettingModel;
use addons\customform\model\VslCustomTagModel;
use addons\customform\server\Custom as CustomServer;
use addons\customform\server\Custom;
use data\model\ConfigModel;
use data\model\AddonsConfigModel;
use data\model\WeixinQrcodeTemplateModel;
use data\service\Upload\AliOss;
use data\service\User;
use think\Db;

class Customform extends Addons
{
    public $info = array(
        'name' => 'customform',//插件名称标识
        'title' => '自定义表单',//插件中文名
        'description' => '高效灵活收集信息',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'no_set' => 1,//不需要应用设置
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'customFormList',//
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197140.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782200.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782321.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '自定义表单',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '高效灵活收集信息',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'customFormList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '模板列表',
            'parent_module_name' => '自定义表单', //上级模块名称 确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '商家可根据自身情况制定会员填写的表单模版，采集会员信息。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'customFormList',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑表单',
            'parent_module_name' => '自定义表单',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '编辑表单',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateCustomForm',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加模板',
            'parent_module_name' => '自定义表单',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '添加模板',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addCustomForm',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除模板',
            'parent_module_name' => '自定义表单',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除模板',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteCustomForm',
            'module' => 'platform'
        ],
        [
            'module_name' => '模板详情',
            'parent_module_name' => '自定义表单',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'customFormInfo',
            'module' => 'platform'
        ],
        [
            'module_name' => '模板标签',
            'parent_module_name' => '自定义表单',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '标签主要用于给表单模版进行分类。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'customFormTagList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加标签',
            'parent_module_name' => '自定义表单',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '添加标签',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addCustomFormTag',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除标签',
            'parent_module_name' => '自定义表单',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '删除标签',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'deleteCustomFormTag',
            'module' => 'platform'
        ],
        [
            'module_name' => '自定义表单的数据',
            'parent_module_name' => '自定义表单',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '自定义表单的数据',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'customData',
            'module' => 'platform'
        ],
        [
            'module_name' => '模板设置',
            'parent_module_name' => '自定义表单',//上级模块名称 用来确定上级目录
            'last_module_name' => '',//上一个菜单名称 用来确定菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '支持设置自定义表单的模块有确认订单、会员资料、分销商申请、股东申请、区域代理申请、队长申请、渠道商申请。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'customFormSetting',
            'module' => 'platform'
        ],
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        $this->assign('website_id', $this->website_id);
        if ($this->module == 'platform') {
            $this->assign('getCustomFormUrl', __URL(addons_url_platform('customform://Customform/getCustomForm')));
            $this->assign('getCustomFormInfo', __URL(addons_url_platform('customform://Customform/getCustomFormInfo')));
            $this->assign('customFormListUrl', __URL(addons_url_platform('customform://Customform/customFormList')));
            $this->assign('addCustomFormUrl', __URL(addons_url_platform('customform://Customform/addCustomForm')));
            $this->assign('updateCustomFormUrl', __URL(addons_url_platform('customform://Customform/updateCustomForm')));
            $this->assign('deleteCustomFormUrl', __URL(addons_url_platform('customform://Customform/deleteCustomForm')));
            $this->assign('customFormTagListUrl', __URL(addons_url_platform('customform://Customform/customFormTagList')));
            $this->assign('addCustomFormTagUrl', __URL(addons_url_platform('customform://Customform/addCustomFormTag')));
            $this->assign('deleteCustomFormTagUrl', __URL(addons_url_platform('customform://Customform/deleteCustomFormTag')));
            $this->assign('saveCustomFormSettingUrl', __URL(addons_url_platform('customform://Customform/customFormSetting')));
            $this->assign('getProvinceUrl', __URL(addons_url_platform('customform://Customform/getProvince')));
            $this->assign('getCityUrl', __URL(addons_url_platform('customform://Customform/getCity')));
            $this->assign('getDistrictUrl', __URL(addons_url_platform('customform://Customform/getDistrict')));
            $this->assign('getCustomDataUrl', __URL(addons_url_platform('customform://Customform/getCustomData')));
            $this->assign('customDataExcelUrl', __URL(addons_url_platform('customform://Customform/customDataExcel')));
        }
    }

    public function customFormList()
    {
        $custom_form = new VslCustomModel();

        $all = $custom_form->all(['custom_tag.website_id'=>$this->website_id],['custom_tag']);

        $custom_setting = new AddonsConfigModel();
        $set_data = $custom_setting->getInfo(['website_id'=>$this->website_id,'addons'=>'customform'],'*');
        $data = [
            'custom_forms' => $all,
            'set_data' => json_decode($set_data['value'])
        ];

        $this->assign('data',$data);
        $this->fetch('template/' . $this->module . '/customFormList');
    }

    public function addCustomForm()
    {

        $custom_tags = new VslCustomTagModel();
        $all = $custom_tags->getQuery(['website_id'=>$this->website_id],'*','');
        $this->assign('custom_tags', $all);
        $this->fetch('template/' . $this->module . '/addCustomForm');
    }

    public function updateCustomForm()
    {
        $custom_form_id = $_GET['custom_form_id'];
        $type = $_GET['type']?$_GET['type']:0;
        $this->assign('type', $type);
        $custom_tags = new VslCustomTagModel();
        $all = $custom_tags->getQuery(['website_id'=>$this->website_id],'*','');
        $coupon_model = new CustomServer();
        $custom_form_info = $coupon_model->getCustomFormDetail($custom_form_id);
        $data = [
            'all' => $all,
            'custom_form_info' => json_decode($custom_form_info)
        ];
        $this->assign('data', $data);
        $this->assign('custom_form_id', $custom_form_id);
        $this->fetch('template/' . $this->module . '/updateCustomForm');
    }

    public function customFormSetting()
    {
        $configModel = new AddonsConfigModel();
        $custom_info = $configModel->getInfo([
            'addons' => 'customform',
            'website_id' => $this->website_id
        ],'value');

        $custom_form = new VslCustomModel();
        $all = $custom_form->getQuery(['website_id'=>$this->website_id],'*','');
        
        $data = [
            'all' => $all,
            'custom_info' => json_decode($custom_info['value']),
            'distributionStatus' =>   getAddons('distribution', $this->website_id),
            'globalbonus' =>   getAddons('globalbonus', $this->website_id),
            'areabonus' =>   getAddons('areabonus', $this->website_id),
            'teambonus' =>   getAddons('teambonus', $this->website_id),
            'channel' =>   getAddons('channel', $this->website_id),
            'shopStatus' =>   getAddons('shop', $this->website_id),
        ];

        $this->assign('all', $data);
        $this->fetch('template/' . $this->module . '/customerFormSetting');
    }

    public function customFormTagList()
    {
        $custom_tags = new VslCustomTagModel();
        $all = $custom_tags->getQuery(['website_id'=>$this->website_id],'*','');
        
        $this->assign('custom_tags',$all);
        $this->fetch('template/' . $this->module . '/customFormTagList');
    }

    public function addCustomFormTag()
    {
        $this->assign('custom_form_info', json_encode(''));
        $this->fetch('template/' . $this->module . '/addCustomFormTag');
    }

    public function customData()
    {
        $Custom_model = new CustomServer();
        $custom_id = $_GET['custom_form_id'];
        $custom_form_info = $Custom_model->getCustomFormDetail($custom_id);
        $this->assign('custom_id',$custom_id);
        $this->assign('custom_data', json_decode($custom_form_info['value'],true));
        if($custom_form_info['usage']){
            $usage = explode(',',$custom_form_info['usage']);
            $custom_usage = $usage[0];
            $count = $Custom_model->getCustomData(1,0,$custom_usage);
            $this->assign('count_num',count($count['data']));
            $this->assign('usage_num',$usage);
            $this->assign('custom_usage',$custom_usage);
        }
        $this->fetch('template/' . $this->module . '/customData');
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