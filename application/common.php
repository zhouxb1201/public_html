<?php

use \data\extend\QRcode as QRcode;
use data\extend\alisms\top\request\AlibabaAliqinFcSmsNumSendRequest;
use data\extend\alisms\top\TopClient;
use data\extend\email\Email;
use data\service\Config as ConfigServer;
use think\Config;
use think\Hook;
use think\Image;
use think\Request;
use think\response\Redirect;
use data\service\WebSite;
use think\Route;
use data\service\BaseService;
use data\model\SysAddonsModel;
use data\model\WebSiteModel;
use data\model\MerchantVersionModel;
use \think\Session as Session;
use data\model\AddonsConfigModel;
use data\model\SysHooksModel;
use data\service\Config as WebConfig;
use data\service\Upload\AliOss;
use data\model\ModuleModel;
use data\service\AdminUser as User;
use data\model\InstanceModel;
use data\model\AuthGroupModel;
use data\model\VslIncreMentOrderModel;
use think\Log;
use \data\model\UserModel;
use data\model\AdminUserModel as AdminUserModel;
use data\service\User as Users;
use addons\blockchain\service\Block;
use think\Loader;
use BCode\BCGColor;
// 错误级别
// error_reporting(E_ERROR | E_WARNING | E_PARSE);
// 去除警告错误
error_reporting(E_ALL ^ E_NOTICE);
define("PAGESIZE", Config::get('paginate.list_rows'));
define("PAGESHOW", Config::get('paginate.list_showpages'));
define("PICTURESIZE", Config::get('paginate.picture_page_size'));
define("MPGOODSDETAIL", 'pages/goods/detail/index');
// 订单退款状态
define('ORDER_REFUND_STATUS', 11);
// 订单完成的状态
define('ORDER_COMPLETE_SUCCESS', 4);
define('ORDER_COMPLETE_SHUTDOWN', 5);
define('ORDER_COMPLETE_REFUND', -2);
// 前台网站风格
define("STYLE_DEFAULT_PC", "shop/default"); // 默认模板样式
define("STYLE_BLUE_PC", "shop/blue"); // 蓝色清爽版样式
define("STYLE_BLACK_PC", "shop/black"); // 黑色经典模板

// 后台网站风格
define("STYLE_DEFAULT_ADMIN", "admin");

// 插件目录
define('ADDON_PATH', ROOT_PATH . 'addons' . DS);

//wap api key
define('API_KEY', 'P6l0Gx9p7Qsijklz');

define('CALLBACK_URL', '/wapapi/Login/callback');
if(!IS_CLI){
    urlRoute();
}
/*************************需要使用到的方法*******************************/



/**
 * POST 请求
 */
function PostCurl($url,$data,$header=""){
    //初始化
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);


    if($header=="json"){
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ));
    }else{
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
    }
    if($header=="json"){
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }else{
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据
    return json_decode($data,true);
}
/**
 * POST 请求
 */
function GetCurl($url){
    // 1. 初始化
    $ch = curl_init();
    // 2. 设置选项，包括URL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_HEADER,0);
    // 3. 执行并获取HTML文档内容
    $output = curl_exec($ch);

    if($output === FALSE ){
        echo "CURL Error:".curl_error($ch);
    }
    // 4. 释放curl句柄
    curl_close($ch);
    return json_decode($output,true);
}
/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstring($para) {
    $arg = "";
    while (list ($key, $val) = each ($para)) {
        $arg.=$key."=".$val."&";
    }
    //去掉最后一个&字符
    $arg = substr($arg,0,count($arg)-2);
    //如果存在转义字符，那么去掉转义
    if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

    return $arg;
}
// RSA256 拼接签名
function sign($arr,$private_key)
{
    $ret = "";
    ksort($arr);
    while (list($k, $v) = each($arr))
    {
        $tmp = "$k=" . "$v&";
        $ret .= $tmp;

    }
    $ret  = substr($ret,0,-1);
    // 私钥签名
    return privateSign($ret,$private_key);
}
/**
 * 对数组排序
 * @param $para 排序前的数组
 * return 排序后的数组
 */
function argSort($para) {
    ksort($para);
    reset($para);
    return $para;
}
/**
 * RSA签名
 * @param $data 待签名数据
 * @param $private_key_path 商户私钥文件路径
 * return 签名结果
 */
function privateEncrypt($data,$private_key) {
    $priKey = formatPriKey($private_key);
    $res = openssl_pkey_get_private($priKey);
    $crypto = "";
    foreach (str_split($data, 245) as $chunk) {
        openssl_private_encrypt($chunk, $encryptData,$priKey);
        $crypto .= $encryptData;

    }

    openssl_free_key($res);
    //base64编码
    $sign = base64_encode($crypto);

    return $sign;
}
/**
拆分函数
 **/
function explodeString($signStr)
{
    if($signStr!=""){
        $list = explode("&", $signStr);
        $args = [];

        foreach ($list as $key => $value) {
            $arg =  explode("=", $value);
            if(isset($arg[0])&&isset($arg[1])){
                $size = count($arg);
                if($size>=3&&($arg[0]=="privateKey"||$arg[0]=="publicKey")){
                    if($size==3){
                        $args[$arg[0]] =  $arg[1]."=";
                    }else{
                        $args[$arg[0]] =  $arg[1]."==";
                    }
                }else{
                    $args[$arg[0]] =  $arg[1];
                }
            }
        }
        return $args;
    }
}

//私钥加密的内容通过公钥可用解密出来
function PublicDecrypt($encrypted,$public_key){
    //读取支付宝公钥文件
    $pubKey = formatPubKey($public_key);
    //转换为openssl格式密钥
    $res = openssl_get_publickey($pubKey);
    $crypto = '';
    foreach (str_split(base64_decode($encrypted), 256) as $chunk) {
        openssl_public_decrypt($chunk, $decryptData, $res);
        $crypto .= $decryptData;
    }
    //openssl_public_decrypt($encrypted,$decrypted,$this->pu_key);//私钥加密的内容通过公钥可用解密出来
    return $crypto;
}


/**
 * 格式化私钥
 * @param  [type] $priKey [description]
 * @return [type]         [description]
 */
function formatPriKey($priKey) {
    $priKey = trim($priKey);
    $fKey = "-----BEGIN PRIVATE KEY-----\n";
    $len = strlen($priKey);
    for($i = 0; $i < $len; ) {
        $fKey = $fKey . substr($priKey, $i, 64) . "\n";
        $i += 64;
    }
    $fKey .= "-----END PRIVATE KEY-----";
    return $fKey;
}

/**
 * 格式化私钥
 * @param  [type] $priKey [description]
 * @return [type]         [description]
 */
function formatPubKey($pubKey) {
    $pubKey = trim($pubKey);
    $fKey = "-----BEGIN PUBLIC KEY-----\n";
    $len = strlen($pubKey);
    for($i = 0; $i < $len; ) {
        $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
        $i += 64;
    }
    $fKey .= "-----END PUBLIC KEY-----";
    return $fKey;
}
function createSignLinkstring($arr)
{
    $ret = "";
    ksort($arr);
    while (list($k, $v) = each($arr))
    {
        $tmp = "$k=" . "$v&";
        $ret .= $tmp;
    }
    $ret  = substr($ret,0,-1);

    // 私钥签名
    return $ret;
}
/**
 * 私钥签名
 * @param    string     $signString 待签名字符串
 * @param    [type]     $priKey     私钥
 * @return   string     base64结果值
 */
function privateSign($signString,$private_key){
    $priKey = formatPriKey($private_key);
    $privKeyId = openssl_pkey_get_private($priKey);
    $signature = '';
    openssl_sign($signString, $signature, $privKeyId,OPENSSL_ALGO_SHA256);
    openssl_free_key($privKeyId);

    return base64_encode($signature);
}
/**
 * 配置pc端缓存
 */
function getShopCache()
{
    if (!Request::instance()->isAjax()) {
        $model = Request::instance()->module();
        $model = strtolower($model);
        $controller = Request::instance()->controller();
        $controller = strtolower($controller);
        $action = Request::instance()->action();
        $action = strtolower($action);
        if ($model == 'shop' && $controller == 'index' && $action = "index") {
            if (Request::instance()->isMobile()) {
                Redirect::create("wap/index/index");
            } else {
                Request::instance()->cache('__URL__', 1800);
            }
        }
        if ($model == 'shop' && $controller != 'goods' && $controller != 'member') {
            Request::instance()->cache('__URL__', 1800);
        }
        if ($model == 'shop' && $controller == 'goods' && $action == 'brandlist') {
            Request::instance()->cache('__URL__', 1800);
        }
    }
}

/**
 * 关闭站点
 */
function webClose($reason, $logo)
{
    if (Request::instance()->isMobile()) {
        echo "<meta charset='UTF-8'>
                    <div style='width:100%;margin:auto;margin-top:250px;    overflow: hidden;'>
                    	<img src='" . $logo . "' style='display: inline-block;float: left;width:90%;margin:0 5%;'/>
                    	<span style='font-size: 36px; display: inline-block;width: 70%;color: #666;text-align:center;margin:-130px 15% 0 15%;'>" . $reason . "</span>
                    	</div>
                ";
    } else {
        echo "<meta charset='UTF-8'>
                    <div style='width:100%;margin:auto;margin-top:200px;overflow: hidden;'>
                    	<img src='" . $logo . "' style='display: inline-block;float: left;width:40%;margin:0 30%;'/>
                    	<span style='font-size: 22px; display: inline-block; width:40%;color:#666;margin: -120px 15% 0 30%;text-align:center;font-weight:bold;'>" . $reason . "</span>
                    	</div>
                ";
    }

    exit();
}

/**
 * 获取手机端缓存
 */
function getWapCache()
{
    if (!Request::instance()->isAjax()) {
        $model = Request::instance()->module();
        $model = strtolower($model);
        $controller = Request::instance()->controller();
        $controller = strtolower($controller);
        $action = Request::instance()->action();
        $action = strtolower($action);
        // 店铺页面缓存8分钟
        if ($model == 'wap' && $controller == 'shop' && $action == 'index') {
            Request::instance()->cache('__URL__', 480);
        }
        if ($model == 'wap' && $controller != 'goods' && $controller != 'member') {
            Request::instance()->cache('__URL__', 1800);
        }
        if ($model == 'wap' && $controller == 'goods' && $action != 'brandlist') {
            Request::instance()->cache('__URL__', 1800);
        }
        if ($model == 'wap' && $controller == 'goods' && $action != 'goodsGroupList') {
            Request::instance()->cache('__URL__', 1800);
        }
    }
}

// 应用公共函数库
/**
 * 循环删除指定目录下的文件及文件夹
 *
 * @param string $dirpath
 *            文件夹路径
 */
function VslDelDir($dirpath)
{
    $dh = opendir($dirpath);
    while (($file = readdir($dh)) !== false) {
        if ($file != "." && $file != "..") {
            $fullpath = $dirpath . "/" . $file;
            if (!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                VslDelDir($fullpath);
                rmdir($fullpath);
            }
        }
    }
    closedir($dh);
    $isEmpty = true;
    $dh = opendir($dirpath);
    while (($file = readdir($dh)) !== false) {
        if ($file != "." && $file != "..") {
            $isEmpty = false;
            break;
        }
    }
    return $isEmpty;
}

/**
 * 生成数据的返回值
 *
 * @param unknown $msg
 * @param unknown $data
 * @return multitype:unknown
 */
function AjaxReturn($err_code, $data = [])
{
    // return $retval;
    $rs = [
        'code' => $err_code,
        'message' => getErrorInfo($err_code)
    ];
    if (!empty($data))
        $rs['data'] = $data;
    return $rs;
}

/**
 * 微信相关返回值
 * @param $err_code string/int [微信返回错误码]
 * @param array $data [返回接口数据]
 * @param string $error_info [微信错误信息]
 * @return array
 */
function AjaxWXReturn($err_code, $data = [], $error_info = '')
{
    require_once APP_PATH . 'error_message_wx.php';
    //微信的错误码有可能正/负，统一转成负，因为微商来错误返回都是负数
    $rs = [
        'code' => 0 - abs($err_code),
        'message' => getWXErrorInfo(0 - abs($err_code), $error_info)
    ];
    if (!empty($data))
        $rs['data'] = $data;
    return $rs;
}
/**
 * 图片上传函数返回上传的基本信息
 * 传入上传路径
 */
function uploadImage($path)
{
    $fileKey = key($_FILES);
    $file = request()->file($fileKey);
    if ($file === null) {
        return array(
            'error' => '上传文件不存在或超过服务器限制',
            'status' => '-1'
        );
    }
    $validate = new \think\Validate([
        [
            'fileMime',
            'fileMime:image/png,image/gif,image/jpeg,image/x-ms-bmp',
            '只允许上传jpg,gif,png,bmp类型的文件'
        ],
        [
            'fileExt',
            'fileExt:jpg,jpeg,gif,png,bmp',
            '只允许上传后缀为jpg,gif,png,bmp的文件'
        ],
        [
            'fileSize',
            'fileSize:2097152',
            '文件大小超出限制'
        ]
    ]); // 最大2M

    $data = [
        'fileMime' => $file,
        'fileSize' => $file,
        'fileExt' => $file
    ];
    if (!$validate->check($data)) {
        return array(
            'error' => $validate->getError(),
            'status' => -1
        );
    }
    $save_path = './' . getUploadPath() . '/' . $path;
    $info = $file->rule('uniqid')->move($save_path);
    if ($info) {
        // 获取基本信息
        $result['ext'] = $info->getExtension();
        $result['pic_cover'] = $path . '/' . $info->getSaveName();
        $result['pic_name'] = $info->getFilename();
        $result['pic_size'] = $info->getSize();
        $img = \think\Image::open('./' . getUploadPath() . '/' . $result['pic_cover']);
        // var_dump($img);
        return $result;
    }
}

/**
 * 判断当前是否是微信浏览器
 */
function isWeixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'],

            'MicroMessenger') !== false) {

        return 1;
    }

    return 0;
}

/**
 * 小程序自定义判断
 * @return int
 */
function isMiniProgram()
{
      if ($websiteId =  Request::instance()->header('website-id')) {
          return $websiteId;
      }
       return 0;
}
function isApp()
{
    if (request()->header('Program') == 'app') {
        return 1;
    }
       return 0;
}

/**
 * 防伪自定义判断
 * @return int
 */
function isAnti()
{
    if (request()->header('addons') == 'anticounterfeiting') {
        return 1;
    }
    return 0;
}

/**
 * 获取上传根目录
 *
 * @return Ambigous <\think\mixed, NULL, multitype:>
 */
function getUploadPath()
{
    $list = \think\config::get("view_replace_str.__UPLOAD__");
    return $list;
}

/**
 * 获取系统根目录
 */
function getRootPath()
{
    return dirname(dirname(dirname(dirname(__File__))));
}

/**
 * 通过第三方获取随机用户名
 *
 * @param unknown $type
 */
function setUserNameOauth($type)
{
    $time = time();
    $name = $time . rand(100, 999);
    return $type . '_' . name;
}

/**
 * 获取标准二维码格式
 *
 * @param unknown $url
 * @param unknown $path
 * @param unknown $ext
 */
function getQRcode($url, $path, $qrcode_name)
{
    if (!is_dir($path)) {
        $mode = intval('0777', 8);
        mkdir($path, $mode, true);
        chmod($path, $mode);
    }
    if ($path[strlen($path) - 1] !== DS) {
        $path .= DS;
    }
    $path .= $qrcode_name . '.png';
    if (file_exists($path)) {
        unlink($path);
    }
    QRcode::png($url, $path, 'L', '10', 1);
    $config = new WebConfig();
    $upload_type = $config->getUploadType();
    if ($upload_type == 2) {
        $alioss = new AliOss();
        $result = $alioss->setAliOssUplaod($path, $path);
        if($result['code']){
            @unlink($path);
            return $result['path'];
        }
    }
    return $path;
}

/**
 * 获取标准二维码格式加字
 *
 * @param unknown $url
 * @param unknown $path
 * @param unknown $ext
 */
function getQRcodeAnti($url, $path, $qrcode_name,$x,$y)
{
    if (!is_dir($path)) {
        $mode = intval('0777', 8);
        mkdir($path, $mode, true);
        chmod($path, $mode);
    }
    if ($path[strlen($path) - 1] !== DS) {
        $path .= DS;
    }
    $path .= $qrcode_name . '.png';
    if (file_exists($path)) {
        unlink($path);
    }
    QRcode::png($url, $path, 'H', '6', 6);
    mark_photo($path,$qrcode_name,$path,$x,$y);
    $config = new WebConfig();
    $upload_type = $config->getUploadType();
    if ($upload_type == 2) {
        $alioss = new AliOss();
        $result = $alioss->setAliOssUplaod($path, $path);
        if($result['code']){
            @unlink($path);
            return $result['path'];
        }
    }
    return $path;
}

function getBarcodeAnti($url, $path, $qrcode_name){
    // 引用barcode文件夹对应的类
    Loader::import('BCode.BCGFontFile',VENDOR_PATH);
    Loader::import('BCode.BCGDrawing',VENDOR_PATH);
    //Loader::import('BCode.BCGColor',VENDOR_PATH);
    // 条形码的编码格式
    Loader::import('BCode.BCGcode128',VENDOR_PATH,'.barcode.php');

    //path
    if (!is_dir($path)) {
        $mode = intval('0777', 8);
        mkdir($path, $mode, true);
        chmod($path, $mode);
    }
    if ($path[strlen($path) - 1] !== DS) {
        $path .= DS;
    }
    $path .= $qrcode_name . '.png';
    if (file_exists($path)) {
        unlink($path);
    }
    // $code = '';
    // 加载字体大小
    //$font = new BCGFontFile('./class/font/Arial.ttf', 18);
    //颜色条形码
    $color_black = new BCGColor(0, 0, 0);
    $color_white = new BCGColor(255, 255, 255);

    $drawException = null;
    try
    {
        $code = new BCGcode128();
        $code->setScale(2);
        $code->setThickness(30); // 条形码的厚度
        $code->setForegroundColor($color_black); // 条形码颜色
        $code->setBackgroundColor($color_white); // 空白间隙颜色
        // $code->setFont($font); //
        $code->parse($qrcode_name); // 条形码需要的数据内容
    }
    catch(\Exception $exception)
    {
        $drawException = $exception;
    }

    //根据以上条件绘制条形码
    $drawing = new BCGDrawing('', $color_white);
    if($drawException) {
        $drawing->drawException($drawException);
    }else{
        $drawing->setBarcode($code);
        $drawing->setFilename($path);
        $drawing->draw();
    }

    // 生成PNG格式的图片
    header('Content-Type: image/png');
    // header('Content-Disposition:attachment; filename="barcode.png"'); //自动下载
    $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);//若直接输出到浏览器,需要加 die;

    return $path;
}

function mark_photo($background,$text,$filename,$x,$y)
{
    $back=imagecreatefrompng($background);
    $color=imagecolorallocate($back,0,0,0);
    //$logo=imagecreatefrompng($logo);
    //$logo_w=imagesx($logo);
    //$logo_h=imagesy($logo);
    $font="public/static/font/MSYHBD.TTF"; // 字体文件
    //imagettftext只认utf8字体，所以用iconv转换
    imagettftext($back, 8, 0, $x, $y, $color, $font, $text);//调二维码中字体位置
    //执行合成调整位置
    //imagecopyresampled($back, $logo, 139,140, 0, 0, 65, 65, $logo_w, $logo_h);//调中间logo位置
    imagejpeg($back,$filename);
    imagedestroy($back);
    //imagedestroy($logo);
}


/**
 * 获取标准二维码格式,不保存
 *
 * @param unknown $text
 * @param unknown $logo
 */
function getQRcodeNotSave($text, $logo)
{
    ob_start();
    QRcode::png($text, false, 'L', '10', 0, false);
    if (file_exists($logo) || @fopen($logo, 'r')) {
        $obcode = ob_get_clean();
        $code = imagecreatefromstring($obcode);
        $logo = getImgCreateFrom($logo);
        $QR_width = imagesx($code);//二维码图片宽度
        $QR_height = imagesy($code);//二维码图片高度
        $logo_width = imagesx($logo);//logo图片宽度
        $logo_height = imagesy($logo);//logo图片高度
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        //重新组合图片并调整大小
        imagecopyresampled($code, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        header("Content-type: image/png");
        imagepng($code);
    } else {
        $obcode = ob_get_clean();
        $code = imagecreatefromstring($obcode);
        header("Content-type: image/png");
        imagepng($code);
    }
}

/**
 * 根据HTTP请求获取用户位置
 */
function getUserLocation()
{
    $key = "16199cf2aca1fb54d0db495a3140b8cb"; // 高德地图key
    $url = "http://restapi.amap.com/v3/ip?key=$key";
    $json = file_get_contents($url);
    $obj = json_decode($json, true); // 转换数组
    $obj["message"] = $obj["status"] == 0 ? "失败" : "成功";
    return $obj;
}

function getLocationByLatLng($location)
{
    //$key = "16199cf2aca1fb54d0db495a3140b8cb"; // 高德地图key
    //$url = "http://restapi.amap.com/v3/ip?key=$key";
    $url = "http://api.map.baidu.com/geocoder/v2/?ak=t16W0CsDyfV8QjlSgS17lgsI&location=$location&output=json";
    $json = file_get_contents($url);
    $obj = json_decode($json, true); // 转换数组
    //$obj["message"] = $obj["status"] == 0 ? "失败" : "成功";
    return $obj;
}

/**
 * 获取ip
 */
function get_ip()
{
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        return $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        return $_SERVER["HTTP_X_FORWARDED_FOR"];
    }
    if (!empty($_SERVER["REMOTE_ADDR"])) {
        return $_SERVER["REMOTE_ADDR"];
    }
    return 0;
}

/**
 * 根据 ip 获取 当前城市
 */
function get_city_by_ip()
{
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $cip = $_SERVER["HTTP_CLIENT_IP"];
    } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } elseif (!empty($_SERVER["REMOTE_ADDR"])) {
        $cip = $_SERVER["REMOTE_ADDR"];
    } else {
        $cip = "";
    }
    $url = 'http://restapi.amap.com/v3/ip';
    $data = array(
        'output' => 'json',
        'key' => '16199cf2aca1fb54d0db495a3140b8cb',
        'ip' => $cip
    );

    $postdata = http_build_query($data);
    $opts = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context = stream_context_create($opts);

    $result = file_get_contents($url, false, $context);
    $res = json_decode($result, true);
    if (count($res['province']) == 0) {
        $res['province'] = '北京市';
    }
    if (!empty($res['province']) && $res['province'] == "局域网") {
        $res['province'] = '北京市';
    }
    if (count($res['city']) == 0) {
        $res['city'] = '北京市';
    }
    return $res;
}

/**
 * 颜色十六进制转化为rgb
 */
function hColor2RGB($hexColor)
{
    $color = str_replace('#', '', $hexColor);
    if (strlen($color) > 3) {
        $rgb = array(
            'r' => hexdec(substr($color, 0, 2)),
            'g' => hexdec(substr($color, 2, 2)),
            'b' => hexdec(substr($color, 4, 2))
        );
    } else {
        $color = str_replace('#', '', $hexColor);
        $r = substr($color, 0, 1) . substr($color, 0, 1);
        $g = substr($color, 1, 1) . substr($color, 1, 1);
        $b = substr($color, 2, 1) . substr($color, 2, 1);
        $rgb = array(
            'r' => hexdec($r),
            'g' => hexdec($g),
            'b' => hexdec($b)
        );
    }
    return $rgb;
}

/**
 * 制作推广二维码
 *
 * @param unknown $path
 *            二维码地址
 * @param unknown $thumb_qrcode中继二维码地址
 * @param unknown $user_headimg
 *            头像
 * @param unknown $shop_logo
 *            店铺logo
 * @param unknown $user_name
 *            用户名
 * @param unknown $data
 *            画布信息 数组
 * @param unknown $create_path
 *            图片创建地址 没有的话不创建图片
 */
function showUserQecode($upload_path, $path, $thumb_qrcode, $user_headimg, $shop_logo, $user_name, $data, $create_path)
{

    // 暂无法生成
    if (!strstr($path, "http://") && !strstr($path, "https://")) {
        if (!file_exists($path)) {
            $path = "public/static/images/template_qrcode.png";
        }
    }

    if (!file_exists($upload_path)) {
        $mode = intval('0777', 8);
        mkdir($upload_path, $mode, true);
    }

    // 定义中继二维码地址

    $image = \think\Image::open($path);
    // 生成一个固定大小为360*360的缩略图并保存为thumb_....jpg
    $image->thumb(288, 288, \think\Image::THUMB_CENTER)->save($thumb_qrcode);
    // 背景图片
    $dst = $data["background"];

    if (!strstr($dst, "http://") && !strstr($dst, "https://")) {
        if (!file_exists($dst)) {
            $dst = "public/static/images/qrcode_bg/shop_qrcode_bg.png";
        }
    }

    // $dst = "http://pic107.nipic.com/file/20160819/22733065_150621981000_2.jpg";
    // 生成画布
    list ($max_width, $max_height) = getimagesize($dst);
    // $dests = imagecreatetruecolor($max_width, $max_height);
    $dests = imagecreatetruecolor(640, 1134);
    $dst_im = getImgCreateFrom($dst);
    imagecopy($dests, $dst_im, 0, 0, 0, 0, $max_width, $max_height);
    // ($dests, $dst_im, 0, 0, 0, 0, 640, 1134, $max_width, $max_height);
    imagedestroy($dst_im);
    // 并入二维码
    // $src_im = imagecreatefrompng($thumb_qrcode);
    $src_im = getImgCreateFrom($thumb_qrcode);
    $src_info = getimagesize($thumb_qrcode);
    // imagecopy($dests, $src_im, $data["code_left"] * 2, $data["code_top"] * 2, 0, 0, $src_info[0], $src_info[1]);
    imagecopy($dests, $src_im, $data["code_left"] * 2, $data["code_top"] * 2, 0, 0, $src_info[0], $src_info[1]);
    imagedestroy($src_im);
    // 并入用户头像

    if (!strstr($user_headimg, "http://") && !strstr($user_headimg, "https://")) {
        if (!file_exists($user_headimg)) {
            $user_headimg = "public/static/images/qrcode_bg/head_img.png";
        }
    }
    $src_im_1 = getImgCreateFrom($user_headimg);
    $src_info_1 = getimagesize($user_headimg);
    // imagecopy($dests, $src_im_1, $data['header_left'] * 2, $data['header_top'] * 2, 0, 0, $src_info_1[0], $src_info_1[1]);
    // imagecopy($dests, $src_im_1, $data['header_left'] * 2, $data['header_top'] * 2, 0, 0, $src_info_1[0], $src_info_1[1]);
    imagecopyresampled($dests, $src_im_1, $data['header_left'] * 2, $data['header_top'] * 2, 0, 0, 80, 80, $src_info_1[0], $src_info_1[1]);
    imagedestroy($src_im_1);

    // 并入网站logo
    if ($data['is_logo_show'] == '1') {
        if (!strstr($shop_logo, "http://") && !strstr($shop_logo, "https://")) {
            if (!file_exists($shop_logo)) {
                $shop_logo = "public/static/images/logo.png";
            }
        }
        $src_im_2 = getImgCreateFrom($shop_logo);
        $src_info_2 = getimagesize($shop_logo);
        // imagecopy($dests, $src_im_2, $data['logo_left'] * 2, $data['logo_top'] * 2, 0, 0, $src_info_2[0], $src_info_2[1]);
        imagecopyresampled($dests, $src_im_2, $data['logo_left'] * 2, $data['logo_top'] * 2, 0, 0, 200, 80, $src_info_2[0], $src_info_2[1]);
        imagedestroy($src_im_2);
    }
    // 并入用户姓名
    if ($user_name == "") {
        $user_name = "用户";
    }
    $rgb = hColor2RGB($data['nick_font_color']);
    $bg = imagecolorallocate($dests, $rgb['r'], $rgb['g'], $rgb['b']);
    $name_top_size = $data['name_top'] * 2 + $data['nick_font_size'];
    @imagefttext($dests, $data['nick_font_size'], 0, $data['name_left'] * 2, $name_top_size, $bg, "public/static/font/Microsoft.ttf", $user_name);
    header("Content-type: image/jpeg");
    if ($create_path == "") {
        imagejpeg($dests);
    } else {
        imagejpeg($dests, $create_path);
    }
}

/**
 * 把微信生成的图片存入本地
 *
 * @param [type] $username
 *            [用户名]
 * @param [string] $LocalPath
 *            [要存入的本地图片地址]
 * @param [type] $weixinPath
 *            [微信图片地址]
 *
 * @return [string] [$LocalPath]失败时返回 FALSE
 */
function save_weixin_img($local_path, $weixin_path)
{
    $weixin_path_a = str_replace("https://", "http://", $weixin_path);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $weixin_path_a);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $r = curl_exec($ch);
    curl_close($ch);
    if (!empty($local_path) && !empty($weixin_path_a)) {
        $msg = file_put_contents($local_path, $r);
    }
    return $local_path;
}

// 分类获取图片对象
function getImgCreateFrom($img_path)
{
    $ename = getimagesize($img_path);
    $ename = explode('/', $ename['mime']);
    $ext = $ename[1];
    switch ($ext) {
        case "png":

            $image = imagecreatefrompng($img_path);
            break;
        case "jpeg":

            $image = imagecreatefromjpeg($img_path);
            break;
        case "jpg":

            $image = imagecreatefromjpeg($img_path);
            break;
        case "gif":

            $image = imagecreatefromgif($img_path);
            break;
    }
    return $image;
}

/**
 * 生成流水号
 *
 * @return string
 */
function getSerialNo()
{
    $no_base = date("ymdhis", time());
    $serial_no = $no_base . rand(111, 999);
    return $serial_no;
}

/**
 * 删除图片文件
 *
 * @param unknown $img_path
 */
function removeImageFile($img_path,$domain)
{
    // 检查图片文件是否存在
    
    if (file_exists($img_path)) {
        return unlink($img_path);
    } else {
        $config = new WebConfig();
        $upload_type = $config->getUploadType();
        if($upload_type==2){
            $alioss = new AliOss();
            $img_path = str_replace($domain.'/','',$img_path);
            $data = $alioss->deleteAliOss($img_path);
            return $data;
        }else{
            return false;
        }
    }
    
    
}

/**
 * 阿里大于短信发送
 *
 * @param unknown $appkey
 * @param unknown $secret
 * @param unknown $signName
 * @param unknown $smsParam
 * @param unknown $send_mobile
 * @param unknown $template_code
 */
function aliSmsSend($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code, $sms_type = 0)
{
    if ($sms_type == 3) {
        // 旧用户发送短信
        return aliSmsSendOld($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code);
    } else {
        // 新用户发送短信
        return aliSmsSendNew($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code);
    }
}

/**
 * 阿里大于旧用户发送短信
 *
 * @param unknown $appkey
 * @param unknown $secret
 * @param unknown $signName
 * @param unknown $smsParam
 * @param unknown $send_mobile
 * @param unknown $template_code
 * @return Ambigous <unknown, \ResultSet, mixed>
 */
function aliSmsSendOld($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code)
{
    require_once 'data/extend/alisms/TopSdk.php';
    $c = new TopClient();
    $c->appkey = $appkey;
    $c->secretKey = $secret;
    $req = new AlibabaAliqinFcSmsNumSendRequest();
    $req->setExtend("");
    $req->setSmsType("normal");
    $req->setSmsFreeSignName($signName);
    $req->setSmsParam($smsParam);
    $req->setRecNum($send_mobile);
    $req->setSmsTemplateCode($template_code);
    $result = $resp = $c->execute($req);
    return $result;
}

/**
 * 阿里大于新用户发送短信
 *
 * @param unknown $appkey
 * @param unknown $secret
 * @param unknown $signName
 * @param unknown $smsParam
 * @param unknown $send_mobile
 * @param unknown $template_code
 */
function aliSmsSendNew($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code)
{
    require_once 'data/extend/alisms_new/aliyun-php-sdk-core/Config.php';
    require_once 'data/extend/alisms_new/SendSmsRequest.php';
    // 短信API产品名
    $product = "Dysmsapi";
    // 短信API产品域名
    $domain = "dysmsapi.aliyuncs.com";
    // 暂时不支持多Region
    $region = "cn-hangzhou";
    $profile = DefaultProfile::getProfile($region, $appkey, $secret);
    DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
    $acsClient = new DefaultAcsClient($profile);

    $request = new SendSmsRequest();
    // 必填-短信接收号码
    $request->setPhoneNumbers($send_mobile);
    // 必填-短信签名
    $request->setSignName($signName);
    // 必填-短信模板Code
    $request->setTemplateCode($template_code);
    // 选填-假如模板中存在变量需要替换则为必填(JSON格式)
    $request->setTemplateParam($smsParam);
    // 选填-发送短信流水号
    $request->setOutId("0");
    // 发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
    return $acsResponse;
}

/**
 * 发送邮件
 *
 * @param unknown $toemail
 * @param unknown $title
 * @param unknown $content
 * @return boolean
 */
function emailSend($email_host, $email_id, $email_pass, $email_port, $email_is_security, $email_addr, $toemail, $title, $content, $shopName = "")
{
    $result = false;
    try {
        $mail = new Email();
        if (!empty($shopName)) {
            $mail->_shopName = $shopName;
        } else {
            $mail->_shopName = "VslShop开源电商";
        }
        $mail->setServer($email_host, $email_id, $email_pass, $email_port, $email_is_security);
        $mail->setFrom($email_addr);
        $mail->setReceiver($toemail);
        $mail->setMail($title, $content);
        $result = $mail->sendMail();

    } catch (\Exception $e) {
        $result = false;
    }
    return $result;
}

/**
 * 执行钩子
 *
 * @param unknown $hookid
 * @param string $params
 */
function runhook($class, $tag, $params = null)
{
    $result = array();
    try {
        $result = Hook::exec("\\data\\extend\\hook\\" . $class, $tag, $params);
    } catch (\Exception $e) {
        $result["code"] = -1;
        $result["message"] = "请求失败!";
        $result['message'] = $e->getMessage();
    }
    return $result;
}

/**
 * 格式化字节大小
 *
 * @param number $size
 *            字节数
 * @param string $delimiter
 *            数字和单位分隔符
 * @return string 格式化后的带单位的大小
 * @author
 *
 */
function format_bytes($size, $delimiter = '')
{
    $units = array(
        'B',
        'KB',
        'MB',
        'GB',
        'TB',
        'PB'
    );
    for ($i = 0; $size >= 1024 && $i < 5; $i++)
        $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 获取插件类的类名
 *
 * @param $name 插件名
 * @param string $type
 *            返回命名空间类型
 * @param string $class
 *            当前类名
 * @return string
 */
function get_addon_class($name, $type = '', $class = null)
{
    $name = \think\Loader::parseName($name);
    if ($type == '' && $class == null) {
        $dir = ADDON_PATH . $name . '/core';
        if (is_dir($dir)) {
            // 目录存在
            $type = 'addons_index';
        } else {
            $type = 'addon_index';
        }
    }
    $class = \think\Loader::parseName(is_null($class) ? $name : $class, 1);
    switch ($type) {
        // 单独的插件addon 入口文件
        case 'addon_index':
            $namespace = "\\addons\\" . $name . "\\" . $class;
            break;
        // 单独的插件addon 控制器
        case 'addon_controller':
            $namespace = "\\addons\\" . $name . "\\controller\\" . $class;
            break;
        // 有下级插件的插件addons 入口文件
        case 'addons_index':
            $namespace = "\\addons\\" . $name . "\\core\\" . $class;
            break;
        // 有下级插件的插件addons 控制器
        case 'addons_controller':
            $namespace = "\\addons\\" . $name . "\\core\\controller\\" . $class;
            break;
        // 插件类型下的下级插件plugin
        default:
            $namespace = "\\addons\\" . $name . "\\" . $type . "\\controller\\" . $class;
    }

    return $namespace;
}

/**
 * 处理插件钩子
 *
 * @param string $hook
 *            钩子名称F
 * @param mixed $params
 *            传入参数
 * @return void
 */
function hook($hook, $params = [])
{
    // 钩子调用
    \think\Hook::listen($hook, $params);
}


/**
 * 判断钩子是否存在
 * 2017年8月25日19:43:08
 *
 * @param unknown $hook
 * @return boolean
 */
function hook_is_exist($hook)
{
    $res = \think\Hook::get($hook);
    if (empty($res)) {
        return false;
    }
    return true;
}

/**
 * 插件显示内容里生成访问插件的url
 *
 * @param string $url
 *            url
 * @param array $param
 *            参数
 */
function addons_url($url, $param = [])
{
    $url = parse_url($url);
    $case = config('url_convert');
    $addons = $case ? \think\Loader::parseName($url['scheme']) : $url['scheme'];
    $controller = $case ? \think\Loader::parseName($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');
    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }
    if (strpos($action, '/') !== false) {
        // 有插件类型 插件类型://插件名/控制器名/方法名
        $controller_action = explode('/', $action);
        $params = array(
            'addons_type' => $addons,
            'addons' => $controller,
            'controller' => $controller_action[0],
            'action' => $controller_action[1]
        );
    } else {
        // 没有插件类型 插件名://控制器名/方法名
        $params = array(
            'addons' => $addons,
            'controller' => $controller,
            'action' => $action
        );
    }
    /* 基础参数 */
    $params = array_merge($params, $param); // 添加额外参数
    $return_url = url("addons/execute", $params, '', true);
    return $return_url;
}

/**
 * 插件显示内容里生成访问插件的url
 *
 * @param string $url
 *            url
 * @param array $param
 *            参数
 * @return string $return_url
 */
function addons_url_platform($url, $param = [])
{
    $url = parse_url($url);
    $case = config('url_convert');
    $addons = $case ? \think\Loader::parseName($url['scheme']) : $url['scheme'];
    $controller = $case ? \think\Loader::parseName($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');
    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }
    if (strpos($action, '/') !== false) {
        // 有插件类型 插件类型://插件名/控制器名/方法名
        $controller_action = explode('/', $action);
        $params = array(
            'addons_type' => $addons,
            'addons' => $controller,
            'controller' => $controller_action[0],
            'action' => $controller_action[1]
        );
    } else {
        // 没有插件类型 插件名://控制器名/方法名
        $params = array(
            'addons' => $addons,
            'controller' => $controller,
            'action' => $action
        );
    }
    /* 基础参数 */
    $params = array_merge($params, $param); // 添加额外参数
    $return_url = url("platform/addons/execute", $params, '', true);
    return $return_url;
}

function addons_url_admin($url, $param = [])
{
    $url = parse_url($url);
    $case = config('url_convert');
    $addons = $case ? \think\Loader::parseName($url['scheme']) : $url['scheme'];
    $controller = $case ? \think\Loader::parseName($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');
    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }
    if (strpos($action, '/') !== false) {
        // 有插件类型 插件类型://插件名/控制器名/方法名
        $controller_action = explode('/', $action);
        $params = array(
            'addons_type' => $addons,
            'addons' => $controller,
            'controller' => $controller_action[0],
            'action' => $controller_action[1]
        );
    } else {
        // 没有插件类型 插件名://控制器名/方法名
        $params = array(
            'addons' => $addons,
            'controller' => $controller,
            'action' => $action
        );
    }
    /* 基础参数 */
    $params = array_merge($params, $param); // 添加额外参数
    $return_url = url("admin/addons/execute", $params, '', true);
    return $return_url;
}

/**
 * 时间戳转时间
 *
 * @param unknown $time_stamp
 */
function getTimeStampTurnTime($time_stamp)
{
    if ($time_stamp > 0) {
        $time = date('Y-m-d H:i:s', $time_stamp);
    } else {
        $time = "";
    }
    return $time;
}

/**
 * 时间戳转年月日
 *
 * @param unknown $time_stamp
 */
function timeStampTurnDate($time_stamp)
{
    if ($time_stamp > 0) {
        $time = date('Y-m-d', $time_stamp);
    } else {
        $time = "";
    }
    return $time;
}

/**
 * 时间转时间戳
 *
 * @param unknown $time
 */
function getTimeTurnTimeStamp($time)
{
    $time_stamp = strtotime($time);
    return $time_stamp;
}

/**
 * 导出数据为excal文件
 * @param $expTitle string [excel文件名]
 * @param $expCellName array [excel第一行标题]
 * @param $expTableData array [excel填充的数据]
 * @param bool $path string [存储文件路径]
 * @param string $suffix string [生成的excel文件后缀]
 * @throws PHPExcel_Exception
 * @throws PHPExcel_Reader_Exception
 * @throws PHPExcel_Writer_Exception
 * @return 如果有$path，就返回创建的文件路径名； 否则浏览器直接下载
 */
function dataExcel($expTitle, $expCellName, $expTableData, $path = false, $suffix = '.xls')
{
    $file_type =  $suffix == '.xlsx' ? 'Excel2007' : 'Excel5';//生成的Excel版本
    if (!class_exists('PHPExcel')){
        include 'data/extend/phpexcel_classes/PHPExcel.php';
    }
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); // 文件名称
    $fileName = $expTitle . date('_YmdHis'); // or $xlsTitle 文件名称可根据自己情况设定

    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
    $objPHPExcel = new \PHPExcel();
    //得到当前活动的表
    $objActSheet = $objPHPExcel->getActiveSheet();
    $cellName = array(
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
        'Z',
        'AA',
        'AB',
        'AC',
        'AD',
        'AE',
        'AF',
        'AG',
        'AH',
        'AI',
        'AJ',
        'AK',
        'AL',
        'AM',
        'AN',
        'AO',
        'AP',
        'AQ',
        'AR',
        'AS',
        'AT',
        'AU',
        'AV',
        'AW',
        'AX',
        'AY',
        'AZ',
        'BA',
        'BB',
        'BC',
        'BD',
        'BE',
        'BF',
        'BG',
        'BH',
        'BI',
        'BJ',
        'BK',
        'BL',
        'BM',
        'BN'
    );
    $objPHPExcel->setActiveSheetIndex(0);

    // 设置默认字体和大小
    $objPHPExcel->getDefaultStyle()->getFont()->setName(iconv('gbk', 'utf-8', ''));
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(11);

    $n = 0;
    for ($i = 0; $i < $cellNum; $i++) {
        if($expCellName[$i][0] === 'receiver_addressB'){
            $n = 3;
            $k = $i+$n;
            $objActSheet->setCellValue($cellName[$i] . '1', $expCellName[$i][1]);
            $objActSheet->mergeCells("{$cellName[$i]}1:{$cellName[$k]}1");
        } else {
            $k = $i+$n;
            $objActSheet->setCellValue($cellName[$k] . '1', is_numeric($expCellName[$i][1]) ? $expCellName[$i][1].' ' : $expCellName[$i][1]);
            $objActSheet->getColumnDimension($cellName[$k])->setWidth(25);
        }
    }
    $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
    for ($i = 0; $i < $dataNum; $i++) {
        $n = 0;
        for ($j = 0; $j < $cellNum; $j++) {
            if($expCellName[$j][0] === 'receiver_addressB'){
                $n = 3;
                $receiver_addressB = $expTableData[$i][$expCellName[$j][0]];
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 2), $receiver_addressB['receiver_province_name']);
                $k = $j + 1;
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$k] . ($i + 2), $receiver_addressB['receiver_city_name']);
                $k = $j + 2;
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$k] . ($i + 2), $receiver_addressB['receiver_district_name']);
                $k = $j + 3;
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$k] . ($i + 2), $receiver_addressB['receiver_address']);
            }else{
                $k = $j+$n;
                $objActSheet->setCellValue($cellName[$k] . ($i + 2), is_numeric($expTableData[$i][$expCellName[$j][0]]) ? $expTableData[$i][$expCellName[$j][0]].' ':$expTableData[$i][$expCellName[$j][0]]);
            }
        }
    }

    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . $suffix);
    header("Content-Disposition:attachment;filename=".$fileName.$suffix); // attachment新窗口打印inline本窗口打印
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, $file_type);
    if ($path) {
        try{
            $file_path = $path.$fileName.$suffix;
            $objWriter->save($file_path);
            return ['code' => 1, 'data' => $file_path];
        }catch (Exception $e){
            return ['code' => -1, 'data' => $e->getMessage()];
        }
    } else {
        $objWriter->save('php://output');
    }
}

/**
 * 获取url参数
 *
 * @param unknown $action
 * @param string $param
 */
function __URL($url, $param = '')
{
    $is_ssl = \think\Request::instance()->isSsl();
    if (strstr($url, 'SELF')) {
        // return 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        return $is_ssl ? "http://" : "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    $url = \str_replace('ADDONS_SHOP_MAIN', ADDONS_SHOP_MODULE, $url);
    $url = \str_replace('SHOP_MAIN', '', $url);
    $url = \str_replace('shop/addons/execute', 'addons/execute', $url);
    $url = \str_replace('APP_MAIN', 'wap', $url);
    //确保ADDONS_ADMIN_MAIN 替换在 ADMIN_MAIN之前发生，不然会二次替换
    $url = \str_replace('ADDONS_ADMIN_MAIN', ADDONS_ADMIN_MODULE, $url);
    $url = \str_replace('ADMIN_MAIN', ADMIN_MODULE, $url);
    $url = \str_replace('PLATFORM_MAIN', PLATFORM_MODULE, $url);
    $url = \str_replace('MASTER_MAIN', MASTER_MODULE, $url);
    $url = \str_replace('ADDONS_MAIN', ADDONS_MODULE, $url);

    $url = \str_replace('ADDONS_WAP_MAIN', ADDONS_WAP_MODULE, $url);
    // 处理后台页面
    $url = \str_replace(__URL__ . '/wap', 'wap', $url);
    $url = \str_replace(__URL__ . ADMIN_MODULE, ADMIN_MODULE, $url);
    $url = \str_replace(__URL__ . PLATFORM_MODULE, PLATFORM_MODULE, $url);
    $url = \str_replace(__URL__ . MASTER_MODULE, MASTER_MODULE, $url);
    $url = \str_replace(__URL__, '', $url);

    $base = new BaseService();
    $model = $base->getRequestModel();
    if ($model == 'wap' || $model == 'shop' && !strpos($param, '&website_id=' . Session::get($model . 'website_id'))) {
//        if ($param) {
//            $param = $param . '&website_id=' . Session::get($model . 'website_id');
//        } else {
//            $param = '?website_id=' . Session::get($model . 'website_id');
//        }
    }
    if (empty($url)) {

        if ($is_ssl) {
            $re_url = __URL__ . $param;

            $re_url = str_replace("http://", "https://", $re_url);
        } else {
            $re_url = __URL__ . $param;
        }

        // return __URL__.'?'.$website;
        return $re_url;
    } else {
        $str = substr($url, 0, 1);
        if ($str === '/' || $str === "\\") {
            $url = substr($url, 1, strlen($url));
        }
        if (REWRITE_MODEL) {

            $url = urlRouteConfig($url, $param);
            if ($is_ssl) {
                $re_url = $url;
                $re_url = str_replace("http://", "https://", $re_url);
            } else {
                $re_url = $url;
            }

            // return $url;
            return $re_url;
        }
        $action_array = explode('?', $url);

        // 检测是否是pathinfo模式
        $url_model = url_model();
        if ($url_model) {
            $base_url = __URL__ . '/' . $action_array[0];
            $tag = '?';
        } else {
            $base_url = __URL__ . '?s=/' . $action_array[0];
            $tag = '&';
        }

        if (!empty($action_array[1])) {
            // 有参数
            if ($is_ssl) {
                $re_url = $base_url . $tag . $action_array[1] . '&' . $param;
                $re_url = str_replace("http://", "https://", $re_url);
            } else {
                $re_url = $base_url . $tag . $action_array[1] . '&' . $param;
            }
            //echo 'after is_ssl:'.$re_url."<br>";
            $re_url = str_replace("?&", "?", $re_url);
            $re_url = str_replace("&?", "&", $re_url);
            $re_url = str_replace("&&", "&", $re_url);
            // return $base_url . $tag . $website. $tag . $action_array[1];
            return $re_url;
        } else {
            if (!empty($param)) {

                if ($is_ssl) {
                    $re_url = $base_url . $tag . $param;
                    $re_url = str_replace("http://", "https://", $re_url);
                } else {
                    $re_url = $base_url . $tag . $param;
                }
                $re_url = str_replace("?&", "?", $re_url);
                $re_url = str_replace("&?", "&", $re_url);
                $re_url = str_replace("&&", "&", $re_url);
                // return $base_url . $tag . $website. $param;
                return $re_url;
            } else {

                if ($is_ssl) {
                    $re_url = $base_url;
                    $re_url = str_replace("http://", "https://", $re_url);
                } else {
                    $re_url = $base_url;
                }
                $re_url = str_replace("?&", "?", $re_url);
                $re_url = str_replace("&?", "&", $re_url);
                $re_url = str_replace("&&", "&", $re_url);
                // return $base_url . $tag . $website;
                return $re_url;
            }
        }
    }
}
function __URLS($url, $param = '',$arr=[])
{
    $is_ssl = \think\Request::instance()->isSsl();
    $base = new BaseService();
    $model = $base->getRequestModel();
    if (strstr($url, 'SELF')) {
        // return 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        return $is_ssl ? "https://" : "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
//    $website_id = $param['website_id'];
    $websiteid = $arr['website_id'];
    if($websiteid){
        $website_id = $websiteid;
    }else{
        $website_id = Session::get($model . 'website_id');
    }
    $website = new WebSiteModel;
    $realm_ip = $website->getInfo(['website_id'=>$website_id],'realm_ip')['realm_ip'];
    if($realm_ip){
        $real_url = $realm_ip;
    }else{
        $real_url = $_SERVER['HTTP_HOST'];
    }
    $urls =  $is_ssl ? "https://"  . $real_url : "http://" . $real_url;
//    $urls = "http://".$real_url;
    $url = \str_replace('ADDONS_SHOP_MAIN', ADDONS_SHOP_MODULE, $url);
    $url = \str_replace('shop/addons/execute', 'addons/execute', $url);

    $url = \str_replace('SHOP_MAIN', '', $url);
    $url = \str_replace('APP_MAIN', 'wap', $url);
    $url = \str_replace('CLERK_MAIN', 'clerk', $url);
    $url = \str_replace('ADDONS_WAP_MAIN', ADDONS_WAP_MODULE, $url);
    $url = \str_replace('POLY_API_MAIN', 'wapapi/polyapi/index', $url);
    $url = $urls.$url;
    // 处理后台页面
    $url = \str_replace($urls . '/wap', 'wap', $url);
    $url = \str_replace($urls, '', $url);

    $str = substr($url, 0, 1);
    if ($str === '/' || $str === "\\") {
            $url = substr($url, 1, strlen($url));
        }
        if (REWRITE_MODEL) {
            $url = urlRouteConfig($url, $param,$urls);
            if ($is_ssl) {
                $re_url = $url;
                $re_url = str_replace("http://", "https://", $re_url);
            } else {
                $re_url = $url;
            }
            // return $url;
            return $re_url;
        }
        $action_array = explode('?', $url);
        // 检测是否是pathinfo模式
        $url_model = url_model();
        if ($url_model) {
            $base_url = $urls. '/' . $action_array[0];
            $tag = '?';
        } else {
            $base_url = $urls . '?s=/' . $action_array[0];
            $tag = '&';
        }
        if (!empty($action_array[1])) {
            // 有参数
            if ($is_ssl) {
                $re_url = $base_url . $tag . $action_array[1] . '&' . $param;
                $re_url = str_replace("http://", "https://", $re_url);
            } else {
                $re_url = $base_url . $tag . $action_array[1] . '&' . $param;
            }
            //echo 'after is_ssl:'.$re_url."<br>";
            $re_url = str_replace("?&", "?", $re_url);
            $re_url = str_replace("&?", "&", $re_url);
            $re_url = str_replace("&&", "&", $re_url);
            // return $base_url . $tag . $website. $tag . $action_array[1];
            return $re_url;
        } else {
            if (!empty($param)) {

                if ($is_ssl) {
                    $re_url = $base_url . $tag . $param;
                    $re_url = str_replace("http://", "https://", $re_url);
                } else {
                    $re_url = $base_url . $tag . $param;
                }
                $re_url = str_replace("?&", "?", $re_url);
                $re_url = str_replace("&?", "&", $re_url);
                $re_url = str_replace("&&", "&", $re_url);
                // return $base_url . $tag . $website. $param;
                return $re_url;
            } else {
                if ($is_ssl) {
                    $re_url = $base_url;
                    $re_url = str_replace("http://", "https://", $re_url);
                } else {
                    $re_url = $base_url;
                }
                $re_url = str_replace("?&", "?", $re_url);
                $re_url = str_replace("&?", "&", $re_url);
                $re_url = str_replace("&&", "&", $re_url);
                // return $base_url . $tag . $website;
                return $re_url;
            }
        }
}
/**
 * 特定路由规则
 */
function urlRoute()
{
    /***********************************************************************************特定路由规则************************************************/
    if (REWRITE_MODEL) {
        \think\Loader::addNamespace('data', 'data/');
        $website = new WebSite();
        $url_route_list = $website->getUrlRoute();
        if (!empty($url_route_list['data'])) {
            foreach ($url_route_list['data'] as $k => $v) {
                //针对特定路由特殊处理
                if ($v['route'] == 'shop/goods/goodsinfo') {
                    Route::get($v['rule'] . '-<goodsid>', $v['route'], []);
                } elseif ($v['route'] == 'shop/cms/articleclassinfo') {
                    Route::get($v['rule'] . '-<article_id>', $v['route'], []);
                } else {
                    Route::get($v['rule'], $v['route'], []);
                }
            }
        }
    }
}

function urlRouteConfig($url, $param,$urls='')
{
    //针对商品信息编辑
    if($urls){
        $main = \str_replace('/index.php', '', $urls);
    }else{
        $main = \str_replace('/index.php', '', __URL__);
    }

    if (!empty($param)) {
        $url = $main . '/' . $url . '?' . $param;
    } else {
        $action_array = explode('?', $url);
        $url = $main . '/' . $url;
    }

//    $html = Config::get('default_return_type');
//    $url = str_replace('.' . $html, '', $url);

    //针对店铺端进行处理
    $model = Request::instance()->module();
    if ($model == 'shop') {
        \think\Loader::addNamespace('data', 'data/');
        $website = new WebSite();
        $url_route_list = $website->getUrlRoute();
        if (!empty($url_route_list['data'])) {
            foreach ($url_route_list['data'] as $k => $v) {
                $v['route'] = str_replace('shop/', '', $v['route']);
                //针对特定功能处理
                if ($v['route'] == 'goods/goodsinfo') {
                    $url = str_replace('goods/goodsinfo?goodsid=', $v['rule'] . '-', $url);
                } elseif ($v['route'] == 'cms/articleclassinfo') {
                    $url = str_replace('cms/articleclassinfo?article_id=', $v['rule'] . '-', $url);
                } else {
                    $url = str_replace($v['route'], $v['rule'], $url);
                }
            }
        }
    }
    $url = str_replace('??', '?', $url);
    $url_array = explode('?', $url);
    if (!empty($url_array[1])) {
        $url = $url_array[0] .'?' . $url_array[1];
    } else {
        $url = $url_array[0];
    }
    return $url;
}

/**
 * 返回系统是否配置了伪静态
 *
 * @return string
 */
function rewrite_model()
{
    $rewrite_model = REWRITE_MODEL;
    if ($rewrite_model) {
        return 1;
    } else {
        return 0;
    }
}

function url_model()
{
    $url_model = 1;
    try {
        \think\Loader::addNamespace('data', 'data/');
        $website = new WebSite();
        $website_info = $website->getWebSiteInfo();
        if (!empty($website_info)) {
            $url_model = isset($website_info["url_type"]) ? $website_info["url_type"] : 1;
        }
    } catch (Exception $e) {
        $url_model = 1;
    }
    return $url_model;
}

function admin_model()
{
    $admin_model = ADMIN_MODULE;
    return $admin_model;
}

function platform_model()
{
    $platform_model = PLATFORM_MODULE;
    return $platform_model;
}

function master_model()
{
    $master_model = MASTER_MODULE;
    return $master_model;
}

/**
 * 过滤特殊字符(微信qq)
 *
 * @param unknown $str
 */
function filterStr($str)
{
    if ($str) {
        $name = $str;
        $name = preg_replace_callback('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', function ($matches) {
            return '';
        }, $name);
        $name = preg_replace_callback('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S', function ($matches) {
            return '';
        }, $name);
        // 汉字不编码
        $name = json_encode($name);
        $name = preg_replace_callback("/\\\ud[0-9a-f]{3}/i", function ($matches) {
            return '';
        }, $name);
        if (!empty($name)) {
            $name = json_decode($name);
            return $name;
        } else {
            return '';
        }
    } else {
        return '';
    }
}

/**
 * 检测ID是否在ID组
 *
 * @param unknown $id
 *            数字
 * @param unknown $id_arr
 *            数字,数字
 */
function checkIdIsinIdArr($id, $id_arr)
{
    $id_arr = $id_arr . ',';
    $result = strpos($id_arr, $id . ',');
    if ($result !== false) {
        return 1;
    } else {
        return 0;
    }
}


/**
 * 检测ID是否在ID组
 *
 * @param int|string $id
 * @param string $id_string
 * @param string $explode_mark
 *
 * @return boolean
 */
function checkIdIsInIdArrNew($id, $id_string, $explode_mark = ',')
{
    $id_array = explode($explode_mark, $id_string);
    if (in_array($id, $id_array)) {
        return true;
    } else {
        return false;
    }

}

/**
 * 用于用户自定义模板判断 为空的话输出
 */
function __isCustomNullUrl($url)
{
    if (trim($url) == "") {
        return "javascript:;";
    } else {
        return __URL('APP_MAIN/' . $url);
    }
}

/**
 * 图片路径拼装(用于完善用于外链的图片)
 *
 * @param unknown $img_path
 * @param unknown $type
 * @param unknown $url
 * @return string
 */
function __IMG($img_path)
{
    $path = "";
    if (!empty($img_path)) {
        if (stristr($img_path, "http://") === false && stristr($img_path, "https://") === false) {
            $is_ssl = \think\Request::instance()->isSsl();
            $url = $_SERVER['HTTP_HOST'];
            $url = $is_ssl ? 'https://' .$url : 'http://'.$url;
            if ((substr($img_path, 0, 1) == '/')) {
                $path = $url . $img_path;
            }
            if ($img_path != '' && (substr($img_path, 0, 1) != '/')) {
                $path = $url . "/" . $img_path;
            }
        } else {
            $path = $img_path;
        }
    }
    return $path;
}

/**
 * *
 * 判断一个数组是否存在于另一个数组中
 *
 * @param unknown $arr
 * @param unknown $contrastArr
 * @return boolean
 */
function is_all_exists($arr, $contrastArr)
{
    if (!empty($arr) && !empty($contrastArr)) {
        for ($i = 0; $i < count($arr); $i++) {
            if (!in_array($arr[$i], $contrastArr)) {
                return false;
            }
        }
        return true;
    }
}

/**
 * 检查模版是否存在
 *
 * @param 文件夹[shop、wap] $folder
 * @param 当前目录文件夹 $curr_template
 * @return boolean
 */
function checkTemplateIsExists($folder, $curr_template)
{
    $file_path = str_replace("\\", "/", ROOT_PATH . 'template/' . $folder . "/" . $curr_template . "/config.xml");
    return file_exists($file_path);
}

/**
 * 通用提示页(专用于数据库的操作)
 *
 * @param string $msg
 *            提示消息（支持语言包变量）
 * @param integer $status
 *            状态（0：失败；1：成功）
 * @param string $extra
 *            附加数据
 */
function showMessage($msg, $status = 0, $extra = '')
{
    $result = array(
        'status' => $status,
        'message' => $msg,
        'result' => $extra
    );
    return $result;
}

/**
 * 发送HTTP请求方法，目前只支持CURL发送请求
 *
 * @param string $url
 *            请求URL
 * @param array $params
 *            请求参数
 * @param string $method
 *            请求方法GET/POST
 * @return array $data 响应数据
 */
function http($url, $timeout = 30, $header = array())
{
    if (!function_exists('curl_init')) {
        throw new Exception('server not install curl');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    if (!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $data = curl_exec($ch);
    list ($header, $data) = explode("\r\n\r\n", $data);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code == 301 || $http_code == 302) {
        $matches = array();
        preg_match('/Location:(.*?)\n/', $header, $matches);
        $url = trim(array_pop($matches));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = curl_exec($ch);
    }

    if ($data == false) {
        curl_close($ch);
    }
    @curl_close($ch);
    return $data;
}

/**
 * 多维数组排序
 *
 * @param unknown $data
 * @param unknown $sort_order_field
 * @param string $sort_order
 * @param string $sort_type
 * @return unknown
 */
function my_array_multisort($data, $sort_order_field, $sort_order = SORT_DESC, $sort_type = SORT_NUMERIC)
{
    foreach ($data as $val) {
        $key_arrays[] = $val[$sort_order_field];
    }
    array_multisort($key_arrays, $sort_order, $sort_type, $data);
    return $data;
}

function getBlockChain($uid, $website_id, $pay_money)
{
    $blockchain = getAddons('blockchain', $website_id);
    $eos_money = '';
    $eth_money = '';
    $eos_balance = '';
    $eth_balance = '';
    $eth_paymoney = '';
    $eos_paymoney = '';
    if ($blockchain) {
        $block = new Block();
        $blockchain_info = $block->getUidInfo($uid, $pay_money);
        $eth_balance = $blockchain_info['eth_balance'];
        $eos_balance = $blockchain_info['eos_balance'];
        $eos_money = $blockchain_info['eos_money'];
        $eth_money = $blockchain_info['eth_money'];
        $eth_paymoney = $blockchain_info['eth_paymoney'];
        $eos_paymoney = $blockchain_info['eos_paymoney'];
    }
    $data['data']['eth_balance'] =$eth_balance;
    $data['data']['eth_money'] =$eth_money;
    $data['data']['eos_balance'] =$eos_balance;
    $data['data']['eos_money'] =$eos_money;
    $data['data']['eth_paymoney'] =$eth_paymoney;
    $data['data']['eos_paymoney'] =$eos_paymoney;
    return $data;
    }

function getAddons($addons, $website_id, $shop_id = 0, $ignore_is_use = false)
{
    $add_status = Session::get($website_id . '-' . $shop_id. '-'.$addons.'-status'.($ignore_is_use?'-ignore':''));
    if($add_status){
        return Session::get($website_id . '-' . $shop_id. '-'.$addons.'-val'.($ignore_is_use?'-ignore':''));
    }
    $website = new WebSiteModel();
    $version = $website->getInfo(['website_id' => $website_id], 'merchant_versionid')['merchant_versionid'];
    if (!$version) {
        Session::set($website_id . '-' . $shop_id. '-'.$addons.'-status'.($ignore_is_use?'-ignore':''),'set',5);
        Session::set($website_id . '-' . $shop_id. '-'.$addons.'-val'.($ignore_is_use?'-ignore':''),0,5);
        return 0;
    }
    $versionConfig = new MerchantVersionModel();
    $type_module_array = $versionConfig->getInfo(['merchant_versionid' => $version], 'type_module_array')['type_module_array'];
    if (!$type_module_array) {
        Session::set($website_id . '-' . $shop_id. '-'.$addons.'-status'.($ignore_is_use?'-ignore':''),'set',5);
        Session::set($website_id . '-' . $shop_id. '-'.$addons.'-val'.($ignore_is_use?'-ignore':''),0,5);
        return 0;
    }
    $module_array = explode(',', $type_module_array);
    $auth_group = new AuthGroupModel();
    $system_auth = $auth_group->getInfo(['is_system'=>1,'instance_id'=>0,'website_id'=>$website_id],'order_id');
    if($system_auth['order_id']){
        $order_ids = explode(',',$system_auth['order_id']);
        $order = new VslIncreMentOrderModel();
        $module = new  ModuleModel();
        foreach($order_ids as $k=>$v){
            $addons_id = $order->getInfo(['order_id'=>$v,'expire_time'=>['>',time()]],'addons_id')['addons_id'];
            if($addons_id){
                $module_ids = $module->Query(['addons_sign'=>$addons_id],'module_id');
                $module_array = array_merge($module_array,$module_ids);
            }
        }
    }
    $addon = new sysAddonsModel();
    $addonInfo = $addon->getInfo(['name' => $addons], 'status,up_status,module_id,admin_module_id,no_set');
    if (!$addonInfo) {
        Session::set($website_id . '-' . $shop_id. '-'.$addons.'-status'.($ignore_is_use?'-ignore':''),'set',5);
        Session::set($website_id . '-' . $shop_id. '-'.$addons.'-val'.($ignore_is_use?'-ignore':''),0,5);
        return 0;
    }
    if ($addonInfo['up_status']==2) {
        Session::set($website_id . '-' . $shop_id. '-'.$addons.'-status'.($ignore_is_use?'-ignore':''),'set',5);
        Session::set($website_id . '-' . $shop_id. '-'.$addons.'-val'.($ignore_is_use?'-ignore':''),0,5);
        return 0;
    }
    $config = new AddonsConfigModel();
    $configInfo = $config->getInfo([
        'website_id' => $website_id,
        'addons' => $addons
    ]);
    if($shop_id>0){
        $instance = new InstanceModel();
        $shop_type_module_array = $instance->alias('si')
        ->join('sys_instance_type sit', 'sit.instance_typeid = si.instance_typeid', 'left')
        ->where(['si.instance_id'=>$shop_id,'si.website_id'=>$website_id])->value('type_module_array');
        if (!$shop_type_module_array) {
            Session::set($website_id . '-' . $shop_id. '-'.$addons.'-status'.($ignore_is_use?'-ignore':''),'set',5);
            Session::set($website_id . '-' . $shop_id. '-'.$addons.'-val'.($ignore_is_use?'-ignore':''),0,5);
            return 0;
        }
        $shop_module_array = explode(',', $shop_type_module_array);
    }
    //不需要开启的应用，默认开启
    if ($addonInfo['no_set'] || $ignore_is_use) {
        $configInfo['is_use'] = 1;
    }
    if (in_array($addonInfo['module_id'], $module_array) && $addonInfo['status'] == 1  && $addonInfo['up_status'] != 2 && $configInfo['is_use'] == 1) {
        $status = 1;
        if($shop_id>0 && !in_array($addonInfo['admin_module_id'], $shop_module_array)){
            $status = 0;
        }
    } else {
        $status = 0;
    }
    Session::set($website_id . '-' . $shop_id. '-'.$addons.'-status'.($ignore_is_use?'-ignore':''),'set',5);
    Session::set($website_id . '-' . $shop_id. '-'.$addons.'-val'.($ignore_is_use?'-ignore':''),$status,5);
    return $status;
}

function checkAddons($hooks, $website_id)
{
    $hookModel = new SysHooksModel();
    $addons = $hookModel->getInfo(['name' => $hooks, 'status' => 1])['addons'];
    return getAddons($addons, $website_id);
}

function addons_is_use($website_id,$addons){
    $config = new AddonsConfigModel();
    $configInfo = $config->getInfo([
        'website_id' => $website_id,
        'addons' => $addons
    ]);
    return $configInfo['is_use'];
}

function isApiLegal()
{
    $app_key = $api_key = API_KEY;
    // wap sign
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

    $http_sign = $_SERVER['HTTP_SIGN'];
    if (md5($api_key) == $http_sign || md5($module_key) == $http_sign || md5($controller_key) == $http_sign || md5($action_key) == $http_sign || API_KEY == $http_sign) {
        return true;
    } else {
        return false;
    }
}

function getUserId($user_token = '')
{
    if (!isset($_SERVER['HTTP_USER_TOKEN']) || empty($_SERVER['HTTP_USER_TOKEN'])){
        return false;
    }
    $user_token = !empty($user_token) ? $user_token : $_SERVER['HTTP_USER_TOKEN'];

    $uid = Session::get($user_token);
    $model = Request::instance()->module();
    if ($uid == Session::get($model . 'uid') && !is_null($uid)) {
        return $uid;
    } else {

        $user_model = new UserModel();
        $user_info = $user_model::get(['user_token' => $user_token]);
        if ($user_info && $user_info['user_status'] == 1) {
            return $user_info['uid'];
        }
        return false;
    }
}

function getApiSrc($src)
{
    if (stristr($src, "http://") || stristr($src, "https://")) {
        return $src;
    }
    if ((substr($src, 0, 1) == '/')) {
        return Request::instance()->domain() . $src;
    }
    if ($src != '' && (substr($src, 0, 1) != '/')) {
        return Request::instance()->domain() . '/' . $src;
    }
    return $src;
}

function changeFile($img_path, $website_id,$src='common')
{
    //获取图片base64字符串
    $imgBase64 = $img_path;
    $new_file = '';
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $imgBase64, $res)) {
        //获取图片类型
        $type = $res[2];

        //图片保存路径
        // 检测文件夹是否存在，不存在则创建文件夹
        $new_file = 'upload/' . $website_id . '/'.$src.'/';

        if (!file_exists($new_file)) {
            $mode = intval('0777', 8);
            mkdir($new_file, $mode, true);
        }
        //图片名字
        $new_file = $new_file . rand(100000,999999).time() . '.' . $type;
        file_put_contents($new_file, base64_decode(str_replace($res[1], '', $imgBase64)));
        $config = new WebConfig();
        $upload_type = $config->getUploadType();
        if ($upload_type == 2) {
            $alioss = new AliOss();
            $result = $alioss->setAliOssUplaod($new_file, $new_file);
            if($result['code']=='1'){
                @unlink($new_file);
                $new_file = $result['path'];
            }
        }
    }
    return $new_file;
}

function isWIthTarBarAndCopyright($type)
{
    //商城首页,会员中心才有底部信息
    return in_array($type, [1, 4]);
}

function objToArr($obj)
{
    return json_decode(json_encode($obj), true);
}
/**
 * @param bool $get_update_data true:只返回需要插入的新数据,false:返回所有数据
 * @param int $maxSize 返回数据条数
 * @param int $page 页数
 * @return array
 * @throws \think\Exception\DbException
 * @see https://wx.jdcloud.com/market/api/10480?apiId=10480
 */
function getExpressCompany($get_update_data = false, $maxSize = 500, $page = 1)
{
    $content = file_get_contents(Config::get('jd_cloud.express_company_api') . "?maxSize=$maxSize&page=$page&appkey=" . Config::get('jd_cloud.express_app_key'));
    $content = json_decode($content, true);
    if ($content['code'] != 10000) {
        return ['code' => -1, 'message' => $content['msg']];
    }
    if (empty($content['result']) || empty($content['result']['showapi_res_body']) || empty($content['result']['showapi_res_body']['expressList'])) {
        return ['code' => -1, 'message' => '返回的空列表数据'];
    }

    $company = Session::get('api_express_company_list_' . $get_update_data);
    if ($company) {
        return ['code' => 1, 'data' => $company];
    } else {
        $company = [];
    }
    $express_company_model = new \data\model\VslOrderExpressCompanyModel();
    foreach ($content['result']['showapi_res_body']['expressList'] as $k => $v) {
        if (empty($v['expName']) || empty($v['simpleName'])) {
            continue;
        }
        if ($get_update_data && $express_company_model::get(['express_no' => $v['simpleName']])) {
            // 只获取不存在的数据
            continue;
        }

        $temp['company_name'] = $v['expName'];
        $temp['express_no'] = $v['simpleName'];
        $temp['phone'] = $v['phone'] ?: '';
        $temp['url'] = $v['url'] ?: '';
        $temp['express_logo'] = $v['imgUrl'] ?: '';

        $company[] = $temp;
    }
    Session::set('api_express_company_list_' . $get_update_data, $company, 1 * 60 * 60);
    return ['code' => 1, 'data' => $company];
}

/**
 * @param string $nu 物流单号
 * @param int $com_id 物流公司id
 * @param string $com 物流公司简称编号,缺少该字段有时查询不到物流信息
 * @return array
 * @throws \think\Exception\DbException
 * @see https://wx.jdcloud.com/market/api/10480?apiId=10480
 */
function getShipping($nu, $com_id = 0, $com = 'auto',$website_id=null)
{
    $nu = trim($nu);
    if (empty($nu)) {
        return ['code' => -1, 'message' => '缺少物流单号'];
    }
    if (!empty($com_id)) {
        $express_company_model = new \data\model\VslOrderExpressCompanyModel();
        $com = $express_company_model::get($com_id)['express_no'];
    }
    $shipping_info = Session::get('shipping_info' . $com . $nu);
    if ($shipping_info) {
        return ['code' => 1, 'message' => '获取成功', 'data' => $shipping_info];
    }
    $config = new \data\model\ConfigModel();
    if($website_id){
        $express_app_key = $config->getInfo(['key'=>'COMPANYCONFIGSET','website_id'=>$website_id],'value')['value'];
    }else{
        $express_app_key = Config::get('jd_cloud.express_app_key');
    }
    $content = file_get_contents(Config::get('jd_cloud.shipping_api') . "?com=$com&nu=$nu&appkey=" . $express_app_key);
    $content = json_decode($content, true);

    if ($content['code'] != 10000) {
        return ['code' => -1, 'message' => $content['msg']];
    }
    if (empty($content['result']) || empty($content['result']['showapi_res_body'])) {
        return ['code' => -1, 'message' => '没查找到物流信息'];
    }
    if ($content['result']['showapi_res_code'] != 0) {
        return ['code' => -1, 'message' => $content['result']['showapi_res_error']];
    }
    if ($content['result']['showapi_res_body']['ret_code'] != 0) {
        return ['code' => -1, 'message' => $content['result']['showapi_res_body']['msg']];
    }
    if ($content['result']['showapi_res_body']['dataSize'] == 0) {
        return ['code' => -1, 'message' => '没查找到物流信息'];
    }
    Session::set('shipping_info' . $com . $nu, $content['result']['showapi_res_body'], 1 * 60 * 60);

    return ['code' => 1, 'data' => $content['result']['showapi_res_body']];
}

function iserializer($value)
{
    return serialize($value);
}

function utf8_bytes($cp)
{
    if ($cp > 0x10000) {
        return chr(0xF0 | (($cp & 0x1C0000) >> 18)) .
            chr(0x80 | (($cp & 0x3F000) >> 12)) .
            chr(0x80 | (($cp & 0xFC0) >> 6)) .
            chr(0x80 | ($cp & 0x3F));
    } else if ($cp > 0x800) {
        return chr(0xE0 | (($cp & 0xF000) >> 12)) .
            chr(0x80 | (($cp & 0xFC0) >> 6)) .
            chr(0x80 | ($cp & 0x3F));
    } else if ($cp > 0x80) {
        return chr(0xC0 | (($cp & 0x7C0) >> 6)) .
            chr(0x80 | ($cp & 0x3F));
    } else {
        return chr($cp);
    }
}

function iunserializer($value)
{
    if (empty($value)) {
        return '';
    }
    if (!is_serialized($value)) {
        return $value;
    }
    $result = unserialize($value);
    if ($result === false) {
        //return preg_replace_callback("/{([^\}\{\n]*)}/", function($r) { return $this->select($r[1]); }, $source);
        $temp = preg_replace_callback("/{([^\}\{\n]*)}/", function ($r) {
            return $this->select($r[1]);
        }, $value);
        return unserialize($temp);
    }
    return $result;
}

function is_serialized($data, $strict = true)
{
    if (!is_string($data)) {
        return false;
    }
    $data = trim($data);
    if ('N;' == $data) {
        return true;
    }
    if (strlen($data) < 4) {
        return false;
    }
    if (':' !== $data[1]) {
        return false;
    }
    if ($strict) {
        $lastc = substr($data, -1);
        if (';' !== $lastc && '}' !== $lastc) {
            return false;
        }
    } else {
        $semicolon = strpos($data, ';');
        $brace = strpos($data, '}');
        if (false === $semicolon && false === $brace)
            return false;
        if (false !== $semicolon && $semicolon < 3)
            return false;
        if (false !== $brace && $brace < 4)
            return false;
    }
    $token = $data[0];
    switch ($token) {
        case 's' :
            if ($strict) {
                if ('"' !== substr($data, -2, 1)) {
                    return false;
                }
            } elseif (false === strpos($data, '"')) {
                return false;
            }
        case 'a' :
        case 'O' :
            return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
        case 'b' :
        case 'i' :
        case 'd' :
            $end = $strict ? '$' : '';
            return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
    }
    return false;
}

function checkUrl(){
    $website = new WebSiteModel();
    $host = $_SERVER['HTTP_HOST'];
    // todo ... 小程序域名
    $websiteId =  Request::instance()->header('website-id');
    if ($websiteId) {
        $website_id = $websiteId; //这个是为了小程序不能用域名获取对应数据而传website_id来对应查询相应数据 by sgw
    } elseif(file_exists('././version.php')) {//源码判断顶级域名
        $host = getHost($host);
        $website_id = $website->getInfo(['realm_ip'=>['like',"%" . $host . "%"]],'website_id')['website_id'];
    } else{
        $website_id = $website->getInfo(['realm_ip'=>$host],'website_id')['website_id'];
    }
    if(!isset($website_id)){
        $host = strstr($host,'.',true);
        $website_id = $website->getInfo(['realm_two_ip'=>$host],'website_id')['website_id'];
    }

    $model = Request::instance()->module();
    Session::set($model . 'website_id', $website_id);
    return $website_id;
}

function msecTime()
{
    list($msec, $sec) = explode(' ', microtime());
    $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectime;
}
function twoDecimal($a){
    if($a < 0.01){
        return 0;
    }
    $retAttr = explode('.',$a);
    if(count($retAttr)>1){
        $strs = substr($retAttr[1],0,2);
        $newsstrs = $retAttr[0].'.'.$strs;
        return $newsstrs;
    }else{
        return $a;
    }
}
/*
 * 截取一级域名
 */
function regular_domain($domain)
{
  if (substr ( $domain, 0, 7 ) == 'http://') {
    $domain = substr ( $domain, 7 );
  }
  if (strpos ( $domain, '/' ) !== false) {
    $domain = substr ( $domain, 0, strpos ( $domain, '/' ) );
  }
  return strtolower ( $domain );
}
function top_domain($domain) {
  $domain = regular_domain ( $domain );
  $iana_root = array (
      'ac',
      'ad',
      'ae',
      'aero',
      'af',
      'ag',
      'ai',
      'al',
      'am',
      'an',
      'ao',
      'aq',
      'ar',
      'arpa',
      'as',
      'asia',
      'at',
      'au',
      'aw',
      'ax',
      'az',
      'ba',
      'bb',
      'bd',
      'be',
      'bf',
      'bg',
      'bh',
      'bi',
      'biz',
      'bj',
      'bl',
      'bm',
      'bn',
      'bo',
      'bq',
      'br',
      'bs',
      'bt',
      'bv',
      'bw',
      'by',
      'bz',
      'ca',
      'cat',
      'cc',
      'cd',
      'cf',
      'cg',
      'ch',
      'ci',
      'ck',
      'cl',
      'cm',
      'cn',
      'co',
      'com',
      'coop',
      'cr',
      'cu',
      'cv',
      'cw',
      'cx',
      'cy',
      'cz',
      'de',
      'dj',
      'dk',
      'dm',
      'do',
      'dz',
      'ec',
      'edu',
      'ee',
      'eg',
      'eh',
      'er',
      'es',
      'et',
      'eu',
      'fi',
      'fj',
      'fk',
      'fm',
      'fo',
      'fr',
      'ga',
      'gb',
      'gd',
      'ge',
      'gf',
      'gg',
      'gh',
      'gi',
      'gl',
      'gm',
      'gn',
      'gov',
      'gp',
      'gq',
      'gr',
      'gs',
      'gt',
      'gu',
      'gw',
      'gy',
      'hk',
      'hm',
      'hn',
      'hr',
      'ht',
      'hu',
      'id',
      'ie',
      'il',
      'im',
      'in',
      'info',
      'int',
      'io',
      'iq',
      'ir',
      'is',
      'it',
      'je',
      'jm',
      'jo',
      'jobs',
      'jp',
      'ke',
      'kg',
      'kh',
      'ki',
      'km',
      'kn',
      'kp',
      'kr',
      'kw',
      'ky',
      'kz',
      'la',
      'lb',
      'lc',
      'li',
      'lk',
      'lr',
      'ls',
      'lt',
      'lu',
      'lv',
      'ly',
      'ma',
      'mc',
      'md',
      'me',
      'mf',
      'mg',
      'mh',
      'mil',
      'mk',
      'ml',
      'mm',
      'mn',
      'mo',
      'mobi',
      'mp',
      'mq',
      'mr',
      'ms',
      'mt',
      'mu',
      'museum',
      'mv',
      'mw',
      'mx',
      'my',
      'mz',
      'na',
      'name',
      'nc',
      'ne',
      'net',
      'nf',
      'ng',
      'ni',
      'nl',
      'no',
      'np',
      'nr',
      'nu',
      'nz',
      'om',
      'org',
      'pa',
      'pe',
      'pf',
      'pg',
      'ph',
      'pk',
      'pl',
      'pm',
      'pn',
      'pr',
      'pro',
      'ps',
      'pt',
      'pw',
      'py',
      'qa',
      're',
      'ro',
      'rs',
      'ru',
      'rw',
      'sa',
      'sb',
      'sc',
      'sd',
      'se',
      'sg',
      'sh',
      'si',
      'sj',
      'sk',
      'sl',
      'sm',
      'sn',
      'so',
      'sr',
      'ss',
      'st',
      'su',
      'sv',
      'sx',
      'sy',
      'sz',
      'tc',
      'td',
      'tel',
      'tf',
      'tg',
      'th',
      'tj',
      'tk',
      'tl',
      'tm',
      'tn',
      'to',
      'tp',
      'tr',
      'travel',
      'tt',
      'tv',
      'tw',
      'tz',
      'ua',
      'ug',
      'uk',
      'um',
      'us',
      'uy',
      'uz',
      'va',
      'vc',
      've',
      'vg',
      'vi',
      'vn',
      'vu',
      'wf',
      'ws',
      'xxx',
      'ye',
      'yt',
      'za',
      'zm',
      'zw'
  );
  $sub_domain = explode ( '.', $domain );
  $top_domain = '';
  $top_domain_count = 0;
  for($i = count ( $sub_domain ) - 1; $i >= 0; $i --) {
    if ($i == 0) {
      // just in case of something like NAME.COM
      break;
    }
    if (in_array ( $sub_domain [$i], $iana_root )) {
      $top_domain_count ++;
      $top_domain = '.' . $sub_domain [$i] . $top_domain;
      if ($top_domain_count >= 2) {
        break;
      }
    }
  }
  $top_domain = $sub_domain [count ( $sub_domain ) - $top_domain_count - 1] . $top_domain;
  return $top_domain;
}

function legalFile($file, array $allow_extension)
{
    $path_info = pathinfo($file);
    if (in_array($path_info['extension'], $allow_extension)) {
        return true;
    } else {
        return false;
    }
}
/**
 * 抽奖概率
 * Alias Method
 * @param array $data 总和为1
 */
function getAliasMethod(array $data) {
    $prob = $alias = [];
    $nums = count($data);
    $small = $large = array();
    for ($i = 0; $i < $nums; ++$i) {
        $data[$i] = $data[$i] * $nums; // 扩大倍数，使每列高度可为1
        /** 分到两个数组，便于组合 */
        if ($data[$i] < 1) {
            $small[] = $i;
        } else {
            $large[] = $i;
        }
    }
    /** 将超过1的色块与原色拼凑成1 */
    while (!empty($small) && !empty($large)) {
        $nindex = array_shift($small);
        $aindex = array_shift($large);
        
        $prob[$nindex] = $data[$nindex];
        $alias[$nindex] = $aindex;
        // 重新调整大色块
        $data[$aindex] = ($data[$aindex] + $data[$nindex]) - 1;
        
        if ($data[$aindex] < 1) {
            $small[] = $aindex;
        } else {
            $large[] = $aindex;
        }
    }
    /** 剩下大色块都设为1 */
    while (!empty($large)) {
        $nindex = array_shift($large);
        $prob[$nindex] = 1;
    }
    /** 一般是精度问题才会执行这一步 */
    while (!empty($small)) {
        $nindex = array_shift($small);
        $prob[$nindex] = 1;
    }
    
    $nums = count($prob) - 1;
    $max = 100000; // 假设最小的几率是万分之一
    $toss = rand(1, $max) / $max; // 抛出硬币
    $col = rand(0, $nums); // 随机落在一列
    $result = ($toss < $prob[$col]) ? TRUE : FALSE; // 判断是否落在原色
    return $result ? $col : $alias[$col];
}

/*
  * 用于测试
  * **/
function p($arr, $desc = ''){
    if ($desc) {
        echo '<pre> ====> '.$desc.' <==== </pre>';echo PHP_EOL;
    }
    echo '<pre>';print_r(objToArr($arr));echo '</pre>';echo PHP_EOL;
}
function v($arr){
    echo '<pre>';var_dump(objToArr($arr));echo '</pre>';
}
/*
 * 判断权限
 */
function per($controller, $action){
    $module = new ModuleModel();
    $module_info = $module->getModuleIdByModule($controller, $action);
    if(!$module_info || $module_info["is_control_auth"] == 0){
        return 1;
    }
    $user = new User();
    return $user->checkAuth($module_info['module_id']);
}
function list_sort_by($list, $field, $sortby = 'asc') {
    if (is_array($list)) {
        $refer = $resultSet = array();
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc':// 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }
    return false;
}
/*
 * 计算两个时间戳的时间间隔 时分秒
 */
function time_diff($timestamp1, $timestamp2)
{
     if ($timestamp2 <= $timestamp1)
     {
         return 0;
     }
     $timediff = $timestamp2 - $timestamp1;
     // 时
     $remain = $timediff%86400;
     $hours = intval($remain/3600);
     // 分
     $remain = $timediff%3600;
     $mins = intval($remain/60);
     // 秒
     //$secs = $remain%60;
     $time = $mins.'分钟';
     if($hours > 0){
         $time = $hours . '小时' .$mins .'分钟';
     }
     return $time;
 }

 /**
  * curl 请求
  */
function https_request($url, $data = null){
    # 初始化一个cURL会话
    $curl = curl_init();
    //设置请求选项, 包括具体的url
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);  //禁用后cURL将终止从服务端进行验证
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_REFERER, $_SERVER['HTTP_HOST']); 
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);  //设置为post请求类型
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  //设置具体的post数据
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($curl);  //执行一个cURL会话并且获取相关回复
    curl_close($curl);  //释放cURL句柄,关闭一个cURL会话

    return $response;
}
//发送请求操作仅供参考,不为最佳实践
function tl_request($url,$params){
    $ch = curl_init();
//    $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
//    curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
//    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//如果不加验证,就设false,商户自行处理
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $output = curl_exec($ch);
    curl_close($ch);
    return  $output;
}
/**
 * 下载图片
*/
function imageDownLoad($fileDir = '', $fileName = '')
{

    // 检查文件是否存在
    if (! file_exists($fileDir) ) {
        return false;
    } else {
        $fileName = !empty($fileName) ? $fileName : $fileDir;
        // 打开文件
        $file1 = fopen($fileDir, "r");
        // 输入文件标签
        Header('Content-type: image/png');
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:".filesize($fileDir));
        Header("Content-Disposition: attachment;filename=" . $fileName);
        ob_clean();
        flush();
        //输出文件内容
        //读取文件内容并直接输出到浏览器
        echo fread($file1, filesize($fileDir));
        fclose($file1);
        exit();
    }
}

/**
 * 上传图片到阿里云
 * @param $url  返回图片字串，需要转base64
 * @param string $path  储存的本地路径，返回后删除
 * @param string $fileName 图片名
 * @return string 阿里云对应存储图片路径图片名
 */
function getImageFromYun($url, $path = '', $fileName = '')
{

    $path = transAndThumbImg($url, $path , $fileName);
    // 上传云
    $config = new WebConfig();
    $upload_type = $config->getUploadType();
    if ($upload_type == 2) {
        $alioss = new AliOss();
        $delte_return = $alioss->deleteAliOss($path.'/.png');
        $result = $alioss->setAliOssUplaod($path, $path);
    }
    if($result['code']){
        @unlink($path);
        return $result['path'];
    }
    return __IMG($path);
}

/**
 * 调试log
 * @param $data| mix  数据
 * @param $desc | string 描述
 * @param $jsonEncode | bool 使用json_encode加密
*/
function debugLog($data, $desc='', $jsonEncode = true)
{
    if ($jsonEncode) {
        $insertData = [
            'content' => $desc .json_encode($data, JSON_UNESCAPED_UNICODE),
            'time' => date('Y-m-d H:i:s')
        ];
    } else {
        $insertData = [
            'content' => $desc .$data,
            'time' => date('Y-m-d H:i:s')
        ];
    }

    \think\Db::table('sys_log')->insert($insertData);
}

/**
 * 数组按某个value排序
 * @param $array 需要排序的数组
 * @param $value 按该值排序
 * @param string $sort 排序方式
 * @return mixed
 */
function arrSortByValue($array, $value, $sort = 'desc')
{
    if (empty($array)) {return;}
    if ($sort == 'desc') {
        $sort = SORT_DESC;
    } else {
        $sort = SORT_ASC;
    }
    array_multisort(array_column($array, $value), $sort, $array);

    return $array;
}

/***
 * 判断是否是json字串
 * @param $string
 * @return bool
 */
function json_validate($string) {
    if (is_string($string)) {
        @json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    return false;
}

/**
 * 通过子域名获取对应配置
 */
function getWchatConfigByChildDomain(){

    $wchat_config_key = 'shop';
    if (strpos(Request::instance()->host(),'sp1') !== false){
        $wchat_config_key = 'sp1';
    } else if(strpos(Request::instance()->host(),'sp2') !== false){
        $wchat_config_key = 'sp2';
    }

    return $wchat_config_key;
}
function getServerIp(){   
  return gethostbyname($_SERVER["SERVER_NAME"]);   
}  
function recordErrorLog(Exception $e)
{
    Log::init([
        'type' => 'File',
        'path' => LOG_PATH,
        'level' => ['error']
   ]);
   Log::record($e->getMessage(), 'error');
}

/**
 * 图片转成base64
 * @param $image_file 图片路径
 * @return string
 */
function base64EncodeImage ($image_file) {
    $image_info = getimagesize($image_file);
    $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
    $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
    $image = str_replace(PHP_EOL, '', $base64_image);
    return $image;
}

/**
 * 图片资源存储本地压缩后返回
 * @param $url
 * @param string $path
 * @param string $fileName
 */
function transAndThumbImg($url, $path = '', $fileName = '', $width = 500, $heigth = 300, $thumb = false)
{
    if (!is_dir($path)) {
        $mode = intval('0777', 8);
        mkdir($path, $mode, true);
        chmod($path, $mode);
    }
    if ($path[strlen($path) - 1] !== '/') {
        $path .= '/';
    }
    // 图片路径名
    $path .= $fileName . '.png';
    @unlink($path);
    $base64_img = base64_encode($url);

    // 存储图片
    @file_put_contents(ROOT_PATH .$path, base64_decode($base64_img));//返回的是字节数
    // 压缩
//    if ($thumb) {
//        $image = think\Image::open(ROOT_PATH .$path);
//        $image->thumb($width,$heigth)->save(ROOT_PATH .$path, null, 90);
//    }
    return $path;
}
/*
 * 创建缓存二级目录
 */
function session_save(){
    $string = '0123456789abcdefghijklmnopqrstuvwxyz';
    
    $websiteSer = new WebSite();
    $dateArr = $websiteSer->getWebCreateTime($website_id);
    $path = ROOT_PATH . '/public/addons_status/' . $dateArr['year'].'/'.$dateArr['month'].'/'.$dateArr['day'].'/'. $website_id;
    if (!is_dir($path)) {
        $mode = intval('0755', 8);
        mkdir($path, $mode, true);
        $arrContent = [];
    }else{
        $value = @file_get_contents($path.'/addons_' . $shop_id);
        if(!$value){
            $arrContent = [];
        }else{
            $arrContent = iunserializer($value);
        }
    }
    $key = $addons;
    if($ignore_is_use){
        $key = $addons . '-ignore';
    }
    if(!file_exists(ADDON_PATH . $addons)){//文件夹不存在
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    $website = new WebSiteModel();
    $version = $website->getInfo(['website_id' => $website_id], 'merchant_versionid')['merchant_versionid'];//查询商家是否有版本
    if (!$version) {//商家没有指定版本
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    $versionConfig = new MerchantVersionModel();
    $type_module_array = $versionConfig->getInfo(['merchant_versionid' => $version], 'type_module_array')['type_module_array'];
    if (!$type_module_array) {//版本没有权限
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    $module_array = explode(',', $type_module_array);
    $auth_group = new AuthGroupModel();
    $system_auth = $auth_group->getInfo(['is_system'=>1,'instance_id'=>0,'website_id'=>$website_id],'order_id');
    if($system_auth['order_id']){//查询购买的增值应用
        $order_ids = explode(',',$system_auth['order_id']);
        $order = new VslIncreMentOrderModel();
        $module = new  ModuleModel();
        foreach($order_ids as $k=>$v){
            $addons_id = $order->getInfo(['order_id'=>$v,'expire_time'=>['>',time()]],'addons_id')['addons_id'];
            if($addons_id){
                $module_ids = $module->Query(['addons_sign'=>$addons_id],'module_id');//根据购买的增值应用查询应用菜单id，与已有权限id合并
                $module_array = array_merge($module_array,$module_ids);
            }
        }
    }
    $addon = new sysAddonsModel();
    $addonInfo = $addon->getInfo(['name' => $addons], 'status,up_status,module_id,admin_module_id,no_set');
    if (!$addonInfo) {//查询应用是否安装
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    if ($addonInfo['up_status']==2) {//应用即将上线
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    $config = new AddonsConfigModel();
    $configInfo = $config->getInfo([
        'website_id' => $website_id,
        'addons' => $addons
    ]);//应用设置
    if($shop_id>0){//店铺端权限
        $instance = new InstanceModel();
        $shop_type_module_array = $instance->alias('si')
        ->join('sys_instance_type sit', 'sit.instance_typeid = si.instance_typeid', 'left')
        ->where(['si.instance_id'=>$shop_id,'si.website_id'=>$website_id])->value('type_module_array');
        if (!$shop_type_module_array) {
            $arrContent[$key] = 0;
            file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
            return 0;
        }
        $shop_module_array = explode(',', $shop_type_module_array);
    }
    //不需要开启的应用，默认开启
    if ($addonInfo['no_set'] || $ignore_is_use) {
        $configInfo['is_use'] = 1;
    }
    if (in_array($addonInfo['module_id'], $module_array) && $addonInfo['status'] == 1  && $addonInfo['up_status'] != 2 && $configInfo['is_use'] == 1) {
        $status = 1;
        if($shop_id>0 && !in_array($addonInfo['admin_module_id'], $shop_module_array)){
            $status = 0;
        }
    } else {
        $status = 0;
    }
    $arrContent[$key] = $status;
    file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
    return $status;
}


//明文密码加解密
function encrypt($string,$operation,$key='vslai'){
    $key=md5($key);
    $key_length=strlen($key);
    $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
    $string_length=strlen($string);
    $rndkey=$box=array();
    $result='';
    for($i=0;$i<=255;$i++){
        $rndkey[$i]=ord($key[$i%$key_length]);
        $box[$i]=$i;
    }
    for($j=$i=0;$i<256;$i++){
        $j=($j+$box[$i]+$rndkey[$i])%256;
        $tmp=$box[$i];
        $box[$i]=$box[$j];
        $box[$j]=$tmp;
    }
    for($a=$j=$i=0;$i<$string_length;$i++){
        $a=($a+1)%256;
        $j=($j+$box[$a])%256;
        $tmp=$box[$a];
        $box[$a]=$box[$j];
        $box[$j]=$tmp;
        $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
    }
    if($operation=='D'){
        if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8)){
            return substr($result,8);
        }else{
            return'';
        }
    }else{
        return str_replace('=','',base64_encode($result));
    }

}
//获取毫秒时间戳
function getMsecTime()
{
    list($msec, $sec) = explode(' ', microtime());
    $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectime;
}

/**
 * 毫秒转日期
 */
function getMsecToMescdate($msectime)
{
    $msectime = $msectime * 0.001;
    if(strstr($msectime,'.')){
        sprintf("%01.3f",$msectime);
        list($usec, $sec) = explode(".",$msectime);
        $sec = str_pad($sec,3,"0",STR_PAD_RIGHT);
    }else{
        $usec = $msectime;
        $sec = "000";
    }
    $date = date("Y-m-d H:i:s.x",$usec);
    return $mescdate = str_replace('x', $sec, $date);
}
// 是否配置了微信
function isWchatSetUp($website_id, $shop_id){
    $config = new ConfigServer();
    $wchat_config = $config->getInstanceWchatConfig($shop_id, $website_id);
    $is_wchat = false;
    if (!empty($wchat_config['value']['appid']) && !empty($wchat_config['value']['public_name']) && !empty($wchat_config['value']['appsecret'])) {
        $is_wchat = true;
    }
    return $is_wchat;
}
/**
 * 获取网站域名
 * @param string $website_id
 * @param bool $independent_domain [获取独立域名]
 * @return string 主域名| 独立域名
 */
function getIndependentDomain($website_id='', $independent_domain = false)
{
    if (!$independent_domain) {
        return request()->domain();//主域名
    }
    if ($website_id) {
        return getDomain($website_id);

    }
}

/**
 * 获取网站域名
 */
function getDomain($website_id = '')
{
    if (!$website_id) return false;
    $website_model = new WebSiteModel();
    $web_info = $website_model->getInfo(['website_id' => $website_id],'realm_ip,realm_two_ip');
    if ($web_info['realm_ip']) {
        $domain = $web_info['realm_ip'];
    }

    if(file_exists('././version.php')){//源码
        $is_ssl = \think\Request::instance()->isSsl();
        if($is_ssl){
            $url = 'https://';
        }else{
            $url = 'http://';
        }
        if(empty($domain)){
            // 第三方域名
            $config = Config::get('weChat.' . getWchatConfigByChildDomain());
            $third_domain_name = $config['open']['third_domain'];
            if (empty($third_domain_name)) {
                $website_model = new WebSiteModel();
                $web_info = $website_model->getInfo(['website_id' => 1],'realm_ip');// 默认第三方独立域名website_id = 1,只能拿第三方独立域名，小程序现在都是拿website_id查询的
                if ($web_info['realm_ip']) {
                    $domain = $web_info['realm_ip'];
                }
            } else {
                $domain = $third_domain_name;
            }
        }
    }else{
        $url = 'http://';
        if($web_info['realm_ip']){
            $url = 'https://';
        }else{
            $is_ssl = \think\Request::instance()->isSsl();
            if($is_ssl){
                $url = 'https://';
            }
        }
        if(empty($domain)){
            $ip = top_domain($_SERVER['HTTP_HOST']);
            $web_info['realm_two_ip'] = $web_info['realm_two_ip'].'.'.$ip;
            $domain =  $web_info['realm_two_ip'];
        }
    }
    $domains = $url.$domain;
    return $domains;
}
//获取顶级域名

function getHost($to_virify_url = ''){
    
    $url   = $to_virify_url ? $to_virify_url : $_SERVER['HTTP_HOST'];
    $data = explode('.', $url);
    $co_ta = count($data);
 
    //判断是否是双后缀
    $zi_tow = true;
    $host_cn = 'com.cn,net.cn,org.cn,gov.cn';
    $host_cn = explode(',', $host_cn);
    foreach($host_cn as $host){
        if(strpos($url,$host)){
            $zi_tow = false;
        }
    }
 
    //如果是返回FALSE ，如果不是返回true
    if($zi_tow == true){
 
        // 是否为当前域名
        if($url == 'localhost'){
            $host = $data[$co_ta-1];
        }
        else{
            $host = $data[$co_ta-2].'.'.$data[$co_ta-1];
        }
        
    }
    else{
        $host = $data[$co_ta-3].'.'.$data[$co_ta-2].'.'.$data[$co_ta-1];
    }
    
    return $host;
}
/**
 * 后台应用修改基础设置 强制清理后台缓存 
 */
function clearCaches($website_id = 0){
    $model = \think\Request::instance()->module();
    $admin = new AdminUserModel();
    $admin_info = $admin->getInfo('uid=' . Session::get($model.'uid'), 'is_admin,group_id_array');
    $auth_group = new AuthGroupModel();
    $auth = $auth_group->get($admin_info['group_id_array']);
    $system_auth = $auth_group->getInfo(['is_system'=>1,'instance_id'=>0,'website_id'=>$website_id],'order_id,group_id');
    if($system_auth['order_id']){
        $bonus_id = [];
        $unbonus_id = [];
        $module = new ModuleModel();
        $addonsmodel = new SysAddonsModel();
        $module_infoId = $module->getInfo(['method'=>'bonusRecordList','module'=>'platform'],'module_id')['module_id'];
        $area_moduleId = $addonsmodel->getInfo(['name'=>'areabonus'],'module_id')['module_id'];
        $global_moduleId = $addonsmodel->getInfo(['name'=>'globalbonus'],'module_id')['module_id'];
        $team_moduleId = $addonsmodel->getInfo(['name'=>'teambonus'],'module_id')['module_id'];
        $order_ids = explode(',',$system_auth['order_id']);
        $order = new VslIncreMentOrderModel();
        $module_id_arrays = ',';
        $shop_module_id_arrays=',';
        $default_module_id_array = explode(',',$auth['module_id_array']);
        $default_shop_module_id_array = explode(',',$auth['shop_module_id_array']);
        foreach ($order_ids as $value){
            $addons_id = $order->getInfo(['order_id'=>$value],'*');
            $module_id = $module->Query(['addons_sign'=>$addons_id['addons_id'],'module'=>'platform'],'module_id');
            $shop_module_id = $module->Query(['addons_sign'=>$addons_id['addons_id'],'module'=>'admin'],'module_id');
            if(in_array($global_moduleId,$module_id) || in_array($area_moduleId,$module_id) || in_array($team_moduleId,$module_id)){
                $bonus_id[] = $module_infoId;
            }
            if($addons_id['expire_time']>time()){
                $module_id_array = implode(',',$module_id);
                $shop_module_id_array = implode(',',$shop_module_id);
                $module_id_arrays .= ','.$module_id_array;
                $shop_module_id_arrays.= $shop_module_id_array;
            }else{
                if(in_array($global_moduleId,$module_id) || in_array($area_moduleId,$module_id) || in_array($team_moduleId,$module_id)){
                    $unbonus_id[] = $module_infoId;
                }
                $default_module_id_array = array_diff($default_module_id_array,$module_id);
                $default_shop_module_id_array= array_diff($default_shop_module_id_array,$shop_module_id);
            }
        }
        $auth['module_id_array'] = implode(',',$default_module_id_array).$module_id_arrays;
        $auth['shop_module_id_array'] = implode(',',$default_shop_module_id_array).$shop_module_id_arrays;
        if(count($bonus_id)==count($unbonus_id) && $bonus_id){
            $unid = [];
            $real_module_id_array = explode(',',$auth['module_id_array']);
            $unid[] = $bonus_id[0];
            $auth['module_id_array'] = implode(',',array_diff($real_module_id_array,$unid));
        }
    }
    Session::set('addons_sign_module', []);
    $user = new Users();
    $no_control = $user->getNoControlAuth();
    $module = new ModuleModel();
    $addons = new SysAddonsModel();
    $addons_sign_module = '';
    $up_status_ids = $addons->Query(['up_status'=>2],'id');
    if($up_status_ids){
        foreach ($up_status_ids as $v){
            $addons_sign_module .= ','.implode(',',$module->Query(['addons_sign' => $v],'module_id'));
        }
        if($addons_sign_module){
            $addons_sign_modules = explode(',',$addons_sign_module);
            foreach($addons_sign_modules as $k=>$v){
                if( !$v )
                    unset($addons_sign_modules[$k] );
            }
            Session::set('addons_sign_module', $addons_sign_modules);
        }
    }
    Session::set($model.'module_id_array', $no_control.$auth['module_id_array']);
    Session::set($model.'shop_module_id_array', $no_control.$auth['shop_module_id_array']);
    Session::set('module_list', []);
    Session::set($model.'module_list', []);
    $retval = VslDelDir('./runtime/cache');
    return $retval;
}
/**
 * 去除URL中'http://' 或 'https://'
 * eg: https://www.baidu.com/aa/bb ==> www.baidu.com/aa/bb
 * eg: https://www.baidu.com/aa/bb ==> www.baidu.com
 * @param $url string [URL]
 * @param bool $get_domain boolean [是否只获取域名部分]
 * @return string
 */
function removeUrlHttp($url, $get_domain = false) {
    $domain_arr = parse_url($url);
    if (isset($domain_arr['scheme'])) {//含有 https或http
        $result = $get_domain ? $domain_arr['host'] : substr($url, strlen($domain_arr['scheme'].'://'));
    } else {
        if ($get_domain) {//自己拼接http并获取域名
            $create = 'https://'.$url;
            $domain_arr = parse_url($create);
            $result = $domain_arr['host'];
        } else {
            $result = $url;
        }
    }
    return $result;
}

function is_utf8($str){
    $len = strlen($str);
    for($i = 0; $i < $len; $i++){ $c = ord($str[$i]); if($c > 128){
        if(($c > 247)){
        return false;
        }elseif($c > 239){
        $bytes = 4;
        }elseif($c > 223){
        $bytes = 3;
        }elseif ($c > 191){
        $bytes = 2;
        }else{
        return false;
        }
        if(($i + $bytes) > $len){
        return false;
        }
        while($bytes > 1){
           $i++;
           $b = ord($str[$i]);
            if($b < 128 || $b > 191){
            return false;
            }
           $bytes--;
        }
      }
    }
    return true;
}
function is_base64($str){
    // 过滤html标签
    $str = strip_tags($str) ;
    if(@preg_match('/^[0-9]*$/',$str) || @preg_match('/^[a-zA-Z]*$/',$str)){
    return false;
    }elseif(is_utf8(base64_decode($str)) && base64_decode($str) != ''){
    return true;
    }
    return false;
}
/**
 * 提取url中文件名
 * @param $url string [url]
 * @param bool $ext bool [是否保留后缀]
 * @return string $file_name [文件名]
 */
function getFileNameOfUrl($url, $ext = false){
    $fileName = basename($url);
    if ($ext) {
        return $fileName;
    }
    return str_replace(strrchr($fileName, "."),"",$fileName);
}
/**
 * 分离数组中重复的键值对
 * @param $array
 * @return array [包含原数组的重复键值对和非重复键值对]
 */
function arraySeparateRepeat($array) {
    $unique_arr = array_unique($array);//去重
    //判断是否有重复值(相等没有重复)
    $n_repeat_arr = [];
    $repeat_arr = [];
    if (count($array) == count($unique_arr)) {
        $n_repeat_arr = $array;
    }
    // 获取重复数据的数组
    $repeatArr = array_diff_assoc($array, $unique_arr);
    foreach ($array as $k => $v) {
        if (in_array($v, $repeatArr)) {//重复键值对
            $repeat_arr[$k] = $v;
        } else {
            $n_repeat_arr[$k] = $v;
        }
    }
    return [
        'n_repeat' => $n_repeat_arr,//非重复键值对
        'repeat' => $repeat_arr,//重复键值对
    ];
}
/**
 * base64解码
 * @param $str
 */
function is_base64_decode($str) {
    $rr = '';
    if(is_base64($str)){
        $decode = base64_decode($str);
        $decode = ltrim(rtrim($decode,'</p>'), '<p>');//去除左右<p></p>标签
        $search ="/<script[^>]*?>.*?<\/script>/si";
        $rr = preg_replace($search,' ',$decode);
        if (is_base64($rr)) {
            $rr = is_base64_decode($rr);
        }
    }

    return $rr;
}
/**
 * 测试 - 写入日志文件
 * @param $string string [写入的字符串或者数组]
 * @param string $desc [文件描述]
 * @param string $file_name [写入的文件名,如果填写的是数字表示testX.txt文件名]
 * @param $mode int [写入模式]
 */
function debugFile($string, $desc = '', $file_name = 'public/test.txt', $mode = FILE_APPEND)
{
    if (is_numeric($file_name)){
        $file_name = 'public/test'.$file_name.'.txt';
    }
    if (is_dir($file_name)) {return;}
    $date = date("Y-m-d H:i:s", time());
    @file_put_contents($file_name, $date.' '.$desc. '=> ' .json_encode($string,JSON_UNESCAPED_UNICODE).PHP_EOL, $mode);
}
/**
 * 图片转为pdf
 * @param $img string [图片绝对地址]
 * @param string $new_img string [pdf绝对地址]
 * @param string $type string [生成pdf后什么类型返回：
 * I:将文件内联发送到浏览器 D:返回并下载 F：保存本地 S:以字符串形式返回文档]
 * @throws \Mpdf\MpdfException
 */
function img2Pdf($img, $new_img = '', $type = 'S')
{
    require 'vendor/tecnickcom/tcpdf/tcpdf.php';
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, [200, 200]);
    $pdf->Open();
    $pdf->AddPage();
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP,PDF_MARGIN_RIGHT);
    $pdf->Image($img);
    $pdf->Output($new_img, $type);
}
/**
 * pdf转图片
 * @param $pdf string [pdf格式]
 * @param string $path string [存储路径]
 * @param string $name string [文件名]
 * @return string
 */
function pdf2Img($pdf, $path = '', $name = ''){
    if (!$path) {
        $path = 'upload/temp/';
    }
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        chmod($path, 0777);
    }
    $name = $name ?: microtime(true);
    try {
        $im = new \Imagick();
        $im->setCompressionQuality(100);
        $im->setResolution(120, 120);//设置分辨率 值越大分辨率越高
        $im->readImage($pdf);
        $canvas = new \Imagick();
        $imgNum = $im->getNumberImages();
        //$canvas->setResolution(120, 120);
        foreach ($im as $k => $sub) {
            $sub->setImageFormat('png');
            //$sub->setResolution(120, 120);
            $sub->stripImage();
            $sub->trimImage(0);
            $width  = $sub->getImageWidth() + 10;
            $height = $sub->getImageHeight() + 10;
            if ($k + 1 == $imgNum) {
                $height += 10;
            } //最后添加10的height
            $canvas->newImage($width, $height, new \ImagickPixel('white'));
            $canvas->compositeImage($sub, \Imagick::COMPOSITE_DEFAULT, 5, 5);
        }
        $canvas->resetIterator();
        $canvas->appendImages(true)->writeImage($filename = $path . $name.'.png');
        return $filename;
    } catch (\Exception $e) {
        debugLog($e->getMessage()) ;
    }
}
/*
 * 获取万位的数字
 * **/
function getTenthousand($num)
{
    if(strlen($num) > 4){
        return ( $num / 10000 ).'万';
    }else{
        return $num;
    }
}
/**
 * 是否来自小程序请求 - 自定义
 * @return bool
 */
function isFromMp(){
    if (Request::instance()->header('website-id')) {
        return true;
    }
    return false;
}

/*
     * 可以发送get和post的请求方式
     * **/
function curlRequest($url, $method, $headers=[], $params=''){
    if (is_array($params)) {
        $requestString = http_build_query($params);
    } else {
        $requestString = $params ? : '';
    }
    if (empty($headers)) {
        $headers = array('Content-type: text/json');
    } elseif (!is_array($headers)) {
        parse_str($headers,$headers);
    }
    // setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    // setting the POST FIELD to curl
    switch ($method){
        case "GET" :
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            break;
        case "POST":
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
            break;
        case "PUT" :
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
            break;
        case "DELETE":
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
            break;
    }
    // getting response from server
    $response = curl_exec($ch);
    //close the connection
    curl_close($ch);

    //return the response
    if (stristr($response, 'HTTP 404') || $response == '') {
        return array('Error' => '请求错误');
    }
    return $response;
}

/**
 * 后台登录用户是否有该应用权限
 * @param $addons
 * @param $website_id
 * @param int $shop_id
 * @return bool|int|mixed
 */
function isExistAddons($addons, $website_id, $shop_id = 0)
{
    $model = request()->module();
    $module_id_array = Session::get($model . 'module_id_array');
    if (!$module_id_array) {
        return getAddons($addons, $website_id, $shop_id);
    }
    $addonsModel = new SysAddonsModel();
    $addonInfo = $addonsModel->getInfo(['name' => $addons], 'module_id');
    if (!$addonInfo) { return false; }
    return !(strpos($module_id_array, (string)$addonInfo['module_id']) === FALSE);

}
function setAddons($addons, $website_id, $shop_id = 0, $ignore_is_use = false, $set = true)
{
    if(!$website_id || !$addons){
        return false;
    }
    if($set && !$shop_id){
        $websiteUpdate = new \data\model\SysWebsiteUpdateModel();
        $websiteUpdate->save(['need_update' => 1, 'modify_time' => time()],['website_id' => $website_id]);
    }
    $websiteSer = new WebSite();
    $dateArr = $websiteSer->getWebCreateTime($website_id);
    $path = ROOT_PATH . '/public/addons_status/' . $dateArr['year'].'/'.$dateArr['month'].'/'.$dateArr['day'].'/'. $website_id;
    if (!is_dir($path)) {
        $mode = intval('0755', 8);
        mkdir($path, $mode, true);
        $arrContent = [];
    }else{
        $value = @file_get_contents($path.'/addons_' . $shop_id);
        if(!$value){
            $arrContent = [];
        }else{
            $arrContent = iunserializer($value);
        }
    }
    $key = $addons;
    if($ignore_is_use){
        $key = $addons . '-ignore';
    }
    if(!file_exists(ADDON_PATH . $addons)){//文件夹不存在
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    $website = new WebSiteModel();
    $version = $website->getInfo(['website_id' => $website_id], 'merchant_versionid')['merchant_versionid'];//查询商家是否有版本
    if (!$version) {//商家没有指定版本
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    $versionConfig = new MerchantVersionModel();
    $type_module_array = $versionConfig->getInfo(['merchant_versionid' => $version], 'type_module_array')['type_module_array'];
    if (!$type_module_array) {//版本没有权限
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    $module_array = explode(',', $type_module_array);
    $auth_group = new AuthGroupModel();
    $system_auth = $auth_group->getInfo(['is_system'=>1,'instance_id'=>0,'website_id'=>$website_id],'order_id');
    if($system_auth['order_id']){//查询购买的增值应用
        $order_ids = explode(',',$system_auth['order_id']);
        $order = new VslIncreMentOrderModel();
        $module = new  ModuleModel();
        foreach($order_ids as $k=>$v){
            $addons_id = $order->getInfo(['order_id'=>$v,'expire_time'=>['>',time()]],'addons_id')['addons_id'];
            if($addons_id){
                $module_ids = $module->Query(['addons_sign'=>$addons_id],'module_id');//根据购买的增值应用查询应用菜单id，与已有权限id合并
                $module_array = array_merge($module_array,$module_ids);
            }
        }
    }
    $addon = new sysAddonsModel();
    $addonInfo = $addon->getInfo(['name' => $addons], 'status,up_status,module_id,admin_module_id,no_set');
    if (!$addonInfo) {//查询应用是否安装
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    if ($addonInfo['up_status']==2) {//应用即将上线
        $arrContent[$key] = 0;
        file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
        return 0;
    }
    $config = new AddonsConfigModel();
    $configInfo = $config->getInfo([
        'website_id' => $website_id,
        'addons' => $addons
    ]);//应用设置
    if($shop_id>0){//店铺端权限
        $instance = new InstanceModel();
        $shop_type_module_array = $instance->alias('si')
        ->join('sys_instance_type sit', 'sit.instance_typeid = si.instance_typeid', 'left')
        ->where(['si.instance_id'=>$shop_id,'si.website_id'=>$website_id])->value('type_module_array');
        if (!$shop_type_module_array) {
            $arrContent[$key] = 0;
            file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));
            return 0;
        }
        $shop_module_array = explode(',', $shop_type_module_array);
    }
    //不需要开启的应用，默认开启
    if ($addonInfo['no_set'] || $ignore_is_use) {
        $configInfo['is_use'] = 1;
    }
    if (in_array($addonInfo['module_id'], $module_array) && $addonInfo['status'] == 1  && $addonInfo['up_status'] != 2 && $configInfo['is_use'] == 1) {
        $status = 1;
        if($shop_id>0 && !in_array($addonInfo['admin_module_id'], $shop_module_array)){
            $status = 0;
        }
    } else {
        $status = 0;
    }
    $arrContent[$key] = $status;
    file_put_contents($path.'/addons_' . $shop_id, iserializer($arrContent));

    return $status;
}