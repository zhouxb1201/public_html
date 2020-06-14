<?php
namespace addons\bargain;
use addons\Addons as Addo;
use addons\bargain\model\VslBargainModel;
use data\model\AddonsConfigModel;
use \addons\bargain\service\Bargain AS bargainServer;
use data\model\AlbumPictureModel;


class Bargain extends Addo
{
    public $info = array(
        'name' => 'bargain',//插件名称标识
        'title' => '砍价',//插件中文名
        'description' => '邀请好友砍价，病毒式宣传',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'bargainList',//
        'config_admin_hook' => 'bargainList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197071.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782121.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782243.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '砍价',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '邀请好友砍价，病毒式宣传',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'bargainList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '砍价列表',
            'parent_module_name' => '砍价', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '可设置固定砍价和随机砍价，会员可随时以砍价后的现价购买该商品，参加砍价的商品不能参与“限时折扣、秒杀、拼团、预售”等活动。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'bargainList',
            'module' => 'platform'
        ],
        [
            'module_name' => '活动详情',
            'parent_module_name' => '砍价', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'bargainDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '砍价',//上级模块名称 用来确定上级目录
            'sort' => 1,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '关闭后，所有砍价活动均不生效，可设置独立的分销分红规则，优先级为：商品独立>活动独立>分销/分红设置。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'bargainConfig',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加/编辑砍价',
            'parent_module_name' => '砍价',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addBargain',
            'module' => 'platform'
        ],
        [
            'module_name' => '砍价记录',
            'parent_module_name' => '砍价', // 上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'bargainRecord',
            'module' => 'platform',
        ],
        [
            'module_name' => '编辑',
            'parent_module_name' => '砍价', // 上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'editBargain',
            'module' => 'platform',
        ],
        //admin
        [
            'module_name' => '砍价',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '邀请好友砍价，病毒式宣传',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'bargainList',
            'module' => 'admin',
            'is_admin_main' => 1
        ],
        [
            'module_name' => '砍价列表',
            'parent_module_name' => '砍价', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '可设置固定砍价和随机砍价，会员可随时以砍价后的现价购买该商品，参加砍价的商品不能参与“限时折扣、秒杀、拼团、预售”等活动。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'bargainList',
            'module' => 'admin'
        ],
        [
            'module_name' => '活动详情',
            'parent_module_name' => '砍价', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'bargainDetail',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加砍价',
            'parent_module_name' => '砍价',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addBargain',
            'module' => 'admin'
        ],
        [
            'module_name' => '砍价记录',
            'parent_module_name' => '砍价', // 上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'bargainRecord',
            'module' => 'admin',
        ],
        [
            'module_name' => '编辑',
            'parent_module_name' => '砍价', // 上级模块名称 用来确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'editBargain',
            'module' => 'admin',
        ],

    ); // 钩子名称（需要该钩子调用的页面）

    public function __construct(){
        parent::__construct();
        $this->bargainServer = new bargainServer();
        $this->assign('website_id', $this->website_id);
        $this->assign('instance_id', $this->instance_id);
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform') {
            $this->assign('addBargainConfig', __URL(addons_url_platform('bargain://Bargain/addBargainConfig')));
            $this->assign('addBargain', __URL(addons_url_platform('bargain://Bargain/addBargain')));
            $this->assign('bargainListUrl', __URL(addons_url_platform('bargain://Bargain/bargainListUrl')));
            $this->assign('bargainRecordUrl', __URL(addons_url_platform('bargain://Bargain/bargainRecordUrl')));
            $this->assign('bargainClose', __URL(addons_url_platform('bargain://Bargain/bargainClose')));
            $this->assign('bargainDelete', __URL(addons_url_platform('bargain://Bargain/bargainDelete')));
        }else if ($this->module == 'admin') {
            $this->assign('addBargainConfig', __URL(addons_url_admin('bargain://Bargain/addBargainConfig')));
            $this->assign('addBargain', __URL(addons_url_admin('bargain://Bargain/addBargain')));
            $this->assign('bargainListUrl', __URL(addons_url_admin('bargain://Bargain/bargainListUrl')));
            $this->assign('bargainRecordUrl', __URL(addons_url_admin('bargain://Bargain/bargainRecordUrl')));
            $this->assign('bargainDialogGoodsList', __URL(addons_url_admin('bargain://Bargain/bargainDialogGoodsList')));
            $this->assign('bargainClose', __URL(addons_url_admin('bargain://Bargain/bargainClose')));
            $this->assign('bargainDelete', __URL(addons_url_admin('bargain://Bargain/bargainDelete')));
        }
    }

    /*
     * 砍价设置
     * **/
    public function bargainConfig()
    {
        $distributionStatus = getAddons('distribution', $this->website_id);
        $globalStatus = getAddons('globalbonus', $this->website_id);
        $areaStatus = getAddons('areabonus', $this->website_id);
        $teamStatus = getAddons('teambonus', $this->website_id);
        $this->assign('has_distribution', $distributionStatus);
        $this->assign('has_global', $globalStatus);
        $this->assign('has_area', $areaStatus);
        $this->assign('has_team', $teamStatus);
        $addons_config_model = new AddonsConfigModel();
        $addons_info = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => strtolower($this->info['name'])]);
        $addons_data = json_decode($addons_info['value'], true) ?: [];
        if($addons_data){
            $addons_data['is_use'] = $addons_info['is_use'] ? : 0;
            $addons_data['bonus_val'] = json_decode($addons_data['bonus_val'],true)?:[];
            $addons_data['distribution_val'] = json_decode($addons_data['distribution_val'],true)?:[];
        }
        $this->assign('addons_data', $addons_data);
        $this->fetch('template/' . $this->module . '/bargainConfig');
    }

    /*
     * 砍价列表
     * **/
    public function bargainList()
    {
        $time = time();
        //全部
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $bargain_count['all_num'] = $this->bargainServer->getBargainStatusCount($condition)?:0;
        //未开始
        $condition1['website_id'] = $this->website_id;
        $condition1['shop_id'] = $this->instance_id;
        $condition1['start_bargain_time'] = ['>', $time];
        $bargain_count['unstart_num'] = $this->bargainServer->getBargainStatusCount($condition1)?:0;
        //进行中
        $condition2['website_id'] = $this->website_id;
        $condition2['shop_id'] = $this->instance_id;
        $condition2['start_bargain_time'] = ['<', $time];
        $condition2['end_bargain_time'] = ['>', $time];
        $bargain_count['going_num'] = $this->bargainServer->getBargainStatusCount($condition2)?:0;
       //已结束
        $condition3['website_id'] = $this->website_id;
        $condition3['shop_id'] = $this->instance_id;
        $condition3['end_bargain_time'] = ['<', $time];
        $bargain_count['ended_num'] = $this->bargainServer->getBargainStatusCount($condition3)?:0;
        $this->assign('bargain_count', $bargain_count);
        $this->fetch('template/' . $this->module . '/bargainList');
    }

    /*
     *砍价详情
     * **/
    public function bargainDetail()
    {
        //查询出活动内容
        $bargain_id = request()->get('bargain_id');
        $bargain_detail = $this->bargainServer->getBargainDetail($bargain_id);
        if($bargain_detail){
            $bargain_detail['pic_cover'] = getApiSrc($bargain_detail['pic_cover']);
            $bargain_detail['start_bargain_time_format'] = date('Y-m-d H:i:s', $bargain_detail['start_bargain_time']);
            $bargain_detail['end_bargain_time_format'] = date('Y-m-d 23:59:59', $bargain_detail['end_bargain_time']);
            if($bargain_detail['end_bargain_time'] < time()){
                $bargain_detail['bargain_status'] = 3;//已结束
            }elseif($bargain_detail['end_bargain_time']>time() && $bargain_detail['start_bargain_time']<time()){
                $bargain_detail['bargain_status'] = 2;//已开始
            }else{
                $bargain_detail['bargain_status'] = 1;//未开始
            }
        }
        $this->assign('bargain_detail', $bargain_detail);
        $this->fetch('template/' . $this->module . '/bargainDetail');
    }
    /*
     * 砍价记录
     * **/
    public function bargainRecord()
    {
        //获取bargain_id
        $bargain_id = request()->get('bargain_id');
        //获取砍价状态 砍价中
        $condition1['b.website_id'] = $this->website_id;
        $condition1['b.shop_id'] = $this->instance_id;
        $condition1['b.bargain_id'] = $bargain_id;
        $condition1['b.end_bargain_time'] = ['>=',time()];
        $condition1['br.bargain_status'] = 1;
        $going_count = $this->bargainServer->getBargainCount($condition1);
        $bargain_status_arr['going_count'] = $going_count;
        //已支付
        $condition2['b.website_id'] = $this->website_id;
        $condition2['b.shop_id'] = $this->instance_id;
        $condition2['b.bargain_id'] = $bargain_id;
        $condition2['br.bargain_status'] = 2;
        $pay_count = $this->bargainServer->getBargainCount($condition2);
        $bargain_status_arr['pay_count'] = $pay_count;
        //已过期 失败
        $condition3['b.website_id'] = $this->website_id;
        $condition3['b.shop_id'] = $this->instance_id;
        $condition3['b.bargain_id'] = $bargain_id;
        $condition3['b.end_bargain_time'] = ['<',time()];
        $condition3['br.bargain_status'] = 1;
        $fail_count = $this->bargainServer->getBargainCount($condition3);
        $bargain_status_arr['fail_count'] = $fail_count;
        $this->assign('bargain_id',$bargain_id);
        $this->assign('bargain_status_arr',$bargain_status_arr);
        $this->fetch('template/' . $this->module . '/bargainRecord');
    }

    /*
     * 添加砍价
     * **/
    public function addBargain()
    {
        $bargain_id = request()->get('bargain_id',0);
        $batgain_mdl = new VslBargainModel();
        $album_pic_mdl = new AlbumPictureModel();
        $bargain_info = $batgain_mdl->getInfo(['bargain_id'=>$bargain_id],'*');
        $pic_cover = getApiSrc($album_pic_mdl->getInfo(['pic_id'=>$bargain_info['picture']],'pic_cover')['pic_cover']);
        $bargain_info['pic_cover'] = $pic_cover;
        $bargain_info['start_bargain_date'] = date('Y-m-d', $bargain_info['start_bargain_time']);
        $bargain_info['end_bargain_date'] = date('Y-m-d', $bargain_info['end_bargain_time']);
        $this->assign('bargain_id',$bargain_id);
        $this->assign('bargain_info',$bargain_info);
        $this->fetch('template/' . $this->module . '/addBargain');
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