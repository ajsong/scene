<?php
#//祝福文章
class blessing extends core {
	private $indexListAdEnable; //首页-列表广告开关
	private $indexListAdImage; //首页-列表广告图片
	private $indexListAdUrl; //首页-列表广告链接
	
	private $indexAddMyEnable; //首页-添加到我的小程序开关
	private $indexAddMyBgColor; //首页-添加到我的小程序背景色
	
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
	
	private $detailListAdEnable; //详情页-列表广告开关
	private $detailListAdImage; //详情页-列表广告图片
	private $detailListAdUrl; //详情页-列表广告链接
	
	private $returnEnable; //多重返回开关
	private $returnEverybody; //查看过新的都进行多重返回
	private $returnUrls; //多重返回网址组
	
	private $detailTipsEnable; //详情页-提示开关
	private $detailTipsText; //详情页-提示文字
	private $detailTipsUrl; //详情页-提示分享跳转链接
	private $detailTipsPosition; //详情页-提示弹出框开关
	private $detailTipsRedirect; //详情页-分享按钮直接跳转网址
	
	private $detailAddMyEnable; //详情页-添加到我的小程序开关
	private $detailFeedbackEnable; //详情页-投诉举报开关
	
	private $wxAdEnable; //微信广告总开关
	private $indexListWxAdEnable; //首页-列表微信广告开关
	private $indexListWxAdUnitId; //首页-列表微信广告UnitId
	private $indexListWxVideoAdUnitId; //首页-列表微信视频广告UnitId
	private $detailListWxAdEnable; //详情页-列表微信广告开关
	private $detailListWxAdUnitId; //详情页-列表微信广告UnitId
	private $detailListWxVideoAdUnitId; //详情页-列表微信视频广告UnitId
	private $detailPositionWxAdEnable; //详情页-插屏微信广告开关
	private $detailPositionWxAdUnitId; //详情页-插屏微信广告UnitId
	private $indexPositionWxAdEnable; //首页-插屏微信广告开关
	private $indexPositionWxAdUnitId; //首页-插屏微信广告UnitId
	
	private $customMessageTitle; //客服消息卡片标题
	private $customMessagePath; //客服消息卡片跳转的小程序路径
	private $customMessageImg; //客服消息卡片图片
	
	private $nongli;
	
	private $appId;
	private $version;
	private $versionNum;
	private $miniprogram;

	public function __construct() {
		parent::__construct();
		if ($this->act != 'pickBlessing') {
			$this->appId = $this->request->get('appId');
			if (!strlen($this->appId) && isset($this->headers['Appid'])) $this->appId = $this->headers['Appid'];
			$this->version = $this->request->get('version');
			$this->versionNum = strlen($this->version) ? (is_numeric(str_replace('.', '', $this->version)) ? intval(str_replace('.', '', $this->version)) : $this->version) : 0;
			$configs = $this->configs;
			if (!strlen($this->appId)) error('缺失AppID');
			$this->miniprogram = SQL::share('miniprogram')->where("appid='{$this->appId}'")->row();
			if (!$this->miniprogram) error('小程序数据错误');
			
			if (!SQL::share('miniprogram_config')->where("miniprogram_id='{$this->miniprogram->id}'")->exist()) {
				$rs = SQL::share('config')->where("name LIKE 'G_BLESSING%'")->find('id, content');
				if ($rs) {
					$data = array();
					foreach ($rs as $row) {
						$data[] = array('miniprogram_id'=>$this->miniprogram->id, 'config_id'=>$row->id, 'content'=>$row->content);
					}
					SQL::share('miniprogram_config')->insert($data);
				}
			}
			$rs = SQL::share('config c')->left('miniprogram_config mc', 'mc.config_id=c.id')->where("c.name LIKE 'G_BLESSING%' AND mc.miniprogram_id='{$this->miniprogram->id}'")->find('c.name, mc.content');
			if ($rs) {
				$configs = array();
				foreach ($rs as $row) {
					$configs[$row->name] = $row->content;
				}
			}
			
			$this->indexListAdEnable = $this->request->act('G_BLESSING_INDEX_LIST_AD_ENABLE', 0, '', $configs);
			$this->indexListAdImage = $this->request->act('G_BLESSING_INDEX_LIST_AD_IMAGE', '', '', $configs);
			$this->indexListAdUrl = $this->request->act('G_BLESSING_INDEX_LIST_AD_URL', '', '', $configs);
			$this->indexAddMyEnable = $this->request->act('G_BLESSING_INDEX_ADDMY_ENABLE', 0, '', $configs);
			$this->indexAddMyBgColor = $this->request->act('G_BLESSING_INDEX_ADDMY_BGCOLOR', '', '', $configs);
			$this->detailTopAdEnable = $this->request->act('G_BLESSING_DETAIL_TOP_AD_ENABLE', 0, '', $configs);
			$this->detailTopAdType = $this->request->act('G_BLESSING_DETAIL_TOP_AD_TYPE', 0, '', $configs);
			$this->detailTopAdImage = $this->request->act('G_BLESSING_DETAIL_TOP_AD_IMAGE', '', '', $configs);
			$this->detailTopAdUrl = $this->request->act('G_BLESSING_DETAIL_TOP_AD_URL', '', '', $configs);
			$this->detailTopAdUnitId = $this->request->act('G_BLESSING_DETAIL_TOP_AD_UNITID', '', '', $configs);
			$this->detailBottomAdEnable = $this->request->act('G_BLESSING_DETAIL_BOTTOM_AD_ENABLE', 0, '', $configs);
			$this->detailBottomAdType = $this->request->act('G_BLESSING_DETAIL_BOTTOM_AD_TYPE', 0, '', $configs);
			$this->detailBottomAdImage = $this->request->act('G_BLESSING_DETAIL_BOTTOM_AD_IMAGE', '', '', $configs);
			$this->detailBottomAdUrl = $this->request->act('G_BLESSING_DETAIL_BOTTOM_AD_URL', '', '', $configs);
			$this->detailBottomAdUnitId = $this->request->act('G_BLESSING_DETAIL_BOTTOM_AD_UNITID', '', '', $configs);
			$this->detailListAdEnable = $this->request->act('G_ARTICLE_DETAIL_LIST_AD_ENABLE', 0, '', $configs);
			$this->detailListAdImage = $this->request->act('G_ARTICLE_DETAIL_LIST_AD_IMAGE', '', '', $configs);
			$this->detailListAdUrl = $this->request->act('G_ARTICLE_DETAIL_LIST_AD_URL', '', '', $configs);
			$this->returnEnable = $this->request->act('G_BLESSING_RETURN_ENABLE', 0, '', $configs);
			$this->returnEverybody = $this->request->act('G_BLESSING_RETURN_EVERYBODY', 0, '', $configs);
			$this->returnUrls = $this->request->act('G_BLESSING_RETURN_URLS', '', '', $configs);
			$this->detailTipsEnable = $this->request->act('G_BLESSING_DETAIL_TIPS_ENABLE', 0, '', $configs);
			$this->detailTipsText = $this->request->act('G_BLESSING_DETAIL_TIPS_TEXT', '', '', $configs);
			$this->detailTipsUrl = $this->request->act('G_BLESSING_DETAIL_TIPS_URL', '', '', $configs);
			$this->detailTipsPosition = $this->request->act('G_BLESSING_DETAIL_TIPS_POSITION_ENABLE', 0, '', $configs);
			$this->detailTipsRedirect = $this->request->act('G_BLESSING_DETAIL_TIPS_REDIRECT', '', '', $configs);
			$this->detailAddMyEnable = $this->request->act('G_BLESSING_DETAIL_ADDMY_ENABLE', 0, '', $configs);
			$this->detailFeedbackEnable = $this->request->act('G_BLESSING_DETAIL_FEEDBACK', 0, '', $configs);
			$this->wxAdEnable = $this->request->act('G_BLESSING_WX_AD_ENABLE', 0, '', $configs);
			$this->indexListWxAdEnable = $this->request->act('G_BLESSING_INDEX_LIST_WX_AD_ENABLE', 0, '', $configs);
			$this->indexListWxAdUnitId = $this->request->act('G_BLESSING_INDEX_LIST_WX_AD_UNITID', '', '', $configs);
			$this->indexListWxVideoAdUnitId = $this->request->act('G_BLESSING_INDEX_LIST_WX_VIDEO_AD_UNITID', '', '', $configs);
			$this->detailListWxAdEnable = $this->request->act('G_BLESSING_DETAIL_LIST_WX_AD_ENABLE', 0, '', $configs);
			$this->detailListWxAdUnitId = $this->request->act('G_BLESSING_DETAIL_LIST_WX_AD_UNITID', '', '', $configs);
			$this->detailListWxVideoAdUnitId = $this->request->act('G_BLESSING_DETAIL_LIST_WX_VIDEO_AD_UNITID', '', '', $configs);
			$this->detailPositionWxAdEnable = $this->request->act('G_BLESSING_DETAIL_POSITION_WX_AD_ENABLE', 0, '', $configs);
			$this->detailPositionWxAdUnitId = $this->request->act('G_BLESSING_DETAIL_POSITION_WX_AD_UNITID', '', '', $configs);
			$this->indexPositionWxAdEnable = $this->request->act('G_BLESSING_INDEX_POSITION_WX_AD_ENABLE', 0, '', $configs);
			$this->indexPositionWxAdUnitId = $this->request->act('G_BLESSING_INDEX_POSITION_WX_AD_UNITID', '', '', $configs);
			$this->customMessageTitle = $this->request->act('G_BLESSING_CUSTOM_MESSAGE_TITLE', '', '', $configs);
			$this->customMessagePath = $this->request->act('G_BLESSING_CUSTOM_MESSAGE_PATH', '', '', $configs);
			$this->customMessageImg = $this->request->act('G_BLESSING_CUSTOM_MESSAGE_IMG', '', '', $configs);
			
			if (!strlen($this->nongli)) {
				$lunar = m('lunar');
				$nongli=$lunar->convertSolarToLunar(date('Y'),date('m'),date('d'));
				$this->nongli = $nongli[1].$nongli[2];
				$_SESSION['nongli'] = $this->nongli;
			}
			
			if ($this->miniprogram->review==1) {
				$this->miniprogram->subscribe_id = '';
				$this->indexListAdEnable = 0;
				$this->indexAddMyEnable = 0;
				$this->detailTopAdEnable = ($this->detailTopAdType==2 || $this->detailTopAdType==3) ? $this->detailTopAdEnable : 0;
				$this->detailBottomAdEnable = ($this->detailBottomAdType==2 || $this->detailBottomAdType==3) ? $this->detailBottomAdEnable : 0;
				$this->detailListAdEnable = 0;
				$this->returnEnable = 0;
				$this->detailTipsEnable = 0;
				$this->detailTipsRedirect = '';
				$this->detailAddMyEnable = 0;
				$this->detailFeedbackEnable = 0;
				//$this->wxAdEnable = 0;
			}
		}
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

	#//文章列表|包括系统文章与会员发表的动态
	public function index() {
		/*
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$rs = SQL::share('article')->find('id');
		foreach ($rs as $g) {
			$clicks = SQL::share('article_attr')->where("article_id='{$g->id}'")->sum('clicks');
			$likes = SQL::share('article_attr')->where("article_id='{$g->id}'")->sum('likes');
			$comments = SQL::share('article_comment')->where("article_id='{$g->id}'")->count();
			SQL::share('article')->where($g->id)->update(['clicks'=>$clicks, 'likes'=>$likes, 'comments'=>$comments]);
		}
		$rs = SQL::share('blessing')->find('id');
		foreach ($rs as $g) {
			$clicks = SQL::share('blessing_attr')->where("blessing_id='{$g->id}'")->sum('clicks');
			$likes = SQL::share('blessing_attr')->where("blessing_id='{$g->id}'")->sum('likes');
			SQL::share('blessing')->where($g->id)->update(['clicks'=>$clicks, 'likes'=>$likes]);
		}
		echo 'OK';exit;
		*/
		$where = "b.status=1 AND ba.miniprogram_id='{$this->miniprogram->id}'";
		$offset = $this->request->get('offset', 0); #//
		$pagesize = $this->request->get('pagesize', 8); #//
		$sort = 'b.id DESC';
		
		$j = $offset*$pagesize;
		$rand = mt_rand($j, $j+$pagesize);
		$list = [];
		
		$rs = SQL::share('blessing b')->left('blessing_attr ba', 'b.id=ba.blessing_id')
			->where($where)->sort($sort)->limit($offset, $pagesize)->find('b.id, b.title, b.pic, b.top_avatar_pic, ba.likes, ba.clicks, b.add_time, 0 as type');
		if ($rs) {
			foreach ($rs as $g) {
				$g->clicks = $this->_changeNum($g->clicks);
				$g->likes = $this->_changeNum($g->likes);
				$g->add_time = date('Y-m-d', $g->add_time);
				$list[] = $g;
				$j++;
				
				if ($this->indexListAdEnable && $j==$rand) {
					$r = new stdClass();
					$r->type = 1;
					$r->pic = $this->indexListAdImage;
					$r->url = $this->indexListAdUrl;
					$list[] = $r;
				} else if ($this->wxAdEnable && $this->indexListWxAdEnable && $j==$rand) {
					if (strlen($this->indexListWxAdUnitId)) {
						$r = new stdClass();
						$r->type = 3;
						$r->adunit = $this->indexListWxAdUnitId;
						$list[] = $r;
					} else if (strlen($this->indexListWxVideoAdUnitId)) {
						$r = new stdClass();
						$r->type = 4;
						$r->adunit = $this->indexListWxVideoAdUnitId;
						$list[] = $r;
					}
				}
			}
		}
		$list = add_domain_deep($list, ['pic', 'top_avatar_pic']);
		
		$addmy = $this->indexAddMyEnable;
		$addmybgcolor = $this->indexAddMyBgColor;
		$res = $this->_returnUrls();
		$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->indexPositionWxAdEnable:0, 'adunit'=>$this->indexPositionWxAdUnitId];
		$material = array(
			'nongli'=>$this->nongli,
			'date'=>date('Y年m月d日'),
			'header'=>'/uploads/blessing/header.jpg',
			'header_bottom'=>'/uploads/blessing/header_bottom.png',
			'list_bg'=>'/uploads/blessing/list_bg.jpg',
			'avatar'=>'/uploads/blessing/avatar.png'
		);
		$material = add_domain_deep($material, ['header', 'header_bottom', 'list_bg', 'avatar']);
		success(compact('res', 'addmy', 'addmybgcolor', 'material', 'list', 'wxpositionad'), '成功', 0, ['appId'=>$this->appId]);
	}

	#//详情
	public function detail() {
		$id = $this->request->get('id');
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		if (!strlen($id)) error('缺少参数');
		$blessing = SQL::share('blessing b')->left('blessing_attr ba', 'b.id=ba.blessing_id')->where("b.id='{$id}' AND ba.miniprogram_id='{$this->miniprogram->id}'")->row('b.*, ba.clicks, ba.likes');
		if (!$blessing) error('祝福不存在');
		$content = stripslashes($blessing->content);
		$content = preg_replace('/(width|height):\s*\d+px;?/', '', $content);
		$content = preg_replace('/\s(width|height)="\d+"/', '', $content);
		/*$content = preg_replace_callback('/<img([^>]+)>/', function($matcher){
			if (preg_match('/style="([^"]*)"/', $matcher[1])) {
				$attr = preg_replace('/style="([^"]*)"/', 'style="$1;max-width:100%;height:auto;display:block;vertical-align:bottom;"', $matcher[1]);
			} else {
				$attr = ' style="max-width:100%;height:auto;display:block;vertical-align:bottom;"'.$matcher[1];
			}
			return "<img{$attr}>";
		}, $content);*/
		$content = add_domain_deep($content);
		SQL::share('blessing_attr')->where("blessing_id='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['clicks'=>['+1']]);
		SQL::share('blessing')->where($id)->update(['clicks'=>['+1'], 'today_clicks'=>['+1']]);
		$blessing->clicks = $blessing->clicks + 1;
		$blessing->content = $content;
		$blessing->clicks = $this->_changeNum($blessing->clicks);
		$blessing->likes = $this->_changeNum($blessing->likes);
		$blessing->add_time = date("Y年m月d日", $blessing->add_time);
		$time = intval(date('H'));
		if (6<=$time && $time<12) {
			$hello = '/uploads/blessing/hello/1_'.floor(mt_rand(1, 2)).'.gif';
		} else if (12<=$time && $time<16) {
			$hello = '/uploads/blessing/hello/2_'.floor(mt_rand(1, 2)).'.gif';
		} else if (16<=$time && $time<18) {
			$hello = '/uploads/blessing/hello/3_'.floor(mt_rand(1, 2)).'.gif';
		} else {
			$hello = '/uploads/blessing/hello/4_'.floor(mt_rand(1, 2)).'.gif';
		}
		$blessing->hello = $hello;
		
		$blessing = add_domain_deep($blessing, ['pic', 'share_pic', 'bg_pic', 'bg_music', 'top_avatar_pic', 'bottom_avatar_pic', 'position_pic1', 'position_pic2', 'hello']);
		
		$list = [];
		$where = "b.id!='{$id}' AND ba.miniprogram_id='{$this->miniprogram->id}' AND status=1";
		$j = $offset*$pagesize;
		$rand = mt_rand($j, $j+$pagesize);
		$rs = SQL::share('blessing b')->left('blessing_attr ba', 'b.id=ba.blessing_id')
			->where($where)->sort('RAND()')->limit($offset, $pagesize)->find('b.id, title, pic, top_avatar_pic, ba.likes, ba.clicks, add_time, 0 as type');
		if ($rs) {
			shuffle($rs);
			foreach ($rs as $g) {
				$g->clicks = $this->_changeNum($g->clicks);
				$g->likes = $this->_changeNum($g->likes);
				$g->add_time = date('Y-m-d', $g->add_time);
				$list[] = $g;
				$j++;
				
				if ($this->detailListAdEnable && $j==$rand) {
					$r = new stdClass();
					$r->type = 1;
					$r->img = $this->detailListAdImage;
					$r->url = $this->detailListAdUrl;
					$list[] = $r;
				} else if ($this->wxAdEnable && $this->detailListWxAdEnable && $j==$rand) {
					if (strlen($this->detailListWxAdUnitId)) {
						$r = new stdClass();
						$r->type = 3;
						$r->adunit = $this->detailListWxAdUnitId;
						$list[] = $r;
					} else if (strlen($this->detailListWxVideoAdUnitId)) {
						$r = new stdClass();
						$r->type = 4;
						$r->adunit = $this->detailListWxVideoAdUnitId;
						$list[] = $r;
					}
				}
			}
		}
		$list = add_domain_deep($list, ['pic', 'top_avatar_pic']);
		
		$detailTipsUrl = '';
		/*$ids = SQL::share('blessing b')->left('blessing_attr ba', 'b.id=ba.blessing_id')->where("b.status=1 AND b.id!='{$id}'")->sort('ba.clicks DESC')->pagesize(10)->returnArray()->find('b.id');
		if (count($ids)) {
			shuffle($ids);
			$detailTipsUrl = '/pages/index/detail?id='.$ids[0];
		}*/
		if (strlen($this->detailTipsUrl)) {
			$detailTipsUrl = $this->detailTipsUrl;
		}
		
		$material = array(
			'list_bg'=>'/uploads/blessing/list_bg.jpg'
		);
		$material = add_domain_deep($material, ['list_bg']);
		
		$banner = ['enable'=>$this->detailTopAdEnable, 'type'=>$this->detailTopAdType, 'image'=>$this->detailTopAdImage, 'url'=>$this->detailTopAdUrl, 'adunit'=>$this->detailTopAdUnitId, 'message_title'=>$this->customMessageTitle, 'message_path'=>$this->customMessagePath, 'message_image'=>$this->customMessageImg];
		$footer = ['enable'=>$this->detailBottomAdEnable, 'type'=>$this->detailBottomAdType, 'image'=>$this->detailBottomAdImage, 'url'=>$this->detailBottomAdUrl, 'adunit'=>$this->detailBottomAdUnitId, 'message_title'=>$this->customMessageTitle, 'message_path'=>$this->customMessagePath, 'message_image'=>$this->customMessageImg];
		$leaf = ['enable'=>1, 'position_pic1'=>'/uploads/blessing/position_pic1.png', 'position_pic2'=>'/uploads/blessing/position_pic2.png'];
		$banner = add_domain_deep($banner, ['image', 'message_image']);
		$footer = add_domain_deep($footer, ['image', 'message_image']);
		$leaf = add_domain_deep($leaf, ['position_pic1', 'position_pic2']);
		$addmy = $this->detailAddMyEnable;
		$feedback = $this->detailFeedbackEnable;
		$ad_fixed = $this->miniprogram->review==1 ? 0 : intval($this->miniprogram->ad_fixed);
		$ad_fixed_percent = $this->miniprogram->review==1 ? 0 : 100-intval($this->miniprogram->ad_fixed_percent);
		$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->detailPositionWxAdEnable:0, 'adunit'=>$this->detailPositionWxAdUnitId];
		$subscribe_id = $this->miniprogram->subscribe_id;
		$texts = ['top_text'=>'我很喜欢这篇文章,给你看看！', 'bottom_text'=>'把这篇文章送给朋友们看看吧', 'top_name_text'=>'，向你问好', 'bottom_name_text'=>'，向你问好'];
		$tips = ['enable'=>$this->detailTipsEnable, 'text'=>$this->detailTipsText, 'url'=>$detailTipsUrl, 'position'=>$this->detailTipsPosition, 'redirect'=>$this->detailTipsRedirect];
		success(compact('blessing', 'list', 'banner', 'footer', 'leaf', 'addmy', 'feedback', 'ad_fixed', 'ad_fixed_percent', 'wxpositionad', 'texts', 'tips', 'material', 'subscribe_id'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	private function _changeNum($num) {
		if (!is_numeric($num)) return 0;
		if ($num > 10000) return number_format($num/10000, 1, '.', '').'w+';
		if ($num > 1000) return number_format($num/1000, 1, '.', '').'k+';
		return $num;
	}
	
	//赞
	public function like() {
		$id = $this->request->post('id', 0);
		if ($id<=0) error('祝福不存在');
		SQL::share('blessing')->where($id)->update(['likes'=>['+1']]);
		SQL::share('blessing_attr')->where("blessing_id='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['likes'=>['+1']]);
		success('ok');
	}
	
	//采集
	//curl /api/blessing/pickBlessing
	public function pickBlessing() {
		/*
		$d = requestData('get', "https://mw.51dugou.com/api/details.php?id=CIUBAJESTBZBGQ&rtime=1581070884&index=27&cid=27&vel=6.0.34", '', true, false, [
			'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
			'referer: https://servicewechat.com/wxcd595a85de47a18d/55/page-frame.html'
		]);
		//echo json_encode($d);exit;
		$content = $d['newModule']['node'];
		preg_match_all('/<img .*?src="([^"]+)"/', $content, $matcher);
		if ($matcher) {
			foreach ($matcher[1] as $m) {
				$u = $this->_getFile($m);
				$content = str_replace($m, $u, $content);
			}
		}
		$pic = $this->_getFile($d['newModule']['imgSrc']);
		$share_pic = $this->_getFile($d['newModule']['share_img']);
		$bg_pic = $this->_getFile($d['newModule']['bg_image']);
		$bg_music = $this->_getFile($d['bg_music'], 'mp3');
		$top_avatar_pic = $this->_getFile($d['bgtarurlTop']);
		$bottom_avatar_pic = $this->_getFile($d['bgtarurlBottom']);
		$r = [];
		$r['title'] = $d['newModule']['title'];
		$r['pic'] = $pic;
		$r['share_pic'] = $share_pic;
		$r['bg_pic'] = $bg_pic;
		$r['bg_music'] = $bg_music;
		$r['top_avatar_pic'] = $top_avatar_pic;
		$r['bottom_avatar_pic'] = $bottom_avatar_pic;
		$r['content'] = $content;
		$r['text_color'] = $d['titlecolor'];
		$r['border_color'] = $d['borderColor'];
		$r['add_time'] = strtotime(str_replace('年', '-', str_replace('月', '-', str_replace('日', '', $d['newModule']['time']))));
		$blessing_id = SQL::share('blessing')->insert($r);
		$rs = SQL::share('miniprogram')->where("type='2'")->sort('id ASC')->find('id');
		foreach ($rs as $g) {
			$miniprogram_id = $g->id;
			SQL::share('blessing_attr')->insert(compact('miniprogram_id', 'blessing_id'));
		}
		echo 'YES';
		*/
		exit;
		$offset = $this->request->get('offset', 0);
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		if (ob_get_level() == 0) ob_start();
		ob_implicit_flush(true);
		ob_clean();
		$count = 0;
		$page = 1;
		if ($offset>0) $page = $offset;
		for ($i=$page; $i>0; $i--) {
			$json = requestData('get', "https://mw.51dugou.com/api/newList.php?maxPid=0&page={$i}&cid=18&vel=6.0.37", '', true, false, [
				'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
				'referer: https://servicewechat.com/wxb82033daeffff893/57/page-frame.html'
			]);
			//echo json_encode($json);exit;
			if (intval($json['error'])!=0) error(json_encode($json));
			if (is_array($json['list'][0]['list'])) {
				$list = $json['list'][0]['list'];
				$list = array_reverse($list);
				foreach ($list as $l) {
					if (SQL::share('blessing')->where("title='".$l['Name']."'")->exist()) continue;
					$rs = SQL::share('blessing')->find('title');
					if ($rs) {
						$similar = false;
						foreach ($rs as $g) {
							similar_text($g->title, $l['title'], $percent);
							if ($percent > 70) {
								$similar = true;
								break;
							}
						}
						if ($similar) continue;
					}
					$d = requestData('get', "https://mw.51dugou.com/api/details.php?id=".$l['FestivalID']."&rtime=undefined&index=18&cid=18&vel=6.0.37", '', true, false, [
						'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
						'referer: https://servicewechat.com/wxb82033daeffff893/57/page-frame.html'
					]);
					//echo json_encode($d);exit;
					$content = $d['newModule']['node'];
					preg_match_all('/<img .*?src="([^"]+)"/', $content, $matcher);
					if ($matcher) {
						foreach ($matcher[1] as $m) {
							$u = $this->_getFile($m);
							$content = str_replace($m, $u, $content);
						}
					}
					$pic = $this->_getFile($d['newModule']['imgSrc']);
					$share_pic = $this->_getFile($d['newModule']['share_img']);
					$bg_pic = $this->_getFile($d['newModule']['bg_image']);
					$bg_music = $this->_getFile($d['bg_music'], 'mp3');
					$top_avatar_pic = $this->_getFile($d['bgtarurlTop']);
					$bottom_avatar_pic = $this->_getFile($d['bgtarurlBottom']);
					$r = [];
					$r['title'] = $d['newModule']['title'];
					$r['pic'] = $pic;
					$r['share_pic'] = $share_pic;
					$r['bg_pic'] = $bg_pic;
					$r['bg_music'] = $bg_music;
					$r['top_avatar_pic'] = $top_avatar_pic;
					$r['bottom_avatar_pic'] = $bottom_avatar_pic;
					$r['content'] = $content;
					$r['text_color'] = $d['titlecolor'];
					$r['border_color'] = $d['borderColor'];
					$r['add_time'] = strtotime(str_replace('年', '-', str_replace('月', '-', str_replace('日', '', $d['newModule']['time']))));
					$blessing_id = SQL::share('blessing')->insert($r);
					$rs = SQL::share('miniprogram')->where("type='2'")->sort('id ASC')->find('id');
					foreach ($rs as $g) {
						$miniprogram_id = $g->id;
						SQL::share('blessing_attr')->insert(compact('miniprogram_id', 'blessing_id'));
					}
					$count++;
				}
			}
		}
		ob_flush();
		flush();
		ob_end_flush();
		write_log("GET BLESSINGS COMPLETE, QUANTITY {$count}", '/temp/blessing.txt');
		success("GET BLESSINGS COMPLETE, QUANTITY {$count}");
	}
	
	private function _getFile($url, $suffix='') {
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$ch = curl_init();
		if (!strlen($suffix)) {
			if (strpos($url, 'wx_fmt=')!==false) $suffix = substr($url, strrpos($url, 'wx_fmt=')+7);
			if (!$suffix) $suffix = substr($url, strrpos($url, '.')+1);
			if (!preg_match('/^(jpe?g|png|gif|bmp)$/', $suffix)) $suffix = 'jpg';
		}
		if ($suffix=='jpeg') $suffix = 'jpg';
		$timeout = 60*5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		if (substr($url, 0, 8)=='https://') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		$content = curl_exec($ch);
		curl_close($ch);
		if ($suffix=='mp3') {
			$file = upload_obj_file($content, 'blessing', NULL, 1, false, array('mp3'));
		} else {
			$file = upload_obj_file($content, 'blessing');
		}
		$file = add_domain($file);
		return $file;
		/*$filename = generate_sn().'.'.$suffix;
		$dir = UPLOAD_PATH.'/blessing/'.date('Y').'/'.date('m').'/'.date('d');
		makedir($dir);
		$res = @fopen(ROOT_PATH.$dir.'/'.$filename, 'a');
		@fwrite($res, $content);
		@fclose($res);
		$file = str_replace('/public/', '/', $dir).'/'.$filename;
		if (in_array($suffix, ['jpg', 'jpeg'])) {
			image_compress('/public/'.$file, 1, '/public/'.$file);
		}
		return $file;*/
	}

}
