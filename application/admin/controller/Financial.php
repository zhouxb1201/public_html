<?php

namespace app\admin\controller;

use addons\shop\model\VslShopModel;
use addons\shop\service\Shop;
use data\model\UserModel;
use data\model\VslBankModel;
use data\service\Member;
use data\service\User;
use data\service\Config as WebConfig;
use addons\distribution\service\Distributor as  DistributorService;

/**
 * 账户控制器
 */
class Financial extends BaseController {

    public function __construct() {

        parent::__construct();
    }

    /**
     * 每日账户收益
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:>|Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function getShopAccountMonthRecored() {
        $shop = new Shop();
        $shop_account_month_recored = $shop->getShopAccountMonthRecored($this->instance_id);
        return $shop_account_month_recored;
    }

    /**
     * 账户列表
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:>|Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function shopAccountList() {
        if (request()->isAjax()) {
            $pageindex = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $condition['shop_id'] = $this->instance_id;
            $condition['website_id'] = $this->website_id;
            $shop = new Shop();
            $list = $shop->getShopBankAccountAll($pageindex, PAGESIZE, $condition, 'is_default desc');
            return $list;
        } else {
            return view($this->style . "Financial/reflectAccount");
        }
    }

    /**
     * 添加银行账户
     */
    public function addShopAccount() {
        if (request()->isAjax()) {
            $type = request()->post('type', 1);
            $realname = request()->post('realname', '');
            $account_number = request()->post('account_number', '');
            $remark = request()->post('remark', '');
            $bank_name = request()->post('bank_name', '');
            $bank_type = request()->post('bank_type', '');
            $bank_card = request()->post('bank_card', '');
            $shop = new Shop();
            $retval = $shop->addShopBankAccount($this->instance_id, $type, $realname, $account_number, $remark,$bank_name,$bank_type,$bank_card);
            return ajaxReturn($retval);
        } else {
            $config_service = new WebConfig();
            $list = $config_service->getBalanceWithdrawConfig(0);
            if($list['value']['withdraw_message']){
                $withdraw_message = explode(',',$list['value']['withdraw_message']);
                if(in_array(1,$withdraw_message)){
                    $this->assign('bank_type',1);
                }
                if(in_array(2,$withdraw_message)){
                    $this->assign('wx_type',2);
                }
                if(in_array(3,$withdraw_message)){
                    $this->assign('ali_type',3);
                }
                if(in_array(4,$withdraw_message)){
                    $this->assign('bank_type',4);
                }
            }
            $bank = new VslBankModel();
            $bank_list = $bank->getQuery([],'*','');
            $this->assign('bank_list',$bank_list);
            return view($this->style . 'Financial/addReflectAccount');
        }
    }

    /**
     * 修改银行账户
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function updateShopAccount() {
        $shop = new Shop();
        if (request()->isAjax()) {
            $id = $_POST["id"];
            $type = $_POST["type"];
            $realname = $_POST["realname"];
            $account_number = $_POST["account_number"];
            $remark = $_POST["remark"];
            $bank_name = request()->post('bank_name', '');
            $bank_type = request()->post('bank_type', '');
            $bank_card = request()->post('bank_card', '');
            $retval = $shop->updateShopBankAccount($this->instance_id, $type, $realname, $account_number, $remark, $bank_name,$bank_type,$bank_card,$id);
            return ajaxReturn($retval);
        } else {
            $id = isset($_GET['id']) ? $_GET['id'] : 0;
            $info = $shop->getShopBankAccountDetail($this->instance_id, $id);
            $this->assign('info', $info);
            $config_service = new WebConfig();
            $list = $config_service->getBalanceWithdrawConfig(0);
            if($list['value']['withdraw_message']){
                $withdraw_message = explode(',',$list['value']['withdraw_message']);
                if(in_array(1,$withdraw_message)){
                    $this->assign('bank_type',1);
                }
                if(in_array(2,$withdraw_message)){
                    $this->assign('wx_type',2);
                }
                if(in_array(3,$withdraw_message)){
                    $this->assign('ali_type',3);
                }
                if(in_array(4,$withdraw_message)){
                    $this->assign('bank_type',4);
                }
            }
            $bank = new VslBankModel();
            $bank_list = $bank->getQuery([],'*','');
            $this->assign('bank_list',$bank_list);
            return view($this->style . 'Financial/updateReflectAccount');
        }
    }


    /**
     * 删除账户
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function deleteAccount() {
        $condition["id"] = $_POST["id"];
        $shop = new Shop();
        $retval = $shop->deleteShopBankAccouht($condition);
        return ajaxReturn($retval);
    }
    public function selectMemberList() {
        if(request()->isPost()){
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            if (isset($_POST['search_text'])) {
                $search_text = $_POST['search_text'];
                if(is_numeric($search_text)){
                    $condition['user_tel'] = $search_text;
                }else{
                    $condition['real_name'] = $search_text;
                }
            }
            $condition['website_id'] = $this->website_id;
            $condition['is_member'] = 1;
            $condition['wx_openid'] = ['neq',''];
            $shop = new Member();
            $retval = $shop->getUserLists($page_index,PAGESIZE, $condition, '');
            return $retval;
        }else{
            return view($this->style . "Financial/selectMember");
        }

    }
    /**
     * 店铺申请提现
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function applyShopAccountWithdraw() {
        $shop = new Shop();
        if (request()->isAjax()) {
            $cash = $_POST["cash"];
            $bank_account_id = $_POST["bank_account_id"];
            $retval = $shop->applyShopAccountWithdraw($this->instance_id, $bank_account_id, $cash);
            return ajaxReturn($retval);
        } else {
            $condition['shop_id'] = $this->instance_id;
            $list = $shop->getShopBankAccountAll(1, 0, $condition);
            $list = $list['data'];
            $shop_account_info = $shop->getShopAccount($this->instance_id);
            $Config = new WebConfig();
            $witndraw_type = $Config->getConfig(0, 'WITHDRAW_BALANCE');
            $witndraw_type['value'] = json_decode($witndraw_type['value'], true);
            if($witndraw_type['value']['withdraw_message']){
                $withdraw_message = explode(',',$witndraw_type['value']['withdraw_message']);
                if(in_array(1,$withdraw_message)){
                    $this->assign('bank_type',1);
                }
                if(in_array(2,$withdraw_message)){
                    $this->assign('wx_type',2);
                }
                if(in_array(3,$withdraw_message)){
                    $this->assign('ali_type',3);
                }
                if(in_array(4,$withdraw_message)){
                    $this->assign('bank_type',4);
                }
            }
            $shop = new VslShopModel();
            $rate = $shop->getInfo(['shop_id'=>$this->instance_id],'shop_platform_commission_rate')['shop_platform_commission_rate'];
            $this->assign("shop_rate", $rate);
            $this->assign("is_use", $witndraw_type['is_use']);
            $this->assign("poundage", $witndraw_type['value']['withdraw_poundage']);
            $this->assign("withdraw_cash_min", $witndraw_type['value']['withdraw_cash_min']);
            $this->assign("shop_account_info", $shop_account_info);
            $this->assign("bank_list", $list);
            return view($this->style . "Financial/reflect");
        }
    }
/**

     * 佣金流水

     */
    public function shopdistriaccount(){
        
        $this->instance_id;
        $urls = __URL(addons_url_platform('distribution://distribution/commissionRecordsDataExcel'));
        $this->assign('commissionRecordsDataExcelUrl', __URL(addons_url_platform('distribution://distribution/commissionRecordsDataExcel')));
        $this->assign('shop_id', $this->instance_id);
        if(request()->isAjax()){
            $commission = new distributorService();
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
        return view($this->style . "Financial/shopdistriaccount");
    }
    /**

     * 佣金流水列表数据

     */
    public function shopdistriaccountlist(){

        $shop_id = $this->instance_id;
        $this->assign('shop_id', $this->instance_id);
        if (request()->isAjax()) {

            $commission = new distributorService();

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

                $condition['nmar.from_type'] = [['>',3],['<',22]];

            }elseif($from_type!=''){

                $condition['nmar.from_type'] = $from_type;

            }

            if($records_no != ''){

                $condition['nmar.records_no'] = $records_no;

            }
            $condition['nmar.shop_id'] = $shop_id;
            $list = $commission->getAccountList($page_index, $page_size, $condition, $order = '', $field = '*');

            return $list;

        }
    }
    /**
     * 店铺账务明细
     *
     * @return Ambigous <multitype:number unknown , unknown>|Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function ShopAccountRecordCount() {
        if (request()->isAjax()) {
            $pageindex = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $condition['shop_id'] = $this->instance_id;
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : '2010-1-1';
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : '2030-1-1';
            // $account_type = !empty($_POST['account_type']) ? $_POST['account_type'] : 3;
            // if($account_type !=3 ){
            // $condition["account_type"] = $account_type;
            // }
            if ($start_date != "") {
                $condition["create_time"][] = [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ];
            }
            if ($end_date != "") {
                $condition["create_time"][] = [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ];
            }
            $shop = new Shop();
            $count = $shop->getShopAccountRecordCount($start_date, $end_date, $this->instance_id);
            $list = $shop->getShopAccountRecordsList($pageindex, PAGESIZE, $condition, 'create_time desc');
            return [
                "list" => $list,
                "count" => $count
            ];
        }
    }

    /**
     * 店铺销售订单
     *
     * @return multitype:unknown Ambigous <multitype:number unknown , unknown> |Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function shopOrderAccountList() {
        if (request()->isAjax()) {
            $pageindex = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : '';
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : '';
            $condition = array();
            if ($start_date != "") {
                $condition["create_time"][] = [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ];
                $count_condition["create_time"][] = [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ];
            }
            if ($end_date != "") {
                $condition["create_time"][] = [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ];
                $count_condition["create_time"][] = [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ];
            }

            $condition["shop_id"] = $this->instance_id;
            $count_condition["shop_id"] = $this->instance_id;
            $shop = new \data\service\Order();
            $list = $shop->getOrderList($pageindex, PAGESIZE, $condition, 'create_time desc');
            return [
                "list" => $list
            ];
        }
    }

    /**
     * 店铺收入
     */
    public function shopAccount() {
        $shop = new Shop();
        // 得到店铺的账户情况
        $shop_account_info = $shop->getShopAccount($this->instance_id);
        // 得到店铺的详细情况
        $shop_info = $shop->getShopDetail($this->instance_id);
        $shop_logo = $shop_info["base_info"]["shop_logo"];
        $this->assign("shop_name", $this->instance_name);
        $this->assign("shop_logo", $shop_logo);
        $this->assign("shop_account_info", $shop_account_info);
        return view($this->style . "Financial/shopIncome");
    }

    /**
     * 余额明细
     */
    public function getShopOrderAccountPage() {
        $pageindex = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
        $count_condition["nsoar.shop_id"] = $this->instance_id;
        $condition["nsoar.id"] = [
            ">",
            0
        ];
        $shop = new Shop();
        $list = $shop->getshopOrderAccountRecordsList($pageindex, PAGESIZE, $condition, 'nsoar.create_time desc');

        return $list;
    }

    /**
     * 店铺提现列表
     *
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function shopAccountWithdrawList() {
        if (request()->isAjax()) {
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : '';
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : '';
            $condition['sp.shop_id'] = $this->instance_id;
            if ($start_date || $end_date) {
                $condition["ask_for_date"] = [
                    [
                        ">",
                        getTimeTurnTimeStamp($start_date)
                    ],
                    [
                        "<",
                        getTimeTurnTimeStamp($end_date)
                    ]
                ];
            }
            $shop_name = '';
            $shop = new Shop();
            $list = $shop->getShopAccountWithdrawList($page_index, PAGESIZE, $condition, $shop_name, 'ask_for_date desc');
            return $list;
        }
    }

    /**
     * 店铺提现详情
     *
     * 
     */
    public function shopAccountWithdrawDetail() {
        $id = request()->post('id', 0);
        $shop = new Shop();
        $retval = $shop->shopAccountWithdrawDetail($id);
        return $retval;
    }

}
