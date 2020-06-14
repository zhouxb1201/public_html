<?php
namespace addons\signin\controller;

use addons\signin\Signin as baseSignin;
use addons\signin\server\Signin as SigninServer;

class Signin extends baseSignin
{
    public function __construct()
    {
        parent::__construct();

    }
    /**
     * 签到明细
     */
    public function signinList()
    {
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $search_text = input('post.search_text','');
        $signin_server = new SigninServer();
        if ($search_text) {
            $where['su.user_tel'] = ['LIKE', '%' . $search_text . '%'];
        }
        $where['vsir.website_id'] = $this->website_id;
        $where['vsir.shop_id'] = $this->instance_id;
        $field = 'vsir.*,su.user_tel,su.nick_name';
        $list = $signin_server->getSignInList($page_index, $page_size, $where ,$field, 'sign_in_time desc');
        return $list;
    }
    /**
     * 每日签到设置
     */
    public function saveSetting()
    {
        $data = input('post.');
        $signin_server = new SigninServer();
        $is_signin = (int)$data['is_signin'];
        $result = $signin_server->saveConfig($is_signin);
        if($result){
            $this->addUserLog('修改每日签到设置', $result);
        }
        setAddons('signin', $this->website_id, $this->instance_id);
        $sign_in_id = (int)$data['sign_in_id'];
        $input['data'] = $data['data'];
        $input['state'] = $data['state'];
        $input['shop_id'] = $this->instance_id;
        $input['website_id'] = $this->website_id;
        $res = $signin_server->updateSignIn($input,$sign_in_id);
        return AjaxReturn($result);
    }
    /**
     * 会员签到信息
     */
    public function userSignInInfo()
    {
        $signin_server = new SigninServer();
        $info = $signin_server->userSignInInfo();
        if($info){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $info;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
    /**
     * 会员签到列表
     */
    public function userSignInList()
    {
        $time = input('time');
        $signin_server = new SigninServer();
        $info = $signin_server->userSignInList($time);
        $data['code'] = 1;
        $data['message'] = "获取成功";
        $data['data'] = $info;
        return json($data);
    }
    /**
     * 会员签到
     */
    public function userSignIn()
    {
        $signin_server = new SigninServer();
        $re = $signin_server->userSignIn();
        return json($re);
    }
    /**
     * 会员签到明细
     */
    public function userSignInRecord()
    {
        $signin_server = new SigninServer();
        $list = $signin_server->userSignInRecord();
        if($list){
            $data['code'] = 1;
            $data['message'] = "获取成功";
            $data['data'] = $list;
        }else{
            $data['code'] = -1;
            $data['message'] = "获取失败";
        }
        return json($data);
    }
}