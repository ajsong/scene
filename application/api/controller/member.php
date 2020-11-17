<?php
class member extends core {
	private $member_mod;
	private $withdraw_mod;
	private $commission_mod;

	public function __construct() {
		parent::__construct();
		$this->member_mod = m('member');
		$this->withdraw_mod = m('withdraw');
		$this->commission_mod = m('commission');
	}

	//首页会员首页接口，在登录时数据已经取到
	public function index() {
		$sign = $this->request->get('sign');
		if ($sign && ALONE_LOGIN==1) {
			if (!SQL::share('member')->where("sign='{$sign}'")->exist()) {
				error('该账号已在其他设备登录', -9);
			}
		}
		$not_pay = $not_shipping = $not_confirm = $not_comment = $notify = $coupon_count = 0;
		$member = NULL;
		if ($this->member_id) {
			$not_pay = $this->_get_status_order_count(0);
			$not_shipping = $this->_get_status_order_count(1);
			$not_confirm = $this->_get_status_order_count(2);
			$not_comment = $this->_get_status_order_count(3);
			$notify = $this->_get_message_count();
			$coupon_count = $this->_get_coupon_count();
			//获取会员所有信息
			$member = $this->get_member_from_sign($this->member_sign);
			if ($member) {
				//获取当前等级的下个等级
				$score = 0;
				$row = SQL::share('grade')->where("status=1 AND id>'{$member->grade_id}'")->sort('sort ASC, id ASC')->row('score');
				if ($row) $score = intval($row->score);
				if ($score==0) {
					$score = intval(SQL::share('grade')->where($member->grade_id)->value('score'));
				}
				$member->next_score = "{$score}";
				$_SESSION['member'] = $member;
			}
		}
		$cart_total = $this->_get_cart_count();
		success(array(
			'member_id' => intval($this->member_id),
			'cart_total' => $cart_total,
			'coupon_count' => $coupon_count,
			'not_pay' => $not_pay,
			'not_shipping' => $not_shipping,
			'not_confirm' => $not_confirm,
			'not_comment' => $not_comment,
			'notify' => $notify,
			'member' => $member
		));
	}
	
	//获取购物车商品总数
	public function _get_cart_count() {
		$where = '';
		if ($this->member_id>0) {
			$where .= " AND (member_id='{$this->member_id}' OR session_id='{$this->session_id}')";
		} else {
			$where .= " AND session_id='{$this->session_id}'";
		}
		$num = intval(SQL::share('cart')->where($where)->value('IFNULL(SUM(quantity),0)'));
		return $num;
	}
	
	//获取指定状态未读订单总数
	public function _get_status_order_count($status) {
		$num = SQL::share('order')->where("status='{$status}' AND member_id='{$this->member_id}' AND readed=0")->count();
		return $num;
	}
	
	//获取未读站内信息总数
	public function _get_message_count() {
		$num = SQL::share('message')->where("member_id='{$this->member_id}' AND readed=0")->count();
		return $num;
	}
	
	//获取优惠券总数
	public function _get_coupon_count() {
		$num = SQL::share('coupon_sn')->where("status='1' AND member_id='{$this->member_id}' AND member_id>0")->count();
		return $num;
	}
	
	//会员信息
	public function detail() {
		$id = $this->request->get('id', 0);
		$member = SQL::share('member')->where($id)->row();
		if (!$member) error('该用户不存在');
		unsets($member, 'id password origin_password salt session_id withdraw_password withdraw_salt sign');
		success($member);
	}
	
	//邀请码分享进来
	public function share() {
		$invite_code = $this->request->get('invite_code');
		if (strlen($invite_code)) {
			$member = SQL::share('member')->where("invite_code='{$invite_code}'")->row('id');
			if ($member) {
				$_SESSION['reseller_id'] = $member->id;
			}
		}
		location('wap.php');
	}
	
	//我的余额
	public function money() {
		$money = 0;
		$member = SQL::share('member')->where($this->member_id)->row('money');
		if ($member) {
			$money = $member->money;
		}
		success($money);
	}
	
	//我的佣金
	public function commission() {
		$money = 0;
		$member = SQL::share('member')->where($this->member_id)->row('commission');
		if ($member) {
			$money = $member->commission;
		}
		success($money);
	}
	
	//我的积分
	public function integral() {
		$this->check_edition(3);
		$integral = 0;
		$member = SQL::share('member')->where($this->member_id)->row('integral');
		if ($member) {
			$integral = $member->integral;
		}
		success($integral);
	}

	//产品浏览历史
	public function goods_history() {
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		$rs = SQL::share('member_goods_history h')->left('goods g', 'g.id=h.goods_id')->where("g.status='1' AND h.member_id='{$this->member_id}'")
			->sort('h.id DESC')->limit($offset, $pagesize)->find('g.*');
		if ($rs) {
			foreach ($rs as $k=>$g) {
				unset($rs[$k]->content);
			}
		}
		$goods_mod = m('goods');
		$rs = $goods_mod->set_min_prices($rs);
		$rs = add_domain_deep($rs, array('pic'));
		success($rs);
	}
	
	//余额明细
	public function money_history() {
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 12);
		$rs = SQL::share('member_money_history')->where("member_id='{$this->member_id}'")->sort('id DESC')->limit($offset, $pagesize)->find();
		if ($rs) {
			foreach ($rs as $k => $g) {
				$rs[$k]->add_time = date("Y-m-d H:i:s", $g->add_time);
			}
		}
		success($rs);
	}
	
	//收入明细
	public function commission_history() {
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 12);
		$rs = SQL::share('member_commission')->where("member_id='{$this->member_id}'")->sort('id DESC')->limit($offset, $pagesize)->find();
		if ($rs) {
			foreach ($rs as $k => $g) {
				$rs[$k]->status_name = $this->commission_mod->status_name($g->status);
				$rs[$k]->add_time = date("Y-m-d H:i:s", $g->add_time);
			}
		}
		success($rs);
	}

	//提现历史
	public function withdraw_history() {
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 15);
		$rs = SQL::share('withdraw')->where("member_id='{$this->member_id}'")->sort('id DESC')->limit($offset, $pagesize)->find();
		if ($rs) {
			foreach ($rs as $k=>$g) {
				$rs[$k]->title = '申请提现';
				$rs[$k]->status_name = $this->withdraw_mod->status_name($g->status);
				$rs[$k]->add_time = date('Y-m-d H:i:s', $g->add_time);
			}
		}
		success($rs);
	}
	
	//签到明细
	public function sign_history() {
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 12);
		$rs = SQL::share('member_sign_history')->where("member_id='{$this->member_id}'")->sort('id DESC')->limit($offset, $pagesize)->find();
		if ($rs) {
			foreach ($rs as $k => $g) {
				$rs[$k]->add_time = date("Y-m-d H:i:s", $g->add_time);
			}
		}
		success($rs);
	}
	
	//积分使用明细
	public function integral_history() {
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 12);
		$rs = SQL::share('member_integral_history')->where("member_id='{$this->member_id}'")->sort('id DESC')->limit($offset, $pagesize)->find();
		if ($rs) {
			foreach ($rs as $k => $g) {
				$rs[$k]->add_time = date("Y-m-d H:i:s", $g->add_time);
			}
		}
		success($rs);
	}

	//消费明细，读取订单，1已支付，2已发货，3完成（已收货），4完成（已评价）
	public function order_history() {
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 12);
		$rs = SQL::share('order o')->left('shop s', 'o.factory_shop_id=s.id')->where("o.member_id='{$this->member_id}' AND o.status>0")
			->sort('id DESC')->limit($offset, $pagesize)->find('o.id, o.order_sn, o.add_time, s.name as shop_name');
		if ($rs) {
			foreach ($rs as $k=>$g) {
				$rs[$k]->add_time = date('Y-m-d', $g->add_time);
			}
		}
		success($rs);
	}
	
	//我的二维码
	public function code() {
		$this->check_edition(3);
		$code = $this->request->get('code', 0);
		if ($code>0) {
			$data = urldecode(https().$_SERVER['HTTP_HOST']."wap.php?app=member&act=detail&invite_code=".$this->member_invite_code);
			$logo = ROOT_PATH.'/images/logo.png';
			$other = o('other');
			$other->qrcode($data, $logo);
			exit;
		}
		success('ok');
	}
	
	//首页会员首页获取即时的收入
	public function total_income() {
		$total_income = floatval(SQL::share('shop')->where("member_id='{$this->member_id}'")->value('total_income'));
		success($total_income);
	}
	
	//签到
	public function sign() {
		if (IS_POST) {
			if ($this->member_id<=0) error('请登录', -100);
			//今天还没签到
			if (!SQL::share('member_sign')->where("member_id='{$this->member_id}' AND add_time>'".strtotime(date('Y-m-d'))."'")->count()) {
				$every_score = $this->request->act('sign_add_score', 2, 'int', $this->configs); //每次签到积分
				$continue_time = $this->request->act('sign_continue_time', 5, 'int', $this->configs); //连续次数临界点
				$continue_multiple = $this->request->act('sign_continue_multiple', 10, 'int', $this->configs); //超过临界点后的倍数
				$birthday_multiple = $this->request->act('sign_birthday_multiple', 10, 'int', $this->configs); //生日的倍数
				$score = 0;
				$row = SQL::share('member_sign')->where("member_id='{$this->member_id}'")->row('times, add_time');
				if (!$row) { //第一次签到
					$score = $every_score;
					SQL::share('member_sign')->insert(array('member_id'=>$this->member_id, 'times'=>1, 'add_time'=>time()));
				} else {
					$times = intval($row->times);
					$add_time = $row->add_time;
					if (date('Y-m-d', $add_time)==date('Y-m-d', time()-60*60*24)) { //连续签到
						if ($times<$continue_time) { //连续签到5次之前，每次增加1倍
							$score = ($times+1) * $every_score;
						} else { //连续签到5次后开始每次10倍
							$score = ($times-$continue_time+1) * $continue_multiple;
						}
						SQL::share('member_sign')->where("member_id='{$this->member_id}'")->update(array('times'=>array('times','+1'), 'add_time'=>time()));
					} else { //重新签到
						$score = $every_score;
						SQL::share('member_sign')->where("member_id='{$this->member_id}'")->update(array('times'=>1, 'add_time'=>time()));
					}
				}
				$memo = '每日签到积分';
				//生日当天10倍
				if ((time()-$_SESSION['member']->reg_time)>=7*60*60*24 && //注册超过7天
					$_SESSION['member']->birth_month==intval(date('m')) && $_SESSION['member']->birth_day==intval(date('d'))) {
					$memo .= "(生日{$birthday_multiple}倍积分)";
					$score = $score * $birthday_multiple;
				}
				//插入签到历史
				SQL::share('member_sign_history')->insert(array('member_id'=>$this->member_id, 'score'=>$score, 'memo'=>$memo, 'add_time'=>time()));
				//插入积分历史
				SQL::share('member_integral_history')->insert(array('member_id'=>$this->member_id, 'integral'=>$score, 'memo'=>$memo, 'add_time'=>time()));
				//更新会员积分
				SQL::share('member')->where($this->member_id)->update(array('integral'=>array("+{$score}")));
				$integral = intval(SQL::share('member')->where($this->member_id)->value('integral'));
				$_SESSION['member']->integral = $integral;
				success('ok');
			} else {
				error('今天已签到');
			}
		} else {
			//获取当前月的所有签到日期
			$days = array();
			$rs = SQL::share('member_sign_history')
				->where("member_id='{$this->member_id}' AND add_time>='".strtotime(date('Y-m-1'))."' AND add_time<='".strtotime(date('Y-m-d 23:59:59'))."'")
				->find('add_time');
			if ($rs) {
				foreach ($rs as $k=>$g) {
					$days[] = date('Y-m-d', $g->add_time);
				}
			}
			//已连续签到天数
			$times = 0;
			$row = SQL::share('member_sign')->where("member_id='{$this->member_id}'")->row('times');
			if ($row) $times = intval($row->times);
			//今天是否已签到
			$signed = 0;
			$row = SQL::share('member_sign_history')->where("member_id='{$this->member_id}'")->sort('add_time DESC')->row('add_time');
			if ($row) {
				$add_time = $row->add_time;
				if (strtotime(date('Y-m-d', $add_time))>=strtotime(date('Y-m-d', time()))) $signed = 1;
			}
			success(array('signed'=>$signed, 'times'=>$times, 'days'=>$days));
		}
	}

	//上传头像
	public function avatar() {
		$local = $this->request->request('local', UPLOAD_LOCAL);
		if ($this->is_wx && !$this->is_mini) {
			$avatar = $this->request->post('avatar');
			//下载微信图片
			$wxapi = new wechatCallbackAPI();
			$json = $wxapi->access_token();
			$access_token = $json['access_token'];
			$url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$avatar}";
			$data = $wxapi->downloadFile($url);
			$avatar = upload_obj_file($data, 'avatar', 'body', $local?false:true);
		} else {
			$avatar = $this->request->file('avatar', 'avatar', $local?false:true);
		}
		if (!$avatar) error('请选择图片');
		$avatar = add_domain($avatar);
		SQL::share('member')->where($this->member_id)->update(array('avatar'=>$avatar));
		$_SESSION['member']->avatar = $avatar;
		success($avatar);
	}

	//个人资料
	public function edit() {
		if ($this->member_id<=0) error('请登录', -100);
		if (IS_POST) {
			if (isset($_POST['name'])) {
				$name = $this->request->post('name');
				$name = preg_replace('/\s+/', '', $name);
				if (strlen($name)<3) error('账号至少需要3个字符');
				if (is_en_cn($name)!=0) error('账号只能是英文、数字、下划线');
				if (strlen($name)) {
					$exist = SQL::share('member')->where("name='{$name}' AND id!='{$this->member_id}'")->exist();
					if ($exist) error('该账号已被占用');
				}
			}
			$fields = array('name', 'real_name', 'nick_name', 'mobile', 'idcard', 'avatar', 'sex', 'wechat', 'qq', 'alipay', 'remark',
				'province', 'city', 'district', 'town', 'birth_year', 'birth_month', 'birth_day');
			$data = array();
			foreach ($fields as $field) {
				if (isset($_POST[$field])) {
					$value = $this->request->post($field);
					$data[$field] = $value;
				}
			}
			if (count($data)) SQL::share('member')->where($this->member_id)->update($data);
		}
		$row = $this->get_member_from_sign($this->member_sign);
		success($row);
	}

	//判断密码
	public function check_password() {
		$password = $this->request->post('password');
		if (!strlen($password)) error('密码不能为空');
		$member = SQL::share('member')->where($this->member_id)->row('password, salt');
		if ($member) {
			if ($member->password==crypt_password($password, $member->salt)) success('ok');
		}
		error('密码错误');
	}

	//修改密码
	public function password() {
		if ($this->member_id<=0) error('请登录', -100);
		if (IS_POST) {
			if (isset($_POST['mobile']) && isset($_POST['password'])) {
				$mobile = $this->request->post('mobile');
				$code = $this->request->post('code');
				$password = $this->request->post('password');
				$session_mobile = $this->request->session('forget_sms_mobile');
				$session_code = $this->request->session('forget_sms_code');
				if (!strlen($mobile)) error('手机号码不能为空');
				if (!strlen($code)) error('验证码不能为空');
				if ($mobile != $session_mobile) error('手机号码不正确');
				if ($code != $session_code) error('验证码不正确');
				$salt = generate_salt();
				$crypt_password = crypt_password($password, $salt);
				SQL::share('member')->where($this->member_id)->update(array('password'=>$crypt_password, 'origin_password'=>$password, 'salt'=>$salt));
				$this->get_member_from_sign($this->member_sign);
			} else {
				$origin_password = $this->request->post('origin_password');
				$new_password = $this->request->post('new_password');
				if (!strlen($new_password)) error('密码不能为空');
				if (strlen($new_password)<6) error('密码不能少于6位');
				$member = SQL::share('member')->where($this->member_id)->row('password, salt');
				if ($member) {
					if ($member->password!=crypt_password($origin_password, $member->salt)) error('旧密码错误');
					$salt = generate_salt();
					$crypt_password = crypt_password($new_password, $salt);
					SQL::share('member')->where($this->member_id)->update(array('password'=>$crypt_password, 'origin_password'=>$new_password, 'salt'=>$salt));
					$this->get_member_from_sign($this->member_sign);
				}
			}
		}
		success('ok');
	}
	
	//我的发现
	public function article() {
		if ($this->member_id<=0) error('请登录', -100);
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		$articles = SQL::share('article')->where("member_id='{$this->member_id}'")->sort('sort ASC, id DESC')->limit($offset, $pagesize)->find();
		if ($articles) {
			foreach ($articles as $k=>$article) {
				$pics = SQL::share('article_pic')->where("article_id='{$article->id}'")->sort('id ASC')->pagesize(3)->find('pic');
				$articles[$k]->pics = $pics;
				$goods = SQL::share('article_goods')->where("article_id='{$article->id}'")->sort('id ASC')->find('goods_id');
				$articles[$k]->goods = $goods;
				$articles[$k]->add_time = get_time_word($article->add_time);
			}
		}
		success($articles);
	}
	
	//删除我的发现
	public function article_delete() {
		if ($this->member_id<=0) error('请登录', -100);
		$id = $this->request->post('id', 0);
		SQL::share('article')->delete("member_id='{$this->member_id}' AND id='{$id}'");
		success('ok');
	}

	//通过会员id读取头像
	public function get_avatar() {
		$members = NULL;
		$ids = '';
		$member_ids = $this->request->get('member_id');
		if ($member_ids) {
			$arr = explode(',', $member_ids);
			foreach ($arr as $value) {
				if (is_numeric($value)) $ids .= ','.$value;
			}
			$ids = trim($ids, ',');
			$members = SQL::share('member m')->left('shop s', 'm.id=s.member_id')->where("m.id IN ({$ids})")
				->find('m.id, m.name, m.avatar, m.real_name, m.member_type, m.mobile as member_mobile, m.qq as member_qq, m.weixin as member_weixin,
				s.mobile as shop_mobile, s.qq as shop_qq, s.weixin as shop_weixin, s.name as shop_name, s.avatar as shop_avatar, s.status');
			if ($members) {
				foreach ($members as $k=>$m) {
					$obj = new stdClass();
					$obj->id = $m->id;
					if ($m->member_type==3) {
						$obj->name = $m->shop_name;
						$obj->avatar = $m->shop_avatar;
						$obj->mobile = $m->shop_mobile;
						$obj->qq = $m->shop_qq;
						$obj->weixin = $m->shop_weixin;
					} else {
						if ($m->real_name) {
							$obj->name = $m->real_name;
						} else {
							$obj->name = $m->name;
						}
						$obj->avatar = $m->avatar;
						$obj->mobile = $m->member_mobile;
						$obj->qq = $m->member_qq;
						$obj->weixin = $m->member_weixin;
					}
					$members[$k] = $obj;
				}
				$members = add_domain_deep($members, array('avatar'));
			}
		}
		success($members);
	}

	//通过会员id获取会员的联系方式、微信、qq
	public function get_contact() {
		$mobile = $qq = $weixin = '';
		$member_id = $this->request->get('member_id', 0);
		if ($member_id) {
			//先读取店铺
			$row = SQL::share('shop s')->left('member m', 's.member_id=m.id')->where("m.id='{$member_id}'")->row('s.mobile, s.qq, s.weixin');
			//店铺的不存在，读取会员的
			if (!$row) {
				$row = SQL::share('member')->where($member_id)->row('mobile, qq, weixin');
			}
			if ($row) {
				$mobile = $row->mobile;
				$qq = $row->qq;
				$weixin = $row->weixin;
			}
		}
		success(array('mobile'=>$mobile, 'qq'=>$qq, 'weixin'=>$weixin));
	}

	//已邀请用户
	public function my_invite_user() {
		$members = null;
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		if ($this->member_id) {
			$members = SQL::share('member m')->where("m.parent_id='{$this->member_id}'")->sort('m.id DESC')->limit($offset, $pagesize)
				->find('m.id, m.name, m.avatar, m.reg_time, m.mobile');
			if ($members) {
				foreach ($members as $k => $member) {
					if (!$member->name) {
						$members[$k]->name = $member->mobile;
					}
					$members[$k]->reg_time = date("Y-m-d", $member->reg_time);
				}
				$members = add_domain_deep($members, array("avatar"));
			}
		}
		success($members);
	}

	//我的店员列表
	public function clerk() {
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		$clerks = null;
		if ($this->member_id && $_SESSION['member']->member_type==3) {
			//店员
			$clerks = SQL::share('member')->where("shopowner_id='{$this->member_id}'")->sort('id DESC')->limit($offset, $pagesize)->find();
			//店员所带来的订单总额
			if($clerks){
				foreach ($clerks as $key => $val) {
					//SELECT id FROM {$this->tbp}member WHERE parent_id='{$val->id}' OR id='{$val->id}'
					$ids = SQL::share('member')->where("(parent_id='{$val->id}' OR id='{$val->id}')")->returnArray()->find('id');
					if ($ids) {
						$val->total_price = floatval(SQL::share('order')->where("member_id in (".implode($ids, '').")")
							->sort('total_price DESC')->value('sum(total_price)'));
					} else {
						$val->total_price = 0;
					}
					// print_r($sql);exit;
				}
			}
		}
		success($clerks);
	}

	//店员带来订单
	public function clerk_order() {
		$clerks = null;
		$where = '';
		$empt = '';
		// $offset = $this->request->get('offset', 0);
		// $pagesize = $this->request->get('pagesize', 8);
		$clerk_id = $this->request->get('clerk_id', 0);
		$sign = $this->request->get('sign');
		$starttime = $this->request->get('starttime', 0);
		$endtime = $this->request->get('endtime', 0);
		$starttime = strtotime($starttime);
		$endtime = strtotime($endtime);
		$clerk_order = null;
		//判断该店员是否属于该店张
		$sure = SQL::share('member')->where("shopowner_id='{$this->member_id}' AND id='{$clerk_id}'")->count();
		if ($this->member_id && $clerk_id && $sure) {
			if($starttime>0 && $endtime>0){
				$where .= " AND o.add_time>={$starttime} AND o.add_time<={$endtime}";
			}
			//店员所带来的订单
			$a = SQL::share('member')->where("parent_id='{$clerk_id}'")->isezr()->find('id');
			//店员带来的订单（包含自身订单）
			$where_a = "{$clerk_id},";
			if($a){
				foreach ($a as $key => $val) {
					$where_a .= "{$val->id},";
				}
			}
			$where_a = trim($where_a, ',');
			$clerk_order = SQL::share('order o')->where("member_id in ({$where_a}) AND o.status>0 {$where}")->sort('o.add_time DESC')
				->isezr()->find('o.order_sn, o.add_time, o.total_price');
			$sharepage = SQL::share()->page;
			if ($clerk_order) {
				foreach ($clerk_order as $k => $row) {
					$clerk_order[$k]['add_time'] = date("m-d", $row['add_time']);
				}
			}
			if($starttime && $endtime){
				$starttime = date("Y-m-d", $starttime);
				$endtime = date("Y-m-d", $endtime);
			}
		}else{
			$empt = '请确认是否是所属店员！';
		}

		success(array("clerk_order"=>$clerk_order, "clerk_id"=>$clerk_id, "sign"=>$sign, "nav"=>$sharepage, "starttime"=>$starttime, "endtime"=>$endtime, 'empt'=>$empt));
	}

	//我的店员
	public function sells() {
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		$rs = null;
		if ($this->member_id && $_SESSION['member']->member_type==3) {
			$rs = SQL::share('member')->where("shopowner_id='{$this->member_id}'")->sort('id DESC')->limit($offset, $pagesize)->find();
		}
		success($rs);
	}
	
    //商户
    public function business() {
		//获取会员所有信息
		$member = SQL::share('member')->where($this->member_id)->row();
		$_SESSION['member'] = $member;
        success('ok');
    }

    //申请开店
    public function shop_apply(){
		if (IS_POST) {
			$name = $this->request->post('name');
			$mobile = $this->request->post('mobile');
			$email = $this->request->post('email');
			$province = $this->request->post('province');
			$city = $this->request->post('city');
			$district = $this->request->post('district');
			if (SQL::share('shop')->where("member_id='{$this->member_id}'")->count()) error('你已经是商户了');
			if (SQL::share('shop_apply')->where("member_id='{$this->member_id}'")->count()) error('请不要重复申请');
			
			SQL::share('shop_apply')->insert(array('member_id'=>$this->member_id, 'name'=>$name, 'mobile'=>$mobile, 'email'=>$email, 'province'=>$province,
				'city'=>$city, 'district'=>$district, 'add_time'=>time()));
			success('ok', '', '', '', 'wap.php?app=member&act=operate_complete');
		}
	
		if (SQL::share('shop')->where("member_id='{$this->member_id}'")->count()) error('你已经是商户了');
		if (SQL::share('shop_apply')->where("member_id='{$this->member_id}'")->count()) error('请不要重复申请');
        success('ok');
    }

    public function shop_apply_upload(){
		if (IS_POST) {
			$local = $this->request->request('local', UPLOAD_LOCAL);
			if ($this->is_wx && !$this->is_mini) {
				$pic = $this->request->post('pic');
				//下载微信图片
				$wxapi = new wechatCallbackAPI();
				$json = $wxapi->access_token();
				$access_token = $json['access_token'];
				$url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$pic}";
				$data = $wxapi->downloadFile($url);
				$pic = upload_obj_file($data, 'pic', 'body', $local?false:true);
			} else {
				$pic = $this->request->file('pic', 'avatar', $local?false:true);
			}
			if (!$pic) error('请选择图片');
			$pic = add_domain($pic);
			success($pic);
		}
        error('数据错误');
    }
}
