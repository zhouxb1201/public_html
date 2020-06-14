<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\channel\controller;

use addons\channel\Channel as baseChannel;
use addons\channel\model\VslChannelCartModel;
use addons\channel\model\VslChannelGoodsModel;
use addons\channel\model\VslChannelGoodsSkuModel;
use addons\channel\model\VslChannelLevelModel;
use addons\channel\model\VslChannelModel;
use addons\channel\model\VslChannelOrderModel;
use addons\channel\server\Channel as ChannelServer;
use addons\channel\model\VslChannelGoodsdelInfoModel;
use addons\shop\model\VslShopModel;
use data\model\AddonsConfigModel;
use data\model\VslGoodsDiscountModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslMemberExpressAddressModel;
use data\model\VslMemberModel;
use data\service\Config;
use data\service\Goods;
use data\service\promotion\GoodsExpress;
use data\service\Member as MemberService;
use data\service\Order\Order as OrderBusiness;
use \data\service\Order as OrderService;
use data\model\UserModel;
use data\service\Member;
use data\service\Member\MemberAccount as MemberAccount;
use addons\distribution\model\VslDistributorAccountModel;
use think\Db;

class Channel extends baseChannel
{
    public function __construct()
    {
        parent::__construct();
        $this->goods = new Goods();
    }
    /*
     * 渠道商列表
     * **/
    public function channelList()
    {
//        var_dump($_POST);exit;
        //当前页数
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        //电话
        $channel_phone = request()->post('channel_phone','');
        if(!empty($channel_phone)){
            $condition['u.user_tel'] = ['like','%'.$channel_phone.'%'];
        }
        //名字
        $name = request()->post('name','');
        if(!empty($name)){
            $condition['u.user_name'] = $name;
        }
        //等级
        $channel_grade = request()->post('channel_grade','');
        if(!empty($channel_grade)){
            $condition['c.channel_grade_id'] = $channel_grade;
        }
        //审核状态
        $status = request()->post('status','');
        if($status !== ''){
            if($status !== '2'){
                $condition['c.status'] = $status;
            }
        }
        //channel_id
        $uid = request()->post('uid',0);
        $channel_id = request()->post('channel_id',0);
        $check_status = request()->post('check_status','');
        $channel_server = new ChannelServer();
        //下级渠道商
        if($uid && $channel_id){
            //获取渠道商信息
            $channel_condition['c.channel_id'] = $channel_id;
            $channel_condition['c.website_id'] = $this->website_id;
            $channel_list = $channel_server->getMyChannelInfo($channel_condition);
            $channel_info['name'] = $channel_list['nick_name']?:$channel_list['user_name'];
            $channel_info['user_headimg'] = getApiSrc($channel_list['user_headimg']);
            $channel_info['channel_name'] = $channel_list['channel_real_name']?:$channel_list['nick_name'];
            $channel_info['channel_phone'] = $channel_list['channel_phone']?:$channel_list['user_tel'];
            $my_grade_weight = $channel_list['weight'];
//            echo '<pre>';print_r($channel_list);exit;
            $condition1['m.referee_id'] = $uid;
            $condition1['m.website_id'] = $this->website_id;
            $condition1['c.status'] = 1;
            $condition1['cl.weight'] = ['<=', $my_grade_weight];
            $channel_id_arr = $channel_server->getAllDownChannelId($condition1);
            $channel_num = count($channel_id_arr);
            $channel_info['channel_num'] = $channel_num;
//        var_dump(objToArr($channel_info));exit;
            if( !empty($channel_id_arr) ){
                $channel_id_str = implode(',', $channel_id_arr);
                $condition['c.channel_id'] = ['in', $channel_id_str];
            }else{
                $condition['c.channel_id'] = 0;
            }
            if($check_status !== ''){
                $condition['c.status'] = $check_status;
            }
        }
        $condition['c.website_id'] = $this->website_id;
//        var_dump($condition);exit;
        $channel_list = $channel_server->getChannelList($page_index, $page_size, $condition, 'c.create_time');
        if($channel_info){
            $channel_list['channel_info'] = $channel_info;
        }
        //处理渠道商头像
        foreach($channel_list['data'] as $k=>$v){
            $user_headimg = $v['user_headimg'];
            $channel_list['data'][$k]['user_headimg'] = getApiSrc($user_headimg);
        }
        return $channel_list;
    }
    /*
     * 采购列表
     * **/
    public function purchaseList()
    {
        //当前页数
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $uid = request()->post('uid',0);
        $channel_id = request()->post('channel_id',0);
        if(empty($uid) && empty($channel_id)){
            return json(['code'=>1,'message'=>'缺少参数']);
        }
        $channel_server = new ChannelServer();
        //获取渠道商信息
        $channel_condition['c.channel_id'] = $channel_id;
        $channel_condition['c.website_id'] = $this->website_id;
        $channel_list = $channel_server->getMyChannelInfo($channel_condition);
        $my_grade_weight = $channel_list['weight'];
        $purchase_discount = $channel_list['purchase_discount'];
        $condition1['m.referee_id'] = $uid;
        $condition1['m.website_id'] = $this->website_id;
        $condition1['c.status'] = 1;
        $condition1['cl.weight'] = ['<=', $my_grade_weight];
        $channel_id_arr = $channel_server->getAllDownChannelId($condition1);
        $channel_num = count($channel_id_arr);
        $channel_info['user_headimg'] = $channel_list['user_headimg']?:'';
        $channel_info['name'] = $channel_list['nick_name']?:$channel_list['user_name'];
        $channel_info['channel_name'] = $channel_list['channel_real_name']?:$channel_list['nick_name'];
        $channel_info['channel_phone'] = $channel_list['channel_phone']?:$channel_list['user_tel'];
        $channel_info['channel_num'] = $channel_num;
        //获取渠道商采购商品列表
        $condition['cg.channel_id'] = $channel_id;
        $condition['cgs.channel_id'] = $channel_id;
        $condition['cg.website_id'] = $this->website_id;
        $order = 'cgs.create_date desc';
        $purchase_info = $channel_server->getPurchaseGoodsSkuInfo($page_index, $page_size, $condition, $order, $purchase_discount);
        $purchase_info['channel_info'] = $channel_info;
        return $purchase_info;
    }
    /*
     * 采购记录接口
     * **/
    public function purchaseRecordList()
    {
        $channel_server = new ChannelServer();
        //当前页数
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $goods_id = request()->post('goods_id',0);
        $sku_id = request()->post('sku_id',0);
        $channel_id = request()->post('channel_id',0);
        if(!$goods_id && !$sku_id && !$channel_id){
            return json(['code'=>-1,'message'=>'缺少参数']);
        }
        //获取我的这个商品sku的采购记录
        $condition['my_channel_id'] = $channel_id;
        $condition['sku_id'] = $sku_id;
        $condition['buy_type'] = 1;
        $condition['website_id'] = $this->website_id;
        $order = 'create_time desc';
        $purchase_list = $channel_server->getPurchaseRecordGoodsList($page_index, $page_size, $condition, $order, $channel_id);
        return $purchase_list;
    }
    /*
     * 移除渠道商
     * **/
    public function removeChannel()
    {
        $channel_server = new ChannelServer();
        $channel_id = request()->post('channel_id',0);
        $retval = $channel_server->removeChannel($channel_id);
        if($retval){
            $this->addUserLog('移除渠道商', $retval);
        }
        return ajaxReturn($retval);
    }
    /*
     * 添加微商中心设置
     * **/
    public function addChannelSetting()
    {
        $params = request()->post();
        //是否开启微商中心
        $is_use = request()->post('channel_status', 0);
        //成为渠道商条件 -1：提交资料申请 1：满足勾选条件之一 2：满足所有勾选条件
        $post_data['channel_condition'] = request()->post('channel_condition', -1);
        //渠道商条件下的勾选条件
        $post_data['channel_conditions'] = request()->post('channel_conditions', '');
        //具体的条件 团队人数
        $channel_team = request()->post('channel_team', '');
        if(!empty($channel_team)){
            $post_data['condition']['channel_team'] = $channel_team;
        }
        //消费元
        $pay_money = request()->post('pay_money', '');
        if(!empty($pay_money)){
            $post_data['condition']['pay_money'] = $pay_money;
        }
        //消费次数
        $pay_number = request()->post('pay_number', '');
        if(!empty($pay_number)){
            $post_data['condition']['pay_number'] = $pay_number;
        }
        //选择商品
        $goods_id = request()->post('goods_id', '');
        if(!empty($goods_id)){
            $post_data['condition']['goods_id'] = $goods_id;
        }
        //是否开启平级奖
        $post_data['peers_status'] = request()->post('peers_status', 0);
        //平级奖模式
        $post_data['channel_peers'] = request()->post('channel_peers', 1);//默认1级
        //是否开启跨级奖
        $post_data['cross_status'] = request()->post('cross_status', 0);
        //自动审核
        $post_data['channel_check'] = request()->post('channel_check', 0);
        //渠道商是否开启升降级模式
        $post_data['channel_grade'] = request()->post('channel_grade', 0);
        //渠道商订单自动关闭时间
        $post_data['channel_order_close_time'] = request()->post('channel_order_close_time', 0);
        //采购支付方式
        $post_data['pay_type'] = request()->post('pay_type', 0);
        //零售结算节点
        $post_data['settle_type'] = request()->post('settle_type', 0);
        $channel_server= new ChannelServer();
        $retval = $channel_server->addChannelConfig($post_data, $is_use);
        if($retval){
            $this->addUserLog('添加渠道商设置', $retval);
        }
        setAddons('channel', $this->website_id, $this->instance_id);
        setAddons('channel', $this->website_id, $this->instance_id, true);
        return AjaxReturn($retval);
    }
    /**
     * 申请协议
     */
    public function channelAgreement()
    {
        $config= new channelServer();
        if (request()->isPost()) {
            // 基本设置
            $logo = request()->post('image', ''); // 协议logo
            $content = request()->post('content', ''); // 协议内容
            if($content){
                $content = htmlspecialchars_decode($content);
            }
            $retval = $config->setAgreementSite($logo,$content);
            if($retval){
                $this->addUserLog('申请协议', $retval);
            }
            return AjaxReturn($retval);
        }
    }
    /*
     * 删除渠道商等级
     * **/
    public function deletaChannelGrade()
    {
        $channel_server = new ChannelServer();
        $channel_grade_id = request()->post('channel_grade_id', 0);
        $retval = $channel_server->deletaChannelGrade($channel_grade_id);
        if($retval){
            $this->addUserLog('删除渠道商等级', $channel_grade_id);
            return ajaxReturn($retval);
        }
    }
    /*
     * 保存/修改分销商等级
     * **/
    public function updateChannelGrade()
    {
        $channel_server= new ChannelServer();
        $post_data = [];
        $website_id = $this->website_id;
        $post_data['website_id'] = $website_id;
        //渠道商等级id
        $channel_grade_id = (int)request()->post('channel_grade_id', 0);
        //渠道商等级名称
        $post_data['channel_grade_name'] = request()->post('channel_grade_name', '');
        if(empty($post_data['channel_grade_name'])){
            return json(['code'=>-1, 'message'=>'渠道商等级名称不能为空']);
        }
        //判断是否存在渠道商等级名称，若存在则不让它加入
        $is_channel_level = $channel_server->isChannelGrade($post_data);
        if($is_channel_level && !$channel_grade_id){
            return json(['code'=>-1, 'message'=>'渠道商等级已经存在']);
        }
        //进货折扣
        $purchase_discount = request()->post('purchase_discount', 0);
        $post_data['purchase_discount'] = $purchase_discount/100;
        if(empty($purchase_discount)){
            return json(['code'=>-1, 'message'=>'进货折扣不能为空']);
        }
        //平一级奖
        $flat_first = request()->post('flat_first', 0);
        $post_data['flat_first'] = $flat_first/100;
        //平二级奖
        $flat_second = request()->post('flat_second');
        if(isset($flat_second)){
            if(!empty($flat_second)){
                $post_data['flat_second'] = $flat_second/100;
            }
        }
        //平三级奖
        $flat_third = request()->post('flat_third');
        if(isset($flat_third)){
            if(!empty($flat_third)){
                $post_data['flat_third'] = $flat_third/100;
            }
        }
        //跨级奖
        $cross_level = request()->post('cross_level', 0);
        if(isset($cross_level)){
            $post_data['cross_level'] = $cross_level/100;
        }
        //最小进货金额
        $post_data['minimum_purchase_amount'] = request()->post('minimum_purchase_amount', 0);
//        if(empty($post_data['minimum_purchase_amount'])){
//            return json(['code'=>-1, 'message'=>'最小进货金额不能为空']);
//        }
        //自动升级
        $post_data['auto_upgrade'] = request()->post('auto_upgrade', 0);
        if($post_data['auto_upgrade'] == 1){
            //升级条件
            $post_data['upgrade_condition'] = request()->post('upgrade_condition', '');
            if(empty($post_data['upgrade_condition'])){
                return json(['code'=>-1, 'message'=>'升级条件不能为空']);
            }
            //复选框升级条件
            $post_data['upgrade_conditions'] = request()->post('upgrade_conditions', '');
            if(empty($post_data['upgrade_conditions'])){
                return json(['code'=>-1, 'message'=>'升级条件不能为空']);
            }
            //累计采购量
            $up_total_purchase_num = request()->post('up_total_purchase_num', 0);
            $up_temp_arr = [];
            if(!empty($up_total_purchase_num)){
                $up_temp_arr['up_total_purchase_num'] = $up_total_purchase_num;
            }
            //累计采购金额
            $up_total_purchase_amount = request()->post('up_total_purchase_amount', 0);
            if(!empty($up_total_purchase_amount)){
                $up_temp_arr['up_total_purchase_amount'] = $up_total_purchase_amount;
            }
            //累计采购单
            $up_total_order_num = request()->post('up_total_order_num', 0);
            if(!empty($up_total_order_num)){
                $up_temp_arr['up_total_order_num'] = $up_total_order_num;
            }
            //购买指定商品
            $goods_id = request()->post('goods_id', 0);
            if(!empty($goods_id)){
                $up_temp_arr['goods_id'] = $goods_id;
            }
            $up_temp_str = json_encode($up_temp_arr);
            $post_data['upgrade_val'] = $up_temp_str;
        }

        //降级
        //自动降级
        $post_data['auto_downgrade'] = request()->post('auto_downgrade', 0);
        if($post_data['auto_downgrade'] == 1){
            //降级条件
            $post_data['downgrade_condition'] = request()->post('downgrade_condition', '');
            if(empty($post_data['downgrade_condition'])){
                return json(['code'=>-1, 'message'=>'降级条件不能为空']);
            }
            //复选框降级条件
            $post_data['downgrade_conditions'] = request()->post('downgrade_conditions', '');
            if(empty($post_data['downgrade_conditions'])){
                return json(['code'=>-1, 'message'=>'降级条件不能为空']);
            }
            //累计采购量
            $down_total_purchase_num = request()->post('down_total_purchase_num_cond', 0);
            $down_temp_arr = [];
            if(!empty($down_total_purchase_num)){
                $down_temp_arr['down_total_purchase_num_cond'] = $down_total_purchase_num;
            }
            //累计采购金额
            $down_total_purchase_amount = request()->post('down_total_purchase_amount_cond', 0);
            if(!empty($down_total_purchase_amount)){
                $down_temp_arr['down_total_purchase_amount_cond'] = $down_total_purchase_amount;
            }
            //累计采购单
            $down_total_order_num = request()->post('down_total_order_num_cond', 0);
            if(!empty($down_total_order_num)){
                $down_temp_arr['down_total_order_num_cond'] = $down_total_order_num;
            }
            $down_temp_str = json_encode($down_temp_arr);
            $post_data['downgrade_val'] = $down_temp_str;
        }
        //权重
        if(!empty($channel_grade_id)){
            //判断当前编辑的是否是权重为1
            $channel_level_mdl = new VslChannelLevelModel();
            $edit_weight = $channel_level_mdl->getInfo(['channel_grade_id'=>$channel_grade_id],'weight')['weight'];
        }
        $post_data['weight'] = request()->post('weight', 0);
        if($edit_weight != 1){
            if(empty($post_data['weight'])){
                return json(['code'=>-1, 'message'=>'权重不能为空']);
            }
        }else{
            $post_data['weight'] = $edit_weight;
        }

        $post_data['create_time'] = time();
        $post_data['update_time'] = time();
//        echo '<pre>';print_r($post_data);exit;
        if(!empty($channel_grade_id)){
            $post_data['channel_grade_id'] = $channel_grade_id;
        }
        $retval = $channel_server->addChannelGrade($post_data);

        if($retval){
            $this->addUserLog('添加/修改渠道商等级', $retval);
        }
        return AjaxReturn($retval);
    }
    /*
     * 获取渠道商等级列表
     * **/
    public function channelGradeList()
    {
        if(request()->isAjax()){
            //当前页数
            $page_index = request()->post('page_index',1);
            $page_size = request()->post('page_size')?:PAGESIZE;
            //条件
            $website_id = $this->website_id;
            $condition['website_id'] = $website_id;
            //查询列表数据
            $channel_sever = new channelServer();
            $channel_grade_list = $channel_sever->getChannelGradeData($page_index, $page_size, $condition, 'weight');
            //处理拼接升级条件
//        echo '<Pre>';print_r($channel_grade_list['data']);exit;
            foreach($channel_grade_list['data'] as $k => $v){
                //升级条件
                $auto_upgrade = $v['auto_upgrade'];
                $auto_downgrade = $v['auto_downgrade'];
                $upgrade_str = '';
                if($auto_upgrade == '1'){
                    $upgrade_val = $v['upgrade_val'];
                    $upgrade_val_arr = json_decode($upgrade_val,true);
                    if(!empty($upgrade_val_arr['up_total_purchase_num'])){
                        $upgrade_str .= '累计采购量大于'.$upgrade_val_arr['up_total_purchase_num'].'件，'."<br/>";
                    }
                    if(!empty($upgrade_val_arr['up_total_purchase_amount'])){
                        $upgrade_str .= '累计采购金额大于'.$upgrade_val_arr['up_total_purchase_amount'].'元，'."<br/>";
                    }
                    if(!empty($upgrade_val_arr['up_total_order_num'])){
                        $upgrade_str .= '累计采购单大于'.$upgrade_val_arr['up_total_order_num'].'笔，'."<br/>";
                    }
                    if(!empty($upgrade_val_arr['goods_id'])){
                        $goods_server = new goods();
                        $goods_arr = $goods_server->getGoodsName($upgrade_val_arr['goods_id']);
                        $upgrade_str .= '购买指定商品：'.$goods_arr['goods_name']."<br/>";
                    }
                }
                if(!$upgrade_str){
                    $upgrade_str = '无';
                }
                $channel_grade_list['data'][$k]['upgrade_str'] = $upgrade_str;
                $downgrade_str = '';
                if($auto_downgrade == '1'){
                    //降级条件
                    $downgrade_val = $v['downgrade_val'];
                    $downgrade_val_arr = json_decode($downgrade_val,true);
                    if(!empty($downgrade_val_arr['down_total_purchase_num_cond'])){
                        $condition_arr1 = explode(',',$downgrade_val_arr['down_total_purchase_num_cond']);
                        $condition_day1 = $condition_arr1[0];
                        $condition_quantity1 = $condition_arr1[1];
                        $downgrade_str .= $condition_day1.'天内，累计采购量小于'.$condition_quantity1.'件，'."<br/>";
                    }
                    if(!empty($downgrade_val_arr['down_total_purchase_amount_cond'])){
                        $condition_arr2 = explode(',',$downgrade_val_arr['down_total_purchase_amount_cond']);
                        $condition_day2 = $condition_arr2[0];
                        $condition_quantity2 = $condition_arr2[1];
                        $downgrade_str .= $condition_day2.'天内，累计采购金额小于'.$condition_quantity2.'元，'."<br/>";
                    }
                    if(!empty($downgrade_val_arr['down_total_order_num_cond'])){
                        $condition_arr3 = explode(',',$downgrade_val_arr['down_total_order_num_cond']);
                        $condition_day3 = $condition_arr3[0];
                        $condition_quantity3 = $condition_arr3[1];
                        $downgrade_str .= $condition_day3.'天内，累计采购单大于'.$condition_quantity3.'笔'."<br/>";
                    }
                }
                if(!$downgrade_str){
                    $downgrade_str = '无';
                }
                $channel_grade_list['data'][$k]['downgrade_str'] = $downgrade_str;

                //处理purchase_discount
                $channel_grade_list['data'][$k]['purchase_discount'] = ($channel_grade_list['data'][$k]['purchase_discount']*100).'%';
            }
            return $channel_grade_list;
        }
    }
    public function channelCheckStatus()
    {
        $status = request()->post('status',0);
        $channel_id = request()->post('channel_id','');
        $channel_sever = new channelServer();
        $retval = $channel_sever->changeChannelStatus($channel_id, $status);
        if($retval){
            $this->addUserLog('审核渠道商', $retval);
        }
        return AjaxReturn($retval);
    }

    /********************************微商中心前端接口编写*********************************/
    /*
     * 微商中心商品采购接口得到上级渠道商
     * **/
    public function getUpChannelInfo()
    {
        $channel_server = new ChannelServer();
        //根据用户id获取到渠道商信息、等级
        $uid = $this->uid;
        $channel_info = $channel_server->getMyChannelInfo(['c.uid'=>$uid]);
        $channel_arr = objToArr($channel_info);
        //获得上级的渠道商id，获取商品信息
        $my_weight = $channel_arr['weight'];
        $up_grade_channel_id = $channel_server->myRefereeChannel($uid,$my_weight);
        return $up_grade_channel_id;
    }
    /*
     * 获取微商中心的商品列表
     * **/
    public function getChannelGradeGoods(){
        $goods_server = new Goods();
        $channel_server = new ChannelServer();
        //获取请求的参数
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $buy_type = request()->post('buy_type', '');
        $category_id = request()->post('category_id', 0);
        $search_text = request()->post('search_text', 0);
        $website_id = $this->website_id;
        $uid = getUserId();
        //判断当前用户是否是分销商
        $member_mdl = new VslMemberModel();
        $is_distributor = $member_mdl->getInfo(['uid' => $uid, 'isdistributor' => 2]);
        if (!$is_distributor) {
            return json(['code' => -1, 'message' => '您还不是分销商哦，不能进行云仓采购，如需帮助，请联系客服！']);
        }
        if(empty($buy_type) && empty($category_id)){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        if(!empty($search_text)){
            $condition['g.goods_name'] = ['like','%'.$search_text.'%'];
        }
        $condition['g.category_id_1'] = $category_id;
        $condition['g.website_id'] = $website_id;
        $condition['g.shop_id'] = 0;
        if($buy_type == 'purchase'){
            //云仓采购默认显示所有平台的商品
            $up_grade_channel_id = 'platform';
            $goods_list = $goods_server->getChannelGoodsList($page_index, $page_size, $condition, '', $up_grade_channel_id, $uid, $buy_type);
        }elseif($buy_type == 'pickupgoods'){
            $condition1['c.website_id'] = $website_id;
            $condition1['c.uid'] = $uid;
            $my_channel_info = $channel_server->getMyChannelInfo($condition1);
            $my_channel_id = $my_channel_info['channel_id'];
            $condition['g.goods_type'] = ['not in', [0, 3, 4]];//0-计时计次 3-虚拟商品 4-知识付费
            $condition['g.channel_id'] = $my_channel_id;
            $goods_list = $goods_server->getChannelGoodsList($page_index, $page_size, $condition, '', $my_channel_id, $uid, $buy_type);
        }

        return json($goods_list);
    }
    /*
     * 获取采购中心的商品的分类
     * **/
    public function getChannelGoodsCategoryList()
    {
        $goods_server = new Goods();
        /*$page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size',PAGESIZE);*/
        $buy_type = request()->post('buy_type', '');
        $website_id = $this->website_id;
        //先写死
        $uid = getUserId();
        if(empty($buy_type)){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $channel_server = new ChannelServer();
        $condition['gc.level'] = 1;
        $condition['g.website_id'] = $website_id;
        if($buy_type == 'purchase'){
            $up_grade_channel_id = 'platform';
//            $res_arr = $goods_server->getChannelGoodsCategoryList($page_index, $page_size, $condition, '',$up_grade_channel_id);
            $res_arr = $goods_server->getChannelGoodsCategoryList($condition, '',$up_grade_channel_id);
        }elseif($buy_type == 'pickupgoods'){
            $condition1['c.website_id'] = $website_id;
            $condition1['c.uid'] = $uid;
            $my_channel_info = $channel_server->getMyChannelInfo($condition1);
            $my_channel_id = $my_channel_info['channel_id'];
            $condition['g.channel_id'] = $my_channel_id;
//            $res_arr = $goods_server->getChannelGoodsCategoryList($page_index, $page_size, $condition, '',$my_channel_id);
            $res_arr = $goods_server->getChannelGoodsCategoryList($condition, '',$my_channel_id);
        }
        foreach($res_arr['data']['category_list'] as $k=>$v){
            $res_arr['data']['category_list'][$k]['short_name'] = $v['short_name']?:'';
        }
        return json($res_arr);
    }
    /*
     * 加入渠道商的购物车
     * **/
    public function addChannelCart(){
        $sku_id = request()->post('sku_id');
        $num = request()->post('num');
        //购买类型
        $buy_type = request()->post('buy_type');
        if (empty($sku_id) || empty($num)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        if($buy_type == 'purchase'){
            //如果是云仓采购，需要判断当前当前用户的等级是否在该商品的渠道商权限里，如果不在则不能购买
            $goods_sku_model = new VslGoodsSkuModel();
            $goods_id = $goods_sku_model->Query(['sku_id' => $sku_id],'goods_id')[0];
            //商品对应的渠道商权限等级
            $goods_discount_model = new VslGoodsDiscountModel();
            $goods_channel_anth = $goods_discount_model->Query(['goods_id' => $goods_id],'channel_auth')[0];
            $goods_channel_anth = explode(',',$goods_channel_anth);
            //当前用户的渠道商等级
            $uid = getUserId();
            $channel_model = new VslChannelModel();
            $user_channel_level = $channel_model->Query(['uid' => $uid],'channel_grade_id')[0];
            if(in_array($user_channel_level, $goods_channel_anth)) {
            $type = 1;
            }else{
               return json(['code' => -1, 'message' => '当前用户渠道商等级不允许购买此商品']);
            }
        }else{
            $type = 2;
        }
        //获取当前用户购物车的数量
        $uid = $this->uid;
        $cart_cond['buyer_id'] = $uid;
        $cart_cond['sku_id'] = $sku_id;
        $cart_cond['buy_type'] = $type;
        $cart_cond['website_id'] = $this->website_id;
        $channel_cart = new VslChannelCartModel();
        $total = $channel_cart->getInfo($cart_cond, 'sum(num) AS total')['total'];
        $num = $total+$num;
        $res = $this->addOrUpdateCartNum($buy_type, $sku_id, $num);
        if($res['code'] == -1){
            return json($res);
        }
        if ($res) {
            return json(['code' => 0, 'message' => '添加成功', 'data' => ['cart_id' => $res]]);
        } else {
            return json(AjaxReturn(ADD_FAIL));
        }
    }
    /*
    * 添加或者修改购物车数量
    * **/
    public function addOrUpdateCartNum($buy_type, $sku_id, $num, $update=0)
    {
        $channel_server = new ChannelServer();
        $goods = new Goods();
        $uid = getUserId() ?: 0;
        //获取我的等级权重，用于获取所有上级的渠道商
        $condition1['c.website_id'] = $this->website_id;
        $condition1['c.uid'] = $uid;
        $channel_info = $channel_server->getMyChannelInfo($condition1);
        $purchase_discount = $channel_info['purchase_discount'];
        $my_weight = $channel_info['weight'];
        $channel_id = $channel_info['channel_id'];
        //核心方法：根据当前渠道商的id查看商品库存是否满足当前采购量，如果不满足则向上取商品数量 返回渠道商id:sku_id:num
        if($buy_type == 'purchase'){
            //上级渠道商id
            $up_grade_channel_id = $this->getUpChannelInfo();
            $channel_sku_num_str = $channel_server->getUpChannelSkuNum($uid, $my_weight, $sku_id, $num);
            $channel_id = $up_grade_channel_id;
//            p($channel_sku_num_str);exit;
        }else{
            $channel_sku_num_str = $channel_server->getMyChannelSkuNum($channel_id, $sku_id, $num);
        }
        //处理购买的sku信息：价格、数量
        $channel_sku_num_price_arr = explode(" ", $channel_sku_num_str);
        foreach($channel_sku_num_price_arr as $k=>$sku_num_price){
            $sku_num_price_arr0 = explode(':', $sku_num_price);
            $channel_info_arr[] = $sku_num_price_arr0[0];
        }
//        p($channel_info_arr);exit;
        if (in_array('platform', $channel_info_arr)) {
            $sku_model = new VslGoodsSkuModel();
            $sku_info = $sku_model::get($sku_id, ['goods']);
            $get_goods['sku_id'] = $sku_id;
        }else{
            $goods_mdl = new VslChannelGoodsModel();
            $sku_model = new VslChannelGoodsSkuModel();
            $get_goods['sku_id'] = $sku_id;
            $get_goods['channel_id'] = $channel_id;
            $sku_info = $sku_model->field('sku_name, goods_id')->where($get_goods)->find();
            $goods_list = $goods_mdl->field( 'goods_name, picture')->where(['goods_id'=>$sku_info['goods_id'], 'channel_id'=>$channel_info_arr[0]])->find();
            $sku_info['goods'] = $goods_list;
        }
//        echo $sku_model->getLastSql();exit;
        //采购渠道商id：skuid：数量：采购价格
        //采购记录表 采购id、采购人渠道商id，被采购人渠道商id（有可能是platform），sku_id，购买多少，采购价格，采购人的采购折扣，商品售价（平台价），采购后剩余多少，购买方式（采购、自提、零售），创建时间
        if (!$sku_info) {
            return ['code' => -1, 'message' => '商品不存在'];
        }
        $goods_name = $sku_info->goods->goods_name;
        $goods_id = $sku_info->goods_id;
        $sku_name = $sku_info->sku_name;
        //获取平台价
        $sku_arr = ['channel_id' => $channel_id, 'sku_id' => $sku_id, 'price' => 0, 'market_price' => 0];
        $goods->getChannelSkuPrice($sku_arr);
        $price = $sku_arr['price'];
        //如果是采购的话，需要在平台价*进货折扣
        if($buy_type == 'purchase'){
            $price = $price*$purchase_discount;
            $buy_type_id = 1;
        }else{
            $buy_type_id = 2;
            $price = 0;
        }
        $picture_id = $sku_info->goods->picture;
        $res = true;
        $channel_cart_mdl = new VslChannelCartModel();
        $cart_condition['buyer_id'] = $uid;
        $cart_condition['sku_id'] = $sku_id;
        $cart_condition['buy_type'] = $buy_type_id;
        $cart_condition['website_id'] = $this->website_id;
        $cart_channel_list = $channel_cart_mdl->getquery($cart_condition, 'channel_info', '');
        $cart_channel_arr = objToArr($cart_channel_list);
        $cart_channel_arr = array_column($cart_channel_arr, 'channel_info');

        foreach($cart_channel_arr as $cci){
            if(!in_array($cci, $channel_info_arr)){
                $del['buyer_id'] = $uid;
                $del['channel_info'] = $cci;
                $del['sku_id'] = $sku_id;
                $del['buy_type'] = $buy_type_id;
                $del['website_id'] = $this->website_id;
                $channel_cart_mdl->where($del)->delete();
            }
        }
        //循环插入购物车 $channel_sku_num_arr
        foreach($channel_sku_num_price_arr as $k=>$sku_num_price){
            //通过:分割
            $sku_num_price_arr = explode(':', $sku_num_price);
            //第一个是渠道商id，第二个是sku_id，第三个是数量
            $channel_info = $sku_num_price_arr[0];
            $sku_id = $sku_num_price_arr[1];
            $fix_num = $sku_num_price_arr[2];
            $num1 = $fix_num;
            $shop_id = 0;
            $result = $channel_server->addcart($uid, $shop_id, $goods_id, $goods_name, $sku_id, $sku_name, $price, $num1, $picture_id, 0, $buy_type_id, $channel_info, $update);
            if(!$result){
                $res = false;
            }
        }
        return $res;
    }

    /*
     * 后台修改渠道商详情
     * **/
    public function updateChannelInfo()
    {
        $channel_id = request()->post('channel_id',0);
        $channel_phone = request()->post('mobile','');
        $status = request()->post('status',0);
        $channel_real_name = request()->post('real_name','');
        $level = request()->post('level',0);
//        if(empty($channel_real_name)){
//            return json(['code'=>-1,'message'=>'真实姓名不能为空']);
//        }
//        if(empty($channel_phone)){
//            return json(['code'=>-1,'message'=>'手机号码不能为空']);
//        }
        $res['channel_phone'] = $channel_phone;
        $res['status'] = $status;
        $res['channel_real_name'] = $channel_real_name;
        $res['channel_grade_id'] = $level;
        $res['upgrade_time'] = time();
        $website_id = $this->website_id;
        $condition['channel_id'] = $channel_id;
        $condition['website_id'] = $website_id;
        $channel_server = new channelServer();
        $retval = $channel_server->updateChannelInfo($condition, $res);
        if($retval){
            $this->addUserLog('保存渠道商详情', $retval);
        }
        return ajaxReturn($retval);
    }
    /*
     * 购物车列表接口
     * **/
    public function getChannelCartGoodsInfo()
    {
        $buy_type = request()->post('buy_type');
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $uid = getUserId() ?: 0;
        //假设uid等于168
        //获取我的等级权重，用于获取所有上级的渠道商
        $condition1['c.website_id'] = $this->website_id;
        $condition1['c.uid'] = $uid;
        $channel_server = new ChannelServer();
        $channel_info = $channel_server->getMyChannelInfo($condition1);
        $my_weight = $channel_info['weight'];
        $my_channel_info = $channel_info['channel_id'];
        $website_id = $this->website_id;
        $condition['c.website_id'] = $website_id;
        $condition['c.buyer_id'] = $uid;
        if($buy_type == 'purchase'){
            $condition['c.buy_type'] = 1;
        }else{
            $condition['c.buy_type'] = 2;
        }
        $order = 'c.cart_id desc';
        $cart_arr = $channel_server->getChannelCart($page_index, $page_size, $condition, $order);
        if(!$cart_arr['data']){
            return json(['code'=>-1,'message'=>'购物车为空']);
        }
        $up_grade_channel_id = $this->getUpChannelInfo();
        $cart_arr['data']['cart_list'] = [];
        $cart_arr['data']['total_money'] = 0;
        if(!empty($cart_arr['data']['data'])){
            foreach($cart_arr['data']['data'] as $k=>$cart_goods){
                if($buy_type == 'purchase'){
                    $stock = $channel_server->myAllRefereeChannelSkuStore($uid, $my_weight, $cart_goods['sku_id']);
                    $change_price = $channel_server->changeCartPrice($cart_goods, $channel_info, $up_grade_channel_id);
                    $cart_goods['price'] = $change_price;
                }else{
                    $stock_list = $channel_server->getChannelSkuStore($cart_goods['sku_id'], $my_channel_info, $website_id);
                    $stock = $stock_list['stock'];
                }
                $pic_cover = getApiSrc($cart_goods['pic_cover']);
                $cart_arr['data']['cart_list'][$k]['buyer_id'] = $cart_goods['buyer_id'];
                $cart_arr['data']['cart_list'][$k]['shop_name'] = $cart_goods['shop_name'];
                $cart_arr['data']['cart_list'][$k]['goods_id'] = $cart_goods['goods_id'];
                $cart_arr['data']['cart_list'][$k]['goods_name'] = $cart_goods['goods_name'];
                $cart_arr['data']['cart_list'][$k]['sku_id'] = $cart_goods['sku_id'];
                $cart_arr['data']['cart_list'][$k]['sku_name'] = $cart_goods['sku_name'];
                $cart_arr['data']['cart_list'][$k]['price'] = $cart_goods['price'];
                $cart_arr['data']['cart_list'][$k]['num'] = $cart_goods['num'];
                $cart_arr['data']['cart_list'][$k]['goods_picture'] = $pic_cover;
//                $cart_arr['data']['cart_list'][$k]['max_buy'] = $stock;
                $cart_arr['data']['cart_list'][$k]['max_buy'] = $stock;
                $cart_arr['data']['cart_list'][$k]['min_buy'] = 1;
                $cart_arr['data']['cart_list'][$k]['channel_info'] = $cart_goods['channel_info'];
                //总金额
                $cart_arr['data']['total_money'] +=  $cart_goods['price']*$cart_goods['num'];
            }
        }
        $cart_arr['data']['total_money'] = round($cart_arr['data']['total_money'], 2);
        unset($cart_arr['data']['data']);
//        if($buy_type == 'purchase'){
//            //取得当前用户的最小采购金额
//            $minimum_purchase_amount = $channel_info['minimum_purchase_amount'];
//            if ($cart_arr['data']['total_money'] < $minimum_purchase_amount) {
//                $cart_arr['code'] = 1;
//                $cart_arr['message'] = '最小采购金额为：￥'.$minimum_purchase_amount.'元';
//            }
//        }
        $cart_arr['data']['lowest_purchase_money'] = (float)$channel_info['minimum_purchase_amount'];
//        echo json_encode($cart_arr);exit;
        return json($cart_arr);
    }
    /*
     * 获取购物车的数量
     * **/
    public function getChannelCartNum(){
        $buy_type = request()->post('buy_type','');
        $uid = $this->uid;
        if($buy_type == 'purchase'){
            $condition['buy_type'] = 1;
        }else{
            $condition['buy_type'] = 2;
        }
        $condition['buyer_id'] = $uid;
        $cart_mdl = new VslChannelCartModel();
        $count = $cart_mdl->where($condition)->count();
//        echo $cart_mdl->getLastSql();exit;
        return $count;
    }
    /*
     * 修改购物车数量
     * **/
    public function channelCartAdjustNum()
    {
        $buy_type = request()->post('buy_type','');
        $sku_id = request()->post('sku_id',0);
        $num = request()->post('num',0);
        $channel_info = request()->post('channel_info',0);
        if (!$buy_type || !$sku_id || !$num || !$channel_info) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $condition['buyer_id'] = $this->uid;
        $condition['sku_id'] = $sku_id;
        $condition['website_id'] = $this->website_id;
        if ($buy_type == 'purchase') {
            $condition['buy_type'] = 1;
        } else {
            $condition['buy_type'] = 2;
        }
        $cart_mdl = new VslChannelCartModel();
        //删除当前的购物车商品，重新加入
//        $delete_res = $cart_mdl->where($condition)->delete();
        $res = $this->addOrUpdateCartNum($buy_type, $sku_id, $num, 1);
        if ($res) {
            return json(['code' => 0, 'message' => '操作成功']);
        } else {
            return json(['code' => -1, 'message' => '系统繁忙']);
        }
    }
    /*
     * 删除渠道商购物车
     * **/
    public function deleteChannelCart()
    {
        $buy_type = request()->post('buy_type','');
        $sku_id = request()->post('sku_id',0);
        if(!$buy_type || !$sku_id){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $uid = getUserId();
        if($buy_type == 'purchase'){
            $buy_type = 1;
        }else{
            $buy_type = 2;
        }
        $channel_server = new ChannelServer();
        $condition1['website_id'] = $this->website_id;
        $condition1['buy_type'] = $buy_type;
        $condition1['sku_id'] = $sku_id;
        $condition1['buyer_id'] = $uid;
        $retval = $channel_server->deleteChannelCart($condition1);
        if($retval){
            return json(['code' => 0, 'message' => '删除成功']);
        }else{
            return json(['code' => -1, 'message' => '删除失败']);
        }
    }
    /*
     *  渠道商结算页
     * **/
    public function channelSettlement()
    {
        $buy_type = request()->post('buy_type', '');
        if (!$buy_type) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $uid = getUserId();
        //获取默认地址
        $member = new MemberService();
        $address = $member->getDefaultExpressAddress($uid);
        if ($buy_type == 'purchase') {
            $buy_method = 'purchase';
            $buy_type = 1;
        } else {
            $buy_method = 'pickupgoods';
            $buy_type = 2;
        }
        $condition['buy_type'] = $buy_type;
        $condition['buyer_id'] = $uid;
        $channel_server = new ChannelServer();
        $order = 'c.cart_id';
        $my_channel_info = $channel_server->getMyChannelInfo(['c.uid'=>$uid, 'c.website_id'=>$this->website_id]);
        $cart_arr = $channel_server->getChannelSettlement($condition, $order);
//        p($cart_arr['data']);exit;
        $total_money = 0;
        $total_quantity = 0;
        $up_grade_channel_id = $this->getUpChannelInfo();
        foreach ($cart_arr['data']['data'] as $k => $cart_goods) {
            //判断是否价格改变过,若更改了，则修改购物车
            if($buy_type == 1){
                $change_price = $channel_server->changeCartPrice($cart_goods, $my_channel_info, $up_grade_channel_id);
                $cart_goods['price'] = $change_price;
            }
            //总金额
            $total_money += $cart_goods['price']*$cart_goods['num'];
            //共多少件
            $total_quantity += $cart_goods['num'];
            //获取购物车中属于谁的商品
            $stock_list = $channel_server->getChannelSkuStore($cart_goods['sku_id'], $cart_goods['channel_info']);
            $pic_cover = getApiSrc($cart_goods['pic_cover']);
            if($cart_goods['channel_info'] == 'platform'){
                $cart_arr['data']['shop_name'] = $cart_goods['shop_name'];
            }else{
                $channel_condition['channel_id'] = $cart_goods['channel_info'];
                $channel_condition['c.website_id'] = $this->website_id;
                $channel_info = $channel_server->getMyChannelInfo($channel_condition);
                $cart_arr['data']['shop_name'] = $channel_info['nick_name']?:($channel_info['user_name']?:$channel_info['user_tel']);
            }

            $cart_arr['data']['shop_id'] = $cart_goods['shop_id'];
            $cart_arr['data']['shop_list'][$k]['buyer_id'] = $cart_goods['buyer_id'];
            $cart_arr['data']['shop_list'][$k]['goods_id'] = $cart_goods['goods_id'];
            $cart_arr['data']['shop_list'][$k]['goods_name'] = $cart_goods['goods_name'];
            $cart_arr['data']['shop_list'][$k]['sku_id'] = $cart_goods['sku_id'];
            $cart_arr['data']['shop_list'][$k]['sku_name'] = $cart_goods['sku_name'];
            $cart_arr['data']['shop_list'][$k]['price'] = $cart_goods['price'];
            $cart_arr['data']['shop_list'][$k]['num'] = $cart_goods['num'];
            //采购于谁
            //获取采购于谁
            if($cart_goods['channel_info'] == 'platform'){
                $cart_arr['data']['shop_list'][$k]['purchase_to'] =  $cart_goods['shop_name']?:'';
            }else{
                $condition_channel['c.website_id'] = $this->website_id;
                $condition_channel['c.channel_id'] = $cart_goods['channel_info'];
                $channel_user_info = $channel_server->getChannelName($condition_channel);
                $cart_arr['data']['shop_list'][$k]['purchase_to'] = $channel_user_info['nick_name']?:($channel_user_info['user_name']?:$channel_user_info['user_tel']);
            }
            $cart_arr['data']['shop_list'][$k]['channel_info'] = $cart_goods['channel_info'];
            $cart_arr['data']['shop_list'][$k]['goods_picture'] = $pic_cover;
            $cart_arr['data']['shop_list'][$k]['max_buy'] = $stock_list['stock'];
            $cart_arr['data']['shop_list'][$k]['min_buy'] = 1;
            unset($cart_arr['data']['data'][$k]['pic_cover']);
            //组计算运费的数组,因为一个goods会属于多个渠道商，所以将该商品的数量汇总取算运费。
            $goods_shipping_arr[$cart_goods['sku_id']]['goods_id'] = $cart_goods['goods_id'];
            $goods_shipping_arr[$cart_goods['sku_id']]['num'] += $cart_goods['num'];
        }
        unset($cart_arr['data']['data']);
        if($buy_type == 1){//采购的时候要判断是否设置了最小采购金额
            //查出设置的采购金额来
            $my_channel_info = $channel_server->getMyChannelInfo(['c.uid'=>$this->uid, 'c.website_id'=>$this->website_id]);
            $minimum_purchase_amount = $my_channel_info['minimum_purchase_amount']?:0;
            if ($total_money < $minimum_purchase_amount) {
                return json(['code' => -1, 'message' => '最小采购金额为：' . $my_channel_info['minimum_purchase_amount'] . '元']);
            }
        }
        $cart_arr['data']['total_money'] = $total_money;
        $cart_arr['data']['total_quantity'] = $total_quantity;
        $cart_arr['data']['buy_type'] = $buy_method;
        if ($buy_type != 1) {
            //计算运费
            $total_shipping_fee = 0;
            foreach($goods_shipping_arr as $k=>$shipping){
                $total_shipping_fee += $this->resetShippingFee(array($shipping['goods_id']), array($shipping['num']), $address['id']);
                $cart_arr['data']['total_shipping_fee'] = $total_shipping_fee;
            }
        }else{
            $cart_arr['data']['total_shipping_fee'] = 0;
        }

        //收货地址
        if (!empty($address)) {
            $cart_arr['data']['address_info'] = $address['address_info']." ".$address['address'];
            $cart_arr['data']['province'] = $address['province'];
            $cart_arr['data']['city'] = $address['city'];
            $cart_arr['data']['district'] = $address['district'];
            $cart_arr['data']['address_id'] = $address['id'];
            $cart_arr['data']['consigner'] = $address['consigner'];
            $cart_arr['data']['mobile'] = $address['mobile'];
        } else {
            $cart_arr['data']['address_info'] = "";
            $cart_arr['data']['province'] = "";
            $cart_arr['data']['city'] = "";
            $cart_arr['data']['district'] = "";
            $cart_arr['data']['address_id'] = "";
            $cart_arr['data']['consigner'] = "";
            $cart_arr['data']['mobile'] = "";
        }
        echo json_encode($cart_arr);exit;
        return $cart_arr;
    }
    /**
    计算运费
     **/
    public function countChannelFree(){
        //计算运费 goodsId:num
        $goods_info= request()->post('goods_info');
        $goods_info_arr = explode(',', $goods_info);
        if(empty($goods_info)){
            $res['code'] = '-1';
            $res['message'] = "商品信息不能为空";
        }
        $address_id = request()->post('address_id');
        if (empty($address_id)) {
            $free_money = 0;
        } else {
            $free_money = 0;
            foreach($goods_info_arr as $k=>$goods){
                $goods_arr = explode(':', $goods);
                $goods_id = [$goods_arr[0]];
                $num = [$goods_arr[1]];
                $free_money += $this->resetShippingFee($goods_id, $num,$address_id,0);
            }
        }
        $res['data']['free_money'] = $free_money;
        $res['code'] = 0;
        $res['message'] = "获取成功";
        return json($res);
    }
    /*
     * 计算每件商品运费
     * **/
    public function resetShippingFee($goodsIds, $nums, $address_id)
    {
        if (!$address_id || !$goodsIds) {
            return 0;
        }
        $goods = [];
        foreach ($goodsIds as $key => $val) {
            $goods[$val]['count'] += $nums[$key];
            $goods[$val]['goods_id'] = $val;
        }
        $addressModel = new VslMemberExpressAddressModel();
        $address = $addressModel->getInfo(['id' => $address_id]);
        if (!$address) {
            return 0;
        }
        $goodsExpress = new GoodsExpress();
        $shippingFee = 0.00;
        $shippingFeeGet = $goodsExpress->getGoodsExpressTemplate($goods, $address['district'])['totalFee'];
        if ($shippingFeeGet) {
            $shippingFee = $shippingFeeGet;
        }
        return $shippingFee;
    }
    /*
     * 提交渠道商订单
     * **/
    public function orderCreate(){
//        echo __URL('wapapi/pay/wchatUrlBack&msg=1&out_trade_no=1');exit;
        //获取参数
        $buy_type = request()->post('buy_type', '');
        $address_id = request()->post('address_id', '');
        $buyer_message = request()->post('buyer_message', '');
        if(!$buy_type){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        //自提是没有地址的
        if($buy_type == 'purchase'){
            $buy_type = 1;
            $address['mobile'] = '';
            $address['province'] = '';
            $address['city'] = '';
            $address['district'] = '';
            $address['address'] = '';
            $address['zip_code'] = '';
            $address['consigner'] = '';
        }else{
            //$address
            $member = new Member();
            $address = $member->getMemberExpressAddressDetail($address_id);
            $buy_type = 2;
        }
//        var_dump($buy_type);
        $uid = $this->uid;
        $channel_server = new ChannelServer();
        $order_business = new OrderBusiness();
        $order_service = new OrderService();
        $user_model = new UserModel();
        //获取购物车的商品
        $website_id = $this->website_id;
        $condition['buyer_id'] = $uid;
        $condition['website_id'] = $website_id;
        $condition['buy_type'] = $buy_type;
//        echo 1111;exit;
        $cart_list = $channel_server->getChannelCartList($condition);
        if(!$cart_list){
            return json(['code'=>-1,'message'=>'购物车为空']);
        }
        $cart_arr = objToArr($cart_list);
        //`out_trade_no` '外部交易号',
        $out_trade_no = 'QD'.$order_service->getOrderTradeNo();
        $order_no = $order_business->createOrderNo(0);
        //用户信息
        $user_info = $user_model::get($uid);
        $user_name = $user_info['nick_name'];
        //买家ip
        $ip = get_ip();
        //合并相同渠道商的商品
//        echo '<pre>';print_R($cart_arr);exit;
        $shipping_fee = 0;
        $goods_money = 0;
        $channel_info = $channel_server->getMyChannelInfo(['c.uid' => $uid, 'c.website_id' => $this->website_id]);
        $up_grade_channel_id = $this->getUpChannelInfo();
        foreach($cart_arr as $k=>$cart){
            if($buy_type == 1){
                //判断价格是否改变，如果改变则更改购物车
                $channel_price = $channel_server->changeCartPrice($cart, $channel_info, $up_grade_channel_id);
                $cart['price'] = $channel_price;
            }
            if($cart['channel_info'] == 'platform'){
                $channel_name = $cart['shop_name'];
                $err_info = '平台：'.$channel_name.' 商品【'.$cart['goods_name'].'】库存不足，购物车已调整，请重新下单';
            }else{
                //获取渠道商的信息
                $condition_channel['c.channel_id'] = $cart['channel_info'];
                $channel_info1 = $channel_server->getMyChannelInfo($condition_channel);
                $channel_name = $channel_info1['user_name']?:$channel_info1['nick_name'];
                $err_info = '渠道商：'.$channel_name.' 商品【'.$cart['goods_name'].'】库存不足，购物车已调整，请重新下单';
            }

            //判断该商品在哪个平台的库存有没有超过
            $num = $cart['num'];
            $store_list = $channel_server->getChannelSkuStore($cart['sku_id'], $cart['channel_info']);
            $stock = $store_list['stock'];
//                var_dump($stock);exit;
            if($num > $stock){
                //修改购物车的数量，并提示不让其下单。
                $condition_cart['buyer_id'] = $cart['buyer_id'];
                $condition_cart['website_id'] = $website_id;
                $condition_cart['channel_info'] = $cart['channel_info'];
                $condition_cart['buy_type'] = $buy_type;
                $channel_server->updateChannelCart($condition_cart, $stock);
                return ['code'=>-1, 'message'=>$err_info];
            }
            //'商品总价',
            $goods_money += $cart['num']*$cart['price'];
            //'订单总价',
            //获取商品的运费
            if($buy_type == 'purchase'){
                $shipping_fee = 0;
            }else{
                $shipping_money = $this->resetShippingFee([$cart['goods_id']], [$cart['num']], $address['id']);
                $cart_arr[$k]['shipping_fee'] = $shipping_money;
                $shipping_fee += $shipping_money;
            }
//            $new_cart_arr[$old_goods['channel_info']][] =  $old_goods;
        }
        $goods_money = ($buy_type == 1)?$goods_money:0;
//        var_dump($goods_money,$shipping_fee);
//        echo '<pre>';print_R($cart_arr);exit;
        //'订单编号',
        $order_info['order_no'] = $order_no;
        //外部交易号
        $order_info['out_trade_no'] = $out_trade_no;
        //多店铺订单关联编号
        $order_info['order_sn'] = $out_trade_no;
        //'归属渠道商id',
//            $order_info['channel_info'] = $k;
        //'支付类型。
        $order_info['pay_type'] = '0';
        //'订单配送方式',
        if($buy_type == 1){
            $order_info['shipping_type'] = 2;//无需物流
        }else{
            $order_info['shipping_type'] = 1;
        }
        $order_info['shipping_type'] = 1;
        //'订单来源',
        $order_info['order_from'] = 2; // 手机
        //'买家id',
        $order_info['buyer_id'] = $uid;
        //'买家会员名称',
        $order_info['nick_name'] = $user_name;
        //'买家ip',
        $order_info['ip'] = $ip;
        //'买家附言',
        $order_info['buyer_message'] = $buyer_message;
        //'买家发票信息',
        $order_info['buyer_invoice'] = '';
        //'收货人的手机号码',
        $order_info['receiver_mobile'] = $address['mobile'];
        //'收货人信息',
        $order_info['receiver_province'] = $address['province'];
        $order_info['receiver_city'] = $address['city'];
        $order_info['receiver_district'] = $address['district'];
        //收货人详细地址
        $order_info['receiver_address'] = $address['address'];
        $order_info['receiver_zip'] = $address['zip_code'];
        $order_info['receiver_name'] = $address['consigner'];
        //'卖家店铺id',
        $order_info['shop_id'] = 0;
        //'卖家店铺名称',
        $shop_name = '自营店';
        if(getAddons('shop', $this->website_id)){
            $shop_model = new VslShopModel();
            $shop_info = $shop_model::get(['shop_id' => $order_info['shop_id'], 'website_id' => $this->website_id]);
            $shop_name = $shop_info['shop_name'];
        }
        $order_info['shop_name'] = $shop_name;
        //'卖家对订单的标注星标',
        $order_info['seller_star'] = '';
        //'卖家对订单的备注',
        $order_info['seller_memo'] = '';
        //'卖家延迟发货时间',
        $order_info['consign_time_adjust'] = '';
        $order_info['order_money'] = $shipping_fee + $goods_money;
        //'订单余额支付金额',
        $order_info['goods_money'] = $goods_money;
        $order_info['user_money'] = 0;
        //'用户平台余额支付',
        $order_info['user_platform_money'] = 0;
        //'订单运费',
        $order_info['shipping_money'] = $shipping_fee;
        //'订单实付金额',
        $order_info['pay_money'] = $shipping_fee + $goods_money;
        //'订单退款金额',
        $order_info['refund_money'] = 0;
        //'购物币金额',
        $order_info['coin_money'] = 0;
        //'订单成功之后返购物币',
        $order_info['give_coin'] = 0;
        if($order_info['pay_money'] == 0){//是0说明是自提免邮
            //'订单状态 0->未支付，1->已付款，2->已发货，3->确认收货,4->已完成,5->已关闭',
            $order_info['order_status'] = 1;
            //'订单付款状态,0->待支付，1->支付中，2->已支付',
            $order_info['pay_status'] = 2;
        }else{
            $order_info['order_status'] = 0;
            $order_info['pay_status'] = 0;
        }
        //`order_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '//订单类型 1-采购 2-提货',
        $order_info['buy_type'] = $buy_type;
        //`shipping_status` tinyint(4) NOT NULL COMMENT '订单配送状态',
        $order_info['shipping_status'] = '';
        //`review_status` tinyint(4) NOT NULL COMMENT '订单评价状态',
        $order_info['buyer_invoice'] = '';
        //`feedback_status` tinyint(4) NOT NULL COMMENT '订单维权状态',
        $order_info['buyer_invoice'] = '';
        $order_info['tax_money'] = 0; // 税费
        $order_info['create_time'] = time(); // 创建时间
        $order_info['website_id'] = $website_id;
        $order_info['sku_info'] = $cart_arr;
//            $channel_buy_str .= $cart['channel_info'].':'.$cart['sku_id'].':'.$cart['num'].' ';
        if($buy_type == 1){//采购
            $order_id = $channel_server->channelOrderCreate($order_info);
        }else{
            //自提的订单走正常流程，去掉QD
            $order_info['out_trade_no'] = trim($out_trade_no, 'QD');
            $order_info['order_sn'] = trim($out_trade_no, 'QD');
            $order_info['order_type'] = 1;//提货也是属于普通订单
            $order_id = $order_business->orderCreateNew($order_info);
        }

        //采购渠道商id：skuid：数量：采购价格
        //采购记录表 采购id、采购人渠道商id，被采购人渠道商id（有可能是platform），sku_id，购买多少，采购价格，采购人的采购折扣，商品售价（平台价），采购后剩余多少，购买方式（采购、自提、零售），创建时间
//        echo '<pre>';print_r(objToArr($cart_list));exit;
//        var_dump($order_id);exit;
        if($order_id>0){
            $channel_setting = $channel_server->getChannelConfig();
            // 还需要支付的订单发送已创建待支付订单短信 邮件通知
            $timeout = date('Y-m-d H:i:s', $order_info['create_time'] + $channel_setting['channel_order_close_time']*60);
            runhook('Notify', 'channelOrderCreateBySms', array('order_id' => $order_id, 'time_out' => $timeout));
            runhook('Notify', 'emailSend', ['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'order_id' => $order_id, 'notify_type' => 'user', 'time_out' => $timeout, 'template_code' => 'create_channel_order']);
            /*var_dump([
                'code' => 0,
                'message' => "提交成功",
                'data' => [
                    'out_trade_no' => $order_info['out_trade_no'],
                ]
            ]);exit;*/
            //删除购物车
            $channel_cart['buyer_id'] = $uid;
            $channel_cart['buy_type'] = $buy_type;
            $channel_cart['website_id'] = $website_id;
            $channel_server->deleteChannelCart($channel_cart);

            //支付订单号qd拼接，用于区分是渠道订单
            return [
                'code' => 0,
                'message' => "提交成功",
                'data' => [
                    'out_trade_no' => $order_info['out_trade_no'],
                ]
            ];
        }else{
            /*var_dump([
                'code' => -1,
                'message' => "订单创建失败",
            ]);exit;*/
            return [
                'code' => -1,
                'message' => "订单创建失败",
            ];
        }
    }
    public function test()
    {
        $channel_server = new ChannelServer();
//        $channel_order_mdl = new VslChannelOrderModel();
//        $order_info = $channel_order_mdl->getInfo(['order_id'=>146],'*');
//        $channel_server->calculateChannelBonus($order_info);
//        $channel_server->getPurchaseBatchRatio(19, 6, 6);
        $channel_server->updateChannelLevel(597, 16);
    }
    /*
     * 云仓库
     * **/
    public function cloudStorage()
    {
        //当前页数
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $stock_status = request()->post('stock_status',0);
        $channel_server = new ChannelServer();
        $uid = $this->uid;
        $channel_condition['c.uid'] = $uid;
        $channel_condition['c.website_id'] = $this->website_id;
        $channel_list = $channel_server->getMyChannelInfo($channel_condition);
        $channel_id = $channel_list['channel_id'];
        //获取我的所有商品
        $condition['cg.website_id'] = $this->website_id;
        $condition['cg.channel_id'] = $channel_id;
        $condition['cgs.channel_id'] = $channel_id;
        //1是还有库存。
        if($stock_status == 1){
            $condition['cgs.stock'] = ['>', 0];
        }else{
            $condition['cgs.stock'] = 0;
        }

        $order = 'cgs.create_date desc';
        $channel_goods_list = $channel_server->getPurchaseGoodsSkuInfo($page_index,$page_size,$condition,$order);
//        echo '<pre>';print_R(json_encode($channel_goods_list));exit;
        return $channel_goods_list;
    }
    /*
     * 云仓库明细
     * **/
    public function cloudStorageDetail()
    {
//        var_dump(12312312);exit;
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $tag_status = request()->post('tag_status',1);
        $sku_id = request()->post('sku_id',0);
        $channel_server = new ChannelServer();
        $website_id = $this->website_id;
        $condition['website_id'] = $website_id;
        $uid = $this->uid;
        $condition_channel['c.uid'] = $uid;
        $condition_channel['c.website_id'] = $website_id;
        $chanenl_info = $channel_server->getMyChannelInfo($condition_channel);
//        var_dump($chanenl_info);exit;
        $channel_id = $chanenl_info['channel_id'];
        $order = 'create_time desc';
        switch($tag_status){
            case '1':
                $condition['uid'] = $uid;
                $condition['buy_type'] = 1;
                $condition['sku_id'] = $sku_id;
                $purchase_list = $channel_server->getPurchaseRecordGoodsList($page_index,$page_size,$condition,$order,$channel_id,$tag_status, 1);
//                echo '<pre>';print_r($purchase_list);exit;
                break;
            case '2':
                $condition['channel_info'] = $channel_id;
                $condition['buy_type'] = 1;
                $condition['sku_id'] = $sku_id;
                $is_purchase = 1;
                $purchase_list = $channel_server->getPurchaseRecordGoodsList($page_index,$page_size,$condition,$order,$channel_id,$tag_status,$is_purchase);
//                echo '<pre>';print_r($purchase_list);exit;
                break;
            case '3':
                $condition['uid'] = $uid;
                $condition['buy_type'] = 2;
                $condition['sku_id'] = $sku_id;
                $purchase_list = $channel_server->getPurchaseRecordGoodsList($page_index,$page_size,$condition,$order,$channel_id,$tag_status, 2);
//                echo '<pre>';print_r($purchase_list);exit;
                break;
            case '4':
                $condition['channel_info'] = $chanenl_info['channel_id'];
                $condition['buy_type'] = 3;//零售
                $condition['sku_id'] = $sku_id;
                $is_purchase = 3;
                $purchase_list = $channel_server->getPurchaseRecordGoodsList($page_index,$page_size,$condition,$order,$channel_id,$tag_status,$is_purchase);
//                echo '<pre>';print_r($purchase_list);exit;
                break;
        }
//        echo json_encode($purchase_list);exit;
        return $purchase_list;
    }
    /*
     * 云仓库日志
     * **/
    public function cloudStorageLog()
    {
//        var_dump(12312312);exit;
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $channel_server = new ChannelServer();
        $website_id = $this->website_id;
        $condition['website_id'] = $website_id;
        $uid = $this->uid;
        $condition_channel['c.uid'] = $uid;
        $condition_channel['c.website_id'] = $website_id;
        $channel_info = $channel_server->getMyChannelInfo($condition_channel);
//        var_dump($chanenl_info);exit;
        $channel_id = $channel_info['channel_id'];
        $order = 'create_time desc';
        $condition['uid'] = $uid;
        $condition['buy_type'] = ['neq', 3];
        $condition['website_id'] = $website_id;
        $purchase_list = $channel_server->getCloudDetail($page_index, $page_size, $condition, $order, $channel_id);
//                echo '<pre>';print_r($purchase_list);exit;
        return $purchase_list;
    }
    /*
     * 我的业绩
     * **/
    public function MyChannelPerformance()
    {
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $channel_server = new ChannelServer();
        $website_id = $this->website_id;
        $uid = $this->uid;

        //获取本月的业绩
        $now_month = date('Y-m',time());
        $date_time = request()->post('date_time',$now_month);
//        var_dump($now_month);exit;
        //获取当月第一天及最后一天.
        $begin_date=date($date_time.'-01 00:00:00', strtotime(date("Y-m-d")));
        $end_date = date('Y-m-d 23:59:59', strtotime("$begin_date +1 month -1 day"));
        $begin_time = strtotime($begin_date);
        $end_time = strtotime($end_date);
        //传uid和channel_id、开始时间、结束时间获取对应金额
        $performance_arr['my_performance'] = $this->getKindMoney($uid, $website_id, $begin_time,$end_time);//我的业绩
        //获取我下面的是渠道商的人
        $condition4['m.referee_id'] = $uid;
        $condition4['m.website_id'] = $this->website_id;
        $condition4['c.status'] = 1;
        $all_my_down_channel_list = $channel_server->getAllDownChannelId($condition4);
        //所有的推荐人都是我
        $referee_condition['c.uid'] = $uid;
        $referee_condition['c.website_id'] = $website_id;
        $my_name_list = $channel_server->getChannelName($referee_condition);
        $referee_name = $my_name_list['nick_name']?:($my_name_list['user_name']?:$my_name_list['user_tel']);
        $count = count($all_my_down_channel_list);
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        for ($i = $offset; $i < $offset + $page_size; $i++) {
            if ($all_my_down_channel_list[$i]) {
                $all_my_down_channel_arr[] = $all_my_down_channel_list[$i];
            }
        }
        if (!empty($all_my_down_channel_arr)) {
            foreach($all_my_down_channel_arr as $k=>$v){
                //获取uid
                $condition_channel['c.channel_id'] = $v;
                $condition_channel['c.website_id'] = $this->website_id;
                $user_channel_info = $channel_server->getMyChannelInfo($condition_channel);
                $uid = $user_channel_info['uid'];

//            var_dump($uid);exit;
                //获取推荐人、上级、等级
                $down_channel_list = $channel_server->getDownChannelInfo($uid);
                $my_down_channel_performance['down_channel'][$k] = $this->getKindMoney($uid, $website_id, $begin_time,$end_time);
                $my_down_channel_performance['down_channel'][$k]['referee_name'] = $referee_name;
                $my_down_channel_performance['down_channel'][$k]['grade_name'] = $down_channel_list['grade_name'];
                $my_down_channel_performance['down_channel'][$k]['up_channel_name'] = $down_channel_list['up_channel_name'];
                $my_down_channel_performance['down_channel'][$k]['my_channel_id'] = $down_channel_list['my_channel_id'];
                $my_down_channel_performance['down_channel'][$k]['uid'] = $down_channel_list['uid'];
                $my_down_channel_performance['down_channel'][$k]['name'] = $user_channel_info['nick_name']?:($user_channel_info['user_name']?:$user_channel_info['user_tel']);
            }
            $performance_arr['down_channel'] = $my_down_channel_performance['down_channel'];
        }else{
            $performance_arr['down_channel'] = [];
        }

//        echo '<pre>';print_r($performance_arr);exit;
//        echo json_encode([
//            'code'=>0,'message'=>'获取成功',
//            'data' => $performance_arr,
//            'total_count' => $count,
//            'page_count' => $page_count
//        ]);exit;
//        return $performance_arr;
        return [
            'code'=>0,'message'=>'获取成功',
            'data' => [
                'data' => $performance_arr,
                'total_count' => $count,
                'page_count' => $page_count
            ]
        ];
    }
    /*
     * 我的团队
     * **/
    public function getMyTeam(){
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $channel_server = new ChannelServer();
        $website_id = $this->website_id;
        $uid = $this->uid;
        //获取我下面的是渠道商的人
        $condition4['m.referee_id'] = $uid;
        $condition4['m.website_id'] = $this->website_id;
        $condition4['c.status'] = 1;
        $all_my_down_channel_list = $channel_server->getAllDownChannelId($condition4);
        //所有的推荐人都是我
        $referee_condition['c.uid'] = $uid;
        $referee_condition['c.website_id'] = $website_id;
        $my_name_list = $channel_server->getChannelName($referee_condition);
        $referee_name = $my_name_list['nick_name']?:($my_name_list['user_name']?:$my_name_list['user_tel']);
        $count = count($all_my_down_channel_list);
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        for($i=$offset;$i<$offset+$page_size;$i++){
            if($all_my_down_channel_list[$i]){
                $all_my_down_channel_arr[] = $all_my_down_channel_list[$i];
            }
        }
        if(!empty($all_my_down_channel_arr)){
            foreach($all_my_down_channel_arr as $k=>$v){
                //获取uid
                $condition_channel['c.channel_id'] = $v;
                $condition_channel['c.website_id'] = $this->website_id;
                $user_channel_info = $channel_server->getMyChannelInfo($condition_channel);
                $uid = $user_channel_info['uid'];
                $user_grade_weight = $user_channel_info['weight'];
//            var_dump($uid);exit;
                //获取推荐人、上级、等级
                $down_channel_list = $channel_server->getDownChannelInfo($uid);
                $my_down_channel_performance['down_channel'][$k]['referee_name'] = $referee_name;
                $my_down_channel_performance['down_channel'][$k]['grade_name'] = $down_channel_list['grade_name'];
                $my_down_channel_performance['down_channel'][$k]['up_channel_name'] = $down_channel_list['up_channel_name'];
                $my_down_channel_performance['down_channel'][$k]['my_channel_id'] = $down_channel_list['my_channel_id'];
                $my_down_channel_performance['down_channel'][$k]['uid'] = $down_channel_list['uid'];
                $my_down_channel_performance['down_channel'][$k]['name'] = $user_channel_info['nick_name']?:($user_channel_info['user_name']?:$user_channel_info['user_tel']);
                //获取下级人数
                $condition1['m.referee_id'] = $uid;
                $condition1['m.isdistributor'] = 2;
                $condition1['m.website_id'] = $this->website_id;
                $condition1['c.status'] = 1;
                $condition1['cl.weight'] = ['<=', $user_grade_weight];
                $channel_id_arr = $channel_server->getAllDownChannelId($condition1);
                $channel_num = count($channel_id_arr);
                $my_down_channel_performance['down_channel'][$k]['down_channel_num'] = $channel_num;
                $channel_info['channel_num'] = $channel_num;
            }
//        p($my_down_channel_performance['down_channel']);exit;
            $performance_arr = $my_down_channel_performance['down_channel'];
            /*echo json_encode([
                'code'=>0,'message'=>'获取成功',
                'data' => $performance_arr,
                'total_count' => $count,
                'page_count' => $page_count
            ]);exit;*/
            return [
                'code'=>0,'message'=>'获取成功',
                'data' => [
                    'data' => $performance_arr,
                    'total_count' => $count,
                    'page_count' => $page_count
                ]

            ];
        }else{
            return [
                'code'=>0,'message'=>'获取成功',
                'data' => [
                    'data' => [],
                    'total_count' => 0,
                    'page_count' => 1
                ],
            ];
        }

    }
    /*
     * 获取各种金额
     * **/
    public function getKindMoney($uid, $website_id, $begin_time,$end_time)
    {
        $channel_server = new ChannelServer();
        $condition_channel['c.uid'] = $uid;
        $condition_channel['c.website_id'] = $website_id;
        $channel_info = $channel_server->getMyChannelInfo($condition_channel);
        $channel_id = $channel_info['channel_id'];
        //获取我现在的等级
        $my_weight = $channel_info['weight'];
        //累计采购金额
        $condition1['buyer_id'] = $uid;
        $condition1['website_id'] = $website_id;
        $condition1['buy_type'] = 1;//采购
        $condition1['pay_time'] = [
            ['>=', $begin_time],['<=', $end_time]
        ];
        $purchase_list = $channel_server->getPurchaseOrderNum($condition1);
//        $channel_info['purchase_num'] = $purchase_list['purchase_num'];//采购订单数
        $kind_money['my_purchase_money'] = $purchase_list['purchase_money']?:0;
        //累计利润,谁采购了我的
        $condition2['channel_info'] = $channel_id;
//        $condition2['channel_info_weight'] = $my_weight;
        $condition2['website_id'] = $website_id;
        $condition2['buy_type'] = 1;
        $condition2['create_time'] = [
            ['>=', $begin_time],['<=', $end_time]
        ];
        $my_profit = $channel_server->getPurchaseOrderGoodsList($condition2);
        $kind_money['my_profit'] = $my_profit?:0;

        //累计奖金
        $condition3['uid'] = $uid;
//        $condition3['my_channel_weight'] = $channel_info['weight']; //不管升不升级都显示累计奖金
        $condition3['add_time'] = [
            ['>=', $begin_time],['<=', $end_time]
        ];
//        $my_bonus = $channel_server->getMyBonus($channel_info['uid'], $channel_info['weight'], $my_proportion, $condition2);
        $my_bonus = $channel_server->getMyBonus($condition3);
        $kind_money['my_bonus'] = round($my_bonus, 2)?:0;

        //业绩，卖掉的商品
        $condition4['website_id'] = $website_id;
        $condition4['channel_info'] = $channel_id;
        $condition4['buy_type'] = ['<>', 2];
        $condition4['create_time'] = [
            ['>=', $begin_time],['<=', $end_time]
        ];
        $sale_money = $channel_server->getMyChannelPerformance($condition4);
        $kind_money['sale_money'] = $sale_money?:0;
        return $kind_money;
    }
    /*
     * 我的余额
     * **/
    public function MyChannelBalance()
    {
        $accountAccount = new MemberAccount();
        $accountSum = $accountAccount->getMemberAccount($this->uid);
        //判断后台开启的是哪种支付方式,如果是货款支付则不能提现
        $addons_config_model = new AddonsConfigModel();
        $value = $addons_config_model->Query(['addons' => 'channel','website_id' => $this->website_id],'value')[0];
        $value = json_decode($value,true);
        if($value['pay_type'] == 0) {
            //商城支付方式
        //判断是否开启提现
        $config_model = new Config();
        $config_info = $config_model->getConfig($this->instance_id,'WITHDRAW_BALANCE');
        if($config_info['is_use']==1){
            $data['is_use'] = 1;
        }else{
            $data['is_use'] = 0;
        }
            $data['can_use_money'] = $accountSum['balance'];
        }elseif ($value['pay_type'] == 1) {
            //货款支付
            $data['is_use'] = 0;
            $data['can_use_money'] = $accountSum['proceeds'];
            $data['is_proceeds'] = true;
        }

        $data['balance'] = $accountSum['balance']+ $accountSum['freezing_balance'];   //总金额
        $data['freezing_balance'] = $accountSum['freezing_balance'];//冻结金额
        //累计利润,谁采购了我的
        $uid = $this->uid;
        $channel_server = new ChannelServer();
        $condition['c.website_id'] = $this->website_id;
        $condition['c.uid'] = $uid;
        $channel_info = $channel_server->getMyChannelInfo($condition);
        $condition2['channel_info'] = $channel_info['channel_id'];
        $condition2['website_id'] = $this->website_id;
        $condition2['buy_type'] = 1;
        $my_profit = $channel_server->getPurchaseOrderGoodsList($condition2);
        $data['my_profit'] = $my_profit?:0;
        //累计奖金
//        $my_proportion['flat_first'] = $channel_info['flat_first'];
//        $my_proportion['flat_second'] = $channel_info['flat_second'];
//        $my_proportion['flat_third'] = $channel_info['flat_first'];
//        $my_proportion['cross_level'] = $channel_info['cross_level'];
        $condition3['uid'] = $uid;
        $condition3['my_channel_weight'] = $channel_info['weight'];
        $my_bonus = $channel_server->getMyBonus($condition3);
        $data['my_bonus'] = round($my_bonus, 2)?:0;
        $result['data'] = $data;
        $result['code'] = 0;
//        echo json_encode($result);exit;
        return json($result);
    }
    /*
     * 获取渠道商订单详情列表
     * **/
    public function getChannelOrderDetailList()
    {
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $buy_type = request()->post('buy_type', '');
        $order_status = request()->post('order_status', '');
        //搜索 订单号、店铺名称、商品名称
        $search_text = request()->post('search_text', '');

        $channel_server = new ChannelServer();
        $website_id = $this->website_id;
        $uid = $this->uid;
        $condition_channel['c.uid'] = $uid;
        $condition_channel['c.website_id'] = $website_id;
        $channel_info = $channel_server->getMyChannelInfo($condition_channel);
        switch($buy_type){
            //采购
            case 'purchase':
                if($order_status != ''){
                    $condition['co.order_status'] = $order_status;
                }
                $condition1['cog.goods_name'] = ['like','%'.$search_text.'%'];
                $condition1['co.order_no'] = ['like','%'.$search_text.'%'];
                $condition1['co.shop_name'] = ['like','%'.$search_text.'%'];
                $condition['co.website_id'] = $website_id;
                $condition['co.buyer_id'] = $uid;
                $condition['co.buy_type'] = 1;
                $order = 'co.create_time desc';
                $order_list = $channel_server->getChannelOrderDetail($page_index, $page_size, $condition, $condition1, $order);
                break;
            //出货
            case 'output':
                if($order_status != ''){
                    $condition['co.order_status'] = $order_status;
                }
                $condition1['cog.goods_name'] = ['like','%'.$search_text.'%'];
                $condition1['co.order_no'] = ['like','%'.$search_text.'%'];
                $condition1['co.shop_name'] = ['like','%'.$search_text.'%'];
                $condition['co.website_id'] = $website_id;
                $condition['cog.channel_info'] = $channel_info['channel_id'];
                $condition['co.buy_type'] = 1;
                $order = 'co.create_time desc';
                $order_list = $channel_server->getChannelOrderDetail($page_index, $page_size, $condition, $condition1, $order);
                break;
            //提货
            case 'pickupgoods':
                if($order_status != ''){
                    $condition['o.order_status'] = $order_status;
                }
                $condition1['og.goods_name'] = ['like','%'.$search_text.'%'];
                $condition1['o.order_no'] = ['like','%'.$search_text.'%'];
                $condition1['o.shop_name'] = ['like','%'.$search_text.'%'];
                $condition['o.website_id'] = $website_id;
                $condition['og.channel_info'] = $channel_info['channel_id'];
                $condition['o.buy_type'] = 2;
                $order = 'o.create_time desc';
                $order_list = $channel_server->getChannelPickOrderDetailList($page_index, $page_size, $condition, $condition1, $order);
                break;
            //零售
            case 'retail':
                if($order_status != ''){
                    $condition['order_status'] = $order_status;
                }
                $channel_info = $channel_info['channel_id'];
                $condition['buy_type'] = 0;
                $condition['channel_money'] = ['<>',0];
                $condition['website_id'] = $website_id;
                $order = 'create_time DESC';
                $order_list = $channel_server->getChanneRetaillOrderDetail($page_index, $page_size, $condition, $order,$search_text,$channel_info);
                break;
        }

        return $order_list;
    }
    /*
     * 根据订单id获取订单详情
     * **/
    public function getPurchaseOrderDetail()
    {
        $order_id = request()->post('order_id',0);
        $order_type = request()->post('order_type','');
        if(!$order_type || !$order_id){
            return  json(['code'=>-1,'message'=>'缺少参数']);
        }
        $channel_server = new ChannelServer();
        $website_id = $this->website_id;
        $uid = $this->uid;
        $condition_channel['c.uid'] = $uid;
        $condition_channel['c.website_id'] = $website_id;
        $channel_info = $channel_server->getMyChannelInfo($condition_channel);
        switch($order_type){
            case 'purchase':
                $condition['co.website_id'] = $this->website_id;
                $condition['co.order_id'] = $order_id;
                $condition['co.buy_type'] = 1;
                $channel_detail = $channel_server->getChannelSingleOrderDetail($condition,$order_type);
                break;
            case 'output':
                $condition['co.website_id'] = $this->website_id;
                $condition['co.order_id'] = $order_id;
                $condition['cog.channel_info'] = $channel_info['channel_id'];
                $condition['co.buy_type'] = 1;
                $channel_detail = $channel_server->getChannelSingleOrderDetail($condition,$order_type);
                break;
                //自提 用老的接口
            case 'pickupgoods':
                $channel_detail = $channel_server->getChannelPickOrderDetail($order_id);
                break;
                //零售
            case 'retail':
                $channel_detail = $channel_server->getChannelRetailOrderDetail($order_id);
                break;
        }
        /*echo json_encode(['code' => 0,
            'message' => '获取成功',
            'data' => $channel_detail
        ]);exit;*/
        return json(['code' => 0,
            'message' => '获取成功',
            'data' => $channel_detail
        ]);
    }
    /*
     * 渠道商订单关闭
     * **/
    public function channelOrderClose(){
        $order_id = request()->post('order_id',0);
        $order_type = request()->post('order_type','');
        if(!$order_type || !$order_id){
            return  json(['code'=>-1,'message'=>'缺少参数']);
        }
        $order_service = new OrderService();
        switch($order_type){
            case 'purchase':
                $res = $order_service->channelOrderClose($order_id);
                break;
            case 'output':
                $res = $order_service->channelOrderClose($order_id);
                break;
            //自提 用老的接口
            case 'pickupgoods':
                $res = $order_service->orderClose($order_id, 1);
                break;
        }
        if ($res) {
            return json(['code' => 1, 'message' => '取消成功']);
        } else {
            return json(['code' => -1, 'message' => '取消失败']);
        }
    }
    /*
     * 渠道商表单页
     * **/
    public function applayChannelForm()
    {
        //判断是否开启了channel
        $channel_status = getAddons('channel', $this->website_id);
        if(!$channel_status){
            return  json(['code'=>0,'message'=>'渠道商未开启']);
        }
        $channel_server = new ChannelServer();
        //获取条件
        $condition_val = $channel_server->getChannelConfig();
        //获取注册协议和图片
        $base_config = new Config();
        $channel_agreement = $base_config->getConfig(0,'CHANNEL');
        $info = json_decode($channel_agreement['value'], true);
        $agree_info['condition'] = $info['content']?:'';
        $agree_info['logo'] = $info['logo']?:'';
        //判断是否是渠道商
        $is_channel_info = $channel_server->isChannel(); //0-未申请 1-已申请未审核 2-已申请已审核
//        $res['is_channel'] = $is_channel_info['is_channel'];
//        $res['is_checked'] = $is_channel_info['is_checked'];
        $res['user_tel'] = $is_channel_info['user_tel'];
        $res['real_name'] = $is_channel_info['real_name'];
        $res['channel_agreement'] = $agree_info;
        if($condition_val['channel_condition'] != 'none'){
//            var_dump($condition_val['condition']);exit;
            $res['channel_condition'] = $condition_val['channel_condition'];
            $condition_arr = $channel_server->getChannelCondition($condition_val['condition'],$condition_val['channel_condition']);
            if($condition_arr['to_channel_status']){
                if($is_channel_info['is_checked'] == 0){//达到条件未申请
                    $res['is_checked'] = 0;
                }elseif($is_channel_info['is_checked'] == 1){//申请了未审核
                    $res['is_checked'] = 1;
                }elseif($is_channel_info['is_checked'] == 2){//申请了已审核
                    $res['is_checked'] = 2;
                }elseif($is_channel_info['is_checked'] == -1){//申请了拒绝
                    $res['is_checked'] = -1;
                }
            }else{
                $res['is_checked'] = -2;//未达到条件
            }
        }else{
            $res['channel_condition'] = $condition_val['channel_condition'];
            $condition_arr = (object)[];
            if($is_channel_info['is_checked'] == 0){//达到条件未申请
                $res['is_checked'] = 0;
            }elseif($is_channel_info['is_checked'] == 1){//申请了未审核
                $res['is_checked'] = 1;
            }elseif($is_channel_info['is_checked'] == 2){//申请了已审核
                $res['is_checked'] = 2;
            }elseif($is_channel_info['is_checked'] == -1){//申请了拒绝
                $res['is_checked'] = -1;
            }
        }
        $custom_arr = $channel_server->getCustomForm();
        $res['condition'] = $condition_arr;
        $res['customform'] = $custom_arr;
        /*echo '<Pre>';print_r($res);exit;
        echo json_encode([
            'code'=>0,
            'data'=>$res,
        ]);exit;*/
        return json([
            'code'=>0,
            'data'=>$res,
        ]);
    }
    /*
     * 申请成为渠道商
     * **/
    public function applayChannel()
    {
        $uid = $this->uid;
        if(empty($uid)){
            echo json_encode(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登陆'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $channel_server = new ChannelServer();
        $website_id =  $this->website_id;
        $post_data = request()->post('post_data')?:'';
        $mobile = request()->post('mobile','');
        $res= $channel_server->addChannelInfo($website_id,$uid, $post_data, $mobile);
        if($res>0){
            $data['code'] = 0;
            $data['message'] = "申请成功";
        }elseif($res==-2){
            $data['code'] = -2;
            $data['message'] = "手机号要与注册手机号一致";
        }elseif($res==-4){
            $data['code'] = -4;
            $data['message'] = "您已经申请过渠道商了";
        }else{
            $data['code'] = -1;
            $data['message'] = "申请失败";
        }
        if($res){
            $this->addUserLog('前台申请成为渠道商接口', $uid);
        }
        return json($data);
    }
    /*
     * 渠道商入口
     * **/
    public function channelIndex()
    {
        $uid = $this->uid;
        $member_mdl = new VslMemberModel();
        $distributor_info = $member_mdl->getInfo(['uid'=>$this->uid, 'isdistributor'=>2]);
        if(empty($distributor_info)){
            return ['code'=>0,'message'=>'成为分销商才能申请渠道商'];
        }
        $channel_sever = new channelServer();
        $condition['c.uid'] = $uid;
        $channel_info = $channel_sever->getChannelDetail($condition);
        $new_data['name'] = $channel_info['nick_name']?:($channel_info['user_name']?:$channel_info['user_tel']);
        $new_data['user_headimg'] = $channel_info['user_headimg'];
        $new_data['channel_grade_name'] = $channel_info['channel_grade_name'];
        $new_data['referee_name'] = $channel_info['referee_name'];
        $new_data['to_channel_time'] = $channel_info['to_channel_timestamp'];
        $new_data['is_channel'] = $channel_info['channel_id'] ? true : false;
        if($channel_info['channel_id']){
            if($channel_info['status'] == 1){
                $channel_info['is_checked'] = 2;//已审核
            }elseif($channel_info['status'] == 0){
                $channel_info['is_checked'] = 1;//未审核
            }elseif($channel_info['status'] == -1){
                $channel_info['is_checked'] = -1;//审核未通过
            }
        }else{
            $channel_info['is_checked'] = 0;//未申请
        }
        return ['code'=>1,'message'=>'获取成功','data'=>$new_data];
    }
    /**
     * B端采购订单
     */
    public function purchaseOrderList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $start_create_date = request()->post('start_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_create_date'));
        $end_create_date = request()->post('end_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_create_date'));
        $start_pay_date = request()->post('start_pay_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_pay_date'));
        $end_pay_date = request()->post('end_pay_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_pay_date'));
        $start_finish_date = request()->post('start_finish_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_finish_date'));
        $end_finish_date = request()->post('end_finish_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_finish_date'));
        $user = request()->post('user', '');
        $order_no = request()->post('order_no', '');
        $order_status = request()->post('order_status', '');
        $payment_type = request()->post('payment_type', '');
        $goods_code = request()->post('goods_code', '');
        $goods_name = request()->post('goods_name', '');
        if ($goods_name) {
            $condition1['goods_name'] = ['LIKE', '%' . $goods_name . '%'];
        }
        if ($goods_code) {
            $condition1['goods_code'] = ['LIKE', '%' . $goods_code . '%'];
        }
        if ($start_create_date) {
            $condition['create_time'][] = ['>=', $start_create_date];
        }
        if ($end_create_date) {
            $condition['create_time'][] = ['<=', $end_create_date + 86399];
        }
        if ($start_pay_date) {
            $condition['pay_time'][] = ['>=', $start_pay_date];
        }
        if ($end_pay_date) {
            $condition['pay_time'][] = ['<=', $end_pay_date + 86399];
        }
        if ($start_finish_date) {
            $condition['pay_time'][] = ['>=', $start_finish_date];
        }
        if ($end_finish_date) {
            $condition['pay_time'][] = ['<=', $end_finish_date + 86399];
        }
        if($order_status != ''){
            $condition['order_status'] = $order_status;
        }
        if (!empty($payment_type)) {
            $condition['payment_type'] = $payment_type;
        }
        if (!empty($user)) {
            $condition2['user_tel|uid|user_name|nick_name'] = array(
                'like',
                '%' . $user . '%'
            );
        }
        if (!empty($order_no)) {
            $condition['order_no'] = array(
                'like',
                '%' . $order_no . '%'
            );
        }
        $condition['website_id'] = $this->website_id;
        $condition['buy_type'] = 1;
        $order = 'create_time desc';
        $channel_server = new ChannelServer();
        $order_list = $channel_server->purchaseOrderList($page_index, $page_size, $condition, $condition1, $condition2, $order);
        return $order_list;
    }
    /**
     * 货款流水
     */
    public function proceedsList()
    {
        if (request()->isAjax()) {
            $member = new MemberService();
            $page_index = request()->post("page_index",1);
            $records_no = request()->post('records_no','');
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text', '');
            $form_type = request()->post('form_type','');
            $start_date = request()->post('start_date') == "" ? '2010-1-1' : request()->post('start_date');
            $end_date = request()->post('end_date') == "" ? '2038-1-1' : request()->post('end_date');
            $condition['nmar.website_id'] = $this->website_id;
            $condition['nmar.account_type'] = 5;
            if ($records_no != '') {
                $condition['nmar.records_no'] = $records_no;
            }
            $condition['su.nick_name|su.user_tel|su.user_name|su.uid'] = [
                'like',
                '%' . $search_text . '%'
            ];
            $condition["nmar.create_time"] = [
                [
                    ">",
                    strtotime($start_date)
                ],
                [
                    "<",
                    strtotime($end_date)
                ]
            ];
            if ($form_type != '') {
                $condition['nmar.from_type'] = $form_type;
            }
            $list = $member->getAccountList($page_index, $page_size, $condition, $order = '', $field = '*');
            return $list;
        }
        return view($this->style . 'Finance/proceedsList');
    }
    /**
     * 货款数据excel导出
     */
    public function proceedsDataExcel()
    {
        $xlsName = "货款流水列表";
        $xlsCell = [
            0=>['records_no','流水号'],
            1=>['nick_name','会员'],
            2=>['type_name','变动类型'],
            3=>['number','变动金额'],
            4=>['text','描述'],
            5=>['create_time','创建时间']
        ];
        $member = new MemberService();
        $records_no = request()->get('records_no','');
        $search_text = request()->get('search_text', '');
        $form_type = request()->get('form_type');
        $start_date = request()->get('start_date') == "" ? '2010-1-1' : request()->get('start_date');
        $end_date = request()->get('end_date') == "" ? '2038-1-1' : request()->get('end_date');
        if ($records_no != '') {
            $condition['nmar.records_no'] = $records_no;
        }
        $condition['nmar.account_type'] = 5;
        $condition['nmar.website_id'] = $this->website_id;
        if($search_text){
            $condition['su.nick_name|su.user_tel|su.user_name'] = [
                'like',
                '%' . $search_text . '%'
            ];
        }
        $condition["nmar.create_time"] = [
            [
                ">",
                strtotime($start_date)
            ],
            [
                "<",
                strtotime($end_date)
            ]
        ];
        if ($form_type != '') {
            $condition['nmar.from_type'] = $form_type;
        }
        $list = $member->getAccountList(1,0, $condition, $order = '', $field = '*');
        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]["number"] = '¥'.$v["number"];
            $list['data'][$k]['records_no'] = $v['records_no']."\t";
        }
        dataExcel($xlsName, $xlsCell, $list['data']);
    }
}