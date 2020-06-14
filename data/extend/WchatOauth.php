<?php

namespace data\extend;

use addons\miniprogram\model\WeixinAuthModel;
use addons\miniprogram\service\MiniProgram;
use data\extend\wchat\WxBizDataCrypt;
use think\Cache as cache;
use data\service\Config;
use think;

/**
 * 功能说明：微信基本功能测试编码，通过此页面可以获取通过开放平台得到的公众号会话（token）以及公众号对应的appid
 */
class WchatOauth
{

    public $author_appid;

    public $token;
    public $instance_id;
    public $website_id;
    protected $values = array();
    /**
     * 构造函数
     *
     * @param unknown $shop_id
     */
    public function __construct($website_id)
    {
        $this->author_appid = $website_id;
        $this->instance_id = 0;
        $this->website_id = $website_id;
    }

    /**
     * ***********************************************************************基础信息*************************************************
     */
    /**
     * 公众号获取access_token
     *
     * @return unknown
     */
    public function get_access_token($website_id=0)
    {
        // 公众平台模式获取token
        $token = $this->single_get_access_token($website_id);
        return $token;
    }

    /**
     * 公众平台账户获取token
     */
    protected function single_get_access_token($website_id)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $config = new Config();
        $wchat_config = $config->getInstanceWchatConfig(0, $websiteid);
        if(cache::get('token-' . $this->author_appid) && cache::get('token-' . $this->author_appid)['time'] > time() && cache::get('token-' . $this->author_appid)['access_token']){
            return cache::get('token-' . $this->author_appid)['access_token'];
        }
        $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wchat_config['value']['appid'].'&secret='.$wchat_config['value']['appsecret'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $a = curl_exec($ch);
        curl_close($ch);
        $strjson = json_decode($a,true);
        if ($strjson['code'] !=0) {
            return $strjson['errmsg'];
        }
        if ($strjson == false || empty($strjson)) {
            return '';
        } else {
            $token['access_token'] = $strjson['access_token'];
            $token['time'] = time()+7100;
            // 注意如果是多用户需要
            cache::set('token-' . $this->author_appid, $token, 7100);
            return $token['access_token'];
        }

    }
    /*
     * 小程序获取access_token
     * **/
    public function getMpAccessToken($website_id)
    {
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $website_id;
        $mini_program_service = new MiniProgram();
        $mini_program_info = $mini_program_service->miniProgramInfo($condition);
//        Cache::set('mp_access_token_'.$website_id, '');
        if(Cache::get('mp_access_token_'.$website_id)){
            $access_token = Cache::get('mp_access_token_'.$website_id);
        }else{
            $appid = $mini_program_info['authorizer_appid'];
            $secret = $mini_program_info['authorizer_secret'];
            $get_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='. $appid .'&secret='.$secret;
            $token_res = curlRequest($get_token_url, 'GET');
            $token_arr = json_decode($token_res, true);
            $access_token = $token_arr['access_token'];
            Cache::set('mp_access_token_'.$website_id, $access_token, 5400);
        }
        return $access_token;
    }
    /**
     * 微信数据获取
     *
     * @param unknown $url
     * @param unknown $data
     * @param string $needToken
     * @return string|unknown
     */
    private function get_url_return($url, $data = '', $needToken = false)
    {
        $this->token = $this->get_access_token();
        // 第一次为空，则从文件中读取
        $newurl = sprintf($url, $this->token);
        $curl = curl_init(); // 创建一个新url资源
        curl_setopt($curl, CURLOPT_URL, $newurl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $AjaxReturn = curl_exec($curl);
        // curl_close();
        $strjson = json_decode($AjaxReturn,true);
        if (!empty($strjson->errcode)) {
            switch ($strjson->errcode) {
                case 40001:
                    return $this->get_url_return($url, $data, true); // 获取access_token时AppSecret错误，或者access_token无效
                    break;
                case 40014:
                    return $this->get_url_return($url, $data, true); // 不合法的access_token
                    break;
                case 42001:
                    return $this->get_url_return($url, $data, true); // access_token超时
                    break;
                case 45009:
                    return json_encode(array(
                        "errcode" => -45009,
                        "errmsg" => "接口调用超过限制：" . $strjson->errmsg
                    ));
                    break;
                case 41001:
                    return json_encode(array(
                        "errcode" => -41001,
                        "errmsg" => "缺少access_token参数：" . $strjson->errmsg
                    ));
                    break;
                default:
                    return json_encode(array(
                        "errcode" => -41000,
                        "errmsg" => $strjson->errmsg
                    )); // 其他错误，抛出
                    break;
            }
        } else {
            return $strjson;
        }
    }

    /**
     * ***********************************************************************基础信息*************************************************
     */
    /**
     * *************************************************微信回复消息部分 开始**************************************
     */
    /**
     * 返回文本消息组装xml
     *
     * @param unknown $postObj
     * @param unknown $content
     * @param number $funcFlag
     * @return string
     */
    public function event_key_text($postObj, $content)
    {
        if (!empty($content)) {
            $content[0]['Content'] = preg_replace_callback('/\:\:([0-9a-zA-Z_-]+)\:\:/', create_function('$matches', 'return utf8_bytes(hexdec($matches[1]));'), $content[0]['Content']);
            $xmlTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[text]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                        </xml>";
            $resultStr = sprintf($xmlTpl, $postObj->FromUserName, $postObj->ToUserName, time(), $content[0]['Content']);
            return $resultStr;
        } else {
            return '';
        }
    }

    /**
     * 返回图片消息组装xml
     *
     * @param unknown $postObj
     * @param unknown $content
     * @param number $funcFlag
     * @return string
     */
    public function event_key_image($postObj, $arr_item)
    {
        if (is_array($arr_item)) {
            $xmlTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Image><MediaId><![CDATA[%s]]></MediaId></Image>
                        </xml>";
            $resultStr = sprintf($xmlTpl, $postObj->FromUserName, $postObj->ToUserName, time(),'image', $arr_item[0]['MediaId']);
            return $resultStr;
        } else {
            return '';
        }
    }
    /**
     * 返回语音消息组装xml
     *
     * @param unknown $postObj
     * @param unknown $content
     * @param number $funcFlag
     * @return string
     */
    public function event_key_voice($postObj, $arr_item)
    {
        if (is_array($arr_item)) {
            $xmlTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Voice><MediaId><![CDATA[%s]]></MediaId></Voice>
                        </xml>";
            $resultStr = sprintf($xmlTpl, $postObj->FromUserName, $postObj->ToUserName, time(),'voice', $arr_item[0]['MediaId']);
            return $resultStr;
        } else {
            return '';
        }
    }
    /**
     * 返回视频消息组装xml
     *
     * @param unknown $postObj
     * @param unknown $content
     * @param number $funcFlag
     * @return string
     */
    public function event_key_video($postObj, $arr_item, $funcFlag = 0)
    {
        if (is_array($arr_item)) {
            $xmlTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Video><MediaId><![CDATA[%s]]></MediaId></Video>
                            <Title><![CDATA[%s]]></Title>
                            <Description><![CDATA[%s]]></Description>
                        </xml>";
            $resultStr = sprintf($xmlTpl, $postObj->FromUserName, $postObj->ToUserName, time(),'video', $arr_item[0]['MediaId'],$arr_item[0]['Title'],$arr_item[0]['Description']);
            return $resultStr;
        } else {
            return '';
        }
    }
    /**
     * 返回图文消息组装xml
     *
     * @param unknown $postObj
     * @param unknown $arr_item
     * @param number $funcFlag
     * @return void|string
     */
    public function event_key_news($postObj, $arr_item)
    {
        // 首条标题28字，其他标题39字
        if (!is_array($arr_item)) {
            return;
        }
        $itemTpl = "<item>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <PicUrl><![CDATA[%s]]></PicUrl>
                        <Url><![CDATA[%s]]></Url>
                    </item>
                ";
        $item_str = "";
        foreach ($arr_item as $k =>$item) {
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $newsTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <Content><![CDATA[]]></Content>
        <ArticleCount>%s</ArticleCount>
        <Articles>$item_str</Articles>
        </xml>";
        //过滤%符号
        $resultStr = sprintf(str_replace("%%s","%s",str_replace("%","%%",$newsTpl)), $postObj->FromUserName, $postObj->ToUserName, time(), count($arr_item));
        return $resultStr;
    }

    /**
     * *************************************************微信回复消息部分 结束******************************************************************************
     */

    /**
     * **********************************************************************************微信获取粉丝信息 开始*********************************************
     */

    /**
     * 微信公众号拉取粉丝信息
     *
     * @param unknown $next_openid
     * @return mixed
     */
    public function get_fans_list($next_openid)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$token.'&next_openid='.$next_openid;
        $strjson = $this->get_url_return($url);
        return $strjson;
    }

    /**
     * 批量获取用户粉丝信息
     * @return mixed
     */
    public function get_fans_info_list($openids)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=' . $token;
        $strjson = $this->get_url_return($url, urldecode(json_encode($openids,true)));
        return $strjson;
    }

    /**
     * 获取公众号标签（通过openID）
     *
     * @param unknown $openid
     * @return Ambigous <string, \data\extend\unknown, mixed>
     */
    public function get_tags_info()
    {
        $token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/tags/get?access_token=".$token;
        return $this->get_url_return($url);
    }
    /**
     * 获取公众号黑名单（通过openID）
     *
     * @param unknown $openid
     * @return Ambigous <string, \data\extend\unknown, mixed>
     */
    public function get_black_list()
    {
        $token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/tags/members/getblacklist?access_token=".$token;
        $send = array(
            'begin_openid' => ['']
        );
        return $this->get_url_return($url,json_encode($send));
    }
    /**
     * 获取粉丝信息（通过openID）
     *
     * @param unknown $openid
     * @return Ambigous <string, \data\extend\unknown, mixed>
     */
    public function get_fans_info($openid, $website_id = 0)
    {
        $token = $this->get_access_token($website_id);
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$token."&openid={$openid}";
        return $this->get_url_return($url);
    }
    /**
     * 添加分组标签（通过openID）
     *
     * @param unknown $openid
     * @return Ambigous <string, \data\extend\unknown, mixed>
     */
    public function add_group_weixin($group_name)
    {
        $token = $this->get_access_token();
        $send['tag'] = array(
            'name' => urlencode($group_name)
        );
        $url = "https://api.weixin.qq.com/cgi-bin/tags/create?access_token=".$token;
        return $this->get_url_return($url,json_encode($send));
    }
    /**
     * 修改分组标签（通过openID）
     *
     * @param unknown $openid
     * @return Ambigous <string, \data\extend\unknown, mixed>
     */
    public function update_group_weixin($group_id,$group_name)
    {
        $token = $this->get_access_token();
        $send['tag'] = array(
            'name' => urlencode($group_name),
            'id' => $group_id,
        );
        $url = "https://api.weixin.qq.com/cgi-bin/tags/update?access_token=".$token;
        return $this->get_url_return($url,json_encode($send));
    }
    /**
     * 删除分组标签（通过openID）
     *
     * @param unknown $openid
     * @return Ambigous <string, \data\extend\unknown, mixed>
     */
    public function del_group_weixin($group_id)
    {
        $token = $this->get_access_token();
        $send['tag'] = array(
            'id' => $group_id,
        );
        $url = "https://api.weixin.qq.com/cgi-bin/tags/delete?access_token=".$token;
        return $this->get_url_return($url,json_encode($send));
    }
    /**
     * 修改粉丝标签（通过openID）
     * $default_group_id: -1未分组  -2黑名单
     * @param unknown $openid
     * @return Ambigous <string, \data\extend\unknown, mixed>
     */
    public function update_fans_group($openid,$group_id,$default_group_id)
    {
        $token = $this->get_access_token();
        if($default_group_id!=-1 && $default_group_id!=-2){//为用户取消标签
            $send['openid_list'] = [$openid];
            $send['tagid'] = $default_group_id;
            $url = "https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging?access_token=".$token;
            $res = $this->get_url_return($url,json_encode($send));
        }
        if($group_id!=-1 && $group_id!=-2) {//为用户打标签
            $send1['openid_list'] = [$openid];
            $send1['tagid'] = $group_id;
            $url1 = "https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token=" . $token;
            $res= $this->get_url_return($url1,json_encode($send1));
        }
        if($group_id==-1){//拉黑用户
            $send2['openid_list'] = [$openid];
            $url2 = "https://api.weixin.qq.com/cgi-bin/tags/members/batchblacklist?access_token=" . $token;
            $res= $this->get_url_return($url2,json_encode($send2));
        }
        if($default_group_id==-1){//取消拉黑用户
            $send3['openid_list'] = [$openid];
            $url3 = "https://api.weixin.qq.com/cgi-bin/tags/members/batchunblacklist?access_token=" . $token;
            $res= $this->get_url_return($url3,json_encode($send3));
        }
        return $res;
    }
    /**
     * 获取openid(前台会员)
     *
     * @return unknown
     */
    public function get_member_access_token()
    {
        if (isWeixin()) {
            // 通过code获得openid
            if (empty($_GET['code'])) {
                // 触发微信返回code码
                //$baseUrl = request()->url(true);
                $redirect_url = request()->post('redirect_url');
                $baseUrl = think\Request::instance()->domain().'/wap'.$redirect_url;
                if(is_object($baseUrl)) {
                    $baseUrl = think\Request::instance()->domain().'/wap/mall/index';
                }
                $url = $this->get_single_authorize_url($baseUrl, "wchat");
                return $url;
                //Header("Location: $url");
                //exit();
            } else {
                // 获取code码，以获取openid
                $code = $_GET['code'];
                $data = $this->get_single_access_token($code);
                return $data;
            }
        }
    }
    /**
     * 获取openid(店员)
     *
     * @return unknown
     */
    public function get_member_access_token_clerk()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            // 通过code获得openid
            if (empty($_GET['code'])) {
                // 触发微信返回code码
                //$baseUrl = request()->url(true);
                $baseUrl = think\Request::instance()->domain() . '/clerk/pages/login/author';
                $url = $this->get_single_authorize_url($baseUrl, "wchat");
                return $url;
                //Header("Location: $url");
                //exit();
            } else {
                // 获取code码，以获取openid
                $code = $_GET['code'];
                $data = $this->get_single_access_token($code);
                return $data;
            }
        }
    }

    /**
     * 小程序用code获取unionid和openid
     * https://developers.weixin.qq.com/miniprogram/dev/api/code2Session.html
     * @param $code
     * @return unknown|string
     */
    public function get_code_to_session($code)
    {
        $data = $this->code_to_session($code);
        return $data;
    }

    /**
     * 获取OAuth2授权access_token(微信公众平台模式)
     *
     * @param string $code
     *            通过get_authorize_url获取到的code
     */
    public function get_single_access_token($code = '')
    {
        $config = new Config();
        $wchat_config = $config->getInstanceWchatConfig(0, $this->website_id);
        $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $wchat_config['value']['appid'] . '&secret=' . $wchat_config['value']['appsecret'] . '&code=' . $code . '&grant_type=authorization_code';
        $data = $this->get_url_return($token_url);
        //$token_data = json_decode($data, true);
        return $data;
    }

    public function refresh_access_token($refresh_token)
    {
        $config = new Config();
        $wchat_config = $config->getInstanceWchatConfig(0, $this->website_id);
        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=' . $wchat_config['value']['appid'] . '&grant_type=refresh_token&refresh_token=' . $refresh_token;
        $data = $this->get_url_return($url);
        return $data;
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
        $wx_auth = $auth->getInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id],'authorizer_appid, authorizer_secret');
        $app_id = $wx_auth['authorizer_appid'];
        $secret = $wx_auth['authorizer_secret'];

        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $app_id . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';
        $data = $this->get_url_return($url);
        return $data;
    }

    /**
     * 获取微信OAuth2授权链接snsapi_base
     *
     * @param string $redirect_uri
     *            跳转地址
     * @param mixed $state
     *            参数
     *            不弹出授权页面，直接跳转，只能获取用户openid
     */
    public function get_single_authorize_url($redirect_url = '', $state = '')
    {
        $redirect_url = urlencode($redirect_url);
        $config = new Config();
        $wchat_config = $config->getInstanceWchatConfig(0, $this->website_id);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $wchat_config['value']['appid'] . "&redirect_uri=" . $redirect_url . "&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";
    }

    /**
     * 获取会员对于公众号信息
     *
     * @param unknown $appid
     */
    public function get_oauth_member_info($token)
    {
        $token_url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $token['access_token'] . "&openid=" . $token['openid'] . "&lang=zh_CN";
        $data = $this->get_url_return($token_url);
        return $data;
    }

    /**
     * 获取会员对于公众号信息
     *
     * @param unknown $appid
     */
    public function get_oauth_member_info_openid($openid)
    {
        $token = $this->get_access_token();
        $token_url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $token . "&openid=" . $openid . "&lang=zh_CN";
        $data = $this->get_url_return($token_url);
        return $data;
    }

    // 基础支持: 多媒体文件上传接口 /media/upload媒体文件类型，分别有图片（image）、语音（voice）、视频（video）和缩略图（thumb）
    // form-data中媒体文件标识，有filename、filelength、content-type等信息
    public function upload_media($data,$type)
    {
        $token = $this->get_access_token();
        $url = "http://file.api.weixin.qq.com/cgi-bin/media/uploadimg?access_token={$token}&type=" . $type;
        return $this->get_url_return($url,$data);
    }
    //命令行模式上传永久素材
    public function upload_exec($type,$img_src)
    {
        /* 使用exec函数 */
        $token = $this->get_access_token();
        $command = 'curl -F media=@'.$img_src.' "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token='.$token.'&type='.$type.'"';
        $retval = array();
        exec($command, $retval, $status);
        $params = array();
        $params = json_decode($retval[0],true);
        if ($status != 0) {
            $params = array(
                'errcode'  => '-100',
                'errmsg'  => '公众号服务出错，请联系管理员',
            );
        }
        return $params;
    }
    //命令行模式上传临时素材
    public function upload_temp_exec($type,$img_src, $website_id)
    {
        /* 使用exec函数 */
        $token = $this->getMpAccessToken($website_id);
        $command = 'curl -F media=@'.$img_src.' "https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$token.'&type='.$type.'"';
        $retval = array();
        exec($command, $retval, $status);
        $params = array();
        $params = json_decode($retval[0],true);
        if ($status != 0) {
            $params = array(
                'errcode'  => '-100',
                'errmsg'  => '服务出错',
            );
        }
        return $params;
    }

    // 新增永久图文素材
    public function add_news($data)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=' . $token;
        $response = $this->get_url_return($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response;
    }
    // 同步微信素材
    public function get_media($data)
    {
        $token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token={$token}";
        $response = $this->get_url_return($url, urldecode(json_encode($data)));
        return $response;
    }
    //素材总数
    public function getMaterialCount() {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=' . $token;
        $response = $this->get_url_return($url);
        return $response;
    }
    //删除素材
    public function delMaterial($media_id) {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/del_material?access_token=' . $token;
        $data = array(
            'media_id' => trim($media_id),
        );
        $response = $this->get_url_return($url, urldecode(json_encode($data)));
        return $response;
    }
    //获取素材信息
    public function getMaterial($media_id, $type = 'image') {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=' . $token;
        $data = array(
            'media_id' => trim($media_id),
        );
        $response = $this->get_url_return($url, urldecode(json_encode($data)));
        if($type == 'image' || $type == 'voice') {
            $response = $response['content'];
        }
        return $response;
    }
    //发送预览
    public function fansSendPreview($wxname, $content, $msgtype) {
        $types = array('text' => 'text', 'image' => 'image', 'news' => 'mpnews', 'voice' => 'voice', 'video' => 'mpvideo', 'wxcard' => 'wxcard');
        if(empty($types[$msgtype])) {
            return array(
                'errcode'=>-1,
                'message'=>'群发类型不合法'
            );
        }
        $msgtype = $types[$msgtype];
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=' . $token;
        $send = array(
            'towxname' => $wxname,
            'msgtype' => $msgtype,
        );
        if($msgtype == 'text') {
            $send[$msgtype] = array(
                'content' => $content
            );
        } elseif($msgtype == 'wxcard') {
            $send[$msgtype] = array(
                'card_id' => $content
            );
        } else {
            $send[$msgtype] = array(
                'media_id' => $content
            );
        }
        $response = $this->get_url_return($url, json_encode($send));
        return $response;
    }
    // 单发客服消息
    public function send_message($openid, $msgtype, $content)
    {
        $token = $this->get_access_token();
        $types = array('text' => 'text', 'image' => 'image', 'news' => 'mpnews', 'voice' => 'voice', 'video' => 'mpvideo', 'wxcard' => 'wxcard');
        if(empty($types[$msgtype])) {
            return array(
                'errcode'=>-1,
                'message'=>'发送类型不合法'
            );
        }
        $msgtype = $types[$msgtype];
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $token;
        $send = array(
            'touser' => $openid,
            'msgtype' => $msgtype,
        );
        if($msgtype == 'text') {
            $content = preg_replace_callback('/\:\:([0-9a-zA-Z_-]+)\:\:/', create_function('$matches', 'return utf8_bytes(hexdec($matches[1]));'), $content);
            $send[$msgtype] = array(
                'content' =>$content
            );
        } elseif($msgtype == 'wxcard') {
            $send[$msgtype] = array(
                'card_id' => $content
            );
        } else {
            $send[$msgtype] = array(
                'media_id' => $content
            );
        }
        return $this->get_url_return($url, json_encode($send,JSON_UNESCAPED_UNICODE));
    }

    // 分组群发消息
    public function send_group_message($tag_id, $msgtype, $content)
    {
        $token = $this->get_access_token();
        $types = array('text' => 'text', 'image' => 'image', 'news' => 'mpnews', 'voice' => 'voice', 'video' => 'mpvideo', 'wxcard' => 'wxcard');
        if(empty($types[$msgtype])) {
            return array(
                'errcode'=>-1,
                'message'=>'群发类型不合法'
            );
        }
        $msgtype = $types[$msgtype];
        $send = array(
            'filter' => array(
                'is_to_all' => false,
                'tag_id'=>$tag_id,
            ),
            'msgtype' => $msgtype,
        );
        if($msgtype == 'text') {
            $send[$msgtype] = array(
                'content' => urlencode($content)
            );
        } elseif($msgtype == 'wxcard') {
            $send[$msgtype] = array(
                'card_id' => $content
            );
        } else {
            $send[$msgtype] = array(
                'media_id' => $content
            );
        }
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=". $token;
        return $this->get_url_return($url, json_encode($send));
    }
    // 所有粉丝群发消息
    public function send_all_group_message($msgtype, $content)
    {
        $token = $this->get_access_token();
        $types = array('text' => 'text', 'image' => 'image', 'news' => 'mpnews', 'voice' => 'voice', 'video' => 'mpvideo', 'wxcard' => 'wxcard');
        if(empty($types[$msgtype])) {
            return array(
                'errcode'=>-1,
                'message'=>'群发类型不合法'
            );
        }
        $msgtype = $types[$msgtype];
        $send = array(
            'filter' => array(
                'is_to_all' => true,
            ),
            'msgtype' => $msgtype,
        );
        if($msgtype == 'text') {
            $send[$msgtype] = array(
                'content' => urlencode($content)
            );
        } elseif($msgtype == 'wxcard') {
            $send[$msgtype] = array(
                'card_id' => $content
            );
        } else {
            $send[$msgtype] = array(
                'media_id' => $content
            );
        }
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=". $token;
        return $this->get_url_return($url, json_encode($send));
    }
    /**
     * **********************************************************************************微信获取粉丝信息 结束*********************************************
     */
    /**
     * 微信公众号自定义菜单
     *
     * @param unknown $appid
     * @param unknown $jsonmenu
     * @return Ambigous <string, \data\extend\unknown, mixed>
     */
    public function menu_create($jsonmenu)
    {
        $token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $token;
        $result = $this->get_url_return($url, $jsonmenu);
        return $result;
    }

    /**
     * 生成临时二维码图片地址
     * @param array $scene
     * @param string $action_name
     */
    public function ever_qrcode(array $scene, $action_name = 'QR_STR_SCENE')
    {
        $data_array = array(
            'expire_seconds' => 604800,
            'action_name' => $action_name,
            'action_info' => array(
                'scene' => $scene
            )
        );

        $json = json_encode($data_array);
        return $this->qrcode_create($json);
    }

    /**
     * 推广支持: 创建二维码ticket接口 /qrcode/create && 换取二维码 /showqrcode
     *
     * @return src [二维码图片地址]
     */
    // 生成二维码基类函数
    public function qrcode_create($json)
    {
        // 临时二维码请求说明POST-json：{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "123"}}}
        // 永久二维码请求说明POST-json：POST数据例子：{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}
        // action_name 二维码类型，QR_SCENE为临时,QR_LIMIT_SCENE为永久,QR_LIMIT_STR_SCENE为永久的字符串参数值
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=%s";
        $jsonReturn = $this->get_url_return($url, $json);
        if (!empty($jsonReturn['ticket'])) {
             $QrCode = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$jsonReturn['ticket'];
//            $QrCode = $jsonReturn['url'];
        } else {
            $QrCode = '';
        }

        return $QrCode;
    }

    /**
     * 把微信生成的图片存入本地
     *
     * @param [type] $username
     *            [用户名]
     * @param [string] $LocalPath
     *            [要存入的本地图片地址]
     * @param [type] $weixinPath
     *            [微信图片地址]
     *
     * @return [string] [$LocalPath]失败时返回 FALSE
     */
    public function save_weixin_img($local_path, $weixin_path)
    {
        $weixin_path_a = str_replace("https://", "http://", $weixin_path);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $weixin_path_a);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
        curl_setopt($ch, CURLOPT_REFERER, _REFERER_);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $r = curl_exec($ch);
        curl_close($ch);
        if (!empty($local_path) && !empty($weixin_path_a)) {
            $msg = file_put_contents($local_path, $r);
        }
        // 执行图片压缩
        $image = think\Image::open($local_path);
        $image->thumb(120, 120, \think\Image::THUMB_CENTER)->save($local_path);
        return $local_path;
    }

    /**
     * **********************************************************************************微信推广二维码 结束*********************************************
     */
    /**
     * 功能说明：从微信选择地址 - 创建签名SHA1
     *
     * @param array $Parameters
     *            string1加密
     */
    public function sha1_sign($Parameters)
    {
        $signPars = '';
        ksort($Parameters);
        foreach ($Parameters as $k => $v) {
            if ("" != $v && "sign" != $k) {
                if ($signPars == '')
                    $signPars .= $k . "=" . $v;
                else
                    $signPars .= "&" . $k . "=" . $v;
            }
        }
        $sign = sha1($signPars);
        return $sign;
    }

    /**
     * 产生随机字符串，不长于32位
     *
     * @param int $length
     * @return 产生的随机字符串
     */
    public function get_nonce_str($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function to_url_param()
    {
        $buff = "";
        foreach ($this->values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }


    /**
     * *************模板消息接口*********************************************************************************************
     */
    // 获取模板ID POST请求
    public function templateID($templateno)
    {
        $templateno_array = array(
            "template_id_short" => $templateno
        );
        $token = $this->get_access_token();
        $json = json_encode($templateno_array);
        $url = "https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token=" . $token;
        return $this->get_url_return($url, $json);
    }

    // 获取模板列表 GET请求
    public function templateList()
    {
        $token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=" . $token;
        return $this->get_url_return($url);
    }

    // 模版消息发送
    public function templateMessageSend($array,$website_id)
    {
        $json = json_encode($array,JSON_UNESCAPED_UNICODE);
        $token = $this->get_access_token($website_id);
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $token;
        $res = $this->get_url_return($url, $json);
        return $res;
    }

    public function MessageSendToUser($openid, $msg_type, $content)
    {
        $array = array(
            'touser' => $openid
        );
        switch ($msg_type) {
            case "text":
                $array['msgtype'] = 'text';
                $array['text'] = array(
                    'content' => $content
                );
                break;
        }
        $json = json_encode($array);
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=%s";
        return $this->get_url_return($url, $json);
    }
    public function jsapi_ticket($website_id){
        $token = $this->get_access_token();
        if($_SESSION[$website_id.'jsapi_ticket'] && $_SESSION[$website_id.'jsapi_ticket_expire_time']>time()){
            $jsapi_ticket = $_SESSION[$website_id.'jsapi_ticket'];
        }else {
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $token . "&type=jsapi";
            $ticket = $this->get_url_return($url);
            $jsapi_ticket = $ticket['ticket'];
            $_SESSION[$website_id.'jsapi_ticket'] = $jsapi_ticket;
            $_SESSION[$website_id.'jsapi_ticket_expire_time'] = time()+7100;
        }
        return $jsapi_ticket;
    }
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function shareWx($url) {
        $config = new Config();
        $result = [];
        $wchat_config = $config->getInstanceWchatConfig(0, $this->website_id);
        $result['nonceStr'] = $this->get_nonce_str(16);
        $result['timestamp'] = time();
        $result['jsapi_ticket'] = $this->jsapi_ticket($this->website_id);
        $string = 'jsapi_ticket='.$result['jsapi_ticket'].'&noncestr='.$result['nonceStr'].'&timestamp='.$result['timestamp'].'&url='.$url;
        $result['signature'] = sha1($string);
        $result['appId'] = $wchat_config['value']['appid'];
        return $result;
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
        $wx_authorizer = $auth->getInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id],'authorizer_appid');
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
    /******************************** 电子发票 start **************************************************/
    /**
     * 设置商户联系方式
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Vendor_API_List.html#17 )
     * TYPE: POST
     * @param $contact array [联系方式信息]
     * @return unknown|array [错误信息 errcode:0 ok; 其他：错误信息]
     */
    public function postInvoiceSetbizattr($contact)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/setbizattr?action=set_contact&access_token=' . $token;
        $result = $this->get_url_return($url, urldecode(json_encode($contact,true)));
        return $result;
    }
    /**
     * 查询商户联系方式
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Vendor_API_List.html#18 )
     * TYPE: POST
     * @param $contact array [联系方式信息]
     * @return unknown|array [contact: 联系方式信息]
     */
    public function getInvoiceSetbizattr()
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/setbizattr?action=get_contact&access_token=' . $token;
        $result = $this->get_url_return($url, "{}");
        return $result;
    }
    /**
     * 获取授权页ticket
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Vendor_API_List.html#1)
     * TYPE: GET
     * @return unknown|array [ticket: 临时票据，用于在获取授权链接时作为参数传入]
     */
    public function getInvoiceTicket()
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $token . '&type=wx_card';
        $result = $this->get_url_return($url);
        return $result;
    }
    /**
     * 获取授权页链接
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Vendor_API_List.html#2)
     * TYPE: POST
     * @param $data array [获取链接相关参数]
     * @return unknown|array [错误信息 errcode:0 ok; 其他：错误信息]
     */
    public function getInvoiceAuthUrl($data)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/getauthurl?access_token=' . $token;
        $result = $this->get_url_return($url, json_encode($data,JSON_UNESCAPED_UNICODE));
        return $result;
    }
    /**
     * 获取自身的开票平台识别码
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Invoicing_Platform_API_List.html#1)
     * TYPE: POST
     * @return unknown|array [invoice_url:授权链接,包含s_pappid：商品平台appid]
     */
    public function getInvoiceStorePlatformAppId()
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/seturl?access_token=' . $token;
        $result = $this->get_url_return($url, "{}");
        return $result;
    }
    /**
     * 创建发票卡券模板
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Invoicing_Platform_API_List.html#2)
     * TYPE: POST
     * @param $invoice_info array [发票模板对象]
     * @return unknown|array [card_id: 卡券模板的编号]
     */
    public function postInvoiceCreateCard($invoice_info)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/platform/createcard?access_token=' . $token;
        $result = $this->get_url_return($url, json_encode($invoice_info,JSON_UNESCAPED_UNICODE));
        return $result;
    }
    /**
     * 上传PDF
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Invoicing_Platform_API_List.html#3)
     * TYPE: POST
     * @param $param multipart/form-data [图片二进制流]
     * $fields = array(
     *  'type' => 'pdf',
     *  'filename' => $fileName,
     *  'filesize' => '',
     *  'offset' => 0,
     *  'filetype' => '.pdf',
     *  'originName' => $fileName,
     *  'upload'=> file_get_contents($file_path)
     *  );
     * @return unknown|array [s_media_id: 电子发票pdf的id]
     */
    public function postInvoiceUploadPdf($param)
    {
        // 组装要上传的pdf文件流
        $delimiter = uniqid();
        $data = '';
        $eol = "\r\n";
        $upload = $param['upload'];
        unset($param['upload']);
        foreach ($param as $name => $content) {
            $data .= "--" . $delimiter . "\r\n"
                . 'Content-Disposition:form-data;name="' . $name . "\"\r\n\r\n"
                . $content . "\r\n";
        }
        // 拼接文件流
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="pdf"; filename="' . $param['filename'] . '"' . "\r\n"
            . 'Content-Type:application/octet-stream'."\r\n\r\n";
        $data .= $upload . "\r\n";
        $data .= "--" . $delimiter . "--\r\n";

        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/platform/setpdf?access_token=' . $token;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type:multipart/form-data;boundary=" . $delimiter,
            "Content-Length:" . strlen($data)
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $info = json_decode($response, true);
        return $info;
    }
    /**
     * 查询已上传的PDF文件
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Invoicing_Platform_API_List.html#4)
     * TYPE: POST
     * @param $data array [请求参数 s_media_id: 电子发票pdf的id]
     * @return unknown|array [pdf_url：pdf 的 url ，两个小时有效期]
     */
    public function getInvoiceUploadPdf($data)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/platform/getpdf?action=get_url&access_token=' . $token;
        $result = $this->get_url_return($url, json_encode($data,JSON_UNESCAPED_UNICODE));
        return $result;
    }
    /**
     * 将电子发票卡券插入用户卡包
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Invoicing_Platform_API_List.html#5)
     * TYPE: POST
     * @param $data array [发票相关数据]
     * @return unknown|array [code：发票code ，openid: 获得发票用户的openid]
     */
    public function postInvoiceInsertCardPackage($data)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/insert?access_token=' . $token;
        $result = $this->get_url_return($url, json_encode($data,JSON_UNESCAPED_UNICODE));
        return $result;
    }
    /**
     * 查询授权完成状态
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Vendor_API_List.html#7)
     * TYPE: POST
     * @param $data array [请求参数]
     * @return unknown|array [invoice_status：订单授权状态]
     */
    public function getInvoiceAuthData($data)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/getauthdata?access_token=' . $token;
        $result = $this->get_url_return($url, json_encode($data,JSON_UNESCAPED_UNICODE));
        return $result;
    }
    /**
     * 拒绝开票
     * Licensed ( https://developers.weixin.qq.com/doc/offiaccount/WeChat_Invoice/E_Invoice/Vendor_API_List.html#8
     * TYPE: POST
     * @param $data array [请求参数]
     * @return unknown|array [invoice_status：订单授权状态]
     */
    public function rejectInsert($data)
    {
        $token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/card/invoice/rejectinsert?access_token=' . $token;
        $result = $this->get_url_return($url, json_encode($data,JSON_UNESCAPED_UNICODE));
        return $result;
    }
    /******************************** 电子发票 end **************************************************/
}