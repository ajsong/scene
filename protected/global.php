<?php
//实例化一个模型类
function m($model) {
	return shortcut::model($model);
}

//实例化一个类
function o($app='', $path='api') {
	return shortcut::classs($app, $path);
}

//根据表字段实例化一个字典类
function t($table='', $fields='*') {
	return shortcut::app($table, $fields);
}

//实例化一个插件
function p($plugin, $type='') {
	return shortcut::plugin($plugin, $type);
}

//获取 REQUEST_METHOD, ex: i('method.name.default.type')
function i($mark) {
	return shortcut::request($mark);
}

//生成序列号
function generate_sn() {
	return date('ymdHis').rand(10000, 99999);
}

//生成sign
function generate_sign() {
	return md5(md5(rand(100000,999999)).time());
}

//生成密码盐值salt
function generate_salt() {
	return rand(100000, 999999);
}

//生成指定位数的随机整数
function generate_code($length=4) {
	return rand(pow(10,($length-1)), pow(10,$length)-1);
}

//根据盐值生成加密密码
function crypt_password($password, $salt) {
	if (!strlen($password) || !strlen($salt)) return '';
	return md5(md5($password).$salt);
}

//获取头部信息
function get_header($key='') {
	if (!function_exists('getallheaders')) {
		function getallheaders() {
			$headers = array();
			foreach ($_SERVER as $name => $value) {
				if (substr($name, 0, 5) == 'HTTP_') {
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
			if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
				$headers['AUTHORIZATION'] = $_SERVER['PHP_AUTH_DIGEST'];
			} else if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
				$headers['AUTHORIZATION'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
			}
			if (isset($_SERVER['CONTENT_LENGTH'])) {
				$headers['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH'];
			}
			if (isset($_SERVER['CONTENT_TYPE'])) {
				$headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
			}
			return $headers;
		}
	}
	$headers = getallheaders();
	if (!is_array($headers)) $headers = array();
	if (strlen($key)) $headers = isset($headers[$key]) ? $headers[$key] : '';
	return $headers;
}

//upload_switch:上传方法(0:本地,[1|字符串]:指定插件里的第三方|2:文件分割上传到本地), return_detail:返回文件所有信息(数组), file_type:NULL不限制类型, file_name:指定文件名(加上后缀名即无后缀名时使用指定)
//上传文件,自动判断name是否数组形式
function upload_file($mydir, $field, $upload_switch=1, $return_detail=false, $file_type=array('jpg', 'jpeg', 'png', 'gif', 'bmp'), $file_name='') {
	if (!isset($_FILES[$field])) exit('FILE ERROR');
	if (is_array($_FILES[$field]['name'])) {
		return upload_array_file($mydir, $field, $upload_switch, $return_detail, $file_type, $file_name);
	} else {
		return upload_one_file($mydir, $field, $upload_switch, $return_detail, $file_type, $file_name);
	}
}

//上传单个文件
function upload_one_file($mydir, $field, $upload_switch=1, $return_detail=false, $file_type=array('jpg', 'jpeg', 'png', 'gif', 'bmp'), $file_name='') {
	$file = upload_obj_file($_FILES, $mydir, $field, $upload_switch, $return_detail, $file_type, $file_name);
	if (!$file || (is_array($file) && !count($file))) $file = isset($_POST["origin_{$field}"]) ? trim($_POST["origin_{$field}"]) : '';
	return $file;
}

//上传数组形式文件
//20151211 by ajsong 为了兼容数组形式的file, e.g.: name="pic[]"
function upload_array_file($mydir, $field, $upload_switch=1, $return_detail=false, $file_type=array('jpg', 'jpeg', 'png', 'gif', 'bmp'), $file_name='') {
	if (!isset($_FILES[$field])) return array();
	$file = $_FILES[$field];
	$name = $file['name'];
	$type = $file['type'];
	$tmp_name = $file['tmp_name'];
	$error = $file['error'];
	$size = $file['size'];
	$files = array();
	for ($i=0; $i<count($name); $i++) {
		if ($name[$i] != '') {
			$fileObj = array();
			$fileObj[$field] = array();
			$fileObj[$field]['name'] = $name[$i];
			$fileObj[$field]['type'] = $type[$i];
			$fileObj[$field]['tmp_name'] = $tmp_name[$i];
			$fileObj[$field]['error'] = $error[$i];
			$fileObj[$field]['size'] = $size[$i];
			$files[] = upload_obj_file($fileObj, $mydir, $field, $upload_switch, $return_detail, $file_type, $file_name);
		} else {
			$files[] = $return_detail ? array() : '';
		}
	}
	return $files;
}

//上传多个文件, e.g.:name="pic1", name="pic2", name="pic3"
function upload_files($mydir, $field, $num=3, $upload_switch=1, $return_detail=false, $file_type=array('jpg', 'jpeg', 'png', 'gif', 'bmp'), $file_name='') {
	$files = array();
	for ($i=1; $i<=$num; $i++) {
		$files[] = upload_one_file($mydir, $field.$i, $upload_switch, $return_detail, $file_type, $file_name);
	}
	return $files;
}

//上传文件操作
function upload_obj_file($fileObj, $mydir, $field=NULL, $upload_switch=1, $return_detail=false, $file_type=array('jpg', 'jpeg', 'png', 'gif', 'bmp'), $file_name='') {
	set_time_limit(0);
	ini_set('memory_limit', '10240M');
	global $upload_type, $client_id;
	if (isset($upload_type) && $upload_type=='location') $upload_switch = 0;
	if (defined('IS_SAAS') && IS_SAAS && ((defined('IS_AG') && IS_AG) || (defined('IS_OP') && IS_OP) || (isset($upload_type) && $upload_type=='softstao'))) {
		$upload_type = 'ypyun';
		$GLOBALS['upyun_bucketname'] = 'bangfang';
		$GLOBALS['upyun_operator_name'] = 'bangfang2';
		$GLOBALS['upyun_operator_pwd'] = 'WfZ9jXRJH#Ts';
		$GLOBALS['upyun_domain'] = 'http://bangfang.b0.upaiyun.com';
	}
	if (defined('IS_SAAS') && IS_SAAS && ((isset($client_id) && intval($client_id)<=0) || !isset($upload_type))) $upload_type = 'ypyun';
	$file_array = array('file'=>'', 'type'=>'', 'name'=>'', 'tmp_name'=>'');
	if ( !$field ||
		(isset($fileObj[$field]) &&
			(is_string($fileObj[$field]) ||
				(is_array($fileObj[$field]) &&
					((!is_array($fileObj[$field]['size']) && $fileObj[$field]['size']>0) || (is_array($fileObj[$field]['size']) && $fileObj[$field]['size'][0]>0))))) ) {
		if (is_string($file_type) && strlen($file_type)) $file_type = explode(',', $file_type);
		//if (!is_array($file_type) || !count($file_type)) $file_type = array('jpg', 'jpeg', 'png', 'gif', 'bmp');
		if (!is_array($file_type)) $file_type = array();
		$fileEle = !$field ? $fileObj : $fileObj[$field];
		$clientDir = '';
		if (defined('IS_SAAS') && IS_SAAS && defined('IS_AG') && defined('IS_OP') && !IS_AG && !IS_OP && isset($client_id)) $clientDir = "/client/{$client_id}";
		if (is_null($mydir)) {
			$dir = '';
		} else {
			if (strlen($mydir)) {
				if (substr($mydir, 0, 1)=='/') {
					$upload_switch = 0;
					$dir = $mydir;
				} else {
					$dir = UPLOAD_PATH.$clientDir.'/'.$mydir.'/'.date('Y').'/'.date('m').'/'.date('d');
				}
			} else {
				$dir = UPLOAD_PATH.$clientDir.'/'.date('Y').'/'.date('m').'/'.date('d');
			}
		}
		if ((!is_string($upload_switch) && $upload_switch==1) || (is_bool($upload_switch) && $upload_switch)) $upload_switch = $upload_type;
		if (is_bool($upload_switch) && !$upload_switch) $upload_switch = 0;
		$uploadCallback = function($fileObj) use ($field, $dir, $upload_switch, $file_type, $file_name, $upload_type) {
			$fileEle = !$field ? $fileObj : $fileObj[$field];
			$ext = '';
			$upload_file = '';
			if (is_array($fileEle) && isset($fileEle['tmp_name'])) {
				$upload_file = $fileEle['name'];
				$fs = fopen($fileEle['tmp_name'], 'rb');
				$byte = fread($fs, 2);
				fclose($fs);
			} else if (is_string($fileEle)) {
				if (preg_match('/^http/', $fileEle)) {
					$fs = fopen($fileEle, 'rb');
					$byte = fread($fs, 2);
					fclose($fs);
				} else {
					$byte = substr($fileEle, 0, 2);
				}
			} else {
				$byte = 0;
			}
			$info = @unpack('C2chars', $byte);
			$code = intval($info['chars1'].$info['chars2']);
			switch ($code) {
				case 6063://php,xml
				case 7790://exe,dll
				case 64101:error('请选择'.implode(',', $file_type).'类型的文件');break;//bat
				case 7173:$ext = '.gif';break;
				case 255216:$ext = '.jpg';break;
				case 13780:$ext = '.png';break;
				case 6677:$ext = '.bmp';break;
				case 8297:$ext = '.rar';break;
				case 4742:$ext = '.js';break;
				case 5666:$ext = '.psd';break;
				case 10056:$ext = '.torrent';break;
				case 239187:$ext = '.txt';break;//txt,aspx,asp,sql
				case 6033:$ext = '.html';break;//htm,html
				case 208207://doc,xls,ppt
				case 8075://docx,xlsx,pptx,zip,mmap
				default:
					$ext = '';
					if ($code==208207 && function_exists('finfo_open')) {
						$finfo = finfo_open(FILEINFO_MIME_TYPE);
						$type = @finfo_file($finfo, $upload_file);
						if ($type) {
							if (strpos($type, 'msword')!==false) $ext = '.doc';
							else if (strpos($type, 'vnd.ms-office')!==false) $ext = '.xls';
							else if (strpos($type, 'powerpoint')!==false) $ext = '.ppt';
						}
					}
					if (!strlen($ext)) {
						if (strlen($upload_file)) {
							$type = pathinfo($upload_file, PATHINFO_EXTENSION);
							if (strlen($type)) $ext = $type;
						} else if (strpos($fileEle, 'http')!==false && strpos($fileEle, '.')!==false) {
							$arr = explode('.', $fileEle);
							$ext = end($arr);
						}
						if (!strlen($ext)) {
							if (is_array($file_type) && count($file_type)==1) {
								$ext = end($file_type);
							} else if (isset($fileEle['type'])) {
								$arr = explode('/', $fileEle['type']);
								$ext = end($arr);
							}
						}
					}
					if (strlen($ext) && substr($ext, 0, 1)!='.') $ext = '.'.$ext;
					break;
			}
			$array = array('file'=>'', 'type'=>'', 'name'=>'', 'tmp_name'=>'');
			if (isset($fileEle['tmp_name'])) $array['tmp_name'] = $fileEle['tmp_name'];
			$file = '';
			if (isset($_POST['split_name']) && strlen($_POST['split_name'])) {
				$file_name = date('Ymd').'_'.$_POST['split_name'];
				$filename = $file_name.$ext;
			} else if (is_string($file_name) && strpos($file_name, '.')!==false) {
				$file_name = explode('.', $file_name);
				if (!strlen($ext)) $ext = $file_name[1];
				$file_name = strlen($file_name[0]) ? $file_name[0] : generate_sn();
				$filename = $file_name.'.'.$ext;
			} else {
				$filename = (is_array($fileEle) && isset($fileEle['name'])) ? $fileEle['name'] : generate_sn().$ext;
			}
			$array['name'] = $filename;
			$names = explode('.', $filename);
			$ext = $names[count($names)-1];
			$array['type'] = $ext;
			if (is_array($fileEle) && isset($fileEle['size'])) $array['size'] = $fileEle['size'];
			if (is_bool($file_name)) {
				$name = '';
				if ($file_name && is_array($fileEle) && isset($fileEle['name'])) {
					unset($names[count($names)-1]);
					$name = implode('.', $names);
				}
				$file_name = $name;
			}
			if (count($file_type) && !in_array(strtolower($ext), $file_type)) error('请选择'.implode(',', $file_type).'类型的文件，当前是 '.$ext);
			$name = strlen($file_name) ? $file_name : generate_sn();
			if (is_string($upload_switch)) {
				//上传到第三方文件存储
				$upload = p('upload', $upload_switch);
				$result = $upload->upload($fileObj, $field, str_replace('/public/', '/', $dir), $name, $ext);
				if (isset($result['width']) && intval($result['width'])>0) $array['width'] = intval($result['width']);
				if (isset($result['height']) && intval($result['height'])>0) $array['height'] = intval($result['height']);
				$file = $result['file'];
			} else if ($upload_switch==2) {
				//文件分割上传到服务器
				$upload_dir = ROOT_PATH . (!strlen($dir) ? UPLOAD_PATH : $dir);
				if (!is_dir($upload_dir)) makedir($upload_dir);
				$savename = $name.'.'.$ext;
				$pathname = $upload_dir.(substr($dir,-1)=='/'?'':'/').$savename;
				if ($field) {
					$tmp_name = $fileEle['tmp_name'];
					if (file_exists($pathname)) @unlink($pathname);
					if (function_exists('move_uploaded_file')) {
						@move_uploaded_file($tmp_name, $pathname);
					} else if(function_exists('rename')) {
						@rename($tmp_name, $pathname);
					} else if (function_exists('copy')) {
						@copy($tmp_name, $pathname);
					}
				} else {
					$handle = @fopen($upload_dir.'/'.$savename, 'w') or error('Unable to write file!');
					@fwrite($handle, $fileEle);
					@fclose($handle);
				}
				$file = $dir.(substr($dir,-1)=='/'?'':'/').$savename;
				$splitTotal = intval($_POST['split_total']);
				for ($i=0; $i<$splitTotal; $i++) {
					$names = explode('_', $name);
					$splitName = $names[0].'_'.$names[1].'_'.$i;
					$pathname = ROOT_PATH.str_replace($name, $splitName, $file);
					if (!file_exists($pathname)) exit;
				}
				$pname = generate_sn();
				$array['name'] = $pname.'.'.$ext;
				$leader = @fopen($upload_dir.(substr($dir,-1)=='/'?'':'/').$pname.'.'.$ext, 'ab');
				for ($i=0; $i<$splitTotal; $i++) {
					$names = explode('_', $name);
					$splitName = $names[0].'_'.$names[1].'_'.$i;
					$pathname = ROOT_PATH.str_replace($name, $splitName, $file);
					$handle = @fopen($pathname, 'rb');
					@fwrite($leader, @fread($handle, @filesize($pathname)));
					@fclose($handle);
					@unlink($pathname);
				}
				@fclose($leader);
				$file = $dir.(substr($dir,-1)=='/'?'':'/').$pname.'.'.$ext;
				$file = str_replace('/public/', '/', $file);
			} else {
				//上传到服务器
				$upload_dir = ROOT_PATH . (!strlen($dir) ? UPLOAD_PATH : $dir);
				if (!is_dir($upload_dir)) makedir($upload_dir);
				if (is_array($fileEle)) {
					$keys = array_keys($fileEle);
					if (array_sum($keys)==array_sum(array_keys($keys))) {
						require_once(SDK_PATH . '/class/upload/class.upload.php');
						$upload = new Upload($fileEle);
						if ($upload->uploaded) {
							$upload->file_new_name_body = $name;
							$upload->Process($upload_dir);
							if ($upload->processed) {
								if (stripos($fileEle['type'],'image/')!==false) {
									$array['width'] = $upload->image_src_x;
									$array['height'] = $upload->image_src_y;
								}
								$file = $dir.(substr($dir,-1)=='/'?'':'/').$upload->file_dst_name;
							}
						}
						$upload->Clean();
					} else if (isset($fileEle['tmp_name'])) {
						$tmp_name = $fileEle['tmp_name'];
						$savename = $name.'.'.$ext;
						$pathname = $upload_dir.(substr($dir,-1)=='/'?'':'/').$savename;
						if (file_exists($pathname)) unlink($pathname);
						if (function_exists('move_uploaded_file')) {
							move_uploaded_file($tmp_name, $pathname);
						} else if(function_exists('rename')) {
							rename($tmp_name, $pathname);
						} else if (function_exists('copy')) {
							copy($tmp_name, $pathname);
						}
						$file = $dir.(substr($dir,-1)=='/'?'':'/').$savename;
					}
				} else {
					$savename = $name.'.'.$ext;
					$pathname = $upload_dir.(substr($dir,-1)=='/'?'':'/').$savename;
					$handle = @fopen($pathname, 'w') or error('Unable to write file!');
					@fwrite($handle, $fileEle);
					@fclose($handle);
					$file = $dir.(substr($dir,-1)=='/'?'':'/').$savename;
				}
				$file = str_replace('/public/', '/', $file);
				if (in_array($ext, ['jpg', 'jpeg'])) {
					image_compress('/public/'.$file, 1, '/public/'.$file);
				}
			}
			$array['file'] = $file;
			return $array;
		};
		if (is_array($fileEle) && isset($fileEle['size']) && is_array($fileEle['size'])) {
			$file = array('file'=>array(), 'type'=>array(), 'name'=>array());
			$array = array();
			for ($i=0; $i<count($fileEle['size']); $i++) {
				$obj = array(
					'name'=>$fileEle['name'][$i],
					'type'=>$fileEle['type'][$i],
					'tmp_name'=>$fileEle['tmp_name'][$i],
					'error'=>$fileEle['error'][$i],
					'size'=>$fileEle['size'][$i]
				);
				$array[] = $uploadCallback(array($field=>$obj));
			}
			foreach ($array as $k=>$obj) {
				$file['file'][$k] = $obj['file'];
				$file['type'][$k] = $obj['type'];
				$file['name'][$k] = $obj['name'];
				if (isset($obj['size'])) $file['size'][$k] = $obj['size'];
				if (isset($obj['width'])) $file['width'][$k] = $obj['width'];
				if (isset($obj['height'])) $file['height'][$k] = $obj['height'];
			}
			$file_array = $file;
		} else {
			$file_array = $uploadCallback($fileObj);
		}
	} else if (isset($_POST[$field])) {
		//如果不是文件即以POST形式获取
		$file = trim($_POST[$field]);
		$file_array['file'] = $file;
	}
	if ($return_detail) {
		return $file_array;
	} else {
		return $file_array['file'];
	}
}

//下载远程文件到本地
function download_file($dir, $url, $origin_name=false, $suffix='') {
	$paths = download_files($dir, array($url), $origin_name, $suffix);
	return count($paths) ? $paths[0] : '';
}
function download_files($dir, $urls, $origin_name=false, $suffix='') {
	set_time_limit(0);
	ini_set('memory_limit', '10240M');
	$dir = str_replace(ROOT_PATH.UPLOAD_PATH.'/', '', $dir);
	$paths = array();
	foreach ($urls as $url) {
		if (!strlen($url)) continue;
		preg_match('/(\.\w+)$/', $url, $matcher);
		if (is_array($matcher) && count($matcher)) $suffix = $matcher[1];
		if (!$origin_name || !(is_array($matcher) && count($matcher))) {
			$filename = generate_sn();
		} else {
			$pathinfo = explode('/', $url);
			$pathinfo = explode('.', $pathinfo[count($pathinfo)-1]);
			$filename = $pathinfo[0];
		}
		if (strlen($suffix) && !preg_match('/^\./', $suffix)) $suffix = '.'.$suffix;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		if (substr($url, 0, 8)=='https://') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		$data = curl_exec($ch);
		curl_close($ch);
		makedir(ROOT_PATH.UPLOAD_PATH.'/'.$dir);
		$filepath = UPLOAD_PATH.'/'.$dir.'/'.$filename.$suffix;
		$res = @fopen(ROOT_PATH.$filepath, 'a');
		@fwrite($res, $data);
		@fclose($res);
		$paths[] = str_replace('/public/', '/', $filepath);
	}
	return $paths;
}

//导出成excel，$return为true即在服务器生成文件，$fields = array('id'=>'ID', 'name'=>'姓名', 'mobile'=>'电话');
function export_excel($rs, $fields, $return=false) {
	$objPHPExcel = new PHPExcel();
	//表格头
	$column = 'A';
	$row_number = 1;
	foreach ($fields as $field=>$name) {
		$cell = "{$column}{$row_number}";
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue("{$cell}", "{$name} ");
		$objSheet = $objPHPExcel->getActiveSheet();
		$objSheet->getColumnDimension($column)->setAutoSize(true);
		$objSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
			->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objSheet->getStyle("{$cell}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
		$objSheet->getStyle("{$cell}")->getAlignment()->setWrapText(false);
		$column++;
	}
	$row_number = 2; //1:based index
	foreach ($rs as $k=>$g) {
		$column = 'A';
		foreach ($fields as $field=>$name) {
			$cell = "{$column}{$row_number}";
			$objSheet = $objPHPExcel->getActiveSheet();
			$objSheet->getColumnDimension($column)->setAutoSize(true);
			if (array_key_exists($field, $g)) {
				$objSheet->setCellValue("{$cell}", "{$g->{$field}} ");
			} else {
				$objSheet->setCellValue("{$cell}", " ");
			}
			$objSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
				->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			$objSheet->getStyle("{$cell}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
			$objSheet->getStyle("{$cell}")->getAlignment()->setWrapText(false);
			$column++;
		}
		$row_number++;
	}
	if ($return) {
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$filepath = '/export/';
		$filename = $filepath.generate_sn().'.xlsx';
		makedir($filepath);
		$objWriter->save(ROOT_PATH.UPLOAD_PATH.$filename);
		return $filename;
	} else {
		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.generate_sn().'.xlsx"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');
		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
}

//导入excel, 对应根目录
function import_excel($file, $sheet=0, $start_row=1, $delete_file=true) {
	set_time_limit(0);
	ini_set('memory_limit', '10240M');
	$file = ROOT_PATH.str_replace(ROOT_PATH, '', $file);
	if (empty($file) || !file_exists($file)) die('EXCEL FILE NOT EXISTS');
	setlocale(LC_ALL, 'zh_CN');
	$objRead = new PHPExcel_Reader_Excel2007(); //建立reader对象
	if (!$objRead->canRead($file)) {
		$objRead = new PHPExcel_Reader_Excel5();
		if (!$objRead->canRead($file)) die('NOT EXCEL FILE');
	}
	require_once(SDK_PATH . '/class/PHPExcel/Classes/PHPExcel/Shared/Date.php');
	$cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
		'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
	$obj = $objRead->load($file); //建立excel对象
	$currSheet = $obj->getSheet($sheet); //获取指定的sheet表
	$columnH = $currSheet->getHighestColumn(); //取得最大的列号
	$columnCnt = array_search($columnH, $cellName);
	$rowCnt = $currSheet->getHighestRow(); //获取总行数
	$data = array();
	for ($row=$start_row; $row<=$rowCnt; $row++) { //读取内容
		$cellValues = array();
		for ($column=0; $column<=$columnCnt; $column++) {
			$cellId = $cellName[$column].$row;
			$cell = $currSheet->getCell($cellId);
			if ($cell->getDataType() == PHPExcel_Cell_DataType::TYPE_NUMERIC) {
				$cellValue = $cell->getValue();
				$cellstyleformat = $cell->getStyle()->getNumberFormat();
				$formatcode = $cellstyleformat->getFormatCode();
				if (preg_match('/^(\[\$[A-Z]*-[0-9A-F]*])*[hmsdy]/i', $formatcode)) {
					$cellValue = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($cellValue));
				} else {
					//$cellValue = PHPExcel_Style_NumberFormat::toFormattedString($cellValue, $formatcode);
					$cellValue = $cell->getFormattedValue();
				}
			} else {
				//$cellValue = $cell->getCalculatedValue(); //获取公式计算的值
				$cellValue = $cell->getFormattedValue();
			}
			if ($cellValue instanceof PHPExcel_RichText) $cellValue = $cellValue->__toString(); //富文本转换字符串
			$cellValues[$cellName[$column]] = $cellValue;
		}
		$notEmptyCell = false;
		foreach ($cellValues as $k=>$g) {
			if (!empty($g) && strlen(trim(strval($g)))) {
				$notEmptyCell = true;
				break;
			}
		}
		if ($notEmptyCell) {
			foreach ($cellValues as $k=>$g) {
				$data[$row][$k] = $g;
				//$data[$row][$k] = iconv('gb2312', 'utf-8//IGNORE', $g);
			}
		}
	}
	if ($delete_file) unlink($file);
	return $data;
}

//导出文件或文件夹为zip
function export_zip($fileOrDir, $base_path='', $filename='') {
	$fileOrDir = ROOT_PATH.str_replace(ROOT_PATH, '', $fileOrDir);
	$add2zip = function($path, &$zip, $base_path='') use(&$add2zip) {
		if (is_dir($path)) {
			$handler = opendir($path);
			while (($file = readdir($handler))!==false) {
				if ($file!='.' && $file!='..') {
					if (is_dir($path.'/'.$file)) {
						$add2zip($path.'/'.$file, $zip, $base_path);
					} else {
						$dir_path = explode('/'.$base_path, $path);
						$zip->addFile($path.'/'.$file, (strlen($dir_path[1])?$dir_path[1].'/':'').$file);
					}
				}
			}
			closedir($handler);
		} else {
			$filename = substr($path, strrpos($path, '/')+1);
			$zip->addFile($path, $filename);
		}
	};
	if (!strlen($filename)) $filename = generate_sn();
	$file = ROOT_PATH.UPLOAD_PATH."/{$filename}.zip";
	$zip = new ZipArchive();
	$res = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
	if ($res === TRUE) {
		$add2zip($fileOrDir, $zip, $base_path);
	}
	$zip->close();
	header('Content-Type: application/zip');
	header('Content-Transfer-Encoding: Binary');
	header('Content-Length: '.filesize($file));
	header('Content-Disposition: attachment; filename="'.basename($file).'"');
	readfile($file);
	unlink($file);
	exit;
}

//循环创建目录,对应根目录
function makedir($destination, $create_html=false) {
	$target_path = str_replace(ROOT_PATH, '', $destination);
	if (is_dir(ROOT_PATH.$target_path)) return true;
	$each_path = explode('/', $target_path);
	$cur_path = ROOT_PATH; //当前循环处理的路径
	$origin_mask = @umask(0);
	foreach ($each_path as $path) {
		if ($path) {
			$cur_path .= '/' . $path;
			if (!is_dir($cur_path)) {
				if (@mkdir($cur_path, 0777)) {
					@chmod($cur_path, 0777);
					if ($create_html) @fclose(@fopen($cur_path . '/index.html', 'w'));
				} else {
					@umask($origin_mask);
					return false;
				}
			}
		}
	}
	@umask($origin_mask);
	return true;
}
function create_folder($destination, $create_html=false) {
	return makedir($destination, $create_html);
}

//复制文件夹,对应根目录
function copy_folder($source, $destination, $station=true) {
	$source_path = str_replace(ROOT_PATH, '', $source);
	$destination_path = str_replace(ROOT_PATH, '', $destination);
	if ($station) {
		$source_path = ROOT_PATH.$source_path;
		$destination_path = ROOT_PATH.$destination_path;
	}
	if (!is_dir($source_path)) return false;
	if (!is_dir($destination_path)) makedir($destination_path);
	$handle = dir($source_path);
	while ($entry=$handle->read()) {
		if ($entry!='.' && $entry!='..') {
			if (stripos($entry, '.DS_Store')!==false || stripos($entry, '.git')!==false || stripos($entry, '.svn')!==false || stripos($entry, '.idea')!==false) continue;
			if (is_dir($source_path.'/'.$entry)) {
				copy_folder($source_path.'/'.$entry, $destination_path.'/'.$entry, $station);
			} else {
				if (!file_exists($destination_path.'/'.$entry)) {
					copy($source_path.'/'.$entry, $destination_path.'/'.$entry);
				}
			}
		}
	}
	return true;
}

//删除文件夹和文件夹下的所有文件
function delete_folder($path, $station=true) {
	$path = str_replace(ROOT_PATH, '', $path);
	if ($station) $path = ROOT_PATH.$path;
	if (!is_dir($path)) return true;
	$handle = dir($path);
	while ($entry=$handle->read()) {
		if ($entry!='.' && $entry!='..') {
			if (is_dir($path.'/'.$entry)) {
				delete_folder($path.'/'.$entry, $station);
			} else {
				unlink($path.'/'.$entry);
			}
		}
	}
	rmdir($path);
	return true;
}

//替换文件夹内所有文件内容,对应根目录
function folder_file_content_replace($path, $callback, $station=true) {
	$path = str_replace(ROOT_PATH, '', $path);
	if ($station) $path = ROOT_PATH.$path;
	if (!is_dir($path)) return false;
	$handle = dir($path);
	while ($entry=$handle->read()) {
		if ($entry!='.' && $entry!='..') {
			if (is_dir($path.'/'.$entry)) {
				folder_file_content_replace($path.'/'.$entry, $callback, $station);
			} else {
				file_content_replace($path.'/'.$entry, $callback, $station);
			}
		}
	}
	return true;
}

//设置文件内容,对应根目录
function file_content_write($file, $content='', $station=true) {
	$file = str_replace(ROOT_PATH, '', $file);
	if ($station) $file = ROOT_PATH.$file;
	$fp = fopen($file, 'w');
	flock($fp, LOCK_EX);
	fwrite($fp, $content);
	flock($fp, LOCK_UN);
	fclose($fp);
}

//替换文件内容,对应根目录
function file_content_replace($file, $callback, $station=true) {
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

//截断过长的字符
function cut_str($string, $length=80, $suffix='') {
	$repeat_pattern = function($pattern, $length) {
		return str_repeat("$pattern{0,65535}", $length / 65535)."$pattern{0,".($length % 65535)."}";
	};
	if (!preg_match("(^(".$repeat_pattern("[\t\r\n -\x{10FFFF}]", $length).")($)?)u", $string, $match)) {
		preg_match("(^(".$repeat_pattern("[\t\r\n -~]", $length).")($)?)", $string, $match);
	}
	return $match[1] . $suffix . (isset($match[2]) ? '' : '<i>...</i>');
}

//是否utf8字符
function is_utf8($str) {
	return preg_match('%^(?:
             [\x09\x0A\x0D\x20-\x7E]                 # ASCII
         | [\xC2-\xDF][\x80-\xBF]                 # non-overlong 2-byte
         |     \xE0[\xA0-\xBF][\x80-\xBF]             # excluding overlongs
         | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}     # straight 3-byte
         |     \xED[\x80-\x9F][\x80-\xBF]             # excluding surrogates
         |     \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
         | [\xF1-\xF3][\x80-\xBF]{3}             # planes 4-15
         |     \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
     )*$%xs', $str);
}

//获取月份中文名称
function get_month_chinese($month) {
	if (!is_numeric($month) || $month<1 || $month>12) $month = 1;
	$arr = array('一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月');
	return $arr[$month-1];
}

//两个日期字符串之间月数
function get_month_range($start, $end) {
	if (!$start || !$end) return array();
	$end = date('Ym', strtotime($end));
	$range = array();
	$i = 0;
	do {
		$m = date('Ym', strtotime("{$start} + {$i} month"));
		$month = date('Y-n', strtotime("{$start} + {$i} month"));
		if (!in_array($month, $range)) $range[] = $month;
		$i++;
	} while ($m < $end);
	return $range;
}

//月份数组排序且过滤重复
function get_month_sort($months) {
	if (!$months || !is_array($months) || !count($months)) return array();
	usort($months, function($a, $b){
		$ma = date('Ym', strtotime($a));
		$mb = date('Ym', strtotime($b));
		if ($ma==$mb) return 0;
		return ($ma<$mb) ? -1 : 1;
	});
	$range = array();
	foreach ($months as $k=>$m) {
		if (!strlen($m)) continue;
		$m = date('Y-n', strtotime($m));
		if (!in_array($m, $range)) $range[] = $m;
	}
	return $range;
}

//两个日期之间的天数
function get_date_diff($date1, $date2) {
	if (strtotime($date1)>strtotime($date2)) list($date1, $date2) = array($date2, $date1);
	$start = strtotime($date1);
	$stop = strtotime($date2);
	$days = ($stop-$start)/86400;
	$result = array('days'=>$days, 'year'=>0, 'month'=>0, 'day'=>0, 'week'=>0);
	if ($days<7) { //如果小于7天直接返回天数
		$result['day'] = $days;
	} else if($days<=31) { //小于28天则返回周数，由于闰年2月满足了
		if ($stop==strtotime($date1.'+1 month')) {
			$result['month'] = 1;
		} else {
			$w = floor($days/7);
			$d = ($stop-strtotime($date1.'+'.$w.' week'))/86400;
			$result['week'] = $w;
			$result['day'] = $d;
		}
	} else {
		$w = 0;
		$y = floor($days/365);
		if ($y>=1) { //如果超过一年
			$start = strtotime($date1.'+'.$y.'year');
			$date1 = date('Y-m-d', $start);
			//判断是否真的已经有了一年了，如果没有的话就开减
			if ($start>$stop) {
				$date1 = date('Y-m-d',strtotime($date1.'-1 month'));
				$m = 11;
				$y--;
			}
			$days = ($stop-strtotime($date1))/86400;
		}
		if (isset($m)) {
			$w = floor($days/7);
			$d = $days-$w*7;
		} else {
			$m = isset($m) ? $m : round($days/30);
			$stop<strtotime($date1.'+'.$m.'month') && $m--;
			if ($stop>=strtotime($date1.'+'.$m.'month')) {
				$d = $w = ($stop-strtotime($date1.'+'.$m.'month'))/86400;
				$w = floor($w/7);
				$d = $d-$w*7;
			}
		}
		$result['year'] = $y;
		$result['month'] = $m;
		$result['week'] = $w;
		$result['day'] = isset($d) ? $d : 0;
	}
	return $result;
}

//将时间转换成刚刚、分钟、小时
function get_time_word($date) {
	if (!is_numeric($date)) $date = strtotime($date);
	$between = time() - $date;
	if ($between < 60) return '刚刚';
	if ($between < 3600) return floor($between/60).'分钟前';
	if ($between < 86400) return floor($between/3600).'小时前';
	if ($between <= 864000) return floor($between/86400).'天前';
	if ($between > 864000) return date('Y-m-d', $date);
	return $between;
}

//倒计时算法
function countdown($seconds) {
	$day = floor($seconds/(24*60*60));
	$hour = floor(($seconds%(24*60*60))/(60*60));
	$minute = floor(($seconds%(60*60))/60);
	$second = floor($seconds%60);
	return compact('day', 'hour', 'minute', 'second');
}

//将从数据库查出的记录集的时间转换成更简短可读的时间格式，如刚刚、n分钟前
function get_time_word_from_rs($obj, $fields=array()) {
	if ($obj && is_array($obj) && $fields) {
		if (!is_array($fields)) $fields = array($fields);
		foreach ($obj as $k=>$g) {
			if (is_array($g)) {
				foreach ($fields as $field) {
					if (isset($g[$field])) $obj[$k][$field] = get_time_word($g[$field]);
				}
			} else {
				foreach ($fields as $field) {
					if (isset($g->{$field})) $obj[$k]->{$field} = get_time_word($g->{$field});
				}
			}
		}
	}
	return $obj;
}

//将从数据库查出的记录集的时间转换成可读的时间格式
function get_date_from_rs($obj, $fields=array(), $format='Y-m-d H:i:s') {
	if ($obj && $fields) {
		if (!is_array($fields)) $fields = array($fields);
		if (is_array($obj)) {
			foreach ($obj as $k => $g) {
				if (is_array($g)) {
					foreach ($fields as $field) {
						if (isset($g[$field])) $obj[$k][$field] = date($format, $g[$field]);
					}
				} else {
					foreach ($fields as $field) {
						if (isset($g->{$field})) $obj[$k]->{$field} = date($format, $g->{$field});
					}
				}
			}
		} else if (is_object($obj)) {
			foreach ($fields as $field) {
				if (isset($obj->{$field})) $obj->{$field} = date($format, $obj->{$field});
			}
		}
	}
	return $obj;
}

//将手机号码中间设为星号
function get_mobile_mark($mobile) {
	return substr_replace($mobile, '****', 3, -4);
}

//生成又拍云缩略图url
function get_upyun_thumb_url($url, $size='') {
	//if ($url && $size) {
	if ($url && $size && !is_my_domain($url)) {
		//产品缩略图
		if (strpos($url, UPLOAD_PATH) !== false) {
			$url = $url . '!' . $size;
		}
	}
	return $url;
}

//是否本站域名
function is_my_domain($url) {
	global $img_domain;
	if (!strlen($img_domain)) $img_domain = https().$_SERVER['HTTP_HOST'];
	if (strpos($url, $img_domain) !== false) {
		return true;
	} else {
		return false;
	}
}

//正则语法替换颜色
function changeColor($str) {
	$str = preg_replace_callback('/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})([^#]+)#/', function($matcher){
		return '<font color="#'.$matcher[1].'">'.$matcher[2].'</font>';
	}, $str);
	$str = preg_replace_callback('/#([RGBOPY])([^#]+)#/', function($matcher){
		$html = '<font color="';
		switch ($matcher[1]) {
			case 'R':$html .= 'red';break;
			case 'G':$html .= 'green';break;
			case 'B':$html .= 'blue';break;
			case 'O':$html .= 'orange';break;
			case 'P':$html .= 'purple';break;
			case 'Y':$html .= '#ffc700';break;
		}
		$html .= '">'.$matcher[2].'</font>';
		return $html;
	}, $str);
	return $str;
}

//坐标的指定公里范围矩形
function locationGeo($lng, $lat, $distance=5) {
	$lng = floatval($lng);
	$lat = floatval($lat);
	$earthhalf = 6371; //地球半径
	$dlng = 2 * asin(sin($distance / (2 * $earthhalf)) / cos(deg2rad($lat)));
	$dlng = rad2deg($dlng);
	$dlat = $distance / $earthhalf;
	$dlat = rad2deg($dlat);
	return array(
		array('lat'=>$lat+$dlat, 'lng'=>$lng-$dlng), //left_top
		array('lat'=>$lat+$dlat, 'lng'=>$lng+$dlng), //right_top
		array('lat'=>$lat-$dlat, 'lng'=>$lng-$dlng), //left_bottom
		array('lat'=>$lat-$dlat, 'lng'=>$lng+$dlng) //right_bottom
	);
}

//检测某坐标是否在指定矩形坐标内
function isPointInGeos($coordArray, $point) {
	$vertx = array();
	$verty = array();
	$maxY = $maxX = 0;
	$minY = $minX = 9999;
	foreach ($coordArray as $item) {
		if($item['lng']>$maxX) $maxX = $item['lng'];
		if($item['lng']<$minX) $minX = $item['lng'];
		if($item['lat']>$maxY) $maxY = $item['lat'];
		if($item['lat']<$minY) $minY = $item['lat'];
		$vertx[] = $item['lng'];
		$verty[] = $item['lat'];
	}
	if ($point['lng']<$minX || $point['lng']>$maxX || $point['lat']<$minY || $point['lat']>$maxY) return false;
	$x = $point['lng'];
	$y = $point['lat'];
	$count = count($coordArray);
	$result = false;
	for ($i=0, $j=$count-1; $i<$count; $j=$i++) {
		if ( (($verty[$i]>$y) != ($verty[$j]>$y)) && ($x<($vertx[$j]-$vertx[$i]) * ($y-$verty[$i]) / ($verty[$j]-$verty[$i]) + $vertx[$i]) ) {
			$result = !$result;
		}
	}
	return $result;
}

//获取ip地址
function ip() {
	if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']!='unknown') {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']!='unknown') {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	if ($ip=='' || $ip=='::1' || $ip=='127.0.0.1') {
		return '127.0.0.1';
	}
	return $ip;
}

//通过第三方获取ip地址
function get_ip($allInfo=false){
	ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)');
	$url = 'http://ip.taobao.com/service/getIpInfo.php?ip='.ip();
	//$url = 'http://ip.taobao.com/service/getIpInfo.php?ip=myip';
	$json = json_decode(file_get_contents($url), true);
	if(intval($json['code'])==1)return $allInfo ? NULL : '127.0.0.1';
	return $allInfo ? $json['data'] : $json['data']['ip'];
	/*
	//免费查询10000次/月
	$url = 'http://api.ipstack.com/check?language=zh&access_key=990add5c9e2d8ad47a20dee7299068a5'; //可获取IP、经纬度
	//$url = 'http://api.ipstack.com/134.201.250.155?language=zh&access_key=990add5c9e2d8ad47a20dee7299068a5'; //指定ip
	$json = json_decode(file_get_contents($url), true);
	return ($allInfo ? $json : $json['ip']);
	*/
}

//获取省市
function city() {
	$data = file_get_contents('http://ip.ws.126.net/ipquery');
	$data = iconv('GB2312', 'UTF-8//IGNORE', $data);
	preg_match('/({[^}]+})/', $data, $matcher);
	$res = $matcher[1];
	$res = preg_replace('/\b(\w+):/', '"$1":', $res);
	return json_decode($res, true);
	//{"city":"广州市", "province":"广东省"}
}

//返回http协议
function https(){
    $is_https = false;
    if (!isset($_SERVER['HTTPS'])) return 'http://';
    if ($_SERVER['HTTPS']===1) { //Apache
        $is_https = true;
    } else if (strtoupper($_SERVER['HTTPS'])==='ON') { //IIS
        $is_https = true;
    } else if ($_SERVER['SERVER_PORT']==443) { //Other
        $is_https = true;
    }
    return $is_https ? 'https://' : 'http://';
}

//当前网址
function domain() {
	//return https().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];
	return https().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

//格式化URL,suffix增加网址后缀, 如七牛?imageMogr2/thumbnail/200x200, 又拍云(需自定义)!logo
function add_domain($url, $origin_third_host='', $replace_third_host='', $suffix='') {
	global $img_domain;
	if (is_string($url) && strlen($url) && !preg_match('/^https?:\/\//', $url)) {
		if (substr($url,0,2) == '//') {
			$url = https().substr($url, 2);
		} else {
			if (stripos($url, '%domain%')!==false && strpos($url, https().$_SERVER['HTTP_HOST'])===false) {
				$url = str_replace('%domain%', https().$_SERVER['HTTP_HOST'], $url);
			} else {
				$url = str_replace('%domain%', '', $url);
				if (substr($url, 0, 1) == '/') {
					$url = (strlen($img_domain) ? $img_domain : https().$_SERVER['HTTP_HOST']).$url;
				} else {
					if (preg_match('/^((http|https|ftp):\/\/)?[\w\-_]+(\.[\w\-_]+)+([\w\-.,@?^=%&:\/~+#]*[\w\-@?^=%&\/~+#])?$/', https().$_SERVER['HTTP_HOST'].'/'.$url)) {
						$url = (strlen($img_domain) ? $img_domain : https().$_SERVER['HTTP_HOST']).'/'.$url;
					} else {
						$url = str_replace('"/uploads/', '"'.https().$_SERVER['HTTP_HOST'].'/uploads/', $url);
					}
				}
			}
		}
	}
	if (is_string($url) && strlen($url) && strpos($url, '/images/')===false && strlen($suffix) && stripos($url, $suffix)===false) $url .= $suffix;
	if (is_string($url)) $url = str_replace('%domain%', '', $url);
	if (strlen($origin_third_host) && strlen($replace_third_host) && preg_match('/^https?:\/\//', $url)) {
		$replace = 'str_replace';
		if (substr($origin_third_host, 0, 1)=='/') $replace = 'preg_replace';
		$url = $replace($origin_third_host, $replace_third_host, $url);
	}
	return $url;
}

//递归一个数组/对象的属性加上域名
function add_domain_deep($obj, $fields=array(), $origin_third_host='', $replace_third_host='') {
	if (is_object($obj) || is_array($obj)) {
		foreach ($obj as $key => $val) {
			if (is_object($val) || is_array($val)) {
				if (is_object($obj)) {
					$obj->{$key} = add_domain_deep($val, $fields, $origin_third_host, $replace_third_host);
				} else if (is_array($obj)) {
					$obj[$key] = add_domain_deep($val, $fields, $origin_third_host, $replace_third_host);
				}
			} else {
				if ((is_array($fields) && in_array($key, $fields)) || (is_bool($fields) && $fields) || (is_string($fields) && $key==$fields)) {
					if (is_object($obj)) {
						$obj->{$key} = add_domain($val, $origin_third_host, $replace_third_host, is_avatar($key));
					} else if (is_array($obj)) {
						$obj[$key] = add_domain($val, $origin_third_host, $replace_third_host, is_avatar($key));
					}
				}
			}
		}
	} else if (!is_null($obj)) {
		$obj = add_domain($obj, $origin_third_host, $replace_third_host);
	}
	return $obj;
}

//递归一个数组/对象的属性替换域名
function replace_domain_deep($obj, $origin_third_host, $replace_third_host) {
	$replace = 'str_replace';
	if (substr($origin_third_host, 0, 1)=='/') $replace = 'preg_replace';
	if (is_object($obj) || is_array($obj)) {
		foreach ($obj as $key => $val) {
			if (is_object($val) || is_array($val)) {
				if (is_object($obj)) {
					$obj->{$key} = replace_domain_deep($val, $origin_third_host, $replace_third_host);
				} else if (is_array($obj)) {
					$obj[$key] = replace_domain_deep($val, $origin_third_host, $replace_third_host);
				}
			} else {
				if (is_object($obj)) {
					if (is_string($val)) $obj->{$key} = $replace($origin_third_host, $replace_third_host, $val);
				} else if (is_array($obj)) {
					if (is_string($val)) $obj[$key] = $replace($origin_third_host, $replace_third_host, $val);
				}
			}
		}
	} else if (is_string($obj)) {
		$obj = $replace($origin_third_host, $replace_third_host, $obj);
	}
	return $obj;
}

//字符串是否包含avatar字样, 且判断是否本地图片(本地需要返回空,否则按照又拍云规则增加后缀)
function is_avatar($key) {
	global $img_domain;
	if (strlen($key)) {
		if (strpos($key, 'avatar')!==false) {
			if (!strlen($img_domain)) $img_domain = https().$_SERVER['HTTP_HOST'];
			if ( !(strpos($key, $img_domain)!==false || (strpos($key, 'http://')===false && strpos($key, 'https://')===false)) ) {
				return '!logo';
			}
		}
	}
	return '';
}

//格式化路径
function add_root($path) {
	if (strlen($path) && strpos($path, ROOT_PATH)===false && strpos($path, 'http://')===false && strpos($path, 'https://')===false) {
		if (stripos($path, '%root%')!==false) {
			$path = str_replace('%root%', ROOT_PATH, $path);
		} else {
			if (substr($path,0,1) == '/') {
				$path = ROOT_PATH.$path;
			} else {
				$path = ROOT_PATH.'/'.$path;
			}
		}
	}
	$path = str_replace('%root%', '', $path);
	return $path;
}

//递归一个数组/对象的属性加上路径
function add_root_deep($obj, $fields=array()) {
	if (is_object($obj) || is_array($obj)) {
		foreach ($obj as $key => $val) {
			if (is_object($val) || is_array($val)) {
				if (is_object($obj)) {
					$obj->{$key} = add_root_deep($val, $fields);
				} else if (is_array($obj)) {
					$obj[$key] = add_root_deep($val, $fields);
				}
			} else {
				if (in_array($key, $fields)) {
					if (is_object($obj)) {
						$obj->{$key} = add_root($val);
					} else if (is_array($obj)) {
						$obj[$key] = add_root($val);
					}
				}
			}
		}
	} else {
		$obj = add_root($obj);
	}
	return $obj;
}

//深度转义斜杠
function addslashes_deep_obj($obj) {
	if (is_object($obj)) {
		foreach ($obj as $key => $val) {
			if (($val) == true) {
				$obj->{$key} = addslashes_deep_obj($val);
			} else {
				$obj->{$key} = addslashes_deep($val);
			}
		}
	} else {
		$obj = addslashes_deep($obj);
	}
	return $obj;
}

//转义斜杠
function addslashes_deep($value) {
	if (empty($value)) {
		return $value;
	} else {
		return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
	}
}

//解码经escape编码的字符串
function unescape($str, $charset='utf8') {
	if ($str=='') return '';
	if ($charset=='utf8') {
		$ret = '';
		$len = strlen($str);
		for ($i=0; $i<$len; $i++) {
			if ($str[$i]=='%' && $str[$i+1]=='u') {
				$val = hexdec(substr($str, $i+2, 4));
				if ($val<0x7f) $ret .= chr($val);
				else if ($val<0x800) $ret .= chr(0xc0|($val>>6)).chr(0x80|($val&0x3f));
				else $ret .= chr(0xe0|($val>>12)).chr(0x80|(($val>>6)&0x3f)).chr(0x80|($val&0x3f));
				$i += 5;
			} else if ($str[$i]=='%') {
				$ret .= urldecode(substr($str, $i, 3));
				$i += 2;
			} else $ret .= $str[$i];
		}
		return $ret;
	} else {
		$str = rawurldecode($str);
		preg_match_all("/%u.{4}|&#x.{4};|&#d+;|.+/U",$str,$r);
		$ar = $r[0];
		foreach ($ar as $k=>$v) {
			if (function_exists('mb_convert_encoding')) {
				if (substr($v,0,2)=='%u') $ar[$k] = mb_convert_encoding(pack('H4',substr($v,-4)), 'GB2312', 'UCS-2');
				else if (substr($v,0,3)=='&#x') $ar[$k] = mb_convert_encoding(pack('H4',substr($v,3,-1)), 'GB2312', 'UCS-2');
				else if (substr($v,0,2)=='&#') $ar[$k] = mb_convert_encoding(pack('H4',substr($v,2,-1)), 'GB2312', 'UCS-2');
			} else {
				if (substr($v,0,2)=='%u') $ar[$k] = iconv('UCS-2', 'GB2312', pack('H4',substr($v,-4)));
				else if (substr($v,0,3)=='&#x') $ar[$k] = iconv('UCS-2', 'GB2312', pack('H4',substr($v,3,-1)));
				else if (substr($v,0,2)=='&#') $ar[$k] = iconv('UCS-2', 'GB2312', pack('H4',substr($v,2,-1)));
			}
		}
		return implode('', $ar);
	}
}

//输出xml格式
function xml_encode($data) {
	$xml = new SimpleXMLElement('<?xml version="1.0"?><rest></rest>');
	foreach ($data as $key => $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$xml->addChild($k, $v);
			}
		} else {
			$xml->addChild($key, $value);
		}
	}
	return $xml->asXML();
}

//解析xml数据
function xml_decode($xml, $assoc=false) {
	$res = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
	if ($assoc) $res = json_decode(json_encode($res), true);
	return $res;
}

//将对象/数组进行urlencode
function url_encode($obj) {
	$o = deep_clone($obj);
	if (is_array($o)) {
		foreach ($o as $key=>$value) $o[urlencode($key)] = url_encode($value);
	} else if (is_object($o)) {
		foreach ($o as $name=>$value) $o->{$name} = url_encode($o->{$name});
	} else if (is_string($o)) {
		$o = urlencode($o);
	}
	return $o;
}

//解决序列化字段中有中文导致出错
function unserialize_mb($str) {
	$str = preg_replace_callback('#s:(\d+):"(.*?)";#s', function($match) {
		return 's:'.strlen($match[2]).':"'.$match[2].'";';
	}, $str);
	return unserialize($str);
}

//深度复制(防止PHP直接赋值到新变量后仍然指向同一内存指针)
function deep_clone($obj) {
	if (is_array($obj)) {
		$o = array();
		foreach ($obj as $k=>$g) $o[$k] = unserialize_mb(serialize($g));
	} else if (is_object($obj)) {
		$o = new stdClass();
		foreach ($obj as $name=>$g) $o->{$name} = unserialize_mb(serialize($obj->{$name}));
	} else {
		$o = unserialize_mb(serialize($obj));
	}
	return $o;
}

//判断字符串包含中英文, 返回0全英文或数字或下划线, 1全中文, 2中英混合
function is_en_cn($str) {
	$allen = preg_match('/^\w+$/', $str); //判断是否是英文或数字或下划线
	$allcn = preg_match('/^[\x7f-\xff]+$/', $str); //判断是否是中文
	if ($allen) {
		return 0;
	} else if ($allcn) {
		return 1;
	} else {
		return 2;
	}
}

//判断移动端浏览器打开
function is_mobile_web() {
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$keywords = array(
			'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'blackberry',
			'meizu', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile',
			'smartphone', 'windows ce', 'windows phone', 'ipod', 'iphone', 'ipad', 'android'
		);
		if (preg_match('/('.implode('|', $keywords).')/i', strtolower($_SERVER['HTTP_USER_AGENT']))) return true;
	}
	return false;
}

//验证手机号
function is_mobile($mobile) {
	return preg_match('/^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[03678]{1}\d{8}$|^18[\d]{9}$/', $mobile) ? true : false;
}

//验证座机
function is_tel($tel) {
	return preg_match('/^((\d{3,4}-)?\d{8}(-\d+)?|(\(\d{3,4}\))?\d{8}(-\d+)?)$/', $tel) ? true : false;
}

//验证电话号码(包括手机号与座机)
function is_phone($phone) {
	$result = is_mobile($phone);
	if (!$result) $result = is_tel($phone);
	return $result;
}

//验证邮箱
function is_email($email) {
	return preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $email) ? true : false;
}

//验证日期
function is_date($date) {
	return preg_match('/^(?:(?!0000)[0-9]{4}[\/-](?:(?:0?[1-9]|1[0-2])[\/-](?:0?[1-9]|1[0-9]|2[0-8])|(?:0?[13-9]|1[0-2])[\/-](?:29|30)|(?:0?[13578]|1[02])[\/-]31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)[\/-]0?2[\/-]29)$/', $date) ? true : false;
}

//严格验证身份证
function is_idcard($idcard) {
	$idcard_verify_number = function($idcard_base) {
		//计算身份证校验码，根据国家标准GB 11643-1999
		if (strlen($idcard_base)!=17) return false;
		$factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); //加权因子
		$verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'); //校验码对应值
		$checksum = 0;
		for ($i=0; $i<strlen($idcard_base); $i++) {
			$checksum += substr($idcard_base,$i,1) * $factor[$i];
		}
		$mod = $checksum % 11;
		$verify_number = $verify_number_list[$mod];
		return $verify_number;
	};
	$idcard_15to18 = function($idcard) use($idcard_verify_number) {
		//将15位身份证升级到18位
		if (strlen($idcard)!=15) return false;
		//如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
		if (array_search(substr($idcard,12,3), array('996','997','998','999')) !== false) {
			$idcard = substr($idcard,0,6).'18'.substr($idcard,6,9);
		} else {
			$idcard = substr($idcard,0,6).'19'.substr($idcard,6,9);
		}
		$idcard = $idcard.$idcard_verify_number($idcard);
		return $idcard;
	};
	$idcard_checksum18 = function($idcard) use($idcard_verify_number) {
		//18位身份证校验码有效性检查
		if (strlen($idcard)!=18) return false;
		$idcard_base = substr($idcard,0,17);
		if ($idcard_verify_number($idcard_base) != strtoupper(substr($idcard,17,1))) {
			return false;
		} else {
			return true;
		}
	};
	if (strlen($idcard)==18) {
		return $idcard_checksum18($idcard);
	} else if (strlen($idcard)==15) {
		$idcard = $idcard_15to18($idcard);
		return $idcard_checksum18($idcard);
	} else {
		return false;
	}
}

//是否图片文件
function is_image($path) {
	if (!strlen($path)) return false;
	$path = add_root($path);
	if (strpos($path, 'http://')===false && strpos($path, 'https://')===false && !file_exists($path)) return false;
	$fs = fopen($path, 'rb');
	$byte = fread($fs, 2);
	fclose($fs);
	$info = @unpack('C2chars', $byte);
	$code = intval($info['chars1'].$info['chars2']);
	switch ($code) {
		case 255216: //jpg
		case 7173: //gif
		case 6677: //bmp
		case 13780:$is_image = true;break; //png
		default:$is_image = false;
	}
	return $is_image;
}

//判断是否PJAX请求
function is_pjax() {
	return array_key_exists('HTTP_X_PJAX', $_SERVER) && $_SERVER['HTTP_X_PJAX'];
}

//网址文本替换为链接标签
function text2link($content){
	if (!strlen($content)) return '';
	//提取替换出所有A标签(统一标记<{link}>)
	preg_match_all('/<a.+?href="[^"]+".*?>.*?<\/a>/i', $content, $linkList);
	$linkList = $linkList[0];
	$str = preg_replace('/<a.+?href="[^"]+".*?>.*?<\/a>/i', '<{link}>', $content);
	//提取替换出所有的IMG标签(统一标记<{img}>)
	preg_match_all('/<img[^>]+>/im', $content, $imgList);
	$imgList = $imgList[0];
	$str = preg_replace('/<img[^>]+>/im', '<{img}>', $str);
	//提取替换出所有的邮箱字符(统一标记<{email}>)
	preg_match_all('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/i', $content, $emailList);
	$emailList = $emailList[0];
	$str = preg_replace('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/i', '<{email}>', $str);
	//提取替换标准的URL地址
	$str = preg_replace('/((http|https|ftp):\/\/)[\w\-_]+(\.[\w\-_]+)+([\w\-.,@?^=%&:\/~+#]*[\w\-@?^=%&\/~+#])?/', '<a href="\0" target="_blank">\0</a>', $str);
	//还原A统一标记为原来的A标签
	for ($i=0; $i<count($linkList); $i++){
		$str = preg_replace('/<{link}>/', $linkList[$i], $str, 1);
	}
	//还原IMG统一标记为原来的IMG标签
	for ($i=0; $i<count($imgList); $i++) {
		$str = preg_replace('/<{img}>/', $imgList[$i], $str, 1);
	}
	//还原邮箱统一标记为原来的邮箱字符
	for ($i=0; $i<count($emailList); $i++) {
		$str = preg_replace('/<{email}>/', $emailList[$i], $str, 1);
	}
	return $str;
}

//生成随机字母、数字
function random_str($length=4, $factor=array()) {
	//生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
	if (count($factor)) {
		$arr = $factor;
	} else {
		$arr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
	}
	//$string = str_shuffle(implode('', $arr)); //随机打乱字符串
	$str = '';
	$arr_len = count($arr);
	for ($i=0; $i<$length; $i++) {
		$rand = mt_rand(0, $arr_len-1);
		$str .= $arr[$rand];
	}
	return $str;
}

//随机范围内整数
function random_num($min=0, $max=PHP_INT_MAX) {
	return mt_rand($min, $max);
}

//随机范围内小数
function random_float($min=0, $max=1, $length=2) {
	$num =  $min + mt_rand()/mt_getrandmax() * ($max-$min);
	return substr(strval($num), 0, $length+2);
}

//随机指定数量的范围内不重复数字, 包含小数, restrict:强制包含小数
function random_norepeat_num($count, $min=0, $max=PHP_INT_MAX, $is_sort=false, $min_float=0, $max_float=0, $length_float=2, $restrict=false) {
	$result = array();
	while (count($result)<$count) {
		$num = mt_rand($min, $max);
		if ($min_float != $max_float) {
			if ($restrict) {
				$num = bcadd($num, random_float($min_float, $max_float, $length_float), $length_float);
			} else {
				if (in_array($num, $result)) $num = bcadd($num, random_float($min_float, $max_float, $length_float), $length_float);
			}
		}
		$result[] = strval($num);
		//数组去重
		//$result = array_unique($result); //效率较低
		$result = array_flip($result);
		$result = array_flip($result);
	}
	if ($is_sort) sort($result);
	return $result;
}

//随机拆分数字, balance各份差值
function random_split_num($number, $count, $balance=10) {
	$average = floatval(bcdiv($number, $count, 2));
	$sum = 0;
	$result = array();
	for ($i=1; $i<$count; $i++) {
		//根据已产生的随机数情况，调整新随机数范围，以保证各份间差值在指定范围内
		if ($sum>0) {
			$max = 0;
			$min = 0 - floatval(bcdiv($balance, 2, 2));
		} else if ($sum<0) {
			$min = 0;
			$max = floatval(bcdiv($balance, 2, 2));
		} else {
			$max = floatval(bcdiv($balance, 2, 2));
			$min = 0 - floatval(bcdiv($balance, 2, 2));
		}
		//产生各份的份额
		$random = rand($min, $max);
		$sum = floatval(bcadd($sum, $random, 2));
		$result[] = floatval(bcadd($average, $random, 2));
	}
	$result[] = floatval(bcsub($average, $sum, 2)); //最后一份的份额由前面的结果决定，以保证各份的总和为指定值
	return $result;
}

//某时间与现在之间剩余的时间
function remain_time($time, $is_day=false) {
	$result = $time - time();
	$r = $result;
	if ($result<=0) return '00:00:00';
	$day = 0;
	if ($is_day) {
		$day = floor($r/(60*60*24));
		$r = $result - $day*60*60*24;
	}
	$hour = floor($r/(60*60));
	$r -= $hour*60*60;
	$minute = floor($r/60);
	$r -= $minute*60;
	$second = $r;
	return ($is_day?sprintf('%02d', $day).'天':'').sprintf('%02d', $hour).':'.sprintf('%02d', $minute).':'.sprintf('%02d', $second);
}

//写log文件
function write_log($content='', $file='', $trace=false, $echo=false) {
	global $client_id;
	if (!strlen($file)) $file = '/temp/log.txt';
	if (defined('IS_SAAS') && IS_SAAS && defined('IS_AG') && defined('IS_OP') && !IS_AG && !IS_OP && isset($client_id) && intval($client_id)>0) $file = '/temp/file/'.$client_id.'/log.txt';
	$filename = ROOT_PATH.str_replace(ROOT_PATH, '', $file);
	$traceStr = '';
	if ($trace) {
		$e = new Exception;
		$trace = $e->getTraceAsString();
		$traceStr = "\n\n".$trace;
	}
	if (is_array($content) || is_object($content)) $content = json_encode($content, JSON_UNESCAPED_UNICODE);
	file_put_contents($filename, date('Y-m-d H:i:s').PHP_EOL.$content.$traceStr.PHP_EOL.'=============================='.PHP_EOL.PHP_EOL, FILE_APPEND);
	if ($echo) echo $content.'<br />';
}
//写error文件
function write_error($content='', $file='', $trace=false, $echo=false) {
	write_log($content, strlen($file) ? $file : (defined('ERROR_FILE')?ERROR_FILE:'/temp/error.txt'), $trace, $echo);
}

//批量unset, fields可为数组或空格分割的字符串
function unsets(&$obj, $fields) {
	if (is_string($fields)) $fields = explode(' ', $fields);
	if (is_array($obj)) {
		if (array_values($obj) === $obj) { //数字索引数组
			for ($i=0; $i<count($fields); $i++) {
				foreach ($obj as $j=>$o) {
					if ($fields[$i] === $o) {
						unset($obj[$j]);
						break;
					}
				}
			}
			$obj = array_values($obj);
		} else {
			foreach ($fields as $field) {
				if (isset($obj[$field])) unset($obj[$field]);
				else {
					foreach ($obj as $k=>$g) unsets($obj[$k], $fields);
				}
			}
		}
	} else if (is_object($obj)) {
		foreach ($fields as $field) {
			if (isset($obj->{$field})) unset($obj->{$field});
			else {
				foreach ($obj as $k=>$g) unsets($g, $fields);
			}
		}
	} else {
		unset($obj);
	}
}

//跳转网址后终止
function location($url) {
	if (substr(strtolower(trim($url)), 0, 9)=='location:') $url = substr(trim($url), 9);
	header("Location:{$url}");
	exit;
}

//原样输出
function debug($obj) {
	if (IS_API) {
		echo json_encode($obj);
	} else {
		echo '<pre>';
		echo print_r($obj, true);
		echo '</pre>';
	}
	exit;
}

//格式化价格
function price_format($price) {
	if (is_numeric($price)) {
		$price = number_format($price, 2);
	}
	return $price;
}

//图片转base64
function image_base64($file) {
	if (stripos($file, 'http')===false) $file = ROOT_PATH.str_replace(ROOT_PATH, '', $file);
	$info = getimagesize($file);
	$data = file_get_contents($file);
	return 'data:'.$info['mime'].';base64,'.base64_encode($data);
}

//图片压缩, percent缩放百分比, saveName保存图片名(没有后缀就用源图后缀)用于保存, 为空即直接显示
function image_compress($file, $percent=1, $saveName='') {
	if (preg_match('/^https?:\/\//', $file)) exit('ONLY COMPRESS LOCAL IMAGES');
	$file = ROOT_PATH.str_replace(ROOT_PATH, '', $file);
	if (!file_exists($file)) exit('IMAGE NOT EXIST');
	$fs = fopen($file, 'rb');
	$byte = fread($fs, 2);
	fclose($fs);
	$info = @unpack('C2chars', $byte);
	$code = intval($info['chars1'].$info['chars2']);
	if (!in_array($code, array(255216, 7173, 6677, 13780))) exit('FILE IS NOT A IMAGE');
	$imageInfo = getimagesize($file);
	$imageInfo = ['width'=>$imageInfo[0], 'height'=>$imageInfo[1], 'type'=>image_type_to_extension($imageInfo[2], false)];
	$fun = 'imagecreatefrom'.$imageInfo['type'];
	$image = $fun($file);
	$new_width = $imageInfo['width'] * $percent;
	$new_height = $imageInfo['height'] * $percent;
	$image_tmp = imagecreatetruecolor($new_width, $new_height);
	imagecopyresampled($image_tmp, $image, 0, 0, 0, 0, $new_width, $new_height, $imageInfo['width'], $imageInfo['height']);
	imagedestroy($image);
	$image = $image_tmp;
	if (strval($saveName)) $saveName = ROOT_PATH.str_replace(ROOT_PATH, '', $saveName);
	if (strval($saveName)) {
		if (file_exists($saveName)) unlink($saveName);
		$allowImg = ['.jpg', '.jpeg', '.png', '.bmp', '.gif', '.wbmp'];
		$dstExt = strrchr($saveName, '.');
		$sourceExt = strrchr($file, '.');
		if (strval($dstExt)) $dstExt = strtolower($dstExt);
		if (strval($sourceExt)) $sourceExt = strtolower($sourceExt);
		if (strval($dstExt) && in_array($dstExt, $allowImg)) {
			$dstName = $saveName;
		} else if (strval($sourceExt) && in_array($sourceExt, $allowImg)) {
			$dstName = $saveName.$sourceExt;
		} else {
			$dstName = $saveName.$imageInfo['type'];
		}
		$func = 'image'.$imageInfo['type'];
		$quality = 75;
		if ($func=='imagejpeg') {
			$func($image, $dstName, round($quality));
		} else if ($func=='imagepng') {
			$func($image, $dstName, round(9*$quality/100));
		} else {
			$func($image, $dstName);
		}
		imagedestroy($image);
	} else {
		header('Content-Type: image/'.$imageInfo['type']);
		$func = 'image'.$imageInfo['type'];
		$func($image);
		imagedestroy($image);
		exit;
	}
}

//解析二维码
function qrcode_decode($url, $type='cli') {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	switch ($type) {
		case 'zxing':
			$target_url = 'https://zxing.org/w/decode?u='.urlencode($url);
			curl_setopt($ch, CURLOPT_URL, $target_url);
			$html = curl_exec($ch);
			curl_close($ch);
			if (strpos($html, '<table id="result">')===false) error('无法扫描出该图片的内容');
			preg_match("/<table id=\"result\">(.*)<\/table>/isU", $html, $mather);
			if (!is_array($mather)) error('无法扫描出该图片的内容');
			preg_match("/<pre>(.*)<\/pre>/isU", $mather[1], $arr);
			return $arr[1];
			break;
		default:
			$target_url = 'https://cli.im/apis/up/deqrimg';
			curl_setopt($ch, CURLOPT_URL, $target_url);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-requested-with: XMLHttpRequest'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, "img=".urlencode($url));
			$html = curl_exec($ch);
			curl_close($ch);
			$data = json_decode($html, true);
			if (is_null($data)) error('无法扫描出该图片的内容');
			if (intval($data['status'])==0) error($data['info']);
			return $data['info']['data'][0];
			break;
	}
}

//合并图片
/*$poster = merge_image(array(
	'bg'=>'/images/poster.jpg',
	'images'=>array(
		array('image'=>$avatar, 'left'=>30, 'top'=>568, 'width'=>68, 'height'=>68),
		array('image'=>$qrcode, 'left'=>184, 'top'=>780, 'width'=>270, 'height'=>270)
	)
), false);*/
function merge_image($options=array(), $show=true){
	$bg = $options['bg']; //背景
	if (!strlen($bg)) return NULL;
	$default = array('left'=>0, 'top'=>0, 'width'=>100, 'height'=>100, 'opacity'=>100);
	if (strpos($bg, 'http')===false) $bg = ROOT_PATH.$bg;
	if (class_exists('Imagick')) {
		$im = new Imagick($bg);
		$bgWidth = $im->getImageWidth();
		$bgHeight = $im->getImageHeight();
		if (isset($options['images']) && is_array($options['images'])) {
			foreach ($options['images'] as $val) {
				$g = array_merge($default, $val);
				$g['left'] = $g['left']<0 ? $bgWidth-abs($g['left'])-$g['width'] : $g['left'];
				$g['top'] = $g['top']<0 ? $bgHeight-abs($g['top'])-$g['height'] : $g['top'];
				$imagePath = $g['image'];
				if (!strlen($imagePath)) continue;
				if (strpos($imagePath, 'http') === false && (!isset($g['stream']) || !$g['stream'])) $imagePath = ROOT_PATH . $imagePath;
				$image = new Imagick($imagePath);
				$image->thumbnailImage($g['width'], $g['height']);
				$im->setImageCompressionQuality(100);
				$im->compositeImage($image, Imagick::COMPOSITE_OVER, $g['left'], $g['top']);
				$image->destroy();
			}
		}
		$dir = UPLOAD_PATH.'/'.date('Y').'/'.date('m').'/'.date('d');
		$upload_dir = ROOT_PATH;
		if (!is_dir($upload_dir.$dir)) makedir($upload_dir.$dir);
		$ext = 'png';
		switch ($im->getImageMimeType()) {
			case 'image/jpeg':$ext = 'jpg';break;
			case 'image/gif':$ext = 'gif';break;
			case 'image/png':$ext = 'png';break;
		}
		$filename = $dir.'/'.generate_sn().'.'.$ext;
		if (!$show) {
			$im->writeImage($upload_dir . $filename);
		} else {
			header('Content-Type: image/png');
			echo $im;
		}
		$im->destroy();
		return $filename;
	} else {
		$bgInfo = getimagesize($bg);
		$bgFn = 'imagecreatefrom'.image_type_to_extension($bgInfo[2], false);
		$bg = $bgFn($bg);
		$bgWidth = imagesx($bg);
		$bgHeight = imagesy($bg);
		$res = imagecreatetruecolor($bgWidth, $bgHeight);
		$color = imagecolorallocate($res, 1000, 1000, 1000);//此处3个1000可以使背景设为白色，3个255可以使背景变成透明色
		imagefill($res, 0, 0, $color);
		imagecopyresampled($res, $bg, 0, 0, 0, 0, $bgWidth, $bgHeight, $bgWidth, $bgHeight);
		imagedestroy($bg);
		//叠加图片
		if (isset($options['images']) && is_array($options['images'])) {
			foreach ($options['images'] as $val) {
				$g = array_merge($default, $val);
				$imagePath = $g['image'];
				if (!strlen($imagePath)) continue;
				if (strpos($imagePath, 'http')===false && (!isset($g['stream']) || !$g['stream'])) $imagePath = ROOT_PATH . $imagePath;
				if (isset($g['stream']) && $g['stream']) { //如果传的是字符串图像流,例如file_get_contents获取的
					$info = getimagesizefromstring($imagePath);
					$fn = 'imagecreatefromstring';
				} else {
					$info = getimagesize($imagePath);
					$fn = 'imagecreatefrom'.image_type_to_extension($info[2], false);
				}
				$imageRes = $fn($imagePath);
				$width = $info[0];
				$height = $info[1];
				//建立画板, 缩放图片至指定尺寸
				$canvas = imagecreatetruecolor($g['width'], $g['height']);
				//关键函数, 参数(目标资源, 源, 目标资源的开始坐标x,y, 源资源的开始坐标x,y, 目标资源的宽高w,h, 源资源的宽高w,h)
				imagecopyresampled($canvas, $imageRes, 0, 0, 0, 0, $g['width'], $g['height'], $width, $height);
				$g['left'] = $g['left']<0 ? $bgWidth-abs($g['left'])-$g['width'] : $g['left'];
				$g['top'] = $g['top']<0 ? $bgHeight-abs($g['top'])-$g['height'] : $g['top'];
				imagedestroy($imageRes);
				//合并图像
				imagecopymerge($res, $canvas, $g['left'], $g['top'], 0, 0, $g['width'], $g['height'], $g['opacity']);
				imagedestroy($canvas);
			}
		}
		//生成图片
		$fn = 'image'.image_type_to_extension($bgInfo[2], false);
		if (!$show) {
			$dir = UPLOAD_PATH.'/'.date('Y').'/'.date('m').'/'.date('d');
			$upload_dir = ROOT_PATH.$dir;
			if (!is_dir($upload_dir)) makedir($upload_dir);
			$ext = 'png';
			switch (intval($bgInfo[2])) {
				case 2:$ext = 'jpg';break;
				case 1:$ext = 'gif';break;
				case 6:$ext = 'bmp';break;
				case 3:$ext = 'png';break;
			}
			$filename = $dir.'/'.generate_sn().'.'.$ext;
			$result = $fn($res, ROOT_PATH.$filename);
			imagedestroy($res);
			if (!$result) return '';
			return $filename;
		} else {
			header('content-type: image/png');
			$fn($res);
			imagedestroy($res);
		}
	}
	return '';
}

//输出script
function script($msg='', $url='', $js='') {
	$html = '<meta charset="UTF-8"><script>';
	if ($msg) $html .= "alert('{$msg}');";
	if ($url) $html .= "location.href = '{$url}';";
	if ($js) $html .= $js;
	$html .= '</script>';
	exit($html);
}
function historyBack($msg='') {
	script($msg, '', 'history.back()');
}

//rewrite转换
function rewrite_change($qs='') {
	$qs = trim($qs, '?');
	if (strlen($qs)) {
		$GET = array();
		$rule = explode('&', $qs);
		for ($i=0; $i<count($rule); $i++) {
			$r = $rule[$i];
			if (!strlen($r) || strpos($r, '=')===false) continue;
			$key = substr($r, 0, strpos($r, '='));
			$value = substr($r, strpos($r, '=')+1);
			if (strlen($value)) {
				if ($key == '_param') {
					$rl = explode('/', trim($value, '/'));
					for ($j=0; $j<count($rl); $j+=2) {
						if ($j+1>=count($rl) || !isset($rl[$j+1])) break;
						$GET[$rl[$j]] = $rl[$j+1];
					}
				} else {
					$GET[$key] = $value;
				}
			}
		}
		if (count($GET)) $_GET = $GET;
	} else {
		$uri = $_SERVER['REQUEST_URI'];
		if (strpos($uri, '.php')!==false) return $_GET;
		if (strpos($uri, '?')!==false) { //api/goods/detail?id=348
			$u = substr($uri, strpos($uri, '?')+1);
			$u = urldecode($u);
			if (strlen($u)) {
				$rule = explode('&', $u);
				for ($i=0; $i<count($rule); $i++) {
					$r = $rule[$i];
					if (!strlen($r) || strpos($r, '=')===false) continue;
					$key = substr($r, 0, strpos($r, '='));
					$value = substr($r, strpos($r, '=')+1);
					if (strlen($value)) {
						$_GET[$key] = $value;
					}
				}
			}
		} else { //api/goods/detail/id/348
			$qs = explode('&', $_SERVER['QUERY_STRING']);
			if (substr(end($qs), 0, 1)=='/') {
				array_pop($_GET);
				$rule = explode('/', trim(end($qs), '/'));
				for ($i=0; $i<count($rule); $i+=2) {
					if ($i+1>=count($rule) || !isset($rule[$i+1])) break;
					$_GET[$rule[$i]] = $rule[$i+1];
				}
			}
		}
		if (isset($_GET['app'])) $_GET['app'] = str_replace('.', '_', $_GET['app']);
		if (isset($_GET['act'])) $_GET['act'] = str_replace('.', '_', $_GET['act']);
	}
	$_REQUEST = array_merge($_GET, $_POST);
	return $_GET;
}

//加密网址参数
//$crypt_key = 'MARIO_@AES_@20200604';
//echo encrypt_param('id=1&page=5&timeout='.time(), $crypt_key);
//http://localhost/11.php?AmwCZV5tUDANLFImUDMJPQRhBDQCYQAiUyFXagU7UDEFbVchXiQDbwA2VmdSYgNiVWQFOQRhCzpTbAdi
//getParams($_SERVER['QUERY_STRING'], $crypt_key);
//echo json_encode($_GET);
function encrypt_param($str, $key, $secret='', $secretIndex=10) {
	$encrypt_key = md5(mt_rand(0, 100));
	$ctr = 0;
	$tmp = '';
	for ($i=0; $i<strlen($str); $i++) {
		if ($ctr==strlen($encrypt_key)) $ctr = 0;
		$tmp .= substr($encrypt_key, $ctr, 1) . (substr($str, $i, 1) ^ substr($encrypt_key, $ctr, 1));
		$ctr++;
	}
	$encrypt_key = md5($key);
	$ctr = 0;
	$result = '';
	for ($i=0; $i<strlen($tmp); $i++) {
		if ($ctr==strlen($encrypt_key)) $ctr = 0;
		$result .= substr($tmp, $i, 1) ^ substr($encrypt_key, $ctr, 1);
		$ctr++;
	}
	$result = base64_encode($result);
	$result = str_replace('/', '|', $result);
	$result = rawurlencode($result);
	if (strlen($secret)) {
		$pages = str_split(strtoupper($secret));
		$secretCount = count($pages);
		$index = $secretIndex;
		$page = substr($result, 0, $index);
		for ($i=0; $i<count($pages); $i++) {
			if ($i<count($pages)-1) {
				$page .= $pages[$i] . substr($result, $index*($i+1), $index);
			} else {
				$page .= $pages[$i] . substr($result, $index*($i+1));
			}
		}
		$result = "{$page}_{$secretCount}";
	}
	return $result;
}
//解密网址参数
function decrypt_param($str, $key, $secretCount=0, $secretIndex=10) {
	$secret = '';
	if ($secretCount>0) {
		$index = $secretIndex;
		$page = '';
		for ($i=0; $i<$secretCount; $i++) {
			$page .= substr($str, 0, $index);
			$secret .= substr($str, $index, 1);
			$str = substr($str, $index+1);
			if ($i==$secretCount-1) $page .= $str;
		}
		$str = $page;
		$secret = strtolower($secret);
	}
	$decode = rawurldecode($str);
	$decode = str_replace('|', '/', $decode);
	$decode = base64_decode($decode);
	$encrypt_key = md5($key);
	$ctr = 0;
	$tmp = '';
	for ($i=0; $i<strlen($decode); $i++) {
		if ($ctr==strlen($encrypt_key)) $ctr = 0;
		$tmp .= substr($decode, $i, 1) ^ substr($encrypt_key, $ctr, 1);
		$ctr++;
	}
	$result = '';
	for ($i=0; $i<strlen($tmp); $i++) {
		$md5 = substr($tmp, $i, 1);
		$i++;
		$result .= substr($tmp, $i, 1) ^ $md5;
	}
	return array('result'=>$result, 'secret'=>$secret);
}

//签名, $sign参数为提供商验证用
//1.参数按key=value格式自然排序后转http_build_query(注：网址类型需编码)
//2.把结果MD5后从第6位开始截取18位，拼接上appSecret
//3.把结果MD5后从第10位开始截取16位，得到最终签名
function sign($appId, $appSecret, $timestamp, $data=array(), $sign='') {
	$param = array('appid'=>$appId, 'timestamp'=>$timestamp);
	$param = array_merge($param, $data);
	ksort($param);
	$res = substr(md5(substr(md5(http_build_query($param)), 6, 18) . $appSecret), 10, 16);
	if (strlen($sign)) return $res === $sign;
	return $res;
}

//获取表单数据 by @jsong 2016-6-26
//兼容普通提交、RSA提交
function getData() {
	global $tbp;
	if (defined('RSA_POST') && RSA_POST && defined('IS_POST') && IS_POST) {
		$data = file_get_contents('php://input');
		if (strlen($data)) {
			$_POST = array();
			if (strlen($data)>=684 && strpos($data, '&')===false && substr($data, strlen($data)-1)=='=') {
				$GLOBALS['isRSA'] = true;
				$rsa = new Rsa(SDK_PATH . '/class/encrypt/keys', "{$tbp}private", "{$tbp}public");
				$data = $rsa->privDecode($data);
			}
			$json = json_decode($data, true);
			if (is_null($json)) {
				$arr = explode('&', $data);
				foreach ($arr as $g) {
					$gs = explode('=', $g);
					if (count($gs)>1) $_POST[$gs[0]] = $gs[1];
				}
			} else {
				foreach ($json as $key => $val) $_POST[$key] = $val;
			}
		}
	}
	return $_POST;
}

//CURL方式请求
function requestCurl($method, $url, $params=array(), $returnJson=false, $postJson=false, $headers=array(), $getHeader=false) {
	set_time_limit(0);
	ini_set('memory_limit', '10240M');
	$method = strtoupper($method);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60*60); //请求超时
	curl_setopt($ch, CURLOPT_TIMEOUT, 60*60); //执行超时
	if ($getHeader) curl_setopt($ch, CURLOPT_HEADER, 1);
	if (isset($_SERVER['HTTP_USER_AGENT'])) curl_setopt($ch, CURLOPT_USERAGENT, implode(' ', array_filter(array($_SERVER['HTTP_USER_AGENT'], 'SDK/'.API_VERSION.' PHP/'.PHP_VERSION))));
	switch ($method){ //请求方式
		case 'POST':curl_setopt($ch, CURLOPT_POST, 1);break;
		case 'PUT':
		case 'PATCH':
		case 'DELETE':curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);break;
		default:curl_setopt($ch, CURLOPT_HTTPGET, 1);break;
	}
	if (is_array($headers) && count($headers)) {
		$headers[] = "X-HTTP-Method-Override: {$method}"; //HTTP头信息
	} else {
		$headers = array("X-HTTP-Method-Override: {$method}");
	}
	if (is_array($headers) && count($headers)) {
		//使用JSON提交
		if ($postJson) {
			$headers[] = 'Content-type: application/json;charset=UTF-8';
			if (!empty($params) && is_array($params)) $params = json_encode($params, JSON_UNESCAPED_UNICODE);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	if (substr($url, 0, 8)=='https://') {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //从证书中检查SSL加密算法是否存在
		//curl_setopt($ch, CURLOPT_SSLVERSION, 3); //SSL版本
	}
	if (!empty($params)) {
		if (is_array($params)) {
			if (class_exists('\CURLFile')) {
				foreach ($params as $key => $param) {
					if (is_string($param) && preg_match('/^@/', $param)) $params[$key] = new CURLFile(realpath(trim($param, '@')));
				}
			} else {
				if (defined('CURLOPT_SAFE_UPLOAD')) curl_setopt($ch, CURLOPT_SAFE_UPLOAD, 0); //指定PHP5.5及以上兼容@语法,否则需要使用CURLFile
			}
		}
		//如果data为数组即使用multipart/form-data提交, 为字符串即使用application/x-www-form-urlencoded
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}
	$res = curl_exec($ch);
	if ($getHeader) {
		//$headers = curl_getinfo($ch);
		//var_dump($heards);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); //获取请求头信息
		if ($http_code == 301 || $http_code == 302) {
			$headers = explode("\r\n", $res);
			$header = '';
			foreach ($headers as $_header) {
				if (preg_match('/^Location: (.+)$/', $_header)) {
					$header = $_header;
					break;
				}
			}
			preg_match('/^Location: (.+)$/', $header, $matcher);
			$url = $matcher[1];
			$res = requestCurl($method, $url, $params, false, $postJson, $headers, $getHeader);
		}
	}
	if ($res === false) {
		echo 'Curl error: ' . curl_error($ch);
		exit;
	}
	curl_close($ch);
	if ($returnJson) {
		$res = json_decode($res, true);
		if (is_null($res)) $res = NULL;
	}
	if (is_null($res)) write_log(print_r($res, true));
	return $res;
}
function requestData($method, $url, $params=array(), $returnJson=false, $postJson=false, $headers=array(), $getHeader=false) {
	set_time_limit(0);
	ini_set('memory_limit', '10240M');
	$method = strtoupper($method);
	$urls = is_array($url) ? array_merge(array(), $url) : array($url);
	$ch = array();
	$res = array();
	$cm = curl_multi_init(); //创建批处理cURL句柄
	foreach ($urls as $k => $_url) {
		$_params = is_array($params) ? array_merge(array(), $params) : array();
		$_headers = is_array($headers) ? array_merge(array(), $headers) : array();
		$ch[$k] = curl_init();
		curl_setopt($ch[$k], CURLOPT_URL, $_url);
		curl_setopt($ch[$k], CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch[$k], CURLOPT_CONNECTTIMEOUT, 60*60); //请求超时
		curl_setopt($ch[$k], CURLOPT_TIMEOUT, 60*60); //执行超时
		if ($getHeader) curl_setopt($ch[$k], CURLOPT_HEADER, 1);
		if (isset($_SERVER['HTTP_USER_AGENT'])) curl_setopt($ch[$k], CURLOPT_USERAGENT, implode(' ', array_filter(array($_SERVER['HTTP_USER_AGENT'], 'SDK/'.API_VERSION.' PHP/'.PHP_VERSION))));
		switch ($method) { //请求方式
			case 'POST':curl_setopt($ch[$k], CURLOPT_POST, 1);break;
			case 'PUT':
			case 'PATCH':
			case 'DELETE':curl_setopt($ch[$k], CURLOPT_CUSTOMREQUEST, $method);break;
			default:curl_setopt($ch[$k], CURLOPT_HTTPGET, 1);break;
		}
		if (is_array($headers) && count($headers)) {
			$_headers[] = "X-HTTP-Method-Override: {$method}"; //HTTP头信息
		} else {
			$_headers = array("X-HTTP-Method-Override: {$method}");
		}
		if (is_array($headers) && count($headers)) {
			//使用JSON提交
			if ($postJson) {
				$_headers[] = 'Content-type: application/json;charset=UTF-8';
				if (!empty($params) && is_array($_params)) $_params = json_encode($_params, JSON_UNESCAPED_UNICODE);
			}
			curl_setopt($ch[$k], CURLOPT_HTTPHEADER, $_headers);
		}
		if (substr($_url, 0, 8)=='https://') {
			curl_setopt($ch[$k], CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
			curl_setopt($ch[$k], CURLOPT_SSL_VERIFYHOST, 0); //从证书中检查SSL加密算法是否存在
			//curl_setopt($ch, CURLOPT_SSLVERSION, 3); //SSL版本
		}
		if (!empty($params)) {
			if (is_array($_params)) {
				if (class_exists('\CURLFile')) {
					foreach ($_params as $key => $param) {
						if (is_string($param) && preg_match('/^@/', $param)) $_params[$key] = new CURLFile(realpath(trim($param, '@')));
					}
				} else {
					if (defined('CURLOPT_SAFE_UPLOAD')) curl_setopt($ch[$k], CURLOPT_SAFE_UPLOAD, 0); //指定PHP5.5及以上兼容@语法,否则需要使用CURLFile
				}
			}
			//如果data为数组即使用multipart/form-data提交, 为字符串即使用application/x-www-form-urlencoded
			curl_setopt($ch[$k], CURLOPT_POSTFIELDS, $_params);
		}
		//附加 Authorization: Basic <Base64(id:key)>
		//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		//curl_setopt($ch, CURLOPT_USERPWD, "api:{$key}");
		curl_multi_add_handle($cm, $ch[$k]); //加入多处理句柄
	}
	$active = NULL; //连接数
	do { //防卡死写法,执行批处理句柄
		//这里$active会被改写成当前未处理数,全部处理成功$active会变成0
		$mrc = curl_multi_exec($cm, $active);
		//这个循环的目的是尽可能的读写，直到无法继续读写为止(返回CURLM_OK)
		//返回(CURLM_CALL_MULTI_PERFORM)就表示还能继续向网络读写
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	//curl_multi_select的作用在等待过程中，如果有就返回目前可以读写的句柄数量,以便
	//继续读写操作,0则没有可以读写的句柄(完成了)
	while ($active && $mrc == CURLM_OK) { //直到出错或者全部读写完毕
		while (curl_multi_exec($cm, $active) === CURLM_CALL_MULTI_PERFORM);
		if (curl_multi_select($cm) != -1) {
			do {
				$mrc = curl_multi_exec($cm, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}
	}
	foreach ($urls as $k => $_url) {
		//$info = curl_multi_info_read($cm); //获取当前解析的cURL的相关传输信息
		$res[$k] = curl_multi_getcontent($ch[$k]); //获取输出的文本流
		if ($getHeader) {
			//$headers = curl_getinfo($ch[$k]);
			//var_dump($heards);
			$http_code = curl_getinfo($ch[$k], CURLINFO_HTTP_CODE); //获取请求头信息
			if ($http_code == 301 || $http_code == 302) {
				$headers = explode("\r\n", $res[$k]);
				$header = '';
				foreach ($headers as $_header) {
					if (preg_match('/^Location: (.+)$/', $_header)) {
						$header = $_header;
						break;
					}
				}
				preg_match('/^Location: (.+)$/', $header, $matcher);
				$_url = $matcher[1];
				$res[$k] = requestData($method, $_url, $params, false, $postJson, $headers, $getHeader);
			}
		}
		$result = $res[$k];
		if ($res[$k] === false) {
			echo 'Curl error: ' . curl_error($ch[$k]);
			exit;
		}
		curl_multi_remove_handle($cm, $ch[$k]); //移除curl批处理句柄资源中的某个句柄资源
		curl_close($ch[$k]);
		if ($returnJson) {
			$res[$k] = is_array($res[$k]) ? $res[$k] : json_decode($res[$k], true);
			if (is_null($res[$k])) $res[$k] = NULL;
		}
		if (is_null($res[$k])) write_log(print_r($result, true));
	}
	curl_multi_close($cm);
	if (is_array($url)) {
		return $res;
	} else {
		$values = array_values($res);
		return $values[0];
	}
}

//异步调用PHP, 例如处理完客户端需要的数据就返回, 再调用该函数异步在服务器执行耗时的操作
function requestAsync($method, $url, $param=array(), $header=array()) {
	//当执行过程中,客户端连接断开或连接超时,都会有可能造成执行不完整,因此目标网址程序需要加上
	//ini_set('ignore_user_abort', true);
	//ignore_user_abort(true); //忽略客户端断开
	//set_time_limit(0); //设置执行不超时
	$method = strtoupper($method);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	if ($method == 'POST') {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, empty($param) ? '' : http_build_query($param));
	}
	if (is_array($header)) {
		$headers = array('Content-type: application/x-www-form-urlencoded');
		foreach ($header as $h) $headers[] = $h;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	if (substr($url, 0, 8) == 'https://') {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}
	curl_exec($ch);
	curl_close($ch);
}

//获取redis
function redisd($host='127.0.0.1', $port=6379) {
	//https://www.cnblogs.com/peteremperor/p/6635778.html
	//命令 /usr/local/redis/bin/redis-cli
	//查看所有key(keys *)
	//获取指定key的值(get KEY)
	//清空(flushall)
	if (!class_exists('Redis')) exit('MISSING CLASS REDIS');
	$instance = new Redis();
	$instance->connect($host, $port);
	return $instance;
}

//获取memcached
function mcached($host='127.0.0.1', $port=11211) {
	if (!class_exists('Memcached')) exit('MISSING CLASS MEMCACHED');
	$instance = new Memcached();
	$instance->connect($host, $port);
	return $instance;
}

//404错误
function error404() {
	header('HTTP/1.1 404 Not Found.', TRUE, 404);
	exit(1);
}

//503错误
function error503() {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	exit(1);
}

//友好提示
function error_tip($tips='') {
	$icon = $iconStyle = $textStyle = '';
	$iconWidth = $iconHeight = '1.5rem';
	$bgColor = '#ebfaff';
	$countDown = true;
	if (is_array($tips)) {
		if (isset($tips['icon'])) $icon = $tips['icon'];
		if (isset($tips['iconWidth'])) $iconWidth = $tips['iconWidth'];
		if (isset($tips['iconHeight'])) $iconHeight = $tips['iconHeight'];
		if (isset($tips['bgColor'])) $bgColor = $tips['bgColor'];
		if (isset($tips['iconStyle'])) $iconStyle = $tips['iconStyle'];
		if (isset($tips['textStyle'])) $textStyle = $tips['textStyle'];
		if (isset($tips['countDown'])) $countDown = $tips['countDown'];
		$tips = $tips['tips'];
	}
	if (defined('IS_API') && IS_API) error($tips);
	$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	$html = '<!doctype html>
<html lang="zh-CN" style="font-size:100px;">
<head>
<meta name="viewport" content="width=320,minimum-scale=1.0,maximum-scale=1.0,initial-scale=1.0,user-scalable=0" />
<meta name="format-detection" content="telephone=no" />
<meta name="format-detection" content="email=no" />
<meta name="format-detection" content="address=no" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta charset="UTF-8">
<title>'.$tips.'</title>
</head>
<body style="background:'.$bgColor.';">
<style>
html, body{height:100%; margin:0; padding:0; position:relative; text-align:center; font-family:Arial; -webkit-font-smoothing:antialiased;}
.tip-view{position:absolute; left:0; top:50%; width:100%; height:auto; margin-top:-0.5rem; -webkit-transform:translateY(-50%); transform:translateY(-50%);}
.tip-view i{display:block; margin:0 auto; width:1.5rem; height:1.5rem; background:no-repeat center center;'.(!strlen($icon)?" background-image:url(\"data:image/png;base64,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\");":'').' background-size:cover;}
.tip-view span{display:block; width:100%; height:0.34rem; line-height:0.34rem; font-size:0.18rem;}
.tip-view font{display:block; width:100%; height:0.2rem; line-height:0.2rem; font-size:0.14rem; color:#ccc;}
.tip-view strong{font-weight:normal;}
</style>
<div class="tip-view">
	<i'.(strlen($icon)?" style=\"background-image:url({$icon});width:{$iconWidth};height:{$iconHeight};{$iconStyle}\"":'').'></i>
	<span'.(strlen($textStyle)?" style=\"{$textStyle}\"":'').'>'.$tips.'</span>
	'.($countDown && (strlen($referer))?'<font>That will be return after at <strong>5</strong>s</font>':'').'
</div>
</body>
</html>';
if (($countDown && strlen($referer))) $html .= PHP_EOL.'<script>
var count = 5, timer = setInterval(function(){
	if(count<=0){
		clearInterval(timer);timer = null;
		history.back();
		return;
	}
	count--;
	let strong = document.getElementsByTagName("strong");
	if(strong.length)strong[0].innerHTML = count;
}, 1000);
</script>';
	echo $html;
	exit;
}

//错误展示
function error($msg='数据错误', $msg_type=0, $error=1, $isJson=false) {
	global $json, $request;
	if (defined('WX_LOGIN') && WX_LOGIN && ($msg_type==-9 || $msg_type==-100)) {
		//$msg = '';
		//$msg_type = -10;
	}
	$json['error'] = $error;
	$json['msg_type'] = $msg_type;
	$json['msg'] = $msg;

	//后台
	if ((defined('IS_AG') && IS_AG) || (defined('IS_GM') && IS_GM) || (defined('IS_OP') && IS_OP)) {
		//api
		if (IS_API) {
			$gourl = $request->request('gourl');
			if (strlen($gourl)) {
				$html = '<meta charset="UTF-8"><script>';
				if (strlen($msg)) $html .= "alert('{$msg}');";
				$html .= "history.back();</script>";
				exit($html);
			}

			die(json_encode($json));
		}

		//html
		if ($isJson) {
			die(json_encode($json));
		} else {
			$html = "<meta charset=\"UTF-8\">";
			$html .= "<script>alert('{$msg}');".(is_string($msg_type)?"location.href='{$msg_type}'":"history.back();")."</script>";
			exit($html);
		}
	}

	//前端
	//api
	if (IS_API) {
		if (is_string($msg_type)) {
			$html = '<meta charset="UTF-8">';
			$is_app = IS_APP ? 1 : 0;
			if ($is_app) {
				$html .= '<script type="text/javascript" src="/js/jquery-3.4.1.min.js"></script>
				<script type="text/javascript" src="/js/coo.js"></script>
				<script type="text/javascript" src="/js/common.js"></script>';
				$html .= "<script>$(function(){alert('{$msg}');history.back()})</script>";
			} else {
				$html .= "<script>alert('{$msg}');location.href='{$msg_type}';</script>";
			}
			exit($html);
		} else {
			$gourl = $request->request('gourl');
			if (strlen($gourl)) {
				$html = '<meta charset="UTF-8"><script>';
				if (strlen($msg)) $html .= "alert('{$msg}');";
				$html .= "history.back();</script>";
				exit($html);
			}
		}

		die(json_encode($json));
	}

	//html
	//重新登录
	if ($msg_type==-100) {
		$gourl = '/wap/?tpl=login';
		if (defined('EXTEND_TEMPLATE_PATH')) {
			if (file_exists(EXTEND_TEMPLATE_PATH . '/home.login.html')) $gourl = '/wap/home/login';
		} else if (!file_exists(TEMPLATE_PATH . '/login.html')) {
			if (file_exists(TEMPLATE_PATH . '/home.login.html')) $gourl = '/wap/home/login';
			else $gourl = '/wap/?app=passport&act=wx_login';
		}
		location($gourl);
	}
	//AJAX请求,bodyView的时候用
	if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){
		die(json_encode($json));
	}
	//商品下架
	if ($msg_type==-99) {
		echo '<meta charset="UTF-8"><script>alert("该商品已经被抢光，库存不足已下架。");location.href="/wap/";</script>';
		exit;
	}
	$errorto = $request->request('errorto');
	if ($errorto!='') {
		$html = "<meta charset='UTF-8'><script>alert('{$msg}');";
		$sign = '';
		if (isset($_SESSION['member'])) $sign = isset($_SESSION['member']->sign) ? $_SESSION['member']->sign : '';
		$errorto = str_replace('<#sign#>', $sign, $errorto);
		if (stripos($errorto, 'history.back()')!==false || stripos($errorto, 'history.go(-1)')!==false) {
			$errorto = stripcslashes($errorto);
			$html .= $errorto;
		} else {
			$errorto = stripcslashes($errorto);
			$html .= 'location.href="'.$errorto.'"';
		}
		$html .= '</script>';
		exit($html);
	}
	switch ($msg_type) {
		case -9:
			echo "<meta charset='UTF-8'><script>".(strlen($msg)?"alert('{$msg}');":"")."location.href='/api/?app=passport&act=logout&gourl=".urlencode('/wap/?app=member&act=index')."';</script>";
			break;
		case -1:
			echo "<meta charset='UTF-8'><script>".(strlen($msg)?"alert('{$msg}');":"")."location.href='/wap/';</script>";
			break;
		default:
			$html = '<meta charset="UTF-8">';
			$is_app = IS_APP ? 1 : 0;
			if ($is_app) {
				$html .= '<script type="text/javascript" src="/js/jquery-3.4.1.min.js"></script>
				<script type="text/javascript" src="/js/coo.js"></script>
				<script type="text/javascript" src="/js/common.js"></script>';
				$html .= "<script>$(function(){alert('{$msg}');history.back()})</script>";
			} else {
				$html .= "<script>alert('{$msg}');".(is_string($msg_type)?"location.href='{$msg_type}'":"history.back();")."</script>";
			}
			echo $html;
			break;
	}
	exit;
}

//成功展示
function success($data, $msg='成功', $msg_type=0, $element=array(), $gourl='', $goalert='') {
	global $tbp, $app, $act, $smarty, $json, $request, $tpl, $isRSA, $edition, $function, $jssdk;
	$is_app = IS_APP ? 1 : 0;
	$is_wx = IS_WX ? 1 : 0;
	$is_mini = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'https://servicewechat.com/wx')!==false) ? 1 : 0;

	//后台
	if ((defined('IS_AG') && IS_AG) || (defined('IS_GM') && IS_GM) || (defined('IS_OP') && IS_OP)) {
		//api
		if (IS_API) {
			if (stripos($msg, '.html')!==false) $msg = '成功';
			$json['data'] = $data;
			$json['msg_type'] = $msg_type;
			$json['msg'] = $msg;
			if (is_array($element)) foreach ($element as $key=>$val) $json[$key] = $val;

			$gourl = $request->request('gourl', $gourl);
			$goalert = $request->request('gourl', $goalert);
			if (strlen($gourl)) {
				$html = '<meta charset="UTF-8"><script>';
				if (strlen($goalert)) $html .= "alert('{$goalert}');";
				$html .= "location.href = '{$gourl}';</script>";
				exit($html);
			}

			$str = str_replace(':[]', ':null', json_encode($json));
			die($str);
		}

		//html
		if (stripos($msg, '.html')!==false) {
			$template_file = $msg;
		} else {
			$template_file = "{$app}.{$act}.html";
		}
		if (defined('EXTEND_APP')) {
			$EXTEND_APP = json_decode(EXTEND_APP);
			foreach ($EXTEND_APP as $extend) {
				$IS_LAST = $EXTEND_APP[count($EXTEND_APP)-1] == $extend;
				if (!file_exists(APPLICATION_PATH . '/' . $extend . '/view/' . $template_file)) {
					if ($IS_LAST) {
						if (!file_exists(TEMPLATE_PATH . '/' . $template_file)) {
							error_tip('TEMPLATE DOES NOT EXISTS');
						}
					}
				}
			}
		} else {
			if (!defined('EXTEND_TEMPLATE_PATH') || !file_exists(EXTEND_TEMPLATE_PATH . '/' . $template_file)) {
				if (!file_exists(TEMPLATE_PATH . '/' . $template_file)) {
					error_tip('TEMPLATE DOES NOT EXISTS');
				}
			}
		}

		$smarty->assign($data);
		$smarty->assign($_GET);
		$smarty->assign($_POST);
		if (is_array($element)) $smarty->assign($element);
		$smarty->assign('app', $app);
		$smarty->assign('act', $act);
		$smarty->assign('domain', https().$_SERVER['HTTP_HOST']);
		$smarty->assign('WEB_NAME', defined('WEB_NAME') ? WEB_NAME : '');
		if (isset($_SESSION['admin'])) $smarty->assign('admin', $_SESSION['admin']);

		$output = $request->request('output');
		if ($output=='json'){
			$vars = $smarty->getTemplateVars();
			unset($vars['output']);
			echo json_encode($vars);
			exit;
		}

		$gourl = $request->request('gourl', $gourl);
		$goalert = $request->request('goalert', $goalert);
		if (strlen($gourl)) {
			$html = '<meta charset="UTF-8"><script>';
			if (strlen($goalert)) $html .= "alert('{$goalert}');";
			$html .= "location.href = '{$gourl}';</script>";
			exit($html);
		}

		$smarty->display($template_file, md5($_SERVER['REQUEST_URI']));
		exit;
	}

	//前端
	//api
	if (IS_API) {
		$json['data'] = $data;
		$json['msg_type'] = $msg_type;
		$json['msg'] = $msg;
		//$json['edition'] = intval($edition); //系统功能版本
		//$json['function'] = $function; //系统功能
		//$json['red_dot'] = 1; //tabBar角标使用红点代替badge

		if ($is_mini) {
			$core = o('core');
			$core->check_facade('miniprogram');
		}

		if (isset($_SESSION['member']) && isset($_SESSION['member']->id) && $_SESSION['member']->id>0 && SQL::share()->tableExist('cart')) {
			$member = o('member');
			$num = $member->_get_cart_count();
			$json['member_cart'] = $num;
			$num = $member->_get_message_count();
			$num += $member->_get_status_order_count(1);
			$num += $member->_get_status_order_count(2);
			$num += $member->_get_status_order_count(3);
			$json['member_notify'] = $num;
		}

		if (is_array($element)) foreach ($element as $key=>$val) $json[$key] = $val;

		if (!$is_app && $request->request('source')!='ios' && $request->request('source')!='android') {
			$gourl = $request->request('gourl', $gourl);
			$goalert = $request->request('goalert', $goalert);
			if (!strlen($gourl)) $gourl = $request->session('gourl', $gourl);
			$_SESSION['gourl'] = '';
			if (strlen($gourl)) {
				$html = '<meta charset="UTF-8"><script>';
				if (strlen($goalert)) $html .= "alert('{$goalert}');";
				if (stripos($gourl, 'commit')!==false) {
					$html .= 'history.go(-2);';
				} else if (substr($gourl, 0, 11)=='javascript:') {
					$html .= substr($gourl, 11);
				} else {
					$html .= "location.href = '{$gourl}';";
				}
				$html .= '</script>';
				exit($html);
			}
		}

		$str = str_replace(':[]', ':null', json_encode($json));

		if (defined('RSA_POST') && RSA_POST && isset($isRSA) && $isRSA) {
			$rsa = new Rsa(SDK_PATH . '/class/encrypt/keys', "{$tbp}private", "{$tbp}public");
			$str = $rsa->privEncode($str);
		}

		die($str);
	}

	//html
	if (stripos($msg, '.html')!==false) {
		$template_file = $msg;
	} else {
		if (preg_match('/^[a-z0-9._]+$/', $tpl)) {
			$template_file = "{$tpl}.html";
		} else {
			$template_file = "{$app}.{$act}.html";
		}
	}
	if (!defined('EXTEND_TEMPLATE_PATH') || !file_exists(EXTEND_TEMPLATE_PATH.'/'.$template_file)) {
		if (!file_exists(TEMPLATE_PATH.'/'.$template_file)) {
			error_tip('TEMPLATE DOES NOT EXISTS');
		}
	}

	$core = o('core');
	$core->check_facade("wap' OR facade='pc", $act=='wxtool');

	$sign = '';
	if (isset($_SESSION['member']) && isset($_SESSION['member']->id)) {
		$smarty->assign('logined', 1);
		$_SESSION['member'] = add_domain_deep($_SESSION['member'], array("avatar"));
		$_SESSION['member']->reg_time_word = date('Y-m-d', $_SESSION['member']->reg_time);
		$member = $_SESSION['member'];
		$smarty->assign('member', $member);
		if (isset($member->sign)) $sign = $member->sign;
	} else {
		$smarty->assign('logined', 0);
		$member = t('member');
		$member->id = 0;
		$member->avatar = add_domain('/images/avatar.png');
		$smarty->assign('member', $member);
	}
	$smarty->assign('data', $data);

	$smarty->assign('edition', $edition);

	if (SQL::share()->tableExist('cart')) {
		$cart = o('cart');
		$carts = $cart->total(false);
		$smarty->assign('cart_notify', $carts['quantity']);
	}

	$smarty->assign($_GET);
	$smarty->assign($_POST);
	if (is_array($element)) $smarty->assign($element);

	$vars = $smarty->getTemplateVars();
	$url = $request->request('url');
	$smarty->assign('url', $url);
	$smarty->assign('is_app', $is_app); //是否公司项目APP内打开网页
	$smarty->assign('is_wx', $is_wx);
	$smarty->assign('is_mini', $is_mini);
	$smarty->assign('app', $app);
	$smarty->assign('act', $act);
	$smarty->assign('domain', https().$_SERVER['HTTP_HOST']);
	if (!isset($vars['WEB_TITLE'])) $smarty->assign('WEB_TITLE', defined('WEB_TITLE') ? WEB_TITLE : '');
	$smarty->assign('WEB_NAME', defined('WEB_NAME') ? WEB_NAME : '');
	$smarty->assign('OSS_DOMAIN', defined('OSS_DOMAIN') ? OSS_DOMAIN : '');
	$smarty->assign('CLICK_DOMAIN', defined('CLICK_DOMAIN') ? CLICK_DOMAIN : '');
	//$smarty->assign('WEB_DESCRIPTION', WEB_DESCRIPTION);
	//$smarty->assign('WEB_KEYWORDS', WEB_KEYWORDS);
	//$smarty->assign('cache_control', '86400');
	//$smarty->assign('cache_expires', gmdate('D, d M Y H:i:s', strtotime('+1 day')).' GMT');

	$gourl = $request->request('gourl', $gourl);
	$goalert = $request->request('goalert', $goalert);
	if (!strlen($gourl)) $gourl = $request->session('gourl', $gourl);
	$_SESSION['gourl'] = '';
	if (strlen($gourl)) {
		$html = '<meta charset="UTF-8"><script>';
		if (strlen($goalert)) $html .= "alert('{$goalert}');";
		if (stripos($gourl, 'commit')!==false) {
			$html .= 'history.go(-2);';
		} else if (substr($gourl, 0, 11)=='javascript:') {
			$html .= substr($gourl, 11);
		} else {
			$html .= "location.href = '{$gourl}';";
		}
		$html .= '</script>';
		exit($html);
	}

	//分享
	if (isset($jssdk) && is_bool($jssdk) && $jssdk) {
		$qs = '';
		if(strlen($_SERVER['QUERY_STRING']))$qs .= '?'.$_SERVER['QUERY_STRING'];
		$link = https().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$qs;
		$link = str_replace("/?reseller=\d+&?/", '?', str_replace("/&reseller=\d+/", '', $link));
		$qrcode = $request->get('qrcode');
		if(!strlen($qrcode) && $member->id>0)$link .= (strpos($link, '?')!==false?'&':'?') . "reseller={$member->id}";
		$share_title = defined('SHARE_TITLE') ? SHARE_TITLE : '';
		$share_desc = defined('SHARE_DESC') ? SHARE_DESC : '';
		$share_link = $link;
		$share_img = add_domain_deep(defined('SHARE_IMG') ? SHARE_IMG : '');
		if ($app=='goods' && $act=='detail') {
			$share_title = $data->name;
			$share_desc = $data->description;
			$share_img = add_domain_deep($data->pic);
		}
		$jssdk = new wechatCallbackAPI();
		$jssdk = $jssdk->getSignPackage();
		$jssdk['share'] = array(
			'title'=>$share_title,
			'desc'=>$share_desc,
			'link'=>$share_link,
			'img'=>$share_img
		);
		$smarty->assign('jssdk', 'Mario'.base64_encode(json_encode($jssdk)));
	}

	$output = $request->request('output');
	if ($output=='json'){
		unset($vars['output']);
		echo json_encode($vars);
		exit;
	}
	$smarty->display($template_file, md5($_SERVER['REQUEST_URI']));
	exit;
}
