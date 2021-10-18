<?php
declare (strict_types = 1);

namespace app\gm\controller;

use app\gm\controller\Core;
use think\facade\Db;
use think\facade\View;
use think\Request;

class Article extends Core
{
	//index
	public function index() {
		$where = [];
		$sort = ['a.mark'=>'ASC', 'a.id'=>'DESC'];
		$id = $this->request->get('id');
		$category_id = $this->request->get('category_id', 0);
		$type = $this->request->get('type');
		$keyword = $this->request->get('keyword');
		$ext_property = $this->request->get('ext_property');
		$sortby = $this->request->get('sortby');
		$action = $this->request->get('action');
		$start = $this->request->get('start');
		$end = $this->request->get('end');
		if (strlen($id)) {
			$where[] = ['a.id', '=', $id];
		}
		if (strlen($keyword)) {
			$where[] = ['a.title|a.content', 'like', "%{$keyword}%"];
		}
		if ($category_id) {
			$where[] = ['a.category_id', '=', $category_id];
		}
		if (strlen($type)) {
			$where[] = ['a.type', '=', $type];
		}
		if ($ext_property) {
			if (!is_array($ext_property)) $ext_property = explode(',', $ext_property);
			$q = '';
			foreach ($ext_property as $e) {
				$q .= "CONCAT(',',a.ext_property,',') LIKE '%,{$e},%' OR ";
			}
			$q = trim($q, ' OR ');
			$where[] = Db::raw($q);
		}
		if ($sortby) {
			$e = explode(',', $sortby);
			$sort = [$e[0] => $e[1]] + $sort;
		}
		if (strlen($start)) {
			$where[] = ['a.add_time', '>=', strtotime($start)];
		}
		if (strlen($end)) {
			$where[] = ['a.add_time', '<=', strtotime("{$end} 23:59:59")];
		}
		$rs = Db::name('article')->alias('a')
			->leftJoin('admin ad', 'a.admin_id=ad.id')
			->leftJoin('article_category ac', 'a.category_id=ac.id')
			->where($where)->order($sort)->field("a.*, ad.name as admin_name, ac.name as category_name, '{$this->admin_id}' as admin")
			->paginate(['list_rows'=>10, 'query'=>request()->param()])->each(function($item) {
				unset($item['content']);
				return $item;
			});
		
		$categories = Db::name('article_category')->where('status', 1)->select();
		
		if ($this->admin_id<=2) {
			$clicks = Db::name('article')->sum('clicks');
			$yesterday_clicks = Db::name('article')->sum('yesterday_clicks');
			$today_clicks = Db::name('article')->sum('today_clicks');
		} else {
			$clicks = 0;
			$yesterday_clicks = 0;
			$today_clicks = 0;
			$miniprograms = Db::name('admin_miniprogram_article')->alias('ama')->leftJoin('admin_miniprogram am', 'ama.miniprogram_id=am.miniprogram_id')->where('am.admin_id', $this->admin_id)
				->field('clicks, yesterday_clicks, today_clicks')->select();
			if ($miniprograms) {
				foreach ($miniprograms as $g) {
					$clicks += $g->clicks;
					$yesterday_clicks += $g->yesterday_clicks;
					$today_clicks += $g->today_clicks;
				}
			}
		}
		
		$bucket_type = $this->bucket_type;
		$buckets = Db::name('bucket')->where('type', $bucket_type)->select();
		$host = $this->front_domain;
		View::assign('is_article_add', core::check_permission('article', 'add'));
		View::assign('is_article_delete', core::check_permission('article', 'delete'));
		setViewAssign(compact('id', 'keyword', 'category_id', 'type', 'ext_property', 'sortby', 'action', 'start', 'end'));
		setViewPage($rs);
		setViewAssign(compact('host', 'categories', 'clicks', 'yesterday_clicks', 'today_clicks', 'buckets', 'bucket_type'));
		return success($rs);
	}
	
	public function more() {
		$id = $this->request->post('id', 0);
		$more = $this->request->post('more', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('article')->where('id', $id)->update(compact('more'));
		return success('ok');
	}
	
	public function recommend() {
		$id = $this->request->post('id', 0);
		$recommend = $this->request->post('recommend', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('article')->where('id', $id)->update(compact('recommend'));
		return success('ok');
	}
	
	public function featured() {
		$id = $this->request->post('id', 0);
		$featured = $this->request->post('featured', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('article')->where('id', $id)->update(compact('featured'));
		return success('ok');
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('article')->where('id', $id)->update(compact('status'));
		return success('ok');
	}
	
	public function detail_status() {
		$id = $this->request->post('id', 0);
		$detail_status = $this->request->post('detail_status', 0);
		if ($id<=0) return error('缺少数据');
		Db::name('article')->where('id', $id)->update(compact('detail_status'));
		return success('ok');
	}
	
	public function add() {
		return $this->edit();
	}
	public function edit() {
		global $img_domain;
		$admin_id = $this->admin_id;
		$id = $this->request->get('id', 0);
		if (IS_POST) {
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
			$pic = $this->request->file('pic', 'article', UPLOAD_THIRD);
			//$music = $this->request->file('article', 'music', UPLOAD_THIRD, false, ['mp3', 'm4a']);
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
			$data = compact('title', 'category_id', 'type', 'pic', 'content', 'memo', 'sort', 'more', 'recommend', 'featured', 'status', 'ext_property', 'url', 'time', 'music_enable', 'music', 'music_name', 'add_time', 'wxparse');
			if ($id>0) {
				Db::name('article')->where('id', $id)->update($data);
				Db::name('admin_article')->where(['admin_id'=>$admin_id, 'article_id'=>$id])->update(compact('prev', 'next', 'url_origin', 'return_url', 'share_url', 'return_url_status', 'share_url_status'));
			} else {
				$data['admin_id'] = $admin_id;
				$data['add_time'] = time();
				$id = Db::name('article')->insertGetId($data);
				$article_id = $id;
				Db::name('admin_article')->insert(compact('admin_id', 'article_id', 'prev', 'next', 'url_origin', 'return_url', 'share_url', 'return_url_status', 'share_url_status'));
				$rs = Db::name('miniprogram')->where('type', 0)->order('id', 'ASC')->field('id')->select();
				foreach ($rs as $g) {
					Db::name('article_attr')->insert(array('miniprogram_id'=>$g['id'], 'article_id'=>$id));
				}
			}
			/*$admin = Db::name('admin')->where($admin_id)->row('prev, next, url_origin, return_url, share_url');
			if ($admin) {
				if (!strlen($prev)) $prev = $admin->prev;
				if (!strlen($next)) $next = $admin->next;
				if (!strlen($url_origin)) $url_origin = $admin->url_origin;
				if (!strlen($return_url)) $return_url = $admin->return_url;
				if (!strlen($share_url)) $share_url = $admin->share_url;
			}
			if ($return_url_status==0) $return_url = '';
			if ($share_url_status==0) $share_url = '';
			$json = array(
				'data' => [
					'prev' => $prev,
					'next' => $next,
					'url_origin' => $url_origin,
					'return_url' => $return_url,
					'share_url' => $share_url,
					'jssdk' => ''
				],
				'msg_type' => 0,
				'msg' => '成功',
				'error' => 0
			);
			$options = json_decode($this->bucket_type=='oss' ? OSS_OPTIONS : COS_OPTIONS, true);
			$model = m($this->bucket_type);
			$model->putObject($options['bucket'], "article/{$admin_id}/{$id}/item.json", json_encode($json, JSON_UNESCAPED_UNICODE));*/
			location("/article/edit?id={$id}&msg=1");
		} else if ($id>0) { //显示
			$row = Db::name('article')->where('id', $id)->field("*, '' as prev, '' as next, '' as url_origin, '' as return_url, '' as share_url, 1 as return_url_status, 1 as share_url_status")->find();
			if (Db::name('admin_article')->where(['admin_id'=>$admin_id, 'article_id'=>$id])->count() > 0) {
				$rs = Db::name('admin_article')->where(['admin_id'=>$admin_id, 'article_id'=>$id])->field('prev, next, url_origin, return_url, share_url, return_url_status, share_url_status')->find();
				$row['prev'] = $rs['prev'];
				$row['next'] = $rs['next'];
				$row['url_origin'] = $rs['url_origin'];
				$row['return_url'] = $rs['return_url'];
				$row['share_url'] = $rs['share_url'];
				$row['return_url_status'] = $rs['return_url_status'];
				$row['share_url_status'] = $rs['share_url_status'];
			} else {
				Db::name('admin_article')->insert(array('admin_id'=>$admin_id, 'article_id'=>$id));
			}
			$miniprogram = Db::name('miniprogram')->where(['gstatus'=>1, 'status'=>1])->order('id', 'ASC')->field('id, name, 0 as shown, 0 as hidden')->select();
			if ($miniprogram) {
				foreach ($miniprogram as $g) {
					$g['shown'] = Db::name('article_comment')->where(['miniprogram_id'=>$g['id'], 'article_id'=>$id, 'parent_id'=>0, 'status'=>1])->count();
					$g['hidden'] = Db::name('article_comment')->where(['miniprogram_id'=>$g['id'], 'article_id'=>$id, 'parent_id'=>0, 'status'=>0])->count();
				}
			}
		} else {
			$row = t('article');
			$row['prev'] = '';
			$row['next'] = '';
			$row['url_origin'] = '';
			$row['return_url'] = '';
			$row['share_url'] = '';
			$row['return_url_status'] = 1;
			$row['share_url_status'] = 1;
			$miniprogram = NULL;
		}
		
		$categories = Db::name('article_category')->where('status', 1)->select();
		setViewAssign(compact('row', 'categories', 'miniprogram'));
		return success('ok', 'edit.html');
	}
	
	private function _getFile($url) {
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
		$result = $this->request->file('article', 'pic', UPLOAD_THIRD);
		return success($result);
	}
	
	//delete
	public function delete() {
		$id = $this->request->get('id', 0);
		Db::name('article')->where('id', $id)->delete();
		Db::name('article_attr')->where('article_id', $id)->delete();
		Db::name('admin_article')->where('article_id', $id)->delete();
		//$model = m($this->bucket_type);
		//$model->delete("article/{$this->admin_id}/{$id}");
		location('/article');
	}
	
	public function multiple_delete() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) return error('请选择');
		Db::name('article')->whereIn('id', $ids)->delete();
		Db::name('article_attr')->whereIn('article_id', $ids)->delete();
		Db::name('admin_article')->whereIn('article_id', $ids)->delete();
		//$model = m($this->bucket_type);
		//$_ids = explode(',', $ids);
		//foreach ($_ids as $id) $model->delete("article/{$this->admin_id}/{$id}");
		location('/article');
	}
	
	public function comment() {
		$article_id = $this->request->get('article_id', 0);
		$miniprogram_id = $this->request->get('miniprogram_id', 0);
		$comments = Db::name('article_comment')->alias('ac')->where(['article_id'=>$article_id, 'miniprogram_id'=>$miniprogram_id, 'ac.parent_id'=>0])
			->order('ac.id', 'DESC')->field("ac.id, ac.member_id, ac.content, ac.ip, ac.add_time, '网友' as member_name, '/images/avatar.png' as member_avatar")->select();
		if ($comments) {
			foreach ($comments as $g) {
				$g['add_time'] = get_time_word($g['add_time']);
				$replys = Db::name('article_comment')->alias('ac')->where('ac.parent_id', $g['id'])
					->order('ac.id', 'ASC')->field("ac.id, ac.member_id, ac.content, ac.ip, ac.add_time, '网友' as member_name")->select();
				if ($replys) {
					foreach ($replys as $rg) {
						$rg['add_time'] = get_time_word($rg['add_time']);
						//if ($rg->member_id==-1) $rg->member_name = '客服';
					}
				}
				$g['replys'] = $replys;
			}
		}
		return success($comments);
	}
	
	public function comment_show() {
		$id = $this->request->post('id', 0);
		$row = Db::name('article_comment')->where('id', $id)->field('status')->find();
		if (!$row) return error('该评论不存在');
		$status = $row['status']==1 ? 0 : 1;
		Db::name('article_comment')->where('id', $id)->update(compact('status'));
		return success('ok');
	}
	
	//删除评论
	public function comment_delete() {
		$id = $this->request->post('id', 0);
		$article_id = Db::name('article_comment')->where('id', $id)->value('article_id');
		Db::name('article')->where('id', $article_id)->dec('comments')->update();
		Db::name('article_comment')->where('id', $id)->delete();
		return success('ok');
	}
	
	//添加评论回复
	public function reply_add() {
		$article_id = $this->request->post('article_id', 0);
		$parent_id = $this->request->post('parent_id', 0);
		$content = $this->request->post('content');
		if ($article_id<=0) return error('缺少文章id');
		if ($parent_id<=0) return error('缺少父评论id');
		if (!$content) return error('请填写回复内容');
		$time = time();
		$id = Db::name('article_comment')->insert(array('member_id'=>-1, 'article_id'=>$article_id, 'parent_id'=>$parent_id, 'content'=>$content, 'status'=>1, 'ip'=>$this->ip, 'add_time'=>$time));
		return success(array('id'=>$id, 'member_name'=>'客服', 'content'=>$content, 'add_time'=>get_time_word($time)));
	}
	
	//删除评论回复
	public function reply_delete() {
		$id = $this->request->post('id', 0);
		Db::name('article_comment')->where('id', $id)->delete();
		return success('ok');
	}
	
	public function hidden() {
		$miniprogram_id = $this->request->get('miniprogram_id', 0);
		if (IS_POST) {
			$miniprogram_id = $this->request->post('miniprogram_id', 0);
			$article_id = $this->request->post('id', 0);
			if (Db::name('miniprogram_article_hidden')->where(['miniprogram_id'=>$miniprogram_id, 'article_id'=>$article_id])->count() > 0) {
				Db::name('miniprogram_article_hidden')->where(['miniprogram_id'=>$miniprogram_id, 'article_id'=>$article_id])->delete();
			} else {
				Db::name('miniprogram_article_hidden')->insert(compact('miniprogram_id', 'article_id'));
			}
			return success('ok');
		}
		$where = [];
		$id = $this->request->get('id');
		$category_id = $this->request->get('category_id', 0);
		$type = $this->request->get('type');
		$keyword = $this->request->get('keyword');
		if (strlen($id)) {
			$where[] = ['a.id', '=', $id];
		}
		if (strlen($keyword)) {
			$where[] = ['a.title|a.content', 'like', "%{$keyword}%"];
		}
		if ($category_id) {
			$where[] = ['a.category_id', '=', $category_id];
		}
		if (strlen($type)) {
			$where[] = ['a.type', '=', $type];
		}
		$rs = Db::name('article')->alias('a')
			->leftJoin('article_category ac', 'a.category_id=ac.id')
			->where($where)->order('a.id', 'DESC')
			->field("a.id, a.title, a.type, a.add_time, ac.name as category_name")->paginate(['list_rows'=>10, 'query'=>request()->param()]);
		$categories = Db::name('article_category')->where('status', 1)->select();
		$miniprogram = Db::name('miniprogram')->where('id', $miniprogram_id)->find();
		$hidden = Db::name('miniprogram_article_hidden')->where('miniprogram_id', $miniprogram_id)->column('article_id');
		$hidden = implode(',', $hidden);
		setViewAssign(compact('id', 'keyword', 'category_id', 'type', 'miniprogram_id'));
		setViewAssign(compact('categories', 'miniprogram', 'hidden'));
		return success($rs);
	}
	
	//上传到OSS
	public function oss() {
		$id = $this->request->get('id', 0);
		$type = $this->request->get('type');
		$url = $this->request->get('url', '', 'url');
		$bucket = $this->request->get('bucket');
		if ($id<=0) return error('缺少数据');
		if (!Db::name('article')->where('id', $id)->count()) return error('记录不存在');
		$target = "{$this->front_domain}/article/{$this->admin_id}/{$id}";
		$self = NULL;
		if ($type=='self') {
			$self = ['url'=>$url, 'path'=>"/t/{$this->admin_id}", 'file'=>$id, 'get_url'=>"{$url}/article/{$this->admin_id}/{$id}", 'use_bucket'=>0];
		} else if ($type=='jingyuxitong') {
			$target = "{$this->front_domain}/article/js/{$this->admin_id}/{$id}";
			$self = ['self_url'=>1];
		}
		$model = m($this->bucket_type);
		$parasitic = $model->upload("article/{$this->admin_id}/{$id}", $target, $type, $self, $bucket);
		return success($parasitic);
	}
	
	//创建储存桶
	public function createBucket() {
		$bucket = $this->request->post('bucket');
		if (!strlen($bucket)) return error('缺少储存桶名称');
		//if (Db::name('bucket')->where("type='{$this->bucket_type}' AND bucket='{$bucket}'")->exist()) return error('储存桶已存在');
		$model = m($this->bucket_type);
		$model->createBucket($bucket);
		return success('ok');
	}
	
	//上传文件到储存桶
	public function putObject() {
		$bucket = $this->request->get('bucket');
		$res = $this->request->file('file', 'filename', 0, true, array('txt'));
		$model = m($this->bucket_type);
		$model->putObject($bucket, $res['name'], file_get_contents($res['tmp_name']));
		return success('ok');
	}
	
	//设置储存桶mobile.js
	public function setMobileJS() {
		if (!IS_POST) return error();
		$IS_LOCAL = true;
		if ($IS_LOCAL) {
			$content = file_get_contents(PUBLIC_PATH.'/js/mobile.js');
		} else {
			$content = file_get_contents(ROOT_PATH.'/console/bucket/js/mobile.js');
		}
		$article = file_get_contents(APPLICATION_PATH.'/api/controller/article.php');
		if (strpos($content, '//location.href') === false) {
			$content = str_replace('location.href', '//location.href', $content);
			$article = str_replace('if (!$this->is_wx) error404();', '//if (!$this->is_wx) error404();', $article);
			$msg = 'PC可打开';
		} else {
			$content = str_replace('//location.href', 'location.href', $content);
			$article = str_replace('//if (!$this->is_wx) error404();', 'if (!$this->is_wx) error404();', $article);
			$msg = 'PC禁止打开';
		}
		file_put_contents(APPLICATION_PATH.'/api/controller/article.php', $article);
		if ($IS_LOCAL) {
			file_put_contents(PUBLIC_PATH.'/js/mobile.js', $content);
		} else {
			file_put_contents(ROOT_PATH.'/console/bucket/js/mobile.js', $content);
			$options = json_decode($this->bucket_type=='oss' ? OSS_OPTIONS : COS_OPTIONS, true);
			$model = m($this->bucket_type);
			$model->putObject($options['bucket'], 'js/mobile.js', $content);
		}
		return success($msg);
	}
	
	//更改储存桶服务商
	public function setBucketType() {
		if (!IS_POST) return error();
		$content = file_get_contents(APPLICATION_PATH.'/config.php');
		if ($this->bucket_type == 'oss') {
			$cos_options = json_decode(COS_OPTIONS, true);
			$content = preg_replace("/'bucket_type' => '[^']+'/", "'bucket_type' => 'cos'", $content);
			$content = preg_replace("/'static_domain' => '[^']+'/", "'static_domain' => 'https://".$cos_options['bucket']."-".$cos_options['appId'].".cos.".$cos_options['region'].".myqcloud.com'", $content);
		} else {
			$oss_options = json_decode(OSS_OPTIONS, true);
			$endpoint = explode('oss-cn-', $oss_options['endpoint']);
			$content = preg_replace("/'bucket_type' => '[^']+'/", "'bucket_type' => 'oss'", $content);
			$content = preg_replace("/'static_domain' => '[^']+'/", "'static_domain' => '".$endpoint[0].$oss_options['bucket'].".oss-cn-".$endpoint[1]."'", $content);
		}
		file_put_contents(APPLICATION_PATH.'/config.php', $content);
		return success('ok');
	}
	
	//设置储存桶参数
	public function setBucketData() {
		$bucket = $this->request->post('bucket');
		$content = file_get_contents(APPLICATION_PATH.'/config.php');
		if ($this->bucket_type == 'oss') {
			$accessKeyId = $this->request->post('accessKeyId');
			$accessKeySecret = $this->request->post('accessKeySecret');
			$endpoint = $this->request->post('endpoint');
			if (!strlen($accessKeyId) || !strlen($accessKeySecret) || !strlen($endpoint) || !strlen($bucket)) return error('缺少参数');
			$content = preg_replace("/'oss_options' => \[[\s\S]*?]/", "'oss_options' => [
		'accessKeyId' => '".substr($accessKeyId, 0, 12)."'.'".substr($accessKeyId, 12)."',
		'accessKeySecret' => '".substr($accessKeySecret, 0, 15)."'.'".substr($accessKeySecret, 15)."',
		'endpoint' => '{$endpoint}',
		'bucket' => '{$bucket}'
	]", $content);
			$endpoint = explode('oss-cn-', $endpoint);
			$content = preg_replace("/'static_domain' => '.*?'/", "'static_domain' => '{$endpoint[0]}{$bucket}.oss-cn-{$endpoint[1]}'", $content);
		} else {
			$appId = $this->request->post('appId');
			$secretId = $this->request->post('secretId');
			$secretKey = $this->request->post('secretKey');
			$region = $this->request->post('region');
			if (!strlen($appId) || !strlen($secretId) || !strlen($secretKey) || !strlen($region) || !strlen($bucket)) return error('缺少参数');
			$content = preg_replace("/'cos_options' => \[[\s\S]*?]/", "'cos_options' => [
		'appId' => '".substr($appId, 0, 5)."'.'".substr($appId, 5)."',
		'secretId' => '".substr($secretId, 0, 18)."'.'".substr($secretId, 18)."',
		'secretKey' => '".substr($secretKey, 0, 16)."'.'".substr($secretKey, 16)."',
		'region' => '{$region}',
		'bucket' => '{$bucket}'
	]", $content);
			$content = preg_replace("/'static_domain' => '.*?'/", "'static_domain' => 'https://{$bucket}-{$appId}.cos.{$region}.myqcloud.com'", $content);
		}
		file_put_contents(APPLICATION_PATH.'/config.php', $content);
		return success('ok');
	}
}
