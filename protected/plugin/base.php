<?php
abstract class plugin_base extends kernel {
	public $member_id;
	public $type;
	
	public function __construct() {
		parent::__construct();
		$this->member_id = 0;
		if (isset($_SESSION['member']) && is_object($_SESSION['member']) && isset($_SESSION['member']->id) && intval($_SESSION['member']->id)>0) {
			$this->member_id = $_SESSION['member']->id;
		}
		$this->setConfigs();
	}
}