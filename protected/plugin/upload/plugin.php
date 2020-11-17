<?php
class upload extends plugin {
	public function __construct($type='') {
		parent::__construct();
		if (!strlen($type)) $type = 'ypyun';
		$this->setAPI(__CLASS__, $type);
	}
	
	public function upload($fileObj, $field, $dir='', $name='', $ext='jpg'){
		$result = $this->api->upload($fileObj, $field, $dir, $name, $ext);
		return $result;
	}
	
	public function delete($url) {
		$result = $this->api->delete($url);
		return $result;
	}
}
