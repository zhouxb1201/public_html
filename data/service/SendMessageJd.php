<?php

namespace data\service;

use data\model\ConfigModel;
use data\service\Addons;
use data\model\MessageCountModel;

require_once 'data/extend/jdsms/HttpClient.php';

class SendMessageJd extends BaseService {

    protected $userid = NULL;
    protected $timestamp = NULL;
    protected $username = NULL;
    protected $password = NULL;
    protected $alarm_num = NULL;
    protected $alarm_mobile = NULL;
    protected $alarm_num_master = NULL;
    protected $alarm_mobile_master = NULL;
    protected $jd_sign_name_master = NULL;
    protected $jd_sign_name = NULL;
    protected $count = 0;
    protected $sign = NULL;
    protected $url = 'http://dc.28inter.com/v2sms.aspx';

    public function __construct() {
        parent::__construct();
        $config_model = new ConfigModel();
        $info_master = $config_model->getInfo(['key' => 'MOBILEMESSAGE', 'website_id' => 0, 'instance_id' => 0], '*');
        $master_array = json_decode($info_master['value'], true);
        
        
        $this->userid = $master_array['userid'];
        $this->username = $master_array['username'];
        $this->password = $master_array['password'];
        $this->alarm_num_master = $master_array['alarm_num'];
        $this->alarm_mobile_master = $master_array['alarm_mobile'];
        $this->jd_sign_name_master = $master_array['jd_sign_name'];
        
        $this->timestamp = date('YmdHis', time());
        $this->sign = $this->getSign();
        if($this->website_id){
            $messageCount = new MessageCountModel();
            $count = $messageCount->getInfo(['website_id' => $this->website_id], 'count');
            $this->count = $count ? $count['count'] : 0;
            $info = $config_model->getInfo(['key' => 'MOBILEMESSAGE', 'website_id' => $this->website_id, 'instance_id' => 0], '*');
            $info_array = json_decode($info['value'], true);
            $this->alarm_num = $info_array['alarm_num'];
            $this->alarm_mobile = $info_array['alarm_mobile'];
            $this->jd_sign_name = $info_array['jd_sign_name'];
        }
    }

    public function sendsms($mobile, $content, $website_id=0, $sign_master = 0) {
        if($website_id){
            $this->website_id = $website_id;
        }
        if($this->website_id){
            $config_model = new ConfigModel();
            $messageCount = new MessageCountModel();
            $count = $messageCount->getInfo(['website_id' => $this->website_id], 'count');
            $this->count = $count ? $count['count'] : 0;
            $info = $config_model->getInfo(['key' => 'MOBILEMESSAGE', 'website_id' => $this->website_id, 'instance_id' => 0], '*');
            $info_array = json_decode($info['value'], true);
            $this->alarm_num = $info_array['alarm_num'];
            $this->alarm_mobile = $info_array['alarm_mobile'];
            $this->jd_sign_name = $info_array['jd_sign_name'];
        }
        if($sign_master){//使用平台签名
            if($this->jd_sign_name_master){
                $content = $content.'【'.$this->jd_sign_name_master.'】';
            }
        }else{
            if($this->jd_sign_name){
                $content = $content.'【'.$this->jd_sign_name.'】';
            }
        }
        
        $pageContents = \HttpClient::quickPost($this->url, array(
                    'action' => 'send',
                    'rt' => 'json',
                    'userid' => $this->userid,
                    'timestamp' => $this->timestamp,
                    'sign' => $this->sign,
                    'mobile' => $mobile,
                    'content' => $content,
                    'sendtime' => '',
                    'extno' => '',
        ));
        $x = json_decode($pageContents,true);
        if ($x['ReturnStatus'] == 'Success') {
            $result['code'] = 0;
            $result['message'] = '发送成功';
            if($this->website_id){
                $addons = new Addons();
                $addons->reduceMessageCount(1, $this->website_id);
                if($this->alarm_num && $this->count - 1 == $this->alarm_num){
                    $addons->reduceMessageCount(1, $this->website_id);
                    $this->sendsmsAlbum($this->alarm_mobile,'短信余额数量到达预警数量,请及时充值',$sign_master);
                }
            }
            if($x['RemainPoint'] == $this->alarm_num_master){
                $this->sendsmsAlbum($this->alarm_mobile_master,'短信余额数量为'.$x['RemainPoint'].',请及时充值', 1);
            }
            
            return $result;
        } else {
            return array('code' => -1, 'message' => $x['Message']);
        }
    }
    public function sendsmsAlbum($mobile, $content, $sign_master = 0) {
        if($sign_master){//使用平台签名
            if($this->jd_sign_name_master){
                $content = $content.'【'.$this->jd_sign_name_master.'】';
            }
        }else{
            if($this->jd_sign_name){
                $content = $content.'【'.$this->jd_sign_name.'】';
            }
        }
        \HttpClient::quickPost($this->url, array(
            'action' => 'send',
            'rt' => 'json',
            'userid' => $this->userid,
            'timestamp' => $this->timestamp,
            'sign' => $this->sign,
            'mobile' => $mobile,
            'content' => $content,
            'sendtime' => '',
            'extno' => '',
        ));
    }

    public function getSign() {
        return md5($this->username . $this->password . $this->timestamp);
    }

}
