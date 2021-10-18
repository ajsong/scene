<?php
declare (strict_types = 1);

namespace app\gm\controller;

use think\facade\Db;
use think\facade\View;
use think\Request;

class Video extends Core
{
	//index
	public function index() {
		$where = [];
		$sort = ['v.id'=>'DESC'];
		$id = $this->request->get('id');
		$keyword = $this->request->get('keyword');
		$tencentvideo = $this->request->get('tencentvideo');
		$category_id = $this->request->get('category_id', 0);
		$sortby = $this->request->get('sortby');
		if (strlen($id)) {
			$where[] = ['v.id', '=', $id];
		}
		if (strlen($keyword)) {
			$where[] = ['v.title', 'like', "%{$keyword}%"];
		}
		if (strlen($tencentvideo)) {
			$where[] = ['v.tencentvideo', '=', $tencentvideo];
		}
		if ($category_id>0) {
			$where[] = ['v.category_id', '=', $category_id];
		}
		if ($sortby) {
			$e = explode(',', $sortby);
			$sort = ["v.{$e[0]}" => $e[1]] + $sort;
		}
		$rs = Db::name('video')->alias('v')
			->leftJoin('video_category vc', 'v.category_id=vc.id')
			->where($where)->order($sort)->field("v.*, vc.name as category_name")->paginate(['list_rows'=>10, 'query'=>request()->param()]);
		
		$categories = Db::name('video_category')->where('id', '>', 1)->order(['sort', 'id'=>'asc'])->select();
		
		$last_count = '';
		$logFile = ROOT_PATH . '/temp/video.txt';
		if (file_exists($logFile)) {
			$log = file_get_contents($logFile);
			preg_match_all('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})[\r\n]+GET VIDEOS COMPLETE, QUANTITY (\d+)/', $log, $matcher);
			if ($matcher) {
				$last_count = '最后采集 <strong style="color:#38f;">'.end($matcher[1]).'</strong>　数量 <strong style="color:#38f;">'.end($matcher[2]).'</strong> 个';
			}
		}
		
		View::assign('rs', $rs);
		setViewAssign(compact('id', 'keyword', 'tencentvideo', 'category_id', 'sortby'));
		View::assign('categories', $categories);
		View::assign('is_video_add', core::check_permission('video', 'add'));
		View::assign('last_count', $last_count);
		
		$clicks = Db::name('video')->sum('click');
		$yesterday_clicks = Db::name('video')->sum('yesterday_clicks');
		$today_clicks = Db::name('video')->sum('today_clicks');
		View::assign('clicks', $clicks);
		View::assign('yesterday_clicks', $yesterday_clicks);
		View::assign('today_clicks', $today_clicks);
		
		return success();
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('video')->where('id', $id)->update(compact('status'));
		return success('ok');
	}
	
	public function add() {
		return $this->edit();
	}
	public function edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) { //添加
			$id = $this->request->post('id', 0);
			$category_id = $this->request->post('category_id', 0);
			$title = $this->request->post('title');
			$status = $this->request->post('status', 1);
			$tencentvideo = $this->request->post('tencentvideo', 0);
			$vid = $this->request->post('vid');
			$time = $this->request->post('time', 0);
			$img = $this->request->file('img', 'video', UPLOAD_THIRD);
			$url = $this->request->post('url');
			$data = compact('title', 'img', 'url', 'category_id', 'vid', 'tencentvideo', 'status', 'time');
			if ($id>0) {
				Db::name('video')->where('id', $id)->update($data);
			} else {
				//$data['played'] = random_num(50, 300) * 100;
				//$data['likes'] = random_num(50, 300);
				$data['add_time'] = time();
				$id = Db::name('video')->insert($data);
				$rs = Db::name('miniprogram')->where('type', 1)->order('id', 'ASC')->field('id')->select();
				foreach ($rs as $g) {
					Db::name('video_attr')->insert(array('miniprogram_id'=>$g['id'], 'video_id'=>$id));
				}
				
				/*$article_id = Db::name('article')->insert(array('title'=>$title, 'category_id'=>6, 'pic'=>$img, 'url'=>$url, 'type'=>5, 'status'=>1, 'sort'=>999, 'add_time'=>time()));
				$rs = Db::name('miniprogram')->where("type='0'")->order('id ASC')->find('id');
				foreach ($rs as $g) {
					Db::name('article_attr')->insert(array('miniprogram_id'=>$g->id, 'article_id'=>$article_id));
				}*/
			}
			location("/video/edit?id={$id}&msg=1");
		} else if ($id>0) { //显示
			$row = Db::name('video')->where('id', $id)->find();
		} else {
			$row = t('video');
		}
		View::assign('row', $row);
		$categories = Db::name('video_category')->where('id', '>', 1)->order(['sort', 'id'=>'asc'])->select();
		View::assign('categories', $categories);
		return success('ok', 'edit.html');
	}
	
	public function upload_pic() {
		$result = $this->request->file('pic', 'video', UPLOAD_THIRD);
		return success($result);
	}
	
	public function upload_video() {
		$result = $this->request->file('filename', 'video', 2, false, ['mp4']);
		return success($result);
	}
	
	//delete
	public function delete() {
		$id = $this->request->get('id', 0);
		$row = Db::name('video')->where('id', $id)->field('img, url')->find();
		if ($row) {
			if (preg_match('/^\/uploads\//', $row->img)) unlink(PUBLIC_PATH.$row->img);
			if (preg_match('/^\/uploads\//', $row->url)) unlink(PUBLIC_PATH.$row->url);
		}
		Db::name('video')->delete($id);
		Db::name('video_attr')->where('video_id', $id)->delete();
		location('/video');
	}
	
	public function multiple_delete() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) return error('请选择');
		$_ids = explode(',', $ids);
		foreach ($_ids as $id) {
			$row = Db::name('video')->where('id', $id)->field('img, url')->find();
			if ($row) {
				if (preg_match('/^\/uploads\//', $row->img)) unlink(PUBLIC_PATH.$row->img);
				if (preg_match('/^\/uploads\//', $row->url)) unlink(PUBLIC_PATH.$row->url);
			}
		}
		Db::name('video')->whereIn('id', $ids)->delete();
		Db::name('video_attr')->whereIn('video_id', $ids)->delete();
		location('/video');
	}
}
