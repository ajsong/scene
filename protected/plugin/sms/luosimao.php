<?php
//螺丝帽
//https://luosimao.com
class luosimao extends sms_base {
	public function __construct() {
		parent::__construct();
	}
	
	public function send($mobile, $content, $template_id=0, $sign='【铁壳测试】') {
		global $sms_api_key;
		if (!strlen($sms_api_key) && isset($this->configs['GLOBAL_SMS_KEY'])) {
			$global_key = explode('|', $this->configs['GLOBAL_SMS_KEY']);
			$fields = array();
			foreach ($global_key as $g) {
				$s = explode('：', $g);
				$fields[$s[0]] = $s[1];
			}
			extract($fields);
		}
		if (!strlen($sms_api_key)) {
			write_log('SMS LOST API KEY', '/temp/smslog.txt');
			error('SMS LOST API KEY');
			return false;
		}
		if (!$this->maxip()) return false;
		include_once(PLUGIN_PATH . '/sms/luosimao/luosimao.php');
		$sms = new luosimao_api(array('api_key'=>$sms_api_key, 'sign'=>$sign, 'use_ssl'=>false));
		if (is_array($content)) $content = implode('', $content);
		//发送接口，签名需在后台报备
		$res = $sms->send($mobile, $content);
		if ($res) {
			if (!isset($res['error'])) {
				write_log('FAILED, NO ERROR', '/temp/smslog.txt');
				error('FAILED, NO ERROR');
				return false;
			}
			if (intval($res['error'])!=0) {
				write_log('FAILED, CODE:'.$res['error'].', MSG:'.$sms->get_error($res['error']).' ('.$res['msg'].')', '/temp/smslog.txt');
				error('FAILED, CODE:'.$res['error'].', MSG:'.$sms->get_error($res['error']).' ('.$res['msg'].')');
				return false;
			}
		} else {
			write_log('SMS SEND ERROR: '.$sms->last_error(), '/temp/smslog.txt');
			error('SMS SEND ERROR: '.$sms->last_error());
			return false;
		}
		//余额查询
		//$res = $sms->get_deposit();
		//$deposit = floatval($res['deposit']);
		return true;
	}
}
