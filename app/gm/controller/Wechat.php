<?php
declare (strict_types = 1);

namespace app\gm\controller;

use think\facade\Db;
use think\facade\View;
use think\Request;

class Wechat extends Core
{
	public function index() {
		$url = trim(request()->baseUrl(), '/');
		$url = explode('/', $url);
		if (count($url)==1 && $this->permission('', 'miniprogram')) {
			location('/wechat/miniprogram');
		}
		//if (Db::name()->tableExist('wechat')) {
		$where = [];
		$sort = ['w.id'=>'DESC'];
		$id = $this->request->get('id');
		$keyword = $this->request->get('keyword');
		$component_id = $this->request->get('component_id', 0);
		$sortby = $this->request->get('sortby');
		if (strlen($id)) {
			$where[] = ['w.id', '=', $id];
		}
		if (strlen($keyword)) {
			$where[] = ['w.name|w.appid|w.username|w.alias', 'like', "%{$keyword}%"];
		}
		if ($component_id>0) {
			$where[] = ['w.component_id', '=', $component_id];
		}
		if ($sortby) {
			$e = explode(',', $sortby);
			$sort = ['w.'.$e[0] => $e[1]] + $sort;
		}
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$rs = Db::name('wechat')->alias('w')
			->leftJoin('component c', 'w.component_id=c.id')
			->where($where)->order($sort)->field("w.*, 0 as alive_fans, c.name as component_name")
			->paginate(['list_rows'=>10, 'query'=>request()->param()])->each(function($g) use ($wxapi) {
				if (!strlen($g['pic'])) {
					$json = $wxapi->authorizer_userinfo($g['appid']);
					if ($json) {
						$name = $json['authorizer_info']['nick_name'];
						$username = $json['authorizer_info']['user_name'];
						$type = $json['authorizer_info']['service_type_info']['id'];
						$alias = $json['authorizer_info']['alias'];
						$pic = $json['authorizer_info']['head_img'];
						$qrcode = $json['authorizer_info']['qrcode_url'];
						$qrcode = download_file($qrcode, 'wxqrcode', false, '.jpg');
						Db::name('wechat')->where('id', $g['id'])->update(compact('name', 'username', 'type', 'alias', 'pic', 'qrcode'));
						$g['name'] = $name;
						$g['type'] = $type;
						$g['alias'] = $alias;
						$g['pic'] = $pic;
						$g['qrcode'] = $qrcode;
					}
				}
				/*
				if ($g['fans_time']==0 || time()-$g['fans_time']>60*60*24) {
					$fans = $g['fans'];
					$json = $wxapi->authorizer_userlist($g['appid'], '', true);
					if ($json && isset($json['total'])) $fans = $json['total'];
					$fans_time = time();
					$g['fans'] = $fans;
					Db::name('wechat')->where('id', $g['id'])->update(compact('fans', 'fans_time'));
				}
				*/
				$g['alive_fans'] = Db::name('wechat_user')->where('wechat_id', $g['id'])->whereRaw(whereTime('h', 'add_time', '<48'))->count();
				return $g;
			});
		$rs = add_domain_deep($rs, array('pic', 'qrcode'));
		$component = Db::name('component')->order('id', 'ASC')->field('id, name, appid')->select();
		View::assign('component', $component);
		setViewAssign(compact('id', 'keyword', 'component_id', 'sortby'));
		return success($rs);
		//} else {
		//	$this->customer();
		//}
	}
	
	public function getfans() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) return error('请选择公众号');
		$ids = explode(',', $ids);
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$count = [];
		foreach ($ids as $id) {
			$fans = 0;
			$row = Db::name('wechat')->where('id', $id)->field('id, appid, component_id')->find();
			if ($row) {
				$component = Db::name('component')->where('id', $row['component_id'])->field('appid, appsecret, token, aeskey')->find();
				$wxapi->WX_THIRD = array(
					'appid' => $component['appid'],
					'secret' => $component['appsecret'],
					'token' => $component['token'],
					'aeskey' => $component['aeskey']
				);
				$json = $wxapi->authorizer_userlist($row['appid'], '', true);
				if ($json && isset($json['total'])) $fans = $json['total'];
				$fans_time = time();
				Db::name('wechat')->where('id', $row['id'])->update(compact('fans', 'fans_time'));
			}
			$count[] = $fans;
		}
		return success($count);
	}
	
	public function wechat_update() {
		$id = $this->request->get('id', 0);
		if ($id<=0) return error('缺少参数');
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$row = Db::name('wechat')->where('id', $id)->field('appid, component_id')->find();
		if (!$row) return error('该公众号不存在');
		$component = Db::name('component')->where('id', $row['component_id'])->field('appid, appsecret, token, aeskey')->find();
		if (!$component) return error('数据错误');
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$json = $wxapi->miniprogramBasicinfo($row['appid']);
		if ($json) {
			$name = $json['authorizer_info']['nick_name'];
			$pic = $json['authorizer_info']['head_img'];
			$alias = $json['authorizer_info']['alias'];
			$qrcode = $json['authorizer_info']['qrcode_url'];
			$qrcode = download_file($qrcode, 'wxqrcode', false, '.jpg');
			$file = PUBLIC_PATH.$qrcode;
			$fp = @fopen($file, 'r');
			$qrcode = @fread($fp, @filesize($file));
			@fclose($fp);
			$qrcode = upload_file($qrcode, 'wxqrcode', UPLOAD_THIRD);
			$qrcode = add_domain($qrcode);
			@unlink($file);
			Db::name('wechat')->where('id', $id)->update(compact('name', 'alias', 'pic', 'qrcode'));
		}
		historyBack();
	}
	
	public function miniprogram() {
		/*
		$rs = Db::name('config')->where("name LIKE 'G_BUDDHA%' AND status=1")->order('id ASC')->find();
		$data = [];
		foreach ($rs as $g) {
			unset($g->id);
			$g['name'] = str_replace('G_BUDDHA_', 'G_BUDDHAAUDIO_', $g['name']);
			$g->memo = str_replace('#9370db佛学#', '#d6487e佛音#', $g->memo);
			$data[] = json_decode(json_encode($g), true);
		}
		Db::name('config')->insert($data);
		exit('OK');
		*/
		$where = [
			['m.gstatus', '=', 1],
			['m.status', '=', 1]
		];
		$sort = ['m.id'=>'DESC'];
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
			$ids = array();
			Db::name('admin_miniprogram')->where('admin_id', $this->admin_id)->field('miniprogram_id')->select()->each(function($item) use (&$ids) {
				$ids[] = $item['miniprogram_id'];
			});
			$where[] = count($ids) ? ['m.id', 'in', $ids] : ['1', '=', 0];
		}
		if (strlen($id)) {
			$where[] = ['m.id', '=', $id];
		}
		if (strlen($keyword)) {
			$where[] = ['m.name|m.appid|m.username|m.alias', 'like', "%{$keyword}%"];
		}
		if (strlen($admin_id)) {
			$where[] = ['am.admin_id', '=', $admin_id];
		}
		if (strlen($fast)) {
			$where[] = ['m.fast', '=', $fast];
		}
		if (strlen($source)) {
			$pagesize = 1000;
			$where[] = ['m.source', '=', $source];
		}
		if (strlen($type)) {
			if (intval($type)>-1) {
				$where[] = ['m.type', '=', $type];
			} else {
				$where[] = ['m.type', 'not in', '0,1,2,3'];
			}
		}
		if ($component_id>0) {
			$where[] = ['m.component_id', '=', $component_id];
		}
		if (strlen($audit_status)) {
			$where[] = (intval($audit_status)==0 || intval($audit_status)==2) ? ['m.audit_status', '=', $audit_status] : ['m.audit_status', 'not in', '0,2'];
		}
		if ($sortby) {
			$e = explode(',', $sortby);
			$sort = ['m.'.$e[0] => $e[1]] + $sort;
		}
		$rs = Db::name('miniprogram')->alias('m')
			->leftJoin('component c', 'm.component_id=c.id')
			->leftJoin('admin_miniprogram am', 'm.id=am.miniprogram_id')
			->where($where)->order($sort)
			->field("m.*, c.name as component_name, '' as admin_name, 0 as clicks, 0 as yesterday_clicks, 0 as today_clicks")
			->paginate(['list_rows'=>$pagesize, 'query'=>request()->param()])->each(function($g) {
				$g['admin_name'] = Db::name('admin_miniprogram')->alias('am')->leftJoin('admin a', 'a.id=am.admin_id')->where('am.miniprogram_id', $g['id'])->value('a.name');
				$g['clicks'] = Db::name('admin_miniprogram_article')->where('miniprogram_id', $g['id'])->sum('clicks');
				$g['yesterday_clicks'] = Db::name('admin_miniprogram_article')->where('miniprogram_id', $g['id'])->sum('yesterday_clicks');
				$g['today_clicks'] = Db::name('admin_miniprogram_article')->where('miniprogram_id', $g['id'])->sum('today_clicks');
				if ($this->admin_id==2) {
					$g['clicks'] = Db::name('article_attr')->where('miniprogram_id', $g['id'])->sum('clicks');
				}
				return $g;
			});
		$rs = add_domain_deep($rs, array('pic', 'qrcode'));
		$component = Db::name('component')->where('status', 1)->order('id', 'ASC')
			->field('id, name, appid, 0 as miniprogram_count, 0 as article_count, 0 as video_count, 0 as blessing_count, 0 as buddha_count, 0 as buddhaaudio_count, 0 as fast_count')
			->select()->each(function($g) {
				$g['miniprogram_count'] = Db::name('miniprogram')->where('component_id', $g['id'])->count();
				$g['article_count'] = Db::name('miniprogram')->where(['component_id'=>$g['id'], 'type'=>0, 'source'=>1])->count();
				$g['video_count'] = Db::name('miniprogram')->where(['component_id'=>$g['id'], 'type'=>1, 'source'=>1])->count();
				$g['blessing_count'] = Db::name('miniprogram')->where(['component_id'=>$g['id'], 'type'=>2, 'source'=>1])->count();
				$g['buddha_count'] = Db::name('miniprogram')->where(['component_id'=>$g['id'], 'type'=>3, 'source'=>1])->count();
				$g['buddhaaudio_count'] = Db::name('miniprogram')->where(['component_id'=>$g['id'], 'type'=>4, 'source'=>1])->count();
				$g['fast_count'] = Db::name('miniprogram')->where(['component_id'=>$g['id'], 'fast'=>1])->count();
				return $g;
			});
		$admins = Db::name('admin')->where([
			['id', '>', 2],
			['status', '=', 1]
		])->field('id, name')->select();
		View::assign('component', $component);
		View::assign('admins', $admins);
		View::assign('is_wechat_setting', core::check_permission('wechat', 'setting'));
		setViewAssign(compact('id', 'keyword', 'admin_id', 'fast', 'source', 'type', 'component_id', 'audit_status', 'sortby'));
		setViewPage($rs);
		return success($rs, 'miniprogram.html');
	}
	
	public function miniprogram_update() {
		$id = $this->request->get('id', 0);
		if ($id<=0) return error('缺少参数');
		set_time_limit(0);
		ini_set('memory_limit', '10240M');
		$row = Db::name('miniprogram')->where('id', $id)->field('appid, component_id')->find();
		if (!$row) return error('该小程序不存在');
		$component = Db::name('component')->where('id', $row['component_id'])->field('appid, appsecret, token, aeskey')->find();
		if (!$component) return error('数据错误');
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$json = $wxapi->miniprogramBasicinfo($row['appid']);
		if ($json) {
			$name = $json['nickname'];
			$pic = $json['head_image_info']['head_image_url'];
			$qrcode = '';
			$buffer = $wxapi->miniprogramQrcode($row['appid']);
			if ($buffer) {
				$qrcode = upload_file($buffer, 'wxqrcode', UPLOAD_PATH);
				$qrcode = add_domain($qrcode);
			}
			Db::name('miniprogram')->where('id', $id)->update(compact('name', 'pic', 'qrcode'));
		}
		historyBack();
	}
	
	public function miniprogram_config() {
		//UPDATE sc_miniprogram_config SET content=1 WHERE config_id IN (15,30,59,73,87,101,130,50,121,55,126,186,187,188);
		//UPDATE sc_miniprogram_config SET content='' WHERE config_id IN (69,140);
		//UPDATE sc_miniprogram_config SET content=2 WHERE config_id IN (56,127);
		$this->permission('wechat', 'setting', 0, false);
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$id = $this->request->post('id', 0);
			$type = $this->request->post('type', 0);
			$ad_fixed = $this->request->post('ad_fixed', 0);
			$ad_fixed_percent = $this->request->post('ad_fixed_percent', 0);
			$recommend_hidden = $this->request->post('recommend_hidden', 0);
			$navbar_textcolor = $this->request->post('navbar_textcolor');
			$navbar_bgcolor = $this->request->post('navbar_bgcolor');
			$bgcolor = $this->request->post('bgcolor');
			$category_bgcolor = $this->request->post('category_bgcolor');
			$category_sort = $this->request->post('category_sort');
			$mp_title = $this->request->post('mp_title');
			$mp_url = $this->request->post('mp_url');
			$mp_pic = $this->request->file('mp_pic', 'wechat', UPLOAD_THIRD);
			$subscribe_id = $this->request->post('subscribe_id');
			$subscribe_img = $this->request->file('subscribe_img', 'wechat', UPLOAD_THIRD);
			$appsecret = $this->request->post('appsecret');
			$trans_title = $this->request->post('trans_title');
			$trans_url = $this->request->post('trans_url');
			$trans_pic = $this->request->file('trans_pic', 'wechat', UPLOAD_THIRD);
			$list_type = $this->request->post('list_type');
			$comment_hidden = $this->request->post('comment_hidden', 0);
			$component_id = $this->request->post('component_id', 0);
			$admin_id = $this->request->post('admin_id', 0);
			$miniprogram_list = $this->request->post('miniprogram_list', '', '?');
			if ($component_id<=0) return error('请选择要绑定的第三方平台');
			$miniprogram = Db::name('miniprogram')->where('id', $id)->field('appid, type, component_id')->find();
			if (!$miniprogram) return error('该小程序不存在');
			if (strlen($subscribe_id) && !strlen($appsecret)) return error('使用订阅消息需填写AppSecret');
			if ($miniprogram['type'] != $type) {
				Db::name('article_attr')->where('miniprogram_id', $id)->delete();
				Db::name('video_attr')->where('miniprogram_id', $id)->delete();
				Db::name('blessing_attr')->where('miniprogram_id', $id)->delete();
				Db::name('buddha_attr')->where('miniprogram_id', $id)->delete();
				Db::name('buddhaaudio_attr')->where('miniprogram_id', $id)->delete();
				$table = 'article';
				if ($type==1) $table = 'video';
				else if ($type==2) $table = 'blessing';
				else if ($type==3) $table = 'buddha';
				else if ($type==4) $table = 'buddhaaudio';
				$rs = Db::name($table)->order('id', 'ASC')->field('id')->select();
				foreach ($rs as $g) {
					Db::name("{$table}_attr")->insert(array('miniprogram_id'=>$id, "{$table}_id"=>$g['id']));
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
			
			$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
			$wxapi->WX_THIRD = array(
				'appid' => $component['appid'],
				'secret' => $component['appsecret'],
				'token' => $component['token'],
				'aeskey' => $component['aeskey']
			);
			
			Db::name('miniprogram')->where('id', $id)->update(compact('type', 'ad_fixed', 'ad_fixed_percent', 'recommend_hidden', 'navbar_textcolor', 'navbar_bgcolor', 'category_bgcolor', 'bgcolor', 'mp_title', 'mp_url', 'mp_pic', 'subscribe_id', 'subscribe_img', 'appsecret', 'trans_title', 'trans_url', 'trans_pic', 'list_type', 'comment_hidden', 'miniprogram_list', 'component_id'));
			
			if (strlen($category_sort)) {
				if (Db::name('miniprogram_category_sort')->where('miniprogram_id', $id)->count() > 0) {
					Db::name('miniprogram_category_sort')->where('miniprogram_id', $id)->update(compact('category_sort'));
				} else {
					Db::name('miniprogram_category_sort')->insert(array('miniprogram_id'=>$id, 'category_sort'=>$category_sort));
				}
			} else {
				Db::name('miniprogram_category_sort')->where('miniprogram_id', $id)->delete();
			}
			
			if ($miniprogram['type']==1) $s = 'VIDEO';
			else if ($miniprogram['type']==2) $s = 'BLESSING';
			else if ($miniprogram['type']==3) $s = 'BUDDHA';
			else if ($miniprogram['type']==4) $s = 'BUDDHAAUDIO';
			else $s = 'ARTICLE';
			$rs = Db::name('config')->where([
				['name', 'like', Db::raw("'G_article/_%' ESCAPE '/'")],
				['status', '=', 1],
				['parent_id', '=', 0],
			])->field('id, name, type')->select();
			if ($rs) {
				$data = array();
				foreach ($rs as $row) {
					if ($row['type']=='file') {
						$content = (isset($_FILES[$row['name']]) && strlen($_FILES[$row['name']]['name'])) ? $_FILES[$row['name']] : '';
						if (!$content) {
							$content = $this->request->post("origin_{$row['name']}");
						} else {
							if (stripos($row['name'], 'MEDIAID')!==false) {
								$content = $this->request->file($row['name'], 'wechat', false);
								if (strlen($content)) {
									$_content = $wxapi->setMedia(PUBLIC_PATH.$content, $miniprogram['appid']);
									$content = $_content . '|' . $content . '|' . time();
								}
							} else {
								$content = $this->request->file($row['name'], 'wechat', UPLOAD_THIRD);
							}
						}
					} else if (stripos($row['type'], 'checkbox')!==false) {
						$content = $this->request->post($row['name'], 0);
					} else if (stripos($row['type'], 'select')!==false || stripos($row['type'], 'switch')!==false) {
						$content = $this->request->post($row['name']);
						$subconfig = Db::name('config')->where('parent_id', $row['id'])->field('id, name, type')->select();
						if ($subconfig) {
							foreach ($subconfig as $g) {
								$_origin_content = $this->request->post("origin_{$g['name']}");
								$_content = $this->request->post($g['name']);
								if ($g['type']=='file') {
									$_content = (isset($_FILES[$g['name']]) && strlen($_FILES[$g['name']]['name'])) ? $_FILES[$g['name']] : '';
									if (!$_content) {
										$_content = $_origin_content;
									} else {
										if (stripos($g['name'], 'MEDIAID')!==false) {
											$_content = $this->request->file($g['name'], 'wechat', false);
											if (strlen($_content)) {
												$__content = $wxapi->setMedia(PUBLIC_PATH.$_content, $miniprogram['appid']);
												$_content = $__content . '|' . $_content . '|' . time();
											}
										} else {
											$_content = $this->request->file($g['name'], 'wechat', UPLOAD_THIRD);
										}
									}
								} else if (stripos($g['type'], 'checkbox')!==false) {
									$_content = intval($_content);
								} else {
									$_content = trim($_content);
								}
								$data[] = array('miniprogram_id'=>$id, 'config_id'=>$g['id'], 'content'=>$_content);
							}
						}
					} else {
						$content = $this->request->post($row['name']);
					}
					$data[] = array('miniprogram_id'=>$id, 'config_id'=>$row['id'], 'content'=>$content);
				}
				if (count($data)) {
					Db::name('miniprogram_config')->where('miniprogram_id', $id)->delete();
					Db::name('miniprogram_config')->insert($data);
				}
			}
			if ($admin_id>0) {
				if (!Db::name('admin_miniprogram')->where(['admin_id'=>$admin_id, 'miniprogram_id'=>$id])->count() > 0) {
					Db::name('admin_miniprogram')->where('miniprogram_id', $id)->delete();
					Db::name('admin_miniprogram_article')->where('miniprogram_id', $id)->delete();
					Db::name('admin_miniprogram')->insert(['admin_id'=>$admin_id, 'miniprogram_id'=>$id]);
				}
			} else {
				Db::name('admin_miniprogram')->where('miniprogram_id', $id)->delete();
				Db::name('admin_miniprogram_article')->where('miniprogram_id', $id)->delete();
			}
			if ($miniprogram['component_id'] != $component_id) {
				$component = Db::name('component')->where('id', $miniprogram['component_id'])->field('appid')->find();
				deletedir(ROOT_PATH."/runtime/{$component['appid']}/{$miniprogram['appid']}");
				$component = Db::name('component')->where('id', $component_id)->field('appid')->find();
				location("/wx_interface/component_auth?component_appid={$component['appid']}");
			}
			location("/wechat/miniprogram_config?id={$id}&msg=1");
		}
		$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
		$miniprogram = add_domain_deep($miniprogram, ['pic']);
		$miniprogram['admin_id'] = Db::name('admin_miniprogram')->where('miniprogram_id', $miniprogram['id'])->value('admin_id');
		$miniprogram['category_sort'] = '';
		/*$catesort = Db::name('miniprogram_category_sort')->where('miniprogram_id', $id)->find();
		if ($catesort) {
			$miniprogram['category_sort'] = $catesort['category_sort'];
		}
		if ($miniprogram['type']==1) {
			$miniprogram['categories'] = NULL;
		} else if ($miniprogram['type']==2) {
			$miniprogram['categories'] = NULL;
		} else if ($miniprogram['type']==3) {
			$miniprogram['categories'] = NULL;
		} else if ($miniprogram['type']==4) {
			$miniprogram['categories'] = NULL;
		} else {
			$catesort = Db::name('miniprogram_category_sort')->where('miniprogram_id', $id)->find();
			$cates = Db::name('article_category')->where('status', 1);
			if ($catesort) {
				$cates->orderRaw("FIELD(id, {$catesort['category_sort']})");
			} else {
				$cates->order(['sort', 'id'=>'ASC']);
			}
			$cates = $cates->field('id, name')->select();
			$miniprogram['categories'] = $cates;
		}*/
		
		if ($miniprogram['type']==1) $s = 'VIDEO';
		else if ($miniprogram['type']==2) $s = 'BLESSING';
		else if ($miniprogram['type']==3) $s = 'BUDDHA';
		else if ($miniprogram['type']==4) $s = 'BUDDHAAUDIO';
		else $s = 'ARTICLE';
		
		if (!Db::name('miniprogram_config')->where('miniprogram_id', $id)->count() > 0) {
			$rs = Db::name('config')->whereRaw("name LIKE 'G_{$s}/_%' ESCAPE '/'")->field('id, content')->select();
			if ($rs) {
				$data = array();
				foreach ($rs as $row) {
					$data[] = ['miniprogram_id'=>$id, 'config_id'=>$row['id'], 'content'=>$row['content']];
				}
				Db::name('miniprogram_config')->insertAll($data);
			}
		}
		
		$list = array();
		$subcontent = function($miniprogramId, $parentId=0, $where='') use (&$subcontent, &$list) {
			$rs = Db::name('config')->whereRaw("status=1 AND parent_id='{$parentId}' {$where}")->order(['group', 'sort', 'id'=>'ASC'])
				->field("id, name, memo, type, parent_id, parent_value, `group`, '' as content, '' as placeholder, 0 as is_image, '' as image, '' as file_attr, NULL as subconfig")
				->select()->each(function($row) use ($miniprogramId, $parentId, &$subcontent, &$list) {
					if (is_null($row['type'])) $row['type'] = '';
					$row['content'] = Db::name('miniprogram_config')->where(['miniprogram_id'=>$miniprogramId, 'config_id'=>$row['id']])->value('content');
					$row['memo'] = changeColor($row['memo']);
					$row['memo'] = str_replace('<font ', '<font style="float:none;" ' ,$row['memo']);
					if (stripos($row['memo'], '，')!==false || stripos($row['memo'], ',')!==false) {
						$comma = stripos($row['memo'], '，')!==false ? '，' : ',';
						$offset = stripos($row['memo'], '，')!==false ? 3 : 1;
						$row['placeholder'] = substr($row['memo'], stripos($row['memo'], $comma)+$offset);
						$row['memo'] = substr($row['memo'], 0, stripos($row['memo'], $comma));
					}
					if ($row['type']=='file') {
						if (stripos($row['name'], 'MEDIAID')!==false) {
							$row['is_image'] = 1;
							if (strlen($row['content'])) $row['image'] = "/gm/api/wechat/miniprogram_media?miniprogram_id={$miniprogramId}&config_id={$row['id']}";
							$row['file_attr'] = 'data-maxsize="2097152"';
						} else {
							$is_image = is_image($row['content']) ? 1 : 0;
							$row['is_image'] = $is_image;
							$row['image'] = $row['content'];
							//$row['content'] = $is_image ? add_domain($row['content']) : $row['content'];
						}
					} else if (stripos($row['type'], 'radio')!==false || stripos($row['type'], 'checkbox')!==false || stripos($row['type'], 'select')!==false || stripos($row['type'], 'switch')!==false) {
						//[radio|checkbox|select|switch]|值1:字1#值2:字2
						$con = explode('|', $row['type']);
						$type = $con[0];
						if ($type=='checkbox') {
							$content = '<input value="1" name="'.$row['name'].'" type="checkbox" data-type="app" data-style="margin-top:5px;" '.(intval($row['content'])==1?'checked':'').' />';
						} else {
							$con = explode('#', $con[1]);
							$content = '';
							if ($type=='select') {
								$content .= '<select name="'.$row['name'].'" class="some-select-'.$row['name'].'">';
							} else if ($type=='switch') {
								$content .= '<span class="some-switch some-switch-'.$row['name'].'">';
							}
							foreach ($con as $h) {
								$g = explode(':', $h);
								if ($type=='radio') {
									$content .= '<input value="'.$g[0].'" name="'.$row['name'].'" type="radio" data-type="ace" data-text="'.$g[1].'" '.($row['content']==$g[0]?'checked':'').' />';
								} else if ($type=='select') {
									$content .= '<option value="'.$g[0].'" '.($row['content']==$g[0]?'selected':'').'>'.$g[1].'</option>';
								} else if ($type=='switch') {
									$content .= '<label><input type="radio" name="'.$row['name'].'" value="'.$g[0].'" '.($row['content']==$g[0]?'checked':'').' /><div>'.$g[1].'</div></label>';
								}
							}
							if ($type=='select') {
								$content .= '</select>';
								$subconfig = $subcontent($miniprogramId, $row['id']);
								if ($subconfig) {
									$content .= '<script>
$(function(){
	$(".some-select-'.$row['name'].'").on("change", function(){
		$("[data-parent'.$row['id'].'-value]").css("display", "none");
		$("[data-parent'.$row['id'].'-value*=\',"+$(this).selected().val()+",\']").css("display", "block");
	}).trigger("change");
});
</script>';
								}
								$row['subconfig'] = $subconfig;
							} else if ($type=='switch') {
								$content .= '</span>';
								$subconfig = $subcontent($miniprogramId, $row['id']);
								if ($subconfig) {
									$content .= '<script>
$(function(){
	$(".some-switch-'.$row['name'].' :radio").on("change", function(){
		$("[data-parent'.$row['id'].'-value]").css("display", "none");
		$("[data-parent'.$row['id'].'-value*=\',"+$(this).parent().parent().find(":checked").val()+",\']").css("display", "block");
	});
	$(".some-switch-'.$row['name'].' :checked").trigger("change");
});
</script>';
								}
								$row['subconfig'] = $subconfig;
							}
						}
						$row['type'] = $type;
						$row['parse_content'] = $content;
					} else if (stripos($row['type'], 'color')!==false) {
						$row['parse_content'] = '<input value="'.$row['content'].'" name="'.$row['name'].'" type="text" /><div class="some-color" style="background:'.$row['content'].';"></div>';
					} else if ((stripos($row['type'], 'input')!==false || stripos($row['type'], 'textarea')!==false) && stripos($row['type'], '|')!==false) {
						$con = explode('|', $row['type']);
						$row['placeholder'] = $con[1];
					}
					$row['placeholder'] = str_replace('"', '&#34', $row['placeholder']);
					if (is_null($row['subconfig'])) $row['subconfig'] = array();
					if ($parentId==0) {
						if (!isset($list[$row['group']])) $list[$row['group']] = array();
						$list[$row['group']][] = $row;
					}
					return $row;
				});
			return $rs;
		};
		
		$subcontent($id, 0, "AND name LIKE 'G_{$s}/_%' ESCAPE '/'");
		
		$component = Db::name('component')->field('id, name, appid')->select();
		$admins = Db::name('admin')->where('id', '>', 2)->field('id, name')->select();
		
		View::assign('miniprogram', $miniprogram);
		View::assign('component', $component);
		View::assign('admins', $admins);
		View::assign('list', $list);
		return success();
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
		if (!strlen($ids)) return error('请选择小程序');
		$ids = explode(',', $ids);
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		foreach ($ids as $id) {
			$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
			if (!$miniprogram || $miniprogram['review']==1) continue;
			$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
			$wxapi->WX_THIRD = array(
				'appid' => $component['appid'],
				'secret' => $component['appsecret'],
				'token' => $component['token'],
				'aeskey' => $component['aeskey']
			);
			$this->miniprogram_qrcode(false, $id, true);
			$res = $wxapi->miniprogramReview($miniprogram['appid'], $miniprogram['name'], '', '', true);
			$data = array();
			$data['audit_status'] = 1;
			$data['audit_submit_time'] = time();
			$data['auditid'] = $res['auditid'];
			$data['review'] = 1;
			Db::name('miniprogram')->where('id', $miniprogram['id'])->update($data);
		}
		return success('ok');
	}
	
	public function mulunreview() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) return error('请选择小程序');
		$ids = explode(',', $ids);
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		foreach ($ids as $id) {
			$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
			if (!$miniprogram || $miniprogram['review']==0) continue;
			$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
			$wxapi->WX_THIRD = array(
				'appid' => $component['appid'],
				'secret' => $component['appsecret'],
				'token' => $component['token'],
				'aeskey' => $component['aeskey']
			);
			$wxapi->miniprogramUnReview($miniprogram['appid'], true);
			$data = array();
			$data['auditid'] = '';
			$data['audit_status'] = -2;
			$data['review'] = 0;
			Db::name('miniprogram')->where('id', $miniprogram['id'])->update($data);
		}
		return success('ok');
	}
	
	public function muldelete() {
		$ids = $this->request->post('ids');
		if (!strlen($ids)) return error('请选择小程序');
		$ids = explode(',', $ids);
		foreach ($ids as $id) {
			$this->miniprogram_delete($id);
		}
		return success('ok');
	}
	
	public function miniprogram_queryquota() {
		$component_id = $this->request->get('component_id', 0);
		$component = Db::name('component')->where('id', $component_id)->find();
		$miniprogram = Db::name('miniprogram')->where('component_id', $component['id'])->field('appid')->find();
		if (!$miniprogram) return error('没有相关的小程序');
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$json = $wxapi->miniprogramQueryquota($miniprogram['appid']);
		return success($json);
	}
	
	public function miniprogram_clear_quota() {
		$component_id = $this->request->get('component_id', 0);
		$component = Db::name('component')->where('id', $component_id)->find();
		$miniprogram = Db::name('miniprogram')->where(['component_id'=>$component['id'], 'source'=>1])->field('appid')->find();
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$wxapi->clear_quota($miniprogram['appid']);
		return success('ok');
	}
	
	public function miniprogram_only_pic() {
		$id = $this->request->post('id', 0);
		$row = Db::name('miniprogram')->where('id', $id)->field('only_pic')->find();
		if ($row) {
			$only_pic = $row['only_pic']==0 ? 1 : 0;
			Db::name('miniprogram')->where('id', $id)->update(compact('only_pic'));
		}
		return success('ok');
	}
	
	public function miniprogram_status() {
		$id = $this->request->post('id', 0);
		$row = Db::name('miniprogram')->where('id', $id)->field('review')->find();
		if ($row) {
			$review = $row['review']==0 ? 1 : 0;
			Db::name('miniprogram')->where('id', $id)->update(compact('review'));
		}
		return success('ok');
	}
	
	public function miniprogram_promote_status() {
		$id = $this->request->post('id', 0);
		$row = Db::name('miniprogram')->where('id', $id)->field('promote_status')->find();
		if ($row) {
			$promote_status = $row['promote_status']==0 ? 1 : 0;
			Db::name('miniprogram')->where('id', $id)->update(compact('promote_status'));
		}
		return success('ok');
	}
	
	public function miniprogram_checkname() {
		$component_id = $this->request->get('component_id', 0);
		$app_name = $this->request->get('app_name');
		if ($component_id<=0 || !strlen($app_name)) return error('缺少参数');
		$component = Db::name('component')->where('id', $component_id)->find();
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$result = $wxapi->miniprogramCheckName($app_name);
		return success($result);
	}
	
	public function miniprogram_create() {
		$component_id = $this->request->post('component_id', 0);
		$app_name = $this->request->post('app_name');
		$name = $this->request->post('name');
		$code = $this->request->post('code');
		$legal_persona_wechat = $this->request->post('legal_persona_wechat');
		$legal_persona_name = $this->request->post('legal_persona_name');
		$component_phone = $this->request->post('component_phone');
		if (!strlen($name) || !strlen($code) || !strlen($legal_persona_wechat) || !strlen($legal_persona_name)) return error('缺少参数');
		if (Db::name('miniprogram_box')->whereRaw("component_id='{$component_id}' AND status=0 AND name='{$name}' AND code='{$code}' AND legal_persona_wechat='{$legal_persona_wechat}' AND legal_persona_name='{$legal_persona_name}'")->count() > 0) return error('该创建任务正在进行中');
		$component = Db::name('component')->where('id', $component_id)->find();
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$wxapi->miniprogramCreate($name, $code, $legal_persona_wechat, $legal_persona_name, $component_phone, 1, 2);
		$category_first = '快递业与邮政';
		$category_second = '快递、物流';
		Db::name('miniprogram_box')->insert(compact('component_id', 'app_name', 'name', 'code', 'legal_persona_wechat', 'legal_persona_name', 'component_phone', 'category_first', 'category_second'));
		return success('ok');
	}
	
	public function miniprogram_media() {
		$miniprogram_id = $this->request->get('miniprogram_id', 0);
		$config_id = $this->request->get('config_id', 0);
		$miniprogram = Db::name('miniprogram')->where('id', $miniprogram_id)->field('appid, component_id')->find();
		$content = Db::name('miniprogram_config')->where(['miniprogram_id'=>$miniprogram_id, 'config_id'=>$config_id])->value('content');
		$content = explode('|', $content);
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$image = $wxapi->getMedia($content[0], $miniprogram['appid']);
		header('Content-Type:image/png');
		echo $image;
		exit;
	}
	
	public function miniprogram_audit_status() {
		$id = $this->request->get('id', 0);
		$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
		if ($miniprogram) {
			$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
			$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
			$wxapi->WX_THIRD = array(
				'appid' => $component['appid'],
				'secret' => $component['appsecret'],
				'token' => $component['token'],
				'aeskey' => $component['aeskey']
			);
			$res = $wxapi->miniprogramLastStatus($miniprogram['appid']);
			$res['reason'] = str_replace('<br>', '\n', $res['reason']);
			return success($res);
		}
		return error('该小程序不存在');
	}
	
	public function miniprogram_tester() {
		$id = $this->request->post('id', 0);
		$name = $this->request->post('name');
		if (!strlen($name)) return error('缺少体验者微信号');
		$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
		if (!$miniprogram) return error('该小程序不存在');
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$wxapi->miniprogramBindTester($miniprogram['appid'], $name);
		return success('ok');
	}
	
	public function miniprogram_qrcode($returnImage=true, $id=0, $passway=false) {
		if ($id<=0) $id = $this->request->get('id', 0);
		if ($id<=0) $id = $this->request->post('id', 0);
		$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
		if (!$miniprogram) return error('该小程序不存在');
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$version = intval(str_replace('.', '', $miniprogram['version']));
		$version++;
		$versionNum = $version;
		$version = strval($version);
		$version = str_split($version);
		$version = implode('.', $version);
		$template_id = -1;
		$res = $wxapi->miniprogramTemplateList($passway);
		if (!$res) {
			if ($passway) return;
			return error('第三方平台没有设置代码模板库');
		}
		$wechat_appid = Db::name('miniprogram')->whereRaw("component_id='{$miniprogram['component_id']}' AND type='{$miniprogram['type']}' AND source=1")->value('appid');
		foreach ($res as $g) {
			if ($g['source_miniprogram_appid']==$wechat_appid) {
				$template_id = $g['template_id'];
				break;
			}
		}
		if ($template_id == -1) return error('第三方平台没有对应类型的代码模板');
		$window = array(
			'navigationBarTitleText'=>$miniprogram['name']
		);
		if (strlen($miniprogram['navbar_textcolor'])) $window['navigationBarTextStyle'] = $miniprogram['navbar_textcolor'];
		if (strlen($miniprogram['navbar_bgcolor'])) $window['navigationBarBackgroundColor'] = $miniprogram['navbar_bgcolor'];
		$config = array('window'=>$window);
		if (strlen($miniprogram['miniprogram_list'])) {
			$miniprogram_list = array();
			$miniprogramList = preg_split("/[\r\n]+/", $miniprogram['miniprogram_list']);
			for ($i=0; $i<count($miniprogramList); $i++) {
				$miniprogram_list[] = $miniprogramList[$i];
				if ($i==9) break;
			}
			if (count($miniprogram_list)) $config['navigateToMiniProgramAppIdList'] = $miniprogram_list;
		}
		$wxapi->miniprogramUploadCode($miniprogram['appid'], $template_id, $version, $versionNum>100 ? '修复bug' : '正式版', array(
			'version' => $version,
			'shareTitle' => $miniprogram['name'],
			'shareImageUrl' => ''
		), NULL, $config);
		if ($returnImage) {
			$res = $wxapi->miniprogramTestQrcode($miniprogram['appid']);
			header('Content-type: image/jpg');
			echo $res;
			exit;
		}
	}
	
	public function miniprogram_review() {
		$id = $this->request->post('id', 0);
		$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
		if (!$miniprogram) return error('该小程序不存在');
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$this->miniprogram_qrcode(false);
		$res = $wxapi->miniprogramReview($miniprogram['appid'], $miniprogram['name']);
		$data = array();
		$data['audit_status'] = 1;
		$data['audit_submit_time'] = time();
		$data['auditid'] = $res['auditid'];
		$data['review'] = 1;
		Db::name('miniprogram')->where('id', $miniprogram['id'])->update($data);
		return success($res);
	}
	
	public function miniprogram_unreview() {
		$id = $this->request->post('id', 0);
		$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
		if (!$miniprogram) return error('该小程序不存在');
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$wxapi->miniprogramUnReview($miniprogram['appid']);
		$data = array();
		$data['auditid'] = '';
		$data['audit_status'] = -2;
		$data['review'] = 0;
		Db::name('miniprogram')->where('id', $miniprogram['id'])->update($data);
		return success('ok');
	}
	
	public function miniprogram_template() {
		$type = $this->request->post('type', 0);
		$component_id = $this->request->post('component_id', 0);
		$component = Db::name('component')->where('id', $component_id)->find();
		$miniprogram = Db::name('miniprogram')->whereRaw("component_id='{$component['id']}' AND type='{$type}' AND source=1")->find();
		if (!$miniprogram) return error('缺失指定第三方平台、类型的源模板');
		$wechat_appid = $miniprogram['appid'];
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$draft_id = -1;
		$res = $wxapi->miniprogramDraftList();
		foreach ($res as $g) {
			if ($g['source_miniprogram_appid']==$wechat_appid) {
				$draft_id = $g['draft_id'];
				break;
			}
		}
		if ($draft_id == -1) return error('第三方平台没有对应类型的草稿模板');
		$template_id = -1;
		$res = $wxapi->miniprogramTemplateList();
		if (!$res) return error('第三方平台没有设置代码模板库');
		foreach ($res as $g) {
			if ($g['source_miniprogram_appid']==$wechat_appid) {
				$template_id = $g['template_id'];
				break;
			}
		}
		if ($template_id != -1) $wxapi->miniprogramTemplateDelete($template_id);
		$wxapi->miniprogramDraftToTemplate($draft_id);
		return success('ok');
	}
	
	public function miniprogram_serverdomain() {
		$miniprogram_id = $this->request->post('miniprogram_id', 0);
		$domain = $this->request->post('domain', '', '?');
		$miniprogram = Db::name('miniprogram')->where('id', $miniprogram_id)->find();
		if (!$miniprogram) return error('该小程序不存在');
		if (!strlen($domain)) return error('请填写服务器域名');
		$domain = preg_split("/[\r\n]+/", $domain);
		$domains = [];
		for ($i=0; $i<count($domain); $i++) {
			$domains[] = $domain[$i];
			//if ($i==1) break;
		}
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$wxapi->miniprogramServerDomain($miniprogram['appid'], $domains, 'set');
		Db::name('miniprogram')->where('id', $miniprogram_id)->update(array('serverdomain'=>implode(PHP_EOL, $domains)));
		return success('ok');
	}
	
	public function miniprogram_businessdomain() {
		$miniprogram_id = $this->request->post('miniprogram_id', 0);
		$domain = $this->request->post('domain', '', '?');
		$miniprogram = Db::name('miniprogram')->where('id', $miniprogram_id)->find();
		if (!$miniprogram) return error('该小程序不存在');
		if (!strlen($domain)) return error('请填写业务域名');
		$domain = preg_split("/[\r\n]+/", $domain);
		$domains = [];
		for ($i=0; $i<count($domain); $i++) {
			$domains[] = $domain[$i];
			//if ($i==1) break;
		}
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		$wxapi->miniprogramBusinessDomain($miniprogram['appid'], $domains, 'set');
		Db::name('miniprogram')->where('id', $miniprogram_id)->update(array('businessdomain'=>implode(PHP_EOL, $domains)));
		return success('ok');
	}
	
	public function miniprogram_delete($id=0) {
		$isMul = false;
		if ($id>0) $isMul = true;
		if (!$isMul) $id = $this->request->get('id', 0);
		$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
		$type = $miniprogram['type'];
		$component = Db::name('component')->where('id', $miniprogram['component_id'])->find();
		if ($component) deletedir(ROOT_PATH."/runtime/{$component['appid']}/{$miniprogram['appid']}");
		Db::name('miniprogram')->delete($id);
		Db::name('miniprogram_article_hidden')->where('miniprogram_id', $id)->delete();
		Db::name('miniprogram_config')->where('miniprogram_id', $id)->delete();
		//Db::name('miniprogram_category_sort')->where('miniprogram_id', $id)->delete();
		Db::name('wechat_template')->where(['parent_type'=>1, 'parent_id'=>$id])->delete();
		Db::name('wechat_template_subscribe')->where(['parent_type'=>1, 'parent_id'=>$id])->delete();
		Db::name('admin_miniprogram')->where('miniprogram_id', $id)->delete();
		Db::name('admin_miniprogram_article')->where('miniprogram_id', $id)->delete();
		switch ($type) {
			case 0:
				Db::name('article_attr')->where('miniprogram_id', $id)->delete();
				if ($miniprogram['source']==1) deletedir(ROOT_PATH."/miniprogram/article/{$miniprogram['name']}");
				break;
			case 1:
				//Db::name('video_attr')->where('miniprogram_id', $id)->delete();
				if ($miniprogram['source']==1) deletedir(ROOT_PATH."/miniprogram/video/{$miniprogram['name']}");
				break;
			case 2:
				Db::name('blessing_attr')->where('miniprogram_id', $id)->delete();
				if ($miniprogram['source']==1) deletedir(ROOT_PATH."/miniprogram/blessing/{$miniprogram['name']}");
				break;
			case 3:
				Db::name('buddha_attr')->where('miniprogram_id', $id)->delete();
				if ($miniprogram['source']==1) deletedir(ROOT_PATH."/miniprogram/buddha/{$miniprogram['name']}");
				break;
		}
		location('/wechat/miniprogram');
	}
	
	//列表
	public function wxmenu() {
		$appid = '';
		$secret = '';
		$access_token = '';
		$wechat = NULL;
		$list = array();
		if (Db::query('SHOW TABLES LIKE "wechat"')) {
			$list = Db::name('wechat')->where('status', 1)->field('appid, name, alias, pic')->select()->toArray();
			$list = add_domain_deep($list, ['pic']);
			$appid = $this->request->get('appid');
			if (!strlen($appid) && $list) $appid = $list[0]['appid'];
			if (strlen($appid)) {
				$wechat = Db::name('wechat')->where(['appid'=>$appid, 'status'=>1])->find();
				if ($wechat) {
					if ($wechat['appsecret'] && strlen($wechat['appsecret'])) {
						$wxapi = new \wechatCallbackAPI\wechatCallbackAPI($appid, $wechat['appsecret']);
						$access_token = $wxapi->getAccessToken("manual/access_token.{$appid}.json");
						if (!strlen($access_token)) return error('获取 access_token 失败');
					}
				} else {
					$appid = '';
				}
			}
		}
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		if ($wechat) {
			$component = Db::name('component')->where('id', $wechat['component_id'])->find();
			$wxapi->WX_THIRD = array(
				'appid' => $component['appid'],
				'secret' => $component['appsecret'],
				'token' => $component['token'],
				'aeskey' => $component['aeskey']
			);
		}
		if (IS_POST) {
			$menu = $this->request->post('menu');
			//$menu = '{"button":[{"type":"view","name":"上头条","url":"https://m.joyicloud.com/","sub_button":[]},{"type":"view","name":"达人榜","url":"https://m.joyicloud.com/wap/?app=talent","sub_button":[]},{"name":"我的","sub_button":[{"type":"view","name":"个人中心","url":"https://m.joyicloud.com/wap/?app=member","sub_button":[]},{"type":"view","name":"我的团队","url":"https://m.joyicloud.com/wap/?app=member&act=team","sub_button":[]},{"type":"view","name":"推广赚钱","url":"https://m.joyicloud.com/wap/?app=member&act=poster","sub_button":[]},{"type":"view","name":"我的钱包","url":"https://m.joyicloud.com/wap/?app=member&act=commission","sub_button":[]},{"type":"view","name":"我发布的任务","url":"https://m.joyicloud.com/wap/?app=member&act=task","sub_button":[]}]}]}';
			$wxapi->setMenu($menu, $appid, $secret, $access_token);
			location('/wechat/wxmenu');
		}
		$menu = $wxapi->getMenu($appid, $secret, $access_token);
		if (is_array($menu)) $menu = json_encode($menu['menu']);
		View::assign('menu', $menu);
		View::assign('list', $list);
		View::assign('appid', $appid);
		return success();
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
		if ($parent_id<=0) return error('数据错误');
		$rs = Db::name('wechat_template')->where(['parent_type'=>$parent_type, 'parent_id'=>$parent_id])->order('id', 'DESC')->field("*, '' as title, 0 as count")
			->paginate(['list_rows'=>10, 'query'=>request()->param()])->each(function($g) {
				$title = json_decode($g['content_data'], true);
				foreach ($title as $t) {
					$title = $t['value'];
					break;
				}
				$g['title'] = $title;
				$g['count'] = Db::name('wechat_template_subscribe')->where(['template_id'=>$g['template_id'], 'send_time'=>0])->count();
				return $g;
			});
		View::assign('rs', $rs);
		setViewAssign(compact('parent_id', 'parent_type'));
		setViewPage($rs);
		return success();
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
				Db::name('wechat_template')->where('id', $id)->update($data);
			} else {
				$id = Db::name('wechat_template')->insert($data);
			}
			location("/wechat/template?parent_id={$parent_id}&parent_type={$parent_type}&msg=1");
		} else if ($id>0) { //显示
			$row = Db::name('wechat_template')->where('id', $id)->find();
		} else {
			$row = t('wechat_template');
		}
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		if ($parent_type==0) {
			$wechat = Db::name('wechat')->where('id', $parent_id)->find();
		} else {
			$wechat = Db::name('miniprogram')->where('id', $parent_id)->find();
		}
		$component = Db::name('component')->where('id', $wechat['component_id'])->find();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		if ($parent_type==0) {
			$templates = $wxapi->getTemplateMessage($wechat['appid']);
		} else {
			$templates = $wxapi->miniprogramGetTemplateMessage($wechat['appid']);
		}
		$templates = json_decode(json_encode($templates));
		
		View::assign('row', $row);
		View::assign('wechat', $wechat);
		View::assign('templates', $templates);
		View::assign('parent_id', $parent_id);
		View::assign('parent_type', $parent_type);
		return success('ok', 'template_edit.html');
	}
	
	//获取模板消息列表
	public function get_template_list() {
		$parent_id = $this->request->get('parent_id', 0);
		$parent_type = $this->request->get('parent_type', 0);
		if ($parent_id<=0) return error('缺少参数');
		$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
		if ($parent_type==0) {
			$wechat = Db::name('wechat')->where('id', $parent_id)->find();
		} else {
			$wechat = Db::name('miniprogram')->where('id', $parent_id)->find();
		}
		$component = Db::name('component')->where('id', $wechat['component_id'])->find();
		$wxapi->WX_THIRD = array(
			'appid' => $component['appid'],
			'secret' => $component['appsecret'],
			'token' => $component['token'],
			'aeskey' => $component['aeskey']
		);
		if ($parent_type==0) {
			$templates = $wxapi->getTemplateMessage($wechat['appid']);
		} else {
			$templates = $wxapi->miniprogramGetTemplateMessage($wechat['appid']);
		}
		return success($templates);
	}
	
	//发送模板消息
	public function template_send() {
		$id = $this->request->get('id', 0);
		if ($id<=0) return error('缺少参数');
		$row = Db::name('wechat_template')->where('id', $id)->find();
		if (!$row) return error('数据错误');
		$rs = Db::name('wechat_template_subscribe')->where(['template_id'=>$row['template_id'], 'send_time'=>0])->select();
		if ($rs) {
			set_time_limit(0);
			ini_set('memory_limit', '10240M');
			if ($row['parent_type']==0) {
				$wechat = Db::name('wechat')->where('id', $row['parent_id'])->find();
			} else {
				$wechat = Db::name('miniprogram')->where('id', $row['parent_id'])->find();
			}
			$wxapi = new \wechatCallbackAPI\wechatCallbackAPI();
			$component = Db::name('component')->where('id', $wechat['component_id'])->find();
			$wxapi->WX_THIRD = array(
				'appid' => $component['appid'],
				'secret' => $component['appsecret'],
				'token' => $component['token'],
				'aeskey' => $component['aeskey']
			);
			foreach ($rs as $g) {
				if ($row['parent_type']==0) {
					$miniprogram = array();
					if (strlen($row['appid'])) $miniprogram = array('appid'=>$row['appid'], 'pagepath'=>$row['pagepath']);
					$wxapi->sendTemplateMessage($wechat['appid'], $g['openid'], $g['template_id'], json_decode($row['content_data']), $row['url'], $miniprogram, '', true);
				} else {
					$wxapi->miniprogramSendTemplateMessage($wechat['appid'], $g['openid'], $g['template_id'], json_decode($row['content_data']), strlen($row['pagepath']) ? $row['pagepath'] : $row['url'], true);
				}
				Db::name('wechat_template_subscribe')->where('id', $g['id'])->update(array('send_time'=>time()));
			}
		}
		script('发送成功', 'history.back()');
	}
	
	//客服消息
	public function customer() {
		$where = [];
		$keyword = $this->request->get('keyword');
		if (strlen($keyword)) {
			$where[] = ['name|alias', 'like', "%{$keyword}%"];
		}
		$rs = Db::name('wechat_customer')->where($where)->order('id', 'DESC')->field('*, 0 as mp_count')
			->paginate(['list_rows'=>10, 'query'=>request()->param()])->each(function($g) {
				$g['mp_count'] = Db::name('wechat_customer_mp')->where('customer_id', $g['id'])->count();
				return $g;
			});
		View::assign('rs', $rs);
		setViewAssign(compact('keyword'));
		return success();
	}
	
	public function customer_add() {
		return $this->customer_edit();
	}
	public function customer_edit() {
		$id = $this->request->get('id', 0);
		if (IS_POST) {
			$preview = $this->request->post('preview', 0);
			$id = $this->request->post('id', 0);
			$name = $this->request->post('name');
			$title = $this->request->post('title');
			$memo = $this->request->post('memo');
			$pic = $this->request->file('pic', 'wxcustomer', UPLOAD_THIRD);
			$url = $this->request->post('url');
			$predict_count = $this->request->post('predict_count', 0);
			$mp = $this->request->post('mp', '', []);
			$send_time = strtotime($this->request->post('send_time'));
			$data = compact('name', 'title', 'memo', 'pic', 'url', 'predict_count', 'send_time');
			if (is_int($mp)) $mp = [$mp];
			if (!is_array($mp) || !count($mp)) return error('请选择公众号');
			
			$wechat_customer = 'wechat_customer';
			$wechat_customer_mp = 'wechat_customer_mp';
			if ($preview) {
				$wechat_customer = 'wechat_customer_preview';
				$wechat_customer_mp = 'wechat_customer_mp_preview';
				$ps = Db::name($wechat_customer)->whereRaw(whereTime('h', 'add_time', '>48'))->field('id')->select()->each(function($g) use ($wechat_customer_mp, $wechat_customer) {
					Db::name($wechat_customer_mp)->where('customer_id', $g['id'])->delete();
					Db::name($wechat_customer)->delete($g['id']);
				});
			}
			
			if ($id>0) {
				Db::name($wechat_customer)->where('id', $id)->update($data);
			} else {
				$data['add_time'] = time();
				$id = Db::name($wechat_customer)->insert($data);
			}
			Db::name($wechat_customer_mp)->where('customer_id', $id)->delete();
			if (count($mp)) {
				$data = [];
				foreach ($mp as $m) {
					$data[] = ['customer_id'=>$id, 'wechat_id'=>$m];
				}
				Db::name($wechat_customer_mp)->insertAll($data);
			}
			if ($preview) {
				return success($id);
			} else {
				location('/wechat/customer');
			}
		} else if ($id>0) { //显示
			$row = Db::name('wechat_customer')->where('id', $id)->find();
			$mp = Db::name('wechat_customer_mp')->where('customer_id', $row['id'])->column('wechat_id');
			$row['mp'] = implode(',', $mp);
		} else {
			$wechat_id = $this->request->get('wechat_id');
			$row = t('wechat_customer');
			$row['mp'] = $wechat_id;
		}
		$list = Db::name('wechat')->order('id', 'ASC')->field('id, appid, name, pic, alias, 0 as alive_fans')->select()->each(function($g) {
			$g['alive_fans'] = Db::name('wechat_user')->where('wechat_id', $g['id'])->whereRaw(whereTime('h', 'add_time', '<48'))->count();
			return $g;
		});
		View::assign('row', $row);
		View::assign('list', $list);
		return success('ok', 'customer_edit.html');
	}
	
	public function customer_delete() {
		$id = $this->request->get('id', 0);
		Db::name('wechat_customer')->delete($id);
		Db::name('wechat_customer_mp')->where('customer_id', $id)->delete();
		location('/wechat/customer');
	}
	
	public function customer_detail() {
		$id = $this->request->get('id', 0);
		$rs = Db::name('wechat_customer_mp')->alias('wcm')->leftJoin('wechat w', 'wcm.wechat_id=w.id')->where('wcm.customer_id', $id)->field('w.pic, w.name, wcm.count, wcm.status')->select();
		$rs = add_domain_deep($rs, ['pic']);
		return success($rs);
	}
	
	public function miniprogram_source() {
		$id = $this->request->get('id', 0);
		if ($this->admin_id!=1 || $id<=0) return error('数据错误');
		$miniprogram = Db::name('miniprogram')->where('id', $id)->find();
		if (!$miniprogram) return error('数据错误');
		if ($miniprogram['source']==0) Db::name('miniprogram')->whereRaw("source='1' AND type='{$miniprogram->type}' AND component_id='{$miniprogram['component_id']}'")->update(array('source'=>0));
		$source = $miniprogram['source']==1 ? 0 : 1;
		Db::name('miniprogram')->where('id', $id)->update(compact('source'));
		script('', 'history.back()');
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
		if (!is_dir($MINIPROGRAM_PATH)) return error('源代码文件不存在');
		$source = Db::name('miniprogram')->where('id', $source_id)->find();
		if (!$source) return error("ID为{$source_id}的小程序不存在");
		if (count($singles)) {
			$dirname = 'single';
		} else {
			$typeName = ['article', 'video', 'blessing', 'buddha'];
			$dirname = $typeName[$source['type']];
		}
		$source_path = $MINIPROGRAM_PATH.'/'.$dirname.'/'.$source['name'];
		if (!is_dir($source_path)) return error("{$source['name']}的源代码文件不存在");
		if (count($singles)) {
			$rs = Db::name('miniprogram')->where("id IN (".implode(',', $singles).")")->find('name, appid');
		} else {
			$rs = Db::name('miniprogram')->where("id NOT IN ({$source_id}) AND source=1 AND type='{$source['type']}'")->find('name, appid');
		}
		foreach ($rs as $g) {
			$path = $MINIPROGRAM_PATH.'/'.$dirname.'/'.$g['name'];
			deletedir($path);
			$this->_copydir($source_path, $path, false);
			$this->_file_content_replace($path.'/app.json', function($content) use($source, $g) {
				$content = str_replace('"navigationBarTitleText": "'.$source['name'].'",', '"navigationBarTitleText": "'.$g['name'].'",', $content);
				return $content;
			});
			$this->_file_content_replace($path.'/project.config.json', function($content) use($source, $g) {
				$content = str_replace('"appid": "'.$source['appid'].'",', '"appid": "'.$g['appid'].'",', $content);
				$content = str_replace('"projectname": "'.$source['name'].'",', '"projectname": "'.urlencode($g['name']).'",', $content);
				$content = str_replace('"projectname": "'.urlencode($source['name']).'",', '"projectname": "'.urlencode($g['name']).'",', $content);
				return $content;
			});
		}
		return success('ok');
	}
	
	//复制文件夹,对应根目录
	private function _copydir($source, $destination, $station=true) {
		$source_path = str_replace(ROOT_PATH, '', $source);
		$destination_path = str_replace(ROOT_PATH, '', $destination);
		if ($station) {
			$source_path = ROOT_PATH . $source_path;
			$destination_path = ROOT_PATH . $destination_path;
		}
		if (!is_dir($source_path)) return false;
		if (!is_dir($destination_path)) makedir($destination_path);
		$handle = dir($source_path);
		while ($entry = $handle->read()) {
			if ($entry != '.' && $entry != '..') {
				if (stripos($entry, '.DS_Store') !== false || stripos($entry, '.git') !== false || stripos($entry, '.svn') !== false || stripos($entry, '.idea') !== false) continue;
				if (is_dir($source_path . '/' . $entry)) {
					$this->_copydir($source_path . '/'.$entry, $destination_path . '/' . $entry, $station);
				} else {
					if (!file_exists($destination_path . '/' . $entry)) {
						copy($source_path . '/' . $entry, $destination_path . '/' . $entry);
					}
				}
			}
		}
		return true;
	}
	
	//替换文件内容,对应根目录
	private function _file_content_replace($file, $callback, $station=true) {
		$origin_file = $file;
		$file = str_replace(ROOT_PATH, '', $file);
		if ($station) $file = ROOT_PATH.$file;
		if (file_exists($file)) {
			clearstatcache();
			$fp = fopen($file, 'r');
			flock($fp, LOCK_EX);
			$content = fread($fp, filesize($file));
			if (strlen($content) && $callback && !is_string($callback) && is_callable($callback)) $content = $callback($content, $origin_file);
			flock($fp, LOCK_UN);
			fclose($fp);
			$fp = fopen($file, 'w');
			flock($fp, LOCK_EX);
			fwrite($fp, $content);
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}
}
