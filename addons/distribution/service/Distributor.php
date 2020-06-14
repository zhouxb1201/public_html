<?php
namespace addons\distribution\service;
/**
 * 分销商服务层
 */
use addons\areabonus\service\AreaBonus;
use addons\bonus\model\VslAgentLevelModel;
use addons\bonus\model\VslBonusAccountModel;
use addons\bonus\model\VslOrderBonusModel;
use addons\distribution\model\VslDistributorAccountRecordsModel;
use addons\globalbonus\service\GlobalBonus;
use addons\groupshopping\server\GroupShopping;
use addons\microshop\model\VslOrderMicroShopProfitModel;
use addons\store\model\VslStoreAssistantModel;
use addons\store\model\VslStoreModel;
use addons\teambonus\service\TeamBonus;
use data\model\VslAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslMemberModel;
use data\model\VslMemberViewModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderGoodsPromotionDetailsModel;
use data\model\VslOrderMemoModel;
use data\model\VslOrderModel;
use data\model\VslOrderGoodsExpressModel;
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
use addons\distribution\model\VslDistributorLevelModel as DistributorLevelModel;
use data\service\Order as OrderService;
use data\service\Config as ConfigService;
use data\model\ConfigModel as ConfigModel;
use data\service\Order\OrderStatus;
use addons\distribution\model\VslOrderDistributorCommissionModel ;
use addons\distribution\model\VslDistributorAccountModel;
use addons\distribution\model\VslDistributorAccountRecordsViewModel;
use addons\distribution\model\VslDistributorCommissionWithdrawModel;
use data\service\Order\OrderAccount;
use data\service\Pay\tlPay;
use data\service\ShopAccount;
use data\model\VslMemberBankAccountModel;
use data\model\VslMemberAccountModel;
use think\db;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
use data\service\Pay\AliPay;
use data\service\Pay\WeiXinPay;
use addons\customform\server\Custom as CustomServer; 
use data\model\VslOrderGoodsViewModel;
use addons\channel\server\Channel;
use addons\distribution\service\Distributor as  DistributorService;
class Distributor extends BaseService
{
    private $config_module;
    private $fre_commission;
    private $wit_commission;
    private $wits_commission;
    private $distributor;
    function __construct()
    {
        parent::__construct();
        $this->config_module = new ConfigModel();
        $this->addons_config_module = new AddonsConfigModel();
        $set = $this->getAgreementSite($this->website_id);
        if($set && $set['distributor_name']){
            $this->distributor = $set['distributor_name'];
        }else{
            $this->distributor = '分销商';
        }
        if($set && $set['frozen_commission']){
            $this->fre_commission = $set['frozen_commission'];
        }else{
            $this->fre_commission = '冻结佣金';
        }
        if($set &&  $set['withdrawable_commission']){
            $this->wit_commission = $set['withdrawable_commission'];
        }else{
            $this->wit_commission = '可提现佣金';
        }
        if($set &&  $set['withdrawals_commission']){
            $this->wits_commission = $set['withdrawals_commission'];
        }else{
            $this->wits_commission = '已提现佣金';
        }
    }

   /**
    * 修改分销商申请状态
    */
    public function setStatus($uid, $status){
        $level = new DistributorLevelModel();
        $level_info = $level->getInfo(['website_id'=>$this->website_id,'is_default'=>1],'*');
        $level_id = $level_info ['id'];
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid'=>$uid]);
        if($status==2){
            $extend_code = $this->create_extend();
            $data = array(
                'isdistributor' => $status,
                'extend_code'=>$extend_code,
                'apply_distributor_time' => time(),
                'become_distributor_time' => time(),
                'distributor_level_id'=>$level_id
            );
        }else{
            $data = array(
                'isdistributor' => $status
            );
        }
        $res =$member->save($data,[
            'uid'=>$uid
        ]);
        if($res && $status==2){
            $config = new AddonsConfigService();
            $info = $config->getAddonsConfig("distribution",$this->website_id);//基本设置
            $base_info = json_decode($info['value'], true);
            $distribution_pattern = $base_info['distribution_pattern'];
            $ratio = '';
            if($distribution_pattern>=1){
                $ratio .= '一级返佣比例'.$level_info['commission1'].'%';
            }
            if($distribution_pattern>=2){
                $ratio .= ',二级返佣比例'.$level_info['commission2'].'%';
            }
            if($distribution_pattern>=3){
                $ratio .= ',三级返佣比例'.$level_info['commission3'].'%';
            }

            if($base_info['distribution_pattern']>=1){
                if($member_info['referee_id']){
                    $recommend1_info = $member->getInfo(['uid'=>$member_info['referee_id']],'*');
                    if($recommend1_info && $recommend1_info['isdistributor']==2){
                        $level_info1 = $level->getInfo(['id' => $recommend1_info['distributor_level_id'],'website_id' => $member_info['website_id']]);
                        $recommend1 = $level_info1['recommend1'];//一级推荐奖
                        $recommend_point1 = $level_info1['recommend_point1'];//一级推荐积分
                        $this->addRecommed($uid,$recommend1_info['uid'],$recommend1,$recommend_point1,$member_info['website_id']);
                    }
                }
            }
            if($base_info['distribution_pattern']>=2){
                $recommend2_info = $member->getInfo(['uid'=>$recommend1_info['referee_id']],'*');
                if($recommend2_info && $recommend2_info['isdistributor']==2) {
                    $level_info2 = $level->getInfo(['id' => $recommend2_info['distributor_level_id'],'website_id' => $member_info['website_id']]);
                    $recommend2 = $level_info2['recommend2'];//二级推荐奖
                    $recommend_point2 = $level_info2['recommend_point2'];//二级推荐积分
                    $this->addRecommed($uid,$recommend2_info['uid'],$recommend2,$recommend_point2,$member_info['website_id']);
                }
            }
            if($base_info['distribution_pattern']>=3){
                $recommend3_info = $member->getInfo(['uid'=>$recommend2_info['referee_id']],'*');
                if($recommend3_info && $recommend3_info['isdistributor']==2) {
                    $level_info3 = $level->getInfo(['id' => $recommend3_info['distributor_level_id'],'website_id' => $member_info['website_id']]);
                    $recommend3 = $level_info3['recommend3'];//三级推荐奖
                    $recommend_point3 = $level_info3['recommend_point3'];//三级推荐积分
                    $this->addRecommed($uid,$recommend3_info['uid'],$recommend3,$recommend_point3,$member_info['website_id']);
                }
            }
            runhook("Notify", "sendCustomMessage", ["messageType"=>"become_distributor","uid" => $uid,"become_time" => time(),'ratio'=>$ratio,'level_name'=>$level_info['level_name']]);//用户成为分销商提醒
            runhook("Notify", "successfulDistributorByTemplate", ["uid" => $uid,"website_id" => $this->website_id]);//用户成为分销商提醒
        }
        return $res;
    }
    public function updateMemberDistributor($uid){
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid'=>$uid],'*');
        $level = new DistributorLevelModel();
        $level_info = $level->getInfo(['website_id'=>$this->website_id,'is_default'=>1],'*');
        $level_id = $level_info ['id'];
        $extend_code = $this->create_extend();
        $data = array(
            'isdistributor' => 2,
            'extend_code'=>$extend_code,
            'become_distributor_time' => time(),
            'distributor_level_id'=>$level_id
        );
        $account = new VslDistributorAccountModel();
        $account->save(['website_id'=>$this->website_id,'uid'=>$uid]);
        $referee_id = $member->getInfo(['uid'=>$uid],'referee_id')['referee_id'];
        if($referee_id){
            $this->updateDistributorLevelInfo($referee_id);
            if(getAddons('globalbonus', $this->website_id)){
                $global = new GlobalBonus();
                $global->updateAgentLevelInfo($referee_id);
                $global->becomeAgent($referee_id);
            }
            if(getAddons('areabonus', $this->website_id)){
                $area = new AreaBonus();
                $area->updateAgentLevelInfo($referee_id);
            }
            if(getAddons('teambonus', $this->website_id)){
                $team = new TeamBonus();
                $team->updateAgentLevelInfo($referee_id);
                $team->becomeAgent($referee_id);
            }
        }
        $res = $member->save($data,[
            'uid'=>$uid
        ]);
        $config = new AddonsConfigService();
        $info = $config->getAddonsConfig("distribution",$this->website_id);//基本设置
        $base_info = json_decode($info['value'], true);
        $distribution_pattern = $base_info['distribution_pattern'];
        $ratio = '';
        if($distribution_pattern>=1){
            $ratio .= '一级返佣比例'.$level_info['commission1'].'%';
        }
        if($distribution_pattern>=2){
            $ratio .= ',二级返佣比例'.$level_info['commission2'].'%';
        }
        if($distribution_pattern>=3){
            $ratio .= ',三级返佣比例'.$level_info['commission3'].'%';
        }
        if($base_info['distribution_pattern']>=1){
            if($member_info['referee_id']){
                $recommend1_info = $member->getInfo(['uid'=>$member_info['referee_id']],'*');
                if($recommend1_info && $recommend1_info['isdistributor']==2){
                    $level_info1 = $level->getInfo(['id' => $recommend1_info['distributor_level_id'],'website_id' => $member_info['website_id']]);
                    $recommend1 = $level_info1['recommend1'];//一级推荐奖
                    $recommend_point1 = $level_info1['recommend_point1'];//一级推荐积分
                    $this->addRecommed($uid,$recommend1_info['uid'],$recommend1,$recommend_point1,$member_info['website_id']);
                }
            }
        }
        if($base_info['distribution_pattern']>=2){
            $recommend2_info = $member->getInfo(['uid'=>$recommend1_info['referee_id']],'*');
            if($recommend2_info && $recommend2_info['isdistributor']==2) {
                $level_info2 = $level->getInfo(['id' => $recommend2_info['distributor_level_id'],'website_id' => $member_info['website_id']]);
                $recommend2 = $level_info2['recommend2'];//二级推荐奖
                $recommend_point2 = $level_info2['recommend_point2'];//二级推荐积分
                $this->addRecommed($uid,$recommend2_info['uid'],$recommend2,$recommend_point2,$member_info['website_id']);
            }
        }
        if($base_info['distribution_pattern']>=3){
            $recommend3_info = $member->getInfo(['uid'=>$recommend2_info['referee_id']],'*');
            if($recommend3_info && $recommend3_info['isdistributor']==2) {
                $level_info3 = $level->getInfo(['id' => $recommend3_info['distributor_level_id'],'website_id' => $member_info['website_id']]);
                $recommend3 = $level_info3['recommend3'];//三级推荐奖
                $recommend_point3 = $level_info3['recommend_point3'];//三级推荐积分
                $this->addRecommed($uid,$recommend3_info['uid'],$recommend3,$recommend_point3,$member_info['website_id']);
            }
        }
        runhook("Notify", "sendCustomMessage", ["messageType"=>"become_distributor","uid" => $uid,"become_time" => time(),'ratio'=>$ratio,'level_name'=>$level_info['level_name']]);//用户成为分销商提醒
        return $res;
    }
    /**
     * 修改推荐人
     */
    public function updateRefereeDistributor($uid,$referee_id){
        if($uid!=$referee_id){
            $shop = new VslMemberModel();
            $data = array(
                "referee_id" => $referee_id
            );
            $res =$shop->save($data,[
                'uid'=>$uid
            ]);
            $this->updateDistributorLevelInfo($referee_id);
            if(getAddons('globalbonus', $this->website_id)){
                $global = new GlobalBonus();
                $global->becomeAgent($referee_id);
                $global->updateAgentLevelInfo($referee_id);
            }
            if(getAddons('areabonus', $this->website_id)){
                $area = new AreaBonus();
                $area->updateAgentLevelInfo($referee_id);
            }
            if(getAddons('teambonus', $this->website_id)){
                $team = new TeamBonus();
                $team->becomeAgent($referee_id);
                $team->updateAgentLevelInfo($referee_id);
            }
            return $res;
        }else{
            return -1;
        }

    }
    /**
     * 修改推荐人
     */
    public function updateLowerRefereeDistributor($uid,$referee_id){
        if($uid!=$referee_id){
            $member = new VslMemberModel();
            $uids = $member->Query(['referee_id'=>$uid],'uid');
            $data = array(
                "referee_id" => $referee_id
            );
            $res = $member->save($data,[
                'uid'=>['in',implode(',',$uids)]
            ]);
            $this->updateDistributorLevelInfo($referee_id);
            if(getAddons('globalbonus', $this->website_id)){
                $global = new GlobalBonus();
                $global->updateAgentLevelInfo($referee_id);
                $global->becomeAgent($referee_id);
            }
            if(getAddons('areabonus', $this->website_id)){
                $area = new AreaBonus();
                $area->updateAgentLevelInfo($referee_id);
            }
            if(getAddons('teambonus', $this->website_id)){
                $team = new TeamBonus();
                $team->updateAgentLevelInfo($referee_id);
                $team->becomeAgent($referee_id);
            }
            return $res;
        }else{
            return -1;
        }

    }
    /**
     * 检查是否存在直属下级
     */
    public function checkDistributor($uid){
        $shop = new VslMemberModel();
        $id = $shop->getInfo([ "referee_id" => $uid],'*');
        $res=-1;
        if($id){
            $res=1;
        }
        return $res;
    }
    /**
     * 判断是否开启自定义模版
     *
     */
    public function check_start_template($condition){

        return Db::table('sys_custom_template')->where($condition)->value('id');

    }

    //获取所有下级
    function getAllChild($uid=0,$website_id=0){
        
        if(empty($uid)){
            return [];
        }
        $distributor = new VslMemberModel();
        $allchild = array();
        
        $level1_agentids = $distributor->Query(['referee_id'=>$uid,'website_id'=>$website_id],'uid');
        
        if(!empty($level1_agentids)){
            $ids = array_values($level1_agentids);
            
            $allchild = array_merge($allchild,$ids);
            
            $idss = implode(",",$ids);
            
            $temp = $this->getAllChildByIn($idss,$website_id);
            
            $allchild = array_merge($allchild,$temp);
            
        }
        return $allchild;
    }
    function getAllChildByIn($idss,$website_id){
        
        $distributor = new VslMemberModel();
        $level1_agentids = $distributor->Query(['referee_id'=>['in',$idss],'website_id'=>$website_id],'uid');
        $ids = array_values($level1_agentids);
        if(!empty($ids)){
            $idss = implode(",",$ids);
            $temp = $this->getAllChildByIn($idss,$website_id);
            $ids = array_merge($ids,$temp);
        }
        return $ids;
    }
    /**
     * 后台客户列表
     */
    public function getDistributorList2($uid,$page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        
        $distributor = new VslMemberModel();
        $user = new UserModel();
        $website_id = $where['nm.website_id'];
        $distributor_view = new VslMemberViewModel();
        $list = $this->getDistributionSite($website_id);
        if($uid &&  $list['distribution_pattern']>=1){
            $id1 = $distributor->Query(['referee_id'=>$uid,'isdistributor'=>['neq', 2],'website_id'=>$website_id],'uid');
            if($id1){
                $where['nm.uid'] = ['in',implode(',',$id1)];
            }
            else{
                $where['nm.uid'] = ['in',''];
            }
        }
        $result = $distributor_view->getDistributorViewList($page_index, $page_size, $where, $order);
       
        $condition['website_id'] = $website_id;
        $condition['isdistributor'] = ['in','1,0,-1'];
        $result['count'] = $distributor_view->getCount($condition);
        $condition['isdistributor'] = 2;
        $result['count1'] = $distributor_view->getCount($condition);
        $condition['isdistributor'] = 1;
        $result['count2'] = $distributor_view->getCount($condition);
        $condition['isdistributor'] = -1;
        $result['count3'] = $distributor_view->getCount($condition);
        if($result['data']){
            foreach ($result['data'] as $k => $v) {
                //是否拥有设为股东 设为区代 设为队长 设为渠道商 设为店长权限 查询会员是否已经是已有权限
                
                $result['data'][$k]['global_status'] = getAddons('globalbonus', $website_id);
                $result['data'][$k]['area_status'] = getAddons('areabonus', $website_id);
                $result['data'][$k]['team_status'] = getAddons('teambonus', $website_id);
                $result['data'][$k]['microshop_status'] = getAddons('microshop', $website_id);
                $result['data'][$k]['channel_status'] = getAddons('channel', $website_id);
                if($v['is_global_agent'] == 2){
                    $result['data'][$k]['global_status'] = 0;
                }
                if($v['is_area_agent'] == 2){
                    $result['data'][$k]['area_status'] = 0;
                }
                if($v['is_team_agent'] == 2){
                    $result['data'][$k]['team_status'] = 0;
                }
                if($v['isshopkeeper'] == 2){
                    $result['data'][$k]['microshop_status'] = 0;
                }
                if($result['data'][$k]['channel_status']){
                    //查询当前会员是否是渠道商
                    $channel = new Channel();

                    $condition_channel['c.website_id'] = $v['website_id'];
                    $condition_channel['c.uid'] = $v['uid'];

                    $channel_info = $channel->getMyChannelInfo($condition_channel); 
                    
                    if($channel_info){
                        $result['data'][$k]['channel_status'] = 0;
                    }

                }
                
                $agentcount = 0;
                $ids1=0;
                $ids2=0;
                $result['data'][$k]['commission'] = 0;
                $user_info = $user->getInfo(['uid'=>$v['referee_id']],'user_name,nick_name,user_headimg');
                $result['data'][$k]['withdrawals'] = 0;
                if($user_info['user_name']){
                    $result['data'][$k]['referee_name'] = $user_info['user_name'];//推荐人
                }else{
                    $result['data'][$k]['referee_name'] = $user_info['nick_name'];//推荐人
                }
                if( empty($result['data'][$k]['user_name'])){
                    $result['data'][$k]['user_name'] =  $result['data'][$k]['nick_name'];
                }
                $result['data'][$k]['referee_headimg'] = $user_info['user_headimg'];//推荐人
                if(1 <= $list['distribution_pattern']){
                    $ids1 = $distributor->Query(['referee_id'=>$v['uid']],'uid');
                    if($ids1){
                        $number1 = count($ids1);//一级人数
                        $agentcount += $number1;
                    }
                }
                if(2 <= $list['distribution_pattern']){
                    if($ids1){
                        $ids2 = $distributor->Query(['referee_id'=>['in',implode(',',$ids1)]],'uid');
                        if($ids2){
                            $number2 = count($ids2);//二级人数
                            $agentcount += $number2;
                        }
                    }
                }
                if(3 <= $list['distribution_pattern']){
                    if($ids2){
                        $ids3 = $distributor->Query(['referee_id'=>['in',implode(',',$ids2)]],'uid');
                        if($ids3){
                            $number3 = count($ids3);//三级人数
                            $agentcount += $number3;
                        }
                    }
                }
                $result['data'][$k]['lower_id'] = $distributor->Query(['referee_id'=>$v['uid']],'uid');//当前用户是否有下级
                $result['data'][$k]['distributor_number'] = $agentcount ;//下级总人数
                $commission = new VslDistributorAccountModel();
                $commission_info = $commission->getInfo(['uid'=>$v['uid']],'*');
                if($commission_info){
                    $result['data'][$k]['commission'] = $commission_info['commission'];//可用佣金
                    $result['data'][$k]['withdrawals'] = $commission_info['withdrawals'];//提现佣金
                }
               
            }
        }
        if($uid){
            $result['commission'] = 0;
            $result['withdrawals'] = 0;
            $result['number1'] = 0;
            $result['user_count'] = 0;
            $result['number2'] = 0;
            $result['number3'] = 0;
            $result['agentcount'] = 0;
            $result['all_child'] = 0;
            //获取所有下级
            $all_child = $this->getAllChild($uid,$website_id);
            if($all_child){
                $total_child = $distributor->Query(['isdistributor'=>2,'uid'=>['in',implode(',',$all_child)]],'uid');
                if($total_child){
                    $result['all_child'] = count($total_child);
                }
            }
            
            $user = new UserModel();
            $user_info = $user->getInfo(['uid'=>$uid],'user_headimg,user_name,nick_name');
            $result['user_headimg'] = $user_info['user_headimg'];//获取分销商头像
            if($user_info['user_name']){
                $result['member_name'] = $user_info['user_name'];//获取分销商名称
            }else{
                $result['member_name'] = $user_info['nick_name'];//获取分销商名称
            }
            $info = $distributor->getInfo(['uid'=>$uid],'*');//获取分销商信息
            $result['real_name'] = $info['real_name'];//获取分销商真实名称
            $result['mobile'] = $info['mobile'];//获取分销商手机号
            $commission = new VslDistributorAccountModel();
            $commission_info = $commission->getInfo(['uid'=>$uid],'*');
            if($commission_info){
                $result['commission'] = $commission_info['commission'];//累积佣金
                $result['withdrawals'] = $commission_info['withdrawals'];//提现佣金
            }
            $distributor_level_id = $info['distributor_level_id'];
            $level = new DistributorLevelModel();
            $result['level_name'] = $level->getInfo(['id'=>$distributor_level_id],'level_name')['level_name'];//等级名称
            if(1 <= $list['distribution_pattern']){
                $idslevel1 = $distributor->Query(['isdistributor'=>2,'referee_id'=>$uid],'uid');
                if($idslevel1){
                    $result['number1'] = count($idslevel1);//一级分销商总人数
                    $result['agentcount'] += $result['number1'];
                }
                //获取1级客户数
                $id1 = $distributor->Query(['referee_id'=>$uid,'isdistributor' => ['neq', 2]],'uid');
                if($id1){
                    $result['user_count'] = count($id1);//一级分销商总人数
                }
            }
            if(2 <= $list['distribution_pattern']){
                if($result['number1']>0){
                    $idslevel2 = $distributor->Query(['isdistributor'=>2,'referee_id'=>['in',implode(',',$idslevel1)]],'uid');
                    if($idslevel2){
                        $result['number2'] = count($idslevel2);//二级分销商总人数
                        $result['agentcount'] += $result['number2'];
                    }
                }
            }
            if(3 <= $list['distribution_pattern']){
                if($result['number2']>0){
                    $idslevel3 = $distributor->Query(['isdistributor'=>2,'referee_id'=>['in',implode(',',$idslevel2)]],'uid');
                    if($idslevel3){
                        $result['number3'] = count($idslevel3);//三级分销商总人数
                        $result['agentcount'] += $result['number3'];
                    }
                }
            }
        }
        return $result;
    }
    /**
     * 获取分销商列表
     */
    public function getDistributorList($uid,$page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $distributor = new VslMemberModel();
        $user = new UserModel();
        $website_id = $where['nm.website_id'];
        $distributor_view = new VslMemberViewModel();
        $list = $this->getDistributionSite($website_id);
        if($uid &&  $list['distribution_pattern']>=1){
            $id1 = $distributor->Query(['referee_id'=>$uid,'isdistributor'=>2,'website_id'=>$website_id],'uid');
            if($id1){
                $where['nm.uid'] = ['in',implode(',',$id1)];
                if($id1 && $list['distribution_pattern']>=2){
                    $id2 = $distributor->Query(['referee_id'=>['in',implode(',',$id1)],'isdistributor'=>2,'website_id'=>$website_id],'uid');
                    if($id2){
                        $id3 = implode(',',$id1).','.implode(',',$id2);
                        $where['nm.uid'] = ['in',$id3];
                    }
                    if($id3 && $list['distribution_pattern']>=3){
                        $id4 = $distributor->Query(['referee_id'=>['in',implode(',',$id2)],'isdistributor'=>2,'website_id'=>$website_id],'uid');
                        if($id4){
                            $id5 = $id3.','.implode(',',$id4);
                            $where['nm.uid'] = ['in',$id5];
                        }
                    }
                }
            }
            else{
                $where['nm.uid'] = ['in',''];
            }
        }
        $result = $distributor_view->getDistributorViewList($page_index, $page_size, $where, $order);
        $condition['website_id'] = $website_id;
        $condition['isdistributor'] = ['in','1,2,-1'];
        $result['count'] = $distributor_view->getCount($condition);
        $condition['isdistributor'] = 2;
        $result['count1'] = $distributor_view->getCount($condition);
        $condition['isdistributor'] = 1;
        $result['count2'] = $distributor_view->getCount($condition);
        $condition['isdistributor'] = -1;
        $result['count3'] = $distributor_view->getCount($condition);
        if($result['data']){
            foreach ($result['data'] as $k => $v) {
                //是否拥有设为股东 设为区代 设为队长 设为渠道商 设为店长权限 查询会员是否已经是已有权限
                
                $result['data'][$k]['global_status'] = getAddons('globalbonus', $website_id);
                $result['data'][$k]['area_status'] = getAddons('areabonus', $website_id);
                $result['data'][$k]['team_status'] = getAddons('teambonus', $website_id);
                $result['data'][$k]['microshop_status'] = getAddons('microshop', $website_id);
                $result['data'][$k]['channel_status'] = getAddons('channel', $website_id);
                if($v['is_global_agent'] == 2){
                    $result['data'][$k]['global_status'] = 0;
                }
                if($v['is_area_agent'] == 2){
                    $result['data'][$k]['area_status'] = 0;
                }
                if($v['is_team_agent'] == 2){
                    $result['data'][$k]['team_status'] = 0;
                }
                if($v['isshopkeeper'] == 2){
                    $result['data'][$k]['microshop_status'] = 0;
                }
                if($result['data'][$k]['channel_status']){
                    //查询当前会员是否是渠道商
                    $channel = new Channel();

                    $condition_channel['c.website_id'] = $v['website_id'];
                    $condition_channel['c.uid'] = $v['uid'];

                    $channel_info = $channel->getMyChannelInfo($condition_channel); 
                    
                    if($channel_info){
                        $result['data'][$k]['channel_status'] = 0;
                    }

                }
                $agentcount = 0;
                $ids1=0;
                $ids2=0;
                $result['data'][$k]['commission'] = 0;
                $user_info = $user->getInfo(['uid'=>$v['referee_id']],'user_name,nick_name,user_headimg');
                $result['data'][$k]['withdrawals'] = 0;
                if($user_info['user_name']){
                    $result['data'][$k]['referee_name'] = $user_info['user_name'];//推荐人
                }else{
                    $result['data'][$k]['referee_name'] = $user_info['nick_name'];//推荐人
                }
                if( empty($result['data'][$k]['user_name'])){
                    $result['data'][$k]['user_name'] =  $result['data'][$k]['nick_name'];
                }
                $result['data'][$k]['referee_headimg'] = $user_info['user_headimg'];//推荐人
                if(1 <= $list['distribution_pattern']){
                    $ids1 = $distributor->Query(['referee_id'=>$v['uid']],'uid');
                    if($ids1){
                        $number1 = count($ids1);//一级人数
                        $agentcount += $number1;
                    }
                }
                if(2 <= $list['distribution_pattern']){
                    if($ids1){
                        $ids2 = $distributor->Query(['referee_id'=>['in',implode(',',$ids1)]],'uid');
                        if($ids2){
                            $number2 = count($ids2);//二级人数
                            $agentcount += $number2;
                        }
                    }
                }
                if(3 <= $list['distribution_pattern']){
                    if($ids2){
                        $ids3 = $distributor->Query(['referee_id'=>['in',implode(',',$ids2)]],'uid');
                        if($ids3){
                            $number3 = count($ids3);//三级人数
                            $agentcount += $number3;
                        }
                    }
                }
                $result['data'][$k]['lower_id'] = $distributor->Query(['referee_id'=>$v['uid']],'uid');//当前用户是否有下级
                $result['data'][$k]['distributor_number'] = $agentcount ;//下级总人数
                $commission = new VslDistributorAccountModel();
                $commission_info = $commission->getInfo(['uid'=>$v['uid']],'*');
                if($commission_info){
                    $result['data'][$k]['commission'] = $commission_info['commission'];//可用佣金
                    $result['data'][$k]['withdrawals'] = $commission_info['withdrawals'];//提现佣金
                }
                
            }
        }
        if($uid){
            $result['commission'] = 0;
            $result['withdrawals'] = 0;
            $result['number1'] = 0;
            $result['user_count'] = 0;
            $result['number2'] = 0;
            $result['number3'] = 0;
            $result['agentcount'] = 0;
            $result['all_child'] = 0;
            //获取所有下级
            $all_child = $this->getAllChild($uid,$website_id);
            if($all_child){
                $total_child = $distributor->Query(['isdistributor'=>2,'uid'=>['in',implode(',',$all_child)]],'uid');
                if($total_child){
                    $result['all_child'] = count($total_child);
                }
            }
            $user = new UserModel();
            $user_info = $user->getInfo(['uid'=>$uid],'user_headimg,user_name,nick_name');
            $result['user_headimg'] = $user_info['user_headimg'];//获取分销商头像
            if($user_info['user_name']){
                $result['member_name'] = $user_info['user_name'];//获取分销商名称
            }else{
                $result['member_name'] = $user_info['nick_name'];//获取分销商名称
            }
            $info = $distributor->getInfo(['uid'=>$uid],'*');//获取分销商信息
            $result['real_name'] = $info['real_name'];//获取分销商真实名称
            $result['mobile'] = $info['mobile'];//获取分销商手机号
            $commission = new VslDistributorAccountModel();
            $commission_info = $commission->getInfo(['uid'=>$uid],'*');
            if($commission_info){
                $result['commission'] = $commission_info['commission'];//累积佣金
                $result['withdrawals'] = $commission_info['withdrawals'];//提现佣金
            }
            $distributor_level_id = $info['distributor_level_id'];
            $level = new DistributorLevelModel();
            $result['level_name'] = $level->getInfo(['id'=>$distributor_level_id],'level_name')['level_name'];//等级名称
            if(1 <= $list['distribution_pattern']){
                $idslevel1 = $distributor->Query(['isdistributor'=>2,'referee_id'=>$uid],'uid');
                if($idslevel1){
                    $result['number1'] = count($idslevel1);//一级分销商总人数
                    $result['agentcount'] += $result['number1'];
                }
                //获取1级客户数
                $id1 = $distributor->Query(['referee_id'=>$uid,'isdistributor' => ['neq', 2]],'uid');
                if($id1){
                    $result['user_count'] = count($id1);//一级分销商总人数
                }
            }
            if(2 <= $list['distribution_pattern']){
                if($result['number1']>0){
                    $idslevel2 = $distributor->Query(['isdistributor'=>2,'referee_id'=>['in',implode(',',$idslevel1)]],'uid');
                    if($idslevel2){
                        $result['number2'] = count($idslevel2);//二级分销商总人数
                        $result['agentcount'] += $result['number2'];
                    }
                }
            }
            if(3 <= $list['distribution_pattern']){
                if($result['number2']>0){
                    $idslevel3 = $distributor->Query(['isdistributor'=>2,'referee_id'=>['in',implode(',',$idslevel2)]],'uid');
                    if($idslevel3){
                        $result['number3'] = count($idslevel3);//三级分销商总人数
                        $result['agentcount'] += $result['number3'];
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 获取我的客户列表
     */
    public function getCustomerList($uid,$page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $distributor = new VslMemberModel();
        $where['nm.website_id'] = $this->website_id;
        $distributor_view = new VslMemberViewModel();
        if($uid){
            $id1 = $distributor->Query(['referee_id'=>$uid,'website_id'=>$this->website_id,'isdistributor' => ['neq', 2]],'uid');
            $where['nm.uid'] = ['in',implode(',',$id1)];
        }
        $result = $distributor_view->getCustomerViewList($page_index, $page_size, $where, $order);
        foreach ($result['data'] as $k=>$v){
            $v1 = objToArr($v);
            $order = new VslOrderModel();
            $result['data'][$k]['order_count'] =  $order->getCount(['website_id'=>$this->website_id,'order_status'=>4,'buyer_id'=>$v1['uid']]);
        }
        return $result;
    }
    /**
     * 获取分销商等级列表
     */
    public function getDistributorLevelList($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $distributor_level = new DistributorLevelModel();
        $list = $distributor_level->pageQuery($page_index, $page_size, $where, $order, '*');
        $goods = new VslGoodsModel();
        foreach ($list['data'] as $k=>$v) {
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
     * 获取当前分销商等级
     */
    public function getDistributorLevel()
    {
        $distributor_level = new DistributorLevelModel();
        $list = $distributor_level->pageQuery(1,0,['website_id' => $this->website_id],'','id,level_name');
        return $list['data'];
    }
    /**
     * 获取当前分销商是否是代理商
     */
    public function getAgentInfo()
    {
        $agent_level = new VslAgentLevelModel();
        $list=[];
        $list1= $agent_level->pageQuery(1,0,['website_id' => $this->website_id,'from_type'=>1],'','id,level_name');
        $list2= $agent_level->pageQuery(1,0,['website_id' => $this->website_id,'from_type'=>2],'','id,level_name');
        $list3= $agent_level->pageQuery(1,0,['website_id' => $this->website_id,'from_type'=>3],'','id,level_name');
        $list['global'] = $list1['data'];
        $list['area'] = $list2['data'];
        $list['team'] = $list3['data'];
        return $list;
    }
    /**
     * 添加分销商等级
     */
    public function addDistributorLevel($level_name,$recommend_type, $commission1, $commission2, $commission3,$commission_point1,$commission_point2,$commission_point3,$commission11, $commission22, $commission33,$commission_point11,$commission_point22,$commission_point33,$recommend1,$recommend2,$recommend3,$recommend_point1,$recommend_point2,$recommend_point3,$upgradetype,$offline_number,$order_money,$order_number,$selforder_money,$selforder_number,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$number1,$number2,$number3,$number4,$number5,  $buyagain,$buyagain_recommendtype,$buyagain_commission1,$buyagain_commission2,$buyagain_commission3,$buyagain_commission_point1,$buyagain_commission_point2,$buyagain_commission_point3,$buyagain_commission11,$buyagain_commission22,$buyagain_commission33,$buyagain_commission_point11,$buyagain_commission_point22,$buyagain_commission_point33)
    {
        $distributor_level = new DistributorLevelModel();
        $where['website_id'] = $this->website_id;
        $where['level_name'] = $level_name;
        $count = $distributor_level->where($where)->count();
        if ($count > 0) {
            return -2;
        }
        $data = array(
            'buyagain' => $buyagain,
            'buyagain_recommendtype' => $buyagain_recommendtype,
            'buyagain_commission1' => $buyagain_commission1,
            'buyagain_commission2' => $buyagain_commission2,
            'buyagain_commission3' => $buyagain_commission3,
            'buyagain_commission_point1' => $buyagain_commission_point1,
            'buyagain_commission_point2' => $buyagain_commission_point2,
            'buyagain_commission_point3' => $buyagain_commission_point3,
            'buyagain_commission11' => $buyagain_commission11,
            'buyagain_commission22' => $buyagain_commission22,
            'buyagain_commission33' => $buyagain_commission33,
            'buyagain_commission_point11' => $buyagain_commission_point11,
            'buyagain_commission_point22' => $buyagain_commission_point22,
            'buyagain_commission_point33' => $buyagain_commission_point33,

            'website_id' => $this->website_id,
            'level_name' => $level_name,
            'recommend_type' => $recommend_type,
            'commission1' => $commission1,
            'commission2' => $commission2,
            'commission3' => $commission3,
            'commission_point1' => $commission_point1,
            'commission_point2' => $commission_point2,
            'commission_point3' => $commission_point3,
            'commission11' => $commission11,
            'commission22' => $commission22,
            'commission33' => $commission33,
            'commission_point11' => $commission_point11,
            'commission_point22' => $commission_point22,
            'commission_point33' => $commission_point33,
            'recommend1' => $recommend1,
            'recommend2' => $recommend2,
            'recommend3' => $recommend3,
            'recommend_point1' => $recommend_point1,
            'recommend_point2' => $recommend_point2,
            'recommend_point3' => $recommend_point3,
            'upgradetype' => $upgradetype,
            'offline_number' => $offline_number,
            'order_money' => $order_money,
            'order_number' => $order_number,
            'selforder_money' => $selforder_money,
            'selforder_number' => $selforder_number,
            'downgradetype' => $downgradetype,
            'team_number' => $team_number,
            'team_money' => $team_money,
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
            'number1' => $number1,
            'number2' => $number2,
            'number3' => $number3,
            'number4' => $number4,
            'number5' => $number5,
            'level_number' => $level_number,
            'upgrade_level' => $upgrade_level,
            'create_time' => time()
        );
        $res = $distributor_level->save($data);
        return $res;
    }

    /**
     * 修改分销商等级
     */
    public function updateDistributorLevel($id, $level_name,$recommend_type, $commission1, $commission2, $commission3,$commission_point1,$commission_point2,$commission_point3,$commission11, $commission22, $commission33,$commission_point11,$commission_point22,$commission_point33,$recommend1,$recommend2,$recommend3,$recommend_point1,$recommend_point2,$recommend_point3,$upgradetype,$offline_number,$order_money,$order_number,$selforder_money,$selforder_number,$downgradetype,$team_number,$team_money,$self_money,$weight,$downgradeconditions,$upgradeconditions,$goods_id,$downgrade_condition,$upgrade_condition,$team_number_day,$team_money_day,$self_money_day,$upgrade_level,$level_number,$number1,$number2,$number3,$number4,$number5,  $buyagain,$buyagain_recommendtype,$buyagain_commission1,$buyagain_commission2,$buyagain_commission3,$buyagain_commission_point1,$buyagain_commission_point2,$buyagain_commission_point3,$buyagain_commission11,$buyagain_commission22,$buyagain_commission33,$buyagain_commission_point11,$buyagain_commission_point22,$buyagain_commission_point33)
    {
        try {
            $distributor_level = new DistributorLevelModel();
            $distributor_level->startTrans();
            $data = array(
                'buyagain' => $buyagain,
                'buyagain_recommendtype' => $buyagain_recommendtype,
                'buyagain_commission1' => $buyagain_commission1,
                'buyagain_commission2' => $buyagain_commission2,
                'buyagain_commission3' => $buyagain_commission3,
                'buyagain_commission_point1' => $buyagain_commission_point1,
                'buyagain_commission_point2' => $buyagain_commission_point2,
                'buyagain_commission_point3' => $buyagain_commission_point3,
                'buyagain_commission11' => $buyagain_commission11,
                'buyagain_commission22' => $buyagain_commission22,
                'buyagain_commission33' => $buyagain_commission33,
                'buyagain_commission_point11' => $buyagain_commission_point11,
                'buyagain_commission_point22' => $buyagain_commission_point22,
                'buyagain_commission_point33' => $buyagain_commission_point33,
                
                'level_name' => $level_name,
                'recommend_type' => $recommend_type,
                'commission1' => $commission1,
                'commission2' => $commission2,
                'commission3' => $commission3,
                'commission_point1' => $commission_point1,
                'commission_point2' => $commission_point2,
                'commission_point3' => $commission_point3,
                'commission11' => $commission11,
                'commission22' => $commission22,
                'commission33' => $commission33,
                'commission_point11' => $commission_point11,
                'commission_point22' => $commission_point22,
                'commission_point33' => $commission_point33,
                'recommend1' => $recommend1,
                'recommend2' => $recommend2,
                'recommend3' => $recommend3,
                'recommend_point1' => $recommend_point1,
                'recommend_point2' => $recommend_point2,
                'recommend_point3' => $recommend_point3,
                'upgradetype' => $upgradetype,
                'offline_number' => $offline_number,
                'order_money' => $order_money,
                'order_number' => $order_number,
                'selforder_money' => $selforder_money,
                'selforder_number' => $selforder_number,
                'downgradetype' => $downgradetype,
                'team_number' => $team_number,
                'team_money' => $team_money,
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
                'number1' => $number1,
                'number2' => $number2,
                'number3' => $number3,
                'number4' => $number4,
                'number5' => $number5,
                'level_number' => $level_number,
                'upgrade_level' => $upgrade_level,
                'modify_time' => time()
            );
            $retval= $distributor_level->save($data, [
                'id' => $id,
                'website_id' => $this->website_id
            ]);
            $distributor_level->commit();
            return $retval;
        } catch (\Exception $e) {
            $distributor_level->rollback();
            $retval = $e->getMessage();
            return 0;
        }
    }
    /*
     * 删除分销商等级
     */
    public function deleteDistributorLevel($id)
    {
        $level = new DistributorLevelModel();
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
     * 获得分销商等级详情
     */
    public function getDistributorLevelInfo($id)
    {
        $level_type = new DistributorLevelModel();
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
     * 获得分销商等级比重
     */
    public function getLevelWeight()
    {
        $level_type = new DistributorLevelModel();
        $level_weight = $level_type->Query(['website_id'=>$this->website_id],'weight');
        return $level_weight;
    }
    /**
     * 更新分销商资料
     */
    public function updateDistributorInfo($data, $uid)
    {
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid'=>$uid]);
        if($data['distributor_level_id']){
            $level_type = new DistributorLevelModel();
            $level_weight = $level_type->getInfo(['id'=>$member_info['distributor_level_id']],'weight')['weight'];
            $level_weights = $level_type->getInfo(['id'=>$data['distributor_level_id']],'weight')['weight'];
            if($level_weights>$level_weight){
                $data['up_level_time'] = time();
            }
        }
        $areaStatus = getAddons('areabonus',$this->website_id);
        $globalStatus = getAddons('globalbonus',$this->website_id);
        $teamStatus = getAddons('teambonus',$this->website_id);
        if($areaStatus || $globalStatus || $teamStatus){
            $agent_level = new VslAgentLevelModel();
            if($data['global_agent_level_id'] && $data['is_global_agent'] && $globalStatus){
                $level_global_weight = $agent_level->getInfo(['id'=>$member_info['global_agent_level_id']],'weight')['weight'];
                $level_global_weights = $agent_level->getInfo(['id'=>$data['global_agent_level_id']],'weight')['weight'];
                if($level_global_weight){
                    if($level_global_weights>$level_global_weight){
                        $data['up_global_level_time'] = time();
                    }
                }
                if($member_info['is_global_agent']!=2){
                    $data['apply_global_agent_time'] = time();
                    $data['become_global_agent_time'] = time();
                    $account = new VslBonusAccountModel();
                    $account_info = $account->getInfo(['website_id'=>$this->website_id,'from_type'=>1,'uid' => $uid]);
                    if(empty($account_info)){
                        $account->save(['website_id'=>$this->website_id,'from_type'=>1,'uid' => $uid]);
                    }
                }
            }
            if($data['area_agent_level_id'] && $data['is_area_agent'] && $areaStatus){
                $level_area_weight = $agent_level->getInfo(['id'=>$member_info['area_agent_level_id']],'weight')['weight'];
                $level_area_weights = $agent_level->getInfo(['id'=>$data['area_agent_level_id']],'weight')['weight'];
                if($data['agent_area_id'] && $member_info['area_type']){
                    if($member_info['is_area_agent']!=2){
                        $data['apply_area_agent_time'] = time();
                        $data['become_area_agent_time'] = time();
                        $account = new VslBonusAccountModel();
                        $account_info = $account->getInfo(['website_id'=>$this->website_id,'from_type'=>2,'uid' => $uid]);
                        if(empty($account_info)){
                            $account->save(['website_id'=>$this->website_id,'from_type'=>2,'uid' => $uid]);
                        }
                    }
                    $member_infos['area_type'] = explode(',',$member_info['area_type']);
                    if($member_infos['area_type'][0]==3){
                        $index = strpos($member_info['agent_area_id'],"d");
                        $data['agent_area_id'] = substr_replace($member_info['agent_area_id'],$data['agent_area_id'],0,$index+1);
                    }
                    if($member_infos['area_type'][0]==2){
                        $index = strpos($member_info['agent_area_id'],"c");
                        $data['agent_area_id'] = substr_replace($member_info['agent_area_id'],$data['agent_area_id'],0,$index+1);
                    }
                    if($member_infos['area_type'][0]==1){
                        $index = strpos($member_info['agent_area_id'],"a");
                        $data['agent_area_id'] = substr_replace($member_info['agent_area_id'],$data['agent_area_id'],0,$index+1);
                    }
                }
                if($data['area_type'] && $member_info['area_type']){
                    $member_info['area_type'] = explode(',',$member_info['area_type']);
                    $member_info['area_type'][0] = $data['area_type'];
                    $data['area_type'] = implode(',',$member_info['area_type']);
                }
                if(!$member_info['area_leg']){
                    $data['area_leg'] = 0;
                }
                if($level_area_weight){
                    if($level_area_weights>$level_area_weight){
                        $data['up_area_level_time'] = time();
                    }
                }
            }
            if($data['team_agent_level_id'] && $data['is_team_agent'] && $teamStatus){
                $level_team_weight = $agent_level->getInfo(['id'=>$member_info['team_agent_level_id']],'weight')['weight'];
                $level_team_weights = $agent_level->getInfo(['id'=>$data['team_agent_level_id']],'weight')['weight'];
                if($level_team_weight){
                    if($level_team_weights>$level_team_weight){
                        $data['up_team_level_time'] = time();
                    }
                }
                if($member_info['is_team_agent']!=2){
                    $data['apply_team_agent_time'] = time();
                    $data['become_team_agent_time'] = time();
                    $account = new VslBonusAccountModel();
                    $account_info = $account->getInfo(['website_id'=>$this->website_id,'from_type'=>3,'uid' => $uid]);
                    if(empty($account_info)){
                        $account->save(['website_id'=>$this->website_id,'from_type'=>3,'uid' => $uid]);
                    }
                }
            }
        }
        $retval = $member->save($data, [
            'uid' => $uid,
            'website_id' =>$this->website_id
        ]);
        if($data['distributor_level_id']){
            if($member_info['referee_id']){
                if($member_info['distributor_level_id']!=$data['distributor_level_id']) {
                    $this->updateDistributorLevelInfo($member_info['referee_id']);
                    if (getAddons('globalbonus', $this->website_id)) {
                        $global = new GlobalBonus();
                        $global->updateAgentLevelInfo($member_info['referee_id']);
                    }
                    if (getAddons('areabonus', $this->website_id)) {
                        $area = new AreaBonus();
                        $area->updateAgentLevelInfo($member_info['referee_id']);
                    }
                    if (getAddons('teambonus', $this->website_id)) {
                        $team = new TeamBonus();
                        $team->updateAgentLevelInfo($member_info['referee_id']);
                    }
                }
            }
        }
        return $retval;
    }
    /**
     * 获得近七天的分销订单佣金
     */
    public function getPayMoneySum($condition)
    {
        $order = new VslOrderModel();
        $orderids = $order->Query($condition,'order_id');
        $orderids = implode(',',$orderids);
        $order_commission = new VslOrderDistributorCommissionModel();
        $orders = $order_commission->Query(['order_id'=>['in',$orderids]],'commission');
        $count = array_sum($orders);
        return $count;
    }
    /**
     * 获得近七天的分销订单金额
     */
    public function getOrderMoneySum($condition)
    {
        $order_commission = new VslOrderDistributorCommissionModel();
        $orderids = array_unique($order_commission->Query(['website_id'=>$condition['website_id']],'distinct order_id'));
        $orderids = implode(',',$orderids);
        $order = new VslOrderModel();
        $condition['order_id'] = ['in',$orderids];
        $orders = $order->Query($condition,'order_money');
        $count = array_sum($orders);
        return $count;
    }
    /**
     * 获得分销统计
     */
    public function getDistributorCount($website_id)
    {
        $start_date = strtotime(date("Y-m-d"),time());
        $end_date = strtotime(date('Y-m-d',strtotime('+1 day')));
        $member = new VslMemberModel();
        $data['distributor_total'] = $member->getCount(['website_id'=>$website_id,'isdistributor'=>2]);
        $data['distributor_today'] = $member->getCount(['website_id'=>$website_id,'isdistributor'=>2,'become_distributor_time'=>[[">",$start_date],["<",$end_date]]]);
        $commission = new VslDistributorAccountModel();
        $commission_total = $commission->Query(['website_id'=>$website_id],'commission');
        $data['commission_total'] = array_sum($commission_total);
        $withdrawals_total = $commission->Query(['website_id'=>$website_id],'withdrawals');
        $data['withdrawals_total'] = array_sum($withdrawals_total);
        return $data;
    }

    /*
      * 获取分销订单
      */
    public function getOrderList($page_index = 1, $page_size = 0, $condition = '', $order = '')
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
        $order_commission = new VslOrderDistributorCommissionModel();
        if($uid){
            $ids = '';
//            $selforder_ids = $order_commission->Query(['website_id'=>$condition['website_id'],'buyer_id'=>$uid],'order_id');//自购订单
//            if($selforder_ids){
//                $ids = implode(',',$selforder_ids);
//            }
            $ids1 = $order_commission->Query(['website_id'=>$condition['website_id'],'commissionA_id'=>$uid],'distinct order_id');//一级佣金订单
            if($ids1 && !empty($ids)){
                $ids = $ids.','.implode(',',$ids1);
            }elseif($ids1){
                $ids = implode(',',$ids1);
            }
            $ids2 = $order_commission->Query(['website_id'=>$condition['website_id'],'commissionB_id'=>$uid],'distinct order_id');//二级佣金订单
            if($ids2 && !empty($ids)){
                $ids = $ids.','.implode(',',$ids2);
            }elseif($ids2){
                $ids = implode(',',$ids2);
            }
            $ids3 = $order_commission->Query(['website_id'=>$condition['website_id'],'commissionC_id'=>$uid],'distinct order_id');//三级佣金订单
            if($ids3 && !empty($ids)){
                $ids = $ids.','.implode(',',$ids3);
            }elseif($ids3){
                $ids = implode(',',$ids3);
            }
            $condition['order_id'] = ['in',$ids];
        }
        if($condition['order_id']){
            $order_list = $order_model->pageQuery($page_index, $page_size, $condition, $order, '*');
            $order_bonus = (getAddons('globalbonus', $this->website_id) || getAddons('areabonus', $this->website_id) || getAddons('teambonus', $this->website_id)) ? new VslOrderBonusModel() : '';
            $group_server = $this->groupshopping ? new GroupShopping() : '';
            $order_memo_model = new VslOrderMemoModel();
            $user = new UserModel();
            if (!empty($order_list['data'])) {
                foreach ($order_list['data'] as $k => $v) {
                    $user_info = $user->getInfo(['uid'=>$order_list['data'][$k]['buyer_id']],'nick_name,user_name,user_tel,user_headimg');
                    if($user_info['user_name']){
                        $order_list['data'][$k]['buyer_name'] = $user_info['user_name'];
                    }elseif ($user_info['nick_name']){
                        $order_list['data'][$k]['buyer_name'] = $user_info['nick_name'];
                    }elseif ($user_info['user_tel']){
                        $order_list['data'][$k]['buyer_name'] = $user_info['user_tel'];
                    }
                    $order_list['data'][$k]['buyer_headimg'] = $user_info['user_headimg'];
                    $order_list['data'][$k]['buyer_nick_name'] = $user_info['nick_name'];
                    $order_list['data'][$k]['buyer_user_tel'] = $user_info['user_tel'];
                    $order_list['data'][$k]['buyer_user_name'] = $user_info['user_name'];
                    $order_list['data'][$k]['order_point'] = $v['point'];
                    //查询订单是否满足满减送的条件
                    $order_list['data'][$k]['promotion_status'] = ($order_list['data'][$k]['promotion_money'] + $order_list['data'][$k]['coupon_money'] > 0) ? 1 : 0;
                    //预售的应该是定金加上尾款
                    $order_list['data'][$k]['first_money'] = $v['order_money'];
                    if($v['presell_id'] && $v['money_type'] == 2){
                        $order_list['data'][$k]['order_money'] = $v['order_money'] + $v['final_money'];
                    }
                    $order_list['data'][$k]['global_bonus'] = 0;
                    $order_list['data'][$k]['area_bonus'] = 0;
                    $order_list['data'][$k]['team_bonus'] = 0;
                    if($this->groupshopping){
                        $isGroupSuccess = $group_server->groupRecordDetail($v['group_record_id'])['status'];
                    }
                    //查询订单分红
                    if (getAddons('globalbonus', $this->website_id)){
                        $orders = $order_bonus->Query(['order_id' => $v['order_id'], 'from_type' => 1], 'bonus');
                        $order_list['data'][$k]['global_bonus'] = array_sum($orders);
                    }
                    if (getAddons('areabonus', $this->website_id)){
                        $orders = $order_bonus->Query(['order_id' => $v['order_id'], 'from_type' => 2], 'bonus');
                        $order_list['data'][$k]['area_bonus'] = array_sum($orders);
                    }
                    if (getAddons('teambonus', $this->website_id)){
                        $orders = $order_bonus->Query(['order_id' => $v['order_id'], 'from_type' => 3], 'bonus');
                        $order_list['data'][$k]['team_bonus'] = array_sum($orders);
                    }

                    $order_list['data'][$k]['bonus'] = $order_list['data'][$k]['global_bonus'] + $order_list['data'][$k]['area_bonus'] + $order_list['data'][$k]['team_bonus'];
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
                            if ($value['commissionA_id'] ==  $uid) {
                                $order_list['data'][$k]['commission'] += $value['commissionA'];
                                $order_list['data'][$k]['point'] += $value['pointA'];
                            }
                            if ($value['commissionB_id'] ==  $uid) {
                                $order_list['data'][$k]['commission'] += $value['commissionB'];
                                $order_list['data'][$k]['point'] += $value['pointB'];
                            }
                            if ($value['commissionC_id'] ==  $uid) {
                                $order_list['data'][$k]['commission'] += $value['commissionC'];
                                $order_list['data'][$k]['point'] += $value['pointC'];
                            }
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
                        //订单商品的佣金
                        $commission_goods_info = $order_commission->getInfo(['website_id'=>$condition['website_id'],'order_goods_id'=>$v_item['order_goods_id'],'order_id'=>$v['order_id']],'*');//自购订单
                        if ($commission_goods_info['commissionA_id'] == $uid) {
                            $order_item_list[$key_item]['commission'] = $commission_goods_info['commissionA'];
                            $order_item_list[$key_item]['point'] = $commission_goods_info['pointA'];
                        }
                        if ($commission_goods_info['commissionB_id'] == $uid) {
                            $order_item_list[$key_item]['commission'] = $commission_goods_info['commissionB'];
                            $order_item_list[$key_item]['point'] = $commission_goods_info['pointB'];
                        }
                        if ($commission_goods_info['commissionC_id'] == $uid) {
                            $order_item_list[$key_item]['commission'] = $commission_goods_info['commissionC'];
                            $order_item_list[$key_item]['point'] = $commission_goods_info['pointC'];
                        }
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

                        //分红信息
                        $order_item_list[$key_item]['bonus'] = 0;
                        $order_item_list[$key_item]['bonusA'] = 0;
                        $order_item_list[$key_item]['bonusB'] = 0;
                        $order_item_list[$key_item]['bonusC'] = 0;
                        if (getAddons('globalbonus', $this->website_id) || getAddons('teambonus', $this->website_id) || getAddons('areabonus', $this->website_id)){
                            $order_bonus = new VslOrderBonusModel();
                            $order_bonus_info = $order_bonus->where(['order_id' => $v['order_id'],'order_goods_id' => $v_item['order_goods_id']])->find();
                            if($order_bonus_info['from_type']==1){
                                $order_item_list[$key_item]['bonusA'] = $order_bonus_info['bonus'];
                                $order_item_list[$key_item]['bonus'] += $order_bonus_info['bonus'];
                            }
                            if($order_bonus_info['from_type']==2){
                                $order_item_list[$key_item]['bonusB'] = $order_bonus_info['bonus'];
                                $order_item_list[$key_item]['bonus'] += $order_bonus_info['bonus'];
                            }
                            if($order_bonus_info['from_type']==3){
                                $order_item_list[$key_item]['bonusC'] = $order_bonus_info['bonus'];
                                $order_item_list[$key_item]['bonus'] += $order_bonus_info['bonus'];
                            }
                        }

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
            $order_list['data']=[];
        }
        return $order_list;
//
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
//            if($query_order_ids){
//                $condition['order_id'] = ['in', implode(',', $query_order_ids)];
//            }else{
//                $condition['order_id'] = '';
//            }
//        }
//        $order_commission = new VslOrderDistributorCommissionModel();
//        if($uid){
//            $ids = '';
////            $selforder_ids = $order_commission->Query(['website_id'=>$condition['website_id'],'buyer_id'=>$uid],'order_id');//自购订单
////            if($selforder_ids){
////                $ids = implode(',',$selforder_ids);
////            }
//            $ids1 = $order_commission->Query(['website_id'=>$condition['website_id'],'commissionA_id'=>$uid],'order_id');//一级佣金订单
//            if($ids1 && !empty($ids)){
//                $ids = $ids.','.implode(',',$ids1);
//            }elseif($ids1){
//                $ids = implode(',',$ids1);
//            }
//            $ids2 = $order_commission->Query(['website_id'=>$condition['website_id'],'commissionB_id'=>$uid],'order_id');//二级佣金订单
//            if($ids2 && !empty($ids)){
//                $ids = $ids.','.implode(',',$ids2);
//            }elseif($ids2){
//                $ids = implode(',',$ids2);
//            }
//            $ids3 = $order_commission->Query(['website_id'=>$condition['website_id'],'commissionC_id'=>$uid],'order_id');//三级佣金订单
//            if($ids3 && !empty($ids)){
//                $ids = $ids.','.implode(',',$ids3);
//            }elseif($ids3){
//                $ids = implode(',',$ids3);
//            }
//            $condition['order_id'] = ['in',$ids];
//        }
//        if($condition['order_id']){
//            // 查询主表
//            $order_list = $order_model->pageQuery($page_index, $page_size, $condition, $order, '*');
//            $user = new UserModel();
//            $order_commission = new VslOrderDistributorCommissionModel();
//            $order_item = new VslOrderGoodsModel();
//            $province = new ProvinceModel();
//            $city = new CityModel();
//            $district = new DistrictModel();
//            $goods_sku = new VslGoodsSkuModel();
//            $picture = new AlbumPictureModel();
//            if (!empty($order_list['data'])) {
//                foreach ($order_list['data'] as $k => $v) {
//                    //查询订单佣金
//                    $user_info = $user->getInfo(['uid'=>$order_list['data'][$k]['buyer_id']],'nick_name,user_name,user_tel,user_headimg');
//                    if($user_info['user_name']){
//                        $order_list['data'][$k]['buyer_name'] = $user_info['user_name'];
//                    }elseif ($user_info['nick_name']){
//                        $order_list['data'][$k]['buyer_name'] = $user_info['nick_name'];
//                    }elseif ($user_info['user_tel']){
//                        $order_list['data'][$k]['buyer_name'] = $user_info['user_tel'];
//                    }
//                    $order_list['data'][$k]['buyer_headimg'] = $user_info['user_headimg'];
//                    $order_list['data'][$k]['buyer_nick_name'] = $user_info['nick_name'];
//                    $order_list['data'][$k]['buyer_user_tel'] = $user_info['user_tel'];
//                    $order_list['data'][$k]['buyer_user_name'] = $user_info['user_name'];
//                    $order_list['data'][$k]['commission'] = 0;
//                    $order_list['data'][$k]['commissionA'] = 0;
//                    $order_list['data'][$k]['commissionB'] = 0;
//                    $order_list['data'][$k]['commissionC'] = 0;
//                    $order_list['data'][$k]['point'] = 0;
//                    $order_list['data'][$k]['pointA'] = 0;
//                    $order_list['data'][$k]['pointB'] = 0;
//                    $order_list['data'][$k]['pointC'] = 0;
//                    $orders = $order_commission->Query(['order_id' => $v['order_id']], '*');
//                    foreach ($orders as $key1 => $value) {
//                        if ($value['commissionA_id'] ==  $uid) {
//                            $order_list['data'][$k]['commission'] += $value['commissionA'];
//                            $order_list['data'][$k]['point'] += $value['pointA'];
//                        }
//                        if ($value['commissionB_id'] ==  $uid) {
//                            $order_list['data'][$k]['commission'] += $value['commissionB'];
//                            $order_list['data'][$k]['point'] += $value['pointB'];
//                        }
//                        if ($value['commissionC_id'] ==  $uid) {
//                            $order_list['data'][$k]['commission'] += $value['commissionC'];
//                            $order_list['data'][$k]['point'] += $value['pointC'];
//                        }
//                        if($value['commissionA_id']){
//                            $order_list['data'][$k]['commissionA_id'] = $value['commissionA_id'];
//                            $member_A = $user->getInfo(['uid' => $value['commissionA_id']], 'user_name,nick_name');
//                            if($member_A['user_name']){
//                                $order_list['data'][$k]['commissionA_name'] = $member_A['user_name'];
//                            }else{
//                                $order_list['data'][$k]['commissionA_name'] =  $member_A['nick_name'];
//                            }
//                            $order_list['data'][$k]['commissionA'] += $value['commissionA'];
//                            $order_list['data'][$k]['pointA'] += $value['pointA'];
//                        }
//                        if($value['commissionB_id']){
//                            $order_list['data'][$k]['commissionB_id'] = $value['commissionB_id'];
//                            $member_B = $user->getInfo(['uid' => $value['commissionB_id']], 'user_name,nick_name');
//                            if($member_B['user_name']){
//                                $order_list['data'][$k]['commissionB_name'] = $member_B['user_name'];
//                            }else{
//                                $order_list['data'][$k]['commissionB_name'] =  $member_B['nick_name'];
//                            }
//                            $order_list['data'][$k]['commissionB'] += $value['commissionB'];
//                            $order_list['data'][$k]['pointB'] += $value['pointB'];
//                        }
//                        if($value['commissionC_id']){
//                            $order_list['data'][$k]['commissionC_id'] = $value['commissionC_id'];
//                            $member_C = $user->getInfo(['uid' => $value['commissionC_id']], 'user_name,nick_name');
//                            if($member_C['user_name']){
//                                $order_list['data'][$k]['commissionC_name'] = $member_C['user_name'];
//                            }else{
//                                $order_list['data'][$k]['commissionC_name'] =  $member_C['nick_name'];
//                            }
//                            $order_list['data'][$k]['commissionC'] += $value['commissionC'];
//                            $order_list['data'][$k]['pointC'] += $value['pointC'];
//                        }
//                    }
//                    // 查询订单项表
//
//                    $order_item_list = $order_item->where([
//                        'order_id' => $v['order_id']
//                    ])->select();
//
//                    $province_name = "";
//                    $city_name = "";
//                    $district_name = "";
//
//
//                    $province_info = $province->getInfo(array(
//                        "province_id" => $v["receiver_province"]
//                    ), "*");
//                    if (count($province_info) > 0) {
//                        $province_name = $province_info["province_name"];
//                    }
//                    $order_list['data'][$k]['receiver_province_name'] = $province_name;
//
//                    $city_info = $city->getInfo(array(
//                        "city_id" => $v["receiver_city"]
//                    ), "*");
//                    if (count($city_info) > 0) {
//                        $city_name = $city_info["city_name"];
//                    }
//                    $order_list['data'][$k]['receiver_city_name'] = $city_name;
//                    $district_info = $district->getInfo(array(
//                        "district_id" => $v["receiver_district"]
//                    ), "*");
//                    if (count($district_info) > 0) {
//                        $district_name = $district_info["district_name"];
//                    }
//                    $order_list['data'][$k]['receiver_district_name'] = $district_name;
//                    foreach ($order_item_list as $key_item => $v_item) {
//                        //订单商品的佣金
//                        $commission_goods_info = $order_commission->getInfo(['website_id'=>$condition['website_id'],'order_goods_id'=>$v_item['order_goods_id'],'order_id'=>$v['order_id']],'*');//自购订单
//                        if ($commission_goods_info['commissionA_id'] == $uid) {
//                            $order_item_list[$key_item]['commission'] = $commission_goods_info['commissionA'];
//                        }
//                        if ($commission_goods_info['commissionB_id'] == $uid) {
//                            $order_item_list[$key_item]['commission'] = $commission_goods_info['commissionB'];
//                        }
//                        if ($commission_goods_info['commissionC_id'] == $uid) {
//                            $order_item_list[$key_item]['commission'] = $commission_goods_info['commissionC'];
//                        }
//                        // 查询商品sku表开始
//                        $goods_sku_info = $goods_sku->getInfo([
//                            'sku_id' => $v_item['sku_id']
//                        ], 'code,attr_value_items');
//                        $order_item_list[$key_item]['code'] = $goods_sku_info['code'];
//                        $order_item_list[$key_item]['spec'] = [];
//                        if ($v_item['sku_attr']) {
//                            $order_item_list[$key_item]['spec'] = json_decode(html_entity_decode($v_item['sku_attr']), true);
//                        }
//                        // 查询商品sku结束
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
//                    // 查询订单操作
//                    foreach ($order_status as $k_status => $v_status) {
//                        if ($v_status['status_id'] == $v['order_status']) {
//
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
//        }else{
//            $order_list['data']=[];
//            return $order_list;
//        }

    }
    /*
     * 获取分销订单详情
     */
    public function getOrderDetail($order_id,$distributor_id)
    {
        // 查询主表信息
        $order_service = new OrderService();
        $detail = $order_service->getOrderDetail($order_id);
        if($detail){
            if ($detail['commissionA_id'] && $detail['commissionA_id']== $distributor_id) {
                $detail['commission'] = $detail['commissionA'];
                $detail['point'] = $detail['pointA'];
                $detail['commission_name'] = $detail['commissionA_name'];
                $detail['commission_user_headimg'] = $detail['commissionA_user_headimg'];
            }
            if ($detail['commissionB_id'] && $detail['commissionB_id'] ==  $distributor_id) {
                $detail['commission'] = $detail['commissionB'];
                $detail['point'] = $detail['pointB'];
                $detail['commission_name'] = $detail['commissionB_name'];
                $detail['commission_user_headimg'] = $detail['commissionB_user_headimg'];
            }
            if ($detail['commissionC_id'] && $detail['commissionC_id'] ==  $distributor_id) {
                $detail['commission'] = $detail['commissionC'];
                $detail['point'] = $detail['pointC'];
                $detail['commission_name'] = $detail['commissionC_name'];
                $detail['commission_user_headimg'] = $detail['commissionC_user_headimg'];
            }
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
        // TODO Auto-generated method stub
    }
    /**
     * 分销商详情(降级条件)
     */
    public function getDistributorInfos($uid,$time)
    {
        $distributor = new VslMemberModel();
        $order_model = new VslOrderModel();
        $result = $distributor->getInfo(['uid' => $uid],"*");
        $website_id =  $result['website_id'];
        $list = $this->getDistributionSite($website_id);
        if($uid && $time){
            $order_commission = new VslOrderDistributorCommissionModel();
            $commission_order_id = implode(',',$order_commission->Query(['website_id'=>$result['website_id']],'distinct order_id'));
            $result['agentordercount'] = 0;
            $result['order_money'] = 0;
            $result['selforder_money'] = 0;
            $result['selforder_number'] = 0;
            $up_time = $distributor->getInfo(['uid'=>$uid],'up_level_time')['up_level_time'];
            $limit_time = $up_time+$time*24*3600;
            $order_ids = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>$uid,'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_id');
            $order_money = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>$uid,'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_money');
            $result['selforder_money'] = array_sum($order_money);//自购订单金额
            $result['selforder_number'] = count($order_ids);//自购订单
            if(1 <= $list['distribution_pattern']){
                $idslevel1 = $distributor->Query(['referee_id'=>$uid],'uid');
                if($idslevel1){
                    $order_ids1 = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>['in',implode(',',$idslevel1)],'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_id');
                    $order1_money1 = $order_model->Query(['order_status'=>[['>',0],['<',5]],'buyer_id'=>['in',implode(',',$idslevel1)],'create_time'=>[[">", $up_time], ["<", $limit_time]],'order_id'=>['in',$commission_order_id]],'order_money');
                    $result['order1'] = count($order_ids1);//一级订单总数
                    $result['order1_money'] = array_sum($order1_money1);//一级订单总金额
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
                        $result['order2'] = count($order_ids2);//二级订单总数
                        $result['order2_money'] = array_sum($order2_money1);//二级订单总金额
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
                        $result['order2'] = count($order_ids3);//三级订单总数
                        $result['order3_money'] = array_sum($order3_money1);//三级订单总金额
                        $result['agentordercount'] += $result['order3'];
                        $result['order_money'] += $result['order3_money'];
                    }
                }
            }
            if($list['purchase_type']==1){
                $result['agentordercount'] += count($order_ids);
                $result['order_money'] += array_sum($order_money);
            }
        }
        return $result;
    }
    /**
     * 分销商详情(升级条件)
     */
    public function getDistributorLowerInfo($uid)
    {
        $distributor = new VslMemberModel();
        $order_model = new VslOrderModel();
        $result = $distributor->getInfo(['uid' => $uid],"*");
        $list = $this->getDistributionSite($result['website_id']);
        $order_commission = new VslOrderDistributorCommissionModel();
        $commission_order_id = implode(',',$order_commission->Query(['website_id'=>$result['website_id']],'distinct order_id'));
        $result['agentordercount'] = 0;
        $result['agentcount'] = 0;
        $result['agentcount1'] = 0;
        $result['agentcount2'] = 0;
        $result['order_money'] = 0;
        $result['number1'] = 0;
        $result['number_1'] = 0;
        $result['number2'] = 0;
        $result['number_2'] = 0;
        $result['number3'] = 0;
        $result['number_3'] = 0;
        
        if($result['down_up_level_time']){ //发生过降级 条件限制条件变更为大于降级时间 'become_distributor_time'=>[">=",$result['down_up_level_time']]
            $order_ids = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$result['down_up_level_time']]],'order_id');
            $order_money = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$result['down_up_level_time']]],'order_money');
            $result['selforder_money'] = array_sum($order_money);//自购订单金额
            $result['selforder_number'] = count($order_ids);//自购订单数
            
            if(1 <= $list['distribution_pattern']){
                $idslevels1 = $distributor->Query(['referee_id'=>$uid,'reg_time'=>[">",$result['down_up_level_time']]],'uid');//是一级下级
                $idslevel_1 = $distributor->Query(['referee_id'=>$uid,'isdistributor'=>['neq',2],'reg_time'=>[">",$result['down_up_level_time']]],'uid');//不是一级分销商的下级
                $idslevel1 = $distributor->Query(['referee_id'=>$uid,'isdistributor'=>2,'reg_time'=>[">",$result['down_up_level_time']]],'uid');//是一级分销商的下级
                
                //edit by 2019/12/03
                $oldidslevels1 = $distributor->Query(['referee_id'=>$uid],'uid');//是一级下级
                $oldidslevel1 = $distributor->Query(['referee_id'=>$uid,'isdistributor'=>2],'uid');//是一级分销商的下级

                if($oldidslevels1){
                    $order_ids1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$oldidslevels1)],'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$result['down_up_level_time']]],'order_id');
                    $order1_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$oldidslevels1)],'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$result['down_up_level_time']]],'order_money');
                    $result['number1'] = count($idslevel1);//一级分销商人数
                    $result['number_1'] = count($idslevel_1);//一级非分销商人数
                    $result['order1'] = count($order_ids1);//一级分销订单数
                    $result['order1_money'] =array_sum($order1_money1);//一级分销商订单总金额
                    $result['agentcount'] += $result['number1']+$result['number_1'];//下线总人数
                    $result['agentcount1'] += $result['number_1'];//下线客户数（非分销商）
                    $result['agentcount2'] += $result['number1'];//团队人数（分销商）
                    $result['agentordercount'] += $result['order'];//分销订单数
                    $result['order_money'] +=  $result['order1_money'];//分销订单金额
                }
            }
            if(2 <= $list['distribution_pattern']){
                if($result['number1']>0){
                    $idslevels2 = $distributor->Query(['referee_id'=>['in',implode(',',$oldidslevel1)],'reg_time'=>[">",$result['down_up_level_time']]],'uid');
                    $idslevel2 = $distributor->Query(['referee_id'=>['in',implode(',',$oldidslevel1)],'isdistributor'=>2,'reg_time'=>[">",$result['down_up_level_time']]],'uid');
                    $idslevel_2 = $distributor->Query(['referee_id'=>['in',implode(',',$oldidslevel1)],'isdistributor'=>['neq',2],'reg_time'=>[">",$result['down_up_level_time']]],'uid');
                    //edit by 2019/12/03
                    $oldidslevels2 = $distributor->Query(['referee_id'=>['in',implode(',',$oldidslevel1)]],'uid');
                    $oldidslevel2 = $distributor->Query(['referee_id'=>['in',implode(',',$oldidslevel1)],'isdistributor'=>2],'uid');
                    if($oldidslevels2){
                        $order_ids2 = $order_model->Query(['buyer_id'=>['in',implode(',',$oldidslevels2)],'order_status'=>4,'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$result['down_up_level_time']]],'order_id');
                        $order2_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$oldidslevels2)],'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$result['down_up_level_time']]],'order_money');
                        $result['number2'] = count($idslevel2);//二级分销商人数
                        $result['number_2'] = count($idslevel_2);//二级非分销商人数
                        $result['order2'] = count($order_ids2);//二级分销商订单总数
                        $result['order2_money'] = array_sum($order2_money1);//二级分销商订单总金额
                        $result['agentcount'] += $result['number2']+$result['number_2'];//下线总人数
                        $result['agentcount1'] += $result['number_2'];//下线客户数（非分销商）
                        $result['agentcount2'] += $result['number2'];//团队人数（分销商）
                        $result['agentordercount'] += $result['order2'];//分销订单数
                        $result['order_money'] +=  $result['order2_money'];//分销订单金额
                    }
                }
            }
            if(3 <= $list['distribution_pattern']){
                if($result['number2']>0){
                    $idslevels3 = $distributor->Query(['referee_id'=>['in',implode(',',$oldidslevel2)],'reg_time'=>[">",$result['down_up_level_time']]],'uid');
                    $idslevel3 = $distributor->Query(['referee_id'=>['in',implode(',',$oldidslevel2)],'isdistributor'=>2,'reg_time'=>[">",$result['down_up_level_time']]],'uid');
                    $idslevel_3 = $distributor->Query(['referee_id'=>['in',implode(',',$oldidslevel2)],'isdistributor'=>['neq',2],'reg_time'=>[">",$result['down_up_level_time']]],'uid');
                    //edit by 2019/12/03
                    $oldidslevels3 = $distributor->Query(['referee_id'=>['in',implode(',',$oldidslevel2)]],'uid');
                    if($oldidslevels3){
                        $order_ids3 = $order_model->Query(['buyer_id'=>['in',implode(',',$oldidslevels3)],'order_status'=>4,'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$result['down_up_level_time']]],'order_id');
                        $order3_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$oldidslevels3)],'order_id'=>['in',$commission_order_id],'finish_time'=>[">",$result['down_up_level_time']]],'order_money');
                        $result['number3'] = count($idslevel3);//三级分销商人数
                        $result['number_3'] = count($idslevel_3);//三级非分销商人数
                        $result['order3'] = count($order_ids3);//三级分销商订单总数
                        $result['order3_money'] = array_sum($order3_money1);//三级分销商订单总金额
                        $result['agentcount'] += $result['number3']+$result['number_3'];//下线总人数
                        $result['agentcount1'] += $result['number_3'];//下线客户数（非分销商）
                        $result['agentcount2'] += $result['number3'];//团队人数（分销商）
                        $result['agentordercount'] += $result['order3'];//分销订单数
                        $result['order_money'] +=  $result['order3_money'];//分销订单金额
                    }
                }
            }
        }else{
            $order_ids = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id]],'order_id');
            $order_money = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id]],'order_money');
            $result['selforder_money'] = array_sum($order_money);//自购订单金额
            $result['selforder_number'] = count($order_ids);//自购订单数
            if(1 <= $list['distribution_pattern']){
                $idslevels1 = $distributor->Query(['referee_id'=>$uid],'uid');//是一级下级
                $idslevel_1 = $distributor->Query(['referee_id'=>$uid,'isdistributor'=>['neq',2]],'uid');//不是一级分销商的下级
                $idslevel1 = $distributor->Query(['referee_id'=>$uid,'isdistributor'=>2],'uid');//是一级分销商的下级
                if($idslevels1){
                    $order_ids1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevels1)],'order_id'=>['in',$commission_order_id]],'order_id');
                    $order1_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevels1)],'order_id'=>['in',$commission_order_id]],'order_money');
                    $result['number1'] = count($idslevel1);//一级分销商人数
                    $result['number_1'] = count($idslevel_1);//一级非分销商人数
                    $result['order1'] = count($order_ids1);//一级分销订单数
                    $result['order1_money'] =array_sum($order1_money1);//一级分销商订单总金额
                    $result['agentcount'] += $result['number1']+$result['number_1'];//下线总人数
                    $result['agentcount1'] += $result['number_1'];//下线客户数（非分销商）
                    $result['agentcount2'] += $result['number1'];//团队人数（分销商）
                    $result['agentordercount'] += $result['order'];//分销订单数
                    $result['order_money'] +=  $result['order1_money'];//分销订单金额
                }
            }
            if(2 <= $list['distribution_pattern']){
                if($result['number1']>0){
                    $idslevels2 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel1)]],'uid');
                    $idslevel2 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel1)],'isdistributor'=>2],'uid');
                    $idslevel_2 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel1)],'isdistributor'=>['neq',2]],'uid');
                    if($idslevels2){
                        $order_ids2 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevels2)],'order_status'=>4,'order_id'=>['in',$commission_order_id]],'order_id');
                        $order2_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevels2)],'order_id'=>['in',$commission_order_id]],'order_money');
                        $result['number2'] = count($idslevel2);//二级分销商人数
                        $result['number_2'] = count($idslevel_2);//二级非分销商人数
                        $result['order2'] = count($order_ids2);//二级分销商订单总数
                        $result['order2_money'] = array_sum($order2_money1);//二级分销商订单总金额
                        $result['agentcount'] += $result['number2']+$result['number_2'];//下线总人数
                        $result['agentcount1'] += $result['number_2'];//下线客户数（非分销商）
                        $result['agentcount2'] += $result['number2'];//团队人数（分销商）
                        $result['agentordercount'] += $result['order2'];//分销订单数
                        $result['order_money'] +=  $result['order2_money'];//分销订单金额
                    }
                }
            }
            if(3 <= $list['distribution_pattern']){
                if($result['number2']>0){
                    $idslevels3 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel2)]],'uid');
                    $idslevel3 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel2)],'isdistributor'=>2],'uid');
                    $idslevel_3 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel2)],'isdistributor'=>['neq',2]],'uid');
                    if($idslevels3){
                        $order_ids3 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevels3)],'order_status'=>4,'order_id'=>['in',$commission_order_id]],'order_id');
                        $order3_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevels3)],'order_id'=>['in',$commission_order_id]],'order_money');
                        $result['number3'] = count($idslevel3);//三级分销商人数
                        $result['number_3'] = count($idslevel_3);//三级非分销商人数
                        $result['order3'] = count($order_ids3);//三级分销商订单总数
                        $result['order3_money'] = array_sum($order3_money1);//三级分销商订单总金额
                        $result['agentcount'] += $result['number3']+$result['number_3'];//下线总人数
                        $result['agentcount1'] += $result['number_3'];//下线客户数（非分销商）
                        $result['agentcount2'] += $result['number3'];//团队人数（分销商）
                        $result['agentordercount'] += $result['order3'];//分销订单数
                        $result['order_money'] +=  $result['order3_money'];//分销订单金额
                    }
                }
            }
        }
        
        return $result;
    }
    /**
     * 分销商详情(订单已完成)
     */
    public function getDistributorInfo($uid)
    {
        $distributor = new VslMemberModel();
        $user = new UserModel();
        if($this->website_id){
            $website_id = $this->website_id;
        }else{
            $website_id =  $distributor->getInfo(['uid'=>$uid],'website_id')['website_id'];
        }
        $order_model = new VslOrderModel();
        $list = $this->getDistributionSite($website_id);
        $result = $distributor->getInfo(['uid' => $uid],"*");
        $result['distribution_pattern'] = $list['distribution_pattern'];
        $commission = new VslDistributorAccountModel();
        $commission_info = $commission->getInfo(['uid'=>$uid],'*');
        $result['commission'] = $commission_info['commission'];
        $result['withdrawals'] = $commission_info['withdrawals'];
        $result['freezing_commission'] = $commission_info['freezing_commission'];
        $result['total_commission'] = $commission_info['freezing_commission']+$commission_info['commission']+$commission_info['withdrawals'];
        $order_commission = new VslOrderDistributorCommissionModel();
        $commission_order_id = implode(',',$order_commission->Query(['website_id'=>$website_id],'distinct order_id'));
        if($uid){
            $result['agentordercount'] = 0;
            $result['order1'] = 0;
            $result['order2'] = 0;
            $result['order3'] = 0;
            $result['order1_money'] = 0;
            $result['order2_money'] = 0;
            $result['order3_money'] = 0;
            $result['order_money'] = 0;
            $result['agentcount'] = 0;
            $result['customcount'] = $distributor->getCount(['referee_id'=>$uid,'website_id'=>$this->website_id,'isdistributor' => ['neq', 2]]);
            if($commission_order_id){
                $order_ids = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id]],'order_id');
                $order_money = $order_model->Query(['order_status'=>4,'buyer_id'=>$uid,'order_id'=>['in',$commission_order_id]],'order_money');
                $result['selforder_money'] = array_sum($order_money);//自购分销订单金额
                $result['selforder_number'] = count($order_ids);//自购分销订单数
            }
            $user_info = $user->getInfo(['uid'=>$uid],'user_headimg,user_name,nick_name');
            $result['user_headimg'] = $user_info['user_headimg'];//获取分销商头像
            $info = $distributor->getInfo(['uid'=>$uid],'*');//获取分销商信息
            if($user_info['user_name']){
                $result['member_name'] = $user_info['user_name'];//获取会员名称
            }else{
                $result['member_name'] = $user_info['nick_name'];//获取会员名称
            }
            $result['mobile'] = $info['mobile'];//获取分销商手机号
            $distributor_level_id = $info['distributor_level_id'];
            $level = new DistributorLevelModel();
            $result['level_name'] = $level->getInfo(['id'=>$distributor_level_id],'level_name')['level_name'];//等级名称
            if(1 <= $list['distribution_pattern']){
                $idslevel1 = $distributor->Query(['referee_id'=>$uid],'uid');
                $idslevel11 = $distributor->Query(['referee_id'=>$uid,'isdistributor'=>2],'uid');
                if($idslevel1){
                    $order_ids1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel1)],'order_id'=>['in',$commission_order_id]],'order_id');
                    $order1_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel1)],'order_id'=>['in',$commission_order_id]],'order_money');
                    $result['order1'] = count($order_ids1);//一级分销订单总数
                    $result['number1'] = count($idslevel1);//一级总人数
                    $result['number11'] = count($idslevel11);//一级总人数(分销商)
                    $result['order1_money'] = array_sum($order1_money1);//一级分销订单总金额
                    $result['agentcount'] += $result['number11'];
                    $result['agentordercount'] += $result['order1'];
                    $result['order_money'] += $result['order1_money'];
                }
            }
            if(2 <= $list['distribution_pattern']){
                if($result['number1']>0){
                    $idslevel2 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel1)]],'uid');
                    $idslevel22 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel1)],'isdistributor'=>2],'uid');
                    if($idslevel2){
                        $order_ids2 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevel2)],'order_status'=>4,'order_id'=>['in',$commission_order_id]],'order_id');
                        $order2_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel2)],'order_id'=>['in',$commission_order_id]],'order_money');
                        $result['order2'] = count($order_ids2);//二级分销商订单总数
                        $result['number2'] = count($idslevel2);//二级总人数
                        $result['number22'] = count($idslevel22);//二级总人数(分销商)
                        $result['order2_money'] = array_sum($order2_money1);//二级分销商订单总金额
                        $result['agentcount'] += $result['number22'];
                        $result['agentordercount'] += $result['order2'];
                        $result['order_money'] += $result['order2_money'];
                    }
                }
            }
            if(3 <= $list['distribution_pattern']){
                if($result['number2']>0){
                    $idslevel3 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel2)]],'uid');
                    $idslevel33 = $distributor->Query(['referee_id'=>['in',implode(',',$idslevel2)],'isdistributor'=>2],'uid');
                    if($idslevel3){
                        $order_ids3 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevel3)],'order_status'=>4,'order_id'=>['in',$commission_order_id]],'order_id');
                        $order3_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel2)],'order_id'=>['in',$commission_order_id]],'order_money');
                        $result['order2'] = count($order_ids3);//三级分销商订单总数
                        $result['number3'] = count($idslevel3);//三级总人数
                        $result['number33'] = count($idslevel33);//三级总人数(分销商)
                        $result['order3_money'] = array_sum($order3_money1);//三级分销商订单总金额
                        $result['agentcount'] += $result['number33'];//下级分总人数
                        $result['agentordercount'] += $result['order3'];//下级分销订单数
                        $result['order_money'] += $result['order3_money'];//下级分销金额
                    }
                }
            }
        }
        if($list['purchase_type']==1){
            $result['agentordercount'] += $result['selforder_number'];
            $result['order_money'] += $result['selforder_money'];
        }
        $result['extensionordercount'] = $result['agentordercount'];
        $result['extensionmoney'] = $result['order_money'];
        if($result['apply_distributor_time']){
            $result['apply_distributor_time'] = date('Y-m-d H:i:s',$result['apply_distributor_time']);
        }else{
            $result['apply_distributor_time'] = date('Y-m-d H:i:s',$result['become_distributor_time']);
        }
        
        $result['become_distributor_time'] = date('Y-m-d H:i:s',$result['become_distributor_time']);
        if(!empty($result['referee_id'])){
            $user_info = $user->getInfo(['uid'=>$result['referee_id']],'user_name,nick_name');
            if($user_info['user_name']){
                $result['referee_name'] = $user_info['user_name'];//获取会员名称
            }else{
                $result['referee_name'] = $user_info['nick_name'];//获取会员名称
            }
        }else{
            $result['referee_name'] = '总店';
        }
        $result['is_datum'] = $this->checkDatum();
        return $result;
    }

    /**
     * 申请成为分销商
     */
    public function addDistributorInfo($website_id,$uid,$post_data,$real_name)
    {

        $info = $this->getDistributionSite($website_id);
        $level = new DistributorLevelModel();
        $level_info = $level->getInfo(['website_id'=>$website_id,'is_default'=>1],'*');
        $level_id = $level_info['id'];
        $distribution_pattern = $info['distribution_pattern'];
        $ratio = '';
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid'=>$uid],'*');
        if($distribution_pattern>=1){
            $ratio .= '一级返佣比例'.$level_info['commission1'].'%';
        }
        if($distribution_pattern>=2){
            $ratio .= ',二级返佣比例'.$level_info['commission2'].'%';
        }
        if($distribution_pattern>=3){
            $ratio .= ',三级返佣比例'.$level_info['commission3'].'%';
        }
        $extend_code = $this->create_extend();
            $user_info = new UserModel();
            if(empty($real_name)){
                $real_name = $user_info->getInfo(['uid'=>$uid],'real_name')['real_name'];
            }
                if($info['distributor_check']==1 || $info['distributor_condition']==3){
                    $data = array(
                        "isdistributor" => 2,
                        "real_name"=>$real_name,
                        "distributor_level_id" => $level_id,
                        "apply_distributor_time" => time(),
                        "become_distributor_time" => time(),
                        "extend_code"=> $extend_code,
                        "distributor_apply"=>$post_data
                    );
                }else{
                    $data = array(
                        "isdistributor" => 1,
                        "real_name"=>$real_name,
                        "distributor_level_id" => $level_id,
                        "apply_distributor_time" => time(),
                        "distributor_apply"=>$post_data
                    );
                }
                $member = new VslMemberModel();
                $result = $member->save($data, [
                    'uid' => $uid
                ]);
                if($real_name && $result==1){
                    $user = new UserModel();
                    $user->save(['real_name'=>$real_name], ['uid' => $uid]);
                }
                if($info['distributor_check']==1 || $info['distributor_condition']==3){
                    $referee_id = $member->getInfo(['uid' => $uid],'referee_id')['referee_id'];
                    if($referee_id){
                        $this->updateDistributorLevelInfo($referee_id);
                        if(getAddons('globalbonus', $this->website_id)){
                            $global = new GlobalBonus();
                            $global->updateAgentLevelInfo($referee_id);
                            $global->becomeAgent($referee_id);
                        }
                        if(getAddons('areabonus', $this->website_id)){
                            $area = new AreaBonus();
                            $area->updateAgentLevelInfo($referee_id);
                        }
                        if(getAddons('teambonus', $this->website_id)){
                            $team = new TeamBonus();
                            $team->updateAgentLevelInfo($referee_id);
                            $team->becomeAgent($referee_id);
                        }
                    }
                    if($info['distribution_pattern']>=1){
                        if($member_info['referee_id']){
                            $recommend1_info = $member->getInfo(['uid'=>$member_info['referee_id']],'*');
                            if($recommend1_info && $recommend1_info['isdistributor']==2){
                                $level_info1 = $level->getInfo(['id' => $recommend1_info['distributor_level_id'],'website_id' => $member_info['website_id']]);
                                $recommend1 = $level_info1['recommend1'];//一级推荐奖
                                $recommend_point1 = $level_info1['recommend_point1'];//一级推荐积分
                                $this->addRecommed($uid,$recommend1_info['uid'],$recommend1,$recommend_point1,$member_info['website_id']);
                            }
                        }
                    }
                    if($info['distribution_pattern']>=2){
                        $recommend2_info = $member->getInfo(['uid'=>$recommend1_info['referee_id']],'*');
                        if($recommend2_info && $recommend2_info['isdistributor']==2) {
                            $level_info2 = $level->getInfo(['id' => $recommend2_info['distributor_level_id'],'website_id' => $member_info['website_id']]);
                            $recommend2 = $level_info2['recommend2'];//二级推荐奖
                            $recommend_point2 = $level_info2['recommend_point2'];//二级推荐积分
                            $this->addRecommed($uid,$recommend2_info['uid'],$recommend2,$recommend_point2,$member_info['website_id']);
                        }
                    }
                    if($info['distribution_pattern']>=3){
                        $recommend3_info = $member->getInfo(['uid'=>$recommend2_info['referee_id']],'*');
                        if($recommend3_info && $recommend3_info['isdistributor']==2) {
                            $level_info3 = $level->getInfo(['id' => $recommend3_info['distributor_level_id'],'website_id' => $member_info['website_id']]);
                            $recommend3 = $level_info3['recommend3'];//三级推荐奖
                            $recommend_point3 = $level_info3['recommend_point3'];//三级推荐积分
                            $this->addRecommed($uid,$recommend3_info['uid'],$recommend3,$recommend_point3,$member_info['website_id']);
                        }
                    }
                    $account = new VslDistributorAccountModel();
                    $account->save(['website_id'=>$website_id,'uid'=>$uid]);
                    runhook("Notify", "successfulDistributorByTemplate", ["uid" => $uid,"website_id" => $this->website_id]);//用户成为分销商提醒
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"become_distributor","ratio"=>$ratio,"uid" => $uid,"become_time" => time(),'level_name'=>$level_info['level_name']]);//用户成为分销商提醒
                }else{
                    runhook("Notify", "sendCustomMessage", ['messageType'=>'apply_distributor',"uid" => $uid,"apply_time" => time()]);//用户申请成为分销商提醒
                }
                return $result;

    }
    /**
     * 提现填写资料
     */
    public function addDistributorInfos($uid,$post_data,$real_name)
    {
        $user_info = new UserModel();
        if(empty($real_name)){
            $real_name = $user_info->getInfo(['uid'=>$uid],'real_name')['real_name'];
        }
        $data = array(
            "complete_datum" => 1,
            "real_name"=>$real_name,
            "distributor_apply"=>$post_data
        );
        $member = new VslMemberModel();
        $result = $member->save($data, [
            'uid' => $uid
        ]);
        if($real_name && $result==1){
            $user = new UserModel();
            $user->save(['real_name'=>$real_name], ['uid' => $uid]);
        }
        return $result;

    }
    public function create_extend()
    {
        $randcode = '';
        for ($i = 0; $i<10; $i++)
        {
            $randcode .= chr(mt_rand(48, 57));
        }
        return $randcode;
    }

    /**
     * 查询分销商状态
     */
    public function getDistributorStatus($uid)
    {
        $user = new VslMemberModel();
        $result = $user->getInfo(['uid' => $uid],"isdistributor");
        return $result;
    }

    /**
     * 分销设置
     */
    public function setDistributionSite($distribution_status,$distribution_pattern, $purchase_type,$distributor_condition, $distributor_conditions, $pay_money, $order_number, $distributor_check, $distributor_grade, $goods_id,$lower_condition,$distributor_datum,$distribution_admin_status)
    {
        // $account = new VslDistributorAccountModel();
        // $user_account = $account->getInfo(['website_id'=>$this->website_id,'commission'=>['>',0]],'commission');
        // if($user_account>0 && $distribution_status==0){
        //     return -3;
        // }
        $ConfigService = new AddonsConfigService();
        $value = array(
            'website_id' => $this->website_id,
            'distribution_admin_status' => $distribution_admin_status,
            'distribution_pattern' => $distribution_pattern,
            'purchase_type' => $purchase_type,
            'distributor_datum' => $distributor_datum,
            'distributor_condition' => $distributor_condition,
            'distributor_conditions' => $distributor_conditions,
            'pay_money' => $pay_money,
            'order_number' => $order_number,
            'distributor_check' => $distributor_check,
            'distributor_grade' => $distributor_grade,
            'goods_id' => $goods_id,
            'lower_condition' => $lower_condition
        );
        $distribution_info = $ConfigService->getAddonsConfig("distribution",$this->website_id);
        if (! empty($distribution_info)) {
            $data = array(
                "value" => json_encode($value),
                "is_use"=>$distribution_status,
                'modify_time' => time()
            );
            $res = $this->addons_config_module->save($data, [
                "website_id" => $this->website_id,
                "addons"=>"distribution"
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig($value, "分销设置", $distribution_status, "distribution");
        }
        return $res;
    }
    /*
     * 获取分销基本设置
     *
     */
    public function getDistributionSite($website_id){
        if($website_id){
           $websiteid =  $website_id;
        }else{
            $websiteid =  $this->website_id;
        }
            $config = new AddonsConfigService();
            $distribution = $config->getAddonsConfig("distribution",$websiteid);
            $distribution_info = json_decode($distribution['value'],true);
            $goods = new VslGoodsModel();
            $goods_info = $goods->getInfo(['goods_id'=>$distribution_info['goods_id']],'picture,goods_name');
            $pic_id = $goods_info['picture'];
            $pic = new AlbumPictureModel();
            $distribution_info['pic'] = $pic->getInfo(['pic_id'=>$pic_id],'pic_cover_mid')['pic_cover_mid'];
            $distribution_info['goods_name'] = $goods_info['goods_name'];
            $distribution_info['is_use'] = $distribution['is_use'];
            return $distribution_info;
    }
    public function checkDatum(){
        $is_datum = 0;
        $config = new AddonsConfigService();
        $distributor = new VslMemberModel();
        $distribution = $config->getAddonsConfig("distribution",$this->website_id);
        $distribution_info = json_decode($distribution['value'],true);
        $result = $distributor->getInfo(['uid' => $this->uid],"*");
        if($distribution_info['distributor_condition']==1 || $distribution_info['distributor_condition']==2 || $distribution_info['distributor_condition']==3){
            if($distribution_info['distributor_datum']==1 && $result['complete_datum']==1){
                $is_datum = 1;
            }else if($distribution_info['distributor_datum']==1 && $result['complete_datum']!=1){
                $is_datum = 2;
            }
        }
        return $is_datum;
    }
    /**
     * 分销结算设置
     */
    public function setSettlementSite($withdrawals_type,$make_money, $commission_calculation, $commission_arrival,$withdrawals_check, $withdrawals_min , $withdrawals_cash, $withdrawals_begin, $withdrawals_end, $poundage)
    {
        $ConfigService = new ConfigService();
        $value = array(
            'website_id' => $this->website_id,
            'withdrawals_type' => $withdrawals_type,
            'commission_calculation' => $commission_calculation,
            'commission_arrival' => $commission_arrival,
            'withdrawals_check' => $withdrawals_check,
            'make_money' => $make_money,
            'withdrawals_min' => $withdrawals_min,
            'withdrawals_cash' => $withdrawals_cash,
            'withdrawals_begin' => $withdrawals_begin,
            'withdrawals_end' => $withdrawals_end,
            'poundage' => $poundage,
        );
        $distribution_info = $ConfigService->getConfig(0,"SETTLEMENT",$this->website_id);
        if (! empty($distribution_info)) {
            $data = array(
                "value" => json_encode($value),
            );
            $res = $this->config_module->save($data, [
                "instance_id" => 0,
                "website_id" => $this->website_id,
                "key" => "SETTLEMENT"
            ]);
        } else {
            $res = $ConfigService->addConfig(0, "SETTLEMENT", $value, "分销结算设置", 1);
        }
        // TODO Auto-generated method stub
        return $res;
    }
    /*
      * 获取分销结算设置
      *
      */
    public function getSettlementSite($website_id){
        if($website_id){
            $website_ids = $website_id;
        }else{
            $website_ids = $this->website_id;
        }
        $config = new ConfigService();
        $distribution = $config->getConfig(0,"SETTLEMENT",$website_ids);
        $distributionInfo = json_decode($distribution['value'], true);
        return $distributionInfo;
    }
    /**
     * 分销申请协议设置
     */
    public function setAgreementSite($type,$logo,$content,$distribution_label,$distribution_name,$distributor_name,$distribution_commission,$commission,$commission_details,$withdrawable_commission,$withdrawals_commission,$withdrawal,$frozen_commission,$distribution_order,$my_team,$team1,$team2,$team3,$my_customer,$extension_code,$distribution_tips,$become_distributor,$total_commission)
    {
        $ConfigService = new ConfigService();
        $agreement_info = $ConfigService ->getConfig(0,"AGREEMENT",$this->website_id);
        $agreement_infos = json_decode($agreement_info['value'],true);
        if($agreement_infos && $type==1){//文案
            $value = array(
                'website_id' => $this->website_id,
                'logo' => $logo,
                'content' =>  $agreement_infos['content'],
                'distribution_label' => $distribution_label,
                'distribution_name' => $distribution_name,
                'distributor_name' => $distributor_name,
                'distribution_commission' => $distribution_commission,
                'total_commission' => $total_commission,
                'commission' => $commission,
                'commission_details' => $commission_details,
                'withdrawable_commission' => $withdrawable_commission,
                'withdrawals_commission' => $withdrawals_commission,
                'withdrawal' => $withdrawal,
                'frozen_commission' => $frozen_commission,
                'distribution_order' => $distribution_order,
                'my_team' => $my_team,
                'team1' => $team1,
                'team2' => $team2,
                'team3' => $team3,
                'my_customer' => $my_customer,
                'extension_code' => $extension_code,
                'distribution_tips' => $distribution_tips,
                'become_distributor' => $become_distributor,
            );
        }else if($agreement_infos && $type==2){
            $value = array(
                'website_id' => $this->website_id,
                'logo' => $agreement_infos['logo'],
                'content' => $content,
                'distribution_label' => $agreement_infos['distribution_label'],
                'distribution_name' => $agreement_infos['distribution_name'],
                'distributor_name' => $agreement_infos['distributor_name'],
                'distribution_commission' => $agreement_infos['distribution_commission'],
                'commission' => $agreement_infos['commission'],
                'total_commission' => $agreement_infos['total_commission'],
                'commission_details' => $agreement_infos['commission_details'],
                'withdrawable_commission' => $agreement_infos['withdrawable_commission'],
                'withdrawals_commission' => $agreement_infos['withdrawals_commission'],
                'withdrawal' => $agreement_infos['withdrawal'],
                'frozen_commission' => $agreement_infos['frozen_commission'],
                'distribution_order' => $agreement_infos['distribution_order'],
                'my_team' => $agreement_infos['my_team'],
                'team1' => $agreement_infos['team1'],
                'team2' => $agreement_infos['team2'],
                'team3' => $agreement_infos['team3'],
                'my_customer' => $agreement_infos['my_customer'],
                'extension_code' => $agreement_infos['extension_code'],
                'distribution_tips' => $agreement_infos['distribution_tips'],
                'become_distributor' => $agreement_infos['become_distributor'],
            );
        }else{
            $value = array(
                'website_id' => $this->website_id,
                'logo' => $logo,
                'content' =>  $content,
                'distribution_label' => $distribution_label,
                'distribution_name' => $distribution_name,
                'distributor_name' => $distributor_name,
                'distribution_commission' => $distribution_commission,
                'commission' => $commission,
                'total_commission' => $total_commission,
                'commission_details' => $commission_details,
                'withdrawable_commission' => $withdrawable_commission,
                'withdrawals_commission' => $withdrawals_commission,
                'withdrawal' => $withdrawal,
                'frozen_commission' => $frozen_commission,
                'distribution_order' => $distribution_order,
                'my_team' => $my_team,
                'team1' => $team1,
                'team2' => $team2,
                'team3' => $team3,
                'my_customer' => $my_customer,
                'extension_code' => $extension_code,
                'distribution_tips' => $distribution_tips,
                'become_distributor' => $become_distributor,
            );
        }
        if (! empty($agreement_info)) {
            $data = array(
                "value" => json_encode($value)
            );
            $res = $this->config_module->save($data, [
                "instance_id" => 0,
                "website_id" => $this->website_id,
                "key" => "AGREEMENT"
            ]);
        } else {
            $res = $ConfigService->addConfig(0, "AGREEMENT", $value, "申请协议", 1);
        }
        return $res;
    }
    /*
      * 获取分销申请协议
      */
    public function getAgreementSite($website_id){
        if($website_id){
            $website_ids = $website_id;
        }else{
            $website_ids = $this->website_id;
        }
        $ConfigService = new ConfigService();
        $distribution_info = $ConfigService ->getConfig(0,"AGREEMENT",$website_ids);
        if($distribution_info){
            $distribution_info = json_decode($distribution_info['value'],true);
            if($distribution_info && !isset($distribution_info['become_distributor'])){
                $distribution_info['become_distributor'] =  '成为分销商';
                $distribution_info['distribution_tips'] =  '分销小提示：可以通过二维码、邀请链接或者邀请码发展下线客户，下线客户购买商品后你可以获得相应的佣金奖励';
                $distribution_info['extension_code'] =  '推广码';
                $distribution_info['team3'] =  '三级';
                $distribution_info['team2'] =  '二级';
                $distribution_info['team1'] =  '一级';
                $distribution_info['my_team'] =  '我的团队';
                $distribution_info['my_customer'] =  '我的客户';
                $distribution_info['distribution_order'] =  '分销订单';
                $distribution_info['frozen_commission'] =  '冻结佣金';
                $distribution_info['withdrawals_commission'] =  '已提现佣金';
                $distribution_info['withdrawal'] =  '提现中';
                $distribution_info['withdrawable_commission'] =  '可提现佣金';
                $distribution_info['commission_details'] =  '佣金明细';
                $distribution_info['commission'] =  '佣金';
                $distribution_info['total_commission'] =  '累积佣金';
                $distribution_info['distribution_commission'] =  '分销佣金';
                $distribution_info['distributor_name'] =  '分销商';
                $distribution_info['distribution_name'] =  '分销中心';
            }
        }else{
            $distribution_info['become_distributor'] =  '成为分销商';
            $distribution_info['distribution_tips'] =  '分销小提示：可以通过二维码、邀请链接或者邀请码发展下线客户，下线客户购买商品后你可以获得相应的佣金奖励';
            $distribution_info['extension_code'] =  '推广码';
            $distribution_info['team3'] =  '三级';
            $distribution_info['team2'] =  '二级';
            $distribution_info['team1'] =  '一级';
            $distribution_info['my_team'] =  '我的团队';
            $distribution_info['my_customer'] =  '我的客户';
            $distribution_info['distribution_order'] =  '分销订单';
            $distribution_info['frozen_commission'] =  '冻结佣金';
            $distribution_info['withdrawals_commission'] =  '已提现佣金';
            $distribution_info['withdrawal'] =  '提现中';
            $distribution_info['withdrawable_commission'] =  '可提现佣金';
            $distribution_info['commission_details'] =  '佣金明细';
            $distribution_info['commission'] =  '佣金';
            $distribution_info['total_commission'] =  '累积佣金';
            $distribution_info['distribution_commission'] =  '分销佣金';
            $distribution_info['distributor_name'] =  '分销商';
            $distribution_info['distribution_name'] =  '分销中心';
        }
        return $distribution_info;
    }
    /*
      * 获取自定义表单
      */
    public function getCustomForm($website_id){
        if($website_id){
            $website_ids = $website_id;
        }else{
            $website_ids = $this->website_id;
        }
        $add_config = new AddonsConfigService();
        $distribution_info =$add_config->getAddonsConfig("customform",$website_ids);
        $distribution_info = json_decode($distribution_info['value'],true);
        $custom_form = [];
        if($distribution_info['distributor']==1){
            $custom_form_id =  $distribution_info['distributor_id'];
            $coupon_model = new CustomServer();
            $custom_form_info = $coupon_model->getCustomFormDetail($custom_form_id)['value'];
            if($custom_form_info){
                $custom_form = json_decode($custom_form_info,true);
            }
        }
        return $custom_form;
    }
    
    /*
     * 删除分销商
     */
    public function deleteDistributor($uid)
    {
        $member = new VslMemberModel();
        $member->startTrans();
        try {
            // 删除分销商信息
            $data = [
                'isdistributor'=>0
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
     * 订单商品佣金计算
     */
    public function orderDistributorCommission($params)
    {
        
        $ConfigService = new ConfigService();
        $order_goods = new VslOrderGoodsModel();
        $order = new VslOrderModel();
        $order_info = $order->getInfo(['order_id'=>$params['order_id']],'bargain_id,group_id,presell_id');
        $order_goods_info = $order_goods->getInfo(['order_goods_id'=>$params['order_goods_id'],'order_id'=>$params['order_id']]);
        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id'=>$params['goods_id']]);
        $addonsConfigService = new AddonsConfigService();
        $info1 = $addonsConfigService ->getAddonsConfig("distribution",$goods_info['website_id']);//基本设置
        $info2 = $ConfigService ->getConfig(0,"SETTLEMENT",$goods_info['website_id']);
        $seckill = getAddons('seckill',$goods_info['website_id']);
        $seckill_rule =  $addonsConfigService ->getAddonsConfig("seckill",$goods_info['website_id']);
        $seckill_value = json_decode($seckill_rule['value'],true);
        $seckill_distribution_val = json_decode($seckill_value['distribution_val'],true);
        $bargain = getAddons('bargain',$goods_info['website_id']);
        $bargain_rule =  $addonsConfigService ->getAddonsConfig("bargain", $goods_info['website_id']);
        $bargain_value = json_decode($bargain_rule['value'],true);
        $bargain_distribution_val = json_decode($bargain_value['distribution_val'],true);
        $order_bargain_id = $order_info['bargain_id'];
        $groupshopping = getAddons('groupshopping',$goods_info['website_id']);
        $groupshopping_rule =  $addonsConfigService ->getAddonsConfig("groupshopping",$goods_info['website_id']);
        $groupshopping_value = json_decode($groupshopping_rule['value'],true);
        $groupshopping_goods_info = $order_info['group_id'];
        $presell_goods_info = $order_goods_info['presell_id'];
        $presell = getAddons('presell',$goods_info['website_id']);
        $presell_rule =  $addonsConfigService ->getAddonsConfig("presell",$goods_info['website_id']);
        $presell_value = json_decode($presell_rule['value'],true);
        $commission1= '';
        $commission2= '';
        $commission3= '';
        $commission11= '';
        $commission22= '';
        $commission33= '';
        $point1= '';
        $point2= '';
        $point3= '';
        $point11= '';
        $point22= '';
        $point33= '';
        $commissionB2 = '';
        $pointB2= '';
        $commissionB22 = '';
        $pointB22 = '';
        $commissionA11 = '';
        $pointA11 = '';
        $commissionA1 = '';
        $pointA1 = '';
        $commissionC33 = '';
        $pointC33 = '';
        $commissionC3 = '';
        $pointC3 = '';
        $level_rule_ids = [];
        $bargain_goods  = 0;
        $seckill_goods  = 0;
        $groupshopping_goods  = 0;
        $presell_goods  = 0;
        $commission_distribution_admin_status = 0;
        if($bargain==1 && $bargain_value['is_distribution']==1 && $order_bargain_id){//砍价是否参与分销分红、分销分红规则
            $bargain_goods  = 1;
            if($bargain_value['rule_commission']==1){//有独立分销规则
                if($bargain_distribution_val['recommend_type']==1){//佣金比例设置
                    $commission1 = $bargain_distribution_val['first_rebate'];
                    $commission2 = $bargain_distribution_val['second_rebate'];
                    $commission3 = $bargain_distribution_val['third_rebate'];
                    $point1 = $bargain_distribution_val['first_point'];
                    $point2 = $bargain_distribution_val['second_point'];
                    $point3 = $bargain_distribution_val['third_point'];
                }else{//固定佣金
                    $commission11 = $bargain_distribution_val['first_rebate1'];
                    $commission22 = $bargain_distribution_val['second_rebate1'];
                    $commission33 = $bargain_distribution_val['third_rebate1'];
                    $point11 = $bargain_distribution_val['first_point1'];
                    $point22 = $bargain_distribution_val['second_point1'];
                    $point33 = $bargain_distribution_val['third_point1'];
                }
            }
        }
        if($seckill==1 && $seckill_value['is_distribution']==1 && $order_goods_info['seckill_id']){//该商品参与秒杀
            $seckill_goods  = 1;
            if($seckill_value['rule_commission']==1){//有独立分销规则
                if($seckill_distribution_val['recommend_type']==1){//佣金比例设置
                    $commission1 = $seckill_distribution_val['first_rebate'];
                    $commission2 = $seckill_distribution_val['second_rebate'];
                    $commission3 = $seckill_distribution_val['third_rebate'];
                    $point1 = $seckill_distribution_val['first_point'];
                    $point2 = $seckill_distribution_val['second_point'];
                    $point3 = $seckill_distribution_val['third_point'];
                }else{//固定佣金
                    $commission11 = $seckill_distribution_val['first_rebate1'];
                    $commission22 = $seckill_distribution_val['second_rebate1'];
                    $commission33 = $seckill_distribution_val['third_rebate1'];
                    $point11 = $seckill_distribution_val['first_point1'];
                    $point22 = $seckill_distribution_val['second_point1'];
                    $point33 = $seckill_distribution_val['third_point1'];
                }
            }
        }
        if($groupshopping==1 && $groupshopping_value['is_distribution']==1 && $groupshopping_goods_info){//该商品参与拼团
            $groupshopping_goods  = 1;
            if($groupshopping_value['rule_commission']==1){//有独立分销规则
                if($groupshopping_value['recommend_type']==1){//佣金比例设置
                    $commission1 = $groupshopping_value['first_rebate'];
                    $commission2 = $groupshopping_value['second_rebate'];
                    $commission3 = $groupshopping_value['third_rebate'];
                    $point1 = $groupshopping_value['first_point'];
                    $point2 = $groupshopping_value['second_point'];
                    $point3 = $groupshopping_value['third_point'];
                }else{//固定佣金
                    $commission11 = $groupshopping_value['first_rebate1'];
                    $commission22 = $groupshopping_value['second_rebate1'];
                    $commission33 = $groupshopping_value['third_rebate1'];
                    $point11 = $groupshopping_value['first_point1'];
                    $point22 = $groupshopping_value['second_point1'];
                    $point33 = $groupshopping_value['third_point1'];
                }
            }
        }
        if($presell==1 && $presell_value['is_distribution']==1 && $presell_goods_info){//该商品参与预售
            $presell_goods  = 1;
            if($presell_value['rule_commission']==1){//有独立分销规则
                if($presell_value['recommend_type']==1){//佣金比例设置
                    $commission1 = $presell_value['first_rebate'];
                    $commission2 = $presell_value['second_rebate'];
                    $commission3 = $presell_value['third_rebate'];
                    $point1 = $presell_value['first_point'];
                    $point2 = $presell_value['second_point'];
                    $point3 = $presell_value['third_point'];
                }else{//固定佣金
                    $commission11 = $presell_value['first_rebate1'];
                    $commission22 = $presell_value['second_rebate1'];
                    $commission33 = $presell_value['third_rebate1'];
                    $point11 = $presell_value['first_point1'];
                    $point22 = $presell_value['second_point1'];
                    $point33 = $presell_value['third_point1'];
                }
            }
        }
        //如果该商品是店铺独立商品 ，由于默认是不开启 ，之前已开启参与产品如果没有设置独立分销则默认为0
        //获取是否开启店铺佣金
        $configAdmin= new DistributorService();
        $distributionStatusAdmin = $configAdmin->getDistributionSite($goods_info['website_id']);
        $distribution_admin_status = $distributionStatusAdmin['distribution_admin_status'];

        if($distribution_admin_status == 0 && $goods_info['shop_id']){
            $goods_info['distribution_rule'] = 0;
            $goods_info['is_distribution'] = 1;
        }
        //获取当前商品 是否重复购买
        // 查询是否已购买过该商品
        $countOrderGoods = new VslOrderGoodsViewModel();

        $goodscondition['website_id'] = $order_goods_info['website_id'];
        $goodscondition['buyer_id'] = $order_goods_info['buyer_id'];
        $goodscondition['goods_id'] = $order_goods_info['goods_id'];
        $resCount = $countOrderGoods -> getAllGoodsOrders($goodscondition); 
        
        
        $countGoods = 0;
        if($resCount > 1){
            $countGoods = 1;
        }
        if($goods_info['is_distribution']==1 ){//该商品参与分销
            
            
            if($goods_info['distribution_rule']==1){//有独立分销规则
                $goods_info['distribution_rule_val'] = json_decode(htmlspecialchars_decode($goods_info['distribution_rule_val']),true);
                if($goods_info['distribution_rule_val']['level_rule'] && $goods_info['distribution_rule_val']['level_rule']==1){
                    $level_rule_ids = $goods_info['distribution_rule_val']['level_ids'];
                    if($goods_info['distribution_rule_val']['recommend_type']==1){//佣金比例设置
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $level_first_rebate = $goods_info['distribution_rule_val']['first_rebate'];
                        $level_second_rebate = $goods_info['distribution_rule_val']['second_rebate'];
                        $level_third_rebate = $goods_info['distribution_rule_val']['third_rebate'];
                        $level_first_point = $goods_info['distribution_rule_val']['first_point'];
                        $level_second_point = $goods_info['distribution_rule_val']['second_point'];
                        $level_third_point = $goods_info['distribution_rule_val']['third_point'];
                    }else{//固定佣金
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $level_first_rebate1 = $goods_info['distribution_rule_val']['first_rebate1'];
                        $level_second_rebate1 = $goods_info['distribution_rule_val']['second_rebate1'];
                        $level_third_rebate1 = $goods_info['distribution_rule_val']['third_rebate1'];
                        $level_first_point1 = $goods_info['distribution_rule_val']['first_point1'];
                        $level_second_point1 = $goods_info['distribution_rule_val']['second_point1'];
                        $level_third_point1 = $goods_info['distribution_rule_val']['third_point1'];
                    }
                }else{
                    if($goods_info['distribution_rule_val']['recommend_type']==1){//佣金比例设置
                        $commission1 = $goods_info['distribution_rule_val']['first_rebate'];
                        $commission2 = $goods_info['distribution_rule_val']['second_rebate'];
                        $commission3 = $goods_info['distribution_rule_val']['third_rebate'];
                        $point1 = $goods_info['distribution_rule_val']['first_point'];
                        $point2 = $goods_info['distribution_rule_val']['second_point'];
                        $point3 = $goods_info['distribution_rule_val']['third_point'];
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                    }else{//固定佣金
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $commission11 = $goods_info['distribution_rule_val']['first_rebate1'];
                        $commission22 = $goods_info['distribution_rule_val']['second_rebate1'];
                        $commission33 = $goods_info['distribution_rule_val']['third_rebate1'];
                        $point11 = $goods_info['distribution_rule_val']['first_point1'];
                        $point22 = $goods_info['distribution_rule_val']['second_point1'];
                        $point33 = $goods_info['distribution_rule_val']['third_point1'];
                    }
                }
            }
            
            //查询是否开启复购 
            if($goods_info['buyagain'] == 1 && $countGoods == 1){ //移除关联独立分销规则 开启商品独立复购

                $goods_info['buyagain_distribution_val'] = json_decode(htmlspecialchars_decode($goods_info['buyagain_distribution_val']),true);
                $goods_info['buyagain_distribution_rule_val'] = $goods_info['buyagain_distribution_val'];
                
                //重置等级规则
                $goods_info['distribution_rule_val'] = $goods_info['buyagain_distribution_rule_val'];
                $goods_info['distribution_rule_val']['recommend_type'] = $goods_info['buyagain_distribution_rule_val']['buyagain_recommend_type'];

                if($goods_info['buyagain_distribution_val']['buyagain_level_rule'] && $goods_info['buyagain_distribution_val']['buyagain_level_rule']==1){
                    $level_rule_ids = $goods_info['buyagain_distribution_rule_val']['level_ids'];
                    if($goods_info['buyagain_distribution_rule_val']['buyagain_recommend_type']==1){//佣金比例设置
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $level_first_rebate = $goods_info['buyagain_distribution_rule_val']['buyagain_first_rebate'];
                        $level_second_rebate = $goods_info['buyagain_distribution_rule_val']['buyagain_second_rebate'];
                        $level_third_rebate = $goods_info['buyagain_distribution_rule_val']['buyagain_third_rebate'];
                        $level_first_point = $goods_info['buyagain_distribution_rule_val']['buyagain_first_point'];
                        $level_second_point = $goods_info['buyagain_distribution_rule_val']['buyagain_second_point'];
                        $level_third_point = $goods_info['buyagain_distribution_rule_val']['buyagain_third_point'];
                    }else{//固定佣金
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $level_first_rebate1 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_rebate1'];
                        $level_second_rebate1 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_rebate1'];
                        $level_third_rebate1 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_rebate1'];
                        $level_first_point1 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_point1'];
                        $level_second_point1 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_point1'];
                        $level_third_point1 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_point1'];
                    }
                }else{
                    if($goods_info['buyagain_distribution_rule_val']['buyagain_recommend_type']==1){//佣金比例设置
                        
                        $commission1 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_rebate'];
                        $commission2 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_rebate'];
                        $commission3 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_rebate'];
                        $point1 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_point'];
                        $point2 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_point'];
                        $point3 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_point'];
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                    }else{//固定佣金
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $commission11 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_rebate1'];
                        $commission22 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_rebate1'];
                        $commission33 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_rebate1'];
                        $point11 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_point1'];
                        $point22 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_point1'];
                        $point33 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_point1'];
                    }
                }
            }
        }
       
        $cost_price = $order_goods_info['cost_price'];//商品成本价
        $price = $order_goods_info['real_money']/$order_goods_info['num'];//商品实际支付金额
        $promotion_price = $order_goods_info ['price'];//商品销售价
        $original_price = $order_goods_info ['market_price'];//商品原价
        // $profit_price = $promotion_price-$cost_price-$order_goods_info['profile_price']+$order_goods_info['adjust_money'];//商品利润价
        $profit_price = $price-$cost_price;//商品利润价
        if($profit_price<0){
            $profit_price = 0;
        }
        $member = new VslMemberModel();
        $distributor = $member->getInfo(['uid' => $params['buyer_id']]);
        $distributor_level_id = $distributor['distributor_level_id'];
        $base_info = json_decode($info1['value'], true);
        $set_info = json_decode($info2['value'], true);
        $level = new DistributorLevelModel();
        $commissionA_id = 0;
        $commissionA = 0;//一级佣金和对应的id
        $pointA = 0;//一级积分和对应的id
        $commissionB_id = 0;
        $commissionB = 0;//二级佣金和对应的id
        $pointB = 0;//二级积分和对应的id
        $commissionC_id = 0;
        $commissionC = 0;//三级佣金和对应的id
        $pointC = 0;//三级积分和对应的id
        $commission_calculation = $set_info['commission_calculation'];//计算节点（商品价格）
        $real_price = 0;
        if ($commission_calculation == 1) {//实际付款金额
            $real_price = $price;
        }elseif($commission_calculation == 2) {//商品原价
            $real_price = $original_price;
        }elseif($commission_calculation == 3) {//商品销售价
            $real_price = $promotion_price;
        }elseif($commission_calculation == 4) {//商品成本价
            $real_price = $cost_price;
        }elseif($commission_calculation == 5) {//商品利润价
            $real_price = $profit_price;
        } 
        debugLog($real_price, '==>订单商品佣金计算-节点金额<==');
        if($goods_info['is_distribution']==1 || $seckill_goods==1 || $groupshopping_goods==1 || $presell_goods==1 || $bargain_goods==1) {
            if ($distributor['isdistributor'] == 2 && $distributor['referee_id']) {//是分销商，有推荐人
                if ($base_info['purchase_type'] == 1) {//开启内购
                    $level_infoA = $level->getInfo(['id' => $distributor_level_id]);
                    //如果开启复购 等级规则变更为复购规则
                    if($level_infoA['buyagain'] == 1 && $countGoods == 1){
                        $level_infoA['recommend_type'] = $level_infoA['buyagain_recommendtype'];
                        $level_infoA['commission1'] = $level_infoA['buyagain_commission1'];
                        $level_infoA['commission2'] = $level_infoA['buyagain_commission2'];
                        $level_infoA['commission3'] = $level_infoA['buyagain_commission3'];
                        $level_infoA['commission_point1'] = $level_infoA['buyagain_commission_point1'];
                        $level_infoA['commission_point2'] = $level_infoA['buyagain_commission_point2'];
                        $level_infoA['commission_point3'] = $level_infoA['buyagain_commission_point3'];

                        $level_infoA['commission11'] = $level_infoA['buyagain_commission11'];
                        $level_infoA['commission22'] = $level_infoA['buyagain_commission22'];
                        $level_infoA['commission33'] = $level_infoA['buyagain_commission33'];
                        $level_infoA['commission_point11'] = $level_infoA['buyagain_commission_point11'];
                        $level_infoA['commission_point22'] = $level_infoA['buyagain_commission_point22'];
                        $level_infoA['commission_point33'] = $level_infoA['buyagain_commission_point33'];
                    }
                    if($level_rule_ids && in_array($distributor_level_id,$level_rule_ids)){//有特定等级返佣设置
                        foreach ($level_rule_ids as $k=>$v){
                            if($v==$distributor_level_id){
                                if($goods_info['distribution_rule_val']['recommend_type']==1){//比例返佣
                                    $commissionA11 = '';
                                    $pointA11 = '';
                                    $commissionA1 =  $level_first_rebate[$k]/100;//开启内购之后当前分销商获得一级佣金
                                    $pointA1 = $level_first_point[$k]/100;//开启内购之后当前分销商获得一级积分
                                }else{
                                    $commissionA1 = '';
                                    $pointA1 = '';
                                    $commissionA11 =  $level_first_rebate1[$k];//开启内购之后当前分销商获得一级佣金
                                    $pointA11 = $level_first_point1[$k];//开启内购之后当前分销商获得一级积分
                                }
                            }
                        }
                    }else{
                        if ($commission11=='' && $commission1!='') {//活动比例一级返佣
                            $commissionA1 = $commission1 / 100;
                            $commissionA11 = '';
                        }
                        if($commission1=='' && $commission11!=''){//活动固定一级返佣
                            $commissionA11 = $commission11;
                            $commissionA1 = '';
                        }
                        if($commission1=='' && $commission11==''){
                            if($level_infoA['recommend_type']==1){//等级比例一级返佣
                                $commissionA1 = $level_infoA['commission1'] / 100;
                                $commissionA11 = '';
                            }else{//等级固定一级返佣
                                $commissionA11 = $level_infoA['commission11'];
                                $commissionA1 = '';
                            }
                        }
                        if ($point11=='' && $point1!='') {//活动比例一级返积分
                            $pointA1 = $point1 / 100;
                            $pointA11 = '';
                        }
                        if($point1=='' && $point11!=''){//活动固定一级返积分
                            $pointA11 = $point11;
                            $pointA1 = '';
                        }
                        if($point1=='' && $point11==''){
                            if($level_infoA['recommend_type']==1){//等级比例一级返积分
                                $pointA1 = $level_infoA['commission_point1'] / 100;
                                $pointA11 = '';
                            }else{//等级固定一级返积分
                                $pointA1 = '';
                                $pointA11 = $level_infoA['commission_point11'];
                            }
                        }
                    }
                    if ($base_info['distribution_pattern'] >= 1) {//一级分销模式
                        $commissionA_id = $params['buyer_id'];//获得一级佣金的用户id
                        if($commissionA1!=''){//比例一级返佣
                            $commissionA = twoDecimal($real_price * $commissionA1*$order_goods_info['num']);//开启内购之后当前分销商获得一级佣金
                        }
                        if($commissionA11!=''){//固定一级返佣
                            $commissionA =  $commissionA11*$order_goods_info['num'];//开启内购之后当前分销商获得一级佣金
                        }
                        if($pointA1!=''){//比例一级返积分
                            $pointA = floor($real_price * $pointA1*$order_goods_info['num']);//开启内购之后当前分销商获得一级积分
                        }
                        if($pointA11!=''){//固定一级返积分
                            $pointA =  $pointA11*$order_goods_info['num'];//开启内购之后当前分销商获得一级积分
                        }
                    }
                    
                    if ($base_info['distribution_pattern'] >= 2 && $distributor['referee_id']) {//二级分销模式
                        $distributorB = $member->getInfo(['uid' => $distributor['referee_id']]);
                        if($distributorB && $distributorB['isdistributor']==2){
                            $level_infoB = $level->getInfo(['id' => $distributorB['distributor_level_id']]);
                            //如果开启复购 等级规则变更为复购规则
                            if($level_infoB['buyagain'] == 1 && $countGoods == 1){
                                $level_infoB['recommend_type'] = $level_infoB['buyagain_recommendtype'];
                                $level_infoB['commission1'] = $level_infoB['buyagain_commission1'];
                                $level_infoB['commission2'] = $level_infoB['buyagain_commission2'];
                                $level_infoB['commission3'] = $level_infoB['buyagain_commission3'];
                                $level_infoB['commission_point1'] = $level_infoB['buyagain_commission_point1'];
                                $level_infoB['commission_point2'] = $level_infoB['buyagain_commission_point2'];
                                $level_infoB['commission_point3'] = $level_infoB['buyagain_commission_point3'];

                                $level_infoB['commission11'] = $level_infoB['buyagain_commission11'];
                                $level_infoB['commission22'] = $level_infoB['buyagain_commission22'];
                                $level_infoB['commission33'] = $level_infoB['buyagain_commission33'];
                                $level_infoB['commission_point11'] = $level_infoB['buyagain_commission_point11'];
                                $level_infoB['commission_point22'] = $level_infoB['buyagain_commission_point22'];
                                $level_infoB['commission_point33'] = $level_infoB['buyagain_commission_point33'];
                            }
                            if($level_rule_ids && in_array($distributorB['distributor_level_id'],$level_rule_ids)){//有特定等级返佣设置
                                foreach ($level_rule_ids as $k=>$v){
                                    if($v==$distributorB['distributor_level_id']){
                                        if($goods_info['distribution_rule_val']['recommend_type']==1){//比例返佣
                                            $commissionB2 =  $level_second_rebate[$k]/100;
                                            $pointB2 =  $level_second_point[$k]/100;
                                            $pointB22 ='';
                                            $commissionB22 ='';
                                        }else{
                                            $pointB2 ='';
                                            $commissionB2 ='';
                                            $commissionB22 =  $level_second_rebate1[$k];
                                            $pointB22 =  $level_second_point1[$k];
                                        }
                                    }
                                }
                            }else{
                                if($commission22=='' && $commission2!=''){//活动比例二级返佣
                                    $commissionB2 = $commission2/100;
                                    $commissionB22 ='';
                                }
                                if($commission2=='' && $commission22!=''){//活动固定二级返佣
                                    $commissionB22 = $commission22;
                                    $commissionB2 ='';
                                }
                                if($commission2=='' && $commission22==''){
                                    if($level_infoB['recommend_type']==1){//等级比例二级返佣
                                        $commissionB2 = $level_infoB['commission2'] / 100;
                                        $commissionB22 ='';
                                    }else{//等级固定二级返佣
                                        $commissionB22 = $level_infoB['commission22'];
                                        $commissionB2 ='';
                                    }
                                }
                                if ($point22=='' && $point2!='') {//活动比例二级返积分
                                    $pointB2 = $point2 / 100;
                                    $pointB22='';
                                }
                                if($point2=='' && $point22!=''){//活动固定二级返积分
                                    $pointB22 = $point22;
                                    $pointB2='';
                                }
                                if($point2=='' && $point22==''){
                                    if($level_infoB['recommend_type']==1){//等级比例二级返积分
                                        $pointB2 = $level_infoB['commission_point2'] / 100;
                                        $pointB22='';
                                    }else{//等级固定二级返积分
                                        $pointB22 = $level_infoB['commission_point22'];
                                        $pointB2='';
                                    }
                                }
                            }
                            $commissionB_id = $distributorB['uid'];//获得二级佣金的用户id
                            if($commissionB2!=''){
                                $commissionB = twoDecimal($real_price * $commissionB2*$order_goods_info['num']);//当前分销商的推荐人获得二级佣金
                            }
                            if($commissionB22!=''){
                                $commissionB = $commissionB22*$order_goods_info['num'];//当前分销商的推荐人获得二级固定佣金
                            }
                            if($pointB2!=''){//比例二级返积分
                                $pointB = floor($real_price * $pointB2*$order_goods_info['num']);//开启内购之后当前分销商获得二级积分
                            }
                            if($pointB22!=''){//固定二级返积分
                                $pointB =  $pointB22*$order_goods_info['num'];//开启内购之后当前分销商获得二级积分
                            }
                        }
                        if ($base_info['distribution_pattern'] >= 3 && $distributorB['referee_id']) {//三级分销模式
                            $distributorC = $member->getInfo(['uid' => $distributorB['referee_id']]);
                            if($distributorC && $distributorC['isdistributor']==2){
                                $level_infoC = $level->getInfo(['id' => $distributorC['distributor_level_id']]);
                                //如果开启复购 等级规则变更为复购规则
                                if($level_infoC['buyagain'] == 1 && $countGoods == 1){
                                    $level_infoC['recommend_type'] = $level_infoC['buyagain_recommendtype'];
                                    $level_infoC['commission1'] = $level_infoC['buyagain_commission1'];
                                    $level_infoC['commission2'] = $level_infoC['buyagain_commission2'];
                                    $level_infoC['commission3'] = $level_infoC['buyagain_commission3'];
                                    $level_infoC['commission_point1'] = $level_infoC['buyagain_commission_point1'];
                                    $level_infoC['commission_point2'] = $level_infoC['buyagain_commission_point2'];
                                    $level_infoC['commission_point3'] = $level_infoC['buyagain_commission_point3'];

                                    $level_infoC['commission11'] = $level_infoC['buyagain_commission11'];
                                    $level_infoC['commission22'] = $level_infoC['buyagain_commission22'];
                                    $level_infoC['commission33'] = $level_infoC['buyagain_commission33'];
                                    $level_infoC['commission_point11'] = $level_infoC['buyagain_commission_point11'];
                                    $level_infoC['commission_point22'] = $level_infoC['buyagain_commission_point22'];
                                    $level_infoC['commission_point33'] = $level_infoC['buyagain_commission_point33'];
                                }
                                if($level_rule_ids && in_array($distributorC['distributor_level_id'],$level_rule_ids)){//有特定等级返佣设置
                                    foreach ($level_rule_ids as $k=>$v){
                                        if($v==$distributorC['distributor_level_id']){
                                            if($goods_info['distribution_rule_val']['recommend_type']==1){//比例返佣
                                                $commissionC3 = $level_third_rebate[$k]/100;
                                                $pointC3 = $level_third_point[$k]/100;
                                                $commissionC33 = '';
                                                $pointC33 = '';
                                            }else{
                                                $commissionC3 ='';
                                                $pointC3 = '';
                                                $commissionC33 =  $level_third_rebate1[$k];
                                                $pointC33 = $level_third_point1[$k];
                                            }
                                        }
                                    }
                                }else{
                                    if($commission33=='' && $commission3!=''){//活动比例三级返佣
                                        $commissionC3 = $commission3 / 100;
                                        $commissionC33 = '';
                                    }
                                    if($commission3=='' && $commission33!=''){//活动固定三级返佣
                                        $commissionC33 = $commission33;
                                        $commissionC3 ='';
                                    }
                                    if($commission3=='' && $commission33==''){
                                        if($level_infoC['recommend_type']==1){//等级比例三级返佣
                                            $commissionC3 = $level_infoC['commission3'] / 100;
                                            $commissionC33 = '';
                                        }else{//等级固定三级返佣
                                            $commissionC33 = $level_infoC['commission33'];
                                            $commissionC3 ='';
                                        }
                                    }
                                    if ($point33=='' && $point3!='') {//活动比例三级返积分
                                        $pointC3 = $point3 / 100;
                                        $pointC33 = '';
                                    }
                                    if($point3=='' && $point33!=''){//活动固定三级返积分
                                        $pointC33 = $point33;
                                        $pointC3 = '';
                                    }
                                    if($point3=='' && $point33==''){
                                        if($level_infoC['recommend_type']==1){//等级比例三级返积分
                                            $pointC3 = $level_infoC['commission_point3'] / 100;
                                            $pointC33 = '';
                                        }else{//等级固定二级返积分
                                            $pointC33 = $level_infoC['commission_point33'];
                                            $pointC3 = '';
                                        }
                                    }
                                }
                                $commissionC_id = $distributorC['uid'];//获得三级佣金的用户id
                                if($commissionC3!=''){
                                    $commissionC = twoDecimal($real_price * $commissionC3*$order_goods_info['num']);//当前分销商的推荐人的上级获得三级佣金
                                }
                                if($commissionC33!=''){
                                    $commissionC =  $commissionC33*$order_goods_info['num'];//当前分销商的推荐人的上级获得固定佣金
                                }
                                if($pointC3!=''){//比例三级返积分
                                    $pointC = floor($real_price * $pointC3*$order_goods_info['num']);//开启内购之后当前分销商获得三级积分
                                }
                                if($pointC33!=''){//固定三级返积分
                                    $pointC =  $pointC33*$order_goods_info['num'];//开启内购之后当前分销商获得三级积分
                                }
                            }
                        }
                    }
                }else{//未开启内购
                    if ($base_info['distribution_pattern'] >= 1 && $distributor['referee_id']) {//一级分销模式
                        $distributorA = $member->getInfo(['uid' => $distributor['referee_id']]);
                        if ($distributorA && $distributorA['isdistributor'] == 2) {
                            $level_infoA = $level->getInfo(['id' => $distributorA['distributor_level_id']]);
                            //如果开启复购 等级规则变更为复购规则
                            if($level_infoA['buyagain'] == 1 && $countGoods == 1){
                                $level_infoA['recommend_type'] = $level_infoA['buyagain_recommendtype'];
                                $level_infoA['commission1'] = $level_infoA['buyagain_commission1'];
                                $level_infoA['commission2'] = $level_infoA['buyagain_commission2'];
                                $level_infoA['commission3'] = $level_infoA['buyagain_commission3'];
                                $level_infoA['commission_point1'] = $level_infoA['buyagain_commission_point1'];
                                $level_infoA['commission_point2'] = $level_infoA['buyagain_commission_point2'];
                                $level_infoA['commission_point3'] = $level_infoA['buyagain_commission_point3'];

                                $level_infoA['commission11'] = $level_infoA['buyagain_commission11'];
                                $level_infoA['commission22'] = $level_infoA['buyagain_commission22'];
                                $level_infoA['commission33'] = $level_infoA['buyagain_commission33'];
                                $level_infoA['commission_point11'] = $level_infoA['buyagain_commission_point11'];
                                $level_infoA['commission_point22'] = $level_infoA['buyagain_commission_point22'];
                                $level_infoA['commission_point33'] = $level_infoA['buyagain_commission_point33'];
                            }
                            if ($level_rule_ids && in_array($distributorA['distributor_level_id'], $level_rule_ids)) {//有特定等级返佣设置
                                foreach ($level_rule_ids as $k => $v) {
                                    if ($v == $distributorA['distributor_level_id']) {
                                        if ($goods_info['distribution_rule_val']['recommend_type'] == 1) {//比例返佣
                                            $commissionA1 = $level_first_rebate[$k]/100;//开启内购之后当前分销商获得一级佣金
                                            $pointA1 = $level_first_point[$k]/100;//开启内购之后当前分销商获得一级积分
                                            $commissionA11 = '';
                                            $pointA11 = '';
                                        } else {
                                            $commissionA11 = $level_first_rebate1[$k];//开启内购之后当前分销商获得一级佣金
                                            $pointA11 =  $level_first_point1[$k];//开启内购之后当前分销商获得一级积分
                                            $commissionA1 = '';
                                            $pointA1 = '';
                                        }
                                    }
                                }
                            } else {
                                if ($commission11=='' && $commission1!='') {//活动比例一级返佣
                                    $commissionA1 = $commission1 / 100;
                                    $commissionA11 = '';
                                }
                                if ($commission1=='' && $commission11!='') {//活动固定一级返佣
                                    $commissionA11 = $commission11;
                                    $commissionA1 = '';
                                }
                                if ($commission1=='' && $commission11=='') {
                                    if ($level_infoA['recommend_type'] == 1) {//等级比例一级返佣
                                        $commissionA1 = $level_infoA['commission1'] / 100;
                                        $commissionA11 = '';
                                    } else {///等级固定一级返佣
                                        $commissionA11 = $level_infoA['commission11'];
                                        $commissionA1 = '';
                                    }
                                }
                                if ($point11=='' && $point1!='') {//活动比例一级返积分
                                    $pointA1 = $point1 / 100;
                                    $pointA11 = '';
                                }
                                if ($point1=='' && $point11!='') {//活动固定一级返积分
                                    $pointA11 = $point11;
                                    $pointA1 = '';
                                }
                                if ($point1=='' && $point11=='') {
                                    if ($level_infoA['recommend_type'] == 1) {//等级比例一级返积分
                                        $pointA1 = $level_infoA['commission_point1'] / 100;
                                        $pointA11 = '';
                                    } else {//等级固定一级返积分
                                        $pointA11 = $level_infoA['commission_point11'];
                                        $pointA1 = '';
                                    }
                                }
                            }
                            $commissionA_id = $distributorA['uid'];//获得一级佣金的用户id
                            if ($commissionA1!='') {
                                $commissionA = twoDecimal($real_price * $commissionA1*$order_goods_info['num']);//当前分销商的推荐人获得一级佣金
                            }
                            if ($commissionA11!='') {
                                $commissionA = $commissionA11 * $order_goods_info['num'];//当前分销商的推荐人获得一级固定佣金
                            }
                            if ($pointA1!='') {//比例一级返积分
                                $pointA = floor($real_price * $pointA1*$order_goods_info['num']);//开启内购之后当前分销商获得一级积分
                            }
                            if ($pointA11!='') {//固定一级返积分
                                $pointA = $pointA11 * $order_goods_info['num'];//开启内购之后当前分销商获得一级积分
                            }
                        }
                    }
                        if ($base_info['distribution_pattern'] >= 2 && $distributorA['referee_id']) {//二级分销模式
                            $distributorB = $member->getInfo(['uid' => $distributorA['referee_id']]);
                            if($distributorB && $distributorB['isdistributor']==2){
                                $level_infoB = $level->getInfo(['id' => $distributorB['distributor_level_id']]);
                                //如果开启复购 等级规则变更为复购规则
                                if($level_infoB['buyagain'] == 1 && $countGoods ==1){
                                    $level_infoB['recommend_type'] = $level_infoB['buyagain_recommendtype'];
                                    $level_infoB['commission1'] = $level_infoB['buyagain_commission1'];
                                    $level_infoB['commission2'] = $level_infoB['buyagain_commission2'];
                                    $level_infoB['commission3'] = $level_infoB['buyagain_commission3'];
                                    $level_infoB['commission_point1'] = $level_infoB['buyagain_commission_point1'];
                                    $level_infoB['commission_point2'] = $level_infoB['buyagain_commission_point2'];
                                    $level_infoB['commission_point3'] = $level_infoB['buyagain_commission_point3'];

                                    $level_infoB['commission11'] = $level_infoB['buyagain_commission11'];
                                    $level_infoB['commission22'] = $level_infoB['buyagain_commission22'];
                                    $level_infoB['commission33'] = $level_infoB['buyagain_commission33'];
                                    $level_infoB['commission_point11'] = $level_infoB['buyagain_commission_point11'];
                                    $level_infoB['commission_point22'] = $level_infoB['buyagain_commission_point22'];
                                    $level_infoB['commission_point33'] = $level_infoB['buyagain_commission_point33'];
                                }
                                if($level_rule_ids && in_array($distributorB['distributor_level_id'],$level_rule_ids)){//有特定等级返佣设置
                                    foreach ($level_rule_ids as $k=>$v){
                                        if($v==$distributorB['distributor_level_id']){
                                            if($goods_info['distribution_rule_val']['recommend_type']==1){//比例返佣
                                                $commissionB2 = $level_second_rebate[$k]/100;
                                                $pointB2 = $level_second_point[$k]/100;
                                                $pointB22 ='';
                                                $commissionB22 ='';
                                            }else{
                                                $commissionB22 =  $level_second_rebate1[$k];
                                                $pointB22 =  $level_second_point1[$k];
                                                $pointB2 ='';
                                                $commissionB2 ='';
                                            }
                                        }
                                    }
                                }else{
                                    if($commission22=='' && $commission2!=''){//活动比例二级返佣
                                        $commissionB2 = $commission2/100;
                                        $commissionB22 ='';
                                    }
                                    if($commission2=='' && $commission22!=''){//活动固定二级返佣
                                        $commissionB22 = $commission22;
                                        $commissionB2 ='';
                                    }
                                    if($commission2=='' && $commission22==''){
                                        if($level_infoB['recommend_type']==1){//等级比例二级返佣
                                            $commissionB2 = $level_infoB['commission2'] / 100;
                                            $commissionB22 ='';
                                        }else{//等级固定二级返佣
                                            $commissionB22 = $level_infoB['commission22'];
                                            $commissionB2 ='';
                                        }
                                    }
                                    if ($point22=='' && $point2!='') {//活动比例二级返积分
                                        $pointB2 = $point2 / 100;
                                        $pointB22 ='';
                                    }
                                    if($point2=='' && $point22!=''){//活动固定二级返积分
                                        $pointB22 =  $point22;
                                        $pointB2 ='';
                                    }
                                    if($point2=='' && $point22==''){
                                        if($level_infoB['recommend_type']==1){//等级比例二级返积分
                                            $pointB2 = $level_infoB['commission_point2'] / 100;
                                            $pointB22 ='';
                                        }else{//等级固定二级返积分
                                            $pointB22 = $level_infoB['commission_point22'];
                                            $pointB2 ='';
                                        }
                                    }
                                }
                                $commissionB_id = $distributorB['uid'];//获得二级佣金的用户id
                                if($commissionB2!=''){
                                    $commissionB = twoDecimal($real_price * $commissionB2*$order_goods_info['num']);//当前分销商的推荐人的上级获得二级佣金
                                }
                                if($commissionB22!=''){
                                    $commissionB = $commissionB22*$order_goods_info['num'];//当前分销商的推荐人的上级获得二级佣金二级固定佣金
                                }
                                if($pointB2!=''){//比例二级返积分
                                    $pointB = floor($real_price * $pointB2*$order_goods_info['num']);//开启内购之后当前分销商获得二级积分
                                }
                                if($pointB22!=''){//固定二级返积分
                                    $pointB =  $pointB22*$order_goods_info['num'];//开启内购之后当前分销商获得二级积分
                                }
                            }
                            if ($base_info['distribution_pattern'] >= 3 && $distributorB['referee_id']) {//三级分销模式
                                $distributorC = $member->getInfo(['uid' => $distributorB['referee_id']]);
                                if($distributorC && $distributorC['isdistributor']==2){
                                    $level_infoC = $level->getInfo(['id' => $distributorC['distributor_level_id']]);
                                    //如果开启复购 等级规则变更为复购规则
                                    if($level_infoC['buyagain'] == 1 && $countGoods ==1){
                                        $level_infoC['recommend_type'] = $level_infoC['buyagain_recommendtype'];
                                        $level_infoC['commission1'] = $level_infoC['buyagain_commission1'];
                                        $level_infoC['commission2'] = $level_infoC['buyagain_commission2'];
                                        $level_infoC['commission3'] = $level_infoC['buyagain_commission3'];
                                        $level_infoC['commission_point1'] = $level_infoC['buyagain_commission_point1'];
                                        $level_infoC['commission_point2'] = $level_infoC['buyagain_commission_point2'];
                                        $level_infoC['commission_point3'] = $level_infoC['buyagain_commission_point3'];

                                        $level_infoC['commission11'] = $level_infoC['buyagain_commission11'];
                                        $level_infoC['commission22'] = $level_infoC['buyagain_commission22'];
                                        $level_infoC['commission33'] = $level_infoC['buyagain_commission33'];
                                        $level_infoC['commission_point11'] = $level_infoC['buyagain_commission_point11'];
                                        $level_infoC['commission_point22'] = $level_infoC['buyagain_commission_point22'];
                                        $level_infoC['commission_point33'] = $level_infoC['buyagain_commission_point33'];
                                    }
                                    if($distributorC && $distributorC['isdistributor']==2) {
                                        if ($level_rule_ids && in_array($distributorC['distributor_level_id'],$level_rule_ids)) {//有特定等级返佣设置
                                            foreach ($level_rule_ids as $k => $v) {
                                                if ($v == $distributorC['distributor_level_id']) {
                                                    if ($goods_info['distribution_rule_val']['recommend_type'] == 1) {//比例返佣
                                                        $commissionC3 = $level_third_rebate[$k]/100;
                                                        $pointC3 =  $level_third_point[$k]/100;
                                                        $commissionC33 = '';
                                                        $pointC33 = '';
                                                    } else {
                                                        $commissionC33 = $level_third_rebate1[$k] ;
                                                        $pointC33 =  $level_third_point1[$k];
                                                        $commissionC3 = '';
                                                        $pointC3 = '';
                                                    }
                                                }
                                            }
                                        }else{
                                        if($commission33=='' && $commission3!=''){//活动比例三级返佣
                                            $commissionC3 = $commission3 / 100;
                                            $commissionC33 = '';
                                        }
                                        if($commission3=='' && $commission33!=''){//活动固定三级返佣
                                            $commissionC33 = $commission33;
                                            $commissionC3 = '';
                                        }
                                        if($commission3=='' && $commission33==''){
                                            if($level_infoC['recommend_type']==1){//等级比例三级返佣
                                                $commissionC3 = $level_infoC['commission3'] / 100;
                                                $commissionC33 = '';
                                            }else{//等级固定三级返佣
                                                $commissionC33 = $level_infoC['commission33'];
                                                $commissionC3 = '';
                                            }
                                        }
                                        if ($point33=='' && $point3!='') {//活动比例三级返积分
                                            $pointC3 = $point3 / 100;
                                            $pointC33 = '';
                                        }
                                        if($point3=='' && $point33!=''){//活动固定三级返积分
                                            $pointC33 = $point33;
                                            $pointC3 = '';
                                        }
                                        if($point3=='' && $point33==''){
                                            if($level_infoC['recommend_type']==1){//等级比例三级返积分
                                                $pointC3 = $level_infoC['commission_point3'] / 100;
                                                $pointC33 = '';
                                            }else{//等级固定二级返积分
                                                $pointC33 = $level_infoC['commission_point33'];
                                                $pointC3 = '';
                                            }
                                        }
                                    }
                                    $commissionC_id = $distributorC['uid'];//获得三级佣金的用户id
                                    if($commissionC3!=''){
                                        $commissionC = twoDecimal($real_price * $commissionC3*$order_goods_info['num']);//当前分销商的推荐人的上级的上级获得三级佣金
                                    }
                                    if($commissionC33!=''){
                                        $commissionC =  $commissionC33*$order_goods_info['num'];//当前分销商的推荐人的上级的上级获得获得固定佣金
                                    }
                                    if($pointC3!=''){//比例三级返积分
                                        $pointC = floor($real_price * $pointC3*$order_goods_info['num']);//开启内购之后当前分销商获得三级积分
                                    }
                                    if($pointC33!=''){//固定三级返积分
                                        $pointC =  $pointC33*$order_goods_info['num'];//开启内购之后当前分销商获得三级积分
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($distributor['isdistributor'] == 2 && $distributor['referee_id'] == "") {//是分销商，推荐人是总店
                
                $level_info = $level->getInfo(['id' => $distributor_level_id]);
                //如果开启复购 等级规则变更为复购规则
                if($level_info['buyagain'] == 1 && $countGoods ==1){
                    $level_info['recommend_type'] = $level_info['buyagain_recommendtype'];
                    $level_info['commission1'] = $level_info['buyagain_commission1'];
                    $level_info['commission2'] = $level_info['buyagain_commission2'];
                    $level_info['commission3'] = $level_info['buyagain_commission3'];
                    $level_info['commission_point1'] = $level_info['buyagain_commission_point1'];
                    $level_info['commission_point2'] = $level_info['buyagain_commission_point2'];
                    $level_info['commission_point3'] = $level_info['buyagain_commission_point3'];

                    $level_info['commission11'] = $level_info['buyagain_commission11'];
                    $level_info['commission22'] = $level_info['buyagain_commission22'];
                    $level_info['commission33'] = $level_info['buyagain_commission33'];
                    $level_info['commission_point11'] = $level_info['buyagain_commission_point11'];
                    $level_info['commission_point22'] = $level_info['buyagain_commission_point22'];
                    $level_info['commission_point33'] = $level_info['buyagain_commission_point33'];
                }
                
                if($level_rule_ids && in_array($distributor_level_id,$level_rule_ids)){//有特定等级返佣设置
                    foreach ($level_rule_ids as $k=>$v){
                        if($v==$distributor_level_id){
                            if($goods_info['distribution_rule_val']['recommend_type']==1){//比例返佣
                                $commissionA11  = '';
                                $pointA11 = '';
                                $commissionA1 = $level_first_rebate[$k]/100;//开启内购之后当前分销商获得一级佣金
                                $pointA1 = $level_first_point[$k]/100;//开启内购之后当前分销商获得一级积分
                            }else{
                                $commissionA1  = '';
                                $pointA1 = '';
                                $commissionA11 =  $level_first_rebate1[$k];//开启内购之后当前分销商获得一级佣金
                                $pointA11 = $level_first_point1[$k];//开启内购之后当前分销商获得一级积分
                            }
                        }
                        
                    }
                }else{
                    if ($commission11=='' && $commission1!='') {//活动比例一级返佣
                        $commissionA1 = $commission1 / 100;
                        $commissionA11 = '';
                    }
                    if($commission1=='' && $commission11!=''){//活动固定一级返佣
                        $commissionA11 = $commission11;
                        $commissionA1 = '';
                    }
                    if($commission1=='' && $commission11==''){
                        
                        if($level_info['recommend_type']==1){//等级比例一级返佣
                            $commissionA1 = $level_info['commission1'] / 100;
                            
                            $commissionA11 = '';
                        }else{//等级固定一级返佣
                            $commissionA11 = $level_info['commission11'];
                            
                            $commissionA1 = '';
                        }
                    }
                    if ($point11=='' && $point1!='') {//活动比例一级返积分
                        $pointA1 = $point1 / 100;
                        $pointA11 = '';
                    }
                    if($point1=='' && $point11!=''){//活动固定一级返积分
                        $pointA11 = $point11;
                        $pointA1 = '';
                    }
                    if($point1=='' && $point11==''){
                        if($level_info['recommend_type']==1){//等级比例一级返积分
                            $pointA1 = $level_info['commission_point1'] / 100;
                            $pointA11 = '';
                        }else{//等级固定一级返积分
                            $pointA11 = $level_info['commission_point11'];
                            $pointA1 = '';
                        }
                    }
                }
                if ($base_info['purchase_type'] == 1) {//开启内购
                    $commissionA_id = $distributor['uid'];//获得一级佣金的用户id
                    
                    if($commissionA1!=''){
                        $commissionA = twoDecimal($real_price * $commissionA1*$order_goods_info['num']);//开启内购之后当前分销商获得一级比例佣金
                    }
                   
                    if($commissionA11!=''){
                        $commissionA = $commissionA11*$order_goods_info['num'];//开启内购之后当前分销商获得一级固定佣金
                    }
                    if($pointA1!=''){//比例一级返积分
                        $pointA = floor($real_price * $pointA1*$order_goods_info['num']);//开启内购之后当前分销商获得一级积分
                    }
                    if($pointA11!=''){//固定一级返积分
                        $pointA =  $pointA11*$order_goods_info['num'];//开启内购之后当前分销商获得一级积分
                    }
                }
            }
            if ($distributor['isdistributor'] != 2 && $distributor['referee_id']) {//不是分销商但有推荐人
                if ($base_info['distribution_pattern'] >= 1 && $distributor['referee_id']) {//一级分销模式
                    $distributorA = $member->getInfo(['uid' => $distributor['referee_id']]);
                    if ($distributorA && $distributorA['isdistributor'] == 2) {
                        $level_infoA = $level->getInfo(['id' => $distributorA['distributor_level_id']]);
                        //如果开启复购 等级规则变更为复购规则
                        if($level_infoA['buyagain'] == 1 && $countGoods ==1){
                            $level_infoA['recommend_type'] = $level_infoA['buyagain_recommendtype'];
                            $level_infoA['commission1'] = $level_infoA['buyagain_commission1'];
                            $level_infoA['commission2'] = $level_infoA['buyagain_commission2'];
                            $level_infoA['commission3'] = $level_infoA['buyagain_commission3'];
                            $level_infoA['commission_point1'] = $level_infoA['buyagain_commission_point1'];
                            $level_infoA['commission_point2'] = $level_infoA['buyagain_commission_point2'];
                            $level_infoA['commission_point3'] = $level_infoA['buyagain_commission_point3'];

                            $level_infoA['commission11'] = $level_infoA['buyagain_commission11'];
                            $level_infoA['commission22'] = $level_infoA['buyagain_commission22'];
                            $level_infoA['commission33'] = $level_infoA['buyagain_commission33'];
                            $level_infoA['commission_point11'] = $level_infoA['buyagain_commission_point11'];
                            $level_infoA['commission_point22'] = $level_infoA['buyagain_commission_point22'];
                            $level_infoA['commission_point33'] = $level_infoA['buyagain_commission_point33'];
                        }
                        if ($level_rule_ids && in_array($distributorA['distributor_level_id'], $level_rule_ids)) {//有特定等级返佣设置
                            foreach ($level_rule_ids as $k => $v) {
                                if ($v == $distributorA['distributor_level_id']) {
                                    if ($goods_info['distribution_rule_val']['recommend_type'] == 1) {//比例返佣
                                        $commissionA11 = '';
                                        $pointA11 = '';
                                        $commissionA1 = $level_first_rebate[$k]/100;//开启内购之后当前分销商获得一级佣金
                                        $pointA1 = $level_first_point[$k]/100;//开启内购之后当前分销商获得一级积分
                                    } else {
                                        $commissionA1 = '';
                                        $pointA1 = '';
                                        $commissionA11 = $level_first_rebate1[$k];//开启内购之后当前分销商获得一级佣金
                                        $pointA11 = $level_first_point1[$k];//开启内购之后当前分销商获得一级积分
                                    }
                                }
                            }
                        } else {
                            if ($commission11=='' && $commission1!='') {//活动比例一级返佣
                                $commissionA1 = $commission1 / 100;
                                $commissionA11 = '';
                            }
                            if ($commission1=='' && $commission11!='') {//活动固定一级返佣
                                $commissionA11 = $commission11;
                                $commissionA1 = '';
                            }
                            if ($commission1=='' && $commission11=='') {
                                if ($level_infoA['recommend_type'] == 1) {//等级比例一级返佣
                                    $commissionA1 = $level_infoA['commission1'] / 100;
                                    $commissionA11 = '';
                                } else {//等级固定一级返佣
                                    $commissionA11 = $level_infoA['commission11'];
                                    $commissionA1 = '';
                                }
                            }
                            if ($point11=='' && $point1!='') {//活动比例一级返积分
                                $pointA1 = $point1 / 100;
                                $pointA11 = '';
                            }
                            if($point1=='' && $point11!='') {//活动固定一级返积分
                                $pointA11 = $point11;
                                $pointA1 = '';
                            }
                            if ($point1=='' && $point11=='') {
                                if ($level_infoA['recommend_type'] == 1) {//等级比例一级返积分
                                    $pointA1 = $level_infoA['commission_point1'] / 100;
                                    $pointA11 = '';
                                } else {//等级固定一级返积分
                                    $pointA11 = $level_infoA['commission_point11'];
                                    $pointA1 = '';
                                }
                            }
                        }

                        $commissionA_id = $distributorA['uid'];//获得一级佣金的用户id
                        if ($commissionA1!='') {
                            $commissionA = twoDecimal($real_price * $commissionA1*$order_goods_info['num']);//当前会员的推荐人获得一级佣金
                        }
                        if ($commissionA11!='') {
                            $commissionA = $commissionA11 * $order_goods_info['num'];//当前会员的推荐人获得一级固定佣金
                        }
                        if ($pointA1!='') {//比例一级返积分
                            $pointA = floor($real_price * $pointA1*$order_goods_info['num']);//开启内购之后当前分销商获得一级积分
                        }
                        if ($pointA11!='') {//固定一级返积分
                            $pointA = $pointA11 * $order_goods_info['num'];//开启内购之后当前分销商获得一级积分
                        }
                    }
                    if ($base_info['distribution_pattern'] >= 2 && $distributorA['referee_id']) {//二级分销模式
                        $distributorB = $member->getInfo(['uid' => $distributorA['referee_id']]);
                        if ($distributorB && $distributorB['isdistributor'] == 2) {//如果该分销商的推荐人有上级
                            $level_infoB = $level->getInfo(['id' => $distributorB['distributor_level_id']]);
                            //如果开启复购 等级规则变更为复购规则
                            if($level_infoB['buyagain'] == 1 && $countGoods ==1){
                                $level_infoB['recommend_type'] = $level_infoB['buyagain_recommendtype'];
                                $level_infoB['commission1'] = $level_infoB['buyagain_commission1'];
                                $level_infoB['commission2'] = $level_infoB['buyagain_commission2'];
                                $level_infoB['commission3'] = $level_infoB['buyagain_commission3'];
                                $level_infoB['commission_point1'] = $level_infoB['buyagain_commission_point1'];
                                $level_infoB['commission_point2'] = $level_infoB['buyagain_commission_point2'];
                                $level_infoB['commission_point3'] = $level_infoB['buyagain_commission_point3'];

                                $level_infoB['commission11'] = $level_infoB['buyagain_commission11'];
                                $level_infoB['commission22'] = $level_infoB['buyagain_commission22'];
                                $level_infoB['commission33'] = $level_infoB['buyagain_commission33'];
                                $level_infoB['commission_point11'] = $level_infoB['buyagain_commission_point11'];
                                $level_infoB['commission_point22'] = $level_infoB['buyagain_commission_point22'];
                                $level_infoB['commission_point33'] = $level_infoB['buyagain_commission_point33'];
                            }
                            if ($level_rule_ids && in_array($distributorB['distributor_level_id'], $level_rule_ids)) {//有特定等级返佣设置
                                foreach ($level_rule_ids as $k => $v) {
                                    if ($v == $distributorB['distributor_level_id']) {
                                        if ($goods_info['distribution_rule_val']['recommend_type'] == 1) {//比例返佣
                                            $commissionB2 =  $level_second_rebate[$k]/100;
                                            $pointB2 = $level_second_point[$k]/100;
                                            $commissionB22= '';
                                            $pointB22='';
                                        } else {
                                            $commissionB22 = $level_second_rebate1[$k];
                                            $pointB22 = $level_second_point1[$k];
                                            $commissionB2= '';
                                            $pointB2='';
                                        }
                                    }
                                }
                            } else {
                                if ($commission22=='' && $commission2!='') {//活动比例二级返佣
                                    $commissionB2 = $commission2 / 100;
                                    $commissionB22= '';
                                }
                                if ($commission2=='' && $commission22!='') {//活动固定二级返佣
                                    $commissionB22 = $commission22;
                                    $commissionB2= '';
                                }
                                if ($commission2=='' && $commission22=='') {
                                    if ($level_infoB['recommend_type'] == 1) {//等级比例二级返佣
                                        $commissionB2 = $level_infoB['commission2'] / 100;
                                        $commissionB22= '';
                                    } else {//等级固定二级返佣
                                        $commissionB22 = $level_infoB['commission22'];
                                        $commissionB2= '';
                                    }
                                }
                                if ($point22=='' && $point2!='') {//活动比例二级返积分
                                    $pointB2 = $point2 / 100;
                                    $pointB22='';
                                }
                                if ($point2=='' && $point22!='') {//活动固定二级返积分
                                    $pointB22 = $point22;
                                    $pointB2='';
                                }
                                if ($point2=='' && $point22=='') {
                                    if ($level_infoB['recommend_type'] == 1) {//等级比例二级返积分
                                        $pointB2 = $level_infoB['commission_point2'] / 100;
                                        $pointB22='';
                                    } else {//等级固定二级返积分
                                        $pointB22 = $level_infoB['commission_point22'];
                                        $pointB2='';
                                    }
                                }
                            }
                            $commissionB_id = $distributorB['uid'];//获得二级佣金的用户id
                            if ($commissionB2!='') {
                                $commissionB = twoDecimal($real_price * $commissionB2*$order_goods_info['num']);//当前分销商的推荐人的上级获得二级佣金
                            }
                            if ($commissionB22!='') {
                                $commissionB = $commissionB22 * $order_goods_info['num'];//当前分销商的推荐人的上级获得二级固定佣金
                            }
                            if ($pointB2!='') {//比例二级返积分
                                $pointB = floor($real_price * $pointB2*$order_goods_info['num']);//开启内购之后当前分销商获得二级积分
                            }
                            if ($pointB22!='') {//固定二级返积分
                                $pointB = $pointB22 * $order_goods_info['num'];//开启内购之后当前分销商获得二级积分
                            }
                            if ($base_info['distribution_pattern'] >= 3 && $distributorB['referee_id']) {//三级分销模式
                                $distributorC = $member->getInfo(['uid' => $distributorB['referee_id']]);
                                //如果开启复购 等级规则变更为复购规则
                                if($level_infoC['buyagain'] == 1 && $countGoods ==1){
                                    $level_infoC['recommend_type'] = $level_infoC['buyagain_recommendtype'];
                                    $level_infoC['commission1'] = $level_infoC['buyagain_commission1'];
                                    $level_infoC['commission2'] = $level_infoC['buyagain_commission2'];
                                    $level_infoC['commission3'] = $level_infoC['buyagain_commission3'];
                                    $level_infoC['commission_point1'] = $level_infoC['buyagain_commission_point1'];
                                    $level_infoC['commission_point2'] = $level_infoC['buyagain_commission_point2'];
                                    $level_infoC['commission_point3'] = $level_infoC['buyagain_commission_point3'];

                                    $level_infoC['commission11'] = $level_infoC['buyagain_commission11'];
                                    $level_infoC['commission22'] = $level_infoC['buyagain_commission22'];
                                    $level_infoC['commission33'] = $level_infoC['buyagain_commission33'];
                                    $level_infoC['commission_point11'] = $level_infoC['buyagain_commission_point11'];
                                    $level_infoC['commission_point22'] = $level_infoC['buyagain_commission_point22'];
                                    $level_infoC['commission_point33'] = $level_infoC['buyagain_commission_point33'];
                                }
                                if ($distributorC && $distributorC['isdistributor'] == 2) {//如果该分销商的推荐人有上级
                                    $level_infoC = $level->getInfo(['id' => $distributorC['distributor_level_id']]);
                                    if ($distributorC && $distributorC['isdistributor'] == 2) {
                                        if ($level_rule_ids && in_array($distributorC['distributor_level_id'], $level_rule_ids)) {//有特定等级返佣设置
                                            foreach ($level_rule_ids as $k => $v) {
                                                if ($v == $distributorC['distributor_level_id']) {
                                                    if ($goods_info['distribution_rule_val']['recommend_type'] == 1) {//比例返佣
                                                        $commissionC3 = $level_third_rebate[$k]/100;
                                                        $pointC3 =  $level_third_point[$k]/100;
                                                        $commissionC33 = '';
                                                        $pointC33 = '';
                                                    } else {
                                                        $commissionC33 = $level_third_rebate1[$k];
                                                        $pointC33 = $level_third_point1[$k];
                                                        $commissionC3 = '';
                                                        $pointC3 = '';
                                                    }
                                                }
                                            }
                                        } else {
                                            if ($commission33=='' && $commission3!='') {//活动比例三级返佣
                                                $commissionC3 = $commission3 / 100;
                                                $commissionC33 = '';
                                            }
                                            if ($commission3=='' && $commission33!='') {//活动固定三级返佣
                                                $commissionC33 = $commission33;
                                                $commissionC3 = '';
                                            }
                                            if ($commission3=='' && $commission33=='') {
                                                if ($level_infoC['recommend_type'] == 1) {//等级比例三级返佣
                                                    $commissionC3 = $level_infoC['commission3'] / 100;
                                                    $commissionC33 = '';
                                                } else {//等级固定三级返佣
                                                    $commissionC33 = $level_infoC['commission33'];
                                                    $commissionC3 = '';
                                                }
                                            }
                                            if ($point33=='' && $point3!='') {//活动比例三级返积分
                                                $pointC3 = $point3 / 100;
                                                $pointC33 = '';
                                            }
                                            if ($point3=='' && $point33!='') {//活动固定三级返积分
                                                $pointC33 = $point33;
                                                $pointC3 = '';
                                            }
                                            if ($point3=='' && $point33=='') {
                                                if ($level_infoC['recommend_type'] == 1) {//等级比例三级返积分
                                                    $pointC3 = $level_infoC['commission_point3'] / 100;
                                                    $pointC33 = '';
                                                } else {//等级固定二级返积分
                                                    $pointC33 = $level_infoC['commission_point33'];
                                                    $pointC3 = '';
                                                }
                                            }
                                        }
                                        $commissionC_id = $distributorC['uid'];//获得三级佣金的用户id
                                        if ($commissionC3!='') {
                                            $commissionC = twoDecimal($real_price * $commissionC3*$order_goods_info['num']);//当前会元的推荐人的上级的上级获得三级佣金
                                        }
                                        if ($commissionC33!='') {
                                            $commissionC = $commissionC33 * $order_goods_info['num'];//当前分销商的推荐人的上级的上级获得固定三级佣金
                                        }
                                        if ($pointC3!='') {//比例三级返积分
                                            $pointC = floor($real_price * $pointC3*$order_goods_info['num']);//开启内购之后当前分销商获得三级积分
                                        }
                                        if ($pointC33!='') {//固定三级返积分
                                            $pointC = $pointC33 * $order_goods_info['num'];//开启内购之后当前分销商获得三级积分
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $commission_total = $commissionA + $commissionB + $commissionC;
            $point_total = $pointA + $pointB + $pointC;
            if($commissionA_id || $commissionB_id || $commissionC_id){
                $commission = new VslOrderDistributorCommissionModel();
                $order_info =  $commission->getInfo(['order_goods_id'=>$params['order_goods_id'],'order_id'=>$params['order_id']]);
                if($order_info){
                    return 1;
                }
                if($distribution_admin_status == 1){
                    $shop_id = $goods_info['shop_id'] ? $goods_info['shop_id'] : $order_info['shop_id'];
                }else{
                    $shop_id = 0;
                }
                if($distribution_admin_status == 1 && $goods_info['distribution_rule']==2 && $goods_info['shop_id'] > 0){
                    //旧商品 开启
                    $commissionA = 0;
                    $pointA = 0;
                    $commissionB = 0;
                    $pointB = 0;
                    $commissionC = 0;
                    $pointC = 0;
                    $commission_total = 0;
                    $point_total = 0;
                    $shop_id = 0;
                }
                $commission->startTrans();
                try {
                    $data = [
                        'order_id' => $params['order_id'],
                        'order_goods_id' => $params['order_goods_id'],
                        'buyer_id' => $params['buyer_id'],
                        'website_id' => $params['website_id'],
                        'commissionA_id' => $commissionA_id,
                        'commissionA' => $commissionA,
                        'pointA' => $pointA,
                        'commissionB_id' => $commissionB_id,
                        'commissionB' => $commissionB,
                        'pointB' => $pointB,
                        'commissionC_id' => $commissionC_id,
                        'commissionC' => $commissionC,
                        'pointC' => $pointC,
                        'commission' => $commission_total,
                        'point' => $point_total,
                        'shop_id' => $shop_id
                    ];
                    $order->isUpdate(true)->save(['is_distribution' =>1],['order_id' =>$params['order_id'],'is_distribution' => 0]);
                    $commission->save($data);
                    $commission->commit();
                    return 1;
                } catch (\Exception $e) {
                    $commission->rollback();
                    return $e->getMessage();
                }
            }
        }
    }
    /*
     * 已支付定金，未支付尾款关闭的预售订单退款 添加佣金账户流水表
     * orderid 订单id
     */
    public function addCommissionDistributionPresell($orderid)
    {
        $distributor_account = new VslDistributorAccountRecordsModel();
        $account_statistics = new VslDistributorAccountModel();
        $order = new VslOrderModel();
        $data_records = array();
        $update_records = [];
        $distributor_account->startTrans();
        $order_info = $order->getInfo(['order_id'=>$orderid],'order_no');
        
        try{
            $order_commission = new VslOrderDistributorCommissionModel();
            $orders = $order_commission->Query(['order_id'=>$orderid],'*');
            $up_data = array();
            foreach ($orders as $key => $value) {
                
                if(floatval($value['commissionA']) > 0){
                    //1级分销解除冻结佣金
                    $records_no_A = 'CR'.time() . rand(111, 999);
                    $countA = $account_statistics->getInfo(['uid'=> $value['commissionA_id']],'*');//佣金账户
                    $commission_data_A = array(
                        'freezing_commission' => $countA['freezing_commission']-abs($value['commissionA']),
                    );
                    $account_statistics->save($commission_data_A,['uid'=> $value['commissionA_id']]);
                    //写入记录
                    $data_records_A = array(
                        'uid' => $value['commissionA_id'],
                        'records_no'=> $records_no_A,
                        'balance'=>$countA['commission'],
                        'data_id' => $order_info['order_no'],
                        'website_id' => $value['website_id'],
                        'commission' => $value['commissionA'],
                        'text' => '已支付定金预售订单关闭,冻结佣金减少',
                        'create_time' => time(),
                        'from_type' => 2,
                        'shop_id' => intval($value['shop_id']),
                    );
                    array_push($up_data,$data_records_A);
                    // $distributor_account->save($data_records_A);
                }
                if(floatval($value['commissionB']) > 0){
                    //2级分销解除冻结佣金
                    $records_no_B = 'CR'.time() . rand(111, 999);
                    $countB = $account_statistics->getInfo(['uid'=> $value['commissionB_id']],'*');//佣金账户
                    $commission_data_B = array(
                        'freezing_commission' => $countB['freezing_commission']-abs($value['commissionB']),
                    );
                    $account_statistics->save($commission_data_B,['uid'=> $value['commissionB_id']]);
                    //写入记录
                    $data_records_B = array(
                        'uid' => $value['commissionB_id'],
                        'records_no'=> $records_no_B,
                        'balance'=>$countB['commission'],
                        'data_id' => $order_info['order_no'],
                        'website_id' => $value['website_id'],
                        'commission' => $value['commissionB'],
                        'text' => '已支付定金预售订单关闭,冻结佣金减少',
                        'create_time' => time(),
                        'from_type' => 2,
                        'shop_id' => intval($value['shop_id']),
                    );
                    array_push($up_data,$data_records_B);
                    // $distributor_account->save($data_records_B);
                }
                if(floatval($value['commissionC']) > 0){
                    //3级分销解除冻结佣金
                    $records_no_C = 'CR'.time() . rand(111, 999);
                    $countC = $account_statistics->getInfo(['uid'=> $value['commissionC_id']],'*');//佣金账户
                    $commission_data_C = array(
                        'freezing_commission' => $countC['freezing_commission']-abs($value['commissionC']),
                    );
                    $account_statistics->save($commission_data_C,['uid'=> $value['commissionC_id']]);
                    //写入记录
                    $data_records_C = array(
                        'uid' => $value['commissionC_id'],
                        'records_no'=> $records_no_C,
                        'balance'=>$countB['commission'],
                        'data_id' => $order_info['order_no'],
                        'website_id' => $value['website_id'],
                        'commission' => $value['commissionC'],
                        'text' => '已支付定金预售订单关闭,冻结佣金减少',
                        'create_time' => time(),
                        'from_type' => 2,
                        'shop_id' => intval($value['shop_id']),
                    );
                    array_push($up_data,$data_records_C);
                    // $distributor_account->save($data_records_C);
                }
                
            }
            if($up_data){
                $distributor_account->saveAll($up_data);
            }
            $distributor_account->commit();
            return 1;

        } catch (\Exception $e)
        {
            $distributor_account->rollback();
            return $e->getMessage();
        }
    }
    /*
     * 添加佣金账户流水表
     */
    public function addCommissionDistribution($params)
    {
        $distributor_account = new VslDistributorAccountRecordsModel();
        $data_records = array();
        $update_records = [];
        $distributor_account->startTrans();
        $order_id = $params['order_id'];
        $shop_id = 0;
        if($params['order_id']){
            $order = new VslOrderModel();
            $order_info = $order->getInfo(['order_id'=>$params['order_id']],'*');
            $params['order_id'] = $order_info['order_no'];
            $buyer_id = $order_info['buyer_id'];
            $shop_id = $order_info['shop_id'];
        }
        $records_no = 'CR'.time() . rand(111, 999);
        $records_info = $distributor_account->getInfo(['data_id'=>$params['data_id']]);
        try{
            //前期检测
            //更新对应佣金流水
            $account_statistics = new VslDistributorAccountModel();
            $account = new VslAccountModel();
            //更新对应佣金账户和平台账户
            $count = $account_statistics->getInfo(['uid'=> $params['uid']],'*');//佣金账户
            $account_count = $account_statistics->getInfo(['website_id'=> $params['website_id']],'*');//平台账户
            if($params['status']==1) {//订单完成，添加佣金
                //积分流水
                $this->addMemberPoint($params['point'],$params['uid'],$params['order_id'],$params['website_id']);
                //佣金账户佣金改变
                if ($count) {
                    $account_data = array(
                        'commission' => $count['commission'] + abs($params['commission']),
                        'freezing_commission' => $count['freezing_commission'] - abs($params['commission'])
                    );
                    $account_statistics->save($account_data, ['uid' => $params['uid']]);
                }
                if($buyer_id != $params['uid']) {
                    runhook("Notify", "sendCustomMessage", ["messageType" => "subordinate_order_fulfillment", "uid" => $buyer_id, 'freezing_commission' => abs($params['commission']), "order_id" => $order_id, 'referee_id' => $params['uid']]);//下线付款
                }
                //平台账户佣金改变
                if ($account_count) {
                    $commission_data = array(
                        'commission' => $account_count['commission'] + abs($params['commission']),
                    );
                    $account->save($commission_data, ['website_id' => $params['website_id']]);
                }
            }
            if($params['status']==2){//订单退款完成，冻结佣金改变
                if($count){
                    $commission_data = array(
                        'freezing_commission' => $count['freezing_commission']-abs($params['commission']),
                    );
                    $account_statistics->save($commission_data,['uid'=> $params['uid']]);
                }
            }
            if($params['status']==3){//订单支付完成，冻结佣金改变
                //分销商佣金账户改变
                if($count){
                    $commission_data = array(
                        'freezing_commission' => $count['freezing_commission']+abs($params['commission']),
                    );
                    $account_statistics->save($commission_data,['uid'=> $params['uid']]);
                }else{
                    $commission_data = array(
                        'uid' => $params['uid'],
                        'website_id' => $params['website_id'],
                        'freezing_commission' => abs($params['commission']),
                    );
                    $account_statistics->save($commission_data);
                }

                if($buyer_id != $params['uid']){
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"subordinate_payment","uid" => $buyer_id,'freezing_commission' => abs($params['commission']),"order_id" => $order_id,'referee_id'=>$params['uid']]);//下线付款
                }
                //平台账户流水表
                $shop = new ShopAccount();
                $shop->addAccountRecords(0, $params['uid'], '订单支付完成佣金', $params['commission'], 5, $params['order_id'], '订单支付完成，账户佣金增加',$params['website_id']);
            }
            if($params['status']==1){
                //查询是否已经插入,有的话不再插入流水
                $checkRecord = $distributor_account->getInfo(['data_id'=>$params['order_id'], 'from_type' => 1,'uid' => $params['uid'],'website_id' => $params['website_id']]);
                if(!$checkRecord){
                    $data_records = array(//订单完成
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'data_id' => $params['order_id'],
                        'commission' => $params['commission'],
                        'balance'=>$count['commission'] + abs($params['commission']),
                        'from_type' => 1,
                        'website_id' => $params['website_id'],
                        'text' => '订单完成,冻结佣金减少,可提现佣金增加',
                        'create_time' => time(),
                    );
                }
                
            }
            if($params['status']==2){//订单退款
                $records_count = $distributor_account->getInfo(['data_id'=> $params['order_id']],'*');
                if($records_count){
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['order_id'],
                        'website_id' => $params['website_id'],
                        'commission' => $params['commission'],
                        'text' => '订单退款,冻结佣金减少',
                        'create_time' => time(),
                        'from_type' => 2,
                    );
                }
            }
            if($params['status']==3){//订单支付成功
                $data_records = array(
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'data_id' => $params['order_id'],
                    'balance'=>$count['commission'],
                    'website_id' => $params['website_id'],
                    'commission' => $params['commission'],
                    'text' => '订单支付,冻结佣金增加',
                    'create_time' => time(),
                    'from_type' => 3,
                );
            }
            if($params['status']==4){//佣金成功提现到账户余额
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到账户余额成功,可提现佣金减少',
                        'from_type' => 4,
                        'status'=>3
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*abs($params['tax']),
                        'text' => $params['text'],
                        'create_time' => time(),
                        'from_type' => 4,//佣金提现到账户余额成功
                    );
                }
            }
            if($params['status']==6){//佣金提现账户余额审核中
                $data_records = array(
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'data_id' => $params['data_id'],
                    'balance'=>$count['commission'],
                    'website_id' => $params['website_id'],
                    'commission' => (-1)*abs($params['cash']),
                    'tax' => (-1)*$params['tax'],
                    'text' =>'提现到余额待审核,可提现佣金减少,冻结佣金增加',
                    'create_time' => time(),
                    'from_type' => 6,
                    'status'=>1
                );
            }
            if($params['status']==15){//佣金提现账户余额待打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到余额待打款,可提现佣金减少,冻结佣金增加',
                        'from_type' => 15,
                        'status'=>2
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'balance'=>$count['commission'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'text' =>'提现到余额待打款,可提现佣金减少,冻结佣金增加',
                        'create_time' => time(),
                        'from_type' => 15,
                        'status'=>2
                    );
                }
            }
            if($params['status']==5){//佣金提现到微信待打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到微信待打款,可提现佣金减少,冻结佣金增加',
                        'from_type' => 5,
                        'status'=>2
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'website_id' => $params['website_id'],
                        'text' =>'提现到微信待打款,可提现佣金减少,冻结佣金增加',
                        'create_time' => time(),
                        'from_type' => 5,
                        'status'=>2
                    );
                }
            }
            if($params['status']==7){//佣金提现到支付宝待打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到支付宝待打款,可提现佣金减少,冻结佣金增加',
                        'status'=>2,
                        'from_type' => 7,
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'balance'=>$count['commission'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'text' =>'提现到支付宝待打款,可提现佣金减少,冻结佣金增加',
                        'create_time' => time(),
                        'from_type' => 7,
                        'status'=>2
                    );
                }
            }
            if($params['status']==8){//佣金提现到银行卡待打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到银行卡待打款,可提现佣金减少,冻结佣金增加',
                        'from_type' => 8,
                        'status'=>2
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'balance'=>$count['commission'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'text' =>'提现到银行卡待打款,可提现佣金减少,冻结佣金增加',
                        'create_time' => time(),
                        'from_type' => 8,
                        'status'=>2
                    );
                }
            }
            if($params['status']==9){//佣金成功提现到到银行卡
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到银行卡成功,可提现佣金减少',
                        'from_type' => 9,
                        'status'=>3
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'text' =>'提现到银行卡成功',
                        'create_time' => time(),
                        'from_type' => 9,
                    );
                }
            }
            if($params['status']==-9){//佣金提现到到银行卡失败
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到银行卡打款失败,等待商家重新打款',
                        'from_type' => -9,
                        'msg' =>$params['msg'],
                        'status'=>4
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'msg' =>$params['msg'],
                        'text' =>'提现到银行卡打款失败,等待商家重新打款',
                        'create_time' => time(),
                        'from_type' => -9,
                        'status'=>4
                    );
                }
            }
            if($params['status']==10){//佣金成功提现到到微信
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到微信成功,冻结佣金减少,已提现佣金增加',
                        'from_type' => 10,
                        'status'=>3
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'text' =>'提现到微信成功',
                        'create_time' => time(),
                        'from_type' => 10,
                    );
                }
            }
            if($params['status']==-10){//佣金提现到微信失败
                if($records_info){
                    $update_records = array(
                        'from_type' => -10,
                        'msg' =>$params['msg'],
                        'text' =>'提现到微信打款失败,等待商家重新打款',
                        'status'=>4
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'text' =>'提现到微信打款失败,等待商家重新打款',
                        'msg' =>$params['msg'],
                        'create_time' => time(),
                        'from_type' => -10,
                        'status'=>4
                    );
                }
            }
            if($params['status']==11){//佣金成功提现到支付宝
                if($records_info){
                    $update_records = array(
                        'from_type' => 11,
                        'text' =>'提现到支付宝成功,冻结佣金减少,已提现佣金增加',
                        'status'=>3
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'text' =>'提现到支付宝成功',
                        'create_time' => time(),
                        'from_type' => 11,
                    );
                }
            }
            if($params['status']==-11){//佣金提现到支付宝失败
                if($records_info){
                    $update_records = array(
                        'from_type' => -11,
                        'msg' =>$params['msg'],
                        'text' =>'提现到支付宝打款失败,等待商家重新打款',
                        'status'=>4
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'msg' =>$params['msg'],
                        'website_id' => $params['website_id'],
                        'commission' => (-1)*abs($params['cash']),
                        'tax' => (-1)*$params['tax'],
                        'text' =>'提现到支付宝打款失败,等待商家重新打款',
                        'create_time' => time(),
                        'from_type' => -11,
                        'status'=>4
                    );
                }
            }
            if($params['status']==12){//佣金提现到银行卡审核中
                $data_records = array(
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'balance'=>$count['commission'],
                    'data_id' => $params['data_id'],
                    'website_id' => $params['website_id'],
                    'commission' => (-1)*abs($params['cash']),
                    'tax' => (-1)*$params['tax'],
                    'text' =>'提现到银行卡审核中,可提现佣金减少,冻结佣金增加',
                    'create_time' => time(),
                    'from_type' => 12,
                    'status'=>1
                );
            }
            if($params['status']==13){//佣金提现到微信审核中
                $data_records = array(
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'balance'=>$count['commission'],
                    'data_id' => $params['data_id'],
                    'website_id' => $params['website_id'],
                    'commission' => (-1)*abs($params['cash']),
                    'tax' => (-1)*$params['tax'],
                    'text' =>'提现到微信审核中,可提现佣金减少,冻结佣金增加',
                    'create_time' => time(),
                    'from_type' => 13,
                    'status'=>1
                );
            }
            if($params['status']==14){//佣金提现到支付宝审核中
                $data_records = array(
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'balance'=>$count['commission'],
                    'data_id' => $params['data_id'],
                    'website_id' => $params['website_id'],
                    'commission' => (-1)*abs($params['cash']),
                    'tax' => (-1)*$params['tax'],
                    'text' =>'提现到支付宝审核中,可提现佣金减少,冻结佣金增加',
                    'create_time' => time(),
                    'from_type' => 14,
                    'status'=>1
                );
            }
            if($params['status']==16){//平台拒绝微信打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到微信平台拒绝,冻结佣金减少,可提现佣金增加',
                        'from_type' => 16,
                        'msg'=>$params['msg'],
                        'status'=>5
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['commission'],
                        'msg'=>$params['msg'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => abs($params['cash']),
                        'tax' => $params['tax'],
                        'text' =>'提现到微信平台拒绝,冻结佣金减少,可提现佣金增加',
                        'create_time' => time(),
                        'from_type' => 16,
                        'status'=>5,
                    );
                }
            }
            if($params['status']==19){//佣金提现到微信审核不通过
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到微信审核不通过,冻结佣金减少,可提现佣金增加',
                        'from_type' => 19,
                        'msg'=>$params['msg'],
                        'status'=>-1
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'msg'=>$params['msg'],
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => abs($params['cash']),
                        'tax' => $params['tax'],
                        'text' =>'提现到微信审核不通过,冻结佣金减少,可提现佣金增加',
                        'create_time' => time(),
                        'from_type' => 19,
                        'status'=>-1,
                    );
                }
            }
            if($params['status']==24){//佣金提现到银行卡不通过
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到银行卡审核不通过,冻结佣金减少,可提现佣金增加',
                        'from_type' => 24,
                        'msg'=>$params['msg'],
                        'status'=>-1
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'msg'=>$params['msg'],
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => abs($params['cash']),
                        'tax' => $params['tax'],
                        'text' =>'提现到银行卡审核不通过,冻结佣金减少,可提现佣金增加',
                        'create_time' => time(),
                        'from_type' => 24,
                        'status'=>5,
                    );
                }
            }
            if($params['status']==23){//平台拒绝银行卡打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到银行卡平台拒绝,冻结佣金减少,可提现佣金增加',
                        'from_type' => 23,
                        'msg'=>$params['msg'],
                        'status'=>5
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'msg'=>$params['msg'],
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => abs($params['cash']),
                        'tax' => $params['tax'],
                        'text' =>'提现到银行卡平台拒绝,冻结佣金减少,可提现佣金增加',
                        'create_time' => time(),
                        'from_type' => 23,
                        'status'=>5,
                    );
                }
            }
            if($params['status']==17){//平台拒绝支付宝打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到支付宝平台拒绝,冻结佣金减少,可提现佣金增加',
                        'from_type' => 17,
                        'msg'=>$params['msg'],
                        'status'=>5
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'msg'=>$params['msg'],
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => abs($params['cash']),
                        'tax' => $params['tax'],
                        'text' =>'提现到支付宝平台拒绝,冻结佣金减少,可提现佣金增加',
                        'create_time' => time(),
                        'from_type' => 17,
                        'status'=>5,
                    );
                }
            }
            if($params['status']==18){//平台拒绝账户余额打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到账户余额平台拒绝,冻结佣金减少,可提现佣金增加',
                        'from_type' => 18,
                        'msg'=>$params['msg'],
                        'status'=>5
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => abs($params['cash']),
                        'tax' => $params['tax'],
                        'msg'=>$params['msg'],
                        'text' =>'提现到账户余额平台拒绝,冻结佣金减少,可提现佣金增加',
                        'create_time' => time(),
                        'from_type' => 18,
                        'status'=>5,
                    );
                }
            }
            if($params['status']==20){//佣金提现到支付宝审核不通过
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到支付宝审核不通过,冻结佣金减少,可提现佣金增加',
                        'from_type' => 20,
                        'msg'=>$params['msg'],
                        'status'=>-1
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'balance'=>$count['commission'],
                        'website_id' => $params['website_id'],
                        'commission' => abs($params['cash']),
                        'tax' => $params['tax'],
                        'msg'=>$params['msg'],
                        'text' =>'提现到支付宝审核不通过,冻结佣金减少,可提现佣金增加',
                        'create_time' => time(),
                        'from_type' => 20,
                        'status'=>-1,
                    );
                }
            }
            if($params['status']==21){//佣金提现到账户余额审核不通过
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到账户余额审核不通过,冻结佣金减少,可提现佣金增加',
                        'from_type' => 21,
                        'msg'=>$params['msg'],
                        'status'=>-1
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['commission'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'commission' => abs($params['cash']),
                        'tax' => $params['tax'],
                        'msg'=>$params['msg'],
                        'text' =>'提现到账户余额审核不通过,冻结佣金减少,可提现佣金增加',
                        'create_time' => time(),
                        'from_type' => 21,
                        'status'=>-1,
                    );
                }
            }
            if($data_records){
                $data_records['shop_id'] = $shop_id;
                $distributor_account->save($data_records);
            }
            if($update_records){
                $distributor_account->save($update_records,['data_id'=>$params['data_id']]);
            }
            $distributor_account->commit();
            return 1;
        } catch (\Exception $e)
        {
            $distributor_account->rollback();
            return $e->getMessage();
        }
    }

    /*
     * 订单完成和修改推荐人后分销商等级升级
     */
    public function updateDistributorLevelInfo($uid)
    {
        $member = new VslMemberModel();
        $level = new DistributorLevelModel();
        $config = new AddonsConfigService();
        $distributor = $member->getInfo(['uid'=>$uid],'*');
        $default_level_name =  $level->getInfo(['id'=>$distributor['distributor_level_id']],'level_name')['level_name'];
        $info = $config->getAddonsConfig("distribution",$distributor['website_id']);//基本设置
        $base_info = json_decode($info['value'], true);
        $distribution_pattern = $base_info['distribution_pattern'];
        $order = new VslOrderModel();
        $order_goods = new VslOrderGoodsModel();
        if($base_info['distributor_grade']==1){//开启跳级
            if($distributor['isdistributor']==2){
                $getDistributorInfo = $this->getDistributorLowerInfo($uid);//当前分销商的详情信息
                $level_weight = $level->Query(['id'=>$distributor['distributor_level_id']],'weight');//当前分销商的等级权重
                $level_weights = $level->Query(['weight'=>['>',implode(',',$level_weight)],'website_id' => $distributor['website_id']],'weight');//当前分销商的等级权重的上级权重
                if ($level_weights) {
                        sort($level_weights);
                        foreach ($level_weights as $k => $v) {
                            $ratio = '';
                            $level_infos = $level->getInfo(['weight' => $v,'website_id' => $distributor['website_id']]);//当前等级信息
                            if($level_infos && $level_infos['upgrade_level']){
                                $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$distributor['website_id']]);//该等级指定推荐等级人数
                            }else{
                                $low_number = 0;
                            }
                            //判断是否购买过指定商品
                            $goods_info = [];
                            if ($level_infos['goods_id']) {
                                $goods_id = $order_goods->Query(['goods_id' => $level_infos['goods_id'], 'buyer_id' => $uid], 'order_id');
                                if ($goods_id) {
                                    $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4], '*');
                                }
                            }
                            if ($level_infos['upgradetype'] == 1) {//是否开启自动升级
                                $conditions = explode(',', $level_infos['upgradeconditions']);
                                $result = [];
                                foreach ($conditions as $k1 => $v1) {
                                    switch ($v1) {
                                        case 7:
                                            if ($getDistributorInfo['number1'] >= $level_infos['number1']) {
                                                $result[] = 7;//一级分销商
                                            }
                                            break;
                                        case 8:
                                            if ($getDistributorInfo['number2'] >= $level_infos['number2']) {
                                                $result[] = 8;//二级分销商
                                            }
                                            break;
                                        case 9:
                                            if ($getDistributorInfo['number3'] >= $level_infos['number3']) {
                                                $result[] = 9;//三级分销商
                                            }
                                            break;
                                        case 10:
                                            if ($getDistributorInfo['agentcount2'] >= $level_infos['number4']) {
                                                $result[] = 10;//团队人数
                                            }
                                            break;
                                        case 11:
                                            if ($getDistributorInfo['agentcount1'] >= $level_infos['number5']) {
                                                $result[] = 11;//客户人数
                                            }
                                            break;
                                        case 12:
                                            if ($low_number >= $level_infos['level_number']) {
                                                $result[] = 12;//指定等级人数
                                            }
                                            break;
                                        case 1:
                                            $offline_number = $level_infos['offline_number'];
                                            if ($getDistributorInfo['agentcount'] >= $offline_number) {
                                                $result[] = 1;//下线总人数
                                            }
                                            break;
                                        case 2:
                                            $order_money = $level_infos['order_money'];
                                            if ($getDistributorInfo['order_money'] >= $order_money) {
                                                $result[] = 2;//分销订单金额达
                                            }
                                            break;
                                        case 3:
                                            $order_number = $level_infos['order_number'];
                                            if ($getDistributorInfo['agentordercount'] >= $order_number) {
                                                $result[] = 3;//分销订单数达
                                            }
                                            break;

                                        case 4:
                                            $selforder_money = $level_infos['selforder_money'];
                                            if ($getDistributorInfo['selforder_money'] >= $selforder_money) {
                                                $result[] = 4;//自购订单金额
                                            }
                                            break;
                                        case 5:
                                            $selforder_number = $level_infos['selforder_number'];
                                            if ($getDistributorInfo['selforder_number'] >= $selforder_number) {
                                                $result[] = 5;//自购订单数
                                            }
                                            break;
                                        case 6:
                                            if ($goods_info) {
                                                $result[] = 6;//指定商品
                                            }
                                            break;
                                    }
                                }
                                if ($level_infos['upgrade_condition'] == 1) {//升级条件类型（满足所有勾选条件）
                                    if (count($result) == count($conditions)) {
                                        $member = new VslMemberModel();
                                        $member->save(['distributor_level_id' => $level_infos['id'], 'up_level_time' => time(), 'down_up_level_time' => ''], ['uid' => $uid]);
                                        if($distribution_pattern>=1){
                                            $ratio .= '一级返佣比例'.$level_infos['commission1'].'%';
                                        }
                                        if($distribution_pattern>=2){
                                            $ratio .= ',二级返佣比例'.$level_infos['commission2'].'%';
                                        }
                                        if($distribution_pattern>=3){
                                            $ratio .= ',三级返佣比例'.$level_infos['commission3'].'%';
                                        }
                                        runhook("Notify", "sendCustomMessage", ['messageType'=>'upgrade_notice','uid' => $uid,'present_grade'=>$level_infos['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'upgrade_time' => time()]);//升级
                                        if($distributor['referee_id']){
                                            if(getAddons('globalbonus', $this->website_id)){
                                                $global = new GlobalBonus();
                                                $global->updateAgentLevelInfo($distributor['referee_id']);
                                            }
                                            if(getAddons('areabonus', $this->website_id)){
                                                $area = new AreaBonus();
                                                $area->updateAgentLevelInfo($distributor['referee_id']);
                                            }
                                            if(getAddons('teambonus', $this->website_id)){
                                                $team = new TeamBonus();
                                                $team->updateAgentLevelInfo($distributor['referee_id']);
                                            }
                                            $this->updateDistributorLevelInfo($distributor['referee_id']);
                                        }
                                    }
                                }
                                if ($level_infos['upgrade_condition'] == 2) {//升级条件类型（满足勾选条件任意一个即可）
                                    if (count($result) >= 1) {
                                        $member = new VslMemberModel();
                                        $member->save(['distributor_level_id' => $level_infos['id'], 'up_level_time' => time(), 'down_up_level_time' => ''], ['uid' => $uid]);
                                        if($distribution_pattern>=1){
                                            $ratio .= '一级返佣比例'.$level_infos['commission1'].'%';
                                        }
                                        if($distribution_pattern>=2){
                                            $ratio .= ',二级返佣比例'.$level_infos['commission2'].'%';
                                        }
                                        if($distribution_pattern>=3){
                                            $ratio .= ',三级返佣比例'.$level_infos['commission3'].'%';
                                        }
                                        runhook("Notify", "sendCustomMessage", ['messageType'=>'upgrade_notice','uid' => $uid,'present_grade'=>$level_infos['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'upgrade_time' => time()]);//升级
                                        if($distributor['referee_id']){
                                            if(getAddons('globalbonus', $this->website_id)){
                                                $global = new GlobalBonus();
                                                $global->updateAgentLevelInfo($distributor['referee_id']);
                                            }
                                            if(getAddons('areabonus', $this->website_id)){
                                                $area = new AreaBonus();
                                                $area->updateAgentLevelInfo($distributor['referee_id']);
                                            }
                                            if(getAddons('teambonus', $this->website_id)){
                                                $team = new TeamBonus();
                                                $team->updateAgentLevelInfo($distributor['referee_id']);
                                            }
                                            $this->updateDistributorLevelInfo($distributor['referee_id']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $member_info = $member->getInfo(['uid'=>$uid],'*');
                    if($distributor['distributor_level_id'] != $member_info['distributor_level_id']){
                        if($base_info['distribution_pattern']>=1){
                            if($member_info['referee_id']){
                                $recommend1_info = $member->getInfo(['uid'=>$member_info['referee_id']],'*');
                                if($recommend1_info && $recommend1_info['isdistributor']==2){
                                    $level_infos = $level->getInfo(['id' => $recommend1_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                                    $recommend1 = $level_infos['recommend1'];//一级推荐奖
                                    $recommend_point1 = $level_infos['recommend_point1'];//一级推荐积分
                                    $this->addRecommed($uid,$recommend1_info['uid'],$recommend1,$recommend_point1,$member_info['website_id']);
                                }
                            }
                        }
                        if($base_info['distribution_pattern']>=2){
                            $recommend2_info = $member->getInfo(['uid'=>$recommend1_info['referee_id']],'*');
                            if($recommend2_info && $recommend2_info['isdistributor']==2) {
                                $level_infos = $level->getInfo(['id' => $recommend2_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                                $recommend2 = $level_infos['recommend2'];//二级推荐奖
                                $recommend_point2 = $level_infos['recommend_point2'];//二级推荐积分
                                $this->addRecommed($uid,$recommend2_info['uid'],$recommend2,$recommend_point2,$member_info['website_id']);
                            }
                        }
                        if($base_info['distribution_pattern']>=3){
                            $recommend3_info = $member->getInfo(['uid'=>$recommend2_info['referee_id']],'*');
                            if($recommend3_info && $recommend3_info['isdistributor']==2) {
                                $level_infos = $level->getInfo(['id' => $recommend3_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                                $recommend3 = $level_infos['recommend3'];//三级推荐奖
                                $recommend_point3 = $level_infos['recommend_point3'];//三级推荐积分
                                $this->addRecommed($uid,$recommend3_info['uid'],$recommend3,$recommend_point3,$member_info['website_id']);
                            }
                        }
                    }
                }
        }
        if($base_info['distributor_grade']==2){//未开启跳级
            if($distributor['isdistributor']==2){
                $getDistributorInfo = $this->getDistributorLowerInfo($uid);//当前分销商的详情信息
                $level_weight = $level->Query(['id'=>$distributor['distributor_level_id']],'weight');//当前分销商的等级权重
                $level_weights = $level->Query(['weight'=>['>',implode(',',$level_weight)],'website_id' => $distributor['website_id']],'weight');//当前分销商的等级权重的上级权重
                if ($level_weights) {
                    sort($level_weights);
                    //为防止跳级 1次终止
                    
                    foreach ($level_weights as $k => $v) {
                        if($k > 0){
                            break;
                        }
                        $ratio = '';
                        $level_infos = $level->getInfo(['weight' => $v,'website_id' => $distributor['website_id']]);//当前等级信息
                        
                        if($level_infos && $level_infos['upgrade_level']){
                            $low_number = $member->getCount(['distributor_level_id'=>$level_infos['upgrade_level'],'referee_id'=>$uid,'website_id'=>$distributor['website_id']]);//该等级指定推荐等级人数
                        }else{
                            $low_number = 0;
                        }
                        //判断是否购买过指定商品
                        $goods_info = [];
                        if ($level_infos['goods_id']) {
                            $goods_id = $order_goods->Query(['goods_id' => $level_infos['goods_id'], 'buyer_id' => $uid], 'order_id');
                            if ($goods_id) {
                                $goods_info = $order->getInfo(['order_id' => ['IN',implode(',',$goods_id)], 'order_status' => 4], '*');
                            }
                        }
                        if ($level_infos['upgradetype'] == 1) {//是否开启自动升级
                            $conditions = explode(',', $level_infos['upgradeconditions']);
                            $result = [];
                            foreach ($conditions as $k1 => $v1) {
                                switch ($v1) {
                                    case 7:
                                        if ($getDistributorInfo['number1'] >= $level_infos['number1']) {
                                            $result[] = 7;//一级分销商
                                        }
                                        break;
                                    case 8:
                                        if ($getDistributorInfo['number2'] >= $level_infos['number2']) {
                                            $result[] = 8;//二级分销商
                                        }
                                        break;
                                    case 9:
                                        if ($getDistributorInfo['number3'] >= $level_infos['number3']) {
                                            $result[] = 9;//三级分销商
                                        }
                                        break;
                                    case 10:
                                        if ($getDistributorInfo['agentcount2'] >= $level_infos['number4']) {
                                            $result[] = 10;//团队人数
                                        }
                                        break;
                                    case 11:
                                        if ($getDistributorInfo['agentcount1'] >= $level_infos['number5']) {
                                            $result[] = 11;//客户人数
                                        }
                                        break;
                                    case 12:
                                        if ($low_number >= $level_infos['level_number']) {
                                            $result[] = 12;//指定等级人数
                                        }
                                        break;
                                    case 1:
                                        $offline_number = $level_infos['offline_number'];
                                        if ($getDistributorInfo['agentcount'] >= $offline_number) {
                                            $result[] = 1;//下线总人数
                                        }
                                        break;
                                    case 2:
                                        $order_money = $level_infos['order_money'];
                                        if ($getDistributorInfo['order_money'] >= $order_money) {
                                            $result[] = 2;//分销订单金额达
                                        }
                                        break;
                                    case 3:
                                        $order_number = $level_infos['order_number'];
                                        if ($getDistributorInfo['agentordercount'] >= $order_number) {
                                            $result[] = 3;//分销订单数达
                                        }
                                        break;

                                    case 4:
                                        $selforder_money = $level_infos['selforder_money'];
                                        if ($getDistributorInfo['selforder_money'] >= $selforder_money) {
                                            $result[] = 4;//自购订单金额
                                        }
                                        break;
                                    case 5:
                                        $selforder_number = $level_infos['selforder_number'];
                                        if ($getDistributorInfo['selforder_number'] >= $selforder_number) {
                                            $result[] = 5;//自购订单数
                                        }
                                        break;
                                    case 6:
                                        if ($goods_info) {
                                            $result[] = 6;//指定商品
                                        }
                                        break;
                                }
                            }
                            
                            if ($level_infos['upgrade_condition'] == 1) {//升级条件类型（满足所有勾选条件）
                                if (count($result) == count($conditions)) {
                                    $member = new VslMemberModel();
                                    $member->save(['distributor_level_id' => $level_infos['id'], 'up_level_time' => time(), 'down_up_level_time' => ''], ['uid' => $uid]);
                                    if($distribution_pattern>=1){
                                        $ratio .= '一级返佣比例'.$level_infos['commission1'].'%';
                                    }
                                    if($distribution_pattern>=2){
                                        $ratio .= ',二级返佣比例'.$level_infos['commission2'].'%';
                                    }
                                    if($distribution_pattern>=3){
                                        $ratio .= ',三级返佣比例'.$level_infos['commission3'].'%';
                                    }
                                    runhook("Notify", "sendCustomMessage", ['messageType'=>'upgrade_notice','uid' => $uid,'present_grade'=>$level_infos['level_name'],'ratio'=>$ratio,'primary_grade'=>$default_level_name,'upgrade_time' => time()]);//升级
                                    if($distributor['referee_id']){
                                        if(getAddons('globalbonus', $this->website_id)){
                                            $global = new GlobalBonus();
                                            $global->updateAgentLevelInfo($distributor['referee_id']);
                                        }
                                        if(getAddons('areabonus', $this->website_id)){
                                            $area = new AreaBonus();
                                            $area->updateAgentLevelInfo($distributor['referee_id']);
                                        }
                                        if(getAddons('teambonus', $this->website_id)){
                                            $team = new TeamBonus();
                                            $team->updateAgentLevelInfo($distributor['referee_id']);
                                        }
                                        $this->updateDistributorLevelInfo($distributor['referee_id']);
                                    }
                                    break;
                                }
                            }
                            if ($level_infos['upgrade_condition'] == 2) {//升级条件类型（满足勾选条件任意一个即可）
                                if (count($result) >= 1) {
                                    $member = new VslMemberModel();
                                    $member->save(['distributor_level_id' => $level_infos['id'], 'up_level_time' => time(), 'down_up_level_time' => ''], ['uid' => $uid]);
                                    if($distribution_pattern>=1){
                                        $ratio .= '一级返佣比例'.$level_infos['commission1'].'%';
                                    }
                                    if($distribution_pattern>=2){
                                        $ratio .= ',二级返佣比例'.$level_infos['commission2'].'%';
                                    }
                                    if($distribution_pattern>=3){
                                        $ratio .= ',三级返佣比例'.$level_infos['commission3'].'%';
                                    }
                                    runhook("Notify", "sendCustomMessage", ['messageType'=>'upgrade_notice','uid' => $uid,'present_grade'=>$level_infos['level_name'],'ratio'=>$ratio,'primary_grade'=>$default_level_name,'upgrade_time' => time()]);//升级
                                    if($distributor['referee_id']){
                                        if(getAddons('globalbonus', $this->website_id)){
                                            $global = new GlobalBonus();
                                            $global->updateAgentLevelInfo($distributor['referee_id']);
                                        }
                                        if(getAddons('areabonus', $this->website_id)){
                                            $area = new AreaBonus();
                                            $area->updateAgentLevelInfo($distributor['referee_id']);
                                        }
                                        if(getAddons('teambonus', $this->website_id)){
                                            $team = new TeamBonus();
                                            $team->updateAgentLevelInfo($distributor['referee_id']);
                                        }
                                        $this->updateDistributorLevelInfo($distributor['referee_id']);
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
                $member_info = $member->getInfo(['uid'=>$uid],'*');
                if($distributor['distributor_level_id'] != $member_info['distributor_level_id']){
                    if($base_info['distribution_pattern']>=1){
                        if($member_info['referee_id']){
                            $recommend1_info = $member->getInfo(['uid'=>$member_info['referee_id']],'*');
                            if($recommend1_info['isdistributor']==2){
                                $level_infos = $level->getInfo(['id' => $recommend1_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                                $recommend1 = $level_infos['recommend1'];//一级推荐奖
                                $recommend_point1 = $level_infos['recommend_point1'];//一级推荐积分
                                $this->addRecommed($uid,$recommend1_info['uid'],$recommend1,$recommend_point1,$member_info['website_id']);
                            }
                        }
                    }
                    if($base_info['distribution_pattern']>=2){
                        $recommend2_info = $member->getInfo(['uid'=>$recommend1_info['referee_id']],'*');
                        if($recommend2_info['isdistributor']==2) {
                            $level_infos = $level->getInfo(['id' => $recommend2_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                            $recommend2 = $level_infos['recommend2'];//二级推荐奖
                            $recommend_point2 = $level_infos['recommend_point2'];//二级推荐积分
                            $this->addRecommed($uid,$recommend2_info['uid'],$recommend2,$recommend_point2,$member_info['website_id']);
                        }
                    }
                    if($base_info['distribution_pattern']>=3){
                        $recommend3_info = $member->getInfo(['uid'=>$recommend2_info['referee_id']],'*');
                        if($recommend3_info['isdistributor']==2) {
                            $level_infos = $level->getInfo(['id' => $recommend3_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                            $recommend3 = $level_infos['recommend3'];//三级推荐奖
                            $recommend_point3 = $level_infos['recommend_point3'];//三级推荐积分
                            $this->addRecommed($uid,$recommend3_info['uid'],$recommend3,$recommend_point3,$member_info['website_id']);
                        }
                    }
                }
            }
        }
    }
    /*
    * 订单完成和修改推荐人后分销商等级升级后获得推荐奖
    */
    public function addRecommed($uid,$recommend_uid,$recommend,$point,$website_id){
        $account = new VslAccountModel();
        $member = new VslMemberAccountModel();
        $account_statistics = new VslDistributorAccountModel();
        $distributor_account = new VslDistributorAccountRecordsModel();
        //更新对应佣金账户和平台账户和会员账户
        $point_count = $member->getInfo(['uid'=> $recommend_uid],'*');//会员账户
        $count = $account_statistics->getInfo(['uid'=> $recommend_uid],'*');//佣金账户
        $account_count = $account_statistics->getInfo(['website_id'=> $website_id],'*');//平台账户
        $member_point = new VslMemberAccountRecordsModel();
        //会员账户积分改变
        if ($point_count) {
            $account_data1 = array(
                'point' => $point_count['point'] + $point,
                'member_sum_point' => $point_count['member_sum_point'] + $point
            );
            $member->save($account_data1, ['uid' => $recommend_uid]);
        }else{
            $account_data2 = array(
                'point' => $point_count['point'] + $point,
                'member_sum_point' => $point_count['member_sum_point'] + $point,
                'uid' => $recommend_uid
            );
            $member->save($account_data2);
        }
        $data_point = array(
            'records_no' => getSerialNo(),
            'uid' => $recommend_uid,
            'account_type' => 1,
            'number'   => $point,
            'data_id' => $uid,
            'from_type' => 29,
            'text' => '下级分销商等级升级，推荐人获得推荐奖积分增加',
            'create_time' => time(),
            'website_id' => $website_id
        );
        $member_point->save($data_point);//添加会员积分流水
        //佣金账户佣金改变
        if ($count) {
            $account_data = array(
                'commission' => $count['commission'] + $recommend
            );
            $account_statistics->save($account_data, ['uid' => $recommend_uid]);
        }else{
            $account_data = array(
                'commission' => $count['commission'] + $recommend,
                'uid' => $recommend_uid
            );
            $account_statistics->save($account_data);
        }
        $records_no = 'LR'.time() . rand(111, 999);
        $data_records = array(
            'uid' => $recommend_uid,
            'records_no'=> $records_no,
            'data_id' => $uid,
            'website_id' => $website_id,
            'commission' => $recommend,
            'text' =>'下级分销商等级升级，推荐人获得推荐奖佣金增加',
            'create_time' => time(),
            'from_type' => 22,
        );
        $distributor_account->save($data_records);
        //平台账户佣金改变
        if ($account_count) {
            $commission_data = array(
                'commission' => $account_count['commission'] + $recommend,
            );
            $account->save($commission_data, ['website_id' => $website_id]);
            //平台账户流水表
            $shop = new ShopAccount();
            $shop->addAccountRecords(0,$recommend_uid, '会员分销等级升级，推荐奖', $recommend, 5, $uid, '会员分销等级升级，推荐奖，账户佣金增加',$website_id);
        }
    }
    /*
   * 订单完成获得积分
   */
    public function addMemberPoint($point,$uid,$data_id,$website_id){
        $member = new VslMemberAccountModel();
        $point_count = $member->getInfo(['uid'=>$uid],'*');//会员账户
        $member_point = new VslMemberAccountRecordsModel();
        //会员账户积分改变
        if ($point_count) {
            $account_data1 = array(
                'point' => $point_count['point'] + $point,
                'member_sum_point' => $point_count['member_sum_point'] + $point
            );
            $member->save($account_data1, ['uid' => $uid]);
        }else{
            $account_data2 = array(
                'point' => $point_count['point'] + $point,
                'member_sum_point' => $point_count['member_sum_point'] + $point,
                'uid' => $uid
            );
            $member->save($account_data2);
        }
        $data_point = array(
            'records_no' => getSerialNo(),
            'uid' => $uid,
            'account_type' => 1,
            'number'   => $point,
            'data_id' => $data_id,
            'from_type' => 30,
            'text' => '分销订单完成，积分增加',
            'create_time' => time(),
            'website_id' => $website_id
        );
        $member_point->save($data_point);//添加会员积分流水
    }
    /*
     * 分销商自动降级
     */
    public function autoDownDistributorLevel($website_id){
        $config = new AddonsConfigService();
        $level = new DistributorLevelModel();
        $info = $config->getAddonsConfig('distribution',$website_id);
        $base_info = json_decode($info['value'], true);
        $distribution_pattern = $base_info['distribution_pattern'];
        $member = new VslMemberModel();
        $distributors = $member->Query(['website_id'=>$website_id,'isdistributor'=>2],'*');
        $default_weight = $level->getInfo(['website_id'=>$website_id,'is_default'=>1],'weight')['weight'];//默认等级权重，也是最低等级
        foreach ($distributors as $k=>$v){
            $level_info =$level->getInfo(['id'=>$v['distributor_level_id']],'weight,level_name');
            $default_level_name = $level_info['level_name'];
            $level_weight =  $level_info['weight'];//分销商的等级权重
            
            if($level_weight>$default_weight){
                if($base_info['distributor_grade']==1){//开启跳降级
                        $level_weights = $level->Query(['weight'=>['<=',$level_weight],'website_id'=>$website_id],'weight');//分销商的等级权重的下级权重
                        rsort($level_weights);
                        foreach ($level_weights as $k1=>$v1){
                            $level_infos = $level->getInfo(['weight'=>$v1,'website_id'=>$website_id],'*');
                            $level_info_desc = $level->getFirstData(['weight'=>['<',$v1],'website_id'=>$website_id],'weight desc');//比当前等级的权重低的等级信息
                            if($v1!=$default_weight){
                                if($level_infos['downgradetype']==1 && $level_infos['downgradeconditions']){//是否开启自动降级并且有降级条件
                                    $conditions = explode(',',$level_infos['downgradeconditions']);
                                    $members_info = $member->getInfo(['uid'=>$v['uid']],'up_level_time,referee_id');
                                    $result = [];
                                    $reason = '';
                                    $ratio = '';
                                    foreach ($conditions as $k2=>$v2){
                                        switch ($v2){
                                            case 1:
                                                $team_number_day = $level_infos['team_number_day'];
                                                $real_level_time = $members_info['up_level_time']+$team_number_day*24*3600;
                                                if($real_level_time<=time()){
                                                    $getDistributorInfo1 = $this->getDistributorInfos($v['uid'],$team_number_day);
                                                    $limit_number =  $getDistributorInfo1['agentordercount'];//限制时间段内团队分销订单数
                                                    if($limit_number <=$level_infos['team_number']){
                                                        $result[] = 1;
                                                        $reason .= '团队分销订单数小于'.$level_infos['team_number'];
                                                    }
                                                }
                                                break;
                                            case 2:
                                                $team_money_day = $level_infos['team_money_day'];
                                                $real_level_time = $members_info['up_level_time']+$team_money_day*24*3600;
                                                if($real_level_time<=time()){
                                                    $getDistributorInfo2 = $this->getDistributorInfos($v['uid'],$team_money_day);
                                                    $limit_money1 =  $getDistributorInfo2['order_money'];//限制时间段内团队分销订单金额
                                                    if($limit_money1 <=$level_infos['team_money']){
                                                        $result[] = 2;
                                                        $reason .= ',团队分销订单金额小于'.$level_infos['team_money'];
                                                    }
                                                }
                                                break;
                                            case 3:
                                                $self_money_day = $level_infos['self_money_day'];
                                                $real_level_time = $members_info['up_level_time']+$self_money_day*24*3600;
                                                if($real_level_time<=time()){
                                                    $getDistributorInfo3 = $this->getDistributorInfos($v['uid'],$self_money_day);
                                                    $limit_money2 = $getDistributorInfo3['selforder_money'];//限制时间段内自购分销订单金额
                                                    if($limit_money2 <=$level_infos['self_money']){
                                                        $result[] = 3;
                                                        $reason .= ',自购分销订单金额小于'.$level_infos['self_money'];
                                                    }
                                                }
                                                break;
                                        }
                                    }
                                    if($level_infos['downgrade_condition']==1){//降级条件类型（满足所有勾选条件）
                                        if(count($result)==count($conditions)){
                                            $member = new VslMemberModel();
                                            if($website_id == 54){
                                                debugLog($v['uid'], '==>分销商自动降级满足所有勾选条件-跳级<==');
                                            }
                                            $member->save(['distributor_level_id'=>$level_info_desc['id'],'down_level_time'=>time(),'down_up_level_time'=>time()],['uid'=>$v['uid']]);
                                            if($distribution_pattern>=1){
                                                $ratio .= '一级返佣比例'.$level_info_desc['commission1'].'%';
                                            }
                                            if($distribution_pattern>=2){
                                                $ratio .= ',二级返佣比例'.$level_info_desc['commission2'].'%';
                                            }
                                            if($distribution_pattern>=3){
                                                $ratio .= ',三级返佣比例'.$level_info_desc['commission3'].'%';
                                            }
                                            runhook("Notify", "sendCustomMessage", ['messageType'=>'down_notice','uid' => $v['uid'],'present_grade'=>$level_info_desc['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'down_reason'=>$reason,'down_time' => time()]);//降级
                                            if(getAddons('globalbonus', $this->website_id)){
                                                $global = new GlobalBonus();
                                                $global->updateAgentLevelInfo($members_info['referee_id']);
                                            }
                                            if(getAddons('areabonus', $this->website_id)){
                                                $area = new AreaBonus();
                                                $area->updateAgentLevelInfo($members_info['referee_id']);
                                            }
                                            if(getAddons('teambonus', $this->website_id)){
                                                $team = new TeamBonus();
                                                $team->updateAgentLevelInfo($members_info['referee_id']);
                                            }
                                            $this->updateDistributorLevelInfo($members_info['referee_id']);
                                        }
                                    }
                                    if($level_infos['downgrade_condition']==2){//降级条件类型（满足勾选条件任意一个即可）
                                        if(count($result)>=1){
                                            $member = new VslMemberModel();
                                            if($website_id == 54){
                                                debugLog($v['uid'], '==>分销商自动降级满足勾选条件任意一个即可-跳级<==');
                                            }
                                            $member->save(['distributor_level_id'=>$level_info_desc['id'],'down_level_time'=>time(),'down_up_level_time'=>time()],['uid'=>$v['uid']]);
                                            if($distribution_pattern>=1){
                                                $ratio .= '一级返佣比例'.$level_info_desc['commission1'].'%';
                                            }
                                            if($distribution_pattern>=2){
                                                $ratio .= ',二级返佣比例'.$level_info_desc['commission2'].'%';
                                            }
                                            if($distribution_pattern>=3){
                                                $ratio .= ',三级返佣比例'.$level_info_desc['commission3'].'%';
                                            }
                                            runhook("Notify", "sendCustomMessage", ['messageType'=>'down_notice','uid' => $v['uid'],'present_grade'=>$level_info_desc['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'down_reason'=>$reason,'down_time' => time()]);//降级
                                            if(getAddons('globalbonus', $this->website_id)){
                                                $global = new GlobalBonus();
                                                $global->updateAgentLevelInfo($members_info['referee_id']);
                                            }
                                            if(getAddons('areabonus', $this->website_id)){
                                                $area = new AreaBonus();
                                                $area->updateAgentLevelInfo($members_info['referee_id']);
                                            }
                                            if(getAddons('teambonus', $this->website_id)){
                                                $team = new TeamBonus();
                                                $team->updateAgentLevelInfo($members_info['referee_id']);
                                            }
                                            $this->updateDistributorLevelInfo($members_info['referee_id']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                if($base_info['distributor_grade']==2){//未开启跳降级
                    $level_weights = $level->Query(['weight'=>['<=',$level_weight],'website_id'=>$website_id],'weight');//分销商的等级权重的下级权重
                    rsort($level_weights);
                    foreach ($level_weights as $k1=>$v1){
                        if($k1 > 0){
                            break;
                        }
                        $level_infos = $level->getInfo(['weight'=>$v1,'website_id'=>$website_id],'*');
                        $level_info_desc = $level->getFirstData(['weight'=>['<',$v1],'website_id'=>$website_id],'weight desc');//比当前等级的权重低的等级信息
                        if($v1!=$default_weight){
                            if($level_infos['downgradetype']==1 && $level_infos['downgradeconditions']){//是否开启自动降级并且有降级条件
                                $conditions = explode(',',$level_infos['downgradeconditions']);
                                $members_info = $member->getInfo(['uid'=>$v['uid']],'up_level_time,referee_id');
                                $result = [];
                                $reason = '';
                                $ratio = '';
                                foreach ($conditions as $k2=>$v2){
                                    switch ($v2){
                                        case 1:
                                            $team_number_day = $level_infos['team_number_day'];
                                            $real_level_time = $members_info['up_level_time']+$team_number_day*24*3600;
                                            
                                            if($real_level_time<=time()){
                                                $getDistributorInfo1 = $this->getDistributorInfos($v['uid'],$team_number_day);
                                                $limit_number =  $getDistributorInfo1['agentordercount'];//限制时间段内团队分销订单数
                                                
                                                if($limit_number <=$level_infos['team_number']){
                                                    $result[] = 1;
                                                    $reason .= '团队分销订单数小于'.$level_infos['team_number'];
                                                }
                                            }
                                            break;
                                        case 2:
                                            $team_money_day = $level_infos['team_money_day'];
                                            $real_level_time = $members_info['up_level_time']+$team_money_day*24*3600;
                                            if($real_level_time<=time()){
                                                $getDistributorInfo2 = $this->getDistributorInfos($v['uid'],$team_money_day);
                                                $limit_money1 =  $getDistributorInfo2['order_money'];//限制时间段内团队分销订单金额
                                                if($limit_money1 <=$level_infos['team_money']){
                                                    $result[] = 2;
                                                    $reason .= ',团队分销订单金额小于'.$level_infos['team_money'];
                                                }
                                            }
                                            break;
                                        case 3:
                                            $self_money_day = $level_infos['self_money_day'];
                                            $real_level_time = $members_info['up_level_time']+$self_money_day*24*3600;
                                            if($real_level_time<=time()){
                                                $getDistributorInfo3 = $this->getDistributorInfos($v['uid'],$self_money_day);
                                                $limit_money2 = $getDistributorInfo3['selforder_money'];//限制时间段内自购分销订单金额
                                                if($limit_money2 <=$level_infos['self_money']){
                                                    $result[] = 3;
                                                    $reason .= ',自购分销订单金额小于'.$level_infos['self_money'];
                                                }
                                            }
                                            break;
                                    }
                                }
                                if($level_infos['downgrade_condition']==1){//降级条件类型（满足所有勾选条件）
                                    if(count($result)==count($conditions)){
                                        $member = new VslMemberModel();
                                        if($website_id == 54){
                                            debugLog($v['uid'], '==>分销商自动降级满足所有勾选条件<==');
                                        }
                                        $member->save(['distributor_level_id'=>$level_info_desc['id'],'down_level_time'=>time(),'down_up_level_time'=>time()],['uid'=>$v['uid']]);
                                        if($distribution_pattern>=1){
                                            $ratio .= '一级返佣比例'.$level_info_desc['commission1'].'%';
                                        }
                                        if($distribution_pattern>=2){
                                            $ratio .= ',二级返佣比例'.$level_info_desc['commission2'].'%';
                                        }
                                        if($distribution_pattern>=3){
                                            $ratio .= ',三级返佣比例'.$level_info_desc['commission3'].'%';
                                        }
                                        runhook("Notify", "sendCustomMessage", ['messageType'=>'down_notice','uid' => $v['uid'],'present_grade'=>$level_info_desc['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'down_reason'=>$reason,'down_time' => time()]);//降级
                                        if(getAddons('globalbonus', $this->website_id)){
                                            $global = new GlobalBonus();
                                            $global->updateAgentLevelInfo($members_info['referee_id']);
                                        }
                                        if(getAddons('areabonus', $this->website_id)){
                                            $area = new AreaBonus();
                                            $area->updateAgentLevelInfo($members_info['referee_id']);
                                        }
                                        if(getAddons('teambonus', $this->website_id)){
                                            $team = new TeamBonus();
                                            $team->updateAgentLevelInfo($members_info['referee_id']);
                                        }
                                        $this->updateDistributorLevelInfo($members_info['referee_id']);
                                        break;
                                    }
                                }
                                if($level_infos['downgrade_condition']==2){//降级条件类型（满足勾选条件任意一个即可）
                                    if(count($result)>=1){
                                        $member = new VslMemberModel();
                                        if($website_id == 54){
                                            debugLog($v['uid'], '==>分销商自动降级满足勾选条件任意一个即可<==');
                                        }
                                        $member->save(['distributor_level_id'=>$level_info_desc['id'],'down_level_time'=>time(),'down_up_level_time'=>time()],['uid'=>$v['uid']]);
                                        if($distribution_pattern>=1){
                                            $ratio .= '一级返佣比例'.$level_info_desc['commission1'].'%';
                                        }
                                        if($distribution_pattern>=2){
                                            $ratio .= ',二级返佣比例'.$level_info_desc['commission2'].'%';
                                        }
                                        if($distribution_pattern>=3){
                                            $ratio .= ',三级返佣比例'.$level_info_desc['commission3'].'%';
                                        }
                                        runhook("Notify", "sendCustomMessage", ['messageType'=>'down_notice','uid' => $v['uid'],'present_grade'=>$level_info_desc['level_name'],'primary_grade'=>$default_level_name,'ratio'=>$ratio,'down_reason'=>$reason,'down_time' => time()]);//降级
                                        if(getAddons('globalbonus', $this->website_id)){
                                            $global = new GlobalBonus();
                                            $global->updateAgentLevelInfo($members_info['referee_id']);
                                        }
                                        if(getAddons('areabonus', $this->website_id)){
                                            $area = new AreaBonus();
                                            $area->updateAgentLevelInfo($members_info['referee_id']);
                                        }
                                        if(getAddons('teambonus', $this->website_id)){
                                            $team = new TeamBonus();
                                            $team->updateAgentLevelInfo($members_info['referee_id']);
                                        }
                                        $this->updateDistributorLevelInfo($members_info['referee_id']);
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
     * 成为分销商的条件
     */
    public function becomeDistributor($uid,$order_id){
        $member = new VslMemberModel();
        $order_money_model = new OrderAccount();
        $distributor = $member->getInfo(['uid'=>$uid],'*');
        $config = new AddonsConfigService();
        $distribution_info = $config->getAddonsConfig("distribution",$distributor['website_id']);
        $base_info = json_decode($distribution_info['value'], true);
        $distribution_pattern = $base_info['distribution_pattern'];
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
        $distributor_level = new DistributorLevelModel();
        $level_info = $distributor_level->getInfo(['website_id' => $distributor['website_id'],'is_default'=>1],'*');
        $level_id = $level_info['id'];
        if($distributor['isdistributor']!=2 && $base_info['distributorcondition']!=-1 && $base_info['distributor_conditions']){//判断是否是分销商
            $result = [];
            $ratio = '';
            $conditions = explode(',',$base_info['distributor_conditions']);
            foreach ($conditions as $k=>$v){
                switch ($v){
                    case 2: $order_money = $order_money_model->getMemberSaleMoney(['order_status'=>4,'buyer_id'=>$uid]);
                        if($order_money>=$base_info['pay_money']){
                            $result[] = 2;//满足消费金额
                        }
                        break;
                    case 3: $order_number = $order_money_model->getShopSaleNumSum(['order_status'=>4,'buyer_id'=>$uid]);
                        if($order_number>=$base_info['order_number']){
                            $result[] = 3;//满足订单数
                        }
                        break;
                    case 4: $orders = $order->getInfo(['buyer_id'=>$uid,'order_status'=>4],'*');
                        if($orders){
                            $result[] = 4;//满足订单完成
                        }
                        break;
                    case 5: if($goods_info){
                        $result[] = 5;//满足购买指定商品
                    }
                        break;
                }
            }
            $extend_code = $this->create_extend();
            if($base_info['distributor_condition']==1){//满足所有勾选条件
                if(count($conditions)==count($result)){
                    $member->save(['isdistributor'=>2,'distributor_level_id'=>$level_id,'extend_code'=>$extend_code,"apply_distributor_time" => time(),'become_distributor_time'=>time()],['uid'=>$uid]);
                    $referee_id = $member->getInfo(['uid'=>$uid],'referee_id')['referee_id'];
                    if($referee_id){
                        $this->updateDistributorLevelInfo($referee_id);
                    }
                    if($distribution_pattern>=1){
                        $ratio .= '一级返佣比例'.$level_info['commission1'].'%';
                    }
                    if($distribution_pattern>=2){
                        $ratio .= ',二级返佣比例'.$level_info['commission2'].'%';
                    }
                    if($distribution_pattern>=3){
                        $ratio .= ',三级返佣比例'.$level_info['commission3'].'%';
                    }
                    if($base_info['distribution_pattern']>=1){
                        if($distributor['referee_id']){
                            $recommend1_info = $member->getInfo(['uid'=>$distributor['referee_id']],'*');
                            if($recommend1_info && $recommend1_info['isdistributor']==2){
                                $level_info1 = $distributor_level->getInfo(['id' => $recommend1_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                                $recommend1 = $level_info1['recommend1'];//一级推荐奖
                                $recommend_point1 = $level_info1['recommend_point1'];//一级推荐积分
                                $this->addRecommed($uid,$recommend1_info['uid'],$recommend1,$recommend_point1,$distributor['website_id']);
                            }
                        }
                    }
                    if($base_info['distribution_pattern']>=2){
                        $recommend2_info = $member->getInfo(['uid'=>$recommend1_info['referee_id']],'*');
                        if($recommend2_info && $recommend2_info['isdistributor']==2) {
                            $level_info2 = $distributor_level->getInfo(['id' => $recommend2_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                            $recommend2 = $level_info2['recommend2'];//二级推荐奖
                            $recommend_point2 = $level_info2['recommend_point2'];//二级推荐积分
                            $this->addRecommed($uid,$recommend2_info['uid'],$recommend2,$recommend_point2,$distributor['website_id']);
                        }
                    }
                    if($base_info['distribution_pattern']>=3){
                        $recommend3_info = $member->getInfo(['uid'=>$recommend2_info['referee_id']],'*');
                        if($recommend3_info && $recommend3_info['isdistributor']==2) {
                            $level_info3 = $distributor_level->getInfo(['id' => $recommend3_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                            $recommend3 = $level_info3['recommend3'];//三级推荐奖
                            $recommend_point3 = $level_info3['recommend_point3'];//三级推荐积分
                            $this->addRecommed($uid,$recommend3_info['uid'],$recommend3,$recommend_point3,$distributor['website_id']);
                        }
                    }
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"become_distributor","uid" => $uid,"become_time" => time(),'ratio'=>$ratio,'level_name'=>$level_info['level_name']]);//用户成为分销商提醒
                    runhook("Notify", "successfulDistributorByTemplate", ["uid" => $uid,"website_id" => $distributor['website_id']]);//用户成为分销商提醒
                }
            }
            if($base_info['distributor_condition']==2){//满足所有勾选条件之一
                if(count($result)>=1){
                    $member->save(['isdistributor'=>2,'distributor_level_id'=>$level_id,'extend_code'=>$extend_code,"apply_distributor_time" => time(),'become_distributor_time'=>time()],['uid'=>$uid]);
                    $referee_id = $member->getInfo(['uid'=>$uid],'referee_id')['referee_id'];
                    if($referee_id){
                        $this->updateDistributorLevelInfo($referee_id);
                    }
                    if($distribution_pattern>=1){
                        $ratio .= '一级返佣比例'.$level_info['commission1'].'%';
                    }
                    if($distribution_pattern>=2){
                        $ratio .= ',二级返佣比例'.$level_info['commission2'].'%';
                    }
                    if($distribution_pattern>=3){
                        $ratio .= ',三级返佣比例'.$level_info['commission3'].'%';
                    }
                    if($base_info['distribution_pattern']>=1){
                        if($distributor['referee_id']){
                            $recommend1_info = $member->getInfo(['uid'=>$distributor['referee_id']],'*');
                            if($recommend1_info && $recommend1_info['isdistributor']==2){
                                $level_info1 = $distributor_level->getInfo(['id' => $recommend1_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                                $recommend1 = $level_info1['recommend1'];//一级推荐奖
                                $recommend_point1 = $level_info1['recommend_point1'];//一级推荐积分
                                $this->addRecommed($uid,$recommend1_info['uid'],$recommend1,$recommend_point1,$distributor['website_id']);
                            }
                        }
                    }
                    if($base_info['distribution_pattern']>=2){
                        $recommend2_info = $member->getInfo(['uid'=>$recommend1_info['referee_id']],'*');
                        if($recommend2_info && $recommend2_info['isdistributor']==2) {
                            $level_info2 = $distributor_level->getInfo(['id' => $recommend2_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                            $recommend2 = $level_info2['recommend2'];//二级推荐奖
                            $recommend_point2 = $level_info2['recommend_point2'];//二级推荐积分
                            $this->addRecommed($uid,$recommend2_info['uid'],$recommend2,$recommend_point2,$distributor['website_id']);
                        }
                    }
                    if($base_info['distribution_pattern']>=3){
                        $recommend3_info = $member->getInfo(['uid'=>$recommend2_info['referee_id']],'*');
                        if($recommend3_info && $recommend3_info['isdistributor']==2) {
                            $level_info3 = $distributor_level->getInfo(['id' => $recommend3_info['distributor_level_id'],'website_id' => $distributor['website_id']]);
                            $recommend3 = $level_info3['recommend3'];//三级推荐奖
                            $recommend_point3 = $level_info3['recommend_point3'];//三级推荐积分
                            $this->addRecommed($uid,$recommend3_info['uid'],$recommend3,$recommend_point3,$distributor['website_id']);
                        }
                    }
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"become_distributor","uid" => $uid,"become_time" => time(),'ratio'=>$ratio,'level_name'=>$level_info['level_name']]);//用户成为分销商提醒
                    runhook("Notify", "successfulDistributorByTemplate", ["uid" => $uid,"website_id" => $distributor['website_id']]);//用户成为分销商提醒
                }
            }
        }

    }
    /*
     * 成为分销商的下线
     */
    public function becomeLower($uid){
        $member = new VslMemberModel();
        $distributor = $member->getInfo(['uid'=>$uid],'*');
       
        $info = $this->getDistributionSite($distributor['website_id']);
        
        if($info['is_use']==1 && $info['lower_condition']==2 && $distributor['default_referee_id'] && $distributor['referee_id']==null){
            
            if($distributor['default_referee_id']!=$uid){
                $lower_id = $member->Query(['referee_id'=>$uid],'*');
                if($lower_id && in_array($distributor['default_referee_id'],$lower_id)){
                    return 1;
                }
                
                runhook("Notify", "sendCustomMessage", ['messageType'=>'new_offline',"uid" => $uid,"add_time" => time(),'referee_id'=>$distributor['default_referee_id']]);//成为下线通知
                $member->save(['referee_id'=>$distributor['default_referee_id'],'default_referee_id'=>null],['uid'=>$uid]);
                $this->updateDistributorLevelInfo($distributor['default_referee_id']);
                if(getAddons('globalbonus', $distributor['website_id'])){
                    $global = new GlobalBonus();
                    $global->updateAgentLevelInfo($distributor['default_referee_id']);
                    $global->becomeAgent($distributor['default_referee_id']);
                }
                if(getAddons('areabonus', $distributor['website_id'])){
                    $area = new AreaBonus();
                    $area->updateAgentLevelInfo($distributor['default_referee_id']);
                }
                if(getAddons('teambonus', $distributor['website_id'])){
                    $team = new TeamBonus();
                    $team->updateAgentLevelInfo($distributor['default_referee_id']);
                    $team->becomeAgent($distributor['default_referee_id']);
                }
            }
        }
    }
    /**
     * 提现详情
     */
    public function withdrawDetail($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $list = $this->getCommissionWithdrawList($page_index = 1, $page_size = 0, $where, $order);
        return $list;
    }
    /**
     * 佣金提现设置
     */
    public function getCommissionWithdrawConfig($uid){
        $config = new ConfigService();
        $account = new VslDistributorAccountModel();
        $account_info = $account->getInfo(['uid'=>$uid],'*');
        $config_info = $config->getConfig(0,"SETTLEMENT",$account_info['website_id']);
        $list = json_decode($config_info['value'],true);
        $config_set = $config->getConfig(0,"WITHDRAW_BALANCE",$account_info['website_id']);
        if($account_info){
            $list['withdraw_money'] = $account_info['commission']-$account_info['withdrawals'];
            $list['commission'] = $account_info['commission'];
            $list['withdrawals'] = $account_info['withdrawals'];
            $list['freezing_commission'] = $account_info['freezing_commission'];
            $list['tax'] = $account_info['tax'];
        }
        if($list['withdrawals_type'] && $config_set['is_use']){
            $list['withdrawals_type'] = explode(',',$list['withdrawals_type']);
            //强行把4余额提现 5银行卡手动提现类型转换
            if(in_array(4, $list['withdrawals_type']) && in_array(5, $list['withdrawals_type'])){
                
            }else if(in_array(4, $list['withdrawals_type']) || in_array(5, $list['withdrawals_type'])){
                foreach ($list['withdrawals_type'] as $key => $value) {
                    if($value == 4){
                        $list['withdrawals_type'][$key] = 5;
                    }else if($value == 5){
                        $list['withdrawals_type'][$key] = 4;
                    }
                }
            }
            $info = $config->getWpayConfig($account_info['website_id']);
            $wx_tw = $info['value']['wx_tw'];
            $user = new UserModel();
            $user_info = $user->getInfo(['uid'=>$uid],'payment_password,wx_openid,mp_open_id');
            if($wx_tw==0 || $info['is_use']==0){
                $list['withdrawals_type'] = array_merge(array_diff($list['withdrawals_type'],[2]));
            }elseif(empty($user_info['wx_openid']) && empty($user_info['mp_open_id'])){
                $list['withdrawals_type'] = array_merge(array_diff($list['withdrawals_type'],[2]));
            }
            $info1 = $config->getTlConfig(0,$this->website_id);
            $tl_tw = $info1['value']['tl_tw'];
            if($tl_tw==0 || $info1['is_use']==0){
                $list['withdrawals_type'] = array_merge(array_diff($list['withdrawals_type'],[1]));
            }
            $info2 = $config->getAlipayConfig($this->website_id);
            if($info2['is_use']==0){
                $list['withdrawals_type'] = array_merge(array_diff($list['withdrawals_type'],[3]));
            }
        }else{
            $list['withdrawals_type'] = [];
        }
        $withdraw_account = new VslDistributorCommissionWithdrawModel();
        $list['make_withdraw'] = abs(array_sum($withdraw_account->Query(['uid'=>$uid,'status'=>2],'cash')));//待打款
        $list['apply_withdraw'] = abs(array_sum($withdraw_account->Query(['uid'=>$uid,'status'=>1],'cash')));//审核中
        $list['account_list'] = $this->getMemberBankAccount($is_default = 0,$uid);
        return $list;
    }
    /**
     * 佣金提现账户类型
     */
    public function getMemberBankAccount($is_default = 0,$uid)
    {
        $member_bank_account = new VslMemberBankAccountModel();
        $bank_account_list = '';
        if (! empty($uid)) {
            if (empty($is_default)) {
                $bank_account_list = $member_bank_account->getQuery([
                    'uid' => $uid
                ], '*', '');
            } else {
                $bank_account_list = $member_bank_account->getQuery([
                    'uid' => $uid,
                    'is_default' => 1
                ], '*', '');
            }
        }
        return $bank_account_list;
    }
    /**
     * 佣金提现
     */
    public function addDistributorCommissionWithdraw($withdraw_no,$uid,$account_id,$cash){
        // 平台的提现设置
        $fail= 0;
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid'=>$uid],'*');
        $website_id = $this->website_id;
        $real_name = $member_info['real_name'];
        $config = new ConfigService();
        $config_set = $config->getConfig(0,"WITHDRAW_BALANCE",$member_info['website_id']);
        $commission_withdraw_set = json_decode($config->getConfig(0,'SETTLEMENT',$member_info['website_id'])['value'],true);
        // 判断是否提现设置是否为空 是否启用
        if (empty($config_set) || $config_set['is_use'] == 0) {
            return USER_WITHDRAW_NO_USE;
        }
        // 最小提现额判断
        if ($cash < $commission_withdraw_set["withdrawals_min"]) {
            return USER_WITHDRAW_MIN;
        }
        // 判断当前分销商的可提现佣金
        $account = new VslDistributorAccountModel();
        $commission_info = $account->getInfo(['uid'=>$uid],'*');
        $commission = $commission_info['commission'];
        if ($commission <= 0) {
            return ORDER_CREATE_LOW_PLATFORM_MONEY;
        }
        if ($commission < $cash || $cash <= 0) {
            return ORDER_CREATE_LOW_PLATFORM_MONEY;
        }
        $member_account = new VslMemberBankAccountModel();
        if($account_id==-1){
            //提现到账户余额
            $account_number = -1;
            $type = 4;
        }else if($account_id==-2){
            //提现到微信
            $account_number = $member_info['mobile'];
            $type = 2;
        }else{
            // 获取 提现账户
            $account_info = $member_account->getInfo([
                'id' => $account_id
            ], '*');
            $account_number = $account_info['account_number'];
            $type = $account_info['type'];
            if($type==4){//会员提现账户的类型4相当于佣金提现里面的类型5
                $type=5;
            }
        }
        if($type==1 || $type ==5){
            if($commission_withdraw_set['withdraw_message']){
                $withdraw_message = explode(',',$commission_withdraw_set['withdraw_message']);
                if(in_array(5,$withdraw_message)){
                    $type =5;
                }
            }
        }

        // 添加佣金提现记录
        $commission_withdraw = new VslDistributorCommissionWithdrawModel();
        try{
            // 查询提现审核方式
            if($commission_withdraw_set['withdrawals_check']==2 && abs($cash)<= $commission_withdraw_set['withdrawals_cash']){//关闭免审核，提现金额小于免审核区间
                $is_examine = 1;
            }else{
                $is_examine =  $commission_withdraw_set['withdrawals_check'];
            }
            $tax = 0;
            //佣金个人所得税
            if($commission_withdraw_set['poundage']) {
                $tax = twoDecimal(abs($cash) * $commission_withdraw_set['poundage']/100);//佣金个人所得税
                if($commission_withdraw_set['withdrawals_end'] && $commission_withdraw_set['withdrawals_begin']){
                    if (abs($cash) <= $commission_withdraw_set['withdrawals_end'] && abs($cash) >= $commission_withdraw_set['withdrawals_begin']) {
                        $tax = 0;//免打税区间
                    }
                }
            }
            if($cash+$tax<=$commission){
                $income_tax = $cash;
            }else if($cash-$tax>=0){
                $income_tax = $cash-$tax;
            }else{
                return ORDER_CREATE_LOW_PLATFORM_MONEY;
            }
            // 查询提现打款方式
            $make_money = $commission_withdraw_set['make_money'];
            if($is_examine==1 && $make_money==1){//自动审核自动打款
                if($account_id==-1){
                    $data = array(
                        'withdraw_no' => $withdraw_no,
                        'uid' => $uid,
                        'account_number' => -1,
                        'realname' => $real_name,
                        'payment_date'=>time(),
                        'type'   => $type,
                        'cash' => (-1)*$cash,
                        'tax' => (-1)*$tax,
                        'income_tax'=>$income_tax,//实际到账金额
                        'ask_for_date' => time(),
                        'status' => 3,//直接提现到账户余额
                        'website_id' => $website_id
                    );
                }else {
                    $data = array(
                        'withdraw_no' => $withdraw_no,
                        'uid' => $uid,
                        'account_number' => $account_number,
                        'income_tax'=>$income_tax,//实际到账金额
                        'realname' => $real_name,
                        'type' => $type,
                        'cash' => (-1)*$cash,
                        'tax' => (-1)*$tax,
                        'ask_for_date' => time(),
                        'status' => 2,//审核通过
                        'website_id' => $website_id
                    );
                }
                $res = $commission_withdraw->save($data);
                if($res){
                    if($account_id==-1){
                        // 更新佣金账户情况
                        $data_commission['uid'] = $data['uid'];
                        $data_commission['commission'] = $data['income_tax'];//扣除佣金总额（包括手续费）
                        $data_commission['cash'] = $data['cash'];
                        $data_commission['tax'] = $tax;
                        $data_commission['income_tax'] = $data['income_tax'];
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['website_id'] = $data['website_id'];
                        $data_commission['text'] = '提现到账户余额成功';
                        $this->updateAccountWithdraw(15,$data_commission);
                        $this->addCommissionWithdraw($data_commission);//审核通过直接提现到账户余额
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" =>$data['uid'],'withdraw_money' =>$data['cash'],"withdraw_type" => '提现到账户余额','withdraw_time'=>time()]);//提现成功
                    }else if($type==1 || $type==2 || $type==3 ||$type==5){
                        $withdraw_info = $commission_withdraw->getInfo(['id'=>$res],'*');
                        if($type==2){
                            $params['shop_id'] = 0;
                            $params['takeoutmoney'] = abs($withdraw_info['cash']);
                            $params['uid'] = $uid;
                            $params['website_id'] = $this->website_id;
                            $data_commission['data_id'] = $data['withdraw_no'];
                            $data_commission['status'] = 5;
                            $data_commission['uid'] = $withdraw_info['uid'];
                            $data_commission['website_id'] = $withdraw_info['website_id'];
                            $data_commission['commission'] = $withdraw_info['income_tax'];//扣除佣金总额（包括手续费）
                            $data_commission['cash'] = $withdraw_info['cash'];
                            $data_commission['tax'] = $tax;
                            $data_commission['text'] = '提现到微信待打款';
                            $this->updateAccountWithdraw(5,$data_commission);
                            $user_info = new UserModel();
                            $wx_openid = $user_info->getInfo(['uid'=>$withdraw_info['uid']],'wx_openid')['wx_openid'];
                            $weixin_pay = new WeiXinPay();
                            $retval = $weixin_pay->EnterprisePayment($wx_openid,$withdraw_info['withdraw_no'],'',abs($withdraw_info['income_tax']),'佣金微信提现',$this->website_id);
                            if($retval['is_success']==1){//自动打款成功
                                runhook('Notify', 'withdrawalSuccessBySms', $params);
                                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $withdraw_info['uid'],'withdraw_money' => $withdraw_info['cash'],"withdraw_type" => '提现到微信','withdraw_time'=>time()]);//提现成功
                                $data_commission['status'] =10;
                                $data_commission['text'] = '提现成功到微信';//微信
                                $this->addAccountWithdrawUserRecords($data_commission, 2, $res, "佣金微信提现，打款成功。");
                                $commission_withdraw->where(array("id" => $res))->update(array("payment_date"=>time(),"status" => 3,"memo"=>'打款成功'));
                            }else{//自动打款失败
                                $data_commission['status'] =-10;
                                $data_commission['msg'] =$retval['msg'];
                                $data_commission['text'] = '提现到微信打款失败，等待商家重新打款';//微信
                                $this->addAccountWithdrawUserRecords($data_commission, 2, $res, "佣金微信提现，打款失败。");
                                $commission_withdraw->where(array("id" => $res))->update(array("status" => 5,"memo"=>'打款失败'));
                                $fail =1;
                            }
                        }
                        if($type==3){
                            $data_commission['data_id'] = $withdraw_info['withdraw_no'];
                            $data_commission['status'] = 7;
                            $data_commission['uid'] = $withdraw_info['uid'];
                            $data_commission['website_id'] = $withdraw_info['website_id'];
                            $data_commission['commission'] = $withdraw_info['income_tax'];
                            $data_commission['cash'] = $withdraw_info['cash'];
                            $data_commission['text'] = '提现到支付宝待打款';
                            $data_commission['tax'] = $tax;
                            $this->updateAccountWithdraw(7,$data_commission);
                            $alipay_pay = new AliPay();
                            $retval = $alipay_pay->aliPayTransferNew($withdraw_info['withdraw_no'],$withdraw_info['account_number'],abs($withdraw_info['income_tax']));
                            if($retval['is_success']==1){
                                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $withdraw_info['uid'],'withdraw_money' => $withdraw_info['cash'],"withdraw_type" => '提现到支付宝','withdraw_time'=>time()]);//提现成功
                                runhook('Notify', 'withdrawalSuccessBySms', $data_commission);
                                $data_commission['status'] =11;
                                $data_commission['text'] = '提现成功到支付宝';//支付宝
                                $this->addAccountWithdrawUserRecords($data_commission, 2, $res, "佣金支付宝提现，打款成功。");
                                $commission_withdraw->where(array("id" => $res))->update(array("payment_date"=>time(),"status" =>3,"memo"=>'打款成功'));
                            }else{//自动打款失败
                                $data_commission['status'] =-11;
                                $data_commission['msg'] =$retval['msg'];
                                $data_commission['text'] = '提现到支付宝打款失败，等待商家重新打款';//支付宝
                                $this->addAccountWithdrawUserRecords($data_commission, 2, $res, "佣金支付宝提现，打款失败。");
                                $commission_withdraw->where(array("id" => $res))->update(array("status" => 5,"memo"=>'打款失败'));
                                $fail =1;
                            }
                        }
                        if($type==1){
                            $data_commission['data_id'] = $withdraw_info['withdraw_no'];
                            $data_commission['status'] = 8;
                            $data_commission['uid'] = $withdraw_info['uid'];
                            $data_commission['website_id'] = $withdraw_info['website_id'];
                            $data_commission['commission'] = $withdraw_info['income_tax'];
                            $data_commission['cash'] = $withdraw_info['cash'];
                            $data_commission['text'] = '提现到银行卡待打款';
                            $data_commission['tax'] = $tax;
                            $this->updateAccountWithdraw(8,$data_commission);
                            $bank = new VslMemberBankAccountModel();
                            $bank_id = $bank->getInfo(['account_number'=>$withdraw_info['account_number'],'uid'=>$withdraw_info['uid']],'id')['id'];
                            $tlpay_pay = new tlPay();
                            $retval = $tlpay_pay->tlWithdraw($withdraw_info['withdraw_no'],$withdraw_info['uid'],$bank_id,abs($withdraw_info['income_tax']));
                            if($retval['is_success']==1){
                                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $withdraw_info['uid'],'withdraw_money' => $withdraw_info['cash'],"withdraw_type" => '提现到银行卡','withdraw_time'=>time()]);//提现成功
                                runhook('Notify', 'withdrawalSuccessBySms', $data_commission);
                                $data_commission['status'] =9;
                                $data_commission['text'] = '提现成功到银行卡';//银行卡
                                $this->addAccountWithdrawUserRecords($data_commission, 2, $res, "佣金银行卡提现，打款成功。");
                                $commission_withdraw->where(array("id" => $res))->update(array("payment_date"=>time(),"status" =>3,"memo"=>'打款成功'));
                            }else{//自动打款失败
                                $data_commission['status'] =-9;
                                $data_commission['msg'] =$retval['msg'];
                                $data_commission['text'] = '提现到银行卡打款失败，等待商家重新打款';//支付宝
                                $this->addAccountWithdrawUserRecords($data_commission, 2, $res, "佣金银行卡提现，打款失败。");
                                $commission_withdraw->where(array("id" => $res))->update(array("status" => 5,"memo"=>'打款失败'));
                                $fail =1;
                            }
                        }
                        if($type==5){
                            $data_commission['data_id'] = $withdraw_info['withdraw_no'];
                            $data_commission['status'] = 8;
                            $data_commission['uid'] = $withdraw_info['uid'];
                            $data_commission['website_id'] = $withdraw_info['website_id'];
                            $data_commission['commission'] = $withdraw_info['income_tax'];
                            $data_commission['cash'] = $withdraw_info['cash'];
                            $data_commission['text'] = '提现到银行卡待打款';
                            $data_commission['tax'] = $tax;
                            $this->updateAccountWithdraw(8,$data_commission);
                            runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $withdraw_info['uid'],'withdraw_money' => $withdraw_info['cash'],"withdraw_type" => '提现到银行卡','withdraw_time'=>time()]);//提现成功
                            runhook('Notify', 'withdrawalSuccessBySms', $data_commission);
                            $data_commission['status'] =9;
                            $data_commission['text'] = '提现成功到银行卡';//银行卡
                            $this->addAccountWithdrawUserRecords($data_commission, 2, $res, "佣金银行卡提现，打款成功。");
                            $commission_withdraw->where(array("id" => $res))->update(array("payment_date"=>time(),"status" =>3,"memo"=>'打款成功'));
                        }
                    }
               }
            }
            if($is_examine==2 && $make_money==1){//手动审核自动打款
                $data = array(
                    'withdraw_no' => $withdraw_no,
                    'uid' => $uid,
                    'account_number' => $account_number,
                    'realname' => $real_name,
                    'income_tax'=>$income_tax,//税后金额
                    'type'   => $type,
                    'cash' => (-1)*$cash,
                    'tax' => (-1)*$tax,
                    'ask_for_date' => time(),
                    'status' => 1,//提现审核中
                    'website_id' => $website_id
                );
              $rel = $commission_withdraw->save($data);
                if($rel>0){
                    if($account_id==-1){
                        $data_commission = array(
                            'status'=>6,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到账户余额待审核',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到账户余额','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                    if($type==1 || $type==5){
                        $data_commission = array(
                            'status'=>12,
                            'cash'=>$cash,
                            'uid' => $uid,
                            'commission' => $income_tax,
                            'text' => '提现到银行卡待审核',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到银行卡','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                    if($type==2){
                        $data_commission = array(
                            'status'=>13,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到微信待审核',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['tax'] = $tax;
                        $data_commission['data_id'] = $data['withdraw_no'];
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到微信','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                    if($type==3){
                        $data_commission = array(
                            'status'=>14,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到支付宝待审核',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['tax'] = $tax;
                        $data_commission['data_id'] = $data['withdraw_no'];
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到支付宝','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                }

            }
            if($is_examine==1 && $make_money==2){//自动审核待打款
                    $data = array(
                        'withdraw_no' => $withdraw_no,
                        'uid' => $uid,
                        'account_number' => $account_number,
                        'realname' => $real_name,
                        'income_tax'=>$income_tax,//税后金额
                        'type' => $type,
                        'cash' => (-1)*$cash,
                        'tax' => (-1)*$tax,
                        'ask_for_date' => time(),
                        'status' => 2,//审核通过，待打款
                        'website_id' => $website_id
                    );
                $rel = $commission_withdraw->save($data);
                if($rel){
                    if($type==4){
                        $data_commission = array(
                            'status'=>15,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到账户余额待打款',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到账户余额','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                    if($type==2){
                        $data_commission = array(
                            'status'=>5,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到微信待打款',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到微信','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                    if($type==3){
                        $data_commission = array(
                            'status'=>7,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到支付宝待打款',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到支付宝','withdraw_time'=>time()]);
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                    if($type==1 || $type==5){
                        $data_commission = array(
                            'status'=>8,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到银行卡待打款',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到银行卡','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                }

            }
            if($is_examine==2 && $make_money==2){//手动审核手动打款
                $data = array(
                    'withdraw_no' => $withdraw_no,
                    'uid' => $uid,
                    'account_number' => $account_number,
                    'realname' => $real_name,
                    'income_tax'=>$income_tax,//税后金额
                    'type'   => $type,
                    'tax' => (-1)*$tax,
                    'cash' => (-1)*$cash,
                    'ask_for_date' => time(),
                    'status' => 1,//提现审核中
                    'website_id' => $website_id
                );
                $rel = $commission_withdraw->save($data);
                if($rel){
                    if($account_id==-1){
                        $data_commission = array(
                            'status'=>6,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到账户余额待审核',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到账户余额','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                    if($type==1 || $type==5){
                        $data_commission = array(
                            'status'=>12,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到银行卡待审核',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到银行卡','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                    if($type==2){
                        $data_commission = array(
                            'status'=>13,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到微信待审核',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到微信','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                    if($type==3){
                        $data_commission = array(
                            'status'=>14,
                            'uid' => $uid,
                            'cash'=>$cash,
                            'commission' => $income_tax,
                            'text' => '提现到支付宝待审核',//提现审核中
                            'website_id' => $website_id
                        );
                        $data_commission['data_id'] = $data['withdraw_no'];
                        $data_commission['tax'] = $tax;
                        runhook("Notify", "sendCustomMessage", ["messageType"=>"application_cash","uid" =>$data_commission['uid'],'withdraw_money' =>$cash,"withdraw_type" => '提现到支付宝','withdraw_time'=>time()]);
                        $this->addAccountWithdrawUserRecords($data_commission,2, $rel,  $data_commission['text']);
                    }
                }

            }
            $commission_withdraw->commit();
            if($fail==1){
                return -9000;
            }
            return $commission_withdraw->id;
        }catch (\Exception $e)
        {
            $commission_withdraw->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 佣金成功提现到账户余额
     */
    public function addCommissionWithdraw($data){
        $commission_withdraw = new VslMemberAccountRecordsModel();
        $member_account = new VslMemberAccountModel();
        $account_info = $member_account->getInfo(['uid'=>$data['uid']],'*');
        try{
            $data1 = array(
                'records_no' => getSerialNo(),
                'uid' => $data['uid'],
                'account_type' => 2,
                'number'   => $data['income_tax'],//实际到账金额
                'data_id' => $data['data_id'],
                'from_type' => 15,
                'balance' => abs($data['income_tax'])+$account_info['balance'],
                'text' => '佣金成功提现到余额',
                'create_time' => time(),
                'website_id' => $data['website_id']
            );
            $res1 = $commission_withdraw->save($data1);//添加会员流水
            $data_commission = array(
                'uid' => $data['uid'],
                'commission'=> $data['income_tax'],
                'data_id' => $data['data_id'],
                'tax'=>$data['tax'],
                'cash'=>$data['cash'],
                'status' => 4,
                'text' => $data['text'],
                'website_id' => $data['website_id']
            );
            $res2 = $this->addCommissionDistribution($data_commission);//更新佣金账户流水
            if($res1 && $res2){
                $data2 = array(
                        'balance' => abs($data['income_tax'])+$account_info['balance']
                    );
                $member_account->save($data2,['uid'=>$data['uid']]);//更新会员账户余额
                // 添加平台的整体资金流水
                $acount = new ShopAccount();
                if(abs($data['tax'])>0){
                    $acount->addAccountRecords(0, $data['uid'], "佣金提现成功，个人所得税!", abs($data['tax']), 24, $data['data_id'], '佣金提现到账户余额，个人所得税增加');
                }
                $acount->addAccountRecords(0, $data['uid'], "佣金提现到账户余额成功!", abs($data['cash']), 34, $data['data_id'], '佣金提现到账户余额');
                $commission_account = new VslDistributorAccountModel();
                $commission_account_info = $commission_account->getInfo(['uid'=>$data['uid']],'*');
                try{
                        $data3 = array(
                            'tax'=>$commission_account_info['tax']+abs($data['tax']),
                            'freezing_commission'=>$commission_account_info['freezing_commission']-abs($data['income_tax'])-abs($data['tax']),//冻结佣金减少
                            'withdrawals'=>$commission_account_info['withdrawals']+abs($data['income_tax'])+abs($data['tax']),//已提现佣金增加
                        );
                        $commission_account->save($data3,['uid'=>$data['uid']]);//更新佣金账户
                        $withdraw = new VslDistributorCommissionWithdrawModel();
                        $res = $withdraw->save(['payment_date'=>time(),'status'=>3],['withdraw_no'=>$data['data_id']]);//更新佣金提现状态
                        $commission_account->commit();
                        return $res;
                        }catch (\Exception $e)
                    {
                        $commission_account->rollback();
                        return $e->getMessage();
                    }
            }
            $commission_withdraw->commit();
        }catch (\Exception $e)
        {
            $commission_withdraw->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 修改佣金提现状态
     */
    public function commissionWithdrawAudit($id,$status,$memo)
    {
        $distributor_commission_withdraw = new VslDistributorCommissionWithdrawModel();
        $commission_info = $distributor_commission_withdraw->getInfo(['id'=>$id],"*");
        $res =0;
        $config = new ConfigService();
        $commission_withdraw_set = json_decode($config->getConfig(0,'SETTLEMENT',$commission_info ['website_id'])['value'],true);
        $make_money = $commission_withdraw_set['make_money'];
        if($commission_info  && $status == 2 && $make_money==2) { // 平台手动审核通过提现待打款，更新提现状态
            $res = $distributor_commission_withdraw->save(['status'=>$status],['id'=>$id]);
        }
        if($commission_info  && $status == 2 && $make_money==1) { // 平台手动审核通过提现自动打款，更新提现状态
            if($commission_info['type']==5){
                $params['shop_id'] = 0;
                $params['takeoutmoney'] = abs($commission_info['cash']);
                $params['uid'] =  $commission_info['uid'];
                $params['website_id'] = $commission_info['website_id'];
                $data_commission['data_id'] = $commission_info['withdraw_no'];
                $data_commission['website_id'] = $commission_info['website_id'];
                $data_commission['commission'] = $commission_info['income_tax'];
                $data_commission['cash'] = $commission_info['cash'];
                $data_commission['tax'] = $commission_info['tax'];
                $data_commission['uid'] = $commission_info['uid'];
                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到银行卡','withdraw_time'=>time()]);//提现成功
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                $data_commission['status'] =9;
                $data_commission['text'] = '提现成功到银行卡';//银行卡
                $data_commission['data_id'] =$commission_info['withdraw_no'];
                $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金银行卡提现，打款成功。");
                $res =$distributor_commission_withdraw->where(array("id" => $id))->update(array("payment_date"=>time(),"status" => 3,"memo"=>'打款成功'));
            }
            if($commission_info['type']==1){//银行卡
                $params['shop_id'] = 0;
                $params['takeoutmoney'] = abs($commission_info['cash']);
                $params['uid'] =  $commission_info['uid'];
                $params['website_id'] = $commission_info['website_id'];
                $data_commission['data_id'] = $commission_info['withdraw_no'];
                $data_commission['website_id'] = $commission_info['website_id'];
                $data_commission['commission'] = $commission_info['income_tax'];
                $data_commission['cash'] = $commission_info['cash'];
                $data_commission['tax'] = $commission_info['tax'];
                $data_commission['uid'] = $commission_info['uid'];
                $bank = new VslMemberBankAccountModel();
                $bank_id = $bank->getInfo(['uid'=>$commission_info['uid'],'account_number'=>$commission_info['account_number']],'id')['id'];
                $weixin_pay = new tlPay();
                $retval = $weixin_pay->tlWithdraw($commission_info['withdraw_no'],$commission_info['uid'],$bank_id,abs($commission_info['income_tax']));
                if($retval['is_success']==1){//自动打款成功
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到银行卡','withdraw_time'=>time()]);//提现成功
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_commission['status'] =9;
                    $data_commission['text'] = '提现成功到银行卡';//银行卡
                    $data_commission['data_id'] =$commission_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金银行卡提现，打款成功。");
                    $res =$distributor_commission_withdraw->where(array("id" => $id))->update(array("payment_date"=>time(),"status" => 3,"memo"=>'打款成功'));
                }else{//自动打款失败
                    $data_commission['status'] =-9;
                    $data_commission['msg'] =$retval['msg'];
                    $data_commission['text'] = '提现到银行卡打款失败';//银行卡
                    $data_commission['data_id'] =$commission_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金银行卡提现，打款失败。");
                    $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => 5,"memo"=>$retval['msg']));
                    return -9000;
                }
            }
            if($commission_info['type']==2){//微信
                $params['shop_id'] = 0;
                $params['takeoutmoney'] = abs($commission_info['cash']);
                $params['uid'] =  $commission_info['uid'];
                $params['website_id'] = $commission_info['website_id'];
                $data_commission['data_id'] = $commission_info['withdraw_no'];
                $data_commission['website_id'] = $commission_info['website_id'];
                $data_commission['commission'] = $commission_info['income_tax'];
                $data_commission['cash'] = $commission_info['cash'];
                $data_commission['tax'] = $commission_info['tax'];
                $data_commission['uid'] = $commission_info['uid'];
                $user_info = new UserModel();
                $wx_openid = $user_info->getInfo(['uid'=>$commission_info['uid']],'wx_openid')['wx_openid'];
                $weixin_pay = new WeiXinPay();
                $retval = $weixin_pay->EnterprisePayment($wx_openid,$commission_info['withdraw_no'],'',abs($commission_info['income_tax']),'佣金微信提现',$this->website_id);
                if($retval['is_success']==1){//自动打款成功
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到微信','withdraw_time'=>time()]);//提现成功
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_commission['status'] =10;
                    $data_commission['text'] = '提现成功到微信';//微信
                    $data_commission['data_id'] =$commission_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金微信提现，打款成功。");
                    $res =$distributor_commission_withdraw->where(array("id" => $id))->update(array("payment_date"=>time(),"status" => 3,"memo"=>'打款成功'));
                }else{//自动打款失败
                    $data_commission['status'] =-10;
                    $data_commission['msg'] =$retval['msg'];
                    $data_commission['text'] = '微信提现打款失败';//微信
                    $data_commission['data_id'] =$commission_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金微信提现，打款失败。");
                    $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => 5,"memo"=>$retval['msg']));
                    return -9000;
                }
            }
            if($commission_info['type']==3){//支付宝
                $data_commission['data_id'] = $commission_info['withdraw_no'];
                $data_commission['status'] = 7;
                $data_commission['uid'] = $commission_info['uid'];
                $data_commission['website_id'] = $commission_info['website_id'];
                $data_commission['commission'] = $commission_info['income_tax'];
                $data_commission['cash'] = $commission_info['cash'];
                $data_commission['tax'] = $commission_info['tax'];
                $data_commission['uid'] = $commission_info['uid'];
                $alipay_pay = new AliPay();
                $retval = $alipay_pay->aliPayTransferNew($commission_info['withdraw_no'],$commission_info['account_number'],abs($commission_info['income_tax']));
                if($retval['is_success']==1){
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到支付宝','withdraw_time'=>time()]);//提现成功
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_commission['status'] =11;
                    $data_commission['text'] = '提现成功到支付宝';//支付宝
                    $data_commission['data_id'] =$commission_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金支付宝提现，打款成功。");
                    $res  = $distributor_commission_withdraw->where(array("id" => $id))->update(array("payment_date"=>time(),"status" =>3,"memo"=>'打款成功'));
                }else{//自动打款失败
                    $data_commission['status'] =-11;
                    $data_commission['msg'] =$retval['msg'];
                    $data_commission['text'] = '支付宝提现打款失败';//支付宝
                    $data_commission['data_id'] =$commission_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金支付宝提现，打款失败。");
                    $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => 5,"memo"=>$retval['msg']));
                    return -9000;
                }
            }
            if($commission_info['type']==4){//直接到账户余额
                $commission_info['data_id'] =$commission_info['withdraw_no'];
                $commission_info['text'] = '提现到账户余额成功,可提现佣金减少,已提现佣金增加';
                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到账户余额','withdraw_time'=>time()]);//提现成功
                $res =  $this->addCommissionWithdraw($commission_info);//审核通过直接提现到账户余额;
            }
        }
        if($commission_info  && $status == 3){// 平台同意打款，更新提现状态（在线打款）
            $data_commission['data_id'] = $commission_info['withdraw_no'];
            $data_commission['uid'] =$commission_info["uid"];
            $data_commission['cash'] =$commission_info["cash"];
            $data_commission['commission'] =$commission_info["income_tax"];
            $data_commission['tax'] =$commission_info['tax'];
            $data_commission['website_id'] = $commission_info['website_id'];
            $params['shop_id'] = 0;
            $params['takeoutmoney'] = abs($commission_info['cash']);
            $params['uid'] = $data_commission['uid'];
            $params['website_id'] = $this->website_id;
            if($commission_info['type']==1){//银行卡
                $bank = new VslMemberBankAccountModel();
                $bank_id = $bank->getInfo(['uid'=>$commission_info['uid'],'account_number'=>$commission_info['account_number']],'id')['id'];
                $weixin_pay = new tlPay();
                $retval = $weixin_pay->tlWithdraw($commission_info['withdraw_no'],$commission_info['uid'],$bank_id,abs($commission_info['income_tax']));
                if($retval['is_success']==1){
                    $data_commission['status'] =9;
                    $data_commission['text'] = '提现成功到银行卡';
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金银行卡提现，在线打款成功。");
                    $res =  $distributor_commission_withdraw->where(array("id" => $id))->update(array("payment_date"=>time(),"status" => 3,"memo"=>'在线打款成功'));
                }else{
                    $data_commission['status'] =-9;
                    $data_commission['msg'] =$retval['msg'];
                    $data_commission['text'] = '银行卡提现打款失败';//银行卡
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金银行卡提现，在线打款失败。");
                    $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => 5,"memo"=>$retval['msg']));
                    return -9000;
                }
            }
            if($commission_info['type']==2){//微信
                $user_info = new UserModel();
                $wx_openid = $user_info->getInfo(['uid'=>$commission_info['uid']],'wx_openid')['wx_openid'];
                $weixin_pay = new WeiXinPay();
                $retval = $weixin_pay->EnterprisePayment($wx_openid,$commission_info['withdraw_no'],'',abs($commission_info['income_tax']),'佣金微信提现',$this->website_id);
                if($retval['is_success']==1){//自动打款成功
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到微信','withdraw_time'=>time()]);//提现成功
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_commission['status'] =10;
                    $data_commission['text'] = '提现成功到微信';//微信
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金微信提现，在线打款成功。");
                    $res =  $distributor_commission_withdraw->where(array("id" => $id))->update(array("payment_date"=>time(),"status" => 3,"memo"=>'在线打款成功'));
                }else{//自动打款失败
                    $data_commission['status'] =-10;
                    $data_commission['msg'] =$retval['msg'];
                    $data_commission['text'] = '微信提现打款失败';//微信
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金微信提现，在线打款失败。");
                    $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => 5,"memo"=>$retval['msg']));
                    return -9000;
                }
            }
            if($commission_info['type']==3){//支付宝
                $alipay_pay = new AliPay();
                $retval = $alipay_pay->aliPayTransferNew($commission_info['withdraw_no'],$commission_info['account_number'],abs($commission_info['income_tax']));
                if($retval['is_success']==1){
                    runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到支付宝','withdraw_time'=>time()]);//提现成功
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_commission['status'] =11;
                    $data_commission['text'] = '提现成功到支付宝';//支付宝
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金支付宝提现，在线打款成功。");
                    $res = $distributor_commission_withdraw ->where(array("id" => $id))->update(array("payment_date"=>time(),"status" =>3,"memo"=>'在线打款成功'));
                }else{//自动打款失败
                    $data_commission['status'] =-11;
                    $data_commission['msg'] =$retval['msg'];
                    $data_commission['text'] = '支付宝提现打款失败';//支付宝
                    $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金支付宝提现，在线打款失败。");
                    $distributor_commission_withdraw ->where(array("id" => $id))->update(array("status" => 5,"memo"=>$retval['msg']));
                    return -9000;
                }
            }
            if($commission_info['type']==4){//余额
                $data['data_id'] = $commission_info['withdraw_no'];
                $data['website_id'] = $commission_info['website_id'];
                $data['commission'] = $commission_info['income_tax'];
                $data['uid'] = $commission_info['uid'];
                $data['tax'] = $commission_info['tax'];
                $data['cash'] =$commission_info["cash"];
                $data['income_tax'] =$commission_info["income_tax"];
                $data['text'] = '提现到账户余额成功,可提现佣金减少,已提现佣金增加';
                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到账户余额','withdraw_time'=>time()]);//提现成功
                $res = $this->addCommissionWithdraw($data);
            }
            if($commission_info['type']==5){//银行卡
                $data_commission['status'] =9;
                $data_commission['text'] = '提现成功到银行卡';
                $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金银行卡提现，在线打款成功。");
                $res =  $distributor_commission_withdraw->where(array("id" => $id))->update(array("payment_date"=>time(),"status" => 3,"memo"=>'在线打款成功'));
            }
        }
        if($commission_info  && $status == 5){// 平台同意打款，更新提现状态（线下打款）
            $data_commission['data_id'] = $commission_info['withdraw_no'];
            $data_commission['uid'] =$commission_info["uid"];
            $data_commission['cash'] =$commission_info["cash"];
            $data_commission['commission'] =$commission_info["income_tax"];
            $data_commission['tax'] =$commission_info["tax"];
            $data_commission['website_id'] = $commission_info['website_id'];
            $params['shop_id'] = 0;
            $params['takeoutmoney'] = abs($commission_info['cash']);
            $params['uid'] = $data_commission['uid'];
            $params['website_id'] = $this->website_id;
            if($commission_info['type']==1 || $commission_info['type']==5){//银行卡
                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到银行卡','withdraw_time'=>time()]);//提现成功
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                $data_commission['status'] =9;
                $data_commission['text'] = '提现成功到银行卡';
                $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金银行卡提现，手动打款成功。");
                $res =  $distributor_commission_withdraw->where(array("id" => $id))->update(array("payment_date"=>time(),"status" => 3,"memo"=>'手动打款成功'));
            }
            if($commission_info['type']==2){//微信
                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到微信','withdraw_time'=>time()]);//提现成功
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                $data_commission['status'] =10;
                $data_commission['text'] = '提现成功到微信';//微信
                $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金微信提现，手动打款成功。");
                $res =  $distributor_commission_withdraw->where(array("id" => $id))->update(array("payment_date"=>time(),"status" => 3,"memo"=>'手动打款成功'));
            }
            if($commission_info['type']==3){//支付宝
                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到支付宝','withdraw_time'=>time()]);//提现成功
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                $data_commission['status'] =11;
                $data_commission['text'] = '提现成功到支付宝';//支付宝
                $this->addAccountWithdrawUserRecords($data_commission, 2, $id, "佣金支付宝提现，手动打款成功。");
                $res = $distributor_commission_withdraw ->where(array("id" => $id))->update(array("payment_date"=>time(),"status" =>3,"memo"=>'手动打款成功'));
            }
            if($commission_info['type']==4){//余额
                $data['data_id'] = $commission_info['withdraw_no'];
                $data['website_id'] = $commission_info['website_id'];
                $data['cash'] = $commission_info['cash'];
                $data['income_tax'] = $commission_info['income_tax'];
                $data['uid'] = $commission_info['uid'];
                $data['tax'] = $data_commission['tax'];
                $data['text'] = '提现到账户余额成功,可提现佣金减少,已提现佣金增加';
                runhook("Notify", "sendCustomMessage", ["messageType"=>"commission_payment","uid" => $commission_info['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到账户余额','withdraw_time'=>time()]);//提现成功
                $res = $this->addCommissionWithdraw($data);
            }
        }
        if($commission_info  && $status == 4){ // 平台拒绝打款，更新提现状态
            $data_commission['data_id'] = $commission_info['withdraw_no'];
            $data_commission['uid'] = $commission_info['uid'];
            $data_commission['website_id'] = $commission_info['website_id'];
            $data_commission['cash'] =$commission_info["cash"];
            $data_commission['commission'] =$commission_info["income_tax"];
            $data_commission['tax'] =$commission_info["tax"];
            $data_commission['msg'] =$memo;
            if($commission_info['type']==1 || $commission_info['type']==5){
                $data_commission['status'] = 23;
                $data_commission['text'] = '提现到银行卡，平台拒绝';
                runhook("Notify", "sendCustomMessage", ["messageType"=>"cash_withdrawal","uid" =>$data_commission['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到银行卡','handle_status'=>$memo,'handle_time'=>time(),'withdraw_time'=>$commission_info['ask_for_date']]);
                $this->addAccountWithdrawUserRecords($data_commission,2, $id,  $data_commission['text']);
                $res = $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => $status,"memo"=>$memo));
            }
            if($commission_info['type']==2){
                $data_commission['status'] = 16;
                $data_commission['text'] = '提现到微信，平台拒绝';
                runhook("Notify", "sendCustomMessage", ["messageType"=>"cash_withdrawal","uid" =>$data_commission['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到微信','handle_status'=>$memo,'handle_time'=>time(),'withdraw_time'=>$commission_info['ask_for_date']]);
                $this->addAccountWithdrawUserRecords($data_commission,2, $id,  $data_commission['text']);
                $res = $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => $status,"memo"=>$memo));
            }
            if($commission_info['type']==3){
                $data_commission['status'] = 17;
                $data_commission['text'] = '提现到支付宝，平台拒绝';
                runhook("Notify", "sendCustomMessage", ["messageType"=>"cash_withdrawal","uid" =>$data_commission['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到支付宝','handle_status'=>$memo,'handle_time'=>time(),'withdraw_time'=>$commission_info['ask_for_date']]);
                $this->addAccountWithdrawUserRecords($data_commission,2, $id,  $data_commission['text']);
                $res = $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => $status,"memo"=>$memo));
            }
            if($commission_info['type']==4){
                $data_commission['status'] = 18;
                $data_commission['text'] = '提现到余额，平台拒绝';
                runhook("Notify", "sendCustomMessage", ["messageType"=>"cash_withdrawal","uid" =>$data_commission['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到账户余额','handle_status'=>$memo,'handle_time'=>time(),'withdraw_time'=>$commission_info['ask_for_date']]);
                $this->addAccountWithdrawUserRecords($data_commission,2, $id,  $data_commission['text']);
                $res = $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => $status,"memo"=>$memo));
            }
        }
        if($commission_info  && $status == -1){ // 平台审核不通过，更新提现状态
            $data_commission['data_id'] = $commission_info['withdraw_no'];
            $data_commission['uid'] = $commission_info['uid'];
            $data_commission['website_id'] = $commission_info['website_id'];
            $data_commission['cash'] =$commission_info["cash"];
            $data_commission['commission'] =$commission_info["income_tax"];
            $data_commission['tax'] =$commission_info["tax"];
            $data_commission['msg'] =$memo;
            if($commission_info['type']==1 || $commission_info['type']==5){
                $data_commission['status'] =24;
                $data_commission['text'] = '提现到银行卡，平台审核不通过';//微信
                runhook("Notify", "sendCustomMessage", ["messageType"=>"cash_withdrawal","uid" =>$data_commission['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到银行卡','handle_status'=>$memo,'handle_time'=>time(),'withdraw_time'=>$commission_info['ask_for_date']]);
            }
            if($commission_info['type']==2){
                $data_commission['status'] =19;
                $data_commission['text'] = '提现到微信，平台审核不通过';//微信
                runhook("Notify", "sendCustomMessage", ["messageType"=>"cash_withdrawal","uid" =>$data_commission['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到账户余额','handle_status'=>$memo,'handle_time'=>time(),'withdraw_time'=>$commission_info['ask_for_date']]);
            }
            if($commission_info['type']==3){
                $data_commission['status'] = 20;
                $data_commission['text'] = '提现到支付宝，平台审核不通过';//支付宝
                runhook("Notify", "sendCustomMessage", ["messageType"=>"cash_withdrawal","uid" =>$data_commission['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到微信','handle_status'=>$memo,'handle_time'=>time(),'withdraw_time'=>$commission_info['ask_for_date']]);
            }
            if($commission_info['type']==4){
                $data_commission['status'] = 21;
                $data_commission['text'] = '提现到账户余额，平台审核不通过';//账户余额
                runhook("Notify", "sendCustomMessage", ["messageType"=>"cash_withdrawal","uid" =>$data_commission['uid'],'withdraw_money' =>$commission_info['cash'],"withdraw_type" => '提现到支付宝','handle_status'=>$memo,'handle_time'=>time(),'withdraw_time'=>$commission_info['ask_for_date']]);
            }
            $this->addAccountWithdrawUserRecords($data_commission,2, $id,  $data_commission['text']);
            $res =  $distributor_commission_withdraw->where(array("id" => $id))->update(array("status" => $status,"memo"=>$memo));
        }
        return $res;
    }
    /**
     * 平台审核提现
     */
    public function addAccountWithdrawUserRecords($data_commission, $account_type, $type_alis_id, $remark)
    {

        if($data_commission['status']==5){//自动审核通过微信待打款
            // 更新佣金账户情况
            $this->updateAccountWithdraw(5,$data_commission);
            //添加佣金账户流水
            $this->addCommissionDistribution($data_commission);
        }
        if($data_commission['status']==7){//自动审核通过支付宝待打款
            // 更新佣金账户情况
            $this->updateAccountWithdraw(7,$data_commission);
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==8){//自动审核通过银行卡待打款
            // 更新佣金账户情况
            $this->updateAccountWithdraw(8,$data_commission);
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==15){//自动审核通过余额待打款
            // 更新佣金账户情况
            $this->updateAccountWithdraw(15,$data_commission);
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==6){//自动提现到账户余额待审核
            // 更新佣金账户情况
            $this->updateAccountWithdraw(6,$data_commission);
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==12){//银行卡提现待审核
            // 更新佣金账户情况
            $this->updateAccountWithdraw(12,$data_commission);
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==13){//微信提现待审核
            // 更新佣金账户情况
            $this->updateAccountWithdraw(13,$data_commission);
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==14){//支付宝提现待审核
            // 更新佣金账户情况
            $this->updateAccountWithdraw(14,$data_commission);
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==10){//微信打款成功
            // 更新佣金账户情况
            $this->updateAccountWithdraw(10,$data_commission);
            //添加佣金账户流水
            $this->addCommissionDistribution($data_commission);
            $acount = new ShopAccount();
            // 更新提现总额的字段
            $acount->updateAccountUserWithdraw($data_commission['cash']);
            // 添加平台的整体资金流水
            $acount = new ShopAccount();
            if(abs($data_commission['tax'])>0){
                 $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现成功，个人所得税!",abs($data_commission['tax']), 24, $type_alis_id, '佣金提现到微信，个人所得税增加');
            }
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现成功!", abs($data_commission['cash']), 1, $type_alis_id, $remark);
        }
        if($data_commission['status']==-10){//微信打款失败
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==11){//支付宝打款成功
            // 更新佣金账户情况
            $this->updateAccountWithdraw(11,$data_commission);
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            $acount = new ShopAccount();
            // 更新提现总额的字段
            $acount->updateAccountUserWithdraw($data_commission['cash']);
            // 添加平台的整体资金流水
            if(abs($data_commission['tax'])>0){
                $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现成功，个人所得税!",abs($data_commission['tax']), 24, $type_alis_id, '佣金提现到支付宝，个人所得税增加');
            }
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现成功!", abs($data_commission['cash']), 2, $type_alis_id, $remark);
        }
        if($data_commission['status']==-11){//支付宝打款失败
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==9){//银行卡打款成功
            // 更新佣金账户情况
            $this->updateAccountWithdraw(9,$data_commission);
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            $acount = new ShopAccount();
            // 更新提现总额的字段
            $acount->updateAccountUserWithdraw($data_commission['cash']);
            // 添加平台的整体资金流水
            if(abs($data_commission['tax'])>0){
                $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现成功，个人所得税!",abs($data_commission['tax']), 24, $type_alis_id, '佣金提现到银行卡，个人所得税增加');
            }
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现成功!", abs($data_commission['cash']), 38, $type_alis_id, $remark);
            }
        if($data_commission['status']==-9){//银行卡打款失败
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
        }
        if($data_commission['status']==16){//平台拒绝微信打款
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            // 更新佣金账户情况
            $this->updateAccountWithdraw(-16,$data_commission);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现拒绝!", $data_commission['cash'], 3, $type_alis_id, $remark);
        }
        if($data_commission['status']==23){//平台拒绝银行卡打款
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            // 更新佣金账户情况
            $this->updateAccountWithdraw(-23,$data_commission);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现拒绝!", $data_commission['cash'], 3, $type_alis_id, $remark);
        }
        if($data_commission['status']==17){//平台拒绝支付宝打款
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            // 更新佣金账户情况
            $this->updateAccountWithdraw(-17,$data_commission);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现拒绝!", $data_commission['cash'], 3, $type_alis_id, $remark);
        }
        if($data_commission['status']==24){//银行卡提现平台审核不通过
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            // 更新佣金账户情况
            $this->updateAccountWithdraw(-24,$data_commission);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现审核不通过!", $data_commission['cash'], 6, $type_alis_id, $remark);
            }
        if($data_commission['status']==19){//微信提现平台审核不通过
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            // 更新佣金账户情况
            $this->updateAccountWithdraw(-19,$data_commission);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现审核不通过!", $data_commission['cash'], 6, $type_alis_id, $remark);
        }
        if($data_commission['status']==20){//支付宝提现平台审核不通过
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            // 更新佣金账户情况
            $this->updateAccountWithdraw(-20,$data_commission);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现审核不通过!", $data_commission['cash'], 6, $type_alis_id, $remark);
            }
        if($data_commission['status']==21){//提现到余额审核不通过
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            // 更新佣金账户情况
            $this->updateAccountWithdraw(-21,$data_commission);
            // 添加平台的整体资金流水
            $acount = new ShopAccount();
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现审核不通过!", $data_commission['cash'], 6, $type_alis_id, $remark);
        }
        if($data_commission['status']==18){//平台拒绝余额打款
            $this->addCommissionDistribution($data_commission);//添加佣金账户流水
            // 更新佣金账户情况
            $this->updateAccountWithdraw(-18,$data_commission);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_commission['uid'], "佣金提现拒绝!", $data_commission['cash'], 3, $type_alis_id, $remark);
        }
    }
    /**
     * 平台审核提现，更新佣金账户
     */
    public function updateAccountWithdraw($status,$data_commission){
        $commission_account = new VslDistributorAccountModel();
        $commission_account_info = $commission_account->getInfo(['uid'=>$data_commission['uid']],'*');
        try{
            if($status==5 || $status==6 || $status==7 || $status==13 || $status==14 || $status==15 || $status==12 || $status==8){//微信支付宝余额提现手动审核和自动审核
                $data3 = array(
                    'commission'=>$commission_account_info['commission']-abs($data_commission['commission'])-abs($data_commission['tax']),//可提现佣金减少
                    'freezing_commission'=>$commission_account_info['freezing_commission']+abs($data_commission['commission'])+abs($data_commission['tax']),//冻结佣金增加
                );
            }
            if($status==9 || $status==10 || $status==11){//微信支付宝提现成功
                $data3 = array(
                    'withdrawals'=>$commission_account_info['withdrawals']+abs($data_commission['cash'])+abs($data_commission['tax']),//已提现佣金增加
                    'freezing_commission'=>$commission_account_info['freezing_commission']-abs($data_commission['commission'])-abs($data_commission['tax']),//冻结佣金减少
                    'tax'=>$commission_account_info['tax']+abs($data_commission['tax'])
                );
            }
            if($status==-10 || $status==-11 || $status==-16 || $status==-17 || $status==-19 || $status==-20 || $status==-21 || $status==-18 || $status==-23 || $status==-24){//微信支付宝提现失败或者拒绝打款审核不通过
                $data3 = array(
                    'commission'=>$commission_account_info['commission']+abs($data_commission['commission'])+abs($data_commission['tax']),//可提现佣金增加
                    'freezing_commission'=>$commission_account_info['freezing_commission']-abs($data_commission['commission'])-abs($data_commission['tax'])//冻结佣金减少
                );
            }
            $commission_account->save($data3,['uid'=>$data_commission['uid']]);//更新佣金账户
            $commission_account->commit();
            return 1;
        }catch (\Exception $e)
        {
            $commission_account->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 后台佣金流水列表
     */
    public function getAccountList($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $commission_account = new VslDistributorAccountRecordsViewModel();
        $list = $commission_account->getViewList($page_index, $page_size, $condition, 'nmar.create_time desc');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if($v['from_type']!=1 && $v['from_type']!=2 && $v['from_type']!=3 && $v['from_type']!=22){
                    if($v["from_type"]==10){
                        $status_name = '提现到微信，成功';
                    }
                    if($v["from_type"]==9){
                        $status_name = '提现到银行卡，成功';
                    }
                    if($v["from_type"]==11){
                        $status_name = '提现到支付宝，成功';
                    }
                    if($v["from_type"]==-11){
                        $status_name = '提现到支付宝，打款失败';
                    }
                    if($v["from_type"]==-9){
                        $status_name = '提现到银行卡，打款失败';
                    }
                    if($v["from_type"]==-10){
                        $status_name = '提现到微信，打款失败';
                    }
                    if($v["from_type"]==12){
                        $status_name = '提现到银行卡，待审核';
                    }
                    if($v["from_type"]==13){
                        $status_name = '提现到微信，待审核';
                    }
                    if($v["from_type"]==14){
                        $status_name = '提现到支付宝，待审核';
                    }
                    if($v["from_type"]==15){
                        $status_name = '提现到账户余额，待打款';
                    }
                    if( $v["from_type"]==6){
                        $status_name = '提现到账户余额，待审核';
                    }
                    if( $v["from_type"]==5){
                        $status_name = '提现到微信，待打款';
                    }
                    if( $v["from_type"]==4){
                        $status_name = '提现到账户余额，成功';
                    }
                    if( $v["from_type"]==7){
                        $status_name = '提现到银行卡，待打款';
                    }
                    if( $v["from_type"]==8){
                        $status_name = '提现到支付宝，待打款';
                    }
                    if( $v["from_type"]==16){
                        $status_name = '提现到微信，已拒绝';
                    }
                    if( $v["from_type"]==17){
                        $status_name = '提现到支付宝，已拒绝';
                    }
                    if( $v["from_type"]==18){
                        $status_name = '提现到账户余额，已拒绝';
                    }
                    if( $v["from_type"]==19){
                        $status_name = '提现到微信，审核不通过';
                    }
                    if( $v["from_type"]==20){
                        $status_name = '提现到支付宝，审核不通过';
                    }
                    if( $v["from_type"]==21){
                        $status_name = '提现到账户余额，不通过';
                    }
                    if( $v["from_type"]==23){
                        $status_name = '提现到银行卡，已拒绝';
                    }
                    if( $v["from_type"]==24){
                        $status_name = '提现到银行卡，审核不通过';
                    }
                    $list['data'][$k]['type_name'] = $status_name;
                    $list['data'][$k]['change_money'] = (-1)*(abs($list['data'][$k]['commission'])+abs($list['data'][$k]['tax']));
                }else{
                    if($v['from_type']==1){
                        $list['data'][$k]['type_name'] = '订单完成';
                        $list['data'][$k]['commission'] = '+'.abs($list['data'][$k]['commission']);
                    }
                    if($v['from_type']==2){
                        $list['data'][$k]['type_name'] = '订单退款完成';
                        $list['data'][$k]['commission'] = '-'.abs($list['data'][$k]['commission']);
                    }
                    if($v['from_type']==3){
                        $list['data'][$k]['type_name'] = '订单支付完成';
                        $list['data'][$k]['commission'] = '+'.abs($list['data'][$k]['commission']);
                    }
                    if($v['from_type']==22){
                        $list['data'][$k]['type_name'] = '下级分销商升级，获得推荐奖';
                        $list['data'][$k]['commission'] = '+'.abs($list['data'][$k]['commission']);
                    }
                    $list['data'][$k]['change_money'] = $list['data'][$k]['commission'];
                }
                if(empty($list['data'][$k]['user_name'])){
                    $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
                $list['data'][$k]['text'] = str_replace("冻结佣金",$this->fre_commission,$list['data'][$k]['text']);
                $list['data'][$k]['text'] = str_replace("可提现佣金",$this->wit_commission,$list['data'][$k]['text']);
                $list['data'][$k]['text'] = str_replace("已提现佣金",$this->wits_commission,$list['data'][$k]['text']);
                $list['data'][$k]['user_info'] = ($v['nick_name'])?$v['nick_name']:($v['user_name']?$v['user_name']:($v['user_tel']?$v['user_tel']:$v['uid']));
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            }
        }
        return $list;
    }
    /**
     * 前台佣金流水列表
     */
    public function getAccountLists($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $commission_account = new VslDistributorAccountRecordsViewModel();
        $list = $commission_account->getViewList($page_index, $page_size, $condition, 'nmar.create_time desc');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if($v['from_type']!=1 && $v['from_type']!=2 && $v['from_type']!=3 && $v['from_type']!=22){
                    if($v["from_type"]==10){
                        $status_name = '提现到微信，成功';
                    }
                    if($v["from_type"]==9){
                        $status_name = '提现到银行卡，成功';
                    }
                    if($v["from_type"]==11){
                        $status_name = '提现到支付宝，成功';
                    }
                    if($v["from_type"]==-11){
                        $status_name = '提现到支付宝，处理中';
                    }
                    if($v["from_type"]==-9){
                        $status_name = '提现到银行卡，处理中';
                    }
                    if($v["from_type"]==-10){
                        $status_name = '提现到微信，处理中';
                    }
                    if($v["from_type"]==12){
                        $status_name = '提现到银行卡，处理中';
                    }
                    if($v["from_type"]==13){
                        $status_name = '提现到微信，处理中';
                    }
                    if($v["from_type"]==14){
                        $status_name = '提现到支付宝，处理中';
                    }
                    if($v["from_type"]==15){
                        $status_name = '提现到账户余额，处理中';
                    }
                    if( $v["from_type"]==6){
                        $status_name = '提现到账户余额，处理中';
                    }
                    if( $v["from_type"]==5){
                        $status_name = '提现到微信，处理中';
                    }
                    if( $v["from_type"]==4){
                        $status_name = '提现到账户余额，成功';
                    }
                    if( $v["from_type"]==7){
                        $status_name = '提现到银行卡，处理中';
                    }
                    if( $v["from_type"]==8){
                        $status_name = '提现到支付宝，处理中';
                    }
                    if( $v["from_type"]==16){
                        $status_name = '提现到微信，失败';
                    }
                    if( $v["from_type"]==17){
                        $status_name = '提现到支付宝，失败';
                    }
                    if( $v["from_type"]==18){
                        $status_name = '提现到账户余额，失败';
                    }
                    if( $v["from_type"]==19){
                        $status_name = '提现到微信，失败';
                    }
                    if( $v["from_type"]==20){
                        $status_name = '提现到支付宝，失败';
                    }
                    if( $v["from_type"]==21){
                        $status_name = '提现到账户余额，失败';
                    }
                    if( $v["from_type"]==23){
                        $status_name = '提现到银行卡，失败';
                    }
                    if( $v["from_type"]==24){
                        $status_name = '提现到银行卡，失败';
                    }
                    $list['data'][$k]['type_name'] = $status_name;
                    $list['data'][$k]['type'] = 1;
                    $list['data'][$k]['change_money'] = (-1)*(abs($list['data'][$k]['commission'])+abs($list['data'][$k]['tax']));
                }else{
                    $list['data'][$k]['type'] = 0;
                    if($v['from_type']==1){
                        $list['data'][$k]['type_name'] = '订单完成';
                        $list['data'][$k]['commission'] = '+'.abs($list['data'][$k]['commission']);
                    }
                    if($v['from_type']==2){
                        $list['data'][$k]['type_name'] = '订单退款完成';
                        $list['data'][$k]['commission'] = '-'.abs($list['data'][$k]['commission']);
                    }
                    if($v['from_type']==3){
                        $list['data'][$k]['type_name'] = '订单支付完成';
                        $list['data'][$k]['commission'] = '+'.abs($list['data'][$k]['commission']);
                    }
                    if($v['from_type']==22){
                        $list['data'][$k]['type_name'] = '下级分销商升级，获得推荐奖';
                        $list['data'][$k]['commission'] = '+'.abs($list['data'][$k]['commission']);
                    }
                    $list['data'][$k]['change_money'] = $list['data'][$k]['commission'] ;
                }
                if(empty($list['data'][$k]['user_name'])){
                    $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
                $list['data'][$k]['type_name'] = str_replace("分销商",$this->distributor,$list['data'][$k]['type_name']);
                $list['data'][$k]['text'] = str_replace("冻结佣金",$this->fre_commission,$list['data'][$k]['text']);
                $list['data'][$k]['text'] = str_replace("可提现佣金",$this->wit_commission,$list['data'][$k]['text']);
                $list['data'][$k]['text'] = str_replace("已提现佣金",$this->wits_commission,$list['data'][$k]['text']);
                $list['data'][$k]['user_info'] = ($v['nick_name'])?$v['nick_name']:($v['user_name']?$v['user_name']:($v['user_tel']?$v['user_tel']:$v['uid']));
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            }
        }
        return $list;
    }
    /**
     * 佣金提现列表
     */
    public function getCommissionWithdrawList($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $commission_withdraw = new VslDistributorCommissionWithdrawModel();
        $list = $commission_withdraw->getViewList($page_index, $page_size, $condition, 'nmar.ask_for_date desc');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if(empty($list['data'][$k]['user_name'])){
                   $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
                $list['data'][$k]['ask_for_date'] = date('Y-m-d H:i:s',$v['ask_for_date']);
                if($v['payment_date']){
                    $list['data'][$k]['payment_date'] = date('Y-m-d H:i:s',$v['payment_date']);
                }else{
                    $list['data'][$k]['payment_date'] = '未到账';
                }
                $list['data'][$k]['user_info'] = ($v['nick_name'])?$v['nick_name']:($v['user_name']?$v['user_name']:($v['user_tel']?$v['user_tel']:$v['uid']));
            }
        }
        return $list;
    }
    public function getMemberWithdrawalCount($condition)
    {
        $commission_withdraw = new VslDistributorCommissionWithdrawModel();
        $user_sum = $commission_withdraw->where($condition)->count();
        if ($user_sum) {
            return $user_sum;
        } else {
            return 0;
        }
    }
    /**
     * 佣金提现详情
     */
    public function commissionWithdrawDetail($id)
    {
        $commission_withdraw = new VslDistributorCommissionWithdrawModel();
        $info = $commission_withdraw->getInfo(['id'=>$id],'*');
        $user = new userModel();
        $user_info= $user->getInfo(['uid'=>$info['uid']],'user_name,nick_name');
        if($user_info['user_name']){
            $info['user_name'] = $user_info['user_name'];//获取会员名称
        }else{
            $info['user_name'] = $user_info['nick_name'];//获取会员名称
        }
        $info['ask_for_date'] = date('Y-m-d H:i:s',$info['ask_for_date']);
        if($info['payment_date']>0){
            $info['payment_date'] = date('Y-m-d H:i:s',$info['payment_date']);
        }else{
            $info['payment_date'] = '未到账';
        }
        if($info['status']==-1){
            $info['status']='审核不通过';
        }elseif($info['status']==1){
            $info['status']='待审核';
        }elseif($info['status']==2){
            $info['status']='待打款';
        }elseif($info['status']==3){
            $info['status']='已打款';
        }elseif($info['status']==4){
            $info['status']='拒绝打款';
        }elseif($info['status']==5){
            $info['status']='打款失败';
        }
        if($info['type']==1 || $info['type']==5){
            $info['type_name']='银行卡';
        }elseif($info['type']==2){
            $info['type_name']='微信';
        }elseif($info['type']==3){
            $info['type_name']='支付宝';
        }elseif($info['type']==4){
            $info['type_name']='账户余额';
            $info['account_number']='账户余额';
        }
        return $info;
    }
    /**
     * 获取本人信息
     */
    public function getmyinfos($uid,$website_id){
        $list = [];
        $list['number1'] = 0;
        $list['number2'] = 0;
        $list['number3'] = 0;
        $user_infos = $this->getDistributorUser($uid,$website_id);
       
        if($user_infos){
            $list['number1'] = $user_infos['number1'];
            $list['number2'] = $user_infos['number2'];
            $list['number3'] = $user_infos['number3'];
        }
        return $list;
    }
    /**
     * 获取当前分销商团队
     */
    public function getTeamList($type,$uid,$website_id,$index, $page_size)
    {
        $member = new VslMemberModel();
        $user = new UserModel();
        $distributor_level = new DistributorLevelModel();
        $account = new VslDistributorAccountModel();
        //        $pattern=$this->getDistributionSite($website_id)['distribution_pattern'];//分销模式
        $list = [];
        if($type==1){
            $first_uid = $member->Query(['referee_id'=>$uid,'isdistributor'=>2,'website_id'=>$website_id],'uid'); //获取一级团队
            if($first_uid){
                $list = $member->pageQuery($index,$page_size,['website_id' => $website_id,'uid'=>['in',implode(',',$first_uid)],'isdistributor' =>2],'become_distributor_time desc','member_name,distributor_level_id,uid');
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['teamcount'] = $this->getDistributorTeam($v['uid'],$website_id);
                    //获取团队所有分销商
                    //获取所有下级
                    $list['data'][$k]['all_child'] = 0;
                    $all_child = $this->getAllChild($v['uid'],$website_id);
                    if($all_child){
                        $total_child = $member->Query(['isdistributor'=>2,'uid'=>['in',implode(',',$all_child)]],'uid');
                        if($total_child){
                            $list['data'][$k]['all_child'] = count($total_child);
                        }
                    }
                    //获取团队三级内分销商
                    //获取下线客户一级
                    $list['data'][$k]['user_count'] = 0;
                    $list['data'][$k]['agentcount'] = 0;
                    $user_list = $this->getDistributorUser($v['uid'],$website_id);
                    if($user_list){
                        $list['data'][$k]['user_count'] = $user_list['user_count'];
                        $list['data'][$k]['agentcount'] = $user_list['agentcount'];
                    }
                    $user_info = $user->getInfo(['uid'=>$v['uid']],'user_name,nick_name');
                    if($user_info['user_name']){
                        $list['data'][$k]['member_name'] = $user_info['user_name'];
                    }else{
                        $list['data'][$k]['member_name'] = $user_info['nick_name'];
                    }
                    $distributor_level_name= $distributor_level->getInfo(['id'=>$v['distributor_level_id']],'level_name');
                    $list['data'][$k]['distributor_level_name'] = $distributor_level_name['level_name'];
                    $commission_account = $account->getInfo(['uid'=>$v['uid']],'*');
                    $list['data'][$k]['commission'] = $commission_account['commission']+$commission_account['withdrawals']+$commission_account['freezing_commission'];
                }
            }
            return $list;
        }
        if($type==2){
            $first_uid = $member->Query(['referee_id'=>$uid,'isdistributor'=>2,'website_id'=>$website_id],'uid'); //获取一级团队
            if($first_uid){
                $second_uid = $member->Query(['referee_id'=>['in',implode(',',$first_uid)],'isdistributor'=>2,'website_id'=>$website_id],'uid');//获取二级团队
                if($second_uid){
                    $list = $member->pageQuery($index,$page_size,['website_id' => $website_id,'uid'=>['in',implode(',',$second_uid)],'isdistributor' =>2],'become_distributor_time desc','member_name,distributor_level_id,uid');
                    foreach ($list['data'] as $k=>$v){
                        $list['data'][$k]['teamcount'] = $this->getDistributorTeam($v['uid'],$website_id);
                        //获取团队所有分销商
                        //获取所有下级
                        $list['data'][$k]['all_child'] = 0;
                        $all_child = $this->getAllChild($v['uid'],$website_id);
                        if($all_child){
                            $total_child = $member->Query(['isdistributor'=>2,'uid'=>['in',implode(',',$all_child)]],'uid');
                            if($total_child){
                                $list['data'][$k]['all_child'] = count($total_child);
                            }
                        }
                        //获取团队三级内分销商
                        //获取下线客户一级
                        $list['data'][$k]['user_count'] = 0;
                        $list['data'][$k]['agentcount'] = 0;
                        $user_list = $this->getDistributorUser($v['uid'],$website_id);
                        if($user_list){
                            $list['data'][$k]['user_count'] = $user_list['user_count'];
                            $list['data'][$k]['agentcount'] = $user_list['agentcount'];
                        }
                        $user_info = $user->getInfo(['uid'=>$v['uid']],'user_name,nick_name');
                        if($user_info['user_name']){
                            $list['data'][$k]['member_name'] = $user_info['user_name'];
                        }else{
                            $list['data'][$k]['member_name'] = $user_info['nick_name'];
                        }
                        $distributor_level_name= $distributor_level->getInfo(['id'=>$v['distributor_level_id']],'level_name');
                        $list['data'][$k]['distributor_level_name'] = $distributor_level_name['level_name'];
                        $commission_account = $account->getInfo(['uid'=>$v['uid']],'*');
                        $list['data'][$k]['commission'] = $commission_account['commission']+$commission_account['withdrawals']+$commission_account['freezing_commission'];
                    }
                }
            }
            return $list;
        }
        if($type==3){
            $first_uid = $member->Query(['referee_id' => $uid, 'isdistributor' => 2, 'website_id' => $website_id], 'uid'); //获取一级团队
            if ($first_uid) {
                $second_uid = $member->Query(['referee_id' => ['in', implode(',', $first_uid)], 'isdistributor' => 2, 'website_id' => $website_id], 'uid');//获取二级团队
                if ($second_uid) {
                    $third_uid = $member->Query(['referee_id' => ['in', implode(',', $second_uid)], 'isdistributor' => 2, 'website_id' => $website_id], 'uid');//获取三级团队
                    if ($third_uid) {
                        $list = $member->pageQuery($index, $page_size, ['website_id' => $website_id, 'uid' => ['in', implode(',', $third_uid)]], 'become_distributor_time desc', 'member_name,distributor_level_id,uid');
                        foreach ($list['data'] as $k => $v) {
                            $list['data'][$k]['teamcount'] = $this->getDistributorTeam($v['uid'], $website_id);
                            //获取团队所有分销商
                            //获取所有下级
                            $list['data'][$k]['all_child'] = 0;
                            $all_child = $this->getAllChild($v['uid'],$website_id);
                            if($all_child){
                                $total_child = $member->Query(['isdistributor'=>2,'uid'=>['in',implode(',',$all_child)]],'uid');
                                if($total_child){
                                    $list['data'][$k]['all_child'] = count($total_child);
                                }
                            }
                            //获取团队三级内分销商
                            //获取下线客户一级
                            $list['data'][$k]['user_count'] = 0;
                            $list['data'][$k]['agentcount'] = 0;
                            $user_list = $this->getDistributorUser($v['uid'],$website_id);
                            if($user_list){
                                $list['data'][$k]['user_count'] = $user_list['user_count'];
                                $list['data'][$k]['agentcount'] = $user_list['agentcount'];
                            }
                            $user_info = $user->getInfo(['uid' => $v['uid']], 'user_name,nick_name');
                            if ($user_info['user_name']) {
                                $list['data'][$k]['member_name'] = $user_info['user_name'];
                            } else {
                                $list['data'][$k]['member_name'] = $user_info['nick_name'];
                            }
                            $distributor_level_name = $distributor_level->getInfo(['id' => $v['distributor_level_id']], 'level_name');
                            {
                                $list['data'][$k]['distributor_level_name'] = $distributor_level_name['level_name'];
                                $commission_account = $account->getInfo(['uid' => $v['uid']], '*');
                                $list['data'][$k]['commission'] = $commission_account['commission'] + $commission_account['withdrawals'] + $commission_account['freezing_commission'];
                            }
                        }
                    }
                }
            }
            return $list;
        }
//        if($pattern==1){
//            $first_uid = $member->Query(['referee_id'=>$uid,'isdistributor'=>2,'website_id'=>$website_id],'uid'); //获取一级团队
//            if($first_uid){
//                $list = $member->pageQuery($index,$page_size,['website_id' => $website_id,'uid'=>['in',implode(',',$first_uid)],'isdistributor' =>2],'become_distributor_time desc','member_name,distributor_level_id,uid');
//                foreach ($list['data'] as $k=>$v){
//                    $list['data'][$k]['teamcount'] = $this->getDistributorTeam($v['uid'],$website_id);
//                    $user_info = $user->getInfo(['uid'=>$v['uid']],'user_name,nick_name');
//                    if($user_info['user_name']){
//                        $list['data'][$k]['member_name'] = $user_info['user_name'];
//                    }else{
//                        $list['data'][$k]['member_name'] = $user_info['nick_name'];
//                    }
//                    $distributor_level_name= $distributor_level->getInfo(['id'=>$v['distributor_level_id']],'level_name');
//                    $list['data'][$k]['distributor_level_name'] = $distributor_level_name['level_name'];
//                    $commission_account = $account->getInfo(['uid'=>$v['uid']],'*');
//                    $list['data'][$k]['commission'] = $commission_account['commission']+$commission_account['withdrawals']+$commission_account['freezing_commission'];
//                }
//            }
//        }
//        if($pattern==2){
//            $second_uid= [];
//            $first_uid = $member->Query(['referee_id'=>$uid,'isdistributor'=>2,'website_id'=>$website_id],'uid'); //获取一级团队
//            if($first_uid){
//                $second_uid = $member->Query(['referee_id'=>['in',implode(',',$first_uid)],'isdistributor'=>2,'website_id'=>$website_id],'uid');//获取二级团队
//                if($second_uid){
//                    $second_uid = array_merge($first_uid,$second_uid);
//                }else{
//                    $second_uid = $first_uid;
//                }
//            }
//            if($second_uid){
//                $list = $member->pageQuery($index,$page_size,['website_id' => $website_id,'uid'=>['in',implode(',',$second_uid)],'isdistributor' =>2],'become_distributor_time desc','member_name,distributor_level_id,uid');
//                foreach ($list['data'] as $k=>$v){
//                    $list['data'][$k]['teamcount'] = $this->getDistributorTeam($v['uid'],$website_id);
//                    $user_info = $user->getInfo(['uid'=>$v['uid']],'user_name,nick_name');
//                    if($user_info['user_name']){
//                        $list['data'][$k]['member_name'] = $user_info['user_name'];
//                    }else{
//                        $list['data'][$k]['member_name'] = $user_info['nick_name'];
//                    }
//                    $distributor_level_name= $distributor_level->getInfo(['id'=>$v['distributor_level_id']],'level_name');
//                    $list['data'][$k]['distributor_level_name'] = $distributor_level_name['level_name'];
//                    $commission_account = $account->getInfo(['uid'=>$v['uid']],'*');
//                    $list['data'][$k]['commission'] = $commission_account['commission']+$commission_account['withdrawals']+$commission_account['freezing_commission'];
//                }
//            }
//        }
//        if($pattern==3){
//            $third_uid = [];
//            $first_uid = $member->Query(['referee_id' => $uid, 'isdistributor' => 2, 'website_id' => $website_id], 'uid'); //获取一级团队
//            if ($first_uid) {
//                $second_uid = $member->Query(['referee_id' => ['in', implode(',', $first_uid)], 'isdistributor' => 2, 'website_id' => $website_id], 'uid');//获取二级团队
//                if ($second_uid) {
//                    $third_uid = $member->Query(['referee_id' => ['in', implode(',', $second_uid)], 'isdistributor' => 2, 'website_id' => $website_id], 'uid');//获取三级团队
//                    $second_uid = array_merge($first_uid, $second_uid);
//                    if ($third_uid) {
//                        $third_uid = array_merge($second_uid, $third_uid);
//                    } else {
//                        $third_uid = $second_uid;
//                    }
//                } else {
//                    $third_uid = $first_uid;
//                }
//            }
//            if ($third_uid) {
//                $list = $member->pageQuery($index, $page_size, ['website_id' => $website_id, 'uid' => ['in', implode(',', $third_uid)]], 'become_distributor_time desc', 'member_name,distributor_level_id,uid');
//                foreach ($list['data'] as $k => $v) {
//                    $list['data'][$k]['teamcount'] = $this->getDistributorTeam($v['uid'], $website_id);
//                    $user_info = $user->getInfo(['uid' => $v['uid']], 'user_name,nick_name');
//                    if ($user_info['user_name']) {
//                        $list['data'][$k]['member_name'] = $user_info['user_name'];
//                    } else {
//                        $list['data'][$k]['member_name'] = $user_info['nick_name'];
//                    }
//                    $distributor_level_name = $distributor_level->getInfo(['id' => $v['distributor_level_id']], 'level_name');
//                    {
//                        $list['data'][$k]['distributor_level_name'] = $distributor_level_name['level_name'];
//                        $commission_account = $account->getInfo(['uid' => $v['uid']], '*');
//                        $list['data'][$k]['commission'] = $commission_account['commission'] + $commission_account['withdrawals'] + $commission_account['freezing_commission'];
//                    }
//                }
//            }
//        }
        return $list;
    }
    /**
     * 获取当前分销商的团队分销商人数（3级内）
     */
    public function getDistributorUser($uid,$website_id){
            
        $list = $this->getDistributionSite($website_id);
        $distributor = new VslMemberModel();
        $result['number1'] = 0;
        $result['agentcount'] = 0;
        $result['user_count'] = 0;
        $result['user_count2'] = 0;
        $result['user_count3'] = 0;
        $result['number2'] = 0;
        $result['number3'] = 0;
        if(1 <= $list['distribution_pattern']){
            $lower_id = $distributor->Query(['website_id'=>$website_id,'referee_id'=>$uid],'uid');//一级
            $idslevel1 = $distributor->Query(['isdistributor'=>2,'referee_id'=>$uid],'uid');
            if($idslevel1){
                $result['number1'] = count($idslevel1);//一级分销商总人数
                $result['agentcount'] += $result['number1'];
            }
            //获取1级客户数
            $id1 = $distributor->Query(['referee_id'=>$uid,'isdistributor' => ['neq', 2]],'uid');
            if($id1){
                $result['user_count'] = count($id1);//一级分销商总人数
            }
        }
        if(2 <= $list['distribution_pattern']){
            if($lower_id){
                $lower_id1 = $distributor->Query(['website_id'=>$website_id,'referee_id'=>['in',implode(',',$lower_id)]],'uid');//二级
                $idslevel2 = $distributor->Query(['isdistributor'=>2,'referee_id'=>['in',implode(',',$lower_id)]],'uid');
                if($idslevel2){
                    $result['number2'] = count($idslevel2);//二级分销商总人数
                    $result['agentcount'] += $result['number2'];
                }
                //获取2级客户数
                $id2 = $distributor->Query(['referee_id'=>['in',implode(',',$lower_id)],'isdistributor' => ['neq', 2]],'uid');
                if($id2){
                    $result['user_count2'] = count($id2);//一级分销商总人数
                }
            }
        }
        if(3 <= $list['distribution_pattern']){
            if($lower_id1){
                $lower_id2 = $distributor->Query(['website_id'=>$website_id,'referee_id'=>['in',implode(',',$lower_id1)]],'uid');//三级
                $idslevel3 = $distributor->Query(['isdistributor'=>2,'referee_id'=>['in',implode(',',$lower_id1)]],'uid');
                if($idslevel3){
                    $result['number3'] = count($idslevel3);//三级分销商总人数
                    $result['agentcount'] += $result['number3'];
                }
                //获取2级客户数
                $id3 = $distributor->Query(['referee_id'=>['in',implode(',',$lower_id1)],'isdistributor' => ['neq', 2]],'uid');
                if($id3){
                    $result['user_count3'] = count($id3);//一级分销商总人数
                }
            }
        }
        
        return $result;
    }
    /**
     * 获取当前分销商的团队人数
     */
    public function getDistributorTeam($uid,$website_id){
        $member = new VslMemberModel();
        $config = $this->getDistributionSite($website_id);
        if($config['distribution_pattern']==1){//一级分销
            $lower_id = $member->Query(['website_id'=>$website_id,'referee_id'=>$uid],'uid');//一级
            if($lower_id){
                $number1 = count($lower_id);
                return $number1;
            }else{
                return 0;
            }
        }
        if($config['distribution_pattern']==2){//二级分销
            $lower_id = $member->Query(['website_id'=>$website_id,'referee_id'=>$uid],'uid');//一级
            if($lower_id){
                $number1 = count($lower_id);
                $lower_id1 = $member->Query(['website_id'=>$website_id,'referee_id'=>['in',implode(',',$lower_id)]],'uid');//二级
                if($lower_id1){
                    $number2 = count($lower_id1);
                    return $number1+$number2;
                }else{
                    return $number1;
                }
            }else{
                return 0;
            }
        }
        if($config['distribution_pattern']==3){//三级分销
            $lower_id = $member->Query(['website_id'=>$website_id,'referee_id'=>$uid],'uid');//一级
            if($lower_id){
                $number1 = count($lower_id);
                $lower_id1 = $member->Query(['website_id'=>$website_id,'referee_id'=>['in',implode(',',$lower_id)]],'uid');//二级
                if($lower_id1){
                    $number2 = count($lower_id1);
                    $lower_id2 = $member->Query(['website_id'=>$website_id,'referee_id'=>['in',implode(',',$lower_id1)]],'uid');//三级
                    if($lower_id2){
                        $number3 = count($lower_id2);
                        return $number1+$number2+$number3;
                    }else{
                        return $number1+$number2;
                    }
                }else{
                    return $number1;
                }
            }else{
                return 0;
            }
        }

    }
    /**
     * 获取商品的分销佣金
     */
    public function getGoodsCommission($website_id,$goods_id,$uid,$price){
        $addonsConfigService = new AddonsConfigService();
        $info1 = $addonsConfigService ->getAddonsConfig("distribution",$website_id);//基本设置
        $base_info = json_decode($info1['value'], true);
        $ConfigService = new ConfigService();
        $info2 = $ConfigService ->getConfig(0,"SETTLEMENT",$website_id);
        $set_infos = json_decode($info2['value'], true);
        $commission_calculation = $set_infos['commission_calculation'];//计算节点（商品价格）
        $set_info = $this->getAgreementSite($website_id);
        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id'=>$goods_id]);
        $member = new VslMemberModel();
        $level_id = $member->getInfo(['uid'=>$uid],'distributor_level_id')['distributor_level_id'];
        $level = new DistributorLevelModel();
        $level_info = $level->getInfo(['id' => $level_id]);
        $distribution_rule = getAddons('distribution',$website_id);
        $cost_price = $goods_info['cost_price'];//商品成本价
        $promotion_price = $goods_info ['promotion_price'];//商品销售价
        $original_price = $goods_info ['market_price'];//商品原价
        $profit_price = $price-$cost_price;//商品利润价
        if($profit_price<0){
            $profit_price = 0;
        }
        $real_price = 0;
        if ($commission_calculation == 1) {//实际付款金额
            $real_price = $price;
        }elseif($commission_calculation == 2) {//商品原价
            $real_price = $original_price;
        }elseif($commission_calculation == 3) {//商品销售价
            $real_price = $promotion_price;
        }elseif($commission_calculation == 4) {//商品成本价
            $real_price = $cost_price;
        }elseif($commission_calculation == 5) {//商品利润价
            $real_price = $profit_price;
        }
        if($distribution_rule && $base_info['purchase_type']==1 && $set_info && $set_info['distribution_label'] && $set_info['distribution_label'] ==1){
            if($goods_info['is_distribution']==1 ){//该商品参与分销
                $commission1= '';
                $commission11= '';
                $commissionA= '';
                $commissionA1= '';
                $commissionA11= '';
                $point1= '';
                $point11= '';
                $pointA= '';
                $pointA1= '';
                $pointA11= '';
                if($goods_info['distribution_rule']==1){//有独立分销规则
                    $goods_info['distribution_rule_val'] = json_decode(htmlspecialchars_decode($goods_info['distribution_rule_val']),true);
                    if($goods_info['distribution_rule_val']['level_rule'] && $goods_info['distribution_rule_val']['level_rule']==1){//固定等级比例
                        $level_rule_ids = $goods_info['distribution_rule_val']['level_ids'];
                        if($goods_info['distribution_rule_val']['recommend_type']==1){//等级佣金比例设置
                            $level_first_rebate = $goods_info['distribution_rule_val']['first_rebate'];
                            $level_first_point = $goods_info['distribution_rule_val']['first_point'];
                        }else{//固定佣金
                            $level_first_rebate1 = $goods_info['distribution_rule_val']['first_rebate1'];
                            $level_first_point1 = $goods_info['distribution_rule_val']['first_point1'];
                        }
                        if($level_rule_ids && in_array($level_id,$level_rule_ids)){//有特定等级返佣设置
                            foreach ($level_rule_ids as $k=>$v){
                                if($v==$level_id){
                                    if($goods_info['distribution_rule_val']['recommend_type']==1){//比例返佣
                                        $commission1 =  $level_first_rebate[$k];
                                        $point1 = $level_first_point[$k];
                                        $commission11 = '';
                                        $point11 = '';
                                    }else{
                                        $commission1 = '';
                                        $point1 = '';
                                        $commission11 =  $level_first_rebate1[$k];
                                        $point11 = $level_first_point1[$k];
                                    }
                                }
                            }
                        }
                    }else{
                        if($goods_info['distribution_rule_val']['recommend_type']==1){//佣金比例设置
                            $commission1 = $goods_info['distribution_rule_val']['first_rebate'];
                            $point1 = $goods_info['distribution_rule_val']['first_point'];
                            $commission11 = '';
                            $point11 = '';
                        }else{//固定佣金
                            $commission1 = '';
                            $point1 = '';
                            $commission11 = $goods_info['distribution_rule_val']['first_rebate1'];
                            $point11 = $goods_info['distribution_rule_val']['first_point1'];
                        }
                    }
                }
                if ($commission11=='' && $commission1!='') {//活动比例一级返佣
                    $commissionA1 = $commission1 / 100;
                }
                if($commission1=='' && $commission11!=''){//活动固定一级返佣
                    $commissionA11 = $commission11;
                }
                if($commission1=='' && $commission11==''){
                    if($level_info['recommend_type']==1){//等级比例一级返佣
                        $commissionA1 = $level_info['commission1'] / 100;
                    }else{//等级固定一级返佣
                        $commissionA11 = $level_info['commission11'];
                    }
                }
                if ($point11=='' && $point1!='') {//活动比例一级返积分
                    $pointA1 = $point1 / 100;
                }
                if($point1=='' && $point11!=''){//活动固定一级返积分
                    $pointA11 = $point11;
                }
                if($point1=='' && $point11==''){
                    if($level_info['recommend_type']==1){//等级比例一级返积分
                        $pointA1 = $level_info['commission_point1'] / 100;
                    }else{//等级固定一级返积分
                        $pointA11 = $level_info['commission_point11'];
                    }
                }
                if($commissionA1!=''){//比例一级返佣
                    $commissionA = twoDecimal($real_price* $commissionA1);
                }
                if($commissionA11!=''){//固定一级返佣
                    $commissionA =  $commissionA11;
                }
                if($pointA1!=''){//比例一级返积分
                    $pointA = floor($real_price * $pointA1);
                }
                if($pointA11!=''){//固定一级返积分
                    $pointA =  $pointA11;//开启内购之后当前分销商获得一级积分
                }
                $data['commission'] = $commissionA;
                $data['point'] = $pointA;
                return $data;
            }
        }else{
            $data['commission'] = '';
            $data['point'] = '';
            return $data;
        }
    }
    
    /*
     * 获取分销商数量
     */
    public function getCountForDistributor(){
        $member = new VslMemberModel();
        $count = $member->getCount(['website_id'=>$this->website_id,'isdistributor'=>2]);
        return $count;
    }
    public function getGoodsCommissionList($uid,$goods_id,$price)
    {
        
        $ConfigService = new ConfigService();
        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id'=>$goods_id]);
        if(empty($goods_info)){
            return;
        }
        $addonsConfigService = new AddonsConfigService();
        $info1 = $addonsConfigService ->getAddonsConfig("distribution",$this->website_id);//基本设置
        $info2 = $ConfigService ->getConfig(0,"SETTLEMENT",$this->website_id);
        $seckill = getAddons('seckill',$this->website_id);
        $seckill_rule =  $addonsConfigService ->getAddonsConfig("seckill",$this->website_id);
        $seckill_value = json_decode($seckill_rule['value'],true);
        $seckill_distribution_val = json_decode($seckill_value['distribution_val'],true);
        $bargain = getAddons('bargain',$this->website_id);
        $bargain_rule =  $addonsConfigService ->getAddonsConfig("bargain", $this->website_id);
        $bargain_value = json_decode($bargain_rule['value'],true);
        $bargain_distribution_val = json_decode($bargain_value['distribution_val'],true);
        $order_bargain_id = $order_info['bargain_id'];
        $groupshopping = getAddons('groupshopping',$this->website_id);
        $groupshopping_rule =  $addonsConfigService ->getAddonsConfig("groupshopping",$this->website_id);
        $groupshopping_value = json_decode($groupshopping_rule['value'],true);
        $groupshopping_goods_info = $order_info['group_id'];
        $presell_goods_info = $order_goods_info['presell_id'];
        $presell = getAddons('presell',$this->website_id);
        $presell_rule =  $addonsConfigService ->getAddonsConfig("presell",$this->website_id);
        $presell_value = json_decode($presell_rule['value'],true);
        $commission1= '';
        $commission2= '';
        $commission3= '';
        $commission11= '';
        $commission22= '';
        $commission33= '';
        $point1= '';
        $point2= '';
        $point3= '';
        $point11= '';
        $point22= '';
        $point33= '';
        $commissionB2 = '';
        $pointB2= '';
        $commissionB22 = '';
        $pointB22 = '';
        $commissionA11 = '';
        $pointA11 = '';
        $commissionA1 = '';
        $pointA1 = '';
        $commissionC33 = '';
        $pointC33 = '';
        $commissionC3 = '';
        $pointC3 = '';
        $level_rule_ids = [];
        $bargain_goods  = 0;
        $seckill_goods  = 0;
        $groupshopping_goods  = 0;
        $presell_goods  = 0;
        if($bargain==1 && $bargain_value['is_distribution']==1 && $order_bargain_id){//砍价是否参与分销分红、分销分红规则
            $bargain_goods  = 1;
            if($bargain_value['rule_commission']==1){//有独立分销规则
                if($bargain_distribution_val['recommend_type']==1){//佣金比例设置
                    $commission1 = $bargain_distribution_val['first_rebate'];
                    $commission2 = $bargain_distribution_val['second_rebate'];
                    $commission3 = $bargain_distribution_val['third_rebate'];
                    $point1 = $bargain_distribution_val['first_point'];
                    $point2 = $bargain_distribution_val['second_point'];
                    $point3 = $bargain_distribution_val['third_point'];
                }else{//固定佣金
                    $commission11 = $bargain_distribution_val['first_rebate1'];
                    $commission22 = $bargain_distribution_val['second_rebate1'];
                    $commission33 = $bargain_distribution_val['third_rebate1'];
                    $point11 = $bargain_distribution_val['first_point1'];
                    $point22 = $bargain_distribution_val['second_point1'];
                    $point33 = $bargain_distribution_val['third_point1'];
                }
            }
        }
        if($seckill==1 && $seckill_value['is_distribution']==1 && $order_goods_info['seckill_id']){//该商品参与秒杀
            $seckill_goods  = 1;
            if($seckill_value['rule_commission']==1){//有独立分销规则
                if($seckill_distribution_val['recommend_type']==1){//佣金比例设置
                    $commission1 = $seckill_distribution_val['first_rebate'];
                    $commission2 = $seckill_distribution_val['second_rebate'];
                    $commission3 = $seckill_distribution_val['third_rebate'];
                    $point1 = $seckill_distribution_val['first_point'];
                    $point2 = $seckill_distribution_val['second_point'];
                    $point3 = $seckill_distribution_val['third_point'];
                }else{//固定佣金
                    $commission11 = $seckill_distribution_val['first_rebate1'];
                    $commission22 = $seckill_distribution_val['second_rebate1'];
                    $commission33 = $seckill_distribution_val['third_rebate1'];
                    $point11 = $seckill_distribution_val['first_point1'];
                    $point22 = $seckill_distribution_val['second_point1'];
                    $point33 = $seckill_distribution_val['third_point1'];
                }
            }
        }
        if($groupshopping==1 && $groupshopping_value['is_distribution']==1 && $groupshopping_goods_info){//该商品参与拼团
            $groupshopping_goods  = 1;
            if($groupshopping_value['rule_commission']==1){//有独立分销规则
                if($groupshopping_value['recommend_type']==1){//佣金比例设置
                    $commission1 = $groupshopping_value['first_rebate'];
                    $commission2 = $groupshopping_value['second_rebate'];
                    $commission3 = $groupshopping_value['third_rebate'];
                    $point1 = $groupshopping_value['first_point'];
                    $point2 = $groupshopping_value['second_point'];
                    $point3 = $groupshopping_value['third_point'];
                }else{//固定佣金
                    $commission11 = $groupshopping_value['first_rebate1'];
                    $commission22 = $groupshopping_value['second_rebate1'];
                    $commission33 = $groupshopping_value['third_rebate1'];
                    $point11 = $groupshopping_value['first_point1'];
                    $point22 = $groupshopping_value['second_point1'];
                    $point33 = $groupshopping_value['third_point1'];
                }
            }
        }
        if($presell==1 && $presell_value['is_distribution']==1 && $presell_goods_info){//该商品参与预售
            $presell_goods  = 1;
            if($presell_value['rule_commission']==1){//有独立分销规则
                if($presell_value['recommend_type']==1){//佣金比例设置
                    $commission1 = $presell_value['first_rebate'];
                    $commission2 = $presell_value['second_rebate'];
                    $commission3 = $presell_value['third_rebate'];
                    $point1 = $presell_value['first_point'];
                    $point2 = $presell_value['second_point'];
                    $point3 = $presell_value['third_point'];
                }else{//固定佣金
                    $commission11 = $presell_value['first_rebate1'];
                    $commission22 = $presell_value['second_rebate1'];
                    $commission33 = $presell_value['third_rebate1'];
                    $point11 = $presell_value['first_point1'];
                    $point22 = $presell_value['second_point1'];
                    $point33 = $presell_value['third_point1'];
                }
            }
        } 
        //如果该商品是店铺独立商品 ，由于默认是不开启 ，之前已开启参与产品如果没有设置独立分销则默认为0
        //获取是否开启店铺佣金
        $configAdmin= new DistributorService();
        $distributionStatusAdmin = $configAdmin->getDistributionSite($this->website_id);
        $distribution_admin_status = $distributionStatusAdmin['distribution_admin_status'];

        if($distribution_admin_status == 0 && $goods_info['shop_id']){
            $goods_info['distribution_rule'] = 0;
            $goods_info['is_distribution'] = 1;
        }
        //获取当前商品 是否重复购买
        // 查询是否已购买过该商品
        if($uid){
            $countOrderGoods = new VslOrderGoodsViewModel();
            $goodscondition['website_id'] = $this->website_id;
            $goodscondition['buyer_id'] = $uid;
            $goodscondition['goods_id'] = $goods_id;
            $resCount = $countOrderGoods->getAllGoodsOrders($goodscondition);
        }else{
            $resCount = 0;
        }
        
        
        $countGoods = 0;
        if($resCount > 1){
            $countGoods = 1;
        }
        
        if($goods_info['is_distribution']==1 ){//该商品参与分销
            if($goods_info['distribution_rule']==1){//有独立分销规则
                $goods_info['distribution_rule_val'] = json_decode(htmlspecialchars_decode($goods_info['distribution_rule_val']),true);
                if($goods_info['distribution_rule_val']['level_rule'] && $goods_info['distribution_rule_val']['level_rule']==1){
                    $level_rule_ids = $goods_info['distribution_rule_val']['level_ids'];
                    if($goods_info['distribution_rule_val']['recommend_type']==1){//佣金比例设置
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $level_first_rebate = $goods_info['distribution_rule_val']['first_rebate'];
                        $level_second_rebate = $goods_info['distribution_rule_val']['second_rebate'];
                        $level_third_rebate = $goods_info['distribution_rule_val']['third_rebate'];
                        $level_first_point = $goods_info['distribution_rule_val']['first_point'];
                        $level_second_point = $goods_info['distribution_rule_val']['second_point'];
                        $level_third_point = $goods_info['distribution_rule_val']['third_point'];
                    }else{//固定佣金
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $level_first_rebate1 = $goods_info['distribution_rule_val']['first_rebate1'];
                        $level_second_rebate1 = $goods_info['distribution_rule_val']['second_rebate1'];
                        $level_third_rebate1 = $goods_info['distribution_rule_val']['third_rebate1'];
                        $level_first_point1 = $goods_info['distribution_rule_val']['first_point1'];
                        $level_second_point1 = $goods_info['distribution_rule_val']['second_point1'];
                        $level_third_point1 = $goods_info['distribution_rule_val']['third_point1'];
                    }
                }else{
                    if($goods_info['distribution_rule_val']['recommend_type']==1){//佣金比例设置
                        $commission1 = $goods_info['distribution_rule_val']['first_rebate'];
                        $commission2 = $goods_info['distribution_rule_val']['second_rebate'];
                        $commission3 = $goods_info['distribution_rule_val']['third_rebate'];
                        $point1 = $goods_info['distribution_rule_val']['first_point'];
                        $point2 = $goods_info['distribution_rule_val']['second_point'];
                        $point3 = $goods_info['distribution_rule_val']['third_point'];
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                    }else{//固定佣金
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $commission11 = $goods_info['distribution_rule_val']['first_rebate1'];
                        $commission22 = $goods_info['distribution_rule_val']['second_rebate1'];
                        $commission33 = $goods_info['distribution_rule_val']['third_rebate1'];
                        $point11 = $goods_info['distribution_rule_val']['first_point1'];
                        $point22 = $goods_info['distribution_rule_val']['second_point1'];
                        $point33 = $goods_info['distribution_rule_val']['third_point1'];
                    }
                }

               

            }
            //查询是否开启复购 //$params['goods_id']
            if($goods_info['buyagain'] == 1 && $countGoods == 1){ //开启商品独立复购

                $goods_info['buyagain_distribution_val'] = json_decode(htmlspecialchars_decode($goods_info['buyagain_distribution_val']),true);
                $goods_info['buyagain_distribution_rule_val'] = $goods_info['buyagain_distribution_val'];
                
                //重置等级规则
                $goods_info['distribution_rule_val'] = $goods_info['buyagain_distribution_rule_val'];
                $goods_info['distribution_rule_val']['recommend_type'] = $goods_info['buyagain_distribution_rule_val']['buyagain_recommend_type'];

                if($goods_info['buyagain_distribution_val']['buyagain_level_rule'] && $goods_info['buyagain_distribution_val']['buyagain_level_rule']==1){
                    $level_rule_ids = $goods_info['buyagain_distribution_rule_val']['level_ids'];
                    if($goods_info['buyagain_distribution_rule_val']['buyagain_recommend_type']==1){//佣金比例设置
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $level_first_rebate = $goods_info['buyagain_distribution_rule_val']['buyagain_first_rebate'];
                        $level_second_rebate = $goods_info['buyagain_distribution_rule_val']['buyagain_second_rebate'];
                        $level_third_rebate = $goods_info['buyagain_distribution_rule_val']['buyagain_third_rebate'];
                        $level_first_point = $goods_info['buyagain_distribution_rule_val']['buyagain_first_point'];
                        $level_second_point = $goods_info['buyagain_distribution_rule_val']['buyagain_second_point'];
                        $level_third_point = $goods_info['buyagain_distribution_rule_val']['buyagain_third_point'];
                    }else{//固定佣金
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $level_first_rebate1 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_rebate1'];
                        $level_second_rebate1 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_rebate1'];
                        $level_third_rebate1 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_rebate1'];
                        $level_first_point1 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_point1'];
                        $level_second_point1 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_point1'];
                        $level_third_point1 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_point1'];
                    }
                }else{
                    if($goods_info['buyagain_distribution_rule_val']['buyagain_recommend_type']==1){//佣金比例设置
                        $commission1 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_rebate'];
                        $commission2 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_rebate'];
                        $commission3 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_rebate'];
                        $point1 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_point'];
                        $point2 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_point'];
                        $point3 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_point'];
                        $commission11 = '';
                        $commission22 = '';
                        $commission33 = '';
                        $point11 = '';
                        $point22 = '';
                        $point33 = '';
                    }else{//固定佣金
                        $commission1 = '';
                        $commission2 = '';
                        $commission3 = '';
                        $point1 = '';
                        $point2 = '';
                        $point3 = '';
                        $commission11 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_rebate1'];
                        $commission22 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_rebate1'];
                        $commission33 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_rebate1'];
                        $point11 = $goods_info['buyagain_distribution_rule_val']['buyagain_first_point1'];
                        $point22 = $goods_info['buyagain_distribution_rule_val']['buyagain_second_point1'];
                        $point33 = $goods_info['buyagain_distribution_rule_val']['buyagain_third_point1'];
                    }
                }
            }
        }
        $goods_info ['price'] = $price ? $price : $goods_info ['price'];
        $cost_price = $goods_info['cost_price'];//商品成本价
        $price = $goods_info ['price'];//商品实际支付金额
        $promotion_price = $goods_info ['price'];//商品销售价
        $original_price = $goods_info ['market_price'];//商品原价
        // $profit_price = $promotion_price-$cost_price-$order_goods_info['profile_price']+$order_goods_info['adjust_money'];//商品利润价
        $profit_price = $promotion_price-$cost_price;//商品利润价
        if($profit_price<0){
            $profit_price = 0;
        }
        
        //未进入
        if($uid){
            $member = new VslMemberModel();
            $distributor = $member->getInfo(['uid' => $uid]);
            if($distributor['isdistributor'] == 2 && $distributor['distributor_level_id']){
                $distributor_level_id = $distributor['distributor_level_id'];
            }else{
                //获取默认等级
                $levels = new DistributorLevelModel();
                $distributor_level_id = $levels->getInfo(['website_id'=>$this->website_id,'is_default'=>1],'id')['id'];//默认等级权重，也是最低等级
            }
        }else{
            //获取默认等级
            $levels = new DistributorLevelModel();
            $distributor_level_id = $levels->getInfo(['website_id'=>$this->website_id,'is_default'=>1],'id')['id'];//默认等级权重，也是最低等级
        }
        
        $base_info = json_decode($info1['value'], true);
        $set_info = json_decode($info2['value'], true);
        $level = new DistributorLevelModel();
        $commissionA_id = 0;
        $commissionA = 0;//一级佣金和对应的id
        $pointA = 0;//一级积分和对应的id
        $commissionB_id = 0;
        $commissionB = 0;//二级佣金和对应的id
        $pointB = 0;//二级积分和对应的id
        $commissionC_id = 0;
        $commissionC = 0;//三级佣金和对应的id
        $pointC = 0;//三级积分和对应的id
        $commission_calculation = $set_info['commission_calculation'];//计算节点（商品价格）
        $real_price = 0;
        if ($commission_calculation == 1) {//实际付款金额
            $real_price = $price;
        }elseif($commission_calculation == 2) {//商品原价
            $real_price = $original_price;
        }elseif($commission_calculation == 3) {//商品销售价
            $real_price = $promotion_price;
        }elseif($commission_calculation == 4) {//商品成本价
            $real_price = $cost_price;
        }elseif($commission_calculation == 5) {//商品利润价
            $real_price = $profit_price;
        }
        
        //debug
        if($goods_info['is_distribution']==1 || $seckill_goods==1 || $groupshopping_goods==1 || $presell_goods==1 || $bargain_goods==1) {
            if ($distributor_level_id) {//获取当前等级
                
                    
                $level_infoA = $level->getInfo(['id' => $distributor_level_id]);
                
                //如果开启复购 等级规则变更为复购规则
                if($level_infoA['buyagain'] == 1 && $countGoods == 1){
                    $level_infoA['recommend_type'] = $level_infoA['buyagain_recommendtype'];
                    $level_infoA['commission1'] = $level_infoA['buyagain_commission1'];
                    $level_infoA['commission2'] = $level_infoA['buyagain_commission2'];
                    $level_infoA['commission3'] = $level_infoA['buyagain_commission3'];
                    $level_infoA['commission_point1'] = $level_infoA['buyagain_commission_point1'];
                    $level_infoA['commission_point2'] = $level_infoA['buyagain_commission_point2'];
                    $level_infoA['commission_point3'] = $level_infoA['buyagain_commission_point3'];

                    $level_infoA['commission11'] = $level_infoA['buyagain_commission11'];
                    $level_infoA['commission22'] = $level_infoA['buyagain_commission22'];
                    $level_infoA['commission33'] = $level_infoA['buyagain_commission33'];
                    $level_infoA['commission_point11'] = $level_infoA['buyagain_commission_point11'];
                    $level_infoA['commission_point22'] = $level_infoA['buyagain_commission_point22'];
                    $level_infoA['commission_point33'] = $level_infoA['buyagain_commission_point33'];
                }
                if($level_rule_ids && in_array($distributor_level_id,$level_rule_ids)){//有特定等级返佣设置
                    foreach ($level_rule_ids as $k=>$v){
                        if($v==$distributor_level_id){
                            if($goods_info['distribution_rule_val']['recommend_type']==1){//比例返佣
                                $commissionA11 = '';
                                $pointA11 = '';
                                $commissionA1 =  $level_first_rebate[$k]/100;//开启内购之后当前分销商获得一级佣金
                                $pointA1 = $level_first_point[$k]/100;//开启内购之后当前分销商获得一级积分
                            }else{
                                $commissionA1 = '';
                                $pointA1 = '';
                                $commissionA11 =  $level_first_rebate1[$k];//开启内购之后当前分销商获得一级佣金
                                $pointA11 = $level_first_point1[$k];//开启内购之后当前分销商获得一级积分
                            }
                        }
                    }
                }else{
                    if ($commission11=='' && $commission1!='') {//活动比例一级返佣
                        $commissionA1 = $commission1 / 100;
                        $commissionA11 = '';
                    }
                    if($commission1=='' && $commission11!=''){//活动固定一级返佣
                        $commissionA11 = $commission11;
                        $commissionA1 = '';
                    }
                    if($commission1=='' && $commission11==''){
                        if($level_infoA['recommend_type']==1){//等级比例一级返佣
                            $commissionA1 = $level_infoA['commission1'] / 100;
                            $commissionA11 = '';
                        }else{//等级固定一级返佣
                            $commissionA11 = $level_infoA['commission11'];
                            $commissionA1 = '';
                        }
                    }
                    if ($point11=='' && $point1!='') {//活动比例一级返积分
                        $pointA1 = $point1 / 100;
                        $pointA11 = '';
                    }
                    if($point1=='' && $point11!=''){//活动固定一级返积分
                        $pointA11 = $point11;
                        $pointA1 = '';
                    }
                    if($point1=='' && $point11==''){
                        if($level_infoA['recommend_type']==1){//等级比例一级返积分
                            $pointA1 = $level_infoA['commission_point1'] / 100;
                            $pointA11 = '';
                        }else{//等级固定一级返积分
                            $pointA1 = '';
                            $pointA11 = $level_infoA['commission_point11'];
                        }
                    }
                }
               
                if ($base_info['distribution_pattern'] >= 1) {//一级分销模式
                    $commissionA_id = $params['buyer_id'];//获得一级佣金的用户id
                    if($commissionA1!=''){//比例一级返佣
                        $commissionA = twoDecimal($real_price * $commissionA1*1);//开启内购之后当前分销商获得一级佣金
                    }
                    if($commissionA11!=''){//固定一级返佣
                        $commissionA =  $commissionA11*1;//开启内购之后当前分销商获得一级佣金
                    }
                    if($pointA1!=''){//比例一级返积分
                        $pointA = floor($real_price * $pointA1*1);//开启内购之后当前分销商获得一级积分
                    }
                    if($pointA11!=''){//固定一级返积分
                        $pointA =  $pointA11*1;//开启内购之后当前分销商获得一级积分
                    }
                }
                if ($base_info['distribution_pattern'] >= 2 ) {//二级分销模式
                        $level_infoB = $level_infoA;
                        //如果开启复购 等级规则变更为复购规则
                        if($level_infoB['buyagain'] == 1){
                            $level_infoB['recommend_type'] = $level_infoB['buyagain_recommendtype'];
                            $level_infoB['commission1'] = $level_infoB['buyagain_commission1'];
                            $level_infoB['commission2'] = $level_infoB['buyagain_commission2'];
                            $level_infoB['commission3'] = $level_infoB['buyagain_commission3'];
                            $level_infoB['commission_point1'] = $level_infoB['buyagain_commission_point1'];
                            $level_infoB['commission_point2'] = $level_infoB['buyagain_commission_point2'];
                            $level_infoB['commission_point3'] = $level_infoB['buyagain_commission_point3'];

                            $level_infoB['commission11'] = $level_infoB['buyagain_commission11'];
                            $level_infoB['commission22'] = $level_infoB['buyagain_commission22'];
                            $level_infoB['commission33'] = $level_infoB['buyagain_commission33'];
                            $level_infoB['commission_point11'] = $level_infoB['buyagain_commission_point11'];
                            $level_infoB['commission_point22'] = $level_infoB['buyagain_commission_point22'];
                            $level_infoB['commission_point33'] = $level_infoB['buyagain_commission_point33'];
                        }
                        if($level_rule_ids && in_array($distributor_level_id,$level_rule_ids)){//有特定等级返佣设置
                            foreach ($level_rule_ids as $k=>$v){
                                if($v==$distributor_level_id){
                                    if($goods_info['distribution_rule_val']['recommend_type']==1){//比例返佣
                                        $commissionB2 =  $level_second_rebate[$k]/100;
                                        $pointB2 =  $level_second_point[$k]/100;
                                        $pointB22 ='';
                                        $commissionB22 ='';
                                    }else{
                                        $pointB2 ='';
                                        $commissionB2 ='';
                                        $commissionB22 =  $level_second_rebate1[$k];
                                        $pointB22 =  $level_second_point1[$k];
                                    }
                                }
                            }
                        }else{
                            if($commission22=='' && $commission2!=''){//活动比例二级返佣
                                $commissionB2 = $commission2/100;
                                $commissionB22 ='';
                            }
                            if($commission2=='' && $commission22!=''){//活动固定二级返佣
                                $commissionB22 = $commission22;
                                $commissionB2 ='';
                            }
                            if($commission2=='' && $commission22==''){
                                if($level_infoB['recommend_type']==1){//等级比例二级返佣
                                    $commissionB2 = $level_infoB['commission2'] / 100;
                                    $commissionB22 ='';
                                }else{//等级固定二级返佣
                                    $commissionB22 = $level_infoB['commission22'];
                                    $commissionB2 ='';
                                }
                            }
                            if ($point22=='' && $point2!='') {//活动比例二级返积分
                                $pointB2 = $point2 / 100;
                                $pointB22='';
                            }
                            if($point2=='' && $point22!=''){//活动固定二级返积分
                                $pointB22 = $point22;
                                $pointB2='';
                            }
                            if($point2=='' && $point22==''){
                                if($level_infoB['recommend_type']==1){//等级比例二级返积分
                                    $pointB2 = $level_infoB['commission_point2'] / 100;
                                    $pointB22='';
                                }else{//等级固定二级返积分
                                    $pointB22 = $level_infoB['commission_point22'];
                                    $pointB2='';
                                }
                            }
                            
                        }
                        $commissionB_id = $distributorB['uid'];//获得二级佣金的用户id
                        if($commissionB2!=''){
                            $commissionB = twoDecimal($real_price * $commissionB2*1);//当前分销商的推荐人获得二级佣金
                        }
                        if($commissionB22!=''){
                            $commissionB = $commissionB22*1;//当前分销商的推荐人获得二级固定佣金
                        }
                        if($pointB2!=''){//比例二级返积分
                            $pointB = floor($real_price * $pointB2*1);//开启内购之后当前分销商获得二级积分
                        }
                        if($pointB22!=''){//固定二级返积分
                            $pointB =  $pointB22*1;//开启内购之后当前分销商获得二级积分
                        }
                    
                    if ($base_info['distribution_pattern'] >= 3) {//三级分销模式
                        
                            $level_infoC = $level_infoA;
                            //如果开启复购 等级规则变更为复购规则
                            if($level_infoC['buyagain'] == 1 && $countGoods ==1){
                                $level_infoC['recommend_type'] = $level_infoC['buyagain_recommendtype'];
                                $level_infoC['commission1'] = $level_infoC['buyagain_commission1'];
                                $level_infoC['commission2'] = $level_infoC['buyagain_commission2'];
                                $level_infoC['commission3'] = $level_infoC['buyagain_commission3'];
                                $level_infoC['commission_point1'] = $level_infoC['buyagain_commission_point1'];
                                $level_infoC['commission_point2'] = $level_infoC['buyagain_commission_point2'];
                                $level_infoC['commission_point3'] = $level_infoC['buyagain_commission_point3'];

                                $level_infoC['commission11'] = $level_infoC['buyagain_commission11'];
                                $level_infoC['commission22'] = $level_infoC['buyagain_commission22'];
                                $level_infoC['commission33'] = $level_infoC['buyagain_commission33'];
                                $level_infoC['commission_point11'] = $level_infoC['buyagain_commission_point11'];
                                $level_infoC['commission_point22'] = $level_infoC['buyagain_commission_point22'];
                                $level_infoC['commission_point33'] = $level_infoC['buyagain_commission_point33'];
                            }
                            if($level_rule_ids && in_array($distributor_level_id,$level_rule_ids)){//有特定等级返佣设置
                                foreach ($level_rule_ids as $k=>$v){
                                    if($v==$distributor_level_id){
                                        if($goods_info['distribution_rule_val']['recommend_type']==1){//比例返佣
                                            $commissionC3 = $level_third_rebate[$k]/100;
                                            $pointC3 = $level_third_point[$k]/100;
                                            $commissionC33 = '';
                                            $pointC33 = '';
                                        }else{
                                            $commissionC3 ='';
                                            $pointC3 = '';
                                            $commissionC33 =  $level_third_rebate1[$k];
                                            $pointC33 = $level_third_point1[$k];
                                        }
                                    }
                                }
                            }else{
                                if($commission33=='' && $commission3!=''){//活动比例三级返佣
                                    $commissionC3 = $commission3 / 100;
                                    $commissionC33 = '';
                                }
                                if($commission3=='' && $commission33!=''){//活动固定三级返佣
                                    $commissionC33 = $commission33;
                                    $commissionC3 ='';
                                }
                                if($commission3=='' && $commission33==''){
                                    if($level_infoC['recommend_type']==1){//等级比例三级返佣
                                        $commissionC3 = $level_infoC['commission3'] / 100;
                                        $commissionC33 = '';
                                    }else{//等级固定三级返佣
                                        $commissionC33 = $level_infoC['commission33'];
                                        $commissionC3 ='';
                                    }
                                }
                                if ($point33=='' && $point3!='') {//活动比例三级返积分
                                    $pointC3 = $point3 / 100;
                                    $pointC33 = '';
                                }
                                if($point3=='' && $point33!=''){//活动固定三级返积分
                                    $pointC33 = $point33;
                                    $pointC3 = '';
                                }
                                if($point3=='' && $point33==''){
                                    if($level_infoC['recommend_type']==1){//等级比例三级返积分
                                        $pointC3 = $level_infoC['commission_point3'] / 100;
                                        $pointC33 = '';
                                    }else{//等级固定二级返积分
                                        $pointC33 = $level_infoC['commission_point33'];
                                        $pointC3 = '';
                                    }
                                }
                            }
                            $commissionC_id = $distributorC['uid'];//获得三级佣金的用户id
                            if($commissionC3!=''){
                                $commissionC = twoDecimal($real_price * $commissionC3*1);//当前分销商的推荐人的上级获得三级佣金
                            }
                            if($commissionC33!=''){
                                $commissionC =  $commissionC33*1;//当前分销商的推荐人的上级获得固定佣金
                            }
                            if($pointC3!=''){//比例三级返积分
                                $pointC = floor($real_price * $pointC3*1);//开启内购之后当前分销商获得三级积分
                            }
                            if($pointC33!=''){//固定三级返积分
                                $pointC =  $pointC33*1;//开启内购之后当前分销商获得三级积分
                            }
                        
                    }
                }
                
            }
            
        }
        $return_data = array(
            'commission1' => $commissionA,
            'commission2' => $commissionB,
            'commission3' => $commissionC,
            'commission_point1' => $pointA,
            'commission_point2' => $pointB,
            'commission_point3' => $pointC,
        );
        return $return_data;
    }
}
