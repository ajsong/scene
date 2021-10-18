<?php
namespace app\index\controller;

use app\Request;
use think\facade\Db;
use app\index\controller\Core;

class Index extends Core
{
	
	//首页接口
	public function index() {
		if (!$this->_check_login()) return $this->about();
		$click = 0;
		$scene = Db::name('member_scene')->where('member_id', $this->member_id)->order('id', 'DESC')->field("*, '' as url")->select();
		$count = count($scene);
		if ($scene) {
			foreach ($scene as $g) {
				$g['url'] = "{$this->domain}/v/".$g['code'];
				$click += $g['click'];
			}
		}
		return success(compact('click', 'count', 'scene'));
	}
	
	public function about() {
		return success('ok', 'about.html');
	}
	
	//修改密码
	public function password() {
		if (IS_POST) {
			$origin_password = $this->request->post('origin_password', '');
			$password = $this->request->post('password', '');
			if (!strlen($origin_password)) return error('原密码不能为空');
			if (!strlen($password)) return error('新密码不能为空');
			$member = Db::name('member')->where('id', $this->member_id)->field('name, password, salt')->find();
			if ($member) {
				if ($member['name']=='test') return error('测试账号不可修改密码');
				if ($member['password']!=crypt_password($origin_password, $member['salt'])) return error('原密码错误');
				$salt = generate_salt();
				$crypt_password = crypt_password($password, $salt);
				Db::name('member')->where('id', $this->member_id)->update(array('password'=>$crypt_password, 'origin_password'=>$password, 'salt'=>$salt));
			}
		}
		return success('ok');
	}
	
	//更改头像
	public function avatar() {
		$avatar = $this->request->file('filename', 'avatar');
		if (!strlen($avatar)) return error('上传接口出错');
		Db::name('member')->where('id', $this->member_id)->update(array('avatar'=>$avatar));
		return success($avatar);
	}
	
	//登录
	public function login() {
		if (session('?member')) location('/');
		return success($this->configs);
	}
	
	//退出
	public function logout() {
		//session_unset();
		if (session('?member')) session('member', null);
		if ($this->member_id>0) {
			$this->member_id = 0;
			$this->cookieAccount('member_token', $this->member_name, NULL);
		}
		$referer = $this->referer;
		if (!strlen($referer)) $referer = '/index/login';
		location($referer);
	}
	
	//跳转随机网址
	public function url() {
		$url = array(
			'http://www.baidu.com',
			'http://www.apple.com.cn',
			'http://www.163.com',
			'http://www.aliyun.com',
			'http://www.alipay.com',
			'http://www.taobao.com',
			'http://mp.weixin.qq.com',
			'https://gitee.com',
			'http://www.sina.com.cn',
			'http://www.qq.com',
			'http://www.360.cn',
			'http://www.cnblogs.com',
			'http://www.csdn.net'
		);
		$domain = $url[mt_rand(0, count($url)-1)];
		location($domain);
	}
	
	//微信用户标签
	public function wxtag() {
		$type = $this->request->get('type', '');
		if (strlen($type)) {
			$appId = $this->request->post('app', '');
			if (!strlen($appId)) return error('请选择公众号');
			$wechat = Db::name('wechat')->where('appid', $appId)->find();
			if (!$wechat) return error('该公众号不存在');
			if ($wechat['appsecret'] && strlen($wechat['appsecret'])) {
				$jssdk = new wechatCallbackAPI($appId, $wechat['appsecret']);
				$access_token = $jssdk->getAccessToken("manual/access_token.{$appId}.json");
			} else {
				$wxapi = new wechatCallbackAPI();
				$json = $wxapi->authorizer_access_token('', $appId);
				$access_token = $json['authorizer_access_token'];
			}
			if (!strlen($access_token)) return error('获取 access_token 失败');
			$res = NULL;
			switch ($type) {
				case 'tags':
					$res = requestData('get', "https://api.weixin.qq.com/cgi-bin/tags/get?access_token={$access_token}", NULL, true);
					if (isset($res['tags'])) $res = ['tags'=>$res['tags'], 'default'=>$wechat['tag']];
					break;
				case 'create':
					$name = $this->request->post('name', '');
					if (!strlen($name)) return error('请填写要添加的标签名称');
					$data = ['tag'=>['name'=>$name]];
					$res = requestData('post', "https://api.weixin.qq.com/cgi-bin/tags/create?access_token={$access_token}", $data, true, true);
					break;
				case 'update':
					$id = $this->request->post('id', 0);
					$name = $this->request->post('name', '');
					if ($id<=0) return error('请选择要更新的标签');
					if (!strlen($name)) error('请填写要更新的标签名称');
					$data = ['tag'=>['id'=>$id, 'name'=>$name]];
					$res = requestData('post', "https://api.weixin.qq.com/cgi-bin/tags/update?access_token={$access_token}", $data, true, true);
					break;
				case 'delete':
					$id = $this->request->post('id', 0);
					if ($id<=0) return error('请选择要删除的标签');
					$data = ['tag'=>['id'=>$id]];
					$res = requestData('post', "https://api.weixin.qq.com/cgi-bin/tags/delete?access_token={$access_token}", $data, true, true);
					break;
				case 'default':
					$id = $this->request->post('id', 0);
					if ($id<=0) return error('请选择要设为默认的标签');
					$res = Db::name('wechat')->where('appid', $appId)->update(['tag'=>$id]);
					break;
			}
			if (!$res) return error('数据错误');
			if (is_array($res) && isset($res['errcode']) && intval($res['errcode'])!=0) {
				if (strpos($res['errmsg'], 'api unauthorized')!==false) return error('该公众号没有权限');
				return error($res['errmsg']);
			}
			return success($res);
		}
		$rs = Db::name('wechat')->order('id')->find();
		return success($rs);
	}
	
	//增加公众号
	public function addWechat() {
		$name = $this->request->post('name', '');
		$appid = $this->request->post('appid', '');
		$appsecret = $this->request->post('appsecret', '');
		$token = $this->request->post('token', '');
		if (!strlen($name) || !strlen($appid) || !strlen($appsecret) || !strlen($token)) return error('所有项都必须填写');
		if (Db::name('wechat')->where('appid', $appid)->count()>0) return error('该公众号已存在');
		Db::name('wechat')->insert(['name'=>$name, 'appid'=>$appid, 'appsecret'=>$appsecret, 'token'=>$token]);
		return success('ok');
	}
	
	//删除公众号
	public function deleteWechat() {
		$appid = $this->request->post('appid', '');
		if (!strlen($appid)) return error('请选择需要删除的公众号');
		Db::name('wechat')->where('appid', $appid)->delete();
		return success('ok');
	}
}
