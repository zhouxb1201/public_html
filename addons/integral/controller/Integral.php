<?php
namespace addons\integral\controller;

use addons\coupontype\model\VslCouponModel;
use addons\coupontype\model\VslCouponTypeModel;
use addons\coupontype\server\Coupon;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\giftvoucher\model\VslGiftVoucherRecordsModel;
use addons\integral\model\VslIntegralCategoryModel;
use addons\integral\model\VslIntegralGoodsModel;
use addons\integral\model\VslIntegralGoodsSkuModel;
use addons\integral\model\VslIntegralUserModel;
use addons\miniprogram\model\WeixinAuthModel;
use data\model\VslGoodsModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberRechargeModel;
use data\model\VslOrderModel;
use data\model\WebSiteModel;
use data\service\Member;
use data\model\AddonsConfigModel;
use data\model\DistrictModel;
use data\model\UserModel;
use data\service\Address;
use data\service\Goods;
use addons\integral\service\Integral AS integralServer;
use addons\integral\Integral AS baseIntegral;
use data\service\Goods as GoodsService;
use data\service\Member as MemberService;
use data\service\Order\Order AS OrderBusiness;
use data\service\Order as orderService;
use data\service\GoodsGroup as GoodsGroup;
use data\service\GoodsCategory as GoodsCategory;
use data\service\promotion\GoodsExpress;
use data\service\UnifyPay;
use data\service\WebSite;
use data\service\Order\OrderStatus;
use data\service\promotion\GoodsExpress as GoodsExpressService;
use think\Cookie;
use think\Session;

class Integral extends baseIntegral
{
    public function __construct()
    {
        parent::__construct();
        $this->integralServer = new integralServer();
    }
    /*
     * 添加积分商城的设置
     * **/
    public function addIntegralSetting()
    {
        try {
            $is_integral_open = request()->post('is_integral_open',0);
            $addons_config_model = new AddonsConfigModel();
            $bargain_info = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => 'integral']);
            if (!empty($bargain_info)) {
                $res = $addons_config_model->save(
                    [
                        'is_use' => $is_integral_open,
                        'modify_time' => time(),
                        'value' => ''
                    ],
                    [
                        'website_id' => $this->website_id,
                        'addons' => 'integral'
                    ]
                );
            } else {
                $data['is_use'] = $is_integral_open;
                $data['value'] = '';
                $data['desc'] = '积分商城设置';
                $data['create_time'] = time();
                $data['addons'] = 'integral';
                $data['website_id'] = $this->website_id;
                $res = $addons_config_model->save($data);
            }
            if($res){
                $this->addUserLog('添加积分商城设置',$res);
            }
            setAddons('integral', $this->website_id, $this->instance_id);
            setAddons('integral', $this->website_id, $this->instance_id, true);
            return ajaxReturn($res);
        } catch (\Exception $e) {
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }
    /*
     * 添加积分分类
     * **/
    public function addIntegralCategory()
    {
//        p(request()->param());exit;
        $integral_cate_mdl = new VslIntegralCategoryModel();
        $category_id = request()->param('category_id',0);
        $data['category_name'] = request()->param('category_name','');
        $data['attr_id'] = request()->param('attr_id',0);
        $data['attr_name'] = request()->param('attr_name','');
        $data['sort'] = request()->param('sort','');
        $data['is_visible'] = request()->param('is_visible','');
        $data['category_pic'] = request()->param('category_pic','');
        $data['short_name'] = request()->param('short_name','');
//        p($data);exit;
        if($category_id){
            $condition['integral_category_id'] = $category_id;
            $res = $integral_cate_mdl->save($data, $condition);
        }else{
            $data['website_id'] = $this->website_id;
            $res = $integral_cate_mdl->save($data);
        }
        if($res){
            $this->addUserLog('添加积分商城分类',$res);
        }
        return AjaxReturn($res);
//        var_dump($res);exit;
    }
    /*
     * 修改分类排序
     * **/
    public function changeIntegralCategorySort()
    {
        $interal_server = new integralServer();
        $category_id  = request()->post('category_id',0);
        $sort_val  = request()->post('sort_val',0);
        $bool = $interal_server->updateIntegralCategorySort($category_id, $sort_val);
        if($bool){
            $this->addUserLog('修改积分商城分类排序', $category_id);
        }
        return AjaxReturn($bool);
    }
    /*
     * 删除分类
     * **/
    public function deleteIntegralCategory()
    {
        $integral_cate_mdl = new VslIntegralCategoryModel();
        $category_id  = request()->post('category_id',0);
        $condition['integral_category_id'] = $category_id;
        $bool = $integral_cate_mdl->where($condition)->delete();
        if($bool){
            $this->addUserLog('删除积分商城分类', $category_id);
        }
        return AjaxReturn($bool);
    }
    /*
     * 修改分类名
     * **/
    public function changeIntegralCategoryName()
    {
        $integral_cate_mdl = new VslIntegralCategoryModel();
        $category_id  = request()->post('category_id',0);
        $category_name  = request()->post('category_name','');
        $data['category_name'] = $category_name;
        $condition['integral_category_id'] = $category_id;
        $bool = $integral_cate_mdl->where($condition)->update($data);
        if($bool){
            $this->addUserLog('修改积分商城分类名', $category_id);
        }
        return AjaxReturn($bool);
    }
    /*
     *积分商城商品列表
     * **/
    public function selfIntegralgoodsList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $start_date = request()->post('start_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('start_date'));
            $end_date = request()->post('end_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('end_date'));
            $goods_name = request()->post('goods_name', '');
            $goods_code = request()->post('code', '');
            $state = request()->post('state', '');
            $category_id_1 = request()->post('category_id_1', '');
            $category_id_2 = request()->post('category_id_2', '');
            $category_id_3 = request()->post('category_id_3', '');
            $type = request()->post('type', 0);
            $selectGoodsLabelId = request()->post('selectGoodsLabelId', '');
            $supplier_id = request()->post('supplier_id', '');
            $stock_warning = request()->post("stock_warning", 0); // 库存预警

            if (! empty($selectGoodsLabelId)) {
                $selectGoodsLabelIdArray = explode(',', $selectGoodsLabelId);
                $selectGoodsLabelIdArray = array_filter($selectGoodsLabelIdArray);
                $str = "FIND_IN_SET(" . $selectGoodsLabelIdArray[0] . ",ng.group_id_array)";
                for ($i = 1; $i < count($selectGoodsLabelIdArray); $i ++) {
                    $str .= "AND FIND_IN_SET(" . $selectGoodsLabelIdArray[$i] . ",ng.group_id_array)";
                }
                $condition[""] = [
                    [
                        "EXP",
                        $str
                    ]
                ];
            }

            if ($start_date != 0 && $end_date != 0) {
                $condition["ng.create_time"] = [
                    [
                        ">",
                        $start_date
                    ],
                    [
                        "<",
                        $end_date
                    ]
                ];
            } elseif ($start_date != 0 && $end_date == 0) {
                $condition["ng.create_time"] = [
                    [
                        ">",
                        $start_date
                    ]
                ];
            } elseif ($start_date == 0 && $end_date != 0) {
                $condition["ng.create_time"] = [
                    [
                        "<",
                        $end_date
                    ]
                ];
            }

            if ($state != "") {
                $condition["ng.state"] = $state;
            }
            if($type){
                switch ($type){
                    case 1:
                        $condition['ng.state'] = 1;
                        break;
                    case 2:
                        $condition['ng.state'] = 0;
                        break;
                    case 3:
                        $condition['ng.stock'] = ['<=','0'];
                        break;
                    case 4:
                        $condition['ng.min_stock_alarm'] = array(
                            "neq",
                            0
                        );
                        $condition['ng.stock'] = array(
                            "exp",
                            "<= ng.min_stock_alarm"
                        );
                        $condition['ng.state'] = 1;
                        break;
                    default :
                        break;
                }
            }
            if (! empty($goods_name)) {
                $condition["ng.goods_name"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
            }
            if (! empty($goods_code)) {
                $condition["ng.code"] = array(
                    "like",
                    "%" . $goods_code . "%"
                );
            }
            if ($category_id_3 != "") {
                $condition["ng.category_id_3"] = $category_id_3;
            } elseif ($category_id_2 != "") {
                $condition["ng.category_id_2"] = $category_id_2;
            } elseif ($category_id_1 != "") {
                $condition["ng.category_id_1"] = $category_id_1;
            }

            if ($supplier_id != '') {
                $condition['ng.supplier_id'] = $supplier_id;
            }

            $condition["ng.shop_id"] = 0;
            $condition["ng.website_id"] = $this->website_id;

            // 发货助手-商品简称一些查询参数
            $goods_type = request()->post('goods_type');
            $category_id = request()->post('category_id');
            if ($goods_type == 2) {
                // 已填写
                $condition['ng.short_name'] = ['NEQ', ''];
            } elseif ($goods_type == 3) {
                // 未填写
                $condition['ng.short_name'] = ['EQ', ''];
            }
            if ($category_id){
                $condition['ng.category_id_1|ng.category_id_2|ng.category_id_3'] = $category_id;
            }

            $result = $this->integralServer->getIntegralGoodsList($page_index, $page_size, $condition, [
                'ng.create_time' => 'desc'
            ]);
            $goods_mdl = new VslGoodsModel();
            // 'ng.sort' => 'desc',
            // 根据商品分组id，查询标签名称
            foreach ($result['data'] as $k => $v) {
                if (! empty($v['group_id_array'])) {
                    $goods_group_id = explode(',', $v['group_id_array']);
                    $goods_group_name = '';
                    foreach ($goods_group_id as $key => $val) {
                        $goods_group = new GoodsGroup();
                        $goods_group_info = $goods_group->getGoodsGroupDetail($val);
                        if (! empty($goods_group_info)) {
                            $goods_group_name .= $goods_group_info['group_name'] . ',';
                        }
                    }
                    $goods_group_name = rtrim($goods_group_name, ',');
                    $result["data"][$k]['goods_group_name'] = $goods_group_name;
                }
                //商品如果是优惠券、礼品券，判断其是否过期
                $now_time = time();
                if($v['goods_exchange_type'] == 1){//优惠券
                    //判断时间是否过期
                    $coupon = new VslCouponTypeModel();
                    $coupon_info = $coupon->getInfo(['coupon_type_id' => $v['coupon_type_id']], 'end_time');
                    if ($now_time > $coupon_info['end_time']) {//过期了
                        $goods_mdl->save(['state' => 0], ['goods_id' => $v['goods_id']]);
                        $result['data'][$k]['state'] = 0;
                    }
                }elseif ($v['goods_exchange_type'] == 2){//礼品券
                    //判断时间是否过期
                    $giftvoucher = new VslGiftVoucherModel();
                    $giftvoucher_info = $giftvoucher->getInfo(['gift_voucher_id' => $v['gift_voucher_id']], 'end_time');
                    if ($now_time > $giftvoucher_info['end_time']) {//过期了
                        $goods_mdl->save(['state' => 0], ['goods_id' => $v['goods_id']]);
                        $result['data'][$k]['state'] = 0;
                    }
                }
            }
            return $result;
        }
        /*else {
            $goods_group = new GoodsGroup();
            $groupList = $goods_group->getGoodsGroupList(1, 0, [
                'shop_id' => 0,
                'website_id' => $this->website_id,
                'pid' => 0
            ]);
            if (! empty($groupList['data'])) {
                foreach ($groupList['data'] as $k => $v) {
                    $v['sub_list'] = $goods_group->getGoodsGroupList(1, 0, 'pid = ' . $v['group_id']);
                }
            }
            $type = request()->get('type','0');
            $this->assign("goods_group", $groupList['data']);
            $this->assign("type", $type);
            $search_info = request()->get('search_info', '');
            $this->assign("search_info", $search_info);
            // 查找一级商品分类
            $goodsCategory = new GoodsCategory();
            $oneGoodsCategory = $goodsCategory->getGoodsCategoryListByParentId(0);
            $this->assign("oneGoodsCategory", $oneGoodsCategory);
            // 上下架
            $state = request()->get("state", "");
            $this->assign("state", $state);
            // 库存预警
            $stock_warning = request()->get("stock_warning", 0);
            $this->assign("stock_warning", $stock_warning);
            //类型
            $type= $_REQUEST['type']?$_REQUEST['type']:'';
            $this->assign("type", $type);
            $this->assign("website_id", $this->website_id);
            return view($this->style . "Goods/selfGoodsList");
        }*/
    }

    /**
     * 删除积分商城商品
     */
    public function deleteIntegralGoods()
    {
        $goods_ids = request()->post('goods_ids');
        $retval = $this->integralServer->deleteIntegralGoods($goods_ids);
        $this->addUserLog("删除积分商城商品",$goods_ids);
        return AjaxReturn($retval);
    }

    /**
     * 删除积分商城回收站商品
     */
    public function emptyDeleteIntegralGoods()
    {
        $goods_ids = $_REQUEST['goodsId'];
        if(is_array($goods_ids)){
            $goods_ids = implode(',', $goods_ids);
        }
        $res = $this->integralServer->deleteRecycleIntegralGoods($goods_ids);
        $this->addUserLog("删除积分商城回收站商品",$goods_ids);
        return AjaxReturn($res);
    }

    /*
     * 积分商城快速编辑
     * **/
    public function integralQuiklyEdit(){

        $id = $_REQUEST['id'];
        if(!$_REQUEST['goods_name']){
            if(!is_numeric($_REQUEST['market_price']) || !is_numeric($_REQUEST['price']) || !is_numeric($_REQUEST['stock'])){
                $data['code'] = -1;
                $data['message'] = "请输入正确的数字";
                return json($data);
            }
        }

        $market_price = $_REQUEST['market_price']?$_REQUEST['market_price']:'';
        $price = $_REQUEST['price']?$_REQUEST['price']:'';
        $stock = $_REQUEST['stock']?$_REQUEST['stock']:'';
        $goods_name = $_REQUEST['goods_name']?$_REQUEST['goods_name']:'';
        if(!empty($market_price)){
            $this->integralServer->updateIntegralGoodsNameOrIntroduction($id,'market_price',$market_price);
        }elseif(!empty($price)){
            $this->integralServer->updateIntegralGoodsNameOrIntroduction($id,'price',$price);
        }elseif(!empty($stock)){
            $this->integralServer->updateIntegralGoodsNameOrIntroduction($id,'stock',$stock);
        }elseif(!empty($goods_name)){
            $this->integralServer->updateIntegralGoodsNameOrIntroduction($id,'goods_name',$goods_name);
        }
    }

    /**
     * 积分商城商品上架
     */
    public function modifyIntegralGoodsOnline()
    {
        $condition = $_POST["goods_ids"]; // 将商品id用,隔开
        $result = $this->integralServer->modifyIntegralGoodsOnline($condition);
        $this->addUserLog("商品上架",$_POST["goods_ids"]);
        return AjaxReturn($result);
    }

    /**
     * 积分商城商品下架
     */
    public function ModifyIntegralGoodsOutline()
    {
        $condition = $_POST["goods_ids"]; // 将商品id用,隔开
        $result = $this->integralServer->ModifyIntegralGoodsOutline($condition);
        $this->addUserLog("商品下架",$_POST["goods_ids"]);
        return AjaxReturn($result);
    }

    /**
     * 积分商城回收站商品恢复
     */
    public function regainIntegralGoodsDeleted()
    {
        if (request()->isAjax()) {
            $goods_ids = request()->post('goodsId/a');
            $res = $this->integralServer->regainIntegralGoodsDeleted($goods_ids);
            $this->addUserLog("积分商城回收站商品恢复",$res);
            return AjaxReturn($res);
        }
    }

    /*
     * 模态框选择商品
     * **/
    public function modalIntegralGoodsList()
    {
        if (request()->post('page_index')) {
            $index = request()->post('page_index', 1);
            $goods_type = request()->post('goods_type', 1);
            $search_text = request()->post('search_text');
            if ($search_text) {
                $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['ng.goods_type'] = ['not in', [0, 3, 4]];//0-计时计次 3-虚拟商品 4-知识付费
            $condition['ng.state'] = 1;
            $condition['ng.website_id'] = $this->website_id;
            $condition['ng.shop_id'] = $this->instance_id;
            //0自营店 1全平台
            if ($goods_type == '0') {
                $condition['ng.shop_id'] = $this->instance_id;
            }
            $goods = new Goods();
            $list = $goods->getModalGoodsList($index, $condition);
            return $list;
        }
        $integral_goods_id = request()->get('integral_goods_id', 0);
        $this->assign('integral_goods_id', $integral_goods_id);
        $this->fetch('template/' . $this->module . '/integralGoodsDialog');
    }
    /*
     * 模态框选择优惠券
     * **/
    public function modalIntegralCouponList()
    {
        if (request()->post('page_index')) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text');
            if ($search_text) {
                $condition['coupon_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['start_time'] = ['<=', time()];
            $condition['end_time'] = ['>', time()];
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            $couponServer = new Coupon();
            $list = $couponServer->getCouponTypeList($page_index, $page_size, $condition, 'start_time desc', '*');
            //处理名称
            foreach($list['data'] as $k=>$v){
                if($v['range_type'] == 0){
                    $goods_str = '部分商品使用';
                }else{
                    $goods_str = '全部商品使用';
                }
                switch($v['coupon_genre']){
                    case 1:
                        $list['data'][$k]['coupon_format_name'] = $v['coupon_name'].'：无门槛券'.$goods_str.'减'.$v['money'].'元';
                        break;
                    case 2:
                        $list['data'][$k]['coupon_format_name'] = $v['coupon_name'].'：满减券'.$goods_str.'满'.$v['at_least'].'元，减'.$v['money'].'元';
                        break;
                    case 3:
                        $list['data'][$k]['coupon_format_name'] = $v['coupon_name'].'：折扣券'.$goods_str.'满'.$v['at_least'].'元，打'.(int)$v['discount'].'折';
                        break;
                }
            }
            return $list;
        }
        $integral_goods_id = request()->get('integral_goods_id', 0);
        $this->assign('integral_goods_id', $integral_goods_id);
        $this->fetch('template/' . $this->module . '/integralCouponDialog');
    }
    /*
     * 模态框选择礼品券
     * **/
    public function modalIntegralGiftList()
    {
        if (request()->post('page_index')) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text');
            if ($search_text) {
                $condition['gv.giftvoucher_name'] = ['LIKE', '%' . $search_text . '%'];
            }
//            获取的是可用的礼品券
//            $condition['start_time'] = ['<=', time()];
//            $condition['end_time'] = ['>', time()];
            $condition['gv.website_id'] = $this->website_id;
            $condition['gv.shop_id'] = $this->instance_id;
            $vsl_voucher = new VslGiftVoucherModel();
            $list = $vsl_voucher->getVoucherViewList($page_index, $page_size, $condition, 'start_time desc');
//            var_dump($list);
            return $list;
        }
        $integral_goods_id = request()->get('integral_goods_id', 0);
        $this->assign('integral_goods_id', $integral_goods_id);
        $this->fetch('template/' . $this->module . '/integralGiftDialog');
    }
    /*
     * 判断优惠券、礼品券是否满足库存
     * **/
    public function isCardStock()
    {
        $goods_type = request()->post('goods_type', '');
        $id = request()->post('id', 0);
        $num = request()->post('num', 0);
        if($goods_type == 'coupon'){
            $coupon = new VslCouponTypeModel();
            $coupon_record = new VslCouponModel();
            $total = $coupon->getInfo(['coupon_type_id' => $id], 'count')['count'];
            $use_count = $coupon_record->where(['coupon_type_id'=>$id, 'website_id' => $this->website_id])->count();
            $remain_count = $total - $use_count > 0 ? $total - $use_count : 0;
            if($num > $remain_count){
                return json(['code' => -1, 'message' => '优惠券设置库存超出原有可领取数量('. $remain_count .'张)']);
            }
        }else{
            $giftvoucher = new VslGiftVoucherModel();
            $giftvoucher_record = new VslGiftVoucherRecordsModel();
            $total = $giftvoucher->getInfo(['gift_voucher_id' => $id], 'count')['count'];
            $use_count = $giftvoucher_record->where(['gift_voucher_id'=>$id, 'website_id' => $this->website_id])->count();
            $remain_count = $total - $use_count > 0 ? $total - $use_count : 0;
            if($num > $remain_count){
                return json(['code' => -1, 'message' => '礼品券设置库存超出原有可领取数量('. $remain_count .'张)']);
            }
        }
    }
    /**
     * 功能说明：添加或更新积分商城商品时 ajax调用的函数
     */
    public function integralGoodsCreateOrUpdate()
    {
        $res = 0;
        $product = [];
        if(empty($_REQUEST['imageArray'])){
           return json(['code'=>0,'message'=>'商品图片不能为空']);
        }
        //重新组成一维数组
        foreach($_REQUEST['data'] as $key=>$value){
            $product[$value['name']] = $value['value'];
        }
        $product['description'] = $product['editorValue'];
        $product['goods_type'] = $_REQUEST['goods_type']?$_REQUEST['goods_type']:'goods';//0-商品 1-优惠券 2-礼品券 3-余额
        //重组图片格式
        $img = '';
        foreach ($_REQUEST['imageArray'] as $k=>$v){
            $img .= $v.',';
        }
        //判断是什么类型的兑换品
        if ($product['goods_type'] == 'coupon') {//优惠券
            $goods_exchange_type = 1;
        }
        if ($product['goods_type'] == 'gift') {//礼品券
            $goods_exchange_type = 2;
        }
        if ($product['goods_type'] == 'balance') {//余额
            $goods_exchange_type = 3;
            $product['conversion_price'] = 0;
        }else{
            $product['balance_setting'] = 0;
        }
        //判断兑换方式
        if (!empty($product['conversion_point']) && !empty($product['conversion_price'])) {
            $point_exchange_type = 2;//用积分和钱
        } elseif (!empty($product['conversion_point'])) {
            $point_exchange_type = 1;//只用积分
        } elseif (!empty($product['conversion_price'])) {
            $point_exchange_type = 0;//非积分兑换，就是用钱
        }else {
            $point_exchange_type = 3;//取sku里面的规格兑换信息
        }
        $goods_exchange_type = $goods_exchange_type?:0;//正常商品
        $product['shipping_fee'] = $_REQUEST['shipping_fee'];
        $product['video_id'] = $_REQUEST['video_id']?:0;
        $product['goods_spec_format'] = $_REQUEST['spec_format'];
//        $product['goods_attribute'] = $_REQUEST['attr'];
        $product['goods_attribute'] = $_REQUEST['goods_attribute'];
        $product['item_no'] = $_REQUEST['item_no'];
        $product["skuArray"] = $_REQUEST['sku_str'];
        if($product['goods_type'] != 'goods'){
            $product["skuArray"] = '';
        }
        $product['imageArray'] = substr($img,0,-1);
        $product['picture'] = $_REQUEST['imageArray'][0];
        $product['goodsId'] = $_REQUEST['goods_id']?$_REQUEST['goods_id']:'';
        if(empty($product["goodsId"]) && $product['goods_type'] == 'balance'){
            $product['conversion_point'] = $product['conversion_point1'];
        }
        $product['goods_attribute_id'] = $_REQUEST['goods_attr_id']?$_REQUEST['goods_attr_id']:'' ;
        //价格
        $product['goods_spec_format'] = $_REQUEST['spec_format'];
        $qrcode = request()->post('is_qrcode', ''); // 1代表 需要创建 二维码 0代表不需要
        if (! empty($product)) {
            if(!is_array($product)) {
                $product = json_decode($product, true);
            }
            $shopId = $this->instance_id;
            $goodservice = new GoodsService();
            $res = $this->integralServer->addOrEditIntegralGoods(
                $product["goodsId"], // 商品Id
                $product["title"], // 商品标题
                $shopId,
                $_REQUEST['category_id'], // 商品类目
                $category_id_1 = 0,
                $category_id_2 = 0,
                $category_id_3 = 0,
                $product["supplierId"],
                $product["brandId"],
                $product["groupArray"], // 商品分组
                1,
                $product["market_price1"],
                $product["price"], // 商品现价
                $product["cost_price"],
//                $product["point_exchange_type"],
                $product['integration_available_use'],
                $product['integration_available_give'],
                $is_member_discount = 0,
                $product["shipping_fee"],
                $product["shipping_fee_id"],
                $product["stock"],
                $product['max_buy'],
                $product['min_buy'],
                $product["minstock"],
                $product["base_good"],
                $product["base_sales"],
                $collects = 0,
                $star = 0,
                $evaluates = 0,
                $product["base_share"],
                $product["province_id"],
                $product["city_id"],
                $product["picture"],
                $product['key_words'],
                $product["introduction"], // 商品简介，促销语
                $product["description"],
                $product['qrcode'], // 商品二维码
                $product["code"],
                $product["display_stock"],
                $is_hot = 0,
                $is_recommend = 0,
                $is_new = 0,
                $sort = $product['sort'],
                $product["imageArray"],
                $product["skuArray"],
                $product["state"], '', // $product["sku_img_array"]
                $product['goods_attribute_id'],
                $product['goods_attribute'],
                $product['goods_spec_format'],
                $product['goods_weight'],
                $product['goods_volume'],
                $product['shipping_fee_type'],
                $product['categoryExtendId'],
                $product["sku_picture_vlaues"],
                $product['item_no'],
                $product['coupon_type_id'],
                $product['gift_voucher_id'],
                $product['balance_setting'],
                $goods_exchange_type,
                $point_exchange_type,
                $product['conversion_point'],
                $product['conversion_price'],
                $product['video_id'],
                $product['limit_num'],
                $product['day_num'],'',[],
                $product['goods_count']
            );
//            var_dump($product["skuArray"]);exit;
            // sku编码分组
            if ($res > 0 && $qrcode == 1) {
                $goodsId = $res;

                $url = __URL(Config::get('view_replace_str.APP_MAIN') . '/goods/goodsdetail?id=' . $goodsId.'&website_id='.$this->website_id);
                $pay_qrcode = getQRcode($url, 'upload/'.$this->website_id.'/goods_qrcode', 'goods_qrcode_' . $goodsId);

                $goodservice->goods_QRcode_make($goodsId, $pay_qrcode);
            }
        }
        $message = $res>0?'添加成功':'添加失败';
        $dataa['code'] = $res;
        $dataa['message'] = $message;
        $this->addUserLog("更新或添加商品",$res);
        return json($dataa);
        // return $res;
    }
    //刷新分类
    public function refreshIntegralCate()
    {
        $inte_cate = new VslIntegralCategoryModel();
        $inte_cate_list = $inte_cate->getquery([], '*', '');
        return json($inte_cate_list);
    }
    /*
     * 获取回收站列表
     * **/
    public function integralGoodsRecycle()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $goods_name = request()->post('goods_name', '');
            $category_id_1 = request()->post('category_id_1', '');
            $category_id_2 = request()->post('category_id_2', '');
            $category_id_3 = request()->post('category_id_3', '');

            if (! empty($goods_name)) {
                $condition["ng.goods_name"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
            }
            if ($category_id_3 != "") {
                $condition["ng.category_id_3"] = $category_id_3;
            } else
                if ($category_id_2 != "") {
                    $condition["ng.category_id_2"] = $category_id_2;
                } else
                    if ($category_id_1 != "") {
                        $condition["ng.category_id_1"] = $category_id_1;
                    }
            $condition["ng.shop_id"] = $this->instance_id;
            $condition["ng.website_id"] = $this->website_id;
            $result = $this->integralServer->getIntegralGoodsDeletedList($page_index, $page_size, $condition, "ng.create_time desc");
            return $result;
        }
    }
    /*
     * 判断是否有会员支付密码
     * **/
    public function getMemberBalancePoint()
    {
            $uid = $this->uid;
            $member_account = new VslMemberAccountModel();
            $account_info = $member_account->getInfo(['uid' => $uid], 'balance, point');
            if($account_info){
                return json(['code' => '1', 'data' => $account_info]);
            }
    }
    /*
     * 积分商城去支付
     * **/
    public function integralPay()
    {
        $order_data = request()->post('order_data/a');
        $is_order_key = md5(json_encode($order_data));
        //判断是否是网络延迟造成多次请求
        if(Cookie::get($is_order_key)){//有session说明创建成功了
            $cookie_data = unserialize(Cookie::get($is_order_key));
            $time = $cookie_data['create_time'] + 5;
            if(time() <= $time){
                $message['code'] = 0;
                $message['message'] = "订单提交成功";
                $message['data']['out_trade_no'] = $cookie_data['out_trade_no'];
                Cookie::delete($is_order_key);
                return json($message);
            }
        }
        //判断当前用户是否还可以购买
        $goods_id = $order_data['goods_list']['goods_id'];
        $num = $order_data['goods_list']['num'];
        $integral_goods = new VslIntegralGoodsModel();
        $num_list = $integral_goods->getInfo(['goods_id' => $goods_id], 'limit_num, day_num, goods_exchange_type, coupon_type_id, gift_voucher_id');
        $now_time = time();
        if($num_list['goods_exchange_type'] == 1){//优惠券
            if (!getAddons('coupontype', $this->website_id)) {
                return ['code' => -1, 'message' => '优惠券应用已关闭'];
            }
            //判断礼品券是否过期
            $coupon = new VslCouponTypeModel();
            $end_time = $coupon->getInfo(['coupon_type_id' => $num_list['coupon_type_id']], 'end_time')['end_time'];
            if($now_time > $end_time){
                return ['code' => -1, 'message' => '优惠券已过期'];
            }
        }elseif($num_list['goods_exchange_type'] == 2){//礼品券
            if (!getAddons('giftvoucher', $this->website_id)) {
                return ['code' => -1, 'message' => '礼品券应用已关闭'];
            }
            //判断礼品券是否过期
            $giftvoucher = new VslGiftVoucherModel();
            $end_time = $giftvoucher->getInfo(['gift_voucher_id' => $num_list['gift_voucher_id']], 'end_time')['end_time'];
            if($now_time > $end_time){
                return ['code' => -1, 'message' => '礼品券已过期'];
            }
        }
        $least_num = $this->integralServer->getUserCanBuyNum($goods_id, $num_list['day_num'], $num_list['limit_num']);
        if($num > $least_num && $least_num != -1){
            $data['code'] = -1;
            $data['message'] = "您已达商品最大购买量";
            return json($data);
        }
        //生成外部支付号
        $order_service = new OrderService();
        $order_business = new OrderBusiness();
        $out_trade_no = 'DH' . $order_service->getOrderTradeNo();
//        $order_no = $order_service->getOrderTradeNo();
        $order_no = $order_business->createOrderNo(0);
        $order_data['out_trade_no'] = $out_trade_no;
        $order_data['order_no'] = $order_no;
        $uid = $this->uid;
        $order_data['uid'] = $uid;
        $order_data['website_id'] = $this->website_id;
        $ip = get_ip();
        $order_data['ip'] = $ip;
        //数据判断并组合数据
        $return_status = $this->integralServer->validateData($order_data);
        if ($return_status) {
            return json($return_status);
        }
        //商品是什么类型
        $goods_type = $order_data['goods_type'];
        //用什么方式去支付
        $point_exchange_type = $order_data['point_exchange_type'];
        $order_data['goods_list']['shipping_fee'] = 0;
        if ($goods_type === '0') {//实物
            $goods_id = $order_data['goods_list']['goods_id'];
            $num = $order_data['goods_list']['num'];
            $member = new Member();
            $address = $member->getMemberExpressAddressDetail($order_data['address_id']);
            $goods_id_info = [
                'goods_info' => [
                    'goods_id' => $goods_id,
                    'count' => $num
                ],
            ];
            //通过sku_id获取规格是哪种兑换方式
            $fee = $this->integralServer->getIntegralGoodsExpressTemplate($goods_id_info, $address['district']);
            $order_data['goods_list']['shipping_fee'] = $fee;
            if ((floatval($order_data['goods_list']['price']) > 0 && $order_data['goods_list']['exchange_point']) || $fee > 0) {
                $point_exchange_type = 2;
            }
        }
        $password = request()->post('password', '');
        if ($point_exchange_type == 1) {//只用积分
            //验证密码
//            if ($password) {
//                $bool = $this->integralServer->check_pay_password($password);
//                if (!$bool) {
//                    $data['code'] = '-1';
//                    $data['data'] = '';
//                    $data['message'] = "支付密码错误";
//                    return json($data);
//                } else {
                    $order_id = $this->integralServer->createIntegralOrder($order_data);
                    if($order_id>0){
                        //更改积分账户变动情况
                        $exchange_point = $order_data['goods_list']['exchange_point'];
                        $pay = new UnifyPay();
                        $pay->calculateMemberPoint($order_data['uid'], $order_data['website_id'], $exchange_point, $order_id);
                        $data['code'] = 0;
                        $data['message'] = "订单创建成功";
                        $data['data']['out_trade_no'] = $out_trade_no;
                        return json($data);
                    }
//                }
//            } else {
//                $data['code'] = '-1';
//                $data['data'] = '';
//                $data['message'] = "请输入密码";
//                return json($data);
//            }
        } elseif ($point_exchange_type == 2) {//用积分和金钱
            //金钱有哪些支付方式  0-在线支付 1-微信支付 2-支付宝 3-银联卡 4-货到付款 5-余额支付
            $pay_type = $order_data['pay_type'];
            //将支付信息存入redis
            $key = 'integral_pay_' . $out_trade_no;
            switch ($pay_type) {
                case '1': //微信支付
                    $redis = $this->connectRedis();
                    $pay_data = $order_data;
//                    p($pay_data);exit;
                    $pay_str = json_encode($pay_data);
                    $redis->set($key, $pay_str);

                    // 支付来源,1 微信浏览器,4 ios,5 Android,6 小程序,2 手机浏览器,3 PC
//                    $type = request()->post('type', 3);
                    $type = $order_data['type'];
                    if (empty($out_trade_no)) {
                        $data['code'] = 0;
                        $data['data'] = '';
                        $data['message'] = "没有获取到订单信息";
                        return json($data);
                    }
                    $red_url = $this->realm_ip."/wapapi/pay/wchatUrlBack";
                    $pay = new UnifyPay();
                    if($type==1){
                        $res = $pay->wchatPay($out_trade_no, 'JSAPI',$red_url);
                        if($res["return_code"]  && $res["return_code"] == "SUCCESS"){
                            $retval = $pay->getWxJsApi($res);
                            $data['data'] = json_decode($retval,true);
                            $data['data']['out_trade_no'] = $out_trade_no;
                            $data['code'] = 0;
                            return json($data);
                        }else{
                            $data['data'] = $res;
                            $data['code'] = -1;
                            $data['message'] = '支付失败,'.$res['err_code_des'];
                            return json($data);
                        }
                    }
                    if($type==2){
                        $call_url = urlencode($this->realm_ip . '/wap/pay/result?out_trade_no=' . $out_trade_no);
                        $res = $pay->wchatPay($out_trade_no, 'MWEB', $red_url);
                        if($res["return_code"] && $res["return_code"] == "SUCCESS"){
                            $res['mweb_url'] = $res['mweb_url']."&redirect_url=".$call_url;
                            $data['data'] = $res;
                            $data['data']['type'] = "h5";
                            $data['code'] = 0;
                            return json($data);
                        }else{
                            $data['code'] = -1;
                            $data['message'] = '支付失败,'.$res['err_code_des'];
                            return json($data);
                        }
                    }
                    if($type==6){
                        $res = $pay->wchatPayMir($out_trade_no, 'JSAPI',$red_url, $this->website_id);
                        if($res["return_code"]  && $res["return_code"] == "SUCCESS"){
                            $auth = new WeixinAuthModel();
                            $app_id = $auth->getInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id],'authorizer_appid')['authorizer_appid'];
                            $retval = $pay->getWxJsApiMir($res,$app_id);
                            $data['data']['out_trade_no'] = $out_trade_no;
                            $data['data'] = json_decode($retval,true);
                            $data['code'] = 0;
//                            var_dump($data);exit;
                            return json($data);
                        }else{
                            $data['data'] = $res;
                            $data['code'] = -1;
                            $data['message'] = '支付失败,'.$res['err_code_des'];
                            return json($data);
                        }
                    }
                    if($type==4 || $type==5){
                        $res = $pay->wchatPay($out_trade_no, 'APP',$red_url);
                        if($res["return_code"] && $res["return_code"] == "SUCCESS"){
                            $retval = $pay->getWxJsApiApp($res);
                            $data['data']['out_trade_no'] = $out_trade_no;
                            $data['data'] = json_decode($retval,true);
                            $data['code'] = 0;
                            return json($data);
                        }else{
                            $data['code'] = -1;
                            $data['data'] = $res;
                            $data['message'] = '支付失败,'.$res['err_code_des'];
                            return json($data);
                        }
                    }
                    break;
                case '2'://支付宝
                    $type = $order_data['type'];
                    if(empty($type)){
                        $type = request()->get('type', 2);
                    }
                    $notify_url = $this->realm_ip . "/wapapi/pay/aliUrlBack";
                    $return_url = $this->realm_ip. "/wap/pay/result?out_trade_no=".$out_trade_no;;
                    if($type==2) {
                        $pay = new UnifyPay();
                        $res = $pay->aliPayNewWap($out_trade_no, $notify_url, $return_url);
                        if($res){
                            $data['data'] = $res;
                            $data['code'] = 0;
                            return json($data);
                        }else{
                            $data['code'] = -1;
                            $data['message'] = '支付失败,'.$res['sub_msg'];
                            return json($data);
                        }
                    }
                    if($type==4 || $type==5){
                        $pay = new UnifyPay();
                        $res = $pay->aliPayNewApp($out_trade_no, $notify_url, $return_url);
                        if($res){
                            $data['data'] = $res;
                            $data['code'] = 0;
                            return json($data);
                        }else{
                            $data['code'] = -1;
                            $data['message'] = '支付失败,'.$res['sub_msg'];
                            return json($data);
                        }
                    }
                    if (empty($out_trade_no)) {
                        $data['code'] = 0;
                        $data['data'] = '';
                        $data['message'] = "没有获取到订单信息";
                        return json($data);
                    }
                    break;
                case '4'://货到付款

                    break;
                case '5'://余额支付
                    $pay = new UnifyPay();
                    $member = new Member\MemberAccount();
                    $member_account = $member->getMemberAccount($this->uid); // 用户余额
                    $balance = $member_account['balance'];
                    $pay_money = $order_data['goods_list']['num'] * $order_data['goods_list']['price'] + $order_data['goods_list']['shipping_fee'];
                    //判断账号体系，若是第三种并且设置了不绑定手机 就不验证密码
                    $website = new WebSiteModel();
                    $webste_info = $website->getInfo(['website_id' => $this->website_id], 'account_type, is_bind_phone');
                    if($webste_info['account_type'] == 3 && $webste_info['is_bind_phone'] == 0){
                        $bool = true;
                        $password = true;
                    }else{
                        $bool = $this->integralServer->check_pay_password($password);
                    }
                    if ($password) {
                        if (!$bool) {
                            $data['code'] = '-1';
                            $data['data'] = '';
                            $data['message'] = "支付密码错误";
                            return json($data);
                        } else {
                            if ($balance < $pay_money) {
                                $data['code'] = -1;
                                $data['message'] = "余额不足。";
                                return json($data);
                            } else {
                                //创建订单
                                $order_id = $this->integralServer->createIntegralOrder($order_data);
                                if ($order_id > 0) {
                                    //更改积分账户变动情况
                                    $exchange_point = (float)$order_data['goods_list']['exchange_point'];
                                    $pay->calculateMemberPoint($order_data['uid'], $order_data['website_id'], $exchange_point, $order_id);
                                    $res=  $order_service->orderOnLinePay($out_trade_no, 5, $order_id);
                                    if($res==1){
//                                $account_flow = new Member\MemberAccount();
//                                $account_flow->addMemberAccountData(2, $order_data['uid'], 0, $pay_money, 1, $order_id, '积分兑换订单，余额支付');
                                        $data['code'] = 0;
                                        $data['message'] = "订单创建成功";
                                        $data['data']['out_trade_no'] = $out_trade_no;
                                        return json($data);
                                    }
                                }
                            }
                        }
                    }else{
                        $data['code'] = '-1';
                        $data['data'] = '';
                        $data['message'] = "请输入密码";
                        return json($data);
                    }
                    break;
            }
        }
    }

    /**
     * 获取 商品 数量       全部    出售中  已审核  已下架
     */
    public function getIntegralGoodsCount(){
        $goods_count_array = array();
        //全部
        $goods_count_array['all'] = $this->integralServer->getIntegralGoodsCount(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id]);
        //出售中
        $goods_count_array['sale'] = $this->integralServer->getIntegralGoodsCount(['website_id'=>$this->website_id,'state'=>1,'shop_id'=>$this->instance_id]);
        //仓库中
        $goods_count_array['shelf'] = $this->integralServer->getIntegralGoodsCount(['website_id'=>$this->website_id,'state'=>0,'shop_id'=>$this->instance_id]);
        //已售罄
        $goods_count_array['soldout'] = $this->integralServer->getIntegralGoodsCount(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id,'stock' => array("<=","0")]);
        //库存预警
        $goods_count_array['alarm'] = $this->integralServer->getIntegralGoodsCount(['website_id'=>$this->website_id,'state'=>1,'shop_id'=>$this->instance_id,'min_stock_alarm' => array("neq", 0),'stock' => array("exp", "<= min_stock_alarm")]);
        return $goods_count_array;
    }

    /**
     * wap分类列表接口
     * @return \think\response\Json
     */
    public function integralCategoryList()
    {
        $list = $this->integralServer->getIntegralCategoryList(['website_id' => $this->website_id, 'is_visible' => 1]);
        $return_data = [];
        foreach ($list as $c) {
            $temp['category_id'] = $c['integral_category_id'];
            $temp['category_name'] = $c['category_name'];
            $temp['short_name'] = $c['short_name'];
            $temp['category_pic'] = getApiSrc($c['category_pic']);

            $return_data[] = $temp;
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => $return_data]);
    }

    /**
     * wap积分商品列表接口
     */
    public function goodsList()
    {
        $page_index = request()->post('page_index') ?: 1;
        $page_size = request()->post('page_size') ?: PAGESIZE;
        $search_text = request()->post('search_text');
        $shop_id = request()->post('shop_id');
        if ($search_text){
            $condition['ng.goods_name'] = ['LIKE','%'.$search_text.'%'];
        }
        $category_id = request()->post('category_id');
        if ($category_id){
            $condition['ng.category_id'] = $category_id;
        }
        $order = request()->post('order') ?: 'ng.sort';
        $sort = request()->post('sort') ?: 'DESC';
        if(strlen($sort) > 4) {
            //防sql注入
            return json(AjaxReturn(PARAMETER_ERROR));
        }
        if($order != 'ng.sort') {
            if(strlen($order) > 14) {
                //防sql注入
                return json(AjaxReturn(PARAMETER_ERROR));
            }
        }
        $order_sort = $order . ' ' . $sort;
        $condition['ng.state'] = 1;
        $exchange_type = [0,1,2,3];
        $coupon = getAddons('coupontype', $this->website_id);
        $giftvoucher = getAddons('giftvoucher', $this->website_id);
        if(!$coupon){
            unset($exchange_type[1]);
        }
        if(!$giftvoucher){
            unset($exchange_type[2]);
        }
        $condition['ng.goods_exchange_type'] = ['in', $exchange_type];
        $condition['ng.website_id'] = $this->website_id;
        $condition['ng.shop_id'] = 0;
        if ($shop_id){
            $condition['ng.shop_id'] = $shop_id;
        }
        $goods_list = $this->integralServer->getIntegralGoodsList($page_index, $page_size, $condition, $order_sort);
        $return_goods = [];
        $return_goods['goods_list'] = [];
        $return_goods['total_count'] = $goods_list['total_count'];
        $return_goods['page_count'] = $goods_list['page_count'];
        foreach ($goods_list['data'] as $v){
            $now_time = time();
            if($v['goods_exchange_type'] == 1){//优惠券
                //判断时间是否过期
                $coupon = new VslCouponTypeModel();
                $coupon_info = $coupon->getInfo(['coupon_type_id' => $v['coupon_type_id']], 'end_time');
                if ($now_time > $coupon_info['end_time']) {//过期了
                    continue;
                }
            }elseif ($v['goods_exchange_type'] == 2){//礼品券
                //判断时间是否过期
                $giftvoucher = new VslGiftVoucherModel();
                $giftvoucher_info = $giftvoucher->getInfo(['gift_voucher_id' => $v['gift_voucher_id']], 'end_time');
                if ($now_time > $giftvoucher_info['end_time']) {//过期了
                    continue;
                }
            }
            $temp = [];
            $temp['goods_id'] = $v['goods_id'];
            //得到sku里面最小的积分和money
            $integral_sku = new VslIntegralGoodsSkuModel();
            $integral_price_arr = $integral_sku->where(['goods_id'=>$v['goods_id']])->column('price');
            $integral_point_arr = $integral_sku->where(['goods_id'=>$v['goods_id']])->column('exchange_point');
            $integral_price = min($integral_price_arr);
            $integral_point = min($integral_point_arr);
            $temp['goods_name'] = $v['goods_name'];
            $temp['logo'] = getApiSrc($v['pic_cover']);
            $temp['point_exchange'] = $integral_point;
            $temp['price'] = $integral_price;
//            0-正常商品 1-优惠券 2-礼品券 3-余额
            if ($v['goods_exchange_type'] == 0){
                $temp['type'] = '商品';
            } elseif($v['goods_exchange_type'] == 1){
                $temp['type'] = '优惠券';
            }elseif ($v['goods_exchange_type'] == 2){
                $temp['type'] = '礼品券';
            } elseif($v['goods_exchange_type'] == 3){
                $temp['type'] = '余额';
            }

            $return_goods['goods_list'][] = $temp;
        }
        return json(['code'=>1,'message'=>'','data'=>$return_goods]);
    }

    /**
     * wap积分商品详情接口
     */
    public function goodsDetail()
    {
        $goods_id = request()->post('goods_id');
        if (empty($goods_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        if (!is_numeric($goods_id)) {
            //防sql注入
            return json(AjaxReturn(PARAMETER_ERROR));
        }
        $goods_data = $this->integralServer->getIntegralGoodsDetail($goods_id);
        if(empty($goods_data)){
            return json(['code' => -1, 'message' => '商品不存在']);
        }
        $now_time = time();
        if($goods_data['goods_exchange_type'] == 1){//优惠券
            if (!getAddons('coupontype', $this->website_id)) {
                return ['code' => -1, 'message' => '优惠券应用已关闭'];
            }
            //判断优惠券是否过期
            $coupon = new VslCouponTypeModel();
            $end_time = $coupon->getInfo(['coupon_type_id' => $goods_data['coupon_type_id']], 'end_time')['end_time'];
            if($now_time > $end_time){
                return ['code' => -1, 'message' => '优惠券已过期'];
            }
        }elseif($goods_data['goods_exchange_type'] == 2){//礼品券
            if (!getAddons('giftvoucher', $this->website_id)) {
                return ['code' => -1, 'message' => '礼品券应用已关闭'];
            }
            //判断礼品券是否过期
            $giftvoucher = new VslGiftVoucherModel();
            $end_time = $giftvoucher->getInfo(['gift_voucher_id' => $goods_data['gift_voucher_id']], 'end_time')['end_time'];
            if($now_time > $end_time){
                return ['code' => -1, 'message' => '礼品券已过期'];
            }
        }

        $uid = getUserId();
        if ($uid){
            $user_model = new UserModel();
            $user_info = $user_model::get($uid,['member_account']);
        }

        $goods_detail['member_point'] = $user_info ? $user_info->member_account->point : 0;
        $goods_detail['goods_id'] = $goods_data['goods_id'];
        $goods_detail['state'] = $goods_data['state'];
        $goods_detail['shop_id'] = $goods_data['shop_id'];
        $goods_detail['goods_name'] = $goods_data['goods_name'];
        $goods_detail['description'] = $goods_data['description'];
        $goods_detail['sales'] = $goods_data['sales'];
        $goods_detail['min_buy'] = $goods_data['min_buy'];
        $goods_detail['max_buy'] = $goods_data['max_buy'];
        $goods_detail['shop_name'] = $goods_data['shop_name'];
        $goods_detail['limit_num'] = $goods_data['limit_num'];
        $goods_detail['day_num'] = $goods_data['day_num'];
        //获取当前用户还可以购买多少
        $least_num = $this->integralServer->getUserCanBuyNum($goods_data['goods_id'], $goods_data['day_num'], $goods_data['limit_num']);
        //取sku里面的最低价和最低积分
        $sku_arr = objToArr($goods_data['sku_list']);
        $lowest_point_arr = array_column($sku_arr, 'exchange_point');
        $goods_detail['point_exchange'] = min($lowest_point_arr);
        //判断商品是否是实物的
        if($goods_data['goods_exchange_type'] == 0){
            if ($goods_data['shipping_fee_type'] == 0) {
                $goods_detail['shipping_fee'] = '包邮';
            } elseif ($goods_data['shipping_fee_type'] == 1) {
                $goods_detail['shipping_fee'] = $goods_data['shipping_fee'];
            } elseif ($goods_data['shipping_fee_type'] == 2) {
                $user_location = get_city_by_ip();
                if ($user_location['status'] == 1) {
                    // 定位成功，查询当前城市的运费
                    $goods_express = new GoodsExpressService();
                    $address = new Address();
                    $city = $address->getCityId($user_location['city']);
                    $district = $address->getCityFirstDistrict($city['city_id']);
                    $express = $goods_express->getGoodsExpressTemplate([['goods_id' => $goods_id, 'count' => 1]], $district)['totalFee'];
                    $goods_detail['shipping_fee'] = $express;
                }
            }
        }else{
            $goods_detail['shipping_fee'] = 0;
        }

        //商品属性
        $goods_detail['goods_attribute_list'] = [];
        foreach ($goods_data['goods_attribute_list'] as $k_attr => $attr) {
            $temp_attr['attr_value'] = $attr['attr_value'];
            $temp_attr['attr_value_name'] = $attr['attr_value_name'];

            $goods_detail['goods_attribute_list'][$k_attr] = $temp_attr;
        }
        //商品图片
        foreach ($goods_data['img_list'] as $k => $pic) {
            $goods_detail['goods_images'][] = getApiSrc($pic['pic_cover']);
        }

        $spec_obj = [];
        $goods_detail['sku']['tree'] = [];
        if (!empty($goods_data['spec_list']) && $goods_data['spec_list'] != '[]') {
            foreach ($goods_data['spec_list'] as $i => $spec_info) {
                $temp_spec = [];
                foreach ($spec_info['value'] as $s => $spec_value) {
                    $temp_spec['k'] = $spec_info['spec_name'];
                    $temp_spec['k_id'] = $spec_info['spec_id'];
                    $temp_spec['v'][$s]['id'] = $spec_value['spec_value_id'];
                    $temp_spec['v'][$s]['name'] = $spec_value['spec_value_name'];
                    $temp_spec['k_s'] = 's' . $i;

                    $spec_obj[$spec_info['spec_id']] = $temp_spec['k_s'];
                    $goods_detail['sku']['tree'][$spec_info['spec_id']] = $temp_spec;
                }
            }
            //接口需要tree是数组，不是对象，去除tree以spec_id为key的值
            $goods_detail['sku']['tree'] = array_values($goods_detail['sku']['tree']);
        }

        //sku
        foreach ($goods_data['sku_list'] as $k => $sku) {
            $temp_sku['id'] = $sku['sku_id'];
            $temp_sku['sku_name'] = $sku['sku_name'];
            $temp_sku['price'] = $sku['price'];
            $temp_sku['min_buy'] = 1;
//            $temp_sku['point_exchange'] = $goods_data['point_exchange'];
            $temp_sku['point_exchange'] = $sku['exchange_point'];
            $temp_sku['market_price'] = $sku['market_price'];
            if($least_num != -1){
                $temp_sku['stock_num'] = $sku['stock'] > $least_num ? $least_num : $sku['stock'];
            }else{//无限制
                $temp_sku['stock_num'] = $sku['stock'];
            }

            $temp_sku['attr_value_items'] = $sku['attr_value_items'];
            $sku_temp_spec_array = explode(';', $sku['attr_value_items_format']);
            $temp_sku['s'] = [];
            foreach ($sku_temp_spec_array as $spec_id => $spec_combination) {
                $explode_spec = explode(':', $spec_combination);
                $spec_id = $explode_spec[0];
                $spec_value_id = $explode_spec[1];

                // ios wants string
                if ($spec_value_id) {
                    $temp_sku['s'][] = (string)$spec_value_id;
                    $temp_sku[$spec_obj[$spec_id] ?: 's0'] = (int)$spec_value_id;
                }
            }
            $goods_detail['sku']['list'][] = $temp_sku;

            $goods_detail['min_price'] = reset($goods_data['sku_list'])['sku_id'] == $sku['sku_id']
                ? $sku['price'] : ($goods_detail['min_price'] <= $sku['price'] ? $goods_detail['min_price'] : $sku['price']);
            $goods_detail['min_market_price'] = reset($goods_data['sku_list'])['sku_id'] == $sku['sku_id']
                ? $sku['market_price'] : ($goods_detail['min_market_price'] <= $sku['market_price'] ? $goods_detail['min_market_price'] : $sku['market_price']);
            $goods_detail['max_price'] = reset($goods_data['sku_list'])['sku_id'] == $sku['sku_id']
                ? $sku['price'] : ($goods_detail['max_price'] >= $sku['price'] ? $goods_detail['max_price'] : $sku['price']);
            $goods_detail['max_market_price'] = reset($goods_data['sku_list'])['sku_id'] == $sku['sku_id']
                ? $sku['market_price'] : ($goods_detail['max_market_price'] >= $sku['market_price'] ? $goods_detail['max_market_price'] : $sku['market_price']);
        }


        $return_data['goods_detail'] = $goods_detail;
        return json(['code' => 1, 'message' => '', 'data' => $return_data]);
    }
    /**
     * wap 积分商品结算
     */
    public function orderInfo()
    {
        $sku_list = request()->post('sku_list/a');
        $address_id = request()->post('address_id');
        $sku_id = $sku_list[0]['sku_id'];
        $integral_gs = new VslIntegralGoodsSkuModel();
        $is_integral_sku = $integral_gs->getInfo(['sku_id' => $sku_id]);
        if (empty($sku_list) || empty($is_integral_sku)) {
            return json(['code' => -1, 'message' => '不存在商品信息']);
        }
        $msg = '';
        $payment_info = $this->integralServer->paymentData($sku_list, $msg);
        if ($payment_info['code'] == -2){
            return json($payment_info);
        }

        if (empty($address_id)) {
            $address_condition['uid'] = $this->uid;
            $address_condition['is_default'] = 1;
        } else {
            $address_condition['id'] = $address_id;
        }
        $member_service = new MemberService();
        $address = $member_service->getMemberExpressAddress($address_condition, ['area_province', 'area_city', 'area_district']);
        if (!empty($address)){
            $return_data['address']['address_id'] = $address['id'];
            $return_data['address']['consigner'] = $address['consigner'];
            $return_data['address']['mobile'] = $address['mobile'];
            $return_data['address']['province_name'] = $address['area_province']['province_name'];
            $return_data['address']['city_name'] = $address['area_city']['city_name'];
            $return_data['address']['district_name'] = $address['area_district']['district_name'];
            $return_data['address']['address_detail'] = $address['address'];
            $return_data['address']['zip_code'] = $address['zip_code'];
            $return_data['address']['alias'] = $address['alias'];
        } else {
            $return_data['address'] = (object)[];
        }
        //end 收获地址

        $need_address = false;
//        if (!empty($address['district'])) {
            // 收货地址为不空
        foreach ($payment_info as $shop_id => $shop_info) {
            // 存在需要发货的商品就需要收货地址
            $need_address = $need_address ?: $shop_info['need_address'];
            unset($payment_info[$shop_id]['need_address']);
            $temp_goods = [];
            foreach ($shop_info['goods_list'] as $sku_id => $sku_info) {
                if (in_array($sku_info['goods_exchange_type'], [0])) {
                    // 普通商品才有运费，优惠券、礼品券、余额没有
                    if (empty($temp_goods[$sku_info['goods_id']])) {
                        $temp_goods[$sku_info['goods_id']]['count'] = $sku_info['num'];
                        $temp_goods[$sku_info['goods_id']]['goods_id'] = $sku_info['goods_id'];
                    } else {
                        $temp_goods[$sku_info['goods_id']]['count'] += $sku_info['num'];
                    }
                }
            }
            // 计算邮费
            $payment_info[$shop_id]['shipping_fee'] = empty($temp_goods) ? 0 : $this->integralServer->getIntegralGoodsExpressTemplate($temp_goods, $address['district']);
            $payment_info[$shop_id]['total_amount'] += $payment_info[$shop_id]['shipping_fee'];
        }
//        }
        $return_data['need_address'] = $need_address;
        $return_data['shop'] = array_values($payment_info);

        return json(['code' => 1, 'message' => $msg, 'data' => $return_data]);
    }

    /**
     * wap积分商品属性
     * @return \think\response\Json
     */
    public function goodsAttribute()
    {
        $goods_id = request()->post('goods_id');
        if (empty($goods_id)){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $goods_attribute = $this->integralServer->goodsAttribute(['goods_id' => $goods_id], ['attribute_value']);
        return json(['code' => 1, 'message' => '获取成功', 'data' => $goods_attribute]);
    }

    /**
     * wap我的(订单列表)
     */
    public function orderList()
    {
        $uid = getUserId();
        if (!$uid){
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登陆']);
        }
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size') ?: PAGESIZE;
        $order_status = request()->post('order_status');
        $search_text = request()->post('search_text');

        $condition['is_deleted'] = 0; // 未删除订单
        $condition['order_type'] = 10;// 积分商城订单
        $condition['buyer_id'] = $uid;
        $condition['website_id'] = $this->website_id;
        if (is_numeric($search_text)) {
            $condition['order_no'] = ['LIKE', '%' . $search_text . '%'];
        } elseif (!empty($search_text)) {
            $condition['or'] = true;
            $condition['shop_name'] = ['LIKE', '%' . $search_text . '%'];
            $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
        }
        if ($order_status != '') {
            // $order_status 1 待发货
            if ($order_status == 1) {
                // 订单状态为待发货实际为已经支付未完成还未发货的订单
                $condition['shipping_status'] = 0; // 0 待发货
                $condition['order_status'][] = ['neq', 4]; // 4 已完成
                $condition['order_status'][] = ['neq', 5]; // 5 关闭订单
                $condition['order_status'][] = ['neq', -1]; // -1 售后订单
            } elseif($order_status == 9) {    //调试，记得换回
                //待付款
                $condition['order_status'] = 0;
                $condition['pay_status'] = 0;
            } elseif($order_status == 0) {   //调试，记得换回
                //待付尾款
                $condition['order_status'] = 6;
            }else{
                $condition['order_status'] = $order_status;
            }
        }
        $order_service = new orderService();
        $list = $order_service->getOrderList($page_index, $page_size, $condition, 'create_time DESC');
        $order_list = [];
        foreach ($list['data'] as $k => $order) {
            $order_list[$k]['order_id'] = $order['order_id'];
            $order_list[$k]['order_no'] = $order['order_no'];
            $order_list[$k]['out_order_no'] = $order['out_trade_no'];
            $order_list[$k]['shop_id'] = $order['shop_id'];
            $order_list[$k]['shop_name'] = $order['shop_name'] ?: '自营店';
            $order_list[$k]['order_money'] = $order['order_money'];
            $order_list[$k]['point'] = $order['point'];
            $order_list[$k]['order_status'] = $order['order_status'];
            if(!empty($order['status_name'])){
                $order_list[$k]['status_name'] = $order['status_name'];
            }
            $order_list[$k]['is_evaluate'] = $order['is_evaluate'];
            if(isset($order['member_operation'])){
                $order_list[$k]['member_operation'] = array_merge($order['member_operation'], [['no' => 'detail', 'name' => '订单详情']]);
            }
            foreach ($order['order_item_list'] as $key_sku => $item) {
                $order_list[$k]['order_item_list'][$key_sku]['order_goods_id'] = $item['order_goods_id'];
                $order_list[$k]['order_item_list'][$key_sku]['goods_id'] = $item['goods_id'];
                $order_list[$k]['order_item_list'][$key_sku]['sku_id'] = $item['sku_id'];
                $order_list[$k]['order_item_list'][$key_sku]['goods_name'] = $item['goods_name'];
                $order_list[$k]['order_item_list'][$key_sku]['price'] = $item['price'];
                $order_list[$k]['order_item_list'][$key_sku]['_'] = $item['goods_point'];
                $order_list[$k]['order_item_list'][$key_sku]['num'] = $item['num'];
                $order_list[$k]['order_item_list'][$key_sku]['pic_cover'] = getApiSrc($item['picture']['pic_cover']);
                $order_list[$k]['order_item_list'][$key_sku]['spec'] = $item['spec'];
                $order_list[$k]['order_item_list'][$key_sku]['status_name'] = $item['status_name'];
//                switch ($item['goods_exchange_type']){
//                    case '0':
//                        $order_list[$k]['order_item_list'][$key_sku]['goods_type_name'] = '商品';
//                        break;
//                    case '1':
//                        $order_list[$k]['order_item_list'][$key_sku]['goods_type_name'] = '优惠券';
//                        break;
//                    case '2':
//                        $order_list[$k]['order_item_list'][$key_sku]['goods_type_name'] = '礼品券';
//                        break;
//                    case '3':
//                        $order_list[$k]['order_item_list'][$key_sku]['goods_type_name'] = '余额';
//                        break;
//                    default:
//                        $order_list[$k]['order_item_list'][$key_sku]['goods_type_name'] = '商品';
//                }
            }
        }

        return json([
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'order_list' => $order_list,
                'page_count' => $list['page_count'],
                'total_count' => $list['total_count']
            ]
        ]);
    }

    /**
     * wap订单详情
     */
    public function orderDetail()
    {
        $order_id = request()->post('order_id');
        if (empty($order_id)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }

        $district_model = new DistrictModel();
        $order_service = new OrderService();
        $order_info = $order_service->getOrderDetail($order_id);

        $order_detail['order_id'] = $order_info['order_id'];
        $order_detail['order_no'] = $order_info['order_no'];
        $order_detail['shop_name'] = $order_info['shop_name'];
        $order_detail['shop_id'] = $order_info['shop_id'];
        $order_detail['order_status'] = $order_info['order_status'];
        $order_detail['payment_type_name'] = $order_info['payment_type_name'];
        $order_detail['is_evaluate'] = $order_info['is_evaluate'];
        $order_detail['order_money'] = $order_info['order_money'];
        $order_detail['goods_money'] = $order_info['goods_money'];
        $order_detail['point'] = $order_info['point'];
        $order_detail['shipping_fee'] = $order_info['shipping_money'] - $order_info['promotion_free_shipping'];

        $address_info = $district_model::get($order_info['receiver_district'], ['city.province']);
        $order_detail['receiver_name'] = $order_info['receiver_name'];
        $order_detail['receiver_mobile'] = $order_info['receiver_mobile'];
        $order_detail['receiver_province'] = $address_info->city->province->province_name;
        $order_detail['receiver_city'] = $address_info->city->city_name;
        $order_detail['receiver_district'] = $address_info->district_name;
        $order_detail['receiver_address'] = $order_info['receiver_address'];
        $order_detail['buyer_message'] = $order_info['buyer_message'];
        $order_detail['group_id'] = $order_info['group_id'];
        $order_detail['group_record_id'] = $order_info['group_record_id'];
        $order_detail['store_id'] = $order_info['store_id'];
        if ($order_info['payment_type'] == 6 || $order_info['shipping_type'] == 2) {
            $order_status_info = OrderService\OrderStatus::getSinceOrderStatus()[$order_info['order_status']];
        } else {
            $order_status_info = OrderService\OrderStatus::getOrderCommonStatus()[$order_info['order_status']];
        }
        $order_detail['member_operation'] = $order_status_info['member_operation'];

        $order_detail['no_delivery_id_array'] = [];
        foreach ($order_info['order_goods_no_delive'] as $v_goods) {
            $order_detail['no_delivery_id_array'][] = $v_goods['order_goods_id'];
        }

        $goods_packet_list = [];
        foreach ($order_info['goods_packet_list'] as $k => $v_packet) {
            $goods_packet_list[$k]['packet_name'] = $v_packet['packet_name'];
//            $goods_packet_list[$k]['express_name'] = $v_packet['express_name'];
//            $goods_packet_list[$k]['express_code'] = $v_packet['express_code'];
            $goods_packet_list[$k]['shipping_info'] = $v_packet['shipping_info'];
            $goods_packet_list[$k]['order_goods_id_array'] = [];
            foreach ($v_packet['order_goods_list'] as $k_o => $v_goods) {
                $goods_packet_list[$k]['order_goods_id_array'][] = $v_goods['order_goods_id'];
            }
        }
        $order_detail['goods_packet_list'] = $goods_packet_list;

        $order_goods = [];
        foreach ($order_info['order_goods'] as $k => $v) {
            $order_goods[$k]['order_goods_id'] = $v['order_goods_id'];
            $order_goods[$k]['goods_id'] = $v['goods_id'];
            $order_goods[$k]['goods_name'] = $v['goods_name'];
            $order_goods[$k]['sku_id'] = $v['sku_id'];
            $order_goods[$k]['sku_name'] = $v['sku_name'];
            $order_goods[$k]['price'] = $v['price'];
            $order_goods[$k]['num'] = $v['num'];
            $order_goods[$k]['spec'] = $v['spec'];
            $order_goods[$k]['pic_cover'] = $v['picture_info']['pic_cover'] ? getApiSrc($v['picture_info']['pic_cover']) : '';
        }
        if (!empty($temp_member_refund_operation)) {
            $order_detail['member_operation'] = array_merge($order_detail['member_operation'], $temp_member_refund_operation);
        }


        $order_detail['order_goods'] = $order_goods;

        return json(['code' => 1,
            'message' => '获取成功',
            'data' => $order_detail
        ]);
    }
    /*
     * 积分商城首页商品列表 手动推荐
     * **/
    public function integralIndexGoodsList()
    {
        $goods_ids = request()->post('goods_ids', '');
        $goods_ids_arr = explode(',', $goods_ids);
        if ($goods_ids_arr){
            $condition['ng.goods_id'] = ['in',$goods_ids_arr];
        }
        $exchange_type = [0,1,2,3];
        $coupon = getAddons('coupontype', $this->website_id);
        $giftvoucher = getAddons('giftvoucher', $this->website_id);
        if(!$coupon){
            unset($exchange_type[1]);
        }
        if(!$giftvoucher){
            unset($exchange_type[2]);
        }
        $condition['ng.state'] = 1;
        $condition['ng.goods_exchange_type'] = ['in', $exchange_type];
        $condition['ng.website_id'] = $this->website_id;
        $condition['ng.shop_id'] = 0;
        $goods_view = new VslIntegralGoodsModel();
        $goods_lists = $goods_view->wapIntegralGoods($condition, 'ng.goods_id, ng.goods_name, ng.sales, pic_cover, ng.price as goods_price,ngs.market_price as market_price, ng.price, ng.point_exchange, ng.goods_exchange_type, ng.coupon_type_id, ng.gift_voucher_id');
        if(!$goods_lists){
            return json(['code' => -1, 'message' => '获取失败']);
        }
        $res = json(['code' => 1, 'message' => '获取成功','data' => $goods_lists]);
        return $res;
    }
    /*
     * 发布商品分类确定品类
     * **/
    public function confirmPinleiByCate()
    {
        $cate_id = request()->post('cate_id', 0);
        if($cate_id){
            $integral_cate = new VslIntegralCategoryModel();
            $attr_id = $integral_cate->getInfo(['integral_category_id' => $cate_id], 'attr_id, attr_name');
            return $attr_id;
        }
    }
}