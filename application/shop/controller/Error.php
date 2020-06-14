<?php
namespace app\shop\controller;

/**
 * 首页控制器
 */
class Error extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function _empty($name)
    {
        $this->redirect(__URL(__URL__));
    }
}