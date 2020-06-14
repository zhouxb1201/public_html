<?php

namespace app\admin\controller;

use data\model\CustomTemplateModel;
use data\model\WebSiteModel;
use data\service\Config as WebConfig;
use data\service\GoodsCategory;
use data\service\Order as OrderService;
use data\service\Goods as GoodsService;

/**
 * 网站设置模块控制器
 *
 * @author  www.vslai.com
 *        
 */
class Config extends BaseController {

    protected $realm_ip;
    protected $realm_two_ip;
    protected $http;
    public function __construct() {
        parent::__construct();
        $is_ssl = \think\Request::instance()->isSsl();
        $this->http = "http://";
        if($is_ssl){
            $this->http = 'https://';
            $this->assign('ssl','https');
        }
        $web_info = $this->website->getWebSiteInfo();
        $this->realm_ip = $web_info['realm_ip'];
        $this->assign('realm_ip',$this->realm_ip);
        if($web_info['realm_two_ip']){
            $ip = top_domain($_SERVER['HTTP_HOST']);
            $web_info['realm_two_ip'] = $web_info['realm_two_ip'].'.'.$ip;
            $this->realm_two_ip = $web_info['realm_two_ip'];
            $this->assign('realm_two_ip',$this->realm_two_ip);
            $this->assign('top_ip',$ip);
        }
        if(empty($this->realm_ip)){
            $real_ip = $this->http.$this->realm_two_ip;
        }else{
            $real_ip = $this->http.$this->realm_ip;
        }
        $this->assign('real_ip',$real_ip);
    }

    /**
     * 退货地址列表
     *
     */
    public function returnSetting() {
        $order_service = new OrderService();
        $shop_id = $this->instance_id;
        if (request()->isAjax()) {
            $return_id = request()->post('return_id', 0);
            $consigner = request()->post('consigner', '');
            $mobile = request()->post('mobile', '');
            $province = request()->post('province', '');
            $city = request()->post('city', '');
            $district = request()->post('district', '');
            $address = request()->post('address', '');
            $zip_code = request()->post('zip_code', '');
            $is_default = request()->post('is_default', 0);
            $retval = $order_service->updateShopReturnSet($shop_id,$return_id,$consigner,$mobile,$province,$city,$district,$address,$zip_code,$is_default);
            if ($retval) {
                $this->addUserLog('退货地址设置', $retval);
            }
            return AjaxReturn($retval);
        } else {
            return view($this->style . "System/returnSetting");
        }
    }
    
    /**
     * 商家地址
     */
    public function getShopReturnList() {
        $order_service = new OrderService();
        $list= $order_service->getShopReturnList($this->instance_id, $this->website_id);
        return $list;
    }
    
    /**
     * 商家地址详情
     */
    public function getShopReturn() {
        $order_service = new OrderService();
        $return_id = request()->post('return_id', 0);
        $shop_id = $this->instance_id;
        $website_id = $this->website_id;
        $info = $order_service->getShopReturn($return_id,$shop_id,$website_id);
        return $info;
    }
    
    /**
     * 商家地址
     */
    public function returnDelete() {
        $order_service = new OrderService();
        $shop_id = $this->instance_id;
        $return_id = request()->post('return_id', 0);
        $retval = $order_service->deleteShopReturnSet($shop_id,$return_id);
        if ($retval) {
            $this->addUserLog('系统商家地址删除', $retval);
        }
        return AjaxReturn($retval);
    }

    /**
     * 设置默认自定义模板主页
     */
    public function useCustomTemplate() {
        $config_server = new WebConfig();
        $id = request()->post('id');
        $type = request()->post('type');
        if (empty($id) || empty($type)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $data = $config_server->getCustomTemplateInfo(['id' => $id]);
        if(!$data){
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $templateData = json_decode($data['template_data'],true);
        if(!isset($templateData['items']) || !$templateData['items']){
            return ['code' => -1, 'message' => '空白模板无法使用'];
        }
        $retval = $config_server->useCustomTemplate($id, $type, $this->instance_id, $this->website_id);
        if ($retval) {
            $this->addUserLog('设置默认自定义模板主页', $id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 设置默认商品详情页
     */
    public function setdefaultgoodsdetail() {

        $id = request()->post('id', 0);
        $web_config = new WebConfig();
        $retval = $web_config->setGoodsdetailTemplate($id, $this->instance_id);
        if ($retval) {
            $this->addUserLog('设置默认商品详情页', $id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 设置默认会员中心
     */
    public function setdefaultmembercenter() {

        $id = request()->post('id', 0);
        $web_config = new WebConfig();
        $retval = $web_config->customtemplate_membercenter($id, $this->instance_id);
        if ($retval) {
            $this->addUserLog('设置默认会员中心页', $id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 设置默认分销中心
     */
    public function setdefaultdistribution() {

        $id = request()->post('id', 0);
        $web_config = new WebConfig();
        $retval = $web_config->customtemplate_distribution($id, $this->instance_id);
        if ($retval) {
            $this->addUserLog('设置默认分销中心页', $id);
        }
        return AjaxReturn($retval);
    }

    //装修
    public function customTemplate() {
        $config_server = new WebConfig();
        $website_model = new WebSiteModel();
        $id = request()->get('id', 0); //自定义模板id
        $goods_category = new GoodsCategory();
        $goods_category_list = $goods_category->getFormatGoodsCategoryList();
        if ($id) {
            $custom_template_info = $this->getCustomTeplateInfo($id);
            $template_data = $custom_template_info['template_data'];
            $type = $custom_template_info['type'];
            $template_name = $custom_template_info['template_name'];
            //平台设置的底部信息
            $bar_info = $config_server->getCustomTemplateInfo(['shop_id' => 0, 'website_id' => $this->website_id, 'type' => 7]);
            $tab_bar = $bar_info['template_data'];
            //平台设置的版权信息
            $copyright_info = $config_server->getCustomTemplateInfo(['shop_id' => 0, 'website_id' => $this->website_id, 'type' => 8]);
            $copyright = $copyright_info['template_data'];
        } else {
            $template_data = '';
            $template_name = '';
            $tab_bar = '';
            $type = 1;
            $copyright = '';
        }
        //列表数据

        $condition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ];
        $condition['type'] = ['NOT IN', [7, 8]];
        $list = $config_server->getCustomTemplateList(1, 0, $condition);
        $this->assign('shop_id', $this->instance_id);
        $this->assign('template_list', $list['data']);
        $this->assign('type', $type);
        $this->assign('id', $id);
        $this->assign('goods_category_list', json_encode($goods_category_list));
        $this->assign('template_data', $template_data);
        $this->assign('template_name', $template_name);
        $this->assign('tabbar', $tab_bar);
        $this->assign('copyright', $copyright);
        $this->assign('default_version',$website_model::get($this->website_id,['merchant_version'])['merchant_version']['is_default']);
        //return view($this->style . 'Config/customTemplate');
        return view($this->style . 'Shop/customTemplate');
    }

    /**
     * 获取自定义模板列表
     *
     * @return list
     */
    public function getCustomTeplateInfo($id) {
        $web_config = new WebConfig();
        $info = $web_config->getCustomTemplateInfo([
            'shop_id' => $this->instance_id,
            'id' => $id,
            'website_id' => $this->website_id
        ]);
        return $info;
    }

    public function editTemplateName() {
        $id = request()->post('id', 0); //自定义模板id
        $name = request()->post('name', '');
        $Custom = new CustomTemplateModel();
        $info = $Custom->save(['template_name'=>$name],['id' => $id]);
        return AjaxReturn($info);
    }

    /**
     * 手机端自定义模板
     *
     * @return list
     */
    public function customTemplateList() {
        $web_config = new WebConfig();
        if (request()->isAjax()) {
            $page_index = request()->post('page_index') ?: 1;
            $page_size = request()->post('page_size') ?: PAGESIZE;
            $template_name = request()->post('template_name');
            $template_type = request()->post('template_type');
            $condition = [
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id
            ];
            if ($template_type == 'diy') {
                $condition['type'] = 6;
            } else {
                $condition['type'] = ['NOT IN', [6, 7, 8]];
            }
            if ($template_name) {
                $condition['template_name'] = ['like', "%" . $template_name . "%"];
            }
            $list = $web_config->getCustomTemplateList($page_index, $page_size, $condition, 'modify_time DESC');
            return $list;
        }
        $count = $web_config->getCustomTemplateCount(['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
        if ($count == 0) {
            $web_config->initCustomTemplate($this->website_id, $this->instance_id);
        }
        return view($this->style . 'Shop/customTemplateList');
    }

    /**
     * 系统默认装修页面
     */
    public function getSystemDefaultTemplateList()
    {
        $custom_template_model = new CustomTemplateModel();
        $condition['is_system_default'] = 1;
        $condition['type'] = ['IN', [2, 3, 6]];
        $condition['shop_id'] = 0;
        $condition['website_id'] = 0;
        $custom_template_list = $custom_template_model::all($condition);
        $list = [];
        foreach ($custom_template_list as $v) {
            $temp['id'] = $v['id'];
            $temp['template_name'] = $v['template_name'];
            $temp['type'] = $v['type'];
            $temp['template_logo'] = $v['template_logo'];

            $list[$v['type']][] = $temp;
        }
        return $list;
    }

    /**
     * 新增装修页面
     */
    public function createCustomTemplate()
    {
        $id = request()->post('id');
        $type = request()->post('type');
        if (empty($type)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $web_config = new WebConfig();
        if ($id) {
            $condition['id'] = $id;
            $system_default_template_data = $web_config->getCustomTemplateInfo($condition);
            $template_data = json_decode($system_default_template_data['template_data'], true);
        } else {
            $template_data = '';
        }
        $data['template_name'] = isset($template_data['template_name']) ? $template_data['template_name'] : '新建模板';
        $data['type'] = $type;
        $data['shop_id'] = $this->instance_id;
        $data['website_id'] = $this->website_id;
        $data['create_time'] = time();
        $data['modify_time'] = time();
        $data['template_data'] = json_encode($template_data, JSON_UNESCAPED_UNICODE);
        $id = $web_config->createCustomTemplate($data);
        if ($id) {
            $this->addUserLog('新增装修页面', $data['template_name']);
        }
        return AjaxReturn(1, ['id' => $id]);
    }

    /**
     * 移动端选择装修页面modal
     */
    public function createWapTemplateDialog()
    {
        return view($this->style . 'Shop/createWapTemplate');
    }


    /**
     * 保存装修数据
     */
    public function saveCustomTemplate() {
        $web_config = new WebConfig();
        $template_data_temp = request()->post('template_data/a', '');
        if(!isset($template_data_temp['items']) || !$template_data_temp['items']){
            return ['code' => -1,'message' => '内容为空无法保存'];
        }
        $template_data = json_encode($template_data_temp, JSON_UNESCAPED_UNICODE); // 模板数据
        $id = request()->post('id'); // 模板id
        // 这里把默认goodstype的0改成2,因为和plateform共用一个初始化默认模板
        $temp = json_decode($template_data, true);
        foreach ($temp['items'] as $k => $v){
            if ($temp['items'][$k]['id'] == 'goods') {
                $temp['items'][$k]['params']['goodstype'] = 2;
            }
        }
        $data['template_data'] = json_encode($temp);
        if ($id) {
            $data['modify_time'] = time();
        } else {
            $data['create_time'] = time();
        }
        $retval = $web_config->saveCustomTemplate($data, $id);
        if ($retval) {
            $this->addUserLog('保存装修数据', $id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 删除手机端自定义模板
     */
    public function deleteCustomTemplateById() {
        $id = request()->post('id/a', 0);
        if (!$id) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $web_config = new WebConfig();
        $condition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'id' => ['in', $id]
        ];
        $retval = $web_config->deleteCustomTemplateById($condition);
        if ($retval) {
            $this->addUserLog('删除手机端自定义模板', $retval);
        }
        return AjaxReturn($retval);
    }

    /**
     * 开启关闭自定义模板
     */
    public function setIsEnableCustomTemplate() {
        $web_config = new WebConfig();
        $is_enable = request()->post("is_enable", 0);
        $retval = $web_config->setIsEnableCustomTemplate($this->instance_id, $is_enable);
        if ($retval) {
            $this->addUserLog($is_enable ? '开启' : '关闭' . '手机端自定义模板');
        }
        return AjaxReturn($retval);
    }

    /**
     * 导航栏获取商品链接
     */
    public function getSearchGoods() {
        $search_text = request()->post('search_text', '');
        $condition = array(
            'goods_name' => ['LIKE', '%' . $search_text . '%']
        );
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $goods_service = new GoodsService();
        $list = $goods_service->getSearchGoodsList(1, 0, $condition);
        return $list;
    }

    /**
     * 商品选择
     */
    public function modalGoodsList() {
        if (request()->post('page_index')) {
            $index = request()->post('page_index', 1);
            $search_text = request()->post('search_text');
            $condition['ng.shop_id'] = $this->instance_id;
            if ($search_text) {
                $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
            }

            $condition['ng.website_id'] = $this->website_id;
            $condition['ng.state'] = 1;
            $goods_service = new GoodsService();
            $list = $goods_service->getgoodslist($index, PAGESIZE, $condition);
            $goods_list = [];
            //删除多余的字段
            foreach ($list['data'] as $k => $v) {
                $goods_list[$k]['goods_id'] = $v['goods_id'];
                $goods_list[$k]['goods_name'] = $v['goods_name'];
                $goods_list[$k]['price'] = $v['price'];
                $goods_list[$k]['shop_name'] = $v['shop_name'] ?: '自营店';
                $goods_list[$k]['pic_cover'] = getApiSrc($v['pic_cover']);
                $goods_list[$k]['pic_cover_mid'] = getApiSrc($v['pic_cover_mid']);
                $goods_list[$k]['pic_cover_small'] = getApiSrc($v['pic_cover_small']);
                $goods_list[$k]['pic_cover_micro'] = getApiSrc($v['pic_cover_micro']);
            }
            $list['data'] = $goods_list;
            return $list;
        }
        return view($this->style . 'Shop/goodsDialog');
    }

    /**
     * 链接选择
     */
    public function modalLinkList() {
        $config['coupontype'] = getAddons('coupontype',$this->website_id,$this->instance_id,true);
        $config['microshop'] = getAddons('microshop',$this->website_id,$this->instance_id,true);
        $config['integral'] = getAddons('integral',$this->website_id,$this->instance_id,true);
        $config['channel'] = getAddons('channel',$this->website_id,$this->instance_id,true);
        $config['seckill'] = getAddons('seckill',$this->website_id,$this->instance_id,true);
        $config['presell'] = getAddons('presell',$this->website_id,$this->instance_id,true);
        $config['groupshopping'] = getAddons('groupshopping',$this->website_id,$this->instance_id,true);
        $config['bargain'] = getAddons('bargain',$this->website_id,$this->instance_id,true);
        $config['signin'] = getAddons('signin',$this->website_id,$this->instance_id,true);
        $config['followgift'] = getAddons('followgift',$this->website_id,$this->instance_id,true);
        $config['festivalcare'] = getAddons('festivalcare',$this->website_id,$this->instance_id,true);
        $config['paygift'] = getAddons('paygift',$this->website_id,$this->instance_id,true);
        $config['scratchcard'] = getAddons('scratchcard',$this->website_id,$this->instance_id,true);
        $config['smashegg'] = getAddons('smashegg',$this->website_id,$this->instance_id,true);
        $config['wheelsurf'] = getAddons('smashegg',$this->website_id,$this->instance_id,true);
        $config['qlkefu'] = getAddons('qlkefu',$this->website_id,$this->instance_id,true);
        $config['taskcenter'] = getAddons('taskcenter',$this->website_id,$this->instance_id,true);
        $config['helpcenter'] = getAddons('helpcenter',$this->website_id,0,true);
        if($config['followgift'] || $config['festivalcare'] || $config['paygift'] || $config['scratchcard'] || $config['smashegg'] || $config['wheelsurf']){
            $config['memberprize'] = 1;
        }else{
            $config['memberprize'] = 0;
        }
        $this->assign('config', $config);
        $this->assign('shop_id',$this->instance_id);
        return view($this->style . 'Shop/linksDialog');
    }
    /**
     * 链接选择_小程序
     */
    public function modalLinkListMin() {
        $config['coupontype'] = getAddons('coupontype',$this->website_id,$this->instance_id,true);
        $config['microshop'] = getAddons('microshop',$this->website_id,$this->instance_id,true);
        $config['integral'] = getAddons('integral',$this->website_id,$this->instance_id,true);
        $config['channel'] = getAddons('channel',$this->website_id,$this->instance_id,true);
        $config['seckill'] = getAddons('seckill',$this->website_id,$this->instance_id,true);
        $config['presell'] = getAddons('presell',$this->website_id,$this->instance_id,true);
        $config['groupshopping'] = getAddons('groupshopping',$this->website_id,$this->instance_id,true);
        $config['bargain'] = getAddons('bargain',$this->website_id,$this->instance_id,true);
        $config['signin'] = getAddons('signin',$this->website_id,$this->instance_id,true);
        $config['followgift'] = getAddons('followgift',$this->website_id,$this->instance_id,true);
        $config['festivalcare'] = getAddons('festivalcare',$this->website_id,$this->instance_id,true);
        $config['paygift'] = getAddons('paygift',$this->website_id,$this->instance_id,true);
        $config['scratchcard'] = getAddons('scratchcard',$this->website_id,$this->instance_id,true);
        $config['smashegg'] = getAddons('smashegg',$this->website_id,$this->instance_id,true);
        $config['wheelsurf'] = getAddons('smashegg',$this->website_id,$this->instance_id,true);
        $config['taskcenter'] = getAddons('taskcenter',$this->website_id,$this->instance_id,true);
        $config['liveshopping'] = getAddons('liveshopping',$this->website_id,$this->instance_id,true);
        $config['thingcircle'] = getAddons('thingcircle',$this->website_id,$this->instance_id,true);
        $config['miniprogram'] = getAddons('miniprogram',$this->website_id,$this->instance_id,true);
        if($config['followgift'] || $config['festivalcare'] || $config['paygift'] || $config['scratchcard'] || $config['smashegg'] || $config['wheelsurf']){
            $config['memberprize'] = 1;
        }else{
            $config['memberprize'] = 0;
        }
        $this->assign('config', $config);
        $this->assign('shop_id',$this->instance_id);
        return view($this->style . 'Shop/linksMinDialog');
    }
}
