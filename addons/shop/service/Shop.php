<?php

namespace addons\shop\service;

/**
 * 店铺服务层
 */

use data\model\VslBankModel;
use data\model\VslMemberFavoritesModel;
use data\model\VslMemberModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberLevelModel;
use data\model\WebSiteModel;
use data\service\BaseService as BaseService;
use addons\shop\model\VslShopModel as VslShopModel;
use addons\shop\model\VslShopGroupModel as VslShopGroupModel;
use addons\shop\model\VslShopApplyModel;
use data\model\AdminUserModel;
use data\model\UserModel;
use data\model\InstanceTypeModel;
use data\service\WebSite;
use data\model\InstanceModel;
use data\model\AuthGroupModel;
use data\model\VslOrderModel;
use addons\shop\model\VslShopAccountModel;
use addons\shop\model\VslShopAccountRecordsModel;
use addons\shop\model\VslShopWithdrawModel;
use addons\shop\model\VslShopBankAccountModel;
use addons\shop\model\VslShopInfoModel;
use addons\shop\service\shopaccount\ShopAccount as ShopAccountService;
use addons\shop\service\shopaccount\ShopAccount;
use addons\shop\model\VslShopOrderReturnModel;
use data\model\VslMemberWithdrawSettingModel;
use data\model\ProvinceModel;
use data\model\CityModel;
use data\model\DistrictModel;
use data\service\Album;
use data\service\User;
use data\model\VslOrderGoodsViewModel;
use data\service\Config as WebConfig;
use data\model\AlbumPictureModel;
use data\model\ConfigModel;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
use data\service\Merchant;
use data\model\VslShopEvaluateModel;
use addons\store\server\Store;
use addons\customform\server\Custom as CustomServer;
use think\Db;

class Shop extends BaseService
{
    private $config_module;
    private $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->config_module = new ConfigModel();
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::updateShopSort()
     */
    public function updateShopSort($shop_id, $shop_sort)
    {
        $shop = new VslShopModel();
        $data = array(
            'shop_sort' => $shop_sort
        );
        $shop->save($data, [
            'shop_id' => $shop_id
        ]);

        return $shop_id;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::setRecomment()
     */
    public function setRecomment($shop_id, $shop_recommend)
    {
        $shop = new VslShopModel();
        $data = array(
            'shop_recommend' => $shop_recommend
        );
        $shop->save($data, [
            'shop_id' => $shop_id
        ]);

        return $shop_id;
    }

    /**
     * (non-PHPdoc)
     *
     * 设置店铺分类是否显示
     */
    public function setIsvisible($shop_group_id, $is_visible)
    {
        $shop = new VslShopGroupModel();
        $data = array(
            'is_visible' => $is_visible
        );
        $shop->save($data, [
            'shop_group_id' => $shop_group_id
        ]);

        return $shop_group_id;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IShop::setStatus()
     */

    public function setStatus($shop_id, $type)
    {
        $shop = new VslShopModel();
        $data = array(
            'shop_state' => $type
        );
        $shop->save($data, [
            'shop_id' => $shop_id
        ]);
        return $shop_id;
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::getShopList()
     */
    public function getShopList($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $shop = new VslShopModel();
        $shop_type = new InstanceTypeModel();
        $shop_group = new VslShopGroupModel();
        $picture = new AlbumPictureModel();

        $list = $shop->pageQuery($page_index, $page_size, $where, $order, '*');

        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]['shop_type_name'] = $shop_type->getInfo([
                'instance_typeid' => $v['shop_type']
            ], 'type_name')['type_name'];

            $list['data'][$k]['group_name'] = $shop_group->getInfo([
                'shop_group_id' => $v['shop_group_id']
            ], 'group_name')["group_name"];

            $shop_picture = $picture->get($v['shop_logo']);
            if (empty($shop_picture)) {
                $shop_picture = array(
                    'pic_cover' => '',
                    'pic_cover_big' => '',
                    'pic_cover_mid' => '',
                    'pic_cover_small' => '',
                    'pic_cover_micro' => '',
                    'upload_type' => 1,
                    'domain' => ''
                );
            }
            $shop_evaluate = $this->getShopEvaluate($v['shop_id']);
            $list['data'][$k]['description_credit'] = $shop_evaluate['shop_desc'];
            $list['data'][$k]['service_credit'] = $shop_evaluate['shop_service'];
            $list['data'][$k]['delivery_credit'] = $shop_evaluate['shop_stic'];
            $list['data'][$k]['picture'] = $shop_picture['pic_cover'];
            $list['data'][$k]['shop_logo_img'] = $shop_picture['pic_cover'];
        }
        return $list;
    }

    /**
     * wap端店铺搜索
     */
    public function shopList($page_index = 1, $page_size = 0, $condition = [], $order = '', $field = '*', $group = '')
    {
        $shop_model = new VslShopModel();
        $shop_group = new VslShopGroupModel();
        $picture = new AlbumPictureModel();
        $website_model = new WebSiteModel();
        $query_list_obj = $shop_model->alias('ns')
            ->join('vsl_goods ng', 'ns.shop_id = ng.shop_id', 'LEFT')
            ->field($field);

        $shop_list = $shop_model->viewPageQuerys($query_list_obj, $page_index, $page_size, $condition, $order, $group);
        //var_dump(Db::table('')->getLastSql());exit;
        $count = $shop_model->alias('ns')
            ->where($condition)
            ->count('ns.shop_id');

        $list = $shop_model->setReturnList($shop_list, $count, $page_size);
        $return_shop_list = [];
        foreach ($list['data'] as $k => $v) {
            $return_shop_list[$k]['id'] = $v['id'];
            $return_shop_list[$k]['shop_id'] = $v['shop_id'];
            $return_shop_list[$k]['shop_name'] = $v['shop_name'];
            $evaluate = $this->getShopEvaluate($v['shop_id']);
            $return_shop_list[$k]['description_credit'] = $evaluate['shop_desc'];
            $return_shop_list[$k]['service_credit'] = $evaluate['shop_service'];
            $return_shop_list[$k]['delivery_credit'] = $evaluate['shop_stic'];
            $return_shop_list[$k]['comprehensive'] = $evaluate['comprehensive'] ?: 5;
            if ($v['shop_id'] == 0) {
                $shop_logo = getApiSrc($website_model::get($this->website_id)['logo']);
            } else {
                $shop_logo = '';
                $shop_picture = $picture->getInfo(['pic_id' =>$v['shop_logo']],'pic_cover,pic_cover_mid,pic_cover_micro');
                if (!empty($shop_picture)) {
                    $shop_logo = getApiSrc($shop_picture['pic_cover']);
                }
            }
            // 是否显示分类
            $shop_group_info =  $shop_group->getInfo([
                'shop_group_id' => $v['shop_group_id']
            ], 'group_name,is_visible');
            $return_shop_list[$k]['group_name'] = $shop_group_info['group_name'];
            $return_shop_list[$k]['is_visible'] = $shop_group_info['is_visible'] ?: 0;
            $return_shop_list[$k]['shop_logo'] = $shop_logo;
            $return_shop_list[$k]['has_store'] = 0;
            $storeSet = 0;
            if(getAddons('store', $this->website_id, $this->instance_id)){
                $storeServer = new Store();
                $storeSet = $storeServer->getStoreSet($v['shop_id'])['is_use'];
            }
            $return_shop_list[$k]['has_store'] = $storeSet ? : 0;
        }
        // 针对评分进行重新排序（因为上面重新计算过）
        if (strstr('ns.comprehensive DESC', $order)) {
            $return_shop_list = arrSortByValue($return_shop_list, 'comprehensive', 'desc' );
        } else if (strstr('ns.comprehensive ASC', $order)) {
            $return_shop_list = arrSortByValue($return_shop_list, 'comprehensive', 'asc' );
        }
        $list['data'] = $return_shop_list;
        return $list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::getShopGroup()
     */
    public function getShopGroup($page_index = 1, $page_size = 0, $where = '', $order = '', $field = '*')
    {
        $shop_group = new VslShopGroupModel();
        $list = $shop_group->pageQuery($page_index, $page_size, $where, $order, $field);
        return $list;
    }

    /**
     * 申请店铺
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::addShopApply()
     */
    public function addShopApply($apply_type, $uid, $company_name, $company_province_id, $company_city_id, $company_district_id, $company_address_detail, $company_phone, $company_type, $company_employee_count, $company_registered_capital, $contacts_name, $contacts_phone, $contacts_email, $contacts_card_no, $contacts_card_electronic_1, $contacts_card_electronic_2, $contacts_card_electronic_3, $business_licence_number, $business_sphere, $business_licence_number_electronic, $shop_name, $apply_state, $apply_message, $apply_year, $shop_group_name, $shop_group_id, $paying_money_certificate, $paying_money_certificate_explain, $paying_amount, $recommend_uid, $post_data='')
    {
        $user = new UserModel();
        // 得到当前会员的信息
        $shop_apply = new VslShopApplyModel();
        $condition['uid'] = $uid;
        $condition['apply_state'] = array(
            "in",
            '1,2'
        );
        $count = $shop_apply->getCount($condition);
        if ($count > 0) {
            return APPLY_REPEAT;
        }
        $instance_type = new InstanceTypeModel();
        $defaultInstanceType = $instance_type->getInfo(['is_default' => 1, 'website_id' => $this->website_id]);//申请店铺入驻店铺默认版本
        if($post_data){
            $data = array(
                "uid" => $uid,
                "shop_type_name" => $defaultInstanceType['type_name'],
                "shop_type_id" => $defaultInstanceType['instance_typeid'],
                "shop_group_name" => $shop_group_name,
                "shop_group_id" => $shop_group_id,
                "shop_name" => $shop_name,
                "post_data" => $post_data,
                "website_id" => $this->website_id
            );
        }else{
            $data = array(
                "apply_type" => $apply_type,
                "uid" => $uid,
                "company_name" => $company_name,
                "company_province_id" => $company_province_id,
                "company_city_id" => $company_city_id,
                "company_district_id" => $company_district_id,
                "company_address_detail" => $company_address_detail,
                "company_phone" => $company_phone,
                "company_type" => $company_type,
                "company_employee_count" => $company_employee_count,
                "company_registered_capital" => $company_registered_capital,
                "contacts_name" => $contacts_name,
                "contacts_phone" => $contacts_phone,
                "contacts_email" => $contacts_email,
                "contacts_card_no" => $contacts_card_no,
                "contacts_card_electronic_1" => $contacts_card_electronic_1,
                "contacts_card_electronic_2" => $contacts_card_electronic_2,
                "contacts_card_electronic_3" => $contacts_card_electronic_3,
                "business_licence_number" => $business_licence_number,
                "business_sphere" => $business_sphere,
                "business_licence_number_electronic" => $business_licence_number_electronic,
                "shop_name" => $shop_name,
                "apply_state" => $apply_state, // 默认输入1
                "apply_message" => $apply_message,
                "apply_year" => $apply_year, // 默认1
                "shop_type_name" => $defaultInstanceType['type_name'],
                "shop_type_id" => $defaultInstanceType['instance_typeid'],
                "shop_group_name" => $shop_group_name,
                "shop_group_id" => $shop_group_id,
                "paying_money_certificate" => $paying_money_certificate,
                "paying_money_certificate_explain" => $paying_money_certificate_explain,
                "paying_amount" => $paying_amount,
                "recommend_uid" => $recommend_uid,
                "website_id" => $this->website_id
            );
        }
        
        $shop_apply->save($data);
        $retval = $shop_apply->apply_id;

        // 如果用户是被拒绝过的重新申请的就删除了以前的拒绝信息
        if (!empty($shop_apply->apply_id)) {
            $shop_apply->destroy([
                'uid' => $this->uid,
                'apply_state' => -1
            ]);
        }

        return $retval;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::getShopDetail()
     */
    public function getShopDetail($shop_id)
    {
        $shop = new VslShopModel();
        $shop_group = new VslShopGroupModel();
        $instance_type = new InstanceTypeModel();
        $shop_company = new VslShopInfoModel();
        $shop_info = array();
        $base_info = $shop->getInfo(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
        $shop_info['base_info'] = $base_info;
        $shop_info['base_info']['shop_logo_img'] = '';
        $shop_info['base_info']['shop_evaluate'] = $this->getShopEvaluate($shop_id);
        $shop_info['base_info']['has_store'] = 0;
        if(getAddons('store', $this->website_id)){
            $storeServer = new Store();
             $shop_info['base_info']['has_store'] = (int)$storeServer->getStoreSet($shop_id)['is_use'] ? : 0;
        }
        if (!empty($base_info)) {
            $picture = new AlbumPictureModel();
            if (!empty($base_info['shop_logo'])) {
                $shop_logo = $picture->getInfo(['pic_id' =>$base_info['shop_logo']],'pic_cover,pic_cover_mid,pic_cover_micro');
                $shop_info['base_info']['shop_logo_img'] = $shop_logo['pic_cover'];
            }

            $group_info = $shop_group->get($base_info['shop_group_id']);
            $shop_info['group_info'] = $group_info;
            $instance_type_info = $instance_type->get($base_info['shop_type']);
            $shop_info['instance_type_info'] = $instance_type_info;
            $company_info = $shop_company->getInfo(['shop_id' => $shop_id]);
            $shop_info['company_info'] = $company_info;
        }
        return $shop_info;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::getShopInfo()
     */
    public function getShopInfo($shop_id, $field = '*')
    {
        $shop = new VslShopModel();
        $info = $shop->getInfo([
            'shop_id' => $shop_id,
            'website_id' => $this->website_id
        ], $field);
        return $info;
    }

    /**
     * (non-PHPdoc)
     * shop_id int(11) NOT NULL COMMENT '店铺索引id',
     * shop_name varchar(50) NOT NULL COMMENT '店铺名称',
     * shop_type int(11) NOT NULL COMMENT '店铺类型等级',
     * uid int(11) NOT NULL COMMENT '会员id',
     * shop_group_id int(11) NOT NULL COMMENT '店铺分类',
     * shop_company_name varchar(50) DEFAULT NULL COMMENT '店铺公司名称',
     * province_id mediumint(8) UNSIGNED NOT NULL DEFAULT 0 COMMENT '店铺所在省份ID',
     * city_id mediumint(8) UNSIGNED NOT NULL DEFAULT 0 COMMENT '店铺所在市ID',
     * shop_address varchar(100) NOT NULL DEFAULT '' COMMENT '详细地区',
     * shop_zip varchar(10) NOT NULL DEFAULT '' COMMENT '邮政编码',
     * shop_state tinyint(1) NOT NULL DEFAULT 2 COMMENT '店铺状态，0关闭，1开启，2审核中',
     * shop_close_info varchar(255) DEFAULT NULL COMMENT '店铺关闭原因',
     * shop_sort int(11) NOT NULL DEFAULT 0 COMMENT '店铺排序',
     * shop_create_time varchar(10) NOT NULL DEFAULT '0' COMMENT '店铺时间',
     * shop_end_time varchar(10) DEFAULT NULL COMMENT '店铺关闭时间',
     * shop_logo varchar(255) DEFAULT NULL COMMENT '店铺logo',
     * shop_banner varchar(255) DEFAULT NULL COMMENT '店铺横幅',
     * shop_avatar varchar(150) DEFAULT NULL COMMENT '店铺头像',
     * shop_keywords varchar(255) NOT NULL DEFAULT '' COMMENT '店铺seo关键字',
     * shop_description varchar(255) NOT NULL DEFAULT '' COMMENT '店铺seo描述',
     * shop_qq varchar(50) DEFAULT NULL COMMENT 'QQ',
     * shop_ww varchar(50) DEFAULT NULL COMMENT '阿里旺旺',
     * shop_phone varchar(20) DEFAULT NULL COMMENT '商家电话',
     * shop_domain varchar(50) DEFAULT NULL COMMENT '店铺二级域名',
     * shop_domain_times tinyint(1) UNSIGNED DEFAULT 0 COMMENT '二级域名修改次数',
     * shop_recommend tinyint(1) NOT NULL DEFAULT 0 COMMENT '推荐，0为否，1为是，默认为0',
     * shop_credit int(10) NOT NULL DEFAULT 0 COMMENT '店铺信用',
     * shop_desccredit float NOT NULL DEFAULT 0 COMMENT '描述相符度分数',
     * shop_servicecredit float NOT NULL DEFAULT 0 COMMENT '服务态度分数',
     * shop_deliverycredit float NOT NULL DEFAULT 0 COMMENT '发货速度分数',
     * shop_collect int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '店铺收藏数量',
     * shop_stamp varchar(200) DEFAULT NULL COMMENT '店铺印章',
     * shop_printdesc varchar(500) DEFAULT NULL COMMENT '打印订单页面下方说明文字',
     * shop_sales int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '店铺销量',
     * shop_workingtime varchar(100) DEFAULT NULL COMMENT '工作时间',
     * live_store_name varchar(255) DEFAULT NULL COMMENT '商铺名称',
     * live_store_address varchar(255) DEFAULT NULL COMMENT '商家地址',
     * live_store_tel varchar(255) DEFAULT NULL COMMENT '商铺电话',
     * live_store_bus varchar(255) DEFAULT NULL COMMENT '公交线路',
     * shop_vrcode_prefix char(3) DEFAULT NULL COMMENT '商家兑换码前缀',
     * store_qtian tinyint(1) DEFAULT 0 COMMENT '7天退换',
     * shop_zhping tinyint(1) DEFAULT 0 COMMENT '正品保障',
     * shop_erxiaoshi tinyint(1) DEFAULT 0 COMMENT '两小时发货',
     * shop_tuihuo tinyint(1) DEFAULT 0 COMMENT '退货承诺',
     * shop_shiyong tinyint(1) DEFAULT 0 COMMENT '试用中心',
     * shop_shiti tinyint(1) DEFAULT 0 COMMENT '实体验证',
     * shop_xiaoxie tinyint(1) DEFAULT 0 COMMENT '消协保证',
     * shop_huodaofk tinyint(1) DEFAULT 0 COMMENT '货到付款',
     * shop_free_time varchar(10) DEFAULT NULL COMMENT '商家配送时间',
     * shop_region varchar(50) DEFAULT NULL COMMENT '店铺默认配送区域',
     *
     * @see \data\api\IShop::addshop()
     */

    public function addshop($shop_name, $shop_type, $uid, $shop_group_id, $shop_company_name, $province_id, $city_id, $shop_address, $shop_zip, $shop_sort, $recommend_uid = 0, $shop_platform_commission_rate = 0, $margin = 0, $shop_state = 0, $shop_audit = 0)
    {
        $shop = new VslShopModel();
        $condition = array(
            "uid" => $uid
        );
        $count = $shop->getCount($condition);
        // 防止出现重复店铺、重复提交问题
        if ($count > 0) {
            return -1;
        }
        $shop->startTrans();
        try {
            $website = new WebSite();
            $shop_id = $website->addSystemInstance($uid, $shop_name, $shop_type);
            $data = array(
                'shop_id' => $shop_id,
                'uid' => $uid,
                'shop_name' => $shop_name,
                'shop_type' => $shop_type,
                'shop_group_id' => $shop_group_id,
                'shop_company_name' => $shop_company_name,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'shop_address' => $shop_address,
                'shop_zip' => $shop_zip,
                'shop_sort' => $shop_sort,
                'margin' => $margin,
                'shop_platform_commission_rate' => $shop_platform_commission_rate,
                'shop_state' => $shop_state,
                'shop_audit' => $shop_audit,
                'recommend_uid' => $recommend_uid,
                'shop_create_time' => time(),
                'website_id' => $this->website_id
            );
            // 添加店铺
            $retval = $shop->save($data);
            // 添加店铺账户
            $shop_account = new VslShopAccountModel();
            $data_account = array(
                'shop_id' => $shop_id,
                'website_id' => $this->website_id
            );
            $shop_account->save($data_account);
            $shop->commit();
            return $shop_id;
        } catch (\Exception $e) {
            $shop->rollback();
            return $e->getMessage();
        }
    }

    // 店铺创建后续操作
    private function addShopConfig($shop_id)
    {
        $shop_region_agent = new NfxShopRegionAgentConfigModel();
        $count = $shop_region_agent->where([
            "shop_id" => $shop_id,
        ])->count();
        if ($count == 0) {
            // 默认添加
            $shop_region_agent = new NfxShopRegionAgentConfigModel();
            $data = array(
                "shop_id" => $shop_id,
                "create_time" => time()
            );
            $shop_region_agent->save($data);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::dealwithShopApply()
     */
    public function dealwithShopApply($shop_apply_id, $type, $shop_platform_commission_rate = 0, $margin = 0, $shop_audit = 0, $refuse_reason = '')
    {
        $shop_apply = new VslShopApplyModel();
        $ConfigService = new AddonsConfigService();
        $shopInfo = $ConfigService->getAddonsConfig('shop',$this->website_id);
        $shopConfig = [];
        if($shopInfo){
            $shopConfig = json_decode($shopInfo['value'], true);
        }
        if ($type == 'disagree') {
            $retval = $shop_apply->save([
                'apply_state' => -1,
                'refuse_reason' => $refuse_reason
            ], [
                'apply_id' => $shop_apply_id
            ]);
            return $retval;
            // 拒绝审核通过
        } elseif ($type == 'agree') {
            $shop_apply = new VslShopApplyModel();
            // 审核通过
            $shop_apply->startTrans();
            try {
                $shop_apply->save([
                    'apply_state' => 2
                ], [
                    'apply_id' => $shop_apply_id
                ]);
                $apply_data = $shop_apply->get($shop_apply_id);
                $res_data = $this->addshop($apply_data['shop_name'], $apply_data['shop_type_id'], $apply_data['uid'], $apply_data['shop_group_id'], $apply_data['company_name'], $apply_data['company_province_id'], $apply_data['company_city_id'], $apply_data['company_address_detail'], '', '0', $apply_data["recommend_uid"], $shop_platform_commission_rate?:$shopConfig['platform_commission_percentage'], $margin, 1, $shop_audit);

                if ($res_data > 0) {
                    $apply_data['shop_id'] = $res_data;
                    $this->addShopInfo($apply_data);
                    $album_name = "默认相册";
                    $sort = 0;
                    $album = new Album();
                    $add_album = $album->addAlbumClass($album_name, $sort, 0, '', 1, $res_data);

                    $shop_apply->save([
                        'shop_id' => $res_data
                    ], [
                        'apply_id' => $shop_apply_id
                    ]);
                    $shop_apply->commit();
                    return 1;
                } else {
                    $shop_apply->rollback();
                    return $res_data;
                }
            } catch (\Exception $e) {
                $shop_apply->rollback();
                return $e;
            }
        } else {
            return -1;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::getShopApplyList()
     */
    public function getShopApplyList($page_index = 1, $page_size = 0, $where = '', $order = 'apply_id DESC')
    {
        $shop_apply = new VslShopApplyModel();
        $list = $shop_apply->pageQuery($page_index, $page_size, $where, $order, '*');

        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $user = new UserModel();
                $userinfo = $user->getInfo([
                    'uid' => $v['uid']
                ], "*");
                $user_name = "";
                $user_tel = "";
                $user_headimg = '';
                if (count($userinfo) > 0) {
                    $user_name = $userinfo["real_name"]?:$userinfo["user_name"]?:$userinfo["nick_name"];
                    $user_tel = $userinfo["user_tel"];
                    $user_headimg = $userinfo["user_headimg"];
                }
                $list['data'][$k]['real_name'] = $user_name;
                $list['data'][$k]['user_tel'] = $user_tel;
                $list['data'][$k]['user_headimg'] = $user_headimg;
            }
        }

        return $list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::getShopTypeList()
     */
    public function getShopTypeList($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $instance_type = new InstanceTypeModel();
        $checkInstanceType = $instance_type->getInfo(['is_default' => 1, 'website_id' => $this->website_id]);
        $list = $instance_type->pageQuery($page_index, $page_size, $where, $order, '*');
        if($checkInstanceType){
            return $list;
        }
        $websiteService = new WebSite();
        $merchantVersionId = $websiteService->getWebDetail($this->website_id)['merchant_versionid'];
        if(!$merchantVersionId){
            return $list;
        }
        $merchantVersionService = new Merchant();
        $merchantVersion = $merchantVersionService->getMerchantVersionDetail($merchantVersionId);
        if(!$merchantVersion){
            return $list;
        }
        $data = array(
            'type_name' => '默认版本',
            'type_desc' => '默认版本',
            'type_module_array' => $merchantVersion['shop_type_module_array'],
            'create_time' => time(),
            'modify_time' => time(),
            'website_id' => $this->website_id,
            'is_default' => 1
        );
        $result = $instance_type->save($data);
        if($result){
            $list = $instance_type->pageQuery($page_index, $page_size, $where, $order, '*');
        }
        return $list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::addShopGroup()
     */
    public function addShopGroup($group_name, $group_sort, $is_visible)
    {
        $shop_group = new VslShopGroupModel();
        $check = $shop_group->getInfo(['website_id' => $this->website_id,'group_name' => $group_name]);
        if($check){
            return -10010;
        }
        $data = array(
            'group_name' => $group_name,
            'group_sort' => $group_sort,
            'is_visible' => $is_visible,
            'create_time' => time(),
            'modify_time' => time(),
            'website_id' => $this->website_id,
        );
        $shop_group->save($data);
        return $shop_group->shop_group_id;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::updateShopGroup()
     */
    public function updateShopGroup($shop_group_id, $group_name, $group_sort, $is_visible)
    {
        $shop_group = new VslShopGroupModel();
        $check = $shop_group->getInfo(['website_id' => $this->website_id,'group_name' => $group_name,'shop_group_id' => ['<>',$shop_group_id]]);
        if($check){
            return -10010;
        }
        $data = array(
            'is_visible' => $is_visible,
            'group_name' => $group_name,
            'group_sort' => $group_sort,
            'modify_time' => time()
        );
        $shop_group->save($data, [
            'shop_group_id' => $shop_group_id
        ]);
        return $shop_group_id;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::getShopGroupDetail()
     */
    public function getShopGroupDetail($shop_group_id)
    {
        $shop_group = new VslShopGroupModel();
        $info = $shop_group->get($shop_group_id);
        return $info;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::delShopGroup()
     */
    public function delShopGroup($shop_group_id)
    {
        $retval = '';
        $shop = new VslShopModel();
        $shop_list = $shop->getQuery([
            'shop_group_id' => $shop_group_id
        ], 'shop_id', '');
        if (!count($shop_list)) {
            $shop_group = new VslShopGroupModel();
            $retval = $shop_group->destroy([
                'shop_group_id' => $shop_group_id
            ]);
        }
        return $retval;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IShop::getShopApplyDetail()
     */
    public function getShopApplyDetail($apply_id)
    {
        $shop_apply = new VslShopApplyModel();
        $shop_apply_info = $shop_apply->get($apply_id);
        if (!empty($shop_apply_info)) {
            $recommend_name = "--";
            $user = new UserModel();
            $user_info = $user->getInfo(array(
                "uid" => $shop_apply_info["recommend_uid"]
            ));
            if (!empty($user_info)) {
                $recommend_name = $user_info["nick_name"];
            }
            $shop_apply_info["recommend_name"] = $recommend_name;
            // 区域解释
            $province_name = "";
            $city_name = "";
            $district_name = "";
            $province = new ProvinceModel();
            $province_info = $province->getInfo(array(
                "province_id" => $shop_apply_info["company_province_id"]
            ), "*");
            if (count($province_info) > 0) {
                $province_name = $province_info["province_name"];
            }
            $shop_apply_info['province_name'] = $province_name;
            $city = new CityModel();
            $city_info = $city->getInfo(array(
                "city_id" => $shop_apply_info["company_city_id"]
            ), "*");
            if (count($city_info) > 0) {
                $city_name = $city_info["city_name"];
            }
            $shop_apply_info['city_name'] = $city_name;
            $district = new DistrictModel();
            $district_info = $district->getInfo(array(
                "district_id" => $shop_apply_info["company_district_id"]
            ), "*");
            if (count($district_info) > 0) {
                $district_name = $district_info["district_name"];
            }
            $shop_apply_info['district_name'] = $district_name;
        }
        return $shop_apply_info;
    }
    /**
     *
     * 获取申请店铺被拒绝理由
     *
     * 
     */
    public function getApplyRefuseReason($uid)
    {
        $shop_apply = new VslShopApplyModel();
        $shop_apply_info = $shop_apply->getInfo(['uid' => $uid],'refuse_reason');
        if(!$shop_apply_info){
            return '';
        }
        return $shop_apply_info['refuse_reason'];
    }

    /*
     * 获取店铺注册信息
     */
    public function getShopInfoDetail($shop_id)
    {
        $picture = new AlbumPictureModel();
        $shopInfoModel = new VslShopInfoModel();
        $shop_info = $shopInfoModel->getInfo(['shop_id' => $shop_id]);
        $shop_picture = $picture->getInfo(['pic_id' =>$shop_info['shop_logo']],'pic_cover,pic_cover_mid,pic_cover_micro');
        if (empty($shop_picture)) {
            $shop_picture = array(
                'pic_cover' => '',
            );
        }
        $shop_info['picture'] = $shop_picture['pic_cover'];
        return $shop_info;
    }
    /*
     * 获取店铺所有者信息
     */
    public function getShopUserDetail($shop_id,$website_id)
    {
        $shopModel = new VslShopModel();
        $shop_info = $shopModel->getInfo(['shop_id' => $shop_id,'website_id' => $website_id],'uid');
        $user = new UserModel();
        $user_info = $user ->getInfo(['uid' => $shop_info['uid']]);
        return $user_info;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::addShopType()
     */
    public function addShopType($type_name, $type_module_array, $type_desc, $type_sort, $is_default = 0)
    {
        $instance_type = new InstanceTypeModel();
        $check = $instance_type->getInfo(['website_id' => $this->website_id,'type_name' => $type_name]);
        if($check){
            return -10011;
        }
        $data = array(
            'website_id' => $this->website_id,
            'type_name' => $type_name,
            'type_module_array' => $type_module_array,
            'type_desc' => $type_desc,
            'type_sort' => $type_sort,
            'create_time' => time(),
            'modify_time' => time(),
            'is_default' => $is_default
        );
        $instance_type->save($data);
        return $instance_type->instance_typeid;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::updateShopType()
     */
    public function updateShopType($instance_typeid, $type_name, $type_module_array, $type_desc, $type_sort)
    {
        $instance_type = new InstanceTypeModel();
        $check = $instance_type->getInfo(['website_id' => $this->website_id,'type_name' => $type_name, 'instance_typeid' => ['<>',$instance_typeid]]);
        if($check){
            return -10011;
        }
        try {
            $instance_type->startTrans();
            $data = array(
                'instance_typeid' => $instance_typeid,
                'type_name' => $type_name,
                'type_module_array' => $type_module_array,
                'type_desc' => $type_desc,
                'type_sort' => $type_sort,
                'modify_time' => time()
            );
            $result = $instance_type->save($data, [
                'instance_typeid' => $instance_typeid
            ]);

            $instance = new InstanceModel();
            $instance_list = $instance->getQuery([
                'instance_typeid' => $instance_typeid
            ], 'instance_id', '');
            $website = new WebSite();
            $dateArr = $website->getWebCreateTime($this->website_id);
            $path = './public/addons_status/' . $dateArr['year'].'/'.$dateArr['month'].'/'.$dateArr['day'].'/'. $this->website_id;
            if($instance_list){
                $instance_arr = '';
                foreach ($instance_list as $item) {
                    $instance_arr .= $item['instance_id'] . ',';
                }
                if(file_exists($path .'/addons_'.$item['instance_id'])){
                    unlink($path .'/addons_'.$item['instance_id']);
                }
                
                $instance_arr = rtrim($instance_arr, ",");
                $auth_group = new AuthGroupModel();

                $retval = $auth_group->save([
                    'module_id_array' => $type_module_array
                ], [
                    'instance_id' => [['<>',0],array(
                        "IN",
                        $instance_arr
                    ),'and'],
                    'is_system' => 1,
                    'website_id' => $this->website_id,
                ]);
            }
            $instance_type->commit();
            return $result;
        } catch (\Exception $e) {
            $instance_type->rollback();
            $retval = $e->getMessage();
            return 0;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IShop::getShopTypeDetail()
     */
    public function getShopTypeDetail($instance_typeid)
    {
        $instance_type = new InstanceTypeModel();
        $shop_type_info = $instance_type->get($instance_typeid);
        return $shop_type_info;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IShop::updateShopConfigByshop()
     */
    public function updateShopConfigByshop($shop_id, $shop_logo, $shop_banner, $shop_avatar, $shop_qrcode, $shop_qq, $shop_ww, $shop_phone, $shop_keywords, $shop_description, $shop_intro, $shop_name, $group_id)
    {
        $shop = new VslShopModel();
        if(!$shop_name){
            return -8002;
        }
        $checkShopName = $shop->getInfo(['shop_name' => $shop_name]);
        if($checkShopName && $checkShopName['shop_id'] != $shop_id){
            return -8001;
        }
        $data = array(
            'shop_logo' => $shop_logo,
            'shop_banner' => $shop_banner,
            'shop_avatar' => $shop_avatar,
            'shop_qrcode' => $shop_qrcode,
            'shop_qq' => $shop_qq,
            'shop_ww' => $shop_ww,
            'shop_phone' => $shop_phone,
            'shop_keywords' => $shop_keywords,
            'shop_description' => $shop_description,
            'shop_intro' => $shop_intro,
            'shop_group_id' => $group_id,
            'shop_name' => $shop_name
        );
        $res = $shop->save($data, [
            'shop_id' => $shop_id,
            'website_id' => $this->website_id
        ]);
        return $res;
    }

    public function updateCompanyConfigByshop($shopApplyInfo)
    {
        $data = array(
            'company_name' => $shopApplyInfo['company_name'],
            'company_province_id' => $shopApplyInfo['company_province_id'],
            'company_city_id' => $shopApplyInfo['company_city_id'],
            'company_district_id' => $shopApplyInfo['company_district_id'],
            'company_address_detail' => $shopApplyInfo['company_address_detail'],
            'company_phone' => $shopApplyInfo['company_phone'],
            'company_employee_count' => $shopApplyInfo['company_employee_count'],
            'company_registered_capital' => $shopApplyInfo['company_registered_capital'],
            'contacts_name' => $shopApplyInfo['contacts_name'],
            'contacts_phone' => $shopApplyInfo['contacts_phone'],
            'contacts_email' => $shopApplyInfo['contacts_email'],
            'company_type' => $shopApplyInfo['company_type'],
            'contacts_card_no' => $shopApplyInfo['contacts_card_no'],
            'contacts_card_electronic_1' => $shopApplyInfo['contacts_card_electronic_1'],
            'contacts_card_electronic_2' => $shopApplyInfo['contacts_card_electronic_2'],
            'contacts_card_electronic_3' => $shopApplyInfo['contacts_card_electronic_3'],
            'business_licence_number' => $shopApplyInfo['business_licence_number'],
            'business_sphere' => $shopApplyInfo['business_sphere'],
            'business_licence_number_electronic' => $shopApplyInfo['business_licence_number_electronic']
        );
        $shop = new VslShopInfoModel();
        $check = $shop->getInfo(['shop_id' => $shopApplyInfo['shop_id'], 'website_id' => $this->website_id]);
        if ($check) {
            $res = $shop->save($data, [
                'shop_id' => $shopApplyInfo['shop_id'],
                'website_id' => $this->website_id
            ]);
        } else {
            $data['shop_id'] = $shopApplyInfo['shop_id'];
            $data['website_id'] = $this->website_id;
            $res = $shop->save($data);
        }
        return $res;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \data\api\IShop::updateShopConfigByPlatform()
     */
    public function updateShopConfigByPlatform($shopInfo)
    {
        $shopModel = new VslShopModel();
        $check = $shopModel->getInfo(['website_id' => $this->website_id,'shop_name' => $shopInfo['shop_name'], 'shop_id' => ['<>', $shopInfo['shop_id']]]);
        if($check){
            return -8001;
        }
        try {
            
            $shopModel->startTrans();
            $data = array(
                'shop_name' => $shopInfo['shop_name'],
                'shop_group_id' => $shopInfo['shop_group_id'],
                'shop_platform_commission_rate' => $shopInfo['shop_platform_commission_rate'],
                'shop_type' => $shopInfo['shop_type'],
                'shop_credit' => $shopInfo['shop_credit'],
                'shop_desccredit' => $shopInfo['shop_desccredit'],
                'shop_servicecredit' => $shopInfo['shop_servicecredit'],
                'shop_deliverycredit' => $shopInfo['shop_deliverycredit'],
                'store_qtian' => $shopInfo['store_qtian'],
                'shop_zhping' => $shopInfo['shop_zhping'],
                'shop_erxiaoshi' => $shopInfo['shop_erxiaoshi'],
                'shop_tuihuo' => $shopInfo['shop_tuihuo'],
                'shop_shiyong' => $shopInfo['shop_shiyong'],
                'shop_shiti' => $shopInfo['shop_shiti'],
                'shop_xiaoxie' => $shopInfo['shop_xiaoxie'],
                'shop_huodaofk' => $shopInfo['shop_huodaofk'],
                'shop_state' => $shopInfo['shop_state'],
                'shop_audit' => $shopInfo['shop_audit'],
                'shop_close_info' => $shopInfo['shop_close_info'],
                'margin' => $shopInfo['margin'],
                'shop_sort' => $shopInfo['shop_sort'],
            );
            
            $res = $shopModel->save($data, [
                'shop_id' => $shopInfo['shop_id'],
                'website_id' => $this->website_id
            ]);

            $instanceModel = new InstanceModel();
            $instanceModel->save(['instance_typeid' => $shopInfo['shop_type']], [
                'instance_id' => $shopInfo['shop_id'],
                'website_id' => $this->website_id
            ]);


            $shop = $shopModel->getInfo([
                'shop_id' => $shopInfo['shop_id'],
                'website_id' => $this->website_id
            ], 'uid');
            $adminUser = new AdminUserModel();
            $group = $adminUser->getInfo([
                'uid' => $shop['uid']
            ], 'group_id_array');
            $shoptypeModel = new InstanceTypeModel();
            $shoptype = $shoptypeModel->getInfo([
                'instance_typeid' => $shopInfo['shop_type']
            ], 'type_module_array');
            if ($shoptype) {
                $auth_group = new AuthGroupModel();
                $auth_group->save([
                    'module_id_array' => $shoptype['type_module_array']
                ], [
                    'group_id' => $group['group_id_array']
                ]);
            }
            if(!$shopInfo['shop_audit']){
                $goodsModel = new \data\model\VslGoodsModel();
                $goodsModel->isUpdate(true)->save(['state' => 1],['website_id' => $this->website_id, 'shop_id' => $shopInfo['shop_id'], 'state' => 11]);
                $goodsModel->isUpdate(true)->save(['state' => 10],['website_id' => $this->website_id, 'shop_id' => $shopInfo['shop_id'], 'state' => 12]);
            }
            $shopModel->commit();
            return $res;
        } catch (\Exception $e) {
            $shopModel->rollback();
            $retval = $e->getMessage();
            return 0;
        }
    }

    public function updateShopApply($apply_id, $company_name, $company_province_id, $company_city_id, $company_district_id, $company_address_detail, $company_phone, $company_employee_count, $company_registered_capital, $contacts_name, $contacts_phone, $contacts_email, $business_licence_number, $business_sphere, $business_licence_number_electronic, $organization_code, $organization_code_electronic, $general_taxpayer, $bank_account_name, $bank_account_number, $bank_name, $bank_code, $bank_address, $bank_licence_electronic, $is_settlement_account, $settlement_bank_account_name, $settlement_bank_account_number, $settlement_bank_name, $settlement_bank_code, $settlement_bank_address, $tax_registration_certificate, $taxpayer_id, $tax_registration_certificate_electronic)
    {
        $data = array(
            'company_name' => $company_name,
            'company_province_id' => $company_province_id,
            'company_city_id' => $company_city_id,
            'company_district_id' => $company_district_id,
            'company_address_detail' => $company_address_detail,
            'company_phone' => $company_phone,
            'company_employee_count' => $company_employee_count,
            'company_registered_capital' => $company_registered_capital,
            'contacts_name' => $contacts_name,
            'contacts_phone' => $contacts_phone,
            'contacts_email' => $contacts_email,
            'business_licence_number' => $business_licence_number,
            'business_sphere' => $business_sphere,
            'business_licence_number_electronic' => $business_licence_number_electronic,
            'organization_code' => $organization_code,
            'organization_code_electronic' => $organization_code_electronic,
            'general_taxpayer' => $general_taxpayer,
            'bank_account_name' => $bank_account_name,
            'bank_account_number' => $bank_account_number,
            'bank_name' => $bank_name,
            'bank_code' => $bank_code,
            'bank_address' => $bank_address,
            'bank_licence_electronic' => $bank_licence_electronic,
            'is_settlement_account' => $is_settlement_account,
            'settlement_bank_account_name' => $settlement_bank_account_name,
            'settlement_bank_account_number' => $settlement_bank_account_number,
            'settlement_bank_name' => $settlement_bank_name,
            'settlement_bank_code' => $settlement_bank_code,
            'settlement_bank_address' => $settlement_bank_address,
            'tax_registration_certificate' => $tax_registration_certificate,
            'taxpayer_id' => $taxpayer_id,
            'tax_registration_certificate_electronic' => $tax_registration_certificate_electronic
        );
        $shop_apply = new VslShopApplyModel();
        $res = $shop_apply->save($data, [
            'apply_id' => $apply_id,
            'website_id' => $this->website_id
        ]);
        return $res;
    }

    /**
     * 用户店铺消费(non-PHPdoc)
     *
     * @see \data\api\IOrder::getShopUserConsume()
     */
    public function getShopUserConsume($shop_id, $uid)
    {
        $order = new VslOrderModel();
        $money = $order->Query([
            'buyer_id' => $uid, 'order_status' => 4
        ], 'order_money');
        if ($money) {
            return array_sum($money);
        } else {
            return 0;
        }

    }

    public function getUserOrderSum($uid)
    {
        $order = new VslOrderModel();
        $num = $order->Query([
            'buyer_id' => $uid, 'order_status' => 4
        ], 'order_id');
        if ($num) {
            return count($num);
        } else {
            return 0;
        }

    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopCommissionWithdrawList()
     */
    public function getShopAccountWithdrawList($page_index, $page_size = 0, $condition = '', $shop_name, $order = '')
    {
        // TODO Auto-generated method stub
        $shop_account_withdraw = new VslShopWithdrawModel();
        if ($shop_name) {
            $condition["sp.shop_name"] = array('like', '%' . $shop_name . '%');
        }
        $list = $shop_account_withdraw->getViewList($page_index, $page_size, $condition, $order);
        foreach ($list['data'] as $k => $v) {
            if ($v['type'] == 1 || $v['type'] == 4) {
                $v['type'] = '银行卡';
            } elseif ($v['type'] == 2) {
                $v['type'] = '微信';
            } elseif ($v['type'] == 3) {
                $v['type'] = '支付宝';
            }
            $v['cash'] = '¥' . $v['cash'];
            $v['ask_for_date'] = date('Y-m-d H:i:s', $v['ask_for_date']);
            if($v['payment_date']>0){
                $v['payment_date'] = date('Y-m-d H:i:s', $v['payment_date']);
            }else{
                $v['payment_date'] = '未到账';
            }

        }
        return $list;
    }
    public function getShopWithdrawalCount($condition)
    {
        $commission_withdraw = new VslShopWithdrawModel();
        $user_sum = $commission_withdraw->where($condition)->count();
        if ($user_sum) {
            return $user_sum;
        } else {
            return 0;
        }
    }
    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopBankAccountList()
     */
    public function getShopBankAccountAll($page_index, $page_size = 0, $condition = '', $order = '')
    {
        // TODO Auto-generated method stub
        $shop_bank_account = new VslShopBankAccountModel();
        $all = $shop_bank_account->pageQuery($page_index, $page_size, $condition, $order, "*");
        return $all;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::addShopBankAccount()
     */
    public function addShopBankAccount($shop_id, $type, $realname, $account_number, $remark,$bank_name,$bank_type,$bank_card)
    {
        // TODO Auto-generated method stub
        $shop_bank_account = new VslShopBankAccountModel();
        $shop_bank_account->save(['is_default' => 0], ['shop_id' => $shop_id]);
        $bank = new VslBankModel();
        $bank_names = $bank->getInfo(['bank_code'=>$bank_name],'bank_name')['bank_name'];
        $data = array(
            "shop_id" => $shop_id,
            "website_id" => $this->website_id,
            "type" => $type,
            "realname" => $realname,
            "branch_bank_name" => $bank_names,
            "account_number" => $account_number,
            "remark" => $remark,
            "create_date" => time(),
            'is_default' => 1,
            'bank_type'=>$bank_type,
            'bank_card'=>$bank_card,
            'bank_code'=>$bank_name,
        );
        $shop_bank_account = new VslShopBankAccountModel();
        $shop_bank_account->save($data);

        return $shop_bank_account->id;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::updateShopBankAccount()
     */
    public function updateShopBankAccount($shop_id, $type, $realname, $account_number, $remark,$bank_name,$bank_type,$bank_card, $id)
    {
        // TODO Auto-generated method stub
        $shop_bank_account = new VslShopBankAccountModel();
        $bank = new VslBankModel();
        $bank_names = $bank->getInfo(['bank_code'=>$bank_name],'bank_name')['bank_name'];
        $data = array(
            "type" => $type,
            "branch_bank_name" => $bank_names,
            "realname" => $realname,
            "account_number" => $account_number,
            "remark" => $remark,
            "modify_date" => time(),
            'bank_type'=>$bank_type,
            'bank_card'=>$bank_card,
            'bank_code'=>$bank_name,
        );
        $retval = $shop_bank_account->where(array(
            "shop_id" => $shop_id,
            "id" => $id
        ))->update($data);
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::modifyShopBankAccountIsdefault()
     */
    public function modifyShopBankAccountIsdefault($shop_id, $id)
    {
        // TODO Auto-generated method stub
        $shop_bank_account = new VslShopBankAccountModel();
        $retval = $shop_bank_account->where(array(
            "shop_id" => $shop_id
        ))->update(array(
            "is_default" => 0
        ));
        $retval = $shop_bank_account->where(array(
            "shop_id" => $shop_id,
            "id" => $id
        ))->update(array(
            "is_default" => 1
        ));
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::deleteShopBankAccouht()
     */
    public function deleteShopBankAccouht($condition)
    {
        // TODO Auto-generated method stub
        $shop_bank_account = new VslShopBankAccountModel();
        $condition['shop_id'] = $this->instance_id;
        $retval = $shop_bank_account->destroy($condition);
        return $retval;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopAccount()
     */
    public function getShopAccount($shop_id)
    {
        // TODO Auto-generated method stub
        $shop_account = new ShopAccount();
        $account_obj = $shop_account->getShopAccount($shop_id);
        return $account_obj;
    }

    /*
     * 店铺申请提现
     * (non-PHPdoc)
     * @see \data\api\IShop::applyShopCommissionWithdraw()
     */
    public function applyShopAccountWithdraw($shop_id, $bank_account_id, $cash)
    {
        $Config = new WebConfig();
        $withdraw_type = $Config->getConfig(0, 'WITHDRAW_BALANCE');
        $withdraw_type['value'] = json_decode($withdraw_type['value'], true);
        $is_examine = $withdraw_type['value']['is_examine'];
        $make_money = $withdraw_type['value']['make_money'];
        if($is_examine==1 ){//自动审核
            $status= 2;
        }else{
            $status= 1;
        }
        $charge = 0;
        //提现手续费
        if($withdraw_type['value']['withdraw_poundage']) {
            $charge = twoDecimal($cash * $withdraw_type['value']['withdraw_poundage']/100);//手续费
            if($withdraw_type['value']['withdrawals_end'] && $withdraw_type['value']['withdrawals_begin']){
                if ($cash <= $withdraw_type['value']['withdrawals_end'] && $cash >=  $withdraw_type['value']['withdrawals_begin']) {
                    $charge = 0;//免手续费区间
                }
            }
        }
        // 查询店铺的账户情况
        $shop_account_obj = $this->getShopAccount($shop_id);
        if($cash+$charge<= $shop_account_obj['shop_total_money']){
            $service_charge= $cash;
        }else if($cash-$charge>=0){
            $service_charge = $cash-$charge;
        }else{
            return USER_NO_WITHDRAW;
        }
        // 判断是否店铺提现设置是否为空 是否启用
        if ($withdraw_type['is_use'] == 0) {
            $result['code'] = -1;
            $result['message'] = USER_WITHDRAW_NO_USE;
            return $result;
        }
        // 最小提现额判断
        if ($cash < $withdraw_type['value']["withdraw_cash_min"]) {
            $result['code'] = -1;
            $result['message'] = USER_WITHDRAW_MIN;
            return $result;
        }

        $bank = new VslShopBankAccountModel();
        $bank_account_info = $bank->getInfo(['id'=>$bank_account_id]);
        if($bank_account_info['type']==2){//微信支付
            $user = new UserModel();
            $wx_openid = $user->getInfo(['website_id'=>$this->website_id,'is_member'=>1,'user_tel'=>$bank_account_info['account_number']],'wx_openid')['wx_openid'];
        }
        $shop_account = new shopAccount();
        $rate = $shop_account->getShopAccountRate($shop_id);
        $platform_money = abs(twoDecimal($rate*$cash/100));
        if($bank_account_info['type']==1 || $bank_account_info['type']==4){
            if($withdraw_type['value']['withdraw_message']){
                $withdraw_message = explode(',',$withdraw_type['value']['withdraw_message']);
                if(in_array(4,$withdraw_message)){
                    $bank_account_info['type'] = 4;
                }
            }
        }
        // 判断店铺金额是否够
        if ($shop_account_obj["shop_total_money"] >= $cash+$platform_money+$charge) {
            $withdraw_no = $this->getWithdrawNo();
            $shop_account_withdraw = new VslShopWithdrawModel();
            $data = array(
                "shop_id" => $shop_id,
                "withdraw_no" => $withdraw_no,
                "type" => $bank_account_info["type"],
                "account_number" => $bank_account_info["account_number"],
                "realname" => $bank_account_info["realname"],
                "remark" => $bank_account_info["remark"],
                "platform_money"=>$platform_money,
                "service_charge"=>$service_charge,
                'charge'=>(-1)*$charge,
                "cash" => (-1)*$cash,
                "uid"=>$this->uid,
                "status" => $status,
                "ask_for_date" => time(),
                "website_id" => $this->website_id
            );
            $id = $shop_account_withdraw->save($data);
            if ($shop_account_withdraw->id > 0) {
                $shop_account_service = new ShopAccount();
                if($is_examine==1 && $make_money==1){//自动审核,自动打款
                   $res =  $shop_account_service->addShopAccountData($shop_id,  $cash*(-1), $id,$is_examine,$make_money,$wx_openid,$shop_account_withdraw->withdraw_no,$bank_account_info['type'],$bank_account_info['account_number'],$service_charge,$charge,$platform_money);
                }
                if($is_examine==1 && $make_money==2){//自动审核,手动打款
                    $res =$shop_account_service->addShopAccountData($shop_id,  $cash*(-1),  $id, $is_examine,$make_money,$wx_openid,$shop_account_withdraw->withdraw_no,$bank_account_info['type'],$bank_account_info['account_number'],$service_charge,$charge,$platform_money);
                }
                if($is_examine==2 && $make_money==1){//手动审核,自动打款
                    $res =$shop_account_service->addShopAccountData($shop_id, $cash*(-1),  $id, $is_examine,$make_money,$wx_openid,$shop_account_withdraw->withdraw_no,$bank_account_info['type'],$bank_account_info['account_number'],$service_charge,$charge,$platform_money);
                }
                if($is_examine==2 && $make_money==2){//手动审核,手动打款
                    $res = $shop_account_service->addShopAccountData($shop_id,$cash*(-1), $id, $is_examine,$make_money,$wx_openid,$shop_account_withdraw->withdraw_no,$bank_account_info['type'],$bank_account_info['account_number'],$service_charge,$charge,$platform_money);
                }
            }
            return $res;
        } else {
            // 店铺账户可提现资金不足
            return 'USER_NO_WITHDRAW';
        }
    }

    /*
     * 店铺提现审核
     * (non-PHPdoc)
     * @see \data\api\IShop::shopAccountWithdrawAudit()
     */
    public function shopAccountWithdrawAudit($id, $status, $memo)
    {
        // TODO Auto-generated method stub
        $shop_account_withdraw = new VslShopWithdrawModel();
        $shop_account_service = new ShopAccountService();
            // 得到当前提现的具体信息
            $shop_account_withdraw_info = $shop_account_withdraw->getInfo(['id'=>$id],'*');
            if ($status == 2) {
                // 平台通过申请，更新平台的账户情况
                $retval= $shop_account_service->addAuditShopAccountData($shop_account_withdraw_info["platform_money"],$shop_account_withdraw_info["charge"],$shop_account_withdraw_info["service_charge"],$id,$shop_account_withdraw_info['shop_id'],$shop_account_withdraw_info["cash"],$shop_account_withdraw_info["uid"],$shop_account_withdraw_info["withdraw_no"],$shop_account_withdraw_info["type"],$shop_account_withdraw_info["account_number"]);
            }
            if ($status == -1) {
                // 平台审核不通过，给店铺打回一笔金额
                $retval=$shop_account_service->addShopAccountRecords($shop_account_withdraw_info["platform_money"],$shop_account_withdraw_info["charge"],$shop_account_withdraw_info["service_charge"],$shop_account_withdraw_info["cash"],$id,$shop_account_withdraw_info['shop_id'],$status, "店铺申请提现, 平台审核不通过。");
            }
            if ($status == 4) {
                // 平台拒绝提现，给店铺打回一笔金额
                $retval=$shop_account_service->addShopAccountRecords($shop_account_withdraw_info["platform_money"],$shop_account_withdraw_info["charge"],$shop_account_withdraw_info["service_charge"],$shop_account_withdraw_info["cash"],$id,$shop_account_withdraw_info['shop_id'],$status, "店铺申请提现, 平台拒绝提现。");
            }
            if ($status == 3) {
                // 平台同意打款，更新平台的账户情况
                $retval= $shop_account_service->addAgreeShopAccountData($shop_account_withdraw_info["service_charge"],$id,$shop_account_withdraw_info['shop_id'],$shop_account_withdraw_info["cash"],'店铺申请提现待打款，平台同意在线打款。');
            }
            if ($status == 5) {
                // 平台同意打款，更新平台的账户情况
                $retval= $shop_account_service->addAgreeShopAccountDatas($shop_account_withdraw_info["service_charge"],$id,$shop_account_withdraw_info['shop_id'],$shop_account_withdraw_info["cash"],'店铺申请提现待打款，平台同意手动打款。');
            }
            return $retval;
    }

    /**
     *
     * {@inheritdoc}店铺提现详情
     *
     * @see \ata\api\IWeixin::getKeyReplyDetail($id)
     */
    public function shopAccountWithdrawDetail($id)
    {
        $shop_account_withdraw = new VslShopWithdrawModel();
        $info = $shop_account_withdraw->getInfo(['id' => $id], '*');
        if (!empty($info)) {
            $info['ask_for_date'] = date('Y-m-d H:i:s', $info['ask_for_date']);
            if($info['payment_date']>0){
                $info['payment_date'] = date('Y-m-d H:i:s', $info['payment_date']);
            }else{
                $info['payment_date'] = '未到账';
            }
            $shop = new VslShopModel();
            $info['shop_name'] = $shop->getInfo(['shop_id' => $info['shop_id']], 'shop_name')['shop_name'];
            if ($info['type'] == 1 || $info['type'] == 4) {
                $info['type_name'] = '银行卡';
            } elseif ($info['type'] == 2) {
                $info['type_name'] = '微信';
            } elseif ($info['type'] == 3) {
                $info['type_name'] = '支付宝';
            }
            if ($info['status'] == 2) {
                $info['status'] = '待打款';
            } elseif ($info['status'] == 3) {
                $info['status'] = '已打款';
            } elseif ($info['status'] == -1) {
                $info['status'] = '审核不通过';
            } elseif ($info['status'] == 1) {
                $info['status'] = '待审核';
            } elseif ($info['status'] == 4) {
                $info['status'] = '拒绝打款';
            } elseif ($info['status'] == 5) {
                $info['status'] = '打款失败';
            }
        }
        return $info;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \ata\api\IWeixin::getKeyReplyDetail($id)
     */
    public function getShopBankAccountDetail($shop_id, $id)
    {
        $shop_bank_account = new VslShopBankAccountModel();
        $info = $shop_bank_account->getInfo(['id'=>$id],'');
        return $info;
    }

    /**
     * 生成佣金流水号
     */
    private function getWithdrawNo()
    {
        $no_base = date("ymdhis", time());
        $withdraw_no = $no_base . rand(111, 999);
        return $withdraw_no;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopAccountCountList()
     */
    public function getShopAccountCountList($page_index, $page_size = 0, $condition = '', $order = '', $search_text)
    {
        // TODO Auto-generated method stub
        $shop = new VslShopModel();
        if ($search_text) {
            $shop_id = $shop->Query([
                "shop_name" => array('like', '%' . $search_text . '%')
            ], "shop_id");
            $condition['shop_id'] = ['in', $shop_id];
        }
        $shop_account = new VslShopAccountModel();
        $list = $shop_account->pageQuery($page_index, $page_size, $condition, $order, '*');
        $shop_withdraw = new VslShopWithdrawModel();
        foreach ($list["data"] as $k => $v) {
            $shop = new VslShopModel();
            $shop_info = $shop->getInfo([
                "shop_id" => $v["shop_id"],
            ], "shop_name,shop_logo,shop_platform_commission_rate");
            $shop_account_records = new VslShopAccountModel();
            $shop_account_info = $shop_account_records->getInfo([
                "shop_id" => $v["shop_id"]
            ], "*");
            $shop_withdraw_cash = $shop_withdraw->Query(["shop_id" => $v["shop_id"], 'status' => [['>', 0], ['<', 3]]], 'cash');
            $shop_logo = $shop_info["shop_logo"];
            $shop_name = $shop_info["shop_name"];
            $list["data"][$k]["shop_logo"] = $shop_logo;
            $list["data"][$k]["shop_name"] = $shop_name;
            $list["data"][$k]["shop_entry"] = $shop_account_info['shop_entry'];
            $list["data"][$k]["withdraw_ing"] = array_sum($shop_withdraw_cash);
            $list["data"][$k]["shop_platform_commission_rate"] = $shop_info['shop_platform_commission_rate'];
            $list["data"][$k]["shop_total_money"] = $shop_account_info['shop_total_money'];
        }
        return $list;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopAccountRecordsList()
     */
    public function getShopAccountRecordsList($page_index, $page_size = 0, $condition = '', $order = '')
    {
        // TODO Auto-generated method stub
        $shop_account_records = new VslShopAccountRecordsModel();
        $list = $shop_account_records->pageQuery($page_index, $page_size, $condition, $order, '*');
        foreach ($list["data"] as $k => $v) {
            // var_dump($v["shop_id"]);
            $shop = new VslShopModel();
            $shop_info = $shop->getInfo([
                "shop_id" => $v["shop_id"]
            ], "shop_name,shop_logo");
            $shop_logo = $shop_info["shop_logo"];
            $shop_name = $shop_info["shop_name"];
            $list["data"][$k]["shop_logo"] = $shop_logo;
            $list["data"][$k]["shop_name"] = $shop_name;
        }
        return $list;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopOrderReturnList()
     */
    public function getShopOrderReturnList($page_index, $page_size = 0, $condition = '', $order = '')
    {
        $shop_order_return_model = new VslShopOrderReturnModel();
        $list = $shop_order_return_model->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopOrderAccountRecordsList()
     */
    public function getShopOrderAccountRecordsList($page_index, $page_size = 0, $condition = '', $order = '')
    {
        $order_goods = new VslOrderGoodsViewModel();
        $return = $order_goods->getOrderGoodsViewList($page_index, $page_size, $condition, $order);
        return $return;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopAll()
     */
    public function getShopAll($condition)
    {
        // TODO Auto-generated method stub
        $shop = new VslShopModel();
        $shop_all = $shop->where($condition)
            ->order(" shop_sales desc ")
            ->limit(10)
            ->select();
        return $shop_all;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopAccountRecordCount()
     */
    public function getShopAccountRecordCount($start_date, $end_date, $shop_id)
    {
        // TODO Auto-generated method stub
        // 可提现余额
        $shop_account_record = new VslShopAccountRecordsModel();
        $shop_account_withdraw = new VslShopWithdrawModel();
        $withdraw_condition["shop_id"] = $shop_id;
        $money_condition["shop_id"] = $shop_id;
        if ($start_date != "") {
            $withdraw_condition["ask_for_date"][] = [
                ">",
                getTimeTurnTimeStamp($start_date)
            ];
            $money_condition["create_time"][] = [
                ">",
                getTimeTurnTimeStamp($start_date)
            ];
        }
        if ($end_date != "") {
            $withdraw_condition["ask_for_date"][] = [
                "<",
                getTimeTurnTimeStamp($end_date)
            ];
            $money_condition["create_time"][] = [
                "<",
                getTimeTurnTimeStamp($end_date)
            ];
        }
        // 已提现
        $withdraw_condition["status"] = 1;
        $withdraw_cash = $shop_account_withdraw->where($withdraw_condition)->sum("cash");
        // 提现审核中
        $withdraw_condition["status"] = 0;
        $withdraw_isaudit = $shop_account_withdraw->where($withdraw_condition)->sum("cash");
        $shop_order_account_record = new VslShopOrderReturnModel();
        // 店铺营业额
        $shop_order_money = $shop_order_account_record->where($money_condition)->sum("order_pay_money");
        $array = array(
            "withdraw_cash" => $withdraw_cash,
            "withdraw_isaudit" => $withdraw_isaudit,
            "shop_order_money" => $shop_order_money
        );
        return $array;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopAccountSales()
     */
    public function getShopAccountSales($condition)
    {
        // TODO Auto-generated method stub
        $shop_order_account_records = new VslShopOrderReturnModel();
        // 店铺销售额
        $shop_sales = $shop_order_account_records->where($condition)->sum("order_pay_money");

        // 平台金额
        $platform_money = $shop_order_account_records->where($condition)->sum("platform_money");

        // 店铺金额
        $shop_money = $shop_sales - $platform_money;
        return [
            "shop_sale" => $shop_sales,
            "platform_money" => $platform_money,
            "shop_money" => $shop_money
        ];
    }

    public function updateShopPlatformCommissionRate($shop_id, $shop_platform_commission_rate)
    {
        $shop_account = new VslShopAccountModel();
        $res = $shop_account->save([
            "shop_platform_commission_rate" => $shop_platform_commission_rate
        ], [
            'shop_id' => $shop_id,
            'website_id' => $this->website_id
        ]);
        return $res;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopCount()
     */
    public function getShopCount($condition)
    {
        // TODO Auto-generated method stub
        $shop = new VslShopModel();
        $shop_list = $shop->getQuery($condition, "count(shop_id) as count", "");
        return $shop_list[0]["count"];
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::getShopWithdrawCount()
     */
    public function getShopWithdrawCount($condition)
    {
        // TODO Auto-generated method stub
        $shop_account_withdraw = new VslShopWithdrawModel();
        $withdraw_isaudit = $shop_account_withdraw->getQuery($condition, "sum(cash) as sum", '');
        return $withdraw_isaudit[0]["sum"];
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::addShopBankAccount()
     */
    public function addMemberWithdrawSetting($shop_id, $withdraw_cash_min, $withdraw_multiple, $withdraw_poundage, $withdraw_message, $withdraw_account_type)
    {
        // TODO Auto-generated method stub
        $member_withdraw_setting = new VslMemberWithdrawSettingModel();
        $data = array(
            "shop_id" => $shop_id,
            "withdraw_cash_min" => $withdraw_cash_min,
            "withdraw_multiple" => $withdraw_multiple,
            "withdraw_poundage" => $withdraw_poundage,
            "withdraw_message" => $withdraw_message,
            "withdraw_account_type" => $withdraw_account_type,
            "create_time" => time()
        );
        $member_withdraw_setting->save($data);
        return $member_withdraw_setting->id;
    }

    /*
     * (non-PHPdoc)
     * @see \data\api\IShop::updateShopBankAccount()
     */
    public function updateMemberWithdrawSetting($shop_id, $withdraw_cash_min, $withdraw_multiple, $withdraw_poundage, $withdraw_message, $withdraw_account_type, $id)
    {
        // TODO Auto-generated method stub
        $member_withdraw_setting = new VslMemberWithdrawSettingModel();
        $data = array(
            "withdraw_cash_min" => $withdraw_cash_min,
            "withdraw_multiple" => $withdraw_multiple,
            "withdraw_poundage" => $withdraw_poundage,
            "withdraw_message" => $withdraw_message,
            "withdraw_account_type" => $withdraw_account_type,
            "modify_time" => time()
        );
        $retval = $member_withdraw_setting->where(array(
            "shop_id" => $shop_id,
            "id" => $id
        ))->update($data);
        return $retval;
    }

    /**
     * 获取提现设置信息
     *
     * @param string $field
     */
    public function getWithdrawInfo($shop_id)
    {
        $member_withdraw_setting = new VslMemberWithdrawSettingModel();
        $info = $member_withdraw_setting->getInfo([
            "shop_id" => $shop_id
        ]);

        return $info;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IShop::addPlatformShop()
     */
    public function addPlatformShop($shopInfo, $shopApplyInfo,$uid)
    {
        $shop_model = new VslShopModel();
        $check = $shop_model->getInfo(['website_id' => $this->website_id,'shop_name' => $shopInfo['shop_name']]);
        $user = new UserModel();
        // $check_tel = $user->getInfo(['user_tel'=>$shopInfo['user_account'],'is_member'=>1,'website_id'=>$this->website_id]);
        // if($check_tel){
        //     return -3;
        // }
        if($check){
            return -8001;
        }
        $shop_model->startTrans();
        try {
            $user_service = new User();
            // $res = $user_service->add($shopInfo['user_account'], $shopInfo['user_pwd'], '', $shopInfo['user_account'], 1, 1, '', '', '', '', '', 0, $this->website_id, 'admin');
            $res = $uid;
            $uid = $res;
            
            if ($res > 0) {
                $res = $this->addshop($shopInfo['shop_name'], $shopInfo['shop_type'], $res, $shopInfo['shop_group_id'], '', '', '', '', '', $shopInfo['shop_sort'], 0, $shopInfo['shop_platform_commission_rate'], $shopInfo['margin'], $shopInfo['shop_state'], $shopInfo['shop_audit']);
            }
            if ($res > 0) {
                $shopApplyInfo['shop_id'] = $res;
                $this->addShopInfo($shopApplyInfo);
            }
            // if ($uid > 0) {
            //     $member = new VslMemberModel();
            //     // 获取默认会员等级id
            //     $member_level = new VslMemberLevelModel();
            //     $level_info = $member_level->getInfo([
            //         'is_default' => 1,
            //         'website_id' => $this->website_id,
            //     ], 'level_id');
            //     $member_level_id = $level_info['level_id'];
            //     $data = array(
            //         'uid' => $uid,
            //         'member_level' => $member_level_id,
            //         'mobile' => $shopInfo['user_account'],
            //         'reg_time' => time(),
            //         'website_id' => $this->website_id
            //     );
            //     $member->save($data);
            //     // 添加会员账户
            //     $member_account = new VslMemberAccountModel();
            //     $data1 = array(
            //         'uid' => $uid,
            //         'website_id' => $this->website_id
            //     );
            //     $member_account->save($data1);
            // }
            $website = new WebSite();
            $dateArr = $website->getWebCreateTime($this->website_id);
            $path = './public/addons_status/' . $dateArr['year'].'/'.$dateArr['month'].'/'.$dateArr['day'].'/'. $this->website_id;
            if(file_exists($path .'/addons_'.$res)){
                unlink($path .'/addons_'.$res);
            }
            $shop_model->commit();
            return $res;
        } catch (\Exception $e) {
            $shop_model->rollback();
            return $e->getMessage();
        }


    }

    /*
     * 添加店铺注册信息
     */
    public function addShopInfo($shopApplyInfo)
    {
        $shopInfoModel = new VslShopInfoModel();
        $data = [
            'apply_type' => $shopApplyInfo['apply_type'],
            'company_name' => $shopApplyInfo['company_name'],
            'company_province_id' => $shopApplyInfo['company_province_id'],
            'company_city_id' => $shopApplyInfo['company_city_id'],
            'company_district_id' => $shopApplyInfo['company_district_id'],
            'company_address_detail' => $shopApplyInfo['company_address_detail'],
            'company_phone' => $shopApplyInfo['company_phone'],
            'company_employee_count' => $shopApplyInfo['company_employee_count'],
            'company_registered_capital' => $shopApplyInfo['company_registered_capital'],
            'contacts_name' => $shopApplyInfo['contacts_name'],
            'contacts_phone' => $shopApplyInfo['contacts_phone'],
            'contacts_email' => $shopApplyInfo['contacts_email'],
            'company_type' => $shopApplyInfo['company_type'],
            'contacts_card_no' => $shopApplyInfo['contacts_card_no'],
            'contacts_card_electronic_1' => $shopApplyInfo['contacts_card_electronic_1'],
            'contacts_card_electronic_2' => $shopApplyInfo['contacts_card_electronic_2'],
            'contacts_card_electronic_3' => $shopApplyInfo['contacts_card_electronic_3'],
            'business_licence_number' => $shopApplyInfo['business_licence_number'],
            'business_sphere' => $shopApplyInfo['business_sphere'],
            'business_licence_number_electronic' => $shopApplyInfo['business_licence_number_electronic'],
            'post_data' => $shopApplyInfo['post_data'],
            'website_id' => $this->website_id
        ];
        $check = $shopInfoModel->getInfo(['shop_id' => $shopApplyInfo['shop_id']], 'id');
        if ($check) {
            $data['update_time'] = time();
            $res = $shopInfoModel->save($data, ['id' => $check['id']]);
        } else {
            $data['create_time'] = time();
            $data['shop_id'] = $shopApplyInfo['shop_id'];
            $res = $shopInfoModel->save($data);
        }
        return $res;
    }

    /**
     * {@inheritdoc}
     * @see \data\api\IShop::updateShopOfflineStoreByshop()
     */
    public function updateShopOfflineStoreByshop($shop_id, $shop_vrcode_prefix, $live_store_name, $live_store_tel, $live_store_address, $live_store_bus, $latitude_longitude)
    {
        $data = array(
            'shop_vrcode_prefix' => $shop_vrcode_prefix,
            'live_store_name' => $live_store_name,
            'live_store_tel' => $live_store_tel,
            'live_store_address' => $live_store_address,
            'live_store_bus' => $live_store_bus,
            'latitude_longitude' => $latitude_longitude

        );
        $shop = new VslShopModel();
        $res = $shop->save($data, ['shop_id' => $shop_id]);
        return $res;
    }

    /*
     * 判断用户是否是店铺超级管理员
     */
    public function getShopByUid($uid)
    {
        $shop = new VslShopModel();
        $base_info = $shop->getInfo(['uid' => $uid], 'id');
        if (!$base_info) {
            return false;
        }
        return $base_info['id'];
    }

    /**
     * (non-PHPdoc)
     *
     * 删除店铺版本
     */
    public function deleteShopLevel($instance_typeid)
    {
        $count = $this->getShopLevelIsUse($instance_typeid);
        $check = $this->checkIsDefault($instance_typeid);
        if ($count > 0) {
            return SHOPLEVEL_ISUSE;
        } 
        if($check){
            return INSTANCE_TYPE_DELETE_ERROR;
        }
        $merchant_version = new InstanceTypeModel();
        $res = $merchant_version->where('instance_typeid', $instance_typeid)->delete();
        return $res;
        
    }

    /*
     * 判断店铺版本下面是否有店铺
     */
    public function getShopLevelIsUse($instance_typeid)
    {
        $instance = new InstanceModel();
        $count = $instance->getCount(['instance_typeid' => $instance_typeid]);
        return $count;
    }
    /*
     * 判断店铺版本是否是默认等级
     */
    public function checkIsDefault($instance_typeid)
    {
        $instance = new InstanceTypeModel();
        $result = $instance->getInfo(['instance_typeid' => $instance_typeid,'is_default' => 1]);
        return $result;
    }

    /*
     * 获取店铺协议
     */
    public function getShopProtocol($key = 'direction')
    {
        $configModule = new ConfigModel();
        $config = new WebConfig();
        // TODO Auto-generated method stub
        $protocol = $configModule->getInfo([
            "key" => $key,
            "website_id" => $this->website_id,
        ], "*");
        if (empty($protocol)) {
            $data = array(
                "title" => "",
                "content" => "",
                "key" => $key
            );
            $res = $config->addConfig(0, $key, json_encode($data), "", 1);
            if (!$res > 0) {
                return null;
            } else {
                $protocol = $configModule->getInfo([
                    "key" => $key,
                    "website_id" => $this->website_id
                ], "*");
            }
        }
        $value = json_decode($protocol["value"], true);
        return $value;
    }

    /*
     * 设置店铺协议
     */
    public function setShopProtocol($value, $key = 'direction')
    {
        $configModule = new ConfigModel();
        $config = new WebConfig();
        $protocol = $configModule->getInfo([
            "key" => $key,
            "website_id" => $this->website_id
        ], "*");
        if (empty($protocol)) {
            $data = array(
                "title" => "",
                "content" => "",
                "key" => $key
            );
            $res = $config->addConfig(0, $key, json_encode($data), "", 1);
        } else {
            $data = array(
                "value" => $value
            );
            $res = $configModule->save($data, [
                "key" => $key,
                "website_id" => $this->website_id
            ]);
        }
        return $res;
    }

    /**
     * 店铺设置
     */
    public function setShopSetting($is_use = 0, $platform_commission_percentage = '0.00')
    {
        $ConfigService = new AddonsConfigService();
        $value = array(
            'platform_commission_percentage' => $platform_commission_percentage//平台抽成比率
        );
        $shop_info = $this->addons_config_module->getInfo([
            "website_id" => $this->website_id,
            "addons" => "shop"
        ], "*");
        if (!empty($shop_info)) {
            $data = array(
                "value" => json_encode($value),
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $this->addons_config_module->save($data, [
                "website_id" => $this->website_id,
                "addons" => 'shop'
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig($value, "店铺设置", $is_use, "shop");
        }
        return $res;
    }

    /*
     * 获取店铺设置
     *
     */
    public function getShopSetting($website_id = 0)
    {
        if ($website_id) {
            $id = $website_id;
        } else {
            $id = $this->website_id;
        }
        $info = $this->addons_config_module->getInfo([
            "website_id" => $id,
            "addons" => "shop"
        ], 'value,is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'platform_commission_percentage' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }

    /**
     * (non-PHPdoc)
     */
    public function addMemberFavouites($fav_id, $fav_type, $log_msg)
    {
        $member_favorites = new VslMemberFavoritesModel();
        $count = $member_favorites->where(array(
            "fav_id" => $fav_id,
            "uid" => $this->uid,
            "fav_type" => $fav_type,
            "website_id" => $this->website_id,
        ))->count("log_id");
        // 检查数据表中，防止用户重复收藏
        if ($count > 0) {
            return 1;
        }
        if ($fav_type == 'shop') {
            $shop = new VslShopModel();
            $shop_info = $shop->getInfo([
                'shop_id' => $fav_id,
                'website_id' => $this->website_id
            ], 'shop_name,shop_logo,shop_collect,shop_state');
            if ($shop_info['shop_state'] != 1){
                return false;
            }
            $data = array(
                'uid' => $this->uid,
                'fav_id' => $fav_id,
                'fav_type' => $fav_type,
                'fav_time' => time(),
                'shop_id' => $fav_id,
                'shop_name' => $shop_info['shop_name'],
                'shop_logo' => empty($shop_info['shop_logo']) ? ' ' : $shop_info['shop_logo'],
                'goods_name' => '',
                'goods_image' => '',
                'log_price' => 0,
                'log_msg' => $log_msg,
                'website_id' => $this->website_id
            );
            $retval = $member_favorites->save($data);
            $shop->save(array(
                'shop_collect' => $shop_info['shop_collect'] + 1
            ), [
                'shop_id' => $fav_id,
                'website_id' => $this->website_id
            ]);
            return $retval;
        }
    }

    /**
     * (non-PHPdoc)
     */
    public function deleteMemberFavorites($fav_id, $fav_type)
    {
        $member_favorites = new VslMemberFavoritesModel();
        if (!empty($this->uid)) {
            if ($fav_type == 'shop') {
                $shop = new VslShopModel();
                $shop_info = $shop->getInfo([
                    'shop_id' => $fav_id,
                    'website_id' => $this->website_id
                ], 'shop_name,shop_logo,shop_collect,shop_state');
                if ($shop_info['shop_state'] != 1){
                    return false;
                }
                $condition = array(
                    'fav_id' => $fav_id,
                    'fav_type' => $fav_type,
                    'uid' => $this->uid,
                    'website_id' => $this->website_id
                );
                $retval = $member_favorites->destroy($condition);
                $shop_collect = empty($shop_info["shop_collect"]) ? 0 : $shop_info["shop_collect"];
                $shop_collect--;
                if ($shop_collect < 0) {
                    $shop_collect = 0;
                }
                $shop->save([
                    'shop_collect' => $shop_collect
                ], [
                    'shop_id' => $fav_id,
                    'website_id' => $this->website_id
                ]);
                return $retval;
            }
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMember::getMemberFavorites()
     */
    public function getMemberShopsFavoritesList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $fav = new VslMemberFavoritesModel();
        $list = $fav->getShopsFavouitesViewList($page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取店铺评价
     */
    public function getShopEvaluate($shop_id = 0) {
        $shopEvaluate = new VslShopEvaluateModel();
        $count = $shopEvaluate->getCount(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
        $evaluateData = ['shop_desc' => 5, 'shop_service' => 5, 'shop_stic' => 5, 'comprehensive' => 5];
        if (!$count) {
            return $evaluateData;
        }
        $evaluateData['count'] = $count;
        $evaluateData['shop_desc'] = floor(($shopEvaluate->getSum(['shop_id' => $shop_id, 'website_id' => $this->website_id], 'shop_desc') / $count)*10)/10 ?:5;//保留小数点后一位
        $evaluateData['shop_service'] = floor(($shopEvaluate->getSum(['shop_id' => $shop_id, 'website_id' => $this->website_id], 'shop_service') / $count)*10)/10 ?:5;
        $evaluateData['shop_stic'] = floor(($shopEvaluate->getSum(['shop_id' => $shop_id, 'website_id' => $this->website_id], 'shop_stic') / $count)*10)/10 ?: 5;
        $evaluateData['comprehensive'] = floor((($evaluateData['shop_desc'] + $evaluateData['shop_service'] + $evaluateData['shop_stic']) / 3)*10)/10 ?: 5;
        return $evaluateData;
    }
    
    /*
     * 获取店铺入驻申请自定义表单
     */
    /*
     * 获取订单自定义表单
     */
    public function getShopCustomForm(){
        if(!getAddons('customform', $this->website_id)){
            return [];
        }
        $add_config = new AddonsConfigService();
        $customform_info =$add_config->getAddonsConfig("customform",$this->website_id);
        $customform = json_decode($customform_info['value'],true);
        $custom_server = new CustomServer();
        $custom_form=[];
        if($customform['shop_apply_dealer']==1){
            $custom_form_id =  $customform['apply_id'];
            $custom_form_info = $custom_server->getCustomFormDetail($custom_form_id)['value'];
            if($custom_form_info){
                $custom_form =  json_decode($custom_form_info,true);
            }
        }
        return $custom_form;
    }

    /**
     * 获取店铺LOGO
     * @param $shop_id int [店铺id]
     * @return string
     */
    public function getShopLogo($shop_id)
    {
        //如果是自营店就取商城sys_website表的logo
        $shop_logo = '';
        if ($shop_id == 0) {
            $website_service = new WebSite();
            $website = $website_service->getWebSiteInfo($this->website_id);
            $shop_logo = $website['logo'];
        }
        //如果是店铺就取店铺vsl_shop的logo
        $logoId = $base_info = $this->getShopInfo($shop_id, 'shop_logo')['shop_logo'];
        $picture = new AlbumPictureModel();
        if (!empty($logoId)) {
            $shopRes = $picture->getInfo(['pic_id' => $logoId],'pic_cover,pic_cover_mid,pic_cover_micro');
            $shop_logo = $shopRes['pic_cover'];
        }
        return __IMG($shop_logo);
    }
}
