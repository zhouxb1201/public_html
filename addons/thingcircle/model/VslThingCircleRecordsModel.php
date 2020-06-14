<?php

namespace addons\thingcircle\model;

use data\model\BaseModel as BaseModel;
/**
 * 好物圈发奖记录
 * @author  www.vslai.com
 *
 */
class VslThingCircleRecordsModel extends BaseModel
{

    protected $table = 'vsl_thing_circle_records';
    /*
     * 获取详情
     */
    public function getRecordDetail($condition,$field = '*'){
        $detail = $this->getInfo($condition,$field);
        return $detail;
    }
}