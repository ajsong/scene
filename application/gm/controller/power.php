<?php
class power extends core {
	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$where = " AND super=0";
		$keyword = $this->request->get('keyword');
		if ($keyword) {
			$where .= " AND (name LIKE '%{$keyword}%' OR real_name LIKE '%{$keyword}%')";
		}
		$rs = SQL::share('admin')->where($where)->isezr()->setpages(compact('keyword'))->sort('id ASC')->find();
		$sharepage = SQL::share()->page;
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display();
	}
	
	public function edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$status = $this->request->post('status', 0);
			$menu = $this->request->post('menu', '', 'origin');
			$operate = $this->request->post('operate', '', 'origin');
			$super = SQL::share('admin')->where($id)->cached(60*60*24*7)->value('super', 'intval');
			if ($super!=1) {
				SQL::share('admin')->where($id)->update(array('status'=>$status));
				//菜单权限
				SQL::share('admin_menu')->delete("admin_id='{$id}'");
				if ($menu) {
					$data = array();
					foreach ($menu as $g) {
						if ($g<=0) continue;
						$data[] = $g;
					}
					SQL::share('admin_menu')->insert(array('admin_id'=>$id, 'menu_id'=>$data), 'menu_id');
				}
				//操作权限
				SQL::share('admin_permission')->delete("admin_id='{$id}'");
				if ($operate) {
					$app = $act = array();
					foreach ($operate as $g) {
						if (!strlen($g)) continue;
						$appact = explode('|', $g);
						$app[] = $appact[0];
						$act[] = $appact[1];
					}
					SQL::share('admin_permission')->insert(array('admin_id'=>$id, 'app'=>$app, 'act'=>$act), 'app');
				}
			}
			SQL::share()->clearCached();
			location("?app=setting&act=manager");
		}
		$row = SQL::share('admin')->where($id)->row();
		$pa = $this->menu($id, 0);
		
		$permission = SQL::share('admin_permission')->where("admin_id='{$id}'")->find();
		$_permission = SQL::share('admin_permission')->where("admin_id='{$this->admin_id}'")->find();
		$rs = SQL::share('menu')->where("parent_id=0 AND status=1 AND is_menu=0 AND is_op=0")->sort('sort ASC, id ASC')->find();
		if ($rs) {
			foreach ($rs as $k=>$g) {
				if (preg_match('/^[a-z,]+$/', $g->edition)) {
					$nonShow = false;
					$editions = explode(',', $g->edition);
					foreach ($editions as $edition) {
						if (!in_array($edition, $this->function)) {
							$nonShow = true;
							break;
						}
					}
					if ($nonShow) {
						unset($rs[$k]);
						continue;
					}
				}
				if ($g->app=='weixin' && (WX_TAKEOVER==0 || !strlen(WX_TOKEN) || !strlen(WX_AESKEY))) {
					unset($rs[$k]);
					continue;
				}
				$hasMenu = false;
				if ($_permission) {
					foreach ($_permission as $m=>$n) {
						if ($n->app==$g->app && $n->act==$g->act) {
							$hasMenu = true;
							break;
						}
					}
				} else if ($this->admin_super==1) {
					$hasMenu = true;
				}
				if (!$hasMenu) {
					unset($rs[$k]);
					continue;
				}
				if ($row->super==1) {
					$rs[$k]->checked = 'checked';
					continue;
				}
				if ($permission) {
					foreach ($permission as $j=>$d) {
						if ($d->app==$g->app && $d->act==$g->act) {
							$rs[$k]->checked = 'checked';
						}
					}
				}
			}
			$rs = array_values($rs);
		}
		
		$this->smarty->assign('row', $row);
		$this->smarty->assign('pa', $pa);
		$this->smarty->assign('operate', $rs);
		
		$this->display();
	}
}
