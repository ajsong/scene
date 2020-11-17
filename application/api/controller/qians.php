<?php
class qians extends core {
	private $indexAddMyEnable; //首页-添加到我的小程序开关
	
	private $detailTopAdEnable; //详情页-顶部广告开关
	private $detailTopAdType; //详情页-顶部广告类型,1自定义,2微信广告,3微信视频广告,4客服消息
	private $detailTopAdImage; //详情页-顶部广告图片
	private $detailTopAdUrl; //详情页-顶部广告链接
	private $detailTopAdUnitId; //详情页-顶部广告微信UnitId
	
	private $detailBottomAdEnable; //详情页-底部广告开关
	private $detailBottomAdType; //详情页-底部广告类型,1自定义,2微信广告,3微信视频广告,4客服消息
	private $detailBottomAdImage; //详情页-底部广告图片
	private $detailBottomAdUrl; //详情页-底部广告链接
	private $detailBottomAdUnitId; //详情页-底部广告微信UnitId
	
	private $returnEnable; //多重返回开关
	private $returnEverybody; //查看过新的都进行多重返回
	private $returnUrls; //多重返回网址组
	
	private $detailAddMyEnable; //详情页-添加到我的小程序开关
	
	private $wxAdEnable; //微信广告总开关
	private $detailListWxAdEnable; //详情页-列表微信广告开关
	private $detailListWxAdUnitId; //详情页-列表微信广告UnitId
	private $detailListWxVideoAdUnitId; //详情页-列表微信视频广告UnitId
	private $detailPositionWxAdEnable; //详情页-插屏微信广告开关
	private $detailPositionWxAdUnitId; //详情页-插屏微信广告UnitId
	
	private $customMessageTitle; //客服消息卡片标题
	private $customMessagePath; //客服消息卡片跳转的小程序路径
	private $customMessageImg; //客服消息卡片图片
	
	private $appId;
	private $version;
	private $versionNum;
	private $miniprogram;

	public function __construct() {
		parent::__construct();
		$this->appId = $this->request->get('appId');
		if (!strlen($this->appId) && isset($this->headers['Appid'])) $this->appId = $this->headers['Appid'];
		$this->version = $this->request->get('version');
		$this->versionNum = strlen($this->version) ? (is_numeric(str_replace('.', '', $this->version)) ? intval(str_replace('.', '', $this->version)) : $this->version) : 0;
		$configs = $this->configs;
		if (!strlen($this->appId)) error('缺失AppID');
		$this->miniprogram = SQL::share('miniprogram')->where("appid='{$this->appId}'")->row();
		if (!$this->miniprogram) error('小程序数据错误');
		
		if (!SQL::share('miniprogram_config')->where("miniprogram_id='{$this->miniprogram->id}'")->exist()) {
			$rs = SQL::share('config')->where("name LIKE 'G_ARTICLE%'")->find('id, content');
			if ($rs) {
				$data = array();
				foreach ($rs as $row) {
					$data[] = array('miniprogram_id'=>$this->miniprogram->id, 'config_id'=>$row->id, 'content'=>$row->content);
				}
				SQL::share('miniprogram_config')->insert($data);
			}
		}
		$rs = SQL::share('config c')->left('miniprogram_config mc', 'mc.config_id=c.id')->where("c.name LIKE 'G_ARTICLE%' AND mc.miniprogram_id='{$this->miniprogram->id}'")->find('c.name, mc.content');
		if ($rs) {
			$configs = array();
			foreach ($rs as $row) {
				$configs[$row->name] = $row->content;
			}
		}
		
		$this->indexAddMyEnable = $this->request->act('G_ARTICLE_INDEX_ADDMY_ENABLE', 0, '', $configs);
		$this->detailTopAdEnable = $this->request->act('G_ARTICLE_DETAIL_TOP_AD_ENABLE', 0, '', $configs);
		$this->detailTopAdType = $this->request->act('G_ARTICLE_DETAIL_TOP_AD_TYPE', 0, '', $configs);
		$this->detailTopAdImage = $this->request->act('G_ARTICLE_DETAIL_TOP_AD_IMAGE', '', '', $configs);
		$this->detailTopAdUrl = $this->request->act('G_ARTICLE_DETAIL_TOP_AD_URL', '', '', $configs);
		$this->detailTopAdUnitId = $this->request->act('G_ARTICLE_DETAIL_TOP_AD_UNITID', '', '', $configs);
		$this->detailBottomAdEnable = $this->request->act('G_ARTICLE_DETAIL_BOTTOM_AD_ENABLE', 0, '', $configs);
		$this->detailBottomAdType = $this->request->act('G_ARTICLE_DETAIL_BOTTOM_AD_TYPE', 0, '', $configs);
		$this->detailBottomAdImage = $this->request->act('G_ARTICLE_DETAIL_BOTTOM_AD_IMAGE', '', '', $configs);
		$this->detailBottomAdUrl = $this->request->act('G_ARTICLE_DETAIL_BOTTOM_AD_URL', '', '', $configs);
		$this->detailBottomAdUnitId = $this->request->act('G_ARTICLE_DETAIL_BOTTOM_AD_UNITID', '', '', $configs);
		$this->returnEnable = $this->request->act('G_ARTICLE_RETURN_ENABLE', 0, '', $configs);
		$this->returnEverybody = $this->request->act('G_ARTICLE_RETURN_EVERYBODY', 0, '', $configs);
		$this->returnUrls = $this->request->act('G_ARTICLE_RETURN_URLS', '', '', $configs);
		$this->detailAddMyEnable = $this->request->act('G_ARTICLE_DETAIL_ADDMY_ENABLE', 0, '', $configs);
		$this->wxAdEnable = $this->request->act('G_ARTICLE_WX_AD_ENABLE', 0, '', $configs);
		$this->detailListWxAdEnable = $this->request->act('G_ARTICLE_DETAIL_LIST_WX_AD_ENABLE', 0, '', $configs);
		$this->detailListWxAdUnitId = $this->request->act('G_ARTICLE_DETAIL_LIST_WX_AD_UNITID', '', '', $configs);
		$this->detailListWxVideoAdUnitId = $this->request->act('G_ARTICLE_DETAIL_LIST_WX_VIDEO_AD_UNITID', '', '', $configs);
		$this->detailPositionWxAdEnable = $this->request->act('G_ARTICLE_DETAIL_POSITION_WX_AD_ENABLE', 0, '', $configs);
		$this->detailPositionWxAdUnitId = $this->request->act('G_ARTICLE_DETAIL_POSITION_WX_AD_UNITID', '', '', $configs);
		$this->customMessageTitle = $this->request->act('G_ARTICLE_CUSTOM_MESSAGE_TITLE', '', '', $configs);
		$this->customMessagePath = $this->request->act('G_ARTICLE_CUSTOM_MESSAGE_PATH', '', '', $configs);
		$this->customMessageImg = $this->request->act('G_ARTICLE_CUSTOM_MESSAGE_IMG', '', '', $configs);
		
		if ($this->miniprogram->review==1) {
			$this->indexAddMyEnable = 0;
			$this->detailTopAdEnable = ($this->detailTopAdType==2 || $this->detailTopAdType==3) ? $this->detailTopAdEnable : 0;
			$this->detailBottomAdEnable = ($this->detailBottomAdType==2 || $this->detailBottomAdType==3) ? $this->detailBottomAdEnable : 0;
			$this->returnEnable = 0;
			$this->detailAddMyEnable = 0;
			//$this->wxAdEnable = 0;
		}
	}
	
	public function index() {
		$res = [
			'bg'=>'/images/qians/bg.jpg',
			'font'=>'/images/qians/font.png',
			'gylay'=>'/images/qians/gy-lay.png',
			'huan'=>'/images/qians/huan.png',
			'qian'=>'/images/qians/qian.png',
			'tips'=>'/images/qians/tips.png',
			'music'=>'/images/qians/shake.mp3',
			'share_title'=>'我找到了一个抽签的非常灵',
			'share_image'=>'/images/qians/share_image.png'
		];
		$res = add_domain_deep($res, ['bg', 'font', 'gylay', 'huan', 'qian', 'tips', 'music', 'share_image']);
		$addmy = $this->indexAddMyEnable;
		$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->detailPositionWxAdEnable:0, 'adunit'=>$this->detailPositionWxAdUnitId];
		success(compact('res', 'addmy', 'wxpositionad'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	public function getresult() {
		$qianNum = $this->request->session('qianNum', 0);
		if ($qianNum>0) {
			$num = $qianNum;
		} else {
			$arr = [33, 36, 43, 47, 48, 49, 51, 53, 54, 57, 63, 64, 67, 68, 69, 73];
			$num = $arr[mt_rand(0, count($arr)-1)];
			$_SESSION['qianNum'] = $num;
		}
		success($num);
	}
	
	public function detail() {
		$q = $this->request->get('q', 0);
		if ($q<=0 || strlen(strval($q))!=2) error('数据错误');
		$arr = str_split(strval($q));
		$name = "{$arr[0]}两{$arr[1]}钱🤗";
		$qian = $q > 47 ? '上上签' : '上签';
		
		$res = [
			'time'=>date('Y-m-d'),
			'layer'=>'/images/qians/share-layer.png',
			'jt'=>'/images/qians/jt.gif',
			'result'=>"/images/qians/result/{$q}.jpg",
			'share_title'=>"我命运竟然是：{$name}",
			'share_image'=>'/images/qians/share_image.png'
		];
		$res = add_domain_deep($res, ['layer', 'jt', 'result', 'share_image']);
		
		$returns = $this->_returnUrls();
		
		$banner = ['enable'=>$this->detailTopAdEnable, 'type'=>$this->detailTopAdType, 'image'=>$this->detailTopAdImage, 'url'=>$this->detailTopAdUrl, 'adunit'=>$this->detailTopAdUnitId, 'message_title'=>$this->customMessageTitle, 'message_path'=>$this->customMessagePath, 'message_image'=>$this->customMessageImg];
		$footer = ['enable'=>$this->detailBottomAdEnable, 'type'=>$this->detailBottomAdType, 'image'=>$this->detailBottomAdImage, 'url'=>$this->detailBottomAdUrl, 'adunit'=>$this->detailBottomAdUnitId, 'message_title'=>$this->customMessageTitle, 'message_path'=>$this->customMessagePath, 'message_image'=>$this->customMessageImg];
		$banner = add_domain_deep($banner, ['image', 'message_image']);
		$footer = add_domain_deep($footer, ['image', 'message_image']);
		$addmy = $this->detailAddMyEnable;
		success(compact('q', 'qian', 'name', 'res', 'returns', 'banner', 'footer', 'addmy'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	//多重返回
	private function _returnUrls() {
		$urls = explode("\r\n", $this->returnUrls);
		return [
			'enable' => $this->returnEnable,
			'everybody' => $this->returnEverybody,
			'urls' => $urls
		];
	}
}
