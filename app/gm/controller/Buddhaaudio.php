<?php
declare (strict_types = 1);

namespace app\gm\controller;

use think\facade\Db;
use think\facade\View;
use think\Request;

class Buddhaaudio extends Core
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
		$rs = Db::name('buddhaaudio')->alias('b')
			->leftJoin('buddhaaudio_category bc', 'b.category_id=bc.id')
			->where($where)->order($sort)->field("b.*, bc.name as category_name")->paginate(['list_rows'=>10, 'query'=>request()->param()]);
		if ($rs) {
			foreach ($rs as $g) {
			
			}
		}
		
		$last_count = '';
		$logFile = ROOT_PATH . '/temp/buddhaaudio.txt';
		if (file_exists($logFile)) {
			$log = file_get_contents($logFile);
			preg_match_all('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})[\r\n]+GET BUDDHAS COMPLETE, QUANTITY (\d+)/', $log, $matcher);
			if ($matcher) {
				$last_count = '最后采集 <strong style="color:#38f;">'.end($matcher[1]).'</strong>　数量 <strong style="color:#38f;">'.end($matcher[2]).'</strong> 个';
			}
		}
		
		$categories = Db::name('buddhaaudio_category')->where('status', 1)->select();
		
		View::assign('rs', $rs);
		setViewAssign(compact('id', 'keyword', 'category_id', 'sortby'));
		View::assign('categories', $categories);
		View::assign('last_count', $last_count);
		View::assign('is_buddhaaudio_add', core::check_permission('buddhaaudio', 'add'));
		
		$clicks = Db::name('buddhaaudio')->sum('clicks');
		$yesterday_clicks = Db::name('buddhaaudio')->sum('yesterday_clicks');
		$today_clicks = Db::name('buddhaaudio')->sum('today_clicks');
		View::assign('clicks', $clicks);
		View::assign('yesterday_clicks', $yesterday_clicks);
		View::assign('today_clicks', $today_clicks);
		
		return success();
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('buddhaaudio')->where('id', $id)->update(compact('status'));
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
			$pic = $this->request->file('pic', 'buddhaaudio', UPLOAD_THIRD);
			$type = $this->request->post('type', 0);
			$url = $this->request->post('url');
			$music = $this->request->file('music', 'buddhaaudio', UPLOAD_THIRD, false, ['mp3', 'm4a']);
			$music_name = $this->request->post('music_name');
			$music_enable = $this->request->post('music_enable', 1);
			$content = $this->request->post('content', '', '\\');
			$sort = $this->request->post('sort', 0);
			$status = $this->request->post('status', 1);
			$data = compact('title', 'category_id', 'pic', 'type', 'url', 'music', 'music_name', 'music_enable', 'content', 'sort', 'status');
			if ($id>0) {
				Db::name('buddhaaudio')->where($id)->update($data);
			} else {
				$data['add_time'] = time();
				$id = Db::name('buddhaaudio')->insert($data);
				$rs = Db::name('miniprogram')->where('type', 4)->order('id', 'ASC')->field('id')->select();
				foreach ($rs as $g) {
					Db::name('buddhaaudio_attr')->insert(array('miniprogram_id'=>$g['id'], 'buddhaaudio_id'=>$id));
				}
			}
			location("/buddhaaudio/edit?id={$id}&msg=1");
		} else if ($id>0) { //显示
			$row = Db::name('buddhaaudio')->where('id', $id)->find();
		} else {
			$row = t('buddhaaudio');
		}
		
		$categories = Db::name('buddhaaudio_category')->where('status', 1)->select();
		
		View::assign('row', $row);
		View::assign('categories', $categories);
		return success('ok', 'edit.html');
	}
	
	public function upload_pic(){
		$result = $this->request->file('pic', 'buddhaaudio', UPLOAD_THIRD);
		return success($result);
	}
	
	//delete
	public function delete() {
		$id = $this->request->get('id', 0);
		Db::name('buddhaaudio')->delete($id);
		Db::name('buddhaaudio_attr')->where('buddhaaudio_id', $id)->delete();
		location('/buddhaaudio');
	}
	
	public function multiple_delete() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) return error('请选择');
		Db::name('buddhaaudio')->whereIn('id', $ids)->delete();
		Db::name('buddhaaudio_attr')->whereIn('buddhaaudio_id', $ids)->delete();
		location('/buddhaaudio');
	}
}
