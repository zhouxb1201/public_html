<?php
namespace addons\qlkefu\server;

use addons\qlkefu\model\VslQlkefuModel;
use data\service\BaseService;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
use think\Config;

class Qlkefu extends BaseService
{
    protected $ql_domain;
    
    function __construct()
    {
        Config::load('addons/qlkefu/config.php');
        $this->ql_domain = Config::get('qlkefu.ql_domain');
        $this->ql_port = Config::get('qlkefu.ql_port');
        $this->salt = Config::get('qlkefu.salt');
        parent::__construct();
    }
    
    /**
     * 添加客服系统
     */
    public function addQlkefu($input)
    {
        $qlkefu = new VslQlkefuModel();
        $qlkefu->startTrans();
        try {
            $qlkefu->save($input);
            $qlkefu->commit();
            return 1;
        } catch (\Exception $e) {
            $qlkefu->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * 修改客服系统
     */
    public function updateQlkefu($input ,$where)
    {
        $qlkefu = new VslQlkefuModel();
        $qlkefu->startTrans();
        try {
            $qlkefu->save($input,$where);
            $qlkefu->commit();
            return 1;
        } catch (\Exception $e) {
            $qlkefu->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * 获取客服系统详情
     */
    public function getQlkefuDetail($condition)
    {
        $qlkefu = new VslQlkefuModel();
        $info = $qlkefu->getDetail($condition);
        return $info;
    }
    
    /**
     * 获取客服列表
     */
    public function getQlkefuList($condition)
    {
        $qlkefu = new VslQlkefuModel();
        $info = $qlkefu->getList($condition,$field);
        return $info;
    }
    
    public function qlkefuConfig($website_id,$shop_id)
    {
        $qlkefu = new VslQlkefuModel();
        $info = $qlkefu->getDetail(['website_id' => $website_id,'shop_id'=>$shop_id]);
        $info['domain'] = getDomain($website_id);
        $info['ql_domain'] = $this->ql_domain;
        $info['ql_port'] = $this->ql_port;
        return $info;
    }
    public function saveConfig($is_qlkefu)
    {
        $addonsconfig_service = new AddonsConfigService();
        $info = $addonsconfig_service->getAddonsConfig("qlkefu");
        if (!empty($info)) {
            $addonsconfig = new AddonsConfigModel();
            $res = $addonsconfig->save(['is_use' => $is_qlkefu, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'qlkefu'
            ]);
        } else {
            $res = $addonsconfig_service->addAddonsConfig('', '客服系统设置', $is_qlkefu, 'qlkefu');
        }
        return $res;
    }
    
    /**
     * 查询开启客服的店铺
     */
    public function isQlkefuShop($website_id)
    {
        $qlkefu = new VslQlkefuModel();
        $list = $qlkefu->getList(['website_id' => $website_id,'is_use'=>1],'shop_id');
        $data = [];
        $data['is_use'] = (count($list)>0)?1:0;
        return $data;
    }
    
    /**
     * 验证客服标识
     */
    public function verifyQlkefu($seller_code)
    {
        $data = $this->createVerify();
        $data['seller_code'] = $seller_code;
        $data['domain'] = getDomain($this->website_id);
        $url = $this->ql_domain."/api/seller/verifyqlkefu";
        $res = $this->sendData($url,$data);
        $result = json_decode($res, true);
        return $result;
    }
    
    /**
     * 获取消息列表
     */
    public function chatList($uid,$seller_codes)
    {
        $data = $this->createVerify();
        $data['uid'] = $uid;
        $data['seller_codes'] = $seller_codes;
        $url = $this->ql_domain."/api/seller/chatlist";
        $res = $this->sendData($url,$data);
        $result = json_decode($res, true);
        return $result;
    }
    
    /**
     * 发送数据
     */
    public function sendData($url,$data=''){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
    
    /**
     * 生成timestamp nonce_str signature
     */
    public function createVerify(){
        $data = $arr = [];
        $data['timestamp'] = $arr['timestamp'] = time();
        $data['nonce_str'] = $arr['nonce_str'] = uniqid();
        $arr['salt'] = $this->salt;
        //按照首字母大小写顺序排序
        sort($arr, SORT_STRING);
        //拼接成字符串
        $str = implode($arr);
        //进行加密,转换成大写
        $data['signature'] = strtoupper(md5(sha1($str)));
        return $data;
    }
}