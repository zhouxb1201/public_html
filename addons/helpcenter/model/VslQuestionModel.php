<?php

namespace addons\helpcenter\model;

use data\model\BaseModel as BaseModel;

class VslQuestionModel extends BaseModel
{
    protected $table = 'vsl_question';
    protected $rule = [
        'question_id' => '',
        'title' => '',
        'content'  =>  'no_html_parse',
    ];
    protected $msg = [
        'question_id' => '',
        'title' => '',
        'content'  =>  'no_html_parse',
    ];

    public function cate()
    {
        return $this->belongsTo('VslQuestionCateModel', 'cate_id', 'cate_id', 'vqc');
    }
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getQuestionViewList($page_index, $page_size, $condition, $order)
    {
        $queryList = $this->getQuestionViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getQuestionViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /*
     * 获取数据
     */
    public function getQuestionViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('vq')
        ->join('vsl_question_cate vqc','vq.cate_id = vqc.cate_id','left')
        ->field('vq.title,vq.status,vq.sort,vq.question_id,vqc.name,vq.cate_id,vq.content');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /*
     * 获取数量
     */
    public function getQuestionViewCount($condition)
    {
        $viewObj = $this->alias('vq')
        ->join('vsl_question_cate vqc','vq.cate_id = vqc.cate_id','left')
        ->field('vq.title,vq.status,vq.sort,vq.question_id,vqc.name');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}