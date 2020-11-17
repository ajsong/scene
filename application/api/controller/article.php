<?php
#//文章动态
class article extends core {
	public $is_web;
	public $is_api;
	public $is_third_mini;
	
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
	
	private $detailBtnEnable; //详情页-隐藏按钮开关
	private $detailBtnText; //详情页-隐藏按钮文字
	private $detailBtnUrl; //详情页-隐藏按钮链接
	
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
	
	private $detailNewYearEnable; //详情页-新年气氛开关
	private $detailNewYearMusic; //详情页-新年气氛音乐链接
	private $detailNewYearMusic2; //详情页-新年气氛音乐链接2
	
	private $detailPositionEnable; //详情页-漂浮圈开关
	private $detailPositionType; //详情页-漂浮圈类型,1回到顶部,2更多精彩,3自定义
	private $detailPositionText; //详情页-漂浮圈文字,自定义时有效
	private $detailPositionUrl; //详情页-漂浮圈链接,小程序内的链接,自定义时有效
	
	private $detailPosAdUrl; //详情页-漂浮图片链接(支持网页、小程序链接)
	private $detailPosAdImage; //详情页-漂浮图片
	
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
	
	private $city;
	private $nongli;
	
	private $appId;
	private $version;
	private $versionNum;
	private $miniprogram;

	public function __construct() {
		parent::__construct();
		$this->is_web = IS_WEB;
		$this->is_api = IS_API;
		$this->is_third_mini = (strlen($this->referer) && strpos($this->referer, 'https://tmaservice.developer.toutiao.com')!==false) ? 1 : 0;
		if ( !($this->act == 'pickArticle' || ($this->is_web && $this->act == 'detail') || ($this->is_api && in_array($this->act, ['item', 'click']))) ) {
			$this->appId = $this->request->get('appId');
			if (!strlen($this->appId) && isset($this->headers['Appid'])) $this->appId = $this->headers['Appid'];
			$this->version = $this->request->get('version');
			if (!strlen($this->appId)) error('缺失AppID');
			$this->versionNum = strlen($this->version) ? (is_numeric(str_replace('.', '', $this->version)) ? intval(str_replace('.', '', $this->version)) : $this->version) : 0;
			$configs = $this->configs;
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
			
			$this->indexListAdEnable = $this->request->act('G_ARTICLE_INDEX_LIST_AD_ENABLE', 0, '', $configs);
			$this->indexListAdImage = $this->request->act('G_ARTICLE_INDEX_LIST_AD_IMAGE', '', '', $configs);
			$this->indexListAdUrl = $this->request->act('G_ARTICLE_INDEX_LIST_AD_URL', '', '', $configs);
			$this->indexAddMyEnable = $this->request->act('G_ARTICLE_INDEX_ADDMY_ENABLE', 0, '', $configs);
			$this->indexAddMyBgColor = $this->request->act('G_ARTICLE_INDEX_ADDMY_BGCOLOR', '', '', $configs);
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
			$this->detailBtnEnable = $this->request->act('G_ARTICLE_DETAIL_BTN_ENABLE', 0, '', $configs);
			$this->detailBtnText = $this->request->act('G_ARTICLE_DETAIL_BTN_TEXT', '', '', $configs);
			$this->detailBtnUrl = $this->request->act('G_ARTICLE_DETAIL_BTN_URL', '', '', $configs);
			$this->detailListAdEnable = $this->request->act('G_ARTICLE_DETAIL_LIST_AD_ENABLE', 0, '', $configs);
			$this->detailListAdImage = $this->request->act('G_ARTICLE_DETAIL_LIST_AD_IMAGE', '', '', $configs);
			$this->detailListAdUrl = $this->request->act('G_ARTICLE_DETAIL_LIST_AD_URL', '', '', $configs);
			$this->returnEnable = $this->request->act('G_ARTICLE_RETURN_ENABLE', 0, '', $configs);
			$this->returnEverybody = $this->request->act('G_ARTICLE_RETURN_EVERYBODY', 0, '', $configs);
			$this->returnUrls = $this->request->act('G_ARTICLE_RETURN_URLS', '', '', $configs);
			$this->detailTipsEnable = $this->request->act('G_ARTICLE_DETAIL_TIPS_ENABLE', 0, '', $configs);
			$this->detailTipsText = $this->request->act('G_ARTICLE_DETAIL_TIPS_TEXT', '', '', $configs);
			$this->detailTipsUrl = $this->request->act('G_ARTICLE_DETAIL_TIPS_URL', '', '', $configs);
			$this->detailTipsPosition = $this->request->act('G_ARTICLE_DETAIL_TIPS_POSITION_ENABLE', 0, '', $configs);
			$this->detailTipsRedirect = $this->request->act('G_ARTICLE_DETAIL_TIPS_REDIRECT', '', '', $configs);
			$this->detailNewYearEnable = $this->request->act('G_ARTICLE_DETAIL_NEW_YEAR_ENABLE', 0, '', $configs);
			$this->detailNewYearMusic = $this->request->act('G_ARTICLE_DETAIL_NEW_YEAR_MUSIC', '', '', $configs);
			$this->detailNewYearMusic2 = $this->request->act('G_ARTICLE_DETAIL_NEW_YEAR_MUSIC2', '', '', $configs);
			$this->detailPositionEnable = $this->request->act('G_ARTICLE_DETAIL_POSITION_ENABLE', 0, '', $configs);
			$this->detailPositionType = $this->request->act('G_ARTICLE_DETAIL_POSITION_TYPE', 0, '', $configs);
			$this->detailPositionText = $this->request->act('G_ARTICLE_DETAIL_POSITION_TEXT', '', '', $configs);
			$this->detailPositionUrl = $this->request->act('G_ARTICLE_DETAIL_POSITION_URL', '', '', $configs);
			$this->detailPosAdUrl = $this->request->act('G_ARTICLE_DETAIL_POSAD_URL', '', '', $configs);
			$this->detailPosAdImage = $this->request->act('G_ARTICLE_DETAIL_POSAD_IMAGE', '', '', $configs);
			$this->detailAddMyEnable = $this->request->act('G_ARTICLE_DETAIL_ADDMY_ENABLE', 0, '', $configs);
			$this->detailFeedbackEnable = $this->request->act('G_ARTICLE_DETAIL_FEEDBACK', 0, '', $configs);
			$this->wxAdEnable = $this->request->act('G_ARTICLE_WX_AD_ENABLE', 0, '', $configs);
			$this->indexListWxAdEnable = $this->request->act('G_ARTICLE_INDEX_LIST_WX_AD_ENABLE', 0, '', $configs);
			$this->indexListWxAdUnitId = $this->request->act('G_ARTICLE_INDEX_LIST_WX_AD_UNITID', '', '', $configs);
			$this->indexListWxVideoAdUnitId = $this->request->act('G_ARTICLE_INDEX_LIST_WX_VIDEO_AD_UNITID', '', '', $configs);
			$this->detailListWxAdEnable = $this->request->act('G_ARTICLE_DETAIL_LIST_WX_AD_ENABLE', 0, '', $configs);
			$this->detailListWxAdUnitId = $this->request->act('G_ARTICLE_DETAIL_LIST_WX_AD_UNITID', '', '', $configs);
			$this->detailListWxVideoAdUnitId = $this->request->act('G_ARTICLE_DETAIL_LIST_WX_VIDEO_AD_UNITID', '', '', $configs);
			$this->detailPositionWxAdEnable = $this->request->act('G_ARTICLE_DETAIL_POSITION_WX_AD_ENABLE', 0, '', $configs);
			$this->detailPositionWxAdUnitId = $this->request->act('G_ARTICLE_DETAIL_POSITION_WX_AD_UNITID', '', '', $configs);
			$this->indexPositionWxAdEnable = $this->request->act('G_ARTICLE_INDEX_POSITION_WX_AD_ENABLE', 0, '', $configs);
			$this->indexPositionWxAdUnitId = $this->request->act('G_ARTICLE_INDEX_POSITION_WX_AD_UNITID', '', '', $configs);
			$this->customMessageTitle = $this->request->act('G_ARTICLE_CUSTOM_MESSAGE_TITLE', '', '', $configs);
			$this->customMessagePath = $this->request->act('G_ARTICLE_CUSTOM_MESSAGE_PATH', '', '', $configs);
			$this->customMessageImg = $this->request->act('G_ARTICLE_CUSTOM_MESSAGE_IMG', '', '', $configs);
			
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
				/*$json = requestData('get', 'http://www.sojson.com/open/api/lunar/json.shtml');
				if (isset($json['status']) && intval($json['status'])==200) {
					$this->nongli = '今天是农历'.$json['data']['cnmonth'].'月'.$json['data']['cnday'];
					$_SESSION['nongli'] = $this->nongli;
				}*/
			}
			
			if (stripos($this->ua, 'mpcrawler')!==false) $this->miniprogram->promote_status = 0;
			if ($this->miniprogram->review==1 || $this->miniprogram->promote_status==0) {
				$this->miniprogram->mp_title = '';
				$this->miniprogram->subscribe_id = '';
				$this->miniprogram->trans_title = '';
				$this->indexListAdEnable = 0;
				$this->indexAddMyEnable = 0;
				$this->detailTopAdEnable = ($this->detailTopAdType==2 || $this->detailTopAdType==3) ? $this->detailTopAdEnable : 0;
				$this->detailBottomAdEnable = ($this->detailBottomAdType==2 || $this->detailBottomAdType==3) ? $this->detailBottomAdEnable : 0;
				$this->detailBtnEnable = 0;
				$this->detailListAdEnable = 0;
				$this->returnEnable = 0;
				$this->detailTipsEnable = 0;
				$this->detailTipsRedirect = '';
				$this->detailPositionEnable = 0;
				$this->detailPosAdUrl = '';
				$this->detailAddMyEnable = 0;
				$this->detailFeedbackEnable = 0;
				$this->wxAdEnable = 0;
			}
			
			//$this->miniprogram->promoting==1 //推广中，需要兼容处理
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
		$where = "a.status=1 AND aa.miniprogram_id='{$this->miniprogram->id}'";
		$category_id = $this->request->get('category_id', 0); #//分类id
		$keyword = $this->request->get('keyword'); #//关键词|搜索标题与内容
		$categories = $this->request->get('categories');
		$offset = $this->request->get('offset', 0); #//
		$pagesize = $this->request->get('pagesize', 8); #//
		$sort = 'aa.clicks DESC, a.sort ASC, a.add_time DESC';
		if ($keyword) {
			$where .= " AND (a.title like '%{$keyword}%' OR a.content like '%{$keyword}%')";
		}
		if ($category_id>0) {
			$where .= " AND a.category_id='{$category_id}'";
		} else {
			if ($category_id==0) { //推荐
				if (strlen($categories)) {
					//$where .= " AND a.category_id IN ({$categories})";
					$sort = "FIELD(category_id,{$categories}) DESC, aa.clicks DESC, a.sort ASC, a.id DESC";
				}
			} else if ($category_id==-1) { //最新
				$sort = 'a.add_time DESC';
			} else if ($category_id==-2) { //热文
				$where .= " AND a.recommend=1";
			} else if ($category_id==-3) { //精选
				$where .= " AND a.featured=1";
			}
		}
		
		$hiddens = SQL::share('miniprogram_article_hidden')->where("miniprogram_id='{$this->miniprogram->id}'")->returnArray()->find('article_id');
		if (count($hiddens)) {
			$where .= " AND a.id NOT IN (".implode(',', $hiddens).")";
		}
		
		if ($this->miniprogram->promote_status==0) {
			$where .= " AND a.id<=4";
		} else if ($this->miniprogram->review==1) {
			$where .= " AND a.id IN (".$this->configs['G_REVIEW_ARTICLE_SHOWN'].")";
		} else {
			$where .= " AND a.id>4";
		}
		
		if ($this->miniprogram->only_pic==1) {
			$where .= " AND a.type=0";
		}
		
		$j = $offset*$pagesize;
		$rand = mt_rand($j, $j+$pagesize);
		if ($this->is_third_mini && $offset>0) $rand = -1;
		$styleIndex = 0;
		$list = [];
		$rs = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')->cached(60*5)
			->where($where)->sort($sort)->limit($offset, $pagesize)
			->find("a.id, a.title, a.pic, a.type, a.content, aa.clicks, aa.likes, a.add_time, 0 as style, '' as pic2, '' as pic3");
		if ($rs) {
			if ($category_id==0) {
				shuffle($rs);
			}
			foreach ($rs as $g) {
				$g->clicks = $this->_changeNum($g->clicks);
				$g->likes = $this->_changeNum($g->likes);
				$g->add_time = date('m-d', $g->add_time);
				//$g->type = 0; //0图文，1自定义广告，2跳转小程序，3微信广告，4微信视频广告，5视频
				$g->style = $styleIndex%3 ? 0 : 1;
				if ($g->style==1) {
					preg_match_all('/<img.+?src="([^"]+)"/', $g->content, $matcher);
					if ($matcher && count($matcher) && isset($matcher[1]) && count($matcher[1])>=3) {
						if ($g->pic == $matcher[1][0]) {
							$g->pic2 = $matcher[1][1];
							$g->pic3 = $matcher[1][2];
						} else {
							$g->pic2 = $matcher[1][0];
							$g->pic3 = $matcher[1][1];
						}
					} else {
						$g->style = 0;
					}
				}
				$styleIndex++;
				unset($g->content);
				$list[] = $g;
				$j++;
				
				if ($this->is_third_mini) {
					if ($this->wxAdEnable && $this->indexListWxAdEnable && $j==$rand) {
						$r = new stdClass();
						$r->type = 3;
						$r->adunit = 'occ4fp56gab10bj942';
						$list[] = $r;
					}
				} else {
					if ($this->indexListAdEnable && $j==$rand) {
						$r = new stdClass();
						$r->type = 1;
						$r->pic = $this->indexListAdImage;
						$r->url = $this->indexListAdUrl;
						$list[] = $r;
					} else if ($this->wxAdEnable && $j==$rand) {
						if ($this->indexListWxAdEnable) {
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
			}
		}
		$list = add_domain_deep($list, ['pic']);
		
		if ($this->is_third_mini) {
			$this->indexAddMyEnable = 0;
		}
		
		$flashes = $this->_flash();
		$addmy = $this->indexAddMyEnable;
		$addmybgcolor = $this->indexAddMyBgColor;
		$res = $this->_returnUrls();
		$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->indexPositionWxAdEnable:0, 'adunit'=>$this->indexPositionWxAdUnitId];
		
		$cate0 = new stdClass();
		$cate0->id = 0;
		$cate0->name = '推荐';
		
		$cate1 = new stdClass();
		$cate1->id = -1;
		$cate1->name = '最新';
		
		$cate2 = new stdClass();
		$cate2->id = -2;
		$cate2->name = '热文';
		
		//$cate3 = new stdClass();
		//$cate3->id = -3;
		//$cate3->name = '精选';
		
		$categories = array();
		if ($this->miniprogram->review==0 && $this->miniprogram->promote_status==1) {
			$cates = SQL::share('article_category')->where("status=1")->sort('sort ASC, id ASC')->find('id, name');
			foreach ($cates as $g) {
				/*if ($g->name=='小说') {
					if ($this->miniprogram->id==6 && $this->miniprogram->review==0 && ($this->version=='develop' || $this->version=='trial')) {
						$cate4 = new stdClass();
						$cate4->id = '/pages/index/qians';
						$cate4->name = '运势求签';
						$categories[] = $cate4;
					}
				}*/
				$categories[] = $g;
			}
		}
		array_unshift($categories, $cate0, $cate1, $cate2);
		
		success(compact('res', 'addmy', 'addmybgcolor', 'flashes', 'categories', 'list', 'wxpositionad'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	//轮播图
	private function _flash() {
		$where = "a.status=1 AND aa.miniprogram_id='{$this->miniprogram->id}'";
		if ($this->miniprogram->promote_status==0) {
			$where .= " AND a.id<=4";
		} else if ($this->miniprogram->review==1) {
			$where .= " AND a.id IN (".$this->configs['G_REVIEW_ARTICLE_SHOWN'].")";
		} else {
			$where .= " AND a.id>4";
		}
		$flashes = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')->cached(60*5)
			->where($where)->sort('RAND()')->pagesize(3)->find("a.id, a.title, a.pic, 'detail' as ad_type, a.id as ad_content, '' as ad_indextype");
		//$flashes = SQL::share()->query("SELECT t1.id, t1.title, t1.pic FROM __ARTICLE__ t1 JOIN (SELECT ROUND( RAND()*((SELECT MAX(id) FROM __ARTICLE__)-(SELECT MIN(id) FROM __ARTICLE__)) + (SELECT MIN(id) FROM __ARTICLE__) ) AS id) t2 WHERE t1.id>=t2.id ORDER BY t1.id LIMIT 3");
		$flashes = add_domain_deep($flashes, ['pic']);
		return $flashes;
	}
	
	public function articleList() {
		$where = " AND aa.miniprogram_id='{$this->miniprogram->id}' AND a.id>4";
		$category_id = $this->request->get('category_id');
		$keyword = $this->request->get('keyword');
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		if ($keyword) {
			$where .= " AND (title like '%{$keyword}%' OR content like '%{$keyword}%')";
		}
		if ($category_id) {
			$where .= " AND category_id='{$category_id}'";
		}
		$j = $offset*$pagesize;
		$rand = mt_rand($j, $j+$pagesize);
		if ($this->is_third_mini && $offset>0) $rand = -1;
		$styleIndex = 0;
		$list = [];
		$rs = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')
			->where("status=1 AND more=1 {$where}")->sort('aa.clicks DESC, sort ASC, a.id DESC')->limit($offset, $pagesize)
			->find("a.id, a.title, a.pic, a.type, a.content, aa.clicks, aa.likes, add_time, 0 as style, '' as pic2, '' as pic3");
		if ($rs) {
			foreach ($rs as $g) {
				$g->clicks = $this->_changeNum($g->clicks);
				$g->likes = $this->_changeNum($g->likes);
				$g->add_time = date('m-d', $g->add_time);
				$g->style = $styleIndex%3 ? 0 : 1;
				if ($g->style==1) {
					preg_match_all('/<img.+?src="([^"]+)"/', $g->content, $matcher);
					if ($matcher && count($matcher) && isset($matcher[1]) && count($matcher[1])>=3) {
						if ($g->pic == $matcher[1][0]) {
							$g->pic2 = $matcher[1][1];
							$g->pic3 = $matcher[1][2];
						} else {
							$g->pic2 = $matcher[1][0];
							$g->pic3 = $matcher[1][1];
						}
					} else {
						$g->style = 0;
					}
				}
				$styleIndex++;
				unset($g->content);
				$list[] = $g;
				$j++;
				
				if ($this->is_third_mini) {
					if ($this->wxAdEnable && $this->indexListWxAdEnable && $j==$rand) {
						$r = new stdClass();
						$r->type = 3;
						$r->adunit = 'occ4fp56gab10bj942';
						$list[] = $r;
					}
				} else {
					if ($this->indexListAdEnable && $j==$rand) {
						$r = new stdClass();
						$r->type = 1;
						$r->pic = $this->indexListAdImage;
						$r->url = $this->indexListAdUrl;
						$list[] = $r;
					} else if ($this->wxAdEnable && $j==$rand) {
						if ($this->indexListWxAdEnable) {
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
			}
		}
		
		$list = add_domain_deep($list, ['pic']);
		$res = $this->_returnUrls();
		success(compact('res', 'list'), '成功', 0, ['appId'=>$this->appId]);
	}

	//文章详情
	public function detail() {
		$id = $this->request->get('id');
		$admin_id = $this->request->get('admin_id', 0);
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		$content_offset = $this->request->get('content_offset', 0); //获取其余部分详情内容
		if (!strlen($id)) error('缺少参数');
		$where = '';
		if ($this->is_web) {
			if (!SQL::share('admin_article')->where("admin_id={$admin_id} AND article_id={$id}")->exist()) {
				SQL::share('admin_article')->insert(array('admin_id'=>$admin_id, 'article_id'=>$id));
			}
			$article = SQL::share('article a')->left('admin_article aa', 'a.id=aa.article_id')->where("a.id='{$id}' AND aa.admin_id='{$admin_id}' {$where}")->cached(60*10)
				->row("a.*, aa.clicks, '{$admin_id}' as owner");
			if ($article) $this->smarty->assign('WEB_TITLE', $article->title);
		} else {
			if ($this->miniprogram->only_pic==1) {
				$where .= " AND a.type=0";
			}
			if ($this->miniprogram->promote_status==0) {
				if ($id>4) $where .= " AND 1=0";
			}
			$article = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')->where("a.id='{$id}' AND a.detail_status=1 AND aa.miniprogram_id='{$this->miniprogram->id}' {$where}")->cached(60*10)
				->row('a.*, aa.clicks, aa.likes');
		}
		if (!$article) error('记录不存在');
		$content = stripslashes($article->content);
		$content = preg_replace('/((?<!-)width|(?<!-)height):\s*\d+px;?/', '', $content);
		$content = preg_replace('/\s(width|height)="\d+"/', '', $content);
		if (!$this->is_web) {
			$content = preg_replace_callback('/<img([^>]+)>/', function($matcher) {
				if (preg_match('/style="([^"]*)"/', $matcher[1])) {
					$attr = preg_replace('/style="([^"]*)"/', 'style="$1;max-width:100%;height:auto;display:block;vertical-align:bottom;"', $matcher[1]);
				} else {
					$attr = ' style="max-width:100%;height:auto;display:block;vertical-align:bottom;"'.$matcher[1];
				}
				return "<img{$attr}>";
			}, $content);
		}
		$content_next = 0;
		$contents = explode('<div style="page-break-after: always"><span style="display: none;">&nbsp;</span></div>', $content);
		if ($content_offset>count($contents)-1) {
			$article = new stdClass();
			$article->content = '';
			success(compact('article', 'content_next'));
		}
		$content = add_domain_deep($contents[$content_offset]);
		if ($content_offset<count($contents)-1)$content_next = 1;
		if ($content_offset>0) {
			$article = new stdClass();
			$article->content = $content;
			success(compact('article', 'content_next'));
		}
		if (!$this->is_web) {
			if (stripos($this->ua, 'mpcrawler')===false) {
				SQL::share('article')->where($id)->update(['clicks'=>['+1'], 'today_clicks'=>['+1']]);
				SQL::share('article_attr')->where("article_id='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['clicks'=>['+1']]);
				if (SQL::share('admin_miniprogram')->where("miniprogram_id='{$this->miniprogram->id}'")->exist()) {
					if (SQL::share('admin_miniprogram_article')->where("miniprogram_id='{$this->miniprogram->id}'")->comparetime('m', 'add_time', '=0')->exist()) {
						SQL::share('admin_miniprogram_article')->where("miniprogram_id='{$this->miniprogram->id}'")->comparetime('m', 'add_time', '=0')->update(['clicks'=>['+1'], 'today_clicks'=>['+1']]);
					} else {
						SQL::share('admin_miniprogram_article')->insert(['miniprogram_id'=>$this->miniprogram->id, 'clicks'=>1, 'today_clicks'=>1, 'add_time'=>strtotime(date('Y-m-1'))]);
					}
				}
			}
		}
		$article->clicks = $article->clicks + 1;
		$article->content = $content;
		$article->clicks = $this->_changeNum($article->clicks);
		$article->likes = $this->_changeNum($article->likes);
		$article->add_time = date("m/d H:i", $article->add_time);
		$article = add_domain_deep($article, ['pic']);
		
		$list = [];
		if (!$this->is_web) {
			if ($this->miniprogram->recommend_hidden==0) {
				$where = "a.id!='{$id}' AND aa.miniprogram_id='{$this->miniprogram->id}' AND status=1 AND category_id='{$article->category_id}'";
				if ($this->miniprogram->promote_status==0) {
					$where .= " AND a.id<=4";
				} else if ($this->miniprogram->review==1) {
					$where .= " AND a.id IN (".$this->configs['G_REVIEW_ARTICLE_SHOWN'].")";
				} else {
					$where .= " AND a.id>4";
				}
				if ($this->miniprogram->only_pic==1) {
					$where .= " AND type=0";
				}
				if ($this->miniprogram->comment_hidden==0) {
					$j = 0;
					$page = 5;
					$rand = mt_rand($j, $j+$page);
					$rs = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')->cached(60*5)
						->where($where)->sort('RAND()')->pagesize($page)->find('a.id, title, pic, type, aa.clicks, aa.likes, add_time');
				} else {
					$j = $offset*$pagesize;
					$rand = mt_rand($j, $j+$pagesize);
					$rs = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')->cached(60*5)
						->where($where)->sort('RAND()')->limit($offset, $pagesize)->find('a.id, title, pic, type, aa.clicks, aa.likes, add_time');
				}
				if ($this->is_third_mini) {
					$this->detailListWxAdEnable = 0;
				}
				if ($rs) {
					shuffle($rs);
					foreach ($rs as $g) {
						$g->clicks = $this->_changeNum($g->clicks);
						$g->likes = $this->_changeNum($g->likes);
						//if (!$this->is_mini) $g->content = preg_replace('/[\n\r]+/', '', preg_replace('/<\/?[^>]+>/', '', $g->content));
						$g->add_time = date('m-d', $g->add_time);
						//$g->type = 0; //0视频，1广告，2跳转小程序
						//unset($g->content);
						$list[] = $g;
						$j++;
						if ($this->miniprogram->comment_hidden==1) {
							if ($this->is_third_mini) {
								if ($this->wxAdEnable && $this->detailListWxAdEnable && $j==$rand) {
									$r = new stdClass();
									$r->type = 3;
									$r->adunit = 'bhhi30ddjgbdk860g8';
									$list[] = $r;
								}
							} else {
								if ($this->detailListAdEnable && $j==$rand) {
									$r = new stdClass();
									$r->type = 1;
									$r->pic = $this->detailListAdImage;
									$r->url = $this->detailListAdUrl;
									$list[] = $r;
								} else if ($this->wxAdEnable && $j==$rand) {
									if ($this->detailListWxAdEnable) {
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
						}
					}
				}
				$list = add_domain_deep($list, ['pic']);
			}
		}
		
		$comment = [];
		if (!$this->is_web) {
			if ($this->miniprogram->comment_hidden==0) {
				$j = $offset*$pagesize;
				$rand = mt_rand($j, $j+$pagesize);
				$rs = SQL::share('article_comment')
					->where("miniprogram_id='{$this->miniprogram->id}' AND article_id='{$id}' AND status=1")->sort('id DESC')->limit($offset, $pagesize)->find("*, '' as member_name, '' as member_avatar");
				if ($rs) {
					foreach ($rs as $g) {
						$g->member_name = '网友';
						$g->member_avatar = '/images/avatar.png';
						$g->likes = $this->_changeNum($g->likes);
						$g->add_time = get_time_word($g->add_time);
						$g->type = 0;
						$comment[] = $g;
						$j++;
						
						if ($this->detailListAdEnable && $j==$rand) {
							$r = new stdClass();
							$r->type = 1;
							$r->pic = $this->detailListAdImage;
							$r->url = $this->detailListAdUrl;
							$comment[] = $r;
						} else if ($this->wxAdEnable && $this->detailListWxAdEnable && $j==$rand) {
							if (strlen($this->detailListWxAdUnitId)) {
								$r = new stdClass();
								$r->type = 3;
								$r->adunit = $this->detailListWxAdUnitId;
								$comment[] = $r;
							} else if (strlen($this->detailListWxVideoAdUnitId)) {
								$r = new stdClass();
								$r->type = 4;
								$r->adunit = $this->detailListWxVideoAdUnitId;
								$comment[] = $r;
							}
						}
					}
				}
				$comment = add_domain_deep($comment, ['member_avatar']);
			}
		}
		
		$bgmusic = '';
		if ($article->music_enable==1) {
			$bgmusic = $article->music;
		}
		
		$detailTipsUrl = '';
		if (!$this->is_web) {
			if ($this->miniprogram->early==1) {
				if (strlen($this->detailTipsUrl)) {
					$ids = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')->where("a.status=1 AND a.id!='{$id}' AND a.type=0")->sort('aa.clicks DESC')->pagesize(10)->returnArray()->find('a.id');
					if (count($ids)) {
						shuffle($ids);
						$detailTipsUrl = '/pages/index/detail?id='.$ids[0];
					}
				}
			} else {
				/*$ids = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')->where("a.status=1 AND a.id!='{$id}' AND a.type=0")->sort('aa.clicks DESC')->pagesize(10)->returnArray()->find('a.id');
				if (count($ids)) {
					shuffle($ids);
					$detailTipsUrl = '/pages/index/detail?id='.$ids[0];
				}*/
				if (strlen($this->detailTipsUrl)) {
					$detailTipsUrl = $this->detailTipsUrl;
				}
			}
		}
		
		if (!$this->is_web) {
			if ($this->is_third_mini) {
				$article->wxparse = 1;
				$this->detailBottomAdType = 3;
				$this->detailTopAdEnable = 0;
				$this->detailTopAdUnitId = '40978ks717v1ihcg56';
				$this->detailBottomAdUnitId = 'lp9n60afjgdb6697g6';
				$this->detailAddMyEnable = 0;
				$this->detailPosAdUrl = '';
				$this->miniprogram->mp_title = '';
				$this->miniprogram->trans_title = '';
				$this->miniprogram->subscribe_id = '';
				$this->miniprogram->ad_fixed = 0;
			}
			$banner = ['enable'=>$this->wxAdEnable==1?$this->detailTopAdEnable:0, 'type'=>$this->detailTopAdType, 'image'=>$this->detailTopAdImage, 'url'=>$this->detailTopAdUrl, 'adunit'=>$this->detailTopAdUnitId, 'message_title'=>$this->customMessageTitle, 'message_path'=>$this->customMessagePath, 'message_image'=>$this->customMessageImg];
			$footer = ['enable'=>$this->wxAdEnable==1?$this->detailBottomAdEnable:0, 'type'=>$this->detailBottomAdType, 'image'=>$this->detailBottomAdImage, 'url'=>$this->detailBottomAdUrl, 'adunit'=>$this->detailBottomAdUnitId, 'message_title'=>$this->customMessageTitle, 'message_path'=>$this->customMessagePath, 'message_image'=>$this->customMessageImg];
			$btn = ['enable'=>$this->detailBtnEnable, 'text'=>$this->detailBtnText, 'url'=>$this->detailBtnUrl];
			$tips = ['enable'=>$this->detailTipsEnable, 'text'=>$this->detailTipsText, 'url'=>$detailTipsUrl, 'position'=>$this->detailTipsPosition, 'redirect'=>$this->detailTipsRedirect];
			$newyear = ['enable'=>$this->detailNewYearEnable, 'music'=>$this->detailNewYearMusic, 'music2'=>$this->detailNewYearMusic2, 'bgmusic'=>$bgmusic];
			$banner = add_domain_deep($banner, ['image', 'message_image']);
			$footer = add_domain_deep($footer, ['image', 'message_image']);
			$newyear = add_domain_deep($newyear, ['music', 'music2', 'bgmusic']);
			$position = ['enable'=>$this->detailPositionEnable, 'type'=>$this->detailPositionType, 'text'=>$this->detailPositionText, 'url'=>$this->detailPositionUrl];
			$posad = ['url'=>$this->detailPosAdUrl, 'image'=>$this->detailPosAdImage, 'message_title'=>$this->customMessageTitle, 'message_path'=>$this->customMessagePath, 'message_image'=>$this->customMessageImg];
			$posad = add_domain_deep($posad, ['image', 'message_image']);
			$addmy = $this->detailAddMyEnable;
			$feedback = $this->detailFeedbackEnable;
			$ad_fixed = $this->miniprogram->review==1?0:intval($this->miniprogram->ad_fixed);
			$ad_fixed_percent = $this->miniprogram->review==1?0:100-intval($this->miniprogram->ad_fixed_percent);
			$mp = ['title'=>$this->miniprogram->mp_title, 'url'=>$this->miniprogram->mp_url, 'image'=>$this->miniprogram->mp_pic];
			$mp = add_domain_deep($mp, ['image']);
			$subscribe_id = $this->miniprogram->subscribe_id;
			$subscribe_img = $this->miniprogram->subscribe_img;
			$subscribe_img = add_domain($subscribe_img);
			$trans = ['title'=>$this->miniprogram->trans_title, 'url'=>$this->miniprogram->trans_url, 'image'=>$this->miniprogram->trans_pic];
			$trans = add_domain_deep($trans, ['image']);
			$comment_hidden = intval($this->miniprogram->comment_hidden);
			$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->detailPositionWxAdEnable:0, 'adunit'=>$this->detailPositionWxAdUnitId];
			success(compact('article', 'content_next', 'banner', 'footer', 'btn', 'tips', 'newyear', 'position', 'posad', 'addmy', 'feedback', 'ad_fixed', 'ad_fixed_percent', 'wxpositionad', 'list', 'comment', 'mp', 'subscribe_id', 'subscribe_img', 'trans', 'comment_hidden'), '成功', 0, ['appId'=>$this->appId]);
		}
		
		$res = SQL::share('admin_article')->where("article_id='{$id}' AND admin_id='{$admin_id}'")->row("return_url, 1 as return_url_status");
		if (!$res) {
			$res = new stdClass();
			$res->return_url = '';
			$res->return_url_status = 1;
		}
		$admin = SQL::share('admin')->where($admin_id)->cached(60*60*24)->row('return_url');
		if ($admin) {
			if (!strlen($res->return_url)) $res->return_url = $admin->return_url;
		}
		if ($res->return_url_status==0) $res->return_url = '';
		$return_url = $res->return_url;
		$oss_domain = OSS_DOMAIN;
		$click_domain = CLICK_DOMAIN;
		success(compact('article', 'return_url', 'content_next', 'oss_domain', 'click_domain'));
	}
	
	//视频详情
	public function video() {
		if ($this->miniprogram->only_pic==1) error('文章不存在');
		$id = $this->request->get('id', 0);
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		if ($id<=0) error('缺少参数');
		$_time = intval(date('H'));
		if ($_time>5 && $_time<=11) $_time = '上午好，';
		else if ($_time>11 && $_time<=14) $_time = '中午好，';
		else if ($_time>14 && $_time<=18) $_time = '下午好，';
		else $_time = '晚上好，';
		
		$video = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')->where("a.id='{$id}' AND a.detail_status=1 AND aa.miniprogram_id='{$this->miniprogram->id}'")
			->row('a.*, aa.clicks, aa.likes');
		if (!$video) error('记录不存在');
		SQL::share('article_attr')->where("article_id='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['clicks'=>['+1']]);
		SQL::share('article')->where($id)->update(['clicks'=>['+1'], 'today_clicks'=>['+1']]);
		if (SQL::share('admin_miniprogram')->where("miniprogram_id='{$this->miniprogram->id}'")->exist()) {
			if (SQL::share('admin_miniprogram_article')->where("miniprogram_id='{$this->miniprogram->id}'")->comparetime('m', 'add_time', '=0')->exist()) {
				SQL::share('admin_miniprogram_article')->where("miniprogram_id='{$this->miniprogram->id}'")->comparetime('m', 'add_time', '=0')->update(['clicks'=>['+1'], 'today_clicks'=>['+1']]);
			} else {
				SQL::share('admin_miniprogram_article')->insert(['miniprogram_id'=>$this->miniprogram->id, 'clicks'=>1, 'today_clicks'=>1, 'add_time'=>strtotime(date('Y-m-1'))]);
			}
		}
		$video->clicks = $video->clicks + 1;
		$title = str_replace('[_city_]', $this->city, $video->title);
		$title = str_replace('[_time_]', $_time, $title);
		$title = str_replace('[_date_]', date('今天是m月d日'), $title);
		$title = str_replace('[_nongli_]', $this->nongli, $title);
		$video->title = $title;
		$video->clicks = $this->_changeNum($video->clicks);
		$video->likes = $this->_changeNum($video->likes);
		$video->info = NULL;
		if (strlen($video->url) && strpos($video->url, 'http://')===false && strpos($video->url, 'https://')===false) {
			$video->info = $this->getInfo(ROOT_PATH."/public{$video->url}");
		}
		$video->url = '%domain%'.$video->url;
		$video = add_domain_deep($video, ['pic', 'url']);
		
		$j = $offset*$pagesize;
		$rand = mt_rand($j, $j+$pagesize);
		$list = [];
		$rs = SQL::share('article a')->left('article_attr aa', 'a.id=aa.article_id')
			->where("a.id!='{$id}' AND status=1 AND aa.miniprogram_id='{$this->miniprogram->id}' AND category_id='{$video->category_id}'")
			->sort('aa.clicks DESC, a.id DESC')->limit($offset, $pagesize)->find('a.id, title, pic, type, aa.likes, aa.clicks, add_time');
		if ($rs) {
			shuffle($rs);
			foreach ($rs as $g) {
				$title = str_replace('[_city_]', $this->city, $g->title);
				$title = str_replace('[_time_]', $_time, $title);
				$title = str_replace('[_date_]', date('今天是m月d日'), $title);
				$title = str_replace('[_nongli_]', $this->nongli, $title);
				$g->title = $title;
				$g->clicks = $this->_changeNum($g->clicks);
				//$g->type = 0; //0视频，1广告，2跳转小程序
				$list[] = $g;
				$j++;
				
				if ($this->detailListAdEnable && $j==$rand) {
					$r = new stdClass();
					$r->type = 1;
					$r->pic = $this->detailListAdImage;
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
		$list = add_domain_deep($list, ['pic', 'url']);
		
		$date = date('Y-m-d');
		$btn = ['enable'=>$this->detailBtnEnable, 'text'=>$this->detailBtnText, 'url'=>$this->detailBtnUrl];
		$wxvideoad = ['enable'=>$this->wxAdEnable==1?$this->detailListWxAdEnable:0, 'adunit'=>$this->detailListWxVideoAdUnitId];
		$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->detailPositionWxAdEnable:0, 'adunit'=>$this->detailPositionWxAdUnitId];
		$addmy = $this->detailAddMyEnable;
		$feedback = $this->detailFeedbackEnable;
		success(compact('video', 'btn', 'addmy', 'feedback', 'wxvideoad', 'wxpositionad', 'list', 'date'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	private function _changeNum($num) {
		if (!is_numeric($num)) return 0;
		if ($num > 10000) return number_format($num/10000, 1, '.', '').'w+';
		if ($num > 1000) return number_format($num/1000, 1, '.', '').'k+';
		return $num;
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
		if ($id<=0) error('文章不存在');
		SQL::share('article')->where($id)->update(['likes'=>['+1']]);
		SQL::share('article_attr')->where("article_id='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['likes'=>['+1']]);
		success('ok');
	}
	
	//获取附加信息
	public function item() {
		$id = $this->request->post('id', 0);
		$admin_id = $this->request->post('owner', 0);
		if ($id<=0 || $admin_id<=0) error('缺少参数');
		$res = SQL::share('admin_article')->where("article_id='{$id}' AND admin_id='{$admin_id}'")->cached(60*60*24)->row("prev, next, url_origin, return_url, share_url, return_url_status, share_url_status, '' as jssdk");
		if (!$res) {
			$res = new stdClass();
			$res->prev = '';
			$res->next = '';
			$res->url_origin = '';
			$res->return_url = '';
			$res->share_url = '';
			$res->return_url_status = 1;
			$res->share_url_status = 1;
			$res->jssdk = '';
		}
		$admin = SQL::share('admin')->where($admin_id)->cached(60*60*24)->row();
		if ($admin) {
			if (!strlen($res->prev)) $res->prev = $admin->prev;
			if (!strlen($res->next)) $res->next = $admin->next;
			if (!strlen($res->url_origin)) $res->url_origin = $admin->url_origin;
			if (!strlen($res->return_url)) $res->return_url = $admin->return_url;
			if (!strlen($res->share_url)) $res->share_url = $admin->share_url;
		}
		if ($res->return_url_status==0) $res->return_url = '';
		if ($res->share_url_status==0) $res->share_url = '';
		if (defined('WX_APPID') && strlen(WX_APPID)) {
			$share_title = defined('SHARE_TITLE') ? SHARE_TITLE : '';
			$share_desc = defined('SHARE_DESC') ? SHARE_DESC : '';
			$share_link = "{$this->domain}/article/{$admin_id}/{$id}";
			$share_img = add_domain_deep(defined('SHARE_IMG') ? SHARE_IMG : '');
			$jssdk = new wechatCallbackAPI();
			$jssdk = $jssdk->getSignPackage();
			$jssdk['share'] = array(
				'title'=>$share_title,
				'desc'=>$share_desc,
				'link'=>$share_link,
				'img'=>$share_img
			);
			$res->jssdk = 'Mario'.base64_encode(json_encode($jssdk));
		}
		success($res);
	}
	
	//点击
	public function click() {
		$id = $this->request->post('id', 0);
		//$callback = $this->request->post('callback');
		if ($id<=0) error('文章不存在');
		$clicks = SQL::share('article')->where($id)->value('clicks');
		$clicks += 1;
		SQL::share('article')->where($id)->update(['clicks'=>['+1']]);
		SQL::share('admin_article')->where("article_id='{$id}'")->update(['clicks'=>['+1'], 'today_clicks'=>['+1']]);
		success($clicks);
	}
	
	public function comment() {
		if (IS_POST) {
			$article_id = $this->request->post('article_id', 0);
			$content = $this->request->post('content');
			if ($article_id<=0 || !strlen($content)) error('缺少参数');
			$count = SQL::share('article_comment')->where("ip='{$this->ip}'")->comparetime('n', 'add_time', '=0')->count();
			if ($count>=2) error('您发表得太快了');
			$miniprogram_id = $this->miniprogram->id;
			$ip = $this->ip;
			$add_time = time();
			$row = SQL::share('article_comment')->returnObj()->insert(compact('miniprogram_id', 'article_id', 'content', 'ip', 'add_time'));
			$row->member_name = '网友';
			$row->member_avatar = '/images/avatar.png';
			$row->add_time = get_time_word($row->add_time);
			$row->type = 0;
			$row = add_domain_deep($row, ['member_avatar']);
			SQL::share('article')->where($article_id)->update(['comments'=>['+1']]);
			success($row);
		}
		success('ok');
	}
	
	public function comment_like() {
		$id = $this->request->post('id', 0);
		if ($id<=0) error('评论不存在');
		SQL::share('article_comment')->where($id)->update(['likes'=>['+1']]);
		success('ok');
	}
	
	//采集
	//curl /api/article/pickArticle
	public function pickArticle() {
		$type = $this->request->get('type', 0);
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		if (ob_get_level() == 0) ob_start();
		ob_implicit_flush(true);
		ob_clean();
		$count = 0;
		switch ($type) {
			case 1:
				exit;
				$json = requestData('post', 'https://agg-api.actuive.com/api/v1/article/list', "cateid=0&offset=0&limit=1", true, false, [
					'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
					'authorization: 0c4b46c0-ab88-4ea3-967e-ccbdd4f65e8c',
					'appid: wx1aafc17e506a4a74'
				]);
				if (intval($json['code'])!=0) error($json['msg'], 0, $json['code']);
				$total = $json['data']['total'];
				//"cateid={$cateid}&offset=".($i*15)."&limit=15"
				$json = requestData('post', 'https://agg-api.actuive.com/api/v1/article/list', "cateid=0&offset=0&limit={$total}", true, false, [
					'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
					'authorization: 0c4b46c0-ab88-4ea3-967e-ccbdd4f65e8c',
					'appid: wx1aafc17e506a4a74'
				]);
				if (intval($json['code'])!=0) error($json['msg'], 0, $json['code']);
				if (is_array($json['data']['list'])) {
					$list = array_reverse($json['data']['list']);
					foreach ($list as $l) {
						if (SQL::share('article')->where("title='".$l['title']."'")->exist()) continue;
						$rs = SQL::share('article')->find('title');
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
						$d = requestData('post', 'https://agg-api.actuive.com/api/v1/article/detail', 'id='.$l['id'], true, false, [
							'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
							'authorization: 0c4b46c0-ab88-4ea3-967e-ccbdd4f65e8c',
							'appid: wx1aafc17e506a4a74'
						]);
						$d = $d['data'];
						$content = $d['content'];
						preg_match_all('/<img .*?src="([^"]+)"/', $content, $matcher);
						if ($matcher) {
							foreach ($matcher[1] as $m) {
								$u = https().$_SERVER['HTTP_HOST'].$this->_getFile(str_replace('https://agg-item.actuive.com//', 'https://agg-item.actuive.com/', $m));
								$content = str_replace($m, $u, $content);
							}
						}
						$pic = str_replace('https://agg-item.actuive.com//', 'https://agg-item.actuive.com/', $d['cover_image'][0]);
						$pic = $this->_getFile($pic);
						$r = [];
						$r['title'] = $d['title'];
						$r['pic'] = $pic;
						$r['content'] = $content;
						$r['sort'] = 999;
						$r['status'] = 1;
						$r['category_id'] = 6;
						$r['recommend'] = 0;
						$r['add_time'] = $d['newstime'];
						$article_id = SQL::share('article')->insert($r);
						$data = [];
						$rs = SQL::share('miniprogram')->where("type='0'")->sort('id ASC')->find('id');
						foreach ($rs as $g) {
							$miniprogram_id = $g->id;
							$data[] = compact('miniprogram_id', 'article_id');
						}
						SQL::share('article_attr')->insert($data);
						$count++;
					}
				}
				break;
			case 2:
				exit;
				for ($i=300; $i<=600; $i++) {
					$json = requestData('post', 'https://cpu.baidu.com/1022/a93ead95/i', "pageNo={$i}&pageSize=16", true);
					if (is_array($json) && isset($json['data']) && is_array($json['data']) && isset($json['data']['result']) && is_array($json['data']['result'])) {
						foreach ($json['data']['result'] as $g) {
							$id = $g['data']['id'];
							$title = $g['data']['title'];
							$type = $g['type'];
							$url = '';
							$content = '';
							if ($type=='video') continue;
							if ($type=='video' && isset($g['data']['thumbUrl'])) {
								$pic = explode('@', 'https:'.$g['data']['thumbUrl']);
							} else if ($type=='news' && isset($g['data']['bigPicUrl'])) {
								$pic = explode('@', 'https:'.$g['data']['bigPicUrl']);
							} else {
								continue;
							}
							if (SQL::share('article2')->where("title='".str_replace("'", "\'", $title)."'")->exist()) continue;
							if ($type=='video') {
								if (isset($g['data']['url'])) $url = $this->_getFile($g['data']['url'], 'video');
							} else {
								$html = requestData('get', "https://cpu.baidu.com/1022/a93ead95/i/detail/{$id}/{$type}?chk=1");
								preg_match_all('/<article class="article container">([\s\S]+)<\/article>/', $html, $matcher);
								if ($matcher) {
									preg_match('/window.APP_STATE=(.+?),"tagList":/', $html, $matcher);
									if (!$matcher) continue;
									preg_match_all('/,"content":"(.+?)","tagList":/', $matcher[0], $matcher);
									if (!$matcher) continue;
									$content = $matcher[1][0];
									$content = str_replace('\u003C', '<', $content);
									$content = str_replace('\u003E', '>', $content);
									$content = str_replace('\u002F', '/', $content);
									$content = str_replace('\"', '"', $content);
									$content = preg_replace('/ width="\d+"/', '', $content);
									$content = preg_replace('/ height="\d+"/', '', $content);
									preg_match_all('/<img .*?src="([^"]+)"/', $content, $matcher);
									if ($matcher) {
										foreach ($matcher[1] as $m) {
											$u = $this->_getFile($m);
											$content = str_replace($m, $u, $content);
										}
									}
								}
							}
							$pic = $pic[0];
							$pic = $this->_getFile($pic);
							$type = $type=='video' ? 1 : 0;
							$add_time = time();
							SQL::share('article2')->insert(compact('title', 'type', 'pic', 'url', 'content', 'add_time'));
							$count++;
						}
					}
				}
				break;
			case 3:
				exit;
				for ($i=1; $i<=1000; $i++) {
					$json = requestData('post', 'https://cpu.baidu.com/1022/a93ead95/i', "pageNo={$i}&pageSize=16", true);
					if (is_array($json) && isset($json['data']) && is_array($json['data']) && isset($json['data']['result']) && is_array($json['data']['result'])) {
						foreach ($json['data']['result'] as $g) {
							$title = $g['data']['title'];
							$type = $g['type'];
							$images = isset($g['data']['images']) ? $g['data']['images'] : '';
							if ($type!='news' || !is_array($images)) continue;
							$row = SQL::share('article2')->where("title='".str_replace("'", "\'", $title)."'")->row('id, pics');
							if (!$row || strlen($row->pics)) continue;
							$pics = [];
							foreach ($images as $image) {
								$pics[] = $this->_getFile('https:'.$image);;
							}
							$pics = implode('|', $pics);
							SQL::share('article2')->where($row->id)->update(compact('pics'));
							$count++;
						}
					}
				}
				break;
			case 4:
				$j = 0;
				$list = [];
				for ($i=1; $i<=9999; $i++) {
					$json = requestData('post', 'http://r.inews.qq.com/getQQNewsUnreadList?mac=020000000000&isJailbreak=0&qn-rid=1003_390D9305-C056-493E-85A3-B9623E4B18F2&device_model=iPhone9%2C2&device_appin=3E05839C-ED23-4659-B77E-8559880ED074&deviceToken=5a3424231a5e46d6537d32df67a06c6181320089360dd082cc12468709531a7e&currentTabId=news_news&qn-time=1589936886862&isMainUserLogin=0&qqnews_refpage=QNLaunchWindowViewController&__qnr=2474ba76a0f2&qn-sig=EA01C8A42BB5B367254377E3F4BA2D61&network_type=wifi&cookie=logintype%3D2&startTimestamp='.time().'&hw=iPhone9%2C2&page_type=timeline&adcode=440112&qimei=995be49c-e1a4-4b4b-994e-74b795a35d35&imsi=460-00&omgbizid=10732a182142684425fa96de8a02eeed7d8e0060115414&chlid=news_news_top&screen_height=736&trueVersion=6.1.21&global_session_id=1589936463492&omgid=58d9e8c2864e5a4630a84cdb26221ba7e93f001011330f&user_vip_type=0&idfa=A9B8BC30-0C84-49FE-8CC4-AD930D03650D&qn-newsig=a001083b5cd135c6e6740cf5b074f0da175cc9def1d4a053ee0a9846579c05d4&currentChannelId=news_news_top&global_info=1%7C1%7C1%7C1%7C1%7C13%7C7%7C1%7C0%7C6%7C1%7C1%7C1%7C%7C0%7CJ060P700000000%3AJ060P600000000%3AJ060P400000000%3AJ060P300000000%3AJ060P200000000%3AJ060P100000000%3AJ060P090000000%3AJ060P080000000%3AJ060P050000000%3AJ060P040000000%3AJ060P030000000%3AJ060P020000000%3AJ060P010000000%3AJ060P000000000%3AA060P099302204%3AA060P016085901%3AB403P700386302%3AJ403P600000000%3AA403P300394102%3AJ403P200000000%3AJ403P100000000%3AJ403P020000000%3AJ403P010000000%3AB403P000373905%3AA403P602218702%3AA402P300401401%3AJ402P100000000%3AJ402P070000000%3AJ402P010000000%3AA402P000387602%3AJ404P200000000%3AJ404P010000000%3AB404P000263408%3AA404P002308201%3AB267P300384604%3AJ267P200000000%3AJ267P100000000%3AJ267P090000000%3AJ267P020000000%3AJ267P010000000%3AB267P000388908%3AJ401P100000000%3AA401P000050901%3AJ701P100000000%3AJ701P000000000%3AJ064P300000000%3AB064P000389008%3AA310P100357501%3AJ310P030000000%3AB310P020395107%3AA310P010384503%3AJ310P000000000%3AB703P100402402%3AJ703P000000000%3AA702P400393001%3AJ702P100000000%3AJ702P000000000%3AJ054P400000000%3AJ054P200000000%3AJ054P090000000%3AJ054P070000000%3AA054P000402101%3AA406P000313201%3AJ405P200000000%3AA405P100380601%3AB405P000374504%3AJ055P400000000%3AJ055P070000000%3AJ055P000000000%3AJ065P400000000%3AJ065P000000000%3AA601P900346303%3AB601P800347703%3AA601P700227201%3AJ601P600000000%3AB601P500154502%3AB601P400389904%3AJ601P300000000%3AB601P200096103%3AA601P100397902%3AB601P000184704%3AJ601P903000000%3AA601P902266601%3AA601P815363101%3AJ601P813000000%3AJ601P812000000%3AJ601P811000000%3AJ601P704000000%3AJ601P623000000%3AA601P622269601%3AA601P620269601%3AJ601P111000000%3AJ601P110000000%3AA601P105118803%3AA601P019237403%3AA601P016212405%3AJ601P006000000%3AA066P400392701%3AJ066P000000000%3AJ069P400000000%3AA069P000358001%3AB074P200238202%3AB602P900246403%3AJ602P800000000%3AJ602P700000000%3AJ602P600000000%3AJ602P500000000%3AJ602P400000000%3AJ602P300000000%3AB602P200284703%3AJ602P100000000%3AJ602P000000000%3AA602P901257901%3AA602P702269101%3AA602P613271701%3AA602P611253801%3AA602P516234601%3AA602P414259901%3AJ602P302000000%3AA602P208205801%3AA602P117262101%3AJ602P007000000%3AA602P003136401%3AB085P000087702%7C1402%7C0%7C1%7C26%7C26%7C0%7C0%7C0%7C1132%3A1001132%3A1043%3A1001043%3A1090%3A1001090%3A1115%3A1001115%3A932%3A1001122%7C3%7C3%7C1%7C1%7C1%7C1%7C1%7C1%7C-1%7C0%7C0%7C0%7C2%7C2%7C1%7C0%7C3%7C0%7C0%7C1%7C0%7C0%7C0%7C0%7C0%7C0%7C0%7C2%7C1%7C0%7C1%7C1%7C1%7C0%7C1%7C0%7C4%7C0%7C0%7C0%7C11%7C20%7C1%7C0%7C1%7C1%7C0%7C0%7C1%7C4%7C0%7C1%7C1%7C41%7C0%7C51%7C60%7C0%7C1%7C0%7C0%7C1%7C0%7C0%7C0%7C0%7C71%7C81%7C0%7C1%7C71&preStartTimestamp='.(time()-342).'&screen_scale=3&pagestartfrom=icon&appver=13.4.1_qqnews_6.1.21&rtAd=1&store=1&screen_width=414&devid=04117E46-E544-4742-A7AD-B174A81BFC73&activefrom=icon&apptype=ios&httpRequestUid=2474ba7611a7', "page={$i}", true);
					if ( !isset($json['newslist']) || !is_array($json['newslist']) || !count($json['newslist']) ) break;
					foreach ($json['newslist'] as $g) {
						if (is_numeric($g['chlid'])) {
							$title = $g['title'];
							if ( in_array($title, $list) ) continue;
							if ( SQL::share('article4')->where("title='".str_replace("'", "\'", $title)."'")->exist() ) continue;
							$url = str_replace('?uid=#', '', $g['url']);
							$pic = (is_array($g['bigImage']) && count($g['bigImage']) && strlen($g['bigImage'][0])) ? $g['bigImage'][0] : '';
							if (!strlen($pic)) $pic = (is_array($g['thumbnails_big']) && count($g['thumbnails_big']) && strlen($g['thumbnails_big'][0])) ? $g['thumbnails_big'][0] : '';
							if (!strlen($pic)) $pic = (is_array($g['thumbnails']) && count($g['thumbnails']) && strlen($g['thumbnails'][0])) ? $g['thumbnails'][0] : '';
							$add_time = strtotime($g['time']);
							$content = file_get_contents($url);
							preg_match('/<script>window\.__initData = (.+?);<\/script>/', $content, $matcher);
							if (is_array($matcher) && count($matcher)) {
								$arr = json_decode($matcher[1], true);
								if ( !isset($arr['content']) || !isset($arr['content']['kb_ext']) || !isset($arr['content']['kb_ext']['tkd_cnt_html']) ) continue;
								$content = $arr['content']['kb_ext']['tkd_cnt_html'];
								preg_match_all('/<!--(IMG_\d+)-->/', $content, $matcher);
								if (is_array($matcher) && count($matcher)) {
									if ( !isset($arr['content']['kb_ext']['tkd_cnt_attr']) ) continue;
									foreach ($matcher[1] as $m) {
										if ( isset($arr['content']['kb_ext']['tkd_cnt_attr'][$m]) ) {
											$img = $arr['content']['kb_ext']['tkd_cnt_attr'][$m]['srcurl'];
											if ( isset($arr['content']['kb_ext']['tkd_cnt_attr'][$m]['img']['imgurl640']) ) $img = $arr['content']['kb_ext']['tkd_cnt_attr'][$m]['img']['imgurl640']['imgurl'];
											$img = $this->_getFile($img);
											$content = str_replace("<!--{$m}-->", '<img src="'.$img.'" />', $content);
										}
									}
								}
							} else {
								continue;
							}
							$list[] = $title;
							$pic = $this->_getFile($pic);
							$content = str_replace('<P>', '<p>', $content);
							$content = str_replace('</P>', '</p>', $content);
							SQL::share('article4')->insert(compact('title', 'pic', 'url', 'content', 'add_time'));
							$count++;
						}
					}
					$j++;
					if ($j >= 100) break;
				}
				break;
			case 5:
				$k = 0;
				$list = [];
				for ($i=1; $i<=9999; $i++) {
					$k++;
					$time = time();
					$res = file_get_contents("http://weatherapi.ifjing.com/api/ucnew/proxy/channel/100?chl=otSy9Zonl%2Fk5CTkt33reOQ%3D%3D&dm=iPhone9%2C2&fromId=0&idfa=A9B8BC30-0C84-49FE-8CC4-AD930D03650D&imei=CF50BA25-3077-4469-A07B-D27873E8AC61&mt=1&nt=10&operator=00000&osv=13.4.1&pid=115&rslt=1242x2208&spid=1&sv=5.3.0&ts={$time}&userid=CF50BA25-3077-4469-A07B-D27873E8AC61&wxMini=1");
					$json = json_decode($res, true);
					if ( !isset($json['data']) || !isset($json['data']['articles']) || !$json['data']['articles'] ) {
						sleep(3);
						continue;
					}
					$articles = array_values($json['data']['articles']);
					foreach ($articles as $g) {
						$title = $g['title'];
						if ( in_array($title, $list) ) continue;
						if ( SQL::share('article5')->where("title='".str_replace("'", "\'", $title)."'")->exist() ) continue;
						$pic = $g['thumbnails'][0]['url'];
						$add_time = $g['publish_time']/1000;
						$content = requestData('get', $g['url']);
						if (!strlen($content) || !$content) continue;
						preg_match('/var xissJsonData = (.+?});/', $content, $matcher);
						if (is_array($matcher) && count($matcher)) {
							$arr = json_decode($matcher[1], true);
							if ( !isset($arr['content']) ) continue;
							$content = $arr['content'];
							preg_match_all('/<!--{img:(\d+)}-->/', $content, $matcher);
							if (is_array($matcher) && count($matcher)) {
								if ( !isset($arr['images']) ) continue;
								foreach ($matcher[1] as $m) {
									if ( isset($arr['images'][intval($m)]) ) {
										$img = str_replace('?id=0&from=export', '', $arr['images'][intval($m)]['url']);
										$img = $this->_getFile($img);
										$content = str_replace("<!--{img:{$m}}-->", '<img src="'.$img.'" />', $content);
									}
								}
							}
						} else {
							continue;
						}
						$list[] = $title;
						$pic = $this->_getFile($pic);
						SQL::share('article5')->insert(compact('title', 'pic', 'content', 'add_time'));
						$k = 0;
						$count++;
					}
					if ($k > 3) break; //连续3页没有新数据
					if ($count >= 600) break;
				}
				break;
			default:
				exit;
				$categories = [2020, 1501, 1537, 1502, 1732, 1419];
				foreach ($categories as $cateid) {
					$json = requestData('post', 'https://agg-api.actuive.com/api/v1/article/list', "cateid={$cateid}&offset=0&limit=1", true, false, [
						'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
						'authorization: 2d8be68e-cfda-48df-b555-9b2523c77485',
						'appid: wxdff55d3091bfabad'
					]);
					if (intval($json['code'])!=0) error($json['msg'], 0, $json['code']);
					$total = $json['data']['total'];
					//"cateid={$cateid}&offset=".($i*15)."&limit=15"
					$json = requestData('post', 'https://agg-api.actuive.com/api/v1/article/list', "cateid={$cateid}&offset=0&limit={$total}", true, false, [
						'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
						'authorization: 2d8be68e-cfda-48df-b555-9b2523c77485',
						'appid: wxdff55d3091bfabad'
					]);
					if (intval($json['code'])!=0) error($json['msg'], 0, $json['code']);
					if (is_array($json['data']['list'])) {
						$list = array_reverse($json['data']['list']);
						foreach ($list as $l) {
							if (SQL::share('article')->where("title='".$l['title']."'")->exist()) continue;
							$rs = SQL::share('article')->find('title');
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
							$d = requestData('post', 'https://agg-api.actuive.com/api/v1/article/detail', 'id='.$l['id'], true, false, [
								'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
								'authorization: 2d8be68e-cfda-48df-b555-9b2523c77485',
								'appid: wxdff55d3091bfabad'
							]);
							$d = $d['data'];
							$content = $d['content'];
							preg_match_all('/<img .*?src="([^"]+)"/', $content, $matcher);
							if ($matcher) {
								foreach ($matcher[1] as $m) {
									$u = $this->_getFile(str_replace('https://agg-item.actuive.com//', 'https://agg-item.actuive.com/', $m));
									$content = str_replace($m, $u, $content);
								}
							}
							$pic = str_replace('https://agg-item.actuive.com//', 'https://agg-item.actuive.com/', $d['cover_image'][0]);
							$pic = $this->_getFile($pic);
							$r = [];
							$r['title'] = $d['title'];
							$r['pic'] = $pic;
							$r['content'] = $content;
							$r['sort'] = 999;
							$r['status'] = 1;
							switch ($cateid) {
								case 1419:$r['category_id'] = 1;break;
								case 1732:$r['category_id'] = 2;break;
								case 2020:$r['category_id'] = 3;break;
								case 1501:$r['category_id'] = 4;break;
								case 1537:
								case 1502:$r['category_id'] = 5;break;
							}
							$r['recommend'] = $cateid==1732 ? 1 : 0;
							$r['add_time'] = $d['newstime'];
							$article_id = SQL::share('article')->insert($r);
							$rs = SQL::share('miniprogram')->where("type='0'")->sort('id ASC')->find('id');
							foreach ($rs as $g) {
								$miniprogram_id = $g->id;
								$data[] = compact('miniprogram_id', 'article_id');
							}
							SQL::share('article_attr')->insert(compact('miniprogram_id', 'article_id'));
							$count++;
						}
					}
				}
				break;
		}
		ob_flush();
		flush();
		ob_end_flush();
		write_log("GET ARTICLES COMPLETE, QUANTITY {$count}", '/temp/article.txt');
		success("GET ARTICLES COMPLETE, QUANTITY {$count}");
	}
	
	private function _getFile($url, $type='') {
		global $upload_type;
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$suffix = '';
		$timeout = 60*60;
		switch ($type) {
			case 'video':
				$url = explode('.mp4', $url);
				$url = $url[0].'.mp4';
				$suffix = 'mp4';
				break;
			default:
				if (strpos($url, 'wx_fmt=')!==false) $suffix = substr($url, strrpos($url, 'wx_fmt=')+7);
				if (!strlen($suffix) && preg_match('/\bfmt=\w+\b/', $url)) {
					preg_match('/\bfmt=(\w+)\b/', $url, $matcher);
					$suffix = $matcher[1];
				}
				if (!strlen($suffix)) $suffix = substr($url, strrpos($url, '.')+1);
				if (!preg_match('/^(jpe?g|png|gif|bmp)$/', $suffix)) $suffix = 'jpg';
				if ($suffix=='jpeg') $suffix = 'jpg';
				//$timeout = (preg_match('/^(jpe?g|png)$/', $suffix) ? 5 : 60*5);
				break;
		}
		$ch = curl_init();
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
		if ($type=='video') {
			$name = generate_sn();
			$dir = UPLOAD_PATH.'/video/'.date('Y').'/'.date('m').'/'.date('d');
			$upload = p('upload', $upload_type);
			$result = $upload->upload($content, NULL, str_replace('/public/', '/', $dir), $name, $suffix);
			$file = $result['file'];
		} else {
			$file = upload_obj_file($content, 'article', NULL, 1, false, ['jpg', 'jpeg', 'png', 'gif', 'bmp'], ".{$suffix}");
		}
		$file = add_domain($file);
		return $file;
		/*$filename = generate_sn().'.'.$suffix;
		$dir = UPLOAD_PATH.'/article/'.date('Y').'/'.date('m').'/'.date('d');
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
