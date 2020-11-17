<?php
//阿里云
//https://help.aliyun.com/document_detail/55359.html?spm=5176.8195934.507901.12.b1ngGK
class aliyun extends sms_base {
	public function __construct() {
		parent::__construct();
	}
	
	public function send($mobile, $content, $template_id=0, $sign='短信签名') {
		global $AccessKeyID, $AccessKeySecret;
		if ((!strlen($AccessKeyID) || !strlen($AccessKeySecret)) && isset($this->configs['GLOBAL_SMS_KEY'])) {
			$global_key = explode('|', $this->configs['GLOBAL_SMS_KEY']);
			$fields = array();
			foreach ($global_key as $g) {
				$s = explode('：', $g);
				$fields[$s[0]] = $s[1];
			}
			extract($fields);
		}
		if (!strlen($AccessKeyID) || !strlen($AccessKeySecret)) {
			write_log('SMS LOST API KEY', '/temp/smslog.txt');
			error('SMS LOST API KEY');
			return false;
		}
		if (!$this->maxip()) return false;
		include_once(PLUGIN_PATH . '/sms/AliyunDysms/sendSms.php');
		if (!is_array($content)) $content = array('code'=>$content);
		$res = sendSms($AccessKeyID, $AccessKeySecret, $mobile, $sign, $template_id, $content);
		//write_log(print_r($res, true), '/temp/smslog.txt');
		if ($res) {
			if (!isset($res['Code'])) {
				write_log('FAILED, NO ERROR', '/temp/smslog.txt');
				error('FAILED, NO ERROR');
				return false;
			}
			if ($res['Code']!='OK') {
				write_log('FAILED, CODE:'.$res['Code'].', MSG:'.$res['Message'], '/temp/smslog.txt');
				error('FAILED, CODE:'.$res['Code'].', MSG:'.$res['Message']);
				return false;
			}
		} else {
			write_log('SMS SEND ERROR', '/temp/smslog.txt');
			error('SMS SEND ERROR');
			return false;
		}
		return true;
	}
}
