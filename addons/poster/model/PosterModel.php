<?php

namespace addons\poster\model;

use data\model\BaseModel as BaseModel;

/**
 * 海报
 *
 */
class PosterModel extends BaseModel
{
    protected $table = 'vsl_poster';

    public function poster_record()
    {
        return $this->hasMany('PosterRecordModel', 'poster_id', 'poster_id');
    }

    public function poster_award()
    {
        return $this->hasMany('PosterAwardModel', 'poster_id', 'poster_id');
    }

    public function push_cover()
    {
        return $this->belongsTo('data\model\AlbumPictureModel','push_cover_id','pic_id');
    }
}