<?php
class cron extends core {
	
	public function __construct() {
		parent::__construct();
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
	}
	
	//作业总汇
	public function jobs() {
		$this->send_customer();
	}
	
	//群发客服消息
	public function send_customer() {
		$_count = 0;
		$list = SQL::share('wechat_customer')->where("status=0")->comparetime('s', 'send_time', '>=0')->find('id, title, memo, pic, url');
		if ($list) {
			$wxapi = new wechatCallbackAPI();
			foreach ($list as $l) {
				$count = 0;
				$wechat = SQL::share('wechat_customer_mp wcm')->left('wechat w', 'wcm.wechat_id=w.id')->where("wcm.customer_id='{$l->id}'")->find('wcm.id, wcm.wechat_id, w.appid');
				if ($wechat) {
					foreach ($wechat as $m) {
						$mpcount = 0;
						$user = SQL::share('wechat_user')->where("wechat_id='{$m->wechat_id}'")->comparetime('h', 'add_time', '<48')->find('openid');
						foreach ($user as $g) {
							$json = $wxapi->authorizer_access_token('', $m->appid, true);
							if (!$json) break;
							$access_token = $json['authorizer_access_token'];
							$data = array();
							$data['touser'] = $g->openid;
							$data['msgtype'] = 'news';
							$data['news'] = array('articles'=>array(
								array(
									'title' => $l->title,
									'description' => $l->memo,
									'url' => $l->url,
									'picurl' => add_domain($l->pic)
								)
							));
							$data = json_encode($data, JSON_UNESCAPED_UNICODE);
							$json = $wxapi->requestData('post', "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}", $data, true, true);
							if (isset($json['errcode']) && intval($json['errcode'])!=0) {
								$wxapi->log(json_encode($json), true);
							} else {
								$count++;
								$mpcount++;
							}
						}
						SQL::share('wechat_customer_mp')->where($m->id)->update(array('count'=>$mpcount, 'status'=>1));
					}
					$_count++;
				}
				$status = 1;
				SQL::share('wechat_customer')->where($l->id)->update(compact('count', 'status'));
			}
		}
		if ($_count>0) write_log('SEND_CUSTOMER COUNT '.$_count, '/temp/cron.txt', false, true);
	}
	
	//更新客服消息回复图片素材
	public function update_custom_media() {
		$total = 0;
		$count = 0;
		$rs = SQL::share('miniprogram_config mc')->left('miniprogram m', 'mc.miniprogram_id=m.id')
			->where("config_id IN (146,157,168,179) AND content LIKE '%|/uploads/wechat/%'")->find('mc.id, content, appid, component_id');
		if ($rs) {
			$total = count($rs);
			$wxapi = new wechatCallbackAPI();
			foreach ($rs as $g) {
				$content = explode('|', $g->content);
				if (time() - intval($content[2]) >= 60*60*24 && file_exists(PUBLIC_PATH.$content[1])) {
					$component = SQL::share('component')->where($g->component_id)->row();
					if (!$component) continue;
					$wxapi->WX_THIRD = array(
						'appid'=>$component->appid,
						'secret'=>$component->appsecret,
						'token'=>$component->token,
						'aeskey'=>$component->aeskey
					);
					$content = $content[1];
					$_content = $wxapi->setMedia(PUBLIC_PATH.$content, $g->appid, '', true);
					if (!$_content) continue;
					$content = $_content . '|' . $content . '|' . time();
					SQL::share('miniprogram_config')->where($g->id)->update(compact('content'));
					$count++;
				}
			}
		}
		write_log('UPDATE_CUSTOM_MEDIA COUNT '.$count.', TOTAL '.$total, '/temp/cron.txt', false, true);
	}
	
	//清空今天点击数
	public function clear_today_clicks() {
		SQL::share('article')->update(array('yesterday_clicks'=>SQL::raw('today_clicks'), 'today_clicks'=>0));
		SQL::share('blessing')->update(array('yesterday_clicks'=>SQL::raw('today_clicks'), 'today_clicks'=>0));
		SQL::share('buddha')->update(array('yesterday_clicks'=>SQL::raw('today_clicks'), 'today_clicks'=>0));
		SQL::share('video')->update(array('yesterday_clicks'=>SQL::raw('today_clicks'), 'today_clicks'=>0));
		SQL::share('admin_miniprogram_article')->update(array('yesterday_clicks'=>SQL::raw('today_clicks'), 'today_clicks'=>0));
		SQL::share('admin_article')->update(array('yesterday_clicks'=>SQL::raw('today_clicks'), 'today_clicks'=>0));
		echo 'CLEAR TODAY CLICKS';
	}
}
