<?php

namespace data\service;

use data\model\VslMemberCardModel;
use data\model\VslMemberCardRecordsModel;
use data\model\VslOrderGoodsModel;
use addons\shop\service\Shop;
use data\model\AlbumPictureModel;
use data\service\WeixinCard;
/**
 *会员计时/次商品
 */
class MemberCard extends BaseService {
    /**
     * @param array $input
     * @return int
     */
    public function saveData($order_id)
    {
        $member_card = new VslMemberCardModel();
        $member_card->startTrans();
        try {
            $order_goods = new VslOrderGoodsModel();
            $order_goods_info = $order_goods->getInfo(['order_id' => $order_id]);
            $ids = '';
            if($order_goods_info){
                $input = [];
                $input['goods_name'] = $order_goods_info['goods_name'];
                $input['goods_picture'] = $order_goods_info['goods_picture'];
                $input['card_title'] = $order_goods_info['card_title'];
                $input['uid'] = $order_goods_info['buyer_id'];
                $input['store_id'] = $order_goods_info['card_store_id'];
                $input['card_type'] = (empty($order_goods_info['wx_card_id']))?1:2;
                $input['wx_card_id'] = $order_goods_info['wx_card_id'];
                $input['num'] = 0;
                $input['invalid_time'] = $order_goods_info['invalid_time'];
                $input['website_id'] = $order_goods_info['website_id'];
                $input['create_time'] = time();
                if($order_goods_info['cancle_times']==0){
                    $input['count_num'] = -999;//无限
                }else{
                    if($order_goods_info['cart_type']==1){
                        $input['count_num'] =  $order_goods_info['cancle_times']*$order_goods_info['num'];
                    }else if($order_goods_info['cart_type']==2){
                        $input['count_num'] =  $order_goods_info['cancle_times'];
                    }
                }
                if($order_goods_info['cart_type']==1){
                    $code = 'B' . rand(100000, 999999). rand(10000, 99999);
                    $input['card_code'] = $code;
                    $url  = __URL('clerk/verify/cardvoucher/'.$code);
                    $result = $member_card->save($input);
                    if($result>0){
                        $ids = $result;
                        $qrcode = getQRcode($url, 'upload/' . $order_goods_info['website_id'] . '/' . $this->instance_id . '/goods_card_qrcode', 'goods_card_qrcode_' . $result);
                        $member_card->save(['card_codeImg'=>$qrcode],['card_id'=>$result]);
                    }
                }else if($order_goods_info['cart_type']==2){
                    for ($i=1; $i<=$order_goods_info['num']; $i++) {
                        $code = 'B' . rand(100000, 999999). rand(10000, 99999);
                        $input['card_code'] = $code;
                        $url  = __URL('clerk/verify/cardvoucher/'.$code);
                        $result = $member_card->insert($input);
                        if($result>0){
                            $ids .= $result.',';
                            $qrcode = getQRcode($url, 'upload/' . $order_goods_info['website_id'] . '/' . $this->instance_id . '/goods_card_qrcode', 'goods_card_qrcode_' . $result);
                            $member_card->save(['card_codeImg'=>$qrcode],['card_id'=>$result]);
                        }
                    }
                }
            }
            $member_card->commit();
            return $ids;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $member_card->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 前台列表
     */
    public function getMemberCardList($page_index, $page_size, $condition, $order = '')
    {
        $member_card = new VslMemberCardModel();
        $uid = $this->uid;
        $list = [];
        if (!empty($uid)) {
            $state = $condition['state'];
            if($state==0){
                $condition['invalid_time'] =['egt',time()];
                $viewObj = $member_card->where('count_num > num or count_num = -999');
            }else if($state==1){
                $viewObj = $member_card->where('count_num = num');
            }else if($state==2){
                $condition['invalid_time'] =['lt',time()];
                $viewObj = $member_card;
            }else{
                $viewObj = $member_card;
            }
            unset($condition['state']);
            $queryList = $member_card->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
            $queryCount = $member_card->viewCount($viewObj,$condition);
            $card_list = $member_card->setReturnList($queryList, $queryCount, $page_size);
            $list['data'] = [];
            if(!empty($card_list['data'])){
                foreach ($card_list['data'] as $k => $v) {
                    $list['data'][$k]['card_id'] = $v['card_id'];
                    $list['data'][$k]['goods_name'] = $v['goods_name'];
                    $list['data'][$k]['card_title'] = $v['card_title'];
                    $list['data'][$k]['state'] = $state;
                    $list['data'][$k]['count_num'] = $v['count_num'];
                    if($v['count_num']==-999){
                        $list['data'][$k]['surplus_num'] = 0;
                        $time = ($v['invalid_time'] - time())/86400;
                        if($time>0){
                            $list['data'][$k]['surplus_num'] = round($time);
                        }
                        $list['data'][$k]['type'] = 1;
                    }else{
                        $list['data'][$k]['surplus_num'] = $v['count_num']-$v['num'];
                        $list['data'][$k]['type'] = 2;
                    }
                    $list['data'][$k]['invalid_time'] = date('Y-m-d H:i:s', $v['invalid_time']);
                }
            }
            $list['total_count'] = $card_list['total_count'];
            $list['page_count'] = $card_list['page_count'];
        }
        return $list;
    }
    /**
     * 前台详情
     */
    public function getCardDetail($card_id,$card_code='',$wx_card_id='')
    {
        if($card_id>0){
            $condition['vmc.card_id'] = $card_id;
            $condition['vmc.uid'] = $this->uid;
        }else if($card_code){
            $condition['vmc.card_code'] = $card_code;
        }else if($wx_card_id){
            $condition['vmc.wx_card_id'] = $wx_card_id;
        }
        $condition['vmc.website_id'] = $this->website_id;
        $member_card = new VslMemberCardModel();
        $card_info = $member_card->getDetail($condition);
        $info = [];
        if(!empty($card_info)){
            $info['card_id'] = $card_info['card_id'];
            $info['goods_name'] = $card_info['goods_name'];
            $picture = new AlbumPictureModel();
            $goods_picture = $picture->get($card_info['goods_picture']);
            $info['goods_picture'] = $goods_picture['pic_cover_small']?__IMG($goods_picture['pic_cover_small']):'';
            $info['card_title'] = $card_info['card_title'];
            $info['card_type'] = $card_info['card_type'];
            $info['wx_card_id'] = $card_info['wx_card_id'];
            $info['wx_card_state'] = $card_info['wx_card_state'];
            if(!$wx_card_id){
                $info['card_code'] = $card_info['card_code'];
                $info['card_codeImg'] = __IMG($card_info['card_codeImg']);
            }
            $info['count_num'] = $card_info['count_num'];
            $info['invalid_time'] = date('Y-m-d H:i:s', $card_info['invalid_time']);
            if($card_info['count_num']==-999){
                $info['surplus_num'] = 0;
                $time = ($card_info['invalid_time'] - time())/86400;
                if($time>0){
                    $info['surplus_num'] = round($time);
                }
                $info['count_num'] = 0;
                $info['type'] = 1;
            }else{
                $info['surplus_num'] = $card_info['count_num']-$card_info['num'];
                $info['count_num'] = $card_info['count_num'];
                $info['type'] = 2;
            }
            $info['store_id'] = $card_info['store_id'];
            $info["store_name"] = $card_info['store_name'];
            $info["store_tel"] = $card_info['store_tel'];
            if($card_info['invalid_time']<time()){
                $info["state"] = 3;
            }else{
                if($card_info['count_num']==-999){
                    $info["state"] = 1;
                }else{
                    if($info['surplus_num']>0){
                        $info["state"] = 1;
                    }else{
                        $info["state"] = 2;
                    }
                }
            }
            $address = new Address();
            $address_name['province_name'] = $address->getProvinceName($card_info['province_id']);
            $address_name['city_name'] = $address->getCityName($card_info['city_id']);
            $address_name['dictrict_name'] = $address->getDistrictName($card_info['district_id']);
            $info["address"] = $address_name['province_name'].$address_name['city_name'].$address_name['dictrict_name'].$card_info['address'];
            if(getAddons('shop', $this->website_id) && $info['shop_id']){
                $shop = new Shop();
                $shop_info = $shop->getShopInfo($info['shop_id'],'shop_name');
                $shop_name = $shop_info['shop_name'];
            }else{
                $shop_name = '自营店';
            }
            $info['shop_name'] = $shop_name;
        }
        return $info;
    }
    /**
     * 前台核销记录
     */
    public function getCardRecordList($page_index, $page_size,$card_id)
    {
        $card_record = new VslMemberCardRecordsModel();
        if($card_id>0){
            $where['vmcr.card_id'] = $card_id;
        }
        $where['vmcr.uid'] = $this->uid;
        $where['vmcr.website_id'] = $this->website_id;
        $field = 'vmcr.record_id,vmcr.card_id,vmcr.num,vmcr.create_time,vs.store_name';
        $list = $card_record->getViewList($page_index, $page_size, $where,$field,'create_time desc');
        if(!empty($list['data'])){
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['num'] = '-'.$v['num'];
                $list['data'][$k]['store_name'] = $v['store_name'].'核销';
            }
        }
        return $list;
    }
    /**
     * 前台核销
     */
    public function getCardUse($card_code, $store_id, $assistantId)
    {
        $result = 0;
        $condition['card_code'] = $card_code;
        $condition['website_id'] = $this->website_id;
        $member_card = new VslMemberCardModel();
        $card_record = new VslMemberCardRecordsModel();
        $info = $member_card::get($condition);
        if($info){
            $result = $this->isCardUse($info,$store_id);
            if($result==1){
                $member_card->where($condition)->setInc('num', 1);
                $data = [];
                $data['card_id'] = $info['card_id'];
                $data['uid'] = $info['uid'];
                $data['num'] = 1;
                $data['create_time'] = time();
                $data['website_id'] = $this->website_id;
                $data['assistant_id'] = $assistantId;
                $result = $card_record->save($data);
            }
            if($info['card_type']==2 && $info['wx_card_state']==1){//微信卡券核销
                $wx_card = $member_card::get($condition);
                if($wx_card['count_num']!=-999 && $wx_card['count_num']<=$wx_card['num']){
                    $weixin_card = new WeixinCard();
                    $re = $weixin_card->cardConsume($wx_card['wx_card_id'],$wx_card['card_code']);
                }
            }
        }
        return $result;
    }
    /**
     * 判读消费卡是否可使用，门店不符合0，已过期-1，核销次数用完-2，可使用返回1
     *
     */
    public function isCardUse($info,$store_id)
    {
        if($info['store_id']!=$store_id){
            return 0;
        }
        if(time() > $info['invalid_time']){
            return -1;
        }
        if($info['count_num']==-999 || $info['count_num']>$info['num']){
            return 1;
        }else{
            return -2;
        }
    }
    /**
     * 卡券列表
     */
    public function getCardsList($cards_id)
    {
        $condition = [];
        $condition['uid'] = $this->uid;
        $condition['website_id'] = $this->website_id;
        $cards = strpos($cards_id, ',');
        $member_card = new VslMemberCardModel();
        $card = $member_card->field('card_id,wx_card_id,wx_card_state,card_type,card_code');
        if($cards){
            $card_ids = explode(',',$cards_id);
            foreach ($card_ids as $k => $v) {
                if($v){
                    $condition['card_id'][] = ['=',$v];
                    $condition['card_id'][] = 'or';
                }
            }
        }else{
            $condition['card_id'] = $cards_id;
        }
        $card_list = $card->where($condition)->select();
        return $card_list;
    }
    /**
     * 微信卡券领取成功
     */
    public function getwxCardUse($cards_id)
    {
        $result = 0;
        $cards = strpos($cards_id, ',');
        $member_card = new VslMemberCardModel();
        if($cards){
            $card_ids = explode(',',$cards_id);
            $data = [];
            foreach ($card_ids as $k => $v) {
                if($v){
                    $condition['card_id'] = $v;
                    $info = $member_card::get($condition);
                    if($info){
                        $data[] = ['card_id'=>$v,'wx_card_state'=>1];
                    }
                }
            }
            if($data){
                $result = $member_card->saveAll($data);
                if($result)$result = 1;
            }
        }else{
            $condition['card_id'] = $cards_id;
            $info = $member_card::get($condition);
            if($info){
                $result = $member_card->where(['card_id'=>$cards_id])->update(['wx_card_state' => 1]);
            }
        }
        return $result;
    }
}
