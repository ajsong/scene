<?php
abstract class upload_base extends plugin_base {
	abstract function upload($fileObj, $field, $dir='', $name='', $ext='jpg');
	abstract function delete($url);
}