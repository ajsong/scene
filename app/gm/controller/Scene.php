<?php
declare (strict_types = 1);

namespace app\gm\controller;

use app\gm\controller\Core;
use think\facade\Db;
use think\facade\View;
use think\Request;

class Scene extends Core
{
	//会员列表
	public function index() {
		$where[] = ['ms.id', '>', 0];
		$member_id = $this->request->get('member_id');
		if (strlen($member_id)) {
			$where[] = ['ms.member_id', '=', $member_id];
		}
		$rs = Db::name('member_scene')->alias('ms')->leftJoin('member m', 'm.id=ms.member_id')->where($where)->order('ms.id DESC')->field("ms.*, m.name as member_name")->paginate([
			'list_rows' => 10,
			'query' => request()->param()
		])->each(function($item) {
			$item['url'] = "{$this->front_domain}/v/{$item['code']}";
			return $item;
		});
		View::assign('rs', $rs);
		return success('ok', 'SUCCESS', 0, compact('member_id'));
	}
	
	//删除
	public function delete() {
		$id = $this->request->get('id', 0);
		Db::name('member_scene')->where('id', $id)->delete();
		Db::name('member_scene_page')->where('scene_id', $id)->delete();
		location('/scene');
	}
	
	//上传到OSS
	public function oss() {
		$id = $this->request->get('id', 0);
		$type = $this->request->get('type');
		if ($id<=0 || !strlen($type)) return error('缺少数据');
		$code = Db::name('member_scene')->where('id', $id)->value('code');
		$model = m('oss');
		$parasitic = $model->upload("show/{$code}", "{$this->domain}/v/{$code}", $type);
		return success($parasitic);
	}
}
