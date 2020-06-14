<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/26 0026
 * Time: 14:44
 */
namespace addons\taskcenter\model;

use data\model\BaseModel;

class VslGeneralPosterListModel extends BaseModel
{
    protected $table = 'vsl_general_poster_list';

    public function poster_reward()
    {
        return $this->hasMany('VslPosterRewardModel','general_poster_id','general_poster_id');
    }

    public function get_task_img()
    {
        return $this->belongsTo('data\model\AlbumPictureModel','task_img','pic_id');
    }
    public function get_push_cover()
    {
        return $this->belongsTo('data\model\AlbumPictureModel','push_cover','pic_id');
    }
}
