<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/25 0025
 * Time: 11:30
 */

namespace addons\seckill\server;

use addons\seckill\model\VslSeckGoodsModel;
use addons\seckill\model\VslSeckillModel;
use addons\seckill\model\VslSeckillGoodsdelInfoModel;
use addons\groupshopping\model\VslGroupGoodsModel;
use addons\shop\model\VslShopModel;
use data\model\VslGoodsModel;
use data\model\VslOrderGoodsPromotionDetailsModel;
use data\service\BaseService;
use data\service\Goods;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
class Seckill extends BaseService
{
    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }
    /**
     * @param array $input
     * @return int
     */
    public function addSecKill(array $input, $check_method)
    {
        $seckill_obj = new VslSeckillModel();
        $seckill_obj->startTrans();
        try {
            //先查询该天该场次是否已经存在，如果存在则直接用该场次id
            $condition = [
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
                'seckill_name' => $input['seckill_name'],
                'seckill_time' => strtotime($input['seckill_time']),
            ];
            if($this->instance_id != 0){
                if($check_method === 1){//审核方式1为店铺自动审核
                    $check_status = 1;
                }else{
                    $check_status = 0;
                }
            }else{
                $check_status = 1;
            }
            //处理当天的秒杀点组成的时间，用于区分正在进行、即将进行
            $str_date = $input['seckill_time'].' '.$input['seckill_name'].':00:00';
            $seckill_now_time = strtotime($str_date);
            $is_seck_res = $seckill_obj->where($condition)->find();
            if(!$is_seck_res){
                $data = array(
                    'shop_id' => $this->instance_id,
                    'website_id' => $this->website_id,
                    'seckill_name' => $input['seckill_name'],
                    'seckill_time' => strtotime($input['seckill_time']),
                    'seckill_now_time' => $seckill_now_time,
                    'create_time'  => time()
                );
                $bool = $seckill_obj->save($data);
                $seckill_id = $seckill_obj->seckill_id;
            }else{
                $seckill_id = $is_seck_res->seckill_id;
            }

            //处理秒杀对应的商品
            $sec_goods_obj = new VslSeckGoodsModel();
            $goods_mdl = new VslGoodsModel();
            $i = 0;
            foreach($input['goods_info'] as $sku_id=>$goods){
                //如果sku_id 等于0 则说明其没有规格sku
                $sec_goods[$i]['seckill_id'] = $seckill_id;
                $sec_goods[$i]['sku_id'] = $sku_id;
                $sec_goods[$i]['goods_id'] = $input['goods_id'];
                $sec_goods[$i]['seckill_num'] = $goods['seckill_num']+$goods['seckill_vrit_num'];
                $sec_goods[$i]['remain_num'] = $goods['seckill_num'];
                $sec_goods[$i]['seckill_price'] = $goods['seckill_price'];
                $sec_goods[$i]['seckill_limit_buy'] = $goods['seckill_limit_buy'];
                $sec_goods[$i]['seckill_vrit_num'] = $goods['seckill_vrit_num'];
                $sec_goods[$i]['check_status'] = $check_status;
                $sec_goods[$i]['del_status'] = 1;
                $sec_goods[$i]['create_time'] = time();
                $i++;
                //将商品更改促销状态 1为秒杀
                $goods_mdl->where(['goods_id'=>$input['goods_id'],'website_id'=>$this->website_id])->update(['promotion_type'=>1]);
            }
            $sec_goods_obj->saveAll($sec_goods);
            $seckill_obj->commit();
            return $seckill_id;
        } catch (\Exception $e) {
            $seckill_obj->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 获取秒杀列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $fields
     *
     * @return array $coupon_type_list
     */
    public function seckillAllList($page_index = 1, $page_size = 0, array $condition = [], $fields = '*')
    {
        $seckill_mdl = new VslSeckillModel();
        //今天进行中的已审核商品 seckill_now_time +24*3600 > time() sekcill_now_time <= time()
        $condition1 = [
            'ns.website_id' => $condition['website_id'],
//            'ns.shop_id' => $condition['shop_id'],
//            'ns.seckill_time'=> strtotime(date('Y-m-d'))-24*3600,
            'ns.seckill_now_time' => [
                [
                    '>',time()-24*3600
                ],
                [
                    '<=',time()
                ]
            ],
            'nsg.check_status' => 1,
            'nsg.del_status' => 1,
        ];
        $today_seckill_arr = $seckill_mdl->getSeckillGoodsCount($condition1);
        //明天秒杀的商品
        $condition2 = [
            'ns.website_id' => $condition['website_id'],
//            'ns.shop_id' => $condition['shop_id'],
            'ns.seckill_time'=>strtotime(date('Y-m-d',strtotime('+1 day'))),
            'nsg.check_status' => 1,
            'nsg.del_status' => 1,
        ];
        $tomorrow_seckill_arr = $seckill_mdl->getSeckillGoodsCount($condition2);
        //未审核商品 time<seckill_now_time
        $condition3 = [
            'ns.website_id' => $condition['website_id'],
            'ns.seckill_now_time' => ['>=', time()],
            'nsg.check_status'=>0,
            'nsg.del_status' => 1,
        ];
        $uncheck_seckill_arr = $seckill_mdl->getSeckillGoodsCount($condition3);
        //将所有的场次查出来
        $condition4 = [
            'website_id' => $condition['website_id'],
            'addons' => 'seckill'
        ];
        $add_conf_mdl = new AddonsConfigModel();
        $all_seckill_list = $add_conf_mdl->field('value')->where($condition4)->find();
        $all_seckill_arr = objToArr($all_seckill_list);
        $value = $all_seckill_arr['value'];
        $value_arr = json_decode($value, true);
        $sk_quantum_str = $value_arr['sk_quantum_str'];
        $sk_quantum_arr = explode(',', $sk_quantum_str);
//        echo '<pre>';print_r($sk_quantum_arr);exit;
        //将三个场次的统计值归为一个数组
        $total_goods = [];
        foreach($sk_quantum_arr as $k=>$v){
            foreach($today_seckill_arr as $k1=>$v1){
                if($v == $v1['seckill_name']){
                    $total_goods[$v]['seckill_name'] =  $v1['seckill_name'];
                    $total_goods[$v]['today_total'] =  $v1['g_total'];
                    $total_goods[$v]['seckill_id'] =  $v1['seckill_id'];
                }
            }
            foreach($tomorrow_seckill_arr as $k2=>$v2){
                if($v == $v2['seckill_name']){
                    $total_goods[$v]['seckill_name'] =  $v2['seckill_name'];
                    $total_goods[$v]['tomorrow_total'] =  $v2['g_total'];
                    $total_goods[$v]['seckill_id'] =  $v2['seckill_id'];
                }
            }
            foreach($uncheck_seckill_arr as $k3=>$v3){
                if($v == $v3['seckill_name']){
                    $total_goods[$v]['seckill_name'] =  $v3['seckill_name'];
                    $total_goods[$v]['uncheck_total'] =  $v3['g_total'];
                    $total_goods[$v]['seckill_id'] =  $v3['seckill_id'];
                }
            }
        }
        //某个活动的总商品数是通过goods_id去重得到的，如果一个活动对应商品数为0，那么去重得到的结果为空，或者这个场次既没有活动也没有商品，那么这个场次对应的所有状态商品应该为0
        foreach($total_goods as $k4=>$v4){
            $seckill_name_arr[] = $v4['seckill_name'];
        }
        $seckill_name_arr = $seckill_name_arr?:[];
        $diff_seckill_arr = array_diff($sk_quantum_arr,$seckill_name_arr);
        if($diff_seckill_arr){
            foreach($diff_seckill_arr as $k5=>$v5){
                $total_goods[$v5]['seckill_name'] = $v5;
                $total_goods[$v5]['uncheck_total'] =  0;
                $total_goods[$v5]['seckill_id'] =  0;
            }
        }
        $total_goods = array_values($total_goods);
        $page_total_goods = [];
        if( !empty($page_index) && !empty($page_size) ){
            $offset_start = ($page_index-1)*$page_size;
            $offset_end = $page_index*$page_size;
            for($i=$offset_start;$i<$offset_end;$i++){
                $page_total_goods[$i] = $total_goods[$i];
            }
        }
        $page_total_goods = array_filter($page_total_goods);
        $count = count($total_goods);
        $page_count = ceil($count/$page_size);
        return array(
            'data' => $page_total_goods,
            'total_count' => $count,
            'page_count' => $page_count
        );
    }
    /*
     * 获取即将要进行的seckill_id
     * **/
    public function getSeckillId($condition){
        $seckill_mdl = new VslSeckillModel();
        $condition['sg.check_status'] = 1;
        $condition['sg.del_status'] = 1;
        //正在进行或者未开始 'seckill_now_time'+24*3600 > time();
        $seckill_list = $seckill_mdl->alias('s')->where($condition)->join('vsl_seckill_goods sg','s.seckill_id = sg.seckill_id')->order('s.seckill_now_time asc')->find();
        return $seckill_list['seckill_id'];
    }
    /*
     * 获取某日的某个时间段商品列表
     * **/
    public function getSeckillGoodsList($page_index=1, $page_size, $condition, $order_by)
    {
        $seckill_mdl = new VslSeckillModel();
        $goods_service = new Goods();
        $today_seckill_goods_list = $seckill_mdl->getSeckillConGoodsList($page_index, $page_size, $condition, $order_by);
        $total_count = $seckill_mdl->getSeckillConGoodsTotal($condition);
        //处理图片
        foreach($today_seckill_goods_list as $k=>$v){
            $v->pic_cover_big = getApiSrc($v->pic_cover_big);
            //判断商品是否属于平台
            $goods_type = $goods_service->getGoodsType($v->goods_id);
            //处理seckill_time
            $v->seckill_date = date('Y-m-d',$v->seckill_time);
            $v->goods_type = $goods_type;
            if($v->goods_id === null){
                unset($today_seckill_goods_list[$k]);
            }
        }
        //页数
        $page_count = ceil($total_count/$page_size);
        return  [
                'data' => $today_seckill_goods_list,
                'total_count' => $total_count,
                'page_count' => $page_count,
            ];
    }
    /*
     * 得到wap端的商品信息列表
     * **/
    public function getWapSeckillGoodsList($condition, $field="*")
    {
        $seckill_mdl = new VslSeckillModel();
        $getWapSeckillGoodsList = $seckill_mdl->getWapSeckillGoodsList($condition, $field);
        return $getWapSeckillGoodsList;
    }
    /*
     * 获取秒杀配置起始后的7天时间字符串
     * **/
    public function getSeckillCheckTime(){

        /*改动从今天开始了，注释掉起始时间
         * $start_days = (int)$start_days;*/
        $start_days = 0;
        //获取加上开始天数的7天的日期
//        $start_date = date('Y-m-d', strtotime('+'.$start_days.'day'));
//        var_dump($start_date);exit;
        for($i=$start_days; $i<$start_days+7; $i++){
            $start_date_arr[] = date('Y-m-d', strtotime('+'.$i.'day'));
        }
        return $start_date_arr;
    }
    /*
     * 得到可报名时间区间
     * **/
    public function getCanApplyDate()
    {
        $website_id = $this->website_id;
        $addons_conf_mdl = new AddonsConfigModel();
        $condition = [
            'website_id' => $website_id,
            'addons' => 'seckill'
        ];
        $value_list = $addons_conf_mdl->field('value')->where($condition)->find();
        $value_arr = json_decode($value_list->value, true);
        $can_apply_date = $value_arr['can_apply_date'];
        $can_apply_date_arr = explode('-', $can_apply_date);
        return $can_apply_date_arr;
    }
    /*
     * 统计每个日期下的商品数量
     * [param] array $date_arr 日期数组
     * **/
    public function getDateGoodsCount( array $date_arr, $check_status, $seckill_name){
        $seckill_mdl = new VslSeckillModel();
        $website_id = $this->website_id;
        $instance_id = $this->instance_id;
        $condition = [
            'ns.website_id' => $website_id,
//            'ns.shop_id' => $instance_id,
            'ns.seckill_name' => $seckill_name,
            'nsg.check_status' =>  $check_status,
            'nsg.del_status' => 1,
            'ns.seckill_now_time'=> ['>=',time()],
            ];
        //加上更多
        $date_arr[] = '更多';
        foreach($date_arr as $k=>$date ){
            $seckill_time = strtotime($date);

            if($date == '更多'){
                //获取倒数第二个值
                $end_date = $date_arr[count($date_arr)-2];
                $end_time = strtotime($end_date);
                $condition['ns.seckill_time'] = ['>', $end_time];
                $date_goods_count = $seckill_mdl->dateGoodsCount($condition);
            }else{
                $condition['ns.seckill_time'] = $seckill_time;
            }
            $date_goods_count = $seckill_mdl->dateGoodsCount($condition);
            $date_arr_count[$date]['date'] = $date;
            $date_arr_count[$date]['count'] = $date_goods_count[0]['goods_count'];
        }
        return $date_arr_count;
    }
    /*
     * 获取活动对应商品的sku详情
     * **/
    public function getGoodsDetail(array $condition = [], $field)
    {
        $seckill_goods_sku_arr = $this->getGoodsSkuArr($condition, $field);
        $sku_detail_arr = $this->action_goods_sku($seckill_goods_sku_arr);
        return json_encode($sku_detail_arr);
    }
    public function getGoodsSkuArr($condition, $field = '*')
    {
        $seckill_mdl = new VslSeckillModel();
        $seckill_goods_sku_list = $seckill_mdl->getSeckillSkuInfo($condition, $field);
//        echo $seckill_mdl->getLastSql();exit;
        $seckill_goods_sku_arr = objToArr($seckill_goods_sku_list);
        return $seckill_goods_sku_arr;
    }
    /*
     * 处理sku
     * **/
    public function action_goods_sku(array $sku_arr)
    {
        $goods = new VslGoodsModel();
        $goods_spec_format = $goods->getInfo(['goods_id' => $sku_arr[0]['goods_id']], 'goods_spec_format')['goods_spec_format'];
        $goods_spec_arr = json_decode($goods_spec_format, true);
        $new_sku_arr = [];
        foreach($sku_arr as $sku_key=>$sku_value){
            $sku_val_item = $sku_value['attr_value_items'];
            if(!$sku_val_item){
                continue;
            }
            $sku_val_arr = explode(';',$sku_val_item);
            $th_name_str = '';
            $show_type_str = '';
            $show_value_str = '';
            foreach($sku_val_arr as $sku_val_key=>$sku_val_value){
                $sku_val_value_arr = explode(':',$sku_val_value);
                //按照规格规则中的顺序定义tr头 删掉规格后会导致商品报错不显示规格，所以直接取商品表的goods_spec_format
                /*$sku_tr_id = $sku_val_value_arr[1];
                $val_type = $goods->getGoodSku(['spec_value_id'=>$sku_tr_id]);
                $val_type_arr = $val_type[0]->toArray();*/
                $val_type_arr = [];
                foreach ($goods_spec_arr as $k0 => $v0) {
                    foreach ($v0['value'] as $k01 => $v01) {
                        if($sku_val_value_arr[1] == $v01['spec_value_id']){
                            $val_type_arr['goods_spec']['show_type'] = $v01['spec_show_type'];
                            $val_type_arr['goods_spec']['spec_name'] = $v01['spec_name'];
                            $val_type_arr['spec_value_name'] = $v01['spec_value_name'];
                        }
                    }
                }
//                var_dump($val_type_arr);
                $show_type = $val_type_arr['goods_spec']['show_type'];
                //根据show_type，获取规格的值，如图片的路径
                if($show_type == '3'){//图片
//                    $pic_id = $val_type_arr['spec_value_data'];
//                    $val_type_str = $goods->getGoodSkuPic(['pic_id'=>$pic_id]);
//                    if(!$val_type_str){
//                        $val_type_str = '无图片';
//                    }
                    $val_type_str = $val_type_arr['spec_value_name'];
                }else if($show_type == '2'){//颜色
                    $val_type_str = $val_type_arr['spec_value_name'];
                }else{
                    $val_type_str = $val_type_arr['spec_value_name'];
                }
                //拼接所有规格展示类型对应的值
                $show_value_str .= $val_type_str.'§';
                //拼接th的名字
                $th_name_str .= $val_type_arr['goods_spec']['spec_name'].' ';
                //拼接展示类型
                $show_type_str .= $show_type.' ';
            }
            $th_name_str = trim($th_name_str);
            $show_type_str = trim($show_type_str);
            $show_value_str = trim($show_value_str, '§');
//            var_dump($show_value_str);

            $sku_id_str_arr = explode(';',$sku_val_item);
            $sku_value_str = trim($show_value_str);
            $sku_value_str_arr = explode('§',$sku_value_str);
            $im_str = '';
            $new_im_str = '';
            for($i=0;$i<count($sku_value_str_arr);$i++){
                $im_str .= $sku_id_str_arr[$i].';';
                $im_str = trim($im_str,';');
                $new_im_str .= $im_str.'='.$sku_value_str_arr[$i].'§';
            }
            $new_im_str = trim($new_im_str,'§');
            $new_sku_arr['goods_id'] = $sku_value['goods_id'];
            $new_sku_arr['goods_name'] = $sku_value['goods_name'];
            $new_sku_arr['sku_list'][$sku_key]['th_name_str'] = $th_name_str;
            $new_sku_arr['sku_list'][$sku_key]['show_type_str'] = $show_type_str;
            $new_sku_arr['sku_list'][$sku_key]['new_im_str'] = $new_im_str;
            $new_sku_arr['sku_list'][$sku_key]['attr_value_items'] = $sku_value['attr_value_items'];
            $new_sku_arr['sku_list'][$sku_key]['seckill_num'] = $sku_value['seckill_num'];
            $new_sku_arr['sku_list'][$sku_key]['remain_num'] = $sku_value['remain_num'];
            $new_sku_arr['sku_list'][$sku_key]['seckill_price'] = $sku_value['seckill_price'];
            $new_sku_arr['sku_list'][$sku_key]['seckill_limit_buy'] = $sku_value['seckill_limit_buy'];
        }
        foreach($new_sku_arr['sku_list'] as $k=>$v){
            $new_im_key_arr = explode('§', $v['new_im_str']);
            $new_im_key1 = $new_im_key_arr[0];
            $new_im_key2 = $new_im_key_arr[1];
            $temp_im_arr[$new_im_key1][$new_im_key2] = $v;
        }
        foreach($temp_im_arr as $k1=>$v1){
            foreach($v1 as $k2=>$v2){
                $new_act_im_arr[] = $v2;
            }
        }
        unset($new_sku_arr['sku_list']);
        $new_sku_arr['sku_list'] = $new_act_im_arr;
        if(!$new_sku_arr){
            return $sku_arr;
        }
        return $new_sku_arr;
    }
    /*
     * 删除秒杀活动下的某个商品
     * **/
    public function delSeckillGoods($condition)
    {
        $sec_goods_mdl = new VslSeckGoodsModel();
        $res['del_status'] = 0;
        $bool = $sec_goods_mdl->where($condition)->update($res);
        return $bool;
    }
    /*
     * 判断是否有该商品其它的未过期档
     * **/
    public function isOtherSeckillExists($cond)
    {
        $seckill_mdl = new VslSeckillModel();
        $is_seckill = $seckill_mdl->alias('s')->join('vsl_seckill_goods sg', 's.seckill_id = sg.seckill_id', 'left')->where($cond)->find();
        echo $seckill_mdl->getLastSql();
        p($is_seckill);exit;
        return $is_seckill;
    }
    /*
     * 秒杀商品通过审核
     * **/
    public function seckillGoodsChecked($condition)
    {
        $sec_goods_mdl = new VslSeckGoodsModel();
        $res['check_status'] = 1;
        $bool = $sec_goods_mdl->where($condition)->update($res);
        return $bool;
    }
    /*
     * 如果商品存在并且删除状态为0（移除、拒绝），若再添加回来，则只需要修改del_status
     * **/
    public function updateSeckillGoodsDelStatus($seckill_id, $check_method, array $input)
    {
        //1为不删除，0为删除
        $res['del_status'] = 1;
        if($this->instance_id != 0){
            if($check_method === 1){//审核方式1为店铺自动审核
                $res['check_status'] = 1;
            }else{
                $res['check_status'] = 0;
            }
        }else{
            $res['check_status'] = 1;
        }
        //处理秒杀对应的商品
        $sec_obj = new VslSeckillModel();
        $sec_goods_obj = new VslSeckGoodsModel();
        $goods_obj = new VslGoodsModel();
        $status = true;
        $sec_data['seckill_name'] = $input['seckill_name'];
        $sec_data['seckill_time'] = strtotime($input['seckill_time']);
        $sec_data['seckill_now_time'] = strtotime($input['seckill_time'].' '.$input['seckill_name'].':00:00');
        $sec_condition['seckill_id'] = $seckill_id;
        $sec_obj->where($sec_condition)->update($sec_data);
        //将promotion_type加上
        $goods_data['promotion_type'] = 1;
        $goods_condition['goods_id'] = $input['goods_id'];
        $goods_obj->where($goods_condition)->update($goods_data);
        foreach($input['goods_info'] as $sku_id=>$goods){
            //如果sku_id 等于0 则说明其没有规格sku
            $condition['seckill_id'] = $seckill_id;
            $condition['sku_id'] = $sku_id;
            $condition['goods_id'] = $input['goods_id'];
            $sec_goods['seckill_num'] = $goods['seckill_num']+$goods['seckill_vrit_num'];
            $sec_goods['remain_num'] = $goods['seckill_num'];
            $sec_goods['seckill_price'] = $goods['seckill_price'];
            $sec_goods['seckill_limit_buy'] = $goods['seckill_limit_buy'];
            $sec_goods['seckill_vrit_num'] = $goods['seckill_vrit_num'];
            $sec_goods['check_status'] = $res['check_status'];
            $sec_goods['del_status'] = 1;
            $sec_goods['update_time'] = time();
            //将商品更改促销状态 1为秒杀
            $bool = $sec_goods_obj->where($condition)->update($sec_goods);
            if(!$bool){
                $status = false;
            }
        }
        return $status;
    }
    /*
     * 获取秒杀商品信息统计的数据
     * **/
    public function getSecGoodsCountInfo($condition, $order)
    {
        $sec_goods_mdl = new VslSeckillModel();
        $goods_count_list = $sec_goods_mdl->getSecGoodsCountInfo($condition, $order);
        return $goods_count_list;
    }
    /*
     * 根据商品id获取名称、图片、店铺
     * **/
    public function getEveryGoodsInfo($goods_id)
    {
        $goods_mdl = new VslGoodsModel();
        $condition = ['g.goods_id'=>$goods_id];
        $every_goods_info = $goods_mdl->alias('g')
            ->field('g.goods_name, sap.pic_cover_small, nsp.shop_name')
            ->join('sys_album_picture sap', 'g.picture=sap.pic_id', 'LEFT')
            ->join('vsl_shop nsp', 'g.shop_id=nsp.shop_id AND g.website_id=nsp.website_id', 'LEFT')
            ->where($condition)
            ->find();
        return $every_goods_info;
    }
    /*
     * 获取商户端的每个状态下的商品总数
     * **/
    public function getStatusGoodsCount($condition)
    {
        $seckill_mdl = new VslSeckillModel();
        $seckill_del_mdl = new VslSeckillGoodsdelInfoModel();
        foreach($condition as $k=>$v){
            $condition1[$k] = $v;
            $condition2[$k] = $v;
            $condition3[$k] = $v;
            $condition4[$k] = $v;
            $condition5[$k] = $v;
        }
        //当前时间
        $h = date('H');
        $today = date('Y-m-d');
        $seckill_now_time = strtotime($today.' '.$h.':00:00');
        //正在进行中
        //获取当前的时间点和今天日期
        $condition1['ns.seckill_now_time'] = [
            [
                '>',time()-24*3600
            ],
            [
                '<=',time()
            ]
        ];
        $condition1['nsg.check_status'] = 1;
        $condition1['nsg.del_status'] = 1;
        $going_total = $seckill_mdl->dateGoodsCount($condition1);
        //待开始
        //获取当前的时间点和今天日期
        $condition2['ns.seckill_now_time'] = ['>', time()];
        $condition2['nsg.check_status'] = 1;
        $condition2['nsg.del_status'] = 1;
        $unstart_total = $seckill_mdl->dateGoodsCount($condition2);

        //未审核
        //获取当前的时间点和今天日期
        $condition3['ns.seckill_now_time'] = ['>', time()];
        $condition3['nsg.check_status'] = 0;
        $condition3['nsg.del_status'] = 1;
        $uncheck_total = $seckill_mdl->dateGoodsCount($condition3);

        //已结束
        //获取当前的时间点和今天日期
        $condition4['ns.seckill_now_time'] = ['<', time() - 24*3600];
        $condition4['nsg.del_status'] = 1;
        $ended_total = $seckill_mdl->dateGoodsCount($condition4);

        //已拒绝
        $condition5['nsg.del_status'] = 0;
        $refused_total = $seckill_del_mdl->refused_goods_count($condition5);

        $status_goods_total_arr['going_total'] = $going_total[0]['goods_count'];
        $status_goods_total_arr['unstart_total'] = $unstart_total[0]['goods_count'];
        $status_goods_total_arr['uncheck_total'] = $uncheck_total[0]['goods_count'];
        $status_goods_total_arr['ended_total'] = $ended_total[0]['goods_count'];
        $status_goods_total_arr['refused_total'] = $refused_total;
        return $status_goods_total_arr;
    }
    /*
     * 获取已拒绝的商品列表
     * **/
    public function getStatusGoodsList($page_index=1, $page_size, $condition, $order_by){
        $seckill_del_mdl = new VslSeckillGoodsdelInfoModel();
        $refused_goods_list = $seckill_del_mdl->refused_goods_list($page_index, $page_size, $condition, $order_by);
        $refused_goods_count_arr = $seckill_del_mdl->refused_goods_count($condition);
        $refused_goods_count = $refused_goods_count_arr[0]['resused_count'];
        //总页数
        $page_count = ceil($refused_goods_count/$page_size);
        return  [
            'data' => $refused_goods_list,
            'total_count' => $refused_goods_count,
            'page_count' => $page_count
        ];
    }
    /*
     * 添加秒杀设置
     * **/
    public function saveSeckConfig($is_open,$value)
    {
        $ConfigService = new AddonsConfigService();
        $seckill_info = $ConfigService->getAddonsConfig("seckill");
        if (!empty($seckill_info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_open, 'value'=>$value, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'seckill'
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig($value, '秒杀设置', $is_open, 'seckill');
        }
        return $res;
    }
    /*
     * 从插件配置中获取店铺审核方式
     * **/
    public function getCheckMethod(){
        $value_arr = $this->getSeckConf();
        $check_method = $value_arr['check_method'];
        return $check_method;
    }
    /*
     * 获取秒杀设置中的每个时间段
     * **/
    public function getSeckTime()
    {
        $value_arr = $this->getSeckConf();
        $sk_quantum_str = $value_arr['sk_quantum_str'];
        return $sk_quantum_str;
    }
    /*
     * 获取addons_conf秒杀value
     * **/
    public function getSeckConf()
    {
        $website_id = $this->website_id;
        $addons_conf_mdl = new AddonsConfigModel();
        $condition = [
            'website_id' => $website_id,
            'addons' => 'seckill'
        ];
        $value_list = $addons_conf_mdl->field('value')->where($condition)->find();
        $value_arr = json_decode($value_list->value, true);
        return $value_arr;
    }
    /*
     * 获取哪天的、每个点的秒杀列表
     * **/
    public function getAllSeckGoods()
    {
        $sec_mdl = new VslSeckillModel();
        $sec_goods_list = $sec_mdl->getSeckGoodsData();
    }
    /*
     * 获取商品下所有秒杀sku的信息
     * **/
    public function getAllSeckillSkuList($condition)
    {
        $sec_goods_mdl = new VslSeckGoodsModel();
        $all_seckill_sku_list = $sec_goods_mdl->getAllSeckillSkuList($condition);
        return $all_seckill_sku_list;
    }
    /*
     * 获取秒杀sku的信息
     * **/
    public function getSeckillSkuInfo($condition)
    {
        $sec_goods_mdl = new VslSeckGoodsModel();
        $seckill_sku_list = $sec_goods_mdl->getSeckillSkuInfo($condition);
        return $seckill_sku_list;
    }
    /*
     * 减少seckill库存
     * **/
    public function dec_seckill_store($condition, $num){
        $sec_goods_mdl = new VslSeckGoodsModel();
        $bool = $sec_goods_mdl->where($condition)->setDec('seckill_num', $num);
        return $bool;
    }
    /*
     * 判断当前商品是否为秒杀商品并且是否已经开始了活动
     * **/
    public function isSeckillGoods($condition)
    {
        $is_addons = getAddons('seckill',$this->website_id);
        if(!$is_addons){
            return false;
        }
        $sec_mdl = new VslSeckillModel();
        $seckill_list = $sec_mdl->isSeckillGoods($condition);
        //判断秒杀活动是否开启
        $addons_conf_server = new AddonsConfigService();
        $addons_seckill_info = $addons_conf_server->getAddonsConfig('seckill', $this->website_id);
        if($addons_seckill_info->is_use === 0){//等于0就是未启动
            return false;
        }
        if(!empty($seckill_list['goods_id'])){
            $now_time = time();
            if($now_time < $seckill_list['seckill_now_time']){
                $is_start = false;//未开始
            }elseif($now_time>=$seckill_list['seckill_now_time'] && $now_time<=$seckill_list['seckill_now_time']+24*3600){
                $is_start = true;//开始
            }else{
                $is_start = false;//结束
            }
            if($is_start){
                return $seckill_list;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    /*
     * 只通过sku 判断sku是否在秒杀活动
     * **/
    public function isSkuStartSeckill($condition)
    {
        $sec_mdl = new VslSeckillModel();
        //判断秒杀活动是否开启
        $goods_server = new goods();
        try{
            $addons_seckill_info = getAddons('seckill', $this->website_id);
            if(!$addons_seckill_info){
                return false;
            }
            $condition['s.website_id'] = $this->website_id;
            //通过商品sku找店铺id
            if($condition['nsg.sku_id']){
                $condition_shop['gs.sku_id'] = $condition['nsg.sku_id'];
            }else{
                $condition_shop['g.goods_id'] = $condition['nsg.goods_id'];
            }
            $sku_shop_info = $goods_server->getSkuShopId($condition_shop);
            $shop_id = $sku_shop_info['shop_id'];
            $condition['s.shop_id'] = $shop_id;
            //通过当前时间组条件 24小时未过期 //seckill_now_time+24*3600>time()  seckill_now_time<=time()
            $max_time = time();
            $min_time = time()-24*3600;
            $condition['s.seckill_now_time'] = [
                ['<=', $max_time], ['>', $min_time]
            ];
            $seckill_list = $sec_mdl->isSeckillGoods($condition);
            return $seckill_list;
        }catch(\Exception $e){
//            echo $e->getMessage();exit;
        }
    }
    /*
     * 判断秒杀商品规定时间24小时内是否存在
     * **/
    public function IsSeckillInTwentyFour($goods_id, $seckill_date)
    {
        $seckill_mdl = new VslSeckillModel();
//        $condition['s.seckill_now_time'] = [['>',$seckill_start_time],['<=',$seckill_end_time]];
        $condition1['sg.goods_id'] = $goods_id;
        $condition1['sg.del_status'] = 1;
        $condition1['s.website_id'] = $this->website_id;
//        $seckill_info = $seckill_mdl->alias('s')->where($condition)->join('vsl_seckill_goods sg', 's.seckill_id = sg.seckill_id','LEFT')->select();
        $seckill_info = $seckill_mdl->alias('s')->where($condition1)->where(function($query) use ($seckill_date){
            //传过来的时间往前推
            $seckill_end_time1 = strtotime($seckill_date);
            $seckill_start_time1 = $seckill_end_time1-24*3600;
            //传过来的时间往后推
            $seckill_start_time2 = strtotime($seckill_date);
            $seckill_end_time2 = $seckill_start_time2+24*3600;
            $condition['s.seckill_now_time'] = [['>',$seckill_start_time1],['<=',$seckill_end_time1]];
            $condition2['s.seckill_now_time'] = [['>',$seckill_start_time2],['<',$seckill_end_time2]];
            $query->where($condition)->whereor(function($query2)use($condition2){
                $query2->where($condition2);
            });
        })->join('vsl_seckill_goods sg', 's.seckill_id = sg.seckill_id','LEFT')->select();
//        if($goods_id == 624){
//            echo  $seckill_mdl->getLastSql();exit;
//        }
        return $seckill_info;
    }
    /*
     * 下单成功减剩余库存
     * **/
    public function subSeckillGoodsStock($seckill_id, $sku_id, $num)
    {
        $num = (int)$num;
        $seck_goods_mdl = new VslSeckGoodsModel();
        $res = $seck_goods_mdl->where(['seckill_id'=>$seckill_id, 'sku_id'=>$sku_id])->setDec('remain_num', $num);
        return $res;
    }
    /*
     * 取消订单加剩余库存
     * **/
    public function addSeckillGoodsStock($seckill_id, $sku_id, $num)
    {
        $seckill_goods_mdl = new VslSeckGoodsModel();
        $seckill_remain_num_arr = $seckill_goods_mdl->field('remain_num')->where(['seckill_id'=>$seckill_id, 'sku_id'=>$sku_id])->find();
        $seckill_remain_num = $seckill_remain_num_arr['remain_num'];
        $data_goods_sku = array(
            'remain_num' => $seckill_remain_num + $num
        );
        $seckill_goods_mdl->save($data_goods_sku,
            ['seckill_id'=>$seckill_id, 'sku_id'=>$sku_id]
        );
    }
    /*
     * 判断订单商品是否是秒杀商品
     * **/
    public function orderSkuIsSeckill($order_id, $sku_id){
        $order_goods_promotion_mdl = new VslOrderGoodsPromotionDetailsModel();
        $order_goods_promotion_info = $order_goods_promotion_mdl->where(['order_id'=>$order_id, 'sku_id'=>$sku_id, 'promotion_type'=>'SECKILL'])->find();
        if($order_goods_promotion_info){
            return $order_goods_promotion_info;
        }else{
            return false;
        }
    }
    /*
     * 获取首页秒杀入口商品 3个
     * **/
    public function getIndexSeckillGoods($condition, $order)
    {
        $seckill_mdl = new VslSeckillModel();
        $seckill_goods_list = $seckill_mdl->alias('s')
            ->field('s.*,min(sg.seckill_price) AS seckill_price,sg.goods_id,g.goods_name,ap.pic_cover')
            ->where($condition)->join('vsl_seckill_goods sg','s.seckill_id = sg.seckill_id','LEFT')
            ->join('vsl_goods g','g.goods_id = sg.goods_id','LEFT')
            ->join('sys_album_picture ap','g.picture = ap.pic_id','LEFT')
            ->group('goods_id')
            ->order($order)
            ->limit(3)
            ->select();
        return $seckill_goods_list;
    }
    /*
     * 获取C端店铺报名条件
     * **/
    public function getShopSeckillRequirements()
    {
        //查询出店铺的可报名条件
        $configModel = new AddonsConfigModel();
        $seck_info = $configModel->getInfo([
            'addons' => 'seckill',
            'website_id' => $this->website_id
        ], '*');
        $value = json_decode($seck_info['value'],true);
        if($value['is_condition'] != 0){
            $condition_arr = json_decode($value['condition_check_val'],true);
            $shop_mdl = new VslShopModel();
            $shop_condition['shop_id'] = $this->instance_id;
            $shop_condition['website_id'] = $this->website_id;
            $shop_score_list = $shop_mdl->getInfo($shop_condition, 'margin,shop_desccredit,shop_servicecredit,shop_deliverycredit');
            //保证金
            $i = 0;
            $unapply_condition_arr = [];
            if (!empty($condition_arr['margin'])) {
                $condition_margin = $condition_arr['margin'];
                $shop_margin = $shop_score_list['margin'];
                //判断满足啥条件
                if($shop_margin>=$condition_margin){
                    $i++;
                    $unapply_condition_arr['margin_info'] = '保证金需达到'.$condition_margin.'元，已达成。';
                }else{
                    $unapply_condition_arr['margin_info'] = '保证金需达到'.$condition_margin.'元，未达成。';
                }
            }
            //商品描述
            if (!empty($condition_arr['shop_desccredit'])) {
                $condition_shop_desccredit = $condition_arr['shop_desccredit'];
                $shop_desccredit = $shop_score_list['shop_desccredit'];
                //判断满足啥条件
                if($shop_desccredit>=$condition_shop_desccredit){
                    $i++;
                    $unapply_condition_arr['shop_desccredit'] = '商品描述评分需达到'.$condition_shop_desccredit.'分，已达成。';
                }else{
                    $unapply_condition_arr['shop_desccredit'] = '商品描述评分需达到'.$condition_shop_desccredit.'分，未达成。';
                }
            }
            //商家服务
            if (!empty($condition_arr['shop_servicecredit'])) {
                $condition_shop_servicecredit = $condition_arr['shop_servicecredit'];
                $shop_servicecredit = $shop_score_list['shop_servicecredit'];
                //判断满足啥条件
                if($shop_servicecredit>=$condition_shop_servicecredit){
                    $i++;
                    $unapply_condition_arr['shop_servicecredit'] = '商家服务评分需达到'.$condition_shop_servicecredit.'分，已达成。';
                }else{
                    $unapply_condition_arr['shop_servicecredit'] = '商家服务评分需达到'.$condition_shop_servicecredit.'分，未达成。';
                }
            }
            //物流发货
            if (!empty($condition_arr['shop_deliverycredit'])) {
                $condition_shop_deliverycredit = $condition_arr['shop_deliverycredit'];
                $shop_deliverycredit = $shop_score_list['shop_deliverycredit'];
                //判断满足啥条件
                if($shop_deliverycredit>=$condition_shop_deliverycredit){
                    $i++;
                    $unapply_condition_arr['shop_deliverycredit'] = '物流发货评分需达到'.$condition_shop_deliverycredit.'分，已达成。';
                }else{
                    $unapply_condition_arr['shop_deliverycredit'] = '物流发货评分需达到'.$condition_shop_deliverycredit.'分，未达成。';
                }
            }
            //店铺评分
            if (!empty($condition_arr['shop_score_num'])) {
                $condition_shop_score_num = $condition_arr['shop_score_num'];
                $shop_score_num = ($shop_score_list['shop_desccredit']+$shop_score_list['shop_servicecredit']+$shop_score_list['shop_deliverycredit'])/3;
                //判断满足啥条件
                if($shop_score_num>=$condition_shop_score_num){
                    $i++;
                    $unapply_condition_arr['condition_shop_score_num'] = '店铺评分需达到'.$condition_shop_score_num.'分，已达成。';
                }else{
                    $unapply_condition_arr['condition_shop_score_num'] = '店铺评分需达到'.$condition_shop_score_num.'分，未达成。';
                }
            }
            if($value['is_condition'] == 2){//满足单一条件
                $unapply_condition_arr['is_condition'] = 1;
                if($i>0){
                    $unapply_condition_arr['status'] = true;
                }else{
                    $unapply_condition_arr['status'] = false;
                }
            }else if($value['is_condition'] == 1){//满足全部条件
                $count = count($condition_arr);
                $unapply_condition_arr['is_condition'] = 1;
                if($i == $count){
                    $unapply_condition_arr['status'] = true;
                }else{
                    $unapply_condition_arr['status'] = false;
                }
            }
        }else{
            $unapply_condition_arr['status'] = true;
        }
        return $unapply_condition_arr;
    }
}