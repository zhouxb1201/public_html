<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:37
 */

namespace addons\poster\controller;

use addons\poster\model\PosterModel;
use data\extend\WchatOauth;
use data\model\UserModel;
use data\model\VslGoodsModel;
use data\model\WebSiteModel;
use data\model\WeixinFansModel;
use data\service\BaseService;
use data\service\Config as configServer;
use addons\poster\Poster as basePoster;
use addons\poster\service\Poster as posterService;
use data\service\Member\MemberAccount;
use data\service\Weixin;
use think\Config;
use think\Db;

class Poster extends basePoster
{
    public function __construct()
    {
        parent::__construct();
    }

    public function poster()
    {
        $poster_id = input('post.poster_id');
        $poster = $this->service->poster(['poster_id' => $poster_id, 'poster_model.website_id' => $this->website_id], ['poster_award', 'push_cover']);
//        var_dump(Db::table('')->getLastSql());
        return $poster;
    }

    /**
     * 海报列表
     */
    public function posterList()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $poster_name = input('post.poster_name', '');
        $type = input('post.type');
        $condition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ];
        if ($poster_name) {
            $condition['poster_name'] = ['like', "%" . $poster_name . "%"];
        }
        if ($type) {
            $condition['type'] = $type;
        }
        $order = 'poster_id DESC';
        $list = $this->service->posterList($page_index, $page_size, $condition, $order);

        return $list;
    }

    /**
     * 推荐列表
     */
    public function recordList()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $start_date = input('post.start_date');
        $end_date = input('post.end_date');
        $condition = [
            'pr.shop_id' => $this->instance_id,
            'pr.website_id' => $this->website_id,
            'pr.poster_id' => input('post.poster_id'),
            'pr.poster_type' => 1,
        ];
        $condition['reco'] = input('post.reco');
        $condition['be_reco'] = input('post.be_reco');
        if ($start_date) {
            $condition['scan_time'][] = ['GT', strtotime($start_date)];
        }
        if ($end_date) {
            $condition['scan_time'][] = ['LT', strtotime($end_date)];
        }

        $list = $this->service->recordList($page_index, $page_size, $condition);

        return $list;
    }

    /**
     * 模板选择
     */
    public function posterDialog()
    {
        $this->assign('posterSystemDefaultTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/posterSystemDefaultTemplate')));
        $this->assign('createPosterUrl', __URL(call_user_func('addons_url_' . $this->module, 'poster://poster/createPoster')));
        $microshop = getAddons('microshop', $this->website_id);
        $this->assign('microshop',$microshop);
        return $this->fetch('/template/' . $this->module . '/' . 'posterDialog');
    }

    /**
     * 系统默认模板列表
     * @return array
     * @throws \think\Exception\DbException
     */
    public function posterSystemDefaultTemplate()
    {
        $condition['is_system_default'] = 1;
        $condition['shop_id'] = 0;
        $condition['website_id'] = 0;
        return $this->service->systemDefaultPoster($condition);
    }

    /**
     * 新增装修页面
     */
    public function createPoster()
    {
        $poster_id = request()->post('poster_id');
        $type = request()->post('type');
        if (empty($type)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        if ($poster_id) {
            $condition['poster_id'] = $poster_id;
            $system_default_template_data = $this->service->poster($condition);
        }
        $data['poster_name'] = isset($system_default_template_data['poster_name']) ? $system_default_template_data['poster_name'] : '新建模板';
        $data['type'] = $type;
        $data['shop_id'] = $this->instance_id;
        $data['website_id'] = $this->website_id;
        $data['create_time'] = time();
        $data['modify_time'] = time();
        if (isset($system_default_template_data) && !empty($system_default_template_data['poster_data'])) {
            $data['poster_data'] = $system_default_template_data['poster_data'];
        }
        $id = $this->service->savePoster($data);
        return AjaxReturn(1, ['poster_id' => $id]);
    }

    /**
     * 保存修改的海报内容
     * @return \multitype
     */
    public function savePoster()
    {
        $data = request()->post();
        $data['modify_time'] = time();
        $award_data = $data['award_data'];
        unset($data['award_data']);
        $data['poster_data'] = json_encode($data['poster_data'], JSON_UNESCAPED_UNICODE);
        $return = $this->service->savePoster($data, $data['poster_id'], $award_data);
        return AjaxReturn($return);
    }

    /**
     * 设置默认海报
     */
    public function defaultPoster()
    {
        $poster_id = input('post.id');
        $type = input('post.type');
        $result = $this->service->defaultPoster($poster_id, $type, $this->instance_id, $this->website_id);
        return AjaxReturn($result);
    }

    /**
     * 删除海报
     */
    public function deletePoster()
    {
        $poster_id = input('post.id');
        if (empty($poster_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        return $this->service->deletePoster(['poster_id' => $poster_id, 'website_id' => $this->website_id]);
    }

    /**
     * 删除海报缓存
     */
    public function deletePosterCache()
    {
        $weixin_service = new Weixin();
        $weixin_service->deletePoster($this->website_id);
        return ajaxReturn(1);
    }
    /**
     * 前台获取海报图片
     */
    public function getKindPoster()
    {
        //1:商城海报  2: 商品海报  3: 关注海报 4: 微店海报
        $poster_type = request()->post('poster_type', 1);
        $goods_id = request()->post('goods_id', 0);
        debugFile($goods_id, $this->uid.'生成海报的时候打印商品id2', 'public/test9.txt');
        $is_mp = request()->post('is_mp');// 小程序码替换二维码
        $mp_page = request()->post('mp_page');//小程序太阳码跳转路径
        $base_service = new BaseService();
        $poster = new PosterModel();
        $goods_mdl = new VslGoodsModel();
        $condition['type'] = $poster_type;
        $condition['is_default'] = 1;
        $condition['website_id'] = $this->website_id;
        $poster = $poster->getInfo($condition, '*');
        //获取每个类型的默认数据
        if($poster_type == 2 && $goods_id){
            $is_goods_poster = $goods_mdl->getInfo(['goods_id' => $goods_id], 'is_goods_poster_open, poster_data, px_type');
            if($is_goods_poster['is_goods_poster_open'] == 1){//开启了商品独立海报
                $poster['poster_data'] = htmlspecialchars_decode($is_goods_poster['poster_data']);
                $poster['px_type'] = $is_goods_poster['px_type'];
                $poster['type'] = 2;
                $poster['poster_type'] = 'poster';
                $poster['is_mp'] = $is_mp;
                $poster['is_perm'] = 0;
                $poster['mp_page'] = $mp_page;
                $poster['poster_id'] = 'g1';
            }
        }
        if(!$poster){
            return ['code' => 0, 'message' => '该类型海报不存在！'];
        }
        $wchat_oauth = new WchatOauth($this->website_id);
        $user_mdl = new UserModel();
        $open_id = $user_mdl->getInfo(['uid'=>$this->uid, 'website_id' => $this->website_id], 'wx_openid')['wx_openid'];
        $weixin_fans = new WeixinFansModel();
        $is_subscribe = $weixin_fans->getInfo(['openid' => $open_id, 'website_id' => $this->website_id])['is_subscribe'];
        if(isset($open_id) && !empty($open_id) && $is_subscribe && isWeixin()){
            //通过open_id更新用户信息
            $wx_user_info = $wchat_oauth->get_fans_info($open_id, $this->website_id);
            $user_arr['nick_name'] = $wx_user_info['nickname'] ?: '';
            $user_arr['user_headimg'] = $wx_user_info['headimgurl'] ?: '';
            $user_arr['sex'] = $wx_user_info['sex'] ?: 0;
            $user_cond['wx_openid'] = "$open_id";
            $user_cond['website_id'] = $this->website_id;
            $user_mdl->save($user_arr, $user_cond);
        }
        $poster['poster_type'] = 'poster';
        $poster['is_mp'] = $is_mp;
        $poster['mp_page'] = $mp_page;
        $poster_res = $base_service->posterImage($poster, $poster['poster_id'], 'poster', '', $goods_id, $this->uid);
        if($poster_res['code'] == -2){
            return ['code' => 0, 'message' => $poster_res['message']];
        }
        $website_model = new WebSiteModel();
        $website_info = $website_model::get(['website_id' => $this->website_id]);
        $is_ssl = \think\Request::instance()->isSsl();
        $http = "http://";
        if($is_ssl){
            $http = 'https://';
        }
        if ($website_info['realm_ip']) {
            $domain_name = $http . $website_info['realm_ip'];
        } else {
            $domain_name = $http . $_SERVER['HTTP_HOST'];
        }
        return [
            'code' => 1,
            'message' => '获取成功',
            'data' => [
                'poster' => $domain_name.'/'.$poster_res['poster'],
            ]
        ];
    }
    public function successRecommend()
    {
        $poster = new \addons\poster\service\Poster();
        $condition['poster_id'] = 13;
        $uid = 597;
        $buid = 686;
        $this->subEvent(686,'ov9Tx0keiaBrXSlq9UXMp9GRY0Dg', 16);
//        $poster->successRecommend($condition, $uid, $buid);
    }
    public function test(){
        $memberAccount = new MemberAccount();
        $award_uid = 686;
        $v['balance'] = 10;
        $poster_info['website_id'] = 16;
        $memberAccount->addMemberAccountData(2, $award_uid, 1, $v['balance'],
            28, $poster_info['website_id'], '海报奖励,推荐得余额');
    }

    /**
     * 绑定用户查看关注事件
     * @param int $uid
     * @param string $open_id 新用户的open_id
     * @param int $website_id
     */
    public function subEvent($uid, $open_id, $website_id)
    {
        $fans_model = new WeixinFansModel();
        $fan_info = $fans_model::get(['openid' => $open_id, 'website_id' => $website_id]);
        if (empty($fan_info)) {
            return ['code' => -1, 'message' => '空的信息'];
        }
        if (!empty($fan_info['source_uid']) && !empty($fan_info['scene']) && !empty($fan_info['scene_id'])) {
            switch ($fan_info['scene']) {
                case 'poster':
                    $poster_service = new \addons\poster\service\Poster();
                    $poster_service->successRecommend(['poster_id' => $fan_info['scene_id']], $fan_info['source_uid'], $uid);
                    break;
                case 'task_poster':
                    $task_poster_service = new Taskcenter();
                    $task_poster_service->successRecommend(['general_poster_id' => $fan_info['scene_id']], $fan_info['source_uid'], $uid);
                    break;
                default:
                    return ['code' => -1, 'message' => '未定义的场景'];
            }
        }
    }
}