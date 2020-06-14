<?php

namespace app\admin\controller;

use data\model\VslOrderGoodsViewModel;
use data\service\Address;
use data\service\Goods;
use data\service\Order;
use data\model\VslOrderModel;
/**
 * 账户控制器
 */
class Statistics extends BaseController {

    /**
     * 商品销售详情
     *
     * @return Ambigous <multitype:number , multitype:number unknown >
     */
    public function goodsAnalysis() {
        if (request()->isAjax()) {
            $order = new VslOrderGoodsViewModel();
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $goods_name = request()->post("goods_name", '');
            $sort = request()->post('sort', '');
            $start_date = request()->post('start_date', '');
            $end_date = request()->post('end_date', '');
            $condition = array();
            if (!empty($start_date)) {
                $condition['no.create_time'][] = [
                    '>',
                    strtotime($start_date)
                ];
                unset($start_date);
            }
            if (!empty($end_date)) {
                $condition['no.create_time'][] = [
                    '<',
                    strtotime($end_date)
                ];
                unset($end_date);
            }


            if ($goods_name != '') {
                $condition["no.order_status"] = array(
                    [
                        'NEQ',
                        0
                    ],
                    [
                        'NEQ',
                        5
                    ]
                );
                $condition["nog.goods_name"] = array(
                    'like',
                    '%' . $goods_name . '%'
                );
            }
            if ($sort == 'num') {
                $query_order = 'sum(nog.num) DESC';
            } elseif ($sort == 'sales') {
                $query_order = 'sum(nog.goods_money) DESC';
            } else {
                $query_order = 'no.create_time DESC';
            }
            $condition["no.shop_id"] = $this->instance_id;
            $list = $order->getOrderGoodsRankList($page_index, $page_size, $condition, $query_order);
            $money = 0.00;
            $count = 0;
            $goods = new Goods();
            $goodsCount = $goods->getGoodsCount(['website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
            foreach ($list['data']['data'] as $v) {
                $v = objToArr($v);
                $money += $v['sumMoney'];
                $count += $v['sumCount'];
            }
            $list['account'] = [$money, $count, $goodsCount];
            return $list;
        } else {
            return view($this->style . "Statistics/goodsAnalysis");
        }
    }

    /*
     * 订单分析
     */

    public function ordersAnalysis() {
        $orderModel = new Order();


            //默认前30天的数据量
            $end_date = date('Y-m-d',time()+24*3600);
            $start_date = date('Y-m-d', time() - 30 * 86400);

            $areas = ['province' => [0 => '全部'], 'city' => [0 => '全部']];
//            //只获取订单出现的地址
//            $condition['no.shop_id'] = $this->instance_id;
//            $fields = 'sp.province_id,sp.province_name,sc.city_id,sc.city_name';
//            $area_info = $orderModel->getOrderAreaCount(1, 0, $condition, '', $fields, 'no.receiver_city');
//            //构建province_id=>province_name,city_id=>city_name的数组
//            foreach ($area_info['data'] as $area) {
//                if (!in_array($area['province_name'], $areas['province']) && !empty($area['province_id']) && !empty($area['province_name'])) {
//                    $areas['province'][$area['province_id']] = $area['province_name'];
//                    $areas['city'][$area['province_id']][0] = '全部';
//                }
//                if (!in_array($area['city_name'], $areas['city']) && !empty($area['province_id']) && !empty($area['city_id']) && !empty($area['city_name'])) {
//                    $areas['city'][$area['province_id']][$area['city_id']] = $area['city_name'];
//                }
//            }
            //获取全部地址信息
            $data_address = new Address();
            $fields = ['sp.province_id', 'sp.province_name', 'sc.city_id', 'sc.city_name'];
            $area_info = $data_address->getAllAddress([], $fields, 'sc.city_id');
            //构建id=>name的数组
            foreach ($area_info as $area) {
                if (!in_array($area['province_name'], $areas['province']) && !empty($area['province_id']) && !empty($area['province_name'])) {
                    $areas['province'][$area['province_id']] = $area['province_name'];
                    $areas['city'][$area['province_id']][0] = '全部';
                }
                if (!in_array($area['city_name'], $areas['city']) && !empty($area['province_id']) && !empty($area['city_id']) && !empty($area['city_name'])) {
                    $areas['city'][$area['province_id']][$area['city_id']] = $area['city_name'];
                }
            }

            $this->assign('start_date', $start_date);
            $this->assign('end_date', $end_date);
            $this->assign('areas', json_encode($areas, JSON_UNESCAPED_UNICODE));
            return view($this->style . 'Statistics/ordersAnalysis');
    }
    /*
      * 订单分布
      */
    public function orderDistribution(){
        if (request()->isAjax()) {
            $data = array();
            $start_date = ! empty($_POST['start_date']) ? $_POST['start_date'] : '';
            $end_date = ! empty($_POST['end_date']) ? $_POST['end_date'] : '';
            $province = ! empty($_POST['province_id']) ? $_POST['province_id'] : '';
            $city = ! empty($_POST['city_id']) ? $_POST['city_id'] : '';
            $condition = array();
            $condition1 = array();
            $condition2 = array();
            $condition["no.shop_id"] = $this->instance_id;
            $condition1['shop_id'] = $this->instance_id;
            $condition2['shop_id'] = $this->instance_id;
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
            $condition1["website_id"] = $this->website_id;
            $condition1["is_deleted"] = 0;
            $condition2["website_id"] = $this->website_id;
            $condition2["is_deleted"] = 0;
            $order = new VslOrderModel();
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
            $data['order_from4'] = $order_service->getOrderCount($condition1) ;
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
            $province = ! empty($_POST['province']) ? $_POST['province'] : '';
            $city = ! empty($_POST['city']) ? $_POST['city'] : '';
            if($province>0){
                $condition['receiver_province'] = $province;
            }
            if($city>0){
                $condition['receiver_city'] = $city;
            }
            $begintime = strtotime($start_date);
            $endtime = strtotime($end_date);
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
            $condition["shop_id"] = $this->instance_id;
            $condition["website_id"] = $this->website_id;
            $condition['is_deleted'] = 0;
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
