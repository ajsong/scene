<?php
class video extends core {
	
	public function __construct() {
		parent::__construct();
	}

	//index
	public function index() {
		$where = '';
		$sort = 'v.id DESC';
		$id = $this->request->get('id');
		$keyword = $this->request->get('keyword');
        $tencentvideo = $this->request->get('tencentvideo');
        $category_id = $this->request->get('category_id', 0);
		$sortby = $this->request->get('sortby');
		if (strlen($id)) {
			$where .= " AND v.id='{$id}'";
		}
		if (strlen($keyword)) {
			$where .= " AND v.title LIKE '%{$keyword}%'";
		}
		if (strlen($tencentvideo)) {
			$where .= " AND v.tencentvideo='{$tencentvideo}'";
		}
		if ($category_id>0) {
			$where .= " AND v.category_id='{$category_id}'";
		}
		if ($sortby) {
			$sort = 'v.'.str_replace(',', ' ', $sortby).', '.$sort;
		}
		$rs = SQL::share('video v')
			->left('video_category vc', 'v.category_id=vc.id')
			->where($where)->isezr()->setpages(compact('id', 'keyword', 'tencentvideo', 'category_id', 'sortby'))
			->sort($sort)->find("v.*, vc.name as category_name");
		$sharepage = SQL::share()->page;
		
		$categories = SQL::share('video_category')->where("id>1")->sort('sort ASC, id ASC')->find();
		
		$last_count = '';
		$logFile = ROOT_PATH . '/temp/video.txt';
		if (file_exists($logFile)) {
			$log = file_get_contents($logFile);
			preg_match_all('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})[\r\n]+GET VIDEOS COMPLETE, QUANTITY (\d+)/', $log, $matcher);
			if ($matcher) {
				$last_count = '最后采集 <strong style="color:#38f;">'.end($matcher[1]).'</strong>　数量 <strong style="color:#38f;">'.end($matcher[2]).'</strong> 个';
			}
		}
		
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->smarty->assign('categories', $categories);
		$this->smarty->assign('last_count', $last_count);
		
		$clicks = SQL::share('video')->sum('click');
		$yesterday_clicks = SQL::share('video')->sum('yesterday_clicks');
		$today_clicks = SQL::share('video')->sum('today_clicks');
		$this->smarty->assign('clicks', $clicks);
		$this->smarty->assign('yesterday_clicks', $yesterday_clicks);
		$this->smarty->assign('today_clicks', $today_clicks);
		
		$this->display();
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) error('缺少数据');
		SQL::share('video')->where($id)->update(compact('status'));
		success('ok');
	}
	
	public function add() {
		$this->edit();
	}
	public function edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) { //添加
			$id = $this->request->post('id', 0);
			$category_id = $this->request->post('category_id', 0);
			$title = $this->request->post('title');
			$status = $this->request->post('status', 1);
			$tencentvideo = $this->request->post('tencentvideo', 0);
			$vid = $this->request->post('vid');
			$time = $this->request->post('time', 0);
			$img = $this->request->file('video', 'img', UPLOAD_LOCAL);
			$url = $this->request->post('url');
			$data = compact('title', 'img', 'url', 'category_id', 'vid', 'tencentvideo', 'status', 'time');
			if ($id>0) {
				SQL::share('video')->where($id)->update($data);
			} else {
				//$data['played'] = random_num(50, 300) * 100;
				//$data['likes'] = random_num(50, 300);
				$data['add_time'] = time();
				$id = SQL::share('video')->insert($data);
				$rs = SQL::share('miniprogram')->where("type=1")->sort('id ASC')->find('id');
				foreach ($rs as $g) {
					SQL::share('video_attr')->insert(array('miniprogram_id'=>$g->id, 'video_id'=>$id));
				}
				
				/*$article_id = SQL::share('article')->insert(array('title'=>$title, 'category_id'=>6, 'pic'=>$img, 'url'=>$url, 'type'=>5, 'status'=>1, 'sort'=>999, 'add_time'=>time()));
				$rs = SQL::share('miniprogram')->where("type='0'")->sort('id ASC')->find('id');
				foreach ($rs as $g) {
					SQL::share('article_attr')->insert(array('miniprogram_id'=>$g->id, 'article_id'=>$article_id));
				}*/
			}
			location("?app=video&act=edit&id={$id}&msg=1");
		} else if ($id>0) { //显示
			$row = SQL::share('video')->where($id)->row();
		} else {
			$row = t('video');
		}
		$this->smarty->assign('row', $row);
		$categories = SQL::share('video_category')->where("id>1")->sort('sort ASC, id ASC')->find();
		$this->smarty->assign('categories', $categories);
		$this->display('video.edit.html');
	}
	
	public function upload_pic(){
		$result = $this->request->file('video', 'pic', UPLOAD_LOCAL);
		success($result);
	}
	
	public function upload_video(){
		$url = $this->request->file('video', 'filename', 2, false, ['mp4']);
		$filepath = PUBLIC_PATH.$url;
		$urls = explode('/', $url);
		$file = $urls[count($urls)-1];
		$dir = str_replace($file, '', $url);
		$files = explode('.', $file);
		$name = $files[0];
		$ext = $files[1];
		$upload = p('upload', 'qniu');
		$result = $upload->upload([$filepath], NULL, str_replace('/public/', '/', $dir), $name, $ext);
		$result = $result['file'];
		$result = add_domain($result);
		unlink(PUBLIC_PATH.$url);
		success($result);
	}

	//delete
	public function delete() {
		$id = $this->request->get('id', 0);
		$row = SQL::share('video')->where($id)->row('img, url');
		if ($row) {
			if (preg_match('/^\/uploads\//', $row->img)) unlink(PUBLIC_PATH.$row->img);
			if (preg_match('/^\/uploads\//', $row->url)) unlink(PUBLIC_PATH.$row->url);
		}
		SQL::share('video')->delete($id);
		SQL::share('video_attr')->delete("video_id='{$id}'");
		header("Location:?app=video&act=index");
	}
}
