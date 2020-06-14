<?php

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 网页基本信息
 */
class WebSiteModel extends BaseModel
{
    protected $table = 'sys_website';
    protected $rule = [
        'website_id' => '',
        'third_count' => 'no_html_parse',
    ];
    protected $msg = [
        'website_id' => '',
        'third_count' => '',
    ];

    public function register_protocol_article()
    {
        return $this->belongsTo('\addons\helpcenter\model\VslQuestionModel', 'reg_id', 'question_id');
    }

    public function merchant_version()
    {
        return $this->belongsTo('MerchantVersionModel', 'merchant_versionid', 'merchant_versionid');
    }
    
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getWebsiteList($page_index, $page_size, $condition, $order){
        
        $queryList = $this->getWebsiteViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getWebsiteViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
     public function getWebsiteViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('sw')
        ->join('sys_user sur', 'sur.uid=sw.uid','left')
        ->join('sys_merchant_version smv','sw.merchant_versionid=smv.merchant_versionid','left')
        ->join('sys_user_admin sua','sw.related_sales=sua.uid','left')
        ->join('sys_user_admin sua2','sw.related_operating=sua2.uid','left')
        ->field('sw.*,sur.user_name,sur.user_tel,sua.user,sua.user,smv.type_name as merchant_version_name,sur.current_login_time,sua.user as sale,sua2.user as operating');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getWebsiteViewCount($condition)
    {
        $viewObj = $this->alias('sw')
        ->join('sys_user sur', 'sur.uid=sw.uid','left')
        ->field('sw.ua_id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

}