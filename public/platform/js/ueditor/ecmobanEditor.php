<?php
//zend by 注:本程序仅适用于学习与研究使用  禁止倒卖 一经发现停止任何服务!
require 'php/config.php';

$lang = ($_GPC['lang'] == 'en_us' ? 'en' : 'zh-cn');
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\r\n<head>\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n<title>ecEditor - Powered by dm299.com</title>\r\n<script type=\"text/javascript\" src=\"ueditor.config.js\"></script>\r\n<script type=\"text/javascript\" src=\"ueditor.all.js\">/*ecmoban模板堂 --zhuo*/</script> \r\n<script type=\"text/javascript\" src=\"lang/";

echo $lang;
echo '/';
echo $lang;
echo ".js\"></script>\r\n<script type=\"text/javascript\" src=\"third-party/jquery-1.10.2.min.js\"></script>\r\n<style type=\"text/css\">\r\nbody {margin:0px; padding:0px;}\r\n</style>\r\n</head>\r\n\r\n<body>\r\n<script type=\"text/plain\" name=\"content\" id=\"container\"></script>\r\n<script type=\"text/javascript\">\r\n";

$item = htmlspecialchars($_GET['item']);
$website_id = htmlspecialchars($_GET['website_id']);
$instance_id = htmlspecialchars($_GET['shop_id']);
if(!$_COOKIE['website_id']){
    setcookie('website_id',$website_id);
}
if(!$_COOKIE['instance_id']){
    setcookie('instance_id',$instance_id);
}
if($instance_id){
    echo "var uploadeditor = 'upload/".$website_id."/".$instance_id."/customer/';";
}else{
    echo "var uploadeditor = 'upload/".$website_id."/customer/';";
}
echo 'var cBox = $(\'#';
echo $item;
$s = '';
$s.="var ueditoroption = {
				'autoClearinitialContent' : false,
				'toolbars' : [['fullscreen', 'source', 'preview', '|', 'bold', 'italic', 'underline', 'strikethrough', 'forecolor', 'backcolor', '|',
					'justifyleft', 'justifycenter', 'justifyright', '|', 'insertorderedlist', 'insertunorderedlist', 'blockquote', 'emotion', 'insertvideo',
					'link', 'removeformat', '|', 'rowspacingtop', 'rowspacingbottom', 'lineheight','indent', 'paragraph', 'fontsize', '|',
					'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol',
					'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols']],
				'elementPathEnabled' : false,
                                'initialFrameHeight': 400,
				'focus' : false,
				'maximumWords' : 9999999999999
			};
			var opts = {
				type :'image',
				direct : false,
				multi : true,
				tabs : {
					'upload' : 'active',
					'browser' : '',
					'crawler' : ''
				},
				path : '',
				dest_dir : '',
				global : false,
				thumb : false,
				width : 0
			};";

echo "', parent.document);";echo $s;echo "\r\nvar editor = UE.getEditor('container',ueditoroption);\r\neditor.addListener('ready', function() {\r\n  \$('#detail-table', parent.document).hide();//先显示再隐藏编辑器，兼容部分浏览在display:none时无法创建的问题\r\n  var content = cBox.val();\r\n  editor.setContent(content);\r\n});\r\n//editor.addListener(\"contentChange\", function(){setSync()});//触发同步\r\n\$(function(){\r\n  window.setInterval(\"setSync()\",1000);//自动同步\r\n})\r\nfunction setSync(){\r\n  var content = editor.getContent();\r\n  cBox.val(content);\r\n}\r\n</script>\r\n</body>\r\n</html>";

?>
