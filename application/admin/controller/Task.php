<?php

/**
 * Login.php
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
/**
 * 后台登录控制器
 */

namespace app\admin\controller;

\think\Loader::addNamespace('data', 'data/');

use data\service\Events;
use think\Controller;
use think\Log;

/**
 * 执行定时任务
 * 
 * @author  www.vslai.com
 *        
 */
class Task extends Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 加载执行任务
     */
    public function load_task() {
        $event = new Events();
        $retval_order_close = $event->ordersClose();
        $retval_mansong_operation = $event->mansongOperation();
        $retval_discount_operation = $event->discountOperation();
        $retval_order_complete = $event->ordersComplete();
        $retval_order_autodeilvery = $event->autoDeilvery();
        $retval_auto_coupon_close = $event->autoCouponClose();
        Log::write('检测自动收货' . $retval_order_autodeilvery);
        Log::write($retval_auto_coupon_close . '个优惠券已过期');
    }

}
