<?php
declare (strict_types = 1);

namespace app\gm\controller;

use app\gm\controller\Core;
use app\model\MemberMod;
use think\facade\Db;
use think\facade\View;
use think\Request;

class Member extends Core
{
	protected $member_mod;
	
	public function __construct(MemberMod $memberMod) {
		parent::__construct();
		$this->member_mod = $memberMod;
	}
	
	//会员列表
	public function index() {
		$where = [];
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
			$where[] = ['m.status', '=', $status];
		}
		if (strlen($member_type)) {
			$where[] = ['m.member_type', '=', $member_type];
		}
		if (strlen($keyword)) {
			$where[] = Db::raw("m.id='{$keyword}' OR m.name LIKE '%{$keyword}%' OR m.mobile LIKE '%{$keyword}%' OR m.nick_name LIKE '%{$keyword}%'");
		}
		if (strlen($member_id)) {
			$where[] = ['m.id', '=', $member_id];
		}
		if (strlen($shop_id)) {
			$where[] = ['m.shop_id', '=', $shop_id];
		}
		if (strlen($begin_date)) {
			$where[] = ['m.reg_time', '>=', strtotime($begin_date)];
		}
		if (strlen($end_date)) {
			$where[] = ['m.reg_time', '<=', strtotime($end_date)];
		}
		if (strlen($invite_code)) {
			$where[] = ['m.invite_code', '=', $invite_code];
		}
		if (strlen($grade_id)) {
			$where[] = ['m.grade_id', '=', $grade_id];
		}
		$rs = Db::name('member')->alias('m')->where($where)->order('m.id', 'DESC')->field("m.*, '' as invitor")->paginate([
			'list_rows' => 10,
			'query' => request()->param()
		])->each(function($item) {
			$item['status_name'] = $this->member_mod->status_name($item['status']);
			$item['member_type_name'] = $this->member_mod->member_type($item['member_type']);
			if ($item['invite_code']) {
				$invitor = Db::name('member')->where("mobile='{$item['invite_code']}'")->field('mobile, name')->find();
				if ($invitor) {
					if ($invitor['name']) {
						$item['invitor'] = $invitor['name'];
					} else {
						$item['invitor'] = $invitor['mobile'];
					}
				}
			}
			$item['url'] = urlencode(https().$_SERVER['HTTP_HOST']."/wap/?reseller={$item['id']}");
			return $item;
		});
		$rs = add_domain_deep($rs, array('avatar'));
		View::assign('rs', $rs);
		View::assign('is_member_add', core::hasMenu('member', 'add'));
		setViewPage($rs);
		return success('', 'SUCCESS', 0, compact('status', 'member_type', 'keyword', 'member_id', 'shop_id', 'begin_date', 'end_date', 'invite_code', 'grade_id'));
	}
	
	//编辑
	public function add() {
		return $this->edit();
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
			$avatar = $this->request->file('member', 'avatar', UPLOAD_THIRD);
			$status = $this->request->post('status', 0);
			if ($member_type != 2) $shopowner_id = 0;
			if ($grade_score<0) $grade_score = 0;
			if (!strlen($name)) return error('请输入账号');
			if ($id<=0 && !strlen($password)) return error('请输入密码');
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
				Db::name('member')->where('id', $id)->update($data);
				//通过等级积分检测是否需要等级升级处理
				if ($this->edition>2 && $grade_score>0) $this->member_mod->update_grade_from_score($id);
			} else if ($this->has_menu('member', 'add')) {
				$data['reg_time'] = time();
				$data['reg_ip'] = $this->ip;
				Db::name('member')->insert($data);
			}
			location('/member');
		} else if ($id>0) {
			$row = Db::name('member')->where('id', $id)->find();
			$row = add_domain_deep($row, array('avatar'));
		} else {
			$row = t('member');
		}
		View::assign('row', $row);
		//店长
		$shopowner = Db::name('member')->where('member_type', 3)->field('id, name, nick_name')->find();
		View::assign('shopowner', $shopowner);
		View::assign('is_member_add', core::hasMenu('member', 'add'));
		return success('ok', 'edit.html');
	}
	
	//删除
	public function delete() {
		$id = $this->request->get('id', 0);
		Db::name('member')->where('id', $id)->delete();
		location('/member');
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
		$rs = Db::name('member m')->where($where)->order('m.id', 'DESC')->field("m.*, '' as invitor")->find();
		if ($rs) {
			foreach ($rs as $row) {
				$row['status'] = $this->member_mod->status_name($row['status']);
				// $row['member_type_name'] = $this->member_mod->member_type($row['member_type']);
				if ($row['reg_time']) $row['reg_time'] = date('Y-m-d H:i:s', $row['reg_time']);
				if ($row['last_time']) $row['last_time'] = date('Y-m-d H:i:s', $row['last_time']);
			}
			export_excel($rs, $fields);
		}
	}
	
	//邀请人排行
	public function invitor_ranks() {
		$invitors = array();
		$mobiles = "";
		$where = base64_decode($this->request->get('where'));
		$rs = Db::name('member m')->where("m.invite_code<>'' {$where}")->order('m.id', 'DESC')->field('m.invite_code')->find();
		if ($rs) {
			foreach ($rs as $row) {
				if (array_key_exists($row['invite_code'], $invitors)) {
					$invitors[$row['invite_code']] += 1;
				} else {
					$invitors[$row['invite_code']] = 1;
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
		if (strlen($status)) {
			$where[] = ['m.status', '=', $status];
		}
		if (strlen($member_type)) {
			$where[] = ['m.member_type', '=', $member_type];
		}
		if (strlen($keyword)) {
			$where[] = Db::raw("m.name LIKE '%{$keyword}%' OR m.mobile LIKE '%{$keyword}%' OR m.nick_name LIKE '%{$keyword}%'");
		}
		if (strlen($member_id)) {
			$where[] = ['m.id', '=', $member_id];
		}
		if (strlen($begin_date)) {
			$where[] = ['m.reg_time', '>=', strtotime($begin_date)];
		}
		if (strlen($end_date)) {
			$where[] = ['m.reg_time', '<=', strtotime($end_date)];
		}
		if (strlen($invite_code)) {
			$where[] = ['m.invite_code', '=', $invite_code];
		}
		$rs = Db::name('member')->alias('m')->where($where)->order('m.id', 'DESC')->field("m.*, '' as invitor")->paginate([
			'list_rows' => 10,
			'query' => request()->param()
		])->each(function($item) {
			$item['status_name'] = $this->member_mod->status_name($item['status']);
			$item['member_type_name'] = $this->member_mod->member_type($item['member_type']);
			if ($item['invite_code']) {
				$invitor = Db::name('member')->where('mobile', $item['invite_code'])->field('mobile, name')->find();
				if ($invitor) {
					if ($invitor['name']) {
						$item['invitor'] = $invitor['name'];
					} else {
						$item['invitor'] = $invitor['mobile'];
					}
				}
			}
		});
		View::assign('rs', $rs);
		return success('ok', 'SUCCESS', 0, compact('status', 'member_type', 'keyword', 'member_id', 'begin_date', 'end_date', 'invite_code'));
	}
}
