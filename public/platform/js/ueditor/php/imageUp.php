<?php

//zend by 注:本程序仅适用于学习与研究使用  禁止倒卖 一经发现停止任何服务!
require 'config.php';

//if (!$enable) {
//	exit('{\'url\':\'\',\'title\':\'\',\'original\':\'\',\'state\':\'没有上传权限\'}');
//}

$config = array(
	'savePath'   => $root_path_relative  . 'images/upload/',
	'maxSize'    => 3000,
	'allowFiles' => array('.gif', '.png', '.jpg', '.jpeg', '.bmp'),
        'website_id' => intval($_COOKIE['website_id']),
        'shop_id' => intval($_COOKIE['instance_id'])
	);

$title = htmlspecialchars($_POST['pictitle'], ENT_QUOTES);


if (isset($_GET['fetch'])) {
	header('Content-Type: text/javascript');
	echo 'updateSavePath(["upload"]);';
	return NULL;
}
$up = new Uploader('upfile', $config);
$info = $up->getFileInfo();
$info['url'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $info['url']);
if ($info['url'] && (substr($info['url'], 0, 1) != '/')) {
	$info['url'] = '/' . $info['url'];
}

echo '{\'url\':\'' . $info['url'] . '\',\'title\':\'' . $title . '\',\'original\':\'' . $info['originalName'] . '\',\'state\':\'' . $info['state'] . '\'}';

?>
