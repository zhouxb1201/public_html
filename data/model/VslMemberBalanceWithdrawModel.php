<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员余额提现记录表
 *
 * id int(11) NOT NULL AUTO_INCREMENT,
  shop_id int(11) NOT NULL COMMENT '店铺编号',
  withdraw_no varchar(255) NOT NULL DEFAULT '' COMMENT '提现流水号',
  uid int(11) NOT NULL COMMENT '会员id',
  bank_name varchar(50) NOT NULL COMMENT '提现银行名称',
  account_number varchar(50) NOT NULL COMMENT '提现银行账号',
  realname varchar(10) NOT NULL COMMENT '提现账户姓名',
  mobile varchar(20) NOT NULL COMMENT '手机',
  cash decimal(10, 2) NOT NULL COMMENT '提现金额',
  ask_for_date datetime NOT NULL COMMENT '提现日期',
  payment_date datetime DEFAULT NULL COMMENT '到账日期',
  status smallint(6) NOT NULL DEFAULT 0 COMMENT '当前状态 0已申请(等待处理) 1已同意 -1 已拒绝',
  memo varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  modify_date datetime DEFAULT NULL COMMENT '修改日期',
  PRIMARY KEY (Id)
 */
class VslMemberBalanceWithdrawModel extends BaseModel {

    protected $table = 'vsl_member_balance_withdraw';

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
        $viewObj = $this->alias('nmar')
            ->join('sys_user su','nmar.uid = su.uid','left')
            ->field('nmar.*, su.nick_name, su.user_name, su.user_tel, su.user_email, su.user_headimg');
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
        $viewObj = $this->alias('nmar')
            ->join('sys_user su','nmar.uid = su.uid','left')
            ->field('nmar.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}