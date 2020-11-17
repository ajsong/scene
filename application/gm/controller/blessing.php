<?php
class blessing extends core {
	
	public function __construct() {
		parent::__construct();
	}

	//index
	public function index() {
		$where = '';
		$sort = 'b.id DESC';
		$id = $this->request->get('id');
		$keyword = $this->request->get('keyword');
        $sortby = $this->request->get('sortby');
		if (strlen($id)) {
			$where .= " AND b.id='{$id}'";
		}
		if (strlen($keyword)) {
			$where .= " AND (b.title LIKE '%{$keyword}%' OR b.content LIKE '%{$keyword}%')";
		}
		if ($sortby) {
			$sort = str_replace(',', ' ', $sortby).', '.$sort;
		}
		$rs = SQL::share('blessing b')
			->where($where)->isezr()->setpages(compact('id', 'keyword', 'sortby'))
			->sort($sort)->find();
		$sharepage = SQL::share()->page;
		if ($rs) {
			foreach ($rs as $g) {
			
			}
		}
		
		$last_count = '';
		$logFile = ROOT_PATH . '/temp/blessing.txt';
		if (file_exists($logFile)) {
			$log = file_get_contents($logFile);
			preg_match_all('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})[\r\n]+GET BLESSINGS COMPLETE, QUANTITY (\d+)/', $log, $matcher);
			if ($matcher) {
				$last_count = '最后采集 <strong style="color:#38f;">'.end($matcher[1]).'</strong>　数量 <strong style="color:#38f;">'.end($matcher[2]).'</strong> 个';
			}
		}
		
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->smarty->assign('last_count', $last_count);
		
		$clicks = SQL::share('blessing')->sum('clicks');
		$yesterday_clicks = SQL::share('blessing')->sum('yesterday_clicks');
		$today_clicks = SQL::share('blessing')->sum('today_clicks');
		$this->smarty->assign('clicks', $clicks);
		$this->smarty->assign('yesterday_clicks', $yesterday_clicks);
		$this->smarty->assign('today_clicks', $today_clicks);
		
		$this->display();
	}
	
	public function status() {
		$id = $this->request->post('id', 0);
		$status = $this->request->post('status', 0);
		if ($id<=0) error('缺少数据');
		SQL::share('blessing')->where($id)->update(compact('status'));
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
			$text_color = $this->request->post('text_color', '#000000');
			$border_color = $this->request->post('border_color', '#000000');
			$pic = $this->request->file('blessing', 'pic', UPLOAD_LOCAL);
			$bg_pic = $this->request->file('blessing', 'bg_pic', UPLOAD_LOCAL);
			$share_pic = $this->request->file('blessing', 'share_pic', UPLOAD_LOCAL);
			$top_avatar_pic = $this->request->file('blessing', 'top_avatar_pic', UPLOAD_LOCAL);
			$bottom_avatar_pic = $this->request->file('blessing', 'bottom_avatar_pic', UPLOAD_LOCAL);
			//$bg_music = $this->request->file('blessing', 'bg_music', UPLOAD_LOCAL, false, ['mp3', 'm4a']);
			$bg_music = $this->request->post('bg_music');
			$music_name = $this->request->post('music_name');
			$music_enable = $this->request->post('music_enable', 0);
			$content = $this->request->post('content', '', '\\');
			$sort = $this->request->post('sort', 0);
			$status = $this->request->post('status', 1);
			$data = compact('title', 'text_color', 'border_color', 'pic', 'bg_pic', 'share_pic', 'top_avatar_pic', 'bottom_avatar_pic', 'bg_music', 'music_name', 'music_enable', 'content', 'sort', 'status');
			if ($id>0) {
				SQL::share('blessing')->where($id)->update($data);
			} else {
				$data['add_time'] = time();
				$id = SQL::share('blessing')->insert($data);
				$rs = SQL::share('miniprogram')->where("type='2'")->sort('id ASC')->find('id');
				foreach ($rs as $g) {
					SQL::share('blessing_attr')->insert(array('miniprogram_id'=>$g->id, 'blessing_id'=>$id));
				}
			}
			location("?app=blessing&act=edit&id={$id}&msg=1");
		} else if ($id>0) { //显示
			$row = SQL::share('blessing')->where($id)->row();
		} else {
			$row = t('blessing');
		}
		
		$this->smarty->assign('row', $row);
		$this->display('blessing.edit.html');
	}

	public function upload_pic(){
        $result = $this->request->file('blessing', 'pic', UPLOAD_LOCAL);
		success($result);
    }

	//delete
	public function delete() {
		$id = $this->request->get('id', 0);
		SQL::share('blessing')->delete($id);
		SQL::share('blessing_attr')->delete("blessing_id='{$id}'");
		header("Location:?app=blessing&act=index");
	}
	
	public function multiple_delete() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) error('请选择');
		SQL::share('blessing')->delete("id IN ({$ids})");
		SQL::share('blessing_attr')->delete("blessing_id IN ({$ids})");
		header("Location:?app=blessing&act=index");
	}
}
