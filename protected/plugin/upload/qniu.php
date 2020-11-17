<?php
//七牛
//https://www.qiniu.com
require_once('Qiniu/autoload.php');
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;
class qniu extends upload_base {
	public function __construct() {
		parent::__construct();
	}
	
	public function upload($fileObj, $field=NULL, $dir='', $name='', $ext='jpg') {
		global $qiniu_bucketname, $qiniu_accessKey, $qiniu_secretKey, $qiniu_domain, $img_domain;
		if (!strlen($qiniu_bucketname) || !strlen($qiniu_accessKey) || !strlen($qiniu_secretKey)) {
			write_log('UPLOAD LOST API KEY', '/temp/uploadlog.txt');
			error('UPLOAD LOST API KEY');
			return false;
		}
		$fileEle = !$field ? $fileObj : $fileObj[$field];
		$file_array = array('file'=>'', 'width'=>0, 'height'=>0);
		if (is_array($fileEle) && isset($fileEle['type']) && stripos($fileEle['type'],'image/')!==false) {
			$size = getimagesize($fileEle['tmp_name']);
			$file_array['width'] = $size[0];
			$file_array['height'] = $size[1];
		}
		if (strlen($dir)) {
			$dir = trim($dir, '/');
			$file = $dir.'/'.$name.'.'.$ext;
		} else {
			$file = $name.'.'.$ext;
		}
		try {
			$auth = new Auth($qiniu_accessKey, $qiniu_secretKey);
			$token = $auth->uploadToken($qiniu_bucketname);
			$uploadMgr = new UploadManager();
			if (is_array($fileEle)) {
				list($ret, $err) = $uploadMgr->putFile($token, $file, isset($fileEle['tmp_name']) ? $fileEle['tmp_name'] : $fileEle[0]);
			} else {
				ini_set('memory_limit', '500M');
				list($ret, $err) = $uploadMgr->put($token, $file, $fileEle);
			}
			if ($err !== null) {
				error($err->message());
			} else {
				$file = $ret['key'];
				if (!isset($qiniu_domain) || !strlen($qiniu_domain)) $qiniu_domain = $img_domain;
				if (substr($qiniu_domain, -1)!='/') $qiniu_domain .= '/';
				if (substr($file, 0, 1)=='/') $file = substr($file, 1);
				$file_array['file'] = $qiniu_domain . $file;
			}
		}
		catch (Exception $e) {}
		return $file_array;
	}
	
	public function delete($url) {
		global $qiniu_bucketname, $qiniu_accessKey, $qiniu_secretKey, $qiniu_domain;
		if (strpos($url, $qiniu_domain)===false) return false;
		$key = str_replace($qiniu_domain.'/', '', $url);
		$auth = new Auth($qiniu_accessKey, $qiniu_secretKey);
		$mgr = new BucketManager($auth);
		$mgr->delete($qiniu_bucketname, $key);
		return true;
	}
}
