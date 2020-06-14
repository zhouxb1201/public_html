<?php
namespace addons\teambonus\service;
/**
 * 团队分红服务层
 */
use addons\bonus\model\VslAgentLevelModel;
use addons\distribution\model\VslDistributorLevelModel;
use addons\distribution\model\VslOrderDistributorCommissionModel;
use addons\store\model\VslStoreAssistantModel;
use addons\store\model\VslStoreModel;
use data\model\VslAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslMemberModel;
use data\model\VslMemberViewModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderGoodsPromotionDetailsModel;
use data\model\VslOrderMemoModel;
use data\model\VslOrderModel;
use data\model\VslOrderGoodsExpressModel;
use data\model\VslGoodsSpecValueModel;
use data\model\ProvinceModel;
use data\model\CityModel;
use data\model\DistrictModel;
use data\model\AlbumPictureModel;
use data\model\VslGoodsSkuModel;
use data\model\VslGoodsModel;
use data\model\VslOrderPromotionDetailsModel;
use data\service\BaseService as BaseService;
use addons\shop\model\VslShopModel as VslShopModel;
use data\model\UserModel;
use data\service\Config;
use think;
use addons\bonus\model\VslAgentLevelModel as AgentLevelModel;
use data\service\Config as ConfigService;
use data\model\ConfigModel as ConfigModel;
use data\service\Order\OrderStatus;
use data\model\VslMemberAccountModel;
use addons\bonus\model\VslBonusAccountModel;
use addons\bonus\model\VslOrderBonusModel;
use addons\bonus\model\VslAgentAccountRecordsModel;
use addons\bonus\model\VslBonusGrantModel;
use addons\bonus\model\VslUnGrantBonusOrderModel;
use addons\bonus\model\VslGrantTimeModel;
use data\service\Order as OrderService;
use data\service\Order\Order as OrderBusiness;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
use data\service\ShopAccount;
use addons\groupshopping\server\GroupShopping;
use addons\microshop\model\VslOrderMicroShopProfitModel;
class TeamBonus extends BaseService
{
    private $config_module;
    private $fre_bonus;
    private $wit_bonus;
    private $wits_bonus;
    private $bonus;
    function __construct()
    {
        parent::__construct();
        $this->config_module = new ConfigModel();
        $this->addons_config_module = new AddonsConfigModel();
        $set = $this->getAgreementSite($this->website_id);
        if($set && $set['frozen_team_bonus']){
            $this->fre_bonus = $set['frozen_team_bonus'];
        }else{
            $this->fre_bonus = '冻结分红';
        }
        if($set &&  $set['withdrawable_team_bonus']){
            $this->wit_bonus = $set['withdrawable_team_bonus'];
        }else{
            $this->wit_bonus = '待发放分红';
        }
        if($set &&  $set['withdrawals_team_bonus']){
            $this->wits_bonus = $set['withdrawals_team_bonus'];
        }else{
            $this->wits_bonus = '已发放分红';
        }
    }

    /**
     * 获取队长列表
     */
    public function getAgentList($uid,$page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $where['nm.website_id'] = $this->website_id;
        $user = new UserModel();
        $agent_view = new VslMemberViewModel();
        $result = $agent_view->getTeamAgentViewList($page_index, $page_size, $where, $order);
        $condition['website_id'] = $this->website_id;
        $condition['is_team_agent'] = ['in','1,2,-1'];
        $result['count'] = $agent_view->getCount($condition);
        $condition['is_team_agent'] = 2;
        $result['count1'] = $agent_view->getCount($condition);
        $condition['is_team_agent'] = 1;
        $result['count2'] = $agent_view->getCount($condition);
        $condition['is_team_agent'] = -1;
        $result['count3'] = $agent_view->getCount($condition);
        $bonus_account = new VslBonusAccountModel();
        foreach ($result['data'] as $k => $v) {
            if(empty($list['data'][$k]['user_name'])){
                $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
            }
            $user_info = $user->getInfo(['uid'=>$v['referee_id']],'user_name,nick_name,user_headimg');
            if($user_info['user_name']){
                $result['data'][$k]['referee_name'] = $user_info['user_name'];//推荐人
            }else{
                $result['data'][$k]['referee_name'] = $user_info['nick_name'];//推荐人
            }
            $result['data'][$k]['referee_headimg'] = $user_info['user_headimg'];
            $result['data'][$k]['account'] = $bonus_account->getInfo(['uid'=>$v['uid'],'from_type'=>3],'*');
        }
        return $result;
    }
    /**
     * 获取队长等级列表
     */
    public function getAgentLevelList($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $agent_level = new AgentLevelModel();
        $distributor_level = new VslDistributorLevelModel();
        $list = $agent_level->pageQuery($page_index, $page_size, $where, $order, '*');
        $goods = new VslGoodsModel();
        foreach ($list['data'] as $k=>$v){
            if ($list['data'][$k]['goods_id']) {
                $list['data'][$k]['goods_name'] = $goods->getInfo(['goods_id' => $list['data'][$k]['goods_id']], 'goods_name')['goods_name'];
            }
            if ($list['data'][$k]['upgrade_level']) {
                $list['data'][$k]['upgrade_level_name'] = $distributor_level->getInfo(['id' => $list['data'][$k]['upgrade_level']], 'level_name')['level_name'];
            }
        }
        return $list;
    }
    /**
     * 获取当前队长等级
     */
    public function getAgentLevel()
    {
        $agent_level = new AgentLevelModel();
        $list = $agent_level->pageQuery(1,0,['website_id' => $this->website_id,'from_type'=>3],'','id,level_name');
        return $list['data'];
    }
    /**
     * 获取当前队长等级权重
     */
    public function getAgentWeight()
    {
        $agent_level = new AgentLevelModel();
        $list = $agent_level->Query(['website_id' => $this->website_id,'from_type'=>3],'weight');
        return $list;
    }
    /**
     * 添加队长等级
     */
    public function addAgentLevel($level_name,$ratio,$upgradetype,$pay_money,$number,$one_number,$two_number,$three_number,$order_money,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$group_number,$up_team_money,$level_award1,$level_award2,$level_award3)
    {
        $Agent_level = new AgentLevelModel();
        $base_set = $this->getTeamBonusSite($this->website_id);
        if($base_set && $base_set['gradation_status']==1){

        }else{
            $ratio_used = $Agent_level->getSum(['website_id'=>$this->website_id,'from_type'=>3],'ratio');
            if($ratio_used){
                $ratio_total = $ratio_used+$ratio;
                if($ratio_total>100){
                    return -3;
                }
            }
        }
        $where['website_id'] = $this->website_id;
        $where['level_name'] = $level_name;
        $where['from_type'] = 3;
        $count = $Agent_level->where($where)->count();
        if ($count > 0) {
            return -2;
        }
        $data = array(
            'website_id' => $this->website_id,
            'level_award1' => $level_award1,
            'level_award2' => $level_award2,
            'level_award3' => $level_award3,
            'level_name' => $level_name,
            'ratio' => $ratio,
            'upgradetype' => $upgradetype,
            'number' => $number,
            'order_money' => $order_money,
            'pay_money' => $pay_money,
            'one_number' => $one_number,
            'two_number' => $two_number,
            'three_number' => $three_number,
            'downgradetype' => $downgradetype,
            'team_number' => $team_number,
            'team_money' => $team_money,
            'up_team_money' => $up_team_money,
            'self_money' => $self_money,
            'team_number_day' => $team_number_day,
            'team_money_day' => $team_money_day,
            'self_money_day' => $self_money_day,
            'weight' => $weight,
            'downgradeconditions' => $downgradeconditions,
            'upgradeconditions' => $upgradeconditions,
            'downgrade_condition' => $downgrade_condition,
            'upgrade_condition' => $upgrade_condition,
            'goods_id' => $goods_id,
            'from_type' => 3,
            'level_number' => $level_number,
            'upgrade_level' => $upgrade_level,
            'group_number'=>$group_number,
            'create_time' => time(),
        );
        $res = $Agent_level->save($data);
        return $res;
    }

    /**
     * 修改队长等级
     */
    public function updateAgentLevel($id, $level_name,$ratio,$upgradetype,$pay_money,$number,$one_number,$two_number,$three_number,$order_money,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$group_number,$up_team_money,$level_award1,$level_award2,$level_award3)
    {
        try {
            $Agent_level = new AgentLevelModel();
            $base_set = $this->getTeamBonusSite($this->website_id);
            if($base_set && $base_set['gradation_status']==1){

            }else{
                $ratio_used = $Agent_level->getSum(['website_id'=>$this->website_id,'id'=>['neq',$id],'from_type'=>3],'ratio');
                if($ratio_used){
                    $ratio_total = $ratio_used+$ratio;
                    if($ratio_total>100){
                        return -3;
                    }
                }
            }
            $where['website_id'] = $this->website_id;
            $where['level_name'] = $level_name;
            $where['from_type'] = 3;
            $where['id'] = ['neq',$id];
            $count = $Agent_level->where($where)->count();
            if ($count > 0) {
                return -2;
            }
            $Agent_level->startTrans();
            $data = array(
                'level_award1' => $level_award1,
                'level_award2' => $level_award2,
                'level_award3' => $level_award3,
                'level_name' => $level_name,
                'ratio' => $ratio,
                'upgradetype' => $upgradetype,
                'number' => $number,
                'order_money' => $order_money,
                'pay_money' => $pay_money,
                'one_number' => $one_number,
                'two_number' => $two_number,
                'three_number' => $three_number,
                'downgradetype' => $downgradetype,
                'team_number' => $team_number,
                'team_money' => $team_money,
                'up_team_money' => $up_team_money,
                'self_money' => $self_money,
                'team_number_day' => $team_number_day,
                'team_money_day' => $team_money_day,
                'self_money_day' => $self_money_day,
                'weight' => $weight,
                'downgradeconditions' => $downgradeconditions,
                'upgradeconditions' => $upgradeconditions,
                'downgrade_condition' => $downgrade_condition,
                'upgrade_condition' => $upgrade_condition,
                'goods_id' => $goods_id,
                'level_number' => $level_number,
                'upgrade_level' => $upgrade_level,
                'group_number'=>$group_number,
                'modify_time' => time()
            );
            $retval= $Agent_level->save($data, [
                'id' => $id,
                'website_id' => $this->website_id
            ]);
            $Agent_level->commit();
            return $retval;
        } catch (\Exception $e) {
            $Agent_level->rollback();
            $retval = $e->getMessage();
            return 0;
        }
    }
    /*
     * 删除分红商等级
     */
    public function deleteAgentLevel($id)
    {
        // TODO Auto-generated method stub
        $level = new AgentLevelModel();
        $level->startTrans();
        try {
            // 删除等级信息
            $retval = $level->destroy($id);
            $level->commit();
            return $retval;
        }catch (\Exception $e) {
            $level->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 获得队长等级详情
     */
    public function getAgentLevelInfo($id)
    {
        $level_type = new AgentLevelModel();
        $level_info = $level_type->getInfo(['id'=>$id]);
        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id'=>$level_info['goods_id']],'picture,goods_name');
        $pic_id = $goods_info['picture'];
        $pic = new AlbumPictureModel();
        $level_info['pic'] = $pic->getInfo(['pic_id'=>$pic_id],'pic_cover_mid')['pic_cover_mid'];
        $level_info['goods_name'] = $goods_info['goods_name'];
        return $level_info;
    }

    /**
     * 修改队长申请状态
     */
    public function setStatus($uid, $status){
        $member = new VslMemberModel();
        $level = new AgentLevelModel();
        $level_info = $level->getInfo(['website_id'=>$this->website_id,'is_default'=>1,'from_type'=>3],'*');
        $level_id = $level_info['id'];
        if($status==2){
            $data = array(
                'is_team_agent' => $status,
                'become_team_agent_time' => time(),
                'team_agent_level_id' => $level_id
            );
        }else{
            $data = array(
                'is_team_agent' => $status
            );
        }
        if($status==2){
            $account = new VslBonusAccountModel();
            $account_info = $account->getInfo(['website_id'=>$this->website_id,'from_type'=>3,'uid' => $uid]);
            if(empty($account_info)){
                $account->save(['website_id'=>$this->website_id,'from_type'=>3,'uid' => $uid]);
            }
            $ratio = $level_info['ratio'].'%';
            runhook("Notify", "sendCustomMessage", ["messageType"=>"become_team","uid" => $uid,"become_time" => time(),'ratio'=>$ratio,'level_name'=>$level_info['level_name']]);//用户成为团队队长提醒
        }
        $res =$member->save($data,[
            'uid'=>$uid
        ]);
        return $res;
    }
    /**
     * 队长详情
     */
    public function getAgentInfo($uid)
    {
        $agent = new VslMemberModel();
        $result = $agent->getInfo(['uid' => $uid],"*");
        $pic = new UserModel();
        $result['pic'] = $pic->getInfo(['uid'=>$uid],'user_headimg')['user_headimg'];
        $result['user_name'] = $pic->getInfo(['uid'=>$uid],'user_name')['user_name'];
        $account = new VslBonusAccountModel();
        $bonus_info = $account->getInfo(['uid'=>$uid,'from_type'=>3],'*');
        $result['total_bonus'] = $bonus_info['total_bonus'];
        $result['grant_bonus'] = $bonus_info['grant_bonus'];
        $referee_id = $result['referee_id'];
        $result['apply_team_agent_time'] = $result['apply_team_agent_time'] ? date('Y-m-d H:i:s',$result['apply_team_agent_time']) : date('Y-m-d H:i:s',$result['become_team_agent_time']);
        $result['become_team_agent_time'] = date('Y-m-d H:i:s',$result['become_team_agent_time']);
        $referee_info = $pic->getInfo(['uid' => $referee_id],"user_name,nick_name");
        if(empty($result['user_name'])){
            $result['user_name'] =$result['nick_name'];
        }
        if($referee_info['user_name']){
            $result['referee_name'] =$referee_info['user_name'];
        }else{
            $result['referee_name'] =$referee_info['nick_name'];
        }
        return $result;
    }
    /**
     * 修改队长资料
     */
    public function updateAgentInfo($data, $uid)
    {
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid'=>$uid]);
        if($data['team_agent_level_id']){
            $agent_level = new VslAgentLevelModel();
            $level_team_weight = $agent_level->getInfo(['id'=>$member_info['team_agent_level_id']],'weight')['weight'];
            $level_team_weights = $agent_level->getInfo(['id'=>$data['team_agent_level_id']],'weight')['weight'];
            if($level_team_weight){
                if($level_team_weights>$level_team_weight){
                    $data['up_team_level_time'] = time();
                    $data['down_up_team_level_time'] = '';
                }
            }
        }
        $retval = $member->save($data, [
            'uid' => $uid
        ]);
        return $retval;
    }

    /**
     * 申请成为队长
     */
    public function addAgentInfo($website_id,$uid,$post_data,$real_name)
    {
        $user = new VslMemberModel();
        $info = $this->getTeamBonusSite($website_id);
        $level = new AgentLevelModel();
        $level_info = $level->getInfo(['website_id'=>$website_id,'is_default'=>1,'from_type'=>3],'*');
        $level_id = $level_info['id'];
        $user_info = new UserModel();
        if(empty($real_name)){
            $real_name = $user_info->getInfo(['uid'=>$uid],'real_name')['real_name'];
        }
        $member_info = $user->getInfo(['uid'=>$uid]);
        if($member_info['is_team_agent']==3){
            $data = array(
                "real_name"=>$real_name,
                "is_team_agent" => 2,
                "team_agent_level_id" => $level_id,
                "apply_team_agent_time" => time(),
                "become_team_agent_time" => time(),
                "custom_team"=>$post_data,
                'complete_datum_team'=>1
            );
            $ratio = $level_info['ratio'].'%';
            runhook("Notify", "sendCustomMessage", ["messageType"=>"become_team","uid" => $uid,"become_time" => time(),'ratio'=>$ratio,'level_name'=>$level_info['level_name']]);//用户成为团队队长提醒
        }else if($member_info['is_team_agent']==2){
            $data = array(
                "real_name"=>$real_name,
                "custom_team"=>$post_data,
                'complete_datum_team'=>1
            );
        }else{
            if($info['teamagent_check']==1){
                $data = array(
                    "real_name"=>$real_name,
                    "is_team_agent" => 2,
                    "team_agent_level_id" => $level_id,
                    "apply_team_agent_time" => time(),
                    "become_team_agent_time" => time(),
                    "custom_team"=>$post_data,
                    'complete_datum_team'=>1
                );
                $ratio = $level_info['ratio'].'%';
                runhook("Notify", "sendCustomMessage", ["messageType"=>"become_team","uid" => $uid,"become_time" => time(),'ratio'=>$ratio,'level_name'=>$level_info['level_name']]);//用户成为团队队长提醒
            }else{
                $data = array(
                    "real_name"=>$real_name,
                    "is_team_agent" => 1,
                    "team_agent_level_id" => $level_id,
                    "apply_team_agent_time" => time(),
                    "custom_team"=>$post_data,
                    'complete_datum_team'=>1
                );
                runhook("Notify", "sendCustomMessage", ["messageType"=>"apply_team","uid" => $uid,"apply_time" => time(),'level_name'=>$level_info['level_name']]);//用户申请成为团队队长提醒
            }
        }

        $result = $user->save($data, [
            'uid' => $uid
        ]);
        $account = new VslBonusAccountModel();
        $account_info = $account->getInfo(['website_id'=>$website_id,'from_type'=>3,'uid' => $uid]);
        if(empty($account_info)){
            $account->save(['website_id'=>$website_id,'from_type'=>3,'uid' => $uid]);
        }
        if($real_name && $result==1){
            $user = new UserModel();
            $user->save(['real_name'=>$real_name], ['uid' => $uid]);
        }
        return $result;
    }
    /**
     * 查询队长状态
     */
    public function getAgentStatus($uid)
    {
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid' => $uid],"*");
        $result['status'] = $member_info['is_team_agent'];
        if($result['status']==2){
            $level = new AgentLevelModel();
            $result['level_name'] = $level->getInfo(['id'=>$member_info['team_agent_level_id']],'level_name')['level_name'];
        }
        return $result;
    }

    /**
     * 团队分红设置
     */
    public function setTeamBonusSite($teambonus_status,$agent_condition, $agent_conditions, $pay_money,$number,$one_number,$two_number,$three_number, $order_money, $agent_check, $agent_grade, $goods_id,$purchase_type,$gradation_status,$agent_data,$up_team_money,$level_award)
    {
        $account = new VslBonusAccountModel();
        $user_account = $account->getInfo(['website_id'=>$this->website_id,'from_type'=>3,'ungrant_bonus'=>['>',0]])['ungrant_bonus'];
        if($user_account>0 && $teambonus_status==0){
            return -3;
        }
        $ConfigService = new AddonsConfigService();
        $value = array(
            'website_id' => $this->website_id,
            'teamagent_condition' => $agent_condition,
            'teamagent_conditions' => $agent_conditions,
            'pay_money' => $pay_money,
            'number' => $number,
            'one_number' => $one_number,
            'two_number' => $two_number,
            'three_number' => $three_number,
            'order_money' => $order_money,
            'teamagent_check' => $agent_check,
            'teamagent_grade' => $agent_grade,
            'goods_id' => $goods_id,
            'purchase_type' => $purchase_type,
            'gradation_status' => $gradation_status,
            'teamagent_data' => $agent_data,
            'up_team_money' => $up_team_money,
            'level_award' => $level_award,
        );

        $teambonus_info = $ConfigService->getAddonsConfig("teambonus");
        if (! empty($teambonus_info)) {
            $data = array(
                "value" => json_encode($value),
                "is_use"=> $teambonus_status,
                'modify_time' => time()
            );
            $res = $this->addons_config_module->save($data, [
                "website_id" => $this->website_id,
                "addons" => "teambonus"
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig($value, "团队分红设置", $teambonus_status,"teambonus");
        }
        return $res;
    }
    /*
     * 获取团队分红基本设置
     *
     */
    public function getTeamBonusSite($website_id){
        $config = new AddonsConfigService();
        $teambonus = $config->getAddonsConfig("teambonus",$website_id);
        $teambonus_info = json_decode($teambonus['value'], true);
        $teambonus_info['is_use'] = $teambonus['is_use'];
        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id'=>$teambonus_info['goods_id']],'picture,goods_name');
        $pic_id = $goods_info['picture'];
        $pic = new AlbumPictureModel();
        $teambonus_info['pic'] = $pic->getInfo(['pic_id'=>$pic_id],'pic_cover_mid')['pic_cover_mid'];
        $teambonus_info['goods_name'] = $goods_info['goods_name'];
        return $teambonus_info;
    }
    /**
     * 分红结算设置
     */
    public function setSettlementSite($bonus_calculation, $limit_time,$withdrawals_check, $bonus_poundage,$poundage,$withdrawals_begin,$withdrawals_end,$limit_date)
    {
        $ConfigService = new ConfigService();
        $value = array(
            'website_id' => $this->website_id,
            'withdrawals_check' => $withdrawals_check,
            'bonus_calculation' => $bonus_calculation,
            'limit_time' => $limit_time,
            'limit_date' => $limit_date,
            'bonus_poundage' => $bonus_poundage,
            'poundage' => $poundage,
            'withdrawals_begin' => $withdrawals_begin,
            'withdrawals_end' => $withdrawals_end
        );
        $teambonus_info = $ConfigService->getConfig(0,"TEAMSETTLEMENT");
        if (! empty($teambonus_info)) {
            $data = array(
                "value" => json_encode($value)
            );
            $res = $this->config_module->save($data, [
                "instance_id" => 0,
                "website_id" => $this->website_id,
                "key" => "TEAMSETTLEMENT"
            ]);
        } else {
            $res = $ConfigService->addConfig(0, "TEAMSETTLEMENT", $value, "团队分红结算设置", 1);
        }
        // TODO Auto-generated method stub
        return $res;
    }
    /*
      * 获取分红结算设置
      *
      */
    public function getSettlementSite($website_id){
        $config = new ConfigService();
        $teambonus = $config->getConfig(0,"TEAMSETTLEMENT",$website_id);
        $teambonus_info = json_decode($teambonus['value'], true);
        return $teambonus_info;
    }
    /**
     * 团队分红申请协议设置
     */
    public function setAgreementSite($type,$logo,$content,$withdrawals_team_bonus,$withdrawal_team_bonus,$frozen_team_bonus,$apply_team,$team_agreement)
    {
        $ConfigService = new ConfigService();
        $agreement = $ConfigService->getConfig(0,"TEAMAGREEMENT");
        $agreement_infos = json_decode($agreement['value'], true);
        if($agreement_infos && $type==1){//文案
            $value = array(
                'logo' => $logo,
                'content' =>  $agreement_infos['content'],
                'withdrawals_team_bonus' => $withdrawals_team_bonus,
                'withdrawal_team_bonus' => $withdrawal_team_bonus,
                'frozen_team_bonus' => $frozen_team_bonus,
                'apply_team' => $apply_team,
                'team_agreement' => $team_agreement
            );
        }else if($agreement_infos && $type==2){
            $value = array(
                'logo' => $agreement_infos['logo'],
                'content' => $content,
                'withdrawals_team_bonus' => $agreement_infos['withdrawals_team_bonus'],
                'withdrawal_team_bonus' => $agreement_infos['withdrawal_team_bonus'],
                'frozen_team_bonus' => $agreement_infos['frozen_team_bonus'],
                'apply_team' => $agreement_infos['apply_team'],
                'team_agreement' => $agreement_infos['team_agreement']
            );
        }else{
            $value = array(
                'logo' => $logo,
                'content' =>  $content,
                'withdrawals_team_bonus' => $withdrawals_team_bonus,
                'withdrawal_team_bonus' => $withdrawal_team_bonus,
                'frozen_team_bonus' => $frozen_team_bonus,
                'apply_team' => $apply_team,
                'team_agreement' => $team_agreement
            );
        }
        if (! empty($agreement)) {
            $data = array(
                "value" => json_encode($value)
            );
            $res = $this->config_module->save($data, [
                "instance_id" => 0,
                "website_id" => $this->website_id,
                "key" => "TEAMAGREEMENT"
            ]);
        } else {
            $res = $ConfigService->addConfig(0, "TEAMAGREEMENT", $value, "团队分红申请协议", 1);
        }
        return $res;
    }
    /*
      * 获取团队分红申请协议
      */
    public function getAgreementSite($website_id){
        $config = new ConfigService();
        $teambonus = $config->getConfig(0,"TEAMAGREEMENT",$website_id);
        $teambonus_info = json_decode($teambonus['value'], true);
        return $teambonus_info;
    }

    /*
     * 删除队长
     */
    public function deleteAgent($uid)
    {
        // TODO Auto-generated method stub
        $member = new VslMemberModel();
        $member->startTrans();
        try {
            // 删除队长信息
            $data = [
                'is_team_agent'=>0
            ];
            $member->save($data,['uid'=>$uid]);
            $member->commit();
            return 1;
        } catch (\Exception $e) {
            $member->rollback();
            return $e->getMessage();
        }
    }

    /*
     * 订单商品团队分红计算
     */
    public function orderAgentBonus($params) 
    {
        $base_info = $this->getTeamBonusSite($params['website_id']);
        $set_info = $this->getSettlementSite($params['website_id']);
        $order_goods = new VslOrderGoodsModel();
        $order = new VslOrderModel();
        $order_info = $order->getInfo(['order_id'=>$params['order_id']],'bargain_id,group_id,presell_id');
        $order_goods_info = $order_goods->getInfo(['order_goods_id'=>$params['order_goods_id'],'order_id'=>$params['order_id']]);
        $cost_price = $order_goods_info['cost_price']*$order_goods_info['num'];//商品成本价
        $price = $order_goods_info['real_money'];//商品实际支付金额
        $promotion_price = $order_goods_info ['price']*$order_goods_info['num'];//商品销售价
        $original_price = $order_goods_info ['market_price']*$order_goods_info['num'];//商品原价
        // $profit_price = $promotion_price-$cost_price-$order_goods_info['profile_price']*$order_goods_info['num']+$order_goods_info['adjust_money'];//商品利润价
        // $profit_price = $promotion_price-$cost_price;//商品利润价
        $profit_price = $price-$cost_price;//商品利润价
        
        if($profit_price<0){
            $profit_price = 0;
        }
        
        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id'=>$order_goods_info['goods_id']]);
        $addonsConfigService = new AddonsConfigService();
        $seckill = getAddons('seckill',$params['website_id']);
        $seckill_rule =  $addonsConfigService ->getAddonsConfig("seckill",$params['website_id']);
        $seckill_value = json_decode($seckill_rule['value'],true);
        $seckill_bonus_val = json_decode($seckill_value['bonus_val'],true);
        $bargain = getAddons('bargain',$goods_info['website_id']);
        $bargain_rule =  $addonsConfigService ->getAddonsConfig("bargain", $goods_info['website_id']);
        $bargain_value = json_decode($bargain_rule['value'],true);
        $bargain_bonus_val = json_decode($bargain_value['bonus_val'],true);
        $order_bargain_id = $order_info['bargain_id'];
        $groupshopping = getAddons('groupshopping',$params['website_id']);
        $groupshopping_rule =  $addonsConfigService ->getAddonsConfig("groupshopping",$params['website_id']);
        $groupshopping_value = json_decode($groupshopping_rule['value'],true);
        $groupshopping_goods_info = $order_info['group_id'];
        $presell_goods_info = $order_goods_info['presell_id'];
        $presell = getAddons('presell',$goods_info['website_id']);
        $presell_rule =  $addonsConfigService ->getAddonsConfig("presell",$goods_info['website_id']);
        $presell_value = json_decode($presell_rule['value'],true);
        $team_bonus = '';
        $bargain_goods  = 0;
        $seckill_goods  = 0;
        $groupshopping_goods  = 0;
        $presell_goods  = 0;
        if($bargain==1 && $bargain_value['is_team_bonus']==1 && $order_bargain_id){//砍价是否参与分销分红、分销分红规则
            $bargain_goods = 1;
            if($bargain_value['rule_bonus']==1){//有独立分销规则
                $team_bonus = $bargain_bonus_val['team_bonus'];
            }
        }
        if($seckill==1 && $seckill_value['is_team_bonus']==1 && $order_goods_info['seckill_id']){//该商品参与秒杀
            $seckill_goods  = 1;
            if($seckill_value['rule_bonus']==1){//有独立分红规则
                $team_bonus = $seckill_bonus_val['team_bonus'];
            }
        }
        if($groupshopping==1 && $groupshopping_value['is_team_bonus']==1 && $groupshopping_goods_info){//该商品参与拼团
            $groupshopping_goods  = 1;
            if($groupshopping_value['rule_bonus']==1){//有独立分红规则
                $team_bonus = $groupshopping_value['team_bonus'];
            }
        }
        if($presell==1 && $presell_value['is_team_bonus']==1 && $presell_goods_info){//该商品参与预售
            $presell_goods  = 1;
            if($presell_value['rule_bonus']==1){//有独立分红规则
                $team_bonus = $presell_value['team_bonus'];
            }
        }
        if($goods_info['is_bonus_team']==1){//该商品参与团队分红
            if($goods_info['bonus_rule']==1){//有独立分红规则
                $goods_info['bonus_rule_val'] = json_decode(htmlspecialchars_decode($goods_info['bonus_rule_val']),true);
                $team_bonus = $goods_info['bonus_rule_val']['team_bonus'];
            }
        }
        $poundage = $set_info['bonus_poundage']/100;//分红比例
        
        $member = new VslMemberModel();
        $buyer_info = $member->getInfo(['uid'=>$params['buyer_id'],'isdistributor'=>2],'*');
        $arr =[];
        $agent_data = $this->get_parent_id($arr,$params['buyer_id'],1);//一条线上的队长信息
        
        $level = new AgentLevelModel();
        if($agent_data){
            if($goods_info['is_bonus_team']==1 || $seckill_goods==1 || $groupshopping_goods==1 || $presell_goods==1 || $bargain_goods==1) {
                if ($base_info['is_use'] == 1 && $base_info['gradation_status'] == 2) {//是否开启团队分红并且没有开启级差
                    $bonus_calculation = $set_info['bonus_calculation'];//计算节点（商品价格）
                    
                    foreach ($agent_data['agent_list'] as $k => $v) {
                        if ($v == $params['buyer_id']) {
                            if ($base_info['purchase_type'] == 2) {//未开启内购但当前购买者是队长
                                continue;
                            }
                        }
                        $level_id = $member->getInfo(['uid' => $v, 'is_team_agent' => 2], 'team_agent_level_id')['team_agent_level_id'];//等级id
                        $number = $member->getCount(['team_agent_level_id' => $level_id,'isdistributor'=>2, 'is_team_agent' => 2, 'uid' => ['in', implode(',', $agent_data['agent_list'])]]);//对应等级的人数
                        if($number>1 && $level_id==$buyer_info['team_agent_level_id'] && $buyer_info['is_team_agent']==2 && $base_info['purchase_type'] == 2){
                            $number = $number-1;
                        }
                        $ratio = $level->getInfo(['id' => $level_id], 'ratio')['ratio'] / 100;
                        if($team_bonus!=''){
                            $real_ratio = $team_bonus/100;//分红比例
                        }else{
                            $real_ratio = $ratio;
                        }
                        if ($bonus_calculation == 1) {//实际付款金额
                            if($presell_goods_info){
                                $price = $promotion_price;
                            }
                            $data['bonus'] = twoDecimal($price * $real_ratio * $poundage / $number);
                        }
                        if ($bonus_calculation == 2) {//商品原价
                            $data['bonus'] = twoDecimal($original_price * $real_ratio* $poundage / $number);
                        }
                        if ($bonus_calculation == 3) {//商品销售价
                            $data['bonus'] = twoDecimal($promotion_price * $real_ratio * $poundage / $number);
                        }
                        if ($bonus_calculation == 4) {//商品成本价
                            $data['bonus'] = twoDecimal($cost_price * $real_ratio * $poundage / $number);
                        }
                        if ($bonus_calculation == 5) {//商品利润价
                            $data['bonus'] = twoDecimal($profit_price * $real_ratio * $poundage / $number);
                        }
                        
                        $bonus = new VslOrderBonusModel();
                        try{
                            $data1 = [
                                'order_id' => $params['order_id'],
                                'order_goods_id' => $params['order_goods_id'],
                                'buyer_id' => $order_goods_info['buyer_id'],
                                'website_id' => $params['website_id'],
                                'bonus' => $data['bonus'],
                                'from_type' => 3,
                                'uid' => $v
                            ];
                            $bonus->save($data1);
                            $bonus->commit();
                        }catch (\Exception $e) {
                            $bonus->rollback();
                            return $e->getMessage();
                        }
                    }
                }
                if ($base_info['is_use'] == 1 && $base_info['gradation_status'] == 1) {//是否开启团队分红并且开启级差
                    //开启级差 并且未开启内购 并且本人是队长 需要去除队长本人
                    if(count($agent_data['level_info']) > 1 && $base_info['purchase_type'] == 2 && $buyer_info['is_team_agent'] == 2.){
                        
                        array_shift($agent_data['level_info']);
                        array_shift($agent_data['level_id']);
                        array_shift($agent_data['weight']);
                    }
                    $bonus_calculation = $set_info['bonus_calculation'];//计算节点（商品价格）
                    $arr = [];
                    foreach ($agent_data['level_id'] as $k1 => $v1){
                        $arr[] = $v1;
                    }
                    $arr = array_unique($arr);
                    $arr1 = array_values($arr);
                    $key = array_keys($arr);
                    $top = 1;
                    $arr2 = array(); //平级奖数组
                    $new_array = array();
                   
                    foreach ($agent_data['level_info'] as $k => $v) {
                        if(in_array($k,$key)){
                            $real_uid = $v['uid'];
                           
                            if ($v['uid'] == $params['buyer_id']) {
                                if ($base_info['purchase_type'] == 2) {//未开启内购
                                    continue;
                                }
                            }
                            $level_info = $level->getInfo(['id' => $v['team_agent_level_id'], 'from_type' => 3], '*');
                            $ratio = $level_info['ratio'];//当前比例
                            $weight = $level_info['weight'];//当前比例权重
                            if($team_bonus!=''){
                                $ratio = $team_bonus;//分红比例
                            }
                            $now_key = array_search($arr[$k],$arr1);
                           
                            $prev = 0;
                            if($now_key>=1){
                                $prev = $arr1[$now_key-1];
                            }
                            
                            if($prev){
                                $lower_ratio = $level->getInfo(['id'=>$prev]);//下级比例
                                $lower_weight = $lower_ratio['weight'];//当前下级比例权重
                               
                                if($weight>$lower_weight){
                                    if($ratio> $lower_ratio['ratio']){
                                        $real_ratio = ($ratio-$lower_ratio['ratio'])/100;//当前比例减去下级比例
                                    }else{
                                        $real_ratio = 0;
                                    }
                                    if($team_bonus!=''){
                                        $real_ratio = 0;//存在独立分红比例
                                    }
                                }else{
                                    continue;
                                }
                            }else{
                                $real_ratio = $ratio/100;//下级比例不存在
                                if(max($agent_data['weight'])==$weight){
                                    //最后一级权重 获取剩余会员
                                    if($k != end($agent_data['level_info'])) {
                                        // 不是最后一项
                                        //拆分数组
                                        debugLog($k+1,'<==团队分红k==>');
                                       
                                        $new_array = array_chunk($agent_data['level_info'], $k+1);
                                    } 
                                    $top = 2;
                                }
                            }
                           
                            if ($bonus_calculation == 1) {//实际付款金额
                                if($presell_goods_info){
                                    $price = $promotion_price;
                                }
                                $data['bonus'] = twoDecimal($price * $real_ratio * $poundage);
                            }
                            if ($bonus_calculation == 2) {//商品原价
                                $data['bonus'] = twoDecimal($original_price * $real_ratio * $poundage);
                            }
                            if ($bonus_calculation == 3) {//商品销售价
                                $data['bonus'] = twoDecimal($promotion_price * $real_ratio * $poundage);
                            }
                            if ($bonus_calculation == 4) {//商品成本价
                                $data['bonus'] = twoDecimal($cost_price * $real_ratio * $poundage);
                            }
                            if ($bonus_calculation == 5) {//商品利润价
                                $data['bonus'] = twoDecimal($profit_price * $real_ratio * $poundage);
                            }
                            $bonus = new VslOrderBonusModel();
                            try{
                                $data1 = [
                                    'order_id' => $params['order_id'],
                                    'order_goods_id' => $params['order_goods_id'],
                                    'buyer_id' => $order_goods_info['buyer_id'],
                                    'website_id' => $params['website_id'],
                                    'bonus' => $data['bonus'],
                                    'from_type' => 3,
                                    'uid' => $real_uid
                                ];
                                $bonus->save($data1);
                                $bonus->commit();
                            }catch (\Exception $e) {
                                $bonus->rollback();
                                return $e->getMessage();
                            }
                            if($top == 2){
                               break;
                            }
                        }else{
                            //开启内购 而且本人是队长 上级跟本人同级 需先去除该上级
                            $check_uid = 0;
                            //获取购买者本人信息
                            $buyer_info2 = $member->getInfo(['uid'=>$params['buyer_id']],'referee_id,team_agent_level_id');
                            
                            $weight1 = -1;
                            $weight2 = -1;
                            if($buyer_info2['referee_id'] && $buyer_info2['team_agent_level_id']){
                                //获取本人级别
                                $check_info1 = $level->getInfo(['id' => $buyer_info2['team_agent_level_id'], 'from_type' => 3], 'weight');
                                $weight1 = $check_info1['weight'];
                                $check_user_info2 = $member->getInfo(['uid'=>$buyer_info2['referee_id']],'team_agent_level_id');
                                if($check_user_info2 && $check_user_info2['team_agent_level_id']){
                                    $check_info2 = $level->getInfo(['id' => $check_user_info2['team_agent_level_id'], 'from_type' => 3], 'weight');
                                    $weight2 = $check_info2['weight'];
                                }
                            }
                            if($weight1 >= 0 && $weight2 >= 0 && $weight1 == $weight2 && $base_info['purchase_type'] == 2){
                                $check_uid = $buyer_info2['referee_id'];
                            }
                            //组装平级奖待发放人员
                            $arr_data = array(
                                'uid'=>$v['uid'],
                                'weight'=>$agent_data['weight'][$k],
                                'level_id'=>$agent_data['level_id'][$k],
                            );
                            if($check_uid != $v['uid'] && $agent_data['weight'][$k] >= $weight){
                                array_push($arr2,$arr_data);
                            }
                            //级差团队外 开始统计3级平级奖 -- 仅限3级内 --  团队id? 权重？
                            continue;
                        }
                    }
                   
                    if($new_array && count($new_array) > 1){
                        // debugLog($new_array[1],'<==团队分红new_array1==>');
                        // unset($new_array[0]);
                        // debugLog($new_array,'<==团队分红new_array12==>');
                        // foreach ($new_array[1] as $keys => $values) {
                        //     $arr_data = array(
                        //         'uid'=>$values['uid'],
                        //         'weight'=>$agent_data['weight'][$keys],
                        //         'level_id'=>$agent_data['level_id'][$keys],
                        //     );
                        //     array_push($arr2,$arr_data);
                        // }
                        foreach ($new_array as $key_f => $value_f) {
                            if($key_f == 0){
                                continue;
                            }
                            foreach ($value_f as $keys => $values) {
                                $arr_data = array(
                                    'uid'=>$values['uid'],
                                    'weight'=>$agent_data['weight'][$keys],
                                    'level_id'=>$agent_data['level_id'][$keys],
                                );
                                array_push($arr2,$arr_data);
                            }
                        }
                    }
                    if($arr2 && count($arr2) > 0 && $base_info['level_award']){ //开始处理平级奖
                        //获取当前价格
                        if ($bonus_calculation == 1) {//实际付款金额
                            if($presell_goods_info){
                                $price = $promotion_price;
                            }
                            $price = $price;
                        }
                        if ($bonus_calculation == 2) {//商品原价
                            $price = $original_price;
                        }
                        if ($bonus_calculation == 3) {//商品销售价
                            $price = $promotion_price;
                        }
                        if ($bonus_calculation == 4) {//商品成本价
                            $price = $cost_price;
                        }
                        if ($bonus_calculation == 5) {//商品利润价
                            $price = $profit_price;
                        }
                        //重新组装数组,每个等级保留前3个
                        $res = array();
                        foreach($arr2 as $v) {
                                $res[$v['level_id']][] = $v; 
                        }
                        $send_array = array();
                        foreach ($res as $key => $value) {
                            $save_array = array_chunk($value, 3);
                            array_push($send_array,$save_array[0]);
                         }
                        // $ids=array();
                        // foreach ($arr as $key=>$value){
                        //     //当id 存在ids 中
                        //     if(array_key_exists($value['id'],$ids)){
                        //         //判断是否已存在3个以上
                        //         if($ids[$value['id']]>=3){
                        //             //删掉数据
                        //             unset($arr[$key]);
                        //         }
                        //         $ids[$value['id']]=$ids[$value['id']]+1;
                        //     }else{
                        //         $ids[$value['id']]=1;
                        //     }
                        // }
                        foreach ($send_array as $key_s => $value_s) {
                            foreach ($value_s as $key_i => $value_i) {
                                $this_level_info = $level->getInfo(['id' => $value_i['level_id'], 'from_type' => 3], 'level_award1,level_award2,level_award3');
                                
                                $level_award_ratio = $key_i == 0 ? $this_level_info['level_award1'] : ( $key_i == 1 ? $this_level_info['level_award2'] : $this_level_info['level_award3'] );
                                $bonus = new VslOrderBonusModel();
                                //加多一个标识，标识是否平级奖
                                try{
                                    $data4 = [
                                        'order_id' => $params['order_id'],
                                        'order_goods_id' => $params['order_goods_id'],
                                        'buyer_id' => $order_goods_info['buyer_id'],
                                        'website_id' => $params['website_id'],
                                        'bonus' => twoDecimal($price * $level_award_ratio / 100),
                                        'from_type' => 3,
                                        'uid' => $value_i['uid'],
                                        'level_award' => $key_i + 1,
                                    ];
                                    $bonus->save($data4);
                                    $bonus->commit();
                                }catch (\Exception $e) {
                                    $bonus->rollback();
                                    return $e->getMessage();
                                 }
                            }
                        }
                    }
                }
            }
        }
    }

    public function get_parent_id($arr,$cid,$type=0,$tops = []){
        $member = new VslMemberModel();
        $level = new VslAgentLevelModel();
        if($type==1){
            $member_info = $member->getInfo(['uid'=>$cid],'*');
        }else{
            $member_info = $member->getInfo(['uid'=>$cid,'isdistributor'=>2],'*');
        }
        $level_weight = $level->getInfo(['id'=>$member_info['team_agent_level_id']],'weight')['weight'];
        if($member_info['is_team_agent']==2){
            if(empty($arr['agent_list'])){
                $arr['agent_list'][] = $member_info['uid'];
            }else{
                if(is_array($arr['agent_list'])){
                    array_push($arr['agent_list'],$member_info['uid']);
                }
            }
            if (empty($arr['level_id'])){
                $arr['weight'][] = $level_weight;
                $arr['level_id'][] = $member_info['team_agent_level_id'];
                $arr['level_info'][] = $member_info;
            }else{
                if(is_array($arr['level_id'])){
                    array_push($arr['weight'],$level_weight);
                    array_push($arr['level_id'],$member_info['team_agent_level_id']);
                    array_push($arr['level_info'],$member_info);
                }
            }
        }
		array_push($tops,$cid);
		if(in_array($member_info['referee_id'], $tops)){
			debugLog($member_info['referee_id'],'重复上级==>');
		}
        if($member_info['referee_id'] && $cid!=$member_info['referee_id'] && !in_array($member_info['referee_id'], $tops)){
            return $this->get_parent_id($arr,$member_info['referee_id'],$type,$tops);
        }else{
            return $arr;
        }
    }
    /*
     * 添加分红账户流水表
     */
    public function addTeamBonus($params)
    {
        $agent_account = new VslAgentAccountRecordsModel();
        $records_no = 'TBS'.time() . rand(111, 999);
        $old_order_id = $params['order_id'];
        if($params['order_id']){
            $order = new VslOrderModel();
            $params['order_id'] = $order->getInfo(['order_id'=>$params['order_id']],'order_no')['order_no'];
        }
        $data_records = array();
        //更新对应分红流水
        if($params['status']==1){
            $data_records = array(
                'uid' => $params['uid'],
                'data_id' => $params['order_id'],
                'records_no' =>  $records_no,
                'bonus' => abs($params['bonus']),
                'from_type' => 1,//订单完成
                'bonus_type' => 3,//团队分红
                'website_id' => $params['website_id'],
                'text' => '订单完成,待发放分红增加,冻结分红减少',
                'create_time' => time(),
                );
            }
            if($params['status']==2){
                $records_count = $agent_account->getInfo(['data_id'=> $params['order_id']],'*');
                if($records_count){
                    $data_records = array(
                        'uid' => $params['uid'],
                        'data_id' => $params['order_id'],
                        'website_id' => $params['website_id'],
                        'records_no' =>  $records_no,
                        'bonus' => -($params['bonus']),
                        'text' => '订单退款,冻结分红减少',
                        'create_time' => time(),
                        'bonus_type' => 3,//团队分红
                        'from_type' => 2,//订单退款
                    );
                }
            }
            if($params['status']==3){
                    $data_records = array(
                        'uid' => $params['uid'],
                        'data_id' => $params['order_id'],
                        'website_id' => $params['website_id'],
                        'records_no' =>  $records_no,
                        'bonus' => +($params['bonus']),
                        'text' => '订单支付,冻结分红增加',
                        'create_time' => time(),
                        'bonus_type' => 3,//团队分红
                        'from_type' => 3,//订单支付成功
                    );
            }
            $agent_account->save($data_records);
            //变更该条记录状态
            if($params['status'] == 3){
                $agent_logs = new VslOrderBonusModel();
                $agent_logs->save(['pay_status'=>1],['id'=>$params['rec_bonus_id']]);
            }
            if($params['status'] == 1){
                $agent_logs = new VslOrderBonusModel();
                $agent_logs->save(['cal_status' => 1], ['order_id'=>$old_order_id,'uid'=>$params['uid'],'from_type'=>3]);
            }
            $account_statistics = new VslBonusAccountModel();
            $account = new VslAccountModel();
            //更新对应分红账户和平台账户余额
            $count = $account_statistics->getInfo(['uid'=> $params['uid'],'from_type'=>3],'*');//分红账户
            if(empty($count)){
                $account_statistics->save(['website_id'=> $params['website_id'],'uid'=> $params['uid'],'from_type'=>3]);
            }
            $count = $account_statistics->getInfo(['uid'=> $params['uid'],'from_type'=>3],'*');//分红账户;
            $account_count = $account_statistics->getInfo(['website_id'=> $params['website_id']],'*');//平台账户
            if($params['status']==1){//订单完成，添加分红
                    //分红账户分红改变
                    if($count){
                        $account_data = array(
                            'ungrant_bonus' => $count['ungrant_bonus']+abs($params['bonus']),
                            'freezing_bonus' => $count['freezing_bonus']-abs($params['bonus']),
                            'total_bonus'=> $count['total_bonus']
                        );
                        $account_statistics->save($account_data,['uid'=> $params['uid'],'from_type'=>3]);
                    }
                //平台账户分红改变
                if($account_count){
                    $bonus_data = array(
                        'bonus' => $account_count['bonus']+$params['bonus'],
                    );
                    $account->save($bonus_data,['website_id'=> $params['website_id']]);
                }

                //添加对应的待分红相关的订单金额
                $order_bonus = new VslUnGrantBonusOrderModel();
                $order = $order_bonus->getInfo(['website_id'=>$params['website_id'],'from_type'=>3,'uid'=> $params['uid'],'grant_status'=>1,'order_id'=> $params['order_id']],'*');//未分红订单
                if($order){
                    $order_ungrant_bonus = array(
                        'bonus'=>$params['bonus']+$order['bonus'],
                    );
                    $order_bonus->save($order_ungrant_bonus,['website_id'=>$params['website_id'],'grant_status'=>1,'order_id'=>$params['order_id'],'uid'=> $params['uid'],'from_type'=>3]);
                }else{
                    $order_ungrant_bonus = array(
                        'grant_status'=>1,//未发放
                        'order_id'=>$params['order_id'],
                        'uid'=>$params['uid'],
                        'bonus'=>$params['bonus'],
                        'from_type'=>3,//团队分红
                        'website_id'=>$params['website_id']
                    );
                    $order_bonus->save($order_ungrant_bonus);
                }
                }
            if($params['status']==2){//订单退款完成，修改分红
                if($count){
                    $bonus_data = array(
                        'freezing_bonus' => $count['freezing_bonus']-$params['bonus'],
                        'total_bonus'=> $count['total_bonus']-abs($params['bonus'])
                    );
                    $account_statistics->save($bonus_data,['uid'=> $params['uid'],'from_type'=>3]);
                }
            }
            if($params['status']==3){//订单支付完成，分红改变
                //队长分红账户改变
                if($count){
                    $bonus_data = array(
                        'freezing_bonus' => $count['freezing_bonus']+$params['bonus'],
                        'total_bonus'=> $count['total_bonus']+abs($params['bonus'])
                    );
                    $account_statistics->save($bonus_data,['uid'=> $params['uid'],'from_type'=>3]);
                    //平台账户流水表
                    $shop = new ShopAccount();
                    $shop->addAccountRecords(0, $params['uid'], '订单支付团队分红', $params['bonus'], 22, $params['order_id'], '订单支付完成，账户分红增加',$params['website_id']);
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"freezing_teambonus","uid" => $params['uid'],"order_time" => time(),'bonus_money'=>$params['bonus']]);
                }
            }
    }
    /*
    * 团队分红自动发放
    */
    public function autoGrantTeamBonus($params){
        $basic_config = $this->getTeamBonusSite($params['website_id']);
        $config = $this->getSettlementSite($params['website_id']);
        if($basic_config['is_use']==1 && $config['withdrawals_check'] == 1){
            $order_grant = new VslUnGrantBonusOrderModel();
            $uids = array_unique($order_grant->Query(['from_type'=>3,'grant_status'=>1,'website_id'=>$params['website_id']],'uid'));
            $grant_time = time();
            $sn =  md5(uniqid(rand()));
            $up_grant = new VslGrantTimeModel();
            $up_grant_time = $up_grant->getInfo(['website_id'=>$params['website_id'],'from_type'=>3],'time,id');
            if($config['limit_time'] && $config['limit_time']!=100){
                $limit_time = $config['limit_time']*24*3600;
                $now_time = strtotime(date('Y-m-d',time()));
                $time = $up_grant_time['time']+$limit_time;
                if($up_grant_time && $up_grant_time['time']){//如果存在上次发放时间
                    $rel_time = strtotime(date('Y-m-d',$time));
                }else{
                    $rel_time = 0;
                }
                foreach($uids as $k=>$v) {
                    $bonus = new VslBonusAccountModel();
                    $grant = new VslBonusGrantModel();
                    $bonus_info = $bonus->getInfo(['uid' => $v,'from_type'=>3], '*');
                    //自动分红
                    if ($rel_time == $now_time) {
                        //添加分红发放流水
                        $data = array(
                            "grant_no" =>'tb'.getSerialNo(),
                            "uid" => $v,
                            "bonus" => $bonus_info['ungrant_bonus'],
                            "grant_time" => $grant_time,
                            "website_id" => $bonus_info['website_id'],
                            "from_type" => 3,
                            "type" => $params['type'],
                            "sn" => $sn
                        );
                        $grant->save($data);
                        $data_info = array(
                            "uid" => $v,
                            "data_id" => $data['grant_no'],
                            "bonus" => $bonus_info['ungrant_bonus'],
                            "website_id" => $bonus_info['website_id'],
                        );
                        //分红发放到账户余额
                        $data_info['ungrant_bonus'] = $data_info['bonus'];
                        $data_info['real_ungrant_bonus'] = $data_info['ungrant_bonus'];
                        if($config['poundage']){
                            $data_info['real_ungrant_bonus'] =abs($data_info['ungrant_bonus'])-twoDecimal($config['poundage']*abs($data_info['ungrant_bonus'])/100);
                            if($config['withdrawals_end'] && $config['withdrawals_begin']){
                                if(abs($data_info['ungrant_bonus'])<=$config['withdrawals_end'] && abs($data_info['ungrant_bonus'])>=$config['withdrawals_begin'] ){
                                    $data_info['real_ungrant_bonus'] = $data_info['ungrant_bonus'];
                                }
                            }
                        }
                        $res = $this->addGrantBonus($data_info);
                        if ($res) {//分红发放完成后更新未发放订单状态
                            $order_grant = new VslUnGrantBonusOrderModel();
                            $order_grant->save(['grant_status' => 2], ['uid' => $v,'from_type'=>3]);
                        }
                    }else if(empty($up_grant_time['time'])){
                        //添加分红发放流水
                        $data = array(
                            "grant_no" => getSerialNo(),
                            "uid" => $v,
                            "bonus" => $bonus_info['ungrant_bonus'],
                            "grant_time" => $grant_time,
                            "website_id" => $bonus_info['website_id'],
                            "from_type" => 3,
                            "type" => 2,
                            "sn" => $sn
                        );
                        $grant->save($data);
                        $data_info = array(
                            "uid" => $v,
                            "data_id" => $data['grant_no'],
                            "bonus" => $bonus_info['ungrant_bonus'],
                            "website_id" => $bonus_info['website_id'],
                        );
                        //分红发放到账户余额
                        $data_info['ungrant_bonus'] = $data_info['bonus'];
                        $data_info['real_ungrant_bonus'] = $data_info['ungrant_bonus'];
                        if($config['poundage']){
                            $data_info['real_ungrant_bonus'] =abs($data_info['ungrant_bonus'])-twoDecimal($config['poundage']*abs($data_info['ungrant_bonus'])/100);
                            if($config['withdrawals_end'] && $config['withdrawals_begin']){
                                if(abs($data_info['ungrant_bonus'])<=$config['withdrawals_end'] && abs($data_info['ungrant_bonus'])>=$config['withdrawals_begin'] ){
                                    $data_info['real_ungrant_bonus'] = $data_info['ungrant_bonus'];
                                }
                            }
                        }
                        $res = $this->addGrantBonus($data_info);
                        if ($res) {//分红发放完成后更新未发放订单状态
                            $order_grant = new VslUnGrantBonusOrderModel();
                            $order_grant->save(['grant_status' => 2], ['uid' => $v,'from_type'=>3]);
                        }
                    }
                }
            }
            if($config['limit_time'] && $config['limit_date'] && $config['limit_time']==100){
                $date = date('d');
                $firstday = date('Y-m-01', strtotime(date("Y-m-d")));
                $lastday = date('d', strtotime("$firstday +1 month -1 day"));
                if($date==$config['limit_date'] || $lastday<=$config['limit_date']){
                    foreach($uids as $k=>$v) {
                        $bonus = new VslBonusAccountModel();
                        $grant = new VslBonusGrantModel();
                        $bonus_info = $bonus->getInfo(['uid' => $v,'from_type'=>3], '*');
                        //添加分红发放流水
                        $data = array(
                                "grant_no" =>'tb'.getSerialNo(),
                                "uid" => $v,
                                "bonus" => $bonus_info['ungrant_bonus'],
                                "grant_time" => $grant_time,
                                "website_id" => $bonus_info['website_id'],
                                "from_type" => 3,
                                "type" => $params['type'],
                                "sn" => $sn
                            );
                        $grant->save($data);
                        $data_info = array(
                                "uid" => $v,
                                "data_id" => $data['grant_no'],
                                "bonus" => $bonus_info['ungrant_bonus'],
                                "website_id" => $bonus_info['website_id'],
                            );
                        //分红发放到账户余额
                        $data_info['ungrant_bonus'] = $data_info['bonus'];
                        $data_info['real_ungrant_bonus'] = $data_info['ungrant_bonus'];
                        if($config['poundage']){
                                $data_info['real_ungrant_bonus'] =abs($data_info['ungrant_bonus'])-twoDecimal($config['poundage']*abs($data_info['ungrant_bonus'])/100);
                                if($config['withdrawals_end'] && $config['withdrawals_begin']){
                                    if(abs($data_info['ungrant_bonus'])<=$config['withdrawals_end'] && abs($data_info['ungrant_bonus'])>=$config['withdrawals_begin'] ){
                                        $data_info['real_ungrant_bonus'] = $data_info['ungrant_bonus'];
                                    }
                                }
                            }
                        $res = $this->addGrantBonus($data_info);
                        if ($res) {//分红发放完成后更新未发放订单状态
                            $order_grant = new VslUnGrantBonusOrderModel();
                            $order_grant->save(['grant_status' => 2], ['uid' => $v,'from_type'=>3]);
                        }
                    }
                }
            }
            if ($res) {
                //添加发放时间记录表
                $data_time = array(
                    "time" => $grant_time,
                    "website_id" => $params['website_id'],
                    "from_type" => 3
                );
                if ($up_grant_time && $up_grant_time['id']) {
                    $up_grant->save($data_time, ['id' => $up_grant_time['id']]);
                } else {
                    $up_grant->save($data_time);
                }
                return $res;
            }
        }
    }
    /*
    * 团队分红手动发放
    */
    public function grantTeamBonus($type){
        $config = $this->getTeamBonusSite($this->website_id);
        $set_config = $this->getSettlementSite($this->website_id);
        if($config['is_use']==1 && $type==1){
            $order_grant = new VslUnGrantBonusOrderModel();
            $uids = array_unique($order_grant->Query(['from_type'=>3,'grant_status'=>1,'website_id'=>$this->website_id],'uid'));
            $grant_time = time();
            $sn =  md5(uniqid(rand()));
            $up_grant = new VslGrantTimeModel();
            $up_grant_time = $up_grant->getInfo(['website_id'=>$this->website_id,'from_type'=>3],'time,id');
            foreach($uids as $k=>$v){
                $bonus = new VslBonusAccountModel();
                $grant = new VslBonusGrantModel();
                $bonus_info = $bonus->getInfo(['uid'=>$v,'from_type'=>3],'*');
                //手动分红
                //添加分红流水
                $data = array(
                    "grant_no"=>'tb'.getSerialNo(),
                    "uid"=>$v,
                    "bonus"=>$bonus_info['ungrant_bonus'],
                    "grant_time"=>$grant_time,
                    "website_id"=>$bonus_info['website_id'],
                    "from_type"=>3,
                    "type"=>$type,
                    "sn" => $sn
                );
                $grant->save($data);
                $data_info = array(
                    "uid"=>$v,
                    "data_id"=>$data['grant_no'],
                    "bonus"=>$bonus_info['ungrant_bonus'],
                    "website_id"=>$bonus_info['website_id'],
                );
                //分红发放到账户余额(扣除个人所得税)
                $data_info['ungrant_bonus'] = $data_info['bonus'];
                $data_info['real_ungrant_bonus'] = $data_info['ungrant_bonus'];
                if($set_config['poundage']){
                    $data_info['real_ungrant_bonus'] =abs($data_info['ungrant_bonus'])-twoDecimal($set_config['poundage']*abs($data_info['ungrant_bonus'])/100);
                    if($set_config['withdrawals_end'] && $set_config['withdrawals_begin']){
                        if(abs($data_info['ungrant_bonus'])<=$set_config['withdrawals_end'] && abs($data_info['ungrant_bonus'])>=$set_config['withdrawals_begin'] ){
                            $data_info['real_ungrant_bonus'] = $data_info['ungrant_bonus'];
                        }
                    }
                }
                $res = $this->addGrantBonus($data_info);
                if($res){//手动分红发放完成后改变未发放订单状态
                    $order_grant = new VslUnGrantBonusOrderModel();
                    $order_grant->save(['grant_status'=>2],['uid'=>$v,'from_type'=>3]);
                }
            }
            if($res){
                //添加发放时间记录表
                $data_time = array(
                    "time"=>$grant_time,
                    "website_id"=>$this->website_id,
                    "from_type"=>3
                );
                if($up_grant_time && $up_grant_time['id']){
                    $up_grant->save($data_time,['id'=>$up_grant_time['id']]);
                }else{
                    $up_grant->save($data_time);
                }
                return $res;
            }
        }
    }
    /**
     * 分红发放到账户余额
     */
    public function addGrantBonus($data_info){
        $bonus_withdraw = new VslMemberAccountRecordsModel();
        try{
            $data1 = array(
                'records_no' => getSerialNo(),
                'uid' => $data_info['uid'],
                'account_type' => 2,
                'number'   => $data_info['real_ungrant_bonus'],
                'data_id' => $data_info['data_id'],
                'from_type' => 13,
                'text' => '队长分红提现到余额',
                'create_time' => time(),
                'website_id' => $data_info['website_id']
            );
            $res = $bonus_withdraw->save($data1);//添加会员流水
            $acount = new ShopAccount();
            $income_tax =$data_info['ungrant_bonus']-$data_info['real_ungrant_bonus'];
            $acount->addAccountRecords(0, $data_info['uid'], "团队分红发放，个人所得税!",$income_tax, 24, $data_info['data_id'], '团队分红发放，个人所得税增加',$data_info['website_id']);//添加平台流水
            if($res){
                $member_account = new VslMemberAccountModel();
                $account_info = $member_account->getInfo(['uid'=>$data_info['uid']],'*');
                try{
                    if($account_info){
                        $data2 = array(
                            'uid' => $data_info['uid'],
                            'balance' => $data_info['real_ungrant_bonus']+$account_info['balance']
                        );
                        $res1 = $member_account->save($data2,['uid'=>$data_info['uid']]);//更新会员账户余额
                    }else{
                        $data2 = array(
                            'uid' => $data_info['uid'],
                            'balance' => $data_info['real_ungrant_bonus'],
                            'website_id' => $data_info['website_id']
                        );
                        $res2 = $member_account->save($data2);//添加会员账户余额
                    }
                    if($res1 || $res2){//更新分红账户
                        //添加分红账户流水
                        $records_no = 'TBS'.time() . rand(111, 999);
                        $agent_account = new VslAgentAccountRecordsModel();
                        $data_account = array(
                            'uid' => $data_info['uid'],
                            'data_id' => $data_info['data_id'],
                            'records_no' => $records_no,
                            'website_id' => $data_info['website_id'],
                            'bonus' => abs($data_info['ungrant_bonus']),
                            'text' => '队长分红发放到账户余额，已发放分红增加，待发放分红减少',
                            'create_time' => time(),
                            'bonus_type' => 3,//团队分红
                            'from_type' => 4,//分红发放成功
                        );
                        $agent_account->save($data_account);
                        $bonus_account = new VslBonusAccountModel();
                        $bonus_account_info = $bonus_account->getInfo(['uid'=>$data_info['uid'],'from_type'=>3],'*');
                        try{
                            $data3 = array(
                                'ungrant_bonus'=>$bonus_account_info['ungrant_bonus']-abs($data_info['ungrant_bonus']),
                                'grant_bonus'=>$bonus_account_info['grant_bonus']+abs($data_info['ungrant_bonus'])
                            );
                            $bonus_account->save($data3,['uid'=>$data_info['uid'],'from_type'=>3]);//更新分红账户
                            runhook("Notify", "sendCustomMessage", ["messageType"=>"teambonus_payment","uid" =>$data_info['uid'],"pay_time" => time(),'bonus_money'=>$data_info['ungrant_bonus']]);
                            $bonus_account->commit();
                            return 1;
                        }catch (\Exception $e)
                        {
                            $bonus_account->rollback();
                            return $e->getMessage();
                        }
                    }
                    $member_account->commit();
                }catch (\Exception $e)
                {
                    $member_account->rollback();
                    return $e->getMessage();
                }
            }
            $bonus_withdraw->commit();
        }catch (\Exception $e)
        {
            $bonus_withdraw->rollback();
            return $e->getMessage();
        }
    }
    /*
     * 订单完成后队长等级升级
     */
    public function updateAgentLevelInfo($uid)
    {
        $member = new VslMemberModel();
        $level = new AgentLevelModel();
        $agent = $member->getInfo(['uid'=>$uid],'*');
        $base_info = $this->getTeamBonusSite($agent['website_id']);
        $order = new VslOrderModel();
        $order_goods = new VslOrderGoodsModel();
        if($base_info['teamagent_grade']==1){//开启跳级
            if($agent['is_team_agent']==2){
                $getAgentInfo = $this->getAgentLowerInfo($uid);//当前队长的详情信息
                $default_level_name = $level->getInfo(['id'=>$agent['team_agent_level_id']],'level_name')['level_name'];
                $level_weight = $level->Query(['id'=>$agent['team_agent_level_id']],'weight');//当前队长的等级权重
                $level_weights = $level->Query(['weight'=>['>',implode(',',$level_weight)],'from_type'=>3,'website_id'=>$agent['website_id']],'weight');//当前队长的等级权重的上级权重
                if ($level_weights) {
                    sort($level_weights);
                    foreach ($level_weights as $k => $v) {
                        $level_infos = $level->getInfo(['weight' => $v,'from_type'=>3,'website_id'=>$agent['website_id']]);//比当前队长等级的权重高的等级信息
                        $ratio = $level_infos['ratio'].'%';
                        //判断是否购买过指定商品
                        $goods_info = [];
                        if ($level_infos['goods_id']) {
                            $goods_id = $order_goods->Query(['goods_id' => $level_infos['goods_id'], 'buyer_id' => $uid], 'order_id');
                            if ($goods_id && $agent['down_up_team_level_time']) { //发生降级后 订单完成时间需大于降级时间
                                $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4,'finish_time'=>[">",$agent['down_up_team_level_time']]], '*');
                            }else if($goods_id){
                                $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4], '*');
                            }

                        }
                        if($level_infos && $level_infos['upgrade_level']){
                            if($level_infos['down_up_team_level_time']){
                                $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id'],'reg_time'=>[">",$agent['down_up_team_level_time']]]);//该等级指定推荐等级人数
                            }else{
                                $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id']]);//该等级指定推荐等级人数
                            }
                            
                        }else{
                            $low_number = 0;
                        }
                        if ($level_infos['upgradetype'] == 1) {//是否开启自动升级
                            $conditions = explode(',', $level_infos['upgradeconditions']);
                            $result = [];
                            foreach ($conditions as $k1 => $v1) {
                                switch ($v1) {
                                    case 1:
                                        $selforder_money = $level_infos['pay_money'];
                                        if ($getAgentInfo['selforder_money'] >= $selforder_money) {
                                            $result[] = 1;//自购订单金额
                                        }
                                        break;
                                    case 2:
                                        $group_number= $level_infos['group_number'];
                                        if ($getAgentInfo['agentcount'] >= $group_number) {
                                            $result[] = 2;//团队人数
                                        }
                                        break;
                                    case 3:
                                        $one_number = $level_infos['one_number'];
                                        if ($getAgentInfo['one_number1'] >= $one_number) {
                                            $result[] = 3;//一级分销商满
                                        }
                                        break;
                                    case 4:
                                        $two_number = $level_infos['two_number'];
                                        if ($getAgentInfo['two_number1'] >= $two_number) {
                                            $result[] = 4;//二级分销商满
                                        }
                                        break;
                                    case 5:
                                        $three_number = $level_infos['three_number'];
                                        if ($getAgentInfo['three_number1'] >= $three_number) {
                                            $result[] = 5;//三级分销商满
                                        }
                                        break;
                                    case 6:
                                        $order_money = $level_infos['order_money'];
                                        if ($getAgentInfo['order_money'] >= $order_money) {
                                            $result[] = 6;//分销订单金额达
                                        }
                                        break;
                                    case 7:
                                        if ($goods_info) {
                                            $result[] = 7;//指定商品
                                        }
                                        break;
                                    case 8:
                                        $offline_number = $level_infos['number'];
                                        if ($getAgentInfo['agentcount1'] >= $offline_number) {
                                            $result[] = 8;//客户人数
                                        }
                                        break;
                                    case 9:
                                        $level_number = $level_infos['level_number'];
                                        if ($low_number>= $level_number) {
                                            $result[] = 9;//指定等级人数
                                        }
                                        break;
                                    case 11:
                                        $up_order_money = $level_infos['up_team_money'];
                                        if ($getAgentInfo['up_team_money']>= $up_order_money) {
                                            $result[] = 11;//指定等级人数
                                        }
                                        break;
                                }
                            }
                            if ($level_infos['upgrade_condition'] == 1) {//升级条件类型（满足所有勾选条件）
                                if (count($result) == count($conditions)) {
                                    runhook("Notify", "sendCustomMessage", ['messageType'=>'team_upgrade_notice','uid' => $uid,'present_grade'=>$level_infos['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'upgrade_time' => time()]);//升级
                                    $member = new VslMemberModel();
                                    $member->save(['team_agent_level_id' => $level_infos['id'], 'up_team_level_time' => time(), 'down_up_team_level_time' => ''], ['uid' => $uid]);
                                }
                            }
                            if ($level_infos['upgrade_condition'] == 2) {//升级条件类型（满足勾选条件任意一个即可）
                                if (count($result) >= 1) {
                                    runhook("Notify", "sendCustomMessage", ['messageType'=>'team_upgrade_notice','uid' => $uid,'present_grade'=>$level_infos['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'upgrade_time' => time()]);//升级
                                    $member = new VslMemberModel();
                                    $member->save(['team_agent_level_id' => $level_infos['id'], 'up_team_level_time' => time(), 'down_up_team_level_time' => ''], ['uid' => $uid]);
                                }
                            }
                        }
                    }
                }
            }
        }
        if($base_info['teamagent_grade']==2){//未开启跳级
            if($agent['is_team_agent']==2){
                $getAgentInfo = $this->getAgentLowerInfo($uid);//当前队长的详情信息
                $default_level_name = $level->getInfo(['id'=>$agent['team_agent_level_id']],'level_name')['level_name'];
                $level_weight = $level->Query(['id'=>$agent['team_agent_level_id']],'weight');//当前队长的等级权重
                $level_weights = $level->Query(['weight'=>['>',implode(',',$level_weight)],'from_type'=>3,'website_id'=>$agent['website_id']],'weight');//当前队长的等级权重的上级权重
                if ($level_weights) {
                    sort($level_weights);
                    foreach ($level_weights as $k => $v) {
                        if($k > 0){
                            break;
                        }
                        $level_infos = $level->getInfo(['weight' => $v,'from_type'=>3,'website_id'=>$agent['website_id']]);//比当前队长等级的权重高的等级信息
                        $ratio = $level_infos['ratio'].'%';
                        //判断是否购买过指定商品
                        $goods_info = [];
                        if ($level_infos['goods_id']) {
                            $goods_id = $order_goods->Query(['goods_id' => $level_infos['goods_id'], 'buyer_id' => $uid], 'order_id');
                            if ($goods_id && $agent['down_up_team_level_time']) { //发生降级后 订单完成时间需大于降级时间
                                $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4,'finish_time'=>[">",$agent['down_up_team_level_time']]], '*');
                            }else if($goods_id){
                                $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4], '*');
                            }
                        }
                        if($level_infos && $level_infos['upgrade_level']){
                            if($level_infos['down_up_team_level_time']){
                                $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id'],'reg_time'=>[">",$agent['down_up_team_level_time']]]);//该等级指定推荐等级人数
                            }else{
                                $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$agent['website_id']]);//该等级指定推荐等级人数
                            }
                        }else{
                            $low_number = 0;
                        }
                        if ($level_infos['upgradetype'] == 1) {//是否开启自动升级
                            $conditions = explode(',', $level_infos['upgradeconditions']);
                            $result = [];
                            foreach ($conditions as $k1 => $v1) {
                                switch ($v1) {
                                    case 1:
                                        $selforder_money = $level_infos['pay_money'];
                                        if ($getAgentInfo['selforder_money'] >= $selforder_money) {
                                            $result[] = 1;//自购订单金额
                                        }
                                        break;
                                    case 2:
                                        $group_number= $level_infos['group_number'];
                                        if ($getAgentInfo['agentcount'] >= $group_number) {
                                            $result[] = 2;//团队人数
                                        }
                                        break;
                                    case 3:
                                        $one_number = $level_infos['one_number'];
                                        if ($getAgentInfo['one_number1'] >= $one_number) {
                                            $result[] = 3;//一级分销商满
                                        }
                                        break;
                                    case 4:
                                        $two_number = $level_infos['two_number'];
                                        if ($getAgentInfo['two_number1'] >= $two_number) {
                                            $result[] = 4;//二级分销商满
                                        }
                                        break;
                                    case 5:
                                        $three_number = $level_infos['three_number'];
                                        if ($getAgentInfo['three_number1'] >= $three_number) {
                                            $result[] = 5;//三级分销商满
                                        }
                                        break;
                                    case 6:
                                        $order_money = $level_infos['order_money'];
                                        if ($getAgentInfo['order_money'] >= $order_money) {
                                            $result[] = 6;//分销订单金额达
                                        }
                                        break;
                                    case 7:
                                        if ($goods_info) {
                                            $result[] = 7;//指定商品
                                        }
                                        break;
                                    case 8:
                                        $offline_number = $level_infos['number'];
                                        if ($getAgentInfo['agentcount1'] >= $offline_number) {
                                            $result[] = 8;//客户人数
                                        }
                                        break;
                                    case 9:
                                        $level_number = $level_infos['level_number'];
                                        if ($low_number>= $level_number) {
                                            $result[] = 9;//指定等级人数
                                        }
                                        break;
                                    case 11:
                                        $up_order_money = $level_infos['up_team_money'];
                                        if ($getAgentInfo['up_team_money']>= $up_order_money) {
                                            $result[] = 11;//指定等级人数
                                        }
                                        break;
                                }
                            }
                            if ($level_infos['upgrade_condition'] == 1) {//升级条件类型（满足所有勾选条件）
                                if (count($result) == count($conditions)) {
                                    runhook("Notify", "sendCustomMessage", ['messageType'=>'team_upgrade_notice','uid' => $uid,'present_grade'=>$level_infos['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'upgrade_time' => time()]);//升级
                                    $member = new VslMemberModel();
                                    $member->save(['team_agent_level_id' => $level_infos['id'], 'up_team_level_time' => time(), 'down_up_team_level_time' => ''], ['uid' => $uid]);
                                    break;
                                }
                            }
                            if ($level_infos['upgrade_condition'] == 2) {//升级条件类型（满足勾选条件任意一个即可）
                                if (count($result) >= 1) {
                                    runhook("Notify", "sendCustomMessage", ['messageType'=>'team_upgrade_notice','uid' => $uid,'present_grade'=>$level_infos['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'upgrade_time' => time()]);//升级
                                    $member = new VslMemberModel();
                                    $member->save(['team_agent_level_id' => $level_infos['id'], 'up_team_level_time' => time(), 'down_up_team_level_time' => ''], ['uid' => $uid]);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    /*
     * 获取团队分红订单
     */
    public function getTeamOrderList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $uid = $condition['buyer_id'];
        unset($condition['buyer_id']);
        $wapStore = false;
        if($condition['wapstore']){
            $wapStore = true;
            unset($condition['wapstore']);
        }
        $order_model = new VslOrderModel();
        //如果有订单表以外的字段，则先按条件查询其他表的orderid，并取出数据的交集，组装到原有查询条件里
        $query_order_ids = 'uncheck';
        $un_query_order_ids = array();
        $checkOthers = false;
        $isGroup = false;
        if ($condition['express_no']) {
            $checkOthers = true;
            $expressNo = $condition['express_no'];
            $orderGoodsExpressModel = new VslOrderGoodsExpressModel();
            $orderGoodsExpressList = $orderGoodsExpressModel->pageQuery($page_index, $page_size, ['express_no' => $expressNo, 'website_id' => $condition['website_id']], '', 'order_id');
            unset($condition['express_no']);
            $express_order_ids = array();
            if ($orderGoodsExpressList['data']) {
                foreach ($orderGoodsExpressList['data'] as $keyEx => $valEx) {
                    $express_order_ids[] = $valEx['order_id'];
                }
                unset($valEx);
            }
            $query_order_ids = $express_order_ids;
        }

        // 接口用
        if ($condition['or'] && $condition['goods_name'] && $condition['shop_name']) {
            $checkOthers = true;
            $orderGoodsModel = new VslOrderGoodsModel();
            $order_goods_condition = ['website_id' => $this->website_id];
            $order_goods_condition['goods_name'] = $condition['goods_name'];

            $orderGoodsList = $orderGoodsModel->pageQuery(1, 0, $order_goods_condition, '', 'order_id');
            $goods_order_ids = array();
            if ($orderGoodsList['data']) {
                foreach ($orderGoodsList['data'] as $keyG => $valG) {
                    $goods_order_ids[] = $valG['order_id'];
                }
            }

            $order_condition['website_id'] = $condition['website_id'];
            $order_condition['shop_name'] = $condition['shop_name'];
            $order_list = $order_model->pageQuery(1, 0, $order_condition, '', 'order_id');
            if ($order_list['data']) {
                foreach ($order_list['data'] as $valG) {
                    $goods_order_ids[] = $valG['order_id'];
                }
            }
            if ($query_order_ids != 'uncheck') {
                $query_order_ids = array_intersect($query_order_ids, $goods_order_ids);
            } else {
                $query_order_ids = $goods_order_ids;
            }
            unset($condition['or'], $condition['goods_name'], $condition['shop_name'], $order_condition, $order_list);
        }
        if ($condition['goods_name'] || $condition['refund_status']) {
            $checkOthers = true;
            $orderGoodsModel = new VslOrderGoodsModel();
            $order_goods_condition = ['website_id' => $this->website_id];
            if ($condition['goods_name']) {
                $order_goods_condition['goods_name'] = $condition['goods_name'];
            }
            if ($condition['refund_status']) {
                if ($condition['refund_status'] == 'backList') {
                    $order_goods_condition['refund_status'] = ['neq', 0];
                } else {
                    $order_goods_condition['refund_status'] = ['IN', $condition['refund_status']];
                }
            }
            if ($condition['buyer_id']) {
                $order_goods_condition['buyer_id'] = $condition['buyer_id'];
            }
            $orderGoodsList = $orderGoodsModel->pageQuery(1, 0, $order_goods_condition, '', 'order_id');
            unset($condition['goods_name'], $condition['refund_status']);
            $goods_order_ids = array();
            if ($orderGoodsList['data']) {
                foreach ($orderGoodsList['data'] as $keyG => $valG) {
                    $goods_order_ids[] = $valG['order_id'];
                }
                unset($valG);
            }
            if ($query_order_ids != 'uncheck') {
                $query_order_ids = array_intersect($query_order_ids, $goods_order_ids);
            } else {
                $query_order_ids = $goods_order_ids;
            }
        }
        if ($condition['vgsr_status']) {
            $isGroup = true;
            $checkOthers = true;
            $vgsr_status = $condition['vgsr_status'];
            $group_server = new GroupShopping();
            $unGroupOrderIds = $group_server->getPayedUnGroupOrder($this->instance_id,$this->website_id);

            if ($vgsr_status == 1) {
                if ($query_order_ids != 'uncheck') {
                    $query_order_ids = array_intersect($query_order_ids, $unGroupOrderIds);
                } else {
                    $query_order_ids = $unGroupOrderIds;
                }
            }
            if ($vgsr_status == 2 && $unGroupOrderIds) {
                $un_query_order_ids = $unGroupOrderIds;
            }
            unset($condition['vgsr_status']);
        }
        if ($checkOthers) {
            if ($query_order_ids != 'uncheck') {
                $condition['order_id'] = ['in', implode(',', $query_order_ids)];
            } elseif ($un_query_order_ids) {
                $condition['order_id'] = ['not in', implode(',', $un_query_order_ids)];
            }
        }

        if ($condition['order_memo']){
            $order_memo = true;
            unset($condition['order_memo']);
        }
        if ($condition['order_amount']){
            unset($condition['order_amount']);
            $order_amount = $order_model->getSum($condition,'order_money');
        }
        // 查询主表
        $order_Bonus = new VslOrderBonusModel();
        $condition['order_id'] = ['in',implode(',',array_unique($order_Bonus->Query(['website_id'=>$condition['website_id'],'uid'=>$uid,'from_type'=>3],'order_id')))];//分红订单id
        if($condition['order_id']){
            $order_list = $order_model->pageQuery($page_index, $page_size, $condition, $order, '*');
            $order_bonus =  new VslOrderBonusModel();
            $group_server = $this->groupshopping ? new GroupShopping() : '';
            $order_memo_model = new VslOrderMemoModel();
            $user = new UserModel();
//        p($order_list['data']);exit;
            if (!empty($order_list['data'])) {
                foreach ($order_list['data'] as $k => $v) {
                    $order_list['data'][$k]['order_point'] = $v['point'];
                    //查询订单是否满足满减送的条件
                    $order_list['data'][$k]['promotion_status'] = ($order_list['data'][$k]['promotion_money'] + $order_list['data'][$k]['coupon_money'] > 0) ? 1 : 0;
                    //预售的应该是定金加上尾款
                    $order_list['data'][$k]['first_money'] = $v['order_money'];
                    if($v['presell_id'] && $v['money_type'] == 2){
                        $order_list['data'][$k]['order_money'] = $v['order_money'] + $v['final_money'];
                    }
                    if($this->groupshopping){
                        $isGroupSuccess = $group_server->groupRecordDetail($v['group_record_id'])['status'];
                    }
                    //查询订单分红
                    $orders = $order_bonus->Query(['order_id' => $v['order_id'],'uid'=>$uid, 'from_type' => 3], 'bonus');
                    $order_list['data'][$k]['bonus'] = array_sum($orders);
                    //查询订单佣金和积分
                    $order_list['data'][$k]['commission'] = '';
                    $order_list['data'][$k]['commissionA'] = '';
                    $order_list['data'][$k]['commissionB'] = '';
                    $order_list['data'][$k]['commissionC'] = '';
                    $order_list['data'][$k]['point'] = '';
                    $order_list['data'][$k]['pointA'] = '';
                    $order_list['data'][$k]['pointB'] = '';
                    $order_list['data'][$k]['pointC'] = '';
                    $order_list['data'][$k]['profit'] = '';
                    $order_list['data'][$k]['profitA'] = '';
                    $order_list['data'][$k]['profitB'] = '';
                    $order_list['data'][$k]['profitC'] = '';
                    if (getAddons('distribution', $this->website_id)){
                        $order_commission = new VslOrderDistributorCommissionModel();
                        $orders = $order_commission->Query(['order_id' => $v['order_id']], '*');
                        foreach ($orders as $key1 => $value) {
                            if ($value['commissionA_id'] == $v['buyer_id']) {
                                $order_list['data'][$k]['commission'] += $value['commissionA'];
                                $order_list['data'][$k]['point'] += $value['pointA'];
                            }
                            if ($value['commissionB_id'] == $v['buyer_id']) {
                                $order_list['data'][$k]['commission'] += $value['commissionB'];
                                $order_list['data'][$k]['point'] += $value['pointB'];
                            }
                            if ($value['commissionC_id'] == $v['buyer_id']) {
                                $order_list['data'][$k]['commission'] += $value['commissionC'];
                                $order_list['data'][$k]['point'] += $value['pointC'];
                            }
                            $user = new UserModel();
                            if($value['commissionA_id']){
                                $order_list['data'][$k]['commissionA_id'] = $value['commissionA_id'];
                                $member_A = $user->getInfo(['uid' => $value['commissionA_id']], 'user_name,nick_name');
                                if($member_A['user_name']){
                                    $order_list['data'][$k]['commissionA_name'] = $member_A['user_name'];
                                }else{
                                    $order_list['data'][$k]['commissionA_name'] =  $member_A['nick_name'];
                                }
                                $order_list['data'][$k]['commissionA'] += $value['commissionA'];
                                $order_list['data'][$k]['pointA'] += $value['pointA'];
                            }
                            if($value['commissionB_id']){
                                $order_list['data'][$k]['commissionB_id'] = $value['commissionB_id'];
                                $member_B = $user->getInfo(['uid' => $value['commissionB_id']], 'user_name,nick_name');
                                if($member_B['user_name']){
                                    $order_list['data'][$k]['commissionB_name'] = $member_B['user_name'];
                                }else{
                                    $order_list['data'][$k]['commissionB_name'] =  $member_B['nick_name'];
                                }
                                $order_list['data'][$k]['commissionB'] += $value['commissionB'];
                                $order_list['data'][$k]['pointB'] += $value['pointB'];
                            }
                            if($value['commissionC_id']){
                                $order_list['data'][$k]['commissionC_id'] = $value['commissionC_id'];
                                $member_C = $user->getInfo(['uid' => $value['commissionC_id']], 'user_name,nick_name');
                                if($member_C['user_name']){
                                    $order_list['data'][$k]['commissionC_name'] = $member_C['user_name'];
                                }else{
                                    $order_list['data'][$k]['commissionC_name'] =  $member_C['nick_name'];
                                }
                                $order_list['data'][$k]['commissionC'] += $value['commissionC'];
                                $order_list['data'][$k]['pointC'] += $value['pointC'];
                            }
                            $order_list['data'][$k]['commission'] = $order_list['data'][$k]['commissionA'] + $order_list['data'][$k]['commissionB'] + $order_list['data'][$k]['commissionC'];
                        }
                    }
                    if (getAddons('microshop', $this->website_id, $this->instance_id)){
                        $order_profit = new VslOrderMicroShopProfitModel();
                        $orders = $order_profit->Query(['order_id' => $v['order_id']], '*');
                        foreach ($orders as $key1 => $value) {
                            if ($value['profitA_id'] == $v['buyer_id']) {
                                $order_list['data'][$k]['profit'] += $value['profitA'];
                            }
                            if ($value['profitB_id'] == $v['buyer_id']) {
                                $order_list['data'][$k]['profit'] += $value['profitB'];
                            }
                            if ($value['profitC_id'] == $v['buyer_id']) {
                                $order_list['data'][$k]['profit'] += $value['profitC'];
                            }
                            $member = new UserModel();
                            $order_list['data'][$k]['profitA_id'] = $value['profitA_id'];
                            $order_list['data'][$k]['profitA_name'] = $member->getInfo(['uid' => $value['profitA_id']], 'user_tel')['user_tel'];
                            $order_list['data'][$k]['profitA'] += $value['profitA'];
                            $order_list['data'][$k]['profitB_id'] = $value['profitB_id'];
                            $order_list['data'][$k]['profitB_name'] = $member->getInfo(['uid' => $value['profitB_id']], 'user_tel')['user_tel'];
                            $order_list['data'][$k]['profitB'] += $value['profitB'];
                            $order_list['data'][$k]['profitC_id'] = $value['profitC_id'];
                            $order_list['data'][$k]['profitC_name'] = $member->getInfo(['uid' => $value['profitC_id']], 'user_tel')['user_tel'];
                            $order_list['data'][$k]['profitC'] += $value['profitC'];
                            $order_list['data'][$k]['profit'] = $order_list['data'][$k]['profitA'] + $order_list['data'][$k]['profitB'] + $order_list['data'][$k]['profitC'];
                        }
                    }
                    // 查询订单项表
                    $order_item = new VslOrderGoodsModel();
                    $order_item_list = $order_item->where([
                        'order_id' => $v['order_id']
                    ])->select();

                    // 查询最新的卖家备注
                    if (isset($order_memo) && $order_memo){
                        $order_list['data'][$k]['order_memo'] = $order_memo_model->where(['order_id' => $v['order_id']])->order('order_memo_id DESC')->limit(1)->find()['memo'];
                    }

                    $province_name = "";
                    $city_name = "";
                    $district_name = "";

                    $province = new ProvinceModel();
                    $province_info = $province->getInfo(array(
                        "province_id" => $v["receiver_province"]
                    ), "*");
                    if (count($province_info) > 0) {
                        $province_name = $province_info["province_name"];
                    }
                    $order_list['data'][$k]['receiver_province_name'] = $province_name;
                    $city = new CityModel();
                    $city_info = $city->getInfo(array(
                        "city_id" => $v["receiver_city"]
                    ), "*");
                    if (count($city_info) > 0) {
                        $city_name = $city_info["city_name"];
                    }
                    $order_list['data'][$k]['receiver_city_name'] = $city_name;
                    $district = new DistrictModel();
                    $district_info = $district->getInfo(array(
                        "district_id" => $v["receiver_district"]
                    ), "*");
                    if (count($district_info) > 0) {
                        $district_name = $district_info["district_name"];
                    }

                    $order_list['data'][$k]['operation'] = '';
                    // 订单来源名称
                    $order_from = OrderStatus::getOrderFrom($v['order_from']);
                    $order_list['data'][$k]['order_type_name'] = OrderStatus::getOrderType($v['order_type']);
                    $order_list['data'][$k]['order_type_color'] = OrderStatus::getOrderTypeColor($v['order_type']);
                    $order_list['data'][$k]['order_from_name'] = $order_from['type_name'];
                    $order_list['data'][$k]['order_from_tag'] = $order_from['tag'];
                    $order_list['data'][$k]['pay_type_name'] = OrderStatus::getPayType($v['payment_type']);
                    $order_list['data'][$k]['unrefund'] = 0;
                    $order_list['data'][$k]['unrefund_reason'] = '';
                    if($this->groupshopping && $v['group_record_id']){
                        $groupServer = new GroupShopping();
                        $record = $groupServer->groupRecordDetail($v['group_record_id']);
                        if($record['status'] == 0){
                            $order_list['data'][$k]['unrefund'] = 1;//待成团订单不能退款
                            $order_list['data'][$k]['unrefund_reason'] = '拼团订单暂时无法退款，若在'.time_diff(time(), $record['finish_time']).'未成团，将自动退款！';
                        }
                    }
                    if(getAddons('microshop', $this->website_id, $this->instance_id)){
                        if($v['order_type']==2 || $v['order_type']==3 || $v['order_type']==4 ){
                            $order_list['data'][$k]['unrefund'] = 1;//微店店主续费升级成为店主订单不能退款
                            $order_list['data'][$k]['unrefund_reason'] = '微店店主续费升级和成为店主是无法退款的订单！';
                        }
                    }
                    if(getAddons('shop', $this->website_id) && $order_list['data'][$k]['shop_id']){
                        $shop_model = new VslShopModel();
                        if ($order_list['data'][$k]['shop_id']) {
                            $shop_info = $shop_model->getInfo(['shop_id' => $order_list['data'][$k]['shop_id']], 'shop_name');
                            $order_list['data'][$k]['shop_name'] = $shop_info['shop_name'];
                        }
                    }else{
                        $order_list['data'][$k]['shop_name'] = '自营店';
                    }
                    if ($order_list['data'][$k]['shipping_type'] == 1) {
                        $order_list['data'][$k]['shipping_type_name'] = '商家配送';
                    } elseif ($order_list['data'][$k]['shipping_type'] == 2) {
                        $order_list['data'][$k]['shipping_type_name'] = '门店自提';
                    } else {
                        $order_list['data'][$k]['shipping_type_name'] = '';
                    }

                    $order_list['data'][$k]['user_tel'] = $user->getInfo(['uid' => $v['buyer_id']],'user_tel')['user_tel'];
                    // 根据订单类型判断订单相关操作
                    if($wapStore){
                        $order_status = OrderStatus::getSinceOrderStatusForStore($order_list['data'][$k]['order_type'], $isGroupSuccess);
                    }else{
                        if ($order_list['data'][$k]['payment_type'] == 6 || $order_list['data'][$k]['shipping_type'] == 2) {
                            $order_status = OrderStatus::getSinceOrderStatus($order_list['data'][$k]['order_type'],$isGroupSuccess);
                        } else {
                            $order_status = OrderStatus::getOrderCommonStatus($order_list['data'][$k]['order_type'],$isGroupSuccess,$order_list['data'][$k]['card_store_id'],$order_item_list ? $order_item_list[0]['goods_type'] : 0);
                        }
                    }

                    $order_list['data'][$k]['excel_order_money'] = $v['goods_money'] + $v['shipping_money'] - $v['promotion_free_shipping'];

                    $refund_member_operation = [];
                    // 查询订单操作
                    foreach ($order_status as $k_status => $v_status) {
                        if ($v_status['status_id'] == $v['order_status']) {
                            //代付定金
                            if($v['presell_id']!=0 && $v['pay_status']==0 && $v['money_type']==0 && $v['order_status'] != 5){
                                $v_status['status_name'] = "待付定金";
                                unset($v_status['operation'][1]);//调整价格 去掉
                            }
                            //待付尾款
                            if($v['presell_id']!=0 && $v['pay_status']==0 && $v['money_type']==1 && $v['order_status'] != 5){
                                $v_status['status_name'] = "待付尾款";
                                unset($v_status['operation'][1]);//调整价格 去掉
                            }

                            //已付定金，去掉定金退款按钮
                            if($v['presell_id']!=0 && $v['pay_status']==0 && $v['money_type']==1 ){
                                $v_status['refund_member_operation'] = '';
                            }
                            //积分订单没有支付、退款
                            if($v['order_type'] == 10){
                                $v_status['refund_member_operation'] = '';
                            }

                            $order_list['data'][$k]['operation'] = $v_status['operation'];
                            $order_list['data'][$k]['member_operation'] = $v_status['member_operation'];
                            $order_list['data'][$k]['status_name'] = $v_status['status_name'];

                            $order_list['data'][$k]['is_refund'] =  $v_status['is_refund'];
                            $refund_member_operation = $v_status['refund_member_operation'];

                        }
                    }
                    $order_list['data'][$k]['receiver_district_name'] = $district_name;
                    $temp_refund_operation = [];// 将需要整单进行售后的操作保存在operation（卖家操作）里面

                    //查询物流
                    $goods_express_model = new VslOrderGoodsExpressModel();
                    $order_express_info = $goods_express_model::all(['order_id' => $v['order_id']]);
                    //获取发货数目和总数目判断是否部分发货
                    $express_num = 0;
                    foreach ($order_item_list as $key_item => $v_item) {
                        if ($order_express_info) {
                            foreach ($order_express_info as $express_info) {
                                $express_order_goods_id_array = explode(',', $express_info['order_goods_id_array']);
                                if (in_array($v_item['order_goods_id'], $express_order_goods_id_array)) {
                                    $order_item_list[$key_item]['express_no'] = $express_info['express_no'];
                                    $order_item_list[$key_item]['express_name'] = $express_info['express_name'];
                                    $express_num++;
                                }
                            }
                        } else {
                            $order_item_list[$key_item]['express_no'] = '';
                            $order_item_list[$key_item]['express_name'] = '';
                        }
                        // 查询商品sku表开始
                        $goods_sku = new VslGoodsSkuModel();
                        $goods_sku_info = $goods_sku->getInfo([
                            'sku_id' => $v_item['sku_id']
                        ], 'code');
                        $order_item_list[$key_item]['code'] = $goods_sku_info['code'];
                        $goods_model = new VslGoodsModel();
                        $goods_info = $goods_model->getInfo([
                            'goods_id' => $v_item['goods_id']
                        ], 'cost_price,code,item_no');
                        $order_item_list[$key_item]['cost_price'] = $goods_info['cost_price'];
                        $order_item_list[$key_item]['goods_code'] = $goods_info['code'];
                        $order_item_list[$key_item]['item_no'] = $goods_info['item_no'];
                        $order_item_list[$key_item]['spec'] = [];
                        if ($v_item['sku_attr']) {
                            $order_item_list[$key_item]['spec'] = json_decode(html_entity_decode($v_item['sku_attr']), true);
                        }
                        // 查询商品sku结束

                        $picture = new AlbumPictureModel();
                        // $order_item_list[$key_item]['picture'] = $picture->get($v_item['goods_picture']);
                        $goods_picture = $picture->getInfo(['pic_id' =>$v_item['goods_picture']],'pic_cover,pic_cover_mid,pic_cover_micro');
                        if (empty($goods_picture)) {
                            $goods_picture = array(
                                'pic_cover' => '',
                                'pic_cover_big' => '',
                                'pic_cover_mid' => '',
                                'pic_cover_small' => '',
                                'pic_cover_micro' => '',
                                'upload_type' => 1,
                                'domain' => ''
                            );
                        }
                        $order_item_list[$key_item]['picture'] = $goods_picture;

                        $order_item_list[$key_item]['refund_type'] = $v_item['refund_type'];
                        $order_item_list[$key_item]['refund_operation'] = [];
                        $order_item_list[$key_item]['new_refund_operation'] = [];
                        $order_item_list[$key_item]['member_operation'] = [];
                        $order_item_list[$key_item]['status_name'] = '';
                        $temp_member_refund_operation = [];
                        if (!in_array($v['order_type'], [2, 3, 4, 10])) {
                            // 2,3,4微店订单 不参与售后
                            if ($v_item['refund_status'] != 0) {
                                $order_refund_status = OrderStatus::getRefundStatus()[$v_item['refund_status']];
                                if ($v_item['refund_type'] == 1 && $order_refund_status['status_id'] == 1) {
                                    //去除处理退货申请
                                    unset($order_refund_status['new_refund_operation'][1]);
                                } elseif ($v_item['refund_type'] == 2 && $order_refund_status['status_id'] == 1) {
                                    //去除处理退款申请
                                    unset($order_refund_status['new_refund_operation'][0]);
                                }
                                $order_item_list[$key_item]['refund_operation'] = $order_refund_status['refund_operation'];
                                if ($order_list['data'][$k]['promotion_status'] == 1) {
                                    $order_item_list[$key_item]['member_operation'] = [];
                                    $temp_member_refund_operation = $order_refund_status['member_operation'];
                                } else {
                                    $order_item_list[$key_item]['member_operation'] = $order_refund_status['member_operation'];
                                }
                                $order_item_list[$key_item]['new_refund_operation'] = $temp_refund_operation = array_values($order_refund_status['new_refund_operation']);
                                $order_item_list[$key_item]['status_name'] = $order_refund_status['status_name'];
                            } elseif($order_list['data'][$k]['promotion_status'] != 1) {
                                $order_item_list[$key_item]['member_operation'] = $refund_member_operation;
                            }
                        }

                        //优惠
                        $ordergoods_promotion = new VslOrderGoodsPromotionDetailsModel();
                        $ordergoods_promotion_info = $ordergoods_promotion->where(['order_id' => $v['order_id'],'sku_id' => $v_item['sku_id']])->find();
                        $order_item_list[$key_item]['manjian_money'] = $order_item_list[$key_item]['coupon_money'] = '';
                        if($ordergoods_promotion_info['promotion_type']=='MANJIAN' && getAddons('fullcut', $this->website_id)){
                            $order_item_list[$key_item]['manjian_money'] = $ordergoods_promotion_info['discount_money'];
                        }
                        if($ordergoods_promotion_info['promotion_type']=='COUPON' && getAddons('coupontype', $this->website_id)){
                            $order_item_list[$key_item]['coupon_money'] = $ordergoods_promotion_info['discount_money'];
                        }

                        //分销信息
                        $order_item_list[$key_item]['commission'] = '';
                        $order_item_list[$key_item]['commissionA'] = '';
                        $order_item_list[$key_item]['commissionB'] = '';
                        $order_item_list[$key_item]['commissionC'] = '';
                        if (getAddons('distribution', $this->website_id)){
                            $order_commission = new VslOrderDistributorCommissionModel();
                            $order_commission_info = $order_commission->where(['order_id' => $v['order_id'],'order_goods_id' => $v_item['order_goods_id']])->find();
                            $order_item_list[$key_item]['commission'] = $order_commission_info['commission'];
                            $order_item_list[$key_item]['commissionA'] = $order_commission_info['commissionA'];
                            $order_item_list[$key_item]['commissionB'] = $order_commission_info['commissionB'];
                            $order_item_list[$key_item]['commissionC'] = $order_commission_info['commissionC'];
                        }

                        //分红信息
                        $order_item_list[$key_item]['bonus'] = '';
                        $order_bonus = new VslOrderBonusModel();
                        $order_bonus_info = $order_bonus->where(['order_id' => $v['order_id'],'uid'=>$uid,'order_goods_id' => $v_item['order_goods_id']])->find();
                        $order_item_list[$key_item]['bonus'] = $order_bonus_info['bonus'];
                        //收益信息
                        $order_item_list[$key_item]['profit'] = '';
                        $order_item_list[$key_item]['profitA'] = '';
                        $order_item_list[$key_item]['profitB'] = '';
                        $order_item_list[$key_item]['profitC'] = '';
                        if (getAddons('microshop', $this->website_id)){
                            $order_profit = new VslOrderMicroShopProfitModel();
                            $order_profit_info = $order_profit->where(['order_id' => $v['order_id'],'order_goods_id' => $v_item['order_goods_id']])->find();
                            $order_item_list[$key_item]['profit'] = $order_profit_info['profit'];
                            $order_item_list[$key_item]['profitA'] = $order_profit_info['profitA'];
                            $order_item_list[$key_item]['profitB'] = $order_profit_info['profitB'];
                            $order_item_list[$key_item]['profitC'] = $order_profit_info['profitC'];
                        }
                    }

                    $order_list['data'][$k]['all_express'] = ($express_num >= count($order_item_list)) ?: false;
                    $order_list['data'][$k]['order_item_list'] = $order_item_list;

                    //订单优惠
                    $order_list['data'][$k]['order_adjust_money'] = 0;// 订单金额调整
                    foreach ($order_item_list as $order_goods_obj) {
                        $order_list['data'][$k]['order_adjust_money'] += $order_goods_obj['adjust_money'] * $order_goods_obj['num'];
                    }
                    if(!$v['presell_id']){
                        $order_list['data'][$k]['order_promotion_money'] = $v['goods_money'] + $v['shipping_money'] - $v['promotion_free_shipping'] - $v['order_money'] + $order_list['data'][$k]['order_adjust_money'];
                        if($v['deduction_money']>0){
                            $order_list['data'][$k]['order_promotion_money'] = "{$order_list['data'][$k]['order_promotion_money']}" - "{$v['deduction_money']}";
                        }
                    }else{
                        $order_list['data'][$k]['order_promotion_money'] = 0;
                    }

                    //查询会员信息
                    $user_item = new UserModel();
                    $user_item_info = $user_item->where(['uid' => $v['buyer_id']])->find();
                    $order_list['data'][$k]['user_tel'] = $user_item_info['user_tel'];
                    $order_list['data'][$k]['buyer_name'] = ($user_item_info['nick_name'])?$user_item_info['nick_name']:($user_item_info['user_name']?$user_item_info['user_name']:($user_item_info['user_tel']?$user_item_info['user_tel']:$user_item_info['uid']));

                    //查询核销门店信息
                    $order_list['data'][$k]['store_name'] = '';
                    $order_list['data'][$k]['assistant_name'] = '';
                    if (getAddons('store', $this->website_id)){
                        $store = new VslStoreModel();
                        $store_assistant = new VslStoreAssistantModel();
                        $order_list['data'][$k]['store_name'] = $store->where(['store_id' => $v['store_id']])->value('store_name');
                        $order_list['data'][$k]['assistant_name'] = $store_assistant->where(['assistant_id' => $v['assistant_id']])->value('assistant_name');
                    }

                    //查询满额赠送
                    $order_list['data'][$k]['manjian_remark'] = '';
                    if (getAddons('fullcut', $this->website_id)){
                        $order_promotion = new VslOrderPromotionDetailsModel();
                        $manjian_remark = $order_promotion->where(['order_id' => $v['order_id'],'promotion_type'=>'MANJIAN'])->value('remark');
                        if(!empty($manjian_remark['coupon'])){
                            $order_list['data'][$k]['manjian_remark'] .= $manjian_remark['coupon']['coupon_name'];
                        }
                        if(!empty($manjian_remark['gift'])){
                            $order_list['data'][$k]['manjian_remark'] .= $manjian_remark['gift']['gift_name'];
                        }
                        if(!empty($manjian_remark['gift_voucher'])){
                            $order_list['data'][$k]['manjian_remark'] .= $manjian_remark['gift_voucher']['giftvoucher_name'];
                        }
                    }


                    // 将需要整单进行售后的 售后操作 放到 非售后操作数组内 因为后者的位置就是位于 th = 操作的 那一列
                    if ($temp_refund_operation && $order_list['data'][$k]['promotion_status'] == 1) {
                        $order_list['data'][$k]['operation'] = array_merge($order_list['data'][$k]['operation'], $temp_refund_operation);
                        //$order_list['data'][$k]['refund_operation_goods'] = array_column($order_list['data'][$k]['order_item_list'], 'order_goods_id');
                    }
                    //积分兑换订单是没有售后的
                    if (!in_array($v['order_status'], [4, 5]) && $v['order_type'] != 10) {
                        // 已完成，已关闭没有售后
                        if ($order_list['data'][$k]['promotion_status'] == 1) {
                            if (!empty($temp_member_refund_operation)) {
                                $order_list['data'][$k]['member_operation'] = array_merge($order_list['data'][$k]['member_operation'], $temp_member_refund_operation);
                            }
                            $order_list['data'][$k]['member_operation'] = array_merge($order_list['data'][$k]['member_operation'], $refund_member_operation);
                        } else {
                            // 将common里面的售后操作放到订单商品里面
                            foreach ($order_list['data'][$k]['order_item_list'] as &$v_item) {
                                if ($v_item['refund_status'] == 0) {
                                    $v_item['member_operation'] = $refund_member_operation;
                                }
                            }
                            unset($v_item);
                        }
                    }

                }
            }
            if (isset($order_amount)){
                $order_list['order_amount'] = $order_amount;
            }
        }else{
            $order_list['data'] = [];
        }

        return $order_list;


//        $condition['order_status'] = [['>',0],['<',5]];
//        $order_model = new VslOrderModel();
//        //如果有订单表以外的字段，则先按条件查询其他表的orderid，并取出数据的交集，组装到原有查询条件里
//        $query_order_ids='uncheck';
//        $checkOthers = false;
//        if($condition['express_no']){
//            $checkOthers = true;
//            $expressNo = $condition['express_no'];
//            $orderGoodsExpressModel = new VslOrderGoodsExpressModel();
//            $orderGoodsExpressList = $orderGoodsExpressModel->pageQuery($page_index, $page_size, ['express_no'=>$expressNo,'website_id'=>$condition['website_id']], '', 'order_id');
//            unset($condition['express_no']);
//            $express_order_ids=array();
//            if($orderGoodsExpressList['data']){
//                foreach($orderGoodsExpressList['data'] as $keyEx => $valEx){
//                    $express_order_ids[] = $valEx['order_id'];
//                }
//                unset($valEx);
//            }
//            $query_order_ids = $express_order_ids;
//        }
//        if($condition['goods_name']){
//            $checkOthers =true;
//            $goodsName = $condition['goods_name'];
//            $orderGoodsModel = new VslOrderGoodsModel();
//            $orderGoodsList = $orderGoodsModel->pageQuery($page_index, $page_size, ['goods_name'=>['like', "%" . $goodsName . "%"],'website_id'=>$condition['website_id']], '', 'order_id');
//            unset($condition['goods_name']);
//            $goods_order_ids=array();
//            if($orderGoodsList['data']){
//                foreach($orderGoodsList['data'] as $keyG => $valG){
//                    $goods_order_ids[] = $valG['order_id'];
//                }
//                unset($valG);
//            }
//            if($query_order_ids!='uncheck'){
//                $query_order_ids = array_intersect($query_order_ids, $goods_order_ids);
//            }else{
//                $query_order_ids = $goods_order_ids;
//            }
//        }
//        if($checkOthers){
//            if($query_order_ids!='uncheck'){
//                $condition['order_id'] = ['in', implode(',', $query_order_ids)];
//            }else{
//                $condition['order_id'] = '';
//            }
//        }
//        $order_Bonus = new VslOrderBonusModel();
//        $condition['order_id'] = ['in',implode(',',array_unique($order_Bonus->Query(['website_id'=>$condition['website_id'],'uid'=>$uid,'from_type'=>3],'order_id')))];//分红订单id
//        if($condition['order_id']){
//            // 查询主表
//            $order_list = $order_model->pageQuery($page_index, $page_size, $condition, $order, '*');
//            if (! empty($order_list['data'])) {
//                $user = new UserModel();
//                foreach ($order_list['data'] as $k => $v) {
//                    $user_info = $user->getInfo(['uid'=>$order_list['data'][$k]['buyer_id']],'nick_name,user_name,user_tel,user_headimg');
//                    if($user_info['user_name']){
//                        $order_list['data'][$k]['buyer_name'] = $user_info['user_name'];
//                    }elseif ($user_info['nick_name']){
//                        $order_list['data'][$k]['buyer_name'] = $user_info['nick_name'];
//                    }elseif ($user_info['user_tel']){
//                        $order_list['data'][$k]['buyer_name'] = $user_info['user_tel'];
//                    }
//                    $order_list['data'][$k]['bonus'] =0;
//                    //查询订单分红
//                    $orders = $order_Bonus->Query(['order_id'=>$v['order_id'],'uid'=>$uid,'from_type'=>3],'bonus');
//                    $order_list['data'][$k]['bonus'] = array_sum($orders) ;
//                    // 查询订单项表
//                    $order_item = new VslOrderGoodsModel();
//                    $order_item_list = $order_item->where([
//                        'order_id' => $v['order_id']
//                    ])->select();
//
//                    $province_name = "";
//                    $city_name = "";
//                    $district_name = "";
//
//                    $province = new ProvinceModel();
//                    $province_info = $province->getInfo(array(
//                        "province_id" => $v["receiver_province"]
//                    ), "*");
//                    if (count($province_info) > 0) {
//                        $province_name = $province_info["province_name"];
//                    }
//                    $order_list['data'][$k]['receiver_province_name'] = $province_name;
//                    $city = new CityModel();
//                    $city_info = $city->getInfo(array(
//                        "city_id" => $v["receiver_city"]
//                    ), "*");
//                    if (count($city_info) > 0) {
//                        $city_name = $city_info["city_name"];
//                    }
//                    $order_list['data'][$k]['receiver_city_name'] = $city_name;
//                    $district = new DistrictModel();
//                    $district_info = $district->getInfo(array(
//                        "district_id" => $v["receiver_district"]
//                    ), "*");
//                    if (count($district_info) > 0) {
//                        $district_name = $district_info["district_name"];
//                    }
//                    $order_list['data'][$k]['receiver_district_name'] = $district_name;
//                    foreach ($order_item_list as $key_item => $v_item) {
//                        // 查询商品sku表开始
//                        $goods_sku = new VslGoodsSkuModel();
//                        $goods_sku_info = $goods_sku->getInfo([
//                            'sku_id' => $v_item['sku_id']
//                        ], 'code,attr_value_items');
//                        $order_item_list[$key_item]['code'] = $goods_sku_info['code'];
//                        $order_item_list[$key_item]['spec'] = [];
//                        if ($v_item['sku_attr']) {
//                            $order_item_list[$key_item]['spec'] = json_decode(html_entity_decode($v_item['sku_attr']), true);
//                        }
//                        // 查询商品sku结束
//
//                        $picture = new AlbumPictureModel();
//                        // $order_item_list[$key_item]['picture'] = $picture->get($v_item['goods_picture']);
//                        $goods_picture = $picture->get($v_item['goods_picture']);
//                        if (empty($goods_picture)) {
//                            $goods_picture = array(
//                                'pic_cover' => '',
//                                'pic_cover_big' => '',
//                                'pic_cover_mid' => '',
//                                'pic_cover_small' => '',
//                                'pic_cover_micro' => '',
//                                'upload_type' => 1,
//                                'domain' => ''
//                            );
//                        }
//                        $order_item_list[$key_item]['picture'] = $goods_picture;
//                    }
//                    $order_list['data'][$k]['order_item_list'] = $order_item_list;
//                    // 订单来源名称
//                    $order_from = OrderStatus::getOrderFrom($v['order_from']);
//                    $order_list['data'][$k]['order_from_name'] = $order_from['type_name'];
//                    $order_list['data'][$k]['order_from_tag'] = $order_from['tag'];
//                    $order_list['data'][$k]['pay_type_name'] = OrderStatus::getPayType($v['payment_type']);
//                    if(getAddons('shop', $this->website_id) && $order_list['data'][$k]['shop_id']){
//                        $shop_model = new VslShopModel();
//                        $shop_info = $shop_model->getInfo(['shop_id' => $order_list['data'][$k]['shop_id']], 'shop_name');
//                        $order_list['data'][$k]['shop_name'] = $shop_info['shop_name'];
//                    }else{
//                        $order_list['data'][$k]['shop_name'] = '自营店';
//                    }
//                    if ($order_list['data'][$k]['shipping_type'] == 1) {
//                        $order_list['data'][$k]['shipping_type_name'] = '商家配送';
//                    } elseif ($order_list['data'][$k]['shipping_type'] == 2) {
//                        $order_list['data'][$k]['shipping_type_name'] = '门店自提';
//                    } else {
//                        $order_list['data'][$k]['shipping_type_name'] = '';
//                    }
//                    // 根据订单类型判断订单相关操作
//                    if ($order_list['data'][$k]['payment_type'] == 6 || $order_list['data'][$k]['shipping_type'] == 2) {
//                        $order_status = OrderStatus::getSinceOrderStatus();
//                    } else {
//                        $order_status = OrderStatus::getOrderCommonStatus();
//                    }
//
//                    // 查询订单操作
//                    foreach ($order_status as $k_status => $v_status) {
//                        if ($v_status['status_id'] == $v['order_status']) {
//                            //代付定金
//                            if($v['presell_id']!=0 && $v['pay_status']==0 && $v['money_type']==0){
//                                $v_status['status_name'] = "待付定金";
//                                unset($v_status['operation'][1]);
//                            }
//                            //待付尾款
//                            if($v['presell_id']!=0 && $v['pay_status']==2 && $v['money_type']==1){
//                                $v_status['status_name'] = "待付尾款";
//                            }
//
//                            //已付定金，去掉定金退款按钮
//                            if($v['presell_id']!=0 && $v['pay_status']==2 ){
//                                $v_status['refund_member_operation'] = '';
//                            }
//                            $order_list['data'][$k]['status_name'] = $v_status['status_name'];
//                        }
//                    }
//                }
//            }
//            return $order_list;
//        }
    }
    /*
    * 获取分红订单详情
    */
    public function getOrderDetail($order_id,$uid)
    {
        // 查询主表信息
        $OrderService = new OrderService();
        $detail = $OrderService->getOrderDetail($order_id);
        if($detail){
            //查询订单分红
            $order_commission = new VslOrderBonusModel();
            $orders = $order_commission->Query(['order_id'=>$order_id,'uid'=>$uid,'from_type'=>3],'bonus');
            $user = new UserModel();
            $userinfo = $user->getInfo(['uid'=>$uid],'user_headimg,user_name');
            $detail['user_headimg'] =  $userinfo['user_headimg'];
            if(empty($userinfo['user_name'])){
                $detail['user_name'] = $userinfo['nick_name'];
            }
            $detail['bonus'] = array_sum($orders);
        }
        if (!empty($detail['operation'])) {
            $operation_array = $detail['operation'];
            foreach ($operation_array as $k => $v) {
                if ($v['no'] == 'logistics' || $v['no'] == 'order_close' || $v['no'] == 'adjust_price' || $v['no'] == 'delete_order') {
                    unset($operation_array[$k]);
                }
            }
            $detail['operation'] = $operation_array;
        }
        if (empty($detail)) {
            return array();
        }
        return $detail;
    }
    /**
     * 队长详情(降级条件)
     */
    public function getAgentInfos($uid,$time)
    {
        $distributor = new VslMemberModel();
        if($this->website_id){
            $website_id = $this->website_id;
        }else{
            $website_id =  $distributor->getInfo(['uid'=>$uid],'website_id')['website_id'];
        }
        $order_model = new VslOrderModel();
        $config = new AddonsConfigService();
        $info1 = $config->getAddonsConfig('distribution',$website_id);
        $list = json_decode($info1['value'], true);
        $result = $distributor->getInfo(['uid' => $uid],"*");
        if($uid && $time){
            $order_commission = new VslOrderDistributorCommissionModel();
            $commission_order_id = implode(',',$order_commission->Query(['website_id'=>$result['website_id']],'order_id'));
            $result['agentordercount'] = 0;
            $result['order_money'] = 0;
            $result['selforder_money'] = 0;
            $result['selforder_number'] = 0;
            $up_time = $distributor->getInfo(['uid'=>$uid],'up_team_level_time')['up_team_level_time'];
            $limit_time = $up_time+$time*24*3600;
            $order_ids = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>$uid,'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_id');
            $order_pay_money = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>$uid,'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_money');
            $result['selforder_money'] = array_sum($order_pay_money);//自购订单金额
            $result['selforder_number'] = count($order_ids);//自购订单数
            if(1 <= $list['distribution_pattern']){
                $idslevel1 = $distributor->Query(['referee_id'=>$uid],'uid');
                if($idslevel1){
                    $order_ids1 = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>['in',implode(',',$idslevel1)],'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_id');
                    $order1_money1 = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>['in',implode(',',$idslevel1)],'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_money');
                    $result['order1'] = count($order_ids1);//一级分销商订单总数
                    $result['number1'] = count($idslevel1);//一级分销商总人数
                    $result['order1_money'] = array_sum($order1_money1);//一级分销商订单总金额
                    $result['agentcount'] += $result['number1'];
                    $result['agentordercount'] += $result['order1'];
                    $result['order_money'] += $result['order1_money'];
                }
            }
            if(2 <= $list['distribution_pattern']){
                if($result['number1']>0){
                    $idslevel2 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel1)]],'uid');
                    if($idslevel2){
                        $order_ids2 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevel2)],'order_status'=>[['>',0],['<',5]],'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_id');
                        $order2_money1 = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>['in',implode(',',$idslevel2)],'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_money');
                        $result['order2'] = count($order_ids2);//二级分销商订单总数
                        $result['number2'] = count($idslevel2);//二级分销商总人数
                        $result['order2_money'] = array_sum($order2_money1);//二级分销商订单总金额
                        $result['agentcount'] += $result['number2'];
                        $result['agentordercount'] += $result['order2'];
                        $result['order_money'] += $result['order2_money'];
                    }
                }
            }
            if(3 <= $list['distribution_pattern']){
                if($result['number2']>0){
                    $idslevel3 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel2)]],'uid');
                    if($idslevel3){
                        $order_ids3 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevel3)],'order_status'=>[['>',0],['<',5]],'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_id');
                        $order3_money1 = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>['in',implode(',',$idslevel3)],'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_money');
                        $result['order2'] = count($order_ids3);//三级分销商订单总数
                        $result['number3'] = count($idslevel3);//三级分销商总人数
                        $result['order3_money'] = array_sum($order3_money1);//三级分销商订单总金额
                        $result['agentcount'] += $result['number3'];
                        $result['agentordercount'] += $result['order3'];
                        $result['order_money'] += $result['order3_money'];
                    }
                }
            }
            if($list['purchase_type']==1){
                $result['agentordercount'] += count($order_ids);
                $result['order_money'] += array_sum($order_pay_money);
            }
        }
        return $result;
    }

    /*
     * 队长自动降级
     */
    public function autoDownAgentLevel($website_id){
        $level = new AgentLevelModel();
        $base_info = $this->getTeamBonusSite($website_id);
        $member = new VslMemberModel();
        $agents = $member->Query(['website_id'=>$website_id,'is_team_agent'=>2],'*');
        $default_weight = $level->getInfo(['website_id'=>$website_id,'is_default'=>1,'from_type'=>3],'weight')['weight'];//默认等级信息
        foreach ($agents as $k=>$v){
            $level_info_default = $level->getInfo(['id'=>$v['team_agent_level_id']],'*');
            $level_weight = $level_info_default ['weight'];//分红商的等级权重
            $level_name_default = $level_info_default['level_name'];
            if($level_weight>$default_weight){
                if($base_info['teamagent_grade']==1){//开启跳降级
                    $level_weights = $level->Query(['weight'=>['<=',$level_weight],'from_type'=>3,'website_id'=>$website_id],'weight');//分红商的等级权重的下级权重
                    rsort($level_weights);
                    foreach ($level_weights as $k1=>$v1){
                        if($v1!=$default_weight){
                        $level_info_desc = $level->getFirstData(['weight' => ['<', $v1], 'website_id' => $website_id, 'from_type' =>3], 'weight desc');//比当前等级的权重低的等级信息
                        $level_infos = $level->getInfo(['weight' => $v1, 'from_type' => 3, 'website_id' => $website_id], '*');
                        $ratio = $level_info_desc['ratio'].'%';
                        if($level_infos['downgradetype']==1 && $level_infos['downgradeconditions']){//是否开启自动降级并且有降级条件
                                $conditions = explode(',',$level_infos['downgradeconditions']);
                                $result = [];
                                $reason = '';
                                foreach ($conditions as $k2=>$v2){
                                    switch ($v2){
                                        case 1:
                                            $team_number_day = $level_infos['team_number_day'];
                                            $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_team_level_time')['up_team_level_time']+$team_number_day*24*3600;
                                            if($real_level_time<=time()){
                                                $getAgentInfo1 = $this->getAgentInfos($v['uid'],$team_number_day);
                                                $limit_number =  $getAgentInfo1['agentordercount'];//限制时间段内团队分红订单数
                                                if($limit_number <=$level_infos['team_number']){
                                                    $result[] = 1;
                                                    $reason .= '团队分红订单数小于'.$level_infos['team_number'];
                                                }
                                            }
                                            break;
                                        case 2:
                                            $team_money_day = $level_infos['team_money_day'];
                                            $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_team_level_time')['up_team_level_time']+$team_money_day*24*3600;
                                            if($real_level_time<=time()){
                                                $getAgentInfo2 = $this->getAgentInfos($v['uid'],$team_money_day);
                                                $limit_money1 =  $getAgentInfo2['order_money'];//限制时间段内团队分红订单金额
                                                if($limit_money1 <=$level_infos['team_money']){
                                                    $result[] = 2;
                                                    $reason .= '团队分红订单金额小于'.$level_infos['team_number'];
                                                }
                                            }
                                            break;
                                        case 3:
                                            $self_money_day = $level_infos['self_money_day'];
                                            $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_team_level_time')['up_team_level_time']+$self_money_day*24*3600;
                                            if($real_level_time<=time()){
                                                $getAgentInfo3 = $this->getAgentInfos($v['uid'],$self_money_day);
                                                $limit_money2 = $getAgentInfo3['selforder_money'];//限制时间段内自购分红订单金额
                                                if($limit_money2 <=$level_infos['self_money']){
                                                    $result[] = 3;
                                                    $reason .= '自购分红订单金额小于'.$level_infos['team_number'];
                                                }
                                            }
                                            break;
                                    }
                                }
                                if($level_infos['downgrade_condition']==1){//降级条件类型（满足所有勾选条件）
                                    if(count($result)==count($conditions)){
                                        runhook("Notify", "sendCustomMessage", ['messageType'=>'team_down_notice','uid' => $v['uid'],'present_grade'=>$level_info_desc['level_name'],'primary_grade'=>$level_name_default,'ratio'=>$ratio,'down_reason'=>$reason,'down_time' => time()]);//降级
                                        $member = new VslMemberModel();
                                        $member->save(['team_agent_level_id'=>$level_info_desc['id'],'down_team_level_time'=>time(), 'down_up_team_level_time' => time()],['uid'=>$v['uid']]);
                                    }
                                }
                                if($level_infos['downgrade_condition']==2){//降级条件类型（满足勾选条件任意一个即可）
                                    if(count($result)>=1){
                                        runhook("Notify", "sendCustomMessage", ['messageType'=>'team_down_notice','uid' => $v['uid'],'present_grade'=>$level_info_desc['level_name'],'primary_grade'=>$level_name_default,'ratio'=>$ratio,'down_reason'=>$reason,'down_time' => time()]);//降级
                                        $member = new VslMemberModel();
                                        $member->save(['team_agent_level_id'=>$level_info_desc['id'],'down_team_level_time'=>time(), 'down_up_team_level_time' => time()],['uid'=>$v['uid']]);
                                    }
                                }
                            }
                        }
                    }
                }
                if($base_info['teamagent_grade']==2){//未开启跳降级
                    $level_weights = $level->Query(['weight'=>['<=',$level_weight],'from_type'=>3,'website_id'=>$website_id],'weight');//分红商的等级权重的下级权重
                    rsort($level_weights);
                    foreach ($level_weights as $k1=>$v1){
                        if($k1 > 0){
                            break;
                        }
                        if($v1!=$default_weight){
                        $level_info_desc = $level->getFirstData(['weight' => ['<', $v1], 'website_id' => $website_id, 'from_type' =>3], 'weight desc');//比当前等级的权重低的等级信息
                        $level_infos = $level->getInfo(['weight' => $v1, 'from_type' => 3, 'website_id' => $website_id], '*');
                        $ratio = $level_info_desc['ratio'].'%';
                        if($level_infos['downgradetype']==1 && $level_infos['downgradeconditions']){//是否开启自动降级并且有降级条件
                                $conditions = explode(',',$level_infos['downgradeconditions']);
                                $result = [];
                                foreach ($conditions as $k2=>$v2){
                                    switch ($v2){
                                        case 1:
                                            $team_number_day = $level_infos['team_number_day'];
                                            $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_team_level_time')['up_team_level_time']+$team_number_day*24*3600;
                                            if($real_level_time<=time()){
                                                $getAgentInfo1 = $this->getAgentInfos($v['uid'],$team_number_day);
                                                $limit_number =  $getAgentInfo1['agentordercount'];//限制时间段内团队分红订单数
                                                if($limit_number <=$level_infos['team_number']){
                                                    $result[] = 1;
                                                    $reason .= '团队分红订单数小于'.$level_infos['team_number'];
                                                }
                                            }
                                            break;
                                        case 2:
                                            $team_money_day = $level_infos['team_money_day'];
                                            $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_team_level_time')['up_team_level_time']+$team_money_day*24*3600;
                                            if($real_level_time<=time()){
                                                $getAgentInfo2 = $this->getAgentInfos($v['uid'],$team_money_day);
                                                $limit_money1 =  $getAgentInfo2['order_money'];//限制时间段内团队分红订单金额
                                                if($limit_money1 <=$level_infos['team_money']){
                                                    $result[] = 2;
                                                    $reason .= '团队分红订单金额小于'.$level_infos['team_number'];
                                                }
                                            }
                                            break;
                                        case 3:
                                            $self_money_day = $level_infos['self_money_day'];
                                            $real_level_time = $member->getInfo(['uid'=>$v['uid']],'up_team_level_time')['up_team_level_time']+$self_money_day*24*3600;
                                            if($real_level_time<=time()){
                                                $getAgentInfo3 = $this->getAgentInfos($v['uid'],$self_money_day);
                                                $limit_money2 = $getAgentInfo3['selforder_money'];//限制时间段内自购分红订单金额
                                                if($limit_money2 <=$level_infos['self_money']){
                                                    $result[] = 3;
                                                    $reason .= '自购分红订单金额小于'.$level_infos['team_number'];
                                                }
                                            }
                                            break;
                                    }
                                }
                                if($level_infos['downgrade_condition']==1){//降级条件类型（满足所有勾选条件）
                                    if(count($result)==count($conditions)){
                                        runhook("Notify", "sendCustomMessage", ['messageType'=>'team_down_notice','uid' => $v['uid'],'present_grade'=>$level_info_desc['level_name'],'primary_grade'=>$level_name_default,'ratio'=>$ratio,'down_reason'=>$reason,'down_time' => time()]);//降级
                                        $member = new VslMemberModel();
                                        $member->save(['team_agent_level_id'=>$level_info_desc['id'],'down_team_level_time'=>time(), 'down_up_team_level_time' => time()],['uid'=>$v['uid']]);
                                        break;
                                    }
                                }
                                if($level_infos['downgrade_condition']==2){//降级条件类型（满足勾选条件任意一个即可）
                                    if(count($result)>=1){
                                        runhook("Notify", "sendCustomMessage", ['messageType'=>'team_down_notice','uid' => $v['uid'],'present_grade'=>$level_info_desc['level_name'],'primary_grade'=>$level_name_default,'ratio'=>$ratio,'down_reason'=>$reason,'down_time' => time()]);//降级
                                        $member = new VslMemberModel();
                                        $member->save(['team_agent_level_id'=>$level_info_desc['id'],'down_team_level_time'=>time(), 'down_up_team_level_time' => time()],['uid'=>$v['uid']]);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /*
     * 成为队长的条件
     */
    public function becomeAgent($uid){
        $member = new VslMemberModel();
        $agent = $member->getInfo(['uid'=>$uid],'*');
        $base_info = $this->getTeamBonusSite($agent['website_id']);
        $order = new VslOrderModel();
        //判断是否购买过指定商品
        $goods_info = [];
        if($base_info['goods_id']){
            $order_goods = new VslOrderGoodsModel();
            $goods_id = $order_goods->Query(['goods_id'=>$base_info['goods_id'],'buyer_id'=>$uid],'order_id');
            if($goods_id){
                $goods_info = $order->getInfo(['order_id'=>['IN',implode(',',$goods_id)],'order_status'=>4],'*');
            }
        }
        $agent_level = new AgentLevelModel();
        $level_info = $agent_level->getInfo(['website_id' => $agent['website_id'],'is_default'=>1,'from_type'=>3],'*');
        $level_id = $level_info['id'];
        $ratio = $level_info['ratio'].'%';
        $member_info = $this->getAgentLowerInfo($uid);
        if($agent['is_team_agent']!=2){//判断是否是队长
            if($base_info['is_use']==1){//判断是否开启团队分红
                if($base_info['teamagent_conditions']){//判断是否有成为队长的条件
                    $result = [];
                    $conditions = explode(',',$base_info['teamagent_conditions']);
                    foreach ($conditions as $k=>$v) {
                            switch ($v) {
                                case 1:
                                    $order_money = $member_info['selforder_money'];
                                    if ($order_money >= $base_info['pay_money']) {
                                        $result[] = 1;//满足自购订单金额
                                    }
                                    break;
                                case 2:
                                    $number = $member_info['agentcount'];
                                    if ($number >= $base_info['number']) {
                                        $result[] = 2;//下级分销商数
                                    }
                                    break;
                                case 3:
                                    $one_number = $member_info['one_number1'];
                                    if ($one_number >= $base_info['one_number']) {
                                        $result[] = 3;//一级分销商
                                    }
                                    break;
                                case 4:
                                    $two_number = $member_info['two_number1'];
                                    if ($two_number >= $base_info['two_number']) {
                                        $result[] = 4;//二级分销商
                                    }
                                    break;
                                case 5:
                                    $three_number = $member_info['three_number1'];
                                    if ($three_number >= $base_info['three_number']) {
                                        $result[] = 5;//三级分销商
                                    }
                                    break;
                                case 6:
                                    $one_number = $member_info['order_money'];
                                    if ($one_number >= $base_info['order_money']) {
                                        $result[] = 6;//下级订单总额
                                    }
                                    break;
                                case 7:
                                    if ($goods_info) {
                                        $result[] = 7;//满足购买指定商品
                                    }
                                    break;
                                case 11:
                                    $up_order_money = $member_info['up_team_money'];
                                    if ($up_order_money>= $base_info['up_team_money']) {
                                        $result[] = 11;//指定等级人数
                                    }
                                    break;
                            }
                        }
                        if($base_info['teamagent_condition']==1){//满足所有勾选条件
                            if(count($conditions)==count($result)) {
                                if ($base_info['teamagent_check'] == 1 && $base_info['teamagent_data'] == 2) {
                                    $data = array(
                                        "is_team_agent" => 2,
                                        "team_agent_level_id" => $level_id,
                                        "apply_team_agent_time" => time(),
                                        "become_team_agent_time" => time(),
                                    );
                                    $member->save($data, ['uid' => $uid]);
                                    $account = new VslBonusAccountModel();
                                    $account_info = $account->getInfo(['website_id' => $agent['website_id'], 'from_type' => 3, 'uid' => $uid]);
                                    if (empty($account_info)) {
                                        $account->save(['website_id' => $agent['website_id'], 'from_type' => 3, 'uid' => $uid]);
                                    }
                                    runhook("Notify", "sendCustomMessage", ["messageType"=>"become_team","uid" => $uid,"become_time" => time(),'ratio'=>$ratio,'level_name'=>$level_info['level_name']]);//用户成为全球股东提醒
                                } else if ($base_info['teamagent_check'] == 2 && $base_info['teamagent_data'] == 2) {
                                    $member->save(['is_team_agent' => 1], ['uid' => $uid]);
                                } else {
                                    $member->save(['is_team_agent' => 3], ['uid' => $uid]);
                                }
                            }
                        }
                        if($base_info['teamagent_condition']==2){//满足所有勾选条件之一
                            if(count($result)>=1){
                                if ($base_info['teamagent_check'] == 1 && $base_info['teamagent_data'] == 2) {
                                    $data = array(
                                        "is_team_agent" => 2,
                                        "team_agent_level_id" => $level_id,
                                        "apply_team_agent_time" => time(),
                                        "become_team_agent_time" => time(),
                                    );
                                    $member->save($data, ['uid' => $uid]);
                                    $account = new VslBonusAccountModel();
                                    $account_info = $account->getInfo(['website_id' => $agent['website_id'], 'from_type' => 3, 'uid' => $uid]);
                                    if (empty($account_info)) {
                                        $account->save(['website_id' => $agent['website_id'], 'from_type' => 3, 'uid' => $uid]);
                                    }
                                    runhook("Notify", "sendCustomMessage", ["messageType"=>"become_team","uid" => $uid,"become_time" => time(),'ratio'=>$ratio,'level_name'=>$level_info['level_name']]);//用户成为全球股东提醒
                                } else if ($base_info['teamagent_check'] == 2 && $base_info['teamagent_data'] == 2) {
                                    $member->save(['is_team_agent' => 1], ['uid' => $uid]);
                                } else {
                                    $member->save(['is_team_agent' => 3], ['uid' => $uid]);
                                }
                            }
                        }
                }

            }
        }
        if($agent['referee_id']){
            $referee_info =  $member->getInfo(['uid'=>$agent['referee_id']],'*');
            if($referee_info['is_team_agent']!=2){
                $this->becomeAgent($agent['referee_id']);
            }
        }
    }
    /*
      * 队长详情(升级条件)
      */
    public function getAgentLowerInfo($uid){
        $agent = new VslMemberModel();
        if($this->website_id){
            $website_id = $this->website_id;
        }else{
            $website_id =  $agent->getInfo(['uid'=>$uid],'website_id')['website_id'];
        }
        $order_model = new VslOrderModel();
        $config = new AddonsConfigService();
        $info1 = $config->getAddonsConfig('distribution',$website_id);
        $order_commission = new VslOrderDistributorCommissionModel();
        $commission_order_id = implode(',',$order_commission->Query(['website_id'=>$website_id],'order_id'));
        $list = json_decode($info1['value'], true);
        $result = [];
        $result['agentcount'] = 0;//团队数
        $result['agentcount1'] = 0;//客户数
        $result['one_number1'] = 0;//一级团队人数
        $result['two_number1'] = 0;//二级团队人数
        $result['three_number1'] = 0;//三级团队人数
        $result['one_number2'] = 0;//一级客户人数
        $result['two_number2'] = 0;//二级客户人数
        $result['three_number2'] = 0;//三级客户人数
        $result['selforder_money'] = 0;//自购订单金额
        $result['order_money'] = 0;//直属下级订单金额

        $result['up_team_money'] = 0;//团队分销订单金额

        //是否发生过降级 产生降级后 统计条件发生改变 down_up_team_level_time 
        $resMember = $agent->getInfo(['uid' => $uid],"down_up_team_level_time");
        if($resMember['down_up_team_level_time']){
            $order_ids = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$resMember['down_up_team_level_time']]],'order_id');
            $order_pay_money = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$resMember['down_up_team_level_time']]],'order_money');
            $result['selforder_money'] = array_sum($order_pay_money);//自购订单金额
            $result['selforder_number'] = count($order_ids);//自购订单数
            if(1 <= $list['distribution_pattern']){
                $idslevel1 = $agent->Query(['referee_id'=>$uid,'reg_time'=>[">",$resMember['down_up_team_level_time']]],'uid');
                $idslevel_1 = $agent->Query(['referee_id'=>$uid,'isdistributor'=>2,'reg_time'=>[">",$resMember['down_up_team_level_time']]],'uid');
                $idslevel_2 = $agent->Query(['referee_id'=>$uid,'isdistributor'=>['neq',2],'reg_time'=>[">",$resMember['down_up_team_level_time']]],'uid');
                //edit by 2019/12/03 订单统计范围为所有 下级统计不变 按指定时间查询
                $oldidslevel1 = $agent->Query(['referee_id'=>$uid],'uid');
                $oldidslevel_1 = $agent->Query(['referee_id'=>$uid,'isdistributor'=>2],'uid');
                if($oldidslevel1){
                    $order_ids1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$oldidslevel_1)],'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$resMember['down_up_team_level_time']]],'order_id');
                    $order1_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$oldidslevel_1)],'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$resMember['down_up_team_level_time']]],'order_money');
                    $result['order1'] = count($order_ids1);//一级分销订单总数
                    $result['one_number1'] = count($idslevel_1);//一级团队人数
                    $result['one_number2'] = count($idslevel_2);//一级客户人数
                    $result['order1_money'] = array_sum($order1_money1);//一级分销商订单总金额
                    $result['up_team_money'] += $result['order1_money'];
                    $result['agentcount'] += $result['one_number1'];
                    $result['agentcount1'] += $result['one_number2'];
                    $result['order_money'] += $result['order1_money'];
                }
            }
            if(2 <= $list['distribution_pattern']){
                if($result['one_number1']>0 || count($oldidslevel_1) > 0){
                    $idslevel2 = $agent->Query(['referee_id'=>['in',implode(',',$oldidslevel1)],'reg_time'=>[">",$resMember['down_up_team_level_time']]],'uid');
                    $idslevel_2 = $agent->Query(['referee_id'=>['in',implode(',',$oldidslevel1)],'isdistributor'=>2,'reg_time'=>[">",$resMember['down_up_team_level_time']]],'uid');
                    $idslevel2_2 = $agent->Query(['referee_id'=>['in',implode(',',$oldidslevel1)],'isdistributor'=>['neq',2],'reg_time'=>[">",$resMember['down_up_team_level_time']]],'uid');
                    //edit by 2019/12/03 订单统计范围为所有 下级统计不变 按指定时间查询
                    $oldidslevel2 = $agent->Query(['referee_id'=>['in',implode(',',$oldidslevel1)]],'uid');
                    $oldidslevel_2 = $agent->Query(['referee_id'=>['in',implode(',',$oldidslevel1)],'isdistributor'=>2],'uid');
                    if($oldidslevel2){
                        $order_ids2 = $order_model->Query(['buyer_id'=>['in',implode(',',$oldidslevel_2)],'order_status'=>4,'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$resMember['down_up_team_level_time']]],'order_id');
                        $order2_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$oldidslevel_2)],'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$resMember['down_up_team_level_time']]],'order_money');
                        $result['order2'] = count($order_ids2);//二级分销订单总数
                        $result['two_number1'] = count($idslevel_2);//一级团队人数
                        $result['two_number2'] = count($idslevel2_2);//一级客户人数
                        $result['order2_money'] = array_sum($order2_money1);//二级分销商订单总金额
                        $result['up_team_money'] += $result['order2_money'];
                        $result['agentcount'] += $result['two_number1'];
                        $result['agentcount1'] += $result['two_number2'];
                    }
                }
            }
            if(3 <= $list['distribution_pattern']){
                if($result['two_number1']>0 || count($oldidslevel_2) > 0){
                    $idslevel3 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel2)],'reg_time'=>[">",$resMember['down_up_team_level_time']]],'uid');
                    $idslevel_3 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel2)],'isdistributor'=>2,'reg_time'=>[">",$resMember['down_up_team_level_time']]],'uid');
                    $idslevel3_3 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel2)],'isdistributor'=>['neq',2],'reg_time'=>[">",$resMember['down_up_team_level_time']]],'uid');
                    //edit by 2019/12/03 订单统计范围为所有 下级统计不变 按指定时间查询
                    $oldidslevel3 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel2)]],'uid');
                    $oldidslevel_3 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel2)],'isdistributor'=>2],'uid');
                    if($oldidslevel3){
                        $order_ids3 = $order_model->Query(['buyer_id'=>['in',implode(',',$oldidslevel_3)],'order_status'=>4,'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$resMember['down_up_team_level_time']]],'order_id');
                        $order3_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$oldidslevel_3)],'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$resMember['down_up_team_level_time']]],'order_money');
                        $result['order2'] = count($order_ids3);//三级分销商订单总数
                        $result['three_number1'] = count($idslevel_3);//一级团队人数
                        $result['three_number2'] = count($idslevel3_3);//一级客户人数
                        $result['order3_money'] = array_sum($order3_money1);//三级分销商订单总金额
                        $result['up_team_money'] += $result['order3_money'];
                        $result['agentcount'] += $result['three_number1'];
                        $result['agentcount1'] += $result['three_number2'];
                    }
                }
            }
        }else{ //未发生过降级
            $order_ids = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id]],'order_id');
            $order_pay_money = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id]],'order_money');
            $result['selforder_money'] = array_sum($order_pay_money);//自购订单金额
            $result['selforder_number'] = count($order_ids);//自购订单数
            if(1 <= $list['distribution_pattern']){
                $idslevel1 = $agent->Query(['referee_id'=>$uid],'uid');
                $idslevel_1 = $agent->Query(['referee_id'=>$uid,'isdistributor'=>2],'uid');
                $idslevel_2 = $agent->Query(['referee_id'=>$uid,'isdistributor'=>['neq',2]],'uid');
                if($idslevel1){
                    $order_ids1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel_1)],'order_id'=>['in',$commission_order_id]],'order_id');
                    $order1_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel_1)],'order_id'=>['in',$commission_order_id]],'order_money');
                    $result['order1'] = count($order_ids1);//一级分销订单总数
                    $result['one_number1'] = count($idslevel_1);//一级团队人数
                    $result['one_number2'] = count($idslevel_2);//一级客户人数
                    $result['order1_money'] = array_sum($order1_money1);//一级分销商订单总金额
                    $result['up_team_money'] += $result['order1_money'];
                    $result['agentcount'] += $result['one_number1'];
                    $result['agentcount1'] += $result['one_number2'];
                    $result['order_money'] += $result['order1_money'];
                }
            }
            if(2 <= $list['distribution_pattern']){
                if($result['one_number']>0){
                    $idslevel2 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel1)]],'uid');
                    $idslevel_2 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel1)],'isdistributor'=>2],'uid');
                    $idslevel2_2 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel1)],'isdistributor'=>['neq',2]],'uid');
                    if($idslevel2){
                        $order_ids2 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevel_2)],'order_status'=>4,'order_id'=>['in',$commission_order_id]],'order_id');
                        $order2_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel_2)],'order_id'=>['in',$commission_order_id]],'order_money');
                        $result['order2'] = count($order_ids2);//二级分销订单总数
                        $result['two_number1'] = count($idslevel_2);//一级团队人数
                        $result['two_number2'] = count($idslevel2_2);//一级客户人数
                        $result['order2_money'] = array_sum($order2_money1);//二级分销商订单总金额
                        $result['up_team_money'] += $result['order2_money'];
                        $result['agentcount'] += $result['two_number1'];
                        $result['agentcount1'] += $result['two_number2'];
                    }
                }
            }
            if(3 <= $list['distribution_pattern']){
                if($result['two_number']>0){
                    $idslevel3 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel2)]],'uid');
                    $idslevel_3 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel2)],'isdistributor'=>2],'uid');
                    $idslevel3_3 = $agent->Query(['referee_id'=>['in',implode(',',$idslevel2)],'isdistributor'=>['neq',2]],'uid');
                    if($idslevel3){
                        $order_ids3 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevel_3)],'order_status'=>4,'order_id'=>['in',$commission_order_id]],'order_id');
                        $order3_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel_3)],'order_id'=>['in',$commission_order_id]],'order_money');
                        $result['order2'] = count($order_ids3);//三级分销商订单总数
                        $result['three_number1'] = count($idslevel_3);//一级团队人数
                        $result['three_number2'] = count($idslevel3_3);//一级客户人数
                        $result['order3_money'] = array_sum($order3_money1);//三级分销商订单总金额
                        $result['up_team_money'] += $result['order3_money'];
                        $result['agentcount'] += $result['three_number1'];
                        $result['agentcount1'] += $result['three_number2'];
                    }
                }
            }
        }

        
        return $result;
    }
    /**
     * 获得团队分红统计
     */
    public function getAgentCount($website_id)
    {
        $start_date = strtotime(date("Y-m-d"),time());
        $end_date = strtotime(date('Y-m-d',strtotime('+1 day')));
        $member = new VslMemberModel();
        $data['agent_total'] = $member->getCount(['website_id'=>$website_id,'is_team_agent'=>2]);
        $data['agent_today'] = $member->getCount(['website_id'=>$website_id,'is_team_agent'=>2,'become_team_agent_time'=>[[">",$start_date],["<",$end_date]]]);
        $account = new VslBonusAccountModel();
        $bonus_total = $account->Query(['website_id'=>$website_id,'from_type'=>3],'total_bonus');
        $data['total_bonus'] = array_sum($bonus_total);
        $grant_bonus = $account->Query(['website_id'=>$website_id,'from_type'=>3],'grant_bonus');
        $data['grant_bonus'] = array_sum($grant_bonus);
        return $data;
    }

    /**
     * 获得近七天的分红订单分红金额
     */
    public function getPayMoneySum($condition)
    {
        $order = new VslOrderModel();
        $orderids = $order->Query($condition,'order_id');
        $orderids = implode(',',$orderids);
        $order_bonus = new VslOrderBonusModel();
        $orders = $order_bonus->Query(['order_id'=>['in',$orderids],'website_id'=>$condition['website_id'],'from_type'=>3],'bonus');
        $count = array_sum($orders);
        return $count;
    }
    /**
     * 获得近七天的分红订单金额
     */
    public function getOrderMoneySum($condition)
    {
        $order = new VslOrderBonusModel();
        $orderids = array_unique($order->Query(['website_id'=>$condition['website_id'],'from_type'=>3],'order_id'));
        $orderids = implode(',',$orderids);
        $condition['order_id'] = ['in',$orderids];
        $order_model = new VslOrderModel();
        $money_sum = $order_model->where($condition)->sum('order_money');
        return $money_sum;
    }
    /**
     * 分红发放列表
     */
    public function getBonusGrantList($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $Bonus_withdraw = new VslBonusGrantModel();
        $list = $Bonus_withdraw->getViewList2($page_index, $page_size, $condition, 'nmar.grant_time desc');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if(empty($list['data'][$k]['user_name'])){
                    $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
                $list['data'][$k]['grant_time'] = date('Y-m-d H:i:s',$v['grant_time']);
            }
        }
        return $list;
    }
    /**
     * 分红未发放列表
     */
    public function getUnGrantBonus($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $bonus = new VslBonusAccountModel();
        $ungrant_order = new VslUnGrantBonusOrderModel();
        $list = $bonus->getViewList2($page_index, $page_size, $condition,'');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if(empty($list['data'][$k]['user_name'])){
                    $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
            }
        }
        $list['ungrant_bonus'] = array_sum($bonus->Query(['from_type'=>3,'website_id'=>$this->website_id],'ungrant_bonus'));
        $list['total_agent'] = $bonus->getCount(['from_type'=>3,'ungrant_bonus'=>['>',0],'website_id'=>$this->website_id]);
        $order_nos = array_unique($ungrant_order->Query(['grant_status'=>1,'from_type'=>3,'website_id'=>$this->website_id],'order_id'));
        $order = new VslOrderModel();
        $list['order_money'] = array_sum($order->Query(['order_no'=>['in',implode(',',$order_nos)],'website_id'=>$this->website_id],'order_money'));
        return $list;
    }

    /**
     * 分红明细列表
     */
    public function getBonusDetailList($page_index, $page_size,$condition,$group)
    {
        $bonus_grant = new VslBonusGrantModel();
        $list = $bonus_grant->getViewLists($page_index, $page_size, $condition, 'nmar.grant_time desc',$group);
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if(empty($list['data'][$k]['user_name'])){
                    $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
                $list['data'][$k]['grant_time'] = date('Y-m-d H:i:s',$v['grant_time']);
                $list['data'][$k]['bonus_number'] = count($bonus_grant->where(['sn'=>$v['sn']])->group('uid')->select());
            }
        }
        return $list;
    }
    /**
     * 分红详情列表
     */
    public function getBonusInfoList($page_index, $page_size, $condition, $order = '', $where)
    {
        $bonus_grant = new VslBonusGrantModel();
        $list = $bonus_grant->getViewListInfo2($page_index, $page_size, $condition, 'nmar.grant_time desc');
        $level = new VslAgentLevelModel();
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if(empty($list['data'][$k]['user_name'])){
                    $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
                $list['data'][$k]['grant_time'] = date('Y-m-d H:i:s',$v['grant_time']);
                $list['data'][$k]['level_name'] = $level->getInfo(['id'=>$list['data'][$k]['team_agent_level_id']])['level_name'];
            }
        }
        return $list;
    }
    /**
     * 分红流水列表
     */
    public function getBonusRecords($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $Bonus_withdraw = new VslAgentAccountRecordsModel();
        $list = $Bonus_withdraw->getViewList($page_index, $page_size, $condition, 'nmar.create_time desc');
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if(empty($list['data'][$k]['user_name'])){
                    $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
                $list['data'][$k]['text'] = str_replace("待发放分红",$this->wit_bonus,$list['data'][$k]['text']);
                $list['data'][$k]['text'] = str_replace("冻结分红",$this->fre_bonus,$list['data'][$k]['text']);
                $list['data'][$k]['text'] = str_replace("已发放分红",$this->wits_bonus,$list['data'][$k]['text']);
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
        }
        return $list;
    }
}
