<?php

namespace app\wapapi\controller;
use addons\miniprogram\service\MiniProgram;
use data\extend\WchatOpen;
use think\Controller;
\think\Loader::addNamespace('data', 'data/');

class mip extends Controller
{
    /**
     * 微信开放平台授权事件接收Url
     */
    public function receiveAuthMessage()
    {

        $encryptMsg = file_get_contents('php://input');
        $xml_tree = new \DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $encrypt = $array_e->item(0)->nodeValue;
        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);
        // 只针对源码（config没有website_id去拿第三方配置信息）
        $website_id = checkUrl() ?: 1;
        $temp = $xml_tree->getElementsByTagName('AppId');
        $appid = $temp->item(0)->nodeValue;
        $wchat_open = new WchatOpen($website_id, $appid);
        $wchat_open->getComponentVerifyTicket($from_xml);
    }
   /**
    * 消息与事件接收URL
     */
    public function receiveEventMessage()
    {
        $encryptMsg = file_get_contents('php://input');
        $xml_tree = new \DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $encrypt = $array_e->item(0)->nodeValue;
        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);
        // 只针对源码（通过appid查询website_id,没有通过域名查询，否则默认源码都是1)
        if (!$websiteId = checkUrl()) {
        if ($_GET['appid']) {
            $miniProgramService = new MiniProgram();
            $websiteId = $miniProgramService->getWebsiteIdByAppId($_GET['appid']);
            }
        }
        $website_id = $websiteId ?: 1;
        $wchat_open = new WchatOpen($website_id);
        $wchat_open->getMessage($from_xml);
    }
}