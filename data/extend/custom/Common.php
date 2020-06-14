<?php
namespace data\extend\custom;

use data\model\AlbumClassModel;
use data\model\AlbumPictureModel;
use data\model\SysPcCustomCodeLogoModel;
use data\model\SysPcCustomNavModel;
use data\model\VslGoodsCategoryModel;
use data\model\VslGoodsModel;
use data\model\SysPcCustomConfigModel;
use data\service\promotion\GoodsPreference;
use data\service\WebSite as WebSite;
use think\Controller;
require_once "Tem.php";



class Common
{
    
    public $instance_id;
    public $website_id;
    public $website;
    protected $module = null;

    /**
     * 构造函数
     *
     * @param unknown $shop_id            
     */
    public function __construct($instance_id = 0,$website_id = 0)
    {
        $this->instance_id = $instance_id;
        $this->website_id = $website_id;
        $this->website = new WebSite();
        $this->module = \think\Request::instance()->module();
    }
    function object2array($object) {
        if (is_object($object)) {
          foreach ($object as $key => $value) {
            $array[$key] = $value;
          }
        }
        else {
          $array = $object;
        }
        return $array;
      }
    /**
     * @pc端装修模板列表
     */
    function get_seller_template_info($template_name = '', $theme = '', $status = 1)
    {
        $info = array();
        $ext = array('png', 'gif', 'jpg', 'jpeg');
        $info['code'] = $template_name;
        $info['screenshot'] = '';
        $info['type'] = $theme;
        $info['used'] = 0;
        $info['default'] = 0;
        $info['typeName'] = $this->getTemplateTypeName($theme,$status);
        $pcCustomConfig = new SysPcCustomConfigModel();
        $usedTem = $pcCustomConfig->getInfo(['type'=>2,'template_type'=>$theme,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id],'code');
        if($template_name==$usedTem['code']){
            $info['used'] = 1;
        }
        $defaultTem = $pcCustomConfig->getInfo(['type'=>1,'template_type'=>$theme,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id],'code');
        if($template_name==$defaultTem['code']){
            $info['default'] = 1;
        }
        if(file_exists(ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$this->instance_id.'/'.$theme.'/' . $template_name)){
            $info['updatetime'] = date('Y-m-d H:i:s',filemtime(ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$this->instance_id.'/'.$theme.'/' . $template_name));
        }
        $info['sort'] = intval(@str_replace($theme.'_tpl_','',$template_name));

            foreach ($ext as $val) {
                if (file_exists(ROOT_PATH .  'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$this->instance_id.'/'.$theme.'/' . $template_name . '/template.' . $val)) {
                    $info['template'] = 'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$this->instance_id.'/'.$theme.'/' . $template_name . '/template.' . $val;
                    break;
                }
            }

		$info_path = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$this->instance_id.'/'.$theme.'/' . $template_name . '/tpl_info.txt';

        if (file_exists($info_path) && !empty($template_name)) {
            $custom_content = addslashes(iconv('GB2312', 'UTF-8', $info_path));
            $arr = @array_slice(file($info_path), 0, 9);
            $arr[1] = addslashes(iconv('GB2312', 'UTF-8', $arr[1]));
            $arr[2] = addslashes(iconv('GB2312', 'UTF-8', $arr[2]));
            $arr[3] = addslashes(iconv('GB2312', 'UTF-8', $arr[3]));
            $template_name = explode('：', $arr[1]);
            $template_screen = explode('：', $arr[2]);
            $template_desc = explode('：', $arr[3]);
            $info['name'] = isset($template_name[1]) ? trim($template_name[1]) : '';
            $info['desc'] = isset($template_desc[1]) ? trim($template_desc[1]) : '';
            $info['screen'] = isset($template_screen[1]) ? trim($template_screen[1]) : '';
        } else {
            $info['name'] = '';
            $info['desc'] = '';
            $info['screen'] = '';
        }
        if($info['screen']){
            $album_picture = new AlbumPictureModel();
            $pic_info = $album_picture->getInfo([
                "pic_id" => $info['screen']
            ], 'pic_cover');
            $info['screenshot'] = $pic_info['pic_cover'] ? $pic_info['pic_cover'] : '';
        }
        return $info;
    }
    /**
     * @pc端装修可选模板列表
     */
    function get_choose_template_info($template_name = '', $theme = '')
    {
        $info = array();
        $ext = array('png', 'gif', 'jpg', 'jpeg');
        $info['code'] = $template_name;
        $info['screenshot'] = '';
        $info['type'] = $theme;
        $info['sort'] = intval(@str_replace($theme.'_tpl_','',$template_name));
        foreach ($ext as $val) {
            if (file_exists(ROOT_PATH .  'public/static/custompc/data/default/tem/'.$theme.'/' . $template_name . '/screenshot.' . $val)) {
                $info['screenshot'] = 'public/static/custompc/data/default/tem/'.$theme.'/' . $template_name . '/screenshot.' . $val;
                break;
            }
        }
        $info_path = ROOT_PATH . 'public/static/custompc/data/default/tem/'.$theme.'/' . $template_name . '/tpl_info.txt';

        if (file_exists($info_path) && !empty($template_name)) {
            $custom_content = addslashes(iconv('GB2312', 'UTF-8', $info_path));
            $arr = @array_slice(file($info_path), 0, 9);
            $arr[1] = addslashes(iconv('GB2312', 'UTF-8', $arr[1]));
            $arr[2] = addslashes(iconv('GB2312', 'UTF-8', $arr[2]));
            $arr[3] = addslashes(iconv('GB2312', 'UTF-8', $arr[3]));
            $template_name = explode('：', $arr[1]);
            $template_screen = explode('：', $arr[2]);
            $template_desc = explode('：', $arr[3]);
            $info['name'] = isset($template_name[1]) ? trim($template_name[1]) : '';
            $info['desc'] = isset($template_desc[1]) ? trim($template_desc[1]) : '';
            $info['screen'] = isset($template_screen[1]) ? trim($template_screen[1]) : '';
        } else {
            $info['name'] = '';
            $info['desc'] = '';
            $info['screen'] = '';
        }
        return $info;
    }
    function getTemplateTypeName($type = '',$status = 1){
        $typeList = array();
        if($this->instance_id==0){
            $typeList['home_templates'] = '商城首页';
        }
        if($status){
            $typeList['shop_templates'] = '店铺首页';
            $typeList['goods_templates'] = '商品详情页';
            $typeList['custom_templates'] = '自定义页';
        }else{
            $typeList['goods_templates'] = '商品详情页';
            $typeList['custom_templates'] = '自定义页';
        }
        if($type && $typeList[$type]){
            return $typeList[$type];
        }
        return $typeList;
    }
    /**
     * pc端自定义模板装修
     */
    function get_html_file($name)
    {
        $smarty = new Tem();

        if (file_exists($name)) {
            $smarty->_current_file = $name;
            $name = $this->read_static_flie_cache($name);
            $source = $smarty->fetch_str($name);

        } else {
            $source = '';
        }

        return $source;
    }

    function read_static_flie_cache($cache_name = '', $suffix = '', $path = '')
    {
        
        if (empty($suffix)) {
        }

        $data = '';

//        if ((DEBUG_MODE & 2) == 2) {
//            return false;
//        }

        static $result = array();

        if (!empty($result[$cache_name])) {
            return $result[$cache_name];
        }



        if (empty($suffix)) {
            $cache_file_path = $cache_name;
        } else {
            $cache_file_path = $path . $cache_name . '.' . $suffix;
        }
        
        if (file_exists($cache_file_path)) {
            $get_data = file_get_contents($cache_file_path);
            return $get_data;
        } else {
            return '';
        }

    }
    function get_bucket_info()
    {
        $res = [];
        return $res;
    }
    /**
     * 删除pc端自定义模板
     */
    function del_DirAndFile($dirName)
    {
        if (is_dir($dirName)) {
            if ($handle = opendir($dirName)) {
                while (false !== ($item = readdir($handle))) {
                    if (($item != '.') && ($item != '..')) {
                        if (is_dir($dirName . '/' . $item)) {
                            $this->del_DirAndFile($dirName . '/' . $item);
                        } else {
                            unlink($dirName . '/' . $item);
                        }
                    }
                }

                closedir($handle);
                return rmdir($dirName);
            }
        } else {
            return true;
        }
    }
    /**
     * 编辑模板
     */
    function get_new_dirName($ru_id = 0, $des = '',$template_type='')
    {
        if ($des == '') {
            $des = ROOT_PATH . '/public/static/custompc/data/web_'.$this->website_id.'/shop_'.$this->instance_id.'/'.$template_type;
        }

        if (!is_dir($des)) {
            return $template_type.'_tpl_1';
        } else {
            $res = array();
            $dir = opendir($des);

            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($des . '/' . $file)) {
                        $arr = explode('_', $file);

                        if ($arr[3]) {
                            $res[] = $arr[3];
                        }
                    }
                }
            }

            closedir($dir);
            if ($res) {
                $suffix = MAX($res) + 1;
                return $template_type.'_tpl_' . $suffix;
            } else {
                return $template_type.'_tpl_1';
            }
        }
    }
    function make_dir($folder)
    {
        $reval = false;

        if (!file_exists($folder)) {
            @umask(0);
            preg_match_all('/([^\\/]*)\\/?/i', $folder, $atmp);
            $base = ($atmp[0][0] == '/' ? '/' : '');

            foreach ($atmp[1] as $val) {
                if ('' != $val) {
                    $base .= $val;
                    if (('..' == $val) || ('.' == $val)) {
                        $base .= '/';
                        continue;
                    }
                } else {
                    continue;
                }

                $base .= '/';

                if (@!file_exists($base)) {
                    if (@mkdir(rtrim($base, '/'), 0777,true)) {
                        @chmod($base, 511);
                        $reval = true;
                    }
                }
            }
        } else {
            $reval = is_dir($folder);
        }

        clearstatcache();
        return $reval;
    }
    function check_file_type($filename, $realname = '', $limit_ext_types = '')
    {
        if ($realname) {
            $extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
        } else {
            $extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
        }

        if ($limit_ext_types && (stristr($limit_ext_types, '|' . $extname . '|') === false)) {
            return '';
        }


        $str = $format = '';
        $file = @fopen($filename, 'rb');

        if ($file) {
            $str = @fread($file, 1024);
            @fclose($file);
        } else if (stristr($filename, ROOT_PATH. '/') === false) {

            if (($extname == 'jpg') || ($extname == 'jpeg') || ($extname == 'gif') || ($extname == 'png') || ($extname == 'doc') || ($extname == 'xls') || ($extname == 'txt') || ($extname == 'zip') || ($extname == 'rar') || ($extname == 'ppt') || ($extname == 'pdf') || ($extname == 'rm') || ($extname == 'mid') || ($extname == 'wav') || ($extname == 'bmp') || ($extname == 'swf') || ($extname == 'chm') || ($extname == 'sql') || ($extname == 'cert') || ($extname == 'pptx') || ($extname == 'xlsx') || ($extname == 'docx')) {
                $format = $extname;
            }
        } else {
            return '';
        }
        $format = $extname;
        if ($limit_ext_types && (stristr($limit_ext_types, '|' . $format . '|') === false)) {

            $format = '';
        }

        return $format;
    }
    function move_upload_file($file_name, $target_name = '')
    {
        if (function_exists('move_uploaded_file')) {
            if (move_uploaded_file($file_name, $target_name)) {
                @chmod($target_name, 493);
                return true;
            } else if (copy($file_name, $target_name)) {
                @chmod($target_name, 493);
                return true;
            }
        } else if (copy($file_name, $target_name)) {
            @chmod($target_name, 493);
            return true;
        }

        return false;
    }
    function write_static_file_cache($cache_name = '', $caches = '', $suffix = '', $path = '')
    {
        $cache_file_path = $path . $cache_name . '.' . $suffix;
        $file_put = @file_put_contents($cache_file_path, $caches, LOCK_EX);
        return $file_put;
    }
    
    function get_array_sort($arr, $keys, $type = 'asc')
    {

        $new_array = array();
        if (is_array($arr) && !empty($arr)) {
            $keysvalue = $new_array = array();

            foreach ($arr as $k => $v) {
                $keysvalue[$k] = $v[$keys];
            }

            if ($type == 'asc') {
                asort($keysvalue);
            } else {
                arsort($keysvalue);
            }

            reset($keysvalue);

            foreach ($keysvalue as $k => $v) {
                $new_array[$k] = $arr[$k];
            }
        }

        return $new_array;
    }
    /**
     * 顶部广告、单图广告、轮播图编辑
     */
    function json_str_iconv($str)
    {
        if (EC_CHARSET != 'utf-8') {
            if (is_string($str)) {
                return addslashes(stripslashes($this->ecs_iconv('utf-8', EC_CHARSET, $str)));
            } else if (is_array($str)) {
                foreach ($str as $key => $value) {
                    $str[$key] = $this->json_str_iconv($value);
                }

                return $str;
            } else if (is_object($str)) {
                foreach ($str as $key => $value) {
                    $str->$key = $this->json_str_iconv($value);
                }

                return $str;
            } else {
                return $str;
            }
        }

        return $str;
    }
    function ecs_iconv($source_lang, $target_lang, $source_string = ''){
        static $chs;
        if (($source_lang == $target_lang) || ($source_string == '') || (preg_match("/[\x80-\xff]+/", $source_string) == 0)) {
            return $source_string;
        }
        return '';
    }
    function object_to_array($obj)
    {
        $_arr = (is_object($obj) ? get_object_vars($obj) : $obj);
        if ($_arr) {
            foreach ($_arr as $key => $val) {
                $val = (is_array($val) || is_object($val) ? $this->object_to_array($val) : $val);
                $arr[$key] = $val;
            }
        } else {
            $arr = array();
        }

        return $arr;
    }

    function getAlbumList($album_id = 0)
    {
        $filter['album_id'] = 0;
        $filter['sort_name'] = !empty($_POST['sort_name']) && ($_POST['sort_name'] != 'undefined') ? intval($_POST['sort_name']) : 2;
        if (0 < $album_id) {
            $filter['album_id'] = $album_id;
        }

        $order='upload_time desc';
        if (0 < $filter['sort_name']) {
            switch ($filter['sort_name']) {
                case '1':
                    $order = 'upload_time asc';
                    break;

                case '2':
                    $order = 'upload_time desc';
                    break;

                case '3':
                    $order = 'pic_size asc';
                    break;

                case '4':
                    $order = 'pic_size desc';
                    break;

                case '5':
                    $order = 'pic_name asc';
                    break;

                case '6':
                    $order = 'pic_name desc';
                    break;
            }
        }
        $albumPic = new AlbumPictureModel();
        $count = $albumPic->getCount(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id,'album_id'=>$filter['album_id']]);
        $filter['record_count'] = $count;
        $recommend_brands = $albumPic->getQuery(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id,'album_id'=>$filter['album_id']], '*', $order);

        $arr = array();

        foreach ($recommend_brands as $key => $row) {
            $row['pic_file'] = __IMG($row['pic_cover']);
            $row['pic_thumb'] = __IMG($row['pic_cover']);
            $row['pic_image'] = __IMG($row['pic_cover']);
            $arr[] = $row;
        }

//        $filter['page_arr'] = $this->seller_page($filter, $filter['page'], 14);
        return array('list' => $arr, 'filter' => $filter);
    }
    /**
     * 热门活动 精选好店
     */
    function resetBarnd($brand_id = array(), $type = 'goods',$self = 0)
    {
        if ($brand_id) {
            if($type=='brand'){
                $ids = \think\Db::table("vsl_goods_brand")->where(['website_id'=>$this->website_id,'shop_id'=>$this->instance_id,'brand_id'=>['in',$brand_id]])->field('brand_id')->select();
            }else if($type=='shop'){
                $ids = [];
                foreach($brand_id as $val){
                    $ids[] = \think\Db::table("vsl_shop")->where(['website_id'=>$this->website_id,'shop_id'=> $val])->field('shop_id')->find();
                }
            }else{
                $where = ['website_id'=>$this->website_id,'goods_id'=>['in',$brand_id]];
                if($self){
                    $where['shop_id']=$this->instance_id;
                }
                $ids = \think\Db::table("vsl_goods")->where($where)->field('goods_id')->select();
            }
            if (!empty($ids)) {
                return implode(',', $this->arr_foreach($ids));
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
    function arr_foreach($multi){
        $arr = array();

        foreach ($multi as $key => $val) {
            if (is_array($val)) {
                $arr = array_merge($arr, $this->arr_foreach($val));
            } else {
                $arr[] = $val;
            }
        }

        return $arr;
    }
    function getShopList($brand_ids)
    {
        $where = ['website_id'=>$this->website_id,'shop_state'=>1,'shop_id'=>['>',0]];
        $recommend_brands = \think\Db::table("vsl_shop")->where($where)->field('shop_id,shop_name,shop_logo')->select();
        if ($brand_ids) {
            $brand_ids = explode(',', $brand_ids);
        }
        $select_shops = array();
        $keys = array_column($recommend_brands,'shop_id');
        $new_array = array_combine($keys,$recommend_brands);
        if($brand_ids){
            foreach ($brand_ids as $v) {
                $select_shops[] = $new_array[$v];
            }
        }
        foreach ($recommend_brands as $key => $val) {
            $recommend_brands[$key]['selected'] = 0;
            if ($brand_ids && in_array($val['shop_id'], $brand_ids)) {
                $recommend_brands[$key]['selected'] = 1;
            }
        }
        return array('list' => list_sort_by($recommend_brands, 'selected', 'desc'),'selected' => $select_shops);
    }
    function getCollectCount($id=0,$type='shop'){
        if(!$id){
            return 0;
        }
        $where = ['fav_id'=>$id,'fav_type'=>$type];
        $res = \think\Db::table("vsl_member_favorites")->where($where)->field('COUNT(0) as total')->find();
        if(!$res){
            return 0;
        }
        return $res['total'];
    }
    function setRewrite($initUrl = '', $params = '', $append = '', $page = 0, $keywords = '', $size = 0)
    {
        $url = false;
        $rewrite = intval($GLOBALS['_CFG']['rewrite']);
        $baseUrl = basename($initUrl);
        $urlArr = explode('?', $baseUrl);
        if ($rewrite && !empty($urlArr[0]) && strpos($urlArr[0], '.php')) {
            $app = str_replace('.php', '', $urlArr[0]);
            @parse_str($urlArr[1], $queryArr);

            if (isset($queryArr['id'])) {
                $id = intval($queryArr['id']);
            }

            if (!empty($id)) {
                switch ($app) {
                    case 'history_list':
                        $idType = array('cid' => $id);
                        break;

                    case 'category':
                        $idType = array('cid' => $id);
                        break;

                    case 'goods':
                        $idType = array('gid' => $id);
                        break;

                    case 'presale':
                        $idType = array('presaleid' => $id);
                        break;

                    case 'brand':
                        $idType = array('bid' => $id);
                        break;

                    case 'brandn':
                        $idType = array('bid' => $id);
                        break;

                    case 'article_cat':
                        $idType = array('acid' => $id);
                        break;

                    case 'article':
                        $idType = array('aid' => $id);
                        break;

                    case 'merchants':
                        $idType = array('mid' => $id);
                        break;

                    case 'merchants_index':
                        $idType = array('urid' => $id);
                        break;

                    case 'group_buy':
                        $idType = array('gbid' => $id);
                        break;

                    case 'seckill':
                        $idType = array('secid' => $id);
                        break;

                    case 'auction':
                        $idType = array('gbid' => $id);
                        break;

                    case 'snatch':
                        $idType = array('sid' => $id);
                        break;

                    case 'exchange':
                        $idType = array('cid' => $id);
                        break;

                    case 'exchange_goods':
                        $idType = array('gid' => $id);
                        break;

                    case 'gift_gard':
                        $idType = array('cid' => $id);
                        break;

                    default:
                        $idType = array('id' => '');
                        break;
                }
            } else {
                switch ($app) {
                    case 'index':
                        $idType = NULL;
                        break;

                    case 'brand':
                        $idType = NULL;
                        break;

                    case 'brandn':
                        $idType = NULL;
                        break;

                    case 'group_buy':
                        $idType = NULL;
                        break;

                    case 'seckill':
                        $idType = NULL;
                        break;

                    case 'auction':
                        $idType = NULL;
                        break;

                    case 'package':
                        $idType = NULL;
                        break;

                    case 'activity':
                        $idType = NULL;
                        break;

                    case 'snatch':
                        $idType = NULL;
                        break;

                    case 'exchange':
                        $idType = NULL;
                        break;

                    case 'store_street':
                        $idType = NULL;
                        break;

                    case 'presale':
                        $idType = NULL;
                        break;

                    case 'categoryall':
                        $idType = NULL;
                        break;

                    case 'merchants':
                        $idType = NULL;
                        break;

                    case 'merchants_index':
                        $idType = NULL;
                        break;

                    case 'message':
                        $idType = NULL;
                        break;

                    case 'wholesale':
                        $idType = NULL;
                        break;

                    case 'gift_gard':
                        $idType = NULL;
                        break;

                    case 'history_list':
                        $idType = NULL;
                        break;

                    case 'merchants_steps':
                        $idType = NULL;
                        break;

                    case 'merchants_steps_site':
                        $idType = NULL;
                        break;

                    default:
                        $idType = array('id' => '');
                        break;
                }
            }

            if ($idType == NULL) {
                $url = $GLOBALS['_CFG']['site_domain'] . $app . '.html';
            } else {
                $params = (empty($params) ? $idType : $params);
                $url = $this->build_uri($app, $params, $append, $page, $keywords, $size);
            }
        }

        if ($url) {
            return $url;
        } else {
            if ((strpos($initUrl, 'http://') === false) && (strpos($initUrl, 'https://') === false)) {
                return $GLOBALS['_CFG']['site_domain'] . $initUrl;
            } else {
                return $initUrl;
            }
        }
    }
    function build_uri($app, $params, $append = '', $page = 0, $keywords = '', $size = 0)
    {
        static $rewrite;

        if ($rewrite === NULL) {
            $rewrite = intval($GLOBALS['_CFG']['rewrite']);
        }

        $args = array('cid' => 0, 'gid' => 0, 'bid' => 0, 'acid' => 0, 'aid' => 0, 'mid' => 0, 'urid' => 0, 'ubrand' => 0, 'chkw' => '', 'is_ship' => '', 'hid' => 0, 'sid' => 0, 'gbid' => 0, 'auid' => 0, 'sort' => '', 'order' => '', 'status' => -1, 'secid' => 0, 'tmr' => 0);
        extract(array_merge($args, $params));
        $uri = '';

        switch ($app) {
            case 'history_list':
                if ($rewrite) {
                    $uri = 'history_list-' . $cid;

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }
                } else {
                    $uri = 'history_list.php?cat_id=' . $cid;

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }
                }

                break;

            case 'category':
                if (empty($cid)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'category-' . $cid;
                    if (isset($bid) && !empty($bid)) {
                        $uri .= '-b' . $bid;
                    }

                    if (isset($ubrand) && !empty($ubrand)) {
                        $uri .= '-ubrand' . $ubrand;
                    }

                    if (isset($price_min)) {
                        $uri .= '-min' . $price_min;
                    }

                    if (isset($price_max)) {
                        $uri .= '-max' . $price_max;
                    }

                    if (isset($filter_attr) && $filter_attr) {
                        $uri .= '-attr' . $filter_attr;
                    }

                    if (isset($ship) && !empty($ship)) {
                        $uri .= '-ship' . $ship;
                    }

                    if (isset($self) && !empty($self)) {
                        $uri .= '-self' . $self;
                    }

                    if (isset($have) && !empty($have)) {
                        $uri .= '-have' . $have;
                    }

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '-' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '-' . $order;
                    }
                } else {
                    $uri = 'category.php?id=' . $cid;

                    if (!empty($bid)) {
                        $uri .= '&amp;brand=' . $bid;
                    }

                    if (!empty($ubrand)) {
                        $uri .= '&amp;ubrand=' . $ubrand;
                    }

                    if (isset($price_min) && !empty($price_min)) {
                        $uri .= '&amp;price_min=' . $price_min;
                    }

                    if (isset($price_max) && !empty($price_max)) {
                        $uri .= '&amp;price_max=' . $price_max;
                    }

                    if (isset($filter_attr) && !empty($filter_attr)) {
                        $uri .= '&amp;filter_attr=' . $filter_attr;
                    }

                    if (isset($ship) && !empty($ship)) {
                        $uri .= '&amp;ship=' . $ship;
                    }

                    if (isset($self) && !empty($self)) {
                        $uri .= '&amp;self=' . $self;
                    }

                    if (isset($have) && !empty($have)) {
                        $uri .= '&amp;have=' . $have;
                    }

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '&amp;sort=' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '&amp;order=' . $order;
                    }
                }

                break;

            case 'wholesale':
                if (empty($cid) && empty($act)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'wholesale';

                    if (!empty($cid)) {
                        $uri .= '-' . $cid;
                    }

                    if (!empty($cid)) {
                        $uri .= '-c' . $cid;
                    }

                    if (isset($status) && ($status != -1)) {
                        $uri .= '-status' . $status;
                    }

                    if (!empty($act)) {
                        $uri .= '-' . $act;
                    }
                } else {
                    $uri = 'wholesale.php?';

                    if (!empty($act)) {
                        $uri .= 'act=' . $act;
                    }

                    if (!empty($cid)) {
                        $uri .= '&amp;id=' . $cid;
                    }

                    if (isset($status) && ($status != -1)) {
                        $uri .= '&amp;status=' . $status;
                    }
                }

                break;

            case 'wholesale_cat':
                if (empty($cid) && empty($act)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'wholesale_cat';

                    if (!empty($cid)) {
                        $uri .= '-' . $cid;
                    }

                    if (isset($status) && ($status != -1)) {
                        $uri .= '-status' . $status;
                    }

                    if (!empty($act)) {
                        $uri .= '-' . $act;
                    }
                } else {
                    $uri = 'wholesale_cat.php?';

                    if (!empty($cid)) {
                        $uri .= 'id=' . $cid;
                    }

                    if (isset($status) && ($status != -1)) {
                        $uri .= '&amp;status=' . $status;
                    }

                    if (!empty($act)) {
                        $uri .= '&amp;act=' . $act;
                    }

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }
                }

                break;

            case 'wholesale_goods':
                if (empty($aid)) {
                    return false;
                } else {
                    $uri = ($rewrite ? 'wholesale_goods-' . $aid : 'wholesale_goods.php?id=' . $aid);
                }

                break;

            case 'wholesale_purchase':
                if (empty($gid) && empty($act)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'wholesale_purchase';

                    if (!empty($gid)) {
                        $uri .= '-' . $gid;
                    }

                    if (!empty($act)) {
                        $uri .= '-' . $act;
                    }
                } else {
                    $uri = 'wholesale_purchase.php?';

                    if (!empty($gid)) {
                        $uri .= 'id=' . $gid;
                    }

                    if (!empty($act)) {
                        $uri .= '&amp;act=' . $act;
                    }
                }

                break;

            case 'goods':
                if (empty($gid)) {
                    return false;
                } else {
                    $uri = ($rewrite ? 'goods-' . $gid : 'goods.php?id=' . $gid);
                }

                break;

            case 'presale':
                if (empty($presaleid) && empty($act)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'presale';

                    if (!empty($presaleid)) {
                        $uri .= '-' . $presaleid;
                    }

                    if (!empty($cid)) {
                        $uri .= '-c' . $cid;
                    }

                    if (isset($status) && ($status != -1)) {
                        $uri .= '-status' . $status;
                    }

                    if (!empty($act)) {
                        $uri .= '-' . $act;
                    }
                } else {
                    $uri = 'presale.php?';

                    if (!empty($presaleid)) {
                        $uri .= 'id=' . $presaleid;
                    }

                    if (!empty($cid)) {
                        $uri .= 'cat_id=' . $cid;
                    }

                    if (isset($status) && ($status != -1)) {
                        $uri .= '&amp;status=' . $status;
                    }

                    if (!empty($act)) {
                        $uri .= '&amp;act=' . $act;
                    }
                }

                break;

            case 'categoryall':
                if (empty($urid)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'categoryall';

                    if (!empty($urid)) {
                        $uri .= '-' . $urid;
                    }
                } else {
                    $uri = 'categoryall.php';

                    if (!empty($urid)) {
                        $uri .= '?id=' . $urid;
                    }
                }

                break;

            case 'brand':
                if (empty($bid)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'brand-' . $bid;

                    if (!empty($mbid)) {
                        $uri .= '-mbid' . $mbid;
                    }

                    if (!empty($cid)) {
                        $uri .= '-c' . $cid;
                    }

                    if (isset($price_min) && !empty($price_min)) {
                        $uri .= '-min' . $price_min;
                    }

                    if (isset($price_max) && !empty($price_max)) {
                        $uri .= '-max' . $price_max;
                    }

                    if (isset($ship) && !empty($ship)) {
                        $uri .= '-ship' . $ship;
                    }

                    if (isset($self) && !empty($self)) {
                        $uri .= '-self' . $self;
                    }

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '-' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '-' . $order;
                    }
                } else {
                    $uri = 'brand.php?id=' . $bid;

                    if (!empty($mbid)) {
                        $uri .= '&amp;mbid=' . $mbid;
                    }

                    if (!empty($cid)) {
                        $uri .= '&amp;cat=' . $cid;
                    }

                    if (isset($price_min)) {
                        $uri .= '&amp;price_min=' . $price_min;
                    }

                    if (isset($price_max)) {
                        $uri .= '&amp;price_max=' . $price_max;
                    }

                    if (isset($ship) && !empty($ship)) {
                        $uri .= '&amp;ship=' . $ship;
                    }

                    if (isset($self) && !empty($self)) {
                        $uri .= '&amp;self=' . $self;
                    }

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '&amp;sort=' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '&amp;order=' . $order;
                    }
                }

                break;

            case 'brandn':
                if (empty($bid)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'brandn-' . $bid;
                    if (isset($cid) && !empty($cid)) {
                        $uri .= '-c' . $cid;
                    }

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '-' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '-' . $order;
                    }

                    if (!empty($act)) {
                        $uri .= '-' . $act;
                    }
                } else {
                    $uri = 'brandn.php?id=' . $bid;

                    if (!empty($cid)) {
                        $uri .= '&amp;cat=' . $cid;
                    }

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }

                    if (isset($price_min)) {
                        $uri .= '&amp;price_min=' . $price_min;
                    }

                    if (isset($price_max)) {
                        $uri .= '&amp;price_max=' . $price_max;
                    }

                    if (isset($is_ship) && !empty($is_ship)) {
                        $uri .= '&amp;is_ship=' . $is_ship;
                    }

                    if (!empty($sort)) {
                        $uri .= '&amp;sort=' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '&amp;order=' . $order;
                    }

                    if (!empty($act)) {
                        $uri .= '&amp;act=' . $act;
                    }
                }

                break;

            case 'article_cat':
                if (empty($acid)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'article_cat-' . $acid;

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '-' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '-' . $order;
                    }

                    if (!empty($keywords)) {
                        $uri .= '-' . $keywords;
                    }
                } else {
                    $uri = 'article_cat.php?id=' . $acid;

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '&amp;sort=' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '&amp;order=' . $order;
                    }

                    if (!empty($keywords)) {
                        $uri .= '&amp;keywords=' . $keywords;
                    }
                }

                break;

            case 'article':
                if (empty($aid)) {
                    return false;
                } else {
                    $uri = ($rewrite ? 'article-' . $aid : 'article.php?id=' . $aid);
                }

                break;

            case 'merchants':
                if (empty($mid)) {
                    return false;
                } else {
                    $uri = ($rewrite ? 'merchants-' . $mid : 'merchants.php?id=' . $mid);
                }

                break;

            case 'merchants_index':
                if (empty($urid) && empty($merchant_id)) {
                    return false;
                } else {
                    if ($urid) {
                        if ($rewrite) {
                            $uri = '';
                            $uri .= 'merchants_index-' . $urid;
                        } else {
                            $uri = 'merchants_index.php?merchant_id=' . $urid;
                        }
                    }

                    if ($merchant_id) {
                        if ($rewrite) {
                            $uri = '';
                            $uri .= 'merchants_index-' . $merchant_id;
                        } else {
                            $uri = 'merchants_index.php?merchant_id=' . $merchant_id;
                        }
                    }
                }

                break;

            case 'merchants_store':
                if (empty($urid)) {
                    return false;
                } else if ($rewrite) {
                    $uri = '';
                    if (isset($domain_name) && !empty($domain_name)) {
                        $uri .= $domain_name . '/';
                    }

                    $uri .= 'merchants_store-' . $urid;

                    if (!empty($cid)) {
                        $uri .= '-c' . $cid;
                    }

                    if (!empty($bid)) {
                        $uri .= '-b' . $bid;
                    }

                    if (!empty($keyword)) {
                        $uri .= '-keyword' . $keyword;
                    }

                    if (isset($price_min)) {
                        $uri .= '-min' . $price_min;
                    }

                    if (isset($price_max)) {
                        $uri .= '-max' . $price_max;
                    }

                    if (isset($filter_attr)) {
                        $uri .= '-attr' . $filter_attr;
                    }

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '-' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '-' . $order;
                    }
                } else {
                    $uri = 'merchants_store.php?merchant_id=' . $urid;

                    if (!empty($cid)) {
                        $uri .= '&amp;id=' . $cid;
                    }

                    if (!empty($bid)) {
                        $uri .= '&amp;brand=' . $bid;
                    }

                    if (!empty($keyword)) {
                        $uri .= '&amp;keyword=' . $keyword;
                    }

                    if (isset($price_min)) {
                        $uri .= '&amp;price_min=' . $price_min;
                    }

                    if (isset($price_max)) {
                        $uri .= '&amp;price_max=' . $price_max;
                    }

                    if (!empty($filter_attr)) {
                        $uri .= '&amp;filter_attr=' . $filter_attr;
                    }

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '&amp;sort=' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '&amp;order=' . $order;
                    }
                }

                break;

            case 'merchants_store_shop':
                if (empty($urid)) {
                    return false;
                } else if ($rewrite) {
                    $uri .= 'merchants_store_shop-' . $urid;

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '-' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '-' . $order;
                    }
                } else {
                    $uri = 'merchants_store_shop.php?id=' . $urid;

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '&amp;sort=' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '&amp;order=' . $order;
                    }
                }

                break;

            case 'group_buy':
                if (empty($gbid)) {
                    return false;
                } else {
                    $uri = ($rewrite ? 'group_buy-' . $gbid : 'group_buy.php?act=view&amp;id=' . $gbid);
                }

                break;

            case 'auction':
                if (empty($auid)) {
                    return false;
                } else {
                    $uri = ($rewrite ? 'auction-' . $auid : 'auction.php?act=view&amp;id=' . $auid);
                }

                break;

            case 'snatch':
                if (empty($sid)) {
                    return false;
                } else {
                    $uri = ($rewrite ? 'snatch-' . $sid : 'snatch.php?id=' . $sid);
                }

                break;

            case 'history_list':
                if (empty($hid)) {
                    return false;
                } else {
                    $uri = ($rewrite ? 'history_list-' . $hid : 'history_list.php?act=user&amp;id=' . $hid);
                }

                break;

            case 'search':
                $uri = 'search.php?keywords=' . $chkw;

                if (!empty($bid)) {
                    $uri .= '&amp;brand=' . $bid;
                }

                if (isset($price_min)) {
                    $uri .= '&amp;price_min=' . $price_min;
                }

                if (isset($price_max)) {
                    $uri .= '&amp;price_max=' . $price_max;
                }

                if (!empty($filter_attr)) {
                    $uri .= '&amp;filter_attr=' . $filter_attr;
                }

                break;

            case 'user':
                if (empty($act)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'user';

                    if (!empty($act)) {
                        $uri .= '-' . $act;
                    }
                } else {
                    $uri = 'user.php?';

                    if (!empty($act)) {
                        $uri .= 'act=' . $act;
                    }
                }

                break;

            case 'exchange':
                if (empty($cid)) {
                    if (!empty($page)) {
                        $uri = 'exchange-' . $cid;

                        if ($rewrite) {
                            $uri .= '-' . $page;
                        } else {
                            $uri = 'exchange.php?';
                            $uri .= 'page=' . $page;
                        }
                    } else {
                        return false;
                    }
                } else if ($rewrite) {
                    $uri = 'exchange-' . $cid;

                    if (isset($price_min)) {
                        $uri .= '-min' . $price_min;
                    }

                    if (isset($price_max)) {
                        $uri .= '-max' . $price_max;
                    }

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '-' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '-' . $order;
                    }
                } else {
                    $uri = 'exchange.php?cat_id=' . $cid;

                    if (isset($price_min)) {
                        $uri .= '&amp;integral_min=' . $price_min;
                    }

                    if (isset($price_max)) {
                        $uri .= '&amp;integral_max=' . $price_max;
                    }

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '&amp;sort=' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '&amp;order=' . $order;
                    }
                }

                break;

            case 'exchange_goods':
                if (empty($gid)) {
                    return false;
                } else {
                    $uri = ($rewrite ? 'exchange-id' . $gid : 'exchange.php?id=' . $gid . '&amp;act=view');
                }

                break;

            case 'gift_gard':
                if (empty($cid)) {
                    return false;
                } else if ($rewrite) {
                    $uri = 'gift_gard-' . $cid;

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '-' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '-' . $order;
                    }
                } else {
                    $uri = 'gift_gard.php?cat_id=' . $cid;

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }

                    if (!empty($sort)) {
                        $uri .= '&amp;sort=' . $sort;
                    }

                    if (!empty($order)) {
                        $uri .= '&amp;order=' . $order;
                    }
                }

                break;

            case 'seckill':
                if (empty($act)) {
                    if (!empty($cid)) {
                        $uri = ($rewrite ? 'seckill-' . $cid : 'seckill.php?cat_id=' . $cid);
                    } else {
                        return false;
                    }
                } else if ($rewrite) {
                    $uri = 'seckill-' . $secid;

                    if (!empty($act)) {
                        $uri .= '-' . $act;
                    }
                } else {
                    $uri = 'seckill.php?id=' . $secid;

                    if ($act == 'view') {
                        $uri .= '&amp;act=view';
                    }

                    if ($tmr) {
                        $uri .= '&tmr=1';
                    }
                }

                break;

            default:
                return false;
                break;
        }

        if ($rewrite) {
            if (($rewrite == 2) && !empty($append)) {
                $uri .= '-' . urlencode(preg_replace('/[\\.|\\/|\\?|&|\\+|\\\\|\'|"|,]+/', '', $append));
            }

            if (!in_array($app, array('search'))) {
                $uri .= '.html';
            }
        }

        if (($rewrite == 2) && (strpos(strtolower(EC_CHARSET), 'utf') !== 0)) {
            $uri = urlencode($uri);
        }

        $site_domain = '';
        if (!isset($domain_name) && empty($domain_name)) {
            $site_domain = $GLOBALS['_CFG']['site_domain'];
        }

        return $site_domain . $uri;
    }
    /**
     * 商品推荐
     */
    function db_create_in($item_list, $field_name = '', $not = '')
    {
        if (!empty($not)) {
            $not = ' ' . $not;
        }

        if (empty($item_list)) {
            return '';
        } else {
            if (!is_array($item_list)) {
                $item_list = explode(',', $item_list);
            }

            $item_list = array_unique($item_list);
            $item_list_tmp = '';

            foreach ($item_list as $item) {
                if ($item !== '') {
                    $item = addslashes($item);
                    $item_list_tmp .= ($item_list_tmp ? ',' . $item . '' : '' . $item . '');
                }
            }

            if (empty($item_list_tmp)) {
                return '';
            } else {
                return $item_list_tmp;
            }
        }
    }
    function getGoodsList($where = '', $sort = '', $search = '', $leftjoin = array())
    {
        if($leftjoin){
            $res = \think\Db::table("vsl_goods ng")->join($leftjoin)->where($where)->field('COUNT(0) as total')->find();

        }else{
            $res = \think\Db::table("vsl_goods ng")->where($where)->field('COUNT(0) as total')->select();
        }

//        $filter['record_count'] = $res['total'];
//        $filter = $this->page_and_size($filter);
        if($leftjoin){
            $goods_list = \think\Db::table("vsl_goods ng")->join($leftjoin)->join('vsl_shop ns','ng.shop_id = ns.shop_id and ns.website_id = ng.website_id', 'left')->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')->where($where)->field('ng.market_price, ng.goods_name, ng.goods_id, ng.price,sap.pic_cover_micro,sap.pic_cover_mid,sap.pic_cover_small,ng.promotion_price,ns.shop_name'.$search)->order($sort)->select();
        }else{
            $goods_list = \think\Db::table("vsl_goods ng")->join('vsl_shop ns','ng.shop_id = ns.shop_id and ns.website_id = ng.website_id', 'left')->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')->where($where)->field('ng.market_price, ng.goods_name, ng.goods_id, ng.price,sap.pic_cover_micro,sap.pic_cover_mid,sap.pic_cover_small,ng.promotion_price,ns.shop_name'.$search)->order($sort)->select();
        }
        if($goods_list){
            $goods_preference = new GoodsPreference();
            foreach($goods_list as $k => $goods){
                $goods_promotion_info = $goods_preference->getGoodsPromote($goods['goods_id']);
                $goods_list[$k]['promotion_info'] = $goods_promotion_info;
            }
        }
//        $filter['page_arr'] = $this->seller_page($filter, $filter['page']);
        return array('list' => $goods_list);
    }
    function page_and_size($filter, $type = 0){
        if ($type == 1) {
            $filter['page_size'] = 10;
        } else if ($type == 2) {
            $filter['page_size'] = 14;
        } else if ($type == 3) {
            $filter['page_size'] = 21;
        } else if ($type == 4) {
            $filter['page_size'] = 18;
        } else {
            if (isset($_REQUEST['page_size']) && (0 < intval($_REQUEST['page_size']))) {
                $filter['page_size'] = intval($_REQUEST['page_size']);
            } else {
                if (isset($_COOKIE['ECSCP']['page_size']) && (0 < intval($_COOKIE['ECSCP']['page_size']))) {
                    $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
                } else {
                    $filter['page_size'] = 15;
                }
            }
        }

        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
        $filter['page_count'] = !empty($filter['record_count']) && (0 < $filter['record_count']) ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        if ($filter['page_count'] < $filter['page']) {
            $filter['page'] = $filter['page_count'];
        }

        $filter['start'] = ($filter['page'] - 1) * $filter['page_size'];
        return $filter;
    }
    /**
     * 自定义区
     */
    function create_ueditor_editor($input_name, $input_value = '', $input_height = 486, $type = 0)
    {
//        global $smarty;
        if($this->module=='platform'){
            $FCKeditor = '<input type="hidden" id="' . $input_name . '" name="' . $input_name . '" value="' . htmlspecialchars($input_value) . '" /><iframe id="' . $input_name . '_frame" src="/public/'.$this->module.'/js/ueditor/ecmobanEditor.php?item=' . $input_name . '&website_id='.$this->website_id.'&shop_id='.$this->instance_id.'" width="100%" height="' . $input_height . '" frameborder="0" scrolling="no"></iframe>';
        }else{
            $FCKeditor = '<input type="hidden" id="' . $input_name . '" name="' . $input_name . '" value="' . htmlspecialchars($input_value) . '" /><iframe id="' . $input_name . '_frame" src="/public/'.$this->module.'/lib/ueditor/ecmobanEditor.php?item=' . $input_name . '&website_id='.$this->website_id.'&shop_id='.$this->instance_id.'" width="100%" height="' . $input_height . '" frameborder="0" scrolling="no"></iframe>';
        }

        if ($type == 1) {
            return $FCKeditor;
        } else {
//            $smarty->assign('FCKeditor', $FCKeditor);
        }
    }
    /**
     * 楼层
     */
    function get_cat_info($cat_id = 0, $select = array())
    {
        if ($select) {
            $select = implode(',', $select);
        } else {
            $select = '*';
        }
        $row = \think\Db::table("vsl_goods_category")->where(['website_id'=>$this->website_id,'category_id'=>$cat_id])->field($select)->find();
        return $row;
    }
    function cat_list($cat_id = 0, $type = 0, $getrid = 0)
    {
        $category = new VslGoodsCategoryModel();
        $res = $category->getQuery(['website_id'=>$this->website_id,'pid'=>$cat_id,'is_visible'=>1], 'category_id,category_name, pid', 'sort asc');
        $arr = array();

        if ($res) {
            foreach ($res as $key => $row) {
                if ($getrid == 0) {
                    $row['cat_name'] = htmlspecialchars(addslashes(str_replace("\r\n", '', $row['category_name'])), ENT_QUOTES);
                    $row['cat_id'] = $row['category_id'];
                    $row['level'] = 0;
                    $row['select'] = str_repeat('&nbsp;', $row['level'] * 4);
                    $arr[$row['category_id']] = $row;
                    $arr[$row['category_id']]['url'] = '#';

                } else {
                    $arr[$row['category_id']]['cat_id'] = $row['category_id'];
                }
                $arr[$row['category_id']]['p_id'] = $row['pid'];
                if ($type) {
                    $arr[$row['category_id']]['child_tree'] = $this->get_child_tree_pro($row['category_id'], 0, $getrid);
                }
            }
        }
        return $arr;
    }
    function get_child_tree_pro($tree_id = 0, $level = 0, $getrid = 0)
    {
        $three_arr = array();
        $category = new VslGoodsCategoryModel();
        $first = $category->getInfo(['website_id'=>$this->website_id,'pid'=>$tree_id,'is_visible'=>1], 'category_id');
        if ($first || ($tree_id == 0)) {
            $res = $category->getQuery(['website_id'=>$this->website_id,'pid'=>$tree_id,'is_visible'=>1], 'category_id, category_name, pid, is_visible','sort asc');


            if ($res) {
                foreach ($res as $row) {
                    $three_arr[$row['category_id']]['id'] = $row['category_id'];

                    if ($getrid == 0) {
                        $three_arr[$row['category_id']]['name'] = htmlspecialchars(addslashes(str_replace("\r\n", '', $row['category_name'])), ENT_QUOTES);
                        $three_arr[$row['category_id']]['url'] = '#';


                        if ($row['pid'] != 0) {
                            $three_arr[$row['category_id']]['level'] = $level + 1;
                        } else {
                            $three_arr[$row['category_id']]['level'] = $level;
                        }

                        $three_arr[$row['category_id']]['select'] = str_repeat('&nbsp;', $three_arr[$row['category_id']]['level'] * 4);
                    }

                    if (isset($row['category_id']) != NULL) {
                        if ($row['pid'] != 0) {
                            $three_arr[$row['category_id']]['cat_id'] = $this->get_child_tree_pro($row['category_id'], $level + 1, $getrid);
                        } else {
                            $three_arr[$row['category_id']]['cat_id'] = $this->get_child_tree_pro($row['category_id'], $level, $getrid);
                        }
                    }

                    if (!$three_arr[$row['category_id']]['cat_id'] && $getrid) {
                        unset($three_arr[$row['category_id']]['cat_id']);
                    }
                }
            }
        }

        return $three_arr;
    }
    function get_floor_style($mode = '')
    {
        $arr = array();

        switch ($mode) {
            case 'homeFloor':
                $arr = array('1,2,3', '1,2,3', '2,3', '1,2,3');
                break;

            case 'homeFloorModule':
                $arr = array('1,3', '1,3', '1,3', '1,3');
                break;

            case 'homeFloorThree':
                $arr = array('2', '1,2,3', '1,3', '2,3');
                break;

            case 'homeFloorFour':
                $arr = array('2', '1', '2', '');
                break;

            case 'homeFloorFive':
                $arr = array('1,2', '1,2,3', '1,2,3', '1,2,3', '1,2,3');
                break;

            case 'homeFloorSix':
                $arr = array('1,2', '1,2', '1,2', '1');
                break;

            case 'homeFloorSeven':
                $arr = array('1,2', '1,2', '1,2', '1,2', '1,2');
                break;
        }

        return $arr;
    }
    function getAdvNum($mode = '', $floorMode = 0)
    {
        $arr = array();

        switch ($mode) {
            case 'homeFloor':
                $arr1 = array('leftBanner' => '3', 'leftAdv' => '2', 'rightAdv' => '5');
                $arr2 = array('leftBanner' => '3', 'leftAdv' => '2', 'rightAdv' => '5');
                $arr3 = array('leftAdv' => '2', 'rightAdv' => '5');
                $arr4 = array('leftBanner' => '3', 'leftAdv' => '2', 'rightAdv' => '5');

                if ($floorMode == 1) {
                    $arr = $arr1;
                } else if ($floorMode == 2) {
                    $arr = $arr2;
                } else if ($floorMode == 3) {
                    $arr = $arr3;
                } else if ($floorMode == 4) {
                    $arr = $arr4;
                } else {
                    $arr[1] = $arr1;
                    $arr[2] = $arr2;
                    $arr[3] = $arr3;
                    $arr[4] = $arr4;
                }

                break;

            case 'homeFloorModule':
                $arr1 = array('leftBanner' => '3', 'rightAdv' => '4');
                $arr2 = array('leftBanner' => '3', 'rightAdv' => '3');
                $arr3 = array('leftBanner' => '3', 'rightAdv' => '3');
                $arr4 = array('leftBanner' => '3', 'rightAdv' => '2');

                if ($floorMode == 1) {
                    $arr = $arr1;
                } else if ($floorMode == 2) {
                    $arr = $arr2;
                } else if ($floorMode == 3) {
                    $arr = $arr3;
                } else if ($floorMode == 4) {
                    $arr = $arr4;
                } else {
                    $arr[1] = $arr1;
                    $arr[2] = $arr2;
                    $arr[3] = $arr3;
                    $arr[4] = $arr4;
                }

                break;

            case 'homeFloorThree':
                $arr1 = array('leftAdv' => '5');
                $arr2 = array('leftBanner' => '3', 'leftAdv' => '1', 'rightAdv' => '6');
                $arr3 = array('leftBanner' => '3', 'rightAdv' => '8');
                $arr4 = array('leftAdv' => '2', 'rightAdv' => '8');

                if ($floorMode == 1) {
                    $arr = $arr1;
                } else if ($floorMode == 2) {
                    $arr = $arr2;
                } else if ($floorMode == 3) {
                    $arr = $arr3;
                } else if ($floorMode == 4) {
                    $arr = $arr4;
                } else {
                    $arr[1] = $arr1;
                    $arr[2] = $arr2;
                    $arr[3] = $arr3;
                    $arr[4] = $arr4;
                }

                break;

            case 'homeFloorFour':
                $arr1 = array('leftAdv' => '2');
                $arr2 = array('leftBanner' => '3');
                $arr3 = array('leftAdv' => '2');
                $arr4 = array();

                if ($floorMode == 1) {
                    $arr = $arr1;
                } else if ($floorMode == 2) {
                    $arr = $arr2;
                } else if ($floorMode == 3) {
                    $arr = $arr3;
                } else if ($floorMode == 4) {
                    $arr = $arr4;
                } else {
                    $arr[1] = $arr1;
                    $arr[2] = $arr2;
                    $arr[3] = $arr3;
                    $arr[4] = $arr4;
                }

                break;

            case 'homeFloorFive':
                $arr1 = array('leftBanner' => '3', 'leftAdv' => '3');
                $arr2 = array('leftBanner' => '3', 'leftAdv' => '3', 'rightAdv' => '3');
                $arr3 = array('leftBanner' => '3', 'leftAdv' => '3', 'rightAdv' => '2');
                $arr4 = array('leftBanner' => '3', 'leftAdv' => '3', 'rightAdv' => '1');
                $arr5 = array('leftBanner' => '3', 'leftAdv' => '3', 'rightAdv' => '2');

                if ($floorMode == 1) {
                    $arr = $arr1;
                } else if ($floorMode == 2) {
                    $arr = $arr2;
                } else if ($floorMode == 3) {
                    $arr = $arr3;
                } else if ($floorMode == 4) {
                    $arr = $arr4;
                } else if ($floorMode == 5) {
                    $arr = $arr5;
                } else {
                    $arr[1] = $arr1;
                    $arr[2] = $arr2;
                    $arr[3] = $arr3;
                    $arr[4] = $arr4;
                    $arr[5] = $arr5;
                }

                break;

            case 'homeFloorSix':
                $arr1 = array('leftBanner' => '3', 'leftAdv' => '4');
                $arr2 = array('leftBanner' => '3', 'leftAdv' => '2');
                $arr3 = array('leftBanner' => '3', 'leftAdv' => '1');
                $arr4 = array('leftBanner' => '3');

                if ($floorMode == 1) {
                    $arr = $arr1;
                } else if ($floorMode == 2) {
                    $arr = $arr2;
                } else if ($floorMode == 3) {
                    $arr = $arr3;
                } else if ($floorMode == 4) {
                    $arr = $arr4;
                } else {
                    $arr[1] = $arr1;
                    $arr[2] = $arr2;
                    $arr[3] = $arr3;
                    $arr[4] = $arr4;
                }

                break;

            case 'homeFloorSeven':
                $arr1 = array('leftBanner' => '3', 'leftAdv' => '1');
                $arr2 = array('leftBanner' => '3', 'leftAdv' => '1');
                $arr3 = array('leftBanner' => '3', 'leftAdv' => '1');
                $arr4 = array('leftBanner' => '3', 'leftAdv' => '1');
                $arr5 = array('leftBanner' => '3', 'leftAdv' => '1');

                if ($floorMode == 1) {
                    $arr = $arr1;
                } else if ($floorMode == 2) {
                    $arr = $arr2;
                } else if ($floorMode == 3) {
                    $arr = $arr3;
                } else if ($floorMode == 4) {
                    $arr = $arr4;
                } else if ($floorMode == 5) {
                    $arr = $arr5;
                } else {
                    $arr[1] = $arr1;
                    $arr[2] = $arr2;
                    $arr[3] = $arr3;
                    $arr[4] = $arr4;
                    $arr[5] = $arr5;
                }

                break;
        }

        return $arr;
    }
    function strFilter($str)
    {
        $str = str_replace('`', '', $str);
        $str = str_replace('·', '', $str);
        $str = str_replace('~', '', $str);
        $str = str_replace('!', '', $str);
        $str = str_replace('！', '', $str);
        $str = str_replace('@', '', $str);
        $str = str_replace('#', '', $str);
        $str = str_replace('$', '', $str);
        $str = str_replace('￥', '', $str);
        $str = str_replace('%', '', $str);
        $str = str_replace('^', '', $str);
        $str = str_replace('……', '', $str);
        $str = str_replace('&', '', $str);
        $str = str_replace('*', '', $str);
        $str = str_replace('(', '', $str);
        $str = str_replace(')', '', $str);
        $str = str_replace('（', '', $str);
        $str = str_replace('）', '', $str);
        $str = str_replace('-', '', $str);
        $str = str_replace('_', '', $str);
        $str = str_replace('——', '', $str);
        $str = str_replace('+', '', $str);
        $str = str_replace('=', '', $str);
        $str = str_replace('|', '', $str);
        $str = str_replace('\\', '', $str);
        $str = str_replace('[', '', $str);
        $str = str_replace(']', '', $str);
        $str = str_replace('【', '', $str);
        $str = str_replace('】', '', $str);
        $str = str_replace('{', '', $str);
        $str = str_replace('}', '', $str);
        $str = str_replace(';', '', $str);
        $str = str_replace('；', '', $str);
        $str = str_replace(':', '', $str);
        $str = str_replace('：', '', $str);
        $str = str_replace('\'', '', $str);
        $str = str_replace('"', '', $str);
        $str = str_replace('“', '', $str);
        $str = str_replace('”', '', $str);
        $str = str_replace(',', '', $str);
        $str = str_replace('，', '', $str);
        $str = str_replace('<', '', $str);
        $str = str_replace('>', '', $str);
        $str = str_replace('《', '', $str);
        $str = str_replace('》', '', $str);
        $str = str_replace('.', '', $str);
        $str = str_replace('。', '', $str);
        $str = str_replace('/', '', $str);
        $str = str_replace('、', '', $str);
        $str = str_replace('?', '', $str);
        $str = str_replace('？', '', $str);
        return trim($str);
    }
    function getFloorGoodsList($where = '', $sort = '', $limit = ''){
        $goods_list = \think\Db::table("vsl_goods ng")->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')->where($where)->field('ng.market_price, ng.goods_name, ng.goods_id, ng.price,sap.pic_cover_micro,sap.pic_cover_mid,sap.pic_cover_small,ng.promotion_price')->order($sort)->limit(0,$limit)->select();
        return $goods_list;
    }
    /**
     * 导航编辑
     */
    /* 删除导航*/
    function removeNav($result,$id)
    {
        $nav = new SysPcCustomNavModel();
        if (0 < $id) {
            $nav->destroy(['id'=>$id,'website_id'=>$this->website_id]);
            $result['error'] = 1;
            $result['content'] = '删除成功';
        }
        else {
            $result['error'] = 0;
            $result['content'] = '导航不存在';
        }
        return json_encode($result);

    }
    /**
     * 导航添加
     */
    function addNav($result,$name,$ntype='index')
    {
        $nav = new SysPcCustomNavModel();
        if (!empty($name)) {
            $is_only = $nav->getInfo(['name'=>$name,'website_id'=>$this->website_id,'shop_id'=>$this->instance_id,'type'=>$ntype]);
            if ($is_only) {
                $result['error'] = 0;
                $result['content'] = '导航’' . $name . '‘已存在';
            } else {
                $data=[
                    'name' => $name,
                    'opennew' => 0,
                    'ifshow' => 0,
                    'type' => $ntype,
                    'vieworder' => 50,
                    'website_id' => $this->website_id,
                    'shop_id'=>$this->instance_id
                ];
                $id = $nav->save($data);
                $result['error'] = 1;
                $html_id = '\'' . $id . '\'';
                $html_act_name = '\'name_home\'';
                $html_act_url = '\'url_home\'';
                $html_act_order = '\'vieworder_home\'';
                $html_act_if_show = '\'ifshow_home\'';
                $html_act_type = '\'0\'';
                $html_ifshow = '\'ifshow\'';
                $html_opennew = '\'opennew\'';
                $html = '<tr><td><input type="text" class="nav-mode-input  get-nav-select-name'.$id.'" onchange = "edit_nav(this.value ,' . $html_id . ',' . $html_act_name . ')" value="' . $name . '"></td>';
                $html .= '<td><input type="text" class="nav-mode-input  get-nav-select-url'.$id.'" onchange = "edit_nav(this.value ,' . $html_id . ',' . $html_act_url . ')" ><a href="javascript:;" class="btn select-lj nav-link" onclick="addSelecturl(this, ' . $id . ', ' . $id . ')"><i class="icon icon-notice"></i></a></td>';
                $html .= '<td> <label for="isWindow"><input type="checkbox" name="isWindow" id="isWindow">是</label></td>';
                $html .= '<td class="center"><input type="text" onchange = "edit_nav(this.value ,' . $html_id . ',' . $html_act_order . ')" class="small" value=""></td>';
                $html .= '<td class="center"><a href="javascript:void(0);" onclick="remove_topicnav(this)" class="pic_del del">删除</a></td></tr>';
                $result['content'] = $html;

            }
        } else {
            $result['error'] = 0;
            $result['content'] = '导航名称不能为空';
        }
        return json_encode($result);
    }
    /**
     * 分类名字方法
     */
    function navName($result, $nav_name, $id)
    {
        $nav = new SysPcCustomNavModel();
        if ((0 < $id) && !empty($nav_name)) {
            $is_only = $nav->getInfo(['name'=>$nav_name,'website_id'=>$this->website_id,'id'=>['neq',$id]]);

            if (!empty($is_only) && isset($is_only[0])) {
                $result['error'] = 0;
                $result['content'] = '导航’' . $nav_name . '‘已存在';
            } else {
                $nav->save(['name'=>$nav_name],['id'=>$id,'website_id'=> $this->website_id]);
                $result['error'] = 1;
                $result['content'] = '编辑成功';
            }
        } else {
            $result['error'] = 0;
            $result['content'] = '导航不存在或者导航名称不能为空';
        }

        return json_encode($result);
    }

    /**
     * 分类地址修改
     */
    function navUrl($result, $url, $id)
    {
        $nav = new SysPcCustomNavModel();
        if (0 < $id) {
            $nav->save(['url'=>$url],['id'=>$id,'website_id'=> $this->website_id]);
            $result['error'] = 1;
            $result['content'] = '编辑成功';
        }
        else {
            $result['error'] = 0;
            $result['content'] = '导航不存在';
        }

        return json_encode($result);
    }

    /**
     * 分类排序修改
     */
    function navViewOrder($result, $order, $id)
    {
        $nav = new SysPcCustomNavModel();
        if (0 < $id) {
            if (preg_match('/^\\d+$/i', $order)) {
                $nav->save(['vieworder'=>$order],['id'=>$id,'website_id'=> $this->website_id]);
                $result['error'] = 1;
                $result['content'] = '编辑成功';
            }
            else {
                $result['error'] = 0;
                $result['content'] = '排序必须为数字';
            }
        }
        else {
            $result['error'] = 0;
            $result['content'] = '导航不存在';
        }

        return json_encode($result);
    }
    /**
     * 导航 是否显示 是否新窗口修改
     */
    function ifshowHome($result, $ifshow, $id, $attr)
    {
        $nav = new SysPcCustomNavModel();
        if (0 < $id) {
            if ($ifshow == 0) {
                $val = 1;
            }
            else {
                $val = 0;
            }
            $nav->save([$attr=>$val],['id'=>$id,'website_id'=> $this->website_id]);
            $result['error'] = 1;
            $result['id'] = $id;
            $html_ifshow = '\'' . $val . '\'';
            $html_id = '\'' . $id . '\'';
            $html_act_if_show = '\'ifshow_home\'';
            $html_act_type = '\'' . $attr . '\'';
            if ($val == 1) {
                $src = '/public/static/custompc/images/yes.gif';
            }
            else {
                $src = '/public/static/custompc/images/no.gif';
            }

            $html = '<img onclick = "edit_nav(' . $html_ifshow . ' ,' . $html_id . ',' . $html_act_if_show . ',' . $html_act_type . ')" src="' . $src . '"/>';
            $result['type'] = $attr;
            $result['content'] = $html;
        } else {
            $result['error'] = 0;
            $result['content'] = '导航不存在';
        }

        return json_encode($result);
    }
    /**
     * 获取分类信息
     */
    function get_array_category_info($arr = array())
    {
        if ($arr) {
            $arr = $this->get_del_str_comma($arr);
            $category = new VslGoodsCategoryModel();
            $category_list = $category->getQuery(['website_id'=>$this->website_id,'category_id'=>['in', implode(',', $arr)]],'category_id, category_name','');
            foreach ($category_list as $key => $val) {
                $category_list[$key]['url'] = '#';
            }
            return $category_list;
        } else {
            return false;
        }
    }
    function get_select_category($cat_id = 0, $relation = 0, $self = true)
    {
        static $cat_list = array();
        $cat_list[] = intval($cat_id);
        $category = new VslGoodsCategoryModel();
        if ($relation == 0) {
            return $cat_list;
        } else if ($relation == 1) {
            $parent = $category->getInfo(['website_id'=>$this->website_id,'category_id'=>$cat_id],'pid');
            $parent_id = $parent['pid'];
            if (!empty($parent_id)) {
                $this->get_select_category($parent_id, $relation, $self);
            }
            if ($self == false) {
                unset($cat_list[0]);
            }
            $cat_list[] = 0;
            return array_reverse(array_unique($cat_list));
        } else if ($relation == 2) {
            $child_id = $category->getQuery(['website_id'=>$this->website_id,'pid'=>$cat_id],'category_id','');
            if (!empty($child_id)) {
                foreach ($child_id as $key => $val) {
                    $this->get_select_category($val, $relation, $self);
                }
            }

            if ($self == false) {
                unset($cat_list[0]);
            }

            return $cat_list;
        }
    }
    function get_category_list($cat_id = 0, $relation = 0)
    {
        $category = new VslGoodsCategoryModel();
        $parent = $category->getInfo(['website_id'=>$this->website_id,'category_id'=>$cat_id],'pid');
        if ($relation == 0 || $relation == 1) {
            $parent_id = $parent['pid'];
        } else if ($relation == 2) {
            $parent_id = $cat_id;
        }

        $parent_id = (empty($parent_id) ? 0 : $parent_id);
        $category_list = $category->getQuery(['website_id'=>$this->website_id,'pid'=>$parent_id],'category_id, category_name','');

        foreach ($category_list as $key => $val) {
            if ($cat_id == $val['category_id']) {
                $is_selected = 1;
            } else {
                $is_selected = 0;
            }

            $category_list[$key]['is_selected'] = $is_selected;
            $category_list[$key]['url'] = '#';
        }

        return $category_list;
    }

    function seller_page($list, $nowpage, $show = '10')
    {
        $arr = array();

        if ($list['page_count'] < $show) {
            $show = $list['page_count'];
        }

        if (($show % 2) == 0) {
            $begin = $nowpage - ceil($show / 2);
            $end = $nowpage + floor($show / 2);
        } else {
            $begin = $nowpage - floor($show / 2);
            $end = $nowpage + ceil($show / 2);
        }

        if (1 < $show) {
            if (((ceil($show / 2) + 1) < $nowpage) && ($nowpage <= $list['page_count'] - ceil($show / 2))) {
                for ($i = $begin; $i < $end; $i++) {
                    $arr[$i] = $i;
                }
            } else {
                if (((ceil($show / 2) + 1) < $nowpage) && (($list['page_count'] - $show - 1) < $nowpage)) {
                    for ($i = $list['page_count'] - $show - 1; $i <= $list['page_count']; $i++) {
                        $arr[$i] = $i;
                    }
                } else {
                    for ($i = 1; $i <= $show; $i++) {
                        $arr[$i] = $i;
                    }
                }
            }
        } else {
            $arr[1] = 1;
        }

        return $arr;
    }

    function getBrandList($brand_ids)
    {
        $where = ['website_id'=>$this->website_id,'brand_recommend'=>1];
        $res = \think\Db::table("vsl_goods_brand")->where($where)->field('COUNT(0) as total')->find();
        $filter['record_count'] = $res['total'];
        $filter = $this->page_and_size($filter, 4);
        $recommend_brands = \think\Db::table("vsl_goods_brand")->where($where)->field('brand_id,brand_name,brand_pic')->limit($filter['start'] . "," . $filter['page_size'])->select();
        if ($brand_ids) {
            $brand_ids = explode(',', $brand_ids);
        }

        foreach ($recommend_brands as $key => $val) {
            $recommend_brands[$key]['selected'] = 0;

            if (!empty($brand_ids)) {
                foreach ($brand_ids as $v) {
                    if ($v == $val['brand_id']) {
                        $recommend_brands[$key]['selected'] = 1;
                    }
                }
            }
        }
        $filter['page_arr'] = $this->seller_page($filter, $filter['page'], 14);
        return array('list' => $recommend_brands, 'filter' => $filter);
    }
    /**
     * 相册确认上传
     */
    function get_goods_gallery_album($type = 0, $id = 0, $select = array(),$id_name = 'album_id', $order = '')
    {
        $where = ['website_id'=>$this->website_id,'shop_id'=>$this->instance_id];
        if($id){
            $where[$id_name] = $id;
        }
        if ($select && is_array($select)) {
            $select = implode(',', $select);
        } else {
            $select = '*';
        }
        $limit = '';
        if ($type == 2) {
            $limit = 1;
        }
        if($type==1 || $type==2){
            $album_list = \think\Db::table("sys_album_class")->where($where)->field($select)->order($order)->limit(0,$limit)->select();
            if ($album_list) {
                foreach ($album_list as $key => $row) {
                    $album_list[$key]['add_time'] = $this->local_date('Y-m-d H:i:s', $row['create_time']);
                }
            }
        }else{
            $album_list = \think\Db::table("sys_album_class")->where($where)->field($select)->order($order)->limit(0,$limit)->find();
        }
        return $album_list;
    }
    /**
     * 广告图片保存操作处理
     */
    function create_html($out = '', $cachename = '', $suffix = '', $topic_type = '',$type = 0)
    {
        $_CFG['cache_time'] = 3600;
        $smarty = new Tem();
        $smarty->cache_lifetime = $_CFG['cache_time'];
        $smarty->cache_dir = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$this->instance_id.'/'.$topic_type;
        $smarty->cache_dir_common = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/common/';
        $smarty->cache_dir_shop_common = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$this->instance_id.'/common/';

        $suffix = $suffix . '/temp';
        $back = '';
        if ($out) {
            $out = str_replace("\r", '', $out);

            while (strpos($out, "\n\n") !== false) {
                $out = str_replace("\n\n", "\n", $out);
            }
            if($type == 1){
                $hash_dir = $smarty->cache_dir_common. '/temp';
            }else if($type == 2){
                $hash_dir = $smarty->cache_dir_shop_common. '/temp';
            }else{
                $hash_dir = $smarty->cache_dir . '/' . $suffix;
            }


            if (!is_dir($hash_dir)) {
                $this->make_dir($hash_dir);
            }

            if ($cachename) {
                $files = explode('.', $cachename);
                $files_count = count($files) - 1;
                $suffix_name = $files[$files_count];

                if (2 < count($files)) {
                    $path = count($files) - 1;
                    $name = '';

                    if ($files[$path]) {
                        foreach ($files[$path] as $row) {
                            $name .= $row . '.';
                        }

                        $name = substr($name, 0, -1);
                    }

                    $file_path = explode('/', $name);

                    if (2 < $file_path) {
                        $path = count($file_path) - 1;
                        $cachename = $file_path[$path];
                    } else {
                        $cachename = $file_path[0];
                    }
                } else {
                    $cachename = $files[0];
                }

                $file_put = $this->write_static_file_cache($cachename, $out, $suffix_name, $hash_dir . '/');
            } else {
                $file_put = false;
            }

            if ($file_put === false) {
                trigger_error('can\'t write:' . $hash_dir . '/' . $cachename);
                $back = '';
            } else {
                $back = $cachename;
            }

            $smarty->template = array();
        } else {
            $back = '';
        }

        return $back;
    }
    /**
     * 保存发布模板
     */
    function recurse_copy($src, $des, $type = 0)
    {
        $dir = opendir($src);

        if (!is_dir($des)) {
            $this->make_dir($des);
        }

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $des . '/' . $file);
                } else if ($type == 0) {
                    copy($src . '/' . $file, $des . '/' . $file);
                } else {
                    $comtent = $this->read_static_flie_cache($src . '/' . $file);
                    $files = explode('.', $file);
                    $files_count = count($files) - 1;
                    $suffix_name = $files[$files_count];

                    if (2 < count($files)) {
                        $path = count($files) - 1;
                        $name = '';

                        if ($files[$path]) {
                            foreach ($files[$path] as $row) {
                                $name .= $row . '.';
                            }

                            $name = substr($name, 0, -1);
                        }

                        $file_path = explode('/', $name);

                        if (2 < $file_path) {
                            $path = count($file_path) - 1;
                            $cachename = $file_path[$path];
                        } else {
                            $cachename = $file_path[0];
                        }
                    } else {
                        $cachename = $files[0];
                    }

                    $this->write_static_file_cache($cachename, $comtent, $suffix_name, $des . '/');
                }
            }
        }

        closedir($dir);
    }
    function unEscape($str){
        $ret = '';
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            if (($str[$i] == '%') && ($str[$i + 1] == 'u')) {
                $val = hexdec(substr($str, $i + 2, 4));

                if ($val < 127) {
                    $ret .= chr($val);
                } else if ($val < 2048) {
                    $ret .= chr(192 | ($val >> 6)) . chr(128 | ($val & 63));
                } else {
                    $ret .= chr(224 | ($val >> 12)) . chr(128 | (($val >> 6) & 63)) . chr(128 | ($val & 63));
                }

                $i += 5;
            } else if ($str[$i] == '%') {
                $ret .= urldecode(substr($str, $i, 3));
                $i += 2;
            } else {
                $ret .= $str[$i];
            }
        }

        return $ret;
    }
    function data_dir($sid = 0)
    {
        if (empty($sid)) {
            $s = 'data';
        } else {
            $s = 'user_files/';
            $s .= ceil($sid / 3000) . '/';
            $s .= $sid % 3000;
        }

        return $s;
    }
    function local_date($format, $time = NULL){
        $timezone = (isset($_SESSION['timezone']) ? $_SESSION['timezone'] : 0);

        if ($time === NULL) {
            $time = $this->gmtime();
        } else if ($time <= 0) {
            return '';
        }

        $time += $timezone * 3600;
        return date($format, $time);
    }
    function gmtime(){
        return time();
    }
    
    function get_goods_desc_images_preg($endpoint = '', $text_desc = '', $str_file = 'goods_desc')
    {
        if ($text_desc) {
            $preg = '/<img.*?src=[\\"|\']?(.*?)[\\"|\'].*?>/i';
            preg_match_all($preg, $text_desc, $desc_img);
        } else {
            $desc_img = '';
        }

        $arr = array();
        if ($desc_img && $endpoint) {
            foreach ($desc_img[1] as $key => $row) {
                $row = explode(IMAGE_DIR, $row);
                $arr[] = $endpoint . IMAGE_DIR . $row[1];
            }

            if ($desc_img[1]) {
                if (1 < count($desc_img[1])) {
                    $desc_img[1] = array_unique($desc_img[1]);

                    foreach ($desc_img[1] as $key => $row) {
                        if ((strpos($row, 'http://') === false) && (strpos($row, 'https://') === false)) {
                            $row_str = substr($row, 0, 1);
                            $str = substr($endpoint, $this->str_len($endpoint) - 1);
                            if (($str == '/') && ($row_str == '/')) {
                                $endpoint = substr($endpoint, 0, -1);
                            }

                            $text_desc = str_replace($row, $endpoint . $row, $text_desc);
                        }
                    }
                } else if (strpos($text_desc, $endpoint) === false) {
                    $text_desc = str_replace('/' . IMAGE_DIR, $endpoint . IMAGE_DIR, $text_desc);
                }
            }
        }

        $res = array('images_list' => $arr, $str_file => $text_desc);
        return $res;
    }



    function get_extension_goods($cats)
    {
        $extension_goods_array = '';
        $sql = 'SELECT goods_id FROM ' . tablename('vslai_shop_pc_goods_cat') . ' AS g WHERE ' . $cats;
        $extension_goods_array = pdo_fetchcolumn($sql);
        return $this->db_create_in($extension_goods_array, 'g.goods_id');
    }

    function get_goods_list($filter)
    {
        $filter->keyword = $this->json_str_iconv($filter->keyword);
        $where = $this->get_where_sql($filter);
        $sql = 'SELECT id, title, marketprice ' . 'FROM ' . tablename('vslai_shop_goods') . ' AS g ' . $where;
        $row = pdo_fetchall($sql);
        return $row;
    }

    function get_where_sql($filter)
    {
        $uniacid = $this->getComUniacid();
        $adminru = $this->get_admin_ru_id();
        $time = date('Y-m-d');
        $where = (isset($filter->is_delete) && ($filter->is_delete == '1') ? ' WHERE uniacid = '.$uniacid.' and is_delete = 1 ' : ' WHERE uniacid = '.$uniacid.' and is_delete = 0 ');
        $where .= (isset($filter->real_goods) && (-1 < $filter->real_goods) ? ' AND is_real = ' . intval($filter->real_goods) : '');
        $where .= (isset($filter->cat_id) && (0 < $filter->cat_id) ? ' AND ' . $this->get_children($filter->cat_id) : '');
        $brand_keyword = $filter->brand_keyword;
        $sel_mode = $filter->sel_mode;

        if ($filter->brand_keyword) {
            if (($sel_mode == 1) && !empty($brand_keyword)) {
                $new_array = array();
                $sql = 'SELECT brand_id FROM ' . tablename('vslai_shop_brand') . ' WHERE brand_name LIKE \'%' . $brand_keyword . '%\' ';
                $brand_id = pdo_fetchall($sql);

                foreach ($brand_id as $key => $value) {
                    $new_array[] = $value['brand_id'];
                }

                $where .= (isset($filter->brand_keyword) && (trim($filter->brand_keyword) != '') ? ' AND brand_id ' . $this->db_create_in($new_array) . '' : '');
            } else {
                if (($sel_mode == 1) && !empty($brand_keyword)) {
                    $filter->brand_id = 0;
                }
            }
        } else {
            $where .= (isset($filter->brand_id) && (0 < $filter->brand_id) ? ' AND brand_id = \'' . $filter->brand_id . '\'' : '');
        }

        $where .= (isset($filter->intro_type) && ($filter->intro_type != '0') ? ' AND ' . $filter->intro_type . ' = \'1\'' : '');
        $where .= (isset($filter->intro_type) && ($filter->intro_type == 'is_promote') ? ' AND promote_start_date <= \'' . $time . '\' AND promote_end_date >= \'' . $time . '\' ' : '');
        $where .= (isset($filter->keyword) && (trim($filter->keyword) != '') ? ' AND (goods_name LIKE \'%' . $this->mysql_like_quote($filter->keyword) . '%\' OR goods_sn LIKE \'%' . mysql_like_quote($filter->keyword) . '%\' OR goods_id LIKE \'%' . mysql_like_quote($filter->keyword) . '%\') ' : '');
        $where .= (isset($filter->suppliers_id) && (trim($filter->suppliers_id) != '') ? ' AND (suppliers_id = \'' . $filter->suppliers_id . '\') ' : '');
        $where .= (isset($filter->in_ids) ? ' AND goods_id ' . $this->db_create_in($filter->in_ids) : '');
        $where .= (isset($filter->exclude) ? ' AND goods_id NOT ' . $this->db_create_in($filter->exclude) : '');
        $where .= (isset($filter->stock_warning) ? ' AND goods_number <= warn_number' : '');
        $where .= (isset($filter->presale) ? ' AND is_on_sale = 0 ' : '');

        if (isset($filter->ru_id)) {
            $where .= ' AND user_id = \'' . $filter->ru_id . '\'';
        } else {
            $where .= ' AND user_id = \'' . $adminru['ru_id'] . '\'';
        }

        return $where;
    }



    function get_goods_image_path($goods_id, $image = '', $thumb = false, $call = 'goods', $del = false, $retain = false)
    {
        if (!empty($image) && (strpos($image, 'http://') === false) && (strpos($image, 'https://') === false) && (strpos($image, 'errorImg.png') === false)) {
//            $image = 'http://pic.vslai1.com/' . $image;
            $image = $this->getPicSetUrl() . '/' . $image;
        }

        $return = 1;

        if (!empty($image) && (strpos($image, 'http://') === false) && (strpos($image, 'https://') === false) && (strpos($image, 'errorImg.png') === false)) {
            if ($return == 1) {
                $image = $_SERVER['HTTP_ORIGIN'] . '/' . $image;
            }
        }
        $url = $image;
        return $url;
    }



    function get_recursive_file_oss($dir, $path = '', $is_recursive = false, $type = 0)
    {
        $file_list = scandir($dir);
        $arr = array();

        if ($file_list) {
            foreach ($file_list as $key => $row) {
                if ($is_recursive && is_dir($dir . $row) && !in_array($row, array('.', '..', '...'))) {
                    $arr[$key]['child'] = $this->get_recursive_file_oss($dir . $row . '/', $path, $is_recursive, 1);
                } else if (is_file($dir . $row)) {
                    if ($type == 1) {
                        $arr[$key] = $dir . $row;
                    } else {
                        $arr[$key] = $path . $row;
                    }
                }

                if ($arr[$key]) {
                    $arr[$key] = str_replace(ROOT_PATH, '', $arr[$key]);
                }
            }

            if ($arr) {
                $arr = $this->arr_foreach($arr);
                $arr = array_unique($arr);
            }
        }

        return $arr;
    }

    function dsc_unlink($file = '')
    {
        if ($file && file_exists($file)) {
            unlink($file);
        }

    }

    function get_category_tree_leve_one($parent_id = 0, $type = 0)
    {
        $goodsCategory = new VslGoodsCategoryModel();
        $first = $goodsCategory->getQuery(['website_id'=>$this->website_id,'pid'=>$parent_id,'is_visible'=>1], 'category_id,category_name,category_pic', 'sort asc');

        $arr = array();

        foreach ($first as $key => $row) {
            $arr[$row['category_id']]['id'] = $row['category_id'];
            $arr[$row['category_id']]['cat_alias_name'] = $row['category_name'];
            $arr[$row['category_id']]['url'] = '#';
            $arr[$row['category_id']]['style_icon'] = '';
            $arr[$row['category_id']]['cat_icon'] = '';


            $arr[$row['category_id']]['name'] = $row['category_name'];


            $arr[$row['category_id']]['nolinkname'] = $row['category_name'];

            if ($type == 1) {
                $arr[$row['category_id']]['child_tree'] = $this->cat_list($row['category_id'], 1);
            }
            $res = $goodsCategory->getQuery(['website_id'=>$this->website_id,'pid'=>$row['category_id'],'is_visible'=>1], 'category_id,category_name,category_pic,pid', 'sort asc');


            foreach ($res as $key2 => $val) {
                $arr[$row['category_id']]['child_two'][$key2]['p_id'] = $val['pid'];
                $arr[$row['category_id']]['child_two'][$key2]['cat_id'] = $val['category_id'];
                $arr[$row['category_id']]['child_two'][$key2]['cat_name'] = $val['category_name'];
                $arr[$row['category_id']]['child_two'][$key2]['url'] = '#';
            }
        }

        return $arr;
    }

    function get_navigator($ntype = 'index')
    {
        $nav = new SysPcCustomNavModel();
        $res = $nav->getQuery(['website_id'=>$this->website_id,'ifshow'=>1,'shop_id'=>$this->instance_id,'type'=>$ntype], '*', 'type desc');
        $cur_url = substr(strrchr($_SERVER['REQUEST_URI'], '/'), 1);

        if (intval($GLOBALS['_CFG']['rewrite'])) {
            if (strpos($cur_url, '-')) {
                preg_match('/([a-z]*)-([0-9]*)/', $cur_url, $matches);
                $cur_url = $matches[1] . '.php?id=' . $matches[2];
            }
        } else {
            $cur_url = substr(strrchr($_SERVER['REQUEST_URI'], '/'), 1);
        }

        $navlist = array();
        if (!empty($res)) {
            foreach ($res as $row) {
                $navlist[] = array('name' => $row['name'], 'opennew' => $row['opennew'], 'url' => $this->setRewrite($row['url']), 'ctype' => $row['ctype'], 'cid' => $row['cid']);
            }
        }


        return $navlist;
    }

    function get_categories_tree_xaphp($cat_id = 0)
    {
        $goodsCotegory = new \data\model\VslGoodsCategoryModel();
        $res = $goodsCotegory->getQuery(['website_id'=>$this->website_id,'pid'=>$cat_id], 'category_id,category_name,pid,is_visible', 'sort asc');

        foreach ($res as $row) {
            $cat_arr[$row['category_id']]['id'] = $row['category_id'];
            $cat_arr[$row['category_id']]['name'] = $row['category_name'];
            $cat_arr[$row['category_id']]['url'] = '#';

            if (isset($row['category_id']) != NULL) {
                $cat_arr[$row['category_']]['cat_id'] = $this->get_child_tree($row['category_id']);
            }
        }

        if (isset($cat_arr)) {
            return $cat_arr;
        }
    }

    function get_child_tree($tree_id = 0, $ru_id = 0)
    {
         $goodsCategory = new \data\model\VslGoodsCategoryModel();
        $first = $goodsCategory->getInfo(['website_id'=>$this->website_id,'pid'=>$tree_id], 'category_id');
        $three_arr = array();
        if ($first || ($tree_id == 0)) {
            $res = $goodsCotegory->getInfo(['website_id'=>$this->website_id,'pid'=>$tree_id], 'category_id,category_name,pid,is_visible','sort asc');

            foreach ($res as $row) {
                if ($row['is_visible']) {
                    $three_arr[$row['category_id']]['id'] = $row['category_id'];
                }

                $three_arr[$row['category_id']]['name'] = $row['category_name'];
                $three_arr[$row['category_id']]['url'] = '#';
                
                if (isset($row['category_id']) != NULL) {
                    $three_arr[$row['category_id']]['cat_id'] = $this->get_child_tree($row['category_id']);
                }
            }
        }

        return $three_arr;
    }

    function my_array_merge($array1, $array2)
    {
        $new_array = $array1;

        foreach ($array2 as $key => $val) {
            $new_array[$key] = $val;
        }

        return $new_array;
    }

    function getPicSetUrl()
    {
        $result = pdo_fetchall('select * from ' . tablename('core_settings'));
        if (!empty($result)) {
            foreach ($result as $ka => $va) {
                $result[$ka]['value'] = iunserializer($va['value']);
            }
            $url = $result[8]['value']['alioss']['url'];
        }
        return $url;
    }

    function get_floor_ajax_goods($cat_id = 0, $num = 0,  $goods_ids = '', $cat_type = 0)
    {
        $where = ['ng.website_id'=>$this->website_id,'ng.state'=>1];
        if (0 < $cat_id) {
            $where['ng.category_id_2']  = $cat_id;


            if (!empty($goods_ids)) {
                $where['ng.goods_id'] = ['in',$goods_ids];
            }

            $limit = '';
            if (0 < $num) {
               $limit = $num;
            }
            $goods_res = \think\Db::table("vsl_goods ng")->join('sys_album_picture sap','ng.picture = sap.pic_id', 'left')->where($where)->field('ng.market_price, ng.goods_name, ng.goods_id, ng.price,sap.pic_cover_micro,sap.pic_cover_mid,sap.pic_cover_small,ng.promotion_price')->order('sort desc')->limit(0,$num)->select();
            foreach ($goods_res as $idx => $row) {
                $goods_res[$idx]['url'] = 'goods';
            }
            return $goods_res;
        }
    }

    /**
     * 复制文件夹
     * @param $source
     * @param $dest
     */
    function copydir($source, $dest)
    {
        if (!file_exists($dest)) @mkdir($dest);
        $handle = @opendir($source);
        while (($item = @readdir($handle)) !== false) {
            if ($item == '.' || $item == '..') continue;
            $_source = $source . '/' . $item;
            $_dest = $dest . '/' . $item;
            if (is_file($_source)) @copy($_source, $_dest);
            if (is_dir($_source)) $this->copydir($_source, $_dest);
        }
        @closedir($handle);
        return true;
    }
    /**
     * 根据用户Id创建默认模板
     */
    function createTem()
    {
        $aType = ['home_templates','shop_templates','goods_templates'];
        if($this->instance_id){
            $aType = ['shop_templates','goods_templates'];
        }
        $defaultDir = ROOT_PATH . '/public/static/custompc/data/default/';
        if(!file_exists($defaultDir) || $this->is_empty_dir($defaultDir)){
            return true;
        }
        $webDir = ROOT_PATH . '/public/static/custompc/data/web_'.$this->website_id;
        $templateDir = $webDir.'/shop_'.$this->instance_id;
        $dir_common = $webDir.'/common';
        $dir_shop_common = $webDir.'/shop_'.$this->instance_id.'/common';
        if(!file_exists($dir_common)){
            @mkdir($dir_common, 0777, true);
            @chmod($dir_common, 0777);
            $this->copydir($defaultDir .'common', $dir_common);
        }elseif($this->is_empty_dir($dir_common)){
            $this->copydir($defaultDir .'common', $dir_common);
        }
        if(!file_exists($dir_shop_common)){
            @mkdir($dir_shop_common, 0777, true);
            @chmod($dir_shop_common, 0777);
            $this->copydir($defaultDir .'shopcommon', $dir_shop_common);
        }elseif($this->is_empty_dir($dir_shop_common)){
            $this->copydir($defaultDir .'shopcommon', $dir_shop_common);
        }
        $pcCustomNav = new SysPcCustomNavModel();
        $pcCustomNavConfig = new \data\model\SysPcCustomNavConfigModel();
        $checkNav = $pcCustomNav->getInfo(['shop_id'=>0,'website_id'=>$this->website_id,'type'=>'index']);
        if(!$checkNav){
            $data = [
                'shop_id' => 0,
                'type' => 'index',
                'website_id' => $this->website_id,
                'name' => '商城首页',
                'ifshow' => 1,
                'url' => __URLS('SHOP_MAIN')
            ];
            $pcCustomNav->save($data);
            $dataconfig = [
                'shop_id' => 0,
                'website_id' => $this->website_id,
                'code' => 'home_templates_tpl_1',
                'template_type' => 'home_templates'
            ];
            $pcCustomNavConfig->save($dataconfig);
        }
        $pcCustomNav = new SysPcCustomNavModel();
        $pcCustomNavConfig = new \data\model\SysPcCustomNavConfigModel();
        $checkShopNav = $pcCustomNav->getInfo(['shop_id'=>$this->instance_id,'website_id'=>$this->website_id,'type'=>'shop']);
        if(!$checkShopNav){
            $data = [
                'shop_id' => $this->instance_id,
                'type' => 'shop',
                'website_id' => $this->website_id,
                'name' => '店铺首页',
                'ifshow' => 1,
                'url' => __URLS('ADDONS_SHOP_MAIN','addons=shopIndex&shop_id='.$this->instance_id)
            ];
            $pcCustomNav->isUpdate(false)->save($data);
            $datashopconfig = [
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
                'code' => 'shop_templates_tpl_1',
                'template_type' => 'shop_templates'
            ];
            $pcCustomNavConfig->isUpdate(false)->save($datashopconfig);
        }
        foreach($aType as $type){
            $typeDir = $templateDir.'/'.$type;
            if (!file_exists($typeDir)) {
                @mkdir($templateDir, 0777, true);
                @chmod($templateDir, 0777);
                $this->copydir($defaultDir .'templates/'. $type, $typeDir);
            }elseif($this->is_empty_dir($typeDir)){
                $this->copydir($defaultDir .'templates/'. $type, $typeDir);
            }
            $pcCustomConfig = new SysPcCustomConfigModel();
            $defaultTem = $pcCustomConfig->getInfo(['type'=>1,'template_type'=>$type,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id],'code');
            if(!$defaultTem){
                $pcCustomConfig->save(['type'=>1,'template_type'=>$type,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id,'code'=>$type.'_tpl_1']);
            }
            $pcCustomConfig2 = new SysPcCustomConfigModel();
            $usedTem = $pcCustomConfig2->getInfo(['type'=>2,'template_type'=>$type,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id],'code');
            if(!$usedTem){
                $pcCustomConfig2->save(['type'=>2,'template_type'=>$type,'shop_id'=>$this->instance_id,'website_id'=>$this->website_id,'code'=>$type.'_tpl_1']);
            }
        }
        return true;
        //判断是否新用户 创建默认模板
    }
    function is_empty_dir($dir_path)
    {
        if (!is_dir($dir_path)){
            return false;
        }
        $dir = @opendir($dir_path);
        $is_empty = true;
        while ($file = readdir($dir)){
            if($file == '.' || $file == '..') 
                continue;
            $is_empty = false;
            break;
        }
        closedir($dir);
        return $is_empty;
    }
    /*
     * 获取logo
     */
    function getLogo($backup)
    {
        $codeLogo = new SysPcCustomCodeLogoModel();
        $setdata = $codeLogo->getInfo(['website_id'=>$this->website_id,'code'=>$backup],'logo');
        if (!empty($setdata['logo'])) {
                $logo_pic = __IMG($setdata['logo']);
            } else {
                $list = $this->website->getWebSiteInfo();
                $logo_pic = __IMG($list['logo']);
        }
        return $logo_pic;
    }

   


  
}