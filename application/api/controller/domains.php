<?php
class domains extends core {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function index() {
		success('ok');
	}
	
	public function setDomains() {
		$root = $this->request->post('root');
		$domains = $this->request->post('domains');
		if (!strlen($root) || !strlen($domains)) error('缺少网站名与域名列表');
		/*
		$list = glob(ROOT_PATH.'/temp/domains/*.txt');
		usort($list, function($a, $b) {
			$aname = explode('.', basename($a));
			$bname = explode('.', basename($b));
			return intval($aname[0])<intval($bname[0]) ? -1 : 1;
		});
		$path = $list[count($list) - 1];
		$names = explode('.', basename($path));
		$domains = file_get_contents($path);
		$root = $names[1];
		*/
		$suffix = '';
		if ($root == 'scene') $suffix = '.scene';
		$normal = file_get_contents(ROOT_PATH."/temp/domains/origin/normal{$suffix}.conf");
		$star = file_get_contents(ROOT_PATH."/temp/domains/origin/star{$suffix}.conf");
		$domains = preg_split("/[\r\n]+/", $domains);
		$count = 0;
		foreach ($domains as $domain) {
			$domain = trim($domain);
			$ds = explode('.', $domain);
			$isStar = false;
			if ($ds[0] == '*') $isStar = true;
			unset($ds[0]);
			if ($isStar) {
				$ds = implode('.', $ds);
				$conf = str_replace('h5.huanlegou668.com', $domain, $star);
				$conf = str_replace('huanlegou668.com', $ds, $conf);
			} else {
				$conf = str_replace('h5.huanlegou668.com', $domain, $normal);
			}
			$conf = str_replace('/dingdan', "/{$root}", $conf);
			file_put_contents(ROOT_PATH."/temp/domains/conf/{$domain}.conf", $conf);
			$count++;
		}
		if ($count) {
			$list = glob(ROOT_PATH.'/temp/domains/*.txt');
			file_put_contents(ROOT_PATH."/temp/domains/".(count($list)+1).".{$root}.txt", implode("\n", $domains));
			$list = glob(ROOT_PATH.'/temp/domains/conf/*.conf');
			foreach ($list as $l) {
				$basename = basename($l);
				$path = "/www/server/panel/vhost/nginx/{$basename}";
				if (file_exists($path)) unlink($path);
				copy($l, $path);
				unlink($l);
			}
		}
		//write_log("{$count} .CONF FILES ARE CREATE COMPLETE~", '/temp/domains/tool/created.log');
		success("{$count} .CONF FILES ARE CREATE COMPLETE~");
	}
	
}