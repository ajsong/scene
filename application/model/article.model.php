<?php
class article_model extends base_model {

	public function __construct() {
		parent::__construct();
	}

	//获取文章详情
	public function detail($id, $mark='') {
		$row = SQL::share('article')->where("status=1 AND id='{$id}'")->row();
		return $row;
	}
	
	//分类列表
	public function categories($parent_id=0) {
		$rs = SQL::share('article_category')->where("status=1 AND parent_id='{$parent_id}'")->sort('sort ASC, id ASC')->find('*, NULL as categories');
		if ($rs) {
			foreach ($rs as $k=>$g) {
				if ($g->parent_id) $rs[$k]->categories = $this->categories($g->parent_id);
			}
			$rs = add_domain_deep($rs, array('pic'));
		}
		return $rs;
	}

	//关联图片
	public function _pics($article_id, $limit=0) {
		$rs = SQL::share('article_pic')->where("article_id='{$article_id}'")->sort('id ASC')->pagesize($limit)->find('pic');
		return $rs;
	}

	//关联商品
	public function _goods($article_id) {
		$rs = SQL::share('article_goods ag')->left('goods g', 'goods_id=g.id')->where("article_id='{$article_id}'")->sort('ag.id ASC')
			->find('g.id, g.name, g.model, g.pic, g.price');
		return $rs;
	}

	//是否点赞
	public function _liked($article_id) {
		if (!$this->member_id) return 0;
		return SQL::share('article_like')->where("article_id='{$article_id}' AND member_id='{$this->member_id}'")->count();
	}
}
