<?php
// +----------------------------------------------------------------------
// | 订单下单，进行弹幕操作（写入redis队列，存入订单弹幕虚拟表）
// +----------------------------------------------------------------------
// | Copyright (c) 微商来
// +----------------------------------------------------------------------
// | Author: sgw
// +----------------------------------------------------------------------

namespace data\extend\hook;

use addons\miniprogram\model\WeixinAuthModel;
use addons\miniprogram\service\MiniProgram as MiniProgramServer;
use data\extend\WchatOpen;
use data\model\DistrictModel;
use data\model\UserModel;
use data\model\VslMemberAccountModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderModel;
use data\model\VslOrderRefundModel;
use data\service\BaseService;
use think\Request;

class MpMessage
{

    const MP_TEMPLATE_CONFIG = 'mp_template_config';//配置redis缓存

    const PAY_SUCCESS = 'pay_success';//付款成功
    const ORDER_CLOSE = 'order_close';//订单关闭
    const BALANCE_CHANGE = 'balance_change';//余额变动
    const REFUND_INFO = 'refund_info';//售后情况

    protected $template_config;//小程序 - 订阅消息配置
    protected $miniprogramServer;

    public function __construct()
    {
        $miniprogram = getcwd() . DS . 'addons' . DS . 'miniprogram' . DS;
        if(!is_dir($miniprogram)){
                return false;
        }
        $this->miniprogramServer = new MiniProgramServer();
    }
    /************************** 订阅消息 START **************************************/
    /**
     * 1、付款成功
     * @param null $params
     * @return array|void
     */
    public function orderPayMpByTemplate($params = null)
    {
        debugLog($params, '1.0、订阅消息_支付==> ');
        $page = $params['page'] ?: 'pages/order/list/index';
        $website_id = $params['website_id'];
        $shop_id = 0;
        $order_id = $params['order_id'];
        if (!$website_id || !$order_id) {return;}
        $orderInfo = $this->getOrderIdByOrderId($order_id);
        debugLog($orderInfo, '1.1、订阅消息_支付(订单信息)==> ');
        $uid = $orderInfo['buyer_id'];
        $http_from = $orderInfo['http_from'];
        $order_status = $orderInfo['order_status'];
        $order_no = $orderInfo['order_no'];
        if (!$uid || $http_from != 1) {return;}
        $mp_open_id = $this->getUserMpOpenId($uid);
        if (!$mp_open_id) {return;}
        // 商城后台订阅消息配置
        $config_name = $website_id .'_'.$shop_id .'_'.self::MP_TEMPLATE_CONFIG;//eg 2_0_mp_template_config
        debugLog($config_name, '1.2、订阅消息_支付(配置名)==> ');
        try {
            if (!$this->template_config) {
                $baseServer = new BaseService();
                $redis = $baseServer->connectRedis();
                $redisRes = $redis->get($config_name);
                $this->template_config = unserialize($redisRes);
                // todo... 如果没有查询数据库
            }

            //判断是否可以发送模板并获取模板id
            $result = $this->matchUserMpTemplate($uid, self::PAY_SUCCESS);
            debugLog($result, '1.3、订阅消息_支付(模板)==> ');

            if ($result && $this->template_config) {
                // 发送模板消息
                //1、查询订单信息 2、根据模板配置替换变量内容
                $template_data = $this->template_config[$result['id']];
                $key_list = json_decode($template_data['key_list'], true);
                // 1、查询订单基本数据
                $orderModel = new VslOrderModel();
                $order_info = $orderModel::get(['order_id' => $order_id],['order_goods']);
                // 查询收货地址
                $district_id = $order_info['receiver_district'];
                $address = $this->getOrderAddress($district_id);
                $address .= $order_info['receiver_address'];
                $orderInfo = [
                    'order_no' => $order_info['order_no'],
                    'order_money' => $order_info['order_money'],
                    'goods_name' => $order_info['order_goods'][0]['goods_name'],
                    'address' => $address,
                    'create_time' => $order_info['create_time'],
                    'order_status' => $order_status,
                ];
                debugLog($orderInfo, '1.4、订阅消息_支付(转换数据)==> ');

                // 2、组装数据
                $data = [];
                foreach ($key_list as $k => $list) {
                    $content = $this->miniprogramServer->matchSubMessageVariable($list['content'], $list['ids'], self::PAY_SUCCESS, $orderInfo);
                    $str = trim($list['value'], "{{ }}");
                    $key = explode('.', $str)[0];
                    switch ($key) {
                        case strpos($key, 'thing'):
                            $content = mb_substr($content, 0, 20, 'utf-8');
                            break;
                        case strpos($key, 'number'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'letter'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'symbol'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                        case strpos($key, 'character_string'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'time'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'date'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'amount'):
                            $content = '￥'.abs($content).'元';
                            break;
                        case strpos($key, 'phone_number'):
                            $content = mb_substr($content, 0, 17, 'utf-8');
                            break;
                        case strpos($key, 'car_number'):
                            $content = mb_substr($content, 0, 8, 'utf-8');
                            break;
                        case strpos($key, 'name'):
                            $content = mb_substr($content, 0, 10, 'utf-8');
                            break;
                        case strpos($key, 'phrase'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                    }
                    $data[$key] = [
                        'value' => $content ?: ' '
                    ];
                }
                //组装完成数据
                $send_data = [
                    'touser' => $mp_open_id,
                    'template_id' => $result['template_id'],
                    'page' => $page,
                    'data' => $data
                ];
                debugLog($send_data, '1.5、订阅消息_支付(发送信息)==> ');

                // 发送
                $wx_auth = new WeixinAuthModel();
                unset($result);
                $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
                $authorizer_access_token = $result['authorizer_access_token'] ?: '';
                $weixin = new WchatOpen($website_id);
                $result = $weixin->sendMpMessageOfSubscribe($authorizer_access_token, $send_data);
                debugLog($result, '1.6、订阅消息_支付(回调)==> ');
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), '1.7、订阅消息_支付_'.$uid.':=>');
        }
    }

    /**
     *  2、订单关闭
     * @param null $params
     * @return array|void
     */
    public function orderCloseMpByTemplate($params = null)
    {
        debugLog($params, '2.0、订阅消息_关闭==> ');
        if (!$params) {return;}
        $page = $params['page'] ?: 'pages/order/list/index';
        $website_id = $params['website_id'];
        $shop_id = 0;
        $order_id = $params['order_id'];
        if (!$website_id || !$order_id) {return;}
        $orderInfo = $this->getOrderIdByOrderId($order_id);
        debugLog($orderInfo, '2.1、订阅消息_关闭(订单信息)==> ');
        $uid = $orderInfo['buyer_id'];
        $http_from = $orderInfo['http_from'];
        $order_status = $orderInfo['order_status'];
        $order_no = $orderInfo['order_no'];
        if (!$uid || $http_from != 1) {return;}
        $mp_open_id = $this->getUserMpOpenId($uid);
        if (!$mp_open_id) {return;}
        // 商城后台订阅消息配置
        $config_name = $website_id .'_'.$shop_id .'_'.self::MP_TEMPLATE_CONFIG;//eg 2_0_mp_template_config
        debugLog($config_name, '2.2、订阅消息_关闭(配置名)==> ');
        try {
            if (!$this->template_config) {
                $baseServer = new BaseService();
                $redis = $baseServer->connectRedis();
                $redisRes = $redis->get($config_name);
                $this->template_config = unserialize($redisRes);
                // todo... 如果没有查询数据库
            }
            //判断是否可以发送模板并获取模板id
            $result = $this->matchUserMpTemplate($uid, self::ORDER_CLOSE);
            debugLog($result, '2.3、订阅消息_关闭(模板)==> ');
            if ($result && $this->template_config) {
                // 发送模板消息
                //1、查询订单信息 2、根据模板配置替换变量内容
                $template_data = $this->template_config[$result['id']];
                $key_list = json_decode($template_data['key_list'], true);

                // 1、查询订单基本数据
                $orderModel = new VslOrderModel();
                $order_info = $orderModel::get(['order_id' => $order_id],['order_goods']);
                // 查询收货地址
                $district_id = $order_info['receiver_district'];
                $address = $this->getOrderAddress($district_id);
                $address .= $order_info['receiver_address'];
                $orderInfo = [
                    'order_no' => $order_info['order_no'],
                    'order_money' => $order_info['order_money'],
                    'goods_name' => $order_info['order_goods'][0]['goods_name'],
                    'address' => $address,
                    'create_time' => $order_info['create_time'],
                    'order_status' => $order_status,
                ];
                debugLog($orderInfo, '2.4、订阅消息_关闭(转换数据)==> ');

                // 2、组装数据
                $data = [];
                foreach ($key_list as $k => $list) {
                    $content = $this->miniprogramServer->matchSubMessageVariable($list['content'], $list['ids'], self::ORDER_CLOSE, $orderInfo);
                    $str = trim($list['value'], "{{ }}");
                    $key = explode('.', $str)[0];
                    switch ($key) {
                        case strpos($key, 'thing'):
                            $content = mb_substr($content, 0, 20, 'utf-8');
                            break;
                        case strpos($key, 'number'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'letter'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'symbol'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                        case strpos($key, 'character_string'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'time'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'date'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'amount'):
                            $content = '￥'.abs($content).'元';
                            break;
                        case strpos($key, 'phone_number'):
                            $content = mb_substr($content, 0, 17, 'utf-8');
                            break;
                        case strpos($key, 'car_number'):
                            $content = mb_substr($content, 0, 8, 'utf-8');
                            break;
                        case strpos($key, 'name'):
                            $content = mb_substr($content, 0, 10, 'utf-8');
                            break;
                        case strpos($key, 'phrase'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                    }
                    $data[$key] = [
                        'value' => $content ?: ' '
                    ];
                }

                //组装完成数据
                $send_data = [
                    'touser' => $mp_open_id,
                    'template_id' => $result['template_id'],
                    'page' => $page,
                    'data' => $data
                ];
                debugLog($send_data, '2.5、订阅消息_关闭(发送数据)==> ');

                // 发送
                $wx_auth = new WeixinAuthModel();
                unset($result);
                $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
                $authorizer_access_token = $result['authorizer_access_token'] ?: '';
                $weixin = new WchatOpen($website_id);
                $result = $weixin->sendMpMessageOfSubscribe($authorizer_access_token, $send_data);
                debugLog($result, '2.6、订阅消息_关闭(回调信息)==> ');
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), '2.7、订阅消息_关闭_'.$uid.':=>');
        }

    }

    /**
     * 3、余额变动 - 提现 - 成功
     * @param null $params
     * @return array|void
     */
    public function successfulWithdrawalsByMpTemplate($params = null)
    {
        debugLog($params, '3.0、订阅消息_提现_成功==> ');
        if (!$params) {return;}
        $page = $params['page'] ?: 'pages/member/index';
        $website_id = $params['website_id'];
        $shop_id = 0;
        $uid = $params['uid'];
        $money = abs($params['takeoutmoney']);//变动金额
        $withdraw_no = $params['withdraw_no'];//变动流水
        $create_time = $params['create_time'];//提现申请时间
        $status = $params['status'];//提现状态
        if (!$website_id || !$uid || !$money) {return;}
        $mp_open_id = $this->getUserMpOpenId($uid);
        if (!$mp_open_id) {return;}
        $balance = $this->getUserBalance($uid);//账户余额
        // 商城后台订阅消息配置
        $config_name = $website_id .'_'.$shop_id .'_'.self::MP_TEMPLATE_CONFIG;//eg 2_0_mp_template_config
        debugLog($config_name, '3.1、订阅消息_提现_成功(配置名)==> ');
        try {
            if (!$this->template_config) {
                $baseServer = new BaseService();
                $redis = $baseServer->connectRedis();
                $redisRes = $redis->get($config_name);
                $this->template_config = unserialize($redisRes);
                // todo... 如果没有查询数据库
            }
            //判断是否可以发送模板并获取模板id
            $result = $this->matchUserMpTemplate($uid, self::BALANCE_CHANGE);
            debugLog($result, '3.2、订阅消息_提现_成功(模板)==> ');
            if ($result && $this->template_config) {
                // 发送模板消息
                //1、查询订单信息 2、根据模板配置替换变量内容
                $template_data = $this->template_config[$result['id']];
                $key_list = json_decode($template_data['key_list'], true);

                //提现状态
                switch ($status) {
                    case 1:
                        $state = '待审核';
                        break;
                    case 2:
                        $state = '待打款';
                        break;
                    case 3:
                        $state = '已打款';
                        break;
                    case 4:
                        $state = '拒绝打款';
                        break;
                    case -1:
                        $state = '审核不通过';
                        break;
                    default :
                        $state = '';
                        break;
                }

                // 1、查询订单基本数据
                $orderInfo = [
                    'type' => '流水号：'.$withdraw_no,
                    'reason' => '提现',
                    'money' => $money,
                    'balance' => $balance,
                    'create_time' => $create_time,
                    'state' => $state,
                ];
                // 查询收货地址
                $district_id = $orderInfo['receiver_district'];
                $address = $this->getOrderAddress($district_id);
                $address .= $orderInfo['receiver_address'];
                $orderInfo['address'] = $address;
                debugLog($orderInfo, '3.3、订阅消息_提现_成功(转换数据)==> ');

                // 2、组装数据
                $data = [];
                foreach ($key_list as $k => $list) {
                    $content = $this->miniprogramServer->matchSubMessageVariable($list['content'], $list['ids'], self::BALANCE_CHANGE, $orderInfo);
                    $str = trim($list['value'], "{{ }}");
                    $key = explode('.', $str)[0];
                    switch ($key) {
                        case strpos($key, 'thing'):
                            $content = mb_substr($content, 0, 20, 'utf-8');
                            break;
                        case strpos($key, 'number'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'letter'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'symbol'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                        case strpos($key, 'character_string'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'time'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'date'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'amount'):
                            $content = '￥'.abs($content).'元';
                            break;
                        case strpos($key, 'phone_number'):
                            $content = mb_substr($content, 0, 17, 'utf-8');
                            break;
                        case strpos($key, 'car_number'):
                            $content = mb_substr($content, 0, 8, 'utf-8');
                            break;
                        case strpos($key, 'name'):
                            $content = mb_substr($content, 0, 10, 'utf-8');
                            break;
                        case strpos($key, 'phrase'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                    }
                    $data[$key] = [
                        'value' => $content ?: ' '
                    ];
                }

                //组装完成数据
                $send_data = [
                    'touser' => $mp_open_id,
                    'template_id' => $result['template_id'],
                    'page' => $page,
                    'data' => $data
                ];
                debugLog($send_data, '3.4、订阅消息_提现_成功(发送数据)==> ');

                // 发送
                $wx_auth = new WeixinAuthModel();
                unset($result);
                $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
                $authorizer_access_token = $result['authorizer_access_token'] ?: '';
                $weixin = new WchatOpen($website_id);
                $result = $weixin->sendMpMessageOfSubscribe($authorizer_access_token, $send_data);
                debugLog($result, '3.5、订阅消息_提现_成功(回调信息)==> ');
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), '3.6、订阅消息_提现_成功_'.$uid.':=>');
        }

    }

    /**
     * 4、余额变动 - 提现 - 失败
     * @param null $params
     * @return array|void
     */
    public function failureWithdrawalsByMpTemplate($params = null)
    {
        debugLog($params, '4.0、订阅消息_提现_失败==> ');
        if (!$params) {return;}
        $page = $params['page'] ?: 'pages/member/index';
        $website_id = $params['website_id'];
        $shop_id = 0;
        $uid = $params['uid'];
        $money = abs($params['money']);//变动金额
        $withdraw_no = $params['withdraw_no'];//变动流水
        $create_time = $params['create_time'];//提现申请时间
        $reason = $params['refusal'] ?: '余额不足';//决绝原因

        if (!$website_id || !$uid || !$money) {return;}
        $mp_open_id = $this->getUserMpOpenId($uid);
        if (!$mp_open_id) {return;}
        $balance = $this->getUserBalance($uid);//账户余额

        // 商城后台订阅消息配置
        $config_name = $website_id .'_'.$shop_id .'_'.self::MP_TEMPLATE_CONFIG;//eg 2_0_mp_template_config
        debugLog($config_name, '4.1、订阅消息_提现_失败(配置名)==> ');
        try {
            if (!$this->template_config) {
                $baseServer = new BaseService();
                $redis = $baseServer->connectRedis();
                $redisRes = $redis->get($config_name);
                $this->template_config = unserialize($redisRes);
                // todo... 如果没有查询数据库
            }
            //判断是否可以发送模板并获取模板id
            $result = $this->matchUserMpTemplate($uid, self::BALANCE_CHANGE);
            debugLog($result, '4.2、订阅消息_提现_失败(模板)==> ');

            if ($result && $this->template_config) {
                // 发送模板消息
                //1、查询订单信息 2、根据模板配置替换变量内容
                $template_data = $this->template_config[$result['id']];
                $key_list = json_decode($template_data['key_list'], true);
                // 1、查询订单基本数据
                $orderInfo = [
                    'type' => '流水号：'.$withdraw_no,
                    'reason' => $reason,
                    'money' => $money,
                    'balance' => $balance,
                    'create_time' => $create_time,
                    'state' => '提现失败',
                ];
                // 查询收货地址
                $district_id = $orderInfo['receiver_district'];
                $address = $this->getOrderAddress($district_id);
                $address .= $orderInfo['receiver_address'];
                $orderInfo['address'] = $address;
                debugLog($orderInfo, '4.3、订阅消息_提现_失败(转换数据)==> ');

                // 2、组装数据
                $data = [];
                foreach ($key_list as $k => $list) {
                    $content = $this->miniprogramServer->matchSubMessageVariable($list['content'], $list['ids'], self::BALANCE_CHANGE, $orderInfo);
                    $str = trim($list['value'], "{{ }}");
                    $key = explode('.', $str)[0];
                    switch ($key) {
                        case strpos($key, 'thing'):
                            $content = mb_substr($content, 0, 20, 'utf-8');
                            break;
                        case strpos($key, 'number'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'letter'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'symbol'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                        case strpos($key, 'character_string'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'time'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'date'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'amount'):
                            $content = '￥'.abs($content).'元';
                            break;
                        case strpos($key, 'phone_number'):
                            $content = mb_substr($content, 0, 17, 'utf-8');
                            break;
                        case strpos($key, 'car_number'):
                            $content = mb_substr($content, 0, 8, 'utf-8');
                            break;
                        case strpos($key, 'name'):
                            $content = mb_substr($content, 0, 10, 'utf-8');
                            break;
                        case strpos($key, 'phrase'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                    }
                    $data[$key] = [
                        'value' => $content ?: ' '
                    ];
                }

                //组装完成数据
                $send_data = [
                    'touser' => $mp_open_id,
                    'template_id' => $result['template_id'],
                    'page' => $page,
                    'data' => $data
                ];
                debugLog($send_data, '4.4、订阅消息_提现_失败(发送数据)==> ');

                // 发送
                $wx_auth = new WeixinAuthModel();
                unset($result);
                $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
                $authorizer_access_token = $result['authorizer_access_token'] ?: '';
                $weixin = new WchatOpen($website_id);
                $result = $weixin->sendMpMessageOfSubscribe($authorizer_access_token, $send_data);
                debugLog($result, '4.5、订阅消息_提现_失败(回调信息)==> ');
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), '4.6、订阅消息_提现_失败'.$uid.':=>');
        }

    }

    /**
     * 5、余额变动 - 充值
     * @param null $params
     * @return array|void
     */
    public function successfulRechargeByMpTemplate($params = null)
    {
        debugLog($params, '5.0、订阅消息_充值==> ');
        // todo.. 过滤小程序
        if (!$params) {return;}
        $page = $params['page'] ?: 'pages/member/index';
        $website_id = $params['website_id'];
        $shop_id = 0;
        $uid = $params['uid'];
        $money = abs($params['pay_money']);//充值金额
        $out_trade_no = $params['out_trade_no'];//交易号
        if (!$website_id || !$uid || !$money) {return;}
        $mp_open_id = $this->getUserMpOpenId($uid);
        if (!$mp_open_id) {return;}
        $balance = $this->getUserBalance($uid);//账户余额
        // 商城后台订阅消息配置
        $config_name = $website_id .'_'.$shop_id .'_'.self::MP_TEMPLATE_CONFIG;//eg 2_0_mp_template_config
        debugLog($config_name, '5.1、订阅消息_充值(配置)==> ');
        try {
            if (!$this->template_config) {
                $baseServer = new BaseService();
                $redis = $baseServer->connectRedis();
                $redisRes = $redis->get($config_name);
                $this->template_config = unserialize($redisRes);
                // todo... 如果没有查询数据库
            }
            //判断是否可以发送模板并获取模板id
            $result = $this->matchUserMpTemplate($uid, self::BALANCE_CHANGE);
            debugLog($result, '5.2、订阅消息_充值(模板)==> ');
            if ($result && $this->template_config) {
                // 发送模板消息
                //1、查询订单信息 2、根据模板配置替换变量内容
                $template_data = $this->template_config[$result['id']];
                $key_list = json_decode($template_data['key_list'], true);
                // 1、查询订单基本数据
                $orderInfo = [
                    'type' => '流水号：'.$out_trade_no,
                    'reason' => '充值',//变动原因
                    'money' => $money,
                    'balance' => $balance,
                ];
                // 查询收货地址
                $district_id = $orderInfo['receiver_district'];
                $address = $this->getOrderAddress($district_id);
                $address .= $orderInfo['receiver_address'];
                $orderInfo['address'] = $address;
                debugLog($orderInfo, '5.3、订阅消息_充值(转换数据)==> ');

                // 2、组装数据
                $data = [];
                foreach ($key_list as $k => $list) {
                    $content = $this->miniprogramServer->matchSubMessageVariable($list['content'], $list['ids'], self::BALANCE_CHANGE, $orderInfo);
                    $str = trim($list['value'], "{{ }}");
                    $key = explode('.', $str)[0];
                    switch ($key) {
                        case strpos($key, 'thing'):
                            $content = mb_substr($content, 0, 20, 'utf-8');
                            break;
                        case strpos($key, 'number'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'letter'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'symbol'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                        case strpos($key, 'character_string'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'time'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'date'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'amount'):
                            $content = '￥'.abs($content).'元';
                            break;
                        case strpos($key, 'phone_number'):
                            $content = mb_substr($content, 0, 17, 'utf-8');
                            break;
                        case strpos($key, 'car_number'):
                            $content = mb_substr($content, 0, 8, 'utf-8');
                            break;
                        case strpos($key, 'name'):
                            $content = mb_substr($content, 0, 10, 'utf-8');
                            break;
                        case strpos($key, 'phrase'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                    }
                    $data[$key] = [
                        'value' => $content ?: ' '
                    ];
                }

                //组装完成数据
                $send_data = [
                    'touser' => $mp_open_id,
                    'template_id' => $result['template_id'],
                    'page' => $page,
                    'data' => $data
                ];
                debugLog($send_data, '5.4、订阅消息_充值(发送数据)==> ');

                // 发送
                $wx_auth = new WeixinAuthModel();
                unset($result);
                $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
                $authorizer_access_token = $result['authorizer_access_token'] ?: '';
                $weixin = new WchatOpen($website_id);
                $result = $weixin->sendMpMessageOfSubscribe($authorizer_access_token, $send_data);
                debugLog($result, '5.5、订阅消息_充值(回调信息)==> ');
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), '5.6、订阅消息_充值'.$uid.':=>');
        }

    }

    /**
     * 6、售后 - 同意退货
     * @param null $params
     * @return array|void
     */
    public function agreedReturnByMpTemplate($params = null)
    {
        // todo... 区分小程序
        debugLog($params, '6.0、订阅消息_退货==> ');
        if (!$params) {return;}
        $page = $params['page'] ?: 'pages/order/list/index';
        $website_id = $params['website_id'];
        $shop_id = 0;
        $order_id = $params['order_id'];
        $order_goods_id = $params['order_goods_id'][0] ? : $params['order_goods_id'];//商品id
        $refund_type = $params['refund_type'] == 1? '退款' : '退货';//售后类型 1退款 2退货

        if (!$website_id || !$order_id) {return;}
        $orderInfo = $this->getOrderIdByOrderId($order_id);
        $uid = $orderInfo['buyer_id'];
        $http_from = $orderInfo['http_from'];
        $order_status = $orderInfo['order_status'];
        $order_no = $orderInfo['order_no'];
        if (!$uid || $http_from != 1) {return;}
        $mp_open_id = $this->getUserMpOpenId($uid);
        if (!$mp_open_id) {return;}
        $balance = $this->getUserBalance($uid);//账户余额

        // 商城后台订阅消息配置
        $config_name = $website_id .'_'.$shop_id .'_'.self::MP_TEMPLATE_CONFIG;//eg 2_0_mp_template_config
        debugLog($config_name, '6.1、订阅消息_退货(配置名)==> ');

        try {
            if (!$this->template_config) {
                $baseServer = new BaseService();
                $redis = $baseServer->connectRedis();
                $redisRes = $redis->get($config_name);
                $this->template_config = unserialize($redisRes);
                // todo... 如果没有查询数据库
            }

            //判断是否可以发送模板并获取模板id
            $result = $this->matchUserMpTemplate($uid, self::REFUND_INFO);
            debugLog($result, '6.2、订阅消息_退货(模板)==> ');

            if ($result && $this->template_config) {
                // 发送模板消息
                //1、查询订单信息 2、根据模板配置替换变量内容
                $template_data = $this->template_config[$result['id']];
                $key_list = json_decode($template_data['key_list'], true);

                $order_model = new VslOrderGoodsModel();
                $page = 'pages/order/detail/index?orderId='.$order_id;//订单详情
                $order_obj = $order_model->getInfo(['order_goods_id' => $order_goods_id], 'goods_name');
                $goods_name = $order_obj['goods_name'];

                // 查询退款状态
                $state = '';
                $orderRefundModel = new VslOrderRefundModel();
                $refund_status = $orderRefundModel->getFirstData(['order_goods_id' => $order_goods_id], 'id desc', 'refund_status');
                switch ($refund_status['refund_status']) {
                    case -3:
                        $state = '退款申请不通过';
                        break;
                    case 0:
                        $state = '关闭退款';
                        break;
                    case 1:
                        $state = '申请退款';
                        break;
                    case 2:
                        $state = '同意退货';
                        break;
                    case 3:
                        $state = '等待卖家确认收货';
                        break;
                    case 4:
                        $state = '等待卖家确认退款';
                        break;
                    case 5:
                        $state = '退款已成功';
                        break;
                    default:
                        break;
                }

                // 1、查询订单基本数据
                $orderInfo = [
                    'order_no' => $order_no,
                    'type' => $refund_type,
                    'state' => $state,//当前进度
                    'goods_name' => $goods_name,
                    'time' => time()- 200,
                    'balance' => $balance,
                ];

                // 查询收货地址
                $district_id = $orderInfo['receiver_district'];
                $address = $this->getOrderAddress($district_id);
                $address .= $orderInfo['receiver_address'];
                $orderInfo['address'] = $address;
                $orderInfo['order_status'] = $order_status;
                debugLog($orderInfo, '6.3、订阅消息_退货(转换数据)==> ');

                // 2、组装数据
                $data = [];
                foreach ($key_list as $k => $list) {
                    $content = $this->miniprogramServer->matchSubMessageVariable($list['content'], $list['ids'], self::REFUND_INFO, $orderInfo);
                    $str = trim($list['value'], "{{ }}");
                    $key = explode('.', $str)[0];
                    switch ($key) {
                        case strpos($key, 'thing'):
                            $content = mb_substr($content, 0, 20, 'utf-8');
                            break;
                        case strpos($key, 'number'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'letter'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'symbol'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                        case strpos($key, 'character_string'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'time'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'date'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'amount'):
                            $content = '￥'.abs($content).'元';
                            break;
                        case strpos($key, 'phone_number'):
                            $content = mb_substr($content, 0, 17, 'utf-8');
                            break;
                        case strpos($key, 'car_number'):
                            $content = mb_substr($content, 0, 8, 'utf-8');
                            break;
                        case strpos($key, 'name'):
                            $content = mb_substr($content, 0, 10, 'utf-8');
                            break;
                        case strpos($key, 'phrase'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                    }
                    $data[$key] = [
                        'value' => $content ?: ' '
                    ];
                }

                //组装完成数据
                $send_data = [
                    'touser' => $mp_open_id,
                    'template_id' => $result['template_id'],
                    'page' => $page,
                    'data' => $data
                ];
                debugLog($send_data, '6.4、订阅消息_退货(发送数据)==> ');

                // 发送
                $wx_auth = new WeixinAuthModel();
                unset($result);
                $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
                $authorizer_access_token = $result['authorizer_access_token'] ?: '';
                $weixin = new WchatOpen($website_id);
                $result = $weixin->sendMpMessageOfSubscribe($authorizer_access_token, $send_data);
                debugLog($result, '6.5、订阅消息_退货(回调)==> ');
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), '6.6、订阅消息_退货'.$uid.':=>');
        }

    }

    /**
     * 7、售后 - 退款
     * @param null $params
     * @return array|void
     */
    public function refundAfterSaleByMpTemplate($params = null)
    {
        // todo... 区分小程序
        debugLog($params, '7.0、订阅消息_退款==> ');
        if (!$params) {return;}
        $page = $params['page'] ?: 'pages/order/list/index';
        $website_id = $params['website_id'];
        $shop_id = 0;
        $order_id = $params['order_id'];
        $order_goods_id = $params['order_goods_id'][0] ? : $params['order_goods_id'];//商品id
        $reason = $params['refusal'] ? $params['refusal']: ' ';//拒绝理由
        $refund_type = $params['refund_type'] == 2 ? '退货' : '退款';//售后类型 1退款 2退货
        $refund_money = $params['refund_money'];
        $type = $params['type'] ?: 0;

        if (!$website_id || !$order_id) {return;}
        $orderInfo = $this->getOrderIdByOrderId($order_id);
        $uid = $orderInfo['buyer_id'];
        $http_from = $orderInfo['http_from'];
        $order_no = $orderInfo['order_no'];
        $order_status = $orderInfo['order_status'];
        if (!$uid || $http_from != 1) {return;}

        $mp_open_id = $this->getUserMpOpenId($uid);
        if (!$mp_open_id) {return;}
        $balance = $this->getUserBalance($uid);//账户余额

        // 商城后台订阅消息配置
        $config_name = $website_id .'_'.$shop_id .'_'.self::MP_TEMPLATE_CONFIG;//eg 2_0_mp_template_config
        debugLog($config_name, '7.1、订阅消息_退款(配置)==> ');

        try {
            if (!$this->template_config) {
                $baseServer = new BaseService();
                $redis = $baseServer->connectRedis();
                $redisRes = $redis->get($config_name);
                $this->template_config = unserialize($redisRes);
                // todo... 如果没有查询数据库
            }
            //判断是否可以发送模板并获取模板id
            $result = $this->matchUserMpTemplate($uid, self::REFUND_INFO);
            debugLog($result, '7.2、订阅消息_退款(模板)==> ');
            if ($result && $this->template_config) {
                // 发送模板消息
                //1、查询订单信息 2、根据模板配置替换变量内容
                $template_data = $this->template_config[$result['id']];
                $key_list = json_decode($template_data['key_list'], true);
                $order_model = new VslOrderGoodsModel();
                $order_obj = $order_model->getInfo(['order_goods_id' => $order_goods_id], 'goods_name');
                $goods_name = $order_obj['goods_name'];

                // 查询退款状态
                $state = '';
                $orderRefundModel = new VslOrderRefundModel();
                $refund_status = $orderRefundModel->getFirstData(['order_goods_id' => $order_goods_id], 'id desc', 'refund_status');
                switch ($refund_status['refund_status']) {
                    case -3:
                        $state = '退款申请不通过';
                        break;
                    case 0:
                        $state = '关闭退款';
                        break;
                    case 1:
                        $state = '申请退款';
                        break;
                    case 2:
                        $state = '同意退款';
                        break;
                    case 3:
                        $state = '等待卖家确认收货';
                        break;
                    case 4:
                        $state = '等待卖家确认退款';
                        break;
                    case 5:
                        $state = '退款已成功';
                        break;
                    default:
                        break;
                }

                // 1、查询订单基本数据
                $orderInfo = [
                    'order_no' => $order_no,
                    'type' => $refund_type,
                    'state' => $state,//当前进度
                    'goods_name' => $goods_name,
                    'time' => time()- 200,
                    'balance' => $balance,
                    'reason' => $reason,
                    'refund_money' => $refund_money,
                    'order_status' => $order_status,
                ];
                // 查询收货地址
                $district_id = $orderInfo['receiver_district'];
                $address = $this->getOrderAddress($district_id);
                $address .= $orderInfo['receiver_address'];
                $orderInfo['address'] = $address;
                debugLog($orderInfo, '7.3、订阅消息_退款(转换数据)==> ');

                // 2、组装数据
                $data = [];
                foreach ($key_list as $k => $list) {
                    $content = $this->miniprogramServer->matchSubMessageVariable($list['content'], $list['ids'], self::REFUND_INFO, $orderInfo);
                    $str = trim($list['value'], "{{ }}");
                    $key = explode('.', $str)[0];
                    switch ($key) {
                        case strpos($key, 'thing'):
                            $content = mb_substr($content, 0, 20, 'utf-8');
                            break;
                        case strpos($key, 'number'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'letter'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'symbol'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                        case strpos($key, 'character_string'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'time'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'date'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'amount'):
                            $content = '￥'.abs($content).'元';
                            break;
                        case strpos($key, 'phone_number'):
                            $content = mb_substr($content, 0, 17, 'utf-8');
                            break;
                        case strpos($key, 'car_number'):
                            $content = mb_substr($content, 0, 8, 'utf-8');
                            break;
                        case strpos($key, 'name'):
                            $content = mb_substr($content, 0, 10, 'utf-8');
                            break;
                        case strpos($key, 'phrase'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                    }
                    $data[$key] = [
                        'value' => $content ?: ' '
                    ];
                }
                //组装完成数据
                $send_data = [
                    'touser' => $mp_open_id,
                    'template_id' => $result['template_id'],
                    'page' => $page,
                    'data' => $data
                ];
                debugLog($send_data, '7.4、订阅消息_退款(发送数据)==> ');

                // 发送
                $wx_auth = new WeixinAuthModel();
                unset($result);
                $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
                $authorizer_access_token = $result['authorizer_access_token'] ?: '';
                $weixin = new WchatOpen($website_id);
                $result = $weixin->sendMpMessageOfSubscribe($authorizer_access_token, $send_data);
                debugLog($result, '7.5、订阅消息_退款(回调信息)==> ');
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), '7.6、订阅消息_退款'.$uid.':=>');
        }

    }

    /**
     * 8、余额变动
     * @param null $params
     * @return array|void
     */
    public function balanceChangeByMpTemplate($params = null)
    {
        debugLog($params, '8.0、订阅消息_余额变动==> ');
        $page = $params['page'] ?: 'pages/member/index';
        $website_id = $params['website_id'];
        $shop_id = 0;
        $order_id = $params['order_id'];
        $type = $params['type'];
        $state = '';

        if (!$website_id || !$order_id) {return;}
        $orderInfo = $this->getOrderIdByOrderId($order_id);
        $uid = $orderInfo['buyer_id'];
        $http_from = $orderInfo['http_from'];
        $order_no = $orderInfo['order_no'];
        $order_status = $orderInfo['order_status'];

        if (!$uid || $http_from != 1) {return;}
        $mp_open_id = $this->getUserMpOpenId($uid);
        $balance = $this->getUserBalance($uid);//账户余额
        if (!$mp_open_id) {return;}
        // 商城后台订阅消息配置
        $config_name = $website_id .'_'.$shop_id .'_'.self::MP_TEMPLATE_CONFIG;//eg 2_0_mp_template_config
        debugLog($config_name, '8.1、订阅消息_余额变动(配置名)==> ');

        try {
            if (!$this->template_config) {
                $baseServer = new BaseService();
                $redis = $baseServer->connectRedis();
                $redisRes = $redis->get($config_name);
                $this->template_config = unserialize($redisRes);
                // todo... 如果没有查询数据库
            }
            //判断是否可以发送模板并获取模板id
            $result = $this->matchUserMpTemplate($uid, self::BALANCE_CHANGE);
            debugLog($result, '8.2、订阅消息_余额变动(模板)==> ');

            if ($result && $this->template_config) {
                switch ($type)
                {
                    case 1:
                        $type = '订单支付';
                        $state = '支付完成';
                        break;
                    case 2:
                        $type = '退款';
                        $state = '退款完成';
                        break;
                    default :
                        break;
                }
                // 发送模板消息
                //1、查询订单信息 2、根据模板配置替换变量内容
                $template_data = $this->template_config[$result['id']];
                $key_list = json_decode($template_data['key_list'], true);
                // 1、查询订单基本数据
                $orderModel = new VslOrderModel();
                $order_info = $orderModel::get(['order_id' => $order_id],['order_goods']);
                // 替换数据
                $orderInfo = [
                    'type' => $type ?: '',
                    'reason' => '余额变动',
                    'money' => $order_info['order_money'],
                    'balance' => $balance,
                    'create_time' => $order_info['create_time'],
                    'state' => $state,
                    'order_status' => $order_status,
                ];
                debugLog($orderInfo, '8.3、订阅消息_余额变动(转换数据)==> ');

                // 2、组装数据
                $data = [];
                foreach ($key_list as $k => $list) {
                    $content = $this->miniprogramServer->matchSubMessageVariable($list['content'], $list['ids'], self::BALANCE_CHANGE, $orderInfo);
                    $str = trim($list['value'], "{{ }}");
                    $key = explode('.', $str)[0];
                    switch ($key) {
                        case strpos($key, 'thing'):
                            $content = mb_substr($content, 0, 20, 'utf-8');
                            break;
                        case strpos($key, 'number'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'letter'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'symbol'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                        case strpos($key, 'character_string'):
                            $content = mb_substr($content, 0, 32, 'utf-8');
                            break;
                        case strpos($key, 'time'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'date'):
                            $content = date('Y-m-d H:i:s', strtotime($content) == $content) ? $content : date('Y-m-d H:i:s', time());
                            break;
                        case strpos($key, 'amount'):
                            $content = '￥'.abs($content).'元';
                            break;
                        case strpos($key, 'phone_number'):
                            $content = mb_substr($content, 0, 17, 'utf-8');
                            break;
                        case strpos($key, 'car_number'):
                            $content = mb_substr($content, 0, 8, 'utf-8');
                            break;
                        case strpos($key, 'name'):
                            $content = mb_substr($content, 0, 10, 'utf-8');
                            break;
                        case strpos($key, 'phrase'):
                            $content = mb_substr($content, 0, 5, 'utf-8');
                            break;
                    }
                    $data[$key] = [
                        'value' => $content ?: ' '
                    ];
                }
                //组装完成数据
                $send_data = [
                    'touser' => $mp_open_id,
                    'template_id' => $result['template_id'],
                    'page' => $page,
                    'data' => $data
                ];
                debugLog($send_data, '8.4、订阅消息_余额变动(发送数据)==> ');

                // 发送
                $wx_auth = new WeixinAuthModel();
                unset($result);
                $result = $wx_auth->getInfo(['website_id' => $website_id, 'shop_id' => $shop_id], 'authorizer_access_token');
                $authorizer_access_token = $result['authorizer_access_token'] ?: '';
                $weixin = new WchatOpen($website_id);
                $result = $weixin->sendMpMessageOfSubscribe($authorizer_access_token, $send_data);
                debugLog($result, '8.5、订阅消息_余额变动(回调信息)==> ');
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), '8.6、订阅消息_余额变动'.$uid.':=>');
        }
    }

    /************************** 公共方法 *********************************************/

    /**
     * 发送模板消息通用处理方法：模板配置对应用户已经授权订阅消息的数据，如果后台存在该模板信息切用户也授权了，则才能发送对应模板消息
     * @param $uid int [用户id]
     * @param $html_id string [后台初始化小程序模板类型名 html_id  sys_mp_message_template表 ]
     * 【html_id: pay_success付款成功； order_close订单关闭; balance_change余额变动; 售后情况refund_info】
     * @return $template_id string [对应的模板id]
     */
    public function matchUserMpTemplate($uid, $html_id)
    {
        // 取出配置信息判断
        foreach ($this->template_config as $key => $config) {
            if ($config['html_id'] == $html_id) {//默认template_id=1为支付成功模板
                if ($config['status'] != 0) {
                    //发消息判断
                    // todo... 查询用户 拥有模板对应的权限
                    $userModel = new UserModel();
                    $userRes = $userModel->getInfo(['uid' => $uid], 'mp_sub_message');
                    if (!$userRes || !$userRes['mp_sub_message']) {return;}
                    $mp_sub_message = json_decode($userRes['mp_sub_message'], true);

                    foreach ($mp_sub_message as $m_k => $message) {
                        if ($config['html_id'] == $m_k) {//说明匹配到用户发送“支付成功”模板的数据
                            if ($message['action'] != 'accept') {return; }//用户不接受消息
                            //获取template_id
                            $template_id = $message['template_id'];
                            return [
                                'id' => $key,
                                'template_id' => $template_id
                            ];
                        }
                    }// foreach
                }

            }//if
        }
    }

    /**
     * 查询订单收货地址
     * @param $district_id int [订单区域id ‘receiver_district’]
     * @return string
     * @throws \think\Exception\DbException
     */
    public function getOrderAddress($district_id)
    {
        $address = '';
        $district_model = new DistrictModel();
        $address_info = $district_model::get($district_id, ['city.province']);
        $province = $address_info->city->province->province_name;
        $city = $address_info->city->city_name;
        $district = $address_info->district_name;

        $address = $province.$city.$district;
        return $address;
    }

    /**
     * 查询接受消息用户小程序open_id
     * @param $uid
     * @return mixed
     */
    public function getUserMpOpenId($uid)
    {
        $userModel = new UserModel();
        $result = $userModel->getInfo(['uid' => $uid], 'mp_open_id');

        return $result['mp_open_id'];
    }

    /**
     * 查询用户余额
     * @param $uid
     * @return int
     */
    public function getUserBalance($uid)
    {
        $member_model = new VslMemberAccountModel();
        $result = $member_model->getInfo(['uid' => $uid], 'balance');
        if ($result) {
            $balance = $result['balance'];
        } else {
            $balance = 0;
        }
        return $balance;
    }

    /**
     * 订单查询信息
     * @param $order_id
     * @return mixed
     */
    public function getOrderIdByOrderId($order_id)
    {
        $orderModel = new VslOrderModel();
        $orderInfo = $orderModel->getInfo(['order_id' => $order_id], 'order_no, buyer_id, http_from, order_status');
        //订单状态
        if ($orderInfo['order_status']) {
            switch ($orderInfo['order_status'])
            {
                case 0:
                    $order_status = '未支付';
                    break;
                case 1:
                    $order_status = '已付款';
                    break;
                case 2:
                    $order_status = '已发货';
                    break;
                case 3:
                    $order_status = '确认收货';
                    break;
                case 4:
                    $order_status = '已完成';
                    break;
                case 5:
                    $order_status = '已关闭';
                    break;
                case 6:
                    $order_status = '链上处理中';
                    break;
                default:
                    $order_status = '订单关闭';
                    break;
            }
            $orderInfo['order_status'] = $order_status;
        }
        return $orderInfo;
    }
}
