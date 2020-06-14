<?php
namespace app\wapapi\controller;
use data\model\UserModel;
use data\service\Address;
use data\service\Member as Member;
use data\service\BaseService;
use data\service\WebSite as WebSite;
use think\Controller;
use think\Cookie;
use think\Db;
use think\Request;
use think\Session;
use data\service\Config;
\think\Loader::addNamespace('data', 'data/');
use data\service\AuthGroup;
class BaseController extends Controller
{

    public $user;

    protected $uid;

    protected $instance_id;
    
    protected $website_id;

    
    protected $is_member;

    protected $shop_name;

    protected $user_name;

    protected $shop_id;

    /**
     * 平台logo
     *
     * @var unknown
     */

    public $web_site;

    public $style;

    public $model;
    public $realm_ip;
    protected $is_seckill = 0;
    protected $is_bargain = 0;
    protected $is_channel = 0;
    protected $is_integral = 0;
    protected $is_coupon_type = 0;
    protected $is_full_cut = 0;
    protected $is_discount = 0;
    protected $is_distribution = 0;
//    protected $is_group_shopping = 0;
    protected $is_presell = 0;
    protected $groupshopping = 0;
    protected $gift_voucher = 0;
    protected $is_gift = 0;
    protected $is_shop = 0;
    protected $is_store = 0;
    protected $http = '';
    public function __construct()
    {
        Cookie::delete("default_client"); // 还原手机端访问
        parent::__construct();
        $base = new BaseService();
        $this->model = $base->getRequestModel();
        $website_id = checkUrl();
        if (empty($website_id) && is_numeric($website_id)) {
            echo json_encode(AjaxReturn(LACK_OF_PARAMETER), JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (Session::has($this->model . 'website_id') && Session::get($this->model . 'website_id') != $website_id) {
            // 处理已登录状态，切换平台，使得当前用户不在该平台下面仍然可以进行操作
            echo json_encode(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->website_id = $website_id;
        Session::set($this->model . 'website_id', $website_id);
        $controller = strtolower(Request::instance()->controller());
        $action = request()->action();
        $params = Request::instance()->param();
        $addons_action = isset($params['action']) ? strtolower($params['action']) : '';
        $addons_controller = isset($params['controller']) ? strtolower($params['controller']) : '';
        $is_ssl = Request::instance()->isSsl();
        $this->http = "http://";
        if($is_ssl){
            $this->http = 'https://';
        }
        if(!IS_CLI){ 
            if (!isApiLegal() && !in_array($action, ['wchaturlback', 'wchatpay', 'aliurlback', 'alipay', 'aliPayReturn', 'recieve_card', 'wchatpay_api','withdraw','withdrawurlback','payurlbacks','ethpayurlback','eospayurlback','refundurlback','tlurlback','setgoodgather','ethchainurlback','gpayurlback']) && !in_array($addons_action, ['showuserqrcode', 'userarchivevoucherpackage', 'userarchivecoupon']) && !in_array($addons_controller, ['goodhelper']) && $controller != 'polyapi' && $controller != 'gjpapi') {
                $data['code'] = -2;
                $data['message'] = '接口签名错误';

                if (request()->get('app_test')){
                    $app_key = $api_key = API_KEY;
                    foreach (request()->post() as $key => $value) {
                        $api_key .= $key;
                    }

                    // app sign
                    $module = strtolower(Request::instance()->module());
                    $controller = strtolower(Request::instance()->controller());
                    $action = strtolower(Request::instance()->action());
                    if ($controller . $action == 'addonsexecute') {
                        $params = Request::instance()->param();
                        $module = strtolower($params['addons']);
                        $controller = strtolower($params['controller']);
                        $action = strtolower($params['action']);
                    }
                    $module_key = $app_key . $module;
                    $controller_key = $app_key . $controller;
                    $action_key = $app_key . $action;

                    $data['php_sign'] = md5($api_key);
                    $data['app_sign'] = md5($module_key);
                    $data['api_key'] = $api_key;
                    $data['module_key'] = $module_key;
                    $data['controller_key'] = $controller_key;
                    $data['action_key'] = $action_key;
                    $data['app_post_sign'] = $_SERVER['HTTP_SIGN'];
                }
                echo json_encode($data, JSON_UNESCAPED_UNICODE);
                exit;
            }
            $this->web_site = new WebSite();
            $web_info = $this->web_site->getWebSiteInfo($this->website_id);
            if($web_info['realm_ip']){
                $this->realm_ip = $this->http.$web_info['realm_ip'];
            }else{
                $this->realm_ip = $this->http.$_SERVER['HTTP_HOST'];
            }

            if (!getUserId() && isWeixin() && !isMiniProgram() && !in_array($controller, ['config', 'custom']) &&
                !in_array($action, ['wchaturlback', 'wchatpay', 'aliurlback', 'alipay', 'aliPayReturn', 'recieve_card', 'wchatpay_api','goodslistindex','goodslist','goodsdetail','categoryinfo','gpayurlback']) &&
                !in_array($addons_action, ['showuserqrcode', 'userarchivevoucherpackage','shopsearch','shopinfo']) && !isAnti()) {
                    // 微信浏览器要求保持登陆状态
                    echo json_encode(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登录！'], JSON_UNESCAPED_UNICODE);
                    exit;

            }

            //是否有秒杀、砍价、渠道商、积分商城的应用
            $this->is_seckill = getAddons('seckill', $this->website_id);
            $this->is_bargain = getAddons('bargain', $this->website_id);
            $this->is_channel = getAddons('seckill', $this->website_id);
            $this->is_integral = getAddons('integral', $this->website_id);
            $this->is_coupon_type = getAddons('coupontype', $this->website_id);
            $this->is_full_cut = getAddons('fullcut', $this->website_id);
            $this->is_discount = getAddons('discount', $this->website_id);
            $this->is_presell = getAddons('presell',$this->website_id);
            $this->is_distribution = getAddons('distribution', $this->website_id);
            $this->is_shop = getAddons('shop', $this->website_id);
            $this->gift_voucher = getAddons('giftvoucher', $this->website_id);
            $this->is_gift = getAddons('gift', $this->website_id);
            $this->groupshopping = getAddons('groupshopping', $this->website_id);
            $this->is_store = getAddons('store', $this->website_id);
            // getWapCache();//开启缓存
            $this->initInfo();
        }
    }
    /*
     * 连接redis
     * **/
    public function connectRedis(){
        $config = new Config();
        $redis_config = $config->getRedisConfig(0);
        $host = '';
        $pwd = '';
        if($redis_config){
            $host = $redis_config['host'];
            $pwd = $redis_config['pass'];
        }
        if ($host && $pwd) {
            $port = 6379;
            $redis = new \Redis();
            if ($redis->connect($host, $port) == false) {
                return json(['code'=>-1, 'message'=>'redis服务连接失败']);
            }
            if ($redis->auth($pwd) == false) {
                return json(['code'=>-1, 'message'=>'redis密码错误']);
            }
        }else{
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
        }
        return $redis;
    }
    public function initInfo()
    {
        $this->user = new Member();
        $this->uid = getUserId();
        $ssl =  \think\Request::instance()->domain().\think\Request::instance()->url();
        if(strpos($ssl, '/menu/addonmenu')){
            $auth_method = \request()->param('addons');
            if($auth_method){
                $auth = new AuthGroup();
                $auth_control = $auth->checkMethod($auth_method);
                if($auth_control==1){
                    //$uid = $this->user->getSessionUid();
                    if (empty($this->uid)) {
                        echo json_encode(['code' => LOGIN_EXPIRE, 'message' => '登录信息已过期，请重新登陆'], JSON_UNESCAPED_UNICODE);exit;
                    }
                }
            }
        }
        $this->instance_id = 0;
        $this->shop_id = request()->get('shop_id', 0);
        $this->shop_name = $this->user->getInstanceName();
    }

    public function returnJson($data)
    {
        ob_clean();
        header('Content-Type:application/json');
        $result = json_encode($data);

    }

    /**
     * 获取省列表
     */
    public function getProvince()
    {
        $address = new Address();
        $province_list = $address->getProvinceList();
        return $province_list;
    }

    /**
     * 获取城市列表
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:, array>
     */
    public function getCity()
    {
        $address = new Address();
        $province_id = request()->post('province_id', 0);
        $city_list = $address->getCityList($province_id);
        return $city_list;
    }

    /**
     * 获取区域地址
     */
    public function getDistrict()
    {
        $address = new Address();
        $city_id = request()->post('city_id', 0);
        $district_list = $address->getDistrictList($city_id);
        return $district_list;
    }
}