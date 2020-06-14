<?php
namespace app\shop\controller;

\think\Loader::addNamespace('data', 'data/');

use addons\areabonus\service\AreaBonus;
use addons\distribution\service\Distributor;
use addons\globalbonus\service\GlobalBonus;
use addons\teambonus\service\TeamBonus;
use data\service\AuthGroup;
use data\service\Config;
use data\service\GoodsCategory;
use data\service\Member as Member;
use data\service\BaseService;
use data\service\WebSite as WebSite;
use think\Controller;
use \think\Session as Session;
use data\service\AdminUser;
use data\model\SysPcCustomConfigModel;
use data\model\SysPcCustomNavConfigModel;
use data\model\SysPcCustomStyleConfigModel;
use data\extend\custom\Common;
use data\service\AddonsConfig as addonsConfig;
use addons\qlkefu\server\Qlkefu;
class BaseController extends Controller {

    public $user;
    protected $uid;
    protected $instance_id;
    protected $website_id;
    protected $is_member;
    protected $shop_name;
    protected $user_name;
    public $web_site;
    public $style;
    protected $rootid = null;
    protected $moduleid = null;
    protected $member_info;
    protected $second_menu_id = null;
    protected $distributionStatus = 0;
    protected $shopStatus = 0;
    protected $globalStatus = 0;
    protected $areaStatus = 0;
    protected $teamStatus = 0;
    protected $isCouponOn = 0;
    protected $is_discount = 0;
    protected $is_full_cut = 0;
    protected $regStatus = 0;
    protected $storeStatus = 0;
    protected $default_url;
    protected $is_seckill = 0;
    protected $is_bargain = 0;
    protected $is_channel = 0;
    protected $is_integral = 0;
    protected $is_gift = 0;
    protected $gift_voucher=0;
	protected $is_qlkefu=0;
    protected $http = '';
    // 验证码配置
    public $login_verify_code;

    public function __construct() {
        // 请求端（PC端、手机端）
        // getShopCache();//开启缓存
        if(!IS_CLI){
            parent::__construct();
            $is_ssl = \think\Request::instance()->isSsl();
            $this->http = "http://";
            if($is_ssl){
                $this->http = 'https://';
            }
            $base = new BaseService();
            $model = $base->getRequestModel();
            $this->user = new AdminUser();
            $website_id = checkUrl();
            if ($website_id && is_numeric($website_id)) {
                Session::set($model . 'website_id', $website_id);
                $this->website_id = $website_id;
            } elseif (Session::get($model . 'website_id')) {
                $this->website_id = Session::get($model . 'website_id');
            } else {
                $this->error("参数错误，请检查域名");
            }
            //文案样式
            $web_config = new Config();
            $copystyle = $web_config->getConfig(0,'COPYSTYLE');
            if($copystyle){
                $this->assign("copystyle", json_decode($copystyle['value'],true));
            }else{
                $copystyle['point_style'] = '积分';
                $copystyle['balance_style'] = '余额';
                $this->assign("copystyle", $copystyle);
            }
            $ConfigService = new addonsConfig();
            $pc_info = $ConfigService->getAddonsConfig('pcport',$this->website_id);
            $pc_info = json_decode($pc_info['value'],true);
            switch ($pc_info['pc_pop_rule']){
                case 1:
                    $pc_info['pc_pop_rule'] = 86400;
                    break;
                case 2:
                    $pc_info['pc_pop_rule'] = 86400*3;
                    break;
                case 3:
                    $pc_info['pc_pop_rule'] = 86400*5;
                    break;
                case 4:
                    $pc_info['pc_pop_rule'] = 86400 * date("t");
                    break;
                default :
                    $pc_info['pc_pop_rule'] = 0;
                    break;
            }
            if(!getAddons('pcport', $this->website_id) && !request()->get('suffix')){
                $action = request()->action();
                $redirect = __URLS('APP_MAIN/mall/index');
                if($action=='goodsinfo'){
                    $goods_id = request()->get('goodsid',0);
                    $redirect = __URLS('APP_MAIN/goods/detail/'. $goods_id);
                }
                $this->redirect($redirect);
            }
            $this->assign("pc_info", $pc_info);
            $this->init();
            $default_client = request()->cookie("default_client", "");
            $this->assign("default_client", $default_client);
            $this->assign("website_id", $this->website_id);
            $member = new Member();
            $this->member_info = $member->getMemberDetail();
            $this->assign('member_info', $this->member_info);
            $hasHelpcenter = getAddons('helpcenter', $this->website_id);//帮助中心应用是否存在
            $this->assign('hasHelpcenter',$hasHelpcenter);
            //查询是否有店铺应用
            $this->shopStatus = getAddons('shop', $this->website_id);
            $this->assign('shopStatus', $this->shopStatus);
            //查询是否有门店应用
            $this->storeStatus = getAddons('store', $this->website_id);
            $this->assign('storeStatus', $this->storeStatus);
            //查询是否有注册营销
            $this->regStatus = getAddons('registermarketing', $this->website_id);
            $this->assign('regStatus', $this->regStatus);
            // 查询是否有优惠券应用
            $this->isCouponOn = getAddons('coupontype', $this->website_id);
            $this->assign("isCoupon", $this->isCouponOn);
            // 限时折扣应用
            $this->is_discount = getAddons('discount', $this->website_id);
            // 满减送应用
            $this->is_full_cut = getAddons('fullcut', $this->website_id);
            //是否有秒杀、砍价、渠道商、积分商城的应用
            $this->is_seckill = getAddons('seckill', $this->website_id);
            $this->is_bargain = getAddons('bargain', $this->website_id);
            $this->is_channel = getAddons('seckill', $this->website_id);
            $this->is_integral = getAddons('integral', $this->website_id);
            $this->globalStatus = getAddons('globalbonus',$this->website_id);
            $this->areaStatus = getAddons('areabonus',$this->website_id);
            $this->teamStatus = getAddons('teambonus',$this->website_id);
            $this->is_gift = getAddons('gift', $this->website_id);
            $this->gift_voucher = getAddons('giftvoucher', $this->website_id);
			$this->is_qlkefu = getAddons('qlkefu', $this->website_id);
            $_SESSION['login_pre_url'] = $_SERVER['HTTP_REFERER'];
            //查询是否有分销应用
            $this->distributionStatus = getAddons('distribution', $this->website_id);
            if ($this->distributionStatus == 1) {
                $distribution = new Distributor();
                $dis_set = $distribution->getAgreementSite($this->website_id);
                if($dis_set){
                    $this->assign("distribution_name", $dis_set['distribution_name']);
                }
                $distribution_info = $ConfigService->getAddonsConfig('distribution',$this->website_id);
                if ($this->member_info) {
                    //查询是否有分红应用
                    if ($this->member_info['isdistributor'] == 2) {
                        $this->globalStatus = getAddons('globalbonus', $this->website_id);
                        $this->areaStatus = getAddons('areabonus', $this->website_id);
                        $this->teamStatus = getAddons('teambonus', $this->website_id);
                        if ($this->globalStatus == 1 || $this->areaStatus == 1 || $this->teamStatus == 1) {
                            $this->assign('bonusStatus', 1);
                        }
                    }
                    $this->assign('distributionInfo', $distribution_info);
                }
                $this->assign('distributionStatus', $this->distributionStatus);
                $this->assign('globalStatus', $this->globalStatus);
                $this->assign('areaStatus', $this->areaStatus);
                $this->assign('teamStatus', $this->teamStatus);
            }
            if($this->globalStatus || $this->areaStatus || $this->teamStatus){
                $config = new Config();
                $bonus_set = $config->getBonusSite($this->website_id);
                if($bonus_set){
                    $this->assign("bonus_set", $bonus_set);
                }
                if($this->globalStatus){
                    $config = new GlobalBonus();
                    $globalbonus_set = $config->getAgreementSite($this->website_id);
                    $this->assign("globalbonus_set", $globalbonus_set);
                }
                if($this->areaStatus){
                    $config = new AreaBonus();
                    $areabonus_set = $config->getAgreementSite($this->website_id);
                    $this->assign("areabonus_set", $areabonus_set);
                }
                if($this->teamStatus){
                    $config = new TeamBonus();
                    $teambonus_set = $config->getAgreementSite($this->website_id);
                    $this->assign("teambonus_set", $teambonus_set);
                }
            }
            //客服系统
            $this->assign("is_qlkefu", $this->is_qlkefu);
            if($this->is_qlkefu){
                $website_id = $this->website_id;
                $shop_id = request()->get('shop_id',0);
                $shop_id = ($shop_id>0)?$shop_id:0;
                $config = new Qlkefu();
                $qlkefu = $config->qlkefuConfig($website_id,$shop_id);
                $seller_domain = '';
                if($qlkefu['w_domain'] && $qlkefu['seller_code'] && $qlkefu['is_use']==1){
                    $seller_domain = $qlkefu['w_domain'].'/index/index/chatBoxJs/u/'.$qlkefu['seller_code'];
                }
                $this->assign("seller_domain", $seller_domain);
            }
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
        $port = 6379;
        $redis = new \Redis();
        if($host && $pwd){
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
    /**
     * 功能说明：action基类
     */
    public function init() {
        
        $domain = \think\Request::instance()->domain() . \think\Request::instance()->url();
        if (strpos($domain, 'menu_addonslist')) {
            $params = http_build_query(request()->param());
            $redirect = __URL(__URL__ . '/' . 'menu/addonmenu', $params);
            $this->redirect($redirect);
        }
        $this->assign('http', $this->http);
        $ssl = \think\Request::instance()->domain() . \think\Request::instance()->url();
        if (strpos($ssl, '/menu/addonmenu')) {
            $auth_method = \request()->param('addons');
            if (!checkAddons($auth_method, $this->website_id)) {
                $this->error('应用不存在');
            }
            if ($auth_method) {
                $auth = new AuthGroup();
                $auth_control = $auth->checkMethod($auth_method);
                if ($auth_control == 1) {
                    $uid = $this->user->getSessionUid();
                    if (empty($uid)) {
                        $redirect = __URL( __URL__ . "/login");
                        $this->redirect($redirect);
                    }
                }
            }
        }
        $this->user = new Member();
        $this->web_site = new WebSite();
        $web_info = $this->web_site->getWebSiteInfo();
        //查询后台url
        $this->default_url = $web_info['default_url'];
        $this->assign('default_url', $this->default_url);

        $this->uid = $this->user->getSessionUid();
        //$this->instance_id = $this->user->getSessionInstanceId();
        $this->instance_id = 0;
        $this->shop_name = $this->user->getInstanceName();
        $this->assign("uid", $this->uid);
        $this->assign("title", $web_info['mall_name']);
        $this->assign("mall_name", $web_info['mall_name']);
        $this->assign("web_info", $web_info);
        // 获取当前使用的PC端模板
        $this->style = "shop/new/";
        $this->assign("style", $this->style);
        //风格
        $styleModel = new SysPcCustomStyleConfigModel();
        $style = $styleModel->getInfo(['website_id' => $this->website_id]);
        $this->assign('colorStyle', $style['style']);
        if (!request()->isAjax()) {
            $Config = new Config();
            // 是否开启验证码
            $this->login_verify_code = $Config->getLoginVerifyCodeConfig(0);
            $this->assign("login_verify_code", $this->login_verify_code["value"]);
            $qq_info = $Config->getQQConfig(0);
            $Wchat_info = $Config->getWchatConfig(0);
            $this->assign("qq_info", $qq_info);
            $this->assign("Wchat_info", $Wchat_info);
            $keyword = request()->get('keyword', '');
            $this->assign("keyword", $keyword);
            /* 商品分类查询 */
            $goodsCategory = new GoodsCategory();
            $goods_category_tree = $goodsCategory->getCategoryTreeUseInShopIndex();
            $this->assign('goods_category_one', $goods_category_tree); // 商品分类一级
            $this->assign("platform_shopname", $this->shop_name);
            $com = new Common(0, $this->website_id);
            $pcCustomConfig = new SysPcCustomConfigModel();
            if (!request()->isMobile()) {
                //使用模板
                $usedTem = $pcCustomConfig->getInfo(['type' => 2, 'template_type' => 'home_templates', 'shop_id' => 0, 'website_id' => $this->website_id], 'code');
                $suffix = (isset($usedTem['code']) ? trim($usedTem['code']) : '');
                if (empty($suffix)) {
                    //默认模板
                    $defaultTem = $pcCustomConfig->getInfo(['type' => 1, 'template_type' => 'home_templates', 'shop_id' => 0, 'website_id' => $this->website_id], 'code');
                    $suffix = (isset($defaultTem['code']) ? trim($defaultTem['code']) : '');
                }
                if (!$suffix) {
                    $com->createTem();
                    $this->redirect($this->request->url());
                }
            }
            
            $dir_common = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/common';
            $dir = ROOT_PATH . 'public/static/custompc/data/web_' . $this->website_id . '/shop_0/home_templates/' . $suffix;
            if (!request()->isMobile() && $suffix) {
                $ntype = 'index';
                $bottom = $com->get_html_file($dir_common . '/bottom_html.php');
                $keywordList = $com->get_html_file($dir . '/header.php');
                /* 商品分类查询 */
                $navigator_list = $com->get_navigator($ntype);
                $navConfig = new SysPcCustomNavConfigModel();
                $navSet = $navConfig->getInfo(['website_id' => $this->website_id, 'code' => $suffix, 'template_type' => 'home_templates', 'shop_id' => 0]);
                $navSet['slide'] = 0;
                $this->assign('navigator_list', $navigator_list);
                $this->assign('bottom', $bottom);
                $this->assign('keywordList', $keywordList);
                $this->assign('navSet', $navSet);
                $this->assign('ntype', $ntype);
            }
            $this->assign('ssl', \think\Request::instance()->isSsl());
            $this->getPageUrl(); // 分页url拼接
            $this->assign('page_num', 5); // 分页显示的页码个数 注：误删不然所有分页都报错必须为奇数
        }
    }

    public function _empty($name) {
        
    }

    /**
     * 拼接共用的分页中的url
     */
    public function getPageUrl() {
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : ""; // 地址
        $path_info = substr($path_info, 1);
        $get_array = request()->get();
        $query_string = '';
        if (array_key_exists('page', $get_array)) {
            $tag = '&';
        } else {
            if (!empty($get_array)) {
                $tag = '&';
            } else
                $tag = '?';
        }
        foreach ($get_array as $k => $v) {
            if ($k != 'page') {
                $query_string .= $tag . $k . '=' . $v;
            }
        }
        $this->assign('path_info', $path_info);
        $this->assign('query_string', $query_string);
    }

    /**
     * 获取三级菜单
     * 目前只有固定模板和自定义模板用
     */
    public function getThreeLevelModule() {
        $child_menu_list_old = $this->user->getchildModuleQuery($this->second_menu_id);
        $child_menu_list = [];
        foreach ($child_menu_list_old as $k => $v) {
            $active = 0;
            $param = request()->param();
            if (strpos(strtolower(request()->pathinfo()), strtolower($v['url']))) {
                $active = 1;
            } else
            if (!empty($param['addons']) && strpos(strtolower($v['url']), strtolower($param['addons'])) !== false) {
                $active = 1;
            }
            $child_menu_list[] = array(
                'url' => $v['url'],
                'menu_name' => $v['module_name'],
                'active' => $active
            );
        }

        $this->assign('child_menu_list', $child_menu_list);
    }

}