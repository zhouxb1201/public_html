<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/26 0026
 * Time: 14:41
 */

namespace addons\presell\service;

use data\model\AddonsConfigModel;
use data\model\VslActivityOrderSkuRecordModel;
use data\model\VslOrderModel;
use data\model\VslPresellGoodsModel;
use data\model\VslPresellModel;
use data\model\VslPushMessage;
use data\model\VslGoodsSkuModel;
use data\model\VslGoodsModel;
use data\service\Goods;
use data\model\VslOrderGoodsModel;
use data\service\BaseService;
use phpDocumentor\Reflection\Types\Object;
use think\db;

class Presell extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }


    //活动购买总人数
    public function get_presell_count_people($presell_id){
        $sql = 'SELECT COUNT(DISTINCT(buyer_id)) AS num FROM `vsl_order` WHERE `presell_id` = '.$presell_id;
        return Db::query($sql);
    }

    //获取已购买的数量（总数）
    public function get_presell_buy_num($presell_id){
        $asor_mdl = new VslActivityOrderSkuRecordModel();
        $buy_num = $asor_mdl->where(['activity_id' => $presell_id, 'buy_type' => 4])->sum('num');
        return $buy_num;
    }

    //获取当前用户已购买总数
    public function get_user_count($presell_id){
//        $order_goods = new VslOrderGoodsModel();
//        $condition['buyer_id'] = $this->uid;
//        $condition['presell_id'] = $presell_id;
////        $count = $order_goods->getCount($condition);
//        $count = $order_goods->where($condition)->sum('num');
        $asor_mdl = new VslActivityOrderSkuRecordModel();
        $condition['activity_id'] = $presell_id;
        $condition['uid'] = $this->uid;
        $condition['buy_type'] = 4;
        $count = $asor_mdl->where($condition)->sum('num');
        return $count;
    }

    //规格商品已购买的数量
    public function get_presell_sku_num($presell_id,$sku_id){

//        $order = new VslPresellGoodsModel();
//        $num = $order->alias('a')->join('vsl_order_goods b','a.presell_id=b.presell_id','left')->field('b.num')->where(['a.presell_id'=>$presell_id,'b.sku_id'=>$sku_id])->SUM('num');
        $activity_goods = new VslActivityOrderSkuRecordModel();
        $num = $activity_goods->where(['activity_id' => $presell_id, 'sku_id' => $sku_id])->sum('num');
        return $num;
    }
    /*
     * 更新活动状态
     *
     */
    public function update_presell_status($field,$content,$where){

        $promotion_discount = new VslPresellModel();
        $data[$field] = $content;
        $where[$field] = $content;
        $promotion_discount->data($where,true)->isUpdate(true)->save();
    }

    //获取各活动状态的数量status
    public function get_status_count($status=''){
        $presell = new VslPresellModel;
        if(empty($status)){
            $count = $presell->where(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id])->count();
        }else{
            $count = $presell->where(['website_id'=>$this->website_id,'status'=>$status,'shop_id'=>$this->instance_id])->count();
        }

        return $count;
    }

    //获取预售详情  ，根据预售ID
    public function get_presell_info($id){

        $presell = new VslPresellModel();
        $goods = new VslGoodsModel();
        $info = $presell->alias('a')->join('vsl_presell_goods g','a.id=g.presell_id','left')->field('a.*,g.sku_id,g.max_buy,g.presell_id,g.first_money,g.all_money,g.presell_num,g.vr_num')->where(['a.id'=>$id])->select();
        $pic_info = $goods->alias('a')->join('sys_album_picture b','a.picture=b.pic_id','left')->field('b.`pic_cover`')->where(['a.goods_id'=>$info[0]['goods_id']])->find();
        $info[0]['pic_cover'] = getApiSrc($pic_info['pic_cover']);
        //商品名
        $goods_name = $goods->getInfo(['goods_id'=>$info[0]['goods_id']],'goods_name');
        $info[0]['goods_name'] = $goods_name['goods_name'];
        return $info;
    }

    //获取预售详情  ，根据商品ID
    public function getPresellInfoByGoodsId($goods_id){

        $condition['a.end_time'] = ['EGT', time()];
        $condition['a.goods_id'] = $goods_id;
        $condition['a.active_status'] = 1;
        $condition['a.status'] = ['neq', 3];

        $presell = new VslPresellModel();
        $info = $presell->alias('a')->join('vsl_presell_goods g','a.id=g.presell_id','left')->field('a.*,g.sku_id,g.max_buy,g.presell_id,g.first_money,g.all_money,g.presell_num,g.vr_num')->where($condition)->select();
        return $info;
    }
    //获取正在进行预售详情  ，根据商品ID
    public function getPresellInfoByGoodsIdIng($goods_id){

        $condition['a.start_time'] = ['LT', time()];
        $condition['a.end_time'] = ['EGT', time()];
        $condition['a.goods_id'] = $goods_id;
        $condition['a.active_status'] = 1;
        $condition['a.status'] = 1;

        $presell = new VslPresellModel();
        $info = $presell->alias('a')->join('vsl_presell_goods g','a.id=g.presell_id','left')->field('a.*,g.sku_id,g.max_buy,g.presell_id,g.first_money,g.all_money,g.presell_num,g.vr_num')->where($condition)->select();
//        echo $presell->getLastSql();
        return $info;
    }


    //获取预售信息，规格ID和预售ID
    public function get_presell_by_sku($presell_id,$sku_id){

        $presell = new VslPresellModel();
        $presell_goods = new VslPresellGoodsModel();
        $condition['presell_id'] = $presell_id;
        $condition['sku_id'] = $sku_id;
        //先从sku里面找，没有再从主表找
        $info = $presell_goods->getInfo($condition);
        if(!empty($info)){
            $time = $presell->getInfo(['id'=>$presell_id]);
            $data['maxbuy'] = $info['max_buy'];
            $data['firstmoney'] = $info['first_money'];
            $data['allmoney'] = $info['all_money'];
            $data['presellnum'] = $info['presell_num'];
            $data['vrnum'] = $info['vr_num'];
            $data['shop_id'] = $info['shop_id'];
            $data['pay_start_time'] = $time['pay_start_time'];
            $data['pay_end_time'] = $time['pay_end_time'];
        }else{
            $info = $presell->getInfo(['id'=>$presell_id,'sku_id'=>$sku_id]);
            if($info){
                $data['maxbuy'] = $info['maxbuy'];
                $data['firstmoney'] = $info['firstmoney'];
                $data['allmoney'] = $info['allmoney'];
                $data['presellnum'] = $info['presell_num'];
                $data['vrnum'] = $info['vrnum'];
                $data['shop_id'] = $info['shop_id'];
                $data['pay_start_time'] = $info['pay_start_time'];
                $data['pay_end_time'] = $info['pay_end_time'];
            }
        }
        return $data;
    }

    //获取预售详情---规格
    public function getPresellSkuinfo($condition){

        $presell = new VslPresellGoodsModel();
        $skuinfo = $presell->getInfo($condition);
        return $skuinfo;
    }

    //获取编辑的规格数据
    public function get_sku_info($goods_id,$presell_id){

            $goods = new Goods();
            $skuModel = new VslGoodsSkuModel();
            $goodsModel = new VslGoodsModel();
            $groupGoods = new VslPresellGoodsModel();
            $goods_spec_format = $goodsModel->getInfo(['goods_id' => $goods_id], 'goods_spec_format')['goods_spec_format'];
            $goods_spec_arr = json_decode($goods_spec_format, true);
            $sku = $skuModel->where(['goods_id' => $goods_id])->select();
            if (!empty($sku[0]['attr_value_items'])) {
                foreach ($sku as $sku_key => $sku_value) {
                    $sku_val_item = $sku_value['attr_value_items'];
                    $sku_val_arr = explode(';', $sku_val_item);
                    $th_name_str = '';
                    $show_value_str = '';
                    $show_type_str = '';
                    foreach ($sku_val_arr as $sku_val_key => $sku_val_value) {
                        $sku_val_value_arr = explode(':', $sku_val_value);
                        //按照规格规则中的顺序定义tr头 删掉规格后会导致商品报错不显示规格，所以直接取商品表的goods_spec_format
//                        $sku_tr_id = $sku_val_value_arr[1];
//                        $val_type = $goods->getGoodSku(['spec_value_id' => $sku_tr_id]);
//                        $val_type_arr = $val_type[0]->toArray();
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
                        $show_type = $val_type_arr['goods_spec']['show_type'];
                        //根据show_type，获取规格的值，如图片的路径
                        if ($show_type == '3') {//图片
//                            $pic_id = $val_type_arr['spec_value_data'];
//                            $val_type_str = $goods->getGoodSkuPic(['pic_id' => $pic_id]);
//                            if (empty($val_type_str)) {
//                                $val_type_str = '暂无图片';
//                            }
                            $val_type_str = $val_type_arr['spec_value_name'];//暂时展示中文。
                        } else if ($show_type == '2') {//颜色
                            $val_type_str = $val_type_arr['spec_value_name'];
                        } else {
                            $val_type_str = $val_type_arr['spec_value_name'];
                        }
                        //拼接所有规格展示类型对应的值
                        $show_value_str .= $val_type_str . '§';
                        //拼接th的名字
                        $th_name_str .= $val_type_arr['goods_spec']['spec_name'] . ' ';
                        //拼接展示类型
                        $show_type_str .= $show_type . ' ';
                    }
                    $th_name_str = trim($th_name_str);//spec_name
                    $show_type_str = trim($show_type_str);//展示类型
                    $show_value_str = trim($show_value_str, '§');//spec_value_name
                    $sku_list = $sku_value->toArray();
                    //处理sku的id对应value
                    $sku_id_str = $sku_list['attr_value_items'];
                    $sku_id_str_arr = explode(';', $sku_id_str);
                    $sku_value_str = trim($show_value_str);
                    $sku_value_str_arr = explode('§', $sku_value_str);
                    $im_str = '';
                    $new_im_str = '';
                    for ($i = 0; $i < count($sku_value_str_arr); $i++) {
                        $im_str .= $sku_id_str_arr[$i] . ';';
                        $im_str = trim($im_str, ';');
                        $new_im_str .= $im_str . '=' . $sku_value_str_arr[$i] . '§';
                    }
                    $new_im_str = trim($new_im_str, '§');
                    $sku[$sku_key]['new_im_str'] = $new_im_str;
                    $sku[$sku_key]['th_name_str'] = $th_name_str;
                    $sku[$sku_key]['show_type_str'] = $show_type_str;
                    $groupSku = $groupGoods->getInfo(['sku_id' => $sku_value['sku_id'], 'goods_id' => $goods_id,'presell_id'=>$presell_id], '*');
                    $sku[$sku_key]['max_buy'] = $groupSku['max_buy'];
                    $sku[$sku_key]['first_money'] = $groupSku['first_money'];
                    $sku[$sku_key]['all_money'] = $groupSku['all_money'];
                    $sku[$sku_key]['presell_num'] = $groupSku['presell_num'];
                    $sku[$sku_key]['vr_num'] = $groupSku['vr_num'];
                    $sku[$sku_key]['presell_id'] = $groupSku['presell_id'];
                    $sku[$sku_key]['presell_goods_id'] = $groupSku['presell_goods_id'];
                }
                /*************************当sku规格错乱的时候排序****************************/
                $temp = [];
                foreach($sku as $k1=>$sort_sku){
                    $sort_arr = explode('§',$sort_sku['new_im_str']);
                    $sort_str = $sort_arr[0];
                    $temp[$sort_str][$k1] = $sort_sku;
                }
                $i = 0;
                $sku_temp = [];
                foreach($temp as $k2=>$r){
                    foreach($r as $last_val){
                        $sku_temp[$i] = $last_val;
                        $i++;
                    }
                }
                $sku = $sku_temp;
            } else {
                $sku = $sku[0];
                $groupSku = $groupGoods->getInfo(['sku_id' => $sku['sku_id'], 'goods_id' => $goods_id,'presell_id'=>$presell_id], '*');
                $sku['group_price'] = $groupSku['group_price'];
                $sku['group_limit_buy'] = $groupSku['group_limit_buy'];
                $sku['group_goods_id'] = $groupSku['group_goods_id'];
                $groupSku = $groupGoods->getInfo(['sku_id' => $sku['sku_id'], 'goods_id' => $goods_id,'presell_id'=>$presell_id], '*');
                $sku['max_buy'] = $groupSku['max_buy'];
                $sku['first_money'] = $groupSku['first_money'];
                $sku['all_money'] = $groupSku['all_money'];
                $sku['presell_num'] = $groupSku['presell_num'];
                $sku['vr_num'] = $groupSku['vr_num'];
                $sku['presell_id'] = $groupSku['presell_id'];
                $sku['presell_goods_id'] = $groupSku['presell_goods_id'];
            }
            return $sku;
    }

    //消息列表
    public function get_message_list($page_index, $page_size, $condition, $order)
    {

        $message = new VslPushMessage();
        $list = $message->pageQuery($page_index, $page_size, $condition, '', '');

        return $list;
    }
    /*
     * 获取预售商品是否在活动中
     * **/
    public function getIsInPresell($goods_id)
    {
        $presell_info = $this->getPresellInfoByGoodsId($goods_id);
        if (!empty($presell_info)) {
            //判断状态是进行中还是
            if (time() > $presell_info[0]['start_time'] && time() < $presell_info[0]['end_time']) {//正在活动中
                $is_presell = $presell_info[0];
            } else if (time() < $presell_info[0]['start_time']) {//没开始
                $is_presell = false;
            } else {//结束了
                $is_presell = false;
            }
        }
        return $is_presell;
    }

    /*
     * 预售获取我还能买多少个
     * **/
    public function getMeCanBuy($presell_id, $sku_id)
    {
        $aosr_mdl = new VslActivityOrderSkuRecordModel();
        $presell_goods = new VslPresellGoodsModel();
        $already_num = $aosr_mdl->where(['activity_id' => $presell_id, 'sku_id'=>$sku_id, 'uid'=>$this->uid])->sum('num');
        $presell_sku_info = $presell_goods->getInfo(['presell_id'=>$presell_id, 'sku_id'=>$sku_id]);
        $max_buy = $presell_sku_info['max_buy'];
//        var_dump($max_buy);
        $can_buy = $max_buy - $already_num >= 0 ? $max_buy - $already_num : 0;
        return $can_buy;
    }

}