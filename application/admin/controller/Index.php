<?php

namespace app\admin\controller;

use addons\miniprogram\model\WeixinAuthModel;
use addons\miniprogram\service\MiniProgram as miniProgramService;
use addons\shop\model\VslShopModel;
use addons\shop\model\VslShopWithdrawModel;
use data\extend\WchatOpen;
use data\model\CustomTemplateModel;
use data\model\UserModel;
use data\model\VslExpressCompanyShopRelationModel;
use data\model\VslGoodsModel;
use data\model\VslOrderShippingFeeModel;
use data\service\Goods as GoodsService;
use data\service\Order as OrderService;
use data\service\User as User;
use data\service\AdminUser;
use think\Db;
use think\helper\Time;
use data\model\AdminUserModel;
use data\model\ModuleModel;
use data\model\VslNvRecordModel;
use data\model\VslOrderGoodsViewModel;

/**
 * 后台主界面
 *
 * @author  www.vslai.com
 *        
 */
class Index extends BaseController {
    protected $http;
    public function __construct() {
        parent::__construct();
        $is_ssl = \think\Request::instance()->isSsl();
        $this->http = "http://";
        if($is_ssl){
            $this->http = 'https://';
        }
        $user = new AdminUserModel();
        $condition = ['uid'=>$this->uid];
        $entry_ids = $user->getInfo($condition,'entry_ids')['entry_ids'];
        $this->assign("entry_ids", $entry_ids);
        //顶部导航统计数据
        $sale_data = $this->getIndexCount();
        $this->assign("sale_data",$sale_data);
        //订单统计数据
        $order_data = $this->getOrderCount();
        $this->assign("order_data",$order_data);
        //商品统计数据
        $goods_data = $this->getGoodsCount();
        $this->assign("goods_data",$goods_data);
        //提现统计数据
        $withdraws_data = $this->getWithdrawCount();
        $this->assign("withdraws_data",$withdraws_data);
        $shop = new VslShopModel();
        $base_info = $shop->getInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
        if($base_info['shop_logo']){
            $this->assign("basic_set",1);
        }
        $express_company_shop_relation_model = new VslExpressCompanyShopRelationModel();
        $list['express_company_set'] = $express_company_shop_relation_model->getInfo(['shop_id' => $this->instance_id,'website_id'=>$this->website_id]);
        if($list['express_company_set']){
            $this->assign("express_company_set",1);
        }
        $vsl_order_shipping_fee = new VslOrderShippingFeeModel();
        $list['shipping_fee_set'] = $vsl_order_shipping_fee->getInfo(['shop_id' => $this->instance_id,'website_id'=>$this->website_id,'is_enabled'=>1]);
        if($list['shipping_fee_set']){
            $this->assign("shipping_fee_set",1);
        }
        $goods = new VslGoodsModel();
        $list['goods_set'] = $goods->getInfo(['shop_id' => $this->instance_id,'website_id'=>$this->website_id,'state'=>1]);
        if($list['goods_set']){
            $this->assign("goods_set",1);
        }
        $custom_template_model = new CustomTemplateModel();
        $list['custom_shop_set'] = $custom_template_model->getInfo(['shop_id' => $this->instance_id,'website_id'=>$this->website_id,'type'=>2,'in_use'=>1,'create_time'=>['exp','<> modify_time']]);
        if($list['custom_shop_set']){
            $this->assign("custom_shop_set",1);
        }
    }

    public function index() {
        // 销售排行
        $goods_rank = $this->getGoodsRealSalesRank();
        $this->assign("goods_list", $goods_rank);
        return view($this->style . 'Index/index');
    }
    public function preview()
    {
        $web_info = $this->website->getWebSiteInfo();

        if($web_info['realm_ip']){
            $text = $this->http . $web_info['realm_ip'].'/wap/shop/home/'.$this->instance_id;
        }else{
            $text = $this->http . $_SERVER['HTTP_HOST'] .'/wap/shop/home/'.$this->instance_id;
        }
        $this->assign("wap_url",$text);  
        // 判断是否有太阳码 by sgw
        if(getAddons('miniprogram', $this->website_id) && getAddons('shop', $this->website_id)){
            $sun_url = $this->getMpCode();
            $this->assign("sun_url",$sun_url);
        }
        return view($this->style.'Index/preview');
    }

    /**
     * ajax 加载 店铺 会员 信息
     */
    public function getUserInfo() {
        $auth = new User();
        $user_info = $auth->getUserDetail($this->uid);
        return $user_info;
    }

    /**
     * 商品列表获取 商品 数量 全部 出售中 已审核 已下架 库存预警数
     */
    public function getGoodsCountList() {
        $goods_count = new GoodsService();
        $goods_count_array = array();
        // 全部
        $goods_count_array['all'] = $goods_count->getGoodsCount([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
        ]);
        // 出售中
        $goods_count_array['sale'] = $goods_count->getGoodsCount([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'state' => 1
        ]);
        // 仓库中
        $goods_count_array['audit'] = $goods_count->getGoodsCount([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'state' => 0
        ]);
        // 违规下架
        $goods_count_array['shelf'] = $goods_count->getGoodsCount([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'state' => 10
        ]);
        //库存低于预警值的商品数
        $goods_count_array['stock_warning'] = $goods_count->getGoodsCount([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'min_stock_alarm' => array("neq", 0),
            'stock' => array("exp", "<= min_stock_alarm")
        ]);
        // 库存为0的商品数
        $goods_count_array['sold_out'] = $goods_count->getGoodsCount([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'stock' => 0
        ]);
        // 待审核的商品数
        $goods_count_array['waitaudit'] = $goods_count->getGoodsCount([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'state' => 11
        ]);
        // 审核不通过的商品数
        $goods_count_array['unaudit'] = $goods_count->getGoodsCount([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'state' => 12
        ]);
        return $goods_count_array;
    }
    /**
     * 移动端首页二维码
     */
    public function getWapCode(){
        $web_info = $this->website->getWebSiteInfo();
        if($web_info['realm_ip']){
            $text = $this->http . $web_info['realm_ip'].'/wap/shop/home/'.$this->instance_id;
        }else{
            $text = $this->http . $_SERVER['HTTP_HOST'] .'/wap/shop/home/'.$this->instance_id;
        }
        getQRcodeNotSave($text,'');
    }
    /**
     * 获取 商品 数量       全部    出售中  仓库中  库存预警
     */
    public function getGoodsCount(){
        $goods_count = new GoodsService();
        $goods_count_array = array();
        //全部
        $goods_count_array['all'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id]);
        //仓库中
        $goods_count_array['store'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>0,'shop_id'=>$this->instance_id]);
        //出售中
        $goods_count_array['sale'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>1,'shop_id'=>$this->instance_id]);
        //库存预警
        $goods_count_array['warning'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id,'min_stock_alarm' => array("neq", 0), 'stock' => array("exp", "<= min_stock_alarm")]);
        return $goods_count_array;
    }
    /**
     * 获取 订单数量       待发货     退款中
     */
    public function getOrderCount(){
        $order = new OrderService();
        $order_count_array = array();
        $start_date=mktime(0,0,0,date('m'),1,date('Y'));
        $end_date=mktime(23,59,59,date('m'),date('t'),date('Y'));
        $start_date1 = mktime(0, 0 , 0,date("m")-1,1,date("Y"));
        $end_date1 = mktime(23,59,59,date("m") ,0,date("Y"));
        $order_count_array['daifahuo'] = $order->getOrderCount(['order_status'=>1,'website_id'=>$this->website_id,'shop_id'=>$this->instance_id]);//待发货
        $order_count_array['tuikuanzhong'] = $order->getOrderCount(['order_status'=>-1,'website_id'=>$this->website_id,'shop_id'=>$this->instance_id]);//退款中
        $order_count_array['total_tips'] =  $order_count_array['daifahuo']+$order_count_array['tuikuanzhong'];
        $order_count_array['complete'] = $order->getOrderCount(['create_time' => [[">",$start_date],["<",$end_date]],'order_status'=>4,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id]);//本月成交
        $order_count_array['complete1'] = $order->getOrderCount(['create_time' => [[">",$start_date1],["<",$end_date1]],'order_status'=>4,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id]);//上月成交
        return $order_count_array;
    }
    /**
     * 获取 余额提现中数量
     */
    public function getWithdrawCount(){
        $Withdraw = new VslShopWithdrawModel();
        $Withdraw_count_array = array();
        $Withdraw_count_array['withdraw'] = $Withdraw->getCount(['status'=>['in',[1,2]],'shop_id'=>$this->instance_id,'website_id'=>$this->website_id]);//提现中
        return $Withdraw_count_array;
    }
    /**
     * 订单 图表 数据
     */
    public function getOrderChartCount() {
        $type = request()->post('date', 4);
        $order = new OrderService();
        $data = array();
        if ($type == 1) {
            list ($start, $end) = Time::today();
            for ($i = 0; $i < 24; $i ++) {
                $date_start = date("Y-m-d H:i:s", $start + 3600 * $i);
                $date_end = date("Y-m-d H:i:s", $start + 3600 * ($i + 1));
                $count = $order->getOrderCount([
                    'shop_id' => $this->instance_id,
                    'create_time' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[$i] = array(
                    $i . ':00',
                    $count
                );
            }
        } elseif ($type == 2) {
            list ($start, $end) = Time::yesterday();
            for ($j = 0; $j < 24; $j ++) {
                $date_start = date("Y-m-d H:i:s", $start + 3600 * $j);
                $date_end = date("Y-m-d H:i:s", $start + 3600 * ($j + 1));
                $count = $order->getOrderCount([
                    'shop_id' => $this->instance_id,
                    'create_time' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[$j] = array(
                    $j . ':00',
                    $count
                );
            }
        } elseif ($type == 3) {
            list ($start, $end) = Time::week();
            $start = $start - 604800;
            for ($j = 0; $j < 7; $j ++) {
                $date_start = date("Y-m-d H:i:s", $start + 86400 * $j);
                $date_end = date("Y-m-d H:i:s", $start + 86400 * ($j + 1));
                $count = $order->getOrderCount([
                    'shop_id' => $this->instance_id,
                    'create_time' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[$j] = array(
                    '星期' . ($j + 1),
                    $count
                );
            }
        } elseif ($type == 4) {
            list ($start, $end) = Time::month();
            for ($j = 0; $j < ($end + 1 - $start) / 86400; $j ++) {
                $date_start = date("Y-m-d H:i:s", $start + 86400 * $j);
                $date_end = date("Y-m-d H:i:s", $start + 86400 * ($j + 1));
                $count = $order->getOrderCount([
                    'shop_id' => $this->instance_id,
                    'create_time' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[$j] = array(
                    (1 + $j) . '日',
                    $count
                );
            }
        }
        return $data;
    }
    /**
     *销售统计
     */
    public function getIndexCount(){
        $start_date = strtotime(date("Y-m-d"),time());//date('Y-m-d 00:00:00', time());
        $end_date = strtotime(date('Y-m-d',strtotime('+1 day')));//date('Y-m-d 00:00:00', strtotime('this day + 1 day'));
        $start_date1 = strtotime(date("Y-m-d"),time()-24*3600);//date('Y-m-d 00:00:00', time());
        $end_date1 = strtotime(date('Y-m-d',time()));//date('Y-m-d 00:00:00', strtotime('this day + 1 day'));
        $order= new OrderService();
        //今日和昨日销售额
        $condition1 = ['create_time' => [[">",$start_date],["<",$end_date]],'shop_id'=>$this->instance_id,'website_id'=>$this->website_id,'order_status'=>[['>',0],['<',5]]];
        $condition2 = ['create_time' => [[">",$start_date1],["<",$end_date1]],'shop_id'=>$this->instance_id,'website_id'=>$this->website_id,'order_status'=>[['>',0],['<',5]]];
        $sale_money_day1 = $order->getShopSaleSum($condition1);
        $sale_money_day2 = $order->getShopSaleSum($condition2);
        //今日和昨日支付订单量
        $sale_num_day1 = $order->getShopSaleNumSum($condition1);
        $sale_num_day2 = $order->getShopSaleNumSum($condition2);
        //今日和昨日支付人数
        $sale_member_day1 = $order->getShopSaleMemberNumSum($condition1);
        $sale_member_day2 = $order->getShopSaleMemberNumSum($condition2);
        $result = array(
            "sale_money_day1"=>$sale_money_day1,
            "sale_num_day1"=>$sale_num_day1,
            "sale_member_day1"=>$sale_member_day1,
            "sale_money_day2"=>$sale_money_day2,
            "sale_num_day2"=>$sale_num_day2,
            "sale_member_day2"=>$sale_member_day2,
        );
        return $result;
    }
    /**
     * 商品销售排行
     *
     * @return unknown
     */
    public function getGoodsRealSalesRank() {
        $goods = new GoodsService();
        $goods_list = $goods->getGoodsRank(array(
            "shop_id" => $this->instance_id
        ));
        return $goods_list;
    }

    /**
     * 设置操作提示是否显示
     * 保存7天
     */
    public function setWarmPromptIsShow() {
        $value = request()->post("value", "show");
        $res = cookie("warm_promt_is_show", $value, 60 * 60 * 24 * 7);
        return $this->getWarmPromptIsShow();
    }

    /**
     * 近七日自营订单走势
     */
    public function getOrdermovementsChartCount() {
        $order = new OrderService();
        list($start, $end) = Time::dayToNow(6, true);
        $orderType = ['订单量', '付款订单', '售后订单'];
        $data = array();
        $data['ordertype'] = $orderType;
        for ($i = 0; $i < count($orderType); $i++) {
            switch ($orderType[$i]) {
                case '售后订单':
                    $status = ['<', '0'];
                    break;
                case '付款订单':
                    $status = ['between', ['1', '4']];
                    break;
                default:
                    $status = '';
                    break;
            }
            for ($j = 0; $j < ($end + 1 - $start) / 86400; $j++) {
                $data['day'][$j] = date("Y-m-d", $start + 86400 * $j);
                $date_start = strtotime(date("Y-m-d H:i:s", $start + 86400 * $j));
                $date_end = strtotime(date("Y-m-d H:i:s", $start + 86400 * ($j + 1)));

                if ($status) {
                    $count = $order->getOrderCount(['website_id' => $this->website_id,'shop_id'=>$this->instance_id, 'create_time' => ['between', [$date_start, $date_end]], 'order_status' => $status]);
                } else {
                    $count = $order->getOrderCount(['website_id' => $this->website_id, 'shop_id'=>$this->instance_id,'create_time' => ['between', [$date_start, $date_end]]]);
                }
                $aCount[$j] = $count;
                $data['all'][$i]['name'] = $orderType[$i];
                $data['all'][$i]['type'] = 'line';
                $data['all'][$i]['data'] = $aCount;
//                $data[$j] = array(
//                    (1+$j).'日',$count
//                );
            }
        }
        return $data;
    }

    /**
     * 用户修改密码
     */
    public function ModifyPassword() {
        $adminUser = new AdminUser();
        $uid = $adminUser->getSessionUid();
        $old_pass = request()->post('old_pass', '');
        $new_pass = request()->post('new_pass', '');
        $retval = $adminUser->ModifyUserPassword($uid, $old_pass, $new_pass);
        if ($retval) {
            $this->addUserLog('后台用户修改密码', $uid);
        }
        return AjaxReturn($retval);
    }
    /*
     * 热销排行
     */
    public function goodsAnalysis(){
        if (request()->isAjax()) {
            if($_POST['times']==7){
                list($start_date, $end_date) = Time::dayToNow(6,true);
            }elseif ($_POST['times']==-1){
                list($start_date, $end_date) = Time::yesterday();
            }else{
                list($start_date, $end_date) = Time::today();
            }
            $start_date = date("Y-m-d H:i:s", $start_date);
            $end_date = date("Y-m-d H:i:s", $end_date);
            $condition = array();
            if ($start_date != "") {
                $condition["no.create_time"][] = [
                    ">",
                    strtotime($start_date)
                ];
            }
            if ($end_date != "") {
                $condition["no.create_time"][] = [
                    "<",
                    strtotime($end_date)
                ];
            }
            $order = 'sum(nog.num) desc';
            $condition["no.website_id"] = $this->website_id;
            $condition["no.shop_id"] = $this->instance_id;
            $condition["no.is_deleted"] = 0;
            $condition["no.order_status"] = [['neq','0'],['neq','5']];
            $orderGoods = new VslOrderGoodsViewModel();
            $list = $orderGoods->getOrderGoodsRankList(1, 7, $condition, $order);
            return $list;
        }
    }
    /**
     * 交易状况
     */
    public function getTransactionStatus(){
//        if($_POST['times']==7){
            list($start_date, $end_date) = Time::dayToNow(6,true);
//        }else{
//            list($start_date, $end_date) = Time::today();
//        }
        $user = new VslNvRecordModel();
        //访客人数
        $visitor_num = $user->getCount(['create_time' => ['between',[$start_date,$end_date]],'shop_id'=>$this->instance_id,'website_id' => $this->website_id]);
        $order = new OrderService();
        //下单买家数和下单金额
        $order_num = $order->getMemberOrderCount(['create_time' => ['between',[$start_date,$end_date]],'shop_id'=>$this->instance_id,'website_id' => $this->website_id]);
        $order_money = $order->getOrderMoneySum(['create_time' => ['between',[$start_date,$end_date]],'shop_id'=>$this->instance_id,'website_id' => $this->website_id],'order_money');
        //支付买家数和支付金额
        $pay_num = $order->getMemberOrderCount(['create_time' => ['between',[$start_date,$end_date]],'shop_id'=>$this->instance_id,'website_id' => $this->website_id,'order_status'=>['>',0]]);
        $user_platform_money = $order->getOrderMoneySum(['create_time' => ['between',[$start_date,$end_date]],'shop_id'=>$this->instance_id,'website_id' => $this->website_id,'order_status'=>['>',0]],'user_platform_money');
        $user_pay_money = $order->getOrderMoneySum(['create_time' => ['between',[$start_date,$end_date]],'shop_id'=>$this->instance_id,'website_id' => $this->website_id,'order_status'=>['>',0]],'pay_money');
        $pay_money = $user_platform_money+$user_pay_money;
        if($order_num && $visitor_num){
            //客单价
            $unit_price = number_format($pay_money/$order_num,2,'.','');
            //下单转化率和下单支付转化率
            $order_conversion = (number_format($order_num/$visitor_num,2,'.','')*100).'%';
            $orderpay_conversion = (number_format($pay_num/$order_num,2,'.','')*100).'%';
            //支付转化率
            $pay_conversion = (number_format($pay_num/$visitor_num,2,'.','')*100).'%';
        }else{
            $unit_price=0;
            $order_conversion=0;
            $pay_conversion = 0;
            $orderpay_conversion = 0;
        }

        $result = array(
            "visitor_num"=>$visitor_num,
            "order_num"=>$order_num,
            "order_money"=>$order_money,
            "pay_num"=>$pay_num,
            "pay_money"=>$pay_money,
            "unit_price"=>$unit_price,
            "order_conversion"=>$order_conversion,
            "orderpay_conversion"=>$orderpay_conversion,
            "pay_conversion"=>$pay_conversion,
        );
        return $result;
    }
    /***
     * 获取小程序太阳码
     */
    public function getMpCode()
    {
        $shopModel= new VslShopModel();
        $shop_id = $this->instance_id ?: 0;
        $url = $shopModel->getInfo(['website_id' => $this->website_id, 'shop_id' => $shop_id ] , 'sun_code_url');
        if (!empty($url['sun_code_url'])) {
            return $url['sun_code_url'];
        }
        $weixin_auth_model = new WeixinAuthModel();
        $auth_result = $weixin_auth_model->getInfo(['website_id' => $this->website_id], 'authorizer_access_token');
        $authorizer_access_token = $auth_result['authorizer_access_token'] ?: '';

        $data = [
            'path' => 'pages/shop/home/index?shopId='.$shop_id
        ];
        $wchat_open = new WchatOpen();
        $imgRes = $wchat_open->getSunCodeApi($authorizer_access_token, $data, 1);
        if (empty($imgRes)) {
            return ;
        }
        // 图片处理
        try{
            $path = 'upload/sunCode/shop/'. $this->website_id .'/'.$shop_id;
            $imgName = !empty($this->nick_name) ? md5($this->nick_name) : time();
            // 上传云端
            $sunUrlFromYun = getImageFromYun($imgRes, $path, $imgName);
            if ($sunUrlFromYun) {
                // 图片链接写入数据库
                $shopModel->update([
                    'sun_code_url' => $sunUrlFromYun
                ],
                    [
                        'shop_id' => $shop_id,
                        'website_id' => $this->website_id
                    ]);
            }

            return $sunUrlFromYun;
        } catch (\Exception $e) {
            $log = [
                'content' => $shop_id.'的太阳码存储错误:'.$e->getMessage(),
                'time' => date('Y-m-d H:i:s', time())
            ];
            Db::table('sys_log')->insert($log);
        }
    }

    /***
     * 更新应用权限
     */
    public function updateWebsiteAddons()
    {
        checkWebNeedUpdate($this->website_id, $this->instance_id);
        return true;
    }
}
