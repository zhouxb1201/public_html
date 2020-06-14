<?php
namespace addons\microshop\service;
/**
 * 店主服务层
 */
use data\model\VslAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslMemberModel;
use data\model\VslMemberViewModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderModel;
use data\model\AlbumPictureModel;
use data\model\VslGoodsModel;
use data\service\BaseService as BaseService;
use data\model\UserModel;
use addons\microshop\model\VslMicroShopLevelModel as MicroShopLevelModel;
use data\service\Config as ConfigService;
use data\model\ConfigModel as ConfigModel;
use addons\microshop\model\VslOrderMicroShopProfitModel ;
use addons\microshop\model\VslMicroShopAccountModel;
use addons\microshop\model\VslMicroShopAccountRecordsModel;
use addons\microshop\model\VslMicroShopAccountRecordsViewModel;
use addons\microshop\model\VslMicroShopProfitWithdrawModel;
use data\model\VslAccountWithdrawUserRecordsModel;
use data\service\Pay\tlPay;
use data\service\ShopAccount;
use data\model\VslMemberBankAccountModel;
use data\model\VslMemberAccountModel;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
use data\service\Pay\AliPay;
use data\service\Pay\WeiXinPay;
use data\model\VslGoodsViewModel;
use data\service\GoodsCategory;
use data\model\VslGoodsCategoryModel;
class Microshop extends BaseService
{
    private $config_module;
    function __construct()
    {
        parent::__construct();
        $this->config_module = new ConfigModel();
        $this->addons_config_module = new AddonsConfigModel();
    }
    /**
     * 获得微店统计
     */
    public function getMicroShopCount($website_id)
    {
        $start_date = strtotime(date("Y-m-d"),time());
        $end_date = strtotime(date('Y-m-d',strtotime('+1 day')));
        $member = new VslMemberModel();
        $data['shopkeeper_total'] = $member->getCount(['website_id'=>$website_id,'isshopkeeper'=>2]);
        $data['shopkeeper_today'] = $member->getCount(['website_id'=>$website_id,'isshopkeeper'=>2,'become_shopkeeper_time'=>[[">",$start_date],["<",$end_date]]]);
        $MicroShopAccount = new VslMicroShopAccountModel();
        $profit_total = $MicroShopAccount->Query(['website_id'=>$website_id],'profit');
        $data['profit_total'] = array_sum($profit_total);
        $withdrawals_total = $MicroShopAccount->Query(['website_id'=>$website_id],'withdrawals');
        $data['withdrawals_total'] = array_sum($withdrawals_total);
        return $data;
    }

    /**
     * 获取店主列表
     */
    public function getShopKeeperList($uid,$page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $MicroShop = new VslMemberModel();
        $user = new UserModel();
        if($this->website_id){
            $website_id = $this->website_id;
            $where['nm.website_id'] = $website_id;
        }else{
            $website_id =  $MicroShop->getInfo(['uid'=>$uid],'website_id')['website_id'];
            $where['nm.website_id'] = $website_id;
        }
        $MicroShop_view = new VslMemberViewModel();
        $list = $this->getMicroShopSite($website_id);
        if($uid &&  $list['microshop_pattern']>=1){
            $id1 = $MicroShop->Query(['referee_id'=>$uid,'website_id'=>$website_id],'uid');
            if($id1){
                $where['nm.uid'] = ['in',implode(',',$id1)];
                if($id1 && $list['microshop_pattern']>=2){
                    $id2 = $MicroShop->Query(['referee_id'=>['in',implode(',',$id1)],'website_id'=>$website_id],'uid');
                    if($id2){
                        $id3 = implode(',',$id1).','.implode(',',$id2);
                        $where['nm.uid'] = ['in',$id3];
                    }
                    if($id3 && $list['microshop_pattern']>=3){
                        $id4 = $MicroShop->Query(['referee_id'=>['in',implode(',',$id2)],'website_id'=>$website_id],'uid');
                        if($id4){
                            $id5 = $id3.','.implode(',',$id4);
                            $where['nm.uid'] = ['in',$id5];
                        }
                    }
                }
            }
        }
        $result = $MicroShop_view->getShopKeeperViewList($page_index, $page_size, $where, $order);
        $condition['website_id'] = $website_id;
        $condition['isshopkeeper'] = ['in','1,2,-1'];
        $result['count'] = $MicroShop_view->getCount($condition);
        $condition['isshopkeeper'] = 2;
        $result['count1'] = $MicroShop_view->getCount($condition);
        $condition['isshopkeeper'] = 1;
        $result['count2'] = $MicroShop_view->getCount($condition);
        $condition['isshopkeeper'] = -1;
        $result['count3'] = $MicroShop_view->getCount($condition);
        $order_model = new VslOrderModel();
        if($result['data']){
            foreach ($result['data'] as $k => $v) {
                $agentcount = 0;
                $number1 = 0;
                $number2 = 0;
                $number3 = 0;
                $agentordercount = 0;
                $order1 = 0;
                $order2 = 0;
                $order3 = 0;
                $order1_money = 0;
                $order2_money = 0;
                $order3_money = 0;
                $self_order = 0;
                $selforder_money = 0;
                $selforder_money1 = 0;
                $selforder_money2 = 0;
                $result['agentcount'] = 0;
                $result['number1'] = 0;
                $result['number2'] = 0;
                $result['number3'] = 0;
                $result['agentordercount'] = 0;
                $result['order1'] = 0;
                $result['order2'] = 0;
                $result['order3'] = 0;
                $result['data'][$k]['Profit'] = 0;
                $user_info = $user->getInfo(['uid'=>$v['referee_id']],'user_name,user_tel,nick_name,user_headimg');
                $result['data'][$k]['withdrawals'] = 0;
                if($result['data'][$k]['user_name']){
                    $result['data'][$k]['member_name'] = $result['data'][$k]['user_name'];//店主
                }else if($result['data'][$k]['nick_name']){
                    $result['data'][$k]['member_name'] = $result['data'][$k]['nick_name'];//店主
                }else{
                    $result['data'][$k]['member_name'] = $result['data'][$k]['user_tel'];//店主
                }
                if($user_info['user_name']){
                    $result['data'][$k]['referee_name'] = $user_info['user_name'];//推荐人
                }else if($user_info['nick_name']){
                    $result['data'][$k]['referee_name'] = $user_info['nick_name'];//推荐人
                }else{
                    $result['data'][$k]['referee_name'] = $user_info['user_tel'];//推荐人
                }
                $result['data'][$k]['referee_heading'] = $user_info['user_headimg'];//推荐人
                if(1 <= $list['microshop_pattern']){
                    $ids1 = $MicroShop->Query(['referee_id'=>$v['uid']],'uid');
                    if($ids1){
                        $order_ids1 = $order_model->Query(['buyer_id'=>['in',implode(',',$ids1)],'order_status'=>4],'order_id');
                        $order1_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$ids1)]],'pay_money');
                        $order1_money2 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$ids1)]],'user_platform_money');
                        $number1 = count($ids1);//一级店主总人数
                        $order1 = count($order_ids1);//一级店主订单总数
                        $order1_money = array_sum($order1_money1) + array_sum($order1_money2);//一级店主订单总金额
                        $agentcount += $number1;
                        $agentordercount += $order1;
                    }
                }
                if(2 <= $list['microshop_pattern']){
                    if($number1>0){
                        $ids2 = $MicroShop->Query(['referee_id'=>['in',implode(',',$ids1)]],'uid');
                        if($ids2){
                            $order_ids2 = $order_model->Query(['buyer_id'=>['in',implode(',',$ids2)],'order_status'=>4],'order_id');
                            $order2_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$ids2)]],'pay_money');
                            $order2_money2 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$ids2)]],'user_platform_money');
                            $number2 = count($ids2);//二级店主总人数
                            $order2 = count($order_ids2);//二级店主订单总数
                            $order2_money = array_sum($order2_money1) + array_sum($order2_money2);//二级店主订单总金额
                            $agentcount += $number2;
                            $agentordercount += $order2;
                        }
                    }
                }
                if(3 <= $list['microshop_pattern']){
                    if($number2>0){
                        $ids3 = $MicroShop->Query(['referee_id'=>['in',implode(',',$ids2)]],'uid');
                        if($ids3){
                            $order_ids3 = $order_model->Query(['buyer_id'=>['in',implode(',',$ids3)],'order_status'=>4],'order_id');
                            $order3_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$ids3)]],'pay_money');
                            $order3_money2 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$ids3)]],'user_platform_money');
                            $number3 = count($ids3);//三级店主总人数
                            $order3 = count($order_ids3);//三级店主订单总数
                            $order3_money = array_sum($order3_money1) + array_sum($order3_money2);//三级店主订单总金额
                            $agentcount += $number3;
                            $agentordercount += $order3;
                        }
                    }
                }
                $self_order = count($order_model->Query(['buyer_id'=>$v['uid'],'order_status'=>4],'order_id'));
                $selforder_money1 = $order_model->Query(['buyer_id'=>$v['uid'],'order_status'=>4],'pay_money');
                $selforder_money2 = $order_model->Query(['buyer_id'=>$v['uid'],'order_status'=>4],'user_platform_money');
                $selforder_money =  array_sum($selforder_money1) +  array_sum($selforder_money2);
                $result['data'][$k]['lower_id'] = $MicroShop->Query(['referee_id'=>$v['uid']],'uid');//当前用户是否有下级
                $result['data'][$k]['self_order'] = $self_order ;//当前用户的订单数
                $result['data'][$k]['selforder_money'] = $selforder_money ;//当前用户的订单金额
                $result['data'][$k]['microShop_number'] = $agentcount ;//下级店主总人数
                $result['data'][$k]['number1'] = $number1 ;//下级一级店主总人数
                $result['data'][$k]['number2'] = $number2 ;//下级二级店主总人数
                $result['data'][$k]['number3'] = $number3 ;//下级三级店主总人数
                $result['data'][$k]['MicroShop_order_number'] = $agentordercount ;//下级店主订单总数
                $result['data'][$k]['order1'] = $order1 ;//下级一级店主订单总数
                $result['data'][$k]['order2'] = $order2 ;//下级二级店主订单总数
                $result['data'][$k]['order3'] = $order3 ;//下级三级店主订单总数
                $result['data'][$k]['order1_money'] = $order1_money ;//下级一级店主订单金额
                $result['data'][$k]['order2_money'] = $order2_money ;//下级二级店主订单金额
                $result['data'][$k]['order3_money'] = $order3_money ;//下级三级店主订单金额
                if($list['purchase_type']==1){//开启内购，自购也算微店订单
                    $result['data'][$k]['order'] = $order1+$order2+$order3+$self_order;//店主订单数
                    $result['data'][$k]['order_money'] = $order1_money+$order2_money+$order3_money+$selforder_money ;//店主订单金额
                }else{
                    $result['data'][$k]['order'] = $order1+$order2+$order3;//店主订单数
                    $result['data'][$k]['order_money'] = $order1_money+$order2_money+$order3_money ;//店主订单金额
                }
                $Profit = new VslMicroShopAccountModel();
                $Profit_info = $Profit->getInfo(['uid'=>$v['uid']],'*');
                if($Profit_info){
                    $result['data'][$k]['profit'] = $Profit_info['Profit'];//累积收益
                    $result['data'][$k]['withdrawals'] = $Profit_info['withdrawals'];//提现收益
                }
            }
        }
        if($uid){
            $result['Profit'] = 0;
            $result['withdrawals'] = 0;
            $user = new UserModel();
            $user_info = $user->getInfo(['uid'=>$uid],'user_headimg,user_name,user_tel,nick_name');
            $result['user_headimg'] = $user_info['user_headimg'];//获取店主头像
            if($user_info['user_name']){
                $result['member_name'] = $user_info['user_name'];//获取店主名称
            }elseif($user_info['nick_name']){
                $result['member_name'] = $user_info['nick_name'];//获取店主名称
            }else{
                $result['member_name'] = $user_info['user_tel'];//获取店主名称
            }
            $info = $MicroShop->getInfo(['uid'=>$uid],'*');//获取店主信息
            $result['real_name'] = $info['real_name'];//获取店主真实名称
            $result['mobile'] = $info['mobile'];//获取店主手机号
            $Profit = new VslMicroShopAccountModel();
            $Profit_info = $Profit->getInfo(['uid'=>$uid],'*');
            if($Profit_info){
                $result['profit'] = $Profit_info['Profit'];//累积收益
                $result['withdrawals'] = $Profit_info['withdrawals'];//提现收益
            }
            $MicroShop_level_id = $info['MicroShop_level_id'];
            $level = new MicroShopLevelModel();
            $result['level_name'] = $level->getInfo(['id'=>$MicroShop_level_id],'level_name')['level_name'];//等级名称
            if(1 <= $list['microshop_pattern']){
                $idslevel1 = $MicroShop->Query(['referee_id'=>$uid],'uid');
                if($idslevel1){
                    $order_ids1 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevel1)],'order_status'=>4],'order_id');
                    $order1_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel1)]],'pay_money');
                    $order1_money2 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel1)]],'user_platform_money');

                    $result['order1'] = count($order_ids1);//一级店主订单总数
                    $result['number1'] = count($idslevel1);//一级店主总人数
                    $result['order1_money'] = array_sum($order1_money1) + array_sum($order1_money2);//一级店主订单总金额
                    $result['agentcount'] += $result['number1'];
                    $result['agentordercount'] += $result['order1'];
                }
            }
            if(2 <= $list['microshop_pattern']){
                if($result['number1']>0){
                    $idslevel2 = $MicroShop->Query(['referee_id'=>['in',implode(',',$idslevel1)]],'uid');
                    if($idslevel2){
                        $order_ids2 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevel2)],'order_status'=>4],'order_id');
                        $order2_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel2)]],'pay_money');
                        $order2_money2 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel2)]],'user_platform_money');
                        $result['order2'] = count($order_ids2);//二级店主订单总数
                        $result['number2'] = count($idslevel2);//二级店主总人数
                        $result['order2_money'] = array_sum($order2_money1) + array_sum($order2_money2);//二级店主订单总金额
                        $result['agentcount'] += $result['number2'];
                        $result['agentordercount'] += $result['order2'];
                    }
                }
            }
            if(3 <= $list['microshop_pattern']){
                if($result['number2']>0){
                    $idslevel3 = $MicroShop->Query(['referee_id'=>['in',implode(',',$idslevel2)]],'uid');
                    if($idslevel3){
                        $order_ids3 = $order_model->Query(['buyer_id'=>['in',implode(',',$idslevel3)],'order_status'=>4],'order_id');
                        $order3_money1 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel3)]],'pay_money');
                        $order3_money2 = $order_model->Query(['order_status'=>4,'buyer_id'=>['in',implode(',',$idslevel3)]],'user_platform_money');
                        $result['order2'] = count($order_ids3);//三级店主订单总数
                        $result['number3'] = count($idslevel3);//三级店主总人数
                        $result['order3_money'] = array_sum($order3_money1) + array_sum($order3_money2);//三级店主订单总金额
                        $result['agentcount'] += $result['number3'];
                        $result['agentordercount'] += $result['order3'];
                    }
                }
            }

        }
        return $result;
    }
    /**
     * 获取店主等级列表
     */
    public function getShopKeeperLevelList($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $MicroShop_level = new MicroShopLevelModel();
        $list = $MicroShop_level->pageQuery($page_index,$page_size,$where,'','*');
        return $list;
    }

    /**
     * 添加店主等级
     */
    public function addShopKeeperLevel($level_name, $profit1, $profit2, $profit3,$selfpurchase_rebate,$shop_rebate,$term_validity,$validity,$weight,$goods_id)
    {
        $MicroShop_level = new MicroShopLevelModel();
        $where['website_id'] = $this->website_id;
        $where['level_name'] = $level_name;
        $count = $MicroShop_level->getCount($where);
        if ($count > 0) {
            return -2;
        }
        $data = array(
            'website_id' => $this->website_id,
            'level_name' => $level_name,
            'profit1' => $profit1,
            'profit2' => $profit2,
            'profit3' => $profit3,
            'weight' => $weight,
            'selfpurchase_rebate' => $selfpurchase_rebate,
            'shop_rebate' => $shop_rebate,
            'term_validity' => $term_validity,
            'validity' => $validity,
            'goods_id' => $goods_id,
            'create_time' => time()
        );
        $res = $MicroShop_level->save($data);
        return $res;
    }

    /**
     * 修改店主等级
     */
    public function updateShopKeeperLevel($id, $level_name, $profit1, $profit2, $profit3,$selfpurchase_rebate,$shop_rebate,$term_validity,$validity,$weight,$goods_id)
    {
        try {
            $MicroShop_level = new MicroShopLevelModel();
            $where['website_id'] = $this->website_id;
            $where['level_name'] = $level_name;
            $where['id'] = ['neq',$id];
            $count = $MicroShop_level->getCount($where);
            if ($count > 0) {
                return -2;
            }
            $MicroShop_level = new MicroShopLevelModel();
            $MicroShop_level->startTrans();
            $data = array(
                'level_name' => $level_name,
                'profit1' => $profit1,
                'profit2' => $profit2,
                'profit3' => $profit3,
                'weight' => $weight,
                'selfpurchase_rebate' => $selfpurchase_rebate,
                'shop_rebate' => $shop_rebate,
                'term_validity' => $term_validity,
                'validity' => $validity,
                'goods_id' => $goods_id,
                'modify_time' => time()
            );
            $retval= $MicroShop_level->save($data, [
                'id' => $id,
                'website_id' => $this->website_id
            ]);
            $MicroShop_level->commit();
            return $retval;
        } catch (\Exception $e) {
            $MicroShop_level->rollback();
            $e->getMessage();
            return 0;
        }
    }
    /*
     * 删除店主等级
     */
    public function deleteShopKeeperLevel($id)
    {
        $level = new MicroShopLevelModel();
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
     * 获得店主等级详情
     */
    public function getShopKeeperLevelInfo($id)
    {
        $level_type = new MicroShopLevelModel();
        $level_info = $level_type->getInfo(['id'=>$id]);
        $goods = new VslGoodsModel();
        $goods_info = $goods->getQuery(['goods_id'=>['in',$level_info['goods_id']]],'picture,goods_name,goods_id','');
        foreach($goods_info as $k=>$v){
            $v = objToArr($v);
            $pic_id = $v['picture'];
            $pic = new AlbumPictureModel();
            $level_info['goods_info'][$k]['pic'] = $pic->getInfo(['pic_id'=>$pic_id],'pic_cover_mid')['pic_cover_mid'];
            $level_info['goods_info'][$k]['goods_name'] = $goods_info[$k]['goods_name'];
            $level_info['goods_info'][$k]['goods_id'] = $v['goods_id'];
        }
        return $level_info;
    }
    /**
     * 获得店主等级比重
     */
    public function getLevelWeight()
    {
        $level_type = new MicroShopLevelModel();
        $level_weight = $level_type->Query(['website_id'=>$this->website_id],'weight');
        return $level_weight;
    }
    /**
     * 修改店主的当前等级
     */
    public function updateLevel($uid,$level_id)
    {
        $member = new VslMemberModel();
        $level = new MicroShopLevelModel();
        $level_info = $level->getInfo(['id'=>$level_id],'*');
        $term_validity = $level_info['term_validity'];
        $validity = $level_info['validity'];
        $time = 0;
        if($validity==1){
            $time = $term_validity*24*3600;
        }
        if($validity==2){
            $time = $term_validity*24*3600*30;
        }
        if($validity==3){
            $time = $term_validity*24*3600*30*365;
        }
        $times = time() + $time;
        if($level_info['is_default']==1){
            $times = 0;
        }
        $res = $member->save(['microshop_level_id'=>$level_id,'shopkeeper_level_time'=>$times],['uid'=>$uid]);
        return $res;
    }
    /**
     * 店主的等级到期降级为默认
     */
    public function downLevel($website_id)
    {
        $member = new VslMemberModel();
        $member_info = $member->Query(['website_id'=>$website_id,'isshopkeeper'=>2,'shopkeeper_level_time'=>['<',time()]],'uid');
        $level = new MicroShopLevelModel();
        $level_info = $level->getInfo(['website_id'=>$website_id,'is_default'=>1],'id');
        $times = 0;
        foreach ($member_info as $v){
            $member = new VslMemberModel();
            $member->save(['microshop_level_id'=>$level_info['id'],'shopkeeper_level_time'=>$times],['uid'=>$v]);
        }
    }
    /**
     * 获得已存在的等级商品
     */
    public function getLevelGoods($id=null)
    {
        $level_type = new MicroShopLevelModel();
        if($id){
            $level_goods = $level_type->Query(['website_id'=>$this->website_id,'id'=>['neq',$id],'is_default'=>0],'goods_id');
        }else{
            $level_goods = $level_type->Query(['website_id'=>$this->website_id,'is_default'=>0],'goods_id');
        }
        $goods = '';
        if($level_goods){
            $level_goods = array_unique($level_goods);
            foreach($level_goods as $v){
                $goods .= $v.',';
            }
            $goods = rtrim($goods,',');
        }
        return $goods;
    }
    /*
     * 删除店主
     */
    public function deleteShopKeeper($uid)
    {
        $member = new VslMemberModel();
        $member->startTrans();
        try {
            // 删除店主信息
            $data = [
                'isshopkeeper'=>0
            ];
            $member->save($data,['uid'=>$uid]);
            $member->commit();
            return 1;
        } catch (\Exception $e) {
            $member->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 店主详情
     */
    public function getShopKeeperInfo($uid)
    {
        $MicroShop = new VslMemberModel();
        $result = $MicroShop->getInfo(['uid' => $uid],"*");
        $result['microshop_set'] = $this->getMicroShopSite($result['website_id']);
        $Profit = new VslMicroShopAccountModel();
        $Profit_info = $Profit->getInfo(['uid'=>$uid],'*');
        $result['profit'] = $Profit_info['profit'];//可用收益
        $result['withdrawals'] = $Profit_info['withdrawals'];//已提现收益
        $result['freezing_profit'] = $Profit_info['freezing_profit'];//冻结收益
        $result['total_profit'] = $Profit_info['profit']+$Profit_info['withdrawals']+$result['freezing_profit'];//累积收益
        $user = new UserModel();
        $user_info = $user->getInfo(['uid'=>$uid],'user_headimg,user_name,user_tel,nick_name');
        if (!empty($user_info['user_headimg'])) {
            $result['user_headimg'] =  getApiSrc($user_info['user_headimg']);//获取店主头像
        } else {
            $result['user_headimg'] = '0';
        }
        $info = $MicroShop->getInfo(['uid'=>$uid],'*');//获取店主信息
        if($user_info['user_name']){
            $result['member_name'] = $user_info['user_name'];//获取会员名称
        }else if($user_info['nick_name']){
            $result['member_name'] = $user_info['nick_name'];//获取会员名称
        }else{
            $result['member_name'] = $user_info['user_tel'];//获取会员名称
        }
        $MicroShop_level_id = $info['microshop_level_id'];
        $level = new MicroShopLevelModel();
        $level_info = $level->getInfo(['id'=>$MicroShop_level_id],'*');
        $result['shopkeeper_level_name'] = $level_info['level_name'];//等级名称
        $result['is_default_shopkeeper'] = $level_info['is_default'];//店主是否是默认等级
        if($level_info['is_default']!=1){
            $result['shopkeeper_level_time'] = date('Y-m-d H:i:s',$result['shopkeeper_level_time']);//店主到期时间
        }else{
            $result['shopkeeper_level_time'] = '无期限';//店主到期时间
        }
        $result['become_shopkeeper_time'] = date('Y-m-d H:i:s',$result['become_shopkeeper_time']);//成为店主时间
        return $result;
    }
    /**
     * 前台获取店主等级中心
     */
    public function getShopKeeperLevelLists($uid='',$page_index = 1, $page_size = 0, $where = '', $order = '')
    {

        $MicroShop_level = new MicroShopLevelModel();
        if($uid){
            $MicroShop = new VslMemberModel();
            $microshop_level_id = $MicroShop->getInfo(['uid' => $uid],"microshop_level_id")['microshop_level_id'];
            $weight = $MicroShop_level->getInfo(['id'=>$microshop_level_id],'weight')['weight'];
            $where['weight']= ['>',$weight];
        }
        $list['data'] = [];
        $list = $MicroShop_level->pageQuery($page_index,$page_size,$where,'weight asc','*');
        if($list['data']){
            foreach($list['data'] as $k=>$v){
                $v = objToArr($v);
                $v['goods_id'] = explode(',',$v['goods_id']);
                $list['data'][$k] = objToArr($v);
            }
        }
        return $list['data'];
    }
    /**
     * 前台微店等级中心立即续费
     */
    public function getShopKeeperLevelInfos($uid)
    {
        $MicroShop = new VslMemberModel();
        $id = $MicroShop->getInfo(['uid' => $uid],"microshop_level_id")['microshop_level_id'];
        $level_type = new MicroShopLevelModel();
        $level_info = $level_type->getInfo(['id'=>$id]);
        $level_info['goods_id'] = explode(',',$level_info['goods_id']);
        return $level_info;
    }
    /**
     * 前台微店管理
     */
    public function addMicroShopSet($uid,$microshop_logo,$shopRecruitment_logo,$microshop_name,$microshop_introduce){
        $shop = new VslMemberModel();
        $data = array(
            'microshop_logo' => $microshop_logo,
            'shopRecruitment_logo' => $shopRecruitment_logo,
            'microshop_name'=>$microshop_name,
            'microshop_introduce'=>$microshop_introduce
        );
        $res =$shop->save($data,[
            'uid'=>$uid
        ]);
        return $res;
    }
    /**
     * 前台挑选微店商品
     */
    public function addGoodsId($uid,$goods_id){

        $member = new VslMemberModel();
        $microshop_goods = $member->getInfo(['uid'=>$uid],'microshop_goods')['microshop_goods'];
        if($microshop_goods){
            $microshop_goods = explode(',',$microshop_goods);
            if(in_array($goods_id,$microshop_goods)){
                return -2;
            }
            array_push($microshop_goods,$goods_id);
            $goodsid = implode(',',$microshop_goods);
        }else{
            $goodsid = $goods_id;
        }
        $data = array(
            'microshop_goods' => $goodsid,
        );
        $res = $member->save($data,[
            'uid'=>$uid
        ]);
        return $res;
    }
    /**
     * 前台取消微店商品
     */
    public function delGoodsId($uid,$goods_id){

        $member = new VslMemberModel();
        $microshop_goods = $member->getInfo(['uid'=>$uid],'microshop_goods')['microshop_goods'];
        if($microshop_goods){
            $microshop_goods = explode(',',$microshop_goods);
            if(in_array($goods_id,$microshop_goods)){
                $key = array_search($goods_id,$microshop_goods);
                array_splice($microshop_goods,$key,1);
                $goodsid = implode(',',$microshop_goods);
            }else{
                return -2;
            }
        }
        $data = array(
            'microshop_goods' => $goodsid,
        );
        $res = $member->save($data,[
            'uid'=>$uid
        ]);
        return $res;
    }
    /**
     * 前台预览微店商品列表
     */
    public function myGoodsList($uid){
        $member = new VslMemberModel();
        $microshop_goods = $member->getInfo(['uid'=>$uid],'microshop_goods')['microshop_goods'];
        if($microshop_goods){
            $condition['ng.goods_id'] = ['in',$microshop_goods];
        }else{
            return [];
        }
        $goods_server = new VslGoodsViewModel();
        $group = 'ng.goods_id';
        $order_sort = 'ng.create_time desc';
        $condition['ng.state'] = 1;
        $condition['ng.website_id'] = $this->website_id;
        $condition['vs.shop_state'] = 1;
        $list = $goods_server->wapGoods(1,0, $condition, 'ng.goods_id,ng.sales,ng.goods_name,sap.pic_cover,ngs.price as goods_price,ngs.market_price as market_price', $order_sort, $group);
        return $list;
    }
    /**
     * 前台预览微店商品分类列表
     */
    public function myCategoryList($uid,$condition=[])
    {
        $member = new VslMemberModel();
        $microshop_goods = $member->getInfo(['uid'=>$uid],'microshop_goods')['microshop_goods'];
        $good = new VslGoodsModel();
        $category = '';
        if($microshop_goods){
            $category1 = array_unique($good->Query(['website_id'=>$this->website_id,'goods_id'=>['in',$microshop_goods]],'category_id_1'));
            $category2 = array_unique($good->Query(['website_id'=>$this->website_id,'goods_id'=>['in',$microshop_goods]],'category_id_2'));
            $category3 = array_unique($good->Query(['website_id'=>$this->website_id,'goods_id'=>['in',$microshop_goods]],'category_id_3'));
            $category = array_merge($category1,$category2,$category3);
        }else{
            return [];
        }
        $goods_category_server = new GoodsCategory();
        $goods_category_model = new VslGoodsCategoryModel();
        $condition['is_visible'] = 1;
        if($category){
            $condition['category_id'] = ['in',implode(',',$category)];
        }
        $condition['website_id'] = $this->website_id;
        $category_info = $goods_category_server->getGoodsCategoryList(1, 0, $condition, 'level ASC', '*');
        $category_list = [];
        foreach ($category_info['data'] as $k => $v) {
            $temp = [
                'category_id' => $v['category_id'],
                'category_name' => $v['category_name'],
                'short_name' => $v['short_name'],
                'category_pic' => getApiSrc($v['category_pic']),
            ];
            if ($v['level'] == 1) {
                $category_list[$v['category_id']] = $temp;
                continue;
            }
            if ($v['level'] == 2) {
                $category_list[$v['pid']]['second_category'][$v['category_id']] = $temp;
                continue;
            }
            if ($v['level'] == 3) {
                // 获取3级分类 pid
                $first_category_id = $goods_category_model::get($v['pid'])['pid'];
                $category_list[$first_category_id]['second_category'][$v['pid']]['third_category'][] = $temp;
                continue;
            }
        }

        // 将数组的key设为0-n
        if (!empty($category_list)) {
            $category_list = array_values($category_list);
        }
        foreach ($category_list as $k_f => $v_f) {
            $category_list[$k_f]['second_category'] = !empty($v_f['second_category']) ? array_values($v_f['second_category']) : [];
            foreach ($category_list[$k_f]['second_category'] as $k_s => $v_s) {
                $category_list[$k_f]['second_category'][$k_s]['third_category'] = !empty($v_s['third_category']) ? array_values($v_s['third_category']) : [];
            }
        }
      return $category_list;
    }

    /**
     * 微店设置
     */
    public function setMicroShopSite($microshop_status,$microshop_pattern, $shopKeeper_check, $goods_id,$pro_types)
    {
        $ConfigService = new AddonsConfigService();
        $value = array(
            'website_id' => $this->website_id,
            'microshop_pattern' => $microshop_pattern,
            'shopKeeper_check' => $shopKeeper_check,
            'goods_id' => $goods_id,
            'pro_types' => $pro_types,
        );
        $MicroShop_info = $ConfigService->getAddonsConfig("microshop",$this->website_id);
        if (! empty($MicroShop_info)) {
            $data = array(
                "value" => json_encode($value),
                "is_use"=>$microshop_status,
                'modify_time' => time()
            );
            $res = $this->addons_config_module->save($data, [
                "website_id" => $this->website_id,
                "addons"=>"microshop"
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig($value, "微店设置", $microshop_status, "microshop");
        }
        return $res;
    }
    /*
     * 获取微店基本设置
     *
     */
    public function getMicroShopSite($website_id){
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
            $config = new AddonsConfigService();
            $MicroShop = $config->getAddonsConfig("microshop",$websiteid);
            $MicroShop_info = json_decode($MicroShop['value'],true);
            $MicroShop_info['goodsid'] = explode(',',$MicroShop_info['goods_id']);
            $goods = new VslGoodsModel();
            $goods_info = $goods->Query(['goods_id'=>['in',$MicroShop_info['goods_id']]],'goods_id,picture,goods_name,price');
            foreach ($goods_info as $k=>$v){
                $pic_id = $v['picture'];
                $pic = new AlbumPictureModel();
                $MicroShop_info['goods_info'][$k]['pic'] = $pic->getInfo(['pic_id'=>$pic_id],'pic_cover_mid')['pic_cover_mid'];
                $MicroShop_info['goods_info'][$k]['goods_name'] =$v['goods_name'];
                $MicroShop_info['goods_info'][$k]['goods_id'] =$v['goods_id'];
                $MicroShop_info['goods_info'][$k]['price'] = $v['price'];
            }
            if($MicroShop_info['goods_info']){
                $MicroShop_info['goods_info'] = array_values($MicroShop_info['goods_info']);
            }else{
                $MicroShop_info['goods_info'] = [];
            }
            $MicroShop_info['is_use'] = $MicroShop['is_use'];
            return $MicroShop_info;
    }
    /**
     * 微店结算设置
     */
    public function setMicroShopSettlementSite($withdrawals_type,$make_money, $profit_calculation, $profit_arrival,$withdrawals_check, $withdrawals_min , $withdrawals_cash, $withdrawals_begin, $withdrawals_end, $poundage)
    {
        $ConfigService = new ConfigService();
        $value = array(
            'website_id' => $this->website_id,
            'withdrawals_type' => $withdrawals_type,
            'profit_calculation' => $profit_calculation,
            'profit_arrival' => $profit_arrival,
            'withdrawals_check' => $withdrawals_check,
            'make_money' => $make_money,
            'withdrawals_min' => $withdrawals_min,
            'withdrawals_cash' => $withdrawals_cash,
            'withdrawals_begin' => $withdrawals_begin,
            'withdrawals_end' => $withdrawals_end,
            'poundage' => $poundage,
        );
        $MicroShop_info = $ConfigService->getConfig(0,"SETMICROSHOPTLEMENT",$this->website_id);
        if (! empty($MicroShop_info)) {
            $data = array(
                "value" => json_encode($value),
            );
            $res = $this->config_module->save($data, [
                "instance_id" => 0,
                "website_id" => $this->website_id,
                "key" => "SETMICROSHOPTLEMENT"
            ]);
        } else {
            $res = $ConfigService->addConfig(0, "SETMICROSHOPTLEMENT", $value, "微店结算设置", 1);
        }
        // TODO Auto-generated method stub
        return $res;
    }
    /*
      * 获取微店结算设置
      *
      */
    public function getMicroShopSettlementSite($website_id=null){
        $config = new ConfigService();
        $MicroShop = $config->getConfig(0,"SETMICROSHOPTLEMENT",$this->website_id);
        $MicroShopInfo = json_decode($MicroShop['value'], true);
        return $MicroShopInfo;
    }
    /**
     * 微店申请协议设置
     */
    public function setMicroShopAgreementSite($content)
    {
        $ConfigService = new ConfigService();
        $value = array(
            'website_id' => $this->website_id,
            'content' => $content
        );
        $agreement_info = $ConfigService ->getConfig(0,"AGREEMICROSHOPMENT",$this->website_id);
        if (! empty($agreement_info)) {
            $data = array(
                "value" => json_encode($value)
            );
            $res = $this->config_module->save($data, [
                "instance_id" => 0,
                "website_id" => $this->website_id,
                "key" => "AGREEMICROSHOPMENT"
            ]);
        } else {
            $res = $ConfigService->addConfig(0, "AGREEMICROSHOPMENT", $value, "申请协议", 1);
        }
        return $res;
    }
    /*
      * 获取微店申请协议
      */
    public function getMicroShopAgreementSite($website_id=null){
        $ConfigService = new ConfigService();
        $MicroShop_info = $ConfigService ->getConfig(0,"AGREEMICROSHOPMENT",$this->website_id);
        $MicroShop_info = json_decode($MicroShop_info['value'],true);
        return $MicroShop_info;
    }
    /**
     * 获得近七天的微店订单金额
     */
    public function getOrderMoneySum($condition)
    {
        $order_profit = new VslOrderMicroShopProfitModel();
        $orderids = array_unique($order_profit->Query(['website_id'=>$condition['website_id']],'order_id'));
        $orderids = implode(',',$orderids);
        $order = new VslOrderModel();
        $condition['order_id'] = ['in',$orderids];
        $orders = $order->Query($condition,'order_money');
        $count = array_sum($orders);
        return $count;
    }
    /**
     * 获得近七天的微店订单收益
     */
    public function getPayMoneySum($condition)
    {
        $order = new VslOrderModel();
        $orderids = $order->Query($condition,'order_id');
        $orderids = implode(',',$orderids);
        $order_profit = new VslOrderMicroShopProfitModel();
        $orders = $order_profit->Query(['order_id'=>['in',$orderids]],'profit');
        $count = array_sum($orders);
        return $count;
    }
    /*
     * 订单商品收益计算
     */
    public function orderMicroShopProfit($params)
    {
        $ConfigService = new ConfigService();
        $order_goods = new VslOrderGoodsModel();
        $order_goods_info = $order_goods->getInfo(['order_goods_id'=>$params['order_goods_id'],'order_id'=>$params['order_id']]);
        $order = new VslOrderModel();
        $order_info = $order->getInfo(['order_id'=>$params['order_id']],'shopkeeper_id');
        $addonsConfigService = new AddonsConfigService();
        $info1 = $addonsConfigService ->getAddonsConfig("microshop",$order_goods_info['website_id']);//基本设置
        $info2 = $ConfigService ->getConfig(0,"SETMICROSHOPTLEMENT",$order_goods_info['website_id']);
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
        $MicroShop = $member->getInfo(['uid' => $params['buyer_id']]);
        $MicroShop_level_id = $MicroShop['microshop_level_id'];
        $base_info = json_decode($info1['value'], true);
        $set_info = json_decode($info2['value'], true);
        $level = new MicroShopLevelModel();
        $ProfitA_id = 0;
        $ProfitA = 0;//一级收益和对应的id
        $ProfitB_id = 0;
        $ProfitB = 0;//二级收益和对应的id
        $ProfitC_id = 0;
        $ProfitC = 0;//三级收益和对应的id
        $Profit_calculation = $set_info['profit_calculation'];//计算节点（商品价格）
        $real_price = 0;
        if ($Profit_calculation == 1) {//实际付款金额
            $real_price = $price;
        }elseif($Profit_calculation == 2) {//商品原价
            $real_price = $original_price;
        }elseif($Profit_calculation == 3) {//商品销售价
            $real_price = $promotion_price;
        }elseif($Profit_calculation == 4) {//商品成本价
            $real_price = $cost_price;
        }elseif($Profit_calculation == 5) {//商品利润价
            $real_price = $profit_price;
        }
        $level_info = $level->getInfo(['id' => $MicroShop_level_id]);
        if ($MicroShop['isshopkeeper'] == 2 && !$order_info['shopkeeper_id']) {//是店主(自购返利)
            $goods_ids = $MicroShop['microshop_goods'];
            if($goods_ids){
                $goods_ids = explode(',',$MicroShop['microshop_goods']);
                if(in_array($params['goods_id'],$goods_ids)){
                    if($level_info['selfpurchase_rebate']){//自购返利
                        $ProfitA_id = $MicroShop['uid'];//获得一级收益的用户id
                        $ProfitA = $level_info['selfpurchase_rebate'] * $real_price/100;
                    }
                }
            }
        }
        if ($order_info['shopkeeper_id']) {//通过微店进入购买微店商品
            if ($base_info['microshop_pattern'] == 3) {//三级收益模式
                if ($order_info['shopkeeper_id']) {//购买的当前店主商品
                    $MicroShopA = $member->getInfo(['uid' => $order_info['shopkeeper_id']]);
                    if($MicroShopA['isshopkeeper']==2){//当前店主
                        $level_infoA = $level->getInfo(['id' => $MicroShopA['microshop_level_id']]);
                        $ProfitA_id = $MicroShopA['uid'];//获得一级收益的用户id
                        $ProfitA1 = $level_infoA['profit1'] / 100;//一级收益比例
                        $ProfitA = twoDecimal($real_price * $ProfitA1*$order_goods_info['num']);//当前店主获得一级收益
                    }
                    $MicroShopB = $member->getInfo(['uid' => $MicroShopA['referee_id']]);//当前店主的推荐人有上级
                    if($MicroShopB['isshopkeeper']==2){//推荐人的上级是店主
                        $level_infoB = $level->getInfo(['id' => $MicroShopB['microshop_level_id']]);
                        $ProfitB_id = $MicroShopB['uid'];//获得二级收益的用户id
                        $ProfitB1 = $level_infoB['profit2'] / 100;//二级收益比例
                        $ProfitB = twoDecimal($real_price * $ProfitB1*$order_goods_info['num']);//当前购买者的推荐人的上级获得二级收益
                    }
                    $MicroShopC = $member->getInfo(['uid' => $MicroShopB['referee_id']]);//当前购买者的推荐人上级有上级
                    if($MicroShopC['isshopkeeper']==2){//推荐人的上级的上级是店主
                        $level_infoC = $level->getInfo(['id' => $MicroShopC['microshop_level_id']]);
                        $ProfitC_id = $MicroShopC['uid'];//获得三级收益的用户id
                        $ProfitC1 = $level_infoC['profit3'] / 100;//三级收益比例
                        $ProfitC = twoDecimal($real_price * $ProfitC1*$order_goods_info['num']);//当前购买者的推荐人的上级的上级获得三级收益
                    }
                }
            }
            if ($base_info['microshop_pattern'] == 2) {//二级收益模式
                if ($order_info['shopkeeper_id']) {//购买的当前店主商品
                    $MicroShopA = $member->getInfo(['uid' => $order_info['shopkeeper_id']]);
                    if($MicroShopA['isshopkeeper']==2){//当前店主
                        $level_infoA = $level->getInfo(['id' => $MicroShopA['microshop_level_id']]);
                        $ProfitA_id = $MicroShopA['uid'];//获得一级收益的用户id
                        $ProfitA1 = $level_infoA['profit1'] / 100;//一级收益比例
                        $ProfitA = twoDecimal($real_price * $ProfitA1*$order_goods_info['num']);//当前店主获得一级收益
                    }
                    $MicroShopB = $member->getInfo(['uid' => $MicroShopA['referee_id']]);//当前店主的推荐人有上级
                    if($MicroShopB['isshopkeeper']==2){//推荐人的上级是店主
                        $level_infoB = $level->getInfo(['id' => $MicroShopB['microshop_level_id']]);
                        $ProfitB_id = $MicroShopB['uid'];//获得二级收益的用户id
                        $ProfitB1 = $level_infoB['profit2'] / 100;//二级收益比例
                        $ProfitB = twoDecimal($real_price * $ProfitB1*$order_goods_info['num']);//当前购买者的推荐人的上级获得二级收益
                    }
                }
            }
            if ($base_info['microshop_pattern'] == 1) {//一级微店模式
                if ($order_info['shopkeeper_id']) {//购买的当前店主商品
                    $MicroShopA = $member->getInfo(['uid' => $order_info['shopkeeper_id']]);
                    if($MicroShopA['isshopkeeper']==2){//当前店主
                        $level_infoA = $level->getInfo(['id' => $MicroShopA['microshop_level_id']]);
                        $ProfitA_id = $MicroShopA['uid'];//获得一级收益的用户id
                        $ProfitA1 = $level_infoA['profit1'] / 100;//一级收益比例
                        $ProfitA = twoDecimal($real_price * $ProfitA1*$order_goods_info['num']);//当前店主获得一级收益
                    }
                }
            }
        }
        $Profit_total = $ProfitA + $ProfitB + $ProfitC;
        $Profit = new VslOrderMicroShopProfitModel();
        $Profit->startTrans();
            try {
                $data = [
                    'order_id' => $params['order_id'],
                    'order_goods_id' => $params['order_goods_id'],
                    'buyer_id' => $params['buyer_id'],
                    'website_id' => $params['website_id'],
                    'profitA_id' => $ProfitA_id,
                    'profitA' => $ProfitA,
                    'profitB_id' => $ProfitB_id,
                    'profitB' => $ProfitB,
                    'profitC_id' => $ProfitC_id,
                    'profitC' => $ProfitC,
                    'profit' => $Profit_total
                ];
                $Profit->save($data);
                $Profit->commit();
                return 1;
            } catch (\Exception $e) {
                $Profit->rollback();
                return $e->getMessage();
            }
    }
    /*
     * 添加收益账户流水表
     */
    public function addProfitMicroShop($params)
    {
        $MicroShop_account = new VslMicroShopAccountRecordsModel();
        $data_records = array();
        $update_records = [];
        $MicroShop_account->startTrans();
        if($params['order_id']){
            $order = new VslOrderModel();
            $params['order_id'] = $order->getInfo(['order_id'=>$params['order_id']],'order_no')['order_no'];
        }
        $records_no = 'PR'.time() . rand(111, 999);
        $records_info = $MicroShop_account->getInfo(['data_id'=>$params['data_id']]);
        try{
            $account_statistics = new VslMicroShopAccountModel();
            $account = new VslAccountModel();
            //更新对应收益账户和平台账户
            $count = $account_statistics->getInfo(['uid'=> $params['uid']],'*');//收益账户
            $account_count = $account_statistics->getInfo(['website_id'=> $params['website_id']],'*');//平台账户
            if($params['status']==1) {//订单完成，添加收益
                //收益账户收益改变
                if ($count) {
                    $account_data = array(
                        'profit' => $count['profit'] + abs($params['profit']),
                        'freezing_profit' => $count['freezing_profit'] - abs($params['profit'])
                    );
                    $account_statistics->save($account_data, ['uid' => $params['uid']]);
                }else{
                    $account_data = array(
                        'profit' =>  abs($params['profit']),
                        'uid' => $params['uid'],
                        'website_id'=>$this->website_id
                    );
                    $account_statistics->save($account_data);
                }
                //平台账户收益改变
                if ($account_count) {
                    $Profit_data = array(
                        'profit' => $account_count['profit'] + abs($params['profit']),
                    );
                    $account->save($Profit_data, ['website_id' => $params['website_id']]);
                }
            }
            if($params['status']==22) {//下级成为店主，添加收益
                //收益账户收益改变
                if ($count) {
                    $account_data = array(
                        'profit' => $count['profit'] + abs($params['profit']),
                    );
                    $account_statistics->save($account_data, ['uid' => $params['uid']]);
                }else{
                    $account_data = array(
                        'profit' =>  abs($params['profit']),
                        'uid' => $params['uid'],
                        'website_id'=>$this->website_id
                    );
                    $account_statistics->save($account_data);
                }
                //平台账户收益改变
                if ($account_count) {
                    $Profit_data = array(
                        'profit' => $account_count['profit'] + abs($params['profit']),
                    );
                    $account->save($Profit_data, ['website_id' => $params['website_id']]);
                    //平台账户流水表
                    $shop = new ShopAccount();
                    $shop->addAccountRecords(0, $params['uid'], '下级成为店主，获得返利', $params['profit'], 32, $params['data_id'], '下级成为店主，获得返利，账户收益增加');
                }
            }
            if($params['status']==2){//订单退款完成，冻结收益改变
                if($count){
                    $Profit_data = array(
                        'freezing_profit' => $count['freezing_profit']-abs($params['profit']),
                    );
                    $account_statistics->save($Profit_data,['uid'=> $params['uid']]);
                }
            }
            if($params['status']==3){//订单支付完成，冻结收益改变
                //店主收益账户改变
                if($count){
                    $Profit_data = array(
                        'freezing_profit' => $count['freezing_profit']+abs($params['profit']),
                    );
                    $account_statistics->save($Profit_data,['uid'=> $params['uid']]);
                }else{
                    $Profit_data = array(
                        'uid' => $params['uid'],
                        'website_id' => $params['website_id'],
                        'freezing_profit' => abs($params['profit']),
                    );
                    $account_statistics->save($Profit_data);
                }
                //平台账户流水表
                $shop = new ShopAccount();
                $shop->addAccountRecords(0, $params['uid'], '订单支付完成收益', $params['profit'], 39, $params['order_id'], '订单支付完成，账户收益增加',$params['website_id']);
            }
            //前期检测
            //更新对应收益流水
            if($params['status']==1){
                $data_records = array(//订单完成
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'balance'=>$count['profit'] + abs($params['profit']),
                    'data_id' => $params['order_id'],
                    'profit' => abs($params['profit']),
                    'from_type' => 1,
                    'website_id' => $params['website_id'],
                    'text' => '订单完成,冻结收益减少,可提现收益增加',
                    'create_time' => time(),
                );
            }
            if($params['status']==2){//订单退款
                $records_count = $MicroShop_account->getInfo(['data_id'=> $params['order_id']],'*');
                if($records_count){
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['profit'],
                        'data_id' => $params['order_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['profit']),
                        'text' => '订单退款,冻结收益减少',
                        'create_time' => time(),
                        'from_type' => 2,
                    );
                }
            }
            if($params['status']==3){//订单支付成功
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['profit'],
                        'data_id' => $params['order_id'],
                        'website_id' => $params['website_id'],
                        'profit' => abs($params['profit']),
                        'text' => '订单支付,冻结收益增加',
                        'create_time' => time(),
                        'from_type' => 3,
                    );
            }
            if($params['status']==4){//收益提现到账户余额
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到账户余额成功,可提现收益减少',
                        'from_type' => 4,
                        'status'=>3
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'records_no'=> $records_no,
                        'balance'=>$count['profit'],
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' => '提现到账户余额成功',
                        'create_time' => time(),
                        'from_type' => 4,//收益提现到账户余额成功
                    );
                }
            }
            if($params['status']==6){//收益提现账户余额审核中
                $data_records = array(
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'balance'=>$count['profit'],
                    'data_id' => $params['data_id'],
                    'website_id' => $params['website_id'],
                    'profit' => (-1)*abs($params['cash']),
                    'tax'=>(-1)*abs($params['tax']),
                    'text' =>'提现到余额待审核,可提现收益减少,冻结收益增加',
                    'create_time' => time(),
                    'from_type' => 6,
                    'status'=>1
                );
            }
            if($params['status']==5){//收益提现到微信待打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到微信待打款',
                        'from_type' => 5,
                        'status'=>2
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'text' =>'提现到微信待打款,可提现收益减少,冻结收益增加',
                        'create_time' => time(),
                        'from_type' => 5,
                        'status'=>2
                    );
                }
            }
            if($params['status']==7){//收益提现到支付宝待打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到支付宝待打款',
                        'from_type' => 7,
                        'status'=>2
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到支付宝待打款,可提现收益减少,冻结收益增加',
                        'create_time' => time(),
                        'from_type' => 7,
                        'status'=>2
                    );
                }
            }
            if($params['status']==8){//收益提现到银行卡待打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到银行卡待打款',
                        'from_type' => 8,
                        'status'=>2
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'text' =>'提现到银行卡待打款,可提现收益减少,冻结收益增加',
                        'create_time' => time(),
                        'from_type' => 8,
                        'status'=>2
                    );
                }
            }
            if($params['status']==9){//收益成功提现到到银行卡
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到银行卡成功,冻结收益减少,已提现收益增加',
                        'from_type' => 9,
                        'status'=>3
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到银行卡成功',
                        'create_time' => time(),
                        'from_type' => 9,
                    );
                }
            }
            if($params['status']==-9){//收益提现到到银行卡失败
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到银行卡失败，等待商家重新打款',
                        'from_type' => -9,
                        'msg' =>$params['msg'],
                        'status'=>4
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'msg' =>$params['msg'],
                        'text' =>'提现到银行卡失败，等待商家重新打款',
                        'create_time' => time(),
                        'from_type' => -9,
                        'status'=>4
                    );
                }
            }
            if($params['status']==10){//收益成功提现到到微信
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到微信成功,冻结收益减少,已提现收益增加',
                        'from_type' => 10,
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到微信成功,冻结收益减少,已提现收益增加',
                        'create_time' => time(),
                        'from_type' => 10,
                    );
                }
            }
            if($params['status']==-10){//收益提现到微信失败
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到微信打款失败,等待商家重新打款',
                        'from_type' => -10,
                        'msg' =>$params['msg'],
                        'status'=>4
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到微信打款失败,等待商家重新打款',
                        'msg' =>$params['msg'],
                        'create_time' => time(),
                        'from_type' => -10,
                        'status'=>4
                    );
                }
            }
            if($params['status']==11){//收益成功提现到支付宝
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到支付宝成功,冻结收益减少,已提现收益增加',
                        'from_type' => 11,
                        'status'=>3
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到支付宝成功',
                        'create_time' => time(),
                        'from_type' => 11,
                    );
                }
            }
            if($params['status']==-11){//收益提现到支付宝失败
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到支付宝打款失败,等待商家重新打款',
                        'from_type' => -11,
                        'msg' =>$params['msg'],
                        'status'=>4
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'msg' =>$params['msg'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到支付宝打款失败,等待商家重新打款',
                        'create_time' => time(),
                        'from_type' => -11,
                        'status'=>4
                    );
                }
            }
            if($params['status']==16){//收益提现到微信拒绝打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到微信,平台拒绝打款',
                        'from_type' => 16,
                        'msg' =>$params['msg'],
                        'status'=>5
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'msg' =>$params['msg'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到微信,平台拒绝打款',
                        'create_time' => time(),
                        'from_type' => 16,
                        'status'=>5
                    );
                }
            }
            if($params['status']==17){//收益提现到支付宝拒绝打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到支付宝,平台拒绝打款',
                        'from_type' => 17,
                        'msg' =>$params['msg'],
                        'status'=>5
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'msg' =>$params['msg'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到支付宝,平台拒绝打款',
                        'create_time' => time(),
                        'from_type' => 17,
                        'status'=>5
                    );
                }
            }
            if($params['status']==18){//收益提现到余额拒绝打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到余额,平台拒绝打款',
                        'from_type' => 18,
                        'msg' =>$params['msg'],
                        'status'=>5
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'msg' =>$params['msg'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到余额,平台拒绝打款',
                        'create_time' => time(),
                        'from_type' => 18,
                        'status'=>5
                    );
                }
            }
            if($params['status']==19){//收益提现到微信审核不通过
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到微信,审核不通过',
                        'from_type' => 19,
                        'msg' =>$params['msg'],
                        'status'=>-1
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'msg' =>$params['msg'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到微信,审核不通过',
                        'create_time' => time(),
                        'from_type' => 19,
                        'status'=>-1
                    );
                }
            }
            if($params['status']==20){//收益提现到支付宝审核不通过
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到支付宝,审核不通过',
                        'from_type' => 20,
                        'msg' => $params['msg'],
                        'status'=>-1
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到支付宝,审核不通过',
                        'create_time' => time(),
                        'msg' => $params['msg'],
                        'from_type' => 20,
                        'status'=>-1
                    );
                }
            }
            if($params['status']==24){//收益提现到银行卡审核不通过
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到支付宝,审核不通过',
                        'from_type' => 20,
                        'msg' => $params['msg'],
                        'status'=>-1
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'msg' =>$params['msg'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到银行卡,审核不通过',
                        'create_time' => time(),
                        'from_type' => -11,
                        'status'=>-1
                    );
                }
            }
            if($params['status']==21){//收益提现到余额审核不通过
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到余额,审核不通过',
                        'msg' => $params['msg'],
                        'from_type' => 21,
                        'status'=>-1
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'msg' => $params['msg'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到余额,审核不通过',
                        'create_time' => time(),
                        'from_type' => -11,
                        'status'=>-1
                    );
                }
            }
            if($params['status']==23){//收益提现到银行卡拒绝打款
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到银行卡,平台拒绝打款',
                        'msg' => $params['msg'],
                        'from_type' => 23,
                        'status'=>5
                    );
                }else{
                    $data_records = array(
                        'uid' => $params['uid'],
                        'balance'=>$count['profit'],
                        'msg' => $params['msg'],
                        'records_no'=> $records_no,
                        'data_id' => $params['data_id'],
                        'website_id' => $params['website_id'],
                        'profit' => (-1)*abs($params['cash']),
                        'tax'=>(-1)*abs($params['tax']),
                        'text' =>'提现到银行卡,平台拒绝打款',
                        'create_time' => time(),
                        'from_type' => 12,
                        'status'=>5
                    );
                }
            }
            if($params['status']==12){//收益提现到银行卡审核中
                $data_records = array(
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'balance'=>$count['profit'],
                    'data_id' => $params['data_id'],
                    'website_id' => $params['website_id'],
                    'profit' => (-1)*abs($params['cash']),
                    'tax'=>(-1)*abs($params['tax']),
                    'text' =>'提现到银行卡审核中,可提现收益减少,冻结收益增加',
                    'create_time' => time(),
                    'from_type' => 12,
                    'status'=>1
                );
            }
            if($params['status']==13){//收益提现到微信审核中
                $data_records = array(
                    'uid' => $params['uid'],
                    'balance'=>$count['profit'],
                    'records_no'=> $records_no,
                    'data_id' => $params['data_id'],
                    'website_id' => $params['website_id'],
                    'profit' => (-1)*abs($params['cash']),
                    'tax'=>(-1)*abs($params['tax']),
                    'text' =>'提现到微信审核中,可提现收益减少,冻结收益增加',
                    'create_time' => time(),
                    'from_type' => 13,
                    'status'=>1
                );
            }
            if($params['status']==14){//收益提现到支付宝审核中
                $data_records = array(
                    'uid' => $params['uid'],
                    'balance'=>$count['profit'],
                    'records_no'=> $records_no,
                    'data_id' => $params['data_id'],
                    'website_id' => $params['website_id'],
                    'profit' => (-1)*abs($params['cash']),
                    'tax'=>(-1)*abs($params['tax']),
                    'text' =>'提现到支付宝审核中,可提现收益减少,冻结收益增加',
                    'create_time' => time(),
                    'from_type' => 14,
                    'status'=>1
                );
            }
            if($params['status']==15){//收益提现到账户余额待打款中
                if($records_info){
                    $update_records = array(
                        'text' =>'提现到余额待打款',
                        'from_type' => 15,
                        'status'=>1
                    );
                }
                $data_records = array(
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'balance'=>$count['profit'],
                    'data_id' => $params['data_id'],
                    'website_id' => $params['website_id'],
                    'profit' => (-1)*abs($params['cash']),
                    'tax'=>(-1)*abs($params['tax']),
                    'text' =>'提现到余额待打款,可提现收益减少,冻结收益增加',
                    'create_time' => time(),
                    'from_type' => 15,
                    'status'=>2
                );
            }
            if($params['status']==22){//下级成为店主，获得返利
                $data_records = array(
                    'uid' => $params['uid'],
                    'records_no'=> $records_no,
                    'balance'=>$count['profit'],
                    'data_id' => $params['data_id'],
                    'website_id' => $params['website_id'],
                    'profit' => $params['profit'],
                    'text' =>'下级成为店主，获得返利，可提现收益增加',
                    'create_time' => time(),
                    'from_type' => 22,
                );
            }
            if($data_records){
                $MicroShop_account->save($data_records);
            }
            if($update_records){
                $MicroShop_account->save($update_records,['data_id'=>$params['data_id']]);
            }
            $MicroShop_account->commit();
            return 1;
        } catch (\Exception $e)
        {
            $MicroShop_account->rollback();
            return $e->getMessage();
        }
    }
    /*
     * 后台设置成为店主
     */
    public function setStatus($uid){
        $MicroShop_level = new MicroShopLevelModel();
        $member = new VslMemberModel();;
        $MicroShop = $member->getInfo(['uid'=>$uid],'*');
        if($this->website_id){
            $website_id = $this->website_id;
        }else{
            $website_id =  $MicroShop['website_id'];
        }
        $level_id = $MicroShop_level->getInfo(['website_id' => $website_id,'is_default'=>1],'id')['id'];
        $microshop_name = $MicroShop['mobile'].'的小店';
        $res = $member->save(['isshopkeeper'=>2,'microshop_name'=>$microshop_name,'microshop_level_id'=>$level_id,'become_shopkeeper_time'=>time()],['uid'=>$uid]);
        // $this->becomeShopKeeperProfit($uid,$base_info['goods_id'],$order_info['order_id']);
        return $res;
    }
    /*
     * 成为店主，立即续费和升级
     */
    public function becomeShopKeeper($uid,$order_id){
        $member = new VslMemberModel();
        $MicroShop = $member->getInfo(['uid'=>$uid],'*');
        $config = new AddonsConfigService();
        $MicroShop_info = $config->getAddonsConfig("microshop",$MicroShop['website_id']);
        $base_info = json_decode($MicroShop_info['value'], true);
        $order = new VslOrderModel();
        $order_info = $order->getInfo(['order_id'=>$order_id],'*');
        $MicroShop_level = new MicroShopLevelModel();
        $order_goods = new VslOrderGoodsModel();
        if($order_info['order_type']==2 && $MicroShop['isshopkeeper']!=2 && $order_info['order_status']==1){//订单支付完成成为店主
            //判断是否购买过指定商品
            if($base_info['goods_id']){
                $orders_id = $order_goods->Query(['goods_id'=>['in',$base_info['goods_id']],'buyer_id'=>$uid],'order_id');
                $goods_info = $order->Query(['order_id'=>['IN',implode(',',$orders_id)],'order_type'=>2,'order_status'=>1],'order_id');
                if($goods_info){//当前用户购买过指定商品
                    $level_id = $MicroShop_level->getInfo(['website_id' => $MicroShop['website_id'],'is_default'=>1],'id')['id'];
                    $microshop_name = $MicroShop['mobile'].'的小店';
                    $member->save(['isshopkeeper'=>2,'microshop_name'=>$microshop_name,'microshop_level_id'=>$level_id,'become_shopkeeper_time'=>time()],['uid'=>$uid]);
                    $this->becomeShopKeeperProfit($uid,$base_info['goods_id'],$order_info['order_id']);
                }
            }
        }
        if($order_info['order_type']==3 && $MicroShop['isshopkeeper']==2 && $order_info['order_status']==1){//购买商品续费
            $microshop_level_info = $MicroShop_level->getInfo(['id'=>$MicroShop['microshop_level_id']],'*');
            $microshop_level_goods = $microshop_level_info['goods_id'];
            $level_goods_id = explode(',',$microshop_level_goods);
            $order_goods_id = $order_goods->getInfo(['order_id'=>$order_id,'goods_id'=>['in',$level_goods_id]],'*');
            if($order_goods_id && $microshop_level_info['is_default']!=1){//当前购买的商品是续费商品
                $term_validity = $microshop_level_info['term_validity'];
                $validity = $microshop_level_info['validity'];
                if($MicroShop['shopkeeper_level_time']){
                    $time = $MicroShop['shopkeeper_level_time'];
                }else{
                    $time = time();
                }
                if($validity==1){
                    $times = strtotime("+".$term_validity."day",$time);
                }
                if($validity==2){
                    $times = strtotime("+".$term_validity."month",$time);
                }
                if($validity==3){
                    $times =strtotime("+".$term_validity."year",$time);
                }
                $member->save(['shopkeeper_level_time'=>$times],['uid'=>$uid]);
            }
        }
        if($order_info['order_type']==4 && $MicroShop['isshopkeeper']==2 && $order_info['order_status']==1){//购买商品升级
            $level_ids = $MicroShop_level->getQuery(['is_default'=>0,'website_id'=>$MicroShop['website_id'],'id'=>['neq',$MicroShop['microshop_level_id']]],'id','weight asc');
            if($level_ids){
                $level_ids = objToArr($level_ids);
                foreach($level_ids as $k=>$v){
                    $info = $MicroShop_level->getInfo(['id'=>$v['id']],'*');
                    $level_goods_id = explode(',',$info['goods_id']);
                    $order_goods_id = $order_goods->getInfo(['order_id'=>$order_id,'goods_id'=>['in',$level_goods_id]],'*');
                    if($order_goods_id){
                        $term_validity = $info['term_validity'];
                        $validity = $info['validity'];
                        if($MicroShop['shopkeeper_level_time']){
                            $time = $MicroShop['shopkeeper_level_time'];
                        }else{
                            $time = time();
                        }
                        if($validity==1){
                            $times = strtotime("+".$term_validity."day",$time);
                        }
                        if($validity==2){
                            $times = strtotime("+".$term_validity."month",$time);
                        }
                        if($validity==3){
                            $times =strtotime("+".$term_validity."year",$time);
                        }
                        $member = new VslMemberModel();
                        $member->save(['shopkeeper_level_time'=>$times,'microshop_level_id'=>$v['id']],['uid'=>$uid]);
                    }
                }
            }
        }
    }
    /**
     * 下级成为店主上级获得返利
     */
    public function becomeShopKeeperProfit($uid,$goods_id,$order_id){
        $MicroShop_level = new MicroShopLevelModel();
        $member = new VslMemberModel();;
        $MicroShop = $member->getInfo(['uid'=>$uid],'*');
        if($MicroShop['referee_id']){
            $MicroShops = $member->getInfo(['uid'=>$MicroShop['referee_id']],'*');
            if($MicroShops['isshopkeeper']==2){
                $goods = new VslGoodsModel();
                $goods_price = $goods->getInfo(['goods_id'=>$goods_id],'promotion_price')['promotion_price'];
                $shop_rebate = $MicroShop_level->getInfo(['id' => $MicroShops['microshop_level_id']],'shop_rebate')['shop_rebate'];
                $real_money = twoDecimal($goods_price * $shop_rebate/100);
                $data['profit'] = $real_money;
                $data['status'] = 22;
                $data['uid'] = $MicroShops['uid'];
                $data['website_id'] = $MicroShops['website_id'];
                $data['data_id'] = $order_id;
                $this->addProfitMicroShop($data);
            }
        }

    }
    /**
     * 提现详情
     */
    public function withdrawDetail($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $list = $this->getProfitWithdrawList($page_index, $page_size, $where, $order);
        return $list;
    }
    /**
     * 收益提现设置
     */
    public function getProfitWithdrawConfig($uid){
        $config = new ConfigService();
        $account = new VslMicroShopAccountModel();
        $account_info = $account->getInfo(['uid'=>$uid],'*');
        $config_info = $config->getConfig(0,"SETMICROSHOPTLEMENT",$account_info['website_id']);
        $list = json_decode($config_info['value'],true);
        $config_set = $config->getConfig(0,"WITHDRAW_BALANCE",$account_info['website_id']);
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
        if($account_info){
            $list['withdraw_money'] = $account_info['profit']-$account_info['withdrawals'];
            $list['profit'] = $account_info['profit'];
            $list['tax'] = $account_info['tax'];
            $list['withdrawals'] = $account_info['withdrawals'];
            $list['freezing_profit'] = $account_info['freezing_profit'];
        }
        $withdraw_account = new VslMicroShopProfitWithdrawModel();
        $list['make_withdraw'] = abs(array_sum($withdraw_account->Query(['uid'=>$uid,'status'=>2],'cash')));//待打款
        $list['apply_withdraw'] = abs(array_sum($withdraw_account->Query(['uid'=>$uid,'status'=>1],'cash')));//审核中
        $list['account_list'] = $this->getMemberBankAccount($is_default = 0,$uid);
        return $list;
    }
    /**
     * 收益提现账户类型
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
                ], '*');
            }
        }
        return $bank_account_list;
    }
    /**
     * 收益提现
     */
    public function addMicroShopProfitWithdraw($withdraw_no,$uid,$account_id,$cash){
        // 平台的提现设置
        $fail =0;
        $member = new VslMemberModel();
        $member_info = $member->getInfo(['uid'=>$uid],'*');
        $website_id = $this->website_id;
        $real_name = $member_info['real_name'];
        $config = new ConfigService();
        $config_set = $config->getConfig(0,"WITHDRAW_BALANCE",$member_info['website_id']);
        $Profit_withdraw_set = json_decode($config->getConfig(0,'SETMICROSHOPTLEMENT',$member_info['website_id'])['value'],true);
        // 判断是否提现设置是否为空 是否启用
        if (empty($config_set) || $config_set['is_use'] == 0) {
            return USER_WITHDRAW_NO_USE;
        }
        // 最小提现额判断
        if ($cash < $Profit_withdraw_set["withdrawals_min"]) {
            return USER_WITHDRAW_MIN;
        }
        // 判断当前店主的可提现收益
        $account = new VslMicroShopAccountModel();
        $Profit_info = $account->getInfo(['uid'=>$uid],'*');
        $Profit = $Profit_info['profit'];
        if ($Profit <= 0) {
            return ORDER_CREATE_LOW_PLATFORM_MONEY;
        }
        if ($Profit < $cash || $cash <= 0) {
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
            if($type==4){
                $type =5;
            }
        }
        if($type==1 || $type ==5){
            if($Profit_withdraw_set['withdraw_message']){
                $withdraw_message = explode(',',$Profit_withdraw_set['withdraw_message']);
                if(in_array(5,$withdraw_message)){
                    $type =5;
                }
            }
        }
        // 添加收益提现记录
        $Profit_withdraw = new VslMicroShopProfitWithdrawModel();
        try{
            // 查询提现审核方式
            if( abs($cash)<= $Profit_withdraw_set['withdrawals_cash']){//提现金额小于免审核区间
                $is_examine = 1;
            }else{
                $is_examine = $Profit_withdraw_set['withdrawals_check'];
            }
            $tax = $cash;
            //收益个人所得税
            if($Profit_withdraw_set['poundage']) {
                $tax = twoDecimal($cash * $Profit_withdraw_set['poundage']/100);//个人所得税
                if($Profit_withdraw_set['withdrawals_end'] && $Profit_withdraw_set['withdrawals_begin']){
                    if (abs($cash) <= $Profit_withdraw_set['withdrawals_end'] && abs($cash) >= $Profit_withdraw_set['withdrawals_begin']) {
                        $tax = 0;//免打税区间
                    }
                }
            }else{
                $tax = 0;//免打税区间
            }
            if($cash+$tax<=$Profit){
                $income_tax = $cash;
            }else if($cash-$tax>=0){
                $income_tax = $cash-$tax;
            }else{
                return ORDER_CREATE_LOW_PLATFORM_MONEY;
            }
            // 查询提现打款方式
            $make_money = $Profit_withdraw_set['make_money'];
            if($is_examine==1 && $make_money==1){//自动审核自动打款
                if($account_id==-1){
                    $data = array(
                        'withdraw_no' => $withdraw_no,
                        'uid' => $uid,
                        'account_number' => $account_number,
                        'realname' => $real_name,
                        'type'   => $type,
                        'cash' => (-1)*$cash,
                        'tax'=>(-1)*$tax,
                        'income_tax'=>$income_tax,//税后金额
                        'ask_for_date' => time(),
                        'status' => 3,//直接提现到账户余额
                        'website_id' => $website_id
                    );
                }else {
                    $data = array(
                        'withdraw_no' => $withdraw_no,
                        'uid' => $uid,
                        'account_number' => -1,
                        'income_tax'=>$income_tax,//税后金额
                        'realname' => $real_name,
                        'type' => $type,
                        'tax'=>(-1)*$tax,
                        'cash' => (-1)*$cash,
                        'ask_for_date' => time(),
                        'status' => 2,//审核通过，待打款
                        'website_id' => $website_id
                    );
                }
                $res = $Profit_withdraw->save($data);
                if($res){
                    if($account_id==-1){
                        $data['data_id'] = $data['withdraw_no'];
                        $data['text'] = '收益成功提现到余额';
                        $this->addProfitWithdraw($data,1);//自动审核自动打款直接提现到账户余额
                    }else{
                        $withdraw_info = $Profit_withdraw->getInfo(['id'=>$res],'*');
                        $data_Profit['uid'] = $withdraw_info['uid'];
                        $data_Profit['website_id'] = $withdraw_info['website_id'];
                        $data_Profit['profit'] = $withdraw_info['income_tax'];
                        $data_Profit['income_tax'] = $withdraw_info['income_tax'];
                        $data_Profit['cash'] = $withdraw_info['cash'];
                        $data_Profit['data_id'] = $data['withdraw_no'];
                        $data_Profit['tax'] = $withdraw_info['tax'];
                        if($type==1){
                            $params['shop_id'] = 0;
                            $params['takeoutmoney'] = abs($withdraw_info['cash']);
                            $params['uid'] = $uid;
                            $params['website_id'] = $this->website_id;
                            $data_Profit['status'] = 8;
                            $data_Profit['text'] = '收益提现到银行卡待打款';
                            $this->updateAccountWithdraw(8,$data_Profit);
                            $bank = new VslMemberBankAccountModel();
                            $bank_id = $bank->getInfo(['account_number'=>$withdraw_info['account_number'],'uid'=>$withdraw_info['uid']],'id')['id'];
                            $tlpay_pay = new tlPay();
                            // $retval = $tlpay_pay->tlWithdraw($withdraw_info['withdraw_no'],$withdraw_info['uid'],$bank_id,abs($withdraw_info['income_tax']));
                            $retval['is_success']=1;
                            if($retval['is_success']==1){//自动打款成功
                                runhook('Notify', 'withdrawalSuccessBySms', $params);
                                $data_Profit['status'] =9;
                                $data_Profit['text'] = '收益提现成功到银行卡';//银行卡
                                $this->addAccountWithdrawUserRecords($data_Profit, 2, $res, "收益银行卡提现，打款成功。");
                                $Profit_withdraw->save(["payment_date"=>time(),"status" => 3,"memo"=>'收益银行卡提现打款成功'],["id" => $res]);
                            }else{//自动打款失败
                                $data_Profit['status'] =-9;
                                $data_Profit['msg'] =$retval['msg'];
                                $data_Profit['text'] = '收益银行卡提现打款失败';//银行卡
                                $this->addAccountWithdrawUserRecords($data_Profit, 2, $res, "收益银行卡提现，打款失败。");
                                $Profit_withdraw->save(["status" => 5,"memo"=>$retval['msg']],["id" => $res]);
                                $fail =1;
                            }
                        }
                        if($type==2){
                            $params['shop_id'] = 0;
                            $params['takeoutmoney'] = abs($withdraw_info['cash']);
                            $params['uid'] = $uid;
                            $params['website_id'] = $this->website_id;
                            $data_Profit['status'] = 5;
                            $data_Profit['text'] = '收益提现到微信待打款';
                            $this->updateAccountWithdraw(5,$data_Profit);
                            $user_info = new UserModel();
                            $wx_openid = $user_info->getInfo(['uid'=>$withdraw_info['uid']],'wx_openid')['wx_openid'];
                            $weixin_pay = new WeiXinPay();
                            $retval = $weixin_pay->EnterprisePayment($wx_openid,$withdraw_info['withdraw_no'],'',abs($withdraw_info['income_tax']),'收益微信提现',$this->website_id);
                            if($retval['is_success']==1){//自动打款成功
                                runhook('Notify', 'withdrawalSuccessBySms', $params);
                                $data_Profit['status'] =10;
                                $data_Profit['text'] = '收益提现成功到微信';//微信
                                $this->addAccountWithdrawUserRecords($data_Profit, 2, $res, "收益微信提现，打款成功。");
                                $Profit_withdraw->save(["payment_date"=>time(),"status" => 3,"memo"=>'收益微信提现打款成功'],["id" => $res]);
                            }else{//自动打款失败
                                $data_Profit['status'] =-10;
                                $data_Profit['msg'] =$retval['msg'];
                                $data_Profit['text'] = '收益微信提现打款失败';//微信
                                $this->addAccountWithdrawUserRecords($data_Profit, 2, $res, "收益微信提现，打款失败。");
                                $Profit_withdraw->save(["status" => 5,"memo"=>$retval['msg']],["id" => $res]);
                                $fail =1;
                            }
                        }
                        if($type==3){
                            $data_Profit['status'] = 7;
                            $data_Profit['text'] = '收益提现到支付宝待打款';
                            $this->updateAccountWithdraw(7,$data_Profit);
                            $alipay_pay = new AliPay();
                            $retval = $alipay_pay->aliPayTransferNew($withdraw_info['withdraw_no'],$withdraw_info['account_number'],abs($withdraw_info['income_tax']));
                            if($retval['is_success']==1){
                                runhook('Notify', 'withdrawalSuccessBySms', $params);
                                $data_Profit['status'] =11;
                                $data_Profit['text'] = '收益提现成功到支付宝';//支付宝
                                $this->addAccountWithdrawUserRecords($data_Profit, 2, $res, "收益支付宝提现，打款成功。");
                                $Profit_withdraw->save(["payment_date"=>time(),"status" => 3,"memo"=>'收益支付宝提现打款成功'],["id" => $res]);
                            }else{//自动打款失败
                                $data_Profit['status'] =-11;
                                $data_Profit['msg'] =$retval['msg'];
                                $data_Profit['text'] = '收益支付宝提现打款失败';//支付宝
                                $this->addAccountWithdrawUserRecords($data_Profit, 2, $res, "收益支付宝提现，打款失败。");
                                $Profit_withdraw->save(["status" => 5,"memo"=>$retval['msg']],["id" => $res]);
                                $fail =1;
                            }
                        }
                        if($type==5){
                            $params['shop_id'] = 0;
                            $params['takeoutmoney'] = abs($withdraw_info['cash']);
                            $params['uid'] = $uid;
                            $params['website_id'] = $this->website_id;
                            $data_Profit['status'] = 8;
                            $data_Profit['text'] = '收益提现到银行卡待打款';
                            $this->updateAccountWithdraw(8,$data_Profit);
                            runhook('Notify', 'withdrawalSuccessBySms', $params);
                            $data_Profit['status'] =9;
                            $data_Profit['text'] = '收益提现成功到银行卡';//银行卡
                            $this->addAccountWithdrawUserRecords($data_Profit, 2, $res, "收益银行卡提现，打款成功。");
                            $Profit_withdraw->save(["payment_date"=>time(),"status" => 3,"memo"=>'收益银行卡提现打款成功'],["id" => $res]);
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
                    'tax'=>(-1)*$tax,
                    'ask_for_date' => time(),
                    'status' => 1,//提现审核中
                    'website_id' => $website_id
                );
              $rel = $Profit_withdraw->save($data);
                if($rel){
                    $data_Profit = array(
                        'uid' => $uid,
                        'income_tax'=>$income_tax,//税后金额
                        'profit' => $income_tax,
                        'cash' => $cash,
                        'tax' => $tax,
                        'data_id'=>$withdraw_no,
                        'website_id' => $data['website_id']
                    );
                    if($account_id==-1){
                        $data_Profit['status'] = 6;
                        $data_Profit['text'] ='提现到账户余额待审核';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                    if($type==1 || $type==5){
                        $data_Profit['status'] = 12;
                        $data_Profit['text'] ='提现到银行卡待审核';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                    if($type==2){
                        $data_Profit['status'] = 13;
                        $data_Profit['text'] ='提现到微信待审核';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                    if($type==3){
                        $data_Profit['status'] = 14;
                        $data_Profit['text'] ='提现到支付宝待审核';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                }
            }
            if($is_examine==1 && $make_money==2){//自动审核待打款
                $data = array(
                    'withdraw_no' => $withdraw_no,
                    'uid' => $uid,
                    'tax'=>(-1)*$tax,
                    'account_number' => $account_number,
                    'realname' => $real_name,
                    'income_tax'=>$income_tax,//税后金额
                    'type' => $type,
                    'cash' => (-1)*$cash,
                    'ask_for_date' => time(),
                    'status' => 2,//审核通过，待打款
                    'website_id' => $website_id
                );
                $rel = $Profit_withdraw->save($data);
                if($rel){
                    $data_Profit = array(
                        'uid' => $uid,
                        'income_tax'=>$income_tax,//税后金额
                        'profit' => $income_tax,
                        'cash' => $cash,
                        'tax' => $tax,
                        'data_id'=>$withdraw_no,
                        'website_id' => $data['website_id']
                    );
                    if($type==4){
                        $data_Profit['status'] = 15;
                        $data_Profit['text'] ='提现到账户余额待打款';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                    if($type==1 || $type==5){
                        $data_Profit['status'] = 8;
                        $data_Profit['text'] ='提现到银行卡待打款';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                    if($type==2){
                        $data_Profit['status'] = 5;
                        $data_Profit['text'] ='提现到微信待打款';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                    if($type==3){
                        $data_Profit['status'] = 7;
                        $data_Profit['text'] ='提现到支付宝待打款';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
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
                    'tax'=>(-1)*$tax,
                    'type' => $type,
                    'cash' => (-1)*$cash,
                    'ask_for_date' => time(),
                    'status' => 1,//提现审核中
                    'website_id' => $website_id
                );
                $rel = $Profit_withdraw->save($data);
                if($rel){
                    $data_Profit = array(
                        'uid' => $uid,
                        'income_tax'=>$income_tax,//税后金额
                        'profit' => $income_tax,
                        'cash' => $cash,
                        'tax' => $tax,
                        'data_id'=>$withdraw_no,
                        'website_id' => $data['website_id']
                    );
                    if($account_id==-1){
                        $data_Profit['status'] = 6;
                        $data_Profit['text'] ='提现到账户余额待审核';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                    if($type==1 || $type==5){
                        $data_Profit['status'] = 12;
                        $data_Profit['text'] ='提现到银行卡待审核';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                    if($type==2){
                        $data_Profit['status'] = 13;
                        $data_Profit['text'] ='提现到微信待审核';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                    if($type==3){
                        $data_Profit['status'] = 14;
                        $data_Profit['text'] ='现到支付宝待审核';
                        $this->addAccountWithdrawUserRecords($data_Profit,2, $rel,  $data_Profit['text']);
                    }
                }
            }
            $Profit_withdraw->commit();
            if($fail==1){
                return -9000;
            }
            return $Profit_withdraw->id;
        }catch (\Exception $e)
        {
            $Profit_withdraw->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 收益成功提现到账户余额
     */
    public function addProfitWithdraw($data,$check)
    {
        $Profit_withdraw = new VslMemberAccountRecordsModel();
        $data1 = array(
            'records_no' => getSerialNo(),
            'uid' => $data['uid'],
            'account_type' => 2,
            'number' => $data['income_tax'],
            'data_id' => $data['data_id'],
            'from_type' => 14,
            'text' => '收益成功提现到余额',
            'create_time' => time(),
            'website_id' => $data['website_id']
        );
        $Profit_withdraw->save($data1);//添加会员流水
        $data_Profit = array(
            'uid' => $data['uid'],
            'profit' => $data['income_tax'],
            'data_id' => $data['data_id'],
            'cash'=>$data['cash'],
            'tax'=>$data['tax'],
            'status' => 4,
            'text' => $data['text'],
            'website_id' => $data['website_id']
        );
        $res = $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        if ($res) {
            $member_account = new VslMemberAccountModel();
            $account_info = $member_account->getInfo(['uid' => $data['uid']], '*');
            $data2 = array(
                'balance' => abs($data['income_tax']) + $account_info['balance']
            );
            $member_account->save($data2, ['uid' => $data['uid']]);//更新会员账户余额
            // 添加平台的整体资金流水
            $acount = new ShopAccount();
            if(abs($data['tax'])>0){
                $acount->addAccountRecords(0, $data['uid'], "收益提现成功，个人所得税!",abs($data['tax']), 27, $data['data_id'], '收益提现到账户余额，个人所得税增加');
            }
            $Profit_account = new VslMicroShopAccountModel();
            $Profit_account_info = $Profit_account->getInfo(['uid' => $data['uid']], '*');
            if ($check == 1) {
                $data3 = array(
                    'withdrawals' => $Profit_account_info['withdrawals'] + abs($data['cash'])+abs($data['tax']),//已提现收益增加
                    'tax'=>$Profit_account_info['tax']+abs($data['tax']),
                    'profit' => $Profit_account_info['profit'] - abs($data['income_tax'])-abs($data['tax']),//可提现收益减少
                );
                $Profit_account->save($data3, ['uid' => $data['uid']]);//更新收益账户
            }else{
                $data4 = array(
                    'tax'=>$Profit_account_info['tax']+abs($data['tax']),
                    'freezing_profit' => $Profit_account_info['freezing_profit'] - abs($data['income_tax'])-abs($data['tax']),//冻结收益减少
                    'withdrawals' => $Profit_account_info['withdrawals'] + abs($data['cash'])+abs($data['tax']),//已提现收益增加
                );
                $Profit_account = new VslMicroShopAccountModel();
                $Profit_account->save($data4, ['uid' => $data['uid']]);//更新收益账户
            }
            $withdraw = new VslMicroShopProfitWithdrawModel();
            $res = $withdraw->save(['payment_date' => time(), 'status' => 3], ['withdraw_no' => $data['data_id']]);//更新收益提现状态
            return $res;
        }
    }

    /**
     * 修改收益提现状态
     */
    public function profitWithdrawAudit($id,$status,$memo)
    {
        $MicroShop_Profit_withdraw = new VslMicroShopProfitWithdrawModel();
        $Profit_info = $MicroShop_Profit_withdraw->getInfo(['id'=>$id],"*");
        $res =0;
        $config = new ConfigService();
        $profit_withdraw_set = json_decode($config->getConfig(0,'SETMICROSHOPTLEMENT',$Profit_info['website_id'])['value'],true);
        $make_money = $profit_withdraw_set['make_money'];
        if($Profit_info  && $status == 2 && $make_money!=1) { // 平台手动审核通过提现待打款，更新提现状态
            $data_Profit['data_id'] = $Profit_info['withdraw_no'];
            $res = $MicroShop_Profit_withdraw->save(['status'=>$status],['id'=>$id]);
        }
        if($Profit_info  && $status == 2 && $make_money==1) { // 平台手动审核通过提现自动打款，更新提现状态
            $data_Profit['data_id'] = $Profit_info['withdraw_no'];
            $data_Profit['website_id'] = $Profit_info['website_id'];
            $data_Profit['profit'] = $Profit_info['income_tax'];
            $data_Profit['cash'] = $Profit_info['cash'];
            $data_Profit['income_tax'] = $Profit_info['income_tax'];
            $data_Profit['uid'] =  $Profit_info['uid'];
            $data_Profit['tax'] =  $Profit_info['tax'];
            if($Profit_info['type']==1){//银行卡
                $params['shop_id'] = 0;
                $params['takeoutmoney'] = abs($Profit_info['cash']);
                $params['uid'] =  $Profit_info['uid'];
                $params['website_id'] = $Profit_info['website_id'];
                $bank = new VslMemberBankAccountModel();
                $bank_id = $bank->getInfo(['account_number'=>$Profit_info['account_number'],'uid'=>$Profit_info['uid']],'id')['id'];
                $tlpay_pay = new tlPay();
                $retval = $tlpay_pay->tlWithdraw($Profit_info['withdraw_no'],$Profit_info['uid'],$bank_id,abs($Profit_info['income_tax']));
                if($retval['is_success']==1){//自动打款成功
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_Profit['status'] =9;
                    $data_Profit['text'] = '收益提现成功到银行卡';//微信
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益银行卡提现，打款成功。");
                    $res =$MicroShop_Profit_withdraw->save(['status'=>3,"payment_date"=>time(),"memo"=>'收益银行卡提现打款成功'],['id'=>$id]);
                }else{//自动打款失败
                    $data_Profit['status'] =-9;
                    $data_Profit['msg'] =$retval['msg'];
                    $data_Profit['text'] = '收益银行卡提现打款失败';//微信
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益银行卡提现，打款失败。");
                    $res = $MicroShop_Profit_withdraw->save(['status'=>5,"memo"=>$retval['msg']],['id'=>$id]);
                    return -9000;
                }
            }
            if($Profit_info['type']==2){//微信
                $params['shop_id'] = 0;
                $params['takeoutmoney'] = abs($Profit_info['cash']);
                $params['uid'] =  $Profit_info['uid'];
                $params['website_id'] = $Profit_info['website_id'];
                $user_info = new UserModel();
                $wx_openid = $user_info->getInfo(['uid'=>$Profit_info['uid']],'wx_openid')['wx_openid'];
                $weixin_pay = new WeiXinPay();
                $retval = $weixin_pay->EnterprisePayment($wx_openid,$Profit_info['withdraw_no'],'',abs($Profit_info['income_tax']),'收益微信提现',$this->website_id);
                if($retval['is_success']==1){//自动打款成功
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_Profit['status'] =10;
                    $data_Profit['text'] = '收益提现成功到微信';//微信
                    $data_Profit['data_id'] =$Profit_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益微信提现，打款成功。");
                    $res =$MicroShop_Profit_withdraw->save(['status'=>3,"payment_date"=>time(),"memo"=>'收益微信提现打款成功'],['id'=>$id]);
                }else{//自动打款失败
                    $data_Profit['status'] =-10;
                    $data_Profit['msg'] =$retval['msg'];
                    $data_Profit['text'] = '收益微信提现打款失败';//微信
                    $data_Profit['data_id'] =$Profit_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益微信提现，打款失败。");
                    $res = $MicroShop_Profit_withdraw->save(['status'=>5,"memo"=>$retval['msg']],['id'=>$id]);
                    return -9000;
                }
            }
            if($Profit_info['type']==3){//支付宝
                $params['shop_id'] = 0;
                $params['takeoutmoney'] = abs($Profit_info['cash']);
                $params['uid'] =  $Profit_info['uid'];
                $params['website_id'] = $Profit_info['website_id'];
                $alipay_pay = new AliPay();
                $retval = $alipay_pay->aliPayTransferNew($Profit_info['withdraw_no'],$Profit_info['account_number'],abs($Profit_info['income_tax']));
                if($retval['is_success']==1){
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_Profit['status'] =11;
                    $data_Profit['text'] = '收益提现成功到支付宝';//支付宝
                    $data_Profit['data_id'] =$Profit_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益支付宝提现，打款成功。");
                    $res  = $MicroShop_Profit_withdraw->save(['status'=>3,"payment_date"=>time(),"memo"=>'收益支付宝提现打款成功'],['id'=>$id]);
                }else{//自动打款失败
                    $data_Profit['status'] =-11;
                    $data_Profit['msg'] =$retval['msg'];
                    $data_Profit['text'] = '收益支付宝提现打款失败';//支付宝
                    $data_Profit['data_id'] =$Profit_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益支付宝提现，打款失败。");
                    $MicroShop_Profit_withdraw->save(['status'=>5,"memo"=>$retval['msg']],['id'=>$id]);
                    return -9000;
                }
            }
            if($Profit_info['type']==4){//直接到账户余额
                $MicroShop_Profit_withdraw->save(['status'=>$status],['id'=>$id]);
                $data_Profit['text'] = '收益成功提现到余额,冻结收益减少,已提现收益增加';
                $res =  $this->addProfitWithdraw($data_Profit,0);//审核通过直接提现到账户余额;
            }
            if($Profit_info['type']==5){
                $params['shop_id'] = 0;
                $params['takeoutmoney'] = abs($Profit_info['cash']);
                $params['uid'] =  $Profit_info['uid'];
                $params['website_id'] = $Profit_info['website_id'];
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                $data_Profit['status'] =9;
                $data_Profit['text'] = '收益提现成功到银行卡';//微信
                $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益银行卡提现，打款成功。");
                $res =$MicroShop_Profit_withdraw->save(['status'=>3,"payment_date"=>time(),"memo"=>'收益银行卡提现打款成功'],['id'=>$id]);
            }
        }
        if($Profit_info  && $status == 3){// 平台同意打款，更新提现状态（在线打款）
            $data_Profit['data_id'] = $Profit_info['withdraw_no'];
            $data_Profit['uid'] =$Profit_info["uid"];
            $data_Profit['income_tax'] =$Profit_info["income_tax"];
            $data_Profit['tax'] =$Profit_info["tax"];
            $data_Profit['profit'] =$Profit_info["income_tax"];
            $data_Profit['cash'] =$Profit_info["cash"];
            $data_Profit['website_id'] = $Profit_info['website_id'];
            $params['shop_id'] = 0;
            $params['takeoutmoney'] = abs($Profit_info['cash']);
            $params['uid'] = $data_Profit['uid'];
            $params['website_id'] = $this->website_id;
            if($Profit_info['type']==1){//银行卡
                $bank = new VslMemberBankAccountModel();
                $bank_id = $bank->getInfo(['account_number'=>$Profit_info['account_number'],'uid'=>$Profit_info['uid']],'id')['id'];
                $tlpay_pay = new tlPay();
                $retval = $tlpay_pay->tlWithdraw($Profit_info['withdraw_no'],$Profit_info['uid'],$bank_id,abs($Profit_info['income_tax']));
                if($retval['is_success']==1){//自动打款成功
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_Profit['status'] =9;
                    $data_Profit['text'] = '收益提现成功到银行卡';//微信
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益银行卡提现，打款成功。");
                    $res =$MicroShop_Profit_withdraw->save(['status'=>3,"payment_date"=>time(),"memo"=>'收益银行卡提现打款成功'],['id'=>$id]);
                }else{//自动打款失败
                    $data_Profit['status'] =-9;
                    $data_Profit['msg'] =$retval['msg'];
                    $data_Profit['text'] = '收益银行卡提现打款失败';//微信
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益银行卡提现，打款失败。");
                    $res = $MicroShop_Profit_withdraw->save(['status'=>5,"memo"=>$retval['msg']],['id'=>$id]);
                    return -9000;
                }
            }
            if($Profit_info['type']==2){//微信
                $user_info = new UserModel();
                $wx_openid = $user_info->getInfo(['uid'=>$Profit_info['uid']],'wx_openid')['wx_openid'];
                $weixin_pay = new WeiXinPay();
                $retval = $weixin_pay->EnterprisePayment($wx_openid,$Profit_info['withdraw_no'],'',abs($Profit_info['income_tax']),'收益微信提现',$this->website_id);
                if($retval['is_success']==1){//自动打款成功
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_Profit['status'] =10;
                    $data_Profit['text'] = '收益提现成功到微信';//微信
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益微信提现，在线打款成功。");
                    $res =$MicroShop_Profit_withdraw->save(["payment_date"=>time(),"status" => 3,"memo"=>'收益微信提现，在线打款成功'],["id" => $id]);
                }else{//自动打款失败
                    $data_Profit['status'] =-10;
                    $data_Profit['msg'] =$retval['msg'];
                    $data_Profit['text'] = '收益微信提现打款失败';//微信
                    $data_Profit['data_id'] =$Profit_info['withdraw_no'];
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益微信提现，在线打款失败。");
                    $res =$MicroShop_Profit_withdraw->save(["status" => 5,"memo"=>$retval['msg']],["id" => $id]);
                    return -9000;
                }
            }
            if($Profit_info['type']==3){//支付宝
                $alipay_pay = new AliPay();
                $retval = $alipay_pay->aliPayTransferNew($Profit_info['withdraw_no'],$Profit_info['account_number'],abs($Profit_info['income_tax']));
                if($retval['is_success']==1){
                    runhook('Notify', 'withdrawalSuccessBySms', $params);
                    $data_Profit['status'] =11;
                    $data_Profit['text'] = '收益提现成功到支付宝';//支付宝
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益支付宝提现，在线打款成功。");
                    $res = $MicroShop_Profit_withdraw ->save(["payment_date"=>time(),"status" => 3,"memo"=>'收益支付宝提现，在线打款成功'],["id" => $id]);
                }else{//自动打款失败
                    $data_Profit['status'] =-11;
                    $data_Profit['msg'] =$retval['msg'];
                    $data_Profit['text'] = '收益支付宝提现打款失败';//支付宝
                    $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益支付宝提现，在线打款失败。");
                    $MicroShop_Profit_withdraw ->save(["status" => 5,"memo"=>$retval['msg']],["id" => $id]);
                    return -9000;
                }
            }
            if($Profit_info['type']==4){//余额
                $data_Profit['text'] = '收益成功提现到余额,冻结收益减少,已提现收益增加';
                $res = $this->addProfitWithdraw($data_Profit,0);
            }
            if($Profit_info['type']==5){//银行卡
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                $data_Profit['status'] =9;
                $data_Profit['text'] = '收益提现成功到银行卡';//微信
                $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益银行卡提现，打款成功。");
                $res =$MicroShop_Profit_withdraw->save(['status'=>3,"payment_date"=>time(),"memo"=>'收益银行卡提现打款成功'],['id'=>$id]);
            }
        }
        if($Profit_info  && $status == 5){// 平台同意打款，更新提现状态（线下打款）
            $data_Profit['data_id'] = $Profit_info['withdraw_no'];
            $data_Profit['uid'] =$Profit_info["uid"];
            $data_Profit['income_tax'] =$Profit_info["income_tax"];
            $data_Profit['profit'] =$Profit_info["income_tax"];
            $data_Profit['cash'] =$Profit_info["cash"];
            $data_Profit['tax'] =$Profit_info["tax"];
            $data_Profit['website_id'] = $Profit_info['website_id'];
            $params['shop_id'] = 0;
            $params['takeoutmoney'] = abs($Profit_info['cash']);
            $params['uid'] = $data_Profit['uid'];
            $params['website_id'] = $this->website_id;
            if($Profit_info['type']==1 || $Profit_info['type']==5){//银行卡
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                $data_Profit['status'] =9;
                $data_Profit['text'] = '收益提现成功到银行卡';//银行卡
                $this->addAccountWithdrawUserRecords($data_Profit, 2,$id, "收益银行卡提现，手动打款成功。");
                $res =$MicroShop_Profit_withdraw->save(["payment_date"=>time(),"status" => 3,"memo"=>'收益银行卡提现打款成功'],["id" => $id]);
            }
            if($Profit_info['type']==2){//微信
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                $data_Profit['status'] =10;
                $data_Profit['text'] = '收益提现成功到微信';//微信
                $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益微信提现，手动打款成功。");
                $res =$MicroShop_Profit_withdraw->save(["payment_date"=>time(),"status" => 3,"memo"=>'收益微信提现打款成功'],["id" => $id]);
            }
            if($Profit_info['type']==3){//支付宝
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                $data_Profit['status'] =11;
                $data_Profit['text'] = '收益提现成功到支付宝';//支付宝
                $this->addAccountWithdrawUserRecords($data_Profit, 2, $id, "收益支付宝提现，手动打款成功。");
                $res = $MicroShop_Profit_withdraw ->save(["payment_date"=>time(),"status" => 3,"memo"=>'收益支付宝提现打款成功'],["id" => $id]);
            }
            if($Profit_info['type']==4){//余额
                $data_Profit['text'] = '收益成功提现到余额,冻结收益减少,已提现收益增加';
                $res = $this->addProfitWithdraw($data_Profit,0);
            }
        }
        if($Profit_info  && $status == 4) { // 平台拒绝打款，更新提现状态
            $data_Profit['data_id'] = $Profit_info['withdraw_no'];
            $data_Profit['uid'] = $Profit_info['uid'];
            $data_Profit['website_id'] = $Profit_info['website_id'];
            $data_Profit['income_tax'] =$Profit_info["income_tax"];
            $data_Profit['profit'] =$Profit_info["income_tax"];
            $data_Profit['cash'] =$Profit_info["cash"];
            $data_Profit['tax'] =$Profit_info["tax"];
            $data_Profit['msg'] =$memo;
            if($Profit_info['type']==1 || $Profit_info['type']==5){
                $data_Profit['status'] = 23;
                $data_Profit['text'] = '提现到银行卡，平台拒绝';
                $this->addAccountWithdrawUserRecords($data_Profit,2, $id,  $data_Profit['text']);
                $res = $MicroShop_Profit_withdraw->save(['status'=>$status,"memo"=>'平台拒绝收益提现到银行卡'],['id'=>$id]);
            }
            if($Profit_info['type']==2){
                $data_Profit['status'] = 16;
                $data_Profit['text'] = '提现到微信，平台拒绝';
                $this->addAccountWithdrawUserRecords($data_Profit,2, $id,  $data_Profit['text']);
                $res = $MicroShop_Profit_withdraw->save(['status'=>$status,"memo"=>'平台拒绝收益提现到微信'],['id'=>$id]);
            }
            if($Profit_info['type']==3){
                $data_Profit['status'] = 17;
                $data_Profit['text'] = '提现到支付宝，平台拒绝';
                $this->addAccountWithdrawUserRecords($data_Profit,2, $id,  $data_Profit['text']);
                $res = $MicroShop_Profit_withdraw->save(['status'=>$status,"memo"=>'平台拒绝收益提现到支付宝'],['id'=>$id]);
            }
            if($Profit_info['type']==4){
                $data_Profit['status'] = 18;
                $data_Profit['text'] = '提现到账户余额，平台拒绝';
                $this->addAccountWithdrawUserRecords($data_Profit,2, $id,  $data_Profit['text']);
                $res = $MicroShop_Profit_withdraw->save(['status'=>$status,"memo"=>$memo],['id'=>$id]);
            }
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($Profit_info  && $status == -1) { // 平台审核不通过，更新提现状态
            $data_Profit['data_id'] = $Profit_info['withdraw_no'];
            $data_Profit['uid'] = $Profit_info['uid'];
            $data_Profit['website_id'] = $Profit_info['website_id'];
            $data_Profit['income_tax'] =$Profit_info["income_tax"];
            $data_Profit['profit'] =$Profit_info["income_tax"];
            $data_Profit['cash'] =$Profit_info["cash"];
            $data_Profit['tax'] =$Profit_info["tax"];
            $data_Profit['msg'] =$memo;
            if($Profit_info['type']==1 || $Profit_info['type']==5){
                $data_Profit['status'] =24;
                $data_Profit['text'] = '提现到银行卡，平台审核不通过';//银行卡
            }
            if($Profit_info['type']==2){
                $data_Profit['status'] =19;
                $data_Profit['text'] = '提现到微信，平台审核不通过';//微信
            }
            if($Profit_info['type']==3){
                $data_Profit['status'] = 20;
                $data_Profit['text'] = '提现到支付宝，平台审核不通过';//支付宝
            }
            if($Profit_info['type']==4){
                $data_Profit['status'] = 21;
                $data_Profit['text'] = '提现到账户余额，平台审核不通过';//账户余额
            }
            $this->addAccountWithdrawUserRecords($data_Profit,2, $id,  $data_Profit['text']);
            $res =  $MicroShop_Profit_withdraw->save(['status'=>$status,"memo"=>$memo],['id'=>$id]);
        }
        return $res;
    }
    /**
     * 平台审核提现
     */
    public function addAccountWithdrawUserRecords($data_Profit, $account_type, $type_alis_id, $remark)
    {
        if($data_Profit['status']==15){//自动审核通过余额待打款
            // 更新佣金账户情况
            $this->updateAccountWithdraw(15,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加佣金账户流水
        }
        if($data_Profit['status']==5){//自动审核通过微信待打款
            // 更新收益账户情况
            $this->updateAccountWithdraw(5,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($data_Profit['status']==8){//自动审核提现到银行卡待打款
            // 更新收益账户情况
            $this->updateAccountWithdraw(8,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($data_Profit['status']==7){//自动审核通过支付宝待打款
            // 更新收益账户情况
            $this->updateAccountWithdraw(7,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($data_Profit['status']==6){//提现到账户余额待审核
            // 更新收益账户情况
            $this->updateAccountWithdraw(6,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($data_Profit['status']==12){//银行卡提现待审核
            // 更新收益账户情况
            $this->updateAccountWithdraw(12,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($data_Profit['status']==13){//微信提现待审核
            // 更新收益账户情况
            $this->updateAccountWithdraw(13,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($data_Profit['status']==14){//支付宝提现待审核
            // 更新收益账户情况
            $this->updateAccountWithdraw(14,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($data_Profit['status']==10){//微信打款成功
            // 更新收益账户情况
            $this->updateAccountWithdraw(10,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            $acount = new ShopAccount();
            // 更新提现总额的字段
            $acount->updateAccountUserWithdraw($data_Profit['profit']);
            // 添加平台的整体资金流水
            if(abs($data_Profit['tax'])>0){
                $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现成功，个人所得税!",abs($data_Profit['tax']), 27, $type_alis_id, '收益提现到微信，个人所得税增加');
            }
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现成功!", $data_Profit['profit'], 28, $type_alis_id, $remark);
        }
        if($data_Profit['status']==-10){//微信打款失败
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($data_Profit['status']==9){//银行卡打款成功
            // 更新收益账户情况
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            $this->updateAccountWithdraw(9,$data_Profit);
            $acount = new ShopAccount();
            // 更新提现总额的字段
            $acount->updateAccountUserWithdraw($data_Profit['cash']);
            // 添加平台的整体资金流水
            $acount = new ShopAccount();
            if(abs($data_Profit['tax'])>0){
                $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现成功，个人所得税!",abs($data_Profit['tax']), 27, $type_alis_id, '收益提现到银行卡，个人所得税增加');
            }
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现成功!", $data_Profit['cash'], 37, $type_alis_id, $remark);
        }
        if($data_Profit['status']==11){//支付宝打款成功
            // 更新收益账户情况
            $this->updateAccountWithdraw(11,$data_Profit);
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            $acount = new ShopAccount();
            // 更新提现总额的字段
            $acount->updateAccountUserWithdraw($data_Profit['profit']);
            // 添加平台的整体资金流水
            if(abs($data_Profit['tax'])>0){
                $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现成功，个人所得税!",abs($data_Profit['tax']), 27, $type_alis_id, '收益提现到支付宝，个人所得税增加');
            }
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现成功!", $data_Profit['profit'], 29, $type_alis_id, $remark);
        }
        if($data_Profit['status']==-11){//支付宝打款失败
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
        }
        if($data_Profit['status']==16){//平台拒绝微信打款
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            // 更新收益账户情况
            $this->updateAccountWithdraw(-16,$data_Profit);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现拒绝!", $data_Profit['profit'], 30, $type_alis_id, $remark);
        }
        if($data_Profit['status']==17){//平台拒绝支付宝打款
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            // 更新收益账户情况
            $this->updateAccountWithdraw(-17,$data_Profit);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现拒绝!", $data_Profit['profit'], 30, $type_alis_id, $remark);
        }
        if($data_Profit['status']==23){//平台拒绝银行卡打款
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            // 更新收益账户情况
            $this->updateAccountWithdraw(-23,$data_Profit);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现拒绝!", $data_Profit['profit'], 30, $type_alis_id, $remark);
        }
        if($data_Profit['status']==19){//微信提现平台审核不通过
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            // 更新收益账户情况
            $this->updateAccountWithdraw(-19,$data_Profit);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现审核不通过!", $data_Profit['profit'], 31, $type_alis_id, $remark);
        }
        if($data_Profit['status']==20){//支付宝提现平台审核不通过
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            // 更新收益账户情况
            $this->updateAccountWithdraw(-20,$data_Profit);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现审核不通过!", $data_Profit['profit'], 31, $type_alis_id, $remark);
        }
        if($data_Profit['status']==24){//银行卡提现平台审核不通过
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            // 更新收益账户情况
            $this->updateAccountWithdraw(-24,$data_Profit);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现审核不通过!", $data_Profit['profit'], 31, $type_alis_id, $remark);
        }
        if($data_Profit['status']==21){//提现到余额审核不通过
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            // 更新收益账户情况
            $this->updateAccountWithdraw(-21,$data_Profit);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现审核不通过!", $data_Profit['profit'], 31, $type_alis_id, $remark);
        }
        if($data_Profit['status']==18){//提现到余额拒绝打款
            $this->addProfitMicroShop($data_Profit);//添加收益账户流水
            // 更新收益账户情况
            $this->updateAccountWithdraw(-18,$data_Profit);
            $acount = new ShopAccount();
            // 添加平台的整体资金流水
            $acount->addAccountRecords(0, $data_Profit['uid'], "收益提现到余额拒绝!", $data_Profit['profit'], 30, $type_alis_id, $remark);
        }
    }
    /**
     * 平台审核提现，更新收益账户
     */
    public function updateAccountWithdraw($status,$data_Profit){
        $Profit_account = new VslMicroShopAccountModel();
        $Profit_account_info = $Profit_account->getInfo(['uid'=>$data_Profit['uid']],'*');
        try{
            if($status==5 || $status==6 || $status==7 || $status==8 || $status==12 || $status==13 || $status==14 || $status==15){//微信支付宝提现手动审核和自动审核
                $data3 = array(
                    'profit'=>$Profit_account_info['profit']-abs($data_Profit['profit'])-abs($data_Profit['tax']),//可提现收益减少
                    'freezing_profit'=>$Profit_account_info['freezing_profit']+abs($data_Profit['profit'])+abs($data_Profit['tax'])//冻结收益增加
                );
            }
            if($status==9 || $status==10 || $status==11){//微信支付宝提现成功
                $data3 = array(
                    'withdrawals'=>$Profit_account_info['withdrawals']+abs($data_Profit['cash'])+abs($data_Profit['tax']),//已提现收益增加
                    'freezing_profit'=>$Profit_account_info['freezing_profit']-abs($data_Profit['profit'])-abs($data_Profit['tax']),//冻结收益减少
                    'tax'=>$Profit_account_info['tax']+abs($data_Profit['tax'])
                );
            }
            if($status==-10 || $status==-11 || $status==-16 || $status==-17 || $status==-19 || $status==-20 || $status==-21 || $status==-18 || $status==-24 || $status==-23){//微信支付宝提现失败或者拒绝打款审核不通过
                $data3 = array(
                    'profit'=>$Profit_account_info['profit']+abs($data_Profit['profit'])+abs($data_Profit['tax']),//可提现收益增加
                    'freezing_profit'=>$Profit_account_info['freezing_profit']-abs($data_Profit['profit'])-abs($data_Profit['tax'])//冻结收益减少
                );
            }
            $Profit_account->save($data3,['uid'=>$data_Profit['uid']]);//更新收益账户
            $Profit_account->commit();
            return 1;
        }catch (\Exception $e)
        {
            $Profit_account->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 后台收益流水列表
     */
    public function getAccountList($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $Profit_account = new VslMicroShopAccountRecordsViewModel();
        $list = $Profit_account->getViewList($page_index, $page_size, $condition, 'nmar.create_time desc');
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
                }else{
                    if($v['from_type']==1){
                        $list['data'][$k]['type_name'] = '订单完成';
                        $list['data'][$k]['profit'] = '+'.$list['data'][$k]['profit'];
                    }
                    if($v['from_type']==2){
                        $list['data'][$k]['type_name'] = '订单退款完成';
                        $list['data'][$k]['profit'] = '-'.$list['data'][$k]['profit'];
                    }
                    if($v['from_type']==3){
                        $list['data'][$k]['type_name'] = '订单支付完成';
                        $list['data'][$k]['profit'] = '+'.$list['data'][$k]['profit'];
                    }
                    if($v['from_type']==22){
                        $list['data'][$k]['type_name'] = '下级开店返利';
                        $list['data'][$k]['profit'] = '+'.$list['data'][$k]['profit'];
                    }
                }
                if(empty($list['data'][$k]['user_name'])){
                    $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
                $list['data'][$k]['user_info'] = ($v['nick_name'])?$v['nick_name']:($v['user_name']?$v['user_name']:($v['user_tel']?$v['user_tel']:$v['uid']));
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            }
        }
        return $list;
    }
    /**
     * 前台收益流水列表
     */
    public function getAccountLists($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $Profit_account = new VslMicroShopAccountRecordsViewModel();
        $list = $Profit_account->getViewList($page_index, $page_size, $condition, 'nmar.create_time desc');
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
                    $list['data'][$k]['type'] = 1;
                    $list['data'][$k]['type_name'] = $status_name;
                    $list['data'][$k]['change_money'] = (-1)*(abs($list['data'][$k]['profit'])+abs($list['data'][$k]['tax']));
                }else{
                    $list['data'][$k]['type'] = 0;
                    if($v['from_type']==1){
                        $list['data'][$k]['type_name'] = '订单完成';
                        $list['data'][$k]['profit'] = '+'.$list['data'][$k]['profit'];
                    }
                    if($v['from_type']==2){
                        $list['data'][$k]['type_name'] = '订单退款完成';
                        $list['data'][$k]['profit'] = '-'.$list['data'][$k]['profit'];
                    }
                    if($v['from_type']==3){
                        $list['data'][$k]['type_name'] = '订单支付完成';
                        $list['data'][$k]['profit'] = '+'.$list['data'][$k]['profit'];
                    }
                    if($v['from_type']==22){
                        $list['data'][$k]['type_name'] = '下级开店返利';
                        $list['data'][$k]['profit'] = '+'.$list['data'][$k]['profit'];
                    }
                    $list['data'][$k]['change_money'] = $list['data'][$k]['profit'];
                }
                if(empty($list['data'][$k]['user_name'])){
                    $list['data'][$k]['user_name'] = $list['data'][$k]['nick_name'];
                }
                $list['data'][$k]['user_info'] = ($v['nick_name'])?$v['nick_name']:($v['user_name']?$v['user_name']:($v['user_tel']?$v['user_tel']:$v['uid']));
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            }
        }
        return $list;
    }
    /**
     * 收益提现列表
     */
    public function getProfitWithdrawList($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $Profit_withdraw = new VslMicroShopProfitWithdrawModel();
        $list = $Profit_withdraw->getViewList($page_index, $page_size, $condition, 'nmar.ask_for_date desc');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
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
    public function getWithdrawalCount($condition)
    {
        $Profit_withdraw = new VslMicroShopProfitWithdrawModel();
        $user_sum = $Profit_withdraw->getCount($condition);
        if ($user_sum) {
            return $user_sum;
        } else {
            return 0;
        }
    }
    /**
     * 收益提现详情
     */
    public function profitWithdrawDetail($id)
    {
        $Profit_withdraw = new VslMicroShopProfitWithdrawModel();
        $info = $Profit_withdraw->getInfo(['id'=>$id],'*');
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
        }
        return $info;
    }
}
