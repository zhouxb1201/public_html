<?php
namespace addons\areabonus\controller;
use addons\areabonus\Areabonus as baseAreaBonus;
use addons\areabonus\service\AreaBonus as agentService;
use addons\distribution\model\SysMessageItemModel;
use addons\distribution\model\SysMessagePushModel;
use data\service\Config;
use think\helper\Time;
use data\service\Address;
use addons\bonus\model\VslAgentLevelModel as AgentLevelModel;
        /**
         * 区域代理设置控制器
         *
         * @author  www.vslai.com
         *
         */
        class Areabonus extends baseAreaBonus
        {
            public function __construct(){
                parent::__construct();
            }

        /**
         * 区域代理列表
         */
        public function areaAgentList(){
            $page_index = request()->post('page_index',1);
            $iphone = request()->post('iphone',"");
            $search_text = request()->post('search_text','');
            $agent_level_id = request()->post('level_id','');
            $isagent = request()->post('is_area_agent','');
            if( $search_text){
                $condition['us.user_name|us.nick_name'] = array('like','%'.$search_text.'%');
            }
            if($iphone ){
                $condition['nm.mobile'] = $iphone;
            }
            if($isagent!=5){
                $condition['nm.is_area_agent'] = $isagent;
            }else{
                $condition['nm.is_area_agent'] = ['in','1,2,-1'];
            }
            if($agent_level_id){
                $condition['nm.area_agent_level_id'] = $agent_level_id;
            }
            $condition['nm.website_id'] = $this->website_id;
            $agent = new agentService();
            $list = $agent->getagentList($page_index, PAGESIZE, $condition,'become_area_agent_time desc');
            return $list;
        }
            
        /**
         * 修改代理状态
         */
        public function setAreaAgentStatus(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $uid = request()->post('uid','');
            $status = request()->post('status','');
            $agent = new agentService();
            $retval = $agent->setStatus($uid, $status);
            if($retval){
                $this->addUserLog('修改代理状态', '代理id'.$uid);
            }
            return AjaxReturn($retval);
        }

        /**
         * 移除代理
         */
        public function delAreaAgent(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $member = new agentService();
            $uid = request()->post("uid", '');
            $res = $member->deleteagent($uid);
            if($res){
                $this->addUserLog('移除代理', '代理id'.$uid);
            }
            return AjaxReturn($res);
        }

        /**
         * 修改代理信息
         */
        public function updateAreaAgentInfo(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $member = new agentService();
            $uid = request()->post("uid", '');
            $agent_level_id = request()->post("area_agent_level_id", '');
            $area_id = request()->post("area_id", '');
            $status = request()->post("status", '');
            $agent_area_id = request()->post("agent_area_id", '');
            $area_leg = request()->post("area_leg", '');
            $data = [
                'area_agent_level_id'=> $agent_level_id,
                'area_type'=> $area_id,
                'area_leg'=> $area_leg,
                'agent_area_id'=> $agent_area_id,
                'is_area_agent'=>$status
            ];
            $res= $member->updateagentInfo($data,$uid);
            if($res){
                $this->addUserLog('修改代理信息', '代理id'.$uid);
            }
            return AjaxReturn($res);
        }

        /**
         * 代理等级列表
         */
        public function areaAgentLevelList(){
            $page_index = isset($_POST["page_index"]) ? $_POST["page_index"] : 1;
            $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
            $agent = new agentService();
            $list =  $agent->getagentLevelList($page_index, PAGESIZE, ['level_name' => array('like','%'.$search_text.'%'),'website_id'=>$this->website_id,'from_type'=>2],'weight asc');
            return json($list);
        }

        /**
         * 添加代理等级
         */
        public function addAreaAgentLevel(){

            $level_name = isset($_POST['level_name'])?$_POST['level_name']:'';//等级名称
            $province_ratio = isset($_POST['province_ratio'])?$_POST['province_ratio']:0;//省分红比例
            $city_ratio = isset($_POST['city_ratio'])?$_POST['city_ratio']:0;//市分红比例
            $area_ratio = isset($_POST['area_ratio'])?$_POST['area_ratio']:0;//区分红比例
            $upgradetype = isset($_POST['upgradetype'])?$_POST['upgradetype']:2;//自动升级
            $pay_money  = request()->post('pay_money', ''); // 自购订单消费金额额度
            $offline_number  = request()->post('offline_number', ''); // 客户人数
            $one_number = request()->post('one_number', '');//一级分销商人数
            $two_number = request()->post('two_number', '');//二级分销商人数
            $three_number = request()->post('three_number', '');//三级分销商人数
            $order_money = request()->post('order_money', ''); // 下级订单总额
            $up_team_money = request()->post('up_team_money', ''); // 团队订单金额
            $downgradetype = isset($_POST['downgradetype'])?$_POST['downgradetype']:2;//自动降级
            $team_number = isset($_POST['team_number'])?$_POST['team_number']:'';//团队人数
            $team_money = isset($_POST['team_money'])?$_POST['team_money']:'';//团队订单金额
            $self_money = isset($_POST['self_money'])?$_POST['self_money']:'';//自购订单金额
            $team_number_day = isset($_POST['team_number_day'])?$_POST['team_number_day']:'';//时间段：团队人数
            $team_money_day = isset($_POST['team_money_day'])?$_POST['team_money_day']:'';//时间段：团队订单金额
            $self_money_day = isset($_POST['self_money_day'])?$_POST['self_money_day']:'';//时间段：自购订单金额
            $weight = isset($_POST['weight'])?$_POST['weight']:'';//权重
            $downgrade_condition = isset($_POST['downgrade_condition'])?$_POST['downgrade_condition']:'';//升级条件
            $upgrade_condition = isset($_POST['upgrade_condition'])?$_POST['upgrade_condition']:'';//降级条件
            $downgradeconditions = isset($_POST['downgradeconditions'])?$_POST['downgradeconditions']:'';//升级条件
            $upgradeconditions = isset($_POST['upgradeconditions'])?$_POST['upgradeconditions']:'';//降级条件
            $goods_id = isset($_POST['goods_id'])?$_POST['goods_id']:'';//指定商品id
            $upgrade_level = isset($_POST['upgrade_level'])?$_POST['upgrade_level']:'0';//推荐等级
            $level_number = isset($_POST['level_number'])?$_POST['level_number']:'0';//推荐等级人数
            $group_number  = request()->post('group_number', ''); // 团队人数
            $agent=new agentService();
            $retval=$agent->addagentLevel($level_name,$province_ratio,$city_ratio,$area_ratio,$upgradetype,$pay_money,$offline_number,$one_number,$two_number,$three_number,$order_money,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$group_number,$up_team_money);
            if($retval){
                $this->addUserLog('添加代理等级', $retval);
            }
            return AjaxReturn($retval);
        }
        /**
         * 修改代理等级
         */
        public function updateAreaAgentLevel(){
            $agent=new agentService();
            $id = request()->post('id', '');
            $level_name = isset($_POST['level_name'])?$_POST['level_name']:'';//等级名称
            $province_ratio = isset($_POST['province_ratio'])?$_POST['province_ratio']:'';//省分红比例
            $city_ratio = isset($_POST['city_ratio'])?$_POST['city_ratio']:0;//市分红比例
            $area_ratio = isset($_POST['area_ratio'])?$_POST['area_ratio']:0;//区分红比例
            $upgradetype = isset($_POST['upgradetype'])?$_POST['upgradetype']:2;//自动升级
            $pay_money  = request()->post('pay_money', ''); // 自购订单消费金额额度
            $offline_number  = request()->post('offline_number', ''); // 客户人数
            $one_number = request()->post('one_number', '');//一级分销商人数
            $two_number = request()->post('two_number', '');//二级分销商人数
            $three_number = request()->post('three_number', '');//三级分销商人数
            $order_money = request()->post('order_money', ''); // 下级订单总额
            $up_team_money = request()->post('up_team_money', ''); // 团队订单金额
            $downgradetype = isset($_POST['downgradetype'])?$_POST['downgradetype']:2;//自动降级
            $team_number = isset($_POST['team_number'])?$_POST['team_number']:'';//团队人数
            $team_money = isset($_POST['team_money'])?$_POST['team_money']:'';//团队订单金额
            $self_money = isset($_POST['self_money'])?$_POST['self_money']:'';//自购订单金额
            $team_number_day = isset($_POST['team_number_day'])?$_POST['team_number_day']:'';//时间段：团队人数
            $team_money_day = isset($_POST['team_money_day'])?$_POST['team_money_day']:'';//时间段：团队订单金额
            $self_money_day = isset($_POST['self_money_day'])?$_POST['self_money_day']:'';//时间段：自购订单金额
            $weight = isset($_POST['weight'])?$_POST['weight']:'';//权重
            $downgrade_condition = isset($_POST['downgrade_condition'])?$_POST['downgrade_condition']:'';//升级条件
            $upgrade_condition = isset($_POST['upgrade_condition'])?$_POST['upgrade_condition']:'';//降级条件
            $downgradeconditions = isset($_POST['downgradeconditions'])?$_POST['downgradeconditions']:'';//升级条件
            $upgradeconditions = isset($_POST['upgradeconditions'])?$_POST['upgradeconditions']:'';//降级条件
            $goods_id = isset($_POST['goods_id'])?$_POST['goods_id']:'';//指定商品id
            $upgrade_level = isset($_POST['upgrade_level'])?$_POST['upgrade_level']:'0';//推荐等级
            $level_number = isset($_POST['level_number'])?$_POST['level_number']:'0';//推荐等级人数
            $group_number  = request()->post('group_number', ''); // 团队人数
            $retval=$agent->updateagentLevel($id,$level_name,$province_ratio,$city_ratio,$area_ratio,$upgradetype,$pay_money,$offline_number,$one_number,$two_number,$three_number,$order_money,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$group_number,$up_team_money);
            if($retval){
                $this->addUserLog('修改代理等级', '代理id'.$id);
            }
            return AjaxReturn($retval);
        }
        /**
         * 删除 代理等级
         */
        public function deleteAreaAgentLevel()
        {
            $agent = new agentService();
            $id = request()->post("id", "");
            $res = $agent->deleteagentLevel($id);
            if($res){
                $this->addUserLog('删除代理等级', '代理id'.$id);
            }
            return AjaxReturn($res);
        }

        /**
         * 代理分红订单
         */
        public function areaAgentOrderList(){
            $member = new agentService();
            $uid = request()->post('uid', "");
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $start_create_date = request()->post('start_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_create_date'));
            $end_create_date = request()->post('end_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_create_date'));
            $start_pay_date = request()->post('start_pay_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_pay_date'));
            $end_pay_date = request()->post('end_pay_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_pay_date'));
            $start_send_date = request()->post('start_send_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_send_date'));
            $end_send_date = request()->post('end_send_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_send_date'));
            $start_finish_date = request()->post('start_finish_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_finish_date'));
            $end_finish_date = request()->post('end_finish_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_finish_date'));
            $user = request()->post('user', '');
            $order_no = request()->post('order_no', '');
            $order_status = request()->post('order_status', '');
            $payment_type = request()->post('payment_type', '');
            $express_no = request()->post('express_no', '');
            $goods_name = request()->post('goods_name', '');
            $order_type = request()->post('order_type', '');
            $order_id_array = request()->post('order_id_array/a');
            $delivery_order_status = request()->post('delivery_order_status');
            $express_order_status = request()->post('express_order_status');
            $condition['is_deleted'] = 0; // 未删除订单
            if ($express_no) {
                $condition['express_no'] = ['LIKE', '%' . $express_no . '%'];
            }
            if ($goods_name) {
                $condition['goods_name'] = ['LIKE', '%' . $goods_name . '%'];
            }
            if ($order_type) {
                $condition['order_type'] = $order_type;
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
            if ($start_send_date) {
                $condition['consign_time'][] = ['>=', $start_send_date];
            }
            if ($end_send_date) {
                $condition['consign_time'][] = ['<=', $end_send_date + 86399];
            }
            if ($start_finish_date) {
                $condition['finish_time'][] = ['>=', $start_finish_date];
            }
            if ($end_finish_date) {
                $condition['finish_time'][] = ['<=', $end_finish_date + 86399];
            }
            if ($order_status != '') {
                // $order_status 1 待发货
                if ($order_status == 1) {
                    // 订单状态为待发货实际为已经支付未完成还未发货的订单
                    $condition['shipping_status'] = 0; // 0 待发货
                    $condition['pay_status'] = 2; // 2 已支付
                    //$condition['store_id'] = 0; // 2 已支付
                    $condition['order_status'][] = array(
                        'neq',
                        4
                    ); // 4 已完成
                    $condition['order_status'][] = array(
                        'neq',
                        5
                    ); // 5 关闭订单
                    $condition['order_status'][] = array(
                        'neq',
                        -1
                    ); // -1 售后
                    //$condition['vgsr_status'] = 2;
                } elseif ($order_status == 10) {// 拼团，已支付未成团订单
                    $condition['vgsr_status'] = 1;
                } elseif ($order_status == 11) {// 拼团，已支付未成团订单
                    $condition['store_id'] = ['>', 0];
                    $condition['order_status'] = 1;
                } else {
                    $condition['order_status'] = $order_status;
                }
            } else {
//                //不包括售后订单
//                $condition['order_status'] = array(
//                    '>=',
//                    0
//                );
            }
            if (!empty($payment_type)) {
                $condition['payment_type'] = $payment_type;
            }
            if (!empty($user)) {
                $condition['receiver_name|receiver_mobile|user_name|buyer_id'] = array(
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
            if ($delivery_order_status){
                $condition['delivery_order_status'] = $delivery_order_status;
            }
            if ($express_order_status){
                $condition['express_order_status'] = $express_order_status;
            }
            if ($order_id_array) {
                $condition['order_id'] = ['IN', $order_id_array];
            }
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = 0;
            if (request()->post('order_amount')){
                $condition['order_amount'] = true;
            }
            if (request()->post('order_memo')){
                $condition['order_memo'] = true;
            }
            $condition['buyer_id'] =$uid;
            $res= $member->getAreaOrderList($page_index, $page_size, $condition, 'create_time desc');
            return json($res);
        }

        /**
         * 分红概况
         */
        public function areaBonusProfile()
            {
                $agent_level = new AgentLevelModel();
                $level_info = $agent_level->getInfo(['website_id' => $this->website_id,'from_type'=>2,'is_default'=>1],'*');
                    if($level_info){

                    }else{
                        $data = array(
                            'level_name' => '默认区域代理等级',
                            'is_default'=>1,
                            'weight' => 1,
                            'from_type'=>2,
                            'province_ratio'=>0,
                            'city_ratio'=>0,
                            'area_ratio'=>0,
                            'create_time' => time(),
                            'website_id' => $this->website_id
                        );
                        $agent_level->save($data);
                    }
                $website_id = $this->website_id;
                $agent = new agentService();
                $data = $agent->getagentCount($website_id);
                return $data;
            }
            /**
             * 分红订单概况
             */
            public function areaBonusOrderProfile()
            {
                $website_id = isset($_POST['website_id'])?$_POST['website_id']:$this->website_id;
                $order_bonus = new agentService();
                list($start, $end) = Time::dayToNow(6,true);
                $orderType = ['订单金额','订单分红'];
                $data = array();
                $data['ordertype'] = $orderType;
                for($i=0;$i<count($orderType);$i++){
                    switch ($orderType[$i]) {
                        case '订单金额':
                            $status = 1;
                            break;
                        case '订单分红':
                            $status = 2;
                            break;
                    }
                    for($j=0;$j<($end+1-$start)/86400;$j++){
                        $data['day'][$j]= date("Y-m-d",$start+86400*$j);
                        $date_start =  strtotime(date("Y-m-d H:i:s",$start+86400*$j));
                        $date_end =  strtotime(date("Y-m-d H:i:s",$start+86400*($j+1)));
                        if($status ==1){
                            $count = $order_bonus->getOrderMoneySum(['order_status'=>['between',[1,4]],'website_id'=>$website_id,'create_time'=>['between',[$date_start,$date_end]]]);
                        }
                        if($status == 2){
                            $count = $order_bonus->getPayMoneySum(['order_status'=>['between',[1,4]],'website_id'=>$website_id,'create_time'=>['between',[$date_start,$date_end]]]);
                        }
                        $aCount[$j] = $count;
                        $data['all'][$i]['name'] = $orderType[$i];
                        $data['all'][$i]['type'] = 'line';
                        $data['all'][$i]['data'] = $aCount;
                    }
                }
                return $data;
            }

        /**
         * 基本设置
         */
        public function areaBasicSetting()
        {
            $config= new agentService();
            if (request()->isPost()) {
                // 基本设置
                $agent_check = request()->post('areaagent_check', ''); // 是否开启自动审核
                $agent_grade = request()->post('areaagent_grade', ''); // 是否开启跳级降级设置
                $areabonus_status = request()->post('areabonus_status', ''); // 是否开启区域分红
                $purchase_type = request()->post('purchase_type', ''); // 是否开启区域分红内购
                $agent_status = request()->post('areaagent_status', '');//是否开启直接申请成为代理
                $retval = $config->setAreaBonusSite($areabonus_status,$agent_status,$agent_check, $agent_grade, $purchase_type);
                if($retval){
                    $this->addUserLog('区域代理基本设置', $retval);
                }
                setAddons('areabonus', $this->website_id, $this->instance_id);
                setAddons('areabonus', $this->website_id, $this->instance_id, true);
                return AjaxReturn($retval);
            }
        }

        /**
         * 结算设置
         */
        public function areaSettlementSetting()
        {
            $config= new agentService();
            if (request()->isPost()) {
                // 结算设置
                $province_ratio = request()->post('province_ratio', ''); // 省级代理分红比例
                $city_ratio = request()->post('city_ratio', ''); // 市级代理分红比例
                $area_ratio = request()->post('area_ratio', '');//区级代理分红比例
                $bonus_calculation = request()->post('bonus_calculation', ''); // 分红计算节点
                $withdrawals_check = request()->post('withdrawals_check', ''); // 自动分红是否开启
                $limit_time = request()->post('limit_time', ''); // 分红发放时间
                $limit_date = request()->post('limit_date', ''); // 分红发放指定日期
                $bonus_poundage = request()->post('bonus_poundage', ''); // 分红比例
                $poundage = request()->post('poundage', ''); // 个人所得税
                $withdrawals_begin = request()->post('withdrawals_begin', ''); // 分红免打税区间
                $withdrawals_end = request()->post('withdrawals_end', ''); // 分红免打税区间
                $retval = $config->setSettlementSite($province_ratio,$city_ratio,$area_ratio,$bonus_calculation, $limit_time,$withdrawals_check,$bonus_poundage,$poundage,$withdrawals_begin,$withdrawals_end,$limit_date);
                if($province_ratio+$city_ratio+$area_ratio > 100){
                    return AjaxReturn(-2);
                }
                if($retval){
                    $this->addUserLog('结算设置', $retval);
                }
                return AjaxReturn($retval);
            }
        }
        /**
         * 申请协议
         */
        public function areaApplicationAgreement()
        {
            $config= new agentService();
            if (request()->isPost()) {
                // 基本设置
                $type = request()->post('type', 2);
                $logo = request()->post('image', ''); // 协议内容
                $content = request()->post('content', ''); // 协议内容
                $bonus_name = request()->post('bonus_name', ''); // 分红中心
                $bonus = request()->post('bonus', ''); // 分红
                $withdrawals_bonus = request()->post('withdrawals_bonus', ''); // 已发放分红
                $withdrawal_bonus = request()->post('withdrawal_bonus', ''); // 待发放分红
                $frozen_bonus = request()->post('frozen_bonus', ''); // 冻结分红
                $bonus_details = request()->post('bonus_details', ''); // 分红明细
                $bonus_money = request()->post('bonus_money', ''); // 分红金额
                $bonus_order = request()->post('bonus_order', ''); // 分红订单
                $withdrawals_area_bonus = request()->post('withdrawals_area_bonus', ''); // 已发放分红
                $withdrawal_area_bonus = request()->post('withdrawal_area_bonus', ''); // 待发放分红
                $frozen_area_bonus = request()->post('frozen_area_bonus', ''); // 冻结分红
                $apply_area = request()->post('apply_area', ''); // 申请区域代理
                $area_agreement = request()->post('area_agreement', ''); // 区域代理
                if($content){
                    $content = htmlspecialchars_decode($content);
                }
                if($type==1){
                    $configs = new Config();
                    $configs->setSite($bonus_name,$bonus,$withdrawals_bonus,$withdrawal_bonus,$frozen_bonus,$bonus_details,$bonus_money,$bonus_order);
                }
                $retval = $config->setAgreementSite($type,$logo,$content,$withdrawals_area_bonus,$withdrawal_area_bonus,$frozen_area_bonus,$apply_area,$area_agreement);
                if($retval){
                    $this->addUserLog('申请协议', $retval);
                }
                return AjaxReturn($retval);
            }
        }
            /**
             * 后台区域分红明细
             */
            public function areaBonusList(){
                $page_index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition = array();
                $condition['nmar.from_type'] = 2;
                $condition['nmar.website_id'] = $this->website_id;
                $group = 'nmar.sn';
                $bonus = new agentService();
                $list = $bonus->getBonusDetailList($page_index, $page_size,$condition,$group);
                return $list;
            }
            /**
             * 后台区域分红详情
             */
            public function areaBonusInfo(){
                if(request()->isPost()){
                    $member_name = request()->post('member_name','');
                    $agent_level_id = request()->post('level_id','');
                    $mobile = request()->post('mobile','');
                    $page_index = request()->post('page_index', 1);
                    $page_size = request()->post('page_size', PAGESIZE);
                    $sn= request()->post('sn', '');
                    if($member_name){
                        $where['us.user_name|us.nick_name'] = array('like','%'.$member_name.'%');
                    }
                    if($mobile){
                        $where['sm.mobile'] = $mobile;
                    }
                    if($agent_level_id){
                        $where['sm.area_agent_level_id'] = $agent_level_id;
                    }
                    $condition = array();
                    $condition['nmar.from_type'] = 2;
                    $condition['nmar.sn'] = $sn;
                    $condition['nmar.website_id'] =  $this->website_id;
                    $bonus = new agentService();
                    $list = $bonus->getBonusInfoList($page_index, $page_size,$condition,'',$where);
                    return $list;
                }
            }
            /**
             * 区域分红结算单
             */
            public function areaBonusBalance()
            {
                $page_index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition = array();
                $condition['nmar.from_type'] = 2;
                $condition['nmar.ungrant_bonus'] = ['>',0];
                $condition['nmar.website_id'] =  $this->website_id;
                $agent = new agentService();
                $data = $agent->getUnGrantBonus($page_index, $page_size, $condition, $order = '', $field = '*');
                return $data;
            }
            /**
             * 未分红发放
             */
            public function areaBonusGrant()
            {
                $agent = new agentService();
                $data = $agent->grantAreaBonus(1);
                return AjaxReturn($data);
            }
            public function areaMessagePushList(){
                $message = new SysMessagePushModel();
                $list = $message->getQuery(['website_id'=>$this->website_id,'type'=>3],'*','template_id asc');
                if(empty($list)){
                    $list = $message->getQuery(['type'=>3,'website_id'=>0],'*','template_id asc');
                    foreach ($list as $k=>$v){
                        $array = [
                            'template_type' => $v['template_type'],
                            'template_title' => $v['template_title'],
                            'sign_item' => $v['sign_item'],
                            'sample' => $v['sample'],
                            'type'=>3,
                            'website_id' => $this->website_id,
                        ];
                        $message = new SysMessagePushModel();
                        $message->save($array);
                    }
                    $list = $message->getQuery(['website_id'=>$this->website_id,'type'=>3],'*','template_id asc');
                }
                return $list;
            }
            public function areaEditMessage(){
                $id = request()->post('id', '');
                $message = new SysMessagePushModel();
                $list = $message->getInfo(['template_id'=>$id],'*');
                $item = new SysMessageItemModel();
                $list['sign'] = $item->getQuery(['id'=>['in',$list['sign_item']]],'*','');
                return $list;
            }
            public function addAreaMessage(){
                $is_enable = request()->post('is_enable', '');
                $id = request()->post('id', '');
                $template_content = request()->post('template_content', '');
                $message = new SysMessagePushModel();
                $res = $message->save(['is_enable'=>$is_enable,'template_content'=>$template_content],['template_id'=>$id]);
                return AjaxReturn($res);
            }

            /**
             * 前台申请成为区域代理接口
             */
            public function areaAgentApply(){
                $uid = $this->uid;
                $website_id =  $this->website_id;
                $province_id = request()->post('province_id');
                $city_id = request()->post('city_id');
                $district_id = request()->post('district_id');
                $area_id = request()->post('area_id');
                $post_data = request()->post('post_data','');
                $real_name = request()->post('real_name ','');
                if($area_id==1){
                    $agent_area_id =  $province_id.',p';
                }
                if($area_id==2){
                    $agent_area_id =  $province_id.','.$city_id.',c';
                }
                if($area_id==3){
                    $agent_area_id =  $province_id.','.$city_id.','.$district_id.',d';
                }
                $member = new agentService();
                $res= $member->addagentInfo($website_id,$uid,$area_id,$agent_area_id,$post_data,$real_name);
                if($res){
                    $data['code'] = 0;
                    $data['message'] = "申请成功";
                }else{
                    $data['code'] = -1;
                    $data['message'] = "申请失败";
                }
                return json($data);
            }

            /**
             * 前台查询申请区域代理状态接口
             */
            public function areaAgentStatus(){
                $uid = $this->uid;
                $member = new agentService();
                $info= $member->getagentStatus($uid);
                return json($info);
            }

            /**
             * 获取省列表
             */
            public function getProvince()
            {
                $address = new Address();
                $province_list = $address->getProvinceList();
                return $province_list;
            }

            /**
             * 获取城市列表
             */
            public function getCity()
            {
                $address = new Address();
                $province_id = request()->post('province_id', 0);
                $city_list = $address->getCityList($province_id);
                return $city_list;
            }

            /**
             * 获取区地址
             */
            public function getDistrict()
            {
                $address = new Address();
                $city_id = request()->post('city_id', 0);
                $district_list = $address->getDistrictList($city_id);
                return $district_list;
            }
            /**
             * 前台区域分红明细
             */
            public function areaBonusDetail(){
                $uid = $this->uid;
                $page_index = request()->post('page', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition = array();
                $condition['nmar.uid'] = $uid;
                $condition['nmar.bonus_type'] = 2;
                $condition['nmar.website_id'] = $this->website_id;
                $bonus = new agentService();
                $list = $bonus->getBonusRecords($page_index, $page_size,$condition,'');
                return json($list);
            }

}
