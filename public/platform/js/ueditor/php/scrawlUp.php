<?php
//zend by 注:本程序仅适用于学习与研究使用  禁止倒卖 一经发现停止任何服务!
function delDir($dir)
{
	$dh = opendir($dir);

	while ($file = readdir($dh)) {
		if (($file != '.') && ($file != '..')) {
			$fullpath = $dir . '/' . $file;

			if (!is_dir($fullpath)) {
				unlink($fullpath);
			}
			else {
				delDir($fullpath);
			}
		}
	}

	closedir($dh);
	return rmdir($dir);
}

require 'config.php';

if (!$enable) {
	exit('{\'url\':\'\',state:\'没有涂鸦权限\'}');
}

$config = array(
	'savePath'   => $root_path_relative . IMAGE_DIR . '/upload/',
	'maxSize'    => 1000,
	'allowFiles' => array('.gif', '.png', '.jpg', '.jpeg', '.bmp')
	);
$tmpPath = 'tmp/';
$action = htmlspecialchars($_GET['action']);

if ($action == 'tmpImg') {
	$config['savePath'] = $tmpPath;
	$up = new Uploader('upfile', $config);
	$info = $up->getFileInfo();
	echo '<script>parent.ue_callback(\'' . $info['url'] . '\',\'' . $info['state'] . '\')</script>';
}
else {
	$up = new Uploader('content', $config, true);

	if (file_exists($tmpPath)) {
		delDir($tmpPath);
	}

	$info = $up->getFileInfo();
	$info['url'] = str_replace($root_path_relative, $root_path, $info['url']);
	echo '{\'url\':\'' . $info['url'] . '\',state:\'' . $info['state'] . '\'}';
}

?>
