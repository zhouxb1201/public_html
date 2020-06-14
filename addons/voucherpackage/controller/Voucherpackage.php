<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\voucherpackage\controller;

use addons\coupontype\model\VslCouponTypeModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\miniprogram\model\WeixinAuthModel;
use addons\shop\model\VslShopModel;
use addons\voucherpackage\Voucherpackage as baseVoucherPackage;
use addons\voucherpackage\service\VoucherPackage as VoucherPackageService;
use data\model\WebSiteModel;
use data\service\AddonsConfig;
use data\service\Member;
use Phinx\Config;

class VoucherPackage extends baseVoucherPackage
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new VoucherPackageService();
    }

    public function voucherPackageList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $condition = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'voucher_package_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        $list = $this->service->getVoucherPackageList($page_index, $page_size, $condition, 'start_time desc', '*');
        //判断pc端、小程序是否开启
        $addons_conf = new AddonsConfig();
        $pc_conf = $addons_conf->getAddonsConfig('pcport', $this->website_id);
        $is_minipro = getAddons('miniprogram', $this->website_id);
        if($is_minipro){
            $weixin_auth = new WeixinAuthModel();
            $new_auth_state = $weixin_auth->getInfo(['website_id' => $this->website_id], 'new_auth_state')['new_auth_state'];
            if(isset($new_auth_state) && $new_auth_state == 0){
                $is_minipro = 1;
            }else{
                $is_minipro = 0;
            }
        }
        $website_mdl = new WebSiteModel();
        //查看移动端的状态
        $wap_status = $website_mdl->getInfo(['website_id' => $this->website_id], 'wap_status')['wap_status'];
        $addon_status['wap_status'] = $wap_status;
        $addon_status['is_pc_use'] = $pc_conf['is_use'];
        $addon_status['is_minipro'] = $is_minipro;
        $list['addon_status'] = $addon_status;
        return $list;
    }

    public function getVoucherPackageInfo()
    {
        $voucher_package_id = input('post.voucher_package_id');
        $voucher_package = $this->service->getVoucherPackageDetail(['voucher_package_id' => $voucher_package_id]);

        return $voucher_package;
    }

    public function addVoucherPackage()
    {
        $input = request()->post();
        $input['start_time'] = strtotime($_POST["start_time"]);
        $input['end_time'] = strtotime($_POST["end_time"]) + (86400 - 1);
        $input['shop_id'] = $this->instance_id;
        $input['website_id'] = $this->website_id;

        $ret_val = $this->service->addVoucherPackage($input);
        if ($ret_val) {
            $this->addUserLog('添加券包', $ret_val);
        }

        return AjaxReturn($ret_val);
    }

    public function updateVoucherPackage()
    {
        $input = request()->post();
        $input['start_time'] = strtotime($input["start_time"]);
        $input['end_time'] = strtotime($input["end_time"]) + (86400 - 1);
        $input['modify_time'] = time();

        $ret_val = $this->service->updateVoucherPackage($input);
        if ($ret_val) {
            $this->addUserLog('修改券包', $ret_val);
        }
        return AjaxReturn($ret_val);
    }

    public function deleteVoucherPackage()
    {
        $voucher_package_id = input('post.voucher_package_id');
        if (empty($voucher_package_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $res = $this->service->deleteVoucherPackage($voucher_package_id);
        if ($res) {
            $this->addUserLog('删除券包', $voucher_package_id);
        }
        return AjaxReturn($res);
    }

    public function voucherPackageSetting()
    {
        $is_addons = input('post.is_addons');
        $result = $this->service->saveVoucherPackageConfig($is_addons);
        if ($result) {
            $this->addUserLog('添加券包设置', $result);
        }
        setAddons('voucherpackage', $this->website_id, $this->instance_id);
        return AjaxReturn($result);
    }

    public function selectModal()
    {
        $type = input('get.type');
        $this->assign('type', $type);
        if ($type == 'coupon_type') {
            $this->assign('title', '优惠券');
            $this->assign('listUrl', __URL(call_user_func('addons_url_' . $this->module, 'coupontype://Coupontype/couponTypeList')));
        } else {
            $this->assign('title', '礼品券');
            $this->assign('listUrl', __URL(call_user_func('addons_url_' . $this->module, 'giftvoucher://Giftvoucher/giftvoucherList')));
        }
        $this->fetch('template/' . $this->module . '/selectModal');
    }

    /**
     * 券包详情
     */
    public function voucherPackage()
    {
        $voucher_package_id = input('voucher_package_id');
        if (empty($voucher_package_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $voucher_package = $this->service->getVoucherPackageDetail(['voucher_package_id' => $voucher_package_id]);
        $return_data = [];
        $return_data['voucher_package_id'] = $voucher_package['voucher_package_id'];
        $return_data['voucher_package_name'] = $voucher_package['voucher_package_name'];
        $return_data['desc'] = $voucher_package['desc'];
        $return_data['start_time'] = $voucher_package['start_time'];
        $return_data['end_time'] = $voucher_package['end_time'];
        $return_data['voucher_package_name'] = $voucher_package['voucher_package_name'];

        return ['code' => 1, 'data' => $return_data];
    }

    /**
     * 领取券包
     * @return \think\response\Json
     * @throws \think\Exception\DbException
     */
    public function userArchiveVoucherPackage()
    {
        $voucher_package_id = input('voucher_package_id');
        $website_model = new WebSiteModel();
        $is_shop = getAddons('shop', $this->website_id);// 店铺应用是否存在
        $shop_model = $is_shop ? new VslShopModel() : '';
        if (empty($voucher_package_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        if (empty($this->uid)) {
            return json(AjaxReturn(LOGIN_EXPIRE));
        }
        $res = $this->service->isVoucherPackageReceivable($voucher_package_id, $this->uid);
        if ($res > 0) {
            $result = $this->service->userAchieveVoucherPackage($this->uid, $voucher_package_id);
            if ($result['code'] == 1) {
                $result['data']['coupon_type_list'] = [];
                $result['data']['gift_voucher_list'] = [];
                // 成功领取后获取优惠券、礼品券的信息
                $temp_shop_info = [];// 临时保存店铺的信息
                if (isset($result['received_coupon_type_id']) && !empty($result['received_coupon_type_id'])) {
                    $coupon_type_model = new VslCouponTypeModel();
                    $coupon_type_list = $coupon_type_model::all(['coupon_type_id' => ['IN', $result['received_coupon_type_id']]]);
                    foreach ($coupon_type_list as $v) {
                        $temp = [];
                        $temp['coupon_type_id'] = $v['coupon_type_id'];
                        $temp['coupon_name'] = $v['coupon_name'];
                        if ($v['range_type'] == 1) {
                            $temp['goods_range'] = '全部商品可用';
                        } else {
                            $temp['goods_range'] = '部分商品可用';
                        }
                        $temp['coupon_genre'] = $v['coupon_genre'];
                        $temp['at_least'] = $v['at_least'];
                        $temp['money'] = $v['money'];
                        $temp['discount'] = $v['discount'];
                        $temp['start_time'] = $v['start_time'];
                        $temp['end_time'] = $v['end_time'];

                        if (!isset($temp_shop_info[$v['shop_id']])) {
                            if ($v['shop_id'] == 0) {
                                // website name
                                $temp_shop_name = $website_model::get(['website_id' => $v['website_id']])['mall_name'];
                            } else {
                                // shop name
                                $temp_shop_name = $shop_model::get(['shop_id' => $v['shop_id']])['shop_name'];
                            }
                            $temp_shop_info[$v['shop_id']]['shop_name'] = $temp_shop_name ?: '';
                        }
                        $temp['shop_name'] = $temp_shop_info[$v['shop_id']]['shop_name'];

                        $result['data']['coupon_type_list'][] = $temp;
                    }
                }
                if (isset($result['received_gift_voucher_id']) && !empty($result['received_gift_voucher_id'])) {
                    $gift_voucher_model = new VslGiftVoucherModel();
                    $gift_voucher_list = $gift_voucher_model::all(['gift_voucher_id' => ['IN', $result['received_gift_voucher_id']]]);
                    foreach ($gift_voucher_list as $v) {
                        $temp = [];
                        $temp['gift_voucher_id'] = $v['gift_voucher_id'];
                        $temp['gift_voucher_name'] = $v['giftvoucher_name'];
                        $temp['start_time'] = $v['start_time'];
                        $temp['end_time'] = $v['end_time'];

                        if (!isset($temp_shop_info[$v['shop_id']])) {
                            if ($v['shop_id'] == 0) {
                                // website name
                                $temp_shop_name = \think\Config::get('mall.default_name');
                            } else {
                                // shop name
                                $temp_shop_name = $shop_model::get(['shop_id' => $v['shop_id']])['shop_name'];
                            }
                            $temp_shop_info[$v['shop_id']]['shop_name'] = $temp_shop_name ?: '';
                        }
                        $temp['shop_name'] = $temp_shop_info[$v['shop_id']]['shop_name'];

                        $result['data']['gift_voucher_list'][] = $temp;
                    }
                }
                unset($result['received_coupon_type_id'], $result['received_gift_voucher_id']);
            }
            return json($result);
        } else if($res == -1){
            return json(['code' => -1,'message' => '已领取']);
        } else{
            return json(['code' => -1,'message' => '领取失败']);
        }
    }
}