<?php
namespace addons\wheelsurf\controller;

use addons\miniprogram\model\WeixinAuthModel;
use addons\wheelsurf\Wheelsurf as baseWheelsurf;
use addons\wheelsurf\model\VslWheelsurfModel;
use addons\wheelsurf\model\VslWheelsurfRecordsModel;
use addons\wheelsurf\server\Wheelsurf as WheelsurfServer;
use addons\coupontype\server\Coupon as CouponServer;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use data\model\VslGoodsModel;
use addons\gift\model\VslPromotionGiftModel;
use data\model\AlbumPictureModel;
use data\model\WebSiteModel;
use data\service\AddonsConfig;

class Wheelsurf extends baseWheelsurf
{
    public function __construct()
    {
        parent::__construct();

    }
    public function wheelsurfList()
    {
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $search_text = input('post.search_text','');
        $state = input('post.state','');
        $WheelsurfServer = new WheelsurfServer();
        if ($search_text) {
            $condition['wheelsurf_name'] = ['LIKE', '%' . $search_text . '%'];
        }
        if($state){
            $condition['state'] = $state;
        }
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $list = $WheelsurfServer->getWheelsurfList($page_index, $page_size, $condition, 'start_time desc');
        $vsl_wheelsurf = new VslWheelsurfModel();
        //判断pc端、小程序是否开启
        $addons_conf = new AddonsConfig();
        $pc_conf = $addons_conf->getAddonsConfig('pcport', $this->website_id);
        $is_minipro = getAddons('miniprogram', $this->website_id);
        if($is_minipro){
            $weixin_auth = new WeixinAuthModel();
            $new_auth_state = $weixin_auth->getInfo(['website_id' => $this->website_id], 'new_auth_state')['new_auth_state'];
            if(isset($new_auth_state) && $new_auth_state === 0){
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
        $list['addon_status'] = $addon_status;
        $count = $vsl_wheelsurf->getWheelsurfNum($condition);
        $list['count'] = $count;
        return $list;
    }
    /**
     * 优惠券/礼品券/商品列表
     */
    public function prizeType()
    {
        $type = input('type',0);
        $list = [];
        if($type==3){
            $is_use = getAddons('coupontype', $this->website_id, $this->instance_id);
            if($is_use==1){
                $condition['website_id'] = $this->website_id;
                $condition['shop_id'] = $this->instance_id;
                $condition['start_receive_time'] = ['elt',time()];
                $condition['end_receive_time'] = ['egt',time()];
                $CouponServer = new CouponServer();
                $coupon = $CouponServer->getCouponTypeList(1, 10, $condition);
                $list['code'] = 1;
                $list['data'] = $coupon['data'];
            }else{
                $list['code'] = 0;
                $list['data']['is_use'] = $is_use;
            }
            return $list;
        }
        if($type==4){
            $is_use = getAddons('giftvoucher', $this->website_id, $this->instance_id);
            if($is_use==1){
                $condition['gv.website_id'] = $this->website_id;
                $condition['gv.shop_id'] = $this->instance_id;
                $condition['gv.start_receive_time'] = ['elt',time()];
                $condition['gv.end_receive_time'] = ['egt',time()];
                $VoucherServer = new VoucherServer();
                $coupon = $VoucherServer->getGiftVoucherList(1, 10, $condition);
                $list['code'] = 1;
                $list['data'] = $coupon['data'];
            }else{
                $list['code'] = 0;
                $list['data']['is_use'] = $is_use;
            }
            return $list;
        }
        if($type==5){
            $sort = input('sort');
            if (request()->post('page_index')) {
                $page_index = input('post.page_index',1);
                $page_size = input('post.page_size',PAGESIZE);
                $search_text = input('post.search_text','');
                if ($search_text) {
                    $condition['goods_name'] = ['LIKE', '%' . $search_text . '%'];
                }
                $condition['website_id'] = $this->website_id;
                $condition['shop_id'] = $this->instance_id;
                $condition['stock'] = ['gt',0];
                $condition['goods_type'] = ['<>',4];
                $goods = new VslGoodsModel();
                $list = $goods->pageQuery($page_index, $page_size, $condition,'create_time desc','goods_id,goods_name,description,price,img_id_array,picture');
                $goods_list = [];
                if( !empty($list['data']) ){
                    foreach($list['data'] as $k => $v){
                        $goods_list[$k]['goods_id'] = $v['goods_id'];
                        $goods_list[$k]['goods_name'] = $v['goods_name'];
                        $goods_list[$k]['description'] = $v['description'];
                        $goods_list[$k]['price'] = $v['price'];
                        
                        // 查询图片表
                        $goods_img = new AlbumPictureModel();
                        $order = "instr('," . $v['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
                        $goods_img_list = $goods_img->getQuery([
                            'pic_id' => [
                                "in",
                                $v['img_id_array']
                            ]
                        ], '*', $order);
                        if (trim($v['img_id_array']) != "") {
                            $img_temp_array = array();
                            $img_array = explode(",", $v['img_id_array']);
                            foreach ($img_array as $ki => $vi) {
                                if (!empty($goods_img_list)) {
                                    foreach ($goods_img_list as $t => $m) {
                                        if ($m["pic_id"] == $vi) {
                                            $img_temp_array[] = $m;
                                        }
                                    }
                                }
                            }
                        }
                        if($img_temp_array){
                            foreach($img_temp_array as $kk => $vv){
                                $img_temp_array[$kk]['pic_cover'] = __IMG($vv['pic_cover']);
                            }
                        }
                        $goods_list[$k]["img_temp_array"] = $img_temp_array;
                        $goods_list[$k]['pic_cover'] = __IMG($img_temp_array[0]['pic_cover']);
                    }
                }
                $list['data'] = $goods_list;
                return $list;
            }
            $this->assign('sort', $sort);
            $this->fetch('template/' . $this->module . '/goodsDialog');
        }
        if($type==6){
            $sort = input('sort');
            if (request()->post('page_index')) {
                $page_index = input('post.page_index',1);
                $page_size = input('post.page_size',PAGESIZE);
                $search_text = input('post.search_text','');
                if ($search_text) {
                    $condition['vpg.gift_name'] = ['LIKE', '%' . $search_text . '%'];
                }
                $condition['vpg.website_id'] = $this->website_id;
                $condition['vpg.shop_id'] = $this->instance_id;
                $condition['vpg.stock'] = ['gt','sended'];
                $giftModel = new VslPromotionGiftModel();
                $list = $giftModel->getGiftViewList($page_index, $page_size, $condition, 'vpg.create_time desc');
                return $list;
            }
            $this->assign('sort', $sort);
            header("Content-type: text/html; charset=utf-8");
            $this->fetch('template/' . $this->module . '/giftlistDialog');
        }
    }
    
    public function addWheelsurf()
    {
        $input['wheelsurf_name'] = input('post.wheelsurf_name');
        $input['max_partake_daily'] = input('post.max_partake_daily');
        $input['max_partake'] = input('post.max_partake');
        $input['point'] = input('post.point');
        $input['desc'] = input('post.desc');
        $input['create_time'] = time();
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['end_time'] = strtotime(input('post.end_time')) + (86400 - 1);
        $input['term_num'] = input('post.term_num');
        $input['background_pic'] = input('post.background_pic','');
        $input['pointer_pic'] = input('post.pointer_pic','');
        if($input['start_time']>time())$input['state'] = 1;
        if($input['start_time']<=time() && $input['end_time']>=time())$input['state'] = 2;
        if($input['end_time']<time())$input['state'] = 3;
        $input['shop_id'] = $this->instance_id;
        $input['website_id'] = $this->website_id;
        $WheelsurfServer = new WheelsurfServer();
        $res = $WheelsurfServer->addWheelsurf($input);
        if($res){
            $input2 = [];
            $sorts = explode(',',input('post.sorts'));
            foreach ($sorts as $v){
                $input2[$v]['wheelsurf_id'] = $res;
                $input2[$v]['term_name'] = input('post.term_name'.$v);
                $input2[$v]['prize_type'] = input('post.prize_type'.$v);
                $input2[$v]['prize_name'] = input('post.prize_name'.$v);
                $input2[$v]['num'] = input('post.num'.$v);
                $input2[$v]['probability'] = input('post.probability'.$v);
                $input2[$v]['prize_type_id'] = input('post.prize_type_id'.$v);
                $input2[$v]['prize_point'] = input('post.prize_point'.$v,0);
                $input2[$v]['prize_money'] = input('post.prize_money'.$v,0);
                $input2[$v]['prize_pic'] = input('post.prize_pic'.$v,'');
                $input2[$v]['expire_time'] = strtotime(input('post.expire_time'.$v)) + (86400 - 1);
                $input2[$v]['sort'] = input('post.sort'.$v);
            }
            $WheelsurfServer->addWheelsurfPrize($input2);
            $this->addUserLog('添加大转盘', $res);
        }
        return AjaxReturn($res);
    }
    public function updateWheelsurf()
    {
        $input['wheelsurf_name'] = input('post.wheelsurf_name');
        $input['max_partake_daily'] = input('post.max_partake_daily');
        $input['max_partake'] = input('post.max_partake');
        $input['point'] = input('post.point');
        $input['desc'] = input('post.desc');
        $input['update_time'] = time();
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['end_time'] = strtotime(input('post.end_time')) + (86400 - 1);
        $input['term_num'] = input('post.term_num');
        $input['background_pic'] = input('post.background_pic','');
        $input['pointer_pic'] = input('post.pointer_pic','');
        if($input['start_time']>time())$input['state'] = 1;
        if($input['start_time']<=time() && $input['end_time']>=time())$input['state'] = 2;
        if($input['end_time']<time())$input['state'] = 3;
        $where['wheelsurf_id'] = input('post.wheelsurf_id');
        $where['shop_id'] = $this->instance_id;
        $where['website_id'] = $this->website_id;
        $WheelsurfServer = new WheelsurfServer();
        $res = $WheelsurfServer->updateWheelsurf($input,$where);
        if($res){
            $input2 = $input3 = [];
            $sorts = explode(',',input('post.sorts'));
            foreach ($sorts as $v){
                $prize_id = input('post.prize_id'.$v);
                if($prize_id>0){
                    $input2[$v]['prize_id'] = input('post.prize_id'.$v);
                    $input2[$v]['term_name'] = input('post.term_name'.$v);
                    $input2[$v]['prize_type'] = input('post.prize_type'.$v);
                    $input2[$v]['prize_name'] = input('post.prize_name'.$v);
                    $input2[$v]['num'] = input('post.num'.$v);
                    $input2[$v]['probability'] = input('post.probability'.$v);
                    $input2[$v]['prize_type_id'] = input('post.prize_type_id'.$v);
                    $input2[$v]['prize_point'] = input('post.prize_point'.$v,0);
                    $input2[$v]['prize_money'] = input('post.prize_money'.$v,0);
                    $input2[$v]['prize_pic'] = input('post.prize_pic'.$v,'');
                    $input2[$v]['expire_time'] = strtotime(input('post.expire_time'.$v)) + (86400 - 1);
                    $input2[$v]['sort'] = input('post.sort'.$v);
                }else{
                    $input3[$v]['wheelsurf_id'] = $where['wheelsurf_id'];
                    $input3[$v]['term_name'] = input('post.term_name'.$v);
                    $input3[$v]['prize_type'] = input('post.prize_type'.$v);
                    $input3[$v]['prize_name'] = input('post.prize_name'.$v);
                    $input3[$v]['num'] = input('post.num'.$v);
                    $input3[$v]['probability'] = input('post.probability'.$v);
                    $input3[$v]['prize_type_id'] = input('post.prize_type_id'.$v);
                    $input3[$v]['prize_point'] = input('post.prize_point'.$v,0);
                    $input3[$v]['prize_money'] = input('post.prize_money'.$v,0);
                    $input3[$v]['prize_pic'] = input('post.prize_pic'.$v,'');
                    $input3[$v]['expire_time'] = strtotime(input('post.expire_time'.$v)) + (86400 - 1);
                    $input3[$v]['sort'] = input('post.sort'.$v);
                }
            }
            $WheelsurfServer->updateWheelsurfPrize($input2,$where['wheelsurf_id']);
            $WheelsurfServer->addWheelsurfPrize($input3);
            $this->addUserLog('修改大转盘', $res);
        }
        return AjaxReturn($res);
    }
    public function deleteWheelsurf()
    {
        $wheelsurf_id = (int)input('post.wheelsurf_id');
        $condition['wheelsurf_id'] = $wheelsurf_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $condition['end_time'] = ['lt',time()];
        if (empty($wheelsurf_id)) {
            return ['code' => -1,'message' => '没有获取到大转盘信息'];
        }
        $WheelsurfServer = new WheelsurfServer();
        $res = $WheelsurfServer->deleteWheelsurf($condition);
        if($res){
            $this->addUserLog('删除大转盘', $wheelsurf_id);
        }
        return AjaxReturn($res);
    }
    public function historyWheelsurf()
    {
        $WheelsurfServer = new WheelsurfServer();
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $where['vmp.state'] = input('post.state');
        $where['vwr.wheelsurf_id'] = (int)input('get.wheelsurf_id');
        $where['vwr.website_id'] = $this->website_id;
        $where['vwr.shop_id'] = $this->instance_id;
        $start_time = input('post.start_time');
        $end_time = input('post.end_time');
        if($start_time){
            $start_time = strtotime($start_time);
            $where['vmp.prize_time'] = ['egt',$start_time];
        }
        if($end_time){
            $end_time = strtotime($end_time) + (86400 - 1);
            $where['vmp.prize_time'] = ['elt',$end_time];
        }
        if($start_time && $end_time){
            $where['vmp.prize_time'] = array(['egt',$start_time],['elt',$end_time],'and');
        }
        $fields = 'vmp.term_name,vmp.prize_name,vmp.prize_time,vmp.state,su.user_tel,su.nick_name';
        $list = $WheelsurfServer->getWheelsurfHistory($page_index, $page_size, $where, $fields,'vmp.prize_time desc');
        $record = new VslWheelsurfRecordsModel();
        $count = $record->getUserPrizeNum($where);
        $list['count'] = $count;
        return $list;
    }

    public function saveSetting()
    {
        $WheelsurfServer = new WheelsurfServer();
        $is_wheelsurf = (int)input('post.is_wheelsurf');
        $result = $WheelsurfServer->saveConfig($is_wheelsurf);
        if($result){
            $this->addUserLog('修改大转盘设置', $result);
        }
        setAddons('wheelsurf', $this->website_id, $this->instance_id);
        return AjaxReturn($result);
    }
    
    /**
     * wab活动用户次数
     */
    public function userFrequency()
    {
        $wheelsurf_id= (int)input('wheelsurf_id');
        $WheelsurfServer = new WheelsurfServer();
        $info = $WheelsurfServer->userFrequency($wheelsurf_id);
        if($info){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $info;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * wab活动中奖名单
     */
    public function prizeRecords()
    {
        $wheelsurf_id= (int)input('wheelsurf_id');
        $WheelsurfServer = new WheelsurfServer();
        $info = $WheelsurfServer->prizeRecords($wheelsurf_id);
        if($info){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $info;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * wab活动详情
     */
    public function wheelsurfInfo()
    {
        $wheelsurf_id= (int)input('wheelsurf_id');
        $WheelsurfServer = new WheelsurfServer();
        $info = $WheelsurfServer->wheelsurfInfo($wheelsurf_id);
        if($info){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $info;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * wab转盘抽奖
     */
    public function userWheelsurf()
    {
        $wheelsurf_id= (int)input('wheelsurf_id');
        $WheelsurfServer = new WheelsurfServer();
        $info = $WheelsurfServer->userWheelsurf($wheelsurf_id);
        return json($info);
    }
}