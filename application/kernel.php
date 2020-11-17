<?php
class kernel {
	public $db;
	public $ezr;
	public $tbp;
	public $smarty;
	public $baidu_ak;
	public $act, $app;
	public $request;
	public $referer;
	public $is_wx, $is_mini, $is_web, $is_wap, $is_ios, $is_android, $is_app, $is_mario;
	public $ua, $now, $ip;
	public $is_gm, $is_ag, $is_op;
	public $domain, $img_domain;
	public $configs;
	public $headers;
	public static $client = NULL;
	public $client_id;
	
	public function __construct() {
		global $db, $ezr, $tbp, $smarty, $baidu_ak, $app, $act, $request, $img_domain, $client_id;
		$this->db = $db;
		$this->ezr = $ezr;
		$this->tbp = $tbp;
		$this->smarty = isset($smarty) ? $smarty : NULL;
		$this->baidu_ak = (isset($baidu_ak) && strlen($baidu_ak)) ? $baidu_ak : 'iaDZrNldobQVbG7L357j8fIPKxIj8A1i';
		$this->app = $app;
		$this->act = $act;
		$this->request = $request;
		$this->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$this->is_wx = (defined('IS_WX') && IS_WX) || (defined('IS_WX_TOOLS') && IS_WX_TOOLS);
		$this->is_mini = (strlen($this->referer) && strpos($this->referer, 'https://servicewechat.com/wx')!==false && $this->is_wx) ? 1 : 0;
		$this->is_web = defined('IS_WEB') ? IS_WEB : false;
		$this->is_wap = defined('IS_WAP') ? IS_WAP : false;
		$this->ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$this->is_ios = strlen($this->ua) ? (stripos($this->ua, 'iPhone')!==false || stripos($this->ua, 'iPad')!==false) : false;
		$this->is_android = strlen($this->ua) ? (stripos($this->ua, 'Android')!==false || stripos($this->ua, 'Linux')!==false) : false;
		$source = $this->request->request('source');
		$this->is_app = (defined('IS_APP') && IS_APP) || $source=='ios' || $source=='android';
		$mario = isset($_REQUEST['mario']) ? $_REQUEST['mario'] : 0;
		$this->is_mario = strlen($this->ua) ? stripos($this->ua, 'mario')!==false : ($mario==1);
		$this->now = time();
		$this->ip = ip();
		$this->is_gm = defined('IS_GM') ? IS_GM : false;
		$this->is_ag = defined('IS_AG') ? IS_AG : false;
		$this->is_op = defined('IS_OP') ? IS_OP : false;
		if (!IS_API && $this->smarty) {
			$this->smarty->assign('is_gm', $this->is_gm);
			$this->smarty->assign('is_ag', $this->is_ag);
			$this->smarty->assign('is_op', $this->is_op);
		}
		$this->domain = https().$_SERVER['HTTP_HOST'];
		if (strlen($img_domain)) {
			$this->img_domain = substr($img_domain, strlen($img_domain)-1)!='/' ? $img_domain : substr($img_domain, 0, strlen($img_domain)-1);
		} else {
			$this->img_domain = $this->domain;
		}
		$this->headers = $this->get_headers();
		$this->client_id = $client_id;
	}
	
	//获取主机头信息
	public function get_headers($key='') {
		return get_header($key);
	}
	
	//获取Authorization
	public function get_authorization() {
		$username = '';
		$password = '';
		if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) { //Apache服务器
			$username = $_SERVER['PHP_AUTH_USER'];
			$password = $_SERVER['PHP_AUTH_PW'];
		} else if(isset($_SERVER['HTTP_AUTHORIZATION']) && stripos($_SERVER['HTTP_AUTHORIZATION'], 'basic')!==false) { //其他服务器如 Nginx  Authorization
			$auth = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			$username = isset($auth[0]) ? $auth[0] : '';
			$password = isset($auth[1]) ? $auth[1] : '';
		}
		return array($username, $password);
	}
	
	//加载配置参数
	public function setConfigs() {
		global $WEB_CONFIGS;
		$this->configs = $WEB_CONFIGS;
	}
	
	//保存Referer
	public function setReferer() {
		$_SESSION['referer'] = $this->referer;
	}
	//获取Referer
	public function getReferer() {
		return $this->request->session('referer', '?');
	}
	
	//通过COOKIE获取账号资料,token为空字符串时插入记录,为NULL时删除记录, 需创建对应token表, 表_token,name:16,token:32
	//CREATE TABLE `ws_member_token` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(16) DEFAULT NULL, `token` varchar(32) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8
	public function cookieAccount($table, $name, $token='', $field='m.*') {
		$master = explode('_', $table);
		$master = $master[0];
		if (strlen($token)) {
			return SQL::share("{$master} m")->left("{$table} t", 'm.name=t.name')->where("t.name='{$name}' AND t.token='{$token}'")->cached(60*60*24*7)->row($field);
		} else if (is_string($token)) {
			$token = md5(uniqid(rand(), true));
			SQL::share($table)->delete("name='{$name}'");
			SQL::share($table)->insert(compact('name', 'token'));
			setcookie("{$master}_name", $name, time()+60*60*24*365, '/');
			setcookie("{$master}_token", $token, time()+60*60*24*365, '/');
		} else {
			SQL::share($table)->delete("name='{$name}'");
			setcookie("{$master}_name", NULL, time()-60*60*24*365, '/');
			setcookie("{$master}_token", NULL, time()-60*60*24*365, '/');
		}
		return NULL;
	}
	
	//消息推送
	public function send_notify($options=array()) {
		$this->notification($options);
	}
	//发送短信
	public function send_sms($options=array()) {
		$options['sms_only'] = true;
		$this->notification($options);
	}
	//站内消息、推送、短信，先保存到数据库，再发送
	public function notification($options=array()) {
		global $push_type, $sms_type;
		$members = $this->request->object('members', $options, -1); //指定发送会员,-1全部会员
		$content = $this->request->object('content', $options); //推送内容,站内消息内容
		$message_type = $this->request->object('message_type', $options); //站内消息类型
		$target = $this->request->object('target', $options); //点击站内消息的跳转目标
		$mobile = $this->request->object('mobile', $options); //手机号码,指定发送会员后该参数无效
		$udid = $this->request->object('udid', $options); //推送的UDID,设置后只推送消息
		$extends = $this->request->object('extends', $options, array()); //推送的扩展参数
		$sms = $this->request->object('sms', $options); //短信内容
		$sign = $this->request->object('sign', $options); //短信签名,视服务商要求而定
		$template_id = $this->request->object('template_id', $options, 0); //短信模板id,视服务商要求而定
		$sms_only = $this->request->object('sms_only', $options, false); //只发送短信
		if (is_numeric($members)) $members = array($members);
		foreach ($members as $member_id) {
			if ($member_id>0) {
				$member = SQL::share('member')->where($member_id)->row('udid, mobile');
				if ($member) $mobile = $member->mobile;
			} else if ($member_id<0 && isset($_SESSION['member'])) {
				$member_id = $_SESSION['member']->id;
				if (!strlen($mobile)) $mobile = $_SESSION['member']->mobile;
			}
			//插入消息表
			if (!strlen($udid) && !$sms_only) {
				if (strlen($message_type)) {
					$type = $message_type;
				} else {
					if (strpos($content, '商品') !== false) {
						$type = 'goods';
					} else if (strpos($content, '店铺') !== false) {
						$type = 'shop';
					} else if (strpos($content, '订单') !== false) {
						$type = 'order';
					} else {
						$type = 'html5';
					}
				}
				$this->send_message($content, $member_id, $type, $target);
			}
			//发送推送
			if (!$sms_only && strlen($content) && $push_type!='nopush') {
				$this->config_notify($member_id, $content, array_merge($extends, array('type'=>'message')), $udid);
			}
			//发送短信
			if (!strlen($udid) && (is_array($sms) || strlen($sms)) && strlen($mobile) && $sms_type!='nosms') {
				$sms_id = $this->save_sms($mobile, $sms);
				$api = p('sms', $sms_type);
				if ($api->send($mobile, $sms, $template_id, $sign)) $this->save_sms($mobile, $sms, $sms_id);
			}
		}
	}
	//推送操作
	public function config_notify($member_id, $message, $extends=array(), $udid='') {
		global $push_type;
		if ($push_type!='nopush') {
			if (!strlen($udid)) {
				$where = "status=1 AND udid!=''";
				if ($member_id>0) {
					$where .= " AND id='{$member_id}'";
				} else if ($member_id<0) {
					if (!isset($_SESSION['member']) || !isset($_SESSION['member']->id) || $_SESSION['member']->id<=0) return;
					$where .= " AND id='".$_SESSION['member']->id."'";
				}
				$member = SQL::share('member')->where($where)->find('id, udid, badge');
				if ($member) {
					foreach ($member as $g) {
						$badge = $g->badge + 1; //增加APP角标
						SQL::share('member')->where($g->id)->update(array('badge'=>$badge));
						$extends = array_merge(array('badge'=>$badge), $extends);
						$this->put_notify($g->udid, $message, $extends);
					}
				}
			} else {
				$this->put_notify($udid, $message, $extends);
			}
		}
	}
	public function put_notify($udid, $message, $extends=array()) {
		global $push_type;
		if ($push_type!='nopush') {
			$api = p('push', $push_type);
			$api->send($udid, $message, $extends, API_DEBUG?false:true);
		}
	}
	//插入短信表
	public function save_sms($mobile, $sms, $sms_id=0) {
		if ($sms_id>0) {
			return SQL::share('sms')->where($sms_id)->update(array('status'=>1));
		} else {
			return SQL::share('sms')->insert(array('mobile'=>$mobile, 'content'=>$sms, 'ip'=>$this->ip, 'add_time'=>time(), 'status'=>0));
		}
	}
	//插入站内消息表
	public function send_message($content, $member_id, $type='', $target='') {
		$add_time = time();
		if ($member_id>0) {
			SQL::share('message')->insert(compact('member_id', 'content', 'type', 'target', 'add_time'));
		} else if ($member_id==0) {
			$members = SQL::share('member')->where("status=1 AND last_time>'".(time()-60*60*24*30)."'")->find('id'); //一个月内登录过的才发送
			if ($members) {
				$member_id = array();
				foreach ($members as $k=>$g) {
					$member_id[] = $g->id;
				}
				SQL::share('message')->insert(compact('member_id', 'content', 'type', 'target', 'add_time'), 'member_id');
			}
		}
	}
	
	//是否微信端打开
	public function is_weixin() {
		if (isset($_SERVER['HTTP_USER_AGENT']) && (stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false || stripos($_SERVER['HTTP_USER_AGENT'], 'wechatdevtools')!==false)) {
			return true;
		} else {
			return false;
		}
	}
	
	//日志
	public function _handle_log() {
		if (!($this->app=='setting' && $this->act=='log')) {
			$log = m('log');
			$log->create();
		}
	}
	
	//动态设置/获取属性
	//魔术方法，当设置的属性不存在或者不可访问(private)时就会调用此函数
	public function __set($name, $value) {
		$array = explode('_', $name);
		if (count($array)<2) return;
		$subject = $array[0];
		$property = substr($name, strlen($subject)+1);
		if (!isset($_SESSION[$subject])) $_SESSION[$subject] = new stdClass();
		$_SESSION[$subject]->{$property} = $value;
	}
	//魔术方法，当获取的属性不存在或者不可访问(private)时就会调用此函数
	public function __get($name) {
		$array = explode('_', $name);
		if (count($array)<2) return NULL;
		$subject = $array[0];
		$property = substr($name, strlen($subject)+1);
		if (!isset($_SESSION[$subject])) return NULL;
		return isset($_SESSION[$subject]->{$property}) ? $_SESSION[$subject]->{$property} : NULL;
	}
}
