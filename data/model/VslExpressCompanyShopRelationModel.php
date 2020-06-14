<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/30 0030
 * Time: 10:58
 */

namespace data\model;

use data\model\BaseModel;

class VslExpressCompanyShopRelationModel extends BaseModel
{
    protected $table = 'vsl_express_company_shop_relation';
    protected $rule = [
        'id' => '',
    ];
    protected $msg = [
        'id' => '',
    ];

    public function express_company()
    {
        return $this->belongsTo('VslOrderExpressCompanyModel', 'co_id', 'co_id');
    }

    public function __get($param)
    {
        return $this->$param;
    }
    /** 
     * 获取物流列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @param unknown $group
     * @param unknown $field
     * @return \data\model\multitype:number
     */ 
    public function getExpressQuery($page_index, $page_size, $condition, $order,$group,$field)
    {
        
        $viewObj = $this->alias('no')
        ->join('vsl_express_company sp','no.co_id=sp.co_id','left')
        ->field($field)
        ->group($group);
        
        $queryList = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        $queryCount = count($queryList);

        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        
        return $list;
        
    }
}