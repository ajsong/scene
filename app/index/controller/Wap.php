<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\index\controller\Core;
use think\facade\Db;
use think\facade\View;
use think\Request;
use wechatCallbackAPI\wechatCallbackAPI;

class Wap extends Core
{
	public function index() {
		exit($_SERVER['HTTP_USER_AGENT']);
	}
	
	public function detail() {
		$code = $this->request->param('code');
		//$from = $this->request->get('from');
		//$isappinstalled = $this->request->get('isappinstalled');
		if (!strlen($code)) error_tip('缺少参数');
		$row = Db::name('member_scene')->whereRaw("code='{$code}' AND status=1 AND is_publish=1")
			->field('id, title, memo, cover, music, music_play, code, suffix, share_url, return_url, fenliu_url')->find();
		if (!$row) error_tip('该场景不存在');
		if (strlen($row['fenliu_url'])) location($row['fenliu_url']);
		unset($row['fenliu_url']);
		Db::name('member_scene')->where('id', $row['id'])->update(array('click'=>array('+1')));
		$row['cover'] = $this->_changeImgDomain($row['cover']);
		$row['music'] = $this->_changeImgDomain($row['music']);
		$pages = Db::name('member_scene_page')->where(['scene_id'=>$row['id'], 'status'=>1])->order(['sort', 'id'=>'asc'])->field('bg, content')->select();
		if ($pages) {
			foreach ($pages as $page) {
				$page['bg'] = $this->_changeImgDomain($page['bg']);
				$page['content'] = $this->_changeImgDomain(base64_decode($page['content']));
			}
			if ($row['suffix']==1) {
				$page = Db::name('member_scene_page')->where('id', 1)->field('bg, content')->find();
				$page['bg'] = $this->_changeImgDomain($page['bg']);
				$page['content'] = $this->_changeImgDomain(base64_decode($page['content']));
				$pages[] = $page;
			}
		}
		$row['pages'] = $pages;
		
		if (defined('WX_APPID') && strlen(WX_APPID)) {
			$jssdk = new wechatCallbackAPI();
			$row['jssdk'] = $jssdk->getSignPackage();
			$row['jssdk']['share'] = array(
				'title'=>$row['title'],
				'desc'=>$row['memo'],
				'link'=>"{$this->domain}/v/".$row['code'],
				'img'=>$row['cover']
			);
		} else {
			$row['jssdk'] = NULL;
		}
		
		View::assign('WEB_TITLE', $row['title']);
		View::assign('WEB_DESCRIPTION', $row['memo']);
		View::assign('WEB_KEYWORDS', $row['title'].(defined('WEB_NAME')?','.WEB_NAME:''));
		View::assign('cache_control', '86400');
		View::assign('cache_expires', gmdate('l d F Y H:i:s', strtotime('+1 day')).' GMT');
		
		return success($row);
	}
	private function _changeImgDomain($url) {
		return str_replace('http://', 'https://', $url);
	}
	
	public function update() {
		if (!IS_POST) error();
		$code = $this->request->post('code');
		if (!strlen($code)) error_tip('缺少参数');
		$row = Db::name('member_scene')->whereRaw("code='{$code}' AND status=1 AND is_publish=1")->field('id')->find();
		if (!$row) error_tip('该场景不存在');
		Db::name('member_scene')->where('id', $row['id'])->inc('share')->update();
		return success('ok');
	}
	
	//点击
	public function click() {
		if (!IS_POST) error();
		$id = $this->request->post('id', 0);
		if ($id<=0) error('该场景不存在');
		Db::name('member_scene')->where('id', $id)->inc('click')->update();
		return success('ok');
	}
}
