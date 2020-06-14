<?php
function file_tree($path) {
	$files = array();
	$ds = glob($path . '*');
	
	if (is_array($ds)) {
		foreach ($ds as $entry) {
			
			if (is_file($entry)) {
				
				if(strstr($entry,'/version.php') || strstr($entry,'application/database.php') || strstr($entry,'application/config.php') || strstr($entry,'/md5.php') || strstr($entry,'/map.json') || preg_match('#update(\d+).zip#',$entry) || preg_match('#backup(\d+).zip#',$entry)){
					continue;
				}
				$files[] = array('path'=>$entry,'checksum'=>md5_file($entry));				
			}
			if (is_dir($entry)) {
				if(strstr($entry,'upload') || strstr($entry,'runtime')){
					continue;
				}
				$rs = file_tree($entry.'/');
				foreach ($rs as $f) {
					$files[] = $f;
				}
			}
		}
	}
	return $files;
}
$path='./';
file_put_contents($path.'map.json',json_encode(file_tree($path)));
$path='./addons/';
file_put_contents($path.'map.json',json_encode(file_tree($path)));
echo 'map.json creat success';