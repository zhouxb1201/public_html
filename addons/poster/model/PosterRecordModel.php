<?php

namespace addons\poster\model;

use data\model\BaseModel as BaseModel;

/**
 * 海报记录
 *
 */
class PosterRecordModel extends BaseModel
{
    protected $table = 'vsl_poster_record';

    public function poster()
    {
        return $this->belongsTo('PosterModel', 'poster_id', 'poster_id');
    }

    public function reco()
    {
        return $this->belongsTo('data\model\UserModel','reco_uid','uid');
    }

    public function be_reco()
    {
        return $this->belongsTo('data\model\UserModel','be_reco_uid','uid');
    }
}