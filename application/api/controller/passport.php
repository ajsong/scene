<?php
class passport extends core {
	public $member_mod;
	public function __construct() {
		parent::__construct();
		$this->member_mod = m('member');
	}

	//第三方快速登录
	public function third_login() {
		global $push_type;
		$udid = $this->request->post('udid');
		$source = $this->request->post('source');
		$openid = $this->request->post('hash');
		$member = SQL::share('member m')->left('member_thirdparty mt', 'mt.member_id=m.id')->where("mt.mark='{$openid}'")->row('m.*');
		if ($member) {
			if ($member->status==1) {
				//推送强制下线通知
				if (strlen($member->udid) && $member->udid!=$udid && $push_type!='nopush') {
					$push = p('push', $push_type);
					$push->send($member->udid, '账号已在其他设备登录', array('action'=>'login', 'state'=>-100));
				}

				$data = array();
				if (strlen($udid)) {
					SQL::share('member')->where("udid='{$udid}'")->update(array('udid'=>''));
				}
				$data['udid'] = $udid;
				$data['logins'] = array('+1');
				$data['last_time'] = time();
				$data['last_ip'] = $this->ip;
				SQL::share('member')->where($member->id)->update($data);
				$this->_after_passport($member, true, false);
			} else {
				error('账号已经被冻结', -1);
			}
		} else {
			$_POST['is_mobile'] = 0;
			$_SESSION['member_temp'] = $_POST;
			//success($_POST);
			$this->set_mobile(false);
		}
	}

	//绑定手机
	public function set_mobile($check_mobile=true) {
		$mobile = $this->request->post('mobile');
		$code = $this->request->post('code');
		$password = $this->request->post('password');
		$session_mobile = $this->request->session('check_mobile_mobile');
		$session_code = $this->request->session('check_mobile_code');
		if ($check_mobile) {
			if (!strlen($mobile)) error('手机号码不能为空');
			if (!strlen($code)) error('验证码不能为空');
			if ($mobile != $session_mobile) error('手机号码不正确');
			if ($code != $session_code) error('验证码不正确');
		}
		$member_temp = $this->request->session('member_temp', NULL, '[]');
		if (!isset($_SESSION['member']) && !$member_temp) error('请先登录', -100);
		
		$member_id = 0;
		$salt = generate_salt();
		$is_login = false;
		$is_register = false;
		if (isset($_SESSION['member']) && isset($_SESSION['member']->id) && $_SESSION['member']->id>0){
			$member_id = $_SESSION['member']->id;
			$is_login = true;
			$data = array();
			$openid = $this->request->session('openid');
			if (!strlen($_SESSION['member']->salt)) {
				if (!strlen($password)) error('密码不能为空');
				$crypt_password = crypt_password($password, $salt);
				$data['password'] = $crypt_password;
				$data['salt'] = $salt;
			}
			$data['mobile'] = $mobile;
			$data['logins'] = array('+1');
			$data['last_time'] = time();
			$data['last_ip'] = $this->ip;
			SQL::share('member')->where($member_id)->update($data);
			if (!strlen($openid)) {
				SQL::share('member_thirdparty')->insert(array('member_id'=>$member_id, 'type'=>'wechat', 'mark'=>$openid));
			}
		} else if ($member_temp) {
			$_POST = $member_temp;
			$udid = $this->request->post('udid');
			$source = $this->request->post('source');
			$openid = $this->request->post('hash');
			$member_id = intval(SQL::share('member_thirdparty')->where("mark='{$openid}'")->value('member_id'));
			$member = NULL;
			if ($member_id>0) $member = SQL::share('member')->where($member_id)->row();
			if ($member) {
				$is_login = true;
				$data = array();
				if (!strlen($member->salt)) {
					if (!strlen($password)) $password = random_str(8);
					$crypt_password = crypt_password($password, $salt);
					$data['password'] = $crypt_password;
					$data['salt'] = $salt;
				}
				if (strlen($udid)) {
					SQL::share('member')->where("udid='{$udid}'")->update(array('udid'=>''));
				}
				$data['udid'] = $udid;
				$data['logins'] = array('+1');
				$data['last_time'] = time();
				$data['last_ip'] = $this->ip;
				SQL::share('member')->where($member_id)->update($data);
			} else {
				$is_register = true;
				if (!strlen($password)) $password = random_str(8);
				if (strlen($password)<6) error('密码不能少于6位');
				$crypt_password = crypt_password($password, $salt);
				//微信的信息
				$nick_name = $this->request->post('nickname');
				if (!strlen($nick_name)) $nick_name = $this->request->post('name');
				$avatar = $this->request->post('headimgurl');
				if (!strlen($avatar)) $avatar = $this->request->post('avatar');
				$sex = $this->request->post('sex');
				$province = $this->request->post('province');
				$city = $this->request->post('city');
				
				//注册信息
				$data = array();
				$data['name'] = $mobile;
				$data['password'] = $crypt_password;
				$data['salt'] = $salt;
				$data['mobile'] = $mobile;
				$data['reg_time'] = time();
				$data['reg_ip'] = $this->ip;
				$data['last_time'] = time();
				$data['last_ip'] = $this->ip;
				$data['logins'] = 1;
				$data['status'] = 1;
				$data['udid'] = $udid;
				$data['sign'] = generate_sign();
				$data['nick_name'] = $nick_name;
				$data['sex'] = $sex;
				$data['province'] = $province;
				$data['city'] = $city;
				$data['avatar'] = $avatar;
				$data['code'] = generate_sign();
				
				if (strlen($udid)) {
					SQL::share('member')->where("udid='{$udid}'")->update(array('udid'=>''));
				}
				$member_id = SQL::share('member')->insert($data);
				//生成新用户的邀请码
				$this->member_mod->new_invite_code($member_id);
			}
			if (strlen($source) && strlen($openid)) {
				if (!SQL::share('member_thirdparty')->where("mark='{$openid}'")->exist()) {
					SQL::share('member_thirdparty')->insert(array('member_id'=>$member_id, 'type'=>$source, 'mark'=>$openid));
				}
			}
		}
		$_SESSION['member_temp'] = '';
		unset($_SESSION['member_temp']);
		
		$order_id = $this->request->session('order_id', 0);
		if ($order_id) {
			$_SESSION['order_id'] = '';
			unset($_SESSION['order_id']);
			header("location:wap.php?app=order&act=complete&order_id={$order_id}");
			exit;
		}
		
		$member = SQL::share('member')->where($member_id)->row();
		if ($member) {
			$_SESSION['member'] = $member;
			$this->_check_login();
			$url = $this->request->session('weixin_url');
			if (strlen($url) && IS_WEB) location($url);
			$this->_after_passport($member, $is_login, $is_register);
		} else {
			error('该账号不存在，请注册');
			//echo '<meta charset="UTF-8"><script>alert("该账号不存在，请注册");location.href="wap.php?app=register";< /script>';
			//header("location:wap.php?tpl=register");
			//exit;
		}
	}

	//登录
	public function login() {
		global $push_type;
		$this->_clearsession();
		if (!IS_POST) {
			success('ok');
		}
		$mobile = $this->request->post('username');
		$password = $this->request->post('password');
		$udid = $this->request->post('udid');
		$member = NULL;
		$openid = $this->request->get('openid'); //增加判断$_GET['openid']为了区分是否主动登录
		if (WX_LOGIN && $this->is_weixin() && $this->weixin_authed() && strlen($openid)) {
			$_SESSION['openid'] = $openid;
			$member = SQL::share('member m')->left('member_thirdparty mt', 'mt.member_id=m.id')->where("mt.mark='{$openid}'")->row('m.*');
		} else {
			if (!strlen($mobile)) error('账号不能为空');
			if (!strlen($password)) error('密码不能为空');
			$member = SQL::share('member')->where("name='{$mobile}' OR mobile='{$mobile}'")->row();
			if (!$member) {
				error('账号不存在');
			}
			$crypt_password = crypt_password($password, $member->salt);
			if ($crypt_password != $member->password) {
				error('账号或密码错误', -2);
			}
		}
		if ($member) {
			if ($member->status==1) {
				//推送强制下线通知
				if (strlen($member->udid) && $member->udid!=$udid && $push_type!='nopush') {
					$push = p('push', $push_type);
					$push->send($member->udid, '账号已在其他设备登录', array('action'=>'login', 'state'=>-100));
				}

				$data = array();
				if (strlen($udid)) {
					//20150708 by ajsong 清除之前登录过有相同udid的账号的udid
					SQL::share('member')->where("udid='{$udid}'")->update(array('udid'=>''));
				}
				$data['udid'] = $udid;

				//环信登录需要原始密码
				if (strlen($password)) $data['origin_password'] = $password;
				$data['logins'] = array('+1');
				$data['last_time'] = time();
				$data['last_ip'] = $this->ip;
				SQL::share('member')->where($member->id)->update($data);
				$this->_after_passport($member, true, false);
			} else {
				error('账号已经被冻结', -1);
			}
		} else if ($this->is_weixin() && $this->weixin_authed() && $openid) {
			$url = $this->request->session('weixin_url', 'wap.php');
			header("location:{$url}");
		}
	}

	//注册
	public function register() {
		$this->_clearsession();
		if (!IS_POST) {
			success('ok');
		}
		if ($this->configs['G_REGISTER']==0) {
			error('当前不能注册');
		}
		$mobile = $this->request->post('username');
		$password = $this->request->post('password');
		$code = $this->request->post('code');
		$invite_code = $this->request->post('invite_code');
		$udid = $this->request->post('udid');
		$session_code = $this->request->session('check_mobile_code');
		$session_mobile = $this->request->session('check_mobile_mobile');
		$salt = generate_salt();
		$crypt_password = crypt_password($password, $salt);
		//微信的信息
		$nick_name = $this->request->post('nick_name');
		$avatar = $this->request->post('avatar');
		$sex = $this->request->post('sex');
		$province = $this->request->post('province');
		$city = $this->request->post('city');
		$openid = $this->request->post('openid'); //openid不为空，表示从微信访问过来，直接使用openid登录
		if (!strlen($openid)) $openid = $this->request->session('openid');
		if (!strlen($mobile)) error('账号不能为空');
		//if (!strlen($code)) error('验证码不能为空');
		if (!strlen($password)) error('密码不能为空');
		//if ($code != $session_code) error('验证码不正确');
		//if ($mobile != $session_mobile) error('手机号码不正确');
		
//		if (SQL::share('member')->where("mobile='{$mobile}'")->count()) {
//			error("手机号码已经被注册");
//		}
		
		//注册信息
		$data = array();
		$data['name'] = $mobile;
		//$data['mobile'] = $mobile;
		$data['reg_time'] = time();
		$data['reg_ip'] = $this->ip;
		$data['last_time'] = time();
		$data['last_ip'] = $this->ip;
		$data['logins'] = 1;
		$data['status'] = 1;
		$data['udid'] = $udid;
		$data['sign'] = generate_sign();
		$data['invite_code'] = $invite_code;
		$data['origin_password'] = $password;
		$data['salt'] = $salt;
		$data['password'] = $crypt_password;
		$data['nick_name'] = $nick_name;
		$data['avatar'] = $avatar;
		$data['sex'] = $sex;
		$data['province'] = $province;
		$data['city'] = $city;
		$data['code'] = generate_sign();

		$reseller_id = $this->request->session('reseller_id', 0);
		if (strlen($invite_code)) {
			$invitor = SQL::share('member')->where("invite_code='{$invite_code}'")->row();
			if (!$invitor) error('邀请码无效');
			$data['parent_id'] = $invitor->id;
		} else if ($reseller_id>0) {
			$invitor = SQL::share('member')->where($reseller_id)->row('id');
			if ($invitor) $data['parent_id'] = $invitor->id;
		}
		if (strlen($udid)) {
			//20150708 by ajsong 清除之前登录过有相同udid的账号的udid
			SQL::share('member')->where("udid='{$udid}'")->update(array('udid'=>''));
		}
		//20160322 by ajsong 增加更新头像,与微信同步
		if (strlen($openid) && isset($_SESSION['weixin'])) {
			$data['nick_name'] = $_SESSION['weixin']->nickname;
			$data['avatar'] = $_SESSION['weixin']->headimgurl;
			$data['sex'] = $_SESSION['weixin']->sex;
			$data['province'] = $_SESSION['weixin']->province;
			$data['city'] = $_SESSION['weixin']->city;
		}
		$member = SQL::share('member')->returnObj()->insert($data);
		//20181225 by ajsong 绑定微信openid
		if (strlen($openid) && !SQL::share('member_thirdparty')->where("mark='{$openid}'")->exist()) {
			SQL::share('member_thirdparty')->insert(array('member_id'=>$member->id, 'type'=>'wechat', 'mark'=>$openid));
		}
        //生成新用户的邀请码
        $this->member_mod->new_invite_code($member->id);
		$this->_after_passport($member, false, true);
	}

	//处理登录或注册后的操作
	private function _after_passport($member, $is_login=false, $is_register=false, $avatar='') {
		if (!$member && !is_object($member)) error('member is not an object');

		//生成签名
		if ($this->is_wx && $is_login) {
			$sign = $member->sign;
		} else {
			//20160322 by ajsong 不理是否微信登录都更新一下sign会好点
			$sign = generate_sign();
			$member->sign = $sign;
			SQL::share('member')->where($member->id)->update(array('sign'=>$sign));
		}

		//设置登录信息
		$this->sign = $sign;

		if (strlen($member->avatar)) {
			$member->avatar = add_domain($member->avatar);
		} else {
			$member->avatar = add_domain('/images/avatar.png');
		}
		//生成缩略图
		$member->format_reg_time = date('Y-m-d', $member->reg_time);
		
		//总财富
		$member->total_price = strval($member->money+$member->commission);

		//登录与注册都需要记录openid
		$openid = $this->request->session('openid');
		if (strlen($openid)) {
			if (!SQL::share('member_thirdparty')->where("mark='{$openid}'")->exist()) {
				SQL::share('member_thirdparty')->insert(array('member_id'=>$member->id, 'type'=>'wechat', 'mark'=>$openid));
			}
			$_SESSION['weixin_authed'] = 1;
		}

		//更新在线
		SQL::share('member')->where($member->id)->update(array('session_id'=>$this->session_id));
		
		//if ($is_login) $this->_check_login();

		//更新购物车
		//SQL::share('cart')->where("session_id='{$this->session_id}'")->update(array('member_id'=>$member->id));

		//是否已绑定手机(账号)
		//$member->is_mobile = !strlen($member->name) ? 0 : 1;
		
		if ($is_register) {
			//设置为最低等级
			if (in_array('grade', $this->function)) {
				$grade = SQL::share('grade')->where("status=1")->sort('score ASC, id ASC')->row('id, score');
				if ($grade) {
					SQL::share('member')->where($member->id)->update(array('grade_id'=>$grade->id, 'grade_score'=>$grade->score, 'grade_time'=>time()));
					$member->grade_id = $grade->id;
					$member->grade_score = $grade->score;
				}
			}
		}
		
		//获取当前等级的下个等级
		if (in_array('grade', $this->function)) {
			$score = 0;
			$grade = SQL::share('grade')->where("status=1 AND id>'{$member->grade_id}'")->sort('score ASC, id ASC')->row('score');
			if ($grade) $score = intval($grade->score);
			if ($score == 0) {
				$score = intval(SQL::share('grade')->where($member->grade_id)->value('score'));
			}
			$member->next_score = "{$score}";
			$grade = SQL::share('grade')->where($member->grade_id)->row();
			$member->grade = $grade;
		}
		
		$member = $this->get_member_from_sign($this->sign);
		$member = add_domain_deep($member, array('avatar'));
		unsets($member, 'password salt withdraw_password withdraw_salt');
		
		$_SESSION['sms_code'] = '';
		$_SESSION['sms_mobile'] = '';
		$_SESSION['check_mobile_code'] = '';
		$_SESSION['check_mobile_mobile'] = '';
		$_SESSION['forget_sms_code'] = '';
		$_SESSION['forget_sms_mobile'] = '';
		
		$remember = $this->request->post('remember', 0);
		if ($is_login && $remember) {
			$this->cookieAccount('member_token', strlen($member->name) ? $member->name : $member->mobile);
		}
		
		//微信端跳转回之前查看的页面
		if ($this->is_wx && !$this->is_mini && $is_login && IS_WEB) {
			$url = $this->request->session('weixin_url', 'wap.php');
			location($url);
		}
		
		$_SESSION['gourl'] = $this->request->session('api_gourl');
		$_SESSION['api_gourl'] = '';
		success($member);
	}
	
	//主动调用微信登录
	public function wx_login(){
		//20160322 by ajsong 先判断是否已经进行认证(因为有可能是认证返回到这个接口)
		if ($this->is_weixin() && $this->weixin_authed()) {
			header("Location:api.php?app=passport&act=login&openid=".$_SESSION['openid']); //需要使用header跳转,为了指定当前不是主动登录
		} else {
			if (!$this->is_weixin()) {
				$url = $this->request->session('weixin_url', 'wap.php');
				header("location:{$url}"); //因为非微信端不能使用认证跳转登录
			} else {
				$this->weixin_login(); //进行认证跳转
			}
		}
		exit;
	}
	
	//保存小程序openid
	public function set_mini_openid() {
		if ($this->member_id<=0) success(NULL);
		$openid = $this->request->post('openid');
		if (strlen($openid) && !SQL::share('member_thirdparty')->where("mark='{$openid}'")->exist()) {
			SQL::share('member_thirdparty')->insert(array('member_id'=>$this->member_id, 'type'=>'mini', 'mark'=>$openid));
		}
		$row = SQL::share('member')->where($this->member_id)->row();
		success($row);
	}
	
	//检查APP本地sign与数据库sign是否一样,不一样即登录失效
	public function check_sign() {
		$id = $this->request->get('id', 0);
		$sign = $this->request->get('sign');
		$where = "id='{$id}'";
		$pass = $id>0;
		if (ALONE_LOGIN==1) {
			$pass = ($pass && strlen($sign));
			$where .= " AND sign='{$sign}'";
		}
		if ($pass) {
			$member = SQL::share('member')->where($where)->row();
			if ($member) {
				$_SESSION['member'] = $member;
				$this->_check_login();
				success($member);
			} else {
				error('该账号已在其他设备登录', -9);
			}
		} else {
			error('缺少数据');
		}
	}
	
	//检查浏览器本地storage与数据库sign是否一样,一样即自动登录
	public function check_storage() {
		$sign = $this->request->get('sign');
		if (strlen($sign)) {
			$member = SQL::share('member')->where("sign='{$sign}'")->row();
			if ($member) {
				$_SESSION['member'] = $member;
				$this->_check_login();
				success($member);
			} else {
				success('ok');
			}
		} else {
			success('ok');
		}
	}
	
	//发送验证码
	public function sms() {
		$mobile = $this->request->post('mobile');
		if (strlen($mobile)) {
			$count = SQL::share('sms')->where("mobile='{$mobile}' AND status=1")->comparetime('n', 'add_time', '=0')->count();
			if ($count>=intval($this->configs['G_SMS_MINUTE'])) success('ok');
			$count = SQL::share('sms')->where("mobile='{$mobile}' AND status=1")->comparetime('h', 'add_time', '=0')->count();
			if ($count>=intval($this->configs['G_SMS_HOUR'])) success('ok');
			$count = SQL::share('sms')->where("mobile='{$mobile}' AND status=1")->comparetime('d', 'add_time', '=0')->count();
			if ($count>=intval($this->configs['G_SMS_DAY'])) success('ok');
			$code = random_str(intval($this->configs['GLOBAL_MOBILE_CODE_NUM']), array_merge(range(0, 9)));
			$this->send_sms(array(
				'mobile'=>$mobile,
				'sms'=>str_replace('{code}', $code, $this->configs['G_SMS_TEMPLATE']),
				'sign'=>$this->configs['G_SMS_SIGN']
			));
			$_SESSION['sms_mobile'] = $mobile;
			$_SESSION['sms_code'] = $code;
			success(array('mobile'=>$mobile, 'code'=>$code));
		} else {
			error('请输入手机号码');
		}
	}
	
	//检查手机是否被注册，以及发送注册验证码
	public function check_mobile() {
		$mobile = $this->request->post('mobile');
		if (strlen($mobile)) {
			if (!SQL::share('member')->where("mobile='{$mobile}'")->exist()) {
				$count = SQL::share('sms')->where("mobile='{$mobile}' AND status=1")->comparetime('n', 'add_time', '=0')->count();
				if ($count>=intval($this->configs['G_SMS_MINUTE'])) success('ok');
				$count = SQL::share('sms')->where("mobile='{$mobile}' AND status=1")->comparetime('h', 'add_time', '=0')->count();
				if ($count>=intval($this->configs['G_SMS_HOUR'])) success('ok');
				$count = SQL::share('sms')->where("mobile='{$mobile}' AND status=1")->comparetime('d', 'add_time', '=0')->count();
				if ($count>=intval($this->configs['G_SMS_DAY'])) success('ok');
				$code = random_str(intval($this->configs['GLOBAL_MOBILE_CODE_NUM']), array_merge(range(0, 9)));
				$this->send_sms(array(
					'mobile'=>$mobile,
					'sms'=>str_replace('{code}', $code, $this->configs['G_CHECK_MOBILE_TEMPLATE']),
					'sign'=>$this->configs['G_SMS_SIGN']
				));
				$_SESSION['check_mobile_mobile'] = $mobile;
				$_SESSION['check_mobile_code'] = $code;
				success(array('mobile'=>$mobile, 'code'=>$code));
			} else {
				error('该手机号码已被注册');
			}
		} else {
			error('请输入手机号码');
		}
	}

	//忘记密码 - 发送手机号码和验证短信
	public function forget_sms() {
		$mobile = $this->request->post('mobile');
		if (!strlen($mobile)) error('请输入合法手机号码');
		$count = SQL::share('member')->where("mobile='{$mobile}'")->count();
		if ($count==1) {
			$count = SQL::share('sms')->where("mobile='{$mobile}' AND status=1")->comparetime('n', 'add_time', '=0')->count();
			if ($count>=intval($this->configs['G_SMS_MINUTE'])) success('ok');
			$count = SQL::share('sms')->where("mobile='{$mobile}' AND status=1")->comparetime('h', 'add_time', '=0')->count();
			if ($count>=intval($this->configs['G_SMS_HOUR'])) success('ok');
			$count = SQL::share('sms')->where("mobile='{$mobile}' AND status=1")->comparetime('d', 'add_time', '=0')->count();
			if ($count>=intval($this->configs['G_SMS_DAY'])) success('ok');
			$code = random_str(intval($this->configs['GLOBAL_MOBILE_CODE_NUM']), array_merge(range(0, 9)));
			$this->send_sms(array(
				'mobile'=>$mobile,
				'sms'=>str_replace('{code}', $code, $this->configs['G_FORGET_SMS_TEMPLATE']),
				'sign'=>$this->configs['G_SMS_SIGN']
			));
			$_SESSION['forget_sms_mobile'] = $mobile;
			$_SESSION['forget_sms_code'] = $code;
			success(array('mobile'=>$mobile, 'code'=>$code));
		} else if ($count>1) {
			error('该手机号码被重复注册，无法找回密码，请联系平台客服');
		} else {
			error('该手机号码没在系统注册');
		}
	}

	//忘记密码
	public function forget() {
		if (IS_POST) {
			$mobile = $this->request->post('mobile');
			$code = $this->request->post('code');
			$password = $this->request->post('password');
			$session_code = $this->request->session('forget_sms_code');
			if (!strlen($mobile) || !strlen($code) || !strlen($password)) error('请输入手机号、验证码与新密码');
			if (!strlen($session_code) || $code!=$session_code) error('验证码不正确');
			$member = SQL::share('member')->where("mobile='{$mobile}'")->row('id, name, mobile');
			if (!$member) error('该会员不存在');
			$salt = generate_salt();
			$crypt_password = crypt_password($password, $salt);
			SQL::share('member')->where($member->id)->update(array('password'=>$crypt_password, 'origin_password'=>$password, 'salt'=>$salt));
			success('ok', '成功', 0, NULL, 'javascript:history.go(-2)', '密码重置成功');
		}
		success('ok');
	}
	
	//清除APP角标
	public function clear_badge() {
		if ($this->member_id>0) {
			SQL::share('member')->where($this->member_id)->update(array('badge'=>0));
		}
		success('ok');
	}
	
	//退出
	public function logout() {
		if (isset($_COOKIE['member_name'])) $this->cookieAccount('member_token', $_COOKIE['member_name'], NULL);
		$this->_clearsession();
		if (IS_API && !IS_APP) $this->weixin_success('退出成功');
		success('ok');
	}

	//清除session
	private function _clearsession() {
		//session_unset();
		if (isset($_SESSION['member'])) unset($_SESSION['member']);
		if (isset($this->member) && $this->member_id>0) $this->member_id = 0;
	}
 }
