<?php
namespace  data\extend\weixin;

use data\service\Config as config;
use think\Cache as cache;
use data\extend\WchatOauth;

/********************************************************

 *      @version 2.0.1
 *      @uses $wxApi = new WxApi();
 *      @package 微信API接口 陆续会继续进行更新

 ********************************************************/


class WxTicketApi {


    public $appid = '';         //appid
    public $appsecret = '';     //appsecret
    public $access_token = '';      //access_token

    public function __construct($website_id){
        $this->website_id = $website_id;
        $config = new config();
        $wchat_config = $config->getInstanceWchatConfig(0,$this->website_id);
        $this->appid = $wchat_config['value']['appid'];
        $this->appsecret = $wchat_config['value']['appsecret'];
        //缓存token
        $wchat_oauth = new WchatOauth($this->website_id);
        $this->access_token = $wchat_oauth->get_access_token($website_id);
    }
    
    /**
     * 获取ticket
     */
    public function single_get_access_ticket($website_id = 0)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $cache = cache::get('ticket-'.$website_id);
        if(empty($cache['ticket']) || ($cache['ticket'] && $cache['time']+7000<time())){
            $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$this->access_token.'&type=wx_card';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $a = curl_exec($ch);
            curl_close($ch);
            $strjson = json_decode($a);
            if ($strjson == false || empty($strjson)) {
                return '';
            } else {
                $ticket = $strjson->ticket;
                if (!empty($ticket)) {
                    $cache = [];
                    $cache['ticket'] = $ticket;
                    $cache['time'] = time();
                    cache::set('ticket-'.$website_id, $cache, 7000);
                }
            }
        }
        return $cache['ticket'];
    }

    //发送数据
    public function send_data($url,$data=''){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    //上传logo
    public function upload_logo($logo_url){
        $url = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".$this->access_token;
        $root = dirname(dirname(dirname(dirname(__File__))));
        if (class_exists('\CURLFile')) {
            $data['buffer'] = new \CURLFile($root.$logo_url);
        } else {
            $data['buffer'] = '@'.$root.$logo_url;
        }
        $data = $this->send_data($url,$data);
        $result = json_decode($data, true);
        return $result;
    }

    //创建卡券
    public function create_card($data){
        $url = "https://api.weixin.qq.com/card/create?access_token=".$this->access_token;
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        $data = $this->send_data($url,$data);
        $result = json_decode($data, true);
        return $result;
    }
    
    //修改卡券
    public function update_card($data){
        $url = "https://api.weixin.qq.com/card/update?access_token=".$this->access_token;
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        $data = $this->send_data($url,$data);
        $result = json_decode($data, true);
        return $result;
    }
    
    //修改卡券库存
    public function stock_card($data){
        $url = "https://api.weixin.qq.com/card/modifystock?access_token=".$this->access_token;
        print_r($data);
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        $data = $this->send_data($url,$data);
        $result = json_decode($data, true);
        return $result;
    }

    //查询code接口
    public function card_code($data){
        $url = "https://api.weixin.qq.com/card/code/get?access_token=".$this->access_token;
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        $data = $this->send_data($url,$data);
        $result = json_decode($data, true);
        return $result;
    }
    
    //核销code
    public function card_consume($data){
        $url = "https://api.weixin.qq.com/card/code/consume?access_token=".$this->access_token;
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        $data = $this->send_data($url,$data);
        $result = json_decode($data, true);
        return $result;
    }
    
    //删除卡券
    public function card_delete($data){
        $url = "https://api.weixin.qq.com/card/delete?access_token=".$this->access_token;
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        $data = $this->send_data($url,$data);
        $result = json_decode($data, true);
        return $result;
    }
}