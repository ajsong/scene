<?php
declare (strict_types = 1);

namespace app\model;

use think\facade\Db;
use think\Model;

/**
 * @mixin \think\Model
 */
class MemberMod extends Model
{
	protected $name = 'member';
	
	//设置用户的微信openid
	public function set_openid() {
		if ($this->member_id<=0) return;
		if (strlen(WX_APPID) && strlen(WX_SECRET) && $this->is_wx && !$this->is_mini) {
			$party = Db::name('member_thirdparty')->where([
				['member_id', '=', $this->member_id],
				['type', '=', 'wechat'],
			])->field('mark')->find();
			if (!$party || !strlen($party->mark)) {
				$wx = new \wechatCallbackAPI\wechatCallbackAPI();
				$openid = $wx->openid('', WX_APPID, WX_SECRET);
				Db::name('member_thirdparty')->insert(array('member_id'=>$this->member_id, 'type'=>'wechat', 'mark'=>$openid));
			}
		}
	}
	
	//生成用户的邀请码
	public function new_invite_code($member_id) {
		$new_invite_code = 0;
		do {
			$new_invite_code = rand(10000,50000) + $member_id;
		} while (Db::name('member')->where('invite_code', $new_invite_code)->count());
		Db::name('member')->where('id', $member_id)->update(array('invite_code'=>$new_invite_code));
	}
	
	//获取名称,优先顺序为 nick_name > real_name > name > mobile
	public function get_name($member_id) {
		$member = Db::name('member')->where('id', $member_id)->field('nick_name, real_name, name, mobile')->find();
		if (!$member) return $member_id;
		$name = $member['nick_name'];
		if (!strlen($name)) $name = $member['real_name'];
		if (!strlen($name)) $name = $member['name'];
		if (!strlen($name)) $name = $member['mobile'];
		return $name;
	}
	
	//状态名称
	public function status_name($status) {
		$str = '';
		switch ($status) {
			case 0:$str = '冻结';break;
			case 1:$str = '正常';break;
		}
		return $str;
	}
	
	//类型名称
	public function member_type($type) {
		$str = '';
		switch ($type) {
			case 1:$str = '普通会员';break;
			case 2:$str = '门店店员';break;
			case 3:$str = '门店店长';break;
		}
		return $str;
	}
	
	//会员详情
	public function detail($id) {
		$member = Db::name('member')->where('id', $id)->find();
		if ($member) {
			$member['avatar'] = $member['avatar'] ?? '/images/avatar.png';
		}
		return $member;
	}
	
	//获取佣金和余额
	public function get_yue_and_commission() {
		if ($this->member_id) {
			$member = Db::name('member')->where($this->member_id)->row('money, commission');
			$money = $member->money;
			$commission = $member->commission;
			$_SESSION['member']->money = $money;
			$_SESSION['member']->commission = $commission;
			$total = $money + $commission;
			return floatval($total);
		} else {
			return 0;
		}
	}
	
	//佣金和余额是否足以支付
	public function is_yue_and_commission_enough($price) {
		if ($this->member_id) {
			$member = Db::name('member')->where($this->member_id)->row('`money`, commission');
			$money = $member->money;
			$commission = $member->commission;
			$_SESSION['member']->money = $money;
			$_SESSION['member']->commission = $commission;
			$total = $money + $commission;
			if ($total >= $price) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	//使用余额和佣金支付, type:产生来源，1订单，2充值，3注册邀请
	//支付成功，返回true
	public function pay_with_yue_and_commission($price, $order_id=0, $type=1) {
		if ($this->is_yue_and_commission_enough($price)) {
			$money = $_SESSION['member']->money;
			$commission = $_SESSION['member']->commission;
			$total = $money + $commission;
			$typename = '';
			switch ($type) {
				default:$typename = '订单';break;
			}
			//2016/2/18，先用佣金支付，再用余额支付
			if ($commission >= $price) {
				$remain = $commission - $price;
				if ($remain<=0) $remain=0;
				Db::name('member')->where($this->member_id)->update(array('commission'=>$remain));
				//写入佣金明细
				Db::name('member_commission')->insert(array('member_id'=>$this->member_id, 'commission'=>"-{$price}", 'memo'=>"使用佣金支付{$typename}",
					'type'=>$type, 'parent_id'=>$order_id, 'add_time'=>time(), 'status'=>1));
				//更新订单
				if ($order_id) {
					switch ($type) {
						case 1:
							Db::name('order')->where($order_id)->update(array('used_commission'=>$price));
							break;
					}
				}
				$_SESSION['member']->commission = $remain;
				//需要加上余额一起支付
			} else {
				//设置佣金为0, 并减去余额
				$remain = $total - $price;
				if ($remain<=0) $remain = 0;
				Db::name('member')->where($this->member_id)->update(array('`money`'=>$remain, 'commission'=>0));
				//写入佣金明细
				if ($commission>0) {
					Db::name('member_commission')->insert(array('member_id'=>$this->member_id, 'commission'=>"-{$commission}", 'memo'=>"使用佣金支付{$typename}",
						'type'=>$type, 'parent_id'=>$order_id, 'add_time'=>time(), 'status'=>1));
				}
				//写入余额变动明细
				$used_money = $price - $commission;
				Db::name('member_money_history')->insert(array('member_id'=>$this->member_id, '`money`'=>"-{$used_money}", 'memo'=>"使用余额支付{$typename}",
					'type'=>$type, 'parent_id'=>$order_id, 'add_time'=>time(), 'status'=>1));
				//更新订单
				if ($order_id) {
					switch ($type) {
						case 1:
							Db::name('order')->where($order_id)->update(array('used_commission'=>$commission, 'used_money'=>$used_money));
							break;
					}
				}
				$_SESSION['member']->money = $remain;
				$_SESSION['member']->commission = 0;
			}
			return true;
		} else {
			return false;
		}
	}
	
	//积分抵扣
	//order_min_price, 订单满多少元才可用
	//order_min_integral, 会员现时最少多少积分才可用
	//order_integral_money, 多少积分抵扣1元
	//order_integral_total_percent, 积分只可抵扣总价格的百分率，小数形式
	public function order_integral_check($member_id=0, $price=0){
		if (!isset($this->configs['order_min_price']) ||
			!isset($this->configs['order_min_integral']) ||
			!isset($this->configs['order_integral_money']) ||
			!isset($this->configs['order_integral_total_percent'])) return false;
		if ($member_id==0) $member_id = $this->member_id;
		//订单总价是否满使用积分的价格
		if ($price < $this->configs['order_min_price']) {
			return false;
		}
		//会员积分是否达标
		$integral = Db::name('member')->where($member_id)->value('integral');
		if ($integral < $this->configs['order_min_integral']) {
			return false;
		}
		return true;
	}
	
	//检测使用积分抵扣
	//成功，返回对象，否则null
	public function check_pay_with_integral($price) {
		if ($this->member_id<=0 ||
			!isset($this->configs['order_min_price']) ||
			!isset($this->configs['order_min_integral']) ||
			!isset($this->configs['order_integral_money']) ||
			!isset($this->configs['order_integral_total_percent'])) return NULL;
		$integral = Db::name('member')->where($this->member_id)->value('integral');
		$integral_pay = new stdClass();
		//积分最多可抵现
		$integral_money = floatval($price * $this->configs["order_integral_total_percent"]);
		//用户积分不够扣除即获取积分最多可抵金额
		if ($integral < ceil($integral_money * $this->configs["order_integral_money"])) {
			$integral_money = $integral / $this->configs["order_integral_money"];
		}
		$integral_pay->integral = ceil($integral_money * $this->configs["order_integral_money"]);
		$integral_pay->money = $integral_money;
		return $integral_pay;
	}
	
	//从会员id获取udid
	public function get_udid_from_member_id($member_id) {
		return trim(Db::name('member')->where($member_id)->value('udid'));
	}
	
	//从店铺id获取udid
	public function get_udid_from_shop_id($shop_id) {
		return trim(Db::name('shop s')->inner('member m', 's.member_id=m.id')->where("s.id='{$shop_id}'")->value('udid'));
	}
	
	//根据等级积分获取等级id
	public function get_grade_from_score($score=0) {
		$grade_id = 0;
		$grades = Db::name('grade')->where('status', 1)->order('score', 'DESC')->field('id, score')->find();
		if ($grades) {
			foreach ($grades as $k=>$g) {
				if ($score >= $g->score) {
					$grade_id = $g->id;
					break;
				}
			}
		}
		return $grade_id;
	}
	
	//通过等级积分检测是否需要等级升级处理
	public function update_grade_from_score($member_id=0) {
		if ($member_id>0) {
			$grade_score = intval(Db::name('member')->where('id', $member_id)->value('grade_score'));
			$grade_id = $this->get_grade_from_score($grade_score);
			Db::name('member')->where('id', $member_id)->update(array('grade_id'=>$grade_id));
			if (session('?member') && session('member.id') && intval(session('member.id'))==intval($member_id)) {
				$member = session('member');
				$member['grade_id'] = $grade_id;
				$member['grade_score'] = $grade_score;
				session('member', $member);
			}
		}
	}
}
