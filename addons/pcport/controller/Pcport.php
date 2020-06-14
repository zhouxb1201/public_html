<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:37
 */

namespace addons\pcport\controller;

use addons\pcport\Pcport as basePcport;
use data\model\SysPcCustomNavModel;
use data\model\SysPcCustomConfigModel;
use data\extend\custom\Json;
use data\model\SysPcCustomStyleConfigModel;
use data\service\Config;
use data\service\GoodsCategory;
use data\service\Goods;
use data\service\AddonsConfig;
use addons\shop\service\Shop;
use addons\helpcenter\server\Helpcenter as helpServer;
use data\model\SysPcCustomNavConfigModel;

class Pcport extends basePcport
{
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function savePcPortSetting()
    {
        $addonsConfigSer = new AddonsConfig();
        $post_data = request()->post();
        $post_data['value'] = $post_data;
        $post_data['addons'] = parent::$addons_name;
        $post_data['desc'] = 'pc端设置';
        $res = $addonsConfigSer->setAddonsConfig($post_data);
        if(!$res){
            return AjaxReturn(0);
        }
        $this->addUserLog('pc端设置',$res);
        setAddons('pcport', $this->website_id, $this->instance_id);
        return AjaxReturn($res);
    }
    
    public function pcSetting(){
        $config_type = request()->post('config_type', '');
        if($config_type == 'basic'){
            $addonsConfigSer = new AddonsConfig();
            $addons_info = $addonsConfigSer->getAddonsConfig(parent::$addons_name, $this->website_id);
            $addons_data = json_decode($addons_info['value'], true) ?: [];
            $addons_data['is_use'] = $addons_info['is_use'] ?: 0;
        }
        $config = new Config();
        if ($config_type == 'seo') {
            $addons_data = $config->getSeoConfig($this->instance_id);
        }
        if ($config_type == 'thirdParty') {
            $domain_name = $this->realm_ip;
            $call_back = $this->realm_ip. CALLBACK_URL;
            $wchat_config = $config->getWchatConfig($this->instance_id);
            $wchat_config['value']["AUTHORIZE"] = $domain_name;
            $wchat_config['value']["CALLBACK"] = $call_back;
            $qq_config = $config->getQQConfig($this->instance_id);
            $qq_config['value']["AUTHORIZE"] = $domain_name;
            $qq_config['value']["CALLBACK"] = $call_back;
            $addons_data['wchat_config'] = $wchat_config;
            $addons_data['qq_config'] = $qq_config;
        }
        return $addons_data;
    }
    /**
     * seo设置
     */
    public function seoConfig() {
        $Config = new Config();
        if (request()->isAjax()) {
            $shop_id = $this->instance_id;
            $seo_title = request()->post("seo_title", '');
            $seo_meta = request()->post("seo_meta", '');
            $seo_desc = request()->post("seo_desc", '');
            $retval = $Config->setSeoConfig($shop_id, $seo_title, $seo_meta, $seo_desc);
            return AjaxReturn($retval);
        }
    }

    /**
     * qq登录配置
     */
    public function loginQqConfig() {
        $appkey = isset($_POST['appkey']) ? $_POST['appkey'] : '';
        $appsecret = isset($_POST['appsecret']) ? $_POST['appsecret'] : '';
        $url = isset($_POST['url']) ? $_POST['url'] : '';
        $call_back_url = isset($_POST['call_back_url']) ? $_POST['call_back_url'] : '';
        $is_use = isset($_POST['is_use']) ? $_POST['is_use'] : 0;
        $web_config = new Config();
        // 获取数据
        $retval = $web_config->setQQConfig($this->instance_id, $appkey, $appsecret, $url, $call_back_url, $is_use);
        return AjaxReturn($retval);
    }

    /**
     * 微信登录配置
     */
    public function loginWeiXinConfig() {
        $appid = isset($_POST['appkey']) ? $_POST['appkey'] : '';
        $appsecret = isset($_POST['appsecret']) ? $_POST['appsecret'] : '';
        $url = isset($_POST['url']) ? $_POST['url'] : '';
        $call_back_url = isset($_POST['call_back_url']) ? $_POST['call_back_url'] : '';
        $is_use = isset($_POST['is_use']) ? $_POST['is_use'] : 0;
        $web_config = new Config();
        // 获取数据
        $retval = $web_config->setWchatConfig($this->instance_id, $appid, $appsecret, $url, $call_back_url, $is_use);
        return AjaxReturn($retval);
    }

    /**
     * pc端自定义模板
     *
     * @return list
     */
    public function pcCustomTemplateList() {
        $this->com->createTem();
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $is_page = request()->post('is_page', 0);
        $template_name = request()->post('template_name', '');
        $template_type = request()->post('type', 'base');
        if($this->instance_id){
            $aType = ['shop_templates', 'goods_templates'];
        }else{
            if (getAddons('shop',$this->website_id,0,true)) {
                $aType = ['home_templates', 'shop_templates', 'goods_templates'];
            } else {
                $aType = ['home_templates', 'goods_templates'];
            }
        }
        
        if ($template_type == 'diy') {
            $aType = ['custom_templates'];
        }
        
        $list = array();
        foreach ($aType as $type) {
            $dir = $this->dir . '/' . $type . '/';

            if (file_exists($dir)) {
                $template_dir = @opendir($dir);
                while ($file = readdir($template_dir)) {

                    if (($file != '.') && ($file != '..') && ($file != '.svn') && ($file != 'index.htm')) {
                        $list[] = $this->com->get_seller_template_info($file, $type);
                    }
                }
            } else {
                @mkdir($dir, 0777, true);
                @chmod($dir, 0777);
            }
        }

        if (!empty($list)) {
            foreach ($list as $atk => $atv) {
                $list[$atk]['yl_url'] = __URLS("SHOP_MAIN/index/index", "suffix=" . $atv['code'] . "&temp_type=" . $atv['type'] . "&website_id=" . $this->website_id . "&instance_id=" . $this->instance_id);
                if (!$template_name) {
                    continue;
                }
                if (strpos($atv['name'], $template_name) === false) {
                    unset($list[$atk]);
                }
            }
        }
        $list = array_values($list);
        $sort1 = array_column($list, 'used');
        $sort2 = array_column($list, 'updatetime');
        array_multisort($sort1, SORT_DESC, $sort2, SORT_DESC, $list);
        $count = count($list);
        if(!$is_page){
            $start_row = $page_size * ($page_index - 1);
            $list = array_slice($list, $start_row, $page_size);
        }
        if ($page_size == 0) {
            $page_count = 1;
        } else {
            if ($count % $page_size == 0) {
                $page_count = $count / $page_size;
            } else {
                $page_count = (int) ($count / $page_size) + 1;
            }
        }
        return array('data' => $list, 'page_count' => $page_count, 'total_count' => $count);
    }

    

    /**
     * 编辑模板
     *
     * @return view
     */
    public function editTem() {
        $allow_file_types = '|GIF|JPG|PNG|';
        $tem = request()->post('tem', '') ? addslashes(request()->post('tem', '')) : '';
        $ten_file = request()->post('ten_file', '');
        $name = request()->post('name', '') ? 'tpl name：' . addslashes(request()->post('name', '')) : 'tpl name：';
        $description = request()->post('description', '') ? 'description：' . addslashes(request()->post('description', '')) : 'description：';
        $template_type = request()->post('template_type', '') ? trim(request()->post('template_type', '')) : '';

        $des = $this->dir . '/' . $template_type;
        $desshopcommon = $this->dir_shop_common;
        if ($tem == 'undefined' || !$tem) {
            $tem = $this->com->get_new_dirName(0, $des, $template_type);
            $code_dir = $des . '/' . $tem;

            if (!is_dir($code_dir)) {
                $this->com->make_dir($code_dir);
            }
            if (!is_dir($desshopcommon)) {
                $this->com->make_dir($desshopcommon);
            }
        }

        $format = array('png', 'gif', 'jpg');
        $file_dir = $des . '/' . $tem;
        $file_dir_common = $descommon;


        if (!is_dir($file_dir)) {
            $this->com->make_dir($file_dir);
        }
        if (!is_dir($desshopcommon)) {
            $this->com->make_dir($desshopcommon);
        }

        $end = '------tpl_info------------';
        $tab = "\n";
        $html = $end . $tab . $name . $tab . 'tpl url：' . $ten_file . $tab . $description . $tab . $end;
        $html = $this->com->write_static_file_cache('tpl_info', iconv('UTF-8', 'GB2312', $html), 'txt', $file_dir . '/');

        if ($html === false) {
            $result = $file_dir . '/tpl_info.txt没有写入权限，请修改权限';
            $this->com->del_DirAndFile($file_dir);
            return AjaxReturn(0, $result);
        } else {
            $seller_dir = $des . '/' . $tem;
            $template_dir = @opendir($seller_dir);

            while ($file = readdir($template_dir)) {
                if (($file != '.') && ($file != '..') && ($file != '.svn') && ($file != 'index.htm')) {
                    $available_templates[] = $this->com->get_seller_template_info($file, $template_type);
                }
            }
            $available_templates = $this->com->get_array_sort($available_templates, 'sort');
            @closedir($template_dir);
            $this->addUserLog('编辑PC端装修模板', $template_dir);
            return AjaxReturn(1, array('code' => $tem, 'type' => $template_type));
        }
    }

    /*
     * 使用模板
     */

    public function setDefaultCustomTemplatePc() {
        $code = trim(request()->post('code', ''));
        $type = trim(request()->post('type', ''));
        $dir = $this->dir . '/' . $type . '/' . $code;
        if (file_exists($dir) && $code) {
            $pcCustomConfig = new SysPcCustomConfigModel();
            $usedTem = $pcCustomConfig->getInfo(['type' => 2, 'template_type' => $type, 'shop_id' => $this->instance_id, 'website_id' => $this->website_id], 'id');
            if (empty($usedTem)) {
                $in_data = array(
                    'code' => $code,
                    'type' => 2,
                    'template_type' => $type,
                    'sort_order' => 1,
                    'website_id' => $this->website_id,
                    'shop_id' => $this->instance_id,
                );
                $pcCustomConfig->save($in_data);
            } else {
                $pcCustomConfig->save(['code' => $code], ['id' => $usedTem['id']]);
            }
            $this->addUserLog('使用pc端装修模板', $code);
            return AjaxReturn(1, '操作成功');
        }
        return AjaxReturn(0, '该模板不存在，请检查');
    }

    /**
     * 删除pc端自定义模板
     *
     * @return list
     */
    public function deletePcCustomTemplate() {
        $result = 0;
        $code = request()->post('code', '') ? addslashes(request()->post('code', '')) : '';
        $template_type = request()->post('template_type', '') ? trim(request()->post('template_type', '')) : '';
        $pcCustomConfig = new SysPcCustomConfigModel();
        $usedTem = $pcCustomConfig->getInfo(['type' => 2, 'template_type' => $template_type, 'shop_id' => $this->instance_id, 'website_id' => $this->website_id], 'code');
        $defaultTem = $pcCustomConfig->getInfo(['type' => 1, 'template_type' => $template_type, 'shop_id' => $this->instance_id, 'website_id' => $this->website_id], 'code');
        if ($defaultTem['code'] == $code) {
            $result = 4;  //默认模板不能删除;
            echo $result;
            die;
        }
        if (($usedTem['code'] == $code)) {
            $result = 1;  //'该模板正在使用中，不能删除！欲删除请先更改模板！';
            echo $result;
            die;
        } else {
            $dir = $this->dir . '/' . $template_type . '/' . $code;
            $rmdir = $this->com->del_DirAndFile($dir);

            if ($rmdir == true) {
                $this->addUserLog('删除pc端自定义模板', $code);
                $result = 0;
                echo $result;
                die;
            } else {
                $result = 2;  // '系统出错，请重试！';
                echo $result;
            }
        }
    }

    

    /**
     * 还原模板
     */
    public function backModal() {
        $code = trim(request()->post('suffix', ''));
        $temp_type = request()->post('template_type', '');
        $dir = $this->dir . '/' . $temp_type . '/' . $code . '/temp';
        $dircommon = $this->dir_common . '/temp';
        $dirshopcommon = $this->dir_shop_common . '/temp';
        if (!empty($code)) {
            $this->com->del_DirAndFile($dir);
            if(!$this->instance_id){
                $this->com->del_DirAndFile($dircommon);
            }
            $this->com->del_DirAndFile($dirshopcommon);
            $this->addUserLog('还原模板', $code);
            return AjaxReturn(1);
        }
        return AjaxReturn(0);
    }

    /**
     * 保存发布模板
     */
    public function downLoadModal() {
        $code = trim(request()->post('suffix', ''));
        $temp_type = request()->post('template_type', '');
        $dir = $this->dir . '/' . $temp_type . '/' . $code . '/temp';
        $file = $this->dir . '/' . $temp_type . '/' . $code;
        $dircommon = $this->dir_common . '/temp';
        $filecommon = $this->dir_common;
        $dirshopcommon = $this->dir_shop_common . '/temp';
        $fileshopcommon = $this->dir_shop_common;
        if (!empty($code)) {
            if (!is_dir($dir)) {
                $this->com->make_dir($dir);
            }
            $this->com->recurse_copy($dir, $file, 1);
            $this->com->del_DirAndFile($dir);
            if(!$this->instance_id){
                if (!is_dir($dircommon)) {
                    $this->com->make_dir($dircommon);
                }
                $this->com->recurse_copy($dircommon, $filecommon, 1);
                $this->com->del_DirAndFile($dircommon);
            }
            if (!is_dir($dirshopcommon)) {
                $this->com->make_dir($dirshopcommon);
            }
            $this->com->recurse_copy($dirshopcommon, $fileshopcommon, 1);
            $this->com->del_DirAndFile($dirshopcommon);
            $this->addUserLog("保存发布模版", '');
            return AjaxReturn(1);
        }
        return AjaxReturn(0);
    }

    /*
     * 店铺头部
     */

    public function shopHeaderMode() {
        $result = array('content' => '', 'mode' => '');
        $result['mode'] = trim(request()->post('mode', ''));
        $temp = $result['mode'];
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);
        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }

        $spec_attr['header_type'] = $spec_attr['header_type'] ? $spec_attr['header_type'] : 'defalt_type';
        $spec_attr['suffix'] = request()->post('suffix') ? addslashes(request()->post('suffix')) : '';

        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 导航栏获取商品链接
     *
     * @return unknown
     */
    public function getSearchGoods() {
        $search_text = request()->post("search_text", "");
        $condition = array(
            "goods_name" => ["like", "%".$search_text."%"]
        );
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $goods_detail = new GoodsService();
        $result = $goods_detail->getSearchGoodsList(1, 0, $condition);
        return $result;
    }

    /**
     * 轮播编辑
     *
     * @return view
     */
    public function banner() {
        $result = array('content' => '', 'sgs' => '', 'mode' => '');
        $temp = 'shop_banner';
        $result['mode'] = request()->post('mode', '');
        $str_spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $str_spec_attr = $this->com->json_str_iconv($str_spec_attr);
        $str_spec_attr = !empty($str_spec_attr) ? stripslashes($str_spec_attr) : '';

        if (!empty($str_spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($str_spec_attr);

            $spec_attr = $this->com->object_to_array($spec_attr);
        }

        $pic_src = (isset($spec_attr['pic_src']) && ($spec_attr['pic_src'] != ',') ? $spec_attr['pic_src'] : array());
        $opennew = (isset($spec_attr['opennew']) && ($spec_attr['opennew'] != ',') ? $spec_attr['opennew'] : array());
        $link = (!empty($spec_attr['link']) && ($spec_attr['link'] != ',') ? explode(',', $spec_attr['link']) : array());
        $pic_link = (!empty($spec_attr['pic_link']) && ($spec_attr['pic_link'] != ',') ? explode(',', $spec_attr['pic_link']) : array());
        $pic_number = request()->post('pic_number', 0);
        $result['diff'] = request()->post('diff', 0);
        $count = COUNT($pic_src);
        $arr = array();

        for ($i = 0; $i < $count; $i++) {
            if ($pic_src[$i]) {

                $arr[$i + 1]['pic_src'] = $pic_src[$i];
                $arr[$i + 1]['opennew'] = $opennew[$i];

                if (!empty($link)) {
                    if (isset($link[$i])) {
                        $arr[$i + 1]['link'] = str_replace(array('＆'), '&', $link[$i]);
                    } else {
                        $arr[$i + 1]['link'] = $link[$i];
                    }
                }
                if (!empty($pic_link)) {
                    if (isset($pic_link[$i])) {
                        $arr[$i + 1]['pic_link'] = str_replace(array('＆'), '&', $pic_link[$i]);
                    } else {
                        $arr[$i + 1]['pic_link'] = $pic_link[$i];
                    }
                }
            }
        }

        $banner_list = $arr;
        $mode = $result['mode'];
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('mode', $mode);
        $this->assign('banner_list', $banner_list);
        $this->assign('temp', $temp);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('pic_number', $pic_number);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 轮播编辑点击确定
     */
    public function addModule() {
        $json = new Json();
        $result = array('error' => 0, 'message' => '', 'content' => '', 'mode' => '');
        $res_spec_attr = $_POST['spec_attr'];
        $result['spec_attr'] = stripslashes($res_spec_attr);

//        $res_spec_attr = $_POST['spec_attr'];
        if ($res_spec_attr) {
            $res_spec_attr = strip_tags(urldecode($res_spec_attr));
            $res_spec_attr = $this->com->json_str_iconv($res_spec_attr);

            if (!empty($res_spec_attr)) {
                $spec_attr = $json->decode($res_spec_attr);
                $spec_attr = $this->com->object_to_array($spec_attr);
            }
        }
        $pic_src = isset($spec_attr['pic_src']) ? $spec_attr['pic_src'] : array();
        $link = (isset($spec_attr['link']) && ($spec_attr['link'] != ',') ? explode(',', $spec_attr['link']) : array());
        $pic_link = (isset($spec_attr['pic_link']) && ($spec_attr['pic_link'] != ',') ? explode(',', $spec_attr['pic_link']) : array());
        $opennew = isset($spec_attr['opennew']) ? $spec_attr['opennew'] : array();
        $result['mode'] = request()->post('mode', '');
        $is_li = (isset($spec_attr['is_li']) ? intval($spec_attr['is_li']) : 0);
        $result['diff'] = intval(request()->post('diff', 0));
        $count = COUNT($pic_src);
        $arr = array();

        for ($i = 0; $i < $count; $i++) {
            if (!empty($pic_src[$i])) {
                $arr[$i]['pic_src'] = $pic_src[$i];
            }

            if (!empty($link[$i])) {
                $arr[$i]['link'] = $link[$i];
            }
            if (!empty($pic_link[$i])) {
                $arr[$i]['pic_link'] = $pic_link[$i];
            }
            $arr[$i]['opennew'] = $opennew[$i];
        }
        $img_list = $arr;
        $attr = $spec_attr;
        $temp = 'img_list';
        $mode = $result['mode'];
        $result_json = json_encode($result);
        $this->assign('img_list', $img_list);
        $this->assign('result_json', $result_json);
        $this->assign('mode', $mode);
        $this->assign('attr', $attr);
        $this->assign('temp', $temp);
        $this->assign('is_li', $is_li);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 单图广告
     *
     * @return view
     */
    public function singleBanner() {
        $result = array('content' => '', 'sgs' => '', 'mode' => '');
        $temp = 'single_banner';
        $result['mode'] = request()->post('mode', '');
        $str_spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $str_spec_attr = $this->com->json_str_iconv($str_spec_attr);
        $str_spec_attr = !empty($str_spec_attr) ? stripslashes($str_spec_attr) : '';

        if (!empty($str_spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($str_spec_attr);

            $spec_attr = $this->com->object_to_array($spec_attr);
        }

        $pic_src = isset($spec_attr['pic_src']) ? $spec_attr['pic_src'] : '';
        $opennew = isset($spec_attr['opennew']) ? $spec_attr['opennew'] : 0;
        $link = !empty($spec_attr['link']) ? $spec_attr['link'] : '';
        $pic_link = !empty($spec_attr['pic_link']) ? $spec_attr['pic_link'] : '';
        $pic_number = (isset($_POST['pic_number']) ? intval($_POST['pic_number']) : 0);
        $result['diff'] = $_POST['diff'];
        $mode = $result['mode'];
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('mode', $mode);
        $this->assign('temp', $temp);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('pic_number', $pic_number);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 单图广告点击确定
     */
    public function addSingleBanner() {
        $json = new Json();
        $result = array('error' => 0, 'message' => '', 'content' => '', 'mode' => '');
        $res_spec_attr = $_POST['spec_attr'];
        $result['spec_attr'] = stripslashes($res_spec_attr);
        $result['mode'] = request()->post('mode', '');
//        $res_spec_attr = $_POST['spec_attr'];
        if ($res_spec_attr) {
            $res_spec_attr = strip_tags(urldecode($res_spec_attr));
            $res_spec_attr = $this->com->json_str_iconv($res_spec_attr);

            if (!empty($res_spec_attr)) {
                $spec_attr = $json->decode($res_spec_attr);
                $spec_attr = $this->com->object_to_array($spec_attr);
            }
        }
        $result['diff'] = intval(request()->post('diff', 0));
        $temp = 'add_single_banner';
        $mode = $result['mode'];
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('mode', $mode);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 保存操作处理
     */
    public function filePutVisual() {
        $result = array('suffix' => '', 'error' => '');
        $temp = intval(request()->post('temp', 0));
        $temp_type = request()->post('temp_type', '');
        $content = $_POST['content'] ? $this->com->unescape($_POST['content']) : '';
        $content = (!empty($content) ? stripslashes($content) : '');
        $content_html = $_POST['content_html'] ? $this->com->unescape($_POST['content_html']) : '';
        $content_html = (!empty($content_html) ? stripslashes($content_html) : '');
        $bottom_html = $_POST['bottom_html'] ? $this->com->unescape($_POST['bottom_html']) : '';
        $bottom_html = (!empty($bottom_html) ? stripslashes($bottom_html) : '');
        $shopbanner_html = $_POST['shopbanner_html'] ? $this->com->unescape($_POST['shopbanner_html']) : '';
        $shopbanner_html = (!empty($shopbanner_html) ? stripslashes($shopbanner_html) : '');

        $des = $this->dir . '/' . $temp_type;
        $suffix = request()->post('suffix', '') ? addslashes(request()->post('suffix', '')) : $this->com->get_new_dirName(0, $des);
        $pc_page_name = 'pc_page.php';
        $type = 0;
        if ($temp == 1) {
            $pc_html_name = 'nav_html.php';
        } else if ($temp == 2) {
            $pc_html_name = 'topBanner.php';
        } else if ($temp == 4) {
            $type = 2;
            $pc_html_name = 'shopbanner.php';
        } else if ($temp == 3) {
            $type = 1;
            $pc_html_name = 'bottom.php';
        } else if ($temp == 5) {
            $pc_html_name = 'header.php';
        } else {
            $pc_html_name = 'pc_html.php';
        }
        $this->com->create_html($content_html, $pc_html_name, $suffix, $temp_type, $type);
        if ($temp != 1 && $temp != 2 && $temp != 4 && $temp != 5) {
            $this->com->create_html($bottom_html, 'bottom_html.php', $suffix, $temp_type, 1);
        }
        if ($temp == 4) {
            $this->com->create_html($shopbanner_html, 'shopbanner_html.php', $suffix, $temp_type, 2);
        }
        $this->com->create_html($content, $pc_page_name, $suffix, $temp_type);
        $result['error'] = 0;
        $result['suffix'] = $suffix;
        exit(json_encode($result));
    }

    /*
     * 获取商品分类用于选择链接
     */

    public function getCategoryListForLink() {
        $goods_category = new GoodsCategory();
        $goods_category_list = $goods_category->getFormatGoodsCategoryList();
        return $goods_category_list;
    }

    /**
     * 首页广告、达人、活动、推荐店铺编辑获取 图片内容
     */
    public function hot() {
        $result = array('content' => '', 'mode' => '');
        $spec_attr['needColor'] = '';
        $result['diff'] = intval(request()->post('diff', 0));
        $masterTitle = request()->post('masterTitle', '') && request()->post('masterTitle', '') != 'undefined' ? trim($this->com->unescape(request()->post('masterTitle', ''))) : '';
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);
        $html_name = '';
        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }
        $result['mode'] = trim(request()->post('mode', ''));

        $temp = $result['mode'];
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('temp', $temp);
        $this->assign('masterTitle', $masterTitle);
        $this->assign('spec_attr', $spec_attr);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 热门活动确定
     */
    public function hotInsert() {
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $result['moded'] = trim(request()->post('mode', ''));
        $original_img = request()->post('original_img/a', '');
        $opennew = request()->post('opennew/a', '');
        $pic_id = request()->post('pic_id/a', '');
        $masterTitle = request()->post('masterTitle', '');
        $url = request()->post('url/a', '');
        $arr = array();
        $count = count($original_img);
        if ($result['moded'] == "h-storeRec") {
            $count = 4;
        }

        for ($i = 0; $i < $count; $i++) {
            if ($url[$i]) {
                $arr[$i]['url'] = $this->com->setRewrite($url[$i]);
            } else {
                $arr[$i]['url'] = $url[$i];
            }
            if ($opennew[$i]) {
                $arr[$i]['opennew'] = 1;
            } else {
                $arr[$i]['opennew'] = 0;
            }
            $arr[$i]['original_img'] = $original_img[$i];
            $arr[$i]['pic_id'] = $pic_id[$i];
        }

        $spec_attr = $arr;

        $temp = $result['moded'];
        $result['masterTitle'] = $masterTitle;
        $result['spec_attr'] = $arr;
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('arr', $arr);
        $this->assign('temp', $temp . '-insert');
        $this->assign('masterTitle', $masterTitle);
        $this->assign('spec_attr', $spec_attr);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 楼层
     */
    public function homeFloor() {
        $result = array('content' => '', 'mode' => '');
        $result['act'] = request()->post('act', '');
        $result['diff'] = intval(request()->post('diff', 0));
        $result['mode'] = trim(request()->post('mode', ''));
        $result['hierarchy'] = trim(request()->post('hierarchy', ''));
        $lift = trim(request()->post('lift', ''));
        $str_spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $str_spec_attr = $this->com->json_str_iconv($str_spec_attr);
        $str_spec_attr = !empty($str_spec_attr) ? stripslashes($str_spec_attr) : '';

        if (!empty($str_spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($str_spec_attr);
            $spec_attr = $this->com->object_to_array($spec_attr);
        }

        $lb = (isset($spec_attr['leftBanner']) && ($spec_attr['leftBanner'] != ',') ? $spec_attr['leftBanner'] : array());
        $lbOpennew = (isset($spec_attr['leftBannerOpennew']) && ($spec_attr['leftBannerOpennew'] != ',') ? $spec_attr['leftBannerOpennew'] : array());
        $lbUrlLink = (!empty($spec_attr['leftBannerUrlLink']) && ($spec_attr['leftBannerUrlLink'] != ',') ? explode(',', $spec_attr['leftBannerUrlLink']) : array());
        $lbLink = (!empty($spec_attr['leftBannerLink']) && ($spec_attr['leftBannerLink'] != ',') ? explode(',', $spec_attr['leftBannerLink']) : array());
        $la = (isset($spec_attr['leftAdv']) && ($spec_attr['leftAdv'] != ',') ? $spec_attr['leftAdv'] : array());
        $laOpennew = (isset($spec_attr['leftAdvOpennew']) && ($spec_attr['leftAdvOpennew'] != ',') ? $spec_attr['leftAdvOpennew'] : array());
        $laUrlLink = (!empty($spec_attr['leftAdvUrlLink']) && ($spec_attr['leftAdvUrlLink'] != ',') ? explode(',', $spec_attr['leftAdvUrlLink']) : array());
        $laLink = (!empty($spec_attr['leftAdvLink']) && ($spec_attr['leftAdvLink'] != ',') ? explode(',', $spec_attr['leftAdvLink']) : array());
        $lcOpennew = (isset($spec_attr['lcOpennew']) && ($spec_attr['lcOpennew'] != ',') ? $spec_attr['lcOpennew'] : array());
        $lcName = $spec_attr['lcName'];
        $lcLink = (!empty($spec_attr['lcLink']) && ($spec_attr['lcLink'] != ',') ? explode(',', $spec_attr['lcLink']) : array());
        $countLc = count($lcName); //分类数量
        $countLa = 8; //展位内容暂时固定8个
        $countLb = count($lb); //主推轮播图数量
        $leftAdvList = array();
        $leftBannerList = array();
        $lcList = array();
        for ($i = 0; $i < $countLc; $i++) {
            $lcList[$i + 1]['name'] = $lcName[$i];
            $lcList[$i + 1]['opennew'] = $lcOpennew[$i];

            if (!empty($lcLink)) {
                if (isset($lcLink[$i])) {
                    $lcList[$i + 1]['link'] = str_replace(array('＆'), '&', $lcLink[$i]);
                } else {
                    $lcList[$i + 1]['link'] = $lcLink[$i];
                }
            }
        }
        for ($i = 0; $i < $countLb; $i++) {
            if ($lb[$i]) {

                $leftBannerList[$i + 1]['pic_src'] = $lb[$i];
                $leftBannerList[$i + 1]['opennew'] = $lbOpennew[$i];

                if (!empty($lbUrlLink)) {
                    if (isset($lbUrlLink[$i])) {
                        $leftBannerList[$i + 1]['link'] = str_replace(array('＆'), '&', $lbUrlLink[$i]);
                    } else {
                        $leftBannerList[$i + 1]['link'] = $lbUrlLink[$i];
                    }
                }
                if (!empty($lbLink)) {
                    if (isset($lbLink[$i])) {
                        $leftBannerList[$i + 1]['pic_link'] = str_replace(array('＆'), '&', $lbLink[$i]);
                    } else {
                        $leftBannerList[$i + 1]['pic_link'] = $lbLink[$i];
                    }
                }
            }
        }
        for ($i = 0; $i < $countLa; $i++) {
            $leftAdvList[$i + 1]['pic_src'] = $la[$i];
            $leftAdvList[$i + 1]['opennew'] = $laOpennew[$i];

            if (!empty($laUrlLink)) {
                if (isset($laUrlLink[$i])) {
                    $leftAdvList[$i + 1]['link'] = str_replace(array('＆'), '&', $laUrlLink[$i]);
                } else {
                    $leftAdvList[$i + 1]['link'] = $laUrlLink[$i];
                }
            }
            if (!empty($laLink)) {
                if (isset($laLink[$i])) {
                    $leftAdvList[$i + 1]['pic_link'] = str_replace(array('＆'), '&', $laLink[$i]);
                } else {
                    $leftAdvList[$i + 1]['pic_link'] = $laLink[$i];
                }
            }
        }
        $spec_attr['lcList'] = $lcList;
        $spec_attr['leftBannerList'] = $leftBannerList;
        $spec_attr['leftAdvList'] = $leftAdvList;
        $temp = $result['act'];
        $mode = $result['mode'];
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('temp', $temp);
        $this->assign('mode', $mode);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('lift', $lift);
        $this->fetch('template/' . $this->module . '/modalFrame');
        //$this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 楼层回调
     */
    public function homeFloorResponse() {
        $json = new Json();
        $result = array('content' => '');
        $res_spec_attr = $_POST['spec_attr'];
        $result['spec_attr'] = stripslashes($res_spec_attr);
        if ($res_spec_attr) {
            $res_spec_attr = strip_tags(urldecode($res_spec_attr));
            $res_spec_attr = $this->com->json_str_iconv($res_spec_attr);

            if (!empty($res_spec_attr)) {
                $spec_attr = $json->decode($res_spec_attr);
                $spec_attr = $this->com->object_to_array($spec_attr);
            }
        }
        $result['mode'] = trim(request()->post('mode', ''));
        $result['diff'] = intval(request()->post('diff', 0));
        $lb = (isset($spec_attr['leftBanner']) && ($spec_attr['leftBanner'] != ',') ? $spec_attr['leftBanner'] : array());
        $lbOpennew = (isset($spec_attr['leftBannerOpennew']) && ($spec_attr['leftBannerOpennew'] != ',') ? $spec_attr['leftBannerOpennew'] : array());
        $lbUrlLink = (!empty($spec_attr['leftBannerUrlLink']) && ($spec_attr['leftBannerUrlLink'] != ',') ? explode(',', $spec_attr['leftBannerUrlLink']) : array());
        $lbLink = (!empty($spec_attr['leftBannerLink']) && ($spec_attr['leftBannerLink'] != ',') ? explode(',', $spec_attr['leftBannerLink']) : array());
        $la = (isset($spec_attr['leftAdv']) && ($spec_attr['leftAdv'] != ',') ? $spec_attr['leftAdv'] : array());
        $laOpennew = (isset($spec_attr['leftAdvOpennew']) && ($spec_attr['leftAdvOpennew'] != ',') ? $spec_attr['leftAdvOpennew'] : array());
        $laUrlLink = (!empty($spec_attr['leftAdvUrlLink']) && ($spec_attr['leftAdvUrlLink'] != ',') ? explode(',', $spec_attr['leftAdvUrlLink']) : array());
        $laLink = (!empty($spec_attr['leftAdvLink']) && ($spec_attr['leftAdvLink'] != ',') ? explode(',', $spec_attr['leftAdvLink']) : array());
        $lcOpennew = (isset($spec_attr['lcOpennew']) && ($spec_attr['lcOpennew'] != ',') ? $spec_attr['lcOpennew'] : array());
        $lcName = $spec_attr['lcName'];
        $lcLink = (!empty($spec_attr['lcLink']) && ($spec_attr['lcLink'] != ',') ? explode(',', $spec_attr['lcLink']) : array());
        $countLc = count($lcName); //分类数量
        $countLa = 8; //展位内容暂时固定8个
        $countLb = count($lb); //主推轮播图数量
        $leftAdvList = array();
        $leftBannerList = array();
        $lcList = array();
        for ($i = 0; $i < $countLc; $i++) {
            $lcList[$i + 1]['name'] = $lcName[$i];
            $lcList[$i + 1]['opennew'] = $lcOpennew[$i];

            if (!empty($lcLink)) {
                if (isset($lcLink[$i])) {
                    $lcList[$i + 1]['link'] = str_replace(array('＆'), '&', $lcLink[$i]);
                } else {
                    $lcList[$i + 1]['link'] = $lcLink[$i];
                }
            }
        }
        for ($i = 0; $i < $countLb; $i++) {
            if ($lb[$i]) {

                $leftBannerList[$i + 1]['pic_src'] = $lb[$i];
                $leftBannerList[$i + 1]['opennew'] = $lbOpennew[$i];

                if (!empty($lbUrlLink)) {
                    if (isset($lbUrlLink[$i])) {
                        $leftBannerList[$i + 1]['link'] = str_replace(array('＆'), '&', $lbUrlLink[$i]);
                    } else {
                        $leftBannerList[$i + 1]['link'] = $lbUrlLink[$i];
                    }
                }
                if (!empty($lbLink)) {
                    if (isset($lbLink[$i])) {
                        $leftBannerList[$i + 1]['pic_link'] = str_replace(array('＆'), '&', $lbLink[$i]);
                    } else {
                        $leftBannerList[$i + 1]['pic_link'] = $lbLink[$i];
                    }
                }
            }
        }
        for ($i = 0; $i < $countLa; $i++) {
            $leftAdvList[$i + 1]['pic_src'] = $la[$i];
            $leftAdvList[$i + 1]['opennew'] = $laOpennew[$i];

            if (!empty($laUrlLink)) {
                if (isset($laUrlLink[$i])) {
                    $leftAdvList[$i + 1]['link'] = str_replace(array('＆'), '&', $laUrlLink[$i]);
                } else {
                    $leftAdvList[$i + 1]['link'] = $laUrlLink[$i];
                }
            }
            if (!empty($laLink)) {
                if (isset($laLink[$i])) {
                    $leftAdvList[$i + 1]['pic_link'] = str_replace(array('＆'), '&', $laLink[$i]);
                } else {
                    $leftAdvList[$i + 1]['pic_link'] = $laLink[$i];
                }
            }
        }
        $spec_attr['leftBannerList'] = $leftBannerList;
        $spec_attr['leftAdvList'] = $leftAdvList;
        $spec_attr['lcList'] = $lcList;
        $temp = $result['mode'] . 'Response';
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('temp', $temp);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('result_json', $result_json);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /*
     * 客服中心
     */

    public function serviceMode() {
        $result = array('content' => '', 'mode' => '');
        $result['mode'] = trim(request()->post('mode', ''));
        $count = intval(request()->post('count', 0));
        $temp = $result['mode'];
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }
        if ($spec_attr['qq']) {
            foreach ($spec_attr['qq'] as $key => $qq) {
                if (!$qq) {
                    continue;
                }
                $spec_attr['servicelist'][$key] = array('qq' => $qq, 'servicepic' => $spec_attr['servicepic'][$key], 'name' => $spec_attr['name'][$key]);
            }
            unset($qq);
        }
        $result['diff'] = intval(request()->post('diff', 0));
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->assign('count', $count);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /*
     * 客服中心回调
     */

    public function serviceModeBack() {
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $result['mode'] = request()->post('mode', '');
        $result['spec_attr'] = stripslashes($_POST['spec_attr']);
        $result['diff'] = intval(request()->post('diff', 0));
        $spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($spec_attr);
            $spec_attr = $this->com->object_to_array($spec_attr);
        }
        if ($spec_attr['qq']) {
            foreach ($spec_attr['qq'] as $key => $qq) {
                if (!$qq) {
                    continue;
                }
                $spec_attr['servicelist'][$key] = array('qq' => $qq, 'servicepic' => $spec_attr['servicepic'][$key], 'name' => $spec_attr['name'][$key]);
            }
            unset($qq);
        }
        $temp = 'service_home';
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /*
     * 自定义区
     */

    public function custom() {
        $result = array('content' => '', 'mode' => '');
        $custom_content = $this->com->unescape($_POST['custom_content']);
        $custom_content = (!empty($custom_content) ? stripslashes($custom_content) : '');
        $result['mode'] = request()->post('mode', '');
        $result['diff'] = intval(request()->post('diff', 0));
        $lift = trim(request()->post('lift', ''));
        $FCKeditor = $this->com->create_ueditor_editor('custom_content', $custom_content, 486, 1);

        $temp = $result['mode'];
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('temp', $temp);
        $this->assign('lift', $lift);
        $this->assign('FCKeditor', $FCKeditor);
        $this->assign('custom_content', htmlspecialchars($custom_content));
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

     /**
     * 导航编辑
     */
    public function navMode() {
        $result = array('content' => '', 'mode' => '');
        $result['mode'] = trim(request()->post('mode', ''));
        $result['topic'] = intval(request()->post('topic', 0));
        $temp_type = request()->post('template_type', '');
        $temp = $result['mode'];
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);
        $type = 'index';
        if ($temp_type != 'home_templates') {
            $type = 'shop';
        }
        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }
        $nav = new SysPcCustomNavModel();
        $navigator = $nav->getQuery(['website_id' => $this->website_id, 'type' => $type, 'shop_id' => $this->instance_id], 'id, name, ifshow, vieworder, opennew, url, type', '');

        $topic_type = $result['topic'];

        $result_json = json_encode($result);
        $this->assign('topic_type', $topic_type);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('result_json', $result_json);
        $this->assign('navigator', $navigator);
        $this->assign('temp_type', $temp_type);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    public function navModeBack() {
        $json = new Json();
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $result['mode'] = request()->post('mode', '');
        $res_spec_attr = $_POST['spec_attr'];
        $result['spec_attr'] = stripslashes($res_spec_attr);
        $code = trim(request()->post('code', ''));
        $temp_type = request()->post('template_type', '');
        if ($temp_type == 'home_templates') {
            $type = 'index';
        } else {
            $type = 'shop';
        }
        if ($res_spec_attr) {
            $res_spec_attr = strip_tags(urldecode($res_spec_attr));
            $res_spec_attr = $this->com->json_str_iconv($res_spec_attr);

            if (!empty($res_spec_attr)) {
                $spec_attr = $json->decode($res_spec_attr);
                $spec_attr = $this->com->object_to_array($spec_attr);
            }
        }
        $navname = isset($spec_attr['navname']) ? $spec_attr['navname'] : array();
        $navurl = (isset($spec_attr['navurl']) && ($spec_attr['navurl'] != ',') ? explode(',', $spec_attr['navurl']) : array());
        $navid = (isset($spec_attr['navid']) ? $spec_attr['navid'] : array());
        $opennew = isset($spec_attr['opennew']) ? $spec_attr['opennew'] : array();
        $showcat = isset($spec_attr['showcat']) ? $spec_attr['showcat'] : array();
        $slide = isset($spec_attr['slide']) ? $spec_attr['slide'] : array();
        $nav = new SysPcCustomNavModel();
        $count = COUNT($navname);
        for ($i = 0; $i < $count; $i++) {
            if (!$navname[$i]) {
                continue;
            }
            if ($navid[$i]) {
                $data = [
                    'name' => $navname[$i],
                    'url' => $navurl[$i],
                    'opennew' => $opennew[$i],
                    'vieworder' => $i,
                    'website_id' => $this->website_id,
                    'shop_id' => $this->instance_id
                ];
                $where = ['id' => $navid[$i]];
                $this->addUserLog('导航编辑', $navid[$i] . '-' . $navname[$i]);
            } else {
                $data = [
                    'name' => $navname[$i],
                    'url' => $navurl[$i],
                    'opennew' => $opennew[$i],
                    'ifshow' => 1,
                    'type' => $type,
                    'vieworder' => $i,
                    'website_id' => $this->website_id,
                    'shop_id' => $this->instance_id
                ];
                $where = [];
                $this->addUserLog('导航添加', $navname[$i]);
            }
            $nav = new SysPcCustomNavModel();
            $nav->save($data, $where);
        }
        $navs = new SysPcCustomNavModel();
        $navigator = $navs->getQuery(['website_id' => $this->website_id, 'type' => $type, 'ifshow' => 1, 'shop_id' => $this->instance_id], 'name, url,opennew', ' vieworder asc');
        if ($result['mode'] == 'home_nav_mode') {
            $temp = 'home_nav_mode_response';
            $navConfig = new SysPcCustomNavConfigModel();
            $check = $navConfig->getInfo(['website_id' => $this->website_id, 'code' => $code, 'template_type' => $temp_type, 'shop_id' => $this->instance_id]);
            if ($check) {
                $navConfig->save(['slide' => $spec_attr['slide'], 'showcat' => $spec_attr['showcat']], ['id' => $check['id']]);
            } else {
                $navConfig->save(['website_id' => $this->website_id, 'slide' => $spec_attr['slide'], 'showcat' => $spec_attr['showcat'], 'shop_id' => $this->instance_id, 'code' => $code, 'template_type' => $temp_type]);
            }
        } else {
            $temp = 'navigator_home';
        }
        $attr = $spec_attr;
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('attr', $attr);
        $this->assign('temp', $temp);
        $this->assign('navigator', $navigator);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }


    /**
     * 导航删除
     */
    public function navRemove() {
        $id = request()->post('id', 0);
        $nav = new SysPcCustomNavModel();
        $check = $nav->getInfo(['id' => $id]);
        if (!$check) {
            exit(json_encode(['code' => 2, 'message' => '导航不存在']));
        }
        $result = $nav->destroy(['id' => $id, 'website_id' => $this->website_id]);
        if (!$result) {
            exit(json_encode(['code' => 0, 'message' => '操作失败']));
        } else {
            $this->addUserLog('导航删除', $check['name']);
            exit(json_encode(['code' => 1, 'message' => '操作成功']));
        }
    }

    /**
     * 商品推荐
     */
    public function goodsInfo() {
        $result = array('content' => '', 'mode' => '');
        $search_type = trim(request()->post('search_type', ''));
        $goods_id = intval(request()->post('goods_id', 0));
        $cat_id = intval(request()->post('cat_id', 0));
        $goods_type = intval(request()->post('goods_type', 0));
        $good_number = intval(request()->post('good_number', 0));
        $spec_attr = $_POST['spec_attr'] ? strip_tags(urldecode($_POST['spec_attr'])) : '';
        $spec_attr = $spec_attr ? $this->com->json_str_iconv($spec_attr) : '';
        $spec_attr = !empty($spec_attr) ? stripslashes($spec_attr) : '';

        if (!empty($spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode(stripslashes($spec_attr));
            $spec_attr = $this->com->object_to_array($spec_attr);
        }

        $spec_attr['is_title'] = isset($spec_attr['is_title']) ? $spec_attr['is_title'] : 0;
        $spec_attr['itemsLayout'] = isset($spec_attr['itemsLayout']) ? $spec_attr['itemsLayout'] : 'row4';
        $result['mode'] = addslashes(request()->post('mode', ''));
        $result['diff'] = intval(request()->post('diff', 0));
        $lift = trim(request()->post('lift', 0));
        $spec_attr['goods_ids'] = $this->com->resetBarnd($spec_attr['goods_ids'], 'goods');

        if ($spec_attr['goods_ids']) {
            $goods_info = explode(',', $spec_attr['goods_ids']);
            foreach ($goods_info as $k => $v) {
                if (!$v) {
                    unset($goods_info[$k]);
                }
            }

            if (!empty($goods_info)) {
                $goodsModel = new Goods();
                $goods_list = $goodsModel->getGoodsList(1, 0, ['ng.website_id' => $this->website_id, 'ng.state' => 1, 'ng.goods_id' => ['in', implode(',', $goods_info)]], '');
                $goods_list = $goods_list['data'];
                $goods_count = count($goods_list);
            }
        }
        $temp = 'goods_info';
        $mode = $result['mode'];
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('mode', $mode);
        $this->assign('temp', $temp);
        $this->assign('arr', $spec_attr);
        $this->assign('goods_count', $goods_count);
        $this->assign('search_type', $search_type);
        $this->assign('goods_id', $goods_id);
        $this->assign('goods_type', $goods_type);
        $this->assign('good_number', $good_number);
        $this->assign('lift', $lift);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 商品推荐
     */
    public function changedGoods() {
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $spec_attr = array();
        $search_type = trim(request()->post('search_type', ''));
        $goods_id = intval(request()->post('goods_id', 0));
        $rec_type = intval(request()->post('rec_type', 0));
        $result['lift'] = trim(request()->post('lift', ''));
        $result['spec_attr'] = $_POST['spec_attr'];
        if ($_POST['spec_attr']) {
            $spec_attr = strip_tags(urldecode($_POST['spec_attr']));
            $spec_attr = $this->com->json_str_iconv($spec_attr);
            if (!empty($spec_attr)) {
                $json = new Json();
                $spec_attr = $json->decode($spec_attr);
                $spec_attr = $this->com->object_to_array($spec_attr);
            }
        }
        $keyword = trim(request()->post('keyword', ''));
        $goodsAttr = (isset($spec_attr['goods_ids']) ? explode(',', $spec_attr['goods_ids']) : array());
        $goods_ids = request()->post('goods_ids') ? explode(',', request()->post('goods_ids')) : array();
        $result['goods_ids'] = !empty($goodsAttr) ? $goodsAttr : $goods_ids;
        $spec_attr['goods_ids'] = $this->com->resetBarnd($spec_attr['goods_ids']);
        $result['cat_name'] = $spec_attr['cat_name'] ? addslashes($spec_attr['cat_name']) : '';
        $result['sort'] = $spec_attr['sort'] ? intval($spec_attr['sort']) : 0;
        $result['count'] = $spec_attr['count'] ? intval($spec_attr['count']) : 0;
        $result['rec_type'] = $spec_attr['rec_type'] ? intval($spec_attr['rec_type']) : 0;
        $result['diff'] = request()->post('diff', 0);
        $type = intval(request()->post('type', 0));
        $temp = request()->post('temp', 'goods_list');
        $result['mode'] = request()->post('mode', '');
        $condition = [
            'ng.website_id' => $this->website_id,
            'ng.shop_id' => $this->instance_id,
            'ng.state' => 1
        ];
        if ($keyword) {
            $condition['ng.goods_name'] = ['like', "%" . $keyword . "%"];
        }

        if ($type == 0 && $rec_type == 0 && $result['rec_type'] == 0) {
            $condition['ng.goods_id'] = ['in', $this->com->db_create_in($result['goods_ids'])];
        }
        switch ($result['sort']) {
            case '1':
                $sort = ' ng.create_time ASC';
                break;

            case '2':
                $sort = ' ng.create_time DESC';
                break;

            case '3':
                $sort = ' ng.sales ASC';
                break;

            case '4':
                $sort = ' ng.sales DESC';
                break;

            case '5':
                $sort = ' ng.collects ASC';
                break;

            case '6':
                $sort = ' ng.collects DESC';
                break;
        }
        if (!$result['rec_type']) {
            if ($type == 1) {
                $list = $this->com->getGoodslist($condition, $sort);
                $goods_list = $list['list'];
                $filter = $list['filter'];
                $filter['cat_id'] = 0;
                $filter['sort_order'] = $sort;
                $filter['keyword'] = $keyword;
                $filter['search_type'] = $search_type;
                $filter['goods_id'] = $goods_id;
            } else {
                $goods = new Goods();
                $goods_list = $goods->getGoodsList(1, 0, $condition, $sort);
                $goods_list = $goods_list['data'];
            }
        } else {
            $goods = new Goods();
            $goods_list = $goods->getGoodsList(1, $result['count'], $condition, $sort);
            $goods_list = $goods_list['data'];
        }

        if (!empty($goods_list) && $result['rec_type'] == 0) {
            foreach ($goods_list as $k => $v) {
                $goods_list[$k]['url'] = '#';
                $goods_list[$k]['is_selected'] = 0;
                if ((0 < $v['goods_id']) && in_array($v['goods_id'], $result['goods_ids']) && !empty($result['goods_ids'])) {
                    $goods_list[$k]['is_selected'] = 1;
                }
            }
        }
        $goods_count = count($goods_list);
        $attr = $spec_attr;
        if ($result['goods_ids']) {
            $result['goods_ids'] = implode(',', $result['goods_ids']);
        }
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('goods_count', $goods_count);
        $this->assign('temp', $temp);
        $this->assign('goods_list', $goods_list);
        $this->assign('attr', $attr);
        $this->assign('filter', $filter);
        $this->assign('type', $type);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }
    
    /**
     * pc端预设模板列表
     *
     * @return list
     */
    public function pcDefaultTemplateList()
    {
        if (request()->isAjax()) {
            $template_type = request()->post('template_type', '');
            $aType = ['home_templates'];
            if ($template_type) {
                $aType = [$template_type];
            }
            $list = array();
            foreach ($aType as $type) {
                $dir = $this->dirDefault . '/' . $type . '/';
                if (file_exists($dir)) {
                    $template_dir = @opendir($dir);
                    while ($file = readdir($template_dir)) {
                        if (($file != '.') && ($file != '..') && ($file != '.svn') && ($file != 'index.htm')) {
                            $list[] = $this->com->get_choose_template_info($file, $type);
                        }
                    }
                } else {
                    @mkdir($dir, 0777, true);
                    @chmod($dir, 0777);
                }
            }
            if (!empty($list)) {
                foreach ($list as $atk => $atv) {
                    if (!$template_name) {
                        continue;
                    }
                    if (strpos($atv['name'], $template_name) === false) {
                        unset($list[$atk]);
                    }
                }
            }
            $list = array_values($list);
           
            return array('data' => $list);
        }
    }

    /**
     * 创建模板
     *
     * @return list
     */
    public function createTemplate(){
        $result = array('suffix' => '', 'error' => '');
        $template_type = request()->post('template_type', '') ? trim(request()->post('template_type', '')) : 'home_templates';
        $template_code = request()->post('template_code', '') ? trim(request()->post('template_code', '')) : '';
        $des = $this->dir . '/' . $template_type;
        $descommon = $this->dir_common;
        $desshopcommon = $this->dir_shop_common;
        $tem = $this->com->get_new_dirName(0, $des, $template_type);
        $name = request()->post('name', '') ? 'tpl name：' . addslashes(request()->post('name', '')) : 'tpl name：'.$tem;
        $code_dir = $des . '/' . $tem;
        $code_dir_common = $descommon;

        if (!is_dir($code_dir)) {
            $this->com->make_dir($code_dir);
        }
        if (!is_dir($code_dir_common)) {
            $this->com->make_dir($code_dir_common);
        }
        if (!is_dir($desshopcommon)) {
            $this->com->make_dir($desshopcommon);
        }
        if($template_code){
            $defaultTem = $this->dirDefault.'/'.$template_type .'/' .$template_code;
            if($this->com->is_empty_dir($defaultTem)){
                $result['error'] = 1;
                $result['message'] = '模板不存在，请稍后再试';
                exit(json_encode($result));
                die;
            }
            $html = $this->com->copydir($defaultTem, $code_dir);
        }else{
            $end = '------tpl_info------------';
            $tab = "\n";
            $html = $end . $tab . $name . $tab . $end;
            $html = $this->com->write_static_file_cache('tpl_info', iconv('UTF-8', 'GB2312', $html), 'txt', $code_dir . '/');
        }
        
        if ($html === false) {
            $result['error'] = 1;
            $result['message'] = $code_dir . '/tpl_info.txt没有写入权限，请修改权限';
            $this->com->del_DirAndFile($code_dir);
            exit(json_encode($result));
            die;
        } else {
            $template_dir = @opendir($code_dir);
            while ($file = readdir($template_dir)) {
                if (($file != '.') && ($file != '..') && ($file != '.svn') && ($file != 'index.htm')) {
                    $available_templates[] = $this->com->get_seller_template_info($file, $template_type);
                }
            }
            $available_templates = $this->com->get_array_sort($available_templates, 'sort');
            @closedir($template_dir);
            $result['code'] = $tem;
            $result['type'] = $template_type;
            $result['error'] = 0;
            $result['message'] = '操作成功';
            $this->addUserLog("新增模版页",$name);
            exit(json_encode($result));
        }
    }
    /**
     * 删除pc端自定义模板头部广告
     *
     * @return list
     */
    public function deletePcCustomTemplateTop() {
        $result = array('error' => '', 'message' => '');
        $code = request()->post('suffix', '') ? trim(request()->post('suffix', '')) : '';
        $type = request()->post('type', '') ? trim(request()->post('type', '')) : '';
        $dir = $this->dir . '/' . $type . '/' . $code;
        if (empty($code) && file_exists($dir)) {
            $result['error'] = 1;
            $result['message'] = '该模板不存在，请刷新重试';
        } else {
            if (file_exists($dir . '/topBanner.php')) {
                unlink($dir . '/topBanner.php');
            }
            if (file_exists($dir . '/temp/topBanner.php')) {
                unlink($dir . '/temp/topBanner.php');
            }
            $result['error'] = 0;
        }
        exit(json_encode($result));
    }
    /**
     * 创建模板弹窗
     *
     * @return list
     */
    public function createTemplateDialog() {
        $typeList = $this->com->getTemplateTypeName('', getAddons('shop', $this->website_id, 0, true));
        $this->assign("typeList", $typeList);
        $this->fetch('template/' . $this->module . '/createTemplate');
    }
    /*
     * 选择风格
     */

    public function changeStyle() {
        $style = request()->post('style', '');
        if (!$style) {
            return AjaxReturn(0);
        }
        $styleModel = new SysPcCustomStyleConfigModel();
        $checkStyle = $styleModel->getInfo(['website_id' => $this->website_id]);
        if (!$checkStyle) {
            $data = [
                'style' => $style,
                'website_id' => $this->website_id
            ];
            $result = $styleModel->save($data);
        } else {
            $data = [
                'style' => $style
            ];
            $result = $styleModel->save($data, ['website_id' => $this->website_id]);
        }
        if (!$result) {
            return AjaxReturn(0);
        }
        return AjaxReturn(1);
    }
    /*
     * 头部编辑回调
     */

    public function homeHeaderModeBack() {
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $result['mode'] = request()->post('mode', '');
        $result['spec_attr'] = stripslashes($_POST['spec_attr']);
        $spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($spec_attr);
            $spec_attr = $this->com->object_to_array($spec_attr);
        }
        if ($spec_attr['copylink']) {
            foreach ($spec_attr['copylink'] as $key => $link) {
                $spec_attr['copylist'][$key] = array('copylink' => $link, 'copyname' => $spec_attr['copyname'][$key], 'opennew' => $spec_attr['opennew'][$key]);
            }
            unset($link);
        }
        $temp = 'homeheader_home';
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }
    //图片空间列表
    public function pic_space() {
        //相册分类
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $album_id = intval(request()->post('album_id', 0));
        $sort_name = intval(request()->post('sort_name', 0));
        $condition['album_id'] = $album_id;
        $album = new Album();
        $order = '';
        if (0 < $sort_name) {
            switch ($sort_name) {
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
        $list = $album->getPictureList($page_index, $page_size, $condition, $order);
        $this->assign('list', $list);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 头部编辑
     */
    public function homeHeaderMode() {
        $result = array('content' => '', 'mode' => '');
        $result['mode'] = trim(request()->post('mode', ''));

        $temp = $result['mode'];
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }
        if ($spec_attr['copylink']) {
            foreach ($spec_attr['copylink'] as $key => $link) {
                $spec_attr['copylist'][$key] = array('copylink' => $link, 'copyname' => $spec_attr['copyname'][$key], 'opennew' => $spec_attr['opennew'][$key]);
            }
            unset($link);
        }
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }
    /**
     * 帮助中心
     */
    public function helpMode() {
        $result = array('content' => '', 'mode' => '');
        $result['mode'] = trim(request()->post('mode', ''));

        $temp = $result['mode'];
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }
        $article = new helpServer();
        
        if ($spec_attr['articleclass']) {
            foreach ($spec_attr['articleclass'] as $key => $class_id) {
                $spec_attr['copylist'][$key] = array('cate_id' => $class_id, 'childcount' => $spec_attr['childcount'][$key], 'articlesort' => $spec_attr['articlesort'][$key]);
            }
            unset($class_id);
        }
        $articleClassList = array();
        $list = $article->questionCateList(1, 0, ['website_id' => $this->website_id,'status' => 1]);
        if ($list['data']) {
            $articleClassList = $list['data'];
        }
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('articleClassList', $articleClassList);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    public function helpModeBack() {
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $result['mode'] = request()->post('mode', '');
        $result['spec_attr'] = stripslashes($_POST['spec_attr']);
        $spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($spec_attr);
            $spec_attr = $this->com->object_to_array($spec_attr);
        }
        $article = new helpServer();
        if ($spec_attr['articleclass']) {
            foreach ($spec_attr['articleclass'] as $key => $class_id) {
                $spec_attr['copylist'][$key] = array('class_id' => $class_id, 'childcount' => $spec_attr['childcount'][$key], 'articlesort' => $spec_attr['articlesort'][$key]);
                $sort = $spec_attr['articlesort'][$key];
                switch ($sort) {
                    case '1':
                        $order = 'vq.create_time asc';
                        break;
                    case '2':
                        $order = 'vq.create_time desc';
                        break;
                    case '3':
                        $order = 'vq.create_time desc';
                        break;
                    case '4':
                        $order = 'vq.create_time desc';
                        break;
                    case '5':
                        $order = 'vq.create_time desc';
                        break;
                }
                $articleClassDetail = $article->questionCateDetail($class_id);
                $spec_attr['copylist'][$key]['name'] = $articleClassDetail['name'];
                $articleList = $article->questionList(1, $spec_attr['childcount'][$key], ['vq.website_id' => $this->website_id, 'vq.cate_id' => $class_id], $order);
                $spec_attr['copylist'][$key]['articlelist'] = $articleList['data'] ? $articleList['data'] : array();
            }
            unset($class_id);
        }
        $temp = 'help_home';
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 友情链接
     */
    public function linkMode() {
        $result = array('content' => '', 'mode' => '');
        $result['mode'] = trim(request()->post('mode', ''));

        $temp = $result['mode'];
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }
        if ($spec_attr['copylink']) {
            foreach ($spec_attr['copylink'] as $key => $link) {
                $spec_attr['copylist'][$key] = array('copylink' => $link, 'copyname' => $spec_attr['copyname'][$key], 'opennew' => $spec_attr['opennew'][$key]);
            }
            unset($link);
        }
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    public function linkModeBack() {
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $result['mode'] = request()->post('mode', '');
        $result['spec_attr'] = stripslashes($_POST['spec_attr']);
        $spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($spec_attr);
            $spec_attr = $this->com->object_to_array($spec_attr);
        }
        if ($spec_attr['copylink']) {
            foreach ($spec_attr['copylink'] as $key => $link) {
                $spec_attr['copylist'][$key] = array('copylink' => $link, 'copyname' => $spec_attr['copyname'][$key], 'opennew' => $spec_attr['opennew'][$key]);
            }
            unset($link);
        }
        $temp = 'link_home';
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 版权信息
     */
    public function copyMode() {
        $result = array('content' => '', 'mode' => '');
        $result['mode'] = trim(request()->post('mode', ''));

        $temp = $result['mode'];
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }
        if ($spec_attr['copylink']) {
            foreach ($spec_attr['copylink'] as $key => $link) {
                $spec_attr['copylist'][$key] = array('copylink' => $link, 'copypic' => $spec_attr['copypic'][$key], 'opennew' => $spec_attr['opennew'][$key]);
            }
            unset($link);
        }
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    public function copyModeBack() {
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $result['mode'] = request()->post('mode', '');
        $result['spec_attr'] = stripslashes($_POST['spec_attr']);
        $spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($spec_attr);
            $spec_attr = $this->com->object_to_array($spec_attr);
        }
        if ($spec_attr['copylink']) {
            foreach ($spec_attr['copylink'] as $key => $link) {
                $spec_attr['copylist'][$key] = array('copylink' => $link, 'copypic' => $spec_attr['copypic'][$key], 'opennew' => $spec_attr['opennew'][$key]);
            }
            unset($link);
        }
        $temp = 'copy_home';
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 右边工具栏编辑
     */
    public function rightMode() {
        $result = array('content' => '', 'mode' => '');
        $result['mode'] = trim(request()->post('mode', ''));

        $temp = $result['mode'];
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }
        if ($spec_attr['tools']) {
            foreach ($spec_attr['tools'] as $tool) {
                $spec_attr[$tool] = 1;
            }
        }
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    public function rightModeBack() {
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $result['mode'] = request()->post('mode', '');
        $result['spec_attr'] = stripslashes($_POST['spec_attr']);
        $spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $spec_attr = $this->com->json_str_iconv($spec_attr);

        if (!empty($spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($spec_attr);
            $spec_attr = $this->com->object_to_array($spec_attr);
        }
        if ($spec_attr['tools']) {
            foreach ($spec_attr['tools'] as $tool) {
                $spec_attr[$tool] = 1;
            }
        }
        $temp = 'right_home';
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('temp', $temp);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }
    /**
     * 首页轮播图编辑
     */
    public function homeBanner() {
        $result = array('content' => '', 'mode' => '');
        $result['act'] = request()->post('act', '');
        $result['diff'] = intval(request()->post('diff', 0));
        $result['mode'] = trim(request()->post('mode', ''));
        $result['hierarchy'] = trim(request()->post('hierarchy', ''));
        $lift = trim(request()->post('lift', ''));
        $str_spec_attr = strip_tags(urldecode($_POST['spec_attr']));
        $str_spec_attr = $this->com->json_str_iconv($str_spec_attr);
        $str_spec_attr = !empty($str_spec_attr) ? stripslashes($str_spec_attr) : '';
        if (!empty($str_spec_attr)) {
            $json = new Json();
            $spec_attr = $json->decode($str_spec_attr);
            $spec_attr = $this->com->object_to_array($spec_attr);
        }
        $lb = (isset($spec_attr['leftBanner']) && ($spec_attr['leftBanner'] != ',') ? $spec_attr['leftBanner'] : array());
        $lbOpennew = (isset($spec_attr['leftBannerOpennew']) && ($spec_attr['leftBannerOpennew'] != ',') ? $spec_attr['leftBannerOpennew'] : array());
        $lbUrlLink = (!empty($spec_attr['leftBannerUrlLink']) && ($spec_attr['leftBannerUrlLink'] != ',') ? explode(',', $spec_attr['leftBannerUrlLink']) : array());
        $lbLink = (!empty($spec_attr['leftBannerLink']) && ($spec_attr['leftBannerLink'] != ',') ? explode(',', $spec_attr['leftBannerLink']) : array());
        $la = (isset($spec_attr['leftAdv']) && ($spec_attr['leftAdv'] != ',') ? $spec_attr['leftAdv'] : array());
        $laOpennew = (isset($spec_attr['leftAdvOpennew']) && ($spec_attr['leftAdvOpennew'] != ',') ? $spec_attr['leftAdvOpennew'] : array());
        $laUrlLink = (!empty($spec_attr['leftAdvUrlLink']) && ($spec_attr['leftAdvUrlLink'] != ',') ? explode(',', $spec_attr['leftAdvUrlLink']) : array());
        $laLink = (!empty($spec_attr['leftAdvLink']) && ($spec_attr['leftAdvLink'] != ',') ? explode(',', $spec_attr['leftAdvLink']) : array());
        $laName = (isset($spec_attr['leftAdvName']) && ($spec_attr['leftAdvName'] != ',') ? $spec_attr['leftAdvName'] : array());
        $countLa = 6; //展位内容暂时固定6个
        $countLb = count($lb); //主推轮播图数量

        $leftAdvList = array();
        $leftBannerList = array();
        for ($i = 0; $i < $countLb; $i++) {
            if ($lb[$i]) {

                $leftBannerList[$i + 1]['pic_src'] = $lb[$i];
                $leftBannerList[$i + 1]['opennew'] = $lbOpennew[$i];

                if (!empty($lbUrlLink)) {
                    if (isset($lbUrlLink[$i])) {
                        $leftBannerList[$i + 1]['link'] = str_replace(array('＆'), '&', $lbUrlLink[$i]);
                    } else {
                        $leftBannerList[$i + 1]['link'] = $lbUrlLink[$i];
                    }
                }
                if (!empty($lbLink)) {
                    if (isset($lbLink[$i])) {
                        $leftBannerList[$i + 1]['pic_link'] = str_replace(array('＆'), '&', $lbLink[$i]);
                    } else {
                        $leftBannerList[$i + 1]['pic_link'] = $lbLink[$i];
                    }
                }
            }
        }
        for ($i = 0; $i < $countLa; $i++) {
            $leftAdvList[$i + 1]['pic_src'] = $la[$i];
            $leftAdvList[$i + 1]['opennew'] = $laOpennew[$i];
            $leftAdvList[$i + 1]['leftAdvName'] = $laName[$i];
            if (!empty($laUrlLink)) {
                if (isset($laUrlLink[$i])) {
                    $leftAdvList[$i + 1]['link'] = str_replace(array('＆'), '&', $laUrlLink[$i]);
                } else {
                    $leftAdvList[$i + 1]['link'] = $laUrlLink[$i];
                }
            }
            if (!empty($laLink)) {
                if (isset($laLink[$i])) {
                    $leftAdvList[$i + 1]['pic_link'] = str_replace(array('＆'), '&', $laLink[$i]);
                } else {
                    $leftAdvList[$i + 1]['pic_link'] = $laLink[$i];
                }
            }
        }
        $spec_attr['leftBannerList'] = $leftBannerList;
        $spec_attr['leftAdvList'] = $leftAdvList;
        $temp = $result['act'];
        $mode = $result['mode'];
        $result_json = json_encode($result);
        $pic_number = (isset($_POST['pic_number']) ? intval($_POST['pic_number']) : 0);
        $this->assign('pic_number', $pic_number);
        $this->assign('result_json', $result_json);
        $this->assign('temp', $temp);
        $this->assign('mode', $mode);
        $this->assign('spec_attr', $spec_attr);
//        var_dump( $spec_attr);die();
        $this->assign('lift', $lift);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }
    
    /**
     *  精选好店
     */
    public function homeAdv() {
        $result = array('content' => '', 'mode' => '');
        $spec_attr['needColor'] = '';
        $result['diff'] = intval(request()->post('diff', 0));
        $result['hierarchy'] = intval(request()->post('hierarchy', 0));
        $lift = trim(request()->post('lift', ''));
        $masterTitle = request()->post('masterTitle', '') && request()->post('masterTitle', '') != 'undefined' ? trim($this->com->unescape(request()->post('masterTitle', ''))) : '';
        $spec_attr = stripslashes($_POST['spec_attr']);
        $spec_attr = urldecode($spec_attr);
        $spec_attr = $this->com->json_str_iconv($spec_attr);
        $html_name = '';
        if (!empty($spec_attr)) {
            $spec_attr = json_decode($spec_attr, true);
        }
        $result['mode'] = trim(request()->post('mode', ''));
        $needColor = $spec_attr['needColor'];

        unset($spec_attr['needColor']);
        if ($result['mode'] == 'h-shop') {
            $shop_ids = '';
            
            if ($spec_attr['selected']) {
                $shop_ids = $this->com->resetBarnd($spec_attr['selected'], 'shop');
            }
            $shop = $this->com->getShopList($shop_ids);
            $shops = $shop['list'];
            $recommend_shops = $shop['selected'];
            $this->assign('shops', $shops);
            $this->assign('shopcount', count($shops));
            $this->assign('recommendcount', count($recommend_shops));
            $this->assign('recommend_shops', $recommend_shops);
        }
        $temp = $result['mode'];
        $hierarchy = $result['hierarchy'];
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('hierarchy', $hierarchy);
        $this->assign('temp', $temp);
        $this->assign('masterTitle', $masterTitle);
        $this->assign('lift', $lift);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('needColor', $needColor);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }
    
    /**
     * 精选好店编辑确定
     */
    public function homeShop() {
        $result = array('content' => '');
        $result['moded'] = trim(request()->post('mode'));
        $result['suffixed'] = trim(request()->post('suffix'));
        $result['type'] = intval(request()->post('type', 0));
        $result['sort'] = intval(request()->post('sort', 0));
        $result['shopnumber'] = intval(request()->post('shopnumber', 0));
        $result['selected'] = request()->post('selected/a');
        if ($result['selected']) {
            $shop_ids = implode(',', $result['selected']);
        }
        $lift = trim(request()->post('lift'));
        $spec_attr = $result;
        $shop_list = array();
        $shop = new Shop();
        $where = ['website_id' => $this->website_id];
        $order = '';
        if ($result['type']) {
            switch ($result['sort']) {
                case 1:
                    $order = 'shop_create_time asc';
                    break;
                case 2:
                    $order = 'shop_create_time desc';
                    break;
                case 3:
                    $order = 'shop_sales asc';
                    break;
                case 4:
                    $order = 'shop_sales desc';
                    break;
            }
            $list = $shop->getShopList(1, $result['shopnumber'], $where, $order);
            $shop_list = $list['data'];
        } else if ($result['selected']) {
            $where['shop_id'] = ['in', $shop_ids];
            $shop_list = array();
            foreach($result['selected'] as $sval){
                $shop_list[] = $shop->getShopDetail($sval)['base_info'];
            }
        }
        
        if (!empty($shop_list)) {
            foreach ($shop_list as $key => $val) {
                $shop_list[$key]['url'] = __URLS('ADDONS_SHOP_MAIN','addons=shopIndex&shop_id='.$val['shop_id']);
                $shop_list[$key]['collect_count'] = $this->com->getCollectCount($val['shop_id'], 'shop');
            }
        }
        $shopbig = $shop_list[0];
        unset($shop_list[0]);
        $result['spec_attr'] = $spec_attr;
        $result['lift'] = $lift;
        $this->assign('shop_list', $shop_list);
        if (count($shop_list) < 17) {
            $num = 17 - count($shop_list);
            $this->assign('shop_num', $num);
        }
        $this->assign('shop_big', $shopbig);
        $this->assign('temp', $result['moded'] . '1');
        $this->assign('suffix', $result['suffixed']);
        $this->assign('lift', $result['lift']);
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }
    
    /**
     * 热门活动编辑确定
     */
    public function homeAdvInsert() {
        $result = array('error' => 0, 'message' => '', 'content' => '');
        $result['moded'] = trim(request()->post('mode', ''));
        $original_img = request()->post('original_img/a', '');
        $opennew = request()->post('opennew/a', '');
        $pic_id = request()->post('pic_id/a', '');
        $masterTitle = request()->post('masterTitle', '');
        $url = request()->post('url/a', '');
        $arr = array();
        $count = count($original_img);
        if ($result['moded'] == "h-storeRec") {
            $count = 4;
        }

        for ($i = 0; $i < $count; $i++) {
            if ($url[$i]) {
                $arr[$i]['url'] = $this->com->setRewrite($url[$i]);
            } else {
                $arr[$i]['url'] = $url[$i];
            }
            if ($opennew[$i]) {
                $arr[$i]['opennew'] = 1;
            } else {
                $arr[$i]['opennew'] = 0;
            }
            $arr[$i]['original_img'] = $original_img[$i];
            $arr[$i]['pic_id'] = $pic_id[$i];
        }

        $spec_attr = $arr;

        $temp = $result['moded'];
        $result['masterTitle'] = $masterTitle;
        $result['spec_attr'] = $arr;
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('arr', $arr);
        $this->assign('temp', $temp . '1');
        $this->assign('masterTitle', $masterTitle);
        $this->assign('spec_attr', $spec_attr);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }
    /**
     * 首页轮播编辑回调
     */
    public function homeBannerResponse() {
        $json = new Json();
        $result = array('content' => '');
        $res_spec_attr = $_POST['spec_attr'];
        $result['spec_attr'] = stripslashes($res_spec_attr);
        if ($res_spec_attr) {
            $res_spec_attr = strip_tags(urldecode($res_spec_attr));
            $res_spec_attr = $this->com->json_str_iconv($res_spec_attr);

            if (!empty($res_spec_attr)) {
                $spec_attr = $json->decode($res_spec_attr);
                $spec_attr = $this->com->object_to_array($spec_attr);
            }
        }
        $result['mode'] = trim(request()->post('mode', ''));
        $result['diff'] = intval(request()->post('diff', 0));
        $lb = (isset($spec_attr['leftBanner']) && ($spec_attr['leftBanner'] != ',') ? $spec_attr['leftBanner'] : array());
        $lbOpennew = (isset($spec_attr['leftBannerOpennew']) && ($spec_attr['leftBannerOpennew'] != ',') ? $spec_attr['leftBannerOpennew'] : array());
        $lbUrlLink = (!empty($spec_attr['leftBannerUrlLink']) && ($spec_attr['leftBannerUrlLink'] != ',') ? explode(',', $spec_attr['leftBannerUrlLink']) : array());
        $lbLink = (!empty($spec_attr['leftBannerLink']) && ($spec_attr['leftBannerLink'] != ',') ? explode(',', $spec_attr['leftBannerLink']) : array());
        $la = (isset($spec_attr['leftAdv']) && ($spec_attr['leftAdv'] != ',') ? $spec_attr['leftAdv'] : array());
        $laOpennew = (isset($spec_attr['leftAdvOpennew']) && ($spec_attr['leftAdvOpennew'] != ',') ? $spec_attr['leftAdvOpennew'] : array());
        $laName = (isset($spec_attr['leftAdvName']) && ($spec_attr['leftAdvName'] != ',') ? $spec_attr['leftAdvName'] : array());
        $laUrlLink = (!empty($spec_attr['leftAdvUrlLink']) && ($spec_attr['leftAdvUrlLink'] != ',') ? explode(',', $spec_attr['leftAdvUrlLink']) : array());
        $laLink = (!empty($spec_attr['leftAdvLink']) && ($spec_attr['leftAdvLink'] != ',') ? explode(',', $spec_attr['leftAdvLink']) : array());
        $countLa = 6; //展位内容暂时固定6个
        $countLb = count($lb); //主推轮播图数量
        $leftAdvList = array();
        $leftBannerList = array();
        for ($i = 0; $i < $countLb; $i++) {
            if ($lb[$i]) {

                $leftBannerList[$i + 1]['pic_src'] = $lb[$i];
                $leftBannerList[$i + 1]['opennew'] = $lbOpennew[$i];

                if (!empty($lbUrlLink)) {
                    if (isset($lbUrlLink[$i])) {
                        $leftBannerList[$i + 1]['link'] = str_replace(array('＆'), '&', $lbUrlLink[$i]);
                    } else {
                        $leftBannerList[$i + 1]['link'] = $lbUrlLink[$i];
                    }
                }
                if (!empty($lbLink)) {
                    if (isset($lbLink[$i])) {
                        $leftBannerList[$i + 1]['pic_link'] = str_replace(array('＆'), '&', $lbLink[$i]);
                    } else {
                        $leftBannerList[$i + 1]['pic_link'] = $lbLink[$i];
                    }
                }
            }
        }
        for ($i = 0; $i < $countLa; $i++) {
            $leftAdvList[$i + 1]['pic_src'] = $la[$i];
            $leftAdvList[$i + 1]['opennew'] = $laOpennew[$i];
            $leftAdvList[$i + 1]['leftAdvName'] = $laName[$i];
            if (!empty($laUrlLink)) {
                if (isset($laUrlLink[$i])) {
                    $leftAdvList[$i + 1]['link'] = str_replace(array('＆'), '&', $laUrlLink[$i]);
                } else {
                    $leftAdvList[$i + 1]['link'] = $laUrlLink[$i];
                }
            }
            if (!empty($laLink)) {
                if (isset($laLink[$i])) {
                    $leftAdvList[$i + 1]['pic_link'] = str_replace(array('＆'), '&', $laLink[$i]);
                } else {
                    $leftAdvList[$i + 1]['pic_link'] = $laLink[$i];
                }
            }
        }
        $spec_attr['leftBannerList'] = $leftBannerList;
        $spec_attr['leftAdvList'] = $leftAdvList;

        $temp = $result['mode'] . 'Response';
        $result_json = json_encode($result);
        $this->assign('result_json', $result_json);
        $this->assign('temp', $temp);
        $this->assign('spec_attr', $spec_attr);
        $this->assign('result_json', $result_json);
        $this->fetch('template/' . $this->module . '/modalFrame');
    }

    /**
     * 编辑模板信息弹窗
     *
     * @return view
     */
    public function pageEditModal() {
        $code = request()->get('code', '') ? addslashes(request()->get('code', '')) : '';
        $template_type = request()->get('template_type', '') ? trim(request()->get('template_type', '')) : '';
        $template = array();
        if ($code != 'undefined') {
            $template = $this->com->get_seller_template_info($code, $template_type);
        }
        $this->assign('code', $code);
        $this->assign('template_type', $template_type);
        $this->assign('template', $template);
        $typeList = $this->com->getTemplateTypeName('', 1);
        $this->assign("typeList", $typeList);
        $this->fetch('template/' . $this->module . '/pageEditModal');
    }
}