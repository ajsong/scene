<?php
declare (strict_types = 1);

namespace app\gm\controller;

use think\facade\Db;
use think\facade\View;
use think\Request;

class Blessing extends Core
{
	//index
	public function index() {
		$where = [];
		$sort = ['b.id'=>'DESC'];
		$id = $this->request->get('id');
		$keyword = $this->request->get('keyword');
		$sortby = $this->request->get('sortby');
		if (strlen($id)) {
			$where[] = ['b.id', '=', $id];
		}
		if (strlen($keyword)) {
			$where[] = ['b.title|b.content', 'like', "%{$keyword}%"];
		}
		if ($sortby) {
			$e = explode(',', $sortby);
			$sort = [$e[0] => $e[1]] + $sort;
		}
		$rs = Db::name('blessing')->alias('b')
			->where($where)->order($sort)->paginate(['list_rows'=>10, 'query'=>request()->param()]);
		if ($rs) {
			foreach ($rs as $g) {
			
			}
		}
		
		$last_count = '';
		$logFile = ROOT_PATH . '/temp/blessing.txt';
		if (file_exists($logFile)) {
			$log = file_get_contents($logFile);
			preg_match_all('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})[\r\n]+GET BLESSINGS COMPLETE, QUANTITY (\d+)/', $log, $matcher);
			if ($matcher) {
				$last_count = '最后采集 <strong style="color:#38f;">'.end($matcher[1]).'</strong>　数量 <strong style="color:#38f;">'.end($matcher[2]).'</strong> 个';
			}
		}
		
		View::assign('rs', $rs);
		setViewAssign(compact('id', 'keyword', 'sortby'));
		View::assign('last_count', $last_count);
		View::assign('is_blessing_add', core::check_permission('blessing', 'add'));
		
		$clicks = Db::name('blessing')->sum('clicks');
		$yesterday_clicks = Db::name('blessing')->sum('yesterday_clicks');
		$today_clicks = Db::name('blessing')->sum('today_clicks');
		View::assign('clicks', $clicks);
		View::assign('yesterday_clicks', $yesterday_clicks);
		View::assign('today_clicks', $today_clicks);
		
		return success();
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('blessing')->where('id', $id)->update(compact('status'));
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
			$text_color = $this->request->post('text_color', '#000000');
			$border_color = $this->request->post('border_color', '#000000');
			$pic = $this->request->file('pic', 'blessing', UPLOAD_THIRD);
			$bg_pic = $this->request->file('bg_pic', 'blessing', UPLOAD_THIRD);
			$share_pic = $this->request->file('share_pic', 'blessing', UPLOAD_THIRD);
			$top_avatar_pic = $this->request->file('top_avatar_pic', 'blessing', UPLOAD_THIRD);
			$bottom_avatar_pic = $this->request->file('bottom_avatar_pic', 'blessing', UPLOAD_THIRD);
			//$bg_music = $this->request->file('blessing', 'bg_music', UPLOAD_THIRD, false, ['mp3', 'm4a']);
			$bg_music = $this->request->post('bg_music');
			$music_name = $this->request->post('music_name');
			$music_enable = $this->request->post('music_enable', 0);
			$content = $this->request->post('content', '', '\\');
			$sort = $this->request->post('sort', 0);
			$status = $this->request->post('status', 1);
			$data = compact('title', 'text_color', 'border_color', 'pic', 'bg_pic', 'share_pic', 'top_avatar_pic', 'bottom_avatar_pic', 'bg_music', 'music_name', 'music_enable', 'content', 'sort', 'status');
			if ($id>0) {
				Db::name('blessing')->where('id', $id)->update($data);
			} else {
				$data['add_time'] = time();
				$id = Db::name('blessing')->insert($data);
				$rs = Db::name('miniprogram')->where('type', 2)->order('id', 'ASC')->field('id')->select();
				foreach ($rs as $g) {
					Db::name('blessing_attr')->insert(array('miniprogram_id'=>$g['id'], 'blessing_id'=>$id));
				}
			}
			location("/blessing/edit?id={$id}&msg=1");
		} else if ($id>0) { //显示
			$row = Db::name('blessing')->where('id', $id)->find();
		} else {
			$row = t('blessing');
		}
		
		View::assign('row', $row);
		return success('ok', 'edit.html');
	}
	
	public function upload_pic(){
		$result = $this->request->file('pic', 'blessing', UPLOAD_THIRD);
		return success($result);
	}
	
	//delete
	public function delete() {
		$id = $this->request->get('id', 0);
		Db::name('blessing')->delete($id);
		Db::name('blessing_attr')->where('blessing_id', $id)->delete();
		location('/blessing');
	}
	
	public function multiple_delete() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) return error('请选择');
		Db::name('blessing')->whereIn('id', $ids)->delete();
		Db::name('blessing_attr')->whereIn('blessing_id', $ids)->delete();
		location('/blessing');
	}
}
