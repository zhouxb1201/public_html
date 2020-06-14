<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\helpcenter;

use addons\Addons;
use addons\helpcenter\server\Helpcenter as helpServer;
use data\model\AddonsConfigModel;

class Helpcenter extends Addons {

    public $info = array(
        'name' => 'helpcenter', //插件名称标识
        'title' => '帮助中心', //插件中文名
        'description' => '完善商城说明，用户协议', //插件描述
        'status' => 1, //状态 1使用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', //版本号
        'has_addonslist' => 1, //是否有下级插件
        'content' => '', //插件的详细介绍或者使用方法
        'config_hook' => 'questionList', //
        'no_set' => 1,
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197004.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782083.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782215.png',
    ); //设置文件单独的钩子
    public $menu_info = array(
        //platform
        [
            'module_name' => '帮助中心',
            'parent_module_name' => '应用', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '完善商城说明，用户协议', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'questionList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '问题列表',
            'parent_module_name' => '帮助中心', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0, // 是否为开发者模式可见
            'desc' => '编写帮助中心文章，让会员更好的理解你的商城体系。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'questionList',
            'module' => 'platform'
        ],
        [
            'module_name' => '修改问题',
            'parent_module_name' => '帮助中心', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'updateQuestion',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加问题',
            'parent_module_name' => '帮助中心', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addQuestion',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除问题',
            'parent_module_name' => '帮助中心', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'deleteQuestion',
            'module' => 'platform'
        ],
        [
            'module_name' => '问题分类',
            'parent_module_name' => '帮助中心', //上级模块名称 用来确定上级目录
            'sort' => 1, //菜单排序
            'is_menu' => 1, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '合理把文章分类，让客户在查找帮助中心的时候更清晰。', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'questionCateList',
            'module' => 'platform'
        ],
        [
            'module_name' => '删除分类',
            'parent_module_name' => '帮助中心', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'deleteCate',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加分类',
            'parent_module_name' => '帮助中心', //上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, //是否为菜单
            'is_dev' => 0, //是否是开发模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片（一般为空）
            'icon_class' => '', //字体图标class（一般为空）
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addCate',
            'module' => 'platform'
        ],
    );

    public function __construct() {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
    }
    /*
     * 问题列表
     */
    public function questionList() {
        $this->assign('questionListUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/questionList')));
        $this->assign('deleteQuestionUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/deleteQuestion')));
        $this->assign('changeQuestionShowUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/changeQuestionShow')));
        $this->assign('changeQuestionSortUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/changeQuestionSort')));
        $this->fetch('template/' . $this->module . '/questionList');
    }

    /*
     * 添加问题
     */
    public function addQuestion() {
        $helpcenterServer = new helpServer();
        $questionCateList = $helpcenterServer->questionCateList(1, 0, ['website_id' => $this->website_id, 'status' => 1], 'sort asc');
        $this->assign('questionCateList', $questionCateList);
        $this->assign('addQuestionUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/addQuestion')));
        $this->fetch('template/' . $this->module . '/addQuestion');
    }

    /*
     * 修改问题
     */
    public function updateQuestion() {
        $questionId = request()->get('question_id');
        $helpcenterServer = new helpServer();
        $questionDetail = $helpcenterServer->questionDetail($questionId);
        $questionCateList = $helpcenterServer->questionCateList(1, 0, ['website_id' => $this->website_id, 'status' => 1], 'sort asc');
        $this->assign('questionCateList', $questionCateList);
        $this->assign('questionId', $questionId);
        $this->assign('questionDetail', $questionDetail);
        $this->assign('updateQuestionUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/updateQuestion')));
        $this->fetch('template/' . $this->module . '/updateQuestion');
    }

    /*
     * 问题分类列表
     */
    public function questionCateList() {
        $this->assign('questionCateListUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/questionCateList')));
        $this->assign('deleteCateUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/deleteCate')));
        $this->assign('changeCateSortUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/changeCateSort')));
        $this->assign('addCateUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/addCate')));
        $this->assign('changeCateNameUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/changeCateName')));
        $this->assign('changeCateShowUrl', __URL(call_user_func('addons_url_' . $this->module, 'helpcenter://Helpcenter/changeCateShow')));
        $this->fetch('template/' . $this->module . '/questionCateList');
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
