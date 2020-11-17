<?php
//Developed by @mario 20200706
/*
快捷方式
*/
class shortcut {
	private static $model = array();
	private static $classs = array();
	private static $app = array();
	private static $plugin = array();
	private static $request = array();
	
	public function __construct() {
		//
	}
	
	//实例化一个模型类
	public static function model($model) {
		$instance = NULL;
		if (!isset(self::$model[$model])) {
			$class = "{$model}_model";
			if (defined('EXTEND_TEMPLATE_PATH')) {
				$file = EXTEND_TEMPLATE_PATH . "/{$model}.model.php";
				if (file_exists($file)) {
					require_once($file);
					$instance = self::$model[$model] = new $class;
				}
			}
			if (!$instance) {
				$file = MODEL_PATH . "/{$model}.model.php";
				if (!file_exists($file)) {
					error('MISSING MODEL FILE: '.strtoupper($model));
				}
				require_once($file);
				if (!class_exists($class)) {
					error('MISSING MODEL: '.strtoupper($model));
				}
				$instance = self::$model[$model] = new $class();
			}
		} else {
			$instance = self::$model[$model];
		}
		return $instance;
	}
	
	//实例化一个类
	public static function classs($app='', $path='api') {
		if (!strlen($app)) return new stdClass();
		$instance = NULL;
		if (!isset(self::$classs[$app])) {
			if (defined('EXTEND_TEMPLATE_PATH')) {
				$file = EXTEND_TEMPLATE_PATH . "/{$app}.php";
				if (file_exists($file)) {
					require_once($file);
					if (class_exists($app)) {
						$instance = self::$classs[$app] = new $app();
					}
				}
			}
			if (!$instance) {
				$file = APPLICATION_PATH . "/{$path}/controller/{$app}.php";
				if (!file_exists($file)) {
					error('MISSING CONTROLLER FILE: '.strtoupper($path).'/'.strtoupper($app));
				}
				require_once($file);
				if (!class_exists($app)) {
					error('MISSING CONTROLLER: '.strtoupper($app));
				}
				$instance = self::$classs[$app] = new $app();
			}
		} else {
			$instance = self::$classs[$app];
		}
		return $instance;
	}
	
	//根据表字段实例化一个字典类
	public static function app($table='', $fields='*') {
		if (!strlen($table)) return new stdClass();
		$instance = NULL;
		if (!isset(self::$app[$table])) {
			global $tbp;
			$is_sqlite3 = false;
			$share = SQL::share($table);
			if (substr($table, 0, 1) == ':') {
				$is_sqlite3 = true;
				$t = substr($table, 1);
				$desc = $share->query("PRAGMA table_info([{$t}])");
			} else {
				$t = $tbp.$table;
				$desc = $share->query("SHOW COLUMNS FROM {$t}");
				//SHOW FULL COLUMNS FROM table //数据表结构(包括注释)
				//SHOW TABLE STATUS //数据表与注释
				//SHOW TABLE STATUS LIKE 'table' //指定数据表
			}
			if (strlen($fields) && $fields != '*') {
				$fieldsArr = array();
				$fields = explode(',', $fields);
				foreach ($fields as $f) $fieldsArr[] = trim($f);
				$fields = $fieldsArr;
			}
			$app = new stdClass();
			foreach ($desc as $g) {
				if (is_array($fields) || $fields == '*') {
					if (is_array($fields) && !in_array($g->Field, $fields)) continue;
					if (preg_match("/^(char|varchar|text|tinytext|mediumtext|longtext)/i", $is_sqlite3 ? $g->type : $g->Type)) {
						if ($is_sqlite3) {
							$app->{$g->name} = $g->dflt_value ? $g->dflt_value : '';
						} else {
							$app->{$g->Field} = $g->Default ? $g->Default : '';
						}
					} else if (isset($g->Field) && $g->Field == 'id') {
						$app->{$g->Field} = 0;
					} else if (isset($g->name) && $g->name == 'id') {
						$app->{$g->name} = 0;
					} else {
						if (preg_match("/^(float|decimal|double|numeric)/i", $is_sqlite3 ? $g->type : $g->Type)) {
							if ($is_sqlite3) {
								$app->{$g->name} = $g->dflt_value ? floatval($g->dflt_value) : 0;
							} else {
								$app->{$g->Field} = $g->Default ? floatval($g->Default) : 0;
							}
						} else {
							if ($is_sqlite3) {
								$app->{$g->name} = $g->dflt_value ? intval($g->dflt_value) : 0;
							} else {
								$app->{$g->Field} = $g->Default ? intval($g->Default) : 0;
							}
						}
					}
				}
			}
			$instance = self::$app[$table] = $app;
		} else {
			$instance = self::$app[$table];
		}
		return $instance;
	}
	
	//实例化一个插件
	public static function plugin($plugin, $type='') {
		$instance = NULL;
		if (!isset(self::$plugin[$plugin])) {
			$file = PLUGIN_PATH . "/{$plugin}/plugin.php";
			$type_file = PLUGIN_PATH . "/{$plugin}/{$type}.php";
			if (file_exists($file)) {
				if (!file_exists($type_file)) {
					error('MISSING PLUGIN: '.strtoupper($type));
				}
				require_once($file);
				$instance = self::$plugin[$plugin] = new $plugin($type);
			} else {
				error('MISSING PLUGIN FILE: '.strtoupper($plugin));
			}
		} else {
			$instance = self::$plugin[$plugin];
		}
		return $instance;
	}
	
	//获取 REQUEST_METHOD, ex: shortcut::request('method.name.default.type')
	public static function request($mark) {
		if (!isset(self::$request[$mark])) {
			global $request;
			$marks = explode('.', $mark);
			if (count($marks) == 1) {
				$array = array();
				switch (strtoupper($mark)) {
					case 'GET':$array = $_GET;break;
					case 'POST':$array = $_POST;break;
					case 'REQUEST':$array = $_REQUEST;break;
					case 'COOKIE':$array = $_COOKIE;break;
					case 'SERVER':$array = $_SERVER;break;
					case 'SESSION':$array = $_SESSION;break;
					case 'GLOBALS':$array = $GLOBALS;break;
				}
				$instance = self::$request[$mark] = $array;
			} else {
				$method = $marks[0];
				$name = $marks[1];
				$default = count($marks)>2 ? $marks[2] : '';
				$type = count($marks)>3 ? $marks[3] : 'string';
				switch ($type) {
					case 'int':$default = intval($default);break;
					case 'float':$default = floatval($default);break;
					case 'array':$default = array();break;
				}
				$instance = self::$request[$mark] = $request->act($name, $default, $type, $method);
			}
		} else {
			$instance = self::$request[$mark];
		}
		return $instance;
	}
}
