<?php

namespace addons\helpcenter\controller;

use addons\helpcenter\Helpcenter as baseHelpcenter;
use addons\helpcenter\model\VslQuestionCateModel;
use addons\helpcenter\model\VslQuestionModel;
use addons\helpcenter\server\Helpcenter as helpServer;
use data\service\AddonsConfig;

/**
 * Class Customform
 * @package addons\customform\controller
 */
class Helpcenter extends baseHelpcenter {

    public function __construct() {
        parent::__construct();
    }

    public function questionList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $search_text = request()->post("search_text", '');
        $helpServer = new helpServer();

        if ($search_text) {
            $condition['vq.title'] = ['LIKE', '%'.$search_text.'%' ];
        }
        $condition['vq.website_id'] = $this->website_id;
        $list = $helpServer->questionList($page_index, $page_size, $condition);
        $data = [
            'value' => '',
            'addons' => "helpcenter",
            'desc' => "帮助中心",
            'is_use' => 1
        ];
        $addonsConfigSer = new AddonsConfig();
        $addonsConfigSer->setAddonsConfig($data);
        setAddons('helpcenter', $this->website_id, $this->instance_id, true);

        return $list;
    }
    /**
     * 添加问题
     * @return \multitype|void
     */
    public function addQuestion() {
        $data['title'] = request()->post('title', '');
        $data['cate_id'] = request()->post('cate_id', 0);
        $data['content'] = $_POST['content'];//request()->post('content', '');
        $data['sort'] = request()->post('sort', 0);
        $data['status'] = request()->post('status', 0);
        $article = new helpServer();
        $result = $article->addQuestion($data);
        if ($result > 0) {
            $this->addUserLog('添加问题', $result);
        }
        return AjaxReturn($result);
    }

    /**
     * 修改问题
     */
    public function updateQuestion() {
        $data['title'] = request()->post('title', '');
        $data['question_id'] = request()->post('question_id', '');
        $data['cate_id'] = request()->post('cate_id', 0);
        $data['content'] = $_POST['content'];//request()->post('content', '');
        $data['sort'] = request()->post('sort', 0);
        $data['status'] = request()->post('status', 0);
        $article = new helpServer();
        $result = $article->updateQuestion($data);
        if ($result > 0) {
            $this->addUserLog('修改问题', $result);
        }
        return AjaxReturn($result);
    }
    
    /*
     * 删除问题
     */
    public function deleteQuestion(){
        $questionId = request()->post("question_id",0);
        if(!$questionId){
            return AjaxReturn(0);
        }
        $helpServer = new helpServer();
        $retval = $helpServer->deleteQuestion($questionId);
        if($retval <= 0){
            return AjaxReturn($retval);
        }
        $this->addUserLog('删除问题', $retval);
        return AjaxReturn(1);
    }
    /*
     * 修改问题是否显示
     */
    public function changeQuestionShow()
    {
        $helpServer = new helpServer();
        $question_id  = request()->post('question_id',0);
        $status  = request()->post('status','');
        $res = $helpServer->updateQuestionShow($question_id, $status);
        if($res > 0){
            $this->addUserLog('修改问题是否显示', $res);
        }
        return AjaxReturn($res);
    }
    
    /*
     * 修改问题是否显示
     */
    public function changeQuestionSort()
    {
        $helpServer = new helpServer();
        $question_id  = request()->post('question_id',0);
        $sort  = request()->post('sort','');
        $res = $helpServer->updateQuestionSort($question_id, $sort);
        if($res > 0){
            $this->addUserLog('修改问题排序', $res);
        }
        return AjaxReturn($res);
    }
    
    /**
     * 添加分类
     * @return \multitype|void
     */
    public function addCate() {
        $data['name'] = request()->post('name', '');
        $data['sort'] = request()->post('sort', 0);
        $helpServer = new helpServer();
        $result = $helpServer->addCate($data);
        if ($result > 0) {
            $this->addUserLog('添加分类', $result);
        }
        return AjaxReturn($result);
    }

    /*
     * 修改分类名称
     */
    public function changeCateName()
    {
        $helpServer = new helpServer();
        $cate_id  = request()->post('cate_id',0);
        $name  = request()->post('name','');
        $res = $helpServer->updateCateName($cate_id, $name);
        if($res > 0){
            $this->addUserLog('修改分类名称', $res);
        }
        return AjaxReturn($res);
    }
    /*
     * 修改分类是否显示
     */
    public function changeCateShow()
    {
        $helpServer = new helpServer();
        $cate_id  = request()->post('cate_id',0);
        $status  = request()->post('status','');
        $res = $helpServer->updateCateShow($cate_id, $status);
        if($res > 0){
            $this->addUserLog('修改分类是否显示', $res);
        }
        return AjaxReturn($res);
    }
    /*
     * 修改分类排序
     */
    public function changeCateSort()
    {
        $helpServer = new helpServer();
        $cate_id  = request()->post('cate_id',0);
        $sort  = request()->post('sort','');
        $res = $helpServer->updateCateSort($cate_id, $sort);
        if($res > 0){
            $this->addUserLog('修改分类排序', $res);
        }
        return AjaxReturn($res);
    }
    /*
     * 问题分类列表
     */
    public function questionCateList() {
        $order = request()->post("order", 0);
        $search_text = request()->post("search_text", '');
        $helpServer = new helpServer();
        if ($search_text) {
            $condition['name'] = ['LIKE', '%'.$search_text.'%' ];
        }
        $condition['website_id'] = $this->website_id;
        $orderBy = 'sort asc';
        if($order){
            $orderBy = 'create_time desc';
        }
        $list = $helpServer->questionCateList(1, 0, $condition, $orderBy);
        return $list;
    }
    
    /*
     * 删除分类
     */
    public function deleteCate(){
        $cateId = request()->post("cate_id",0);
        if(!$cateId){
            return AjaxReturn(0);
        }
        $helpServer = new helpServer();
        $retval = $helpServer->deleteCate($cateId);
        if($retval <= 0){
            return AjaxReturn($retval);
        }
        $this->addUserLog('删除分类', $retval);
        return AjaxReturn(1);
    }

    /**
     * 接口 - 获取分类以及标题
     * @return \multitype
     */
    public function getQuesCategoryList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post("page_size", PAGESIZE);//分类条数
        $search_text = request()->post("search_text", '');

        //先查询分类
        $qc_condition = [
            'c.website_id' => $this->website_id,
            'c.status' => 1,
            'c.status' => ['neq', 0],
        ];
        if ($search_text) {
            $qc_condition['c.name | q.title'] = ['LIKE', '%'.$search_text.'%'];
        }
        $qc_order = 'c.sort ASC';
        $questionModel = new VslQuestionCateModel();
        $lists = $questionModel->getCategory2QuestionViewList($page_index, $page_size ,$qc_condition, $qc_order);
        $data = [];
        foreach ($lists['data'] as $list) {
            if ($list['status'] == 0) {
                $list['question_id'] = null;
                $list['title'] = null;
            }
            $flag = false;
            $addItem = [
                'id' => $list['question_id'] ?: '',
                'title' => $list['title'] ?: '',
            ];
            foreach ($data as $k => $v) {
                if ($v['cate_id'] == $list['cate_id']) {
                    $data[$k]['items'][] = $addItem;
                    $flag = true;
                    break;
                }
            }
            //新增
            if (!$flag) {
                $item = [];
                if ($list['question_id']) {
                array_push($item, $addItem);
                }
                $data[] = [
                    'cate_id' => $list['cate_id'],
                    'name' => $list['name'],
                    'items' => $item,
                ];
            }

        }
        $result = [
            'c_total' => count($data),
            'c_page' => $lists['page_count'],
            'c_data' => $data,
        ];

        return AjaxReturn(SUCCESS, $result);
    }
    /**
     * 接口 - 获取分类详情
     */
    public function getQuesCategoryDetail()
    {

        $page_index = request()->post('page_index', 1);
        $page_size = request()->post("page_size", PAGESIZE);//分类条数
        $cate_id = request()->post("cate_id", '');
        $search_text = request()->post("search_text", '');

        if (!$cate_id) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }

        $condition = [
            'website_id' => $this->website_id,
            'status' => 1,
            'cate_id' => (int)$cate_id
        ];
        if ($search_text) {
            $condition['title'] = ['LIKE', '%'.$search_text.'%'];
        }
        $order = 'sort ASC';
        $qModel = new VslQuestionModel();
        $q_result = $qModel->pageQuery($page_index, $page_size, $condition, $order, '*');

        $data = [
            'total_count' => $q_result['total_count'],
            'page_count' => $q_result['page_count'],
            'items' => []
        ];
        foreach ($q_result['data'] as $key => $val) {
            $data['items'][$key] = [
                'question_id' => $val['question_id'],
                'title' => $val['title']
            ];
        }
        return AjaxReturn(SUCCESS, $data);
    }
    /**
     * 接口 - 获取文章详情
     * @return \multitype
     */
    public function getQuesDetail()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post("page_size", PAGESIZE);//分类条数
        $question_id = request()->post("question_id", '');

        if (!$question_id) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $qModel = new VslQuestionModel();
        $condition = [
            'website_id' => $this->website_id,
            'question_id' => $question_id
        ];
        $q_result = $qModel->getInfo($condition, 'title,content,status');
        if (!$q_result || $q_result['status'] == 0) {
            return AjaxReturn(-1);
        }

        $return_data = [
            'title' => $q_result['title'],
            'content' => $q_result['content'],
        ];
        return AjaxReturn(SUCCESS, $return_data);
    }
}
