<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/25 0025
 * Time: 11:30
 */

namespace addons\voucherpackage\service;

use addons\coupontype\model\VslCouponGoodsModel;
use addons\coupontype\model\VslCouponModel;
use addons\coupontype\model\VslCouponTypeModel;
use addons\coupontype\server\Coupon;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\giftvoucher\server\GiftVoucher;
use addons\registermarketing\model\VslRegisterMarketingCouponTypeModel;

use addons\voucherpackage\model\VslVoucherPackageHistoryModel;
use addons\voucherpackage\model\VslVoucherPackageModel;
use addons\voucherpackage\model\VslVoucherPackageRelationModel;
use data\model\AlbumPictureModel;
use data\model\VslGoodsModel;
use addons\shop\model\VslShopModel;
use data\service\BaseService;

use think\Db;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
use data\service\Member;

class VoucherPackage extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * 获取优惠券列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     * @param string $fields
     *
     * @return array $coupon_type_list
     */
    public function getVoucherPackageList($page_index = 1, $page_size = 0, array $condition = [], $order = 'create_time desc', $fields = '*')
    {
        $coupon_type = new VslVoucherPackageModel();
        $coupon_type_list = $coupon_type->pageQuery($page_index, $page_size, $condition, $order, $fields);

        return $coupon_type_list;
    }

    public function getVoucherPackageDetail(array $condition, array $with = [])
    {
        $voucher_package = new VslVoucherPackageModel();
        $data = $voucher_package::get($condition, $with)->toArray();
        $data['coupon_type_list'] = $data['gift_voucher_list'] = $data['gift_voucher_id_array'] = $data['coupon_type_id_array'] = [];
        if (isset($data['voucher_relation'])) {
            $coupon_type_model = new VslCouponTypeModel();
            $gift_voucher_model = new VslGiftVoucherModel();
            foreach ($data['voucher_relation'] as $k => $v) {
                if ($v['relation_type'] == 'coupon_type') {
                    $data['coupon_type_list'][$v['relation_id']]['id'] = $v['relation_id'];
                    $data['coupon_type_list'][$v['relation_id']]['name'] = $coupon_type_model::get($v['relation_id'])['coupon_name'];
                    $data['coupon_type_list'][$v['relation_id']]['type'] = 'coupon_type';
                    $data['coupon_type_id_array'][] = $v['relation_id'];
                } elseif ($v['relation_type'] == 'gift_voucher') {
                    $data['gift_voucher_list'][$v['relation_id']]['id'] = $v['relation_id'];
                    $data['gift_voucher_list'][$v['relation_id']]['name'] = $gift_voucher_model::get($v['relation_id'])['giftvoucher_name'];
                    $data['gift_voucher_list'][$v['relation_id']]['type'] = 'gift_voucher';
                    $data['gift_voucher_id_array'][] = $v['relation_id'];
                }
            }
            unset($data['voucher_relation']);
        }
        return $data;
    }

    /**
     * @param array $input
     * @return int
     */
    public function addVoucherPackage(array $input)
    {
        $voucher_package_model = new VslVoucherPackageModel();
        $voucher_package_relation_model = new VslVoucherPackageRelationModel();
        $voucher_package_model->startTrans();
        try {
            $gift_voucher_id_array = $input['gift_voucher_id_array'];
            $coupon_type_id_array = $input['coupon_type_id_array'];
            unset($input['gift_voucher_id_array'], $input['coupon_type_id_array']);
            $voucher_package_id = $voucher_package_model->save($input);
            $relation_data = [];
            if (!empty($gift_voucher_id_array)) {
                foreach ($gift_voucher_id_array as $v) {
                    $temp = [];
                    $temp['voucher_package_id'] = $voucher_package_id;
                    $temp['relation_type'] = 'gift_voucher';
                    $temp['relation_id'] = $v;

                    $relation_data[] = $temp;
                }
            }
            if (!empty($coupon_type_id_array)) {
                foreach ($coupon_type_id_array as $v) {
                    $temp = [];
                    $temp['voucher_package_id'] = $voucher_package_id;
                    $temp['relation_type'] = 'coupon_type';
                    $temp['relation_id'] = $v;

                    $relation_data[] = $temp;
                }
            }
            if (!empty($relation_data)) {
                $voucher_package_relation_model->saveAll($relation_data);
            }
            $voucher_package_model->commit();
            return 1;
        } catch (\Exception $e) {
            $voucher_package_model->rollback();
            return $e->getMessage();
        }
    }

    /**
     * @param array $input
     * @return int
     */
    public function updateVoucherPackage(array $input)
    {
        $voucher_package_model = new VslVoucherPackageModel();
        $voucher_package_relation_model = new VslVoucherPackageRelationModel();
        $voucher_package_model->startTrans();
        try {
            $gift_voucher_id_array = $input['gift_voucher_id_array'];
            $coupon_type_id_array = $input['coupon_type_id_array'];
            unset($input['gift_voucher_id_array'], $input['coupon_type_id_array']);
            $voucher_package_model->save($input, [
                'voucher_package_id' => $input['voucher_package_id']
            ]);
            // 更新关系表
            $voucher_package_relation_model->destroy([
                'voucher_package_id' => $input['voucher_package_id']
            ]);
            $relation_data = [];
            if (!empty($gift_voucher_id_array)) {
                foreach ($gift_voucher_id_array as $v) {
                    $temp = [];
                    $temp['voucher_package_id'] = $input['voucher_package_id'];
                    $temp['relation_type'] = 'gift_voucher';
                    $temp['relation_id'] = $v;

                    $relation_data[] = $temp;
                }
            }
            if (!empty($coupon_type_id_array)) {
                foreach ($coupon_type_id_array as $v) {
                    $temp = [];
                    $temp['voucher_package_id'] = $input['voucher_package_id'];
                    $temp['relation_type'] = 'coupon_type';
                    $temp['relation_id'] = $v;

                    $relation_data[] = $temp;
                }
            }
            if (!empty($relation_data)) {
                $voucher_package_relation_model->saveAll($relation_data);
            }
            $voucher_package_model->commit();
            return 1;
        } catch (\Exception $e) {
            $voucher_package_model->rollback();
            return 0;
        }
    }

    /**
     * 删除优惠券
     * @param int|string $voucher_package_id
     *
     * @return int 1
     */
    public function deleteVoucherPackage($voucher_package_id)
    {
        $voucher_package_model = new VslVoucherPackageModel();
        $voucher_package_model->startTrans();
        try {
            $voucher_package_relation_model = new VslVoucherPackageRelationModel();
            $voucher_package_relation_model::destroy(['voucher_package_id' => $voucher_package_id]);
            $voucher_package_history_model = new VslVoucherPackageHistoryModel();
            $voucher_package_history_model::destroy(['voucher_package_id' => $voucher_package_id]);
            $voucher_package_model::destroy(['voucher_package_id' => $voucher_package_id]);
            $voucher_package_model->commit();
            return 1;
        } catch (\Exception $e) {
            $voucher_package_model->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 判读优惠券是否科领取，不可领取时返回0，超过领取时返回-1，可领取返回可领取数目
     *
     * @param int $coupon_type_id
     * @param int $uid
     * @param int $time
     *
     * @return int $rest
     */
    public function isVoucherPackageReceivable($coupon_type_id, $uid, $time = 0)
    {
        $voucher_package_model = new VslVoucherPackageModel();
        if (empty($time)) {
            $time = time();
        }
        $voucher_package_info = $voucher_package_model::get($coupon_type_id, ['voucher_relation', 'history']); 
        //应用是否开启
        if(!getAddons('coupontype', $voucher_package_info['website_id']) && !getAddons('giftvoucher', $voucher_package_info['website_id'])){
            return 0;
        }
        // 不在领取或者使用时间范围内
        if ($time < $voucher_package_info['start_time'] ||
            $time > $voucher_package_info['end_time']) {
            return 0;
        }
        // 已经被领取、使用了全部数目
        $rest = $voucher_package_info['count'] - count($voucher_package_info->history);
        if($voucher_package_info['count']==0)$rest = 10000;//无限领
        if ($rest <= 0)return 0;
        //没有uid 返回该优惠券的剩余数目;有uid 返回该uid用户可领取该优惠券的数目
        if (empty($uid) || $voucher_package_info['max_fetch'] == 0) {
            return $rest;
        } else {
            $u_rest = $voucher_package_info['max_fetch'] - $voucher_package_info->history()->where('uid', $uid)->count();
            if($u_rest <= 0) return -1;
            return ($u_rest > $rest) ? $rest : $u_rest;
        }
    }

    /**
     * 领取券包
     * @param $uid
     * @param $voucher_package_id
     * @return array
     * @throws \think\Exception\DbException
     */
    public function userAchieveVoucherPackage($uid, $voucher_package_id)
    {
        $voucher_package_history_model = new VslVoucherPackageHistoryModel();
        $voucher_package_model = new VslVoucherPackageModel();
        $voucher_package_info = $voucher_package_model::get($voucher_package_id, ['voucher_relation']);
        $is_coupon_type = getAddons('coupontype', $voucher_package_info['website_id']);
        $is_gift_voucher = getAddons('giftvoucher', $voucher_package_info['website_id']);
        $coupon_service = new Coupon();
        $gift_voucher_service = new GiftVoucher();
        $received_coupon_type_id = [];// 通过券包成功领取的优惠券id
        $received_gift_voucher_id = [];// 通过券包成功领取的礼品券id
        if ($voucher_package_info) {

            foreach ($voucher_package_info->voucher_relation as $v) {
                if ($v->relation_type == 'coupon_type' &&
                    $coupon_service->isCouponTypeReceivable($v->relation_id, $uid) &&
                    $is_coupon_type) {
                    $coupon_service->userAchieveCoupon($uid, $v->relation_id, 7);
                    $received_coupon_type_id[] = $v->relation_id;
                    continue;
                }

                if ($v->relation_type == 'gift_voucher' &&
                    $gift_voucher_service->isGiftVoucherReceive(['gift_voucher_id' => $v->relation_id, 'website_id' => $this->website_id]) &&
                    $is_gift_voucher) {
                    $gift_voucher_service->getUserReceive($uid, $v->relation_id, 3);
                    $received_gift_voucher_id[] = $v->relation_id;
                    continue;
                }
            }

            $data = array(
                'uid' => $uid,
                'fetch_time' => time(),
                'voucher_package_id' => $voucher_package_id,
            );
            $voucher_package_history_model->save($data);
            $result = 1;
        } else {
            $result = NO_COUPON;
        }
        return ['code' => $result, 'received_coupon_type_id' => $received_coupon_type_id, 'received_gift_voucher_id' => $received_gift_voucher_id];
    }


    public function saveVoucherPackageConfig($is_addons)
    {
        $config_service = new AddonsConfigService();
        $addons_info = $config_service->getAddonsConfig("voucherpackage");
        if (!empty($addons_info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_addons, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'voucherpackage'
            ]);
        } else {
            $res = $config_service->addAddonsConfig('', '券包设置', $is_addons, 'voucherpackage');
        }
        return $res;
    }
}