<?php
#//佛学文章
class buddhaaudio extends core {
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
	
	private $appId;
	private $ver;
	private $version;
	private $versionNum;
	private $miniprogram;

	public function __construct() {
		parent::__construct();
		if ($this->act != 'pickBuddha') {
			$this->appId = $this->request->get('appId');
			if (!strlen($this->appId) && isset($this->headers['Appid'])) $this->appId = $this->headers['Appid'];
			$this->ver = $this->request->get('ver', 0);
			$this->version = $this->request->get('version');
			$this->versionNum = strlen($this->version) ? (is_numeric(str_replace('.', '', $this->version)) ? intval(str_replace('.', '', $this->version)) : $this->version) : 0;
			$configs = $this->configs;
			if (!strlen($this->appId)) error('缺失AppID');
			$this->miniprogram = SQL::share('miniprogram')->where("appid='{$this->appId}'")->row();
			if (!$this->miniprogram) error('小程序数据错误');
			
			if (!SQL::share('miniprogram_config')->where("miniprogram_id='{$this->miniprogram->id}'")->exist()) {
				$rs = SQL::share('config')->where("name LIKE 'G_BUDDHAAUDIO/_%' ESCAPE '/'")->find('id, content');
				if ($rs) {
					$data = array();
					foreach ($rs as $row) {
						$data[] = array('miniprogram_id'=>$this->miniprogram->id, 'config_id'=>$row->id, 'content'=>$row->content);
					}
					SQL::share('miniprogram_config')->insert($data);
				}
			}
			$rs = SQL::share('config c')->left('miniprogram_config mc', 'mc.config_id=c.id')->where("c.name LIKE 'G_BUDDHAAUDIO/_%' ESCAPE '/' AND mc.miniprogram_id='{$this->miniprogram->id}'")->find('c.name, mc.content');
			if ($rs) {
				$configs = array();
				foreach ($rs as $row) {
					$configs[$row->name] = $row->content;
				}
			}
			
			$this->indexListAdEnable = $this->request->act('G_BUDDHAAUDIO_INDEX_LIST_AD_ENABLE', 0, '', $configs);
			$this->indexListAdImage = $this->request->act('G_BUDDHAAUDIO_INDEX_LIST_AD_IMAGE', '', '', $configs);
			$this->indexListAdUrl = $this->request->act('G_BUDDHAAUDIO_INDEX_LIST_AD_URL', '', '', $configs);
			$this->indexAddMyEnable = $this->request->act('G_BUDDHAAUDIO_INDEX_ADDMY_ENABLE', 0, '', $configs);
			$this->indexAddMyBgColor = $this->request->act('G_BUDDHAAUDIO_INDEX_ADDMY_BGCOLOR', '', '', $configs);
			$this->detailTopAdEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_TOP_AD_ENABLE', 0, '', $configs);
			$this->detailTopAdType = $this->request->act('G_BUDDHAAUDIO_DETAIL_TOP_AD_TYPE', 0, '', $configs);
			$this->detailTopAdImage = $this->request->act('G_BUDDHAAUDIO_DETAIL_TOP_AD_IMAGE', '', '', $configs);
			$this->detailTopAdUrl = $this->request->act('G_BUDDHAAUDIO_DETAIL_TOP_AD_URL', '', '', $configs);
			$this->detailTopAdUnitId = $this->request->act('G_BUDDHAAUDIO_DETAIL_TOP_AD_UNITID', '', '', $configs);
			$this->detailBottomAdEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_BOTTOM_AD_ENABLE', 0, '', $configs);
			$this->detailBottomAdType = $this->request->act('G_BUDDHAAUDIO_DETAIL_BOTTOM_AD_TYPE', 0, '', $configs);
			$this->detailBottomAdImage = $this->request->act('G_BUDDHAAUDIO_DETAIL_BOTTOM_AD_IMAGE', '', '', $configs);
			$this->detailBottomAdUrl = $this->request->act('G_BUDDHAAUDIO_DETAIL_BOTTOM_AD_URL', '', '', $configs);
			$this->detailBottomAdUnitId = $this->request->act('G_BUDDHAAUDIO_DETAIL_BOTTOM_AD_UNITID', '', '', $configs);
			$this->detailBtnEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_BTN_ENABLE', 0, '', $configs);
			$this->detailBtnText = $this->request->act('G_BUDDHAAUDIO_DETAIL_BTN_TEXT', '', '', $configs);
			$this->detailBtnUrl = $this->request->act('G_BUDDHAAUDIO_DETAIL_BTN_URL', '', '', $configs);
			$this->detailListAdEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_LIST_AD_ENABLE', 0, '', $configs);
			$this->detailListAdImage = $this->request->act('G_BUDDHAAUDIO_DETAIL_LIST_AD_IMAGE', '', '', $configs);
			$this->detailListAdUrl = $this->request->act('G_BUDDHAAUDIO_DETAIL_LIST_AD_URL', '', '', $configs);
			$this->returnEnable = $this->request->act('G_BUDDHAAUDIO_RETURN_ENABLE', 0, '', $configs);
			$this->returnEverybody = $this->request->act('G_BUDDHAAUDIO_RETURN_EVERYBODY', 0, '', $configs);
			$this->returnUrls = $this->request->act('G_BUDDHAAUDIO_RETURN_URLS', '', '', $configs);
			$this->detailTipsEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_TIPS_ENABLE', 0, '', $configs);
			$this->detailTipsText = $this->request->act('G_BUDDHAAUDIO_DETAIL_TIPS_TEXT', '', '', $configs);
			$this->detailTipsUrl = $this->request->act('G_BUDDHAAUDIO_DETAIL_TIPS_URL', '', '', $configs);
			$this->detailTipsPosition = $this->request->act('G_BUDDHAAUDIO_DETAIL_TIPS_POSITION_ENABLE', 0, '', $configs);
			$this->detailTipsRedirect = $this->request->act('G_BUDDHAAUDIO_DETAIL_TIPS_REDIRECT', '', '', $configs);
			$this->detailNewYearEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_NEW_YEAR_ENABLE', 0, '', $configs);
			$this->detailNewYearMusic = $this->request->act('G_BUDDHAAUDIO_DETAIL_NEW_YEAR_MUSIC', '', '', $configs);
			$this->detailNewYearMusic2 = $this->request->act('G_BUDDHAAUDIO_DETAIL_NEW_YEAR_MUSIC2', '', '', $configs);
			$this->detailPositionEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_POSITION_ENABLE', 0, '', $configs);
			$this->detailPositionType = $this->request->act('G_BUDDHAAUDIO_DETAIL_POSITION_TYPE', 0, '', $configs);
			$this->detailPositionText = $this->request->act('G_BUDDHAAUDIO_DETAIL_POSITION_TEXT', '', '', $configs);
			$this->detailPositionUrl = $this->request->act('G_BUDDHAAUDIO_DETAIL_POSITION_URL', '', '', $configs);
			$this->detailPosAdUrl = $this->request->act('G_BUDDHAAUDIO_DETAIL_POSAD_URL', '', '', $configs);
			$this->detailPosAdImage = $this->request->act('G_BUDDHAAUDIO_DETAIL_POSAD_IMAGE', '', '', $configs);
			$this->detailAddMyEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_ADDMY_ENABLE', 0, '', $configs);
			$this->detailFeedbackEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_FEEDBACK', 0, '', $configs);
			$this->wxAdEnable = $this->request->act('G_BUDDHAAUDIO_WX_AD_ENABLE', 0, '', $configs);
			$this->indexListWxAdEnable = $this->request->act('G_BUDDHAAUDIO_INDEX_LIST_WX_AD_ENABLE', 0, '', $configs);
			$this->indexListWxAdUnitId = $this->request->act('G_BUDDHAAUDIO_INDEX_LIST_WX_AD_UNITID', '', '', $configs);
			$this->indexListWxVideoAdUnitId = $this->request->act('G_BUDDHAAUDIO_INDEX_LIST_WX_VIDEO_AD_UNITID', '', '', $configs);
			$this->detailListWxAdEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_LIST_WX_AD_ENABLE', 0, '', $configs);
			$this->detailListWxAdUnitId = $this->request->act('G_BUDDHAAUDIO_DETAIL_LIST_WX_AD_UNITID', '', '', $configs);
			$this->detailListWxVideoAdUnitId = $this->request->act('G_BUDDHAAUDIO_DETAIL_LIST_WX_VIDEO_AD_UNITID', '', '', $configs);
			$this->detailPositionWxAdEnable = $this->request->act('G_BUDDHAAUDIO_DETAIL_POSITION_WX_AD_ENABLE', 0, '', $configs);
			$this->detailPositionWxAdUnitId = $this->request->act('G_BUDDHAAUDIO_DETAIL_POSITION_WX_AD_UNITID', '', '', $configs);
			$this->indexPositionWxAdEnable = $this->request->act('G_BUDDHAAUDIO_INDEX_POSITION_WX_AD_ENABLE', 0, '', $configs);
			$this->indexPositionWxAdUnitId = $this->request->act('G_BUDDHAAUDIO_INDEX_POSITION_WX_AD_UNITID', '', '', $configs);
			$this->customMessageTitle = $this->request->act('G_BUDDHAAUDIO_CUSTOM_MESSAGE_TITLE', '', '', $configs);
			$this->customMessagePath = $this->request->act('G_BUDDHAAUDIO_CUSTOM_MESSAGE_PATH', '', '', $configs);
			$this->customMessageImg = $this->request->act('G_BUDDHAAUDIO_CUSTOM_MESSAGE_IMG', '', '', $configs);
			
			if ($this->miniprogram->review==1) {
				//$this->miniprogram->id = 6;
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
	
	//分类
	public function categories() {
		header('Access-Control-Allow-Origin: servicewechat.com');
		header('Access-Control-Allow-Methods: GET');
		$categories = SQL::share('buddhaaudio_category')->where("status=1")->sort('sort ASC')->cached(60*5)->find();
		if ($categories) {
			foreach ($categories as $g) {
				if ($this->miniprogram->review==1) {
					$g->name = $g->name2;
					$g->pic = $g->pic2;
				}
			}
		}
		$categories = add_domain_deep($categories, ['pic']);
		//$addmy = $this->indexAddMyEnable;
		$first_share_count = 2; //0为不需要分享
		$mod_share_count = 10; //0为不需要间隔分享
		$addmy = 0;
		$audio = '/uploads/pic/2020/05/29/20052915472464239.mp3';
		if ($this->miniprogram->review==1) {
			$bgimg = '/uploads/pic/2020/06/10/20061014323568194.jpg';
			$bg = '/uploads/pic/2020/06/10/20061014355156314.jpg';
		} else {
			$bgimg = '/uploads/pic/2020/06/01/20060109170649115.jpg';
			$bg = '/uploads/pic/2020/05/29/20052914281591358.jpg';
		}
		$font = '/images/font.ttf';
		$share = '/uploads/pic/2020/06/15/20061509595794017.png';
		$play_percent = 0; //播放弹广告进度，单位%
		$check_share_group = 0; //检测是否已分享群
		success(compact('categories', 'addmy', 'audio', 'bgimg', 'bg', 'font', 'share', 'play_percent', 'check_share_group', 'first_share_count', 'mod_share_count'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	//分类
	public function category() {
		$id = $this->request->get('id', 0);
		$category = SQL::share('buddhaaudio_category')->where($id)->cached(60*5)->row();
		if ($category) {
			if ($this->miniprogram->review==1) {
				$category->name = $category->name2;
				$category->pic = $category->pic2;
			}
		}
		success(compact('category'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	//文章列表
	public function index() {
		$where = "a.status=1 AND aa.miniprogram_id='{$this->miniprogram->id}'";
		$category_id = $this->request->get('category_id', 0);
		$keyword = $this->request->get('keyword');
		$categories = $this->request->get('categories');
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		//$sort = 'aa.clicks DESC, a.sort ASC, a.id DESC';
		$sort = 'a.sort ASC, a.id ASC';
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
				$sort = 'a.id DESC';
			} else if ($category_id==-2) { //热文
				$where .= " AND a.recommend=1";
			} else if ($category_id==-3) { //精选
				$where .= " AND a.featured=1";
			}
		}
		$table = 'buddhaaudio';
		$table_attr = 'buddhaaudio_attr';
		$table_id = 'buddhaaudio_id';
//		if ($this->miniprogram->review==1) {
//			$table = 'article';
//			$table_attr = 'article_attr';
//			$table_id = 'article_id';
//			$where .= " AND a.id IN (".$this->configs['G_REVIEW_ARTICLE_SHOWN'].")";
//			$where .= " AND a.type=0";
//		} else if ($this->ver > 2) {
			$where .= " AND a.type=0 AND a.music_enable=1";
//		}
		if ($this->miniprogram->review==1) {
			$where .= " AND a.id BETWEEN 72 AND 80";
		} else {
			$where .= " AND a.id NOT IN (72,73,74,75,76,77,78,79,80)";
		}
		
		$j = $offset*$pagesize;
		$rand = mt_rand($j, $j+$pagesize);
		$styleIndex = 0;
		$list = [];
		$rs = SQL::share("{$table} a")->left("{$table_attr} aa", "a.id=aa.{$table_id}")
			->where($where)->sort($sort)->limit($offset, $pagesize)->cached(60*5)
			->find("a.id, a.title, a.pic, a.type, aa.clicks, aa.likes, a.add_time");
		if ($rs) {
			if ($category_id==0) {
				shuffle($rs);
			}
			foreach ($rs as $g) {
				$g->clicks = $this->_changeNum($g->clicks);
				$g->likes = $this->_changeNum($g->likes);
				$g->add_time = date('m-d', $g->add_time);
				//$g->type = 0; //0图文，1自定义广告，2跳转小程序，3微信广告，4微信视频广告，5视频
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
		$list = add_domain_deep($list, ['pic']);
		
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
//
//		$cate2 = new stdClass();
//		$cate2->id = -2;
//		$cate2->name = '热文';
//
//		$cate3 = new stdClass();
//		$cate3->id = -3;
//		$cate3->name = '精选';
		
		$categories = array();
		if ($this->miniprogram->review==0) {
			$cates = SQL::share('buddhaaudio_category')->where("status=1")->sort('sort ASC, id ASC')->find('id, name');
			foreach ($cates as $g) {
				$categories[] = $g;
			}
		}
		//array_unshift($categories, $cate0, $cate1, $cate2, $cate3);
		array_unshift($categories, $cate0, $cate1);
		if ($this->miniprogram->review==0 && ($this->version=='develop' || $this->version=='trial')) {
//			$cate4 = new stdClass();
//			$cate4->id = '/pages/index/qians';
//			$cate4->name = '求签';
//			$categories[] = $cate4;
		}
		
		$review = intval($this->miniprogram->review);
		$bg = '/uploads/buddha/2020/05/28/20052814054358557.jpg';
		$data = compact('res', 'addmy', 'addmybgcolor', 'flashes', 'categories', 'list', 'wxpositionad', 'review', 'bg');
		if ($this->ver > 2) {
			$count = SQL::share("{$table} a")->left("{$table_attr} aa", "a.id=aa.{$table_id}")->where($where)->count();
			$clicks = SQL::share("{$table} a")->left("{$table_attr} aa", "a.id=aa.{$table_id}")->where($where)->sum('aa.clicks');
			$trans = ['title'=>$this->miniprogram->trans_title, 'url'=>$this->miniprogram->trans_url, 'image'=>$this->miniprogram->trans_pic];
			$trans = add_domain_deep($trans, ['image']);
			$data['count'] = $count;
			$data['clicks'] = $this->_changeNum($clicks);
			$data['trans'] = $trans;
		}
		success($data, '成功', 0, ['appId'=>$this->appId]);
	}
	
	//轮播图
	private function _flash() {
		$where = "a.status=1 AND a.type=0 AND aa.miniprogram_id='{$this->miniprogram->id}'";
		$table = 'buddhaaudio';
		$table_attr = 'buddhaaudio_attr';
		$table_id = 'buddhaaudio_id';
		if ($this->miniprogram->review==1) {
//			$where .= " AND a.id IN (".$this->configs['G_REVIEW_ARTICLE_SHOWN'].")";
//			$table = 'article';
//			$table_attr = 'article_attr';
//			$table_id = 'article_id';
		}
		$flashes = SQL::share("{$table} a")->left("{$table_attr} aa", "a.id=aa.{$table_id}")
			->where($where)->sort('RAND()')->pagesize(3)->find("a.id, a.title, a.pic, 'detail' as ad_type, a.id as ad_content");
		//$flashes = SQL::share()->query("SELECT t1.id, t1.title, t1.pic FROM __ARTICLE__ t1 JOIN (SELECT ROUND( RAND()*((SELECT MAX(id) FROM __ARTICLE__)-(SELECT MIN(id) FROM __ARTICLE__)) + (SELECT MIN(id) FROM __ARTICLE__) ) AS id) t2 WHERE t1.id>=t2.id ORDER BY t1.id LIMIT 3");
		$flashes = add_domain_deep($flashes, ['pic']);
		return $flashes;
	}
	
	public function buddhaList() {
		$where = " AND aa.miniprogram_id='{$this->miniprogram->id}'";
		$category_id = $this->request->get('category_id', 0);
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
		$styleIndex = 0;
		$list = [];
		$rs = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')
			->where("status=1 {$where}")->sort('sort ASC, a.id ASC')->limit($offset, $pagesize)// AND more=1
			->find("a.id, a.title, a.pic, a.type, a.content, aa.clicks, aa.likes, add_time, 0 as style, '' as pic2, '' as pic3");
		if ($rs) {
			foreach ($rs as $g) {
				$g->clicks = $this->_changeNum($g->clicks);
				$g->likes = $this->_changeNum($g->likes);
				$g->add_time = date('m-d', $g->add_time);
				$g->style = $styleIndex%3 ? 1 : 0;
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
		
		$list = add_domain_deep($list, ['pic']);
		$res = $this->_returnUrls();
		success(compact('res', 'list'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	#//文章详情
	public function detail() {
		$id = $this->request->get('id');
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		$content_offset = $this->request->get('content_offset', 0); //获取其余部分详情内容
		if (!strlen($id)) error('缺少参数');
		$table = 'buddhaaudio';
		$table_attr = 'buddhaaudio_attr';
		$table_id = 'buddhaaudio_id';
		if ($this->miniprogram->review==1) {
//			$table = 'article';
//			$table_attr = 'article_attr';
//			$table_id = 'article_id';
		}
		$buddha = SQL::share("{$table} a")
			->left("{$table_attr} aa", "a.id=aa.{$table_id}")
			->where("a.id='{$id}' AND aa.miniprogram_id='{$this->miniprogram->id}'")
			->cached(60*5)
			->row('a.*, aa.clicks, aa.likes');
		if (!$buddha) error('记录不存在');
		$content = stripslashes($buddha->content);
		$content = preg_replace('/(width|height):\s*\d+px;?/', '', $content);
		$content = preg_replace('/\s(width|height)="\d+"/', '', $content);
		$content = preg_replace_callback('/<img([^>]+)>/', function($matcher) {
			if (preg_match('/style="([^"]*)"/', $matcher[1])) {
				$attr = preg_replace('/style="([^"]*)"/', 'style="$1;max-width:100%;height:auto;display:block;vertical-align:bottom;"', $matcher[1]);
			} else {
				$attr = ' style="max-width:100%;height:auto;display:block;vertical-align:bottom;"'.$matcher[1];
			}
			return "<img{$attr}>";
		}, $content);
		$content_next = 0;
		$contents = explode('<div style="page-break-after: always"><span style="display: none;">&nbsp;</span></div>', $content);
		if ($content_offset>count($contents)-1) {
			$buddha = new stdClass();
			$buddha->content = '';
			success(compact('buddha', 'content_next'));
		}
		$content = add_domain_deep($contents[$content_offset]);
		if ($content_offset<count($contents)-1) $content_next = 1;
		if ($content_offset>0) {
			$buddha = new stdClass();
			$buddha->content = $content;
			success(compact('buddha', 'content_next'));
		}
		SQL::share($table_attr)->where("{$table_id}='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['clicks'=>['+1']]);
		SQL::share($table)->where($id)->update(['clicks'=>['+1'], 'today_clicks'=>['+1']]);
		$buddha->clicks = $buddha->clicks + 1;
		$buddha->content = $content;
		$buddha->clicks = $this->_changeNum($buddha->clicks);
		$buddha->likes = $this->_changeNum($buddha->likes);
		$buddha->add_time = date("m/d H:i", $buddha->add_time);
		$buddha = add_domain_deep($buddha, ['pic']);
		
		$j = 0;
		$page = 5;
		$rand = mt_rand($j, $j+$page);
		$list = [];
		$where = "aa.miniprogram_id='{$this->miniprogram->id}' AND status=1 AND category_id='{$buddha->category_id}'";
		if ($this->miniprogram->review==1) {
			$where .= " AND a.id BETWEEN 72 AND 80";
		} else {
			$where .= " AND a.id NOT IN (72,73,74,75,76,77,78,79,80)";
		}
		if ($this->ver > 2) {
			$sort = 'a.id ASC';
			$w = $where . " AND a.id>'{$id}' AND a.type=0 AND a.music_enable=1";
			$count = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')->where($w)->sort('a.id DESC')->count();
			if ($offset==0) {
				$offset = intval($count/$pagesize)  * $pagesize;
			} else {
				$offset = intval($count/$pagesize)  * $pagesize + $offset;
			}
			$where .= " AND a.type=0 AND a.music_enable=1";
			$rs = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')
				->where($where)->sort($sort)->limit($offset, $pagesize)->find('a.id, title, pic, type, aa.likes, aa.clicks, add_time');
		} else {
			$sort = 'a.id ASC';
			$where .= " AND a.id!='{$id}'";
			$where .= " AND a.id!='{$id}' AND a.type=0";
			$rs = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')
				->where($where)->sort($sort)->limit($offset, $pagesize)->find('a.id, title, pic, type, aa.likes, aa.clicks, add_time');
		}
		if ($rs) {
			//shuffle($rs);
			foreach ($rs as $g) {
				$g->clicks = $this->_changeNum($g->clicks);
				$g->likes = $this->_changeNum($g->likes);
				//if (!$this->is_mini) $g->content = preg_replace('/[\n\r]+/', '', preg_replace('/<\/?[^>]+>/', '', $g->content));
				$g->add_time = date('m-d', $g->add_time);
				//$g->type = 0; //0视频，1广告，2跳转小程序
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
		$list = add_domain_deep($list, ['pic']);
		
		$j = $offset*$pagesize;
		$rand = mt_rand($j, $j+$pagesize);
		$comment = [];
		/*if ($this->miniprogram->early==0) {
			$rs = SQL::share('buddhaaudio_comment')
				->where("miniprogram_id='{$this->miniprogram->id}' AND buddhaaudio_id='{$id}' AND status=1")->sort('id DESC')->limit($offset, $pagesize)->find("*, '' as member_name, '' as member_avatar");
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
		}
		$comment = add_domain_deep($comment, ['member_avatar']);*/
		
		$bgmusic = '';
		if ($buddha->music_enable==1) {
			$bgmusic = $buddha->music;
		}
		
		$detailTipsUrl = '';
		if ($this->miniprogram->early==1) {
			if (strlen($this->detailTipsUrl)) {
				$ids = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')->where("a.status=1 AND a.id!='{$id}' AND a.type=0")->sort('aa.clicks DESC')->pagesize(10)->returnArray()->find('a.id');
				if (count($ids)) {
					shuffle($ids);
					$detailTipsUrl = '/pages/index/detail?id='.$ids[0];
				}
			}
		} else {
			/*$ids = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')->where("a.status=1 AND a.id!='{$id}' AND a.type=0")->sort('aa.clicks DESC')->pagesize(10)->returnArray()->find('a.id');
			if (count($ids)) {
				shuffle($ids);
				$detailTipsUrl = '/pages/index/detail?id='.$ids[0];
			}*/
			if (strlen($this->detailTipsUrl)) {
				$detailTipsUrl = $this->detailTipsUrl;
			}
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
		$posad = ['url'=>$this->detailPosAdUrl, 'image'=>$this->detailPosAdImage];
		$posad = add_domain_deep($posad, ['image']);
		$trans = ['title'=>$this->miniprogram->trans_title, 'url'=>$this->miniprogram->trans_url, 'image'=>$this->miniprogram->trans_pic];
		$trans = add_domain_deep($trans, ['image']);
		$addmy = $this->detailAddMyEnable;
		$feedback = $this->detailFeedbackEnable;
		$ad_fixed = $this->miniprogram->review==1 ? 0 : intval($this->miniprogram->ad_fixed);
		$ad_fixed_percent = $this->miniprogram->review==1 ? 0 : 100-intval($this->miniprogram->ad_fixed_percent);
		$subscribe_id = $this->miniprogram->subscribe_id;
		$comment_hidden = intval($this->miniprogram->comment_hidden);
		$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->detailPositionWxAdEnable:0, 'adunit'=>$this->detailPositionWxAdUnitId];
		$prev_page = null;
		$next_page = $list ? $list[0] : null;
		if ($this->ver > 2) {
			$where = "aa.miniprogram_id='{$this->miniprogram->id}' AND status=1 AND category_id='{$buddha->category_id}'";
			$where .= " AND a.id<'{$id}' AND a.type=0 AND a.music_enable=1";
			$prev_page = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')->where($where)->sort('a.id DESC')->row('a.id, a.title, type, music');
			$where = "aa.miniprogram_id='{$this->miniprogram->id}' AND status=1 AND category_id='{$buddha->category_id}'";
			$where .= " AND a.id>'{$id}' AND a.type=0 AND a.music_enable=1";
			$next_page = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')->where($where)->sort('a.id ASC')->row('a.id, a.title, type, music');
		}
		$review = intval($this->miniprogram->review);
		$bg = '/uploads/pic/2020/05/29/20052916064558550.jpg';
		$bgsize = '320px 210px';
		success(compact('buddha', 'content_next', 'banner', 'footer', 'btn', 'tips', 'newyear', 'position', 'posad', 'trans', 'addmy', 'feedback', 'ad_fixed', 'ad_fixed_percent', 'subscribe_id', 'comment_hidden', 'wxpositionad', 'list', 'comment', 'prev_page', 'next_page', 'review', 'bg', 'bgsize'), '成功', 0, ['appId'=>$this->appId]);
	}
	
	public function getMusic() {
		$id = $this->request->get('id', 0);
		if (!strlen($id)) error('缺少参数');
		$buddha = SQL::share("buddhaaudio a")
			->left("buddhaaudio_attr aa", "a.id=aa.buddhaaudio_id")
			->where("a.id='{$id}' AND aa.miniprogram_id='{$this->miniprogram->id}'")
			->cached(60*5)
			->row('a.id, a.title, a.music, a.category_id, 0 as next_id');
		if (!$buddha) error('佛音不存在');
		$where = "aa.miniprogram_id='{$this->miniprogram->id}' AND status=1 AND category_id='{$buddha->category_id}'";
		$where .= " AND a.id>'{$id}' AND a.type=0 AND a.music_enable=1";
		$buddha->next_id = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')->where($where)->sort('a.id ASC')->value('a.id');
		success($buddha, '成功', 0, ['appId'=>$this->appId]);
	}
	
	//视频详情
	public function video() {
		//if ($this->miniprogram->only_pic==1) error('记录不存在');
		$id = $this->request->get('id', 0);
		$offset = $this->request->get('offset', 0);
		$pagesize = $this->request->get('pagesize', 8);
		if ($id<=0) error('缺少参数');
		$_time = intval(date('H'));
		if ($_time>5 && $_time<=11) $_time = '上午好，';
		else if ($_time>11 && $_time<=14) $_time = '中午好，';
		else if ($_time>14 && $_time<=18) $_time = '下午好，';
		else $_time = '晚上好，';
		
		$video = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')->where("a.id='{$id}' AND aa.miniprogram_id='{$this->miniprogram->id}'")
			->row('a.*, aa.clicks, aa.likes');
		if (!$video) error('记录不存在');
		SQL::share('buddhaaudio_attr')->where("buddhaaudio_id='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['clicks'=>['+1']]);
		SQL::share('buddhaaudio')->where($id)->update(['clicks'=>['+1'], 'today_clicks'=>['+1']]);
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
		$rs = SQL::share('buddhaaudio a')->left('buddhaaudio_attr aa', 'a.id=aa.buddhaaudio_id')
			->where("a.id!='{$id}' AND status=1 AND aa.miniprogram_id='{$this->miniprogram->id}' AND category_id='{$video->category_id}'")
			->sort('aa.clicks DESC, a.id DESC')->limit($offset, $pagesize)->find('a.id, a.title, a.pic, a.type, a.add_time, aa.likes, aa.clicks, add_time');
		if ($rs) {
			shuffle($rs);
			foreach ($rs as $g) {
				$title = str_replace('[_city_]', $this->city, $g->title);
				$title = str_replace('[_time_]', $_time, $title);
				$title = str_replace('[_date_]', date('今天是m月d日'), $title);
				$title = str_replace('[_nongli_]', $this->nongli, $title);
				$g->add_time = get_time_word($g->add_time);
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
		$position = ['enable'=>$this->detailPositionEnable, 'type'=>$this->detailPositionType, 'text'=>$this->detailPositionText, 'url'=>$this->detailPositionUrl];
		$wxvideoad = ['enable'=>$this->wxAdEnable==1?$this->detailListWxAdEnable:0, 'adunit'=>$this->detailListWxVideoAdUnitId];
		$banner = ['enable'=>$this->wxAdEnable==1?$this->detailTopAdEnable:0, 'type'=>$this->detailTopAdType, 'image'=>$this->detailTopAdImage, 'url'=>$this->detailTopAdUrl, 'adunit'=>$this->detailTopAdUnitId, 'message_title'=>$this->customMessageTitle, 'message_path'=>$this->customMessagePath, 'message_image'=>$this->customMessageImg];
		$wxpositionad = ['enable'=>$this->wxAdEnable==1?$this->detailPositionWxAdEnable:0, 'adunit'=>$this->detailPositionWxAdUnitId];
		$addmy = $this->detailAddMyEnable;
		$feedback = $this->detailFeedbackEnable;
		$trans = ['title'=>$this->miniprogram->trans_title, 'url'=>$this->miniprogram->trans_url, 'image'=>$this->miniprogram->trans_pic];
		$trans = add_domain_deep($trans, ['image']);
		success(compact('video', 'btn', 'addmy', 'position', 'banner', 'trans', 'feedback', 'wxvideoad', 'wxpositionad', 'list', 'date'), '成功', 0, ['appId'=>$this->appId]);
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
		if ($id<=0) error('文章不存在');
		SQL::share('buddhaaudio')->where($id)->update(['likes'=>['+1']]);
		SQL::share('buddhaaudio_attr')->where("buddhaaudio_id='{$id}' AND miniprogram_id='{$this->miniprogram->id}'")->update(['likes'=>['+1']]);
		success('ok');
	}
	
	public function comment() {
		if (IS_POST) {
			$buddhaaudio_id = $this->request->post('buddhaaudio_id', 0);
			$content = $this->request->post('content');
			if ($buddhaaudio_id<=0 || !strlen($content)) error('缺少参数');
			$count = SQL::share('buddhaaudio_comment')->where("ip='{$this->ip}'")->comparetime('n', 'add_time', '=0')->count();
			if ($count>=2) error('您发表得太快了');
			$miniprogram_id = $this->miniprogram->id;
			$ip = $this->ip;
			$add_time = time();
			$row = SQL::share('buddhaaudio_comment')->returnObj()->insert(compact('miniprogram_id', 'buddhaaudio_id', 'content', 'ip', 'add_time'));
			$row->member_name = '网友';
			$row->member_avatar = '/images/avatar.png';
			$row->add_time = get_time_word($row->add_time);
			$row->type = 0;
			$row = add_domain_deep($row, ['member_avatar']);
			SQL::share('buddhaaudio')->where($buddhaaudio_id)->update(['comments'=>['+1']]);
			success($row);
		}
		success('ok');
	}
	
	public function comment_like() {
		$id = $this->request->post('id', 0);
		if ($id<=0) error('评论不存在');
		SQL::share('buddhaaudio_comment')->where($id)->update(['likes'=>['+1']]);
		success('ok');
	}
	
	//采集
	//curl /api/buddha/pickBuddha
	public function pickBuddha() {
		exit;
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		if (ob_get_level() == 0) ob_start();
		ob_implicit_flush(true);
		ob_clean();
		$count = 0;
		//$categories = [1418, 2463, 0];
		$categories = [0];
		foreach ($categories as $cateid) {
			$json = requestData('post', 'https://agg-api.actuive.com/api/v1/article/list', "cateid={$cateid}&offset=0&limit=1000", true, false, [
				'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
				'authorization: 0c4b46c0-ab88-4ea3-967e-ccbdd4f65e8c',
				'appid: wx1aafc17e506a4a74'
			]);
			write_log(json_encode($json), '/temp/buddha.txt');
			if (intval($json['code'])!=0) {
				write_log("CODE ".$json['code'].' MSG '.$json['msg'], '/temp/buddha.txt');
				//error($json['msg'], 0, $json['code']);
				continue;
			}
			$total = $json['data']['total'];
			//"cateid={$cateid}&offset=".($i*15)."&limit=15"
			$json = requestData('post', 'https://agg-api.actuive.com/api/v1/article/list', "cateid={$cateid}&offset=0&limit={$total}", true, false, [
				'user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/WIFI Language/zh_CN',
				'authorization: 0c4b46c0-ab88-4ea3-967e-ccbdd4f65e8c',
				'appid: wx1aafc17e506a4a74'
			]);
			if (intval($json['code'])!=0) {
				write_log("CODE ".$json['code'].' MSG '.$json['msg'], '/temp/buddha.txt');
				//error($json['msg'], 0, $json['code']);
				continue;
			}
			if (is_array($json['data']['list'])) {
				$list = array_reverse($json['data']['list']);
				foreach ($list as $l) {
					if (SQL::share('buddhaaudio')->where("title='".$l['title']."'")->exist()) continue;
					$rs = SQL::share('buddhaaudio')->find('title');
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
						case 1418:$r['category_id'] = 1;break;
						case 0:$r['category_id'] = 1;break;
						case 2463:$r['category_id'] = 3;break;
					}
					$r['add_time'] = $d['newstime'];
					$buddhaaudio_id = SQL::share('buddhaaudio')->insert($r);
					$miniprogram_id = 0;
					SQL::share('buddhaaudio_attr')->insert(compact('miniprogram_id', 'buddhaaudio_id'));
					$count++;
				}
			}
		}
		ob_flush();
		flush();
		ob_end_flush();
		write_log("GET BUDDHAS COMPLETE, QUANTITY {$count}", '/temp/buddha.txt');
		success("GET BUDDHAS COMPLETE, QUANTITY {$count}");
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
		$timeout = 60*60;
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
		$file = upload_obj_file($content, 'buddhaaudio');
		$file = add_domain($file);
		return $file;
		/*$filename = generate_sn().'.'.$suffix;
		$dir = UPLOAD_PATH.'/buddha/'.date('Y').'/'.date('m').'/'.date('d');
		makedir($dir);
		$res = @fopen(ROOT_PATH.$dir.'/'.$filename, 'a');
		@fwrite($res, $content);
		@fclose($res);
		$file = str_replace('/public/', '/', $dir).'/'.$filename;
		if (in_array($suffix, ['jpg', 'jpeg'])) {
			//image_compress('/public/'.$file, 1, '/public/'.$file);
		}
		return $file;*/
	}

}
