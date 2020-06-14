<?php
namespace addons\presell;
use addons\Addons as Addo;
use addons\miniprogram\model\WeixinAuthModel;
use data\model\AddonsConfigModel;
use addons\discount\model\Discount as PromotionService;
use addons\discount\controller\Discount as discout;
use addons\presell\controller\Presell as pre_sell;
use data\model\WebSiteModel;
use data\service\AddonsConfig;
use data\service\Order as OrderService;
use addons\presell\service\Presell as PresellService;
use data\model\VslGoodsModel;
use think\Db;

class Presell extends Addo
{
    public $shopfinfo = array();

    public $info = array(
        'name' => 'presell', // 插件名称标识
        'title' => '商品预售', // 插件中文名
        'description' => '先付定金提前开卖，饥饿营销', // 插件概述
        'status' => 1, // 状态 1启用 0禁用
        'author' => 'vslaishop', // 作者
        'version' => '1.0', // 版本号
        'has_addonslist' => 1, // 是否有下级插件 例如：第三方登录插件下有 qq登录，微信登录
        'content' => 'presellconfig', // 插件的详细介绍或使用方法
        'config_hook' => 'presellList',
        'config_admin_hook' => 'presellList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197124.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782153.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782303.png',
    ); // 设置文件单独的钩子

    //platform
    public $menu_info = array(
        [
            'module_name' => '商品预售',
            'parent_module_name' => '应用', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '先付定金提前开卖，饥饿营销', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'presellList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '预售列表',
            'parent_module_name' => '商品预售', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '预售采用“定金+尾款”的模式，下单先支付定金，到指定时间再支付尾款，参加预售的商品不能参与“限时折扣、秒杀、拼团、砍价”等活动。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'presellList',
            'module' => 'platform',
        ],
        [
            'module_name' => '预售设置',
            'parent_module_name' => '商品预售', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '关闭后，所有预售活动均不生效，可设置独立的分销分红规则，优先级为：商品独立>活动独立>分销/分红设置。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'presellconfig',
            'module' => 'platform',
        ],
        [
            'module_name' => '添加预售',
            'parent_module_name' => '商品预售', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addpresell',
            'module' => 'platform',
        ],
        [
            'module_name' => '订购记录',
            'parent_module_name' => '商品预售', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderrecord',
            'module' => 'platform',
        ],
        [
            'module_name' => '预售编辑',
            'parent_module_name' => '商品预售', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'editpresell',
            'module' => 'platform',
        ],

        //admin
        [
            'module_name' => '商品预售',
            'parent_module_name' => '应用', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '先付定金提前开卖，饥饿营销', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'presellList',
            'module' =>'admin',
            'is_admin_main' => 1//c端应用页面主入口标记
        ],
        [
            'module_name' => '预售列表',
            'parent_module_name' => '商品预售', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '预售采用“定金+尾款”的模式，参加预售的商品不能参与“限时折扣、秒杀、拼团、砍价”等活动。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'presellList',
            'module' => 'admin'
        ],
        [
            'module_name' => '添加预售',
            'parent_module_name' => '商品预售', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'addpresell',
            'module' => 'admin',
        ],
        [
            'module_name' => '订购记录',
            'parent_module_name' => '商品预售', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'orderrecord',
            'module' => 'admin',
        ],
        [
            'module_name' => '预售编辑',
            'parent_module_name' => '商品预售', // 上级模块名称 用来确定上级目录
            'last_module_name' => '', // 上一个菜单名称 用来确定菜单排序
            'is_menu' => 0, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'editpresell',
            'module' => 'admin',
        ],


    ) // 钩子名称（需要该钩子调用的页面）
    ;


     public function __construct(){
         parent::__construct();
         if ($this->module == 'platform' || $this->module == 'platform_new') {
             $this->assign('orderrecord', __URL(addons_url_platform('presell://Presell/orderrecord')));

         } else if ($this->module == 'admin' || $this->module == 'admin_new') {
             $this->assign('orderrecord', __URL(addons_url_admin('presell://Presell/orderrecord')));
         }
     }

    /**
     * 预售列表
     *
     * @param array $params
     **/
    public function presellList()
    {
        $presell_service = new PresellService();
        if (request()->isAjax()) {
            $presell = new pre_sell();
            $page_index = $_REQUEST['page_index']?$_REQUEST['page_index']:1;
            $page_size = $_REQUEST['page_size']?$_REQUEST['page_size']:PAGESIZE;
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            if(!empty($_REQUEST['search_text'])){
                $condition['name'] = $_REQUEST['search_text'];
            }
            if(!empty($_REQUEST['status'])){
                $condition['status'] = $_REQUEST['status'];
            }
            $res = $presell->presell_list($page_index,$page_size,$condition);
            //重拼所需展示数据
            foreach ($res['data'] as $k=>$v){
                //更新活动状态,默认进行中
                $res['data'][$k]['status_name'] = '进行中';
                $condition['id'] = $v['id'];
                if(time()>$v['end_time'] || $res['data'][$k]['status'] == 3){
                    //结束
                    $presell_service->update_presell_status('status','3',$condition);
                    $res['data'][$k]['status'] = 3;
                    $res['data'][$k]['status_name'] = '已结束';
//                    //修改promotion
//                    $goods = new VslGoodsModel();
//                    $goods->save(['promotion_type'=>'0'],['goods_id'=>$v['goods_id']]);
                }
                if($v['start_time']>time()){
                    $presell_service->update_presell_status('status','2',$condition);
                    $res['data'][$k]['status'] = 2;
                    $res['data'][$k]['status_name'] = '未开始';
                }
                //进行中
                if ($v['start_time'] < time() && time() < $v['end_time'] && $res['data'][$k]['status'] != 3) {
                    $res['data'][$k]['status'] = 1;
                    $presell_service->update_presell_status('status','1',$condition);
                }
                $res['data'][$k]['first_pay_time'] = date('Y-m-d H:i:s',$v['start_time']).'~'.date('Y-m-d H:i:s',$v['end_time']);

                $res['data'][$k]['last_money'] = number_format($v['allmoney'] - $v['firstmoney'],2,'.','');
                //预定总人数
                $count_people = $presell_service->get_presell_count_people($v['id']);
                $res['data'][$k]['count_people'] = $count_people[0]['num'];
                //已够数量
                $buy_num = $presell_service->get_presell_buy_num($v['id']);
                $res['data'][$k]['surplus_num'] = $v['presellnum'] - $buy_num;
            }
            //判断pc端、小程序是否开启
            $addons_conf = new AddonsConfig();
            $pc_conf = $addons_conf->getAddonsConfig('pcport', $this->website_id);
            $is_minipro = getAddons('miniprogram', $this->website_id);
            if($is_minipro){
                $weixin_auth = new WeixinAuthModel();
                $new_auth_state = $weixin_auth->getInfo(['website_id' => $this->website_id], 'new_auth_state')['new_auth_state'];
                if(isset($new_auth_state) && $new_auth_state == 0){
                    $is_minipro = 1;
                }else{
                    $is_minipro = 0;
                }
            }
            $website_mdl = new WebSiteModel();
            //查看移动端的状态
            $wap_status = $website_mdl->getInfo(['website_id' => $this->website_id], 'wap_status')['wap_status'];
            $res['addon_status']['wap_status'] = $wap_status;
            $res['addon_status']['is_pc_use'] = $pc_conf['is_use'];
            $res['addon_status']['is_minipro'] = $is_minipro;

            ob_clean();
            echo json_encode($res);exit;
        }

        //获取状态
        $count = $presell_service->get_status_count();  //全部
        $count_1 = $presell_service->get_status_count('1');  //进行
        $count_2 = $presell_service->get_status_count('2');  //未开始
        $count_3 = $presell_service->get_status_count('3');  //已结束
        $this->assign('count',$count);
        $this->assign('count_1',$count_1);
        $this->assign('count_2',$count_2);
        $this->assign('count_3',$count_3);
        $this->fetch('template/'.$this->module.'/presellList');

    }

    //基础配置
    public function presellconfig(){
        if(request()->isAjax()){
            try {
                $post_data = request()->post();
                $is_presell = $post_data['is_presell'];
                $addons_config_model = new AddonsConfigModel();
                $group_shopping_info = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => 'presell']);
                if (!empty($group_shopping_info)) {
                    $res = $addons_config_model->save(
                        [
                            'is_use' => $is_presell,
                            'modify_time' => time(),
                            'value' => json_encode($post_data, JSON_UNESCAPED_UNICODE)
                        ],
                        [
                            'website_id' => $this->website_id,
                            'addons' => 'presell'
                        ]
                    );
                } else {
                    $data['is_use'] = $is_presell;
                    $data['value'] = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                    $data['desc'] = '预售设置';
                    $data['create_time'] = time();
                    $data['addons'] = 'presell';
                    $data['website_id'] = $this->website_id;
                    $res = $addons_config_model->save($data);
                }
                setAddons('presell', $this->website_id, $this->instance_id);
                setAddons('presell', $this->website_id, $this->instance_id, true);
                ob_clean();
                $data['code'] = $res;
                $data['message'] = "操作成功";
                print_r(json_encode($data));exit;
            } catch (\Exception $e) {
                return ['code' => -1, 'message' => $e->getMessage()];
            }
        }

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
        $addons_data = json_decode($addons_info['value'], true) ?: []   ;
        if(empty($addons_data['is_distribution'])){
            $addons_data['is_distribution'] = 0;
        }
        $addons_data['is_use'] = $addons_info['is_use'] ?: 0;
        $this->assign('addons_data', $addons_data);
        $this->fetch('template/'.$this->module.'/presellConfig');
    }

    //编辑
    public function editpresell(){
        $id = $_REQUEST['id'];
        $presell = new PresellService();
        $info = $presell->get_presell_info($id);
        $info = objToArr($info);
        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id'=>$info[0]['goods_id']],'goods_name');
        foreach ($info as $k=>$v){
            $info[$k]['start_time'] = date('Y-m-d', $v['start_time']);
            $info[$k]['end_time'] = date('Y-m-d', $v['end_time']);
            $info[$k]['pay_start_time'] = date('Y-m-d', $v['pay_start_time']);
            $info[$k]['pay_end_time'] = date('Y-m-d', $v['pay_end_time']);
            $info[$k]['send_goods_time'] = date('Y-m-d', $v['send_goods_time']);
        }
        $this->assign('goods_name',$goods_info['goods_name']);
        $this->assign('info',$info);
        if($_REQUEST['type']=='edit'){
            $this->fetch('template/'.$this->module.'/editPresell');
        }else{
            $this->fetch('template/'.$this->module.'/presellDetail');
        }
    }

    //增加
    public function addpresell(){
        if(request()->isAjax()) {
            $data['name'] = $_REQUEST['name']?$_REQUEST['name']:'';
            $data['goods_id'] =  $_REQUEST['goods_id']?$_REQUEST['goods_id']:'';
            $data['sku_id'] =  $_REQUEST['sku_id']?$_REQUEST['sku_id']:'';
            $data['start_time'] = strtotime($_REQUEST['start_time']);
            $data['end_time'] = strtotime($_REQUEST['end_time'].' 23:59:59');         //活动结束时间
            $data['pay_start_time'] = strtotime($_REQUEST['pay_start_time']);              //支付开始时间
            $data['pay_end_time'] = strtotime($_REQUEST['pay_end_time'].' 23:59:59'); //支付尾款时间
            $data['send_goods_time'] = strtotime($_REQUEST['send_goods_time']);            //发货时间
            $data['active_status'] = $_REQUEST['active_status']?$_REQUEST['active_status']:'1';
            $data['allmoney'] = $_REQUEST['allmoney']?$_REQUEST['allmoney']:'0';         //总价
            $data['firstmoney'] = $_REQUEST['firstmoney']?$_REQUEST['firstmoney']:'';    //定金
            $data['presellnum'] = $_REQUEST['presellnum']?$_REQUEST['presellnum']:'1';   //库存
            $data['maxbuy'] = $_REQUEST['maxbuy']?$_REQUEST['maxbuy']:'0';               //限购
            $data['vrnum'] = $_REQUEST['vrnum']?$_REQUEST['vrnum']:'0';                 //虚拟数量
            $data['create_time'] = time();
            $data['website_id'] = $this->website_id;
            $data['shop_id'] = $this->instance_id;
            if(empty($data['goods_id'])){
                $data['code'] = -1;
                $data['message'] = "请选择商品";
                ob_clean();
                echo json_encode($data);exit;
            }
            if($data['allmoney']<$data['firstmoney'] || $data['allmoney']==$data['firstmoney']){
                $data['code'] = -1;
                $data['message'] = "预售价格必须大于定金";
                ob_clean();
                echo json_encode($data);exit;
            }

            if($data['start_time']>$data['end_time']){
                $data['code'] = -1;
                $data['message'] = "开始时间不能大于结束时间";
                ob_clean();
                echo json_encode($data);exit;
            }
            $now_time = strtotime(date('Y-m-d 00:00:00'));
            if ($data['end_time'] < $now_time) {
                $data['code'] = -1;
                $data['message'] = "结束时间不能小于当前时间";
                ob_clean();
                echo json_encode($data);exit;
            }
            if($data['pay_start_time']>$data['pay_end_time']){
                $data['code'] = -1;
                $data['message'] = "支付开始时间不能大于结束时间";
                ob_clean();
                echo json_encode($data);exit;
            }
            if($data['pay_start_time']<$data['start_time']){
                $data['code'] = -1;
                $data['message'] = "支付时间不能小于活动开始时间";
                ob_clean();
                echo json_encode($data);exit;
            }

            if($data['pay_end_time']<$data['end_time']){
                $data['code'] = -1;
                $data['message'] = "支付结束时间不能小于活动结束时间";
                ob_clean();
                echo json_encode($data);exit;
            }

            if($data['send_goods_time']<$data['pay_start_time']){
                $data['code'] = -1;
                $data['message'] = "发货时间不能小于支付开始时间";
                ob_clean();
                echo json_encode($data);exit;
            }
//var_dump($_REQUEST);exit;
            $presell = new pre_sell();
            if(!empty($_REQUEST['goods_info'])){
                $new_array = '';
                $sku_data = explode('§',$_REQUEST['goods_info']);
                unset($sku_data[0]);
                array_values($sku_data);
                foreach ($sku_data as $k=>$v){
                    $num = explode(',',$v);
                    $new_array[$num['0']]['all_money'] = $num[1];
                    $new_array[$num['0']]['first_money'] = $num[2];
                    $new_array[$num['0']]['presell_num'] = $num[3];
                    $new_array[$num['0']]['max_buy'] = $num[4];
                    $new_array[$num['0']]['vr_num'] = $num[5];
                    if(!empty($_REQUEST['presell_id'])){
                        $new_array[$num['0']]['presell_goods_id'] = $num[6];
                    }
                    $new_array[$num['0']]['shop_id'] = $this->instance_id;
                }
            }else{
                $new_array = '';
            }
            //编辑则重置数据
            if(!empty($_REQUEST['presell_id'])){
                $condition['id'] = $_REQUEST['presell_id'];
                $result = $presell->update_presell($data,$new_array,$condition);
            }else{
                $result = $presell->add_presell($data,$new_array);
            }

            if($result){
                $data['code'] = 1;
                $data['message'] = "添加成功";
                ob_clean();
                echo json_encode($data);exit;
            }else{
                $data['code'] = -1;
                $data['message'] = "添加失败";
                ob_clean();
                echo json_encode($data);exit;
            }

        }

        $this->fetch('template/'.$this->module.'/addPresell');
    }

    //订购记录
    public function orderrecord(){
        if(request()->isAjax()) {
            $order_status = $_REQUEST['status']?$_REQUEST['status']:'';
            if($order_status=='1'){//订购成功
                $condition['pay_status'] = 2;
                $condition['money_type'] = 2;
            }
            if($order_status=='2'){//待付定金
                $condition['order_status'] = ['neq', 5];
                $condition['pay_status'] = 0;
                $condition['money_type'] = 0;
                $condition['presell_id'] = ['>',0];
            }
            if($order_status=='3'){//待付尾款
                $condition['order_status'] = ['neq', 5];
                $condition['money_type'] = 1;
                $condition['pay_status'] = 0;
            }
            if($order_status=='4'){//订购失败
                $condition['order_status'] = 5;
            }
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $goods_name = request()->post('goods_name', '');
            $condition['is_deleted'] = 0; // 未删除订单
            $condition['presell_id'] = request()->post('presell_id', ''); // 未删除订单
            if ($goods_name) {
                $condition['goods_name'] = ['LIKE', '%' . $goods_name . '%'];
            }

//            if ($order_status != '') {
//                // $order_status 1 待发货
//                if ($order_status == 1) {
//                    // 订单状态为待发货实际为已经支付未完成还未发货的订单
//                    $condition['shipping_status'] = 0; // 0 待发货
//                    $condition['pay_status'] = 2; // 2 已支付
//                    $condition['order_status'][] = array(
//                        'neq',
//                        4
//                    ); // 4 已完成
//                    $condition['order_status'][] = array(
//                        'neq',
//                        5
//                    ); // 5 关闭订单
//                    $condition['vgsr_status'] = 2;
//                } elseif($order_status == 10){// 拼团，已支付未成团订单
//                    $condition['vgsr_status'] = 1;
//                } else {
//                    $condition['order_status'] = $order_status;
//                }
//
//            }
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            $order_service = new OrderService();
            $list = $order_service->getOrderList($page_index, $page_size, $condition, 'create_time desc');
            ob_clean();
            print_r(json_encode($list));exit;
        }
        $this->assign('presell_id',$_REQUEST['id']);
        $this->fetch('template/'.$this->module.'/presellRecord');
    }

    function object2array(&$object) {
        $object =  json_decode( json_encode( $object),true);
        return  $object;
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

    public function returnJson($data){
        ob_clean();
        $result = json_encode($data);
        header('Content-Type:application/json');
        echo $result;exit;
    }


}