<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:39
 */

namespace addons\groupshopping\server;

use addons\invoice\server\Invoice as InvoiceService;
use addons\miniprogram\model\WeixinAuthModel;
use data\model\AddonsConfigModel;
use data\model\WebSiteModel;
use data\service\AddonsConfig;
use data\service\BaseService;
use addons\groupshopping\model\VslGroupShoppingModel;
use addons\groupshopping\model\VslGroupGoodsModel;
use addons\groupshopping\model\VslGroupShoppingRecordModel;
use data\model\VslGoodsSkuModel;
use data\service\Goods;
use data\model\UserModel;
use data\model\VslOrderModel;
use data\model\VslOrderGoodsModel;
use data\service\Order as OrderService;
use data\service\User;
use data\service\AddonsConfig as AddonsConfigService;
use data\model\AlbumPictureModel;
use data\model\VslGoodsModel;
use data\model\VslActivityOrderSkuRecordModel;

class GroupShopping extends BaseService {

    public $addons_config_module;

    function __construct() {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * @param array $input
     * @return int
     */
    public function addGroupShopping(array $input) {
        $group_obj = new VslGroupShoppingModel();
        $checkGoods = $group_obj->getInfo(['goods_id' => $input['goods_id'], 'status' => ['<',2], 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
        if ($checkGoods) {
            return -10007;
        }
        $goodsModel = new VslGoodsModel();
        //查询商品是否参加了其他活动或者其他拼团
        $checkGoodsType = $goodsModel->getInfo(['goods_id' => $input['goods_id']],'promotion_type')['promotion_type'];
        if($checkGoodsType){
            return -10007;
        }
        $group_obj->startTrans();
        try {
            $data = array(
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
                'group_name' => $input['group_name'],
                'group_time' => $input['group_time'],
                'group_num' => $input['group_num'],
                'status' => 1,
                'goods_id' => $input['goods_id'],
                'create_time' => time(),
            );
            $res = $group_obj->save($data);
            if($res){
                //更新商品表促销类型为团购
                $goodsModel->save(['promotion_type' => 2],['goods_id' => $input['goods_id'],'website_id' => $this->website_id,'shop_id' => $this->instance_id]);
            }
            $group_id = $group_obj->group_id;

            //处理秒杀对应的商品
            $group_goods_obj = new VslGroupGoodsModel();
            $i = 0;
            $group_goods = array();
            foreach ($input['goods_info'] as $sku_id => $goods) {
                //如果sku_id 等于0 则说明其没有规格sku
                $group_goods[$i]['group_id'] = $group_id;
                $group_goods[$i]['sku_id'] = $sku_id;
                $group_goods[$i]['goods_id'] = $input['goods_id'];
                $group_goods[$i]['group_price'] = $goods['group_price'];
                $group_goods[$i]['group_limit_buy'] = $goods['group_limit_buy'];
                $group_goods[$i]['create_time'] = time();
                $i++;
            }

            $group_goods_obj->saveAll($group_goods);
            $group_obj->commit();
            return $group_id;
        } catch (\Exception $e) {
            $group_obj->rollback();
            return $e->getMessage();
        }
    }
    /**
     * @param array $input
     * @return int
     */
    public function updateGroupShopping(array $input) {
        $group_obj = new VslGroupShoppingModel();
        $group_obj->startTrans();
        $group_id = $input['group_id'];
        if(!$group_id){
            return -1006;
        }
        try {
            $data = array(
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
                'group_name' => $input['group_name'],
                'group_time' => $input['group_time'],
                'group_num' => $input['group_num'],
                'status' => $input['status'],
                'goods_id' => $input['goods_id'],
                'create_time' => time()
            );
            $group_obj->save($data,['group_id' => $group_id]);

            //处理拼团对应的商品
            
            $group_goods = array();
            foreach ($input['goods_info'] as $sku_id => $goods) {
                
                //如果sku_id 等于0 则说明其没有规格sku
                $group_goods['sku_id'] = $sku_id;
                $group_goods['goods_id'] = $input['goods_id'];
                $group_goods['group_price'] = $goods['group_price'];
                $group_goods['group_limit_buy'] = $goods['group_limit_buy'];
                $group_goods['update_time'] = time();
                $group_goods_obj = new VslGroupGoodsModel();
                $group_goods_obj->save($group_goods,['group_goods_id' => $goods['group_goods_id']]);
            }
            $group_obj->commit();
            return $group_id;
        } catch (\Exception $e) {
            $group_obj->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 获取拼团列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $fields
     *
     * @return array $coupon_type_list
     */
    public function groupShoppingList($page_index = 1, $page_size = 0, array $condition = []) {
        $module = \think\Request::instance()->module();
        $groupMdl = new VslGroupShoppingModel();
        $list = $groupMdl->getGroupViewList($page_index, $page_size, $condition, 'ng.create_time desc');
        if (!$list['data']) {
            return $list;
        }
        foreach ($list['data'] as $key => $val) {
            if (!$val['group_id']) {
                continue;
            }
            $list['data'][$key]['tuxedo_situation'] = $this->getSituation($val['group_id']); //参团情况
            if($module !='platform' && $module != 'admin'){
                $list['data'][$key]['user'] = $this->getGroupUser($val['group_id']); //参团情况
                $list['data'][$key]['goods_total'] = $this->getGroupGoodsTotal($val['group_id']); //参团情况
            }
        }
        //判断pc端、小程序是否开启
        $addons_conf = new AddonsConfig();
        $pc_conf = $addons_conf->getAddonsConfig('pcport', $this->website_id);
        $is_minipro = getAddons('miniprogram', $this->website_id);
        if($is_minipro){
            $weixin_auth = new WeixinAuthModel();
            $new_auth_state = $weixin_auth->getInfo(['website_id' => $this->website_id], 'new_auth_state')['new_auth_state'];
            if(isset($new_auth_state) && $new_auth_state == 0){
                $is_minipro = 1;
            }else{
                $is_minipro = 0;
            }
        }
        $website_mdl = new WebSiteModel();
        //查看移动端的状态
        $wap_status = $website_mdl->getInfo(['website_id' => $this->website_id], 'wap_status')['wap_status'];
        $list['addon_status']['wap_status'] = $wap_status;
        $list['addon_status']['is_pc_use'] = $pc_conf['is_use'];
        $list['addon_status']['is_minipro'] = $is_minipro;
        return $list;
    }

    /**
     * 获取拼团记录列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $fields
     *
     * @return array $coupon_type_list
     */
    public function getGroupRecordViewList($page_index = 1, $page_size = 0, array $condition = []) {
        $groupMdl = new VslGroupShoppingRecordModel();
        $list = $groupMdl->getGroupRecordViewList($page_index, $page_size, $condition, 'ng.create_time desc');
        return $list;
    }

    /**
     * 获取拼团详情
     * @param int $group_id
     * @return array $info
     */
    public function groupShoppingDetail($group_id) {
        $groupMdl = new VslGroupShoppingModel();
        $info = $groupMdl->getGroupDetail($group_id);
        return $info;
    }
    /*
     * 获取拼团标签
     */
    public function getGroupName($group_id){
        $groupMdl = new VslGroupShoppingModel();
        $groupDetail = $groupMdl->getInfo(['group_id'=>$group_id],'group_name');
        if(!$groupDetail){
            return '';
        }
        return $groupDetail['group_name'];
    }

    /*
     * 编辑拼团获取sku列表
     */

    public function groupShoppingSku($goods_id, $group_id) {
        $goods = new Goods();
        $skuModel = new VslGoodsSkuModel();
        $groupGoods = new VslGroupGoodsModel();
        $goodsModel = new VslGoodsModel();
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
                    $val_type_str = $val_type_arr['spec_value_name'];
                    //拼接所有规格展示类型对应的值
                    $show_value_str .= $val_type_str . ' ';
                    //拼接th的名字
                    $th_name_str .= $val_type_arr['goods_spec']['spec_name'] . ' ';
                    //拼接展示类型
                    $show_type_str .= $show_type . ' ';
                }
                $th_name_str = trim($th_name_str);
                $show_type_str = trim($show_type_str);
                $show_value_str = trim($show_value_str);
                $sku_list = $sku_value->toArray();
                //处理sku的id对应value
                $sku_id_str = $sku_list['attr_value_items'];
                $sku_id_str_arr = explode(';', $sku_id_str);
                $sku_value_str = trim($show_value_str);
                $sku_value_str_arr = explode(' ', $sku_value_str);
                $im_str = '';
                $new_im_str = '';
                for ($i = 0; $i < count($sku_value_str_arr); $i++) {
                    $im_str .= $sku_id_str_arr[$i] . ';';
                    $im_str = trim($im_str, ';');
                    $new_im_str .= $im_str . '=' . $sku_value_str_arr[$i] . ' ';
                }
                $new_im_str = trim($new_im_str, ' ');
                $sku[$sku_key]['new_im_str'] = $new_im_str;
                $sku[$sku_key]['th_name_str'] = $th_name_str;
                $sku[$sku_key]['show_type_str'] = $show_type_str;
                $groupSku = $groupGoods->getInfo(['sku_id' => $sku_value['sku_id'], 'goods_id' => $goods_id, 'group_id' => $group_id], 'group_price,group_limit_buy,group_goods_id');
				
                $sku[$sku_key]['group_price'] = $groupSku['group_price'];
                $sku[$sku_key]['group_limit_buy'] = $groupSku['group_limit_buy'];
                $sku[$sku_key]['group_goods_id'] = $groupSku['group_goods_id'];
            }
            $temp = [];
            foreach($sku as $k1=>$sort_sku){
                $sort_arr = explode(' ',$sort_sku['new_im_str']);
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
            $groupSku = $groupGoods->getInfo(['sku_id' => $sku[0]['sku_id'], 'goods_id' => $goods_id, 'group_id' => $group_id], 'group_price,group_limit_buy,group_goods_id');
            $sku[0]['group_price'] = $groupSku['group_price'];
            $sku[0]['group_limit_buy'] = $groupSku['group_limit_buy'];
            $sku[0]['group_goods_id'] = $groupSku['group_goods_id'];
        }
        return $sku;
    }

    /*
     * 计算团购参团情况
     */

    public function getSituation($group_id = 0) {
        $situation = array(
            'tuxedo' => 0,
            'group_count' => 0,
            'success_count' => 0
        );
        if (!$group_id) {
            return $situation;
        }
        $groupRecordModel = new VslGroupShoppingRecordModel();
        $orderModel = new VslOrderModel();
        $situation['group_count'] = $groupRecordModel->getCount(['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
        $situation['success_count'] = $groupRecordModel->getCount(['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'status' => 1]);
        $groupRecord = $groupRecordModel->Query(['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id], 'record_id');
        if ($groupRecord) {
            $orders = $orderModel->where(['group_record_id' => ['in', $groupRecord]])->group('buyer_id')->field('order_id')->select();
            $situation['tuxedo'] = count($orders);
        }
        return $situation;
    }

    /*
     * 获取拼团记录详情
     */

    public function groupRecordDetail($record_id = 0) {
        $groupRecordMdl = new VslGroupShoppingRecordModel();
        $info = $groupRecordMdl->getRecordDetail($record_id);
        return $info;
    }

    /*
     * 获取拼团记录详情
     */

    public function getGroupMemberList($page_index, $page_size, $condition, $buyer_id) {
        $orderModel = new VslOrderModel();
        $userModel = new UserModel();
        $orderGoodsModel = new VslOrderGoodsModel();
        $orderList = $orderModel->pageQuery($page_index, $page_size, $condition, 'create_time desc', 'buyer_id,order_id,pay_money,user_platform_money,create_time,order_status');
        if ($orderList['data']) {
            foreach ($orderList['data'] as $key => $val) {
                $orderList['data'][$key]['real_price'] = $val['pay_money'] + $val['user_platform_money'];
                $orderList['data'][$key]['is_head'] = 0;
                if ($val['buyer_id'] == $buyer_id) {
                    $orderList['data'][$key]['is_head'] = 1;
                }
                $orderList['data'][$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
                $userInfo = $userModel->alias('su')
                                ->join('vsl_member vm', 'su.uid = vm.uid', 'left')
                                ->join('vsl_member_level vml', 'vm.member_level = vml.level_id', 'left')
                                ->field('su.user_headimg,su.user_name,su.nick_name,vml.level_name')->where(['su.uid' => $val['buyer_id']])->find();
                $orderList['data'][$key]['buyer'] = $userInfo;
                $orderList['data'][$key]['buyer']['user_headimg'] = __IMG($orderList['data'][$key]['buyer']['user_headimg']);
                $orderGoods = $orderGoodsModel->getInfo(['order_id' => $val['order_id']], 'price,num'); //拼团订单没有购物车，直接查询单条数据
                $orderList['data'][$key]['price'] = $orderGoods['price'];
                $orderList['data'][$key]['num'] = $orderGoods['num'];
            }
        }
        return $orderList;
    }
    /*
     * 移动端支付成功获取拼团记录详情
     */

    public function getGroupMemberListForWap($record_id) {
        $groupRecordMdl = new VslGroupShoppingRecordModel();
        $info = $groupRecordMdl->getInfo(['record_id' => $record_id,'website_id' => $this->website_id],'record_id,group_id,shop_id,website_id,group_num,now_num,status,finish_time,uid,goods_id');
        if(!$info){
            return [];
        }
        $info['self_order_id'] = 0;
        $orderModel = new VslOrderModel();
        $userModel = new UserModel();
        $buyerList = $orderModel->getQuery(['group_record_id' => $record_id,'website_id' => $this->website_id], 'buyer_id,order_id','create_time asc');
        if(!$buyerList){
            $info['buyer_list'] = [];
            return $info;
        }
        foreach ($buyerList as $key => $val) {
            $buyerList[$key]['is_head'] = 0;
            if ($val['buyer_id'] == $info['uid']) {
                $buyerList[$key]['is_head'] = 1;
            }
            if ($val['buyer_id'] ==  $this->uid) {
                $info['self_order_id'] = $val['order_id'];
            }
            $buyerList[$key]['user_headimg'] = $userModel->getInfo(['uid' => $val['buyer_id']],'user_headimg')['user_headimg'];
        }
        $info['buyer_list'] = $buyerList;
        $goodsModel = new VslGoodsModel();
        $goods = $goodsModel->getInfo(['goods_id' => $info['goods_id']],'goods_name,picture,goods_id');
        $picture = new AlbumPictureModel();
        $picture_info = $picture->getInfo(['pic_id' => $goods['picture']],'pic_cover');
        if (empty($picture_info)){
            $picture_info['pic_cover'] = '';
        }
        $goods['pic_cover'] = $picture_info['pic_cover'];
        $goods['group_price'] = 0;
        $goods['price'] = 0;
        $sku_first = $this->groupShoppingSku($info['goods_id'],$info['group_id']);
        if(!$sku_first){
            $info['goods'] = $goods;
            return $info;
        }
		$new_sku = [];
		foreach($sku_first as $key1 => $val1){
			$new_sku[$key1] = $val1;
		}
        $sku_change = $this->list_sort_by($new_sku, 'group_price');
        if(!$sku_change){
            $info['goods'] = $goods;
            return $info;
        }
        $goods['group_price'] = $sku_change[0]['group_price'];
        $goods['price'] = $sku_change[0]['price'];
        $info['goods'] = $goods;
        return $info;
    }

    /*
     * 开启拼团活动
     */

    public function groupShoppingOn($group_id) {
        if (!$group_id) {
            return -1006;
        }
        $groupMdl = new VslGroupShoppingModel();
        $group = $groupMdl->getInfo(['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
        if (!$group) {
            return -10003;
        }
        $retval = $groupMdl->save(['status' => 1, 'update_time' => time()], ['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
        return $retval;
    }

    /*
     * 关闭拼团活动
     */

    public function groupShoppingOff($group_id) {
        if (!$group_id) {
            return -1006;
        }
        $groupMdl = new VslGroupShoppingModel();
        $group = $groupMdl->getInfo(['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
        if (!$group) {
            return -10003;
        }
        $goodsModel = new VslGoodsModel();
        $goodsModel->save(['promotion_type' => 0],['goods_id' => $group['goods_id']]);//商品去除营销状态
        $groupRecordModel = new VslGroupShoppingRecordModel();
        $groupRecordList = $groupRecordModel->getQuery(['group_id' => $group_id, 'status' => 0], ['record_id'], 'create_time desc');
        if (!$groupRecordList) {
            $retval = $groupMdl->save(['status' => 2, 'update_time' => time()], ['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
            return $retval;
        }
        $orderStatus = true;
        foreach ($groupRecordList as $grVal) {
            $result = $this->groupFail($grVal['record_id']);
            if ($result == -1) {
                $orderStatus = false;
            }
        }
        $retval = $groupMdl->save(['status' => 2, 'update_time' => time()], ['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
        if (!$orderStatus) {
            return -10006;
        }
        return $retval;
    }

    /*
     * 删除拼团活动
     */

    public function deleteGroup($group_id) {
        if (!$group_id) {
            return -1006;
        }

        $groupMdl = new VslGroupShoppingModel();
        $group = $groupMdl->getInfo(['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
        if (!$group) {
            return -10003;
        }
        if ($group['status'] != 2) {
            return -10004;
        }
        $retval = $groupMdl->delData(['group_id' => $group_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id]);
        if($retval){
            $groupGoodsModel = new VslGroupGoodsModel();
            $groupGoodsModel->delData(['group_id' => $group_id]);
        }
        return $retval;
    }

    /*
     * 组团失败，处理团购订单
     */

    public function groupFail($group_record_id = 0) {
        $orderModel = new VslOrderModel();
        $skuRecordModel = new VslActivityOrderSkuRecordModel();
        $orderList = $orderModel::all(['group_record_id' => $group_record_id]);
        $orderStatus = 1;
        $groupRecordModel = new VslGroupShoppingRecordModel();
        if (!$orderList) {
            $retval = $groupRecordModel->save(['status' => -1, 'order_status' => $orderStatus, 'update_time' => time()], ['record_id' => $group_record_id]);
            return $retval;
        }
        $orderService = new OrderService();
        foreach ($orderList as $oval) {
            $orderGoodsModel = new VslOrderGoodsModel();
            $ordergoods = $orderGoodsModel->getQuery(['order_id' => $oval['order_id']], 'order_goods_id,sku_id,num','');
            foreach($ordergoods as $val1){
                $skuRecord = $skuRecordModel->getInfo(['buy_type' => 2, 'activity_id' => $oval['group_id'], 'sku_id' => $val1['sku_id'], 'uid' => $oval['buyer_id']],'order_sku_record_id,num');
                $num = ($skuRecord['num'] >= $val1['num']) ? ($skuRecord['num'] - $val1['num']) : 0;
                $skuRecordModel->update(['num' =>$num],['order_sku_record_id' => $skuRecord['order_sku_record_id']]);
            }
            unset($val1);
            if (!$oval['order_status'] || $oval['offline_pay'] == 2) {
                $orderService->orderClose($oval['order_id']);
                continue;
            }
//            $pay_type = $orderModel->getInfo(['order_id'=>$oval['order_id']],'payment_type')['payment_type'];
            $pay_type = $oval['payment_type'];//1微信 2支付宝 3银行卡 4货到付款 5余额支付 10线下支付 16eth支付 17eos支付
            if($pay_type==16 || $pay_type==17){
                $orderModel->save(['order_status'=>-1],['order_id'=>$oval['order_id']]);
                foreach($ordergoods as $val){
                    $orderGoodsModel = new VslOrderGoodsModel();
                    $orderGoodsModel->save(['refund_status'=>4],['order_goods_id'=>$val['order_goods_id']]);
                }
                unset($val);
            }else{
                foreach($ordergoods as $val){
                    $result = $orderService->orderGoodsConfirmRefund($oval['order_id'], $val['order_goods_id']);
                    if ($result['code'] < 0) {
                        $orderStatus = -1;
                    }
                }
                unset($val);
            }

            // 修改发票状态
            if (getAddons('invoice', $this->website_id, $this->instance_id)) {
                $invoice = new InvoiceService();
                $invoice->updateOrderStatusByOrderId($oval['order_id'], 2);//关闭发票状态
            }
        }
        unset($oval);
        $retval = $groupRecordModel->save(['status' => -1, 'order_status' => $orderStatus, 'update_time' => time()], ['record_id' => $group_record_id]);

        if ($orderStatus) {
            return $retval;
        } else {
            return -1;
        }
    }

    /*
     * wap商品详情，拼团列表
     */

    public function goodsGroupRecordListForWap($goods_id = 0,$num = 0) {
        $groupRecordModel = new VslGroupShoppingRecordModel();
        $groupRecordList = $groupRecordModel->pageQuery(1,$num,['goods_id' => $goods_id, 'website_id' => $this->website_id, 'status' => 0 ,'finish_time' =>['>',time()]],'(group_num - now_num) asc, finish_time asc', 'uid,group_num,now_num,finish_time,record_id');
        $rebuidList = array();//重新组装数组
        if($groupRecordList['data']){
            foreach($groupRecordList['data'] as $key => $val){
                $rebuidList[$key]['group_num'] = $val['group_num'];
                $rebuidList[$key]['record_id'] = $val['record_id'];
                $rebuidList[$key]['now_num'] = $val['now_num'];
                $rebuidList[$key]['finish_time'] = $val['finish_time'];
                $user = new User();
                $userInfo = $user ->getUserInfoByUid($val['uid']);
                $rebuidList[$key]['user_name'] = $userInfo['nick_name'] ? $userInfo['nick_name'] : $userInfo['user_name'];
                $rebuidList[$key]['user_headimg'] = __IMG($userInfo['user_headimg']);
            }
        }
        return $rebuidList;
    }
    /*
     * wap商品详情，有多少人在参与该商品拼单
     */

    public function goodsGroupRecordCount($goods_id = 0) {
        $groupRecordModel = new VslGroupShoppingRecordModel();
        $groupRecordCount = $groupRecordModel ->getSum(['goods_id' => $goods_id, 'website_id' => $this->website_id, 'status' => 0 ,'finish_time' =>['>',time()]],'now_num');
        return $groupRecordCount;
    }

    /*
     * wap商品详情，已有多少人成团
     */

    public function goodsRegimentCount($goods_id = 0) {
        $model = new VslGroupShoppingRecordModel();
        $groupRegimentCount = $model ->getFieldSum(['goods_id' => $goods_id, 'website_id' => $this->website_id, 'status' => 1 ,'finish_time' =>['<',time()]],'sum(now_num) as now_num, sum(group_num) as group_num');
        return $groupRegimentCount;
    }
    
    /*
     * 判断当前商品是否为拼团商品
     * **/
    public function isGroupGoods($goods_id = 0)
    {
        $groupMdl = new VslGroupShoppingModel();
        $group = $groupMdl->isGroupGoods($goods_id);
        //判断秒杀活动是否开启
        $addonsConfServer = new AddonsConfigService();
        $addonsGroupInfo = $addonsConfServer->getAddonsConfig('groupshopping', $this->website_id);
        if(!$addonsGroupInfo['is_use']){//等于0就是未启动
            return false;
        }
        if(!$group){
            return false;
        }
        return $group;
    }
    
    /*
     * 参团时判断团购是否可以参加
     */
    public function checkGroupIsCan($record_id = 0, $uid = 0){
        if(!$record_id){
            return -1006;
        }
        $groupRecordModel = new VslGroupShoppingRecordModel();
        $record = $groupRecordModel->getInfo(['record_id' => $record_id,'website_id' => $this->website_id]);
        if(!$record){
            return -10003;
        }
        if($record['status'] == 1 || $record['now_num'] == $record['group_num']){
            return -10008;
        }
        if($record['status'] == -1){
            return -10009;
        }
        if($uid){
          $orderModel = new VslOrderModel();
          $order = $orderModel->getInfo(['group_record_id' => $record_id,'buyer_id' => $uid,'website_id' => $this->website_id, 'shop_id' => $record['shop_id'], 'order_status' => ['<',5]],['order_id']);
          if($order){
              return -10009;
          }
        }
        return true;
    }
    /*
     * 获取拼团sku的信息
     * **/
    public function getGroupSkuInfo($condition)
    {
        $groupGoodsModel = new VslGroupGoodsModel();
        $groupSkuInfo = $groupGoodsModel->getGroupSkuInfo($condition);
        return $groupSkuInfo;
    }
    
    /*
     * 付款判断团购是否可以参加
     */
    public function checkGroupIsCanByOrder($out_trade_no){
        $orderModel = new VslOrderModel();
        $orderInfo = $orderModel->getInfo(['out_trade_no' => $out_trade_no,'website_id' => $this->website_id],'group_record_id,group_id,order_id, buyer_id');
        if(!$orderInfo['group_id']){
            return true;
        }
        $group_id = $orderInfo['group_id'];
        $groupModel = new VslGroupShoppingModel();
        $group = $groupModel->getInfo(['group_id' => $group_id, 'website_id' => $this->website_id]);
        if($group['status'] != 1){
            return -10009;//团购结束
        }
        if(!$orderInfo['group_record_id']){
            return true;
        }
        $record_id = $orderInfo['group_record_id'];
        $groupRecordModel = new VslGroupShoppingRecordModel();
        $record = $groupRecordModel->getInfo(['record_id' => $record_id,'website_id' => $this->website_id]);
        if(!$record){
            return -10003;
        }
        if($record['status'] == 1 || $record['now_num'] == $record['group_num']){
            return -10008;
        }
        if($record['status'] == -1){
            return -10009;
        }
        //判断限购
        $skuRecordModel = new VslActivityOrderSkuRecordModel();
        $orderGoodsModel = new VslOrderGoodsModel();
        $canBuy = 1;
        $ordergoods = $orderGoodsModel->getQuery(['order_id' => $orderInfo['order_id']], 'order_goods_id,sku_id','');
        foreach($ordergoods as $val1){
            $skuRecord = $skuRecordModel->getInfo(['buy_type' => 2, 'activity_id' => $orderInfo['group_id'], 'sku_id' => $val1['sku_id'], 'uid' => $orderInfo['buyer_id']],'order_sku_record_id,num');
            $skuRecordModel->update(['num' =>$skuRecord['num'] - 1],['order_sku_record_id' => $skuRecord['order_sku_record_id']]);
        }
        return true;
    }
    
    /*
     * 获取已支付未成团的订单
     * **/
    public function getPayedUnGroupOrder()
    {
        $groupRecordModel = new VslGroupShoppingRecordModel();
        $orderList = $groupRecordModel->getPayedUnGroupOrder($this->instance_id,$this->website_id);
        return $orderList;
    }
    
    /*
     * 创建团购记录
     * **/
    public function createGroupRecord($order_id = 0)
    {
        $orderModel = new VslOrderModel();
        $orderInfo = $orderModel->getInfo(['order_id' => $order_id, 'website_id' => $this->website_id],'group_id,group_record_id,buyer_id,shop_id');
        if(!$orderInfo){
            return;
        }
        if(!$orderInfo['group_id']){//非拼团订单，不创建
            return;
        }
        $groupRecordModel = new VslGroupShoppingRecordModel();
        $groupId = $orderInfo['group_id'];
        $groupRecordId = $orderInfo['group_record_id'];
        if($groupRecordId){//有记录id，说明是参加已发起的团
            $groupRecord = $groupRecordModel->getInfo(['record_id' => $groupRecordId,'website_id' => $this->website_id, 'shop_id' => $orderInfo['shop_id']]);
            if(!$groupRecord || $groupRecord['status']==1){
                return;
            }
            $orderCount = $orderModel->getCount(['website_id' => $this->website_id, 'shop_id' => $orderInfo['shop_id'], 'group_record_id' => $groupRecord['record_id'],'order_id' => ['<>',$order_id]]);
            if($orderCount != $groupRecord['now_num']){
                $groupRecord['now_num'] = $orderCount;
            }
            $need = $groupRecord['group_num'] - $groupRecord['now_num'];
            if($need <= 0){
                $groupRecordModel->save(['status' => 1,'update_time' => time()],['record_id' => $groupRecord['record_id']]);
                return;
            }elseif($need == 1){
                $groupRecordModel->save(['now_num' => $groupRecord['now_num'] + 1,'status' => 1,'update_time' => time()],['record_id' => $groupRecord['record_id']]);
                return;
            }else{
                $groupRecordModel->save(['now_num' => $groupRecord['now_num'] + 1,'update_time' => time()],['record_id' => $groupRecord['record_id']]);
                return;
            }
        }else{
            $groupModel = new VslGroupShoppingModel();
            $group = $groupModel->getInfo(['group_id' => $groupId,'website_id' => $this->website_id, 'shop_id' => $orderInfo['shop_id']]);
            if(!$group){
                return;
            }
            $record_id = $groupRecordModel->save([
                'group_id' => $groupId,
                'record_no' => $this->createGroupNo(),
                'shop_id' => $orderInfo['shop_id'],
                'website_id' => $this->website_id,
                'group_num' => $group['group_num'],
                'now_num' => 1,
                'group_time' => $group['group_time'],
                'uid' => $orderInfo['buyer_id'],
                'goods_id' => $group['goods_id'],
                'create_time' => time(),
                'finish_time' => time() + ($group['group_time']*60)
            ]);
            $orderModel->save(['group_record_id' => $record_id], ['order_id' => $order_id]);
            return;
        }
    }
    /*
     * 创建团购记录编号
     */
    public function createGroupNo()
    {
        $billno = date('YmdHis') . mt_rand(100000, 999999);
        while (1) {
            $recordModel = new VslGroupShoppingRecordModel();
            $count = $recordModel->getCount(['record_no'=>$billno]);
            if ($count <= 0) {
                break;
            }
            $billno = date('YmdHis') . mt_rand(100000, 999999);
        }
        return $billno;
    }
    
    /*
     * 参团人员
     */
    public function getGroupUser($group_id){
        $recordModel = new VslGroupShoppingRecordModel();
        $uidList = $recordModel->getQuery(['group_id' => $group_id,'website_id' => $this->website_id],'uid','create_time desc');
        if(!$uidList){
            return [];
        }
        $userModel = new UserModel();
        foreach($uidList as $key => $val){
            $uidList[$key]['user_img'] = $userModel->getInfo(['uid' => $val['uid']],'user_headimg')['user_headimg'];
        }
        return $uidList;
    }
    
    /*
     * 已拼件数
     */
    public function getGroupGoodsTotal($group_id){
        $orderModel = new VslOrderModel();
        $orderGoodsModel = new VslOrderGoodsModel();
        $orderList = $orderModel->Query(['group_id' => $group_id,'website_id' => $this->website_id,'shop_id' => $this->instance_id], 'order_id');
        $total = 0;
        if(!$orderList){
            return $total;
        }
        foreach($orderList as $val){
            $total += $orderGoodsModel->getSum(['order_id' => $val], 'num');
        }
        return $total;
    }
    public function list_sort_by($list, $field, $sortby = 'asc') {

        if (is_array($list)) {
            $refer = $resultSet = array();
            foreach ($list as $i => $data)
                $refer[$i] = &$data[$field];
            switch ($sortby) {
                case 'asc': // 正向排序
                    asort($refer);
                    break;
                case 'desc':// 逆向排序
                    arsort($refer);
                    break;
                case 'nat': // 自然排序
                    natcasesort($refer);
                    break;
            }
            foreach ($refer as $key => $val)
                $resultSet[] = &$list[$key];
            return $resultSet;
        }
        return false;
    }
    
    /*
     * 判断团购的状态，是否能够关闭应用
     */
    public function checkGroupStatus(){
        $groupMdl = new VslGroupShoppingModel();
        $groupListOpen = $groupMdl->getQuery(['website_id' => $this->website_id,'status' => 1], 'group_id', '');
        if(!$groupListOpen){
            return false;
        }
        return true;
    }
}
