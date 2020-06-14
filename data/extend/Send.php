<?php
namespace data\extend;
use think\Validate;
use think\Controller;
use data\extend\email\Email;
use data\extend\email\Phpmailer;
use data\service\Config;
use data\service\WebSite;
class Send extends \think\Model
{
    public static $sms_config =null;
    function __construct(){
        $config_service=new Config();
        $config = $config_service->getMobileMessage(0);
        static ::$sms_config= [
    		'appkey'		=> $config['value']['appKey'],//阿里大于APPKEY
    		'secretKey' 	=> $config['value']['secretKey'],//阿里大于secretKey
    		'FreeSignName' 	=> $config['value']['freeSignName'],//短信签名
	     ];
    }
    
	public function sms($data=[])
	{
		$validate = new Validate([
			['param','require|array','参数必填|参数必须为数组'],
			['mobile','require|/1[34578]{1}\d{9}$/','手机号错误|手机号错误'],
			['template','require','模板id错误'],
		]);
		if (!$validate->check($data)) {
			return $validate->getError();
		}
		define('TOP_SDK_WORK_DIR', CACHE_PATH.'sms_tmp/');
		define('TOP_SDK_DEV_MODE', false);
		vendor('alidayu.TopClient');
		vendor('alidayu.AlibabaAliqinFcSmsNumSendRequest');
		vendor('alidayu.RequestCheckUtil');
		vendor('alidayu.ResultSet');
		vendor('alidayu.TopLogger');
		$config = self::$sms_config;
		$c = new \TopClient;
		$c->appkey = $config['appkey'];
		$c->secretKey = $config['secretKey'];
		$req = new \AlibabaAliqinFcSmsNumSendRequest;
		$req->setExtend('');
		$req->setSmsType('normal');
		$req->setSmsFreeSignName($config['FreeSignName']);
		$req->setSmsParam(json_encode($data['param']));
		$req->setRecNum($data['mobile']);
		$req->setSmsTemplateCode($data['template']);
		$result = $c->execute($req);
		$result = $this->_simplexml_to_array($result);
		if(isset($result['code'])){
			return $result['sub_code'];
		}
		return true;
	}

	private function _simplexml_to_array($obj)
	{
		if(count($obj) >= 1){
			$result = $keys = [];
			foreach($obj as $key=>$value){
				isset($keys[$key]) ? ($keys[$key] += 1) : ($keys[$key] = 1);
				if( $keys[$key] == 1 ){
					$result[$key] = $this->_simplexml_to_array($value);
				}elseif( $keys[$key] == 2 ){
					$result[$key] = [$result[$key], $this->_simplexml_to_array($value)];
				}else if( $keys[$key] > 2 ){
					$result[$key][] = $this->_simplexml_to_array($value);
				}
			}
			return $result;
		}else if(count($obj) == 0){
			return (string)$obj;
		}
	}
	public function email_old($toemail, $title, $content){
	    $config = new Config();
	    $info = $config->getEmailMessage(0);
	    $mail = new Email();
	    $mail->setServer($info['value']['email_host'], $info['value']['email_id'], $info['value']['email_pass']);
	    $mail->setFrom($info['value']['email_addr']);
	    $mail->setReceiver($toemail);
	    $mail->setMailInfo($title, $content);
	    $result=$mail->sendMail();
	    return $result;
	}
	public function email($toemail, $title, $content){
            $config = new Config();
	        $info = $config->getEmailMessage(0);
            $website = new WebSite();
            $webInfo = $website->getWebSiteInfo();
             $Email = new Phpmailer();
            //设置PHPMailer使用SMTP服务器发送email
            $Email->IsSMTP();
            //设置字符串编码
            $Email->CharSet = 'UTF-8';

            $Email->ContentType = 'text/html';

            //添加收件人地址，可以使用多次来添加多个收件人
            if (is_array($toemail)){
                foreach ($toemail as $v){
                    $Email->AddAddress($v);
                }
            }else {
                $Email->AddAddress($toemail);
            }

            //设置邮件正文
            $Email->Body = $content;
            if(strpos($info['value']['email_host'],'ssl://') !== false){
                $Email->Port = 465;
            }else{
                $Email->Port = 587;
            }
            //设置邮件头的FROM字段
            $Email->From = $info['value']['email_id'];

            //设置发件人名称
            $Email->FromName = $webInfo['title'];

            //设置邮件标题
            $Email->Subject = $title;

            //设置SMTP服务器
            $Email->Host = $info['value']['email_host'];//SMTP服务器

            //smtp需要鉴权 这个必须是true
            $Email->SMTPAuth = true;
            
            //设置用户名密码
            $Email->Username = $info['value']['email_id'];//'421945437@qq.com'
            $Email->Password = $info['value']['email_pass'];//"oqzvqvolpdvhbhjj";//SMTP服务器的用户密码
            $result = $Email->Send();
            if($result) {
                return true;
            }else{
                return false;
            }
	}

}
?>