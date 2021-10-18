<?php
namespace app;

use think\facade\Db;
use think\facade\Request;

if (!IS_AG && !IS_OP) {
	//固定参数定义
	$CONFIG = Db::name('client_define')->where('id', 1)->cache(60*60*24*3)->find();
	unset($CONFIG['id']);
	unset($CONFIG['client_id']);
	foreach ($CONFIG as $k => $g) {
		if (is_null($g)) $g = '';
		defined($k) or define($k, $g);
	}
}

class Kernel {
	public $baidu_ak;
	public $act, $app;
	public $request;
	public $referer;
	public $is_wx, $is_mini, $is_web, $is_wap, $is_ios, $is_android, $is_app, $is_mario;
	public $ua, $wx_ua, $now, $ip;
	public $is_gm, $is_ag, $is_op;
	public $domain, $front_domain;
	public $configs;
	public $headers;
	public static $client = NULL;
	public $client_id;
	
	public function __construct() {
		$configs = array();
		if (!IS_AG && !IS_OP) {
			//客户数据抽出为全局变量
			$config = Db::name('client')->where((defined('IS_SAAS') && IS_SAAS) ? $this->client_id : 1)->cache(60*60*24*3)->find();
			unsets($config, array('id', 'name', 'host', 'add_time'));
			foreach ($config as $k => $g) {
				if ($g == NULL) $g = '';
				if (in_array($k, array('push_fields', 'upload_fields', 'sms_fields'))) {
					$fields = explode('|', $g);
					foreach ($fields as $field) {
						$p = explode('：', $field);
						if (count($p)<=1) continue;
						$configs[$p[0]] = $p[1];
					}
				} else {
					$configs[$k] = $g;
				}
			}
		}
		$this->app = Request::controller(true);
		$this->act = Request::action(true);
		$this->baidu_ak = strlen($configs['baidu_ak']) ? $configs['baidu_ak'] : 'iaDZrNldobQVbG7L357j8fIPKxIj8A1i';
		$this->request = new \Request\Request();
		$this->referer = $this->request->server('HTTP_REFERER');
		$this->is_wx = defined('IS_WX') ? IS_WX : false;
		$this->is_mini = defined('IS_MINI') ? IS_MINI : false;
		$this->is_web = defined('IS_WEB') ? IS_WEB : false;
		$this->is_wap = defined('IS_WAP') ? IS_WAP : false;
		$this->ua = $this->request->server('HTTP_USER_AGENT');
		$this->wx_ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/8.0.5(0x18000527) NetType/WIFI Language/zh_CN';
		$this->is_ios = strlen($this->ua) ? (stripos($this->ua, 'iPhone') !== false || stripos($this->ua, 'iPad') !== false) : false;
		$this->is_android = strlen($this->ua) ? (stripos($this->ua, 'Android') !== false || stripos($this->ua, 'Linux') !== false) : false;
		$this->is_app = (defined('IS_APP') && IS_APP) || $this->request->request('source') == 'ios' || $this->request->request('source') == 'android';
		$this->is_mario = (strlen($this->ua) && stripos($this->ua, 'mario') !== false) || (intval(Request::param('mario/d')) == 1);
		$this->now = time();
		$this->ip = ip();
		$this->is_gm = defined('IS_GM') ? IS_GM : false;
		$this->is_ag = defined('IS_AG') ? IS_AG : false;
		$this->is_op = defined('IS_OP') ? IS_OP : false;
		/*if (!IS_API && $this->smarty) {
			$this->smarty->assign('is_gm', $this->is_gm);
			$this->smarty->assign('is_ag', $this->is_ag);
			$this->smarty->assign('is_op', $this->is_op);
		}*/
		$this->domain = Request::domain();
		$domain_bind = config('app.domain_bind');
		if (is_array($domain_bind)) {
			$apps = array_keys(config('app.domain_bind'));
			$this->front_domain = https() . array_shift($apps);
		} else {
			$this->front_domain = $this->domain;
		}
		$this->headers = $this->get_headers();
		$this->client_id = 0;
	}
	
	//获取主机头信息
	public function get_headers($key='') {
		return Request::header($key);
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
		$configs = array();
		//总config
		$config = Db::name('op_config')->cache(60*60*24*3)->field('name, content')->select();
		if ($config) {
			foreach ($config as $g) {
				$configs[$g['name']] = $g['content'];
			}
		}
		if (!IS_AG && !IS_OP) {
			//客户config
			$config = Db::name('config')->where((defined('IS_SAAS') && IS_SAAS) ? "client_id='{$this->client_id}'" : '')->cache(60*60*24*3)->field('name, content')->select();
			if ($config) {
				foreach ($config as $g) {
					$configs[$g['name']] = $g['content'];
				}
			}
		}
		$this->configs = $configs;
		session('configs', $configs);
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
		if (is_string($token)) {
			if (strlen($token)) {
				return Db::name($master)->alias('m')->leftJoin("{$table} t", 'm.name=t.name')->where(['t.name'=>$name, 't.token'=>$token])->cache(60*60*24*7)->field($field)->find();
			} else {
				$token = md5(uniqid(rand(), true));
				Db::name($table)->where('name', $name)->delete();
				Db::name($table)->insert(compact('name', 'token'));
				cookie("{$master}_name", $name, 60*60*24*365);
				cookie("{$master}_token", $token, 60*60*24*365);
			}
		} else {
			Db::name($table)->where('name', $name)->delete();
			cookie("{$master}_name", NULL);
			cookie("{$master}_token", NULL);
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
		$members = isset($options['members']) ? intval($options['members']) : -1; //指定发送会员,-1全部会员
		$content = $options['content'] ?? ''; //推送内容,站内消息内容
		$message_type = $options['message_type'] ?? ''; //站内消息类型
		$target = $options['target'] ?? ''; //点击站内消息的跳转目标
		$mobile = $options['mobile'] ?? ''; //手机号码,指定发送会员后该参数无效
		$udid = $options['udid'] ?? ''; //推送的UDID,设置后只推送消息
		$extends = isset($options['extends']) ? intval($options['extends']) : array(); //推送的扩展参数
		$sms = $options['sms'] ?? ''; //短信内容
		$sign = $options['sign'] ?? ''; //短信签名,视服务商要求而定
		$template_id = isset($options['template_id']) ? intval($options['template_id']) : 0; //短信模板id,视服务商要求而定
		$sms_only = $options['sms_only'] ?? false; //只发送短信
		if (is_numeric($members)) $members = array($members);
		foreach ($members as $member_id) {
			if ($member_id>0) {
				$member = Db::name('member')->where('id', $member_id)->field('udid, mobile')->find();
				if ($member) $mobile = $member['mobile'];
			} else if ($member_id<0 && session('?member')) {
				$member_id = session('member')['id'];
				if (!strlen($mobile)) $mobile = session('member')['mobile'];
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
				$where = [
					['status', '=', 1],
					['udid', '<>', '']
				];
				if ($member_id>0) {
					$where[] = ['id', '=', $member_id];
				} else if ($member_id<0) {
					if (!session('?member') || !session('member.id') || session('member.id')<=0) return;
					$where[] = ['id', '=', session('member.id')];
				}
				$member = Db::name('member')->where($where)->field('id, udid, badge')->find();
				if ($member) {
					foreach ($member as $g) {
						$badge = $g['badge'] + 1; //增加APP角标
						Db::name('member')->where('id', $g['id'])->update(array('badge'=>$badge));
						$extends = array_merge(array('badge'=>$badge), $extends);
						$this->put_notify($g['udid'], $message, $extends);
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
			$api->send($udid, $message, $extends, true);
		}
	}
	//插入短信表
	public function save_sms($mobile, $sms, $sms_id=0) {
		if ($sms_id>0) {
			return Db::name('sms')->where('id', $sms_id)->update(array('status'=>1));
		} else {
			return Db::name('sms')->insert(array('mobile'=>$mobile, 'content'=>$sms, 'ip'=>$this->ip, 'add_time'=>time(), 'status'=>0));
		}
	}
	//插入站内消息表
	public function send_message($content, $member_id, $type='', $target='') {
		$add_time = time();
		if ($member_id>0) {
			Db::name('message')->insert(compact('member_id', 'content', 'type', 'target', 'add_time'));
		} else if ($member_id==0) {
			$members = Db::name('member')->where('status', 1)->whereTime('last_time', '>', time()-60*60*24*30)->field('id')->select(); //一个月内登录过的才发送
			if ($members) {
				$member_id = array();
				foreach ($members as $g) {
					$member_id[] = $g['id'];
				}
				//Db::name('message')->insert(compact('member_id', 'content', 'type', 'target', 'add_time'), 'member_id');
			}
		}
	}
	
	//是否微信端打开
	public function is_weixin() {
		return IS_WX;
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
		$subjectArr = array();
		if (session("?{$subject}")) $subjectArr = session("{$subject}");
		$subjectArr[$property] = $value;
		session("{$subject}", $subjectArr);
	}
	//魔术方法，当获取的属性不存在或者不可访问(private)时就会调用此函数
	public function __get($name) {
		$array = explode('_', $name);
		if (count($array)<2) return NULL;
		$subject = $array[0];
		$property = substr($name, strlen($subject)+1);
		if (!session("?{$subject}")) return NULL;
		$subjectArr = session("{$subject}");
		return isset($subjectArr[$property]) ? $subjectArr[$property] : NULL;
	}
}
