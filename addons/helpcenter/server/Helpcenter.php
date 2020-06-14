<?php

namespace addons\helpcenter\server;

use data\model\AddonsConfigModel;
use data\service\BaseService;
use addons\helpcenter\model\VslQuestionModel;
use addons\helpcenter\model\VslQuestionCateModel;
use data\service\AddonsConfig as AddonsConfigService;

class Helpcenter extends BaseService {

    public $addons_config_module;

    function __construct() {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * @param array $input
     * @return int
     */
    public function addQuestion(array $input) {
        $questionModel = new VslQuestionModel();
        //检查是否有同样名称的问题
        $checkTitle = $questionModel->getInfo(['website_id' => $this->website_id, 'title' => $input['title']]);
        if ($checkTitle) {
            return -10012;
        }
        $data = array(
            'website_id' => $this->website_id,
            'title' => $input['title'],
            'cate_id' => $input['cate_id'],
            'content' => $input['content'],
            'status' => $input['status'],
            'sort' => $input['sort'],
            'create_time' => time(),
            'uid' => $this->uid
        );
        $questionModel->save($data);
        $questionId = $questionModel->question_id;
        return $questionId;
    }

    /**
     * @param array $input
     * @return int
     */
    public function updateQuestion(array $input) {
        $questionModel = new VslQuestionModel();
        $questionId = $input['question_id'];
        if (!$questionId) {
            return;
        }
        //检查是否有同样名称的问题
        $checkTitle = $questionModel->getInfo(['website_id' => $this->website_id, 'title' => $input['title'], 'question_id' => ['<>', $questionId]]);
        if ($checkTitle) {
            return -10012;
        }
        $data = array(
            'website_id' => $this->website_id,
            'title' => $input['title'],
            'cate_id' => $input['cate_id'],
            'content' => $input['content'],
            'status' => $input['status'],
            'sort' => $input['sort'],
            'modify_time' => time()
        );
        $retval = $questionModel->save($data, ['question_id' => $questionId]);
        return $retval;
    }

    /**
     * 获取问题列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $fields
     *
     * @return array $list
     */
    public function questionList($page_index = 1, $page_size = 0, array $condition = []) {
        $quesitonMdl = new VslQuestionModel();
        $list = $quesitonMdl->getQuestionViewList($page_index, $page_size, $condition, 'vq.sort asc');
        return $list;
    }

    /**
     * 获取问题详情
     * @param int $question_id
     * @return array $info
     */
    public function questionDetail($question_id) {
        $quesitonMdl = new VslQuestionModel();
        $info = $quesitonMdl->get($question_id);
        return $info;
    }
    
    /**
     * 获取分类详情
     * @param int $cate_id
     * @return array $info
     */
    public function questionCateDetail($cate_id) {
        $quesitonCateMdl = new VslQuestionCateModel();
        $info = $quesitonCateMdl->get($cate_id);
        return $info;
    }

    /**
     * 获取问题分类列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     *
     * @return array $list
     */
    public function questionCateList($page_index = 1, $page_size = 0, $condition = '', $order = '') {
        $questionCateModel = new VslQuestionCateModel();
        $list = $questionCateModel->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
    }
    
    /*
     * 删除问题
     */
    public function deleteQuestion($question_id) {
        if (!$question_id) {
            return -1006;
        }
        $quesitonMdl = new VslQuestionModel();
        $question = $quesitonMdl->getInfo(['question_id' => $question_id, 'website_id' => $this->website_id]);
        if (!$question) {
            return;
        }
        $retval = $quesitonMdl->destroy($question_id);
        return $retval;
    }
    
    /**
     * 添加分类
     * @param array $input
     * @return int
     */
    public function addCate(array $input) {
        $questionCateModel = new VslQuestionCateModel();
        //检查是否有同样名称的问题
        $checkName = $questionCateModel->getInfo(['website_id' => $this->website_id, 'name' => $input['name']]);
        if ($checkName) {
            return -10012;
        }
        $data = array(
            'website_id' => $this->website_id,
            'name' => $input['name'],
            'status' => 1,
            'sort' => $input['sort'],
            'create_time' => time()
        );
        $questionCateModel->save($data);
        $cateId = $questionCateModel->cate_id;
        return $cateId;
    }
    
    /*
     * 修改分类排序
     * **/
    public function updateCateSort($cate_id, $sort_val)
    {
        $questionCateModel = new VslQuestionCateModel();
        $res['sort'] = $sort_val;
        $res['update_time'] = time();
        $result = $questionCateModel->where(['cate_id'=>$cate_id])->update($res);
        return $result;
    }
    /*
     * 修改分类名称
     * **/
    public function updateCateName($cate_id, $name)
    {
        $questionCateModel = new VslQuestionCateModel();
        $res['name'] = $name;
        $res['update_time'] = time();
        $result = $questionCateModel->where(['cate_id'=>$cate_id])->update($res);
        return $result;
    }
    
    /*
     * 修改分类是否显示
     * **/
    public function updateCateShow($cate_id, $status)
    {
        $questionCateModel = new VslQuestionCateModel();
        $res['status'] = $status;
        $res['update_time'] = time();
        $result = $questionCateModel->where(['cate_id'=>$cate_id])->update($res);
        return $result;
    }
    
    /*
     * 修改问题是否显示
     * **/
    public function updateQuestionShow($question_id, $status)
    {
        $questionModel = new VslQuestionModel();
        $res['status'] = $status;
        $res['modify_time'] = time();
        $result = $questionModel->where(['question_id'=>$question_id])->update($res);
        return $result;
    }
    
    /*
     * 修改问题排序
     * **/
    public function updateQuestionSort($question_id, $sort)
    {
        $questionModel = new VslQuestionModel();
        $res['sort'] = $sort;
        $res['modify_time'] = time();
        $result = $questionModel->where(['question_id'=>$question_id])->update($res);
        return $result;
    }
    /*
     * 删除分类
     */

    public function deleteCate($cate_id) {
        if (!$cate_id) {
            return -1006;
        }
        $quesitonCateMdl = new VslQuestionCateModel();
        $cate = $quesitonCateMdl->getInfo(['cate_id' => $cate_id, 'website_id' => $this->website_id]);
        if (!$cate) {
            return;
        }
        $retval = $quesitonCateMdl->destroy($cate_id);
        return $retval;
    }

}
