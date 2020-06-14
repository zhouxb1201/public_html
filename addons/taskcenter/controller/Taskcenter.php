<?php
namespace addons\taskcenter\controller;

use addons\taskcenter\model\VslGeneralPosterListModel;
use addons\taskcenter\model\VslGeneralPosterModel;
use addons\taskcenter\model\VslGeneralTaskModel;
use addons\taskcenter\model\VslPosterRewardListModel;
use addons\taskcenter\model\VslPosterRewardModel;
use data\model\AddonsConfigModel;
use addons\taskcenter\Taskcenter AS baseTaskcenter;
use data\model\UserTaskModel;
use data\model\WeixinFansModel;
use data\service\Goods;
use \addons\taskcenter\service\Taskcenter AS taskcenterServer;
use data\service\Goods as GoodsService;
use data\service\Order\Order;
use think\Db;

class Taskcenter extends baseTaskcenter
{
    public function __construct()
    {
        parent::__construct();
        $this->taskcenterServer = new taskcenterServer();
    }
    /**
     * 任务中心商品选择
     */
    public function generalTaskDialogGoodsList()
    {
        if (request()->post('page_index')) {
            $index = request()->post('page_index', 1);
            $goods_type = request()->post('goods_type', 1);
            $search_text = request()->post('search_text');
            if ($search_text) {
                $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['ng.website_id'] = $this->website_id;
            $condition['ng.shop_id'] = $this->instance_id;
            $condition['ng.state'] = 1;
            //0自营店 1全平台
            if ($goods_type == '0') {
                $condition['ng.shop_id'] = $this->instance_id;
            }
            $goods = new Goods();
            $list = $goods->getModalGoodsList($index, $condition);
            return $list;
        }
        $this->fetch('template/' . $this->module . '/generalTaskGoodsDialog');
    }
    /*
     * 添加普通任务
     * **/
    public function addGeneralTask()
    {
//        p(request()->param());exit;
        //任务名称
        $data['task_name'] = request()->post('task_name', '');
        $data['task_type'] = request()->post('task_type', '');
        $data['task_img'] = request()->post('task_img', 0);
        $start_task_time = request()->post('start_task_time', '');
        if ($start_task_time) {
            $start_task_time = $start_task_time . ' 23:59:59';
        }
        $data['start_task_time'] = strtotime($start_task_time);
        $end_task_time = request()->post('end_task_time', '');
        $data['end_task_time'] = strtotime($end_task_time);
        $data['task_limit_time'] = request()->post('task_limit_time', 0);
//        $data['task_status'] = request()->post('task_status', 0);
        $data['task_status'] = 1;
        /*********************任务规则****************************/
        //推荐人数
        $data['referrals'] = request()->post('referrals', 0);
        //分销佣金
        $data['distribution_commission'] = request()->post('distribution_commission', 0);
        //分销订单数
        $data['distribution_orders'] = request()->post('distribution_orders', 0);
        $data['order_total_money'] = request()->post('order_total_money', 0);
        $data['order_total_sum'] = request()->post('order_total_sum', 0);
        //支付订单数
        $data['pay_order_total_num'] = request()->post('pay_order_total_num', 0);
        $data['goods_id'] = request()->post('goods_id', 0);
        $data['goods_comment_num'] = request()->post('goods_comment_num', 0);
        $data['total_recharge'] = request()->post('total_recharge', 0);
        $data['single_recharge'] = request()->post('single_recharge', 0);
        //任务说明
        $data['task_explain'] = request()->post('task_explain', '');
        /*************************送奖励************************************/
        $data['send_points'] = request()->post('send_points', 0);
        $data['send_balance'] = request()->post('send_balance', 0);
        $data['send_wchat_packet'] = request()->post('send_wchat_packet', 0);
        $data['send_member_growth'] = request()->post('send_member_growth', 0);
        $data['gift_voucher_id'] = request()->post('gift_voucher_id', 0);
        $data['coupon_type_id'] = request()->post('coupon_type_id', 0);
        //添加时间
        $data['create_time'] = time();
        $data['website_id'] = $this->website_id;
        $data['shop_id'] = $this->instance_id;
        $general_task_id = request()->post('general_task_id', 0);
        if (!$general_task_id) {
            //插入活动表
            $general_task_id = $this->taskcenterServer->addGeneralTask($data);
        } else {
            //编辑活动表
            $general_task_id = $this->taskcenterServer->editGeneralTask($data, $general_task_id);
        }

        if ($general_task_id) {
            $this->addUserLog('添加/编辑普通任务', $general_task_id);
            return ajaxReturn($general_task_id);
        }
    }

    /**
     * 推荐列表
     */
    public function recordList()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $start_date = input('post.start_date');
        $end_date = input('post.end_date');
        $condition = [
            'pr.shop_id' => $this->instance_id,
            'pr.website_id' => $this->website_id,
            'pr.poster_id' => input('post.poster_id'),
            'pr.poster_type' => 2,
        ];
        $condition['reco'] = input('post.reco');
        $condition['be_reco'] = input('post.be_reco');
        if ($start_date) {
            $condition['scan_time'][] = ['GT', strtotime($start_date)];
        }
        if ($end_date) {
            $condition['scan_time'][] = ['LT', strtotime($end_date)];
        }

        $list = $this->taskcenterServer->recordList($page_index, $page_size, $condition);

        return $list;
    }

    /*
     * 添加任务
     * **/
    public function addPosterTask()
    {
//        p(request()->post());exit;
        Db::startTrans();
        try{
            $poster_reward_mdl = new VslPosterRewardModel();
            $poster_reward_list_mdl = new VslPosterRewardListModel();
            $post_data = request()->post();
            $poster_data = $post_data;
            $post_data['task_explain'] = htmlspecialchars_decode($poster_data['task_explain']);
            $post_data['task_status'] = 1;
            if($post_data['task_type']== 0){//普通任务
                unset($post_data['task_rule']);
                unset($post_data['general_reward']);
            }else{
                unset($post_data['referral_reward']);
            }

            //添加时间
            $post_data['poster_design'] = json_encode($post_data['poster_design']);
            $post_data['start_task_time'] = strtotime($post_data['start_task_time']);
//            var_dump(date('Y-m-d', strtotime($post_data['start_task_time'])).' 23:59:59');exit;
            $post_data['end_task_time'] = strtotime(date('Y-m-d', strtotime($post_data['end_task_time'])).' 23:59:59');
            if($post_data['end_task_time'] < strtotime(date('Y-m-d'))){
                return ['code'=>-1, 'message'=>'任务结束时间不能小于当前时间'];
            }
            $post_data['website_id'] = $this->website_id;
            $post_data['shop_id'] = $this->instance_id;
            $general_poster_id = $post_data['general_poster_id'];
            $task_type = $poster_data['task_type']?:1;
            $referral_reward = $poster_data['referral_reward'];
//            p($referral_reward);exit;
            if (!$general_poster_id) {
                //插入活动表
                $post_data['create_time'] = time();
//                p($post_data);exit;
                $general_poster_id = $this->taskcenterServer->addGeneralPoster($post_data);
//                var_dump($general_poster_id);exit;
                if($general_poster_id){
                    if($post_data['task_type']== 0){
                        $task_rule_str = json_encode($poster_data['task_rule']);
                        $reward_data[0] = $poster_data['general_reward'];
                        $reward_data[0]['task_rule'] = $task_rule_str;
                        $reward_data[0]['general_poster_id'] = $general_poster_id;
                        $reward_data[0]['reward_obj'] = 0;
                    }else{
                        $reward_data = $this->getRewards($referral_reward, $general_poster_id, $task_type);
                    }
                }
//                p($reward_data);exit;
                $poster_reward_mdl->saveAll($reward_data);
                $poster_reward_list_mdl->saveAll($reward_data);//记录防止删除任务，任务信息丢失的情况
//                echo $poster_reward_mdl->getLastSql();
//                var_dump($id);exit;
            } else {
                //编辑活动表
                $bool = $this->taskcenterServer->editGeneralPoster($post_data);
                if($bool){
                    //先删除之前保存的奖励以及规则
                    $delete_bool = $poster_reward_mdl->where(['general_poster_id'=>$general_poster_id])->delete();
                    $delete_bool2 = $poster_reward_list_mdl->where(['general_poster_id'=>$general_poster_id])->delete();
//                    var_dump($delete_bool, $poster_reward_mdl->getLastSql());exit;
                    if($delete_bool && $delete_bool2){
                        if($post_data['task_type']== 0){
                            $task_rule_str = json_encode($poster_data['task_rule']);
                            $reward_data[0] = $poster_data['general_reward'];
                            $reward_data[0]['general_poster_id'] = $general_poster_id;
                            $reward_data[0]['task_rule'] = $task_rule_str;
                            $reward_data[0]['reward_obj'] = 0;
                        }else{
                            $reward_data = $this->getRewards($referral_reward, $general_poster_id, $task_type);
                        }
                        $poster_reward_mdl->saveAll($reward_data);
                        $poster_reward_list_mdl->saveAll($reward_data);//记录防止删除任务，任务信息丢失的情况
                    }
                }
            }

            if ($general_poster_id) {
                Db::commit();
                $this->addUserLog('添加/编辑海报任务', $general_poster_id);
                return ajaxReturn($general_poster_id);
            }
        }catch(\Exception $e){
            echo $e->getMessage();exit;
            Db::rollback();
        }

    }
    /*
     * 获取奖励数据
     * **/
    public function getRewards($referral_reward, $general_poster_id, $task_type)
    {
        //推荐人
        foreach($referral_reward['referral_reward'] as $k=>$v_referral_reward){
            //海报任务id
            $reward_data[$k]['general_poster_id'] = $general_poster_id;
            //要求 推荐人数
            $reward_data[$k]['referral_num'] = $v_referral_reward['referral_num'];
            //送积分
            $reward_data[$k]['point'] = $v_referral_reward['point'];
            //送余额
            $reward_data[$k]['balance'] = $v_referral_reward['balance'];
            //送微信红包
            $reward_data[$k]['wchat_red_packet'] = $v_referral_reward['wchat_red_packet'];
            //送会员成长值
            $reward_data[$k]['growth'] = $v_referral_reward['growth'];
            //送礼品券
            $reward_data[$k]['gift_voucher_id'] = $v_referral_reward['gift_voucher_id'];
            //送优惠券
            $reward_data[$k]['coupon_type_id'] = $v_referral_reward['coupon_type_id'];
            //推荐人
            $reward_data[$k]['reward_obj'] = 1;
            //海报类型
            $reward_data[$k]['task_type'] = $v_referral_reward['task_type']?:1;
        }
        //被推荐人
        $be_reward_k = count($reward_data);
        $reward_data[$be_reward_k]['general_poster_id'] = $general_poster_id;
        $reward_data[$be_reward_k]['point'] = $referral_reward['be_reco']['send_points'];
        $reward_data[$be_reward_k]['balance'] = $referral_reward['be_reco']['send_balance'];
        $reward_data[$be_reward_k]['wchat_red_packet'] = $referral_reward['be_reco']['send_wchat_packet'];
        $reward_data[$be_reward_k]['growth'] = $referral_reward['be_reco']['send_member_growth'];
        $reward_data[$be_reward_k]['gift_voucher_id'] = $referral_reward['be_reco']['gift_voucher_id'];
        $reward_data[$be_reward_k]['coupon_type_id'] = $referral_reward['be_reco']['coupon_type_id'];
        $reward_data[$be_reward_k]['reward_obj'] = 2;
        $reward_data[$be_reward_k]['task_type'] = $task_type;
        $reward_data[$be_reward_k]['referral_num'] = 0;
        return $reward_data;
    }
    /*
     * 获取普通任务列表
     * **/
    public function GeneralTaskList()
    {
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $search_goods_text = request()->post('search_text','');
        if($search_goods_text){
            $condition['task_name'] = ['like', '%'.$search_goods_text.'%'];
        }
        $condition['website_id'] = $this->website_id;
        $condition['task_type'] = 0;
        $order = 'general_poster_id desc';
        $general_task_list = $this->taskcenterServer->getGeneralTaskList($page_index,$page_size,$condition,$order);
        return $general_task_list;
    }
    /*
     * 关闭设置的某个任务
     * **/
    public function closeGeneralPoster()
    {
        $general_poster_id = request()->post('general_poster_id');
        $general_poster_mdl = new VslGeneralPosterModel();
        $change_data['task_status'] = 0;
        $bool = $general_poster_mdl->save($change_data, ['general_poster_id' => $general_poster_id]);
        if ($bool) {
            $this->addUserLog('关闭普通任务', $general_poster_id);
            return ajaxReturn($bool);
        }
    }

    /*
     * 删除设置的某个任务
     * **/
    public function deleteGeneralPoster()
    {
        $general_poster_id = request()->post('general_poster_id');
        $general_poster_mdl = new VslGeneralPosterModel();
        $general_poster_list_mdl = new VslGeneralPosterListModel();
        $poster_reward_mdl = new VslPosterRewardModel();
//        $user_task_mdl = new UserTaskModel();
        $bool = $general_poster_mdl->where(['general_poster_id' => $general_poster_id])->delete();
        if($bool){
            $bool2 = $poster_reward_mdl->where(['general_poster_id' => $general_poster_id])->delete();
            $general_poster_list_mdl->where(['general_poster_id' => $general_poster_id] )->update(['is_delete' => 1]);
//            if($bool2){
//                //删除该任务下用户领取的任务记录
//                $user_task_mdl->where(['general_poster_id' => $general_poster_id, 'website_id' => $this->website_id])->delete();
//            }
        }
        if ($bool2) {
            $this->addUserLog('删除普通任务', $general_poster_id);
            return ajaxReturn($bool2);
        }
    }
    /*
     * 获取海报任务内容
     * **/
    public function posterTaskList()
    {
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $search_goods_text = request()->post('search_text','');
        if($search_goods_text){
            $condition['task_name'] = ['like', '%'.$search_goods_text.'%'];
        }
        $condition['website_id'] = $this->website_id;
        $condition['task_type'] = ['neq',0];
        $order = 'general_poster_id desc';
        $general_task_list = $this->taskcenterServer->getPosterTaskList($page_index,$page_size,$condition,$order);
        return $general_task_list;
    }
    /*
     * 通过海报任务id获取海报任务内容
     * **/
    public function getGeneralPosterInfo()
    {
        $general_poster_id = request()->post('general_poster_id',0);
        $general_poster_mdl = new VslGeneralPosterModel();
//        $general_poster_info = $general_poster_mdl->alias('gp')->join('vsl_poster_reward pr','gp.general_poster_id = pr.general_poster_id','LEFT')->where(['gp.general_poster_id'=>$general_poster_id])->select();
        $general_poster_info = $general_poster_mdl::get(['general_poster_id'=>$general_poster_id],['poster_reward','get_task_img','get_push_cover']);
//        echo $general_poster_mdl->getLastSql();exit;
        $general_poster_info['start_task_time'] = date('Y-m-d',$general_poster_info['start_task_time']);
        $general_poster_info['end_task_time'] = date('Y-m-d',$general_poster_info['end_task_time']);
        //图片
        $general_poster_info['get_task_img']['pic_cover'] = getApiSrc($general_poster_info['get_task_img']['pic_cover']);
        $general_poster_info['get_push_cover']['pic_cover'] = getApiSrc($general_poster_info['get_push_cover']['pic_cover']);
        return $general_poster_info;
//        p($general_poster_info);exit;
    }
    /*
     * 获取任务中心列表
     * **/
    public function getTaskList()
    {
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $task_kind = request()->post('task_kind',1);
        $condition = [];
        $condition['task_kind'] = $task_kind;
        //不是过期的任务
        $condition['end_task_time'] = ['>=',time()];
        $condition['start_task_time'] = ['<=',time()];
        $condition['gp.task_type'] = [['neq',1], ['neq',2]];
        $condition['gp.website_id'] = $this->website_id;
        $order = 'gp.general_poster_id desc';
        $general_task_list = $this->taskcenterServer->getWapPosterTaskList($page_index,$page_size,$condition,$order);
        return $general_task_list;
    }
    /*
     * 获取任务详情
     * **/
    public function getTaskDetail()
    {
        $general_poster_id = request()->post('general_poster_id', 0);
        $user_task_id = request()->post('user_task_id', 0);
        $condition['gp.general_poster_id'] = $general_poster_id;
        if($user_task_id){
            $condition['user_task_id'] = $user_task_id;
        }
        $general_task_Detail = $this->taskcenterServer->getWapPosterTaskDetail($condition);
        return [
            'code' => 0,
            'data'=>[
                'general_task_detail'=>$general_task_Detail
            ]
        ];
    }
    /*
     * 获取我的任务列表
     * **/
    public function getMyTaskList()
    {
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size')?:PAGESIZE;
        $task_status = request()->post('task_status')?:1; //1-进行中 2已完成 3-已失效
//        $condition['end_task_time'] = ['>',time()];
//        $condition['start_task_time'] = ['<=',time()]; //因为失效的领取任务也存在过期，所以去掉这个时间期限，不然会导致有的过期任务之前领取了不见得可能。

        $condition['task_status'] = 1;
        $condition['pr.reward_obj'] = ['neq', 2];
        $condition['ut.uid'] = $this->uid;
        $order = 'pr.referral_num ASC, ut.get_time DESC';
        $general_task_list = $this->taskcenterServer->getMyPosterTaskList($page_index, $page_size, $condition, $order, $task_status);
        return $general_task_list;
    }

    /*
     * 领取普通任务
     * **/
    public function getMyTask()
    {
        $uid = $this->uid;
        if (!$uid) {
            return ['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录'];
        }
        $general_poster_id = request()->post('general_poster_id',0);
//        var_dump($general_poster_id);
        if($general_poster_id){
            $general_poster_mdl = new VslGeneralPosterModel();
            $user_task_mdl = new UserTaskModel();
            $condition0['general_poster_id'] = $general_poster_id;
            $condition0['start_task_time'] = ['<=', time()];
            $condition0['end_task_time'] = ['>=', time()];
            $general_poster_info = $general_poster_mdl->getInfo($condition0,'*');
//            var_dump($general_poster_info);
            if($general_poster_info){//任务没有过期
                //先判断是否领取过任务
                $condition1['uid'] = $uid;
                $condition1['general_poster_id'] = $general_poster_id;
                $general_poster_user_info1 = $user_task_mdl->getInfo($condition1, '*');
//                var_dump($general_poster_user_info1);
                if($general_poster_user_info1){//如果领取过该任务
                    //判断是否可以重复领取任务 普通周期任务 || 海报任务设置了可重复领取
                    if ($general_poster_info['is_repeat_take_task'] || $general_poster_info['task_kind'] == 2) {
                        $condition2['uid'] = $uid;
                        $condition2['general_poster_id'] = $general_poster_id;
                        $condition2['is_complete'] = 0;
                        $condition2['get_time'] = ['<=', time()];
                        $condition2['need_complete_time'] = ['>=', time()];
                        $general_poster_user_info2 = $user_task_mdl->getInfo($condition2, '*');
                        if(!$general_poster_user_info2){//能够领取的条件 不存在未完成的该任务了
                            $data['uid'] = $uid;
                            $data['general_poster_id'] = $general_poster_id;
                            $data['get_time'] = time();
                            $task_limit_time = $general_poster_info['task_limit_time'];
                            if($task_limit_time == 0){
                                $data['need_complete_time'] = $general_poster_info['end_task_time'];
                            }else{
                                $data['need_complete_time'] = ($data['get_time'] + $task_limit_time*3600) > $general_poster_info['end_task_time'] ? $general_poster_info['end_task_time'] : ($data['get_time'] + $task_limit_time*3600);
                            }
                            $data['website_id'] = $this->website_id;
                            $data['create_time'] = $data['get_time'];
                            $bool = $user_task_mdl->save($data);
                        }else{
                            return json(['code'=>-1, 'message'=>'您已经领取过该任务了！']);
                        }
                    }else{
                        return json(['code'=>-1, 'message'=>'您已经领取过该任务了！']);
                    }
                }else{
                    $data['uid'] = $uid;
                    $data['general_poster_id'] = $general_poster_id;
                    $data['get_time'] = time();
                    $task_limit_time = $general_poster_info['task_limit_time'];
                    if($task_limit_time == 0){
                        $data['need_complete_time'] = $general_poster_info['end_task_time'];
                    }else{
                        $data['need_complete_time'] = ($data['get_time'] + $task_limit_time*3600) > $general_poster_info['end_task_time'] ? $general_poster_info['end_task_time'] : ($data['get_time'] + $task_limit_time*3600);
                    }
                    $data['website_id'] = $this->website_id;
                    $data['create_time'] = $data['get_time'];
                    $bool = $user_task_mdl->save($data);
                }
            }
            if($bool){
                return ['code'=>0, 'message'=>'领取成功！'];
            }
        }
    }
    public function test(){
        //通过uid获取场景和海报id
        /*$weixin_fans_mdl = new WeixinFansModel();
        $openid = 'ov9Tx0uS87loCpxMYjK0NQw4FE5E1';
        $uid = 168;
        $buid = 144;
        $poster_info = $weixin_fans_mdl->getInfo(['openid'=>$openid, 'website_id'=>26],'scene,scene_id');
//        $general_poster_condition['poster_type'] = $poster_info['scene'];
        if($poster_info['scene'] == 'task_poster'){
            $general_poster_condition['general_poster_id'] = $poster_info['scene_id'];
            $this->taskcenterServer->successRecommend($general_poster_condition, $uid, $buid);
        }*/
//        $this->taskcenterServer->getPosterTask(9,266);
        $task_poster_service = new \addons\taskcenter\service\Taskcenter();
        $task_poster_service->successRecommend(['general_poster_id' => 10], 609, 790);


    }
}