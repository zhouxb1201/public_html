<?php
namespace app\platform\controller;
use addons\distribution\model\VslDistributorCommissionWithdrawModel;
use addons\miniprogram\model\WeixinAuthModel;
use data\model\CustomTemplateModel;
use data\model\VslAccountRecordsModel;
use data\model\VslCmsArticleModel;
use data\model\VslCmsTopicModel;
use data\model\VslExpressCompanyShopRelationModel;
use data\model\VslGoodsCategoryModel;
use data\model\VslGoodsModel;
use data\model\VslMemberBalanceWithdrawModel;
use data\model\VslNvRecordModel;
use data\model\VslOrderShippingFeeModel;
use data\service\Events;
use data\service\Goods as GoodsService;
use data\service\Order as OrderService;
use data\service\User as User;
use think\Db;
use think\helper\Time;
use data\service\Member;
use data\model\VslOrderGoodsViewModel;
use data\service\Article;
use data\service\Config as WebConfig;
/**
 * 后台主界面
 */
class Index extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

   public function index()
    {
        $debug = config('app_debug') == true ? '开发者模式':'部署模式';
        $this->assign('debug',$debug);
        //获取公告
        $cms = new VslCmsTopicModel();
        $cms_info = $cms->getQuery([],'content,title,create_time,topic_id','create_time desc');
        $this->assign("cms_info",$cms_info);
        $cms_infos = $cms->field('*')->where('!FIND_IN_SET('.$this->website_id.',is_check)')->order('create_time desc')->limit(1)->select();
        if($cms_infos){
            $this->assign('cms_infos',$cms_infos[0]);
        }
        //获取站内信
        $mail = new Article();
        $condition['ncc.website_id']=$this->website_id;
        $article_info = $mail->getMailViewList(1,0,$condition,'ncc.message_send_id desc');
        $this->assign("mail_info",$article_info);
        //顶部导航统计数据
        $sale_data = $this->getIndexCount();
        $this->assign("sale_data",$sale_data);
        //订单统计数据
        $order_data = $this->getOrderCount();
        $this->assign("order_data",$order_data);
        //商品数据
        $goods_data = $this->getGoodsCount();
        $this->assign("goods_data",$goods_data);
        //提现数据
        $withdraws_data = $this->getWithdrawCount();
        $this->assign("withdraws_data",$withdraws_data);
        $basic_set =  $this->website->getWebSiteInfo();
        if($basic_set && $basic_set['mall_name']){
            $this->assign("basic_set",1);
        }
        $config  = new WebConfig();
        $list['wx_set'] = $config->getWpayConfig($this->website_id)['is_use'];
        $list['ali_set'] = $config->getAlipayConfig($this->website_id)['is_use'];
        $list['b_set'] = $config->getBpayConfig(0)['is_use'];
        if($list['wx_set'] || $list['ali_set'] || $list['b_set']){
            $this->assign("pay_set",1);
        }
        $list['mobile_message'] = $config->getMobileMessage(0,$this->website_id);
        if($list['mobile_message'] && $list['mobile_message']['is_use'] ){
            $this->assign("mobile_set",1);
        }
        $list['trade_set']= $config->getShopConfig(0,$this->website_id);
        if($list['trade_set'] && $list['trade_set']['order_buy_close_time']){
            $this->assign("trade_set",1);
        }
        $order_service = new OrderService();
        $list['return_set']= $order_service->getShopReturnList(0, $this->website_id);
        if($list['return_set']){
            $this->assign("return_set",1);
        }
        $list['withdraw_set'] = $config->getBalanceWithdrawConfig(0);
        if($list['withdraw_set']){
            $this->assign("withdraw_set",1);
        }
        $express_company_shop_relation_model = new VslExpressCompanyShopRelationModel();
        $list['express_company_set'] = $express_company_shop_relation_model->getInfo(['website_id'=>$this->website_id]);
        if($list['express_company_set']){
            $this->assign("express_company_set",1);
        }
        $vsl_order_shipping_fee = new VslOrderShippingFeeModel();
        $list['shipping_fee_set'] = $vsl_order_shipping_fee->getInfo(['website_id'=>$this->website_id,'is_enabled'=>1]);
        if($list['shipping_fee_set']){
            $this->assign("shipping_fee_set",1);
        }
        $goods = new VslGoodsModel();
        $list['goods_set'] = $goods->getInfo(['website_id'=>$this->website_id,'shop_id'=>0,'state'=>1,'create_time'=>['exp','<> update_time']]);
        if($list['goods_set']){
            $this->assign("goods_set",1);
        }
        $category = new VslGoodsCategoryModel();
        $list['category_set'] = $category->getInfo(['website_id'=>$this->website_id,'create_time'=>['exp','<> update_time']]);
        if($list['category_set']){
            $this->assign("category_set",1);
        }
        $custom_template_model = new CustomTemplateModel();
        $list['custom_website_set'] = $custom_template_model->getInfo(['website_id'=>$this->website_id,'type'=>1,'in_use'=>1,'create_time'=>['exp','<> modify_time']]);
        if($list['custom_website_set']){
            $this->assign("custom_website_set",1);
        }
        $list['custom_shop_set'] = $custom_template_model->getInfo(['website_id'=>$this->website_id,'type'=>2,'in_use'=>1,'create_time'=>['exp','<> modify_time']]);
        if($list['custom_shop_set'] && $this->shopStatus){
            $this->assign("custom_shop_set",1);
        }
        $list['custom_member_set'] = $custom_template_model->getInfo(['website_id'=>$this->website_id,'type'=>4,'in_use'=>1,'create_time'=>['exp','<> modify_time']]);
        if($list['custom_member_set']){
            $this->assign("custom_member_set",1);
        }
        return view($this->style.'index/index');
    }
    public function preview()
    {
        $web_info = $this->website->getWebSiteInfo();
        $text = $this->http.$web_info['realm_ip'].'/wap/mall/index';
        // 判断是否有太阳码 by sgw
        if(getAddons('miniprogram', $this->website_id)){
        $sun_url = $this->getMpCode();
            $this->assign("sun_url",$sun_url);
        }
        $this->assign("wap_url",$text);
        

        return view($this->style.'index/preview');
    }
    public function tipCount(){
        //订单统计数据
        $data= [];
        $order_data = $this->getOrderCount();
        $data['daifahuo'] = $order_data['daifahuo'];
        $data['tuikuanzhong'] = $order_data['tuikuanzhong'];
        //未读站内信
        $message = new Article();
        $unread = $message->getMessageStatus(0);
        $data['unread'] = $unread;
        $uncms = $message->getCmsStatus();
        $data['uncms'] = $uncms;
        $data['total_tips'] =  $data['daifahuo']+$data['tuikuanzhong']+$data['unread']+$data['uncms'];
        return $data;
    }
    /**
     * ajax 加载 店铺 会员 信息
     */
    public function getUserInfo(){
        $auth = new User();
        $user_info = $auth->getUserDetail();
        return $user_info;
    }
    /**
     * 获取 商品 数量       全部    出售中  仓库中  库存预警
     */
    public function getGoodsCount(){
        $goods_count = new GoodsService();
        $goods_count_array = array();
        //全部
        $goods_count_array['all'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id]);
        //仓库中
        $goods_count_array['store'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>0]);
        //出售中
        $goods_count_array['sale'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id,'state'=>1]);
        //库存预警
        $goods_count_array['warning'] = $goods_count->getGoodsCount(['website_id'=>$this->website_id, 'min_stock_alarm' => array("neq", 0), 'stock' => array("exp", "<= min_stock_alarm")]);
        return $goods_count_array;
    }
    /**
     * 获取 订单数量       待发货    退款中   本月成交和上月成交
     */
    public function getOrderCount(){
        $order = new OrderService();
        $start_date=mktime(0,0,0,date('m'),1,date('Y'));
        $end_date=mktime(23,59,59,date('m'),date('t'),date('Y'));
        $start_date1 = mktime(0, 0 , 0,date("m")-1,1,date("Y"));
        $end_date1 = mktime(23,59,59,date("m") ,0,date("Y"));
        $order_count_array = array();
        $order_count_array['daifahuo'] = $order->getOrderCount(['order_status'=>1,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id]);//待发货
        $order_count_array['tuikuanzhong'] = $order->getOrderCount(['order_status'=>-1,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id]);//退款中
        $order_count_array['complete'] = $order->getOrderCount(['create_time' => [[">",$start_date],["<",$end_date]],'order_status'=>4,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id]);//本月成交
        $order_count_array['complete1'] = $order->getOrderCount(['create_time' => [[">",$start_date1],["<",$end_date1]],'order_status'=>4,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id]);//上月成交
        return $order_count_array;
    }
    /**
     * 获取 余额提现中数量
     */
    public function getWithdrawCount(){
        $commission_withdraw = new VslDistributorCommissionWithdrawModel();
        $Withdraw = new VslMemberBalanceWithdrawModel();
        $Withdraw_count_array = array();
        $Withdraw_count_array['member_withdraw'] = abs($Withdraw->getSum(['status'=>['in',[1,2]],'website_id'=>$this->website_id],'cash'));//会员提现中
        $Withdraw_count_array['commission_withdraw'] = abs($commission_withdraw->getSum(['status'=>['in',[1,2]],'website_id'=>$this->website_id],'cash'));//佣金提现中
        $account = new VslAccountRecordsModel();
        $start_date=mktime(0,0,0,date('m'),1,date('Y'));
        $end_date=mktime(23,59,59,date('m'),date('t'),date('Y'));
        $start_date1 = mktime(0, 0 , 0,date("m")-1,1,date("Y"));
        $end_date1 = mktime(23,59,59,date("m") ,0,date("Y"));
        $Withdraw_count_array['commission1'] = abs($account->getSum(['create_time' => [[">",$start_date],["<",$end_date]],'account_type'=>['in',[1,2,34]],'website_id'=>$this->website_id],'money'));//本月佣金提现
        $Withdraw_count_array['commission2'] = abs($account->getSum(['create_time' => [[">",$start_date1],["<",$end_date1]],'account_type'=>['in',[1,2,34]],'website_id'=>$this->website_id],'money'));//上月佣金提现
        return $Withdraw_count_array;
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
        $condition1 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'order_status'=>[['>',0],['<',5]]];
        $condition2 = ['create_time' => [[">",$start_date1],["<",$end_date1]],'website_id'=>$this->website_id,'order_status'=>[['>',0],['<',5]]];
        $sale_money_day1 = $order->getShopSaleSum($condition1);
        $sale_money_day2 = $order->getShopSaleSum($condition2);
        //今日和昨日支付订单量
        $sale_num_day1 = $order->getShopSaleNumSum($condition1);
        $sale_num_day2 = $order->getShopSaleNumSum($condition2);
        //今日和昨日支付人数
        $sale_member_day1 = $order->getShopSaleMemberNumSum($condition1);
        $sale_member_day2 = $order->getShopSaleMemberNumSum($condition2);
        //今日和昨日新增会员
        $member = new Member();
        $member_num1 = $member->getMemberCount(['reg_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id]);
        $member_num2 = $member->getMemberCount(['reg_time' => [[">",$start_date1],["<",$end_date1]],'website_id'=>$this->website_id]);
        $member_num = $member->getMemberCount(['website_id'=>$this->website_id]);
        $user = new VslNvRecordModel();
        //今日和昨日活跃用户
        $visitor_num1 = $user->getCount(['create_time' => ['between',[$start_date,$end_date]],'website_id' => $this->website_id,'shop_id'=>0]);
        $visitor_num2 = $user->getCount(['create_time' => ['between',[$start_date1,$end_date1]],'website_id' => $this->website_id,'shop_id'=>0]);
        //今日新增分销商
        $commission_num1 = $member->getMemberCount(['reg_time' => [[">",$start_date],["<",$end_date]],'isdistributor'=>2,'website_id'=>$this->website_id]);
        $commission_num2 = $member->getMemberCount(['reg_time' => [[">",$start_date1],["<",$end_date1]],'isdistributor'=>2,'website_id'=>$this->website_id]);
        $commission_num3 = $member->getMemberCount(['isdistributor'=>1,'website_id'=>$this->website_id]);
        $commission_num = $member->getMemberCount(['website_id'=>$this->website_id,'isdistributor'=>2]);
        $result = array(
            "visitor_num1"=>$visitor_num1,
            "visitor_num2"=>$visitor_num2,
            "sale_money_day1"=>$sale_money_day1,
            "sale_num_day1"=>$sale_num_day1,
            "sale_member_day1"=>$sale_member_day1,
            "sale_money_day2"=>$sale_money_day2,
            "sale_num_day2"=>$sale_num_day2,
            "sale_member_day2"=>$sale_member_day2,
            "member_num1"=>$member_num1,
            "member_num2"=>$member_num2,
            "member_num"=>$member_num,
            "commission_num1"=>$commission_num1,
            "commission_num2"=>$commission_num2,
            "commission_num3"=>$commission_num3,
            "commission_num"=>$commission_num,
            "assistant_num"=>0,
            "commission"=>sprintf('%.2f', 0)
        );
        return $result;
    }


    /**
     * 近七日自营订单走势
     */
    public function getOrderMovementsChartCount(){
        if (request()->isAjax()) {
            list($start_date, $end_date) = Time::dayToNow(6,true);
            $start_date = date("Y-m-d H:i:s", $start_date);
            $end_date = date("Y-m-d H:i:s", $end_date);
            $begintime = strtotime($start_date);
            $endtime = strtotime($end_date);
            $between=5;
            if($endtime>$begintime){
                $between = ceil(($endtime-$begintime)/7/24/3600);
            }
            $shop = new OrderService();
            if ($start_date != "") {
                $condition["create_time"][] = [
                    ">",
                    $begintime
                ];
            }
            if ($end_date != "") {
                $condition["create_time"][] = [
                    "<",
                    $endtime
                ];
            }
            $condition["website_id"] = $this->website_id;
            $condition['is_deleted'] = 0;
            $list = $shop->getBusinessProfileList($begintime,$endtime, $condition);
            $date_string = array();
            $allOrderStat = array();
            $payOrderStat = array();
            $returnOrderStat = array();
            $turnover = array();
            $money = array();
            $sum = array();
            foreach ($list as $k => $v) {
                $date_string[] = $k;
                $allOrderStat['name'] = '订单量';
                $allOrderStat['data'][] = $v['count'];
                $payOrderStat['name'] = '付款订单';
                $payOrderStat['data'][] = $v['paycount'];
                $returnOrderStat['name'] = '售后订单';
                $returnOrderStat['data'][] = $v['returncount'];
                $turnover['name'] = '交易额';
                $turnover['data'][] = $v['sum'];
            }
            $sum[] = $allOrderStat;
            $sum[] = $payOrderStat;
            $sum[] = $returnOrderStat;
            $money[] = $turnover;
            $array = [
                $date_string,
                $sum,
                $money,
                $between
            ];
            return $array;
        }
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
            $condition["no.is_deleted"] = 0;
            $condition["no.order_status"] = [['>','0'],['<','5']];
            $orderGoods = new VslOrderGoodsViewModel();
            $list = $orderGoods->getOrderGoodsRankList(1, 0, $condition, $order);
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
        $visitor_num = $user->getCount(['create_time' => ['between',[$start_date,$end_date]],'website_id' => $this->website_id,'shop_id'=>0]);
        $order = new OrderService();
        //下单买家数和下单金额
        $order_num = $order->getMemberOrderCount(['create_time' => ['between',[$start_date,$end_date]],'website_id' => $this->website_id]);
        $order_money = $order->getOrderMoneySum(['create_time' => ['between',[$start_date,$end_date]],'website_id' => $this->website_id],'order_money');
        //支付买家数和支付金额
        $pay_num = $order->getMemberOrderCount(['create_time' => ['between',[$start_date,$end_date]],'website_id' => $this->website_id,'order_status'=>[['>',0],['<',5]]]);
        $user_money = $order->getOrderMoneySum(['create_time' => ['between',[$start_date,$end_date]],'website_id' => $this->website_id,'order_status'=>[['>',0],['<',5]]],'order_money');
        $pay_money = $user_money;
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
    /**
     * 移动端首页二维码
     */
    public function getWapCode(){
        $web_info = $this->website->getWebSiteInfo();
        $text = $this->http.$web_info['realm_ip'].'/wap/mall/index';
        getQRcodeNotSave($text,'');
    }
    /***
     * 临时手动刷新开放平台access_token
     */
    public function refresh()
    {
        $event = new Events();
        $res = $event->refreshAuthAccessToken($this->website_id);
        if (!$res) {
            echo 'ok!';
        } else {
            echo 'error!';
        }
    }
    /***
     * 获取小程序太阳码
     */
    public function getMpCode()
    {
        $wx_auth_model = new WeixinAuthModel();
        $condition = [
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id
        ];
        $sun_code_url = $wx_auth_model->getInfo($condition, 'sun_code_url');

        return $sun_code_url['sun_code_url'];
    }
}
