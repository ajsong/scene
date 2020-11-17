<?php
class weixin_model extends base_model {

	public function __construct() {
		parent::__construct();
	}
	
	//发送微信模板消息给用户
	public function send_template($member_id, $parent_id=0, $type=1) {
		if ($member_id<=0) return false;
		$member = SQL::share('member m')->left('member_thirdparty mt', 'mt.member_id=m.id')
			->where("m.id='{$member_id}' AND mt.type='wechat'")->row('nick_name, grade_id, grade_time, parent_id, mt.mark as openid');
		if (!$member || !strlen($member->openid)) return false;
		if (!SQL::share()->tableExist('weixin_template')) return false;
		$template = SQL::share('weixin_template')->where("type_id='{$type}'")->row('template_id');
		if (!$template) return false;
		$url = '';
		$miniprogram = array();
		$data = array();
		switch ($type) {
			case 1: //订单支付成功
				$row = SQL::share('order')->where($parent_id)->row();
				if (!$row) return false;
				$order_goods = SQL::share('order_goods')->where("order_id='{$parent_id}'")->row('goods_name');
				$url = $GLOBALS['domain']."/wap/?app=order&act=detail&id={$parent_id}";
				if ($row->is_mini) $miniprogram = array('appid'=>WX_PROGRAM_APPID, 'pagepath'=>"pages/order/detail?id={$parent_id}");
				$data = array(
					'first'=>array('value'=>"您的订单{$row->order_sn}已支付成功", 'color'=>'#173177'),
					'orderMoneySum'=>array('value'=>"{$row->total_price}元", 'color'=>'#173177'),
					'orderProductName'=>array('value'=>$order_goods->goods_name, 'color'=>'#173177'),
					'Remark'=>array('value'=>'请耐心等待卖家发货。', 'color'=>'#173177')
				);
				break;
			case 2: //商品已发货
				$row = SQL::share('order')->where($parent_id)->row();
				if (!$row) return false;
				$data = array(
					'first'=>array('value'=>"您的订单{$row->order_sn}商品已发货了。", 'color'=>'#173177'),
					'delivername'=>array('value'=>$row->shipping_company, 'color'=>'#173177'),
					'ordername'=>array('value'=>$row->shipping_number, 'color'=>'#173177'),
					'remark'=>array('value'=>'如有疑问，请尽快联系客服。', 'color'=>'#173177')
				);
				break;
			case 3: //订单退款
				$row = SQL::share('order')->where($parent_id)->row();
				if (!$row) return false;
				$refund = SQL::share('order_refund')->where("order_id='{$parent_id}'")->row('reason, price');
				$data = array(
					'first'=>array('value'=>"您好，您的订单{$row->order_sn}已成功退款。", 'color'=>'#173177'),
					'reason'=>array('value'=>$refund->reason, 'color'=>'#173177'),
					'refund'=>array('value'=>"{$refund->price}元", 'color'=>'#173177'),
					'remark'=>array('value'=>'如有疑问，请尽快联系客服。', 'color'=>'#173177')
				);
				break;
			case 4: //用户提现
				$row = SQL::share('withdraw')->where($parent_id)->row();
				if (!$row) return false;
				$data = array(
					'first'=>array('value'=>"您好，您的提现操作已经成功。", 'color'=>'#173177'),
					'keyword1'=>array('value'=>date('Y年m月d日 H:i:s', $row->add_time), 'color'=>'#173177'),
					'keyword2'=>array('value'=>number_format($row->withdraw_money,2,'.','')."元", 'color'=>'#173177'),
					'remark'=>array('value'=>'感谢您的使用。', 'color'=>'#173177')
				);
				break;
		}
		if (!is_array($data) || !count($data)) return false;
		$wxapi = new wechatCallbackAPI();
		$wxapi->sendTemplateMessage($member->openid, $template->template_id, $data, $url, $miniprogram, '', false);
		return true;
	}
}
