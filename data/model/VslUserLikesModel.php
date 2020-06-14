<?php
namespace data\model;

use data\model\BaseModel as BaseModel;

use think\Db;

/**
 * 会员点赞表
 * @author  www.vslai.com
 *
 */
class VslUserLikesModel extends BaseModel {
    
    protected $table = 'vsl_user_likes';
    
    /*
     * 获取数量
     */
    public function getThingCircleViewCount($condition)
    {
        $count = $this->getCount($condition);
        return $count;
    }
    
    public function getLikesCount($condition)
    {
        $count = $this->alias('tcl')
        ->join('vsl_thing_circle tc', 'tcl.type_id = tc.id and tcl.type = 1', 'LEFT')
        ->join('vsl_thing_circle_comment tcc', 'tcl.type_id = tcc.id and tcl.type = 2', 'LEFT')
        ->where($condition)
        ->count();
        
        return $count;
    }
    
    public function getLikesCounts($condition,$condition2)
    {
        $count = $this->alias('tcl')
        ->join('vsl_thing_circle tc', 'tcl.type_id = tc.id and tcl.type = 1', 'LEFT')
        ->join('vsl_thing_circle_comment tcc', 'tcl.type_id = tcc.id and tcl.type = 2', 'LEFT')
        ->where($condition)
        ->where(function ($q) use($condition2) {
            $q->whereOr($condition2);
        })->count();
        
        return $count;
    }
    
    public function getLikeAndCollect($page_index, $page_size, $condition)
    {
        $matSql = Db::name('vsl_thing_circle_collection')
        ->alias('tcc')
        ->join('sys_user u ', 'tcc.user_id = u.uid')
        ->join('vsl_thing_circle tc','tc.id = tcc.thing_id')
        ->where(['tcc.user_id'=>$condition['user_id']])
        ->field('tcc.id')
        ->buildSql();
        
        $count = $this->alias('tcl')
        ->join('sys_user u', 'tcl.user_id = u.uid', 'LEFT')
        ->join('vsl_thing_circle tc','tc.id = tcl.type_id and tcl.type = 1')
        ->where(['tcl.user_id'=>$condition['user_id']])
        ->field('tcl.id')
        ->union($matSql, true)
        ->count();
        
        //var_dump($this->getLastSql());
        $page_count = ceil($count/$page_size);
        $offset = ($page_index-1)*$page_size;
        $matsSql = Db::name('vsl_thing_circle_collection')
        ->alias('tcc')
        ->join('sys_user u ', 'tcc.user_id = u.uid')
        ->join('vsl_thing_circle tc','tc.id = tcc.thing_id')
        ->where(['tcc.user_id'=>$condition['user_id']])
        ->field('tcc.id,tcc.user_id,tcc.thing_id,null type,tcc.status,tcc.is_check,tcc.create_time,u.user_name,u.user_headimg,u.nick_name,u.user_tel,tc.media_val,tc.thing_type,tc.video_img')
        ->buildSql();
        
        $list = $this->alias('tcl')
        ->join('sys_user u', 'tcl.user_id = u.uid', 'LEFT')
        ->join('vsl_thing_circle tc','tc.id = tcl.type_id and tcl.type = 1')
        ->where(['tcl.user_id'=>$condition['user_id']])
        ->union($matsSql, true)
        ->limit($offset, $page_size)
        ->field('tcl.id,tcl.user_id,tcl.type_id,tcl.type,tcl.status,tcl.is_check,tcl.create_time,u.user_name,u.user_headimg,u.nick_name,u.user_tel,tc.media_val,tc.thing_type,tc.video_img')
        ->select();
        if($list){
            foreach($list as $value){
                if($value['type'] == '' || $value['type'] == null){
                    $value['type'] = 3;
                }
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
                unset($value['media_val']);
                $value['pic_cover'] = __IMG($img_temp_array[0]['pic_cover']);
                
                $value['video_img'] = json_decode($value['video_img'],true);
                $video_img = [];
                $video_img['pic_cover'] = __IMG($value['video_img']['pic_cover']);
                $video_img['pic_size'] = $value['video_img']['pic_size'];
                $value['video_img'] = $video_img;
                
                $value['thing_user_name'] = ($value['nick_name']) ? $value['nick_name'] : ($value['user_name'] ? $value['user_name'] : ($value['user_tel'] ? $value['user_tel'] : $value['user_id']));
            }
        }
        return ['data'=>$list,'total_count' => $count,'page_count' => $page_count];
    }
}