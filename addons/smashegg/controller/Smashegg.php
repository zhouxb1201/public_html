<?php
namespace addons\smashegg\controller;

use addons\miniprogram\model\WeixinAuthModel;
use addons\smashegg\Smashegg as baseSmashegg;
use addons\smashegg\model\VslSmashEggModel;
use addons\smashegg\model\VslSmashEggRecordsModel;
use addons\smashegg\server\SmashEgg as SmashEggServer;
use addons\coupontype\server\Coupon as CouponServer;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use data\model\VslGoodsModel;
use addons\gift\model\VslPromotionGiftModel;
use data\model\AlbumPictureModel;
use data\model\WebSiteModel;
use data\service\AddonsConfig;

class Smashegg extends baseSmashegg
{
    public function __construct()
    {
        parent::__construct();

    }
    public function smasheggList()
    {
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $search_text = input('post.search_text','');
        $state = input('post.state','');
        $SmashEggServer = new SmashEggServer();
        if ($search_text) {
            $condition['smashegg_name'] = ['LIKE', '%' . $search_text . '%'];
        }
        if($state){
            $condition['state'] = $state;
        }
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $list = $SmashEggServer->getSmashEggList($page_index, $page_size, $condition, 'start_time desc');
        $vsl_smashegg = new VslSmashEggModel();
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
        $count = $vsl_smashegg->getSmasheggNum($condition);
        $list['addon_status'] = $addon_status;
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
    
    public function addSmashegg()
    {
        $input['smashegg_name'] = input('post.smashegg_name');
        $input['max_partake_daily'] = input('post.max_partake_daily');
        $input['max_partake'] = input('post.max_partake');
        $input['point'] = input('post.point');
        $input['desc'] = input('post.desc');
        $input['create_time'] = time();
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['end_time'] = strtotime(input('post.end_time')) + (86400 - 1);
        $input['noprize_tip'] = input('post.noprize_tip');
        if($input['start_time']>time())$input['state'] = 1;
        if($input['start_time']<=time() && $input['end_time']>=time())$input['state'] = 2;
        if($input['end_time']<time())$input['state'] = 3;
        $input['shop_id'] = $this->instance_id;
        $input['website_id'] = $this->website_id;
        $SmashEggServer = new SmashEggServer();
        $res = $SmashEggServer->addSmashEgg($input);
        if($res){
            $input2 = [];
            $sorts = explode(',',input('post.sorts'));
            foreach ($sorts as $v){
                $input2[$v]['smash_egg_id'] = $res;
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
            $SmashEggServer->addSmasheggPrize($input2);
            $this->addUserLog('添加砸金蛋', $res);
        }
        return AjaxReturn($res);
    }
    public function updateSmashegg()
    {
        $input['smashegg_name'] = input('post.smashegg_name');
        $input['max_partake_daily'] = input('post.max_partake_daily');
        $input['max_partake'] = input('post.max_partake');
        $input['point'] = input('post.point');
        $input['desc'] = input('post.desc');
        $input['update_time'] = time();
        $input['start_time'] = strtotime(input('post.start_time'));
        $input['end_time'] = strtotime(input('post.end_time')) + (86400 - 1);
        $input['noprize_tip'] = input('post.noprize_tip');
        if($input['start_time']>time())$input['state'] = 1;
        if($input['start_time']<=time() && $input['end_time']>=time())$input['state'] = 2;
        if($input['end_time']<time())$input['state'] = 3;
        $where['smash_egg_id'] = input('post.smash_egg_id');
        $where['shop_id'] = $this->instance_id;
        $where['website_id'] = $this->website_id;
        $SmashEggServer = new SmashEggServer();
        $res = $SmashEggServer->updateSmashEgg($input,$where);
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
                    $input3[$v]['smash_egg_id'] = $where['smash_egg_id'];
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
            $SmashEggServer->updateSmasheggPrize($input2,$where['smash_egg_id']);
            $SmashEggServer->addSmasheggPrize($input3);
            $this->addUserLog('修改砸金蛋', $res);
        }
        return AjaxReturn($res);
    }
    public function deleteSmashegg()
    {
        $smash_egg_id = (int)input('post.smash_egg_id');
        $condition['smash_egg_id'] = $smash_egg_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $condition['end_time'] = ['lt',time()];
        if (empty($smash_egg_id)) {
            return ['code' => -1,'message' => '没有获取到砸金蛋信息'];
        }
        $SmashEggServer = new SmashEggServer();
        $res = $SmashEggServer->deleteSmashEgg($condition);
        if($res){
            $this->addUserLog('删除砸金蛋', $smash_egg_id);
        }
        return AjaxReturn($res);
    }
    public function historySmashegg()
    {
        $SmashEggServer = new SmashEggServer();
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $where['vmp.state'] = input('post.state');
        $where['vser.smash_egg_id'] = (int)input('get.smash_egg_id');
        $where['vser.website_id'] = $this->website_id;
        $where['vser.shop_id'] = $this->instance_id;
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
        $list = $SmashEggServer->getSmasheggHistory($page_index, $page_size, $where, $fields,'vmp.prize_time desc');
        $record = new VslSmashEggRecordsModel();
        $count = $record->getUserPrizeNum($where);
        $list['count'] = $count;
        return $list;
    }

    public function saveSetting()
    {
        $SmashEggServer = new SmashEggServer();
        $is_smashegg = (int)input('post.is_smashegg');
        $result = $SmashEggServer->saveConfig($is_smashegg);
        if($result){
            $this->addUserLog('修改砸金蛋设置', $result);
        }
        setAddons('smashegg', $this->website_id, $this->instance_id);
        return AjaxReturn($result);
    }
    
    /**
     * wab活动用户次数
     */
    public function userFrequency()
    {
        $smash_egg_id= (int)input('smash_egg_id');
        $SmashEggServer = new SmashEggServer();
        $info = $SmashEggServer->userFrequency($smash_egg_id);
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
        $smash_egg_id= (int)input('smash_egg_id');
        $SmashEggServer = new SmashEggServer();
        $info = $SmashEggServer->prizeRecords($smash_egg_id);
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
    public function smasheggInfo()
    {
        $smash_egg_id= (int)input('smash_egg_id');
        $SmashEggServer = new SmashEggServer();
        $info = $SmashEggServer->smasheggInfo($smash_egg_id);
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
     * wab砸金蛋
     */
    public function userSmashegg()
    {
        $smash_egg_id= (int)input('smash_egg_id');
        $SmashEggServer = new SmashEggServer();
        $info = $SmashEggServer->userSmashegg($smash_egg_id);
        return json($info);
    }
}