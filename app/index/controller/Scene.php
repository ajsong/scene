<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\index\controller\Core;
use think\facade\Db;
use think\facade\View;
use think\Request;


class Scene extends Core
{
	
	public function copy() {
		$id = $this->request->request('id', 0);
		if (Db::name('member_scene')->where('id', $id)->count()==0) return error('该场景不存在');
		if (IS_POST) {
			$code = strtoupper(random_str(11));
			$time = time();
			$fields = 'member_id, title, memo, cover, music, music_name, music_pic, music_position, music_play, share_url, return_url, fenliu_url, grid, ruler, pad, suffix';
			$data = Db::name('member_scene')->where('id', $id)->field("{$fields}, '{$code}' as code, '{$time}' as edit_time, '{$time}' as add_time")->find()->toArray();
			$scene_id = Db::name('member_scene')->insertGetId($data);
			$data = Db::name('member_scene_page')->where('scene_id', $id)->order('id')->field("title, bg, content, sort, '{$scene_id}' as scene_id")->select();
			Db::name('member_scene_page')->insert($data);
			$row = Db::name('member_scene')->where('id', $scene_id)->find();
			$row['url'] = "{$this->domain}/v/{$code}";
			return success($row);
		}
		return success('ok');
	}
	
	public function gift() {
		$id = $this->request->request('id', 0);
		if (Db::name('member_scene')->where('member_id', $this->member_id)->where('id', $id)->count()==0) return error('该场景不存在');
		if (IS_POST) {
			$username = $this->request->post('username');
			if (!strlen($username)) return error('请填写受赠方的登录账号');
			$member = Db::name('member')->where('name', $username)->field('id')->find();
			if (!$member) return error('该受赠方不存在');
			if ($member['id']==$this->member_id) return error('不应该赠送给自己');
			Db::name('member_scene')->where('member_id', $this->member_id)->where('id', $id)->update(array('member_id'=>$member->id));
		}
		return success('ok');
	}
	
	public function delete() {
		$id = $this->request->request('id', 0);
		if (Db::name('member_scene')->where('member_id', $this->member_id)->where('id', $id)->count()==0) return error('该场景不存在');
		if (IS_POST) {
			Db::name('member_scene')->where('member_id', $this->member_id)->where('id', $id)->delete();
			Db::name('member_scene_page')->where('scene_id', $id)->delete();
		}
		return success('ok');
	}
	
	public function detail() {
		$id = $this->request->get('id', 0);
		if ($id<=0) return error('数据错误');
		$row = Db::name('member_scene')->where('member_id', $this->member_id)->where('id', $id)->find();
		if ($row) {
			$row['url'] = "{$this->domain}/v/".$row['code'];
		}
		return success($row);
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) return error('数据错误');
		Db::name('member_scene')->where('member_id', $this->member_id)->where('id', $id)->update(compact('status'));
		return success('ok');
	}
	
	public function publish() {
		$id = $this->request->request('id', 0);
		if ($id<=0) return error('数据错误');
		Db::name('member_scene')->where('member_id', $this->member_id)->where('id', $id)->update(array('is_publish'=>1));
		if (IS_POST) return success('ok');
		location("/scene/detail?id={$id}");
	}
	
	public function add() {
		return $this->edit();
	}
	public function edit() {
		$id = $this->request->param('id', 0);
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$title = $this->request->post('title');
			$memo = $this->request->post('memo');
			$cover = $this->request->post('cover');
			$music = $this->request->post('music');
			$music_name = $this->request->post('music_name');
			$music_pic = $this->request->post('music_pic');
			$music_position = $this->request->post('music_position', 0);
			$music_play = $this->request->post('music_play', 0);
			$share_url = $this->request->post('share_url');
			$return_url = $this->request->post('return_url');
			$fenliu_url = $this->request->post('fenliu_url');
			$grid = $this->request->post('grid', '1|60|#dddddd');
			$ruler = $this->request->post('ruler', 1);
			$pad = $this->request->post('pad');
			$suffix = $this->request->post('suffix', 1);
			$status = $this->request->post('status', 1);
			$pages = $this->request->post('pages', array());
			$member_id = $this->member_id;
			$edit_time = time();
			if (!strlen($cover)) return error('请选择封面图片');
			if (!strlen($title)) return error('场景标题不能为空');
			if (!is_array($pages) || !count($pages)) return error('缺少场景页');
			$data = compact('member_id', 'title', 'memo', 'cover', 'music', 'music_name', 'music_pic', 'music_position', 'music_play',
				'share_url', 'return_url', 'fenliu_url', 'grid', 'ruler', 'pad', 'suffix', 'status', 'edit_time');
			if ($id>0) {
				if (Db::name('member_scene')->where('member_id', $member_id)->where('id', $id)->count()==0) return error('该场景不存在');
				Db::name('member_scene')->where('member_id', $member_id)->where('id', $id)->update($data);
			} else {
				$data['code'] = strtoupper(random_str(11));
				$data['add_time'] = time();
				$id = Db::name('member_scene')->insert($data);
			}
			Db::name('member_scene_page')->where('scene_id', $id)->delete();
			$data = array();
			foreach ($pages as $page) {
				$scene_id = $id;
				$title = isset($page['title']) ? trim($page['title']) : '';
				$bg = isset($page['bg']) ? trim($page['bg']) : '';
				$content = isset($page['content']) ? base64_encode(stripslashes(trim($page['content']))) : '';
				$status = isset($page['status']) ? intval($page['status']) : 0;
				$sort = isset($page['sort']) ? intval($page['sort']) : 0;
				$data[] = compact('scene_id', 'title', 'bg', 'content', 'status', 'sort');
			}
			Db::name('member_scene_page')->insert($data);
			return success($id);
		} else if ($id>0) {
			View::assign('WEB_TITLE', '修改场景');
			$row = Db::name('member_scene')->where('member_id', $this->member_id)->where('id', $id)->find();
		} else {
			View::assign('WEB_TITLE', '创建场景');
			$row = t('member_scene');
		}
		unsets($row, array('member_id', 'code', 'click', 'share', 'is_publish', 'add_time'));
		return success($row, 'edit.html');
	}
	
	public function getPages() {
		$id = $this->request->get('id', 0);
		if ($id<=0) {
			$page = new \stdClass();
			$page->title = '新页面';
			$page->bg = '';
			$page->content = '';
			$page->status = 1;
			return success(array($page));
		}
		$pages = Db::name('member_scene_page')->where('scene_id', $id)->order(['sort', 'id'=>'asc'])->field('title, bg, content, status')->select();
		if ($pages) {
			foreach ($pages->toArray() as $page) {
				$page['content'] = base64_decode($page['content']);
			}
		} else {
			$page = array();
			$page['title'] = '新页面';
			$page['bg'] = '';
			$page['content'] = '';
			$page['status'] = 1;
			return success(array($page));
		}
		return success($pages);
	}
	
	public function importExcel() {
		$file = $this->request->file('filename', 'excel', ['xls', 'xlsx']);
		if (!strlen($file)) return error('缺少文件');
		$res = import_excel($file, -1);
		if (!count($res)) return error('缺少数据');
		return success($res);
	}
	
	public function template() {
		$rs = Db::name('lib_template')->where('member_id', $this->member_id)->order('id', 'DESC')->select();
		return success(compact('rs'));
	}
	
	public function addTemplate() {
		$member_id = $this->member_id;
		$bg = $this->request->post('bg');
		$content = $this->request->post('content');
		if (!strlen($content)) return error('页面内容为空');
		Db::name('lib_template')->insert(compact('member_id', 'bg', 'content'));
		return success('ok');
	}
	
	public function delTemplate() {
		$id = $this->request->post('id', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('lib_template')->where('member_id', $this->member_id)->where('id', $id)->delete();
		return success('ok');
	}
	
	public function pic() {
		if (IS_POST) {
			$this->uploadImage();
		}
		$group_id = $this->request->get('group_id');
		$where = [
			['member_id', '=', $this->member_id]
		];
		if (strlen($group_id)) {
			$where[] = ['group_id', '=', $group_id];
		}
		$type = $this->request->get('type');
		$list = Db::name('lib_pic_group')->where('member_id', $this->member_id)->order('id', 'DESC')->select();
		$rs = Db::name('lib_pic')->where($where)->order('id', 'DESC')->select();
		return success(compact('type', 'list', 'rs'));
	}
	
	public function music() {
		if (IS_POST) {
			$this->uploadMusic();
		}
		$group_id = $this->request->get('group_id');
		$where = [
			['member_id', '=', $this->member_id]
		];
		if (strlen($group_id)) {
			$where[] = ['group_id', '=', $group_id];
		}
		$list = Db::name('lib_music_group')->where('member_id', $this->member_id)->order('id', 'DESC')->select();
		$rs = Db::name('lib_music')->where($where)->order('id', 'DESC')->select();
		return success(compact('list', 'rs'));
	}
	
	public function addGroup() {
		$type = $this->request->post('type');
		$title = $this->request->post('title');
		if (!strlen($type) || !strlen($title)) return error('参数错误');
		$id = Db::name("lib_{$type}_group")->insert(array('member_id'=>$this->member_id, 'title'=>$title));
		return success($id);
	}
	
	public function transferGroup() {
		$type = $this->request->post('type');
		$group_id = $this->request->post('group_id', 0);
		$id = $this->request->post('id', 0);
		if (!strlen($type) || $group_id<0 || $id<=0) return error('参数错误');
		Db::name("lib_{$type}")->where('member_id', $this->member_id)->where('id', $id)->update(array('group_id'=>$group_id));
		return success('ok');
	}
	
	public function uploadImage() {
		$pic = $this->request->file('filename', 'scene');
		$group_id = $this->request->post('group_id', 0);
		if (!strlen($pic)) return error('提交出错');
		$id = Db::name('lib_pic')->insert(array('member_id'=>$this->member_id, 'group_id'=>$group_id, 'pic'=>$pic));
		return success(compact('id', 'pic'));
	}
	
	public function deleteImage() {
		if (!isset($_POST['ids']) || !is_array($_POST['ids'])) return error('参数错误');
		$ids = $this->request->post('ids', array());
		foreach ($ids as $id) {
			$row = Db::name('lib_pic')->where('member_id', $this->member_id)->where('id', $id)->field('pic')->find();
			if (!$row) continue;
			$file = $row['pic'];
			Db::name('lib_pic')->delete($id);
			$api = new \Qiniu\Qiniu(config('filesystem.disks.qiniu.bucket'), config('filesystem.disks.qiniu.accessKey'), config('filesystem.disks.qiniu.secretKey'), config('filesystem.disks.qiniu.domain'));
			$api->delete($file);
		}
		return success('ok');
	}
	
	public function uploadMusic() {
		$remote = $this->request->post('remote', 0);
		$group_id = $this->request->post('group_id', 0);
		if ($remote==1) {
			$url = $this->request->post('url');
			$title = substr($url, strrpos($url, '/')+1);
			$headers = get_headers($url, true);
			$size = $headers['Content-Length'];
			$size = $this->_sizeFormat($size, 1);
		} else {
			$res = $this->request->file('filename', 'scene', UPLOAD_THIRD, true, array('mp3'));
			$title = $res['name'];
			$url = $res['file'];
			$size = $this->_sizeFormat($res['size'], 1);
		}
		if (!strlen($url)) return error('提交出错');
		$id = Db::name('lib_music')->insert(array('member_id'=>$this->member_id, 'group_id'=>$group_id, 'title'=>$title, 'url'=>$url, 'size'=>$size));
		return success(compact('id', 'title', 'url', 'size'));
	}
	
	public function deleteMusic() {
		if (!isset($_POST['ids']) || !is_array($_POST['ids'])) return error('缺少数据');
		$ids = $this->request->post('ids', array());
		foreach ($ids as $id) {
			$row = Db::name('lib_music')->where('member_id', $this->member_id)->where('id', $id)->field('url')->find();
			if (!$row) continue;
			$file = $row['url'];
			Db::name('lib_music')->delete($id);
			$api = new \Qiniu\Qiniu(config('filesystem.disks.qiniu.bucket'), config('filesystem.disks.qiniu.accessKey'), config('filesystem.disks.qiniu.secretKey'), config('filesystem.disks.qiniu.domain'));
			$api->delete($file);
		}
		return success('ok');
	}
	
	private function _sizeFormat($size, $float=0){
		if(!is_numeric($size))return '';
		if(!is_numeric($float))$float = '';
		if($size>=1073741824){
			return round($size/1073741824, $float).'GB';
		}elseif($size>=1048576){
			return round($size/1048576, $float).'MB';
		}elseif($size>=1024){
			return round($size/1024, $float).'KB';
		}else{
			return $size.'bytes';
		}
	}
	
	//上传到OSS
	public function oss() {
		$id = $this->request->get('id', 0);
		$type = $this->request->get('type');
		$url = $this->request->get('url', '', 'url');
		$bucket = $this->request->get('bucket');
		if ($id<=0 || !strlen($type)) return error('缺少数据');
		$row = Db::name('member_scene')->where('id', $id)->field('code')->find();
		if (!$row) return error('场景不存在');
		$model = m($this->bucket_type);
		$parasitic = $model->upload("show/".$row['code'], "{$this->domain}/v/".$row['code'], $type, array('url'=>$url, 'path'=>"/t/".$row['code'], 'file'=>$id), $bucket);
		return success($parasitic);
	}
	
	public function help() {
		return success('ok');
	}
}
