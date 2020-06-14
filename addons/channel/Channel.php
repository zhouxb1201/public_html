<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\channel;

use addons\Addons;
use addons\channel\model\VslChannelLevelModel;
use addons\channel\model\VslChannelModel;
use addons\channel\model\VslChannelOrderModel;
use addons\channel\server\Channel as channelServer;
use data\model\ConfigModel;
use data\model\AddonsConfigModel;
use data\service\Goods as goodsServer;
use data\service\Member as MemberService;
use think\Db;

class Channel extends Addons
{
    public $info = array(
        'name' => 'channel',//插件名称标识
        'title' => '微商系统',//插件中文名
        'description' => '虚拟库存，人、货、钱一体化管理',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'channelList',//
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197107.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782161.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782287.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '微商系统',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '虚拟库存，人、货、钱一体化管理',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'channelList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '渠道商列表',
            'parent_module_name' => '微商系统', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '渠道商可以低价采购商品虚拟库存，通过销售或下级采购可赚取差价，当下级与你平级甚至比你高级时采购商品你可获得相应的平级奖或跨级奖。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'channelList',
            'module' => 'platform'
        ],
        [
            'module_name' => '渠道商详情',
            'parent_module_name' => '微商系统', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'channelDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '采购列表',
            'parent_module_name' => '微商系统', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'purchaseList',
            'module' => 'platform'
        ],
        [
            'module_name' => '采购记录列表',
            'parent_module_name' => '微商系统', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'purchaseRecordList',
            'module' => 'platform'
        ],
        [
            'module_name' => '下级渠道商',
            'parent_module_name' => '微商系统', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'downChannelList',
            'module' => 'platform'
        ],
        [
            'module_name' => '渠道商等级',
            'parent_module_name' => '微商系统', //上级模块名称 确定上级目录
            'sort' => 1, // 菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '渠道商可设置不同等级享受不同的采购价格，权重越大等级越高。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'channelGradeList',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加渠道商等级',
            'parent_module_name' => '微商系统',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'updateChannelGrade',
            'module' => 'platform'
        ],
        [
            'module_name' => '采购订单',
            'parent_module_name' => '微商系统',//上级模块名称 用来确定上级目录
            'sort' => 2,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '查看所有采购订单。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'purchaseOrder',
            'module' => 'platform'
        ],
        [
            'module_name' => '采购订单详情',
            'parent_module_name' => '微商系统', //上级模块名称 确定上级目录
            'sort' => 0, // 菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'purchaseOrderDetail',
            'module' => 'platform'
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '微商系统',//上级模块名称 用来确定上级目录
            'sort' => 3,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '可设置成为渠道商条件，平级模式（最高三级）与成为渠道商的申请协议。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'channelSetting',
            'module' => 'platform'
        ],
        [
            'module_name' => '申请协议',
            'parent_module_name' => '微商系统',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'channelAgreement',
            'module' => 'platform'
        ],
        [
            'module_name' => '渠道商自动降级',
            'parent_module_name' => '微商系统',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'autoDownChannelAgentLevel',
            'module' => 'platform'
        ],
        [
            'module_name' => '货款流水',
            'parent_module_name' => '财务', // 上级模块名称 用来确定上级目录
            'sort' => 11, // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '货款充值、支付都会产生记录，当账目有出入时可通过流水追溯原由。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'proceedsList'
        ],
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign('website_id', $this->website_id);
        $this->assign('instance_id', $this->instance_id);
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform' || $this->module == 'platform_new') {
            $this->assign('addChannelSetting', __URL(addons_url_platform('channel://Channel/addChannelSetting')));
            $this->assign('channelAgreement', __URL(addons_url_platform('channel://Channel/channelAgreement')));
            $this->assign('updateChannelGrade', __URL(addons_url_platform('channel://Channel/updateChannelGrade')));
            $this->assign('channelGradeList', __URL(addons_url_platform('channel://Channel/channelGradeList')));
            $this->assign('channelList', __URL(addons_url_platform('channel://Channel/channelList')));
            $this->assign('purchaseList', __URL(addons_url_platform('channel://Channel/purchaseList')));
            $this->assign('channelCheckStatus', __URL(addons_url_platform('channel://Channel/channelCheckStatus')));
            $this->assign('updateChannelInfo', __URL(addons_url_platform('channel://Channel/updateChannelInfo')));
            $this->assign('purchaseRecordList', __URL(addons_url_platform('channel://Channel/purchaseRecordList')));
            $this->assign('removeChannel', __URL(addons_url_platform('channel://Channel/removeChannel')));
            $this->assign('deletaChannelGrade', __URL(addons_url_platform('channel://Channel/deletaChannelGrade')));
            $this->assign('purchaseOrderList', __URL(addons_url_platform('channel://Channel/purchaseOrderList')));
            $this->assign('dataExcel', __URL(addons_url_platform('channel://Channel/dataExcel')));
            $this->assign('proceedsList', __URL(addons_url_platform('channel://Channel/proceedsList')));
            $this->assign('proceedsDetail', __URL(addons_url_platform('channel://Channel/proceedsDetail')));
            $this->assign('proceedsDataExcel', __URL(addons_url_platform('channel://Channel/proceedsDataExcel')));
        }
    }
    /*
     * 渠道商列表
     * **/
    public function channelList()
    {
        $channel_sever = new channelServer();
        //插入默认的渠道商等级，权重为1
        $default['website_id'] = $this->website_id;
        $default['channel_grade_name'] = '默认等级';
        $default['create_time'] = time();
        $default['auto_upgrade'] = 1;
        $default['weight'] = 1;
        $default['purchase_discount'] = 1;
        $default['is_default'] = 1;
        $condition['website_id'] = $this->website_id;
        $condition['is_default'] = 1;
        $condition['weight'] = 1;
        $is_default_channel_grade = $channel_sever->getchannelGradeList($condition);
        if(!$is_default_channel_grade){
            $channel_sever->addChannelGrade($default);
        }
        //处理渠道商等级显示用于筛选
        $channel_grade_condition['website_id'] = $this->website_id;
        $channel_grade_list = $channel_sever->getchannelGradeList($channel_grade_condition);
        $this->assign('channel_grade_list', $channel_grade_list);
        //统计全部、已审核、未审核通过、待审核的渠道商数目
            //全部
        $channel_count['all_count'] = $channel_sever->getChannelStatusCount();
            //已审核
        $channel_count['checked_count'] = $channel_sever->getChannelStatusCount(['status'=>1]);
            //待审核
        $channel_count['uncheck_count'] = $channel_sever->getChannelStatusCount(['status'=>0]);
            //未审核通过
        $channel_count['nocheck_count'] = $channel_sever->getChannelStatusCount(['status'=>-1]);
        $this->assign('channel_count', $channel_count);
        $this->fetch('template/' . $this->module . '/channelList');
    }
   /*
    * 渠道商详情
    * **/
   public function channelDetail()
   {
       $channel_id = request()->get('channel_id');
       $channel_sever = new channelServer();
       $condition['c.channel_id'] = $channel_id;
       $channel_info = $channel_sever->getChannelDetail($condition);
       //获取渠道商等级
       $grade_condition['website_id'] = $this->website_id;
       $grade_list = $channel_sever->getchannelGradeList($grade_condition);
//       echo '<pre>';print_r($channel_info);exit;
       $this->assign('channel_info', $channel_info);
       $this->assign('grade_list', $grade_list);
       $this->fetch('template/' . $this->module . '/channelDetail');
   }
   /*
    * 采购列表
    * **/
    public function purchaseList()
    {
        $channel_id = request()->get('channel_id',0);
        $uid = request()->get('uid',0);
        $this->assign('channel_id',$channel_id);
        $this->assign('uid',$uid);
        $this->fetch('template/' . $this->module . '/purchaseList');
    }
    /*
     * 采购记录列表
     * **/
    public function purchaseRecordList(){
        $goods_id = request()->get('goods_id',0);
        $sku_id = request()->get('sku_id',0);
        $channel_id = request()->get('channel_id',0);
        $this->assign('goods_id',$goods_id);
        $this->assign('sku_id',$sku_id);
        $this->assign('channel_id',$channel_id);
        $this->fetch('template/' . $this->module . '/purchaseRecordList');
    }

    /*
    * 下级渠道商
    * **/
    public function downChannelList()
    {
//        var_dump($_REQUEST);exit;
        $page_status = request()->get('page_status');
        $channel_id = request()->get('channel_id');
        $uid = request()->get('uid');
        $this->assign('page_status',$page_status);
        $this->assign('channel_id',$channel_id);
        $this->assign('uid',$uid);
        $this->fetch('template/' . $this->module . '/channelList');
    }
    /*
     * 添加渠道商等级页面
     * **/
    public function updateChannelGrade()
    {
        $channel_grade_id = request()->get('channel_grade_id',0);
        //分配设置是否开启了平2级、平3级比例
        //查询出设置的信息
        $channel_sever = new channelServer();
        $channel_config = $channel_sever->getChannelConfig();
        $this->assign('channel_config', $channel_config);
        if(!empty($channel_grade_id)){
            //获取参数供编辑用
            $channel_grade_info = $channel_sever->getChannelGradeById($channel_grade_id);
            //处理比例*100
                //进货折扣
            $channel_grade_info['purchase_discount'] = $channel_grade_info['purchase_discount']*100;
                //平一级奖
            $channel_grade_info['flat_first'] = $channel_grade_info['flat_first']*100;
                //平二级奖
            $channel_grade_info['flat_second'] = $channel_grade_info['flat_second']*100;
                //平三级奖
            $channel_grade_info['flat_third'] = $channel_grade_info['flat_third']*100;
                //跨级奖
            $channel_grade_info['cross_level'] = $channel_grade_info['cross_level']*100;
            //处理升级条件、降级条件
            $upgrade_val = json_decode($channel_grade_info['upgrade_val'],true);
            $channel_grade_info['upgrade_val'] = $upgrade_val;
            if(!empty($upgrade_val['goods_id'])){
                $goods_id = $upgrade_val['goods_id'];
                $channel_goods_arr = $channel_sever->getChannelGoodsInfo($goods_id);
                $channel_grade_info['upgrade_val']['goods_name'] = $channel_goods_arr['goods_name'];
                $channel_grade_info['upgrade_val']['pic'] = getApiSrc($channel_goods_arr['pic_url']);
            }
//            var_dump($upgrade_val['goods_id'],$channel_grade_info);exit;
            $downgrade_val = json_decode($channel_grade_info['downgrade_val'],true);
            //处理降级条件 天数,值  需分开用于展示
            if(!empty($downgrade_val['down_total_purchase_num_cond'])){
                $down_total_purchase_num_cond_arr = explode(',',$downgrade_val['down_total_purchase_num_cond']);
                $downgrade_val['down_total_purchase_num_day'] = $down_total_purchase_num_cond_arr[0];
                $downgrade_val['down_total_purchase_num'] = $down_total_purchase_num_cond_arr[1];
            }
            if(!empty($downgrade_val['down_total_purchase_amount_cond'])){
                $down_total_purchase_amount_cond_arr = explode(',',$downgrade_val['down_total_purchase_amount_cond']);
                $downgrade_val['down_total_purchase_amount_day'] = $down_total_purchase_amount_cond_arr[0];
                $downgrade_val['down_total_purchase_amount'] = $down_total_purchase_amount_cond_arr[1];
            }
            if(!empty($downgrade_val['down_total_order_num_cond'])){
                $down_total_order_num_cond_arr = explode(',',$downgrade_val['down_total_order_num_cond']);
                $downgrade_val['down_total_order_num_day'] = $down_total_order_num_cond_arr[0];
                $downgrade_val['down_total_order_num'] = $down_total_order_num_cond_arr[1];
            }
            $channel_grade_info['downgrade_val'] = $downgrade_val;
            $this->assign('list', $channel_grade_info);
        }
        //获取权重
        $channel_weight_list = $channel_sever->getChannelGradeWeight();
        $this->assign('level_weight',json_encode($channel_weight_list));

        $this->fetch('template/' . $this->module . '/updateChannelGrade');
    }
    //渠道商设置页面
    public function channelSetting()
    {
        //查询出设置的信息
        $channel_sever = new channelServer();
        $channel_config = $channel_sever->getChannelConfig();
        $this->assign('website', $channel_config);
        $this->fetch('template/' . $this->module . '/channelSetting');
    }
    /*
     * 申请协议
     * **/
    public function channelAgreement()
    {
        $website_id = $this->website_id;
        //获取协议信息
        $channel_sever = new channelServer();
        $agreement_info = $channel_sever->getAgreementSite($website_id);
        $this->assign('website', $agreement_info);
        $this->fetch('template/' . $this->module . '/channelAgreement');
    }
    /*
     * 渠道商等级页面
     * **/
    public function channelGradeList()
    {
        $this->fetch('template/' . $this->module . '/channelGradeList');
    }
    /*
     * 渠道商自动降级
     * **/
    public function autoDownChannelAgentLevel($website_id_codition)
    {
        $website_id = $website_id_codition['website_id'];
        //先判断是否开启了自动升级
        $channel = new channelServer();
        $channel_conf = $channel->getChannelConfig($website_id);
        $channel_mdl = new VslChannelModel();
        $channel_level = new VslChannelLevelModel();
        //获取所有渠道商
        $all_channel_list = $channel_mdl->Query(['website_id'=>$website_id,'status'=>1],'*');
        //获取默认等级
        $default_weight_list = $channel_level->getInfo(['is_default'=>1,'website_id'=>$website_id],'channel_grade_id, weight');
        $default_weight = $default_weight_list['weight'];
        $default_grade_id = $default_weight_list['channel_grade_id'];
        foreach($all_channel_list as $k=>$v){
            //获取当前用户的等级
            $now_user_grade_list = $channel_mdl->alias('c')->field('channel_id,weight,upgrade_time,auto_downgrade,downgrade_val,downgrade_condition')->join('vsl_channel_level cl','c.channel_grade_id = cl.channel_grade_id')->where(['uid'=>$v['uid'], 'c.website_id'=>$website_id])->find();
            $now_user_grade = $now_user_grade_list['weight'];
            $upgrade_time = $now_user_grade_list['upgrade_time'];
            $auto_downgrade = $now_user_grade_list['auto_downgrade'];
            //获取升级时间
            if($now_user_grade > $default_weight){
                if($channel_conf['is_use'] == '1' && $auto_downgrade == '1'){//开启了自动升降级
                    if($channel_conf['channel_grade'] == '1'){//是否开启了跳级
                        //获取我的所有下面的等级
                        $down_grade_weight_list = $channel_level->Query(['weight'=>['<',$now_user_grade]],'weight');
                        arsort($down_grade_weight_list);//从上到下循环，
                        foreach($down_grade_weight_list as $k1=>$v1){
                            $mark = [];
                            $i = 0;
                            $down_grade_condition_list = $channel_level->getInfo(['weight'=>$v1],'channel_grade_id,downgrade_condition,downgrade_val,auto_downgrade');
//                            p($down_grade_condition_list);exit;
                            if($down_grade_condition_list['auto_downgrade'] == 1 && $down_grade_condition_list['downgrade_val']){
                                $downgrade_arr = json_decode($down_grade_condition_list['downgrade_val'],true);
                                //获取要求的采购量  {"down_total_purchase_num_cond":"10,50","down_total_purchase_amount_cond":"5,500","down_total_order_num_cond":"1,2"}
                                $condition1 = $downgrade_arr['down_total_purchase_num_cond'];
                                if(!empty($condition1)){
                                    $i++;
                                    $condition1_arr = explode(',', $condition1);//天数，规定多久降级
                                    $day1 = $condition1_arr[0];
                                    $last_time = $upgrade_time+$day1*24*3600;
                                    if (time() > $last_time) {
                                        //获取我的采购量
                                        $my_purchase_num = $channel->getMyLeaseDayPurchaseNum($condition1_arr[0],$condition1_arr[1],$upgrade_time,$v['uid'],$website_id);
                                        if($my_purchase_num<=$condition1_arr[1]){
                                            $mark[] = 1;
                                        }
                                    }
                                }

                                //获取采购金额
                                $condition2 = $downgrade_arr['down_total_purchase_amount_cond'];
                                if(!empty($condition2)){
                                    $i++;
                                    $condition2_arr = explode(',', $condition2);
                                    $day2 = $condition2_arr[0];
                                    $last_time = $upgrade_time+$day2*24*3600;
                                    if (time() > $last_time) {
                                        //获取我的采购金额
                                        $my_purchase_amount = $channel->getMyLeaseDayPurchaseAmount($condition2_arr[0],$condition2_arr[1],$upgrade_time,$v['uid'],$website_id);
                                        if($my_purchase_amount<=$condition2_arr[1]){
                                            $mark[] = 2;
                                        }
                                    }
                                }

                                //获取采购单
                                $condition3 = $downgrade_arr['down_total_order_num_cond'];
                                if(!empty($condition3)){
                                    $i++;
                                    $condition3_arr = explode(',', $condition3);
                                    $day3 = $condition3_arr[0];
                                    $last_time = $upgrade_time+$day3*24*3600;
                                    if (time() > $last_time) {
                                        //获取采购订单数
                                        $my_order_num = $channel->getMyLeaseDayOrderNum($condition3_arr[0],$condition3_arr[1],$upgrade_time,$v['uid'],$website_id);
                                        if($my_order_num<=$condition3_arr[1]){
                                            $mark[] = 3;
                                        }
                                    }
                                }
//                                var_dump($my_purchase_num,$my_purchase_amount,$my_order_num,count($mark,$i));exit;
                                if($down_grade_condition_list['downgrade_condition'] == 'all'){
                                    if(count($mark) == $i ){
                                        $channel_mdl->save(['channel_grade_id' => $down_grade_condition_list['channel_grade_id'], 'downgrade_time' => time(), 'down_upgrade_time' => time()], ['uid' => $v['uid']]);
                                    }
                                }else{
                                    if(count($mark) >= 1 ){
                                        $channel_mdl->save(['channel_grade_id' => $down_grade_condition_list['channel_grade_id'], 'downgrade_time' => time(), 'down_upgrade_time' => time()], ['uid' => $v['uid']]);
                                    }
                                }
                            }else{
                                //判断如果这个下级为默认等级，则降级条件为空，判断当前渠道商的自身降级条件是否达到降级条件
                                if($v1 == 1){
                                    //$now_user_grade_list
                                    if($now_user_grade_list['auto_downgrade'] == 1 && $now_user_grade_list['downgrade_val']){
                                        $downgrade_arr = json_decode($now_user_grade_list['downgrade_val'],true);
                                        //获取要求的采购量  {"down_total_purchase_num_cond":"10,50","down_total_purchase_amount_cond":"5,500","down_total_order_num_cond":"1,2"}
                                        $condition1 = $downgrade_arr['down_total_purchase_num_cond'];
                                        if(!empty($condition1)){
                                            $i++;
                                            $condition1_arr = explode(',', $condition1);
                                            $day1 = $condition1_arr[0];
                                            $last_time = $upgrade_time+$day1*24*3600;
                                            if (time() > $last_time) {
                                                //获取我的采购量
                                                $my_purchase_num = $channel->getMyLeaseDayPurchaseNum($condition1_arr[0],$condition1_arr[1],$upgrade_time,$v['uid'],$website_id);
                                                if($my_purchase_num<=$condition1_arr[1]){
                                                    $mark[] = 1;
                                                }
                                            }
                                        }

                                        //获取采购金额
                                        $condition2 = $downgrade_arr['down_total_purchase_amount_cond'];
                                        if(!empty($condition2)){
                                            $i++;
                                            $condition2_arr = explode(',', $condition2);
                                            $day2 = $condition2_arr[0];
                                            $last_time = $upgrade_time+$day2*24*3600;
                                            if (time() > $last_time) {
                                                //获取我的采购金额
                                                $my_purchase_amount = $channel->getMyLeaseDayPurchaseAmount($condition2_arr[0],$condition2_arr[1],$upgrade_time,$v['uid'],$website_id);
                                                if($my_purchase_amount<=$condition2_arr[1]){
                                                    $mark[] = 2;
                                                }
                                            }

                                        }

                                        //获取采购单
                                        $condition3 = $downgrade_arr['down_total_order_num_cond'];
                                        if(!empty($condition3)){
                                            $i++;
                                            $condition3_arr = explode(',', $condition3);
                                            $day3 = $condition3_arr[0];
                                            $last_time = $upgrade_time+$day3*24*3600;
                                            if (time() > $last_time) {
                                                //获取采购订单数
                                                $my_order_num = $channel->getMyLeaseDayOrderNum($condition3_arr[0],$condition3_arr[1],$upgrade_time,$v['uid'],$website_id);
                                                if($my_order_num<=$condition3_arr[1]){
                                                    $mark[] = 3;
                                                }
                                            }
                                        }
                                        if($now_user_grade_list['downgrade_condition'] == 'all'){
                                            if(count($mark) == $i ){
                                                $channel_mdl->save(['channel_grade_id' => $default_grade_id, 'downgrade_time' => time(), 'down_upgrade_time' => time()], ['uid' => $v['uid']]);
                                            }
                                        }else{
                                            if(count($mark) >= 1 ){
                                                $channel_mdl->save(['channel_grade_id' => $default_grade_id, 'downgrade_time' => time(), 'down_upgrade_time' => time()], ['uid' => $v['uid']]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }else{//未跳级
                        //获取我的所有下面的等级
                        $down_grade_weight_list = $channel_level->getInfo(['weight'=>$now_user_grade-1],'weight');
                        $mark = [];
                        $i = 0;
                        $down_grade_condition_list = $channel_level->getInfo(['weight'=>$down_grade_weight_list['weight']],'channel_grade_id,downgrade_condition,downgrade_val,auto_downgrade');
                        if($down_grade_condition_list['auto_downgrade'] == 1 && $down_grade_condition_list['downgrade_val']){
                            $downgrade_arr = json_decode($down_grade_condition_list['downgrade_val'],true);
                            //获取要求的采购量  {"down_total_purchase_num_cond":"10,50","down_total_purchase_amount_cond":"5,500","down_total_order_num_cond":"1,2"}
                            $condition1 = $downgrade_arr['down_total_purchase_num_cond'];
                            if(!empty($condition1)){
                                $i++;
                                $condition1_arr = explode(',', $condition1);
                                $day1 = $condition1_arr[0];
                                $last_time = $upgrade_time+$day1*24*3600;
                                if (time() > $last_time) {
                                    //获取我的采购量
                                    $my_purchase_num = $channel->getMyLeaseDayPurchaseNum($condition1_arr[0],$condition1_arr[1],$upgrade_time,$v['uid'],$website_id);
                                    if($my_purchase_num<=$condition1_arr[1]){
                                        $mark[] = 1;
                                    }
                                }

                            }

                            //获取采购金额
                            $condition2 = $downgrade_arr['down_total_purchase_amount_cond'];
                            if(!empty($condition2)){
                                $i++;
                                $condition2_arr = explode(',', $condition2);
                                $day2 = $condition2_arr[0];
                                $last_time = $upgrade_time+$day2*24*3600;
                                if (time() > $last_time) {
                                    //获取我的采购金额
                                    $my_purchase_amount = $channel->getMyLeaseDayPurchaseAmount($condition2_arr[0],$condition2_arr[1],$upgrade_time,$v['uid'],$website_id);
                                    if($my_purchase_amount<=$condition2_arr[1]){
                                        $mark[] = 2;
                                    }
                                }
                            }

                            //获取采购单
                            $condition3 = $downgrade_arr['down_total_order_num_cond'];
                            if(!empty($condition3)){
                                $i++;
                                $condition3_arr = explode(',', $condition3);
                                $day3 = $condition3_arr[0];
                                $last_time = $upgrade_time+$day3*24*3600;
                                if (time() > $last_time) {
                                    //获取采购订单数
                                    $my_order_num = $channel->getMyLeaseDayOrderNum($condition3_arr[0],$condition3_arr[1],$upgrade_time,$v['uid'],$website_id);
                                    if($my_order_num<=$condition3_arr[1]){
                                        $mark[] = 3;
                                    }
                                }
                            }
                            if($down_grade_condition_list['downgrade_condition'] == 'all'){
                                if(count($mark) == $i ){
                                    $channel_mdl->save(['channel_grade_id' => $down_grade_condition_list['channel_grade_id'], 'downgrade_time' => time(), 'down_upgrade_time' => time()], ['uid' => $v['uid']]);
                                }
                            }else{
                                if(count($mark) >= 1 ){
                                    $channel_mdl->save(['channel_grade_id' => $down_grade_condition_list['channel_grade_id'], 'downgrade_time' => time(), 'down_upgrade_time' => time()], ['uid' => $v['uid']]);
                                }
                            }
                        }else{
                            $down_grade_weight = $now_user_grade-1;
                            //判断如果这个下级为默认等级，则降级条件为空，判断当前渠道商的自身降级条件是否达到降级条件
                            if( $down_grade_weight == 1){
                                //$now_user_grade_list
                                if($now_user_grade_list['auto_downgrade'] == 1 && $now_user_grade_list['downgrade_val']){
                                    $downgrade_arr = json_decode($now_user_grade_list['downgrade_val'],true);
                                    //获取要求的采购量  {"down_total_purchase_num_cond":"10,50","down_total_purchase_amount_cond":"5,500","down_total_order_num_cond":"1,2"}
                                    $condition1 = $downgrade_arr['down_total_purchase_num_cond'];
                                    if(!empty($condition1)){
                                        $i++;
                                        $condition1_arr = explode(',', $condition1);
                                        $day1 = $condition1_arr[0];
                                        $last_time = $upgrade_time+$day1*24*3600;
                                        if (time() > $last_time) {
                                            //获取我的采购量
                                            $my_purchase_num = $channel->getMyLeaseDayPurchaseNum($condition1_arr[0],$condition1_arr[1],$upgrade_time,$v['uid'],$website_id);
                                            if($my_purchase_num<=$condition1_arr[1]){
                                                $mark[] = 1;
                                            }
                                        }
                                    }

                                    //获取采购金额
                                    $condition2 = $downgrade_arr['down_total_purchase_amount_cond'];
                                    if(!empty($condition2)){
                                        $i++;
                                        $condition2_arr = explode(',', $condition2);
                                        $day2 = $condition2_arr[0];
                                        $last_time = $upgrade_time+$day2*24*3600;
                                        if (time() > $last_time) {
                                            //获取我的采购金额
                                            $my_purchase_amount = $channel->getMyLeaseDayPurchaseAmount($condition2_arr[0],$condition2_arr[1],$upgrade_time,$v['uid'],$website_id);
                                            if($my_purchase_amount<=$condition2_arr[1]){
                                                $mark[] = 2;
                                            }
                                        }
                                    }

                                    //获取采购单
                                    $condition3 = $downgrade_arr['down_total_order_num_cond'];
                                    if(!empty($condition3)){
                                        $i++;
                                        $condition3_arr = explode(',', $condition3);
                                        $day3 = $condition3_arr[0];
                                        $last_time = $upgrade_time+$day3*24*3600;
                                        if (time() > $last_time) {
                                            //获取采购订单数
                                            $my_order_num = $channel->getMyLeaseDayOrderNum($condition3_arr[0],$condition3_arr[1],$upgrade_time,$v['uid'],$website_id);
                                            if($my_order_num<=$condition3_arr[1]){
                                                $mark[] = 3;
                                            }
                                        }
                                    }
                                    if($now_user_grade_list['downgrade_condition'] == 'all'){
                                        if(count($mark) == $i ){
                                            $channel_mdl->save(['channel_grade_id' => $default_grade_id, 'downgrade_time' => time(), 'down_upgrade_time' => time()], ['uid' => $v['uid']]);
                                        }
                                    }else{
                                        if(count($mark) >= 1 ){
                                            $channel_mdl->save(['channel_grade_id' => $default_grade_id, 'downgrade_time' => time(), 'down_upgrade_time' => time()], ['uid' => $v['uid']]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * 采购订单
     */
    public function purchaseOrder()
    {
        $channel_sever = new channelServer();
        //统计全部、待付款、已完成、已关闭的采购订单数目
        //全部
        $channel_order_count['all_count'] = $channel_sever->getPurchaseOrderStatusCount();
        //待付款
        $channel_order_count['no_pay_count'] = $channel_sever->getPurchaseOrderStatusCount(['order_status'=>0]);
        //已完成
        $channel_order_count['finish_count'] = $channel_sever->getPurchaseOrderStatusCount(['order_status'=>4]);
        //已关闭
        $channel_order_count['off_count'] = $channel_sever->getPurchaseOrderStatusCount(['order_status'=>5]);
        $this->assign('channel_order_count', $channel_order_count);
        $this->fetch('template/' . $this->module . '/purchaseOrder');
    }
    /**
     * B端采购订单详情
     */
    public function purchaseOrderDetail()
    {
        $purchase_order_id = request()->get('purchase_order_id', '0');
        $channel_server = new ChannelServer();
        $order_detail = $channel_server->purchaseOrderDetail($purchase_order_id);
        $this->assign('order', $order_detail);
        $this->fetch('template/' . $this->module . '/purchaseOrderDetail');
    }
    /**
     * 货款流水
     */
    public function proceedsList()
    {
        $this->fetch('template/' . $this->module . '/proceedsList');
    }
    /**
     * 货款详情
     */
    public function proceedsDetail()
    {
        $member = new MemberService();
        $id = request()->get('id','');
        $condition['nmar.id'] = $id;
        $condition['nmar.account_type'] = 5;
        $list = $member->getAccountList(1,0, $condition, $order = '', $field = '*');
        $this->assign('list',$list['data']) ;
        $this->fetch('template/' . $this->module . '/proceedsDetail');
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