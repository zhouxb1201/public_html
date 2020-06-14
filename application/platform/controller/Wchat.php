<?php
namespace app\platform\controller;
use addons\miniprogram\model\WeixinAuthModel;
use data\extend\WchatOauth;
use data\model\WebSiteModel;
use data\model\WechatAttachmentModel;
use data\model\WechatNewsModel;
use data\model\WeixinGroupModel;
use data\model\WeixinKeyReplayModel;
use data\model\WeixinMenuModel;
use data\service\Config;
use data\service\Weixin;
use data\service\WeixinMessage;
use data\model\WeixinFansModel;

use think\Cache;
/**
 * 微信管理
 *
 * @author  www.vslai.com
 *        
 */
class Wchat extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 微信账户设置
     */
    public function config()
    {
        $config = new Config();
        $wchat_config = $config->getInstanceWchatConfig($this->instance_id, $this->website_id);
        // 获取当前域名
        $web_info = $this->website->getWebSiteInfo();
        $domain_name1 = $this->http . $web_info['realm_ip'];
        $domain_name11 =  $web_info['realm_ip'];
        $this->assign("domain_name1", $domain_name11);
        $this->assign('realm_ip', $domain_name1);
        $call_back_url1 = $domain_name1 . '/wapapi/wchat/relateWeixin';
        $call_payback_url1 = $domain_name1 . '/wapapi/pay/wchatUrlBack/';
        $this->assign("call_payback_url1", $call_payback_url1);
        $this->assign("call_back_url1", $call_back_url1);
        $this->assign("public_url", $web_info['realm_ip'].'/upload/'.$this->website_id.'/');
        $this->assign("time", time());
        $this->assign('wchat_config', $wchat_config["value"]);
        $ip = getServerIp();
        $this->assign('ip', $ip);
        return view($this->style . 'Wchat/config');
    }

    /**
     * 修改微信配置
     *
     * @return unknown
     */
    public function setInstanceWchatConfig()
    {
        $config = new Config();
        $appid = str_replace(' ', '', request()->post('appid', ''));
        $appsecret = str_replace(' ', '', request()->post('appsecret', ''));
        $token = request()->post('token', '');
        $public_name = request()->post('public_name', '');
        $encodingAESKey = request()->post('encodingAESKey', '');
        $type = request()->post('type', '0');
        $res = $config->setInstanceWchatConfig($type, $appid, $appsecret, $token,$public_name,$encodingAESKey);
        $this->addUserLogByParam("修改微信配置", $res);
        return AjaxReturn($res);
    }

    /**
     * 微信菜单
     */
    public function menu()
    {
        $weixin = new Weixin();
        $menu_list = $weixin->getInstanceWchatMenu($this->instance_id);
        $default_menu_info = array(); // 默认显示菜单
        $menu_list_count = count($menu_list);
        $class_index = count($menu_list);
        if ($class_index > 0) {
            if ($class_index == MAX_MENU_LENGTH) {
                $class_index = MAX_MENU_LENGTH - 1;
            }
        }
        if ($menu_list_count > 0) {
            $default_menu_info = $menu_list[$menu_list_count - 1];
            $default_menu_info["key_name"] = '';
        } else {
            $default_menu_info["menu_name"] = "";
            $default_menu_info["menu_id"] = 0;
            $default_menu_info["child_count"] = 0;
            $default_menu_info["media_id"] = '';
            $default_menu_info["menu_event_url"] = "";
            $default_menu_info["menu_event_type"] = 0;
            $default_menu_info["key_name"] = '';
        }
        $media_detail = array();
        $menu_list = objToArr($menu_list);
        if ($menu_list[$menu_list_count-1]["media_id"] && $menu_list[$menu_list_count-1]["menu_event_type"]!=5) {
            // 查询素材消息
            $media_detail = $weixin->getWeixinMediaDetail($menu_list[$menu_list_count-1]["media_id"]);
            $media_detail["item_list_count"] = count($media_detail["items"]['data']);
        }elseif ($menu_list[$menu_list_count-1]["media_id"] && $menu_list[$menu_list_count-1]["menu_event_type"]==5){
            // 查询关键字
            $keys = $weixin->getWeixinKey($menu_list[$menu_list_count-1]["media_id"]);
            $default_menu_info["key_name"] = $keys["key"];
        } else {
            $media_detail["create_time"] = "";
            $media_detail["title"] = "";
            $media_detail["item_list_count"] = 0;
        }
        $default_menu_info["media_list"] = $media_detail;
        $website = new WebSiteModel();
        $mall_name = $website->getInfo(['website_id' => $this->website_id], 'mall_name')['mall_name'];
        $this->assign("mall_name", $mall_name);
        $this->assign("wx_name", $this->instance_name);
        $this->assign("menu_list", $menu_list);
        $this->assign("MAX_MENU_LENGTH", MAX_MENU_LENGTH); // 一级菜单数量
        $this->assign("MAX_SUB_MENU_LENGTH", MAX_SUB_MENU_LENGTH); // 二级菜单数量
        $this->assign("menu_list_count", $menu_list_count);
        $this->assign("default_menu_info", $default_menu_info);
        $this->assign("class_index", $class_index);
        if($this->miniprogramStatus){
            $weixin_auth_model = new WeixinAuthModel();
            $min_config = $weixin_auth_model->getInfo(['website_id'=>$this->website_id],'authorizer_appid');
            $this->assign("authorizer_appid", $min_config['authorizer_appid']);
        }
        return view($this->style . 'Wchat/menu');
    }

    /**
     * 更新菜单到微信,保存并发布
     */
    public function updateMenuToWeixin()
    {
        $weixin = new Weixin();
        $result = $weixin->updateInstanceMenuToWeixin($this->instance_id);
        $config = new Config();
        $auth_info = $config->getInstanceWchatConfig($this->instance_id, $this->website_id);
        if (!empty($auth_info)) {
            $wchat_auth = new WchatOauth($this->website_id);
            $res = $wchat_auth->menu_create($result);
            if (!empty($res)) {
                if ($res['errcode'] == 0) {
                    $retval = 1;
                } else {
                    $retval = $res['errcode'] .': '. $res['errmsg'];
                }
            } else {
                $retval = 0;
            }
        } else {
            $retval = "当前未配置微信授权";
        }
        $this->addUserLogByParam("更新菜单到微信", '');
        return AjaxReturn($retval);
    }

    /**
     * 添加微信自定义菜单
     * @return unknown
     */
    public function addWeixinMenu()
    {
        $menu = isset($_POST["menu"]) ? $_POST["menu"] : "";
        if (!empty($menu)) {
            $menu = $_POST["menu"];
            $weixin = new Weixin();
            $instance_id = $this->instance_id;
            $menu_name = $menu["menu_name"]; // 菜单名称
            $menu_name = base64_encode(iserializer($menu_name));
            $ico = ""; // 菜图标单
            $pid = $menu["pid"]; // 父级菜单（一级菜单）
            $menu_event_type = $menu["menu_event_type"];
            $menu_event_url = $menu["menu_event_url"]; // '菜单url',
            $media_id = $menu["media_id"]; // '图文消息ID',
            $sort = $menu["sort"]; // 排序
            $res = $weixin->addWeixinMenu($instance_id, $menu_name, $ico, $pid, $menu_event_type, $menu_event_url, $media_id, $sort);
            $this->addUserLogByParam("添加微信自定义菜单", $menu);
            return $res;
        }
        return -1;
    }

    /**
     * 修改微信自定义菜单
     *
     * @return unknown
     */
    public function updateWeixinMenu()
    {
        $menu = isset($_POST["menu"]) ? $_POST["menu"] : "";
        if (!empty($menu)) {
            $weixin = new Weixin();
            $instance_id = $this->instance_id;
            $menu_name = $menu["menu_name"]; // 菜单名称
            $menu_id = $menu["menu_id"];
            $ico = ""; // 菜图标单
            $pid = $menu["pid"]; // 父级菜单（一级菜单）
            $menu_event_type = $menu["menu_event_type"]; // '1普通url 2 图文素材 3 功能',
            $menu_event_url = $menu["menu_event_url"]; // '菜单url',
            $media_id = $menu["media_id"]; // '图文消息ID',
            $res = $weixin->updateWeixinMenu($menu_id, $instance_id, $menu_name, $ico, $pid, $menu_event_type, $menu_event_url, $media_id);
            $this->addUserLogByParam("修改微信自定义菜单", $menu);
            return $res;
        }
        return -1;
    }

    /**
     * 修改排序
     *
     * @return number
     */
    public function updateWeixinMenuSort()
    {
        $menu_id_arr = isset($_POST["menu_id_arr"]) ? $_POST["menu_id_arr"] : "";
        if (!empty($menu_id_arr)) {
            $weixin = new Weixin();
            $res = $weixin->updateWeixinMenuSort($menu_id_arr);
            $this->addUserLogByParam("修改排序", $menu_id_arr);
            return $res;
        }
        return -1;
    }

    /**
     * 修改微信菜单名称
     */
    public function updateWeixinMenuName()
    {
        $menu_name = isset($_POST["menu_name"]) ? $_POST["menu_name"] : "";
        $menu_id = isset($_POST["menu_id"]) ? $_POST["menu_id"] : "";
        if (!empty($menu_name)) {
            $weixin = new Weixin();
            $menu_name = base64_encode(iserializer($menu_name));
            $res = $weixin->updateWeixinMenuName($menu_id, $menu_name);
            $this->addUserLogByParam("修改微信菜单名称", $menu_name);
            return $res;
        }
        return -1;
    }

    /**
     * 修改跳转链接地址
     */
    public function updateWeixinMenuUrl()
    {
        $menu_event_url = isset($_POST["menu_event_url"]) ? $_POST["menu_event_url"] : "";
        $menu_id = isset($_POST["menu_id"]) ? $_POST["menu_id"] : "";
        $weixin = new Weixin();
        $res = $weixin->updateWeixinMenuUrl($menu_id, $menu_event_url);
        $this->addUserLogByParam("修改链接跳转地址", $menu_id);
        return $res;
    }
    /**
     * 修改跳转链接地址
     */
    public function updateWeixinMenuKey()
    {
        $reply_key_id = isset($_POST["reply_key_id"]) ? $_POST["reply_key_id"] : "";
        $menu_id = isset($_POST["menu_id"]) ? $_POST["menu_id"] : "";
        $weixin = new Weixin();
        $res = $weixin->updateWeixinMenuKey($menu_id, $reply_key_id);
        $this->addUserLogByParam("修改触发关键字", $menu_id);
        return $res;
    }
    /**
     * 修改跳转链接小程序
     */
    public function updateWeiXinMenuMiniprogram()
    {
        $menu_event_url = isset($_POST["menu_event_url"]) ? $_POST["menu_event_url"] : "";
        $appid = isset($_POST["appid"]) ? $_POST["appid"] : "";
        $menu_id = isset($_POST["menu_id"]) ? $_POST["menu_id"] : "";
        $weixin = new Weixin();
        $res = $weixin->updateWeiXinMenuMiniprogram($menu_id, $menu_event_url,$appid);
        $this->addUserLogByParam("修改小程序跳转地址", $menu_id);
        return AjaxReturn($res);
    }
    /**
     * 修改菜单类型，1：文本，2：单图文，3：多图文
     *
     * @return unknown|number
     */
    public function updateWeixinMenuEventType()
    {
        $menu_event_type = isset($_POST["menu_event_type"]) ? $_POST["menu_event_type"] : "";
        $menu_id = isset($_POST["menu_id"]) ? $_POST["menu_id"] : "";
        if (!empty($menu_event_type)) {
            $weixin = new Weixin();
            $res = $weixin->updateWeixinMenuEventType($menu_id, $menu_event_type);
            $this->addUserLogByParam("修改菜单类型", $menu_id);
            return $res;
        }
        return -1;
    }

    public function getMinConfig()
    {
        $weixin_auth_model = new WeixinAuthModel();
        $min_config = $weixin_auth_model->getInfo(['website_id'=>$this->website_id],'authorizer_appid');
        return $min_config['authorizer_appid'];
    }

    /**
     * 修改菜单图文消息
     *
     * @return unknown|number
     */
    public function updateWeiXinMenuMessage()
    {
        $menu_event_type = isset($_POST["menu_event_type"]) ? $_POST["menu_event_type"] : "";
        $menu_id = isset($_POST["menu_id"]) ? $_POST["menu_id"] : "";
        $media_id = isset($_POST["media_id"]) ? $_POST["media_id"] : "";
        if($menu_event_type==3){
            $content = isset($_POST['text_content']) ? $_POST['text_content'] : '';
            $weixin = new Weixin();
            $media_id = $weixin->addWeixinText($content);
        }
        if (!empty($menu_event_type) && !empty($menu_id)) {
            $weixin = new Weixin();
            $res = $weixin->updateWeiXinMenuMessage($menu_id, $media_id, $menu_event_type);
            $this->addUserLogByParam("修改菜单图文消息", $menu_id);
            return AjaxReturn($res,$media_id );
        }
        return AjaxReturn(-1);
    }

    /**
     * 删除微信自定义菜单
     *
     * @return unknown|number
     */
    public function deleteWeixinMenu()
    {
        $menu_id = isset($_POST["menu_id"]) ? $_POST["menu_id"] : "";
        if (!empty($menu_id)) {
            $weixin = new Weixin();
            $res = $weixin->deleteWeixinMenu($menu_id);
            $this->addUserLogByParam("删除微信自定义菜单", $menu_id);
            return AjaxReturn($res);
        }
        return AjaxReturn(-1);
    }
    /**
     * 获取关键字
     */
    public function getWeiXinKey()
    {
        $media_id = $_POST["media_id"];
        $menu_id = $_POST["menuid"];
        $weixin = new Weixin();
        $res = $weixin->getWeixinMenuDetail($menu_id);
        $key = new WeixinKeyReplayModel();
        $res['key_name'] = $key->getInfo(['id'=>$media_id],'key')['key'];
        $this->addUserLogByParam("获取关键字", $media_id);
        return $res;
    }
    /**
     * 获取图文素材
     */
    public function getWeiXinMediaDetail()
    {
        $media_id = $_POST["media_id"];
        $weixin = new Weixin();
        $res = $weixin->getWeiXinMediaDetail($media_id);
        $this->addUserLogByParam("获取图文素材", $media_id);
        return $res;
    }
    /**
     * 获取图文素材
     */
    public function getWeixinMenuidDetail()
    {
        $menuid = $_POST["menuid"];
        $weixin = new WeixinMenuModel();
        $res = $weixin->getInfo(['menu_id'=>$menuid]);
        $res['low_id'] = $weixin->getInfo(['pid'=>$menuid]);
        $this->addUserLogByParam("获取菜单信息", $menuid);
        return $res;
    }

    /**
     * 回复设置
     */
    public function replayConfig()
    {
        $weixin = new Weixin();
        $info = $weixin->getFollowReplayDetail([
            'website_id' => $this->website_id
        ]);
        $this->assign('info', $info);
        $default_info = $weixin->getDefaultReplayDetail([
            'website_id' => $this->website_id
        ]);
        $this->assign('default_info', $default_info);

        return view($this->style . 'Wchat/replayConfig');
    }

    /**
     * 添加 或 修改 关注时回复
     */
    public function addOrUpdateFollowReply()
    {
        $weixin = new Weixin();
        $id = isset($_POST['id']) ? $_POST['id'] : -1;
        $replay_media_id = isset($_POST['media_id']) ? $_POST['media_id'] : '';
        $replay_content_text = isset($_POST['content_text']) ? $_POST['content_text'] : '';
        if($replay_content_text){
            $replay_media_id = $weixin->addWeixinText($replay_content_text);
        }
        $res = -1;
        if ($id < 0) {
            $res = -1;
        } else
            if ($id == 0) {
                if ($replay_media_id) {
                    $res = $weixin->addFollowReplay($replay_media_id, 0);
                } else {
                    $res = -1;
                }
            } else
                if ($id > 0) {
                    if ($replay_media_id) {
                        $res = $weixin->updateFollowReplay($id, $replay_media_id, 0);
                    } else {
                        $res = -1;
                    }
                }
        $this->addUserLogByParam(" 添加或修改关注时回复", $replay_media_id);
        return AjaxReturn($res);
    }

    /**
     * 添加 或 修改 默认时回复
     */
    public function addOrUpdateDefaultReply()
    {
        $weixin = new Weixin();
        $id = isset($_POST['id']) ? $_POST['id'] : -1;
        $replay_media_id = isset($_POST['media_id']) ? $_POST['media_id'] : '';
        $replay_content_text = isset($_POST['content_text']) ? $_POST['content_text'] : '';
        if($replay_content_text){
            $replay_media_id = $weixin->addWeixinText($replay_content_text);
        }
        $res = -1;
        if ($id < 0) {
            $res = -1;
        } else
            if ($id == 0) {
                if ($replay_media_id) {
                    $res = $weixin->addDefaultReplay($replay_media_id, 0);
                } else {
                    $res = -1;
                }
            } else
                if ($id > 0) {
                    if ($replay_media_id) {
                        $res = $weixin->updateDefaultReplay($id, $replay_media_id, 0);
                    } else {
                        $res = -1;
                    }
                }
        $this->addUserLogByParam(" 添加或修改默认时回复", $replay_media_id);
        return AjaxReturn($res);
    }


    /**
     * 删除图文详情页列表
     */
    public function deleteWeixinMediaDetail()
    {
        $id = isset($_POST['id']) ? $_POST['id'] : "";
        $res = 0;
        if (!empty($id)) {
            $weixin = new Weixin();
            $this->addUserLogByParam(" 删除图文详情页列表", $id);
            $res = $weixin->deleteWeixinMediaDetail($id);
        }
        $this->addUserLogByParam(" 删除图文详情页列表", $id);
        return AjaxReturn($res);
    }

    /**
     *
     * 素材
     */
    public function materialMessage()
    {
        $type = isset($_GET['type']) ? $_GET['type'] : 0;
        $child_menu_list = array(
            array(
                'url' => "wchat/materialMessage",
                'menu_name' => "全部",
                "active" => $type == 0 ? 1 : 0
            ),
            array(
                'url' => "wchat/materialMessage?type=1",
                'menu_name' => "文本",
                "active" => $type == 1 ? 1 : 0
            ),
            array(
                'url' => "wchat/materialMessage?type=2",
                'menu_name' => "单图文",
                "active" => $type == 2 ? 1 : 0
            ),
            array(
                'url' => "wchat/materialMessage?type=3",
                'menu_name' => "多图文",
                "active" => $type == 3 ? 1 : 0
            )
        );
        if (request()->isAjax()) {
            $type = isset($_POST['type']) ? $_POST['type'] : 0;
            $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $page_size = request()->post("page_size", PAGESIZE);
            $weixin = new Weixin();
            $condition = array();
            if ($type != 0) {
                $condition['type'] = $type;
            }
            $condition['title'] = array(
                'like',
                '%' . $search_text . '%'
            );
            $condition['website_id'] = $this->website_id;
            $condition = array_filter($condition);
            $list = $weixin->getWeixinMediaList($page_index, $page_size, $condition, 'create_time desc');
            return $list;
        }
        $this->assign('type', $type);
        $this->assign('child_menu_list', $child_menu_list);
        return view($this->style . 'Wchat/materialMessage');
    }


    /**
     * 获取 图片、音频、视频（素材管理）
     */
    public function materialManagement()
    {
        if (request()->isAjax()) {
            $type = isset($_POST['type']) ? $_POST['type'] : 'news';
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $page_size = request()->post("page_size", PAGESIZE);
            $wchat = new WchatOauth($this->website_id);
            $count = $wchat->getMaterialCount();//获取语音、视频、图片、语音素材总数
            $key = $type . '_count';//不同素材的总数
            $total = $count[$key];
            if($total){
                $weixin = new Weixin();
                $condition['type'] = $type;
                $condition['website_id'] = $this->website_id;
                $condition = array_filter($condition);
                $list = $weixin->getWeixinMediaList($type, $page_index, $page_size, $condition, 'id asc');
                $list['total_count'] = $total;
                return $list;
            }else{
                $list['data'] = [];
                return $list;
            }

        }
        return view($this->style . 'Wchat/materialManagement');
    }

    /**
     * 获取 文本（素材管理）
     */
    public function materialManagements()
    {
        if (request()->isAjax()) {

            $type = isset($_POST['type']) ? $_POST['type'] : 'text';
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $page_size = request()->post("page_size", PAGESIZE);
            $weixin = new Weixin();
            $condition['type'] = $type;
            $condition['website_id'] = $this->website_id;
            $condition = array_filter($condition);
            $list = $weixin->getWeixinMediaList($type, $page_index, $page_size, $condition, 'id asc');
            return $list;
        }
        return view($this->style . 'Wchat/materialManagement');
    }

    /**
     * 删除 文本（素材管理）
     */
    public function delText()
    {
        if (request()->isAjax()) {
            $id = isset($_POST['id']) ? $_POST['id'] : '';
            $weixin = new Weixin();
            $res = $weixin->delWeixinText($id);
            return AjaxReturn($res);
        }
    }

    /**
     * 修改 文本（素材管理）
     */
    public function updateText()
    {
        if (request()->isAjax()) {
            $content = isset($_POST['content']) ? $_POST['content'] : '';
            $id = isset($_POST['id']) ? $_POST['id'] : '';
            $weixin = new Weixin();
            $res = $weixin->updateWeixinText($id, $content);
            return AjaxReturn($res);
        }
    }

    /**
     * 查看 文本（素材管理）
     */
    public function getText()
    {
        if (request()->isAjax()) {
            $id = isset($_POST['id']) ? $_POST['id'] : '';
            $weixin = new Weixin();
            $res = $weixin->getWeixinText($id);
            return $res;
        }
    }

    /**
     * 同步 图片、音频、视频（素材管理）
     */
    public function getMaterial()
    {
        $fail = array();
        $fail['code'] = 0;
        $type = isset($_POST['type']) ? $_POST['type'] : 'news';
        $weixin = new WchatOauth($this->website_id);
        $count = $weixin->getMaterialCount();//获取语音、视频、图片、语音素材总数
        $key = $type . '_count';//不同素材的总数
        $total = $count[$key];
        $pindex = max(1, intval($_POST['page_index']));
        $psize = PAGESIZE;
        $offset = ($pindex - 1) * $psize;
        $cache_key = "media_sync:{$this->website_id}";
        $has = Cache::get($cache_key);
        if (!is_array($has)) {
            $has = array(0);
        }
        $table1 = new WechatAttachmentModel();//图文消息表
        $table2 = new WechatNewsModel();//图片、语音、视频表
        if ($total == 0 || ($pindex > ceil($total / $psize))) {
            $has_str = implode(',', $has);
            $condition = ['website_id' => $this->website_id, 'id' => ['not in', $has_str], 'type' => $type];
            $table1->delData($condition);
            if ($type == 'news') {
                $condition1 = ['website_id' => $this->website_id, 'attach_id' => ['not in', $has_str]];
                $table2->delData($condition1);
            }
            Cache::rm($cache_key);
            $fail['message'] = '已同步到最新素材';
            $fail['code'] = 3;
            return ['fail' => $fail];
        }
        if ($pindex == ceil($total / $psize)) {
            $psize = $total % $psize == 0 ? PAGESIZE : ($total % $psize);
        }
        $data1 = array(
            'type' => $type,
            'offset' => $offset,
            'count' => $psize,
        );
        $result = $weixin->get_media($data1);//获取微信素材

        if ($result['errcode']) {
            $fail['message'] = '获取微信素材失败';
            return ['fail' => $fail];
        }
        $return['total_count'] = $result['total_count'];
        $return['item_count'] = $result['item_count'];
        $return['data'] = $result['item'];
        if($return['data']){
            foreach ($return['data'] as $data) {
            if ($type != 'news') {
                $table1 = new WechatAttachmentModel();
                $media = $table1->getInfo(['website_id' => $this->website_id, 'media_id' => $data['media_id']]);
//                $is_down = 0;
                $url = $tag = '';
                if ($type == 'image') {
                    if (!empty($media) && !empty($media['attachment'])) {
                        $has[] = $media['id'];
                        continue;
                    }
                    if ($data['url']) {
                        $url = $tag = $data['url'];
//                        $is_down = 1;
                    }
                } elseif ($type == 'voice') {
                    if (!empty($media) && !empty($media['attachment'])) {
                        $has[] = $media['id'];
                        continue;
                    }
                } elseif ($type == 'video') {
                    $stream = $weixin->getMaterial($data['media_id'], $type);
                    $tag = $url = '';
                    $tag = array(
                        'title' => $stream['title'],
                        'description' => $stream['description'],
                        'down_url' => $stream['down_url'],
                    );
                    $tag = $this->iserializer($tag);
                }
//                if(!$is_down) {
//                    $stream = $weixin->getMaterial($data['media_id'], $type);
//                    if($stream['errcode']) {
//                        $fail['message'] = $stream['message'];
//                        continue;
//                    }
//                    if($type == 'image' || $type == 'voice') {
//                        $path = $_SERVER['DOCUMENT_ROOT']."/".UPLOAD."/{$this->website_id}/material/{$type}";
//                        $this->mkdirs($path);
//                        $is_ok = file_put_contents($path."/{$data['media_id']}", $stream);
//                        if(!$is_ok) {
//                            $fail['message'] = '保存文件失败，请检查目录权限';
//                        }
//                        $tag = '';
//                        $url = "/".UPLOAD."/{$this->website_id}/material/{$type}/{$data['media_id']}";
//                    } elseif($type == 'video') {
//                        $tag = $url = '';
//                        $tag = array(
//                            'title' => $stream['title'],
//                            'description' => $stream['description'],
//                            'down_url' => $stream['down_url'],
//                        );
//                        $tag = $this->iserializer($tag);
//                    }
//                }

                $insert = array(
                    'website_id' => $this->website_id,
                    'filename' => $data['name'],
                    'uid' => $this->uid,
                    'attachment' => $url,
                    'media_id' => $data['media_id'],
                    'type' => $type,
                    'model' => 'perm',
                    'tag' => $tag,
                    'createtime' => $data['update_time']
                );
                if (empty($media)) {
                    $retval = $table1->save($insert);
                    $media['id'] = $retval;
                } else {
                    $table1->save($insert, ['website_id' => $this->website_id, 'media_id' => $data['media_id']]);
                    $media_id = $media['id'];
                }
                $has[] = $media['id'];
            } else {
                $this->addUserLogByParam(" 删除图文详情页列表",  $this->uid );
                $table1 = new WechatAttachmentModel();//图文消息表
                $table2 = new WechatNewsModel();//图片、语音、视频表
                $media = $table1->getInfo(['website_id' => $this->website_id, 'media_id' => $data['media_id']]);
                if (empty($media)) {
                    $insert = array(
                        'website_id' => $this->website_id,
                        'uid' => $this->uid,
                        'media_id' => $data['media_id'],
                        'type' => $type,
                        'model' => 'perm',
                        'createtime' => $data['update_time']
                    );
                    $insert_id = $table1->save($insert);
                } else {
                    $table1->save(['createtime' => $data['update_time']], ['website_id' => $this->website_id, 'media_id' => $data['media_id']]);
                    $insert_id = $media['id'];
                    $table2->delData(['website_id' => $this->website_id, 'attach_id' => $insert_id]);
                }
                $items = $data['content']['news_item'];
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $table2 = new WechatNewsModel();//图片、语音、视频表
                        $item['attach_id'] = $insert_id;
                        $item['website_id'] = $this->website_id;
                        $table2->save($item);
                    }
                }
                $has[] = $insert_id;
            }
        }
        Cache::set($cache_key, $has);
        }
        $result_data = ['total_count' => $total, 'fail' => $fail, 'item_count' => $result['item_count']];
        $this->addUserLogByParam("同步素材管理", $this->uid);
        return $result_data;
    }

    public function mkdirs($path)
    {
        if (!is_dir($path)) {
            $this->mkdirs(dirname($path));
            mkdir($path);
        }
        return is_dir($path);
    }

    //预览
    public function purview()
    {
        if (request()->isPost()) {
            $wxname = trim($_POST['wxname']);
            $type = trim($_POST['type']);
            $media_id = trim($_POST['media_id']);
            $weixin = new WchatOauth($this->website_id);
            $data = $weixin->fansSendPreview($wxname, $media_id, $type);
            return $data;
        } else {
            return view($this->style . 'Wchat/purview');
        }
    }


    public function strexists($string, $find)
    {
        return !(strpos($string, $find) === FALSE);
    }

    public function iserializer($value)
    {
        return serialize($value);
    }
    /**
     * 修改消息素材
     */
//    public function updateMedia()
//    {
//        $weixin = new Weixin();
//        if (request()->isAjax()) {
//            $media_id = isset($_POST['media_id']) ? $_POST['media_id'] : 0;
//            $type = isset($_POST['type']) ? $_POST['type'] : '';
//            $title = isset($_POST['title']) ? $_POST['title'] : '';
//            $content = isset($_POST['content']) ? $_POST['content'] : '';
//            $sort = 0;
//            $res = $weixin->updateWeixinMedia($media_id, $title, $this->instance_id, $type, $sort, $content);
//            return AjaxReturn($res);
//        }
//        $media_id = isset($_GET['media_id']) ? $_GET['media_id'] : 0;
//        $info = $weixin->getWeixinMediaDetail($media_id);
//        $this->assign('info', $info);
//        return view($this->style . 'Wchat/updateMedia');
//    }
    /**
     * 删除图文消息
     *
     * @return number
     */
    public function deleteWeixinMedia()
    {
        $id = isset($_POST['media_id']) ? $_POST['media_id'] : "";
        $type = isset($_POST['type']) ? $_POST['type'] : "";
        $weixin = new Weixin();
        $res = $weixin->deleteWeixinMedia($type, $id);
        $this->addUserLogByParam(" 删除图文消息", $id);
        return AjaxReturn($res);
    }

    /**
     * ajax 加载 选择素材 弹框数据
     */
    public function onLoadMaterial()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $this->assign('key_id', $id);
        return view($this->style . 'Wchat/materialDialog');
    }

    /**
     * 删除 回复
     *
     * @return unknown[]
     */
    public function delReply()
    {
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        if ($type == '') {
            return AjaxReturn(-1);
        } else {
            if ($type == 1) {
                // 删除 关注时回复
                $weixin = new Weixin();
                $res = $weixin->deleteFollowReplay($this->instance_id);
                return AjaxReturn($res);
            } else
                if ($type == 3) {
                    // 删除 默认时回复
                    $weixin = new Weixin();
                    $res = $weixin->deleteDefaultReplay($this->instance_id);
                    return AjaxReturn($res);
                }
            $this->addUserLogByParam("删除回复", $type);
        }
    }

    /**
     * 关键字 回复
     */
    public function keyReplayList()
    {
        $weixin = new Weixin();
        $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
        $rule_name = isset($_POST['key_name']) ? $_POST['key_name'] : '';
        if($rule_name){
            $condition['key|rule_name'] = [
                'like',
                '%' . $rule_name . '%'
            ];
            $condition['website_id'] =  $this->website_id;
            $list = $weixin->getKeyReplayList($page_index, PAGESIZE,$condition,'create_time DESC');
        }else{
            $list = $weixin->getKeyReplayList($page_index, PAGESIZE, [
                'instance_id' => $this->instance_id,
                'website_id' => $this->website_id
            ], 'create_time DESC');
        }
        return $list;
    }
    /**
     * 添加 或 修改 关键字 回复
     */
    public function addOrUpdateKeyReplay()
    {
        $weixin = new Weixin();
        if (request()->isPost()) {
            $id = isset($_POST['id']) ? $_POST['id'] : -1;
            $key = isset($_POST['key']) ? $_POST['key'] : '';
            $rule_name = isset($_POST['rule_name']) ? $_POST['rule_name'] : '';
            $match_type = isset($_POST['match_type']) ? $_POST['match_type'] : 1;
            $replay_media_id = isset($_POST['media_id']) ? $_POST['media_id'] : '';
            $replay_content_text = isset($_POST['content_text']) ? $_POST['content_text'] : '';
            $res = $weixin->checkKeys($id,$key);
            if($res<0){
                return AjaxReturn($res);
            }
            if($replay_content_text){
                $replay_media_id = $weixin->addWeixinText($replay_content_text);
            }
            $sort = 0;
            if ($id > 0) {
                $res = $weixin->updateKeyReplay($id, $key, $match_type, $replay_media_id, $sort,$rule_name);
            } else
                if ($id == 0) {
                    $res = $weixin->addKeyReplay($key, $match_type, $replay_media_id, $sort,$rule_name);
                } else
                    if ($id < 0) {
                        $res = -1;
                    }
            $this->addUserLogByParam("添加或修改关键字回复", $id);
            return AjaxReturn($res);
        }
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $this->assign('id', $id);
        $info = array(
            'key' => '',
            'match_type' => 1,
            'reply_media_id' => '',
            'media_info' => array()
        );
        if ($id > 0) {
            $info = $weixin->getKeyReplyDetail($id);
        }

        //$child_menu['menu_name'] = "编辑回复";
        $secend_menu['module_name'] = "编辑回复";
        $child_menu_list = array(
            array(
                'url' => "Wchat/addOrUpdateKeyReplay.html?id=" . $id,
                'menu_name' => "编辑回复",
                "active" => 1
            )
        );

        if (!empty($id)) {
            $this->assign("secend_menu", $secend_menu);
            $this->assign('child_menu_list', $child_menu_list);
        }

        $this->assign('info', $info);
        return view($this->style . 'Wchat/addOrUpdateKeyReplay');
    }

    public function getKeyReplayMedia()
    {
        $weixin = new Weixin();
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $info = $weixin->getKeyReplyDetail($id);
        return $info;
    }

    public function updateKeyReplayMedia()
    {
        $weixin = new Weixin();
        $media_id = isset($_POST['media_id']) ? $_POST['media_id'] : 0;
        $info = $weixin->getWeixinMediaDetail($media_id);
        return $info;
    }

    /**
     * 删除 回复
     *
     * @return unknown[]
     */
    public function delKeyReply()
    {
        $id = isset($_POST['id']) ? $_POST['id'] : '';
        if ($id == '') {
            return AjaxReturn(-1);
        } else {
            // 删除 关注时回复
            $weixin = new Weixin();
            $res = $weixin->deleteKeyReplay($id);
            $this->addUserLogByParam("删除回复", $id);
            return AjaxReturn($res);
        }
    }

    /**
     * 粉丝列表
     */
    public function fansList()
    {
        $weixin = new Weixin();
        if (request()->isAjax()) {
            $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $page_size = request()->post("page_size", PAGESIZE);
            $start_create_date = request()->post('start_create_date') == "" ? '2010-1-1' : request()->post('start_create_date');
            $end_create_date = request()->post('end_create_date') == "" ? '2038-1-1' : request()->post('end_create_date');
            $condition['nickname|openid'] = [
                'like',
                '%' . $search_text . '%'
            ];
            $condition['website_id'] = $this->website_id;
            $condition["subscribe_date"] = [
                [
                    ">",
                    strtotime($start_create_date)
                ],
                [
                    "<",
                    strtotime($end_create_date)
                ]
            ];
            $template_list = $weixin->getWeixinFansList($page_index, $page_size, $condition, 'subscribe_date desc');
            $template_list['group'] = $weixin->getWeixinFansGroupList(1, 0, ['website_id' => $this->website_id], 'group_id desc');
            $count = $weixin->getWeixinFansCount(['website_id' => $this->website_id]);
            $template_list['count'] = $count;
            return $template_list;
        }
        $count = $weixin->getWeixinFansCount(['website_id' => $this->website_id]);
        $this->assign("fans_count", $count);
        return view($this->style . 'Wchat/fansList');
    }

    /**
     * 粉丝分组
     */
    public function fansGroupList()
    {
        $weixin = new Weixin();
        if (request()->isAjax()) {
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $page_size = request()->post("page_size", PAGESIZE);
            $condition['website_id'] = $this->website_id;
            $template_list = $weixin->getWeixinFansGroupList($page_index, $page_size, $condition, 'group_id desc');
            return $template_list;
        }
        return view($this->style . 'Wchat/fansGroupList');
    }

    /**
     * 粉丝分组弹窗
     */
    public function groupList()
    {
        if (request()->isPost()) {
            $weixin = new Weixin();
            $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
            $page_size = request()->post("page_size", PAGESIZE);
            $condition['website_id'] = $this->website_id;
            $group_list = $weixin->getWeixinFansGroupList($page_index, $page_size, $condition, 'group_id desc');
            return $group_list;
        }
        return view($this->style . 'Wchat/selectGroup');
    }



    /**
     * 查看详情
     */
    public function fansDetail()
    {
        $weixin = new weixin;
        $openid = request()->get("openid", '');
        $fans_info = $weixin->getFansDetail($openid);
        $this->assign("list", $fans_info);
        return view($this->style . 'Wchat/fansDetail');
    }
    /**
     * 发送消息
     */
    public function serviceNews()
    {
        $weixin = new weixin;
        $openid = request()->get("openid", '');
        $fans_info = $weixin->getFansDetail($openid);
        $this->assign("fans_info", $fans_info);
        return view($this->style . 'Wchat/serviceNews');
    }
    /**
     * 加载聊天记录
     */
    public function loadNews()
    {
        $weixin = new weixin;
        $uid = request()->post("uid", '');
        $news_info = $weixin->getFansNews($uid);
        return $news_info;
    }
    /**
     * 发送客服消息
     */
    public function sendMsg()
    {
        $weixin = new weixin;
        $openid = request()->post("openid", '');
        $media_id = request()->post("media_id", '');
        return $weixin->sendFanMessage($openid, $media_id, $this->website_id);
    }
    /**
     * 群发
     */
    public function sendAll()
    {
        $weixin = new WchatOauth($this->website_id);
        $attachment = new WechatAttachmentModel();
        $groupid = request()->post("groupid", '');
        $media_id = request()->post("media_id", '');
        if ($groupid == -3) {//所有粉丝
            $type = $attachment->getInfo(['media_id'=>$media_id],'type')['type'];
            $res = $weixin->send_all_group_message($type,$media_id);
        }else if($groupid == -1){//黑名单
            $weixins = new weixin;
            $weixin_fans_group = new WeixinFansModel();
            $openids = $weixin_fans_group->Query(['groupid' => $groupid], 'openid');
            foreach($openids as $v){
                $res = $weixins->sendFanMessage($v, $media_id, $this->website_id);
            }
        }else if($groupid == -2){//未分组
            $weixins = new weixin;
            $weixin_fans_group = new WeixinFansModel();
            $openids = $weixin_fans_group->Query(['groupid' => $groupid], 'openid');
            $res = $weixins->sendFanMessage($openids, $media_id, $this->website_id);
        } else {
                $type = $attachment->getInfo(['media_id'=>$media_id],'type')['type'];
                $res = $weixin->send_group_message($groupid,$type,$media_id);
        }
        return $res;
    }

    /**
     * 添加粉丝分组
     */
    public function addFansGroupList()
    {
        $weixin = new Weixin();
        $group_name = request()->post("group_name", '');
        $res = $weixin->addWeixinFansGroup($group_name, $this->website_id);
        return AjaxReturn($res);
    }

    /**
     * 修改分组名称
     */
    public function updateGroupName()
    {
        $weixin = new Weixin();
        $group_name = request()->post("group_name", '');
        $group_id = request()->post("group_id", '');
        $res = $weixin->updateGroupName($group_id,$group_name,$this->website_id);
        return AjaxReturn($res);
    }

    /**
     * 修改粉丝分组
     */
    public function updateFansGroup()
    {
        $weixin = new Weixin();
        $group_id = request()->post("group_id", '');
        $default_group_id = request()->post("default_group_id", '');
        $fans_id = request()->post("fans_id", '');
        $openid = request()->post("openid", '');
        $res = $weixin->updateFansGroup($openid,$fans_id,$default_group_id,$group_id,$this->website_id);
        return AjaxReturn($res);
    }

    /**
     * 删除分组
     */
    public function delFansGroup()
    {
        $weixin = new Weixin();
        $id = request()->post("group_id", '');
        $res = $weixin->delWeixinFansGroup($id,$this->website_id);
        return AjaxReturn($res);
    }

    /**
     * 同步粉丝
     */
    public function uploadFans()
    {
        $weixin = new Weixin();
        $openid_all = $weixin->getWeixinOpenidList();
        $tag_all = $weixin->getWeixinTags();
        $backList = $weixin->getBackList();
        if ($openid_all['errcode'] == '0') {
            $count = count($openid_all['openid_list']);
            foreach ($openid_all['openid_list'] as $v) {
                $fan = new WeixinFansModel();
                $openid = $fan->getInfo(['website_id' => $this->website_id, 'openid' => $v],'openid')['openid'];
                if(!isset($openid)){
                    $fan->save(['website_id' => $this->website_id, 'openid' => $v]);
                }
            }
            $buffSize = ceil($count / 100);
            for ($i = 0; $i < $buffSize; $i++) {
                $openids = array_slice($openid_all['openid_list'], $i * 100, 100);
                $data = [];
                foreach ($openids as $v1) {
                    $data['user_list'][]['openid'] = $v1;
                }
                if($data){
                    $weixin->updateWchatFansList($data);
                }
            }
        }
        if($openid_all['errcode']==0){
            if ($tag_all) {
                $tag_info = $tag_all['tags'];
                if ($tag_info) {
                    foreach ($tag_info as $k => $v) {
                        $fan_group = new WeixinGroupModel();
                        $group_id = $fan_group->getInfo(['website_id'=>$this->website_id,'group_id' => $v['id']],'group_id')['group_id'];
                        if($group_id){
                            $fan_group->save(['group_name' => $v['name']],['website_id' => $this->website_id, 'group_id' => $v['id']]);
                        }else{
                            if($v['id']==2) {
                                $fan_group->save(['website_id' => $this->website_id, 'group_id' => $v['id'], 'group_name' => $v['name']]);
                            }else{
                                $fan_group->save(['website_id' => $this->website_id, 'group_id' => $v['id'],'from'=>2, 'group_name' => $v['name']]);
                            }
                        }
                    }
                }
            }
            if ($backList) {
                $fan_group = new WeixinGroupModel();
                $group_id_in = $fan_group->getInfo(['website_id'=>$this->website_id,'group_id' => -2],'group_id')['group_id'];
                $group_id = $fan_group->getInfo(['website_id'=>$this->website_id,'group_id' => -1],'group_id')['group_id'];
                if(empty($group_id)){
                    $fan_group = new WeixinGroupModel();
                    $fan_group->save(['website_id' => $this->website_id, 'group_id' => -1, 'group_name' => '黑名单']);
                }
                if(empty($group_id_in)){
                    $fan_group = new WeixinGroupModel();
                    $fan_group->save(['website_id' => $this->website_id, 'group_id' => -2, 'group_name' => '未分组']);
                }
                $black_info = $backList['data']['openid'];
                if ($black_info) {
                    foreach ($black_info as $k => $v) {
                        $fan = new WeixinFansModel();
                        $count = $fan->getInfo(['openid'=>$v],'*');
                        if($count){
                            $fan->save(['groupid' => -1],['website_id' => $this->website_id,'openid'=>$v]);
                        }
                    }
                }
            }
            return AjaxReturn(1);
        }else{
            return AjaxReturn(-1,$openid_all['errormsg']);
        }
    }

    /**
     * 模板消息列表
     *
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function messageTemplate()
    {
        $WeixinMessage = new WeixinMessage();
        $template_info = $WeixinMessage->getWxMsgTemplate($this->website_id);
        $this->assign('list',$template_info);
        return view($this->style . 'Wchat/templateNews');
    }
    public function addMessageTemplate()
    {
        if (request()->isPost()) {
            $WeixinMessage = new WeixinMessage();
            $type = request()->post("type", '');
            $template_id = request()->post("template_id", '');
            $is_use = request()->post("is_use", '');
            $inform = request()->post("inform", '');
            $res = $WeixinMessage->addWxMsgTemplate($this->website_id,$type,$template_id,$is_use,$inform);
            return  AjaxReturn($res);
        }
    }
}
