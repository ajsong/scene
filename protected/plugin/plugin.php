<?php
class plugin extends kernel {
	public $member_id;
	public $type;
	public $api;
	
	public function __construct() {
		parent::__construct();
		$this->member_id = isset($_SESSION['member']) ? $_SESSION['member']->id : 0;
		$this->setConfigs();
	}
	
	public function setAPI($class, $type) {
		if (!strlen($type)) error_tip('PLUGIN TYPE LOAST');
		if (!defined(strtoupper($class).'_BASE')) {
			define(strtoupper($class).'_BASE', true);
			$file = PLUGIN_PATH . "/{$class}/base.php";
			if (file_exists($file)) {
				require_once($file);
			} else {
				error_tip(strtoupper($class).' BASE LOST');
			}
		}
		if (!defined(strtoupper($type).'_CLASS')) {
			$file = PLUGIN_PATH . "/{$class}/{$type}.php";
			if (file_exists($file)) {
				require_once($file);
				$this->api = new $type();
				$this->api->type = $type;
				define(strtoupper($type).'_CLASS', serialize($this->api));
			} else {
				error_tip(strtoupper($class).' API LOST');
			}
		} else {
			$this->api = unserialize(constant(strtoupper($type).'_CLASS'));
		}
		$this->type = $type;
	}
}
