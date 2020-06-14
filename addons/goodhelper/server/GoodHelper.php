<?php

namespace addons\goodhelper\server;

use data\model\ModuleModel;
use data\model\VslGoodsModel;
use data\service\BaseService;
use data\service\Config as WebConfig;
use data\service\Upload\AliOss;
use data\service\Album as Album;
use data\model\AlbumPictureModel;
use data\model\VslGoodsSkuModel;
use app\platform\controller\Upload;
use data\model\VslGoodsBrandModel;
use data\model\VslGoodsCategoryModel;
use data\model\VslAttributeModel;
use data\model\VslGoodsAttributeModel;
use data\model\VslAttributeValueModel;
use data\model\VslGoodsSpecModel;
use data\model\VslGoodsSpecValueModel;
use data\model\VslOrderShippingFeeModel;
use addons\goodhelper\model\VslGoodsHelpModel;
use data\model\UserModel;
use think\Db;

/**
 * 商品导入助手数据处理
 * Class Good
 * @package addons\goodhelper\server
 */
class GoodHelper extends BaseService {

    private $upload_type = 1;
    
    private $http = '';

    public function __construct() {
        parent::__construct();
        $config = new WebConfig();
        $this->upload_type = $config->getUploadType($this->website_id);
        $is_ssl = \think\Request::instance()->isSsl();
        $this->http = "http://";
        if($is_ssl){
            $this->http = 'https://';
        }
    }

    /*
     * 采集单个商品
     */

    public function getGoodGather($url) {
        header("Content-Type: text/html;charset=utf-8");
        date_default_timezone_set('PRC');
        ignore_user_abort(true);//关掉浏览器，PHP脚本也可以继续执行. 
        set_time_limit(0);
        $res = $this->curl_get_contents($url);
        if (!$res) {
            return 0;
        }
        if (strstr($url, 'taobao')) {
            preg_match('/<ul[^>]*id="J_UlThumb"[^>]*>(.*?) <\/ul>/si', $res, $img);
            //取得第一個img标签，並储存至阵列match2
            preg_match_all('/<img[^>][^r]*rc=\"([^"]*)\"[^>]*>/', $img[0], $img2); //获取淘宝商品图片
            $data['imgArray'] = $img2[1];
            preg_match('/<ul[^>]*class="attributes-list"[^>]*>(.*?) <\/ul>/si', $res, $attr);
            //取得第一個attr标签，並储存至阵列attr2
            preg_match_all('/<li.*?>(.*?)<\/li>/si', $attr[0], $attr2); //获取淘宝商品属性
            $data['taoAttributes'] = $attr2[1];
            preg_match('/<input\s*type="hidden"\s*name="item_id"\s*value="(.*?)"\s*\/>/i', $res, $id); //获取淘宝商品id
            $data['taoId'] = $id[1];
            preg_match('/<input\s*type="hidden"\s*name="current_price"\s*value= "(.*?)"\s*\/>/i', $res, $price); //获取淘宝商品价格
            $data['taoPrice'] = $price[1];
            preg_match('/<h3[^>]*class="tb-main-title"[^>]*>(.*?)<\/h3>/si', $res, $title); //获取商品名称
            $title[1]=preg_replace('/<span.*?>[\s|\S]*?<\/span>/',"",$title[1]); //过滤html标签
            $data['taoTitle'] = trim($title[1]);
            preg_match('/<span[^>]*id="J_SpanStock"[^>]*>(.*?)<\/span>/si', $res, $stock); //获取商品库存
            $data['taoStock'] = $stock[1];
            $descUrl = $this->http . '47.52.71.120/crawler/spider/taobao/'.$data['taoId'].'/1'; //获取商品描述
            $resDesc = $this->getDesc($descUrl);
//            preg_match("/tfsContent : '(.*?)'/si", $resDesc, $desc);
            $data['taoDesc'] = $resDesc;
        } elseif (strstr($url, 'tmall')) {
            preg_match('/<ul[^>]*id="J_UlThumb"[^>]*>(.*?)<\/ul>/si', $res, $img);
            //取得第一個img标签，並储存至阵列match2
            preg_match_all('/<img[^>][^r]*rc=\"([^"]*)\"[^>]*>/', $img[0], $img2); //获取商品图片
            $data['imgArray'] = $img2[1];
            preg_match('/<ul[^>]*id="J_AttrUL"[^>]*>(.*?) <\/ul>/si', $res, $attr);
            //取得第一個attr标签，並储存至阵列attr2
            preg_match_all('/<li.*?>(.*?)<\/li>/si', $attr[0], $attr2); //获取商品属性
            $data['taoAttributes'] = $attr2[1];
            preg_match('/<div[^>]*id="LineZing"[^>]*itemid="(.*?)">/si', $res, $id); //获取商品id
            $data['taoId'] = $id[1];
            preg_match('/"defaultItemPrice":"(.*?)"/si', $res, $price);
            $data['taoPrice'] = $price[1];
            preg_match('/<input\s*type="hidden"\s*name="title"\s*value="(.*?)"\s*\/>/i', $res, $title); //获取淘宝商品价格
            $data['taoTitle'] = $title[1];
            preg_match('/"quantity":(.*?),/si', $res, $stock);
            $data['taoStock'] = (int) $stock[1];
            $descUrl = $this->http . '47.52.71.120/crawler/spider/tmall/'. $data['taoId'].'/1'; //获取商品描述
            $resDesc = $this->getDesc($descUrl);
            $data['taoDesc'] = $resDesc;
        }
        $result = $this->addGoodsByLink($data);
        if (!$result) {
            return 0;
        }
        return $result;
    }
    /*
     * 采集多个商品
     */

    public function getMultipleGoodGather(array $aUrl, array $aTid, $batch_no = 0) {
        header("Content-Type: text/html;charset=utf-8");
        date_default_timezone_set('PRC');
        ignore_user_abort(true);//关掉浏览器，PHP脚本也可以继续执行. 
        set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去
        $allData = [];
        foreach($aUrl as $uk => $url){
            $res = $this->curl_get_contents($url);
            if (!$res) {
                unset($aUrl[$uk]);
                unset($uk);
                continue;
            }
            if (strstr($url, 'taobao')) {
                preg_match('/<ul[^>]*id="J_UlThumb"[^>]*>(.*?) <\/ul>/si', $res, $img);
                //取得第一個img标签，並储存至阵列match2
                preg_match_all('/<img[^>][^r]*rc=\"([^"]*)\"[^>]*>/', $img[0], $img2); //获取淘宝商品图片
                $allData[$aTid[$uk]['itemId']]['imgArray'] = $img2[1];
                preg_match('/<ul[^>]*class="attributes-list"[^>]*>(.*?) <\/ul>/si', $res, $attr);
                //取得第一個attr标签，並储存至阵列attr2
                preg_match_all('/<li.*?>(.*?)<\/li>/si', $attr[0], $attr2); //获取淘宝商品属性
                $allData[$aTid[$uk]['itemId']]['taoAttributes'] = $attr2[1];
                preg_match('/<input\s*type="hidden"\s*name="item_id"\s*value="(.*?)"\s*\/>/i', $res, $id); //获取淘宝商品id
                $allData[$aTid[$uk]['itemId']]['taoId'] = $id[1];
                preg_match('/<input\s*type="hidden"\s*name="current_price"\s*value= "(.*?)"\s*\/>/i', $res, $price); //获取淘宝商品价格
                $allData[$aTid[$uk]['itemId']]['taoPrice'] = $price[1];
                preg_match('/<h3[^>]*class="tb-main-title"[^>]*>(.*?)<\/h3>/si', $res, $title); //获取商品名称
                $title[1]=preg_replace('/<span.*?>[\s|\S]*?<\/span>/',"",$title[1]); //过滤html标签
                $allData[$aTid[$uk]['itemId']]['taoTitle'] = trim($title[1]);
                preg_match('/<span[^>]*id="J_SpanStock"[^>]*>(.*?)<\/span>/si', $res, $stock); //获取商品库存
                $allData[$aTid[$uk]['itemId']]['taoStock'] = $stock[1];
            } elseif (strstr($url, 'tmall')) {
                preg_match('/<ul[^>]*id="J_UlThumb"[^>]*>(.*?)<\/ul>/si', $res, $img);
                //取得第一個img标签，並储存至阵列match2
                preg_match_all('/<img[^>][^r]*rc=\"([^"]*)\"[^>]*>/', $img[0], $img2); //获取商品图片
                $allData[$aTid[$uk]['itemId']]['imgArray'] = $img2[1];
                preg_match('/<ul[^>]*id="J_AttrUL"[^>]*>(.*?) <\/ul>/si', $res, $attr);
                //取得第一個attr标签，並储存至阵列attr2
                preg_match_all('/<li.*?>(.*?)<\/li>/si', $attr[0], $attr2); //获取商品属性
                $allData[$aTid[$uk]['itemId']]['taoAttributes'] = $attr2[1];
                preg_match('/<div[^>]*id="LineZing"[^>]*itemid="(.*?)">/si', $res, $id); //获取商品id
                $allData[$aTid[$uk]['itemId']]['taoId'] = $id[1];
                preg_match('/"defaultItemPrice":"(.*?)"/si', $res, $price);
                $allData[$aTid[$uk]['itemId']]['taoPrice'] = $price[1];
                preg_match('/<input\s*type="hidden"\s*name="title"\s*value="(.*?)"\s*\/>/i', $res, $title); //获取淘宝商品价格
                $allData[$aTid[$uk]['itemId']]['taoTitle'] = $title[1];
                preg_match('/"quantity":(.*?),/si', $res, $stock);
                $allData[$aTid[$uk]['itemId']]['taoStock'] = (int) $stock[1];
            }
        }
        \think\Session::set('allData', json_encode($allData,true));
        $aDesc = $this->getMultipleDesc($aTid,$batch_no);
        if($aDesc['code'] == 0){
            return -10020;
        }
//        foreach($allData as $key => $data){
//            $data['taoDesc'] = $aDesc[$key];
//            $this->addGoodsByLink($data);
//        }
        return true;
    }
    
    public function PostCurl($url,$data,$header=""){
	//初始化
        $curl = curl_init();
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
        $res = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return json_decode($res,true);
    }
    public function getDesc($url){
        $resDesc = $this->GetCurl($url);
        if(!$resDesc['msg'] == 'SUCCESS'){
            return '';
        }
        $detail = '<p>';
        if(isset($resDesc['data']['detailMap'])){
            $resDesc['data']['detailMap'] = array_values($resDesc['data']['detailMap']);
            $detail .= '<ul style="width:100%;display:block;">';
            foreach($resDesc['data']['detailMap'] as $val){
                $detail.= '<li style="float:left;width:33%;line-height:30px;list-style: none;">'.$val.'</li>';
            }
            $detail .= '<li style="clear:both"></li></ul>';
        }
        if(isset($resDesc['data']['imgUrlMap'])){
            $detail .= '<div style="width:100%; text-align:center;">';
            foreach($resDesc['data']['imgUrlMap'] as $img){
                $detail.= '<img style="width:auto;max-width:100%;height:auto;" src="'.$img.'">';
            }
            $detail .= '</div>';
        }
        $detail .= '</p>';
        return $detail;
    }
    public function getMultipleDesc($aTid,$batch_no){
        $param['key'] = "MFUA7JxTr3vDGZlwGKj7FnGK7HXhCJV9";
        // 批次号
        $param['batchNo'] = $batch_no;
        $param['data'] = json_encode($aTid);
        $data = $this->PostCurl($this->http . '47.52.71.120/crawler/batchSpider',$param,"json");
        return $data;
    }
    
    /*
     * 存储采集的数据
     */
    public function setGoodDesc($data){
        $allData = \think\Session::get('allData');
        if(!$allData){
            return false;
        }
        if($allData){
            $allData = json_decode($allData,true);
        }
        if(count($data) != count($allData)){
            return false;
        }
        $aDesc = [];
        foreach($data as $kd => $desc){
            $detail = '<p>';
            if(isset($desc['detailMap'])){
                $detail .= '<ul style="width:100%;display:block;">';
                foreach($desc['detailMap'] as $val){
                    $detail.= '<li style="float:left;width:33%;line-height:30px;list-style: none;">'.$val.'</li>';
                }
                $detail .= '<li style="clear:both"></li></ul>';
            }
            if(isset($desc['imgUrlMap'])){
                $detail .= '<div style="width:100%; text-align:center;">';
                foreach($desc['imgUrlMap'] as $img){
                    $detail.= '<img style="width:auto;max-width:100%;height:auto;" src="'.$img.'">';
                }
                $detail .= '</div>';
            }
            $detail .= '</p>';
            $aDesc[$kd] = $detail;
        }
        foreach($allData as $key => $data){
            $data['taoDesc'] = $aDesc[$key];
            $this->addGoodsByLink($data);
        }
        \think\Session::delete('allData');
        return true;
    }

    /*
     * curl抓取页面
     */

    public function curl_get_contents($url, $cookie = '', $referer = '', $timeout = 5, $isproxy = 0) {
        $curl = curl_init();
//        curl_setopt($curl,CURLOPT_PROXY,'http://140.227.212.179:3128');185.90.210.146	61468
        if ($isproxy) {
            curl_setopt($curl, CURLOPT_PROXY, 'http://103.106.148.207:51451');
            curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("application/x-www-form-urlencoded;charset=utf-8"));
        if ($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        if ($referer) {
            curl_setopt($curl, CURLOPT_REFERER, $referer);
        }
        $ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
        if ($ssl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $res = curl_exec($curl);
        $res = str_replace('gb2312', 'utf-8', $res);
        $res = iconv("gb2312", "utf-8//IGNORE", $res);
        curl_close($curl);
        return $res;
    }
    public function GetCurl($url){

    //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return json_decode($data,true);
    }

    //读取 unicode csv 文件
    public function fopen_utf8($filename) {
        $encoding = '';
        $handle = fopen($filename, 'r');
        $bom = fread($handle, 2);
        //    fclose($handle);
        rewind($handle);

        if ($bom === chr(0xff) . chr(0xfe) || $bom === chr(0xfe) . chr(0xff)) {
            // UTF16 Byte Order Mark present
            $encoding = 'UTF-16';
        } else {
            $file_sample = fread($handle, 1000) + 'e'; //read first 1000 bytes
            // + e is a workaround for mb_string bug
            rewind($handle);
            $encoding = mb_detect_encoding($file_sample, 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP');
        }

        if ($encoding) {
            stream_filter_append($handle, 'convert.iconv.' . $encoding . '/UTF-8');
        }
        return ($handle);
    }

    /*
     * 加入队列，等待执行
     */

    public function joinTheQueue($file, $zip, $add_type) {
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1000M');

        $base_path = 'upload' . DS . $this->website_id . DS . 'goodhelper' . DS;
        if (!is_dir($base_path)) {
            mkdir($base_path, 0777, true);
        }
        if ($add_type == 1) {//淘宝只能传csv文件
            $info = $file->validate(['ext' => 'csv'])->move( $base_path. 'Excel');
        } else {//商城限制只能传xlsx和xls文件
            $info = $file->validate(['ext' => 'xlsx,xls'])->move($base_path . 'Excel');
        }
        if (!$info) {
            // 上传失败获取错误信息  
            return ['code' => 0, 'message' => '文件格式错误，请重新上传'];
        }
        $exclePath = $info->getSaveName(); //获取文件名
        $old_excel_name = $info->getInfo($exclePath);
        $file_types = explode(".", $old_excel_name['name']);
        $zipname = '';
        $old_zip_name = [];
        if ($zip) {
            $zipInfo = $zip->validate(['ext' => 'zip'])->move($base_path . 'Zip');
            if (!$zipInfo) {
                // 上传失败获取错误信息  
                return ['code' => 0, 'message' => '文件格式错误，请重新上传'];
            }
            $zipPath = $zipInfo->getSaveName(); //获取文件名
            $old_zip_name = $zipInfo->getInfo($zipPath);
            $zipname = $base_path . 'Zip' . DS . $zipPath;
            //$this->get_zip_originalsize($zip_name,$img_path);//处理zip文件 bcnmnbnm 
        }

        $data['add_type'] = $add_type;
        $data['type'] = end($file_types); //文件类型
        $data['file_name'] = $base_path . 'Excel' . DS . $exclePath; //上传文件的地址
        $data['zip_name'] = $zipname; //上传文件的地址
        $data['website_id'] = $this->website_id;
        $data['shop_id'] = $this->instance_id;
        $data['create_time'] = time();
        $data['old_name'] = $old_excel_name['name'];
        $data['old_excel_name'] = $old_excel_name['name'];
        $data['old_zip_name'] = $old_zip_name['name'] ?: '';
        $data['status'] = 3;//等待中


        $goodsHelp = new VslGoodsHelpModel();
        $save = $goodsHelp->isUpdate(false)->save($data);
        if (!$save) {
            $result['code'] = 0;
            $result['message'] = '操作失败，请稍后重试';
            return $result;
        }
        $result['code'] = 1;
        $result['message'] = '已添加导入队列，等待执行';
        return $result;
    }

    /**
     * 导入商品
     * @param $file string [Eecel文件路径]
     * @param $zip string [Zip文件路径]
     * @param $add_type int [Excel文件类型0商城数据包 1淘宝]
     * @param $file_type int [Excel文件类型 '.xls、.xlsx、.csv']
     * @param $shop_id
     * @param $website_id
     * @return array|false|int
     */
    public function addGoodsByXls($file, $zip, $add_type, $file_type, $shop_id, $website_id) {
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1000M');
        Db::startTrans();
        try {
            $img_path = 'upload/' . $website_id . '/goodhelper/common/';
            if ($shop_id) {
                $img_path = 'upload/' . $website_id . '/' . $shop_id . '/goodhelper/common/';
            }
            $zip_error_info = [];
            if ($zip) {
                $zip_error_info =  $this->get_zip_originalsize($zip, $img_path, $website_id, $shop_id); //处理zip文件 bcnmnbnm
                if (empty($zip_error_info['right_path'])) {
                    //储存excel路径
                    return ['code' => 0, 'message' => $zip_error_info['message'] ?: 'Zip解压失败', 'data' => $file];
                }
            }
            Vendor('PhpExcel.PHPExcel');
            if (strtolower($file_type) == 'xls') {
                $objReader = \PHPExcel_IOFactory::createReader('Excel5');
                $objReader->setReadDataOnly(true);
                $obj_PHPExcel = $objReader->load($file, 'utf-8'); //加载文件内容,编码utf-8
                $excel_array = $obj_PHPExcel->getsheet(0)->toArray(); //转换为数组格式
            } elseif (strtolower($file_type) == 'xlsx') {
                $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                $objReader->setReadDataOnly(true);
                $obj_PHPExcel = $objReader->load($file, 'utf-8'); //加载文件内容,编码utf-8
                $excel_array = $obj_PHPExcel->getsheet(0)->toArray(); //转换为数组格式
            } elseif (strtolower($file_type) == 'csv') {//淘宝csv文件单独处理
                if (($handle = $this->fopen_utf8($file, "r")) === FALSE) {
                    // 上传失败获取错误信息  
                    return ['code' => 0, 'message' => '文件格式错误，请重新上传'];
                }

                $i = 0;
                $excel_array = array();
                //todo... 这里cvs转成数组，中文会多行并一行，不知怎么处理！
                while (($cols = fgetcsv($handle, 20000, "\t")) !== FALSE) {
                    if ($i == 0) {//淘宝csv第一行不读取
                        $i++;
                        continue;
                    }
                    $excel_array[] = $cols;
                }
            } else {
                return ['code' => 0, 'message' => '文件格式错误，请重新上传'];
            }

            $i = 0; //成功数量
            $j = 0; //全部数量
            $right_arr = [];
            $error_arr = [];
            $excel_error_message = '';
            if ($add_type == 1) {//淘宝csv去掉已读取的第二行中文字段名
                $key_array = $excel_array[0]; //英文字段名作key
                unset($excel_array[0]);
                unset($excel_array[1]);
                $excel_array = array_values($excel_array);
                // excel为空
                if (empty($excel_array)) {
                    return ['code' => 0, 'message' => 'Excel文件为空', 'data' => $file];
                }
                //去除后缀
                if ($zip_error_info['right']) {
                    foreach ($zip_error_info['right'] as $k =>$zip) {
                        $zip_error_info['right'][$k] = getFileNameOfUrl($zip);
                    }
                }
                foreach ($excel_array as $k => $v) {
                    $j++;
                    //处理商品图片
                    $sheetCount = count($v);
                    $csv_img_name = substr($v[28], 0, stripos($v[28], ':'));
                    if ($zip_error_info['right'] && in_array($csv_img_name,  $zip_error_info['right'])) {
                        $right_arr[$k][] = $csv_img_name;// 记录正确上传图片,Excel循环删除
                        $t = array_keys($zip_error_info['right'], $csv_img_name)[0];//查询包含该图片名的$zip_error_info的key
                        $right_temp[] = $zip_error_info['right_path'][$t];//图片名对应的图片URL
                    }
                    $right_temp = [];
                    if (count($right_arr[$k]) == $sheetCount) {
                        if(!@array_combine($key_array, $v)){
                            continue;
                        }
                        $excelData = @array_combine($key_array, $v); //合并数组

                        $goods = new VslGoodsModel();
                        if (!$excelData['title']) {
                            continue;
                        }

                        $check = $goods->getInfo(['goods_name' => $excelData['title'], 'shop_id' => $shop_id, 'website_id' => $website_id], 'goods_id');

                        $data = [
                            'goods_name' => $excelData['title'],
                            'price' => $excelData['price'],
                            'stock' => $excelData['num'],
                            'description' => $excelData['description'],
                            'update_time' => time(),
                            'goods_volume' => $excelData['item_size'],
                            'goods_weight' => $excelData['item_weight'],
                            'code' => $excelData['barcode'],
                            'shop_id' => $shop_id,
                            'website_id' => $website_id,
                        ];
                        $goodsImg = $this->getGoodsImg($excelData['picture'],$shop_id, $website_id);
                        $data['picture'] = $goodsImg['picture'];
                        $data['img_id_array'] = $goodsImg['img_id_array'];
                        if ($check) {
                            $result = $goods->save($data, ['goods_id' => $check['goods_id']]);
                        } else {
                            $data['create_time'] = time();
                            $result = $goods->save($data);
                        }
                        if (!$result) {
                            continue;
                        }
                        $goodsSku = new VslGoodsSkuModel();
                        $checkSku = $goodsSku->getInfo(['goods_id' => $check['goods_id']], 'sku_id');
                        if ($checkSku && $check['goods_id']) {
                            $goodsSku->save(['price' => $data['price'], 'stock' => $data['stock'], 'code' => $data['code'], 'update_date' => time()], ['sku_id' => $checkSku['sku_id']]);
                        } else {
                            $goodsSku->save(['goods_id' => $result, 'price' => $data['price'], 'stock' => $data['stock'], 'code' => $data['code'], 'update_date' => time(), 'create_date' => time()]);
                        }
                        $i++;
                    } else {
                        $error_arr[] = $v;
                    }
                }
            } else {//商城数据包
                $key_array = $excel_array[0];
                unset($excel_array[0]);
                $excel_array = array_values($excel_array);
                // excel为空
                if (empty($excel_array)) {
                    return ['code' => 0, 'message' => 'Excel文件为空', 'data' => $file];
                }

                //todo... 新处理
                $column = 11;// 这里是根据Excel图片所在列默认值
                // Excel含有图片的列
                $excel_all_images = [];//记录当前excel中所有的图片名
                $excel_img_col = array_column($excel_array, $column);//根据Excel知晓
                foreach ($excel_img_col as $img) {
                    $temp = explode('|', $img);
                    foreach ($temp as $t) {
                    $excel_all_images[] = $t;
                    }
                }
                unset($excel_img_col);
                unset($temp);
                unset($t);
                $repeat_arr =  array_unique(arraySeparateRepeat($excel_all_images)['repeat']);//Excel中所有图片中存在重复的图片名
                foreach ($excel_array as $k => $v) {
                    $j++;
                    $v_img_arr = explode('|', $v[$column]);//Excel一行中所有图片
                    if (!array_intersect($v_img_arr, $repeat_arr)) {//与重复数组没有交集,可以往下判断是否在zip解压成功中
                        $right = $zip_error_info['right'] ? array_intersect($v_img_arr, $zip_error_info['right']) : [];//交集
                        if (count($right) == count($v_img_arr)) {//该行所有图片也全在zip解压成功的集合中
                            $t = 0;//记录Excel每一个行中的每一个图片是否处理成功
                            foreach ($v_img_arr as $v_img) {
                                $z_k = array_search($v_img, $zip_error_info['right']) ;//查找zip是这张图的键key,通过key查到对应right_path
                                $file_name = $zip_error_info['right_path'][$z_k];//正确待上传的图片路径 upload/96/goodhelper/common/b5ee77de8f458a4e46b4858af7c3d480/gsdgsdg0388c03b9ea71f4c386d26e1fc.png
                                //上传
                                unset($result);
                                $result = [];
                                $result["domain"] = '';
                                $result["bucket"] = '';
                                $result["path"] = '';
                                if ($this->upload_type == 2) {
                                    $alioss = new AliOss();
                                    $result = $alioss->setAliOssUplaod($file_name, $file_name);
                                    if($result['code']){
                                        @unlink($file_name);
                                        $file_name = $result['path'];//云返回图片路径
                                    } else {//记录错误
                                        $excel_error_message .= $k+1 .'行'.$v_img.'上传失败; ';
                                        $error_arr[] = $v;
                                        continue;
                                    }
                                }
                                //存储
                                $image_size = getimagesize($file_name);
                                $file_path = substr($file_name, 0, strrpos($file_name, '/'));
                                $ext = strrchr($file_name, '.');
                                $img_name = $v_img;
                                $album = new Album();
                                $album_id = $album->getDefaultAlbum($shop_id, $website_id)['album_id'];
                                $this->photoCreateForGoodsHelper($file_path . '/', $file_name, $ext, 0, $img_name, $album_id, $image_size[0], $image_size[1], $img_name, 0, $result["domain"], $result["bucket"], $result['path'] ?: $file_name, $website_id, $shop_id);
                                $t++;
                            //foeach 结束
                            }
                            //Excel一行中所有图片都处理没问题，才能存储到商品表，如果其中有一张图片有问题，都不去存储商品表,而导出成为新的Excel表
                            if (count($v_img_arr) == $t) {
                                if(!@array_combine($key_array, $v)){
                                    continue;
                                }
                                $excelData = array_combine($key_array, $v);
                                if (!$excelData['商品名称']) {
                                    continue;
                                }
                                $goods = new VslGoodsModel();
                                $check = $goods->getInfo(['goods_name' => $excelData['商品名称'], 'shop_id' => $shop_id, 'website_id' => $website_id], 'goods_id');
                                $data = [
                                    'goods_name' => $excelData['商品名称'],
                                    'price' => $excelData['销售价'],
                                    'stock' => $excelData['总库存'],
                                    'description' => $excelData['商品详情'],
                                    'update_time' => time(),
                                    'code' => $excelData['商品编号'],
                                    'market_price' => $excelData['市场价'],
                                    'cost_price' => $excelData['成本价'],
                                    'sort' => $excelData['商品排序'],
                                    'min_stock_alarm' => $excelData['库存预警'],
                                    'state' => $excelData['是否上架'],
                                    'shipping_fee_type' => $excelData['运费类型'],
                                    'is_new' => $excelData['是否新品'],
                                    'is_hot' => $excelData['是否热卖'],
                                    'is_promotion' => $excelData['是否促销'],
                                    'is_recommend' => $excelData['是否推荐'],
                                    'is_shipping_free' => $excelData['是否包邮'],
                                    'goods_weight' => $excelData['商品重量'],
                                    'goods_volume' => $excelData['商品体积'],
                                    'shop_id' => $shop_id,
                                    'website_id' => $website_id,
                                ];
                                $goodsImg = $this->getGoodsImgForSelf($excelData['商品图片']);
                                $data['picture'] = $goodsImg['picture'];
                                $data['img_id_array'] = $goodsImg['img_id_array'];
                                if (!$excelData['商品规格']) {
                                    $data['item_no'] = $excelData['商品货号'];
                                }
                                if ($excelData['运费模板']) {
                                    $shippingModel = new VslOrderShippingFeeModel();
                                    $shippingFee = $shippingModel->getInfo(['shipping_fee_name' => $excelData['运费模板'], 'website_id' => $website_id, 'shop_id' => $shop_id], 'shipping_fee_id');
                                    if ($shippingFee) {
                                        $data['shipping_fee_type'] = 2;
                                        $data['shipping_fee_id'] = $shippingFee['shipping_fee_id'];
                                    }
                                }
                                if ($check) {
                                    $result = $goods->save($data, ['goods_id' => $check['goods_id']]);
                                    $goods_id = $check['goods_id'];
                                } else {
                                    $data['create_time'] = time();
                                    $result = $goods->save($data);
                                    unset($data);
                                    $goods_id = $result;
                                }
                                if (!$result) {
                                    $excel_error_message .= $k+1 .'行商品信息存储错误; ';
                                    continue;
                                }

                                $otherData = $this->setOtherData($goods_id, $excelData['商品分类'], $excelData['商品品类'], $excelData['商品规格'], $excelData['规格值'], $excelData['商品属性'], $excelData['商品品牌'], $website_id, $shop_id);
                                $update['category_id'] = $otherData['categoryArr']['category_id'];
                                $update['category_id_1'] = $otherData['categoryArr']['category_id_1'];
                                $update['category_id_2'] = $otherData['categoryArr']['category_id_2'];
                                $update['category_id_3'] = $otherData['categoryArr']['category_id_3'];
                                $update['goods_attribute_id'] = $otherData['attr_id'];
                                $update['goods_spec_format'] = json_encode($otherData['goods_spec_format']);
                                $update['brand_id'] = $otherData['brand_id'];
                                $goods->save($update, ['goods_id' => $goods_id]);
                                $i++;
                            } else {
                                $excel_error_message .= $k+1 .'行图片未上传成功; ';
                                $error_arr[] = $v;
                            }
                        } else {
                            $excel_error_message .= $k+1 .'行Excel图片名与zip不一致; ';
                            $error_arr[] = $v;
                        }
                    } else {
                        $excel_error_message .= $k+1 .'行Excel有重复图片名; ';
                        $error_arr[] = $v;
                    }
                //foreach结束
                }
            }

            $error_new_path = '';
            if ($error_arr) {
                $xlsName = getFileNameOfUrl($file, TRUE);
                $suffix = substr($xlsName, strripos($xlsName, '.'));
                $file_name = substr($xlsName, 0, strripos($xlsName, '.'));
                $data = $error_arr;
                $path = str_replace($xlsName, '', $file);
                $xlsCell = [];
                $c = 0;
                foreach ($key_array as $k1 => $v1) {
                    $xlsCell[$k1] = [$c, $v1];
                    $c ++;
                }
                $res = dataExcel($file_name, $xlsCell, $data, $path, $suffix);
                if ($res['code'] > 0) {
                    $error_new_path = $res['data'];
                }
            }
            unset($res);
            if ($i == 0) {
                $res['code'] = 0;
                $res['message'] = $excel_error_message;
                Db::rollback();
            } else if ($i == $j) {
                $res['code'] = 1;
                $res['message'] = '导入总数目：' . $j . ',导入成功';
                Db::commit();
            } else {
                $res['code'] = 2;
                $res['message'] = '导入总数目：' . $j . ',导入成功数目：' . $i.'失败原因：'.$excel_error_message;
                $res['data'] = $error_new_path;
                Db::rollback();
            }
            @unlink($file);
            @unlink($zip);
            return $res;
        } catch (\Exception $e) {
            Db::rollback();
            $msg = $e->getMessage();
            $log_dir = getcwd() . '/goods_import.log';
            file_put_contents($log_dir, date('Y-m-d H:i:s') . $msg . PHP_EOL, 8);
            return array('code' => 0, 'message' => $e->getMessage());
        }
    }

    /*
     * 处理zip文件，解压$filename文件到$path
     */

    public function get_zip_originalsize($filename, $path, $website_id = 0, $shop_id = 0) {
        $record_info = [
            'error' => [],
            'error_path' => [],
            'right' => [],
            'right_path' => [],
            'message' => ''
        ];//用于记录错误信息， error:解压后错误图片名 right:解压并上传正确图片名（用与excel对应储存）
        if (!file_exists($filename)) {
            return $record_info[ 'message'] = 'zip已删除';
        }
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $path = iconv('utf-8', 'gb2312', $path);
        $resource = zip_open($filename);
        if (!$resource) {
            return $record_info[ 'message'] = 'zip打开失败';
        }
        $i = 1;
        while ($img = zip_read($resource)) {
        if (!$img){
            return $record_info[ 'message'] = 'zip读取失败';
        }
        if (!zip_entry_open($resource, $img)) {
            break;
        }
        $img_i = zip_entry_name($img);//文件名
        if (strpos($img_i, '/')) {
            $img_i = substr($img_i, strripos($img_i, '/')+1);
        }
        $img_name = substr($img_i, 0, strlen($img_i) - 4);
        $img_name = str_replace(' ', '', $img_name);
        $file_name = $path . getFileNameOfUrl($filename) .'/' . $img_i;
        $file_name = str_replace(' ', '', $file_name);
        $file_path = substr($file_name, 0, strrpos($file_name, '/'));

        $i++;
        if (!is_dir($file_path)) {
                mkdir($file_path, 0777, true);
        }
        if (!is_dir($file_name)) {
            $file_size = zip_entry_filesize($img);
            if ($file_size < (1024 * 1024 * 10)) {
                $file_content = zip_entry_read($img, $file_size);
                $ext = strrchr($file_name, '.');
                if (!in_array($ext, ['.tbi', '.png', '.jpg', '.jpeg', '.gif', '.bmp'])) {
                    array_push($record_info['error'], $img_name);//记录错误图片名
                    array_push($record_info['error_path'], $file_name);//记录错误图片名地址
                    continue;
                }
                if ($ext == '.tbi') {
                    $ext = '.png';
                    $file_name = substr($file_name, 0, strlen($file_name) - 4) .$ext;
                }

                $file_res = file_put_contents($file_name, $file_content);
                if (!$file_res ) {
                    array_push($record_info['error'], $img_name);//记录错误图片名
                    array_push($record_info['error_path'], $file_name);//记录错误图片名地址
                    continue;
                }
                    array_push($record_info['right'], $img_name);//记录上传云的正确图片名
                    array_push($record_info['right_path'], $file_name);//记录上传云的正确图片名地址
            } else {
                array_push($record_info['error'], $img_name);//记录错误图片名
                array_push($record_info['error_path'], $file_name);//记录错误图片名地址
            }
        }

        zip_entry_close($img);
        }
        zip_close($resource);
        return $record_info;
    }

    /**
     * 各类型图片生成
     *
     * @param unknown $photoPath            
     * @param unknown $ext            
     * @param number $type            
     */
    public function photoCreateForGoodsHelper($upFilePath, $photoPath, $ext, $type, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id, $domain, $bucket, $upload_img, $website_id, $shop_id) {
        $width1 = 0.6 * $width;
        $width2 = 0.4 * $width;
        $width3 = 0.2 * $width;
        $width4 = 0.1 * $width;
        $height1 = 0.6 * $height;
        $height2 = 0.4 * $height;
        $height3 = 0.2 * $height;
        $height4 = 0.1 * $height;
        $photoArray = array(
            "bigPath" => array(
                "path" => '',
                "width" => $width1,
                "height" => $height1,
                'type' => '1'
            ),
            "middlePath" => array(
                "path" => '',
                "width" => $width2,
                "height" => $height2,
                'type' => '2'
            ),
            "smallPath" => array(
                "path" => '',
                "width" => $width3,
                "height" => $height3,
                'type' => '3'
            ),
            "littlePath" => array(
                "path" => '',
                "width" => $width4,
                "height" => $height4,
                'type' => '4'
            )
        );

        $photoArray["bigPath"]["path"] = $upFilePath . md5(time() . $pic_tag) . "1" . $ext;
        $photoArray["middlePath"]["path"] = $upFilePath . md5(time() . $pic_tag) . "2" . $ext;
        $photoArray["smallPath"]["path"] = $upFilePath . md5(time() . $pic_tag) . "3" . $ext;
        $photoArray["littlePath"]["path"] = $upFilePath . md5(time() . $pic_tag) . "4" . $ext;
        // 循环生成4张大小不一的图
        foreach ($photoArray as $k => $v) {
//            if (stristr($type, $v['type'])) {
            $upload = new Upload();
            $result = $upload->uploadThumbFile($photoPath, $v["path"], $v["width"], $v["height"]);
            if ($result["code"]) {
                $photoArray[$k]["path"] = $result["path"];
            } else {
                return 0;
            }
            //}
        }
        $album = new Album();
        if ($pic_id == "") {
            $retval = $album->addPicture($pic_name, $pic_tag, $album_id, $upload_img, $width . "," . $height, $width . "," . $height, $photoArray["bigPath"]["path"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["middlePath"]["path"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["smallPath"]["path"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["littlePath"]["path"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $shop_id, $this->upload_type, $domain, $bucket, $website_id);
        } else {
            $retval = $album->ModifyAlbumPicture($pic_id, $upload_img, $width . "," . $height, $width . "," . $height, $photoArray["bigPath"]["path"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["middlePath"]["path"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["smallPath"]["path"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["littlePath"]["path"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $shop_id, $this->upload_type, $domain, $bucket);
            $retval = $pic_id;
        }
        return $retval;
    }

    /*
     * 根据csv图片获取图片id
     */

    public function getGoodsImg($picture, $shop_id = 0, $website_id =0) {
        $img = [
            'picture' => 0,
            'img_id_array' => ''
        ];
        if (!$picture) {
            return $img;
        }
        $allPics = explode(';', $picture);
        $albumPic = new AlbumPictureModel();
        foreach ($allPics as $key => $imgurl) {
            if (!$imgurl) {
                continue;
            }
            $picDetail = explode('|', $imgurl);
            $picDetail = explode(':', $picDetail[0]);
            $pic = $albumPic->getFirstData(['pic_name' => ['like','%'.$picDetail[0].'%'],'website_id' => $website_id, 'shop_id' => $shop_id],'upload_time desc'); //根据图片名称查找已导入的图片数据
            if ($picDetail[1] != 1 || !$pic) {
                continue;
            }
            if (!$key) {//第一张作为主图
                $img['picture'] = $pic['pic_id'];
            }
            $img['img_id_array'][] = $pic['pic_id'];
        }
        if ($img['img_id_array']) {
            $img['img_id_array'] = implode(',', $img['img_id_array']);
        }
        return $img;
    }

    /*
     * 商城数据包根据图片获取图片id
     */

    public function getGoodsImgForSelf($picture) {
        $img = [
            'picture' => 0,
            'img_id_array' => ''
        ];
        if (!$picture) {
            return $img;
        }
        $allPics = explode('|', $picture);
        $albumPic = new AlbumPictureModel();
        foreach ($allPics as $key => $imgurl) {
            if (!$imgurl) {
                continue;
            }
            $pic = $albumPic->getFirstData(['pic_name' => $imgurl],'upload_time desc'); //根据图片名称查找已导入的图片数据
            if (!$pic) {
                continue;
            }
            if (!$key) {//第一张作为主图
                $img['picture'] = $pic['pic_id'];
            }
            $img['img_id_array'][] = $pic['pic_id'];
        }
        if ($img['img_id_array']) {
            $img['img_id_array'] = implode(',', $img['img_id_array']);
        }
        return $img;
    }

    /*
     * 添加商品
     */

    public function addGoodsByLink($data) {
        if (!$data['taoId']) {
            return false;
        }
        $goods = new VslGoodsModel();
        $check = $goods->getInfo(['taobao_id' => $data['taoId'], 'shop_id' => $this->instance_id, 'website_id' => $this->website_id], 'goods_id');
        $insertData = [
            'goods_name' => $data['taoTitle'],
            'price' => $data['taoPrice'],
            'stock' => $data['taoStock'],
            'description' => $data['taoDesc'],
            'update_time' => time(),
            'state' => 0,
        ];
        if ($data['imgArray']) {
            $img = $this->dealTaoImg($data['imgArray']);
            $insertData['picture'] = $img['picture'];
            $insertData['img_id_array'] = $img['img_id_array'];
        }
        $goodsSku = new VslGoodsSkuModel();
        if ($check) {
            $result = $goods->save($insertData, ['goods_id' => $check['goods_id']]);
            $checkSku = $goodsSku->getInfo(['goods_id' => $check['goods_id']], 'sku_id');
            if ($checkSku) {
                $goodsSku->save(['price' => $insertData['price'], 'stock' => $insertData['stock'], 'update_date' => time()], ['sku_id' => $checkSku['sku_id']]);
            } else {
                $goodsSku->save(['goods_id' => $check['goods_id'], 'price' => $insertData['price'], 'stock' => $insertData['stock'], 'update_date' => time(), 'create_date' => time()]);
            }
            $status = 1;
        } else {
            $insertData['create_time'] = time();
            $insertData['shop_id'] = $this->instance_id;
            $insertData['website_id'] = $this->website_id;
            $insertData['taobao_id'] = $data['taoId'];
            $result = $goods->save($insertData);
            if ($result) {
                $goodsSku->save(['goods_id' => $result, 'price' => $insertData['price'], 'stock' => $insertData['stock'], 'update_date' => time(), 'create_date' => time()]);
            }
            $status = 2;
        }
        if (!$result) {
            return false;
        }

        return $status;
    }

    /*
     * 处理淘宝图片
     */

    public function checkImg($file) {
        if (empty($file)) {
            return false;
        }

        $ch = curl_init();
        $timeout = 3;
        curl_setopt($ch, CURLOPT_URL, $file);
        curl_setopt($ch, CURLOPT_HEADER, 1); //将文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将获取的信息以字符串返回
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); //设置等待时间
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //禁止验证对等证书
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); //获取请求状态码
        curl_close($ch);
        if ($http_code == '200') {
            return true;
        }
        return false;
    }

    public function dealTaoImg($imgArray,$platForm = '0') {
        @ini_set('default_socket_timeout', 2);
        $img = array('picture' => 0, 'img_id_array' => []);
        if (!$imgArray) {
            return $img;
        }
        $timeout = array(
            'http' => array(
                'timeout' => 5//设置一个超时时间，单位为秒 
            )
        );
        $ctx = stream_context_create($timeout);
        $albumSer = new Album();
        $album_id = $albumSer->getDefaultAlbumDetail()['album_id'];
        foreach ($imgArray as $key => $val) {
            //$file = substr($val, 0, strripos($val, '_'));
            $file = $val;
            if (!strstr($file, 'http')) {
                $file = 'https:' . $file;
            } else {
                $file = substr($file, strripos($file, 'http'));
            }
            if (!$file || !$this->checkImg($file)) {
                continue;
            }
            $fileInfo = pathinfo($file);
            if ($fileInfo['extension'] == 'SS2') {
                $fileInfo['extension'] = 'jpg';
            }
            $imageSize = getimagesize($file);
            $filename = $val;
            $albumPic = new AlbumPictureModel();
            $checkPic = $albumPic->getInfo(['pic_name' => $filename, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id], 'pic_id');
            if( strripos($file, '.jpg_')){
                $file = substr($file, 0, strripos($file, '.jpg_') + 4);
            }
            if($platForm == "京东"){
                $file = substr_replace($file,"s800x800_",strpos($file,"n5/")+3, strpos($file,"jfs")-strpos($file,"n5/")-3);
            }
            if ($checkPic) {
                $picId = $checkPic['pic_id'];
            } elseif ($fileInfo['extension'] == 'jpg') {
                if($platForm == "京东"){
                    $picId = $albumSer->addPicture($filename, $filename, $album_id, $file, $imageSize[0] . ',' . $imageSize[1], $imageSize[0] . ',' . $imageSize[1], substr_replace($file,"s800x800_",strpos($file,"n5/")+3, strpos($file,"jfs")-strpos($file,"n5/")-3), '800,800', '800,800', substr_replace($file,"s400x400_",strpos($file,"n5/")+3, strpos($file,"jfs")-strpos($file,"n5/")-3), '400,400', '400,400', substr_replace($file,"s200x200_",strpos($file,"n5/")+3, strpos($file,"jfs")-strpos($file,"n5/")-3), '200,200', '200,200', substr_replace($file,"s100x100_",strpos($file,"n5/")+3, strpos($file,"jfs")-strpos($file,"n5/")-3), '100,100', '100,100', $this->instance_id, 1, '', '');
                }else{
                    $picId = $albumSer->addPicture($filename, $filename, $album_id, $file, $imageSize[0] . ',' . $imageSize[1], $imageSize[0] . ',' . $imageSize[1], $file . '_800x800.' . $fileInfo['extension'], '800,800', '800,800', $file . '_400x400.' . $fileInfo['extension'], '400,400', '400,400', $file . '_200x200.' . $fileInfo['extension'], '200,200', '200,200', $file . '_100x100.' . $fileInfo['extension'], '100,100', '100,100', $this->instance_id, 1, '', '');
                }
            } else {
                $picId = $albumSer->addPicture($filename, $filename, $album_id, $file, $imageSize[0] . ',' . $imageSize[1], $imageSize[0] . ',' . $imageSize[1], $file, '--', '--', $file, '--', '--', $file, '--', '--', $file, '--', '--', $this->instance_id, 1, '', '');
            }
            if (!$key) {
                $img['picture'] = $picId;
            }
            $img['img_id_array'][] = $picId;
        }
        if ($img['img_id_array']) {
            $img['img_id_array'] = implode(',', $img['img_id_array']);
        }
        return $img;
    }

    /*
     * 商品导入相关数据关联处理
     */

    public function setOtherData($goods_id = 0, $category_name = '', $attr_name = '', $spec_name = '', $spec_value = '', $attr_value = '', $brand_name = '', $website_id = 0, $shop_id = 0) {
        if (!$goods_id || !$attr_name) {
            return false;
        }
        $data = [];
        $attrModel = new VslAttributeModel();
        $attrModel->startTrans();
        try {
            $checkAttr = $attrModel->getInfo(['attr_name' => $attr_name, 'website_id' => $website_id]);
            if ($checkAttr) {
                $data['attr_id'] = $checkAttr['attr_id'];
            } else {
                $data['attr_id'] = $attrModel->save(['attr_name' => $attr_name, 'is_use' => 1, 'website_id' => $website_id]);
            }
            $data['categoryArr'] = $this->setCategory($category_name, $data['attr_id'], $website_id); //根据品类id和分类名称处理商品分类

            $data['brand_id'] = $this->setBrand($brand_name, $data['categoryArr'], $website_id); //根据分类和品牌处理商品品牌

            $data['goods_spec_format'] = $this->setSpecAndSku($spec_name, $spec_value, $goods_id, $data['attr_id'], $website_id, $shop_id);

            $data['attr_value'] = $this->setAttrValue($attr_value, $goods_id, $data['attr_id'], $website_id, $shop_id);

            $attrModel->commit();
            return $data;
        } catch (\Exception $e) {
            $attrModel->rollback();
            return false;
        }

        if (!$brand_name) {
            return 0;
        }
    }

    /*
     * 商品导入商品分类处理
     */

    public function setCategory($category_name = '', $attr_id = 0, $website_id) {
        $category = array(
            'category_id' => 0,
            'category_id_1' => 0,
            'category_id_2' => 0,
            'category_id_3' => 0
        );
        if (!$category_name) {
            return $category;
        }
        $categoryArr = explode('>', $category_name);
        $cateModel = new VslGoodsCategoryModel();
        try {
            if ($categoryArr[1]) {//有二级分类
                $cate_1 = $cateModel->getInfo(['category_name' => $categoryArr[0], 'level' => 1, 'website_id' => $website_id])['category_id'];
                if (!$cate_1) {
                    $cateModel = new VslGoodsCategoryModel();
                    $cate_1 = $cateModel->save(['category_name' => $categoryArr[0],'short_name' => $categoryArr[0], 'level' => 1, 'is_visible' => 1, 'website_id' => $website_id]);
                }
                $category['category_id_1'] = $cate_1 ?: 0;
            } else {//没有二级分类
                $cate_1 = $cateModel->getInfo(['category_name' => $categoryArr[0], 'level' => 1, 'attr_id' => $attr_id, 'website_id' => $website_id])['category_id'];
                if (!$cate_1) {
                    $cateModel = new VslGoodsCategoryModel();
                    $cate_1 = $cateModel->save(['category_name' => $categoryArr[0], 'short_name' => $categoryArr[0], 'level' => 1, 'attr_id' => $attr_id, 'is_visible' => 1, 'website_id' => $website_id]);
                }
                $category['category_id_1'] = $cate_1 ?: 0;
                $category['category_id'] = $cate_1 ?: 0;
                return $category;
            }
            if ($categoryArr[2]) {
                $cate_2 = $cateModel->getInfo(['category_name' => $categoryArr[1], 'level' => 2, 'pid' => $category['category_id_1'], 'website_id' => $website_id])['category_id'];
                if (!$cate_2) {
                    $cateModel = new VslGoodsCategoryModel();
                    $cate_2 = $cateModel->save(['category_name' => $categoryArr[1], 'short_name' => $categoryArr[1], 'level' => 2, 'pid' => $category['category_id_1'], 'is_visible' => 1, 'website_id' => $website_id]);
                }
                $category['category_id_2'] = $cate_2 ?: 0;
            } else {//没有三级分类
                $cate_2 = $cateModel->getInfo(['category_name' => $categoryArr[1], 'level' => 2, 'pid' => $category['category_id_1'], 'attr_id' => $attr_id, 'website_id' => $website_id])['category_id'];
                if (!$cate_2) {
                    $cateModel = new VslGoodsCategoryModel();
                    $cate_2 = $cateModel->save(['category_name' => $categoryArr[1], 'short_name' => $categoryArr[1], 'level' => 2, 'pid' => $category['category_id_1'], 'is_visible' => 1, 'attr_id' => $attr_id, 'website_id' => $website_id]);
                }
                $category['category_id_2'] = $cate_2 ?: 0;
                $category['category_id'] = $cate_2 ?: 0;
                return $category;
            }
            $cate_3 = $cateModel->getInfo(['category_name' => $categoryArr[2], 'level' => 3, 'pid' => $category['category_id_2'], 'attr_id' => $attr_id, 'website_id' => $website_id])['category_id'];
            if (!$cate_3) {
                $cateModel = new VslGoodsCategoryModel();
                $cate_3 = $cateModel->save(['category_name' => $categoryArr[2], 'short_name' => $categoryArr[2], 'level' => 3, 'pid' => $category['category_id_2'], 'is_visible' => 1, 'attr_id' => $attr_id, 'website_id' => $website_id]);
            }
            $category['category_id_3'] = $cate_3 ?: 0;
            $category['category_id'] = $cate_3 ?: 0;
            return $category;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /*
     * 商品导入品牌处理
     */

    public function setBrand($brand_name = '', $categoryArr = [], $website_id = 0) {
        if (!$brand_name) {
            return 0;
        }
        $brandModel = new VslGoodsBrandModel();
        $brand = $brandModel->getInfo(['brand_name' => $brand_name, 'website_id' => $website_id], 'brand_id');
        if ($brand) {
            $brand_id = $brand['brand_id'];
        } else {
            $brand_id = $brandModel->save(['brand_name' => $brand_name, 'website_id' => $website_id]);
        }
        $category_id = $categoryArr['category_id_3'] ?: $categoryArr['category_id_2'] ?: $categoryArr['category_id_1']; //根据导入的分类确定查询哪一级分类的关联品牌
        if (!$category_id) {
            return $brand_id;
        }
        $cateModel = new VslGoodsCategoryModel();
        $checkCategoryBrand = $cateModel->getInfo(['category_id' => $category_id, 'website_id' => $website_id], 'brand_id_array'); //查询分类关联品牌
        if (!$checkCategoryBrand['brand_id_array']) {
            $cateModel->save(['brand_id_array' => $brand_id], ['category_id' => $category_id, 'website_id' => $website_id]);
        } else {
            $brand_id_array = explode(',', $checkCategoryBrand['brand_id_array']);
            if (!in_array($brand_id, $brand_id_array)) {
                array_push($brand_id_array, $brand_id);
                $cateModel->save(['brand_id_array' => implode(',', $brand_id_array)], ['category_id' => $category_id, 'website_id' => $website_id]);
            }
        }
        return $brand_id;
    }

    /*
     * 商品导入处理商品属性
     */

    public function setAttrValue($attr_value = '', $goods_id = 0, $attr_id = 0, $website_id = 0, $shop_id = 0) {
        if (!$attr_value || !$goods_id) {
            return;
        }

        $goodsAttrModel = new VslGoodsAttributeModel();
        // 删除商品属性
        $goodsAttrModel->where(['goods_id' => $goods_id])->delete();
        $attrValueModel = new VslAttributeValueModel();
        $attr_value_arr = explode(';', $attr_value);
        $goods_attr_arr = array();
        foreach ($attr_value_arr as $k_av => $v_av) {
            $attr_value_one = explode(':', $v_av);
            if (!$attr_value_one[0] || !$attr_value_one[1]) {//没有属性或者属性值都不执行
                continue;
            }
            $attr_value_id = $attrValueModel->getInfo(['attr_value_name' => $attr_value_one[0], 'attr_id' => $attr_id, 'type' => 1, 'website_id' => $website_id, 'shop_id' => array(['eq', 0], ['eq', $shop_id], 'or')], 'attr_value_id')['attr_value_id'];
            if (!$attr_value_id) {
                $attr_value_id = $attrValueModel->save(['attr_value_name' => $attr_value_one[0], 'attr_id' => $attr_id, 'type' => 1, 'website_id' => $website_id, 'shop_id' => $shop_id, 'is_search' => 1]);
            }
            $goods_attr_arr[$k_av] = array(
                'goods_id' => $goods_id,
                'shop_id' => $shop_id,
                'attr_value_id' => $attr_value_id,
                'attr_value' => $attr_value_one[0],
                'attr_value_name' => $attr_value_one[1],
                'create_time' => time(),
                'website_id' => $website_id,
            );
        }
        return $goodsAttrModel->saveAll($goods_attr_arr);
    }

    /*
     * 商品导入处理规格信息
     */

    public function setSpecAndSku($spec_name = '', $spec_value = '', $goods_id = 0, $attr_id = 0, $website_id = 0, $shop_id = 0) {
        if (!$spec_name || !$spec_value || !$goods_id) {
            return false;
        }
        try {
            $spec_name_arr = explode(':', $spec_name);
            $spec_value_arr = explode(';', $spec_value);
            $spec_arr_format = array();
            $specModel = new VslGoodsSpecModel();
            $goodsModel = new VslGoodsModel();
            $specValueModel = new VslGoodsSpecValueModel();
            $skuModel = new VslGoodsSkuModel();
            // 删除商品sku
            $skuModel->destroy(['goods_id' => $goods_id]);
            $sku_list = [];
            $spec_id_arr = [];
            $is_platform = 1;
            if ($shop_id) {
                $is_platform = 0;
            }
            foreach ($spec_name_arr as $ks => $vs) {

                $check_spec = $specModel->getInfo(['spec_name' => $vs, 'website_id' => $website_id, 'shop_id' => array(['eq', 0], ['eq', $shop_id], 'or'), 'show_type' => 1], 'spec_id,goods_attr_id');

                if (!$check_spec) {
                    $specModel = new VslGoodsSpecModel();
                    $spec_id = $specModel->save(['spec_name' => $vs, 'website_id' => $website_id, 'shop_id' => $shop_id, 'show_type' => 1, 'goods_attr_id' => $attr_id, 'sort' => 0, 'create_time' => time(), 'is_visible' => 1, 'is_screen' => 0, 'is_platform' => $is_platform]);
                } else {
                    $goods_attr_id_arr = explode(',', $check_spec['goods_attr_id']);
                    if (!in_array($attr_id, $goods_attr_id_arr)) {
                        array_push($goods_attr_id_arr, $attr_id);
                        $specModel->save(['goods_attr_id' => implode(',', $goods_attr_id_arr)], ['spec_id' => $check_spec['spec_id']]);
                    }
                    $spec_id = $check_spec['spec_id'];
                }
                $spec_id_arr[$ks] = $spec_id;
                $spec_arr_format[$ks]['spec_name'] = $vs;
                $spec_arr_format[$ks]['spec_id'] = $spec_id;
                $spec_arr_format[$ks]['value'] = array();
                $spec_arr_format[$ks]['value_id'] = array();
            }
            if ($attr_id) {
                $attrModel = new VslAttributeModel();
                $attrModel->save(['spec_id_array' => implode(',', $spec_id_arr)], ['attr_id' => $attr_id]);
            }
            $allStock = 0;
            foreach ($spec_value_arr as $ksv => $vsv) {
                $spec_value_one = explode(':', $vsv);
                $sku_list[$ksv]['price'] = $spec_value_one[1];
                $sku_list[$ksv]['market_price'] = $spec_value_one[2];
                $sku_list[$ksv]['cost_price'] = $spec_value_one[3];
                $sku_list[$ksv]['stock'] = $spec_value_one[4];
                $allStock += $spec_value_one[4];
                $sku_list[$ksv]['code'] = $spec_value_one[5];
                $sku_one = explode('|', $spec_value_one[0]);
                $sku_list[$ksv]['sku_name'] = implode(' ', $sku_one);
                $sku_id_arr = [];

                foreach ($sku_one as $kso => $vso) {
                    $spec_value_id = $specValueModel->getInfo(['spec_value_name' => $vso, 'spec_id' => $spec_id_arr[$kso]], 'spec_value_id')['spec_value_id'];
                    if (!$spec_value_id) {
                        $specValueModel = new VslGoodsSpecValueModel();
                        $spec_value_id = $specValueModel->save(['spec_value_name' => $vso, 'spec_id' => $spec_id_arr[$kso], 'is_visible' => 1, 'create_time' => time()]);
                    }
                    if (!in_array($spec_value_id, $spec_arr_format[$kso]['value_id'])) {
                        array_push($spec_arr_format[$kso]['value'], ['spec_value_name' => $vso, 'spec_name' => $spec_name_arr[$kso], 'spec_id' => $spec_id_arr[$kso], 'spec_value_id' => $spec_value_id, 'spec_show_type' => 1, 'spec_value_data' => '']);
                        array_push($spec_arr_format[$kso]['value_id'], $spec_value_id);
                    }
                    $sku_id_arr[$kso] = $spec_id_arr[$kso] . ':' . $spec_value_id;
                }

                $sku_id_str = implode(';', $sku_id_arr);
                $sku_list[$ksv]['attr_value_items'] = $sku_id_str;
                $sku_list[$ksv]['attr_value_items_format'] = $sku_id_str;
                $sku_list[$ksv]['goods_id'] = $goods_id;
                $sku_list[$ksv]['create_date'] = time();
                $sku_list[$ksv]['update_date'] = time();
            }
            foreach ($spec_arr_format as $kk => $vv) {
                unset($spec_arr_format[$kk]['value_id']);
            }
            $skuModel->saveAll($sku_list);
            if ($sku_list) {
                $goodsModel->save(['stock' => $allStock], ['goods_id' => $goods_id]);
            }
            return $spec_arr_format;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /*
     * 添加商品by接口
     */

    public function addGoodsByArray($data) {
        if (!$data['taoId']) {
            return false;
        }
        $goods = new VslGoodsModel();
        $check = $goods->getInfo(['taobao_id' => $data['taoId'], 'shop_id' => $data['instance_id'], 'website_id' => $data['website_id']], 'goods_id');
        $insertData = [
            'goods_name' => $data['taoTitle'],
            'price' => $data['taoPrice'],
            'market_price' => $data['marketprice'],
            'stock' => $data['taoStock'],
            'description' => $data['description'],
            'update_time' => time(),
            'state' => 0,
        ];
        if($data['platForm']){
            $insertData['wplatForm'] = $data['platForm'];
        }
        if($data['url']){
            $insertData['wurl'] = $data['url'];
        }
        if ($data['imgArray']) {
            $img = $this->dealTaoImg($data['imgArray'],$data['platForm']);
            $insertData['picture'] = $img['picture'];
            $insertData['img_id_array'] = $img['img_id_array'];
        }
        $goodsSku = new VslGoodsSkuModel();
        if ($check) {
            $result = $goods->save($insertData, ['goods_id' => $check['goods_id']]);
            $checkSku = $goodsSku->getInfo(['goods_id' => $check['goods_id']], 'sku_id');
            if ($checkSku) {
                $goodsSku->save(['price' => $insertData['price'], 'market_price' => $insertData['market_price'], 'stock' => $insertData['stock'], 'update_date' => time()], ['sku_id' => $checkSku['sku_id']]);
            } else {
                $goodsSku->save(['goods_id' => $check['goods_id'], 'price' => $insertData['price'], 'market_price' => $insertData['market_price'], 'stock' => $insertData['stock'], 'update_date' => time(), 'create_date' => time()]);
            }
            $status = 1;
        } else {
            $insertData['create_time'] = time();
            $insertData['shop_id'] = $data['instance_id'];
            $insertData['website_id'] = $data['website_id'];
            $insertData['taobao_id'] = $data['taoId'];
            $result = $goods->save($insertData);
            if ($result) {
                $goodsSku->save(['goods_id' => $result, 'price' => $insertData['price'], 'market_price' => $insertData['market_price'], 'stock' => $insertData['stock'], 'update_date' => time(), 'create_date' => time()]);
            }
            $status = 2;
        }
        if (!$result) {
            return false;
        }

        return $status;
    }

    function create_guid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125);// "}"
        return $uuid;
    }

    public function getDetail($detail_list){
        $detail = '<p>';
        if(isset($detail_list['detailMap'])){
            $detail_list['data']['detailMap'] = array_values($detail_list['detailMap']);
            $detail .= '<ul style="width:100%;display:block;">';
            foreach($detail_list['detailMap'] as $val){
                $detail.= '<li style="float:left;width:33%;line-height:30px;list-style: none;">'.preg_replace('/\d:/','',$val).'</li>';
            }
            $detail .= '<li style="clear:both"></li></ul>';
        }
        if(isset($detail_list['imgUrlMap'])){
            $detail .= '<div style="width:100%; text-align:center;">';
            foreach($detail_list['imgUrlMap'] as $img){
                $detail.= '<img style="width:auto;max-width:100%;height:auto;" src="'.$img.'">';
            }
            $detail .= '</div>';
        }
        $detail .= '</p>';
        return $detail;
    }

    public function getModuleIdByModule($controller,$action,$module){
        $condition = array(
            'controller' => $controller,
            'method' => $action,
            'module' => $module
        );
        $module_model = new ModuleModel();
        $count = $module_model->where($condition)->count('module_id');
        if($count > 1)
        {
            $condition = array(
                'module' => $module,
                'controller' => $controller,
                'method' => $action,
                'pid' => array('<>', 0)
            );
        }

        $res = $module_model->where($condition)->order('level DESC')->find();
        return $res;
    }

    /**
     * 登录
     *
     * @param unknown $user_name
     * @param unknown $password
     */
    public function login($usert_tel, $password = '') {
        $userModel = new UserModel();
        $website_id = checkUrl();
        $port = explode("/", $_SERVER['REQUEST_URI']);

        $condition = [
            'user_tel' => $usert_tel,
            'website_id' => $website_id,
            'user_password' => md5($password),
            'port' => $port[1]
        ];
        $userInfo = $userModel->getInfo($condition, $field = 'uid,user_tel,user_status,user_name,user_headimg,is_system,instance_id,is_member,current_login_ip, current_login_time, current_login_type,website_id,port');
        if (!empty($userInfo)) {
            if ($userInfo['user_status'] == 0) {
                return USER_LOCK;
            } else {
                //$this->initLoginInfo($userInfo);
                //登录成功后增加用户的登录次数
                $set_inc_condition['uid'] = $userInfo['uid'];
                $userModel->save(['login_num' => $userInfo['login_num'] + 1, 'user_token' => md5($userInfo['uid'])], $set_inc_condition);
                return $userInfo;
            }
        } else {
            return USER_ERROR;
        }
    }

    /**
     * 修改商品导入数据
     * @param $data [0未执行，1执行中 2失败 3成功 4等待中 5 部分完成]
     * @param $condition
     * @return mixed
     */
    public function updateGoodsHelpInfo($data, $condition)
    {
        $goodsHelp = new VslGoodsHelpModel();
        return $goodsHelp->save($data, $condition);
    }

    /**
     * 获取商品助手上传文件列表
     * @param int $page_index
     * @param int $page_size
     * @param string $condition
     * @param string $order
     * @return array
     */
    public function getGoodsHelperList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $goodsHelper = new VslGoodsHelpModel();
        $list = $goodsHelper->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
    }
}
