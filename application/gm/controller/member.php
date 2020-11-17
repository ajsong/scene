<?php
class member extends core {
	private $member_mod;

	public function __construct() {
		parent::__construct();
		$this->member_mod = m('member');
	}

	//会员列表
	public function index() {
		$where = '';
		$status = $this->request->get('status');
		$member_type = $this->request->get('member_type');
		$keyword = $this->request->get('keyword');
		$member_id = $this->request->get('member_id');
		$shop_id = $this->request->get('shop_id');
		$begin_date = $this->request->get('begin_date');
		$end_date = $this->request->get('end_date');
		$invite_code = $this->request->get('invite_code');
		$grade_id = $this->request->get('grade_id');
		if (strlen($status)) {
			$where .= " AND m.status='{$status}'";
		}
		if (strlen($member_type)) {
			$where .= " AND m.member_type='{$member_type}'";
		}
		if (strlen($keyword)) {
			$where .= " AND (m.id='{$keyword}' OR m.name LIKE '%{$keyword}%' OR m.mobile LIKE '%{$keyword}%' OR m.nick_name LIKE '%{$keyword}%')";
		}
		if (strlen($member_id)) {
			$where .= " AND m.id='{$member_id}'";
		}
		if (strlen($shop_id)) {
			$where .= " AND m.shop_id='{$shop_id}'";
		}
		if (strlen($begin_date)) {
			$where .= " AND m.reg_time>='".strtotime($begin_date)."'";
		}
		if (strlen($end_date)) {
			$where .= " AND m.reg_time<='".strtotime($end_date)."'";
		}
		if (strlen($invite_code)) {
			$where .= " AND m.invite_code='{$invite_code}'";
		}
		if (strlen($grade_id)) {
			$where .= " AND m.grade_id='{$grade_id}'";
		}
		$rs = SQL::share('member m')->where($where)->isezr()
			->setpages(compact('status', 'member_type', 'keyword', 'member_id', 'shop_id', 'begin_date', 'end_date', 'invite_code', 'grade_id'))
			->sort('m.id DESC')->find("m.*, '' as invitor");
		$sharepage = SQL::share()->page;
		$wherebase64 = SQL::share()->wherebase64;
		if ($rs) {
			foreach ($rs as $key => $row) {
				$rs[$key]->status_name = $this->member_mod->status_name($row->status);
				$rs[$key]->member_type_name = $this->member_mod->member_type($row->member_type);
				if ($row->invite_code) {
					$invitor = SQL::share('member')->where("mobile='{$row->invite_code}'")->row('mobile, name');
					if ($invitor) {
						if ($invitor->name) {
							$rs[$key]->invitor = $invitor->name;
						} else {
							$rs[$key]->invitor = $invitor->mobile;
						}
					}
				}
				$rs[$key]->url = urlencode(https().$_SERVER['HTTP_HOST']."/wap/?reseller={$row->id}");
			}
			$rs = add_domain_deep($rs, array('avatar'));
		}
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->smarty->assign('where', $wherebase64);
		$this->display();
	}

	//编辑
	public function add() {
		$this->edit();
	}
	public function edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$name = $this->request->post('name');
			$password = $this->request->post('password');
			$nick_name = $this->request->post('nick_name');
			$card_sn = $this->request->post('card_sn');
			$mobile = $this->request->post('mobile');
			$qq = $this->request->post('qq');
			$invite_code = $this->request->post('invite_code');
			$money = $this->request->post('money', 0.0);
			$commission = $this->request->post('commission', 0.0);
			$member_type = $this->request->post('member_type', 1);
			$shopowner_id = $this->request->post('shopowner_id', 0);
			$grade_id = $this->request->post('grade_id', 0);
			$grade_score = $this->request->post('grade_score', 0);
			$belong_shop_id = $this->request->post('belong_shop_id', 0);
			$avatar = $this->request->file('member', 'avatar', UPLOAD_LOCAL);
			$status = $this->request->post('status', 0);
			if ($member_type != 2) $shopowner_id = 0;
			if ($grade_score<0) $grade_score = 0;
			if (!strlen($name)) error('请输入账号');
			if ($id<=0 && !strlen($password)) error('请输入密码');
			$data = compact('name', 'avatar', 'nick_name', 'member_type', 'commission', 'mobile', 'qq', 'status', 'invite_code', 'money', 'shopowner_id', 'grade_id', 'grade_score', 'belong_shop_id', 'card_sn');
			if (strlen($password)) {
				$salt = generate_salt();
				$crypt_password = crypt_password($password, $salt);
				$data['password'] = $crypt_password;
				$data['origin_password'] = $password;
				$data['salt'] = $salt;
			}
			//编辑
			if ($id>0) {
				SQL::share('member')->where($id)->update($data);
				//通过等级积分检测是否需要等级升级处理
				if ($this->edition>2 && $grade_score>0) $this->member_mod->update_grade_from_score($id);
			} else if ($this->has_menu('member', 'add')) {
				$data['reg_time'] = time();
				$data['reg_ip'] = $this->ip;
				SQL::share('member')->insert($data);
			}
			location("Location:?app=member&act=index");
		} else if ($id>0) {
			$row = SQL::share('member')->where($id)->row();
			$row = add_domain_deep($row, array('avatar'));
		} else {
			$row = t('member');
		}
		$this->smarty->assign('row', $row);
		//店长
		$shopowner = SQL::share('member')->where("member_type=3")->find('id, name, nick_name');
		$this->smarty->assign('shopowner', $shopowner);
		$this->display('member.edit.html');
	}

	//删除
	public function delete() {
		$id = $this->request->get('id', 0);
		SQL::share('member')->delete($id);
		header("Location:?app=member&act=index");
	}

	//导出
	public function export() {
		set_time_limit(0);
		$fields = array(
			'id'=>'ID',
			'name'=>'会员名称',
			'real_name'=>'真实姓名',
			'mobile'=>'手机号码',
			'invite_code'=>'邀请码',
			'money'=>'余额',
			'status'=>'状态',
			'logins'=>'登录次数',
			'last_time'=>'登录时间',
			'reg_time'=>'注册时间'
		);
		$where = base64_decode($this->request->get('where'));
		$rs = SQL::share('member m')->where($where)->sort('m.id DESC')->find("m.*, '' as invitor");
		if ($rs) {
			foreach ($rs as $key => $row) {
				$rs[$key]->status = $this->member_mod->status_name($row->status);
				// $rs[$key]->member_type_name = $this->member_mod->member_type($row->member_type);
				if ($row->reg_time) $rs[$key]->reg_time = date('Y-m-d H:i:s', $row->reg_time);
				if ($row->last_time) $rs[$key]->last_time = date('Y-m-d H:i:s', $row->last_time);
			}
			export_excel($rs, $fields);
		}
	}

	//邀请人排行
	public function invitor_ranks() {
		$invitors = array();
		$mobiles = "";
		$where = base64_decode($this->request->get('where'));
		$rs = SQL::share('member m')->where("m.invite_code<>'' {$where}")->sort('m.id DESC')->find('m.invite_code');
		if ($rs) {
			foreach ($rs as $key => $row) {
				if (array_key_exists($row->invite_code, $invitors)) {
					$invitors[$row->invite_code] += 1;
				} else {
					$invitors[$row->invite_code] = 1;
				}
			}
			arsort($invitors);
			$invitors = array_slice($invitors, 0, 20, true);
			foreach ($invitors as $mobile => $num) {
				if ($mobiles) {
					$mobiles .= ",{$mobile}";
				} else {
					$mobiles = "{$mobile}";
				}
			}
			//print_r($mobiles);
			print_r($invitors);
		}
	}

	public function choose(){
        $where = '';
		$status = $this->request->get('status');
		$member_type = $this->request->get('member_type', 0);
		$keyword = $this->request->get('keyword');
		$member_id = $this->request->get('member_id', 0);
		$begin_date = $this->request->get('begin_date');
		$end_date = $this->request->get('end_date');
		$invite_code = $this->request->get('invite_code');
		if (strlen($begin_date)) {
			$where .= " AND m.reg_time>='".strtotime($begin_date)."'";
		}
		if (strlen($end_date)) {
			$where .= " AND m.reg_time<='".strtotime($end_date)."'";
		}
		if ($keyword) {
			$where .= " AND (m.name LIKE '%{$keyword}%' OR m.mobile LIKE '%{$keyword}%')";
		}
		if (strlen($member_id)) {
			$where .= " AND m.id='{$member_id}'";
		}
		if (strlen($status)) {
			$where .= " AND m.status='{$status}'";
		}
		if ($member_type) {
			$where .= " AND m.member_type='{$member_type}'";
		}
		if ($invite_code) {
			$where .= " AND m.invite_code='{$invite_code}'";
		}
		$rs = SQL::share('member m')->where($where)->isezr()
			->setpages(compact('status', 'member_type', 'keyword', 'member_id', 'begin_date', 'end_date', 'invite_code'))
			->sort('m.id DESC')->find("m.*, '' as invitor");
		$sharepage = SQL::share()->page;
		$wherebase64 = SQL::share()->wherebase64;
		if ($rs) {
			foreach ($rs as $key => $row) {
				$rs[$key]->status_name = $this->member_mod->status_name($row->status);
				$rs[$key]->member_type_name = $this->member_mod->member_type($row->member_type);
				if ($row->invite_code) {
					$invitor = SQL::share('member')->where("mobile='{$row->invite_code}'")->row('mobile, name');
					if ($invitor) {
						if ($invitor->name) {
							$rs[$key]->invitor = $invitor->name;
						} else {
							$rs[$key]->invitor = $invitor->mobile;
						}
					}
				}
			}
		}
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->smarty->assign('where', $wherebase64);
		$this->display();
    }
}

