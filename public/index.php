<?php
//error_reporting(0); //关闭错误报告
//error_reporting(E_ERROR | E_WARNING | E_PARSE); //报告runtime错误
//error_reporting(E_ALL & ~E_NOTICE); //报告E_NOTICE之外的所有错误
error_reporting(E_ALL & ~E_DEPRECATED); //报告E_DEPRECATED之外的所有错误
ini_set('display_errors', 1); //开启错误提示
header('Content-type: text/html; charset=utf-8');
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('APPLICATION_PATH', ROOT_PATH . '/application');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('SDK_PATH', ROOT_PATH . '/protected');
define('PLUGIN_PATH', SDK_PATH . '/plugin');
define('MODEL_PATH', APPLICATION_PATH . '/model');
require_once(SDK_PATH . '/global.php');
require_once(SDK_PATH . '/start.php');
