<?php
namespace data\service;
use data\extend\weixin\WxTicketApi as ticket;
/**
 * 微信卡券
 */
use data\service\BaseService as BaseService;
use data\model\UserModel;

class WxTicket extends BaseService
{
    //上传logo
    public function uploadLogo($url){
        $ticket = new ticket($this->website_id);
        $color = $ticket->upload_logo($url);
        if(empty($color['url'])){
            $data['code'] = -1;
            $data['message'] = "LOGO上传失败";
            return $data;
        }else{
            return $color;
        }
    }

    //创建卡券
    public function createCard($card_info){
        //卡券信息
        $data = [];
        $data['card']['card_type'] = 'GIFT';
        $data['card']['gift']['base_info']['logo_url'] = ($card_info['logo_url'])?$card_info['logo_url']:''; //卡券的商户logo
        $data['card']['gift']['base_info']['brand_name'] = ($card_info['brand_name'])?$card_info['brand_name']:''; //商户名字,字数上限为12个汉字
        $data['card']['gift']['base_info']['code_type'] = 'CODE_TYPE_QRCODE'; //码型
        $data['card']['gift']['base_info']['title'] = $card_info['card_title']; //卡券名
        $data['card']['gift']['base_info']['color'] = $this->cardColor($card_info['card_color']);; //券颜色
        $data['card']['gift']['base_info']['notice'] = $card_info['op_tips']; //卡券使用提醒
        $data['card']['gift']['base_info']['can_give_friend'] = ($card_info['send_set']==1)?true:false; //卡券是否可转赠
        $data['card']['gift']['base_info']['custom_url_name'] = '消费卡列表'; //营销场景的自定义入口名称
        $data['card']['gift']['base_info']['custom_url'] = __URL('wap/consumercard/list'); //入口跳转外链的地址链接
        $data['card']['gift']['base_info']['description'] = ' '; //卡券使用说明
        $data['card']['gift']['base_info']['use_custom_code'] = true; //是否自定义Code码
        $data['card']['gift']['base_info']['bind_openid'] = true; //是否指定用户领取
        if(!empty($card_info['store_service'])){
            $store_service = explode(",",$card_info['store_service']);
            $data['card']['gift']['advanced_info']['business_service'] = $store_service; //商家服务类型
        }
        if($this->shopstatus && $card_info['service_phone']){
            $data['card']['gift']['base_info']['service_phone'] = $card_info['service_phone']; //客服电话
        }
        //时间信息
        $data['card']['gift']['base_info']['date_info']['type'] = 'DATE_TYPE_FIX_TIME_RANGE'; //使用时间的类型
        $data['card']['gift']['base_info']['date_info']['begin_timestamp'] = time(); //表示起用时间
        $data['card']['gift']['base_info']['date_info']['end_timestamp'] = $card_info['end_timestamp']; //表示结束时间 
        //数量
        $data['card']['gift']['base_info']['sku']['quantity'] = $card_info['quantity']; //卡券库存的数量
        if($card_info['icon_url']){
            $data['card']['gift']['advanced_info']['abstract']['abstract'] = $card_info['card_descript'];
            $data['card']['gift']['advanced_info']['abstract']['icon_url_list'][] = ($card_info['icon_url'])?$card_info['icon_url']:'';
        }
        $ticket = new ticket($this->website_id);
        $result = $ticket->create_card($data);
        return $result;
    }
    
    //卡券颜色
    public function cardColor($data){
        $color = [
            '#63b359'=>'Color010',
            '#2c9f67'=>'Color020',
            '#509fc9'=>'Color030',
            '#5885cf'=>'Color040',
            '#9062c0'=>'Color050',
            '#d09a45'=>'Color060',
            '#e4b138'=>'Color070',
            '#ee903c'=>'Color080',
            '#f08500'=>'Color081',
            '#a9d92d'=>'Color082',
            '#dd6549'=>'Color090',
            '#cc463d'=>'Color100',
            '#cf3e36'=>'Color101',
            '#5E6671'=>'Color102'
        ];
        if(empty($color[$data])){
            return '';
        }
        return $color[$data];
    }

    //领取卡券
    public function addCard($card_list){
        $card_lists = [];
        $time = time();
        $ticket = new ticket($this->website_id);
        $api_ticket = $ticket->single_get_access_ticket();
        $user = new UserModel();
        $user_info = $user->getInfo(['uid'=>$this->uid],'wx_openid');
        if(empty($api_ticket) || empty($user_info['wx_openid'])){
            return $card_lists;
        }
        foreach ($card_list as $k => $v) {
            if($v['card_type']==2 && $v['wx_card_id']){
                $data = $card_ext = [];
                $nonce_str = $this->generateNonceStr();
                $data['cardId'] = $v['wx_card_id'];
                $card_ext['code'] = $v['card_code'];
                $card_ext['openid'] = $user_info['wx_openid'];
                $card_ext['timestamp'] = $time;
                $card_ext['nonce_str'] = $nonce_str;
                $signature = $this->cardSignature($card_ext['code'],$card_ext['timestamp'],$data['cardId'],$api_ticket,$card_ext['nonce_str'],$card_ext['openid']);
                $card_ext['signature'] = $signature;
                $data['cardExt'] = json_encode($card_ext, JSON_FORCE_OBJECT);
                $card_lists['cardList'][] = $data;
            }
        }
        return $card_lists;
    }

    /** 
     * 获取卡券签名
     */
    public function cardSignature($code,$timestamp,$card_id,$api_ticket, $nonce_str,$openid){
        //按官网要求排序
        $str = [];
        $str[0] = $code;
        $str[1] = $timestamp;
        $str[2] = $card_id;
        $str[3] = $api_ticket;
        $str[4] = $nonce_str;
        $str[5] = $openid;
        sort($str, SORT_STRING );
        $signature = sha1(implode($str));
        return $signature;
    }

    //生成随机字符串
    function generateNonceStr($length=16){
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for($i = 0; $i < $length; $i++)
        {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
    
    /**
     * 核销卡券
     */
    public function cardConsume($card_id,$card_code){
        $result = 0;
        $ticket = new ticket($this->website_id);
        $data = [];
        $data['card_id'] = $card_id;
        $data['code'] = $card_code;
        $data['check_consume'] = true;
        $card = $ticket->card_code($data);
        if(!empty($card['user_card_status']) && $card['user_card_status']=='NORMAL'){
            $data = [];
            $data['card_id'] = $card_id;
            $data['code'] = $card_code;
            $result = $ticket->card_consume($data);
        }
        return $result;
    }
}
