<?php
namespace addons\bargain\controller;

use addons\bargain\model\VslBargainDetailModel;
use addons\bargain\model\VslBargainRecordModel;
use data\model\AddonsConfigModel;
use addons\bargain\Bargain AS baseBargain;
use data\model\UserModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderModel;
use data\service\Goods;
use \addons\bargain\service\Bargain AS bargainServer;
use data\service\Goods as GoodsService;
use data\service\Order\Order;
use data\service\User;

class Bargain extends baseBargain
{
    public function __construct()
    {
        parent::__construct();
        $this->bargainServer = new bargainServer();
    }

    /*
     * 添加设置
     * **/
    public function addBargainConfig()
    {
//        p(request()->param());exit;
        try {
            $post_data = request()->post();
            $post_data['bonus_val'] = json_encode($post_data['bonus_val'], JSON_UNESCAPED_UNICODE);
            $post_data['distribution_val'] = json_encode($post_data['distribution_val'], JSON_UNESCAPED_UNICODE);
            $is_bargain = $post_data['is_bargain'];
            $addons_config_model = new AddonsConfigModel();
            $bargain_info = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => 'bargain']);
            if (!empty($bargain_info)) {
                $res = $addons_config_model->save(
                    [
                        'is_use' => $is_bargain,
                        'modify_time' => time(),
                        'value' => json_encode($post_data, JSON_UNESCAPED_UNICODE)
                    ],
                    [
                        'website_id' => $this->website_id,
                        'addons' => 'bargain'
                    ]
                );
            } else {
                $data['is_use'] = $is_bargain;
                $data['value'] = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                $data['desc'] = '砍价设置';
                $data['create_time'] = time();
                $data['addons'] = 'bargain';
                $data['website_id'] = $this->website_id;
                $res = $addons_config_model->save($data);
            }
            if($res){
                $this->addUserLog('添加砍价设置',$res);
            }
            setAddons('bargain', $this->website_id, $this->instance_id);
            setAddons('bargain', $this->website_id, $this->instance_id, true);
            return ajaxReturn($res);
        } catch (\Exception $e) {
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }
    /*
     * 添加砍价活动
     * **/
    public function addBargain()
    {
        $bargain_server = new bargainServer();
        //获取参数并判断
        $data['bargain_name'] = request()->post('bargain_name','');
        $data['goods_id'] = request()->post('goods_id',0);
        //初始金额
        $data['start_money'] = request()->post('start_money',0);
        if(!$data['start_money']){
            return ['code'=>-1,'message'=>'初始金额不能为空并且要大于0'];
        }
        //最低砍至金额
        $data['lowest_money'] = request()->post('lowest_money',0);
        if($data['lowest_money'] >= $data['start_money']){
            return ['code'=>-1,'message'=>'最低砍至的金额不能大于或者等于初始金额'];
        }
        //第一刀
        $data['first_bargain_money'] = request()->post('first_bargain_money',1);
        if($data['first_bargain_money'] > $data['start_money']){
            return ['code'=>-1,'message'=>'第一刀砍价金额不能大于初始金额'];
        }
        //单次砍价金额
        $data['bargain_method'] = request()->post('bargain_method',1);
        //固定金额
        $data['fix_money'] = request()->post('fix_money',1);
        if($data['fix_money'] > $data['start_money']){
            return ['code'=>-1,'message'=>'最低砍价金额不能大于初始金额'];
        }
        $data['rand_lowest_money'] = request()->post('rand_lowest_money',0);
        $data['rand_highest_money'] = request()->post('rand_highest_money',0);
        if ($data['rand_lowest_money'] && $data['rand_highest_money']) {
            if($data['rand_lowest_money'] > $data['rand_highest_money']){
                return ['code'=>-1,'message'=>'随机最低砍价金额不能大于随机最高金额'];
            }else{
                if ($data['rand_lowest_money'] > $data['start_money'] || $data['rand_highest_money'] > $data['start_money']) {
                    return ['code'=>-1,'message'=>'随机最低/最高砍价金额不能大于起始金额'];
                }
            }
        }
        $start_bargain_time = request()->post('start_bargain_time',0);
        $end_bargain_time = request()->post('end_bargain_time',0);
        $start_bargain_time = $start_bargain_time.' 00:00:00';
        $end_bargain_time = $end_bargain_time.' 23:59:59';
        if($start_bargain_time && $end_bargain_time){
            $data['start_bargain_time'] = strtotime($start_bargain_time);
            $data['end_bargain_time'] = strtotime($end_bargain_time);
            if($data['start_bargain_time'] > $data['end_bargain_time']){
                return ['code'=>-1,'message'=>'开始时间要小于结束时间'];
            }
            //结束时间不能小于当前时间
            $now_time = strtotime(date('Y-m-d 00:00:00'));
            if ($data['end_bargain_time'] < $now_time) {
                return ['code'=>-1,'message'=>'结束时间不能小于当前时间'];
            }
        }else{
            return ['code'=>-1,'message'=>'开始时间和结束时间不能为空'];
        }
        //活动库存
        $data['bargain_stock'] = request()->post('bargain_stock',0);
        //限购
        $data['limit_buy'] = request()->post('limit_buy',0);
        //我是否可以砍价
        $data['is_my_bargain'] = request()->post('is_my_bargain',0);
        $bargain_id = request()->post('bargain_id',0);
        $retval = $bargain_server->addBargain($data, $bargain_id);
        if($retval['code'] <0){
            return ['code'=>-1,'message'=>$retval['message']];
        }else{
            $this->addUserLog('添加砍价活动',$retval);
            return ajaxReturn($retval['code']);
        }
//        var_dump($_POST);exit;
    }
    /**
     * 砍价商品选择
     */
    public function bargainDialogGoodsList()
    {
        if (request()->post('page_index')) {
            $index = request()->post('page_index', 1);
            $goods_type = request()->post('goods_type', 1);
            $search_text = request()->post('search_text');
            if ($search_text) {
                $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['ng.website_id'] = $this->website_id;
            $condition['ng.shop_id'] = $this->instance_id;
            $condition['ng.state'] = 1;
            //0自营店 1全平台
            if ($goods_type == '0') {
                $condition['ng.shop_id'] = $this->instance_id;
            }
            $goods = new Goods();
            $list = $goods->getModalGoodsList($index, $condition);
            return $list;
        }
        $this->fetch('template/' . $this->module . '/bargainGoodsDialog');
    }
    /*
     * 获取砍价列表
     * **/
    public function bargainListUrl()
    {
        //当前页数
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $search_goods_text = request()->post('search_text','');
        if($search_goods_text){
            $condition['goods_name'] = ['like', '%'.$search_goods_text.'%'];
        }
        //状态 全部-无时间显示  待开始-time()< start_bargain_time 进行中-time()> start_bargain_eime time()< end_bargain_time 已结束-time()>end_bargain_time
        $search_status = request()->post('bargain_type');
        $time = time();
        switch($search_status){
            case 'unstart':
                $condition['b.website_id'] = $this->website_id;
                $condition['b.shop_id'] = $this->instance_id;
                $condition['start_bargain_time'] = ['>', $time];
                break;
            case 'going':
                $condition['b.website_id'] = $this->website_id;
                $condition['b.shop_id'] = $this->instance_id;
                $condition['start_bargain_time'] = ['<', $time];
                $condition['end_bargain_time'] = ['>', $time];
                break;
            case 'ended':
                $condition['b.website_id'] = $this->website_id;
                $condition['b.shop_id'] = $this->instance_id;
                $condition['end_bargain_time'] = ['<', $time];
                break;
            default:
                $condition['b.website_id'] = $this->website_id;
                $condition['b.shop_id'] = $this->instance_id;
                break;
        }
        $order = 'bargain_id DESC';
        $bargain_list = $this->bargainServer->bargainList($page_index, $page_size, $condition, $order);
        return $bargain_list;
    }
    /*
     * 活动记录
     * **/
    public function bargainRecordUrl()
    {
        //当前页数
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $bargain_id = request()->post('bargain_id','');
        $bargain_status = request()->post('bargain_status','');
        switch($bargain_status){
            case 'payed':
                $condition['br.bargain_status'] = 2;
                break;
            case 'going':
                $condition['br.bargain_status'] = 1;
                $condition['b.end_bargain_time'] = ['>=', time()];
                break;
            case 'fail':
                $condition['br.bargain_status'] = 1;
                $condition['b.end_bargain_time'] = ['<', time()];
                break;
        }
        $bargain_time = request()->post('bargain_time','');
        $bargain_time_arr = explode(' - ', $bargain_time);
        $start_bargain_time = strtotime(trim($bargain_time_arr[0]));
        $end_bargain_time = strtotime(trim($bargain_time_arr[1]));
        if($start_bargain_time && $end_bargain_time){
            $condition['br.create_time'] = [
                ['>=',$start_bargain_time],['<=',$end_bargain_time]
            ];
        }
        $condition['b.website_id'] = $this->website_id;
        $condition['b.shop_id'] = $this->instance_id;
        $condition['b.bargain_id'] = $bargain_id;
        $search_goods_text = request()->post('search_text','');
        if($search_goods_text){
            $condition['goods_name'] = ['like', '%'.$search_goods_text.'%'];
        }
        $order = 'br.create_time';
        $record_list = $this->bargainServer->getBargainRecord($page_index,$page_size,$condition,$order);
        return $record_list;
    }
    /*
     * 关闭活动
     * **/
    public function bargainClose()
    {
        $bargain_id = request()->post('bargain_id', 0);
        $res = $this->bargainServer->bargainClose($bargain_id);
        if($res){
            $this->addUserLog('添加砍价活动',$bargain_id);
        }
        return ajaxReturn($res);
    }
    /*
     * 移除活动
     * **/
    public function bargainDelete()
    {
        $bargain_id = request()->post('bargain_id', 0);
        $res = $this->bargainServer->bargainDelete($bargain_id);
        if($res){
            $this->addUserLog('移除砍价活动',$bargain_id);
        }
        return ajaxReturn($res);
    }

    /********************************前端接口开始*******************************************/

    /*
     * 获取前端的预售列表
     * **/
    public function getBargainList()
    {
        //当前页数
        if(getAddons('bargain', $this->website_id)){
            $page_index = request()->post('page_index',1);
            $page_size = request()->post('page_size')?:PAGESIZE;
            $shop_id = request()->post('shop_id');
            $time = time();
            if (isset($shop_id)) { //by sgw
                $condition['b.shop_id'] = $shop_id;
            }
            $condition['b.website_id'] = $this->website_id;
            //获取没有过期的活动
            $condition['end_bargain_time'] = ['>', $time];
            $condition['close_status'] = 1;
            $order = 'bargain_id DESC';
            // 获取该用户的权限
            if($this->uid) {
                $userService = new User();
                $userLevle = $userService->getUserLevelAndGroupLevel($this->uid);// code | <0 错误; 1系统会员; 2;分销商; 3会员
                if (!empty($userLevle)) {
                $sql1 = '';
                $sql2 = '(';
                // 会员权限
                if ($userLevle['user_level']) {
                    $u_id = $userLevle['user_level'];
                    $sql1 .= "instr(CONCAT( ',', vgd.browse_auth_u, ',' ), ',".$u_id.",' ) OR ";
                    $sql2 .= "vgd.browse_auth_u IS NULL OR vgd.browse_auth_u = '' ";
                }
                // 分销商权限
                if ($userLevle['distributor_level']) {
                    $d_id = $userLevle['distributor_level'];
                    $sql1 .= "instr(CONCAT( ',', vgd.browse_auth_d, ',' ), ',".$d_id.",' ) OR ";
                        $sql2 .= " OR vgd.browse_auth_d IS NULL OR vgd.browse_auth_d = '' ";
                }
                // 标签权限
                if ($userLevle['member_group']) {
                    $g_ids = explode(',',$userLevle['member_group']);
                    foreach ($g_ids as $g_id) {
                        $sql1 .= "instr(CONCAT( ',', vgd.browse_auth_s, ',' ), ',".$g_id.",' ) OR ";
                            $sql2 .= " OR vgd.browse_auth_s IS NULL OR vgd.browse_auth_s = '' ";
                }
                } else {
                    $sql1 .= "  ";
                }
                $sql2 .= " )";
                $condition[] = ['exp', $sql1 . $sql2];
                }
            }

            $bargain_list = $this->bargainServer->frontBargainList($page_index, $page_size, $condition, $order);
        }else{
            $bargain_list = [
                'code'=>0,
                'data'=>[
                    'bargain_list'=>[],
                    "total_count"=>0,
                    "page_count"=>0
                ],
            ];
        }
        return $bargain_list;
    }
    /*
     * 我要砍价页面
     * **/
    public function myActionBargain()
    {
        //如果uid不存在，则提示他去登陆
        if (empty($this->uid)) {
            echo json_encode(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录'], JSON_UNESCAPED_UNICODE);exit;
        }
        $bargain_id = request()->post('bargain_id',0);
        $goods_id = request()->post('goods_id',0);
        $bargain_uid = request()->param('bargain_uid',0);
        if(!$goods_id && !$bargain_id && !$bargain_uid){
            return json(['code'=>-1, 'message'=>'缺少参数']);
        }
        $uid = $this->uid;
        if($bargain_uid == $uid){
            $uid = $this->uid;
            //页面标识
            $is_my_bargain = true;
            //帮砍标识
            $is_help_bargain = false;
        }else{//登录的uid和get传过来的id不等则说明是帮砍页面
            //帮砍标识
            $is_help_condition['br.uid'] = $bargain_uid;//参加活动的uid
            $is_help_condition['br.bargain_id'] = $bargain_id;//参加活动的uid
            $is_help_condition['bd.help_uid'] = $uid;//帮砍人的uid
            $bargain_record_mdl = new VslBargainRecordModel();
            $is_help_list = $bargain_record_mdl->alias('br')->join('vsl_bargain_detail bd','br.bargain_record_id=bd.bargain_record_id','LEFT')->where($is_help_condition)->find();
            if($is_help_list){
                $is_help_bargain = false;
            }else{
                $is_help_bargain = true;
            }
            $uid = $bargain_uid;
            //页面标识
            $is_my_bargain = false;
        }
        //判断是否开启了自己砍价的操作
        $condition['website_id'] = $this->website_id;
        $condition['bargain_id'] = $bargain_id;
        $condition['goods_id'] = $goods_id;
        $bargain_list = $this->bargainServer->isBargain($condition);
//        var_dump($uid);
        $website_id = $this->website_id;
        if ($bargain_list) {
            //如果我的my_bargain为空的，则说明是第一次进来，先判断是否可以自己砍价
            $bargain_list['my_bargain'] = objToArr($bargain_list['my_bargain']);
            if (!$bargain_list['my_bargain'] && ($bargain_uid == $this->uid)) {//是我的页面的时候才创建活动
                //执行砍价
                $data['uid'] = $uid;
                $data['website_id'] = $website_id;
                $data['bargain_id'] = $bargain_id;
                if($bargain_list['is_my_bargain'] == 1){//我是否可以砍价 1是可以
                    //我 砍价金额
                    $bargain_method = $bargain_list['bargain_method'];
                    if($bargain_method == 1){
                        $my_bargain_price = $bargain_list['fix_money'];
                    }else{
                        $my_bargain_price = round(rand($bargain_list['rand_lowest_money'], $bargain_list['rand_highest_money']), 2);
                    }
                    if ($bargain_list['first_bargain_money'] > 0) {//如果第一刀有值则取第一刀的
                        //设置了第一刀，但是有 砍价的最低金额、随机砍价金额
//                        $my_bargain_price = $bargain_list['first_bargain_money'] > $my_bargain_price ? $my_bargain_price : $bargain_list['first_bargain_money'];
                        $my_bargain_price = $bargain_list['first_bargain_money'];
                        //看下是否符合砍至最低金额的差值
                        $lowest_bargain_price = $bargain_list['start_money'] - $bargain_list['lowest_money'];
                        $my_bargain_price = $my_bargain_price > $lowest_bargain_price ? $lowest_bargain_price : $my_bargain_price;
                    }
                }else{
                    $my_bargain_price = 0;
                }
                $data['now_bargain_money'] = $bargain_list['start_money'] - $my_bargain_price;
                $data['start_money'] = $bargain_list['start_money'];
                $data['already_bargain_money'] = $my_bargain_price;
                $data['bargain_status'] = 1; //1为砍价中 2位已支付
                $data['create_time'] = time();
                $res = $this->bargainServer->addMyBargain($data);
                //插入自己砍价记录
                if($res && $bargain_list['is_my_bargain'] == 1 && $my_bargain_price > 0){
                    $bargain_detail = new VslBargainDetailModel();
                    $bargain_detail_data['bargain_record_id'] = $res;
                    $bargain_detail_data['help_uid'] = $uid;
                    $bargain_detail_data['help_price'] = $my_bargain_price;
                    $bargain_detail_data['before_price'] = $bargain_list['start_money'];
                    $bargain_detail_data['after_price'] = $data['now_bargain_money'];
                    $bargain_detail_data['create_time'] = time();
                    $bargain_detail->save($bargain_detail_data);
                }
            }
            //重新获取我的砍价详情
            $detail_condition['b.website_id'] = $website_id;
            $detail_condition['br.bargain_id'] = $bargain_id;
            $detail_condition['br.uid'] = $uid;
//            p($detail_condition);exit;
            $bargain_detail_list = $this->bargainServer->getFrontBargainDetail($detail_condition);
//            p($bargain_detail_list);exit;
            $bargain_detail_list['is_my_bargain'] = $is_my_bargain;
            $bargain_detail_list['is_help_bargain'] = $is_help_bargain;
            //获取商品的属性、sku
            $goods_server = new GoodsService();
            $goods_data = $goods_server->getGoodsDetail($goods_id);
            if (!empty($goods_data['spec_list']) && $goods_data['spec_list'] != '[]') {
                foreach ($goods_data['spec_list'] as $i => $spec_info) {
                    $temp_spec = [];
                    foreach ($spec_info['value'] as $s => $spec_value) {
                        $temp_spec['k'] = $spec_info['spec_name'];
                        $temp_spec['k_id'] = $spec_info['spec_id'];
                        $temp_spec['v'][$s]['id'] = $spec_value['spec_value_id'];
                        $temp_spec['v'][$s]['name'] = $spec_value['spec_value_name'];
                        $temp_spec['k_s'] = 's' . $i;
                        $spec_obj[$spec_info['spec_id']] = $temp_spec['k_s'];
                        $goods_detail['sku']['tree'][$spec_info['spec_id']] = $temp_spec;
                    }
                }
                //接口需要tree是数组，不是对象，去除tree以spec_id为key的值
                $goods_detail['sku']['tree'] = array_values($goods_detail['sku']['tree']);
            }
            //sku
            foreach ($goods_data['sku_list'] as $k => $sku) {
                $temp_sku['id'] = $sku['sku_id'];
                $temp_sku['sku_name'] = $sku['sku_name'];
                $temp_sku['min_buy'] = 1;
                //获取max_buy 限购
                $limit_buy = $bargain_list['limit_buy'];
                $order = new Order();
                $bargain_stock = $bargain_list['bargain_stock'];
                $buy_num = $order->getActivityOrderSkuNum($uid, $goods_id, $website_id,3, $bargain_id);//3是砍价
                $max_buy = $limit_buy - $buy_num;
                $temp_sku['max_buy'] = $max_buy > 0 ? ($max_buy > $bargain_stock ? $bargain_stock : $max_buy) : 0;
                $temp_sku['stock_num'] = $bargain_stock;
                $temp_sku['attr_value_items'] = $sku['attr_value_items'];
                $sku_temp_spec_array = explode(';', $sku['attr_value_items_format']);
                $temp_sku['s'] = [];
                foreach ($sku_temp_spec_array as $spec_id => $spec_combination) {
                    $explode_spec = explode(':', $spec_combination);
                    $spec_id = $explode_spec[0];
                    $spec_value_id = $explode_spec[1];

                    // ios wants string
                    if ($spec_value_id) {
                        $temp_sku['s'][] = (string)$spec_value_id;
                        $temp_sku[$spec_obj[$spec_id] ?: 's0'] = (int)$spec_value_id;
                    }
                }
                $goods_detail['sku']['list'][] = $temp_sku;
            }
            $bargain_detail_list['sku'] = $goods_detail['sku'];
            $bargain_detail_list['goods_type'] = $goods_data['goods_type'];
            if($goods_data['goods_type'] == 4) {
                //知识付费商品，如果是已经购买过的就不能再购买
                $order_goods_model = new VslOrderGoodsModel();
                $order_model = new VslOrderModel();
                $data = [
                    'website_id' => $this->website_id,
                    'buyer_id' => $uid,
                    'goods_id' => $goods_id
                ];
                $order_list = $order_goods_model->getQuery($data, 'order_id','order_id ASC');
                if ($order_list) {
                    foreach ($order_list as $k => $v) {
                        $order_status = $order_model->getInfo(['order_id' => $v['order_id']], 'order_status');
                        if($order_status['order_status'] == 4) {
                            $bargain_detail_list['is_buy'] = true;
                            continue;
                        }else{
                            $bargain_detail_list['is_buy'] = false;
                        }
                    }
                } else {
                    $bargain_detail_list['is_buy'] = false;
                }
                if($is_my_bargain && $bargain_detail_list['is_buy']) {
                    return ['code'=>-1, 'message'=>'您已购买过此商品'];
                }
            }
        }else{
            return ['code'=>-1, 'message'=>'砍价活动已结束或已关闭'];
        }
//        echo json_encode($bargain_detail_list);exit;
        return json([
            'code'=> 0,
            'data'=> $bargain_detail_list
        ]);
    }
    /*
     * 邀请好友砍价
     * **/
    public function helpBargain()
    {
        //如果uid不存在，则提示他去登陆
        if (empty($this->uid)) {
            echo json_encode(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录'], JSON_UNESCAPED_UNICODE);exit;
        }
        $bargain_record_mdl = new VslBargainRecordModel();
        $bargain_detail_mdl = new VslBargainDetailModel();
        $bargain_record_mdl->startTrans();
        try{
            $bargain_record_id = request()->post('bargain_record_id',0);
            $condition['bargain_record_id'] = $bargain_record_id;
            $help_uid = $this->uid;
            $is_already_bargain_condition['bargain_record_id'] = $bargain_record_id;
            $is_already_bargain_condition['help_uid'] = $help_uid;
            $is_help_bargain = $bargain_detail_mdl->getInfo($is_already_bargain_condition);
            if(!$is_help_bargain){
                //查出该活动设置的砍价方法
                $bargain_record_list = $this->bargainServer->getBargainByRecord($bargain_record_id);
                if($this->uid != $bargain_record_list['uid']){//登录的uid和参加活动的uid不能相同
                    $is_other_bargain = $bargain_detail_mdl->getInfo(['bargain_record_id'=>$bargain_record_id]);
                    $first_bargain_money = $bargain_record_list['first_bargain_money'];
                    $is_my_bargain  = $bargain_record_list['is_my_bargain'];
                    if (!$is_other_bargain && !$is_my_bargain && $first_bargain_money > 0) {//是否设置了第一刀价格 并且没有设置自己砍价 并且没有人砍过，就砍设置的价格
                        $help_bargain_arr1['bargain_record_id'] = $bargain_record_id;
                        $help_bargain_arr1['help_uid'] = $help_uid;
                        $help_bargain_arr1['help_price'] = $first_bargain_money;
                        $help_bargain_arr1['before_price'] = $bargain_record_list['now_bargain_money'];
                        $help_bargain_arr1['after_price'] = $bargain_record_list['now_bargain_money'] - $first_bargain_money;
                        $help_bargain_arr1['create_time'] = time();
                        $bargain_detail_mdl->save($help_bargain_arr1);
                        //更新record表
                        $lowest_bargain_price = $bargain_record_list['now_bargain_money'] - $bargain_record_list['lowest_money'];
                        $bargain_money = $first_bargain_money > $lowest_bargain_price ? $lowest_bargain_price : $first_bargain_money;
                        $bargain_record_arr['now_bargain_money'] = $bargain_record_list['now_bargain_money'] - $bargain_money;
                        $bargain_record_arr['already_bargain_money'] = $bargain_record_list['already_bargain_money'] + $bargain_money;
                        $bargain_record_arr['help_count'] = $bargain_record_list['help_count'] + 1;
                        $bargain_record_arr['update_time'] = time();
                        $bargain_record_mdl->where(['bargain_record_id'=>$bargain_record_id])->update($bargain_record_arr);
                    }else{
                        if($bargain_record_list['bargain_method'] == 1){
                            $bargain_money = $bargain_record_list['fix_money'];
                        }else{
                            //保留2位小数
                            $bargain_record_list['rand_lowest_money'] = $bargain_record_list['rand_lowest_money'] * 100;
                            $bargain_record_list['rand_highest_money'] = $bargain_record_list['rand_highest_money'] * 100;
                            $rand_money = rand($bargain_record_list['rand_lowest_money'], $bargain_record_list['rand_highest_money']);
                            $bargain_money = $rand_money/100;
//                            $bargain_money = round(rand($bargain_record_list['rand_lowest_money'], $bargain_record_list['rand_highest_money']), 2);
                        }
                        //看下是否符合砍至最低金额的差值
                        $lowest_bargain_price = $bargain_record_list['now_bargain_money'] - $bargain_record_list['lowest_money'];
                        $bargain_money = $bargain_money > $lowest_bargain_price ? $lowest_bargain_price : $bargain_money;
                        $bargain_money = $bargain_record_list['now_bargain_money'] > $bargain_money ? $bargain_money : $bargain_record_list['now_bargain_money'];
                        $bargain_record_arr['now_bargain_money'] = $bargain_record_list['now_bargain_money'] - $bargain_money;
                        $bargain_record_arr['already_bargain_money'] = $bargain_record_list['already_bargain_money'] + $bargain_money;
                        $bargain_record_arr['help_count'] = $bargain_record_list['help_count'] + 1;
                        $bargain_record_arr['update_time'] = time();
                        $bargain_record_mdl->where(['bargain_record_id'=>$bargain_record_id])->update($bargain_record_arr);
                        $help_bargain_arr['bargain_record_id'] = $bargain_record_id;
                        $help_bargain_arr['help_uid'] = $help_uid;
                        $help_bargain_arr['help_price'] = $bargain_money;
                        $help_bargain_arr['before_price'] = $bargain_record_list['now_bargain_money'];
                        $help_bargain_arr['after_price'] = $bargain_record_list['now_bargain_money'] - $bargain_money;
                        $help_bargain_arr['create_time'] = time();
                        $bargain_detail_mdl->save($help_bargain_arr);
                    }
                    $bargain_record_mdl->commit();
                    return json(['code'=>0, 'message'=>'帮砍成功']);
                }else{
                    return json(['code'=>-1, 'message'=>'不能给自己砍价']);
                }
            }else{
                return json(['code'=>-1, 'message'=>'您已经帮砍过了']);
            }
        }catch(\Exception $e){
            $bargain_record_mdl->rollback();
            return json(['code'=>-1, 'message'=>$e->getMessage()]);
        }
    }
}