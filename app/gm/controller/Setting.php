<?php
declare (strict_types = 1);

namespace app\gm\controller;

use think\facade\Db;
use think\facade\View;
use think\Request;

class Setting extends Core
{
	public function index() {
		location('/setting/manager');
	}
	
	//管理员列表
	public function manager() {
		if ($this->admin_super==0) {
			$ids = $this->_subManager($this->admin_id);
			$ids = implode(',', $ids);
			$where = [
				['id', 'in', $ids],
				['id', '<>', $this->admin_id]
			];
		} else {
			$where = [];
		}
		$keyword = $this->request->get('keyword');
		$date = $this->request->get('date');
		if ($keyword) {
			$where[] = ['name|real_name', 'like', $keyword];
		}
		$time = $date;
		if (!strlen($time)) $time = date('Y-m');
		$time = $this->_getMonthNum($time.'-01', date('Y-m-1'));
		$rs = Db::name('admin')->where($where)->order('id ASC')->field('*, 0 as clicks')->paginate(['list_rows'=>10, 'query'=>request()->param()])->each(function($item) use ($time) {
			$item['clicks'] = Db::name('admin_miniprogram_article')->alias('ama')
				->leftJoin('admin_miniprogram am', 'am.miniprogram_id=ama.miniprogram_id')
				->where('am.admin_id', $item['id'])->whereRaw(whereTime('m', 'ama.add_time', "={$time}"))
				->sum('clicks');
			return $item;
		});
		View::assign('rs', $rs);
		setViewAssign(compact('keyword', 'date'));
		setViewPage($rs);
		View::assign('is_power_edit', core::check_permission('power', 'edit'));
		if (strlen($date)) {
			View::assign('month', str_replace('-', '年', $date).'月');
		} else {
			View::assign('month', date('Y年m月'));
		}
		return success('ok', 'manager.html');
	}
	private function _subManager($id) {
		$subId = array($id);
		$rs = Db::name('admin')->where('parent_id', $id)->field('id')->select();
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
			if (!$name) return error('账号不能为空');
			$data = compact('name', 'real_name', 'mobile', 'qq', 'weixin', 'status');
			if ($id) {
				if (strlen($password)) {
					$salt = generate_salt();
					$password = crypt_password($password, $salt);
					$data['password'] = $password;
					$data['salt'] = $salt;
				}
				Db::name('admin')->where([
					['id', '=', $id],
					['id', '<>', $this->admin_id]
				])->update($data);
				$url = "/setting/manager";
			} else {
				if (!$password) return error('密码不能为空');
				if (Db::name('admin')->where('name', $name)->count() > 0) return error('该账号已存在');
				$salt = generate_salt();
				$password = crypt_password($password, $salt);
				$data['password'] = $password;
				$data['salt'] = $salt;
				$data['parent_id'] = $this->admin_id;
				$data['add_time'] = time();
				$id = Db::name('admin')->insert($data);
				$url = "/power/edit?id={$id}"; //添加后跳转到权限管理
			}
			location($url);
		} else if ($id>0) {
			$row = Db::name('admin')->where([
				['id', '=', $id],
				['id', '<>', $this->admin_id]
			])->find();
			if (!$row) return error('账号不存在');
		} else {
			$row = t('admin');
		}
		View::assign('row', $row);
		return success();
	}
	
	//删除管理员
	public function manager_delete() {
		$id = $this->request->get('id', 0);
		$name = Db::name('admin')->where('id', $id)->value('name');
		Db::name('admin')->delete($id);
		Db::name('admin_menu')->where('admin_id', $id)->delete();
		Db::name('admin_permission')->where('admin_id', $id)->delete();
		Db::name('admin_token')->where('name', $name)->delete();
		location("/setting/manager");
	}
	
	public function power() {
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$status = $this->request->post('status', 0);
			$menu = $this->request->post('menu', '', 'origin');
			$operate = $this->request->post('operate', '', 'origin');
			$super = Db::name('admin')->where('id', $id)->cache(60*60*24*7)->value('super');
			if ($super!=1) {
				Db::name('admin')->where('id', $id)->update(array('status'=>$status));
				//菜单权限
				Db::name('admin_menu')->where('admin_id', $id)->delete();
				if ($menu) {
					$data = array();
					foreach ($menu as $g) {
						if ($g<=0) continue;
						$data[] = ['admin_id'=>$id, 'menu_id'=>$g];
					}
					Db::name('admin_menu')->insertAll($data);
				}
				//操作权限
				Db::name('admin_permission')->where('admin_id', $id)->delete();
				if ($operate) {
					$data = array();
					foreach ($operate as $g) {
						if (!strlen($g)) continue;
						$appact = explode('|', $g);
						$app[] = $appact[0];
						$act[] = $appact[1];
						$data[] = ['admin_id'=>$id, 'app'=>$appact[0], 'act'=>$appact[1]];
					}
					Db::name('admin_permission')->insertAll($data);
				}
			}
			deletedir('/runtime/cache', false);
			location('/setting/manager');
		}
		$row = Db::name('admin')->where('id', $id)->find();
		$pa = $this->menu($id, 0);
		
		$permission = Db::name('admin_permission')->where('admin_id', $id)->select()->toArray();
		$_permission = Db::name('admin_permission')->where('admin_id', $this->admin_id)->select()->toArray();
		$rs = Db::name('menu')->where(['parent_id'=>0, 'status'=>1, 'is_menu'=>0, 'is_op'=>0])->order(['sort', 'id'=>'ASC'])->select()->toArray();
		if ($rs) {
			foreach ($rs as $k => $g) {
				if (preg_match('/^[a-z,]+$/', $g['edition'])) {
					$nonShow = false;
					$editions = explode(',', $g['edition']);
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
				if ($g['app'] == 'weixin' && ((defined('WX_TAKEOVER') && WX_TAKEOVER == 0) || (defined('WX_TOKEN') && !strlen(WX_TOKEN)) || (defined('WX_AESKEY') && !strlen(WX_AESKEY)))) {
					unset($rs[$k]);
					continue;
				}
				$hasMenu = false;
				if ($_permission) {
					foreach ($_permission as $m => $n) {
						if ($n['app'] == $g['app'] && $n['act'] == $g['act']) {
							$hasMenu = true;
							break;
						}
					}
				} else if ($this->admin_super == 1) {
					$hasMenu = true;
				}
				if (!$hasMenu) {
					unset($rs[$k]);
					continue;
				}
				if ($row['super'] == 1) {
					$rs[$k]['checked'] = 'checked';
					continue;
				}
				if ($permission) {
					foreach ($permission as $j => $d) {
						if ($d['app'] == $g['app'] && $d['act'] == $g['act']) {
							$rs[$k]['checked'] = 'checked';
						}
					}
				}
			}
			$rs = array_values($rs);
		}
		
		View::assign('row', $row);
		View::assign('pa', $pa);
		View::assign('operate', $rs);
		
		return success();
	}
	
	//参数配置
	public function configs() {
		if (IS_PUT) {
			$id = $this->request->put('id', 0);
			$content = $this->request->put('content');
			Db::name('config')->where('id', $id)->update(compact('content'));
			return success('ok');
		}
		$where[] = ['status', '=', 1];
		$where[] = ['parent_id', '=', 0];
		$name = $this->request->get('name');
		$memo = $this->request->get('memo');
		if (strlen($name)) {
			$where[] = ['name', 'like', "%{$name}%"];
		}
		if (strlen($memo)) {
			$where[] = ['memo', 'like', "%{$memo}%"];
		}
		$rs = Db::name('config')->where($where)->order(['group', 'id'=>'ASC'])->field("*, 0 as is_image, '' as image")->paginate(['list_rows'=>20, 'query'=>request()->param()])->each(function($row) {
			$memo = cut_str($row['memo'], 100);
			$row['memo'] = $memo;
			if (is_null($row['type'])) $row['type'] = '';
			if ($row['type'] == 'file') {
				$is_image = is_image($row['content']) ? 1 : 0;
				$row['is_image'] = $is_image;
				$row['image'] = $row['content'];
				//$row['content'] = $is_image ? add_domain($row['content']) : $row['content'];
			} else if (stripos($row['type'], 'checkbox') !== false) {
				$row['content'] = intval($row['content']) == 1 ? '<font class="fa fa-check"></font>' : '<font class="fa fa-close"></font>';
			} else if (stripos($row['type'], 'radio') !== false || stripos($row['type'], 'select') !== false || stripos($row['type'], 'switch') !== false) {
				$con = explode('|', $row['type']);
				$con = explode('#', $con[1]);
				foreach ($con as $h) {
					$g = explode(':', $h);
					if ($row['content'] == $g[0]) {
						$row['content'] = $g[1];
						break;
					}
				}
			} else if (stripos($row['type'], 'color') !== false) {
				$row['content'] = $row['content'].'<div class="some-color" style="background:'.$row['content'].';"></div>';
			} else if (strlen($row['content']) > 100) {
				$content = preg_match('/^https?:\/\//', $row['content']) ? '<a href="'.$row['content'].'" target="_blank">'.cut_str($row['content'], 100).'</a>' : cut_str($row['content'], 100);
				$row['content'] = $content;
			} else {
				$row['content'] = '<div class="some-edit" data-id="'.$row['id'].'" data-field="content">'.$row['content'].'</div>';
			}
			return $row;
		});
		setViewAssign(compact('name', 'memo'));
		setViewPage($rs);
		return success($rs);
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
				if (Db::name('config')->where([
						['id', '<>', $id],
						['name', '=', $name]
					])->count() > 0) return error('该参数名称已存在');
				$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
				$row = Db::name('config')->where('id', $id)->field('name, type')->find();
				if ($row['type'] == 'file') {
					$content = (isset($_FILES['content']) && strlen($_FILES['content']['name'])) ? $_FILES['content'] : '';
					if (!$content) {
						$content = $origin_content;
					} else {
						$content = $this->request->file('content', 'pic', UPLOAD_THIRD);
						if (stripos($row->name, 'MEDIAID') !== false && strlen($content)) {
							//$_content = $wxapi->setMedia(PUBLIC_PATH.$content, $miniprogram->appid);
							//$content = $_content . '|' . $content . '|' . time();
						}
					}
				} else if (stripos($row['type'], 'checkbox') !== false) {
					$content = intval($content);
				} else if (stripos($row['type'], 'select') !== false || stripos($row['type'], 'switch') !== false) {
					$content = trim($content);
					$subconfig = Db::name('config')->where('parent_id', $id)->field('id, name, type')->select();
					if ($subconfig) {
						foreach ($subconfig as $g) {
							$_origin_content = $this->request->post("origin_{$g['name']}");
							$_content = $this->request->post($g['name']);
							if ($g['type'] == 'file') {
								$_content = (isset($_FILES[$g['name']]) && strlen($_FILES[$g['name']]['name'])) ? $_FILES[$g['name']] : '';
								if (!$_content) {
									$_content = $_origin_content;
								} else {
									$_content = $this->request->file($g['name'], 'pic', UPLOAD_THIRD);
									if (stripos($g['name'], 'MEDIAID') !== false && strlen($_content)) {
										//$__content = $wxapi->setMedia(PUBLIC_PATH.$_content, $miniprogram->appid);
										//$_content = $__content . '|' . $_content . '|' . time();
									}
								}
							} else if (stripos($g['type'], 'checkbox') !== false) {
								$_content = intval($_content);
							} else {
								$_content = trim($_content);
							}
							Db::name('config')->where('id', $g['id'])->update(compact('_content'));
						}
					}
				} else {
					$content = trim($content);
				}
				//Db::name('config')->where('id', $id)->update(compact('name', 'memo', 'content'));
				Db::name('config')->where('id', $id)->update(compact('content'));
			}
			location("/setting/configs_edit?id={$id}&msg=1");
		}
		$msg = $this->request->get('msg', 0);
		$id = $this->request->get('id', 0);
		$subcontent = function($id, $parentId=0) use (&$subcontent) {
			if (!$parentId) {
				$where = ['id' => $id];
				$fn = 'findOrEmpty';
			} else {
				$where = ['status'=>1, 'parent_id'=>$parentId];
				$fn = 'select';
			}
			$rs = Db::name('config')->where($where)->field("*, '' as placeholder, 0 as is_image, '' as image, '' as file_attr, NULL as subconfig")->$fn();
			if ($rs) {
				if (!$parentId) {
					$rs = array($rs);
				} else if ($fn == 'select') {
					$rs = $rs->toArray();
				}
				foreach ($rs as $k => $row) {
					if (is_null($row['type'])) $rs[$k]['type'] = '';
					$rs[$k]['memo'] = str_replace('<font ', '<font style="float:none;" ' ,$row['memo']);
					if (stripos($row['memo'], '，') !== false || stripos($row['memo'], ',') !== false) {
						$comma = stripos($row['memo'], '，') !== false ? '，' : ',';
						$offset = stripos($row['memo'], '，') !== false ? 3 : 1;
						$rs[$k]['placeholder'] = substr($row['memo'], stripos($row['memo'], $comma)+$offset);
						$rs[$k]['memo'] = substr($row['memo'], 0, stripos($row['memo'], $comma));
					}
					if ($row['type'] == 'file') {
						if (stripos($row['name'], 'MEDIAID') !== false) {
							$rs[$k]['is_image'] = 1;
							if (strlen($row['content'])) $rs[$k]['image'] = "/setting/configs_media?id={$row['id']}";
							$rs[$k]['file_attr'] = 'data-maxsize="2097152"';
						} else {
							$is_image = is_image($row['content']) ? 1 : 0;
							$rs[$k]['is_image'] = $is_image;
							$rs[$k]['image'] = $row['content'];
							//$row['content'] = $is_image ? add_domain($row['content']) : $row['content'];
						}
					} else if (stripos($row['type'], 'radio') !== false || stripos($row['type'], 'checkbox') !== false || stripos($row['type'], 'select') !== false || stripos($row['type'], 'switch') !== false) {
						//[radio|checkbox|select|switch]|值1:字1#值2:字2
						$con = explode('|', $row['type']);
						$type = $con[0];
						if ($type == 'checkbox') {
							$content = '<input value="1" name="'.($parentId==0?'content':$row['name']).'" type="checkbox" data-type="app" data-style="margin-top:5px;" '.(intval($row['content'])==1?'checked':'').' />';
						} else {
							$con = explode('#', $con[1]);
							$content = '';
							if ($type == 'select') {
								$content .= '<select name="'.($parentId==0?'content':$row['name']).'" class="some-select-'.($parentId==0?'content':$row['name']).'">';
							} else if ($type == 'switch') {
								$content .= '<span class="some-switch some-switch-'.($parentId==0?'content':$row['name']).'">';
							}
							foreach ($con as $h) {
								$g = explode(':', $h);
								if ($type == 'radio') {
									$content .= '<input value="'.$g[0].'" name="'.($parentId==0?'content':$row['name']).'" type="radio" data-type="ace" data-text="'.$g[1].'" '.($row['content']==$g[0]?'checked':'').' />';
								} else if ($type == 'select') {
									$content .= '<option value="'.$g[0].'" '.($row['content']==$g[0]?'selected':'').'>'.$g[1].'</option>';
								} else if ($type == 'switch') {
									$content .= '<label><input type="radio" name="'.($parentId==0?'content':$row['name']).'" value="'.$g[0].'" '.($row['content']==$g[0]?'checked':'').' /><div>'.$g[1].'</div></label>';
								}
							}
							if ($type == 'select') {
								$content .= '</select>';
								$subconfig = $subcontent(0, $row['id']);
								if ($subconfig) {
									$content .= '<script>
$(function(){
	$(".some-select-'.($parentId==0?'content':$row['name']).'").on("change", function(){
		$("[data-parent'.$row['id'].'-value]").css("display", "none");
		$("[data-parent'.$row['id'].'-value*=\',"+$(this).selected().val()+",\']").css("display", "block");
	}).trigger("change");
});
</script>';
								}
								$rs[$k]['subconfig'] = $subconfig;
							} else if ($type == 'switch') {
								$content .= '</span>';
								$subconfig = $subcontent(0, $row['id']);
								if ($subconfig) {
									$content .= '<script>
$(function(){
	$(".some-switch-'.($parentId==0?'content':$row['name']).' :radio").on("change", function(){
		$("[data-parent'.$row['id'].'-value]").css("display", "none");
		$("[data-parent'.$row['id'].'-value*=\',"+$(this).parent().parent().find(":checked").val()+",\']").css("display", "block");
	});
	$(".some-switch-'.($parentId==0?'content':$row['name']).' :checked").trigger("change");
});
</script>';
								}
								$rs[$k]['subconfig'] = $subconfig;
							}
						}
						$rs[$k]['type'] = $type;
						$rs[$k]['parse_content'] = $content;
					} else if (stripos($row['type'], 'color') !== false) {
						$rs[$k]['parse_content'] = '<input value="'.$row['content'].'" name="'.($parentId==0?'content':$row['name']).'" type="text" /><div class="some-color" style="background:'.$row['content'].';"></div>';
					} else if ((stripos($row['type'], 'input') !== false || stripos($row['type'], 'textarea') !== false) && stripos($row['type'], '|') !== false) {
						$con = explode('|', $row['type']);
						$rs[$k]['placeholder'] = $con[1];
					}
					$rs[$k]['placeholder'] = str_replace('"', '&#34', $row['placeholder']);
					if (is_null($row['subconfig'])) $rs[$k]['subconfig'] = array();
				}
				if (!$parentId) $rs = $rs[0];
			}
			return $rs;
		};
		$row = $subcontent($id);
		View::assign('row', $row);
		View::assign('msg', $msg);
		return success();
	}
	
	//运费模板
	public function shipping() {
		$where = [];
		$id = $this->request->get('id');
		$keyword = $this->request->get('keyword');
		if (strlen($id)) {
			$where[] = ['id', '=', $id];
		}
		if (strlen($keyword)) {
			$where[] = ['name', 'like', "%{$keyword}%"];
		}
		$rs = Db::name('shipping_fee')->where($where)->order('id', 'ASC')->paginate(['list_rows'=>10, 'query'=>request()->param()]);
		View::assign('rs', $rs);
		setViewAssign(compact('id', 'keyword'));
		return success();
	}
	
	//修改运费模板
	public function shipping_edit() {
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
				Db::name('shipping_fee')->where('id', $id)->update($data);
			} else {
				$data['add_time'] = time();
				$id = Db::name('shipping_fee')->insert($data);
			}
			Db::name('shipping_fee_area')->where('shipping_fee_id', $id)->delete();
			if (is_array($districts)) {
				$data = [];
				foreach ($districts as $d) {
					$data[] = compact('first', 'first_price', 'second', 'second_price');
					$data['districts'] = $d;
					$data['shipping_fee_id'] = $id;
				}
				Db::name('shipping_fee_area')->insertAll($data);
			}
			location("/setting/shipping");
		} else if ($id>0) {
			$row = Db::name('shipping_fee')->where('id', $id)->find();
		} else {
			$row = t('shipping_fee');
		}
		View::assign('row', $row);
		$area = Db::name('shipping_fee_area')->where('shipping_fee_id', $row['id'])->select();
		View::assign('area', $area);
		$province = Db::name('province')->order('province_id', 'ASC')->field("province_id as id, name, NULL as sub, 0 as subcount")->select();
		if ($province) {
			foreach ($province as $p) {
				$count = 0;
				$city = Db::name('city')->where("parent_id='{$p['id']}'")->order('city_id', 'ASC')->field("city_id as id, name, NULL as sub, 0 as subcount")->select();
				if ($city) {
					foreach ($city as $c) {
						$district = Db::name('district')->where('parent_id', $c['id'])->order('district_id', 'ASC')->field("district_id as id, name")->select();
						if (!$district) $district = array();
						$c['sub'] = $district;
						$c['subcount'] = count($district);
						$count += $c['subcount'] + 1;
					}
				} else {
					$city = array();
				}
				$p['sub'] = $city;
				$p['subcount'] = $count;
			}
		}
		View::assign('province', json_encode($province, JSON_UNESCAPED_UNICODE));
		return success();
	}
	
	//复制运费模板
	public function shipping_copy() {
		$id = $this->request->get('id', 0);
		if ($id<=0) return error('缺少数据');
		$row = Db::name('shipping_fee')->where('id', $id)->find();
		if (!$row) return error('数据错误');
		$newId = Db::name('shipping_fee')->insert(array('name'=>$row['name'], 'type'=>$row['type'], 'add_time'=>time()));
		$rs = Db::name('shipping_fee_area')->where('shipping_fee_id', $id)->select();
		if ($rs) {
			foreach ($rs as $g) {
				Db::name('shipping_fee_area')->insert(array('shipping_fee_id'=>$newId, 'districts'=>$g['districts'], 'first'=>$g['first'], 'first_price'=>$g['first_price'],
					'second'=>$g['second'], 'second_price'=>$g['second_price']));
			}
		}
		location("/setting/shipping");
	}
	
	//删除运费模板
	public function shipping_delete() {
		$id = $this->request->get('id', 0);
		Db::name('shipping_fee')->delete($id);
		Db::name('shipping_fee_area')->where('shipping_fee_id', $id)->delete();
		location("/setting/shipping");
	}
	
	//开放城市
	public function city() {
		$where = [];
		$rs = Db::name('open_city')->where($where)->order('id', 'DESC')->limit(20)->paginate(['list_rows'=>10, 'query'=>request()->param()]);
		View::assign('rs', $rs);
		return success();
	}
	
	//开放区域
	public function district() {
		$where = [];
		$id = $this->request->get('id', 0);
		$city_id = $this->request->get('city_id', 0);
		if ($city_id) {
			$where[] = ['city_id', '=', $city_id];
		}
		$rs = Db::name('open_district')->where($where)->order('id DESC')->limit(20)->paginate(['list_rows'=>10, 'query'=>request()->param()]);
		View::assign('rs', $rs);
		setViewAssign(compact('id', 'city_id'));
		if ($id) {
			$row = Db::name('open_district')->where('id', $id)->find();
			View::assign('row', $row);
		} else {
			$row = array();
			$row['name'] = '';
			$row['sort'] = 999;
			$row['district_id'] = '';
			View::assign('row', $row);
		}
		return success();
	}
	//添加开放区域
	public function district_add() {
		return $this->district_edit();
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
			$data = compact('name', 'sort', 'city_id', 'district_id');
			if ($id) {
				Db::name('open_district')->where('id', $id)->update($data);
			} else {
				$id = Db::name('open_district')->insertGetId($data);
			}
			location('/setting/district');
		} else if ($id>0) {
			$row = Db::name('open_district')->where('id', $id)->find();
		} else {
			$row = t('open_district');
		}
		View::assign('row', $row);
		return success('ok', 'district_edit.html');
	}
	//删除区域
	public function district_delete() {
		$id = $this->request->get('id', 0);
		$city_id = $this->request->get('city_id', 0);
		Db::name('open_district')->delete($id);
		location("/setting/district?city_id={$city_id}");
	}
	
	//客户反馈
	public function feedback() {
		$where = [];
		$member_id = $this->request->get('member_id');
		$keyword = $this->request->get('keyword');
		if (strlen($keyword)) {
			$where[] = ['f.name|f.content', 'like', "%{$keyword}%"];
		}
		if (strlen($member_id)) {
			$where[] = ['member_id', '=', $member_id];
		}
		$rs = Db::name('feedback')->alias('f')
			->leftJoin('member m', 'f.member_id=m.id')
			->where($where)->order('f.id', 'DESC')->field("f.*, m.name as member_name, '' as miniprogram_type, '' as pic, '' as miniprogram_name, '' as parent_name, '' as parent_type")
			->paginate(['list_rows'=>10, 'query'=>request()->param()])->each(function($g) {
				if ($g['miniprogram_id']>0) {
					$miniprogram = Db::name('miniprogram')->where(['id'=>$g['miniprogram_id'], 'status'=>1])->field('name, pic, type')->find();
					$g['miniprogram_type'] = $miniprogram['type'];
					$g['pic'] = $miniprogram['pic'];
					$g['miniprogram_name'] = $miniprogram['name'];
					switch ($g['miniprogram_type']) {
						case 1:
							$g['parent_type'] = 'video';
							break;
						case 2:
							$g['parent_type'] = 'blessing';
							break;
						case 3:
							$g['parent_type'] = 'buddha';
							break;
						default:
							$g['parent_type'] = 'article';
							break;
					}
					$g['parent_name'] = Db::name($g['parent_type'])->where('id', $g['parent_id'])->value('title');
				} else {
					$g['parent_name'] = $g['name'];
				}
				return $g;
			});
		$rs = add_domain_deep($rs, ['pic']);
		View::assign('rs', $rs);
		View::assign('is_feedback_delete', core::check_permission('feedback', 'delete'));
		setViewAssign(compact('member_id', 'keyword'));
		setViewPage($rs);
		return success();
	}
	
	//反馈删除
	public function feedback_delete() {
		$id = $this->request->get('id', 0);
		Db::name('feedback')->delete($id);
		location('/setting/feedback');
	}
	
	//操作日志
	public function log() {
		if ($this->admin_super==0) {
			$ids = $this->_subManager($this->admin_id);
			$ids = implode(',', $ids);
			$where = [
				['user_id', 'in', $ids]
			];
		} else {
			$where = [];
		}
		$type = $this->request->get('type');
		$content = $this->request->get('content');
		$ip = $this->request->get('ip');
		if (strlen($type)) {
			$where[] = ['type', '=', $type];
		}
		if (strlen($content)) {
			$where[] = ['content', 'like', "%{$content}%"];
		}
		if (strlen($ip)) {
			$where[] = ['ip', '=', $ip];
		}
		$rs = Db::name('access_log')->where($where)->order('id', 'DESC')->paginate(['list_rows'=>20, 'query'=>request()->param()]);
		View::assign('rs', $rs);
		setViewAssign(compact('type', 'content', 'ip'));
		setViewPage($rs);
		return success();
	}
	
	//访问路由
	public function router() {
		$where = [];
		$type = $this->request->get('type');
		$member_name = $this->request->get('member_name');
		$router_app = $this->request->get('router_app');
		$router_act = $this->request->get('router_act');
		$ip = $this->request->get('ip');
		if (strlen($type)) {
			$where[] = ['type', '=', $type];
		}
		if (strlen($member_name)) {
			$where[] = ['member_name', '=', $member_name];
		}
		if (strlen($router_app)) {
			$where[] = ['app', '=', $router_app];
		}
		if (strlen($router_act)) {
			$where[] = ['act', '=', $router_act];
		}
		if (strlen($ip)) {
			$where[] = ['ip', '=', $ip];
		}
		$rs = Db::name('router_log')->where($where)->order('id', 'DESC')->paginate(['list_rows'=>20, 'query'=>request()->param()])->each(function($row) {
			/*
			$app_name = $log_mod->convert_app_name($row['app']);
			$act_name = $log_mod->convert_act_name($row['act']);
			if (!$app_name) $app_name = $row['app'];
			if (!$act_name) $act_name = $row['act'];
			$row['app'] = "{$app_name}";
			$row['act'] = "{$act_name}";
			*/
			return $row;
		});
		View::assign('rs', $rs);
		setViewAssign(compact('type', 'member_name', 'router_app', 'router_act', 'ip'));
		return success();
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
				$row = Db::name('miniprogram')->where("appid='{$entry2}'")->row();
				if (!$row) delete_folder($path2.'/'.$entry2);
			}
		}
		echo 'OK';
		exit;
		//*/
		deletedir('/runtime/cache', false);
		$tables = ['article', 'blessing', 'buddha', 'video'];
		foreach ($tables as $t) {
			Db::query("DELETE FROM sc_{$t}_attr_id WHERE id NOT IN (SELECT id FROM sc_{$t}_attr_0)");
		}
		return success('ok');
	}
}
