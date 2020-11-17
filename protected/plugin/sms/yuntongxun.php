<?php
//容联云通讯
//https://www.yuntongxun.com
class yuntongxun extends sms_base {
	public function __construct() {
		parent::__construct();
	}
	
	public function send($mobile, $content, $template_id=0, $sign='') {
		global $sms_accountSid, $sms_accountToken, $sms_appId;
		if (!strlen($sms_accountSid) || !strlen($sms_accountToken) || !strlen($sms_appId)) {
			write_log('SMS LOST API KEY', '/temp/smslog.txt');
			error('SMS LOST API KEY');
			return false;
		}
		if (!$this->maxip()) return false;
		include_once(PLUGIN_PATH . '/sms/yuntongxun/CCPRestSmsSDK.php');
		//沙盒环境: sandboxapp.cloopen.com
		//生产环境: app.cloopen.com
		$serverIP = 'sandboxapp.cloopen.com';
		$serverPort = '8883'; //请求端口，生产环境和沙盒环境一致
		$softVersion = '2013-12-26'; //REST版本号，在官网文档REST介绍中获得
		$rest = new REST($serverIP, $serverPort, $softVersion);
		$rest->setAccount($sms_accountSid, $sms_accountToken);
		$rest->setAppId($sms_appId);
		$result = NULL;
		//发送模板短信
		if (is_array($content)) {
			$result = $rest->sendTemplateSMS($mobile, $content, $template_id);
		} else {
			if (strlen($content)) {
				$result = $rest->sendTemplateSMS($mobile, array($content), $template_id);
			}
		}
		if ($result == NULL ) {
			return false;
			//echo "result error!";
			//break;
		}
		if ($result->statusCode!=0) {
			return false;
			//echo "error code :" . $result->statusCode . "<br>";
			//echo "error msg :" . $result->statusMsg . "<br>";
			//TODO 添加错误处理逻辑
		} else {
			return true;
			//echo "Sendind TemplateSMS success!<br/>";
			// 获取返回信息
			//$smsmessage = $result->TemplateSMS;
			//echo "dateCreated:".$smsmessage->dateCreated."<br/>";
			//echo "smsMessageSid:".$smsmessage->smsMessageSid."<br/>";
		}
	}
}
