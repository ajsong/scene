<?php
class buddhaaudio extends core {
	
	public function __construct() {
		parent::__construct();
	}

	//index
	public function index() {
		$where = '';
		$sort = 'b.id ASC';
		$id = $this->request->get('id');
		$category_id = $this->request->get('category_id', 0);
		$keyword = $this->request->get('keyword');
        $sortby = $this->request->get('sortby');
		if (strlen($id)) {
			$where .= " AND b.id='{$id}'";
		}
		if (strlen($keyword)) {
			$where .= " AND (b.title LIKE '%{$keyword}%' OR b.content LIKE '%{$keyword}%')";
		}
		if ($category_id) {
			$where .= " AND b.category_id='{$category_id}'";
		}
		if ($sortby) {
			$sort = str_replace(',', ' ', $sortby).', '.$sort;
		}
		$rs = SQL::share('buddhaaudio b')
			->left('buddhaaudio_category bc', 'b.category_id=bc.id')
			->where($where)->isezr()->setpages(compact('id', 'keyword', 'category_id', 'sortby'))
			->sort($sort)->find("b.*, bc.name as category_name");
		$sharepage = SQL::share()->page;
		if ($rs) {
			foreach ($rs as $g) {
			
			}
		}
		
		$last_count = '';
		$logFile = ROOT_PATH . '/temp/buddhaaudio.txt';
		if (file_exists($logFile)) {
			$log = file_get_contents($logFile);
			preg_match_all('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})[\r\n]+GET BUDDHAS COMPLETE, QUANTITY (\d+)/', $log, $matcher);
			if ($matcher) {
				$last_count = '最后采集 <strong style="color:#38f;">'.end($matcher[1]).'</strong>　数量 <strong style="color:#38f;">'.end($matcher[2]).'</strong> 个';
			}
		}
		
		$categories = SQL::share('buddhaaudio_category')->where("status=1")->find();
		
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->smarty->assign('categories', $categories);
		$this->smarty->assign('last_count', $last_count);
		
		$clicks = SQL::share('buddhaaudio')->sum('clicks');
		$yesterday_clicks = SQL::share('buddhaaudio')->sum('yesterday_clicks');
		$today_clicks = SQL::share('buddhaaudio')->sum('today_clicks');
		$this->smarty->assign('clicks', $clicks);
		$this->smarty->assign('yesterday_clicks', $yesterday_clicks);
		$this->smarty->assign('today_clicks', $today_clicks);
		
		$this->display();
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) error('缺少数据');
		SQL::share('buddhaaudio')->where($id)->update(compact('status'));
		success('ok');
	}
	
	public function add() {
		$this->edit();
	}
	public function edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) { //添加
			$id = $this->request->post('id', 0);
			$title = $this->request->post('title');
			$category_id = $this->request->post('category_id', 0);
			$pic = $this->request->file('buddhaaudio', 'pic', UPLOAD_LOCAL);
			$type = $this->request->post('type', 0);
			$url = $this->request->post('url');
			$music = $this->request->file('buddhaaudio', 'music', UPLOAD_LOCAL, false, ['mp3', 'm4a']);
			$music_name = $this->request->post('music_name');
			$music_enable = $this->request->post('music_enable', 1);
			$content = $this->request->post('content', '', '\\');
			$sort = $this->request->post('sort', 0);
			$status = $this->request->post('status', 1);
			$data = compact('title', 'category_id', 'pic', 'type', 'url', 'music', 'music_name', 'music_enable', 'content', 'sort', 'status');
			if ($id>0) {
				SQL::share('buddhaaudio')->where($id)->update($data);
			} else {
				$data['add_time'] = time();
				$id = SQL::share('buddhaaudio')->insert($data);
				$rs = SQL::share('miniprogram')->where("type='4'")->sort('id ASC')->find('id');
				foreach ($rs as $g) {
					SQL::share('buddhaaudio_attr')->insert(array('miniprogram_id'=>$g->id, 'buddhaaudio_id'=>$id));
				}
			}
			location("?app=buddhaaudio&act=edit&id={$id}&msg=1");
		} else if ($id>0) { //显示
			$row = SQL::share('buddhaaudio')->where($id)->row();
		} else {
			$row = t('buddhaaudio');
		}
		
		$categories = SQL::share('buddhaaudio_category')->where("status=1")->find();
		
		$this->smarty->assign('row', $row);
		$this->smarty->assign('categories', $categories);
		$this->display('buddhaaudio.edit.html');
	}

	public function upload_pic(){
        $result = $this->request->file('buddhaaudio', 'pic', UPLOAD_LOCAL);
		success($result);
    }

	//delete
	public function delete() {
		$id = $this->request->get('id', 0);
		SQL::share('buddhaaudio')->delete($id);
		SQL::share('buddhaaudio_attr')->delete("buddhaaudio_id='{$id}'");
		header("Location:?app=buddhaaudio&act=index");
	}
	
	public function multiple_delete() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) error('请选择');
		SQL::share('buddhaaudio')->delete("id IN ({$ids})");
		SQL::share('buddhaaudio_attr')->delete("buddhaaudio_id IN ({$ids})");
		header("Location:?app=buddhaaudio&act=index");
	}
}
