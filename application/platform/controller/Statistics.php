<?php
namespace app\platform\controller;

use data\model\VslOrderGoodsModel;
use data\service\Order;
use data\service\Goods;
use data\service\Member;
use data\model\VslOrderGoodsViewModel;
use data\model\VslOrderModel;

/**
 * 系统模块控制器
 *
 * @author  www.vslai.com
 *        
 */
class Statistics extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 会员统计
     */
    public function userStat()
    {
        $user = new Member();
        $user_count_num = $user->getMemberCount(array("website_id" => $this->website_id));
        $user_today_num = $user->getMemberCount(array(
            "reg_time" => strtotime(date("Y-m-d", time())),
            "website_id" => $this->website_id
        ));
        $month_end = date('Y-m-d', strtotime(date("Y-m-d"),time()));
        $month_begin = date('Y-m-d', strtotime("$month_end -1 month"));
        $condition["reg_time"] = [
            [
                ">",
                strtotime($month_begin)
            ],
            [
                "<",
                strtotime($month_end)
            ]
        ];

        $condition["website_id"] = $this->website_id;
        $user_month_num = $user->getMemberCount($condition);
        $this->assign("user_count_num", $user_count_num);
        $this->assign("user_today_num", $user_today_num);
        $this->assign("user_month_num", $user_month_num);
        $this->assign("start_date", $month_begin);
        $this->assign("end_date", $month_end);
        return view($this->style . 'Statistics/userStat');
    }

    /**
     * 会员统计
     */
    public function getMemberMonthCount()
    {
        $start_date = $_POST["start_date"] ? $_POST['start_date'] : '';
        $end_date = $_POST["end_date"]? $_POST['end_date'] : '';
        $member = new Member();
        $member_list = $member->getMemberMonthCount($start_date, $end_date);
        $date_string = array();
        $user_num = array();
        foreach ($member_list as $k => $v) {
            $date_string[] = $k;
            $user_num[] = $v;
        }
        $array = [
            $date_string,
            $user_num
        ];
        // 或区域一段时间内的用户数量
        return $array;
    }

    /*
     * 经营概况
     */
    public function businessProfile(){
        if (request()->isAjax()) {
            $start_date = ! empty($_POST['start_date']) ? $_POST['start_date'] : '';
            $end_date = ! empty($_POST['end_date']) ?  $_POST['end_date'] : '';
            $shop_type = ! empty($_POST['shop_type']) ? $_POST['shop_type'] : 0;
            if($start_date){
                $begintime = strtotime($start_date);
            }
            if($end_date){
                $endtime = strtotime($end_date)+86399;
            }
            $between=5;
            if($endtime>$begintime){
                $between = ceil(($endtime-$begintime)/7/24/3600);
            }
            $shop = new Order();
            if ($begintime != "") {
                $condition["create_time"][] = [
                    ">",
                    $begintime
                ];
            }
            if ($endtime != "") {
                $condition["create_time"][] = [
                    "<",
                    $endtime
                ];
            }
            if ($shop_type == 1) {
                $condition["shop_id"] = 0;
            } elseif ($shop_type == 2) {
                $condition["shop_id"] = ['>', 0];
            }
            $condition["website_id"] = $this->website_id;
            $condition['is_deleted'] = 0;
            $list = $shop->getBusinessProfileList($begintime,$endtime,$condition);
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
        } else {
            $month_end = date('Y-m-d', strtotime(date("Y-m-d"),time()));
            $month_begin = date('Y-m-d', strtotime("$month_end -1 month"));
            $this->assign("start_date", $month_begin);
            $this->assign("end_date", $month_end);
            return view($this->style . 'Statistics/businessProfile');
        }
    }
    /*
   * 获取订单数量
   */
    public function getOrderAccount(){
        $order= new Order();
        $start_date = ! empty($_POST['start_date']) ? $_POST['start_date'] : '';
        $end_date = ! empty($_POST['end_date']) ?  $_POST['end_date'] : '';
        $shop_type = ! empty($_POST['shop_type']) ? $_POST['shop_type'] : 0;
        $begintime = strtotime($start_date);
        $endtime = strtotime($end_date)+86399;
        if ($begintime != "") {
            $condition["create_time"][] = [
                ">",
                $begintime
            ];
        }
        if ($endtime != "") {
            $condition["create_time"][] = [
                "<",
                $endtime
            ];
        }
        if ($shop_type == 1) {
            $condition["shop_id"] = 0;
        } elseif ($shop_type == 2) {
            $condition["shop_id"] = ['>', 0];
        }
        $condition["website_id"] = $this->website_id;
        $condition['is_deleted'] = 0;
        //订单量
        $sale_num = $order->getShopSaleNumSum($condition);
        //付款订单
        $condition1=$condition;
        $condition1['order_status'] = [['>',0],['<',5]];
        $order_pay = $order->getOrderCount($condition1);
        //销售额
        $sale_money = $order->getShopSaleSum($condition1);
        //售后订单
        $condition2=$condition;
        $condition2['order_status'] = ['<',0];
        $order_return = $order->getOrderCount($condition2);
        $result = array(
            "sale_money"=>$sale_money,
            "sale_num"=>$sale_num,
            "order_return"=>$order_return,
            "order_pay"=>$order_pay
        );
        return $result;
    }
    /*
     * 商品分析
     */
    public function goodsAnalysis(){
        if (request()->isAjax()) {
            $pageindex = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $start_date = ! empty($_POST['start_date']) ? $_POST['start_date'] : '';
            $end_date = ! empty($_POST['end_date']) ? $_POST['end_date'] : '';
            $shop_type = ! empty($_POST['shop_type']) ? $_POST['shop_type'] : 0;
            $sort = ! empty($_POST['sort']) ? $_POST['sort'] : 0;
            $goods_name = ! empty($_POST['goods_name']) ? $_POST['goods_name'] : '';
            $condition = array();
            $shop_id=-1;
            if ($shop_type==1) {
                $condition["no.shop_id"] = 0;
                $shop_id=0;
            }elseif($shop_type==2){
                $condition["no.shop_id"] = ['>',0];
                $shop_id=['>',0];
            }
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
            if($goods_name){
                $condition["nog.goods_name"] = ["like","%" . $goods_name . "%"];
            }
            if($sort==1){
                $order = 'sum(nog.num) desc';
            }elseif($sort==2){
                // $order = 'sum(nog.real_money*nog.num) desc';
                $order = 'sumMoney desc';
            }
            $condition["no.website_id"] = $this->website_id;
            $condition["no.is_deleted"] = 0;
            $condition["no.order_status"] = [['>','0'],['<','5']]; 
            $orderGoods = new VslOrderGoodsViewModel();
            $list = $orderGoods->getOrderGoodsRankList($pageindex, PAGESIZE, $condition, $order);
            $list['account'] = ['1','2','4'];
            $money=0.00;
            $count=0;
            $goods = new Goods();
            if($shop_id==0 || $shop_id!=-1){
                $goodsCount=$goods->getGoodsCount(['website_id'=>$this->website_id,'shop_id'=>$shop_id]);
            }
            if($shop_id==-1){
                $goodsCount=$goods->getGoodsCount(['website_id'=>$this->website_id]);
            }
            foreach($list['data']['data'] as $v){
                $money += $v['sumMoney'];
                $count +=$v['sumCount'];
            }
            $list['account'] = [$money,$count,$goodsCount];
            return $list;
        } else {
            $shop_id = isset($_GET["shop_id"]) ? $_GET["shop_id"] : 0;
            $this->assign("shop_id", $shop_id);
            return view($this->style . "Statistics/goodsAnalysis");
        }
    }
    
    /*
     * 订单分布
     */
    public function orderDistribution(){
        if (request()->isAjax()) {
            $data = array();
            $start_date = ! empty($_POST['start_date']) ? $_POST['start_date'] : '';
            $end_date = ! empty($_POST['end_date']) ? $_POST['end_date'] : '';
            $shop_type = ! empty($_POST['shop_type']) ? $_POST['shop_type'] : 0;
            $province = ! empty($_POST['province']) ? $_POST['province'] : '';
            $city = ! empty($_POST['city']) ? $_POST['city'] : '';
            $condition = array();
            $condition1 = array();
            $condition2 = array();
            if ($shop_type==1) {
                $condition["no.shop_id"] = 0;
                $condition1['shop_id'] = 0;
                $condition2['shop_id'] = 0;
            }elseif($shop_type==2){
                $condition["no.shop_id"] = ['>',0];
                $condition1['shop_id'] = ['>',0];
                $condition2['shop_id'] = ['>',0];
            }
            if ($start_date != "") {
                $condition["no.create_time"][] = [
                    ">",
                    strtotime($start_date)
                ];
                $condition1["create_time"][] = [
                    ">",
                    strtotime($start_date)
                ];
                $condition2["create_time"][] = [
                    ">",
                    strtotime($start_date)
                ];
            }
            if ($end_date != "") {
                $condition["no.create_time"][] = [
                    "<",
                    strtotime($end_date)
                ];
                $condition1["create_time"][] = [
                    "<",
                    strtotime($end_date)
                ];
                $condition2["create_time"][] = [
                    "<",
                    strtotime($end_date)
                ];
            }
            $group = 'no.receiver_province';
            $field = 'sp.province_name as area,count(no.order_id) as count,sc.city_id,sp.province_id';
            $condition['no.receiver_province'] = ['>',0];
            $condition1['receiver_province'] = ['>',0];
            $condition['no.receiver_city'] = ['>',0];
            $condition1['receiver_city'] = ['>',0];
            if($province>0){
                $condition['no.receiver_province'] = $province;
                $condition1['receiver_province'] = $province;
                $group = 'no.receiver_city';
                $field = 'sc.city_name as area,count(no.order_id) as count,sc.city_id,sp.province_id';
            }
            if($city>0){
                $condition['no.receiver_city'] = $city;
                $condition1['receiver_city'] = $city;
                $group = 'no.receiver_district';
                $field = 'sd.district_name as area,count(no.order_id) as count,sc.city_id,sp.province_id';
            }
            $condition["no.website_id"] = $this->website_id;
            $condition["no.is_deleted"] = 0;
            $condition["no.order_status"] = ['neq',5];
            $condition1["website_id"] = $this->website_id;
            $condition1["is_deleted"] = 0;
            $condition1["order_status"] = ['neq',5];
            $condition2["website_id"] = $this->website_id;
            $condition2["is_deleted"] = 0;
            $condition2["order_status"] = ['neq',5];
            $order_goods = new VslOrderGoodsModel();
            $order_goods_id = $order_goods->Query(['website_id'=>$this->website_id,'goods_exchange_type'=>['neq',0]],'order_id');
            $order = new VslOrderModel();
            if($order_goods_id){
                $order_ids = $order->Query(['website_id'=>$this->website_id,'is_deleted'=>0,'order_status'=>['neq',5]],'order_id');
                $order_id = array_intersect($order_ids,$order_goods_id);
                if($order_id){
                    $order_real_id = implode(',',$order_id);
                    $condition["no.order_id"] = ['not in',$order_real_id];
                    $condition1["order_id"] = ['not in',$order_real_id];
                    $condition2["order_id"] = ['not in',$order_real_id];
                }
            }
            $list['area_info'] = $order->getOrderDistributionList(1, 0, $condition,'',$group,$field);
            $order_service = new Order();
            //订单来源
            $condition1['order_from'] = 1;// 微信
            $data['order_from1'] = $order_service->getOrderCount($condition1);
            $data['order_from1_member'] = $order_service->getMemberOrderCount($condition1);
            $data['order_from1_money'] = $order_service->getOrderMoneySum($condition1,'order_money');
            $condition1['order_from'] = 2;// 手机
            $data['order_from2'] = $order_service->getOrderCount($condition1);
            $data['order_from2_member'] = $order_service->getMemberOrderCount($condition1);
            $data['order_from2_money'] = $order_service->getOrderMoneySum($condition1,'order_money');
            $condition1['order_from'] = 3;//pc
            $data['order_from3'] = $order_service->getOrderCount($condition1);
            $data['order_from3_member'] = $order_service->getMemberOrderCount($condition1);
            $data['order_from3_money'] = $order_service->getOrderMoneySum($condition1,'order_money');
            $condition1['order_from'] = 4;// ios
            $data['order_from4'] = $order_service->getOrderCount($condition1);
            $data['order_from4_member'] = $order_service->getMemberOrderCount($condition1);
            $data['order_from4_money'] = $order_service->getOrderMoneySum($condition1,'order_money');
            $condition1['order_from'] = 5;// // Android
            $data['order_from5'] = $order_service->getOrderCount($condition1)+$data['order_from4'];
            $data['order_from5_member'] = $order_service->getMemberOrderCount($condition1)+$data['order_from4_member'];
            $data['order_from5_money'] = $order_service->getOrderMoneySum($condition1,'order_money')+$data['order_from4_money'];
            $condition1['order_from'] = 6;// // 小程序
            $data['order_from6'] = $order_service->getOrderCount($condition1);
            $data['order_from6_member'] = $order_service->getMemberOrderCount($condition1);
            $data['order_from6_money'] = $order_service->getOrderMoneySum($condition1,'order_money');
            if($list['area_info']['data']){
                foreach($list['area_info']['data'] as $val){
                    $data['area_info']['areas'][] = $val['area'];
                    if($city>0){
                        $condition2['receiver_city'] = $val['city_id'];
                    }else{
                        $condition2['receiver_province'] = $val['province_id'];
                    }
                    $data['area_info']['order_from_member'][] = $order_service->getMemberOrderCount($condition2);
                    $data['area_info']['order_from_money'][]= $order_service->getOrderMoneySum($condition2,'order_money');
                    $data['area_info']['counts'][] = $val['count'];
                }
                unset($val);
            }
            return $data;
        } else {
            return view($this->style . "Statistics/orderAnalysis");
        }
    }
    /*
     * 订单分布概况
     */
    public function orderProfile(){
        if (request()->isAjax()) {
            $month_end = date('Y-m-d', strtotime(date("Y-m-d"),time()));
            $month_begin = date('Y-m-d', strtotime("$month_end -1 month"));
            $start_date = ! empty($_POST['start_date']) ? $_POST['start_date'] : $month_begin;
            $end_date = ! empty($_POST['end_date']) ?  $_POST['end_date'] : $month_end;
            $shop_type = ! empty($_POST['shop_type']) ? $_POST['shop_type'] : 0;
            $province = ! empty($_POST['province']) ? $_POST['province'] : '';
            $city = ! empty($_POST['city']) ? $_POST['city'] : '';
            if($province>0){
                $condition['receiver_province'] = $province;
            }
            if($city>0){
                $condition['receiver_city'] = $city;
            }
            $begintime = strtotime($start_date);
            $endtime = strtotime($end_date)+24*3600;
            $between=5;
            if($endtime>$begintime){
                $between = ceil(($endtime-$begintime)/7/24/3600);
            }
            if ($begintime != "") {
                $condition["create_time"][] = [
                    ">",
                    $begintime
                ];
            }
            if ($endtime != "") {
                $condition["create_time"][] = [
                    "<",
                    $endtime
                ];
            }
            if ($shop_type == 1) {
                $condition["shop_id"] = 0;
            } elseif ($shop_type == 2) {
                $condition["shop_id"] = ['>', 0];
            }
            $condition["website_id"] = $this->website_id;
            $condition['is_deleted'] = 0;
            $condition["order_status"] = ['neq',5];
            $shop = new Order();
            $list = $shop->getBusinessProfileList($begintime,$endtime,$condition);
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
}
