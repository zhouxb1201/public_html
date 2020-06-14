<?php
/**
 * Promotion.php
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace app\admin\controller;

use data\service\Address;
use data\service\Config;
use data\service\promotion\PromoteRewardRule;
use data\service\Promotion as PromotionService;
use data\service\Addons;

/**
 * 营销控制器
 *
 * @author  www.vslai.com
 *        
 */
class Promotion extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 营销列表
     */
    public function promotionList()
    {
        if (request()->isAjax()) {
            $search_text = request()->post("search_text");
            $addons = new Addons();
            $list = $addons->getModuleList($search_text);
            return $list;
        }
        return view($this->style . "Promotion/promotionList");
    }


    /**
     * 优惠券类型列表
     *
     * @return multitype:number unknown |Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function couponTypeList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $search_text = request()->post('search_text', '');
            $coupon = new PromotionService();
            $condition = array(
                'shop_id' => $this->instance_id,
                'coupon_name' => array(
                    'like',
                    '%' . $search_text . '%'
                ),
                'website_id'=>$this->website_id
            );
            $list = $coupon->getCouponTypeList($page_index, $page_size, $condition, 'start_time desc');
            return $list;
        } else {
            return view($this->style . "Promotion/couponTypeList");
        }
    }

    /**
     * 满减送 列表
     */
    public function mansongList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text', '');
            $status = request()->post('status', '');
            $condition = array(
                'shop_id' => $this->instance_id,
                'mansong_name' => array(
                    'like',
                    '%' . $search_text . '%'
                ),
                'website_id'=>$this->website_id
            );
            if ($status !== '-1') {
                $condition['status'] = $status;
                $mansong = new PromotionService();
                $list = $mansong->getPromotionMansongList($page_index, $page_size, $condition);
            } else {
                $mansong = new PromotionService();
                $list = $mansong->getPromotionMansongList($page_index, $page_size, $condition);
            }
            return $list;
        }
        
        $status = request()->get('status', - 1);
        $this->assign("status", $status);
        $child_menu_list = array(
            array(
                'url' => "promotion/mansonglist",
                'menu_name' => "全部",
                "active" => $status == '-1' ? 1 : 0
            ),
            array(
                'url' => "promotion/mansonglist?status=0",
                'menu_name' => "未发布",
                "active" => $status == 0 ? 1 : 0
            ),
            array(
                'url' => "promotion/mansonglist?status=1",
                'menu_name' => "进行中",
                "active" => $status == 1 ? 1 : 0
            ),
            array(
                'url' => "promotion/mansonglist?status=3",
                'menu_name' => "已关闭",
                "active" => $status == 3 ? 1 : 0
            ),
            array(
                'url' => "promotion/mansonglist?status=4",
                'menu_name' => "已结束",
                "active" => $status == 4 ? 1 : 0
            )
        );
        $this->assign('child_menu_list', $child_menu_list);
        return view($this->style . "Promotion/mansongList");
    }

    /**
     * 添加满减送活动
     *
     * @return \think\response\View
     */
    public function addMansong()
    {
        $mansong = new PromotionService();
        if (request()->isAjax()) {
            $mansong_name = $_POST['mansong_name'];
            $level = $_POST['level'];
            $remark = $_POST['remark'];
            $status = $_POST['status'];
            $range = $_POST['range'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $shop_id = $this->instance_id;
            $type = $_POST['type'];
            $range_type = $_POST['range_type'];
            $rule = $_POST['rule'];
            $goods_id_array = $_POST['goods_id_array'];
            $res = $mansong->addPromotionMansong($mansong_name, $start_time, $end_time, $shop_id, $remark, $type, $range_type, $rule, $goods_id_array,$range,$status,$level);
            return AjaxReturn($res);
        } else {
            return view($this->style . "Promotion/addMansong");
        }
    }

    /**
     * 修改 满减送活动
     */
    public function updateMansong()
    {
        $mansong = new PromotionService();
        if (request()->isAjax()) {
            $mansong_id = $_POST['mansong_id'];
            $mansong_name = $_POST['mansong_name'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $type = $_POST['type'];
            $remark = $_POST['remark'];
            $range_type = $_POST['range_type'];
            $rule = $_POST['rule'];
            $status = $_POST['status'];
            $shop_id = $this->instance_id;
            $level = $_POST['level'];
            $goods_id_array = $_POST['goods_id_array'];
            $res = $mansong->updatePromotionMansong($mansong_id, $mansong_name, $start_time, $end_time, $shop_id,$remark, $type, $range_type, $rule, $goods_id_array,$range,$status,$level);
            if($res){
                $json['message'] = "操作成功";
                $json['code'] = $res;
                ob_clean();
                echo json_encode($json);exit;
            }
            //return AjaxReturn($res);
        } else {
            $mansong_id = request()->get('mansong_id', '');
            if (! is_numeric($mansong_id)) {
                $this->error('未获取到信息');
            }
            $info = $mansong->getPromotionMansongDetail($mansong_id);
            $condition = array(
                'shop_id' => $this->instance_id,
                'website_id'=>$this->website_id
            );
            $coupon_type_list = $mansong->getCouponTypeList(1, 0, $condition);
            $gift_list = $mansong->getPromotionGiftList(1, 0, $condition);
            $this->assign('coupon_type_list', $coupon_type_list);
            $this->assign('gift_list', $gift_list);
            $this->assign('mansong_info', $info);
            return view($this->style . "Promotion/updateMansong");
        }
    }

    /**
     * 获取限时折扣；列表
     */
    public function getDiscountList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text', '');
            $status = request()->post('status', '');
            $discount = new PromotionService();
            
            $condition = array(
                'shop_id' => $this->instance_id,
                'discount_name' => array(
                    'like',
                    '%' . $search_text . '%'
                ),
                'website_id'=>$this->website_id
            );
            if ($status !== '-1') {
                $condition['status'] = $status;
                $list = $discount->getPromotionDiscountList($page_index, $page_size, $condition);
            } else {
                $list = $discount->getPromotionDiscountList($page_index, $page_size, $condition);
            }
            
            return $list;
        }
        
        $status = request()->get('status', - 1);
        $this->assign("status", $status);
        $child_menu_list = array(
            array(
                'url' => "promotion/getdiscountList",
                'menu_name' => "全部",
                "active" => $status == '-1' ? 1 : 0
            ),
            array(
                'url' => "promotion/getdiscountList?status=0",
                'menu_name' => "未发布",
                "active" => $status == 0 ? 1 : 0
            ),
            array(
                'url' => "promotion/getdiscountList?status=1",
                'menu_name' => "进行中",
                "active" => $status == 1 ? 1 : 0
            ),
            array(
                'url' => "promotion/getdiscountList?status=3",
                'menu_name' => "已关闭",
                "active" => $status == 3 ? 1 : 0
            ),
            array(
                'url' => "promotion/getdiscountList?status=4",
                'menu_name' => "已结束",
                "active" => $status == 4 ? 1 : 0
            )
        );
        $this->assign('child_menu_list', $child_menu_list);
        
        return view($this->style . "Promotion/getDiscountList");
    }

    /**
     * 添加限时折扣
     */
    public function addDiscount()
    {
        if (request()->isAjax()) {
            $discount = new PromotionService();
            $discount_name = isset($_POST['discount_name']) ? $_POST['discount_name'] : '';
            $level = isset($_POST['level']) ? $_POST['level'] : '';
            $range = "3";
            $status = isset($_POST['status']) ? $_POST['status'] : '';
            $range_type = isset($_POST['range_type']) ? $_POST['range_type'] : '';
            $discount_num = isset($_POST['discount']) ? $_POST['discount'] : '';
            $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : '';
            $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : '';
            $remark = $_POST['remark'];
            $goods_id_array = isset($_POST['goods_id_array']) ? $_POST['goods_id_array'] : '';
            $retval = $discount->addPromotiondiscount($discount_name, $start_time, $end_time, $remark, $goods_id_array,$level,$range,$status,$range_type,$discount_num);
            return AjaxReturn($retval);
        }
        return view($this->style . "Promotion/addDiscount");
    }

    /**
     * 修改限时折扣
     */
    public function updateDiscount()
    {
        if (request()->isAjax()) {
            $discount = new PromotionService();
            $discount_id = isset($_POST['discount_id']) ? $_POST['discount_id'] : '';
            $discount_name = isset($_POST['discount_name']) ? $_POST['discount_name'] : '';
            $level = isset($_POST['level']) ? $_POST['level'] : '';
            $range = "3";
            $status = isset($_POST['status']) ? $_POST['status'] : '';
            $range_type = isset($_POST['range_type']) ? $_POST['range_type'] : '';
            $discount_num = isset($_POST['discount']) ? $_POST['discount'] : '';
            $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : '';
            $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : '';
            $remark = $_POST['remark'];
            $goods_id_array = isset($_POST['goods_id_array']) ? $_POST['goods_id_array'] : '';
            $retval = $discount->updatePromotionDiscount($discount_id, $discount_name, $start_time, $end_time, $remark, $goods_id_array,$level,$range,$status,$range_type,$discount_num);
            return AjaxReturn($retval);
        }
        $info = $this->getDiscountDetail();
        if (! empty($info['goods_list'])) {
            foreach ($info['goods_list'] as $k => $v) {
                $goods_id_array[] = $v['goods_id'];
            }
        }
        $info['goods_id_array'] = $goods_id_array;
        $this->assign("info", $info);
        return view($this->style . "Promotion/updateDiscount");
    }

    /**
     * 获取限时折扣详情
     */
    public function getDiscountDetail()
    {
        $discount_id = request()->get('discount_id', '');
        if (! is_numeric($discount_id)) {
            $this->error("没有获取到折扣信息");
        }
        $discount = new PromotionService();
        $detail = $discount->getPromotionDiscountDetail($discount_id);
        return $detail;
    }

    /**
     * 获取满减送详情
     */
    public function getMansongDetail()
    {
        $mansong_id = request()->get('mansong_id', '');
        if (! is_numeric($mansong_id)) {
            $this->error("没有获取到满减送信息");
        }
        $mansong = new PromotionService();
        $detail = $mansong->getPromotionMansongDetail($mansong_id);
        return $detail;
    }

    /**
     * 删除限时折扣
     */
    public function delDiscount()
    {
        $discount_id = request()->post('discount_id', '');
        if (empty($discount_id)) {
            $this->error("没有获取到折扣信息");
        }
        $discount = new PromotionService();
        $res = $discount->delPromotionDiscount($discount_id);
        return AjaxReturn($res);
    }

    /**
     * 关闭正在进行的限时折扣
     */
    public function closeDiscount()
    {
        $discount_id = request()->post('discount_id', '');
        if (! is_numeric($discount_id)) {
            $this->error("没有获取到折扣信息");
        }
        $discount = new PromotionService();
        $res = $discount->closePromotionDiscount($discount_id);
        return AjaxReturn($res);
    }

    /**
     * 删除满减送活动
     *
     * @return unknown[]
     */
    public function delMansong()
    {
        $mansong_id = request()->post('mansong_id', '');
        if (empty($mansong_id)) {
            $this->error("没有获取到满减送信息");
        }
        $mansong = new PromotionService();
        $res = $mansong->delPromotionMansong($mansong_id);
        return AjaxReturn($res);
    }

    /**
     * 关闭满减送活动
     *
     * @return unknown[]
     */
    public function closeMansong()
    {
        $mansong_id = request()->post('mansong_id', '');
        if (! is_numeric($mansong_id)) {
            $this->error("没有获取到满减送信息");
        }
        $mansong = new PromotionService();
        $res = $mansong->closePromotionMansong($mansong_id);
        return AjaxReturn($res);
    }

    /**
     * 满额包邮
     */
    public function fullShipping()
    {
        $full = new PromotionService();
        if (request()->isAjax()) {
            $is_open = request()->post('is_open', '');
            $full_mail_money = request()->post('full_mail_money', '');
            $no_mail_province_id_array = request()->post('no_mail_province_id_array', '');
            $no_mail_city_id_array = request()->post("no_mail_city_id_array", '');
            $res = $full->updatePromotionFullMail($this->instance_id, $is_open, $full_mail_money, $no_mail_province_id_array, $no_mail_city_id_array);
            return AjaxReturn($res);
        } else {
            $info = $full->getPromotionFullMail($this->instance_id);
            $this->assign("info", $info);
            $existing_address_list['province_id_array'] = explode(',', $info['no_mail_province_id_array']);
            $existing_address_list['city_id_array'] = explode(',', $info['no_mail_city_id_array']);
            $address = new Address();
            // 目前只支持省市，不支持区县，在页面上不会体现
            $address_list = $address->getAreaTree($existing_address_list);
            $this->assign("address_list", $address_list);
            $no_mail_province_id_array = '';
            if(!empty($existing_address_list['province_id_array'])){
                foreach ($existing_address_list['province_id_array'] as $v) {
                    $no_mail_province_id_array[] = $address->getProvinceName($v);
                }
            }
            $no_mail_province = implode(',', $no_mail_province_id_array);
            $this->assign("no_mail_province", $no_mail_province);
            return view($this->style . "Promotion/fullShipping");
        }
    }

    /**
     * 单店基础版积分奖励
     */
    public function integral()
    {
        $child_menu_list = array(
            array(
                'url' => "promotion/pointconfig",
                'menu_name' => "积分管理",
                "active" => 0
            ),
            array(
                'url' => "promotion/integral",
                'menu_name' => "积分奖励",
                "active" => 1
            )
        );
        $this->assign('child_menu_list', $child_menu_list);
        if (request()->isAjax()) {
            $shop_id = $this->instance_id;
            $sign_point = request()->post('sign_point', 0);
            $share_point = request()->post('share_point', 0);
            $reg_member_self_point = request()->post('reg_member_self_point', 0);
            $reg_member_one_point = 0;
            $reg_member_two_point = 0;
            $reg_member_three_point = 0;
            $reg_promoter_self_point = 0;
            $reg_promoter_one_point = 0;
            $reg_promoter_two_point = 0;
            $reg_promoter_three_point = 0;
            $reg_partner_self_point = 0;
            $reg_partner_one_point = 0;
            $reg_partner_two_point = 0;
            $reg_partner_three_point = 0;
            $click_point = request()->post("click_point", 0);
            $comment_point = request()->post("comment_point", 0);
            $rewardRule = new PromoteRewardRule();
            $res = $rewardRule->setPointRewardRule($shop_id, $sign_point, $share_point, $reg_member_self_point, $reg_member_one_point, $reg_member_two_point, $reg_member_three_point, $reg_promoter_self_point, $reg_promoter_one_point, $reg_promoter_two_point, $reg_promoter_three_point, $reg_partner_self_point, $reg_partner_one_point, $reg_partner_two_point, $reg_partner_three_point, $click_point, $comment_point);
            return AjaxReturn($res);
        }
        $rewardRule = new PromoteRewardRule();
        $res = $rewardRule->getRewardRuleDetail($this->instance_id);
        $Config = new Config();
        $integralConfig = $Config->getIntegralConfig($this->instance_id);
        $this->assign("res", $res);
        $this->assign("integralConfig", $integralConfig);
        return view($this->style . "Promotion/integral");
    }
}