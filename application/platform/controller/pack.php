<?php
$paths = htmlspecialchars_decode($_POST['paths']);
$content = htmlspecialchars_decode($_POST['content']);
$paths = json_decode($paths);
$do=$_GET['op'] ? $_GET['op'] : 'backup';
if($do == 'backup'){
	$filename = '../../../'."backup".time().".zip";
	if(empty($paths)){
		$data['code'] = 1;
		exit(json_encode($data));
	}
	backup($paths,$filename);
	if(!empty($content)){
		$filename = '../../../'."update".time().".zip";
		file_put_contents($filename,$content);
		if(!file_exists($filename)){
			$data['code'] = -1;
			$data['message'] = '文件目录没有读写权限';
			$data['data'] = '';
			exit(json_encode($data));
		}
	}
	$data['data']=str_replace('../../../','',$filename);
	exit(json_encode($data));
}
if($do == 'getzip'){
	$filename = '../../../'."update".time().".zip";
	backup($paths,$filename);
	if(!file_exists($filename)){
		$data['code'] = -1;
		$data['message'] = '文件目录没有读写权限';
		$data['data'] = '';
		exit(json_encode($data));
	}else{
		$data['data']=str_replace('../../../','',$filename);
		exit(json_encode($data));
	}
}
function backup($paths,$filename){
	if(!$paths){ 
		$data['code'] = -1;
		$data['message'] = '未知错误';
		$data['data'] = '';
		exit(json_encode($data));
	}
	if(!class_exists('ZipArchive')){
		$data['code'] = -13;
		$data['message'] = '缺少php_zip扩展，不支持ZipArchive类';
		$data['data'] = '';
		exit(json_encode($data));
	}
	$zip = new ZipArchive();	
	if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {  
		$data['code'] = -1;
		$data['message'] = '文件目录没有读写权限';
		$data['data'] = '';
		exit(json_encode($data));
	}
	$obj=$_GET['obj'] ? $_GET['obj'] : '';
	if(empty($obj)){
		$webfiles=file_tree('../../../wap/');
		foreach($webfiles as $file){
			if(file_exists($file)){	
			   $zip->addFile($file,str_replace('../../../','',$file)); 
			}
		}
	}
	
	foreach($paths as $path){
		if(file_exists('../../.'.$path)){	
		   $zip->addFile('../../.'.$path,str_replace('./','',$path));
		}
	}
	$zip->close();
}
function file_tree($path) {
	$files = array();
	$ds = glob($path . '*');
	
	if (is_array($ds)) {
		foreach ($ds as $entry) {			
			if (is_file($entry)) {
				$files[] = $entry;				
			}
			if (is_dir($entry)) {
				$rs = file_tree($entry.'/');
				foreach ($rs as $f) {
					$files[] = $f;
				}
			}
		}
	}
	return $files;
}
 