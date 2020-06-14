<?php

namespace addons\channel\model;

use data\model\BaseModel as BaseModel;
use think\Db;

/**
 * 渠道商表
 * @author Administrator
 *
 */
class VslChannelModel extends BaseModel
{
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'vsl_channel';
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getViewList($page_index, $page_size, $condition, $order){

        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
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
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nm')
            ->join('sys_user su','nm.uid= su.uid','left')
            ->field('nm.channel_custom,su.uid,su.user_headimg, su.user_name, su.user_tel,su.nick_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nm')
            ->join('sys_user su','nm.uid= su.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}