<?php

/**
 * install.php
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 * 
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================

 */
define('IN_IA', true);
error_reporting(0);
@set_time_limit(0);
//@set_magic_quotes_runtime(0);
ob_start();
define('IA_ROOT', str_replace("\\", '/', dirname(__FILE__)));
$ispost = strtolower($_SERVER['REQUEST_METHOD']) == 'post';
if (file_exists(IA_ROOT . '/installed.lock')) {
    header('location: ./platform');
    exit;
}
$version = include ( __DIR__ . '/version.php');
if (!file_exists(__DIR__ . '/version.php')) {
    die('配置文件丢失，请恢复源码');
}
if (!isset($version['secret_key']) || empty($version['secret_key'])) {
    require __DIR__ . '/thinkphp/sourceAuthorization.html';
    exit;
}
header('content-type: text/html; charset=utf-8');
if ($ispost && !$_POST['action']) {
    $db['server'] = $_POST['server'];
    $db['username'] = $_POST['username'];
    $db['password'] = $_POST['password'];
    $db['name'] = $_POST['name'];
    $user['password'] = $_POST['user_password'];
    $user['username'] = $_POST['user_username'];
    //  针对php7版本数据库安装
    if (strstr(PHP_VERSION, '7.')) {
        $link = mysqli_connect($db['server'], $db['username'], $db['password']);

        if (!$link) {
            $error = mysqli_connect_error();
            if (strpos($error, 'Access denied for user') !== false) {
                $error = '您的数据库访问用户名或是密码错误. <br />';
            } else {
                $error = iconv('gbk', 'utf8', $error);
            }
        } else {
            mysqli_query($link, "SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
            mysqli_query($link, "SET sql_mode=''");
            if (mysqli_errno($link)) {
                $error = mysqli_error($link);
            } else {
                $query = mysqli_query($link, "SHOW DATABASES LIKE  '{$db['name']}';");
                if (!mysqli_fetch_assoc($query)) {
                    if (mysqli_get_server_info() > '4.1') {
                        mysqli_query($link, "CREATE DATABASE IF NOT EXISTS `{$db['name']}` DEFAULT CHARACTER SET utf8");
                    } else {
                        mysqli_query($link, "CREATE DATABASE IF NOT EXISTS `{$db['name']}`");
                    }
                }
                $query = mysqli_query($link, "SHOW DATABASES LIKE  '{$db['name']}';");
                if (!mysqli_fetch_assoc($query)) {
                    $error .= "数据库不存在且创建数据库失败. <br />";
                }
                if (mysqli_errno($link)) {
                    $error .= mysqli_error($link);
                }
            }
        }
        if (empty($error)) {
            mysqli_select_db($link, $db['name']);
            $query = mysqli_query($link, "SHOW TABLES LIKE 'sys%';");
            if (mysqli_fetch_assoc($query)) {
                echo json_encode(['code' => -1, 'message' => '您的数据库不为空，请重新建立数据库或是清空该数据库！']);
                exit();
            }
            $query2 = mysqli_query($link, "SHOW TABLES LIKE 'vsl%';");
            if (mysqli_fetch_assoc($query2)) {
                echo json_encode(['code' => -1, 'message' => '您的数据库不为空，请重新建立数据库或是清空该数据库！']);
                exit();
            }
        }
        if (empty($error)) {
            $pieces = explode(':', $db['server']);
            $db['port'] = !empty($pieces[1]) ? $pieces[1] : '3306';
            $config = db_config();
            $cookiepre = local_salt(4) . '_';
            $authkey = local_salt(8);
            $config = str_replace(array(
                '{db-server}', '{db-username}', '{db-password}', '{db-port}', '{db-name}'
                    ), array(
                $db['server'], $db['username'], $db['password'], $db['port'], $db['name']
                    ), $config);
            mysqli_close($link);




            //循环添加数据
            if (file_exists(IA_ROOT . '/vslaishop_ptb.sql')) {

                $link = mysqli_connect($db['server'], $db['username'], $db['password'], $db['name'], $db['port']);
                mysqli_query($link, "SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
                mysqli_query($link, "SET sql_mode=''");
                if (!$link) {
                    die('<script type="text/javascript">alert("连接不到数据库, 请稍后重试！");history.back();</script>');
                }
                $sql = file_get_contents(IA_ROOT . '/vslaishop_ptb.sql');
                $sql = str_replace("\r", "\n", $sql);
                $sql = explode(";\n", $sql);
                foreach ($sql as $k => $item) {

                    $item = trim($item);
                    if (empty($item))
                        continue;

                    preg_match('/CREATE TABLE `([^ ]*)`/', $item, $matches);


                    if ($matches) {
                        mysqli_select_db($link, $db['name']);
                        $table_name = $matches[1];

                        $result = mysqli_query($link, $item);
                    } else {

                        mysqli_select_db($link, $db['name']);
                        $result = mysqli_query($link, $item);
                        //$db->execute($item);
                    }
                }
            } else {
                echo json_encode(['code' => -1, 'message' => '安装包不正确, 数据安装脚本缺失.']);
                exit();
            }
            //删除商品sku错误信息 和 商品属性错误信息
            mysqli_query($link, "DELETE FROM vsl_goods_sku WHERE goods_id NOT IN (SELECT goods_id FROM vsl_goods)");
            mysqli_query($link, "DELETE FROM vsl_goods_attribute WHERE goods_id NOT IN ( SELECT goods_id FROM vsl_goods)");
            $group_string = "";
            $group_string_platform = ""; //商家端权限列表
            $result = mysqli_query($link, "SELECT * FROM sys_module where module='admin'");
            while ($row = mysqli_fetch_array($result)) {
                $group_string .= "," . $row["module_id"];
            }
            if ($group_string != '') {
                $group_string = substr($group_string, 1);
            }
            $result_platform = mysqli_query($link, "SELECT * FROM sys_module where module='platform'");
            while ($row_platform = mysqli_fetch_array($result_platform)) {
                $group_string_platform .= "," . $row_platform["module_id"];
            }
            if ($group_string_platform != '') {
                $group_string_platform = substr($group_string_platform, 1);
            }
            //添加用户管理员
            $password = md5($user['password']);
            $datetime = time();
            $insert_mer = mysqli_query($link, "INSERT INTO sys_merchant_version (type_name, type_module_array, create_time, shop_type_module_array, is_default) 
                        VALUES('{$version['version_name']}', '{$group_string_platform}','" . $datetime . "', '{$group_string}','0')"); //添加版本
            if (!$insert_mer) {
                echo json_encode(['code' => -1, 'message' => '管理员账户注册失败.']);
                exit();
            }
            $insert_mer_id = mysqli_insert_id($link);
            $insert_web = mysqli_query($link, "INSERT INTO sys_website (merchant_versionid, title, create_time,realm_ip) 
			VALUES('{$insert_mer_id}', '网站标题','" . $datetime . "','".$_SERVER['HTTP_HOST']."')"); //添加商家
            if (!$insert_web) {
                echo json_encode(['code' => -1, 'message' => '管理员账户注册失败.']);
                exit();
            }
            $insert_web_id = mysqli_insert_id($link);
            $member_level_insert = mysqli_query($link, "INSERT INTO vsl_member_level (level_name, is_default, create_time, website_id) 
    			VALUES('默认等级', 1, '" . $datetime . "', '{$insert_web_id}')");
            $insert_error = mysqli_query($link, "INSERT INTO sys_user (user_name, user_tel, user_password, is_system, is_member, reg_time, nick_name, port, website_id) 
    			VALUES('{$user['username']}', '{$user['username']}', '{$password}', '1', '0','" . $datetime . "','{$user['username']}','platform', '{$insert_web_id}')");
            
            if ($insert_error) {
                $insert_id = mysqli_insert_id($link);
                $update_web = mysqli_query($link, "UPDATE sys_website SET uid = '{$insert_id}' where website_id = '{$insert_web_id}'");
                //添加店铺
                $shop_insert = mysqli_query($link, "INSERT INTO vsl_shop (shop_id, uid, shop_name, shop_create_time, shop_state, website_id) 
    			VALUES(0, '{$insert_id}', '自营店', '" . $datetime . "',1, '{$insert_web_id}')");
                //添加管理员用户组
                $group_list = array();

                $group_error = mysqli_query($link, "INSERT INTO sys_user_group (group_name,instance_id, is_system, module_id_array,shop_module_id_array, create_time,website_id)
			        VALUES('管理员组','0', '1','{$group_string_platform}','{$group_string}','" . $datetime . "','{$insert_web_id}')");
                if ($group_error) {
                    $group_insert_id = mysqli_insert_id($link);
                    //给用户添加管理员权限
                    mysqli_query($link, "INSERT INTO sys_user_admin (uid, admin_name, group_id_array, is_admin, admin_status,website_id)
			        VALUES('{$insert_id}', '管理员','{$group_insert_id}', '1', '1','{$insert_web_id}')");
                } else {
                    echo json_encode(['code' => -1, 'message' => '管理员账户注册失败.']);
                    exit();
                }
            }
        } else {
            echo json_encode(['code' => -1, 'message' => $error]);
            exit();
        }
    } else {
        $link = mysql_connect($db['server'], $db['username'], $db['password']);

        if (empty($link)) {
            $error = mysql_error();

            if (strpos($error, 'Access denied for user') !== false) {
                $error = '您的数据库访问用户名或是密码错误';
            } else {
                $error = iconv('gbk', 'utf8', $error);
            }
        } else {
            mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
            mysql_query("SET sql_mode=''");
            if (mysql_errno()) {
                $error = mysql_error();
            } else {
                $query = mysql_query("SHOW DATABASES LIKE  '{$db['name']}';");
//     				var_dump($query);

                if (!mysql_fetch_assoc($query)) {
                    if (mysql_get_server_info() > '4.1') {
                        mysql_query("CREATE DATABASE IF NOT EXISTS `{$db['name']}` DEFAULT CHARACTER SET utf8", $link);
                    } else {
                        mysql_query("CREATE DATABASE IF NOT EXISTS `{$db['name']}`", $link);
                    }
                }

                $query = mysql_query("SHOW DATABASES LIKE  '{$db['name']}';");
                if (!mysql_fetch_assoc($query)) {
                    $error .= "数据库不存在且创建数据库失败";
                }
                if (mysql_errno()) {
                    $error .= mysql_error();
                }
            }
        }
        if (empty($error)) {
            mysql_select_db($db['name']);
            $query = mysql_query("SHOW TABLES LIKE 'vsl%';");
            if (mysql_fetch_assoc($query)) {
                echo json_encode(['code' => -1, 'message' => '您的数据库不为空，请重新建立数据库或是清空该数据库.']);
                exit();
            }
            $query2 = mysql_query("SHOW TABLES LIKE 'sys%';");
            if (mysql_fetch_assoc($query2)) {
                echo json_encode(['code' => -1, 'message' => '您的数据库不为空，请重新建立数据库或是清空该数据库.']);
                exit();
            }
        }
        if (empty($error)) {
            $pieces = explode(':', $db['server']);
            $db['port'] = !empty($pieces[1]) ? $pieces[1] : '3306';
            $config = db_config();
            $cookiepre = local_salt(4) . '_';
            $authkey = local_salt(8);
            $config = str_replace(array(
                '{db-server}', '{db-username}', '{db-password}', '{db-port}', '{db-name}'
                    ), array(
                $db['server'], $db['username'], $db['password'], $db['port'], $db['name']
                    ), $config);


            mysql_close($link);


            $link = mysql_connect($db['server'], $db['username'], $db['password']);
            if (!$link) {
                echo json_encode(['code' => -1, 'message' => '连接不到服务器, 请稍后重试！']);
                exit();
            }
            $mysql_db = mysql_select_db($db['name']);
            if (!$mysql_db) {
                echo json_encode(['code' => -1, 'message' => '连接不到服务器, 请稍后重试！']);
                exit();
            }
            mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
            mysql_query("SET sql_mode=''");



            //循环添加数据
            if (file_exists(IA_ROOT . '/vslaishop_ptb.sql')) {
                $sql = file_get_contents(IA_ROOT . '/vslaishop_ptb.sql');
                $sql = str_replace("\r", "\n", $sql);
                $sql = explode(";\n", $sql);

                foreach ($sql as $item) {
                    $item = trim($item);
                    if (empty($item))
                        continue;
                    preg_match('/CREATE TABLE `([^ ]*)`/', $item, $matches);
                    if ($matches) {
                        $table_name = $matches[1];
                        mysql_query($item, $link);
                    } else {
                        mysql_close($link);
                        $link = mysql_connect($db['server'], $db['username'], $db['password']);
                        mysql_select_db($db['name']);
                        mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
                        mysql_query("SET sql_mode=''");
                        mysql_query($item, $link);
                        //$db->execute($item);
                    }
                }
            } else {
                echo json_encode(['code' => -1, 'message' => '安装包不正确, 数据安装脚本缺失！']);
                exit();
            }

            //添加用户管理员
            mysql_close($link);
            $link = mysql_connect($db['server'], $db['username'], $db['password']);
            mysql_select_db($db['name']);
            mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
            mysql_query("SET sql_mode=''");
            //删除商品sku错误信息 和 商品属性错误信息
            mysql_query("DELETE FROM vsl_goods_sku WHERE goods_id NOT IN (SELECT goods_id FROM vsl_goods)");
            mysql_query("DELETE FROM vsl_goods_attribute WHERE goods_id NOT IN ( SELECT goods_id FROM vsl_goods)");
            $group_string = ""; //店铺端权限列表
            $group_string_platform = ""; //商家端权限列表
            $result = mysql_query("SELECT * FROM sys_module where module='admin'", $link);
            while ($row = mysql_fetch_array($result)) {
                $group_string .= "," . $row["module_id"];
            }
            if ($group_string != '') {
                $group_string = substr($group_string, 1);
            }
            $result_platform = mysql_query("SELECT * FROM sys_module where module='platform'", $link);
            while ($row_platform = mysql_fetch_array($result_platform)) {
                $group_string_platform .= "," . $row_platform["module_id"];
            }
            if ($group_string_platform != '') {
                $group_string_platform = substr($group_string_platform, 1);
            }
            $password = md5($user['password']);
            $datetime = time();
            $insert_mer = mysql_query("INSERT INTO sys_merchant_version (type_name, type_module_array, create_time, shop_type_module_array, is_default) 
			VALUES('{$version['version_name']}', '{$group_string_platform}','" . $datetime . "', '{$group_string}','0')"); //添加版本
            if (!$insert_mer) {
                echo json_encode(['code' => -1, 'message' => '管理员账户注册失败']);
                exit();
            }
            $insert_mer_id = mysql_insert_id();
            $insert_web = mysql_query("INSERT INTO sys_website (merchant_versionid, title, create_time,realm_ip) 
			VALUES('{$insert_mer_id}', '网站标题','" . $datetime . "','{$_SERVER['HTTP_HOST']}')"); //添加商家
            if (!$insert_web) {
                echo json_encode(['code' => -1, 'message' => '管理员账户注册失败']);
                exit();
            }
            $insert_web_id = mysql_insert_id();
            $member_level_insert = mysql_query("INSERT INTO vsl_member_level (level_name, is_default, create_time, website_id) 
    			VALUES('默认等级', 1, '" . $datetime . "', '{$insert_web_id}')");
            $insert_error = mysql_query("INSERT INTO sys_user (user_name, user_tel, user_password, is_system, is_member, reg_time, nick_name, port, website_id) 
			VALUES('{$user['username']}', '{$user['username']}', '{$password}', '1', '0','" . $datetime . "', '{$user['username']}','platform', '{$insert_web_id}')"); //添加用户
            if ($insert_error) {
                $insert_id = mysql_insert_id();
                $update_web = mysql_query("UPDATE sys_website SET uid = '{$insert_id}' where website_id = '{$insert_web_id}'", $link);
                //添加店铺
                $shop_insert = mysql_query("INSERT INTO vsl_shop (shop_id, uid, shop_name, shop_create_time, shop_state, website_id) 
    			VALUES(0, '{$insert_id}', '自营店', '" . $datetime . "',1, '{$insert_web_id}')");                
                //添加管理员用户组
                $group_list = array();

                $group_error = mysql_query("INSERT INTO sys_user_group (group_name,instance_id, is_system, module_id_array, shop_module_id_array, create_time,website_id)
			        VALUES('管理员组','0', '1','{$group_string_platform}','{$group_string}','" . $datetime . "','{$insert_web_id}')", $link);
                if ($group_error) {
                    $group_insert_id = mysql_insert_id();
                    //给用户添加管理员权限
                    mysql_query("INSERT INTO sys_user_admin (uid, admin_name, group_id_array, is_admin, admin_status,website_id)
			        VALUES('{$insert_id}', '管理员','{$group_insert_id}', '1', '1','{$insert_web_id}')", $link);
                } else {
                    echo json_encode(['code' => -1, 'message' => '管理员账户注册失败']);
                    exit();
                }
            } else {
                echo json_encode(['code' => -1, 'message' => '管理员账户注册失败']);
                exit();
            }
        } else {
            echo json_encode(['code' => -1, 'message' => $error]);
            exit();
        }
    }
    //配置数据库
    file_put_contents(IA_ROOT . '/application/database.php', $config);
    touch(IA_ROOT . '/installed.lock');
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    echo json_encode(['code' => 1, 'message' => '安装成功', 'url' => $http_type . $_SERVER['HTTP_HOST'] . '/platform/login']);
    exit();
}
tpl_frame();

function local_salt($length = 8) {
    $result = '';
    while (strlen($result) < $length) {
        $result .= sha1(uniqid('', true));
    }
    return substr($result, 0, $length);
}

function db_config() {
    $cfg = <<<EOF
<?php
/**
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

return [
// 数据库类型
'type'           => 'mysql',
// 服务器地址
'hostname'       => '{db-server}',
// 数据库名
'database'       => '{db-name}',
// 用户名
'username'       => '{db-username}',
// 密码
'password'       => '{db-password}',
// 端口
'hostport'       => '{db-port}',
// 连接dsn
'dsn'            => '',
// 数据库连接参数
'params'         => [],
// 数据库编码默认采用utf8
'charset'        => 'utf8',
// 数据库表前缀
'prefix'         => '',
// 数据库调试模式
'debug'          => true,
// 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
'deploy'         => 0,
// 数据库读写是否分离 主从式有效
'rw_separate'    => false,
// 读写分离后 主服务器数量
'master_num'     => 1,
// 指定从服务器序号
'slave_no'       => '',
// 是否严格检查字段是否存在
'fields_strict'  => true,
// 数据集返回类型 array 数组 collection Collection对象
'resultset_type' => 'array',
// 是否自动写入时间戳字段
'auto_timestamp' => false,
    // 是否需要进行SQL性能分析
'sql_explain'    => false,
];
    

EOF;
    return trim($cfg);
}

function local_mkdirs($path) {
    if (!is_dir($path)) {
        local_mkdirs(dirname($path));
        mkdir($path);
    }
    return is_dir($path);
}

function tpl_frame() {
    $content = ob_get_contents();
    ob_clean();
    $tpl = <<<EOF
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>数据库安装</title>
    <style>
        body,
        div,
        dl,
        dt,
        dd,
        ul,
        ol,
        li,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        pre,
        code,
        form,
        fieldset,
        legend,
        input,
        button,
        textarea,
        p,
        blockquote,
        th,
        td {
            margin: 0;
            padding: 0;
        }
        body {
            background: #f5f5f5;
            color: #555;
            font-size: 12px;
            font-family: "Microsoft yahei";
            min-width: 1200px;
        }
        .login-box {
            width: 750px;
            height: 750px;
            background-color: #fff;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%,-50%);
        }
        .login-box .login-right {
            float: left;
            width: 750px;
            height: 600px;
            /*text-align: center;*/
        }
        .login-right .logos{
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .clearfix:before{
            display: table;
            content: " ";
        }
        .clearfix:after {
            visibility: hidden;
            display: block;
            font-size: 0;
            content: " ";
            clear: both;
            height: 0;
        }
        .control-label {
            float: left;
            width: 194px;
            text-align: right;
            line-height: 26px;
            color: #666;
            padding-right: 10px;
        }
        .control-group {
            margin-bottom: 20px;
        }
        .controls {
            position: relative;
            float: left;
        }
        .controls .text {
            width: 390px;
            height: 24px;
            padding: 3px 5px;
            border: 1px solid #bbb;
            line-height: 18px;
            border-radius: 4px;
        }
        .control-label em {
            color: #f00;
            font-style: normal;
        }
        .controls .tips{
            color: #999;
            margin-top: 4px;

        }
        .login-msg {
            width: 381px;
            margin-bottom: 20px;
            margin-left: 203px;
            border: 1px solid #ffb4a8;
            line-height: 16px;
            padding: 6px 10px;
            overflow: hidden;
            background: #fef2f2;
            color: #6C6C6C;
            border-radius: 4px;
        }
        .login-msg p {
            white-space: normal;
            word-wrap: break-word;
            width: 346px;
        }
        .ms-stand-btn1 {
            width: 80px;
            height: 30px;
            display: inline-block;
            background: #2c9cf0;
            border-radius: 4px;
            text-align: center;
            color: #fff;
            line-height: 30px;
            text-decoration: none;
        }
        .ml-10{
            margin-left: 10px;
        }
             /*消息提示*/
        .alert-message-dialog{
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            top: 4%;
            z-index: 100000000;
            min-width: 320px;
            max-width: 400px;
            margin: 0;
            text-align: center;
            -webkit-animation-duration: .5s;
            -webkit-animation-delay: .1s;
            -webkit-animation-timing-function: ease-out;
            -webkit-animation-fill-mode: both;
            -moz-animation-duration: .5s;
            -moz-animation-delay: .1s;
            -moz-animation-timing-function: ease-out;
            -moz-animation-fill-mode: both;
            -ms-animation-duration: .5s;
            -ms-animation-delay: .1s;
            -ms-animation-timing-function: ease-out;
            -ms-animation-fill-mode: both;
            animation-duration: .5s;
            animation-delay: .1s;
            animation-timing-function: ease-out;
            animation-fill-mode: both;
        }
        .alert-message-dialog .icon{
            font-size: 20px;
            padding-right: 4px;
            vertical-align: text-top;
        }
        .alert {
            padding-top: 10px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        .disabled {
            cursor: not-allowed;
            background:gray;
        }
            .updateTips{
  z-index: 10000000;
  position: fixed;
  left: 0;
  top: 0;
  background-color: rgba(0, 0, 0,.7);
  width: 100%;
  height: 100%;
}
.updateTips .updateTips-dia{
  width: 350px;
  height: 300px;
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%,-50%);
  text-align: center;
}
.updateTips .updateTips-dia-img{
    margin-bottom: 20px;
}
    </style>
</head>
<body>
<div class="login-box">
    <div class="login-right">
        <form method="post" role="form">
            <div class="logos"><img src="../../public/logo.png"  alt=""></div>

            <div class="login-msg" style="display: none;">
                <p class="error hint">请输入手机号</p>
            </div>

            <div class="control-group clearfix">
                <label class="control-label">
                    <em>*</em>数据库主机地址
                </label>
                <div class="controls">
                    <input id="dbserver" name="db[server]" type="text" class="text" value="" autocomplete="on">
                    <p class="tips">本地数据库为localhost，线上数据库则为IP地址</p>
                    <p class="tips2" style="display:none;">数据库主机地址</p>
                </div>
            </div>
            <div class="control-group clearfix">
                <label class="control-label">
                    <em>*</em>数据库用户名
                </label>
                <div class="controls">
                    <input id="dbusername" name="db[username]" type="text" class="text" value="" autocomplete="on">
                    <p class="tips">访问数据库的用户名</p>
                    <p class="tips2" style="display:none;">数据库用户名</p>
                </div>
            </div>
            <div class="control-group clearfix">
                <label class="control-label">
                    <em>*</em>数据库密码
                </label>
                <div class="controls">
                    <input id="dbpassword" name="db[password]" type="password" class="text" value="" autocomplete="off">
                    <p class="tips">访问数据库的密码</p>
                    <p class="tips2" style="display:none;">数据库密码</p>
                </div>
            </div>
            <div class="control-group clearfix">
                <label class="control-label">
                    <em>*</em>数据库名
                </label>
                <div class="controls">
                    <input id="dbname" name="db[name]" type="text" class="text"  value="" autocomplete="on">
                    <p class="tips">希望安装到的数据库名称</p>
                    <p class="tips2" style="display:none;">数据库名</p>
                </div>
            </div>
            <div class="control-group clearfix">
                <label class="control-label">
                    <em>*</em>管理员账号
                </label>
                <div class="controls">
                    <input id="usermobile" name="user[username]" type="text" class="text"  value="" autocomplete="on">
                    <p class="tips">登陆后台管理系统的账号</p>
                    <p class="tips2" style="display:none;">管理员账号</p>
                </div>
            </div>
            <div class="control-group clearfix">
                <label class="control-label">
                    <em>*</em>管理员密码
                </label>
                <div class="controls">
                    <input id="userpw" name="user[password]" type="password" class="text"  value="" autocomplete="off">
                    <p class="tips">登陆后台管理系统的密码</p>
                    <p class="tips2" style="display:none;">管理员密码</p>
                </div>
            </div>
            <div class="control-group clearfix">
                <label class="control-label">
                    <em>*</em>确认密码
                </label>
                <div class="controls">
                    <input id="userpw2" type="password" class="text"  value="" autocomplete="off">
                    <p class="tips">再次输入管理员密码</p>
                    <p class="tips2" style="display:none;">密码</p>
                </div>
            </div>

            <div class="control-group clearfix priority-low">
                <label class="control-label">&nbsp;</label>
                <div class="fl">
                    <a href="javascript:void(0);" class="ms-stand-btn1" onclick="check(this)">确定</a>
                    <a href="javascript:void(0);" class="ms-stand-btn1 ml-10">关闭</a>
                </div>

            </div>


        </form>
    </div>
</div>
<div  class="updateTips" style="display: none;">
	<div class="updateTips-dia">
		<img src="/public/platform/images/updateTips.svg" alt="" class="updateTips-dia-img">
		<div style="color: #fff">系统正在安装，请不要关闭或刷新页面。</div>
	</div>
</div>
</body>
<script  src="http://libs.baidu.com/jquery/2.1.1/jquery.min.js"></script>
<script>
            // 消息提示
function message(content,type,callback){
    type ? type : type = 'info'
    var messageHtml = '<div class="alert alert-'+type+' alert-message-dialog fadeInDown" id="msgHtml" role="alert"><i class="icon icon-'+type+'"></i>'+content+'</div>'
    $(document.body).append(messageHtml)
    setTimeout(function(){
        $("#msgHtml").removeClass('fadeInDown').addClass('fadeInOut')
        removeHtml()
    },1500)
    function removeHtml(){
        setTimeout(function(){
            $("#msgHtml").remove();
            var regex = /^http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- ./?%&=#]*)?$/;
            if(callback && typeof callback === "function") {
                callback();
            }else if(regex.test(callback)){
                window.location.href=callback;
            }
        },500)
    }
}
var lock = false;
function check(obj) {
    if(lock) {
            return;
    }
    var error = false;
    $('.text').each(function(){		
        if($(this).val() == '') {
            $(this).focus();
            $('.error').html('请填写'+$(this).next().next('.tips2').html());
            $('.login-msg').show();
            error = true;
            return false;
        }
    });
    if(error) {
        return false;
    }
    if($('#userpw').val() != $('#userpw2').val()) {
        $('#userpw2').focus();
        $('.error').html('两次密码不一致');
        $('.login-msg').show();
        return false;
    }
    lock = true;
    $(obj).addClass('disabled');
    $(obj).html('安装中');
            $('.updateTips').show();
    $.ajax({
        url: "install.php",
        type: 'post',
        dataType: "json",
        data: {'server':$("#dbserver").val(), 'password':$("#dbpassword").val(), 'username':$("#dbusername").val(),'name':$("#dbname").val(), 'user_password':$("#userpw").val(),'user_username':$("#usermobile").val()},
        success: function(res){
            if(res.code < 0){
                message(res.message,'danger');
                $(obj).removeClass('disabled');
                $(obj).html('确定');
                $('.updateTips').hide();
                lock = false;
                return false;
            }else{
                message(res.message,'success',function(){
                    lock = false;
                    location.href = res.url
                });
            }
         }
    });
}
window.onbeforeunload = function(e)
{
    if(lock){
         return e.returnValue='你真的要关闭吗？';
    }

};
</script>
</html>
EOF;
    echo trim($tpl);
}
