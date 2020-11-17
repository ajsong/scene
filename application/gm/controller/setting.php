<?php
class setting extends core {
	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$this->manager();
	}

	//管理员列表
	public function manager() {
		if ($this->admin_super==0) {
			$ids = $this->_subManager($this->admin_id);
			$ids = implode(',', $ids);
			$where = "id IN ({$ids}) AND id!='{$this->admin_id}'";
		} else {
			$where = '';
		}
		$keyword = $this->request->get('keyword');
		$date = $this->request->get('date');
		if ($keyword) {
			$where .= " AND (name LIKE '%{$keyword}%' OR real_name LIKE '%{$keyword}%')";
		}
		$time = $date;
		if (!strlen($time)) $time = date('Y-m');
		$time = $this->_getMonthNum($time.'-01', date('Y-m-1'));
		$rs = SQL::share('admin')->where($where)->isezr()->setpages(compact('keyword', 'date'))->sort('id ASC')->find('*, 0 as clicks');
		$sharepage = SQL::share()->page;
		if ($rs) {
			foreach ($rs as $g) {
				$g->clicks = SQL::share('admin_miniprogram_article ama')
					->left('admin_miniprogram am', 'am.miniprogram_id=ama.miniprogram_id')
					->where("am.admin_id='{$g->id}'")->comparetime('m', 'ama.add_time', "={$time}")
					->sum('clicks');
			}
		}
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		if (strlen($date)) {
			$this->smarty->assign('month', str_replace('-', '年', $date).'月');
		} else {
			$this->smarty->assign('month', date('Y年m月'));
		}
		$this->display();
	}
	private function _subManager($id) {
		$subId = array($id);
		$rs = SQL::share('admin')->where("parent_id='{$id}'")->find('id');
		if ($rs) {
			foreach ($rs as $g) {
				$subId = array_merge($subId, $this->_subManager($g->id));
			}
		}
		return $subId;
	}
	private function _getMonthNum($date1, $date2) {
		$datestamp1 = strtotime($date1);
		$datestamp2 = strtotime($date2);
		list($date_1['y'], $date_1['m']) = explode('-', date('Y-m', $datestamp1));
		list($date_2['y'], $date_2['m']) = explode('-', date('Y-m', $datestamp2));
		return abs($date_1['y']-$date_2['y'])*12 + $date_2['m'] - $date_1['m'];
	}

	//添加管理员
	public function manager_edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$name = $this->request->post('name');
			$password = $this->request->post('password');
			$real_name = $this->request->post('real_name');
			$mobile = $this->request->post('mobile');
			$qq = $this->request->post('qq');
			$weixin = $this->request->post('weixin');
			$status = $this->request->post('status', 0);
			if (!$name) error('账号不能为空');
			$data = array('name'=>$name, 'real_name'=>$real_name, 'mobile'=>$mobile, 'qq'=>$qq, 'weixin'=>$weixin, 'status'=>$status);
			if ($id) {
				if ($password) {
					$salt = generate_salt();
					$password = crypt_password($password, $salt);
					$data['password'] = $password;
					$data['salt'] = $salt;
				}
				$where = "id='{$id}' AND id!='{$this->admin_id}'";
				SQL::share('admin')->where($where)->update($data);
				$url = "?app=setting&act=manager";
			} else {
				if (!$password) error('密码不能为空');
				$count = SQL::share('admin')->where("name='{$name}'")->count();
				if ($count) error('该账号已存在');
				$salt = generate_salt();
				$password = crypt_password($password, $salt);
				$data['password'] = $password;
				$data['salt'] = $salt;
				$data['parent_id'] = $this->admin_id;
				$data['add_time'] = time();
				$id = SQL::share('admin')->insert($data);
				$url = "?app=power&act=edit&id={$id}"; //添加后跳转到权限管理
			}
			location($url);
		} else if ($id) {
			$where = "id='{$id}' AND id!='{$this->admin_id}'";
			$row = SQL::share('admin')->where($where)->row();
			if (!$row) error('账号不存在');
		} else {
			$row = t('admin');
		}
		$this->smarty->assign('row', $row);
		$this->display();
	}

	//删除管理员
	public function manager_delete() {
		$id = $this->request->get('id', 0);
		$name = SQL::share('admin')->where($id)->value('name');
		SQL::share('admin')->delete($id);
		SQL::share('admin_menu')->delete("admin_id='{$id}'");
		SQL::share('admin_permission')->delete("admin_id='{$id}'");
		SQL::share('admin_token')->delete("name='{$name}'");
		location("?app=setting&act=manager");
	}
	
	//运费模板
	public function shipping(){
		$where = "";
		$id = $this->request->get('id');
		$keyword = $this->request->get('keyword');
		if (strlen($id)) {
			$where .= " AND id='{$id}'";
		}
		if ($keyword) {
			$where .= " AND (name LIKE '%{$keyword}%')";
		}
		$rs = SQL::share('shipping_fee')->where($where)->isezr()->setpages(compact('id', 'keyword'))->sort('id ASC')->find();
		$sharepage = SQL::share()->page;
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display();
	}
	
	//修改运费模板
	public function shipping_edit(){
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$name = $this->request->post('name');
			$type = $this->request->post('type', 0);
			$default_first = $this->request->post('default_first', 0);
			$default_first_price = $this->request->post('default_first_price', 0, '0.0');
			$default_second = $this->request->post('default_second', 0);
			$default_second_price = $this->request->post('default_second_price', 0, '0.0');
			$districts = $this->request->post('districts', '', '?');
			$first = $this->request->post('first', '', '?');
			$first_price = $this->request->post('first_price', '', '?');
			$second = $this->request->post('second', '', '?');
			$second_price = $this->request->post('second_price', '', '?');
			$data = compact('name', 'type');
			$data['first'] = $default_first;
			$data['first_price'] = $default_first_price;
			$data['second'] = $default_second;
			$data['second_price'] = $default_second_price;
			if ($id) {
				SQL::share('shipping_fee')->where($id)->update($data);
			} else {
				$data['add_time'] = time();
				$id = SQL::share('shipping_fee')->insert($data);
			}
			SQL::share('shipping_fee_area')->delete("shipping_fee_id='{$id}'");
			if (is_array($districts)) {
				$data = compact('districts', 'first', 'first_price', 'second', 'second_price');
				$data['shipping_fee_id'] = $id;
				SQL::share('shipping_fee_area')->insert($data, 'districts');
			}
			location("?app=setting&act=shipping");
		} else if ($id>0) {
			$row = SQL::share('shipping_fee')->where($id)->row();
		} else {
			$row = t('shipping_fee');
		}
		$this->smarty->assign('row', $row);
		$area = SQL::share('shipping_fee_area')->where("shipping_fee_id='{$row->id}'")->find();
		$this->smarty->assign('area', $area);
		$province = SQL::share('province')->sort('province_id ASC')->find("province_id as id, name, NULL as sub, 0 as subcount");
		if ($province) {
			foreach ($province as $p) {
				$count = 0;
				$city = SQL::share('city')->where("parent_id='{$p->id}'")->sort('city_id ASC')->find("city_id as id, name, NULL as sub, 0 as subcount");
				if ($city) {
					foreach ($city as $c) {
						$district = SQL::share('district')->where("parent_id='{$c->id}'")->sort('district_id ASC')->find("district_id as id, name");
						if (!$district) $district = array();
						$c->sub = $district;
						$c->subcount = count($district);
						$count += $c->subcount + 1;
					}
				} else {
					$city = array();
				}
				$p->sub = $city;
				$p->subcount = $count;
			}
		}
		$this->smarty->assign('province', json_encode($province, JSON_UNESCAPED_UNICODE));
		$this->display();
	}
	
	//复制运费模板
	public function shipping_copy(){
		$id = $this->request->get('id', 0);
		if ($id<=0) error('缺少数据');
		$row = SQL::share('shipping_fee')->where($id)->row();
		if (!$row) error('数据错误');
		$newId = SQL::share('shipping_fee')->insert(array('name'=>$row->name, 'type'=>$row->type, 'add_time'=>time()));
		$rs = SQL::share('shipping_fee_area')->where("shipping_fee_id='{$id}'")->find();
		if ($rs) {
			foreach ($rs as $k=>$g) {
				SQL::share('shipping_fee_area')->insert(array('shipping_fee_id'=>$newId, 'districts'=>$g->districts, 'first'=>$g->first, 'first_price'=>$g->first_price,
					'second'=>$g->second, 'second_price'=>$g->second_price));
			}
		}
		location("?app=setting&act=shipping");
	}
	
	//删除运费模板
	public function shipping_delete(){
		$id = $this->request->get('id', 0);
		SQL::share('shipping_fee')->delete($id);
		SQL::share('shipping_fee_area')->delete("shipping_fee_id='{$id}'");
		location("?app=setting&act=shipping");
	}

	//开放城市
	public function city() {
		$where = '';
		$rs = SQL::share('open_city')->where($where)->isezr()->sort('id DESC')->pagesize(20)->find();
		$sharepage = SQL::share()->page;
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display();
	}

	//开放区域
	public function district() {
		$where = '';
		$id = $this->request->get('id', 0);
		$city_id = $this->request->get('city_id', 0);
		if ($city_id) {
			$where .= " AND city_id='{$city_id}'";
		}
		$rs = SQL::share('open_district')->where($where)->isezr()->setpages(compact('id', 'city_id'))->sort('id DESC')->pagesize(20)->find();
		$sharepage = SQL::share()->page;
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		if ($id) {
			$row = SQL::share('open_district')->where($id)->row();
			$this->smarty->assign('row', $row);
		} else {
			$row = new stdClass();
			$row->name = '';
			$row->sort = 999;
			$row->district_id = '';
			$this->smarty->assign('row', $row);
		}
		$this->display();
	}
	//添加开放区域
	public function district_add() {
		$this->district_edit();
	}
	//编辑开放区域
	public function district_edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$city_id = $this->request->post('city_id', 0);
			$district_id = $this->request->post('district_id', 0);
			$name = $this->request->post('name');
			$sort = $this->request->post('sort');
			if ($id) {
				SQL::share('open_district')->where($id)->update(array('name'=>$name, 'sort'=>$sort, 'city_id'=>$city_id, 'district_id'=>$district_id));
			} else {
				$id = SQL::share('open_district')->insert(array('name'=>$name, 'sort'=>$sort, 'city_id'=>$city_id, 'district_id'=>$district_id));
			}
			header("Location:?app=setting&act=district");
		} else if ($id>0) {
			$row = SQL::share('open_district')->where($id)->row();
		} else {
			$row = t('open_district');
		}
		$this->smarty->assign('row', $row);
		$this->display('setting.district_edit');
	}
	//删除区域
	public function district_delete() {
		$id = $this->request->get('id', 0);
		$city_id = $this->request->get('city_id', 0);
		SQL::share('open_district')->delete($id);
		header("Location:?app=setting&act=district&city_id={$city_id}");
	}

	//参数配置
	public function configs() {
		if (IS_PUT) {
			$id = $this->request->put('id', 0);
			$content = $this->request->put('content');
			SQL::share('config')->where($id)->update(compact('content'));
			success('ok');
		}
		$where = ' AND status=1';
		$name = $this->request->get('name');
		$memo = $this->request->get('memo');
		if ($name) {
			$where .= " AND name LIKE '%{$name}%'";
		}
		if ($memo) {
			$where .= " AND memo LIKE '%{$memo}%'";
		}
		$rs = SQL::share('config')->where("status=1 AND parent_id=0 {$where}")->isezr()->setpages(compact('name', 'memo'))->sort('`group` ASC, id ASC')->pagesize(20)->find("*, 0 as is_image, '' as image");
		$sharepage = SQL::share()->page;
		if ($rs) {
			foreach ($rs as $row) {
				$memo = cut_str($row->memo, 100);
				$row->memo = $memo;
				if ($row->type=='file') {
					$is_image = is_image($row->content) ? 1 : 0;
					$row->is_image = $is_image;
					$row->image = $row->content;
					//$row->content = $is_image ? add_domain($row->content) : $row->content;
				} else if (stripos($row->type, 'checkbox')!==false) {
					$row->content = intval($row->content)==1 ? '<font class="fa fa-check"></font>' : '<font class="fa fa-close"></font>';
				} else if (stripos($row->type, 'radio')!==false || stripos($row->type, 'select')!==false || stripos($row->type, 'switch')!==false) {
					$con = explode('|', $row->type);
					$con = explode('#', $con[1]);
					foreach ($con as $h) {
						$g = explode(':', $h);
						if ($row->content==$g[0]) {
							$row->content = $g[1];
							break;
						}
					}
				} else if (stripos($row->type, 'color')!==false) {
					$row->content = $row->content.'<div class="some-color" style="background:'.$row->content.';"></div>';
				} else if (strlen($row->content)>100) {
					$content = preg_match('/^https?:\/\//', $row->content) ? '<a href="'.$row->content.'" target="_blank">'.cut_str($row->content, 100).'</a>' : cut_str($row->content, 100);
					$row->content = $content;
				} else {
					$row->content = '<div class="some-edit" data-id="'.$row->id.'" data-field="content">'.$row->content.'</div>';
				}
			}
		}
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display();
	}

	//参数修改
	public function configs_edit() {
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$origin_content = $this->request->post('origin_content');
			$content = $this->request->post('content');
			$name = $this->request->post('name');
			//$memo = $this->request->post('memo');
			if ($id>0) {
				if (SQL::share('config')->where("id!='{$id}' AND name='{$name}'")->count()) error('该参数名称已存在');
				$wxapi = new wechatCallbackAPI();
				$row = SQL::share('config')->where($id)->row('name, type');
				if ($row->type=='file') {
					$content = (isset($_FILES['content']) && strlen($_FILES['content']['name'])) ? $_FILES['content'] : '';
					if (!$content) {
						$content = $origin_content;
					} else {
						$content = $this->request->file('pic', 'content', UPLOAD_LOCAL);
						if (stripos($row->name, 'MEDIAID')!==false && strlen($content)) {
							//$_content = $wxapi->setMedia(PUBLIC_PATH.$content, $miniprogram->appid);
							//$content = $_content . '|' . $content . '|' . time();
						}
					}
				} else if (stripos($row->type, 'checkbox')!==false) {
					$content = intval($content);
				} else if (stripos($row->type, 'select')!==false || stripos($row->type, 'switch')!==false) {
					$content = trim($content);
					$subconfig = SQL::share('config')->where("parent_id='{$id}'")->find('id, name, type');
					if ($subconfig) {
						foreach ($subconfig as $g) {
							$_origin_content = $this->request->post("origin_{$g->name}");
							$_content = $this->request->post($g->name);
							if ($g->type=='file') {
								$_content = (isset($_FILES[$g->name]) && strlen($_FILES[$g->name]['name'])) ? $_FILES[$g->name] : '';
								if (!$_content) {
									$_content = $_origin_content;
								} else {
									$_content = $this->request->file('pic', $g->name, UPLOAD_LOCAL);
									if (stripos($g->name, 'MEDIAID')!==false && strlen($_content)) {
										//$__content = $wxapi->setMedia(PUBLIC_PATH.$_content, $miniprogram->appid);
										//$_content = $__content . '|' . $_content . '|' . time();
									}
								}
							} else if (stripos($g->type, 'checkbox')!==false) {
								$_content = intval($_content);
							} else {
								$_content = trim($_content);
							}
							SQL::share('config')->where($g->id)->update(compact('_content'));
						}
					}
				} else {
					$content = trim($content);
				}
				//SQL::share('config')->where($id)->update(compact('name', 'memo', 'content'));
				SQL::share('config')->where($id)->update(compact('content'));
			}
			location("?app=setting&act=configs_edit&id={$id}&msg=1");
		} else {
			$msg = isset($_GET['msg']) ? 1 : 0;
			$id = $this->request->get('id', 0);
			$subcontent = function($id, $parentId=0) use (&$subcontent) {
				if (!$parentId) {
					$where = $id;
					$fn = 'row';
				} else {
					$where = "status=1 AND parent_id={$parentId}";
					$fn = 'find';
				}
				$rs = SQL::share('config')->where($where)->$fn("*, '' as placeholder, 0 as is_image, '' as image, '' as file_attr, NULL as subconfig");
				if ($rs) {
					if (!$parentId) $rs = array($rs);
					foreach ($rs as $row) {
						$row->memo = str_replace('<font ', '<font style="float:none;" ' ,$row->memo);
						if (stripos($row->memo, '，')!==false || stripos($row->memo, ',')!==false) {
							$comma = stripos($row->memo, '，')!==false ? '，' : ',';
							$offset = stripos($row->memo, '，')!==false ? 3 : 1;
							$row->placeholder = substr($row->memo, stripos($row->memo, $comma)+$offset);
							$row->memo = substr($row->memo, 0, stripos($row->memo, $comma));
						}
						if ($row->type=='file') {
							if (stripos($row->name, 'MEDIAID')!==false) {
								$row->is_image = 1;
								if (strlen($row->content)) $row->image = "/gm/api/setting/configs_media?id={$row->id}";
								$row->file_attr = 'data-maxsize="2097152"';
							} else {
								$is_image = is_image($row->content) ? 1 : 0;
								$row->is_image = $is_image;
								$row->image = $row->content;
								//$row->content = $is_image ? add_domain($row->content) : $row->content;
							}
						} else if (stripos($row->type, 'radio')!==false || stripos($row->type, 'checkbox')!==false || stripos($row->type, 'select')!==false || stripos($row->type, 'switch')!==false) {
							//[radio|checkbox|select|switch]|值1:字1#值2:字2
							$con = explode('|', $row->type);
							$type = $con[0];
							if ($type=='checkbox') {
								$content = '<input value="1" name="'.($parentId==0?'content':$row->name).'" type="checkbox" data-type="app" data-style="margin-top:5px;" '.(intval($row->content)==1?'checked':'').' />';
							} else {
								$con = explode('#', $con[1]);
								$content = '';
								if ($type=='select') {
									$content .= '<select name="'.($parentId==0?'content':$row->name).'" class="some-select-'.($parentId==0?'content':$row->name).'">';
								} else if ($type=='switch') {
									$content .= '<span class="some-switch some-switch-'.($parentId==0?'content':$row->name).'">';
								}
								foreach ($con as $h) {
									$g = explode(':', $h);
									if ($type=='radio') {
										$content .= '<input value="'.$g[0].'" name="'.($parentId==0?'content':$row->name).'" type="radio" data-type="ace" data-text="'.$g[1].'" '.($row->content==$g[0]?'checked':'').' />';
									} else if ($type=='select') {
										$content .= '<option value="'.$g[0].'" '.($row->content==$g[0]?'selected':'').'>'.$g[1].'</option>';
									} else if ($type=='switch') {
										$content .= '<label><input type="radio" name="'.($parentId==0?'content':$row->name).'" value="'.$g[0].'" '.($row->content==$g[0]?'checked':'').' /><div>'.$g[1].'</div></label>';
									}
								}
								if ($type=='select') {
									$content .= '</select>';
									$subconfig = $subcontent(0, $row->id);
									if ($subconfig) {
										$content .= '<script>
$(function(){
	$(".some-select-'.($parentId==0?'content':$row->name).'").on("change", function(){
		$("[data-parent'.$row->id.'-value]").css("display", "none");
		$("[data-parent'.$row->id.'-value*=\',"+$(this).selected().val()+",\']").css("display", "block");
	}).trigger("change");
});
</script>';
									}
									$row->subconfig = $subconfig;
								} else if ($type=='switch') {
									$content .= '</span>';
									$subconfig = $subcontent(0, $row->id);
									if ($subconfig) {
										$content .= '<script>
$(function(){
	$(".some-switch-'.($parentId==0?'content':$row->name).' :radio").on("change", function(){
		$("[data-parent'.$row->id.'-value]").css("display", "none");
		$("[data-parent'.$row->id.'-value*=\',"+$(this).parent().parent().find(":checked").val()+",\']").css("display", "block");
	});
	$(".some-switch-'.($parentId==0?'content':$row->name).' :checked").trigger("change");
});
</script>';
									}
									$row->subconfig = $subconfig;
								}
							}
							$row->type = $type;
							$row->parse_content = $content;
						} else if (stripos($row->type, 'color')!==false) {
							$row->parse_content = '<input value="'.$row->content.'" name="'.($parentId==0?'content':$row->name).'" type="text" /><div class="some-color" style="background:'.$row->content.';"></div>';
						} else if ((stripos($row->type, 'input')!==false || stripos($row->type, 'textarea')!==false) && stripos($row->type, '|')!==false) {
							$con = explode('|', $row->type);
							$row->placeholder = $con[1];
						}
						$row->placeholder = str_replace('"', '&#34', $row->placeholder);
					}
					if (!$parentId) $rs = $rs[0];
				}
				return $rs;
			};
			$row = $subcontent($id);
			$this->smarty->assign("row", $row);
			$this->smarty->assign("msg", $msg);
			$this->display();
		}
	}
	
	//操作日志
	public function log() {
		if ($this->admin_super==0) {
			$ids = $this->_subManager($this->admin_id);
			$ids = implode(',', $ids);
			$where = "user_id IN ({$ids})";
		} else {
			$where = '';
		}
		$type = $this->request->get('type');
		$content = $this->request->get('content');
		$ip = $this->request->get('ip');
		if (strlen($type)) {
			$where .= " AND type='{$type}'";
		}
		if ($content) {
			$where .= " AND content LIKE '%{$content}%'";
		}
		if ($ip) {
			$where .= " AND ip='{$ip}'";
		}
		$rs = SQL::share('access_log')->where($where)->sort('id DESC')
			->setpages(compact('type', 'content', 'ip'))->isezr()->pagesize(20)->find();
		$sharepage = SQL::share()->page;
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display();
	}
	
	//访问路由
	public function router() {
		$where = '';
		$type = $this->request->get('type');
		$member_name = $this->request->get('member_name');
		$router_app = $this->request->get('router_app');
		$router_act = $this->request->get('router_act');
		$ip = $this->request->get('ip');
		if (strlen($type)) {
			$where .= " AND type='{$type}'";
		}
		if ($member_name) {
			$where .= " AND member_name='{$member_name}'";
		}
		if ($router_app) {
			$where .= " AND app='{$router_app}'";
		}
		if ($router_act) {
			$where .= " AND act='{$router_act}'";
		}
		if ($ip) {
			$where .= " AND ip='{$ip}'";
		}
		$rs = SQL::share('router_log')->where($where)->sort('id DESC')
			->setpages(compact('type', 'member_name', 'router_app', 'router_act', 'ip'))->isezr()->pagesize(20)->find();
		$sharepage = SQL::share()->page;
		/*
		if ($rs) {
			foreach ($rs as $k => $g) {
				$app_name = $log_mod->convert_app_name($g->app);
				$act_name = $log_mod->convert_act_name($g->act);
				if (!$app_name) $app_name = $g->app;
				if (!$act_name) $act_name = $g->act;
				$rs[$k]->app = "{$app_name}";
				$rs[$k]->act = "{$act_name}";
			}
		}
		*/
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display();
	}
	
	//清除缓存
	public function clear() {
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		/*//
		$path = ROOT_PATH.'/temp';
		$handle = dir($path);
		while ($entry=$handle->read()) {
			if ($entry=='.' || $entry=='..') continue;
			if (!is_dir($path.'/'.$entry) || strpos($entry, 'wx')===false) continue;
			$path2 = $path.'/'.$entry;
			$handle2 = dir($path2);
			while ($entry2=$handle2->read()) {
				if ($entry2=='.' || $entry2=='..') continue;
				if (!is_dir($path2.'/'.$entry2) || strpos($entry2, 'wx')===false) continue;
				$row = SQL::share('miniprogram')->where("appid='{$entry2}'")->row();
				if (!$row) delete_folder($path2.'/'.$entry2);
			}
		}
		echo 'OK';
		exit;
		//*/
		SQL::share()->clearCached();
		delete_folder('/temp/cache/api');
		$tables = ['article', 'blessing', 'buddha', 'video'];
		foreach ($tables as $t) {
			SQL::share()->query("DELETE FROM sc_{$t}_attr_id WHERE id NOT IN (SELECT id FROM sc_{$t}_attr_0)");
		}
		success('ok');
	}
}
