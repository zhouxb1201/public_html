<?php
namespace data\service\Member;
/**
 * 会员流水账户
 */
use data\service\BaseService;
use data\model\VslConsultTypeModel;
use addons\coupontype\model\VslCouponTypeModel;
use addons\coupontype\model\VslCouponModel;
use data\service\Promotion;

class MemberCoupon extends BaseService
{
    function __construct(){
        parent::__construct();
    }
    /**
     * 使用优惠券
     * @param unknown $uid
     * @param unknown $coupon_id
     */
    public function useCoupon($uid, $coupon_id, $order_id)
    {
        $coupon = new VslCouponGoodsdelInfoModel();
        $data = array(
            'use_order_id' => $order_id,
            'state' => 2,
            'website_id' => $this->website_id,
            'use_time' => time()
        );
        $res = $coupon->save($data, ['coupon_id' => $coupon_id]);
        return $res;
    
    }
    /**
     * 用户获取优惠券
     * @param int|string $uid
     * @param int|string $coupon_type_id
     * @param int $get_type
     * @return  int $result
     */
    public function UserAchieveCoupon($uid, $coupon_type_id, $get_type)
    {
        $coupon = new VslCouponGoodsdelInfoModel();
        $promotion = new Promotion();
        //由 新建优惠券时插入vsl_coupon 修改为 领取时才插入vsl_coupon
        //$count = $coupon->where(['coupon_type_id'=>$coupon_type_id, 'uid'=> 0,'website_id'=>$this->website_id])->count();
        $coupon_type_detail = $promotion->getCouponTypeDetail($coupon_type_id);
        if($coupon_type_detail)
        {
            $data = array(
                'uid' => $uid,
                'state'=> 1,
                'get_type' => $get_type,
                'fetch_time' => time(),
                'coupon_type_id' => $coupon_type_id,
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
                'coupon_code' => time() . rand(111, 999),
                'create_order_id' => 0,
                'money' => $coupon_type_detail['money'],
                'discount' => $coupon_type_detail['discount'],
                'start_receive_time' => $coupon_type_detail['start_receive_time'],
                'end_receive_time' => $coupon_type_detail['end_receive_time'],
                "start_time" => $coupon_type_detail['start_time'],
                "end_time" => $coupon_type_detail['end_time']
            );
            //$result = $coupon->where(['coupon_type_id'=>$coupon_type_id, 'uid'=> 0,'website_id'=>$this->website_id])->limit(1)->update($data);
            $result = $coupon->save($data);
        }else{
            $result = NO_COUPON;
        }
        return $result;
    
    }
    /**
     * 订单返还会员优惠券
     * @param unknown $coupon_id
     */
    public function UserReturnCoupon($coupon_id){
        $coupon = new VslCouponGoodsdelInfoModel();
        $data = array(
            'state' => 1,
           'website_id'=>$this->website_id
        );
        $retval = $coupon->save($data,['coupon_id' => $coupon_id]);
        return $retval;
    }
    /**
     * 获取优惠券金额
     * @param unknown $coupon_id
     */
    public function getCouponMoney($coupon_id){
        $coupon = new VslCouponGoodsdelInfoModel();
        $money = $coupon->getInfo(['coupon_id' => $coupon_id,'website_id'=>$this->website_id],'money');
        if(!empty($money['money']))
        {
            return $money['money'];
        }else{
            return 0;
        }
    }
    /**
     * 查询当前会员优惠券列表
     * @param unknown $type  1已领用（未使用） 2已使用 3已过期
     */
    public function getUserCouponList($type = '',$shop_id='')
    {
        $time = time();
        $condition['uid'] = $this->uid;
        $condition['website_id'] = $this->website_id;
        switch ($type)
        {
            case 1:
                //未使用，已领用,未过期
               // $condition['start_time'] = array('ELT', $time);
                $condition['end_time'] = array('GT', $time);
                $condition['state'] = 1;
				break;
            case 2:
                //已使用
                $condition['state'] = 2;
				break;
            case 3:
                //$condition['end_time'] = array('ELT', $time);
                $condition['state'] = 3;
				break;
			default:
			    break;
        }
        if(!empty($shop_id)){
            $condition['shop_id'] = $shop_id;
        }
        $coupon = new VslCouponModel();
        $coupon_list = $coupon->getQuery($condition, '*', 'money desc');
        if(!empty($coupon_list))
        {
            $coupon_type_model = new VslCouponTypeModel();
            foreach ($coupon_list as $k => $v)
            {
                $type_info = $coupon_type_model->getInfo(['coupon_type_id' => $v['coupon_type_id']], 'coupon_genre,discount,coupon_name,at_least');
                $coupon_list[$k]['coupon_genre'] = $type_info['coupon_genre'];
                $coupon_list[$k]['discount'] = $type_info['discount'];
                $coupon_list[$k]['coupon_name'] = $type_info['coupon_name'];
                $coupon_list[$k]['at_least'] = $type_info['at_least'];
            }
        }
        
        return $coupon_list;
    }
}