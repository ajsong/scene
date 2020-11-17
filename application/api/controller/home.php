<?php
class home extends core {

	public function __construct() {
		parent::__construct();
	}

	//首页接口
	public function index() {
		if (!$this->_check_login()) success('ok', 'about.html');
		$click = 0;
		$scene = SQL::share('member_scene')->where("member_id='{$this->member_id}'")->sort('id DESC')->find("*, '' as url");
		$count = count($scene);
		if ($scene) {
			foreach ($scene as $g) {
				$g->url = "{$this->domain}/v/{$g->code}";
				$click += $g->click;
			}
		}
		success(compact('click', 'count', 'scene'));
	}
	
	public function mario() {
//		$data = json_decode(file_get_contents(ROOT_PATH.'/console/content.json'), true);
//		foreach ($data as $k=>$d) {
//			$data[$k]['pic'] = $this->_getFile($d['pic']);
//		}
//		SQL::share('article3')->insert($data);
		exit('OK');
	}
	private function _getFile($url, $type='') {
		global $upload_type;
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
				if (strpos($url, 'wx_fmt=')!==false) $suffix = substr($url, strrpos($url, 'wx_fmt=')+7);
				if (!$suffix) $suffix = substr($url, strrpos($url, '.')+1);
				if (!preg_match('/^(jpe?g|png|gif|bmp)$/', $suffix)) $suffix = 'jpg';
				if ($suffix=='jpeg') $suffix = 'jpg';
				//$timeout = (preg_match('/^(jpe?g|png)$/', $suffix) ? 5 : 60*5);
				break;
		}
		$ch = curl_init();
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
		if ($type=='video') {
			$name = generate_sn();
			$dir = UPLOAD_PATH.'/video/'.date('Y').'/'.date('m').'/'.date('d');
			$upload = p('upload', $upload_type);
			$result = $upload->upload($content, NULL, str_replace('/public/', '/', $dir), $name, $suffix);
			$file = $result['file'];
		} else {
			$file = upload_obj_file($content, 'article', NULL, 1, false, ['jpg', 'jpeg', 'png', 'gif', 'bmp'], ".{$suffix}");
		}
		$file = add_domain($file);
		return $file;
	}
	
	//修改密码
	public function password() {
		if (IS_POST) {
			$origin_password = $this->request->post('origin_password');
			$password = $this->request->post('password');
			if (!strlen($origin_password)) error('原密码不能为空');
			if (!strlen($password)) error('新密码不能为空');
			$member = SQL::share('member')->where($this->member_id)->row('password, salt');
			if ($member) {
				if ($member->password!=crypt_password($origin_password, $member->salt)) error('原密码错误');
				$salt = generate_salt();
				$crypt_password = crypt_password($password, $salt);
				SQL::share('member')->where($this->member_id)->update(array('password'=>$crypt_password, 'origin_password'=>$password, 'salt'=>$salt));
			}
		}
		success('ok');
	}
	
	//更改头像
	public function avatar() {
		$avatar = $this->request->file('avatar', 'filename');
		if (!strlen($avatar)) error('上传接口出错');
		SQL::share('member')->where($this->member_id)->update(array('avatar'=>$avatar));
		success($avatar);
	}
	
	//登录
	public function login() {
		if ($this->_check_login()) location('/index/home');
		success($this->configs);
	}
	
	//退出
	public function logout() {
		session_unset();
		if (isset($_SESSION['member'])) unset($_SESSION['member']);
		if ($this->member_id>0) {
			$this->member_id = 0;
			$this->cookieAccount('member_token', $this->member_name, NULL);
		}
		$referer = $this->referer;
		if (!strlen($referer)) $referer = '/';
		location($referer);
	}
	
	//跳转随机网址
	public function url() {
		$url = array(
			'http://www.baidu.com',
			'http://www.apple.com.cn',
			'http://www.163.com',
			'http://www.aliyun.com',
			'http://www.alipay.com',
			'http://www.taobao.com',
			'http://mp.weixin.qq.com',
			'https://gitee.com',
			'http://www.sina.com.cn',
			'http://www.qq.com',
			'http://www.360.cn',
			'http://www.cnblogs.com',
			'http://www.csdn.net'
		);
		$domain = $url[mt_rand(0, count($url)-1)];
		location($domain);
	}
	
	//微信用户标签
	public function wxtag() {
		$type = $this->request->get('type');
		if (strlen($type)) {
			$appId = $this->request->post('app');
			if (!strlen($appId)) error('请选择公众号');
			$wechat = SQL::share('wechat')->where("appid='{$appId}'")->row();
			if (!$wechat) error('该公众号不存在');
			if ($wechat->appsecret && strlen($wechat->appsecret)) {
				$jssdk = new wechatCallbackAPI($appId, $wechat->appsecret);
				$access_token = $jssdk->getAccessToken("manual/access_token.{$appId}.json");
			} else {
				$wxapi = new wechatCallbackAPI();
				$json = $wxapi->authorizer_access_token('', $appId);
				$access_token = $json['authorizer_access_token'];
			}
			if (!strlen($access_token)) error('获取 access_token 失败');
			$res = NULL;
			switch ($type) {
				case 'tags':
					$res = requestData('get', "https://api.weixin.qq.com/cgi-bin/tags/get?access_token={$access_token}", NULL, true);
					if (isset($res['tags'])) $res = ['tags'=>$res['tags'], 'default'=>$wechat->tag];
					break;
				case 'create':
					$name = $this->request->post('name');
					if (!strlen($name)) error('请填写要添加的标签名称');
					$data = ['tag'=>['name'=>$name]];
					$res = requestData('post', "https://api.weixin.qq.com/cgi-bin/tags/create?access_token={$access_token}", $data, true, true);
					break;
				case 'update':
					$id = $this->request->post('id', 0);
					$name = $this->request->post('name');
					if ($id<=0) error('请选择要更新的标签');
					if (!strlen($name)) error('请填写要更新的标签名称');
					$data = ['tag'=>['id'=>$id, 'name'=>$name]];
					$res = requestData('post', "https://api.weixin.qq.com/cgi-bin/tags/update?access_token={$access_token}", $data, true, true);
					break;
				case 'delete':
					$id = $this->request->post('id', 0);
					if ($id<=0) error('请选择要删除的标签');
					$data = ['tag'=>['id'=>$id]];
					$res = requestData('post', "https://api.weixin.qq.com/cgi-bin/tags/delete?access_token={$access_token}", $data, true, true);
					break;
				case 'default':
					$id = $this->request->post('id', 0);
					if ($id<=0) error('请选择要设为默认的标签');
					$res = SQL::share('wechat')->where("appid='{$appId}'")->update(['tag'=>$id]);
					break;
			}
			if (!$res) error('数据错误');
			if (is_array($res) && isset($res['errcode']) && intval($res['errcode'])!=0) {
				if (strpos($res['errmsg'], 'api unauthorized')!==false) error('该公众号没有权限');
				error($res['errmsg']);
			}
			success($res);
		}
		$rs = SQL::share('wechat')->find();
		$this->smarty->clearCache('home.wxtag.html');
		success($rs);
	}
	
	//增加公众号
	public function addWechat() {
		$name = $this->request->post('name');
		$appid = $this->request->post('appid');
		$appsecret = $this->request->post('appsecret');
		$token = $this->request->post('token');
		if (!strlen($name) || !strlen($appid) || !strlen($appsecret) || !strlen($token)) error('所有项都必须填写');
		if (SQL::share('wechat')->where("appid='{$appid}'")->exist()) error('该公众号已存在');
		SQL::share('wechat')->insert(['name'=>$name, 'appid'=>$appid, 'appsecret'=>$appsecret, 'token'=>$token]);
		success('ok');
	}
	
	//删除公众号
	public function deleteWechat() {
		$appid = $this->request->post('appid');
		if (!strlen($appid)) error('请选择需要删除的公众号');
		SQL::share('wechat')->delete(['appid'=>"='{$appid}'"]);
		success('ok');
	}
	
	//特殊文章
	public function article3() {
		$url = SQL::share('client')->value('mini_qrcode');
		$flashes = SQL::share('article3')->where("category_id=1")->sort('sort ASC, id DESC')->find('id, title, pic');
		$list = SQL::share('article3')->where("category_id=0")->sort('sort ASC, id DESC')->find('id, title, pic, clicks');
		if ($list) {
			foreach ($list as $g) {
				$g->clicks = $this->_changeNum($g->clicks);
			}
		}
		$this->smarty->assign('WEB_TITLE', '平台禁播！冒险揭露！马上删除！');
		success(compact('url', 'flashes', 'list'));
	}
	private function _changeNum($num) {
		if (!is_numeric($num)) return 0;
		if ($num > 10000) return number_format($num/10000, 1, '.', '').'w+';
		if ($num > 1000) return number_format($num/1000, 1, '.', '').'k+';
		return $num;
	}
	
	//举报
	public function report() {
		if (IS_POST) {
			$type = $this->request->post('type');
			$content = $this->request->post('content');
			SQL::share('feedback')->insert(array('name'=>$content, 'content'=>$type, 'mobile'=>'article3', 'ip'=>$this->ip, 'add_time'=>time()));
			script('感谢您的反馈，我们将尽快处理', '/index/home/article3');
		}
		$types = ['欺诈', '色情', '政治谣言', '常识性谣言', '诱导分享', '恶意营销', '私隐信息收集', '抄袭公众号文章', '其他侵权类（冒名、诽谤、抄袭）', '违规声明原创'];
		$maxlength = 50;
		success(compact('types', 'maxlength'));
	}
}
