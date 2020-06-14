<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/25 0025
 * Time: 15:21
 */

namespace addons\registermarketing\server;

use addons\coupontype\server\Coupon;
use addons\giftvoucher\server\GiftVoucher;
use addons\registermarketing\model\VslRegisterMarketingModel;
use data\service\BaseService;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
use data\service\Member;
use data\model\VslMemberViewModel;
use data\model\VslMemberModel;
use data\model\VslMemberAccountRecordsModel;

class RegisterMarketing extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }

    public function registerMarketingInfo($website_id, array $relations = [])
    {
        $registerMarketing = new VslRegisterMarketingModel();
        return $registerMarketing::get($website_id, $relations);
    }


    /**
     * @param array $input
     * @return int 1 or $e->message()
     */
    public function saveRegisterMarketing(array $input = array())
    {
        $registerMarketingModel = new VslRegisterMarketingModel();
        $registerMarketingModel->startTrans();
        try {
            $with = [];
            if (getAddons('coupontype', $this->website_id)) {
                $with[] = 'coupons';
            }
            if (getAddons('giftvoucher', $this->website_id)) {
                $with[] = 'gift_voucher';
            }
            $registerMarketing = $registerMarketingModel::get($input['website_id'], $with);
            $coupon_type_id_array = $input['coupon_type_id'];
            $gift_voucher_id_array = $input['gift_voucher_id'];
            unset($input['coupon_type_id'], $input['gift_voucher_id']);
            if ($registerMarketing) {
                //update
                $registerMarketingModel->data($input, true)->isUpdate(true)->save();
                if (!empty($registerMarketing->coupons)) {
                    $delete_coupon_type_id_array = [];
                    foreach ($registerMarketing->coupons as $c) {
                        $delete_coupon_type_id_array[] = $c['coupon_type_id'];
                    }
                    //删除现有的优惠券关系
                    $registerMarketing->coupons()->detach($delete_coupon_type_id_array);
                }
                if (!empty($registerMarketing['gift_voucher'])) {
                    $delete_gift_voucher_id_array = [];
                    foreach ($registerMarketing['gift_voucher'] as $c) {
                        $delete_gift_voucher_id_array[] = $c['gift_voucher_id'];
                    }
                    //删除现有的礼品券关系
                    $registerMarketing->gift_voucher()->detach($delete_gift_voucher_id_array);
                }
            } else {
                //insert
                $registerMarketingModel->save($input);
                $registerMarketing = $registerMarketingModel::get($input['website_id']);
            }

            if (!empty($coupon_type_id_array) && is_array($coupon_type_id_array)) {
                //插入新的所有优惠券关系
                $registerMarketing->coupons()->saveAll($coupon_type_id_array);
            }
            if (!empty($gift_voucher_id_array) && is_array($gift_voucher_id_array)) {
                //插入新的所有礼品券关系
                $registerMarketing->gift_voucher()->saveAll($gift_voucher_id_array);
            }

            $registerMarketingModel->commit();
            return 1;
        } catch (\Exception $e) {
            $registerMarketingModel->rollback();
//            var_dump($e->getMessage());
            return 0;
        }
    }

    /**
     * 注册营销设置
     */
    public function setRegSetting($is_use = 0)
    {
        $ConfigService = new AddonsConfigService();
        $info = $ConfigService->getAddonsConfig('registermarketing');
        if (!empty($info)) {
            $data = array(
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $this->addons_config_module->save($data, [
                "website_id" => $this->website_id,
                "addons" => 'registermarketing'
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig('', "注册营销设置", $is_use, "registermarketing");
        }
        return $res;
    }

    /**
     * 获取注册营销设置
     *
     */
    public function getRegSetting($website_id = 0)
    {
        if ($website_id) {
            $id = $website_id;
        } else {
            $id = $this->website_id;
        }
        $info = $this->addons_config_module->getInfo(["website_id" => $id, "addons" => "registermarketing"], 'is_use')['is_use'];
        return $info;
    }

    /**
     * 发放注册营销的奖励
     * @param $uid 用户id
     */
    public function deliveryAward($uid)
    {
        $with = [];
        $coupontype = getAddons('coupontype', $this->website_id);
        $giftvoucher = getAddons('giftvoucher', $this->website_id);
        if ($coupontype) {
            $with[] = 'coupons';
        }
        if ($giftvoucher) {
            $with[] = 'gift_voucher';
        }
        $registerMarketingInfo = $this->registerMarketingInfo($this->website_id, $with);
        //设置了注册营销 && 注册礼包处于开启状态 && 处于有效时间内
        if (!empty($registerMarketingInfo) && (time() >= $registerMarketingInfo['start_time']) && (time() <= $registerMarketingInfo['end_time'])) {
            $member_view = new VslMemberViewModel();
            $member_info = $member_view->getInfo(['uid'=>$uid],'referee_id,default_referee_id,growth_num');
            
            //积分 > 0 送积分
            if ($registerMarketingInfo['point'] > 0) {
                $is_point = 1;
                if($registerMarketingInfo['is_distributor_point']==1){
                    $is_point = ($member_info['referee_id']>0 || $member_info['default_referee_id']>0)?1:0;
                }
                if($is_point==1){
                    $memberAccount = new Member\MemberAccount();
                    $memberAccount->addMemberAccountData(1, $uid, 1, $registerMarketingInfo['point'],7, $registerMarketingInfo['website_id'], '注册营销,注册得积分');
                }
            }
            
            //成长值 > 0 送成长值
            if ($registerMarketingInfo['growth_num'] > 0) {
                $is_growth = 1;
                if($registerMarketingInfo['is_distributor_growth']==1){
                    $is_growth = ($member_info['referee_id']>0 || $member_info['default_referee_id']>0)?1:0;
                }
                if($is_growth==1){
                    $member_model = new VslMemberModel();
                    $member_account_record = new VslMemberAccountRecordsModel();
                    $growthNum = $member_info['growth_num'] + $registerMarketingInfo['growth_num'];
                    $member_model->save(['growth_num' => $growthNum], ['uid' => $uid]);
                    $data = array(
                        'records_no' => getSerialNo(),
                        'account_type' => 4,
                        'uid' => $uid,
                        'sign' => '注册',
                        'number' => $registerMarketingInfo['growth_num'],
                        'from_type' => 7,
                        'data_id' => $registerMarketingInfo['website_id'],
                        'text' => '注册营销会员成长值增加',
                        'create_time' => time(),
                        'website_id' => $registerMarketingInfo['website_id']
                    );
                    $member_account_record->save($data);
                }
            }

            //有设置优惠券 送优惠券
            if (!empty($registerMarketingInfo->coupons) && is_array($registerMarketingInfo->coupons) && $coupontype) {
                $coupon_server = new Coupon();
                foreach ($registerMarketingInfo->coupons as $coupon) {
                    if ($coupon_server->isCouponTypeReceivable($coupon->coupon_type_id, $uid)) {
                        $coupon_server->userAchieveCoupon($uid, $coupon->coupon_type_id, 3);
                    }
                }
            }

            //有设置礼品券 送礼品券
            if (!empty($registerMarketingInfo->gift_voucher) && is_array($registerMarketingInfo->gift_voucher) && $giftvoucher) {
                $gift_voucher_service = new GiftVoucher();
                foreach ($registerMarketingInfo->gift_voucher as $gift_voucher) {
                    if ($gift_voucher_service->isGiftVoucherReceive($gift_voucher->gift_voucher_id, $uid)) {
                        $gift_voucher_service->getUserReceive($uid, $gift_voucher->gift_voucher_id, 4);
                    }
                }
            }
        }
    }

}