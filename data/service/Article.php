<?php
namespace data\service;
use data\model\VslMailListViewModel;
use data\model\WebSiteModel;
use data\service\BaseService as BaseService;
use data\model\VslCmsCommentModel;
use data\model\VslCmsCommentViewModel;
use data\model\VslCmsTopicModel;
use data\model\MerchantVersionModel;
use data\model\VslMessageInfoModel;
use data\model\VslMessageSendModel;
/**
 * 文章服务层
 */
class Article extends BaseService
{
    /* (non-PHPdoc)
     * @see \data\api\cms\IArticle::getCommentList()
     */
    public function getCommentList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $commentview = new VslCmsCommentViewModel();
        $list = $commentview->getViewList($page_index, $page_size, $condition, $order);
        return $list;
        // TODO Auto-generated method stub
    
    }
    /* (non-PHPdoc)
     * @see \data\api\cms\IArticle::getCommentDetail()
     */
    public function getCommentDetail($comment_id)
    {
        $comment = new VslCmsCommentModel();
        $data = $comment->get($comment_id);
        return $data;
        // TODO Auto-generated method stub
    
    }
    
    /* (non-PHPdoc)
     * @see \data\api\cms\IArticle::deleteComment()
     */
    public function deleteComment($comment_id){
        $comment = new VslCmsCommentModel();
        $retval=$comment->destroy($comment_id);
        return $retval;
    }
    /**
     * 添加公告
     */
     public function addTopic($instance_id,$title,$image,$content,$status){
        $topic = new VslCmsTopicModel();
        if($this->website_id){
            $websiteid = $this->website_id;
        }else{
            $websiteid = 0;
        }
        $data = array(
            'instance_id' => $instance_id,
            'website_id' => $websiteid,
            'title' => $title,
            'image' => $image,
            'content'=> htmlspecialchars_decode($content),
            'status'=>$status,
            'create_time'   => time()
        );
        $retval = $topic->save($data);
        return $retval;
     }
     /**
      * 公告列表
      */
     public function getTopicList($page_index = 1, $page_size = 0, $condition = '', $order = '',$field= '*')
     {
         $topic = new VslCmsTopicModel();
         $list = $topic->pageQuery($page_index, $page_size, $condition, $order, $field);
         return $list;
         // TODO Auto-generated method stub
     }
     /**
      * 获取公告详情
      */
     public function getTopicDetail($topic_id){
         $topic = new VslCmsTopicModel();
         $list = $topic->get($topic_id);
         if($list['is_check']){
             $website_ids = explode(',',$list['is_check']);
             if(!in_array($this->website_id,$website_ids)){
                 $check = $list['is_check'].','.$this->website_id;
                 $topic->save(['is_check'=>$check],['topic_id'=>$topic_id]);
             }
         }else{
             $check = $list['is_check'].','.$this->website_id;
             $topic->save(['is_check'=>$check],['topic_id'=>$topic_id]);
         }
         return $list;
     }
     /**
      * 修改专题
      */
     public function  updateTopic($instance_id,$topic_id,$title,$image,$content,$status)
     {
         $topic = new VslCmsTopicModel();
         if($this->website_id){
             $websiteid = $this->website_id;
         }else{
             $websiteid = 0;
         }
         $data = array(
             'instance_id' => $instance_id,
             'website_id' => $websiteid,
             'title' => $title,
             'image' => $image,
             'content'=>htmlspecialchars_decode($content),
             'status'=>$status,
             'modify_time'  => time()
         );
         $retval = $topic->save($data,['topic_id'=>$topic_id]);
         return $retval;
     }
     /**
      * 删除公告
      */
     public function  deleteTopic($topic_id)
     {
        $topic = new VslCmsTopicModel();
        $retval=$topic->destroy($topic_id);
        return $retval;
     }

    /**
     * 添加站内信
     */
    public function addMail($title,$content,$version_id,$status,$type){
        $topic = new VslMessageInfoModel();
        if($status==0){
            $data = array(
                'title' => $title,
                'type_ids' => $version_id,
                'content'=>$content,
                'type'=>$type,
                'status'=>$status,
                'user_id'=>$this->uid,
                'create_time'   => time()
            );
        }
        if($status==1){
            $data = array(
                'title' => $title,
                'type_ids' => $version_id,
                'content'=>$content,
                'type'=>$type,
                'status'=>$status,
                'user_id'=>$this->uid,
                'create_time'   => time(),
                'push_time'     => time()
            );
        }
        $retval = $topic->save($data);
        if($status==1){
            $version = new WebSiteModel();
            $condition = [];
            if($type==1){
                $condition['merchant_versionid'] = ['in',$version_id];
            }else if($type == 2){
                $condition['website_id'] = ['in',$version_id];
            }
            $version_id = $version->Query($condition,'website_id');
            foreach($version_id as $k=>$v){
                $data1 = array(
                    'website_id' => $v,
                    'message_info_id'=>$retval,
                    'status'=>0,
                    'send_time'  => time()
                );
                $message = new VslMessageSendModel();
                $message->save($data1);
            }
        }
        return $retval;
    }
    /**
     * 站内信列表
     */
    public function getMailList($page_index = 1, $page_size = 0, $condition = '', $order = '',$field= '*')
    {
        $topic = new VslMessageInfoModel();
        $list = $topic->pageQuery($page_index, $page_size, $condition, $order, $field);
        
        if($list['data']){
            foreach($list['data'] as $k => $v){
                $list['data'][$k] = $this->getMailDetail($v['message_info_id']);
            }
            unset($v);
        }
        return $list;
    }
    /**
     * 获取站内信详情
     */
    public function getMailDetail($message_info_id){
        $topic = new VslMessageInfoModel();
        $list = $topic->getInfo(['message_info_id'=>$message_info_id]);
        $userService = new User();
        $userInfo = $userService->getUserInfoByUid($list['user_id']);
        $list['sender'] = $userInfo['user_name'];
        $type_info = "";
        $websiteModel = new WebSiteModel();
        $condition = [];
        if($list['type']==1){
            $condition['merchant_versionid'] = ['in',$list['type_ids']];
        }else if($list['type']==2){
            $condition['website_id'] = ['in',$list['type_ids']];    
        }
        $type =  $websiteModel->getQuery($condition,'website_id,title','create_time desc');
        foreach($type as $v){
            $type_info .= $v['title'].';';
        }
        unset($v);
        $list['recipient'] = $type_info;
        return $list;
    }
    /**
     * 修改站内信
     */
    public function updateMail($message_info_id,$type_ids, $title,$content, $status, $type)
    {
        $mail = new VslMessageInfoModel();
        $data = array(
            'title' => $title,
            'type_ids' => $type_ids,
            'content'=>$content,
            'type'=>$type,
            'status'=>$status,
            'modify_time'  => time()
        );
        if($status==1){
            $data['push_time'] = time();
        }
        $retval = $mail->save($data,['message_info_id'=>$message_info_id]);
        if($status==1){
            $version = new WebSiteModel();
            $condition = [];
            if($type==1){
                $condition['merchant_versionid'] = ['in',$type_ids];
            }else if($type == 2){
                $condition['website_id'] = ['in',$type_ids];
            }
            $version_id = $version->Query($condition,'website_id');
            foreach($version_id as $k=>$v){
                $data1 = array(
                    'website_id' => $v,
                    'message_info_id'=>$message_info_id,
                    'status'=>0,
                    'send_time'  => time()
                );
                $message = new VslMessageSendModel();
                $message->save($data1);
            }
        }
        return $retval;
    }
    /**
     * 发送站内信
     */
    public function sendMail($title,$content,$version_id,$status,$type,$expiry_time)
    {
        $topic = new VslMessageInfoModel();
        if($status==1){
            $data = array(
                'title' => $title,
                'type_ids' => $version_id,
                'content'=>$content,
                'type'=>$type,
                'status'=>$status,
                'user_id'=>'1',
                'create_time'=> time(),
                'push_time'=> time()
            );
        }
        $retval = $topic->save($data);
        if($status==1){
            $version = new WebSiteModel();
            $version->save(['expiry_time'=>$expiry_time],['website_id'=>$version_id]);
                $data1 = array(
                    'website_id' => $version_id,
                    'message_info_id'=>$retval,
                    'status'=>0,
                    'send_time'  => time()
                );
                $message = new VslMessageSendModel();
                $message->save($data1);
        }
        return $retval;
    }
    /**
     * 获取版本信息
     */
    public function  getVersionList($condition)
    {
        $instance_type = new MerchantVersionModel();
        $retval=$instance_type->Query($condition,'merchant_versionid');
        $type = [];
        foreach ($retval as $k=>$v){
            $type[$k] = $instance_type->getInfo(['merchant_versionid'=>$v],'merchant_versionid,type_name');
        }
        return $type;
    }
    /**
     * 获取商户信息
     */
    public function  getWebsiteList($condition)
    {
        $websiteModel = new WebSiteModel();
        return $websiteModel->getQuery($condition,'website_id,title','create_time desc');
    }
    /**
     * 获取版本的消息id
     */
    public function  getMessageId($status)
    {
        $instance_type = new VslMessageSendModel();
        $retval = $instance_type->Query(['website_id' => $this->website_id, 'status' => $status], 'message_info_id');
        return $retval;
    }
    /**
     * 获取消息的详情
     */
    public function  getMessageInfo($id)
    {
        $message = new VslMessageInfoModel();
        $list = $message->getInfo(['message_info_id'=>$id],"*");

        return $list;
    }
    /**
     * 商户删除站内信
     */
    public function  deleteMessage($message_info_id)
    {
        $mail = new VslMessageSendModel();
        $retval=$mail->destroy(['message_info_id'=>$message_info_id,'website_id'=>$this->website_id]);
        return $retval;
    }
    /**
     * 商户站内信列表
     */
    public function getMailViewList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $condition['ncc.website_id']=$this->website_id;
        $mailview = new VslMailListViewModel();
        $list = $mailview->getViewList($page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 商户未读站内信
     */
    public function getMessageStatus($status)
    {
        $condition['website_id']=$this->website_id;
        $condition['status']=$status;
        $mail = new VslMessageSendModel();
        $count = $mail->getCount($condition);
        return $count;
    }
    /**
     * 商户未读公告
     */
    public function getCmsStatus()
    {
        $mail = new VslCmsTopicModel();
        $count = $mail->getCount([]);
        $count1 = $mail->where('is_check', ['=', $this->website_id], ['like', '%'.$this->website_id],['like', '%'.$this->website_id.'%'],['like', $this->website_id.'%'], 'or')->count();
        return $count-$count1;
    }
    /**
     * 修改商户站内信状态
     */
    public function updateMessageStatus($message_info_id)
    {
        $condition['website_id']=$this->website_id;
        $condition['message_info_id']=$message_info_id;
        $data = array(
          'status'=>1
        );
        $mail = new VslMessageSendModel();
        $mail->save($data,$condition);
    }
}