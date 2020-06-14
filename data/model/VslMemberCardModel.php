<?php

namespace data\model;

use data\model\BaseModel as BaseModel;
use think\Db;

/**
 * 会员计时/次商品表
 */
class VslMemberCardModel extends BaseModel
{
    public $table = 'vsl_member_card';
    protected $rule = [
        'card_id' => '',
    ];
    protected $msg = [
        'card_id' => '',
    ];
    /*
     * 获取消费卡详情
     */
    public function getDetail($condition){
        $detail = $this->alias('vmc')
        ->join('vsl_store vs','vs.store_id = vmc.store_id','left')
        ->where($condition)
        ->field('vmc.*,vs.store_name,vs.store_tel,vs.province_id,vs.city_id,vs.district_id,vs.address,vs.shop_id')
        ->find();
        return $detail;
    }

    /**
     * 获取消费卡数量
     * @param $where1 查询条件1
     * @param $where2 查询条件2
     * @return int
     */
    public function getMemberCardCount($where1, $where2 = [])
    {
        if (!empty($where2)) {
            $count = Db::table($this->table)
                ->where(function ($q) use ($where1) {
                    $q->where($where1);
                })
                ->whereOr(function ($q) use ($where2) {
                    $q->where($where2);
                })
                ->count();
        } else {
            $count = Db::table($this->table)->where($where1)
                ->count();
        }

        return $count;
    }
}
