<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/26 0026
 * Time: 14:44
 */
namespace addons\taskcenter\model;

use data\model\BaseModel;

class VslPosterRecordModel extends BaseModel
{
    protected $table = 'vsl_poster_record';
    public function poster()
    {
        return $this->belongsTo('VslGeneralPosterModel', 'poster_id', 'general_poster_id');
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
