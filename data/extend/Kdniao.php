<?php

namespace data\extend;

use data\model\ConfigModel;
use think\Config;

/**
 * 快递鸟即时查询接口
 * @author  www.vslai.com
 *
 */
class Kdniao
{
    private $user_id;//商户ID
    private $api_key;     //商户秘钥
    private $request_type;//请求类型
    private $request_url; //请求URL

    /**
     * 构造函数
     * @param int $website_id
     * @param int $shop_id
     * @param string $request_type
     */
    public function __construct($website_id, $shop_id, $request_type)
    {
        $config_model = new ConfigModel();
        $condition['website_id'] = $website_id;
        $condition['instance_id'] = $shop_id;
        switch ($request_type) {
            case 'form':
                // 电子面单
                $condition['key'] = 'DELIVERY_ASSISTANT';
                break;
        }
        $express_config = $config_model::get($condition);
        $value = json_decode($express_config['value'], true);
        $this->user_id = $value["user_id"];
        $this->api_key = $value["api_key"];

        $config = Config::get('kdn.' . $request_type);
        $this->request_type = $config['request_type'];
        $this->request_url = $config['https_api'];
    }


    //---------------------------------------------

    /**
     * Json方式 查询订单物流轨迹
     */
    public function getOrderTracesByJson($requestData)
    {
        //$requestData= "{'OrderCode':'','ShipperCode':'YTO','LogisticCode':'12345678'}";
        $datas = array(
            'EBusinessID' => $this->user_id,
            'RequestType' => $this->request_type,
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData);
        $result = $this->sendPost($this->request_url, $datas);
        return $result;
    }

    public function form($order_data)
    {
        $order_data_json = json_encode($order_data, JSON_UNESCAPED_UNICODE);
        $data = array(
            'EBusinessID' => $this->user_id,
            'RequestType' => $this->request_type,
            'RequestData' => urlencode($order_data_json),
            'DataType' => '2',
        );
        $data['DataSign'] = $this->encrypt($order_data_json);
        $result = $this->sendPost($this->request_url, $data);
        return json_decode($result, true);
    }

    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    public function sendPost($url, $datas)
    {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if (empty($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        $httpheader .= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    public function encrypt($data)
    {
        return urlencode(base64_encode(md5($data . $this->api_key)));
    }
}

