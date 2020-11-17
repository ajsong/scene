<?php
class feedback extends core {
	public function __construct() {
		parent::__construct();
	}
	
	//index
	public function index() {
		$where = '';
		$member_id = $this->request->get('member_id');
		$keyword = $this->request->get('keyword');
		if ($keyword) {
			$where .= " AND (f.name LIKE '%{$keyword}%' OR f.content LIKE '%{$keyword}%')";
		}
		if (strlen($member_id)) {
			$where .= " AND member_id='{$member_id}'";
		}
		$rs = SQL::share('feedback f')
			->left('member m', 'f.member_id=m.id')
			->where($where)->isezr()->setpages(compact('member_id', 'keyword'))
			->sort('f.id DESC')->find("f.*, m.name as member_name, '' as miniprogram_type, '' as pic, '' as miniprogram_name, '' as parent_name, '' as parent_type");
		$sharepage = SQL::share()->page;
		if ($rs) {
			foreach ($rs as $g) {
				if ($g->miniprogram_id>0) {
					$miniprogram = SQL::share('miniprogram')->where("id='{$g->miniprogram_id}' AND status=1")->row('name, pic, type');
					$g->miniprogram_type = $miniprogram->type;
					$g->pic = $miniprogram->pic;
					$g->miniprogram_name = $miniprogram->name;
					switch ($g->miniprogram_type) {
						case 1:
							$g->parent_type = 'video';
							break;
						case 2:
							$g->parent_type = 'blessing';
							break;
						case 3:
							$g->parent_type = 'buddha';
							break;
						default:
							$g->parent_type = 'article';
							break;
					}
					$g->parent_name = SQL::share($g->parent_type)->where($g->parent_id)->value('title');
				} else {
					$g->parent_name = $g->name;
				}
			}
		}
		$rs = add_domain_deep($rs, ['pic']);
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display();
	}
	
	//delete
	public function delete() {
		$id = $this->request->get('id', 0);
		SQL::share('feedback')->delete($id);
		header("Location:?app=feedback&act=index");
	}
}
