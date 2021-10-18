<?php
declare (strict_types = 1);

namespace app\gm\controller;

use think\facade\Db;
use think\facade\View;
use think\Request;

class Buddha extends Core
{
	//index
	public function index() {
		$where = [];
		$sort = ['b.id'=>'DESC'];
		$id = $this->request->get('id');
		$category_id = $this->request->get('category_id', 0);
		$keyword = $this->request->get('keyword');
		$sortby = $this->request->get('sortby');
		if (strlen($id)) {
			$where[] = ['b.id', '=', $id];
		}
		if (strlen($keyword)) {
			$where[] = ['b.title|b.content', 'like', "%{$keyword}%"];
		}
		if ($category_id) {
			$where[] = ['b.category_id', '=', $category_id];
		}
		if ($sortby) {
			$e = explode(',', $sortby);
			$sort = [$e[0] => $e[1]] + $sort;
		}
		$rs = Db::name('buddha')->alias('b')
			->leftJoin('buddha_category bc', 'b.category_id=bc.id')
			->where($where)->order($sort)->field("b.*, bc.name as category_name")->paginate(['list_rows'=>10, 'query'=>request()->param()]);
		if ($rs) {
			foreach ($rs as $g) {
			
			}
		}
		
		$last_count = '';
		$logFile = ROOT_PATH . '/temp/buddha.txt';
		if (file_exists($logFile)) {
			$log = file_get_contents($logFile);
			preg_match_all('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})[\r\n]+GET BUDDHAS COMPLETE, QUANTITY (\d+)/', $log, $matcher);
			if ($matcher) {
				$last_count = '最后采集 <strong style="color:#38f;">'.end($matcher[1]).'</strong>　数量 <strong style="color:#38f;">'.end($matcher[2]).'</strong> 个';
			}
		}
		
		$categories = Db::name('buddha_category')->where('status', 1)->order('sort', 'ASC')->select();
		
		View::assign('rs', $rs);
		setViewAssign(compact('id', 'keyword', 'category_id', 'sortby'));
		View::assign('categories', $categories);
		View::assign('last_count', $last_count);
		View::assign('is_buddha_add', core::check_permission('buddha', 'add'));
		
		$clicks = Db::name('buddha')->sum('clicks');
		$yesterday_clicks = Db::name('buddha')->sum('yesterday_clicks');
		$today_clicks = Db::name('buddha')->sum('today_clicks');
		View::assign('clicks', $clicks);
		View::assign('yesterday_clicks', $yesterday_clicks);
		View::assign('today_clicks', $today_clicks);
		
		return success();
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('buddha')->where('id', $id)->update(compact('status'));
		return success('ok');
	}
	
	public function add() {
		return $this->edit();
	}
	public function edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) { //添加
			$id = $this->request->post('id', 0);
			$title = $this->request->post('title');
			$category_id = $this->request->post('category_id', 0);
			$pic = $this->request->file('pic', 'buddha', UPLOAD_THIRD);
			//$music = $this->request->file('buddha', 'music', UPLOAD_THIRD, false, ['mp3', 'm4a']);
			$type = $this->request->post('type', 0);
			$url = $this->request->post('url');
			$music = $this->request->post('music');
			$music_name = $this->request->post('music_name');
			$music_enable = $this->request->post('music_enable', 0);
			$content = $this->request->post('content', '', '\\');
			$sort = $this->request->post('sort', 0);
			$status = $this->request->post('status', 1);
			$vid = $this->request->post('vid');
			$tencentvideo = $this->request->post('tencentvideo', 0);
			$tencent_url = strlen($vid) ? $this->_getTencentUrl($vid) : '';
			$data = compact('title', 'category_id', 'pic', 'type', 'url', 'music', 'music_name', 'music_enable', 'content', 'sort', 'status', 'vid', 'tencentvideo', 'tencent_url');
			if ($id>0) {
				Db::name('buddha')->where('id', $id)->update($data);
				location("?app=buddha&act=edit&id={$id}&msg=1");
			} else {
				$data['add_time'] = time();
				$id = Db::name('buddha')->insert($data);
				$rs = Db::name('miniprogram')->where('type', 3)->order('id', 'ASC')->field('id')->select();
				foreach ($rs as $g) {
					Db::name('buddha_attr')->insert(array('miniprogram_id'=>$g['id'], 'buddha_id'=>$id));
				}
				location("/buddha/edit?msg=1");
			}
		} else if ($id>0) { //显示
			$row = Db::name('buddha')->where('id', $id)->find();
		} else {
			$row = t('buddha');
		}
		
		$categories = Db::name('buddha_category')->where('status', 1)->order('sort', 'ASC')->select();
		
		View::assign('row', $row);
		View::assign('categories', $categories);
		return success('ok', 'edit.html');
	}
	
	public function getTencentVideo() {
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$url = 'https://mp.weixin.qq.com/s?__biz=MzI2OTc4ODUwMg==&mid=2247487561&idx=1&sn=a0b604d2f0137abade7fb552ccaa7e10&chksm=eadba470ddac2d665be8fe90ac1615bd60c3f2e6a6b53a91cf8acda3e446d935a095b422700d&mpshare=1&scene=1&srcid=0514FxUYRJGQU3xMSaT96uvL&sharer_sharetime=1620983411055&sharer_shareid=27e6efba143cff9a72e2a8c3360f1563#rd';
		$res = requestCurl('get', $url);
		$fn = function($res, $level) use (&$fn) {
			preg_match_all('/<div class="rich_media_content " id="js_content" style="visibility: hidden;">[\s\S]+<pre[\s\S]+<\/pre>(<p .+?<\/p>)?<table width="([\s\S]+)<\/table>/', $res, $matcher);
			if (!count($matcher[2])) exit('HTML ERROR');
			preg_match_all('/<td([\s\S]+?)<\/td>/', $matcher[2][0], $matcher);
			if (!count($matcher[1])) exit('CODE ERROR');
			$result = [];
			$items = [];
			foreach ($matcher[1] as $match) {
				$item = [];
				preg_match_all('/<a.+?href="([^"]+)"/', $match, $m);
				if (!count($m[1])) continue;
				$item['url'] = $m[1][0];
				preg_match_all('/<img.+?data-src="([^"]+)"/', $match, $m);
				if (!count($m[1])) continue;
				$item['image'] = $m[1][0];
				$items[] = $item;
			}
			foreach ($items as $item) {
				$res = requestCurl('get', $item['url']);
				preg_match_all('/<h2 class="rich_media_title" id="activity-name">([\s\S]+?)<\/h2>/', $res, $matcher);
				if (!count($matcher[1])) continue;
				$title = trim($matcher[1][0]);
				preg_match_all('/data-src="https:\/\/v\.qq\.com\/iframe\/preview\.html.+?vid=(\w{11})"/', $res, $matcher);
				if (count($matcher[1])) {
					foreach ($matcher[1] as $vid) {
						$url = $this->_getTencentUrl($vid);
						if (!strlen($url)) continue;
						$it = [];
						$it['title'] = $title;
						$it['image'] = $item['image'];
						$it['vid'] = $vid;
						$it['video'] = $url;
						$result[] = $it;
						break;
					}
				} else if ($level == 0) {
					preg_match_all('/<div class="rich_media_content " id="js_content" style="visibility: hidden;">[\s\S]+?<pre[\s\S]+?<\/pre>(<p .+?<\/p>)?<table width="([\s\S]+)<\/table>/', $res, $matcher);
					if (count($matcher[2])) {
						$list = $fn($res, $level + 1);
						if (count($list)) {
							$it = [];
							$it['title'] = $title;
							$it['image'] = $item['image'];
							$it['list'] = $list;
							$result[] = $it;
						}
					}
				}
			}
			return $result;
		};
		$result = $fn($res, 0);
		$create = function($result, $main='', $main_pic='') use (&$create) {
			foreach ($result as $res) {
				if (isset($res['list'])) {
					$create($res['list'], $res['title'], $this->_getFile($res['image']));
				} else {
					$title = $res['title'];
					$pic = $this->_getFile($res['image']);
					$vid = $res['vid'];
					$tencent_url = $res['video'];
					$tencentvideo = 1;
					$type = 5;
					$category_id = 5;
					$add_time = time();
					$special = 1;
					$data = compact('title', 'category_id', 'pic', 'type', 'vid', 'tencentvideo', 'tencent_url', 'add_time', 'main', 'main_pic', 'special');
					$id = Db::name('buddha')->insert($data);
					$rs = Db::name('miniprogram')->where('type', 3)->order('id', 'ASC')->field('id')->select();
					foreach ($rs as $g) {
						Db::name('buddha_attr')->insert(array('miniprogram_id'=>$g['id'], 'buddha_id'=>$id));
					}
				}
			}
		};
		$create($result);
		exit('SUCCESS, COUNT: ' . count($result));
	}
	
	private function _getTencentUrl($vid) {
		$res = requestCurl('get', "http://vv.video.qq.com/getinfo?vids={$vid}&platform=101001&charge=0&otype=json");
		$content = str_replace(['QZOutputJson=', ';'], '', $res);
		$data = json_decode($content, true);
		if (!isset($data['vl'])) return '';
		$fn = $data['vl']['vi'][0]['fn'];
		$fvkey = $data['vl']['vi'][0]['fvkey'];
		$url = $data['vl']['vi'][0]['ul']['ui'][0]['url'];
		return "{$url}{$fn}?vkey={$fvkey}";
	}
	
	private function _getFile($url) {
		if (!strlen($url)) return '';
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$ch = curl_init();
		$suffix = '';
		if (stripos($url, 'image/svg+xml') !== false || stripos($url, 'wx_fmt=svg') !== false) return $url;
		if (strpos($url, 'wx_fmt=')!==false) $suffix = substr($url, strrpos($url, 'wx_fmt=')+7);
		if (!$suffix) $suffix = substr($url, strrpos($url, '.')+1);
		if (!preg_match('/^(jpe?g|png|gif|bmp)$/', $suffix)) $suffix = 'jpg';
		if ($suffix=='jpeg') $suffix = 'jpg';
		$timeout = 60*60;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		if (substr($url, 0, 8)=='https://') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		$content = curl_exec($ch);
		$header_info = curl_getinfo($ch);
		if (intval($header_info['http_code']) != 200) return $url;
		curl_close($ch);
		$file = upload_obj_file($content, 'buddha', NULL, UPLOAD_THIRD, false, ['jpg', 'jpeg', 'png', 'gif', 'bmp'], ".{$suffix}");
		$file = add_domain($file);
		return $file;
	}
	
	public function upload_pic(){
		$result = $this->request->file('pic', 'buddha', UPLOAD_THIRD);
		return success($result);
	}
	
	public function delete() {
		$id = $this->request->get('id', 0);
		Db::name('buddha')->delete($id);
		Db::name('buddha_attr')->where('buddha_id', $id)->delete();
		location('/buddha');
	}
	
	public function multiple_delete() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) return error('请选择');
		Db::name('buddha')->whereIn('id', $ids)->delete();
		Db::name('buddha_attr')->whereIn('buddha_id', $ids)->delete();
		location('/buddha');
	}
}
