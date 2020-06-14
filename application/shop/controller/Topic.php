<?php
/**
 * Member.php
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace app\shop\controller;

use data\service\Article;

/**
 * 专题
 * 
 * @author  www.vslai.com
 *        
 */
class Topic extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 专题页
     */
    public function detail()
    {
        $article = new Article();
        $topic_id = request()->get('topic_id', '');
        $info = $article->getTopicDetail($topic_id);
        $this->assign('info', $info);
        //专题详情页网站title
        $this->assign('title_before',$info['title'].'-');
        return view($this->style . 'Topic/detail');
    }
}