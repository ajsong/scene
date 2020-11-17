<?php
//$wxapi = new wechatCallbackAPI();
//$wxapi->valid(); //Application Message Interface
//$wxapi->responseMsg();

define('SHOW_RESPONSE_MSG', false);
require_once 'Component/wxBizMsgCrypt.php';
class wechatCallbackAPI{
	private $request;
	private $appId;
	private $appSecret;
	private $isEncrypt; //是否使用了加密
	private $isMiniprogram; //是否小程序消息推送, 小程序后台填写www.website.com/wx_interface?act=miniprogram
	public $remoteUrl; //远程获取 [access_token|jsapi_ticket] 的网址
	public $file_path; //凭证文件、log文件存放路径
	public $WX_THIRD; //第三方开发平台资料
	
	public function __construct($appId='', $appSecret='') {
		global $request;
		$this->request = $request;
		$this->appId = strlen($appId) ? $appId : WX_APPID;
		$this->appSecret = strlen($appSecret) ? $appSecret : WX_SECRET;
		$this->remoteUrl = '';
		$this->file_path = '';
		$this->isEncrypt = false;
		$this->isMiniprogram = false;
		if (defined('ROOT_PATH')) {
			$this->file_path = ROOT_PATH . '/temp/';
			if (!is_dir($this->file_path)) {
				$this->makeDir($this->file_path);
			}
		}
		if (defined('WX_THIRDPARTY_APPID') && strlen(WX_THIRDPARTY_APPID)) {
			$this->WX_THIRD = array(
				'appid'=>WX_THIRDPARTY_APPID,
				'secret'=>WX_THIRDPARTY_SECRET,
				'token'=>WX_THIRDPARTY_TOKEN,
				'aeskey'=>WX_THIRDPARTY_AESKEY
			);
		}
	}
	
	public function setFilepath($path) {
		if (substr($path, -1)!='/') $path .= '/';
		$this->file_path = $path;
		if (defined('ROOT_PATH')) {
			$this->file_path = ROOT_PATH . str_replace(ROOT_PATH, '', $this->file_path);
		}
		if (!is_dir($this->file_path)) {
			$this->makeDir($this->file_path);
		}
	}
	
	private function checkSignature() {
		$signature = $this->request->get('signature');
		$timestamp = $this->request->get('timestamp');
		$nonce = $this->request->get('nonce');
		$token = is_array($this->WX_THIRD) ? $this->WX_THIRD['token'] : WX_TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		$tmpStr = strtolower($tmpStr);
		if ($tmpStr == $signature) {
			return true;
		} else {
			return false;
		}
	}
	
	//生成随机串
	private function createNonceStr($length=16) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$str = '';
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}
	
	//验证微信服务号
	public function valid() {
		$echostr = $this->request->get('echostr');
		if ($this->checkSignature()) {
			echo $echostr;
		}
		exit;
	}
	
	//获取事件推送, callback为授权登录用
	public function responseMsg($callback = NULL) {
		$this->checkAuth($callback);
		//$data = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : ''; //PHP7以上不支持
		$data = file_get_contents('php://input');
		if (!empty($data)) {
			//判断POST信息是否加密信息
			libxml_disable_entity_loader(true); //禁止引用外部xml实体,修复XXE漏洞
			$res = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
			$res = json_encode($res);
			$res = json_decode($res, true);
			$res = json_encode($res);
			$res = json_decode($res);
			if (isset($res->Encrypt)) {
				if (SHOW_RESPONSE_MSG) $this->log("encryptMsg:\n".$data);
				$timestamp = $this->request->get('timestamp');
				$nonce = $this->request->get('nonce');
				$msg_signature = $this->request->get('msg_signature');
				if (!strlen($timestamp) || !strlen($nonce) || !strlen($msg_signature)) {
					$this->log('MISSING PARAMETER');
					exit('MISSING PARAMETER');
				}
				$this->isEncrypt = true;
				$appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : $this->appId;
				$token = is_array($this->WX_THIRD) ? $this->WX_THIRD['token'] : WX_TOKEN;
				$aeskey = is_array($this->WX_THIRD) ? $this->WX_THIRD['aeskey'] : WX_AESKEY;
				$pc = new WXBizMsgCrypt($token, $aeskey, $appid);
				$errCode = $pc->decryptMsg($msg_signature, $timestamp, $nonce, $data, $decryptMsg); //解密
				if ($errCode != 0) {
					if (SHOW_RESPONSE_MSG) $this->log('decryptMsg error: '.$errCode."\n".json_encode($_GET, JSON_UNESCAPED_UNICODE)."\n".json_encode($res, JSON_UNESCAPED_UNICODE));
					echo 'success';
					exit;
				}
				$data = $decryptMsg;
				if (SHOW_RESPONSE_MSG) $this->log("decryptMsg:\n".$data);
			}
			if (!$this->isEncrypt && SHOW_RESPONSE_MSG) $this->log($data);
			//正常收取POST信息
			$res = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
			$res = json_encode($res);
			$res = json_decode($res, true);
			$res = json_encode($res);
			$res = json_decode($res);
			//第三方平台Ticket推送
			if (isset($res->ComponentVerifyTicket)) {
				$this->component_verify_ticket($res->ComponentVerifyTicket);
			}
			//第三方平台快速创建小程序审核推送
			if (isset($res->InfoType) && $res->InfoType=='notify_third_fasteregister') {
				if (intval($res->status)==0) {
					//AppId第三方平台AppId, appid创建的小程序AppId, auth_code第三方授权码, info提交的小程序企业信息
					if (function_exists('thirdFasteregister')) thirdFasteregister($res->AppId, $res->appid, json_decode(json_encode($res->info, JSON_UNESCAPED_UNICODE), true), $res->auth_code);
				} else {
					$this->log(json_encode($res));
					if (function_exists('thirdFasteregister')) thirdFasteregister($res->AppId, $res->appid, json_decode(json_encode($res->info, JSON_UNESCAPED_UNICODE), true), $res->auth_code, $res->status, $res->msg);
				}
				echo 'success';
				exit;
			}
			$toUserName = '';
			$fromUserName = '';
			$event = '';
			$content = '';
			if (isset($res->ToUserName)) $toUserName = $res->ToUserName;
			if (isset($res->FromUserName)) $fromUserName = $res->FromUserName;
			if (isset($res->Event)) $event = $res->Event;
			switch (strtolower($event)) {
				case 'click': //点击
					if (isset($res->EventKey)) { //CUSTOM_VALUE
						if (function_exists('click')) $content = click($fromUserName, $res->EventKey);
					}
					break;
				case 'scan': //扫描带参数二维码,用户已关注时
					if (isset($res->EventKey)) { //SCENE_ID
						if (function_exists('scan')) $content = scan($fromUserName, $res->EventKey, $toUserName);
					}
					break;
				case 'subscribe': //关注
					if (isset($res->EventKey) && strpos($res->EventKey, 'qrscene_')!==false) { //扫描带参数二维码,用户未关注时,进行关注后的事件推送
						if (function_exists('scan')) $content = scan($fromUserName, str_replace('qrscene_', '', $res->EventKey), $toUserName);
					} else {
						if (function_exists('subscribe')) $content = subscribe($toUserName, $fromUserName);
					}
					break;
				case 'unsubscribe': //取消关注
					if (function_exists('unsubscribe')) $content = unsubscribe($toUserName, $fromUserName);
					break;
				case 'user_enter_tempsession': //小程序进入客服会话
					if (function_exists('userEnterTempsession')) $content = userEnterTempsession($toUserName, $fromUserName);
					break;
				default:
					$msgtype = '';
					if (isset($res->MsgType)) $msgtype = $res->MsgType;
					switch(strtolower($msgtype)){
						case 'text': //文本
							if (function_exists('msgText')) $content = msgText($toUserName, $fromUserName, trim($res->Content), $this->isMiniprogram);
							//Content:文本消息内容
							break;
						case 'miniprogrampage': //小程序客服消息卡片
							if (function_exists('msgMiniprogramPage')) $content = msgMiniprogramPage($toUserName, $fromUserName, $res->AppId, $res->Title, $res->PagePath, $res->ThumbUrl);
							//AppId:进入客服消息的小程序AppId, Title:卡片标题, PagePath:卡片点击跳转小程序路径, ThumbUrl:卡片图片
							break;
						case 'image': //图片
							if (function_exists('msgImage')) $content = msgImage($toUserName, $fromUserName, $res->PicUrl, $res->MediaId, $this->isMiniprogram);
							//PicUrl:图片链接, MediaId:消息媒体id,可调用多媒体文件下载接口拉取数据
							//http://mp.weixin.qq.com/wiki/10/78b15308b053286e2a66b33f0f0f5fb6.html
							break;
						case 'voice': //语音
							if (function_exists('msgVoice')) $content = msgVoice($toUserName, $fromUserName, $res->Format, $res->MediaId, $this->isMiniprogram);
							//Format:语音格式,如amr,speex
							break;
						case 'video': //视频
							if (function_exists('msgVideo')) $content = msgVideo($toUserName, $fromUserName, $res->ThumbMediaId, $res->MediaId, $this->isMiniprogram);
							//ThumbMediaId:消息缩略图的媒体id,可调用多媒体文件下载接口拉取数据
							break;
						case 'location': //地理位置
							if (function_exists('msgLocation')) $content = msgLocation($toUserName, $fromUserName, $res->Location_Y, $res->Location_X, $res->Scale, $res->Label, $this->isMiniprogram);
							//Location_Y:经度, Location_X:纬度, Scale:缩放大小, Label:地理位置信息
							break;
						case 'link': //链接
							if (function_exists('msgLink')) $content = msgLink($toUserName, $fromUserName, $res->Title, $res->Description, $res->Url, $this->isMiniprogram);
							//Title:消息标题, Description:消息描述, Url:消息链接
							break;
						case 'event': //操作
							switch (strtolower($event)) {
								case 'templatesendjobfinish': //发送消息模板通知
									if (isset($res->Status)) {
										switch ($res->Status) {
											case 'success':
												break;
											case 'failed:user block':
												//用户拒收
												break;
											case 'failed: system failed':
												//其他原因
												break;
										}
									}
									break;
								case 'weapp_audit_success': //小程序代码审核通过
									if (function_exists('weappAuditSuccess')) $content = weappAuditSuccess($toUserName, $fromUserName);
									break;
								case 'weapp_audit_fail': //小程序代码审核不通过
									//ScreenShot: 审核不通过的截图，用 | 分隔的 media_id 的列表，可通过获取永久素材接口拉取截图内容
									//永久素材: https://developers.weixin.qq.com/doc/offiaccount/Asset_Management/Getting_Permanent_Assets.html
									if (function_exists('weappAuditFail')) $content = weappAuditFail($toUserName, $fromUserName, $res->Reason, explode('|', $res->ScreenShot));
									break;
								case 'weapp_audit_delay': //小程序代码审核通过
									if (function_exists('weappAuditDelay')) $content = weappAuditDelay($toUserName, $fromUserName, $res->Reason);
									break;
							}
							break;
						default:
							break;
					}
					if (!strlen($content) && function_exists('autoReply')) $content = autoReply($toUserName, $fromUserName, $this->isMiniprogram);
					break;
			}
			if (strlen($content)) $this->sendTextMsg($fromUserName, $toUserName, $content);
		}
		echo 'success';
		exit;
	}
	
	//事件推送
	public function sendTextMsg($toUserName, $fromUserName, $content) {
		$tpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";
		//<FuncFlag>1</FuncFlag>
		$str = sprintf($tpl, $toUserName, $fromUserName, time(), $content);
		//$this->log($str);
		if ($this->isEncrypt) {
			$encryptMsg = '';
			$appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : $this->appId;
			$token = is_array($this->WX_THIRD) ? $this->WX_THIRD['token'] : WX_TOKEN;
			$aeskey = is_array($this->WX_THIRD) ? $this->WX_THIRD['aeskey'] : WX_AESKEY;
			$nonce = $this->createNonceStr();
			$pc = new WXBizMsgCrypt($token, $aeskey, $appid);
			$errCode = $pc->encryptMsg($str, time(), $nonce, $encryptMsg);
			if ($errCode != 0) {
				$this->log('sendTextMsg error: '.$errCode);
				echo $errCode;
				exit;
			}
			$str = $encryptMsg;
		}
		echo $str;
		exit;
	}
	
	//检测是否有认证授权操作
	public function checkAuth($callback = NULL) {
		//普通微信号网页授权
		if ($callback=='weixin_auth') {
			$json = $this->weixin_auth();
			if (function_exists($callback)) call_user_func($callback, $json);
			$html = $this->showSuccess('登录授权成功');
			exit($html);
			//exit('LOGIN SUCCESS');
		}
		//普通微信号网页授权(第三方开发平台引起)
		if ($callback=='mp_auth') {
			$json = $this->mp_auth();
			if (function_exists($callback)) call_user_func($callback, $json);
			exit('AUTH SUCCESS');
		}
		//电脑端打开进行公众号授权给第三方开发平台
		if ($callback=='component_auth') {
			$json = $this->component_auth();
			$return = '';
			if (function_exists($callback)) $return = call_user_func($callback, $json);
			if (!$return) $return = '绑定授权成功';
			$html = $this->showSuccess($return);
			exit($html);
			//exit('AUTH SUCCESS');
		}
		//APP端传递code过来换取access_token与获取用户资料
		if ($callback=='getcode') {
			if (!defined('WX_OPEN_APPID') || !defined('WX_OPEN_SECRET')) exit;
			$code = $this->request->get('code');
			$this->log($code);
			if (!$code) {
				$error = array('errcode'=>10000, 'errmsg'=>'lost code');
				exit(json_encode($error));
			}
			$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".WX_OPEN_APPID."&secret=".WX_OPEN_SECRET."&code={$code}&grant_type=authorization_code";
			$json = $this->requestData('get', $url, array(), true);
			if (isset($json['errcode']) && intval($json['errcode'])!=0) {
				$this->log(json_encode($json));
				exit($json['errmsg'].' errcode: '.$json['errcode']);
			}
			$url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$json['access_token']."&openid=".$json['openid']."&lang=zh_CN";
			$json = $this->requestData('get', $url, array(), true);
			if (isset($json['errcode']) && intval($json['errcode'])!=0) {
				$this->log(json_encode($json));
				exit($json['errmsg'].' errcode: '.$json['errcode']);
			}
			if (function_exists($callback)) call_user_func($callback, $json);
			exit;
		}
		//小程序传递code过来换取access_token与openid
		if ($callback=='get_session_key') {
			$code = $this->request->get('code');
			if (!$code) {
				$error = array('errcode'=>10000, 'errmsg'=>'lost code');
				exit(json_encode($error));
			}
			$appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : $this->appId; //小程序的appid
			$secret = is_array($this->WX_THIRD) ? $this->WX_THIRD['secret'] : $this->appSecret; //小程序的app secret
			$url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
			$json = $this->requestData('get', $url, array(), true);
			if (isset($json['errcode']) && intval($json['errcode'])!=0) {
				$this->log(json_encode($json));
				exit($json['errmsg'].' errcode: '.$json['errcode']);
			}
			if (function_exists($callback)) call_user_func($callback, $json);
			exit;
		}
		//小程序消息推送
		if ($callback=='miniprogram') {
			$this->isMiniprogram = true;
			if (defined('WX_PROGRAM_APPID') && strlen(WX_PROGRAM_APPID)) {
				$this->WX_THIRD = array(
					'appid'=>WX_PROGRAM_APPID,
					'secret'=>WX_PROGRAM_SECRET,
					'token'=>WX_PROGRAM_TOKEN,
					'aeskey'=>WX_PROGRAM_AESKEY
				);
			}
			if (function_exists('actMiniprogram')) {
				$data = file_get_contents('php://input');
				if (!empty($data)) {
					libxml_disable_entity_loader(true);
					$res = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
					$res = json_encode($res);
					$res = json_decode($res, true);
					$res = json_encode($res);
					$res = json_decode($res);
					if (isset($res->ToUserName)) {
						actMiniprogram($res->ToUserName);
					}
				}
			}
		}
	}
	
	private function showSuccess($str) {
		return $this->showHtml($str, 'weui_icon_success');
	}
	private function showError($str) {
		return $this->showHtml($str, 'weui_icon_warn');
	}
	private function showHtml($str, $icon_class) {
		$isPc = false;
		if (isset($_GET['act']) && $_GET['act']=='component_auth') $isPc = true;
		return '<title>'.preg_replace('/<[^>]+>[\s\S]+?<\/[^>]+>/', '', $str).'</title><meta charset="utf-8">
			<meta name="viewport" content="width=320,minimum-scale=1.0,maximum-scale=1.0,initial-scale=1.0,user-scalable=no">
			<link rel="stylesheet" type="text/css" href="https://res.wx.qq.com/open/libs/weui/0.4.1/weui.css">
			<style>body{background:#fff;}.weui-btn{display:block;margin:0 auto;box-sizing:border-box;width:100px;font-size:13px;text-align:center;text-decoration:none;color:#fff;line-height:2.3;border-radius:5px;-webkit-tap-highlight-color:rgba(0,0,0,0);overflow:hidden;background-color:#1AAD19;}</style>
			<div class="weui_msg"><div class="weui_icon_area"><i class="'.$icon_class.' weui_icon_msg"></i></div>
			<div class="weui_text_area"><h4 class="weui_msg_title">'.$str.'</h4></div></div>
			'.(!$isPc?'<a href="javascript:WeixinJSBridge.call(\'closeWindow\')" class="weui-btn">关闭窗口</a>':'');
	}
	
	//获取jssdk - access_token
	public function getAccessToken($filename='access_token.json') {
		if (strlen($this->remoteUrl)) {
			$file_contents = file_get_contents($this->remoteUrl);
			$data = json_decode($file_contents);
			return $data->access_token;
		}
		$file_path = $this->file_path . $filename;
		$this->makeDir(dirname($file_path));
		if (!file_exists($file_path)) {
			$handle = @fopen($file_path, 'a') or die('UNABLE TO OPEN FILE');
			@fwrite($handle, '');
			@fclose($handle);
		}
		$data = new stdClass();
		$access_token = '';
		$need_new_token = false;
		$file_contents = file_get_contents($file_path);
		if (strlen(trim($file_contents))<=0) {
			$need_new_token = true;
		} else {
			$data = json_decode($file_contents);
			if ($data->expires_in < time()) $need_new_token = true;
		}
		if ($need_new_token) {
			// 如果是企业号用以下URL获取access_token
			// $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$this->appId}&corpsecret={$this->appSecret}";
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
			$json = $this->requestData('get', $url, array(), true);
			if (isset($json->access_token)) {
				$access_token = $json->access_token;
				$data->access_token = $access_token;
				$data->expires_in = $json->expires_in + time();
				@chmod($this->file_path, 0777);
				$fp = @fopen($file_path, 'w');
				@fwrite($fp, json_encode($data));
				@fclose($fp);
			}
		} else {
			$access_token = $data->access_token;
		}
		return $access_token;
	}
	
	//生成jssdk签名
	public function getSignPackage($url='') {
		$jsapiTicket = $this->getJsApiTicket();
		// 注意 URL 一定要动态获取，不能 hardcode.
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
		if (!strlen($url)) $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$timestamp = time();
		$nonceStr = $this->createNonceStr();
		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
		$signature = sha1($string);
		$signPackage = array(
			'appId'     => $this->appId,
			'nonceStr'  => $nonceStr,
			'timestamp' => $timestamp,
			'url'       => $url,
			'signature' => $signature,
			'rawString' => $string
		);
		return $signPackage;
	}
	
	//获取jssdk - jsapi_ticket
	public function getJsApiTicket($filename='jsapi_ticket.json', $tokenname='access_token.json') {
		if (strlen($this->remoteUrl)) {
			$file_contents = file_get_contents($this->remoteUrl);
			$data = json_decode($file_contents);
			return $data->jsapi_ticket;
		}
		$file_path = $this->file_path . $filename;
		$this->makeDir(dirname($file_path));
		if (!file_exists($file_path)) {
			$handle = @fopen($file_path, 'a') or die('UNABLE TO OPEN FILE');
			@fwrite($handle, '');
			@fclose($handle);
		}
		$data = new stdClass();
		$ticket = '';
		$need_new_token = false;
		$file_contents = file_get_contents($file_path);
		if (strlen(trim($file_contents))<=0) {
			$need_new_token = true;
		} else {
			$data = json_decode($file_contents);
			if ($data->expires_in < time()) $need_new_token = true;
		}
		if ($need_new_token) {
			$access_token = $this->getAccessToken($tokenname);
			// 如果是企业号用以下 URL 获取 ticket
			// $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token={$access_token}";
			$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token={$access_token}";
			$json = $this->requestData('get', $url, array(), true);
			if (isset($json->ticket)) {
				$ticket = $json->ticket;
				$data->jsapi_ticket = $ticket;
				$data->expires_in = $json->expires_in + time();
				@chmod($this->file_path, 0777);
				$fp = @fopen($file_path, 'w');
				@fwrite($fp, json_encode($data));
				@fclose($fp);
			}
		} else {
			$ticket = $data->jsapi_ticket;
		}
		return $ticket;
	}
	
	//获取openid
	public function openid($redirect_uri='', $appid='', $secret='', $state='STATE') {
		if (!strlen($appid)) $appid = $this->appId;
		if (!strlen($secret)) $secret = $this->appSecret;
		$code = $this->request->get('code');
		if (!strlen($code)) {
			if (!strlen($redirect_uri)) $redirect_uri = https().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$redirect_uri = urlencode($redirect_uri);
			$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";
			header("location:{$url}");
			exit;
		}
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
		$json = $this->requestData('get', $url, array(), true);
		return isset($json['openid']) ? $json['openid'] : '';
	}
	
	//获取普通access_token
	public function access_token($appid='', $secret='', $access_token_filename='', $is_refresh=false) {
		if (!$access_token_filename) $access_token_filename = ((strlen($appid) && $appid!=$this->appId) ? $appid.'/' : '')."access_token.json";
		if (!strlen($appid)) $appid = $this->appId;
		if (!strlen($secret)) $secret = $this->appSecret;
		$expires_in = 0;
		$json = $this->getJson($access_token_filename, true);
		if (is_array($json)) {
			//$access_token = $json['access_token'];
			$expires_in = intval($json['expires_in']);
		}
		if ($is_refresh || time()>$expires_in) {
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
			$json = $this->requestData('get', $url, array(), true);
			if (isset($json['errcode']) && intval($json['errcode'])!=0) {
				if (intval($json['errcode'])==40001) {
					$this->log(json_encode($json));
					exit($json['errmsg'].'<br />expires_in is: '.date('Y-m-d H:i:s', $expires_in));
				} else if (intval($json['errcode'])==40164) {
					exit(preg_replace('/^invalid ip (\d+\.\d+\.\d+\.\d+).+$/', '$1 没有添加到白名单', $json['errmsg']));
				} else {
					$this->log(json_encode($json));
					exit($json['errmsg'].' errcode: '.$json['errcode']);
				}
			}
			$json['expires_in'] = intval($json['expires_in'])+time();
			$this->saveFile($access_token_filename, json_encode($json));
		}
		return $json;
	}
	
	//查询自定义菜单
	public function getMenu($appid='', $secret='', $passway=false) {
		if (!strlen($appid) && !strlen($secret)) {
			$json = $this->access_token($appid, $secret);
			$access_token = $json['access_token'];
		} else {
			$json = $this->authorizer_access_token('', $appid, $passway);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
		}
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (is_null($json)) die('WEIXIN GET MENU API ERROR');
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (intval($json['errcode'])==46003) return NULL;
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
				return false;
			}
		}
		return $json;
	}
	
	//创建自定义菜单
	//https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141013&token=&lang=zh_CN
	public function setMenu($data, $appid='', $secret='', $passway=false) {
		if (!strlen($appid) && !strlen($secret)) {
			$json = $this->access_token($appid, $secret);
			$access_token = $json['access_token'];
		} else {
			$json = $this->authorizer_access_token('', $appid, $passway);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
		}
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (is_null($json)) die('WEIXIN CREATE MENU API ERROR');
		if (isset($json['errcode'])&& intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
				return false;
			}
		}
		return true;
	}
	
	//删除自定义菜单
	public function deleteMenu($appid='', $secret='', $passway=false) {
		if (!strlen($appid) && !strlen($secret)) {
			$json = $this->access_token($appid, $secret);
			$access_token = $json['access_token'];
		} else {
			$json = $this->authorizer_access_token('', $appid, $passway);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
		}
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (is_null($json)) die('WEIXIN DELETE MENU API ERROR');
		if (isset($json['errcode'])&& intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
				return false;
			}
		}
		return true;
	}
	
	//从模板库通过模板编号添加模板消息
	//https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1433751277
	public function addTemplateMessage($template_id_short, $appid='', $secret='', $passway=false) {
		if (!strlen($appid) && !strlen($secret)) {
			$json = $this->access_token($appid, $secret);
			$access_token = $json['access_token'];
		} else {
			$json = $this->authorizer_access_token('', $appid, $passway);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
		}
		if (!strlen($access_token)) return false;
		if (!is_array($template_id_short)) $template_id_short = array($template_id_short);
		$url = "https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token={$access_token}";
		$array = array();
		foreach ($template_id_short as $id) {
			$data = array('template_id_short'=>$id);
			$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
			if (isset($json['errcode']) && intval($json['errcode'])!=0) {
				if (!$passway) {
					exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
				} else {
					$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
					return false;
				}
			}
			$array[] = $json['template_id'];
		}
		return $array;
	}
	
	//获取模板消息列表
	public function getTemplateMessage($appid='', $secret='', $passway=false) {
		if (!strlen($appid) && !strlen($secret)) {
			$json = $this->access_token($appid, $secret);
			$access_token = $json['access_token'];
		} else {
			$json = $this->authorizer_access_token('', $appid, $passway);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
		}
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
				return false;
			}
		}
		/*列表格式：[{
			"template_id":"模板ID",
			"title":"模板标题",
			"primary_industry":"模板所属行业的一级行业",
			"deputy_industry":"模板所属行业的二级行业",
			"content":"{{result.DATA}}\n\n领奖金额:{{withdrawMoney.DATA}}\n银行信息:{{cardInfo.DATA}}\n到账时间:{{arrivedTime.DATA}}\n{{remark.DATA}}",
			"example":"您已提交领奖申请\n\n领奖金额:xxxx元\n银行信息:xx银行(尾号xxxx)\n到账时间:预计xxxxxxx\n\n预计将于xxxx到达您的银行卡"
		}]*/
		return $json['template_list'];
	}
	
	//发送模板消息
	public function sendTemplateMessage($appid, $touser, $template_id, $data, $target_url='', $miniprogram=array(), $secret='', $passway=false) {
		if (!strlen($appid) && !strlen($secret)) {
			$json = $this->access_token($appid, $secret);
			$access_token = $json['access_token'];
		} else {
			$json = $this->authorizer_access_token('', $appid, $passway);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
		}
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
		$dat = array();
		$dat['touser'] = $touser; //openid
		$dat['template_id'] = $template_id; //模板ID
		$dat['data'] = $data;
		/*data格式: {
			"keyword1":{"value":"修水龙头", "color":"#173177"},
			"keyword2":{"value":"水工", "color":"#173177"}
		}*/
		if (strlen($target_url)) $dat['url'] = $target_url; //模板跳转链接
		if (count($miniprogram)) $dat['miniprogram'] = $miniprogram;
		/*miniprogram格式: {
			"appid": "所需跳转到的小程序appid",
			"pagepath": "所需跳转到小程序的具体页面路径,支持带参数"
		}*/
		//注：url和miniprogram都是非必填字段,若都不传则模板无跳转;若都传,会优先跳转至小程序。当用户的微信客户端版本不支持跳小程序时将会跳转至url
		$json = $this->requestData('post', $url, json_encode($dat, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
				return false;
			}
		}
		return array($json['msgid']);
	}
	
	//获取带参数二维码, type为temp时生成临时二维码
	public function getQrcode($scene_id, $type='', $expire_seconds=604800) {
		$ticket = $this->getQrcodeTicket($scene_id, $type, $expire_seconds);
		if (!strlen($ticket)) $this->showError('TICKET获取错误');
		$url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket);
		$image = file_get_contents($url);
		return $image;
		//header('Content-type: image/jpeg');
		//$image = $wechat->getQrcode(30);
		//echo $image;
	}
	//获取带参数二维码ticket
	public function getQrcodeTicket($scene_id, $type='', $expire_seconds=604800, $appid='', $secret='') {
		if ($type=='temp') {
			$data = '{"expire_seconds": %s, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
			$data = sprintf($data, $expire_seconds, $scene_id);
		} else {
			$data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": %s}}}';
			$data = sprintf($data, $scene_id);
		}
		$json = $this->access_token($appid, $secret);
		$access_token = $json['access_token'];
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$access_token}";
		$json = $this->requestData('post', $url, $data, true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			$this->log(json_encode($json));
			return '';
		}
		//{"ticket":"gQH47joAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL2taZ2Z3TVRtNzJXV1Brb3ZhYmJJAAIEZ23sUwMEmm3sUw==","expire_seconds":60,"url":"http://weixin.qq.com/q/kZgfwMTm72WWPkovabbI"}
		return $json->ticket;
	}
	
	//获取临时素材
	public function getMedia($media_id, $appid='', $secret='', $passway=false) {
		if (!strlen($appid) && !strlen($secret)) {
			$json = $this->access_token($appid, $secret);
			$access_token = $json['access_token'];
		} else {
			$json = $this->authorizer_access_token('', $appid, $passway);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
		}
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$media_id}";
		$res = file_get_contents($url);
		$json = json_decode($res, true);
		if ($json && isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
				return false;
			}
		}
		return $res; //图片Buffer
		//header('Content-Type:image/png');
		//echo $res;
	}
	
	//上传临时素材
	public function setMedia($file, $appid='', $secret='', $passway=false) {
		if (!file_exists($file)) exit('FILE NOT EXIST');
		if (!strlen($appid) && !strlen($secret)) {
			$json = $this->access_token($appid, $secret);
			$access_token = $json['access_token'];
		} else {
			$json = $this->authorizer_access_token('', $appid, $passway);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
		}
		if (!strlen($access_token)) return false;
		$file = ROOT_PATH.str_replace(ROOT_PATH, '', $file);
		$type = 'image';
		$url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$access_token}&type={$type}";
		$fileInfo = getimagesize($file);
		$data = array('media'=>"@{$file}", 'form-data'=>array(
			'filename'=>str_replace(PUBLIC_PATH, '', $file), //图片相对于网站根目录的路径
			'content-type'=>$fileInfo['mime'], //文件类型
			'filelength'=>filesize($file) //图文大小
		));
		$json = $this->requestData('post', $url, $data, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$appid);
				return false;
			}
		}
		return $json['media_id'];
	}
	
	//CURL方式请求
	public function requestData($method, $url, $params=array(), $returnJson=false, $postJson=false, $headers=array(), $getHeader=false) {
		$method = strtoupper($method);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			curl_setopt($ch, CURLOPT_USERAGENT, join(' ', array_filter(array($_SERVER['HTTP_USER_AGENT'], 'SDK/'.API_VERSION.' PHP/'.PHP_VERSION))));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); //请求超时
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); //执行超时
		switch ($method){ //请求方式
			case 'POST':curl_setopt($ch, CURLOPT_POST, TRUE);break;
			case 'PUT':
			case 'PATCH':
			case 'DELETE':curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);break;
			default:curl_setopt($ch, CURLOPT_HTTPGET, TRUE);break;
		}
		if (is_array($headers) && count($headers)) {
			$headers[] = "X-HTTP-Method-Override: {$method}"; //HTTP头信息
		} else {
			$headers = array("X-HTTP-Method-Override: {$method}");
		}
		if (is_array($headers) && count($headers)) {
			//使用JSON提交
			if ($postJson) {
				$headers[] = 'Content-type: application/json;charset=UTF-8';
				if (!empty($params) && is_array($params)) $params = json_encode($params, JSON_UNESCAPED_UNICODE);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if (substr($url, 0, 8)=='https://') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //对认证证书来源的检查
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //从证书中检查SSL加密算法是否存在
			//curl_setopt($ch, CURLOPT_SSLVERSION, 3); //SSL版本
		}
		if (!empty($params)) {
			if (is_array($params)) {
				if (class_exists('\CURLFile')) {
					foreach ($params as $key => $param) {
						if (is_string($param) && preg_match('/^@/', $param)) $params[$key] = new CURLFile(realpath(trim($param, '@')));
					}
				} else {
					if (defined('CURLOPT_SAFE_UPLOAD')) curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE); //指定PHP5.5及以上兼容@语法,否则需要使用CURLFile
				}
			}
			//如果data为数组即使用multipart/form-data提交, 为字符串即使用application/x-www-form-urlencoded
			@curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
		//附加 Authorization: Basic <Base64(id:key)>
		//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		//curl_setopt($ch, CURLOPT_USERPWD, "api:{$key}");
		if ($getHeader) {
			curl_setopt($ch, CURLOPT_HEADER, TRUE);
		}
		$res = curl_exec($ch);
		if ($getHeader) {
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($http_code==301 || $http_code==302) {
				$headers = explode("\r\n", $res);
				$header = '';
				foreach ($headers as $_header) {
					if (preg_match('/^Location: (.+)$/', $_header)) {
						$header = $_header;
						break;
					}
				}
				preg_match('/^Location: (.+)$/', $header, $matcher);
				$url = $matcher[1];
				$res = requestData($method, $url, $params, false, $postJson, $headers, $getHeader);
			}
		}
		$result = $res;
		if ($res === false) {
			echo 'Curl error: ' . curl_error($ch);
			exit;
		}
		curl_close($ch);
		if ($returnJson) {
			$res = json_decode($res, true);
			if (is_null($res)) $res = NULL;
		}
		if (is_null($res)) write_log(print_r($result, true));
		return $res;
	}
	
	//创建文件夹, 支持创建多级目录
	public function makeDir($name) {
		if (is_dir($name)) return true;
		$name = str_replace('\\', '/', $name);
		$root_path = defined('ROOT_PATH') ? ROOT_PATH : $_SERVER['DOCUMENT_ROOT'];
		$root = str_replace('\\', '/', $root_path);
		$relative = str_replace($root, '', $name);
		$each = explode('/', $relative);
		$path = $root;
		foreach ($each as $p) {
			if ($p) {
				$path .= '/'.$p;
				if (!is_dir($path)) {
					if (@mkdir($path, 0777)) {
						@chmod($path, 0777);
						@fclose(@fopen($path . '/index.html', 'w'));
					} else {
						return false;
					}
				} else {
					@chmod($path, 0777);
				}
			}
		}
		return true;
	}
	
	//删除文件夹及其所有文件
	public function deleteDir($name) {
		//先删除目录下的文件：
		$dh = opendir($name);
		while ($file=readdir($dh)) {
			if ($file!='.' && $file!='..') {
				$fullpath = $name.'/'.$file;
				if (!is_dir($fullpath)) {
					unlink($fullpath);
				} else {
					$this->deleteDir($fullpath);
				}
			}
		}
		closedir($dh);
		//删除当前文件夹：
		if (rmdir($name)) {
			return true;
		} else {
			return false;
		}
	}
	
	//获取文件内容且转为json
	public function getJson($filename, $assoc=false) {
		$str = $this->getFile($filename);
		$json = json_decode($str, $assoc);
		return $json;
	}
	
	//获取文件内容
	public function getFile($filename) {
		$filename = $this->file_path.$filename;
		if (!file_exists($filename)) return '';
		return file_get_contents($filename);
	}
	
	//保存文件
	public function saveFile($filename, $content) {
		$filename = $this->file_path.str_replace($this->file_path, '', $filename);
		$path = $filename;
		$paths = explode('/', $path);
		$path = str_replace('/'.$paths[count($paths)-1], '', $path);
		$this->makeDir($path);
		$handle = @fopen($filename, 'w') or die('UNABLE TO OPEN FILE');
		@fwrite($handle, $content);
		@fclose($handle);
		@chmod($filename, 0777);
	}
	
	//删除文件,默认ACCESS_TOKEN缓存
	public function deleteFile($filename='') {
		if (!$filename) $filename = 'access_token.json';
		$filename = $this->file_path.$filename;
		if (!file_exists($filename)) return false;
		return unlink($filename);
	}
	
	//下载媒体文件
	public function downloadFile($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_NOBODY, 0); //只取body头
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$package = curl_exec($ch);
		if ($package === false) {
			echo 'Curl error: ' . curl_error($ch);
			exit;
		}
		$httpinfo = curl_getinfo($ch);
		curl_close($ch);
		$data = json_decode($package, true);
		if (!is_null($data)) {
			if (isset($data['errcode']) && intval($data['errcode'])!=0) {
				if (intval($data['errcode'])==40001) {
					$this->access_token('', '', '', true);
					$json = $this->downloadFile($url);
					$package = $json['body'];
					$httpinfo = $json['header'];
				} else {
					$this->log(json_encode($data));
					exit($data['errmsg'].' errcode: '.$data['errcode']);
				}
			}
		}
		return array('header'=>$httpinfo, 'body'=>$package);
	}
	
	//美化json字符串
	public function formatJSON($json, $indent='') {
		if (!isset($json)) return '';
		if (!strlen($indent)) $indent = chr(9);
		if (!is_string($json)) $json = urldecode(json_encode($this->url_encode($json)));
		$json = str_replace('\\/', '/', $json);
		$result = '';
		$pos = 0;
		$line = "\n";
		$prevChar = '';
		$outOfQuotes = true;
		for ($i=0; $i<=strlen($json); $i++) {
			$char = substr($json, $i, 1);
			if ($char=='"' && $prevChar!='\\') {
				$outOfQuotes = !$outOfQuotes;
			} else if (($char=='}' || $char==']') && $outOfQuotes) {
				$result .= $line;
				$pos--;
				for ($j=0; $j<$pos; $j++) $result .= $indent;
			}
			$result .= $char;
			if (($char==',' || $char=='{' || $char=='[') && $outOfQuotes) {
				$result .= $line;
				if ($char=='{' || $char=='[') $pos++;
				for ($j=0; $j<$pos; $j++) $result .= $indent;
			}
			$prevChar = $char;
		}
		return $result;
	}
	
	//将对象/数组进行urlencode
	public function url_encode($obj) {
		if (is_array($obj)) {
			foreach ($obj as $key=>$value) $obj[urlencode($key)] = $this->url_encode($value);
		} else if (is_object($obj)) {
			foreach($obj as $name=>$value) $obj->$name = $this->url_encode($obj->$name);
		} else if (is_string($obj)) {
			$obj = urlencode($obj);
		}
		return $obj;
	}
	
	//生成由数字,大写字母,小写字母组合的指定位数的随机数,str:指定字符,rndCode(8,"")
	public function rndCode($num, $str='') {
		if (!strlen($str)) $str = '9zML5pGCkBAJQ2Zh4de1RlqNPno8m3FKijbrc6SDEas7O0TUXYtwxuVHWvIfgy'; //可使用中文字
		if (!is_numeric($num)) return '';
		$rndCode = '';
		$str = strval($str);
		$count = strlen($str);
		for ($o=1; $o<=$num; $o++) {
			$Rnd = mt_rand(0, $count-1);
			$rndCode .= substr($str, $Rnd, 1);
		}
		return $rndCode;
	}
	
	//将内容进行UNICODE编码，编码后的内容格式：\u56fe\u7247 （原始：图片）
	public function unicode_encode($name) {
		$name = iconv('UTF-8', 'UCS-2', $name);
		$len = strlen($name);
		$str = '';
		for ($i = 0; $i < $len - 1; $i = $i + 2) {
			$c = $name[$i];
			$c2 = $name[$i + 1];
			if (ord($c) > 0) { // 两个字节的文字
				$str .= '\u'.base_convert(ord($c), 10, 16).base_convert(ord($c2), 10, 16);
			} else {
				$str .= $c2;
			}
		}
		return $str;
	}
	
	//记录LOG
	public function log($content, $trace=false) {
		$filename = $this->file_path.'wxlog.txt';
		$traceStr = '';
		if ($trace) {
			$e = new Exception;
			$trace = $e->getTraceAsString();
			$traceStr = "\n\n".$trace;
		}
		file_put_contents($filename, date('Y-m-d H:i:s')."\n".$content.$traceStr."\n==============================\n\n", FILE_APPEND);
	}
	
	//第三方开发平台操作===============================================================================================
	//伪静增加 ^wx\w{16}/(.+)$ /$1
	
	//保存微信服务器推送的component_verify_ticket
	private function component_verify_ticket($component_verify_ticket) {
		$component_appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : '';
		$component_verify_ticket_filename = $component_appid."/component_verify_ticket.txt";
		$this->saveFile($component_verify_ticket_filename, $component_verify_ticket);
		//if (ob_get_level() == 0) ob_start();
		//ob_implicit_flush(true);
		//ob_clean();
		exit('success');
		//ob_flush();
		//flush();
		//ob_end_flush();
	}
	
	//获取component_access_token
	public function component_access_token() {
		$component_appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : '';
		$component_secret = is_array($this->WX_THIRD) ? $this->WX_THIRD['secret'] : '';
		$component_verify_ticket_filename = $component_appid."/component_verify_ticket.txt";
		if (!$this->getFile($component_verify_ticket_filename)) {
			header("Content-type: text/html; charset=utf-8");
			exit('不存在COMPONENT_VERIFY_TICKET凭据文件，请在10分钟后再次打开本链接');//NO COMPONENT_VERIFY_TICKET
		}
		$component_access_token_filename = $component_appid."/component_access_token.json";
		$access_token = '';
		$expires_in = 0;
		$json = $this->getJson($component_access_token_filename, true);
		if (is_array($json)) {
			$access_token = $json['component_access_token'];
			$expires_in = intval($json['expires_in']);
		}
		if (time()>$expires_in-60){
			$url = "https://api.weixin.qq.com/cgi-bin/component/api_component_token";
			$data = array();
			$data['component_appid'] = $component_appid;
			$data['component_appsecret'] = $component_secret;
			$data['component_verify_ticket'] = $this->getFile($component_verify_ticket_filename);
			$json = $this->requestData('post', $url, json_encode($data), true, true);
			if (isset($json['errcode']) && intval($json['errcode'])!=0) {
				exit($json['errmsg'].' errcode: '.$json['errcode']);
			}
			$json['expires_in'] = intval($json['expires_in'])+time();
			$access_token = $json['component_access_token'];
			$this->saveFile($component_access_token_filename, json_encode($json, JSON_UNESCAPED_UNICODE));
		}
		return $access_token;
	}
	
	//获取预授权码pre_auth_code
	public function pre_auth_code() {
		$component_appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : '';
		$component_access_token = $this->component_access_token();
		if (!$component_access_token) exit('NO COMPONENT_ACCESS_TOKEN');
		$url = "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token={$component_access_token}";
		$data = array();
		$data['component_appid'] = $component_appid;
		$json = $this->requestData('post', $url, json_encode($data), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			exit($json['errmsg'].' errcode: '.$json['errcode']);
		}
		$pre_auth_code = $json['pre_auth_code'];
		return $pre_auth_code;
	}
	
	//使用授权码(component_auth回调得到的auth_code)换取公众号的接口调用凭据(authorizer_access_token)和授权信息
	public function authorizer_access_token($auth_code='', $mp_appid='', $passway=false) {
		$component_appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : '';
		$expires_in = 0;
		$json = NULL;
		if (strlen($mp_appid)) {
			$path = $component_appid.'/'.$mp_appid;
			$mp_path = is_dir($this->file_path.$path) ? $path : $component_appid;
			$authorizer_access_token_filename = $mp_path."/authorizer_access_token.json";
			$json = $this->getJson($authorizer_access_token_filename, true);
			if (is_array($json)) {
				//$access_token = $json['authorizer_access_token'];
				$expires_in = intval($json['expires_in']);
			}
		}
		if (time()>$expires_in-60){
			$component_access_token = $this->component_access_token();
			if (strlen($auth_code)) {
				$url = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token={$component_access_token}";
				$data = array();
				$data['component_appid'] = $component_appid;
				$data['authorization_code'] = $auth_code;
				$json = $this->requestData('post', $url, json_encode($data), true, true);
				if (isset($json['errcode']) && intval($json['errcode'])!=0) {
					if (!$passway) {
						exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
					} else {
						$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
					}
				}
				$json = $json['authorization_info'];
				$json['expires_in'] = intval($json['expires_in'])+time();
				//获取授权方资料
				$userinfo = $this->authorizer_userinfo($json['authorizer_appid']);
				$json['authorizer_info'] = $userinfo['authorizer_info'];
				//$userinfo['authorizer_info']['user_name'] 原始id
				$authorizer_path = $component_appid.'/'.$json['authorizer_appid'];
				$authorizer_access_token_filename = $authorizer_path."/authorizer_access_token.json";
				$this->saveFile($authorizer_access_token_filename, json_encode($json, JSON_UNESCAPED_UNICODE));
				if (!is_dir($this->file_path.$authorizer_path)) {
					$this->makeDir($this->file_path.$authorizer_path);
				}
			} else if ($expires_in>0 && isset($json['authorizer_appid'])) {
				$url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token={$component_access_token}";
				$data = array();
				$data['component_appid'] = $component_appid;
				$data['authorizer_appid'] = $json['authorizer_appid'];
				$data['authorizer_refresh_token'] = $json['authorizer_refresh_token'];
				$json_refresh = $this->requestData('post', $url, json_encode($data), true, true);
				if (isset($json_refresh['errcode']) && intval($json_refresh['errcode'])!=0) {
					if (!$passway) {
						exit(__FUNCTION__.': '.$json_refresh['errmsg'].' errcode: '.$json_refresh['errcode'].' appid: '.$data['authorizer_appid']);
					} else {
						$this->log(__FUNCTION__.': '.$json_refresh['errmsg'].' errcode: '.$json_refresh['errcode'].' appid: '.$data['authorizer_appid']);
						return NULL;
					}
				}
				$json['authorizer_access_token'] = $json_refresh['authorizer_access_token'];
				$json['authorizer_refresh_token'] = $json_refresh['authorizer_refresh_token'];
				$json['expires_in'] = intval($json_refresh['expires_in'])+time();
				$authorizer_path = $component_appid.'/'.$json['authorizer_appid'];
				$authorizer_access_token_filename = $authorizer_path."/authorizer_access_token.json";
				$this->saveFile($authorizer_access_token_filename, json_encode($json, JSON_UNESCAPED_UNICODE));
			} else {
				if (!$passway) {
					$url = https().$_SERVER['HTTP_HOST']."/wx_interface?act=component_auth&component_appid={$component_appid}";
					header("location:{$url}");
					exit;
				}
			}
		}
		return $json;
	}
	
	//获取授权方资料
	public function authorizer_userinfo($authorizer_appid, $passway=false) {
		$component_appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : '';
		$component_access_token = $this->component_access_token();
		$url = "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token={$component_access_token}";
		$data = array();
		$data['component_appid'] = $component_appid;
		$data['authorizer_appid'] = $authorizer_appid;
		$json = $this->requestData('post', $url, json_encode($data), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return $json;
	}
	
	//获取授权方关注者列表
	public function authorizer_userlist($authorizer_appid, $next_openid='', $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return NULL;
		$url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token={$access_token}&next_openid={$next_openid}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return $json;
	}
	
	//接口调用次数清零
	public function clear_quota($appid='', $passway=false) {
		$component_appid = 'COMPONENT APPID';
		if (strlen($appid)) {
			$json = $this->authorizer_access_token('', $appid, $passway);
			if (!$json) return false;
			$access_token = $json['authorizer_access_token'];
			if (!strlen($access_token)) return false;
			$url = "https://api.weixin.qq.com/cgi-bin/clear_quota?access_token={$access_token}";
			$data = array('appid'=>$appid);
		} else {
			$component_appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : '';
			$component_access_token = $this->component_access_token();
			$url = "https://api.weixin.qq.com/cgi-bin/component/clear_quota?component_access_token={$component_access_token}";
			$data = array('component_appid'=>$component_appid);
		}
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].(strlen($appid) ? ' appid: '.$appid : ' component_appid: '.$component_appid));
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].(strlen($appid) ? ' appid: '.$appid : ' component_appid: '.$component_appid));
				return false;
			}
		}
		return true;
	}
	
	//获取网页授权access_token
	public function auth_access_token($appid='', $secret='', $code='', $access_token_filename='') {
		if (!$access_token_filename) $access_token_filename = ((strlen($appid) && $appid!=$this->appId) ? $appid.'/' : '')."auth_access_token.json";
		if (!strlen($appid)) $appid = $this->appId;
		if (!strlen($secret)) $secret = $this->appSecret;
		$expires_in = 0;
		$json = $this->getJson($access_token_filename, true);
		if (is_array($json)) {
			//$access_token = $json['access_token'];
			$expires_in = intval($json['expires_in']);
		}
		if (time()>$expires_in-60) {
			if ($code) {
				$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
				$json = $this->requestData('get', $url, array(), true);
				if (isset($json['errcode']) && intval($json['errcode'])!=0) {
					exit($json['errmsg'].' errcode: '.$json['errcode']);
				}
				$json['expires_in'] = intval($json['expires_in'])+time();
				$this->saveFile($access_token_filename, json_encode($json));
			} else if ($expires_in>0) {
				$refresh_token = $json['refresh_token'];
				$url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid={$appid}&grant_type=refresh_token&refresh_token={$refresh_token}";
				$json_refresh = $this->requestData('get', $url, array(), true);
				if (isset($json_refresh['errcode']) && intval($json_refresh['errcode'])!=0) {
					exit($json_refresh['errmsg'].' errcode: '.$json_refresh['errcode']);
				}
				$json['access_token'] = $json_refresh['access_token'];
				$json['refresh_token'] = $json_refresh['refresh_token'];
				$json['expires_in'] = intval($json_refresh['expires_in'])+time();
				$this->saveFile($access_token_filename, json_encode($json));
			} else {
				$url = https().$_SERVER['HTTP_HOST']."/wx_interface?act=weixin_auth";
				header("location:{$url}");
				exit;
			}
		}
		return $json;
	}
	
	//电脑端打开进行公众号授权给第三方开发平台
	public function component_auth() {
		$component_appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : '';
		$code = $this->request->get('auth_code');
		//先获取auth_code
		if (!strlen($code)) {
			$redirect_uri = urlencode(https().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			$pre_auth_code = $this->pre_auth_code();
			$url = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid={$component_appid}&pre_auth_code={$pre_auth_code}&redirect_uri={$redirect_uri}";
			echo "<script>location.href = '{$url}';</script>";
			//header("location:{$url}");
			exit;
		} else {
			return $this->authorizer_access_token($code);
		}
	}
	
	//普通微信号网页授权(第三方开发平台引起)
	public function mp_auth() {
		$appid = isset($_GET['appid']) ? $_GET['appid'] : '';
		$component_appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : '';
		$code = $this->request->get('code');
		//先获取code
		if (!strlen($code)) {
			$url = https().$_SERVER['HTTP_HOST']."/wx_interface?act=mp_auth&appid=".$appid;
			$redirect_uri = urlencode($url);
			$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state=STATE&component_appid={$component_appid}#wechat_redirect";
			header("location:{$url}");
			exit;
		} else {
			return $this->mp_access_token($code);
		}
	}
	
	//获取公众号的access_token
	public function mp_access_token($code='') {
		$appid = isset($_GET['appid']) ? $_GET['appid'] : '';
		$component_appid = is_array($this->WX_THIRD) ? $this->WX_THIRD['appid'] : '';
		$access_token_filename = $component_appid."/".$appid."/mp_access_token.json";
		if (strlen($code)) {
			$component_access_token = $this->component_access_token();
			$url = "https://api.weixin.qq.com/sns/oauth2/component/access_token?appid={$appid}&code={$code}&grant_type=authorization_code&component_appid={$component_appid}&component_access_token={$component_access_token}";
			$json = $this->requestData('get', $url, array(), true);
			if (isset($json['errcode']) && intval($json['errcode'])!=0) {
				exit($json['errmsg'].' errcode: '.$json['errcode']);
			}
			$json['expires_in'] = intval($json['expires_in'])+time();
			$this->saveFile($access_token_filename, json_encode($json));
			return $json;
		}
		$expires_in = 0;
		$json = $this->getJson($access_token_filename, true);
		if (is_array($json)) {
			//$access_token = $json['access_token'];
			$expires_in = intval($json['expires_in']);
		}
		if (time()>$expires_in-60) {
			$url = https().$_SERVER['HTTP_HOST']."/wx_interface?act=mp_auth&appid=".$appid;
			header("location:{$url}");
			exit;
		}
		return $json;
	}
	
	//普通微信号网页授权
	public function weixin_auth() {
		$appid = $this->appId;
		$secret = $this->appSecret;
		$code = $this->request->get('code');
		//先获取code
		if (!strlen($code)) {
			$url = https().$_SERVER['HTTP_HOST']."/wx_interface?act=weixin_auth";
			$redirect_uri = urlencode($url);
			$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
			header("location:{$url}");
			exit;
		} else {
			$json = $this->auth_access_token($appid, $secret, $code);
			$access_token = $json['access_token'];
			$openid = $json['openid'];
			//获取用户信息
			$json = $this->get_userinfo($access_token, $openid);
			return $json;
		}
	}
	
	//获取用户基本信息
	public function get_userinfo($access_token, $openid) {
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			$this->log(json_encode($json));
			exit($json['errmsg'].' errcode: '.$json['errcode']);
		}
		$json = $this->wash_userinfo($json);
		$json['openid'] = $openid;
		return $json;
	}
	
	//获取用户基本信息(包括UnionID机制,关注过公众号才能获取到)
	public function get_userinfo_unionid($access_token, $openid) {
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			$this->log(json_encode($json));
			exit($json['errmsg'].' errcode: '.$json['errcode']);
		}
		$json = $this->wash_userinfo($json);
		$json['openid'] = $openid;
		return $json;
	}
	
	//设置用户基本信息的兼容
	public function wash_userinfo($json) {
		$nickname = isset($json['nickname']) ? $json['nickname'] : $json['nickName'];
		//$nickname = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $nickname);
		//$nickname = $this->removeEmoji($nickname);
		//$nickname = addslashes($nickname);
		$json['nickname'] = $nickname;
		$sex = isset($json['sex']) ? $json['sex'] : (isset($json['gender']) ? $json['gender'] : 1);
		if (is_numeric($sex)) $sex = intval($sex)==1 ? '男' : '女';
		$json['sex'] = $sex;
		$headimgurl = isset($json['headimgurl']) ? $json['headimgurl'] : $json['avatarUrl'];
		$json['headimgurl'] = $headimgurl;
		return $json;
	}
	
	//去除emoji表情符
	public function removeEmoji($text) {
		// Match Emoticons
		$regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
		$text = preg_replace($regexEmoticons, '', $text);
		// Match Miscellaneous Symbols and Pictographs
		$regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
		$text = preg_replace($regexSymbols, '', $text);
		// Match Transport And Map Symbols
		$regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
		$text = preg_replace($regexTransport, '', $text);
		// Match Miscellaneous Symbols
		$regexMisc = '/[\x{2600}-\x{26FF}]/u';
		$text = preg_replace($regexMisc, '', $text);
		// Match Dingbats
		$regexDingbats = '/[\x{2700}-\x{27BF}]/u';
		$text = preg_replace($regexDingbats, '', $text);
		return $text;
	}
	
	//把微信个人资料(部分)进行md5
	public function md5_userinfo($json) {
		$md5 = '';
		if (is_string($json)) $json = json_decode($json, true);
		if (!is_array($json)) $json = json_decode(json_encode($json), true);
		if (is_array($json) && isset($json['nickname']) && isset($json['sex']) && isset($json['country'])
			&& isset($json['city']) && isset($json['province']) && isset($json['language'])) {
			if (is_numeric($json['sex'])) $json['sex'] = intval($json['sex'])==1 ? '男' : '女';
			$json['nickname'] = $this->unicode_encode($json['nickname']);
			$arr = array($json['nickname'], $json['sex'], $json['country'], $json['province'], $json['city'], $json['language']);
			$md5 = implode($arr);
			//$this->log($md5);
			$md5 = md5($md5);
		}
		return $md5;
	}
	
	//第三方平台小程序操作===============================================================================================
	
	//设置小程序服务器地址，无需加https前缀，但域名必须可以通过https访问，domain域名地址(string|array)
	public function miniprogramServerDomain($authorizer_appid, $domain, $action='add', $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/modify_domain?access_token={$access_token}";
		$data = array();
		if (is_array($domain)) {
			$requestdomain = array();
			$wsrequestdomain = array();
			$uploaddomain = array();
			$downloaddomain = array();
			foreach ($domain as $key => $value) {
				$value = preg_replace('/^https?:\/\/(.+)$/', '$1', $value);
				$requestdomain[] = 'https://'.$value;
				$wsrequestdomain[] = 'wss://'.$value;
				$uploaddomain[] = 'https://'.$value;
				$downloaddomain[] = 'https://'.$value;
			}
			$data['action'] = $action;
			$data['requestdomain'] = $requestdomain;
			$data['wsrequestdomain'] = $wsrequestdomain;
			$data['uploaddomain'] = $uploaddomain;
			$data['downloaddomain'] = $downloaddomain;
		} else {
			$domain = preg_replace('/^https?:\/\/(.+)$/', '$1', $domain);
			$data['action'] = $action;
			$data['requestdomain'] = 'https://'.$domain;
			$data['wsrequestdomain'] = 'wss://'.$domain;
			$data['uploaddomain'] = 'https://'.$domain;
			$data['downloaddomain'] = 'https://'.$domain;
		}
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (intval($json['errcode'])==85017 && $action=='add'){
				return $this->miniprogramServerDomain($authorizer_appid, $domain, 'set', $passway);
			} else {
				if (!$passway) {
					exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				} else {
					$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
					return false;
				}
			}
		}
		return true;
	}
	
	//设置小程序业务域名，无需加https前缀，但域名必须可以通过https访问，domain域名地址(string|array)
	public function miniprogramBusinessDomain($authorizer_appid, $domain, $action='add', $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/setwebviewdomain?access_token={$access_token}";
		$data = array();
		if (is_array($domain)) {
			$webviewdomain = array();
			foreach ($domain as $key => $value) {
				$value = preg_replace('/^https?:\/\/(.+)$/', '$1', $value);
				$webviewdomain[] = 'https://'.$value;
			}
			$data['action'] = $action;
			$data['webviewdomain'] = $webviewdomain;
		} else {
			$domain = preg_replace('/^https?:\/\/(.+)$/', '$1', $domain);
			$data['action'] = $action;
			$data['webviewdomain'] = 'https://'.$domain;
		}
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (intval($json['errcode'])==85017 && $action=='add'){
				return $this->miniprogramBusinessDomain($authorizer_appid, $domain, 'set', $passway);
			} else {
				//出错原因有可能是个人小程序，个人小程序不能设置业务域名
				/*
				if (!$passway) {
					exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				} else {
					$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
					return false;
				}
				*/
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			}
		}
		return true;
	}
	
	//成员管理，获取小程序体验者列表
	public function miniprogramTester($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/memberauth?access_token={$access_token}";
		$data = array('action'=>'get_experiencer');
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return $json['members'];
	}
	
	//成员管理，绑定小程序体验者，wechatid体验者微信号
	public function miniprogramBindTester($authorizer_appid, $wechatid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/bind_tester?access_token={$access_token}";
		$data = array('wechatid'=>$wechatid);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return true;
	}
	
	//成员管理，解绑小程序体验者，wechatid体验者微信号
	public function miniprogramUnBindTester($authorizer_appid, $wechatid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/unbind_tester?access_token={$access_token}";
		$data = array('wechatid'=>$wechatid);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return true;
	}
	
	//为授权的小程序帐号上传小程序代码，template_id模板ID，user_version代码版本号，user_desc代码描述，ext扩展配置(小程序以通过 wx.getExtConfigSync 或 wx.getExtConfig 获取)，extPages单独设置每个页面的json，config配置覆盖
	//https://developers.weixin.qq.com/miniprogram/dev/devtools/ext.html
	public function miniprogramUploadCode($authorizer_appid, $template_id=0, $user_version='1.0.0', $user_desc='修复bug', $ext=array(), $extPages=array(), $config=array(), $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/commit?access_token={$access_token}";
		$ext_json = array();
		$ext_json['extEnable'] = true;
		$ext_json['extAppid'] = $authorizer_appid;
		if (is_array($ext) && count($ext)) {
			$ext_json['ext'] = $ext;
		}
		if (is_array($extPages) && count($extPages)) {
			$ext_json['extPages'] = $extPages;
			/*
			$extPages['pages/logs/logs'] = array(
				'navigationBarTitleText'=>'logs'
			)
			*/
		}
		if (is_array($config) && count($config)) {
			$ext_json = array_merge($ext_json, $config);
		}
		$data = array();
		$data['template_id'] = $template_id;
		$data['ext_json'] = json_encode($ext_json, JSON_UNESCAPED_UNICODE);
		$data['user_version'] = $user_version;
		$data['user_desc'] = $user_desc;
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return true;
	}
	
	//获取小程序的体验二维码，path指定二维码跳转到某个具体小程序页面，不能斜杠开始
	public function miniprogramTestQrcode($authorizer_appid, $path='', $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		if (strlen($path)) {
			$url = "https://api.weixin.qq.com/wxa/get_qrcode?access_token={$access_token}&path=".urlencode($path);
		} else {
			$url = "https://api.weixin.qq.com/wxa/get_qrcode?access_token={$access_token}";
		}
		$res = file_get_contents($url);
		$json = json_decode($res, true);
		if ($json && isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return $res; //图片二进制代码
	}
	
	//小程序提交审核，title小程序页面标题(长度不超过32)，tag小程序标签(多个以空格分开)，home_page小程序首页
	public function miniprogramReview($authorizer_appid, $title, $tag='', $home_page='pages/index/index', $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$first_class = '';
		$second_class = '';
		$first_id = 0;
		$second_id = 0;
		$address = $home_page;
		$category = $this->_miniprogramCategory($access_token, $passway);
		if (!empty($category)) {
			$first_class = isset($category[0]['first_class']) ? $category[0]['first_class'] : '';
			$second_class = isset($category[0]['second_class']) ? $category[0]['second_class'] : '';
			$first_id = isset($category[0]['first_id']) ? $category[0]['first_id'] : 0;
			$second_id = isset($category[0]['second_id']) ? $category[0]['second_id'] : 0;
		}
		$getpage = $this->_miniprogramPage($access_token, $passway);
		if (!strlen($address) && !empty($getpage) && isset($getpage[0])) {
			$address = $getpage[0];
		}
		$url = "https://api.weixin.qq.com/wxa/submit_audit?access_token={$access_token}";
		$data = array();
		$data['item_list'] = array(
			array(
				'address'=>$address,
				'tag'=>$tag,
				'title'=>$title,
				'first_class'=>$first_class,
				'second_class'=>$second_class,
				'first_id'=>$first_id,
				'second_id'=>$second_id
			)
		);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return $json; //需要数据库保存审核编号 $json['auditid']
	}
	//获取授权小程序帐号的可选类目
	private function _miniprogramCategory($access_token, $passway=false) {
		$url = "https://api.weixin.qq.com/wxa/get_category?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		return $json['category_list'];
	}
	//获取小程序的第三方提交代码的页面配置
	private function _miniprogramPage($access_token, $passway=false) {
		$url = "https://api.weixin.qq.com/wxa/get_page?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		return $json['page_list'];
	}
	
	//小程序撤回审核，单个帐号每天审核撤回次数最多不超过1次，一个月不超过10次
	public function miniprogramUnReview($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/undocodeaudit?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return true;
	}
	
	//查询指定版本的审核状态，auditid提交审核时获得的审核编号
	public function miniprogramStatus($authorizer_appid, $auditid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/get_auditstatus?access_token={$access_token}";
		$data = array('auditid'=>$auditid);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		$status = intval($json['status']);
		$status_name = '';
		switch ($status) {
			case 0:$status_name = '审核成功';break;
			case 1:$status_name = '审核被拒绝';break;
			case 2:$status_name = '审核中';break;
			case 3:$status_name = '已撤回';break;
			case 4:$status_name = '审核延后';break;
		}
		return array('status'=>$status, 'status_name'=>$status_name, 'reason'=>(isset($json['reason']) ? $json['reason'] : ''));
	}
	
	//查询最新一次提交的审核状态
	public function miniprogramLastStatus($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/get_latest_auditstatus?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		$status = intval($json['status']);
		$status_name = '';
		switch ($status) {
			case 0:$status_name = '审核成功';break;
			case 1:$status_name = '审核被拒绝';break;
			case 2:$status_name = '审核中';break;
			case 3:$status_name = '已撤回';break;
		}
		return array('auditid'=>$json['auditid'], 'status'=>$status, 'status_name'=>$status_name, 'reason'=>(isset($json['reason']) ? $json['reason'] : ''));
	}
	
	//发布已通过审核的小程序
	public function miniprogramRelease($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/release?access_token={$access_token}";
		$data = new stdClass();
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return true;
	}
	
	//获取第三方平台小程序代码草稿列表，草稿是由第三方平台的开发小程序在使用微信开发者工具上传
	public function miniprogramDraftList($passway=false) {
		$access_token = $this->component_access_token();
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/gettemplatedraftlist?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		//create_time:说开发者上传草稿时间戳
		//user_version:版本号，开发者自定义字段
		//user_desc:版本描述，开发者自定义字段
		//draft_id:草稿id
		return $json['draft_list'];
	}
	
	//第三方平台将草稿添加到代码模板库
	public function miniprogramDraftToTemplate($draft_id=0, $passway=false) {
		$access_token = $this->component_access_token();
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/addtotemplate?access_token={$access_token}";
		$data = array('draft_id'=>$draft_id);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		return true;
	}
	
	//获取第三方平台小程序代码列表
	public function miniprogramTemplateList($passway=false) {
		$access_token = $this->component_access_token();
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/gettemplatelist?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		//create_time:被添加为模版的时间
		//user_version:模版版本号，开发者自定义字段
		//user_desc:模版描述，开发者自定义字段
		//template_id:模板id
		return $json['template_list'];
	}
	
	//第三方平台删除指定代码模版
	public function miniprogramTemplateDelete($template_id=0, $passway=false) {
		$access_token = $this->component_access_token();
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/deletetemplate?access_token={$access_token}";
		$data = array('template_id'=>$template_id);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		return true;
	}
	
	//查询服务商的当月提审限额（quota）和加急次数
	public function miniprogramQueryquota($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/queryquota?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		return $json;
	}
	
	//查询当前设置的最低基础库版本及各版本用户占比
	public function miniprogramGetSupportVersion($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/getweappsupportversion?access_token={$access_token}";
		$data = array();
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		return $json;
	}
	
	//设置最低基础库版本
	public function miniprogramSetSupportVersion($authorizer_appid, $version='1.0.0', $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxa/setweappsupportversion?access_token={$access_token}";
		$data = array('version'=>$version);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		return true;
	}
	
	//快速创建小程序, 注意：创建任务逻辑串行，单次任务结束后才可以使用相同信息下发第二次任务，请注意规避任务阻塞。
	public function miniprogramCreate($name, $code, $legal_persona_wechat, $legal_persona_name, $component_phone='', $category_first=0, $category_second=0, $passway=false) {
		$access_token = $this->component_access_token();
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/component/fastregisterweapp?action=create&component_access_token={$access_token}";
		$data = array(
			'name' => $name, //企业名
			'code' => $code, //企业代码
			'code_type' => 1, //企业代码类型(1：统一社会信用代码，2：组织机构代码，3：营业执照注册号)
			'legal_persona_wechat' => $legal_persona_wechat, //法人微信
			'legal_persona_name' => $legal_persona_name, //法人姓名
			'component_phone' => $component_phone //第三方联系电话
		);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode']);
				return false;
			}
		}
		if (isset($json['appid']) && $category_first>0 && $category_second>0) $this->miniprogramSetCategory($category_first, $category_second, $json['appid'], $passway);
		return true;
	}
	
	//小程序认证名称检测
	public function miniprogramCheckName($nick_name) {
		$access_token = $this->component_access_token();
		if (!strlen($access_token)) return '缺少component_access_token';
		$url = "https://api.weixin.qq.com/cgi-bin/wxverify/checkwxverifynickname?access_token={$access_token}";
		$data = array('nick_name'=>$nick_name);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			switch (intval($json['errcode'])) {
				case 53010: return '名称格式不合法';break;
				case 53011: return '名称检测命中频率限制';break;
				case 53012: return '禁止使用该名称';break;
				case 53013: return '公众号：名称与已有公众号名称重复;小程序：该名称与已有小程序名称重复';break;
				case 53014: return '公众号：公众号已有{名称 A+}时，需与该帐号相同主体才可申请{名称 A};小程序：小程序已有{名称 A+}时，需与该帐号相同主体才可申请{名称 A}';break;
				case 53015: return '公众号：该名称与已有小程序名称重复，需与该小程序帐号相同主体才可申请;小程序：该名称与已有公众号名称重复，需与该公众号帐号相同主体才可申请';break;
				case 53016: return '公众号：该名称与已有多个小程序名称重复，暂不支持申请;小程序：该名称与已有多个公众号名称重复，暂不支持申请';break;
				case 53017: return '公众号：小程序已有{名称 A+}时，需与该帐号相同主体才可申请{名称 A};小程序：公众号已有{名称 A+}时，需与该帐号相同主体才可申请{名称 A}';break;
				case 53018: return '名称命中微信号';break;
				case 53019: return '名称在保护期内';break;
			}
		}
		if (isset($json['hit_condition']) && boolval($json['hit_condition'])) {
			return $json['wording'];
		}
		return '';
	}
	
	//获取小程序基本信息, https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/Mini_Program_Information_Settings.html
	public function miniprogramBasicinfo($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/account/getaccountbasicinfo?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (intval($json['errcode'])==41033) {
				//exit('该小程序不是由第三方平台api创建，因此无法调用该接口');
				return $this->authorizer_userinfo($authorizer_appid, $passway);
			}
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return $json;
	}
	
	//小程序修改名称, principal_type主体类型(0个人,1企业), id_card身份证mediaid(个人号必填), license营业执照mediaid(组织号必填)
	//当名称没有命中关键词，则直接设置成功；当名称命中关键词，需提交证明材料，并需要审核。审核结果会向授权事件接收 URL 进行事件推送
	public function miniprogramSetName($name, $principal_type, $file, $authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$media_id = $this->setMedia($file, $authorizer_appid, '', $passway);
		$url = "https://api.weixin.qq.com/wxa/setnickname?access_token={$access_token}";
		$data = array('nick_name'=>$name);
		switch ($principal_type) {
			case 0:$data['id_card'] = $media_id;break;
			case 1:$data['license'] = $media_id;break;
		}
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			$msg = '';
			switch (intval($json['errcode'])) {
				case 40097:$msg = '参数错误';break;
				case 91001:$msg = '不是公众号快速创建的小程序';break;
				case 91002:$msg = '小程序发布后不可改名';break;
				case 91003:$msg = '改名状态不合法';break;
				case 91004:$msg = '昵称不合法';break;
				case 91005:$msg = '昵称 15 天主体保护';break;
				case 91006:$msg = '昵称命中微信号';break;
				case 91007:$msg = '昵称已被占用';break;
				case 91008:$msg = '昵称命中 7 天侵权保护期';break;
				case 91009:$msg = '需要提交材料';break;
				case 91010:$msg = '其他错误';break;
				case 91011:$msg = '查不到昵称修改审核单信息';break;
				case 91012:$msg = '其他错误';break;
				case 91013:$msg = '占用名字过多';break;
				case 91014:$msg = '+号规则 同一类型关联名主体不一致';break;
				case 91015:$msg = '原始名不同类型主体不一致';break;
				case 91016:$msg = '名称占用者 ≥2';break;
				case 91017:$msg = '+号规则 不同类型关联名主体不一致';break;
			}
			if (!$passway) {
				exit(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		if (isset($json['audit_id'])) return array('wording'=>$json['wording'], 'audit_id'=>$json['audit_id']); //audit_id查询状态用
		return true;
	}
	
	//小程序修改头像, 每个月只可修改7次
	public function miniprogramSetAvatar($file, $authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$media_id = $this->setMedia($file, $authorizer_appid, '', $passway);
		$url = "https://api.weixin.qq.com/cgi-bin/account/modifyheadimage?access_token={$access_token}";
		$data = array('head_img_media_id'=>$media_id, 'x1'=>0, 'y1'=>0, 'x2'=>1, 'y2'=>1);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			$msg = '';
			switch (intval($json['errcode'])) {
				case 40097:$msg = '参数错误';break;
				case 41006:$msg = 'media_id 不能为空';break;
				case 40007:$msg = '非法的 media_id';break;
				case 46001:$msg = 'media_id 不存在';break;
				case 40009:$msg = '图片尺寸太大';break;
				case 53202:$msg = '本月头像修改次数已用完';break;
			}
			if (!$passway) {
				exit(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return true;
	}
	
	//小程序修改功能介绍, 每个月只可修改5次
	public function miniprogramSetMemo($content, $authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/account/modifysignature?access_token={$access_token}";
		$data = array('signature'=>$content);
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			$msg = '';
			switch (intval($json['errcode'])) {
				case 40097:$msg = '参数错误';break;
				case 53200:$msg = '本月功能介绍修改次数已用完';break;
				case 53201:$msg = '功能介绍内容命中黑名单关键字';break;
			}
			if (!$passway) {
				exit(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return true;
	}
	
	//获取小程序允许设置的所有类目
	public function miniprogramGetAllCategories($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/wxopen/getallcategories?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return $json['categories_list']['categories']; //企业275文娱{276资讯,279视频}, 个人287工具{302图片}, 第三方快速创建1快递业与邮政{2快递、物流}
	}
	
	//获取小程序类目
	public function miniprogramGetCategory($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/wxopen/getcategory?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return $json;
	}
	
	//设置小程序类目
	public function miniprogramSetCategory($first_id, $second_id, $authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/wxopen/addcategory?access_token={$access_token}";
		$data = array('categories'=>array(
			array('first'=>$first_id, 'second'=>$second_id)
		));
		$json = $this->requestData('post', $url, json_encode($data, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			$msg = '';
			switch (intval($json['errcode'])) {
				case 53300:$msg = '超出每月次数限制';break;
				case 53301:$msg = '超出可配置类目总数限制';break;
				case 53302:$msg = '当前账号主体类型不允许设置此种类目';break;
				case 53303:$msg = '提交的参数不合法';break;
				case 53304:$msg = '与已有类目重复';break;
				case 53305:$msg = '包含未通过 ICP 校验的类目';break;
				case 53306:$msg = '修改类目只允许修改类目资质，不允许修改类目 ID';break;
				case 53307:$msg = '只有审核失败的类目允许修改';break;
				case 53308:$msg = '审核中的类目不允许删除';break;
			}
			if (!$passway) {
				exit(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return true;
	}
	
	//获取订阅消息列表
	public function miniprogramGetTemplateMessage($authorizer_appid, $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/wxaapi/newtmpl/gettemplate?access_token={$access_token}";
		$json = $this->requestData('get', $url, array(), true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			if (!$passway) {
				exit(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$json['errmsg'].' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		/*列表格式：[{
			"priTmplId": "9Aw5ZV1j9xdWTFEkqCpZ7mIBbSC34khK55OtzUPl0rU",
			"title": "报名结果通知",
			"content": "会议时间:{{date2.DATA}}\n会议地点:{{thing1.DATA}}\n",
			"example": "会议时间:2016年8月8日\n会议地点:TIT会议室\n",
			"type": 2 //模板类型，2 代表一次性订阅，3 代表长期订阅
		}]*/
		return $json['data'];
	}
	
	//发送订阅消息
	public function miniprogramSendTemplateMessage($authorizer_appid, $touser, $template_id, $data, $page='', $passway=false) {
		$json = $this->authorizer_access_token('', $authorizer_appid, $passway);
		if (!$json) return false;
		$access_token = $json['authorizer_access_token'];
		if (!strlen($access_token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$access_token}";
		$dat = array();
		$dat['touser'] = $touser; //openid
		$dat['template_id'] = $template_id; //模板ID
		$dat['data'] = $data;
		/*data格式: {
			"keyword1":{"value":"修水龙头", "color":"#173177"},
			"keyword2":{"value":"水工", "color":"#173177"}
		}*/
		if (strlen($page)) $dat['page'] = $page; //模板跳转小程序链接
		$json = $this->requestData('post', $url, json_encode($dat, JSON_UNESCAPED_UNICODE), true, true);
		if (isset($json['errcode']) && intval($json['errcode'])!=0) {
			$msg = '';
			switch (intval($json['errcode'])) {
				case 40003:$msg = 'touser字段openid为空或者不正确';break;
				case 40037:$msg = '订阅模板id为空不正确';break;
				case 43101:$msg = '用户拒绝接受消息，如果用户之前曾经订阅过，则表示用户取消了订阅关系';break;
				case 47003:$msg = '模板参数不准确，可能为空或者不满足规则，errmsg会提示具体是哪个字段出错 ('.$json['errmsg'].')';break;
				case 41030:$msg = 'page路径不正确，需要保证在现网版本小程序中存在，与app.json保持一致';break;
			}
			if (!$passway) {
				exit(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
			} else {
				$this->log(__FUNCTION__.': '.$msg.' errcode: '.$json['errcode'].' appid: '.$authorizer_appid);
				return false;
			}
		}
		return true;
	}
}
