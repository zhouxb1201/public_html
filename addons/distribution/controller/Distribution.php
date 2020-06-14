<?php
namespace addons\distribution\controller;
use addons\distribution\Distribution as baseDistribution;
use addons\distribution\model\SysMessageItemModel;
use addons\distribution\model\SysMessagePushModel;
use addons\distribution\model\VslDistributorCommissionWithdrawModel;
use addons\distribution\service\Distributor as DistributorService;
use data\model\VslMemberModel;
use data\service\Member;
use think\helper\Time;
use data\model\UserModel;
use data\service\Address;
use addons\distribution\model\VslDistributorLevelModel;
use addons\distribution\model\VslOrderDistributorCommissionModel;
use data\model\VslAccountRecordsModel;
use data\model\VslMemberAccountRecordsModel;
use think\Db;
use addons\teambonus\service\TeamBonus;
use addons\globalbonus\service\GlobalBonus;
use addons\areabonus\service\AreaBonus;
use addons\bonus\model\VslAgentLevelModel;
use data\model\VslOrderModel;
use data\model\VslOrderGoodsModel;
use data\model\VslGoodsModel;
use data\model\ProvinceModel;
use data\model\CityModel;
use data\model\DistrictModel;
use addons\bonus\model\VslOrderBonusModel;
        /**
         * 分销商设置控制器
         *
         * @author  www.vslai.com
         *
         */
        class Distribution extends baseDistribution
        {
            public function __construct(){
                parent::__construct();
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
        public function test()
        {
            $DistributorService = new DistributorService();
            $orderid = 65113;
            $res = $DistributorService->addCommissionDistributionPresell($orderid);
            var_dump($res);
        }
        /**
         * 分销商列表
         */
        public function distributorList(){
            $index = request()->post('page_index',1);
            $iphone = request()->post('iphone',"");
            $search_text = request()->post('search_text','');
            $referee_name = request()->post('referee_name','');
            $distributor_level_id = request()->post('level_id','');
            $isdistributor = request()->post('isdistributor','5');
            if( $search_text){
                $condition['us.user_name|us.nick_name'] = array('like','%'.$search_text.'%');
            }
            if( $referee_name){
                //推荐人姓名换取推荐人uid
                $member = new UserModel();
                $referee_info = $member->getInfo(['user_name|nick_name'=>['like','%'.$referee_name.'%'],'website_id'=>$this->website_id],'*');
                if($referee_info){
                    $condition['nm.referee_id'] = $referee_info['uid'];
                }
                // $condition['us.user_name|us.nick_name'] = array('like','%'.$referee_name.'%');
            }
            if($iphone ){
                $condition['nm.mobile'] = $iphone;
            }
            if($isdistributor!=5){
                $condition['nm.isdistributor'] = $isdistributor;
            }else{
                $condition['nm.isdistributor'] = ['in','1,2,-1,-3'];
            }
            if($distributor_level_id){
                $condition['nm.distributor_level_id'] = $distributor_level_id;
            }
            $condition['nm.website_id'] = $this->website_id;
            $distributor = new DistributorService();
            $uid = 0;
            $list = $distributor->getDistributorList($uid,$index, PAGESIZE, $condition,'become_distributor_time desc');
            return $list;
        }

        /**
         * 修改上级分销商
         */
        public function test1(){
            $agent_logs = new VslOrderBonusModel();
            $res = $agent_logs->save(['cal_status' => 1], ['order_id'=>65756,'uid'=>2259,'from_type'=>3]);
           
            $distributor=new DistributorService();
            $retval=$distributor->updateDistributorLevelInfo(2259);
        }
        public function updateRefereeDistributor(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $distributor=new DistributorService();
            $uid=isset($_POST['uid'])?$_POST['uid']:'';;
            $referee_id=isset($_POST['referee_id'])?$_POST['referee_id']:'';
            $retval=$distributor->updateRefereeDistributor($uid,$referee_id);
            if($retval){
                $this->addUserLog('修改上级分销商', $referee_id);
            }
            return AjaxReturn($retval);
        }
            /**
             * 修改直属下级分销商
             */
            public function updateLowerRefereeDistributor(){
                $distributor=new DistributorService();
                $uid=isset($_POST['uid'])?$_POST['uid']:'';
                $referee_id=isset($_POST['referee_id'])?$_POST['referee_id']:'';
                $retval=$distributor->updateLowerRefereeDistributor($uid,$referee_id);
                if($retval){
                    $this->addUserLog('修改直属下级分销商', $referee_id);
                }
                return AjaxReturn($retval);
            }
            /**
             * 修改分销商上级列表
             */
            public function refereeDistributorLists(){
                $distributor = new DistributorService();
                $condition['nm.isdistributor'] = 2;
                $uid = request()->post('uid', "");
                $search_text = request()->post('search_text','');
                $page_index = request()->post('page_index', 1);
                $lower_id = request()->post('lower_id', '');
                if($lower_id){
                    $condition['us.uid'] =['not in',$lower_id];
                }
                if( $search_text){
                    $condition['us.user_name|us.nick_name'] = array('like','%'.$search_text.'%');
                }
                $condition['us.website_id'] =$this->website_id;
                $list = $distributor->getDistributorList($uid,$page_index, PAGESIZE, $condition,'become_distributor_time desc');
                return $list;
            }
        /**
         * 修改分销商上级列表
         */
        public function refereeDistributorList(){
            $distributor = new DistributorService();
            $condition['nm.isdistributor'] = 2;
            $uid = request()->post('uid', "");
            $search_text = request()->post('search_text','');
            $page_index = request()->post('page_index', 1);
            $lower_id = request()->post('lower_id', '');
            if($lower_id){
                $condition['us.uid'] =['not in',$lower_id];
            }
            if( $search_text){
                $condition['us.user_name|us.nick_name'] = array('like','%'.$search_text.'%');
            }
            $condition['us.website_id'] =$this->website_id;
            $list = $distributor->getDistributorList($uid,$page_index, PAGESIZE, $condition,'become_distributor_time desc');
            return $list;
        }

        /**
         * 后台分销订单
         */
        public function distributorOrderList(){
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
            // $condition['shop_id'] = 0;//移除，分销商查看订单，可以查看自营以及入驻店订单
            if (request()->post('order_amount')){
                $condition['order_amount'] = true;
            }
            if (request()->post('order_memo')){
                $condition['order_memo'] = true;
            }
            $condition['buyer_id'] =$uid;
            $order_service = new DistributorService();
            $list = $order_service->getOrderList($page_index, $page_size, $condition, 'create_time desc');
            return $list;
        }

        /**
         * 下级分销商列表
         */
        public function lowerDistributorList(){
            $uid = request()->post('uid',"");
            $index = request()->post('page_index',1);
            $iphone = request()->post('iphone',"");
            $search_text = request()->post('search_text','');
            $distributor_level_id = request()->post('level_id','');
            $isdistributor = request()->post('isdistributor','');
            //获取客户列表
            $types = request()->post('types',1);
            $iphone2 = request()->post('iphone2',"");
            $search_text2 = request()->post('search_text2','');
            if($types == 2){ //客户列表
                if( $search_text2){ 
                    $condition['us.user_name|us.nick_name'] = array('like','%'.$search_text2.'%');
                }
                if($iphone2 ){
                    $condition['nm.mobile'] = $iphone2;
                }
                if($uid){
                    $condition['nm.uid'] = ['neq',$uid];
                }
                $condition['nm.isdistributor'] = ['neq',2];
                $condition['nm.website_id'] = $this->website_id;
                $distributor = new DistributorService();
                $list = $distributor->getDistributorList2($uid,$index, PAGESIZE, $condition,'nm.reg_time desc');
            }else{
                if( $search_text){
                    $condition['us.user_name|us.nick_name'] = array('like','%'.$search_text.'%');
                }
                if($iphone ){
                    $condition['nm.mobile'] = $iphone;
                }
                if($isdistributor){
                    $condition['nm.isdistributor'] = $isdistributor;
                }else{
                    $condition['nm.isdistributor'] = 2;
                }
                if($distributor_level_id){
                    $condition['nm.distributor_level_id'] = $distributor_level_id;
                }
                if($uid){
                    $condition['nm.uid'] = ['neq',$uid];
                }
                $condition['nm.website_id'] = $this->website_id;
                $distributor = new DistributorService();
                $list = $distributor->getDistributorList($uid,$index, PAGESIZE, $condition,'become_distributor_time desc');
            }
            return $list;
        }

        /**
         * 修改分销商状态
         */
        public function setStatus(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $uid = request()->post('uid','');
            $status = request()->post('status','');
            $distributor = new DistributorService();
            $retval = $distributor->setStatus($uid, $status);
            if($retval){
                $this->addUserLog('修改分销商状态', $uid);
            }
            return AjaxReturn($retval);
        }
            /**
             * 检查当前分销商是否有下级
             */
            public function checkDistributor(){
                $uid = request()->post('uid','');
                $distributor = new DistributorService();
                $retval = $distributor->checkDistributor($uid);
                return AjaxReturn($retval);
            }
        /**
         * 移除分销商
         */
        public function delDistributor(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $member = new DistributorService();
            $uid = request()->post("uid", '');
            $res = $member->deleteDistributor($uid);
            if($res){
                $this->addUserLog('移除分销商', $uid);
            }
            return AjaxReturn($res);
        }

        /**
         * 分销商详情
         */
        public function updateDistributorInfo(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $member = new DistributorService();
            $uid = request()->post("uid", '');
            $distributor_level_id = request()->post("level", '');
            $team_agent = request()->post("team_agent", '');
            $status = request()->post("status", '');
            $real_name = request()->post("real_name", '');
            $area_agent = request()->post("area_agent", '');
            $global_agent = request()->post("global_agent", '');
            $province_id = request()->post("province_id", '');
            $city_id = request()->post("city_id", '');
            $district_id = request()->post("district_id", '');
            $area_id = request()->post("area_id", '');
            $data=[];
            if($area_id==1){
                $agent_area_id =  $province_id.',p';
            }
            if($area_id==2){
                $agent_area_id =  $province_id.','.$city_id.',c';
            }
            if($area_id==3){
                $agent_area_id =  $province_id.','.$city_id.','.$district_id.',d';
            }
            if($team_agent==-1){
                $data['is_team_agent'] = 0;
            }
            if($global_agent==-1){
                $data['is_global_agent'] = 0;
            }
            if($area_agent==-1){
                $data['is_area_agent'] = 0;
            }
            if($global_agent>0){
                $data['is_global_agent'] = 2;
                $data['global_agent_level_id'] = $global_agent;
                $data['become_global_agent_time'] = time();
                $data['apply_global_agent_time'] = time();
            }
            if($area_agent>0){
                $data['is_area_agent'] = 2;
                $data['area_agent_level_id'] = $area_agent;
                $data['become_area_agent_time'] = time();
                $data['apply_area_agent_time'] = time();
            }
            if($team_agent>0){
                $data['is_team_agent'] = 2;
                $data['team_agent_level_id'] = $team_agent;
                $data['become_team_agent_time'] = time();
                $data['apply_team_agent_time'] = time();
            }
            if($distributor_level_id){
                $data['distributor_level_id'] =$distributor_level_id;
            }
            if($status){
                $data['isdistributor'] = $status;
            }
            if($area_id){
                $data['area_type'] = $area_id;
            }
            if($agent_area_id){
                $data['agent_area_id'] = $agent_area_id;
            }
            if($real_name){
                $data['real_name'] = $real_name;
            }
            $res= $member->updateDistributorInfo($data,$uid);
            if($res){
                $this->addUserLog('修改分销商详情', $res);
            }
            return AjaxReturn($res);
        }

        /**
         * 分销商等级列表
         */
        public function distributorLevelList(){
            $index = isset($_POST["page_index"]) ? $_POST["page_index"] : 1;
            $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
            $distributor = new DistributorService();
            $list =  $distributor->getDistributorLevelList($index, PAGESIZE, ['level_name' => array('like','%'.$search_text.'%'),'website_id'=>$this->website_id],'weight asc');
            return json($list);
        }

        /**
         * 添加分销商等级
         */
        public function addDistributorLevel(){
            $level_name = isset($_POST['level_name'])?$_POST['level_name']:'';//等级名称
            $recommend_type= isset($_POST['recommend_type'])?$_POST['recommend_type']:'1';//返佣类型
            $commission1 = isset($_POST['commission1'])?$_POST['commission1']:'0';//一级返佣比例
            $commission2 = isset($_POST['commission2'])?$_POST['commission2']:'0';//二级返佣比例
            $commission3 = isset($_POST['commission3'])?$_POST['commission3']:'0';//三级返佣比例
            $commission_point1 = isset($_POST['commission_point1'])?$_POST['commission_point1']:'0';//一级返佣积分比例
            $commission_point2 = isset($_POST['commission_point2'])?$_POST['commission_point2']:'0';//二级返佣积分比例
            $commission_point3 = isset($_POST['commission_point3'])?$_POST['commission_point3']:'0';//三级返佣积分比例
            $commission11 = isset($_POST['commission11'])?$_POST['commission11']:'0';//一级返佣
            $commission22 = isset($_POST['commission22'])?$_POST['commission22']:'0';//二级返佣
            $commission33 = isset($_POST['commission33'])?$_POST['commission33']:'0';//三级返佣
            $commission_point11 = isset($_POST['commission_point11'])?$_POST['commission_point11']:'0';//一级返佣积分
            $commission_point22 = isset($_POST['commission_point22'])?$_POST['commission_point22']:'0';//二级返佣积分
            $commission_point33 = isset($_POST['commission_point33'])?$_POST['commission_point33']:'0';//三级返佣积分
            $recommend1 = isset($_POST['recommend1'])?$_POST['recommend1']:'0';//一级奖励金
            $recommend2 = isset($_POST['recommend2'])?$_POST['recommend2']:'0';//二级奖励金
            $recommend3 = isset($_POST['recommend3'])?$_POST['recommend3']:'0';//三级奖励金
            $recommend_point1 = isset($_POST['recommend_point1'])?$_POST['recommend_point1']:'0';//一级奖励积分
            $recommend_point2 = isset($_POST['recommend_point2'])?$_POST['recommend_point2']:'0';//二级奖励积分
            $recommend_point3 = isset($_POST['recommend_point3'])?$_POST['recommend_point3']:'0';//三级奖励积分
            $upgradetype = isset($_POST['upgradetype'])?$_POST['upgradetype']:2;//自动升级
            $offline_number = isset($_POST['offline_number'])?$_POST['offline_number']:'0';//满足下线总人数
            $order_money = isset($_POST['order_money'])?$_POST['order_money']:'0';//满足分销订单金额
            $order_number = isset($_POST['order_number'])?$_POST['order_number']:'0';//满足分销订单数
            $selforder_money = isset($_POST['selforder_money'])?$_POST['selforder_money']:'0';//自购订单金额
            $selforder_number = isset($_POST['selforder_number'])?$_POST['selforder_number']:'0';//自购订单数
            $downgradetype = isset($_POST['downgradetype'])?$_POST['downgradetype']:2;//自动降级
            $team_number = isset($_POST['team_number'])?$_POST['team_number']:'0';//团队人数
            $team_money = isset($_POST['team_money'])?$_POST['team_money']:'0';//团队订单金额
            $self_money = isset($_POST['self_money'])?$_POST['self_money']:'0';//自购订单金额
            $team_number_day = isset($_POST['team_number_day'])?$_POST['team_number_day']:'0';//时间段：团队人数
            $team_money_day = isset($_POST['team_money_day'])?$_POST['team_money_day']:'0';//时间段：团队订单金额
            $self_money_day = isset($_POST['self_money_day'])?$_POST['self_money_day']:'0';//时间段：自购订单金额
            $weight = isset($_POST['weight'])?$_POST['weight']:'';//权重
            $downgrade_condition = isset($_POST['downgrade_condition'])?$_POST['downgrade_condition']:'';//升级条件
            $upgrade_condition = isset($_POST['upgrade_condition'])?$_POST['upgrade_condition']:'';//降级条件
            $downgradeconditions = isset($_POST['downgradeconditions'])?$_POST['downgradeconditions']:'';//升级条件
            $upgradeconditions = isset($_POST['upgradeconditions'])?$_POST['upgradeconditions']:'';//降级条件
            $goods_id = isset($_POST['goods_id'])?$_POST['goods_id']:'';//指定商品id
            $upgrade_level = isset($_POST['upgrade_level'])?$_POST['upgrade_level']:'0';//推荐等级
            $level_number = isset($_POST['level_number'])?$_POST['level_number']:'0';//推荐等级人数
            $number1 = isset($_POST['number1'])?$_POST['number1']:'0';//一级分销商
            $number2 = isset($_POST['number2'])?$_POST['number2']:'0';//二级分销商
            $number3 = isset($_POST['number3'])?$_POST['number3']:'0';//三级分销商
            $number4 = isset($_POST['number4'])?$_POST['number4']:'0';//团队人数
            $number5 = isset($_POST['number5'])?$_POST['number5']:'0';//下线客户
            //复购设置
            $buyagain = isset($_POST['buyagain'])?$_POST['buyagain']:0;//是否开启复购
            $buyagain_recommendtype = isset($_POST['buyagain_recommendtype'])?$_POST['buyagain_recommendtype']:1;//复购类型
            $buyagain_commission1 = isset($_POST['buyagain_commission1'])?$_POST['buyagain_commission1']:'0';//一级返佣比例
            $buyagain_commission2 = isset($_POST['buyagain_commission2'])?$_POST['buyagain_commission2']:'0';//二级返佣比例
            $buyagain_commission3 = isset($_POST['buyagain_commission3'])?$_POST['buyagain_commission3']:'0';//三级返佣比例
            $buyagain_commission_point1 = isset($_POST['buyagain_commission_point1'])?$_POST['buyagain_commission_point1']:'0';//一级返佣积分比例
            $buyagain_commission_point2 = isset($_POST['buyagain_commission_point2'])?$_POST['buyagain_commission_point2']:'0';//二级返佣积分比例
            $buyagain_commission_point3 = isset($_POST['buyagain_commission_point3'])?$_POST['buyagain_commission_point3']:'0';//三级返佣积分比例
            $buyagain_commission11 = isset($_POST['buyagain_commission11'])?$_POST['buyagain_commission11']:'0';//一级返佣
            $buyagain_commission22 = isset($_POST['buyagain_commission22'])?$_POST['buyagain_commission22']:'0';//二级返佣
            $buyagain_commission33 = isset($_POST['buyagain_commission33'])?$_POST['buyagain_commission33']:'0';//三级返佣
            $buyagain_commission_point11 = isset($_POST['buyagain_commission_point11'])?$_POST['buyagain_commission_point11']:'0';//一级返佣积分
            $buyagain_commission_point22 = isset($_POST['buyagain_commission_point22'])?$_POST['buyagain_commission_point22']:'0';//二级返佣积分
            $buyagain_commission_point33 = isset($_POST['buyagain_commission_point33'])?$_POST['buyagain_commission_point33']:'0';//三级返佣积分
           
            $distributor=new DistributorService();
            $retval=$distributor->addDistributorLevel($level_name,$recommend_type, $commission1, $commission2, $commission3,$commission_point1,$commission_point2,$commission_point3, $commission11, $commission22, $commission33,$commission_point11,$commission_point22,$commission_point33,$recommend1,$recommend2,$recommend3,$recommend_point1,$recommend_point2,$recommend_point3,$upgradetype,$offline_number,$order_money,$order_number,$selforder_money,$selforder_number,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$number1,$number2,$number3,$number4,$number5,  $buyagain,$buyagain_recommendtype,$buyagain_commission1,$buyagain_commission2,$buyagain_commission3,$buyagain_commission_point1,$buyagain_commission_point2,$buyagain_commission_point3,$buyagain_commission11,$buyagain_commission22,$buyagain_commission33,$buyagain_commission_point11,$buyagain_commission_point22,$buyagain_commission_point33);
            if($retval){
                $this->addUserLog('添加分销商等级', $retval);
            }
            return AjaxReturn($retval);
        }
        /**
         * 修改分销商等级
         */
        public function updateDistributorLevel(){
            $distributor=new DistributorService();
            $id = isset($_POST['id'])?$_POST['id']:'';//等级id
            $level_name = isset($_POST['level_name'])?$_POST['level_name']:'';//等级名称
            $recommend_type= isset($_POST['recommend_type'])?$_POST['recommend_type']:'1';//返佣类型
            $commission1 = isset($_POST['commission1'])?$_POST['commission1']:'0';//一级返佣比例
            $commission2 = isset($_POST['commission2'])?$_POST['commission2']:'0';//二级返佣比例
            $commission3 = isset($_POST['commission3'])?$_POST['commission3']:'0';//三级返佣比例
            $commission_point1 = isset($_POST['commission_point1'])?$_POST['commission_point1']:'0';//一级返佣积分比例
            $commission_point2 = isset($_POST['commission_point2'])?$_POST['commission_point2']:'0';//二级返佣积分比例
            $commission_point3 = isset($_POST['commission_point3'])?$_POST['commission_point3']:'0';//三级返佣积分比例
            $commission11 = isset($_POST['commission11'])?$_POST['commission11']:'0';//一级返佣
            $commission22 = isset($_POST['commission22'])?$_POST['commission22']:'0';//二级返佣
            $commission33 = isset($_POST['commission33'])?$_POST['commission33']:'0';//三级返佣
            $commission_point11 = isset($_POST['commission_point11'])?$_POST['commission_point11']:'0';//一级返佣积分
            $commission_point22 = isset($_POST['commission_point22'])?$_POST['commission_point22']:'0';//二级返佣积分
            $commission_point33 = isset($_POST['commission_point33'])?$_POST['commission_point33']:'0';//三级返佣积分
            $recommend1 = isset($_POST['recommend1'])?$_POST['recommend1']:'0';//一级奖励金
            $recommend2 = isset($_POST['recommend2'])?$_POST['recommend2']:'0';//二级奖励金
            $recommend3 = isset($_POST['recommend3'])?$_POST['recommend3']:'0';//三级奖励金
            $recommend_point1 = isset($_POST['recommend_point1'])?$_POST['recommend_point1']:'0';//一级奖励积分
            $recommend_point2 = isset($_POST['recommend_point2'])?$_POST['recommend_point2']:'0';//二级奖励积分
            $recommend_point3 = isset($_POST['recommend_point3'])?$_POST['recommend_point3']:'0';//三级奖励积分
            $upgradetype = isset($_POST['upgradetype'])?$_POST['upgradetype']:2;//自动升级
            $offline_number = isset($_POST['offline_number'])?$_POST['offline_number']:'0';//满足下线总人数
            $order_money = isset($_POST['order_money'])?$_POST['order_money']:'0';//满足分销订单金额
            $order_number = isset($_POST['order_number'])?$_POST['order_number']:'0';//满足分销订单数
            $selforder_money = isset($_POST['selforder_money'])?$_POST['selforder_money']:'0';//自购订单金额
            $selforder_number = isset($_POST['selforder_number'])?$_POST['selforder_number']:'0';//自购订单数
            $downgradetype = isset($_POST['downgradetype'])?$_POST['downgradetype']:2;//自动降级
            $team_number = isset($_POST['team_number'])?$_POST['team_number']:'0';//团队人数
            $team_money = isset($_POST['team_money'])?$_POST['team_money']:'0';//团队订单金额
            $self_money = isset($_POST['self_money'])?$_POST['self_money']:'0';//自购订单金额
            $team_number_day = isset($_POST['team_number_day'])?$_POST['team_number_day']:'0';//时间段：团队人数
            $team_money_day = isset($_POST['team_money_day'])?$_POST['team_money_day']:'0';//时间段：团队订单金额
            $self_money_day = isset($_POST['self_money_day'])?$_POST['self_money_day']:'0';//时间段：自购订单金额
            $weight = isset($_POST['weight'])?$_POST['weight']:'';//权重
            $downgrade_condition = isset($_POST['downgrade_condition'])?$_POST['downgrade_condition']:'';//升级条件
            $upgrade_condition = isset($_POST['upgrade_condition'])?$_POST['upgrade_condition']:'';//降级条件
            $downgradeconditions = isset($_POST['downgradeconditions'])?$_POST['downgradeconditions']:'';//升级条件
            $upgradeconditions = isset($_POST['upgradeconditions'])?$_POST['upgradeconditions']:'';//降级条件
            $goods_id = isset($_POST['goods_id'])?$_POST['goods_id']:'';//指定商品id
            $upgrade_level = isset($_POST['upgrade_level'])?$_POST['upgrade_level']:'0';//推荐等级
            $level_number = isset($_POST['level_number'])?$_POST['level_number']:'0';//推荐等级人数
            $number1 = isset($_POST['number1'])?$_POST['number1']:'0';//一级分销商
            $number2 = isset($_POST['number2'])?$_POST['number2']:'0';//二级分销商
            $number3 = isset($_POST['number3'])?$_POST['number3']:'0';//三级分销商
            $number4 = isset($_POST['number4'])?$_POST['number4']:'0';//团队人数
            $number5 = isset($_POST['number5'])?$_POST['number5']:'0';//下线客户
            //复购设置
            $buyagain = isset($_POST['buyagain'])?$_POST['buyagain']:0;//是否开启复购
            $buyagain_recommendtype = isset($_POST['buyagain_recommendtype'])?$_POST['buyagain_recommendtype']:1;//复购类型
            $buyagain_commission1 = isset($_POST['buyagain_commission1'])?$_POST['buyagain_commission1']:'0';//一级返佣比例
            $buyagain_commission2 = isset($_POST['buyagain_commission2'])?$_POST['buyagain_commission2']:'0';//二级返佣比例
            $buyagain_commission3 = isset($_POST['buyagain_commission3'])?$_POST['buyagain_commission3']:'0';//三级返佣比例
            $buyagain_commission_point1 = isset($_POST['buyagain_commission_point1'])?$_POST['buyagain_commission_point1']:'0';//一级返佣积分比例
            $buyagain_commission_point2 = isset($_POST['buyagain_commission_point2'])?$_POST['buyagain_commission_point2']:'0';//二级返佣积分比例
            $buyagain_commission_point3 = isset($_POST['buyagain_commission_point3'])?$_POST['buyagain_commission_point3']:'0';//三级返佣积分比例
            $buyagain_commission11 = isset($_POST['buyagain_commission11'])?$_POST['buyagain_commission11']:'0';//一级返佣
            $buyagain_commission22 = isset($_POST['buyagain_commission22'])?$_POST['buyagain_commission22']:'0';//二级返佣
            $buyagain_commission33 = isset($_POST['buyagain_commission33'])?$_POST['buyagain_commission33']:'0';//三级返佣
            $buyagain_commission_point11 = isset($_POST['buyagain_commission_point11'])?$_POST['buyagain_commission_point11']:'0';//一级返佣积分
            $buyagain_commission_point22 = isset($_POST['buyagain_commission_point22'])?$_POST['buyagain_commission_point22']:'0';//二级返佣积分
            $buyagain_commission_point33 = isset($_POST['buyagain_commission_point33'])?$_POST['buyagain_commission_point33']:'0';//三级返佣积分

            $retval=$distributor->updateDistributorLevel($id,$level_name,$recommend_type, $commission1, $commission2, $commission3,$commission_point1,$commission_point2,$commission_point3, $commission11, $commission22, $commission33,$commission_point11,$commission_point22,$commission_point33,$recommend1,$recommend2,$recommend3,$recommend_point1,$recommend_point2,$recommend_point3,$upgradetype,$offline_number,$order_money,$order_number,$selforder_money,$selforder_number,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$number1,$number2,$number3,$number4,$number5,  $buyagain,$buyagain_recommendtype,$buyagain_commission1,$buyagain_commission2,$buyagain_commission3,$buyagain_commission_point1,$buyagain_commission_point2,$buyagain_commission_point3,$buyagain_commission11,$buyagain_commission22,$buyagain_commission33,$buyagain_commission_point11,$buyagain_commission_point22,$buyagain_commission_point33);
            if($retval){
                $this->addUserLog('修改分销商等级', $id);
            }
            return AjaxReturn($retval);
        }
        /**
         * 删除 分销商等级
         */
        public function deleteDistributorLevel()
        {
            $distributor = new DistributorService();
            $id = request()->post("id", "");
            $res = $distributor->deleteDistributorLevel($id);
            if($res){
                $this->addUserLog('删除 分销商等级', $id);
            }
            return AjaxReturn($res);
        }
        /**
         * 分销订单概况
         */
        public function distributionOrderProfile()
            {
                $website_id = isset($_POST['website_id'])?$_POST['website_id']:$this->website_id;
                $order_distributor = new DistributorService();
                list($start, $end) = Time::dayToNow(6,true);
                $orderType = ['订单金额','订单佣金'];
                $data = array();
                $data['ordertype'] = $orderType;
                for($i=0;$i<count($orderType);$i++){
                    switch ($orderType[$i]) {
                        case '订单金额':
                            $status = 1;
                            break;
                        case '订单佣金':
                            $status = 2;
                            break;
                    }
                    for($j=0;$j<($end+1-$start)/86400;$j++){
                        $data['day'][$j]= date("Y-m-d",$start+86400*$j);
                        $date_start =  strtotime(date("Y-m-d H:i:s",$start+86400*$j));
                        $date_end =  strtotime(date("Y-m-d H:i:s",$start+86400*($j+1)));
                        if($status ==1){
                            $count = $order_distributor->getOrderMoneySum(['order_status'=>['between',[1,4]],'website_id'=>$website_id,'create_time'=>['between',[$date_start,$date_end]]]);
                        }
                        if($status == 2){
                            $count = $order_distributor->getPayMoneySum(['order_status'=>['between',[1,4]],'website_id'=>$website_id,'create_time'=>['between',[$date_start,$date_end]]]);
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
          * 分销概况
          */
        public function distributionProfile()
            {
                $website_id = isset($_POST['website_id'])?$_POST['website_id']:$this->website_id;
                $agent_level = new VslDistributorLevelModel();
                $level_info = $agent_level->getInfo(['website_id' => $website_id,'is_default'=>1],'*');
                    if($level_info){
                    }else{
                        $data = array(
                            'level_name' => '默认分销等级',
                            'is_default'=>1,
                            'commission1'=>0,
                            'commission2'=>0,
                            'commission3'=>0,
                            'weight' => 1,
                            'create_time' => time(),
                            'website_id' => $website_id
                        );
                        $agent_level->save($data);
                    }
                $distributor = new DistributorService();
                $data = $distributor->getDistributorCount($website_id);
                return $data;
            }

        /**
         * 基本设置
         */
        public function basicSetting()
        {
            $config= new DistributorService();
            if (request()->isPost()) {
                // 基本设置
                $distribution_status = request()->post('distribution_status', ''); // 是否开启分销
                $distribution_admin_status = request()->post('distribution_admin_status', ''); // 是否开启店铺分销
                $distribution_pattern = request()->post('distribution_pattern', ''); // 分销模式
                $purchase_type = request()->post('purchase_type', ''); // 分销内购是否开启
                $distributor_condition = request()->post('distributor_condition', ''); // 成为分销商的条件
                $distributor_conditions = request()->post('distributor_conditions', ''); // 条件选择
                $pay_money  = request()->post('pay_money', ''); // 消费金额额度
                $order_number = request()->post('order_number', ''); // 订单数
                $distributor_check = request()->post('distributor_check', ''); // 是否开启自动审核
                $distributor_grade = request()->post('distributor_grade', ''); // 是否开启跳级降级设置
                $goods_id = request()->post('goods_id', ''); // 指定商品
                $lower_condition = request()->post('lower_condition', ''); // 成为下线条件
                $distributor_datum = request()->post('distributor_datum', ''); // 分销必须完整资料
                $retval = $config->setDistributionSite($distribution_status,$distribution_pattern, $purchase_type,$distributor_condition, $distributor_conditions, $pay_money, $order_number, $distributor_check, $distributor_grade, $goods_id,$lower_condition,$distributor_datum,$distribution_admin_status);
                if($retval){
                    $this->addUserLog('保存分销商基本设置', $retval);
                }
                setAddons('distribution', $this->website_id, $this->instance_id);
                setAddons('distribution', $this->website_id, $this->instance_id, true);
                return AjaxReturn($retval);
            }
        }

        /**
         * 结算设置
         */
        public function settlementSetting()
        {
            $config= new DistributorService();
            if (request()->isPost()) {
                // 结算设置
                $withdrawals_type = request()->post('withdrawals_type', ''); // 提现方式
                $make_money = request()->post('make_money', ''); // 打款方式
                $commission_calculation = request()->post('commission_calculation', ''); // 佣金计算节点
                $commission_arrival = request()->post('commission_arrival', ''); // 佣金到账节点
                $withdrawals_check = request()->post('withdrawals_check', ''); // 佣金提现免审核
                $withdrawals_min = request()->post('withdrawals_min', ''); // 佣金最低提现金额
                $withdrawals_cash  = request()->post('withdrawals_cash', ''); // 佣金免审核提现金额
                $withdrawals_begin = request()->post('withdrawals_begin', ''); // 佣金提现免手续费区间
                $withdrawals_end = request()->post('withdrawals_end', '');//佣金提现免手续费区间
                $poundage = request()->post('poundage', ''); // 佣金提现手续费
                $retval = $config->setSettlementSite($withdrawals_type,$make_money, $commission_calculation, $commission_arrival,$withdrawals_check, $withdrawals_min , $withdrawals_cash, $withdrawals_begin, $withdrawals_end, $poundage);
                if($retval){
                    $this->addUserLog('分销商结算设置', $retval);
                }
                return AjaxReturn($retval);
            }
        }
        /**
         * 申请协议
         */
        public function applicationAgreement()
        {
            $config= new DistributorService();
            if (request()->isPost()) {
                // 基本设置
                $type = request()->post('type', 2);
                $logo = request()->post('image', '');
                $content = htmlspecialchars(stripslashes($_POST['content']));
                $distribution_label = request()->post('distribution_label', ''); // 分销标识
                $distribution_name = request()->post('distribution_name', ''); // 分销中心
                $distributor_name = request()->post('distributor_name', ''); // 分销商名称
                $distribution_commission = request()->post('distribution_commission', ''); //分销佣金
                $commission = request()->post('commission', ''); // 累积佣金
                $total_commission = request()->post('total_commission', ''); // 累积佣金
                $commission_details = request()->post('commission_details', ''); // 佣金明细
                $withdrawable_commission = request()->post('withdrawable_commission', ''); // 可提现佣金
                $withdrawals_commission = request()->post('withdrawals_commission', ''); //已提现佣金
                $withdrawal = request()->post('withdrawal', ''); // 提现中
                $frozen_commission = request()->post('frozen_commission', ''); // 冻结佣金
                $distribution_order = request()->post('distribution_order', ''); // 分销订单
                $my_team = request()->post('my_team', ''); // 我的团队
                $team1 = request()->post('team1', ''); // 一级团队
                $team2 = request()->post('team2', ''); // 二级团队
                $team3 = request()->post('team3', ''); // 三级团队
                $my_customer = request()->post('my_customer', ''); // 我的客户
                $extension_code = request()->post('extension_code', ''); // 推广码
                $distribution_tips = request()->post('distribution_tips', ''); // 分销小提示
                $become_distributor = request()->post('become_distributor', ''); // 成为分销商
                
                if($content){
                    $content = htmlspecialchars_decode($content);
                }
                $retval = $config->setAgreementSite($type,$logo,$content,$distribution_label,$distribution_name,$distributor_name,$distribution_commission,$commission,$commission_details,$withdrawable_commission,$withdrawals_commission,$withdrawal,$frozen_commission,$distribution_order,$my_team,$team1,$team2,$team3,$my_customer,$extension_code,$distribution_tips,$become_distributor,$total_commission);
                if($retval){
                    $this->addUserLog('分销商申请协议', $retval);
                }
                return AjaxReturn($retval);
            }
        }
            /**
             * 佣金提现列表
             */
            public function commissionWithdrawList(){
                $page_index = request()->post("page_index",1);
                $withdraw_no = request()->post('withdraw_no','');
                $status = request()->post('status','');
                $website_id = request()->post('website_id',$this->website_id);
                $commission = new DistributorService();
                $condition=array('nmar.website_id'=>$website_id);
                $search_text = request()->post('search_text','');
                $condition['su.nick_name|su.user_tel|su.user_name|su.uid'] = [
                    'like',
                    '%' . $search_text . '%'
                ];
                if($status != '' && $status !=9){
                    $condition['nmar.status'] = $status;
                }
                if($withdraw_no != ''){
                    $condition['nmar.withdraw_no'] = $withdraw_no;
                }
                if(empty($_POST['start_date'])){
                    $start_date = strtotime('2018-1-1');
                }else{
                    $start_date = strtotime($_POST['start_date']);
                }
                if(empty($_POST['end_date'])){
                    $end_date = strtotime('2038-1-1');
                }else{
                    $end_date = strtotime($_POST['end_date']);
                }
                $condition["nmar.ask_for_date"] = [[">",$start_date],["<",$end_date]];
                $list = $commission->getCommissionWithdrawList($page_index,PAGESIZE,$condition, 'ask_for_date desc');
                return $list;
            }
            public function getWithdrawCount()
            {
                $order = new DistributorService();
                $order_count_array = array();
                $order_count_array['countall'] = $order->getMemberWithdrawalCount(['website_id' => $this->website_id]);
                $order_count_array['waitcheck'] = $order->getMemberWithdrawalCount(['status' => 1, 'website_id' => $this->website_id]);
                $order_count_array['waitmake'] = $order->getMemberWithdrawalCount(['status' => 2, 'website_id' => $this->website_id]);
                $order_count_array['make'] = $order->getMemberWithdrawalCount(['status' => 3, 'website_id' => $this->website_id]);
                $order_count_array['makefail'] = $order->getMemberWithdrawalCount(['status' => 5, 'website_id' => $this->website_id]);
                $order_count_array['nomake'] = $order->getMemberWithdrawalCount(['status' => 4, 'website_id' => $this->website_id]);
                $order_count_array['nocheck'] = $order->getMemberWithdrawalCount(['status' => -1, 'website_id' => $this->website_id]);
                return $order_count_array;
            }
            /**
             * 佣金提现列表导出
             */
            public function commissionWithdrawListDataExcel()
            {
                $xlsName = "佣金提现流水列表";
                $xlsCell = array(
                    array(
                        'withdraw_no',
                        '提现流水号'
                    ),
                    array(
                        'user_name',
                        '分销商名'
                    ),
                    array(
                        'type',
                        '提现类型'
                    ),
                    array(
                        'account_number',
                        '提现账号'
                    ),
                    array(
                        'cash',
                        '提现金额'
                    ),
                    array(
                        'tax',
                        '个人所得税'
                    ),
                    array(
                        'status',
                        '提现状态'
                    ),
                    array(
                        'ask_for_date',
                        '申请时间'
                    ),
                    array(
                        'payment_date',
                        '到账时间'
                    )
                );
                $withdraw_no = request()->get('withdraw_no','');
                $status = request()->get('status','');
                $website_id = request()->get('website_id',$this->website_id);
                $commission = new DistributorService();
                $condition=array('nmar.website_id'=>$website_id);
                $search_text = request()->get('search_text','');
                $condition['su.nick_name|su.user_tel|su.user_name'] = [
                    'like',
                    '%' . $search_text . '%'
                ];
                if($status != '' && $status!=9){
                    $condition['nmar.status'] = $status;
                }
                if($withdraw_no != ''){
                    $condition['nmar.withdraw_no'] = $withdraw_no;
                }
                if(empty($_POST['start_date'])){
                    $start_date = strtotime('2018-1-1');
                }else{
                    $start_date = strtotime($_POST['start_date']);
                }
                if(empty($_POST['end_date'])){
                    $end_date = strtotime('2038-1-1');
                }else{
                    $end_date = strtotime($_POST['end_date']);
                }
                $condition["nmar.ask_for_date"] = [[">",$start_date],["<",$end_date]];
                $list = $commission->getCommissionWithdrawList(1,0,$condition, 'ask_for_date desc');
                foreach ($list['data'] as $k=>$v){
                    if($v['type']==1 || $v['type']==5){
                        $v['type']= '银行卡';
                    }elseif ($v['type']==2){
                        $v['type']= '微信';
                    }elseif ($v['type']==3){
                        $v['type']= '支付宝';
                    }elseif ($v['type']==4){
                        $v['type']= '账户余额';
                        $v['account_number']= '账户余额';
                    }
                    if($v['status']==3){
                        $v['status']= '已打款';
                    }elseif ($v['status']==1){
                        $v['status']= '审核中';
                    }elseif ($v['status']==2){
                        $v['status']= '待打款';
                    }elseif ($v['status']==4){
                        $v['status']= '拒绝打款';
                    }elseif ($v['status']==-1){
                        $v['status']= '审核不通过';
                    }elseif ($v['status']==5){
                        $v['status']= '打款失败';
                    }
                    $v['cash'] = '¥'.$v['cash'];

                }
                dataExcel($xlsName, $xlsCell, $list['data']);
            }

            /**
             * 佣金提现详情
             */
            public function commissionWithdrawInfo(){
                $commission = new DistributorService();
                $id = $_GET['id'];
                $retval = $commission->commissionWithdrawDetail($id);
                return $retval;
            }
            /**
             * 佣金提现审核
             */
            public function commissionWithdrawAudit(){
                $commission = new DistributorService();
                $id = $_POST['id'];
                $status = $_POST['status'];
                $memo = $_POST['memo'];
                $ids = explode(',',$id);
                if(count($ids)>1){
                    foreach ($ids as $v) {
                        $retval = $commission->commissionWithdrawAudit($v, $status, $memo);
                    }
                }else{
                    $retval = $commission->commissionWithdrawAudit($id, $status,$memo);
                }
                if($retval==-9000){
                    $balance = new VslDistributorCommissionWithdrawModel();
                    $msg = $balance->getInfo(['id'=>$id],'memo')['memo'];
                }else if($retval>0){
                    $msg = '打款成功';
                }else{
                    $msg = '打款失败';
                }
                if($retval){
                    $this->addUserLog('佣金提现审核', $id);
                }
                return AjaxReturn($retval,$msg);
            }
            /**
             * 佣金流水
             */
            public function commissionRecordsList()
            {
                if (request()->isAjax()) {
                    $commission = new DistributorService();
                    $page_index = request()->post("page_index",1);
                    $page_size = request()->post('page_size', PAGESIZE);
                    $search_text = request()->post('search_text', '');
                    $records_no = request()->post('records_no','');
                    $from_type = request()->post('from_type','');
                    $start_date = request()->post('start_date') == "" ? '2010-1-1' : request()->post('start_date');
                    $end_date = request()->post('end_date') == "" ? '2038-1-1' : request()->post('end_date');
                    $condition['nmar.website_id'] = request()->post('website_id', $this->website_id);
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
                    if($from_type==4){
                        $condition['nmar.from_type'] = [['>',3],['<=',24]];
                    }elseif($from_type!=''){
                        $condition['nmar.from_type'] = $from_type;
                    }
                    if($records_no != ''){
                        $condition['nmar.records_no'] = $records_no;
                    }
                    $condition['nmar.commission'] = ['neq',0];
                    $list = $commission->getAccountList($page_index, $page_size, $condition, $order = '', $field = '*');
                    return $list;
                }
            }
            /**
             * 佣金流水详情
             */
            public function commissionInfo()
            {
                $commission = new DistributorService();
                $id = request()->get('id');
                $condition['nmar.id'] = $id;
                $list = $commission->getAccountList(1,0, $condition, $order = '', $field = '*');
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["commission"] = '¥'.$list['data'][$k]["commission"];
                    if( $list['data'][$k]["from_type"]==1){
                        $list['data'][$k]["from_type"] = '订单完成';
                    }
                    if( $list['data'][$k]["from_type"]==2){
                        $list['data'][$k]["from_type"] = '订单退款成功';
                    }
                    if( $list['data'][$k]["from_type"]==3){
                        $list['data'][$k]["from_type"] = '订单支付成功';
                    }
                    if( $list['data'][$k]["from_type"]==4){
                        $list['data'][$k]["from_type"] = '成功提现到账户余额';
                    }
                    if( $list['data'][$k]["from_type"]==5){
                        $list['data'][$k]["from_type"] = '提现到微信待打款';
                    }
                    if( $list['data'][$k]["from_type"]==6){
                        $list['data'][$k]["from_type"] = '提现到账户余额审核中';
                    }
                    if( $list['data'][$k]["from_type"]==7){
                        $list['data'][$k]["from_type"] = '提现到支付宝待打款';
                    }
                    if( $list['data'][$k]["from_type"]==8){
                        $list['data'][$k]["from_type"] = '提现到银行卡待打款';
                    }
                    if( $list['data'][$k]["from_type"]==9){
                        $list['data'][$k]["from_type"] = '成功提现到银行卡';
                    }
                    if( $list['data'][$k]["from_type"]==-9){
                        $list['data'][$k]["from_type"] = '提现到银行卡打款失败';
                    }
                    if( $list['data'][$k]["from_type"]==10){
                        $list['data'][$k]["from_type"] = '成功提现到微信';
                    }
                    if( $list['data'][$k]["from_type"]==-10){
                        $list['data'][$k]["from_type"] = '提现到微信打款失败';
                    }
                    if( $list['data'][$k]["from_type"]==11){
                        $list['data'][$k]["from_type"] = '成功提现到支付宝';
                    }
                    if( $list['data'][$k]["from_type"]==-11){
                        $list['data'][$k]["from_type"] = '提现到支付宝打款失败';
                    }
                    if( $list['data'][$k]["from_type"]==12){
                        $list['data'][$k]["from_type"] = '提现到银行卡，审核中';
                    }
                    if( $list['data'][$k]["from_type"]==13){
                        $list['data'][$k]["from_type"] = '提现到微信，审核中';
                    }
                    if( $list['data'][$k]["from_type"]==14){
                        $list['data'][$k]["from_type"] = '提现到支付宝，审核中';
                    }
                    if( $list['data'][$k]["from_type"]==15){
                        $list['data'][$k]["from_type"] = '提现到账户余额，待打款';
                    }
                    if( $list['data'][$k]["from_type"]==16){
                        $list['data'][$k]["from_type"] = '提现到微信，已拒绝';
                    }
                    if( $list['data'][$k]["from_type"]==17){
                        $list['data'][$k]["from_type"] = '提现到支付宝，已拒绝';
                    }
                    if( $list['data'][$k]["from_type"]==18){
                        $list['data'][$k]["from_type"] = '提现到账户余额，已拒绝';
                    }
                    if( $list['data'][$k]["from_type"]==19){
                        $list['data'][$k]["from_type"] = '提现到微信，审核不通过';
                    }
                    if( $list['data'][$k]["from_type"]==20){
                        $list['data'][$k]["from_type"] = '提现到支付宝，审核不通过';
                    }
                    if( $list['data'][$k]["from_type"]==21){
                        $list['data'][$k]["from_type"] = '提现到账户余额，不通过';
                    }
                    if( $list['data'][$k]["from_type"]==22){
                        $list['data'][$k]["from_type"] = '分销商等级升级获得推荐奖';
                    }
                    if( $list['data'][$k]["from_type"]==23){
                        $list['data'][$k]["from_type"] = '提现到银行卡，已拒绝';
                    }
                    if( $list['data'][$k]["from_type"]==24){
                        $list['data'][$k]["from_type"] = '提现到银行卡，审核不通过';
                    }
                }
                return $list['data'];
            }
            /**
             * 佣金流水数据excel导出
             */
            public function commissionRecordsDataExcel()
            {
                $xlsName = "佣金流水列表";
                $xlsCell = array(
                    array(
                        'records_no',
                        '流水号'
                    ),
                    array(
                        'data_id',
                        '订单或提现流水号'
                    ),
                    array(
                        'uid',
                        '用户编号'
                    ),
                    array(
                        'user_tel',
                        '用户名'
                    ),
                    array(
                        'from_type',
                        '类别'
                    ),
                    array(
                        'commission',
                        '金额'
                    ),
                    array(
                        'tax',
                        '个人所得税'
                    ),
                    array(
                        'text',
                        '描述'
                    ),
                    array(
                        'create_time',
                        '创建时间'
                    )
                );
                $commission = new DistributorService();
                $search_text = request()->get('search_text', '');
                $from_type = request()->get('from_type','');
                $records_no = request()->get('records_no','');
                $start_date = request()->get('start_date') == "" ? '2010-1-1' : request()->get('start_date');
                $end_date = request()->get('end_date') == "" ? '2038-1-1' : request()->get('end_date');
                $condition['nmar.website_id'] = request()->get('website_id', $this->website_id);
                $shop_id = request()->get('shop_id','');
                if($shop_id){
                    $condition['nmar.shop_id'] = $shop_id;
                }
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
                if ($from_type != '') {
                    if($from_type==4){
                        $condition['nmar.from_type'] = ['>',3];
                    }else{
                        $condition['nmar.from_type'] = $from_type;
                    }
                }
                if($records_no != ''){
                    $condition['nmar.records_no'] = $records_no;
                }
                $list = $commission->getAccountList(1,0, $condition, $order = '', $field = '*');
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["commission"] = '¥'.$list['data'][$k]["commission"];
                    $list['data'][$k]["tax"] = '¥'.$list['data'][$k]["tax"];
                    if( $list['data'][$k]["from_type"]==1){
                        $list['data'][$k]["from_type"] = '订单完成';
                    }
                    if( $list['data'][$k]["from_type"]==2){
                        $list['data'][$k]["from_type"] = '订单退款成功';
                    }
                    if( $list['data'][$k]["from_type"]==3){
                        $list['data'][$k]["from_type"] = '订单支付成功';
                    }
                    if( $list['data'][$k]["from_type"]==4){
                        $list['data'][$k]["from_type"] = '成功提现到账户余额';
                    }
                    if( $list['data'][$k]["from_type"]==5){
                        $list['data'][$k]["from_type"] = '提现到微信待打款';
                    }
                    if( $list['data'][$k]["from_type"]==6){
                        $list['data'][$k]["from_type"] = '提现到账户余额审核中';
                    }
                    if( $list['data'][$k]["from_type"]==7){
                        $list['data'][$k]["from_type"] = '提现到支付宝待打款';
                    }
                    if( $list['data'][$k]["from_type"]==8){
                        $list['data'][$k]["from_type"] = '提现到银行卡待打款';
                    }
                    if( $list['data'][$k]["from_type"]==9){
                        $list['data'][$k]["from_type"] = '成功提现到银行卡';
                    }
                    if( $list['data'][$k]["from_type"]==10){
                        $list['data'][$k]["from_type"] = '成功提现到微信';
                    }
                    if( $list['data'][$k]["from_type"]==11){
                        $list['data'][$k]["from_type"] = '成功提现到支付宝';
                    }
                    if( $list['data'][$k]["from_type"]==12){
                        $list['data'][$k]["from_type"] = '提现到银行卡，审核中';
                    }
                    if( $list['data'][$k]["from_type"]==13){
                        $list['data'][$k]["from_type"] = '提现到微信，审核中';
                    }
                    if( $list['data'][$k]["from_type"]==14){
                        $list['data'][$k]["from_type"] = '提现到支付宝，审核中';
                    }
                    if( $list['data'][$k]["from_type"]==15){
                        $list['data'][$k]["from_type"] = '提现到账户余额，待打款';
                    }
                    if( $list['data'][$k]["from_type"]==16){
                        $list['data'][$k]["from_type"] = '提现到微信，已拒绝';
                    }
                    if( $list['data'][$k]["from_type"]==17){
                        $list['data'][$k]["from_type"] = '提现到支付宝，已拒绝';
                    }
                    if( $list['data'][$k]["from_type"]==18){
                        $list['data'][$k]["from_type"] = '提现到账户余额，已拒绝';
                    }
                    if( $list['data'][$k]["from_type"]==19){
                        $list['data'][$k]["from_type"] = '提现到微信，审核不通过';
                    }
                    if( $list['data'][$k]["from_type"]==20){
                        $list['data'][$k]["from_type"] = '提现到支付宝，审核不通过';
                    }
                    if( $list['data'][$k]["from_type"]==21){
                        $list['data'][$k]["from_type"] = '提现到账户余额，不通过';
                    }
                    if( $list['data'][$k]["from_type"]==22){
                        $list['data'][$k]["from_type"] = '分销商等级升级获得推荐奖';
                    }
                    if( $list['data'][$k]["from_type"]==23){
                        $list['data'][$k]["from_type"] = '提现到银行卡，已拒绝';
                    }
                    if( $list['data'][$k]["from_type"]==24){
                        $list['data'][$k]["from_type"] = '提现到银行卡，审核不通过';
                    }
                }

                $this->addUserLog('佣金流水数据excel导出', 1);
                dataExcel($xlsName, $xlsCell, $list['data']);
            }

            /**
             * 前台申请成为分销商接口
             */
            public function distributorApply($params=array()){

                $uid = $this->uid;
                $website_id = $this->website_id;
                $post_data =request()->post('post_data','');
                $real_name =request()->post('real_name','');
                $member = new DistributorService();
                $res= $member->addDistributorInfo($website_id,$uid,$post_data,$real_name);
                if($res>0){
                    $data['code'] = 0;
                    $data['message'] = "提交成功";
                }else{
                    $data['code'] = -1;
                    $data['message'] = "提交失败";
                }
                return json($data);
            }
            /**
             * 前台完善资料修改
             */
            public function dataComplete(){

                $uid = $this->uid;
                $post_data =request()->post('post_data','');
                $real_name =request()->post('real_name','');
                $member = new DistributorService();
                $res= $member->addDistributorInfos($uid,$post_data,$real_name);
                if($res>0){
                    $data['code'] = 0;
                    $data['message'] = "提交成功";
                }else{
                    $data['code'] = -1;
                    $data['message'] = "提交失败";
                }
                return json($data);
            }
            /**
             * 前台查询申请分销商状态接口
             */
            public function distributorStatus($params=array()){
                $uid = request()->post('uid', '');
                $member = new DistributorService();
                $res= $member->getDistributorStatus($uid);
                return json($res['isdistributor']);
            }

            /**
             * 前台分销中心接口
             */
            public function distributionIndex($params=array()){
                $member = new DistributorService();
                $uid = request()->post("uid", '');
                $res= $member->getDistributorInfo($uid);
                return json($res);
            }

            /**
             * 前台分销订单
             */
            public function distributionOrder($params=array()){
                $uid = $this->uid;
                $page_index = request()->post('page_index', 1);
                $website_id = $this->website_id;
                $page_size = request()->post('page_size', PAGESIZE);
                if(request()->post('order_status', '')){
                    $condition['order_status'] = request()->post('order_status', '');
                }
                $condition['is_deleted'] = 0; // 未删除订单
                $condition['website_id'] = $website_id;
                $condition['buyer_id'] =$uid;
                $order_service = new DistributorService();
                $list = $order_service->getOrderList($page_index, $page_size, $condition, 'create_time desc');
                if(count($list)>0){
                    $data['code'] = 0;
                    $data['data'] = $list;
                    $data['data']['page_index'] = $page_index;
                }else{
                    $data['code'] = -1;
                    $data['message'] = "";
                }
                return json($data);
            }

            /**
             * 前台分销提现详情
             */
            public function withdrawDetail($params=array()){
                $uid = request()->post('uid', '');
                $page_index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition = array();
                if(request()->post('status', '')!=0){
                    $condition['nmar.status'] = request()->post('status', '');
                }
                if(request()->post('status', '')==3){
                    $condition['nmar.status'] = [
                        [
                            ">",
                            5
                        ],
                        [
                            "<",
                            8
                        ]
                    ];
                }
                if(request()->post('status', '')==4){
                    $condition['nmar.status'] = [
                        [
                            ">",
                            3
                        ],
                         [
                             "<",
                             6
                         ]
                    ];
                }
                $condition['nmar.uid'] = $uid;
                $commission = new DistributorService();
                $list = $commission->withdrawDetail($page_index, $page_size,$condition,'');
                return json($list);
            }
            /**
             * pc前台分销佣金明细
             */
            public function commissionDetails($params=array()){
                $page_index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition['nmar.uid'] = $this->uid;
                $commission = new DistributorService();
                $list = $commission->getAccountLists($page_index, $page_size,$condition,'');
                return $list;
            }
            /**
             * 前台分销佣金明细
             */
            public function commissionDetail($params=array()){
                $page_index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition['nmar.uid'] = $this->uid;
                $commission = new DistributorService();
                $list = $commission->getAccountLists($page_index, $page_size,$condition,'');
                $data['code'] = 0;
                $data['data'] = $list;
                $data['data']['page_index'] = $page_index;
                $data['data']['page_size'] = $page_size;
                return json($data);
            }
            /**
             * 前台分销佣金明细详情
             */
            public function commissionRecordDetail($params=array()){
                $id = request()->post('id', '');
                $commission = new DistributorService();
                $list = $commission->getAccountLists(1, 0,['nmar.id'=>$id],'');
                $data['code'] = 0;
                $data['data'] = $list['data'][0];
                return json($data);
            }
            /**
             * 前台我的客户
             */
            public function customerList($params=array()){
                $uid = $this->uid;
                $index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $distributor = new DistributorService();
                $list = $distributor->getCustomerList($uid,$index, $page_size, ['nm.website_id'=>$this->website_id],'nm.reg_time desc');
                $list = $this->object2array($list);
                $new_array = array();
                if($list){
                    $data['data'] = $list;
                }else{
                    $data['data'] = $new_array;
                }
                $data['data']['total_count'] = $list['total_count'];
                $data['data']['page_count'] = $list['page_count'];
                $data['code'] = 0;
                return json($data);
            }
            /**
             * 前台我的团队
             */
            public function teamList($params=array()){
                $uid = $this->uid;
                $website_id = $this->website_id;
                $type = request()->post('type', 1);
                $index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $distributor = new DistributorService();
                $list = $distributor->getTeamList($type,$uid,$website_id,$index, $page_size);
                $myinfos = $distributor->getmyinfos($uid,$website_id);
                $user = new UserModel();
                if(empty($list['data'])){
                    $data['code'] = 0;
                    $data['data']['data'] = array();
                    $data['data']['number1'] = 0;
                    $data['data']['number2'] = 0;
                    $data['data']['number3'] = 0;
                    $data['data']['page_index'] = 1;
                    $data['data']['page_count'] = 1;
                    return json($data);
                }else{
                    foreach ($list['data'] as $k=>$v){
                        if(empty($v['distributor_level_name'])){
                            $list['data'][$k]['distributor_level_name'] = "暂无";
                        }
                        $head = $user->getInfo(["uid"=>$v['uid']],'user_headimg');
                        $list['data'][$k]['user_headimg'] = $head['user_headimg'];
                    }
                }

                $data['data'] = $list;
                $data['data']['page_index'] = $index;
                $data['data']['page_size'] = $page_size;
                $data['data']['number1'] = $myinfos['number1'];
                $data['data']['number2'] = $myinfos['number2'];
                $data['data']['number3'] = $myinfos['number3'];
                $data['code'] = 0;
                return json($data);
            }
            /**
             * 前台佣金提现
             */
            public function commissionWithdraw($params=array()){
                $uid = $this->uid;
                $withdraw_no = 'CW'.time() . rand(111, 999);
                $account_id = request()->post('account_id', '');
                $cash = request()->post('cash', '');
                $distributor = new DistributorService();
                $retval = $distributor->addDistributorCommissionWithdraw($withdraw_no,$uid,$account_id,$cash);
                if($retval>0){
                    $data['code'] = 0;
                    $data['message'] = "申请成功";
                }else{
                    $data['code'] = -1;
                    $data['message'] = getErrorInfo($retval);
                }
                return json($data);
            }
            /**
             * 佣金提现表单页
             */
            public function commissionWithdraw_show($params=array()){
                $uid = $this->uid;
                $user = new usermodel();
                $user_info= $user->getInfo(['uid'=>$this->uid],'payment_password,wx_openid');
                $commission = new DistributorService();
                $my_commission = $commission->getCommissionWithdrawConfig($uid);
                $data = array();
                if($my_commission){
                    $data['data'] = $my_commission;
                }
                $data['data']['is_datum'] = $commission->checkDatum();
                //可提现佣金
                if($my_commission['commission']){
                    $data['data']['commission'] = $my_commission['commission'];
                }else{
                    $data['data']['commission'] = '0.00';
                }
                //设置密码
                if(empty( $user_info['payment_password'])){
                    $data['data']['set_password'] = 1;
                }else{
                    $data['data']['set_password'] = 0;
                }
                if(empty($user_info['wx_openid'])){
                    $data['data']['wx_openid'] = 0;
                }else{
                    $data['data']['wx_openid'] = 1;
                }
                $data['code'] = 0;
                return json($data);
            }

            public function messagePushList(){
                $message = new SysMessagePushModel();
                $list = $message->getQuery(['website_id'=>$this->website_id,'type'=>1],'*','template_id asc');
                if(empty($list)){
                    $list = $message->getQuery(['type'=>1,'website_id'=>0],'*','template_id asc');
                    foreach ($list as $k=>$v){
                        $array = [
                            'template_type' => $v['template_type'],
                            'template_title' => $v['template_title'],
                            'sign_item' => $v['sign_item'],
                            'sample' => $v['sample'],
                            'type'=>1,
                            'website_id' => $this->website_id,
                        ];
                        $message = new SysMessagePushModel();
                        $message->save($array);
                    }
                    $list = $message->getQuery(['website_id'=>$this->website_id,'type'=>1],'*','template_id asc');
                }
                return $list;
            }

            public function editMessage(){
                $id = request()->post('id', '');
                $message = new SysMessagePushModel();
                $list = $message->getInfo(['template_id'=>$id],'*');
                $item = new SysMessageItemModel();
                $list['sign'] = $item->getQuery(['id'=>['in',$list['sign_item']]],'*','');
                return $list;
            }

            public function addMessage(){
                $is_enable = request()->post('is_enable', '');
                $id = request()->post('id', '');
                $template_content = request()->post('template_content', '');
                $message = new SysMessagePushModel();
                $res = $message->save(['is_enable'=>$is_enable,'template_content'=>$template_content],['template_id'=>$id]);
                return AjaxReturn($res);
            }
            /*-----------------------------------------------------------------接口------------------------------------*/
            /**
             * 前台分销中心
             */
            public function distributionCenter(){
                $params['uid'] = $this->uid;
                //查询是否有分销应用
                $member = new DistributorService();
                $member_info = $member->getDistributorInfo($params['uid']);
                $data['code'] = 0;
                $data['data'] = $member_info;
                return json($data);

            }
            /**
             * 前台分销设置
             */
            public function distributionSet(){
                $member = new DistributorService();
                $member_info = $member->getAgreementSite($this->website_id);
                $data['code'] = 0;
                if($member_info){
                    $data['data'] = $member_info;
                }else{
                    $data['data'] = (object)[];
                }
                return json($data);

            }
            /**
             * 前台申请分销商
             */
            public function distributorApply_show(){
                $uid = $this->uid;
                $user = new UserModel();
                $user_info = $user->getInfo(['uid'=>$uid],'real_name,user_tel');
                $member = new VslMemberModel();
                $member_info = $member->getInfo(['uid'=>$uid],'isdistributor');
                $member_info['isdistributor'] = empty($member_info['isdistributor'])?0:$member_info['isdistributor'];
                $config= new DistributorService();
                $list = $config->getDistributionSite($this->website_id);
                $agreement = $config->getAgreementSite($this->website_id);
                $customform = $config->getCustomForm($this->website_id);
                $data['data']['condition'] = $list;
                $data['data']['customform'] = $customform;//自定义表单
                $data['data']['xieyi'] = $agreement;
                $data['data']['user_tel'] = $user_info['user_tel'];
                $data['data']['real_name'] = $user_info['real_name'];
                $data['data']['isdistributor'] = $member_info['isdistributor'];
                $data['code'] = 0;
                return json($data);
            }


            /**
             * 分销佣金
             */
            public function myCommissiona()
            {

                $commission = new DistributorService();
                $uid = $this->uid;
                $my_commission = $commission->getCommissionWithdrawConfig($uid);
                $data = array();
                //可提现佣金
                if($my_commission['commission']){
                    $data['data']['commission'] = $my_commission['commission'];
                }else{
                    $data['data']['commission'] = '0.00';
                }

                //累积佣金
                if($my_commission['commission']) {
                    $data['data']['total_money'] = $my_commission['commission'] + $my_commission['withdrawals']+$my_commission['freezing_commission'];
                }else{
                    $data['data']['total_money'] = '0.00';
                }

                //已提现佣金
                if($my_commission['withdrawals']){
                    $data['data']['withdrawals'] = $my_commission['withdrawals'];
                }else{
                    $data['data']['withdrawals'] = '0.00';
                }

                //提现中
                if($my_commission['apply_withdraw'] || $my_commission['make_withdraw']){
                    $data['data']['apply_withdraw'] = $my_commission['apply_withdraw']+$my_commission['make_withdraw'];
                }else{
                    $data['data']['apply_withdraw'] = '0.00';
                }

                //冻结中
                if($my_commission['freezing_commission']){
                    $data['data']['freezing_commission'] = $my_commission['freezing_commission'];
                }else{
                    $data['data']['freezing_commission'] = '0.00';
                }

                $data['code'] = 0;
                return json($data);

            }


            /**
             * 分销排行榜
             * types 1推荐榜 2佣金榜 3积分榜
             * times month year '' 
             * psize 排名个数 默认10
             * * operation 1 后台 0移动端
             */
            public function ranking(){

                $user_model = new UserModel();
                $order = new VslOrderDistributorCommissionModel();
                $tRecords = new VslAccountRecordsModel();
                $mRecords = new VslMemberAccountRecordsModel();
                $types = request()->post('types', 1);
                $times = request()->post('times', 'month');
                $psize = request()->post('psize', 10);
                $operation = request()->post('operation', 0);
         
                //获取本月起始时间戳和结束时间戳
                $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
                $endThismonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
                //获取本年的起始时间:
                $begin_year = strtotime(date("Y",time())."-1"."-1"); //本年开始
                $end_year = strtotime(date("Y",time())."-12"."-31"); //本年结束
            
                $where = ' where p.website_id=' . $this->website_id;
                if($types == 1){ //推荐榜
                    if($times == "month"){

                        $sql = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` and s.reg_time > ". $beginThismonth ." and s.reg_time <= ". $endThismonth ." LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` and s1.reg_time > ". $beginThismonth ." and s1.reg_time <= ". $endThismonth ." LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` and s2.reg_time > ". $beginThismonth ." and s2.reg_time <= ". $endThismonth ."  where p.website_id=". $this->website_id ." GROUP BY p.uid order by children_count desc limit 0". ',' . $psize;

                        //获取本人的信息
                        $sql_user = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` and s.reg_time > ". $beginThismonth ." and s.reg_time <= ". $endThismonth ." LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` and s1.reg_time > ". $beginThismonth ." and s1.reg_time <= ". $endThismonth ." LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` and s2.reg_time > ". $beginThismonth ." and s2.reg_time <= ". $endThismonth ."  where p.website_id=". $this->website_id . " and p.uid= " . $this->uid ." GROUP BY p.uid order by children_count desc limit 0". ',' . $psize;

                    }else if($times == "year"){
                        $sql = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` and s.reg_time > ". $begin_year ." and s.reg_time <= ". $end_year ." LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` and s1.reg_time > ". $begin_year ." and s1.reg_time <= ". $end_year ." LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` and s2.reg_time > ". $begin_year ." and s2.reg_time <= ". $end_year ."  where p.website_id=". $this->website_id ." GROUP BY p.uid order by children_count desc limit 0". ',' . $psize;

                        //获取本人的信息
                        $sql_user = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` and s.reg_time > ". $begin_year ." and s.reg_time <= ". $end_year ." LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` and s1.reg_time > ". $begin_year ." and s1.reg_time <= ". $end_year ." LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` and s2.reg_time > ". $begin_year ." and s2.reg_time <= ". $end_year ."  where p.website_id=". $this->website_id . " and p.uid= " . $this->uid ." GROUP BY p.uid order by children_count desc limit 0". ',' . $psize;

                    }else{
                        $sql = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` ". $where ." GROUP BY p.uid order by children_count desc limit 0 ". ',' . $psize;

                        //获取本人的信息
                        $sql_user = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` ". " where p.website_id=" . $this->website_id . " and p.uid= " . $this->uid  ." GROUP BY p.uid order by children_count desc limit 0 ". ',' . $psize;
                    }
                    $rankinglists = Db::query($sql); //排行信息
                    $rankingusers = Db::query($sql_user); //本人信息
                }else if($types == 2){ //佣金榜
                    
                    $condition = "website_id = " . $this->website_id;

                    if($times == "month"){
                        $condition .= ' AND create_time > '. $beginThismonth . ' AND create_time <= ' . $endThismonth;
                        
                    }else if($times == "year"){
                        $condition .= ' AND create_time > '. $begin_year . ' AND create_time <= ' . $end_year;
                       
                    }
                    $c_sql = "select 
                    uid,(from_type_2 + from_type_3 - from_type_4) as commissions FROM
                   (
                    select 
                      uid,
                    sum(case when from_type=3 then `commission` else 0 end) as from_type_2,
                    sum(case when from_type=22 then `commission` else 0 end) as from_type_3,
                    sum(case when from_type=2 then `commission` else 0 end) as from_type_4
                    from vsl_distributor_account_records where ".$condition." GROUP BY uid    
                   ) as t2 ORDER BY commissions desc LIMIT 0 ". "," . $psize;

                    $rankinglists = Db::query($c_sql); //排行信息

                     //获取本人佣金
                     $condition .= " and uid = ". $this->uid;
                     $cz_sql = "select 
                     uid,(from_type_2 + from_type_3 - from_type_4) as commissions FROM
                    (
                     select 
                       uid,
                     sum(case when from_type=3 then `commission` else 0 end) as from_type_2,
                     sum(case when from_type=22 then `commission` else 0 end) as from_type_3,
                     sum(case when from_type=2 then `commission` else 0 end) as from_type_4
                     from vsl_distributor_account_records where ".$condition." GROUP BY uid   
                    ) as t2 ORDER BY commissions ";
 
                     $rankingusers = Db::query($cz_sql); //排行信息

                }else{ //积分榜
                    $orderby = 'points desc';
                    $group = 'uids';
                    $field = 'sum(number) as points,uid as uids';
                    $condition = array();
                    $condition['website_id'] = $this->website_id;
                    // $condition['no.from_type'] = 30; //订单完成积分记录
                    $condition['account_type'] = 1; //订单完成积分记录
                    if($times == "month"){
                        $condition["create_time"][] = [
                            ">",
                            $beginThismonth
                        ];
                        $condition["create_time"][] = [
                            "<=",
                            $endThismonth
                        ];
                    }else if($times == "year"){
                        $condition["create_time"][] = [
                            ">",
                            $begin_year
                        ];
                        $condition["create_time"][] = [
                            "<=",
                            $end_year
                        ];
                    } 
                    $rankinglists_all = $mRecords->getRecordsPonitQuery(1, $psize, $condition,$orderby,$group,$field);
                    $rankinglists = array();
                    foreach ($rankinglists_all['data'] as $value) {
                        $rdata = array(
                            'points' => $value['points'],
                            'uid' => $value['uids'],
                        );
                        array_push($rankinglists,$rdata);                
                    }
                    //获取本人积分
                    $condition_user = array();
                    $condition_user['website_id'] = $this->website_id;
                    $condition_user['uid'] = $this->uid;
                    // $condition['no.from_type'] = 30; //订单完成积分记录
                    $condition_user['account_type'] = 1; //订单完成积分记录
                    if($times == "month"){
                        $condition_user["create_time"][] = [
                            ">",
                            $beginThismonth
                        ];
                        $condition_user["create_time"][] = [
                            "<=",
                            $endThismonth
                        ];
                    }else if($times == "year"){
                        $condition_user["create_time"][] = [
                            ">",
                            $begin_year
                        ];
                        $condition_user["create_time"][] = [
                            "<=",
                            $end_year
                        ];
                    }
                    $rankingusers_all = $mRecords->getRecordsPonitQuery(1, $psize, $condition_user,$orderby,$group,$field);
                    $rankingusers = array();
                    foreach ($rankingusers_all['data'] as $v) {
                        $rankingusers['points'] = $v['points'];
                        $rankingusers['uid'] = $v['uids'];
                    }
                }
                //获取当前用户信息
                $member = new Member();
                $member_info = $member->getMemberDetail();
                if(empty($member_info)){
                    $data['code'] = -1000;
                    $data['message'] = '登录信息已过期，请重新登录!';
                    return json($data);
                }
                // 头像
                if (!empty($member_info['user_info']['user_headimg'])) {
                    $member_img = getApiSrc($member_info['user_info']['user_headimg']);
                } else {
                    $member_img = '0';
                }
                $user = array();//本人信息
                if($rankinglists){
                    foreach ($rankinglists as $key => $value) {
                        $user_info = $user_model->getInfo([
                            'uid' => $value['uid']
                        ], 'user_headimg,user_name,nick_name');
                        $rankinglists[$key]['ranking'] = $key+1; //排名
                        $rankinglists[$key]['user_headimg'] = getApiSrc($user_info['user_headimg']); //排名
                        $rankinglists[$key]['user_name'] = $user_info['user_name']; //排名
                        $rankinglists[$key]['nick_name'] = $user_info['nick_name']; //排名
                        $rankinglists[$key]['total'] = $types == 1 ? intval($value['children_count']) : 0; //总数
                        $rankinglists[$key]['points'] = $types == 3 ? $value['points'] : 0; //总数
                        $rankinglists[$key]['commissions'] = $types == 2 ? $value['commissions'] : 0; //总数
                        if($operation == 1){ //后台操作 需补充其余信息
                            if($types == 1){ //原查询推荐榜 补充佣金 积分榜信息
                                //佣金 
                                $condition_commissions= "website_id = " . $this->website_id;
                                if($times == "month"){
                                    $condition_commissions .= ' AND create_time > '. $beginThismonth . ' AND create_time <= ' . $endThismonth;
                                }else if($times == "year"){
                                    $condition_commissions .= ' AND create_time > '. $begin_year . ' AND create_time <= ' . $end_year;
                                }
                                //获取本人佣金
                                $condition_commissions .= " and uid = ". $value['uid'];
                                $cz_sql_c = "select 
                                uid,(from_type_2 + from_type_3 - from_type_4) as commissions FROM
                            (
                                select 
                                uid,
                                sum(case when from_type=3 then `commission` else 0 end) as from_type_2,
                                sum(case when from_type=22 then `commission` else 0 end) as from_type_3,
                                sum(case when from_type=2 then `commission` else 0 end) as from_type_4
                                from vsl_distributor_account_records where ".$condition_commissions." GROUP BY uid   
                            ) as t2 ORDER BY commissions ";

                            $users_commissions = Db::query($cz_sql_c); //排行信息
                            $rankinglists[$key]['commissions'] = $users_commissions ? $users_commissions[0]['commissions'] : 0;
                            //积分
                            //获取本人积分
                            $condition_user = array();
                            $condition_user['website_id'] = $this->website_id;
                            $condition_user['uid'] = $value['uid'];
                            // $condition['no.from_type'] = 30; //订单完成积分记录
                            $condition_user['account_type'] = 1; //订单完成积分记录
                            if($times == "month"){
                                $condition_user["create_time"][] = [
                                    ">",
                                    $beginThismonth
                                ];
                                $condition_user["create_time"][] = [
                                    "<=",
                                    $endThismonth
                                ];
                            }else if($times == "year"){
                                $condition_user["create_time"][] = [
                                    ">",
                                    $begin_year
                                ];
                                $condition_user["create_time"][] = [
                                    "<=",
                                    $end_year
                                ];
                            }
                            $orderby = 'points desc';
                            $group = 'uids';
                            $field = 'sum(number) as points,uid as uids';
                            $rankingusers_all = $mRecords->getRecordsPonitQuery(1, $psize, $condition_user,$orderby,$group,$field);
                            
                            foreach ($rankingusers_all['data'] as $v) {
                                $rankinglists[$key]['points'] = $v['points'];
                            }
                            }else if($types == 2){ //原查询佣金榜 补充推荐 积分榜信息
                                if($times == "month"){
                                    //获取本人的信息
                                    $sql_user = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` and s.reg_time > ". $beginThismonth ." and s.reg_time <= ". $endThismonth ." LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` and s1.reg_time > ". $beginThismonth ." and s1.reg_time <= ". $endThismonth ." LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` and s2.reg_time > ". $beginThismonth ." and s2.reg_time <= ". $endThismonth ."  where p.website_id=". $this->website_id . " and p.uid= " . $value['uid'] ." GROUP BY p.uid order by children_count desc limit 0". ',' . $psize;
            
                                }else if($times == "year"){
                                    //获取本人的信息
                                    $sql_user = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` and s.reg_time > ". $begin_year ." and s.reg_time <= ". $end_year ." LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` and s1.reg_time > ". $begin_year ." and s1.reg_time <= ". $end_year ." LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` and s2.reg_time > ". $begin_year ." and s2.reg_time <= ". $end_year ."  where p.website_id=". $this->website_id . " and p.uid= " . $value['uid'] ." GROUP BY p.uid order by children_count desc limit 0". ',' . $psize;
            
                                }else{
                                    //获取本人的信息
                                    $sql_user = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` ". " where p.website_id=" . $this->website_id . " and p.uid= " . $value['uid']  ." GROUP BY p.uid order by children_count desc limit 0 ". ',' . $psize;
                                }
                                
                                $rankingusers = Db::query($sql_user); //本人信息
                                $rankinglists[$key]['total'] = $rankingusers ? $rankingusers[0]['children_count'] : 0;
                                //获取本人积分
                                $condition_user = array();
                                $condition_user['website_id'] = $this->website_id;
                                $condition_user['uid'] = $value['uid'];
                                // $condition['no.from_type'] = 30; //订单完成积分记录
                                $condition_user['account_type'] = 1; //订单完成积分记录
                                if($times == "month"){
                                    $condition_user["create_time"][] = [
                                        ">",
                                        $beginThismonth
                                    ];
                                    $condition_user["create_time"][] = [
                                        "<=",
                                        $endThismonth
                                    ];
                                }else if($times == "year"){
                                    $condition_user["create_time"][] = [
                                        ">",
                                        $begin_year
                                    ];
                                    $condition_user["create_time"][] = [
                                        "<=",
                                        $end_year
                                    ];
                                }
                                $orderby = 'points desc';
                                $group = 'uids';
                                $field = 'sum(number) as points,uid as uids';
                                $rankingusers_all = $mRecords->getRecordsPonitQuery(1, $psize, $condition_user,$orderby,$group,$field);
                                
                                foreach ($rankingusers_all['data'] as $v) {
                                    $rankinglists[$key]['points'] = $v['points'];
                                }
                            }else if($types == 3){ //原查询积分榜 补充推荐 佣金榜信息
                                if($times == "month"){
                                    //获取本人的信息
                                    $sql_user = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` and s.reg_time > ". $beginThismonth ." and s.reg_time <= ". $endThismonth ." LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` and s1.reg_time > ". $beginThismonth ." and s1.reg_time <= ". $endThismonth ." LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` and s2.reg_time > ". $beginThismonth ." and s2.reg_time <= ". $endThismonth ."  where p.website_id=". $this->website_id . " and p.uid= " . $value['uid'] ." GROUP BY p.uid order by children_count desc limit 0". ',' . $psize;
            
                                }else if($times == "year"){
                                    //获取本人的信息
                                    $sql_user = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` and s.reg_time > ". $begin_year ." and s.reg_time <= ". $end_year ." LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` and s1.reg_time > ". $begin_year ." and s1.reg_time <= ". $end_year ." LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` and s2.reg_time > ". $begin_year ." and s2.reg_time <= ". $end_year ."  where p.website_id=". $this->website_id . " and p.uid= " . $value['uid'] ." GROUP BY p.uid order by children_count desc limit 0". ',' . $psize;
            
                                }else{
                                    //获取本人的信息
                                    $sql_user = "SELECT p.`uid`,p.`referee_id`, (count(distinct s.`uid`) + count(distinct s1.`uid`) + count(distinct s2.`uid`)) AS children_count FROM `vsl_member` as p LEFT JOIN `vsl_member` AS s ON s.`referee_id` = p.`uid` LEFT JOIN `vsl_member` AS s1 ON s1.`referee_id` = s.`uid` LEFT JOIN `vsl_member` AS s2 ON s2.`referee_id` = s1.`uid` ". " where p.website_id=" . $this->website_id . " and p.uid= " . $value['uid']  ." GROUP BY p.uid order by children_count desc limit 0 ". ',' . $psize;
                                }
                                
                                $rankingusers = Db::query($sql_user); //本人信息
                                $rankinglists[$key]['total'] = $rankingusers ? $rankingusers[0]['children_count'] : 0;
                                //佣金 
                                $condition_commissions= "website_id = " . $this->website_id;
                                if($times == "month"){
                                    $condition_commissions .= ' AND create_time > '. $beginThismonth . ' AND create_time <= ' . $endThismonth;
                                }else if($times == "year"){
                                    $condition_commissions .= ' AND create_time > '. $begin_year . ' AND create_time <= ' . $end_year;
                                }
                                //获取本人佣金
                                $condition_commissions .= " and uid = ". $value['uid'];
                                $cz_sql_c = "select 
                                    uid,(from_type_2 + from_type_3 - from_type_4) as commissions FROM
                                (
                                    select 
                                    uid,
                                    sum(case when from_type=3 then `commission` else 0 end) as from_type_2,
                                    sum(case when from_type=22 then `commission` else 0 end) as from_type_3,
                                    sum(case when from_type=2 then `commission` else 0 end) as from_type_4
                                    from vsl_distributor_account_records where ".$condition_commissions." GROUP BY uid   
                                ) as t2 ORDER BY commissions ";

                                $users_commissions = Db::query($cz_sql_c); //排行信息
                                $rankinglists[$key]['commissions'] = $users_commissions ? $users_commissions[0]['commissions'] : 0;
                            }

                        }
                        if($value['uid'] == $this->uid){
                            $user = $rankinglists[$key];
                        }
                        if(($rankinglists[$key]['total'] <= 0 && $types == 1) || ($rankinglists[$key]['commissions'] <= 0 && $types == 2) || ($rankinglists[$key]['points'] <= 0 && $types == 3)) {
                            unset($rankinglists[$key]);
                        }
                    }
                    if(empty($user)){
                        $user = $user_model->getInfo([
                            'uid' => $this->uid
                        ], 'user_headimg,user_name,nick_name');
                        $user['ranking'] = $key+1; //排名
                        $user['user_headimg'] = getApiSrc($user['user_headimg']); //排名
                        $user['ranking'] = 0;
                        $user['total'] = $types == 1 ? intval($rankingusers['children_count']) : 0;
                        $user['points'] = $types == 3 ? $rankingusers['points'] : 0;
                        $user['commissions'] = $types == 2 ? $rankingusers['commissions'] : 0;
                        //获取本人当前信息

                    }
                    if(empty($rankinglists)){
                        $data['code'] = 1;
                        $data['data']['rankinglists'] = [];
                        $data['data']['user'] = $user;
                        $data['message'] = "获取成功,暂未有排名信息";
                    }else{
                        $data['data']['rankinglists'] = $rankinglists; //列表
                        $data['data']['user'] = $user; //本人
                        $data['code'] = 1;
                        $data['message'] = "获取成功";
                    }
                    
                }else{
                    $data['code'] = 1;
                    $data['data']['rankinglists'] = [];
                    $data['data']['user'] = $user;
                    $data['message'] = "获取成功,暂未有排名信息";
                }
                return json($data);
            } 

    /**
     * 获取各项升级条件
     * uid 会员Id
     * level_infos 该等级信息
     * types 1 团队分红 2区域分红 3 全球分红 4分销
     */
    public function levelConditions($uid=0,$level_infos=array(),$types = 1){
        $result = array();
        if(empty($uid)){
            return $result;
        }
        
        $order = new VslOrderModel();
        $order_goods = new VslOrderGoodsModel();
        $member = new VslMemberModel();
        $goods_model = new VslGoodsModel();
        $distributor = new DistributorService();
        $agent = $member->getInfo(['uid'=>$uid],'*');
        
        //获取分销文案设置
        $text = $distributor->getAgreementSite($agent['website_id']);
        
        switch ($types) {
            case 1:
                //团队分红
                    $agents = new TeamBonus();
                    $conditions = explode(',', $level_infos['upgradeconditions']);
                    $result = array();
                    $getAgentInfo = $agents->getAgentLowerInfo($uid);//当前队长的详情信息
                    //判断是否购买过指定商品
                    $goods_info = [];
                    $goods_name = '';
                    if ($level_infos['goods_id']) {
                        //获取商品名称
                        $goods_info = $goods_model->getInfo(['goods_id' => $level_infos['goods_id']], 'goods_name');
                        if($goods_info){
                            $goods_name = $goods_info['goods_name'];
                        }
                        $goods_id = $order_goods->Query(['goods_id' => $level_infos['goods_id'], 'buyer_id' => $uid], 'order_id');
                        if ($goods_id && $agent['down_up_team_level_time']) { //发生降级后 订单完成时间需大于降级时间
                            $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4,'finish_time'=>[">",$agent['down_up_team_level_time']]], '*');
                        }else if($goods_id){
                            $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4], '*');
                        }
                        
                    }
                    $distributor_name = '';
                    if($level_infos && $level_infos['upgrade_level']){
                        //获取指定分销等级名称
                        
                        $distributor_info = $distributor->getDistributorLevelInfo($level_infos['upgrade_level']);
                        if($distributor_info){
                            $distributor_name = $distributor_info['level_name'];
                        }
                        
                        if($agent['down_up_team_level_time']){
                            $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id'],'reg_time'=>[">",$agent['down_up_team_level_time']]]);//该等级指定推荐等级人数
                        }else{
                            $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id']]);//该等级指定推荐等级人数
                        }
                        
                    }else{
                        $low_number = 0;
                    }
                    foreach ($conditions as $k1 => $v1) {
                        switch ($v1) {
                            case 1:
                                $edata = array();
                                $edata['condition_name'] = "商城消费订单满";
                                $edata['condition_type'] = 1;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['pay_money'];
                                $edata['number'] = $getAgentInfo['selforder_money'];
                                array_push($result,$edata);
                                break;
                            case 2:
                                $edata = array();
                                $edata['condition_name'] = "下线会员数满";
                                $edata['condition_type'] = 2;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['group_number'];
                                $edata['number'] = $getAgentInfo['agentcount'];
                                array_push($result,$edata);
                                break;
                            case 3:
                                $edata = array();
                                $edata['condition_name'] = $text['team1']."满";
                                $edata['condition_type'] = 3;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['one_number'];
                                $edata['number'] = intval($getAgentInfo['one_number']);
                                array_push($result,$edata);
                                break;
                            case 4:
                                $edata = array();
                                $edata['condition_name'] = $text['team2']."满";
                                $edata['condition_type'] = 4;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['two_number'];
                                $edata['number'] = intval($getAgentInfo['two_number']);
                                array_push($result,$edata);
                                break;
                            case 5:
                                $edata = array();
                                $edata['condition_name'] = $text['team3']."满";
                                $edata['condition_type'] = 5;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['three_number'];
                                $edata['number'] = intval($getAgentInfo['three_number']);
                                array_push($result,$edata);
                                break;
                            case 6:
                                $edata = array();
                                $edata['condition_name'] = $text['team1']."订单金额满";
                                $edata['condition_type'] = 6;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['order_money'];
                                $edata['number'] = $getAgentInfo['order_money'];
                                array_push($result,$edata);
                                break;
                            case 7:
                                $edata = array();
                                $edata['condition_name'] = $goods_name;
                                $edata['condition_type'] = 7;
                                $edata['up_number'] = 1;
                                $edata['unit'] = "件";
                                $edata['number'] = 0;
                                if ($goods_info) {
                                    $edata['number'] = 1;
                                }
                                array_push($result,$edata);
                                break;
                            case 8:
                                $edata = array();
                                $edata['condition_name'] = "下线总人数满";
                                $edata['condition_type'] = 8;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['number'];
                                $edata['number'] = $getAgentInfo['agentcount1'];
                                array_push($result,$edata);
                                break;
                            case 9:
                                $edata = array();
                                $edata['condition_name'] = "下级";
                                $edata['distributor_name'] = $distributor_name;
                                $edata['condition_type'] = 9;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['level_number'];
                                $edata['number'] = $low_number;
                                array_push($result,$edata);
                                break;
                            case 11:
                                $edata = array();
                                $edata['condition_name'] = " 团队订单金额满";
                                $edata['condition_type'] = 11;
                                $edata['up_number'] = $level_infos['up_team_money'];
                                $edata['number'] = $getAgentInfo['up_team_money'];
                                $edata['unit'] = "元";
                                array_push($result,$edata);
                                break;
                        }
                    }
                    
                    $return['upgrade_condition'] = $level_infos['upgrade_condition'];
                    $return['result'] = $result;
                break;
            case 2:
                //区域分红 AreaBonus
                $agents = new AreaBonus();
                    $conditions = explode(',', $level_infos['upgradeconditions']);
                    $result = array();
                    $getAgentInfo = $agents->getAgentLowerInfo($uid);//当前队长的详情信息
                    //判断是否购买过指定商品
                    $goods_info = [];
                    $goods_name = '';
                    if ($level_infos['goods_id']) {
                        //获取商品名称
                        $goods_info = $goods_model->getInfo(['goods_id' => $level_infos['goods_id']], 'goods_name');
                        if($goods_info){
                            $goods_name = $goods_info['goods_name'];
                        }
                        $goods_id = $order_goods->Query(['goods_id' => $level_infos['goods_id'], 'buyer_id' => $uid], 'order_id');
                        if ($goods_id && $agent['down_up_area_level_time']) { //发生降级后 订单完成时间需大于降级时间
                            $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4,'finish_time'=>[">",$agent['down_up_area_level_time']]], '*');
                        }else if($goods_id){
                            $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4], '*');
                        }
                        
                    }
                    $distributor_name = '';
                    if($level_infos && $level_infos['upgrade_level']){
                        //获取指定分销等级名称
                        
                        $distributor_info = $distributor->getDistributorLevelInfo($level_infos['upgrade_level']);
                        if($distributor_info){
                            $distributor_name = $distributor_info['level_name'];
                        }
                        
                        if($agent['down_up_area_level_time']){
                            $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id'],'reg_time'=>[">",$agent['down_up_area_level_time']]]);//该等级指定推荐等级人数
                        }else{
                            $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id']]);//该等级指定推荐等级人数
                        }
                        
                    }else{
                        $low_number = 0;
                    }
                    foreach ($conditions as $k1 => $v1) {
                        switch ($v1) {
                            case 1:
                                $edata = array();
                                $edata['condition_name'] = "商城消费订单满";
                                $edata['condition_type'] = 1;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['pay_money'];
                                $edata['number'] = $getAgentInfo['selforder_money'];
                                array_push($result,$edata);
                                break;
                            case 2:
                                $edata = array();
                                $edata['condition_name'] = "下线会员数满";
                                $edata['condition_type'] = 2;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['group_number'];
                                $edata['number'] = $getAgentInfo['agentcount'];
                                array_push($result,$edata);
                                break;
                            case 3:
                                $edata = array();
                                $edata['condition_name'] = $text['team1']."满";
                                $edata['condition_type'] = 3;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['one_number'];
                                $edata['number'] = $getAgentInfo['one_number'];
                                array_push($result,$edata);
                                break;
                            case 4:
                                $edata = array();
                                $edata['condition_name'] = $text['team2']."满";
                                $edata['condition_type'] = 4;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['two_number'];
                                $edata['number'] = $getAgentInfo['two_number'];
                                array_push($result,$edata);
                                break;
                            case 5:
                                $edata = array();
                                $edata['condition_name'] = $text['team3']."满";
                                $edata['condition_type'] = 5;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['three_number'];
                                $edata['number'] = $getAgentInfo['three_number'];
                                array_push($result,$edata);
                                break;
                            case 6:
                                $edata = array();
                                $edata['condition_name'] = $text['team1']."订单金额满";
                                $edata['condition_type'] = 6;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['order_money'];
                                $edata['number'] = $getAgentInfo['order_money'];
                                array_push($result,$edata);
                                break;
                            case 7:
                                $edata = array();
                                $edata['condition_name'] = $goods_name;
                                $edata['condition_type'] = 7;
                                $edata['up_number'] = 1;
                                $edata['unit'] = "件";
                                $edata['number'] = 0;
                                if ($goods_info) {
                                    $edata['number'] = 1;
                                }
                                array_push($result,$edata);
                                break;
                            case 8:
                                $edata = array();
                                $edata['condition_name'] = "下线总人数满";
                                $edata['condition_type'] = 8;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['number'];
                                $edata['number'] = $getAgentInfo['agentcount1'];
                                array_push($result,$edata);
                                break;
                            case 9:
                                $edata = array();
                                $edata['condition_name'] = "下级";
                                $edata['distributor_name'] = $distributor_name;
                                $edata['condition_type'] = 9;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['level_number'];
                                $edata['number'] = $low_number;
                                array_push($result,$edata);
                                break;
                            case 11:
                                $edata = array();
                                $edata['condition_name'] = " 团队订单金额满";
                                $edata['condition_type'] = 11;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['up_team_money'];
                                $edata['number'] = $getAgentInfo['up_team_money'];
                                array_push($result,$edata);
                                break;
                        }
                    }
                    
                    $return['upgrade_condition'] = $level_infos['upgrade_condition'];
                    $return['result'] = $result;
                break;
            case 3:
                //全球分红 GlobalBonus
                    $agents = new GlobalBonus();
                    $conditions = explode(',', $level_infos['upgradeconditions']);
                    $result = array();
                    $getAgentInfo = $agents->getAgentLowerInfo($uid);//当前队长的详情信息
                    //判断是否购买过指定商品
                    $goods_info = [];
                    $goods_name = '';
                    if ($level_infos['goods_id']) {
                        //获取商品名称
                        $goods_info = $goods_model->getInfo(['goods_id' => $level_infos['goods_id']], 'goods_name');
                        if($goods_info){
                            $goods_name = $goods_info['goods_name'];
                        }
                        $goods_id = $order_goods->Query(['goods_id' => $level_infos['goods_id'], 'buyer_id' => $uid], 'order_id');
                        if ($goods_id && $agent['down_up_global_level_time']) { //发生降级后 订单完成时间需大于降级时间
                            $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4,'finish_time'=>[">",$agent['down_up_global_level_time']]], '*');
                        }else if($goods_id){
                            $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4], '*');
                        }
                        
                    }
                    $distributor_name = '';
                    if($level_infos && $level_infos['upgrade_level']){
                        //获取指定分销等级名称
                        
                        $distributor_info = $distributor->getDistributorLevelInfo($level_infos['upgrade_level']);
                        if($distributor_info){
                            $distributor_name = $distributor_info['level_name'];
                        }
                        
                        if($agent['down_up_global_level_time']){
                            $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id'],'reg_time'=>[">",$agent['down_up_global_level_time']]]);//该等级指定推荐等级人数
                        }else{
                            $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id']]);//该等级指定推荐等级人数
                        }
                        
                    }else{
                        $low_number = 0;
                    }
                    foreach ($conditions as $k1 => $v1) {
                        switch ($v1) {
                            case 1:
                                $edata = array();
                                $edata['condition_name'] = "商城消费订单满";
                                $edata['condition_type'] = 1;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['pay_money'];
                                $edata['number'] = $getAgentInfo['selforder_money'];
                                array_push($result,$edata);
                                break;
                            case 2:
                                $edata = array();
                                $edata['condition_name'] = "下线会员数满";
                                $edata['condition_type'] = 2;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['group_number'];
                                $edata['number'] = $getAgentInfo['agentcount'];
                                array_push($result,$edata);
                                break;
                            case 3:
                                $edata = array();
                                $edata['condition_name'] = $text['team1']."满";
                                $edata['condition_type'] = 3;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['one_number'];
                                $edata['number'] = $getAgentInfo['one_number'];
                                array_push($result,$edata);
                                break;
                            case 4:
                                $edata = array();
                                $edata['condition_name'] = $text['team2']."满";
                                $edata['condition_type'] = 4;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['two_number'];
                                $edata['number'] = $getAgentInfo['two_number'];
                                array_push($result,$edata);
                                break;
                            case 5:
                                $edata = array();
                                $edata['condition_name'] = $text['team3']."满";
                                $edata['condition_type'] = 5;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['three_number'];
                                $edata['number'] = $getAgentInfo['three_number'];
                                array_push($result,$edata);
                                break;
                            case 6:
                                $edata = array();
                                $edata['condition_name'] = $text['team1']."订单金额满";
                                $edata['condition_type'] = 6;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['order_money'];
                                $edata['number'] = $getAgentInfo['order_money'];
                                array_push($result,$edata);
                                break;
                            case 7:
                                $edata = array();
                                $edata['condition_name'] = $goods_name;
                                $edata['condition_type'] = 7;
                                $edata['up_number'] = 1;
                                $edata['unit'] = "件";
                                $edata['number'] = 0;
                                if ($goods_info) {
                                    $edata['number'] = 1;
                                }
                                array_push($result,$edata);
                                break;
                            case 8:
                                $edata = array();
                                $edata['condition_name'] = "下线客户数达";
                                $edata['condition_type'] = 8;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['number'];
                                $edata['number'] = $getAgentInfo['agentcount1'];
                                array_push($result,$edata);
                                break;
                            case 9:
                                $edata = array();
                                $edata['condition_name'] = "下级";
                                $edata['condition_type'] = 9;
                                $edata['distributor_name'] = $distributor_name;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['level_number'];
                                $edata['number'] = $low_number;
                                array_push($result,$edata);
                                break;
                            case 11:
                                $edata = array();
                                $edata['condition_name'] = " 团队订单金额满";
                                $edata['condition_type'] = 11;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['up_team_money'];
                                $edata['number'] = $getAgentInfo['up_team_money'];
                                array_push($result,$edata);
                                break;
                        }
                    }
                    
                    $return['upgrade_condition'] = $level_infos['upgrade_condition'];
                    $return['result'] = $result;
                break;
            case 4:
                    //全网分销
                    $agents = new DistributorService();
                    $conditions = explode(',', $level_infos['upgradeconditions']);
                    $result = array();
                    $getDistributorInfo = $agents->getDistributorLowerInfo($uid);//当前队长的详情信息
                    
                    $distributor_name = '';
                    if($level_infos && $level_infos['upgrade_level']){
                        //获取指定分销等级名称
                        
                        $distributor_info = $distributor->getDistributorLevelInfo($level_infos['upgrade_level']);
                        if($distributor_info){
                            $distributor_name = $distributor_info['level_name'];
                        }
                        //查看是否降级 变更条件为降级时间之后开始计算 'become_distributor_time'=>[">=",$distributor['down_level_time']]
                        if($agent['down_level_time']){ //发生过降级
                            $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id'],'become_distributor_time'=>[">",$agent['down_level_time']]]);//该等级指定推荐等级人数
                        }else{
                            $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id']]);//该等级指定推荐等级人数
                        }
                    }else{
                        $low_number = 0;
                    }
                    //判断是否购买过指定商品
                    $goods_info = [];
                    $goods_name = '';
                    if ($level_infos['goods_id']) {
                        //获取商品名称
                        $goods_info = $goods_model->getInfo(['goods_id' => $level_infos['goods_id']], 'goods_name');
                        if($goods_info){
                            $goods_name = $goods_info['goods_name'];
                        }
                        $goods_id = $order_goods->Query(['goods_id' => $level_infos['goods_id'], 'buyer_id' => $uid], 'order_id');
                        
                        if ($goods_id && $agent['down_level_time']) { //发生降级后 订单完成时间需大于降级时间
                            $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4,'finish_time'=>[">",$agent['down_level_time']]], '*');
                        }else if($goods_id){
                            $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4], '*');
                        }
                    }
                    foreach ($conditions as $k1 => $v1) {
                        switch ($v1) {
                            case 7:
                                $edata = array();
                                $edata['condition_name'] = $text['team1']."满";
                                $edata['condition_type'] = 7;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['number1'];
                                $edata['number'] = $getDistributorInfo['number1'];
                                array_push($result,$edata);
                                break;
                            case 8:
                                $edata = array();
                                $edata['condition_name'] = $text['team2']."满";
                                $edata['condition_type'] = 8;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['number2'];
                                $edata['number'] = $getDistributorInfo['number2'];
                                array_push($result,$edata);
                                break;
                            case 9:
                                $edata = array();
                                $edata['condition_name'] = $text['team3']."满";
                                $edata['condition_type'] = 9;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['number3'];
                                $edata['number'] = $getDistributorInfo['number3'];
                                array_push($result,$edata);
                                break;
                            case 10:
                                $edata = array();
                                $edata['condition_name'] = "团队分销商满";
                                $edata['condition_type'] = 10;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['number4'];
                                $edata['number'] = $getDistributorInfo['agentcount2'];
                                array_push($result,$edata);
                                break;
                            case 11:
                                $edata = array();
                                $edata['condition_name'] = "下线会员数满";
                                $edata['condition_type'] = 11;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['number5'];
                                $edata['number'] = $getDistributorInfo['agentcount1'];
                                array_push($result,$edata);
                                break;
                            case 12:
                                $edata = array();
                                $edata['condition_name'] = "下级";
                                $edata['condition_type'] = 12;
                                $edata['unit'] = "人";
                                $edata['distributor_name'] = $distributor_name;
                                $edata['up_number'] = $level_infos['level_number'];
                                $edata['number'] = $low_number;
                                array_push($result,$edata);
                                break;
                            case 1:
                                $edata = array();
                                $edata['condition_name'] = "下线总人数满";
                                $edata['condition_type'] = 1;
                                $edata['unit'] = "人";
                                $edata['up_number'] = $level_infos['offline_number'];
                                $edata['number'] = $getDistributorInfo['agentcount'];
                                array_push($result,$edata);
                                break;
                            case 2:
                                $edata = array();
                                $edata['condition_name'] = "分销订单金额满";
                                $edata['condition_type'] = 2;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['order_money'];
                                $edata['number'] = $getDistributorInfo['order_money'];
                                array_push($result,$edata);
                                break;
                            case 3:
                                $edata = array();
                                $edata['condition_name'] = "分销订单数满";
                                $edata['condition_type'] = 3;
                                $edata['unit'] = "单";
                                $edata['up_number'] = $level_infos['order_number'];
                                $edata['number'] = $getDistributorInfo['agentordercount'];
                                array_push($result,$edata);
                                break;
                            case 4:
                                $edata = array();
                                $edata['condition_name'] = "商城消费金额满";
                                $edata['condition_type'] = 4;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['selforder_money'];
                                $edata['number'] = $getDistributorInfo['selforder_money'];
                                array_push($result,$edata);
                                break;
                            case 5:
                                $edata = array();
                                $edata['condition_name'] = "商城消费订单满";
                                $edata['condition_type'] = 5;
                                $edata['unit'] = "元";
                                $edata['up_number'] = $level_infos['selforder_number'];
                                $edata['number'] = $getDistributorInfo['selforder_number'];
                                array_push($result,$edata);
                                break;
                            case 6:
                                $edata = array();
                                $edata['condition_name'] = $goods_name;
                                $edata['condition_type'] = 6;
                                $edata['up_number'] = 1;
                                $edata['unit'] = "件";
                                $edata['number'] = 0;
                                if ($goods_info) {
                                    $edata['number'] = 1;
                                }
                                array_push($result,$edata);
                                break;
                        }
                    }
                $return['upgrade_condition'] = $level_infos['upgrade_condition'];
                $return['result'] = $result;
                   
                break;
            
        }
        return $return;

    }
    /**
     * 获取各项降级条件
     * uid 会员Id
     * level_infos 该等级信息
     */
    public function downlevelConditions($uid=0,$level_infos=array(),$types = 1){
        $result = array();
        if(empty($uid)){
            return $result;
        }
        $member = new VslMemberModel();
        
        $order = new VslOrderModel();
        $order_goods = new VslOrderGoodsModel();
        
        switch ($types) {
            case 1:
                //团队分红
                $agents = new TeamBonus();
                $conditions = explode(',', $level_infos['downgradeconditions']);
                    $result = array();
                    $getAgentInfo = $agents->getAgentLowerInfo($uid);//当前队长的详情信息
                    
                    //获取最长时间天数
                    $maxdays = max($level_infos['team_number_day'],$level_infos['team_money_day'],$level_infos['self_money_day']);
                    //获取会员升级时间
                    $agent = $member->getInfo(['uid'=>$uid],'*');
                    $starttimes = $agent['up_team_level_time'] ? $agent['up_team_level_time'] : $agent['become_team_agent_time'];

                    $starttime = date( "m-d", $starttimes);
                    $endtime = date( "m-d", $starttimes+$maxdays*24*60*60);
                    //降级类型
                    foreach ($conditions as $k1 => $v1) {
                        switch ($v1) {
                            case 1:
                                
                                $team_number_day = $level_infos['team_number_day'];
                                $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_team_level_time')['up_team_level_time']+$team_number_day*24*3600;

                                $getAgentInfo1 = $agents->getAgentInfos($v['uid'],$team_number_day);
                                $limit_number =  $getAgentInfo1['agentordercount'];//限制时间段内团队分红订单数
                                
                                $edata = array();
                                $edata['condition_name'] = "团队分红订单数小于";
                                $edata['condition_type'] = 1;
                                $edata['down_number'] = $level_infos['team_number'];
                                $edata['number'] = $getAgentInfo1['agentordercount']?$getAgentInfo1['agentordercount']:0;
                                $edata['days'] = $team_number_day;
                                array_push($result,$edata);
                                break;
                            case 2:
                                $team_money_day = $level_infos['team_money_day'];
                                $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_team_level_time')['up_team_level_time']+$team_money_day*24*3600;
                               
                                $getAgentInfo2 = $agents->getAgentInfos($v['uid'],$team_money_day);
                                $limit_money1 =  $getAgentInfo2['order_money'];//限制时间段内团队分红订单金额

                                $edata = array();
                                $edata['condition_name'] = "团队分红订单金额小于";
                                $edata['condition_type'] = 2;
                                $edata['down_number'] = $level_infos['team_money'];
                                $edata['number'] = $getAgentInfo2['order_money']?$getAgentInfo1['order_money']:0;
                                $edata['days'] = $team_money_day;
                                array_push($result,$edata);
                                break;
                            case 3:
                                $self_money_day = $level_infos['self_money_day'];
                                $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_team_level_time')['up_team_level_time']+$self_money_day*24*3600;
                                
                                $getAgentInfo3 = $agents->getAgentInfos($v['uid'],$self_money_day);
                                $limit_money2 = $getAgentInfo3['selforder_money'];//限制时间段内自购分红订单金额
                                    
                                $edata = array();
                                $edata['condition_name'] = "自购分红订单金额小于";
                                $edata['condition_type'] = 3;
                                $edata['down_number'] = $level_infos['self_money'];
                                $edata['number'] = $getAgentInfo3['selforder_money']?$getAgentInfo1['selforder_money']:0;
                                $edata['days'] = $self_money_day;
                                array_push($result,$edata);
                                break;
                        }
                    }
                    $return['starttime'] = $starttime;
                    $return['endtime'] = $endtime;
                    $return['downgrade_condition'] = $level_infos['downgrade_condition'];
                    $return['result'] = $result;
                break;
            case 2:
                //区域分红  AreaBonus
                $agents = new AreaBonus();
                $conditions = explode(',', $level_infos['downgradeconditions']);
                    $result = array();
                    $getAgentInfo = $agents->getAgentLowerInfo($uid);//当前队长的详情信息
                    
                    //获取最长时间天数
                    $maxdays = max($level_infos['team_number_day'],$level_infos['team_money_day'],$level_infos['self_money_day']);
                    //获取会员升级时间
                    $agent = $member->getInfo(['uid'=>$uid],'*');
                    $starttimes = $agent['up_area_level_time'] ? $agent['up_area_level_time'] : $agent['become_area_agent_time'];

                    $starttime = date( "m-d", $starttimes);
                    $endtime = date( "m-d", $starttimes+$maxdays*24*60*60);
                    //降级类型
                    foreach ($conditions as $k1 => $v1) {
                        switch ($v1) {
                            case 1:
                                
                                $team_number_day = $level_infos['team_number_day'];
                                $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_area_level_time')['up_area_level_time']+$team_number_day*24*3600;

                                $getAgentInfo1 = $agents->getAgentInfos($v['uid'],$team_number_day);
                                $limit_number =  $getAgentInfo1['agentordercount'];//限制时间段内团队分红订单数
                                
                                $edata = array();
                                $edata['condition_name'] = "团队分红订单数小于";
                                $edata['condition_type'] = 1;
                                $edata['down_number'] = $level_infos['team_number'];
                                $edata['number'] = $getAgentInfo1['agentordercount']?$getAgentInfo1['agentordercount']:0;
                                $edata['days'] = $team_number_day;
                                array_push($result,$edata);
                                break;
                            case 2:
                                $team_money_day = $level_infos['team_money_day'];
                                $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_area_level_time')['up_area_level_time']+$team_money_day*24*3600;
                               
                                $getAgentInfo2 = $agents->getAgentInfos($v['uid'],$team_money_day);
                                $limit_money1 =  $getAgentInfo2['order_money'];//限制时间段内团队分红订单金额

                                $edata = array();
                                $edata['condition_name'] = "团队分红订单金额小于";
                                $edata['condition_type'] = 2;
                                $edata['down_number'] = $level_infos['team_money'];
                                $edata['number'] = $getAgentInfo2['order_money']?$getAgentInfo1['order_money']:0;
                                $edata['days'] = $team_money_day;
                                array_push($result,$edata);
                                break;
                            case 3:
                                $self_money_day = $level_infos['self_money_day'];
                                $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_area_level_time')['up_area_level_time']+$self_money_day*24*3600;
                                
                                $getAgentInfo3 = $agents->getAgentInfos($v['uid'],$self_money_day);
                                $limit_money2 = $getAgentInfo3['selforder_money'];//限制时间段内自购分红订单金额
                                    
                                $edata = array();
                                $edata['condition_name'] = "自购分红订单金额小于";
                                $edata['condition_type'] = 3;
                                $edata['down_number'] = $level_infos['self_money'];
                                $edata['number'] = $getAgentInfo3['selforder_money']?$getAgentInfo1['selforder_money']:0;
                                $edata['days'] = $self_money_day;
                                array_push($result,$edata);
                                break;
                        }
                    }
                    $return['starttime'] = $starttime;
                    $return['endtime'] = $endtime;
                    $return['downgrade_condition'] = $level_infos['downgrade_condition'];
                    $return['result'] = $result;
                break;
            case 3:
                //全球分红 GlobalBonus
                $agents = new GlobalBonus();
                $conditions = explode(',', $level_infos['downgradeconditions']);
                    $result = array();
                    $getAgentInfo = $agents->getAgentLowerInfo($uid);//当前队长的详情信息
                    
                    //获取最长时间天数
                    $maxdays = max($level_infos['team_number_day'],$level_infos['team_money_day'],$level_infos['self_money_day']);
                    //获取会员升级时间
                    $agent = $member->getInfo(['uid'=>$uid],'*');
                    $starttimes = $agent['up_global_level_time'] ? $agent['up_global_level_time'] : $agent['become_global_agent_time'];

                    $starttime = date( "m-d", $starttimes);
                    $endtime = date( "m-d", $starttimes+$maxdays*24*60*60);
                    //降级类型
                    foreach ($conditions as $k1 => $v1) {
                        switch ($v1) {
                            case 1:
                                
                                $team_number_day = $level_infos['team_number_day'];
                                $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_global_level_time')['up_global_level_time']+$team_number_day*24*3600;

                                $getAgentInfo1 = $agents->getAgentInfos($v['uid'],$team_number_day);
                                $limit_number =  $getAgentInfo1['agentordercount'];//限制时间段内团队分红订单数
                                
                                $edata = array();
                                $edata['condition_name'] = "团队分红订单数小于";
                                $edata['condition_type'] = 1;
                                $edata['down_number'] = $level_infos['team_number'];
                                $edata['number'] = $getAgentInfo1['agentordercount']?$getAgentInfo1['agentordercount']:0;
                                $edata['days'] = $team_number_day;
                                array_push($result,$edata);
                                break;
                            case 2:
                                $team_money_day = $level_infos['team_money_day'];
                                $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_global_level_time')['up_global_level_time']+$team_money_day*24*3600;
                               
                                $getAgentInfo2 = $agents->getAgentInfos($v['uid'],$team_money_day);
                                $limit_money1 =  $getAgentInfo2['order_money'];//限制时间段内团队分红订单金额

                                $edata = array();
                                $edata['condition_name'] = "团队分红订单金额小于";
                                $edata['condition_type'] = 2;
                                $edata['down_number'] = $level_infos['team_money'];
                                $edata['number'] = $getAgentInfo2['order_money']?$getAgentInfo1['order_money']:0;
                                $edata['days'] = $team_money_day;
                                array_push($result,$edata);
                                break;
                            case 3:
                                $self_money_day = $level_infos['self_money_day'];
                                $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_global_level_time')['up_global_level_time']+$self_money_day*24*3600;
                                
                                $getAgentInfo3 = $agents->getAgentInfos($v['uid'],$self_money_day);
                                $limit_money2 = $getAgentInfo3['selforder_money'];//限制时间段内自购分红订单金额
                                    
                                $edata = array();
                                $edata['condition_name'] = "自购分红订单金额小于";
                                $edata['condition_type'] = 3;
                                $edata['down_number'] = $level_infos['self_money'];
                                $edata['number'] = $getAgentInfo3['selforder_money']?$getAgentInfo1['selforder_money']:0;
                                $edata['days'] = $self_money_day;
                                array_push($result,$edata);
                                break;
                        }
                    }
                    $return['starttime'] = $starttime;
                    $return['endtime'] = $endtime;
                    $return['downgrade_condition'] = $level_infos['downgrade_condition'];
                    $return['result'] = $result;
                break;
            case 4:
                    //全网分销
                    $agents = new DistributorService();
                    $conditions = explode(',', $level_infos['downgradeconditions']);
                    $result = array();
                    
                    //获取最长时间天数
                    $maxdays = max($level_infos['team_number_day'],$level_infos['team_money_day'],$level_infos['self_money_day']);
                    //获取会员升级时间
                    $agent = $member->getInfo(['uid'=>$uid],'*');
                   
                    $starttimes = $agent['up_level_time'] ? $agent['up_level_time'] : $agent['become_distributor_time'];

                    $starttime = date( "m-d", $starttimes);
                    $endtime = date( "m-d", $starttimes+$maxdays*24*60*60);
                    //降级类型
                    
                    foreach ($conditions as $k1 => $v1) {
                        switch ($v1) {
                            case 1:
                                
                                $team_number_day = $level_infos['team_number_day'];
                                $real_level_time = $member->getInfo(['uid'=>$uid],'up_level_time')['up_level_time']+$team_number_day*24*3600;

                                $getAgentInfo1 = $agents->getDistributorInfos($uid,$team_number_day);
                                
                                $limit_number =  $getAgentInfo1['agentordercount'];//限制时间段内团队分红订单数
                                
                                $edata = array();
                                $edata['condition_name'] = "团队分红订单数小于";
                                $edata['condition_type'] = 1;
                                $edata['down_number'] = $level_infos['team_number'];
                                $edata['number'] = $getAgentInfo1['agentordercount']?$getAgentInfo1['agentordercount']:0;
                                $edata['days'] = $team_number_day;
                                array_push($result,$edata);
                                break;
                            case 2:
                                $team_money_day = $level_infos['team_money_day'];
                                $real_level_time = $member->getInfo(['uid'=>$uid],'up_level_time')['up_level_time']+$team_money_day*24*3600;
                               
                                $getAgentInfo2 = $agents->getDistributorInfos($uid,$team_money_day);
                                $limit_money1 =  $getAgentInfo2['order_money'];//限制时间段内团队分红订单金额

                                $edata = array();
                                $edata['condition_name'] = "团队分红订单金额小于";
                                $edata['condition_type'] = 2;
                                $edata['down_number'] = $level_infos['team_money'];
                                $edata['number'] = $getAgentInfo2['order_money']?$getAgentInfo1['order_money']:0;
                                $edata['days'] = $team_money_day;
                                array_push($result,$edata);
                                break;
                            case 3:
                                $self_money_day = $level_infos['self_money_day'];
                                $real_level_time = $member->getInfo(['uid'=>$uid],'up_level_time')['up_level_time']+$self_money_day*24*3600;
                                
                                $getAgentInfo3 = $agents->getDistributorInfos($uid,$self_money_day);
                                $limit_money2 = $getAgentInfo3['selforder_money'];//限制时间段内自购分红订单金额
                                    
                                $edata = array();
                                $edata['condition_name'] = "自购分红订单金额小于";
                                $edata['condition_type'] = 3;
                                $edata['down_number'] = $level_infos['self_money'];
                                $edata['number'] = $getAgentInfo3['selforder_money']?$getAgentInfo1['selforder_money']:0;
                                $edata['days'] = $self_money_day;
                                array_push($result,$edata);
                                break;
                        }
                    }
                    $return['starttime'] = $starttime;
                    $return['endtime'] = $endtime;
                    $return['downgrade_condition'] = $level_infos['downgrade_condition'];
                    $return['result'] = $result;
                   
                break;
        }
        return $return;

    }
    /**
     * 获取分红升下一级详情
     * types 1 团队分红 2 区域分红 3 全球分红 4分销
     */
    public function upbonusLevel(){
        
        $types = request()->post('types', 4);
        $uid = $this->uid;
        
        $website_id = $this->website_id?$this->website_id:54;
        $member = new VslMemberModel();
    
        $agent = $member->getInfo(['uid'=>$uid],'*');

        $users = new UserModel();
        $member_info = $users->getInfo(['uid' => $uid, 'website_id' => $website_id], '*');
        
        //组装个人信息
        $user['user_name'] = $member_info['nick_name'];
        $user['user_headimg'] = $member_info['user_headimg'];

        $agent = new TeamBonus();
        $member = $agent->getAgentInfo($uid);

        $level = new VslAgentLevelModel();

        if($types == 1){ //团队升级详情分红 team_agent_level_id
            
            if($member['is_team_agent'] != 2 || empty(intval($member['team_agent_level_id']))){
                $data['code'] = -1;
                $data['message'] = "该会员不是团队分红身份";
                return json($data);
            }
            
            //获取所有团队等级 getAgentInfo
            $teamlist =  $agent->getagentLevelList(1, '', ['website_id'=>$website_id,'from_type'=>3],'weight asc');
            $tlist = array();
            foreach ($teamlist['data'] as $key => $value) {
                $rdata['level_name'] = $value['level_name'];
                $rdata['ratio'] = $value['ratio'];
                array_push($tlist,$rdata);
                if($value['id'] == $member['team_agent_level_id']){
                    $user['level_name'] = $value['level_name'];
                }
            }
            
            
            //获取高等级
            $level_weight = $level->Query(['id'=>$member['team_agent_level_id']],'weight');//当前队长的等级权重
            
            $level_weights = $level->Query(['weight'=>['>',implode(',',$level_weight)],'from_type'=>3,'website_id'=>$website_id],'weight');//当前队长的等级权重的上级权重
            if($level_weights){
                sort($level_weights);
                $level_infos = $level->getInfo(['weight' => $level_weights[0],'from_type'=>3,'website_id'=>$website_id]);//比当前队长等级的权重高的等级信息
                if ($level_infos['upgradetype'] == 1) {//是否开启自动升级
                    //获取当前升级进度
                    $levelCondition = $this->levelConditions($uid,$level_infos,1);
                    if($levelCondition){
                        $levelCondition['levelname'] = $level_infos['level_name'];
                    }
                }else{
                    //没有开启自动升级,不显示升级条件
                    $levelCondition = [];
                }
            }else{
                //本人是最高级,不显示升级条件
                $levelCondition = [];
            }
            
            //获取当降级进度
            $down_level_weights = $level->Query(['weight'=>['<',implode(',',$level_weight)],'from_type'=>3,'website_id'=>$website_id],'weight');//分红商的等级权重的下级权重
            
            if($down_level_weights){
                //存在低等级 获取当前等级降级信息
                $level_infos = $level->getInfo(['weight' => $level_weight[0], 'from_type' => 3, 'website_id' => $website_id], '*');
                if($level_infos['downgradetype']==1 && $level_infos['downgradeconditions']){//是否开启自动降级并且有降级条件
                    $downlevelCondition = $this->downlevelConditions($uid,$level_infos,1);
                    if($downlevelCondition){
                        $down_level_infos =  $level->getInfo(['weight' => $down_level_weights[0], 'from_type' => 3, 'website_id' => $website_id], 'level_name');
                        $downlevelCondition['levelname'] = $down_level_infos['level_name'];
                    }
                }else{
                    $downlevelCondition = [];
                }
            }else{
                $downlevelCondition = [];
            }
            //组装信息 返回
            $data['code'] = 1;
            $data['data']['downlevelCondition'] = $downlevelCondition;
            $data['data']['levelCondition'] = $levelCondition;
            $data['data']['user'] = $user;
            $data['data']['levels'] = $tlist;
            $data['message'] = "获取成功";
            return json($data);
            
        }else if($types == 2){ //区域升级详情分红 area_agent_level_id
            if($member['is_area_agent'] != 2 || empty($member['area_agent_level_id'])){
                $data['code'] = -1;
                $data['message'] = "该会员不是区域分红身份";
                return json($data);
            }
            
            
            $user['area_name'] = $area_name;
            //获取所有区域等级 getAgentInfo
            $teamlist =  $agent->getagentLevelList(1, '', ['website_id'=>$website_id,'from_type'=>2],'weight asc');
            $tlist = array();
            foreach ($teamlist['data'] as $key => $value) {
                $rdata['level_name'] = $value['level_name'];
                $rdata['province_ratio'] = $value['province_ratio'];
                $rdata['city_ratio'] = $value['city_ratio'];
                $rdata['area_ratio'] = $value['area_ratio'];
                array_push($tlist,$rdata);
                if($value['id'] == $member['area_agent_level_id']){
                    $user['level_name'] = $value['level_name'];
                    $user['province_ratio'] = $value['province_ratio'];
                    $user['city_ratio'] = $value['city_ratio'];
                    $user['area_ratio'] = $value['area_ratio'];
                    $province = new ProvinceModel();
                    $city = new CityModel();
                    $district = new DistrictModel();
                    $area_id = explode(',',$member['agent_area_id']);
                    $area_type = explode(',',$member['area_type']);
                    $index = 0;
                    $area_name = [];
                    foreach ($area_type as $k1=>$v1){
                        if($v1==3){
                            $area_name[$k1] .= $province->getInfo(['province_id'=>$area_id[$index]],'province_name')['province_name'];
                            $area_name[$k1] .= $city->getInfo(['city_id'=>$area_id[$index+1]],'city_name')['city_name'];
                            $area_name[$k1] .= $district->getInfo(['district_id'=>$area_id[$index+2]],'district_name')['district_name'];
                            $index = $index+4;
                            $area_types[$k1] = '区域代理';
                        }
                        if($v1==2){
                            $area_name[$k1] .= $province->getInfo(['province_id'=>$area_id[$index]],'province_name')['province_name'];
                            $area_name[$k1] .= $city->getInfo(['city_id'=>$area_id[$index+1]],'city_name')['city_name'];
                            $index = $index+3;
                            $area_types[$k1] = '市级代理';
                        }
                        if($v1==1){
                            $area_name[$k1] .= $province->getInfo(['province_id'=>$area_id[$index]],'province_name')['province_name'];
                            $index = $index+2;
                            $area_types[$k1] = '省级代理';
                        }
                    }
                    $area_data = array();
                    if($area_types){
                       
                        foreach ($area_types as $keys => $values) {
                            if($values == '区域代理'){
                                $a_data = array(
                                    'area_ratio' => $user['area_ratio'],
                                    'area_name' => $area_name[$keys],
                                );
                            }
                            if($values == '市级代理'){
                                $a_data = array(
                                    'area_ratio' => $user['city_ratio'],
                                    'area_name' => $area_name[$keys],
                                );
                            }
                            if($values == '省级代理'){
                                $a_data = array(
                                    'area_ratio' => $user['province_ratio'],
                                    'area_name' => $area_name[$keys],
                                );
                            }
                            array_push($area_data,$a_data);
                        }
                    }
                    $user['area_data'] = $area_data;
                }
            }
            //获取升级进度
            //获取高等级
            $level_weight = $level->Query(['id'=>$member['area_agent_level_id']],'weight');//当前队长的等级权重
            $level_weights = $level->Query(['weight'=>['>',implode(',',$level_weight)],'from_type'=>2,'website_id'=>$website_id],'weight');//当前队长的等级权重的上级权重
            if($level_weights){
                sort($level_weights);
                $level_infos = $level->getInfo(['weight' => $level_weights[0],'from_type'=>2,'website_id'=>$website_id]);//比当前队长等级的权重高的等级信息
                if ($level_infos['upgradetype'] == 1) {//是否开启自动升级
                    //获取当前升级进度
                    $levelCondition = $this->levelConditions($uid,$level_infos,3);
                    if($levelCondition){
                        $levelCondition['levelname'] = $level_infos['level_name'];
                    }
                }else{
                    //没有开启自动升级,不显示升级条件
                    $levelCondition = [];
                }
            }else{
                //本人是最高级,不显示升级条件
                $levelCondition = [];
            }

            //获取降级进度
            $down_level_weights = $level->Query(['weight'=>['<',implode(',',$level_weight)],'from_type'=>2,'website_id'=>$website_id],'weight');//分红商的等级权重的下级权重
            
            if($down_level_weights){
                //存在低等级 获取当前等级降级信息
                $level_infos = $level->getInfo(['weight' => $level_weight[0], 'from_type' => 2, 'website_id' => $website_id], '*');
                if($level_infos['downgradetype']==1 && $level_infos['downgradeconditions']){//是否开启自动降级并且有降级条件
                    $downlevelCondition = $this->downlevelConditions($uid,$level_infos,3);
                    if($downlevelCondition){
                        $down_level_infos =  $level->getInfo(['weight' => $down_level_weights[0], 'from_type' => 2, 'website_id' => $website_id], 'level_name');
                        $downlevelCondition['levelname'] = $down_level_infos['level_name'];
                    }
                }else{
                    $downlevelCondition = [];
                }
            }else{
                $downlevelCondition = [];
            }
            //组装信息 返回
            $data['code'] = 1;
            $data['data']['downlevelCondition'] = $downlevelCondition;
            $data['data']['levelCondition'] = $levelCondition;
            $data['data']['user'] = $user;
            $data['data']['levels'] = $tlist;
            $data['message'] = "获取成功";
            return json($data);
            
        }else if($types == 3){ //全球升级详情分红 global_agent_level_id
            if($member['is_global_agent'] != 2 || empty(intval($member['global_agent_level_id']))){
                $data['code'] = -1;
                $data['message'] = "该会员不是全球分红身份";
                return json($data);
            }
            //获取所有全球等级 getAgentInfo
            $agent = new GlobalBonus();
            $teamlist =  $agent->getagentLevelList(1, '', ['website_id'=>$website_id,'from_type'=>1],'weight asc');
            $tlist = array();
            foreach ($teamlist['data'] as $key => $value) {
                $rdata['level_name'] = $value['level_name'];
                $rdata['ratio'] = $value['ratio'];
                array_push($tlist,$rdata);
                if($value['id'] == $member['global_agent_level_id']){
                    $user['level_name'] = $value['level_name'];
                }
            }

            //获取升级进度
            //获取高等级
            $level_weight = $level->Query(['id'=>$member['global_agent_level_id']],'weight');//当前队长的等级权重
            $level_weights = $level->Query(['weight'=>['>',implode(',',$level_weight)],'from_type'=>1,'website_id'=>$website_id],'weight');//当前队长的等级权重的上级权重
            if($level_weights){
                sort($level_weights);
                $level_infos = $level->getInfo(['weight' => $level_weights[0],'from_type'=>1,'website_id'=>$website_id]);//比当前队长等级的权重高的等级信息
                if ($level_infos['upgradetype'] == 1) {//是否开启自动升级
                    //获取当前升级进度
                    $levelCondition = $this->levelConditions($uid,$level_infos,3);
                    if($levelCondition){
                        $levelCondition['levelname'] = $level_infos['level_name'];
                    }
                }else{
                    //没有开启自动升级,不显示升级条件
                    $levelCondition = [];
                }
            }else{
                //本人是最高级,不显示升级条件
                $levelCondition = [];
            }

            //获取降级进度
            $down_level_weights = $level->Query(['weight'=>['<',implode(',',$level_weight)],'from_type'=>1,'website_id'=>$website_id],'weight');//分红商的等级权重的下级权重
            
            if($down_level_weights){
                //存在低等级 获取当前等级降级信息
                $level_infos = $level->getInfo(['weight' => $level_weight[0], 'from_type' => 1, 'website_id' => $website_id], '*');
                if($level_infos['downgradetype']==1 && $level_infos['downgradeconditions']){//是否开启自动降级并且有降级条件
                    $downlevelCondition = $this->downlevelConditions($uid,$level_infos,3);
                    if($downlevelCondition){
                        $down_level_infos =  $level->getInfo(['weight' => $down_level_weights[0], 'from_type' => 1, 'website_id' => $website_id], 'level_name');
                        $downlevelCondition['levelname'] = $down_level_infos['level_name'];
                    }
                }else{
                    $downlevelCondition = [];
                }
            }else{
                $downlevelCondition = [];
            }
            //组装信息 返回
            $data['code'] = 1;
            $data['data']['downlevelCondition'] = $downlevelCondition;
            $data['data']['levelCondition'] = $levelCondition;
            $data['data']['user'] = $user;
            $data['data']['levels'] = $tlist;
            $data['message'] = "获取成功";
            return json($data);
           
        }else{ //分销
            if($member['isdistributor'] != 2 || empty(intval($member['distributor_level_id']))){
                $data['code'] = -1;
                $data['message'] = "该会员不是分销商身份";
                return json($data);
            }
            //获取所有分销等级 getAgentInfo
            $distributor = new DistributorService();
            $teamlist =  $distributor->getDistributorLevelList(1, '', ['website_id'=>$website_id],'weight asc');
            $tlist = array();
            foreach ($teamlist['data'] as $key => $value) {
                //佣金+积分
                $rdata['level_name'] = $value['level_name'];
                $rdata['recommend_type'] = $value['recommend_type'];

                $rdata['commission1'] = $value['commission1'];
                $rdata['commission2'] = $value['commission2'];
                $rdata['commission3'] = $value['commission3'];
                $rdata['commission_point1'] = $value['commission_point1'];
                $rdata['commission_point2'] = $value['commission_point2'];
                $rdata['commission_point3'] = $value['commission_point3'];

                $rdata['commission11'] = $value['commission11'];
                $rdata['commission22'] = $value['commission22'];
                $rdata['commission33'] = $value['commission33'];
                $rdata['commission_point11'] = $value['commission_point11'];
                $rdata['commission_point22'] = $value['commission_point22'];
                $rdata['commission_point33'] = $value['commission_point33'];
                //推荐奖 佣金+积分
                $rdata['recommend1'] = $value['recommend1'];
                $rdata['recommend2'] = $value['recommend2'];
                $rdata['recommend3'] = $value['recommend3'];
                $rdata['recommend_point1'] = $value['recommend_point1'];
                $rdata['recommend_point2'] = $value['recommend_point2'];
                $rdata['recommend_point3'] = $value['recommend_point3'];
                array_push($tlist,$rdata);
                if($value['id'] == $member['distributor_level_id']){
                    $user['level_name'] = $value['level_name'];
                }
            }

            //获取升级进度
            $level = new VslDistributorLevelModel();
            //获取高等级
            $level_weight = $level->Query(['id'=>$member['distributor_level_id']],'weight');//当前队长的等级权重
            $level_weights = $level->Query(['weight'=>['>',implode(',',$level_weight)],'website_id'=>$website_id],'weight');//当前队长的等级权重的上级权重
            if($level_weights){
                sort($level_weights);
                $level_infos = $level->getInfo(['weight' => $level_weights[0],'website_id'=>$website_id]);//比当前队长等级的权重高的等级信息
                
                if ($level_infos['upgradetype'] == 1) {//是否开启自动升级
                    //获取当前升级进度
                    $levelCondition = $this->levelConditions($uid,$level_infos,4);
                    if($levelCondition){
                        $levelCondition['levelname'] = $level_infos['level_name'];
                    }
                }else{
                    //没有开启自动升级,不显示升级条件
                    $levelCondition = [];
                }
            }else{
                //本人是最高级,不显示升级条件
                $levelCondition = [];
            }

            //获取降级进度
            $down_level_weights = $level->Query(['weight'=>['<',implode(',',$level_weight)],'website_id'=>$website_id],'weight');//分红商的等级权重的下级权重
            
            if($down_level_weights){
                //存在低等级 获取当前等级降级信息
                $level_infos = $level->getInfo(['weight' => $level_weight[0], 'website_id' => $website_id], '*');
                if($level_infos['downgradetype']==1 && $level_infos['downgradeconditions']){//是否开启自动降级并且有降级条件
                    $downlevelCondition = $this->downlevelConditions($uid,$level_infos,4);
                    if($downlevelCondition){
                        $down_level_infos =  $level->getInfo(['weight' => $down_level_weights[0], 'website_id' => $website_id], 'level_name');
                        $downlevelCondition['levelname'] = $down_level_infos['level_name'];
                    }
                }else{
                    $downlevelCondition = [];
                }
            }else{
                $downlevelCondition = [];
            }


            //组装信息 返回
            $data['code'] = 1;
            $data['data']['downlevelCondition'] = $downlevelCondition;
            $data['data']['levelCondition'] = $levelCondition;
            $data['data']['user'] = $user;
            $data['data']['levels'] = $tlist;
            $data['message'] = "获取成功";
            return json($data);
            

        }
    }

}
