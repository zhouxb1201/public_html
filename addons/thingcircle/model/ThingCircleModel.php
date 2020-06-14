<?php

namespace addons\thingcircle\model;

use data\model\BaseModel as BaseModel;
use data\model\AlbumPictureModel;

/**
 * 干货表
 * @author  www.vslai.com
 *
 */
class ThingCircleModel extends BaseModel
{

    protected $table = 'vsl_thing_circle';
    
    /*
     * 获取列表
     */
    public function getThingCircleList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getThingCircleViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getThingCircleViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    /*
     * 获取数据
     */
    public function getThingCircleViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('tc')
        ->join('sys_user u', 'tc.user_id = u.uid', 'LEFT')
        ->join('vsl_thing_circle_topic tct', 'tc.topic_id = tct.topic_id', 'LEFT')
        ->field('tc.*,u.user_name,u.user_headimg,u.nick_name,u.user_tel,u.port,tct.topic_title');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getThingCircleViewCount($condition)
    {
        
        $viewObj = $this->alias('tc')
        ->join('sys_user u', 'tc.user_id = u.uid', 'LEFT')
        ->join('vsl_thing_circle_topic tct', 'tc.topic_id = tct.topic_id', 'LEFT');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

    public function getThingCircleAttentionLists($page_index, $page_size, $condition, $order)
    {
        $count = $this->alias('tc')
            ->join('sys_user u', 'tc.user_id = u.uid', 'LEFT')
            ->join('vsl_thing_circle_topic tct', 'tc.topic_id = tct.topic_id', 'LEFT')
            ->where($condition)
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $list = $this->alias('tc')
            ->join('sys_user u', 'tc.user_id = u.uid', 'LEFT')
            ->join('vsl_thing_circle_topic tct', 'tc.topic_id = tct.topic_id', 'LEFT')
            ->field('tc.*,u.user_name,u.user_headimg,u.nick_name,tct.topic_title')
            ->where($condition)
            ->order($order)
            ->limit($offset, $page_size)
            ->select();
        //return $list;
        return ['code'=>0,
            'data'=>$list,
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }

    public function getThingCircleCollectLists($page_index, $page_size, $condition, $order)
    {
        $count = $this->alias('tc')
            ->join('sys_user u', 'tc.user_id = u.uid', 'LEFT')
            ->join('vsl_thing_circle_topic tct', 'tc.topic_id = tct.topic_id', 'LEFT')
            ->join('vsl_thing_circle_collection tcc', 'tc.id = tcc.thing_id', 'LEFT')
            ->where($condition)
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $list = $this->alias('tc')
            ->join('sys_user u', 'tc.user_id = u.uid', 'LEFT')
            ->join('vsl_thing_circle_topic tct', 'tc.topic_id = tct.topic_id', 'LEFT')
            ->join('vsl_thing_circle_collection tcc', 'tc.id = tcc.thing_id', 'LEFT')
            ->field('tc.*,u.user_name,u.user_headimg,u.nick_name,tct.topic_title')
            ->where($condition)
            ->order($order)
            ->limit($offset, $page_size)
            ->select();
        //return $list;
        return ['code'=>0,
            'data'=>$list,
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }

    public function getThingCircleLikeLists($page_index, $page_size, $condition, $order)
    {
        $count = $this->alias('tc')
            ->join('sys_user u', 'tc.user_id = u.uid', 'LEFT')
            ->join('vsl_thing_circle_topic tct', 'tc.topic_id = tct.topic_id', 'LEFT')
            ->join('vsl_user_likes tcl', 'tc.id = tcl.type_id', 'LEFT')
            ->where($condition)
            ->count();
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $list = $this->alias('tc')
            ->join('sys_user u', 'tc.user_id = u.uid', 'LEFT')
            ->join('vsl_thing_circle_topic tct', 'tc.topic_id = tct.topic_id', 'LEFT')
            ->join('vsl_user_likes tcl', 'tc.id = tcl.type_id', 'LEFT')
            ->field('tc.*,u.user_name,u.user_headimg,u.nick_name,tct.topic_title')
            ->where($condition)
            ->order($order)
            ->limit($offset, $page_size)
            ->select();
        //return $list;
        return ['code'=>0,
            'data'=>$list,
            'total_count' => $count,
            'page_count' => $page_count
        ];
    }

    public function getThingcircleById($condition)
    {
        $list = $this->alias('tc')
            ->join('vsl_thing_circle_topic tct', 'tc.topic_id = tct.topic_id', 'LEFT')
            ->join('vsl_goods g', 'tc.recommend_goods = g.goods_id', 'LEFT')
            ->join('sys_user u', 'tc.user_id = u.uid', 'LEFT')
            ->field('tc.*,tct.topic_title,u.user_name,u.user_headimg,u.nick_name,u.user_tel')
            ->where($condition)
            ->find();
        $goods_img = new AlbumPictureModel();

        $order = "instr('," . $list['media_val'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
        $goods_img_list = $goods_img->getQuery([
            'pic_id' => [
                "in",
                $list['media_val']
            ]
        ], 'pic_cover_big,pic_size_big,pic_cover_mid,pic_size_mid,pic_cover_small,pic_size_small,pic_id,pic_cover,pic_size', $order);
        if (trim($list['media_val']) != "") {
            $img_temp_array = array();
            $img_array = explode(",", $list['media_val']);
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
        $img_temp_array2 = [];
        if($img_temp_array){
            foreach($img_temp_array as $kk => $vv){
                $img_temp_array2[$kk]['pic_id'] = $vv['pic_id'];
                $img_temp_array2[$kk]['pic_cover'] = __IMG($vv['pic_cover']);
            }
        }
        $list["img_temp_array"] = $img_temp_array2;
        $list['user_headimg'] = __IMG($list['user_headimg']);
        
        $list['video_img'] = json_decode($value['video_img'],true);
        $video_img = [];
        $video_img['pic_cover'] = __IMG($value['video_img']['pic_cover']);
        $video_img['pic_size'] = $value['video_img']['pic_size'];
        $list['video_img'] = $video_img;
        return $list;
    }
}