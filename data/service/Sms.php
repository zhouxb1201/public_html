<?php


namespace data\service;

use data\service\notification\android\AndroidBroadcast;
use data\service\notification\android\AndroidUnicast;
use data\service\notification\ios\IOSUnicast;
use data\service\notification\ios\IOSBroadcast;

use data\model\MovementMessageModel;
use data\service\BaseService as BaseService;

require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidFilecast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidGroupcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidUnicast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidCustomizedcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSFilecast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSGroupcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSCustomizedcast.php');

class Sms extends BaseService{

	protected $an_appkey           = NULL;
    protected $ios_appkey           = NULL;
	protected $an_master_secret     = NULL;
    protected $ios_master_secret     = NULL;
    protected $umeng_secret     = NULL;
	protected $timestamp        = NULL;
	protected $validation_token = NULL;

	function __construct() {
        parent::__construct();
        $move = new MovementMessageModel();
        $condition['website_id'] = $this->website_id;
        $config = $move->getInfo($condition);

		$this->an_appkey = $config['an_appkey'];
        $this->ios_appkey = $config['ios_appkey'];
        $this->ios_master_secret = $config['ios_master_secret'];
        $this->an_master_secret = $config['an_master_secret'];
        $this->umeng_secret = $config['umeng_secret'];
		$this->timestamp = strval(time());
	}

    /**
     * $type:   1:全部,2:IOS广播,3:安卓广播,4:IOS单播,5:安卓单播
     * $title   标题
     * $content 内容
     * $device_token  设备号
     * $start_time   时间，格式2010-11-11 12:00:00，定时填写
     */
	function common_cast($type,$title,$content,$device_token='',$start_time=''){

	    if($type==1){
            $ios_send = $this->sendIOSBroadcast($title,$content,$start_time);
            $ios_send = json_decode($ios_send,true);
            $an_send = $this->sendAndroidBroadcast($title,$content,$start_time);
            $an_send = json_decode($an_send,true);
            $result['ios'] = $ios_send['ret'];
            $result['android'] = $an_send['ret'];
            return $result;
        }elseif($type==2){
            $result = $this->sendIOSBroadcast($title,$content,$start_time);
        }elseif($type==3){
            $result = $this->sendAndroidBroadcast($title,$content,$start_time);
        }elseif($type==4){
            $result = $this->sendIOSUnicast($title,$content,$device_token);
        }elseif($type==5){
            $result = $this->sendAndroidBroadcast($title,$content,$device_token);
        }
        return json_decode($result,true);
    }

	//安卓广播
	function sendAndroidBroadcast($title,$content,$start_time='') {
		try {
			$brocast = new AndroidBroadcast();
			$brocast->setAppMasterSecret($this->an_master_secret);
			$brocast->setPredefinedKeyValue("appkey",           $this->an_appkey);
			$brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			$brocast->setPredefinedKeyValue("ticker",           "Android broadcast ticker");
			$brocast->setPredefinedKeyValue("title",            $title);
			$brocast->setPredefinedKeyValue("text",             $content);
			$brocast->setPredefinedKeyValue("after_open",       "go_app");
            if($start_time!=''){
                $brocast->setPredefinedKeyValue("start_time",$start_time);
            }
			// Set 'production_mode' to 'false' if it's a test device. 
			// For how to register a test device, please see the developer doc.
			$brocast->setPredefinedKeyValue("production_mode", "true");
			// [optional]Set extra fields
			$brocast->setExtraField("test", "helloworld");
			$brocast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}

	//安卓单播
    function sendAndroidUnicast($title,$content,$tokens) {
        try {
            $unicast = new AndroidUnicast();
            $unicast->setAppMasterSecret($this->an_master_secret);
            $unicast->setPredefinedKeyValue("appkey",           $this->an_appkey);
            $unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            $unicast->setPredefinedKeyValue("device_tokens",    $tokens);
            $unicast->setPredefinedKeyValue("ticker",           "Android unicast ticker");
            $unicast->setPredefinedKeyValue("title",            $title);
            $unicast->setPredefinedKeyValue("text",             $content);
            $unicast->setPredefinedKeyValue("after_open",       "go_app");

            //$unicast->setPredefinedKeyValue("start_time",       time()+600);
            $unicast->setPredefinedKeyValue("production_mode", "true");
            $unicast->setExtraField("test", "helloworld");
            return $unicast->send();
        } catch (\Exception $e) {
            recordErrorLog($e);
            print("Caught exception: " . $e->getMessage());
        }
    }

	function sendAndroidFilecast() {
		try {
			$filecast = new AndroidFilecast();
			$filecast->setAppMasterSecret($this->an_master_secret);
			$filecast->setPredefinedKeyValue("appkey",           $this->an_appkey);
			$filecast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			$filecast->setPredefinedKeyValue("ticker",           "Android filecast ticker");
			$filecast->setPredefinedKeyValue("title",            "Android filecast title");
			$filecast->setPredefinedKeyValue("text",             "Android filecast text");
			$filecast->setPredefinedKeyValue("after_open",       "go_app");  //go to app
			$filecast->uploadContents("aa"."\n"."bb");
			$filecast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendAndroidGroupcast() {
		try {
			/* 
		 	 *  Construct the filter condition:
		 	 *  "where": 
		 	 *	{
    	 	 *		"and": 
    	 	 *		[
      	 	 *			{"tag":"test"},
      	 	 *			{"tag":"Test"}
    	 	 *		]
		 	 *	}
		 	 */
			$filter = 	array(
							"where" => 	array(
								    		"and" 	=>  array(
								    						array(
							     								"tag" => "test"
															),
								     						array(
							     								"tag" => "Test"
								     						)
								     		 			)
								   		)
					  	);
					  
			$groupcast = new AndroidGroupcast();
			$groupcast->setAppMasterSecret($this->an_master_secret);
			$groupcast->setPredefinedKeyValue("appkey",           $this->an_appkey);
			$groupcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set the filter condition
			$groupcast->setPredefinedKeyValue("filter",           $filter);
			$groupcast->setPredefinedKeyValue("ticker",           "Android groupcast ticker");
			$groupcast->setPredefinedKeyValue("title",            "Android groupcast title");
			$groupcast->setPredefinedKeyValue("text",             "Android groupcast text");
			$groupcast->setPredefinedKeyValue("after_open",       "go_app");
			// Set 'production_mode' to 'false' if it's a test device. 
			// For how to register a test device, please see the developer doc.
			$groupcast->setPredefinedKeyValue("production_mode", "true");
			$groupcast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendAndroidCustomizedcast() {
		try {
			$customizedcast = new AndroidCustomizedcast();
			$customizedcast->setAppMasterSecret($this->an_master_secret);
			$customizedcast->setPredefinedKeyValue("appkey",           $this->an_appkey);
			$customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);

			$customizedcast->setPredefinedKeyValue("alias",            "xx");
			// Set your alias_type here
			$customizedcast->setPredefinedKeyValue("alias_type",       "xx");
			$customizedcast->setPredefinedKeyValue("ticker",           "Android customizedcast ticker");
			$customizedcast->setPredefinedKeyValue("title",            "Android customizedcast title");
			$customizedcast->setPredefinedKeyValue("text",             "Android customizedcast text");
			$customizedcast->setPredefinedKeyValue("after_open",       "go_app");
			$customizedcast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendAndroidCustomizedcastFileId() {
		try {
			$customizedcast = new AndroidCustomizedcast();
			$customizedcast->setAppMasterSecret($this->an_master_secret);
			$customizedcast->setPredefinedKeyValue("appkey",           $this->an_appkey);
			$customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// if you have many alias, you can also upload a file containing these alias, then
			// use file_id to send customized notification.
			$customizedcast->uploadContents("aa"."\n"."bb");
			// Set your alias_type here
			$customizedcast->setPredefinedKeyValue("alias_type",       "xx");
			$customizedcast->setPredefinedKeyValue("ticker",           "Android customizedcast ticker");
			$customizedcast->setPredefinedKeyValue("title",            "Android customizedcast title");
			$customizedcast->setPredefinedKeyValue("text",             "Android customizedcast text");
			$customizedcast->setPredefinedKeyValue("after_open",       "go_app");
			$customizedcast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}

	//IOS广播
	function sendIOSBroadcast($title,$content,$start_time='') {
		try {
			$brocast = new IOSBroadcast();
			$brocast->setAppMasterSecret($this->ios_master_secret);
			$brocast->setPredefinedKeyValue("appkey",           $this->ios_appkey);
			$brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);

			$brocast->setPredefinedKeyValue("title", $title);
            //$brocast->setPredefinedKeyValue("description", "描述");
            $brocast->setPredefinedKeyValue("body", $content);
			$brocast->setPredefinedKeyValue("badge", 0);
            //$brocast->setPredefinedKeyValue("subtitle", "subtitle");  //副标题
			$brocast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$brocast->setPredefinedKeyValue("production_mode", "true");
            if(!empty($start_time)){
                $brocast->setPredefinedKeyValue("start_time",$start_time);
            }
			// Set customized fields

			 $brocast->setCustomizedField("test", "helloworld");
			$brocast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}

	//IOS单播
	function sendIOSUnicast($title,$content,$device_token) {
		try {
			$unicast = new IOSUnicast();
			$unicast->setAppMasterSecret($this->ios_master_secret);
			$unicast->setPredefinedKeyValue("appkey",           $this->ios_appkey);
			$unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set your device tokens here
			$unicast->setPredefinedKeyValue("device_tokens",   $device_token);
			$unicast->setPredefinedKeyValue("title", $title);
            $unicast->setPredefinedKeyValue("body", $content);
            $unicast->setPredefinedKeyValue("description", $content);
			$unicast->setPredefinedKeyValue("badge", 0);
			$unicast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$unicast->setPredefinedKeyValue("production_mode", "true");
			// Set customized fields
			$unicast->setCustomizedField("test", "helloworld");
			$unicast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendIOSFilecast() {
		try {
			$filecast = new IOSFilecast();
			$filecast->setAppMasterSecret($this->ios_master_secret);
			$filecast->setPredefinedKeyValue("appkey",           $this->ios_appkey);
			$filecast->setPredefinedKeyValue("timestamp",        $this->timestamp);

			$filecast->setPredefinedKeyValue("alert", "IOS 文件播测试");
			$filecast->setPredefinedKeyValue("badge", 0);
			$filecast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$filecast->setPredefinedKeyValue("production_mode", "false");
			// Upload your device tokens, and use '\n' to split them if there are multiple tokens
			$filecast->uploadContents("aa"."\n"."bb");
			$filecast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendIOSGroupcast() {
		try {
			/* 
		 	 *  Construct the filter condition:
		 	 *  "where": 
		 	 *	{
    	 	 *		"and": 
    	 	 *		[
      	 	 *			{"tag":"iostest"}
    	 	 *		]
		 	 *	}
		 	 */
			$filter = 	array(
							"where" => 	array(
								    		"and" 	=>  array(
								    						array(
							     								"tag" => "iostest"
															)
								     		 			)
								   		)
					  	);
					  
			$groupcast = new IOSGroupcast();
			$groupcast->setAppMasterSecret($this->ios_master_secret);
			$groupcast->setPredefinedKeyValue("appkey",           $this->ios_appkey);
			$groupcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set the filter condition
			$groupcast->setPredefinedKeyValue("filter",           $filter);
			$groupcast->setPredefinedKeyValue("alert", "IOS 组播测试");
			$groupcast->setPredefinedKeyValue("badge", 0);
			$groupcast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$groupcast->setPredefinedKeyValue("production_mode", "false");
			$groupcast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendIOSCustomizedcast() {
		try {
			$customizedcast = new IOSCustomizedcast();
			$customizedcast->setAppMasterSecret($this->ios_master_secret);
			$customizedcast->setPredefinedKeyValue("appkey",           $this->ios_appkey);
			$customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);

			// Set your alias here, and use comma to split them if there are multiple alias.
			// And if you have many alias, you can also upload a file containing these alias, then 
			// use file_id to send customized notification.
			$customizedcast->setPredefinedKeyValue("alias", "xx");
			// Set your alias_type here
			$customizedcast->setPredefinedKeyValue("alias_type", "xx");
			$customizedcast->setPredefinedKeyValue("alert", "IOS 个性化测试");
			$customizedcast->setPredefinedKeyValue("badge", 0);
			$customizedcast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$customizedcast->setPredefinedKeyValue("production_mode", "false");
			$customizedcast->send();
		} catch (\Exception $e) {
            recordErrorLog($e);
			print("Caught exception: " . $e->getMessage());
		}
	}
}

// Set your appkey and master secret here
/* these methods are all available, just fill in some fields and do the test
 * $demo->sendAndroidBroadcast();
 * $demo->sendAndroidFilecast();
 * $demo->sendAndroidGroupcast();
 * $demo->sendAndroidCustomizedcast();
 * $demo->sendAndroidCustomizedcastFileId();
 *
 * $demo->sendIOSBroadcast();
 * $demo->sendIOSUnicast();
 * $demo->sendIOSFilecast();
 * $demo->sendIOSGroupcast();
 * $demo->sendIOSCustomizedcast();
 */