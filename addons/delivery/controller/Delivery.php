<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/12 0012
 * Time: 11:44
 */

namespace addons\delivery\controller;

use addons\delivery\Delivery as baseDelivery;
use data\model\ConfigModel;
use addons\delivery\service\Delivery as deliveryService;
use data\model\VslExpressCompanyShopRelationModel;
use data\model\VslOrderExpressCompanyModel;
use data\service\Address;
use data\service\Order as orderService;
use think\Cookie;
use think\Db;

class Delivery extends baseDelivery
{
    private $baseDelivery;

    public function __construct()
    {
        parent::__construct();
        $this->baseDelivery = new deliveryService();
    }

    public function printSetting()
    {
        try {
            $post_data = request()->post();
            $config_model = new ConfigModel();
            $info = $config_model::get(['website_id' => $this->website_id, 'instance_id' => $this->instance_id, 'key' => 'DELIVERY_ASSISTANT']);
            if (!empty($info)) {
                $res = $config_model->save(
                    [
                        'modify_time' => time(),
                        'value' => json_encode($post_data, JSON_UNESCAPED_UNICODE)
                    ],
                    [
                        'website_id' => $this->website_id,
                        'instance_id' => $this->instance_id,
                        'key' => 'DELIVERY_ASSISTANT'
                    ]
                );
            } else {
                $data['is_use'] = 1;
                $data['value'] = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                $data['desc'] = '发货助手设置';
                $data['create_time'] = time();
                $data['key'] = 'DELIVERY_ASSISTANT';
                $data['website_id'] = $this->website_id;
                $data['instance_id'] = $this->instance_id;
                $res = $config_model->save($data);
            }
            if($res){
                $this->addUserLog('保存打印设置', $res);
            }
            setAddons('delivery', $this->website_id, $this->instance_id);
            return ['code' => $res, 'message' => '修改成功'];
        } catch (\Exception $e) {
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }

    public function formList()
    {
        $page_index = request()->post('page_index') ?: 1;
        $page_size = request()->post('page_size', PAGESIZE);
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        if (request()->post('search_text')) {
            $condition['template_name'] = ['LIKE', '%' . request()->post('search_text') . '%'];
        }
        $data = $this->baseDelivery->formTemplateList($page_index, $page_size, $condition, 'create_time DESC');
        return $data;
    }

    public function deliveryList()
    {
        $page_index = request()->post('page_index') ?: 1;
        $page_size = request()->post('page_size', PAGESIZE);
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        if (request()->post('search_text')) {
            $condition['template_name'] = ['LIKE', '%' . request()->post('search_text') . '%'];
        }
        $data = $this->baseDelivery->deliveryTemplateList($page_index, $page_size, $condition, 'create_time DESC');
        return $data;
    }

    public function expressList()
    {
        $page_index = request()->post('page_index') ?: 1;
        $page_size = request()->post('page_size', PAGESIZE);
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        if (request()->post('search_text')) {
            $condition['template_name'] = ['LIKE', '%' . request()->post('search_text') . '%'];
        }
        $data = $this->baseDelivery->expressTemplateList($page_index, $page_size, $condition, 'create_time DESC');
        return $data;
    }

    public function senderList()
    {
        $page_index = request()->post('page_index') ?: 1;
        $page_size = request()->post('page_size', PAGESIZE);
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        if (request()->post('search_text')) {
            $condition['template_name'] = ['LIKE', '%' . request()->post('search_text') . '%'];
        }
        $data = $this->baseDelivery->senderTemplateList($page_index, $page_size, $condition, 'create_time DESC');
        return $data;
    }

    public function deleteFormTemplate()
    {
        $form_template_id = request()->post('form_template_id');
        if (empty($form_template_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition['form_template_id'] = $form_template_id;
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $res = $this->baseDelivery->deleteFormTemplate($condition);
        if ($res) {
            $this->addUserLog('删除电子面单', $res);
        }
        return $res;

    }

    public function deleteSenderTemplate()
    {
        $sender_template_id = request()->post('sender_template_id');
        if (empty($sender_template_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition['sender_template_id'] = $sender_template_id;
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $res = $this->baseDelivery->deleteSenderTemplate($condition);
        if ($res) {
            $this->addUserLog('删除发货人模板', $res);
        }
        return $res;
    }

    public function deleteDeliveryTemplate()
    {
        $delivery_template_id = request()->post('delivery_template_id');
        if (empty($delivery_template_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition['delivery_template_id'] = $delivery_template_id;
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $res = $this->baseDelivery->deleteDeliveryTemplate($condition);
        if ($res) {
            $this->addUserLog('删除发货单模板', $res);
        }
        return $res;
    }

    public function saveFormTemplate()
    {
        $data = request()->post();
        if ($data['form_template_id']) {
            $condition['form_template_id'] = $data['form_template_id'];
            unset($data['form_template_id']);
            $data['modify_time'] = time();
        } else {
            $condition = [];
            $data['create_time'] = time();
            $data['website_id'] = $this->website_id;
            $data['shop_id'] = $this->instance_id;

        }
        if ($data['is_default']) {
            $condition_default['website_id'] = $this->website_id;
            $condition_default['shop_id'] = $this->instance_id;
            $condition_default['is_default'] = 1;
            $this->baseDelivery->saveFormTemplate(['is_default' => 0], $condition_default);
        }
        $result = $this->baseDelivery->saveFormTemplate($data, $condition);
        if ($result) {
            $this->addUserLog('保存电子面单模板', $result);
        }
        return AjaxReturn($result);
    }

    public function saveExpressTemplate()
    {
        $id = request()->post('id');
        $template_data = request()->post('template_data/a');
        if (empty($id) || empty($template_data)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $data['create_time'] = time();
        $data['modify_time'] = time();
        $data['template_data'] = json_encode($template_data, true);
        $result = $this->baseDelivery->saveExpressTemplate($data, ['id' => $id]);
        if ($result) {
            $this->addUserLog('保存物流单模板', $result);
        }
        return AjaxReturn($result);
    }

    public function saveDeliveryTemplate()
    {
        $delivery_template_id = request()->post('delivery_template_id');
        $template_data = request()->post('template_data/a');
        $template_name = request()->post('template_name');
        if (empty($template_data) || empty($template_name)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition = [];
        if ($delivery_template_id) {
            $condition['delivery_template_id'] = $delivery_template_id;
            $data['modify_time'] = time();
        } else {
            $data['create_time'] = time();
            $data['shop_id'] = $this->instance_id;
            $data['website_id'] = $this->website_id;
        }
        $data['template_data'] = json_encode($template_data, true);
        $data['template_name'] = $template_name;
        $result = $this->baseDelivery->saveDeliveryTemplate($data, $condition);
        if ($result) {
            $this->addUserLog('保存发货单模板', $result);
        }
        return AjaxReturn($result);
    }

    public function saveSenderTemplate()
    {
        $data = request()->post();
        if ($data['sender_template_id']) {
            $condition['sender_template_id'] = $data['sender_template_id'];
            unset($data['sender_template_id']);
            $data['modify_time'] = time();
        } else {
            $condition = [];
            $data['create_time'] = time();
            $data['website_id'] = $this->website_id;
            $data['shop_id'] = $this->instance_id;

        }
        if ($data['is_default']) {
            $condition_default['website_id'] = $this->website_id;
            $condition_default['shop_id'] = $this->instance_id;
            $condition_default['is_default'] = 1;
            $this->baseDelivery->saveSenderTemplate(['is_default' => 0], $condition_default);
        }
        $result = $this->baseDelivery->saveSenderTemplate($data, $condition);
        if ($result) {
            $this->addUserLog('保存发货人模板', $result);
        }
        return AjaxReturn($result);
    }

    public function defaultForm()
    {
        $form_template_id = request()->post('form_template_id');
        if (empty($form_template_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['is_default'] = 1;
        $this->baseDelivery->saveFormTemplate(['is_default' => 0], $condition);
        $result = $this->baseDelivery->saveFormTemplate(['is_default' => 1], ['form_template_id' => $form_template_id]);
        if ($result) {
            $this->addUserLog('设置默认电子面单模板', $result);
        }
        return AjaxReturn($result);
    }

    public function defaultExpress()
    {
        $id = request()->post('id');
        if (empty($id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['is_default'] = 1;
        $this->baseDelivery->saveExpressTemplate(['is_default' => 0], $condition);
        $result = $this->baseDelivery->saveExpressTemplate(['is_default' => 1], ['id' => $id]);
        if ($result) {
            $this->addUserLog('设置默认快递单模板', $result);
        }
        return AjaxReturn($result);
    }

    public function defaultDelivery()
    {
        $delivery_template_id = request()->post('delivery_template_id');
        if (empty($delivery_template_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['is_default'] = 1;
        $this->baseDelivery->saveDeliveryTemplate(['is_default' => 0], $condition);
        $result = $this->baseDelivery->saveDeliveryTemplate(['is_default' => 1], ['delivery_template_id' => $delivery_template_id]);
        if ($result) {
            $this->addUserLog('设置默认发货单模板', $result);
        }
        return AjaxReturn($result);
    }

    public function defaultSender()
    {
        $sender_template_id = request()->post('sender_template_id');
        if (empty($sender_template_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['is_default'] = 1;
        $this->baseDelivery->saveSenderTemplate(['is_default' => 0], $condition);
        $result = $this->baseDelivery->saveSenderTemplate(['is_default' => 1], ['sender_template_id' => $sender_template_id]);
        if ($result) {
            $this->addUserLog('设置默认发货人模板', $result);
        }
        return AjaxReturn($result);
    }

    public function formExpressCompanyList()
    {
        return $this->baseDelivery->formExpressCompanyList();
    }

    public function formTemplateDetail()
    {
        $condition['form_template_id'] = request()->post('id');
        return $this->baseDelivery->formTemplateDetail($condition);
    }

    public function expressTemplateDetail()
    {
        $model = new VslExpressCompanyShopRelationModel();
        if (request()->post('id')) {
            $condition['id'] = request()->post('id');
        }
        if (request()->post('is_default')) {
            $condition[$model->table . '.website_id'] = $this->website_id;
            $condition[$model->table . '.shop_id'] = $this->instance_id;
            $condition[$model->table . '.is_default'] = 1;
        }
        return $this->baseDelivery->expressTemplateDetail($condition, ['express_company']);
    }

    public function deliveryTemplateDetail()
    {
        if (request()->post('id')) {
            $condition['delivery_template_id'] = request()->post('id');
        }
        if (request()->post('is_default')) {
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            $condition['is_default'] = 1;
        }

        return $this->baseDelivery->deliveryTemplateDetail($condition);
    }

    public function senderTemplateDetail()
    {
        $with = [];
        if (request()->post('id')) {
            $condition['sender_template_id'] = request()->post('id');
        }
        if (request()->post('is_default')) {
            $condition['vsl_sender_template.website_id'] = $this->website_id;
            $condition['vsl_sender_template.shop_id'] = $this->instance_id;
            $condition['vsl_sender_template.is_default'] = 1;
            $with = ['province', 'city', 'district'];
        }

        return $this->baseDelivery->senderTemplateDetail($condition, $with);
    }

    public function formPrint()
    {
        $order_goods_id_array = request()->post('order_goods_id_array/a');
        $receiver_info = request()->post('receiver_info/a');
        if (empty($order_goods_id_array)) {
            return ['code' => -1, 'message' => '请选择至少一项订单商品,'];
        }
        $result = $this->baseDelivery->formPrint($order_goods_id_array, $receiver_info);
        return $result;
    }

    public function area()
    {
        $area_service = new Address();
        $fields = ['sp.province_id', 'sp.province_name', 'sc.city_id', 'sc.city_name', 'sd.district_id', 'sd.district_name'];
        return $area_service->allArea([], $fields, 'sd.district_id');
    }

    /**
     * 发货助手一键发货
     */
    public function orderDeliveryModal()
    {
        if (request()->post('data')) {
            $order_goods_id_string = Cookie::get('order_goods_id_string');
            Cookie::delete('order_goods_id_string');
            $order_service = new orderService();
            $order_list = $order_service->deliveryOrderList(['order_goods_id' => ['IN', $order_goods_id_string]], ['order.order_goods_express']);
            // 使用默认快递公司
            $express_template_model = new VslExpressCompanyShopRelationModel();
            $exp_template_info = $express_template_model->getInfo(
                [
                    'website_id' => $this->website_id,
                    'shop_id' => $this->instance_id,
                    'is_default' => 1
                ]
            );
            $list['default_company_name'] = '';
            if ($exp_template_info) {
                $express_company_model = new VslOrderExpressCompanyModel();
                $list['default_company_name'] = $express_company_model::get($exp_template_info['co_id'])['company_name'];
            }
            $list['list'] = $order_list;
            return $list;
        }
        $this->assign('orderDeliveryModal', __URL(call_user_func('addons_url_' . $this->module, 'delivery://delivery/orderDeliveryModal')));
        return $this->fetch('/template/' . $this->module . '/orderDelivery');
    }

    public function orderDelivery()
    {
        $list = request()->post('list/a');
        $order_service = new orderService();
        $result = $order_service->ordersDelivery($list);
        if ($result) {
            $this->addUserLog('批量发货', $result);
        }
        return AjaxReturn($result);
    }

}