<?php
namespace addons\taskcenter;
use addons\Addons as Addo;
use addons\coupontype\model\VslCouponTypeModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\giftvoucher\server\GiftVoucher;
use addons\taskcenter\model\VslGeneralPosterModel;
use addons\taskcenter\model\VslGeneralTaskModel;
use addons\taskcenter\model\VslTaskcenterModel;
use data\model\AddonsConfigModel;
use \addons\taskcenter\service\Taskcenter AS taskcenterServer;
use data\model\AlbumPictureModel;
use data\model\VslGoodsModel;


class Taskcenter extends Addo
{
    public $info = array(
        'name' => 'taskcenter',//插件名称标识
        'title' => '任务中心',//插件中文名
        'description' => '制定任务让会员与商城更多交互稳定会员',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'no_set' => 1,//不需要应用设置
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'taskcenterList',//
        'config_admin_hook' => 'taskcenterList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1565403621.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1565403617.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1565403880.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '任务中心',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '制定任务让会员与商城更多交互稳定会员',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'generalTaskList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '普通任务',
            'parent_module_name' => '任务中心', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '普通任务分为“单次任务”与“周期任务”，单次任务会员只能领取一次，周期任务允许会员在任务完成以及任务失败再次领取。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'generalTaskList',
            'module' => 'platform'
        ],
        [
            'module_name' => '海报任务',
            'parent_module_name' => '任务中心', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '海报任务分为普通海报与多级海报，单级海报与多级海报区别在于多级海报可以添加多种推荐条件，与相对应的推荐奖励。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'posterTaskList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加任务',
            'parent_module_name' => '任务中心', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addGeneralTask',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑任务',
            'parent_module_name' => '任务中心', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'editGeneralTask',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加普通海报',
            'parent_module_name' => '任务中心', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addGeneralPoster',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑普通海报',
            'parent_module_name' => '任务中心', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'editGeneralPoster',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加多级海报',
            'parent_module_name' => '任务中心', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addMultilevelPoster',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑多级海报',
            'parent_module_name' => '任务中心', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'editMultilevelPoster',
            'module' => 'platform'
        ],
        [
            'module_name' => '任务扫描记录',
            'parent_module_name' => '任务中心',//上级模块名称 用来确定上级目录
            'sort' => 0,//上一个菜单名称 用来确定菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'generalPosterRecord',
            'module' => 'platform'
        ],
    ); // 钩子名称（需要该钩子调用的页面）

    public function __construct(){
        parent::__construct();
        $this->taskcenterServer = new taskcenterServer();
        $this->assign('website_id', $this->website_id);
        $this->assign('instance_id', $this->instance_id);
        $this->assign("pageshow", PAGESHOW);
//        var_dump(__URL(addons_url_platform('taskcenter://Taskcenter/isRepeatKeyword')));exit;
        if ($this->module == 'platform') {
            $this->assign('addGeneralTask', __URL(addons_url_platform('taskcenter://Taskcenter/addGeneralTask')));
            $this->assign('generalTaskList', __URL(addons_url_platform('taskcenter://Taskcenter/GeneralTaskList')));
            $this->assign('deleteGeneralPoster', __URL(addons_url_platform('taskcenter://Taskcenter/deleteGeneralPoster')));
            $this->assign('closeGeneralPoster', __URL(addons_url_platform('taskcenter://Taskcenter/closeGeneralPoster')));//关闭任务
            $this->assign('addPosterTask', __URL(addons_url_platform('taskcenter://Taskcenter/addPosterTask')));
            $this->assign('posterTaskList', __URL(addons_url_platform('taskcenter://Taskcenter/posterTaskList')));
            $this->assign('getGeneralPosterInfo', __URL(addons_url_platform('taskcenter://Taskcenter/getGeneralPosterInfo')));
            $this->assign('getMutillevelPosterInfo', __URL(addons_url_platform('taskcenter://Taskcenter/getMutillevelPosterInfo')));
            $this->assign('deletePoster', __URL(addons_url_platform('taskcenter://Taskcenter/deletePoster')));
            $this->assign('isRepeatKeyword', __URL(addons_url_platform('taskcenter://Taskcenter/isRepeatKeyword')));//关键字检查是否重复
            $this->assign('recordListUrl', __URL(addons_url_platform('taskcenter://Taskcenter/recordList')));//关键字检查是否重复
        }
    }

    /*
     * 普通任务列表
     * **/
    public function generalTaskList()
    {
        $this->fetch('template/' . $this->module . '/generalTaskList');
    }
    /*
     * 海报任务列表
     * **/
    public function posterTaskList()
    {
        $this->fetch('template/' . $this->module . '/posterTaskList');
    }
    /*
     * 添加普通任务
     * **/
    public function addGeneralTask()
    {
        //查出礼品券，优惠券
        if(getAddons('giftvoucher', $this->website_id)){
            //未过期的
            $gift_condition['start_time'] = ['<=', time()];
            $gift_condition['end_time'] = ['>', time()];
            $gift_condition['website_id'] = $this->website_id;
            $gift_voucher_mdl = new VslGiftVoucherModel();
            $gift_voucher_info = $gift_voucher_mdl->getQuery($gift_condition, 'gift_voucher_id, giftvoucher_name','');
        }
        if(getAddons('coupontype', $this->website_id)){
            $coupon_condition['start_time'] = ['<=', time()];
            $coupon_condition['end_time'] = ['>', time()];
            $coupon_condition['website_id'] = $this->website_id;
            $coupon_type_mdl = new VslCouponTypeModel();
            $coupon_type_info = $coupon_type_mdl->getQuery($coupon_condition,'coupon_type_id, coupon_name','');
        }
        $this->assign('gift_voucher_info', $gift_voucher_info);
        $this->assign('coupon_type_info', $coupon_type_info);
        $this->fetch('template/' . $this->module . '/addGeneralTask');
    }
    /*
     * 编辑普通任务
     * **/
    public function editGeneralTask()
    {
        $general_poster_id = request()->get('general_poster_id');
        //获取该普通任务的内容
        $general_poster_mdl = new VslGeneralPosterModel();
        $condition['general_poster_id'] = $general_poster_id;
        $condition['vsl_general_poster.task_type'] = 0;
        $condition['vsl_general_poster.website_id'] = $this->website_id;
        $general_poster_info = $general_poster_mdl::get($condition,['poster_reward','get_task_img']);
//        p($general_poster_info);exit;
        //获取图片
        $pic_cover = getApiSrc($general_poster_info['get_task_img']['pic_cover']);
        $general_poster_info['task_img_src'] = $pic_cover;
        //时间
        $general_poster_info['start_task_date'] = date('Y-m-d', $general_poster_info['start_task_time']);
        $general_poster_info['end_task_date'] = date('Y-m-d', $general_poster_info['end_task_time']);
        //查出礼品券，优惠券
        if(getAddons('giftvoucher', $this->website_id)){
            $gift_condition['start_time'] = ['<=', time()];
            $gift_condition['end_time'] = ['>', time()];
            $gift_condition['website_id'] = $this->website_id;
            $gift_voucher_mdl = new VslGiftVoucherModel();
            $gift_voucher_info = $gift_voucher_mdl->getQuery($gift_condition, 'gift_voucher_id, giftvoucher_name','');
        }else{
            $gift_voucher_info = '';
        }
        if(getAddons('coupontype', $this->website_id)){
            $coupon_condition['start_time'] = ['<=', time()];
            $coupon_condition['end_time'] = ['>', time()];
            $coupon_condition['website_id'] = $this->website_id;
            $coupon_type_mdl = new VslCouponTypeModel();
            $coupon_type_info = $coupon_type_mdl->getQuery($coupon_condition,'coupon_type_id, coupon_name','');
        }else{
            $coupon_type_info = '';
        }
        //任务规则获取商品id
        $task_rule = $general_poster_info['poster_reward'][0]['task_rule'];
        $task_rule_arr = json_decode($task_rule,true);
        $goods_id = $task_rule_arr['goods_id'];
        if($goods_id){
            //获取商品名字
            $goods_mdl = new VslGoodsModel();
            $goods_name = $goods_mdl->getInfo(['goods_id'=>$goods_id], 'goods_name')['goods_name'];
//            echo $goods_mdl->getLastSql();
//            var_dump($goods_name);
            $this->assign('goods_name', $goods_name);
        }
        $poster_reward_arr = $general_poster_info['poster_reward'][0];
        $this->assign('gift_voucher_info', $gift_voucher_info);
        $this->assign('poster_reward_arr', $poster_reward_arr);
        $this->assign('coupon_type_info', $coupon_type_info);
        $this->assign('task_info', $general_poster_info);
        $this->assign('task_rule', $task_rule_arr);
        $this->assign('general_poster_id', $general_poster_id);
        $this->fetch('template/' . $this->module . '/addGeneralTask');
    }
    /*
     * 添加普通海报
     * **/
    public function addGeneralPoster()
    {
        //查出礼品券，优惠券
        $gift_voucher_mdl = new VslGiftVoucherModel();
        $coupon_type_mdl = new VslCouponTypeModel();
        $condition['start_time'] = ['<=', time()];
        $condition['end_time'] = ['>', time()];
        $condition['website_id'] = $this->website_id;
        $gift_voucher_info = $gift_voucher_mdl->getQuery($condition, 'gift_voucher_id, giftvoucher_name','');
        $coupon_type_info = $coupon_type_mdl->getQuery($condition,'coupon_type_id, coupon_name','');
        $this->assign('gift_voucher_info', $gift_voucher_info);
        $this->assign('coupon_type_info', $coupon_type_info);
        $this->fetch('template/' . $this->module . '/addGeneralPoster');
    }
    /*
     * 编辑普通海报
     * **/
    public function editGeneralPoster()
    {
        //查出礼品券，优惠券
        $gift_voucher_mdl = new VslGiftVoucherModel();
        $coupon_type_mdl = new VslCouponTypeModel();
        $condition['start_time'] = ['<=', time()];
        $condition['end_time'] = ['>', time()];
        $condition['website_id'] = $this->website_id;
        if(getAddons('giftvoucher',$this->website_id)){
            $gift_voucher_info = $gift_voucher_mdl->getQuery($condition, 'gift_voucher_id, giftvoucher_name','');
        }else{
            $gift_voucher_info = '';
        }
        if(getAddons('coupontype',$this->website_id)){
            $coupon_type_info = $coupon_type_mdl->getQuery($condition,'coupon_type_id, coupon_name','');
        }else{
            $coupon_type_info = '';
        }
        $this->assign('gift_voucher_info', $gift_voucher_info);
        $this->assign('coupon_type_info', $coupon_type_info);
        $general_poster_id = request()->get('general_poster_id');
        $this->assign('general_poster_id', $general_poster_id);
        $this->fetch('template/' . $this->module . '/addGeneralPoster');
    }
    /*
     * 添加多级海报
     * **/
    public function addMultilevelPoster()
    {
        //查出礼品券，优惠券
        $gift_voucher_mdl = new VslGiftVoucherModel();
        $coupon_type_mdl = new VslCouponTypeModel();
        $condition['start_time'] = ['<=', time()];
        $condition['end_time'] = ['>', time()];
        $condition['website_id'] = $this->website_id;
        if(getAddons('giftvoucher',$this->website_id)){
            $gift_voucher_info = $gift_voucher_mdl->getQuery($condition, 'gift_voucher_id, giftvoucher_name','');
            $gift_voucher_info = json_encode($gift_voucher_info);
        }else{
            $gift_voucher_info = '';
        }
        if(getAddons('coupontype',$this->website_id)){
            $coupon_type_info = $coupon_type_mdl->getQuery($condition,'coupon_type_id, coupon_name','');
            $coupon_type_info = json_encode($coupon_type_info);
        }else{
            $coupon_type_info = '';
        }
        $this->assign('gift_voucher_info', $gift_voucher_info);
        $this->assign('coupon_type_info', $coupon_type_info);
        $this->fetch('template/' . $this->module . '/addMultilevelPoster');
    }
    /*
     * 编辑多级海报
     * **/
    public function editMultilevelPoster()
    {
        //查出礼品券，优惠券
        $gift_voucher_mdl = new VslGiftVoucherModel();
        $coupon_type_mdl = new VslCouponTypeModel();
        $condition['start_time'] = ['<=', time()];
        $condition['end_time'] = ['>', time()];
        $condition['website_id'] = $this->website_id;
        if(getAddons('giftvoucher',$this->website_id)){
            $gift_voucher_info = $gift_voucher_mdl->getQuery($condition, 'gift_voucher_id, giftvoucher_name','');
            $gift_voucher_info = json_encode($gift_voucher_info);
        }else{
            $gift_voucher_info = '';
        }
        if(getAddons('coupontype',$this->website_id)){
            $coupon_type_info = $coupon_type_mdl->getQuery($condition,'coupon_type_id, coupon_name','');
            $coupon_type_info = json_encode($coupon_type_info);
        }else{
            $coupon_type_info = '';
        }
        $this->assign('gift_voucher_info', $gift_voucher_info);
        $this->assign('coupon_type_info', $coupon_type_info);
        $general_poster_id = request()->get('general_poster_id');
        $this->assign('general_poster_id', $general_poster_id);
        $this->fetch('template/' . $this->module . '/addMultilevelPoster');
    }

    public function generalPosterRecord()
    {
        $this->assign('poster_id', input('get.poster_id'));
        $this->fetch('template/' . $this->module . '/generalPosterRecord');
    }

    public function run(){

    }

    /*
     * 安装方法
     */
    public function install()
    {
        // TODO: Implement install() method.

        return true;
    }

    /*
     * 卸载方法
     */
    public function uninstall()
    {

        return true;
        // TODO: Implement uninstall() method.
    }

}