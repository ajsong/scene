<?php
class base_model extends kernel {
	public $function, $edition;

	public function __construct() {
		parent::__construct();
		//获取系统功能版本
		if (self::$client==NULL) {
			self::$client = SQL::share('client')->row('function, edition');
		}
		$this->edition = intval(self::$client->edition);
		if (strlen(self::$client->function)) $this->function = explode(',', self::$client->function);
		
		$this->setConfigs();
	}
}
