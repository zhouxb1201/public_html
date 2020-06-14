<?php

namespace data\service;

/**
 * 物流
 */

use data\model\VslExpressCompanyShopRelationModel;
use data\model\VslOrderShippingFeeAreaModel;
use data\model\VslOrderShippingFeeModel;
use data\service\BaseService as BaseService;
use data\model\VslOrderExpressCompanyModel;
use think\Log;

class Express extends BaseService
{

    /*
     * (non-PHPdoc)
     * @see \data\api\IExpress::getShippingFeeList()
     */
    public function getShippingFeeList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $vsl_order_shipping_fee = new VslOrderShippingFeeModel();

        $list = $vsl_order_shipping_fee->pageQuery($page_index, $page_size, $condition, $order, '*');
        //var_dump(Db::table('')->getLastSql());

        $vsl_order_shipping_fee_area = new VslOrderShippingFeeAreaModel();
        foreach ($list['data'] as $k => $v) {
            $fee_area_info = $vsl_order_shipping_fee_area::get(['shipping_fee_id' => $v['shipping_fee_id'], 'is_default_area' => 1]);
            if ($fee_area_info) {
                $list['data'][$k]['main_level_num'] = $fee_area_info['main_level_num'];
                $list['data'][$k]['main_level_fee'] = $fee_area_info['main_level_fee'];
                $list['data'][$k]['extra_level_num'] = $fee_area_info['extra_level_num'];
                $list['data'][$k]['per_extra_level_fee'] = $fee_area_info['per_extra_level_fee'];
            } else {
                $list['data'][$k]['main_level_num'] = 0;
                $list['data'][$k]['main_level_fee'] = 0;
                $list['data'][$k]['extra_level_num'] = 0;
                $list['data'][$k]['per_extra_level_fee'] = 0;
            }

        }

        return $list;
    }

    public function addShippingFeeNew(array $data)
    {
        $order_shipping_model = new VslOrderShippingFeeModel();
        $order_shipping_model->startTrans();
        try {
            $data = array(
                'shipping_fee_name' => $data['shipping_fee_name'],
                'co_id' => $data['co_id'],
                'calculate_type' => $data['calculate_type'],
                'is_default' => $data['is_default'],
                'shop_id' => $data['shop_id'],
                'website_id' => $data['website_id'],
                'create_time' => time()
            );
            $shipping_fee_id = $order_shipping_model->save($data);
            //$this->dealWithExpressCompany($co_id);
            $order_shipping_model->commit();
            return $shipping_fee_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $order_shipping_model->rollback();
            Log::write("检测错误" . $e->getMessage());
            return $e->getMessage();
        }
        return -1;
    }

    public function updateShippingFeeNew(array $data)
    {
        $order_shipping_model = new VslOrderShippingFeeModel();
        $order_shipping_model->startTrans();
        try {
            $shipping_fee_data = array(
                'shipping_fee_name' => $data['shipping_fee_name'],
                'co_id' => $data['co_id'],
                'is_default' => $data['is_default'],
                'is_enabled' => $data['is_enabled'],
                'calculate_type' => $data['calculate_type'],
                'update_time' => time()
            );
            $order_shipping_model->save($shipping_fee_data, ['shipping_fee_id' => $data['shipping_fee_id']]);
            //$shipping_fee_info = $order_shipping_model->getInfo(['shipping_fee_id' => $shipping_fee_id], 'co_id');
            //$this->dealWithExpressCompany($shipping_fee_info['co_id']);
            $order_shipping_model->commit();
            return $data['shipping_fee_id'];
        } catch (\Exception $e) {
            recordErrorLog($e);
            $order_shipping_model->rollback();
            return $e->getMessage();
        }
        return -1;
    }

    public function saveShippingFeeArea(array $data, $shipping_fee_id)
    {
        $shipping_fee_area_model = new VslOrderShippingFeeAreaModel();
        $shipping_fee_area_id_post = array_keys($data);
        $shipping_fee_area_db = $shipping_fee_area_model::all(['shipping_fee_id' => $shipping_fee_id]);
        $shipping_fee_area_delete_id_array = [];
        foreach ($shipping_fee_area_db as $k => $v) {
            if (!in_array($v['shipping_fee_area_id'], $shipping_fee_area_id_post)) {
                $shipping_fee_area_delete_id_array[] = $v['shipping_fee_area_id'];
            }
        }
        unset($shipping_fee_area_db);
        $shipping_fee_area_model->startTrans();
        try {
            foreach ($data as $shipping_fee_area_id => $v) {
                $area_data = [];
                $i = 0;
                if (strpos($shipping_fee_area_id, 'new') === false) {
                    $area_data['is_default_area'] = $v['is_default_area'];
                    if (!$v['is_default_area']) {
                        $area_data['province_id_array'] = implode(',', $v['province_id_array']);
                        $area_data['city_id_array'] = implode(',', $v['city_id_array']);
                        $area_data['district_id_array'] = implode(',', $v['district_id_array']);
                    }
                    $area_data['main_level_num'] = $v['main_level_num'];
                    $area_data['main_level_fee'] = $v['main_level_fee'];
                    $area_data['extra_level_num'] = $v['extra_level_num'];
                    $area_data['per_extra_level_fee'] = $v['per_extra_level_fee'];
                    $shipping_fee_area_model = new VslOrderShippingFeeAreaModel();
                    $shipping_fee_area_model->save($area_data, ['shipping_fee_area_id' => $v['shipping_fee_area_id']]);
                } else {
                    //$shipping_fee_area_model = new VslOrderShippingFeeAreaModel();
                    $area_data_all[$i]['shipping_fee_id'] = $shipping_fee_id;
                    $area_data_all[$i]['is_default_area'] = $v['is_default_area'];
                    if (!$v['is_default_area']) {
                        $area_data_all[$i]['province_id_array'] = implode(',', $v['province_id_array']);
                        $area_data_all[$i]['city_id_array'] = implode(',', $v['city_id_array']);
                        $area_data_all[$i]['district_id_array'] = implode(',', $v['district_id_array']);
                    }
                    $area_data_all[$i]['main_level_num'] = $v['main_level_num'];
                    $area_data_all[$i]['main_level_fee'] = $v['main_level_fee'];
                    $area_data_all[$i]['extra_level_num'] = $v['extra_level_num'];
                    $area_data_all[$i]['per_extra_level_fee'] = $v['per_extra_level_fee'];
                    $i++;
                    //$shipping_fee_area_model->save($area_data);
                }
                if (!empty($area_data_all)) {
                    $shipping_fee_area_model->saveAll($area_data_all);
                }
            }

            if (!empty($shipping_fee_area_delete_id_array) && is_array($shipping_fee_area_delete_id_array)) {
                $shipping_fee_area_model::destroy($shipping_fee_area_delete_id_array);
            }
            $shipping_fee_area_model->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $shipping_fee_area_model->rollback();
            return $e->getMessage();
        }
        return -1;
    }


    /*
     * (non-PHPdoc)
     * @see \data\api\IExpress::shippingFeeDelete()
     */
    public function shippingFeeDelete($shipping_fee_id)
    {
        $order_shipping_fee = new VslOrderShippingFeeModel();
        $order_shipping_fee_area_model = new VslOrderShippingFeeAreaModel();
        $condition = [
            'shop_id' => $this->instance_id,
            'shipping_fee_id' => [['in', $shipping_fee_id]]
        ];
        $area_condition = ['shipping_fee_id' => [['in', $shipping_fee_id]]];

        $order_shipping_return = $order_shipping_fee::destroy($condition);
        $order_shipping_fee_area_model::destroy($area_condition);
        /*    if(is_int($shipping_fee_id))
           {
               $shipping_fee_array = $shipping_fee_id;
           }else{
               $shipping_fee_array = explode(',', $shipping_fee_id);
           }

           if(is_array($shipping_fee_array))
           {
               $info = $order_shipping_fee->getInfo(['shipping_fee_id' => $shipping_fee_array[0]],'co_id');
               $this->dealWithExpressCompany($info['co_id']);
           }else{
               $this->dealWithExpressCompany($shipping_fee_id);
           } */

        if ($order_shipping_return > 0) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IExpress::shippingFeeQuery()
     */
    public function shippingFeeQuery($where = "", $fields = "*")
    {
        $order_shipping_fee = new VslOrderShippingFeeModel();
        return $order_shipping_fee->getQuery($where, $fields, 'is_default desc');
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IExpress::getExpressCompanyList()
     */
    public function getExpressCompanyList($page_index = 1, $page_size = 0, $website_id, $shop_id, $condition = [], $order = '')
    {
        $vsl_express_company = new VslOrderExpressCompanyModel();
        //$list = $vsl_express_company->pageQuery($page_index, $page_size, $condition, $order, '*');

        $viewObj = $vsl_express_company->alias('nec')
            ->join('vsl_express_company_shop_relation nr', 'nec.co_id=nr.co_id AND nr.website_id=' . $website_id . ' AND nr.shop_id=' . $shop_id, 'LEFT')
            ->field('nec.*, nr.id, nr.shop_id, nr.website_id');
        $queryList = $vsl_express_company->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);

        //var_dump(Db::table('')->getLastSql());
        $queryCount = $vsl_express_company->alias('nec')
            ->join('vsl_express_company_shop_relation nr', 'nec.co_id=nr.co_id AND nr.website_id=' . $website_id . ' AND nr.shop_id=' . $shop_id, 'LEFT')
            ->field('nec.co_id')
            ->where($condition)
            ->count('nec.co_id');
        //var_dump(Db::table('')->getLastSql());
        $list = $vsl_express_company->setReturnList($queryList, $queryCount, $page_size);

        return $list;
    }

    /**
     *
     */

    /**
     * 增加物流公司启用数目
     * @param int|string $co_id
     * @return void
     */
    public function incExpressCompanyUseNum($co_id)
    {
        $express_company = new VslOrderExpressCompanyModel();
        $express_company->where(['co_id' => $co_id])->setInc('use_num');
    }

    /**
     * 减去物流公司启用数目
     * @param int|string $co_id
     * retunr void
     */
    public function decExpressCompanyUseNum($co_id)
    {
        $express_company = new VslOrderExpressCompanyModel();
        $express_company->where(['co_id' => $co_id])->setDec('use_num');
    }

    /**
     * 店铺启用物流公司
     * @param array $data
     * @return int $id|$e->getCode()
     */
    public function setUseExpressCompany(array $data)
    {
        $express_company_shop_relation_model = new VslExpressCompanyShopRelationModel();
        $express_company_shop_relation_model->startTrans();
        try {
            $id = $express_company_shop_relation_model->save($data);
            $express_company_shop_relation_model->commit();
            return $id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $express_company_shop_relation_model->rollback();
            return $e->getCode();
        }
    }

    /**
     * 店铺禁用物流公司
     * @param array $condition
     * @return int 1|$e->getCode()
     */
    public function setUnusedExpressCompany(array $condition)
    {
        $express_company_shop_relation_model = new VslExpressCompanyShopRelationModel();
        $express_company_shop_relation_model->startTrans();
        try {
            $express_company_shop_relation_model->get($condition)->delete();
            $express_company_shop_relation_model->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $express_company_shop_relation_model->rollback();
            return $e->getCode();
        }
    }

    /**
     * 把别的改为未默认,把当前设置为默认
     */
    public function defaultExpressCompany()
    {
        $vsl_express_company = new VslOrderExpressCompanyModel();
        $data = array(
            'is_default' => 0
        );
        $vsl_express_company->save($data, [
            'shop_id' => $this->instance_id
        ]);
    }

    public function defaultShippingFee($default_shipping_fee_id)
    {
        $shipping_fee_model = new VslOrderShippingFeeModel();
        $shipping_fee_model->startTrans();
        try {
            $shipping_fee_model->save(['is_default' => 0], ['shipping_fee_id' => ['NEQ', $default_shipping_fee_id], 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
            $shipping_fee_model->save(['is_default' => 1], ['shipping_fee_id' => $default_shipping_fee_id]);
            $shipping_fee_model->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            //var_dump($e->getMessage());
            $shipping_fee_model->rollback();
            return UPDATA_FAIL;
        }
    }


    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IExpress::updateExpressCompany()
     */
    public function updateExpressCompany($co_id, $shopId, $company_name, $express_logo, $express_no, $is_enabled, $image, $phone, $orders, $is_default)
    {
        $vsl_express_company = new VslOrderExpressCompanyModel();
        if ($is_default == 1) {
            $this->defaultExpressCompany();
        }
        $data = array(
            'shop_id' => $shopId,
            'company_name' => $company_name,
            'express_logo' => $express_logo,
            'express_no' => $express_no,
            'is_enabled' => $is_enabled,
            'image' => $image,
            'phone' => $phone,
            'orders' => $orders,
            'is_default' => $is_default
        );
        $res = $vsl_express_company->save($data, [
            'co_id' => $co_id
        ]);
        return $res;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IExpress::expressCompanyDetail()
     */
    public function expressCompanyDetail($co_id)
    {
        $vsl_express_company = new VslOrderExpressCompanyModel();
        return $vsl_express_company->get($co_id);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IExpress::expressCompanyDelete()
     */
    public function expressCompanyDelete($co_id)
    {
        $vsl_express_company = new VslOrderExpressCompanyModel();
        $conditon = array(
            'shop_id' => $this->instance_id,
            'co_id' => array(
                in,
                $co_id
            )
        );
        $vsl_express_company_return = $vsl_express_company->destroy($conditon);
        if ($vsl_express_company_return > 0) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IExpress::expressCompanyQuery()
     */
    public function expressCompanyQuery($where = "", $field = "*")
    {
        $vsl_express_company = new VslOrderExpressCompanyModel();
        return $vsl_express_company->where($where)
            ->field($field)
            ->select();
    }

    /**
     *
     * 根据物流公司id查询是否有默认地区
     *
     * {@inheritdoc}
     *
     * @see \data\api\IExpress::isHasExpressCompanyDefaultTemplate()
     */
    public function isHasExpressCompanyDefaultTemplate($co_id)
    {
        $vsl_order_shipping_fee = new VslOrderShippingFeeModel();
        $list = $vsl_order_shipping_fee->getQuery([
            'co_id' => $co_id
        ], 'is_default', '');
        $is_default = 1; // 是否有默认地区 1,可以添加默认地区：0，不可以添加默认地区
        foreach ($list as $v) {
            if ($v['is_default']) {
                $is_default = 0;
                break;
            }
        }
        return $is_default;
    }

    /**
     * 获取物流公司的省市id组，排除默认地区、以及当前编辑的运费模板省市id组
     * @see \data\api\IExpress::getExpressCompanyProvincesAndCitiesById()
     */
    public function getExpressCompanyProvincesAndCitiesById($co_id, $current_province_id_array, $current_city_id_array, $current_district_id_array)
    {
        $curr_province_id_array = []; // 省id组
        $curr_city_id_array = []; // 市id组
        $curr_district_id_array = []; // 区县id组

        // 编辑运费模板时的省id组排除
        if (!empty($current_province_id_array)) {
            if (is_array($current_province_id_array)) {
                array_push($curr_province_id_array, $current_province_id_array);
            } else {
                $curr_province_id_array = explode(',', $current_province_id_array);
            }
        }

        // 编辑运费模板时的市id组排除
        if (!empty($current_city_id_array)) {
            if (is_array($current_city_id_array)) {
                array_push($curr_city_id_array, $current_city_id_array);
            } else {
                $curr_city_id_array = explode(',', $current_city_id_array);
            }
        }

        // 编辑运费模板时的区县id组排除
        if (!empty($current_district_id_array)) {
            if (is_array($current_district_id_array)) {
                array_push($curr_district_id_array, $current_district_id_array);
            } else {
                $curr_district_id_array = explode(',', $current_district_id_array);
            }
        }

        $vsl_order_shipping_fee = new VslOrderShippingFeeModel();
        $list = $vsl_order_shipping_fee->getQuery([
            'co_id' => $co_id,
            'is_default' => 0
        ], 'province_id_array,city_id_array,district_id_array', '');

        // 1.把当前公司的所有省市id进行组拼
        $province_id_array = [];
        $city_id_array = [];
        $district_id_array = [];

        $res_list['province_id_array'] = [];
        $res_list['city_id_array'] = [];
        $res_list['district_id_array'] = [];

        foreach ($list as $k => $v) {

            if (!strstr($v['province_id_array'], ',')) {
                array_push($province_id_array, $v['province_id_array']);
            } else {
                $temp_province_array = explode(",", $v['province_id_array']);
                foreach ($temp_province_array as $temp_province_id) {
                    array_push($province_id_array, $temp_province_id);
                }
            }

            if (!strstr($v['city_id_array'], ',')) {
                array_push($city_id_array, $v['city_id_array']);
            } else {
                $temp_city_array = explode(",", $v['city_id_array']);
                foreach ($temp_city_array as $temp_city_id) {
                    array_push($city_id_array, $temp_city_id);
                }
            }

            if (!strstr($v['district_id_array'], ',')) {
                array_push($district_id_array, $v['district_id_array']);
            } else {
                $temp_district_array = explode(",", $v['district_id_array']);
                foreach ($temp_district_array as $temp_district_id) {
                    array_push($district_id_array, $temp_district_id);
                }
            }
        }

        // 2.排除当前编辑用到的省id组
        if (count($province_id_array)) {
            foreach ($province_id_array as $province_id) {
                $flag = true;
                foreach ($curr_province_id_array as $temp_province_id) {

                    if ($province_id == $temp_province_id) {
                        $flag = false;
                    }
                }
                if ($flag) {
                    array_push($res_list['province_id_array'], $province_id);
                }
            }
        }

        // 3.排除当前编辑用到的市id组
        if (count($city_id_array)) {
            foreach ($city_id_array as $city_id) {
                $flag = true;
                foreach ($curr_city_id_array as $temp_city_id) {

                    if ($city_id == $temp_city_id) {
                        $flag = false;
                    }
                }
                if ($flag) {
                    array_push($res_list['city_id_array'], $city_id);
                }
            }
        }

        // 4.排除当前编辑用到的区县id组
        if (count($district_id_array)) {
            foreach ($district_id_array as $district_id) {
                $flag = true;
                foreach ($curr_district_id_array as $temp_district_id) {

                    if ($district_id == $temp_district_id) {
                        $flag = false;
                    }
                }
                if ($flag) {
                    array_push($res_list['district_id_array'], $district_id);
                }
            }
        }

        return $res_list;
    }
}