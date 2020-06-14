<?php
namespace addons\globalbonus\controller;
use addons\distribution\model\SysMessageItemModel;
use addons\distribution\model\SysMessagePushModel;
use addons\globalbonus\Globalbonus as baseGlobalBonus;
use addons\bonus\model\VslAgentLevelModel as AgentLevelModel;
use addons\globalbonus\service\GlobalBonus as agentService;
use data\service\Config;
use think\helper\Time;
        /**
         * 股东设置控制器
         *
         *
         */
        class Globalbonus extends baseGlobalBonus
        {
            public function __construct(){
                parent::__construct();
            }

        /**
         * 股东列表
         */
        public function globalAgentList(){
            $page_index = request()->post('page_index',1);
            $iphone = request()->post('iphone',"");
            $search_text = request()->post('search_text','');
            $agent_level_id = request()->post('level_id','');
            $isagent = request()->post('is_global_agent','');
            if( $search_text){
                $condition['us.user_name|us.nick_name'] = array('like','%'.$search_text.'%');
            }
            if($iphone ){
                $condition['nm.mobile'] = $iphone;
            }
            if($isagent!=5){
                $condition['nm.is_global_agent'] = $isagent;
            }else{
                $condition['nm.is_global_agent'] = ['in','1,2,-1'];
            }
            if($agent_level_id){
                $condition['nm.global_agent_level_id'] = $agent_level_id;
            }
            $condition['nm.website_id'] = $this->website_id;
            $agent = new agentService();
            $uid = "";
            $list = $agent->getagentList($uid,$page_index, PAGESIZE, $condition,'become_global_agent_time desc');
            return $list;
        }
            
        /**
         * 修改股东状态
         */
        public function setGlobalAgentStatus(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $uid = request()->post('uid','');
            $status = request()->post('status','');
            $agent = new agentService();
            $retval = $agent->setStatus($uid, $status);
            if($retval){
                $this->addUserLog('修改股东状态', $uid);
            }
            return AjaxReturn($retval);
        }

        /**
         * 移除股东
         */
        public function delGlobalAgent(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $member = new agentService();
            $uid = request()->post("uid", '');
            $res = $member->deleteagent($uid);
            if($res){
                $this->addUserLog('移除股东', '股东id'.$uid);
            }
            return AjaxReturn($res);
        }

        /**
         * 修改股东信息
         */
        public function updateGlobalAgentInfo(){
            if($this->merchant_expire==1){
                return AjaxReturn(-1);
            }
            $member = new agentService();
            $uid = request()->post("uid", '');
            $status = request()->post("status", '');
            $agent_level_id = request()->post("global_agent_level_id", '');
            $data = [
                'global_agent_level_id'=> $agent_level_id,
                'is_global_agent'=>$status
            ];
            $res= $member->updateagentInfo($data,$uid);
            return AjaxReturn($res);
        }

        /**
         * 股东等级列表
         */
        public function globalAgentLevelList(){
            $index = isset($_POST["page_index"]) ? $_POST["page_index"] : 1;
            $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
            $agent = new agentService();
            $list =  $agent->getagentLevelList($index, PAGESIZE, ['level_name' => array('like','%'.$search_text.'%'),'website_id'=>$this->website_id,'from_type'=>1]);
            return json($list);
        }

        /**
         * 添加股东等级
         */
        public function addGlobalAgentLevel(){

            $level_name = isset($_POST['level_name'])?$_POST['level_name']:'';//等级名称
            $ratio = isset($_POST['ratio'])?$_POST['ratio']:'';//分红比例
            $upgradetype = isset($_POST['upgradetype'])?$_POST['upgradetype']:2;//自动升级
            $pay_money  = request()->post('pay_money', ''); // 自购订单消费金额额度
            $offline_number  = request()->post('offline_number', ''); // 下线客户人数
            $one_number = request()->post('one_number', '');//一级分销商人数
            $two_number = request()->post('two_number', '');//二级分销商人数
            $three_number = request()->post('three_number', '');//三级分销商人数
            $order_money = request()->post('order_money', ''); // 下级订单总额
            $downgradetype = isset($_POST['downgradetype'])?$_POST['downgradetype']:2;//自动降级
            $team_number = isset($_POST['team_number'])?$_POST['team_number']:'';//团队订单数
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
            $up_team_money = request()->post('up_team_money', ''); // 团队订单金额
            $agent=new agentService();
            $retval=$agent->addagentLevel($level_name,$ratio,$upgradetype,$pay_money,$offline_number,$one_number,$two_number,$three_number,$order_money,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$group_number,$up_team_money);
            if($retval){
                $this->addUserLog('添加股东等级', $retval);
            }
            return AjaxReturn($retval);
        }
        /**
         * 修改股东等级
         */
        public function updateGlobalAgentLevel(){
            $agent=new agentService();
            $id = request()->post('id', '');
            $level_name = isset($_POST['level_name'])?$_POST['level_name']:'';//等级名称
            $ratio = isset($_POST['ratio'])?$_POST['ratio']:'';//分红比例
            $upgradetype = isset($_POST['upgradetype'])?$_POST['upgradetype']:2;//自动升级
            $pay_money  = request()->post('pay_money', ''); // 自购订单消费金额额度
            $offline_number  = request()->post('offline_number', ''); // 下级分销商人数
            $one_number = request()->post('one_number', '');//一级分销商人数
            $two_number = request()->post('two_number', '');//二级分销商人数
            $three_number = request()->post('three_number', '');//三级分销商人数
            $order_money = request()->post('order_money', ''); // 下级订单总额
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
            $up_team_money = request()->post('up_team_money', ''); // 团队订单金额
            $retval=$agent->updateagentLevel($id,$level_name,$ratio,$upgradetype,$pay_money,$offline_number,$one_number,$two_number,$three_number,$order_money,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$group_number,$up_team_money);
            if($retval){
                $this->addUserLog('修改股东等级', $id);
            }
            return AjaxReturn($retval);
        }
        /**
         * 删除 股东等级
         */
        public function deleteGlobalAgentLevel()
        {
            $agent = new agentService();
            $id = request()->post("id", "");
            $res = $agent->deleteagentLevel($id);
            return AjaxReturn($res);
        }
        /**
         * 分红概况
         */
        public function globalBonusProfile(){
                $agent_level = new AgentLevelModel();
                $level_info = $agent_level->getInfo(['website_id' => $this->website_id,'from_type'=>1,'is_default'=>1],'*');
                    if($level_info){
                    }else{
                        $data = array(
                            'level_name' => '默认股东等级',
                            'is_default'=>1,
                            'ratio'=>0,
                            'weight' => 1,
                            'from_type'=>1,
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
            public function globalBonusOrderProfile()
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
        public function globalBasicSetting()
        {
            $config= new agentService();
            if (request()->isPost()) {
                // 基本设置
                $globalbonus_status = request()->post('globalbonus_status', ''); // 是否开启股东分红
                $agent_condition = request()->post('globalagent_condition', ''); // 成为股东的条件
                $agent_conditions = request()->post('globalagent_conditions', ''); // 条件选择
                $pay_money  = request()->post('pay_money', ''); // 自购订单消费金额额度
                $number  = request()->post('number', ''); // 下级分销商人数
                $one_number = request()->post('one_number', '');//一级分销商人数
                $two_number = request()->post('two_number', '');//二级分销商人数
                $three_number = request()->post('three_number', '');//三级分销商人数
                $goods_id = request()->post('goods_id', ''); // 指定商品
                $order_money = request()->post('order_money', ''); // 下级订单总额
                $agent_check = request()->post('globalagent_check', ''); // 是否开启自动审核
                $agent_grade = request()->post('globalagent_grade', ''); // 是否开启跳级降级设置
                $agent_data = request()->post('globalagent_data', ''); // 开启资料完善
                $up_team_money = request()->post('up_team_money', ''); // 团队订单金额
                $retval = $config->setGlobalBonusSite($globalbonus_status,$agent_condition, $agent_conditions, $pay_money,$number,$one_number,$two_number,$three_number, $order_money,$agent_check, $agent_grade, $goods_id,$agent_data,$up_team_money);
                if($retval){
                    $this->addUserLog('添加基本设置', $retval);
                }
                setAddons('globalbonus', $this->website_id, $this->instance_id);
                return AjaxReturn($retval);
            }
        }

        /**
         * 结算设置
         */
        public function globalSettlementSetting()
        {
            $config= new agentService();
            if (request()->isPost()) {
                // 结算设置
                $bonus_calculation = request()->post('bonus_calculation', ''); // 分红计算节点
                $withdrawals_check = request()->post('withdrawals_check', ''); // 自动分红是否开启
                $limit_date = request()->post('limit_date', ''); // 分红发放指定日期
                $limit_time = request()->post('limit_time', ''); // 分红发放时间
                $bonus_poundage = request()->post('bonus_poundage', ''); // 分红比例
                $poundage = request()->post('poundage', ''); // 个人所得税
                $withdrawals_begin = request()->post('withdrawals_begin', ''); // 分红免打税区间
                $withdrawals_end = request()->post('withdrawals_end', ''); // 分红免打税区间
                $retval = $config->setSettlementSite($bonus_calculation, $limit_time,$withdrawals_check,  $bonus_poundage,$poundage,$withdrawals_begin,$withdrawals_end,$limit_date);
                if($retval){
                    $this->addUserLog('结算设置', $retval);
                }
                return AjaxReturn($retval);
            }
        }
        /**
         * 申请协议
         */
        public function globalApplicationAgreement()
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
                $withdrawals_global_bonus = request()->post('withdrawals_global_bonus', ''); // 已发放分红
                $withdrawal_global_bonus = request()->post('withdrawal_global_bonus', ''); // 待发放分红
                $frozen_global_bonus = request()->post('frozen_global_bonus', ''); // 冻结分红
                $apply_global = request()->post('apply_global', ''); // 申请全球股东
                $global_agreement = request()->post('global_agreement', ''); // 全球股东
                if($type==1){
                    $configs = new Config();
                    $configs->setSite($bonus_name,$bonus,$withdrawals_bonus,$withdrawal_bonus,$frozen_bonus,$bonus_details,$bonus_money,$bonus_order);
                }
                if($content){
                    $content = htmlspecialchars_decode($content);
                }
                $retval = $config->setAgreementSite($type,$logo,$content,$withdrawals_global_bonus,$withdrawal_global_bonus,$frozen_global_bonus,$apply_global,$global_agreement);
                if($retval){
                    $this->addUserLog('全球股东申请协议', $retval);
                }
                return AjaxReturn($retval);
            }
        }
            /**
             * 后台股东分红明细
             */
            public function globalBonusList(){
                $page_index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition = array();
                $condition['nmar.from_type'] = 1;
                $condition['nmar.website_id'] = $this->website_id;
                $group = 'nmar.sn';
                $bonus = new agentService();
                $list = $bonus->getBonusDetailList($page_index, $page_size,$condition,$group);
                return $list;
            }
            /**
             * 后台股东分红详情
             */
            public function globalBonusInfo(){
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
                        $where['us.mobile'] = $mobile;
                    }
                    if($agent_level_id){
                        $where['sm.global_agent_level_id'] = $agent_level_id;
                    }
                    $condition = array();
                    $condition['nmar.from_type'] = 1;
                    $condition['nmar.sn'] = $sn;
                    $condition['nmar.website_id'] =  $this->website_id;
                    $bonus = new agentService();
                    $list = $bonus->getBonusInfoList($page_index, $page_size,$condition,'',$where);
                    return $list;
                }
            }
            /**
             * 股东分红结算单
             */
            public function globalBonusBalance()
            {
                $page_index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition = array();
                $condition['nmar.from_type'] = 1;
                $condition['nmar.ungrant_bonus'] = ['>',0];
                $condition['nmar.website_id'] =  $this->website_id;
                $agent = new agentService();
                $data = $agent->getUnGrantBonus($page_index, $page_size, $condition, $order = '', $field = '*');
                return $data;
            }
            /**
             * 未分红发放
             */
            public function globalBonusGrant()
            {
                $agent = new agentService();
                $retval = $agent->grantGlobalBonus(1);
                if($retval){
                    $this->addUserLog('未分红发放', $retval);
                }
                return AjaxReturn($retval);
            }
            public function globalMessagePushList(){
                $message = new SysMessagePushModel();
                $list = $message->getQuery(['website_id'=>$this->website_id,'type'=>2],'*','template_id asc');
                if(empty($list)){
                    $list = $message->getQuery(['type'=>2,'website_id'=>0],'*','template_id asc');
                    foreach ($list as $k=>$v){
                        $array = [
                            'template_type' => $v['template_type'],
                            'template_title' => $v['template_title'],
                            'sign_item' => $v['sign_item'],
                            'sample' => $v['sample'],
                            'type'=>2,
                            'website_id' => $this->website_id,
                        ];
                        $message = new SysMessagePushModel();
                        $message->save($array);
                    }
                    $list = $message->getQuery(['website_id'=>$this->website_id,'type'=>2],'*','template_id asc');
                }
                return $list;
            }
            public function globalEditMessage(){
                $id = request()->post('id', '');
                $message = new SysMessagePushModel();
                $list = $message->getInfo(['template_id'=>$id],'*');
                $item = new SysMessageItemModel();
                $list['sign'] = $item->getQuery(['id'=>['in',$list['sign_item']]],'*','');
                return $list;
            }
            public function addGlobalMessage(){
                $is_enable = request()->post('is_enable', '');
                $id = request()->post('id', '');
                $template_content = request()->post('template_content', '');
                $message = new SysMessagePushModel();
                $res = $message->save(['is_enable'=>$is_enable,'template_content'=>$template_content],['template_id'=>$id]);
                return AjaxReturn($res);
            }

            /**
             * 前台申请成为股东接口
             */
            public function globalAgentApply(){
                $uid = $this->uid;
                $website_id =  $this->website_id;
                $post_data = request()->post('post_data');
                $real_name = request()->post('real_name','');
                $member = new agentService();
                $res= $member->addagentInfo($website_id,$uid, $post_data, $real_name);
                if($res>0){
                    $data['code'] = 0;
                    $data['message'] = "申请成功";
                }else{
                    $data['code'] = -1;
                    $data['message'] = "申请失败";
                }
                if($res){
                    $this->addUserLog('前台申请成为股东接口', $uid);
                }

                return json($data);
            }

            /**
             * 前台查询申请股东状态接口
             */
            public function globalAgentStatus(){
                $uid = $this->uid;
                $member = new agentService();
                $info= $member->getagentStatus($uid);
                return json($info);
            }

            /**
             * 前台股东分红明细
             */
            public function globalBonusDetail(){
                $uid = $this->uid;
                $page_index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition = array();
                $condition['nmar.uid'] = $uid;
                $condition['nmar.bonus_type'] = 1;
                $condition['nmar.website_id'] = $this->website_id;
                $bonus = new agentService();
                $list = $bonus->getBonusRecords($page_index, $page_size,$condition,'');
                return json($list);
            }

}
