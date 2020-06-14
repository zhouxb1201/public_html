<?php

namespace addons\thingcircle\controller;

use data\service\Goods as GoodsService;
use addons\thingcircle\model\ThingCircleCollectionModel;
use addons\thingcircle\model\ThingCircleCommentModel;
use addons\thingcircle\model\ThingCircleModel;
use addons\thingcircle\model\ThingCircleReportCommentModel;
use addons\thingcircle\model\ThingCircleTopicModel;
use addons\thingcircle\model\VslThingCircleRecordsModel;
use data\service\Goods;
use data\service\Address;
use think\Validate;
use addons\thingcircle\Thingcircle as baseThingcircle;
use addons\thingcircle\server\Thingcircle as ThingcircleServer;
use addons\thingcircle\model\ThingCircleViolationModel;
use data\service\User;
use data\service\Album;
use data\model\VslOrderGoodsModel;
use data\model\AlbumPictureModel;
use data\model\AddonsConfigModel;
use data\model\VslUserFollowModel;
use data\model\VslUserLikesModel;
use data\model\UserModel;

class Thingcircle extends baseThingcircle
{
    public function __construct()
    {
        parent::__construct();

    }

    public function thingcircleList()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $search_user = input('post.search_user', '');
        $search_text = input('post.search_text', '');
        $create_starttime = input('post.create_starttime', '');
        $create_endtime = input('post.create_endtime', '');
        $thing_server = new ThingcircleServer();
        $condition['tc.website_id'] = $this->website_id;
        if ($this->module == 'admin') {
            $condition['tc.shop_id'] = $this->instance_id;
        }
        if ($search_user) {
            $condition['u.user_name|u.user_tel|u.nick_name'] = ['LIKE', '%' . $search_user . '%'];
        }
        if ($search_text) {
            $condition['tct.topic_title|tc.title|tc.content'] = ['LIKE', '%' . $search_text . '%'];
        }
        if ($create_starttime || $create_endtime) {
            $condition['tc.create_time'] = array(array('egt', strtotime($create_starttime)), array('elt', strtotime($create_endtime)));
        }
        $list = $thing_server->getThingCircleLists($page_index, $page_size, $condition);
        foreach ($list['data'] as $value) {
            $value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
        }
        return $list;
    }

    /**
     * 话题列表
     */
    public function selectTopicList()
    {
        if (request()->post('page_index')) {
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text');
            if ($search_text) {
                $condition['topic_title'] = ['LIKE', '%' . $search_text . '%'];
            }
            //$condition['tct.website_id'] = $this->website_id;
            //$condition['ng.state'] = 1;
            $thing_server = new ThingcircleServer();
            $setting = $thing_server->getThingcircleSite($this->website_id);
            if($setting['topic_state'] == 0){
                $condition['superiors_id'] = 0;
            }
            
            $list = $thing_server->getTopicList($page_index, $page_size, $condition, $order = 'topic_id desc');
            return $list;
        }
        $this->fetch('template/' . $this->module . '/topicList');
    }

    /**
     * 获取省列表
     */
    public function getProvince()
    {
        $address = new Address();
        $province_list = $address->getProvinceList();
        return $province_list;
    }

    /**
     * 获取城市列表
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:, array>
     */
    public function getCity()
    {
        $address = new Address();
        $province_id = isset($_POST['province_id']) ? $_POST['province_id'] : 0;
        $city_list = $address->getCityList($province_id);
        return $city_list;
    }

    /**
     * 获取区域地址
     */
    public function getDistrict()
    {
        $address = new Address();
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        $district_list = $address->getDistrictList($city_id);
        return $district_list;
    }

    /**
     * 设置
     */
    public function thingcircleSetting()
    {
        try {
            $post_data = request()->post();
            $uid = $post_data['uid'];
            if(!$post_data['uid']){
                return ['code' => -1, 'message' => '请选择会员'];
            }
            $user = new User();
            $user_info = $user->getUserInfoByUid($uid);
            if(empty($user_info)){
                return ['code' => -1, 'message' => '会员信息错误，重新选择会员'];
            }
            $condition = [];
            $condition['uid'] = $uid;
            $condition['website_id'] = $this->website_id;
            $condition['is_member'] = 1;
            if(empty($user_info['user_tel'])){
                $user_tel = $post_data['user_tel'];
                $update_data = ['user_tel' => $user_tel];
                $user->updateUserNew($update_data, $condition);
            }
            if(empty($user_info['user_password'])){
                $user_password = $post_data['user_password'];
                $user->updatePassword($user_password, $condition);
            }
            $is_thing = $post_data['is_use'];
            unset($post_data['user_tel'],$user_info['user_password']);
            $addons_config_model = new AddonsConfigModel();
            $group_shopping_info = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => 'thingcircle']);
            if (!empty($group_shopping_info)) {
                $res = $addons_config_model->save(
                    [
                        'is_use' => $is_thing,
                        'modify_time' => time(),
                        'value' => json_encode($post_data, JSON_UNESCAPED_UNICODE)
                    ],
                    [
                        'website_id' => $this->website_id,
                        'addons' => 'thingcircle'
                    ]
                );
            } else {
                $data['is_use'] = $is_thing;
                $data['value'] = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                $data['desc'] = '好物圈设置';
                $data['create_time'] = time();
                $data['addons'] = 'thingcircle';
                $data['website_id'] = $this->website_id;
                $res = $addons_config_model->save($data);
            }
            if ($res) {
                $this->addUserLog('好物圈设置', $res);
            }
            setAddons('thingcircle', $this->website_id, $this->instance_id);
            setAddons('thingcircle', $this->website_id, $this->instance_id, true);
            return ['code' => $res, 'message' => '修改成功'];
        } catch (\Exception $e) {
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }

    public function delThingcircle()
    {
        $id = isset($_POST['id']) ? $_POST['id'] : '';
        $thing_model = new ThingCircleModel();
        $res = $thing_model->where(['id' => $id])->delete();
        return AjaxReturn($res);
    }

    public function addThingcircleTopic()
    {
        $validate = new Validate([
            '__token__' => 'token',
        ]);
        if (!$validate->check(request()->post())) {
            return ['code' => -1, 'message' => $validate->getError()];
        }
        $condition['topic_title'] = request()->post('topic_title', '');
        $condition['superiors_id'] = request()->post('pid', '');
        $condition['sort'] = request()->post('sort', '');
        $condition['state'] = request()->post('state', '');
        $condition['topic_pic'] = request()->post('topic_pic', '');
        $condition['create_time'] = time();

        $topic_model = new ThingCircleTopicModel();
        $res = $topic_model->save($condition);
        return AjaxReturn($res);

    }

    public function changeTopicState()
    {
        $topic_model = new ThingCircleTopicModel();
        $topic_id = request()->post('topic_id', 0);
        $state = request()->post('state', 0);
        $bool = $topic_model->where(['topic_id' => $topic_id])->update(['state' => $state]);
        return AjaxReturn($bool);
    }

    public function updateThingcircleTopic()
    {
        $topic_id = request()->post('topic_id', '');
        $condition['topic_title'] = request()->post('topic_title');
        $condition['superiors_id'] = request()->post('pid');
        $condition['sort'] = request()->post('sort');
        $condition['state'] = request()->post('state');
        $condition['topic_pic'] = request()->post('topic_pic');
        $topic_model = new ThingCircleTopicModel();
        $res = $topic_model->where(['topic_id' => $topic_id])->update($condition);
        return AjaxReturn($res);
    }

    public function delThingcircleTopic()
    {
        $topic_id = request()->post('topic_id', '');

        $topic_model = new ThingCircleTopicModel();
        $res = $topic_model->where(['topic_id' => $topic_id])->delete();
        return AjaxReturn($res);
    }

    public function thingcircleViolationList()
    {
        $violation_model = new ThingcircleViolationModel();
        $condition = array(
            'website_id' => $this->website_id,
        );
        $orderBy = 'sort asc';
        $list = $violation_model->getThingCircleViolationList(1, 0, $condition, $orderBy);
        return $list;
    }

    public function addThingcircleViolation()
    {
        $data['name'] = request()->post('name', '');
        $data['sort'] = request()->post('sort', 0);
        $thing_server = new ThingcircleServer();
        $result = $thing_server->addViolation($data);
        if ($result > 0) {
            $this->addUserLog('添加违规类型', $result);
        }
        return AjaxReturn($result);
    }

    public function changeThingcircleViolationName()
    {
        $thing_server = new ThingcircleServer();
        $violation_id = request()->post('violation_id', 0);
        $name = request()->post('name', '');
        $result = $thing_server->updateViolationName($violation_id, $name);
        if ($result > 0) {
            $this->addUserLog('修改违规名称', $result);
        }
        return AjaxReturn($result);
    }

    public function changeThingcircleViolationSort()
    {
        $thing_server = new ThingcircleServer();
        $violation_id = request()->post('violation_id', 0);
        $sort = request()->post('sort', '');
        $result = $thing_server->updateViolationSort($violation_id, $sort);
        if ($result > 0) {
            $this->addUserLog('修改违规排序', $result);
        }
        return AjaxReturn($result);
    }

    public function deleteThingcircleViolation()
    {
        $violationId = request()->post("violation_id", 0);
        if (!$violationId) {
            return AjaxReturn(0);
        }
        $thing_server = new ThingcircleServer();
        $retval = $thing_server->deleteViolation($violationId);
        if ($retval <= 0) {
            return AjaxReturn($retval);
        }
        $this->addUserLog('删除违规类型', $retval);
        return AjaxReturn(1);
    }

    public function changeThingcircleViolationShow()
    {
        $thing_server = new ThingcircleServer();
        $violation_id = request()->post('violation_id', 0);
        $state = request()->post('state', '');
        $res = $thing_server->updateViolationShow($violation_id, $state);
        if ($res > 0) {
            $this->addUserLog('修改分类是否显示', $res);
        }
        return AjaxReturn($res);
    }

    public function thingcircleViolationCommentList()
    {
        $thing_server = new ThingcircleServer();
        if (request()->isAjax()) {
            $page_index = input('post.page_index', 1);
            $page_size = input('post.page_size', PAGESIZE);
            $search_user = input('post.search_user', '');
            $search_text = input('post.search_text', '');
            $condition['tcrc.state'] = input('post.state', '');
            if ($condition['tcrc.state'] == 0) {
                $condition['tcrc.state'] = 1;
            }
            $condition['tcrc.website_id'] = $this->website_id;
            if ($search_user) {
                $condition['u.user_name'] = ['LIKE', '%' . $search_user . '%', 'or'];
                $condition['u.user_tel'] = ['LIKE', '%' . $search_user . '%', 'or'];
            }
            if ($search_text) {
                //$condition['tcc.report_reason'] = ['LIKE', '%' . $search_text . '%', 'or'];
                $condition['tcrc.content'] = ['LIKE', '%' . $search_text . '%', 'or'];
            }
            $list = $thing_server->getThingCircleReportCommentLists($page_index, $page_size, $condition, 'report_time desc');
            return $list;
        } else {
            $state = request()->get('state', '0');

            $this->assign("state", $state);
            $this->fetch('template/' . $this->module . '/thingcircleViolationCommentList');
        }

    }

    public function delThingcircleComment()
    {
        $ids = $_POST["comment_ids"];
        if (!$ids) {
            return AjaxReturn(0);
        }
        $thing_model = new ThingcircleServer();
        $retval = $thing_model->changeReportStateByCommentId($ids, 3);
        $res = $thing_model->changeCommentState($ids, 4);
        if ($res) {
            $comment = $thing_model->getCommentById(1, PAGESIZE, ['tcc.id' => $ids]);
            foreach ($comment['data'] as $value) {
                $content = $value['content'];
                $comment_uid = $value['from_uid'];
            }

            $thing_model->addReportMsg($comment_uid, $content);

            $report_condition['rc.comment_id'] = $ids;
            $report_condition['rc.state'] = 3;
            $report = $thing_model->getReportById($report_condition);

            foreach ($report as $value) {
                $name = $value['name'];
                $report_uid = $value['report_uid'];

                $thing_model->addReportSuccessMsg($report_uid, $name);
            }

        }
        $this->addUserLog('删除违规评论', $res);
        return AjaxReturn($retval);
    }

    public function ignThingcircleComment()
    {
        $ids = $_POST["report_ids"];
        if (!$ids) {
            return AjaxReturn(0);
        }
        $thing_model = new ThingcircleServer();
        $retval = $thing_model->changeReportState($ids, 2);
        $this->addUserLog('忽略违规举报', $retval);
        return AjaxReturn($retval);
    }

    public function deleteThingcircleComment()
    {
        $ids = $_POST["comment_ids"];
        if (!$ids) {
            return AjaxReturn(0);
        }
        $thing_model = new ThingcircleServer();
        $retval = $thing_model->deleteReportComment($ids);
        $res = $thing_model->deleteComment($ids);
        $this->addUserLog('彻底删除违规评论', $res);
        return AjaxReturn($retval);
    }

    public function recThingcircleComment()
    {
        $ids = $_POST["comment_ids"];
        if (!$ids) {
            return AjaxReturn(0);
        }
        $thing_model = new ThingcircleServer();
        $retval = $thing_model->changeReportStateByCommentId($ids, 1);
        $res = $thing_model->changeCommentState($ids, 1);
        $this->addUserLog('恢复违规评论', $res);
        return AjaxReturn($retval);
    }

    public function addThingcircle()
    {
        $condition = [];
        if($this->module == 'platform'){
            $thing_server = new ThingcircleServer();
            $setting = $thing_server->getThingcircleSite($this->website_id);
            if(empty($setting['uid'])){
                return ['code' => -1, 'message' => '设置没有选择会员，不能添加'];
            }
            $condition['user_id'] = $setting['uid'];
        }else{
            $condition['user_id'] = $this->uid;
        }
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $condition['thing_type'] = input('post.thing_type','');
        $condition['title'] = input('post.thing_title','');
        $condition['content'] = input('post.content','');
        if ($condition['thing_type'] == 1) {
            $img_list = input('post.img_id/a');
            $img = '';
            if ($img_list) {
                foreach ($img_list as $k => $value) {
                    $img .= $value . ',';
                }
            }
            $condition['media_val'] = substr($img, 0, -1);
        } else {
            $condition['media_val'] = input('post.img_id','');
            $goods_img = new AlbumPictureModel();
            $goods_img_info = $goods_img->getInfo(['pic_id'=>$condition['media_val']],'pic_cover');
            $thing_server = new ThingcircleServer();
            if(!empty($goods_img_info['pic_cover'])){
                $res = $thing_server->ffmpeg($goods_img_info['pic_cover']);
                if($res['code'] == 1){
                    $condition['video_img'] = json_encode($res['data'], JSON_UNESCAPED_UNICODE);
                }
            }
        }
        $condition['lat'] = input('post.lat','');
        $condition['lng'] = input('post.lng','');
        $condition['location'] = input('post.location','');
        $condition['topic_id'] = input('post.topic_id','');
        $condition['recommend_goods'] = input('post.goods_array','');
        $condition['create_time'] = time();
        if($condition['lat'] && $condition['lng'] && empty($condition['location'])){
            $location = $condition['lat'].','.$condition['lng'];
            $region = getLocationByLatLng($location);
            if(!empty($region['result']['formatted_address'])){
                $condition['location'] = $region['result']['formatted_address'];
            }
        }
        $thing_model = new ThingCircleModel();
        $res = $thing_model->save($condition);
        if ($res && $this->module == 'admin') {
            $thing_server = new ThingcircleServer();
            $config = $thing_server->getThingcircleSite($this->website_id);
            //发布干货
            if ($config['release_thing'] == 1) {
                $thing_server->giveReward($res,$condition['user_id'],$condition['website_id'],$config['release_point'],$config['release_growth_num'],$config['release_coupon_type_id'],$config['release_gift_voucher_id'],0);
            }
        }
        return AjaxReturn($res);
    }

    public function updateThingcircle()
    {
        $id = input('post.id','');
        $condition = [];
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $condition['thing_type'] = input('post.thing_type','');
        $condition['title'] = input('post.thing_title','');
        $condition['content'] = input('post.content','');
        if ($condition['thing_type'] == 1) {
            $img_list = input('post.img_id/a');
            $img = '';
            if ($img_list) {
                foreach ($img_list as $k => $value) {
                    $img .= $value . ',';
                }
            }
            $condition['media_val'] = substr($img, 0, -1);
        } else {
            $condition['media_val'] = input('post.img_id','');
            $goods_img = new AlbumPictureModel();
            $goods_img_info = $goods_img->getInfo(['pic_id'=>$condition['media_val']],'pic_cover');
            $thing_server = new ThingcircleServer();
            if(!empty($goods_img_info['pic_cover'])){
                $res = $thing_server->ffmpeg($goods_img_info['pic_cover']);
                if($res['code'] == 1){
                    $condition['video_img'] = json_encode($res['data'], JSON_UNESCAPED_UNICODE);
                }
            }
        }
        $condition['lat'] = input('post.lat','');
        $condition['lng'] = input('post.lng','');
        $condition['location'] = input('post.location','');
        $condition['topic_id'] = input('post.topic_id','');
        $condition['recommend_goods'] = input('post.goods_array','');  
        if($condition['lat'] && $condition['lng'] && empty($condition['location'])){
            $location = $condition['lat'].','.$condition['lng'];
            $region = getLocationByLatLng($location);
            if(!empty($region['result']['formatted_address'])){
                $condition['location'] = $region['result']['formatted_address'];
            }
        }
        $thing_model = new ThingCircleModel();
        $res = $thing_model->save($condition, ['id' => $id]);
        return AjaxReturn($res);
    }

    /**
     *计算某个经纬度的周围某段距离的正方形的四个点
     *
     *@param lng float 经度
     *@param lat float 纬度
     *@param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为5千米
     *@return array 正方形的四个点的经纬度坐标
     */
    function returnSquarePoint($lng, $lat, $distance = 5)
    {
        $dlng = 2 * asin(sin($distance / (2 * 6371)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        $dlat = $distance / 6371;//地球半径，平均半径为6371km
        $dlat = rad2deg($dlat);
        return array(
            'left-top' => array('lat' => $lat + $dlat, 'lng' => $lng - $dlng),
            'right-top' => array('lat' => $lat + $dlat, 'lng' => $lng + $dlng),
            'left-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng - $dlng),
            'right-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng + $dlng)
        );
    }

    public function dealImg($imgArray)
    {
        @ini_set('default_socket_timeout', 2);
        $img = array('picture' => 0, 'img_id_array' => []);
        if (!$imgArray) {
            return $img;
        }
        $timeout = array(
            'http' => array(
                'timeout' => 5//设置一个超时时间，单位为秒
            )
        );
        $ctx = stream_context_create($timeout);
        $albumSer = new Album();
        $album_id = $albumSer->getDefaultThingCircleAlbum()['album_id'];
        foreach ($imgArray as $key => $val) {
            //$file = substr($val, 0, strripos($val, '_'));
            $file = $val;
            if (!strstr($file, 'http')) {
                $file = 'https:' . $file;
            } else {
                $file = substr($file, strripos($file, 'http'));
            }
            if (!$file /*|| !$this->checkImg($file)*/) {
                continue;
            }
            $fileInfo = pathinfo($file);
            if ($fileInfo['extension'] == 'SS2') {
                $fileInfo['extension'] = 'jpg';
            }
            $imageSize = getimagesize($file);
            $filename = $val;
            $albumPic = new AlbumPictureModel();
            $checkPic = $albumPic->getInfo(['pic_name' => $filename, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id], 'pic_id');
            if (strripos($file, '.jpg_')) {
                $file = substr($file, 0, strripos($file, '.jpg_') + 4);
            }
            if ($checkPic) {
                $picId = $checkPic['pic_id'];
            } /*elseif ($fileInfo['extension'] == 'jpg') {
                $picId = $albumSer->addPicture($filename, $filename, $album_id, $file, $imageSize[0] . ',' . $imageSize[1], $imageSize[0] . ',' . $imageSize[1], $file . '_800x800.' . $fileInfo['extension'], '800,800', '800,800', $file . '_400x400.' . $fileInfo['extension'], '400,400', '400,400', $file . '_200x200.' . $fileInfo['extension'], '200,200', '200,200', $file . '_100x100.' . $fileInfo['extension'], '100,100', '100,100', $this->instance_id, 1, '', '');
            } */ else {
                $picId = $albumSer->addPicture($filename, $filename, $album_id, $file, $imageSize[0] . ',' . $imageSize[1], $imageSize[0] . ',' . $imageSize[1], $file, '--', '--', $file, '--', '--', $file, '--', '--', $file, '--', '--', $this->instance_id, 1, '', '');
            }
            if (!$key) {
                $img['picture'] = $picId;
            }
            $img['img_id_array'][] = $picId;
        }
        if ($img['img_id_array']) {
            $img['img_id_array'] = implode(',', $img['img_id_array']);
        }
        return $img;
    }
    
    /**
     * 获取评论列表
     */
    function getCommentList(){
        $id = (int)input('post.id');
        $page_index = input('post.page_index',1);
        $page_size = input('post.page_size',PAGESIZE);
        $condition = [];
        $condition['tcc.thing_id'] = $id;
        $condition['tcc.state'] = ['neq',4];
        $condition['tcc.comment_pid'] = 0;
        $condition['tcc.website_id'] = $this->website_id;
        
        $thing_server = new ThingcircleServer();
        $list = $thing_server->getThingCircleCommentLists($page_index, $page_size, $condition);
        if(!empty($list['data'])){
            foreach($list['data'] as $k => $v){
                $condition = [];
                $condition['tcc.comment_pid'] = $v['id'];
                $condition['tcc.state'] = ['neq',4];
                $condition['tcc.website_id'] = $this->website_id;
                $list['data'][$k]['reply_list'] = $thing_server->getReplyCommentList($condition,1);
            }
        }
        return $list;
    }
    
    /**
     * 获取评论列表
     */
    function getReplyList(){
        $id = (int)input('post.id');
        $condition = [];
        $condition['tcc.comment_pid'] = $id;
        $condition['tcc.state'] = ['neq',4];
        $condition['tcc.website_id'] = $this->website_id;
        $thing_server = new ThingcircleServer();
        $list = $thing_server->getReplyCommentList($condition);
        return $list;
    }

    /********************************接口*******************************************/

    /**
     * 获取干货列表
     */
    public function getThingcircleList()
    {
        $thing_server = new ThingcircleServer();
        $setting = $thing_server->getThingcircleSite($this->website_id);

        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $search_text = input('post.search_text', '');
        $condition = [];
        $lat = input('post.lat', '');
        $lng = input('post.lng', '');
        if ($lat && $lng) {
            $squares = $this->returnSquarePoint($lng, $lat, 5);
            $condition['tc.lat'] = array(['gt',$squares['right-bottom']['lat']],['lt',$squares['left-top']['lat']],'and');
            $condition['tc.lng'] = array(['gt',$squares['left-top']['lng']],['lt',$squares['right-bottom']['lng']],'and');
        }
        if ($search_text) {
            $condition['tct.topic_title|tc.title|tc.content'] = ['LIKE', '%' . $search_text . '%'];
        }
        $follow = input('post.follow', '');
        $condition['tc.website_id'] = $this->website_id;
        $user_server = new User();
        $userinfo = $user_server->getUserInfo();
        
        if (!$userinfo['thing_circle_uid']) {
            $thing_uid = $thing_server->autoId();
            $user_server->addUserInfoThingcircleId($thing_uid, $userinfo['uid']);
        }

        if ($follow == 1) {
            $attention['uid'] = $userinfo['uid'];

            $arr = $thing_server->getAttentionArray($attention);
            $condition['tc.user_id'] = ['in', $arr];
        }
        $res = $thing_server->getThingCircleLists($page_index, $page_size, $condition);

        foreach ($res['data'] as $value) {
            $comment_condition['tcc.thing_id'] = $value['id'];
            $comment_condition['tcc.comment_pid'] = 0;
            $comment_condition['tcc.state'] = ['neq',4];
            $value['comment'] = $thing_server->getCommentById(1, 10, $comment_condition);
        }

        $res['display_model'] = $setting['display_model'];

        return json(['code' => 1, 'message' => '获取成功', 'data' => $res]);
    }

    /**
     * 用户关注
     */
    public function attentionThingcircle()
    {
        if (empty($this->uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $uid = $this->uid;
        $follow_uid = input('post.thing_auid', '');
        $condition = [];
        $condition['uid'] = $uid;
        $condition['follow_uid'] = $follow_uid;
        $condition['follow_type'] = 1;
        $thing_server = new ThingcircleServer();
        $res = $thing_server->getAttentionInfo($condition);
        $user_follow = new VslUserFollowModel();
        $user = new UserModel();
        if ($res) {
            $status = ($res['status']==1)?0:1;
            $result = $user_follow->where($condition)->update(['status' => $status, 'update_time' => time()]);
        } else {
            $status = 1;
            $condition['create_time'] = time();
            $result = $user_follow->save($condition);
        }
        if($result){
            if($status==1){
                $user->where(['uid'=>$uid,'website_id'=>$this->website_id])->setInc('focus_num');
                $user->where(['uid'=>$follow_uid,'website_id'=>$this->website_id])->setInc('fans_num');
            }else{
                $user->where(['uid'=>$uid,'website_id'=>$this->website_id])->setDec('focus_num');
                $user->where(['uid'=>$follow_uid,'website_id'=>$this->website_id])->setDec('fans_num');
            }
            return json(['code' => 1, 'message' => ($status==1)?'关注成功':'取消关注成功']);
        }else{
            return json(['code' => -1, 'message' => '操作失败']);
        }
    }

    /**
     * 获取我的干货信息
     */
    public function getThingcircleUser()
    {
        if (empty($this->uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $user_server = new User();
        $userinfo = $user_server->getUserInfo();
        $thing_server = new ThingcircleServer();
    //    $userinfo['attentions'] = $thing_server->getAttentionCount($userinfo['uid']);
    //    $userinfo['fans'] = $thing_server->getFansCount($userinfo['uid']);
        $userinfo['likes'] = $thing_server->getLikesCount($userinfo['uid']);

        $res['uid'] = $userinfo['uid'];
        //获取用户名规则
        $res['thing_user_name'] = ($userinfo['nick_name']) ? $userinfo['nick_name'] : ($userinfo['user_name'] ? $userinfo['user_name'] : ($userinfo['user_tel'] ? $userinfo['user_tel'] : $userinfo['uid']));
        $res['user_status'] = $userinfo['user_status'];
        $res['user_headimg'] = __IMG($userinfo['user_headimg']);
        $res['user_tel'] = $userinfo['user_tel'];
        $res['attentions'] = $userinfo['focus_num'];
        $res['fans'] = $userinfo['fans_num'];
        $res['likes'] = $userinfo['likes'];
        $res['thing_circle_uid'] = $userinfo['thing_circle_uid'];

        return json(['code' => 1, 'message' => '获取成功', 'data' => $res]);
    }

    /**
     * 用户点赞干货
     */
    public function likesThingcircle()
    {
        if (empty($this->uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $thing_id = input('post.thing_id', '');
        $website_id = $this->website_id;
        $condition = [];
        $condition['user_id'] = $this->uid;
        $condition['type_id'] = $thing_id;
        $condition['type'] = 1;
        $thing_model = new ThingCircleModel();
        $thing_info = $thing_model->getInfo(['id'=>$thing_id],'user_id');
        if(empty($thing_info)){
            return json(['code' => 1, 'message' => '操作失败']);
        }
        $condition['to_uid'] = $thing_info['user_id'];

        $thing_server = new ThingcircleServer();
        $config = $thing_server->getThingcircleSite($website_id);
        $res = $thing_server->getLikesInfo($condition);
        $likes_model = new VslUserLikesModel();
        if ($res > 0) {
            if ($res['status'] == 1) {
                $result = $likes_model->where($condition)->update(['status' => 0, 'update_time' => time()]);
                $message = '取消点赞成功';
            } else {
                $result = $likes_model->where($condition)->update(['status' => 1, 'update_time' => time()]);
                $message = '点赞成功';
            }
        } else {
            $condition['create_time'] = time();
            $result = $likes_model->save($condition);
            if ($result) {
                $thing = $thing_model->where(['id' => $condition['type_id']])->find();
                //点赞干货
                if ($config['like_thing'] == 1) {
                    $thing_server->giveReward($thing_id,$condition['user_id'],$website_id,$config['like_point'],$config['like_growth_num'],$config['like_coupon_type_id'],$config['like_gift_voucher_id'],2);
                }
                
                //干货达到多少赞
                if ($config['thing_likes'] == 1) {
                    $thingcircle_records = new VslThingCircleRecordsModel();
                    $record_info = $thingcircle_records->getRecordDetail(['food_id'=>$thing_id,'uid'=>$this->uid,'website_id'=>$website_id,'reward_type'=>4]);
                    if($thing['likes'] + 1 >= intval($config['thing_likes_num']) && empty($record_info)){
                        $thing_server->giveReward($thing_id,$condition['to_uid'],$website_id,$config['thing_likes_point'],$config['thing_likes_growth_num'],$config['thing_likes_coupon_type_id'],$config['thing_likes_gift_voucher_id'],4);
                    }
                }
            }
            $message = '点赞成功';
        }
        if($result){
            //同步点赞数
            $like_count = $thing_server->getLikeCount(['type_id'=>$thing_id,'type'=>1,'status'=>1]);
            $thing_model->where(['id' => $thing_id])->setField('likes',$like_count);
            return json(['code' => 1, 'message' => $message,'count'=>$like_count]);
        }
        return json(['code' => -1, 'message' => '操作失败']);
    }

    /**
     * 用户点赞评论
     */
    public function likesThingcircleComment()
    {
        if (empty($this->uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $comment_id = input('post.comment_id', '');
        $condition = [];
        $condition['user_id'] = $this->uid;
        $condition['type_id'] = $comment_id;
        $condition['type'] = 2;
        
        $comment_model = new ThingCircleCommentModel();
        $comment_info = $comment_model->getInfo(['id'=>$comment_id],'from_uid');
        if(empty($comment_info)){
            return json(['code' => 1, 'message' => '操作失败']);
        }
        $condition['to_uid'] = $comment_info['from_uid'];
        
        $thing_server = new ThingcircleServer();
        $res = $thing_server->getLikesInfo($condition);
        $likes_model = new VslUserLikesModel();
        if ($res > 0) {
            if ($res['status'] == 1) {
                $result = $likes_model->where($condition)->update(['status' => 0, 'update_time' => time()]);
                $message = '取消点赞成功';
            } else {
                $result = $likes_model->where($condition)->update(['status' => 1, 'update_time' => time()]);
                $message = '点赞成功';
            }
        } else {
            $condition['create_time'] = time();
            $result = $likes_model->save($condition);
            $message = '点赞成功';
        }
        if($result){
            //同步点赞数
            $like_count = $thing_server->getLikeCount(['type_id'=>$comment_id,'type'=>2,'status'=>1]);
            $comment_model->where(['id' => $comment_id])->setField('comment_likes',$like_count);
            return json(['code' => 1, 'message' => $message,'count'=>$like_count]);
        }
        return json(['code' => -1, 'message' => '操作失败']);
    }

    /**
     * 用户收藏干货
     */
    public function collectionThingcircle()
    {
        if (empty($this->uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $thing_id = input('post.thing_id', '');
        $condition = [];
        $condition['user_id'] = $this->uid;
        $condition['thing_id'] = $thing_id;
        
        $thing_model = new ThingCircleModel();
        $thing_info = $thing_model->getInfo(['id'=>$thing_id],'user_id');
        if(empty($thing_info)){
            return json(['code' => 1, 'message' => '操作失败']);
        }
        $condition['to_uid'] = $thing_info['user_id'];
        
        $thing_server = new ThingcircleServer();
        $res = $thing_server->getCollectionInfo($condition);
        $collection_model = new ThingCircleCollectionModel();
        if ($res > 0) {
            if ($res['status'] == 1) {
                $result = $collection_model->where($condition)->update(['status' => 0, 'update_time' => time()]);
                $message = '取消收藏成功';
            } else {
                $result = $collection_model->where($condition)->update(['status' => 1, 'update_time' => time()]);
                $message = '收藏成功';
            }
        } else {
            $condition['create_time'] = time();
            $result = $collection_model->save($condition);
            $message = '收藏成功';
        }
        if($result){
            //同步收藏数
            $collect_count = $thing_server->getCollectCount(['thing_id'=>$thing_id,'status'=>1]);
            $thing_model->where(['id' => $thing_id])->setField('collects',$collect_count);
            return json(['code' => 1, 'message' => $message,'count'=>$collect_count]);
        }
        return json(['code' => -1, 'message' => '操作失败']);
    }

    /**
     * 获取干货内容
     */
    public function getThingcircleDetail()
    {
        $thing_id = input('post.thing_id', '');
        $uid = input('post.uid','');
        $uid = base64_decode($uid);
        
        //分享
        if($uid && is_numeric($uid) && $uid != $this->uid){
            $this->shareAward($uid, $thing_id);
        }
        $condition = [];
        $condition['tcc.thing_id'] = $thing_id;
        $condition['tcc.comment_pid'] = 0;

        $thing_server = new ThingcircleServer();
        $thing_model = new ThingCircleModel();

        $thing_model->where(['id' => $condition['tcc.thing_id']])->setInc('reading_volumes');
        $thing_info = $thing_server->getThingcircleById(['tc.id'=>$condition['tcc.thing_id']]);
        
        $list = [];
        
        if($thing_info['recommend_goods']){
            $goods_condition['goods_id'] = ['in', $thing_info['recommend_goods']];
            
            $goods = new Goods();
            $res = $goods->getGoodsInfo($goods_condition);
            if ($res) {
                foreach ($res as $k => $value) {
                    $list[$k]['goods_id'] = $value['goods_id'];
                    $list[$k]['goods_name'] = $value['goods_name'];
                    $list[$k]['price'] = $value['price'];
                    $list[$k]['market_price'] = $value['market_price'];
                    $list[$k]['state'] = $value['state'];
                    $thing_server = new ThingcircleServer();
                    $every_goods_info = $thing_server->getEveryGoodsInfo($value['goods_id']);
                    $list[$k]['goods_img'] = getApiSrc($every_goods_info->pic_cover_small);
                }
            }
        }
        
        $thing_info['reply_count'] = $thing_server->countCommentById(['thing_id'=>$thing_id,'comment_pid'=>0]);
        
        $thing_info['recommend_goods_list'] = $list;

        if ($thing_info) {
            return json(['code' => 1, 'message' => '获取成功', 'data' => $thing_info]);
        } else {
            return AjaxReturn(-1);
        }
    }

    /**
     * 获取评论回复
     */
    public function getThingcircleReply()
    {
        $condition = [];
        $condition['tcc.thing_id'] = input('post.thing_id', '');
        $condition['tcc.comment_pid'] = input('post.comment_pid', '');
        $condition['tcc.state'] = ['neq',4];
        $thing_server = new ThingcircleServer();
        $reply_list = $thing_server->getReplyCommentList($condition);

        if ($reply_list) {
            return json(['code' => 1, 'message' => '获取成功', 'data' => $reply_list]);
        } else {
            return AjaxReturn(-1);
        }
    }

    /**
     * 用户评论干货
     */
    public function pushThingcircleComment()
    {
        if (empty($this->uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $condition = [];
        $thing_id = input('post.thing_id');
        
        $condition['thing_id'] = $thing_id;
        $condition['from_uid'] = $this->uid;
        $condition['topic_id'] = input('post.topic_id', '');
        $condition['content'] = input('post.content', '');
        $condition['create_time'] = time();
        $condition['website_id'] = $this->website_id;
        
        $thing_model = new ThingCircleModel();
        $thing_info = $thing_model->getInfo(['id'=>$thing_id],'user_id');
        if(empty($thing_info)){
            return json(['code' => 1, 'message' => '操作失败']);
        }
        $condition['to_uid'] = $thing_info['user_id'];

        $thing_server = new ThingcircleServer();
        $res = $thing_server->addComment($condition);

        if ($res) {
            //同步评论数
            $thing_model = new ThingCircleModel();
            $like_count = $thing_server->getCommentCount(['thing_id'=>$condition['thing_id'],'state'=>1]);
            $thing_model->where(['id' => $condition['thing_id']])->setField('evaluates',$like_count);
            return json(['code' => 1, 'message' => '添加成功']);
        } else {
            return AjaxReturn(-1);
        }
    }

    /**
     * 用户回复
     */
    public function replyThingcircleComment()
    {
        if (empty($this->uid)) {
            return json(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录']);
        }
        $condition = [];
        $condition['thing_id'] = input('post.thing_id', '');
        $condition['from_uid'] = $this->uid;
        $condition['to_uid'] = input('post.to_uid', '');
        $condition['topic_id'] = input('post.topic_id', '');
        $condition['content'] = input('post.content', '');
        $condition['comment_pid'] = input('post.comment_pid', '');
        $condition['create_time'] = time();
        $condition['website_id'] = $this->website_id;

        $thing_server = new ThingcircleServer();
        $res = $thing_server->addComment($condition);
        if ($res) {
            //同步评论数
            $thing_model = new ThingCircleModel();
            $like_count = $thing_server->getCommentCount(['thing_id'=>$condition['thing_id'],'state'=>1]);
            $thing_model->where(['id' => $condition['thing_id']])->setField('evaluates',$like_count);
            return json(['code' => 1, 'message' => '添加成功']);
        } else {
            return AjaxReturn(-1);
        }
    }

    /**
     * 用户关注列表
     */
    public function attentionUserList()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $condition['tca.uid'] = $this->uid;
        $condition['tca.status'] = 1;

        $thing_server = new ThingcircleServer();
        $res = $thing_server->getAttentionList($page_index, $page_size, $condition, $order = 'create_time desc');
        $attention_model = new VslUserFollowModel();

        foreach ($res['data'] as $value) {
            //是否相互关注
            if ($attention_model->where(['uid' => $value['follow_uid'], 'follow_uid' => $value['uid'], 'status' => 1])->find()) {
                $value['mutual'] = 1;
            } else {
                $value['mutual'] = 0;
            }

            $value['thing_count'] = $thing_server->countThingById(['tc.user_id' => $value['follow_uid']]);
        }

        if ($res) {
            return json(['code' => 1, 'message' => '获取成功', 'data' => $res]);
        } else {
            return AjaxReturn(-1);
        }
    }

    /**
     * 用户粉丝列表
     */
    public function fansUserList()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $condition['tca.follow_uid'] = $this->uid;
        $condition['tca.status'] = 1;

        $thing_server = new ThingcircleServer();
        $res = $thing_server->getAttentionList($page_index, $page_size, $condition, $order = 'create_time desc');
        $attention_model = new VslUserFollowModel();

        foreach ($res['data'] as $value) {
            if ($attention_model->where(['uid' => $value['follow_uid'], 'follow_uid' => $value['uid'], 'status' => 1])->find()) {
                $value['mutual'] = 1;
            } else {
                $value['mutual'] = 0;
            }

            $value['thing_count'] = $thing_server->countThingById(['tc.user_id' => $value['uid']]);
        }

        if ($res) {
            return json(['code' => 1, 'message' => '获取成功', 'data' => $res]);
        } else {
            return AjaxReturn(-1);
        }
    }

    /**
     * 好物圈消息中心
     */
    public function getThingcircleMessageCenter()
    {
        $thing_server = new ThingcircleServer();
        $condition = $data = [];
        $condition['to_uid'] = $this->uid;
        $condition['is_check'] = 0;
        $data['message_count'] = $thing_server->getMsgCount($condition);//消息
        $like_count = $thing_server->getLikeCount($condition);//点赞
        $collection_count = $thing_server->getCollectCount($condition);//收藏
        $data['lac_count'] = $like_count + $collection_count;
        $data['comment_count'] = $thing_server->getCommentCount($condition);//评论

        return json(['code' => 1, 'message' => '获取成功', 'data' => $data]);
    }

    /**
     * 好物圈消息
     */
    public function getThingcircleMessage()
    {
        $thing_server = new ThingcircleServer();

        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $condition['to_uid'] = $this->uid;
        $res = $thing_server->getMsg($page_index, $page_size, $condition, $order = 'create_time desc');
        //标为已读
        $thing_server->getReadMsg(['to_uid'=>$this->uid,'is_check'=>0]);
        return json(['code' => 1, 'message' => '获取成功', 'data' => $res]);
    }

    /**
     * 好物圈消息点赞和收藏
     */
    public function getThingcircleLac()
    {
        $thing_server = new ThingcircleServer();

        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $condition['user_id'] = $this->uid;
        $res = $thing_server->getLikeAndCollect($page_index, $page_size, $condition);
        //标为已读
        $thing_server->getReadLike(['to_uid'=>$this->uid,'is_check'=>0]);
        $thing_server->getReadCollection(['to_uid'=>$this->uid,'is_check'=>0]);
        return json(['code' => 1, 'message' => '获取成功', 'data' => $res]);
    }

    /**
     * 好物圈评论/@
     */
    public function getThingcircleComment()
    {
        $thing_server = new ThingcircleServer();
        
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $condition['tcc.to_uid'] = $this->uid;
        $condition['tcc.state'] = ['neq',4];
        $res = $thing_server->getCommentById($page_index, $page_size, $condition);
        //标为已读
        $thing_server->getReadComment(['to_uid'=>$this->uid,'is_check'=>0]);
        return json(['code' => 1, 'message' => '获取成功', 'data' => $res]);
    }

    /**
     * 好物圈发布干货
     */
    public function addThingcircleWap()
    {
        $condition = [];
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $condition['thing_type'] = request()->post('thing_type', '');
        $condition['title'] = request()->post('thing_title', '');
        $condition['content'] = request()->post('content', '');
        $img_list = input('post.img_id');
        if ($img_list) {
            $array = explode(',', $img_list);
            $img = $this->dealImg($array);
        }
        $thing_server = new ThingcircleServer();
        if($condition['thing_type'] == 2 && $img_list){
            $res = $thing_server->ffmpeg($img_list);
            if($res['code'] == 1){
                $condition['video_img'] = json_encode($res['data'], JSON_UNESCAPED_UNICODE);
            }
        }
        $condition['media_val'] = $img['img_id_array'];
        $condition['lat'] = request()->post('lat', '');
        $condition['lng'] = request()->post('lng', '');
        $condition['location'] = request()->post('location', '');
        $condition['topic_id'] = request()->post('topic_id', '');
        $condition['recommend_goods'] = request()->post('goods_array', '');
        $condition['create_time'] = time();
        $condition['user_id'] = $this->uid;
        
        $thing_model = new ThingCircleModel();
        $res = $thing_model->save($condition);
        if ($res) {
            $config = $thing_server->getThingcircleSite($this->website_id);
            
            //发布干货
            if ($config['release_thing'] == 1) {
                $thing_server->giveReward($res,$condition['user_id'],$condition['website_id'],$config['release_point'],$config['release_growth_num'],$config['release_coupon_type_id'],$config['release_gift_voucher_id'],0);
            }
            
            //累积发布
            if ($config['release_num'] == 1) {
                $thing_num = $thing_model->getThingCircleViewCount(['tc.user_id'=>$this->uid]);
                $thingcircle_records = new VslThingCircleRecordsModel();
                $record_info = $thingcircle_records->getRecordDetail(['uid'=>$this->uid,'website_id'=>$this->website_id,'reward_type'=>1]);
                
                if($thing_num >= $config['release_nums'] && empty($record_info)){
                    $thing_server->giveReward($res,$condition['user_id'],$condition['website_id'],$config['release_num_point'],$config['release_growth_num'],$config['release_num_coupon_type_id'],$config['release_num_gift_voucher_id'],1);
                }
            }
            return json(['code' => 1, 'message' => '发布成功']);
        } else {
            return json(['code' => 0, 'message' => '发布失败']);
        }
    }

    /**
     * 获取举报类型
     */
    public function getViolationList()
    {
        $violation_model = new ThingcircleViolationModel();
        $condition = array(
            'website_id' => $this->website_id,
        );
        $orderBy = 'sort asc';
        $list = $violation_model->getThingCircleViolationList(1, 0, $condition, $orderBy);
        
        return json(['code' => 1, 'message' => '获取成功', 'data' => $list]);
    }

    /**
     * 举报评论
     */
    public function addViolation()
    {
        $condition['report_uid'] = $this->uid;
        $condition['comment_id'] = request()->post('comment_id');
        //$thing_uid = request()->post('thing_uid');
        if (!isset($condition['comment_id'])) {
            return json(['code' => -1, 'message' => '评论不存在']);
        }
        $condition['violation_id'] = request()->post('violation_id', '');
        $condition['report_reason'] = request()->post('report_reason', '');
        $img_list = request()->post('report_photo', '');
        if ($img_list) {
            /*foreach ( $img_list as $k=>$value){
                $img .= $value.',';
            }*/
            $array = explode(',', $img_list);
            $img = $this->dealImg($array);
        }
        $condition['report_photo'] = $img['img_id_array'];
        $condition['create_time'] = time();
        $condition['website_id'] = $this->website_id;
        $condition['state'] = 1;

        $report = new ThingCircleReportCommentModel();
        $res = $report->save($condition);
        if ($res) {
            return json(['code' => 1, 'message' => '举报成功']);
        } else {
            return json(['code' => -1, 'message' => '举报失败']);
        }

    }

    /**
     * 获取推荐商品
     */
    public function getRecommendGoods()
    {
        $condition['buyer_id'] = $this->uid;

        $thing_server = new ThingcircleServer();
        $setting = $thing_server->getThingcircleSite($this->website_id);

        if ($setting['recommend_goods'] != 1) {
            return json(['code' => 0, 'message' => '没有开启推荐商品功能']);
        }

        $order_goods = new VslOrderGoodsModel();
        $buy_list = $order_goods->where($condition)->field('goods_id')->Distinct(true)->select();

        $list = [];
        $temp = '';
        if ($buy_list) {
            foreach ($buy_list as $k => $value) {
                $temp .= $value['goods_id'] . ',';
            }
            $temp = substr($temp, 0, -1);
            
            $goods_condition['goods_id'] = ['in', $temp];
            
            $goods = new Goods();
            $res = $goods->getGoodsInfo($goods_condition);
            if ($res) {
                foreach ($res as $k => $value) {
                    $list[$k]['goods_id'] = $value['goods_id'];
                    $list[$k]['goods_name'] = $value['goods_name'];
                    $list[$k]['price'] = $value['price'];
                    $list[$k]['market_price'] = $value['market_price'];
                    $list[$k]['state'] = $value['state'];
                    $thing_server = new ThingcircleServer();
                    $every_goods_info = $thing_server->getEveryGoodsInfo($value['goods_id']);
                    $list[$k]['goods_img'] = getApiSrc($every_goods_info->pic_cover_small);
                }
            }
        }

        if ($list) {
            return json(['code' => 1, 'message' => '获取成功', 'data' => $list]);
        } else {
            return json(['code' => 2, 'message' => '还没购买过商品']);
        }
    }

    /**
     * 获取话题
     */
    public function getTopicList()
    {

        $search_text = request()->post('search_text');

        $thing_server = new ThingcircleServer();
        $setting = $thing_server->getThingcircleSite($this->website_id);

        if ($search_text) {
            $condition['topic_title'] = ['LIKE', '%' . $search_text . '%'];
        }

        $condition['state'] = 1;
        $condition['superiors_id'] = 0;
        
        if($setting['topic_state']==0){
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $list = $thing_server->getTopicList($page_index, $page_size, $condition, $order = 'topic_id desc');
        }else{
            $list['data'] = $thing_server->getTopicQuery($condition, $field = '*', $order = 'topic_id desc');
        }
        $list['topic_state'] = $setting['topic_state'];
        return json(['code' => 1, 'message' => '获取成功', 'data' => $list]);
    }

    /**
     * 获取二级话题
     */
    public function getLowerTopicList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text');
        $superiors_id = request()->post('superiors_id');

        $thing_server = new ThingcircleServer();
        $setting = $thing_server->getThingcircleSite($this->website_id);

        $condition['state'] = 1;
        if ($search_text) {
            $condition['topic_title'] = ['LIKE', '%' . $search_text . '%'];
        }

        if ($superiors_id) {
            $condition['superiors_id'] = $superiors_id;
        } else {
            $condition['superiors_id'] = array('neq', 0);
        }

        $list = $thing_server->getTopicList($page_index, $page_size, $condition, $order = 'topic_id desc');

        if ($list) {
            return json(['code' => 1, 'message' => '获取成功', 'data' => $list]);
        } else {
            return json(['code' => 0, 'message' => '获取失败']);
        }
    }

    /**
     * 删除评论
     */
    public function delComment()
    {
        $ids = request()->post('comment_id');
        if (!$ids) {
            return json(['code' => -1, 'message' => '删除失败']);
        }
        $thing_model = new ThingcircleServer();
        $res = $thing_model->deleteComment($ids);
        $this->addUserLog('删除评论', $res);

        if ($res) {
            return json(['code' => 1, 'message' => '删除成功']);
        } else {
            return json(['code' => -1, 'message' => '删除失败']);
        }
    }

    /**
     * 获取干货内容（视频）
     */
    public function getThingcircleVideoDetail()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        
        $condition['tcc.thing_id'] = input('post.thing_id', '');
        $uid = input('post.uid','');
        $uid = base64_decode($uid);
        //分享
        if($uid && is_numeric($uid) && $uid != $this->uid){
            $this->shareAward($uid, $condition['tcc.thing_id']);
        }
        $thing_server = new ThingcircleServer();
        $thing_model = new ThingCircleModel();

        $thing_model->where(['id' => $condition['tcc.thing_id']])->setInc('reading_volumes');
        $thing_con['tc.id'] = $condition['tcc.thing_id'];
        $thing_info = $thing_server->getThingcircleById($thing_con);
        $list = [];
        $goods = new Goods();
        if($thing_info['recommend_goods']){
            $goods_condition['goods_id'] = ['in', $thing_info['recommend_goods']];
            
            $res = $goods->getGoodsInfo($goods_condition);
            if ($res) {
                foreach ($res as $k => $value) {
                    $list[$k]['goods_id'] = $value['goods_id'];
                    $list[$k]['goods_name'] = $value['goods_name'];
                    $list[$k]['price'] = $value['price'];
                    $list[$k]['market_price'] = $value['market_price'];
                    $list[$k]['state'] = $value['state'];
                    $thing_server = new ThingcircleServer();
                    $every_goods_info = $thing_server->getEveryGoodsInfo($value['goods_id']);
                    $list[$k]['goods_img'] = getApiSrc($every_goods_info->pic_cover_small);
                }
            }
        }
        $thing_info['recommend_goods_list'] = $list;
        $conditions['tc.thing_type'] = 2;
        $conditions['tc.id'] = array('not in', $condition['tcc.thing_id']);
        $conditions['tc.website_id'] = $this->website_id;
        $thing_list = $thing_server->getThingCircleLists($page_index, $page_size, $conditions);
        foreach ($thing_list['data'] as $v) {
            if(!$v['recommend_goods']){
                $v['recommend_goods_list'] = [];
                continue;
            }
            $list = [];
            $goods_condition['goods_id'] = ['in', $v['recommend_goods']];
            $res = $goods->getGoodsInfo($goods_condition);
            if ($res) {
                foreach ($res as $k => $value) {
                    $list[$k]['goods_id'] = $value['goods_id'];
                    $list[$k]['goods_name'] = $value['goods_name'];
                    $list[$k]['price'] = $value['price'];
                    $list[$k]['market_price'] = $value['market_price'];
                    $list[$k]['state'] = $value['state'];
                    $thing_server = new ThingcircleServer();
                    $every_goods_info = $thing_server->getEveryGoodsInfo($value['goods_id']);
                    $list[$k]['goods_img'] = getApiSrc($every_goods_info->pic_cover_small);
                }
            }
            
            $v['recommend_goods_list'] = $list;
        }

        array_unshift($thing_list['data'], $thing_info);
        $thing_list['total_count'] = $thing_list['total_count'] + 1;

        if ($thing_info) {
            return json(['code' => 1, 'message' => '获取成功', 'data' => $thing_list]);
        } else {
            return AjaxReturn(-1);
        }
    }

    /**
     * 获取评论
     */
    public function getComment()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $condition['tcc.thing_id'] = input('post.thing_id', '');
        $condition['tcc.comment_pid'] = 0;
        $condition['tcc.state'] = ['neq',4];
        $thing_server = new ThingcircleServer();
        
        $comment_info = $thing_server->getCommentById($page_index, $page_size, $condition);
        foreach ($comment_info['data'] as $v) {
            $con['tcc.comment_pid'] = $v['id'];
            $con['tcc.state'] = ['neq',4];
            $con['tcc.website_id'] = $this->website_id;
            $v['reply_list'] = $thing_server->getReplyCommentList($con,1);
        }

        if ($comment_info) {
            return json(['code' => 1, 'message' => '获取成功', 'data' => $comment_info]);
        } else {
            return AjaxReturn(-1);
        }
    }

    /**
     * 获取用户干货列
     */
    public function getUserThingList()
    {
        $page_index = input('post.page_index', 1);
        $page_size = input('post.page_size', PAGESIZE);
        $thing_option = input('post.thing_option', '');
        $thing_server = new ThingcircleServer();
        $setting = $thing_server->getThingcircleSite($this->website_id);

        if ($thing_option == 2) {
            $collect_condition['tcc.user_id'] = $this->uid;
            $collect_condition['tc.website_id'] = $this->website_id;
            $collect_condition['tcc.status'] = 1;
            $res = $thing_server->getThingCircleCollectLists($page_index, $page_size, $collect_condition, $order = 'create_time desc');
            if ($setting['display_model'] == 0) {
                $list = array();
                foreach ($res['data'] as $k => $value) {
                    $date = date("Y-m-d", $value['create_time']);
                    $list[$date]['create_day'] = $date;
                    $list[$date]['child_data'][] = $res['data'][$k];
                }
                $list = array_values($list);
                $res['data'] = $list;
            }
        } elseif ($thing_option == 3) {
            $like_condition['tcl.user_id'] = $this->uid;
            $like_condition['tc.website_id'] = $this->website_id;
            $like_condition['tcl.status'] = 1;
            $like_condition['tcl.type'] = 1;
            $res = $thing_server->getThingCircleLikeLists($page_index, $page_size, $like_condition, $order = 'create_time desc');
            if ($setting['display_model'] == 0) {
                $list = array();
                foreach ($res['data'] as $k => $value) {
                    $date = date("Y-m-d", $value['create_time']);
                    $list[$date]['create_day'] = $date;
                    $list[$date]['child_data'][] = $res['data'][$k];
                }
                $list = array_values($list);
                $res['data'] = $list;
            }
        } else {
            $condition['tc.website_id'] = $this->website_id;
            $condition['tc.user_id'] = $this->uid;
            $res = $thing_server->getThingCircleLists($page_index, $page_size, $condition);
            if ($setting['display_model'] == 0) {
                $list = [];
                foreach ($res['data'] as $k => $value) {
                    $date = date("Y-m-d", $value['create_time']);
                    $list[$date]['create_day'] = $date;
                    $list[$date]['child_data'][] = $res['data'][$k];
                }
                $list = array_values($list);
                $res['data'] = $list;
            }
        }
        $res['display_model'] = $setting['display_model'];

        return json(['code' => 1, 'message' => '获取成功', 'data' => $res]);
    }

    /**
     * 挑选商品
     */
    public function selectGoods()
    {
        if (request()->post()) {
            $goodservice = new GoodsService();
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $goods_name = request()->post('goods_name', '');
            $ungoodsid = request()->post('ungoodsid', '');
            if (!empty($goods_name)) {
                $condition["ng.goods_name"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
            }
            if ($ungoodsid) {
                $condition['ng.goods_id'] = ['not in', $ungoodsid];
            }
            $condition['ng.shop_id'] = $this->instance_id;
            $condition['ng.website_id'] = $this->website_id;
            $list = $goodservice->getGoodsViewList($page_index, $page_size, $condition, [
                'ng.create_time' => 'desc'
            ]);
            if(!empty($list['data'])){
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]['pic_cover'] = __IMG($v['pic_cover']);
                }
            }
            return $list;
        } else {
            $goodsid = request()->get("goodsid", '');
            $ungoodsid = request()->get("ungoodsid", '');
            $this->assign('goodsid', $goodsid);
            $this->assign('ungoodsid', $ungoodsid);
            $this->fetch('template/' . $this->module . '/selectNumGoods');
        }
    }

    /**
     * 获取分享信息
     */
    public function getShareInfo()
    {
        $thing_id = (int)input('post.thing_id');
        $thing_server = new ThingcircleServer();
        $setting = $thing_server->getThingcircleSite($this->website_id);
        $config = [];
        $config['thing_title'] = "";
        $config['thing_describe'] = $setting['thing_describe'];
        $config['thing_pic'] = $setting['thing_pic'];
        $config['other_title'] = $setting['other_title'];
        $config['other_describe'] = $setting['other_describe'];
        $config['other_pic'] = $setting['other_pic'];
        if($thing_id>0){
            $thing_model = new ThingCircleModel();
            $thing_info = $thing_model->getInfo(['id'=>$thing_id],'title');
            $config['thing_title'] = $thing_info['title'];
            if(!empty($setting['thing_title'])){
                $config['thing_title'] = str_replace('${title}',$thing_info['title'],$setting['thing_title']);
            }
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => $config]);
    }
    /**
     * 获取视频上传秘钥
     */
    public function getCloudSign(){
        $thing_server = new ThingcircleServer();
        $set_info = $thing_server->getThingcircleSite($this->website_id);

        $secret_id = $set_info['secret_id'];
        $secret_key = $set_info['secret_key'];

        // 确定签名的当前时间和失效时间
        $current = time();
        $expired = $current + 86400;  // 签名有效期：1天

        // 向参数列表填入参数
        $arg_list = array(
            "secretId" => $secret_id,
            "currentTimeStamp" => $current,
            "expireTime" => $expired,
            "random" => rand());

        // 计算签名
        $original = http_build_query($arg_list);
        $signature = base64_encode(hash_hmac('SHA1', $original, $secret_key, true).$original);
        $data['signature'] = $signature;
        return json(['code' => 1, 'message' => '获取成功', 'data' => $data]);
    }
    
    public function shareAward($uid = 0, $thing_id = 0){
        if(!$uid || !$thing_id){
            return false;
        }
        $user_server = new User();
        $userinfo = $user_server->getUserInfoByUid($uid);
        if($userinfo){
            $condition = [];
            $condition['user_id'] = $uid;
            $condition['website_id'] = $this->website_id;

            $thing_server = new ThingcircleServer();
            $config = $thing_server->getThingcircleSite($this->website_id);

            $thing_model = new ThingCircleModel();
            $info = $thing_model->getInfo(['id'=>$thing_id]);

            if($info){
                $thingcircle_records = new VslThingCircleRecordsModel();
                $record_info = $thingcircle_records->getRecordDetail(['food_id'=>$thing_id,'uid'=>$this->uid,'website_id'=>$this->website_id,'reward_type'=>3]);
                //转发干货
                if ($config['relay_thing'] == 1 && empty($record_info)) {
                    $thing_server->giveReward($thing_id,$condition['user_id'],$condition['website_id'],$config['relay_point'],$config['like_growth_num'],$config['relay_coupon_type_id'],$config['relay_gift_voucher_id'],3);
                }
            }
        }
    }
    
    /*
     * 区域检索
     */
    public function getArea(){
        $lat = request()->post('lat',0);
        $lng = request()->post('lng',0);
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size',PAGESIZE);
        $query = request()->post('query','公司企业$交通设施$教育培训$金融');
        if(!$lat || !$lng){
            return json(AjaxReturn(-1006));
        }
        $location = $lat.','.$lng;
        $tag = '飞机场,火车站,地铁站,高等院校,中学,小学,幼儿园,公司,银行';
        $url = 'http://api.map.baidu.com/place/v2/search?query='.$query.'&tag'.$tag.'&location='.$location.'&radius=3000&output=json&ak=t16W0CsDyfV8QjlSgS17lgsI&page_size='.$page_size.'&page_num='.$page_index;//银行&location=39.915,116.404&radius=2000&output=xml&ak=您的密钥
        $result = GetCurl($url);
        if($result['message'] == 'ok'){
            return json(['code' => 1, 'message' => '获取成功', 'data' => $result]);
        }
        return json(['code' => -1, 'message' => '获取地区失败,稍后重试!']);
    }
}