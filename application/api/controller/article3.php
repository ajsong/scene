<?php
class article3 extends core {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function index() {
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$title = $this->request->post('title');
			$pic = $this->request->file('article', ':pic');
			$clicks = $this->request->post('clicks', 0);
			$sort = $this->request->post('sort', 0);
			$category_id = $this->request->post('category_id', 0);
			if ($id>0) {
				SQL::share('article3')->where($id)->update(compact('title', 'pic', 'clicks', 'sort', 'category_id'));
			} else {
				$add_time = time();
				SQL::share('article3')->insert(compact('title', 'pic', 'clicks', 'sort', 'category_id', 'add_time'));
			}
			script('保存成功', '/index/article3');
		}
		$list = SQL::share('article3')->sort('sort ASC, id DESC')->find('id, title, pic, category_id, clicks, sort, add_time');
		$list = add_domain_deep($list, ['pic']);
		$url = SQL::share('client')->value('mini_qrcode');
		success(compact('list', 'url'));
	}
	
	public function delete() {
		$id = $this->request->post('id', 0);
		if ($id<=0) error('数据错误');
		SQL::share('article3')->delete($id);
		location('/index/article3');
	}
	
	public function setUrl() {
		if (!IS_POST) error('数据错误');
		$url = $this->request->post('url');
		if (!strlen($url)) error('请输入跳转网址');
		SQL::share('client')->update(['mini_qrcode'=>$url]);
		success('ok');
	}
	
}