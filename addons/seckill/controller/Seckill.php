<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 11:16
 */

namespace addons\seckill\controller;

use addons\seckill\model\VslSeckGoodsModel;
use addons\seckill\Seckill as baseSeckill;
use addons\seckill\server\Seckill as SeckillServer;
use addons\seckill\model\VslSeckillModel;
use addons\seckill\model\VslSeckillGoodsdelInfoModel;
use data\model\VslMemberFavoritesModel;
use data\service\Goods;
use data\model\VslGoodsModel;
use data\service\GoodsCalculate\GoodsCalculate;
use data\service\User;
use think\Config;
use think\Db;
use think\Validate;
use think\View;

class Seckill extends baseSeckill
{
    public function __construct()
    {
        parent::__construct();
        $this->goods = new Goods();
    }
    /*
     * *获取platform的秒杀列表
     * **/
    public function seckillAllList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $seckillServer = new SeckillServer();
        $condition = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
//            'seckill_name' => array(
//                'like',
//                '%' . $search_text . '%'
//            )
        );
        $list = $seckillServer->seckillAllList($page_index, $page_size, $condition);
        return $list;
    }

    /*
     * 获取进行中的商品数
     * **/
    public function todaySeckillList()
    {
        $website_id = request()->post('website_id',26);
        $seckill_name = request()->post('seckill_name',0);
        $page_index = request()->request('page_index',1);
        $search_text = request()->post('search_text','');
        $page_size = request()->post('page_size',PAGESIZE);
        //获取今天的日期时间戳
        $today = strtotime(date('Y-m-d'));
        $condition = [
            'ns.website_id'=>$website_id,
            'ns.seckill_now_time' => [
                [
                    '>',time()-24*3600
                ],
                [
                    '<=',time()
                ]
            ],
            'nsg.del_status'=>1,
            'nsg.check_status'=>1
        ];
        if(!empty($search_text)){
            $condition['g.goods_name'] = ['like', '%'.$search_text.'%'];
        }
        $seckillServer = new SeckillServer();
        $res = $seckillServer->getSeckillGoodsList($page_index, $page_size, $condition, 'ns.create_time desc');
        return $res;
    }
    /*
     * 记录删除秒杀商品的删除原因
     * **/
    public function modalSeckillDelGoodsRecord()
    {
        $this->fetch('template/' . $this->module . '/seckillDelGoodsRecordDialog');
    }
    /*
     * 店铺报名条件显示
     * **/
    public function seckillRequirementsDialog()
    {
        $seckill_server = new seckillServer();
        $apply_condition = $seckill_server->getShopSeckillRequirements();
        $this->assign('is_condition',$apply_condition['is_condition']);
        unset($apply_condition['status']);
        unset($apply_condition['is_condition']);
        $this->assign('apply_condition',$apply_condition);
        $this->fetch('template/' . $this->module . '/seckillRequirementsDialog');
    }
    /*
     * 删除活动商品
     * **/
    public function delSeckillGoods()
    {
        $content = request()->post('content','');
        $goods_id = request()->post('goods_id', 0);
        $seckill_id = request()->post('seckill_id', '');
        $skgd_mdl = new VslSeckillGoodsdelInfoModel();
        $goods_service = new Goods();
        $goods_mdl = new VslGoodsModel();

        $goods_id_arr = explode(',', $goods_id);
        $seckill_id_arr = explode(',', $seckill_id);
        foreach($goods_id_arr as $k=>$goods_id){
            //判断当前商品是否是店铺的还是平台的
            $is_shop = $goods_service->getGoodsType($goods_id);
            //0 是店铺
            if($is_shop === 0){
                if(!empty($content)){
                    $goods_name = request()->post('goods_name', '');
                    if( empty($goods_name) ){
                        $goods_name_list = $goods_mdl->field('goods_name')->where(['goods_id'=>$goods_id])->find();
                        $goods_name = $goods_name_list->goods_name;
                    }
                    //判断数据库中是否存在该条对应的删除记录，如果有则更新，因为每删除一条是可以加回去的，加回去就有需要再移除的可能
                    $is_skgd_res = $skgd_mdl->where(['seckill_id'=>$seckill_id_arr[$k], 'goods_id'=>$goods_id])->find();
                    if($is_skgd_res){
                        $del_info['seckill_del_info'] = $content;
                        $skgd_mdl->where(['seckill_id'=>$seckill_id_arr[$k], 'goods_id'=>$goods_id])->update($del_info);
                    }else{
                        $params['seckill_id'] = $seckill_id_arr[$k];
                        $params['goods_id'] = $goods_id;
                        $params['seckill_del_info'] = $content;
                        $params['goods_name'] = $goods_name;
                        $skgd_mdl->data($params,true)->isUpdate(false)->save($params);
                    }
                }
            }
            $condition = [
                'goods_id' => $goods_id,
                'seckill_id' => $seckill_id_arr[$k],
            ];
            $sec_server = new SeckillServer();
            $res = $sec_server->delSeckillGoods($condition);
            if($res){
                //判断是否还有其它时间段的未过期的该类商品档
                $other_cond['sg.goods_id'] = $goods_id;
                $other_cond['sg.del_status'] = 1;
                $other_cond['s.seckill_now_time'] = ['>=', time()-24*3600];
                $is_other_seckill = $sec_server->isOtherSeckillExists($other_cond);
                if(!$is_other_seckill){
                    //将商品的促销类型去掉
                    $goods_condition = ['goods_id'=>$goods_id, 'promotion_type'=>1];
                    $goods_res['promotion_type'] = 0;
                    $goods_mdl->where($goods_condition)->update($goods_res);
                }
            }
            if($res){
                $this->addUserLog('移除/删除秒杀活动商品', $res);
            }

        }
        return AjaxReturn(1);
    }
    /*
     * 活动商品通过审核接口
     * **/
    public function passSeckillGoods()
    {
        $seckillServer = new SeckillServer();
        $seckill_id = request()->post('seckill_id',0);
        $goods_id = request()->post('goods_id',0);
        $seckill_id_arr = explode(',', $seckill_id);
        $goods_id_arr = explode(',', $goods_id);
        $res = 1;
        foreach($goods_id_arr as $k=>$gid){
            $sid = $seckill_id_arr[$k];
            //这里为什么goods_id seckill_id就够了，因为每个店铺报名后有且只有一个seckill_id，对应一个时间点，并且对应一个商品
            $condition['goods_id'] = $gid;
            $condition['seckill_id'] = $sid;
            $bool = $seckillServer->seckillGoodsChecked($condition);
            if(!$bool){
                $res = 0;
            }
        }
        if($res){
            $this->addUserLog('活动商品通过审核接口', $res);
        }
        return AjaxReturn($res);
    }
    /*
     * 获取今日活动中的有sku的商品详情弹框
     * **/
    public function seckillGoodsDetailDialog()
    {
        $condition['ns.website_id'] = $this->website_id;
        $goods_id = request()->get('goods_id',0);
        $seckill_id = request()->get('seckill_id',0);
        $condition['nsg.goods_id'] = $goods_id;
        $condition['ns.seckill_id'] = $seckill_id;
        $condition['nsg.del_status'] = 1;
        $goods = new SeckillServer();
        $field = 'nsg.sku_id, ng.goods_name, ngs.attr_value_items, ngs.sku_name, nsg.goods_id, nsg.seckill_num, nsg.seckill_price, nsg.seckill_limit_buy, nsg.remain_num';
        $seckill_goods_sku_list = $goods->getGoodsDetail($condition, $field);
        $this->assign('seckill_goods_sku_list', $seckill_goods_sku_list);
        $this->fetch('template/' . $this->module . '/seckillGoodsDetailDialog');
    }
    /**
     * 店铺秒杀商品选择
     */
//    public function modalSeckillGoodsList()
//    {
//        if (request()->post('page_index')) {
//            $index = request()->post('page_index', 1);
//            $goods_type = request()->post('goods_type', 1);
//            $search_text = request()->post('search_text');
//            if ($search_text) {
//                $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
//            }
//            $condition['ng.website_id'] = $this->website_id;
//            $condition['ng.shop_id'] = $this->instance_id;
//            $condition['ng.state'] = 1;
//            //0自营店 1全平台
//            if ($goods_type == '0') {
//                $condition['ng.shop_id'] = $this->instance_id;
//            }
//            $goods = new Goods();
//            $list = $goods->getgoodslist($index, PAGESIZE, $condition);
//            if( !empty($list['data']) ) {
//                //处理删除第一个是空sku，第二个为有sku的情况
//                foreach ($list['data'] as $k => $v) {
//                    if (!empty($v['sku_list'][0]['attr_value_items']) || !empty($v['sku_list'][1]['attr_value_items'])) {
//                        unset($list['data'][$k]['sku_list'][0]);
//                    }
//                    if (!empty($list['data'][$k])) {
//                        $goods_list[$k]['goods_id'] = $v['goods_id'];
//                    }
//                }
//                //删除多余的字段
//                $sku_list = [];
//                if(!empty($list['data'])){
//                    foreach ($list['data'] as $k => $v) {
//                        $goods_list[$k]['goods_id'] = $v['goods_id'];
//                        $goods_list[$k]['goods_name'] = $v['goods_name'];
//                        $goods_list[$k]['price'] = $v['price'];
//                        $goods_list[$k]['promotion_type'] = $v['promotion_type'];
//                        $goods_list[$k]['promotion_name'] = $goods->getGoodsPromotionType($v['promotion_type']);
//                        //处理skulist对象
//                        $v['sku_list'][0]['attr_value_items'] = trim($v['sku_list'][0]['attr_value_items']);
//                        if (!empty($v['sku_list'][0]['attr_value_items'])) {
//                            foreach ($v['sku_list'] as $sku_key => $sku_value) {
//                                $sku_val_item = $sku_value['attr_value_items'];
//                                $sku_val_arr = explode(';', $sku_val_item);
//                                $th_name_str = '';
//                                $show_value_str = '';
//                                $show_type_str = '';
//                                foreach ($sku_val_arr as $sku_val_key => $sku_val_value) {
//                                    $sku_val_value_arr = explode(':', $sku_val_value);
//                                    //按照规格规则中的顺序定义tr头
//                                    $sku_tr_id = $sku_val_value_arr[1];
//                                    $val_type = $goods->getGoodSku(['spec_value_id' => $sku_tr_id]);
//                                    $val_type_arr = $val_type[0]->toArray();
//                                    $show_type = $val_type_arr['goods_spec']['show_type'];
//                                    //根据show_type，获取规格的值，如图片的路径
//                                    if ($show_type == '3') {//图片
//                                        $pic_id = $val_type_arr['spec_value_data'];
//                                        $val_type_str = $goods->getGoodSkuPic(['pic_id' => $pic_id]);
//                                        if (empty($val_type_str)) {
//                                            $val_type_str = '暂无图片';
//                                        }
//                                    } else if ($show_type == '2') {//颜色
//                                        $val_type_str = $val_type_arr['spec_value_name'];
//                                    } else {
//                                        $val_type_str = $val_type_arr['spec_value_name'];
//                                    }
//                                    //拼接所有规格展示类型对应的值
//                                    $show_value_str .= $val_type_str . ' ';
//                                    //拼接th的名字
//                                    $th_name_str .= $val_type_arr['goods_spec']['spec_name'] . ' ';
//                                    //拼接展示类型
//                                    $show_type_str .= $show_type . ' ';
//                                }
//                                $th_name_str = trim($th_name_str);
//                                $show_type_str = trim($show_type_str);
//                                $show_value_str = trim($show_value_str);
//                                $sku_list = $sku_value->toArray();
//                                //处理sku的id对应value
//                                $sku_id_str = $sku_list['attr_value_items'];
//                                $sku_id_str_arr = explode(';', $sku_id_str);
//                                $sku_value_str = trim($show_value_str);
//                                $sku_value_str_arr = explode(' ', $sku_value_str);
//                                $im_str = '';
//                                $new_im_str = '';
//                                for ($i = 0; $i < count($sku_value_str_arr); $i++) {
//                                    $im_str .= $sku_id_str_arr[$i] . ';';
//                                    $im_str = trim($im_str, ';');
//                                    $new_im_str .= $im_str . '=' . $sku_value_str_arr[$i] . ' ';
//                                }
//                                $new_im_str = trim($new_im_str, ' ');
//                                $v['sku_list'][$sku_key]['new_im_str'] = $new_im_str;
//                                $v['sku_list'][$sku_key]['th_name_str'] = $th_name_str;
//                                $v['sku_list'][$sku_key]['show_type_str'] = $show_type_str;
//                            }
//                            /*************************当sku规格错乱的时候排序****************************/
//                            $temp = [];
//                            foreach($v['sku_list'] as $k1=>$sort_sku){
//                                $sort_arr = explode(' ',$sort_sku['new_im_str']);
//                                $sort_str = $sort_arr[0];
//                                $temp[$sort_str][$k1] = $sort_sku;
//                            }
//                            $i = 0;
//                            $sku_temp = [];
//                            foreach($temp as $k2=>$r){
//                                foreach($r as $last_val){
//                                    $sku_temp[$i] = $last_val;
//                                    $i++;
//                                }
//                            }
//                            $v['sku_list'] = $sku_temp;
//                        } else {
//                            $v['sku_list'] = $v['sku_list'][0];
//                        }
//
//                        $goods_list[$k]['shop_name'] = $v['shop_name'] ?: '自营店';
//                        $goods_list[$k]['pic_cover'] = getApiSrc($v['pic_cover']);
//                        $goods_list[$k]['sku_list'] = $v['sku_list'];
//                    }
//                }
//            }else{
//                $goods_list = [];
//            }
//
//            //处理sku字符串
//            if( !empty($goods_list) ){
//                foreach($goods_list as $sku_key2=>$sku_value2){
//                    $goods_list[$sku_key2]['sku_list'] = json_encode($sku_value2['sku_list']);
//                }
//                $list['data'] = $goods_list;
//            }else{
//                $list['data'] = '';
//                $list['page_count'] = 0;
//                $list['total_count'] = 0;
//            }
//
//            return $list;
//        }
//        $this->fetch('template/' . $this->module . '/seckillGoodsDialog');
//    }
    /**
     * 店铺秒杀商品选择
     */
    public function modalSeckillGoodsList()
    {
//        header('Content-Encoding: *');
//        header('Vary: Accept-Encoding');
        if (request()->post('page_index')) {
            $index = request()->post('page_index', 1);
            $goods_type = request()->post('goods_type', 1);
            $search_text = request()->post('search_text');
            $seckill_time = request()->post('seckill_time');
            $seckill_name = request()->post('seckill_name');
            if($seckill_time && $seckill_name){
                $seckill_date = $seckill_time.' '.$seckill_name.':00:00';
            }
            if ($search_text) {
                $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['ng.website_id'] = $this->website_id;
            $condition['ng.shop_id'] = $this->instance_id;
            $condition['ng.state'] = 1;
            //0自营店 1全平台
            if ($goods_type == '0') {
                $condition['ng.shop_id'] = $this->instance_id;
            }
            $goods = new Goods();
            $list = $goods->getModalGoodsList($index, $condition, $seckill_date);
            return $list;
        }
        $this->fetch('template/' . $this->module . '/seckillGoodsDialog');
    }
    /*
     * 添加秒杀配置
     * **/
    public function secSetting()
    {
        if(!empty($_POST)){
            if( !isset($_POST['sk_quantum_str'] ) || ( empty($_POST['sk_quantum_str']) && $_POST['sk_quantum_str'] != '0' ) ){
                $code = -22;
                $message = '秒杀时段不能为空';
                return compact($code,$message);
            }
            if( !isset($_POST['pay_limit_time'] ) || empty($_POST['pay_limit_time']) ){
                $code = -22;
                $message = '支付限时不能为空';
                return compact($code,$message);
            }
        }
        $seckillServer = new SeckillServer();
        $is_open = $_POST['is_open'] ?: 0;
        unset($_POST['is_open']);
        $value = json_encode($_POST);
        $result = $seckillServer->saveSeckConfig($is_open,$value);
        if($result){
            $this->addUserLog('添加秒杀配置', $result);
        }
        setAddons('seckill', $this->website_id, $this->instance_id);
        setAddons('seckill', $this->website_id, $this->instance_id, true);
        return AjaxReturn($result);
    }
    /*
     * 添加秒杀活动及其商品
     * **/
    public function addSecKill()
    {
        $sec_server = new SeckillServer();
        $goods_mdl = new VslGoodsModel();
        $seckill_name = request()->post('seckill_name','');
        $seckill_time = request()->post('seckill_time','');

        if(empty($seckill_time)){
            return ['code'=>-1,'message'=>'活动时间不能为空'];
        }
        if(empty($seckill_name)&&$seckill_name !== '0'){
            return ['code'=>-1,'message'=>'秒杀场次不能为空'];
        }
        //判断商品是否存在其他活动中
        $is_promotion_list = $goods_mdl->where(['goods_id'=>$_POST['goods_id'], 'website_id'=>$this->website_id, 'promotion_type'=>['neq',0]])->find();
        $is_promotion = $is_promotion_list['promotion_type'];
        if($is_promotion_list && $is_promotion != 1){
            return ['code'=>-334,'message'=>'商品存在其它促销活动中'];
        }
        //验证seckill_goods表
        $sku_goods = [];
        foreach($_POST['goods_info'] as $sku_id=>$goods){
            $sku_goods['seckill_num'] = $goods['seckill_num'];
            $sku_goods['seckill_price'] = $goods['seckill_price'];
            if(empty($sku_goods['seckill_num'])){
                return ['code'=>-333,'message'=>'商品所有规格的活动库存不能为空'];
            }
//            if(empty($sku_goods['seckill_price']) && $sku_goods['seckill_price'] != 0){
            if(empty($sku_goods['seckill_price'])){
                return ['code'=>-333,'message'=>'商品所有规格的活动价格不能为空并且须大于0'];
            }
            //限购
            if(empty($goods['seckill_limit_buy'])){
                return ['code'=>-333,'message'=>'商品所有规格的限购数目不能为空并且须大于0'];
            }
        }
        //查询24小时内该场次是否存在该商品了，如果存在，则返回该商品已经存在
        $where['ns.website_id'] = $this->website_id;
        $where['ns.shop_id'] = $this->instance_id;
        //seckill_now_time+24*3600>time()  seckill_now_time<=time()
        if((empty($_POST['seckill_name']) && $_POST['seckill_name'] !== '0') || empty($_POST['seckill_time'])){
            return ['code'=>-333,'message'=>'所选活动时间或活动场次不能为空'];
        }
        $post_seckill_now_time = $_POST['seckill_time'].' '.$_POST['seckill_name'].':00:00';
        $post_time = strtotime($post_seckill_now_time);
        $min_time = $post_time-24*3600;
        $max_time = $post_time+24*3600;
        $test = date('Y-m-d H:i:s',$max_time);
//        var_dump($test);exit;
        $where['ns.seckill_now_time'] = [['>',$min_time], ['<',$max_time]];
        $where['nsg.goods_id'] = $_POST['goods_id'];
        //获取店铺审核方式 0-手动 1-自动
        $check_method = (int)$sec_server->getCheckMethod();
        $seckill_mdl = new VslSeckillModel();
        $goods_list = $seckill_mdl->getSeckillGoodsList($where);
        $del_status = (int)$goods_list[0]->del_status;
        //del_status为1说明该商品为正常存在，0为移除，如果继续添加，则将状态改为1。
        if($goods_list && $del_status === 1){
            return ['code'=>-334,'message'=>'该商品24小时内已有其它秒杀活动存在'];
        }elseif($goods_list && $del_status === 0){
            $seckill_id = (int)$goods_list[0]->seckill_id;
            $ret_val = $sec_server->updateSeckillGoodsDelStatus($seckill_id,$check_method, $_POST);
        }else{
            $ret_val = $sec_server->addSecKill($_POST, $check_method);
        }
        if($ret_val){
            $this->addUserLog('添加秒杀活动及其商品', $ret_val);
        }
        return AjaxReturn(1);
    }
    //ajax获取某个秒杀点的审核与未审核的商品列表信息
    public function getAjaxSeckNameGoodsList()
    {
        $seckillServer = new SeckillServer();
        $now_check_date = request()->post('seckill_time_str', '');
        $check_status = request()->post('check_status');
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size',PAGESIZE);
        $seckill_time = strtotime($now_check_date);
        $seckill_name = request()->post('seckill_name','');
        $search_text = request()->post('search_text','');
        $website_id = $this->website_id;
        $check_date_arr = $seckillServer->getSeckillCheckTime();
        $end_date = array_pop($check_date_arr);
        $end_time = strtotime($end_date);
        $condition = [
            'ns.website_id'=>$website_id,
            'ns.seckill_name' => $seckill_name,
            'nsg.check_status' => $check_status,
            'nsg.del_status' => 1,
            'ns.seckill_now_time'=> ['>=',time()],
        ];
        if($now_check_date == '更多'){
            $condition['ns.seckill_time'] = ['>', $end_time];
        }else{
            //获取某个时间段的商品
            $condition['ns.seckill_time'] = $seckill_time;
        }

        if(!empty($search_text)){
            $condition['g.goods_name'] = ['like', '%'.$search_text.'%'];
        }
        $sec_goods_list = $seckillServer->getSeckillGoodsList($page_index, $page_size, $condition, 'ns.create_time desc');
        return $sec_goods_list;
    }
    /*
     * 获取商品信息统计数据
     * **/
    public function getSecGoodsInfoCount(){
        $seckillServer = new SeckillServer();
        $page_index = request()->request('page_index',1);
        $page_size = request()->request('page_size',PAGESIZE);
        //条件
        $condition['nsg.goods_id'] = ['>', 0];
        $condition['nsg.seckill_price'] = ['>', 0];
        $condition['ns.website_id'] = $this->website_id;
        $search_text = request()->post('search_text','');
        $seckill_date = request()->post('seckill_date','');
        $seckill_name = request()->post('seckill_name','');
        if(!empty($search_text)){
            $condition['g.goods_name'] = ['like', '%'.$search_text.'%'];
        }
        if(!empty($seckill_date)){
            $seckill_time = strtotime($seckill_date);
            $condition['ns.seckill_time'] = $seckill_time;
        }
        if($seckill_name != '-1' && $seckill_name !== ''){
            $condition['ns.seckill_name'] = $seckill_name;
        }
        //排序
        $order = request()->post('order','');
        switch($order){
            case 'store_num_asc':
                $order = 'store_num ASC';
                break;
            case 'store_num_desc':
                $order = 'store_num DESC';
                break;
            case 'store_price_asc':
                $order = 'store_price ASC';
                break;
            case 'store_price_desc':
                $order = 'store_price DESC';
                break;
            case 'seckill_price_asc':
                $order = 'seckill_price ASC';
                break;
            case 'seckill_price_desc':
                $order = 'seckill_price DESC';
                break;
        }
        $goods_count_list = $seckillServer->getSecGoodsCountInfo($condition,$order);
        //处理每个商品对应的商品名称、图片、店铺
        $new_goods_count_info_arr = [];
        $new_goods_count_arr = [];
        $goods_quantity_total = 0;
        $store_price_total = 0;
        $store_num_total = 0;
        foreach($goods_count_list as $k=>$goods_count){
            $goods_id = $goods_count->goods_id;
            $every_goods_info = $seckillServer->getEveryGoodsInfo($goods_id);
            if (empty($every_goods_info)) {
                continue;
            }
            $new_goods_count_info_arr[$k]['goods_name'] = $every_goods_info->goods_name;
            $new_goods_count_info_arr[$k]['goods_img'] = getApiSrc($every_goods_info->pic_cover_small);
            $new_goods_count_info_arr[$k]['shop_name'] = $every_goods_info->shop_name;
            $new_goods_count_info_arr[$k]['store_price'] = $goods_count->store_price;
            $new_goods_count_info_arr[$k]['store_num'] = $goods_count->store_num;
            $new_goods_count_info_arr[$k]['seckill_price'] = $goods_count->seckill_price;
            //统计总商品件数、总销量、总销售额
            $goods_quantity_total++;
            $store_price_total = $store_price_total+$goods_count->store_price;
            $store_num_total = $store_num_total+$goods_count->store_num;
        }
        $new_goods_count_arr['goods_quantity_total'] = $goods_quantity_total;
        $new_goods_count_arr['store_price_total'] = $store_price_total;
        $new_goods_count_arr['store_num_total'] = $store_num_total;
        $total_count = count($goods_count_list);
        $page_count = ceil($total_count/$page_size);
        //分页起始量
        $start_offset = ($page_index-1)*$page_size;
        //分页末变量
        $end_offset = $page_index*$page_size;
        $res_goods_count_info = [];
        for($i=$start_offset;$i<$end_offset;$i++){
            $res_goods_count_info[$i] = $new_goods_count_info_arr[$i];
            if(empty($res_goods_count_info[$i])){
                unset($res_goods_count_info[$i]);
            }
        }
        return  [
            'data' => $res_goods_count_info,
            'goods_count_total'=>$new_goods_count_arr,
            'total_count' => $total_count,
            'page_count' => $page_count,
        ];
    }
    /*
     * Ajax根据条件获取后台秒杀商品列表-商户C端
     * **/
    public function getAdminSecKillList()
    {
        $seckillServer = new SeckillServer();
        $status = request()->post('status');
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size',PAGESIZE);
        $search_text = request()->post('search_text','');
        $date = request()->post('date','');
        $website_id = $this->website_id;
        $instance_id = $this->instance_id;
        $condition['ns.website_id'] = $website_id;
        $condition['ns.shop_id'] = $instance_id;
        //处理日期条件
        if( !empty($date) ){
           $date_arr = explode(' - ', $date);
           $start_date = $date_arr[0];
           $end_date = $date_arr[1];
           if($start_date == $end_date){
               $start_seckill_time = strtotime($start_date);
               $condition['ns.seckill_time'] = $start_seckill_time;
           }elseif( !empty($start_date) && !empty($end_date) ){
               $start_seckill_time = strtotime($start_date);
               $end_seckill_time = strtotime($end_date);
               $condition['ns.seckill_time'] = [
                   ['>', $start_seckill_time],
                   ['<', $end_seckill_time],
               ];
           }
        }
        //处理商品名称
        if( !empty($search_text) ){
            $condition['g.goods_name'] = ['like', '%'.$search_text.'%'];
        }
        //获取每个状态的总数
        $goods_status_count_arr = $seckillServer->getStatusGoodsCount($condition);
        //今天当前时间
        $h = date('H');
        $today = date('Y-m-d');
        $seckill_now_time = strtotime($today.' '.$h.':00:00');

        //处理状态条件
        switch($status){
            case 'going':
                //获取当前的时间点和今天日期
                $condition['ns.seckill_now_time'] = [
                    [
                        '>',time()-24*3600
                    ],
                    [
                        '<=',time()
                    ]
                ];
                $condition['nsg.check_status'] = 1;
                $condition['nsg.del_status'] = 1;
                break;
            case 'unstart':
                //获取当前的时间点和今天日期 seckill_now_time > time()
                $condition['ns.seckill_now_time'] = ['>', time()];
                $condition['nsg.check_status'] = 1;
                $condition['nsg.del_status'] = 1;
                break;
            case 'uncheck':
                //获取当前的时间点和今天日期
                $condition['ns.seckill_now_time'] = ['>', time()];
                $condition['nsg.check_status'] = 0;
                $condition['nsg.del_status'] = 1;
                break;
            case 'ended':
                //获取当前的时间点和今天日期 seckill_now_time + 24*3600 < time()
                $condition['ns.seckill_now_time'] = ['<', time() - 24*3600];
                $condition['nsg.del_status'] = 1;
                break;
            case 'refused':
                $condition['nsg.del_status'] = 0;
                //获取vsl_seckill_delgoods_info中的数据
                break;
        }
        if($status != 'refused'){
            $sec_goods_list = $seckillServer->getSeckillGoodsList($page_index, $page_size, $condition, 'ns.create_time desc');
        }else{
            $sec_goods_list = $seckillServer->getStatusGoodsList($page_index, $page_size, $condition, 'ns.create_time desc');
        }
        $sec_goods_list['status_goods_total'] = $goods_status_count_arr;
        return $sec_goods_list;
    }
    /*
     * 获取并处理所有循环时间段
     * **/
    public function getAllSecTime()
    {
        $seckillServer = new SeckillServer();
        //先获取当前时间点
        $now = date('H');
        $now = (int)$now;
        //获取所有的报名时间段
        $sk_quantum_str = $seckillServer->getSeckTime();//0,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23
        $sk_quantum_arr = explode(',', $sk_quantum_str);
        $new_quantum_arr = [
            [
                'tag_name'=>'好货疯抢',
                'tag_status'=>'started',//已疯抢
                'condition_time' => 'good_rushed',
                'condition_day' => 'good_rushed',
            ]
        ];
        foreach($sk_quantum_arr as $k=>$time){
            if($time==$now){
                //抢购中
                $tag_arr = [
                    'tag_name' => $time.':00',
                    'tag_status' => 'going',//抢购中
                    'condition_time' => $time,
                    'condition_day' => 'today',
                ];
                array_push($new_quantum_arr,$tag_arr);
            }
        }
        foreach($sk_quantum_arr as $k2=>$time2){
            $time2 = (int)$time2;
            if($time2>$now && $time2<=23){
                //则为今日的时间点
                $tag_arr = [
                    'tag_name' => $time2.':00',
                    'tag_status' => 'unstart',//即将开抢
                    'condition_time' => $time2,
                    'condition_day' => 'today',
                ];
                array_push($new_quantum_arr,$tag_arr);
            }
        }
        foreach($sk_quantum_arr as $k3=>$time3){
            $time3 = (int)$time3;
            if($time3<$now){
                //则为今日的时间点
                $tag_arr = [
                    'tag_name' => $time3.':00',
                    'tag_status' => 'tomorrow_start',//即将开抢
                    'condition_time' => $time3,
                    'condition_day' => 'tomorrow',
                ];
                array_push($new_quantum_arr,$tag_arr);
            }
        }
        $res_arr['code'] = 1;
        $res_arr['message'] = '获取成功';
        $res_arr['data'] = $new_quantum_arr;
        return $res_arr;
    }
    /*
     * wap端各自条件秒杀商品列表接口
     * **/
    public function getSeckillGoodsList()
    {
        $seckillServer = new SeckillServer();
        $condition_time = request()->post('condition_time');
        $condition_day = request()->post('condition_day');
        $tag_status = request()->post('tag_status');
        $page_size = request()->post('page_size',PAGESIZE);
        $page_index = request()->post('page_index',1);
        $condition['ns.website_id'] = $this->website_id;
        $condition['nsg.check_status'] = 1;
        $condition['nsg.del_status'] = 1;
        if($condition_time == 'good_rushed' && $condition_day == 'good_rushed'){
            //当前时间大于开始时间，小于结束时间
            $time = time();
            $past_time = $time - 24*3600;
            $condition['ns.seckill_now_time'] = [
                ['>=', $past_time],['<=', $time]
            ];
        }else{
            if($condition_day == 'today'){
                $seckill_name = $condition_time;
                $seckill_time = strtotime(date('Y-m-d'));
            }elseif($condition_day == 'tomorrow'){
                $seckill_name = $condition_time;
                $seckill_time = strtotime(date('Y-m-d', strtotime('+1 day')));
            }
            $condition['ns.seckill_name'] = $seckill_name;
            $condition['ns.seckill_time'] = $seckill_time;
        }
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
                    $sql1 .= "  ";
                }
                $sql2 .= " )";
                $condition[] = ['exp', $sql1 . $sql2];
            }
        }

        //获取所有的已审核秒杀、及秒杀商品
        $order_by = 'ns.create_time desc';
        $seckill_goods_list = $seckillServer->getSeckillGoodsList($page_index, $page_size, $condition, $order_by);
        $seckill_goods_arr = objToArr($seckill_goods_list);
        //商品列表：商品名称、已抢（实际+虚拟抢购量）、总活动库存、抢购说明、百分数、秒杀价、原价。
        $new_data = [];
        $seck_goods_mdl = new VslSeckGoodsModel();
        foreach($seckill_goods_arr['data'] as $k=>$data){
            //查询该商品sku所有的活动库存、剩余库存、虚拟抢购量
            $seckill_id = $data['seckill_id'];
            $goods_id = $data['goods_id'];
            $sec_goods_info = $seck_goods_mdl->getInfo(['goods_id' => $goods_id, 'seckill_id' => $seckill_id], 'sum(seckill_num) as seckill_num, sum(remain_num) as remain_num, sum(seckill_vrit_num) as seckill_vrit_num');
            //已抢购 活动库存-剩余库存+虚拟抢购量
//            $robbed_num = (int)$sec_goods_info['seckill_num'] - ((int)$sec_goods_info['remain_num'] + (int)$sec_goods_info['seckill_vrit_num']);
            $robbed_num = (int)$sec_goods_info['seckill_num'] - ((int)$sec_goods_info['remain_num']);// 调整：虚拟抢购量显示在进度条中
            //已抢购百分数
            $robbed_percent = round($robbed_num/(int)$sec_goods_info['seckill_num']*100)."％"  ;
            //获取当前的时间点和今天日期
            $h = (int)date('H');
            if($condition_day == 'good_rushed' && $condition_time == 'good_rushed'){
                $rob_time = '马上抢';
            }elseif($condition_day == 'today' && $h == $condition_time){
                $rob_time = '马上抢';
            }elseif($condition_day == 'today' && $h < $condition_time){
                $rob_time = $seckill_name.'点开抢';
            }elseif($condition_day == 'tomorrow' && $h > $condition_time){
                $rob_time = '明日'.$seckill_name.'点开抢';
            }
            //判断商品是否收藏过
            $member_favorite_mdl = new VslMemberFavoritesModel();
            $is_collection = $member_favorite_mdl->getInfo(
                    ['fav_id'=>$data['goods_id'],
                    'fav_type'=>'goods',
                    'seckill_id'=>$data['seckill_id']],
                    '*');
            $is_collection = $is_collection ? true : false;
            $new_data[$k]['goods_id'] = $data['goods_id'];
            $new_data[$k]['goods_name'] = $data['goods_name'];
            $new_data[$k]['seckill_id'] = $data['seckill_id'];
            $new_data[$k]['goods_id'] = $data['goods_id'];
            $new_data[$k]['goods_img'] = $data['pic_cover_big'];
            $new_data[$k]['remain_num'] = $data['remain_num'];
            $new_data[$k]['robbed_num'] = $robbed_num;
            $new_data[$k]['seckill_num'] = (int)$data['seckill_num'];
            $new_data[$k]['robbed_percent'] = $robbed_percent;
            $new_data[$k]['price'] = (float)$data['price'];
            $new_data[$k]['seckill_price'] = (float)$data['seckill_price'];
            $new_data[$k]['rob_time'] = $rob_time;
            $new_data[$k]['condition_day'] = $condition_day;
            $new_data[$k]['condition_time'] = $condition_time;
            $new_data[$k]['tag_status'] = $tag_status;
            $new_data[$k]['is_collection'] = $is_collection;
        }
        $seckill_goods_arr['data'] = $new_data;
        $res_arr['code'] = 1;
        $res_arr['message'] = '获取成功';
        $res_arr['data']['sec_goods_list'] = $seckill_goods_arr['data'];
        $res_arr['data']['page_count'] = $seckill_goods_arr['page_count'];
        $res_arr['data']['total_count'] = $seckill_goods_arr['total_count'];
        return $res_arr;
    }
    public function getIndexSeckillList(){
        //获取当前小时的上一个时间点
        $seckill_server = new SeckillServer();
        $times_list = $seckill_server->getSeckTime();
        $times_arr = explode(',',$times_list);
        $new_times = array_filter($times_arr,function($val){
            //获取当前时间
            $hours = date('H');
            if($hours == 0){
                $hours = 24;
            }
            if($val>=$hours){
                return $val;
            }
        });
        $goods_sort = request()->post('seckill_goods_sort');
        if (!isset($goods_sort)) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        //活动申请时间升序 0
        if($goods_sort == 0){
            $order = 's.create_time asc';
        }
        //活动申请时间降序 1
        if($goods_sort == 1){
            $order = 's.create_time asc';
        }
        //销量升序 2
        if($goods_sort == 2){
            $order = 'sum(seckill_sales) asc';
        }
        //销量降序 3
        if($goods_sort == 3){
            $order = 'sum(seckill_sales) desc';
        }
        //收藏数升序 4
        if($goods_sort == 4){
            $order = 'g.collects asc';
        }
        //收藏数降序 5
        if($goods_sort == 5){
            $order = 'g.collects desc';
        }
        foreach($new_times as $h){
            //获取商品
            $condition['s.seckill_name'] = $h;
            //获取今天
            $today = strtotime(date('Y-m-d'));
            $condition['s.seckill_time'] = $today;
            $condition['s.website_id'] = $this->website_id;
            $seckill_goods_list = $seckill_server->getIndexSeckillGoods($condition, $order);
            if($seckill_goods_list){
                break;
            }
        }
//        p($seckill_goods_list);exit;
        $now_hours = date('H');
        if ($now_hours == (int)$seckill_goods_list[0]['seckill_name']) {
            $seckill_going_status = 'going';
        } elseif ($now_hours < (int)$seckill_goods_list[0]['seckill_name']) {
            $seckill_going_status = 'unstart';
        }
        $today = date('d');
        if($seckill_goods_list){
            foreach($seckill_goods_list as $k=>$v){
                $data_list['seckill_time'] = $v['seckill_name']?:'';
                $data_list['seckill_going_status'] = $seckill_going_status?:'';
                $data_list['end_time'] = strtotime(date('Y-m-'.($today+1).' '.$v['seckill_name'].':00:00'))?:'';//结束时间
                $data_list['begin_time'] = strtotime(date('Y-m-d '.$v['seckill_name'].':00:00'))?:'';//开始时间
                $data_list['goods_list'][$k]['pic_cover'] = getApiSrc($v['pic_cover'])?:'';
                $data_list['goods_list'][$k]['goods_name'] = $v['goods_name']?:'';
                $data_list['goods_list'][$k]['goods_id'] = $v['goods_id']?:'';
                $data_list['goods_list'][$k]['seckill_price'] = $v['seckill_price']?:'';
            }
        }
        
        if(!$data_list){
            $data_list['seckill_time'] = '';
            $data_list['seckill_going_status'] = '';
            $data_list['end_time'] = '';//结束时间
            $data_list['begin_time'] = '';//开始时间
            $data_list['goods_list'] = [];
        }
//        echo json_encode([
//            'code'=>0,'message'=>'获取成功',
//            'data'=>$data_list
//        ]);exit;
        return json([
            'code'=>0,'message'=>'获取成功',
            'data'=>$data_list
        ]);
//        var_dump($time);exit;
    }
}