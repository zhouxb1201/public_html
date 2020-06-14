<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 订单主表
 *   order_id int(11) NOT NULL AUTO_INCREMENT COMMENT '订单id',
 order_type tinyint(4) NOT NULL DEFAULT 1 COMMENT '订单类型',
 out_trade_no varchar(100) NOT NULL DEFAULT '0' COMMENT '外部交易号',
 payment_type tinyint(4) NOT NULL DEFAULT 0 COMMENT '支付类型。取值范围：
 WEIXIN (微信自有支付)
 WEIXIN_DAIXIAO (微信代销支付)
 ALIPAY (支付宝支付)',
 shipping_type tinyint(4) NOT NULL DEFAULT 1 COMMENT '订单配送方式',
 order_from varchar(255) NOT NULL DEFAULT '' COMMENT '订单来源',
 buyer_id int(11) NOT NULL COMMENT '买家id',
 user_name varchar(50) NOT NULL DEFAULT '' COMMENT '买家会员名称',
 pay_time datetime NOT NULL COMMENT '订单付款时间',
 buyer_ip varchar(20) NOT NULL DEFAULT '' COMMENT '买家ip',
 buyer_message varchar(255) NOT NULL DEFAULT '' COMMENT '买家附言',
 buyer_invoice varchar(255) NOT NULL DEFAULT '' COMMENT '买家发票信息',
 shipping_time datetime NOT NULL COMMENT '买家要求配送时间',
 sign_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '买家签收时间',
 receiver_mobile varchar(11) NOT NULL DEFAULT '' COMMENT '收货人的手机号码',
 receiver_province int(11) NOT NULL COMMENT '收货人所在省',
 receiver_city int(11) NOT NULL COMMENT '收货人所在城市',
 receiver_district int(11) NOT NULL COMMENT '收货人所在街道',
 receiver_address varchar(255) NOT NULL DEFAULT '' COMMENT '收货人详细地址',
 receiver_zip varchar(6) NOT NULL DEFAULT '' COMMENT '收货人邮编',
 receiver_name varchar(50) NOT NULL DEFAULT '' COMMENT '收货人姓名',
 shop_id int(11) NOT NULL COMMENT '卖家店铺id',
 shop_name varchar(100) NOT NULL DEFAULT '' COMMENT '卖家店铺名称',
 seller_star tinyint(4) NOT NULL DEFAULT 0 COMMENT '卖家对订单的标注星标',
 seller_memo varchar(255) NOT NULL DEFAULT '' COMMENT '卖家对订单的备注',
 consign_time datetime NOT NULL COMMENT '卖家发货时间',
 consign_time_adjust int(11) NOT NULL COMMENT '卖家延迟发货时间',
 goods_money decimal(19, 2) NOT NULL COMMENT '商品总价',
 order_money decimal(10, 2) NOT NULL COMMENT '订单总价',
 point int(11) NOT NULL COMMENT '订单消耗积分',
 point_money decimal(10, 2) NOT NULL COMMENT '订单消耗积分抵多少钱',
 coupon_money decimal(10, 2) NOT NULL COMMENT '订单代金券支付金额',
 coupon_id int(11) NOT NULL COMMENT '订单代金券id',
 user_money decimal(10, 2) NOT NULL COMMENT '订单预存款支付金额',
 promotion_money decimal(10, 2) NOT NULL COMMENT '订单优惠活动金额',
 shipping_money decimal(10, 2) NOT NULL COMMENT '订单运费',
 pay_money decimal(10, 2) NOT NULL COMMENT '订单实付金额',
 refund_money decimal(10, 2) NOT NULL COMMENT '订单退款金额',
 give_point int(11) NOT NULL COMMENT '订单赠送积分',
 order_status tinyint(4) NOT NULL COMMENT '订单状态',
 pay_status tinyint(4) NOT NULL COMMENT '订单付款状态',
 shipping_status tinyint(4) NOT NULL COMMENT '订单配送状态',
 review_status tinyint(4) NOT NULL COMMENT '订单评价状态',
 feedback_status tinyint(4) NOT NULL COMMENT '订单维权状态',
 promotion_details varchar(255) NOT NULL DEFAULT '' COMMENT '订单使用到的优惠活动详情',
 coupon_details varchar(255) NOT NULL DEFAULT '' COMMENT '订单使用到的代金券详情',
 create_time datetime NOT NULL DEFAULT 'CURRENT_TIMESTAMP' COMMENT '订单创建时间',
 finish_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '订单完成时间',
 */
class VslOrderModel extends BaseModel {

    protected $table = 'vsl_order';
    protected $rule = [
        'order_id'  =>  '',
    ];
    protected $msg = [
        'order_id'  =>  '',
    ];

    public function order_memo()
    {
        return $this->hasMany('VslOrderMemoModel', 'order_id', 'order_id');
    }

    public function order_goods()
    {
        return $this->hasMany('VslOrderGoodsModel', 'order_id', 'order_id');
    }

    public function buyer()
    {
        return $this->belongsTo('UserModel', 'buyer_id', 'uid');
    }

    public function order_goods_express()
    {
        return $this->hasMany('VslOrderGoodsExpressModel', 'order_id', 'order_id');
    }

    /**
     * 获取商订单分布列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @param unknown $group
     * @return unknown
     */
    public function getOrderDistributionList($page_index, $page_size, $condition, $order,$group,$field){

        $queryList = $this->getOrderDistributionQuery($page_index, $page_size, $condition, $order,$group,$field);
        $queryCount = count($queryList);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取订单分布
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @param unknown $group
     * @param unknown $field
     * @return \data\model\multitype:number
     */
    public function getOrderDistributionQuery($page_index, $page_size, $condition, $order,$group,$field)
    {
        $viewObj = $this->alias('no')
        ->join('sys_province sp','no.receiver_province=sp.province_id','left')
        ->join('sys_city sc','no.receiver_city=sc.city_id','left')
        ->join('sys_district sd','no.receiver_district=sd.district_id','left')
        ->field($field)
        ->group($group);
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getViewList($page_index, $page_size, $condition, $order){

        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nm')
            ->join('sys_user su','nm.buyer_id= su.uid','left')
            ->field('nm.custom_order,su.uid,su.user_headimg, su.user_name, su.user_tel,su.nick_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nm')
            ->join('sys_user su','nm.buyer_id= su.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}