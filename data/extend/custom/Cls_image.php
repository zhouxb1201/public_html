<?php
namespace data\extend\custom;

class Cls_image
{
	public $error_no = 0;
	public $error_msg = '';
	public $images_dir = 'images';
	public $data_dir = 'data';
	public $bgcolor = '';
	public $type_maping = array(1 => 'image/gif', 2 => 'image/jpeg', 3 => 'image/png');

	public function __construct($bgcolor = '')
	{
		$this->cls_image($bgcolor);
	}

	public function cls_image($bgcolor = '')
	{
		if ($bgcolor) {
			$this->bgcolor = $bgcolor;
		}
		else {
			$this->bgcolor = '#FFFFFF';
		}
	}

	public function upload_image($upload, $dir = '', $img_name = '', $steps = 0, $name = '', $type = '', $tmp_name = '', $error = '', $size = '')
	{
        require_once "./common.php";
        $com = new common();

		$upload_type = 0;
		if ($dir && is_array($dir)) {
			$upload_type = $dir['type'];
			$dir = (isset($dir['dir']) ? $dir['dir'] : '');
		}

		$admin_id = $com->get_admin_id();
		$admin_dir = date('Ym');
		$admin_dir = ROOT_PATH . '/public/static/custompc/resource/' . $this->images_dir . '/' . $admin_dir . '/' . 'admin_' . $admin_id;

		if (!file_exists($admin_dir)) {
            $com->make_dir($admin_dir);
		}

		if ($steps == 1) {
			$uplode_name = $name;
			$type = $type;
			$tmp_name = $tmp_name;
			$upload['name'] = $uplode_name;
			$upload['type'] = $type;
			$upload['tmp_name'] = $tmp_name;
			$upload['error'] = $error;
			$upload['size'] = $size;
		}

		if (empty($dir)) {
			$dir = date('Ym');
			$dir = ROOT_PATH . '/public/static/custompc/resource/' . $this->images_dir . '/' . $dir . '/' . 'admin_' . $admin_id . '/';
		}
		else {
			$dir = ROOT_PATH . '/public/static/custompc/resource/' . $this->data_dir . '/' . $dir . '/';

			if ($img_name) {
				$img_name = $dir . $img_name;
			}
		}
		if (!file_exists($dir)) {

            if (!$com->make_dir($dir)) {
				$this->error_msg = sprintf('目錄 % 不存在或不可寫', $dir);
				$this->error_no = 4;
				return false;
			}
		}

		if (isset($upload['name']) && !empty($upload['name']) && empty($img_name)) {
			$img_name = $this->unique_name($dir);
			$img_name = $dir . $img_name . $this->get_filetype($upload['name']);
		}

		if (isset($upload['type']) && !empty($upload['type'])) {
            if (!$this->check_img_type($upload['type'])) {
                $this->error_msg = '不是允許的圖片格式';
				$this->error_no = 7;
				return false;
			}
		}

//		$allow_file_types = '|GIF|JPG|JEPG|PNG|BMP|SWF|TMP|';
//		if (isset($upload['tmp_name']) && !empty($upload['tmp_name'])) {
//			if (!$com->check_file_type($upload['tmp_name'], $img_name, $allow_file_types)) {
//                $this->error_msg = '不是允許的圖片格式';
//				$this->error_no = 7;
//				return false;
//			}
//		}
		if ($this->move_file($upload, $img_name)) {
			if ($upload_type) {
				return $img_name;
			}
			else {
				return str_replace(ROOT_PATH . '/public/static/custompc/resource/', '', $img_name);
			}
		}
		else {
			$this->error_msg = sprintf('文件 %s 上傳失敗。', $upload['name']);
			$this->error_no = 5;
			return false;
		}
	}

	public function make_thumb($img, $thumb_width = 0, $thumb_height = 0, $path = '', $bgcolor = '', $filename = '')
	{
        require_once "./common.php";
        $com = new common();

		$upload_type = 0;
		if ($img && is_array($img)) {
			$upload_type = $img['type'];
			$img = (isset($img['img']) ? $img['img'] : '');
		}

		$gd = $this->gd_version();

		if ($gd == 0) {
			$this->error_msg = '沒有安裝GD庫';
			return false;
		}

		if (($thumb_width == 0) && ($thumb_height == 0)) {
			return str_replace(ROOT_PATH, '', str_replace('\\', '/', realpath($img)));
		}

		$org_info = @getimagesize($img);

		if (!$org_info) {
			$this->error_msg = sprintf('找不到原始圖片 %s ', $img);
			$this->error_no = 3;
			return false;
		}

		if (!$this->check_img_function($org_info[2])) {
			$this->error_msg = sprintf('不支持該圖像格式 %s ', $this->type_maping[$org_info[2]]);
			$this->error_no = 2;
			return false;
		}

		$img_org = $this->img_resource($img, $org_info[2]);
		$scale_org = $org_info[0] / $org_info[1];

		if ($thumb_width == 0) {
			$thumb_width = $thumb_height * $scale_org;
		}

		if ($thumb_height == 0) {
			$thumb_height = $thumb_width / $scale_org;
		}

		if ($gd == 2) {
			$img_thumb = imagecreatetruecolor($thumb_width, $thumb_height);
		}
		else {
			$img_thumb = imagecreate($thumb_width, $thumb_height);
		}

		if (empty($bgcolor)) {
			$bgcolor = $this->bgcolor;
		}

		$bgcolor = trim($bgcolor, '#');
		sscanf($bgcolor, '%2x%2x%2x', $red, $green, $blue);
		$clr = imagecolorallocate($img_thumb, $red, $green, $blue);
		imagefilledrectangle($img_thumb, 0, 0, $thumb_width, $thumb_height, $clr);

		if (($org_info[1] / $thumb_height) < ($org_info[0] / $thumb_width)) {
			$lessen_width = $thumb_width;
			$lessen_height = $thumb_width / $scale_org;
		}
		else {
			$lessen_width = $thumb_height * $scale_org;
			$lessen_height = $thumb_height;
		}

		$dst_x = ($thumb_width - $lessen_width) / 2;
		$dst_y = ($thumb_height - $lessen_height) / 2;

		if ($gd == 2) {
			imagecopyresampled($img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1]);
		}
		else {
			imagecopyresized($img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1]);
		}

		if (empty($path)) {
			$admin_id = $com->get_admin_id();
			$admin_dir = date('Ym');
			$admin_dir = ROOT_PATH . $this->images_dir . '/' . $admin_dir . '/' . 'admin_' . $admin_id;

			if (!file_exists($admin_dir)) {
                $com->make_dir($admin_dir);
			}

			$dir = ROOT_PATH . $this->images_dir . '/' . date('Ym') . '/' . 'admin_' . $admin_id . '/';
		}
		else {
			$dir = $path;
		}

		if (!file_exists($dir)) {
			if (!$com->make_dir($dir)) {
				$this->error_msg = sprintf('目錄 % 不存在或不可寫', $dir);
				$this->error_no = 4;
				return false;
			}
		}

		if ($filename == '') {
			$filename = $this->unique_name($dir);

			if (function_exists('imagejpeg')) {
				$filename .= '.jpg';
				imagejpeg($img_thumb, $dir . $filename, 90);
			}
			else if (function_exists('imagegif')) {
				$filename .= '.gif';
				imagegif($img_thumb, $dir . $filename);
			}
			else if (function_exists('imagepng')) {
				$filename .= '.png';
				imagepng($img_thumb, $dir . $filename);
			}
			else {
				$this->error_msg = '創建圖片失敗';
				$this->error_no = 2;
				return false;
			}
		}
		else {
			imagepng($img_thumb, $dir . $filename);
		}

		imagedestroy($img_thumb);
		imagedestroy($img_org);

		if (file_exists($dir . $filename)) {
			if ($upload_type) {
				return $dir . $filename;
			}
			else {
				return str_replace(ROOT_PATH, '', $dir) . $filename;
			}
		}
		else {
			$this->error_msg = '圖片寫入失敗';
			$this->error_no = 4;
			return false;
		}
	}

	public function add_watermark($filename, $target_file = '', $watermark = '', $watermark_place = '', $watermark_alpha = 0.65000000000000002)
	{
		$gd = $this->gd_version();

		if ($gd == 0) {
			$this->error_msg = '沒有安裝GD庫';
			$this->error_no = 2;
			return false;
		}

		if (!file_exists($filename) || !is_file($filename)) {
			$this->error_msg = sprintf('找不到原始圖片 %s ', $filename);
			$this->error_no = 3;
			return false;
		}

		if (($watermark_place == 0) || empty($watermark)) {
			return str_replace(ROOT_PATH, '', str_replace('\\', '/', realpath($filename)));
		}

		if (!$this->validate_image($watermark)) {
			return false;
		}

		$watermark_info = @getimagesize($watermark);
		$watermark_handle = $this->img_resource($watermark, $watermark_info[2]);

		if (!$watermark_handle) {
			$this->error_msg = sprintf('創建水印圖片資源失敗。水印圖片類型為%s', $this->type_maping[$watermark_info[2]]);
			$this->error_no = 1;
			return false;
		}

		$source_info = @getimagesize($filename);
		$source_handle = $this->img_resource($filename, $source_info[2]);

		if (!$source_handle) {
			$this->error_msg = sprintf('創建原始圖片資源失敗，原始圖片類型%s', $this->type_maping[$source_info[2]]);
			$this->error_no = 1;
			return false;
		}

		switch ($watermark_place) {
		case '1':
			$x = 0;
			$y = 0;
			break;

		case '2':
			$x = $source_info[0] - $watermark_info[0];
			$y = 0;
			break;

		case '4':
			$x = 0;
			$y = $source_info[1] - $watermark_info[1];
			break;

		case '5':
			$x = $source_info[0] - $watermark_info[0];
			$y = $source_info[1] - $watermark_info[1];
			break;

		default:
			$x = ($source_info[0] / 2) - ($watermark_info[0] / 2);
			$y = ($source_info[1] / 2) - ($watermark_info[1] / 2);
		}

		if (strpos(strtolower($watermark_info['mime']), 'png') !== false) {
			imageAlphaBlending($watermark_handle, true);
			imagecopy($source_handle, $watermark_handle, $x, $y, 0, 0, $watermark_info[0], $watermark_info[1]);
		}
		else {
			imagecopymerge($source_handle, $watermark_handle, $x, $y, 0, 0, $watermark_info[0], $watermark_info[1], $watermark_alpha);
		}

		$target = (empty($target_file) ? $filename : $target_file);

		switch ($source_info[2]) {
		case 'image/gif':
		case 1:
			imagegif($source_handle, $target);
			break;

		case 'image/pjpeg':
		case 'image/jpeg':
		case 2:
			imagejpeg($source_handle, $target);
			break;

		case 'image/x-png':
		case 'image/png':
		case 3:
			imagepng($source_handle, $target);
			break;

		default:
			$this->error_msg = '創建圖片失敗';
			$this->error_no = 2;
			return false;
		}

		imagedestroy($source_handle);
		$path = realpath($target);

		if ($path) {
			return str_replace(ROOT_PATH, '', str_replace('\\', '/', $path));
		}
		else {
			$this->error_msg = '圖片寫入失敗';
			$this->error_no = 4;
			return false;
		}
	}

	public function get_width_to_height($path, $image_width = 0, $image_height = 0)
	{
		$width = 0;
		$height = 0;
		$img = @getimagesize($path);

		if ($img) {
			$width = $img[0];
			$height = $img[1];

			if ($width < $image_width) {
				$image_width = $width;
			}

			if ($height < $image_height) {
				$image_height = $height;
			}

			$arr = array('width' => $width, 'height' => $height, 'image_width' => $image_width, 'image_height' => $image_height);
		}

		return $arr;
	}

	public function validate_image($path)
	{
		if (empty($path)) {
			$this->error_msg = '水印文件參數不能為空';
			$this->error_no = 6;
			return false;
		}

		if (!file_exists($path)) {
			$this->error_msg = sprintf('找不到水印文件%s', $path);
			$this->error_no = 3;
			return false;
		}

		$image_info = @getimagesize($path);

		if (!$image_info) {
			$this->error_msg = sprintf('無法識別水印圖片 %s', $path);
			$this->error_no = 1;
			return false;
		}

		if (!$this->check_img_function($image_info[2])) {
			$this->error_msg = sprintf('不支持該圖像格式 %s ', $this->type_maping[$image_info[2]]);
			$this->error_no = 2;
			return false;
		}

		return true;
	}

	public function error_msg()
	{
		return $this->error_msg;
	}

	public function check_img_type($img_type)
	{
		return ($img_type == 'image/pjpeg') || ($img_type == 'image/x-png') || ($img_type == 'image/png') || ($img_type == 'image/gif') || ($img_type == 'image/jpeg') || ($img_type == 'application/octet-stream');
	}

	public function check_img_function($img_type)
	{
		switch ($img_type) {
		case 'image/gif':
		case 1:
			if ('4.3' <= PHP_VERSION) {
				return function_exists('imagecreatefromgif');
			}
			else {
				return 0 < (imagetypes() & IMG_GIF);
			}

			break;

		case 'image/pjpeg':
		case 'image/jpeg':
		case 2:
			if ('4.3' <= PHP_VERSION) {
				return function_exists('imagecreatefromjpeg');
			}
			else {
				return 0 < (imagetypes() & IMG_JPG);
			}

			break;

		case 'image/x-png':
		case 'image/png':
		case 3:
			if ('4.3' <= PHP_VERSION) {
				return function_exists('imagecreatefrompng');
			}
			else {
				return 0 < (imagetypes() & IMG_PNG);
			}

			break;

		default:
			return false;
		}
	}

	static public function random_filename()
	{
        require_once "./common.php";
        $com = new common();

		$str = '';

		for ($i = 0; $i < 9; $i++) {
			$str .= mt_rand(0, 9);
		}

		return $com->gmtime() . $str;
	}

	static public function unique_name($dir)
	{
		$filename = '';

		while (empty($filename)) {
			$filename = cls_image::random_filename();
			if (file_exists($dir . $filename . '.jpg') || file_exists($dir . $filename . '.gif') || file_exists($dir . $filename . '.png')) {
				$filename = '';
			}
		}

		return $filename;
	}

	static public function get_filetype($path)
	{
		$pos = strrpos($path, '.');

		if ($pos !== false) {
			return substr($path, $pos);
		}
		else {
			return '';
		}
	}

	public function img_resource($img_file, $mime_type)
	{
		switch ($mime_type) {
		case 1:
		case 'image/gif':
			$res = imagecreatefromgif($img_file);
			break;

		case 2:
		case 'image/pjpeg':
		case 'image/jpeg':
			$res = imagecreatefromjpeg($img_file);
			break;

		case 3:
		case 'image/x-png':
		case 'image/png':
			$res = imagecreatefrompng($img_file);
			break;

		default:
			return false;
		}

		return $res;
	}

	static public function gd_version()
	{
		static $version = -1;

		if (0 <= $version) {
			return $version;
		}

		if (!extension_loaded('gd')) {
			$version = 0;
		}
		else if ('4.3' <= PHP_VERSION) {
			if (function_exists('gd_info')) {
				$ver_info = gd_info();
				preg_match('/\\d/', $ver_info['GD Version'], $match);
				$version = $match[0];
			}
			else if (function_exists('imagecreatetruecolor')) {
				$version = 2;
			}
			else if (function_exists('imagecreate')) {
				$version = 1;
			}
		}
		else if (preg_match('/phpinfo/', ini_get('disable_functions'))) {
			$version = 1;
		}
		else {
			ob_start();
			phpinfo(8);
			$info = ob_get_contents();
			ob_end_clean();
			$info = stristr($info, 'gd version');
			preg_match('/\\d/', $info, $match);
			$version = $match[0];
		}

		return $version;
	}

	public function move_file($upload, $target)
	{
        require_once "./common.php";
        $com = new common();
		if (isset($upload['error']) && (0 < $upload['error'])) {
			return false;
		}

		if (isset($upload['tmp_name']) && !empty($upload['tmp_name'])) {
			if (!$com->move_upload_file($upload['tmp_name'], $target)) {
				return false;
			}
		}

		return true;
	}
}


?>
