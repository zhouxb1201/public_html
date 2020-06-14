<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/13 0013
 * Time: 9:44
 */

namespace addons\delivery\service;

use addons\delivery\model\VslDeliveryTemplateModel;
use addons\delivery\model\VslFormExpressCompanyModel;
use addons\delivery\model\VslFormTemplateModel;
use addons\delivery\model\VslSenderTemplateModel;
use data\extend\Kdniao;
use data\model\CityModel;
use data\model\ConfigModel;
use data\model\DistrictModel;
use data\model\ProvinceModel;
use data\model\VslExpressCompanyShopRelationModel;
use data\model\VslOrderExpressCompanyModel;
use data\service\BaseService;
use data\service\Order;
use think\Db;

class delivery extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function formTemplateList($page_index = 1, $page_size = 0, array $condition = [], $order = '', $field = '*')
    {
        $form_template_model = new VslFormTemplateModel();
        $form_express_company = new VslFormExpressCompanyModel();
//        $list = $form_template_model::all(function ($query) use ($condition, $page_index, $page_size, $order, $field,$form_template_model) {
//            $query->where($condition)->field($field)->order($order)->limit(($page_index - 1) * $page_size, $page_size);
//            $query->form_template_model->pageQuery($page_index, $page_size, $condition, $order, $field);
//        }, $with);
        $data = $form_template_model->pageQuery($page_index, $page_size, $condition, $order, $field);
        foreach ($data['data'] as &$v) {
            $v['company_name'] = $form_express_company::get($v['form_express_company_id'])['company_name'];
        }
        unset($v);
        return $data;
    }

    public function senderTemplateList($page_index = 1, $page_size = 0, array $condition = [], $order = '', $field = '*')
    {
        $sender_template_model = new VslSenderTemplateModel();
        $data = $sender_template_model->pageQuery($page_index, $page_size, $condition, $order, $field);
        $province_model = new ProvinceModel();
        $city_model = new CityModel();
        $district_model = new DistrictModel();
        foreach ($data['data'] as &$v) {
            $v['province_name'] = $province_model::get($v['province_id'])['province_name'] ?: '';
            $v['city_name'] = $city_model::get($v['city_id'])['city_name'] ?: '';
            $v['district_name'] = $district_model::get($v['district_id'])['district_name'] ?: '';
        }
        unset($v);
        return $data;
    }

    public function deliveryTemplateList($page_index = 1, $page_size = 0, array $condition = [], $order = '', $field = '*')
    {
        $delivery_template_model = new VslDeliveryTemplateModel();
        $data = $delivery_template_model->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $data;
    }

    public function expressTemplateList($page_index = 1, $page_size = 0, array $condition = [], $order = '', $field = '*')
    {
        $express_template_model = new VslExpressCompanyShopRelationModel();
        $express_company_model = new VslOrderExpressCompanyModel();
        $data = $express_template_model->pageQuery($page_index, $page_size, $condition, $order, $field);
        foreach ($data['data'] as &$v) {
            $v['express_company_name'] = $express_company_model::get($v['co_id'])['company_name'];
        }
        unset($v);
        return $data;
    }

    public function saveFormTemplate(array $data, array $condition = [])
    {
        $form_template_model = new VslFormTemplateModel();
        return $form_template_model->save($data, $condition);
    }

    public function saveExpressTemplate(array $data, array $condition = [])
    {
        $express_template_model = new VslExpressCompanyShopRelationModel();
        return $express_template_model->save($data, $condition);
    }

    public function saveDeliveryTemplate(array $data, array $condition = [])
    {
        $delivery_template_model = new VslDeliveryTemplateModel();
        return $delivery_template_model->save($data, $condition);
    }

    public function saveSenderTemplate(array $data, array $condition = [])
    {
        $sender_template_model = new VslSenderTemplateModel();
        return $sender_template_model->save($data, $condition);
    }

    public function deleteFormTemplate(array $condition)
    {
        $form_template_model = new VslFormTemplateModel();
        return $form_template_model::destroy($condition);
    }

    public function deleteSenderTemplate(array $condition)
    {
        $sender_template_model = new VslSenderTemplateModel();
        return $sender_template_model::destroy($condition);
    }

    public function deleteDeliveryTemplate(array $condition)
    {
        $delivery_template_model = new VslDeliveryTemplateModel();
        return $delivery_template_model::destroy($condition);
    }

    public function formExpressCompanyList(array $condition = [])
    {
        $form_express_company_model = new VslFormExpressCompanyModel();
        $list = $form_express_company_model::all($condition);
        $return_data = [];
        foreach ($list as $v) {
            $temp_company = [];
            $temp_company['form_express_company_id'] = $v['form_express_company_id'];
            $temp_company['company_name'] = $v['company_name'];

            $return_data['company_list'][] = $temp_company;
            foreach ($v->form_style()->select() as $f) {
                $temp_style = [];
                $temp_style['form_style_id'] = $f['form_style_id'];
                $temp_style['style_name'] = $f['style_name'];

                $return_data['style_list'][$v['form_express_company_id']][] = $temp_style;
            }
        }
        return $return_data;
    }

    public function formTemplateDetail(array $condition, array $with = [])
    {
        $form_template_model = new VslFormTemplateModel();
        return $form_template_model::get($condition, $with);
    }

    public function senderTemplateDetail(array $condition, array $with = [])
    {
        $sender_template_model = new VslSenderTemplateModel();
        return $sender_template_model::get($condition, $with);
    }

    public function expressTemplateDetail(array $condition, array $with = [])
    {
        $express_company_relation_model = new VslExpressCompanyShopRelationModel();
        $return_data = $express_company_relation_model::get($condition, $with);
        $return_data['template_data'] = json_decode(htmlspecialchars_decode($return_data['template_data']), true);
        return $return_data;
    }

    public function deliveryTemplateDetail(array $condition)
    {
        $delivery_template_model = new VslDeliveryTemplateModel();
        $return_data = $delivery_template_model::get($condition);
        $return_data['template_data'] = json_decode(htmlspecialchars_decode($return_data['template_data']), true);
        return $return_data;
    }

    public function formPrint($order_goods_id_array, $receiver_info = '')
    {
        $order_service = new Order();
        $result = []; // 快递鸟接口返回结果
        $kdn = new Kdniao($this->website_id, $this->instance_id, 'form');
        $form_condition['website_id'] = $this->website_id;
        $form_condition['shop_id'] = $this->instance_id;
        $form_condition['vsl_form_template_model.is_default'] = 1;
        $with = ['form_express_company', 'form_style'];
        $form_template_data = $this->formTemplateDetail($form_condition, $with);
        unset($form_condition, $with);
        if (empty($form_template_data)) {
            return ['code' => -1, 'message' => '缺少电子面单模板数据'];
        }
        $sender_condition['vsl_sender_template.website_id'] = $this->website_id;
        $sender_condition['vsl_sender_template.shop_id'] = $this->instance_id;
        $sender_condition['vsl_sender_template.is_default'] = 1;
        $with = ['province', 'city', 'district'];
        $sender_template_data = $this->senderTemplateDetail($sender_condition, $with);
        unset($sender_condition, $with);
        if (empty($sender_template_data)) {
            return ['code' => -1, 'message' => '缺少发货人模板数据'];
        }
        $list = $order_service->printOrderList(['order_goods_id' => ['IN', $order_goods_id_array]], ['order']);
        foreach ($list as $order) {
            $form_data = [];
            $form_data['ShipperCode'] = $form_template_data->form_express_company->shipper_code;// 快递公司code
            $form_data['OrderCode'] = $order['order_no'];//订单编号
            $form_data['PayType'] = $form_template_data->pay_type;//结算方式
            $form_data['ExpType'] = 1;//快递类型：1-标准快件
            if ($form_template_data->form_express_company->need_customer_name){
                $form_data['CustomerName'] = $form_template_data->client_account;
            }
            if ($form_template_data->form_express_company->need_customer_pwd){
                $form_data['CustomerPwd'] = $form_template_data->client_pwd;
            }
            if ($form_template_data->form_express_company->need_send_site){
                $form_data['SendSite'] = $form_template_data->send_site;
            }
            if ($form_template_data->form_express_company->need_month_code){
                $form_data['MonthCode'] = $form_template_data->monthly_code;
            }
            if ($form_template_data->form_express_company->need_send_staff){
                $form_data['SendStaff'] = '';
            }
            if ($form_template_data->form_express_company->need_logistics_code){
                $form_data['LogisticCode'] = '';
            }

            $sender = [];
            $sender['Name'] = $sender_template_data->sender;
            $sender['Mobile'] = $sender_template_data->mobile;
            $sender['ProvinceName'] = $sender_template_data->province->province_name;
            $sender['CityName'] = $sender_template_data->city->city_name;
            $sender['ExpAreaName'] = $sender_template_data->district->district_name;
            $sender['Address'] = $sender_template_data->address;
            $sender['PostCode'] = $sender_template_data->zip_code;

            // $order->receiver_name,批量打印收货人信息;$receiver_info['name'],单个打印收货人信息
            $receiver = [];
            $receiver['Name'] = !empty($receiver_info['name']) ? $receiver_info['name'] : $order['receiver_name'];
            $receiver['Mobile'] = !empty($receiver_info['mobile']) ? $receiver_info['mobile'] : $order['receiver_mobile'];
            $receiver['PostCode'] = !empty($receiver_info['zip']) ? $receiver_info['zip'] : ($order['receiver_zip']?:000000);
            $receiver['ProvinceName'] = !empty($receiver_info['province_name']) ? $receiver_info['province_name'] : $order['receiver_province_name'];
            $receiver['CityName'] = !empty($receiver_info['city_name']) ? $receiver_info['city_name'] : $order['receiver_city_name'];
            $receiver['ExpAreaName'] = !empty($receiver_info['district_name']) ? $receiver_info['district_name'] : $order['receiver_district_name'];
            $receiver['Address'] = !empty($receiver_info['address']) ? $receiver_info['address'] : $order['receiver_address'];

            $commodity = [];
            // 只打印第一个商品名称
            $commodity[]['GoodsName'] = reset($order['goods_list'])['goods_name'];
            foreach ($order['goods_list'] as $goods) {
//                $commodity[]['GoodsName'] = $goods['short_name'] ?: $goods['goods_name'];
                $result[$order['order_id']]['order_goods_id_array'][] = $goods['order_goods_id'];
            }
            $form_data['Sender'] = $sender;
            $form_data['Receiver'] = $receiver;
            $form_data['Commodity'] = $commodity;
            $form_data['Quantity'] = 1;//包裹数
            $form_data['IsReturnPrintTemplate'] = 1;//返回电子面单
            $form_data['IsNotice'] = $form_template_data->is_notice ? 0 : 1;//是否通知快递员上门取件 本系统，0：不通知，1：通知，快递鸟相反

            if (!$form_template_data->form_style->is_default) {
                // 不是默认的电子面单需要传面单大小
                $form_data['TemplateSize'] = $form_template_data->form_style->template_size;
            }
            $kdn_result = $kdn->form($form_data);
            $result[$order['order_id']]['result'] = $kdn_result;
            if ($form_template_data->auto_delivery && $kdn_result['Success'] && $kdn_result['Order']['LogisticCode']) {
                // 自动发货 && 返回物流单号
                $delivery_result = $order_service->orderDelivery($order['order_id'], implode(',', $result[$order['order_id']]['order_goods_id_array']), '', 1, '', $kdn_result['Order']['LogisticCode']);
                $result[$order['order_id']]['auto_delivery_result'] = $delivery_result;
            }
        }

        return $result;
    }
}