<?php
namespace addons\qlkefu\controller;

use addons\qlkefu\server\Qlkefu as QlkefuServer;
use data\model\UserModel;
use data\extend\WchatOauth;
use data\service\Album as Album;

class Qlkefuapi extends BaseController
{
    public function __construct()
    {
        parent::__construct();

    }
    
    /**
     * 获取图片分组
     */
    public function albumlist()
    {
        if(request()->isPost()) {
            $seller_code = input('post.seller_code');
            $page_index = input('post.page_index',1);
            $page_size = input('post.page_size',PAGESIZE);
            $search_text = input('post.album_name','');
            $qlkefu_server = new QlkefuServer();
            $qlkefu_info = $qlkefu_server->getQlkefuDetail(['seller_code'=>$seller_code]);
            if(!$qlkefu_info){
                return json(['code' => 0, 'msg' => 'ok','data'=>['data'=>[],'page_count'=>0,'total_count'=>0]]);
            }
            $album = new Album();
            $condition = ['shop_id'=>$qlkefu_info['shop_id'],'website_id'=>$qlkefu_info['website_id']];
            if($search_text!=''){
                $condition['album_name'] = ['like', '%' . $search_text . '%'];
            }
            $list = $album->getAlbumClassList($page_index, $page_size, $condition);
            return json(['code' => 0, 'msg' => 'ok','data'=>$list]);
        }
    }
    
    /**
     * 图片空间弹窗 相册图片获取
     */
    public function getalbunpic(){
        if(request()->isPost()) {
            $seller_code = input('post.seller_code');
            $page_index = input('post.page_index',1);
            $page_size = input('post.page_size',PAGESIZE);
            $album_id = input('post.album_id',0);
            $file_type = input('post.file_type',0);
            $sort_name = input('post.sort_name',0);
            $qlkefu_server = new QlkefuServer();
            $qlkefu_info = $qlkefu_server->getQlkefuDetail(['seller_code'=>$seller_code]);
            if(!$qlkefu_info){
                return json(['code' => 0, 'msg' => 'ok','data'=>['data'=>[],'page_count'=>0,'total_count'=>0]]);
            }
            $album = new Album();
            if(!$album_id){
                $album_id = $album->getDefaultAlbum($qlkefu_info['shop_id'],$qlkefu_info['website_id'])['album_id'];
            }
            $condition = ['album_id'=>$album_id,'is_wide'=>$file_type];
            $order='upload_time desc';
            if (0 < $sort_name) {
                switch ($sort_name) {
                    case '1':
                        $order = 'upload_time asc';
                        break;
                        
                    case '2':
                        $order = 'upload_time desc';
                        break;
                        
                    case '3':
                        $order = 'pic_size asc';
                        break;
                        
                    case '4':
                        $order = 'pic_size desc';
                        break;
                        
                    case '5':
                        $order = 'pic_name asc';
                        break;
                        
                    case '6':
                        $order = 'pic_name desc';
                        break;
                }
            }
            $list = $album->getPictureList($page_index, $page_size, $condition,$order);
            if(!empty($list['data'])){
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['pic_cover'] = __IMG($v['pic_cover']);
                    $list['data'][$k]['pic_cover_big'] = __IMG($v['pic_cover_big']);
                    $list['data'][$k]['pic_cover_micro'] = __IMG($v['pic_cover_micro']);
                    $list['data'][$k]['pic_cover_mid'] = __IMG($v['pic_cover_mid']);
                    $list['data'][$k]['pic_cover_small'] = __IMG($v['pic_cover_small']);
                }
            }
            return json(['code' => 0, 'msg' => 'ok','data'=>$list]);
        }
    }
    
    /**
     * 微信消息通知
     */
    public function sendwechat()
    {
        if(request()->isPost()) {
            $uid = input('post.uid');
            $seller_code = input('post.seller_code');
            $doubt = input('post.doubt');
            $reply = input('post.reply');
            $notice_template_id = input('post.notice_template_id');
            $qlkefu_server = new QlkefuServer();
            $qlkefu_info = $qlkefu_server->getQlkefuDetail(['seller_code'=>$seller_code]);
            $user = new UserModel();
            $user_info = $user->getInfo(['uid' => $uid], 'wx_openid');
            if(empty($notice_template_id)){
                return json(['code' => -1, 'msg' => '发送失败，没有配置公众号模版ID']);
            }
            if(empty($user_info['wx_openid'])){
                return json(['code' => -1, 'msg' => '发送失败，会员没有绑定微信']);
            }
            if(!empty($user_info['wx_openid']) && !empty($notice_template_id)){
                $openid = $user_info['wx_openid'];
                $array = array(
                    'touser' => $openid,
                    'template_id' => $notice_template_id,
                    'url' => getDomain($qlkefu_info['website_id']).'/wap/message',  
                    'data' => array(
                        'first' => array(
                            'value' => '您的咨询已有客服进行回答',
                        ),
                        'keyword1' => array(
                            'value' => date('Y-m-d H:i:s'),
                        ),
                        'keyword2' => array(
                            'value' => $doubt,
                        ),
                        'keyword3' => array(
                            'value' => $reply,
                        )
                    )
                );
                $weixin = new WchatOauth($qlkefu_info['website_id']);
                $res = $weixin->templateMessageSend($array, $qlkefu_info['website_id']);
                if($res['errmsg']=='ok'){
                    return json(['code' => 0, 'msg' => '发送成功']);
                }else{
                    if(!empty($res['errcode']) && $res['errcode']=='40037'){
                        return json(['code' => -1, 'msg' => '发送失败，模版ID无效']);
                    }else{
                        return json(['code' => -1, 'msg' => $res]);
                    }
                }
            }
            return json(['code' => -1, 'msg' => '发送失败']);
        }
    }
    
    /**
     * 获取注册验证码
     */
    public function sendlogincode()
    {
        if(request()->isPost()) {
            $param = [];
            $param['mobile'] = input('post.username');
            $param['shop_id'] = 0;
            $param['website_id'] = 0;
            $result = runhook('Notify', 'qlkefuLoginBySms', $param);
            if (empty($result)) {
                return json(['code' => -1, 'msg' => '发送失败']);
            } else if ($result['code'] < 0) {
                return json(['code' => $result['code'], 'msg' => $result['message']]);
            } else {
                return json(['code' => 0, 'msg' => '发送成功','data'=>['mobileCode'=>$result['param'],'sendMobile'=>$param['mobile']]]);
            }
        }
    }
}