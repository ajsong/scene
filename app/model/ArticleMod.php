<?php
namespace app\model;

use think\Model;

class ArticleMod extends Model
{
	protected $name = 'article';
	
	public function base_list($status = '', $sort = 'id DESC') {
		$rs = static::field('id, title, add_time')->order($sort);
		if (strlen($status)) $rs->where('status', '=', $status);
		$rs = $rs->select();
		return $rs;
	}
}