<?php
namespace app\gm\controller;

use think\facade\View;
use think\facade\Db;
use app\Kernel;
use think\Request;

class Core extends Kernel
{
	public $function, $edition;
	public $dirname;
	public $defines;
	public $admin, $admin_id, $admin_name;
	
	public function __construct() {
		parent::__construct();
		if (!in_array($this->act, array('allowip'))) {
			if (!$this->is_allow_ip($this->ip)) {
				error404();
			}
		}
		
		//获取系统功能版本
		if (self::$client==NULL) {
			if (session('?client')) {
				self::$client = session('client');
			} else {
				self::$client = Db::name('client')->where('id', 1)->find();
				session('client', self::$client);
			}
		}
		$this->edition = intval(self::$client['edition']);
		$this->function = array();
		if (strlen(self::$client['function'])) $this->function = explode(',', self::$client['function']);
		View::assign('edition', $this->edition);
		View::assign('function', $this->function);
		View::assign('client', self::$client);
		
		//检测系统版本权限
		$editions = array();
		//需要检查权限的方法
		$need_check_edition_actions = array();
		if (session('?client_function')) {
			$actions = session('client_function');
		} else {
			$actions = Db::name('client_function')->field('value')->select();
			session('client_function', $actions);
		}
		if ($actions) {
			foreach ($actions as $action) {
				$need_check_edition_actions[$action['value']] = array('*');
			}
		}
		foreach($need_check_edition_actions as $app_name => $actions) {
			if ($this->app == $app_name) {
				foreach ($actions as $action) {
					if ($action == '*') {
						$rs = Db::name('menu')->where('app', $app_name)->cache(60*60*24*7)->field('edition')->select();
						if ($rs) {
							foreach ($rs as $g) {
								$edition = explode(',', $g['edition']);
								foreach ($edition as $e) {
									if (!in_array($e, $editions)) $editions[] = $e;
								}
							}
						}
					} else if ($this->act == $action) {
						$rs = Db::name('menu')->where(['app'=>$app_name, 'act'=>$action])->cache(60*60*24*7)->field('edition')->select();
						if ($rs) {
							foreach ($rs as $g) {
								$edition = explode(',', $g['edition']);
								foreach ($edition as $e) {
									if (!in_array($e, $editions)) $editions[] = $e;
								}
							}
						}
					}
				}
			}
		}
		$this->check_edition($editions);
		
		$this->setConfigs();
		View::assign('configs', $this->configs);
		
		//加载固定参数
		$WEB_DEFINE = Db::name('client_define')->where('id', 1)->cache(60*60*24*3)->find();
		unset($WEB_DEFINE['id']);
		unset($WEB_DEFINE['client_id']);
		$this->defines = $WEB_DEFINE;
		View::assign('defines', $this->defines);
		
		if (in_array($this->act, array('login'))) {
			$this->admin = NULL;
			$this->admin_id = 0;
			$this->admin_name = '';
		} else {
			$this->check_login();
		}
		
		//检测权限
		if (!($this->app=='core' || $this->app=='index')) {
			$this->permission($this->app, $this->act, $this->admin_id, false);
		}
		
		$menus = Db::name('menu')->where([
			['status', '=', 1],
			['parent_id', '<>', 0],
			['is_op', '=', 0],
		])->group('app')->order(['sort', 'id'=>'asc'])->cache(60*60*24*30)->column('app');
		if ($menus) {
			foreach ($menus as $app) {
				$key = "has_{$app}";
				$permission = $this->permission($app);
				$this->{$key} = $permission;
				VIew::assign($key, $permission);
			}
		}
		
		//添加允许访问的ip,登录后才能添加
		if (in_array($this->act, array('allowip'))) {
			$ip = $this->request->get('addip');
			if (strlen($ip)) {
				Db::name('allowip')->insert(array('ip'=>$ip, 'add_ip'=>$this->ip, 'add_time'=>time()));
				$message = "{$ip} added by {$this->ip} on ".date('Y-m-d H:i:s');
				write_log($message);
				echo $message;
				exit;
			}
		}
		
		$this->has_order = Db::query('SHOW TABLES LIKE "order"');
		View::assign('has_order', !!$this->has_order);
		
		//日志
		//$this->_handle_log();
		
		//菜单
		if (!in_array($this->act, array('login', 'logout'))) $this->_menu();
	}
	
	//是否允许登录的ip
	public function is_allow_ip($ip) {
		if ($ip=='127.0.0.1' || $ip=='::1') return true;
		return Db::name('allowip')->whereOr([
			['ip', '=', $ip],
			['ip', '=', '*']
		])->count() > 0;
	}
	
	//是否登录
	public function _check_login() {
		if (session('?admin') && intval(session('admin.id'))>0) {
			$admin = Db::name('admin')->where('id', session('admin.id'))->cache(60*60*24)->find();
			if ($admin) {
				$this->admin = $admin;
				$this->admin_id = $admin['id'];
				$this->admin_name = $admin['name'];
				session('admin', $admin);
				return true;
			}
		} else if (cookie('?admin_name') && cookie('?admin_token')) {
			$admin = $this->cookieAccount('admin_token', cookie('admin_name'), cookie('admin_token'));
			if ($admin) {
				$this->admin = $admin;
				$this->admin_id = $admin['id'];
				$this->admin_name = $admin['name'];
				session('admin', $admin);
				return true;
			}
		} else if (defined('WX_LOGIN_GM') && WX_LOGIN_GM && strlen(WX_APPID) && strlen(WX_SECRET) && $this->is_weixin()) {
			if ($this->weixin_authed()) return true;
			$this->weixin_auth();
		}
		return false;
	}
	
	//对是否登录函数的封装，如果登录了，则返回true，
	public function check_login() {
		if ($this->_check_login()) {
			return true;
		} else {
			$gourl = preg_replace('/^\/index\.php\/?/', '/', $_SERVER['PHP_SELF']).(strlen($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:'');
			$gourl = str_replace('/&', '/?', preg_replace('/^\/index\.php\?s=\/?/', '/', $gourl));
			$gourl = preg_replace('/^\/\?s=(.*)$/', '$1', $gourl);
			$gourl = preg_replace('/^\/(\w+)&/', '/$1?', $gourl);
			session('admin_gourl', $gourl);
			if (preg_match('/\/api\b/', $this->request->server('REQUEST_URI'))) return error('请重新登录', -100);
			redirect('/index/login')->send();
			return false;
		}
	}
	
	//检测权限,供ajax调用,例如 /core/checkPermission?application=power&action=edit
	public function checkPermission() {
		$app = $this->request->get('application');
		$act = $this->request->get('action');
		$permission = core::check_permission($app, $act);
		return success($permission ? 1 : 0);
	}
	//检测权限,供模板调用,例如 {if core::check_permission('power', 'edit')}
	public static function check_permission($app='', $act='') {
		if (!session('?admin')) return false;
		$act_where = '';
		if (strlen($act)) {
			$config = Db::name('op_config')->where('name', 'GLOBAL_IGNORE_PERMISSION_ACTS')->cache(60*60*24)->field('content')->find();
			if ($config) {
				$ignore = false;
				$ignore_permission_acts = explode(',', $config['content']);
				foreach ($ignore_permission_acts as $_act) {
					if (stripos($act, $_act)!==false) {
						$ignore = true;
						break;
					}
				}
				if (!$ignore) $act_where .= " AND (LOCATE(',{$act},', CONCAT(',',act,','))>0 OR act='*')";
			}
		}
		$admin_id = session('admin.id');
		$super = Db::name('admin')->where('id', $admin_id)->cache(60*60*24*7)->value('super');
		if ($super == 1) {
			$exist = Db::name('menu')->whereRaw("app='{$app}'{$act_where}")->cache(60*60*24)->count() > 0;
			return $exist;
		} else {
			$permission = Db::name('admin_permission')->whereRaw("app='{$app}'{$act_where} AND admin_id='{$admin_id}'")->count() > 0;
			if (!$permission) {
				$permission = Db::name('admin_menu')->alias('am')->leftJoin('menu m', 'am.menu_id=m.id')
					->whereRaw("app='{$app}'{$act_where} AND is_menu=1 AND admin_id='{$admin_id}'")->cache(60*60*24)->count() > 0;
			}
			return $permission;
		}
	}
	
	//检测权限
	public function permission($app='', $act='', $admin_id=0, $return=true) {
		if (!strlen($app)) $app = $this->app;
		if ($admin_id<=0) $admin_id = $this->admin_id;
		$exist = true;
		$act_where = '';
		if (strlen($act)) {
			$ignore = false;
			$ignore_permission_acts = explode(',', $this->configs['GLOBAL_IGNORE_PERMISSION_ACTS']);
			foreach ($ignore_permission_acts as $_act) {
				if (stripos($act, $_act)!==false) {
					$ignore = true;
					break;
				}
			}
			if (!$ignore) $act_where .= " AND (LOCATE(',{$act},', CONCAT(',',act,','))>0 OR act='*')";
		}
		$menu = true;
		$permission = true;
		$super = Db::name('admin')->where('id', $admin_id)->cache(60*60*24*7)->value('super');
		if ($super == 1) {
			$exist = Db::name('menu')->whereRaw("app='{$app}'{$act_where}")->cache(60*60*24)->count() > 0;
			if ($return) return $exist;
		} else {
			$permission = Db::name('admin_permission')->whereRaw("app='{$app}'{$act_where} AND admin_id='{$admin_id}'")->cache(60*60*24)->count() > 0;
			if (!$permission) {
				$menu = Db::name('admin_menu')->alias('am')->leftJoin('menu m', 'am.menu_id=m.id')->whereRaw("app='{$app}'{$act_where} AND is_menu=1 AND admin_id='{$admin_id}'")->cache(60*60*24)->count();
			}
		}
		if (!$exist || (!$permission && !$menu)) {
			if (!$return) return error('你没有权限，请联系超级管理员');
			return false;
		}
		return true;
	}
	
	//检测系统版本权限
	public function check_edition($editions) {
		if (is_numeric($editions)) $editions = "{$editions}";
		if (is_string($editions) && !strlen($editions)) return;
		if (is_string($editions)) $editions = explode(',', $editions);
		if (!count($editions)) return;
		$function = false;
		$client = $this->request->session('client', '', 'origin');
		if ($client) {
			$functions = explode(',', $client['function']);
			foreach ($functions as $f) {
				if (in_array($f, $editions)) {
					$function = true;
					break;
				}
			}
		}
		if (!in_array("{$this->edition}", $editions) && !$function) {
			error503();
		}
	}
	
	public static function hasMenu($app, $act='') {
		$where = "status=1 AND app REGEXP '{$app}(,|$)'";
		if (strlen($act)) $where .= " AND act REGEXP '{$act}(,|$)'"; //区分大小写，应该使用BINARY关键字，如 xxx REGEXP BINARY 'Hello.000'
		return Db::name('menu')->whereRaw($where)->count() > 0;
	}
	public function has_menu($app, $act='') {
		return core::hasMenu($app, $act);
	}
	private function _menu() {
		$nav = $this->menu();
		$nav_sub = 0;
		if ($nav) {
			foreach ($nav as $g) {
				if (strpos(','.$g['app'].',', ",{$this->app},") !== false) {
					if (isset($g['sub']) && count($g['sub'])) {
						$nav_sub = 1;
					}
					break;
				}
			}
		}
		#exit(json_encode($nav));
		View::assign('nav', $nav);
		View::assign('nav_sub', $nav_sub);
	}
	public function menu($admin_id='', $cache_time=60*60*24) {
		if (!strlen(strval($admin_id))) $admin_id = $this->admin_id;
		$super = intval(Db::name('admin')->where('id', $admin_id)->cache(60*60*24*7)->value('super'));
		$menu = Db::name('admin_menu')->where('admin_id', $admin_id)->cache($cache_time)->select()->toArray();
		$_super = intval(Db::name('admin')->where('id', $this->admin_id)->cache(60*60*24*7)->value('super'));
		$_menu = Db::name('admin_menu')->where('admin_id', $this->admin_id)->cache($cache_time)->select()->toArray();
		$first = Db::name('menu')->where([
			['parent_id', '=', 0],
			['status', '=', 1],
			['is_menu', '=', 1],
			['is_op', '=', 0],
		])->order(['sort', 'id'=>'asc'])->cache($cache_time)->select()->toArray();
		$second = Db::name('menu')->where([
			['parent_id', '>', 0],
			['status', '=', 1],
			['is_menu', '=', 1],
			['is_op', '=', 0],
		])->order(['sort', 'id'=>'asc'])->cache($cache_time)->select()->toArray();
		if ($first) {
			foreach ($first as $k => $g) {
				if (preg_match('/^[a-z,]+$/', $g['edition'])) {
					$nonShow = false;
					$editions = explode(',', $g['edition']);
					foreach ($editions as $edition) {
						if (!in_array($edition, $this->function)) {
							$nonShow = true;
							break;
						}
					}
					if ($nonShow) {
						unset($first[$k]);
						continue;
					}
				}
				if ($g['app'] == 'wechat' && ((defined('WX_TAKEOVER') && WX_TAKEOVER == 0) || (defined('WX_TOKEN') && !strlen(WX_TOKEN)) || (defined('WX_AESKEY') && !strlen(WX_AESKEY)))) {
					unset($first[$k]);
					continue;
				}
				$nav = array();
				if ($super == 1) {
					$first[$k]['checked'] = 'checked';
					if ($second) {
						foreach ($second as $i => $s) {
							if (preg_match('/^[a-z,]+$/', $s['edition'])) {
								$nonShow = false;
								$editions = explode(',', $s['edition']);
								foreach ($editions as $edition) {
									if (!in_array($edition, $this->function)) {
										$nonShow = true;
										break;
									}
								}
								if ($nonShow) {
									unset($second[$i]);
									continue;
								}
							}
							$second[$i]['checked'] = 'checked';
							if ($g['id'] == $s['parent_id']) {
								$nav[$i] = $second[$i];
							}
						}
					}
					if (count($nav)) $first[$k]['sub'] = $nav;
					continue;
				}
				$hasMenu = false;
				if ($_super == 1) {
					$hasMenu = true;
				} else if ($_menu) {
					foreach ($_menu as $m => $n) {
						if ($n['menu_id'] == $g['id']) {
							$hasMenu = true;
							break;
						}
					}
				}
				if (!$hasMenu) {
					unset($first[$k]);
					continue;
				}
				if ($menu) {
					foreach ($menu as $j => $d) {
						$hasMenu = false;
						if ($_super == 1) {
							$hasMenu = true;
						} else if ($_menu) {
							foreach ($_menu as $m => $n) {
								if ($n['menu_id'] == $g['id']) {
									$hasMenu = true;
									break;
								}
							}
						}
						if (!$hasMenu) {
							unset($first[$k]);
							continue;
						}
						if ($d['menu_id'] == $g['id']) {
							$first[$k]['checked'] = 'checked';
						}
					}
				}
				if ($second) {
					foreach ($second as $i => $s) {
						if (preg_match('/^[a-z,]+$/', $s['edition'])) {
							$nonShow = false;
							$editions = explode(',', $s['edition']);
							foreach ($editions as $edition) {
								if (!in_array($edition, $this->function)) {
									$nonShow = true;
									break;
								}
							}
							if ($nonShow) {
								unset($second[$i]);
								continue;
							}
						}
						$hasMenu = false;
						if ($_super == 1) {
							$hasMenu = true;
						} else if ($_menu) {
							foreach ($_menu as $m => $n) {
								if ($n['menu_id'] == $s['id']) {
									$hasMenu = true;
									break;
								}
							}
						}
						if (!$hasMenu) {
							unset($second[$i]);
							continue;
						}
						if ($menu) {
							foreach ($menu as $j => $d) {
								if ($d['menu_id'] == $s['id']) {
									$second[$i]['checked'] = 'checked';
									break;
								}
							}
						}
						if ($g['id'] == $s['parent_id']) {
							$nav[$i] = $second[$i];
						}
					}
				}
				if (count($nav)) $first[$k]['sub'] = $nav;
			}
			$first = array_values($first);
		}
		//exit(str_replace('<', '< ', json_encode($first, JSON_UNESCAPED_UNICODE)));
		return $first;
	}
	
	public function weixin_authed() {
		if (isset($_SESSION['openid']) && trim($_SESSION['openid']) && isset($_SESSION['weixin']) && isset($_SESSION['admin'])) {
			return true;
		} else {
			return false;
		}
	}
	public function weixin_auth() {
		$appid = WX_APPID;
		$appsecrect = WX_SECRET;
		$active = $this->request->get('active', 0);
		$code = $this->request->get('code');
		if ($active) {
			if (!isset($_SESSION['admin'])) error_tip('PLEASE LOGIN FIRST');
			$openid = $this->request->session('openid');
			if ($active && strlen($openid)) {
				Db::name('admin')->where('id', $this->admin_id)->update(array('openid'=>$openid));
				location('/#wxactive');
			}
		}
		//先获取code
		if ($code=='') {
			$_SESSION['wx_gourl'] = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
			$redirect_url = urlencode(https().$_SERVER['HTTP_HOST']."/core/weixin_auth");
			$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_url}".
				"&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
			//$url = "https://m.qfgyp.com/api.php?app=core&act=weixin_auth&platform=console";
			location($url);
		}
		//用户授权
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecrect}&code={$code}&grant_type=authorization_code";
		$html = file_get_contents($url);
		$json = json_decode($html);
		//echo json_encode($json);exit;
		if (isset($json->errcode) && intval($json->errcode)!=0) return error($json->errmsg);
		$openid = $json->openid;
		$_SESSION['openid'] = $openid;
		//获取用户信息
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$json = $wxapi->get_userinfo($json->access_token, $json->openid);
		$json = json_decode(json_encode($json));
		$_SESSION['weixin'] = $json;
		$admin = Db::name('admin')->where('openid', $openid)->find();
		if (!$admin) {
			location('/index/login');
		} else {
			$_SESSION['admin'] = $admin;
			$wx_gourl = $this->request->session('wx_gourl', '/');
			location($wx_gourl);
		}
	}
}
