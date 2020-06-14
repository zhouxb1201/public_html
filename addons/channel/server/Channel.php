<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/25 0025
 * Time: 11:30
 */

namespace addons\channel\server;
use addons\channel\model\VslChannelBonusModel;
use addons\channel\model\VslChannelCartModel;
use addons\channel\model\VslChannelGoodsModel;
use addons\channel\model\VslChannelGoodsSkuModel;
use addons\channel\model\VslChannelModel;
use addons\channel\model\VslChannelOrderActionModel;
use addons\channel\model\VslChannelOrderGoodsModel;
use addons\channel\model\VslChannelOrderModel;
use addons\channel\model\VslChannelOrderSkuRecordModel;
use addons\distribution\service\Distributor;
use addons\shop\model\VslShopModel;
use data\model\DistrictModel;
use data\model\VslGoodsSpecModel;
use data\model\VslGoodsSpecValueModel;
use data\model\VslMemberAccountModel;
use data\model\VslMemberAccountRecordsModel;
use data\model\VslOrderActionModel as VslOrderActionModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderModel;
use data\service\ShopAccount;
use data\model\UserModel;
use data\model\VslGoodsModel;
use addons\channel\model\VslChannelLevelModel;
use data\model\VslGoodsSkuModel;
use data\model\VslMemberModel;
use data\service\BaseService;
use data\model\AddonsConfigModel;
use data\model\ConfigModel;
use data\model\AlbumPictureModel;
use data\service\Config as ConfigService;
use data\service\Goods;
use data\service\Member\MemberAccount;
use data\service\Order\OrderGoods;
use data\service\UnifyPay;
use data\service\Order\OrderStatus;
use data\service\Order\Order;
use data\service\GoodsCalculate\GoodsCalculate;
use data\service\AddonsConfig as AddonsConfigService;
use addons\customform\server\Custom as CustomServer;
use \data\service\Order as OrderService;
use think\Db;
use think\Log;

class Channel extends BaseService
{
    protected $order = '';
    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
        $this->config_module = new ConfigModel();
        $this->order = new VslChannelOrderModel();
        $this->order_goods = new VslChannelOrderGoodsModel();
    }
    /*
     * 添加渠道商设置
     * **/
    public function addChannelConfig($post_data, $is_use)
    {
        $website_id = $this->website_id;
        //看设置是否存在数据中
        $is_channel_setting = $this->addons_config_module->where(['addons'=>'channel', 'website_id'=>$website_id])->find();
        if($is_channel_setting){
            $config_data['value'] = json_encode($post_data);
            $config_data['is_use'] = $is_use;
            $config_data['modify_time'] = time();
            $retval = $this->addons_config_module->save($config_data,['addons'=>'channel', 'website_id'=>$website_id]);
        }else{
            $config_data['value'] = json_encode($post_data);
            $config_data['desc'] = '渠道商设置';
            $config_data['is_use'] = $is_use;
            $config_data['create_time'] = time();
            $config_data['addons'] = 'channel';
            $config_data['website_id'] = $website_id;
            $retval = $this->addons_config_module->save($config_data);
        }
        return $retval;

    }
    /*
     * 得到渠道商设置信息
     * **/
    public function getChannelConfig($website_id = 0)
    {
        if(!$website_id){
            $website_id = $this->website_id;
        }
        $channel_setting = $this->addons_config_module->where(['addons'=>'channel', 'website_id'=>$website_id])->find();
        $channel_setting_arr = objToArr($channel_setting);
        $return_data = json_decode($channel_setting_arr['value'],true);
        $return_data['is_use'] = $channel_setting_arr['is_use'];
        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id'=>$return_data['condition']['goods_id']],'picture,goods_name');
        $pic_id = $goods_info['picture'];
        $pic = new AlbumPictureModel();
        $return_data['condition']['pic'] = $pic->getInfo(['pic_id'=>$pic_id],'pic_cover_mid')['pic_cover_mid'];
        $return_data['condition']['goods_name'] = $goods_info['goods_name'];
        return $return_data;
    }
    /*
     * 得到渠道商升级条件购买的商品信息
     * **/
    public function getChannelGoodsInfo($goods_id)
    {
        $goods = new VslGoodsModel();
        $goods_info = $goods->getInfo(['goods_id'=>$goods_id],'picture,goods_name');
        $pic_id = $goods_info['picture'];
        $pic = new AlbumPictureModel();
        $pic_url = $pic->getInfo(['pic_id'=>$pic_id],'pic_cover_mid')['pic_cover_mid'];
        return [
            'goods_name' => $goods_info['goods_name'],
            'pic_url' => $pic_url,
        ];
    }
    /*
     * 删除渠道商等级
     * **/
    public function deletaChannelGrade($channel_grade_id)
    {
        $channel_grade_mdl = new VslChannelLevelModel();
        $res = $channel_grade_mdl->where(['channel_grade_id'=>$channel_grade_id])->delete();
        return $res;
    }
    /**
     * 渠道商申请协议设置
     */
    public function setAgreementSite($logo,$content)
    {
        $ConfigService = new ConfigService();
        $value = array(
            'website_id' => $this->website_id,
            'logo' => $logo,
            'content' => $content
        );
        $agreement_info = $this->getAgreementSite($this->website_id);
        if (! empty($agreement_info)) {
            $data = array(
                "value" => json_encode($value, JSON_UNESCAPED_SLASHES ),
            );
            $res = $this->config_module->save($data, [
                "instance_id" => 0,
                "website_id" => $this->website_id,
                "key" => "CHANNEL"
            ]);
        } else {
            $res = $ConfigService->addConfig(0, "CHANNEL", $value, "渠道商申请协议", 1);
        }
        return $res;
    }
    /*
      * 获取渠道商申请协议
      */
    public function getAgreementSite($website_id){
        $config = new ConfigService();
        $globalbonus = $config->getConfig(0,"CHANNEL");
        $globalbonus_info = json_decode($globalbonus['value'], true);
        return $globalbonus_info;
    }
    /*
     * 添加渠道商等级
     * **/
    public function addChannelGrade($post_data)
    {
        $channel_grade_model = new VslChannelLevelModel();
        if($post_data['channel_grade_id']){
            $retval = $channel_grade_model->save($post_data, ['channel_grade_id'=>$post_data['channel_grade_id']]);
        }else{
            $retval = $channel_grade_model->save($post_data);
        }
        return $retval;
    }
    /*
     * 得到渠道商等级列表数据
     * **/
    public function getChannelGradeData($page_index=1, $page_size, $condition = '', $order = '')
    {
        $channel_grade_model = new VslChannelLevelModel();
        //获取总数
        $count = $channel_grade_model->where($condition)->count();
        //获取共多少页
        $page_count = ceil($count/$page_size);
        //获取页数偏移量
        $page_offset = ($page_index-1)*$page_size;
        $all_channel_grade_list = $channel_grade_model->where($condition)->limit($page_offset, $page_size)->order($order)->select();
        return [
            'data' => objToArr($all_channel_grade_list),
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }
    /*
     * 根据id获取渠道商等级信息 -编辑
     * **/
    public function getChannelGradeById($channel_grade_id)
    {
        $channel_grade_model = new VslChannelLevelModel();
        $all_channel_grade_list = $channel_grade_model->where(['channel_grade_id'=>$channel_grade_id])->find();
        return objToArr($all_channel_grade_list);
    }
    /**
     * 获取渠道商等级权重
     */
    public function getChannelGradeWeight()
    {
        $channel_grade_model = new VslChannelLevelModel();
        $list = $channel_grade_model->Query(['website_id' => $this->website_id],'weight');
        return $list;
    }
    /*
     * 获取渠道商信息
     * **/
    public function getChannelList($page_index=1, $page_size, $condition, $order)
    {
        $channel_mdl = new VslChannelModel();
        if(!empty($condition['u.user_name'])){
            $name = $condition['u.user_name'];
            unset($condition['u.user_name']);
            $condition1['u.user_name'] = ['like','%'.$name.'%'];
            $condition1['u.nick_name'] = ['like', '%'.$name.'%'];
        }
        else{
            $condition1['u.user_name'] = ['like','%'.''.'%'];
            $condition1['u.nick_name'] = ['like', '%'.''.'%'];
        }
        $page_offset = ($page_index-1)*$page_size;
        $count = $channel_mdl->alias('c')
            ->join('sys_user u','c.uid=u.uid','LEFT')
            ->where($condition)
            ->where(function ($q) use($condition1) {
                $q->whereOr($condition1);
            })
            ->count();
        //页数
        $page_count = ceil($count/$page_size);
        $channel_list = $channel_mdl->alias('c')
            ->join('sys_user u','c.uid=u.uid','LEFT')
            ->join('vsl_channel_level cg','c.channel_grade_id=cg.channel_grade_id','LEFT')
            ->where($condition)
            ->where(function ($q) use($condition1) {
                    $q->whereOr($condition1);
                })
            ->limit($page_offset,$page_size)
            ->order($order)
            ->select();
        return [
            'data' => objToArr($channel_list),
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }
    /*
     * 获取渠道商商品信息
     * **/
    public function getPurchaseGoodsSkuInfo($page_index=1, $page_size, $condition, $order, $purchase_discount = 1){
        $website_id = $this->website_id;
        $goods_mdl = new VslGoodsModel();
        $channel_goods_mdl = new VslChannelGoodsModel();
        $count = $channel_goods_mdl->alias('cg')
            ->field('cg.goods_id,cgs.sku_id')
            ->join('vsl_channel_goods_sku cgs','cg.goods_id = cgs.goods_id','LEFT')
            ->where($condition)
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $goods_sku_list = $channel_goods_mdl->alias('cg')
            ->field('cg.goods_name,cgs.sku_name,cg.goods_id,cgs.sku_id,cgs.stock,cgs.price,cgs.sku_sales,ap.pic_cover')
            ->join('vsl_channel_goods_sku cgs','cg.goods_id = cgs.goods_id','LEFT')
            ->join('sys_album_picture ap','cg.picture = ap.pic_id')
            ->where($condition)
            ->limit($offset,$page_size)
            ->order($order)
            ->select();
        $goods_sku_arr = objToArr($goods_sku_list);
        //获取商品sku在平台价、库存、出货量
        $goods_condition['g.website_id'] = $website_id;
        foreach($goods_sku_arr as $k=>$gs){
            $sku_id = $gs['sku_id'];
            $goods_condition['gs.sku_id'] = $sku_id;
            $goods_list = $goods_mdl->alias('g')->field('gs.price,gs.stock')->join('vsl_goods_sku gs', 'g.goods_id=gs.goods_id')->where($goods_condition)->find();
            $channel_id = $condition['cg.channel_id'];
            $goods_sku_arr[$k]['platform_price'] = $goods_list['price'];
            $goods_sku_arr[$k]['price'] = $goods_list['price'] * $purchase_discount;
            $goods_sku_arr[$k]['platform_stock'] = $goods_list['stock'];
            $goods_sku_arr[$k]['channel_id'] = $channel_id;
            $goods_sku_arr[$k]['pic_cover'] = getApiSrc($gs['pic_cover']);
            //查询出累计进货
            $og_record = new VslChannelOrderSkuRecordModel();
            $purchase_num = $og_record->where(['my_channel_id' => $channel_id, 'buy_type' => 1, 'sku_id' => $sku_id, 'website_id' => $website_id])->sum('total_num');
            $goods_sku_arr[$k]['purchase_num'] = $purchase_num;
            //统计出货量
//            var_dump($purchase_num);exit;
        }
        return [
            'code' => 0,
            'data' =>[
                'data' => $goods_sku_arr,
                'total_count' => $count,
                'page_count' => $page_count
            ]
        ];
//        echo '<pre>';print_r($goods_sku_arr);exit;
    }
    /*
     * 具体的商品信息
     * **/
    public function getPurchaseRecordGoodsList($page_index=1, $page_size, $condition, $order, $channel_id, $tag_status=0, $is_purchase = 0)
    {
        $cos_record_mdl = new VslChannelOrderSkuRecordModel();
        $cg_mdl = new VslChannelGoodsModel();
        $channel_mdl = new VslChannelModel();
        $count = $cos_record_mdl->where($condition)->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $cos_record_list = $cos_record_mdl
            ->where($condition)
            ->limit($offset,$page_size)
            ->order($order)
            ->select();
        $cos_record_arr = objToArr($cos_record_list);
        $website_id = $this->website_id;
        if($is_purchase){
            $channel_condition['channel_id'] = $channel_id;
            $my_channel_info = $this->getMyChannelInfo($channel_condition);
            $purchase_discount = $my_channel_info['purchase_discount'];
        }
        //获取我的渠道商信息
        $condition['cg.website_id'] = $website_id;
        $condition1['cg.channel_id'] = $channel_id;
        $condition1['cgs.channel_id'] = $channel_id;
        foreach($cos_record_arr as $k=>$g_info){
            $condition1['cgs.sku_id'] = $g_info['sku_id'];
            $condition1['cg.goods_id'] = $g_info['goods_id'];
            $cg_list = $cg_mdl->alias('cg')->field('cg.goods_name,cgs.sku_name,ap.pic_cover,cgs.price')->join('vsl_channel_goods_sku cgs','cg.goods_id=cgs.goods_id')->join('sys_album_picture ap','cg.picture=ap.pic_id')->where($condition1)->find();
            $cos_record_arr[$k]['goods_name'] = $cg_list['goods_name'];
            $cos_record_arr[$k]['sku_name'] = $cg_list['sku_name'];
            $cos_record_arr[$k]['pic_cover'] = $cg_list['pic_cover'];
            $cos_record_arr[$k]['create_time_date'] = date('Y-m-d H:i:s',$g_info['create_time']);
            $cos_record_arr[$k]['pic_cover'] = getApiSrc($cg_list['pic_cover']);
            if($is_purchase == 2 || $is_purchase == 1){//自提和出货里面的采购单价
                $cos_record_arr[$k]['purchase_price'] = $g_info['platform_price']*$purchase_discount;
            }
            if($is_purchase == 3){//拿零售单价、采购单价
                $cos_record_arr[$k]['retail_price'] = $g_info['price'];
                $cos_record_arr[$k]['purchase_price'] = $g_info['platform_price']*$purchase_discount;
                //判断是否是预售的订单
                $order_mdl = new VslOrderModel();
                $is_presell_order = $order_mdl->getInfo(['order_id' => $g_info['order_id']], 'presell_id,money_type,final_money,order_money, pay_status');
                if(!empty($is_presell_order['presell_id'])){
                    if ($is_presell_order['money_type'] == 0) {
                        $cos_record_arr[$k]['real_money'] = 0;
                    } elseif ($is_presell_order['money_type'] == 1) {
                        $cos_record_arr[$k]['real_money'] = $is_presell_order['order_money'];
                    }elseif ($is_presell_order['money_type'] == 2) {
                        $cos_record_arr[$k]['real_money'] = $is_presell_order['order_money'] + $is_presell_order['final_money'];
                    }
                }else{
                    if($is_presell_order['pay_status'] == 0){
                        $cos_record_arr[$k]['real_money'] = 0;
                    }
                }
            }
            //采购来源
            $channel_info = $g_info['channel_info'];
            $cos_record_arr[$k]['tag_status'] = $tag_status;
            if($channel_info== 'platform'){
                $cos_record_arr[$k]['purchase_from'] = '总店';
            }else{
                $channel_condition['channel_id'] = $channel_info;
                $channel_condition['c.website_id'] = $website_id;
                $channel_info = $channel_mdl->alias('c')->field('u.user_name,u.nick_name,u.user_tel')->join('sys_user u', 'c.uid=u.uid')->where($channel_condition)->find();
                $cos_record_arr[$k]['purchase_from'] = $channel_info['nick_name']?:($channel_info['user_name']?:$channel_info['user_tel']);
//                var_dump(objToArr($channel_info));exit;
            }
        }
        return [
            'code'=>0,
            'data' => [
                'channel_goods_info'=> $cos_record_arr,
                'total_count' => $count,
                'page_count' => $page_count
            ]
        ];
//        echo '<pre>';print_r($cos_record_arr);exit;
    }

    public function getCloudDetail($page_index=1, $page_size, $condition, $order, $channel_id, $tag_status=0, $is_purchase = 0)
    {
        $cos_record_mdl = new VslChannelOrderSkuRecordModel();
        $cg_mdl = new VslChannelGoodsModel();
        $channel_mdl = new VslChannelModel();
        $or_condition['channel_info'] = $channel_id;
        $or_condition['website_id'] = $this->website_id;
        $count = $cos_record_mdl
            ->where($condition)
            ->whereOr(function ($query) use($or_condition) {
                $query->where($or_condition);
            })
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $cos_record_list = $cos_record_mdl
            ->where($condition)
            ->whereOr(function ($query) use($or_condition) {
                $query->where($or_condition);
            })
            ->limit($offset,$page_size)
            ->order($order)
            ->select();
        $cos_record_arr = objToArr($cos_record_list);
        //获取我的渠道商信息
        $website_id = $this->website_id;
        $condition['cg.website_id'] = $website_id;
        $condition1['cg.channel_id'] = $channel_id;
        $condition1['cgs.channel_id'] = $channel_id;
        foreach($cos_record_arr as $k=>$g_info){
            $condition1['cgs.sku_id'] = $g_info['sku_id'];
            $condition1['cg.goods_id'] = $g_info['goods_id'];
            $cg_list = $cg_mdl->alias('cg')->field('cg.goods_name,cgs.sku_name,ap.pic_cover,cgs.price')->join('vsl_channel_goods_sku cgs','cg.goods_id=cgs.goods_id')->join('sys_album_picture ap','cg.picture=ap.pic_id')->where($condition1)->find();
            $cos_record_arr[$k]['goods_name'] = $cg_list['goods_name'];
            $cos_record_arr[$k]['sku_name'] = $cg_list['sku_name'];
            $cos_record_arr[$k]['pic_cover'] = $cg_list['pic_cover'];
            $cos_record_arr[$k]['create_time_date'] = date('Y-m-d H:i:s',$g_info['create_time']);
            $cos_record_arr[$k]['pic_cover'] = getApiSrc($cg_list['pic_cover']);
            //采购
            if($g_info['buy_type'] == 1 && $g_info['channel_info'] != $channel_id){
                $cos_record_arr[$k]['buy_type'] = 1;
            }
            //自提
            if($g_info['buy_type'] == 2){
                $cos_record_arr[$k]['buy_type'] = 2;
            }
            //出货
            if($g_info['buy_type'] == 1 && $g_info['channel_info'] == $channel_id){
                $cos_record_arr[$k]['buy_type'] = 3;
            }
            //零售
            if($g_info['buy_type'] == 3){
                $cos_record_arr[$k]['buy_type'] = 4;
            }
        }
        return [
            'code'=>0,
            'data' => [
                'channel_goods_info'=> $cos_record_arr,
                'total_count' => $count,
                'page_count' => $page_count
            ]
        ];
//        echo '<pre>';print_r($cos_record_arr);exit;
    }

    /*
     * 只获取渠道商等级名字、id
     * **/
    public function getchannelGradeList($condition='')
    {
        $channel_grade_mdl = new VslChannelLevelModel();
        $channel_grade_list = $channel_grade_mdl->where($condition)->field('channel_grade_id,channel_grade_name,weight')->order('weight')->select();
        return $channel_grade_list;
    }
    /*
     * 审核渠道商
     * **/
    public function changeChannelStatus($channel_id,$status){
        $channel_mdl = new VslChannelModel();
        $retval = $channel_mdl->save(['status'=>$status],['channel_id'=>$channel_id]);
        return $retval;
    }
    /*
     * 查询出渠道商各个状态的数目
     * **/
    public function getChannelStatusCount($condition = [])
    {
        $condition['website_id'] = $this->website_id;
        $channel_mdl = new VslChannelModel();
       $channel_count = $channel_mdl->where($condition)->count();
       return $channel_count;
    }
    /*
     * 判断渠道商等级是否存在
     * **/
    public function isChannelGrade($params)
    {
        $condition['website_id'] = $params['website_id'];
        $condition['channel_grade_name'] = $params['channel_grade_name'];
        $channel_level_mdl = new VslChannelLevelModel();
        $is_channel_level = $channel_level_mdl->where($condition)->find();
        return $is_channel_level;
    }
    /*
     * 获取渠道商的信息
     * **/
    public function getChannelDetail($condition)
    {
        //获取基础信息
        $condition['c.status'] = 1;
        $channel_mdl = new VslChannelModel();
        $user_mdl = new UserModel();
        $channel_info = $channel_mdl->alias('c')
            ->field('c.uid, c.channel_id, u.user_name, u.nick_name, u.user_tel, u.user_headimg, c.channel_phone, c.channel_real_name, c.status, c.to_channel_time, cl.channel_grade_id, cl.channel_grade_name,cl.weight, c.create_time, m.referee_id, cl.flat_first, cl.flat_second, cl.flat_third, cl.cross_level')
            ->where($condition)
            ->join('sys_user u','c.uid=u.uid','LEFT')
            ->join('vsl_channel_level cl', 'c.channel_grade_id=cl.channel_grade_id', 'LEFT')
            ->join('vsl_member m', 'm.uid=u.uid', 'LEFT')
            ->find();
        //获取渠道商的分销商推荐人
        $referee_id = $channel_info['referee_id'];
        $referee_info = $user_mdl->field('user_name,nick_name')->where(['uid'=>$referee_id])->find();
        if(!empty($referee_info['nick_name'])){
            $channel_info['referee_name'] = $referee_info['nick_name'];
        }else{
            $channel_info['referee_name'] = $referee_info['user_name'];
        }
        $website_id = $this->website_id;
        //采购订单数 得到我的uid
        $condition1['buyer_id'] = $channel_info['uid'];
        $condition1['website_id'] = $website_id;
        $condition1['buy_type'] = 1;
        $purchase_list = $this->getPurchaseOrderNum($condition1);
        $channel_info['purchase_num'] = $purchase_list['purchase_num'];
        //累计采购金额
        $channel_info['my_purchase_money'] = $purchase_list['purchase_money'];
        //累计利润,谁采购了我的
        $condition2['channel_info'] = $channel_info['channel_id'];
        $condition2['website_id'] = $website_id;
        $condition2['buy_type'] = 1;
        $my_profit = $this->getPurchaseOrderGoodsList($condition2);
        $channel_info['my_profit'] = $my_profit;
        //累计奖金
        $condition3['uid'] = $channel_info['uid'];
        $condition3['my_channel_weight'] = $channel_info['weight'];
        $my_bonus = $this->getMyBonus($condition3);
        $channel_info['my_bonus'] = $my_bonus['total_bonus'];
        $channel_info['user_headimg'] = getApiSrc($channel_info['user_headimg']);
        $channel_info['to_channel_timestamp'] = $channel_info['to_channel_time'];
        $channel_info['to_channel_time'] = date('Y-m-d H:i:s', $channel_info['to_channel_time']);
        $channel_info['channel_phone'] = $channel_info['channel_phone']?:$channel_info['user_tel'];
        return $channel_info;
    }
    /*
     * 添加渠道商详情
     * **/
    public function updateChannelInfo($condition, $res)
    {
        $channel_mdl = new VslChannelModel();
        $bool = $channel_mdl->where($condition)->update($res);
        return $bool;
    }
    /*
     * 获取我下面的所有渠道商id 直属下级（沟通就是一级）
     * **/
    public function getAllDownChannelId($condition)
    {
        $member_mdl = new VslMemberModel();
        $channel_list = $member_mdl->alias('m')
            ->field('c.channel_id')
            ->join('vsl_channel c','m.uid=c.uid','LEFT')
            ->join('vsl_channel_level cl','c.channel_grade_id=cl.channel_grade_id','LEFT')
            ->where($condition)
            ->select();
        $channel_arr = objToArr($channel_list);
        $channel_id_arr = array_column($channel_arr, 'channel_id');
        return $channel_id_arr;
//        echo '<pre>';print_r(objToArr($channel_list));exit;
    }
    /*
     * 我的奖金
     * **/
    /*public function getMyBonus($uid, $my_weight, $my_proportion, $condition)
    {
        //找到我的所有下级，根据分销来找
        $member_mdl = new VslMemberModel();
        $my_down_channel = $this->myAllDownChannel($uid, $my_weight);
        $condition['website_id'] = $this->website_id;
        $condition['buy_type'] = 1;
        $bonus_money = 0;
        if(empty($my_down_channel)){
            return 0;
        }
        foreach($my_down_channel as $k=>$buyer_id){
            $buyer_id_str = implode(',', $buyer_id);
            $condition['uid'] = ['in', $buyer_id_str];
            switch($k){
                case 'cross_grade':
                    $cross_order_list = $this->getPurchaseOriginList($condition);
                    $cross_level = $my_proportion['cross_level'];
//                    echo Db::table('')->getLastSql();
                    $pay_money_arr = array_column($cross_order_list,'purchase_money');
                    $totay_pay_money1 = array_sum($pay_money_arr);
                    $bonus_money += $cross_level*$totay_pay_money1;
                    break;
                case 'peer_one_grade':
                    $flat_first = $my_proportion['flat_first'];
                    $peer_one_order_list = $this->getPurchaseOriginList($condition);
                    $pay_money_arr2 = array_column($peer_one_order_list,'purchase_money');
//                    var_dump($pay_money_arr2);exit;
                    $totay_pay_money2 = array_sum($pay_money_arr2);
                    $bonus_money += $flat_first*$totay_pay_money2;
                    break;
                case 'peer_two_grade':
                    $flat_second = $my_proportion['flat_second'];
                    $peer_two_order_list = $this->getPurchaseOriginList($condition);
                    $pay_money_arr3 = array_column($peer_two_order_list,'purchase_money');
                    $totay_pay_money3 = array_sum($pay_money_arr3);
                    $bonus_money += $flat_second*$totay_pay_money3;
                    break;
                case 'peer_three_grade':
                    $flat_second = $my_proportion['flat_second'];
                    $peer_three_order_list = $this->getPurchaseOriginList($condition);
                    $pay_money_arr4 = array_column($peer_three_order_list,'purchase_money');
                    $totay_pay_money4 = array_sum($pay_money_arr4);
                    $bonus_money += $flat_second*$totay_pay_money4;
                    break;
            }
        }
        return round($bonus_money,2);

    }*/
    /*
     * 我的奖金
     * **/
    public function getMyBonus($condition)
    {
        //找到我的所有下级，根据分销来找
        $channel_bonus_mdl = new VslChannelBonusModel();
        $channel_level = new VslChannelLevelModel();
        $money_list = $channel_bonus_mdl->getquery($condition,'order_money,buyer_channel_weight, money_type, buyer_channel_grade_id', '');
        $bonus = 0;
        foreach($money_list as $k=>$v){
            $buyer_channel_grade_id = $v['buyer_channel_grade_id'];
            $channel_level_info = $channel_level->getInfo(['channel_grade_id' => $buyer_channel_grade_id]);
            $ratio = 0;
            switch($v['money_type']){
                case '1'://跨级
                    $ratio = $channel_level_info['cross_level'];
                    break;
                case '2'://平1
                    $ratio = $channel_level_info['flat_first'];
                    break;
                case '3'://平2
                    $ratio = $channel_level_info['flat_second'];
                    break;
                case '4'://平3
                    $ratio = $channel_level_info['flat_third'];
                    break;
            }
            $bonus += $v['order_money'] * $ratio;
        }
        return $bonus;
    }

    /*
     * 获取我的下级渠道商 最多3级
     * **/
    public function myAllDownChannel($uid, $my_grade_weight)
    {
        $member_mdl = new VslMemberModel();
        $channel_mdl = new VslChannelModel();
        $website_id = $this->website_id;
        $condition['referee_id'] = $uid;
        $condition['website_id'] = $website_id;
        $condition['isdistributor'] = 2;
        $member_info = $member_mdl
            ->field('uid, referee_id')
            ->where($condition)
            ->select();
        $member_info = objToArr($member_info);
//        p($member_info);
        $condition['m.referee_id'] = $member_info['uid'];
        if($member_info){
            foreach($member_info as $k=>$user_arr){
                $my_down_channel['one_grade'][] = $user_arr['uid'];
                $condition2['referee_id'] = $user_arr['uid'];
                $condition2['website_id'] = $website_id;
                $condition2['isdistributor'] = 2;
                $member_info_2 = $member_mdl
                    ->field('uid, referee_id')
                    ->where($condition2)
                    ->select();
                if($member_info_2){
                    foreach($member_info_2 as $k=>$user_arr1){
                        $my_down_channel['two_grade'][] = $user_arr1['uid'];
                        $condition3['referee_id'] = $user_arr1['uid'];
                        $condition3['website_id'] = $website_id;
                        $condition3['isdistributor'] = 2;
                        $member_info_3 = $member_mdl
                            ->field('uid, referee_id')
                            ->where($condition3)
                            ->select();
                        foreach($member_info_3 as $k=>$user_arr2){
                            $my_down_channel['three_grade'][] = $user_arr2['uid'];
                        }
                    }
                }
            }
        }else{
            $my_down_channel = [];
        }
        if(!$my_down_channel){
            return [];
        }
        //查出渠道商的设置，看开启了平几级
        $channel_setting = $this->getChannelConfig();
        $channel_peers = $channel_setting['channel_peers'];
        //判断1级、2级分销商是否是我的下级渠道商和平级渠道商
        foreach($my_down_channel['one_grade'] as $k=>$user_id){
            $condition_channel['c.uid'] = $user_id;
            $condition_channel['c.status'] = 1;
            $condition_channel['c.website_id'] = $website_id;
            $channel_list = $channel_mdl->alias('c')->field('c.uid, c.channel_id, cl.weight')->join('vsl_channel_level cl', 'c.channel_grade_id=cl.channel_grade_id')->where($condition_channel)->find();
            $channel_grade = $channel_list['weight'];
            if($my_grade_weight<$channel_grade){
                $down_channel_arr['cross_grade'][$k] = $channel_list['uid'];
            }elseif($my_grade_weight==$channel_grade){
                $down_channel_arr['peer_one_grade'][$k] = $channel_list['uid'];
            }
        }
        if($channel_peers>=2 && !empty($my_down_channel['two_grade'])){
            foreach($my_down_channel['two_grade'] as $k=>$user_id){
                $condition_channel['c.uid'] = $user_id;
                $condition_channel['c.status'] = 1;
                $condition_channel['c.website_id'] = $website_id;
                $channel_list = $channel_mdl->alias('c')->field('c.uid, cl.weight')->join('vsl_channel_level cl', 'c.channel_grade_id=cl.channel_grade_id')->where($condition_channel)->find();
                $channel_grade = $channel_list['weight'];
                if($my_grade_weight==$channel_grade){
                    $down_channel_arr['peer_two_grade'][$k] = $channel_list['uid'];
                }
            }
            if($channel_peers == 3 && !empty($my_down_channel['three_grade'])){
                foreach($my_down_channel['three_grade'] as $k=>$user_id){
                    $condition_channel['c.uid'] = $user_id;
                    $condition_channel['c.status'] = 1;
                    $condition_channel['c.website_id'] = $website_id;
                    $channel_list = $channel_mdl->alias('c')->field('c.uid, cl.weight')->join('vsl_channel_level cl', 'c.channel_grade_id=cl.channel_grade_id')->where($condition_channel)->find();
                    $channel_grade = $channel_list['weight'];
                    if($my_grade_weight==$channel_grade){
                        $down_channel_arr['peer_three_grade'][$k] = $channel_list['uid'];
                    }
                }
            }
        }
//        echo '<pre>';print_R($down_channel_arr);exit;
        return $down_channel_arr;
    }

    /*
     * 获取我的下级渠道商 最多3级
     * **/
    public function myAllUpChannel($uid, $my_grade_weight)
    {
        $member_mdl = new VslMemberModel();
        $channel_mdl = new VslChannelModel();
        $website_id = $this->website_id;
        $condition['uid'] = $uid;
        $condition['website_id'] = $website_id;
        $condition['isdistributor'] = 2;
        $member_info = $member_mdl
            ->field('uid, referee_id')
            ->where($condition)
            ->select();
        $member_info = objToArr($member_info);
//        p($member_info);
//        $condition['m.referee_id'] = $member_info['uid'];
        if($member_info){
            foreach($member_info as $k=>$user_arr){
                if($user_arr['referee_id']){
                    $my_down_channel['one_grade'][] = $user_arr['referee_id'];//上一级
                }
                $condition2['uid'] = $user_arr['referee_id'];
                $condition2['website_id'] = $website_id;
                $condition2['isdistributor'] = 2;
                $member_info_2 = $member_mdl
                    ->field('uid, referee_id')
                    ->where($condition2)
                    ->select();
                if($member_info_2){
                    foreach($member_info_2 as $k=>$user_arr1){
                        if($user_arr1['referee_id']){
                            $my_down_channel['two_grade'][] = $user_arr1['referee_id'];//上二级
                        }
                        $condition3['uid'] = $user_arr1['referee_id'];
                        $condition3['website_id'] = $website_id;
                        $condition3['isdistributor'] = 2;
                        $member_info_3 = $member_mdl
                            ->field('uid, referee_id')
                            ->where($condition3)
                            ->select();
                        foreach($member_info_3 as $k=>$user_arr2){
                            if($user_arr2['referee_id']){
                                $my_down_channel['three_grade'][] = $user_arr2['referee_id'];//上三级
                            }
                        }
                    }
                }
            }
        }else{
            $my_down_channel = [];
        }
        if(!$my_down_channel){
            return [];
        }
        //查出渠道商的设置，看开启了平几级
        $channel_setting = $this->getChannelConfig();
        $channel_peers = $channel_setting['channel_peers'];
        //判断1级、2级分销商是否是我的下级渠道商和平级渠道商
        foreach($my_down_channel['one_grade'] as $k=>$user_id){
            $condition_channel['c.uid'] = $user_id;
            $condition_channel['c.status'] = 1;
            $condition_channel['c.website_id'] = $website_id;
            $channel_list = $channel_mdl->alias('c')->field('c.uid, c.channel_id, cl.weight')->join('vsl_channel_level cl', 'c.channel_grade_id=cl.channel_grade_id')->where($condition_channel)->find();
            $channel_grade = $channel_list['weight'];
            if($channel_grade && $my_grade_weight>$channel_grade){
                $down_channel_arr['cross_grade'][$k] = $channel_list['uid'];
            }elseif($my_grade_weight==$channel_grade){
                $down_channel_arr['peer_one_grade'][$k] = $channel_list['uid'];
            }
        }
        if($channel_peers>=2 && !empty($my_down_channel['two_grade'])){
            foreach($my_down_channel['two_grade'] as $k=>$user_id){
                $condition_channel['c.uid'] = $user_id;
                $condition_channel['c.status'] = 1;
                $condition_channel['c.website_id'] = $website_id;
                $channel_list = $channel_mdl->alias('c')->field('c.uid, cl.weight')->join('vsl_channel_level cl', 'c.channel_grade_id=cl.channel_grade_id')->where($condition_channel)->find();
                $channel_grade = $channel_list['weight'];
                if($my_grade_weight==$channel_grade){
                    $down_channel_arr['peer_two_grade'][$k] = $channel_list['uid'];
                }
            }
            if($channel_peers == 3 && !empty($my_down_channel['three_grade'])){
                foreach($my_down_channel['three_grade'] as $k=>$user_id){
                    $condition_channel['c.uid'] = $user_id;
                    $condition_channel['c.status'] = 1;
                    $condition_channel['c.website_id'] = $website_id;
                    $channel_list = $channel_mdl->alias('c')->field('c.uid, cl.weight')->join('vsl_channel_level cl', 'c.channel_grade_id=cl.channel_grade_id')->where($condition_channel)->find();
                    $channel_grade = $channel_list['weight'];
                    if($my_grade_weight==$channel_grade){
                        $down_channel_arr['peer_three_grade'][$k] = $channel_list['uid'];
                    }
                }
            }
        }
        return $down_channel_arr;
    }

    /*
     * 获取采购的订单列表
     * **/
    public function getPurchaseOriginList($condition)
    {
        $channel_order_record_mdl = new VslChannelOrderSkuRecordModel();
        $channel_order_list = $channel_order_record_mdl->where($condition)->select();
        $channel_order_arr = objToArr($channel_order_list);
        foreach($channel_order_arr as $k=>$v){
            $channel_order_arr[$k]['purchase_money'] = $v['num']*$v['price'];
        }
        return $channel_order_arr;
    }
    /*
     * 获取采购的订单列表
     * **/
    public function getRetailList($condition)
    {
        $channel_order_record_mdl = new VslChannelOrderSkuRecordModel();
        $channel_order_list = $channel_order_record_mdl->alias('cosr')->field('cosr.order_id')->join('vsl_order o', 'cosr.order_id = o.order_id', 'LEFT')->where($condition)->select();
        $channel_order_arr = objToArr($channel_order_list);
        $order_id_arr = [];
        foreach ($channel_order_arr as $k => $v) {
            $order_id_arr[] = $v['order_id'];
        }
        return $order_id_arr;
    }

    /*
     * 获取利润
     * **/
    public function getPurchaseOrderGoodsList($condition)
    {
        //采购的利润 一句话：买的我的，查出我的采购价，然后算差价
        $channel_order_arr = $this->getPurchaseOriginList($condition);
        $channel_id = $condition['channel_info'];
        $website_id = $this->website_id;
        $my_profit = 0;
        if(!empty($channel_order_arr)){
            foreach($channel_order_arr as $k=>$channel){
                //获取当时采购的时候我的折扣
                $batch_ratio_record = $channel['batch_ratio_record'];
                $batch_ratio_record_arr = explode(';', $batch_ratio_record);
                $up_per_batch_purchase = 0;
                foreach($batch_ratio_record_arr as $record_str){
                    $record_arr = explode(':', $record_str);
                    $purchase_num = $record_arr[1];
                    $purchase_ratio = $record_arr[2];
                    $up_per_batch_purchase += $purchase_num*$channel['platform_price']*$purchase_ratio;
                }
                //下级进货价
                $down_price = $channel['price']*$channel['total_num'];
                $my_profit += $down_price - $up_per_batch_purchase;
            }
        }
        //零售的利润 已完成的
        $condition2['cosr.channel_info'] = $channel_id;
        $condition2['cosr.buy_type'] = 3;//零售
        $condition2['cosr.website_id'] = $website_id;
        if($condition['create_time']){
            $condition2['cosr.create_time'] = $condition['create_time'];
        }
        $condition2['o.order_status'] = 4;
        $order_id_arr = $this->getRetailList($condition2);
        if(empty($order_id_arr)){
            return $my_profit;
        }
        $order_model = new VslOrderModel();
        $channel_order_record_mdl = new VslChannelOrderSkuRecordModel();
        $order_id_arr = array_unique($order_id_arr);
        foreach ($order_id_arr as $k => $v) {
            //此笔订单结算给渠道商的金额
            $channel_money = $order_model->Query(['order_id' => $v],'channel_money')[0] ?: 0;
            //算此笔订单的商品当时采购时的成本
            $record_list = $channel_order_record_mdl->getQuery(['order_id' => $v],'*','');
            $purchase_money = 0;
            foreach ($record_list as $retail) {
            $batch_ratio_record1 = $retail['batch_ratio_record'];
            $batch_ratio_record_arr1 = explode(';', $batch_ratio_record1);
            foreach($batch_ratio_record_arr1 as $record_str){
                $record_arr1 = explode(':', $record_str);
//                    $purchase_num1 = $record_arr1[1];
                $purchase_ratio1 = $record_arr1[2];
                    $purchase_money += $retail['num']*$retail['platform_price']*$purchase_ratio1;
                }
            }
            $my_profit += (float)$channel_money - (float)$purchase_money;
        }
        return $my_profit;
    }
    /*
     * 获取采购订单数
     * **/
    public function getPurchaseOrderNum($condition)
    {
        //采购订单数
        $condition['pay_status'] = 2;
        $channel_order_mdl = new VslChannelOrderModel();
        $channel_order_list = $channel_order_mdl->field('count(order_id) AS purchase_num, sum(order_money) AS purchase_money')->where($condition)->find();
        return $channel_order_list;


    }
    /*
     * 移除渠道商
     * **/
    public function removeChannel($channel_id)
    {
        $channel_mdl = new VslChannelModel();
        $retval = $channel_mdl->where(['channel_id'=>$channel_id])->delete();
        return $retval;
    }
    /***************************************前台接口*******************************************/
    /*
     * 获取当前用户的上级分销商是渠道商
     * **/
    public function myRefereeChannel($uid,$my_weight)
    {
        $member_mdl = new VslMemberModel();
        $channel_mdl = new VslChannelModel();
        $is_channel = false;
        //获取我的上级渠道商，就一个。若没有，则是平台。
        while(!$is_channel){
            $member_info = $member_mdl->alias('m')
                ->where(['m.uid'=>$uid,'m.website_id'=>$this->website_id,'m.isdistributor'=>2])//2是分销商
//                ->join('sys_user u','m.referee_id=u.uid','LEFT')
                ->find();
            $member_info = objToArr($member_info);
            $is_channel = $channel_mdl->alias('c')
                ->join('vsl_channel_level cl','c.channel_grade_id=cl.channel_grade_id')
                ->where(['uid'=>$member_info['referee_id'],'cl.weight'=>['>',$my_weight],'c.status'=>1])
                ->find();
            $uid = $member_info['referee_id'];
            if($is_channel){
                return $is_channel['channel_id'];
            }
            //如果上级分销商的推荐人id为空了，说明最上级了，而且这个也不是渠道商，那么只能找平台了。
            if(!empty($member_info['referee_id'])){
                continue;
            }else{
                return 'platform';
            }
        }
    }
    /*
     * 获取当前用户渠道商的所有上级渠道商的sku库存
     * **/
    public function myAllRefereeChannelSkuStore($uid, $my_grade_weight, $sku_id)
    {
        $channel_arr = $this->myAllRefereeChannel($uid,$my_grade_weight);
        $stock = 0;
        foreach($channel_arr as $k=>$v){
            $stock += $this->getChannelSkuStore($sku_id, $v)['stock'];
        }
        return $stock;
    }
    /*
     * 根据sku和当前的渠道商id获取当前渠道商商品的库存
     * **/
    public function getChannelSkuStore($sku_id, $channel_info, $website_id = 0)
    {
        $website_id = $website_id?:$this->website_id;
        if($channel_info == 'platform'){
            $condition['g.website_id'] = $website_id;
            $condition['gs.sku_id'] = $sku_id;
            $goods_mdl = new VslGoodsModel();
            $sku_list = $goods_mdl->alias('g')->field('gs.stock, gs.price')->join('vsl_goods_sku gs', 'g.goods_id=gs.goods_id')->where($condition)->find();
        }else{
            $condition_channel['cgs.channel_id'] = $channel_info;
            $condition_channel['cg.channel_id'] = $channel_info;
            $condition_channel['cg.website_id'] = $website_id;
            $condition_channel['cgs.sku_id'] = $sku_id;
            $goods_mdl = new VslChannelGoodsModel();
            $sku_list = $goods_mdl->alias('cg')->field('cgs.stock, cgs.price')->join('vsl_channel_goods_sku cgs', 'cg.goods_id=cgs.goods_id')->where($condition_channel)->find();
//            echo $goods_mdl->getLastSql();exit;
        }
        return $sku_list;
    }
    /*
     * 判断渠道商商品库存是否大于了平台最大单次限购量,是就取单次限购量，若不是，则取库存
     * **/
    public function isChannelgPlatform($goods_id, $sku_id, $channel_info){
        $goods_mdl = new VslGoodsModel();
        $goods_list = $goods_mdl->where(['website_id'=>$this->website_id, 'goods_id'=>$goods_id])->find();
//        echo $goods_mdl->getLastSql();exit;
        $max_buy = $goods_list['max_buy'];
        $sku_list = $this->getChannelSkuStore($sku_id, $channel_info);
        $channel_stock = $sku_list['stock'];
        if($max_buy === 0){
            $limit_buy = $channel_stock;
        }elseif($max_buy>$channel_stock){
            $limit_buy = $channel_stock;
        }elseif($max_buy<$channel_stock){
            $limit_buy = $max_buy;
        }
        return $limit_buy;
    }
    /*
     * 根据当前渠道商采购的数量，判断上级渠道商若不够数量，则依次向上取商品数量和价格
     * **/
    public function getUpChannelSkuNum($uid, $my_grade_weight, $sku_id, $num)
    {
        $stock_arr = $this->getMyGradeStock($uid,$my_grade_weight,$sku_id);
        //$v1是数量
//        $stock_arr = array ( 3=>2, 4 => 1, 5 => 2, 6=>3, 'platform' => 2, );
//        $num = 9;
        $channel_stock = '';
        foreach ($stock_arr as $k1 => $v1) {
            //这里的意思是如果渠道商的库存大于0，则要买它的商品库存
            if ($v1 > 0) {
                //如果要买的数量大于渠道商的库存，则取渠道商的库存，否则取买的数目
                if ($num >= $v1) {
                    $num = $num - $v1;
                    //如果循环发现，购买的数目小于渠道商的库存，则取循环后的购买数，否则取渠道商的库存数
                    $channel_stock .= $k1 . ':' . $sku_id . ':' . $v1 . ' ';
                } else {
                    if ($num > 0) {
                        $channel_stock .= $k1 . ':' . $sku_id . ':' . $num . ' ';
                        //这里是为了结束循环
                        return trim($channel_stock);
                    }
                }
            }
        }
        //若最后一次刚好是符合条件且结束就不会再循环了，所以需要在这里return。
        return trim($channel_stock);
    }
    /*
     * 获取我上面所有渠道商该sku的库存
     * **/
    public function getMyGradeStock($uid,$my_grade_weight,$sku_id)
    {
        $channel_arr = $this->myAllRefereeChannel($uid,$my_grade_weight);
        //$v是渠道商id
        foreach($channel_arr as $k=>$v){
            //依次获取库存值，如果库存不足，则获取上一级的，直到符合用户购买的商品数量
            $stock = (int)$this->getChannelSkuStore($sku_id, $v)['stock'];
            $stock_arr[$v] = $stock;
        }
        return $stock_arr;
    }
    /*
     * 得到自提的商品的购买量，就是它自己的sku库存
     * **/
    public function getMyChannelSkuNum($channel_id, $sku_id, $num)
    {
        $channel_goods_sku_list = $this->getChannelSkuInfo($channel_id, $sku_id);
        $my_stock = $channel_goods_sku_list['stock'];
        $channel_id = $channel_goods_sku_list['channel_id'];
        $buy_num = ($num<=$my_stock)?$num:$my_stock;
        //组合成和采购的那种数据格式
        $my_channel_stock = $channel_id.':'.$sku_id.':'.$buy_num;
        return $my_channel_stock;
    }
    /*
     * 得到单个渠道商sku的数据
     * **/
    public function getChannelSkuInfo($channel_id, $sku_id)
    {
        $website_id = $this->website_id;
        $condition['cgs.channel_id'] = $channel_id;
        $condition['cg.channel_id'] = $channel_id;
        $condition['cg.website_id'] = $website_id;
        $condition['cgs.sku_id'] = $sku_id;
        $goods_mdl = new VslChannelGoodsModel();
        $channel_goods_sku_list = $goods_mdl->alias('cg')
            ->where($condition)
            ->join('vsl_channel_goods_sku cgs', 'cg.goods_id = cgs.goods_id', 'LEFT')
            ->find();
//        echo $goods_mdl->getLastSql();exit;
        return $channel_goods_sku_list;
    }
    /*
     * 获取当前用户的所有上级分销商是渠道商，返回所有的上级渠道商id
     * **/
    public function myAllRefereeChannel($uid, $my_grade_weight)
    {
        $member_mdl = new VslMemberModel();
        $channel_mdl = new VslChannelModel();
        $mark = false;
        $website_id = $this->website_id;
        $condition['uid'] = $uid;
        $condition['website_id'] = $website_id;
        while(!$mark){
            $member_info = $member_mdl->alias('m')
                ->where($condition)
                ->find();
            $member_info = objToArr($member_info);
            $condition['uid'] = $member_info['referee_id'];
            //如果上级分销商的推荐人id为空了，说明最上级了，而且这个也不是渠道商，那么只能是平台了。
            if(!empty($member_info['referee_id'])){
                $my_referee_destribution[] =  $member_info['referee_id'];
                continue;
            }else{
                $mark = true;
            }
        }
//        如果当前$my_referee_destribution为空，则说明直接拿平台的商品
        if(!$my_referee_destribution){
            $channel_arr = ['platform'];
        }else{
            $my_referee_str = implode($my_referee_destribution,',');
            $condition_channel['c.website_id'] = $website_id;
            $condition_channel['c.uid'] = ['in', $my_referee_str];
            $condition_channel['cl.weight'] = ['>', $my_grade_weight];
            $channel_list = $channel_mdl->alias('c')->field('c.channel_id')->join('vsl_channel_level cl', 'c.channel_grade_id=cl.channel_grade_id')->where($condition_channel)
                ->order("field(c.uid,".$my_referee_str.")")//通过上级的in uid排序
                ->select();
            $channel_id_arr = objToArr($channel_list);
            $channel_arr = array_column($channel_id_arr, 'channel_id');
            $channel_arr[] = 'platform';
        }
        return $channel_arr;
    }
    /**
     * 添加购物车(non-PHPdoc)
     *
     * @see \data\api\IGoods::addCart()
     */
    public function addCart($uid, $shop_id, $goods_id, $goods_name, $sku_id, $sku_name, $price, $num, $picture, $bl_id, $buy_type, $channel_info, $update=0)
    {
        // 检测当前购物车中是否存在产品
        if ($uid > 0) {
//            $channel_cart_mdl = new VslCartModel();
            $channel_cart_mdl = new VslChannelCartModel();
            $goods_server = new Goods();
            $condition = array(
                'buyer_id' => $uid,
                'sku_id' => $sku_id,
                'channel_info' => $channel_info,
                'buy_type' => $buy_type
            );
            //获取店铺名称
            $shop_name = '自营店';
            if(getAddons('shop', $this->website_id)){
                $shop_model = new VslShopModel();
                $shop_info = $shop_model::get(['shop_id' => $shop_id, 'website_id' => $this->website_id]);
                $shop_name = $shop_info['shop_name'];
            }
            
            $count = $channel_cart_mdl->where($condition)->count();
            if ($count == 0 || empty($count)) {
                $data = array(
                    'buyer_id' => $uid,
                    'shop_id' => $shop_id,
                    'shop_name' => $shop_name,
                    'goods_id' => $goods_id,
                    'goods_name' => $goods_name,
                    'sku_id' => $sku_id,
                    'sku_name' => $sku_name,
                    'price' => $price,
                    'num' => $num,
                    'goods_picture' => $picture,
                    'bl_id' => $bl_id,
                    'website_id' => $this->website_id,
                    'buy_type' => $buy_type,
                    'channel_info'=>$channel_info,
                );
                $channel_cart_mdl->save($data);
                $retval = $channel_cart_mdl->cart_id;
            } else {
                $channel_cart_mdl = new VslChannelCartModel();
                // 查询商品限购
                $condition1 = [];
                if($channel_info == 'platform'){
                    $goods_sku_mdl = new VslGoodsSkuModel();
                    $condition1['sku_id'] = $sku_id;
                    $goods_sku_info = $goods_sku_mdl->where($condition1)->find();
                    $stock = $goods_sku_info['stock'];
                }else{
                    $condition1['cgs.sku_id'] = $sku_id;
                    $condition1['cgs.channel_id'] = $channel_info;
                    $condition1['cg.website_id'] = $this->website_id;
                    $condition1['cg.channel_id'] = $channel_info;
                    $goods_mdl = new VslChannelGoodsModel();
                    $goods_sku_info = $goods_mdl->alias('cg')->join('vsl_channel_goods_sku cgs', 'cg.goods_id=cgs.goods_id')->where($condition1)->find();
                    $stock = $goods_sku_info['stock'];
                }
                $get_num = $channel_cart_mdl->getInfo($condition, 'cart_id,num');
                if(!$update){
                    $new_num = $num;
//                    $new_num = $num + $get_num['num'];
//                    var_dump($new_num);exit;
                }else{
                    $new_num = $num;
                }
                //获取该渠道商的剩余库存，如果大于了，则取库存的数量
                $new_num = ($new_num>=$stock)?$stock:$new_num;
                $data = array(
                    'num' => $new_num
                );
//                var_dump($stock,$new_num);exit;
                $retval = $channel_cart_mdl->save($data, $condition);
                if ($retval) {
                    $retval = $get_num['cart_id'];
                }
            }
        }
        return $retval;
    }
    /*
     * 得到我的channel_id
     * **/
    public function getMyChannelInfo($condition)
    {
        //必须都为审核
        $condition['status'] = 1;
        $channel_mdl = new VslChannelModel();
        $channel_info = $channel_mdl->alias('c')
            ->field('c.*,cl.*,u.user_name,u.nick_name,u.user_tel,u.user_headimg')
            ->where($condition)
            ->join('vsl_channel_level cl','c.channel_grade_id=cl.channel_grade_id','LEFT')
            ->join('sys_user u', 'u.uid = c.uid','LEFT')
            ->find();
        if($channel_info['user_headimg']){
            $channel_info['user_headimg'] = getApiSrc($channel_info['user_headimg']);
        }
        
        return $channel_info;
    }
    /*
     * 获取购物车内容
     * **/
    public function getChannelCart($page_index, $page_size, $condition, $order)
    {
        $channel_cart_mdl = new VslChannelCartModel();
        $cart_count = $channel_cart_mdl->alias('c')
            ->field('c.cart_id')
            ->join('sys_album_picture ap', 'c.goods_picture = ap.pic_id')
            ->order($order)
            ->where($condition)
            ->group('c.sku_id')
            ->select();
        $count = count($cart_count);
        $page_count = ceil($count/$page_size);
        $page_offset = ($page_index-1)*$page_size;
        $cart_list = $channel_cart_mdl->alias('c')
            ->field('c.buyer_id, c.shop_name, c.goods_id, c.goods_name, c.sku_id, c.sku_name, c.price, sum(num) AS num, c.buyer_id, ap.pic_cover, c.channel_info')
            ->join('sys_album_picture ap', 'c.goods_picture = ap.pic_id')
            ->limit($page_offset, $page_size)
            ->order($order)
            ->where($condition)
            ->group('c.sku_id')
            ->select();
        return [
            'code'=>0,'message'=>'获取成功',
            'data' => [
                'data' => objToArr($cart_list),
                'total_count' => $count,
                'page_count' => $page_count
            ]
        ];
//        var_dump(objToArr($cart_list));exit;
    }

    public function changeCartPrice($cart_goods, $channel_info, $up_grade_channel_id)
    {
        $goods = new Goods();
        //判断当前的价格是否更改，若更改则修改购物车
        $temp_arr = ['channel_id' => $up_grade_channel_id, 'sku_id'=>$cart_goods['sku_id'], 'price'=>$cart_goods['price'], 'market_price'=>0];
        $goods->getChannelSkuPrice($temp_arr);
        $change_price = $temp_arr['price'] * $channel_info['purchase_discount'];
        if($change_price != $cart_goods['price']){
            $channel_cart = new VslChannelCartModel();
            $change_arr['price'] = $change_price;
            $condition_cart['buyer_id'] = $cart_goods['buyer_id'];
            $condition_cart['sku_id'] = $cart_goods['sku_id'];
            $condition_cart['buy_type'] = 1;
            $condition_cart['website_id'] = $this->website_id;
            $channel_cart->save($change_arr, $condition_cart);
        }
        return $change_price;
    }
    /*
     * 获取确认订单页内容
     * **/
    public function getChannelSettlement($condition, $order)
    {
        $channel_cart_mdl = new VslChannelCartModel();
        $cart_list = $channel_cart_mdl->alias('c')
            ->field('c.buyer_id, c.shop_name, c.shop_id, c.goods_id, c.goods_name, c.sku_id, c.sku_name, c.price, c.num, c.buyer_id, c.channel_info, ap.pic_cover')
            ->join('sys_album_picture ap', 'c.goods_picture = ap.pic_id')
            ->order($order)
            ->where($condition)
            ->select();
        return [
            'code'=>0,'message'=>'获取成功',
            'data' => [
                'data'=>objToArr($cart_list),
            ]
        ];
//        var_dump(objToArr($cart_list));exit;
    }
    /*
     * 删除购物车
     * **/
    public function deleteChannelCart($condition)
    {
        $channel_cart_mdl = new VslChannelCartModel();
        $retval = $channel_cart_mdl->where($condition)->delete();
        return $retval;
    }
    /*
     * 获取提交订单的购物车数据
     * **/
    public function getChannelCartList($condition)
    {
        $channel_cart_mdl = new VslChannelCartModel();
        $cart_list = $channel_cart_mdl->where($condition)->select();
        return $cart_list;
    }
    /*
     * 调整购物车数量
     * **/
    public function updateChannelCart($condition,$num)
    {
        $channel_cart_mdl = new VslChannelCartModel();
        //库存不足，为0就删除购物车商品
        if($num>0){
            $channel_cart_mdl->save(['num'=>$num], $condition);
        }else{
            $channel_cart_mdl->where($condition)->delete();
        }
    }
    /*
     * 创建渠道商id
     * **/
    public function channelOrderCreate($order_info)
    {
        $this->order->startTrans();
//        $account_flow = new MemberAccount();
        try {
            $data_order = array(
                'order_no' => $order_info['order_no'],
                'out_trade_no' => $order_info['out_trade_no'],
                'order_sn' => $order_info['order_sn'],
                //订单归属渠道商信息
//                'channel_info' => $order_info['channel_info'],
                'order_from' => $order_info['order_from'],
                'buyer_id' => $order_info['buyer_id'],
                'user_name' => $order_info['nick_name'],
                'buyer_ip' => $order_info['ip'],
                'buyer_message' => '',
                'buyer_invoice' => $order_info['buyer_invoice'],
                'shipping_time' => $order_info['shipping_time'], // datetime NOT NULL COMMENT '买家要求配送时间',
                'receiver_mobile' => $order_info['receiver_mobile'], // varchar(11) NOT NULL DEFAULT '' COMMENT '收货人的手机号码',
                'receiver_province' => $order_info['receiver_province'], // int(11) NOT NULL COMMENT '收货人所在省',
                'receiver_city' => $order_info['receiver_city'], // int(11) NOT NULL COMMENT '收货人所在城市',
                'receiver_district' => $order_info['receiver_district'], // int(11) NOT NULL COMMENT '收货人所在街道',
                'receiver_address' => $order_info['receiver_address'], // varchar(255) NOT NULL DEFAULT '' COMMENT '收货人详细地址',
                'receiver_zip' => $order_info['receiver_zip'], // varchar(6) NOT NULL DEFAULT '' COMMENT '收货人邮编',
                'receiver_name' => $order_info['receiver_name'], // varchar(50) NOT NULL DEFAULT '' COMMENT '收货人姓名',
                'shop_id' => $order_info['shop_id'], // int(11) NOT NULL COMMENT '卖家店铺id',
                'shop_name' => $order_info['shop_name'], // varchar(100) NOT NULL DEFAULT '' COMMENT '卖家店铺名称',
                'create_time' => $order_info['create_time'],
                'website_id' => $order_info['website_id'],
                'shipping_company_id' => 0,//配送物流公司ID
                'payment_type' => $order_info['pay_type'],
                'shipping_type' => $order_info['shipping_type'],
                'order_status' => $order_info['order_status'], // tinyint(4) NOT NULL COMMENT '订单状态',
                'pay_status' => $order_info['pay_status'], // tinyint(4) NOT NULL COMMENT '订单付款状态',
                'shipping_status' => 0, // tinyint(4) NOT NULL COMMENT '订单配送状态',
                'review_status' => 0, // tinyint(4) NOT NULL COMMENT '订单评价状态',
                'feedback_status' => 0, // tinyint(4) NOT NULL COMMENT '订单维权状态',
                'user_money' => $order_info['user_money'], // decimal(10, 2) NOT NULL COMMENT '订单预存款支付金额',
                'user_platform_money' => $order_info['user_platform_money'], // 平台余额支付
                'shipping_money' => $order_info['shipping_money'], // decimal(10, 2) NOT NULL COMMENT '订单运费',
                'pay_money' => $order_info['pay_money'], // decimal(10, 2) NOT NULL COMMENT '订单实付金额',
                'refund_money' => 0, // decimal(10, 2) NOT NULL COMMENT '订单退款金额',
                'coin_money' => $order_info['coin_money'],
                'goods_money' => $order_info['goods_money'], // decimal(19, 2) NOT NULL COMMENT '商品总价',
                'tax_money' => $order_info['tax_money'], // 税费
                'order_money' => $order_info['order_money'], // decimal(10, 2) NOT NULL COMMENT '订单总价',
                'buy_type' => $order_info['buy_type'],
            );
            // datetime NOT NULL DEFAULT 'CURRENT_TIMESTAMP' COMMENT '订单创建时间',
            if ($order_info['pay_status'] == 2) {
                $data_order['pay_time'] = time();
            }
            $order = new VslChannelOrderModel();
            $order->save($data_order);
            $order_id = $order->order_id;
            $pay = new UnifyPay();
            $pay->createChannelPayment($order_info['shop_id'], $order_info['out_trade_no'], $order_info['shop_name'] . '订单', $order_info['shop_name'] . '订单', $order_info['pay_money'], 1, $order_id, $order_info['create_time']);
            // 添加订单商品项
            //$order_id, $money, $give_point, array $sku_lists, $adjust_money = 0
            $res_order_goods = $this->addChannelOrderGoods($order_id,  $order_info['sku_info'], 0, $order_info['order_no']);
           
            if (!($res_order_goods > 0)) {
                $this->order->rollback();
                return $res_order_goods;
            }
            $this->addChannelOrderAction($order_id, $order_info['buyer_id'], '创建订单');
            $this->order->commit();
            return $order_id;
        } catch (\Exception $e) {
            $this->order->rollback();
            return ORDER_CREATE_FAIL;
//            return $e->getMessage();
        }
    }
    /*
     * 添加渠道商订单商品
     * **/
    public function addChannelOrderGoods($order_id, array $cart_lists, $adjust_money = 0, $order_no)
    {
       
        $this->order_goods->startTrans();
        try {
           
            $err = 0;
            $order_goods_service = new OrderGoods();
            foreach ($cart_lists as $channnel_info=> $cart) {
                if($cart['channel_info'] == 'platform'){
                    $goods_model = new VslGoodsModel();
                }else{
                    $goods_model = new VslChannelGoodsModel();
                }
                $goods_sku_model1 = new VslGoodsSkuModel();
                $goods_sku_info1 = $goods_sku_model1->getInfo([
                    'sku_id' => $cart['sku_id'],
                ], '*');
                if(!$goods_sku_info1){
                    $goods_sku_model1 = new VslChannelGoodsSkuModel();
                    $goods_sku_info1 = $goods_sku_model1->getInfo([
                        'sku_id' => $cart['sku_id'],
                    ], '*');
                }

                // 如果当前商品有SKU图片，就用SKU图片。没有则用商品主图
                $picture = $order_goods_service->getSkuPictureBySkuId($goods_sku_info1);
                //                var_dump($picture);exit;
                $goods_info = $goods_model->getInfo([
                    'goods_id' => $cart['goods_id']
                ], '*');
                //                echo '<pre>';print_r(objToArr($goods_info));exit;
                $data_order_sku = array(
                    'order_id' => $order_id,
                    'goods_id' => $cart['goods_id'],
                    'goods_name' => $goods_info['goods_name'],
                    'sku_id' => $cart['sku_id'],
                    'sku_name' => $goods_sku_info1['sku_name'],
                    'real_money' =>  $cart['price']*$cart['num']+$cart['shipping_fee'],//商品应付总额
                    'actual_price' => $cart['price'], //实际单价
                    'price' => $cart['price'],// 销售价
                    'market_price' => $goods_sku_info1['market_price'],//原价（市场价）
                    'num' => $cart['num'],
                    'adjust_money' => $adjust_money,
                    'cost_price' => $goods_sku_info1['cost_price'],
                    'goods_money' => $cart['price'] * $cart['num'] - $adjust_money,
                    'goods_picture' => $picture != 0 ? $picture : $goods_info['picture'], // 如果当前商品有SKU图片，就用SKU图片。没有则用商品主图
                    'shop_id' => $cart['shop_id'],
                    'website_id' => $this->website_id,
                    'buyer_id' => $this->uid,
                    'goods_type' => $goods_info['goods_type'],
                    'order_type' => 1, // 订单类型默认1
                    'shipping_fee' => $cart['shipping_fee'],
                    'channel_info' => $cart['channel_info']
                ); // 积分数量默认0
                $order_goods = new VslChannelOrderGoodsModel();
                $order_goods->save($data_order_sku);
                if ($cart['num'] == 0) {
                    $err = 1;
                }
                // 库存减少销量增加
                $goods_calculate = new GoodsCalculate();
                if($cart['channel_info'] == 'platform'){
                    $goods_calculate->subGoodsStock($cart['goods_id'], $cart['sku_id'], $cart['num']);
                    //                    $goods_calculate->addGoodsSales($cart['goods_id'], $cart['num']);
                }else{
                    $goods_calculate->subChannelGoodsStock($cart['goods_id'], $cart['sku_id'], $cart['num'], $cart['channel_info']);
                }
            }
            if ($err == 0) {
                $this->order_goods->commit();
                return 1;
            } elseif ($err == 1) {
                $this->order_goods->rollback();
                return ORDER_GOODS_ZERO;
            }
        } catch (\Exception $e) {
            $this->order_goods->rollback();
            echo $e->getMessage();exit;
            return $e->getMessage();
        }
    }
    /**
     * 添加订单操作日志
     * order_id int(11) NOT NULL COMMENT '订单id',
     * action varchar(255) NOT NULL DEFAULT '' COMMENT '动作内容',
     * uid int(11) NOT NULL DEFAULT 0 COMMENT '操作人id',
     * user_name varchar(50) NOT NULL DEFAULT '' COMMENT '操作人',
     * order_status int(11) NOT NULL COMMENT '订单大状态',
     * order_status_text varchar(255) NOT NULL DEFAULT '' COMMENT '订单状态名称',
     * action_time datetime NOT NULL COMMENT '操作时间',
     * PRIMARY KEY (action_id)
     *
     * @param unknown $order_id
     * @param unknown $uid
     * @param unknown $action_text
     */
    public function addChannelOrderAction($order_id, $uid, $action_text)
    {
        $this->order->startTrans();
        try {
            $order = new Order();
            $order_status = $this->order->getInfo([
                'order_id' => $order_id
            ], 'order_status');
            if ($uid != 0) {
                $user = new UserModel();
                $user_name = $user->getInfo([
                    'uid' => $uid
                ], 'nick_name');
                $action_name = $user_name['nick_name'];
            } else {
                $action_name = 'system';
            }
            $data_log = array(
                'order_id' => $order_id,
                'action' => $action_text,
                'uid' => $uid,
                'user_name' => $action_name,
                'order_status' => $order_status['order_status'],
                'order_status_text' => $this->getOrderStatusName($order_id),
                'action_time' => time(),
                'website_id' => $this->website_id
            );
            $order_action = new VslChannelOrderActionModel();
            $order_action->save($data_log);
            $this->order->commit();
            return $order_action->action_id;
        } catch (\Exception $e) {
            $this->order->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 获取订单当前状态 名称
     *
     * @param unknown $order_id
     */
    public function getOrderStatusName($order_id)
    {
        $order_status = $this->order->getInfo([
            'order_id' => $order_id
        ], 'order_status');
        $status_array = OrderStatus::getOrderCommonStatus();
        foreach ($status_array as $k => $v) {
            if ($v['status_id'] == $order_status['order_status']) {
                return $v['status_name'];
            }
        }
        return false;
    }
    /*
     * 渠道商订单在线支付后，处理订单的后续状态更新，以及发送通知
     */
    public function channelOrderUpdateStatus($order_pay_no, $pay_type)
    {
        $order = new VslChannelOrderModel();
        $retval = $this->ChannelOrderPay($order_pay_no, $pay_type, 0);
        try {
            if ($retval > 0) {
                //判断该订单是采购订单并且向谁采购的，平台还是渠道商
                $condition['out_trade_no'] = $order_pay_no;
                $order_list = $order->getQuery($condition, "order_id", "");
                $this->dealChannelPlatformAccount($order_pay_no, 0);
                foreach ($order_list as $k => $v) {
                    runhook("Notify", "channelOrderPayBySms", array(
                        "order_id" => $v["order_id"],
                        'website_id'=> $this->website_id
                    ));
                    runhook("Notify", "channelOrderPayByTemplate", array(
                        "order_id" => $v["order_id"],
                        'website_id'=> $this->website_id
                    ));
                    runhook('Notify', 'channelOrderRemindBusinessBySms', [
                        "order_id" => $v["order_id"],
                        "shop_id" => 0,
                        "website_id" => $this->website_id
                    ]); // 订单提醒
                    // 邮件通知 - 用户
                    runhook('Notify', 'emailSend', [
                        'website_id' => $this->website_id,
                        'shop_id' => 0,
                        'order_id' => $v['order_id'],
                        'notify_type' => 'user',
                        'template_code' => 'pay_success',
                        'is_channel' => 1
                    ]);
                    // 邮件通知 - 卖家
                    runhook('Notify', 'emailSend', [
                        'website_id' => $this->website_id,
                        'shop_id' => 0,
                        'order_id' => $v['order_id'],
                        'notify_type' => 'business',
                        'template_code' => 'order_remind',
                        'is_channel' => 1
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::write($e->getMessage());
        }
        return $retval;
    }
    /**
     * 订单支付
     *
     * @param unknown $order_pay_no
     * @param unknown $pay_type (10:线下支付)
     * @param unknown $status
     *            0:订单支付完成 1：订单交易完成
     * @param string $seller_memo
     * @return Exception
     */
    public function ChannelOrderPay($order_pay_no, $pay_type, $status)
    {
        $this->order->startTrans();
        try {
            // 添加订单日志
            // 可能是多个订单
            $order_id_array = $this->order->where([
                'out_trade_no' => $order_pay_no
            ])->column('order_id');
            foreach ($order_id_array as $k => $order_id) {
                // 赠送赠品
                $order_info = $this->order->getInfo([
                    'order_id' => $order_id
                ], '*');
                //获取order_id的sku_id
                $og_mdl = new VslChannelOrderGoodsModel();
                $order_goods_info = $og_mdl->getQuery([
                    'order_id' => $order_id
                ], '*','');
//                echo '<Pre>';print_r(objToArr($order_goods_info));exit;
                if ($pay_type == 10) {
                    // 线下支付
                    $this->addChannelOrderAction($order_id, $order_info['buyer_id'], '线下支付');
                } else {
                    // 查询订单购买人ID
                    $this->addChannelOrderAction($order_id, $order_info['buyer_id'], '订单支付');
                }
                // 增加会员累计消费
                $account = new MemberAccount();
                $account->addMmemberConsum(0, $order_info['buyer_id'], $order_info['pay_money']);
                // 修改订单状态
                $data = array(
                    'payment_type' => $pay_type,
                    'pay_status' => 2,
                    'pay_time' => time(),
                    'order_status' => 4//付完款就是已完成了
                ); // 订单转为待发货状态
                $res = $this->order->save($data, [
                    'order_id' => $order_id
                ]);
                $goods = new Goods();
                foreach($order_goods_info as $k1=>$og){
                    //进行插入channel_order_sku_record
                    $record_condition = [];
                    $sku_record_mdl = new VslChannelOrderSkuRecordModel();
                    $record_condition['channel_info'] = $og['channel_info'];
                    $record_condition['order_no'] = $order_info['order_no'];
                    $record_condition['sku_id'] = $og['sku_id'];
                    $record_condition['website_id'] = $this->website_id;
                    $is_record = $sku_record_mdl->where($record_condition)->find();
                    $sku_record_arr['uid'] = $order_info['buyer_id'];
                    $sku_record_arr['order_id'] = $order_id;
                    $sku_record_arr['order_no'] = $order_info['order_no'];
                    //获取的渠道商信息
                    $condition_channel['c.website_id'] = $this->website_id;
                    $condition_channel['c.uid'] = $order_info['buyer_id'];
                    $channel_info = $this->getMyChannelInfo($condition_channel);
                    $stock_list = $this->getChannelSkuStore($og['sku_id'], $channel_info['channel_id']);
//                var_dump(objToArr($stock_list));exit;
                    $sku_record_arr['my_channel_id'] = $channel_info['channel_id'];
                    $sku_record_arr['my_channel_weight'] = $channel_info['weight'];
                    $sku_record_arr['channel_info'] = $og['channel_info'];
//                    if($og['channel_info'] != 'platform'){
//                        $condition_channel2['channel_id'] = $og['channel_info'];
//                        $condition_channel2['c.website_id'] = $this->website_id;
//                        $channel_info2 = $this->getMyChannelInfo($condition_channel2);
//                        //被购买人的weight
//                        $sku_record_arr['channel_info_weight'] = $channel_info2['weight'];
//                    }
                    $sku_record_arr['goods_id'] = $og['goods_id'];
                    $sku_record_arr['sku_id'] = $og['sku_id'];
                    $sku_record_arr['total_num'] = $og['num'];
                    $sku_record_arr['num'] = $og['num'];
                    $sku_record_arr['price'] = $og['price'];
                    $sku_record_arr['real_money'] = $og['real_money'];
                    $batch_ratio_record = '';
                    if ($order_info['buy_type'] == 1 && $og['channel_info'] != 'platform') {//采购
                        //根据当前采购的数量去获取 批次id:num:bili
                        $batch_ratio_record = $this->getPurchaseBatchRatio($og['channel_info'], $og['num'], $og['sku_id']);//p1:采购谁  p2:采购数量
                    }
                    $sku_record_arr['batch_ratio_record'] = $batch_ratio_record?:'';
                    $sku_record_arr['shipping_fee'] = $og['shipping_fee']?:0;
                    $sku_record_arr['channel_purchase_discount'] = $channel_info['purchase_discount'];
                    $sku_arr = ['channel_id'=>$og['channel_info'], 'sku_id' => $og['sku_id'], 'price'=>0, 'market_price' => 0];
                    $goods->getChannelSkuPrice($sku_arr);
                    $sku_record_arr['platform_price'] = $sku_arr['price'];
                    //我剩余的所有该sku的库存
                    $sku_record_arr['remain_num'] = $stock_list['stock']+$og['num'];
                    $sku_record_arr['buy_type'] = $order_info['buy_type'];
                    $sku_record_arr['website_id'] = $this->website_id;
                    $sku_record_arr['create_time'] = time();
                    if(!$is_record){
                        $id = $sku_record_mdl->save($sku_record_arr);
                    }else{//这里因为采购的相同的商品有渠道商的和平台的，所以需要循环插入record表，不然会少商品数量
                        $sku_record_arr = [];
                        $sku_record_arr['remain_num'] = $is_record['remain_num']+$og['num'];
                        $sku_record_arr['num'] = $is_record['num']+$og['num'];
                        $sku_record_arr['create_time'] = time();
                        $sku_record_mdl->where($record_condition)->update($sku_record_arr);
                    }
                    //插入进渠道商商品表，自提的不用加到渠道商的商品中
                    if($order_info['buy_type'] == 1){
                        $buyer_id = $order_info['buyer_id'];
                        //获取我的渠道商id
                        $condition_channel['c.website_id'] = $this->website_id;
                        $condition_channel['c.uid'] = $buyer_id;
                        $channel_info = $this->getMyChannelInfo($condition_channel);
                        $buyer_channel_id = $channel_info['channel_id'];
                        $channel_goods_model = new VslChannelGoodsModel();
                        $channel_goods_sku_model = new VslChannelGoodsSkuModel();
                        //通过我的channel_id去查询是否存在这个商品
                        $channel_goods_list = $channel_goods_model->where(['channel_id'=>$buyer_channel_id, 'goods_id'=>$og['goods_id']])->find();
                        //通过我的channel_id去查询是否存在这个商品
                        $channel_goods_sku_list = $channel_goods_sku_model->where(['channel_id'=>$buyer_channel_id, 'sku_id'=>$og['sku_id']])->find();
//                        var_dump($channel_goods_list);var_dump($channel_goods_sku_list);exit;
                        //判断采购的是平台还是渠道商的
                        if($og['channel_info'] == 'platform'){
                            $goods_mdl = new VslGoodsModel();
                            $goods_sku_mdl = new VslGoodsSkuModel();
                            $who_goods_list = $goods_mdl->where(['goods_id'=>$og['goods_id']])->find();
                            $who_goods_sku_list = $goods_sku_mdl->where(['sku_id'=>$og['sku_id']])->find();
                        }else{
                            $who_goods_list = $channel_goods_model->where(['channel_id'=>$og['channel_info'], 'goods_id'=>$og['goods_id']])->find();
                            $who_goods_sku_list = $channel_goods_sku_model->where(['channel_id'=>$og['channel_info'], 'sku_id'=>$og['sku_id']])->find();
                        }
                        if($channel_goods_list){
                            if($channel_goods_list['goods_spec_format'] != $who_goods_list['goods_spec_format']){
                                $channel_goods_list->goods_spec_format = $who_goods_list['goods_spec_format'];
                            }
                            $channel_goods_list->stock = $channel_goods_list['stock'] + $og['num'];
                            $channel_goods_list->update_time = time();
                            $channel_goods_list->save();
                        }else{
                            $goods_arr = objToArr($who_goods_list);
                            $new_goods_arr['goods_id'] = $goods_arr['goods_id'];
                            $new_goods_arr['channel_id'] = $channel_info['channel_id'];
                            $new_goods_arr['goods_name'] = $goods_arr['goods_name'];
                            $new_goods_arr['shop_id'] = $goods_arr['shop_id'];
                            $new_goods_arr['category_id'] = $goods_arr['category_id'];
                            $new_goods_arr['category_id_1'] = $goods_arr['category_id_1'];
                            $new_goods_arr['category_id_2'] = $goods_arr['category_id_2'];
                            $new_goods_arr['category_id_3'] = $goods_arr['category_id_3'];
                            $new_goods_arr['brand_id'] = $goods_arr['brand_id'];
                            $new_goods_arr['group_id_array'] = $goods_arr['group_id_array'];
                            $new_goods_arr['goods_type'] = $goods_arr['goods_type'];
                            $new_goods_arr['market_price'] = $goods_arr['market_price'];
                            $new_goods_arr['price'] = $goods_arr['price'];
                            $new_goods_arr['promotion_price'] = $goods_arr['promotion_price'];
                            $new_goods_arr['cost_price'] = $goods_arr['cost_price'];
                            $new_goods_arr['is_member_discount'] = $goods_arr['is_member_discount'];
                            $new_goods_arr['shipping_fee'] = $goods_arr['shipping_fee'];
                            $new_goods_arr['shipping_fee_id'] = $goods_arr['shipping_fee_id'];
                            $new_goods_arr['max_buy'] = $goods_arr['max_buy'];
                            $new_goods_arr['clicks'] = 0;
                            $new_goods_arr['min_stock_alarm'] = $goods_arr['min_stock_alarm'];
                            $new_goods_arr['sales'] = 0;
                            $new_goods_arr['collects'] = $goods_arr['collects'];
                            $new_goods_arr['star'] = $goods_arr['star'];
                            $new_goods_arr['evaluates'] = $goods_arr['evaluates'];
                            $new_goods_arr['shares'] = 0;
                            $new_goods_arr['province_id'] = $goods_arr['province_id'];
                            $new_goods_arr['city_id'] = $goods_arr['city_id'];
                            $new_goods_arr['picture'] = $goods_arr['picture'];
                            $new_goods_arr['keywords'] = $goods_arr['keywords'];
                            $new_goods_arr['introduction'] = $goods_arr['introduction'];
                            $new_goods_arr['description'] = $goods_arr['description'];
                            $new_goods_arr['QRcode'] = $goods_arr['QRcode'];
                            $new_goods_arr['code'] = $goods_arr['code'];
                            $new_goods_arr['is_stock_visible'] = $goods_arr['is_stock_visible'];
                            $new_goods_arr['is_hot'] = $goods_arr['is_hot'];
                            $new_goods_arr['is_recommend'] = $goods_arr['is_recommend'];
                            $new_goods_arr['is_new'] = $goods_arr['is_new'];
                            $new_goods_arr['is_pre_sale'] = 0;
                            $new_goods_arr['is_bill'] = $goods_arr['is_bill'];
                            $new_goods_arr['state'] = $goods_arr['state'];
                            $new_goods_arr['img_id_array'] = $goods_arr['img_id_array'];
                            $new_goods_arr['sku_img_array'] = $goods_arr['sku_img_array'];
                            $new_goods_arr['match_point'] = $goods_arr['match_point'];
                            $new_goods_arr['match_ratio'] = $goods_arr['match_ratio'];
                            $new_goods_arr['real_sales'] = 0;
                            $new_goods_arr['goods_attribute_id'] = $goods_arr['goods_attribute_id'];
                            $new_goods_arr['goods_spec_format'] = $goods_arr['goods_spec_format'];
                            $new_goods_arr['goods_weight'] = $goods_arr['goods_weight'];
                            $new_goods_arr['goods_volume'] = $goods_arr['goods_volume'];
                            $new_goods_arr['shipping_fee_type'] = $goods_arr['shipping_fee_type'];
                            $new_goods_arr['extend_category_id'] = $goods_arr['extend_category_id'];
                            $new_goods_arr['extend_category_id_1'] = $goods_arr['extend_category_id_1'];
                            $new_goods_arr['extend_category_id_2'] = $goods_arr['extend_category_id_2'];
                            $new_goods_arr['extend_category_id_3'] = $goods_arr['extend_category_id_3'];
                            $new_goods_arr['supplier_id'] = $goods_arr['supplier_id'];
                            $new_goods_arr['sale_date'] = 0;
                            $new_goods_arr['min_buy'] = $goods_arr['min_buy'];
                            $new_goods_arr['website_id'] = $goods_arr['website_id'];
                            $new_goods_arr['item_no'] = $goods_arr['item_no'];
                            $new_goods_arr['stock'] = $og['num'];
                            $new_goods_arr['create_time'] = time();
                            $res = $channel_goods_model->save($new_goods_arr);
                        }
                        //插入goods_sku表
                        if($channel_goods_sku_list){
                            $channel_goods_sku_list->stock = $channel_goods_sku_list['stock'] + $og['num'];
                            $channel_goods_sku_list->update_date = time();
                            $channel_goods_sku_list->save();
//                        echo $channel_goods_model->getLastSql();exit;
                        }else{
                            $goods_sku_arr = objToArr($who_goods_sku_list);
//                        echo '<pre>';print_r($goods_sku_arr);exit;
                            $new_goods_sku_arr['sku_id'] = $goods_sku_arr['sku_id'];
                            $new_goods_sku_arr['channel_id'] = $channel_info['channel_id'];
                            $new_goods_sku_arr['goods_id'] = $goods_sku_arr['goods_id'];
                            $new_goods_sku_arr['sku_name'] = $goods_sku_arr['sku_name'];
                            $new_goods_sku_arr['attr_value_items'] = $goods_sku_arr['attr_value_items'];
                            $new_goods_sku_arr['attr_value_items_format'] = $goods_sku_arr['attr_value_items_format'];
                            $new_goods_sku_arr['market_price'] = $goods_sku_arr['market_price'];
                            $new_goods_sku_arr['price'] = $goods_sku_arr['price'];
                            $new_goods_sku_arr['sku_sales'] = 0;
                            $new_goods_sku_arr['promote_price'] = $goods_sku_arr['promote_price'];
                            $new_goods_sku_arr['cost_price'] = $goods_sku_arr['cost_price'];
                            $new_goods_sku_arr['picture'] = $goods_sku_arr['picture'];
                            $new_goods_sku_arr['code'] = $goods_sku_arr['code'];
                            $new_goods_sku_arr['QRcode'] = $goods_sku_arr['QRcode'];
                            $new_goods_sku_arr['stock'] = $og['num'];
                            $new_goods_sku_arr['create_date'] = time();
                            $new_goods_sku_arr['website_id'] = $this->website_id;
                            $res1 = $channel_goods_sku_model->save($new_goods_sku_arr);
                        }
                    }
                    //加渠道商的sku销量
                    $goods_calculate = new GoodsCalculate();
                    if($og['channel_info'] == 'platform'){
                        $goods_calculate->addGoodsSales($og['goods_id'], $og['num']);
                    }else{
                        $goods_calculate->addChannelGoodsSales($og['goods_id'], $og['num'], $og['channel_info']);
                        //增加该渠道商sku的销量
                        $goods_calculate->addChannelSkuSales($og['sku_id'], $og['num'], $og['channel_info']);
                    }
                }
            }
            //支付完成就更新升级
            //发放奖金，并插入奖金表
            $this->calculateChannelBonus($order_info,1);
            $this->updateChannelLevel($order_info['buyer_id'], $order_info['website_id']);
            $this->order->commit();
            return 1;
        } catch (\Exception $e) {
            $this->order->rollback();
            Log::write('订单支付出错' . $e->getMessage());
            return $e->getMessage();
        }
    }
    /*
     * 获取采购批次比例
     * **/
    public function getPurchaseBatchRatio($channel_info, $num, $sku_id, $website_id=0)//p1:采购谁  p2:采购数量
    {
        try{
            //获取采购谁的所有批次比例
            $website_id = $website_id?:$this->website_id;
            $record_mdl = new VslChannelOrderSkuRecordModel();
            $record_list = $record_mdl->getQuery(['my_channel_id'=>$channel_info, 'sku_id'=>$sku_id, 'buy_type'=>'1', 'is_sell'=>0, 'website_id'=>$website_id], 'record_id, num, channel_purchase_discount', 'create_time ASC');
//        p($record_list);exit;
            if($record_list){
                $new_batch_ratio_record = [];
                foreach($record_list as $k=>$v){
                    $batch_num = $v['num'];
                    $is_num = $num - $batch_num;
                    if( $is_num >= 0 ){
                        $num = $num - $batch_num;
                        $new_batch_ratio_record[] = $v['record_id'].':'.$v['num'].':'.$v['channel_purchase_discount'];
                        //将这个批次的采购商品卖出的的状态设为已卖出
                        $data['record_id'] = $v['record_id'];
                        $data['is_sell'] = 1;
                        $data['num'] = 0;
                        $record_mdl->data($data, true)->isUpdate(true)->save();
                    }elseif($is_num < 0 && $num > 0){
                        $new_batch_ratio_record[] = $v['record_id'].':'.$num.':'.$v['channel_purchase_discount'];
                        $data['record_id'] = $v['record_id'];
                        $data['num'] = $batch_num - $num;
                        $record_mdl->isUpdate(true)->save($data);
                        $num = -1;
                    }
                }
            }else{//如果不存在，则说明从来没有采购过，说明应该取当前的比例
                $channel_mdl = new VslChannelModel();
                $channel_info = $channel_mdl->alias('c')
                    ->join('vsl_channel_level cl', 'c.channel_grade_id = cl.channel_grade_id', 'left')
                    ->where(['c.channel_id' => $channel_info, 'c.website_id' => $website_id])
                    ->find();
                $new_batch_ratio_record_str = '0:'.$num.':'.$channel_info['purchase_discount'];
                return $new_batch_ratio_record_str;
            }
            $new_batch_ratio_record_str = implode(';', $new_batch_ratio_record);
            return $new_batch_ratio_record_str;
        }catch(\Exception $e){
            echo $e->getMessage();exit;
        }

    }
    /**
     *
     * 根据渠道商外部交易号查询订单状态
     */
    public function getOrderStatusByOutTradeNo($out_trade_no)
    {
        if (!empty($out_trade_no)) {
            $order_status = $this->order->getInfo([
                'out_trade_no' => $out_trade_no
            ], 'order_status', '');
            return $order_status;
        }
        return 0;
    }
    /**
     * 渠道商采购平台订单支付时处理 平台的账户
     * @param string $order_out_trade_no
     * @param number $order_id
     */
//    public function dealChannelPlatformAccount($order_out_trade_no = "", $order_id = 0, $channel_info)
    public function dealChannelPlatformAccount($order_out_trade_no = "", $order_id = 0)
    {
        if ($order_out_trade_no != "" && $order_id == 0) {
//            $condition = " out_trade_no=" . $order_out_trade_no;
            $condition['out_trade_no'] = $order_out_trade_no;
            $order_list = $this->order->alias('co')->where($condition)->field("*")->join('vsl_channel_order_goods cog','co.order_id=cog.order_id','LEFT')->select();
            foreach ($order_list as $k => $v) {
                $this->updateAccountOrderPay($v["order_id"], $v['channel_info'], $v);
            }
        }
    }
    /**
     * 订单支付成功后处理 平台账户
     * @param unknown $orderid
     */
    private function updateAccountOrderPay($order_id, $channel_info, $order_goods_info)
    {
//        $order_model = new VslOrderModel();
        $shop_account = new ShopAccount();
//        $order = new OrderBusiness();
        $this->order->startTrans();
        try {
//            $order_obj = $this->order->getInfo(['order_id' => $order_id], '*');
            // 订单的实际付款金额
            $pay_money = $order_goods_info['num']*$order_goods_info['price'];
            // 订单的支付方式
            $payment_type = $order_goods_info["payment_type"];
            // 店铺id
            $shop_id = $order_goods_info["shop_id"];
            // 订单号
            $order_no = $order_goods_info["order_no"];
            // 用户id
            $uid = $order_goods_info["buyer_id"];
            //判断是向平台采购还是向渠道商采购
            if($channel_info == 'platform'){
                if ($payment_type != ORDER_REFUND_STATUS) {
                    // 在线支付 处理平台的资金账户
                    if ($payment_type == 5) {
                        $shop_account->updateAccountOrderBalance($pay_money);
                        //26是代表渠道商订单
                        $shop_account->addAccountRecords($shop_id, $uid, '渠道商订单支付', $pay_money, 26, $order_id, "渠道商订单余额支付成功，余额支付总额增加");
                    } elseif ($payment_type == 1) {
                        $shop_account->updateAccountOrderMoney($pay_money);
                        $shop_account->addAccountRecords($shop_id, $uid, '渠道商订单支付', $pay_money, 26, $order_id, "渠道商订单微信支付成功，入账总额增加");
                    } elseif ($payment_type == 2) {
                        $shop_account->updateAccountOrderMoney($pay_money);
                        $shop_account->addAccountRecords($shop_id, $uid, '渠道商订单支付', $pay_money, 26, $order_id, "渠道商订单支付宝支付成功，入账总额增加");
                    }
                    // 添加平台的整体资金流水和订单流水
                    $shop_account->addAccountOrderRecords($shop_id, $pay_money, 26, $order_id, "渠道商订单支付" . $pay_money . "元, 订单号为：" . $order_no . ", 支付方式【在线支付】。", $uid);
                }
            }else{
                if ($payment_type != ORDER_REFUND_STATUS) {
                    $channel_mdl = new VslChannelModel();
                    //通过channel_info获取uid,这个uid是向谁采购的id，如平台或者渠道商
                    $channel_info = $channel_mdl->where(['channel_id'=>$channel_info])->find();
                    $uid = $channel_info['uid'];
                    // 在线支付 处理平台的资金账户
                    $this->updateMemberAccountMoney($pay_money, $uid);
                    if ($payment_type == 5) {
                        //26是代表渠道商订单 $uid, $title, $money, $type_alis_id
                        $this->updateMemberAccountRecords($uid, '渠道商出货订单余额支付成功，余额总额增加', $pay_money, $order_id);
                        sleep(1);//用于记录流水的顺序
                    } elseif ($payment_type == 1) {
                        $this->updateMemberAccountRecords($uid, "渠道商出货订单微信支付成功，余额总额增加", $pay_money, $order_id );
                        sleep(1);
                    } elseif ($payment_type == 2) {
                        $this->updateMemberAccountRecords($uid, '渠道商出货订单支付宝支付成功，余额总额增加', $pay_money,  $order_id);
                        sleep(1);
                    }
                    //用户余额变动商家提醒
                    runhook("Notify", "successfulChannelBonusByTemplate", ["website_id" => $order_goods_info['website_id'], "uid" => $uid, "pay_money" => $pay_money, 'money_channel_type' => 1]);//用户奖金发放用户提醒 // 1-出货零售金额  2-奖金
                    runhook("Notify", "successfulChannelBonusByMpTemplate", ["website_id" => $order_goods_info['website_id'], "uid" => $uid, "pay_money" => $pay_money, 'money_channel_type' => 1]);//小程序用户奖金发放用户提醒
                }
            }
            $this->order->commit();
        } catch (\Exception $e) {
            Log::write("错误updateAccountOrderPay:" . $e->getMessage());
            $this->order->rollback();
        }
    }
    /**
     * 更新渠道商订单->个人账户的订单支付总额
     *
     * @param unknown $money
     */
    public function updateMemberAccountMoney($money, $uid)
    {
        $member_account_model = new VslMemberAccountModel();
        $member_account_list = $member_account_model->getInfo([
            'website_id' => $this->website_id,
            'uid' => $uid
        ]);
        if($member_account_list){
            $data = array(
                "balance" => $member_account_list["balance"] + $money
            );
            $member_account_model->save($data, [
                'website_id' => $this->website_id,
                'uid' => $uid
            ]);
        }else{
            $data = array(
                'website_id' => $this->website_id,
                'uid' => $uid,
                "balance" => $money
            );
            $res = $member_account_model->save($data);
        }
    }
    /**
     * 更新个人账户的订单冻结总额
     *
     * @param unknown $money
     */
    public function updateMemberAccountFreezingBalance($money, $uid)
    {
        $account_model = new VslMemberAccountModel();
        $account_obj = $account_model->getInfo([
            'uid'=>$uid,
            'website_id' => $this->website_id
        ]);
        if($account_obj){
            $data = array(
                "freezing_balance" => $account_obj["freezing_balance"] + $money
            );
            $account_model->save($data, [
                'uid'=>$uid,
                'website_id' => $this->website_id
            ]);
        }else{
            $data = array(
                'website_id' => $this->website_id,
                'uid' => $uid,
                "freezing_balance" => $money
            );
            $account_model->save($data);
        }
    }
    /*
     * 处理渠道商订单完成个人账户金额解冻
     * **/
    public function updateMemberAccountBalance($order_id, $website)
    {
        $order_mdl = new VslOrderModel();
        $channel_goods_info = $order_mdl->alias('o')
            ->where(['o.order_id'=>$order_id,'og.channel_info'=>['neq',0]])
            ->join('vsl_order_goods og','og.order_id=o.order_id','LEFT')
            ->select();
//        $channel_money = (float)$channel_goods_info[0]['channel_money'] - (float)$channel_goods_info[0]['shipping_money'];
        $channel_money = $channel_goods_info[0]['channel_money'];
        $channel = new VslChannelModel();
        //零售对象的uid
        $uid = $channel->getInfo(['channel_id' => $channel_goods_info[0]['channel_info']])['uid'];
        if(!$channel_money && !$uid){
            return false;
        }
        $account_model = new VslMemberAccountModel();
        $account_obj = $account_model->getInfo([
            'uid'=>$uid,
            'website_id' => $website
        ]);
        if($account_obj){
            $data = array(
                "balance" => $account_obj["balance"] + $channel_money,
                "freezing_balance" => $account_obj["freezing_balance"] - $channel_money
            );
            $account_model->save($data, [
                'uid'=>$uid,
                'website_id' => $website
            ]);
        }
        //加入用户账户记录表
        $title = '渠道商订单完成，用户解冻完成，余额增加';
        $this->saveMemberAccountRecords($title, $order_id, 28, 3, $uid,$channel_money);
        //用户余额变动商家提醒
        runhook("Notify", "successfulChannelBonusByTemplate", ["website_id" => $website, "uid" => $uid, "pay_money" => $channel_money, 'money_channel_type' => 1]);//用户奖金发放用户提醒 // 1-出货零售金额  2-奖金
        runhook("Notify", "successfulChannelBonusByMpTemplate", ["website_id" => $website, "uid" => $uid, "pay_money" => $channel_money, 'money_channel_type' => 1]);//小程序用户奖金发放用户提醒

    }
    /**
     * 个人账户 添加渠道商订单的整体资金流水
     *
     * @param unknown $shop_id
     * @param unknown $user_id
     * @param unknown $title
     * @param unknown $money
     * @param unknown $account_type
     * @param unknown $type_alis_id
     * @param unknown $remark
     */
    public function addMemberAccountRecords($uid, $title, $money, $type_alis_id, $website_id, $order_act = 0)
    {
        $account_model = new VslMemberAccountRecordsModel();
        $member_account = new VslMemberAccountModel();
        $balance = $member_account->getInfo(['uid'=> $uid], 'balance')['balance'];
        if($order_act){//如果order_act为1 则说明是退款
            $from_type = 70;
        }else{
            $from_type = 28;
        }
        //添加会员账户流水
        $data = array(
            'records_no' => 'Qd' . getSerialNo(),
            'account_type' => 2,//余额
            'uid' => $uid,
            'sign' => 0,
            'number' => $money,
            'from_type' => $from_type,//27是采购  28是零售
            'data_id' => $type_alis_id,
            'text' => $title,
            'balance' => $balance,
            'create_time' => time(),
            'website_id' => $website_id
        );
        $res = $account_model->save($data);
        return $res;
    }

    public function saveMemberAccountRecords($title, $order_id, $from_type, $status, $uid,$channel_money)
    {
        $account_model = new VslMemberAccountRecordsModel();
        $member_account = new VslMemberAccountModel();
        $balance = $member_account->getInfo(['uid'=> $uid], 'balance')['balance'];
        //添加会员账户流水
        $data = array(
            'text' => $title,
            'create_time' => time(),
            'status' => $status,
            'balance' => $balance,
            'number' => $channel_money
        );
        $res = $account_model->save($data, ['data_id' => $order_id, 'from_type' => $from_type]);
        return $res;
    }

    /*
     * 处理渠道商订单退款去掉解冻金额
     * **/
    public function deleteMemberFreezingAccountBalance($order_id, $order_goods_id, $website)
    {
        $order_mdl = new VslOrderModel();
        $order_goods_mdl = new VslOrderGoodsModel();
        $channel_money = 0;

        $channel_goods_info = $order_mdl->alias('o')
            ->where(['o.order_id'=>$order_id,'og.channel_info'=>['neq',0]])
            ->join('vsl_order_goods og','og.order_id=o.order_id','LEFT')
            ->select();
        if(empty($order_goods_id)) {
            //整单退款
        $channel_money = (float)$channel_goods_info[0]['channel_money'];
            //更新订单表的渠道商金额
            $order_channel_money = 0;
        }else{
            //不是整单退款
            $addons_config_model = new AddonsConfigModel();
            $value = $addons_config_model->Query(['addons' => 'channel','website_id' => $this->website_id],'value')[0];
            $value = json_decode($value,true);
            if (is_string($order_goods_id) || is_int($order_goods_id) ) {
                $order_goods_info = $order_goods_mdl->getInfo(['order_goods_id' => $order_goods_id],'*');
                if($order_goods_info['channel_stock']) {
                    if($value['settle_type'] == 1) {
                        //以商品售价结算 price
                        $channel_money += (float)$order_goods_info['price'] * $order_goods_info['channel_stock'];
                    }elseif ($value['settle_type'] == 2) {
                        //以商品原价结算 market_price
                        $channel_money += (float)$order_goods_info['market_price'] * $order_goods_info['channel_stock'];
                    }elseif ($value['settle_type'] == 3) {
                        //以商品实付价结算 discount_price
                        if($order_goods_info['deduction_money']) {
                            $channel_money += (float)($order_goods_info['actual_price'] - round($order_goods_info['deduction_money'] / $order_goods_info['num'],'2'))  * $order_goods_info['channel_stock'];
                        }else{
                            $channel_money += (float)$order_goods_info['actual_price'] * $order_goods_info['channel_stock'];
                        }
                    }
                    $channel_info = $order_goods_info['channel_info'];
                }
            } else {
            foreach ($order_goods_id as $k => $v) {
                $order_goods_info = $order_goods_mdl->getInfo(['order_goods_id' => $v],'*');
                if($order_goods_info['channel_stock']) {
                    if($value['settle_type'] == 1) {
                        //以商品售价结算 price
                        $channel_money += (float)$order_goods_info['price'] * $order_goods_info['channel_stock'];
                    }elseif ($value['settle_type'] == 2) {
                        //以商品原价结算 market_price
                        $channel_money += (float)$order_goods_info['market_price'] * $order_goods_info['channel_stock'];
                    }elseif ($value['settle_type'] == 3) {
                        //以商品实付价结算 discount_price
                        if($order_goods_info['deduction_money']) {
                            $channel_money += (float)($order_goods_info['actual_price'] - round($order_goods_info['deduction_money'] / $order_goods_info['num'],'2'))  * $order_goods_info['channel_stock'];
                        }else{
                            $channel_money += (float)$order_goods_info['actual_price'] * $order_goods_info['channel_stock'];
                        }
                    }
                    $channel_info = $order_goods_info['channel_info'];
                }
            }
            }
            //更新订单表的渠道商金额
            $order_channel_money = (float)$channel_goods_info[0]['channel_money'] - $channel_money;
        }
        $channel_id = $channel_goods_info[0]['channel_info'] ?: $channel_info;
        $uid = $this->getMyChannelInfo(['channel_id'=>$channel_id])['uid'];
        if(!$channel_money && !$uid){
            return;
        }
        $account_model = new VslMemberAccountModel();
        $account_obj = $account_model->getInfo([
            'uid'=>$uid,
            'website_id' => $website
        ]);
        if($account_obj){
            $freezing_balance = ($account_obj["freezing_balance"] - $channel_money >=0) ? $account_obj["freezing_balance"] - $channel_money : 0;
            $data = array(
                "freezing_balance" => $freezing_balance
            );
            $account_model->save($data, [
                'uid'=>$uid,
                'website_id' => $website
            ]);
        }
        //更新订单表的渠道商金额
        $order_mdl -> save(['channel_money' => $order_channel_money],['order_id' => $order_id,'website_id' => $website]);
        //加入用户账户记录表
        $title = '渠道商订单退货退款，用户解冻金额减少';
        $order_act = 1;
        $this->addMemberAccountRecords($uid, $title, '-'.$channel_money, $order_id, $website, $order_act);
    }
    /**
     * 个人账户 添加渠道商订单的整体资金流水
     *
     * @param unknown $shop_id
     * @param unknown $user_id
     * @param unknown $title
     * @param unknown $money
     * @param unknown $account_type
     * @param unknown $type_alis_id
     * @param unknown $remark
     */
    public function updateMemberAccountRecords($uid, $title, $money, $type_alis_id)
    {
        $account_model = new VslMemberAccountRecordsModel();
        $member_account = new VslMemberAccountModel();
        $balance = $member_account->getInfo(['uid'=> $uid], 'balance')['balance'];
        //添加会员账户流水
        $data = array(
            'records_no' => 'Qd' . getSerialNo(),
            'account_type' => 2,//余额
            'uid' => $uid,
            'sign' => 0,
            'number' => $money,
            'from_type' => 27,
            'data_id' => $type_alis_id,
            'text' => $title,
            'balance' => $balance,
            'create_time' => time(),
            'website_id' => $this->website_id
        );
        $res = $account_model->save($data);
        return $res;
    }
    /*
     * 获取我的业绩
     * **/
    public function getMyChannelPerformance($condition)
    {
        $cos_mdl = new VslChannelOrderSkuRecordModel();
        $cos_list = $cos_mdl->where($condition)->select();
        $cos_arr = objToArr($cos_list);
//        echo '<pre>';print_r($cos_arr);exit;
        $sale_money = 0;
        if(empty($cos_arr)){
            return $sale_money;
        }
        foreach($cos_arr as $k=>$cos){
            $sale_money += $cos['total_num']*$cos['price'];
        }
        return $sale_money;
    }
    /*
     * 获取我的团队的上级、等级
     * **/
    public function getDownChannelInfo($uid)
    {
        //有三个恶心的容易混的字段 我的uid、我团队的uid，团队uid的上级uid
        //获取用户的权重
        $website_id = $this->website_id;
        $condition['c.website_id'] = $website_id;
        $condition['c.uid'] = $uid;
        $channel_info = $this->getMyChannelInfo($condition);
        $weight = $channel_info['weight'];
//        echo '<pre>';print_r($channel_info);exit;
        //获取上级
        $all_up_channel = $this->myAllRefereeChannel($uid,$weight);
        $channel_up_id = $all_up_channel[0];
        if($channel_up_id == 'platform'){
            $shop_name = '自营店';
            if(getAddons('shop', $website_id)){
                $shop_model = new VslShopModel();
                $shop_info = $shop_model::get(['shop_id' => 0, 'website_id' => $website_id]);
                $shop_name = $shop_info['shop_name'];
            }
            $channel_up_arr['up_channel_name'] = $shop_name;
        }else{
            $condition2['c.website_id'] = $website_id;
            $condition2['c.channel_id'] = $channel_up_id;
            $channel_user_info = $this->getChannelName($condition2);
            $channel_up_arr['up_channel_name'] = $channel_user_info['nick_name']?:($channel_user_info['user_name']?:$channel_user_info['user_tel']);
        }
        //获取我团队渠道商等级
        $condition3['c.website_id'] = $website_id;
        $condition3['c.uid'] = $uid;
        $channel_grade_list = $this->getChannelName($condition3);
        $channel_up_arr['grade_name'] = $channel_grade_list['channel_grade_name'];
        $channel_up_arr['my_channel_id'] = $channel_grade_list['channel_id'];
        $channel_up_arr['uid'] = $uid;
        return $channel_up_arr;
    }
    /*
     * 获取渠道商的名字
     * **/
    public function getChannelName($condition)
    {
        $channel_mdl = new VslChannelModel();
        $channel_user_info = $channel_mdl->alias('c')
            ->field('c.channel_id,c.channel_real_name,c.channel_phone,u.uid,u.user_name,u.nick_name,u.user_tel,cl.channel_grade_name,cl.weight')
            ->join('sys_user u','c.uid=u.uid','LEFT')
            ->join('vsl_channel_level cl','c.channel_grade_id = cl.channel_grade_id', 'LEFT')
            ->where($condition)
            ->find();
        return $channel_user_info;
    }

    /*
     * 获取渠道商订单详情
     * **/
    public function getChannelOrderDetail($page_index=1, $page_size, $condition, $condition1, $order)
    {
        $channel_order_mdl = new VslChannelOrderModel();
        /*$user_mdl = new VslMemberModel();*/
        $offset = ($page_index-1)*$page_size;
        $count_list = $channel_order_mdl->alias('co')
            ->join('vsl_channel_order_goods cog','co.order_id=cog.order_id','LEFT')
            ->where($condition)
            ->where(function ($q) use($condition1) {
                $q->whereOr($condition1);
            })
            ->group('co.order_id')
            ->select();
        //获取order_id用于分页
        $order_id_list = $channel_order_mdl->alias('co')
            ->field('co.order_id')
            ->join('vsl_channel_order_goods cog','co.order_id=cog.order_id','LEFT')
            ->where($condition)
            ->where(function ($q) use($condition1) {
                $q->whereOr($condition1);
            })
            ->group('co.order_id')
            ->order('co.order_id DESC')
            ->limit($offset,$page_size)
            ->select();
        $order_ids = '';
        foreach($order_id_list as $k=>$v){
            $order_ids .= $v['order_id'].',';
        }
        $order_ids = trim($order_ids,',');
        $condition['co.order_id'] = ['in',$order_ids];
        $count = count($count_list);
        $page_count = ceil($count/$page_size);
        $channel_order_list = $channel_order_mdl->alias('co')
            ->field('co.order_no, co.out_trade_no, co.order_id, cog.goods_name, co.order_status, cog.sku_name, co.pay_money, cog.num, cog.price, cog.shipping_fee, co.pay_status, co.payment_type, co.shipping_status, cog.channel_info, ap.pic_cover, co.buyer_id, co.shop_id, cog.price')
            ->join('vsl_channel_order_goods cog','co.order_id=cog.order_id', 'LEFT')
            ->join('vsl_goods cg', 'cg.goods_id = cog.goods_id', 'LEFT')
            ->join('sys_album_picture ap','cg.picture=ap.pic_id', 'LEFT')
            ->where($condition)
            ->where(function ($q) use($condition1) {
                $q->whereOr($condition1);
            })
            ->order($order)
            ->select();
        $channel_order_arr = objToArr($channel_order_list);
        //处理商品所属平台名称、采购于谁
        $website_id = $this->website_id;
        $shop_name = '自营店';
        if(getAddons('shop', $website_id)){
            $shop_model = new VslShopModel();
            $shop_info = $shop_model::get(['shop_id' => 0, 'website_id' => $website_id]);
            $shop_name = $shop_info['shop_name'];
        }
        foreach($channel_order_arr as $k=>$order){
            $channel_info = $order['channel_info'];
//            var_dump($channel_info);exit;
            $new_channel_order_arr[$order['order_no']]['goods_list'][$k] = $order;
            if($channel_info == 'platform'){
                $new_channel_order_arr[$order['order_no']]['goods_list'][$k]['purchase_to'] = $shop_name;
                $new_channel_order_arr[$order['order_no']]['goods_list'][$k]['goods_img'] = getApiSrc($order['pic_cover']);
                $new_channel_order_arr[$order['order_no']]['order_no'] = $order['order_no'];
                $new_channel_order_arr[$order['order_no']]['order_id'] = $order['order_id'];
                $new_channel_order_arr[$order['order_no']]['shop_name'] = $shop_name;
                $new_channel_order_arr[$order['order_no']]['shop_id'] = 0;
                $new_channel_order_arr[$order['order_no']]['website_id'] = $this->website_id;
                $new_channel_order_arr[$order['order_no']]['pay_status'] = $order['pay_status'];
                $new_channel_order_arr[$order['order_no']]['order_status'] = $order['order_status'];
                $new_channel_order_arr[$order['order_no']]['pay_money'] = $order['pay_money'];
                $new_channel_order_arr[$order['order_no']]['payment_type'] = $order['payment_type'];
            }else{
                //谁采购的
                $buyer_id = $order['buyer_id'];
                $who_purchase_condition['u.website_id'] = $website_id;
                $who_purchase_condition['u.uid'] = $buyer_id;
                $who_purchase_info = $this->getChannelName($who_purchase_condition);
                $new_channel_order_arr[$order['order_no']]['who_purchase'] = $who_purchase_info['nick_name']?:($who_purchase_info['user_name']?:$who_purchase_info['user_tel']);
                //获取出货时，那个采购我货物人的上级
                //先获取所有的权重等级
//                $all_channel_grade_info = $this->getchannelGradeList();
//                $all_channel_grade_arr = objToArr($all_channel_grade_info);
                //获得上级的渠道商id，获取商品信息
                $who_purchase_weight = $who_purchase_info['weight'];
//                $all_weight = array_column($all_channel_grade_arr,'weight');
                //获取当前等级的上一级的权重
//                $up_grade_weight = $this->getUpChannelGrade($who_purchase_weight,$all_weight);
                $channel_name = $this->myRefereeChannel($buyer_id, $who_purchase_weight);
                if($channel_name == 'platform'){
                    $new_channel_order_arr[$order['order_no']]['who_purchase_grade'] = '总店';
                }else{
                    $who_purchase_grade_condition['c.channel_id'] = $channel_name;
                    $who_purchase_grade_condition['c.website_id'] = $this->website_id;
                    $who_purchase_grade_info = $this->getChannelName($who_purchase_grade_condition);
                    $new_channel_order_arr[$order['order_no']]['who_purchase_grade'] = $who_purchase_grade_info['nick_name']?:($who_purchase_grade_info['user_name']?:$who_purchase_grade_info['user_tel']);
                }
                $condition_channel['c.website_id'] = $website_id;
                $condition_channel['c.channel_id'] = $channel_info;
                $channel_user_info = $this->getChannelName($condition_channel);
//                var_dump(objToArr($channel_name_info));exit;
                $new_channel_order_arr[$order['order_no']]['goods_list'][$k]['purchase_to'] = $channel_user_info['nick_name']?:($channel_user_info['user_name']?:$channel_user_info['user_tel']);
                $new_channel_order_arr[$order['order_no']]['goods_list'][$k]['goods_img'] = getApiSrc($order['pic_cover']);
                $new_channel_order_arr[$order['order_no']]['order_no'] = $order['order_no'];
                $new_channel_order_arr[$order['order_no']]['order_id'] = $order['order_id'];
                $new_channel_order_arr[$order['order_no']]['shop_name'] = $shop_name;
                $new_channel_order_arr[$order['order_no']]['shop_id'] = 0;
                $new_channel_order_arr[$order['order_no']]['website_id'] = $this->website_id;
                $new_channel_order_arr[$order['order_no']]['pay_status'] = $order['pay_status'];
                $new_channel_order_arr[$order['order_no']]['order_status'] = $order['order_status'];
                $new_channel_order_arr[$order['order_no']]['pay_money'] += $order['num'] * $order['price'];
                $new_channel_order_arr[$order['order_no']]['payment_type'] = $order['payment_type'];
            }
        }
        if(!$new_channel_order_arr){
            return [
                    'code'=>0,'message'=>'获取成功',
                    'data' => [
                        'data' => [],
                        'total_count' => 0,
                        'page_count' => 1
                    ]
                ];
        }
        $new_channel_order_arr = array_values($new_channel_order_arr);
        foreach($new_channel_order_arr as $k=>$new_og){
            $new_channel_order_arr[$k]['goods_list'] = array_values($new_channel_order_arr[$k]['goods_list']);
        }
        return [
            'code'=>0,'message'=>'获取成功',
            'data' => [
                'data'=>objToArr($new_channel_order_arr),
                'total_count' => $count,
                'page_count' => $page_count
            ]
        ];
//        echo '<pre>';print_r($new_channel_order_arr);exit;
    }
    /*
     * 获取上一级的channel_id
     * **/
//    public function getUpChannelGrade($my_weight,$all_weight)
//    {
//        foreach($all_weight as $k=>$v){
//            if($v == $my_weight){
//                $my_key = $k;
//            }
//        }
//        $upGradeWeight = $all_weight[$my_key+1];
//        if($upGradeWeight){
//            return $upGradeWeight;
//        }else{
//            return 'platform';
//        }
//    }
    /*
     * 获取零售订单
     * **/
    Public function getChanneRetaillOrderDetail($page_index, $page_size, $condition, $order,$search_text,$channel_info)
    {
        $order_goods_mdl = new VslOrderGoodsModel();
        $order_mdl = new VslOrderModel();
        $channel_mdl = new VslChannelModel();
        $album_mdl = new AlbumPictureModel();

        if($search_text) {
            $condition['order_no|shop_name'] = ['like','%'.$search_text.'%'];
        }

        $order_list = $order_mdl->getQuery($condition,'order_id',$order);
        if(empty($order_list)) {
            if($search_text && !is_numeric($search_text)) {
                $condition1 = [
                    'goods_name' =>  ['like','%'.$search_text.'%'],
                    'channel_info' => $channel_info
                ];
                $order_goods_list = $order_goods_mdl->getQuery($condition1,'order_id','order_id DESC');
                if($order_goods_list) {
                    $order_id_list = '';
                    foreach ($order_goods_list as $k =>$v) {
                        $order_id_list .= $v['order_id'].',';
                    }
                    $order_id_list = trim($order_id_list,',');
                    $condition2 = [
                        'order_id' => ['IN',$order_id_list],
                        'channel_money' => ['<>',0],
                        'buy_type' => 0,
                    ];
                    if($condition['order_status']) {
                        $condition2['order_status'] = $condition['order_status'];
                    }
                    $order_list = $order_mdl->getQuery($condition2,'order_id',$order);
                }
            }
        }

        $new_channel_order_arr = [];

        if($order_list) {
            $order_ids = '';
            foreach($order_list as $k=>$v){
                $order_ids .= $v['order_id'].',';
            }
            $order_ids = trim($order_ids,',');
            $condition3 =  [
                'order_id' => ['IN',$order_ids],
                'channel_info' => $channel_info,
            ];
            $data = $order_goods_mdl->pageQuery($page_index, $page_size, $condition3,'order_id DESC','*');
            $order_goods_arr = $data['data'];

            foreach($order_goods_arr as $k => $order_goods){
                //主订单
                $order_info = $order_mdl->getInfo(['order_id' => $order_goods['order_id']],'*');

                if($order_info['order_type'] == 7){//预售
                    if($order_info['money_type'] == 0){
                        $new_channel_order_arr[$order_info['order_no']]['pay_money'] += $order_info['pay_money'];
                    }elseif($order_info['money_type'] == 1){
                        $new_channel_order_arr[$order_info['order_no']]['pay_money'] += $order_info['final_money'];
                    }else{
                        $new_channel_order_arr[$order_info['order_no']]['pay_money'] += ($order_goods['channel_stock']?:$order_goods['num']) * $order_goods['discount_price'] + $order_goods['shipping_fee'];
                    }
                }else{
                    $new_channel_order_arr[$order_info['order_no']]['pay_money'] += ($order_goods['channel_stock']?:$order_goods['num']) * $order_goods['actual_price'] + $order_goods['shipping_fee'];
                }

                $new_channel_order_arr[$order_info['order_no']]['order_no'] =  $order_info['order_no'];
                $new_channel_order_arr[$order_info['order_no']]['order_id'] =  $order_info['order_id'];

                //零售的应该是显示的渠道商的信息
                $channel_id = $order_goods['channel_info'];
                $channel_user_info = $channel_mdl->alias('c')->where(['channel_id'=>$channel_id])->join('sys_user u', 'c.uid = u.uid', 'LEFT')->find();
                $shop_name = $channel_user_info['nick_name']?:($channel_user_info['user_name']?:$channel_user_info['user_tel']);
                $new_channel_order_arr[$order_info['order_no']]['shop_name'] =  $shop_name;
                $new_channel_order_arr[$order_info['order_no']]['shop_id'] =  0;
                $new_channel_order_arr[$order_info['order_no']]['pay_status'] = $order_info['pay_status'];
                $new_channel_order_arr[$order_info['order_no']]['order_status'] = $order_info['order_status'];
                $new_channel_order_arr[$order_info['order_no']]['goods_list'][$k] = $order_goods;
                $order_goods['pic_cover'] = $album_mdl->Query(['pic_id' => $order_goods['goods_picture']],'pic_cover')[0];
                $new_channel_order_arr[$order_info['order_no']]['goods_list'][$k]['goods_img'] = getApiSrc($order_goods['pic_cover']);
                $new_channel_order_arr[$order_info['order_no']]['goods_list'][$k]['num'] = $order_goods['channel_stock']?:$order_goods['num'];;
            }
        }

        $new_channel_order_arr = array_values($new_channel_order_arr);
        foreach($new_channel_order_arr as $k=>$new_og){
            $new_channel_order_arr[$k]['goods_list'] = array_values($new_channel_order_arr[$k]['goods_list']);
        }
        return [
            'code'=>0,'message'=>'获取成功',
            'data' => [
                'data' => $new_channel_order_arr,
                'total_count' => $data['total_count']?:0,
                'page_count' => $data['page_count']?:0
            ]
        ];
    }

    /*
     * 获取提货订单
     * **/
    Public function getChannelPickOrderDetailList($page_index, $page_size, $condition, $condition1, $order)
    {
        $order_mdl = new VslOrderModel();
        $count_list = $order_mdl->alias('o')
            ->where($condition)
            ->where(function ($q) use($condition1) {
                $q->whereOr($condition1);
            })
            ->join('vsl_order_goods og','o.order_id = og.order_id', 'LEFT')
            ->group('o.order_id')
            ->select();
//            echo $order_goods_mdl->getLastSql();exit;
        $count = count($count_list);
//        var_dump($count);exit;
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        //获取order_id用于分页
        $order_id_list = $order_mdl->alias('o')
            ->where($condition)
            ->where(function ($q) use($condition1) {
                $q->whereOr($condition1);
            })
            ->join('vsl_order_goods og','o.order_id = og.order_id', 'LEFT')
            ->group('o.order_id')
            ->order('o.order_id DESC')
            ->limit($offset,$page_size)
            ->select();
        $order_ids = '';
        foreach($order_id_list as $k=>$v){
            $order_ids .= $v['order_id'].',';
        }
        $order_ids = trim($order_ids,',');
        $condition['o.order_id'] = ['in',$order_ids];
        $channel_id = $this->getMyChannelInfo(['c.uid'=>$this->uid])['channel_id'];
        $condition['g.channel_id'] = $channel_id;
        $order_goods_list = $order_mdl->alias('o')
            ->field('o.shop_name,o.order_no,g.goods_name,og.sku_name,og.goods_id,o.order_id,og.goods_id,og.sku_id,og.num,og.price,og.shipping_fee,o.order_status,o.pay_status,og.channel_info,ap.pic_cover,o.payment_type,o.shipping_money,og.real_money')
            ->where($condition)
            ->where(function ($q) use($condition1) {
                $q->whereOr($condition1);
            })
            ->join('vsl_order_goods og', 'og.order_id = o.order_id', 'LEFT')
            ->join('vsl_channel_goods g', 'og.goods_id = g.goods_id', 'LEFT')
            ->join('sys_album_picture ap','g.picture=ap.pic_id', 'LEFT')
            ->order($order)
            ->select();
        $order_goods_arr = objToArr($order_goods_list);
//        echo $order_mdl->getLastSql();
//        echo '<pre>';print_r($order_goods_arr);exit;
        //处理商品所属平台名称、采购于谁
        $website_id = $this->website_id;
        $shop_name = '自营店';
        if(getAddons('shop', $website_id)){
            $shop_model = new VslShopModel();
            $shop_info = $shop_model::get(['shop_id' => 0, 'website_id' => $website_id]);
            $shop_name = $shop_info['shop_name'];
        }
        //算订单金额
        $new_channel_order_arr = [];
        foreach($order_goods_arr as $k=>$order_goods){
            $new_channel_order_arr[$order_goods['order_no']]['pay_money'] +=  $order_goods['real_money'];
            $new_channel_order_arr[$order_goods['order_no']]['order_no'] =  $order_goods['order_no'];
            $new_channel_order_arr[$order_goods['order_no']]['order_id'] =  $order_goods['order_id'];
            $new_channel_order_arr[$order_goods['order_no']]['shop_name'] =  $shop_name;
            $new_channel_order_arr[$order_goods['order_no']]['shop_id'] =  0;
            $new_channel_order_arr[$order_goods['order_no']]['pay_status'] = $order_goods['pay_status'];
            $new_channel_order_arr[$order_goods['order_no']]['order_status'] = $order_goods['order_status'];
            $new_channel_order_arr[$order_goods['order_no']]['payment_type'] = $order_goods['payment_type'];
            $new_channel_order_arr[$order_goods['order_no']]['goods_list'][$k] = $order_goods;
            $new_channel_order_arr[$order_goods['order_no']]['goods_list'][$k]['goods_img'] = getApiSrc($order_goods['pic_cover']);
        }
        $new_channel_order_arr = array_values($new_channel_order_arr);
        foreach($new_channel_order_arr as $k=>$new_og){
            $new_channel_order_arr[$k]['goods_list'] = array_values($new_channel_order_arr[$k]['goods_list']);
        }
        return [
            'code'=>0,'message'=>'获取成功',
            'data' => [
                'data' => objToArr($new_channel_order_arr),
                'total_count' => $count,
                'page_count' => $page_count
            ]
        ];
//        echo '<pre>';print_r($new_channel_order_arr);exit;
    }
    /*
     * 获取采购某一订单详情
     * **/
    public function getChannelSingleOrderDetail($condition,$order_type)
    {
        $channel_order_mdl = new VslChannelOrderModel();
        $order_status_server = new OrderStatus();
        $channel_order_detail_list = $channel_order_mdl->alias('co')
            ->field('cog.*,co.*,ap.pic_cover')
            ->join('vsl_channel_order_goods cog','co.order_id=cog.order_id','LEFT')
            ->join('vsl_goods g', 'cog.goods_id = g.goods_id', 'LEFT')
            ->join('sys_album_picture ap','g.picture=ap.pic_id')
            ->where($condition)
            ->select();
//        echo $channel_order_mdl->getLastSql();exit;
        $channel_order_detail = objToArr($channel_order_detail_list);
//        echo '<Pre>';print_r($channel_order_detail);exit;
        $new_detail_arr = [];
        //店铺名字
        $website_id = $this->website_id;
        $shop_name = '自营店';
        if(getAddons('shop', $this->website_id)){
            $shop_model = new VslShopModel();
            $shop_info = $shop_model::get(['shop_id' => 0, 'website_id' => $this->website_id]);
            $shop_name = $shop_info['shop_name'];
        }
        foreach($channel_order_detail as $k=>$og_detail){
            //谁采购的
            $buyer_id = $og_detail['buyer_id'];
            $who_purchase_condition['c.website_id'] = $website_id;
            $who_purchase_condition['c.uid'] = $buyer_id;
            $who_purchase_info = $this->getChannelName($who_purchase_condition);
            $new_detail_arr['who_purchase'] = $who_purchase_info['nick_name']?:($who_purchase_info['user_name']?:$who_purchase_info['user_tel']);
            //获得上级的渠道商id，获取商品信息
            $who_purchase_weight = $who_purchase_info['weight'];
            //获取当前等级的上一级的权重
            $channel_name = $this->myRefereeChannel($buyer_id, $who_purchase_weight);
            if($channel_name == 'platform'){
                $new_detail_arr['who_purchase_grade'] = '总店';
            }else{
                $who_purchase_grade_condition['c.channel_id'] = $channel_name;
                $who_purchase_grade_condition['c.website_id'] = $this->website_id;
                $who_purchase_grade_info = $this->getChannelName($who_purchase_grade_condition);
                $new_detail_arr['who_purchase_grade'] = $who_purchase_grade_info['nick_name']?:($who_purchase_grade_info['user_name']?:$who_purchase_grade_info['user_tel']);
            }
            //获取采购于谁
            if($og_detail['channel_info'] == 'platform'){
                $new_detail_arr['order_goods'][$k]['purchase_to'] =  $shop_name?:'';
            }else{
                $condition_channel['c.website_id'] = $website_id;
                $condition_channel['c.channel_id'] = $og_detail['channel_info'];
                $channel_user_info = $this->getChannelName($condition_channel);
//                var_dump(objToArr($channel_name_info));exit;
                $new_detail_arr['order_goods'][$k]['purchase_to'] = $channel_user_info['nick_name']?:($channel_user_info['user_name']?:$channel_user_info['user_tel']);
            }
            //店铺
            $new_detail_arr['shop_name'] = $shop_name;
            $new_detail_arr['order_no'] = $og_detail['order_no'];
            $new_detail_arr['order_id'] = $og_detail['order_id'];
            $new_detail_arr['order_status'] = $og_detail['order_status'];
            $new_detail_arr['pay_status'] = $og_detail['pay_status'];
            $new_detail_arr['order_money'] += $og_detail['price'] * $og_detail['num'];;
            $new_detail_arr['payment_name'] += OrderStatus::getPayType($og_detail['payment_type']);
            $new_detail_arr['create_time'] =  $og_detail['create_time'];
            $new_detail_arr['order_goods'][$k]['goods_name'] =  $og_detail['goods_name'];
            $new_detail_arr['order_goods'][$k]['sku_name'] =  $og_detail['sku_name'];
            $new_detail_arr['order_goods'][$k]['num'] =  $og_detail['num'];
            $new_detail_arr['order_goods'][$k]['price'] =  $og_detail['price'];
            $new_detail_arr['order_goods'][$k]['pic_cover'] =  getApiSrc($og_detail['pic_cover']);
            $order_status = $og_detail['order_status'];
        }
//        var_dump($order_status);exit;
        //tag
        $tag_arr = $order_status_server->getOrderCommonStatus();
        if($order_type == 'purchase'){
            $tag_arr = $tag_arr[$order_status]['channel_purchase_operation'];
        }else{
            $tag_arr = $tag_arr[$order_status]['channel_output_operation'];
        }
        unset($tag_arr[2]);
        $new_detail_arr['member_operation'] = $tag_arr?:[];
        return $new_detail_arr;
    }
    /*
     * 获取自提某一个订单详情
     * **/
    public function getChannelPickOrderDetail($order_id)
    {

        $district_model = new DistrictModel();
        $order_service = new OrderService();
        $order_info = $order_service->getOrderDetail($order_id);
        $order_detail['order_id'] = $order_info['order_id'];
        $order_detail['order_no'] = $order_info['order_no'];
        $order_detail['shop_name'] = $order_info['shop_name'];
        $order_detail['shop_id'] = $order_info['shop_id'];
        $order_detail['order_status'] = $order_info['order_status'];
        $order_detail['payment_type_name'] = $order_info['payment_type_name'];
        $order_detail['promotion_status'] = ($order_info['promotion_money'] + $order_info['coupon_money'] > 0) ?: false;
        $order_detail['order_refund_status'] = reset($order_info['order_goods'])['refund_status'];
        $order_detail['is_evaluate'] = $order_info['is_evaluate'];
        $order_detail['order_money'] = $order_info['order_money'];
        $order_detail['goods_money'] = $order_info['goods_money'];
        $order_detail['shipping_fee'] = $order_info['shipping_money'] - $order_info['promotion_free_shipping'];
        $order_detail['promotion_money'] = 0;

        $address_info = $district_model::get($order_info['receiver_district'], ['city.province']);
        $order_detail['receiver_name'] = $order_info['receiver_name'];
        $order_detail['receiver_mobile'] = $order_info['receiver_mobile'];
        $order_detail['receiver_province'] = $address_info->city->province->province_name;
        $order_detail['receiver_city'] = $address_info->city->city_name;
        $order_detail['receiver_district'] = $address_info->district_name;
        $order_detail['receiver_address'] = $order_info['receiver_address'];
        $order_detail['buyer_message'] = $order_info['buyer_message'];
        $order_detail['create_time'] = $order_info['create_time'];
//        var_dump($order_info['order_status']);exit;
        if($order_info['order_status'] == '2' || $order_info['order_status'] == '0'){
            if ($order_info['payment_type'] == 6 || $order_info['shipping_type'] == 2) {
                $order_status = OrderService\OrderStatus::getSinceOrderStatus()[$order_info['order_status']];
            } else {
                $order_status = OrderService\OrderStatus::getOrderCommonStatus()[$order_info['order_status']];
            }
            $order_detail['member_operation'] = $order_status['member_operation'];
        }elseif($order_info['order_status'] == '5'){
            $order_detail['member_operation'] = [];
        }else{
            $order_detail['member_operation'] = [array(
                'no' => 'evaluation',
                'name' => '评价'
            )];
        }

        $order_detail['no_delivery_id_array'] = [];
        foreach ($order_info['order_goods_no_delive'] as $v_goods){
            $order_detail['no_delivery_id_array'][] = $v_goods['order_goods_id'];
        }

        $goods_packet_list = [];
        foreach ($order_info['goods_packet_list'] as $k => $v_packet){
            $goods_packet_list[$k]['packet_name'] = $v_packet['packet_name'];
//            $goods_packet_list[$k]['express_name'] = $v_packet['express_name'];
//            $goods_packet_list[$k]['express_code'] = $v_packet['express_code'];
            $goods_packet_list[$k]['shipping_info'] = $v_packet['shipping_info'];
            $goods_packet_list[$k]['order_goods_id_array'] = [];
            foreach ($v_packet['order_goods_list'] as $k_o => $v_goods){
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
            $order_goods[$k]['refund_status'] = $v['refund_status'];
            $order_goods[$k]['spec'] = $v['spec'];
            $order_goods[$k]['pic_cover'] = $v['picture_info']['pic_cover'] ? getApiSrc($v['picture_info']['pic_cover']) : '';

            $order_detail['promotion_money'] += round(($v['price'] - $v['actual_price']) * $v['num'], 2) + $v['promotion_free_shipping'];
        }

        $order_detail['order_goods'] = $order_goods;
        return $order_detail;
    }
    /*
     * 获取零售某一个订单详情
     * **/
    public function getChannelRetailOrderDetail($order_id)
    {

        $district_model = new DistrictModel();
        $order_service = new OrderService();
        $channel_status = 'channel_retail';
        $order_info = $order_service->getOrderDetail($order_id, $channel_status);
        $order_detail['order_id'] = $order_info['order_id'];
        $order_detail['order_no'] = $order_info['order_no'];
        $order_detail['shop_name'] = $order_info['shop_name'];
        $order_detail['shop_id'] = $order_info['shop_id'];
        $order_detail['order_status'] = $order_info['order_status'];
        $order_detail['payment_type_name'] = $order_info['payment_type_name'];
        $order_detail['promotion_status'] = ($order_info['promotion_money'] + $order_info['coupon_money'] > 0) ?: false;
        $order_detail['order_refund_status'] = reset($order_info['order_goods'])['refund_status'];
        $order_detail['is_evaluate'] = $order_info['is_evaluate'];
        $order_detail['order_money'] = $order_info['order_money'];
        $order_detail['goods_money'] = $order_info['goods_money'];
        $order_detail['shipping_fee'] = $order_info['shipping_money'] - $order_info['promotion_free_shipping'];
        $order_detail['promotion_money'] = 0;

        $address_info = $district_model::get($order_info['receiver_district'], ['city.province']);
        $order_detail['receiver_name'] = $order_info['receiver_name'];
        $order_detail['receiver_mobile'] = $order_info['receiver_mobile'];
        $order_detail['receiver_province'] = $address_info->city->province->province_name;
        $order_detail['receiver_city'] = $address_info->city->city_name;
        $order_detail['receiver_district'] = $address_info->district_name;
        $order_detail['receiver_address'] = $order_info['receiver_address'];
        $order_detail['buyer_message'] = $order_info['buyer_message'];
        $order_detail['create_time'] = $order_info['create_time'];
//        var_dump($order_info['order_status']);exit;
        $order_detail['member_operation'] = [];

        $order_detail['no_delivery_id_array'] = [];
        foreach ($order_info['order_goods_no_delive'] as $v_goods){
            $order_detail['no_delivery_id_array'][] = $v_goods['order_goods_id'];
        }

        $goods_packet_list = [];
        foreach ($order_info['goods_packet_list'] as $k => $v_packet){
            $goods_packet_list[$k]['packet_name'] = $v_packet['packet_name'];
//            $goods_packet_list[$k]['express_name'] = $v_packet['express_name'];
//            $goods_packet_list[$k]['express_code'] = $v_packet['express_code'];
            $goods_packet_list[$k]['shipping_info'] = $v_packet['shipping_info'];
            $goods_packet_list[$k]['order_goods_id_array'] = [];
            foreach ($v_packet['order_goods_list'] as $k_o => $v_goods){
                $goods_packet_list[$k]['order_goods_id_array'][] = $v_goods['order_goods_id'];
            }
        }
        $order_detail['goods_packet_list'] = $goods_packet_list;

        $order_goods = [];
        if($channel_status == 'channel_retail'){//展示如果是渠道商零售的订单，则将order_price、goods_money置为0，重新算渠道商的价格
            $order_detail['order_money'] = 0;
            $order_detail['goods_money'] = 0;
            $order_detail['shipping_fee'] = 0;
            $order_detail['promotion_money'] = 0;
        }
//        echo '<pre>';print_r(objToArr($order_info['order_goods']));exit;
        foreach ($order_info['order_goods'] as $k => $v) {
            $order_goods[$k]['order_goods_id'] = $v['order_goods_id'];
            $order_goods[$k]['goods_id'] = $v['goods_id'];
            $order_goods[$k]['goods_name'] = $v['goods_name'];
            $order_goods[$k]['sku_id'] = $v['sku_id'];
            $order_goods[$k]['sku_name'] = $v['sku_name'];
            $order_goods[$k]['price'] = $v['price'];
            $order_goods[$k]['num'] = $v['channel_stock']?:$v['num'];
            $order_goods[$k]['refund_status'] = $v['refund_status'];
            $order_goods[$k]['spec'] = $v['spec'];
            $order_goods[$k]['pic_cover'] = $v['picture_info']['pic_cover'] ? getApiSrc($v['picture_info']['pic_cover']) : '';
            if($channel_status == 'channel_retail'){
                $order_detail['goods_money'] += ($v['channel_stock']?:$v['num'])*$v['actual_price'];
                if($order_info['order_type'] == 7 && $order_info['money_type'] == 0){
                    $order_detail['order_money'] = $order_detail['order_money'];
                    $order_detail['shipping_fee'] = 0;
                }elseif($order_info['order_type'] == 7 && $order_info['money_type'] == 1){
                    $order_detail['already_money'] = $order_info['order_money'];
                    $order_detail['order_money'] = $order_info['final_money'];
                    $order_detail['shipping_fee'] += $v['shipping_fee'];
                }elseif($order_info['order_type'] == 7 && $order_info['money_type'] == 2){
                    $order_detail['shipping_fee'] += $v['shipping_fee'];
                }else{
                    $order_detail['shipping_fee'] += $v['shipping_fee'];
                }

            }
            $order_detail['promotion_money'] += round(($v['price'] - $v['actual_price']) * $v['num'], 2) + $v['promotion_free_shipping'];
        }
        if(($order_info['order_type'] == 7 && $order_info['money_type'] == 2) ||  $order_info['order_type'] != 7) {
            $order_detail['order_money'] = $order_detail['goods_money'] + $order_detail['shipping_fee'];
        }

        $order_detail['order_goods'] = $order_goods;
        return $order_detail;
    }
    /*
     * 获取渠道商的自定义表单
     * **/
    public function getCustomForm($website_id=null){
        $add_config = new AddonsConfigService();
        $customform_info =$add_config->getAddonsConfig("customform",$website_id);
        $customform = json_decode($customform_info['value'],true);
        $coupon_model = new CustomServer();
        $custom_form=[];
        if($customform['channel_dealer']==1){
            $custom_form_id =  $customform['channel_id'];
            $custom_form_info = $coupon_model->getCustomFormDetail($custom_form_id)['value'];
            $custom_form['channel'] =  json_decode($custom_form_info,true);
        }
//        echo '<pre>';print_r($custom_form['channel']);exit;
        return $custom_form;
    }
    /*
     * 成为渠道商条件判断
     * **/
    public function getChannelCondition($channel_condition, $channel_con)
    {
        $mark_num = 0;
        $i = 0;
        $website_id = $this->website_id;
        $uid = $this->uid;
        $member_mdl = new VslMemberModel();
        $order_mdl = new VslOrderModel();
        //成为渠道商必须要为分销商
        $distribution_status = $member_mdl->getInfo(['uid'=>$uid], 'isdistributor');
        $isdistributor = $distribution_status['isdistributor'];
        if($isdistributor != 2){
            $to_channel_condition['condition0'] = ['text'=>'必须成为分销商才能申请渠道商','condition'=>'','my_condition'=>''];//不是分销商不能成为渠道商
            $to_channel_condition['to_channel_status'] = 0;
            return $to_channel_condition;
        }
        if(!empty($channel_condition['channel_team'])){
            $i++;
            //分销商团队
            $distribution_condition = $channel_condition['channel_team'];
            $distributor_server = new Distributor();
            $team_num = $distributor_server->getDistributorTeam($uid,$website_id);
            if($team_num>=$distribution_condition){
                $mark_num++;
            }
            $to_channel_condition['condition1'] = ['text'=>'分销商团队人数达到'.$distribution_condition.'人','condition'=>$distribution_condition,'my_condition'=>$team_num];
        }
        if(!empty($channel_condition['pay_money'])){
            $i++;
            //消费金额
            $order_condition['buyer_id'] = $uid;
            $order_condition['website_id'] = $website_id;
            //已完成订单
            $order_condition['order_status'] = 4;
            $pay_money_condition = $channel_condition['pay_money'];
            $my_pay_money_list = $order_mdl->field('sum(order_money) AS order_money')->where($order_condition)->find();
            $my_pay_money = $my_pay_money_list['order_money'];
            if($my_pay_money>=$pay_money_condition){
                $mark_num++;
            }
            $to_channel_condition['condition2'] = ['text'=>'用户累计消费金额达'.$pay_money_condition.'元','condition'=>$pay_money_condition,'my_condition'=>$my_pay_money];
        }
        if(!empty($channel_condition['pay_number'])){
            $i++;
            //消费次数
            $order_condition['buyer_id'] = $uid;
            $order_condition['website_id'] = $website_id;
            //已完成订单
            $order_condition['order_status'] = 4;
            $pay_num_condition = $channel_condition['pay_number'];
            $count = $order_mdl->where($order_condition)->count();
            if($count>=$pay_num_condition){
                $mark_num++;
            }
            $to_channel_condition['condition3'] = ['text'=>'用户累计消费次数达'.$pay_num_condition.'次','condition'=>$pay_num_condition,'my_condition'=>$count];
        }
        if(!empty($channel_condition['goods_id'])){
            $i++;
            //购买指定商品
            $order_condition1['o.buyer_id'] = $uid;
            $order_condition1['o.website_id'] = $website_id;
            //已完成订单
            $order_condition1['o.order_status'] = 4;
            $order_condition1['og.goods_id'] = $channel_condition['goods_id'];
            $buy_goods_name = $channel_condition['goods_name'];
            $order_list = $order_mdl->alias('o')->where($order_condition1)->join('vsl_order_goods og','o.order_id=og.order_id','LEFT')->find();
//        var_dump(objToArr($order_list));exit;
            if($order_list){
                $mark_num++;
            }
            $to_channel_condition['condition4'] = ['text'=>'用户购买指定商品：'.$buy_goods_name,'goods_id'=>$channel_condition['goods_id']];
        }

        if($channel_con == 'all'){
            if($mark_num == $i){
                $to_channel_status = 1;
            }else{
                $to_channel_status = 0;
            }
        }else{
            if($mark_num > 0){
                $to_channel_status = 1;
            }else{
                $to_channel_status = 0;
            }
        }
//        echo '<Pre>';print_r($to_channel_condition);exit;
        $to_channel_condition['to_channel_status'] = $to_channel_status;
        return $to_channel_condition;
    }
    /**
     * 后台设置成为渠道商
     */
    public function setStatus($uid){
        $member = new VslMemberModel();;
        $MicroShop = $member->getInfo(['uid'=>$uid],'*');
        if($this->website_id){
            $website_id = $this->website_id;
        }else{
            $website_id =  $MicroShop['website_id'];
        }
        $channel_grade_mdl = new VslChannelLevelModel();
        $default_channel_grade_id = $channel_grade_mdl->getInfo(['website_id'=>$website_id, 'weight'=>1, 'is_default'=>1],'channel_grade_id')['channel_grade_id'];
        $channel = new VslChannelModel();
        $data = array(
            "website_id" => $website_id,
            "uid" => $uid,
            "status" => 1,
            "channel_grade_id" => $default_channel_grade_id,
            "create_time" => time(),
            "to_channel_time" => time(),
            "channel_custom" => ''
        );
        $result = $channel->save($data);
        return $result;
    }
    /**
     * 申请成为渠道商
     */
    public function addChannelInfo($website_id,$uid,$post_data, $mobile)
    {
        $channel = new VslChannelModel();
        $channel_grade_mdl = new VslChannelLevelModel();
        $info = $this->getChannelConfig();
        $user_info = new UserModel();
        $member_tel = $user_info->getInfo(['uid'=>$uid],'user_tel')['user_tel'];
        if($mobile && $mobile!=$member_tel){
            return -2;
        };
        $condition['website_id'] = $website_id;
        $condition['uid'] = $uid;
        $is_channel = $channel->where($condition)->find();
        if($is_channel){
            return  -4;
        }
        if($info['channel_check']==1){
            $status = 1;
        }else{
            $status = 0;
        }
        //查询出当前website_id平台的默认等级 id
        $default_channel_grade_id = $channel_grade_mdl->getInfo(['website_id'=>$this->website_id, 'weight'=>1, 'is_default'=>1],'channel_grade_id')['channel_grade_id'];
        $data = array(
            "website_id" => $website_id,
            "uid" => $uid,
            "status" => $status,
            "channel_grade_id" => $default_channel_grade_id,
            "create_time" => time(),
            "to_channel_time" => time(),
            "channel_custom" => $post_data
        );
        $result = $channel->save($data);
        return $result;
    }
    /*
     * 更新渠道商的等级
     * **/
    public function updateChannelLevel($buyer_id,$website_id)
    {
        //先判断是否开启了自动升级
        $channel_conf = $this->getChannelConfig();
        $channel_mdl = new VslChannelModel();
        $channel_level = new VslChannelLevelModel();
        $channel_order_mdl = new VslChannelOrderModel();
        //获取我的等级
        $condition_list = $channel_mdl->alias('c')->field('weight,auto_upgrade,down_upgrade_time')->join('vsl_channel_level cl','c.channel_grade_id = cl.channel_grade_id')->where(['uid'=>$buyer_id, 'c.website_id'=>$website_id])->find();
        if (!$condition_list) {
            return 0;
        }
        $mygrade_weight = $condition_list['weight'];
//        var_dump($condition_list['auto_upgrade']);exit;
        if ($channel_conf['is_use'] == '1' && ($condition_list['auto_upgrade'] == '1' || $condition_list['weight'] == '1')) {//开启了自动升降级
            //开启了跳级
            if ($channel_conf['channel_grade'] == '1') {
                //获取我的所有上面的等级
                $up_grade_weight_list = $channel_level->Query(['weight' => ['>',$mygrade_weight], 'website_id' => $website_id],'weight');
                asort($up_grade_weight_list);
                $up_grade_weight_list = array_values($up_grade_weight_list);
                foreach ($up_grade_weight_list as $k => $v) {
                    $mark = [];
                    $up_grade_condition_list = $channel_level->getInfo(['weight' => $v, 'website_id' => $website_id],'channel_grade_id,upgrade_condition,upgrade_val,auto_upgrade');
                    if($up_grade_condition_list['auto_upgrade'] == 1 && $up_grade_condition_list['upgrade_val']){
                        $upgrade_arr = json_decode($up_grade_condition_list['upgrade_val'],true);
                        //获取要求的采购量
                        $condition_quantity = $upgrade_arr['up_total_purchase_num'];
                        $i = 0;
                        if (!empty($condition_quantity)) {
                            $i++;
                            //累计采购量
                            //如果发生降级后 变更统计条件为 down_upgrade_time 之后 ，升级后重置
                            if($condition_list['down_upgrade_time']){
                                $purchase_quantity = $channel_order_mdl->alias('co')->where(['co.buyer_id' => $buyer_id, 'co.website_id' => $website_id, 'co.order_status' => 4,'co.pay_time'=>[">",$condition_list['down_upgrade_time']]])->join('vsl_channel_order_goods cog', 'co.order_id = cog.order_id', 'LEFT')->sum('cog.num');
                            }else{
                                $purchase_quantity = $channel_order_mdl->alias('co')->where(['co.buyer_id' => $buyer_id, 'co.website_id' => $website_id, 'co.order_status' => 4])->join('vsl_channel_order_goods cog', 'co.order_id = cog.order_id', 'LEFT')->sum('cog.num');
                            }
                            if ($purchase_quantity >= $condition_quantity) {
                                $mark[] = 1;
                            }
                        }

                        //获取采购金额
                        $condition_money = $upgrade_arr['up_total_purchase_amount'];
                        if (!empty($condition_money)) {
                            $i++;
                            //累计采购金额
                            if($condition_list['down_upgrade_time']){
                                $purchase_price = $channel_order_mdl->where(['buyer_id' => $buyer_id, 'website_id' => $website_id, 'order_status' => 4,'pay_time'=>[">",$condition_list['down_upgrade_time']]])->sum('pay_money');
                            }else{
                                $purchase_price = $channel_order_mdl->where(['buyer_id' => $buyer_id, 'website_id' => $website_id, 'order_status' => 4])->sum('pay_money');
                            }
                            if ($purchase_price >= $condition_money) {
                                $mark[] = 2;
                            }
                        }

                        //获取采购单
                        $condition_num = $upgrade_arr['up_total_order_num'];
                        if (!empty($condition_num)) {
                            $i++;
                            //累计采购单
                            if($condition_list['down_upgrade_time']){
                                $purchase_num = $channel_order_mdl->where(['buyer_id' => $buyer_id, 'website_id' => $website_id, 'order_status' => 4,'pay_time'=>[">",$condition_list['down_upgrade_time']]])->count();
                            }else{
                                $purchase_num = $channel_order_mdl->where(['buyer_id' => $buyer_id, 'website_id' => $website_id, 'order_status' => 4])->count();
                            }
                            if ($purchase_num >= $condition_num) {
                                $mark[] = 3;
                            }
                        }
                        //采购指定商品
                        $goods_id = $upgrade_arr['goods_id'];
                        if (!empty($goods_id)) {
                            $i++;
                            $channel_order_mdl = new VslChannelOrderModel();
                            if($condition_list['down_upgrade_time']){
                                $is_channel_goods = $channel_order_mdl->alias('co')->join('vsl_channel_order_goods cog', 'co.order_id=cog.order_id', 'LEFT')->where(['cog.goods_id' => $goods_id, 'co.buyer_id' => $buyer_id, 'co.website_id' => $website_id, 'co.order_status' => 4,'co.pay_time'=>[">",$condition_list['down_upgrade_time']]])->find();
                            }else{
                                $is_channel_goods = $channel_order_mdl->alias('co')->join('vsl_channel_order_goods cog', 'co.order_id=cog.order_id', 'LEFT')->where(['cog.goods_id' => $goods_id, 'co.buyer_id' => $buyer_id, 'co.website_id' => $website_id, 'co.order_status' => 4])->find();
                            }
                            if ($is_channel_goods) {
                                $mark[] = 4;
                            }
                        }
                        //判断当前条件满足单个或全部
                        if ($up_grade_condition_list['upgrade_condition'] == 'all') {
                            if (count($mark) == $i) {
                                $channel_mdl->save(['channel_grade_id' => $up_grade_condition_list['channel_grade_id'], 'upgrade_time' => time(), 'down_upgrade_time' => ''], ['uid' => $buyer_id]);
                            }
                        } else {
                            if (count($mark) >= 1) {
                                $channel_mdl->save(['channel_grade_id' => $up_grade_condition_list['channel_grade_id'], 'upgrade_time' => time(), 'down_upgrade_time' => ''], ['uid' => $buyer_id]);
                            }
                        }
                    }
                }

            }else{//未开启跳级
                //获取我的所有上面的等级
                $up_grade_weight_list = $channel_level->Query(['weight' => ['>',$mygrade_weight], 'website_id' => $website_id],'weight');
                asort($up_grade_weight_list);
                $up_grade_weight_list = array_values($up_grade_weight_list);
                $up_grade = $up_grade_weight_list[0];
//                $up_grade = $mygrade_weight+1;
                //判断这个等级是否存在
                $up_grade_weight = $channel_level->getInfo(['weight' => $up_grade, 'website_id' => $website_id],'weight')['weight'];
                if(!$up_grade_weight){
                    return  0;
                }
                //获取我的所有采购信息
                $i = 0;
                $mark = [];
                $up_grade_condition_list = $channel_level->getInfo(['weight' => $up_grade_weight, 'website_id' => $website_id],'channel_grade_id,upgrade_condition,upgrade_val,auto_upgrade');
                if($up_grade_condition_list['auto_upgrade'] == 1 && $up_grade_condition_list['upgrade_val']){
                    $upgrade_arr = json_decode($up_grade_condition_list['upgrade_val'],true);
                    //获取要求的采购量
                    $condition_quantity = $upgrade_arr['up_total_purchase_num'];
                    if (!empty($condition_quantity)) {
                        $i++;
                        //累计采购量
                        if($condition_list['down_upgrade_time']){
                            $purchase_quantity = $channel_order_mdl->alias('co')->where(['co.buyer_id' => $buyer_id, 'co.website_id' => $website_id, 'co.order_status' => 4,'co.pay_time'=>[">",$condition_list['down_upgrade_time']]])->join('vsl_channel_order_goods cog', 'co.order_id = cog.order_id', 'LEFT')->sum('cog.num');
                        }else{
                            $purchase_quantity = $channel_order_mdl->alias('co')->where(['co.buyer_id' => $buyer_id, 'co.website_id' => $website_id, 'co.order_status' => 4])->join('vsl_channel_order_goods cog', 'co.order_id = cog.order_id', 'LEFT')->sum('cog.num');
                        }
                        if ($purchase_quantity >= $condition_quantity) {
                            $mark[] = 1;
                        }
                    }

                    //获取采购金额
                    $condition_money = $upgrade_arr['up_total_purchase_amount'];
                    if (!empty($condition_money)) {
                        $i++;
                        //累计采购金额
                        if($condition_list['down_upgrade_time']){
                            $purchase_price = $channel_order_mdl->where(['buyer_id' => $buyer_id, 'website_id' => $website_id, 'order_status' => 4,'pay_time'=>[">",$condition_list['down_upgrade_time']]])->sum('pay_money');
                        }else{
                            $purchase_price = $channel_order_mdl->where(['buyer_id' => $buyer_id, 'website_id' => $website_id, 'order_status' => 4])->sum('pay_money');
                        }
                        if ($purchase_price >= $condition_money) {
                            $mark[] = 2;
                        }
                    }

                    //获取采购单
                    $condition_num = $upgrade_arr['up_total_order_num'];
                    if (!empty($condition_num)) {
                        $i++;
                        //累计采购单
                        if($condition_list['down_upgrade_time']){
                            $purchase_num = $channel_order_mdl->where(['buyer_id' => $buyer_id, 'website_id' => $website_id, 'order_status' => 4,'pay_time'=>[">",$condition_list['down_upgrade_time']]])->count();
                        }else{
                            $purchase_num = $channel_order_mdl->where(['buyer_id' => $buyer_id, 'website_id' => $website_id, 'order_status' => 4])->count();
                        }
                        if ($purchase_num >= $condition_num) {
                            $mark[] = 3;
                        }
                    }
                    //采购指定商品
                    $goods_id = $upgrade_arr['goods_id'];
                    if (!empty($goods_id)) {
                        $i++;
                        $channel_order_mdl = new VslChannelOrderModel();
                        if($condition_list['down_upgrade_time']){
                            $is_channel_goods = $channel_order_mdl->alias('co')->join('vsl_channel_order_goods cog', 'co.order_id=cog.order_id', 'LEFT')->where(['cog.goods_id' => $goods_id, 'co.buyer_id' => $buyer_id, 'co.website_id' => $website_id, 'co.order_status' => 4,'co.pay_time'=>[">",$condition_list['down_upgrade_time']]])->find();
                        }else{
                            $is_channel_goods = $channel_order_mdl->alias('co')->join('vsl_channel_order_goods cog', 'co.order_id=cog.order_id', 'LEFT')->where(['cog.goods_id' => $goods_id, 'co.buyer_id' => $buyer_id, 'co.website_id' => $website_id, 'co.order_status' => 4])->find();
                        }
                        if ($is_channel_goods) {
                            $mark[] = 4;
                        }
                    }

                    //判断当前条件满足单个或全部
                    if ($up_grade_condition_list['upgrade_condition'] == 'all') {
                        if (count($mark) == $i) {
                            $channel_mdl->save(['channel_grade_id' => $up_grade_condition_list['channel_grade_id'], 'upgrade_time' => time(), 'down_upgrade_time' => ''], ['uid' => $buyer_id]);
                        }
                    } else {
                        if (count($mark) >= 1) {
                            $channel_mdl->save(['channel_grade_id' => $up_grade_condition_list['channel_grade_id'], 'upgrade_time' => time(), 'down_upgrade_time' => ''], ['uid' => $buyer_id]);
                        }
                    }
                }

            }
        }
    }
    /*
     * 计算渠道商的利润和奖金,并发放
     * [$order_info] array 订单信息
     * [$buy_type] int 购买类型 1-采购 2-零售
     * **/
    public function calculateChannelBonus($order_info)
    {
        //购买人
        $buyer_id = $order_info['buyer_id'];
        $condition['c.uid'] = $buyer_id;
        $condition['c.website_id'] = $this->website_id;
        $buyer_channel_info = $this->getMyChannelInfo($condition);
        $my_weight = $buyer_channel_info['weight'];
        $my_up_channel = $this->myAllUpChannel($buyer_id, $my_weight);
        $buyer_user_name = $buyer_channel_info['nick_name']?:($buyer_channel_info['user_name']?:$buyer_channel_info['user_tel']);
        if(!$my_up_channel){
            return;
        }
        foreach($my_up_channel as $k=>$up_id){
            switch($k){
                case 'cross_grade':
                    $uid = $up_id[0];//受益人的id
                    $member_account_text = '下级渠道商（' . $buyer_user_name . '）：购买商品，获得跨级奖金';
                    $this->insertChannelBonus('cross_grade', $order_info, $uid, $member_account_text);
                    break;
                case 'peer_one_grade':
                    $uid = $up_id[0];//受益人的id
                    $member_account_text = '下级渠道商（' . $buyer_user_name . '）：购买商品，获得平一级奖金';
                    $this->insertChannelBonus('peer_one_grade', $order_info, $uid, $member_account_text);
                    break;
                case 'peer_two_grade':
                    $uid = $up_id[0];//受益人的id
                    $member_account_text = '下级渠道商（' . $buyer_user_name . '）：购买商品，获得平二级奖金';
                    $this->insertChannelBonus('peer_two_grade', $order_info, $uid, $member_account_text);
                    break;
                case 'peer_three_grade':
                    $uid = $up_id[0];//受益人的id
                    $member_account_text = '下级渠道商（' . $buyer_user_name . '）：购买商品，获得平三级奖金';
                    $this->insertChannelBonus('peer_three_grade', $order_info, $uid, $member_account_text);
                    break;
            }
        }
    }
    /*
     * 插入渠道商奖金表
     * **/
    public function insertChannelBonus($type, $order_info, $uid, $member_account_text)
    {
        //判断是否开启了平级奖和跨级奖，如果没有开启则不能算奖金
        $channel_config = $this->getChannelConfig();
        $channel_bonus = new VslChannelBonusModel();
        $order_money = $order_info['order_money'];
        //获取受益人的等级
        $channel_user = $this->getMyChannelInfo(['c.uid'=>$uid,'c.website_id'=>$order_info['website_id']]);
        if($type == 'cross_grade'){
            $ratio = $channel_config['cross_status'] != '1' ? 0 :$channel_user['cross_level'];
            $money_type = 1;
        } elseif ($type == 'peer_one_grade') {
            $ratio = $channel_config['peers_status'] != '1' ? 0 :$channel_user['flat_first'];
            $money_type = 2;
        } elseif ($type == 'peer_two_grade') {
            $ratio = $channel_config['peers_status'] != '1' ? 0 : $channel_user['flat_second'];
            $money_type = 3;
        } elseif ($type == 'peer_three_grade') {
            $ratio = $channel_config['peers_status'] != '1' ? 0 : $channel_user['flat_third'];
            $money_type = 4;
        }
        if($ratio == 0){
            return;
        }
        $bonus_money = $ratio * $order_money;//获得奖金
        $data['bonus'] = $bonus_money;
        $data['uid'] = $uid;
        $uid_weight = $channel_user['weight'];
        // 在线支付 处理平台的资金账户
        $this->updateMemberAccountMoney($bonus_money, $uid);
        //26是代表渠道商订单 $uid, $title, $money, $type_alis_id
        //用户余额奖金变动商家提醒
        runhook("Notify", "successfulChannelBonusByTemplate", ["website_id" => $order_info['website_id'], "uid" => $uid, "pay_money" => $bonus_money, 'money_channel_type' => 2]);//用户奖金发放用户提醒 // 1-利润  2-奖金
        runhook("Notify", "successfulChannelBonusByMpTemplate", ["website_id" => $order_info['website_id'], "uid" => $uid, "pay_money" => $bonus_money, 'money_channel_type' => 2]);//小程序用户奖金发放用户提醒
        $this->updateMemberAccountRecords($uid, $member_account_text, $bonus_money, $order_info['order_id']);
        $data['my_channel_weight'] = $uid_weight;
        $data['order_id'] = $order_info['order_id'];
        $data['order_money'] = $order_money;
        $data['order_buyer_id'] = $order_info['buyer_id'];
        $buyer_channel_info = $this->getMyChannelInfo(['c.uid'=>$order_info['buyer_id'],'c.website_id'=>$this->website_id]);
        $buyer_channel_weight = $buyer_channel_info['weight'];
        $data['buyer_channel_grade_id'] = $buyer_channel_info['channel_grade_id'];
        $data['buyer_channel_weight'] = $buyer_channel_weight;
        $data['money_type'] = $money_type;//奖励类型 1-跨级奖 2-平一级奖 3-平二级将 4-平三级奖
        $data['add_time'] = time();
        $channel_bonus->save($data);
    }
    /*
     * 获取自动降级的规定时间我的采购量
     * **/
    public function getMyLeaseDayPurchaseNum($day,$val,$upgrade_time,$uid,$website_id)
    {
        $channel_order_mdl = new VslChannelOrderModel();
        $last_time = $upgrade_time+$day*24*3600;
        $first_time = $upgrade_time;
        $condition['co.buyer_id'] = $uid;
        $condition['co.website_id'] = $website_id;
        $condition['co.order_status'] = 4;
        $condition['co.create_time'] = [
            ['>',$first_time],['<',$last_time]
        ];
        $purchase_quantity = $channel_order_mdl->alias('co')->where($condition)->join('vsl_channel_order_goods cog','co.order_id = cog.order_id','LEFT')->sum('cog.num');
//        echo $channel_order_mdl->getLastSql();exit;
        return $purchase_quantity;
    }
    /*
     * 获取自动降级的规定时间我的采购金额
     * **/
    public function getMyLeaseDayPurchaseAmount($day,$val,$upgrade_time,$uid,$website_id)
    {
        $channel_order_mdl = new VslChannelOrderModel();
        $last_time = $upgrade_time+$day*24*3600;
        $first_time = $upgrade_time;
        $condition['buyer_id'] = $uid;
        $condition['website_id'] = $website_id;
        $condition['order_status'] = 4;
        $condition['create_time'] = [
            ['>',$first_time],['<',$last_time]
        ];
        $purchase_price = $channel_order_mdl->where($condition)->sum('pay_money');
        return $purchase_price;
    }
    /*
     * 获取自动降级的规定时间我的订单数
     * **/
    public function getMyLeaseDayOrderNum($day,$val,$upgrade_time,$uid,$website_id)
    {
        $channel_order_mdl = new VslChannelOrderModel();
        $last_time = $upgrade_time+$day*24*3600;
        $first_time = $upgrade_time;
        $condition['buyer_id'] = $uid;
        $condition['website_id'] = $website_id;
        $condition['order_status'] = 4;
        $condition['create_time'] = [
            ['>',$first_time],['<',$last_time]
        ];
        $order_num = $channel_order_mdl->where($condition)->count();
        return $order_num;
    }
    /*
     * 判断是否是渠道商
     * **/
    public function isChannel()
    {
        $uid = $this->uid;
        $channel_mdl = new VslChannelModel();
        $user_mdl = new UserModel();
        $channel_info = $channel_mdl->alias('c')->where(['c.uid'=>$uid])->join('sys_user u','c.uid=u.uid','LEFT')->find();
        if($channel_info['channel_id']){
            if($channel_info['status'] == 1){//申请成功了，并且已经审核了
//                $res['is_channel'] = true;
                $res['is_checked'] = 2;
            }elseif($channel_info['status'] == 0){//申请成功了，未审核
//                $res['is_channel'] = true;
                $res['is_checked'] = 1;
            }elseif($channel_info['status'] == -1){//申请了拒绝
                $res['is_checked'] = -1;
            }
            $res['user_tel'] = $channel_info['user_tel'];
            $res['real_name'] = $channel_info['real_name']?:'';
        }else{//未申请
//            $res['is_channel'] = false;
            $res['is_checked'] = 0;
            //获取用户的信息
            $user_info = $user_mdl->getInfo(['uid'=>$uid],'user_tel,real_name');
            $res['user_tel'] = $user_info['user_tel'];
            $res['real_name'] = $user_info['real_name']?:'';
        }
        return $res;
    }
    /*
    * 查询出采购订单各个状态的数目
    * **/
    public function getPurchaseOrderStatusCount($condition = [])
    {
        $condition['website_id'] = $this->website_id;
        $channel_order_mdl = new VslChannelOrderModel();
        $channel_order_count = $channel_order_mdl->where($condition)->count();
        return $channel_order_count;
    }
    /**
     * B端采购订单
     */
    public function purchaseOrderList($page_index, $page_size, $condition, $condition1, $condition2, $order)
    {
        $channel_order_mdl = new VslChannelOrderModel();
        $channel_order_goods_mdl = new VslChannelOrderGoodsModel();
        $goods_mdl = new VslGoodsModel();
        $user_mdl  = new UserModel();
        $album_mdl = new AlbumPictureModel();
        $goods_sku_mdl = new VslGoodsSkuModel();
        $spec_mdl = new VslGoodsSpecModel();
        $spec_val_mdl = new VslGoodsSpecValueModel();
        $order_ids = '';
        //根据商品名称
        if($condition1['goods_name']) {
            $channel_order_goods_cdt = [
                'goods_name' =>  $condition1['goods_name']
            ];
            $order_id_list = $channel_order_goods_mdl->getQuery($channel_order_goods_cdt,'order_id','');
            if($order_id_list) {
                foreach ($order_id_list as $k => $v) {
                    $order_ids .= $v['order_id'] . ',';
                }
                $order_ids = trim($order_ids,',');
                $condition['order_id'] = ['in',$order_ids];
            }
        }
        //根据商品编号
        if($condition1['goods_code']) {
            $goods_cdt = [
                'code' =>  $condition1['goods_code']
            ];
            $goods_list = $goods_mdl->getQuery($goods_cdt,'goods_id','');
            if($goods_list) {
                $goods_ids =  '';
                foreach ($goods_list as $k => $v) {
                    $goods_ids .= $v['goods_id'] . ',';
                }
                $goods_ids = trim($goods_ids,',');
                $channel_order_goods_cdt = [
                    'goods_id' =>  ['IN',$goods_ids]
                ];
                $order_id_list = $channel_order_goods_mdl->getQuery($channel_order_goods_cdt,'order_id','');
                if($order_id_list) {
                    foreach ($order_id_list as $k => $v) {
                        $order_ids .= $v['order_id'] . ',';
                    }
                    $order_ids = trim($order_ids,',');
                    $condition['order_id'] = ['in',$order_ids];
                }
            }
        }
        //根据用户信息
        if($condition2['user_tel|uid|user_name|nick_name']) {
            $user_cdt = [
                'user_tel|uid|user_name|nick_name' => $condition2['user_tel|uid|user_name|nick_name']
            ];
            $user_list = $user_mdl->getQuery($user_cdt,'uid','');
            if($user_list) {
                $uids = '';
                foreach ($user_list as $k => $v) {
                    $uids .= $v['uid'] . ',';
                }
                $uids = trim($uids,',');
                $condition['buyer_id'] = ['in',$uids];
            }
        }
        $order_list = $channel_order_mdl->pageQuery($page_index, $page_size, $condition,$order,'*');
        $channel_order_arr = $order_list['data'];
        //处理商品所属平台名称、采购于谁
        $shop_name = '自营店';
        if(getAddons('shop', $this->website_id)){
            $shop_model = new VslShopModel();
            $shop_info = $shop_model::get(['shop_id' => 0, 'website_id' => $this->website_id]);
            $shop_name = $shop_info['shop_name'];
        }
        foreach($channel_order_arr as $k=>$order){
            $new_channel_order_arr[$k]['order_item_list'] = $channel_order_goods_mdl->getQuery(['order_id' => $order['order_id']],'*','');
            $new_channel_order_arr[$k]['order_no'] = $order['order_no'];
            $new_channel_order_arr[$k]['order_id'] = $order['order_id'];
            $new_channel_order_arr[$k]['create_time'] = date('Y-m-d H:i:s',$order['create_time']);
            $new_channel_order_arr[$k]['pay_time'] = date('Y-m-d H:i:s',$order['pay_time']);
            $new_channel_order_arr[$k]['website_id'] = $this->website_id;
            $new_channel_order_arr[$k]['pay_status'] = $order['pay_status'];
            $new_channel_order_arr[$k]['order_status'] = $order['order_status'];
            $new_channel_order_arr[$k]['pay_money'] = $order['pay_money'];
            $new_channel_order_arr[$k]['payment_type'] = $order['payment_type'];
            $new_channel_order_arr[$k]['buyer_id'] = $order['buyer_id'];
            foreach ($new_channel_order_arr[$k]['order_item_list'] as $k1 => $order_goods) {
                //处理图片
                $new_channel_order_arr[$k]['order_item_list'][$k1]['pic_cover_micro'] =
                    getApiSrc($album_mdl->Query(['pic_id' => $order_goods['goods_picture']],'pic_cover_micro')[0]);
                //处理采购方
                $buyer_info = $user_mdl->getInfo(['uid' => $order_goods['buyer_id']],'*');
                $new_channel_order_arr[$k]['buyer_name'] = $buyer_info['nick_name']?:($buyer_info['user_name']?:$buyer_info['user_tel'])?:'';
                //处理供货方
                $channel_info = $order_goods['channel_info'];
                if($channel_info == 'platform'){
                    $new_channel_order_arr[$k]['order_item_list'][$k1]['purchase_to'] = '总店';
                    $new_channel_order_arr[$k]['order_item_list'][$k1]['purchase_to_id'] = 0;
                }else{
                    $condition_channel['c.website_id'] = $this->website_id;
                    $condition_channel['c.channel_id'] = $channel_info;
                    $channel_user_info = $this->getChannelName($condition_channel);
                    $new_channel_order_arr[$k]['order_item_list'][$k1]['purchase_to'] = $channel_user_info['nick_name']?:($channel_user_info['user_name']?:$channel_user_info['user_tel']?:'');
                    $new_channel_order_arr[$k]['order_item_list'][$k1]['purchase_to_id'] = $channel_user_info['uid'];
                }
                //处理sku
                $sku_name = '';
                if($order_goods['sku_name']) {
                    $order_goods['sku_name'] = explode(' ',$order_goods['sku_name']);
                    foreach ($order_goods['sku_name'] as $k2 => $sku) {
                        $sku_name .= $sku . ';';
                    }
                    $sku_name = trim($sku_name,';');
                    $new_channel_order_arr[$k]['order_item_list'][$k1]['sku_name'] = $sku_name;
                }else{
                    $new_channel_order_arr[$k]['order_item_list'][$k1]['sku_name'] = '';
                }
            }
        }
        if(!$new_channel_order_arr){
            return [
                'total_count' => 0,
                'page_count' => 0,
                'data' => []
            ];
        }
        return [
            'data'=>$new_channel_order_arr,
            'total_count' => $order_list['total_count'],
            'page_count' => $order_list['page_count']
        ];
    }
    /**
     *B端采购订单详情
     */
    public function purchaseOrderDetail($purchase_order_id)
    {
        $channel_order_mdl = new VslChannelOrderModel();
        $channel_order_goods_mdl = new VslChannelOrderGoodsModel();
        $user_mdl  = new UserModel();
        $album_mdl = new AlbumPictureModel();
        $goods_sku_mdl = new VslGoodsSkuModel();
        $spec_mdl = new VslGoodsSpecModel();
        $spec_val_mdl = new VslGoodsSpecValueModel();
        $member_mdl = new VslMemberModel();
        $channel_order_action = new VslChannelOrderActionModel();
        $order_info = $channel_order_mdl->getInfo(['order_id' => $purchase_order_id],'*');
        $order_detail = [
              'order_no' => $order_info['order_no'],
              'order_status' => $order_info['order_status'],
              'payment_type' => $order_info['payment_type'],
              'create_time' => date('Y-m-d H:i:s',$order_info['create_time']),
              'pay_money' => $order_info['pay_money'],
              'buyer_id' =>  $order_info['buyer_id'],
        ];
        //采购方
        $buyer_info = $user_mdl->getInfo(['uid' => $order_info['buyer_id']],'*');
        $order_detail['buyer_name'] = $buyer_info['nick_name']?:($buyer_info['user_name']?:$buyer_info['user_tel'])?:'';
        //采购方上级
        $referee_id = $member_mdl->Query(['uid' => $order_info['buyer_id']],'referee_id')[0];
        if(empty($referee_id)) {
            $order_detail['buyer_heigher_name'] = '总店';
            $order_detail['buyer_heigher_id'] = 0;
        }else{
            $buyer_heigher_info = $user_mdl->getInfo(['uid' => $referee_id],'*');
            $order_detail['buyer_heigher_name'] = $buyer_heigher_info['nick_name']?:($buyer_heigher_info['user_name']?:$buyer_heigher_info['user_tel'])?:'';
            $order_detail['buyer_heigher_id'] = $buyer_heigher_info['uid'];
        }
        //订单下的商品
        $order_goods_list = $channel_order_goods_mdl->getQuery(['order_id' => $purchase_order_id],'*','');
        foreach ($order_goods_list as $k => $v) {
            //处理图片
            $order_goods_list[$k]['pic_cover_mid'] =
                getApiSrc($album_mdl->Query(['pic_id' => $v['goods_picture']],'pic_cover_micro')[0]);
            //处理供货方
            $channel_info = $v['channel_info'];
            if($channel_info == 'platform'){
                $order_goods_list[$k]['purchase_to'] = '总店';
            }else{
                $condition_channel['c.website_id'] = $this->website_id;
                $condition_channel['c.channel_id'] = $channel_info;
                $channel_user_info = $this->getChannelName($condition_channel);
                $order_goods_list[$k]['purchase_to'] = $channel_user_info['nick_name']?:($channel_user_info['user_name']?:$channel_user_info['user_tel']?:'');
            }
            //处理sku
            $sku_name = '';
            if($v['sku_name']) {
                $v['sku_name'] = explode(' ',$v['sku_name']);
                foreach ($v['sku_name'] as $k2 => $sku) {
                    $sku_name .= $sku . ';';
                }
                $sku_name = trim($sku_name,';');
                $order_goods_list[$k]['sku_name'] = $sku_name;
            }else{
                $order_goods_list[$k]['sku_name'] = '';
            }
        }
        $order_detail['order_goods'] = $order_goods_list;
        foreach ($order_goods_list as $k => $v) {
            $purchase_to[] = $v['purchase_to'];
        }
        $purchase_to = array_unique($purchase_to);
        $order_detail['purchase_to'] = implode('+',$purchase_to);
        //订单操作
        $order_detail['order_action'] = $channel_order_action->getQuery(['order_id' => $purchase_order_id],'action,user_name,action_time','action_id DESC');
        return $order_detail;
    }
}