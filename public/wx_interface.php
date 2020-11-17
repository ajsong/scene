<?php
define('DIRNAME', 'api');
define('APP_PATH', APPLICATION_PATH . '/' . DIRNAME . '/controller');
require_once(APPLICATION_PATH . '/helper.php');

$signature = $request->get('signature');
$timestamp = $request->get('timestamp');
$nonce = $request->get('nonce');
$echostr = $request->get('echostr');
$component_appid = $request->get('component_appid');

$wxapi = new wechatCallbackAPI();
//write_log("GET:\n".json_encode($_GET));
//$wxapi->log("POST:\n".json_encode($_POST));
//write_log(file_get_contents('php://input'));
if (strlen($signature) && strlen($timestamp) && strlen($nonce) && strlen($echostr)) $wxapi->valid(); //验证

$callback = NULL;
$act = $request->request('act');
if (in_array($act, array('weixin_auth', 'mp_auth', 'component_auth', 'getcode', 'get_session_key', 'miniprogram'))) {
	$callback = $act;
	if ($act=='weixin_auth') $callback = NULL;
	if ($act=='get_session_key') {
		$_appId = get_header('Appid');
		if ($_appId) {
			$wechat = SQL::share('miniprogram')->where("appid='{$_appId}'")->row();
			$wxapi->WX_THIRD = array(
				'appid'=>$wechat->appid,
				'secret'=>$wechat->appsecret
			);
		}
	}
	//第三方接管的授权登录,appid为公众号AppID
	//www.website.com/wx_interface?act=mp_auth&appid=wx092909739421988d
	//电脑端打开进行公众号授权给第三方开发平台
	//www.website.com/wx_interface?act=component_auth
	/*if ($act=='component_auth') {
		if ( !(isset($_SESSION['member']) && is_object($_SESSION['member']) && intval($_SESSION['member']->id)>0) ) {
			$html = '<script>if(typeof window.top.needLogin === "function")window.top.needLogin();</script>';
			exit($html);
		}
	}*/
}
if (strlen($component_appid)) {
	$component = SQL::share('component')->where("appid='{$component_appid}'")->row();
	$wxapi->WX_THIRD = array(
		'appid'=>$component->appid,
		'secret'=>$component->appsecret,
		'token'=>$component->token,
		'aeskey'=>$component->aeskey
	);
}
$wxapi->responseMsg($callback); //获取事件推送

//授权登录后执行
function mp_auth($json){
	global $wxapi, $db, $tbp;
}

//APP端传递code过来换取access_token与获取用户资料
function getcode($json){
	global $wxapi, $request;
	$member_id = $request->get('member_id', 0);
	if (!$member_id) {
		$error = array('errcode'=>10001, 'errmsg'=>'lost member id');
		exit(json_encode($error));
	}
	$md5 = $wxapi->md5_userinfo($json);
	//$wxapi->log(json_encode($json));
	//$wxapi->log($md5);
	SQL::share('member')->where($member_id)->update(array('wx_name'=>$json['nickname'], 'md5'=>$md5));
	$data = array('md5'=>$md5);
	exit(json_encode($data));
}

//小程序传递code过来换取access_token与openid
function get_session_key($json){
	exit(json_encode($json));
}

//关注
function subscribe($toUserName, $fromUserName){
	global $wxapi;
	if (!strlen($fromUserName)) return '';
	$wechat = SQL::share('wechat')->where("username='{$toUserName}'")->row();
	if (!$wechat) return '';
	$component = SQL::share('component')->where($wechat->component_id)->row();
	$wxapi->WX_THIRD = array(
		'appid'=>$component->appid,
		'secret'=>$component->appsecret,
		'token'=>$component->token,
		'aeskey'=>$component->aeskey
	);
	$json = $wxapi->authorizer_access_token('', $wechat->appid, true);
	$access_token = $json['authorizer_access_token'];
	if (!strlen($access_token)) return '';
	$data = ['openid_list'=>[$fromUserName], 'tagid'=>intval($wechat->tag)];
	$res = $wxapi->requestData('post', "https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token={$access_token}", $data, true, true);
	
	SQL::share('wechat_user')->insert(array('wechat_id'=>$wechat->id, 'openid'=>$fromUserName, 'add_time'=>time()));
	return '';
}

//取消关注
function unsubscribe($toUserName, $fromUserName){
	global $wxapi;
	if (!strlen($fromUserName)) return '';
	$wechat = SQL::share('wechat')->where("username='{$toUserName}'")->row();
	if (!$wechat) return '';
	SQL::share('wechat_user')->delete(array('wechat_id'=>"='{$wechat->id}'", 'openid'=>"='{$fromUserName}'"));
	return '';
}

//关键字回复
function msgText($toUserName, $fromUserName, $content='', $isMiniprogram=false){
	global $wxapi;
	if (!strlen($content)) return '';
	if (strpos($content, 'QUERY_AUTH_CODE:')!==false) { //全网发布 - 返回Api文本检测
		$auth_code = trim(str_replace('QUERY_AUTH_CODE:', '', $content));
		$component_access_token = $wxapi->component_access_token();
		$url = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token={$component_access_token}";
		$data = array();
		$data['component_appid'] = $wxapi->WX_THIRD['appid'];
		$data['authorization_code'] = $auth_code;
		$json = $wxapi->requestData('post', $url, json_encode($data), true, true);
		$authorizer_access_token = $json['authorization_info']['authorizer_access_token'];
		$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$authorizer_access_token}";
		$data = array();
		$data['touser'] = $fromUserName;
		$data['msgtype'] = 'text';
		$data['text'] = array('content'=>"{$auth_code}_from_api");
		$wxapi->requestData('post', $url, json_encode($data), true, true);
		return '';
	} else if (strpos($content, 'TESTCOMPONENT_MSG_TYPE_TEXT')!==false) { //全网发布 - 返回普通文本检测
		return 'TESTCOMPONENT_MSG_TYPE_TEXT_callback';
	}
	$wechat = SQL::share('wechat')->where("username='{$toUserName}'")->row();
	if (!$wechat) {
		$wechat = SQL::share('miniprogram')->where("username='{$toUserName}'")->row();
		$isMiniprogram = true;
	}
	if (!$wechat) return '';
	$component = SQL::share('component')->where($wechat->component_id)->row();
	$wxapi->WX_THIRD = array(
		'appid'=>$component->appid,
		'secret'=>$component->appsecret,
		'token'=>$component->token,
		'aeskey'=>$component->aeskey
	);
	$return = '';
	if ($isMiniprogram) {
		/*if (preg_match('/^66$/', $content)) {
			$json = $wxapi->authorizer_access_token('', $wechat->appid, true);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
			if (!strlen($access_token)) return false;
			$content = '<a href="http://mp.weixin.qq.com/s?__biz=MzU2MDQyODcyMw==&mid=100000016&idx=1&sn=&chksm=7c0962cc4b7eebda6ee4bc2c6c055612b20b0e565ce166e805e0787841946fd857c333e7fd44#rd">点我看看</a>';
			$data = array();
			$data['touser'] = $fromUserName;
			$data['msgtype'] = 'text';
			$data['text'] = array('content'=>$content);
			$data = json_encode($data, JSON_UNESCAPED_UNICODE);
			$json = $wxapi->requestData('post', "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}", $data, true, true);
			if (isset($json['errcode']) && intval($json['errcode'])!=0) {
				write_log(json_encode($json), true);
			}
		}*/
		msgMiniprogramPage($toUserName, $fromUserName);
	} else {
		SQL::share('wechat_user')->where("openid='{$fromUserName}'")->update(array('add_time'=>time()));
		if (preg_match('/^\d+$/', $content)) {
			$row = SQL::share('wechat_customer_preview')->where($content)->row('id, title, memo, pic, url');
			if ($row) {
				$mp = SQL::share('wechat_customer_mp_preview wcm')->left('wechat w', 'wcm.wechat_id=w.id')->where("wcm.customer_id='{$row->id}' AND w.username='{$toUserName}'")->row('w.appid');
				if ($mp) {
					$json = $wxapi->authorizer_access_token('', $mp->appid);
					$access_token = $json['authorizer_access_token'];
					$data = array();
					$data['touser'] = $fromUserName;
					$data['msgtype'] = 'news';
					$data['news'] = array(
						'articles'=>array(
							array(
								'title' => $row->title,
								'description' => $row->memo,
								'url' => $row->url,
								'picurl' => add_domain($row->pic)
							)
						)
					);
					$data = json_encode($data, JSON_UNESCAPED_UNICODE);
					$json = $wxapi->requestData('post', "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}", $data, true, true);
					if (isset($json['errcode']) && intval($json['errcode'])!=0) {
						$wxapi->log(json_encode($json), true);
					}
				}
			}
		}
	}
	return $return;
}

//点击菜单
function click($fromUserName, $eventKey) {
	SQL::share('wechat_user')->where("openid='{$fromUserName}'")->update(array('add_time'=>time()));
	return '';
}

//默认自动回复
function autoReply($toUserName, $fromUserName, $isMiniprogram=false){
	global $wxapi;
	return '';
}

//扫描
function scan($fromUserName, $scene_value, $toUserName=''){
	global $wxapi;
	//$wxapi->log($scene_value);
	if ($toUserName) subscribe($toUserName, $fromUserName);
	if (!SQL::share('member_thirdparty')->where("mark='{$fromUserName}'")->exist() &&
		!SQL::share('openid')->where("openid='{$fromUserName}'")->exist()) {
		SQL::share('openid')->insert(array('openid'=>$fromUserName, 'reseller_id'=>$scene_value));
	}
	return '';
}

//公众号授权给第三方开发平台后执行
//伪静增加 ^wx\w{16}/(.+)$ /$1
function component_auth($json){
	global $wxapi, $component_appid;
	$html = '';
	$appid = $json['authorizer_appid'];
	if (isset($json['authorizer_info']['MiniProgramInfo'])) {
		$row = SQL::share('miniprogram')->where("appid='{$appid}'")->row('id');
		if (!$row) {
			$component = SQL::share('component')->where("appid='{$component_appid}'")->row();
			$component_id = $component->id;
			$name = $json['authorizer_info']['nick_name'];
			$username = $json['authorizer_info']['user_name'];
			$first = $json['authorizer_info']['MiniProgramInfo']['categories'][0]['first'];
			$second = $json['authorizer_info']['MiniProgramInfo']['categories'][0]['second'];
			$alias = $json['authorizer_info']['alias'];
			$pic = $json['authorizer_info']['head_img'];
			$qrcode = $json['authorizer_info']['qrcode_url'];
			$qrcode = download_file('wxqrcode', $qrcode, false, '.jpg');
			$file = PUBLIC_PATH.$qrcode;
			$fp = @fopen($file, 'r');
			$qrcode = @fread($fp, @filesize($file));
			@fclose($fp);
			$qrcode = upload_obj_file($qrcode, 'wxqrcode');
			$qrcode = add_domain($qrcode);
			@unlink($file);
			$miniprogram_id = SQL::share('miniprogram')->insert(compact('component_id', 'appid', 'name', 'username', 'first', 'second', 'alias', 'pic', 'qrcode'));
			
			$s = $second=='视频' ? 'VIDEO' : 'ARTICLE';
			$rs = SQL::share('config')->where("name LIKE 'G_{$s}%'")->find('id, content');
			if ($rs) {
				$data = array();
				foreach ($rs as $row) {
					$data[] = array('miniprogram_id'=>$miniprogram_id, 'config_id'=>$row->id, 'content'=>$row->content);
				}
				SQL::share('miniprogram_config')->insert($data);
			}
			
			if ($s=='ARTICLE') {
				$article = SQL::share('article')->sort('id ASC')->find('id');
				foreach ($article as $g) {
					SQL::share('article_attr')->insert(array('miniprogram_id'=>$miniprogram_id, 'article_id'=>$g->id));
				}
			}
			
			$wxapi->miniprogramServerDomain($appid, '<#server#>', 'set');
			//if (intval($json['authorizer_info']['verify_type_info']['id'])>-1) //未认证无法设置业务域名
			$wxapi->miniprogramBusinessDomain($appid, '<#server#>', 'set');
		} else {
			$html = '小程序已存在，更新信息成功';
			$name = $json['authorizer_info']['nick_name'];
			$pic = $json['authorizer_info']['head_img'];
			$qrcode = $json['authorizer_info']['qrcode_url'];
			$qrcode = download_file('wxqrcode', $qrcode, false, '.jpg');
			$file = PUBLIC_PATH.$qrcode;
			$fp = @fopen($file, 'r');
			$qrcode = @fread($fp, @filesize($file));
			@fclose($fp);
			$qrcode = upload_obj_file($qrcode, 'wxqrcode');
			$qrcode = add_domain($qrcode);
			@unlink($file);
			SQL::share('miniprogram')->where("appid='{$appid}'")->update(compact('name', 'pic', 'qrcode'));
		}
	} else {
		$row = SQL::share('wechat')->where("appid='{$appid}'")->row('id');
		if (!$row) {
			$component = SQL::share('component')->where("appid='{$component_appid}'")->row();
			$component_id = $component->id;
			$name = $json['authorizer_info']['nick_name'];
			$username = $json['authorizer_info']['user_name'];
			$type = $json['authorizer_info']['service_type_info']['id'];
			$alias = $json['authorizer_info']['alias'];
			$pic = $json['authorizer_info']['head_img'];
			$qrcode = $json['authorizer_info']['qrcode_url'];
			$qrcode = download_file('wxqrcode', $qrcode, false, '.jpg');
			$file = PUBLIC_PATH.$qrcode;
			$fp = @fopen($file, 'r');
			$qrcode = @fread($fp, @filesize($file));
			@fclose($fp);
			$qrcode = upload_obj_file($qrcode, 'wxqrcode');
			$qrcode = add_domain($qrcode);
			@unlink($file);
			SQL::share('wechat')->insert(compact('component_id', 'appid', 'name', 'username', 'type', 'alias', 'pic', 'qrcode'));
		} else {
			$html = '公众号已存在，更新信息成功';
			$name = $json['authorizer_info']['nick_name'];
			$pic = $json['authorizer_info']['head_img'];
			$qrcode = $json['authorizer_info']['qrcode_url'];
			$qrcode = download_file('wxqrcode', $qrcode, false, '.jpg');
			$file = PUBLIC_PATH.$qrcode;
			$fp = @fopen($file, 'r');
			$qrcode = @fread($fp, @filesize($file));
			@fclose($fp);
			$qrcode = upload_obj_file($qrcode, 'wxqrcode');
			$qrcode = add_domain($qrcode);
			@unlink($file);
			SQL::share('wechat')->where("appid='{$appid}'")->update(compact('name', 'pic', 'qrcode'));
		}
	}
	if (!strlen($html)) $html = '绑定授权成功';
	$html .= PHP_EOL.'<script>
if(window.top.document!==window.document){
	let count = 3, timer = setInterval(function(){
		if(count<=0){
			clearInterval(timer);timer = null;
			if(window.top.closeLay)window.top.closeLay();
			window.top.location.reload();
			return;
		}
		count--;
	}, 1000);
}
</script>';
	return $html;
}

//根据小程序账号切换第三方平台
function actMiniprogram($toUserName) {
	global $wxapi;
	$wechat = SQL::share('miniprogram')->where("username='{$toUserName}'")->row('component_id');
	if (!$wechat) return;
	$component = SQL::share('component')->where($wechat->component_id)->row('appid, appsecret, token, aeskey');
	if (!$component) return;
	$wxapi->WX_THIRD = array(
		'appid'=>$component->appid,
		'secret'=>$component->appsecret,
		'token'=>$component->token,
		'aeskey'=>$component->aeskey
	);
}

//小程序进入客服会话
function userEnterTempsession($toUserName, $fromUserName) {
	global $wxapi;
	$isMiniprogram = false;
	$wechat = SQL::share('wechat')->where("username='{$toUserName}'")->row();
	if (!$wechat) {
		$wechat = SQL::share('miniprogram')->where("username='{$toUserName}'")->row();
		$isMiniprogram = true;
	}
	if (!$wechat) return '';
	if ($isMiniprogram) {
		msgMiniprogramPage($toUserName, $fromUserName);
	} else {
		$component = SQL::share('component')->where($wechat->component_id)->row();
		$json = $wxapi->access_token($component->appid, $component->appsecret, ROOT_PATH.'/temp/miniprogram/'.$component->appid.'/access_token.json');
		$access_token = $json['access_token'];
		$data = array();
		$data['access_token'] = $access_token;
		$data['touser'] = $fromUserName;
		$data['msgtype'] = 'link';
		$data['link'] = array(
			'title'=>'军狮报读',
			'description'=>'新时事，新发现，最新的时事信息就在这里',
			'url'=>'http://a.279618.com.cn/v/U1010SV3CR8',
			'thumb_url'=>'http://mmbiz.qpic.cn/mmbiz_png/Xu9Z3Tb6AZS2zO0lSRmXfD3gWVezuc4yyRFtGwjT8ibXlicia9MAAW4o8ZWCeibD8d56QURkib0TVVCzMEfMkXnR5lA/0?wx_fmt=png'
		);
		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		$json = $wxapi->requestData('post', "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}", $data, true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			$wxapi->log(json_encode($json));
		}
	}
	return '';
}

//小程序客服消息卡片
function msgMiniprogramPage($toUserName, $fromUserName, $AppId='', $Title='', $PagePath='', $ThumbUrl='') {
	global $wxapi, $request;
	$wechat = SQL::share('miniprogram')->where("username='{$toUserName}'")->row();
	if (!$wechat) return '';
	if ($wechat->type==1) $s = 'VIDEO';
	else if ($wechat->type==2) $s = 'BLESSING';
	else if ($wechat->type==3) $s = 'BUDDHA';
	else $s = 'ARTICLE';
	$rs = SQL::share('config c')->left('miniprogram_config mc', 'mc.config_id=c.id')->where("c.name LIKE 'G_{$s}_CUSTOM_MESSAGE%' AND mc.miniprogram_id='{$wechat->id}'")->find('c.name, mc.content');
	if ($rs) {
		$configs = array();
		foreach ($rs as $row) {
			$configs[$row->name] = $row->content;
		}
	}
	$component = SQL::share('component')->where($wechat->component_id)->row();
	$wxapi->WX_THIRD = array(
		'appid'=>$component->appid,
		'secret'=>$component->appsecret,
		'token'=>$component->token,
		'aeskey'=>$component->aeskey
	);
	$json = $wxapi->authorizer_access_token('', $wechat->appid, true);
	if (!$json) return false;
	$access_token = $json['authorizer_access_token'];
	if (!strlen($access_token)) return false;
	$data = array();
	$data['touser'] = $fromUserName;
	$message_type = $request->act("G_{$s}_CUSTOM_MESSAGE_SEND_TYPE", 0, '', $configs);
	switch ($message_type) {
		case 0:
			$data['msgtype'] = 'text';
			$data['text'] = array(
				'content'=>$request->act("G_{$s}_CUSTOM_MESSAGE_SEND_TEXT", '', '', $configs)
			);
			break;
		case 1:
			$media_id = $request->act("G_{$s}_CUSTOM_MESSAGE_SEND_MEDIAID", '', '', $configs);
			$media_id = explode('|', $media_id);
			$data['msgtype'] = 'image';
			$data['image'] = array(
				'media_id'=>$media_id[0]
			);
			break;
		case 2:
			$data['msgtype'] = 'link';
			$data['link'] = array(
				'title'=>$request->act("G_{$s}_CUSTOM_MESSAGE_SEND_TITLE", '', '', $configs),
				'description'=>$request->act("G_{$s}_CUSTOM_MESSAGE_SEND_DESCRIPTION", '', '', $configs),
				'url'=>$request->act("G_{$s}_CUSTOM_MESSAGE_SEND_LINK", '', '', $configs),
				'thumb_url'=>add_domain($request->act("G_{$s}_CUSTOM_MESSAGE_SEND_IMG", '', '', $configs))
			);
			break;
		case 3:
			$media_id = $request->act("G_{$s}_CUSTOM_MESSAGE_SEND_MEDIAID", '', '', $configs);
			$media_id = explode('|', $media_id);
			$data['msgtype'] = 'miniprogrampage';
			$data['miniprogrampage'] = array(
				'title'=>$request->act("G_{$s}_CUSTOM_MESSAGE_SEND_TITLE", '', '', $configs),
				'pagepath'=>$request->act("G_{$s}_CUSTOM_MESSAGE_SEND_PATH", '', '', $configs),
				'thumb_media_id'=>$media_id[0]
			);
			break;
	}
	$data = json_encode($data, JSON_UNESCAPED_UNICODE);
	$json = $wxapi->requestData('post', "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}", $data, true, true);
	if (isset($json['errcode']) && intval($json['errcode'])!=0) {
		write_log(json_encode($json), true);
	}
	return '';
}

//小程序审核通过
function weappAuditSuccess($toUserName, $fromUserName) {
	global $wxapi;
	if (!strlen($fromUserName)) return '';
	$row = SQL::share('miniprogram')->where("username='{$toUserName}'")->row();
	if (!$row) return '';
	$component = SQL::share('component')->where($row->component_id)->row();
	$wxapi->WX_THIRD = array(
		'appid'=>$component->appid,
		'secret'=>$component->appsecret,
		'token'=>$component->token,
		'aeskey'=>$component->aeskey
	);
	$wxapi->miniprogramRelease($row->appid);
	$version = intval(str_replace('.', '', $row->version));
	$version++;
	$version = strval($version);
	$version = str_split($version);
	$version = implode('.', $version);
	$data = array();
	$data['review'] = 0;
	$data['promote_status'] = 1;
	$data['version'] = $version;
	$data['audit_status'] = 2;
	$data['audit_time'] = time();
	SQL::share('miniprogram')->where($row->id)->update($data);
	return '';
}

//小程序审核失败
function weappAuditFail($toUserName, $fromUserName, $reason, $screenshot=array()) {
	global $wxapi;
	if (!strlen($fromUserName)) return '';
	$row = SQL::share('miniprogram')->where("username='{$toUserName}'")->row();
	if (!$row) return '';
	$data = array();
	$data['review'] = 0;
	$data['promote_status'] = 1;
	$data['audit_status'] = -1;
	$data['audit_reason'] = $reason;
	$data['audit_time'] = time();
	if (count($screenshot)) $data['audit_screenshot'] = implode('|', $screenshot);
	SQL::share('miniprogram')->where($row->id)->update($data);
	return '';
}

//小程序审核延迟
function weappAuditDelay($toUserName, $fromUserName, $reason) {
	global $wxapi;
	if (!strlen($fromUserName)) return '';
	$row = SQL::share('miniprogram')->where("username='{$toUserName}'")->row();
	if (!$row) return '';
	$data = array();
	$data['review'] = 0;
	$data['promote_status'] = 1;
	$data['audit_status'] = -1;
	$data['audit_reason'] = $reason;
	$data['audit_time'] = time();
	SQL::share('miniprogram')->where($row->id)->update($data);
	return '';
}

//第三方平台快速注册小程序
function thirdFasteregister($component_appid, $appid, $info, $auth_code, $status=0, $reason='') {
	global $wxapi;
	$component = SQL::share('component')->where("appid='{$component_appid}'")->row();
	$component_id = $component->id;
	$name = $info['name'];
	$code = $info['code'];
	$legal_persona_wechat = $info['legal_persona_wechat'];
	$legal_persona_name = $info['legal_persona_name'];
	$row = SQL::share('miniprogram_box')
		->where("component_id='{$component_id}' AND status=0 AND name='{$name}' AND code='{$code}' AND legal_persona_wechat='{$legal_persona_wechat}' AND legal_persona_name='{$legal_persona_name}'")
		->row();
	if ($row) {
		if (strlen($reason)) {
			SQL::share('miniprogram_box')->where($row->id)->update(array('status'=>$status, 'reason'=>$reason));
		} else {
			$name = $row->app_name;
			$first = $row->category_first;
			$second = $row->category_second;
			$fast = 1;
			$status = 0;
			$wxapi->WX_THIRD = array(
				'appid'=>$component->appid,
				'secret'=>$component->appsecret,
				'token'=>$component->token,
				'aeskey'=>$component->aeskey
			);
			$wxapi->authorizer_access_token($auth_code);
			SQL::share('miniprogram_box')->where($row->id)->update(array('status'=>1));
			SQL::share('miniprogram')->insert(compact('component_id', 'appid', 'name', 'first', 'second', 'fast', 'status'));
			$wxapi->miniprogramServerDomain($appid, '<#server#>', 'set');
			$wxapi->miniprogramBusinessDomain($appid, '<#server#>', 'set');
		}
	}
	return '';
}
