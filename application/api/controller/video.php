<?php
class video extends core {
	private $indexListAdEnable; //首页-列表广告开关
	private $indexListAdImage; //首页-列表广告图片
	private $indexListAdUrl; //首页-列表广告链接
	
	private $detailBtnEnable; //详情页-隐藏按钮开关
	private $detailBtnText; //详情页-隐藏按钮文字
	private $detailBtnUrl; //详情页-隐藏按钮链接
	
	private $detailListAdEnable; //详情页-列表广告开关
	private $detailListAdImage; //详情页-列表广告图片
	private $detailListAdUrl; //详情页-列表广告链接
	
	private $returnEnable; //多重返回开关
	private $returnEverybody; //查看过新的都进行多重返回
	private $returnUrls; //多重返回网址组
	
	private $detailAddMyEnable; //详情页-添加到我的小程序开关
	private $detailFeedbackEnable; //详情页-投诉举报开关
	
	private $wxAdEnable; //微信广告总开关
	private $indexListWxAdEnable; //首页-列表微信广告开关
	private $indexListWxAdUnitId; //首页-列表微信广告UnitId
	private $indexListWxVideoAdUnitId; //首页-列表微信视频广告UnitId
	private $detailListWxAdEnable; //详情页-列表微信广告开关
	private $detailListWxAdUnitId; //详情页-列表微信广告UnitId
	private $detailListWxVideoAdUnitId; //详情页-列表微信视频广告UnitId
	private $detailVideoWxAdEnable; //详情页-微信广告开关
	private $detailVideoWxAdType; //详情页-微信广告类型
	private $detailVideoWxAdUnitId; //详情页-微信广告UnitId
	private $detailVideoWxAdImage; //详情页-自定义广告图片
	private $detailVideoWxAdUrl; //详情页-自定义广告链接
	private $detailPositionWxAdEnable; //详情页-微信插屏广告开关
	private $detailPositionWxAdUnitId; //详情页-微信插屏广告UnitId
	private $detailRewardedWxAdEnable; //详情页-微信激励广告开关
	private $detailRewardedWxAdUnitId; //详情页-微信激励广告UnitId
	private $indexPositionWxAdEnable; //首页-微信插屏广告开关
	private $indexPositionWxAdUnitId; //首页-微信插屏广告UnitId
	
	private $city;
	private $nongli;
	
	private $appId;
	private $version;
	private $versionNum;
	private $miniprogram;

	public function __construct() {
		parent::__construct();
		$this->appId = $this->request->get('appId');
		if (!strlen($this->appId) && isset($this->headers['Appid'])) $this->appId = $this->headers['Appid'];
		$this->version = $this->request->get('version');
		if (!strlen($this->appId)) error('缺失AppID');
		$this->versionNum = strlen($this->version) ? (is_numeric(str_replace('.', '', $this->version)) ? intval(str_replace('.', '', $this->version)) : $this->version) : 0;
		$configs = $this->configs;
		$this->miniprogram = SQL::share('miniprogram')->where("appid='{$this->appId}'")->row();
		if (!$this->miniprogram) error('小程序数据错误');
		
		if (!SQL::share('miniprogram_config')->where("miniprogram_id='{$this->miniprogram->id}'")->exist()) {
			$rs = SQL::share('config')->where("name LIKE 'G_VIDEO%'")->find('id, content');
			if ($rs) {
				$data = array();
				foreach ($rs as $row) {
					$data[] = array('miniprogram_id'=>$this->miniprogram->id, 'config_id'=>$row->id, 'content'=>$row->content);
				}
				SQL::share('miniprogram_config')->insert($data);
			}
		}
		$rs = SQL::share('config c')->left('miniprogram_config mc', 'mc.config_id=c.id')->where("c.name LIKE 'G_VIDEO%' AND mc.miniprogram_id='{$this->miniprogram->id}'")->find('c.name, mc.content');
		if ($rs) {
			$configs = array();
			foreach ($rs as $row) {
				$configs[$row->name] = $row->content;
			}
		}
		
		$this->indexListAdEnable = $this->request->act('G_VIDEO_INDEX_LIST_AD_ENABLE', 0, '', $configs);
		$this->indexListAdImage = $this->request->act('G_VIDEO_INDEX_LIST_AD_IMAGE', '', '', $configs);
		$this->indexListAdUrl = $this->request->act('G_VIDEO_INDEX_LIST_AD_URL', '', '', $configs);
		$this->detailBtnEnable = $this->request->act('G_VIDEO_DETAIL_BTN_ENABLE', 0, '', $configs);
		$this->detailBtnText = $this->request->act('G_VIDEO_DETAIL_BTN_TEXT', '', '', $configs);
		$this->detailBtnUrl = $this->request->act('G_VIDEO_DETAIL_BTN_URL', '', '', $configs);
		$this->detailListAdEnable = $this->request->act('G_VIDEO_DETAIL_LIST_AD_ENABLE', 0, '', $configs);
		$this->detailListAdImage = $this->request->act('G_VIDEO_DETAIL_LIST_AD_IMAGE', '', '', $configs);
		$this->detailListAdUrl = $this->request->act('G_VIDEO_DETAIL_LIST_AD_URL', '', '', $configs);
		$this->returnEnable = $this->request->act('G_VIDEO_RETURN_ENABLE', 0, '', $configs);
		$this->returnEverybody = $this->request->act('G_VIDEO_RETURN_EVERYBODY', 0, '', $configs);
		$this->returnUrls = $this->request->act('G_VIDEO_RETURN_URLS', '', '', $configs);
		$this->detailAddMyEnable = $this->request->act('G_VIDEO_DETAIL_ADDMY_ENABLE', 0, '', $configs);
		$this->detailFeedbackEnable = $this->request->act('G_VIDEO_DETAIL_FEEDBACK', 0, '', $configs);
		$this->wxAdEnable = $this->request->act('G_VIDEO_WX_AD_ENABLE', 0, '', $configs);
		$this->indexListWxAdEnable = $this->request->act('G_VIDEO_INDEX_LIST_WX_AD_ENABLE', 0, '', $configs);
		$this->indexListWxAdUnitId = $this->request->act('G_VIDEO_INDEX_LIST_WX_AD_UNITID', '', '', $configs);
		$this->indexListWxVideoAdUnitId = $this->request->act('G_VIDEO_INDEX_LIST_WX_VIDEO_AD_UNITID', '', '', $configs);
		$this->detailListWxAdEnable = $this->request->act('G_VIDEO_DETAIL_LIST_WX_AD_ENABLE', 0, '', $configs);
		$this->detailListWxAdUnitId = $this->request->act('G_VIDEO_DETAIL_LIST_WX_AD_UNITID', '', '', $configs);
		$this->detailListWxVideoAdUnitId = $this->request->act('G_VIDEO_DETAIL_LIST_WX_VIDEO_AD_UNITID', '', '', $configs);
		$this->detailVideoWxAdEnable = $this->request->act('G_VIDEO_DETAIL_VIDEO_WX_AD_ENABLE', 0, '', $configs);
		$this->detailVideoWxAdType = $this->request->act('G_VIDEO_DETAIL_VIDEO_WX_AD_TYPE', 0, '', $configs);
		$this->detailVideoWxAdUnitId = $this->request->act('G_VIDEO_DETAIL_VIDEO_WX_AD_UNITID', '', '', $configs);
		$this->detailVideoWxAdImage = $this->request->act('G_VIDEO_DETAIL_VIDEO_WX_AD_IMAGE', '', '', $configs);
		$this->detailVideoWxAdUrl = $this->request->act('G_VIDEO_DETAIL_VIDEO_WX_AD_URL', '', '', $configs);
		$this->detailPositionWxAdEnable = $this->request->act('G_VIDEO_DETAIL_POSITION_WX_AD_ENABLE', 0, '', $configs);
		$this->detailPositionWxAdUnitId = $this->request->act('G_VIDEO_DETAIL_POSITION_WX_AD_UNITID', '', '', $configs);
		$this->detailRewardedWxAdEnable = $this->request->act('G_VIDEO_DETAIL_REWARDED_WX_AD_ENABLE', 0, '', $configs);
		$this->detailRewardedWxAdUnitId = $this->request->act('G_VIDEO_DETAIL_REWARDED_WX_AD_UNITID', '', '', $configs);
		$this->indexPositionWxAdEnable = $this->request->act('G_VIDEO_INDEX_POSITION_WX_AD_ENABLE', 0, '', $configs);
		$this->indexPositionWxAdUnitId = $this->request->act('G_VIDEO_INDEX_POSITION_WX_AD_UNITID', '', '', $configs);
		
		$this->city = $this->request->session('city');
		$this->nongli = $this->request->session('nongli');
		/*
		if (!strlen($this->city)) {
			$o = get_ip(true);
			if (!isset($o['city'])) {
				$o = [];
				$o['city'] = '广东';
			} else if ($o['city']=='XX') {
				$o['city'] = $o['region'];
			}
			$this->city = $o['city'];
			$_SESSION['city'] = $this->city;
		}
		*/
		if (!strlen($this->nongli)) {
			$lunar = m('lunar');
			$nongli=$lunar->convertSolarToLunar(date('Y'),date('m'),date('d'));
			$this->nongli = '今天是农历'.$nongli[1].$nongli[2];
			$_SESSION['nongli'] = $this->nongli;
		}
		
		if ($this->miniprogram->review==1) {
			$this->miniprogram->subscribe_id = '';
			$this->indexListAdEnable = 0;
			$this->detailBtnEnable = 0;
			$this->detailListAdEnable = 0;
			$this->returnEnable = 0;
			$this->detailAddMyEnable = 0;
			$this->detailFeedbackEnable = 0;
			$this->wxAdEnable = 0;
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
	
	//视频列表
	public function index() {
		$category_id = $this->request->get('category_id', 0);
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		$where = "a.status=1 AND aa.miniprogram_id='{$this->miniprogram->id}'";
		if ($category_id>1) {
			$where .= " AND a.category_id='{$category_id}'";
			$sort = 'aa.clicks DESC';
		} else {
			$sort = 'a.id DESC';
		}
		$j = $offset*$pagesize;
		$rand = mt_rand($j, $j+$pagesize);
		$list = [];
		$rs = SQL::share('video a')->left('video_attr aa', 'a.id=aa.video_id')
			->where($where)->sort($sort)->limit($offset, $pagesize)
			->find("a.*, aa.clicks, aa.likes");
		if ($rs) {
			shuffle($rs);
			$_time = intval(date('H'));
			if ($_time>5 && $_time<=11) $_time = '上午好，';
			else if ($_time>11 && $_time<=14) $_time = '中午好，';
			else if ($_time>14 && $_time<=18) $_time = '下午好，';
			else $_time = '晚上好，';
			foreach ($rs as $g) {
				$title = str_replace('[_city_]', $this->city, $g->title);
				$title = str_replace('[_time_]', $_time, $title);
				$title = str_replace('[_date_]', date('今天是m月d日'), $title);
				$title = str_replace('[_nongli_]', $this->nongli, $title);
				$g->title = $title;
				$g->played = $this->_changeNum($g->clicks);
				$g->likes = $this->_changeNum($g->likes);
				$g->type = 0; //0视频，1广告，2跳转小程序
				//type=1: url网址, img图片
				//type=2: url小程序APPID, img图片
				$list[] = $g;
				$j++;
				
				if ($this->indexListAdEnable && $j==$rand) {
					$r = new stdClass();
					//if (mt_rand(1, 1000)%2==0) {
					$r->type = 1;
					$r->img = $this->indexListAdImage;
					$r->url = $this->indexListAdUrl;
					//} else {
					//	$r->type = 2;
					//	$r->img = '/uploads/miniprogram.png';
					//	$r->appid = 'wx2a81a778dbb0aba3';
					//}
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
		$list = add_domain_deep($list, ['img', 'url']);
		
		$category = SQL::share('video_category')->sort('sort ASC, id ASC')->find();
		
		$res = $this->_returnUrls();
		$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->indexPositionWxAdEnable:0, 'adunit'=>$this->indexPositionWxAdUnitId];
		
		success(compact('res', 'category', 'list', 'wxpositionad'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	//视频详情
	public function detail() {
		$id = $this->request->get('id', 0);
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		if ($id<=0) error('缺少参数');
		$_time = intval(date('H'));
		if ($_time>5 && $_time<=11) $_time = '上午好，';
		else if ($_time>11 && $_time<=14) $_time = '中午好，';
		else if ($_time>14 && $_time<=18) $_time = '下午好，';
		else $_time = '晚上好，';
		
		$where = '';
		$video = SQL::share('video a')->left('video_attr aa', 'a.id=aa.video_id')->where("a.id='{$id}' AND aa.miniprogram_id='{$this->miniprogram->id}' {$where}")
			->row('a.*, aa.clicks, aa.likes');
		if (!$video) error('记录不存在');
		$title = str_replace('[_city_]', $this->city, $video->title);
		$title = str_replace('[_time_]', $_time, $title);
		$title = str_replace('[_date_]', date('今天是m月d日'), $title);
		$title = str_replace('[_nongli_]', $this->nongli, $title);
		$video->title = $title;
		$video->clicks += 1;
		$video->played = $this->_changeNum($video->clicks);
		$video->likes = $this->_changeNum($video->likes);
		$video->info = NULL;
		if (strlen($video->url) && !preg_match('/^https?:\/\//', $video->url)) {
			$video->info = $this->getInfo(ROOT_PATH."/public{$video->url}");
		}
		$video = add_domain_deep($video, ['img', 'url']);
		if (stripos($this->ua, 'mpcrawler')===false) {
			SQL::share('video_attr')->where("video_id='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['clicks'=>['+1']]);
			SQL::share('video')->where($id)->update(['played'=>['+1'], 'today_clicks'=>['+1']]);
			if (SQL::share('admin_miniprogram')->where("miniprogram_id='{$this->miniprogram->id}'")->exist()) {
				if (SQL::share('admin_miniprogram_article')->where("miniprogram_id='{$this->miniprogram->id}'")->comparetime('m', 'add_time', '=0')->exist()) {
					SQL::share('admin_miniprogram_article')->where("miniprogram_id='{$this->miniprogram->id}'")->comparetime('m', 'add_time', '=0')->update(['clicks'=>['+1'], 'today_clicks'=>['+1']]);
				} else {
					SQL::share('admin_miniprogram_article')->insert(['miniprogram_id'=>$this->miniprogram->id, 'clicks'=>1, 'today_clicks'=>1, 'add_time'=>strtotime(date('Y-m-1'))]);
				}
			}
		}
		
		$where = "a.id!='{$id}' AND aa.miniprogram_id='{$this->miniprogram->id}' AND status=1 AND category_id='{$video->category_id}'";
		$j = $offset*$pagesize;
		$rand = mt_rand($j, $j+$pagesize);
		$list = [];
		$rs = SQL::share('video a')->left('video_attr aa', 'a.id=aa.video_id')
			->where($where)->sort('RAND()')->limit($offset, $pagesize)->find('a.*, aa.clicks, aa.likes');
		if ($rs) {
			shuffle($rs);
			foreach ($rs as $g) {
				$title = str_replace('[_city_]', $this->city, $g->title);
				$title = str_replace('[_time_]', $_time, $title);
				$title = str_replace('[_date_]', date('今天是m月d日'), $title);
				$title = str_replace('[_nongli_]', $this->nongli, $title);
				$g->title = $title;
				$g->url = '%domain%'.$g->url;
				$g->played = $this->_changeNum($g->clicks);
				$g->likes = $this->_changeNum($g->likes);
				$g->type = 0; //0视频，1广告，2跳转小程序
				$list[] = $g;
				$
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
		$list = add_domain_deep($list, ['img', 'url']);
		
		$date = date('Y-m-d');
		$is_android = $this->is_android;
		$btn = ['enable'=>$this->detailBtnEnable, 'text'=>$this->detailBtnText, 'url'=>$this->detailBtnUrl];
		$wxvideoad = ['enable'=>$this->wxAdEnable==1?$this->detailVideoWxAdEnable:0, 'type'=>$this->detailVideoWxAdType, 'adunit'=>$this->detailVideoWxAdUnitId];
		$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->detailPositionWxAdEnable:0, 'adunit'=>$this->detailPositionWxAdUnitId];
		$rewardedad = ['enable'=>$this->wxAdEnable==1?$this->detailRewardedWxAdEnable:0, 'adunit'=>$this->detailRewardedWxAdUnitId];
		$addmy = $this->detailAddMyEnable;
		$feedback = $this->detailFeedbackEnable;
		$ad_fixed = $this->miniprogram->review==1 ? 0 : intval($this->miniprogram->ad_fixed);
		$subscribe_id = $this->miniprogram->subscribe_id;
		$banner = ['enable'=>$this->wxAdEnable==1?$this->detailVideoWxAdEnable:0, 'type'=>$this->detailVideoWxAdType, 'image'=>$this->detailVideoWxAdImage, 'url'=>$this->detailVideoWxAdUrl, 'adunit'=>$this->detailVideoWxAdUnitId, 'message_title'=>$this->customMessageTitle, 'message_path'=>$this->customMessagePath, 'message_image'=>$this->customMessageImg];
		$banner = add_domain_deep($banner, ['image', 'message_image']);
		success(compact('video', 'btn', 'addmy', 'feedback', 'ad_fixed', 'wxvideoad', 'wxpositionad', 'rewardedad', 'list', 'date', 'is_android', 'subscribe_id', 'banner'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	private function _changeNum($num) {
		if ($num > 10000) return number_format($num/10000, 1, '.', '').'w+';
		if ($num > 1000) return number_format($num/1000, 1, '.', '').'k+';
		return $num;
	}
	
	//视频分类
	public function category() {
		$rs = SQL::share('video_category')->sort('sort ASC, id ASC')->find();
		success($rs);
	}
	
	//获取视频信息
	public function getInfo($url) {
		$getVideoInfo = function($file) {
			$command = sprintf('/usr/local/bin/ffmpeg -i "%s" 2>&1', $file);
			ob_start();
			passthru($command);
			$info = ob_get_contents();
			ob_end_clean();
			$data = [];
			if (preg_match("/Duration: (.*?), start: (.*?), bitrate: (\d*) kb\/s/", $info, $matcher)) {
				$data['duration'] = $matcher[1]; //播放时间
				$duration = explode(':', $matcher[1]);
				$data['seconds'] = $duration[0] * 3600 + $duration[1] * 60 + $duration[2]; //转换播放时间为秒数
				$data['start'] = $matcher[2]; //开始时间
				$data['bitrate'] = $matcher[3]; //码率(kb)
			}
			if (preg_match("/Video: (.*?), (.*?), (\d+x\d+)[,\s]/", $info, $matcher)) {
				$data['vcodec'] = $matcher[1]; //视频编码格式
				$data['vformat'] = $matcher[2]; //视频格式
				$data['resolution'] = $matcher[3]; //视频分辨率
				$resolution = explode('x', $matcher[3]);
				$data['width'] = $resolution[0];
				$data['height'] = $resolution[1];
			}
			if (preg_match("/Audio: (\w*), (\d*) Hz/", $info, $matcher)) {
				$data['acodec'] = $matcher[1]; //音频编码
				$data['asamplerate'] = $matcher[2]; //音频采样频率
			}
			if (isset($data['seconds']) && isset($data['start'])) {
				$data['play_time'] = $data['seconds'] + $data['start']; //实际播放时间
			}
			$data['size'] = filesize($file); //文件大小
			return $data;
		};
		return $getVideoInfo($url);
	}
	
	//赞
	public function like() {
		$id = $this->request->post('id', 0);
		if ($id<=0) error('视频不存在');
		SQL::share('video')->where($id)->update(['likes'=>['+1']]);
		SQL::share('video_attr')->where("video_id='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['likes'=>['+1']]);
		success('ok');
	}
	
	//采集
	//curl /api/video/pickVideo?type=51dugou
	public function pickVideo() {
		$type = $this->request->get('type');
		if (!strlen($type)) error('MISSING TYPE');
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		if (ob_get_level() == 0) ob_start();
		ob_implicit_flush(true);
		ob_clean();
		$count = 0;
		switch ($type) {
			case 'gaoxiaovod':
				$listUrl = 'https://www.gaoxiaovod.com/jingdian/';
				$detailUrl = 'https://www.gaoxiaovod.com';
				for ($k=0; $k<=4; $k++) {
					$html = requestData('get', $listUrl.($k==0?'':$k));
					preg_match('/<div class="piclist2">([\s\S]+?)<\/div><div class="clear">/', $html, $matcher);
					if ($matcher) {
						preg_match_all('/<a href="([^"]+)".+? title="([^"]+)"><img src="([^"]+)"/', $matcher[1], $matcher);
						if ($matcher) {
							for ($i=0; $i<count($matcher[0]); $i++) {
								if (SQL::share('video')->where("title='".$matcher[2][$i]."'")->exist()) continue;
								$rs = SQL::share('video')->find('title');
								if ($rs) {
									$similar = false;
									foreach ($rs as $g) {
										similar_text($g->title, $matcher[2][$i], $percent);
										if ($percent > 70) {
											$similar = true;
											break;
										}
									}
									if ($similar) continue;
								}
								$detail = requestData('get', $detailUrl.$matcher[1][$i]);
								preg_match("/poster:'([^']+)',[\s\S]+?video:[\s\S]+?\['([^']+)'/", $detail, $mt);
								if ($mt) {
									//$url = $this->_getFile($mt[2]);
									//if (!$url) continue;
									$img = $mt[1];
									$url = $mt[2];
									$video = [];
									$video['category_id'] = 4;
									$video['title'] = $matcher[2][$i];
									$video['img'] = $img;
									$video['url'] = $url;
									$video['time'] = 0;
									$video['played'] = random_num(50, 300) * 100;
									$video['likes'] = random_num(50, 300);
									$video['status'] = 1;
									$video['add_time'] = time();
									SQL::share('video')->insert($video);
									$count++;
								}
							}
						}
					}
				}
				break;
			case '51dugou':
				$listUrl = 'https://tv.51dugou.com/tv/tvIndex.php?type=type1&page=[page]&type_id=[type]&shareTo=0&casenum=4&ver=1.5&scene=1089';
				$detailUrl = 'https://tv.51dugou.com/tv/tvIndex.php?type=type2&id=[id]&casenum=4&rtime=undefined&ver=1.5&scene=undefined&gaoshitimeflag=1';
				for ($k=2; $k<=5; $k++) {
					for ($l=1; $l<=3; $l++) {
					//for ($l=3; $l>=1; $l--) {
						$json = requestData('get', str_replace('[page]', $l, str_replace('[type]', $k, $listUrl)), NULL, true, false, [
							'referer: https://servicewechat.com/wx4a13be18c31d542c/14/page-frame.html'
						]);
						if (!is_array($json) || !isset($json['note']) || !is_array($json['note']) || !count($json['note'])) break;
						foreach ($json['note'] as $note) {
							if (SQL::share('video')->where("title='".$note['video_name']."'")->exist()) continue;
							$rs = SQL::share('video')->find('title');
							if ($rs) {
								$similar = false;
								foreach ($rs as $g) {
									similar_text($g->title, $note['video_name'], $percent);
									if ($percent > 70) {
										$similar = true;
										break;
									}
								}
								if ($similar) continue;
							}
							$m = requestData('get', str_replace('[id]', $note['id'], $detailUrl), NULL, true, false, [
								'referer: https://servicewechat.com/wx4a13be18c31d542c/14/page-frame.html'
							]);
							$q = requestData('get', 'https://vv.video.qq.com/getinfo?vid='.$m['note']['video_id'].'&platform=101001&charge=0&otype=json');
							$q = str_replace('QZOutputJson=', '', $q) . 'qzo';
							$q = str_replace(';qzo', '', $q);
							$q = json_decode($q, true);
							if (!is_array($q) || !isset($q['vl']) || !isset($q['vl']['vi']) || !is_array($q['vl']['vi']) || !count($q['vl']['vi'])) continue;
							$q = $q['vl']['vi'][0];
							$img = $m['note']['video_we_pic'];
							$img = $this->_getFile($img, $type, true);
							$url = $q['ul']['ui'][0]['url'].$q['fn'].'?vkey='.$q['fvkey']; //url + fn + '?vkey=' + fvkey
							$url = $this->_getFile($url, $type);
							$video = [];
							$video['category_id'] = $k;
							$video['title'] = $note['video_name'];
							$video['img'] = $img;
							$video['url'] = $url;
							$video['time'] = 0;
							$video['played'] = random_num(50, 300) * 100;
							$video['likes'] = random_num(50, 300);
							$video['status'] = 1;
							$video['add_time'] = time();
							SQL::share('video')->insert($video);
							
							/*$article = [];
							$article['category_id'] = 6;
							$article['title'] = $note['video_name'];
							$article['pic'] = $img;
							$article['url'] = $url;
							$article['type'] = 5;
							$article['status'] = 1;
							$article['sort'] = 999;
							$article['add_time'] = time();
							$article_id = SQL::share('article')->insert($article);
							$rs = SQL::share('miniprogram')->where("second!='视频'")->sort('id ASC')->find('id');
							foreach ($rs as $g) {
								$miniprogram_id = $g->id;
								SQL::share('article_attr')->insert(compact('miniprogram_id', 'article_id'));
							}*/
							
							$count++;
						}
					}
				}
				break;
		}
		ob_flush();
		flush();
		ob_end_flush();
		write_log("GET VIDEOS COMPLETE, QUANTITY {$count}", '/temp/video.txt');
		success("GET VIDEOS COMPLETE, QUANTITY {$count}");
	}
	private function _getFile($url, $type, $isImg=false) {
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$ch = curl_init();
		$suffix = '';
		switch ($type) {
			case '51dugou':
				curl_setopt($ch, CURLOPT_REFERER, 'https://servicewechat.com/wx4a13be18c31d542c/14/page-frame.html');
				$suffix = 'mp4';
				$timeout = 60*5;
				if ($isImg) {
					if (strpos($url, 'wx_fmt=')!==false) $suffix = substr($url, strrpos($url, 'wx_fmt=')+7);
					if (!$suffix) $suffix = substr($url, strrpos($url, '.')+1);
					if (!preg_match('/^(jpe?g|png|mp4)$/', $suffix)) return NULL;
					$timeout = (preg_match('/^(jpe?g|png)$/', $suffix) ? 5 : 60*5);
				}
				break;
			default:
				if (strpos($url, 'wx_fmt=')!==false) $suffix = substr($url, strrpos($url, 'wx_fmt=')+7);
				if (!$suffix) $suffix = substr($url, strrpos($url, '.')+1);
				if (!preg_match('/^(jpe?g|png|mp4)$/', $suffix)) return NULL;
				$timeout = (preg_match('/^(jpe?g|png)$/', $suffix) ? 5 : 60*5);
				break;
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		if (substr($url, 0, 8)=='https://') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			//curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		}
		$content = curl_exec($ch);
		curl_close($ch);
		$file = upload_obj_file($content, 'video');
		$file = add_domain($file);
		return $file;
		/*$filename = generate_sn().'.'.$suffix;
		$dir = UPLOAD_PATH.'/video/'.date('Y').'/'.date('m').'/'.date('d');
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
