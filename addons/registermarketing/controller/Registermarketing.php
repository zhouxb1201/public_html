<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 14:30
 */

namespace addons\registermarketing\controller;

use addons\coupontype\server\Coupon as CouponServer;
use addons\registermarketing\server\RegisterMarketing as serverRegisterMarketing;
use addons\registermarketing\Registermarketing as baseRegisterMarketing;

class Registermarketing extends baseRegisterMarketing
{
    public function __construct()
    {
        parent::__construct();
    }

    public function saveRegisterMarketing()
    {
        $registerMarketing = new serverRegisterMarketing();
        $post_data = request()->post();
        unset($post_data['is_use']);
        $post_data['website_id'] = $this->website_id;
        $post_data['start_time'] = strtotime($post_data['start_time']);
        $post_data['end_time'] = strtotime($post_data['end_time']);
        $result = $registerMarketing->saveRegisterMarketing($post_data);
        $registerMarketing = new serverRegisterMarketing();
        // 注册营销设置
        $is_use = request()->post('is_use', 0); // 是否开启注册营销
        $retval = $registerMarketing->setRegSetting($is_use);
        setAddons('registermarketing', $this->website_id, $this->instance_id);
        if ($result && $retval) {
            $this->addUserLog('保存注册营销', $retval);
            return ['code' => 1, 'message' => '保存成功'];
        } else {
            return ['code' => -1, 'message' => '保存失败'];
        }
    }
}