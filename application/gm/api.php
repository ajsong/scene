<?php
define('DIRNAME', basename(dirname(__FILE__)));
define('APP_PATH', APPLICATION_PATH . '/' . DIRNAME . '/controller');
require_once(APPLICATION_PATH . '/helper.php');
define('TEMPLATE_PATH', APPLICATION_PATH . '/' . DIRNAME . '/view');

//初始化smarty
//framework/class/smarty/libs/sysplugins/smarty_internal_resource_file.php
$smarty->setCompileDir(ROOT_PATH . '/temp/templates_c/gm/');
$smarty->setCacheDir(ROOT_PATH . '/temp/cache/gm/');
$smarty->setTemplateDir(TEMPLATE_PATH);

if (preg_match("/^[a-zA-Z0-9_.-]+$/", $app) && preg_match("/^[a-zA-Z0-9_.-]+$/", $act)) {
	$file = APP_PATH . "/{$app}.php";
	if (file_exists($file)) {
		require_once($file);
		if (class_exists($app)) {
			$class = new $app();
			if (method_exists($class, $act)) {
				$class->$act();
			} else {
				error_tip('MISSING METHOD');
			}
		} else {
			error_tip('MISSING CONTROLLER');
		}
	} else {
		error_tip('MISSING FILE');
	}
} else {
	error_tip('WRONG FILE');
}
