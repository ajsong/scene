<?php
define('DIRNAME', basename(dirname(__FILE__)));
define('APP_PATH', APPLICATION_PATH . '/' . DIRNAME . '/controller');
require_once(APPLICATION_PATH . '/helper.php');
define('TEMPLATE_PATH', APPLICATION_PATH . '/' . DIRNAME . '/view/' . PC_TEMPLATE);

//初始化smarty
//framework/class/smarty/libs/sysplugins/smarty_internal_resource_file.php
$smarty->setCompileDir(ROOT_PATH . '/temp/templates_c/' . DIRNAME . '/');
$smarty->setCacheDir(ROOT_PATH . '/temp/cache/' . DIRNAME . '/');
$smarty->setTemplateDir(TEMPLATE_PATH);
$smarty->caching = SMARTY_CACHING;
$smarty->cache_lifetime = SMARTY_CACHE_LIFETIME;
//$smarty->clearCache('home.index.html');
//$smarty->clearAllCache();

$isRSA = false; //供success函数输出值类型判断用
if (IS_POST) $_POST = getData(); //RSA加密获取数据(兼容非RSA传输)
$session_id = '';

if (preg_match("/^[a-zA-Z0-9_.-]+$/", $app) && preg_match("/^[a-zA-Z0-9_.-]+$/", $act)) {
	//如使用不同版本的参数ver，则调用此版本的类文件
	if (strlen($ver)) {
		if (file_exists(APP_PATH . "/{$app}_{$ver}.php")) $app = "{$app}_{$ver}";
	}
	$file = APP_PATH . "/{$app}.php";
	if (file_exists($file)) {
		require_once($file);
		if (class_exists($app)) {
			$class = new $app();
			$session_id = $class->session_id;
			$function = $class->function;
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
