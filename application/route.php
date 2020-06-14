<?php
use think\Route;
use think\Cookie;
use think\Request;
/*****************************************************************************************************设置后台登录模块**********************************************************************************/
//检测后台系统模块
     if(ADMIN_MODULE != 'admin')
    {
        Route::group(ADMIN_MODULE,function(){
            Route::rule(':controller/:action','admin/:controller/:action');
            Route::rule(':controller','admin/:controller/index');
            Route::rule('','admin/index/index');
          
        });
        Route::group('admin',function(){
            Route::rule(':controller/:action','shop/:controller/:action');
            Route::rule(':controller','shop/:controller/index');
            Route::rule('','shop/index/index');

        });
        
    }
    
    
   /************************************************************************************************检测入口文件*****************************************************************************************/
    //检测入口文件
    $base_file = Request::instance()->baseFile();
    if(strpos($_SERVER['QUERY_STRING'], '/wapapi/addons/')){
        Route::rule('wapapi/addons/:addons/:controller/:action','wapapi/addons/execute','POST');
    }
    if(strpos($_SERVER['QUERY_STRING'], '/platform/addons/goodhelper/')){
        Route::rule('platform/addons/:addons/:controller/:action','platform/addons/execute','POST');
    }
    if(strpos($_SERVER['QUERY_STRING'], '/admin/addons/goodhelper/')){
        Route::rule('admin/addons/:addons/:controller/:action','admin/addons/execute','POST');
    }
    $alipay = strpos($base_file, 'alipay.php');
    $weixinpay = strpos($base_file, 'weixinpay.php');
    $is_api =  strpos($base_file, 'api.php');
    $alipays = strpos($base_file, 'alipays.php');
    if($alipay)
    {
        Route::bind('wapapi/pay/aliUrlBack');
    }
    if($alipays)
    {
        Route::bind('platform/pay/aliUrlBack');
    }
    if($weixinpay)
    {
        Route::bind('wapapi/pay/wchatUrlBack');
    }
   
    /********************************************************************************检测打开端口******************************************************************************************************/
    //检测浏览器类型以及显示方式(电脑端、手机端)
    function getShowModule(){
    
        $default_client = Cookie::get('default_client');
        if(!empty($default_client)){
            $default_client = Cookie::get('default_client');
        }else{
            if(Request::instance()->get('default_client') == 'shop'){
                $default_client = 'shop';
            }else{
                $default_client = 'wap';
            }
        }
        $is_mobile = Request::instance()->isMobile();
    
        if($is_mobile)
        {
           return 'wap';
        }else{
            return 'shop';
        }
    }
    $show_module = getShowModule();
    /*****************************************************************************************************针对商品详情设置路由***************************************************************************/
    //设置商品详情页面
    
  /*     if($show_module == 'shop')
    {
        $goods_info_url = 'shop/goods/goodsinfo';
        $shop_url       = 'shop/index/index';
    }else{
        $goods_info_url = 'wap/goods/goodsdetail';
        $shop_url       = 'wap/index/index';
    }  */
    //pc端开启路由去除shop
    /*****************************************************************************************************普通路由设置开始******************************************************************************/
    $common_route = [
        //pc端商品相关
        /*    ''           =>[
         '/'       =>   [$shop_url],
        ], */
        '[goods]'     => [
    
            //商品列表
            //  'goodsinfo'     => [$goods_info_url],
            ':action'         => ['shop/goods/:action'],
    
        ],
        '[pay]'     => [
    
            //支付控制器
            //  'goodsinfo'     => [$goods_info_url],
            ':action'         => ['shop/pay/:action'],
    
        ],
        '[list]'     => [
    
            //商品列表
            '/'         => ['shop/goods/goodslist'],
    
        ],
        '[index]'     => [
    
            //商品列表
            ':action'         => ['shop/index/:action'],
            '/'               => ['shop/index/index'],
        ],
        '[helpcenter]'     => [
    
            //商品列表
            ':action'         => ['shop/helpcenter/:action'],
            '/'               => ['shop/helpcenter/index'],
        ],
        '[login]'     => [
    
            //商品列表
            ':action'         => ['shop/login/:action'],
            '/'               => ['shop/login/index'],
        ],
        '[member]'     => [
    
            //商品列表
            ':action'         => ['shop/member/:action'],
            '/'               => ['shop/member/index'],
        ],
        '[menu]'     => [

            //插件列表
            ':action'         => ['shop/menu/:action'],
            '/'               => ['shop/menu/addonmenu'],
        ],
        '[addons]'     => [

            //插件执行
            ':action'         => ['shop/addons/:action'],
            '/'               => ['shop/addons/execute'],
        ],
        '[components]'     => [
    
            //商品列表
            ':action'         => ['shop/components/:action'],
            '/'               => ['shop/components/index'],
        ],
        '[helpcenter]'     => [
    
            //商品列表
            ':action'         => ['shop/helpcenter/:action'],
            '/'               => ['shop/helpcenter/index'],
        ],
        '[order]'     => [
    
            //商品列表
            ':action'         => ['shop/order/:action'],
            '/'               => ['shop/order/index'],
        ],
        '[topic]'     => [
    
            //商品列表
            ':action'         => ['shop/topic/:action'],
            '/'               => ['shop/topic/index'],
        ],
        '[cms]'     => [
    
            //文章
            ':action'         => ['shop/cms/:action'],
            '/'               => ['shop/cms/index'],
        ],
        '[shop]'     => [
            ':action'         => ['shop/shop/:action'],
            ':controller/:action'         => ['shop/:controller/:action'],
            ':controller'         => ['shop/:controller/index'],
            '/'               => ['shop/index/index'],
        ],
        '[notice]'     => [
    
            //商品列表
            ':action'         => ['shop/notice/:action'],
            '/'               => ['shop/notice/index'],
        ],
        '[task]'     => [
    
            //商品列表
            ':action'         => ['shop/task/:action'],
            '/'               => ['shop/task/load_task'],
        ],
        '[uploader]'     => [
    
            //商品列表
            ':action'         => ['shop/uploader/:action'],
            '/'               => ['shop/uploader/uploadFile'],
        ],
        '[wap]'  => [
            ':controller/:action'         => ['wap/:controller/:action'],
            ':controller'         => ['wap/:controller/index'],
            '/'               => ['wap/index/index'],
        ],
        '[clerk]'  => [
            ':controller/:action'         => ['clerk/:controller/:action'],
            ':controller'         => ['clerk/:controller/index'],
            '/'               => ['clerk/index/index'],
        ],
         '['.ADMIN_MODULE.']'  => [
            ':controller/:action'         => ['admin/:controller/:action'],
            ':controller'         => ['admin/:controller/index'],
            '/'               => ['admin/index/index'],
        ],
        
    ];
    $api_route = [];
    /*****************************************************************************************************普通路由设置结束******************************************************************************/
    if($is_api)
    {
        return $api_route;
    }else{
        return $common_route;
    }
  
    
   

