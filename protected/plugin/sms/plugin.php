<?php
class sms extends plugin {
	public function __construct($type='') {
		parent::__construct();
		if (!strlen($type)) $type = 'luosimao';
		$this->setAPI(__CLASS__, $type);
	}
	
	public function send($mobile, $content, $template_id=0, $sign=''){
		$result = $this->api->send($mobile, $content, $template_id, $sign);
		return $result;
	}
}
