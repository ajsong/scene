<?php
namespace app\index\controller;

use think\facade\View;
use think\facade\Db;
use app\Kernel;
use think\Request;

class Core extends Kernel
{
	public $function, $edition;
	public $session_id;
	public $longitude; //经度,113.440685
	public $latitude; //纬度,23.136588
	public $member_id, $member_name, $shop_id, $sign;
	public $memberObj = NULL;
	
	public function __construct() {
		parent::__construct();
		
		//获取系统功能版本
		if (self::$client==NULL) {
			if (session('?client')) {
				self::$client = session('client');
			} else {
				self::$client = Db::name('client')->where('id', 1)->cache(60*60*24*7)->find();
				session('client', self::$client);
			}
		}
		$this->edition = intval(self::$client['edition']);
		$this->function = array();
		if (strlen(self::$client['function'])) $this->function = explode(',', self::$client['function']);
		
		$this->setConfigs();
		
		$session_id = session_id();
		//解决小程序每次请求的session_id都不一样
		//if ($this->is_mini && isset($this->headers['Session-id'])) $session_id = $this->headers['Session-id'];
		$this->session_id = $session_id;
		$this->member_id = 0;
		$this->member_name = '';
		$this->shop_id = 0;
		$this->sign = $this->request->get('sign'); //优先
		if (!strlen($this->sign)) $this->sign = $this->get_headers('sign');
		$reseller_id = $this->request->get('reseller');
		if (strlen($reseller_id)) session('reseller_id', $reseller_id);
		
		//if (!strlen($this->sign) && $this->is_mario) $this->autoLoginFirst();
		if (strlen($this->sign)) $this->_check_login();
		if (session('?member')) {
			$this->member_id = session('member.id');
			$this->member_name = session('member.name');
			$this->shop_id = session('member.shop_id');
			$this->sign = session('member.sign');
		}
		if ($this->member_id<=0) {
			if ($this->app) {
				$not_check_login = json_decode(NOT_CHECK_LOGIN, true);
				$is_login = false;
				if ( !$is_login && $this->is_wap && is_array($not_check_login) && isset($not_check_login['wap']) && count($not_check_login['wap']) ) {
					if (isset($not_check_login['global']) && count($not_check_login['global'])) $not_check_login['wap'] = array_merge($not_check_login['global'], $not_check_login['wap']);
					if ( !isset($not_check_login['wap'][$this->app]) ) {
						$this->check_login();
					} else {
						if ( !in_array('*', $not_check_login['wap'][$this->app]) && !in_array($this->act, $not_check_login['wap'][$this->app]) ) {
							$this->check_login();
						} else if ( isset($this->headers['Authorization']) && strlen($this->headers['Authorization']) ) {
							$this->check_login();
						}
					}
					$is_login = true;
				}
				if ( !$is_login && $this->is_web && is_array($not_check_login) && isset($not_check_login['web']) && count($not_check_login['web']) ) {
					if (isset($not_check_login['global']) && count($not_check_login['global'])) $not_check_login['web'] = array_merge($not_check_login['global'], $not_check_login['web']);
					if ( !isset($not_check_login['web'][$this->app]) ) {
						$this->check_login();
					} else {
						if ( !in_array('*', $not_check_login['web'][$this->app]) && !in_array($this->act, $not_check_login['web'][$this->app]) ) {
							$this->check_login();
						} else if ( isset($this->headers['Authorization']) && strlen($this->headers['Authorization']) ) {
							$this->check_login();
						}
					}
					$is_login = true;
				}
				if ( !$is_login && $this->is_mini && is_array($not_check_login) && isset($not_check_login['mini']) && count($not_check_login['mini']) ) {
					if (isset($not_check_login['global']) && count($not_check_login['global'])) $not_check_login['mini'] = array_merge($not_check_login['global'], $not_check_login['mini']);
					if ( !isset($not_check_login['mini'][$this->app]) ) {
						$this->check_login();
					} else {
						if ( !in_array('*', $not_check_login['mini'][$this->app]) && !in_array($this->act, $not_check_login['mini'][$this->app]) ) {
							$this->check_login();
						} else if ( isset($this->headers['Authorization']) && strlen($this->headers['Authorization']) ) {
							$this->check_login();
						}
					}
					$is_login = true;
				}
				if ( !$is_login && is_array($not_check_login) && isset($not_check_login['global']) ) {
					if ( !isset($not_check_login['global'][$this->app]) ) {
						$this->check_login();
					} else {
						if ( !in_array('*', $not_check_login['global'][$this->app]) && !in_array($this->act, $not_check_login['global'][$this->app]) ) {
							$this->check_login();
						} else if ( isset($this->headers['Authorization']) && strlen($this->headers['Authorization']) ) {
							$this->check_login();
						}
					}
				}
			}
		}
	}
	
	//get member info from sign
	public function get_member_from_sign($sign, $is_session=false) {
		if (!strlen($sign)) return false;
		if ($this->memberObj==NULL || $is_session) {
			$member = Db::name('member')->where('sign', $sign)->field('*, 0 as shop_id, NULL as shop, NULL as grade')->find();
			if (!$member) {
				if (ALONE_LOGIN==0 && session('?member')) {
					$member = session('member');
				} else {
					if ($is_session) {
						return error('该账号已在其他设备登录', -9);
					}
					return false;
				}
			}
			if (in_array('shop', $this->function)) {
				$shop = Db::name('shop')->alias('s')->leftJoin('member m', 's.member_id=m.id')->where('m.id', $member['function'])->field('s.*')->find();
				if ($shop) {
					$member['shop_id'] = $shop['id'];
					$member['shop'] = $shop;
				}
			}
			if (in_array('grade', $this->function)) {
				$grade = Db::name('grade')->where('id', $member['grade_id'])->find();
				if ($grade) {
					$member['grade'] = $grade;
				}
			}
			$member['total_price'] = strval($member['money']+$member['commission']); //总财富
			$this->member_id = $member['id'];
			$this->member_name = $member['name'];
			$this->shop_id = $member['shop_id'];
			$this->sign = $member['sign'];
			$member = add_domain_deep($member, array('avatar', 'pic'));
			unset($member['origin_password']);
			session('member', $member);
			$this->memberObj = $member;
		} else {
			$member = $this->memberObj;
			$this->member_id = $member['id'];
			$this->member_name = $member['name'];
			$this->shop_id = $member['shop_id'];
			$this->sign = $member['sign'];
		}
		return $this->memberObj;
	}
	
	//是否登录
	public function _check_login() {
		if (session('?member') && intval(session('member')['id'])>0 && !strlen($this->sign)) {
			return $this->get_member_from_sign(session('member')['sign'], true);
		} else if (strlen($this->sign)) {
			return $this->get_member_from_sign($this->sign);
		}else if (cookie('?member_name') && cookie('?member_token')) {
			$member = $this->cookieAccount('member_token', cookie('member_name'), cookie('member_token'), 'sign');
			if ($member) return $this->get_member_from_sign($member['sign']);
		} else if (WX_LOGIN && strlen(WX_APPID) && strlen(WX_SECRET) && IS_WAP && $this->is_wx && !$this->is_mini) {
			if ($this->weixin_authed()) return true;
			$this->weixin_auth();
		} else if (isset($this->headers['Authorization']) && strlen($this->headers['Authorization'])) {
			if (strpos(strtolower($this->headers['Authorization']), 'basic') !== false) {
				$sign = base64_decode(substr($this->headers['Authorization'], 6));
				if (strlen($sign)) return $this->get_member_from_sign($sign);
			}
		}
		return false;
	}
	
	//对是否登录函数的封装，如果登录了，则返回true，
	//否则，返回错误信息：-100，APP需检查此返回值，判断是否需要重新登录
	public function check_login(){
		if ($this->_check_login()) {
			return true;
		} else {
			$gourl = preg_replace('/^\/index\.php\//', '/', $_SERVER['PHP_SELF']).(strlen($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:'');
			$gourl = str_replace('/&', '/?', preg_replace('/^\/index\.php\?s=\/?/', '/', $gourl));
			$gourl = preg_replace('/^\/\?s=(.*)$/', '$1', $gourl);
			$gourl = preg_replace('/^\/(\w+)&/', '/$1?', $gourl);
			$gourl = preg_replace('/^\/index\.php$/', '/', $gourl);
			$_SESSION['api_gourl'] = $gourl;
			return error('请登录', -100);
		}
	}
	
	//微信提示
	public function weixin_warning() {
		$this->weixin_html('请在微信客户端打开链接', 'weui_icon_info', false);
	}
	public function weixin_success($str, $btn_str='关闭窗口', $goto='') {
		$this->weixin_html($str, 'weui_icon_success', true, $btn_str, $goto);
	}
	public function weixin_error($str, $btn_str='关闭窗口', $goto='') {
		$this->weixin_html($str, 'weui_icon_warn', true, $btn_str, $goto);
	}
	public function weixin_html($str, $icon_class='', $show_btn=true, $btn_str='关闭窗口', $goto='') {
		if (!strlen($icon_class)) $icon_class = 'weui_icon_success';
		$html = '<title>'.$str.'</title><meta charset="utf-8">
			<meta name="viewport" content="width=320,minimum-scale=1.0,maximum-scale=1.0,initial-scale=1.0,user-scalable=no">
			<meta name="format-detection" content="telephone=no">
			<meta name="format-detection" content="email=no">
			<meta name="format-detection" content="address=no">
			<link rel="stylesheet" type="text/css" href="/css/mobile.css">
			<link rel="stylesheet" type="text/css" href="/css/weui.css">
			<style>body{background:#f3f3f3;}.weui-btn{display:block;margin:0 auto;margin-bottom:15px;box-sizing:border-box;width:150px;line-height:40px;font-size:16px;text-align:center;text-decoration:none;color:#fff;border-radius:5px;-webkit-tap-highlight-color:rgba(0,0,0,0);overflow:hidden;background-color:#1aad19;}</style>
			<div class="weui_msg"><div class="weui_icon_area"><i class="'.$icon_class.' weui_icon_msg"></i></div>
			<div class="weui_text_area"><h4 class="weui_msg_title">'.$str.'</h4></div></div>';
		if ($show_btn || is_array($show_btn)) {
			$close = $this->is_wx ? "javascript:WeixinJSBridge.call('closeWindow')" : "javascript:window.close()";
			if (is_array($show_btn)) {
				foreach ($show_btn as $s) {
					$url = isset($s['url']) ? $s['url'] : $close;
					$title = isset($s['title']) ? $s['title'] : 'Button';
					$class = isset($s['class']) ? $s['class'] : '';
					$bgcolor = isset($s['bgcolor']) ? 'style="background-color:'.$s['bgcolor'].';"' : '';
					$html .= '<a href="'.$url.'" class="weui-btn '.$class.'" '.$bgcolor.'>'.$title.'</a>';
				}
			} else {
				if (!strlen($goto)) $goto = $close;
				$html .= '<a href="'.$goto.'" class="weui-btn">'.$btn_str.'</a>';
			}
		}
		exit($html);
	}
	
	//强制使用微信登录
	public function weixin_login() {
		if (!$this->weixin_authed(false)) {
			$this->weixin_auth();
		}
	}
	
	//是否已微信认证过, $is_wx_login_actions 开启某些方法微信登录
	public function weixin_authed($is_wx_login_actions=true) {
		if (isset($_SESSION['weixin_authed']) && intval($_SESSION['weixin_authed'])==1 && isset($_SESSION['weixin']) && isset($_SESSION['member'])) {
			return true;
		} else {
			if ($is_wx_login_actions) {
				if (in_array($this->act, array('weixin_auth', 'wx_login', 'get_wxcode'))) return true; //白名单
				$wx_login_actions = array(
					'home' => array('index')
				);
				if ( isset($wx_login_actions[$this->app]) ) {
					if ( in_array('*', $wx_login_actions[$this->app]) || in_array($this->act, $wx_login_actions[$this->app]) ) {
						return true;
					}
				}
			}
			return false;
		}
	}
	
	public function weixin_auth() {
		$appid = WX_APPID;
		$appsecrect = WX_SECRET;
		$wxapi = new wechatCallbackAPI();
		$userinfo = $this->request->post('userinfo', '', 'xg');
		if (!$userinfo) {
			$code = $this->request->get('code');
			$state = $this->request->get('state');
			//先获取code
			if (!strlen($code)) {
				$platform = $this->request->get('platform', 'STATE'); //调起授权的平台(外部网站的标识)
				$_SESSION['weixin_url'] = $_SERVER['REQUEST_URI']; //登录后跳转的目标网址
				if (isset($_GET['app']) && isset($_GET['act']) && $_GET['app']=='core' && $_GET['app']=='weixin_auth' && trim($_SERVER['HTTP_REFERER'])) {
					$_SESSION['weixin_url'] = $_SERVER['HTTP_REFERER'];
					if (!trim($_SESSION['weixin_url'])) $_SESSION['weixin_url'] = $_SERVER['REQUEST_URI'];
				}
				$redirect_url = urlencode(https().$_SERVER['HTTP_HOST']."/api.php?app=core&act=weixin_auth");
				$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_url}".
					"&response_type=code&scope=snsapi_userinfo&state={$platform}#wechat_redirect";
				location($url);
			}
			if (strlen($state) && $state!='STATE') {
				switch ($state) {
					case 'outlet':
						$url = "http://www.outlet.com/wx_authorization.php?code={$code}";
						location($url);
						break;
				}
			}
			//用户授权
			$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecrect}&code={$code}&grant_type=authorization_code";
			$html = file_get_contents($url);
			$json = json_decode($html);
			//echo json_encode($json);exit;
			if (isset($json->errcode) && intval($json->errcode)!=0) error($json->errmsg);
			$openid = $json->openid;
			$_SESSION['openid'] = $openid;
			//获取用户信息
			$json = $wxapi->get_userinfo($json->access_token, $json->openid);
		} else {
			$json = json_decode($userinfo, true);
			$openid = $json['openid'];
			$_SESSION['openid'] = $openid;
		}
		$json = $wxapi->wash_userinfo($json);
		$json = json_decode(json_encode($json));
		$_SESSION['weixin'] = $json;
		
		//$url = 'wap.php?app=login&act=mobile';
		$url = $this->request->session('weixin_url', 'wap.php');
		
		$member_id = SQL::share('member_thirdparty')->where("mark='{$openid}'")->value('member_id', 'intval');
		$member = SQL::share('member')->where($member_id)->row();
		if (!$member) {
			if (WX_REGISTER || $userinfo) {
				$reseller_id = $this->request->session('reseller_id', 0);
				/*
				if ($reseller_id<=0) { //扫微信的带参数二维码用
					$reseller = SQL::share('openid')->where("openid='{$openid}'")->row('id, openid, reseller_id');
					if ($reseller) $reseller_id = $reseller->reseller_id;
				}
				*/
				$wx_openid = $mini_openid = $openid;
				if ($userinfo) {
					$wx_openid = '';
				} else {
					$mini_openid = '';
				}
				$salt = generate_salt();
				$password = random_str(8);
				$crypt_password = crypt_password($password, $salt);
				$sign = generate_sign();
				$avatar = add_domain('%domain%'.getRemoteFile($json->headimgurl, 'avatar'));
				$member_id = SQL::share('member')->insert(array('password'=>$crypt_password, 'origin_password'=>$password, 'salt'=>$salt, 'real_name'=>$json->nickname,
					'nick_name'=>$json->nickname, 'sex'=>$json->sex, 'province'=>$json->province, 'city'=>$json->city, 'avatar'=>$avatar,
					'reg_time'=>time(), 'reg_ip'=>$this->ip, 'sign'=>$sign, 'session_id'=>$this->session_id,
					'last_time'=>time(), 'last_ip'=>$this->ip, 'logins'=>1, 'parent_id'=>$reseller_id, 'status'=>1, 'code'=>generate_sign()));
				if (strlen($wx_openid)) SQL::share('member_thirdparty')->insert(array('member_id'=>$member_id, 'type'=>'wechat', 'mark'=>$wx_openid));
				if (strlen($mini_openid)) SQL::share('member_thirdparty')->insert(array('member_id'=>$member_id, 'type'=>'mini', 'mark'=>$mini_openid));
				SQL::share('member')->where($member_id)->update(array('name'=>"user{$member_id}"));
				//生成新用户的邀请码
				$member_mod = m('member');
				$member_mod->new_invite_code($member_id);
				$member = SQL::share('member')->where($member_id)->row('*, 0 as total_price');
				$member->total_price = strval($member->money+$member->commission); //总财富
				//设置为最低等级
				if (in_array('grade', $this->function)) {
					$grade = SQL::share('grade')->where("status=1")->sort('score ASC, id ASC')->row('id, score');
					if ($grade) {
						if ($grade->id>0) SQL::share('member')->where($member_id)->update(array('grade_id'=>$grade->id, 'grade_time'=>time()));
						$member->grade_id = $grade->id;
						$member->grade_score = $grade->score;
					}
				}
				$_SESSION['member'] = $member;
				$_SESSION['weixin_authed'] = 1;
				$this->_check_login();
				if (!$userinfo) header("Location:{$url}");
			} else {
				$_POST = json_decode($json, true);
				$_POST['source'] = 'wechat';
				$_POST['hash'] = $openid;
				$_SESSION['member_temp'] = $_POST;
				header("Location:wap.php?app=login&act=mobile");
				//不绑定手机，直接去登录页面
				//header("Location:api.php?app=passport&act=login&openid={$openid}"); //增加openid参数是为了指定当前不是主动登录
			}
		} else {
			$sign = generate_sign();
			$member->total_price = strval($member->money+$member->commission); //总财富
			$member->sign = $sign;
			$member->logins += 1;
			$member->last_time = time();
			$member->last_ip = $this->ip;
			$data = array(
				'sign'=>$sign,
				//'nick_name'=>$json->nickname,
				//'avatar'=>$json->headimgurl,
				'sex'=>$json->sex,
				'province'=>$json->province,
				'city'=>$json->city,
				'logins'=>array('+1'),
				'last_time'=>time(),
				'last_ip'=>$this->ip
			);
			SQL::share('member')->where($member->id)->update($data);
			$_SESSION['member'] = $member;
			$_SESSION['weixin_authed'] = 1;
			$this->_check_login();
			if (strlen($member->mobile)) {
				$url = $this->request->session('weixin_url', 'wap.php');
			}
			if (!$userinfo) header("Location:{$url}");
		}
		if ($userinfo) success($member);
		exit;
	}
	
	//外部平台获取微信code
	public function get_wxcode() {
		$url = $this->request->get('url'); //外部平台返回地址,必传
		$appid = $this->request->get('appid');
		$is_pay = $this->request->get('is_pay', 0); //显示支付界面
		$order_sn = $this->request->get('order_sn');
		$total_price = $this->request->get('total_price');
		$order_body = $this->request->get('order_body');
		$code = $this->request->get('code');
		if (!strlen($url)) return;
		$wx_appid = strlen($appid) ? $appid : WX_APPID;
		if (!strlen($code)) {
			$url = base64_encode($url);
			$params = $is_pay ? "&is_pay={$is_pay}" : '';
			if ($is_pay) $params .= "&order_sn={$order_sn}&total_price={$total_price}&order_body=".urlencode($order_body);
			$redirect_url = urlencode(https().$_SERVER['HTTP_HOST']."/api.php?app=core&act=get_wxcode&appid={$appid}{$params}&url={$url}");
			$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$wx_appid}&redirect_uri={$redirect_url}".
				"&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
			location($url);
		}
		$url = base64_decode($url);
		if ($is_pay) {
			$this->_show_wxpay($order_sn, $total_price, $order_body, array('appid'=>$wx_appid));
		} else {
			$url .= strpos($url, '?')!==false ? "&code={$code}" : "?code={$code}";
			location($url);
		}
	}
	private function _show_wxpay($order_sn, $total_price, $order_body, $options=array()) {
		$other = o('other');
		$js = $other->jsPayHtml($order_sn, $total_price, $order_body, '', $options);
		$html = '<html style="font-size:100px;"><title>立即支付</title><meta charset="utf-8">
			<meta name="viewport" content="width=320,minimum-scale=1.0,maximum-scale=1.0,initial-scale=1.0,user-scalable=no">
			<script type="text/javascript" src="/js/jquery-3.4.1.min.js"></script>
			<style>body{text-align:center;font-size:0.14rem;-webkit-touch-callout:none;-webkit-user-select:none;}h2{margin-top:0.5rem;}
			a{display:block;width:1rem;height:0.4rem;line-height:0.4rem;text-decoration:none;margin:0 auto;margin-top:0.3rem;color:#fff;background:#53a046;border-radius:0.04rem;-webkit-tap-highlight-color:rgba(255,0,0,0);}</style>
			<h2>立即支付</h2>
			<h4>'.(strlen($order_body)?$order_body:'订单号 '.$order_sn).'</h4>
			<a href="javascript:callpay()">立即支付</a>'.$js;
		exit($html);
	}
	
	//获取第三方凭证,例如openid
	public function get_thirdparty($type='wechat') {
		if ($this->member_id<=0) return '';
		$party = SQL::share('member_thirdparty')->where("member_id='{$this->member_id}' AND type='{$type}'")->row('mark');
		if ($party) return $party->mark;
		return '';
	}
}
