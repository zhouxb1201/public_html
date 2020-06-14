<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/30 0030
 * Time: 10:58
 */

namespace data\model;

use data\model\BaseModel;

class VslExpressCompanyModel extends BaseModel
{
    protected $table = 'vsl_express_company';
    protected $rule = [
        'id' => '',
    ];
    protected $msg = [
        'id' => '',
    ];

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
    public function getExpressCompanyQuery($page_index, $page_size, $condition, $order,$group,$field)
    {
        
        $viewObj = $this->alias('no')
        ->field($field)
        ->group($group);

        $queryList = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        
        $queryCount = $this->getViewCount($condition);
        
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        
        return $list;
        
    }
     /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('no')
        ->field($field)
        ->group($group);
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}