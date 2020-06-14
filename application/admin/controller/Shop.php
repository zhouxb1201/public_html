<?php

namespace app\admin\controller;

use data\service\Address;
use addons\shop\service\Shop as ShopService;

/**
 * 店铺设置控制器
 *
 * @author  www.vslai.com
 *        
 */
class Shop extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 店铺基础设置
     */
    public function shopConfig() {
        $shop = new ShopService();
        if (request()->isAjax()) {
            $shop_id = $this->instance_id;
            $shop_logo = request()->post('shop_logo', '');
            $shop_banner = request()->post('shop_banner', '');
            $shop_avatar = request()->post('shop_avatar', '');
            $shop_qq = request()->post('shop_qq', '');
            $shop_name = trim(request()->post('shop_name', ''));
            $shop_ww = request()->post('shop_ww', '');
            $shop_phone = request()->post('shop_phone', '');
            $shop_keywords = request()->post('shop_keywords', '');
            $shop_intro = request()->post('shop_intro', '');
            $shop_description = request()->post('shop_description', '');
            $group_id = request()->post('group_id', '');
            $retval = $shop->updateShopConfigByshop($shop_id, $shop_logo, $shop_banner, $shop_avatar, '', $shop_qq, $shop_ww, $shop_phone, $shop_keywords, $shop_description, $shop_intro, $shop_name, $group_id);
            if ($retval) {
                $this->addUserLog('店铺基础设置', $shop_id . '-' . $shop_name);
            }
            return AjaxReturn($retval);
        }
        $shop_info = $shop->getShopDetail($this->instance_id);
        $group_list = $shop->getShopGroup(1, 0, ['website_id' => $this->website_id]);
        $this->assign('group_list', $group_list['data']);
        $this->assign('shop_info', $shop_info);
        return view($this->style . "Shop/shopConfig");
    }

    /**
     * 店铺公司信息设置
     */
    public function companyConfig() {
        $shop = new ShopService();
        $shopApplyInfo['shop_id'] = $this->instance_id;
        $shopApplyInfo['company_name'] = request()->post('company_name', '');
        $shopApplyInfo['company_province_id'] = request()->post('company_province_id', 0);
        $shopApplyInfo['company_city_id'] = request()->post('company_city_id', 0);
        $shopApplyInfo['company_district_id'] = request()->post('company_district_id', 0);
        $shopApplyInfo['company_address_detail'] = request()->post('company_address_detail', '');
        $shopApplyInfo['company_phone'] = request()->post('company_phone', '');
        $shopApplyInfo['company_type'] = request()->post('company_type', '');
        $shopApplyInfo['company_employee_count'] = request()->post('company_employee_count', '');
        $shopApplyInfo['company_registered_capital'] = request()->post('company_registered_capital', '');
        $shopApplyInfo['contacts_name'] = request()->post('contacts_name', '');
        $shopApplyInfo['contacts_phone'] = request()->post('contacts_phone', '');
        $shopApplyInfo['contacts_email'] = request()->post('contacts_email', '');
        $shopApplyInfo['contacts_card_no'] = request()->post('contacts_card_no', '');
        $shopApplyInfo['contacts_card_electronic_1'] = request()->post('contacts_card_electronic_1', '');
        $shopApplyInfo['contacts_card_electronic_2'] = request()->post('contacts_card_electronic_2', '');
        $shopApplyInfo['contacts_card_electronic_3'] = request()->post('contacts_card_electronic_3', '');
        $shopApplyInfo['business_licence_number'] = request()->post('business_licence_number', '');
        $shopApplyInfo['business_sphere'] = request()->post('business_sphere', '');
        $shopApplyInfo['business_licence_number_electronic'] = request()->post('business_licence_number_electronic', '');
        $retval = $shop->updateCompanyConfigByshop($shopApplyInfo);
        if ($retval) {
            $this->addUserLog('店铺公司信息设置', $shopApplyInfo['shop_id']);
        }
        return AjaxReturn($retval);
    }

    /**
     * 获取省列表
     */
    public function getProvince() {
        $address = new Address();
        $province_list = $address->getProvinceList();
        return $province_list;
    }

    /**
     * 获取城市列表
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:, array>
     */
    public function getCity() {
        $address = new Address();
        $province_id = request()->post('province_id', 0);
        $city_list = $address->getCityList($province_id);
        return $city_list;
    }

    /**
     * 获取区域地址
     */
    public function getDistrict() {
        $address = new Address();
        $city_id = request()->post('city_id', 0);
        $district_list = $address->getDistrictList($city_id);
        return $district_list;
    }
    /**
     * 链接选择Pc
     */
    public function modalLinkListPc() {
        $config['pcport'] = getAddons('pcport',$this->website_id, $this->instance_id, true);
        $this->assign('pcCustomTemplateListUrl', __URL(call_user_func('addons_url_' . $this->module, 'pcport://Pcport/pccustomtemplatelist')));
        $this->assign('config', $config);
        $this->assign('shop_id',$this->instance_id);
        return view($this->style . 'Shop/linksPcDialog');
    }
}
