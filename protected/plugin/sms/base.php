<?php
abstract class sms_base extends plugin_base {
	abstract function send($mobile, $content, $template_id=0, $sign='');
	
	//指定时间限制ip操作, 默认60秒
	public function maxip() {
		global $sms_max_ip_time;
		if (!isset($sms_max_ip_time) || $sms_max_ip_time<=0) $sms_max_ip_time = 12;
		$path = ROOT_PATH.'/temp';
		if (SQL::share()->tableExist('operator')) {
			$path = ROOT_PATH.'/temp';
			makedir($path);
		}
		$filename = $path.'/maxip.txt';
		$ip = ip();
		$content = array();
		if (file_exists($filename)) {
			$data = file_get_contents($filename);
			if (strlen(trim($data))) {
				$content = unserialize(trim($data));
				if (!is_array($content)) $content = array();
				foreach ($content as $i=>$t) {
					if (intval($t)<time()-24*60*60) { //一天后删除
						unset($content[$i]);
					}
				}
				if (isset($content[$ip]) && intval($content[$ip])>time()-$sms_max_ip_time) return false;
			}
		}
		$content["{$ip}"] = time();
		$fp = fopen($filename, 'w');
		flock($fp, LOCK_EX);
		fwrite($fp, serialize($content));
		flock($fp, LOCK_UN);
		fclose($fp);
		return true;
	}
}