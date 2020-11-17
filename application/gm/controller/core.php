<?php
class core extends kernel {
	public $function, $edition;
	public $dirname;
	public $defines;
	public $admin, $admin_id, $admin_name;
	public $has_order;

	public function __construct() {
		global $IS_ALONE_DOMAIN, $WEB_DEFINE;
		parent::__construct();
		if (!in_array($this->act, array('allowip'))) {
			if (!$this->is_allow_ip($this->ip)) {
				error404();
			}
		}

		$this->dirname = '/' . ($IS_ALONE_DOMAIN ? '' : DIRNAME.'/');
		$this->smarty->assign('GM_PATH', $this->dirname);

		//获取系统功能版本
		if (self::$client==NULL) {
			if (isset($_SESSION['client'])) {
				self::$client = $_SESSION['client'];
			} else {
				self::$client = SQL::share('client')->row();
				$_SESSION['client'] = self::$client;
			}
		}
		$this->edition = intval(self::$client->edition);
		$this->function = array();
		if (strlen(self::$client->function)) $this->function = explode(',', self::$client->function);
		$this->smarty->assign('edition', $this->edition);
		$this->smarty->assign('function', $this->function);
		$this->smarty->assign('client', self::$client);

		//检测系统版本权限
		$editions = array();
		//需要检查权限的方法
		$need_check_edition_actions = array();
		if (isset($_SESSION['client_function'])) {
			$actions = $_SESSION['client_function'];
		} else {
			$actions = SQL::share('client_function')->find('value');
			$_SESSION['client_function'] = $actions;
		}
		if ($actions) {
			foreach ($actions as $action) {
				$need_check_edition_actions[$action->value] = array('*');
			}
		}
		foreach($need_check_edition_actions as $app_name=>$actions) {
			if ($this->app == $app_name) {
				foreach ($actions as $action) {
					if ($action == '*') {
						$rs = SQL::share('menu')->where("app='{$app_name}'")->cached(60*60*24*7)->find('edition');
						if ($rs) {
							foreach ($rs as $g) {
								$edition = explode(',', $g->edition);
								foreach ($edition as $e) {
									if (!in_array($e, $editions)) $editions[] = $e;
								}
							}
						}
					} else if ($this->act == $action) {
						$rs = SQL::share('menu')->where("app='{$app_name}' AND act='{$action}'")->cached(60*60*24*7)->find('edition');
						if ($rs) {
							foreach ($rs as $g) {
								$edition = explode(',', $g->edition);
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
		$this->smarty->assign('configs', $this->configs);

		//加载固定参数
		$this->defines = $WEB_DEFINE;
		$this->smarty->assign('defines', $this->defines);

		if (in_array($this->act, array('login'))) {
			$this->admin = NULL;
			$this->admin_id = 0;
			$this->admin_name = '';
		} else {
			$this->check_login();
		}

		//检测权限
		if (!($this->app=='core' || $this->app=='home')) {
			$this->permission($this->app, $this->act, $this->admin_id, false);
		}

		$menus = SQL::share('menu')->where("status=1 AND parent_id!=0 AND is_op=0")->group('app')->sort('sort ASC, id ASC')->returnArray()->cached(60*60*24*30)->find('app');
		if ($menus) {
			foreach ($menus as $app) {
				$key = "has_{$app}";
				$permission = $this->permission($app);
				$this->{$key} = $permission;
				$this->smarty->assign($key, $permission);
			}
		}

		//添加允许访问的ip,登录后才能添加
		if (in_array($this->act, array('allowip'))) {
			$ip = $this->request->get('addip');
			if (strlen($ip)) {
				SQL::share('allowip')->insert(array('ip'=>$ip, 'add_ip'=>$this->ip, 'add_time'=>time()));
				$message = "{$ip} added by {$this->ip} on ".date('Y-m-d H:i:s');
				write_log($message);
				echo $message;
				exit;
			}
		}

		$this->has_order = SQL::share()->cached(60*60*24*30)->tableExist('order');
		$this->smarty->assign('has_order', $this->has_order);

		//日志
		//$this->_handle_log();

		//菜单
		if (!in_array($this->act, array('login', 'logout'))) $this->_menu();
	}

	//是否允许登录的ip
	public function is_allow_ip($ip) {
		if ($ip=='127.0.0.1' || $ip=='::1') return true;
		return SQL::share('allowip')->where("ip='{$ip}' OR ip='*'")->cached(60*60*24)->exist();
	}

	//是否登录
	public function _check_login() {
		if (isset($_SESSION['admin']) && is_object($_SESSION['admin']) && isset($_SESSION['admin']->id) && intval($_SESSION['admin']->id)>0) {
			$admin = SQL::share('admin')->where($_SESSION['admin']->id)->cached(60*60*24)->row();
			if ($admin) {
				$this->admin = $admin;
				$this->admin_id = $admin->id;
				$this->admin_name = $admin->name;
				$_SESSION['admin'] = $admin;
				return true;
			}
		} else if (isset($_COOKIE['admin_name']) && isset($_COOKIE['admin_token'])) {
			$admin = $this->cookieAccount('admin_token', $_COOKIE['admin_name'], $_COOKIE['admin_token']);
			if ($admin) {
				$this->admin = $admin;
				$this->admin_id = $admin->id;
				$this->admin_name = $admin->name;
				$_SESSION['admin'] = $admin;
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
			$_SESSION['admin_gourl'] = $gourl;
			if (preg_match('/\/api\b/', $_SERVER['REQUEST_URI'])) error('请重新登录', -1, -100);
			location('?app=home&act=login');
		}
		return false;
	}

	//显示
	public function display($tpl='', $element=array()) {
		$output = $this->request->request('output');
		if (!strlen($tpl)) $tpl = '成功';
		$data = $this->smarty->getTemplateVars();
		if (!strlen($output)) $this->smarty->clearAllAssign();
		if (IS_API) $data = $data['rs'];
		success($data, $tpl, 0, $element);
	}

	//检测权限,供ajax调用,例如 /gm/api/core/checkPermission?application=power&action=edit
	public function checkPermission() {
		$app = $this->request->get('application');
		$act = $this->request->get('action');
		$permission = core::check_permission($app, $act);
		success($permission ? 1 : 0);
	}
	//检测权限,供模板调用,例如 {if core::check_permission('power', 'edit')}
	public static function check_permission($app='', $act='') {
		if (!isset($_SESSION['admin'])) return false;
		$act_where = '';
		if (strlen($act)) {
			$config = SQL::share('op_config')->where("name='GLOBAL_IGNORE_PERMISSION_ACTS'")->cached(60*60*24)->row('content');
			if ($config) {
				$ignore = false;
				$ignore_permission_acts = explode(',', $config->content);
				foreach ($ignore_permission_acts as $_act) {
					if (stripos($act, $_act)!==false) {
						$ignore = true;
						break;
					}
				}
				if (!$ignore) $act_where .= " AND (LOCATE(',{$act},', CONCAT(',',act,','))>0 OR act='*')";
			}
		}
		$admin_id = $_SESSION['admin']->id;
		$super = SQL::share('admin')->where($admin_id)->cached(60*60*24*7)->value('super', 'intval');
		if ($super==1) {
			$exist = SQL::share('menu')->where("app='{$app}'{$act_where}")->cached(60*60*24)->exist();
			return $exist;
		} else {
			$permission = SQL::share('admin_permission')->where("app='{$app}'{$act_where} AND admin_id='{$admin_id}'")->exist();
			if (!$permission) {
				$permission = SQL::share('admin_menu am')->left('menu m', 'am.menu_id=m.id')
					->where("app='{$app}'{$act_where} AND is_menu=1 AND admin_id='{$admin_id}'")->cached(60*60*24)->exist();
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
		$super = SQL::share('admin')->where($admin_id)->cached(60*60*24*7)->value('super', 'intval');
		if ($super==1) {
			$exist = SQL::share('menu')->where("app='{$app}'{$act_where}")->cached(60*60*24)->exist();
			if ($return) return $exist;
		} else {
			$permission = SQL::share('admin_permission')->where("app='{$app}'{$act_where} AND admin_id='{$admin_id}'")->cached(60*60*24)->exist();
			if (!$permission) {
				$menu = SQL::share('admin_menu am')->left('menu m', 'am.menu_id=m.id')->where("app='{$app}'{$act_where} AND is_menu=1 AND admin_id='{$admin_id}'")->cached(60*60*24)->exist();
			}
		}
		if (!$exist || (!$permission && !$menu)) {
			if (!$return) error('你没有权限，请联系超级管理员');
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
			$functions = explode(',', $client->function);
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
		return SQL::share('menu')->where($where)->exist();
	}
	public function has_menu($app, $act='') {
		return core::hasMenu($app, $act);
	}
	private function _menu() {
		$nav = $this->menu();
		$nav_sub = 0;
		if ($nav) {
			foreach ($nav as $g) {
				if (strpos(",{$g->app},", ",{$this->app},") !== false) {
					if (isset($g->sub) && is_object($g->sub) && count(get_object_vars($g->sub))) {
						$nav_sub = 1;
					}
					break;
				}
			}
		}
		$this->smarty->assign('nav', $nav);
		$this->smarty->assign('nav_sub', $nav_sub);
	}
	public function menu($admin_id='', $cached_time=60*60*24) {
		if (!strlen(strval($admin_id))) $admin_id = $this->admin_id;
		$super = SQL::share('admin')->where($admin_id)->cached(60*60*24*7)->value('super', 'intval');
		$menu = SQL::share('admin_menu')->where("admin_id='{$admin_id}'")->cached($cached_time)->find();
		$_super = SQL::share('admin')->where($this->admin_id)->cached(60*60*24*7)->value('super', 'intval');
		$_menu = SQL::share('admin_menu')->where("1=1 AND admin_id='{$this->admin_id}'")->cached($cached_time)->find();
		$first = SQL::share('menu')->where("parent_id=0 AND status=1 AND is_menu=1 AND is_op=0")->sort('sort ASC, id ASC')->cached($cached_time)->find();
		$second = SQL::share('menu')->where("parent_id>0 AND status=1 AND is_menu=1 AND is_op=0")->sort('sort ASC, id ASC')->cached($cached_time)->find();
		if ($first) {
			foreach ($first as $k=>$g) {
				if (preg_match('/^[a-z,]+$/', $g->edition)) {
					$nonShow = false;
					$editions = explode(',', $g->edition);
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
				if ($g->app=='wechat' && ((defined('WX_TAKEOVER') && WX_TAKEOVER==0) || (defined('WX_TOKEN') && !strlen(WX_TOKEN)) || (defined('WX_AESKEY') && !strlen(WX_AESKEY)))) {
					unset($first[$k]);
					continue;
				}
				$nav = new stdClass();
				if ($super==1) {
					$first[$k]->checked = 'checked';
					if ($second) {
						foreach ($second as $i=>$s) {
							if (preg_match('/^[a-z,]+$/', $s->edition)) {
								$nonShow = false;
								$editions = explode(',', $s->edition);
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
							$second[$i]->checked = 'checked';
							if ($g->id==$s->parent_id) {
								$nav->$i = $second[$i];
							}
						}
					}
					if (count(get_object_vars($nav))) $first[$k]->sub = $nav;
					continue;
				}
				$hasMenu = false;
				if ($_super==1) {
					$hasMenu = true;
				} else if ($_menu) {
					foreach ($_menu as $m=>$n) {
						if ($n->menu_id==$g->id) {
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
					foreach ($menu as $j=>$d) {
						$hasMenu = false;
						if ($_super==1) {
							$hasMenu = true;
						} else if ($_menu) {
							foreach ($_menu as $m=>$n) {
								if ($n->menu_id==$g->id) {
									$hasMenu = true;
									break;
								}
							}
						}
						if (!$hasMenu) {
							unset($first[$k]);
							continue;
						}
						if ($d->menu_id==$g->id) {
							$first[$k]->checked = 'checked';
						}
					}
				}
				if ($second) {
					foreach ($second as $i=>$s) {
						if (preg_match('/^[a-z,]+$/', $s->edition)) {
							$nonShow = false;
							$editions = explode(',', $s->edition);
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
						if ($_super==1) {
							$hasMenu = true;
						} else if ($_menu) {
							foreach ($_menu as $m=>$n) {
								if ($n->menu_id==$s->id) {
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
							foreach ($menu as $j=>$d) {
								if ($d->menu_id==$s->id) {
									$second[$i]->checked = 'checked';
									break;
								}
							}
						}
						if ($g->id==$s->parent_id) {
							$nav->$i = $second[$i];
						}
					}
				}
				if (count(get_object_vars($nav))) $first[$k]->sub = $nav;
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
				SQL::share('admin')->where($this->admin_id)->update(array('openid'=>$openid));
				location('?app=home&act=index#wxactive');
			}
		}
		//先获取code
		if ($code=='') {
			$_SESSION['wx_gourl'] = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
			$redirect_url = urlencode(https().$_SERVER['HTTP_HOST']."/gm/?app=core&act=weixin_auth");
			$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_url}".
				"&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
			//$url = "https://m.qfgyp.com/api.php?app=core&act=weixin_auth&platform=console";
			location($url);
		}
		//用户授权
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecrect}&code={$code}&grant_type=authorization_code";
		$html = @file_get_contents($url);
		$json = @json_decode($html);
		//echo json_encode($json);exit;
		if (isset($json->errcode) && intval($json->errcode)!=0) error($json->errmsg);
		$openid = $json->openid;
		$_SESSION['openid'] = $openid;
		//获取用户信息
		$wxapi = new wechatCallbackAPI();
		$json = $wxapi->get_userinfo($json->access_token, $json->openid);
		$json = json_decode(json_encode($json));
		$_SESSION['weixin'] = $json;
		$admin = SQL::share('admin')->where("openid='{$openid}'")->row();
		if (!$admin) {
			location('?app=home&act=login');
		} else {
			$_SESSION['admin'] = $admin;
			$wx_gourl = $this->request->session('wx_gourl', '?app=home&act=index');
			location($wx_gourl);
		}
	}
}
