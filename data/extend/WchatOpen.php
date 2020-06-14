<?php

namespace data\extend;

use data\extend\wchat\WxBizDataCrypt;
use data\service\Config as ConfigSer;
use think\Cache as cache;
use data\extend\wchat\WxBizMsgCrypt as WxBizMsgCrypt;
use data\service\Weixin;
use think\Config;
use think\Request;
use think\Session;
use addons\miniprogram\service\MiniProgram as miniProgramService;
use addons\miniprogram\model\WeixinAuthModel;

class WchatOpen
{
    public $ticket;//推送component_verify_ticket，开放平台设置后每隔10分钟发一次，但要解密
    public $appId;//开放平台appid，在开放平台中
    public $appsecret;//开放平台app密码，在开放平台中
    public $tk;//通过开放平台设置的开放平台的token
    public $encodingAesKey;//开放平台的加密解密秘钥,在开放平台中
    public $component_token;//通过ticket等获取到的开放平台的access_token component_access_token
    public $pre_auth_code;//预授权码
    public $author_appid;//获取的公众平台的appid
    public $token = '';//获取公众账号的token
    public $weixin_service;
    public $temp_appid;//微信返回的appid
    public $website_id;

    function __construct($website_id = 1, $temp_appid='')
    {
        if ($temp_appid) {
            $this->temp_appid = $temp_appid;
        }
        $this->website_id = $website_id;
        $this->weixin_service = new Weixin();
        $this->init();
    }

    /**
     * 微信开放平台初始化
     */
    private function init()
    {
        // 初始的微信开放平台配置
        $session_config = Session::get($this->website_id.'wechat_config');
        if ($session_config && $session_config != 'null') {
            $config = json_decode($session_config, true);
        } else {
            //后台config.php配置
        $wchat_config_key = 'shop';
        if (strpos(Request::instance()->host(),'sp1') !== false){
            $wchat_config_key = 'sp1';
        } else if(strpos(Request::instance()->host(),'sp2') !== false){
            $wchat_config_key = 'sp2';
        }
        $we_config = Config::get('weChat.' . $wchat_config_key);
        if ($we_config) {
                $config = [
                    'app_id' => $we_config['open']['app_id'],
                    'app_secret' => $we_config['open']['app_secret'],
                    'key' => $we_config['open']['key'],
                    'token' => $we_config['open']['token'],
                ];
        } else {
            //源码数据库config配置
            $config = new ConfigSer();
                $wechat_config = [];
            if($this->temp_appid){
                $wechat_config = $config->getWechatOpen($this->temp_appid, 0);
            }else if($this->website_id){
                $config = new ConfigSer();
                $wechat_config = $config->getWechatOpenByWebsiteId($this->website_id);
            }
            if (!$wechat_config) {return;}
            $config = [
                'app_id' => $wechat_config['open_appid'],
                'app_secret' => $wechat_config['open_secrect'],
                'key' => $wechat_config['open_key'],
                'token' => $wechat_config['open_token'],
            ];
        }
            $temp_config = $config;
            if (!$config) {return;}
            Session::set($this->website_id.'wechat_config', json_encode($temp_config));
        }

        // 属性赋值
        $this->appId = $config['app_id'];
        $this->appsecret = $config['app_secret'];
        $this->encodingAesKey = $config['key'];
        $this->tk = $config['token'];
        $this->ticket = @\file_get_contents('public/ticket.txt');
        //获取第三方token
        if (cache::get('component_access_token') == false) {
            $this->getCommonAccessToken();
        }
        $this->component_token = cache::get('component_access_token');
        //获取预授权码
        if (cache::get('pre_auth_code') == false) {
            $this->getPreAuthCode();
        }
        // \file_put_contents("ss.txt", $code_arr);
        $this->pre_auth_code = cache::get('pre_auth_code');
        //获取微信支付接口
        //小程序authorize_access_token
        if (cache::get($this->website_id.'_authorize_access_token') == false) {
            $this->get_access_token($this->website_id);
        }elseif(cache::get($this->website_id.'_authorize_access_token')['time'] < time()) {
            $this->get_access_token($this->website_id);
        }
    }

    /**
     * 获取第三方token,需要第三方的appid，密码，ticket     $component_token
     */
    private function getCommonAccessToken()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_component_token";
        $data = array('component_appid' => $this->appId, 'component_appsecret' => $this->appsecret, 'component_verify_ticket' => $this->ticket);
        $AjaxReturn = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        if (!empty($AjaxReturn->component_access_token)) {
            cache::set('component_access_token', $AjaxReturn->component_access_token, 7100);
        }
    }

    /**
     * 获取第三方平台的预授权码    需要第三方token，以及第三方appid
     */
    private function getPreAuthCode()
    {

        $url = "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=" . $this->component_token;
        $data = ['component_appid' => $this->appId];
        $AjaxReturn = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        if (!empty($AjaxReturn->pre_auth_code)) {
            cache::set('pre_auth_code', $AjaxReturn->pre_auth_code, 500);
        }
    }

    /**
     * 通过此方法入口获取ComponentVerifyTicket
     * 微信授权使用
     */
    public function getComponentVerifyTicket($from_xml)
    {
        $msg = '';
        $error_code = $this->decryptMsg($from_xml, $msg);
        //$postObj = \simplexml_load_string($ticket_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($error_code == 0) {
            $xml = new \DOMDocument();
            $xml->loadXML($msg);
            $array_e = $xml->getElementsByTagName('ComponentVerifyTicket');
            $component_verify_ticket = $array_e->item(0)->nodeValue;
            \file_put_contents('public/ticket.txt', $component_verify_ticket);
            echo "success";
            exit;
        }
//        switch ($postObj->InfoType) {
//            case "component_verify_ticket":
//                //cache::set('ComponentVerifyTicket',$postObj->ComponentVerifyTicket,5400);
//                \file_put_contents('data/extend/ticket.txt', $postObj->ComponentVerifyTicket);
//                echo "success";
//                break;
//            case "unauthorized":
//                //当用户取消授权的时候，微信服务器也会向这个页面发送信息
//                break;
//            default:
//                break;
//        }
    }
    public function getMessage($from_xml)
    {
        $msg = '';
        $error_code = $this->decryptMsg($from_xml, $msg);
        if ($error_code == 0){
            $xml = new \DOMDocument();
            $xml->loadXML($msg);
            $array_e = $xml->getElementsByTagName('Event');
            $audit_result = $array_e->item(0)->nodeValue;
            if (in_array($audit_result, ['weapp_audit_fail', 'weapp_audit_success', 'weapp_audit_delay'])) {
                // 小程序审核结果
                $mp_service = new miniProgramService();
                $mp_service->getListAuditResult();
            }

            // 全网发布
            $postObj = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
            $msg_type = $postObj->MsgType;
            $to_username = trim($postObj->ToUserName);
            $xml_content = trim($postObj->Content);
            $exp_result = explode('QUERY_AUTH_CODE:', $xml_content);
            if (count($exp_result) > 1) {
                $content = $exp_result[1];
            } else {
                $content = $xml_content;
            }
            if ($to_username == 'gh_3c884a361561' && $msg_type == 'text') { // 公众号
                if ($content == 'TESTCOMPONENT_MSG_TYPE_TEXT') {
                    // 回复文本
                    $return_content = 'TESTCOMPONENT_MSG_TYPE_TEXT_callback';
                    echo $this->responseOfText($postObj, $return_content);
                    exit;
                } else {
                    // 调用客户api返回消息
                    $info = $this ->get_query_auth($content);
                    $authorizer_access_token = objToArr($info)['authorization_info']['authorizer_access_token'];
                    $return_content = $content . '_from_api';
                    $this->sendServiceText($postObj, $return_content, $authorizer_access_token);
                }
            }
            if ($to_username == 'gh_8dad206e9538' && $msg_type == 'text') { //小程序
                // 调用客户api返回消息
                $info = $this ->get_query_auth($content);
                $authorizer_access_token = objToArr($info)['authorization_info']['authorizer_access_token'];
                $return_content = $content . '_from_api';
                $this->sendServiceText($postObj, $return_content, trim($authorizer_access_token));
            }
        }
    }

    /**
     * 微信开放平台数据加密
     * @param unknown $msg_sign
     * @param unknown $timeStamp
     * @param unknown $nonce
     * @param unknown $from_xml
     * @return string
     */
    public function encryptMsg($from_xml, &$msg)
    {
        // 第三方发送消息给公众平台
        $encodingAesKey = $this->encodingAesKey;
        $token = $this->tk;
        $appId = $this->appId;
        $pc = new WxBizMsgCrypt($token, $encodingAesKey, $appId);
        // 第三方收到公众号平台发送的消息
        $timeStamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $errCode = $pc->encryptMsg($from_xml, $timeStamp, $nonce, $msg);
        return $errCode;
    }
    
    /**
     * 消息解密
     * @param unknown $from_xml
     * @return string
     */
    public function decryptMsg($from_xml, &$msg)
    {
        // 第三方发送消息给公众平台
        $encodingAesKey = $this->encodingAesKey;
        $token = $this->tk;
        $appId = $this->appId;
        $pc = new WxBizMsgCrypt($token, $encodingAesKey, $appId);
        // 第三方收到公众号平台发送的消息
        $timeStamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $msg_signature = $_GET['msg_signature'];
        $errCode = $pc->decryptMsg($msg_signature, $timeStamp, $nonce, $from_xml, $msg);
        return $errCode;
    }

    /**
     * 授权入口，注意授权之后的auth_code要存入库中 （用预授权码以及开放平台的appid，授权成功后会给回调网址发送授权的auth_code，用于获取授权公众号基本信息）
     */
    public function auth($url)
    {
        $redurl = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=' . $this->appId . '&pre_auth_code=' . $this->pre_auth_code . '&redirect_uri=' . $url . '&auth_type=2';
        return $redurl;

    }

    /**
     * 使用授权码换取公众号的接口调用凭据和授权信息,得到之后要存入数据库中，尤其是authorizer_appid,authorizer_refresh_token  只提供一次
     */
    public function get_query_auth($author_code = '')
    {
        //此页面可以是授权的回调地址通过get方法获取到authorization_code
        if (empty($author_code)) {
            $author_code = Session::get('auth_code');
        }
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=" . $this->component_token;
        $data = ['component_appid' => $this->appId, 'authorization_code' => $author_code];
        $AjaxReturn = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $AjaxReturn;
    }


    /**
     * 定时更新access_token- 通过上述方法获取的公众号access_token可能会过期，因此需要定时获取access_token
     */
    public function get_access_token_crontab($appid, $authorizer_refresh_token = '')
    {
        //获取公众号token
        if (empty($authorizer_refresh_token)) {
            $info = $this->weixin_service->getWeixinInfoByAppid($appid);
            $authorizer_refresh_token = $info['authorizer_refresh_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=" . $this->component_token;
        $data = ['component_appid' => $this->appId, 'authorizer_appid' => $appid, 'authorizer_refresh_token' => $authorizer_refresh_token];
        $AjaxReturn = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        if (!empty($AjaxReturn->authorizer_access_token)) {
            cache::set($this->website_id.'_authorize_access_token', time() + 7198, 7198);
            return ['authorizer_access_token' => $AjaxReturn->authorizer_access_token, 'authorizer_refresh_token' => $AjaxReturn->authorizer_refresh_token];
        } else {
            return '';
        }
    }
    /**
     * 加载判断更新access_token - 通过上述方法获取的公众号access_token可能会过期，因此需要定时获取access_token
     * 为了防止代码执行期间正好token判断7200s过期，所以缓存到期时间提前2s
     */
    public function get_access_token()
    {
        // 1、下查询数据库
        $weixin_auth_model = new WeixinAuthModel();
        $weixin_auth_info = $weixin_auth_model->getInfo(['website_id' => $this->website_id],'update_token_time,authorizer_appid,authorizer_refresh_token');

        //过期更新token插入数据库并返回
        if ($weixin_auth_info) {
            //更新 - 获取/刷新接口调用令牌
            $url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=" . $this->component_token;
            $data = ['component_appid' => $this->appId, 'authorizer_appid' => $weixin_auth_info['authorizer_appid'], 'authorizer_refresh_token' => $weixin_auth_info['authorizer_refresh_token']];
            $AjaxReturn = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
            //更新 - 插入weixin_auth表
            if (!empty($AjaxReturn->authorizer_access_token)) {
                $weixin_auth_model->save(
                    [
                        'authorizer_refresh_token' => $AjaxReturn->authorizer_refresh_token,
                        'authorizer_access_token' => $AjaxReturn->authorizer_access_token,
                        'update_token_time' => time()
                    ], [
                        'website_id' => $this->website_id
                    ]
                );

                cache::set($this->website_id.'_authorize_access_token', [
                    'authorizer_access_token' => $AjaxReturn->authorizer_access_token,
                    'time' => time() + 7198
                ], time() + 7198);
            }
        }
    }
    /**
     * 该API用于获取授权方的公众号基本信息，包括头像、昵称、帐号类型、认证类型、微信号、原始ID和二维码图片URL
     */
    public function get_authorizer_info($appid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=" . $this->component_token;
        $data = ['component_appid' => $this->appId, 'authorizer_appid' => $appid];
        $AjaxReturn = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $AjaxReturn;
    }

    /**
     * 该API用于获取授权方的公众号基本信息，包括头像、昵称、帐号类型、认证类型、微信号、原始ID和二维码图片URL
     */
    public function get_mp_base_info($authorizer_access_token)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/account/getaccountbasicinfo?access_token=" . $authorizer_access_token;
        $AjaxReturn = $this->get_url_return($url);
        return $AjaxReturn;
    }

    /**
     * 设置授权方的选项信息应用较少，在公众号设置就可以
     * 该API用于设置授权方的公众号的选项信息，如：地理位置上报，语音识别开关，多客服开关。注意，设置各项选项设置信息，需要有授权方的授权，详见权限集说明
     */
    public function set_authorizer_option()
    {
        //调用网址 https://api.weixin.qq.com/cgi-bin/component/ api_set_authorizer_option?component_access_token=xxxx
        //post数据:
        /*
         {
         "component_appid":"appid_value",
         "authorizer_appid": " auth_appid_value ",
         "option_name": "option_name_value",
         "option_value":"option_value_value"
         }*/
    }

    /**
     * 对象转化为数组
     * @param unknown $array
     * @return array
     */
    public function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    /**
     * 开放平台代替公众号实现获取前台会员会话
     * @param unknown $appid
     * @param string $code
     * @return mixed
     */
    public function get_access_token_member($appid, $code = '')
    {
        $token_url = "https://api.weixin.qq.com/sns/oauth2/component/access_token?appid={$appid}&code={$code}&grant_type=authorization_code&component_appid={$this->appId}&component_access_token=" . $this->component_token;
        $result = $this->get_url_return($token_url);
        return $result;
    }

    /**
     * 微信数据获取
     * @param string $url
     * @param string $data
     *
     * @return mixed
     */
    private function get_url_return($url, $data = '', $flag = false)
    {
        $curl = curl_init();  //创建一个新url资源
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data))); //设置header
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $AjaxReturn = curl_exec($curl);
        if ($flag) {
            return $AjaxReturn;
        }

        //curl_close();
//        $strjson = json_decode($AjaxReturn);
//        if (!empty($strjson->errcode)) {
//            switch ($strjson->errcode) {
////                case 40001:
////                    return $this->get_url_return($url, $data); //获取access_token时AppSecret错误，或者access_token无效
////                    break;
////                case 40014:
////                    return $this->get_url_return($url, $data); //不合法的access_token
////                    break;
//                case 42001:
//                    return $this->get_url_return($url, $data); //access_token超时
//                    break;
//                case 45009:
//                    return "接口调用超过限制：" . $strjson->errmsg;
//                    break;
//                case 41001:
//                    return "缺少access_token参数：" . $strjson->errmsg;
//                    break;
//                default:
//                    return $strjson->errmsg; //其他错误，抛出
//                    break;
//            }
//        } else {
//            return $AjaxReturn;
//        }
        return json_decode($AjaxReturn);
    }

    /**
     * 获取微信OAuth2授权链接snsapi_base,snsapi_userinfo
     * @param string $redirect_uri 跳转地址
     * @param mixed $state 参数
     * 不弹出授权页面，直接跳转，只能获取用户openid
     */
    public function get_authorize_url_info($appid, $redirect_uri = '', $state = '')
    {
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base,snsapi_userinfo&state={$state}&component_appid={$this->appId}#wechat_redirect";
    }

    /**
     * 获取微信OAuth2授权链接snsapi_base
     * @param string $redirect_uri 跳转地址
     * @param mixed $state 参数
     * 不弹出授权页面，直接跳转，只能获取用户openid
     */
    public function get_authorize_url_base($appid, $redirect_uri = '', $state = '')
    {
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state={$state}&component_appid={$this->appId}#wechat_redirect";
    }

    /**
     * 提交小程序代码
     * @param string $authorizer_access_token 请使用第三方平台获取到的该小程序授权
     * @param array $data
     *
     * @return string $token_arr
     */
    public function commitMpCode($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/wxa/commit?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 绑定小程序体验者
     * @param string $authorizer_access_token
     * @params array $data
     *
     * @return mixed
     */
    public function bindTester($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/wxa/bind_tester?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 小程序体验者列表
     * @param $authorizer_access_token
     *
     * @return mixed
     */
    public function testerList($authorizer_access_token)
    {
        $data['action'] = 'get_experiencer';
        $url = 'https://api.weixin.qq.com/wxa/memberauth?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 解除体验者
     * @params string $authorizer_access_token
     * @param array $data
     *
     * @return mixed
     */
    public function unBindTester($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/wxa/unbind_tester?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 获取小程序体验码
     * @param string $authorizer_access_token
     *
     * @return mixed
     */
    public function getQrCode($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxa/get_qrcode?access_token=' . $authorizer_access_token;
        $result = $this->get_url_return($url, '', true);
        return $result;
    }

    /**
     * 获取授权小程序帐号的可选类目
     * @param string $authorizer_access_token
     *
     * @return mixed
     */
    public function getMpCategory($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxa/get_category?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url);
        return $ajax_return;
    }

    /**
     * 获取小程序的第三方提交代码的页面配置
     * @param $authorizer_access_token
     *
     * @return mixed
     */
    public function getMpPage($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxa/get_page?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url);
        return $ajax_return;
    }

    /**
     * 将第三方提交的代码包提交审核
     * @param string $authorizer_access_token
     * @param array $data
     *
     * @return mixed
     */
    public function submitAudit($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxa/submit_audit?access_token=' . $authorizer_access_token;
//        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        $ajax_return = $this->get_url_return($url, "{}");
        return $ajax_return;
    }

    /**
     * 发布已通过审核的小程序,这里有坑，只能传“{ }”字符串
     * @param $authorizer_access_token
     * @param $data
     * @return mixed
     */
    public function release($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxa/release?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, "{}");
        return $ajax_return;
    }

    /**
     * 查询某个指定版本的审核状态
     * @param string $authorizer_access_token
     * @param array $data
     *
     * @return
     */
    public function getAuditStatus($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/wxa/get_auditstatus?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 获取草稿箱内的所有临时代码草稿
     * @param string $authorizer_access_token
     *
     * @return mixed
     */
    public function draftList($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxa/gettemplatedraftlist?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url);
        return $ajax_return;
    }

    /**
     * 代码模版库中的所有小程序代码模版
     * @param string $authorizer_access_token
     *
     * @return mixed $ajax_return
     */
    public function templateList($component_token = '')
    {
        $component_token = !empty($component_token) ?: $this->component_token;
        $url = 'https://api.weixin.qq.com/wxa/gettemplatelist?access_token=' . $component_token;
        $ajax_return = $this->get_url_return($url);
        return $ajax_return;
    }

    /**
     * 将草稿箱的草稿选为小程序代码模版
     * @param string $authorizer_access_token
     * @param array $data
     *
     * @return mixed $ajax_return
     */
    public function addToTemplate($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/wxa/addtotemplate?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 删除指定小程序代码模版
     * @param string $authorizer_access_token
     * @param array $data
     *
     * @return mixed
     */
    public function deleteTemplate($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/wxa/deletetemplate?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 获取模板库某个模板标题下关键词库
     * @param string $authorizer_access_token
     * @param array $data
     *
     * @return mixed
     */
    public function templateLibrary($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/library/get?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 添加消息模板到小程序个人模板库
     * @param $authorizer_access_token
     * @param $data
     * @return mixed
     */
    public function addMessageTemplate($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/add?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 小程序个人模板库删除消息模板
     * @param $authorizer_access_token
     * @param $data
     * @return mixed
     */
    public function deleteMessageTemplate($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/del?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    public function modifyDomain($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/wxa/modify_domain?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /**
     * 获取小程序码（太阳码）
     * @param string $access_token
     * @param array $data 参数
     * @type int | 1:A接口（有限个） 其他：B接口（无限个）
     * @return mixed
     */
    public function getSunCodeApi($access_token, array $data=[], $type = 1)
    {
  
        $sun_url = '';
        switch ($type) {
            case  1:
                $sun_url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$access_token;
                break;
            case  2:
                $sun_url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
                break;
        }
        $result = $this->get_url_return($sun_url, json_encode($data, JSON_UNESCAPED_UNICODE), true);
        return $result;
    }

    /**
     * 查询最新一次提交的审核状态
     * @param string $authorizer_access_token
     *
     * @return mixed
     */
    public function getLasAudistatus($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxa/get_latest_auditstatus?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url);
        return $ajax_return;
    }


    /**
     * 自动回复文本
     * @param string $object
     * @param string $content [返回内容]
     * @return string [xml字串]
     */
    public function responseOfText($object = '', $content = '')
    {
        if (!isset($content) || empty($content)){
            return "";
        }
        $xmlTpl =   "<xml>
                     <ToUserName><![CDATA[%s]]></ToUserName>
                     <FromUserName><![CDATA[%s]]></FromUserName>
                     <CreateTime>%s</CreateTime>
                     <MsgType><![CDATA[text]]></MsgType>
                     <Content><![CDATA[%s]]></Content>
                     </xml>";
        $from_xml = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        $this->encryptMsg($from_xml, $encryptMsg);
        return $encryptMsg;
    }

    /**
     * 发送文本消息
     * @param string $object
     * @param string $content [返回内容]
     * @param string $access_token [authorizer_access_token]
     */
    public function sendServiceText($object = '', $content = '', $authorizer_access_token = '')
    {
        /* 获得openId值 */
        $openid = (string)$object->FromUserName;
        $post_data = array(
            'touser'    => $openid,
            'msgtype'   => 'text',
            'text'      => array(
                'content'   => $content
            )
        );
        $this->sendMessages($post_data, $authorizer_access_token);
    }

    /**
     * 客服接口-发消息
     * @param $data array
     * @param $authorizer_access_token string
     */
    public function sendMessages($data = array(), $authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $authorizer_access_token;
        $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 发送模板消息
     * @param $authorizer_access_token [小程序接口调用凭据]
     * @param $data
     * @return mixed
     */
    public function templateMessageSend($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }

    /***
     * 获取帐号下已存在的消息模板列表
     * @param $authorizer_access_token
     * @param $data
     * @return mixed
     */
    public function getTemplateList($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/list?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }
    /***
     * 小程序审核撤回:单个帐号每天审核撤回次数最多不超过1次，一个月不超过10次。
     * @param $authorizer_access_token
     * @param $data
     * @return mixed
     */
    public function recallcommitMp($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxa/undocodeaudit?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url);
        return $ajax_return;
    }
    /**
     * 获取当前帐号下的个人模板列表
     * Licensed ( https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/subscribe-message/subscribeMessage.getTemplateList.html)
     * TYPE: GET
     * @param $authorizer_access_token string [小程序token]
     * @return mixed]
     */
    public function getTemplateListOfSub($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/gettemplate?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url);
        return $ajax_return;
    }
    /**
     * 组合模板并添加至帐号下的个人模板库
     * Licensed ( https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/subscribe-message/subscribeMessage.addTemplate.html)
     * TYPE: POST
     * @param $authorizer_access_token string [小程序token]
     * @param $data array [请求参数]
     * @return mixed]
     */
    public function addMessageTemplateOfSub($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/addtemplate?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data,JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }
    /**
     * 删除帐号下的个人模板
     * Licensed ( https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/subscribe-message/subscribeMessage.deleteTemplate.html)
     * TYPE: POST
     * @param $authorizer_access_token string [小程序token]
     * @param $data array [请求参数]
     * @return mixed]
     */
    public function deleteMessageTemplateOfSub($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/deltemplate?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data,JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }
    /**
     * 发送订阅消息
     * Licensed ( https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/subscribe-message/subscribeMessage.send.html)
     * TYPE: POST
     * @param $authorizer_access_token string [小程序token]
     * @param $data array [请求参数]
     * @return mixed]
     */
    public function sendMpMessageOfSubscribe($authorizer_access_token, $data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url, json_encode($data,JSON_UNESCAPED_UNICODE));
        return $ajax_return;
    }
    /**
     * 登录凭证校验
     * @param $code 登录时获取的 code
     * @return unknown|string
     * @throws think\Exception\DbException
     */
    public function code_to_session($code)
    {
        $auth = new WeixinAuthModel();
        $wx_auth = $auth->getInfo(['website_id' => $this->website_id],'authorizer_appid, authorizer_secret');
        $app_id = $wx_auth['authorizer_appid'];
        $secret = $wx_auth['authorizer_secret'];
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $app_id . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';
        $ajax_return = $this->get_url_return($url);
        return $ajax_return;
    }
    /**
     * 小程序获取unionid
     * @param $session_key
     * @param $encryptedData
     * @param $iv
     * @throws think\Exception\DbException
     */
    public function getMpUnionId($session_key, $encryptedData, $iv)
    {
        $auth = new WeixinAuthModel();
        $wx_authorizer = $auth->getInfo(['website_id' => $this->website_id],'authorizer_appid');
        if (empty($wx_authorizer)) {
            return ['result' => false, 'message' => '授权失败'];
        }
        $app_id = $wx_authorizer['authorizer_appid'];
        $wechat = new WxBizDataCrypt($app_id, $session_key);
        $errCode = $wechat->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            return ['result' => true, 'data' => $data];
        } else {
            return ['result' => false, 'message' => $errCode];
        }
    }

    /**
     * # https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/code/query_quota.html
     * 查询服务商的当月提审限额（quota）和加急次数
     * @param $authorizer_access_token
     * @return mixed
     */
    public function queryQuota($authorizer_access_token)
    {
        $url = 'https://api.weixin.qq.com/wxa/queryquota?access_token=' . $authorizer_access_token;
        $ajax_return = $this->get_url_return($url);
        return $ajax_return;
    }
}
