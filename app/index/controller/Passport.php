<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\index\controller\Core;
use think\facade\Db;

class Passport extends Core
{
	
	//登录
	public function login() {
		global $push_type;
		$this->_clearsession();
		if (!IS_POST) {
			return success('ok');
		}
		$mobile = $this->request->post('username');
		$password = $this->request->post('password');
		$udid = $this->request->post('udid');
		$member = NULL;
		$openid = $this->request->get('openid'); //增加判断$_GET['openid']为了区分是否主动登录
		if (WX_LOGIN && $this->is_weixin() && $this->weixin_authed() && strlen($openid)) {
			session('openid', $openid);
			$member = Db::name('member')->alias('m')->leftJoin('member_thirdparty mt', 'mt.member_id=m.id')->where("mt.mark='{$openid}'")->field('m.*')->find();
		} else {
			if (!strlen($mobile)) error('账号不能为空');
			if (!strlen($password)) error('密码不能为空');
			$member = Db::name('member')->whereOr(['name'=>$mobile, 'mobile'=>$mobile])->find();
			if (!$member) {
				return error('账号不存在');
			}
			$crypt_password = crypt_password($password, $member['salt']);
			if ($crypt_password != $member['password']) {
				return error('账号或密码错误', -2);
			}
		}
		if ($member) {
			if ($member['status']==1) {
				//推送强制下线通知
				if (strlen($member['udid']) && $member['udid']!=$udid && $push_type!='nopush') {
					$push = p('push', $push_type);
					$push->send($member['udid'], '账号已在其他设备登录', array('action'=>'login', 'state'=>-100));
				}
				
				$data = array();
				if (strlen($udid)) {
					//20150708 by ajsong 清除之前登录过有相同udid的账号的udid
					Db::name('member')->where('udid', $udid)->update(array('udid'=>''));
				}
				$data['udid'] = $udid;
				
				//环信登录需要原始密码
				if (strlen($password)) $data['origin_password'] = $password;
				$data['logins'] = array('+1');
				$data['last_time'] = time();
				$data['last_ip'] = $this->ip;
				Db::name('member')->where('id', $member['id'])->update($data);
				return $this->_after_passport($member, true, false);
			} else {
				return error('账号已经被冻结', -1);
			}
		} else if ($this->is_weixin() && $this->weixin_authed() && $openid) {
			$url = $this->request->session('weixin_url', '/index/wap');
			location($url);
		}
		return error();
	}
	
	//注册
	public function register() {
		$this->_clearsession();
		if (!IS_POST) {
			return success('ok');
		}
		if ($this->configs['G_REGISTER']==0) {
			return error('当前不能注册');
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
		if (!strlen($mobile)) return error('账号不能为空');
		//if (!strlen($code)) return error('验证码不能为空');
		if (!strlen($password)) return error('密码不能为空');
		//if ($code != $session_code) return error('验证码不正确');
		//if ($mobile != $session_mobile) return error('手机号码不正确');
		
		//		if (Db::name('member')->where("mobile='{$mobile}'")->count()) {
		//			return error("手机号码已经被注册");
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
		
		$reseller_id = session('reseller_id');
		if (strlen($invite_code)) {
			$invitor = Db::name('member')->where('invite_code', $invite_code)->find();
			if (!$invitor) return error('邀请码无效');
			$data['parent_id'] = $invitor['id'];
		} else if ($reseller_id>0) {
			$invitor = Db::name('member')->where('id', $reseller_id)->field('id')->find();
			if ($invitor) $data['parent_id'] = $invitor['id'];
		}
		if (strlen($udid)) {
			//20150708 by ajsong 清除之前登录过有相同udid的账号的udid
			Db::name('member')->where('udid', $udid)->update(array('udid'=>''));
		}
		//20160322 by ajsong 增加更新头像,与微信同步
		if (strlen($openid) && session('?weixin')) {
			$data['nick_name'] = session('weixin')['nickname'];
			$data['avatar'] = session('weixin')['headimgurl'];
			$data['sex'] = session('weixin')['sex'];
			$data['province'] = session('weixin')['province'];
			$data['city'] = session('weixin')['city'];
		}
		$memberId = Db::name('member')->insertGetId($data);
		$member = Db::name('member')->where('id', $memberId)->find();
		//20181225 by ajsong 绑定微信openid
		if (strlen($openid) && Db::name('member_thirdparty')->where('mark', $openid)->count()==0) {
			Db::name('member_thirdparty')->insert(array('member_id'=>$member['id'], 'type'=>'wechat', 'mark'=>$openid));
		}
		//生成新用户的邀请码
		$this->member_mod->new_invite_code($member['id']);
		return $this->_after_passport($member, false, true);
	}
	
	//处理登录或注册后的操作
	private function _after_passport($member, $is_login=false, $is_register=false, $avatar='') {
		if (!$member) return error('member is null');
		
		//生成签名
		if ($this->is_wx && $is_login) {
			$sign = $member['sign'];
		} else {
			//20160322 by ajsong 不理是否微信登录都更新一下sign会好点
			$sign = generate_sign();
			$member['sign'] = $sign;
			Db::name('member')->where('id', $member['id'])->update(array('sign'=>$sign));
		}
		
		//设置登录信息
		$this->sign = $sign;
		
		if (strlen($member['avatar'])) {
			$member['avatar'] = add_domain($member['avatar']);
		} else {
			$member['avatar'] = add_domain('/images/avatar.png');
		}
		//生成缩略图
		$member['format_reg_time'] = date('Y-m-d', $member['reg_time']);
		
		//总财富
		$member['total_price'] = strval($member['money']+$member['commission']);
		
		//登录与注册都需要记录openid
		$openid = $this->request->session('openid');
		if (strlen($openid)) {
			if (Db::name('member_thirdparty')->where('mark', $openid)->count()==0) {
				Db::name('member_thirdparty')->insert(array('member_id'=>$member['id'], 'type'=>'wechat', 'mark'=>$openid));
			}
			session('weixin_authed', 1);
		}
		
		//更新在线
		Db::name('member')->where('id', $member['id'])->update(array('session_id'=>$this->session_id));
		
		//if ($is_login) $this->_check_login();
		
		//更新购物车
		//Db::name('cart')->where("session_id='{$this->session_id}'")->update(array('member_id'=>$member->id));
		
		//是否已绑定手机(账号)
		//$member->is_mobile = !strlen($member->name) ? 0 : 1;
		
		if ($is_register) {
			//设置为最低等级
			if (in_array('grade', $this->function)) {
				$grade = Db::name('grade')->where('status', 1)->order(['score', 'id'=>'ASC'])->field('id, score')->find();
				if ($grade) {
					Db::name('member')->where('id', $member['id'])->update(array('grade_id'=>$grade['id'], 'grade_score'=>$grade['score'], 'grade_time'=>time()));
					$member['grade_id'] = $grade['id'];
					$member['grade_score'] = $grade['score'];
				}
			}
		}
		
		//获取当前等级的下个等级
		if (in_array('grade', $this->function)) {
			$score = 0;
			$grade = Db::name('grade')->where(['status'=>1, 'id'=>$member['grade_id']])->order(['score', 'id'=>'ASC'])->field('score')->find();
			if ($grade) $score = intval($grade['score']);
			if ($score == 0) {
				$score = intval(Db::name('grade')->where('id', $member['grade_id'])->value('score'));
			}
			$member['next_score'] = "{$score}";
			$grade = Db::name('grade')->where('id', $member['grade_id'])->find();
			$member['grade'] = $grade;
		}
		
		$member = $this->get_member_from_sign($this->sign);
		$member = add_domain_deep($member, array('avatar'));
		unsets($member, 'password salt withdraw_password withdraw_salt');
		
		session('sms_code', null);
		session('sms_mobile', null);
		session('check_mobile_code', null);
		session('check_mobile_mobile', null);
		session('forget_sms_code', null);
		session('forget_sms_mobile', null);
		
		$remember = $this->request->post('remember', 0);
		if ($is_login && $remember) {
			$this->cookieAccount('member_token', strlen($member['name']) ? $member['name'] : $member['mobile']);
		}
		
		//微信端跳转回之前查看的页面
		if ($this->is_wx && !$this->is_mini && $is_login && IS_WEB) {
			$url = $this->request->session('weixin_url', '\index\wap');
			location($url);
		}
		
		session('gourl', $this->request->session('api_gourl'));
		session('api_gourl', null);
		
		session('member', $member);
		return success($member);
	}
	
	//退出
	public function logout() {
		if (cookie('?member_name')) $this->cookieAccount('member_token', cookie('member_name'), NULL);
		$this->_clearsession();
		if (IS_API && !IS_APP) $this->weixin_success('退出成功');
		return success('ok');
	}
	
	//清除session
	private function _clearsession() {
		//session_unset();
		session('member', null);
		if (isset($this->member) && $this->member_id>0) $this->member_id = 0;
	}
}
