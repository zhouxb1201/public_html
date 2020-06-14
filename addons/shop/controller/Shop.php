<?php

namespace addons\shop\controller;

use addons\shop\Shop as baseShop;
use data\model\AlbumPictureModel;
use data\model\VslGoodsViewModel;
use data\model\VslMemberFavoritesModel;
use data\model\WebSiteModel;
use data\service\Address;
use addons\shop\service\Shop as ShopService;
use data\service\User;
use data\service\Order as OrderService;
use data\service\Goods as GoodsService;
use data\service\Config as configService;
use data\service\WebSite;
use think\Exception;
use data\service\Member;
use think\Session;


/**
 * 店铺设置控制器
 *
 * @author  www.vslai.com
 *
 */
class Shop extends baseShop
{

    public function __construct()
    {
        parent::__construct();
//        $this->website_id = request()->post('website_id', 0);
    }

    /**
     * 店铺列表
     */
    public function shopList()
    {
        $index = request()->post('page_index', 1);
        $search_text = request()->post('search_text', '');
        $shop_type = request()->post('shop_type', '');
        $status = request()->post('status', '');
        $condition['shop_name'] = array('like', '%' . $search_text . '%');
        $condition['shop_type'] = $shop_type;
        $condition['website_id'] = $this->website_id;

        $condition = array_filter($condition);
        if ($status != '') {
            $condition['shop_state'] = $status;
        }

        //var_dump($condition);
        $shop = new ShopService();
        $list = $shop->getShopList($index, PAGESIZE, $condition, 'shop_recommend desc,shop_sort');
        foreach ($list['data'] as $k => $v) {
            $username = new User();
            $user_name = $username->getUserInfoByUid($uid = $v['uid']);
            $list['data'][$k]['user_tel'] = $user_name['user_tel'];
        }
        return $list;
    }

    /**
     * 店铺搜索-接口
     */
    public function shopSearch()
    {
        try {
            $shop_id = request()->post('shop_id');
            $search_text = request()->post('search_text');
            $page_index = request()->post('page_index',1);
            $page_size = request()->post('page_size',PAGESIZE);
            $order = request()->post('order');
            $sort = request()->post('sort') ?: 'DESC';
            $shop_group_id = request()->post('shop_group_id');

            if ($shop_id) {
                $condition['ns.shop_id'] = $shop_id;
            }
            $condition['ns.shop_state'] = 1;
            $condition['ns.website_id'] = $this->website_id;
            if ($search_text != '') {
                $condition['ns.shop_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            if ($shop_group_id) {
                $condition['ns.shop_group_id'] = $shop_group_id;
            }
            $order_sort = 'ns.shop_recommend DESC,ns.shop_sort ASC';
            if (!empty($order) && !empty($sort)) {
                if ($order == 'sale_num') {
                    $order_sort = 'SUM(ng.sales)' . ' ' . $sort;
                } else {
                    $order == 'shop_credit' ? $order = 'comprehensive' :
                    $order_sort = 'ns.' . $order . ' ' . $sort;
                }
            }
            $shop = new ShopService();
            $goods_view_model = new VslGoodsViewModel();
            $field = 'ns.id, ns.shop_id, ns.shop_name, ns.shop_group_id,ns.shop_collect, ns.shop_desccredit, ns.shop_servicecredit, ns.shop_deliverycredit,ns.comprehensive, ns.shop_logo';
            $list = $shop->ShopList($page_index, $page_size, $condition, $order_sort, $field, 'ns.shop_id');
            $goods_fields = 'ng.goods_id, sap.pic_cover, ngs.price as goods_price,ngs.market_price as market_price';
            $group = 'ng.goods_id';
            $order_sort = 'ng.sort desc,ng.sales desc';
            unset($condition);
            foreach ($list['data'] as &$v) {
                $v['goods_list'] = [];
                $condition = [
                    'ng.website_id' => $this->website_id,
                    'ng.shop_id' => $v['shop_id'],
                    'ng.state' => 1,
                    'ng.goods_id' => ['>', 0]
                ];
                // 获取该用户的权限
                if($this->uid) {
                    $userService = new User();
                    $userLevle = $userService->getUserLevelAndGroupLevel($this->uid);// code | <0 错误; 1系统会员; 2;分销商; 3会员
                    if (!empty($userLevle)) {
                    $sql1 = '';
                    $sql2 = '(';
                    // 会员权限
                    if ($userLevle['user_level']) {
                        $u_id = $userLevle['user_level'];
                        $sql1 .= "instr(CONCAT( ',', vgd.browse_auth_u, ',' ), ',".$u_id.",' ) OR ";
                        $sql2 .= "vgd.browse_auth_u IS NULL OR vgd.browse_auth_u = '' ";
                    }
                    // 分销商权限
                    if ($userLevle['distributor_level']) {
                        $d_id = $userLevle['distributor_level'];
                        $sql1 .= "instr(CONCAT( ',', vgd.browse_auth_d, ',' ), ',".$d_id.",' ) OR ";
                            $sql2 .= " OR vgd.browse_auth_d IS NULL OR vgd.browse_auth_d = '' ";
                    }
                    // 标签权限
                    if ($userLevle['member_group']) {
                        $g_ids = explode(',',$userLevle['member_group']);
                        foreach ($g_ids as $g_id) {
                            $sql1 .= "instr(CONCAT( ',', vgd.browse_auth_s, ',' ), ',".$g_id.",' ) OR ";
                                $sql2 .= " OR vgd.browse_auth_s IS NULL OR vgd.browse_auth_s = '' ";
                    }
                    } else {
                        $sql1 .= " ";
                    }
                    $sql2 .= " )";
                    $condition[] = ['exp', $sql1 . $sql2];
                    }
                }
                $goods_list = $goods_view_model->wapGoods(1, 4, $condition , $goods_fields, $order_sort, $group);
                foreach ($goods_list['data'] as $goods){
                    $temp_goods = [];
                    $temp_goods['goods_id'] = $goods['goods_id'];
                    $temp_goods['logo'] = $goods['pic_cover'] ? getApiSrc($goods['pic_cover']) : '';
                    $temp_goods['price'] = $goods['goods_price'];

                    $v['goods_list'][] = $temp_goods;
                }
            }
            unset($v);
            return json(['code' => 1, 'message' => '成功获取', 'data' => ['shop_list' => $list['data'], 'total_count' => $list['total_count'], 'page_count' => $list['page_count']]]);
        } catch (\Exception $e) {
            return json(['code' => -1, 'message' => '系统出错']);
        }
    }

    /**
     * 店铺主页-接口
     */
    public function shopInfo()
    {
        try {
            $shop_id = request()->post('shop_id');
            if ($shop_id == '') {
                return AjaxReturn(LACK_OF_PARAMETER);
            }

            $shop_service = new ShopService();
            $info = $shop_service->getShopDetail($shop_id);

            if ($info['base_info']['shop_state'] != 1){
                return json(['code' => -1, 'message' => '店铺已关闭']);
            }
            $data['shop_name'] = $info['base_info']['shop_name'];
            $data['shop_logo'] = $info['base_info']['shop_logo_img'];
            $data['fans_number'] = $info['base_info']['shop_collect'] ?: 0;
            $data['is_collection'] = $this->uid ? (VslMemberFavoritesModel::get(['fav_type' => 'shop', 'shop_id' => $shop_id, 'uid' => $this->uid]) ? true : false) : false;
            if ($shop_id == 0) {
                $website_service = new WebSite();
                $website = $website_service->getWebSiteInfo($this->website_id);
                //$data['shop_name'] = $website['mall_name'];
                $data['shop_logo'] = $website['logo'];
            }
            $data['evaluate'] = $info['base_info']['shop_evaluate'];//店铺评分
            $data['has_store'] = $info['base_info']['has_store'];//店铺评分
            $data['shop_logo'] = __IMG( $data['shop_logo']); 
            return json(['code' => 1, 'message' => '成功获取', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => -1, 'message' => '系统出错']);
        }
    }

    /**
     * 修改店铺排序
     */
    public function updateShopList()
    {
        $shop_id = isset($_POST['shop_id']) ? $_POST['shop_id'] : '';
        $shop_sort = isset($_POST['shop_sort']) ? $_POST['shop_sort'] : '';
        $shop = new ShopService();
        $retval = $shop->updateShopSort($shop_id, $shop_sort);
        if($retval){
            $this->addUserLog('修改店铺排序', $shop_id);
        }
        return AjaxReturn($retval);
    }

    public function setStatus()
    {
        $shop_id = request()->post('shop_id', '');
        $type = request()->post('type', '1');
        $shop = new ShopService();
        $retval = $shop->setStatus($shop_id, $type);
        return AjaxReturn($retval);
    }
    
    public function shopApplyModal(){
        $apply_id = request()->get('id',0);
        $shop = new ShopService();
        $result=$shop->getShopApplyDetail($apply_id);
        $this->assign('result',$result);
        $this->fetch('template/platform/shopApplyModal');
    }
    /**
     * 设置店铺为推荐
     */
    public function setRecomment()
    {
        $shop_id = isset($_POST['shop_id']) ? $_POST['shop_id'] : '';
        $shop_recommend = isset($_POST['shop_recommend']) ? $_POST['shop_recommend'] : '';
        $shop = new ShopService();
        $retval = $shop->setRecomment($shop_id, $shop_recommend);
        if($retval){
            $this->addUserLog('设置店铺为推荐', $shop_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 店铺版本
     * @return multitype:number unknown
     */
    public function shopLevelList()
    {
        $index = isset($_POST["page_index"]) ? $_POST["page_index"] : 1;
        $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
        $shop = new ShopService();
        $list = $shop->getShopTypeList($index, PAGESIZE, ['type_name' => array('like', '%' . $search_text . '%'), 'website_id' => $this->website_id],'is_default desc');
        return $list;
    }

    /**
     * 店铺申请列表
     * @return multitype:number unknown
     */
    public function shopApplyList()
    {
        $index = request()->post('page_index', 1);
        $search_text = request()->post('search_text', '');
        $shop = new ShopService();
        $list = $shop->getShopApplyList($index, PAGESIZE, ['shop_name' => array('like', '%' . $search_text . '%'), 'website_id' => $this->website_id]);
        return $list;
    }

    /**
     * 店铺分组列表
     * @return multitype:number unknown
     */
    public function shopGroupList()
    {
        $index = request()->post('page_index', 1);
        $search_text = request()->post('search_text', '');
        $shop = new ShopService();
        $list = $shop->getShopGroup($index, PAGESIZE, ['group_name' => array('like', '%' . $search_text . '%'), 'website_id' => $this->website_id], 'group_sort ASC');
        return $list;
    }

    /**
     * 店铺分组列表-接口-下拉
     */
    public function shopGroup()
    {
        $shop = new ShopService();
        $list = $shop->getShopGroup(1, 0, ['website_id' => $this->website_id], 'group_sort ASC', ['shop_group_id', 'group_name','is_visible']);
	
        return json(['code' => 1, 'message' => '成功获取店铺分组', 'data' => ['shop_group_list' => $list['data']]]);
    }

    /**
     * 修改店铺分组
     */
    public function updateShopGroup()
    {
        $shop = new ShopService();
        $shop_group_id = request()->post('shop_group_id', 0);
        $group_name = request()->post('group_name', '');
        $group_sort = request()->post('group_sort', 0);
        $is_visible = request()->post('is_visible', 0);
        if ($shop_group_id) {
            $retval = $shop->updateShopGroup($shop_group_id, $group_name, $group_sort, $is_visible);
        } else {
            $retval = $shop->addShopGroup($group_name, $group_sort, $is_visible);
        }
        if($retval){
            $this->addUserLog('修改店铺分组', $retval);
        }
        return AjaxReturn($retval);
    }

    /**
     *
     */
    public function ajax_shopVerify()
    {
        $shop_apply_id = request()->post('apply_id', 0);
        $shop_platform_commission_rate = request()->post('shop_platform_commission_rate', 0);
        $margin = request()->post('margin', 0);
        $type = request()->post('type', '');
        $refuse_reason = request()->post('refuse_reason', '');
        $shop_audit = request()->post('shop_audit', 0);
        $shop = new ShopService();
        $retval = $shop->dealwithShopApply($shop_apply_id, $type, $shop_platform_commission_rate, $margin, $shop_audit, $refuse_reason);
        return AjaxReturn($retval);
    }

    /**
     * 删除店铺分组
     * @return multitype:number string |multitype:unknown
     */
    public function delShopGroup()
    {
        $shop = new ShopService();
        $shop_group_id = request()->post('shop_group_id', 0);
        $retval = $shop->delShopGroup($shop_group_id);
        if (empty($retval)) {
            $retval = ['code' => 0, 'message' => '分组已被使用不可删除!'];
            return $retval;
        }
        if($retval){
            $this->addUserLog('删除店铺分组', '店铺分组id'.$shop_group_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 编辑店铺版本
     * @return multitype:unknown
     */
    public function updateShopLevel()
    {
        $shop = new ShopService();
        $instance_typeid = request()->post('instance_typeid', 0);
        $type_name = request()->post('type_name', '');
        $type_module_array = request()->post('type_module_array', '');
        $type_desc = request()->post('type_desc', '');
        $type_sort = request()->post('type_sort', 0);
        if ($instance_typeid) {
            $retval = $shop->updateShopType($instance_typeid, $type_name, $type_module_array, $type_desc, $type_sort);
        } else {
            $retval = $shop->addShopType($type_name, $type_module_array, $type_desc, $type_sort);
        }
        if($retval){
            $this->addUserLog('编辑店铺版本', $retval);
        }
        return AjaxReturn($retval);
    }

    /**
     * 修改店铺
     */
    public function updateShop()
    {
        $shop_info['shop_id'] = request()->post('shop_id', '');
        $shop_info['shop_name'] = request()->post('shop_name', '');
        $shop_info['shop_group_id'] = request()->post('shop_group_id', '');
        $shop_info['shop_platform_commission_rate'] = request()->post('shop_platform_commission_rate', '');
        $shop_info['shop_type'] = request()->post('shop_type', '');
        $shop_info['shop_credit'] = request()->post('shop_credit', '');
        $shop_info['shop_desccredit'] = request()->post('shop_desccredit', '');
        $shop_info['shop_servicecredit'] = request()->post('shop_servicecredit', '');
        $shop_info['shop_deliverycredit'] = request()->post('shop_deliverycredit', '');
        $shop_info['store_qtian'] = request()->post('store_qtian', '');
        $shop_info['shop_zhping'] = request()->post('shop_zhping', '');
        $shop_info['shop_erxiaoshi'] = request()->post('shop_erxiaoshi', '');
        $shop_info['shop_tuihuo'] = request()->post('shop_tuihuo', '');
        $shop_info['shop_shiyong'] = request()->post('shop_shiyong', '');
        $shop_info['shop_shiti'] = request()->post('shop_shiti', '');
        $shop_info['shop_xiaoxie'] = request()->post('shop_xiaoxie', '');
        $shop_info['shop_huodaofk'] = request()->post('shop_huodaofk', '');
        $shop_info['shop_state'] = request()->post('shop_state', '');
        $shop_info['shop_close_info'] = request()->post('shop_close_info', '');
        $shop = new ShopService();
        $res = $shop->updateShopConfigByPlatform($shop_info);
        if($res){
            $this->addUserLog('修改店铺', $res);
        }
        return AjaxReturn($res);
    }

    public function updateShopApply()
    {
        $apply_id = isset($_POST['apply_id']) ? $_POST['apply_id'] : '';
        $company_name = isset($_POST['company_name']) ? $_POST['company_name'] : '';
        $company_province_id = isset($_POST['company_province_id']) ? $_POST['company_province_id'] : '';
        $company_city_id = isset($_POST['company_city_id']) ? $_POST['company_city_id'] : '';
        $company_district_id = isset($_POST['company_district_id']) ? $_POST['company_district_id'] : '';
        $company_address_detail = isset($_POST['company_address_detail']) ? $_POST['company_address_detail'] : '';
        $company_phone = isset($_POST['company_phone']) ? $_POST['company_phone'] : '';
        $company_employee_count = isset($_POST['company_employee_count']) ? $_POST['company_employee_count'] : '';
        $company_registered_capital = isset($_POST['company_registered_capital']) ? $_POST['company_registered_capital'] : '';
        $contacts_name = isset($_POST['contacts_name']) ? $_POST['contacts_name'] : '';
        $contacts_phone = isset($_POST['contacts_phone']) ? $_POST['contacts_phone'] : '';
        $contacts_email = isset($_POST['contacts_email']) ? $_POST['contacts_email'] : '';
        $business_licence_number = isset($_POST['business_licence_number']) ? $_POST['business_licence_number'] : '';
        $business_sphere = isset($_POST['business_sphere']) ? $_POST['business_sphere'] : '';
        $business_licence_number_electronic = isset($_POST['business_licence_number_electronic']) ? $_POST['business_licence_number_electronic'] : '';
        $organization_code = isset($_POST['organization_code']) ? $_POST['organization_code'] : '';
        $organization_code_electronic = isset($_POST['organization_code_electronic']) ? $_POST['organization_code_electronic'] : '';
        $general_taxpayer = isset($_POST['general_taxpayer']) ? $_POST['general_taxpayer'] : '';
        $bank_account_name = isset($_POST['bank_account_name']) ? $_POST['bank_account_name'] : '';
        $bank_account_number = isset($_POST['bank_account_number']) ? $_POST['bank_account_number'] : '';
        $bank_name = isset($_POST['bank_name']) ? $_POST['bank_name'] : '';
        $bank_code = isset($_POST['bank_code']) ? $_POST['bank_code'] : '';
        $bank_address = isset($_POST['bank_address']) ? $_POST['bank_address'] : '';
        $bank_licence_electronic = isset($_POST['bank_licence_electronic']) ? $_POST['bank_licence_electronic'] : '';
        $is_settlement_account = isset($_POST['is_settlement_account']) ? $_POST['is_settlement_account'] : '';
        $settlement_bank_account_name = isset($_POST['settlement_bank_account_name']) ? $_POST['settlement_bank_account_name'] : '';
        $settlement_bank_account_number = isset($_POST['settlement_bank_account_number']) ? $_POST['settlement_bank_account_number'] : '';
        $settlement_bank_name = isset($_POST['settlement_bank_name']) ? $_POST['settlement_bank_name'] : '';
        $settlement_bank_code = isset($_POST['settlement_bank_code']) ? $_POST['settlement_bank_code'] : '';
        $settlement_bank_address = isset($_POST['settlement_bank_address']) ? $_POST['settlement_bank_address'] : '';
        $tax_registration_certificate = isset($_POST['tax_registration_certificate']) ? $_POST['tax_registration_certificate'] : '';
        $taxpayer_id = isset($_POST['taxpayer_id']) ? $_POST['taxpayer_id'] : '';
        $tax_registration_certificate_electronic = isset($_POST['tax_registration_certificate_electronic']) ? $_POST['tax_registration_certificate_electronic'] : '';
        $shop = new ShopService();
        $res = $shop->updateShopApply($apply_id, $company_name, $company_province_id, $company_city_id, $company_district_id, $company_address_detail, $company_phone, $company_employee_count, $company_registered_capital, $contacts_name, $contacts_phone, $contacts_email, $business_licence_number, $business_sphere, $business_licence_number_electronic, $organization_code, $organization_code_electronic, $general_taxpayer, $bank_account_name, $bank_account_number, $bank_name, $bank_code, $bank_address, $bank_licence_electronic, $is_settlement_account, $settlement_bank_account_name, $settlement_bank_account_number, $settlement_bank_name, $settlement_bank_code, $settlement_bank_address, $tax_registration_certificate, $taxpayer_id, $tax_registration_certificate_electronic);
        return AjaxReturn($res);
    }

    public function getWithdrawCount()
    {
        $order = new ShopService();
        $order_count_array = array();
        $order_count_array['countall'] = $order->getShopWithdrawalCount(['website_id' => $this->website_id]);
        $order_count_array['waitcheck'] = $order->getShopWithdrawalCount(['status' => 1, 'website_id' => $this->website_id]);
        $order_count_array['waitmake'] = $order->getShopWithdrawalCount(['status' => 2, 'website_id' => $this->website_id]);
        $order_count_array['make'] = $order->getShopWithdrawalCount(['status' => 3, 'website_id' => $this->website_id]);
        $order_count_array['makefail'] = $order->getShopWithdrawalCount(['status' => 5, 'website_id' => $this->website_id]);
        $order_count_array['nomake'] = $order->getShopWithdrawalCount(['status' => 4, 'website_id' => $this->website_id]);
        $order_count_array['nocheck'] = $order->getShopWithdrawalCount(['status' => -1, 'website_id' => $this->website_id]);
        return $order_count_array;
    }
    /**
     * 店铺提现列表
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function shopAccountWithdrawList()
    {
        $page_index = request()->post("page_index", 1);
        $status = request()->post('status', '');
        $website_id = request()->post('website_id', '');
        $shop = new ShopService;
        $condition = ['nmar.website_id' => $website_id];
        $records_no = request()->post('records_no', '');
        $shop_name = request()->post('search_text', '');
        if ($status != '' && $status!=9) {
            $condition['nmar.status'] = $status;
        }
        if ($records_no != '') {
            $condition['nmar.withdraw_no'] = $records_no;
        }
        if (empty($_POST['start_date'])) {
            $start_date = strtotime('2018-1-1');
        } else {
            $start_date = strtotime($_POST['start_date']);
        }
        if (empty($_POST['end_date'])) {
            $end_date = strtotime('2038-1-1');
        } else {
            $end_date = strtotime($_POST['end_date']);
        }
        $condition["nmar.ask_for_date"] = [[">", $start_date], ["<", $end_date]];
        $list = $shop->getShopAccountWithdrawList($page_index, PAGESIZE, $condition, $shop_name, 'ask_for_date desc');
        return $list;
    }

    /**
     * 店铺提现列表导出
     */
    public function shopAccountWithdrawListDataExcel()
    {
        $xlsName = "店铺提现流水列表";
        $xlsCell = array(
            array(
                'withdraw_no',
                '提现流水号'
            ),
            array(
                'shop_name',
                '店铺名'
            ),
            array(
                'type',
                '提现类型'
            ),
            array(
                'account_number',
                '提现账号'
            ),
            array(
                'cash',
                '提现金额'
            ),
            array(
                'charge',
                '手续费'
            ),
            array(
                'status',
                '提现状态'
            ),
            array(
                'ask_for_date',
                '申请时间'
            ),
            array(
                'payment_date',
                '到账时间'
            )
        );
        $status = request()->get('status', '');
        $website_id = request()->get('website_id', '');
        $shop = new ShopService;
        $condition = ['nmar.website_id' => $website_id];
        $records_no = request()->get('records_no', '');
        $shop_name = request()->get('search_text', '');
        if ($status != '' && $status!=9) {
            $condition['nmar.status'] = $status;
        }
        if ($records_no != '') {
            $condition['nmar.withdraw_no'] = $records_no;
        }
        if (empty($_GET['start_date'])) {
            $start_date = strtotime('2018-1-1');
        } else {
            $start_date = strtotime($_GET['start_date']);
        }
        if (empty($_GET['end_date'])) {
            $end_date = strtotime('2038-1-1');
        } else {
            $end_date = strtotime($_GET['end_date']);
        }
        $condition["nmar.ask_for_date"] = [[">", $start_date], ["<", $end_date]];
        $list = $shop->getShopAccountWithdrawList(1, 0, $condition, $shop_name, 'ask_for_date desc');
        foreach ($list['data'] as $k => $v) {
            if ($v['status'] == 2) {
                $v['status'] = '待打款';
            } elseif ($v['status'] == 3) {
                $v['status'] = '已打款';
            } elseif ($v['status'] == 4) {
                $v['status'] = '拒绝打款';
            } elseif ($v['status'] == 1) {
                $v['status'] = '待审核';
            } elseif ($v['status'] == -1) {
                $v['status'] = '审核不通过';
            } elseif ($v['status'] == 5) {
                $v['status'] = '打款失败';
            }
        }
        $this->addUserLog('店铺提现列表导出', 1);
        dataExcel($xlsName, $xlsCell, $list['data']);
    }

    /**
     * 店铺提现审核
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function shopAccountWithdrawAudit()
    {
        $shop = new ShopService();
        $id = $_POST['id'];
        $memo = isset($_POST['memo']) ? $_POST['memo'] : '';
        $status = $_POST['status'];
        $ids = explode(',',$id);
        if(count($ids)>1){
            foreach ($ids as $v) {
                $retval = $shop->shopAccountWithdrawAudit($v, $status, $memo);
            }
        }else{
            $retval = $shop->shopAccountWithdrawAudit($id, $status, $memo);
        }
        if($retval){
            if($status==-1){
                $this->addUserLog('店铺提现审核不通过', $retval);
            }
            if($status==2){
                $this->addUserLog('店铺提现审核通过', $retval);
            }
            if($status==3){
                $this->addUserLog('店铺提现同意打款', $retval);
            }
            if($status==4){
                $this->addUserLog('店铺提现拒绝打款', $retval);
            }
        }
        return AjaxReturn($retval);
    }
    /*
     * 店铺详情
     * **/
    public function shopAccountWithdrawInfo()
    {
        $shop = new ShopService;
        $id = $_GET['id'];
        $retval = $shop->shopAccountWithdrawDetail($id);
        return json($retval);
    }

    /**
     * 获取省列表
     */
    public function getProvince()
    {
        $address = new Address();
        $province_list = $address->getProvinceList();
        return $province_list;
    }

    /**
     * 获取城市列表
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:, array>
     */
    public function getCity()
    {
        $address = new Address();
        $province_id = isset($_POST['province_id']) ? $_POST['province_id'] : 0;
        $city_list = $address->getCityList($province_id);
        return $city_list;
    }

    /**
     * 获取区域地址
     */
    public function getDistrict()
    {
        $address = new Address();
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        $district_list = $address->getDistrictList($city_id);
        return $district_list;
    }

    /**
     * 获取选择地址
     *
     * @return unknown
     */
    public function getSelectAddress()
    {
        $address = new Address();
        $province_list = $address->getProvinceList();
        $province_id = isset($_POST['province_id']) ? $_POST['province_id'] : 0;
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        $city_list = $address->getCityList($province_id);
        $district_list = $address->getDistrictList($city_id);
        $data["province_list"] = $province_list;
        $data["city_list"] = $city_list;
        $data["district_list"] = $district_list;
        return $data;
    }

    /**
     * 添加店铺
     */
    public function addPlatformShop()
    {
        $shopInfo = array();
        $shopApplyInfo = array();
        $shop_id = request()->post('shop_id', '');
        //店铺信息

        $shopInfo['shop_name'] = request()->post('shop_name', '');
        $shopInfo['shop_group_id'] = request()->post('shop_group_id', '');
        $shopInfo['shop_type'] = request()->post('shop_type', '');
        $shopInfo['shop_platform_commission_rate'] = request()->post('shop_platform_commission_rate', 0);
        $shopInfo['margin'] = request()->post('margin', 0);
        $shopInfo['shop_sort'] = request()->post('shop_sort', 0);
        $shopInfo['shop_state'] = request()->post('shop_state', 0);
        $shopInfo['shop_audit'] = request()->post('shop_audit', 0);
        //注册信息
        $shopApplyInfo['apply_type'] = request()->post('apply_type', '');
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
        $shop = new ShopService();
        
        if ($shop_id || $shop_id==='0') {//添加店铺或者自营店修改店铺
            $shopInfo['shop_id'] = $shop_id;
            if($shop_id==='0'){
                $shopInfo['shop_state'] = 1;
            }
            $shopApplyInfo['shop_id'] = $shop_id;
            $shop->updateShopConfigByPlatform($shopInfo);
            $shop->addShopInfo($shopApplyInfo);
            $res = 1;
        } else {
            //获取会员信息
            $uid = request()->post('uid', '');
            if(empty($uid)){
                return json(['code' => -1,'message' => '请选择会员']);
            }
            $shopInfo['user_account'] = request()->post('user_account', '');
            $shopInfo['user_pwd'] = request()->post('user_pwd', '');
            //获取会员信息
            $user = new User();
            $uid = request()->post("uid", '');
            $result = $user->getUserInfoByUid($uid);
            if(!$result){
                return json(['code' => -1,'message' => '会员信息错误，请重新选择']);
            }
            if(empty($shopInfo['user_account']) && empty($shopInfo['user_pwd']) && $uid){
                //直接取会员手机号密码信息
                if(empty($result['user_tel'])){
                    return json(['code' => -1,'message' => '会员手机信息为空，请重新选择会员或补充手机信息']);
                }
                $shopInfo['user_account'] = $result['user_tel'];
                $shopInfo['user_pwd'] = $result['user_password'];
                //直接更新已加密会员密码手机信息
                $res = $shop->addPlatformShop($shopInfo, $shopApplyInfo,$uid);
            }else if($uid && $shopInfo['user_account'] && $shopInfo['user_pwd']){
                //当前选择的会员并没有设置手机密码 设置手机密码后同步至会员信息
                $res = $shop->addPlatformShop($shopInfo, $shopApplyInfo,$uid);
                //更新会员信息
                $update_data = [
                    'user_tel' => $shopInfo['user_account'],
                    'user_password' => md5($shopInfo['user_pwd'])
                ];
                $condition['uid'] = $uid;
                $condition['website_id'] = $this->website_id;
                $condition['is_member'] = 1;
                $user->updateUserNew($update_data, $condition);
            }
        }
        if($res){
            $this->addUserLog('添加店铺', $shopInfo['shop_name']);
        }
        return AjaxReturn($res);
    }

    /**
     * 保存店铺协议
     */
    public function setShopProtocol()
    {
        $title = request()->post('title/a', []);
        $key = request()->post('key/a', []);
        $content = $_POST['content'];
        $shop = new ShopService();
        $res = 1;
        foreach ($key as $k => $val) {
            $value = array(
                "key" => $val,
                "title" => $title[$k],
                "content" => $content[$k]
            );
            $jsonValue = json_encode($value);
            $res = $shop->setShopProtocol($jsonValue, $val);
            if(!$res){
                $res = 0;
            }
        }
        if($res){
            $this->addUserLog('添加店铺协议', $title);
        }
        unset($val);
        return AjaxReturn(1);
    }

    /**
     * 修改入驻指南
     */
    public function updateGuide()
    {
        $guide_id = request()->post('guide_id', '');
        $title = request()->post('title', '');
        $sort = request()->post('sort', '');
        $image = request()->post('image', '');
        $content = request()->post('content', '');
        $shop = new ShopService();
        $retval = $shop->updateGuide($guide_id, $title, $sort, $image, $content);
        if($retval){
            $this->addUserLog('修改入驻指南', $guide_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 店铺账目列表
     */
    public function shopAccountList()
    {
        $page_index = request()->post('page_index', '');
        $page_size = request()->post('page_size', '');
        $website_id = request()->post('website_id', '');
        $search_text = request()->post('search_text', '');
        $shop = new ShopService();
        $retval = $shop->getShopAccountCountList($page_index, $page_size, ['website_id' => $website_id], '', $search_text);
        return $retval;
    }

    /**
     * 入驻店订单列表
     */
    public function shopOrderList()
    {
        $page_index = request()->post('page_index', 1);
        $website_id = request()->post('website_id', '');
        $page_size = request()->post('page_size', PAGESIZE);
        $start_create_date = request()->post('start_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_create_date'));
        $end_create_date = request()->post('end_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_create_date'));
        $start_pay_date = request()->post('start_pay_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_pay_date'));
        $end_pay_date = request()->post('end_pay_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_pay_date'));
        $start_send_date = request()->post('start_send_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_send_date'));
        $end_send_date = request()->post('end_send_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_send_date'));
        $start_finish_date = request()->post('start_finish_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_finish_date'));
        $end_finish_date = request()->post('end_finish_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_finish_date'));
        $user = request()->post('user', '');
        $order_no = request()->post('order_no', '');
        $payment_type = request()->post('payment_type', 1);
        $express_no = request()->post('express_no', '');
        $goods_name = request()->post('goods_name', '');
        $order_type = request()->post('order_type', '');
        $condition['is_deleted'] = 0; // 未删除订单
        if ($express_no) {
            $condition['express_no'] = ['LIKE', '%' . $express_no . '%'];
        }
        if ($goods_name) {
            $condition['goods_name'] = ['LIKE', '%' . $goods_name . '%'];
        }
        if ($order_type) {
            $condition['order_type'] = $order_type;
        }
        if ($start_create_date) {
            $condition['create_time'][] = ['>=', $start_create_date];
        }
        if ($end_create_date) {
            $condition['create_time'][] = ['<=', $end_create_date + 86399];
        }
        if ($start_pay_date) {
            $condition['pay_time'][] = ['>=', $start_pay_date];
        }
        if ($end_pay_date) {
            $condition['pay_time'][] = ['<=', $end_pay_date + 86399];
        }
        if ($start_send_date) {
            $condition['consign_time'][] = ['>=', $start_send_date];
        }
        if ($end_send_date) {
            $condition['consign_time'][] = ['<=', $end_send_date + 86399];
        }
        if ($start_finish_date) {
            $condition['finish_time'][] = ['>=', $start_finish_date];
        }
        if ($end_finish_date) {
            $condition['finish_time'][] = ['<=', $end_finish_date + 86399];
        }

        if (!empty($payment_type)) {
            $condition['payment_type'] = $payment_type;
        }
        if (!empty($user)) {
            $condition['receiver_name|receiver_mobile|user_name|buyer_id'] = array(
                "like",
                "%" . $user . "%"
            );
        }
        if (!empty($order_no)) {
            $condition['order_no'] = array(
                "like",
                "%" . $order_no . "%"
            );
        }
        $condition['website_id'] = $website_id;
        $condition['shop_id'] = ['>', '0'];
        $order_service = new OrderService();
        $list = $order_service->getOrderList($page_index, $page_size, $condition, 'create_time desc');
        return $list;
    }

    /**
     * 店铺商品列表
     */
    public function goodsList()
    {
        $page_index = request()->post('page_index', 1);
        $goods_name = request()->post('goods_name', '');
        $shop_name = request()->post('shop_name', '');
        $state = request()->post('state', 1);
        $goodservice = new GoodsService();
        if ($state) {
            $condition["ng.state"] = $state;
        }
        if (!empty($goods_name)) {
            $condition["ng.goods_name"] = array(
                "like",
                "%" . $goods_name . "%"
            );
        }
        if (!empty($shop_name)) {
            $condition["nss.shop_name"] = array(
                "like",
                "%" . $shop_name . "%"
            );
        }
        $condition["ng.shop_id"] = [
            [
                ">",
                0
            ]
        ];
        $condition['ng.website_id'] = $this->website_id;
        $result = $goodservice->getGoodsList($page_index, 20, $condition, 'ng.create_time desc');
        return $result;
    }

    /**
     * 商家入驻第二步：公司信息认证
     * @return \think\response\View
     */
    public function applySecondCompanyInfo($param = array())
    {
        $shop = new ShopService();
        $apply_type = isset($_POST['apply_type']) ? $_POST['apply_type'] : '';
        $uid = $this->user->getSessionUid();
        $company_name = isset($_POST['company_name']) ? $_POST['company_name'] : '';
        $company_province_id = isset($_POST['company_province_id']) ? $_POST['company_province_id'] : '';
        $company_city_id = isset($_POST['company_city_id']) ? $_POST['company_city_id'] : '';
        $company_district_id = isset($_POST['company_district_id']) ? $_POST['company_district_id'] : '';
        $company_address_detail = isset($_POST['company_address_detail']) ? $_POST['company_address_detail'] : '';
        $company_phone = isset($_POST['company_phone']) ? $_POST['company_phone'] : '';
        $company_type = isset($_POST['company_type']) ? $_POST['company_type'] : 1;
        $company_employee_count = isset($_POST['company_employee_count']) ? $_POST['company_employee_count'] : 1;
        $company_registered_capital = isset($_POST['company_registered_capital']) ? $_POST['company_registered_capital'] : 0;
        $contacts_name = isset($_POST['contacts_name']) ? $_POST['contacts_name'] : '';
        $contacts_phone = isset($_POST['contacts_phone']) ? $_POST['contacts_phone'] : '';
        $contacts_email = isset($_POST['contacts_email']) ? $_POST['contacts_email'] : '';
        $contacts_card_no = isset($_POST['contacts_card_no']) ? $_POST['contacts_card_no'] : '';
        $contacts_card_electronic_1 = isset($_POST['contacts_card_electronic_1']) ? $_POST['contacts_card_electronic_1'] : '';
        $contacts_card_electronic_2 = isset($_POST['contacts_card_electronic_2']) ? $_POST['contacts_card_electronic_2'] : '';
        $contacts_card_electronic_3 = isset($_POST['contacts_card_electronic_3']) ? $_POST['contacts_card_electronic_3'] : '';
        $business_licence_number = isset($_POST['business_licence_number']) ? $_POST['business_licence_number'] : '';
        $business_sphere = isset($_POST['business_sphere']) ? $_POST['business_sphere'] : '';
        $business_licence_number_electronic = isset($_POST['business_licence_number_electronic']) ? $_POST['business_licence_number_electronic'] : '';
        
        $shop_name = isset($_POST['shop_name']) ? $_POST['shop_name'] : '';
        $apply_state = isset($_POST['apply_state']) ? $_POST['apply_state'] : 1;
        $apply_message = isset($_POST['apply_message']) ? $_POST['apply_message'] : '';
        $apply_year = isset($_POST['apply_year']) ? $_POST['apply_year'] : 1;
        $shop_group_name = isset($_POST['shop_group_name']) ? $_POST['shop_group_name'] : '';
        $shop_group_id = isset($_POST['shop_group_id']) ? $_POST['shop_group_id'] : 0;
        $paying_money_certificate = isset($_POST['paying_money_certificate']) ? $_POST['paying_money_certificate'] : '';
        $paying_money_certificate_explain = isset($_POST['paying_money_certificate_explain']) ? $_POST['paying_money_certificate_explain'] : '';
        $paying_amount = isset($_POST['paying_amount']) ? $_POST['paying_amount'] : 0;
        $post_data = isset($_POST['post_data']) ? $_POST['post_data'] : '';
        $retval = $shop->addShopApply($apply_type, $uid, $company_name, $company_province_id, $company_city_id, $company_district_id, $company_address_detail, $company_phone, $company_type, $company_employee_count, $company_registered_capital, $contacts_name, $contacts_phone, $contacts_email, $contacts_card_no, $contacts_card_electronic_1, $contacts_card_electronic_2, $contacts_card_electronic_3, $business_licence_number, $business_sphere, $business_licence_number_electronic,  $shop_name, $apply_state, $apply_message, $apply_year, $shop_group_name, $shop_group_id, $paying_money_certificate, $paying_money_certificate_explain, $paying_amount, 0, $post_data);
        return AjaxReturn($retval);
    }

    /**
     * 删除店铺版本
     */
    public function deleteShopLevel()
    {
        $instance_typeid = request()->post('instance_typeid', 0);
        $shop = new ShopService();
        $retval = $shop->deleteShopLevel($instance_typeid);
        if($retval){
            $this->addUserLog('删除店铺版本', $instance_typeid);
        }
        return AjaxReturn($retval);
    }

    /**
     * 设置店铺分类是否显示
     */
    public function setIsvisible()
    {
        $shop_group_id = request()->post('shop_group_id', 0);
        $is_visible = request()->post('is_visible', 0);
        $shop = new ShopService();
        $retval = $shop->setIsvisible($shop_group_id, $is_visible);
        if($retval){
            $this->addUserLog('设置店铺分类是否显示', $shop_group_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 店铺设置
     */
    public function setShopSetting()
    {
        $shop = new shopService();
        // 店铺设置
        $is_use = request()->post('is_use', 0); // 是否开启店铺
        $platform_commission_percentage = request()->post('platform_commission_percentage', ''); // 店铺抽成比率
        $retval = $shop->setShopSetting($is_use, $platform_commission_percentage);
        if($retval){
            $this->addUserLog('店铺设置', $retval);
        }
        setAddons('shop', $this->website_id, $this->instance_id);
        setAddons('shop', $this->website_id, $this->instance_id, true);
        return AjaxReturn($retval);
    }

    /**
     * 移动端店铺选择
     */
    public function modalShopList()
    {
        if (request()->post('page_index')){
            $index = request()->post('page_index', 1);
            $search_text = request()->post('search_text', '');
            if ($search_text) {
                $condition['shop_name'] = ['like', '%' . $search_text . '%'];
            }
            $condition['website_id'] = $this->website_id;
            $shop = new ShopService();
            $list = $shop->getShopList($index, PAGESIZE, $condition);
            $shop_list = [];
            //删除多余的字段
            foreach ($list['data'] as $k => $v) {
                $shop_list[$k]['shop_id'] = $v['shop_id'];
                $shop_list[$k]['shop_name'] = $v['shop_name'];
                $shop_list[$k]['pic_cover'] = __IMG($v['picture']);
            }
            $list['data'] = $shop_list;
            return $list;
        }
        $this->assign('modalUrl',__URL(addons_url_platform('shop://shop/modalShopList')));
        $this->fetch('template/platform/shopDialog');

    }

    /**
     * 收藏店铺
     */
    public function collectShop()
    {
        try {
            $shop_id = request()->post('shop_id');
            if ($shop_id == '') {
                return AjaxReturn(LACK_OF_PARAMETER);
            }
            if (!getUserId()) {
                return AjaxReturn(NO_LOGIN);
            }
            $member_server = new ShopService();
            $result = $member_server->addMemberFavouites($shop_id, 'shop', '');
            if ($result) {
                return AjaxReturn(SUCCESS);
            } else {
                return json(['code' => -1, 'message' => '店铺已关闭不能收藏']);
            }
        } catch (\Exception $e) {
            return AjaxReturn(SYSTEM_ERROR);
        }
    }

    /**
     * 取消店铺收藏
     */
    public function cancelCollectShop()
    {
        try {
            $shop_id = request()->post('shop_id');
            if (($shop_id == '')) {
                return AjaxReturn(LACK_OF_PARAMETER);
            }
            if (!getUserId()) {
                return AjaxReturn(NO_LOGIN);
            }
            $member_server = new ShopService();
            $result = $member_server->deleteMemberFavorites($shop_id, 'shop');
            if ($result) {
                return json(AjaxReturn(SUCCESS));
            } else {
                return json(['code' => -1, 'message' => '店铺已关闭不能收藏']);
            }
        } catch (\Exception $e) {
            return json(AjaxReturn(SYSTEM_ERROR));
        }
    }

    /**
     * 我的店铺收藏
     */
    public function myShopCollection()
    {
        $page_index = request()->post('page_index');
        $page_size = request()->post('page_size') ?: PAGESIZE;
        if (empty($page_index)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition = array(
            'nmf.fav_type' => 'shop',
            'nmf.uid' => $this->uid
        );
        $shop_server= new ShopService();
        $album_picture_model = new AlbumPictureModel();
        $shop_collection_data = $shop_server->getMemberShopsFavoritesList($page_index,$page_size,$condition,'fav_time desc');
        $shop_list = [];
        foreach ($shop_collection_data['data'] as $k => $v){
            $shop_list[$k]['shop_id'] = $v['shop_id'];
            $shop_list[$k]['shop_name'] = $v['shop_name'];
            $shop_list[$k]['shop_logo'] = $v['shop_logo'] ? getApiSrc($album_picture_model::get($v['shop_logo'])['pic_cover']) : '';
        }
        return json([
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'shop_list' => $shop_list,
                'page_count' => $shop_collection_data['page_count'],
                'total_count' => $shop_collection_data['total_count']
            ]
        ]);
    }
    /**
     * 店铺收藏 商品/店铺
     */
    public function shopCollectionList()
    {
        if(request()->isPost()){
            $member = new Member();
            $page = isset($_POST['page_index']) ? $_POST['page_index'] : '1';
            $data = array(
                "nmf.fav_type" => 'shop',
                "nmf.uid" => $this->uid,
                "nmf.website_id" => $this->website_id
            );
            $list = $member->getMemberShopsFavoritesList($page, 12, $data);
            foreach ($list['data'] as $k => $v){
                $album_picture_model = new AlbumPictureModel();
                $v['shop_logo'] = $v['shop_logo'] ? getApiSrc($album_picture_model::get($v['shop_logo'])['pic_cover']) : '';
                if(empty($v['shop_logo'])){
                    $website = new WebSiteModel();
                    $v['shop_logo'] = $website->getInfo(['website_id'=>$this->website_id],'logo')['logo'];
                }
            }
            return $list;
        }
    }
    /**
     * 取消收藏 商品/店铺
     */
    public function cancelCollGoodsOrShop()
    {
        $fav_id = request()->post('fav_id','');
        $fav_type = request()->post('fav_type','');
        $member = new Member();
        $result = $member->deleteMemberFavorites($fav_id, $fav_type);
        if($result){
            $this->addUserLog('取消收藏 商品/店铺', $fav_id);
        }
        return json(AjaxReturn($result));
    }
    /*
     * 移动端店铺入驻申请
     */
    public function applyForWap(){
        $shop = new ShopService();
        $apply_type = input('post.apply_type');
        $company_name = input('post.company_name') ? : '';
        $company_province_id = input('post.company_province_id');
        $company_city_id = input('post.company_city_id');
        $company_district_id = input('post.company_district_id');
        $company_address_detail = input('post.company_address_detail');
        $company_phone = input('post.company_phone') ? : '';
        $company_type = input('post.company_type') ? : '';
        $company_employee_count = input('post.company_employee_count') ? : 1;
        $company_registered_capital = input('post.company_registered_capital') ? : 1;
        $contacts_name = input('post.contacts_name');
        $contacts_phone = input('post.contacts_phone');
        $contacts_email = input('post.contacts_email');
        $contacts_card_no = input('post.contacts_card_no');
        $contacts_card_electronic_1 = input('post.contacts_card_electronic_1');
        $contacts_card_electronic_2 = input('post.contacts_card_electronic_2');
        $contacts_card_electronic_3 = input('post.contacts_card_electronic_3');
        $business_licence_number = input('post.business_licence_number') ? : '';
        $business_sphere = input('post.business_sphere') ? : '';
        $business_licence_number_electronic = input('post.business_licence_number_electronic') ? : '';
        $shop_name = input('post.shop_name');
        $apply_state = input('post.apply_state') ? : 1;
        $apply_message = input('post.apply_message')? : '';
        $apply_year = input('post.apply_year') ? : 1;
        $shop_group_name = input('post.shop_group_name');
        $shop_group_id = input('post.shop_group_id');
        $paying_money_certificate = input('post.paying_money_certificate')? : '';
        $paying_money_certificate_explain = input('post.paying_money_certificate_explain')? : '';
        $paying_amount = input('post.paying_amount')? : '';
        $post_data = input('post.post_data');//自定义表单数据
        $uid = $this->user->getSessionUid();
        $retval = $shop->addShopApply($apply_type, $uid, $company_name, $company_province_id, $company_city_id, $company_district_id, $company_address_detail, $company_phone, $company_type, $company_employee_count, $company_registered_capital, $contacts_name, $contacts_phone, $contacts_email, $contacts_card_no, $contacts_card_electronic_1, $contacts_card_electronic_2, $contacts_card_electronic_3, $business_licence_number, $business_sphere, $business_licence_number_electronic,  $shop_name, $apply_state, $apply_message, $apply_year, $shop_group_name, $shop_group_id, $paying_money_certificate, $paying_money_certificate_explain, $paying_amount, 0, $post_data);
        return json(AjaxReturn($retval));
    }
    /*
     * 移动端获取店铺协议
     */
    public function getShopProtocolByWap(){
        $shop = new ShopService();
        $shopProtocol = array();
        $direction= $shop->getShopProtocol('DIRECTION');
        $direction['title'] = $direction['title'] ? : '招商方向';
        unset($direction['key']);
//        $direction['key'] = '招商方向';
        $shopProtocol[] = $direction;
        $standard= $shop->getShopProtocol('STANDARD');
        $standard['title'] = $standard['title'] ? : '招商标准';
        unset($standard['key']);
//        $standard['key'] = '招商标准';
        $shopProtocol[] = $standard;
        $demand= $shop->getShopProtocol('DEMAND');
        $demand['title'] = $demand['title'] ? : '资质要求';
        unset($demand['key']);
//        $demand['key'] = '资质要求';
        $shopProtocol[] = $demand;
        $cost = $shop->getShopProtocol('COST');
        $cost['title'] = $cost['title'] ? : '资费标准';
        unset($cost['key']);
//        $cost['key'] = '资费标准';
        $shopProtocol[]  = $cost;
        $join= $shop->getShopProtocol('JOIN');
        $join['title'] = $join['title'] ? : '入驻协议';
        unset($join['key']);
//        $join['key'] = '入驻协议';
        $shopProtocol[] = $join;
        return json([
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'shop_protocol' => $shopProtocol
            ]
        ]);
    }
    /*
     * 移动端获取申请状态
     */
    public function getApplyStateByWap(){
        $member = new Member();
        $apply_state = $member->getMemberIsApplyShop($this->uid);
        if(file_exists('././version.php')){//源码
            $url = __URL('ADMIN_MAIN/login','website_id='.$this->website_id);
        }else{
            $url = 'https://shop.vslai.com.cn/admin/login?website_id='.$this->website_id;
        }
        return json([
            'code' => 1,
            'message' => '获取成功',
            
            'data' => [
                'status' => $apply_state,
                'url' => $url
            ]
        ]);
    }
    /*
     * 获取自定义表单
     */
    public function getApplyCustomForm(){
        $shop = new ShopService();
        $customForm = $shop->getShopCustomForm();
        return json([
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'custom_form' => $customForm
            ]
        ]);
    }
    /*
     * 获取审核详情 （自定义表单）
     */
    public function getApplyDetail(){
        $apply_id = request()->post('apply_id',0);
        $shop = new ShopService();
        $result=$shop->getShopApplyDetail($apply_id);
         $result['custom_data'] = $shop->getShopCustomForm();
        return $result;
    }
    
    /**
     * 获取 入驻店商品 数量 出售中 待审核 审核不通过 已下架
     */
    public function getShopGoodsCount(){
        $goods_count = new GoodsService();
        $goods_count_array = array();
        //出售中
        $goods_count_array['on'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>1,'shop_id'=>['>', 0]]);
        //待审核
        $goods_count_array['checking'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>11,'shop_id'=>['>', 0]]);
        //审核不通过
        $goods_count_array['uncheck'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>12,'shop_id'=>['>', 0]]);
        //已下架
        $goods_count_array['out'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>10,'shop_id'=>['>', 0]]);
        return $goods_count_array;
    }
}
