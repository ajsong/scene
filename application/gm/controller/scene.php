<?php
class scene extends core {

	public function __construct() {
		parent::__construct();
	}

	//会员列表
	public function index() {
		$where = 'ms.id>0';
		$member_id = $this->request->get('member_id');
		if (strlen($member_id)) {
			$where .= " AND ms.member_id='{$member_id}'";
		}
		$rs = SQL::share('member_scene ms')->left('member m', 'm.id=ms.member_id')->where($where)->isezr()
			->setpages(compact('member_id'))
			->sort('ms.id DESC')->find("ms.*, m.name as member_name");
		$sharepage = SQL::share()->page;
		$wherebase64 = SQL::share()->wherebase64;
		if ($rs) {
			foreach ($rs as $key => $row) {
				//$rs[$key]->url = urlencode(https().$_SERVER['HTTP_HOST']."/wap.php?reseller={$row->id}");
				$rs[$key]->url = "{$this->domain}/v/{$row->code}";
			}
		}
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->smarty->assign('where', $wherebase64);
		$this->display();
	}

	//删除
	public function delete() {
		$id = $this->request->get('id', 0);
		$code = SQL::share('member_scene')->where($id)->value('code');
		SQL::share('member_scene')->delete($id);
		SQL::share('member_scene_page')->delete("scene_id='{$id}'");
		$model = m('oss');
		$model->delete("show/{$code}");
		header("Location:?app=scene&act=index");
	}
	
	//上传到OSS
	public function oss() {
		$id = $this->request->get('id', 0);
		$type = $this->request->get('type');
		if ($id<=0 || !strlen($type)) error('缺少数据');
		$code = SQL::share('member_scene')->where($id)->value('code');
		$model = m('oss');
		$parasitic = $model->upload("show/{$code}", "{$this->domain}/v/{$code}", $type);
		success($parasitic);
	}
}

