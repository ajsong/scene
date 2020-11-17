<?php
class log_model extends base_model {
	public function __construct() {
		parent::__construct();
	}

	//写入日志
	public function create() {
		$member_id = 0;
		$member_name = '';
		if ($this->is_gm) {
			$type = 1;
			if (isset($_SESSION['admin']) && is_object($_SESSION['admin'])) {
				$member_id = $_SESSION['admin']->id;
				$member_name = $_SESSION['admin']->name;
			}
		} else {
			$type = 0;
			if (isset($_SESSION['member']) && is_object($_SESSION['member'])) {
				$member_id = $_SESSION['member']->id;
				$member_name = $_SESSION['member']->name;
			}
		}
		$app = $this->app;
		$act = $this->act;
		$ip = $this->ip;
		$add_time = $this->now;
		$user_agent = (isset($_SERVER['HTTP_USER_AGENT']) && trim($_SERVER['HTTP_USER_AGENT'])) ? substr(trim($_SERVER['HTTP_USER_AGENT']), 0, 300) : '';
		$script_name = (isset($_SERVER['SCRIPT_NAME']) && trim($_SERVER['SCRIPT_NAME'])) ? substr(trim($_SERVER['SCRIPT_NAME']), 0, 50) : '';
		$query_string = (isset($_SERVER['QUERY_STRING']) && trim($_SERVER['QUERY_STRING'])) ? substr(trim($_SERVER['QUERY_STRING']), 0, 125) : '';
		$app_version = $this->request->get('app_version');
		$api_version = $this->request->get('api_version', API_VERSION);
		SQL::share('router_log')->insert(compact('type', 'member_id', 'member_name', 'script_name', 'app', 'act', 'query_string', 'user_agent', 'ip',
			'app_version', 'api_version', 'add_time'));
	}
}
