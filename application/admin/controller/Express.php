<?php

namespace app\admin\controller;

use data\model\VslOrderShippingFeeModel;
use data\model\ProvinceModel;
use data\service\Address as Address;
use data\service\Express as ExpressService;
use data\service\Goods as GoodsService;

/**
 * 物流
 *
 * @author  www.vslai.com
 *
 */
class Express extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 功能说明：运费模板管理-列表分页
     */
    public function freightTemplateList() {
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $co_id = request()->post("co_id", 0);
            $search_text = request()->post('search_text', '');
            if ($co_id) {
                $condition['co_id'] = $co_id;
            }
            if ($search_text) {
                $condition['shipping_fee_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['shop_id'] = $this->instance_id;
            $condition['website_id'] = $this->website_id;

            $shipping_fee_service = new ExpressService();
            $shipping_fee_lists = $shipping_fee_service->getShippingFeeList($page_index, $page_size, $condition, 'is_default desc,create_time desc');
            return $shipping_fee_lists;
        } else {
            $co_id = request()->get('co_id', 0);
            $this->assign('co_id', $co_id);
            return view($this->style . 'System/freightTemplate');
        }
    }

    /**
     * 功能说明：运费模板管理-添加
     */
    public function freightTemplateEdit() {
        $express = new ExpressService();
        if (request()->isAjax()) {
            $data = json_decode($_POST['data'],true);

            if ($data['shipping_fee_id']) {
                //$retval = $express->updateShippingFee($shipping_fee_id, $is_default, $shipping_fee_name, $province_id_array, $city_id_array, $district_id_array, $weight_is_use, $weight_snum, $weight_sprice, $weight_xnum, $weight_xprice, $volume_is_use, $volume_snum, $volume_sprice, $volume_xnum, $volume_xprice, $bynum_is_use, $bynum_snum, $bynum_sprice, $bynum_xnum, $bynum_xprice);
                $shipping_fee_id = $express->updateShippingFeeNew($data);
            } else {
                //$retval = $express->addShippingFee($co_id, $is_default, $shipping_fee_name, $province_id_array, $city_id_array, $district_id_array, $weight_is_use, $weight_snum, $weight_sprice, $weight_xnum, $weight_xprice, $volume_is_use, $volume_snum, $volume_sprice, $volume_xnum, $volume_xprice, $bynum_is_use, $bynum_snum, $bynum_sprice, $bynum_xnum, $bynum_xprice);
                $data['shop_id'] = $this->instance_id;
                $data['website_id'] = $this->website_id;
                $shipping_fee_id = $express->addShippingFeeNew($data);
            }
            if ($data['is_default']) {
                $express->defaultShippingFee($shipping_fee_id);
            }
            $express->saveShippingFeeArea($data['shipping_area'], $shipping_fee_id);
            if ($shipping_fee_id) {
                $this->addUserLog('运费模板管理-添加', $shipping_fee_id);
            }
            return AjaxReturn($shipping_fee_id);
        } else {
            $shipping_fee_id = request()->get('shipping_fee_id', 0); // 运费模板id（用于修改运费模板）
            $co_id = request()->get('co_id', 0);
            $order_shipping_fee_model = new VslOrderShippingFeeModel();
            $province_model = new ProvinceModel();
            $expressCompany = new ExpressService();
            $company_lists = $expressCompany->getExpressCompanyList(1, 0, $this->website_id, $this->instance_id, ['nr.website_id' => $this->website_id, 'nr.shop_id' => $this->instance_id], '');

            if ($shipping_fee_id) {
                //update
                $shipping_fee_detail = $order_shipping_fee_model::get($shipping_fee_id, ['shipping_area'])->toArray();
                if ($shipping_fee_detail) {
                    foreach ($shipping_fee_detail['shipping_area'] as $k => $area) {
                        $province_id_array = $area['province_id_array'] ? explode(',', $area['province_id_array']) : [];
                        $city_id_array = $area['city_id_array'] ? explode(',', $area['city_id_array']) : [];
                        $district_id_array = $area['district_id_array'] ? explode(',', $area['district_id_array']) : [];

                        unset($shipping_fee_detail['shipping_area'][$k]);
                        //转换成id为key的数组
                        $shipping_fee_detail['shipping_area'][$area['shipping_fee_area_id']] = $area;
                        $shipping_fee_detail['shipping_area'][$area['shipping_fee_area_id']]['province_id_array'] = $province_id_array;
                        $shipping_fee_detail['shipping_area'][$area['shipping_fee_area_id']]['city_id_array'] = $city_id_array;
                        $shipping_fee_detail['shipping_area'][$area['shipping_fee_area_id']]['district_id_array'] = $district_id_array;

                        if (!empty($province_id_array)) {
                            $shipping_fee_detail['shipping_area'][$area['shipping_fee_area_id']]['province_name_array'] = [];
                            $province_info = $province_model::all(['province_id' => ['IN', $province_id_array]]);
                            foreach ($province_info as $key => $info) {
                                $shipping_fee_detail['shipping_area'][$area['shipping_fee_area_id']]['province_name_array'][] = $info->province_name;
                            }
                        }
                    }
                }
            } else {
                //new
                //给一个默认的数据让模板文件渲染出来default的内容
                $shipping_fee_detail['co_id'] = $co_id;
                $shipping_fee_detail['shipping_area']['new_default']['shipping_fee_area_id'] = 'new_default';
                $shipping_fee_detail['shipping_area']['new_default']['is_default_area'] = 1;
            }
            $this->assign('shipping_fee_detail', $shipping_fee_detail);

            $areas = ['province' => [], 'city' => [], 'district' => []];
            $data_address = new Address();
            $fields = ['sp.province_id', 'sp.province_name', 'sc.city_id', 'sc.city_name', 'sd.district_id', 'sd.district_name'];
            $area_info = $data_address->getAllAddress([], $fields, 'sd.district_id'); //这个取数据的方式,没有district数据的将取不到数据，例如香港澳门
            //构建id=>name的数组
            foreach ($area_info as $area) {
                if (!in_array($area['province_name'], $areas['province']) && !empty($area['province_id']) && !empty($area['province_name'])) {
                    $areas['province'][$area['province_id']] = $area['province_name'];
                }
                if (empty($areas['city'][$area['province_id']])) {
                    $areas['city'][$area['province_id']][$area['city_id']] = $area['city_name'];
                    //$areas['city'][$area['province_id']][$area['city_id']]['city_name'] = $area['city_name'];
                } elseif (!in_array($area['city_name'], $areas['city'][$area['province_id']]) && !empty($area['province_id']) && !empty($area['city_id']) && !empty($area['city_name'])) {
                    $areas['city'][$area['province_id']][$area['city_id']] = $area['city_name'];
                    //$areas['city'][$area['province_id']][$area['city_id']]['city_name'] = $area['city_name'];
                }
                if (empty($areas['district'][$area['city_id']])) {
                    $areas['district'][$area['city_id']][$area['district_id']] = $area['district_name'];
                    //$areas['district'][$area['city_id']][$area['district_id']]['district_name'] = $area['district_name'];
                } elseif (!in_array($area['district_name'], $areas['district'][$area['city_id']]) && !empty($area['province_id']) && !empty($area['city_id']) && !empty($area['district_id']) && !empty($area['district_name'])) {
                    $areas['district'][$area['city_id']][$area['district_id']] = $area['district_name'];
                    //$areas['district'][$area['city_id']][$area['district_id']]['district_name'] = $area['district_name'];
                }
            }
            $this->assign('co_id', $co_id);
            $goods_service = new GoodsService();
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            $condition['shipping_fee_id'] = $shipping_fee_id;
            $count = $goods_service->freightTemplateCount($condition);
            $this->assign('use_count', $count);
            $this->assign('province_lists',$areas['province']);
            $this->assign('city_lists',$areas['city']);
            $this->assign('district_lists',$areas['district']);
            $this->assign('company_lists', $company_lists);

            return view($this->style . 'System/freightTemplateEdit');
        }
    }

    /**
     * 使用该物流公司
     */
    public function setUse() {
        $co_id = request()->post('co_id');
        $express_server = new ExpressService();
        if (empty($co_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $data['co_id'] = $co_id;
        $data['shop_id'] = $this->instance_id;
        $data['website_id'] = $this->website_id;
        $result = $express_server->setUseExpressCompany($data);
        $express_server->incExpressCompanyUseNum($co_id);
        return AjaxReturn($result);
    }

    /**
     * 禁用该物流公司
     */
    public function setUnused() {
        $co_id = request()->post('co_id');
        $express_server = new ExpressService();
        if (empty($co_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition['co_id'] = $co_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        //检查该公司是否应用在运费模板上
        $list = $express_server->shippingFeeQuery($condition, 'shipping_fee_id');
        if (count($list) > 0) {
            return ['code' => -1, 'message' => '该物流公司已被运费模板使用,不能禁用'];
        }
        $express_server->decExpressCompanyUseNum($co_id);
        $result = $express_server->setUnusedExpressCompany($condition);
        return AjaxReturn($result);
    }

    /**
     * 设置默认运维模板
     */
    public function setDefaultShippingFee() {
        $retval = UPDATA_FAIL;
        $shipping_fee_id = request()->post('shipping_fee_id');
        if ($shipping_fee_id) {
            $express = new ExpressService();
            $retval = $express->defaultShippingFee($shipping_fee_id);
        }
        if ($retval) {
            $this->addUserLog('设置默认运费模板', $shipping_fee_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 运费模板删除
     */
    public function freightTemplateDelete() {
        $shipping_fee_id = request()->post('shipping_fee_id', '');
        $goods_service = new GoodsService();
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['shipping_fee_id'] = $shipping_fee_id;
        $count = $goods_service->freightTemplateCount($condition);
        if ($count > 0){
            return ['code'=>-1,'message'=>'运费模板已在商品、商品回收站中使用，不能删除'];
        }
        $express = new ExpressService();
        $retval = $express->shippingFeeDelete($shipping_fee_id);
        if ($retval) {
            $this->addUserLog('运费模板删除', $shipping_fee_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 功能：物流公司
     */
    public function expressCompany() {
        $expressCompany = new ExpressService();
        if (request()->isAjax()) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text', '');
            $order = 'IFNULL(nr.shop_id ,- 1) DESC, use_num DESC';
            $condition = [];
//            $condition['nr.shop_id'] = $this->instance_id;
//            $condition['nr.website_id'] = $this->website_id;
            if ($search_text != '') {
                $condition['nec.company_name|nec.express_no'] = array(
                    'like',
                    '%' . $search_text . '%'
                );
            }
            $retval = $expressCompany->getExpressCompanyList($page_index, $page_size, $this->website_id, $this->instance_id, $condition, $order);
            return $retval;
        }
        $this->assign('shop_id', $this->instance_id);
        $this->assign('website_id', $this->website_id);
        return view($this->style . 'System/expressCompany');
    }

}
