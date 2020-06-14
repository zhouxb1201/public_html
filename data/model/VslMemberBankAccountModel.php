<?php
/**
 * NfxUserBankAccountModel.php
 *
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 会员提现账号
 *
 *    id int(11) NOT NULL AUTO_INCREMENT,
      uid int(11) NOT NULL COMMENT '会员id',
      bank_type int(11) NOT NULL DEFAULT 1 COMMENT '账号类型 1银行卡2微信3支付宝',
      realname varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
      account_number varchar(50) NOT NULL DEFAULT '' COMMENT '账号',
      mobile varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
      is_default bit(1) NOT NULL DEFAULT b'0' COMMENT '是否默认账号',
      create_date datetime DEFAULT NULL COMMENT '创建日期',
      modify_date datetime DEFAULT NULL COMMENT '修改日期',
      PRIMARY KEY (Id),
 */
class VslMemberBankAccountModel extends BaseModel {

    protected $table = 'vsl_member_bank_account';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

}