<?php

namespace addons\goodhelper\controller;

use addons\goodhelper\Goodhelper as baseGoodHelper;
use addons\goodhelper\model\VslGoodsHelpTokenModel;
use addons\goodhelper\server\GoodHelper as GoodsServer;
use addons\goodhelper\model\VslGoodsHelpModel;
use data\model\UserModel;
use data\service\User;

/**
 * 商品助手控制器
 * Class GoodHelper
 * @package addons\goodhelper\controller
 */
class Goodhelper extends baseGoodHelper {

    public function __construct() {
        parent::__construct();
    }

    public function goodConfirmImport() {
        if (request()->isPost()) {
            $file = request()->file('excel');
            $zip = request()->file('zip');
            $add_type = (int) request()->post('add_type'); //导入类型
            if (!$file) {
                $result['code'] = 0;
                $result['message'] = '请上传文件';
                return $result;
            }
            $goodsServer = new GoodsServer();
            $result = $goodsServer->joinTheQueue($file, $zip, $add_type);
            $this->addUserLog('商品导入',$result['code']);
            return $result;
        }
    }

    /**
     * 商品采集
     * @return \multitype
     */
    public function getGoodGather() {
        $con = trim(request()->post('content', ''));
        $goods_type = request()->post('goods_type', '');
        $batch_no = request()->post('batch_no', 0);
        if (!$con) {
            return AjaxReturn(0);
        }
        $text = nl2br($con); //将分行符"\r\n"转义成HTML的换行符"<br />"
        $contentArr = explode("<br />", $text); //"<br />"作为分隔切成数组
        $good_gather = new GoodsServer();
        if(count($contentArr) > 10){
            return AjaxReturn(-10021);
        }
        if(count($contentArr) == 1){
            $content = $contentArr[0];
            if (strstr($content, 'tmall')) {
                $content = str_replace('amp;', '', $content);
                parse_str(@array_pop(explode('?', $content)), $a);
                $content = 'https://detail.tmall.com/item.htm?id='.$a['id'];
            } elseif(strstr($content, 'taobao')){
                $content = str_replace('amp;', '', $content);
                parse_str(@array_pop(explode('?', $content)), $a);
                $content =  'https://item.taobao.com/item.htm?id=' . $a['id'];
            } elseif(intval($content)) {
                if ($goods_type) {
                    $content = 'https://item.taobao.com/item.htm?id=' . $content;
                } else {
                    $content = 'https://detail.tmall.com/item.htm?id=' . $content;
                }
            } else{
                return AjaxReturn(0);
            }
            $res = $good_gather->getGoodGather($content);
            if(!$res){
                return AjaxReturn(0);
            }
            $this->addUserLog('商品采集', $res);
            return AjaxReturn(1);
        }
        $aUrl = [];
        $aTid = [];
        foreach ($contentArr as $content) {
            $content = trim($content);

            if (strstr($content, 'tmall')) {
                $content = str_replace('amp;', '', $content);
                parse_str(@array_pop(explode('?', $content)), $a);
                $aUrl[] = 'https://detail.tmall.com/item.htm?id='.$a['id'];
                $aTid[] = ["itemId"=>$a['id'],"isCache"=>"2","type" => "tmall"];
            } elseif(strstr($content, 'taobao')){
                $content = str_replace('amp;', '', $content);
                parse_str(@array_pop(explode('?', $content)), $a);
                $aUrl[] =  'https://item.taobao.com/item.htm?id=' . $a['id'];
                $aTid[] = ["itemId"=>$a['id'],"isCache"=>"2","type" => "taobao"];
            } elseif(intval($content)) {
                if ($goods_type) {
                    $aUrl[] = 'https://item.taobao.com/item.htm?id=' . $content;
                    $aTid[] = ["itemId"=>$content,"isCache"=>"2","type" => "taobao"];
                } else {
                    $aUrl[] = 'https://detail.tmall.com/item.htm?id=' . $content;
                    $aTid[] = ["itemId"=>$content,"isCache"=>"2","type" => "tmall"];
                }
            } else{
                continue;
            }
        }
        $res = $good_gather->getMultipleGoodGather($aUrl,$aTid,$batch_no);
        if ($res < 0) {
            return AjaxReturn($res);
        } 
        return AjaxReturn(2);
    }
    /*
     * 采集的商品详情也cookie的商品数据合并存入数据库
     */
    public function setGoodDesc(){
        $data = request()->post('data/a', '');
        $goodsServer = new GoodsServer();
        $res = $goodsServer->setGoodDesc($data);
        if(!$res){
            return AjaxReturn(0);
        }
        return AjaxReturn(1);
    }
    
    /**
     * 导入进度
     */
    public function progress()
    {
        if(request()->post('data')){
            $goodsHelpModel = new VslGoodsHelpModel();
            $goodsHelpList = $goodsHelpModel->getQuery(['website_id' => $this->website_id, 'shop_id' => $this->instance_id], '*', 'create_time asc');
            if($goodsHelpList){
                foreach($goodsHelpList as $key => $val){
                    switch ($val['status']){//todo... 修改商品助手状态
                        case 0:
                            $goodsHelpList[$key]['status_name'] = '等待执行';
                            break;
                        case 1:
                            $goodsHelpList[$key]['status_name'] = '执行中';
                            break;
                        case 2:
                            $goodsHelpList[$key]['status_name'] = '执行失败';
                            break;
                        default :
                            $goodsHelpList[$key]['status_name'] = '执行失败';
                            break;
                    }
                    switch ($val['add_type']){
                        case 0:
                            $goodsHelpList[$key]['add_type'] = '商城数据包';
                            break;
                        case 1:
                            $goodsHelpList[$key]['add_type'] = '淘宝';
                            break;
                        default :
                            $goodsHelpList[$key]['add_type'] = '商城数据包';
                            break;
                    }
                    $goodsHelpList[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
                }
            }
            return $goodsHelpList;
        }
        $this->assign('delGoodsHelpUrl', __URL(call_user_func('addons_url_' . $this->module, 'goodhelper://GoodHelper/delGoodsHelp')));
        $this->assign('progressUrl', __URL(call_user_func('addons_url_' . $this->module, 'goodhelper://GoodHelper/progress')));
        return $this->fetch('/template/' . $this->module . '/progress');
    }
    
    /*
     * 删除导入任务
     */
    public function delGoodsHelp(){
        $help_id = request()->post('help_id','');
        if(!$help_id){
            return AjaxReturn(0);
        }
        
        $goodsHelpModel = new VslGoodsHelpModel();
        $checkHelp = $goodsHelpModel->getInfo(['help_id' => $help_id]);
        if(!$checkHelp || $checkHelp['status'] == 1){//todo... 修改商品助手状态
            return AjaxReturn(0);
        }
        $retval = $goodsHelpModel->delData(['help_id' => $help_id]);
        if($retval){
            @unlink($checkHelp['file_name']);
            @unlink($checkHelp['zip_name']);
            return AjaxReturn(1);
        }else{
            return AjaxReturn(0);
        }
    }

    /*
     * 商品采集接口
     */
    public function setGoodGather()
    {
        $username = request()->post('username','');
        $password = request()->post('password','');
        $con = request()->post('content','');
        $checkToken = request()->post('token','');

        $help_server = new GoodsServer;
        $token_model = new VslGoodsHelpTokenModel();
        $userModel = new User();
        $user_model = new UserModel();
        $isBase64 = request()->post('isBase64');
        // debugLog($con, 'setGoodGather1=>');
        // debugLog($isBase64, 'isBase64=>');
        if($isBase64 == null){
            $con = base64_decode($con);
            // debugLog($con, 'setGoodGather2=>');
        }
        
        /*if(!getAddons('goodhelper',$this->website_id)){
            return json(['code' => -1, 'message' => '应用已关闭']);
        }*/
        if($username){
            /*$condition = [
                'user_tel' => $username,
                'website_id' => $this->website_id
            ];*/
            $status = $help_server->login($username, $password);
            if($status > 0){
                $userInfo = $status;
            }else{
                return json(['code' => -1, 'message' => '账号或密码错误']);
            }

            if(!getAddons('goodhelper',$userInfo['website_id'])){
                return json(['code' => -1, 'message' => '应用已关闭']);
            }

            if($userInfo){
                $temp = $help_server->getModuleIdByModule("goods","addgoods",$userInfo['port']);
                $module_array = $userModel->getUserModuleIdArray(['u.uid' => $userInfo['uid']]);
                if(strpos($module_array['module_id_array'], (string)$temp['module_id']) === false){
                    return json(['code' => -1, 'message' => '权限不足']);
                }

                $token = $token_model->where(['uid' => $userInfo['uid']])->field('token')->find();
                if($token){
                    $token = $help_server->create_guid();
                    $token_model->update(['token' => $token],['uid' => $userInfo['uid']]);
                    return json(['code' => 1, 'message' => '登录成功', 'data' => $token]);
                }else{
                    $token = $help_server->create_guid();
                    $token_model->save(['uid' => $userInfo['uid'],'token' => $token]);
                    return json(['code' => 1, 'message' => '登录成功', 'data' => $token]);
                }
            }

        }

        if($checkToken){
            $uid = $token_model->where(['token' => $checkToken])->field('uid')->find();
            
            if($uid){
                $condition = [
                    'uid' => $uid['uid'],
                    'website_id' => $this->website_id
                ];
                $user_info = $user_model->getInfo($condition, $field = '*');
            }else{
                return json(['code' => -1, 'message' => 'token不存在']);
            }
            
            $content = htmlspecialchars_decode($con);
            
            $con = json_decode($content, TRUE);
           
            if($con){
                $arr = array();
                $arr['website_id'] = $user_info['website_id'];
                $arr['instance_id'] = $user_info['instance_id'];
                $regex = "/\[|\]|&quot;|\{|\}|\￥|\"/";
                //循环导入
                foreach($con as $key => $data){
                    $arr['taoId'] = $data['GoodsId'];
                    $arr['platForm'] = $data['PlatForm'];
                    $arr['url'] = urldecode($data['Url']); //保存采集商品的原始url
                    $arr['taoPrice'] = preg_replace($regex,'',$data['JsonData']['price']);
                    $arr['marketprice'] = preg_replace($regex,'',$data['JsonData']['marketprice']);
                    $arr['taoTitle'] = $data['JsonData']['title'];
                    $arr['taoStock'] = $data['JsonData']['stock'];
                    $data['JsonData']['gallery'] = preg_replace($regex,'',$data['JsonData']['gallery']);
                    $imgArr = explode(',',$data['JsonData']['gallery']);
                    foreach($imgArr as $k => $val){
                        if(strripos($val, 'tbvideo') ){
                            unset($imgArr[$k]);
                        }
                    }
                    $arr['imgArray'] = array_slice($imgArr,0,5);
                    $data['JsonData']['descprition'] = preg_replace($regex,'',$data['JsonData']['descprition']);
                    $arr['taoAttributes'] = explode(',:',$data['JsonData']['descprition']);
                    $data['JsonData']['detailImg'] = preg_replace($regex,'',$data['JsonData']['detailImg']);
                    $detail['detailMap'] = explode(',',$data['JsonData']['descprition']);
                    $detail['imgUrlMap'] = explode(',',$data['JsonData']['detailImg']);
                    $arr['description'] = $help_server->getDetail($detail);

                    $result = $help_server->addGoodsByArray($arr);
                    if (!$result) {
                        return json(['code' => -1, 'message' => $arr['taoTitle'].'上传失败']);
                    }else{
                        $this->addUserLog('上传采集商品', $arr['taoTitle']);
                    }
                }

                return json(['code' => 1, 'message' => '上传成功']);
            }else{
                return json(['code' => -1, 'message' => '数据错误']);
            }

        }else{
            return json(['code' => -1, 'message' => '数据错误']);
        }
    }
}
