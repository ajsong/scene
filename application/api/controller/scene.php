<?php
class scene extends core {

	public function __construct() {
		parent::__construct();
	}
	
	public function copy() {
		$id = $this->request->request('id', 0);
		if (!SQL::share('member_scene')->where($id)->exist()) error('该场景不存在');
		if (IS_POST) {
			$code = strtoupper(random_str(11));
			$time = time();
			$fields = 'member_id, title, memo, cover, music, music_name, music_pic, music_position, music_play, share_url, return_url, fenliu_url, grid, ruler, pad, suffix';
			$data = SQL::share('member_scene')->where($id)->returnArray()->row("{$fields}, '{$code}' as code, '{$time}' as edit_time, '{$time}' as add_time");
			$scene_id = SQL::share('member_scene')->insert($data);
			$data = SQL::share('member_scene_page')->where("scene_id='{$id}'")->returnArray()->sort('id ASC')->find("title, bg, content, sort, '{$scene_id}' as scene_id");
			SQL::share('member_scene_page')->insert($data);
			$row = SQL::share('member_scene')->where($scene_id)->row();
			$row->url = "{$this->domain}/v/{$code}";
			success($row);
		}
		success('ok');
	}
	
	public function gift() {
		$id = $this->request->request('id', 0);
		if (!SQL::share('member_scene')->where("member_id='{$this->member_id}' AND id='{$id}'")->exist()) error('该场景不存在');
		if (IS_POST) {
			$username = $this->request->post('username');
			if (!strlen($username)) error('请填写受赠方的登录账号');
			$member = SQL::share('member')->where("name='{$username}'")->row('id');
			if (!$member) error('该受赠方不存在');
			if ($member->id==$this->member_id) error('不应该赠送给自己');
			SQL::share('member_scene')->where("member_id='{$this->member_id}' AND id='{$id}'")->update(array('member_id'=>$member->id));
		}
		success('ok');
	}
	
	public function delete() {
		$id = $this->request->request('id', 0);
		if (!SQL::share('member_scene')->where("member_id='{$this->member_id}' AND id='{$id}'")->exist()) error('该场景不存在');
		if (IS_POST) {
			SQL::share('member_scene')->delete("member_id='{$this->member_id}' AND id='{$id}'");
			SQL::share('member_scene_page')->delete("scene_id='{$id}'");
		}
		success('ok');
	}
	
	public function detail() {
		$id = $this->request->get('id', 0);
		if ($id<=0) error('数据错误');
		$row = SQL::share('member_scene')->where("member_id='{$this->member_id}' AND id='{$id}'")->row();
		if ($row) {
			$row->url = "{$this->domain}/v/{$row->code}";
		}
		success($row);
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) error('数据错误');
		SQL::share('member_scene')->where("member_id='{$this->member_id}' AND id='{$id}'")->update(compact('status'));
		success('ok');
	}
	
	public function publish() {
		$id = $this->request->request('id', 0);
		if ($id<=0) error('数据错误');
		SQL::share('member_scene')->where("member_id='{$this->member_id}' AND id='{$id}'")->update(array('is_publish'=>1));
		if (IS_POST) success('ok');
		location("/index/scene/detail?id={$id}");
	}
	
	public function add() {
		$this->edit();
	}
	public function edit() {
		$id = $this->request->get('id', 0);
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
			$grid = $this->request->post('grid', 1);
			$ruler = $this->request->post('ruler', 1);
			$pad = $this->request->post('pad');
			$suffix = $this->request->post('suffix', 1);
			$status = $this->request->post('status', 1);
			$pages = $this->request->post('pages', array());
			$member_id = $this->member_id;
			$edit_time = time();
			if (!strlen($cover)) error('请选择封面图片');
			if (!strlen($title)) error('场景标题不能为空');
			if (!is_array($pages) || !count($pages)) error('缺少场景页');
			$data = compact('member_id', 'title', 'memo', 'cover', 'music', 'music_name', 'music_pic', 'music_position', 'music_play',
				'share_url', 'return_url', 'fenliu_url', 'grid', 'ruler', 'pad', 'suffix', 'status', 'edit_time');
			if ($id>0) {
				if (!SQL::share('member_scene')->where("member_id='{$member_id}' AND id='{$id}'")->exist()) error('该场景不存在');
				SQL::share('member_scene')->where("member_id='{$member_id}' AND id='{$id}'")->update($data);
			} else {
				$data['code'] = strtoupper(random_str(11));
				$data['add_time'] = time();
				$id = SQL::share('member_scene')->insert($data);
			}
			SQL::share('member_scene_page')->delete("scene_id='{$id}'");
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
			SQL::share('member_scene_page')->insert($data);
			success($id);
		} else if ($id>0) {
			$this->smarty->assign('WEB_TITLE', '修改场景');
			$row = SQL::share('member_scene')->where("member_id='{$this->member_id}' AND id='{$id}'")->row();
		} else {
			$this->smarty->assign('WEB_TITLE', '创建场景');
			$row = t('member_scene');
		}
		unsets($row, array('member_id', 'code', 'click', 'share', 'is_publish', 'add_time'));
		$this->smarty->clearCache('scene.edit.html');
		success($row, 'scene.edit.html');
	}
	
	public function getPages() {
		$id = $this->request->get('id', 0);
		if ($id<=0) {
			$page = new stdClass();
			$page->title = '新页面';
			$page->bg = '';
			$page->content = '';
			success(array($page));
		}
		$pages = SQL::share('member_scene_page')->where("scene_id='{$id}'")->sort('sort ASC, id ASC')->find('title, bg, content');
		if ($pages) {
			foreach ($pages as $page) {
				$page->content = base64_decode($page->content);
			}
		} else {
			$page = new stdClass();
			$page->title = '新页面';
			$page->bg = '';
			$page->content = '';
			success(array($page));
		}
		success($pages);
	}
	
	public function template() {
		$rs = SQL::share('lib_template')->where("member_id='{$this->member_id}'")->sort('id DESC')->find();
		success(compact('rs'));
	}
	
	public function addTemplate() {
		$member_id = $this->member_id;
		$bg = $this->request->post('bg');
		$content = $this->request->post('content');
		if (!strlen($content)) error('页面内容为空');
		SQL::share('lib_template')->insert(compact('member_id', 'bg', 'content'));
		success('ok');
	}
	
	public function delTemplate() {
		$id = $this->request->post('id', 0);
		if ($id<=0) error('缺少数据');
		SQL::share('lib_template')->delete("member_id='{$this->member_id}' AND id='{$id}'");
		success('ok');
	}
	
	public function pic() {
		if (IS_POST) {
			$this->uploadImage();
		}
		$group_id = $this->request->get('group_id');
		$where = '';
		if (strlen($group_id)) {
			$where .= " AND group_id='{$group_id}'";
		}
		$type = $this->request->get('type');
		$list = SQL::share('lib_pic_group')->where("member_id='{$this->member_id}'")->sort('id DESC')->find();
		$rs = SQL::share('lib_pic')->where("member_id='{$this->member_id}'{$where}")->sort('id DESC')->find();
		success(compact('type', 'list', 'rs'));
	}
	
	public function music() {
		if (IS_POST) {
			$this->uploadMusic();
		}
		$group_id = $this->request->get('group_id');
		$where = '';
		if (strlen($group_id)) {
			$where .= " AND group_id='{$group_id}'";
		}
		$list = SQL::share('lib_music_group')->where("member_id='{$this->member_id}'")->sort('id DESC')->find();
		$rs = SQL::share('lib_music')->where("member_id='{$this->member_id}'{$where}")->sort('id DESC')->find();
		success(compact('list', 'rs'));
	}
	
	public function addGroup() {
		$type = $this->request->post('type');
		$title = $this->request->post('title');
		if (!strlen($type) || !strlen($title)) error('参数错误');
		$id = SQL::share("lib_{$type}_group")->insert(array('member_id'=>$this->member_id, 'title'=>$title));
		success($id);
	}
	
	public function transferGroup() {
		$type = $this->request->post('type');
		$group_id = $this->request->post('group_id', 0);
		$id = $this->request->post('id', 0);
		if (!strlen($type) || $group_id<0 || $id<=0) error('参数错误');
		SQL::share("lib_{$type}")->where("member_id='{$this->member_id}' AND id='{$id}'")->update(array('group_id'=>$group_id));
		success('ok');
	}
	
	public function uploadImage() {
		$pic = $this->request->file(NULL, 'filename');
		$group_id = $this->request->post('group_id', 0);
		if (!strlen($pic)) error('提交出错');
		$id = SQL::share('lib_pic')->insert(array('member_id'=>$this->member_id, 'group_id'=>$group_id, 'pic'=>$pic));
		success(compact('id', 'pic'));
	}
	
	public function deleteImage() {
		if (!isset($_POST['ids']) || !is_array($_POST['ids'])) error('参数错误');
		$ids = $this->request->post('ids', array());
		foreach ($ids as $id) {
			$row = SQL::share('lib_pic')->where("id='{$id}' AND member_id='{$this->member_id}'")->row('pic');
			if (!$row) continue;
			$file = $row->pic;
			SQL::share('lib_pic')->delete($id);
			$api = p('upload', 'qniu');
			$api->delete($file);
		}
		success('ok');
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
			$res = $this->request->file(NULL, 'filename', true, true, array('mp3'));
			$title = $res['name'];
			$url = $res['file'];
			$size = $this->_sizeFormat($res['size'], 1);
		}
		if (!strlen($url)) error('提交出错');
		$id = SQL::share('lib_music')->insert(array('member_id'=>$this->member_id, 'group_id'=>$group_id, 'title'=>$title, 'url'=>$url, 'size'=>$size));
		success(compact('id', 'title', 'url', 'size'));
	}
	
	public function deleteMusic() {
		if (!isset($_POST['ids']) || !is_array($_POST['ids'])) error('缺少数据');
		$ids = $this->request->post('ids', array());
		foreach ($ids as $id) {
			$row = SQL::share('lib_music')->where("id='{$id}' AND member_id='{$this->member_id}'")->row('url');
			if (!$row) continue;
			$file = $row->url;
			SQL::share('lib_music')->delete($id);
			$api = p('upload', 'qniu');
			$api->delete($file);
		}
		success('ok');
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
		$return_array = $this->request->get('return_array', 0);
		$bucket = $this->request->get('bucket');
		if ($id<=0 || !strlen($type)) error('缺少数据');
		$row = SQL::share('member_scene')->where($id)->row('code');
		if (!$row) error('场景不存在');
		$model = m('cos');
		$parasitic = $model->upload("show/{$row->code}", "{$this->domain}/v/{$row->code}", $type, $return_array, $bucket);
		success($parasitic);
	}
	
	public function help() {
		success('ok');
	}
}
