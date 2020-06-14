<?php
namespace app\platform\controller;
use addons\distribution\service\Distributor;
use data\model\UserModel;
use data\model\VslMemberBalanceWithdrawModel;
use data\model\VslMemberGroupModel;
use data\model\VslMemberLevelModel;
use data\model\VslMemberModel;
use data\service\Member as MemberService;
use data\service\Address;
use data\service\Config;
use data\service\User;

/**
 * 会员管理
 *
 * @author  www.vslai.com
 *        
 */
class Member extends BaseController
{

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 会员列表主页
     */
    public function memberList()
    {
        $member = new MemberService();
        if (request()->isAjax()) {
            $page_index = request()->post('page_index',1);
            $page_size = request()->post('page_size',PAGESIZE);
            $member_id = request()->post('member_id', '');
            $user_group = request()->post('user_group', '');
            $member_status = request()->post('member_status', '');
            $search_text = request()->post('search_text', '');
            $start_create_date = request()->post('start_create_date') == "" ? '2018-1-1' : request()->post('start_create_date');
            $end_create_date = request()->post('end_create_date') == "" ? '2038-1-1' : request()->post('end_create_date');
            $level_id = request()->post('member_level', '');
            if($member_id){
                $condition['su.uid'] = $member_id;
            }
            if($user_group){
                $condition['CONCAT(",",nm.group_id,",")'] = [['=', $user_group], ['like', '%,'.$user_group.',%'], 'or'];
            }
            if($member_status!='' && $member_status!='undefined'){
                $condition['su.user_status'] = $member_status;
            }
            $condition["su.reg_time"] = [
                    [
                        ">",
                        strtotime($start_create_date)
                    ],
                    [
                        "<",
                        strtotime($end_create_date)
                    ]
                ];
            $condition['su.is_member'] = 1;
            if($search_text){
                $condition['su.nick_name|su.user_tel|su.user_name'] = [
                    'like',
                    '%' . $search_text . '%'
                ];
            }
            if ($level_id) {
                $condition['nml.level_id'] = $level_id;
            }
            $condition['su.website_id'] = $this->website_id;
            $list = $member->getMemberList($page_index, $page_size, $condition, 'su.reg_time desc');
            return $list;
        } else {
            //是否开启积分
            $web_config = new Config();
            //是否开启购物返积分
            $isPoint = $web_config->getConfig(0,'IS_POINT')['value'];
            $this->assign("isPoint", $isPoint);
            $group_id = request()->get('group_id', '');
            // 查询会员等级
            $list = $member->getMemberLevelList(1, 0,["website_id" => $this->website_id]);
            // 查询会员标签
            $list_label = $member->getMemberGroupList(1, 0,["website_id" => $this->website_id],'');
            //会员总数
            $user_count_num = $member->getMemberCount(array("website_id" => $this->website_id));
            //会员总余额
            $user_balance_num = $member->getMemberBalanceCount('');
            //会员总积分
            $user_point_num = $member->getMemberPointCount();
            //会员黑名单
            $user_black_num = $member->getUserCount(["website_id" => $this->website_id,'is_member'=>1,'user_status'=>0]);
            $this->assign('member_group_id', $group_id);
            $this->assign('level_list', $list);
            $this->assign('label_list', $list_label);
            $this->assign('user_count_num', $user_count_num);
            $this->assign('user_black_num', $user_black_num);
            $this->assign('user_point_num', $user_point_num);
            $this->assign('user_balance_num', $user_balance_num);
            return view($this->style . 'Member/memberList');
        }
    }
    /**
     * 会员详情
     */
    public function memberDetail()
    {
        $member = new MemberService();
        $member_id = request()->get('member_id');
        $condition['su.uid'] = $member_id;
        $condition['su.website_id'] = $this->website_id;
        $list = $member->getMemberList(1, 0, $condition, '');
        // 查询会员等级
        $list1 = $member->getMemberLevelList(1, 0,['website_id'=>$this->website_id]);
        $this->assign('list',$list['data']);
        $this->assign('level_list', $list1);
        return view($this->style . 'Member/memberDetail');
    }

    /**
     * 会员积分明细
     */
    public function pointDetail()
    {
        $member_id = request()->get('member_id');
        $page_index = request()->get('page_index',1);
        $page_size = request()->get('page_size',PAGESIZE);
        $condition['nmar.uid'] = $member_id;
        $condition['nmar.website_id'] = $this->website_id;
        $condition['nmar.account_type'] = 1;
        $member = new MemberService();
        $list = $member->getPointList($page_index, $page_size, $condition, $order = '', $field = '*');
        return $list;
    }

    /**
     * 会员余额明细
     */
    public function accountDetail()
    {
        $member_id = request()->get('member_id');
        $page_index = request()->get('page_index',1);
        $page_size = request()->get('page_size',PAGESIZE);
        $condition['nmar.uid'] = $member_id;
        $condition['nmar.website_id'] = $this->website_id;
        $condition['nmar.account_type'] = 2;
        $member = new MemberService();
        $list = $member->getAccountList($page_index, $page_size, $condition, $order = '', $field = '*');
        return $list;
    }



    /**
     * 用户锁定
     */
    public function memberLock()
    {
        $uid = isset($_POST["id"]) ? $_POST["id"] : '';
        $retval = 0;
        if (! empty($uid)) {
            $uids = explode(',',$uid);
            foreach ($uids as $v){
                $member = new MemberService();
                $retval = $member->userLock($v);
                if($retval==-2)return ['code' => -2,'message' => '有会员为店铺卖家，不能拉入黑名单。'];
            }
            $this->addUserLogByParam("用户锁定",$retval);
        }
        return AjaxReturn($retval);
    }

    /**
     * 用户解锁
     */
    public function memberUnlock()
    {
        $uid = isset($_POST["id"]) ? $_POST["id"] : '';
        $retval = 0;
        if (! empty($uid)) {
            $uids = explode(',',$uid);
            foreach ($uids as $v) {
                $member = new MemberService();
                $retval = $member->userUnlock($v);
            }
            $this->addUserLogByParam("用户解锁",$retval);
        }
        return AjaxReturn($retval);
    }


    /**
     * 积分、余额调整
     */
    public function addMemberAccount()
    {
        $member = new MemberService();
        $uid = isset($_POST["id"]) ? $_POST["id"] : '';
        $type = isset($_POST["type"]) ? $_POST["type"] : '';
        $num = isset($_POST["num"]) ? $_POST["num"] : '';
        $text = ($_POST["text"]);
        if(empty($text)){
            $text = '后台调整';
        }
        $retval = $member->addMemberAccount($type, $uid, $num, $text);
        $this->addUserLogByParam("积分余额调整",$retval);
        return AjaxReturn($retval);
    }

    /**
     * 会员等级列表
     */
    public function memberLevelList()
    {
        $member = new MemberService();
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $condition['website_id'] = $this->website_id;
            $list = $member->getMemberLevelList($page_index, $page_size,$condition);
            return $list;
        }
        return view($this->style . 'Member/memberLevelList');
    }
    /**
     * 会员等级弹出层
     */
    public function memberLevelLists()
    {
        $member = new MemberService();
        if (request()->isPost()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $condition['website_id'] = $this->website_id;
            $list = $member->getMemberLevelList($page_index, $page_size,$condition);
            return $list;
        }
        return view($this->style . 'Member/memberLevelLists');
    }
    /**
     * 会员等级是否存在
     */
    public function memberInfo()
    {
        $member = new VslMemberLevelModel();
        $level_name = request()->post("level_name", '');
        $condition['website_id'] = $this->website_id;
        $condition['level_name'] = $level_name;
        $list = $member->getInfo($condition,'*');
        if($list){
            return AjaxReturn(1);
        }else{
            return AjaxReturn(-1);
        }
    }
    /**
     * 添加会员等级
     */
    public function addMemberLevel()
    {
        $member = new MemberService();
        if (request()->isAjax()) {
            $level_name = request()->post("level_name", '');
            $goods_discount = request()->post("goods_discount", '');
            $growth_num= request()->post("growth_num", '');
            $is_label= request()->post("is_label", '0');
            $res = $member->addMemberLevel($this->instance_id, $level_name, $growth_num, $goods_discount,$is_label);
            $this->addUserLogByParam("添加会员等级",$res);
            return AjaxReturn($res);
        }
        $member_level = $member->getMemberHeight();
        $this->assign('level_growth_num',implode(',',$member_level));
        return view($this->style . 'Member/addMemberLevel');
    }

    /**
     * 修改会员等级
     */
    public function updateMemberLevel()
    {
        $member = new MemberService();
        if (request()->isAjax()) {
            $level_id = request()->post("level_id", 0);
            $level_name = request()->post("level_name", '');
            $goods_discount = request()->post("goods_discount", '');
            $growth_num= request()->post("growth_num", '');
            $is_label= request()->post("is_label", '0');
            $res = $member->updateMemberLevel($level_id, $this->instance_id,$level_name, $growth_num, $goods_discount,$is_label);
            $this->addUserLogByParam("修改会员等级",$res);
            return AjaxReturn($res);
        }
        $level_id = request()->get("level_id", 0);
        $info = $member->getMemberLevelDetail($level_id);
        $this->assign('info', $info);
        $member_level = $member->getMemberHeight();
        $this->assign('level_growth_num',implode(',',$member_level));
        return view($this->style . 'Member/updateMemberLevel');
    }
    /**
     * 修改 当前会员的等级
     */
    public function adjustMemberLevel()
    {
        $member = new MemberService();
        $level_id = request()->post("level_id", 0);
        $uid = request()->post("uid", 0);
        $ids = explode(',',$uid);
        if(count($ids)>1){
            foreach ($ids as $v) {
                $res = $member->adjustMemberLevel($level_id,$v);
            }
        }else{
            $res = $member->adjustMemberLevel($level_id,$uid);
        }
        $this->addUserLogByParam("修改用户会员等级", $res);
        return AjaxReturn($res);
    }

    /**
     * 删除 会员等级
     */
    public function deleteMemberLevel()
    {
        $member = new MemberService();
        $level_id = request()->post("level_id", 0);
        $res = $member->deleteMemberLevel($level_id);
        $this->addUserLogByParam("删除会员等级",$res);
        return AjaxReturn($res);
    }

    /**
     * 修改 会员等级 单个字段
     */
    public function modityMemberLevelField()
    {
        $member = new MemberService();
        $level_id = request()->post("level_id", 0);
        $field_name = request()->post("field_name", '');
        $field_value = request()->post("field_value", '');
        $res = $member->modifyMemberLevelField($level_id, $field_name, $field_value);
        $this->addUserLogByParam("修改会员等级",$res);
        return AjaxReturn($res);
    }
    /**
     * 会员标签分组
     */
    public function memberGroupList()
    {
        $member = new MemberService();
        if (request()->isAjax()) {
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $page_size = request()->post("page_size", PAGESIZE);
            $condition['website_id'] = $this->website_id;
            $template_list = $member->getMemberGroupList($page_index, $page_size, $condition, 'group_id desc');
            return $template_list;
        }
        return view($this->style . 'Member/memberGroupList');
    }
    /**
     * 会员标签分组弹出层
     */
    public function memberGroupLists()
    {
        $member = new MemberService();
        if (request()->isPost()) {
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $page_size = request()->post("page_size", PAGESIZE);
            $default_uid = request()->post("default_uid", '');
            $condition['website_id'] = $this->website_id;
            $group_list = $member->getMemberGroupList($page_index, $page_size, $condition, 'group_id desc');
            if($default_uid){
                $member = new VslMemberModel();
                $member_ids = $member->getInfo(['uid'=>$default_uid],'group_id')['group_id'];
                if($member_ids){
                    $member_group_id = explode(',',$member_ids);
                    foreach ($group_list['data'] as $k=>$v){
                        if(in_array($v['group_id'],$member_group_id)){
                            $group_list['data'][$k]['is_select'] = 1;
                        }else{
                            $group_list['data'][$k]['is_select'] = 0;
                        }
                    }
                }
            }
            return $group_list;
        }
        $default_uid= request()->get("default_uid", '');
        $member = new VslMemberModel();
        $member_ids = $member->getInfo(['uid'=>$default_uid],'group_id')['group_id'];
        $group_name ='';
        if($member_ids){
            $group = new VslMemberGroupModel();
            $group_ids = explode(',',$member_ids);
            foreach ($group_ids as $v){
                $group_name .= $group ->getInfo(['group_id'=>$v],'group_name')['group_name'].',';
            }
        }
        $this->assign('default_group_name',$group_name);
        $this->assign('default_uid',$default_uid);
        $this->assign('default_group_id',$member_ids);
        return view($this->style . 'Member/memberGroupLists');
    }
    /**
     * 会员成长值
     */
    public function growthNum()
    {
        $member = new MemberService();
        if (request()->isPost()) {
            $pay_num = request()->post("pay_num", '');
            $complete_num = request()->post("complete_num", '');
            $recharge_num = request()->post("recharge_num", '');
            $recharge_money = request()->post("recharge_money", '');
            $order_money = request()->post("order_money", '');
            $recharge_multiple = request()->post("recharge_multiple", '');
            $order_multiple = request()->post("order_multiple", '');
            $res = $member->addMemberGrowthNum($pay_num,$complete_num,$recharge_num,$recharge_money,$order_money,$recharge_multiple,$order_multiple,$this->website_id);
            return AjaxReturn($res);
        }
        $website_id = $this->website_id;
        $info = $member->getMemberGrowthNum($website_id);
        $this->assign('growth_info',$info);
        return view($this->style . 'Member/growthNum');
    }
    /**
     * 修改上级分销商
     */
    public function updateRefereeDistributor(){
        if($this->merchant_expire==1){
            return AjaxReturn(-1);
        }
        $distributor=new Distributor();
        $uid=isset($_POST['uid'])?$_POST['uid']:'';
        $referee_id=isset($_POST['referee_id'])?$_POST['referee_id']:'';
        $retval=$distributor->updateRefereeDistributor($uid,$referee_id);
        return AjaxReturn($retval);
    }
    /**
     * 添加粉丝分组
     */
    public function addLabel()
    {
        if (request()->isPost()) {
            $member = new MemberService();
            $group_name = request()->post("group_name", '');
            $is_label = request()->post("is_label", 0);
            $label_condition = isset($_POST['label_condition']) ? $_POST['label_condition'] : '';//满足条件
            $order_money = isset($_POST['order_money']) ? $_POST['order_money'] : '';//满足交易金额
            $order_pay = isset($_POST['order_pay']) ? $_POST['order_pay'] : '';//满足支付订单
            $point = isset($_POST['point']) ? $_POST['point'] : '';//当前积分
            $balance = isset($_POST['balance']) ? $_POST['balance'] : '';//当前余额
            $goods_id = isset($_POST['goods_id']) ? $_POST['goods_id'] : '';//指定商品id
            $labelconditions = isset($_POST['labelconditions']) ? $_POST['labelconditions'] : '';//标签条件
            $res = $member->addGroup($group_name, $is_label, $label_condition, $order_money, $order_pay, $point, $balance, $goods_id,$labelconditions, $this->website_id);
            return AjaxReturn($res);
        }
        return view($this->style . 'Member/addMemberLabel');
    }

    /**
     * 修改分组名称
     */
    public function updateLabel()
    {
        if (request()->isPost()) {
            $member = new MemberService();
            $group_name = request()->post("group_name", '');
            $group_id = request()->post("group_id", '');
            $is_label = request()->post("is_label", 0);
            $label_condition = isset($_POST['label_condition']) ? $_POST['label_condition'] : '';//满足条件
            $order_money = isset($_POST['order_money']) ? $_POST['order_money'] : '';//满足交易金额
            $order_pay = isset($_POST['order_pay']) ? $_POST['order_pay'] : '';//满足支付订单
            $point = isset($_POST['point']) ? $_POST['point'] : '';//当前积分
            $balance = isset($_POST['balance']) ? $_POST['balance'] : '';//当前余额
            $goods_id = isset($_POST['goods_id']) ? $_POST['goods_id'] : '';//指定商品id
            $labelconditions = isset($_POST['labelconditions']) ? $_POST['labelconditions'] : '';//标签条件
            $res = $member->updateGroupName($group_id, $group_name, $is_label, $label_condition, $order_money, $order_pay, $point, $balance, $goods_id, $labelconditions,$this->website_id);
            return AjaxReturn($res);
        }
        $member = new MemberService();
        $group_id = request()->get("group_id", '');
        $info = $member->getMemberGroupInfo($group_id);
        $this->assign('list',$info);
        return view($this->style . 'Member/updateMemberLabel');
    }
    public function checkLabel()
    {
        $member = new MemberService();
        $group_name= request()->post("group_name", '');
        $res = $member->checkLabel($group_name);
        if($res){
            $res =1;
        }else{
            $res =-1;
        }
        return AjaxReturn($res);
    }
    /**
     * 修改会员当前分组
     */
    public function updateMemberGroup()
    {
        $member = new MemberService();
        $group_id = request()->post("group_id", '');
        $check_uid = request()->post("check_uid", '');
        $res = $member->updateMemberGroup($check_uid,$group_id,$this->website_id);
        return AjaxReturn($res);
    }
    public function getWithdrawCount()
    {
        $order = new MemberService();
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
     * 删除分组
     */
    public function delGroup()
    {
        $member = new MemberService();
        $id = request()->post("group_id", '');
        $res = $member->delGroup($id);
        return AjaxReturn($res);
    }
    /**
     * 会员提现列表
     */
    public function userCommissionWithdrawList()
    {
        if (request()->isAjax()) {
            $member = new MemberService();
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : '';
            if(empty($_POST['start_date'])){
                $start_date = strtotime('2018-1-1');
            }else{
                $start_date = strtotime($_POST['start_date']);
            }
            $withdraw_no = isset($_POST['withdraw_no']) ? $_POST['withdraw_no'] : '';
            if ($withdraw_no != '') {
                $condition['nmar.withdraw_no'] = $withdraw_no;
            }
            if(empty($_POST['end_date'])){
                $end_date = strtotime('2038-1-1');
            }else{
                $end_date = strtotime($_POST['end_date']);
            }
            if ($_POST['user_name'] != "") {
                $condition["su.nick_name|su.user_tel|su.user_name|su.uid"] = array(
                    "like",
                    "%" . $_POST['user_name'] . "%"
                );
            }
            $condition["nmar.website_id"] = $this->website_id;
            if($_POST['status']!="" && $_POST['status']!=9){
                $condition["nmar.status"] = $_POST['status'];
            }
            $condition["nmar.ask_for_date"] = [[">",$start_date],["<",$end_date]];
            $list = $member->getMemberBalanceWithdraw($page_index, PAGESIZE, $condition, 'ask_for_date desc');
            return $list;
        } else {
            return view($this->style . "Member/memberWithdrawalApply");
        }
    }
    /**
     * 会员提现列表导出
     */
    public function userCommissionWithdrawListDataExcel()
    {
        $xlsName = "会员提现流水列表";
        $xlsCell = [
            0=>['withdraw_no','提现流水号'],
            1=>['user_info','会员信息'],
            2=>['type','提现类型'],
            3=>['account_number','提现账户'],
            4=>['cash','提现金额'],
            5=>['status','提现状态'],
            6=>['memo','备注'],
            7=>['ask_for_date','提现时间'],
            8=>['payment_date','到账时间']           
        ];
        $member = new MemberService();
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
        if ($_GET['user_name'] != "") {
            $condition["su.nick_name|su.user_tel|su.user_name"] = array(
                "like",
                "%" . $_GET['user_name'] . "%"
            );
        }
        $condition["nmar.website_id"] = $this->website_id;
        if($_GET['status']!="" && $_GET['status']!=9){
            $condition["status"] = $_GET['status'];
        }
        $condition["ask_for_date"] = [[">",$start_date],["<",$end_date]];
        $list = $member->getMemberBalanceWithdraw(1,0 ,$condition, 'ask_for_date desc');
        foreach ($list['data'] as $k=>$v){
            if($v['type']==1 || $v['type']==4){
                $list['data'][$k]['type']= '银行卡';
            }elseif ($v['type']==2){
                $list['data'][$k]['type']= '微信';
            }elseif ($v['type']==3){
                $list['data'][$k]['type']= '支付宝';
            }
            if($v['status']==2){
                $list['data'][$k]['status']= '待打款';
            }elseif ($v['status']==1){
                $list['data'][$k]['status']= '待审核';
            }elseif ($v['status']==3){
                $list['data'][$k]['status']= '已打款';
            }elseif ($v['status']==4){
                $list['data'][$k]['status']= '拒绝打款';
            }elseif ($v['status']==-1){
                $list['data'][$k]['status']= '审核不通过';
            }elseif ($v['status']==5){
                $list['data'][$k]['status']= '打款失败';
            }
            $list['data'][$k]['withdraw_no'] = $v['withdraw_no']."\t";
            $list['data'][$k]['cash'] = '¥'.$v['cash'];
            if(empty($v['user_name'])){
                $list['data'][$k]['user_name'] = $v['nick_name'];
            }
            if($v['payment_date']>0){
                $list['data'][$k]['payment_date'] = date('Y-m-d H:i:s', $v['payment_date']);
            }else{
                $list['data'][$k]['payment_date'] = '未到账';
            }

        }
       $this->addUserLogByParam('提现流水导出', 1);
       dataExcel($xlsName, $xlsCell, $list['data']);
    }
    /**
     * 用户打款
     */
    public function withdrawMakeMoney()
    {
        $id = $_GET["id"];
        $member = new MemberService();
        $retval = $member->getMemberWithdrawalsDetails($id);
        $uid = $retval['uid'];
        $user_mdl = new UserModel();
        $user_info = $user_mdl->getInfo(['uid' => $uid]);
        $retval['realname'] = $retval['realname'] ? : (($user_info['nick_name'])?$user_info['nick_name']:($user_info['user_name']?$user_info['user_name']:($user_info['user_tel']?$user_info['user_tel']:$user_info['uid'])));
        $this->assign('list',$retval);
        return view($this->style . "Member/withdrawalsMake");
    }
    /**
     * 打款状态
     */
    public function userWithdrawMake()
    {
        $id = $_POST["id"];
        $remark = $_POST['memo'];
        $status = $_POST['status'];
        $member = new MemberService();
        $ids = explode(',',$id);
        if(count($ids)>1){
            foreach ($ids as $v) {
                $retval = $member->memberBalanceWithdraw($this->instance_id,$v, $status,$remark);
            }
        }else{
            $retval = $member->memberBalanceWithdraw($this->instance_id,$id, $status,$remark);
        }
        $this->addUserLogByParam("打款",$retval);
        if($retval){
            $this->addUserLogByParam('打款状态', $retval);
        }
        if($retval==-9000){
            $balance = new VslMemberBalanceWithdrawModel();
            $msg = $balance->getInfo(['id'=>$id],'memo')['memo'];
        }else if($retval>0){
            $msg = '打款成功';
        }else{
            $msg = '打款失败';
        }
        return AjaxReturn($retval,$msg);
    }
    /**
     *用户提现失败原因
     */
    public function withdrawFailReason()
    {
        $id = $_GET["id"];
        $member = new MemberService();
        $retval = $member->getMemberWithdrawalsDetails($id);
        $this->assign('list',$retval);
        return view($this->style . "Member/withdrawFailReason");
    }
    /**
     * 用户提现详情
     */
    public function withdrawAudit()
    {
        $id = $_GET["id"];
        $member = new MemberService();
        $retval = $member->getMemberWithdrawalsDetails($id);
        $uid = $retval['uid'];
        $user_mdl = new UserModel();
        $user_info = $user_mdl->getInfo(['uid' => $uid]);
        $retval['realname'] = $retval['realname'] ? : (($user_info['nick_name'])?$user_info['nick_name']:($user_info['user_name']?$user_info['user_name']:($user_info['user_tel']?$user_info['user_tel']:$user_info['uid'])));
        $this->assign('list',$retval);
        return view($this->style . "Member/withdrawalsAudit");
    }
    /**
     * 用户提现审核
     */
    public function userCommissionWithdraw()
    {
        $id = $_POST["id"];
        $status = $_POST["status"];
        $remark = isset($_POST['memo']) ? $_POST['memo'] : '';
        $member = new MemberService();
        $ids = explode(',',$id);
        if(count($ids)>1){
            foreach ($ids as $v) {
                $retval = $member->userCommissionWithdraw($this->instance_id, $v, $status, $remark);
            }
        }else{
            $retval = $member->userCommissionWithdraw($this->instance_id, $id, $status, $remark);
        }
        $this->addUserLogByParam("用户提现审核",$id);
        if($retval){
            $this->addUserLogByParam('用户提现审核', $retval);
        }

        if (isset($retval['is_success'])) {
            return [
                'code' => $retval['is_success'],
                'message' => $retval['msg']
            ];
        }

        return AjaxReturn($retval);
    }

    /**
     * 查寻符合条件的数据并返回id （多个以“,”隔开）
     */
    public function getUserUids($condition)
    {
        $member = new MemberService();
        $list = $member->getMemberAll($condition);
        $uid_string = "";
        foreach ($list as $k => $v) {
            $uid_string = $uid_string . "," . $v["uid"];
        }
        if ($uid_string != "") {
            $uid_string = substr($uid_string, 1);
        }
        return $uid_string;
    }

    /**
     * 获取提现详情
     */
    public function getWithdrawalsInfo()
    {
        $id = $_GET['id'] ? $_GET['id'] : '';
        $member = new MemberService();
        $retval = $member->getMemberWithdrawalsDetails($id);
        $uid = $retval['uid'];
        $user_mdl = new UserModel();
        $user_info = $user_mdl->getInfo(['uid' => $uid]);
        $retval['realname'] = $retval['realname'] ? : (($user_info['nick_name'])?$user_info['nick_name']:($user_info['user_name']?$user_info['user_name']:($user_info['user_tel']?$user_info['user_tel']:$user_info['uid'])));
        $this->assign('list',$retval);
        return view($this->style . "Member/withdrawalsDetail");
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
     *
     */
    public function getCity()
    {
        $address = new Address();
        $province_id = isset($_POST['province_id']) ? $_POST['province_id'] : 0;
        $city_list = $address->getCityList($province_id);
        return $city_list;
    }
    
    /**
     * 获取区域地址
     */
    public function getDistrict()
    {
        $address = new Address();
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        $district_list = $address->getDistrictList($city_id);
        return $district_list;
    }

    /**
     * 用户数据excel导出
     */
    public function memberDataExcel()
    {
        $xlsName = "会员数据列表";
        $xlsCell = [
            0=>['uid','ID'],
            1=>['nick_name','昵称'],
            2=>['user_name','用户名'],
            3=>['user_tel','手机号码'],
            4=>['level_name','会员等级'],
            5=>['group_name','会员标签'],
            6=>['point','积分'],
            7=>['balance','余额'],
            8=>['order_num','成交订单数'],
            9=>['order_money','成交总金额'],
            10=>['reg_time','注册时间'],

        ];
        $user_group = request()->get('user_group', '');
        $member_status = request()->get('member_status', '');
        $member_id = request()->get('member_id', '');
        $search_text = request()->get('search_text', '');
        $start_create_date = request()->get('start_create_date') == "" ? '2018-1-1' : request()->get('start_create_date');
        $end_create_date = request()->get('end_create_date') == "" ? '2038-1-1' : request()->get('end_create_date');
        $level_id = request()->get('member_level', '');
        if($member_id){
            $condition['su.uid'] = $member_id;
        }
        if($user_group){
            $condition['nm.group_id'] = [['=', $user_group], ['like', '%'.$user_group],['like', '%'.$user_group.'%'],['like', $user_group.'%'], 'or'];
        }
        if($member_status && $member_status!='undefined'){
            $condition['su.user_status'] = $member_status;
        }
        $condition["su.reg_time"] = [
            [
                ">",
                strtotime($start_create_date)
            ],
            [
                "<",
                strtotime($end_create_date)
            ]
        ];
        $condition['su.is_member'] = 1;
        if($search_text){
            $condition['su.nick_name|su.user_tel|su.user_email'] = [
                'like',
                '%' . $search_text . '%'
            ];
        }
        if ($level_id) {
            $condition['nml.level_id'] = $level_id;
        }
        $condition['su.website_id'] = $this->website_id;
        $member = new MemberService();
        $list = $member->getMemberList(1, 0, $condition, 'su.reg_time desc');
        $data = [];
        foreach ($list['data'] as $k => $v) {
            $data[$k]["uid"] = $v['uid'];
            $data[$k]["nick_name"] = iconv('gb2312//ignore', 'utf-8', iconv('utf-8', 'gb2312//ignore', $v['nick_name']));
            $data[$k]["user_name"] = $v['user_name'];
            $data[$k]["user_tel"] = $v['user_tel']."\t";
            $data[$k]["level_name"] = $v['level_name'];
            $data[$k]["group_name"] = $v['group_name'];
            $data[$k]["point"] = $v['point'];
            $data[$k]["balance"] = '¥'.$v['balance'];
            $data[$k]["order_num"] = $v['order_num'];
            $data[$k]["order_money"] = '¥'.$v['order_money'];
            $data[$k]["reg_time"] = $v['reg_time'];
        }
        $this->addUserLogByParam("用户excel导出",'');
        dataExcel($xlsName, $xlsCell, $data);
    }
    /**
     * 修改会员为分销商
     */
    public function becomeDis()
    {
        $member = new MemberService();
        $uid = request()->post("uid", 0);
        $res = $member->updateMemberDistributor($uid);
        $this->addUserLogByParam("修改会员为分销商",$res);
        return AjaxReturn($res);
    }
    /**
     * 修改会员为股东
     */
    public function becomeGlobal()
    {
        $member = new MemberService();
        $uid = request()->post("uid", 0);
        $res = $member->updateMemberGlobal($uid);
        $this->addUserLogByParam("修改会员为股东",$res);
        return AjaxReturn($res);
    }
    /**
     * 修改会员为区代
     */
    public function becomeArea()
    {
        $member = new MemberService();
        $uid = request()->post("uid", 0);
        $res = $member->updateMemberArea($uid);
        $this->addUserLogByParam("修改会员为区代",$res);
        return AjaxReturn($res);
    }
    /**
     * 修改会员为队长
     */
    public function becomeTeam()
    {
        $member = new MemberService();
        $uid = request()->post("uid", 0);
        $res = $member->updateMemberTeam($uid);
        $this->addUserLogByParam("修改会员为队长",$res);
        return AjaxReturn($res);
    }
    /**
     * 修改会员为渠道商
     */
    public function becomeChannel()
    {
        $member = new MemberService();
        $uid = request()->post("uid", 0);
        $res = $member->updateMemberChannel($uid);
        $this->addUserLogByParam("修改会员为渠道商",$res);
        return AjaxReturn($res);
    }
    /**
     * 修改会员为店长
     */
    public function becomeMicroshop()
    {
        $member = new MemberService();
        $uid = request()->post("uid", 0);
        $res = $member->updateMemberMicroshop($uid);
        $this->addUserLogByParam("修改会员为店长",$res);
        return AjaxReturn($res);
    }
    /**
     * 后台添加新会员账号
     */
    public function addUsers(){
        $member = new MemberService();
        $list = $member->getMemberLevelList(1, 0,["website_id" => $this->website_id]);
        $this->assign('level_list',$list);
        return view($this->style . 'Member/addUsers');
    }
    /**
     * 后台手动添加新用户
     */ 
    public function register(){
        $member = new MemberService();
        $user = new User();
        $mobile = request()->post("mobile", '');
        $password = request()->post("password", '');
        $level_id = request()->post("level_id", '');
        $referee_id = request()->post("referee_id", '');
        $pic = request()->post("pic", '');
        $nickname = request()->post("nickname", '');
        //校验手机号 
        if(!preg_match("/^[1][3,4,5,6,7,8,9][0-9]{9}$/", $mobile)){
            return json(['code' => 0,'message' => '请输入正确的手机号码']);
        } 
        if(!preg_match("/^(\w){6,20}$/", $password)){
            return json(['code' => 0,'message' => '请输入由6-20个字母、数字、下划线组成的密码']);
        }
        //查询该手机号是否已经被注册
        $res = $user->checkIsAssociate($mobile);
        if($res == true){
            return json(['code' => 0,'message' => '该手机号已注册']);
        }
        $data = array(
            'mobile' => $mobile,
            'password' => $password,
            'level_id' => $level_id,
            'referee_id' => $referee_id,
            'pic' => $pic,
            'nickname' => $nickname
        );
        $retval = $member->registerPlaMember($data);
        if ($retval > 0) {
            return json(['code' => 1,'message' => '注册成功']);
        }else{
            return json(['code' => 0,'message' => '注册失败']);
        }
    }
    /**
     * 获取会员信息
     */
    public function getUser(){
        $user = new User();
        $uid = request()->post("uid", '');
        $res = $user->getUserInfoByUid($uid);
        $res['user_headimg'] = __IMG($res['user_headimg']);
        $data = array(
            'user' => $res
        );
        return json(['code' => 1,'message' => '获取成功','data' => $data]);
    }
    /**
     * 获取会员列表
     */
    public function getUserLists(){
        $member = new MemberService();
        $search_text = request()->post('search_text','');
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size',PAGESIZE);
        if($search_text){
            $condition['su.nick_name|su.user_tel|su.user_name'] = [
                'like',
                '%' . $search_text . '%'
            ];
        }
        $condition['su.website_id'] = $this->website_id;
        $data = $member -> getMemberList($page_index, $page_size, $condition, 'su.reg_time desc');
        return $data;
    }
}