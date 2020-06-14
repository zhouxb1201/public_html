<?php
/**
 * 返回值格式
 */
//定义返回值字母格式     基础1000-1999，  用户：2000-2999 商品：3000-3999， 订单：4000-4999 活动：5000-5999
//基础变量定义
define('SUCCESS', 1);
define('LOGIN_EXPIRE', -1000);
define('UPDATA_FAIL', -1001);
define('DELETE_FAIL', -1002);
define('DELETE_PROMOTIONGOODS_FAIL', -1010);
define('SYSTEM_DELETE_FAIL', -1003);
define('WEIXIN_AUTH_ERROR', -1004);
define('NO_AITHORITY', -1005);
define('LACK_OF_PARAMETER', -1006);
define('SYSTEM_ERROR',-1007);
define('ADD_FAIL', -1008);
define('CHOOSE_STORE', -1009);
define('LOGIN_LACK_OF_PARAMETER', -1010);
define('PARAMETER_ERROR', -1011);
//用户变量定义
define('USER_ERROR', -2001);
define('USER_LOCK', -2002);
define('USER_NBUND', -2003);
define('USER_REPEAT', -2004);
define('PASSWORD_ERROR', -2005);
define('USER_WORDS_ERROR', -2006);
define('USER_ADDRESS_DELETE_ERROR', -2007);
define('USER_GROUP_ISUSE', -2008);
define('NO_LOGIN', -2009);
define('USER_HEAD_GET', -2010);
define('NO_COUPON', -2011);
define('USER_MOBILE_REPEAT', -2012);
define('USER_EMAIL_REPEAT', -2013);
define('USER_GROUP_REPEAT', -2014);
define('USER_WITHDRAW_NO_USE', -2015);
define('USER_WITHDRAW_BEISHU', -2016);
define('USER_WITHDRAW_MIN', -2017);
define('MEMBER_LEVEL_DELETE', -2018);
define('USER_NO_WITHDRAW', -2019);
define('USER_NO_AITHORITY', -9999);

//订单定义变量
define('ORDER_DELIVERY_ERROR', -4002);
define('LOW_STOCKS', -4003);
define('LOW_POINT', -4004);
define('LOW_BALANCE', -4006);
define('ORDER_PAY', -4005);
define('ORDER_CREATE_LOW_POINT', -4007);
define('ORDER_CREATE_LOW_PLATFORM_MONEY', -4008);
define('ORDER_CREATE_LOW_USER_MONEY', -4009);
define('CLOSE_POINT', -4010);
define('LOW_COIN', -4011);
define('NULL_EXPRESS_FEE', -4012);
define('NULL_EXPRESS', -4013);
define('ORDER_CASH_DELIVERY', -4014);
define('ORDER_GOODS_ZERO', -4015);
define('CHANNEL_ORDER_GOODS_ZERO', -4017);
define('ORDER_CREATE_FAIL', -4016);
//活动定义变量
define('ACTIVE_REPRET', -5001);

//发送邮件
define("EMAIL_SENDERROR", -6001);

//微信菜单
define("MAX_MENU_LENGTH", 3);//一级菜单数量
define("MAX_SUB_MENU_LENGTH", 5);//二级菜单数量
//注册错误提示
define('REGISTER_CONFIG_OFF', -2051);
define('REGISTER_MOBILE_CONFIG_OFF', -2052);
define('REGISTER_EMAIL_CONFIG_OFF', -2053);
define('REGISTER_PLAIN_CONFIG_OFF', -2054);
define('REGISTER_USERNAME_ERROR', -2055);
define('REGISTER_PASSWORD_ERROR', -2056);

define('UPLOAD_FILE_ERROR', -7001);
//插件提示
define('ADDONS_CATEGORY_NAME_REPEAT', -7002);
define('VERSION_ISUSE', -7003);
define('SHOPLEVEL_ISUSE', -7004);

//接口提示
define('SIGN_WRONG', -2020);
//支付密码验证
define('UNCORRECT', -8000);
define('REPEAT_NAME', -8001);
define('NONAME', -8002);
define('INSTANCE_TYPE_DELETE_ERROR', -8003);
define('REPEAT_WEBSITE_NAME', -8004);
define('REPEAT_USER_TEL', -8005);
//阿里云存储
define('ACCESS_ERROR', -8006);
//独立域名
define('REALMIP_ERROR', -8007);
define('REALMTWOIP_ERROR', -8008);
define('SSL_ERROR', -8009);
define('REALMIP_NEED', -8010);
define('WITHDRAW_FAIL', -9000);
//wap
define('MALL_CLOSE', -10000);
define('MALL_WAP_CLOSE', -10001);
define('APP_CLOSE', -10002);
define('NO_GROUP', -10003);
define('FAIL_STATUS', -10004);
define('ORDER_FAIL', -10005);
define('REFUND_FAIL', -10006);
define('GOODS_REPEAT', -10007);
define('GROUP_SUCCESS', -10008);
define('GROUP_FAIL', -10009);
define('CATEGORY_REPEAT', -10010);
define('TYPE_REPEAT', -10011);
define('QUESTION_NAME_REPEAT', -10012);
define('NO_SHOP', -10013);
define('NO_HERE', -10014);
define('CANT_CLOSE', -10015);
define('ASSIS_UNOPEN', -10016);
define('ATTR_REPEAT', -10017);

define('GROUP_UNSUCCESS', -10018);
define('APPLY_REPEAT', -10019);
define('REQUEST_FREQUENT', -10020);
define('COUNT_TOOMORE', -10021);
define('ETH_NO_USE', -10022);
define('LOW_ETH', -10023);
define('LOW_PLATFORM_ETH', -10024);
define('CREATE_PREPAY_ID', -10025);
define('CREATE_ORDER_ID', -10026);
define('APPLY_FAIL', -10027);
define('EOS_NO_USE', -10028);
define('LOW_PLATFORM_EOS', -10029);
define('LOW_EOS', -10030);
define('SPEC_REPEAT', -10031);
define('NO_SPEC', -10032);
define('NO_BUY_RAM', -10033);
define('PASSWORD_NO_AGREE', -10034);
define('APPLY_EOS_FAIL', -10035);
define('CREATE_CODE_FAIL', -10036);
function getErrorInfo($error_code)
{
    $system_error_arr = array(
        //基础变量
        APPLY_EOS_FAIL=>'创建申请eos失败',
        PASSWORD_NO_AGREE=>'与商城支付密码不一致',
        CREATE_CODE_FAIL=>'创建邀请码失败',
        SUCCESS => '操作成功',
        ADD_FAIL => '添加失败',
        UPDATA_FAIL => '修改失败',
        DELETE_FAIL => '删除失败',
        DELETE_PROMOTIONGOODS_FAIL => '活动商品无法删除',
        SYSTEM_DELETE_FAIL => '当前分类下存在子分类，不能删除!',
        NO_AITHORITY => '当前用户无权限',
        LACK_OF_PARAMETER => '缺少参数',
        SYSTEM_ERROR => '系统错误',
        UNCORRECT => '支付密码不正确',
        ACCESS_ERROR => 'accessKeyId或accessKeySecret不正确',
        LOGIN_EXPIRE => '登录信息已过期，请重新登录',
        WITHDRAW_FAIL=>'提现失败，请联系商家',
        LOGIN_LACK_OF_PARAMETER=>'登录缺少参数',
        PARAMETER_ERROR=>'参数错误',
        //用户变量定义
        USER_ERROR => '账号或者密码错误',
        USER_LOCK => '用户被锁定',
        USER_NBUND => '用户未绑定',
        USER_REPEAT => '当前用户已存在',
        PASSWORD_ERROR => '用户密码错误',
        USER_WORDS_ERROR => '用户名只能是数字或者英文字母',
        USER_ADDRESS_DELETE_ERROR => '当前用户默认地址不能删除',
        USER_GROUP_ISUSE => '当前用户组已被使用，不能删除',
        NO_LOGIN => '当前用户未登录',
        USER_HEAD_GET => '用户已领用过',
        NO_COUPON => '该券已被领完',
        USER_MOBILE_REPEAT => '用户手机重复',
        USER_EMAIL_REPEAT => '用户邮箱重复',
        ADDONS_CATEGORY_NAME_REPEAT => '插件分类名称重复',
        USER_GROUP_REPEAT => '用户组名称重复',
        USER_WITHDRAW_NO_USE => '会员提现功能未启用',
        USER_WITHDRAW_BEISHU => '提现倍数不符合',
        USER_WITHDRAW_MIN => '申请提现小于最低提现',
        MEMBER_LEVEL_DELETE => '该等级正在使用中,不可删除',
        USER_NO_AITHORITY => '所属版本没有权限访问，请联系平台方',
        ETH_NO_USE => '商城未开启ETH兑换',
        EOS_NO_USE => '商城未开启EOS兑换',

        //订单定义变量
        ORDER_DELIVERY_ERROR => '存在未发货订单',
        LOW_STOCKS => '库存不足',
        LOW_POINT => '用户积分不足',
        LOW_ETH => 'eth余额不足',
        LOW_EOS => 'eos余额不足',
        LOW_PLATFORM_ETH => '商家eth余额不足',
        LOW_PLATFORM_EOS => '商家eos余额不足',
        NO_BUY_RAM=>'请先购买内存才能创建eos钱包',
        CREATE_PREPAY_ID=>'生成预订单ID失败',
        CREATE_ORDER_ID=>'支付订单失败',
        APPLY_FAIL=>'申请失败',
        LOW_COIN => '用户购物币不足',
        CLOSE_POINT => '店铺积分功能未开启',
        ORDER_PAY => '订单已支付',
        ORDER_CREATE_LOW_POINT => '当前用户积分不足',
        ORDER_CREATE_LOW_PLATFORM_MONEY => '当前用户余额不足',
        ORDER_CREATE_LOW_USER_MONEY => '当前用户店铺余额不足',
        ORDER_CASH_DELIVERY => '当前地址不支持货到付款',
        NULL_EXPRESS_FEE => '当前收货地址暂不支持配送！',
        NULL_EXPRESS => '无货',
        ORDER_CREATE_FAIL => '订单创建失败',
        CHANNEL_ORDER_GOODS_ZERO => '渠道商商品异常',
        //活动定义变量

        ACTIVE_REPRET => '在同一时间段内存在相同商品的活动！',

        //注册错误提示
        REGISTER_CONFIG_OFF => '抱歉,商城暂未开启用户注册！',
        REGISTER_MOBILE_CONFIG_OFF => '抱歉,商城暂未开启用户手机注册！',
        REGISTER_EMAIL_CONFIG_OFF => '抱歉,商城暂未开启用户邮箱注册！',
        REGISTER_PLAIN_CONFIG_OFF => '抱歉,商城暂未开启用户普通注册！',
        REGISTER_USERNAME_ERROR => '你所填的账号不符合注册规则！',
        REGISTER_PASSWORD_ERROR => '你所填的密码不符合注册规则！',

        EMAIL_SENDERROR => '请开启或启用sockets扩展 和  socket_connect函数！',
        UPLOAD_FILE_ERROR => '文件权限不足！',
        VERSION_ISUSE => '当前商家版本已有商家，不能删除',
        SHOPLEVEL_ISUSE => '当前店铺等级已有店铺，不能删除',
        REPEAT_NAME => '店铺名称重复',
        NONAME => '店铺名称不能为空',
        INSTANCE_TYPE_DELETE_ERROR => '当前默认等级不能删除',
        REPEAT_WEBSITE_NAME => '公司名称已存在，请更换',
        REPEAT_USER_TEL => '手机号码已存在，请更换',

        //接口提示
        SIGN_WRONG => '签名错误',
        //域名
        REALMTWOIP_ERROR => '二级域名已存在，请重新填写',
        REALMIP_ERROR => '独立域名已存在，请重新填写',
        REALMIP_NEED => '请填写独立域名',
        SSL_ERROR => '请上传完整证书',

        MALL_CLOSE => '商城已过期',
        MALL_WAP_CLOSE => '商城移动端已关闭',
        APP_CLOSE => 'APP已关闭',
        NO_GROUP => '活动不存在',
        FAIL_STATUS => '活动未关闭',
        ORDER_FAIL => '订单错误，请稍后重试',
        REFUND_FAIL => '活动已关闭，但部分订单退款失败，请进入拼团记录重新退款',
        GOODS_REPEAT => '该商品已经参加过其他活动或者其他拼团，不能添加',
        GROUP_SUCCESS => '该团购已满员，无法参加',
        GROUP_FAIL => '该团购已结束，无法参加',
        CATEGORY_REPEAT => '分类名称已存在',
        TYPE_REPEAT => '等级名称已存在',
        QUESTION_NAME_REPEAT => '名称不能重复',
        NO_HERE => '数据不存在，请刷新重试',
        CANT_CLOSE => '该应用有活动中的数据，暂时无法关闭，必须先关闭该应用下所有活动或等活动结束后再进行操作！',
        ASSIS_UNOPEN => '店员已在其他门店启用',
        ATTR_REPEAT => '属性名称不能重复',
        SPEC_REPEAT => '规格名称不能重复',
        GROUP_UNSUCCESS => '订单未成团无法提货',
        APPLY_REPEAT => '已经提交过申请，请勿重复操作',
        REQUEST_FREQUENT => '请求过于频繁，请稍后重试',
        COUNT_TOOMORE => '一次最多采集10条数据，请检查采集数量',
        NO_SPEC => '规格已被删除，无法添加规格值',
    );
    if (array_key_exists($error_code, $system_error_arr)) {
        return $system_error_arr[$error_code];
    } elseif ($error_code > 0) {
        return '操作成功';
    } else {
        return '操作失败';
    }


}
 