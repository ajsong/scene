<?php
class jump extends core {
	private $model;
	private $rootPath;
	
	public function __construct() {
		parent::__construct();
		$this->model = m('cos');
		$this->rootPath = 'art';
	}
	
	public function index() {
		if (IS_POST) {
			$bucket = $this->request->post('bucket');
			$dir = $this->request->post('dir');
			if (!strlen($bucket) || !strlen($dir)) error('请选择储存桶与文件夹');
			$files = $this->request->post('file', []);
			$targets = $this->request->post('target', []);
			$urls = $this->request->post('url', []);
			$marks = $this->request->post('mark', []);
			if (!count($targets)) error('没有任何目标网址');
			$hasUrl = false;
			foreach ($targets as $target) {
				if (strlen($target)) {
					$hasUrl = true;
					break;
				}
			}
			if (!$hasUrl) error('没有任何目标网址');
			$_files = $this->getFile($bucket, $dir);
			$this->model->delete($_files, $bucket);
			for ($i=0; $i<count($targets); $i++) {
				if (!strlen($targets[$i]) || !strlen($urls[$i])) continue;
				$html = $this->_createHtml($targets, $urls, $marks, $i);
				$filename = basename($files[$i]);
				$this->model->putObject($bucket, "{$this->rootPath}/{$dir}/{$filename}", $html);
			}
			historyBack('文件已生成');
		}
		$list = $this->model->listBucket();
		$url = preg_replace('/^.+?-(\d+)\./', "{$list[0]}-$1.", $this->model->url);
		success(array('list'=>$list, 'url'=>$url, 'path'=>$this->rootPath));
	}
	
	public function getBucket() {
		$bucket = $this->request->get('bucket');
		if (!strlen($bucket)) error('缺少参数');
		$list = $this->model->listObject($bucket, "{$this->rootPath}/");
		$dir = array();
		if (is_array($list)) {
			array_shift($list);
			foreach ($list as $key) {
				preg_match('/^'.$this->rootPath.'\/([^\/]+)\//', $key, $matcher);
				if (!in_array($matcher[1], $dir)) $dir[] = $matcher[1];
			}
		}
		if (!count($dir)) $dir = ['default'];
		success($dir);
	}
	
	public function getFile($bucket='', $dir='') {
		if (strlen($bucket) && strlen($dir)) {
			$res = $this->model->listObject($bucket, "{$this->rootPath}/{$dir}/");
			usort($res, function($a, $b) {
				$aname = explode('.', basename($a));
				$bname = explode('.', basename($b));
				return intval(str_replace('g', '', $aname[0]))<intval(str_replace('g', '', $bname[0])) ? -1 : 1;
			});
			$list = array();
			foreach ($res as $file) {
				if (preg_match('/\.html$/', $file)) $list[] = $file;
			}
			return $list;
		}
		$bucket = $this->request->get('bucket');
		$dir = $this->request->get('dir');
		$res = $this->model->listObject($bucket, "{$this->rootPath}/{$dir}/");
		usort($res, function($a, $b) {
			$aname = explode('.', basename($a));
			$bname = explode('.', basename($b));
			return intval(str_replace('g', '', $aname[0]))<intval(str_replace('g', '', $bname[0])) ? -1 : 1;
		});
		$list = array();
		foreach ($res as $file) {
			if (preg_match('/\.html$/', $file)) $list[] = $file;
		}
		$pages = [];
		foreach ($list as $file) {
			$data = requestCurl('get', "https://{$this->model->url}/{$file}");
			$page = new stdClass();
			$page->self = $file;
			preg_match('/let targetDTO = \[{id:1, url:"([^\"]*)"}];/', $data, $target);
			$page->target = $target ? $target[1] : '';
			preg_match('/let urlsDTO = \[{id:1, url:"([^\"]*)"}];/', $data, $url);
			$page->url = $url ? $url[1] : '';
			preg_match('/let markDTO = "([^\"]*)";/', $data, $mark);
			$page->mark = $mark ? $mark[1] : '';
			$pages[] = $page;
		}
		success($pages);
		return true;
	}
	
	private function _createHtml($targets, $urls, $marks, $i) {
		$html = "<script>
let targetDTO = [{id:1, url:\"".(isset($targets[$i])?$targets[$i]:'')."\"}]; //显示页面
let urlsDTO = [{id:1, url:\"".(isset($urls[$i])?$urls[$i]:'')."\"}]; //返回跳转
let markDTO = \"".(isset($marks[$i])?$marks[$i]:'')."\"; //备注
window.addEventListener('pageshow', function (e) {
	// 通过persisted属性判断是否存在 BF Cache
	if (e.persisted) {
		location.reload();
	}
});
let urlDTO, showCount = parseInt(sessionStorage.getItem('showCount') || 0);
sessionStorage.setItem('showCount', (showCount + 1) + '');
history.pushState({}, '1', '#ree');
urlsDTO.unshift({id:0, url:'https://www.baidu.com'});
if (urlsDTO.length > 0 && showCount > 0 && (showCount % urlsDTO.length) !== 0) {
	urlDTO = urlsDTO[showCount % urlsDTO.length];
} else {
	urlDTO = targetDTO[Math.floor((Math.random() * targetDTO.length))];
}
let url = urlDTO.url;
if (url.indexOf('mp.weixin.qq.com') > -1) {
	let id = urlDTO.id;
	let numId = parseInt(sessionStorage.getItem('numId'+id) || 0);
	if (numId === 0) {
		sessionStorage.setItem('numId'+id, (numId + 1) + '');
		location.href = url;
	} else {
		location.href = url + '#'+((new Date()).getTime());
	}
} else if (url.indexOf('function(){') > -1) {
	let fn = eval(url);
	fn();
} else {
	location.href = url;
}
</script>";
		return $html;
	}
	
	public function index2() {
		//$this->smarty->clearCache('jump.index.html');
		$host = str_replace('.', '', $_SERVER['HTTP_HOST']);
		$list = glob(PUBLIC_PATH."/art/{$host}/*.html");
		usort($list, function($a, $b) {
			$aname = explode('.', basename($a));
			$bname = explode('.', basename($b));
			return intval(str_replace('g', '', $aname[0]))<intval(str_replace('g', '', $bname[0])) ? -1 : 1;
		});
		if (IS_POST) {
			$files = $this->request->post('file', []);
			$targets = $this->request->post('target', []);
			$urls = $this->request->post('url', []);
			$marks = $this->request->post('mark', []);
			if (!count($targets)) error('没有任何目标网址');
			$hasUrl = false;
			foreach ($targets as $target) {
				if (strlen($target)) {
					$hasUrl = true;
					break;
				}
			}
			if (!$hasUrl) error('没有任何目标网址');
			delete_folder(PUBLIC_PATH."/art/{$host}");
			makedir(PUBLIC_PATH."/art/{$host}");
			for ($i=0; $i<count($targets); $i++) {
				if (!strlen($targets[$i]) || !strlen($urls[$i])) continue;
				$html = $this->_createHtml($targets, $urls, $marks, $i);
				$filename = basename($files[$i]);
				file_put_contents(PUBLIC_PATH."/art/{$host}/".$filename, $html);
			}
			historyBack('文件已生成');
		}
		$pages = [];
		foreach ($list as $k=>$file) {
			$filename = basename($file);
			$data = file_get_contents($file);
			$page = new stdClass();
			$page->self = https().$_SERVER['HTTP_HOST'].'/art/'.str_replace('.', '', $_SERVER['HTTP_HOST']).'/'.$filename;
			preg_match('/let targetDTO = \[\{id:1, url:"([^\"]*)"\}\];/', $data, $target);
			$page->target = $target ? $target[1] : '';
			preg_match('/let urlsDTO = \[\{id:1, url:"([^\"]*)"\}\];/', $data, $url);
			$page->url = $url ? $url[1] : '';
			preg_match('/let markDTO = "([^\"]*)";/', $data, $mark);
			$page->mark = $mark ? $mark[1] : '';
			$pages[] = $page;
		}
		success($pages);
	}
	
}
