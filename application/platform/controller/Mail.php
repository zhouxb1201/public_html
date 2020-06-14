<?php
namespace app\platform\controller;
use data\service\Article;

/**
 * cms内容管理系统
 */
class Mail extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 站内信列表
     */
    public function MailList()
    {
        $mail = new Article();
        if(request()->isAjax()){
            $condition=[];
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $result = $mail->getMailViewList($page_index, $page_size, $condition,'ncc.send_time DESC');
            foreach ($result['data'] as $k=>$v){
                $v['push_time'] = date('Y-m-d h:i:s', $v['send_time']);
            }
            return $result;
        }else{
            return view($this->style . '/Mail/MailList');
        }
    }
    /**
     * 站内信未读列表
     */
    public function UnMailList()
    {
        $mail = new Article();
        if(request()->isAjax()){
            $condition=[];
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $message_id = $mail->getMessageId(0);
            $condition['ncc.message_info_id'] = ['in',implode(',',$message_id)];
            $result = $mail->getMailViewList($page_index, $page_size, $condition,'ncc.create_time DESC');
            foreach ($result['data'] as $k=>$v){
                $v['push_time'] = date('Y-m-d h:i:s', $v['create_time']);
            }
            return $result;
        }else{
            if($_GET['status']==0){
                $this->assign('status',0);
            }
            return view($this->style . '/Mail/UnMailList');
        }
    }
    /**
     * 根据站内信id获取站内信详情
     */
    public function MailInfo()
    {
        $article = new Article();
        $id = isset($_GET['message_info_id']) ? $_GET['message_info_id'] : '';
        $info = $article->getMessageInfo($id);
        $article->updateMessageStatus($id);
        $info['push_time'] = date('Y-m-d h:i:s', $info['push_time']);
        $this->assign('list',$info);
        return view($this->style . '/Mail/MailInfo');
    }


    /**
     * 站内信删除
     */
    public function deleteMail()
    {
        if (request()->isAjax()) {
            $article = new Article();
            $message_info_id = isset($_POST['message_info_id']) ? $_POST['message_info_id'] : '';
            $retval = $article->deleteMessage($message_info_id);
            return AjaxReturn($retval);
        }
    }

    /**
     * 公告列表
     */
    public function cmsList()
    {
        $mail = new Article();
        if(request()->isAjax()){
            $condition=[];
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $result = $mail->getTopicList($page_index, $page_size, $condition,'create_time DESC');
            foreach ($result['data'] as $k=>$v){
                $v['push_time'] = date('Y-m-d h:i:s', $v['create_time']);
            }
            return $result;
        }else{
            return view($this->style . '/Cms/cmsList');
        }
    }

    /**
     * 公告详情
     */
    public function cmsInfo()
    {
        $article = new Article();
        $id = request()->get('topic_id') ? : '';
        $info = $article->getTopicDetail($id);
        $info['create_time'] = date('Y-m-d h:i:s', $info['create_time']);
        $this->assign('info',$info);
        return view($this->style . '/Cms/cmsInfo');
    }

}