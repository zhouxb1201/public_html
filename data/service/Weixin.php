<?php
namespace data\service;

use addons\poster\model\PosterModel;
use addons\taskcenter\model\VslGeneralPosterModel;
use data\extend\WchatOauth;
use data\model\AlbumPictureModel;
use data\model\UserModel;
use addons\miniprogram\model\WeixinAuthModel;
use data\model\WeixinGroupModel;
use data\model\WeixinDefaultReplayModel;
use data\model\WeixinFansModel;
use data\model\WeixinFollowReplayModel;
use data\model\WeixinKeyReplayModel;
use data\model\WeixinMediaItemModel;
use data\model\WeixinMediaModel;
use data\model\WeixinMenuModel;
use data\model\WeixinOneKeySubscribeModel;
use data\model\WeixinQrcodeConfigModel;
use data\model\WeixinQrcodeTemplateModel;
use data\model\WeixinUserMsgModel;
use data\model\WeixinUserMsgReplayModel;
use data\service\BaseService;
use think\Log;
use data\model\WechatAttachmentModel;
use data\model\WechatNewsModel;
use think\Db;
class Weixin extends BaseService
{

    /**
     * (non-PHPdoc)
     */
    public function getWeixinMenuList($instance_id, $pid = '')
    {
        $weixin_menu = new WeixinMenuModel();
        if ($pid == '') {
            $list = $weixin_menu->pageQuery(1, 0, [
                'instance_id' => $instance_id,
                'website_id' => $this->website_id
            ], 'sort', '*');
        } else {
            $list = $weixin_menu->pageQuery(1, 0, [
                'instance_id' => $instance_id,
                'website_id' => $this->website_id,
                'pid' => $pid
            ], 'sort', '*');
        }
        return $list['data'];
        // TODO Auto-generated method stub
    }

    /**
     * (non-PHPdoc)
     */
    public function addWeixinMenu($instance_id, $menu_name, $ico, $pid, $menu_event_type, $menu_event_url, $media_id, $sort)
    {
        $weixin_menus = new WeixinMenuModel();
        if($pid){
           $weixin_menus->save(['menu_event_type'=>2,'menu_event_url' =>'','media_id' =>0],['menu_id'=>$pid]);
        }
        $data = array(
            'instance_id' => $instance_id,
            'website_id' => $this->website_id,
            'menu_name' => $menu_name,
            'ico' => $ico,
            'pid' => $pid,
            'menu_event_type' => $menu_event_type,
            'menu_event_url' => $menu_event_url,
            'media_id' => $media_id,
            'sort' => $sort,
            'create_date' => time()
        );
        $weixin_menu = new WeixinMenuModel();
        $weixin_menu->save($data);
        return $weixin_menu->menu_id;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     */
    public function updateWeixinMenu($menu_id, $instance_id, $menu_name, $ico, $pid, $menu_event_type, $menu_event_url, $media_id)
    {
        $weixin_menu = new WeixinMenuModel();
        $data = array(
            'instance_id' => $instance_id,
            'website_id' => $this->website_id,
            'menu_name' => $menu_name,
            'ico' => $ico,
            'pid' => $pid,
            'menu_event_type' => $menu_event_type,
            'menu_event_url' => $menu_event_url,
            'media_id' => $media_id,
            'modify_date' => time()
        );
        $retval = $weixin_menu->save($data, [
            "menu_id" => $menu_id
        ]);
        return $retval;
        // TODO Auto-generated method stub
    }

    /**
     * 修改菜单排序
     *
     * {@inheritdoc}
     *
     */
    public function updateWeixinMenuSort($menu_id_arr)
    {
        $weixin_menu = new WeixinMenuModel();
        $retval = 0;
        foreach ($menu_id_arr as $k => $v) {
            $data = array(
                'sort' => $k + 1,
                'modify_date' => time()
            );
            $retval += $weixin_menu->save($data, [
                "menu_id" => $v
            ]);
        }
        return $retval;
    }

    /**
     * 修改菜单名称，目前用的是updateWeixinMenu，还没有单独修改
     *
     * {@inheritdoc}
     *
     */
    public function updateWeixinMenuName($menu_id, $menu_name)
    {
        $weixin_menu = new WeixinMenuModel();
        
        $retval = $weixin_menu->save([
            "menu_name" => $menu_name
        ], [
            "menu_id" => $menu_id
        ]);
        return $retval;
    }

    /**
     * 修改跳转链接地址
     *
     * {@inheritdoc}
     *
     */
    public function updateWeixinMenuUrl($menu_id, $menu_event_url)
    {
        $weixin_menu = new WeixinMenuModel();
        
        $retval = $weixin_menu->save([
            "menu_event_url" => $menu_event_url,
            "menu_event_type" => 1
        ], [
            "menu_id" => $menu_id
        ]);
        return $retval;
    }
    public function updateWeixinMenuKey($menu_id, $reply_key_id)
    {
        $weixin_menu = new WeixinMenuModel();

        $retval = $weixin_menu->save([
            "media_id" => $reply_key_id,
            "menu_event_url"=>'',
            "menu_event_type" => 5
        ], [
            "menu_id" => $menu_id
        ]);
        return $retval;
    }
    public function updateWeiXinMenuMiniprogram($menu_id, $menu_event_url,$appid)
    {
        $weixin_menu = new WeixinMenuModel();

        $retval = $weixin_menu->save([
            "menu_event_url" => $menu_event_url,
            "appid"=>$appid,
            "media_id"=>0,
            "menu_event_type" => 4
        ], [
            "menu_id" => $menu_id
        ]);
        return $retval;
    }
    /**
     * 修改菜单类型，1：文本，2：单图文，3：多图文
     *
     * {@inheritdoc}
     *
     */
    public function updateWeixinMenuEventType($menu_id, $menu_event_type)
    {
        $weixin_menu = new WeixinMenuModel();
        if($menu_event_type==2){
            $retval = $weixin_menu->save([
                "menu_event_type" => $menu_event_type,
                "menu_event_url"=>''
            ], [
                "menu_id" => $menu_id
            ]);
        }else{
            $retval = $weixin_menu->save([
                "menu_event_type" => $menu_event_type
            ], [
                "menu_id" => $menu_id
            ]);
        }

        return $retval;
    }

    /**
     * 修改图文消息
     *
     * {@inheritdoc}
     *
     */
    public function updateWeiXinMenuMessage($menu_id, $media_id, $menu_event_type)
    {
        $weixin_menu = new WeixinMenuModel();
        $retval = $weixin_menu->save([
            "media_id" => $media_id,
            "menu_event_type" => $menu_event_type
        ], [
            "menu_id" => $menu_id
        ]);
        return $retval;
    }

    /*
     * (non-PHPdoc)
     */
    public function addMenuHits($menu_id)
    {
        
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     */
    public function getWeixinMenuDetail($menu_id,$website_id=0)
    {
        if(empty($website_id)){
            $website_id = $this->website_id;
        }
        $weixin_menu = new WeixinMenuModel();
        $data = $weixin_menu->getInfo(['menu_id'=>$menu_id,'website_id'=>$website_id]);
        return $data;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     */
    public function addWeixinGroup($instance_id, $authorizer_appid, $authorizer_refresh_token, $authorizer_access_token, $func_info, $nick_name, $head_img, $user_name, $alias, $qrcode_url)
    {
        $weixin_group = new WeixinGroupModel();
        $data = array(
            'instance_id' => $instance_id,
            'website_id' => $this->website_id,
            'authorizer_appid' => $authorizer_appid,
            'authorizer_refresh_token' => $authorizer_refresh_token,
            'authorizer_access_token' => $authorizer_access_token,
            'func_info' => $func_info,
            'nick_name' => $nick_name,
            'head_img' => $head_img,
            'user_name' => $user_name,
            'alias' => $alias,
            'qrcode_url' => $qrcode_url,
            'auth_time' => time()
        );
        $count = $weixin_group->where([
            'instance_id' => $instance_id,
            'website_id' => $this->website_id,
        ])->count();
        if ($count == 0) {
            $weixin_group = new WeixinGroupModel();
            $retval = $weixin_group->save($data);
        } else {
            $weixin_group = new WeixinGroupModel();
            $retval = $weixin_group->save($data, [
                'instance_id' => $instance_id,
                'website_id' => $this->website_id,
            ]);
        }
        
        return $retval;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     */
    public function addWeixinFans(array $data)
    {
        $weixin_fans = new WeixinFansModel();
        if(!$data['groupid']){
            $data['groupid'] = -2;
        }
        $info = $weixin_fans->getInfo(['openid' => $data['openid'], 'website_id' => $data['website_id']]);
        if ($info) {
            // 防止海报推荐奖励被重复领取
//            unset($data['source_uid'], $data['scene'], $data['scene_id']);
            $retval = $weixin_fans->save($data, [
                'openid' => $data['openid'],
                'website_id' =>$data['website_id']
            ]);
        } else {
            $retval = $weixin_fans->save($data);
        }
        return $retval;
    }

    /*
     * (non-PHPdoc)
     */
    public function addFollowReplay($replay_media_id, $sort)
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        $data = array(
            'website_id' => $this->website_id,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'create_time' => time()
        );
        $weixin_follow_replay->save($data);
        return $weixin_follow_replay->id;
        // TODO Auto-generated method stub
    }

    public function addDefaultReplay($replay_media_id, $sort)
    {

        $weixin_default_replay = new WeixinDefaultReplayModel();
        $data = array(
            'website_id' => $this->website_id,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'create_time' => time()
        );
        $weixin_default_replay->save($data);
        return $weixin_default_replay->id;
    }

    /*
     * (non-PHPdoc)
     */
    public function updateFollowReplay($id, $replay_media_id, $sort)
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        $data = array(
            'website_id' => $this->website_id,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'modify_time' => time()
        );
        $retval = $weixin_follow_replay->save($data, [
            'id' => $id
        ]);
        return $retval;
        // TODO Auto-generated method stub
    }

    public function updateDefaultReplay($id, $replay_media_id, $sort)
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $data = array(
            'website_id' => $this->website_id,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'modify_time' => time()
        );
        $retval = $weixin_default_replay->save($data, [
            'id' => $id
        ]);
        return $retval;
    }

    /*
     * (non-PHPdoc)
     */
    public function addKeyReplay($key, $match_type, $replay_media_id, $sort,$rule_name)
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        $data = array(
            'website_id' => $this->website_id,
            'key' => $key,
            'rule_name' => $rule_name,
            'match_type' => $match_type,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'create_time' => time()
        );
        $res =  $weixin_key_replay->save($data);
        return $res;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     */
    public function updateKeyReplay($id,$key,$match_type,$replay_media_id,  $sort,$rule_name)
    {

        $weixin_key_replay = new WeixinKeyReplayModel();
        $data = array(
            'website_id' => $this->website_id,
            'reply_media_id' => $replay_media_id,
            'key' => $key,
            'rule_name' => $rule_name,
            'match_type' => $match_type,
            'sort' => $sort,
            'create_time' => time()
        );
        $retval = $weixin_key_replay->save($data, [
            'id' => $id
        ]);
        return $retval;
        // TODO Auto-generated method stub
    }
    public function updateKeyReplayMedia($id,$media_id)
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        $data = array(
            'reply_media_id' => $media_id,
        );
        $retval = $weixin_key_replay->save($data, [
            'id' => $id
        ]);
        return $retval;
    }
    /*
     * (non-PHPdoc)
     */
    public function getKeyReplayList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        $list = $weixin_key_replay->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     */
    public function getFollowReplayList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        $list = $weixin_follow_replay->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
        // TODO Auto-generated method stub
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function getDefaultReplayList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $list = $weixin_default_replay->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
    }
    public function sendFanMessage($openid,$media_id,$website_id)
    {
        try{
            $news = new WechatAttachmentModel();
            $user = new UserModel();
            $uid  = $user->getInfo(['wx_openid'=>"$openid", 'website_id'=>$website_id],'uid')['uid'];
            $user_replay = new WeixinUserMsgModel();
            $msg_id = $user_replay->getMax(['website_id'=>$website_id,'uid'=>$uid],'msg_id');
            $WchatOauth = new WchatOauth($website_id);
            $media_info = $news->getInfo(['media_id'=>$media_id],'*');
            if($media_info){
                $msgtype = $media_info['type'];
                if($msgtype=='text'){
                    $content = $media_info['attachment'];
                }else{
                    $content = $media_id;
                }
            }else{
                $msgtype ='text';
                $content = $media_id;
            }
            $res = $WchatOauth->send_message("$openid", $msgtype, $content);
            if($res['errcode']==0){
                $custom_replay = new WeixinUserMsgReplayModel();
                $custom_replay->save(['msg_id'=>$msg_id,'replay_uid'=>$uid,'replay_type'=>$msgtype,'content'=>$media_id,'replay_time'=>time(),'website_id'=>$website_id]);
                $user_replay->save(['is_replay'=>1],['msg_id'=>$msg_id]);
            }
            return $res;
        }catch(\Exception $e){

        }

        // TODO Auto-generated method stub
    }
    /*
     * (non-PHPdoc)
     */
    public function getWeixinFansList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $weixin_fans = new WeixinFansModel();
        $weixin_fans_group = new WeixinGroupModel();
        $list = $weixin_fans->pageQuery($page_index, $page_size, $condition, $order, '*');
        foreach ($list ['data'] as $k => $v) {
            $list['data'][$k]['subscribe_date'] = date('Y-m-d H:i:s', $v['subscribe_date']);
            $group_name = $weixin_fans_group->getInfo([ 'group_id' => $list['data'][$k]['groupid'], 'website_id' => $this->website_id ], 'group_name')['group_name'];
            $list['data'][$k]['group_name'] = $group_name;
        }
        return $list;
        // TODO Auto-generated method stub
    }
    public function getFansDetail($openid)
    {
        $weixin_fans = new WeixinFansModel();
        $weixin_fans_group = new WeixinGroupModel();
        $list = $weixin_fans->getInfo(['openid'=>$openid],'*');
        $list['subscribe_date'] = date('Y-m-d H:i:s', $list['subscribe_date']);
        $user = new UserModel();
        $uid = $user->getInfo(['wx_openid'=>$openid],'uid')['uid'];
        $list['uid'] = $uid;
        $group_name = $weixin_fans_group->getInfo(['group_id'=>$list['groupid']],'group_name')['group_name'];
        if($group_name){
            $list['group_name'] = $group_name;
        }
        return $list;
        // TODO Auto-generated method stub
    }
    public function getFansNews($uid)
    {
        $custom_replay = new WeixinUserMsgReplayModel();
        $user_replay = new WeixinUserMsgModel();
        $user_replay_list = $user_replay->pageQuery(1,0,['uid'=>$uid], 'create_time asc', '*');
        $str = '';
        $user = new UserModel();
        $user_headimg = $user->getInfo(['uid'=>$uid],'user_headimg')['user_headimg'];
        $start_time = $user_replay->getInfo(['uid'=>$uid],'create_time')['create_time'];
        $custom_replay_info1 = $custom_replay->pageQuery(1,0,['replay_uid'=>$uid,'replay_time'=>['<=',$start_time]], 'replay_time asc','*');
        if($custom_replay_info1['data']) {
            foreach ($custom_replay_info1['data'] as $key2=>$row2) {
                $weixin_attachment = new WechatAttachmentModel();
                $weixin_news = new WechatNewsModel();
                $row1= objToArr($row2);
                $row2['replay_time'] = date('Y-m-d H:i:s',$row2['replay_time']);
                $news = $weixin_attachment->getInfo(['media_id'=>$row2['content']],'*');
                $attach_id = $weixin_attachment->getInfo(['media_id'=>$row2['content']],'id')['id'];
                if($news){
                    $row2['news'] = $news;
                    if($row2['replay_type'] == 'video') {
                        $row2['news']['tag'] = $this->iunserializer($news['tag']);
                    }elseif($row2['replay_type'] == 'news') {
                        $row2['news']['items'] = $weixin_news->getInfo(['website_id'=>$this->website_id,'attach_id'=>$attach_id],'*');
                    }elseif($row2['replay_type'] == 'text') {
                        $row2['news'] = $news['attachment'];
                    }
                }else{
                    $row2['news'] = $row2['content'];
                }
                $str .= '<div class="pull-left col-lg-12 col-md-12 col-sm-12 col-xs-12">';
                $str .= '<div class="pull-right"><img src="/public/platform/images/wxMenu/gw-wx.gif" width="35" style="border:2px solid #418BCA;border-radius:5px"><br></div>';
                if ($row2['replay_type'] == 'video') {
                    $str .= '<div class="alert alert-info pull-right infor">视频：' . $row2['news']['tag']['title'] . '<br>' . $row2['replay_time'] . '</div>';
                } else if ($row2['replay_type'] == 'news') {
                    $str .= '<div class="alert alert-info pull-right infor"><a target="_blank" href="' . $row2['news']['items']['url'] . '" >图文消息：' . $row2['news']['items']['title'] . '<br>' . $row1['replay_time'] . '</a></div>';
                } else if ($row2['replay_type'] == 'image') {
                    $str .= '<div class="alert alert-info pull-right infor">图片：' . $row2['news']['filename'] . '<br>' . $row2['replay_time'] . '</div>';
                } else if ($row2['replay_type'] == 'voice') {
                    $str .= '<div class="alert alert-info pull-right infor">语音：' . $row2['news']['filename'] . '<br>' . $row2['replay_time'] . '</div>';
                } else if ($row2['replay_type'] == 'text') {
                    $str .= '<div class="alert alert-info pull-right infor">文本：' . $row2['news'] . '<br>' . $row2['replay_time'] . '</div>';
                }
                $str .= '<div style="clear:both"></div>';
                $str .= '</div>';
                $str .= '<div style="clear:both"></div>';
            }
        }
        if($user_replay_list['data']){
            foreach($user_replay_list['data'] as $key=>$row){
                $row= objToArr($row);
                $row['create_time'] = date('Y-m-d H:i:s',$row['create_time']);
                $custom_replay_info = $custom_replay->pageQuery(1,0,['replay_uid'=>$uid,'msg_id'=>$row['msg_id']], 'replay_time asc','*');
                $weixin_attachment = new WechatAttachmentModel();
                $weixin_news = new WechatNewsModel();
                $str .='<div class="pull-left col-lg-12 col-md-12 col-sm-12 col-xs-12">';
                $str .='<div class="pull-left"><img src="'.$user_headimg.'" width="35"><br></div>';
                $str .='<div class="alert alert-info pull-left infol">'.$row['content'].'<br>'.$row['create_time'].'</div>';
                $str .='<div style="clear:both"></div>';
                $str .='</div>';
                $str .='<div style="clear:both"></div>';
                if($custom_replay_info['data']) {
                    foreach ($custom_replay_info['data'] as $key1=>$row1) {
                        $row1= objToArr($row1);
                        $row1['replay_time'] = date('Y-m-d H:i:s',$row1['replay_time']);
                        $news = $weixin_attachment->getInfo(['media_id'=>$row1['content']],'*');
                        $attach_id = $weixin_attachment->getInfo(['media_id'=>$row1['content']],'id')['id'];
                        if($news){
                            $row1['news'] = $news;
                            if($row1['replay_type'] == 'video') {
                                $row1['news']['tag'] = $this->iunserializer($news['tag']);
                            }elseif($row1['replay_type'] == 'news') {
                                $row1['news']['items'] = $weixin_news->getInfo(['website_id'=>$this->website_id,'attach_id'=>$attach_id],'*');
                            }elseif($row1['replay_type'] == 'text') {
                                $row1['news'] = $news['attachment'];
                            }
                        }else{
                            $row1['news'] = $row1['content'];
                        }
                        $str .= '<div class="pull-left col-lg-12 col-md-12 col-sm-12 col-xs-12">';
                        $str .= '<div class="pull-right"><img src="/public/platform/images/wxMenu/gw-wx.gif" width="35" style="border:2px solid #418BCA;border-radius:5px"><br></div>';
                        if ($row1['replay_type'] == 'video') {
                            $str .= '<div class="alert alert-info pull-right infor">视频：' . $row1['news']['tag']['title'] . '<br>' . $row1['replay_time'] . '</div>';
                        } else if ($row1['replay_type'] == 'news') {
                            $str .= '<div class="alert alert-info pull-right infor"><a target="_blank" href="' . $row1['news']['items']['url'] . '" >图文消息：' . $row1['news']['items']['title'] . '<br>' . $row1['replay_time'] . '</a></div>';
                        } else if ($row1['replay_type'] == 'image') {
                            $str .= '<div class="alert alert-info pull-right infor">图片：' . $row1['news']['filename'] . '<br>' . $row1['replay_time'] . '</div>';
                        } else if ($row1['replay_type'] == 'voice') {
                            $str .= '<div class="alert alert-info pull-right infor">语音：' . $row1['news']['filename'] . '<br>' . $row1['replay_time'] . '</div>';
                        } else if ($row1['replay_type'] == 'text') {
                            $str .= '<div class="alert alert-info pull-right infor">文本：' . $row1['news'] . '<br>' . $row1['replay_time'] . '</div>';
                        }
                        $str .= '<div style="clear:both"></div>';
                        $str .= '</div>';
                        $str .= '<div style="clear:both"></div>';
                    }
                }
            }
        }
        return $str;
        // TODO Auto-generated method stub
    }
    public function getWeixinFansGroupList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $weixin_fans_group = new WeixinGroupModel();
        $weixin_fans = new WeixinFansModel();
        $list = $weixin_fans_group->pageQuery($page_index, $page_size, $condition, $order, '*');
        foreach ($list ['data'] as $k => $v) {
            $list['data'][$k]['count'] =  $weixin_fans->getCount(['groupid'=>$v['group_id']]);
        }
        return $list;
        // TODO Auto-generated method stub
    }
    public function addWeixinFansGroup($group_name,$website_id)
    {
        $weixin_fans_group = new WeixinGroupModel();
        $WchatOauth = new WchatOauth($website_id);
        $groups_info = $WchatOauth->add_group_weixin($group_name);
        if($groups_info['tag']){
            $res = $weixin_fans_group->save(['group_name'=>$group_name,'website_id'=>$website_id,'group_id'=>$groups_info['tag']['id'],'from'=>2]);
        }else{
            $res=-1;
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function updateGroupName($group_id,$group_name,$website_id)
    {
        $weixin_fans_group = new WeixinGroupModel();
        $WchatOauth = new WchatOauth($website_id);
        $data = $WchatOauth->update_group_weixin($group_id,$group_name);
        if($data['errcode']==0){
            $res = $weixin_fans_group->save(['group_name'=>$group_name],['group_id'=>$group_id,'from'=>2,'website_id'=>$website_id]);
        }else{
            $res = -1;
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function updateFansGroup($openid,$fans_id,$default_group_id,$group_id,$website_id)
    {
        $weixin_fans = new WeixinFansModel();
        $WchatOauth = new WchatOauth($website_id);
        $code = $WchatOauth->update_fans_group($openid,$group_id,$default_group_id);
        if($code['errcode']==0){
            $res = $weixin_fans->save(['groupid'=>$group_id],['fans_id'=>$fans_id]);
        }else{
            $res = -1;
        }
        return $res;
        // TODO Auto-generated method stub
    }
    public function delWeixinFansGroup($id,$website_id)
    {
        $weixin_fans_group = new WeixinGroupModel();
        $weixin_fans = new WeixinFansModel();
        $group_ids = $weixin_fans->Query(['groupid'=>$id],'groupid');
        if(empty($group_ids)){
            $WchatOauth = new WchatOauth($website_id);
            $data = $WchatOauth->del_group_weixin($id);
            if($data['errcode']==0){
                $res = $weixin_fans_group->delData(['group_id'=>$id]);
            }else{
                $res =-1;
            }
            return $res;
        }else{
            return -2;
        }

        // TODO Auto-generated method stub
    }
    /*
     * (non-PHPdoc)
     */
    public function getBackList()
    {
        $weixin_auth = new WchatOauth($this->website_id);
        $list = $weixin_auth->get_black_list();
        return $list;
        // TODO Auto-generated method stub
    }
    public function getWeixinTags()
    {
        $weixin_auth = new WchatOauth($this->website_id);
        $list = $weixin_auth->get_tags_info();
        return $list;
        // TODO Auto-generated method stub
    }
    public function addWeixinText($content)
    {
        $weixin_media = new WechatAttachmentModel();
        $weixin_media->startTrans();
        try {
            $data_media = array(
                'filename' => '',
                'attachment'=>$content,
                'uid' => $this->uid,
                'media_id' => '',
                'width'=>0,
                'height'=>0,
                'website_id' => $this->website_id,
                'type' => 'text',
                'model'=>'',
                'tag' => '',
                'createtime' => time()
            );
            $res = $weixin_media->save($data_media);
            $weixin_media->save(['media_id'=>$res],['id'=>$res]);
            $weixin_media->commit();
            return $res;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $weixin_media->rollback();
            return $e->getMessage();
        }
        // TODO Auto-generated method stub
    }
    public function updateWeixinText($id,$content)
    {
        $weixin_media = new WechatAttachmentModel();
        $data_media = array(
            'attachment'=>$content,
        );
        $res = $weixin_media->save($data_media,['id'=>$id]);
        return $res;
    }
    public function getWeixinText($id)
    {
        $weixin_media = new WechatAttachmentModel();
        $res = $weixin_media->getInfo(['id'=>$id],'*');
        return $res;
    }
    public function delWeixinText($id)
    {
        $weixin_media = new WechatAttachmentModel();
        $res = $weixin_media->delData(['id'=>$id]);
        return $res;

        // TODO Auto-generated method stub
    }
    /*
     * (non-PHPdoc)
     * $content格式 = 标题,作者,封面图片,显示在正文,摘要,正文,链接地址;标题,作者,封面图片,显示在正文,摘要,正文,链接地址
     */
    public function addWeixinMedia($title, $instance_id, $type, $sort, $content)
    {
        $weixin_media = new WeixinMediaModel();
        $weixin_media->startTrans();
        try {
            $data_media = array(
                'title' => $title,
                'instance_id' => $instance_id,
                'website_id' => $this->website_id,
                'type' => $type,
                'sort' => $sort,
                'create_time' => time()
            );
            $weixin_media->save($data_media);
            $media_id = $weixin_media->media_id;
            if ($type == 1) {
                $this->addWeixinMediaItem($media_id, $title, '', '', '', '', '', '', 0);
            } else 
                if ($type == 2) {
                    $info = explode('`|`', $content);
                    $this->addWeixinMediaItem($media_id, $info[0], $info[1], $info[2], $info[3], $info[4], $info[5], $info[6], 0);
                } else 
                    if ($type == 3) {
                        $list = explode('`$`', $content);
                        foreach ($list as $k => $v) {
                            $arr = Array();
                            $arr = explode('`|`', $v);
                            $this->addWeixinMediaItem($media_id, $arr[0], $arr[1], $arr[2], $arr[3], $arr[4], $arr[5], $arr[6], 0);
                        }
                    }
            $weixin_media->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $weixin_media->rollback();
            return $e->getMessage();
        }
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     */
    public function addWeixinMediaItem($media_id, $title, $author, $cover, $show_cover_pic, $summary, $content, $content_source_url, $sort)
    {
        $weixin_media_item = new WeixinMediaItemModel();
        $data = array(
            'media_id' => $media_id,
            'title' => $title,
            'author' => $author,
            'thumb_media_id' => $cover,
            'show_cover_pic' => $show_cover_pic,
            'digest' => $summary,
            'content' => $content,
            'content_source_url' => $content_source_url,
        );
        $weixin = new WchatOauth($this->website_id);
        $real_media_id = $weixin->addMatrialNews($data);
        if($real_media_id){
            $data = array(
                'real_media_id'=>$real_media_id,
                'media_id' => $media_id,
                'title' => $title,
                'author' => $author,
                'cover' => $cover,
                'show_cover_pic' => $show_cover_pic,
                'summary' => $summary,
                'content' => $content,
                'content_source_url' => $content_source_url,
                'sort' => $sort
            );

            $retval = $weixin_media_item->save($data);
            return $retval;
        }

        
        // TODO Auto-generated method stub
    }

    /*
     * (non-PHPdoc)
     */
    public function getWeixinMediaList($type,$page_index = 1, $page_size = 0, $condition = '', $order = '')
    {

            $weixin_media = new WechatAttachmentModel();
            $weixin_news = new WechatNewsModel();
            $list = $weixin_media->pageQuery($page_index, $page_size, $condition, $order, '*');
            foreach($list['data'] as $key=>$row) {
                $row['createtime'] = date('Y-m-d H:i:s',$row['createtime']);
                if($type == 'video') {
                    $row['tag'] = $this->iunserializer($row['tag']);
                }elseif($type == 'news') {
                    $row['items'] = $weixin_news->pageQuery(1, $page_size, ['website_id'=>$this->website_id,'attach_id'=>$row['id']], $order, '*');
                }
            }
        return $list;
        // TODO Auto-generated method stub
    }
    function iunserializer($value) {
        if (empty($value)) {
            return '';
        }
        if (!$this->is_serialized($value)) {
            return $value;
        }
        $result = unserialize($value);
        if ($result === false) {
            //return preg_replace_callback("/{([^\}\{\n]*)}/", function($r) { return $this->select($r[1]); }, $source);
            $temp = preg_replace_callback("/{([^\}\{\n]*)}/", function($r) { return $this->select($r[1]); }, $value);
            return unserialize($temp);
        }
        return $result;
    }
    function is_serialized($data, $strict = true) {
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            if (false === $semicolon && false === $brace)
                return false;
            if (false !== $semicolon && $semicolon < 3)
                return false;
            if (false !== $brace && $brace < 4)
                return false;
        }
        $token = $data[0];
        switch ($token) {
            case 's' :
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
            case 'a' :
            case 'O' :
                return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b' :
            case 'i' :
            case 'd' :
                $end = $strict ? '$' : '';
                return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }
        return false;
    }
    //获取微信图文素材
    public function getWechatNewsList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $weixin_media = new WechatNewsModel();
        $list = $weixin_media->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
        // TODO Auto-generated method stub
    }
    //获取微信图片视频语音素材
    public function getWechatAttachmentList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $weixin_media = new WechatAttachmentModel();
        $list = $weixin_media->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
        // TODO Auto-generated method stub
    }
    /*
     * (non-PHPdoc)
     */
    public function getWeiXinMediaDetail($media_id)
    {
        $weixin_media = new WechatAttachmentModel();
        $weixin_news = new WechatNewsModel();
        $media_info = $weixin_media->getInfo(['media_id'=>$media_id],'*');
        $type = $media_info['type'];
        $media_info['createtime'] = date('Y-m-d H:i:s',$media_info['createtime']);
            if($type == 'video') {
                $media_info['tag'] = $this->iunserializer($media_info['tag']);
            }elseif($type == 'news') {
                $media_info['items'] = $weixin_news->pageQuery(1,0,['attach_id' => $media_info['id']], '','*');
            }
        return $media_info;
    }
    public function getWeiXinKey($id)
    {
        $key = new WeixinKeyReplayModel();
        $media_info = $key->getInfo(['id'=>$id],'*');
        return $media_info;
    }
    /**
     * 根据图文消息id查询
     *
     * {@inheritdoc}
     *
     */
    public function getWeixinMediaDetailByMediaId($media_id)
    {
        $weixin_media_item = new WechatAttachmentModel();
        $item_list = $weixin_media_item->getInfo([
            'media_id' => $media_id
        ], '*');
        
        if (! empty($item_list)) {
            
            // 主表
            $weixin_media = new WechatNewsModel();
            $weixin_media_info["media_parent"] = $weixin_media->getQuery([
                "attach_id" => $item_list["media_id"]
            ], "*",'');
            
            // 微信配置
            $weixin_group = new WeixinGroupModel();
            $weixin_media_info["weixin_auth"] = $weixin_group->getInfo([
                "instance_id" => $weixin_media_info["media_parent"]["instance_id"],
                "website_id" => $weixin_media_info["media_parent"]["website_id"]
            ], "*");
            
            $weixin_media_info["media_item"] = $item_list;
            
            // 更新阅读次数
            $res = $weixin_media_item->save([
                "hits" => ($item_list["hits"] + 1)
            ], [
                "id" => $media_id
            ]);
            
            return $weixin_media_info;
        }
        return null;
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function getShopidByAuthorAppid($author_appid)
    {
        $weixin_group = new WeixinGroupModel();
        $instance_id = $weixin_group->getInfo([
            'authorizer_appid' => $author_appid
        ], 'instance_id');
        if (! empty($instance_id['instance_id'])) {
            return $instance_id['instance_id'];
        } else {
            return '';
        }
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function getWeixinUidByOpenid($openid)
    {
        $weixin_fans = new UserModel();
        $uid = $weixin_fans->getInfo([
            'wx_openid' => $openid
        ], 'uid');
        if (! empty($uid['uid'])) {
            return $uid['uid'];
        } else {
            return 0;
        }
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function getWeixinInfoByAppid($author_appid)
    {
        $weixin_auth_model = new WeixinAuthModel();
        $info = $weixin_auth_model->getInfo([
            'authorizer_appid' => $author_appid
        ], '*');
        return $info;
    }

    /** 保存微信授权微信公众号小程序的信息
     * @param array $data
     * @param array $condition
     *
     * @return mixed
     */
    public function saveWeixinAuth($data, array $condition = [])
    {
        $weixin_auth_model = new WeixinAuthModel();
        $retval = $weixin_auth_model->save($data, $condition);
        return $retval;
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function WeixinUserUnsubscribe($openid)
    {
        $weixin_fans = new WeixinFansModel();
        $data = array(
            'is_subscribe' => 0,
            'unsubscribe_date' => time()
        );
        
        $retval = $weixin_fans->save($data, [
            'openid' => $openid
        ]);
        return $retval;
    }

    public function getWeixinGroupInfo($instance_id)
    {
        $weixin_group = new WeixinGroupModel();
        $data = $weixin_group->getInfo([
            'instance_id' => $instance_id,
            'website_id' => $this->website_id
        ], '*');
        return $data;
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function getInstanceWchatMenu($instance_id)
    {
        $weixin_menu = new WeixinMenuModel();
        $foot_menu = $weixin_menu->getQuery([
            'instance_id' => $instance_id,
            'website_id' => $this->website_id,
            'pid' => 0
        ], '*', 'sort');
        if (! empty($foot_menu)) {
            foreach ($foot_menu as $k => $v) {
                $foot_menu[$k]['child'] = '';
                $v['menu_name'] = iunserializer(base64_decode($v['menu_name']));
                $second_menu = $weixin_menu->getQuery([
                    'instance_id' => $instance_id,
                    'website_id' => $this->website_id,
                    'pid' => $v['menu_id']
                ], '*', 'sort');
                foreach($second_menu as $k1=>$v1){
                    $v1['menu_name'] = iunserializer(base64_decode($v1['menu_name']));
                }
                if (! empty($second_menu)) {
                    $foot_menu[$k]['child'] = $second_menu;
                    $foot_menu[$k]['child_count'] = count($second_menu);
                } else {
                    $foot_menu[$k]['child_count'] = 0;
                }
            }
        }
        return $foot_menu;
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function updateInstanceMenuToWeixin($instance_id)
    {
        $menu = array();
        $menu_list = $this->getInstanceWchatMenu($instance_id);
        if (! empty($menu_list)) {
            
            foreach ($menu_list as $k => $v) {
                if (! empty($v)) {
                    $menu_item = array(
                        'name' => ''
                    );
                    $menu_item['name'] = $v['menu_name'];
                    $menu_item['name'] = preg_replace_callback('/\:\:([0-9a-zA-Z_-]+)\:\:/', create_function('$matches', 'return utf8_bytes(hexdec($matches[1]));'), $menu_item['name']);
                    $menu_item['name'] = urlencode($menu_item['name']);
                    if (! empty($v['child'])) {
                        
                        foreach ($v['child'] as $k_child => $v_child) {
                            if (! empty($v_child)) {
                                $sub_menu = array();
                                $sub_menu['name'] = $v_child['menu_name'];
                                $sub_menu['name'] = preg_replace_callback('/\:\:([0-9a-zA-Z_-]+)\:\:/', create_function('$matches', 'return utf8_bytes(hexdec($matches[1]));'), $sub_menu['name']);
                                $sub_menu['name'] = urlencode($sub_menu['name']);
                                if ($v_child['menu_event_type'] == 1) {
                                    $sub_menu['type'] = 'view';
                                    $sub_menu['url'] = urlencode(htmlspecialchars_decode($v_child['menu_event_url']));
                                }else if ($v_child['menu_event_type'] == 4) {
                                    $sub_menu['type'] = 'miniprogram';
                                    $sub_menu['appid'] = $v_child['appid'];
                                    $sub_menu['pagepath'] = $v_child['menu_event_url'];
                                    $sub_menu['url'] = urlencode(htmlspecialchars_decode("http://mp.weixin.qq.com"));
                                } else {
                                    $sub_menu['type'] = 'click';
                                    $sub_menu['key'] = urlencode($v_child['menu_id']);
                                }
                                $menu_item['sub_button'][] = $sub_menu;
                            }
                        }
                    } else {
                        if ($v['menu_event_type'] == 1) {
                            $menu_item['type'] = 'view';
                            $menu_item['url'] = urlencode(htmlspecialchars_decode($v['menu_event_url']));
                        }else if ($v['menu_event_type'] == 4) {
                            $menu_item['type'] = 'miniprogram';
                            $menu_item['appid'] = $v['appid'];
                            $menu_item['pagepath'] = $v['menu_event_url'];
                            $menu_item['url'] = urlencode(htmlspecialchars_decode("http://mp.weixin.qq.com"));
                        } else {
                            $menu_item['type'] = 'click';
                            $menu_item['key'] = urlencode($v['menu_id']);
                        }
                    }
                    $menu[] = $menu_item;
                }
            }
        }
        $menu_array = array();
        $menu_array['button'] = array();
        foreach ($menu as $k => $v) {
            $menu_array['button'][] = $v;
        }
//        // 汉字不编码
        $menu_array = urldecode(json_encode($menu_array));
//        // 链接不转义
//        $menu_array = preg_replace_callback("/\\\u([0-9a-f]{4})/i", create_function('$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'), $menu_array);
        return $menu_array;
    }

    /**
     * // 构造media数据并返回
     * // media_type 消息素材类型1文本 2单图文 3多图文',(non-PHPdoc)
     *
     */
    public function getMediaWchatStruct($media_info)
    {
        switch ($media_info['type']) {
            case "text":
                $contentStr[] = array(
                    "Content" => $media_info['attachment'],
                );
                $contentStr['type'] = 'text';
                break;
            case "image":
                $contentStr[] = array(
                    "MediaId" => $media_info['media_id'],
                );
                $contentStr['type'] = 'image';
                break;
            case "voice":
                $contentStr[] = array(
                    "MediaId" => $media_info['media_id'],
                );
                $contentStr['type'] = 'voice';
                break;
            case "video":
                $contentStr[] = array(
                    "Title" => $media_info['tag']['title'],
                    "Description" => $media_info['tag']['description'],
                    "MediaId" => $media_info['media_id'],
                );
                $contentStr['type'] = 'video';
                break;
            case "news":
                foreach ($media_info['items']['data'] as $k => $v){
                    $v = objToArr($v);
                    $contentStr[$k] = array(
                        "Title" => $v['title'],
                        "Description" => $v['digest'],
                        "PicUrl" => $v['thumb_url'],
                        "Url" => $v['url']
                    );
                }
                $contentStr['type'] = 'news';
                break;
            default:
                $contentStr = "";
                break;
        }
        return $contentStr;
    }
    public function checkKeys($id,$key){
        if($id>0){
            $key1 = ';'.$key;
            $key2 = ';'.$key.';';
            $key3 = $key.';';
            $info = Db::table('sys_weixin_key_replay')
                ->where('key', ['=', $key], ['like', '%'.$key1],['like', '%'.$key2.'%'],['like', $key3.'%'], 'or')
                ->where('website_id', ['=', $this->website_id], 'and')
                ->where('match_type', ['=', 2], 'and')
                ->find();
            if($info){
                return -2;
            }
        }else{
            $key1 = ';'.$key;
            $key2 = ';'.$key.';';
            $key3 = $key.';';
            $info = Db::table('sys_weixin_key_replay')
                ->where('key', ['like', '%'.$key.'%'], ['like', '%'.$key1.'%'],['like', '%'.$key2.'%'],['like', '%'.$key3.'%'], 'or')
                ->where('website_id', ['=', $this->website_id], 'and')
                ->find();
            if($info){
                return -2;
            }
        }
    }
    /**
     * 获取关键字回复
     *
     * @param unknown $key_words            
     */
    public function getWhatReplay($instance_id,$key_words)
    {
        // 全部匹配
        $key1 = ';'.$key_words;
        $key2 = ';'.$key_words.';';
        $key3 = $key_words.';';
        $info = Db::table('sys_weixin_key_replay')
            ->where('key', ['=', $key_words], ['like', '%'.$key1],['like', '%'.$key2.'%'],['like', $key3.'%'], 'or')
            ->where('website_id', ['=', $this->website_id], 'and')
            ->where('match_type', ['=', 2], 'and')
            ->find();
        if (empty($info)) {
            // 模糊匹配
            $info = Db::table('sys_weixin_key_replay')
                ->where('key', ['like', '%'.$key_words.'%'], ['like', '%'.$key1.'%'],['like', '%'.$key2.'%'],['like', '%'.$key3.'%'], 'or')
                ->where('website_id', ['=', $this->website_id], 'and')
                ->where('match_type', ['=', 1], 'and')
                ->find();
        }
        if ($info) {
            $media_detail = $this->getWeixinMediaDetail($info['reply_media_id']);
            $content = $this->getMediaWchatStruct($media_detail);
            $content['type'] = $media_detail['type'];
            $content['replay_type'] = $info['replay_type'];
            return $content;
        } else {
            return [];
        }
    }
    /*
     * 获取海报数据
     * **/
    public function getPosterData($key_words, $website_id)
    {
        try{
            $key_words2 = preg_replace("/\\d+/",'', $key_words);
            if(class_exists('\addons\taskcenter\model\VslGeneralPosterModel')){
                $general_poster_mdl = new VslGeneralPosterModel();
                //去掉关键字为数字的情况
                $condition1['back_keywords'] = $key_words;
//                $condition1['back_keywords2'] = $key_words2;
                $condition12['website_id'] = $website_id;
                if(!empty($key_words2)){
                    $condition1['back_keywords2'] = $key_words2;
                }
                $poster_data = $general_poster_mdl->where(function($q1)use($condition1){
                    $q1->where(['back_keywords' => $condition1['back_keywords']])->whereor(['back_keywords' => $condition1['back_keywords2']]);
                })->where($condition12)->find();
            }
            //获取商品的id
            if(!$poster_data){
                if(class_exists('\addons\poster\model\PosterModel')){
                    $poster_mdl = new PosterModel();
                    $condition2['key_words'] = $key_words;
                    $condition2['key_words2'] = $key_words2;
                    $condition22['website_id'] = $website_id;
//                    $condition22['is_default'] = 1; //暂时去掉默认
                    $poster_data = $poster_mdl->where(function($q2)use($condition2){
                        $q2->where(['key' => $condition2['key_words']])->whereor(['key' => $condition2['key_words2']]);
                    })->where($condition22)->find();
                }
//                $dir = getCwd().'/'.'test_huifu.log';
//                $sql = $poster_mdl->getLastSql();
//                file_put_contents($dir, date('Y-m-d H:i:s').': '.var_export($poster_data, true).$key_words.$key_words2.PHP_EOL.$sql.PHP_EOL);
                if(!$poster_data){
                    return [];
                }
                $poster_data['poster_type'] = 'poster';
            }else{
                //普通任务海报  多级海报
                $poster_data['poster_type'] = 'task_poster';
            }
            return $poster_data;
        }catch(\Exception $e){
            recordErrorLog($e);
            $err = $e->getMessage();
//            $dir = getCwd().'/'.'test_huifu.log';
//            file_put_contents($dir, date('Y-m-d H:i:s').': '.var_export($err, true).PHP_EOL);
        }
    }

    /**
     * 获取海报，并且调用客服消息发送
     */
    public function getPosterSend($poster_data, $key_words, $open_id)
    {
        try{
            // 微信服务器5秒内得不到响应就会重连3次然后推送3次，防止推送3次消息
            ignore_user_abort(true);//设置客户端断开连接时是否中断脚本的执行
            ob_start(); //打开输出控制缓冲
            echo 'success'; // 返给微信服务器的字符串
            header('Connection: close');//关闭http
            header('Content-Length: ' . ob_get_length());
            header('X-Accel-Buffering: no');//nginx
            ob_end_flush();
            ob_flush();
            flush();
            $wchat_oauth = new WchatOauth($this->website_id);
            $user_mdl = new UserModel();
            //通过open_id更新用户信息
            $wx_user_info = $wchat_oauth->get_fans_info($open_id, $this->website_id);
            $user_arr['nick_name'] = $wx_user_info['nickname'] ?: '';
            $user_arr['user_headimg'] = $wx_user_info['headimgurl'] ?: '';
            $user_arr['sex'] = $wx_user_info['sex'] ?: 0;
            $user_cond['wx_openid'] = "$open_id";
            $user_cond['website_id'] = $this->website_id;
            $user_mdl->save($user_arr, $user_cond);
            //现获取缓存数据
            $redis = $this->connectRedis();
            if(is_object($open_id)){
                $open_id = json_decode(json_encode($open_id),true)[0];
            }
            if ($poster_data['poster_type'] == 'task_poster') {
                $waiting_msg = $poster_data['poster_waiting_text'];
                if(!empty($waiting_msg)){
                    //调用客服消息发送
                    $media_id = $waiting_msg;
                    $this->sendFanMessage($open_id, $media_id, $this->website_id);
                }
                $img_arr = $this->posterImage($poster_data, $poster_data['general_poster_id'], 'task_poster', $open_id, 0);
                $poster_id = $poster_data['general_poster_id'];
                $push_data = $this->posterPushData($poster_data, $wchat_oauth, $redis);

            } elseif ($poster_data['poster_type'] == 'poster') {
                $waiting_msg = $poster_data['waiting_reply'];
                //调用客服消息发送
                $media_id = $waiting_msg;
                $this->sendFanMessage($open_id, $media_id, $this->website_id);
                //调用客服消息发送
//            $media_id = $waiting_msg;
//            $this->sendFanMessage($open_id, $media_id, $this->website_id);
                if($poster_data['type'] == 2){
                    $goods_id = $this->getGoodsId($key_words);
                }
                $img_arr = $this->posterImage($poster_data, $poster_data['poster_id'], 'poster', $open_id, $goods_id);
                $poster_id = $poster_data['poster_id'];
                if ($poster_data['type'] == 3) {//关注海报推送
                    $push_data = $this->posterPushData($poster_data, $wchat_oauth, $redis);
                }
            }
//        if($img_arr['code'] == -1){
//            var_dump($img_arr);exit;
//            exit;
//        }
            if($img_arr['code'] == -2){
                if (!empty($img_arr['message'])) {
                    $media_id = $img_arr['message'];
                    $this->sendFanMessage($open_id, $media_id, $this->website_id);exit;
                }else{
                    exit;
                }
            }
//        $img_arr = ['poster' => 'upload/26/task_poster/2.jpg'];
            if ($poster_data['poster_type'] == 'poster') {
                if($poster_data['type'] == 2){
                    $reply_key = $poster_data['poster_type'].'_'.$this->website_id.'_'.$open_id.'_'.$poster_id.'_'.$goods_id;
                }else{
                    $reply_key = $poster_data['poster_type'].'_'.$this->website_id.'_'.$open_id.'_'.$poster_id;
                }
            }else{
                $reply_key = $poster_data['poster_type'].'_'.$this->website_id.'_'.$open_id.'_'.$poster_id;
            }
            $media_id = $redis->get($reply_key);
            if(!$media_id){
                //先上传素材
                $img_src = realpath($img_arr['poster']);
                $res = $wchat_oauth->upload_exec('image', $img_src);
                $media_id = $res['media_id'];
                //将生产的永久素材存入redis
                $redis->set($reply_key, $media_id);
            }
            $res = $wchat_oauth->send_message($open_id, 'image', $media_id);
            if (!isset($push_type) && !empty($push_data['push_type']) && !empty($push_data['push_content'])) {
                $wchat_oauth->send_message($open_id, $push_data['push_type'], $push_data['push_content']);
            }
        }catch(\Exception $e){
            recordErrorLog($e);
//            $file_dir = getcwd().'/test_haibao.log';
//            file_put_contents($file_dir, $e->getMessage().PHP_EOL, 8);
        }

    }
    /*
     * 删除海报缓存，清除微信素材
     * **/
    public function deletePoster($website_id)
    {
        $wchat_oauth = new WchatOauth($website_id);
        $redis = $this->connectRedis();
        $redis_key_arr = $redis->keys('*poster_'.$website_id.'*');
        foreach($redis_key_arr as $v){
            $media_id = $redis->get($v);
            $res = $wchat_oauth->delMaterial($media_id);
        }
        $path0 = getcwd() . '/upload/' . $website_id . '/poster_mp/';
        $path1 = getcwd() . '/upload/' . $website_id . '/poster/';
        $path2 = getcwd() . '/upload/' . $website_id . '/task_poster/';
        $exec_rm0 = 'rm -rf ' . $path0;
        $exec_rm1 = 'rm -rf ' . $path1;
        $exec_rm2 = 'rm -rf ' . $path2;
        exec($exec_rm0);
        exec($exec_rm1);
        exec($exec_rm2);
        $keys = $redis->keys('*poster_' . $website_id . '*');
        foreach($keys as $v1){
            $bool = $redis->del($v1);
        }
        return 1;
    }
    /*
     * 获取goods_id
     * **/
    public function getGoodsId($key_words)
    {
        $goods_id = '';
        for ($i=0;$i<mb_strlen($key_words);$i++) {
            $s = mb_substr($key_words, $i, 1);
            if (is_numeric($s)) {
                $goods_id .= $s;
            }
        }
        return $goods_id;
    }

    /**
     * 处理海报推送设置
     * @param array $poster_data
     * @param object $wchat_oauth
     * @param object $redis
     * @param
     */
    public function posterPushData($poster_data,$wchat_oauth = null,$redis = null)
    {
        try{
            //现获取缓存数据
            if (empty($redis)){
                $redis = $this->connectRedis();
            }
            if ($poster_data['poster_type'] == 'task_poster'){
                $poster_data['poster_id'] = $poster_data['general_poster_id'];
                $poster_data['push_cover_id'] = $poster_data['push_cover'];
            }
            $reply_key = $poster_data['poster_type'] . '_' . $this->website_id . '_' . $poster_data['poster_id'] . '_' . 'push';
            $return = [];
            $media_id = $redis->get($reply_key);
            if ($media_id){
                $return['push_type'] = 'news';
                $return['push_content'] = $media_id;
                return $return;
            }
            if (empty($wchat_oauth)){
                $wchat_oauth = new WchatOauth($this->website_id);
            }
            // 关注海报推送设置
            if ($poster_data['push_type'] == 1 &&
                !empty($poster_data['push_title']) &&
                !empty($poster_data['push_cover_id']) &&
                !empty($poster_data['push_desc']) &&
                !empty($poster_data['push_link'])) {
                // 图文消息 && 各项设置不为空
                $album_picture_model = new AlbumPictureModel();
                $pic_cover = $album_picture_model::get($poster_data['push_cover_id'])['pic_cover'];
                $had_download = false;
                if (stristr($pic_cover, "http://") || stristr($pic_cover, "https://")) {
                    $push_img_src = 'temp_push_' . uniqid() . '.png';
                    $this->downFile($pic_cover, $push_img_src);
                    $had_download = true;
                } else {
                    $push_img_src = realpath($pic_cover);
                }
//            var_dump($push_img_src);
                $res = $wchat_oauth->upload_exec('image', $push_img_src);
//            var_dump($res);
                if ($had_download) {
                    $this->unlinkImage($push_img_src);
                }
                $push_media_id = $res['media_id'];
                $news_data = [];
                $news_data['articles'] = [];
                //"title": TITLE,标题
                //"thumb_media_id": THUMB_MEDIA_ID,图文消息的封面图片素材id（必须是永久mediaID）
                //"show_cover_pic": SHOW_COVER_PIC(0 / 1),是否显示封面，0为false，即不显示，1为true，即显示
                //"content": CONTENT,图文消息的具体内容
                //"content_source_url": CONTENT_SOURCE_URL,图文消息的原文地址，即点击“阅读原文”后的URL
                $news_data['articles'][0]['title'] = $poster_data['push_title'];
                $news_data['articles'][0]['thumb_media_id'] = $push_media_id;
                $news_data['articles'][0]['show_cover_pic'] = 1;
                $news_data['articles'][0]['content'] = $poster_data['push_desc'];
                $news_data['articles'][0]['content_source_url'] = $poster_data['push_link'];
//            var_dump($news_data);
                $news_result = $wchat_oauth->add_news($news_data);
                $return['push_type'] = 'news';
                $return['push_content'] = $news_result['media_id'];
                $redis->set($reply_key, $news_result['media_id']);
                return $return;
            }
            if ($poster_data['push_type'] == 2 && !empty($poster_data['push_text'])) {
                // 文本消息 && 文本不为空
                $return['push_type'] = 'text';
                $return['push_content'] = $poster_data['push_text'];
                return $return;
            }
        }catch(\Exception $e){
        }
    }

    /**
     * 获取关注回复
     *
     * @param unknown $instance_id            
     * @return unknown|string
     */
    public function getSubscribeReplay($website_id=0)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $weixin_flow_replay = new WeixinFollowReplayModel();
        $info = $weixin_flow_replay->getInfo([
            'website_id' => $websiteid
        ], '*');
        if (! empty($info)) {
            $media_detail = $this->getWeixinMediaDetail($info['reply_media_id']);
            $content = $this->getMediaWchatStruct($media_detail);
            $content['type'] = $media_detail['type'];
            return $content;
        } else {
            return '';
        }
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function getDefaultReplay($instance_id)
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $info = $weixin_default_replay->getInfo([
            'website_id' => $this->website_id
        ], '*');
        if ($info) {
            $media_detail = $this->getWeixinMediaDetail($info['reply_media_id']);
            $content = $this->getMediaWchatStruct($media_detail);
            $content['type'] = $media_detail['type'];
            return $content;
        } else {
            return [];
        }
    }

    /**
     * 获取会员 微信公众号二维码
     * @param array $scene
     * @param string $action_name
     */
    public function getUserWchatQrcode(array $scene, $action_name = 'QR_STR_SCENE')
    {
        $weixin_auth = new WchatOauth($this->website_id);
        $qrcode_url = $weixin_auth->ever_qrcode($scene, $action_name);
        return $qrcode_url;
    }

    /**
     * (non-PHPdoc)
     */
    public function getWeixinQrcodeConfig($instance_id, $uid)
    {
        $user = new UserModel();
        $userinfo = $user->getInfo([
            "uid" => $uid
        ]);
        $qrcode_template_id = $userinfo["qrcode_template_id"];
        $weixin_qrcode = new WeixinQrcodeTemplateModel();
        if ($qrcode_template_id == 0 || $qrcode_template_id == null) {
            $weixin_obj = $weixin_qrcode->getInfo([
                "instance_id" => $instance_id,
                "website_id" => $this->website_id,
                "is_check" => 1
            ], "*");
        } else {
            $weixin_obj = $weixin_qrcode->getInfo([
                "instance_id" => $instance_id,
                "website_id" => $this->website_id,
                "id" => $qrcode_template_id
            ], "*");
        }
        
        if (empty($weixin_obj)) {
            $weixin_obj = $weixin_qrcode->getInfo([
                "instance_id" => $instance_id,
                "website_id" => $this->website_id,
                "is_remove" => 0
            ], "*");
        }
        return $weixin_obj;
    }

    /**
     * (non-PHPdoc)
     */
    public function updateWeixinQrcodeConfig($instance_id, $background, $nick_font_color, $nick_font_size, $is_logo_show, $header_left, $header_top, $name_left, $name_top, $logo_left, $logo_top, $code_left, $code_top)
    {
        $weixin_qrcode = new WeixinQrcodeConfigModel();
        $num = $weixin_qrcode->where([
            'instance_id' => $instance_id,
            'website_id'  => $this->website_id
        ])->count();
        if ($num > 0) {
            $data = array(
                'background' => $background,
                'nick_font_color' => $nick_font_color,
                'nick_font_size' => $nick_font_size,
                'is_logo_show' => $is_logo_show,
                'header_left' => $header_left . 'px',
                'header_top' => $header_top . 'px',
                'name_left' => $name_left . 'px',
                'name_top' => $name_top . 'px',
                'logo_left' => $logo_left . 'px',
                'logo_top' => $logo_top . 'px',
                'code_left' => $code_left . 'px',
                'code_top' => $code_top . 'px'
            );
            $res = $weixin_qrcode->save($data, [
                'instance_id' => $instance_id,
                'website_id'  => $this->website_id
            ]);
        } else {
            $data = array(
                'instance_id' => $instance_id,
                'website_id'  => $this->website_id,
                'background' => $background,
                'nick_font_color' => $nick_font_color,
                'nick_font_size' => $nick_font_size,
                'is_logo_show' => $is_logo_show,
                'header_left' => $header_left . 'px',
                'header_top' => $header_top . 'px',
                'name_left' => $name_left . 'px',
                'name_top' => $name_top . 'px',
                'logo_left' => $logo_left . 'px',
                'logo_top' => $logo_top . 'px',
                'code_left' => $code_left . 'px',
                'code_top' => $code_top . 'px'
            );
            $weixin_qrcode->save($data);
            $res = 1;
        }
        return $res;
        // TODO Auto-generated method stub
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function updateWeixinMedia($media_id, $title, $instance_id, $type, $sort, $content)
    {
        $weixin_media = new WeixinMediaModel();
        $weixin_media->startTrans();
        try {
            // 先修改 图文消息表
            $data_media = array(
                'title' => $title,
                'instance_id' => $instance_id,
                'website_id'  => $this->website_id,
                'type' => $type,
                'sort' => $sort,
                'create_time' => time()
            );
            $weixin_media->save($data_media, [
                'media_id' => $media_id
            ]);
            // 修改 图文消息内容的时候 先删除了图文消息内容再添加一次
            $weixin_media_item = new WeixinMediaItemModel();
            $weixin_media_item->destroy([
                'media_id' => $media_id
            ]);
            if ($type == 1) {
                $this->addWeixinMediaItem($media_id, $title, '', '', '', '', '', '', 0);
            } else 
                if ($type == 2) {
                    $info = explode('`|`', $content);
                    $this->addWeixinMediaItem($media_id, $info[0], $info[1], $info[2], $info[3], $info[4], $info[5], $info[6], 0);
                } else 
                    if ($type == 3) {
                        $list = explode('`$`', $content);
                        foreach ($list as $k => $v) {
                            $arr = Array();
                            $arr = explode('`|`', $v);
                            $this->addWeixinMediaItem($media_id, $arr[0], $arr[1], $arr[2], $arr[3], $arr[4], $arr[5], $arr[6], 0);
                        }
                    }
            $weixin_media->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $weixin_media->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 删除图文消息
     *
     * {@inheritdoc}
     *
     */
    public function deleteWeixinMedia($type,$id)
    {
        $weixin_attachment = new WechatAttachmentModel();
        $weixin_news = new WechatNewsModel();
        $media_id = $weixin_attachment->getInfo(['id'=>$id],'media_id')['media_id'];
        $wchat = new WchatOauth($this->website_id);
        $res = $wchat->delMaterial($media_id);
        if($res['errcode']==0){
            if($type=='news'){
                $res = $weixin_news->destroy([
//                    'media_id' => $media_id  // by sgw 关联用attach_id
                    'attach_id' => $id
                ]);
                //wechat_attachment表也需要删除
                $weixin_attachment->destroy([
                    'id' => $id,
                ]);
            }else{
                $res = $weixin_attachment->destroy([
                    'media_id' => $media_id,
                    'website_id' => $this->website_id
                ]);
            }
        }
        return $res;
    }

    /**
     * 删除图文消息详情下列表
     */
    public function deleteWeixinMediaDetail($id)
    {
        $weixin_media_item = new WeixinMediaItemModel();
        $res = $weixin_media_item->where("id=$id")->delete();
        return $res;
    }

    /**
     * 删除微信自定义菜单
     *
     * {@inheritdoc}
     *
     */
    public function deleteWeixinMenu($menu_id)
    {
        $weixin_menu = new WeixinMenuModel();
        $res = $weixin_menu->where("menu_id=$menu_id or pid=$menu_id")->delete();
        return $res;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getFollowReplayDetail($condition)
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        $info = $weixin_follow_replay->getInfo($condition);
        if ($info['reply_media_id']) {
            $info['media_info'] = $this->getWeixinMediaDetail($info['reply_media_id']);
            $info['create_time'] =date('Y-m-d H:i:s', $info['create_time']);
        }
        return $info;
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function getDefaultReplayDetail($condition)
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $info = $weixin_default_replay->getInfo($condition);
        if ($info['reply_media_id']) {
            $info['media_info'] = $this->getWeixinMediaDetail($info['reply_media_id']);
            $info['create_time'] =date('Y-m-d H:i:s', $info['create_time']);
        }
        return $info;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function deleteFollowReplay($instance_id)
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        return $weixin_follow_replay->destroy([
            'instance_id' => $instance_id,
            'website_id' => $this->website_id

        ]);
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function deleteDefaultReplay($instance_id)
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        return $weixin_default_replay->destroy([
            'instance_id' => $instance_id,
            'website_id' => $this->website_id
        ]);
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getKeyReplyDetail($id)
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        $info = $weixin_key_replay->getInfo(['id'=>$id],'*');
        if ($info['reply_media_id']) {
            $info['media_info'] = $this->getWeixinMediaDetail($info['reply_media_id']);
        }
        return $info;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function deleteKeyReplay($id)
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        return $weixin_key_replay->destroy($id);
    }

    /**
     * 得到店铺的推广二维码模板列表
     *
     * {@inheritdoc}
     */
    public function getWeixinQrcodeTemplate($shop_id)
    {
        $weixin_qrcode_template = new WeixinQrcodeTemplateModel();
        return $weixin_qrcode_template->all(array(
            "instance_id" => $shop_id,
            "website_id" => $this->website_id,
            "is_remove" => 0
        ));
    }

    /**
     * 将某个模板设置为最新默认模板
     *
     * {@inheritdoc}
     *
     */
    public function modifyWeixinQrcodeTemplateCheck($shop_id, $id)
    {
        $weixin_qrcode_template = new WeixinQrcodeTemplateModel();
        $weixin_qrcode_template->where(array(
            "instance_id" => $shop_id,
            "website_id" => $this->website_id,
        ))->update(array(
            "is_check" => 0
        ));
        $retval = $weixin_qrcode_template->where(array(
            "instance_id" => $shop_id,
            "website_id" => $this->website_id,
            "id" => $id
        ))->update(array(
            "is_check" => 1
        ));
        return $retval;
    }

    /**
     * 添加店铺推广二维码模板
     * (non-PHPdoc)
     *
     */
    public function addWeixinQrcodeTemplate($instance_id, $background, $nick_font_color, $nick_font_size, $is_logo_show, $header_left, $header_top, $name_left, $name_top, $logo_left, $logo_top, $code_left, $code_top, $template_url)
    {
        $weixin_qrcode = new WeixinQrcodeTemplateModel();
        $data = array(
            'instance_id' => $instance_id,
            'website_id' => $this->website_id,
            'background' => $background,
            'nick_font_color' => $nick_font_color,
            'nick_font_size' => $nick_font_size,
            'is_logo_show' => $is_logo_show,
            'header_left' => $header_left . 'px',
            'header_top' => $header_top . 'px',
            'name_left' => $name_left . 'px',
            'name_top' => $name_top . 'px',
            'logo_left' => $logo_left . 'px',
            'logo_top' => $logo_top . 'px',
            'code_left' => $code_left . 'px',
            'code_top' => $code_top . 'px',
            'template_url' => $template_url
        );
        $weixin_query = $weixin_qrcode->getQuery([
            "instance_id" => $instance_id,
            'website_id' => $this->website_id,
            "is_check" => 1
        ], "*", '');
        if (empty($weixin_query)) {
            $data["is_check"] = 1;
        }
        $res = $weixin_qrcode->save($data);
        return $weixin_qrcode->id;
    }

    /**
     * 更新模板
     * (non-PHPdoc)
     *
     */
    public function updateWeixinQrcodeTemplate($id, $instance_id,  $background, $nick_font_color, $nick_font_size, $is_logo_show, $header_left, $header_top, $name_left, $name_top, $logo_left, $logo_top, $code_left, $code_top, $template_url)
    {
        $weixin_qrcode = new WeixinQrcodeTemplateModel();
        $data = array(
            'instance_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'background' => $background,
            'nick_font_color' => $nick_font_color,
            'nick_font_size' => $nick_font_size,
            'is_logo_show' => $is_logo_show,
            'header_left' => $header_left . 'px',
            'header_top' => $header_top . 'px',
            'name_left' => $name_left . 'px',
            'name_top' => $name_top . 'px',
            'logo_left' => $logo_left . 'px',
            'logo_top' => $logo_top . 'px',
            'code_left' => $code_left . 'px',
            'code_top' => $code_top . 'px',
            'template_url' => $template_url
        );
        
        $res = $weixin_qrcode->save($data, [
            'id' => $id
        ]);
        return $res;
    }

    /**
     * 删除模板
     * (non-PHPdoc)
     *
     */
    public function deleteWeixinQrcodeTemplate($id, $instance_id)
    {
        $weixin_qrcode_template = new WeixinQrcodeTemplateModel();
        $retval = $weixin_qrcode_template->where(array(
            "instance_id" => $instance_id,
            'website_id' => $this->website_id,
            "id" => $id
        ))->update(array(
            "is_remove" => 1
        ));
        return $retval;
    }

    /**
     * 查询单个模板的具体信息
     * (non-PHPdoc)
     *
     */
    public function getDetailWeixinQrcodeTemplate($id)
    {
        if ($id == 0) {
            $template_obj = array(
                "background" => "",
                "nick_font_color" => "#2B2B2B",
                "nick_font_size" => "23",
                "is_logo_show" => 1,
                "header_left" => "59px",
                "header_top" => "15px",
                "name_left" => "150px",
                "name_top" => "13px",
                "name_top" => "120px",
                "logo_top" => "100px",
                "logo_left" => "120px",
                "code_left" => "70px",
                "code_top" => "300px"
            );
            return $template_obj;
        } else {
            $weixin_qrcode_template = new WeixinQrcodeTemplateModel();
            $template_obj = $weixin_qrcode_template->get($id);
            return $template_obj;
        }
    }

    /**
     * 用户更换 自己的推广二维码
     * (non-PHPdoc)
     *
     */
    public function updateMemberQrcodeTemplate($shop_id, $uid)
    {
        $user = new UserModel();
        $userinfo = $user->getInfo([
            "uid" => $uid
        ], "qrcode_template_id");
        $qrcode_template_id = $userinfo["qrcode_template_id"];
        $qrcode_template = new WeixinQrcodeTemplateModel();
        if ($qrcode_template_id == 0 || $qrcode_template_id == null) {
            $template_obj = $qrcode_template->getInfo([
                "instance_id" => $shop_id,
                "website_id" => $this->website_id,
                "is_remove" => 0
            ], "*");
        } else {
            $condition["id"] = array(
                ">",
                $qrcode_template_id
            );
            $condition["instance_id"] = $shop_id;
            $condition["website_id"] = $this->website_id;
            $condition["is_remove"] = 0;
            $template_obj = $qrcode_template->getInfo($condition, "*");
            if (empty($template_obj)) {
                $template_obj = $qrcode_template->getInfo([
                    "instance_id" => $shop_id,
                    "website_id" => $this->website_id,
                    "is_remove" => 0
                ], "*");
            }
        }
        if (! empty($template_obj)) {
            $user->where(array(
                "uid" => $uid
            ))->update(array(
                "qrcode_template_id" => $template_obj["id"]
            ));
        }
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function getInstanceOneKeySubscribe($instance_id)
    {
        $weixin_subscribe = new WeixinOneKeySubscribeModel();
        $info = $weixin_subscribe->get($instance_id,$this->website_id);
        if (empty($info)) {
            $data = array(
                'instance_id' => $instance_id,
                'website_id' => $this->website_id,
                'url' => ''
            );
            $weixin_subscribe->save($data);
            $info = $weixin_subscribe->get($instance_id,$this->website_id);
        }
        return $info;
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function setInsanceOneKeySubscribe($instance_id, $url)
    {
        $weixin_subscribe = new WeixinOneKeySubscribeModel();
        $retval = $weixin_subscribe->save([
            'url' => $url
        ], [
            'instance_id' => $instance_id,
            'website_id' => $this->website_id
        ]);
        return $retval;
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function getUserOpenid($instance_id)
    {}

    /**
     * (non-PHPdoc)
     *
     */
    public function getWeixinFansCount($condition)
    {
        $weixin_fans = new WeixinFansModel();
        $count = $weixin_fans->where($condition)->count();
        return $count;
    }

    /**
     * 获取会员关注微信信息(non-PHPdoc)
     *
     */
    public function getUserWeixinSubscribeData($uid, $instance_id)
    {
        // 查询会员信息
        $user = new UserModel();
        $user_info = $user->getInfo([
            'uid' => $uid,
            'website_id' => $this->website_id
        ], 'wx_openid,wx_unionid');
        $fans_info = '';
        // 通过openid查询信息
        if (! empty($user_info['wx_openid'])) {
            $weixin_fans = new WeixinFansModel();
            $fans_info = $weixin_fans->getInfo([
                'openid' => $user_info['wx_openid'],
                'website_id' => $this->website_id
            ]);
        }
        if (empty($fans_info) && ! empty($user_info['wx_unionid'])) {
            $weixin_fans = new WeixinFansModel();
            $fans_info = $weixin_fans->getInfo([
                'openid' => $user_info['wx_unionid'],
                'website_id' => $this->website_id
            ]);
        }
        return $fans_info;
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function addUserMessage($openid, $content, $msg_type,$website_id=0)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $weixin_user_msg = new WeixinUserMsgModel();
        $uid = $this->getWeixinUidByOpenid($openid);
        $data = array(
            'uid' => $uid,
            'msg_type' => $msg_type,
            'content' => $content,
            'create_time' => time(),
            'website_id' => $websiteid
        );

        if (!empty($uid)) {
            $weixin_user_msg->save($data);
            return $weixin_user_msg->msg_id;
        } else {
            return 0;
        }
    }

    /**
     * (non-PHPdoc)
     *
     */
    public function addUserMessageReplay($msg_id, $replay_uid, $replay_type, $content)
    {
        $weixin_user_msg_replay = new WeixinUserMsgReplayModel();
        $data = array(
            'msg_id' => $msg_id,
            'replay_uid' => $replay_uid,
            'replay_type' => $replay_type,
            'content' => $content,
            'replay_time' => time(),
            'website_id' => $this->website_id
        );
        $weixin_user_msg_replay->save($data);
        return $weixin_user_msg_replay->replay_id;
    }
    /**
     * 更新粉丝信息
     * @param string $next_openid
     * @return mixed
     */
    public function updateWchatFansList($openid_array){
        $wchatOauth = new WchatOauth($this->website_id);
        $fans_list_info = $wchatOauth -> get_fans_info_list($openid_array);
        //获取微信粉丝列表
        if(isset($fans_list_info["errcode"]) && $fans_list_info["errcode"] < 0){
            return $fans_list_info;
        }else{
            if($fans_list_info['user_info_list']){
                foreach ($fans_list_info['user_info_list'] as $k=>$info){
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
                    if($info['groupid']){
                        $data['groupid'] = $info['groupid'];
                    }else{
                        $data['groupid'] = -2;
                    }
                    $data['unionid'] = $info['unionid'];
                    $data['is_subscribe'] = $info['subscribe'];
                    $data['memo'] = $info['remark'];
                    $data['subscribe_date'] = $info['subscribe_time'];
                    $data['update_date'] = time();
                    $this->addWeixinFans($data);
                }
            }
        }
        return array(
            'errcode'  => '0',
            'errormsg' => 'success'
        );
        
    }
    /**
     * 获取微信所有openid
     */
    public function getWeixinOpenidList(){
        $wchatOauth = new WchatOauth($this->website_id);
        $res = $wchatOauth->get_fans_list("");
        $openid_list = array();
        if(!empty($res['data']))
        {
            $openid_list = $res['data']['openid'];
            $wchatOauth = new WchatOauth($this->website_id);
            while($res['next_openid']){
                $res = $wchatOauth->get_fans_list($res['next_openid']);
                if(!empty($res['data']))
                {
                    $openid_list = array_merge($openid_list,$res['data']['openid']);
                }
                
            }
            return array(
                'total' => $res['total'],
                'openid_list' => $openid_list,
                'errcode'  => '0',
                'errormsg' => ''
            );
           
        }else{
            if(!empty($res["errcode"]))
            {
                return array(
                    'errcode'  => $res['errcode'],
                    'errormsg' => $res['errmsg'],
                    'total'    => 0,
                    'openid_list' => ''
                );
            }else{
                return array(
                    'errcode'  => '-400001',
                    'errormsg' => '当前无粉丝列表或者获取失败',
                    'total'    => 0,
                    'openid_list' => ''
                );
            }
         
        }
        
    }
}