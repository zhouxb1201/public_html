<?php

/**
 * **************************************************************版本定义*******************************************************************************
 */

require APP_PATH . 'error_message.php';

$root = \think\Request::instance()->root();
$root = str_replace('/index.php', '', $root);
define("__ROOT__", $root);
/**
 * *************************************************************伪静态*******************************************************************************
 */
define("REWRITE_MODEL", true); // 设置伪静态
// 入口文件,系统未开启伪静态
$rewrite = REWRITE_MODEL;
if (!$rewrite) {
    define('__URL__', \think\Request::instance()->domain() . \think\Request::instance()->baseFile());
} else {
    // 系统开启伪静态
    if (empty($root)) {
        define('__URL__', \think\Request::instance()->domain());
    } else {
        define('__URL__', \think\Request::instance()->domain() . \think\Request::instance()->root());
    }
}
/**
 * *************************************************************伪静态*******************************************************************************
 */

define('UPLOAD', "upload"); // 上传文件路径
define('ADMIN_MODULE', "admin"); // 重新定义后台模块
define('PLATFORM_MODULE', "platform");//重新定义店铺端后台模块
define('MASTER_MODULE', "master");//重新定义店铺端后台模块
define('ADDONS_MODULE', "platform/addonslist/menu_addonslist?addons=");//重新定义店铺端后台模块
define('ADDONS_ADMIN_MODULE', "admin/addonslist/menu_addonslist?addons=");//重新定义店铺端后台模块
define('ADDONS_SHOP_MODULE', "shop/addonslist/menu_addonslist?");//重新定义前台模块
define('ADDONS_WAP_MODULE', "wap/addonslist/menu_addonslist?");//重新定义前台模块
define('OAUTH_LOGIN_SESSION_TIME', 2*24*3600);//微信登录session存储时间
define('MINI_TEMPLATE_KEY', 'VSL_MINWECHATSETCODE');//微信登录session存储时间
define('MPLIVE_VERSION', '1.0.7');//直播插件版本
return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    // 应用命名空间
    'app_namespace' => 'app',
    // 应用调试模式，正式发布版本时改为false
    'app_debug' => true,
    // 应用Trace
    'app_trace' => false,
    // 应用模式状态
    'app_status' => '',
    // 是否支持多模块
    'app_multi_module' => true,
    // 入口自动绑定模块
    'auto_bind_module' => false,
    // 注册的根命名空间
    'root_namespace' => [],
    // 扩展配置文件
    'extra_config_list' => [
        'database',
        'validate'
    ],
    // 扩展函数文件
    'extra_file_list' => [
        THINK_PATH . 'helper' . EXT
    ],
    // 默认输出类型
    'default_return_type' => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return' => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler' => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler' => 'callback',
    // 默认时区
    'default_timezone' => 'PRC',
    // 是否开启多语言
    'lang_switch_on' => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter' => 'htmlspecialchars',
    // 默认语言
    'default_lang' => 'zh-cn',
    // 应用类库后缀
    'class_suffix' => false,
    // 控制器类后缀
    'controller_suffix' => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module' => 'shop',
    // 禁止访问模块
    'deny_module_list' => [
        'common'
    ],
    // 默认控制器名
    'default_controller' => 'Index',
    // 默认操作名
    'default_action' => 'index',
    // 默认验证器
    'default_validate' => '',
    // 默认的空控制器名
    'empty_controller' => 'Error',
    // 操作方法后缀
    'action_suffix' => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo' => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch' => [
        'ORIG_PATH_INFO',
        'REDIRECT_PATH_INFO',
        'REDIRECT_URL'
    ],
    // pathinfo分隔符
    'pathinfo_depr' => '/',
    // URL伪静态后缀
    'url_html_suffix' => '',
    // URL普通方式参数 用于自动生成
    'url_common_param' => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type' => 0,
    // 是否开启路由
    'url_route_on' => true,
    // 路由配置文件（支持配置多个）
    'route_config_file' => [
        'route'
    ],
    // 是否强制使用路由
    'url_route_must' => false,
    // 域名部署
    'url_domain_deploy' => false,
    // 域名根，如thinkphp.cn
    'url_domain_root' => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert' => true,
    // 默认的访问控制器层
    'url_controller_layer' => 'controller',
    // 表单请求类型伪装变量
    'var_method' => '_method',

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template' => [
        // 模板引擎类型 支持 php think 支持扩展
        'type' => 'Think',
        // 模板路径
        'view_path' => 'template/',

        // 模板后缀
        'view_suffix' => 'html',
        // 模板文件名分隔符
        'view_depr' => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin' => '{',
        // 模板引擎普通标签结束标记
        'tpl_end' => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end' => '}',
        'taglib_load' => true, // 是否使用内置标签库之外的其它标签库，默认自动检测
        'taglib_build_in' => 'cx'
    ], // 内置标签库名称(标签使用不必指定标签库名称),以逗号分隔 注意解析顺序

    // 视图输出字符串内容替换
    'view_replace_str' => [],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => ROOT_PATH . 'template' . DS . 'success_tmpl.html',
    'dispatch_error_tmpl' => ROOT_PATH . 'template' . DS . 'error_tmpl.html',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl' => ROOT_PATH . 'template' . DS . 'think_exception.html',

    // 错误显示信息,非调试模式有效
    'error_message' => '页面不存在或者系统正忙，请稍后再试！',
    // 显示错误信息
    'show_error_msg' => true,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle' => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log' => [
        // 日志记录方式，内置 file socket 支持扩展
        'type' => 'test',
        // 日志保存目录
        'path' => LOG_PATH,
        // 日志记录级别
        'level' => ['error']
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace' => [
        // 内置Html Console 支持扩展
        'type' => 'Html'
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache' => [
        // 驱动方式
        'type' => 'File',
        // 缓存保存目录
        'path' => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session' => [
        'id' => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix' => '',
        // 驱动方式 支持redis memcache memcached
        'type' => '',
        // 是否自动开启 SESSION
        'auto_start' => true,
        // SESSION 保存时间
        'expire' => 86400
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie' => [
        // cookie 名称前缀
        'prefix' => '',
        // cookie 保存时间
        'expire' => 86400,
        // cookie 保存路径
        'path' => '/',
        // cookie 有效域名
        'domain' => '',
        // cookie 启用安全传输
        'secure' => true,
        // httponly设置
        'httponly' => true,
        // 是否使用 setcookie
        'setcookie' => true
    ],
    'view_replace_str' => array(
        '__PUBLIC__' => __ROOT__ . '/public/',
        '__STATIC__' => __ROOT__ . '/public/static',

        'ADMIN_IMG' => __ROOT__ . '/public/admin/images',
        'ADMIN_CSS' => __ROOT__ . '/public/admin/css',
        'ADMIN_JS' => __ROOT__ . '/public/admin/js',
        'ADMIN_LIB' => __ROOT__ . '/public/admin/lib',

        'CUSTOM_PC_RESOURCE' => __ROOT__ . '/public/static/custompc',
        'PLATFORM_IMG' => __ROOT__ . '/public/platform/images',
        'PLATFORM_CSS' => __ROOT__ . '/public/platform/css',
        'PLATFORM_JS' => __ROOT__ . '/public/platform/js',
        'PLATFORM_NEW_CSS' => __ROOT__ . '/public/platform/css',
        'PLATFORM_NEW_JS' => __ROOT__ . '/public/platform/js',
        'PLATFORM_NEW_LIB' => __ROOT__ . '/public/platform/lib',
        'PLATFORM_NEW_IMG' => __ROOT__ . '/public/platform/images',
        'PLATFORM_STATIC' => __ROOT__ . '/public/platform/static',
        'MASTER_IMG' => __ROOT__ . '/public/master/images',
        'MASTER_CSS' => __ROOT__ . '/public/master/css',
        'MASTER_JS' => __ROOT__ . '/public/master/js',
        'MASTER_LIB' => __ROOT__ . '/public/master/lib',
        '__TEMP__' => __ROOT__ . '/template',
        '__ROOT__' => __ROOT__,
        'UPLOAD_URL' => __URL__ . '/' . ADMIN_MODULE,
        'PLATFORM_MAIN' => __URL__ . '/platform',
        'ADDONS_MAIN' => __URL__ . '/platform/addonslist/menu_addonslist?addons=',
        'ADDONS_ADMIN_MAIN' => __URL__ . '/admin/addonslist/menu_addonslist?addons=',
        'ADDONS_SHOP_MAIN' => __URL__ . '/shop/addonslist/menu_addonslist?',
        'ADDONS_WAP_MAIN' => __URL__ . '/wap/addonslist/menu_addonslist?',
        'MASTER_MAIN' => __URL__ . '/master',
        'ADMIN_MAIN' => __URL__ . '/' . ADMIN_MODULE,
        'APP_MAIN' => __URL__ . '/wap',
        'CLERK_MAIN' => __URL__ . '/clerk',
        'SHOP_MAIN' => __URL__ . '',
        '__UPLOAD__' => __ROOT__,
        '__MODULE__' => '/' . ADMIN_MODULE,
        '__ADDONS__' => __ROOT__ . '/addons', // 插件目录

        // 上传文件路径
        'UPLOAD_GOODS' => UPLOAD . '/goods/', // 存放商品图片主图
        'UPLOAD_GOODS_SKU' => UPLOAD . '/goods_sku/', // 存放商品sku图片
        'UPLOAD_GOODS_BRAND' => UPLOAD . '/goods_brand/', // 存放商品品牌图
        'UPLOAD_GOODS_GROUP' => UPLOAD . '/goods_group/', // 存放商品分组图片
        'UPLOAD_GOODS_CATEGORY' => UPLOAD . '/goods_category/', // 存放商品分组图片
        'UPLOAD_COMMON' => UPLOAD . '/common/', // 存放公共图片、网站logo、独立图片、没有任何关联的图片
        'UPLOAD_AVATOR' => UPLOAD . '/avator/', // 存放用户头像
        'UPLOAD_PAY' => UPLOAD . '/pay/', // 存放支付生成的二维码图片
        'UPLOAD_ADV' => UPLOAD . '/image_collection/', // //存放广告位图片，由于原“advertising”文件夹名称会被过滤掉。2017年9月14日 14:58:07 修改为“image_collection”
        'UPLOAD_EXPRESS' => UPLOAD . '/express/', // 存放物流
        'UPLOAD_CMS' => UPLOAD . '/cms/', // 存放文章图片
        'UPLOAD_VIDEO' => UPLOAD . "/video/",
        'UPLOAD_WX' => UPLOAD . "/wx/"
    ), // 存放视频文件

    // 验证码排至文件
    'captcha' => [
        // 2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY
        // vslai2017459ABCDEFGHJKLMNQRTVWXYZ
        // 验证码字符集合
        'codeSet' => '0123456789',
        // 验证码字体大小(px)
        'fontSize' => 15,

        // 是否画混淆曲线
        'useCurve' => false,

        // 是否添加杂点
        'useNoise' => false,

        // 验证码图片高度
        'imageH' => 40,
        // 验证码图片宽度
        'imageW' => 100,
        // 验证码位数
        'length' => 4,
        // 验证成功后是否重置
        'reset' => true
    ],
    // 分页配置
    'paginate' => [
        'type' => 'bootstrap',
        'var_page' => 'page',
        'list_rows' => 20,
        'list_showpages' => 5,
        'picture_page_size' => 15
    ],

    // ssl
    'ssl' => [
        'http' => 'https://'
    ],

    // 快递鸟
    'kdn' => [
        // 电子面单
        'form' => [
            'request_type' => 1007,
            'test_api' => 'http://sandboxapi.kdniao.com:8080/kdniaosandbox/gateway/exterfaceInvoke.json',
            'http_api' => 'http://api.kdniao.com/api/EOrderService',
            'https_api' => 'https://api.kdniao.com/api/EOrderService',
        ]
    ],

    // 京东万象物流接口信息
    'jd_cloud' => [
        'express_company_api' => 'https://way.jd.com/showapi/company',
        'shipping_api' => 'https://way.jd.com/showapi/order_path'
    ],

    // 小程序页面路由
    'mp_route' => [
        "pages/index/index",
        "pages/member/index",
        "pages/payment/pay/index",
        "pages/shopcart/index",
        "pages/payment/payfail/index",
        "pages/payment/paysuccess/index",
        "pages/search/index",
        "pages/category/index",
        "pages/goodlist/index",
        "pages/orderInfo/index",
        "pages/order/list/index",
        "pages/order/detail/index",
        "pages/order/evaluate/index",
        "pages/order/logistics/index",
        "pages/order/refund/index",
        "pages/order/customerService/index",
        "pages/goods/collection/index",
        "pages/goods/share/index",
        "pages/goods/detail/index",
        "pages/shop/home/index",
        "pages/shop/list/index",
        "pages/shop/collection/index",
        "pages/logon/index",
        "pages/commission/order/index",
        "pages/commission/customer/index",
        "pages/commission/team/index",
        "pages/commission/qrcode/index",
        "pages/commission/log/index",
        "pages/commission/withdraw/index",
        "pages/commission/detail/index",
        "pages/commission/centre/index",
        "pages/commission/apply/index",
        "pages/custom/index",
        "pages/shop/centre/index",
        "pages/shop/apply/index",
        "pages/shop/agreement/index",
        "pages/shop/result/index",
        "pages/commission/ranking/index"
    ],

    // 小程序菜单栏
    'mp_foot' => [
        'icon' => 'foots/icon/',
    ],

    'mall' => [
        'default_name' => '自营店',
    ],

    // 海报设置
    'poster' => [
        'default_bg' => 'public' . DS . 'static' . DS . 'images' . DS . 'poster' . DS . 'default_poster.jpg',// 默认背景图
        'default_width' => 640, // 默认背景图宽度 318
        'default_height' => 1008,// 默认背景图高度 502
        'default_ext' => 'png',// 默认海报后缀名
    ],

    // 证书设置
    'credential' => [
        'default_bg' => 'public' . DS . 'static' . DS . 'images' . DS . 'poster' . DS . 'default_poster.jpg',// 默认背景图
        'default_width' => 640, // 默认背景图宽度
        'default_height' => 1008,// 默认背景图高度
        'default_ext' => 'png',// 默认后缀名
    ],
    'rabbitmq' => [
        'host' => '127.0.0.1',
        'port' => '5672',
        'user' => 'admin',
        'pass' => 'admin',
        'exchange_name' => 'rabbitmq_act_order_calculate_sp2',//交换机名称
        'queue_name' => 'order_bonus_sp2',
    ],
];

