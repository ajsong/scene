<?php
class article extends core {
	
	public function __construct() {
		parent::__construct();
	}

	//index
	public function index() {
		$where = '';
		$sort = 'a.mark ASC, a.id DESC';
		$id = $this->request->get('id');
		$category_id = $this->request->get('category_id', 0);
		$type = $this->request->get('type');
		$keyword = $this->request->get('keyword');
        $ext_property = $this->request->get('ext_property');
        $sortby = $this->request->get('sortby');
		if (strlen($id)) {
			$where .= " AND a.id='{$id}'";
		}
		if (strlen($keyword)) {
			$where .= " AND (a.title LIKE '%{$keyword}%' OR a.content LIKE '%{$keyword}%')";
		}
		if ($category_id) {
			$where .= " AND a.category_id='{$category_id}'";
		}
		if (strlen($type)) {
			$where .= " AND a.type='{$type}'";
		}
		if ($ext_property) {
			if (!is_array($ext_property)) $ext_property = explode(',', $ext_property);
			$where .= " AND (";
			foreach ($ext_property as $e) {
				$where .= "CONCAT(',',a.ext_property,',') LIKE '%,{$e},%' OR ";
			}
			$where = trim($where, ' OR ').")";
		}
		if ($sortby) {
			$sort = str_replace(',', ' ', $sortby).', '.$sort;
		}
		$rs = SQL::share('article a')
			->left('admin ad', 'a.admin_id=ad.id')
			->left('article_category ac', 'a.category_id=ac.id')
			->where($where)->isezr()->setpages(compact('id', 'keyword', 'category_id', 'type', 'ext_property', 'sortby'))
			->sort($sort)->find("a.*, ad.name as admin_name, ac.name as category_name, '{$this->admin_id}' as admin");
		$sharepage = SQL::share()->page;
		if ($rs) {
			foreach ($rs as $g) {
				unset($g->content);
			}
		}
		
		$categories = SQL::share('article_category')->where("status=1")->find();
		
		if ($this->admin_id<=2) {
			$clicks = SQL::share('article')->sum('clicks');
			$yesterday_clicks = SQL::share('article')->sum('yesterday_clicks');
			$today_clicks = SQL::share('article')->sum('today_clicks');
		} else {
			$clicks = 0;
			$yesterday_clicks = 0;
			$today_clicks = 0;
			$miniprograms = SQL::share('admin_miniprogram_article ama')->left('admin_miniprogram am', 'ama.miniprogram_id=am.miniprogram_id')->where("am.admin_id='{$this->admin_id}'")
				->find('clicks, yesterday_clicks, today_clicks');
			if ($miniprograms) {
				foreach ($miniprograms as $g) {
					$clicks += $g->clicks;
					$yesterday_clicks += $g->yesterday_clicks;
					$today_clicks += $g->today_clicks;
				}
			}
		}
		
		$buckets = SQL::share('bucket')->find();
		$host = https().$_SERVER['HTTP_HOST'];
		success(compact('host', 'rs', 'sharepage', 'categories', 'buckets', 'clicks', 'yesterday_clicks', 'today_clicks'));
	}
	
	public function more() {
		$id = $this->request->post('id', 0);
		$more = $this->request->post('more', 0);
		if ($id<=0) error('缺少数据');
		SQL::share('article')->where($id)->update(compact('more'));
		success('ok');
	}
	
	public function recommend() {
		$id = $this->request->post('id', 0);
		$recommend = $this->request->post('recommend', 0);
		if ($id<=0) error('缺少数据');
		SQL::share('article')->where($id)->update(compact('recommend'));
		success('ok');
	}
	
	public function featured() {
		$id = $this->request->post('id', 0);
		$featured = $this->request->post('featured', 0);
		if ($id<=0) error('缺少数据');
		SQL::share('article')->where($id)->update(compact('featured'));
		success('ok');
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) error('缺少数据');
		SQL::share('article')->where($id)->update(compact('status'));
		success('ok');
	}
	
	public function detail_status() {
		$id = $this->request->post('id', 0);
		$detail_status = $this->request->post('detail_status', 0);
		if ($id<=0) error('缺少数据');
		SQL::share('article')->where($id)->update(compact('detail_status'));
		success('ok');
	}
	
	public function add() {
		$this->edit();
	}
	public function edit() {
		global $img_domain;
		$admin_id = $this->admin_id;
		$id = $this->request->get('id', 0);
		if (IS_POST) { //添加
			$id = $this->request->post('id', 0);
			$title = $this->request->post('title');
			$category_id = $this->request->post('category_id', 0);
			$type = $this->request->post('type', 0);
			$content = $this->request->post('content', '', '\\');
			$prev = $this->request->post('prev', '', '\\');
			$next = $this->request->post('next', '', '\\');
			$url_origin = $this->request->post('url_origin');
			$return_url = $this->request->post('return_url');
			$share_url = $this->request->post('share_url');
			$return_url_status = $this->request->post('return_url_status', 0);
			$share_url_status = $this->request->post('share_url_status', 0);
			$memo = $this->request->post('memo');
			$url = $this->request->post('url');
			$time = $this->request->post('time', 0);
			$sort = $this->request->post('sort', 0);
			$more = $this->request->post('more', 0);
			$recommend = $this->request->post('recommend', 0);
			$featured = $this->request->post('featured', 0);
			$status = $this->request->post('status', 1);
			$wxparse = $this->request->post('wxparse', 0);
			$ext_property = $this->request->post('ext_property', '', 'origin');
			$pic = $this->request->file('article', 'pic', UPLOAD_LOCAL);
			//$music = $this->request->file('article', 'music', UPLOAD_LOCAL, false, ['mp3', 'm4a']);
			$music = $this->request->post('music');
			$music_name = $this->request->post('music_name');
			$music_enable = $this->request->post('music_enable', 0);
			$add_time = $this->request->post('add_time');
			if (strlen($add_time)) $add_time = strtotime($add_time);
			else $add_time = time();
			if (is_array($ext_property)) $ext_property = implode(',', $ext_property);
			if ($type==0) {
				$url = '';
				$time = 0;
			} else {
				$content = '';
			}
			if (strlen($content)) {
				$content = str_replace('data-src=', 'src=', $content);
				$content = str_replace('iframe/preview.html', 'iframe/player.html', $content);
				$content = preg_replace('/width=\d+&amp;height=\d+&amp;/', '', $content);
				preg_match_all('/<img .*?src="([^"]+)"/', $content, $matcher);
				if ($matcher) {
					foreach ($matcher[1] as $m) {
						if (substr($m, 0, 9)!='/uploads/' && strpos($m, $img_domain)===false) {
							$u = $this->_getFile($m);
							$content = str_replace($m, $u, $content);
						}
					}
				}
			}
			if (strlen($prev)) {
				$prev = str_replace('data-src=', 'src=', $prev);
				$prev = str_replace('iframe/preview.html', 'iframe/player.html', $prev);
				$prev = preg_replace('/width=\d+&amp;height=\d+&amp;/', '', $prev);
				preg_match_all('/<img .*?src="([^"]+)"/', $prev, $matcher);
				if ($matcher) {
					foreach ($matcher[1] as $m) {
						if (substr($m, 0, 9)!='/uploads/' && strpos($m, $img_domain)===false) {
							$u = $this->_getFile($m);
							$prev = str_replace($m, $u, $prev);
						}
					}
				}
			}
			if (strlen($next)) {
				$next = str_replace('data-src=', 'src=', $next);
				$next = str_replace('iframe/preview.html', 'iframe/player.html', $next);
				$next = preg_replace('/width=\d+&amp;height=\d+&amp;/', '', $next);
				preg_match_all('/<img .*?src="([^"]+)"/', $next, $matcher);
				if ($matcher) {
					foreach ($matcher[1] as $m) {
						if (substr($m, 0, 9)!='/uploads/' && strpos($m, $img_domain)===false) {
							$u = $this->_getFile($m);
							$next = str_replace($m, $u, $next);
						}
					}
				}
			}
			$data = compact('title', 'category_id', 'type', 'pic', 'content', 'memo', 'sort', 'more', 'recommend', 'featured', 'status', 'ext_property', 'url', 'time', 'music_enable', 'music', 'music_name', 'admin_id', 'add_time', 'wxparse');
			if ($id>0) {
				SQL::share('article')->where($id)->update($data);
				SQL::share('admin_article')->where("admin_id={$admin_id} AND article_id={$id}")->update(compact('prev', 'next', 'url_origin', 'return_url', 'share_url', 'return_url_status', 'share_url_status'));
				$sql = SQL::share('admin_article')->where("article_id='{$id}' AND admin_id='{$admin_id}'")->createSql("prev, next, url_origin, return_url, share_url, return_url_status, share_url_status, '' as jssdk");
				SQL::share()->clearCached($sql);
			} else {
				$data['add_time'] = time();
				$id = SQL::share('article')->insert($data);
				$article_id = $id;
				SQL::share('admin_article')->insert(compact('admin_id', 'article_id', 'prev', 'next', 'url_origin', 'return_url', 'share_url', 'return_url_status', 'share_url_status'));
				$rs = SQL::share('miniprogram')->where("type='0'")->sort('id ASC')->find('id');
				foreach ($rs as $g) {
					SQL::share('article_attr')->insert(array('miniprogram_id'=>$g->id, 'article_id'=>$id));
				}
			}
			location("?app=article&act=edit&id={$id}&msg=1");
		} else if ($id>0) { //显示
			$row = SQL::share('article')->where($id)->row("*, '' as prev, '' as next, '' as url_origin, '' as return_url, '' as share_url, 1 as return_url_status, 1 as share_url_status");
			if (SQL::share('admin_article')->where("admin_id={$admin_id} AND article_id={$id}")->exist()) {
				$rs = SQL::share('admin_article')->where("admin_id={$admin_id} AND article_id={$id}")->row('prev, next, url_origin, return_url, share_url, return_url_status, share_url_status');
				$row->prev = $rs->prev;
				$row->next = $rs->next;
				$row->url_origin = $rs->url_origin;
				$row->return_url = $rs->return_url;
				$row->share_url = $rs->share_url;
				$row->return_url_status = $rs->return_url_status;
				$row->share_url_status = $rs->share_url_status;
			} else {
				SQL::share('admin_article')->insert(array('admin_id'=>$admin_id, 'article_id'=>$id));
			}
			$miniprogram = SQL::share('miniprogram')->where("gstatus=1 AND status=1")->sort('id ASC')->find('id, name, 0 as shown, 0 as hidden');
			if ($miniprogram) {
				foreach ($miniprogram as $g) {
					$g->shown = SQL::share('article_comment')->where("miniprogram_id='{$g->id}' AND article_id='{$id}' AND parent_id=0 AND status=1")->count();
					$g->hidden = SQL::share('article_comment')->where("miniprogram_id='{$g->id}' AND article_id='{$id}' AND parent_id=0 AND status=0")->count();
				}
			}
		} else {
			$row = t('article');
			$row->prev = '';
			$row->next = '';
			$row->url_origin = '';
			$row->return_url = '';
			$row->share_url = '';
			$row->return_url_status = 1;
			$row->share_url_status = 1;
			$miniprogram = NULL;
		}
		
		$categories = SQL::share('article_category')->where("status=1")->find();
		
		success(compact('row', 'categories', 'miniprogram'), 'article.edit.html');
	}
	
	private function _getFile($url) {
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$ch = curl_init();
		$suffix = '';
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
		curl_close($ch);
		$file = upload_obj_file($content, 'article');
		$file = add_domain($file);
		return $file;
		/*$filename = generate_sn().'.'.$suffix;
		$dir = UPLOAD_PATH.'/article/'.date('Y').'/'.date('m').'/'.date('d');
		makedir($dir);
		$res = @fopen(ROOT_PATH.$dir.'/'.$filename, 'a');
		@fwrite($res, $content);
		@fclose($res);
		$file = str_replace('/public/', '/', $dir).'/'.$filename;
		if (in_array($suffix, ['jpg', 'jpeg'])) {
			image_compress('/public/'.$file, 1, '/public/'.$file);
		}
		return $file;*/
	}

	public function upload_pic(){
        $result = $this->request->file('article', 'pic', UPLOAD_LOCAL);
		success($result);
    }

	//delete
	public function delete() {
		$id = $this->request->get('id', 0);
		SQL::share('article')->delete($id);
		SQL::share('article_attr')->delete("article_id='{$id}'");
		$model = m('oss');
		$model->delete("article/{$this->admin_id}/{$id}");
		header("Location:?app=article&act=index");
	}
	
	public function multiple_delete() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) error('请选择');
		SQL::share('article')->delete("id IN ({$ids})");
		SQL::share('article_attr')->delete("article_id IN ({$ids})");
		$model = m('oss');
		$_ids = explode(',', $ids);
		foreach ($_ids as $id) $model->delete("article/{$this->admin_id}/{$id}");
		header("Location:?app=article&act=index");
	}
	
	public function comment() {
		$article_id = $this->request->get('article_id', 0);
		$miniprogram_id = $this->request->get('miniprogram_id', 0);
		$comments = SQL::share('article_comment c')->where("article_id='{$article_id}' AND miniprogram_id='{$miniprogram_id}' AND c.parent_id=0")
			->sort('c.id DESC')->find("c.id, c.member_id, c.content, c.ip, c.add_time, '网友' as member_name, '/images/avatar.png' as member_avatar");
		if ($comments) {
			foreach ($comments as $g) {
				$g->add_time = get_time_word($g->add_time);
				$replys = SQL::share('article_comment c')->where("c.parent_id='{$g->id}'")
					->sort('c.id ASC')->find("c.id, c.member_id, c.content, c.ip, c.add_time, '网友' as member_name");
				if ($replys) {
					foreach ($replys as $rg) {
						$rg->add_time = get_time_word($rg->add_time);
						//if ($rg->member_id==-1) $rg->member_name = '客服';
					}
				}
				$g->replys = $replys;
			}
		}
		success($comments);
	}
	
	public function comment_show() {
		$id = $this->request->post('id', 0);
		$row = SQL::share('article_comment')->where($id)->row('status');
		if (!$row) error('该评论不存在');
		$status = $row->status==1 ? 0 : 1;
		SQL::share('article_comment')->where($id)->update(compact('status'));
		success('ok');
	}
	
	//删除评论
	public function comment_delete() {
		$id = $this->request->post('id', 0);
		$article_id = SQL::share('article_comment')->where($id)->value('article_id');
		SQL::share('article')->where($article_id)->update(array('comments'=>array('-1')));
		SQL::share('article_comment')->delete($id);
		success('ok');
	}
	
	//添加评论回复
	public function reply_add() {
		$article_id = $this->request->post('article_id', 0);
		$parent_id = $this->request->post('parent_id', 0);
		$content = $this->request->post('content');
		if ($article_id<=0) error('缺少文章id');
		if ($parent_id<=0) error('缺少父评论id');
		if (!$content) error('请填写回复内容');
		$time = time();
		$id = SQL::share('article_comment')->insert(array('member_id'=>-1, 'article_id'=>$article_id, 'parent_id'=>$parent_id, 'content'=>$content, 'status'=>1, 'ip'=>$this->ip, 'add_time'=>$time));
		success(array('id'=>$id, 'member_name'=>'客服', 'content'=>$content, 'add_time'=>get_time_word($time)));
	}
	
	//删除评论回复
	public function reply_delete() {
		$id = $this->request->post('id', 0);
		SQL::share('article_comment')->delete($id);
		success('ok');
	}
	
	public function hidden() {
		$miniprogram_id = $this->request->get('miniprogram_id', 0);
		if (IS_POST) {
			$miniprogram_id = $this->request->post('miniprogram_id', 0);
			$article_id = $this->request->post('id', 0);
			if (SQL::share('miniprogram_article_hidden')->where("miniprogram_id='{$miniprogram_id}' AND article_id='{$article_id}'")->exist()) {
				SQL::share('miniprogram_article_hidden')->delete("miniprogram_id='{$miniprogram_id}' AND article_id='{$article_id}'");
			} else {
				SQL::share('miniprogram_article_hidden')->insert(compact('miniprogram_id', 'article_id'));
			}
			success('ok');
		}
		$where = '';
		$id = $this->request->get('id');
		$category_id = $this->request->get('category_id', 0);
		$type = $this->request->get('type');
		$keyword = $this->request->get('keyword');
		if (strlen($id)) {
			$where .= " AND a.id='{$id}'";
		}
		if (strlen($keyword)) {
			$where .= " AND (a.title LIKE '%{$keyword}%' OR a.content LIKE '%{$keyword}%')";
		}
		if ($category_id) {
			$where .= " AND a.category_id='{$category_id}'";
		}
		if (strlen($type)) {
			$where .= " AND a.type='{$type}'";
		}
		$rs = SQL::share('article a')
			->left('article_category ac', 'a.category_id=ac.id')
			->where($where)->isezr()->setpages(compact('id', 'keyword', 'category_id', 'type', 'miniprogram_id'))
			->sort('a.id DESC')->find("a.id, a.title, a.type, a.add_time, ac.name as category_name");
		$sharepage = SQL::share()->page;
		$categories = SQL::share('article_category')->where("status=1")->find();
		$miniprogram = SQL::share('miniprogram')->where($miniprogram_id)->row();
		$hidden = SQL::share('miniprogram_article_hidden')->where("miniprogram_id='{$miniprogram_id}'")->returnArray()->find('article_id');
		$hidden = implode(',', $hidden);
		success(compact('rs', 'sharepage', 'categories', 'miniprogram', 'hidden'));
	}
	
	//上传到OSS
	public function oss() {
		$id = $this->request->get('id', 0);
		$type = $this->request->get('type');
		$return_array = $this->request->get('return_array', 0);
		$bucket = $this->request->get('bucket');
		if ($id<=0 || !strlen($type)) error('缺少数据');
		if (!SQL::share('article')->where($id)->exist()) error('记录不存在');
		$model = m('cos');
		$parasitic = $model->upload("article/{$this->admin_id}/{$id}", "{$this->domain}/article/{$this->admin_id}/{$id}", $type, $return_array, $bucket);
		success($parasitic);
	}
	
	//创建储存桶
	public function createBucket() {
		$type = $this->request->post('type', 'cos');
		$bucket = $this->request->post('bucket');
		if (!strlen($bucket)) error('缺少储存桶名称');
		//if (SQL::share('bucket')->where("type='{$type}' AND bucket='{$bucket}'")->exist()) error('储存桶已存在');
		$model = m($type);
		$model->createBucket($bucket);
		success('ok');
	}
	
	//上传文件到储存桶
	public function putObject() {
		$type = $this->request->post('type', 'cos');
		$bucket = $this->request->get('bucket');
		$res = $this->request->file('file', 'filename', 0, true, array('txt'));
		$model = m($type);
		$model->putObject($bucket, $res['name'], file_get_contents($res['tmp_name']));
		success('ok');
	}
}
