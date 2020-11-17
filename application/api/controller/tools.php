<?php
class tools extends core {
	private $appId;
	private $version;
	private $versionNum;

	public function __construct() {
		parent::__construct();
		$this->appId = $this->request->get('appId');
		if (!strlen($this->appId) && isset($this->headers['Appid'])) $this->appId = $this->headers['Appid'];
		$this->version = $this->request->get('version');
		if (!strlen($this->appId)) error('缺失AppID');
		$this->versionNum = strlen($this->version) ? (is_numeric(str_replace('.', '', $this->version)) ? intval(str_replace('.', '', $this->version)) : $this->version) : 0;
	}
	
	public function index() {
		$list = [
			['name'=>'短视频去水印', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813423442923.jpg', 'url'=>'/pages/index/detail?title=短视频去水印'],
			['name'=>'皮皮侠、披披搞笑', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813423479318.jpg', 'url'=>'/pages/index/detail?title=皮皮侠、披披搞笑'],
			['name'=>'火珊、最又', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813423427597.jpg', 'url'=>'/pages/index/detail?title=火珊、最又'],
			['name'=>'头條去水印', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813423586647.jpg', 'url'=>'/pages/index/detail?title=头條去水印'],
			['name'=>'块手、希瓜', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813423564804.jpg', 'url'=>'/pages/index/detail?title=块手、希瓜'],
			['name'=>'网意视频去水印', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813423579939.jpg', 'url'=>'/pages/index/detail?title=网意视频去水印'],
			['name'=>'微士去水印', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813423524365.jpg', 'url'=>'/pages/index/detail?title=微士去水印'],
			['name'=>'美排等其他', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813423688166.jpg', 'url'=>'/pages/index/detail?title=美排等其他'],
			['name'=>'图片去水印', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813463911988.jpg', 'url'=>'/pages/index/pic?title=图片去水印'],
			['name'=>'批量保存微信群图片视频', 'pic'=>'https://img.shuyi88.cn/uploads/pic/2020/07/08/20070813463933216.jpg', 'url'=>'/pages/index/batch?title=批量保存']
		];
		//$list = add_domain_deep($list, ['pic']);
		$banner = ['enable'=>0, 'type'=>'', 'image'=>'', 'url'=>'', 'adunit'=>'', 'message_title'=>'', 'message_path'=>'', 'message_image'=>''];
		$wxpositionad = ['enable'=>0, 'adunit'=>''];
		success(compact('banner', 'wxpositionad', 'list'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	public function detail() {
		$banner = ['enable'=>0, 'type'=>'', 'image'=>'', 'url'=>'', 'adunit'=>'', 'message_title'=>'', 'message_path'=>'', 'message_image'=>''];
		$wxpositionad = ['enable'=>0, 'adunit'=>''];
		$rewarded = ['enable'=>0, 'adunit'=>''];
		$pic = 'http://img.shuyi88.cn/tools_help.jpg';
		success(compact('banner', 'wxpositionad', 'rewarded', 'pic'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	//视频去水印, 抖音,皮皮虾,微视,最右,火山,今日头条,快手,网易视频,西瓜,抖音1,皮皮搞笑,美拍
	public function video() {
		$url = $this->request->get('url');
		if (!strlen($url)) error('缺少参数');
		$clientId = '1';
		$clientSecretKey = '32C3D946380DCD222C5B55243B2F00FC88A3123D2C17F25816';
		$res = requestData('get', "https://jx.muzzz.cn/api/dsp/{$clientSecretKey}/{$clientId}/?url={$url}", NULL, true);
		switch (intval($res['status'])) {
			case 101:
				$res = $res['data'];
				$url = download_file('tools', $res['url'], false, 'mp4');
				$img = download_file('tools', $res['img'], false, 'jpg');
				$res['server_url'] = $this->domain.$url;
				$res['server_img'] = $this->domain.$img;
				requestAsync('post', "{$this->domain}/api/v2/tools/deleteFile", ['url'=>$url, 'img'=>$img], ["Appid: {$this->appId}"]);
				break;
			case 103:error('请输入正确的链接');break;
			case 104:error('接口不存在/暂停使用');break;
			case 107:error('数据结构异常');break;
			case 108:error('会员接口不存在');break;
			case 109:error('接口被系统管理员关闭');break;
			case 110:error('次数不足');break;
			case 113:error('解析失败');break;
			case 115:error('会员等级不足');break;
			default:error($res);break;
		}
		success($res);
	}
	
	//批量保存微信群图片和视频
	public function batch() {
		$banner = ['enable'=>0, 'type'=>'', 'image'=>'', 'url'=>'', 'adunit'=>'', 'message_title'=>'', 'message_path'=>'', 'message_image'=>''];
		$wxpositionad = ['enable'=>0, 'adunit'=>''];
		$rewarded = ['enable'=>0, 'adunit'=>''];
		success(compact('banner', 'wxpositionad', 'rewarded'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	public function deleteFile() {
		ini_set('ignore_user_abort', true);
		ignore_user_abort(true); //设置与客户机断开是否会终止执行
		set_time_limit(0);
		sleep(5*60);
		$url = $this->request->post('url');
		$img = $this->request->post('img');
		if (strlen($url) && file_exists(PUBLIC_PATH.$url)) unlink(PUBLIC_PATH.$url);
		if (strlen($img) && file_exists(PUBLIC_PATH.$img)) unlink(PUBLIC_PATH.$img);
		exit;
	}
}
