<?php

namespace addons\helpcenter\model;

use data\model\BaseModel as BaseModel;

class VslQuestionCateModel extends BaseModel
{
    protected $table = 'vsl_question_cate';
    protected $rule = [
        'cate_id' => '',
    ];
    protected $msg = [
        'cate_id' => '',
    ];

    public function questions()
    {
        return $this->hasMany('VslQuestionModel', 'cate_id', 'cate_id');
    }

    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getCategory2QuestionViewList($page_index, $page_size, $condition, $order)
    {

        $queryList = $this->getCategory2QuestionViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getCategory2QuestionViewCount($condition);
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
    public function getCategory2QuestionViewQuery($page_index, $page_size, $condition, $order)
    {

        $viewObj = $this->alias('c')
            ->join('vsl_question q', 'c.cate_id=q.cate_id', 'LEFT')
            ->field('c.cate_id, c.name, q.question_id, q.title, q.status');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }

    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getCategory2QuestionViewCount($condition)
    {
        $viewObj = $this->alias('c')
            ->join('vsl_question q', 'c.cate_id=q.cate_id','left')
            ->field('c.cate_id, c.name,q.question_id, q.title, q.content');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}