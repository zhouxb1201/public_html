<?php
namespace addons\microshop\controller;
use addons\microshop\Microshop as baseMicroShop;
use addons\microshop\model\VslMicroShopLevelModel;
use addons\microshop\model\VslMicroShopProfitWithdrawModel;
use addons\microshop\service\MicroShop as MicroShopService;
use addons\seckill\server\Seckill;
use think\helper\Time;
use data\model\UserModel;
        /**
         * 微店设置控制器
         *
         * @author  www.vslai.com
         *
         */
        class Microshop extends baseMicroShop
        {
            public function __construct(){
                parent::__construct();
            }

        /**
         * 店主列表
         */
        public function shopKeeperList(){
            $index = request()->post('page_index',1);
            $iphone = request()->post('iphone',"");
            $search_text = request()->post('search_text','');
            $referee_name = request()->post('referee_name','');
            $ShopKeeper_level_id = request()->post('level_id','');
            $isShopKeeper = request()->post('isshopkeeper','');
            if( $search_text){
                $condition['us.user_name|us.nick_name'] = array('like','%'.$search_text.'%');
            }
            if( $referee_name){
                $condition['us.user_name|us.nick_name'] = array('like','%'.$referee_name.'%');
            }
            if($iphone){
                $condition['nm.mobile'] = $iphone;
            }
            if($isShopKeeper!=5){
                $condition['nm.isshopkeeper'] = $isShopKeeper;
            }else{
                $condition['nm.isshopkeeper'] = ['in','1,2,-1'];
            }
            if($ShopKeeper_level_id){
                $condition['nm.shopKeeper_level_id'] = $ShopKeeper_level_id;
            }
            $ShopKeeper = new MicroShopService();
            $uid = 0;
            $list = $ShopKeeper->getShopKeeperList($uid,$index, PAGESIZE, $condition,'become_ShopKeeper_time desc');
            return $list;
        }

        /**
         * 移除店主
         */
        public function delShopKeeper(){
            $member = new MicroShopService();
            $uid = request()->post("uid", '');
            $res = $member->deleteShopKeeper($uid);
            if($res){
                $this->addUserLog('移除店主', $uid);
            }
            return AjaxReturn($res);
        }

        /**
         * 店主等级列表
         */
        public function shopKeeperLevelList(){
            $index = isset($_POST["page_index"]) ? $_POST["page_index"] : 1;
            $ShopKeeper = new MicroShopService();
            $list =  $ShopKeeper->getShopKeeperLevelList($index, PAGESIZE, ['website_id'=>$this->website_id],'weight asc');
            return $list;
        }

        /**
         * 添加店主等级
         */
        public function addShopKeeperLevel(){

            $level_name = isset($_POST['level_name'])?$_POST['level_name']:'';//等级名称
            $profit1 = isset($_POST['profit1'])?$_POST['profit1']:'';//一级收益比例
            $profit2 = isset($_POST['profit2'])?$_POST['profit2']:'';//二级收益比例
            $profit3 = isset($_POST['profit3'])?$_POST['profit3']:'';//三级收益比例
            $weight = isset($_POST['weight'])?$_POST['weight']:'';//权重
            $selfpurchase_rebate = isset($_POST['selfpurchase_rebate'])?$_POST['selfpurchase_rebate']:'';//自购返利
            $shop_rebate = isset($_POST['shop_rebate'])?$_POST['shop_rebate']:'';//下级开店返利
            $term_validity = isset($_POST['term_validity'])?$_POST['term_validity']:'';//有效期
            $validity = isset($_POST['term_validity'])?$_POST['validity']:'';//有效期
            $goods_id = isset($_POST['goods_id'])?$_POST['goods_id']:'';//指定商品id
            $ShopKeeper=new MicroShopService();
            $retval=$ShopKeeper->addShopKeeperLevel($level_name, $profit1, $profit2, $profit3,$selfpurchase_rebate,$shop_rebate,$term_validity,$validity,$weight,$goods_id);
            if($retval){
                $this->addUserLog('添加店主等级', $retval);
            }
            return AjaxReturn($retval);
        }
        /**
         * 修改店主等级
         */
        public function updateShopKeeperLevel(){
            $ShopKeeper=new MicroShopService();
            $id = isset($_POST['id'])?$_POST['id']:'';//等级id
            $level_name = isset($_POST['level_name'])?$_POST['level_name']:'';//等级名称
            $profit1 = isset($_POST['profit1'])?$_POST['profit1']:'';//一级收益比例
            $profit2 = isset($_POST['profit2'])?$_POST['profit2']:'';//二级收益比例
            $profit3 = isset($_POST['profit3'])?$_POST['profit3']:'';//三级收益比例
            $weight = isset($_POST['weight'])?$_POST['weight']:'';//权重
            $selfpurchase_rebate = isset($_POST['selfpurchase_rebate'])?$_POST['selfpurchase_rebate']:'';//自购返利
            $shop_rebate = isset($_POST['shop_rebate'])?$_POST['shop_rebate']:'';//下级开店返利
            $term_validity = isset($_POST['term_validity'])?$_POST['term_validity']:'';//有效期
            $validity = isset($_POST['term_validity'])?$_POST['validity']:'';//有效期
            $goods_id = isset($_POST['goods_id'])?$_POST['goods_id']:'';//指定商品id
            $retval=$ShopKeeper->updateShopKeeperLevel($id,$level_name, $profit1, $profit2, $profit3,$selfpurchase_rebate,$shop_rebate,$term_validity,$validity,$weight,$goods_id);
            if($retval){
                $this->addUserLog('修改店主等级', $id);
            }
            return AjaxReturn($retval);
        }
            /**
             * 修改店主等级
             */
            public function updateLevel(){
                $ShopKeeper=new MicroShopService();
                $uid = isset($_POST['uid'])?$_POST['uid']:'';
                $level_id = isset($_POST['level_id'])?$_POST['level_id']:'';//等级id
                $retval=$ShopKeeper->updateLevel($uid,$level_id);
                if($retval){
                    $this->addUserLog('修改店主等级', $uid);
                }
                return AjaxReturn($retval);
            }
        /**
         * 删除 店主等级
         */
        public function deleteShopKeeperLevel()
        {
            $ShopKeeper = new MicroShopService();
            $id = request()->post("id", "");
            $res = $ShopKeeper->deleteShopKeeperLevel($id);
            if($res){
                $this->addUserLog('删除 店主等级', $id);
            }
            return AjaxReturn($res);
        }
        /**
         * 微店订单概况
         */
        public function microShopOrderProfit()
            {
                $website_id = isset($_POST['website_id'])?$_POST['website_id']:$this->website_id;
                $order_ShopKeeper = new MicroShopService();
                list($start, $end) = Time::dayToNow(6,true);
                $orderType = ['订单金额','订单收益'];
                $data = array();
                $data['ordertype'] = $orderType;
                for($i=0;$i<count($orderType);$i++){
                    switch ($orderType[$i]) {
                        case '订单金额':
                            $status = 1;
                            break;
                        case '订单收益':
                            $status = 2;
                            break;
                    }
                    for($j=0;$j<($end+1-$start)/86400;$j++){
                        $data['day'][$j]= date("Y-m-d",$start+86400*$j);
                        $date_start =  strtotime(date("Y-m-d H:i:s",$start+86400*$j));
                        $date_end =  strtotime(date("Y-m-d H:i:s",$start+86400*($j+1)));
                        if($status ==1){
                            $count = $order_ShopKeeper->getOrderMoneySum(['order_status'=>['between',[1,4]],'website_id'=>$website_id,'create_time'=>['between',[$date_start,$date_end]]]);
                        }
                        if($status == 2){
                            $count = $order_ShopKeeper->getPayMoneySum(['order_status'=>['between',[1,4]],'website_id'=>$website_id,'create_time'=>['between',[$date_start,$date_end]]]);
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
             * 微店概况
             */
            public function microShopProfit()
            {
                $website_id = isset($_POST['website_id'])?$_POST['website_id']:$this->website_id;
                $agent_level = new VslMicroShopLevelModel();
                $level_info = $agent_level->getInfo(['website_id' => $website_id,'is_default'=>1],'*');
                    if($level_info){
                    }else{
                        $data = array(
                            'level_name' => '默认店主等级',
                            'is_default'=>1,
                            'profit1'=>0,
                            'profit2'=>0,
                            'profit3'=>0,
                            'weight' => 1,
                            'create_time' => time(),
                            'website_id' => $website_id
                        );
                        $agent_level->save($data);
                    }
                $microshop = new MicroShopService();
                $data = $microshop->getMicroShopCount($website_id);
                return $data;
            }


        /**
         * 基本设置
         */
        public function basicMicroShopSetting()
        {
            $config= new MicroShopService();
            if (request()->isPost()) {
                // 基本设置
                $microshop_status = request()->post('microshop_status', ''); // 是否开启微店
                $microshop_pattern = request()->post('microshop_pattern', ''); // 微店模式
                $shopKeeper_check = request()->post('shopKeeper_check', ''); // 是否开启自动成为店主
                $goods_id = request()->post('goods_id', ''); // 指定商品
                $pro_types = request()->post('pro_types', '0'); // 指定商品
                $retval = $config->setMicroshopSite($microshop_status,$microshop_pattern, $shopKeeper_check, $goods_id,$pro_types);
                if($retval){
                    $this->addUserLog('保存店主基本设置', $retval);
                }
                setAddons('microshop', $this->website_id, $this->instance_id);
                setAddons('microshop', $this->website_id, $this->instance_id, true);
                return AjaxReturn($retval);
            }
        }

        /**
         * 结算设置
         */
        public function settlementMicroShopSetting()
        {
            $config= new MicroShopService();
            if (request()->isPost()) {
                // 结算设置
                $withdrawals_type = request()->post('withdrawals_type', ''); // 提现方式
                $make_money = request()->post('make_money', ''); // 打款方式
                $profit_calculation = request()->post('profit_calculation', ''); // 收益计算节点
                $profit_arrival = request()->post('profit_arrival', ''); // 收益到账节点
                $withdrawals_check = request()->post('withdrawals_check', ''); // 收益提现免审核
                $withdrawals_min = request()->post('withdrawals_min', ''); // 收益最低提现金额
                $withdrawals_cash  = request()->post('withdrawals_cash', ''); // 收益免审核提现金额
                $withdrawals_begin = request()->post('withdrawals_begin', ''); // 收益提现免所得税区间
                $withdrawals_end = request()->post('withdrawals_end', '');//收益提现免所得税区间
                $poundage = request()->post('poundage', ''); // 收益提现所得税
                $retval = $config->setMicroShopSettlementSite($withdrawals_type,$make_money, $profit_calculation, $profit_arrival,$withdrawals_check, $withdrawals_min , $withdrawals_cash, $withdrawals_begin, $withdrawals_end, $poundage);
                if($retval){
                    $this->addUserLog('店主结算设置', $retval);
                }
                return AjaxReturn($retval);
            }
        }
        /**
         * 申请协议
         */
        public function applicationMicroShopAgreement()
        {
            $config= new MicroShopService();
            if (request()->isPost()) {
                // 基本设置
                $content = request()->post('content', ''); // 协议内容
                $retval = $config->setMicroShopAgreementSite($content);
                if($retval){
                    $this->addUserLog('店主申请协议', $retval);
                }
                return AjaxReturn($retval);
            }
        }
            public function getWithdrawCount()
            {
                $order = new MicroShopService();
                $order_count_array = array();
                $order_count_array['countall'] = $order->getWithdrawalCount(['website_id' => $this->website_id]);
                $order_count_array['waitcheck'] = $order->getWithdrawalCount(['status' => 1, 'website_id' => $this->website_id]);
                $order_count_array['waitmake'] = $order->getWithdrawalCount(['status' => 2, 'website_id' => $this->website_id]);
                $order_count_array['make'] = $order->getWithdrawalCount(['status' => 3, 'website_id' => $this->website_id]);
                $order_count_array['makefail'] = $order->getWithdrawalCount(['status' => 5, 'website_id' => $this->website_id]);
                $order_count_array['nomake'] = $order->getWithdrawalCount(['status' => 4, 'website_id' => $this->website_id]);
                $order_count_array['nocheck'] = $order->getWithdrawalCount(['status' => -1, 'website_id' => $this->website_id]);
                return $order_count_array;
            }
            /**
             * 收益提现列表
             */
            public function profitWithdrawList(){
                $page_index = request()->post("page_index",1);
                $withdraw_no = request()->post('withdraw_no','');
                $status = request()->post('status','');
                $website_id = request()->post('website_id',$this->website_id);
                $profit = new MicroShopService();
                $condition=array('nmar.website_id'=>$website_id);
                $search_text = request()->post('search_text','');
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
                $list = $profit->getprofitWithdrawList($page_index,PAGESIZE,$condition, 'ask_for_date desc');
                return $list;
            }
            /**
             * 收益提现列表导出
             */
            public function profitWithdrawListDataExcel()
            {
                $xlsName = "收益提现流水列表";
                $xlsCell = array(
                    array(
                        'withdraw_no',
                        '提现流水号'
                    ),
                    array(
                        'user_info',
                        '店主名'
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
                $status = request()->get('status','');
                $withdraw_no = request()->get('withdraw_no','');
                $profit = new MicroShopService();
                $condition=array('nmar.website_id'=>isset($_GET['website_id'])?$_GET['website_id']:$this->website_id);
                $search_text = request()->get('search_text','');
                if($search_text){
                    $condition['su.nick_name | su.user_name | su.user_tel'] = [
                        'like',
                        '%' . $search_text . '%'
                    ];
                }
                if($status != '' && $status!=9){
                    $condition['nmar.status'] = $status;
                }
                if($withdraw_no != ''){
                    $condition['nmar.withdraw_no'] = $withdraw_no;
                }
                if(empty($_GET['start_date'])){
                    $start_date = strtotime('2018-1-1');
                }else{
                    $start_date = strtotime($_GET['start_date']);
                }
                if(empty($_GET['end_date'])){
                    $end_date = strtotime('2038-1-1');
                }else{
                    $end_date = strtotime($_GET['end_date']);
                }
                $condition["nmar.ask_for_date"] = [[">",$start_date],["<",$end_date]];

                $list = $profit->getprofitWithdrawList(1,0,$condition, 'ask_for_date desc');
                foreach ($list['data'] as $k=>$v){
                    if($v['type']==1 || $v['type']==5){
                        $v['type']= '银行卡';
                    }elseif ($v['type']==2){
                        $v['type']= '微信';
                    }elseif ($v['type']==3){
                        $v['type']= '支付宝';
                    }elseif ($v['type']==4){
                        $v['type']= '账户余额';
                    }
                    if($v['account_number']==-1){
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
             * 收益提现表单页
             */

            public function profitShow(){
                $user = new usermodel();
                $user_info= $user->getInfo(['uid'=>$this->uid],'payment_password,wx_openid');
                $profit = new MicroShopService();
                $my_profit = $profit->getProfitWithdrawConfig($this->uid);
                $data = array();
                if($my_profit){
                    $data['data'] = $my_profit;
                }
                //可提现佣金
                if($my_profit['profit']){
                    $data['data']['profit'] = $my_profit['profit'];
                }else{
                    $data['data']['profit'] = '0.00';
                }
                //设置密码
                if(empty($user_info['payment_password'])){
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
            /**
             * 收益提现详情
             */
            public function profitWithdrawInfo(){
                $profit = new MicroShopService();
                $id = $_GET['id'];
                $retval = $profit->profitWithdrawDetail($id);
                return $retval;
            }
            /**
             * 收益提现审核
             */
            public function profitWithdrawAudit(){
                $profit = new MicroShopService();
                $id = $_POST['id'];
                $status = $_POST['status'];
                $memo = $_POST['memo'];
                $ids = explode(',',$id);
                if(count($ids)>1) {
                    foreach ($ids as $v) {
                        $retval = $profit->profitWithdrawAudit($v, $status,$memo);
                    }
                }else{
                    $retval = $profit->profitWithdrawAudit($id, $status,$memo);
                }
                if($retval==-9000){
                    $balance = new VslMicroShopProfitWithdrawModel();
                    $msg = $balance->getInfo(['id'=>$id],'memo')['memo'];
                }else if($retval>0){
                    $msg = '打款成功';
                }else{
                    $msg = '打款失败';
                }
                if($retval){
                    $this->addUserLog('收益提现审核', $id);
                }
                return AjaxReturn($retval,$msg);
            }
            /**
             * 收益流水
             */
            public function profitRecordsList()
            {
                if (request()->isAjax()) {
                    $profit = new MicroShopService();
                    $page_index = request()->post("page_index",1);
                    $page_size = request()->post('page_size', PAGESIZE);
                    $search_text = request()->post('search_text', '');
                    $records_no = request()->post('records_no','');
                    $from_type = request()->post('from_type','');
                    $start_date = request()->post('start_date') == "" ? '2010-1-1' : request()->post('start_date');
                    $end_date = request()->post('end_date') == "" ? '2038-1-1' : request()->post('end_date');
                    $condition['nmar.website_id'] = request()->post('website_id', $this->website_id);
                    $condition['su.nick_name|su.user_tel|su.user_name'] = [
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
                        $condition['nmar.from_type'] = ['>',3];
                    }elseif($from_type!=''){
                        $condition['nmar.from_type'] = $from_type;
                    }
                    if($records_no != ''){
                        $condition['nmar.records_no'] = $records_no;
                    }
                    $condition['nmar.profit'] = ['neq',0];
                    $list = $profit->getAccountList($page_index, $page_size, $condition, $order = '', $field = '*');
                    return $list;
                }
            }
            /**
             * 收益流水详情
             */
            public function profitInfo()
            {
                $profit = new MicroShopService();
                $id = request()->get('id');
                $condition['nmar.id'] = $id;
                $list = $profit->getAccountList(1,0, $condition, $order = '', $field = '*');
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["profit"] = '¥'.$list['data'][$k]["profit"];
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
                        $list['data'][$k]["from_type"] = '下级开店返利';
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
             * 收益流水数据excel导出
             */
            public function profitRecordsDataExcel()
            {
                $xlsName = "收益流水列表";
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
                        'user_name',
                        '用户名'
                    ),
                    array(
                        'from_type',
                        '类别'
                    ),
                    array(
                        'profit',
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
                $profit = new MicroShopService();
                $search_text = request()->get('search_text', '');
                $from_type = request()->get('from_type','');
                $records_no = request()->get('records_no','');
                $start_date = request()->get('start_date') == "" ? '2010-1-1' : request()->get('start_date');
                $end_date = request()->get('end_date') == "" ? '2038-1-1' : request()->get('end_date');
                $condition['nmar.website_id'] = request()->get('website_id', $this->website_id);
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
                $list = $profit->getAccountList(1,0, $condition, $order = '', $field = '*');
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["profit"] = '¥'.$list['data'][$k]["profit"];
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
                        $list['data'][$k]["from_type"] = '下级开店返利';
                    }
                }

                $this->addUserLog('收益流水数据excel导出', 1);
                dataExcel($xlsName, $xlsCell, $list['data']);
            }

            /**
             * 前台微店提现详情
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
                $profit = new MicroShopService();
                $list = $profit->withdrawDetail($page_index, $page_size,$condition,'');
                return json($list);
            }
            /**
             * 前台微店收益明细
             */
            public function profitDetail($params=array()){
                $page_index = request()->post('page_index', 1);
                $page_size = request()->post('page_size', PAGESIZE);
                $condition['nmar.uid'] = $this->uid;
                $profit = new MicroShopService();
                $list = $profit->getAccountLists($page_index, $page_size,$condition,'');
                $data['code'] = 0;
                $data['data'] = $list;
                $data['data']['page_index'] = $page_index;
                $data['data']['page_size'] = $page_size;
                return json($data);
            }
            /**
             * 前台收益明细详情
             */
            public function profitRecordDetail($params=array()){
                $id = request()->post('id', '');
                $commission = new MicroShopService();
                $list = $commission->getAccountLists(1, 0,['nmar.id'=>$id],'');
                $data['code'] = 0;
                $data['data'] = $list['data'][0];
                return json($data);
            }
            /**
             * 前台收益提现
             */
            public function profitWithdraw($params=array()){
                $uid = $this->uid;
                $withdraw_no = 'PW'.time() . rand(111, 999);
                $account_id = request()->post('account_id', '');
                $cash = request()->post('cash', '');
                $ShopKeeper = new MicroShopService();
                $retval = $ShopKeeper->addMicroShopProfitWithdraw($withdraw_no,$uid,$account_id,$cash);
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
             * 收益提现表单页
             */

            public function profitWithdraw_show($params=array()){
                $user = new usermodel();
                $uid = $this->uid;
                $condition['uid'] = $this->uid;

                $user_password = $user->getInfo($condition,'payment_password');

                $profit = new MicroShopService();
                $my_profit = $profit->getprofitWithdrawConfig($uid);
                $data = array();
                //可提现收益
                if($my_profit['profit']){
                    $data['data']['profit'] = $my_profit['profit'];
                }else{
                    $data['data']['profit'] = '0.00';
                }
                //设置密码
                if(empty($user_password['payment_password'])){
                    $data['data']['set_password'] = 1;
                }else{
                    $data['data']['set_password'] = 0;
                }
                $data['code'] = 0;
                return json($data);
            }

            /*-----------------------------------------------------------------接口------------------------------------*/
            /**
             * 前台微店中心
             */
            public function microShopCenter(){
                $params['uid'] = request()->post('shopkeeper_id', '');
                if(!$params['uid']){
                    $params['uid'] = $this->uid;
                }
                $member = new MicroShopService();
                $member_info = $member->getShopKeeperInfo($params['uid']);
                $data['code'] = 0;
                $data['data'] = $member_info;
                return json($data);

            }
            /**
             * 前台微店等级中心
             */
            public function microShopLevelCenter(){
                $params['uid'] = $this->uid;
                $member = new MicroShopService();
                $member_info = $member->getShopKeeperLevelLists('',1,0,['website_id'=>$this->website_id,'is_default'=>0]);
                $data['code'] = 0;
                $data['data'] = $member_info;
                return json($data);
            }
            /**
             * 前台微店等级中心立即续费
             */
            public function immediateRenewal(){
                $params['uid'] = $this->uid;
                $member = new MicroShopService();
                $member_info = $member->getShopKeeperLevelInfos($params['uid']);
                $data['code'] = 0;
                $data['data'] = $member_info;
                return json($data);
            }
            /**
             * 前台微店等级中心提升等级
             */
            public function upgradeLevel(){
                $uid = $this->uid;
                $member = new MicroShopService();
                $member_info = $member->getShopKeeperLevelLists($uid,1,0,['website_id'=>$this->website_id,'is_default'=>0]);
                $data['code'] = 0;
                $data['data'] = $member_info;
                return json($data);
            }
            /**
             * 前台微店管理
             */
            public function microShopSet(){
                $uid = $this->uid;
                $microshop_logo = request()->post('microshop_logo', '');
                $shopRecruitment_logo = request()->post('shopRecruitment_logo', '');
                $microshop_name = request()->post('microshop_name', '');
                $microshop_introduce = request()->post('microshop_introduce', '');
                $member = new MicroShopService();
                $retval = $member->addMicroShopSet($uid,$microshop_logo,$shopRecruitment_logo,$microshop_name,$microshop_introduce);
                if($retval>0){
                    $data['code'] = 0;
                    $data['message'] = "提交成功";
                }else{
                    $data['code'] = -1;
                    $data['message'] = "提交失败";
                }
                return json($data);
            }
            /**
             * 挑选商品
             */
            public function selectGoods(){
                $uid = $this->uid;
                $goods_id = request()->post('goods_id', '');
                $member = new MicroShopService();
                $retval = $member->addGoodsId($uid,$goods_id);
                if($retval>0){
                    $data['code'] = 0;
                    $data['message'] = "提交成功";
                }elseif($retval==-2){
                    $data['code'] = -2;
                    $data['message'] = "该商品已被选择";
                }else{
                    $data['code'] = -1;
                    $data['message'] = "提交失败";
                }
                return json($data);
            }
            /**
             * 取消商品
             */
            public function delGoods(){
                $uid = $this->uid;
                $goods_id = request()->post('goods_id', '');
                $member = new MicroShopService();
                $retval = $member->delGoodsId($uid,$goods_id);
                if($retval>0){
                    $data['code'] = 0;
                    $data['message'] = "取消成功";
                }elseif($retval==-2){
                    $data['code'] = -2;
                    $data['message'] = "该商品不存在";
                }else{
                    $data['code'] = -1;
                    $data['message'] = "取消失败";
                }
                return json($data);
            }
            /**
             * 收益详情
             */
            public function myProfit()
            {
                $profit = new MicroShopService();
                $uid = $this->uid;
                $my_profit = $profit->getprofitWithdrawConfig($uid);
                $data = array();
                //可提现收益
                if($my_profit['profit']){
                    $data['data']['profit'] = $my_profit['profit'];
                }else{
                    $data['data']['profit'] = '0.00';
                }

                //累积收益
                if($my_profit['profit']) {
                    $data['data']['total_money'] = $my_profit['profit'] + $my_profit['withdrawals'] +$my_profit['freezing_profit'];
                }else{
                    $data['data']['total_money'] = '0.00';
                }

                //已提现收益
                if($my_profit['withdrawals']){
                    $data['data']['withdrawals'] = $my_profit['withdrawals'];
                }else{
                    $data['data']['withdrawals'] = '0.00';
                }

                //体现中
                if($my_profit['apply_withdraw'] || $my_profit['make_withdraw']){
                    $data['data']['apply_withdraw'] = $my_profit['apply_withdraw'] + $my_profit['make_withdraw'];
                }else{
                    $data['data']['apply_withdraw'] = '0.00';
                }

                //冻结中
                if($my_profit['freezing_profit']){
                    $data['data']['freezing_profit'] = $my_profit['freezing_profit'];
                }else{
                    $data['data']['freezing_profit'] = '0.00';
                }

                $data['code'] = 0;
                return json($data);
            }
            /**
             * 预览微店
             */
            public function previewMicroShop(){
                $uid = request()->post('shopkeeper_id', '');
                if(!$uid){
                    $uid = $this->uid;
                }
                $member = new MicroShopService();
                $list = $member->myGoodsList($uid);
                $goods_list = [];
                $is_seckill = getAddons('seckill', $this->website_id);
                if($is_seckill){
                    $seckill_server = new Seckill();
                }
                if($list){
                    foreach ($list['data'] as $k => $v) {
                        if($is_seckill){
                            //判断如果是秒杀商品，则取最低秒杀价
                            $goods_id = $v['goods_id'];
                            $seckill_condition['nsg.goods_id'] = $goods_id;
                            $is_seckill = $seckill_server->isSkuStartSeckill($seckill_condition);
                        }
                        if($is_seckill){
                            $v['goods_price'] = $is_seckill['seckill_price'];
                        }
                        $goods_list[$k]['goods_id'] = $v['goods_id'];
                        $goods_list[$k]['goods_name'] = $v['goods_name'];
                        $goods_list[$k]['price'] = $v['goods_price'];
                        $goods_list[$k]['market_price'] = $v['market_price'];
                        $goods_list[$k]['sales'] = $v['sales'];
                        $goods_list[$k]['logo'] = $v['pic_cover'] ? getApiSrc($v['pic_cover']) : '';
                    }
                }else{
                    $goods_list = [];
                }
                return json(['code' => 1, 'message' => '获取成功',
                    'data' => [
                        'goods_list' => $goods_list,
                        'page_count' => $list['page_count'],
                        'total_count' => $list['total_count']
                    ]
                ]);
            }
            /**
             * 我的微店
             */
            public function myMicroShop(){
                $uid = request()->post('shopkeeper_id', '');
                if(!$uid){
                    $uid = $this->uid;
                }
                $member = new MicroShopService();
                $list = $member->myGoodsList($uid);
                $goods_list = [];
                $is_seckill = getAddons('seckill', $this->website_id);
                if($is_seckill){
                    $seckill_server = new Seckill();
                }
                foreach ($list['data'] as $k => $v) {
                    if($is_seckill){
                        //判断如果是秒杀商品，则取最低秒杀价
                        $goods_id = $v['goods_id'];
                        $seckill_condition['nsg.goods_id'] = $goods_id;
                        $is_seckill = $seckill_server->isSkuStartSeckill($seckill_condition);
                    }
                    if($is_seckill){
                        $v['goods_price'] = $is_seckill['seckill_price'];
                    }
                    $goods_list[$k]['goods_id'] = $v['goods_id'];
                    $goods_list[$k]['goods_name'] = $v['goods_name'];
                    $goods_list[$k]['price'] = $v['goods_price'];
                    $goods_list[$k]['market_price'] = $v['market_price'];
                    $goods_list[$k]['sales'] = $v['sales'];
                    $goods_list[$k]['logo'] = $v['pic_cover'] ? getApiSrc($v['pic_cover']) : '';
                }
                return json(['code' => 1, 'message' => '获取成功',
                    'data' => [
                        'goods_list' => $goods_list,
                        'page_count' => $list['page_count'],
                        'total_count' => $list['total_count']
                    ]
                ]);
            }
            /**
             * 前台预览微店商品分类列表
             */
            public function previewMicroShopGoods(){
                $uid = request()->post('shopkeeper_id', '');
                if(!$uid){
                    $uid = $this->uid;
                }
                $member = new MicroShopService();
                $condition['level'] = 3;
                $my_category = $member->myCategoryList($uid,$condition);
                return json(['code' => 1, 'message' => '获取成功', 'data' => $my_category]);
            }
            /**
             * 预览微店获取分类
             */
            public function previewMicroShopCategory(){
                $uid = request()->post('shopkeeper_id', '');
                if(!$uid){
                    $uid = $this->uid;
                }
                $member = new MicroShopService();
                $my_category = $member->myCategoryList($uid);
                return json(['code' => 1, 'message' => '获取成功', 'data' => $my_category]);
            }
}
