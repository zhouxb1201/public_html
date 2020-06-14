<?php
namespace addons\thingcircle\server;

use addons\thingcircle\model\ThingCircleCollectionModel;
use addons\thingcircle\model\ThingCircleCommentModel;
use addons\thingcircle\model\MsgReminderModel;
use addons\thingcircle\model\ThingCircleReportCommentModel;
use addons\thingcircle\model\ThingCircleTopicModel;
use addons\thingcircle\model\ThingCircleViolationModel;
use addons\thingcircle\model\VslThingCircleRecordsModel;
use data\model\VslGoodsModel;
use data\service\BaseService;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
use addons\thingcircle\model\ThingCircleModel;
use addons\coupontype\server\Coupon as CouponServer;
use addons\giftvoucher\server\GiftVoucher as VoucherServer;
use data\model\AlbumPictureModel;
use data\model\VslUserFollowModel;
use data\model\VslUserLikesModel;
use data\model\VslMemberViewModel;
use addons\coupontype\server\Coupon;
use addons\giftvoucher\server\GiftVoucher;
use data\model\VslMemberModel;
use data\model\VslMemberAccountRecordsModel;
use data\service\promotion\PromoteRewardRule;
use data\service\Config as WebConfig;
use data\service\Upload\AliOss;

class Thingcircle extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }
    /**
     * 获取干货列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     */
    public function getThingCircleList($page_index, $page_size, $condition, $order = 'create_time desc')
    {
        $thing_model = new ThingCircleModel();
        $list = $thing_model->getThingCircleList($page_index, $page_size, $condition, $order);
        return $list;
    }

    public function getThingCircleLists($page_index, $page_size, $condition)
    {
        $setting = $this->getThingcircleSite($this->website_id);
        if($setting['thing_sort'] == 0){
            $order = 'tc.likes desc, tc.create_time desc';
        }else if($setting['thing_sort'] == 1){
            $order = 'tc.likes asc, tc.create_time desc';
        }else if($setting['thing_sort'] == 2){
            $order = 'tc.create_time desc';
        }else if($setting['thing_sort'] == 3){
            $order = 'tc.create_time asc';
        }
        
        $thing_model = new ThingCircleModel();
        $list = $thing_model->getThingCircleList($page_index, $page_size, $condition, $order);
        $like_model = new VslUserLikesModel();
        foreach($list['data'] as $value){
            $goods_img = new AlbumPictureModel();

            $order = "instr('," . $value['media_val'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
            $goods_img_list = $goods_img->getQuery([
                'pic_id' => [
                    "in",
                    $value['media_val']
                ]
            ], 'pic_cover_big,pic_size_big,pic_cover_mid,pic_size_mid,pic_cover_small,pic_size_small,pic_id,pic_cover,pic_size', $order);
            $img_temp_array = array();
            if (trim($value['media_val']) != "") {
                $img_array = explode(",", $value['media_val']);
                foreach ($img_array as $ki => $vi) {
                    if (!empty($goods_img_list)) {
                        foreach ($goods_img_list as $t => $m) {
                            if ($m["pic_id"] == $vi) {
                                $img = [];
                                $img['pic_cover'] = __IMG($m['pic_cover']);
                                $img['pic_size'] = $m['pic_size'];
                                $img_temp_array[] = $img;
                            }
                        }
                    }
                }
            }
            
            $value["img_temp_array"] = $img_temp_array;
            
            $value['video_img'] = json_decode($value['video_img'],true);
            $video_img = [];
            $video_img['pic_cover'] = __IMG($value['video_img']['pic_cover']);
            $video_img['pic_size'] = $value['video_img']['pic_size'];
            $value['video_img'] = $video_img;

            $value['is_show'] = false;

            $like['user_id'] = $this->uid;
            $like['type_id'] = $value['id'];
            $like['type'] = 1;
            $like['status'] = 1;
            $like_res = $like_model->where($like)->find();
            if($like_res){
                $value['is_like'] = 1;
            }else{
                $value['is_like'] = 0;
            }

            $attention_model = new VslUserFollowModel();
            $attend['uid'] = $this->uid;
            $attend['follow_uid'] = $value['user_id'];
            $attend['status'] = 1;
            $attend_res = $attention_model->where($attend)->find();
            if($attend_res){
                $value['is_attention'] = 1;
            }else{
                $value['is_attention'] = 0;
            }

            $collect_model = new ThingCircleCollectionModel();
            $collect['user_id'] = $this->uid;
            $collect['thing_id'] = $value['id'];
            $collect['status'] = 1;
            $collect_res = $collect_model->where($collect)->find();
            if($collect_res){
                $value['is_collect'] = 1;
            }else{
                $value['is_collect'] = 0;
            }

            $value['like_count'] = $this->countLikesById(['type_id' => $value['id'] , 'type' => 2, 'status' => 1]);

            $reply['comment_pid'] = $value['id'];
            $value['reply_count'] = $this->countCommentById($reply);

            $value['thing_user_name'] = ($value['nick_name']) ? $value['nick_name'] : ($value['user_name'] ? $value['user_name'] : ($value['user_tel'] ? $value['user_tel'] : $value['user_id']));
        }
        return $list;
    }

    public function getThingCircleCollectLists($page_index, $page_size, $condition, $order = 'create_time desc')
    {
        $thing_model = new ThingCircleModel();
        $list = $thing_model->getThingCircleCollectLists($page_index, $page_size, $condition, $order);
        $like_model = new VslUserLikesModel();
        foreach($list['data'] as $value){
            $goods_img = new AlbumPictureModel();

            $order = "instr('," . $value['media_val'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
            $goods_img_list = $goods_img->getQuery([
                'pic_id' => [
                    "in",
                    $value['media_val']
                ]
            ], 'pic_cover_big,pic_size_big,pic_cover_mid,pic_size_mid,pic_cover_small,pic_size_small,pic_id,pic_cover,pic_size', $order);
            if (trim($value['media_val']) != "") {
                $img_temp_array = array();
                $img_array = explode(",", $value['media_val']);
                foreach ($img_array as $ki => $vi) {
                    if (!empty($goods_img_list)) {
                        foreach ($goods_img_list as $t => $m) {
                            if ($m["pic_id"] == $vi) {
                                $img = [];
                                $img['pic_cover'] = __IMG($m['pic_cover']);
                                $img['pic_size'] = $m['pic_size'];
                                $img_temp_array[] = $img;
                            }
                        }
                    }
                }
            }
            $value["img_temp_array"] = $img_temp_array;
            
            $value['video_img'] = json_decode($value['video_img'],true);
            $video_img = [];
            $video_img['pic_cover'] = __IMG($value['video_img']['pic_cover']);
            $video_img['pic_size'] = $value['video_img']['pic_size'];
            $value['video_img'] = $video_img;

            $value['is_show'] = false;

            $like['user_id'] = $this->uid;
            $like['type_id'] = $value['id'];
            $like['type'] = 2;
            $like['status'] = 1;
            $like_res = $like_model->where($like)->find();
            if($like_res){
                $value['is_like'] = 1;
            }else{
                $value['is_like'] = 0;
            }

            $value['like_count'] = $this->countLikesById(['type_id' => $value['id'] , 'type' => 2, 'status' => 1]);

            $reply['comment_pid'] = $value['id'];
            $value['reply_count'] = $this->countCommentById($reply);

            $value['thing_user_name'] = ($value['nick_name']) ? $value['nick_name'] : ($value['user_name'] ? $value['user_name'] : ($value['user_tel'] ? $value['user_tel'] : $value['user_id']));
        }
        return $list;
    }

    public function getThingCircleLikeLists($page_index, $page_size, $condition, $order = 'create_time desc')
    {
        $thing_model = new ThingCircleModel();
        $list = $thing_model->getThingCircleLikeLists($page_index, $page_size, $condition, $order);
        $like_model = new VslUserLikesModel();
        foreach($list['data'] as $value){
            $goods_img = new AlbumPictureModel();

            $order = "instr('," . $value['media_val'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
            $goods_img_list = $goods_img->getQuery([
                'pic_id' => [
                    "in",
                    $value['media_val']
                ]
            ], 'pic_cover_big,pic_size_big,pic_cover_mid,pic_size_mid,pic_cover_small,pic_size_small,pic_id,pic_cover,pic_size', $order);
            if (trim($value['media_val']) != "") {
                $img_temp_array = array();
                $img_array = explode(",", $value['media_val']);
                foreach ($img_array as $ki => $vi) {
                    if (!empty($goods_img_list)) {
                        foreach ($goods_img_list as $t => $m) {
                            if ($m["pic_id"] == $vi) {
                                $img = [];
                                $img['pic_cover'] = __IMG($m['pic_cover']);
                                $img['pic_size'] = $m['pic_size'];
                                $img_temp_array[] = $img;
                            }
                        }
                    }
                }
            }
            $value["img_temp_array"] = $img_temp_array;
            
            $value['video_img'] = json_decode($value['video_img'],true);
            $video_img = [];
            $video_img['pic_cover'] = __IMG($value['video_img']['pic_cover']);
            $video_img['pic_size'] = $value['video_img']['pic_size'];
            $value['video_img'] = $video_img;

            $value['is_show'] = false;

            $like['user_id'] = $this->uid;
            $like['type_id'] = $value['id'];
            $like['type'] = 1;
            $like['status'] = 1;
            $like_res = $like_model->where($like)->find();
            if($like_res){
                $value['is_like'] = 1;
            }else{
                $value['is_like'] = 0;
            }

            $value['like_count'] = $this->countLikesById(['type_id' => $value['id'] , 'type' => 2, 'status' => 1]);

            $reply['comment_pid'] = $value['id'];
            $value['reply_count'] = $this->countCommentById($reply);

            $value['thing_user_name'] = ($value['nick_name']) ? $value['nick_name'] : ($value['user_name'] ? $value['user_name'] : ($value['user_tel'] ? $value['user_tel'] : $value['user_id']));
        }
        return $list;
    }

    public function saveThingcircleConfig($data)
    {
        $AddonsConfig = new AddonsConfigService();
        $info = $AddonsConfig->getAddonsConfig("thingcircle");
        if (!empty($info)) {
            $res = $this->addons_config_module->save(['value' => json_encode($data), 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'thingcircle'
            ]);
        } else {
            $res = $AddonsConfig->addAddonsConfig($data, '好物圈设置', 1, 'thingcircle');
        }
        return $res;
    }

    public function getThingcircleSite($website_id)
    {
        if($website_id){
            $websiteid =  $website_id;
        }else{
            $websiteid =  $this->website_id;
        }
        $config = new AddonsConfigService();
        $thing_config = $config->getAddonsConfig("thingcircle",$websiteid, 'is_use');
        $thing_info = json_decode($thing_config['value'],true);
        return $thing_info;
    }

    public function prizeList($condition)
    {
        $list = $where = [];
        $where['website_id'] = $condition['website_id'];
        $where['shop_id'] = $condition['shop_id'];
        $where['start_receive_time'] = ['elt',time()];
        $where['end_receive_time'] = ['egt',time()];
        $CouponServer = new CouponServer();
        $coupon = $CouponServer->getCouponTypeList(1, 10, $where);
        $list['coupontype'] = $coupon['data'];
        $where = [];
        $where['gv.website_id'] = $condition['website_id'];
        $where['gv.shop_id'] = $condition['shop_id'];
        $where['gv.start_receive_time'] = ['elt',time()];
        $where['gv.end_receive_time'] = ['egt',time()];
        $VoucherServer = new VoucherServer();
        $coupon = $VoucherServer->getGiftVoucherList(1, 10, $where);
        $list['giftvoucher'] = $coupon['data'];
        return $list;
    }

    public function addViolation(array $input)
    {
        $violation_model = new ThingCircleViolationModel();
        //检查是否有同样名称违规类型
        $checkName = $violation_model->getInfo(['website_id' => $this->website_id, 'name' => $input['name']]);
        if ($checkName) {
            return -10012;
        }
        $data = array(
            'website_id' => $this->website_id,
            'name' => $input['name'],
            'state' => 1,
            'sort' => $input['sort'],
            'create_time' => time()
        );
        $violation_model->save($data);
        $violationId = $violation_model->violation_id;
        return $violationId;
    }

    public function updateViolationName($violation_id, $name)
    {
        $violation_model = new ThingCircleViolationModel();
        $res['name'] = $name;
        $result = $violation_model->where(['violation_id'=>$violation_id])->update($res);
        return $result;
    }

    public function updateViolationSort($violation_id, $sort)
    {
        $violation_model = new ThingCircleViolationModel();
        $res['sort'] = $sort;
        $result = $violation_model->where(['violation_id'=>$violation_id])->update($res);
        return $result;
    }

    public function deleteViolation($violation_id)
    {
        if (!$violation_id) {
            return -1006;
        }
        $violation_model = new ThingCircleViolationModel();
        $violation = $violation_model->getInfo(['violation_id' => $violation_id, 'website_id' => $this->website_id]);
        if (!$violation) {
            return;
        }
        $retval = $violation_model->destroy($violation_id);
        return $retval;
    }

    public function updateViolationShow($violation_id, $state)
    {
        $violation_model = new ThingCircleViolationModel();
        $res['state'] = $state;
        $result = $violation_model->where(['violation_id'=>$violation_id])->update($res);
        return $result;
    }

    public function getThingCircleCommentLists($page_index, $page_size, $condition, $order = 'create_time desc')
    {
        $thing_model = new ThingCircleCommentModel();
        $list = $thing_model->getThingCircleCommentByState($page_index, $page_size, $condition, $order);
        foreach($list['data'] as $value){
            $value['thing_user_name'] = ($value['nick_name']) ? $value['nick_name'] : ($value['user_name'] ? $value['user_name'] : ($value['user_tel'] ? $value['user_tel'] : $value['from_uid']));
            $value['user_headimg'] = __IMG($value['user_headimg']);
        }
        return $list;
    }

    public function changeCommentState($condition, $state)
    {
        $data = array(
            "state" => $state,
            'update_time' => time()
        );
        $comment_model = new ThingCircleCommentModel();
        $result = $comment_model->save($data, "id  in($condition)");
        if ($result > 0) {
            hook("changeCommentSuccess", [
                'comment_id' => $condition
            ]);
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    public function deleteComment($condition)
    {
        $comment_model = new ThingCircleCommentModel();
        $result = $comment_model->where("id  in($condition)")->delete();
        if ($result > 0) {
            $thing_model = new ThingCircleReportCommentModel();
            $thing_model->where("comment_id  in($condition)")->delete();
            return SUCCESS;
        } else {
            return DELETE_FAIL;
        }
    }

    public function getAttentionInfo($condition)
    {
        $attention_moedel = new VslUserFollowModel();
        $result = $attention_moedel->where(['uid'=>$condition['uid'],'follow_uid'=>$condition['follow_uid']])->find();

        return $result;
    }

    public function getLikesInfo($condition)
    {
        $likes_moedel = new VslUserLikesModel();
        $result = $likes_moedel->where($condition)->find();

        return $result;
    }

    public function getCollectionInfo($condition)
    {
        $collection_moedel = new ThingCircleCollectionModel();
        $result = $collection_moedel->where(['user_id'=>$condition['user_id'],'thing_id'=>$condition['thing_id']])->find();

        return $result;
    }

    public function getAttentionCount($uid)
    {
        $attention_model = new VslUserFollowModel();
        $result = $attention_model->where(['uid' => $uid , 'status' => 1])->count();

        return $result;
    }

    public function getFansCount($uid)
    {
        $attention_model = new VslUserFollowModel();
        $result = $attention_model->where(['follow_uid' => $uid , 'status' => 1])->count();

        return $result;
    }

    public function getLikesCount($uid)
    {
        $condition['tcl.to_uid'] = $uid;
        $condition['tcl.type'] = 1;
        $condition['tcl.status'] = 1;
        $likes_model = new VslUserLikesModel();
        $result = $likes_model->getLikesCount($condition);

        return $result;
    }

    public function getCommentById($page_index, $page_size, $condition)
    {
        $thing_model = new ThingCircleCommentModel();
        $like_model = new VslUserLikesModel();
        
        $setting = $this->getThingcircleSite($this->website_id);
        if($setting['comment_sort'] == 0){
            $order = 'tcc.comment_likes desc, tcc.create_time desc';
        }else if($setting['comment_sort'] == 1){
            $order = 'tcc.comment_likes asc, tcc.create_time desc';
        }else if($setting['comment_sort'] == 2){
            $order = 'tcc.create_time desc';
        }else if($setting['comment_sort'] == 3){
            $order = 'tcc.create_time asc';
        }
        
        $list = $thing_model->getThingCircleCommentById($page_index, $page_size, $condition, $order);
        foreach($list['data'] as $value){
            $value['is_show'] = false;
            $value['is_self'] = ($value['from_uid'] == $this->uid)?1:0;
            $value['is_author'] = ($value['from_uid'] == $value['author_id'])?1:0;

            $like['user_id'] = $this->uid;
            $like['type_id'] = $value['id'];
            $like['type'] = 2;
            $like['status'] = 1;
            $like_res = $like_model->getThingCircleViewCount($like);
            
            $value['is_like'] = ($like_res)?1:0;

            $value['like_count'] = $this->countLikesById(['type_id' => $value['id'] , 'type' => 2, 'status' => 1]);

            $reply['comment_pid'] = $value['id'];
            $value['reply_count'] = $this->countCommentById($reply);

            $value["user_headimg"] = __IMG($value['user_headimg']);
            
            $goods_img = new AlbumPictureModel();
            $order = "instr('," . $value['media_val'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
            $goods_img_list = $goods_img->getQuery([
                'pic_id' => [
                    "in",
                    $value['media_val']
                ]
            ], 'pic_cover_big,pic_size_big,pic_cover_mid,pic_size_mid,pic_cover_small,pic_size_small,pic_id,pic_cover,pic_size', $order);
            if (trim($value['media_val']) != "") {
                $img_temp_array = array();
                $img_array = explode(",", $value['media_val']);
                foreach ($img_array as $ki => $vi) {
                    if (!empty($goods_img_list)) {
                        foreach ($goods_img_list as $t => $m) {
                            if ($m["pic_id"] == $vi) {
                                $img_temp_array[] = $m;
                            }
                        }
                    }
                }
            }
            $value['pic_cover'] = __IMG($img_temp_array[0]['pic_cover']);
            
            $value['video_img'] = json_decode($value['video_img'],true);
            $video_img = [];
            $video_img['pic_cover'] = __IMG($value['video_img']['pic_cover']);
            $video_img['pic_size'] = $value['video_img']['pic_size'];
            $value['video_img'] = $video_img;

            $value['thing_user_name'] = ($value['nick_name']) ? $value['nick_name'] : ($value['user_name'] ? $value['user_name'] : ($value['user_tel'] ? $value['user_tel'] : $value['from_uid']));
            $value['to_thing_user_name'] = ($value['to_nick_name']) ? $value['to_nick_name'] : ($value['to_user_name'] ? $value['to_user_name'] : ($value['to_user_tel'] ? $value['to_user_tel'] : $value['to_uid']));
        
            unset($value['nick_name'],$value['user_name'],$value['user_tel'],$value['media_val']);
            unset($value['to_nick_name'],$value['to_user_name'],$value['to_user_tel']);
        }
        return $list;
    }

    public function countLikesById($condition)
    {
        $like_model = new VslUserLikesModel();
        $count = $like_model->getThingCircleViewCount($condition);
        return $count;
    }

    public function countCommentById($condition)
    {
        $comment_model = new ThingCircleCommentModel();
        $count = $comment_model->getThingCircleViewCount($condition);
        return $count;
    }

    public function addComment($condition)
    {
        $comment_model = new ThingCircleCommentModel();
        $res = $comment_model->save($condition);
        return $res;
    }

    public function getAttentionList($page_index, $page_size, $condition, $order)
    {
        $attention_model = new VslUserFollowModel();
        $res = $attention_model->getThingCircleAttentionLists($page_index, $page_size, $condition, $order);
        foreach($res['data'] as $value){
            $value['thing_user_name'] = ($value['nick_name']) ? $value['nick_name'] : ($value['user_name'] ? $value['user_name'] : ($value['user_tel'] ? $value['user_tel'] : $value['uid']));
            $value['attention_thing_user_name'] = ($value['anick_name']) ? $value['anick_name'] : ($value['auser_name'] ? $value['auser_name'] : ($value['auser_tel'] ? $value['auser_tel'] : $value['follow_uid']));
        }
        return $res;
    }

    public function getAttentionArray($condition)
    {
        $attention_model = new VslUserFollowModel();
        $arr = $attention_model->getAttentionArray($condition);
        foreach($arr as $value){
            $temp[] = $value['follow_uid'];
        }
        return $temp;
    }

    public function autoId()
    {
        $autoID = mt_rand(1, 550000);
        $autoCharacter = array("1","2","3","4","5","6","7","8","9","A","B","C","D","E");
        $len = 7-((int)log10($autoID) + 1);
        $i=1;
        $numberID = mt_rand(1, 3).mt_rand(1, 9);
        for($i;$i<=$len-1;$i++)
        {
            $numberID .= $autoCharacter[mt_rand(1, 13)];
        }
        return base_convert($numberID.$autoID, 16, 10); //--->这里因为autoid永远不可能为E所以使用E来分割保证不会重复
    }

    public function getTopicList($page_index, $page_size, $condition, $order)
    {
        $topic_model = new ThingCircleTopicModel();
        $res = $topic_model->getThingCircleTopicList($page_index, $page_size, $condition, $order);
        foreach($res['data'] as $value){
            $temp = $topic_model->where(['topic_id'=>$value['superiors_id']])->field('topic_title')->find();
            $value['stopic_title'] = $temp['topic_title'];
        }
        return $res;
    }
    
    public function getTopicQuery($condition, $field, $order)
    {
        $topic_model = new ThingCircleTopicModel();
        $res = $topic_model->getThingCircleTopicQuery($condition, $field, $order);
        foreach($res as $value){
            $temp = $topic_model->where(['topic_id'=>$value['superiors_id']])->field('topic_title')->find();
            $value['stopic_title'] = $temp['topic_title'];
        }
        return $res;
    }

    /*
     * 根据商品id获取名称、图片、店铺
     * **/
    public function getEveryGoodsInfo($goods_id)
    {
        $goods_mdl = new VslGoodsModel();
        $condition = ['g.goods_id'=>$goods_id];
        $every_goods_info = $goods_mdl->alias('g')
            ->field('g.goods_name, g.price, sap.pic_cover_small, nsp.shop_name')
            ->join('sys_album_picture sap', 'g.picture=sap.pic_id', 'LEFT')
            ->join('vsl_shop nsp', 'g.shop_id=nsp.shop_id AND g.website_id=nsp.website_id', 'LEFT')
            ->where($condition)
            ->find();
        return $every_goods_info;
    }

    public function getMsgCount($condition)
    {
        $msg_model = new MsgReminderModel();
        $count = $msg_model->getCount($condition);

        return $count;
    }

    public function getLikeCount($condition)
    {
        $likes_model = new VslUserLikesModel();
        $count = $likes_model->getCount($condition);
        return $count;
    }

    public function getCollectCount($condition)
    {
        $collect_model = new ThingCircleCollectionModel();
        $count = $collect_model->getCount($condition);
        return $count;
    }

    public function getCommentCount($condition)
    {
        $comment_model = new ThingCircleCommentModel();
        $count = $comment_model->getCount($condition);

        return $count;
    }

    public function getMsg($page_index, $page_size, $condition, $order)
    {
        $msg_model = new MsgReminderModel();
        $res = $msg_model->getThingCircleMsgList($page_index, $page_size, $condition, $order);

        return $res;
    }

    public function getLikeAndCollect($page_index, $page_size, $condition)
    {
        $model = new VslUserLikesModel();
        $res = $model->getLikeAndCollect($page_index, $page_size, $condition);
        return $res;
    }
    
    /**
     * 点赞已读
     */
    public function getReadLike($input)
    {
        $model = new VslUserLikesModel();
        $model->startTrans();
        try {
            $res = $model->update(['is_check'=>1],$input);
            $model->commit();
            return $res;
        } catch (\Exception $e) {
            $model->rollback();
            return $e->getMessage();
        }
    }
    /**
     *  收藏已读
     */
    public function getReadCollection($input)
    {
        $model = new ThingCircleCollectionModel();
        $model->startTrans();
        try {
            $res = $model->update(['is_check'=>1],$input);
            $model->commit();
            return $res;
        } catch (\Exception $e) {
            $model->rollback();
            return $e->getMessage();
        }
    }
    /**
     *  消息已读
     */
    public function getReadMsg($input)
    {
        $model = new MsgReminderModel();
        $model->startTrans();
        try {
            $res = $model->update(['is_check'=>1],$input);
            $model->commit();
            return $res;
        } catch (\Exception $e) {
            $model->rollback();
            return $e->getMessage();
        }
    }
    /**
     * 评论已读
     */
    public function getReadComment($input)
    {
        $model = new ThingCircleCommentModel();
        $model->startTrans();
        try {
            $res = $model->update(['is_check'=>1],$input);
            $model->commit();
            return $res;
        } catch (\Exception $e) {
            $model->rollback();
            return $e->getMessage();
        }
    }
    
    public function getReplyCommentList($condition, $limit = 0)
    {
        $thing_model = new ThingCircleCommentModel();
        $like_model = new VslUserLikesModel();
        $setting = $this->getThingcircleSite($this->website_id);
        if($setting['comment_sort'] == 0){
            $order = 'tcc.comment_likes desc, tcc.create_time desc';
        }else if($setting['comment_sort'] == 1){
            $order = 'tcc.comment_likes asc, tcc.create_time desc';
        }else if($setting['comment_sort'] == 2){
            $order = 'tcc.create_time desc';
        }else if($setting['comment_sort'] == 3){
            $order = 'tcc.create_time asc';
        }
        $list = $thing_model->getReplyCommentList($condition, $order, $limit);
        foreach($list['data'] as $k => $v){
            $like_res = $like_model->getThingCircleViewCount(['user_id'=>$this->uid,'type_id'=>$list['data'][$k]['id'],'type'=>2,'status'=>1]);
            $list['data'][$k]['is_like'] = ($like_res)?1:0;
            $list['data'][$k]['is_self'] = ($v['from_uid'] == $this->uid)?1:0;
            $list['data'][$k]['is_author'] = ($v['from_uid'] == $v['author_id'])?1:0;
            $list['data'][$k]['thing_user_name'] = ($v['nick_name']) ? $v['nick_name'] : ($v['user_name'] ? $v['user_name'] : ($v['user_tel'] ? $v['user_tel'] : $v['from_uid']));
            $list['data'][$k]['to_thing_user_name'] = ($v['to_nick_name']) ? $v['to_nick_name'] : ($v['to_name'] ? $v['to_name'] : ($v['to_user_tel'] ? $v['to_user_tel'] : $v['to_uid']));
            $list['data'][$k]['user_headimg'] = __IMG($v['user_headimg']);
            $list['data'][$k]['to_headimg'] = __IMG($v['to_headimg']);
            unset($list['data'][$k]['nick_name'],$list['data'][$k]['user_name'],$list['data'][$k]['user_tel']);
            unset($list['data'][$k]['to_nick_name'],$list['data'][$k]['to_name'],$list['data'][$k]['to_user_tel']);
        }
        return $list;
    }

    public function setDefultViolation()
    {
        $violation_model = new ThingCircleViolationModel();
        $violation = $violation_model->where(['name' => '广告内容'])->find();
        if($violation){
            return flase;
        }else{
            $condition[0] =array('name' => '广告内容','sort' => 0,'state' => 1,'create_time' => time(),'website_id' => $this->website_id);
            $condition[1] =array('name' => '不友善内容','sort' => 1,'state' => 1,'create_time' => time(),'website_id' => $this->website_id);
            $condition[2] =array('name' => '造谣、伪科学','sort' => 2,'state' => 1,'create_time' => time(),'website_id' => $this->website_id);
            $condition[3] =array('name' => '违法违规','sort' => 3,'state' => 1,'create_time' => time(),'website_id' => $this->website_id);
            $condition[4] =array('name' => '其他','sort' => 4,'state' => 1,'create_time' => time(),'website_id' => $this->website_id);
            $res = $violation_model->saveAll($condition);
            return $res;
        }

    }

    public function getThingCircleReportCommentLists($page_index, $page_size, $condition, $order = 'create_time desc')
    {
        $thing_model = new ThingCircleReportCommentModel();
        $album_picture_model = new AlbumPictureModel();
        $list = $thing_model->getThingCircleReportCommentByState($page_index, $page_size, $condition, $order);
        foreach($list['data'] as $k=>$v){
            $v['report_imgs'] = [];
            $v['create_time'] = date("Y-m-d H:i:s",$v['create_time']);
            $v['comment_user_name'] = ($v['nick_name']) ? $v['nick_name'] : ($v['user_name'] ? $v['user_name'] : ($v['user_tel'] ? $v['user_tel'] : $v['uid']));
            $v['report_user_name'] = ($v['report_nick_name']) ? $v['report_nick_name'] : ($v['report_name'] ? $v['report_name'] : ($v['report_tel'] ? $v['report_tel'] : $v['report_uid']));
            $v['report_photo'] = explode(',',$v['report_photo']);
            $arr = [];
            foreach ($v['report_photo'] as $key  => $val) {
                $arr[] =  $album_picture_model->Query(['pic_id'=>$val], 'pic_cover')[0];
            }
            $v['report_imgs'] = $arr;
        }
        return $list;
    }

    public function changeReportState($condition, $state)
    {
        $data = array(
            "state" => $state,
            'update_time' => time()
        );
        $report_model = new ThingCircleReportCommentModel();
        $result = $report_model->save($data, "id  in($condition)");
        if ($result > 0) {
            hook("changeReportSuccess", [
                'report_id' => $condition
            ]);
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    public function changeReportStateByCommentId($condition, $state)
    {
        $data = array(
            "state" => $state,
            'update_time' => time()
        );
        $report_model = new ThingCircleReportCommentModel();
        $result = $report_model->save($data, "comment_id  in($condition)");
        if ($result > 0) {
            hook("changeReportSuccess", [
                'comment_id' => $condition
            ]);
            return SUCCESS;
        } else {
            return UPDATA_FAIL;
        }
    }

    public function deleteReportComment($condition)
    {
        $report_model = new ThingCircleReportCommentModel();
        $result = $report_model->where("comment_id  in($condition)")->delete();
        if ($result > 0) {
            hook("deleteReportCommentSuccess", [
                'comment_id' => $condition
            ]);
            return SUCCESS;
        } else {
            return DELETE_FAIL;
        }
    }

    /*
     * 根据id获取干货内容
     * **/
    public function getThingcircleById($condition){
        $thing = new ThingCircleModel();
        $result = $thing->getThingcircleById($condition);

        $attention_model = new VslUserFollowModel();
        $attend['uid'] = $this->uid;
        $attend['follow_uid'] = $result['user_id'];
        $attend['status'] = 1;
        $attend_res = $attention_model->where($attend)->find();
        if($attend_res){
            $result['is_attention'] = 1;
        }else{
            $result['is_attention'] = 0;
        }

        $like_model = new VslUserLikesModel();
        $like['user_id'] = $this->uid;
        $like['type_id'] = $result['id'];
        $like['type'] = 1;
        $like['status'] = 1;
        $like_res = $like_model->where($like)->find();
        if($like_res){
            $result['is_like'] = 1;
        }else{
            $result['is_like'] = 0;
        }

        $collect_model = new ThingCircleCollectionModel();
        $collect['user_id'] = $this->uid;
        $collect['thing_id'] = $result['id'];
        $collect['status'] = 1;
        $collect_res = $collect_model->where($collect)->find();
        if($collect_res){
            $result['is_collect'] = 1;
        }else{
            $result['is_collect'] = 0;
        }
        $result['thing_user_name'] = ($result['nick_name']) ? $result['nick_name'] : ($result['user_name'] ? $result['user_name'] : ($result['user_tel'] ? $result['user_tel'] : $result['uid']));
        unset($result['nick_name'],$result['user_name'],$result['user_tel'],$result['media_val']);
        return $result;
    }

    public function addMsg($condition)
    {
        $msg_model = new MsgReminderModel();
        $res = $msg_model->save($condition);

        return $res;
    }

    public function addReportMsg($comment_uid,$content)
    {
        $condition['title'] = '被举报提醒';
        $condition['content'] = "你的评论'".$content."'已被举报，系统判断为违规,系统已做删除处理，请注意自己的言论！";
        $condition['to_uid'] = $comment_uid;
        $condition['status'] = 1;
        $condition['create_time'] = time();
        $msg_model = new MsgReminderModel();
        $res = $msg_model->save($condition);

        return $res;
    }

    public function addReportSuccessMsg($report_uid,$name)
    {
        $condition['title'] = '举报受理提醒';
        $condition['content'] = '你举报的评论判断为'.$name.'系统已做删除处理，感谢反馈！';
        $condition['to_uid'] = $report_uid;
        $condition['status'] = 1;
        $condition['create_time'] = time();
        $msg_model = new MsgReminderModel();
        $res = $msg_model->save($condition);

        return $res;
    }

    public function getReportById($condition){
        $report_model = new ThingCircleReportCommentModel();
        $res = $report_model->getReportById($condition);

        return $res;
    }

    public function countThingById($condition)
    {
        $comment_model = new ThingCircleModel();
        $count = $comment_model->getThingCircleViewCount($condition);
        return $count;
    }

    public function getTopicLists()
    {
        $topic_model = new ThingCircleTopicModel();
        $res = $topic_model->getTopicLists();
        $thing_model = new ThingCircleModel();
        foreach($res as $value){
            $value['count'] = $thing_model->where(['topic_id' => $value['topic_id']])->count();
        }
        return $res;
    }

    public function getThingCircleTopicByParentId($topic_id)
    {
        $topic_model = new ThingCircleTopicModel();
        $res = $topic_model->getThingCircleTopicByParentId($topic_id);
        $thing_model = new ThingCircleModel();
        foreach($res as $value){
            $value['count'] = $thing_model->where(['topic_id' => $value['topic_id']])->count();
        }
        return $res;
    }
    
    /**
     * 发放奖励
     */
    public function giveReward($food_id,$uid,$website_id,$point,$growth_num,$coupon_type_id,$gift_voucher_id,$type){
        $config = $this->getThingcircleSite($website_id);
        $model = new PromoteRewardRule();
        $member_view = new VslMemberViewModel();
        $member_info = $member_view->getInfo(['uid'=>$uid],'referee_id,default_referee_id,growth_num');
        $text = '';
        $point_record_id = $growth_record_id = $coupon_record_id = $gift_voucher_record_id = 0;
        if($type == 0){
            $text = '好物圈发布';
        }else if($type == 1){
            $text = '好物圈累积发布'.$config['release_nums'].'个干货';
        }else if($type == 2){
            $text = '好物圈点赞干货';
        }else if($type == 3){
            $text = '好物圈转发';
        }else if($type == 4){
            $text = '好物圈干货达到'.$config['thing_likes_num'].'个赞';
        }
        
        if($point > 0){
            $point_record_id = $model->addMemberPointData(0,$uid,$point,39,$text.'送积分');
        }
        if($growth_num > 0){
            $member_model = new VslMemberModel();
            $member_account_record = new VslMemberAccountRecordsModel();
            $growthNum = $member_info['growth_num'] + $growth_num;
            $member_model->save(['growth_num' => $growthNum], ['uid' => $uid]);
            $data = array(
                'records_no' => getSerialNo(),
                'account_type' => 4,
                'uid' => $uid,
                'sign' => '好物圈',
                'number' => $growth_num,
                'from_type' => 39,
                'data_id' => $website_id,
                'text' => $text.'送成长值',
                'create_time' => time(),
                'website_id' => $website_id
            );
            $growth_record_id = $member_account_record->save($data);
        }
        
        //有设置优惠券 送优惠券
        if (!empty($coupon_type_id) && getAddons('coupontype', $website_id)) {
            $coupon_server = new Coupon();
            if ($coupon_server->isCouponTypeReceivable($coupon_type_id, $uid)) {
                $coupon_record_id = $coupon_server->userAchieveCoupon($uid, $coupon_type_id, 11);
            }
            
        }
        
        //有设置礼品券 送礼品券
        if (!empty($gift_voucher_id) && getAddons('giftvoucher', $website_id)) {
            $gift_voucher_service = new GiftVoucher();
            $gift['gift_voucher_id'] = $gift_voucher_id;
            $gift['website_id'] = $website_id;
            if ($gift_voucher_service->isGiftVoucherReceive($gift, $uid)) {
                $gift_voucher_record_id = $gift_voucher_service->getUserReceive($uid, $gift_voucher_id, 6);
            }
        }
        
        if($point_record_id || $growth_record_id || $coupon_record_id || $gift_voucher_record_id){
            $thingcircle_records = new VslThingCircleRecordsModel();
            $input = [];
            $input['food_id'] = $food_id;
            $input['uid'] = $uid;
            $input['reward_type'] = $type;
            $input['point_record_id'] = $point_record_id;
            $input['growth_record_id'] = $growth_record_id;
            $input['coupon_record_id'] = $coupon_record_id;
            $input['gift_voucher_record_id'] = $gift_voucher_record_id;
            $input['create_time'] = time();
            $input['website_id'] = $website_id;
            $thingcircle_records->save($input);
        }
    }
    
    /**
     * ffmpeg
     */
    function ffmpeg($url){
        if(!empty($url)){
            $name = substr($url,strrpos($url,"/")+1);
            $name = substr($name,0,strrpos($name,"."));
            $name = $name.'.png';//图片名字
            $from = __IMG($url);//文件存放路径
            $route = "upload/" . $this->website_id . "/video_img/";
            $to = dirname(dirname(dirname(dirname(__File__)))).'/'.$route;//生成图片存放路径
            if (!is_dir($to)){
                mkdir(iconv("UTF-8", "GBK", $to),0777,true);
            }
            if(!file_exists($to . $name)){
                $str = "ffmpeg -i " . $from . " -ss 1 -f image2 ". $to . $name;//ffmpeg命令
                exec($str);
            }
        }
        if(file_exists($to . $name)){
            $path = $route.$name;
       //     $image_info = getimagesize($to . $name);
            $image = \think\Image::open($to . $name);
            $width = $image->width(); 
            $height = $image->height();
            $config = new WebConfig();
            $upload_type = $config->getUploadType();
            if ($upload_type == 2) {
                $alioss = new AliOss();
                $result = $alioss->setAliOssUplaod($path, $path);
                if($result['code']){
                    @unlink($to.$name);
                    $path = $result['path'];
                }
            }
            $data = ['pic_cover'=>$path,'pic_size'=>$width.','.$height];
            return ['code' => 1, 'message' => '生成成功','data'=>$data];
        }else{
            return ['code' => -1, 'message' => '生成封面失败'];
        }
    }
}