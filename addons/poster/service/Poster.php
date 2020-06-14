<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:39
 */

namespace addons\poster\service;

use addons\coupontype\model\VslCouponTypeModel;
use addons\coupontype\server\Coupon;
use addons\distribution\model\VslDistributorLevelModel;
use addons\distribution\service\Distributor;
use addons\giftvoucher\server\GiftVoucher;
use addons\poster\model\PosterAwardModel;
use addons\poster\model\PosterModel;
use addons\poster\model\PosterRecordModel;
use data\extend\WchatOauth;
use data\extend\weixin\WxPayApi;
use data\model\AddonsConfigModel;
use data\model\RedPackFailRecordModel;
use data\model\UserModel;
use data\model\VslMemberModel;
use data\model\WebSiteModel;
use data\model\WeixinKeyReplayModel;
use data\service\BaseService;
use data\service\Member\MemberAccount;
use data\service\User;
use data\service\WebSite;
use think\Config;
use think\Db;

class Poster extends BaseService
{

    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * 海报列表
     * @param int $page_index
     * @param mixed $page_size
     * @param array $condition
     * @param string $order
     * @param string $field
     * @return array
     */
    public function posterList($page_index = 1, $page_size = 0, $condition = [], $order = '', $field = '*')
    {
        $poster_model = new PosterModel();
        $list = $poster_model->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
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
            ru.nick_name as reco_user_name,
            ru.user_headimg as reco_user_headimg,
            ru.user_tel as reco_user_tel,
            bru.nick_name as b_reco_user_name,
            bru.user_headimg as b_reco_user_headimg,
            bru.user_tel as b_reco_user_tel';
        }
        $poster_record_model = new PosterRecordModel();
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
//        var_dump(Db::table('')->getLastSql());exit;
        $count = $poster_record_model->alias('pr')
            ->where($condition)
            ->count('pr.poster_record_id');

        $list = $poster_record_model->setReturnList($record_list, $count, $page_size);
//        $list = $poster_record_model->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }

    /**
     * 设置默认的海报
     * @param $id
     * @param $type
     * @param $shop_id
     * @param $website_id
     * @return int
     */
    public function defaultPoster($id, $type, $shop_id, $website_id)
    {
        $poster_model = new PosterModel();
        try {
            $poster_model->save(['is_default' => 0], ['type' => $type, 'is_default' => 1, 'shop_id' => $shop_id, 'website_id' => $website_id, 'poster_id' => ['NEQ', $id]]);
            $poster_model->save(['is_default' => 1], ['poster_id' => $id]);
            $poster_model->commit();
            return 1;
        } catch (\Exception $e) {
            return UPDATA_FAIL;
        }
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
        $poster_model = new PosterModel();
        return $poster_model::get($condition, $with);
    }

    /**
     * 保存海报的信息 只用于保存单个海报
     * @param array $data 海报数据
     * @param string $id 海报id
     * @param array $award_data 海报奖励
     * @return false|int
     */
    public function savePoster(array $data, $id = '', array $award_data = [])
    {
        $weixinKeyReplay = new WeixinKeyReplayModel();
        $key_replay_cond['key'] = $data['key'];
        $key_replay_cond['website_id'] = $this->website_id;
        $key_replay_cond['poster_id'] = ['neq', $id];
        $key_list = $weixinKeyReplay->getInfo($key_replay_cond, '*');
        if($key_list){
            return -1;
        }
        $data['website_id'] = $this->website_id;
        $poster_model = new PosterModel();
        if (!$id) {
            $data['create_time'] = time();
            $id = $poster_model->save($data);
        } else {
            $data['modify_time'] = time();
            $poster_model->save($data, ['poster_id' => $id]);
        }
//        $key_replay_cond['key'] = $data['key'];
        $key_replay_cond1['website_id'] = $this->website_id;
        $key_replay_cond1['poster_id'] = $id;
        $key_list2 = $weixinKeyReplay->getInfo($key_replay_cond1, '*');
        if($data['key']){
            if($data['type'] == 1){//1:商城 2:商品  3:关注 4:微店
                $rule_name = '商城海报';
            }elseif($data['type'] == 2){
                $rule_name = '商品海报';
            }elseif($data['type'] == 3){
                $rule_name = '关注海报';
            }elseif($data['type'] == 4){
                $rule_name = '微店海报';
            }
            //将关键字插入到关键字回复表中
            $key_replay['rule_name'] = $rule_name;
            $key_replay['key'] = $data['key'];
            $key_replay['match_type'] = 2;
            $key_replay['create_time'] = time();
            $key_replay['website_id'] = $this->website_id;
            $key_replay['replay_type'] = 1;//0-普通关键字 1-海报关键字
            $key_replay['poster_id'] = $id;
            if(!$key_list2){
                $weixinKeyReplay->save($key_replay);
            }else{
                $replay_cond['poster_id'] = $id;
                $replay_cond['website_id'] = $this->website_id;
                $weixinKeyReplay->save($key_replay, $replay_cond);
            }
        }

        if($id && $data['is_default'] == 1){
            //将该类型的其它默认全部设为非默认
            $cond['poster_id'] = ['neq', $id];
            $cond['type'] = $data['type'];
            $cond['website_id'] = $this->website_id;
            $change_data['is_default'] = 0;
            $poster_model->save($change_data, $cond);
        }
        if (!empty($award_data)) {
            $poster_award_model = new PosterAwardModel();
            if (isset(reset($award_data)['award_id']) && !empty(reset($award_data)['award_id'])) {
                foreach ($award_data as $v) {
                    $v['poster_id'] = $id;
                    $poster_award_model->data($v, true)->isUpdate(true)->save();
                }
            } else {
                foreach ($award_data as &$v) {
                    $v['poster_id'] = $id;
                    unset($v['award_id']);
                }
                unset($v);
                $poster_award_model->saveAll($award_data);
            }
        }
        return $id;
    }

    /**
     * 删除海报
     * @param $condition
     * @return int
     */
    public function deletePoster($condition)
    {
        $poster_model = new PosterModel();
        $poster_award_model = new PosterAwardModel();
        $poster_record_model = new PosterRecordModel();
        $wxkey_replay = new WeixinKeyReplayModel();
        $poster_list = $poster_model::all($condition);
        foreach ($poster_list as $v) {
            $poster_award_model->destroy(['poster_id' => $v['poster_id']]);
            $poster_record_model->destroy(['poster_id' => $v['poster_id']]);
        }
        $poster_model->destroy($condition);
        //删除回复关键字表中的对应海报
        $wxkey_replay->destroy(['poster_id' => $condition['poster_id']]);
        return ['code' => 1, 'message' => '删除成功'];
    }

    /**
     * 获取系统默认模板
     * @param array $condition
     * @param bool $empty 是否返回空白的内容
     * @return array
     * @throws \think\Exception\DbException
     */
    public function systemDefaultPoster(array $condition, $empty = true)
    {
        $poster_model = new PosterModel();
        $poster_list = $poster_model::all($condition);
        $list = [];
        if ($empty) {
            $list = [
                1 => [ // 海报类型
                    0 => [ // 数组下标
                        'poster_id' => '',
                        'poster_name' => '空白模板',
                        'template_logo' => '/public/static/images/customPC/blankTemplate.png',
                    ],
                ],
//                2 => [
//                    0 => [
//                        'poster_id' => '',
//                        'poster_name' => '空白模板',
//                        'template_logo' => '/public/static/images/customPC/blankTemplate.png',
//                    ]
//                ],
//                3 => [
//                    0 => [
//                        'poster_id' => '',
//                        'poster_name' => '空白模板',
//                        'template_logo' => '/public/static/images/customPC/blankTemplate.png',
//                    ]
//                ],
//                4 => [
//                    0 => [
//                        'poster_id' => '',
//                        'poster_name' => '空白模板',
//                        'template_logo' => '/public/static/images/customPC/blankTemplate.png',
//                    ]
//                ]
            ];
        }
        foreach ($poster_list as $v) {
            $temp['poster_id'] = $v['poster_id'];
            $temp['poster_name'] = $v['poster_name'];
            $temp['type'] = $v['type'];
            $temp['template_logo'] = $v['template_logo'];

//            $list[$v['type']][] = $temp;
            $list[1][] = $temp;
        }
        return $list;
    }

    public function savePosterRecord(array $data)
    {
        $poster_record_model = new PosterRecordModel();
        $poster_record_model->save($data);
    }

    /**
     * 成功推荐：记录推荐、发放奖励
     * @param array $poster_condition 海报
     * @param int $uid 推荐人
     * @param int $buid 被推荐人
     */
    public function successRecommend(array $poster_condition, $uid, $buid)
    {
        try{
            $poster_info = $this->poster($poster_condition, ['poster_award']);
            if (empty($poster_info)) {
                return ['code' => -1, 'message' => '海报数据为空'];
            }

            $weixin = new WchatOauth($poster_info['website_id']);
            $user_service = new User();
            $user_model = new UserModel();
            $red_pack_fail_record_model = new RedPackFailRecordModel();
            $fail_record_data = [];
            $act_name = $remark = $wishing = '海报任务红包奖励';
            $is_gift_voucher = getAddons('giftvoucher', $poster_info['website_id']);
            $is_coupon = getAddons('coupontype', $poster_info['website_id']);
            $coupon_type_model = $is_coupon ? new VslCouponTypeModel() : '';
            $temp_user_list = $user_model::all(['user_model.uid' => ['IN', [$uid, $buid]]], ['member_info']);
            $user_list = [];
            foreach ($temp_user_list as $v) {
                $user_list[$v['uid']] = $v;
            }
            unset($temp_user_list);
            if($uid != $buid){
                // start 记录推荐
                $record_data = [];
                $record_data['poster_type'] = 1;// 超级海报
                $record_data['poster_id'] = $poster_info['poster_id'];
                $record_data['reco_uid'] = $uid;
                $record_data['be_reco_uid'] = $buid;
                $record_data['scan_time'] = time();
                $record_data['shop_id'] = $poster_info['shop_id'];
                $record_data['website_id'] = $poster_info['website_id'];
                $this->savePosterRecord($record_data);
                $this->savePoster(['scan_times' => $poster_info['scan_times'] + 1], $poster_info['poster_id']);
                unset($record_data);
            }

            // end 记录推荐
            // start 发放奖励
            if (!empty($poster_info['poster_award']) && is_array($poster_info['poster_award'])) {
                $coupon_server = $is_coupon ? new Coupon() : '';
                $gift_voucher_service = $is_gift_voucher ? new GiftVoucher() : '';
                $wx_pai_api = new WxPayApi();
                foreach ($poster_info['poster_award'] as $v) {
                    $memberAccount = new MemberAccount();
                    $template_message_award_detail = '';
                    if ($v['award_obj'] == 1) {
                        // 推荐人
                        $award_uid = $uid;
                        $customer_message = $poster_info['reco_notice'];
                        $user_list[$buid]['nick_name'] = $user_list[$buid]['nick_name']?:($user_list[$buid]['user_name']?:$user_list[$buid]['user_tel']);
                        // 谁扫描了你的二维码
                        $customer_message = str_replace('[nickname]', $user_list[$buid]['nick_name'], $customer_message);
                    }
                    if ($v['award_obj'] == 2) {
                        // 被推荐人
                        $award_uid = $buid;
                        $customer_message = $poster_info['follow_notice'];
                        $user_list[$uid]['nick_name'] = $user_list[$uid]['nick_name']?:($user_list[$uid]['user_name']?:$user_list[$uid]['user_tel']);
                        // 你扫描了谁的二维码
                        $customer_message = str_replace('[nickname]', $user_list[$uid]['nick_name'], $customer_message);
                    }
                    if (empty($award_uid)) {
                        return ['code' => -1, 'message' => '奖励对象错误'];
                    }
                    if ($v['point'] > 0) {
                        // 积分
                        $memberAccount->addMemberAccountData(1, $award_uid, 1, $v['point'],
                            33, $poster_info['website_id'], '海报奖励,推荐得积分');
//                        if($v['award_obj'] == 1){
//                            Db::table('')->getLastSql();
//                        }
//                        if($v['award_obj'] == 2){
//                            Db::table('')->getLastSql();exit(444444);
//                        }
                        $customer_message = str_replace('[credit]', (int)$v['point'], $customer_message);
                        $template_message_award_detail .= $v['point'] . '积分 ';
                    } else {
                        $customer_message = str_replace('[credit]', 0, $customer_message);
                    }
                    if ($v['balance'] > 0) {
                        // 余额
                        $memberAccount->addMemberAccountData(2, $award_uid, 1, $v['balance'],
                            29, $poster_info['website_id'], '海报奖励,推荐得余额');
                        $customer_message = str_replace('[money]', (int)$v['balance'], $customer_message);
                        $template_message_award_detail .= $v['balance'] . '余额 ';
                    } else {
                        $customer_message = str_replace('[money]', 0, $customer_message);
                    }
                    if ($v['wchat_red_packet'] > 0) {
                        // 微信红包
                        $scene_id = '';
                        if ($v['wchat_red_packet'] > 200 || $v['wchat_red_packet'] < 1) {
                            // 大于200元或者小于1元 需要场景id
                            $scene_id = 'PRODUCT_5';
                        }
                        $result = $wx_pai_api::sendRedPack($act_name, $v['wchat_red_packet'], 1, $poster_info['website_id'], $remark, $wishing, $user_list[$award_uid]['wx_openid'], $scene_id);
                        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS' && $result['err_code'] == 'SUCCESS') {
                            // 发送成功
                            $customer_message = str_replace('[redpack]', (int)$v['wchat_red_packet'], $customer_message);
                            $template_message_award_detail .= $v['balance'] . '微信红包 ';
                        } else {
                            // 发送失败
                            $customer_message = str_replace('[redpack]', 0, $customer_message);
                            // insert fail record
                            $temp_fail_data = [];
                            $temp_fail_data['open_id'] = $user_list[$award_uid]['wx_openid'];
                            $temp_fail_data['money'] = $v['wchat_red_packet'];
                            $temp_fail_data['num'] = 1;
                            $temp_fail_data['website_id'] = $poster_info['website_id'];
                            $temp_fail_data['act_name'] = $act_name;
                            $temp_fail_data['remark'] = $remark;
                            $temp_fail_data['wishing'] = $wishing;
                            $temp_fail_data['scene_id'] = $scene_id ? : '';
                            $temp_fail_data['fail_reason'] = $result['return_msg'];

                            $fail_record_data[] = $temp_fail_data;
                            unset($temp_fail_data);
                        }
                    } else {
                        $customer_message = str_replace('[redpack]', 0, $customer_message);
                    }
                    if ($v['growth'] > 0) {
                        // 成长值
                        $user_service->updateUserGrowthNum(4, $award_uid, $v['growth'], 0);
                        $customer_message = str_replace('[growth]', (int)$v['growth'], $customer_message);
                        $template_message_award_detail .= $v['growth'] . '成长值 ';
                    } else {
                        $customer_message = str_replace('[growth]', 0, $customer_message);
                    }
                    if (!empty($v['gift_voucher_id']) && $is_gift_voucher && $gift_voucher_service->isGiftVoucherReceive($v['gift_voucher_id'], $award_uid)) {
                        // 礼品券
                        $gift_voucher_service->getUserReceive($award_uid, $v['gift_voucher_id'], 5);
                        $gift_voucher_info = $gift_voucher_service->getGiftVoucherDetail($v['gift_voucher_id']);
                        $customer_message = str_replace('[giftvouchername]', $gift_voucher_info['giftvoucher_name'], $customer_message);
                        $template_message_award_detail .= $gift_voucher_info['giftvoucher_name'] . '礼品券 ';
                    } else {
                        $customer_message = str_replace('[giftvouchername]', '', $customer_message);
                    }
                    if (!empty($v['coupon_type_id']) && $is_coupon && $coupon_server->isCouponTypeReceivable($v['coupon_type_id'], $award_uid)) {
                        // 优惠券
                        $coupon_server->userAchieveCoupon($award_uid, $v['coupon_type_id'], 10);
                        $coupon_type_info = $coupon_type_model::get($v['coupon_type_id']);
                        $customer_message = str_replace('[couponname]', $coupon_type_info['coupon_name'], $customer_message);
                        $template_message_award_detail .= $coupon_type_info['coupon_name'] . '优惠券 ';
                    } else {
                        $customer_message = str_replace('[couponname]', '', $customer_message);
                    }
                    // 发送消息
                    if ($poster_info['notice_type'] == 1 && !empty($poster_info['notice_template_id'])) {
                        // 1:模板消息
                        $array = array(
                            'touser' => $user_list[$award_uid]['wx_openid'],
                            'template_id' => $poster_info['notice_template_id'],
                            'data' => array(
                                'first' => array(
                                    'value' => '海报任务已完成'
                                ),
                                'keyword1' => array(
                                    'value' => '关注海报'
                                ),
                                'keyword2' => array(
                                    'value' => '任务完成'
                                ),
                                'keyword3' => array(
                                    'value' => date('Y-m-d H:i:s')
                                ),
                                'remark' => array(
                                    'value' => '任务奖励为:' . $template_message_award_detail
                                )
                            )
                        );
                        $result = $weixin->templateMessageSend($array, $poster_info['website_id']);
                    } elseif ($poster_info['notice_type'] == 2 && !empty($customer_message)) {
                        // 2：客服消息
                        $result = $weixin->send_message($user_list[$award_uid]['wx_openid'], 'text', $customer_message);
                    }
                    // end 发送消息

                }
            }
            // end 发放奖励
            if (!empty($fail_record_data)){
                // 记录失败的红包发送记录
                $red_pack_fail_record_model->saveAll($fail_record_data);
            }
            $distribution_status = getAddons('distribution', $poster_info['website_id']);
            if($poster_info['is_perm'] == 0){//都是分销商的情况
                if ($poster_info['is_sub']) {
                    // 成为下线
                    $member_data['recommend_id'] = $uid;
                    if ($user_list[$uid]['member_info']['isdistributor'] == 2) {
                        // 推荐者是分销商，被推荐人才能成为分销商
                        $member_data['referee_id'] = $uid;
                    }
                }
            }else{
                // 成为下线
                $member_data['recommend_id'] = $uid;//推荐人，不一定是分销商
                if ($user_list[$uid]['member_info']['isdistributor'] == 2) {//是分销商才能绑定下级
                    // 推荐者是分销商，被推荐人才能成为分销商
                    $member_data['referee_id'] = $uid;
                }
            }
            if ($poster_info['is_distribution'] && $distribution_status && $user_list[$uid]['member_info']['isdistributor'] == 2) {
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

            return ['code' => 1];

        }catch(\Exception $e){
            echo $e->getMessage();exit;
        }
    }
}