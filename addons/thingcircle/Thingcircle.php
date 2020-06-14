<?php
namespace addons\thingcircle;

use addons\Addons;
use addons\thingcircle\model\ThingCircleModel;
use data\service\Goods as GoodsService;
use addons\thingcircle\model\ThingCircleTopicModel;
use addons\thingcircle\server\Thingcircle as ThingcircleServer;
use data\service\User;
use data\model\AlbumPictureModel;
use data\model\VslGoodsModel;

class Thingcircle extends Addons
{
    //sys_addons
    public $info = array(
        'name' => 'thingcircle',//插件名称标识
        'title' => '好物圈',//插件中文名
        'description' => '好物圈',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'thingcircleList',//
        'config_admin_hook' => 'thingcircleList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1573552535.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1573552539.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1573552545.png',
    );//设置文件单独的钩子

    //sys_module
    public $menu_info = array(
        //platform
        [
            'module_name' => '好物圈',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '好物圈',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'thingcircleList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '干货列表',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'thingcircleList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加干货',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addThingcircle',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑干货',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'updateThingcircle',
            'module' => 'platform'
        ],
        [
            'module_name' => '干货详情',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'thingcircleDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '话题列表',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'topicList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加话题',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addThingcircleTopic',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑话题',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'updateThingcircleTopic',
            'module' => 'platform'
        ],
        [
            'module_name' => '违规评论',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 2,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'violationCommentsList',
            'module' => 'platform'
        ],
        [
            'module_name' => '违规类型',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 3,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'violationTypeList',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 4,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'baseSetting',
            'module' => 'platform'
        ],

        //admin
        [
            'module_name' => '好物圈',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '好物圈',//菜单描述
            'module_picture' => '',//图片（一般为空
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'thingcircleList',
            'module' => 'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '干货列表',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'thingcircleList',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加干货',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addThingcircle',
            'module' => 'admin'
        ],
        [
            'module_name' => '编辑干货',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'updateThingcircle',
            'module' => 'admin'
        ],
        [
            'module_name' => '干货详情',
            'parent_module_name' => '好物圈', //上级模块名称 确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'thingcircleDetail',
            'module' => 'admin'
        ]
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform') {
            $this->assign('thingcircleListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/thingcircleList')));
            $this->assign('delThingcircleUrl', __URL(addons_url_platform('thingcircle://Thingcircle/delThingcircle')));
            $this->assign('thingcircleSettingUrl', __URL(addons_url_platform('thingcircle://Thingcircle/thingcircleSetting')));
            $this->assign('addThingcircleTopicUrl', __URL(addons_url_platform('thingcircle://Thingcircle/addThingcircleTopic')));
            $this->assign('changeTopicStateUrl', __URL(addons_url_platform('thingcircle://Thingcircle/changeTopicState')));
            $this->assign('updateThingcircleTopicUrl', __URL(addons_url_platform('thingcircle://Thingcircle/updateThingcircleTopic')));
            $this->assign('delThingcircleTopicUrl', __URL(addons_url_platform('thingcircle://Thingcircle/delThingcircleTopic')));
            $this->assign('thingcircleViolationListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/thingcircleViolationList')));
            $this->assign('addThingcircleViolationUrl', __URL(addons_url_platform('thingcircle://Thingcircle/addThingcircleViolation')));
            $this->assign('changeThingcircleViolationNameUrl', __URL(addons_url_platform('thingcircle://Thingcircle/changeThingcircleViolationName')));
            $this->assign('changeThingcircleViolationSortUrl', __URL(addons_url_platform('thingcircle://Thingcircle/changeThingcircleViolationSort')));
            $this->assign('deleteThingcircleViolationUrl', __URL(addons_url_platform('thingcircle://Thingcircle/deleteThingcircleViolation')));
            $this->assign('changeThingcircleViolationShowUrl', __URL(addons_url_platform('thingcircle://Thingcircle/changeThingcircleViolationShow')));
            $this->assign('thingcircleViolationCommentListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/thingcircleViolationCommentList')));
            $this->assign('delThingcircleCommentUrl', __URL(addons_url_platform('thingcircle://Thingcircle/delThingcircleComment')));
            $this->assign('ignThingcircleCommentUrl', __URL(addons_url_platform('thingcircle://Thingcircle/ignThingcircleComment')));
            $this->assign('deleteThingcircleCommentUrl', __URL(addons_url_platform('thingcircle://Thingcircle/deleteThingcircleComment')));
            $this->assign('recThingcircleCommentUrl', __URL(addons_url_platform('thingcircle://Thingcircle/recThingcircleComment')));
            $this->assign('getCommentListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getCommentList')));
            $this->assign('getReplyListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getReplyList')));
            $this->assign('selectTopicList', __URL(addons_url_platform('thingcircle://Thingcircle/selectTopicList')));
            $this->assign('addThingcircleUrl', __URL(addons_url_platform('thingcircle://Thingcircle/addThingcircle')));
            $this->assign('updateThingcircleUrl', __URL(addons_url_platform('thingcircle://Thingcircle/updateThingcircle')));
            //-----------------------------------------------------
            $this->assign('getThingcircleListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getThingcircleList')));
            $this->assign('getThingcircleUserUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getThingcircleUser')));
            $this->assign('attentionThingcircleUrl', __URL(addons_url_platform('thingcircle://Thingcircle/attentionThingcircle')));
            $this->assign('likesThingcircleUrl', __URL(addons_url_platform('thingcircle://Thingcircle/likesThingcircle')));
            $this->assign('likesThingcircleCommentUrl', __URL(addons_url_platform('thingcircle://Thingcircle/likesThingcircleComment')));
            $this->assign('collectionThingcircleUrl', __URL(addons_url_platform('thingcircle://Thingcircle/collectionThingcircle')));
            $this->assign('getThingcircleDetailUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getThingcircleDetail')));
            $this->assign('getThingcircleReplyUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getThingcircleReply')));
            $this->assign('pushThingcircleCommentUrl', __URL(addons_url_platform('thingcircle://Thingcircle/pushThingcircleComment')));
            $this->assign('replyThingcircleCommentUrl', __URL(addons_url_platform('thingcircle://Thingcircle/replyThingcircleComment')));
            $this->assign('attentionUserListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/attentionUserList')));
            $this->assign('fansUserListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/fansUserList')));
            $this->assign('getThingcircleMessageCenterUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getThingcircleMessageCenter')));
            $this->assign('getThingcircleMessageUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getThingcircleMessage')));
            $this->assign('getThingcircleLacUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getThingcircleLac')));
            $this->assign('getThingcircleCommentUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getThingcircleComment')));
            $this->assign('addThingcircleWapUrl', __URL(addons_url_platform('thingcircle://Thingcircle/addThingcircleWap')));
            $this->assign('addViolationUrl', __URL(addons_url_platform('thingcircle://Thingcircle/addViolation')));
            $this->assign('getViolationListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getViolationList')));
            $this->assign('getRecommendGoodsUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getRecommendGoods')));
            $this->assign('getTopicListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getTopicList')));
            $this->assign('getLowerTopicListUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getLowerTopicList')));
            $this->assign('getThingcircleVideoDetailUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getThingcircleVideoDetail')));
            $this->assign('getCommentUrl', __URL(addons_url_platform('thingcircle://Thingcircle/getComment')));
            $this->assign('updateThingcircleUrl', __URL(addons_url_platform('thingcircle://Thingcircle/updateThingcircle')));
            $this->assign('selectGoodsUrl', __URL(addons_url_platform('thingcircle://Thingcircle/selectGoods')));
        }else{
            $this->assign('thingcircleListUrl', __URL(addons_url_admin('thingcircle://Thingcircle/thingcircleList')));
            $this->assign('delThingcircleUrl', __URL(addons_url_admin('thingcircle://Thingcircle/delThingcircle')));
            $this->assign('addThingcircleUrl', __URL(addons_url_admin('thingcircle://Thingcircle/addThingcircle')));
            $this->assign('updateThingcircleUrl', __URL(addons_url_admin('thingcircle://Thingcircle/updateThingcircle')));
            $this->assign('selectTopicList', __URL(addons_url_admin('thingcircle://Thingcircle/selectTopicList')));
            $this->assign('selectGoodsUrl', __URL(addons_url_admin('thingcircle://Thingcircle/selectGoods')));
            $this->assign('delThingcircleCommentUrl', __URL(addons_url_admin('thingcircle://Thingcircle/delThingcircleComment')));
            $this->assign('getCommentListUrl', __URL(addons_url_admin('thingcircle://Thingcircle/getCommentList')));
            $this->assign('getReplyListUrl', __URL(addons_url_admin('thingcircle://Thingcircle/getReplyList')));
        }
    }

    public function thingcircleList()
    {
        $thing_server = new ThingcircleServer();
        $config = $thing_server->getThingcircleSite($this->website_id);
        
        if($config['uid']){
            $user = new User();
            $user_info = $user->getUserInfoByUid($config['uid']);
            if(!$user_info['thing_circle_uid']){
                $thing_uid = $thing_server->autoId();
                $user->addUserInfoThingcircleId($thing_uid,$user_info['uid']);
            }
            $this->assign('uid',$config['uid']);
        }

        $this->fetch('template/' . $this->module . '/thingcircleList');
    }

    public function topicList()
    {
        $thing_server = new ThingcircleServer();
        $setup = $thing_server->getThingcircleSite($this->website_id);
        $this->assign('setup',$setup);
        $topic_server = new ThingcircleServer();
        $topic_list = $topic_server->getTopicLists();
        if (! empty($topic_list)) {
            foreach ($topic_list as $k => $v) {
                $two_list = array();
                $two_list = $topic_server->getThingCircleTopicByParentId($v['topic_id']);
                $v['child_list'] = $two_list;
            }
        }
        $this->assign('topic_list',$topic_list);
        $this->fetch('template/' . $this->module . '/thingcircleTopicList');
    }

    public function baseSetting()
    {
        $thing_server = new ThingcircleServer();
        $list = $thing_server->getThingcircleSite($this->website_id);
        $user_info = [];
        if(!empty($list['uid'])){
            $user = new User();
            $user_info = $user->getUserInfoByUid($list['uid']);
            $user_info['user_headimg'] = __IMG($user_info['user_headimg']);
        }
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $prize['coupontype']['is_use'] = getAddons('coupontype', $this->website_id);
        $prize['giftvoucher']['is_use'] = getAddons('giftvoucher', $this->website_id);
        $prizelist = $thing_server->prizeList($condition);
        $prize['coupontype']['list'] = $prizelist['coupontype'];
        $prize['giftvoucher']['list'] = $prizelist['giftvoucher'];
        $this->assign('prize',$prize);
        $this->assign("user_info", $user_info);
        $this->assign("info", $list);
        $this->fetch('template/' . $this->module . '/thingcircleSetting');
    }

    public function addThingcircleTopic()
    {
        if(input('topic_id','')){
            $topic_id = input('topic_id','');
            $this->assign("topic_temp", $topic_id);
        }
        $thing_server = new ThingcircleServer();
        $config = $thing_server->getThingcircleSite($this->website_id);
        $this->assign("topic_state", $config['topic_state']);
        $topic_model = new ThingCircleTopicModel();
        $list = $topic_model->getThingCircleTopicByParentId(0);
        $this->assign("topic_list", $list);
        $this->fetch('template/' . $this->module . '/addThingcircleTopic');
    }

    public function updateThingcircleTopic()
    {
        $topic_id = input('topic_id','');
        $thing_server = new ThingcircleServer();
        $config = $thing_server->getThingcircleSite($this->website_id);
        $this->assign("topic_state", $config['topic_state']);
        $topic_model = new ThingCircleTopicModel();
        $list = $topic_model->getThingCircleTopicByParentId(0);
        $this->assign("topic_list", $list);
        $res = $topic_model->where(['topic_id' => $topic_id])->find();
        $this->assign("topic_info", $res);
        $this->fetch('template/' . $this->module . '/updateThingcircleTopic');
    }

    public function violationTypeList()
    {
        $thing_server = new ThingcircleServer();
        $config = $thing_server->getThingcircleSite($this->website_id);
        if(!$config){
            $thing_server->setDefultViolation();
        }

        $this->fetch('template/' . $this->module . '/thingcircleViolationList');
    }

    public function violationCommentsList()
    {
        $state = (int)input('get.state','');
        $this->assign('state',$state);
        $this->fetch('template/' . $this->module . '/thingcircleViolationCommentList');
    }

    public function addThingcircle()
    {
        $this->fetch('template/' . $this->module . '/updateThingcircle');
    }
    
    public function updateThingcircle()
    {
        $id = (int)input('get.id');
        $thing_model = new ThingCircleModel();
        $thing_info = $thing_model->getThingcircleById(['id'=>$id]);
        $recommend_goods_list = [];
        if($thing_info['recommend_goods']){
            $goods = new GoodsService();
            $list = $goods->getGoodsInfo(['goods_id'=>['in',$thing_info['recommend_goods']]]);
            foreach ($list as $k=>$v){
                $pic = new AlbumPictureModel();
                $recommend_goods_list[$k]['pic'] = __IMG($pic->getInfo(['pic_id'=>$v['picture']],'pic_cover_mid')['pic_cover_mid']);
                $recommend_goods_list[$k]['goods_name'] =$v['goods_name'];
                $recommend_goods_list[$k]['goods_id'] =$v['goods_id'];
                $recommend_goods_list[$k]['price'] = $v['price'];
            }
        }
        $this->assign('recommend_goods_list',$recommend_goods_list);
        $this->assign('thing_info',$thing_info);
        $this->fetch('template/' . $this->module . '/updateThingcircle');
    }

    public function editThingcircle()
    {
        $id = (int)input('get.id','');
        $thing_model = new ThingCircleModel();
        $res = $thing_model->where(['id' => $id])->find();
        $this->assign('thing_info',$res);
        $this->fetch('template/' . $this->module . '/');
    }

    public function thingcircleDetail()
    {
        $id = (int)input('get.id','');
        $page_index = (int)input('get.page_index',1);
        $page_size = (int)input('get.page_size',PAGESIZE);
        $map['id'] = $id;

        $thing_circle = new ThingCircleModel();
        $res = $thing_circle->getThingcircleById($map);
        $res['create_time'] = date("Y-m-d",$res['create_time']);
        $thing_server = new ThingcircleServer();
        /*$every_goods_info = $thing_server->getEveryGoodsInfo($res['recommend_goods']);
        $res['goods_img'] = getApiSrc($every_goods_info->pic_cover_small);*/
        $res['thing_user_name'] = ($res['nick_name']) ? $res['nick_name'] : ($res['user_name'] ? $res['user_name'] : ($res['user_tel'] ? $res['user_tel'] : $res['uid']));
        $res = json_decode($res,true);
        $res['goods_info'] = [];
        if($res['recommend_goods']){
            $res['goodsid'] = explode(',',$res['recommend_goods']);
            $goods = new VslGoodsModel();
            $goods_info = $goods->Query(['goods_id'=>['in',$res['recommend_goods']]],'goods_id,picture,goods_name,price');
            $res['goods_info'] = array();
            foreach ($goods_info as $k=>$v){
                $pic_id = $v['picture'];
                $pic = new AlbumPictureModel();
                $res['goods_info'][$k]['pic'] = __IMG($pic->getInfo(['pic_id'=>$pic_id],'pic_cover_mid')['pic_cover_mid']);
                $res['goods_info'][$k]['goods_name'] =$v['goods_name'];
                $res['goods_info'][$k]['goods_id'] =$v['goods_id'];
                $res['goods_info'][$k]['price'] = $v['price'];
            }
            $res['goods_info'] = array_values($res['goods_info']);
        }

        $this->assign('thing_info',$res);
        $this->assign('goods_info', json_encode($res['goods_info']));
        $this->fetch('template/' . $this->module . '/thingcircleDetail');
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