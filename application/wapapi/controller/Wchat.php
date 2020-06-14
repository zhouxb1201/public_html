<?php

namespace app\wapapi\controller;

use addons\distribution\service\Distributor;
use addons\invoice\controller\Invoice;
use addons\taskcenter\model\VslGeneralPosterModel;
use data\extend\wchat\WxBizMsgCrypt;
use data\model\UserModel;
use data\model\UserTaskModel;
use data\service\User;
use think\Controller;

\think\Loader::addNamespace('data', 'data/');

use data\extend\WchatOauth;
use data\service\Weixin;
use data\service\WebSite;
use data\service\Config;
use data\service\BaseService as BaseService;
use think\Db;
use think\Exception;
use \think\Session as Session;
use addons\followgift\server\FollowGift as FollowGiftServer;

class Wchat extends Controller
{

    public $wchat;

    public $weixin_service;

    public $author_appid;

    public $website_id;

    public $style;

    public $instance_id;

    public function __construct()
    {
        parent::__construct();
        $base = new BaseService();
        $model = $base->getRequestModel();
        $website_id = checkUrl();
        if ($website_id) {
            Session::set($model . 'website_id', $website_id);
            $this->website_id = $website_id;
        } elseif (Session::get($model . 'website_id')) {
            $this->website_id = Session::get($model . 'website_id');
        } else {
            return 0;
        }
        $this->instance_id = 0;
        $this->wchat = new WchatOauth($this->website_id); // 微信公众号相关类
        $this->weixin_service = new Weixin();
        // 使用那个手机模板
        $config = new Config();
        $use_wap_template = $config->getUseWapTemplate(0);
        if (empty($use_wap_template)) {
            $use_wap_template['value'] = 'default';
        }
        $this->style = "wap/" . $use_wap_template['value'] . "/";
        $this->assign("style", "wap/" . $use_wap_template['value']);
        $this->getMessage();
        $this->web_site = new WebSite();
        $web_info = $this->web_site->getWebSiteInfo();
        if ($web_info['wap_status'] == 2) {
            $this->error($web_info['close_reason']);
//            webClose($web_info['close_reason'],$web_info['logo']);
        }
    }

    /**
     * ************************************************************************微信公众号消息相关方法 开始******************************************************
     */
    /**
     * 关联公众号微信
     */
    public function relateWeixin()
    {
        $sign = request()->get('signature', '');
        if (isset($sign)) {
            $signature = $sign;
            $timestamp = request()->get('timestamp', '');
            $nonce = request()->get('nonce', '');
            $config = new Config();
            $wchat_config = $config->getInstanceWchatConfig(0, $this->website_id);
            $token = $wchat_config['value']['token'];
            $tmpArr = array(
                $token,
                $timestamp,
                $nonce
            );
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);
            if ($tmpStr == $signature) {
                $echostr = request()->get('echostr', '');
                if (!empty($echostr)) {
                    echo $echostr;exit;
                }
            }
        }
    }

    public function templateMessage()
    {
        $media_id = request()->get('media_id', 0);
        $weixin = new Weixin();
        $info = $weixin->getWeixinMediaDetailByMediaId($media_id);
        if (!empty($info["media_parent"])) {
            $this->assign("info", $info);
            return view($this->style . 'Wchat/templateMessage');
        } else {
            echo "图文消息没有查询到";
        }
    }

    /**
     * 微信开放平台模式(需要对消息进行加密和解密)
     * 微信获取消息以及返回接口
     */
    public function getMessage()
    {
        $weixin = new Weixin();
        $from_xml = file_get_contents('php://input');
        if (empty($from_xml)) {
            return;
        }
        if(strstr($from_xml, 'Encrypt')){
            $ticket_xml = '';
            $this->decryptMsg($from_xml,  $ticket_xml);
        }else{
            $ticket_xml = $from_xml;
        }
        $postObj = simplexml_load_string($ticket_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!empty($postObj->MsgType)) {
            switch ($postObj->MsgType) {
                case "text":
                    //                    $sql = "insert into `sys_log` (`content`) VALUES ('$postObj->Content')";
                    //                    Db::query($sql);
                    // 用户发的消息 存入表中
                    $weixin->addUserMessage((string)$postObj->FromUserName, (string)$postObj->Content, (string)$postObj->MsgType, $this->website_id);
                    $resultStr = $this->MsgTypeText($postObj, $this->website_id);
                    break;
                case "event":
                    $resultStr = $this->MsgTypeEvent($postObj);
                    break;
                default:
                    $resultStr = "";
                    break;
            }
        } else {
            return;
        }

        if (!empty($resultStr)) {
            echo $resultStr;
        } else {
            echo '';
        }
        exit();
    }
    /**
     * 消息解密
     * @param unknown $from_xml
     * @return string
     */
    public function decryptMsg($from_xml, &$msg)
    {
        // 获取公众平台的信息
        $config = new Config();
        $wchat_config = $config->getInstanceWchatConfig(0, $this->website_id);
        $encodingAesKey = $wchat_config['value']['encodingAESKey'];//
        $token =$wchat_config['value']['token'];
        $appId = $wchat_config['value']['appid'];
        $pc = new WxBizMsgCrypt($token, $encodingAesKey, $appId);
        // 第三方收到公众号平台发送的消息
        $timeStamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $msg_signature = $_GET['msg_signature'];
        $errCode = $pc->decryptMsg($msg_signature, $timeStamp, $nonce, $from_xml, $msg);
        return $errCode;
    }
    /**
     * 文本消息回复格式
     *
     * @param unknown $postObj
     * @return Ambigous <void, string>
     */
    private function MsgTypeText($postObj, $website_id)
    {
        $wchat_replay = $this->weixin_service->getWhatReplay(0, (string)$postObj->Content);
        // 判断用户输入text
        if (!empty($wchat_replay) && empty($wchat_replay['replay_type'])) { // 关键词匹配回复
            $contentStr = $wchat_replay; // 构造media数据并返回
        } else {
            //从关键词查询数据库，构造海报数据
            $poster_data = $this->weixin_service->getPosterData((string)$postObj->Content, $website_id);
            if($poster_data){
                $this->weixin_service->getPosterSend($poster_data, (string)$postObj->Content, $postObj->FromUserName);
            }else{
                //默认回复
                $wchat_replay = $this->weixin_service->getDefaultReplay(0);
                if (!empty($wchat_replay)) {
                    $contentStr = $wchat_replay;
                } else {
                    $contentStr = '';
                }
            }
        }
        if (is_array($contentStr)) {
            unset($contentStr['type']);
            if ($wchat_replay['type'] == 'text') {
                $resultStr = $this->wchat->event_key_text($postObj, $contentStr);
            }
            if ($wchat_replay['type'] == 'image') {
                $resultStr = $this->wchat->event_key_image($postObj, $contentStr);
            }
            if ($wchat_replay['type'] == 'voice') {
                $resultStr = $this->wchat->event_key_voice($postObj, $contentStr);
            }
            if ($wchat_replay['type'] == 'video') {
                $resultStr = $this->wchat->event_key_video($postObj, $contentStr);
            }
            if ($wchat_replay['type'] == 'news') {
                $resultStr = $this->wchat->event_key_news($postObj, $contentStr);
            }
        } elseif (!empty($contentStr)) {
            $resultStr = $this->wchat->event_key_text($postObj, $contentStr);
        } else {
            $resultStr = '';
        }
        return $resultStr;
    }
    /*
     * 清除海报缓存，并删掉微信素材中的图片
     * **/
    public function deletePoster()
    {
        $this->weixin_service->deletePoster();
        return ajaxReturn(1);
    }
    /**
     * 事件消息回复机制
     */
    // 事件自动回复 MsgType = Event
    private function MsgTypeEvent($postObj)
    {
        $contentStr = "";
        switch ($postObj->Event) {
            case "subscribe": // 关注公众号
                // 添加关注回复
                $content = $this->weixin_service->getSubscribeReplay($this->website_id);
                if (!empty($content)) {
                    $contentStr = $content;
                }
                $info = $this->wchat->get_fans_info($postObj->FromUserName, $this->website_id);
                $openid = $info['openid'];
                //关注有礼
                if(getAddons('followgift', $this->website_id)){
                    $followgift_server = new FollowGiftServer();
                    $followgift_server->createFollowGiftRecord($openid);
                }
                $data = [];
                $data['website_id'] = $this->website_id;
                $data['instance_id'] = $this->instance_id;
                $data['nickname'] = filterStr($info['nickname']);
                $data['nickname_decode'] = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $info['nickname']);
                $data['headimgurl'] = $info['headimgurl'];
                $data['sex'] = $info['sex'];
                $data['language'] = $info['language'];
                $data['country'] =$info['country'];
                $data['province'] = filterStr($info['province']);
                $data['city'] = filterStr($info['city']);
                $data['openid'] = $info['openid'];
                $data['groupid'] = $info['groupid'];
                $data['unionid'] = $info['unionid'];
                $data['is_subscribe'] = $info['subscribe'];
                $data['memo'] = $info['remark'];
                $data['subscribe_date'] = $info['subscribe_time'];
                $data['update_date'] = time();
                if (preg_match("/^qrscene_/", $postObj->EventKey)) {
                    $scene_str = substr($postObj->EventKey, 8);
                    if (is_numeric($scene_str)){
                        // QR_LIMIT_SCENE 整型scene 场景
                        $data['source_uid'] = $scene_str;

                    } else {
                        // QR_LIMIT_STR_SCENE 自定义scene 场景
                        $scene_str = stripslashes($scene_str);
                        $scene_array = json_decode($scene_str,true);
                        $data['source_uid'] = isset($scene_array['uid']) ? $scene_array['uid'] : 0;
                        $data['scene'] = isset($scene_array['poster_type']) ? $scene_array['poster_type'] : '';
                        $data['scene_id'] = isset($scene_array['poster_id']) ? $scene_array['poster_id'] : 0;
                        if($data['scene'] == 'task_poster'){//海报任务的情况，扫码后判断上一级用户领取的任务是否过期，过期不绑定关系
                            $general_poster = new VslGeneralPosterModel();
                            $gp_cond['general_poster_id'] = $data['scene_id'];
                            $gp_cond['start_task_time'] = ['<=', time()];
                            $gp_cond['end_task_time'] = ['>=', time()];
                            $is_general_info = $general_poster->getInfo($gp_cond);
//                            $content[0]['Content'] = '该海报任务已过期';
                            $weixin = new Weixin();
                            if($is_general_info){//任务是否过期
                                if($data['source_uid'] && $data['scene_id']){
                                    //判断当前用户领取的海报任务是否过期
                                    $poster_cond['uid'] = $data['source_uid'];
                                    $poster_cond['general_poster_id'] = $data['scene_id'];
                                    $poster_cond['get_time'] = ['<=', time()];
                                    $poster_cond['need_complete_time'] = ['>=', time()];
                                    $user_task = new UserTaskModel();
                                    $is_user_task = $user_task->getInfo($poster_cond);
                                    if(!$is_user_task){//如果扫描的该任务已过期、失效
                                        $data['source_uid'] = 0;
                                        $data['scene'] = '';
                                        $data['scene_id'] = 0;
                                        $media_id = '海报任务已失效';
                                        $weixin->sendFanMessage($postObj->FromUserName, $media_id, $this->website_id);
//                                        $this->wchat->event_key_text($postObj, $content);
                                    }
                                }
                            }else{
                                $data['source_uid'] = 0;
                                $data['scene'] = '';
                                $data['scene_id'] = 0;
                                $media_id = '海报任务已失效';
                                $weixin->sendFanMessage($postObj->FromUserName, $media_id, $this->website_id);
//                                $this->wchat->event_key_text($postObj, $content);
                            }
                        }
                    }
                }
                $this->weixin_service->addWeixinFans($data); // 关注
                // 构造media数据并返回 */
                //关注就注册用户信息
                $member = new \data\service\Member();
                $distributor = new Distributor();
                $user_mdl = new UserModel();
                $extend_code = $distributor->create_extend();
                //先判断是否有用户信息
                $user_cond['website_id'] = $this->website_id;
                if($info['openid']){
                    $user_cond['wx_openid'] = $info['openid'];
                }else{
                    $user_cond['wx_unionid'] = $info['unionid'];
                }
                $user_info = $user_mdl->getInfo($user_cond);
                if(!$user_info){
                    $retval = $member->registerMember($extend_code, '', '', '', '', '', '', $info['openid'], json_encode($info), $info['unionid']);
                    if ($retval > 1) {
                        $user_service = new User();
                        $user_service->updateUserNew([
                            'user_headimg' => $info['headimgurl'],
                            'nick_name' => $info['nickname'],
                            'sex' => $info['sex'],
                            'user_token' => md5($retval)
                        ], ['uid' => $retval]);
                    }
                }
                break;
            case "unsubscribe": // 取消关注公众号
                $openid = $postObj->FromUserName;
                $this->weixin_service->WeixinUserUnsubscribe((string)$openid);
                break;
            case "VIEW": // VIEW事件 - 点击菜单跳转链接时的事件推送
                /* $this->wchat->weichat_menu_hits_view($postObj->EventKey); //菜单计数 */
                $contentStr = "";
                break;
            case "SCAN": // SCAN事件 - 用户已关注时的事件推送 - 扫描带参数二维码事件
                $contentStr = "";
                // $contentStr = "shop_url：".$this->shop_url." uid：".$postObj->EventKey; //二维码推广
                break;
            case "CLICK": // CLICK事件 - 自定义菜单事件
                $menu_detail = $this->weixin_service->getWeixinMenuDetail($postObj->EventKey, $this->website_id);
                if($menu_detail['menu_event_type']==5){
                    $key = $this->weixin_service->getWeixinKey($menu_detail['media_id']);
                    $menu_detail['media_id'] = $key['reply_media_id'];
                    if(empty($menu_detail['media_id'])){
                        $poster_data = $this->weixin_service->getPosterData($key['key'], $this->website_id);
                        if($poster_data){
                            $this->weixin_service->getPosterSend($poster_data, $key['key'], $postObj->FromUserName);
                        }
                    }
                }
                $media_info = $this->weixin_service->getWeixinMediaDetail($menu_detail['media_id']);
                $contentStr = $this->weixin_service->getMediaWchatStruct($media_info); // 构造media数据并返回 */
                break;
                /*发票助手*/
            case "user_authorize_invoice":
                if (getAddons('invoice', $this->website_id)) {
                $contentStr = "";
                    $postObj = objToArr($postObj);
                    debugLog($postObj, '发票-用户领取回调1.1==> ');
                $invoice_data = [
                        'create_time' => $postObj['CreateTime'],
                        's_order_no' => $postObj['SuccOrderId'],
                        'f_order_no' => $postObj['FailOrderId'][0],
                        'source' => $postObj['Source'],
                        'openid' => $postObj['AuthorizeAppId']
                ];
                    debugLog($invoice_data, '发票-用户领取回调1==> ');
                try {
                    $invoice = new Invoice();
                    $invoice->userGetInvoiceFromWchat($invoice_data);
                } catch (\Exception $e) {
                        debugLog($e->getMessage(), '发票-用户领取回调2==> ');
                        return '';
                    }
                }
                break;
            default:
                break;
        }
        // $contentStr = $postObj->Event."from_callback";//测试接口正式部署之后注释不要删除
        if (is_array($contentStr)) {
            if ($contentStr['type'] == 'text') {
                $resultStr = $this->wchat->event_key_text($postObj, $contentStr);
            }
            if ($contentStr['type'] == 'image') {
                unset($contentStr['type']);
                $resultStr = $this->wchat->event_key_image($postObj, $contentStr);
            }
            if ($contentStr['type'] == 'voice') {
                unset($contentStr['type']);
                $resultStr = $this->wchat->event_key_voice($postObj, $contentStr);
            }
            if ($contentStr['type'] == 'video') {
                unset($contentStr['type']);
                $resultStr = $this->wchat->event_key_video($postObj, $contentStr);
            }
            if ($contentStr['type'] == 'news') {
                unset($contentStr['type']);
                $resultStr = $this->wchat->event_key_news($postObj, $contentStr);
            }
        } else {
            $resultStr = $this->wchat->event_key_text($postObj, $contentStr);
        }
        return $resultStr;
    }

    /**
     * ************************************************************************微信公众号消息相关方法 结束******************************************************
     */
}