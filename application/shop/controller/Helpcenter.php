<?php
namespace app\shop\controller;

use addons\helpcenter\server\Helpcenter as helpServer;

/**
 * 帮助中心
 */
class Helpcenter extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 首页
     */
    public function index()
    {
       
        $helpServer = new helpServer();
        $question_id = request()->get("id", 0);
        $cate_id = request()->get("cate_id", 0);
        $cateListSelect= $helpServer->questionCateList(1, 0, ['status' => 1, 'website_id' => $this->website_id],'sort asc');
        $cateList = $cateListSelect['data'];
        $this->assign('cate_list', $cateList); // 帮助中心分类列表
        $questionListSelect = $helpServer->questionList(1, 0, ['vq.status'=>1], 'sort asc');
        $questionList = $questionListSelect['data'];
        $this->assign('question_list', $questionList); // 帮助中心列表
        
        if (empty($question_id)) {
            $is_exit = false;
            if($cateList){
                foreach ($cateList as $cate) {
                    if ($is_exit) {
                        break;
                    }
                    foreach ($questionList as $question) {
                        if ($cate['cate_id'] == $question['cate_id']) {
                            $is_exit = true;
                            $title = $question['title'];
                            $content = $question['content'];
                            $cate_id = $question['cate_id'];
                            break;
                        }
                    }
                }
            }
            $help_document_info = array(
                'title' => $title,
                'content' => $content
            );
            $this->assign('help_document_info', $help_document_info); // 帮助中心信息详情
        } else {
            $help_document_info = $helpServer->questionDetail($question_id);
            if (empty($help_document_info)) {
                $redirect = __URL(__URL__ . '/index');
                $this->redirect($redirect);
            }
            $this->assign('help_document_info', $help_document_info); // 帮助中心信息详情
        }
        $this->assign("title_before", "帮助中心");
        $this->assign('question_id', $question_id);
        $this->assign('cate_id', $cate_id);
        return view($this->style . 'Help/helpCenter');
    }
}