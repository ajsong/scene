<?php
class wechat extends core {
	private $image_mod;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function index() {
		if (!isset($_GET['act']) && $this->permission('', 'miniprogram')) {
			location('?app=wechat&act=miniprogram');
		}
		//if (SQL::share()->tableExist('wechat')) {
			$where = '';
			$sort = 'w.id DESC';
			$id = $this->request->get('id');
			$keyword = $this->request->get('keyword');
			$component_id = $this->request->get('component_id', 0);
			$sortby = $this->request->get('sortby');
			if (strlen($id)) {
				$where .= " AND w.id='{$id}'";
			}
			if (strlen($keyword)) {
				$where .= " AND (w.name LIKE '%{$keyword}%' OR w.appid LIKE '%{$keyword}%' OR w.username LIKE '%{$keyword}%' OR w.alias LIKE '%{$keyword}%')";
			}
			if ($component_id>0) {
				$where .= " AND w.component_id='{$component_id}'";
			}
			if ($sortby) {
				$sort = 'w.'.str_replace(',', ' ', $sortby).', '.$sort;
			}
			$rs = SQL::share('wechat w')
				->left('component c', 'w.component_id=c.id')
				->where($where)->sort($sort)->isezr()->setpages(compact('id', 'keyword', 'component_id', 'sortby'))
				->find("w.*, 0 as alive_fans, c.name as component_name");
			$sharepage = SQL::share()->page;
			if ($rs) {
				$wxapi = new wechatCallbackAPI();
				foreach ($rs as $g) {
					if (!strlen($g->pic)) {
						$json = $wxapi->authorizer_userinfo($g->appid);
						if ($json) {
							$name = $json['authorizer_info']['nick_name'];
							$username = $json['authorizer_info']['user_name'];
							$type = $json['authorizer_info']['service_type_info']['id'];
							$alias = $json['authorizer_info']['alias'];
							$pic = $json['authorizer_info']['head_img'];
							$qrcode = $json['authorizer_info']['qrcode_url'];
							$qrcode = download_file('wxqrcode', $qrcode, false, '.jpg');
							SQL::share('wechat')->where($g->id)->update(compact('name', 'username', 'type', 'alias', 'pic', 'qrcode'));
							$g->name = $name;
							$g->type = $type;
							$g->alias = $alias;
							$g->pic = $pic;
							$g->qrcode = $qrcode;
						}
					}
					/*
					if ($g->fans_time==0 || time()-$g->fans_time>60*60*24) {
						$fans = $g->fans;
						$json = $wxapi->authorizer_userlist($g->appid, '', true);
						if ($json && isset($json['total'])) $fans = $json['total'];
						$fans_time = time();
						$g->fans = $fans;
						SQL::share('wechat')->where($g->id)->update(compact('fans', 'fans_time'));
					}
					*/
					$g->alive_fans = SQL::share('wechat_user')->where("wechat_id='{$g->id}'")->comparetime('h', 'add_time', '<48')->count();
				}
			}
			$rs = add_domain_deep($rs, array('pic', 'qrcode'));
			$component = SQL::share('component')->sort('id ASC')->find('id, name, appid');
			$this->smarty->assign('rs', $rs);
			$this->smarty->assign('component', $component);
			$this->smarty->assign('sharepage', $sharepage);
			$this->display();
		//} else {
		//	$this->customer();
		//}
	}
	
	public function getfans() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) error('请选择公众号');
		$ids = explode(',', $ids);
		$wxapi = new wechatCallbackAPI();
		$count = [];
		foreach ($ids as $id) {
			$fans = 0;
			$row = SQL::share('wechat')->where($id)->row('id, appid, component_id');
			if ($row) {
				$component = SQL::share('component')->where($row->component_id)->row('appid, appsecret, token, aeskey');
				$wxapi->WX_THIRD = array(
					'appid'=>$component->appid,
					'secret'=>$component->appsecret,
					'token'=>$component->token,
					'aeskey'=>$component->aeskey
				);
				$json = $wxapi->authorizer_userlist($row->appid, '', true);
				if ($json && isset($json['total'])) $fans = $json['total'];
				$fans_time = time();
				SQL::share('wechat')->where($row->id)->update(compact('fans', 'fans_time'));
			}
			$count[] = $fans;
		}
		success($count);
	}
	
	public function wechat_update() {
		$id = $this->request->get('id', 0);
		if ($id<=0) error('缺少参数');
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$row = SQL::share('wechat')->where($id)->row('appid, component_id');
		if (!$row) error('该公众号不存在');
		$component = SQL::share('component')->where($row->component_id)->row('appid, appsecret, token, aeskey');
		if (!$component) return;
		$wxapi = new wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$json = $wxapi->miniprogramBasicinfo($row->appid);
		if ($json) {
			$name = $json['authorizer_info']['nick_name'];
			$pic = $json['authorizer_info']['head_img'];
			$alias = $json['authorizer_info']['alias'];
			$qrcode = $json['authorizer_info']['qrcode_url'];
			$qrcode = download_file('wxqrcode', $qrcode, false, '.jpg');
			$file = PUBLIC_PATH.$qrcode;
			$fp = @fopen($file, 'r');
			$qrcode = @fread($fp, @filesize($file));
			@fclose($fp);
			$qrcode = upload_obj_file($qrcode, 'wxqrcode');
			$qrcode = add_domain($qrcode);
			@unlink($file);
			SQL::share('wechat')->where($id)->update(compact('name', 'alias', 'pic', 'qrcode'));
		}
		historyBack();
	}
	
	public function miniprogram() {
		/*
		$rs = SQL::share('config')->where("name LIKE 'G_BUDDHA%' AND status=1")->sort('id ASC')->find();
		$data = [];
		foreach ($rs as $g) {
			unset($g->id);
			$g->name = str_replace('G_BUDDHA_', 'G_BUDDHAAUDIO_', $g->name);
			$g->memo = str_replace('#9370db佛学#', '#d6487e佛音#', $g->memo);
			$data[] = json_decode(json_encode($g), true);
		}
		SQL::share('config')->insert($data);
		exit('OK');
		*/
		$where = "m.gstatus=1 AND m.status=1";
		$sort = 'm.id DESC';
		$id = $this->request->get('id');
		$keyword = $this->request->get('keyword');
		$admin_id = $this->request->get('admin_id');
		$fast = $this->request->get('fast');
		$source = $this->request->get('source');
		$type = $this->request->get('type');
		$component_id = $this->request->get('component_id', 0);
		$audit_status = $this->request->get('audit_status');
		$sortby = $this->request->get('sortby');
		$pagesize = 10;
		if ($this->admin_id>2) {
			$ids = SQL::share('admin_miniprogram')->where("admin_id='{$this->admin_id}'")->returnArray()->find('miniprogram_id');
			$where .= count($ids) ? " AND m.id IN (".implode(',', $ids).")" : ' AND 1=0';
		}
		if (strlen($id)) {
			$where .= " AND m.id='{$id}'";
		}
		if (strlen($keyword)) {
			$where .= " AND (m.name LIKE '%{$keyword}%' OR m.appid LIKE '%{$keyword}%' OR m.username LIKE '%{$keyword}%' OR m.alias LIKE '%{$keyword}%')";
		}
		if (strlen($admin_id)) {
			$where .= " AND am.admin_id='{$admin_id}'";
		}
		if (strlen($fast)) {
			$where .= " AND m.fast='{$fast}'";
		}
		if (strlen($source)) {
			$pagesize = 1000;
			$where .= " AND m.source='{$source}'";
		}
		if (strlen($type)) {
			if (strpos($type, ',')===false) {
				$where .= " AND m.type='{$type}'";
			} else {
				$where .= " AND m.id IN ({$type})";
			}
		}
		if ($component_id>0) {
			$where .= " AND m.component_id='{$component_id}'";
		}
		if (strlen($audit_status)) {
			$where .= (intval($audit_status)==0 || intval($audit_status)==2) ? " AND m.audit_status='{$audit_status}'" : " AND m.audit_status!='0' AND m.audit_status!='2'";
		}
		if ($sortby) {
			$sort = 'm.'.str_replace(',', ' ', $sortby).', '.$sort;
		}
		$rs = SQL::share('miniprogram m')
			->left('component c', 'm.component_id=c.id')
			->left('admin_miniprogram am', 'm.id=am.miniprogram_id')
			->where($where)->sort($sort)->isezr()->pagesize($pagesize)->setpages(compact('id', 'keyword', 'admin_id', 'fast', 'source', 'type', 'component_id', 'audit_status', 'sortby'))
			->find("m.*, c.name as component_name, '' as admin_name, 0 as clicks, 0 as yesterday_clicks, 0 as today_clicks");
		$sharepage = SQL::share()->page;
		if ($rs) {
			foreach ($rs as $g) {
				$g->admin_name = SQL::share('admin_miniprogram am')->left('admin a', 'a.id=am.admin_id')->where("am.miniprogram_id='{$g->id}'")->value('a.name');
				$g->clicks = SQL::share('admin_miniprogram_article')->where("miniprogram_id='{$g->id}'")->sum('clicks');
				$g->yesterday_clicks = SQL::share('admin_miniprogram_article')->where("miniprogram_id='{$g->id}'")->sum('yesterday_clicks');
				$g->today_clicks = SQL::share('admin_miniprogram_article')->where("miniprogram_id='{$g->id}'")->sum('today_clicks');
				if ($this->admin_id==2) {
					$g->clicks = SQL::share('article_attr')->where("miniprogram_id='{$g->id}'")->sum('clicks');
				}
			}
		}
		$rs = add_domain_deep($rs, array('pic', 'qrcode'));
		$where = "status=1";
		$component = SQL::share('component')->where($where)->sort('id ASC')
			->find('id, name, appid, 0 as miniprogram_count, 0 as article_count, 0 as video_count, 0 as blessing_count, 0 as buddha_count, 0 as buddhaaudio_count, 0 as fast_count');
		if ($component) {
			foreach ($component as $g) {
				$g->miniprogram_count = SQL::share('miniprogram')->where("component_id='{$g->id}'")->count();
				$g->article_count = SQL::share('miniprogram')->where("component_id='{$g->id}' AND type=0 AND source=1")->count();
				$g->video_count = SQL::share('miniprogram')->where("component_id='{$g->id}' AND type=1 AND source=1")->count();
				$g->blessing_count = SQL::share('miniprogram')->where("component_id='{$g->id}' AND type=2 AND source=1")->count();
				$g->buddha_count = SQL::share('miniprogram')->where("component_id='{$g->id}' AND type=3 AND source=1")->count();
				$g->buddhaaudio_count = SQL::share('miniprogram')->where("component_id='{$g->id}' AND type=4 AND source=1")->count();
				$g->fast_count = SQL::share('miniprogram')->where("component_id='{$g->id}' AND fast=1")->count();
			}
		}
		$admins = SQL::share('admin')->where("id>2 AND status=1")->find('id, name');
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('component', $component);
		$this->smarty->assign('admins', $admins);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display('wechat.miniprogram.html');
	}
	
	public function miniprogram_update() {
		$id = $this->request->get('id', 0);
		if ($id<=0) error('缺少参数');
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$row = SQL::share('miniprogram')->where($id)->row('appid, component_id');
		if (!$row) error('该小程序不存在');
		$component = SQL::share('component')->where($row->component_id)->row('appid, appsecret, token, aeskey');
		if (!$component) return;
		$wxapi = new wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$json = $wxapi->miniprogramBasicinfo($row->appid);
		if ($json) {
			$name = $json['authorizer_info']['nick_name'];
			$pic = $json['authorizer_info']['head_img'];
			$qrcode = $json['authorizer_info']['qrcode_url'];
			$qrcode = download_file('wxqrcode', $qrcode, false, '.jpg');
			$file = PUBLIC_PATH.$qrcode;
			$fp = @fopen($file, 'r');
			$qrcode = @fread($fp, @filesize($file));
			@fclose($fp);
			$qrcode = upload_obj_file($qrcode, 'wxqrcode');
			$qrcode = add_domain($qrcode);
			@unlink($file);
			SQL::share('miniprogram')->where($id)->update(compact('name', 'pic', 'qrcode'));
		}
		historyBack();
	}
	
	public function miniprogram_config() {
		//UPDATE sc_miniprogram_config SET content=1 WHERE config_id IN (15,30,59,73,87,101,130,50,121,55,126,186,187,188);
		//UPDATE sc_miniprogram_config SET content='' WHERE config_id IN (69,140);
		//UPDATE sc_miniprogram_config SET content=2 WHERE config_id IN (56,127);
		$this->permission('wechat', 'setting', 0, false);
		$wxapi = new wechatCallbackAPI();
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$type = $this->request->post('type', 0);
			$ad_fixed = $this->request->post('ad_fixed', 0);
			$ad_fixed_percent = $this->request->post('ad_fixed_percent', 0);
			$recommend_hidden = $this->request->post('recommend_hidden', 0);
			$navbar_textcolor = $this->request->post('navbar_textcolor');
			$navbar_bgcolor = $this->request->post('navbar_bgcolor');
			$mp_title = $this->request->post('mp_title');
			$mp_url = $this->request->post('mp_url');
			$mp_pic = $this->request->file('wechat', 'mp_pic', UPLOAD_LOCAL);
			$subscribe_id = $this->request->post('subscribe_id');
			$subscribe_img = $this->request->file('wechat', 'subscribe_img', UPLOAD_LOCAL);
			$appsecret = $this->request->post('appsecret');
			$trans_title = $this->request->post('trans_title');
			$trans_url = $this->request->post('trans_url');
			$trans_pic = $this->request->file('wechat', 'trans_pic', UPLOAD_LOCAL);
			$comment_hidden = $this->request->post('comment_hidden', 0);
			$component_id = $this->request->post('component_id', 0);
			$admin_id = $this->request->post('admin_id', 0);
			$miniprogram_list = $this->request->post('miniprogram_list', '', '?');
			if ($component_id<=0) error('请选择要绑定的第三方平台');
			$miniprogram = SQL::share('miniprogram')->where($id)->row('appid, type, component_id');
			if (!$miniprogram) error('该小程序不存在');
			if (strlen($subscribe_id) && !strlen($appsecret)) error('使用订阅消息需填写AppSecret');
			if ($miniprogram->type != $type) {
				SQL::share('article_attr')->delete("miniprogram_id='{$id}'");
				SQL::share('video_attr')->delete("miniprogram_id='{$id}'");
				SQL::share('blessing_attr')->delete("miniprogram_id='{$id}'");
				SQL::share('buddha_attr')->delete("miniprogram_id='{$id}'");
				SQL::share('buddhaaudio_attr')->delete("miniprogram_id='{$id}'");
				$table = 'article';
				if ($type==1) $table = 'video';
				else if ($type==2) $table = 'blessing';
				else if ($type==3) $table = 'buddha';
				else if ($type==4) $table = 'buddhaaudio';
				$rs = SQL::share($table)->sort('id ASC')->find('id');
				foreach ($rs as $g) {
					SQL::share("{$table}_attr")->insert(array('miniprogram_id'=>$id, "{$table}_id"=>$g->id));
				}
			}
			if (strlen($miniprogram_list)) {
				$k = 0;
				$list = array();
				$miniprogramList = preg_split("/[\r\n]+/", $miniprogram_list);
				for ($i=0; $i<count($miniprogramList); $i++) {
					if (strlen($miniprogramList[$i])) {
						$list[] = $miniprogramList[$i];
						$k++;
					}
					if ($k==9) break;
				}
				$miniprogram_list = implode("\r\n", $list);
			}
			
			$component = SQL::share('component')->where($miniprogram->component_id)->row();
			$wxapi->WX_THIRD = array(
				'appid'=>$component->appid,
				'secret'=>$component->appsecret,
				'token'=>$component->token,
				'aeskey'=>$component->aeskey
			);
			
			SQL::share('miniprogram')->where($id)->update(compact('type', 'ad_fixed', 'ad_fixed_percent', 'recommend_hidden', 'navbar_textcolor', 'navbar_bgcolor', 'mp_title', 'mp_url', 'mp_pic', 'subscribe_id', 'subscribe_img', 'appsecret', 'trans_title', 'trans_url', 'trans_pic', 'comment_hidden', 'miniprogram_list', 'component_id'));
			if ($miniprogram->type==1) $s = 'VIDEO';
			else if ($miniprogram->type==2) $s = 'BLESSING';
			else if ($miniprogram->type==3) $s = 'BUDDHA';
			else if ($miniprogram->type==4) $s = 'BUDDHAAUDIO';
			else $s = 'ARTICLE';
			$rs = SQL::share('config')->where("name LIKE 'G_{$s}/_%' ESCAPE '/' AND status=1 AND parent_id=0")->find('id, name, type');
			if ($rs) {
				$data = array();
				foreach ($rs as $row) {
					if ($row->type=='file') {
						$content = (isset($_FILES[$row->name]) && strlen($_FILES[$row->name]['name'])) ? $_FILES[$row->name] : '';
						if (!$content) {
							$content = $this->request->post("origin_{$row->name}");
						} else {
							if (stripos($row->name, 'MEDIAID')!==false) {
								$content = $this->request->file('wechat', $row->name, false);
								if (strlen($content)) {
									$_content = $wxapi->setMedia(PUBLIC_PATH.$content, $miniprogram->appid);
									$content = $_content . '|' . $content . '|' . time();
								}
							} else {
								$content = $this->request->file('wechat', $row->name, UPLOAD_LOCAL);
							}
						}
					} else if (stripos($row->type, 'checkbox')!==false) {
						$content = $this->request->post($row->name, 0);
					} else if (stripos($row->type, 'select')!==false || stripos($row->type, 'switch')!==false) {
						$content = $this->request->post($row->name);
						$subconfig = SQL::share('config')->where("parent_id='{$row->id}'")->find('id, name, type');
						if ($subconfig) {
							foreach ($subconfig as $g) {
								$_origin_content = $this->request->post("origin_{$g->name}");
								$_content = $this->request->post($g->name);
								if ($g->type=='file') {
									$_content = (isset($_FILES[$g->name]) && strlen($_FILES[$g->name]['name'])) ? $_FILES[$g->name] : '';
									if (!$_content) {
										$_content = $_origin_content;
									} else {
										if (stripos($g->name, 'MEDIAID')!==false) {
											$_content = $this->request->file('wechat', $g->name, false);
											if (strlen($_content)) {
												$__content = $wxapi->setMedia(PUBLIC_PATH.$_content, $miniprogram->appid);
												$_content = $__content . '|' . $_content . '|' . time();
											}
										} else {
											$_content = $this->request->file('wechat', $g->name, UPLOAD_LOCAL);
										}
									}
								} else if (stripos($g->type, 'checkbox')!==false) {
									$_content = intval($_content);
								} else {
									$_content = trim($_content);
								}
								$data[] = array('miniprogram_id'=>$id, 'config_id'=>$g->id, 'content'=>$_content);
							}
						}
					} else {
						$content = $this->request->post($row->name);
					}
					$data[] = array('miniprogram_id'=>$id, 'config_id'=>$row->id, 'content'=>$content);
				}
				if (count($data)) {
					SQL::share('miniprogram_config')->delete("miniprogram_id='{$id}'");
					SQL::share('miniprogram_config')->insert($data);
				}
			}
			if ($admin_id>0) {
				if (!SQL::share('admin_miniprogram')->where("admin_id='{$admin_id}' AND miniprogram_id='{$id}'")->exist()) {
					SQL::share('admin_miniprogram')->delete("miniprogram_id='{$id}'");
					SQL::share('admin_miniprogram_article')->delete("miniprogram_id='{$id}'");
					SQL::share('admin_miniprogram')->insert(['admin_id'=>$admin_id, 'miniprogram_id'=>$id]);
				}
			} else {
				SQL::share('admin_miniprogram')->delete("miniprogram_id='{$id}'");
				SQL::share('admin_miniprogram_article')->delete("miniprogram_id='{$id}'");
			}
			if ($miniprogram->component_id != $component_id) {
				$component = SQL::share('component')->where($miniprogram->component_id)->row('appid');
				delete_folder("/temp/{$component->appid}/{$miniprogram->appid}");
				$component = SQL::share('component')->where($component_id)->row('appid');
				location("/wx_interface?act=component_auth&component_appid={$component->appid}");
			}
			location("?app=wechat&act=miniprogram_config&id={$id}&msg=1");
		}
		$miniprogram = SQL::share('miniprogram')->where($id)->row();
		$miniprogram = add_domain_deep($miniprogram, ['pic']);
		$miniprogram->admin_id = SQL::share('admin_miniprogram')->where("miniprogram_id='{$miniprogram->id}'")->value('admin_id', 'intval');
		if ($miniprogram->type==1) $s = 'VIDEO';
		else if ($miniprogram->type==2) $s = 'BLESSING';
		else if ($miniprogram->type==3) $s = 'BUDDHA';
		else if ($miniprogram->type==4) $s = 'BUDDHAAUDIO';
		else $s = 'ARTICLE';
		
		if (!SQL::share('miniprogram_config')->where("miniprogram_id='{$id}'")->exist()) {
			$rs = SQL::share('config')->where("name LIKE 'G_{$s}/_%' ESCAPE '/'")->find('id, content');
			if ($rs) {
				$data = array();
				foreach ($rs as $row) {
					$data[] = array('miniprogram_id'=>$id, 'config_id'=>$row->id, 'content'=>$row->content);
				}
				SQL::share('miniprogram_config')->insert($data);
			}
		}
		
		$list = array();
		$subcontent = function($miniprogramId, $parentId=0, $where='') use (&$subcontent, &$list) {
			$rs = SQL::share('config')->where("status=1 AND parent_id='{$parentId}' {$where}")->sort('`group` ASC, sort ASC, id ASC')
				->find("id, name, memo, type, parent_id, parent_value, `group`, '' as content, '' as placeholder, 0 as is_image, '' as image, '' as file_attr, NULL as subconfig");
			if ($rs) {
				foreach ($rs as $row) {
					$row->content = SQL::share('miniprogram_config')->where("miniprogram_id='{$miniprogramId}' AND config_id='{$row->id}'")->value('content');
					$row->memo = changeColor($row->memo);
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
							if (strlen($row->content)) $row->image = "/gm/api/wechat/miniprogram_media?miniprogram_id={$miniprogramId}&config_id={$row->id}";
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
							$content = '<input value="1" name="'.$row->name.'" type="checkbox" data-type="app" data-style="margin-top:5px;" '.(intval($row->content)==1?'checked':'').' />';
						} else {
							$con = explode('#', $con[1]);
							$content = '';
							if ($type=='select') {
								$content .= '<select name="'.$row->name.'" class="some-select-'.$row->name.'">';
							} else if ($type=='switch') {
								$content .= '<span class="some-switch some-switch-'.$row->name.'">';
							}
							foreach ($con as $h) {
								$g = explode(':', $h);
								if ($type=='radio') {
									$content .= '<input value="'.$g[0].'" name="'.$row->name.'" type="radio" data-type="ace" data-text="'.$g[1].'" '.($row->content==$g[0]?'checked':'').' />';
								} else if ($type=='select') {
									$content .= '<option value="'.$g[0].'" '.($row->content==$g[0]?'selected':'').'>'.$g[1].'</option>';
								} else if ($type=='switch') {
									$content .= '<label><input type="radio" name="'.$row->name.'" value="'.$g[0].'" '.($row->content==$g[0]?'checked':'').' /><div>'.$g[1].'</div></label>';
								}
							}
							if ($type=='select') {
								$content .= '</select>';
								$subconfig = $subcontent($miniprogramId, $row->id);
								if ($subconfig) {
									$content .= '<script>
$(function(){
	$(".some-select-'.$row->name.'").on("change", function(){
		$("[data-parent'.$row->id.'-value]").css("display", "none");
		$("[data-parent'.$row->id.'-value*=\',"+$(this).selected().val()+",\']").css("display", "block");
	}).trigger("change");
});
</script>';
								}
								$row->subconfig = $subconfig;
							} else if ($type=='switch') {
								$content .= '</span>';
								$subconfig = $subcontent($miniprogramId, $row->id);
								if ($subconfig) {
									$content .= '<script>
$(function(){
	$(".some-switch-'.$row->name.' :radio").on("change", function(){
		$("[data-parent'.$row->id.'-value]").css("display", "none");
		$("[data-parent'.$row->id.'-value*=\',"+$(this).parent().parent().find(":checked").val()+",\']").css("display", "block");
	});
	$(".some-switch-'.$row->name.' :checked").trigger("change");
});
</script>';
								}
								$row->subconfig = $subconfig;
							}
						}
						$row->type = $type;
						$row->parse_content = $content;
					} else if (stripos($row->type, 'color')!==false) {
						$row->parse_content = '<input value="'.$row->content.'" name="'.$row->name.'" type="text" /><div class="some-color" style="background:'.$row->content.';"></div>';
					} else if ((stripos($row->type, 'input')!==false || stripos($row->type, 'textarea')!==false) && stripos($row->type, '|')!==false) {
						$con = explode('|', $row->type);
						$row->placeholder = $con[1];
					}
					$row->placeholder = str_replace('"', '&#34', $row->placeholder);
					if ($parentId==0) {
						if (!isset($list[$row->group])) $list[$row->group] = array();
						$list[$row->group][] = $row;
					}
				}
			}
			return $rs;
		};
		
		$subcontent($id, 0, "AND name LIKE 'G_{$s}/_%' ESCAPE '/'");
		
		$component = SQL::share('component')->find('id, name, appid');
		$admins = SQL::share('admin')->where('id>2')->find('id, name');
		
		$this->smarty->assign('miniprogram', $miniprogram);
		$this->smarty->assign('component', $component);
		$this->smarty->assign('admins', $admins);
		$this->smarty->assign('list', $list);
		$this->display();
	}
	private function _removeColor($str) {
		$str = preg_replace_callback('/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})([^#]+)#/', function($matcher){
			return $matcher[2];
		}, $str);
		$str = preg_replace_callback('/#([RGBOPY])([^#]+)#/', function($matcher){
			return $matcher[2];
		}, $str);
		return $str;
	}
	
	public function mulreview() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) error('请选择小程序');
		$ids = explode(',', $ids);
		$wxapi = new wechatCallbackAPI();
		foreach ($ids as $id) {
			$miniprogram = SQL::share('miniprogram')->where($id)->row();
			if (!$miniprogram || $miniprogram->review==1) continue;
			$component = SQL::share('component')->where($miniprogram->component_id)->row();
			$wxapi->WX_THIRD = array(
				'appid'=>$component->appid,
				'secret'=>$component->appsecret,
				'token'=>$component->token,
				'aeskey'=>$component->aeskey
			);
			$this->miniprogram_qrcode(false, $id);
			$res = $wxapi->miniprogramReview($miniprogram->appid, $miniprogram->name);
			$data = array();
			$data['audit_status'] = 1;
			$data['audit_submit_time'] = time();
			$data['auditid'] = $res['auditid'];
			$data['review'] = 1;
			SQL::share('miniprogram')->where($miniprogram->id)->update($data);
		}
		success('ok');
	}
	
	public function mulunreview() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) error('请选择小程序');
		$ids = explode(',', $ids);
		$wxapi = new wechatCallbackAPI();
		foreach ($ids as $id) {
			$miniprogram = SQL::share('miniprogram')->where($id)->row();
			if (!$miniprogram || $miniprogram->review==0) continue;
			$component = SQL::share('component')->where($miniprogram->component_id)->row();
			$wxapi->WX_THIRD = array(
				'appid'=>$component->appid,
				'secret'=>$component->appsecret,
				'token'=>$component->token,
				'aeskey'=>$component->aeskey
			);
			$wxapi->miniprogramUnReview($miniprogram->appid);
			$data = array();
			$data['auditid'] = '';
			$data['audit_status'] = -2;
			$data['review'] = 0;
			SQL::share('miniprogram')->where($miniprogram->id)->update($data);
		}
		success('ok');
	}
	
	public function miniprogram_queryquota() {
		$component_id = $this->request->get('component_id', 0);
		$component = SQL::share('component')->where($component_id)->row();
		$miniprogram = SQL::share('miniprogram')->where("component_id='{$component->id}'")->row('appid');
		if (!$miniprogram) error('没有相关的小程序');
		$wxapi = new wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$json = $wxapi->miniprogramQueryquota($miniprogram->appid);
		success($json);
	}
	
	public function miniprogram_clear_quota() {
		$component_id = $this->request->get('component_id', 0);
		$component = SQL::share('component')->where($component_id)->row();
		$miniprogram = SQL::share('miniprogram')->where("component_id='{$component->id}' AND source=1")->row('appid');
		$wxapi = new wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$wxapi->clear_quota($miniprogram->appid);
		success('ok');
	}
	
	public function miniprogram_only_pic() {
		$id = $this->request->post('id', 0);
		$row = SQL::share('miniprogram')->where($id)->row('only_pic');
		if ($row) {
			$only_pic = $row->only_pic==0 ? 1 : 0;
			SQL::share('miniprogram')->where($id)->update(compact('only_pic'));
		}
		success('ok');
	}
	
	public function miniprogram_status() {
		$id = $this->request->post('id', 0);
		$row = SQL::share('miniprogram')->where($id)->row('review');
		if ($row) {
			$review = $row->review==0 ? 1 : 0;
			SQL::share('miniprogram')->where($id)->update(compact('review'));
		}
		success('ok');
	}
	
	public function miniprogram_promote_status() {
		$id = $this->request->post('id', 0);
		$row = SQL::share('miniprogram')->where($id)->row('promote_status');
		if ($row) {
			$promote_status = $row->promote_status==0 ? 1 : 0;
			SQL::share('miniprogram')->where($id)->update(compact('promote_status'));
		}
		success('ok');
	}
	
	public function miniprogram_checkname() {
		$component_id = $this->request->get('component_id', 0);
		$app_name = $this->request->get('app_name');
		if ($component_id<=0 || !strlen($app_name)) error('缺少参数');
		$component = SQL::share('component')->where($component_id)->row();
		$wxapi = new wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$result = $wxapi->miniprogramCheckName($app_name);
		success($result);
	}
	
	public function miniprogram_create() {
		$component_id = $this->request->post('component_id', 0);
		$app_name = $this->request->post('app_name');
		$name = $this->request->post('name');
		$code = $this->request->post('code');
		$legal_persona_wechat = $this->request->post('legal_persona_wechat');
		$legal_persona_name = $this->request->post('legal_persona_name');
		$component_phone = $this->request->post('component_phone');
		if (!strlen($name) || !strlen($code) || !strlen($legal_persona_wechat) || !strlen($legal_persona_name)) error('缺少参数');
		if (SQL::share('miniprogram_box')->where("component_id='{$component_id}' AND status=0 AND name='{$name}' AND code='{$code}' AND legal_persona_wechat='{$legal_persona_wechat}' AND legal_persona_name='{$legal_persona_name}'")->exist()) error('该创建任务正在进行中');
		$component = SQL::share('component')->where($component_id)->row();
		$wxapi = new wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$wxapi->miniprogramCreate($name, $code, $legal_persona_wechat, $legal_persona_name, $component_phone, 1, 2);
		$category_first = '快递业与邮政';
		$category_second = '快递、物流';
		SQL::share('miniprogram_box')->insert(compact('component_id', 'app_name', 'name', 'code', 'legal_persona_wechat', 'legal_persona_name', 'component_phone', 'category_first', 'category_second'));
		success('ok');
	}
	
	public function miniprogram_media() {
		$miniprogram_id = $this->request->get('miniprogram_id', 0);
		$config_id = $this->request->get('config_id', 0);
		$miniprogram = SQL::share('miniprogram')->where($miniprogram_id)->row('appid, component_id');
		$content = SQL::share('miniprogram_config')->where("miniprogram_id='{$miniprogram_id}' AND config_id='{$config_id}'")->value('content');
		$content = explode('|', $content);
		$wxapi = new wechatCallbackAPI();
		$component = SQL::share('component')->where($miniprogram->component_id)->row();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$image = $wxapi->getMedia($content[0], $miniprogram->appid);
		header('Content-Type:image/png');
		echo $image;
		exit;
	}
	
	public function miniprogram_audit_status() {
		$id = $this->request->get('id', 0);
		$miniprogram = SQL::share('miniprogram')->where($id)->row();
		if ($miniprogram) {
			$wxapi = new wechatCallbackAPI();
			$component = SQL::share('component')->where($miniprogram->component_id)->row();
			$wxapi->WX_THIRD = array(
				'appid'=>$component->appid,
				'secret'=>$component->appsecret,
				'token'=>$component->token,
				'aeskey'=>$component->aeskey
			);
			$res = $wxapi->miniprogramLastStatus($miniprogram->appid);
			$res['reason'] = str_replace('<br>', '\n', $res['reason']);
			success($res);
		}
		error('该小程序不存在');
	}
	
	public function miniprogram_tester() {
		$id = $this->request->post('id', 0);
		$name = $this->request->post('name');
		if (!strlen($name)) error('缺少体验者微信号');
		$miniprogram = SQL::share('miniprogram')->where($id)->row();
		if (!$miniprogram) error('该小程序不存在');
		$wxapi = new wechatCallbackAPI();
		$component = SQL::share('component')->where($miniprogram->component_id)->row();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$wxapi->miniprogramBindTester($miniprogram->appid, $name);
		success('ok');
	}
	
	public function miniprogram_qrcode($returnImage=true, $id=0) {
		if ($id<=0) $id = $this->request->get('id', 0);
		if ($id<=0) $id = $this->request->post('id', 0);
		$miniprogram = SQL::share('miniprogram')->where($id)->row();
		if (!$miniprogram) error('该小程序不存在');
		$wxapi = new wechatCallbackAPI();
		$component = SQL::share('component')->where($miniprogram->component_id)->row();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$version = intval(str_replace('.', '', $miniprogram->version));
		$version++;
		$versionNum = $version;
		$version = strval($version);
		$version = str_split($version);
		$version = implode('.', $version);
		$template_id = -1;
		$res = $wxapi->miniprogramTemplateList();
		if (!$res) error('第三方平台没有设置代码模板库');
		$wechat_appid = SQL::share('miniprogram')->where("component_id='{$miniprogram->component_id}' AND type='{$miniprogram->type}' AND source=1")->value('appid');
		foreach ($res as $g) {
			if ($g['source_miniprogram_appid']==$wechat_appid) {
				$template_id = $g['template_id'];
				break;
			}
		}
		if ($template_id == -1) error('第三方平台没有对应类型的代码模板');
		$window = array(
			'navigationBarTitleText'=>$miniprogram->name
		);
		if (strlen($miniprogram->navbar_textcolor)) $window['navigationBarTextStyle'] = $miniprogram->navbar_textcolor;
		if (strlen($miniprogram->navbar_bgcolor)) $window['navigationBarBackgroundColor'] = $miniprogram->navbar_bgcolor;
		$config = array('window'=>$window);
		if (strlen($miniprogram->miniprogram_list)) {
			$miniprogram_list = array();
			$miniprogramList = preg_split("/[\r\n]+/", $miniprogram->miniprogram_list);
			for ($i=0; $i<count($miniprogramList); $i++) {
				$miniprogram_list[] = $miniprogramList[$i];
				if ($i==9) break;
			}
			if (count($miniprogram_list)) $config['navigateToMiniProgramAppIdList'] = $miniprogram_list;
		}
		$wxapi->miniprogramUploadCode($miniprogram->appid, $template_id, $version, $versionNum>100?'修复bug':'正式版', array(
			'version'=>$version,
			'shareTitle'=>$miniprogram->name,
			'shareImageUrl'=>''
		), NULL, $config);
		if ($returnImage) {
			$res = $wxapi->miniprogramTestQrcode($miniprogram->appid);
			header('Content-type: image/jpg');
			echo $res;
			exit;
		}
	}
	
	public function miniprogram_review() {
		$id = $this->request->post('id', 0);
		$miniprogram = SQL::share('miniprogram')->where($id)->row();
		if (!$miniprogram) error('该小程序不存在');
		$wxapi = new wechatCallbackAPI();
		$component = SQL::share('component')->where($miniprogram->component_id)->row();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$this->miniprogram_qrcode(false);
		$res = $wxapi->miniprogramReview($miniprogram->appid, $miniprogram->name);
		$data = array();
		$data['audit_status'] = 1;
		$data['audit_submit_time'] = time();
		$data['auditid'] = $res['auditid'];
		$data['review'] = 1;
		SQL::share('miniprogram')->where($miniprogram->id)->update($data);
		success($res);
	}
	
	public function miniprogram_unreview() {
		$id = $this->request->post('id', 0);
		$miniprogram = SQL::share('miniprogram')->where($id)->row();
		if (!$miniprogram) error('该小程序不存在');
		$wxapi = new wechatCallbackAPI();
		$component = SQL::share('component')->where($miniprogram->component_id)->row();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$wxapi->miniprogramUnReview($miniprogram->appid);
		$data = array();
		$data['auditid'] = '';
		$data['audit_status'] = -2;
		$data['review'] = 0;
		SQL::share('miniprogram')->where($miniprogram->id)->update($data);
		success('ok');
	}
	
	public function miniprogram_template() {
		$type = $this->request->post('type', 0);
		$component_id = $this->request->post('component_id', 0);
		$component = SQL::share('component')->where($component_id)->row();
		$miniprogram = SQL::share('miniprogram')->where("component_id='{$component->id}' AND type='{$type}' AND source=1")->row();
		if (!$miniprogram) error('缺失指定第三方平台、类型的源模板');
		$wechat_appid = $miniprogram->appid;
		$wxapi = new wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$draft_id = -1;
		$res = $wxapi->miniprogramDraftList();
		foreach ($res as $g) {
			if ($g['source_miniprogram_appid']==$wechat_appid) {
				$draft_id = $g['draft_id'];
				break;
			}
		}
		if ($draft_id == -1) error('第三方平台没有对应类型的草稿模板');
		$template_id = -1;
		$res = $wxapi->miniprogramTemplateList();
		foreach ($res as $g) {
			if ($g['source_miniprogram_appid']==$wechat_appid) {
				$template_id = $g['template_id'];
				break;
			}
		}
		if ($template_id != -1) $wxapi->miniprogramTemplateDelete($template_id);
		$wxapi->miniprogramDraftToTemplate($draft_id);
		success('ok');
	}
	
	public function miniprogram_serverdomain() {
		$miniprogram_id = $this->request->post('miniprogram_id', 0);
		$domain = $this->request->post('domain', '', '?');
		$miniprogram = SQL::share('miniprogram')->where($miniprogram_id)->row();
		if (!$miniprogram) error('该小程序不存在');
		if (!strlen($domain)) error('请填写服务器域名');
		$domain = preg_split("/[\r\n]+/", $domain);
		$domains = [];
		for ($i=0; $i<count($domain); $i++) {
			$domains[] = $domain[$i];
			//if ($i==1) break;
		}
		$wxapi = new wechatCallbackAPI();
		$component = SQL::share('component')->where($miniprogram->component_id)->row();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$wxapi->miniprogramServerDomain($miniprogram->appid, $domains, 'set');
		SQL::share('miniprogram')->where($miniprogram_id)->update(array('serverdomain'=>implode(PHP_EOL, $domains)));
		success('ok');
	}
	
	public function miniprogram_businessdomain() {
		$miniprogram_id = $this->request->post('miniprogram_id', 0);
		$domain = $this->request->post('domain', '', '?');
		$miniprogram = SQL::share('miniprogram')->where($miniprogram_id)->row();
		if (!$miniprogram) error('该小程序不存在');
		if (!strlen($domain)) error('请填写业务域名');
		$domain = preg_split("/[\r\n]+/", $domain);
		$domains = [];
		for ($i=0; $i<count($domain); $i++) {
			$domains[] = $domain[$i];
			//if ($i==1) break;
		}
		$wxapi = new wechatCallbackAPI();
		$component = SQL::share('component')->where($miniprogram->component_id)->row();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		$wxapi->miniprogramBusinessDomain($miniprogram->appid, $domains, 'set');
		SQL::share('miniprogram')->where($miniprogram_id)->update(array('businessdomain'=>implode(PHP_EOL, $domains)));
		success('ok');
	}
	
	public function miniprogram_delete() {
		$id = $this->request->get('id', 0);
		$miniprogram = SQL::share('miniprogram')->where($id)->row();
		$type = $miniprogram->type;
		$component = SQL::share('component')->where($miniprogram->component_id)->row();
		if ($component) delete_folder(ROOT_PATH."/temp/{$component->appid}/{$miniprogram->appid}");
		SQL::share('miniprogram')->delete($id);
		SQL::share('miniprogram_article_hidden')->delete("miniprogram_id='{$id}'");
		SQL::share('miniprogram_config')->delete("miniprogram_id='{$id}'");
		SQL::share('wechat_template')->delete("parent_type=1 AND parent_id='{$id}'");
		SQL::share('wechat_template_subscribe')->delete("parent_type=1 AND parent_id='{$id}'");
		SQL::share('admin_miniprogram')->delete("miniprogram_id='{$id}'");
		SQL::share('admin_miniprogram_article')->delete("miniprogram_id='{$id}'");
		switch ($type) {
			case 0:
				SQL::share('article_attr')->delete("miniprogram_id='{$id}'");
				if ($miniprogram->source==1) delete_folder(ROOT_PATH."/miniprogram/article/{$miniprogram->name}");
				break;
			case 1:
				//SQL::share('video_attr')->delete("miniprogram_id='{$id}'");
				if ($miniprogram->source==1) delete_folder(ROOT_PATH."/miniprogram/video/{$miniprogram->name}");
				break;
			case 2:
				SQL::share('blessing_attr')->delete("miniprogram_id='{$id}'");
				if ($miniprogram->source==1) delete_folder(ROOT_PATH."/miniprogram/blessing/{$miniprogram->name}");
				break;
			case 3:
				SQL::share('buddha_attr')->delete("miniprogram_id='{$id}'");
				if ($miniprogram->source==1) delete_folder(ROOT_PATH."/miniprogram/buddha/{$miniprogram->name}");
				break;
		}
		location("?app=wechat&act=miniprogram");
	}
	
	//列表
	public function wxmenu() {
		$appid = '';
		$secret = '';
		$access_token = '';
		$wechat = NULL;
		$list = array();
		if (SQL::share()->tableExist('wechat')) {
			$list = SQL::share('wechat')->where("status=1")->find('appid, name, alias, pic');
			$list = add_domain_deep($list, ['pic']);
			$appid = $this->request->get('appid');
			if (!strlen($appid) && $list) $appid = $list[0]->appid;
			if (strlen($appid)) {
				$wechat = SQL::share('wechat')->where("appid='{$appid}' AND status=1")->row();
				if ($wechat) {
					if ($wechat->appsecret && strlen($wechat->appsecret)) {
						$wxapi = new wechatCallbackAPI($appid, $wechat->appsecret);
						$access_token = $wxapi->getAccessToken("manual/access_token.{$appid}.json");
						if (!strlen($access_token)) error('获取 access_token 失败');
					}
				} else {
					$appid = '';
				}
			}
		}
		$wxapi = new wechatCallbackAPI();
		if ($wechat) {
			$component = SQL::share('component')->where($wechat->component_id)->row();
			$wxapi->WX_THIRD = array(
				'appid'=>$component->appid,
				'secret'=>$component->appsecret,
				'token'=>$component->token,
				'aeskey'=>$component->aeskey
			);
		}
		if (IS_POST) {
			$menu = $this->request->post('menu');
			//$menu = '{"button":[{"type":"view","name":"上头条","url":"https://m.joyicloud.com/","sub_button":[]},{"type":"view","name":"达人榜","url":"https://m.joyicloud.com/wap/?app=talent","sub_button":[]},{"name":"我的","sub_button":[{"type":"view","name":"个人中心","url":"https://m.joyicloud.com/wap/?app=member","sub_button":[]},{"type":"view","name":"我的团队","url":"https://m.joyicloud.com/wap/?app=member&act=team","sub_button":[]},{"type":"view","name":"推广赚钱","url":"https://m.joyicloud.com/wap/?app=member&act=poster","sub_button":[]},{"type":"view","name":"我的钱包","url":"https://m.joyicloud.com/wap/?app=member&act=commission","sub_button":[]},{"type":"view","name":"我发布的任务","url":"https://m.joyicloud.com/wap/?app=member&act=task","sub_button":[]}]}]}';
			$wxapi->setMenu($menu, $appid, $secret, $access_token);
			location("?app=wechat&act=wxmenu");
		}
		$menu = $wxapi->getMenu($appid, $secret, $access_token);
		if (is_array($menu)) $menu = json_encode($menu['menu']);
		$this->smarty->assign('menu', $menu);
		$this->smarty->assign('list', $list);
		$this->smarty->assign('appid', $appid);
		$this->display();
	}
	
	//模板消息
	/*
CREATE TABLE `sc_wechat_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_type` int(11) DEFAULT '0' COMMENT '所属类型，0公众号，1小程序',
  `parent_id` int(11) DEFAULT '0' COMMENT '所属id',
  `template_id` varchar(64) DEFAULT NULL COMMENT '模板id',
  `name` varchar(64) DEFAULT NULL COMMENT '模板名称',
  `type` int(11) DEFAULT '0' COMMENT '跳转类型，0链接，1小程序',
  `url` varchar(1000) DEFAULT NULL COMMENT '链接',
  `appid` varchar(64) DEFAULT NULL COMMENT '小程序appId',
  `pagepath` varchar(1000) DEFAULT NULL COMMENT '小程序pagepath',
  `content` text COMMENT '模板内容',
  `content_data` text COMMENT '模板内容数据',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信模板消息';
	*/
	public function template() {
		$parent_id = $this->request->get('parent_id', 0);
		$parent_type = $this->request->get('parent_type', 0);
		if ($parent_id<=0) error('数据错误');
		$rs = SQL::share('wechat_template')->where("parent_type='{$parent_type}' AND parent_id='{$parent_id}'")->sort('id DESC')->isezr()->setpages(compact('parent_id', 'parent_type'))->find("*, '' as title, 0 as count");
		$sharepage = SQL::share()->page;
		if ($rs) {
			foreach ($rs as $g) {
				$title = json_decode($g->content_data, true);
				foreach ($title as $t) {
					$title = $t['value'];
					break;
				}
				$g->title = $title;
				$g->count = SQL::share('wechat_template_subscribe')->where("template_id='{$g->template_id}' AND send_time=0")->count();
			}
		}
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display();
	}
	
	//修改模板消息
	public function template_edit() {
		$parent_id = $this->request->get('parent_id', 0);
		$parent_type = $this->request->get('parent_type', 0);
		$id = $this->request->get('id', 0);
		if (IS_POST) { //添加
			$id = $this->request->post('id', 0);
			$template_id = $this->request->post('template_id');
			$name = $this->request->post('name');
			$type = $this->request->post('type', 0);
			$url = $this->request->post('url');
			$appid = $this->request->post('appid');
			$pagepath = $this->request->post('pagepath');
			$content = $this->request->post('content', '', '\\');
			$content_data = $this->request->post('content_data', '', '\\');
			$data = compact('parent_id', 'parent_type', 'template_id', 'name', 'type', 'url', 'appid', 'pagepath', 'content', 'content_data');
			if ($id>0) {
				SQL::share('wechat_template')->where($id)->update($data);
			} else {
				$id = SQL::share('wechat_template')->insert($data);
			}
			location("?app=wechat&act=template&parent_id={$parent_id}&parent_type={$parent_type}&msg=1");
		} else if ($id>0) { //显示
			$row = SQL::share('wechat_template')->where($id)->row();
		} else {
			$row = t('wechat_template');
		}
		$wxapi = new wechatCallbackAPI();
		if ($parent_type==0) {
			$wechat = SQL::share('wechat')->where($parent_id)->row();
		} else {
			$wechat = SQL::share('miniprogram')->where($parent_id)->row();
		}
		$component = SQL::share('component')->where($wechat->component_id)->row();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		if ($parent_type==0) {
			$templates = $wxapi->getTemplateMessage($wechat->appid);
		} else {
			$templates = $wxapi->miniprogramGetTemplateMessage($wechat->appid);
		}
		$templates = json_decode(json_encode($templates));
		
		$this->smarty->assign('row', $row);
		$this->smarty->assign('wechat', $wechat);
		$this->smarty->assign('templates', $templates);
		$this->smarty->assign('parent_id', $parent_id);
		$this->smarty->assign('parent_type', $parent_type);
		$this->display('wechat.template_edit.html');
	}
	
	//获取模板消息列表
	public function get_template_list() {
		$parent_id = $this->request->get('parent_id', 0);
		$parent_type = $this->request->get('parent_type', 0);
		if ($parent_id<=0) error('缺少参数');
		$wxapi = new wechatCallbackAPI();
		if ($parent_type==0) {
			$wechat = SQL::share('wechat')->where($parent_id)->row();
		} else {
			$wechat = SQL::share('miniprogram')->where($parent_id)->row();
		}
		$component = SQL::share('component')->where($wechat->component_id)->row();
		$wxapi->WX_THIRD = array(
			'appid'=>$component->appid,
			'secret'=>$component->appsecret,
			'token'=>$component->token,
			'aeskey'=>$component->aeskey
		);
		if ($parent_type==0) {
			$templates = $wxapi->getTemplateMessage($wechat->appid);
		} else {
			$templates = $wxapi->miniprogramGetTemplateMessage($wechat->appid);
		}
		success($templates);
	}
	
	//发送模板消息
	public function template_send() {
		$id = $this->request->get('id', 0);
		if ($id<=0) error('缺少参数');
		$row = SQL::share('wechat_template')->where($id)->row();
		if (!$row) error('数据错误');
		$rs = SQL::share('wechat_template_subscribe')->where("template_id='{$row->template_id}' AND send_time=0")->find();
		if ($rs) {
			set_time_limit(0);
			ini_set('memory_limit', '10240M');
			if ($row->parent_type==0) {
				$wechat = SQL::share('wechat')->where($row->parent_id)->row();
			} else {
				$wechat = SQL::share('miniprogram')->where($row->parent_id)->row();
			}
			$wxapi = new wechatCallbackAPI();
			$component = SQL::share('component')->where($wechat->component_id)->row();
			$wxapi->WX_THIRD = array(
				'appid'=>$component->appid,
				'secret'=>$component->appsecret,
				'token'=>$component->token,
				'aeskey'=>$component->aeskey
			);
			foreach ($rs as $g) {
				if ($row->parent_type==0) {
					$miniprogram = array();
					if (strlen($row->appid)) $miniprogram = array('appid'=>$row->appid, 'pagepath'=>$row->pagepath);
					$wxapi->sendTemplateMessage($wechat->appid, $g->openid, $g->template_id, json_decode($row->content_data), $row->url, $miniprogram, '', true);
				} else {
					$wxapi->miniprogramSendTemplateMessage($wechat->appid, $g->openid, $g->template_id, json_decode($row->content_data), strlen($row->pagepath) ? $row->pagepath : $row->url, true);
				}
				SQL::share('wechat_template_subscribe')->where($g->id)->update(array('send_time'=>time()));
			}
		}
		script('发送成功', '', 'history.back()');
	}
	
	//客服消息
	public function customer() {
		$where = '';
		$keyword = $this->request->get('keyword');
		if (strlen($keyword)) {
			$where .= " AND (name LIKE '%{$keyword}%' OR alias LIKE '%{$keyword}%')";
		}
		$rs = SQL::share('wechat_customer')->where($where)->sort('id DESC')->isezr()->setpages(compact('keyword'))->find('*, 0 as mp_count');
		$sharepage = SQL::share()->page;
		if ($rs) {
			foreach ($rs as $g) {
				$g->mp_count = SQL::share('wechat_customer_mp')->where("customer_id='{$g->id}'")->count();
			}
		}
		$this->smarty->assign('rs', $rs);
		$this->smarty->assign('sharepage', $sharepage);
		$this->display();
	}
	
	public function customer_add() {
		$this->customer_edit();
	}
	public function customer_edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$preview = $this->request->post('preview', 0);
			$id = $this->request->post('id', 0);
			$name = $this->request->post('name');
			$title = $this->request->post('title');
			$memo = $this->request->post('memo');
			$pic = $this->request->file('wxcustomer', 'pic', UPLOAD_LOCAL);
			$url = $this->request->post('url');
			$predict_count = $this->request->post('predict_count', 0);
			$mp = $this->request->post('mp', '', []);
			$send_time = strtotime($this->request->post('send_time'));
			$data = compact('name', 'title', 'memo', 'pic', 'url', 'predict_count', 'send_time');
			if (is_int($mp)) $mp = [$mp];
			if (!is_array($mp) || !count($mp)) error('请选择公众号');
			
			$wechat_customer = 'wechat_customer';
			$wechat_customer_mp = 'wechat_customer_mp';
			if ($preview) {
				$wechat_customer = 'wechat_customer_preview';
				$wechat_customer_mp = 'wechat_customer_mp_preview';
				$ps = SQL::share($wechat_customer)->comparetime('h', 'add_time', '>48')->find('id');
				if ($ps) {
					foreach ($ps as $g) {
						SQL::share($wechat_customer_mp)->delete("customer_id='{$g->id}'");
						SQL::share($wechat_customer)->delete($g->id);
					}
				}
			}
			
			if ($id>0) {
				SQL::share($wechat_customer)->where($id)->update($data);
			} else {
				$data['add_time'] = time();
				$id = SQL::share($wechat_customer)->insert($data);
			}
			SQL::share($wechat_customer_mp)->delete("customer_id='{$id}'");
			if (count($mp)) {
				SQL::share($wechat_customer_mp)->insert(['customer_id'=>$id, 'wechat_id'=>$mp], 'wechat_id');
			}
			if ($preview) {
				success($id);
			} else {
				location("?app=wechat&act=customer");
			}
		} else if ($id>0) { //显示
			$row = SQL::share('wechat_customer')->where($id)->row();
			$row->mp = SQL::share('wechat_customer_mp')->where("customer_id='{$row->id}'")->returnArray(',')->find('wechat_id');
		} else {
			$wechat_id = $this->request->get('wechat_id');
			$row = t('wechat_customer');
			$row->mp = $wechat_id;
		}
		$list = SQL::share('wechat')->sort('id ASC')->find('id, appid, name, pic, alias, 0 as alive_fans');
		if ($list) {
			foreach ($list as $g) {
				$g->alive_fans = SQL::share('wechat_user')->where("wechat_id='{$g->id}'")->comparetime('h', 'add_time', '<48')->count();
			}
		}
		$this->smarty->assign('row', $row);
		$this->smarty->assign('list', $list);
		$this->display('wechat.customer_edit.html');
	}
	
	public function customer_delete() {
		$id = $this->request->get('id', 0);
		SQL::share('wechat_customer')->delete($id);
		SQL::share('wechat_customer_mp')->delete("customer_id='{$id}'");
		location("?app=wechat&act=customer");
	}
	
	public function customer_detail() {
		$id = $this->request->get('id', 0);
		$rs = SQL::share('wechat_customer_mp wcm')->left('wechat w', 'wcm.wechat_id=w.id')->where("wcm.customer_id='{$id}'")->find('w.pic, w.name, wcm.count, wcm.status');
		$rs = add_domain_deep($rs, ['pic']);
		success($rs);
	}
	
	public function miniprogram_source() {
		$id = $this->request->get('id', 0);
		if ($this->admin_id!=1 || $id<=0) error('数据错误');
		$miniprogram = SQL::share('miniprogram')->where($id)->row();
		if (!$miniprogram) error('数据错误');
		if ($miniprogram->source==0) SQL::share('miniprogram')->where("source='1' AND type='{$miniprogram->type}' AND component_id='{$miniprogram->component_id}'")->update(array('source'=>0));
		$source = $miniprogram->source==1 ? 0 : 1;
		SQL::share('miniprogram')->where($id)->update(compact('source'));
		script('', '', 'history.back()');
	}
	
	public function syncSingle() {
		$this->_syncSource(276, [244, 245, 246, 247]);
	}
	
	public function syncArticle() {
		$this->_syncSource(6);
	}
	
	public function syncVideo() {
		$this->_syncSource(7);
	}
	
	public function syncBlessing() {
		$this->_syncSource(116);
	}
	
	public function syncBuddha() {
		$this->_syncSource(278);
	}
	
	private function _syncSource($source_id, $singles=array()) {
		if (!IS_POST) error404();
		$MINIPROGRAM_PATH = ROOT_PATH.'/miniprogram';
		if (!is_dir($MINIPROGRAM_PATH)) error('源代码文件不存在');
		$source = SQL::share('miniprogram')->where($source_id)->row();
		if (!$source) error("ID为{$source_id}的小程序不存在");
		if (count($singles)) {
			$dirname = 'single';
		} else {
			$typeName = ['article', 'video', 'blessing', 'buddha'];
			$dirname = $typeName[$source->type];
		}
		$source_path = $MINIPROGRAM_PATH.'/'.$dirname.'/'.$source->name;
		if (!is_dir($source_path)) error("{$source->name}的源代码文件不存在");
		if (count($singles)) {
			$rs = SQL::share('miniprogram')->where("id IN (".implode(',', $singles).")")->find('name, appid');
		} else {
			$rs = SQL::share('miniprogram')->where("id NOT IN ({$source_id}) AND source=1 AND type='{$source->type}'")->find('name, appid');
		}
		foreach ($rs as $g) {
			$path = $MINIPROGRAM_PATH.'/'.$dirname.'/'.$g->name;
			delete_folder($path);
			copy_folder($source_path, $path, false);
			file_content_replace($path.'/app.json', function($content) use($source, $g) {
				$content = str_replace('"navigationBarTitleText": "'.$source->name.'",', '"navigationBarTitleText": "'.$g->name.'",', $content);
				return $content;
			});
			file_content_replace($path.'/project.config.json', function($content) use($source, $g) {
				$content = str_replace('"appid": "'.$source->appid.'",', '"appid": "'.$g->appid.'",', $content);
				$content = str_replace('"projectname": "'.$source->name.'",', '"projectname": "'.urlencode($g->name).'",', $content);
				$content = str_replace('"projectname": "'.urlencode($source->name).'",', '"projectname": "'.urlencode($g->name).'",', $content);
				return $content;
			});
		}
		success('ok');
	}

}
