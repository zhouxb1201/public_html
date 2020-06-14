<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/26 0026
 * Time: 14:41
 */

namespace addons\bargain\service;

use addons\bargain\model\AppCustomTemplate;
use addons\bargain\model\AppVersion;
use addons\bargain\model\VslBargainDetailModel;
use addons\bargain\model\VslBargainModel;
use addons\bargain\model\VslBargainRecordModel;
use addons\miniprogram\model\WeixinAuthModel;
use addons\shop\service\Shop as ShopService;
use data\model\AddonsConfigModel;
use data\model\VslGoodsModel;
use data\model\AppadsetModel;
use data\model\VslOrderModel;
use data\model\VslPushMessage;
use data\model\VslGoodsViewModel;
use data\model\WebSiteModel;
use data\service\AddonsConfig;
use data\service\BaseService;
use phpDocumentor\Reflection\Types\Object;
use think\db;

class Bargain extends BaseService
{
    public $addons_config_module;
    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
        $this->bargain_mdl = new VslBargainModel();
    }
    //插入bargain活动表
    public function addBargain($data, $bargain_id)
    {
        //获取商品的名称
        $goods_mdl = new VslGoodsModel();
        $bargain_mdl = new VslBargainModel();
        $goods_id = $data['goods_id'];
        //判断该商品是否在其它活动中
        $promotion_type = $goods_mdl->getInfo(['goods_id'=>$goods_id],'promotion_type')['promotion_type'];
        $goods_list = $goods_mdl->getInfo(['goods_id'=>$goods_id],'goods_name,picture');
        if(!$bargain_id){
            if($promotion_type){
                return ['code'=>-1, 'message'=>'商品在其它活动中已存在'];
            }
            $time = time();
            $condition['website_id'] = $this->website_id;
            $condition['end_bargain_time'] = ['>=',$time];
            $condition['goods_id'] = $data['goods_id'];
            $condition['close_status'] = ['neq', 0];
            //判断是否存在
            $is_bargain = $bargain_mdl->where($condition)->find();
//        echo $bargain_mdl->getLastSql();exit;
            if($is_bargain){
                //为啥要判断活动标签和商品是否存在？因为如果a商品存在其他的活动名称里面，这时添加该商品到这个活动名称，就会矛盾。
                return ['code'=>-1, 'message'=>'活动商品已存在'];
            }else{
                $data['goods_name'] = $goods_list['goods_name'];
                $data['picture'] = $goods_list['picture'];
                $data['website_id'] = $this->website_id;
                $data['shop_id'] = $this->instance_id;
                $data['create_time'] = time();
                $res = $bargain_mdl->save($data);
                //修改商品活动表
                if($res){
                    $goods_mdl->where(['goods_id'=>$goods_id])->update(['promotion_type'=>4]);
                }
            }
        }else{//编辑
            if($promotion_type && $promotion_type != 4){
                return ['code'=>-1, 'message'=>'商品在其它活动中已存在'];
            }
            $data['goods_name'] = $goods_list['goods_name'];
            $data['picture'] = $goods_list['picture'];
            $data['website_id'] = $this->website_id;
            $data['shop_id'] = $this->instance_id;
            $data['create_time'] = time();
            $res = $bargain_mdl->save($data,['bargain_id'=>$bargain_id]);
        }
        return ['code'=>$res];
    }
    /*
     * 获取bargain列表
     * **/
    public function bargainList($page_index, $page_size, $condition, $order)
    {
        $count = $this->bargain_mdl->alias('b')->where($condition)->join('sys_album_picture ap', 'b.picture=ap.pic_id', 'LEFT')->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $bargain_list = $this->bargain_mdl->field('b.*, ap.pic_cover')->alias('b')->where($condition)->join('sys_album_picture ap', 'b.picture=ap.pic_id', 'LEFT')->limit($offset, $page_size)->order($order)->select();
        $time = time();
        foreach($bargain_list as $k=>$v){
//            $bargain_list[$k]['pic_cover'] = getApiSrc($v['pic_cover']);
            $bargain_list[$k]['start_bargain_date'] = date('Y:m:d H:i:s', $v['start_bargain_time']);
            $bargain_list[$k]['end_bargain_date'] = date('Y:m:d H:i:s', $v['end_bargain_time']);
            //处理状态
            if ($v['start_bargain_time'] > $time) {
                //未开始
                $bargain_list[$k]['status'] = 0;
                if($v['close_status'] === 0){//活动已关闭
                    $bargain_list[$k]['status'] = 3;
                }
            } elseif ($v['start_bargain_time'] < $time && $v['end_bargain_time'] > $time) {
                //进行中
                $bargain_list[$k]['status'] = 1;
                if($v['close_status'] === 0){//活动已关闭
                    $bargain_list[$k]['status'] = 3;
                }
            } elseif ($v['end_bargain_time'] < $time) {
                //已结束
                $bargain_list[$k]['status'] = 2;
            }
            $bargain_list[$k]['pic_cover_url'] = getApiSrc($v['pic_cover']);
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
        $addon_status['wap_status'] = $wap_status;
        $addon_status['is_pc_use'] = $pc_conf['is_use'];
        $addon_status['is_minipro'] = $is_minipro;
        return ['code'=>0,
            'data'=>$bargain_list,
            'addon_status'=>$addon_status,
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }
    /*
     * 获取列表的每个状态的数目
     * **/
    public function getBargainStatusCount($condition='')
    {
        $count = $this->bargain_mdl->where($condition)->count();
        return $count;
    }
    /*
     * 获取砍价记录
     * **/
    public function getBargainRecord($page_index=1,$page_size,$condition='',$order)
    {
        $count = $this->bargain_mdl->alias('b')
            ->where($condition)
            ->join('vsl_bargain_record br','b.bargain_id=br.bargain_id','LEFT')
            ->join('vsl_bargain_detail bd','br.bargain_record_id=bd.bargain_record_id','LEFT')
            ->join('sys_user u','br.uid=u.uid','LEFT')
            ->count();
        $offset = ($page_index-1)*$page_size;
        $page_count = ceil($count/$page_size);
        $record_list = $this->bargain_mdl->alias('b')
            ->field('b.end_bargain_time, br.*,u.user_name,u.nick_name,u.user_tel,u.user_headimg,ml.level_name')
            ->where($condition)
            ->join('vsl_bargain_record br','b.bargain_id=br.bargain_id','LEFT')
            ->join('sys_user u','br.uid=u.uid','LEFT')
            ->join('vsl_member m','m.uid=u.uid','LEFT')
            ->join('vsl_member_level ml','m.member_level=ml.level_id','LEFT')
            ->order($order)
            ->limit($offset, $page_size)
            ->select();
//        echo $this->bargain_mdl->getLastSql();
//        p(objToArr($record_list));exit;
        //处理会员
        foreach($record_list as $k=>$v){
            //判断当前时间是否大于end_bargain_time
            if ($v['end_bargain_time'] < time()) {
                $new_record_list[$k]['bargain_status'] = 3;
            }else{
                $new_record_list[$k]['bargain_status'] = $v['bargain_status'];
                if($v['bargain_status'] == 2){//已支付
                    //获取订单编号
                    $order_mdl = new VslOrderModel();
                    $order_cond['buyer_id'] = $v['uid'];
                    $order_cond['bargain_id'] = $v['bargain_id'];
                    $order_no = $order_mdl->getInfo($order_cond,'order_no')['order_no'];
                    $new_record_list[$k]['order_no'] = $order_no;
                }
            }
            $new_record_list[$k]['user_name'] = $v['nick_name']?:($v['user_name']?:$v['user_tel']);
            $new_record_list[$k]['now_bargain_money'] = $v['now_bargain_money'];
            $new_record_list[$k]['start_price'] = $v['start_money'];
            $new_record_list[$k]['already_bargain_money'] = $v['already_bargain_money'];
            $new_record_list[$k]['order_id'] = $v['order_id'];
            $new_record_list[$k]['help_count'] = $v['help_count'];
            $new_record_list[$k]['level_name'] = $v['level_name'];
            $new_record_list[$k]['pic_cover'] = getApiSrc($v['user_headimg']);
        }
        return ['code'=>0,
            'data'=>$new_record_list,
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }
    /*
     * 获取砍价统计记录 已支付、砍价中、失败
     * **/
    public function getBargainCount($condition)
    {
        $count = $this->bargain_mdl->alias('b')
            ->join('vsl_bargain_record br','b.bargain_id=br.bargain_id','LEFT')
            ->where($condition)
            ->count();
        return $count;
    }
    /*
     * 获取活动详情
     * **/
    public function getBargainDetail($bargain_id)
    {
        $detail_list = $this->bargain_mdl->alias('b')->field('b.*,ap.pic_cover')->join('sys_album_picture ap','b.picture=ap.pic_id')->where(['bargain_id'=>$bargain_id])->find();
        return $detail_list;
    }
    /*
     * 关闭砍价活动
     * **/
    public function bargainClose($bargain_id)
    {
        $bargain_mdl = new VslBargainModel();
        $goods_mdl = new VslGoodsModel();
        //关闭后，将商品的promotion_type归0
        //清除掉goods的促销类型
        $goods_id = $bargain_mdl->getInfo(['bargain_id'=>$bargain_id],'goods_id')['goods_id'];
        $promotion_arr = [
            'promotion_type' => 0
        ];
        $promotion_condition['goods_id'] = $goods_id;
        $promotion_condition['promotion_type'] = 4;
        $goods_mdl->where($promotion_condition)->update($promotion_arr);
        $res = $bargain_mdl->where(['bargain_id'=>$bargain_id])->update(['close_status'=>0]);
        return $res;
    }
    /*
     * 移除砍价活动
     * **/
    public function bargainDelete($bargain_id)
    {
        $bargain_mdl = new VslBargainModel();
        $bargain_record_mdl = new VslBargainRecordModel();
        $bargain_detail_mdl = new VslBargainDetailModel();
        $goods_mdl = new VslGoodsModel();
        try{
            $bargain_mdl->startTrans();
            $bargain_record_id = $bargain_record_mdl->getInfo(['bargain_id'=>$bargain_id],'bargain_record_id')['bargain_record_id'];
            $goods_id = $bargain_mdl->getInfo(['bargain_id'=>$bargain_id],'goods_id')['goods_id'];
            $bargain_mdl->where(['bargain_id'=>$bargain_id])->delete();
            if($bargain_record_id){
                $res1 = $bargain_record_mdl->where(['bargain_id'=>$bargain_id])->delete();
                $res2 = $bargain_detail_mdl->where(['bargain_record_id'=>$bargain_record_id])->delete();
            }
            //清除掉goods的促销类型
            $promotion_arr = [
                'promotion_type' => 0
            ];
            $promotion_condition['goods_id'] = $goods_id;
            $promotion_condition['promotion_type'] = 4;
            $goods_mdl->where($promotion_condition)->update($promotion_arr);
            $bargain_mdl->commit();
            return 1;
        }catch(\Exception $e){
            $bargain_mdl->rollback();
            return -1;
        }
    }



    /***********************************************前端接口开始*****************************************************/
    /*
     * 获取前台bargain列表
     * **/
    public function frontBargainList($page_index, $page_size, $condition, $order)
    {
        $count = $this->bargain_mdl->alias('b')->where($condition)->join('sys_album_picture ap', 'b.picture=ap.pic_id', 'LEFT')->join('vsl_goods_discount vgd', 'vgd.goods_id = b.goods_id', 'LEFT')->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $bargain_list = $this->bargain_mdl->field('b.bargain_id, b.goods_id, b.goods_name, b.start_bargain_time, b.end_bargain_time, b.start_money, ap.pic_cover')->alias('b')->where($condition)->join('sys_album_picture ap', 'b.picture=ap.pic_id', 'LEFT')->join('vsl_goods_discount vgd', 'vgd.goods_id = b.goods_id', 'LEFT')->limit($offset, $page_size)->order($order)->select();
        $time = time();
        foreach($bargain_list as $k=>$v){
//            $bargain_list[$k]['pic_cover'] = getApiSrc($v['pic_cover']);
            $bargain_list[$k]['start_bargain_date'] = date('Y:m:d H:i:s', $v['start_bargain_time']);
            $bargain_list[$k]['end_bargain_date'] = date('Y:m:d H:i:s', $v['end_bargain_time']);
            //处理状态
            if ($v['start_bargain_time'] > $time) {
                //未开始
                $bargain_list[$k]['status'] = 0;
            } elseif ($v['start_bargain_time'] < $time && $v['end_bargain_time'] > $time) {
                //进行中
                $bargain_list[$k]['status'] = 1;
            } elseif ($v['end_bargain_time'] < $time) {
                //已结束
                $bargain_list[$k]['status'] = 2;
            }
            $bargain_list[$k]['pic_cover_url'] = getApiSrc($v['pic_cover']);
        }
//        return ['code'=>0,
//            'data'=>$bargain_list,
//            'total_count' => $count,
//            'page_count' => $page_count
//        ];
        return ['code'=>0,
                'data'=>[
                    'bargain_list'=>$bargain_list,
                    'total_count' => $count,
                    'page_count' => $page_count
                ]
        ];
    }
    /*
     * 判断砍价活动是否过期
     * **/
    public function isBargain($condition)
    {
        $is_addons = getAddons('bargain', $this->website_id);
        if(!$is_addons){
            return false;
        }
        $condition['close_status'] = 1;//未关闭
        $bargain_mdl = new VslBargainModel();
        $bargain_record_mdl = new VslBargainRecordModel();
        $bargain_list = $bargain_mdl
            ->field('bargain_id, bargain_name, lowest_money, start_bargain_time, end_bargain_time, start_money, is_my_bargain, first_bargain_money, bargain_method, fix_money, rand_lowest_money, rand_highest_money, limit_buy, bargain_stock, bargain_sales')
            ->where($condition)
            ->find();
        if($bargain_list['end_bargain_time'] < time()){
            return false;
        }else{
            if($bargain_list['end_bargain_time']>time() && $bargain_list['start_bargain_time']<time()){
                //判断我是否参与过砍价
                $my_bargain_condition['bargain_id'] = $bargain_list['bargain_id'];
                $my_bargain_condition['uid'] = $this->uid?:0;
                $my_bargain = $bargain_record_mdl->where($my_bargain_condition)->find();
                if($my_bargain){
                    $bargain_list['is_join_bargain'] = true;
                    $bargain_list['bargain_uid'] = $this->uid?:0;
                    $bargain_list['my_bargain'] = $my_bargain;
                }else{
                    $bargain_list['is_join_bargain'] = false;
                    $bargain_list['bargain_uid'] = $this->uid?:0;
                    $bargain_list['my_bargain'] = (object)[];
                }
                $bargain_list['status'] = 1;//正在进行
            }else{
                $bargain_list['status'] = 0;//未开始
            }
            return $bargain_list;
        }
    }
    /*
     * 判断砍价活动是否过期
     * **/
    public function isBargainByGoodsId($condition)
    {
        $is_addons = getAddons('bargain', $this->website_id);
        if(!$is_addons){
            return false;
        }
        $condition['close_status'] = 1;//未关闭
        $bargain_mdl = new VslBargainModel();
        $bargain_record_mdl = new VslBargainRecordModel();
        $bargain_list = $bargain_mdl
            ->field('bargain_id, bargain_name, lowest_money, start_bargain_time, end_bargain_time, start_money, is_my_bargain, first_bargain_money, bargain_method, fix_money, rand_lowest_money, rand_highest_money, limit_buy, bargain_stock, bargain_sales')
            ->where($condition)
            ->find();
        if($bargain_list['end_bargain_time'] < time()){
            return false;
        }else{
            if($bargain_list['end_bargain_time']>time() && $bargain_list['start_bargain_time']<time()){
                //判断我是否参与过砍价
                $my_bargain_condition['bargain_id'] = $bargain_list['bargain_id'];
                $my_bargain_condition['uid'] = $this->uid?:0;
                $my_bargain = $bargain_record_mdl->where($my_bargain_condition)->find();
                if($my_bargain){
                    $bargain_list['is_join_bargain'] = true;
                    $bargain_list['bargain_uid'] = $this->uid?:0;
                    $bargain_list['my_bargain'] = $my_bargain;
                }else{
                    $bargain_list['is_join_bargain'] = false;
                    $bargain_list['bargain_uid'] = $this->uid?:0;
                    $bargain_list['my_bargain'] = (object)[];
                }
                $bargain_list['status'] = 1;//正在进行
            }else{
                $bargain_list['status'] = 0;//未开始
            }
            return $bargain_list;
        }
    }
    /*
     * 添加我的砍价
     * **/
    public function addMyBargain($data)
    {
        $bargain_record_mdl = new VslBargainRecordModel();
        $res = $bargain_record_mdl->save($data);
        return $res;
    }
    /*
     * 获取我的砍价详情
     * **/
    public function getFrontBargainDetail($condition)
    {
        $bargain_detail_list = $this->bargain_mdl->alias('b')
            ->field('b.bargain_id, br.bargain_record_id, b.goods_id, b.goods_name, b.lowest_money, b.end_bargain_time, br.now_bargain_money, br.now_bargain_money, br.start_money, br.bargain_record_id, br.uid, br.bargain_record_id, ap.pic_cover, bd.help_uid, bd.help_price, u.user_headimg, u.user_name, u.nick_name, u.user_tel')
            ->join('vsl_bargain_record br','b.bargain_id=br.bargain_id','LEFT')
            ->join('sys_album_picture ap','b.picture=ap.pic_id','LEFT')
            ->join('vsl_bargain_detail bd','br.bargain_record_id=bd.bargain_record_id','LEFT')
            ->join('sys_user u','bd.help_uid=u.uid','LEFT')
            ->where($condition)
            ->select();
//        echo $this->bargain_mdl->getLastSql();
//        p($bargain_detail_list);exit;
        //处理图片、帮砍用户信息
        foreach($bargain_detail_list as $k=>$v){
            $new_bargain_detail_list['bargain_id'] = $v['bargain_id'];
            $new_bargain_detail_list['bargain_uid'] = $this->uid?:0;
            $new_bargain_detail_list['bargain_record_id'] = $v['bargain_record_id'];
            $new_bargain_detail_list['goods_id'] = $v['goods_id'];
            $new_bargain_detail_list['goods_name'] = $v['goods_name'];
            $new_bargain_detail_list['can_bargain_money'] = $v['now_bargain_money'] - $v['lowest_money'] <= 0 ? 0 : $v['now_bargain_money'] - $v['lowest_money'];//还能砍多少
            $new_bargain_detail_list['pic_cover'] = getApiSrc($v['pic_cover']);
            $new_bargain_detail_list['start_money'] = $v['start_money'];//起始价
            $new_bargain_detail_list['now_bargain_money'] = $v['now_bargain_money'];//现价
            $new_bargain_detail_list['end_bargain_time'] = $v['end_bargain_time'];//结束时间
            if($v['help_price'] != 0){
                $new_bargain_detail_list['help_bargain_list'][$k]['help_user_headimg'] = getApiSrc($v['user_headimg']);
                $new_bargain_detail_list['help_bargain_list'][$k]['help_name'] = $v['nick_name']?:($v['user_name']?:$v['user_tel']);
                $new_bargain_detail_list['help_bargain_list'][$k]['help_price'] = $v['help_price'];
            }
        }
        if(!isset($new_bargain_detail_list['help_bargain_list'])){
            $new_bargain_detail_list['help_bargain_list'] = [];
        }
//        p($new_bargain_detail_list);exit;
        return $new_bargain_detail_list;
    }
    /*
     * 通过用户记录id获取砍价活动信息
     * **/
    public function getBargainByRecord($bargain_record_id)
    {
        $bargain_record_mdl = new VslBargainRecordModel();
        $bargain_record_list = $bargain_record_mdl->alias('br')
            ->where(['bargain_record_id'=>$bargain_record_id])
            ->join('vsl_bargain b','br.bargain_id=b.bargain_id','LEFT')
            ->find();
        return $bargain_record_list;
    }
    /*
     * 减砍价库存
     * **/
    public function subBargainGoodsStock($bargain_id, $num){
        $bargain_mdl = new VslBargainModel();
        $bargain_list = $bargain_mdl->where(['bargain_id'=>$bargain_id])->find();
        $bargain_list->bargain_stock = $bargain_list->bargain_stock-$num;
        $bargain_list->save();
    }
    /*
     * 加砍价库存
     * **/
    public function addBargainGoodsStock($bargain_id, $num){
        $bargain_mdl = new VslBargainModel();
        $bargain_list = $bargain_mdl->where(['bargain_id'=>$bargain_id])->find();
        $bargain_list->bargain_stock = $bargain_list->bargain_stock+$num;
        $bargain_list->save();
    }
}