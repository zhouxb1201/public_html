<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/26 0026
 * Time: 14:41
 */

namespace addons\taskcenter\service;

use addons\coupontype\model\VslCouponTypeModel;
use addons\coupontype\server\Coupon;
use addons\distribution\model\VslDistributorAccountModel;
use addons\distribution\model\VslDistributorAccountRecordsModel;
use addons\distribution\model\VslDistributorLevelModel;
use addons\distribution\model\VslOrderDistributorCommissionModel;
use addons\distribution\service\Distributor;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\giftvoucher\server\GiftVoucher;
use addons\poster\model\PosterAwardModel;
use addons\poster\model\PosterRecordModel;
use addons\taskcenter\model\AppCustomTemplate;
use addons\taskcenter\model\AppVersion;
use addons\taskcenter\model\VslGeneralPosterListModel;
use addons\taskcenter\model\VslGeneralPosterModel;
use addons\taskcenter\model\VslGeneralTaskModel;
use addons\taskcenter\model\VslPosterRecordModel;
use addons\taskcenter\model\VslPosterRewardModel;
use addons\taskcenter\model\VslTaskcenterDetailModel;
use addons\taskcenter\model\VslTaskcenterModel;
use addons\taskcenter\model\VslTaskcenterRecordModel;
use addons\shop\service\Shop as ShopService;
use data\extend\weixin\WxPayApi;
use data\model\AddonsConfigModel;
use data\model\RedPackFailRecordModel;
use data\model\UserModel;
use data\model\UserTaskModel;
use data\model\VslGoodsEvaluateModel;
use data\model\VslGoodsModel;
use data\model\AppadsetModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslMemberModel;
use data\model\VslMemberRechargeModel;
use data\model\VslOrderModel;
use data\model\VslPushMessage;
use data\model\VslGoodsViewModel;
use data\service\BaseService;
use data\service\Member\MemberAccount;
use data\service\User;
use data\service\Weixin;
use phpDocumentor\Reflection\Types\Object;
use think\db;

class Taskcenter extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
//        $this->taskcenter_mdl = new VslTaskcenterModel();
    }

    /*
     * 添加普通任务活动
     * **/
    public function addGeneralTask($data)
    {
        $general_task_mdl = new VslGeneralTaskModel();
        $general_task_id = $general_task_mdl->save($data);
        return $general_task_id;
    }

    /*
     * 添加普通任务活动
     * **/
    public function editGeneralTask($data, $general_task_id)
    {
        $general_task_mdl = new VslGeneralTaskModel();
        $bool = $general_task_mdl->save($data, ['general_task_id' => $general_task_id]);
        return $bool;
    }

    /*
     * 普通任务列表
     * **/
    public function getGeneralTaskList($page_index = 1, $page_size, $condition, $order)
    {
        //偏移量
        $offset = ($page_index - 1) * $page_size;
        $general_poster_mdl = new VslGeneralPosterModel();
        $count = $general_poster_mdl->where($condition)->count();
        //一共多少页
        $page_count = ceil($count / $page_size);
        $general_poster_list = $general_poster_mdl->limit($offset, $page_size)->where($condition)->order($order)->select();
        foreach ($general_poster_list as $k => $v) {
            if($v['task_status'] == 1){
                //判断当前时间 当前时间 >= 活动开始时间   当前时间 < 活动结束时间
                if(time() >= $v['start_task_time'] && time() < $v['end_task_time']){//正在进行中
                    $general_poster_list[$k]['time_status'] = 1;
                }elseif(time() < $v['start_task_time']){//未进行
                    $general_poster_list[$k]['time_status'] = 2;
                }elseif(time() >= $v['end_task_time']){//已关闭
                    $general_poster_list[$k]['time_status'] = 3;
                }
            }else{
                $general_poster_list[$k]['time_status'] = 3;//已关闭
            }

            //修改时间格式
            $general_poster_list[$k]['start_task_date'] = date('Y-m-d H:i:s', $v['start_task_time']);
            $general_poster_list[$k]['end_task_date'] = date('Y-m-d H:i:s', $v['end_task_time']);
        }
//        p($general_task_list);exit;
        return ['code' => 0,
            'data' => $general_poster_list,
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }

    /*
     * 海报任务列表
     * **/
    public function getPosterTaskList($page_index = 1, $page_size, $condition, $order)
    {
        //偏移量
        $offset = ($page_index - 1) * $page_size;
        $general_poster_mdl = new VslGeneralPosterModel();
        $count = $general_poster_mdl->where($condition)->count();
        //一共多少页
        $page_count = ceil($count / $page_size);
        $general_poster_list = $general_poster_mdl->limit($offset, $page_size)->where($condition)->order($order)->select();
        foreach ($general_poster_list as $k => $v) {
            if($v['task_status'] == 1){
                //判断当前时间 当前时间 >= 活动开始时间   当前时间 < 活动结束时间
                if(time() >= $v['start_task_time'] && time() < $v['end_task_time']){//正在进行中
                    $general_poster_list[$k]['time_status'] = 1;
                }elseif(time() < $v['start_task_time']){//未进行
                    $general_poster_list[$k]['time_status'] = 2;
                }elseif(time() >= $v['end_task_time']){//已关闭
                    $general_poster_list[$k]['time_status'] = 3;
                }
            }else{
                $general_poster_list[$k]['time_status'] = 3;//已关闭
            }
            //修改时间格式
            $general_poster_list[$k]['start_task_date'] = date('Y-m-d H:i:s', $v['start_task_time']);
            $general_poster_list[$k]['end_task_date'] = date('Y-m-d H:i:s', $v['end_task_time']);
        }
//        p($general_poster_list);exit;
        return ['code' => 0,
            'data' => $general_poster_list,
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }

    /*
     * 前台-海报任务列表
     * **/
    public function getWapPosterTaskList($page_index = 1, $page_size, $condition, $order)
    {
        $uid = $this->uid;
        if (!$uid) {
            return ['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录'];
        }
        //偏移量
        $offset = ($page_index - 1) * $page_size;
        $general_poster_mdl = new VslGeneralPosterModel();
        $count = $general_poster_mdl->alias('gp')
            ->join('vsl_poster_reward pr', 'gp.general_poster_id = pr.general_poster_id', 'LEFT')
            ->join('vsl_user_task ut', 'gp.general_poster_id = ut.general_poster_id', 'LEFT')
            ->where($condition)
            ->count();
        //一共多少页
        $page_count = ceil($count / $page_size);
        $general_poster_list = $general_poster_mdl->alias('gp')
            ->field('gp.*, pr.*, ut.*, gp.general_poster_id')
            ->join('vsl_poster_reward pr', 'gp.general_poster_id = pr.general_poster_id', 'LEFT')
            ->join('vsl_user_task ut', 'gp.general_poster_id = ut.general_poster_id', 'LEFT')
            ->limit($offset, $page_size)
            ->where($condition)
            ->order($order)
            ->select();
//        echo $general_poster_mdl->getLastSql();
        $new_arr = [];
        foreach ($general_poster_list as $k => $v) {
            $user_task_cond = [];
            //判断任务是否可以领取
            $general_poster_id = $v['general_poster_id'];
            $user_task_mdl = new UserTaskModel();
            //time()>=get_time time()< need_complete_time
            if ($v['task_kind'] == 1) {//单次任务
                $user_task_cond['general_poster_id'] = $general_poster_id;
                $user_task_cond['uid'] = $uid;
                $user_task_list = $user_task_mdl->getInfo($user_task_cond, '*');
                //单次任务只有领取过了，就不能领取了
                if ($user_task_list) {
                    if ($user_task_list['is_complete'] == 0) {
                        $new_arr[$v['general_poster_id']]['task_info_status'] = 1;//说明任务已领取未完成 不能再领取
                    } elseif ($user_task_list['is_complete'] == 1) {
                        $new_arr[$v['general_poster_id']]['task_info_status'] = 2;//说明任务已领取已完成 不能再领取
                    }
                } else {
                    if($v['task_type'] == 1 || $v['task_type'] == 2){//海报任务不能普通领取
//                        $new_arr[$v['general_poster_id']]['task_info_status'] = 2;//海报任务 不能再领取
                    }else{
                        $new_arr[$v['general_poster_id']]['task_info_status'] = 0;//说明任务未领取 可以领取
                    }
                }

            } elseif ($v['task_kind'] == 2) {
                //1、过期了，可以领取2、当前时间小于 需要完成时间并且已经完成了 既可以领取
                //取出该任务领取的最后一条 order by get_time desc
                $user_task_cond['general_poster_id'] = $general_poster_id;
                $user_task_cond['uid'] = $uid;
                $user_task_list = $user_task_mdl->where($user_task_cond)->order('get_time desc')->find();
                if($user_task_list) {
                    $need_complete_time = $user_task_list['need_complete_time'];
                    $now_time = time();
                    $complete_status = $user_task_list['is_complete'];
                    if (($now_time < $need_complete_time && $complete_status == 1) || ($now_time > $need_complete_time)) {//没过期并且已经完成了
                        $new_arr[$v['general_poster_id']]['task_info_status'] = 0;//说明任务未领取，可以领取
                    }elseif($now_time < $need_complete_time && $complete_status == 0){//领取后没过期没完成说明不能领取了
                        $new_arr[$v['general_poster_id']]['task_info_status'] = 1;
                    }
                }else{
                    $new_arr[$v['general_poster_id']]['task_info_status'] = 0;//说明任务未领取，可以领取
                }
            }
            $new_arr[$v['general_poster_id']]['general_poster_id'] = $v['general_poster_id'];
            $new_arr[$v['general_poster_id']]['task_type'] = $v['task_type'];
            //任务名称
            $new_arr[$v['general_poster_id']]['task_name'] = $v['task_name'];
            //任务规则
            if ($v['task_type'] == 0) {//普通任务
                $task_rule = $general_poster_list[$k]['task_rule'];
                $task_rule_arr = json_decode($task_rule, true);
                //处理商品信息
                $goods_id = $task_rule_arr['goods_id'];
                $goods_mdl = new VslGoodsModel();
                $goods_name = $goods_mdl->getInfo(['goods_id' => $goods_id], 'goods_name')['goods_name'];
                $task_rule_arr['goods_name'] = $goods_name;
                $new_arr[$v['general_poster_id']]['task_rule'][0] = $task_rule_arr;
            } else {//海报任务
                if ($v['reward_obj'] == 1) {
                    $referrals = $v['referral_num'];
                    $poster_rule_arr = [];
                    $poster_rule_arr['referrals'] = $referrals;
                    $new_arr[$v['general_poster_id']]['task_rule'][] = $poster_rule_arr;
                }
            }
        }
//        echo 2234344;exit;
        $new_arr = array_values($new_arr);
        $res['task_info'] = $new_arr;
        //获取用户信息 和任务未完成统计数量
        $sys_user = new UserModel();
        $user_info = $sys_user->getInfo(['uid' => $uid], 'user_headimg, user_name, user_tel, nick_name');
        $user_name = $user_info['nick_name'] ?: ($user_info['user_name'] ?: $user_info['user_tel']);
        $user_headimg = getApiSrc($user_info['user_headimg']);
        $user_task = new UserTaskModel();
        //用户在规定的时间内 未完成
        $uncomp_cond['uid'] = $uid;
        $uncomp_cond['need_complete_time'] = ['>=', time()];
        $uncomp_cond['is_complete'] = 0;
        $unfinished_count = $user_task->where($uncomp_cond)->count();
        $res['user_info']['user_name'] = $user_name;
        $res['user_info']['user_headimg'] = $user_headimg;
        $res['user_info']['unfinished_count'] = $unfinished_count;
        return [
            'code' => 0,
            'data' => [
                'user_task_info' => $res,
                'total_count' => $count,
                'page_count' => $page_count
            ]
        ];
    }

    /*
     * 获取前端接口任务详情
     * **/
    public function getWapPosterTaskDetail($condition)
    {
//        $general_poster_mdl = new VslGeneralPosterModel();
        $general_poster_mdl = new VslGeneralPosterListModel();
        $user_task_mdl = new UserTaskModel();
        if($condition['user_task_id']){
            $user_task_id = $condition['user_task_id'];
            unset($condition['user_task_id']);
        }
        $task_detail = $general_poster_mdl->alias('gp')
            ->field('gp.*,gp.general_poster_id AS gp_id,pr.*,pr.referral_num AS reward_referral_num,ap.pic_cover')
            ->where($condition)
            ->join('vsl_poster_reward_list pr', 'gp.general_poster_id = pr.general_poster_id', 'LEFT')
            ->join('sys_album_picture ap', 'gp.task_img = ap.pic_id', 'LEFT')
            ->select();
        $rule_arr = [];
        $general_poster_id = $task_detail[0]['general_poster_id'];
        if($user_task_id){
            $condition2 = ['uid'=>$this->uid, 'general_poster_id' => $general_poster_id, 'user_task_id' => $user_task_id];
        }else{
            $condition2 = ['uid'=>$this->uid, 'general_poster_id' => $general_poster_id, 'get_time' => ['<=', time()], 'need_complete_time' => ['>=', time()]];
        }
        $is_get_task = $user_task_mdl->getInfo($condition2, '*');
        foreach ($task_detail as $k => $v) {
            switch ($v['task_type']) {
                case '0'://普通任务
                    //任务图片、标题、类型
                    $task_img = getApiSrc($v['pic_cover']);
                    $task_name = $v['task_name'];
                    //任务时间
                    $start_task_time = date('Y-m-d H:i:s', $v['start_task_time']);
                    $end_task_time = date('Y-m-d H:i:s', $v['end_task_time']);
                    if ($v['task_kind'] == 1) {//单次任务  //1-单次 2-周期 3-单级海报任务  4-多级海报任务
                        $task_kind = 1;
                    } elseif ($v['task_kind'] == 2) {//周期任务
                        $task_kind = 2;
                    }
                    //判断我是否领取过 0-未领取 1-已领取
                    if ($is_get_task) {
                        $rule_arr[0]['is_get'] = 1;
                        $rule_arr[0]['start_task_time'] = date('Y-m-d H:i:s', $is_get_task['get_time']);
                        $rule_arr[0]['end_task_time'] =  date('Y-m-d H:i:s', $is_get_task['need_complete_time']);
                        $start_time = $is_get_task['get_time'];
                        $end_time = $is_get_task['need_complete_time'];
                    }else{
                        $rule_arr[0]['is_get'] = 0;
                        $rule_arr[0]['start_task_time'] = $start_task_time;
                        $rule_arr[0]['end_task_time'] = $end_task_time;
                        $start_time = $is_get_task['get_time'] ?:0;
                        $end_time = $is_get_task['need_complete_time']?:0;
                    }
                    $rule_arr[0]['task_img'] = $task_img;
                    $rule_arr[0]['task_name'] = $task_name;
                    $rule_arr[0]['task_kind'] = $task_kind;
                    $task_limit_time = $v['task_limit_time'];
                    if($task_limit_time == 0){
                        $task_limit_time = round(($v['end_task_time'] - time())/3600, 1);
                    }
                    $rule_arr[0]['task_limit_time'] = $task_limit_time;
                    //获取规则
                    $task_rule_arr = json_decode($v['task_rule'], true);
                    //任务规定完成时间
//                    $rule_arr[0]['task_rule_reward'][0]['task_rule'] = $task_rule_arr;
//                    p($task_rule_arr);exit;
                    //将这个是否完成的方法封装一个固定的方法 没有领取时间
                    $new_task_rule_arr = $this->isRuleComplete($task_rule_arr,$start_time,$end_time, $this->website_id);
                    $rule_arr[0]['task_rule_reward'][0]['task_rule'] = $new_task_rule_arr;
                    //任务奖励
                    if (!empty($v['point']) && $v['point'] != 0) {
                        $rule_arr[0]['task_rule_reward'][0]['task_reward']['point'] = $v['point'];
                    }
                    if (!empty($v['balance']) && $v['balance'] != 0) {
                        $rule_arr[0]['task_rule_reward'][0]['task_reward']['balance'] = $v['balance'];
                    }
                    if (!empty($v['wchat_red_packet']) && $v['wchat_red_packet'] != 0) {
                        $rule_arr[0]['task_rule_reward'][0]['task_reward']['wchat_red_packet'] = $v['wchat_red_packet'];
                    }
                    if (!empty($v['growth']) && $v['growth'] != 0) {
                        $rule_arr[0]['task_rule_reward'][0]['task_reward']['growth'] = $v['growth'];
                    }
                    if (!empty($v['gift_voucher_id'])) {
                        if(getAddons('giftvoucher', $this->website_id)){
                            $gift_voucher = new VslGiftVoucherModel();
                            $giftvoucher_name = $gift_voucher->getInfo(['gift_voucher_id' => $v['gift_voucher_id']], 'giftvoucher_name')['giftvoucher_name'];
                            $rule_arr[0]['task_rule_reward'][0]['task_reward']['gift_voucher_id'] = $v['gift_voucher_id'];
                            $rule_arr[0]['task_rule_reward'][0]['task_reward']['gift_voucher_name'] = $giftvoucher_name;
                        }
                    }
                    if (!empty($v['coupon_type_id'])) {
                        if(getAddons('coupontype', $this->website_id)){
                            $coupon_type = new VslCouponTypeModel();
                            $coupon_name = $coupon_type->getInfo(['coupon_type_id'=> $v['coupon_type_id']], 'coupon_name')['coupon_name'];
                            $rule_arr[0]['task_rule_reward'][0]['task_reward']['coupon_type_id'] = $v['coupon_type_id'];
                            $rule_arr[0]['task_rule_reward'][0]['task_reward']['coupon_name'] = $coupon_name;
                        }
                    }
                    //任务说明
                    $rule_arr[0]['task_explain'] = $v['task_explain'];
                    break;
                case '1'://普通海报
                    //任务图片、标题、类型
                    $task_img = getApiSrc($v['pic_cover']);
                    $task_name = $v['task_name'];
                    $task_kind = 3;//1-单次 2-周期 3-单级海报任务  4-多级海报任务
                    $task_limit_time = $v['task_limit_time'];
                    if($task_limit_time == 0){
                        $task_limit_time = round(($v['end_task_time'] - time())/3600, 1);
                    }
                    //任务时间
                    $start_task_time = date('Y-m-d H:i:s', $v['start_task_time']);
                    $end_task_time = date('Y-m-d H:i:s', $v['end_task_time']);
                    $rule_arr[$v['gp_id']]['task_img'] = $task_img;
                    $rule_arr[$v['gp_id']]['task_name'] = $task_name;
                    //判断我是否领取过 0-未领取 1-已领取
                    if ($is_get_task) {
                        $rule_arr[$v['gp_id']]['is_get'] = 1;
                        $rule_arr[$v['gp_id']]['start_task_time'] = date('Y-m-d H:i:s', $is_get_task['get_time']);
                        $rule_arr[$v['gp_id']]['end_task_time'] =  date('Y-m-d H:i:s', $is_get_task['need_complete_time']);
                        $start_time = $is_get_task['get_time'];
                        $end_time = $is_get_task['need_complete_time'];
                    } else {
                        $rule_arr[$v['gp_id']]['is_get'] = 0;
                        $rule_arr[$v['gp_id']]['start_task_time'] = $start_task_time;
                        $rule_arr[$v['gp_id']]['end_task_time'] = $end_task_time;
                        $start_time = $is_get_task['get_time'] ?:0;
                        $end_time = $is_get_task['need_complete_time']?:0;
                    }
                    $rule_arr[$v['gp_id']]['task_kind'] = $task_kind;
                    $rule_arr[$v['gp_id']]['task_limit_time'] = $task_limit_time;
                    //判断我是否领取过 0-未领取 1-已领取
                    if ($is_get_task) {
                        $rule_arr[0]['is_get'] = 1;
                    } else {
                        $rule_arr[0]['is_get'] = 0;
                    }
                    if ($v['reward_obj'] == 1) {
                        //任务奖励
                        $new_task_rule_arr = $this->isRuleComplete(['referral_num' => $v['referral_num']], $start_time, $end_time, $this->website_id);
//                        $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_rule']['referral_num'] = $new_task_rule_arr;
                        $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_rule'] = $new_task_rule_arr;
//                        p($rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_rule']);exit;
                        if (!empty($v['point']) && $v['point'] != 0) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['point'] = $v['point'];
                        }
                        if (!empty($v['balance']) && $v['balance'] != 0) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['balance'] = $v['balance'];
                        }
                        if (!empty($v['wchat_red_packet']) && $v['wchat_red_packet'] != 0) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['wchat_red_packet'] = $v['wchat_red_packet'];
                        }
                        if (!empty($v['growth']) && $v['growth'] != 0) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['growth'] = $v['growth'];
                        }
                        if (!empty($v['gift_voucher_id'])) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['gift_voucher_id'] = $v['gift_voucher_id'];
                        }
                        if (!empty($v['coupon_type_id'])) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['coupon_type_id'] = $v['coupon_type_id'];
                        }
                    }
                    //任务说明
                    $rule_arr[$v['gp_id']]['task_explain'] = $v['task_explain'];
                    break;
                case '2'://多级海报
                    //任务图片、标题、类型
                    $task_img = getApiSrc($v['pic_cover']);
                    $task_name = $v['task_name'];
                    $task_kind = 4;////1-单次 2-周期 3-单级海报任务  4-多级海报任务
                    $task_limit_time = $v['task_limit_time'];
                    $task_limit_time = $v['task_limit_time'];
                    if($task_limit_time == 0){
                        $task_limit_time = round(($v['end_task_time'] - time())/3600, 1);
                    }
                    //任务时间
                    $start_task_time = date('Y-m-d H:i:s', $v['start_task_time']);
                    $end_task_time = date('Y-m-d H:i:s', $v['end_task_time']);
                    $rule_arr[$v['gp_id']]['task_img'] = $task_img;
                    $rule_arr[$v['gp_id']]['task_name'] = $task_name;
                    //判断我是否领取过 0-未领取 1-已领取
                    if ($is_get_task) {
                        $rule_arr[$v['gp_id']]['is_get'] = 1;
                        $rule_arr[$v['gp_id']]['start_task_time'] = date('Y-m-d H:i:s', $is_get_task['get_time']);
                        $rule_arr[$v['gp_id']]['end_task_time'] =  date('Y-m-d H:i:s', $is_get_task['need_complete_time']);
                        $start_time = $is_get_task['get_time'];
                        $end_time = $is_get_task['need_complete_time'];
                    } else {
                        $rule_arr[$v['gp_id']]['is_get'] = 0;
                        $rule_arr[$v['gp_id']]['start_task_time'] = $start_task_time;
                        $rule_arr[$v['gp_id']]['end_task_time'] = $end_task_time;
                        $start_time = $is_get_task['get_time'] ?:0;
                        $end_time = $is_get_task['need_complete_time']?:0;
                    }
                    $rule_arr[$v['gp_id']]['task_kind'] = $task_kind;
                    $rule_arr[$v['gp_id']]['task_limit_time'] = $task_limit_time;
                    //判断我是否领取过 0-未领取 1-已领取
                    if ($is_get_task) {
                        $rule_arr[0]['is_get'] = 1;
                    } else {
                        $rule_arr[0]['is_get'] = 0;
                    }
                    if ($v['reward_obj'] == 1) {
                        //任务奖励
                        $new_task_rule_arr = $this->isRuleComplete(['referral_num' => $v['referral_num']], $start_time, $end_time, $this->website_id);
                        //任务奖励
//                        $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_rule']['referral_num'] = $new_task_rule_arr;
                        $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_rule'] = $new_task_rule_arr;
                        if (!empty($v['point']) && $v['point'] != 0) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['point'] = $v['point'];
                        }
                        if (!empty($v['balance']) && $v['balance'] != 0) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['balance'] = $v['balance'];
                        }
                        if (!empty($v['wchat_red_packet']) && $v['wchat_red_packet'] != 0) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['wchat_red_packet'] = $v['wchat_red_packet'];
                        }
                        if (!empty($v['growth']) && $v['growth'] != 0) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['growth'] = $v['growth'];
                        }
                        if (!empty($v['gift_voucher_id'])) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['gift_voucher_id'] = $v['gift_voucher_id'];
                        }
                        if (!empty($v['coupon_type_id'])) {
                            $rule_arr[$v['gp_id']]['task_rule_reward'][$k]['task_reward']['coupon_type_id'] = $v['coupon_type_id'];
                        }
                    }
                    //任务说明
                    $rule_arr[$v['gp_id']]['task_explain'] = $v['task_explain'];
                    break;
            }
        }
        $task_arr = array_values($rule_arr)[0];
        return $task_arr;
    }

    public function isRuleComplete($rules,$start_time,$end_time,$website_id,$uid=0)
    {
        if(!$uid){
            $this->uid = $this->uid;
        }else{
            $this->uid = $uid;
        }
        $new_rules = [];
        $i = 0;
        foreach ($rules as $k => $v) {
            $condition = [];
            if ($k == 'referrals' || $k == 'referral_num') {//推荐人
//                $poster_reward = new PosterRecordModel();
//                $condition['poster_type'] = 2;
//                $condition['reco_uid'] = $this->uid;
//                $poster_reward_count = $poster_reward->where($condition)->count();
                if($v){
                    //应该是统计member表
                    $member_mdl = new VslMemberModel();
                    $condition['referee_id|recommend_id'] = $this->uid;
                    $condition['reg_time'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    //统计分销关系
                    $poster_reward_count = $member_mdl->where($condition)->count();
                    $new_rules[$i]['referrals'] = $v;
                    $new_rules[$i]['is_complete'] = $poster_reward_count >= $v ? 1 : 0;
                }

            } elseif ($k == 'distribution_commission') {//分销佣金
                if($v){
                    $distributor_account = new VslDistributorAccountRecordsModel();
                    $condition['uid'] = $this->uid;
                    $condition['website_id'] = $website_id;
                    $condition['create_time'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    $condition['from_type'] = 1;
                    $total_commission_obj = $distributor_account->field('sum(commission) AS total_commission')->where($condition)->find();
                    $total_commission = $total_commission_obj['total_commission'];
                    $new_rules[$i]['distribution_commission'] = $v;
                    $new_rules[$i]['is_complete'] = $total_commission >= $v ? 1 : 0;
                }

            } elseif ($k == 'distribution_orders') {//分销订单
                if($v){
                    $order_distributor = new VslOrderDistributorCommissionModel();
                    $condition['od.buyer_id'] = $this->uid;
                    $condition['od.website_id'] = $website_id;
                    $condition['o.create_time'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    $distribution_orders_obj = $order_distributor
                        ->alias('od')->field('count(DISTINCT(od.order_id)) AS distribution_orders')
                        ->join('vsl_order o', 'od.order_id = o.order_id', 'LEFT')
                        ->where($condition)
                        ->find();
                    $distribution_orders = $distribution_orders_obj['distribution_orders'];
                    $new_rules[$i]['distribution_orders'] = $v;
                    $new_rules[$i]['is_complete'] = $distribution_orders >= $v ? 1 : 0;
                }

            } elseif ($k == 'order_total_money') {//订单满额
                if($v){
                    //订单满额
                    $order_mdl = new VslOrderModel();
                    $condition['order_status'] = 4;
                    $condition['buyer_id'] = $this->uid;
                    $condition['create_time'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    $order_money_obj = $order_mdl
                        ->field('max(order_money) AS max_order_money, order_id')
                        ->where($condition)
                        ->find();
                    $max_order_money = $order_money_obj['max_order_money'];
                    $new_rules[$i]['order_total_money'] = $v;
                    $new_rules[$i]['is_complete'] = $max_order_money >= $v ? 1 : 0;
                }

            } elseif ($k == 'order_total_sum') {//订单累计
                if($v){
                    //订单累计
                    $order_mdl = new VslOrderModel();
                    $condition['order_status'] = 4;
                    $condition['buyer_id'] = $this->uid;
                    $condition['create_time'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    $order_money_obj = $order_mdl
                        ->field('sum(order_money) AS order_money')
                        ->where($condition)
                        ->find();
                    $order_money = $order_money_obj['order_money'];
                    $new_rules[$i]['order_total_sum'] = $v;
                    $new_rules[$i]['is_complete'] = $order_money >= $v ? 1 : 0;
                }

            } elseif ($k == 'pay_order_total_num') {//支付订单
                if($v){
                    //支付订单数
                    $order_mdl = new VslOrderModel();
                    $condition['order_status'] = ['in', [1,2,3,4]];
                    $condition['buyer_id'] = $this->uid;
                    $condition['create_time'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    $buy_count = $order_mdl
                        ->where($condition)
                        ->count();
                    $new_rules[$i]['pay_order_total_num'] = $v;
                    $new_rules[$i]['is_complete'] = $buy_count >= $v ? 1 : 0;
                }

            } elseif ($k == 'goods_id') {
                if($v){
                    //判断是否购买了商品
                    $order_mdl = new VslOrderModel();
                    $condition['og.goods_id'] = $v;
                    $condition['o.order_status'] = 4;
                    $condition['o.buyer_id'] = $this->uid;
                    $condition['o.create_time'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    $is_buy_goods = $order_mdl->alias('o')
                        ->where($condition)
                        ->join('vsl_order_goods og', 'o.order_id = og.order_id', 'LEFT')
                        ->select();
                    $goods = new VslGoodsModel();
                    $goods_name = $goods->getInfo(['goods_id' => $v], 'goods_name')['goods_name'];
                    $new_rules[$i]['goods_id'] = $v;
                    $new_rules[$i]['goods_name'] = $goods_name;
                    $new_rules[$i]['is_complete'] = $is_buy_goods ? 1 : 0;
                }

            } elseif ($k == 'goods_comment_num') {//商品评价
                if($v){
                    $goods_evalute = new VslGoodsEvaluateModel();
                    $condition['uid'] = $this->uid;
                    $condition['website_id'] = $website_id;
                    $condition['addtime'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    $comment_count = $goods_evalute->where($condition)->count();
                    $new_rules[$i]['goods_comment_num'] = $v;
                    $new_rules[$i]['is_complete'] = $comment_count >= $v ? 1 : 0;
                }

            } elseif ($k == 'total_recharge') {//累计充值
                if($v){
                    $member_account_record = new VslMemberAccountRecordsModel();
                    $condition['uid'] = $this->uid;
                    $condition['from_type'] = 4;
                    $condition['create_time'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    $condition['website_id'] = $website_id;
                    $member_recharge_obj = $member_account_record->field('sum(number) AS total_recharge_money')->where($condition)->find();
                    $member_recharge_total = $member_recharge_obj['total_recharge_money'];
                    $new_rules[$i]['total_recharge'] = $v;
                    $new_rules[$i]['is_complete'] = $member_recharge_total >= $v ? 1 : 0;
                }

            } elseif ($k == 'single_recharge') {//单次充值
                if($v){
                    $member_account_record = new VslMemberAccountRecordsModel();
                    $condition['uid'] = $this->uid;
                    $condition['from_type'] = 4;
                    $condition['create_time'] = [
                        ['>=', $start_time],['<=', $end_time]
                    ];
                    $condition['website_id'] = $website_id;
                    $max_member_recharge_obj = $member_account_record->field('max(number) AS max_recharge_money')->where($condition)->find();
                    $max_recharge_money = $max_member_recharge_obj['max_recharge_money'];
                    $new_rules[$i]['single_recharge'] = $v;
                    $new_rules[$i]['is_complete'] = $max_recharge_money >= $v ? 1 : 0;
                }
            }
            $i++;
        }
        $new_rules = array_values($new_rules);
        return $new_rules;
    }

    /*
     * 插入addGeneralPoster
     * **/
    public function addGeneralPoster($post_data)
    {
        $poster_mdl = new VslGeneralPosterModel();
        $poster_list_mdl = new VslGeneralPosterListModel();
        $general_poster_id = $poster_mdl->save($post_data);
        $post_data['general_poster_id'] = $general_poster_id;
        $poster_list_mdl->save($post_data);//这里是为了防止删除了任务，任务名称等信息失去
        return $general_poster_id;
    }

    /*
     * 编辑GeneralPoster
     * **/
    public function editGeneralPoster($post_data)
    {
        $post_data['update_time'] = time();
        $general_poster_id = $post_data['general_poster_id'];
        unset($post_data['general_poster_id']);
        $poster_mdl = new VslGeneralPosterModel();
        $poster_list_mdl = new VslGeneralPosterListModel();
        $bool = $poster_mdl->save($post_data, ['general_poster_id' => $general_poster_id]);
        $poster_list_mdl->save($post_data, ['general_poster_id' => $general_poster_id]);
        return $bool;
    }

    /*
     * 前台-海报任务列表
     * **/
    public function getMyPosterTaskList($page_index = 1, $page_size, $condition, $order, $task_status)
    {
        $uid = $this->uid;
        if (!$uid) {
            return ['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录'];
        }
        //偏移量
        $general_poster_mdl = new VslGeneralPosterListModel();
        //一共多少页
        $general_poster_list = $general_poster_mdl->alias('gp')
            ->field('gp.task_name, gp.general_poster_id, pr.task_rule, gp.task_type, gp.referrals, gp.is_delete, pr.reward_obj, pr.referral_num, ut.get_time, ut.need_complete_time, gp.task_limit_time, ut.user_task_id, ut.is_complete, gp.end_task_time')
            ->join('vsl_poster_reward_list pr', 'gp.general_poster_id = pr.general_poster_id', 'LEFT')
            ->join('vsl_user_task ut', 'gp.general_poster_id = ut.general_poster_id', 'LEFT')
            ->where($condition)
            ->order($order)
            ->select();
//        p($general_poster_list);
//        echo $general_poster_mdl->getLastSql();exit;
        $temp_new_task_list = [];
        $new_task_list = [];
        foreach($general_poster_list as $k0 => $v0){
            $temp_new_task_list[$v0['user_task_id']][$v0['referral_num']][$v0['general_poster_id']] = $v0;
        }
        foreach($temp_new_task_list as $k01 => $v01){
            foreach($v01 as $k02 => $v02){
                foreach($v02 as $k03 => $v03){
                    $new_task_list[$v03['user_task_id']][] = $v03;
                }
            }
        }
        $new_arr = [];
        foreach ($new_task_list as $k1 => $v1) {
            foreach($v1 as $k=>$v){
                if($task_status == 1){//正在进行
                    if($v['task_limit_time'] != 0){//任务限时为0说明任务不限制时间， 直到过期。
                        if($v['need_complete_time'] <= time() || $v['is_complete'] == 1 || $v['is_delete'] == 1){//删除了的跳出去。有一张任务副表记录是否删除
                            continue;
                        }
                    }else{
                        if($v['end_task_time'] <= time() || $v['is_complete'] == 1 || $v['is_delete'] == 1){
                            continue;
                        }
                    }
                }elseif($task_status == 2){//已完成
                    if($v['is_complete'] != 1){
                        continue;
                    }
                }elseif($task_status == 3){//已失效
                    if($v['is_delete'] == 0){
                        if($v['task_limit_time'] != 0){
                            if($v['need_complete_time'] > time() || $v['is_complete'] == 1){
                                continue;
                            }
                        }else{
                            if($v['end_task_time'] > time() || $v['is_complete'] == 1){
                                continue;
                            }
                        }
                    }else{
                        if($v['is_complete'] == 1){
                            continue;
                        }
                    }
                }
                $new_arr[$k1]['general_poster_id'] = $v['general_poster_id'];
                //任务名称
                $new_arr[$k1]['task_name'] = $v['task_name'];
                $new_arr[$k1]['user_task_id'] = $v['user_task_id'];
                //任务规则
                if ($v['task_type'] == 0) {//普通任务
                    $task_rule = $v1[$k]['task_rule'];
                    $task_rule_arr = json_decode($task_rule, true);
                    //处理商品信息
                    $goods_id = $task_rule_arr['goods_id'];
                    $goods_mdl = new VslGoodsModel();
                    $goods_name = $goods_mdl->getInfo(['goods_id' => $goods_id], 'goods_name')['goods_name'];
                    $task_rule_arr['goods_name'] = $goods_name;
                    $new_arr[$k1]['task_rule'][0] = $task_rule_arr;
                } else {//海报任务
                    if ($v['reward_obj'] == 1) {
                        $referrals = $v['referral_num'];
                        $poster_rule_arr = [];
                        $poster_rule_arr['referrals'] = $referrals;
                        $new_arr[$k1]['task_rule'][] = $poster_rule_arr;
                    }
                }

            }
        }
        $new_arr = array_values($new_arr);
        $count = count($new_arr);
        $offset = ($page_index - 1) * $page_size;
        $page_count = ceil($count / $page_size);
        $page_data = array_slice($new_arr, $offset, $page_size);
        $res['task_info'] = $page_data;
        return [
            'code' => 0,
            'data' => [
                'user_task_info' => $res,
                'total_count' => $count,
                'page_count' => $page_count
            ]
        ];
    }
    /**
     * 获取海报信息
     * @param array $condition
     * @param array $with
     *
     * @return mixed
     */
    public function poster(array $condition, array $with = [])
    {
        $general_poster_model = new VslGeneralPosterModel();
        return $general_poster_model::get($condition, $with);
    }


    public function savePosterRecord(array $data)
    {
        $poster_record_model = new PosterRecordModel();
        $poster_record_model->save($data);
    }

    /**
     * 用户扫码关注公众号进入商城，说明推荐成功
     */
    public function successRecommend(array $general_poster_condition, $uid, $buid)
    {
        $general_poster_condition['start_task_time'] = ['<=', time()];
        $general_poster_condition['end_task_time'] = ['>', time()];
        $poster_info = $this->poster($general_poster_condition, ['poster_reward']);
        //用户领取的任务不能过期
        $user_task_condition['uid'] = $buid;
        $user_task_condition['is_complete'] = 0;
        $user_task_condition['get_time'] = ['<=', time()];
        $user_task_condition['need_complete_time'] = ['>', time()];
        $user_task = new UserTaskModel();
        $user_task_list = $user_task->getInfo($user_task_condition, '*');
        if (empty($poster_info) || empty($user_task_list)) {
            return ['code' => -1, 'message' => '任务海报数据异常'];
        }
        $user_model = new UserModel();
        $temp_user_list = $user_model::all(['user_model.uid' => $buid], ['member_info']);
        $user_list = [];
        foreach ($temp_user_list as $v) {
            $user_list[$v['uid']] = $v;
        }
        $wx_openid = $temp_user_list[0]['wx_openid'];
        unset($temp_user_list);
        // start 记录推荐
        $record_data = [];
        $record_data['poster_type'] = 2;// 海报任务
        $record_data['poster_id'] = $poster_info['general_poster_id'];
        $record_data['reco_uid'] = $uid;
        $record_data['be_reco_uid'] = $buid;
        $record_data['scan_time'] = time();
        $record_data['shop_id'] = $poster_info['shop_id'];
        $record_data['website_id'] = $poster_info['website_id'];
        $this->savePosterRecord($record_data);
        unset($record_data);
        // end 记录推荐
        //发放被推荐者奖励
        foreach ($poster_info['poster_reward'] as $v) {
            if ($v['reward_obj'] == 2) {
                // 被推荐人
                $reward_uid = $buid;
                if (empty($reward_uid)) {
                    return ['code' => -1, 'message' => '奖励对象错误'];
                }
                $change_str = '关注公众号获得任务奖励';
                $this->sendDetailReward($v, $reward_uid, $user_list, $poster_info, $change_str);
            }
        }
        if($poster_info['perm_setting'] == 0){
            $member_data['recommend_id'] = $uid;
            if ($poster_info['is_become_offline']) {//都是分销商的情况
                // 成为下线
                //判断uid是否为分销商
                $member = new VslMemberModel();
                $isdistributor = $member->getInfo(['uid' => $uid], 'isdistributor')['isdistributor'];
                if ($isdistributor == 2) {
                    // 推荐者是分销商，被推荐人才能成为分销商
                    $member_data['referee_id'] = $uid;
                }
            }
        }else{
            // 成为下线
            $member_data['recommend_id'] = $uid;
            //判断uid是否为分销商
            $member = new VslMemberModel();
            $isdistributor = $member->getInfo(['uid' => $uid], 'isdistributor')['isdistributor'];
            if ($isdistributor == 2) {
                // 推荐者是分销商，被推荐人才能成为分销商
                $member_data['referee_id'] = $uid;
            }
        }
        $distribution_status = getAddons('distribution', $poster_info['website_id']);
        if ($poster_info['is_become_distribution'] && $distribution_status && $isdistributor) {
            // 成为分销商
            $distributor_level_model = new VslDistributorLevelModel();
            $distributor_service = new Distributor();
            $member_data['distributor_level_id'] = $distributor_level_model::get(['website_id' => $poster_info['website_id'], 'is_default' => 1])['id'];
            $member_data['isdistributor'] = 2;
            $member_data['extend_code'] = $distributor_service->create_extend();
            $member_data['become_distributor_time'] = time();
        }
        if (isset($member_data) && !empty($member_data)) {
            $member_model = new VslMemberModel();
            $member_model->save($member_data, ['uid' => $buid]);
        }
        //推送一张海报
        $weixin = new Weixin();
        $poster_info['poster_type'] = 'task_poster';
        $weixin->getPosterSend($poster_info, '', $wx_openid);
        return ['code' => 1];
    }

    public function sendDetailReward($reward_arr, $reward_uid, $user_list, $poster_info, $change_str)
    {
        $memberAccount = new MemberAccount();
        $wx_pai_api = new WxPayApi();
        $red_pack_fail_record_model = new RedPackFailRecordModel();
        if ($reward_arr['point'] > 0) {
            // 积分
            $memberAccount->addMemberAccountData(1, $reward_uid, 1, $reward_arr['point'],
                33, $poster_info['website_id'], '海报奖励,推荐得积分');

        }
        if ($reward_arr['balance'] > 0) {
            // 余额
            $memberAccount->addMemberAccountData(2, $reward_uid, 1, $reward_arr['balance'],
                29, $poster_info['website_id'], '海报奖励,推荐得余额');
            //发送余额变动提醒
            runhook("Notify", "balanceChangeByTemplate", ["website_id" => $poster_info['website_id'], "uid" => $reward_uid, "change_money" => $reward_arr['balance'], 'change_str' => $change_str, 'type_desc' => '任务奖励']);
        }
        if ($reward_arr['wchat_red_packet'] > 0) {
            // 微信红包
            $scene_id = '';
            if ($reward_arr['wchat_red_packet'] > 200 || $reward_arr['wchat_red_packet'] < 1) {
                // 大于200元或者小于1元 需要场景id
                $scene_id = 'PRODUCT_5';
            }
            $act_name = $remark = $wishing = '海报任务红包奖励';
            $result = $wx_pai_api::sendRedPack($act_name, $reward_arr['wchat_red_packet'], 1, $poster_info['website_id'], $remark, $wishing, $user_list[$reward_uid]['wx_openid'], $scene_id);
            if (!($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS' && $result['err_code'] == 'SUCCESS')) {
                // insert fail record
                $temp_fail_data = [];
                $temp_fail_data['open_id'] = $user_list[$reward_uid]['wx_openid'];
                $temp_fail_data['money'] = $reward_arr['wchat_red_packet'];
                $temp_fail_data['num'] = 1;
                $temp_fail_data['website_id'] = $poster_info['website_id'];
                $temp_fail_data['act_name'] = $act_name;
                $temp_fail_data['remark'] = $remark;
                $temp_fail_data['wishing'] = $wishing;
                $temp_fail_data['scene_id'] = $scene_id;
                $temp_fail_data['fail_reason'] = $result['return_msg'];
//                $fail_record_data[] = $temp_fail_data;
                $red_pack_fail_record_model->save($temp_fail_data);
//                unset($temp_fail_data);
            }
        }
        if ($reward_arr['growth'] > 0) {
            $user_service = new User();
            // 成长值
            $user_service->updateUserGrowthNum(4, $reward_uid, $reward_arr['growth'], 0);
        }
        $is_gift_voucher = getAddons('giftvoucher', $poster_info['website_id']);
        $is_coupon = getAddons('coupontype', $poster_info['website_id']);
        $coupon_server = $is_coupon ? new Coupon() : '';
        $gift_voucher_service = $is_gift_voucher ? new GiftVoucher() : '';
        if (!empty($reward_arr['gift_voucher_id']) && $is_gift_voucher && $gift_voucher_service->isGiftVoucherReceive($reward_arr['gift_voucher_id'], $reward_uid)) {
            // 礼品券
            $gift_voucher_service->getUserReceive($reward_uid, $reward_arr['gift_voucher_id'], 5);
        }
        if (!empty($reward_arr['coupon_type_id']) && $is_coupon && $coupon_server->isCouponTypeReceivable($reward_arr['coupon_type_id'], $reward_uid)) {
            // 优惠券
            $coupon_server->userAchieveCoupon($reward_uid, $reward_arr['coupon_type_id'], 10);
        }
    }
    /*
     * 领取海报任务
     * **/
    public function getPosterTask($general_poster_id, $uid, $website_id = 0, $key_status = 0)
    {
        if($website_id){
            $this->website_id = $website_id;
        }
        $general_poster_mdl = new VslGeneralPosterModel();
        $user_task_mdl = new UserTaskModel();
        $condition0['general_poster_id'] = $general_poster_id;
        $condition0['start_task_time'] = ['<=', time()];
        $condition0['end_task_time'] = ['>=', time()];
        if(!$key_status){
            $condition0['is_auto_take_task'] = 1;
        }
        $general_poster_info = $general_poster_mdl->getInfo($condition0,'*');
        if($general_poster_info || $key_status){//任务应该设置了关注领取，并且没有过期 或者通过关键字领取
            //先判断是否领取过任务
            $condition1['uid'] = $uid;
            $condition1['general_poster_id'] = $general_poster_id;
            $general_poster_user_info1 = $user_task_mdl->getInfo($condition1, '*');
            if($general_poster_user_info1){//如果有领取过该任务
                //判断是否可以重复领取任务
                if ($general_poster_info['is_repeat_take_task']) {
                    $condition2['uid'] = $uid;
                    $condition2['general_poster_id'] = $general_poster_id;
                    $condition2['is_complete'] = 0;
                    $condition2['get_time'] = ['<=', time()];
                    $condition2['need_complete_time'] = ['>=', time()];
                    $general_poster_user_info2 = $user_task_mdl->getInfo($condition2, '*');
                    if(!$general_poster_user_info2){//能够领取的条件
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
            }else{
                $data['uid'] = $uid;
                $data['general_poster_id'] = $general_poster_id;
                $data['get_time'] = time();
                $task_limit_time = $general_poster_info['task_limit_time'];
                //如果限时为0，则说明是不限时间直到任务结束
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
    }
    /**
     * 海报列表
     * @param int $page_index
     * @param mixed $page_size
     * @param array $condition
     * @param string $order
     * @param string $field
     * @param string $group
     * @return array
     */
    public function recordList($page_index = 1, $page_size = 0, $condition = [], $order = 'pr.poster_record_id DESC', $field = '', $group = 'pr.poster_record_id')
    {
        if (empty($field)) {
            $field = 'pr.*,
            ru.user_name as reco_user_name,
            ru.user_headimg as reco_user_headimg,
            ru.user_tel as reco_user_tel,
            bru.user_name as b_reco_user_name,
            bru.user_headimg as b_reco_user_headimg,
            bru.user_tel as b_reco_user_tel,
            bru.nick_name as b_nick_name';
        }
        $poster_record_model = new VslPosterRecordModel();
        $user_model = new UserModel();
        if ($condition['reco']) {
            $reco_id_list = objToArr($user_model->where('user_name', 'LIKE', '%' . $condition['reco'] . '%')->whereOr('nick_name', 'LIKE', '%' . $condition['reco'] . '%')->field('uid')->select());
            $reco_id_array = array_column($reco_id_list, 'uid');
            $condition['pr.reco_uid'] = ['IN', $reco_id_array];
        }
        if ($condition['be_reco']) {
            $be_reco_id_list = objToArr($user_model->where('user_name', 'LIKE', '%' . $condition['be_reco'] . '%')->whereOr('nick_name', 'LIKE', '%' . $condition['be_reco'] . '%')->field('uid')->select());
            $be_reco_id_array = array_column($be_reco_id_list, 'uid');
            $condition['pr.be_reco_uid'] = ['IN', $be_reco_id_array];
        }
        unset($condition['reco'], $condition['be_reco']);
        $query_list_obj = $poster_record_model->alias('pr')
            ->join($user_model->table . ' ru', 'pr.reco_uid = ru.uid', 'LEFT')
            ->join($user_model->table . ' bru', 'pr.be_reco_uid = bru.uid', 'LEFT')
            ->field($field);

        $record_list = $poster_record_model->viewPageQuerys($query_list_obj, $page_index, $page_size, $condition, $order, $group);
        $count = $poster_record_model->alias('pr')
            ->where($condition)
            ->count('pr.poster_record_id');

        $list = $poster_record_model->setReturnList($record_list, $count, $page_size);
//        $list = $poster_record_model->pageQuery($page_index, $page_size, $condition, $order, $field);

        return $list;
    }
    /*
     * 任务是否完成，发放奖励
     * **/
    public function sendReward($general_poster_info, $uid, $get_time, $need_complete_time)
    {
        //查询出规则
        $poster_reward = new VslPosterRewardModel();
        $user_task = new UserTaskModel();
        $poster_reward_arr = $poster_reward->getquery(['general_poster_id' => $general_poster_info['general_poster_id'], 'reward_obj'=>['neq', 2]], '*', '');
        $user_task_cond = [
            'uid' => $uid,
            'general_poster_id' => $general_poster_info['general_poster_id'],
            'is_complete' => 0,
            'get_time' => ['<=',time()],
            'need_complete_time' => ['>=',time()],
            'website_id' => $general_poster_info['website_id']
        ];
        $complete_case = $user_task->getInfo($user_task_cond, 'complete_case')['complete_case'];
        //先判断任务类型
        foreach($poster_reward_arr as $k=>$v){
            if($v['task_type'] == 0){
                //任务规则
                $task_rule = $v['task_rule'];
                $task_rule = json_decode($task_rule, true);
            }elseif($v['task_type'] == 1 || $v['task_type'] == 2){
                //任务规则
                if($v['reward_obj'] == 1) {
                    $task_rule[$v['referral_num']] = $v['referral_num'];
                }
            }
        }
        //判断是否达成了条件
        $reach_arr = $this->isTaskFinish($v['task_type'], $task_rule, $uid, $get_time, $need_complete_time, $general_poster_info['website_id']);
        if($reach_arr['is_finish']){
            $temp_finish_arr = [];
            $reward_arr = [];
            foreach($poster_reward_arr as $k1 => $v1){
                if($v1['task_type'] == 0){
                    $reward_arr[0]['general_poster_id'] = $general_poster_info['general_poster_id'];
                    $reward_arr[0]['uid'] = $uid;
                    $reward_arr[0]['point'] = $v1['point'];
                    $reward_arr[0]['balance'] = $v1['balance'];
                    $reward_arr[0]['wchat_red_packet'] = $v1['wchat_red_packet'];
                    $reward_arr[0]['growth'] = $v1['growth'];
                    $reward_arr[0]['gift_voucher_id'] = $v1['gift_voucher_id'];
                    $reward_arr[0]['coupon_type_id'] = $v1['coupon_type_id'];
                    $temp_finish_arr[] = 1;
                }elseif($v1['task_type'] == 1 || $v1['task_type'] == 2){
                    if($reach_arr['reach_num'] >= $v1['referral_num'] && $v1['referral_num'] > $complete_case){
                        $reward_arr[$k1] = $v1;
                        $reward_arr[$k1]['general_poster_id'] = $general_poster_info['general_poster_id'];
                        $reward_arr[$k1]['uid'] = $uid;
                    }
                    if($reach_arr['reach_num'] >= $v1['referral_num']){//用于判断是否完成任务
                        $temp_finish_arr[] =  $v1['referral_num'];

                    }
                }
            }
            $this->sendUserReward($reward_arr);
            $user_task = new UserTaskModel();
            if(count($poster_reward_arr) == count($temp_finish_arr)){
                //将任务设置成已完成
                $user_task_cond['uid'] = $uid;
                $user_task_cond['general_poster_id'] = $general_poster_info['general_poster_id'];
                $user_task_cond['is_complete'] = 0;
                $user_task_cond['get_time'] = ['<=', time()];
                $user_task_cond['need_complete_time'] = ['>', time()];
                $user_task->save(['is_complete' => 1], $user_task_cond);
            }else{
                if($v1['task_type'] == 2 && count($reward_arr) >= 1){
                    $user_task_cond['uid'] = $uid;
                    $user_task_cond['general_poster_id'] = $general_poster_info['general_poster_id'];
                    $user_task_cond['is_complete'] = 0;
                    $user_task_cond['get_time'] = ['<=', time()];
                    $user_task_cond['need_complete_time'] = ['>', time()];
                    $user_task->save(['complete_case' => $reach_arr['reach_num']], $user_task_cond);
                }
            }
        }
    }

    public function isTaskFinish($task_type, $task_rule, $uid, $get_time, $need_complete_time, $website_id)
    {
        if($task_type === 0){
            $total_task = [];
            $referral_num = $task_rule['referrals'];
            if($referral_num){
                $total_task[] = 1;
            }
            $distribution_commission = $task_rule['distribution_commission'];
            if($distribution_commission){
                $total_task[] = 1;
            }
            $distribution_orders = $task_rule['distribution_orders'];
            if($distribution_orders){
                $total_task[] = 1;
            }
            $order_total_money = $task_rule['order_total_money'];
            if($order_total_money){
                $total_task[] = 1;
            }
            $order_total_sum = $task_rule['order_total_sum'];
            if($order_total_sum){
                $total_task[] = 1;
            }
            $pay_order_total_num = $task_rule['pay_order_total_num'];
            if($pay_order_total_num){
                $total_task[] = 1;
            }
            $goods_id = $task_rule['goods_id'];
            if($goods_id){
                $total_task[] = 1;
            }
            $goods_comment_num = $task_rule['goods_comment_num'];
            if($goods_comment_num){
                $total_task[] = 1;
            }
            $total_recharge = $task_rule['total_recharge'];
            if($total_recharge){
                $total_task[] = 1;
            }
            $single_recharge = $task_rule['single_recharge'];
            if($single_recharge){
                $total_task[] = 1;
            }
            $task_count = count($total_task);
            $finish_task_info = $this->isRuleComplete($task_rule, $get_time, $need_complete_time, $website_id, $uid);
            $finish_mark = [];
            foreach($finish_task_info as $v){
                if($v['is_complete'] == 1){
                    $finish_mark[] = 1;
                }
            }
            if(count($finish_mark) == $task_count){
                $reach_arr['is_finish']  = 1;
            }else{
                $reach_arr['is_finish']  = 0;
            }
        }elseif($task_type === 1 || $task_type === 2){
//            p($task_rule);exit;
            $reach_arr = [];
            foreach($task_rule as $k=>$referral_num){
                $member_list = $this->getrRefereeInfo($uid, $get_time, $need_complete_time);
                $already_referee_num = count($member_list);
                if($already_referee_num >= $referral_num){
                    $reach_arr['is_finish'] = 1;
                    $reach_arr['reach_num'] = $referral_num;
                }
                if(!isset($reach_arr['is_finish'])){
                    $reach_arr['is_finish'] = 0;
                }
            }
        }
        return $reach_arr;
    }

    public function getrRefereeInfo($uid, $get_time, $need_complete_time)
    {
        $member_mdl = new VslMemberModel();
        $referral_cond['reg_time'] = [['>=', $get_time], ['<', $need_complete_time]];
        $referral_cond['referee_id|recommend_id'] = $uid;
        $member_list = $member_mdl->getquery($referral_cond, 'uid', '');
        return $member_list;
    }
    /*
     * 发放奖励
     * **/
    public function sendUserReward($reward_arr)
    {
        foreach($reward_arr as $reward_value){
            $general_poster_mdl = new VslGeneralPosterModel();
            $user_model = new UserModel();
            $temp_user_list = $user_model::all(['user_model.uid' => $reward_value['uid']], ['member_info']);
            $user_list = [];
            foreach ($temp_user_list as $v) {
                $user_list[$v['uid']] = $v;
            }
            unset($temp_user_list);
            $poster_info = $general_poster_mdl->getInfo(['general_poster_id' => $reward_value['general_poster_id']], '*');
            $change_str = '任务：'.$poster_info['task_name'].'完成，奖励余额';
            $this->sendDetailReward($reward_value, $reward_value['uid'], $user_list, $poster_info, $change_str);
        }
    }
}