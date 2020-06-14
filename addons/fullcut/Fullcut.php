<?php
namespace addons\fullcut;
use addons\Addons as Addo;
use addons\coupontype\server\Coupon;
use addons\gift\server\Gift;
use addons\giftvoucher\server\GiftVoucher;
use data\service;
use addons\fullcut\service\Fullcut as mansong;
use data\service\WebSite as WebSite;
use \think\Session as Session;
use app\platform\controller;
use data\service\Promotion as PromotionService;
use think\Db;

class Fullcut extends Addo
{
    public $shopfinfo = array();
    
    public $gv = 0;
    public $gift = 0;
    public $coupon = 0;

    public $info = array(
        'name' => 'fullcut', // 插件名称标识
        'title' => '满减送', // 插件中文名
        'description' => '促销活动，轻松提升增购复购', // 插件概述
        'status' => 1, // 状态 1启用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', // 版本号
        'has_addonslist' => 1, // 是否有下级插件 例如：第三方登录插件下有 qq登录，微信登录
        'content' => '', // 插件的详细介绍或使用方法
        'config_hook' => 'fullCutList',
        'config_admin_hook' => 'fullCutList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197078.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782129.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782252.png',
    ); // 设置文件单独的钩子

    public $menu_info = array(
        [
            'module_name' => '满减送',
            'parent_module_name' => '应用', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '促销活动，轻松提升增购复购', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'fullCutList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '满减送列表',
            'parent_module_name' => '满减送', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '以订单为基准，活动商品达到一定条件可减金额、包邮、送赠品、送优惠券、送礼品券。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'fullCutList',
            'module' => 'platform'
        ],
        [
            'module_name' => '满减送设置',
            'parent_module_name' => '满减送', // 上级模块名称 用来确定上级目录
            'last_module_name' => '满减送列表', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '关闭后，商城已设置的满减活动均不生效。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'fullCutSet',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加满减送',
            'parent_module_name' => '满减送', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' =>0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '每档满减活动可设置最多五个规则，每个规则可设置减钱或送赠品的优惠，活动开始后不能对活动进行修改，请谨慎填写。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addFullCut',
            'module' => 'platform'
        ],
        [
            'module_name' => '编辑满减送',
            'parent_module_name' => '满减送', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '每档满减活动可设置最多五个规则，每个规则可设置减钱或送赠品的优惠，活动开始后不能对活动进行修改，请谨慎填写。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'editFullCut',
            'module' => 'platform'
        ],
        [
            'module_name' => '满减送详情',
            'parent_module_name' => '满减送', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'fullCutInfo',
            'module' => 'platform'
        ],

        //admin

        [
            'module_name' => '满减送',
            'parent_module_name' => '应用', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '促销活动，轻松提升增购复购', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'fullCutList',
            'module' =>'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '满减送列表',
            'parent_module_name' => '满减送', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '以订单为基准，活动商品达到一定条件可减金额、包邮、送赠品、送优惠券、送礼品券。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'fullCutList',
            'module' =>'admin',
            'is_admin_main' => 0//c端应用页面主入口标记
        ],
        [
            'module_name' => '添加满减送',
            'parent_module_name' => '满减送', // 上级模块名称 用来确定上级目录
            'last_module_name' => '满减送列表', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '每档满减活动可设置最多五个规则，每个规则可设置减钱或送赠品的优惠，活动开始后不能对活动进行修改，请谨慎填写。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addFullCut',
            'module' => 'admin'
        ],

        [
            'module_name' => '编辑满减送',
            'parent_module_name' => '满减送', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '每档满减活动可设置最多五个规则，每个规则可设置减钱或送赠品的优惠，活动开始后不能对活动进行修改，请谨慎填写。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'editFullCut',
            'module' => 'admin'
        ],
        [
            'module_name' => '满减送详情',
            'parent_module_name' => '满减送', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'fullCutInfo',
            'module' => 'admin'
        ]


    ) // 钩子名称（需要该钩子调用的页面）
    ;


    public function __construct(){
        parent::__construct();
        $this->assign('setConfigUrl', __URL(addons_url_platform('fullcut://Fullcut/setConfig')));
        $this->shopfinfo['website_id'] = $this->website_id;
        $this->shopfinfo['instance_id'] = $this->instance_id;
        //$this->website_id = $this->shopfinfo['website_id'];
        $this->coupon = getAddons('coupontype',$this->website_id);
        $this->gift = getAddons('gift',$this->website_id);
        $this->gv = getAddons('giftvoucher',$this->website_id);
        $this->assign('couponStatus', $this->coupon);
        $this->assign('gift_status', $this->gift);
        $this->assign('giftvoucher_status', $this->gv);
        $this->assign('website_id', $this->website_id);
    }

    /*
     * 实现第三方钩子
     *
     * @param array $params
     */
    public function fullCutList()
    {
        if (request()->isAjax()) {
            $config= new mansong();
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text', '');
            $status = request()->post('status', '');
            $shop_id = $this->instance_id;
            $condition = array(
                'website_id' => $this->website_id,
                'mansong_name' => array(
                    'like',
                    '%' . $search_text . '%'
                ),
                'shop_id' => $shop_id
            );

            $list = $config->getPromotionMansongList($page_index, $page_size, $condition);
            $list = objToArr($list);
            foreach ($list['data'] as $k=>$v){

                //未开始
                if($v['start_time']>time() && $v['end_time']>time()){
                    $config->update_mansong_status(['status'=>0],['mansong_id'=>$v['mansong_id']]);
                    $list['data'][$k]['status'] = 0;
                }

                //开始
                if($v['start_time']<time() && $v['end_time']>time() && $v['status']==0){
                    $config->update_mansong_status(['status'=>1],['mansong_id'=>$v['mansong_id']]);
                    $list['data'][$k]['status'] = 1;
                }

                //结束
                if($v['start_time']<time() && $v['end_time']<time()){
                    $config->update_mansong_status(['status'=>4],['mansong_id'=>$v['mansong_id']]);
                    $list['data'][$k]['status'] = 4;
                }
            }
            //return json($list);
            ob_clean();
            print_r(json_encode($list));
            exit;
        }
        $status = request()->get('status', - 1);
        $this->assign("status", $status);
        $child_menu_list = array(
            array(
                'url' => "promotion/mansonglist",
                'menu_name' => "全部",
                "active" => $status == '-1' ? 1 : 0
            ),
            array(
                'url' => "promotion/mansonglist?status=0",
                'menu_name' => "未发布",
                "active" => $status == 0 ? 1 : 0
            ),
            array(
                'url' => "promotion/mansonglist?status=1",
                'menu_name' => "进行中",
                "active" => $status == 1 ? 1 : 0
            ),
            array(
                'url' => "promotion/mansonglist?status=3",
                'menu_name' => "已关闭",
                "active" => $status == 3 ? 1 : 0
            ),
            array(
                'url' => "promotion/mansonglist?status=4",
                'menu_name' => "已结束",
                "active" => $status == 4 ? 1 : 0
            )
        );
        if($this->module=='admin' || $this->module == 'admin'){
            $this->assign('shop_id',$this->instance_id);
        }
        $this->assign('child_menu_list', $child_menu_list);
        $this->fetch('template/'.$this->module.'/mansongList');
    }
    public function fullCutInfo()
    {
        $mansong = new mansong($this->shopfinfo);
        $mansong_id = $_GET['mansong_id'];
        $info = $mansong->getPromotionMansongDetail($mansong_id);
        //商品已选列表
        if (!empty($info['goods_list'])) {
            foreach ($info['goods_list'] as $k => $v) {
                $goods_id_array[] = $v['goods_id'];
            }
            $this->assign("seleted_goods", json_encode($goods_id_array));
        }else{
            $this->assign("seleted_goods", '1');
        }

        $info['goods_id_array'] = $goods_id_array;
        if(!empty($info['goods_list'])){
            $this->assign("goods_list", json_encode($info['goods_list']));
        }else{
            $this->assign("goods_list",array());
        }
        //活动名
        $mansong_name = '';
        foreach ($info['rule'] as $k=>$v){
            if($v['discount']>0) {
                $mansong_name .= "满:" . $v['price'] . "减" . $v['discount'] . ";";
            }
            if($v['free_shipping']==1) {
                $mansong_name .= "满:" . $v['price'] . "包邮;";
            }
            if($v['give_coupon']>0) {
                $mansong_name .= "满:" . $v['price'] . "送" . $v['coupon_name'] . ";";
            }
            if($v['gift_id']>0) {
                $mansong_name .= "满:" . $v['price'] . "送" . $v['gift_name'] . ";";
            }
            if($v['gift_card_id']>0) {
                $mansong_name .= "满:" . $v['price'] . "送" . $v['giftvoucher_name'] . ";";
            }
        }

        $this->assign('mansong_rule',$mansong_name);
        $this->assign("seleted_goods", json_encode($info['goods_id_array']));
        $this->assign('mansong_info', $info);
        $this->assign('shop_id', $this->instance_id);
        $this->fetch('template/'.$this->module.'/mansong_info');
    }

    /*
     * 添加满减送活动
     *
     * @return \think\response\View
     */
    public function addFullCut()
    {
        //获取优惠券列表
        $mansong = new mansong($this->shopfinfo);
        if($this->gift){
            $gift = new Gift();
            $gift_condition = array(
                'vpg.website_id' => $this->website_id,
                'vpg.shop_id' => $this->instance_id,
            );
            $gift_list = $gift->giftList(1,0,$gift_condition);  //礼品列表
        }else{
            $gift_list = [];
        }
        $this->assign("gift_list",json_encode($gift_list));
        if($this->coupon){
            $coupon_service = new Coupon();
            $condition = array(
                'shop_id' => $this->instance_id,
                'website_id'=>$this->website_id,
                'start_receive_time'=>['<',time()],
                'end_receive_time'=>['>',time()]
            );
            $coupon_type_list = $coupon_service->getCouponTypeList(1, 0, $condition);//优惠券列表
        }else{
            $coupon_type_list = [];
        }
        $this->assign("coupon_type_list",json_encode($coupon_type_list));
        if($this->gv){
            $giftVoucher = new GiftVoucher();
            $giftvoucher_condition = array(
                'gv.website_id' => $this->website_id,
                'gv.shop_id' => $this->instance_id,
                'gv.start_receive_time'=>['<',time()],
                'gv.end_receive_time'=>['>',time()]
            );
            $giftvoucher_list = $giftVoucher->getGiftVoucherList(1,0,$giftvoucher_condition);  //礼品列表
        }else{
            $giftvoucher_list = [];
        }
        $this->assign("giftvoucher_list",json_encode($giftvoucher_list));
        
        if (request()->isAjax()) {
            $mansong_name = $_POST['mansong_name'];
            $level = $_POST['level'];
            $remark = $_POST['remark'];
            $status = $_POST['status'];
            $range = $_POST['range'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time']." 23:59:59";
            if(strtotime($start_time)>strtotime($end_time)){
                $json['code'] = -1;
                $json['message'] = "开始时间不能大于结束时间";
                ob_clean();
                print_r(json_encode($json));
                exit;
            }
            if(!empty($_REQUEST['shop_id'])){
                $shop_id = $_REQUEST['shop_id'];
            }else{
                $shop_id = $this->instance_id;
            }
            $type = $_POST['type'];
            $range_type = $_POST['range_type'];
            $rule = $_POST['rule'];
            $goods_id_array = $_POST['goods_id_array'];

            $res = $mansong->addPromotionMansong($mansong_name, $start_time, $end_time, $shop_id, $remark, $type, $range_type, $rule, $goods_id_array,$range,$status,$level);
            ob_clean();
            if($res) {
                $json['code'] = $res;
                $json['message'] = "操作成功";
                print_r(json_encode($json));
                exit;
            }else{
                $json['code'] = '-1';
                $json['message'] = "添加失败";
                print_r(json_encode($json));
            }
            //return AjaxReturn($res);
        } else {
            if($this->module=='admin' || $this->module=='admin'){
                $shop_id = $this->instance_id;
            }else{
                $shop_id = 0;
            }
            $time = date('Y-m-d',time());
            $this->assign("seleted_goods",'0');
            $this->assign("time",$time);
            $this->assign("shop_id",$shop_id);
            $this->fetch('template/'.$this->module.'/addMansong');
        }
    }

    static function run(){

    }


    //更新满减送活动
    public function editFullCut()
    {
        //获取优惠券列表
        $mansong = new mansong($this->shopfinfo);
        
        
        if($this->gv){
            $giftVoucher = new GiftVoucher();
            $giftvoucher_condition = array(
                'gv.website_id' => $this->website_id,
                'gv.shop_id' => $this->instance_id,
                'gv.end_receive_time'=>['>',time()],
                'gv.start_receive_time'=>['<',time()]
            );
            $giftvoucher_list = $giftVoucher->getGiftVoucherList(1,0,$giftvoucher_condition);  //礼品列表
        }else{
            $giftvoucher_list = [];
        }
        if($this->gift){
            $gift = new Gift();
            $gift_condition = array(
                'vpg.website_id' => $this->website_id,
                'vpg.shop_id' => $this->instance_id,
            );
            $gift_list = $gift->giftList(1,0,$gift_condition);  //礼品列表
        }else{
            $gift_list = [];
        }
        
        if($this->coupon){
            $coupon_service = new Coupon();
            $condition = array(
                'shop_id' => $this->instance_id,
                'website_id'=>$this->website_id,
                'end_receive_time'=>['>',time()],
                'start_receive_time'=>['<',time()]
            );
            $coupon_type_list = $coupon_service->getCouponTypeList(1, 0, $condition);//优惠券列表
        }else{
            $coupon_type_list = [];
        }
        
        $this->assign("coupon_type_list",json_encode($coupon_type_list));
        $this->assign("coupon_type_array",$coupon_type_list);
        $this->assign("gift_list",json_encode($gift_list));
        $this->assign("giftvoucher_list",json_encode($giftvoucher_list));
        $this->assign("gift_list_array",$gift_list);
        $this->assign("giftvoucher_list_array",$giftvoucher_list);
        if (request()->isAjax()) {
            $mansong_id = $_POST['mansong_id'];
            $mansong_name = $_POST['mansong_name'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time']." 23:59:59";
            $type = $_POST['type'];
            $remark = $_POST['remark'];
            $range_type = $_POST['range_type'];
            $rule = $_POST['rule'];
            $range = $_POST['range'];
            $status = $_POST['status'];
            $level = $_POST['level'];
            $goods_id_array = $_POST['goods_id_array'];
            if(strtotime($start_time)>strtotime($end_time)){
                $json['code'] = -1;
                $json['message'] = "开始时间不能大于结束时间";
                ob_clean();
                print_r(json_encode($json));
                exit;
            }
            $res = $mansong->updatePromotionMansong($mansong_id, $mansong_name, $start_time, $end_time, $remark, $type, $range_type, $rule, $goods_id_array,$range,$status,$level);
            if($res){
                $json['message'] = "操作成功";
                $json['code'] = $res;
                ob_clean();
                echo json_encode($json);exit;
            }

           //详情
        } else{
            $mansong_id = $_GET['mansong_id'];
            $info = $mansong->getPromotionMansongDetail($mansong_id);
            //商品已选列表
            if (!empty($info['goods_list'])) {
                foreach ($info['goods_list'] as $k => $v) {
                    $goods_id_array[] = $v['goods_id'];
                }
                $this->assign("seleted_goods", json_encode($goods_id_array));
            }else{
                $this->assign("seleted_goods", '1');
            }

            $info['goods_id_array'] = $goods_id_array;
            if(!empty($info['goods_list'])){
                $this->assign("goods_list", json_encode($info['goods_list']));
            }else{
                $this->assign("goods_list",'');
            }
            $this->assign("seleted_goods", json_encode($info['goods_id_array']));
            $this->assign('mansong_info', $info);
            $this->assign('shop_id', $this->instance_id);

            $this->fetch('template/'.$this->module.'/updateMansong');
        }

    }
    public function fullCutSet()
    {
        $config= new mansong();
        $list = $config->getManSongSite($this->shopfinfo['website_id']);
        $this->assign('is_use', $list['is_use']);
        $this->fetch('template/'.$this->module.'/manSetting');
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