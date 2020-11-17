<?php
class wap extends core {

	public function __construct() {
		parent::__construct();
		
	}
	
	public function index() {
		exit($_SERVER['HTTP_USER_AGENT']);
	}
	
	public function detail() {
		$code = $this->request->get('code');
		//$from = $this->request->get('from');
		//$isappinstalled = $this->request->get('isappinstalled');
		if (!strlen($code)) error_tip('缺少参数');
		$row = SQL::share('member_scene')->where("code='{$code}' AND status=1 AND is_publish=1")
			->row('id, title, memo, cover, music, music_play, code, suffix, share_url, return_url, fenliu_url');
		if (!$row) error_tip('该场景不存在');
		if (strlen($row->fenliu_url)) location($row->fenliu_url);
		unset($row->fenliu_url);
		//SQL::share('member_scene')->where($row->id)->update(array('click'=>array('+1')));
		$row->cover = $this->_changeImgDomain($row->cover);
		$row->music = $this->_changeImgDomain($row->music);
		$pages = SQL::share('member_scene_page')->where("scene_id='{$row->id}' AND status=1")->sort('sort ASC, id ASC')->find('bg, content');
		if ($pages) {
			foreach ($pages as $page) {
				$page->bg = $this->_changeImgDomain($page->bg);
				$page->content = $this->_changeImgDomain(base64_decode($page->content));
			}
			if ($row->suffix==1) {
				$page = SQL::share('member_scene_page')->where("id=1")->row('bg, content');
				$page->bg = $this->_changeImgDomain($page->bg);
				$page->content = $this->_changeImgDomain(base64_decode($page->content));
				$pages[] = $page;
			}
		}
		$row->pages = $pages;
		
		if (defined('WX_APPID') && strlen(WX_APPID)) {
			$jssdk = new wechatCallbackAPI();
			$row->jssdk = $jssdk->getSignPackage();
			$row->jssdk['share'] = array(
				'title'=>$row->title,
				'desc'=>$row->memo,
				'link'=>"{$this->domain}/v/{$row->code}",
				'img'=>$row->cover
			);
		} else {
			$row->jssdk = NULL;
		}
		
		$this->smarty->assign('WEB_TITLE', $row->title);
		$this->smarty->assign('WEB_DESCRIPTION', $row->memo);
		$this->smarty->assign('WEB_KEYWORDS', $row->title.(defined('WEB_NAME')?','.WEB_NAME:''));
		$this->smarty->assign('cache_control', '86400');
		$this->smarty->assign('cache_expires', gmdate('l d F Y H:i:s', strtotime('+1 day')).' GMT');
		
		success($row);
	}
	private function _changeImgDomain($url) {
		return str_replace('http://', 'https://', $url);
	}
	
	public function update() {
		if (!IS_POST) error('DATA ERROR');
		$code = $this->request->post('code');
		if (!strlen($code)) error_tip('缺少参数');
		$row = SQL::share('member_scene')->where("code='{$code}' AND status=1 AND is_publish=1")->row('id');
		if (!$row) error_tip('该场景不存在');
		SQL::share('member_scene')->where($row->id)->update(array('share'=>array('+1')));
		success('ok');
	}
	
	//点击
	public function click() {
		if (!IS_POST) error('DATA ERROR');
		$id = $this->request->post('id', 0);
		if ($id<=0) error('该场景不存在');
		SQL::share('member_scene')->where($id)->update(array('click'=>array('+1')));
		success('ok');
	}
}
